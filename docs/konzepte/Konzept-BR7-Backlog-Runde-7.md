# Konzept BR7 — Backlog-Runde 7, vor Schritt 13 (Werkzeugschritt ohne Fahrplanzeile)

**Kürzel:** BR7. Pakete `BR7-NN`, Entscheidungen `E-BR7-NN`, Befunde
`F-BR7-NN`, Fragen `Q-BR7-NN`, Prüfpunkte `P-BR7-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/admiring-sagan-mql8t4` (nach dem Merge von PR #153 frisch
von `main`, Entscheid der Betreiberin 05.11.2026) · **Herkunft:** Auftrag der
Betreiberin vom 05.11.2026 („Backlog-Runde vor Schritt 13 vorbereiten",
Umfang „Nr. 510 und eine Prüfrunde auf Staging", E-BR7-01) · **Backlog-Spanne:**
511 bis 519 (aus der Spanne von 24, derselbe Zweig) · **Prüfdokument:**
keines — es bleibt kein Handpunkt (BR7-03).

**Modell:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine, in keinem Paket (CLAUDE.md 7). Die Runde ist klein, und
jedes Paket endet mit Prüfarbeit an der einen örtlichen Anlage.

**Keine Fassungsnummer in diesem Dokument** — welche Zählung hochgeht,
entscheidet die Umsetzung (CLAUDE.md 2).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| BR7-01 | Schemaprobe ohne Griff an die `config.php` der Anlage: Nr. 510 | **erledigt** 06.11.2026 (nur Werkzeug, keine Fassung) |
| BR7-02 | Bedienprobe: Nachladeknopf auf 390 px (ersetzt P-R4-17) | **erledigt** 06.11.2026 (nur Werkzeug) |
| BR7-03 | Abschluss | **in Arbeit** — Pull Request |

**In Arbeit:** BR7-03. **Es hakt:** nirgends. Alle Fragen sind entschieden
(E-BR7-03 bis -07); BR7 läuft als Werkzeugschritt ohne Fahrplanzeile
(E-BR7-07). *(Bis zum 06.11.2026 stand hier ein Schritt 25 mit vier Paketen;
Runbook-Satz und Testkonto sind entfallen.)*

---

## 1. Auftrag

Unter `gehört zu: nächste Backlog-Runde` steht am 05.11.2026 genau ein Punkt,
Nr. 510. Die übrigen offenen Punkte gehören zu 13, 14, Zuarbeit, Pflege oder
„nach v1.0" — die Werkzeugpunkte von 14 ausdrücklich bis zur Inventur
(E-BE-04). Damit die Runde nicht nur aus einem Werkzeugpunkt besteht, ist
ihr eine **Prüfrunde auf Staging** vorgeschaltet: die offenen Punkte der
Prüfliste mit „Wer" **Claude** oder **beide** (10 und 6 Stück). Was dabei
scheitert, wird Backlog-Punkt und Teil der Runde (E-BR7-01).

**Warum vor 13:** wie bei BR6 — P7 beschreibt das Verhalten der 1.0 und
fasst fast jede Datei an; was vorher behoben ist, beschreibt P7 nicht.

**Nicht Teil:** Datenmodell und Migrationen; die Punkte mit `gehört zu: 14`
(E-BE-04); die Gerätetests (P-GR, P-BR6), die auf echter Hardware liegen.

## 2. Befund

Gelesen am 05.11.2026 auf `origin/main` `bfaca8b` (nach PR #153), nach den
Auslieferungen `web-v23.8.2`, `uhr-v4.3.2` und `android-v0.21.0` (Läufe 178
bis 180, alle grün).

### 2.1 Die Prüfrunde

**Bestanden und aus der Prüfliste gelöscht (vier Punkte):**

| Punkt | Beleg |
|---|---|
| P-PU-01 | PR #152, Lauf 42098312578, Job `Stufe 1`, Schritt „Prüfbericht gegenlesen": „Prüfbericht in Ordnung: Stufe neben …", darunter „Geerbt vom Vorgänger (Lage 8 nachgerechnet): …" mit 36 Proben |
| P-PU-04 | RD-04 (Web 23.8.0, Nebenstufe): Kopf „Umfang schmal", Engines nur Chromium, Breiten „fuenf", 2 742 s statt der 12 205 s des Bezugslaufs; Seiten „alle", weil auch Nicht-Seitendateien berührt waren |
| P-PU-06 | RD-06: „erbt 36 von 38 Proben", je Probe „erbt NAME — grün im Bericht von HEAD"; `kreislauf-edbak` und die Riegel gemessen; 93 s |
| P-BR-08 | Lauf 178 (`web-v23.8.2`), Job `produktion`, Schritt „Grüner Staging- und Stufe-1-Lauf": „Grüne Stufe-1-Läufe auf diesem Baum: 1" (PR #153, `f3c6bbc`) über `baumsuche.py`, Fenster 50 |

**Jetzt nicht prüfbar, weil an ein Ereignis gebunden (bleiben stehen):**
P-PU-05 (Zehnergrenze 23.10), P-PU-07 (Paket mit `style.css` in `neben`),
P-PR-01 (Lauf mit Anteil- und Freigabeprobe), P-PR-02 (Merge mit neuen
Backlog-Nummern), P-BR5-08 (ein gescheiterter Job).

**Teile der Betreiberin (Handy, Safari, Produktiv), bleiben stehen:**
P-R4-16, -21, -23, P-S11-23, -25 (b).

**Nicht prüfbar ohne Vorarbeit:** P-R4-13 und P-R4-17 → F-BR7-03, -04.

### 2.2 Befunde

**Nr. 510 — bestätigt.** `tools/schemaprobe/probe.php` legt im Abschnitt
„Wegwerf-config.php" die echte `server/config.php` beiseite, schreibt eine
eigene mit der Probe-Datenbank an ihre Stelle und holt die echte erst im
`register_shutdown_function` zurück. In diesem Fenster sieht jede Anfrage an
die örtliche Anlage eine Datenbank mit offenen Migrationen, und der
Torwächter schaltet die Wartung ein (F-RD-23).

**F-BR7-01 — Der Zeigerzweig `produktion` fehlte rund 26 Stunden, und die
Integritätswache war so lange rot.** Läufe 264 bis 271 (04.11.2026 21:59 bis
05.11.2026 23:42) mit „Der Zeigerzweig 'produktion' fehlt — die Wache hat
NICHT verglichen"; Lauf 263 (04.11.2026 12:08) war grün. Der Zweig ist also
zwischen diesen beiden Zeitpunkten verschwunden; die Ursache steht nirgends
(Q-BR7-01). Neu angelegt hat ihn der Job `zeiger` nach `web-v23.8.2`
(auf `bfaca8b`); Lauf 272 ist grün. **Bemerkt hat das Rot niemand**: Der
Alarmweg der Wache ist allein die Benachrichtigung von GitHub
(`integritaet.yml`, Kopf „DER ALARMWEG"). Gefunden hat es die Arbeitsumgebung
nebenbei, an einer 404 im Produktionslauf. Das Löschen zu sperren steht seit
dem 21.09.2026 als Zuarbeit in der Prüfliste („Kette II 9").

**F-BR7-02 — Ein Tag vor dem Ende des Staging-Laufs scheitert am Tor.**
`web-v23.8.2` wurde um 21:40:59 gesetzt, der Staging-Lauf 177 desselben
Commits lief noch; das Tor fand „Anerkannte Staging-Läufe: 0" und brach ab,
richtig. Erst „Re-run failed jobs" nach dem grünen Lauf 177 lieferte aus.
Kein Schaden — aber der Weg steht nirgends, und die Meldung des Laufs
(„GESCHEITERT … der Abgleich kann halb gelaufen sein") liest sich
gefährlicher, als die Lage war: Der Abbruch lag vor jedem Zugriff.

**F-BR7-03 — P-R4-13 ist auf Staging nicht prüfbar und wird es nicht.**
Es gibt kein SFTP-Sicherungsziel, und die Betreiberin hat keines
(05.11.2026).

**F-BR7-04 — P-R4-17 braucht ein Konto mit mehr als 200 Einsätzen.** Das
Demo-Konto trägt 106. Ein Prüfbestand ginge über den CSV-Import in ein
eigenes Testkonto auf Staging (`csv_import.mjs`, Dubletten werden erkannt,
also eine eigene Datei mit über 200 verschiedenen Einsätzen).

**F-BR7-05 — Die Prüfrunde hat keinen Fehler der Anwendung gefunden.**
Neben den vier bestandenen Punkten stand nichts an, was jetzt messbar war.
Mit Nr. 510 bleibt damit ein Werkzeugpunkt und, je nach Antwort, Doku und
ein Prüfbestand.

## 3. Fragen an die Betreiberin

| Nr. | Frage | Vorschlag | Paket | Antwort |
|---|---|---|---|---|
| Q-BR7-01 | **Woher kam das Fehlen von `produktion` (F-BR7-01)?** Hast du am 04.11.2026 zwischen 12:08 und 21:59 auf GitHub Zweige gelöscht (etwa die zwei toten aus P-R4-06) oder eine Regel geändert? | Wenn ja, ist die Ursache erklärt. Dann „Kette II 9" jetzt erledigen: Regel für `produktion` mit „Löschen sperren" und Push nur für GitHub Actions. | — | **Ja** — die Betreiberin hat am 04.11. zwei tote Zweige gelöscht, `produktion` ging mit. Ruleset „Zeiger produktion" mit „Restrict deletions" angelegt (06.11.2026), nur Löschsperre (E-BR7-03). |
| Q-BR7-02 | **Alarmweg der Wache.** (a) Nur die Einstellung: GitHub-Benachrichtigung für fehlgeschlagene Läufe an dein Konto und Mail prüfen. (b) Dazu ein Backlog-Punkt „die Wache meldet Rot über einen zweiten Weg" (z. B. ein Issue oder eine Statuszeile in Betrieb → Status), `gehört zu: 14` (Kette im neuen Repositorium). (c) Den zweiten Weg in BR7 bauen. | **(b)** — E-BE-04 schiebt Kettenarbeit nach 14; (a) ist sofort nötig. | — | **(a)** — die Betreiberin prüft ihre Benachrichtigungen; kein Backlog-Punkt (E-BR7-04). |
| Q-BR7-03 | **Weg für Nr. 510.** (a) Die Probe arbeitet auf einer Kopie von `server/` in einem Wegwerfverzeichnis und fasst die Anlage nie an. (b) `db.php` liest den Pfad der `config.php` aus einem Umgebungswert (Änderung unter `server/`, Web-Fassung, Registerzeile `konfig`). (c) Nach dem Zurücklegen nachsehen und ein von der Probe verursachtes `wartung.lock` entfernen. | **(a)** — nur Werkzeug, keine Fassung; (b) öffnet einen zweiten Weg zur Konfiguration, (c) räumt auf statt zu verhindern. | BR7-01 | **(a)** (E-BR7-05). |
| Q-BR7-04 | **P-R4-17 (F-BR7-04).** (a) Ein Testkonto auf Staging mit rund 220 erzeugten Einsätzen über den CSV-Import anlegen; es bleibt als Prüfbestand für Mengenpunkte. (b) Der Punkt geht ganz an dich, sobald ein echtes Konto über 200 Einsätze hat. (c) Verwerfen. | **(a)** — ein dauerhafter Mengenbestand auf Staging hilft auch Nr. 37 später; Aufwand ein Paket. | BR7-03 | **Weder noch** — die Zeitraumübersicht misst die Bedienprobe schon (`zeitraum-seitengrenze`, vervielfachte Antwort), dieselbe Tabelle wie die Suche (`missiontable.js`); dazu nur die Messung auf 390 px. P-R4-17 gelöscht (E-BR7-06). |
| Q-BR7-05 | **P-R4-13 (F-BR7-03).** (a) Verwerfen — es gibt kein SFTP-Ziel und keines ist geplant. (b) Örtlich mit einem SFTP-Container in der Sandbox prüfen. (c) Offen lassen, bis ein SFTP-Ziel existiert. | **(a)**; wird je ein SFTP-Ziel eingerichtet, ist das ein neuer Prüfpunkt. | — | **(a) verworfen.** |
| Q-BR7-06 | **Runbook-Satz zu F-BR7-02:** In `Technik.md` 12 und der Ansage des Laufs festhalten: „Tag erst setzen, wenn der Staging-Lauf desselben Commits grün ist; sonst nach dem grünen Lauf ‚Re-run failed jobs'"? Und soll die Ansage bei einem Abbruch vor dem Abgleich „nichts geschehen" statt „Dateistand unbekannt" sagen? | **Ja** zum Satz; die Ansage nur, wenn der Lauf den Abbruchschritt sicher kennt — sonst bleibt sie vorsichtig. | BR7-02 | **Nein** — die Betreiberin merkt es sich; BR7-02 alt entfällt. |
| Q-BR7-07 | **Eigener Schritt oder nicht?** Ohne Q-BR7-04 (a) besteht BR7 aus einem Werkzeugpunkt und einem Doku-Satz. (a) Eigener Schritt 25 wie vorgeschlagen. (b) Nr. 510 und der Doku-Satz gehen als Werkzeugschritt ohne Fahrplanzeile (wie „PR — Prüfmittel"), 13 beginnt sofort. | **(a), wenn Q-BR7-04 (a); sonst (b).** | — | **Kein eigener Schritt** (E-BR7-07). |

## 4. Entscheidungen

| Nr. | Entscheidung | Herkunft |
|---|---|---|
| E-BR7-01 | Umfang: Nr. 510 und eine Prüfrunde auf Staging über die offenen Prüflistenpunkte (Claude, beide); Funde werden Backlog-Punkte der Runde. Erst prüfen, dann Konzept. | Betreiberin, 05.11.2026 |
| E-BR7-02 | Gearbeitet wird auf `claude/admiring-sagan-mql8t4`, nach dem Merge von PR #153 frisch von `main`. | Betreiberin, 05.11.2026 |
| E-BR7-03 | `produktion` bekommt nur eine Löschsperre (Ruleset „Zeiger produktion", „Restrict deletions"). Keine Sperre für Updates oder Force-Push: Der Job `zeiger` schiebt mit `git push --force` und dem Token des Laufs. | Betreiberin, 06.11.2026 |
| E-BR7-04 | Alarmweg der Wache bleibt die GitHub-Benachrichtigung; die Betreiberin prüft deren Einstellung. | Betreiberin, 06.11.2026 |
| E-BR7-05 | Nr. 510: Die Schemaprobe arbeitet auf einer Kopie von `server/` in einem Wegwerfverzeichnis. | Betreiberin, 06.11.2026 |
| E-BR7-06 | Kein Prüfbestand über 200 Einsätze. Der vorhandene Weg `zeitraum-seitengrenze` misst zusätzlich auf 390 px; P-R4-17 wird gelöscht, P-R4-13 verworfen. Keine neue Prüfung, kein neuer Weg. | Betreiberin, 06.11.2026 |
| E-BR7-07 | Kein Fahrplanschritt; BR7 ist ein Werkzeugschritt mit Pull Request, danach 13. | Betreiberin, 06.11.2026 |

## 5. Arbeitspakete

### BR7-01 Schemaprobe ohne Griff an die Anlage (Nr. 510)

*Gebaut (06.11.2026):* `tools/schemaprobe/probe.php` kopiert `server/` ohne
`doku`, `assets`, `demo`, `apk`, `sicherungen`, `.sitzungen`, `.zustand` und
`config.php*` in ein Wegwerfverzeichnis unter `sys_get_temp_dir()`, setzt
`$WURZEL` dorthin und schreibt die Wegwerf-`config.php` nur dort; das
Verzeichnis geht im `register_shutdown_function`. Nachgezogen:
`tools/schemaprobe/LIESMICH.md` (Anlass Nr. 510, „Was es braucht"),
`Sandbox-Setup.md` 6.1a (die Ursache der 503 vom 16.10.2026 ist gefunden).
*Probleme:* Der Container wurde mitten in der Messung neu gestartet;
Startfolge 6.1a gefahren, Messung wiederholt.

### BR7-02 Nachladeknopf auf 390 px (E-BR7-06)

*Gebaut (06.11.2026):* `tools/bedienprobe/wege/zeitraum.mjs`, Weg
`zeitraum-seitengrenze`: nach dem Kachelsprung Fenster auf 390 × 844,
„Weitere 200 anzeigen" muss sichtbar sein und nachladen, danach die alte
Breite. Ersetzt die Handprüfung P-R4-17 (F-R4-59). Die Zahl der Wege bleibt.
*Probleme:* Ein Aufruf `probe.mjs --help` fuhr die ganze Bedienprobe an;
abgebrochen, Bericht zurückgesetzt, Demo-Konto zurückgesetzt.

### BR7-03 Abschluss

Prüfstand im Zug, Pull Request. Nach dem Merge: Erledigt-Zeile in Rahmenplan
8 (Werkzeug), Konzept löschen. Ein Prüfdokument entfällt: Es bleibt kein
Handpunkt (Abschnitt 6).

## 6. Prüfprotokoll

| Paket | Mittel | Gemessen | Ergebnis |
|---|---|---|---|
| Vorbereitung | GitHub-Läufe 42098312578, 178, Prüfstandlogs RD-04 und RD-06 | P-PU-01, -04, -06, P-BR-08 | bestanden (Abschnitt 2.1) |
| Vorbereitung | Läufe der Integritätswache 213 bis 272 | Ergebnis je Lauf | grün bis 263, rot 264 bis 271 (Zeiger fehlt), grün ab 272 |
| BR7-01 | `plattform.sh schema`, daneben Prüfsumme der `config.php` alle 0,2 s und Blick auf `wartung.lock` | Prüfungen, Abweichungen | neu: **4 × 52/0, 0 Abweichungen**, kein Wegwerfverzeichnis übrig; alte Fassung (Gegenprobe): 4 × 52/0, **76 Abweichungen** |
| BR7-01 | — | `wartung.lock` | in beiden Fassungen 0 — während der Messung traf keine angemeldete Anfrage die Anlage; belegt ist das Fehlen der Ursache, nicht der Fehlerfall |
| BR7-02 | `probe.mjs --nur zeitraum-seitengrenze` | Weg | **1 von 1**: „390 px: Knopf sichtbar, 400 → 492 Zeilen" |
| BR7-02 | Gegenprobe: `.mehrzeile` unter 720 px ausgeblendet (wie vor Web 21.4.0) | Weg | **0 von 1**, „Knopf NICHT sichtbar, 400 → 400 Zeilen"; Stylesheet zurückgelegt |

**Was nicht geprüft ist:** der Weg in Firefox und WebKit (läuft im nächsten
Prüfstand, der die Bedienprobe dreifach fährt); ein echtes Handy (bewusst
nicht, E-BR7-06).

## 7. Verlauf

| Datum | Was |
|---|---|
| 06.11.2026 | Fragen beantwortet (E-BR7-03 bis -07), kein Fahrplanschritt; BR7-01 (Nr. 510) und BR7-02 (390 px) gebaut, P-R4-13 verworfen, P-R4-17 und „Kette II 9“ aus der Prüfliste. |
| 05.11.2026 | Konzept angelegt nach der Prüfrunde: vier Prüfpunkte bestanden und gelöscht, Befunde F-BR7-01 bis -05, Fragen Q-BR7-01 bis -07, Pakete BR7-01 bis -04. Fahrplanzeile 25 auf „Konzept". |
