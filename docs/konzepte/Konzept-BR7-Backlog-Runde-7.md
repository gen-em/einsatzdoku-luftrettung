# Konzept BR7 — Backlog-Runde 7, vor Schritt 13 (Fahrplanschritt 25)

**Kürzel:** BR7. Pakete `BR7-NN`, Entscheidungen `E-BR7-NN`, Befunde
`F-BR7-NN`, Fragen `Q-BR7-NN`, Prüfpunkte `P-BR7-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/admiring-sagan-mql8t4` (nach dem Merge von PR #153 frisch
von `main`, Entscheid der Betreiberin 05.11.2026) · **Herkunft:** Auftrag der
Betreiberin vom 05.11.2026 („Backlog-Runde vor Schritt 13 vorbereiten",
Umfang „Nr. 510 und eine Prüfrunde auf Staging", E-BR7-01) · **Backlog-Spanne:**
511 bis 519 (aus der Spanne von 24, derselbe Zweig) · **Prüfdokument:**
`Pruefdokument-BR7-Backlog-Runde-7.md` (entsteht mit dem Abschluss).

**Modell:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine, in keinem Paket (CLAUDE.md 7). Die Runde ist klein, und
jedes Paket endet mit Prüfarbeit an der einen örtlichen Anlage.

**Keine Fassungsnummer in diesem Dokument** — welche Zählung hochgeht,
entscheidet die Umsetzung (CLAUDE.md 2).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| BR7-01 | Schemaprobe ohne Griff an die `config.php` der Anlage: Nr. 510 | offen — wartet auf Q-BR7-03 |
| BR7-02 | Runbook: Tag erst nach grünem Staging-Lauf (F-BR7-02) | offen — wartet auf Q-BR7-06 |
| BR7-03 | Prüfbestand mit mehr als 200 Einsätzen auf Staging (P-R4-17) | offen — nur bei Q-BR7-04 (a) |
| BR7-04 | Abschluss | offen |

**In Arbeit:** nichts — das Konzept wartet auf die Antworten zu Q-BR7-01 bis
-07. **Es hakt:** am Umfang. Die Prüfrunde hat keinen Fehler der Anwendung
gefunden; ob BR7 ein eigener Schritt bleibt, entscheidet Q-BR7-07.

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

| Nr. | Frage | Vorschlag | Paket |
|---|---|---|---|
| Q-BR7-01 | **Woher kam das Fehlen von `produktion` (F-BR7-01)?** Hast du am 04.11.2026 zwischen 12:08 und 21:59 auf GitHub Zweige gelöscht (etwa die zwei toten aus P-R4-06) oder eine Regel geändert? | Wenn ja, ist die Ursache erklärt. Dann „Kette II 9" jetzt erledigen: Regel für `produktion` mit „Löschen sperren" und Push nur für GitHub Actions. | — |
| Q-BR7-02 | **Alarmweg der Wache.** (a) Nur die Einstellung: GitHub-Benachrichtigung für fehlgeschlagene Läufe an dein Konto und Mail prüfen. (b) Dazu ein Backlog-Punkt „die Wache meldet Rot über einen zweiten Weg" (z. B. ein Issue oder eine Statuszeile in Betrieb → Status), `gehört zu: 14` (Kette im neuen Repositorium). (c) Den zweiten Weg in BR7 bauen. | **(b)** — E-BE-04 schiebt Kettenarbeit nach 14; (a) ist sofort nötig. | — |
| Q-BR7-03 | **Weg für Nr. 510.** (a) Die Probe arbeitet auf einer Kopie von `server/` in einem Wegwerfverzeichnis und fasst die Anlage nie an. (b) `db.php` liest den Pfad der `config.php` aus einem Umgebungswert (Änderung unter `server/`, Web-Fassung, Registerzeile `konfig`). (c) Nach dem Zurücklegen nachsehen und ein von der Probe verursachtes `wartung.lock` entfernen. | **(a)** — nur Werkzeug, keine Fassung; (b) öffnet einen zweiten Weg zur Konfiguration, (c) räumt auf statt zu verhindern. | BR7-01 |
| Q-BR7-04 | **P-R4-17 (F-BR7-04).** (a) Ein Testkonto auf Staging mit rund 220 erzeugten Einsätzen über den CSV-Import anlegen; es bleibt als Prüfbestand für Mengenpunkte. (b) Der Punkt geht ganz an dich, sobald ein echtes Konto über 200 Einsätze hat. (c) Verwerfen. | **(a)** — ein dauerhafter Mengenbestand auf Staging hilft auch Nr. 37 später; Aufwand ein Paket. | BR7-03 |
| Q-BR7-05 | **P-R4-13 (F-BR7-03).** (a) Verwerfen — es gibt kein SFTP-Ziel und keines ist geplant. (b) Örtlich mit einem SFTP-Container in der Sandbox prüfen. (c) Offen lassen, bis ein SFTP-Ziel existiert. | **(a)**; wird je ein SFTP-Ziel eingerichtet, ist das ein neuer Prüfpunkt. | — |
| Q-BR7-06 | **Runbook-Satz zu F-BR7-02:** In `Technik.md` 12 und der Ansage des Laufs festhalten: „Tag erst setzen, wenn der Staging-Lauf desselben Commits grün ist; sonst nach dem grünen Lauf ‚Re-run failed jobs'"? Und soll die Ansage bei einem Abbruch vor dem Abgleich „nichts geschehen" statt „Dateistand unbekannt" sagen? | **Ja** zum Satz; die Ansage nur, wenn der Lauf den Abbruchschritt sicher kennt — sonst bleibt sie vorsichtig. | BR7-02 |
| Q-BR7-07 | **Eigener Schritt oder nicht?** Ohne Q-BR7-04 (a) besteht BR7 aus einem Werkzeugpunkt und einem Doku-Satz. (a) Eigener Schritt 25 wie vorgeschlagen. (b) Nr. 510 und der Doku-Satz gehen als Werkzeugschritt ohne Fahrplanzeile (wie „PR — Prüfmittel"), 13 beginnt sofort. | **(a), wenn Q-BR7-04 (a); sonst (b).** | — |

## 4. Entscheidungen

| Nr. | Entscheidung | Herkunft |
|---|---|---|
| E-BR7-01 | Umfang: Nr. 510 und eine Prüfrunde auf Staging über die offenen Prüflistenpunkte (Claude, beide); Funde werden Backlog-Punkte der Runde. Erst prüfen, dann Konzept. | Betreiberin, 05.11.2026 |
| E-BR7-02 | Gearbeitet wird auf `claude/admiring-sagan-mql8t4`, nach dem Merge von PR #153 frisch von `main`. | Betreiberin, 05.11.2026 |

## 5. Arbeitspakete

### BR7-01 Schemaprobe ohne Griff an die Anlage (Nr. 510; Q-BR7-03)

*Weg (bei (a)):* `probe.php` kopiert `server/` in ein Wegwerfverzeichnis,
setzt `$WURZEL` dorthin und schreibt die Wegwerf-`config.php` nur dort; das
Verzeichnis geht im `register_shutdown_function`. `plattform.sh` und die
Stufe-1-Jobs „Schema gegen …" rufen unverändert.
*Ort:* `tools/schemaprobe/probe.php`, `tools/schemaprobe/LIESMICH.md`,
`Sandbox-Setup.md` 6.1a (die Erscheinung dort austragen).
*Abnahme:* Prüfstand mit Schemaprobe, während eine angemeldete Anfrage die
Anlage alle Sekunde trifft: kein `wartung.lock`, keine 503; `server/config.php`
vor und nach byte-gleich (SHA-256); Schemaprobe auf MariaDB 10.11, 10.6,
MySQL 8.0, 8.4.0 grün wie vorher (52 / 0).
*Fassung:* keine (nur Werkzeug).

### BR7-02 Runbook: Tag-Zeitpunkt (F-BR7-02; Q-BR7-06)

*Weg:* Satz in `Technik.md` 12 (Auslieferung) und in die Ansage des
Tor-Schritts in `ausliefern-lauf.yml`: Tag nach grünem Staging-Lauf; sonst
„Re-run failed jobs". Die Ansage im Fehlerfall unterscheidet „vor dem
Abgleich abgebrochen" von „im Abgleich", wenn die Schrittfolge es sicher
hergibt.
*Ort:* `docs/Technik.md` 12, `.github/workflows/ausliefern-lauf.yml`
(nur Text der Ansage), `CLAUDE.md` 3 ein Halbsatz.
*Abnahme:* `kettenaufrufe` grün, die neunzehn SHA-Zeilen unverändert.
*Fassung:* keine.

### BR7-03 Prüfbestand über 200 Einsätze (P-R4-17; nur bei Q-BR7-04 (a))

*Weg:* Generator erzeugt eine CSV mit rund 220 Einsätzen im Profil
`export_csv_v1` (ohne Dubletten zum Referenzbestand); ein Testkonto auf
Staging (Rolle `user`, eigene Adresse der Prüfanlage) importiert sie über
`csv_import.mjs`. Danach P-R4-17, Schreibtischteil.
*Ort:* `tools/referenzdatensatz/generator/` (neue Ausgabe), Zugang des
Testkontos in den Umgebungswerten (`Sandbox-Setup.md` 4), Prüfliste.
*Abnahme:* Suche ohne Filter: „200 angezeigt", „Weitere 200 anzeigen" lädt
nach; die Kachel springt zur Zeile. Handyteil bleibt bei der Betreiberin.
*Fassung:* keine.

### BR7-04 Abschluss

Prüfdokument, Prüfstand im Zug, Pull Request; nach Merge R91-Prüfung,
Erledigt-Zeile, Konzept löschen, Prüfdokument in die Prüfliste.

## 6. Prüfprotokoll

| Paket | Mittel | Gemessen | Ergebnis |
|---|---|---|---|
| Vorbereitung | GitHub-Läufe 42098312578, 178, Prüfstandlogs RD-04 und RD-06 | P-PU-01, -04, -06, P-BR-08 | bestanden (Abschnitt 2.1) |
| Vorbereitung | Läufe der Integritätswache 213 bis 272 | Ergebnis je Lauf | grün bis 263, rot 264 bis 271 (Zeiger fehlt), grün ab 272 |

## 7. Verlauf

| Datum | Was |
|---|---|
| 05.11.2026 | Konzept angelegt nach der Prüfrunde: vier Prüfpunkte bestanden und gelöscht, Befunde F-BR7-01 bis -05, Fragen Q-BR7-01 bis -07, Pakete BR7-01 bis -04. Fahrplanzeile 25 auf „Konzept". |
