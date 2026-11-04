# Konzept BR6 — Backlog-Runde 6, Web, Uhr, Handy und Prüfaufbau (Fahrplanschritt 23)

**Kürzel:** BR6. Pakete `BR6-NN`, Entscheidungen `E-BR6-NN`, Befunde
`F-BR6-NN`, Fragen `Q-BR6-NN`, Prüfpunkte `P-BR6-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/stoic-turing-mpxuch` (Konzept und Umsetzung, E-BR6-03) · **Herkunft:** Auftrag der Betreiberin vom 04.11.2026 (E-BR6-01
bis -03) · **Backlog-Spanne:** 500 bis 509 · **Prüfdokument:**
`Pruefdokument-BR6-Backlog-Runde-6.md` (entsteht mit BR6-08).

**Modell:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine, in keinem Paket (CLAUDE.md 7). Die Web-Pakete
berühren gemeinsame Dateien (`version.php`, `CHANGELOG.md`), und jedes Paket
endet mit Prüfarbeit an der einen örtlichen Anlage.

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| BR6-01 | Prüfaufbau: Nr. 490, 491 | **erledigt** 04.11.2026 (nur Werkzeug, keine Fassung, E-BR6-14) |
| BR6-02 | Wartungslauf auf MySQL 8: Nr. 458 | offen |
| BR6-03 | Änderungsmarke des Demo-Kontos: Nr. 459 | offen |
| BR6-04 | Endgültige Mail-Absage: Nr. 481 | offen |
| BR6-05 | Abstand zwischen zwei Formularen: Nr. 480 | offen |
| BR6-06 | Uhr, Sync-Seite auf der Venu 3s: Nr. 470 | offen |
| BR6-07 | Handy, Frist für den Klartextpuffer nach der Abmeldung: Nr. 471 | offen |
| BR6-08 | Abschluss | offen |

**In Arbeit:** BR6-02 als Nächstes. **Es hakt:** nirgends. Q-BR6-01 bis
-09 sind nach den Vorschlägen entschieden (E-BR6-04 bis -12); vor BR6-05
fragt die Umsetzung noch einmal, ob der Abstand ohne Mockup gehen darf
(E-BR6-09).

---

## 1. Auftrag

Die acht Punkte unter `gehört zu: nächste Backlog-Runde` (Stand 04.11.2026)
werden vor Schritt 13 abgearbeitet: 458, 459, 470, 471, 480, 481, 490, 491.
Danach ist dieser Teil des Backlogs leer.

Berührt werden `server/` (458, 459, 480, 481), `watch/` (470),
`android/handy/` (471), `tools/sandbox/`, `tools/pruefstand/` und die
Prüffälle in `android/handy/src/test/` (490, 491), dazu die Dokumente nach
CLAUDE.md 2 und 9. **Nicht berührt:** `.github/`, `android/uhr/`,
`docs/JSON-Vertrag.md`. Ausgeliefert wird je ein Tag `web-v…`, `uhr-v…` und
`android-v…` nach der Freigabe; das ist nicht Teil dieses Schritts
(CLAUDE.md 3, Q-BR6-01).

**Warum vor 13:** P7 beschreibt das Verhalten der 1.0 und fasst bei der
Umbenennung fast jede Datei an. Fehler, die vorher behoben sind, beschreibt
P7 nicht, und zwei Schritte an denselben Dateien entfallen (E-BE-02 gilt
sinngemäß).

**Nicht Teil:** Datenmodell und Migrationen. Kein Paket braucht nach dem
heutigen Befund eine Schemaänderung. Findet die Umsetzung doch eine, ist das
ein Befund mit Rückfrage, weil dann `update.php` nach dem Deploy fällig
wird (CLAUDE.md 3). Ebenso nicht Teil: die Punkte mit `gehört zu: 14`
(E-BE-04) und die Zuarbeiten.

## 2. Befund

Gelesen am 04.11.2026 auf `origin/main` `a1521a6` (nach PR #149). Jede
Backlog-Angabe ist am Code nachgesehen; wo sie abweicht, steht ein Befund.

**Nr. 458 — bestätigt.** `spur2_faellig_kandidaten()` in `spur_lib.php`
gruppiert nach `o.id`; die korrelierte Unterabfrage `geschnitten` greift auf
`s.owner_type` und `s.owner_id` zu, die weder gruppiert noch aggregiert sind.
MySQL mit `ONLY_FULL_GROUP_BY` lehnt das ab (1055), MariaDB lässt es durch.
`s.owner_id` ist über den JOIN gleich `o.id`, `s.owner_type` ist der
Parameter `?` des Durchgangs — beide lassen sich ohne Bedeutungsänderung
ersetzen.

**F-BR6-01 — Die Plattformprüfung fährt diese Abfrage nicht.** Die
Plattformprüfungen gegen MySQL 8.0 und 8.4 (`tools/sandbox/plattform.sh`)
messen das Schema, nicht die Abfragen der Spurwartung. Dieselbe Art Fehler
hat `admin_users.php` schon einmal gehabt (dort steht der Kommentar zu
`ONLY_FULL_GROUP_BY`). Ob weitere `GROUP BY`-Abfragen in `server/` die
Regel verletzen, ist nicht gemessen → BR6-02 Schritt 3.

**Nr. 459 — bestätigt, mit Ergänzung.** `auth_guard.php` setzt die Marke
für jede POST des Demo-Kontos mit gültigem Formular-Token
(`demo_aenderung_vermerken()`). Die Startseite und die Einstellungen laden
`assets/wartungslauf.js`, das `api/wartungslauf.php` mit `liste` und `zahl`
aufruft — beide lesen nur.

**F-BR6-02 — Es gibt weitere lesende POSTs.** Neben dem Wartungslauf
fragen mindestens `api/kontopaar.php` (`zustand`, `abweichung`) und
`api/export_data.php` per POST, ohne etwas zu schreiben. `wartungslauf.php`
mit `tauschen` schreibt dagegen wirklich: Es verdichtet Spuren. Ob die
Fixture des Demo-Kontos fällige Spuren enthält, ist nicht gemessen; täte
sie es, würde schon das erste Ansehen sie verdichten und damit tatsächlich
ändern → Q-BR6-03.

**Nr. 481 — bestätigt, größer als im Backlog.** `mail_einreihen()` gibt nach
einem gescheiterten ersten Versuch `MAIL_WARTET` zurück, gleich in welchem
Zustand `mail_zeile_versuchen()` die Zeile hinterlassen hat.

**F-BR6-03 — Auch „zu spät“ heißt „wartet“.** `mail_zeile_versuchen()` kennt
drei Ausgänge nach einem Fehlschlag: `offen` (nächste Stufe der Leiter),
`unzustellbar` (endgültige Absage oder Leiter zu Ende) und `zu_spaet`
(nächster Versuch läge nach dem Ablauf des Tokens). Nur der erste ist
„wartet“.

**F-BR6-04 — `MAIL_ABGELEHNT` heißt heute „nicht eingereiht“.** Die
Testmail in `betrieb_status.php` sagt bei `MAIL_ABGELEHNT` „wurde nicht
eingereiht … Adresse unbrauchbar oder Warteschlange nicht erreichbar“, und
`email_lib.php`, `konto_lib.php`, `speicher_lib.php`, `adminbackup_lib.php`
werten `!== MAIL_ABGELEHNT` als Erfolg. Wer „unzustellbar“ auf denselben
Wert legt, ändert die Bedeutung an diesen Stellen mit → Q-BR6-04.

**F-BR6-05 — Es sind nicht fünf Aufrufer.** `mail_einreihen()` hat 41
Aufrufe in 22 Dateien; den Wert `MAIL_WARTET` unterscheiden davon sieben
Stellen (`admin_users.php`, viermal `admin_user.php`,
`ankuendigung_lib.php`, `login.php`) und der `match` in
`betrieb_status.php`. Die Liste im Backlog nennt fünf Dateien und
`email_lib.php`, das `MAIL_WARTET` gar nicht unterscheidet.

**Nr. 480 — bestätigt.** In `betrieb_server.php` („Nachtragen vom Blatt“)
stehen bei „beide abweichend“ zwei `<form>` ohne Klasse in einem
`div.listen-form`. `.listen-form-fuss` hat nur `margin-top`; das zweite
Formular beginnt ohne Abstand unter dem Fuß des ersten. Eine Regel für
aufeinanderfolgende Formulare in `.listen-form` gibt es im Stylesheet nicht.

**Nr. 470 — bestätigt.** Die Zeile entsteht in `SyncView.mc` als
`Input.lSelectHold() + ": Gerät koppeln"`; auf der Venu 3s ist
`L_SELECT_HOLD` „Action halten“. `Ui.fitFont()` wählt aus einer Liste und
gibt die kleinste zurück, auch wenn sie nicht passt — ein Kürzen gibt es
nicht. Die Bildprobe `tools/uhr-pruefstand/syncseite/` misst Höhen, keine
Breiten.

**Nr. 471 — bestätigt.** `Rueckstandsbindung` hält `abgemeldet` als
Wahrheitswert, ohne Zeitpunkt. Die Garmin-Uhr hält den Zeitpunkt der ersten
401/403 (`abgem_seit`, `Uploader.abmeldungVermerken()`) und gibt die Frist
auf der Sync-Seite aus (`abmeldeFristBis()`). `Raeumung.FRIST_TAGE` (30) gibt
es im Handy schon, für abgewiesene Pakete. Das Bedrohungsmodell, Lage H,
nennt für die Garmin die Frist nach der Abmeldung, für das Handy keine.

**Nr. 490 — übernommen, nicht nachgemessen.** Der Server der örtlichen
Anlage startet in `lokal_starten.sh`; `hochfahren.sh` beendet ihn nur mit
`--php 8.3` oder `--neu`. Der Ablauf aus dem Backlog (Prüfstand im
Arbeitsbaum, Arbeitsbaum löschen, 404) ist in BR6-01 vor der Änderung
einmal nachzustellen.

**F-BR6-06 — Den Server zurückzustellen reicht für Nr. 490 nicht** (gefunden
in BR6-01). Ein Arbeitsbaum hat keine `config.php`; `hochfahren.sh` richtet
dort deshalb neu ein, und `lokal_einrichten.sh` **löscht die gemeinsame
Datenbank** und schreibt eine neue `config.php` mit neuem Server-Anteil.
Danach passt die `config.php` des Hauptbaums zu keinem Konto mehr — der
Anmelde-Hash trägt den Pfeffer aus dem Anteil (`pf1:`). Auch mit dem Server
auf dem richtigen Baum scheitern dann alle Anmeldungen → E-BR6-13.

**Nr. 491 — übernommen, nicht nachgemessen.** `KopplungRundlaufTest`,
`MissionRundlaufTest` und `SendeRundlaufTest` nutzen `Kopplungshilfe`
(Testquelle). Konto 1 der örtlichen Anlage hat kein Schlüsselpaar; das
Handy sendet seit S11 ohne Kontoschlüssel nichts. Vor der Änderung einmal
nachmessen (P-AR-15, 6 Fehlschläge erwartet).

## 3. Fragen an die Betreiberin

| Nr. | Frage | Vorschlag | Paket |
|---|---|---|---|
| Q-BR6-01 | **Tags von Schritt 21.** `uhr-v4.3.1` und `android-v0.20.0` sind gebaut, aber nicht ausgeliefert. Jetzt setzen oder mit den Fassungen aus BR6-06 und -07 zusammen? | **Zusammen nach BR6.** Eine Auslieferung je Gerät statt zwei; P-GR-01 bis -07 lassen sich dann gleich an der neueren Fassung prüfen. Preis: Die Korrekturen aus GR erreichen die Geräte später. | BR6-08 |
| Q-BR6-02 | **Nr. 458, Gegenprobe über alle Abfragen.** Nur die eine Abfrage richten und in eine Probe gegen MySQL 8.4 nehmen, oder zusätzlich alle `GROUP BY` in `server/` einmal gegen MySQL 8.4 mit `ONLY_FULL_GROUP_BY` laufen lassen? | **Beides**, die Gegenprobe einmalig als Messung im Prüfprotokoll; ein dauerhaftes Prüfmittel nur, wenn sie einen zweiten Fehler findet (Grundsatz 5). | BR6-02 |
| Q-BR6-03 | **Nr. 459, Weg.** (a) Wartungslauf für das Demo-Konto ganz auslassen; (b) lesende POSTs per Liste von der Marke ausnehmen; (c) beides. | **(c).** (a), weil ein Verdichten der Fixture eine echte Änderung wäre und der Reset die Spuren ohnehin zurücksetzt; (b) für die übrigen lesenden POSTs (F-BR6-02). Die Liste steht an einer Stelle in `demo_lib.php`. | BR6-03 |
| Q-BR6-04 | **Nr. 481, Rückgabewert.** Neuer Wert `MAIL_UNZUSTELLBAR` oder „unzustellbar“ auf `MAIL_ABGELEHNT` legen? | **Neuer Wert.** `MAIL_ABGELEHNT` heißt heute „nicht eingereiht“ (F-BR6-04); ein vierter Wert ändert nur, wo er ausdrücklich behandelt wird. „Zu spät“ (F-BR6-03) bekommt denselben Wert, weil auch dort nichts mehr versucht wird. | BR6-04 |
| Q-BR6-05 | **Nr. 481, Meldung bei der Testmail.** Braucht `betrieb_status.php` einen eigenen Text für „endgültig abgelehnt“? | **Ja**, mit dem Grund aus der Warteschlange („Der Mailserver hat die Adresse endgültig abgelehnt …“). | BR6-04 |
| Q-BR6-06 | **Nr. 480, Ort der Regel.** Allgemeine Regel `.listen-form > form + form` (Abstand wie zwischen Abschnitten, vorhandenes Token) oder eine Klasse nur für diese Karte? | **Allgemeine Regel**, eingetragen in `Design.md`. Sie gilt überall, wo zwei Formulare in einem Abschnitt stehen. Kein neuer Baustein und kein neuer Token, deshalb ohne Mockup — **bitte bestätigen**, dass das keine „neue Darstellung“ im Sinn von CLAUDE.md 5 ist. | BR6-05 |
| Q-BR6-07 | **Nr. 470, Weg.** Zeile kürzen („Koppeln: Action halten“), die Bildprobe um Breiten erweitern, oder beides? | **Beides.** Die Breitenmessung (Text gegen Sehne an seiner Höhe, dieselbe Rechnung wie `Ui.chordW()`) findet den nächsten Fall, das Kürzen behebt diesen. Gekürzt wird nur, wo die lange Form nicht passt. | BR6-06 |
| Q-BR6-08 | **Nr. 471, Umfang der Räumung.** Was fällt nach 30 Tagen ab der ersten 401/403? | **Wie die Uhr:** alle abgeschlossenen Pakete der Kopplung **und** ein gebundener Rückstand aus einer früheren Kopplung; ein laufender Dienst nicht (er gehört zu keiner Abmeldung). Die App zeigt dazu „Daten bis TT.MM.“ wie die Sync-Seite der Uhr. Die Frist beginnt neu, wenn der Server wieder annimmt. | BR6-07 |
| Q-BR6-09 | **Nr. 491, Weg.** Die örtliche Anlage legt für Konto 1 ein Schlüsselpaar an (`hochfahren.sh`), oder der Rundlauf legt es selbst an (`Kopplungshilfe`)? | **Der Rundlauf** über `api/kontopaar.php`, wie es das Einspielwerkzeug tut (`Sitzung.kontoschluessel()` in `tools/referenzdatensatz/einspielen/sitzung.py`). So bleibt die örtliche Anlage, wie eine frische aussieht, und der Prüffall hängt nicht an einem Zustand, den ein anderes Werkzeug herstellt. | BR6-01 |

## 4. Entscheidungen

| Nr. | Entscheidung | Wer, wann |
|---|---|---|
| E-BR6-01 | Alle acht Punkte unter `nächste Backlog-Runde` werden vor Schritt 13 abgearbeitet; der Schritt heißt 23 (BR6). Reihenfolge 23 → 13 → 14; die Konzeptsitzung zu 13 darf daneben laufen (Rahmenplan 4). | Betreiberin, 04.11.2026 |
| E-BR6-02 | Nr. 490 und 491 gehören in diese Runde, obwohl E-BE-04 Werkzeugpunkte an die Inventur von 14 bindet. Grund: Sie stören die Prüfarbeit heute, nicht erst in 14. | Betreiberin, 04.11.2026 |
| E-BR6-03 | Konzept und Umsetzung laufen in dieser Sitzung und auf diesem Zweig. *(Bis zum Beginn von BR6-01 stand hier: Umsetzung in einer eigenen Sitzung.)* | Betreiberin, 04.11.2026 („Hier weitermachen“) |
| E-BR6-04 | Q-BR6-01: Die Tags `uhr-v…` und `android-v…` aus Schritt 21 werden mit den Fassungen aus BR6 gesetzt, nicht vorher. | Betreiberin, 04.11.2026 — Vorschlag übernommen („Mach hier weiter“) |
| E-BR6-05 | Q-BR6-02: Abfrage richten und in eine MySQL-8.4-Probe nehmen; dazu einmalig alle `GROUP BY` in `server/` gegen MySQL 8.4 als Messung, ein dauerhaftes Mittel nur bei einem zweiten Fund. | wie E-BR6-04 |
| E-BR6-06 | Q-BR6-03: (c) — Wartungslauf lässt das Demo-Konto aus, lesende POSTs stehen auf einer Ausnahmeliste in `demo_lib.php`. | wie E-BR6-04 |
| E-BR6-07 | Q-BR6-04: neuer Rückgabewert `MAIL_UNZUSTELLBAR`, auch für „zu spät“. | wie E-BR6-04 |
| E-BR6-08 | Q-BR6-05: eigener Text der Testmail für die endgültige Absage, mit Grund. | wie E-BR6-04 |
| E-BR6-09 | Q-BR6-06: allgemeine Regel `.listen-form > form + form` mit vorhandenem Token, in `Design.md`. **Ob das ohne Mockup geht, fragt die Umsetzung vor BR6-05 ausdrücklich** (CLAUDE.md 5); bis dahin ist nur der Ort entschieden. | wie E-BR6-04 |
| E-BR6-10 | Q-BR6-07: beides — Breitenmessung in der Bildprobe und Kürzen, wo die lange Form nicht passt. | wie E-BR6-04 |
| E-BR6-11 | Q-BR6-08: Räumung wie die Uhr — abgeschlossene Pakete der Kopplung und gebundener Rückstand, nicht der laufende Dienst; „Daten bis TT.MM.“ in der App; neue Frist nach einer Annahme. | wie E-BR6-04 |
| E-BR6-12 | Q-BR6-09: Der Rundlauf legt das Paar selbst an, über `api/kontopaar.php`. | wie E-BR6-04 |
| E-BR6-13 | F-BR6-06: Ein Baum ohne `config.php` übernimmt `config.php` und `install.lock` vom Baum, dessen Server läuft, statt neu einzurichten. Eingerichtet wird nur noch, wenn kein Server läuft oder `--neu` es verlangt. Grund: Mehrere Bäume teilen eine Datenbank; eine zweite Einrichtung zerstört die erste. | Umsetzung, 04.11.2026 (im Rahmen von Nr. 490) |
| E-BR6-14 | BR6-01 stuft keine Zählung hoch: Berührt sind `tools/`, Prüfquellen unter `android/handy/src/test/` und `android/werkzeuge/` — nichts davon wird ausgeliefert (CLAUDE.md 2). Kein Changelog-Eintrag. | Umsetzung, 04.11.2026 |

## 5. Arbeitspakete

Jedes Paket benennt den Ort jeder neuen Funktion (K1, R74). Die Reihenfolge
ist die Abarbeitungsfolge: Zuerst der Prüfaufbau, auf dem die übrigen
Pakete prüfen; dann die Web-Fehler nach Schwere; die Geräte zuletzt. Jedes
Paket mit Codeänderung stuft die betroffene Zählung hoch (`server/version.php`,
`watch/source/Const.mc` oder `android/version.properties`) und schreibt einen
Changelog-Eintrag (CLAUDE.md 2); welche Stufe, entscheidet die Umsetzung
(K3). Ein Paket nur unter `tools/` stuft nichts hoch.

### BR6-01 Prüfaufbau (Nr. 490, 491; Q-BR6-09)

*Vorab:* beide Befunde einmal nachstellen (Abschnitt 2).

*Weg:*
1. Nr. 490: `pruefen.sh` merkt sich vor dem Hochfahren, welcher Server auf
   der Adresse lief und welche Wurzel er bediente, und stellt nach dem Lauf
   diesen Zustand wieder her (beendet den eigenen, startet den des
   Hauptbaums). `hochfahren.sh` prüft ohne `--neu`, ob der laufende Server
   die eigene `server/`-Wurzel bedient, und startet ihn sonst neu, statt nur
   „antwortet nicht mit 200“ zu melden.
2. Nr. 491: nach Q-BR6-09.
3. `LIESMICH.md` der berührten Werkzeuge und `docs/Sandbox-Setup.md`
   nachziehen.

*Ort:* `tools/pruefstand/pruefen.sh`, `tools/sandbox/hochfahren.sh` (ggf.
`lokal_starten.sh`), `android/handy/src/test/…/kopplung/Kopplungshilfe.kt`.

*Gebaut (04.11.2026):* `hochfahren.sh` liest die Wurzel des laufenden
Servers aus dessen Befehlszeile (`laufende_wurzel()`, im Behälter von
`--php 8.3` aus der Einhängung), sagt sie mit `--welche-wurzel`, startet
einen Server auf fremder Wurzel um und übernimmt in einem Baum ohne
`config.php` die des laufenden Baums (E-BR6-13). `pruefen.sh` merkt sich die
Wurzel vor dem Hochfahren und gibt sie am Ende zurück (`wurzel_zurueck()`,
im selben `trap` wie die Demo-Marke, auch nach einem Abbruch). Nr. 491:
`Kopplungshilfe.kontopaar` ruft einmal je Lauf
`android/werkzeuge/rundlauf_kontopaar.py`, das sich als
`admin@example.invalid` anmeldet und `Sitzung.kontoschluessel()` aus dem
Einspielwerkzeug nimmt; `koppeln()` fragt es vor dem ersten `start`.

*Abnahme:* den Prüfstand aus einem `git worktree` außerhalb des
Repositoriums fahren (der Weg des erledigten P-BR-10), den Arbeitsbaum
löschen; danach läuft die Zweitfaktorprobe ohne Eingriff grün (Zahl der
Fälle nennen). P-AR-15 auf
frischer Anlage: 0 Fehlschläge, 0 übersprungen.

### BR6-02 Wartungslauf auf MySQL 8 (Nr. 458; Q-BR6-02)

*Weg:*
1. In der Unterabfrage `c.owner_type = ?` und `c.owner_id = o.id`; der
   Parameter wird doppelt übergeben.
2. Die Abfrage in eine Probe nehmen, die gegen MySQL 8.4 läuft
   (`tools/proben/spur/probe.php` oder die Schemaprobe in `plattform.sh`;
   die Umsetzung wählt und begründet). Anlass-Zeile: Nr. 458.
3. Gegenprobe nach Q-BR6-02.

*Ort:* `server/spur_lib.php` (`spur2_faellig_kandidaten()`), die gewählte
Probe.

*Abnahme:* Probe gegen MySQL 8.4 grün, gegen den alten Stand rot (1055);
gegen MariaDB 10.11 unverändert dieselben Kandidaten. Auf Staging nach dem
Merge (R91, Claude): keine neue Zeile „wartungslauf … 1055“ im Reiter
System, `api/wartungslauf.php` antwortet 200, „GPS-Daten verdichten“ nennt
eine Zahl; danach P-S11-30 auf Staging.

### BR6-03 Änderungsmarke des Demo-Kontos (Nr. 459; Q-BR6-03)

*Weg:* nach Q-BR6-03. Bei (c): `api/wartungslauf.php` antwortet dem
Demo-Konto mit einer leeren Liste und `zahl` 0, ohne Marke; die
Ausnahmeliste lesender POSTs (Endpunkt und Aktion) steht in `demo_lib.php`
und wird in `auth_guard.php` vor `demo_aenderung_vermerken()` gefragt. Jede
Zeile der Liste nennt, warum der Aufruf nichts schreibt.

*Ort:* `server/auth_guard.php`, `server/demo_lib.php`,
`server/api/wartungslauf.php`; `docs/Technik.md` (Abschnitt zum
Demo-Konto), `docs/Handbuch.md`, falls es die Marke beschreibt.

*Abnahme:* frischer Reset, anmelden, Startseite, Einstellungen und eine
Einsatzseite ansehen — Verwaltung → Demo-Konto „Geändert: nein“. Eine echte
Änderung (Notiz am Diensttag) setzt die Marke weiterhin.

### BR6-04 Endgültige Mail-Absage (Nr. 481; Q-BR6-04, -05)

*Weg:*
1. `mail_zeile_versuchen()` gibt den Ausgang zurück statt eines
   Wahrheitswerts (oder `mail_einreihen()` liest danach den Zustand der
   Zeile; die Umsetzung wählt). `mail_einreihen()` gibt bei `unzustellbar`
   und `zu_spaet` den Wert aus Q-BR6-04 zurück.
2. Jede Stelle, die `MAIL_WARTET` unterscheidet (F-BR6-05), bekommt den
   neuen Fall mit einem eigenen Text; die Stellen, die `!== MAIL_ABGELEHNT`
   werten, werden einzeln gelesen und im Prüfprotokoll mit ihrem Ergebnis
   genannt.
3. `jobs_lib.php` ruft `mail_zeile_versuchen()` ebenfalls; der Job darf
   durch die neue Rückgabe nichts anders tun als heute.

*Ort:* `server/mail_lib.php`, die Aufrufer nach F-BR6-05,
`server/betrieb_status.php`; `docs/Technik.md` (Mailversand).

*Abnahme:* örtlich mit der Mailprobe (Art `nach_daten` bzw. 550 beim
Empfänger): Konto anlegen → „konnte NICHT verschickt werden“; Testmail →
der Text aus Q-BR6-05. Ein vorübergehender Fehler (4xx) meldet weiter
„steht in der Warteschlange“. Auf Staging (R91, Claude): Konto für eine
nicht vorhandene Adresse der eigenen Domain.

### BR6-05 Abstand zwischen zwei Formularen (Nr. 480; Q-BR6-06)

*Weg:* nach Q-BR6-06; der Abstand ist der zwischen zwei Abschnitten
(`.listen-form`: `padding-top` plus `margin-top`), ohne Linie. Erzeugte
Tabellen in `Design.md` neu schreiben (`tools/erzeugen/design.py
schreiben`).

*Ort:* `server/assets/style.css`, `docs/Design.md`.

*Abnahme:* Bild der Karte „Servereinstellungen“ in der Lage „beide
abweichend“, 390 und 1280 px — Abstand wie zwischen Abschnitten. Der
Stilvergleich nennt keine weitere Seite mit Änderung (sonst steht dort ein
zweites Paar Formulare, das die Regel mit trifft: jede Seite nennen). Drei
Engines (Grundsatz 8, `style.css` berührt).

### BR6-06 Uhr, Sync-Seite auf der Venu 3s (Nr. 470; Q-BR6-07)

*Weg:* nach Q-BR6-07. Die Breitenmessung gehört in die Bildprobe, nicht in
die App; das Kürzen in `SyncView.mc` an der Stelle, die die Zeile baut.

*Ort:* `watch/source/SyncView.mc` (ggf. `Ui.mc`),
`tools/uhr-pruefstand/syncseite/` samt `LIESMICH.md`.

*Abnahme:* Bildprobe auf Fenix 6 Pro, FR 945 und Venu 3s mit 6 und 7
Zeilen: jede Zeile ganz lesbar, die Breitenmessung 0 Überschreitungen; gegen
den alten Stand meldet sie die Venu-3s-Zeile. Die Bilder werden angesehen
(wie P-GR-08), nicht nur gezählt.

### BR6-07 Handy, Frist für den Klartextpuffer (Nr. 471; Q-BR6-08)

*Weg:*
1. `Rueckstandsbindung` legt beim ersten Vermerk den Zeitpunkt ab
   (`abmeldungVermerken()` schreibt ihn nur, wenn keiner da ist;
   `abmeldungVergessen()` löscht ihn). Bestehende Installationen mit
   `abgemeldet = true` ohne Zeitpunkt bekommen beim ersten Lesen „jetzt“ —
   die Frist beginnt dann mit der neuen Fassung, nicht rückwirkend.
2. Die Räumung nach Q-BR6-08 läuft dort, wo heute die Frist für
   abgewiesene Pakete greift (`Raeumung`, Aufruf aus `Sender`), mit
   derselben Frist `Raeumung.FRIST_TAGE`.
3. Anzeige nach Q-BR6-08 in der App.
4. `docs/Bedrohungsmodell.md` Lage H und G3 (3) nachziehen,
   `docs/Technik.md` 6, `android/LIESMICH.md`, `docs/Handbuch.md`
   (Abschnitt zum Handy).

*Ort:* `android/handy/…/kopplung/Rueckstandsbindung.kt`,
`android/handy/…/puffer/Puffer.kt` (`Raeumung`),
`android/handy/…/senden/Sender.kt`, die Ansicht, die den Abmeldezustand
zeigt.

*Abnahme:* Prüffall in `SenderTest` mit gestellter Uhr: 30 Tage nach der
ersten 401 ist der Puffer leer (auch ein gebundener Rückstand); 29 Tage
danach nicht; nach einer 200 dazwischen beginnt die Frist neu; ein laufender
Dienst bleibt. Bilderlauf des Handys mit der Fristzeile. Am echten Gerät
bleibt es ein Punkt im Prüfdokument.

### BR6-08 Abschluss

Prüfstand über den Endstand (Umfang nach `docs/Pruefablauf.md`; die
Umsetzung nennt den gewählten Umfang), Regressionspflicht (R24, beide
Kreisläufe), Prüfdokument (CLAUDE.md 7), Backlog-Punkte nach
`Backlog-Erledigt.md`, Rahmenplan (Fahrplanzeile), Pull Request. Danach auf
Staging die Punkte für Claude (R91). Erledigt-Zeile und Löschung von
Konzept und Prüfdokument erst nach der Freigabe. Die Tags nach Q-BR6-01.

## 6. Prüfprotokoll

**BR6-01** (04.11.2026; örtliche Anlage, MariaDB, PHP 8.4; Android-SDK
nach `aufbauen.sh android`, `ANDROID_HOME=/opt/android-sdk`):

- *Nr. 491, alter Stand:* `./gradlew :handy:testDebugUnitTest --rerun-tasks
  -Pnadoku.rundlauf=http://127.0.0.1:8080/` gegen die Anlage, Konto 1 ohne
  Paar (`kp_oeffentlich IS NULL` = 1): **299 Fälle, 6 Fehlschläge, 1
  übersprungen** — die sechs sind die drei Fälle in `SendeRundlaufTest` und
  die drei in `MissionRundlaufTest`, wie im Backlog.
- *Nr. 491, neuer Stand:* derselbe Aufruf: **299 Fälle, 0 Fehlschläge, 1
  übersprungen**; Konto 1 hat danach ein Paar. Der übersprungene Fall ist
  `ServeradresseTest.imReleaseGiltHttpsAuchFuerOertlicheAdressen` („nur im
  Release“) und gehört nicht zum Rundlauf; P-AR-15 verlangte „0
  übersprungen“ und meinte die Rundlauffälle. P-AR-15 ist damit erledigt und
  aus der Prüfliste genommen.
- *Nr. 490:* Arbeitsbaum außerhalb des Repositoriums
  (`git worktree add <Notizordner>/wt HEAD`, die zwei geänderten Skripte
  hineinkopiert), dort `pruefen.sh`: „Installation aus …/server übernehmen“,
  „Der Server bediente …/server — neu gestartet für …/wt/server“, Lauf
  **25 grün, 0 rot**, danach „Server wieder auf …/server“. Datenbank vorher
  und nachher 4 Konten, 109 Einsätze; `config.php` des Hauptbaums
  unverändert. Dann Arbeitsbaum gelöscht und im Hauptbaum
  `proben.sh zweitfaktor`: **139 ok, 0 fehlen** (im Backlog: 45 ok, 94
  fehlen). Den alten Stand habe ich nicht noch einmal nachgestellt: Er
  hätte die gemeinsame Datenbank gelöscht (F-BR6-06), und der Fehlerweg
  ist am Code belegt (`lokal_einrichten.sh`, Schritt 1).
- *Gegenprobe Syntax:* `bash -n` für beide Skripte; `hochfahren.sh
  --welche-wurzel` gibt `…/einsatzdoku-luftrettung/server` aus.
- *Nicht geprüft:* der Weg über `--php 8.3` (Behälter, Wurzel `/app`) —
  `dockerd` lief nicht; die Auflösung über `docker inspect` ist gelesen,
  nicht gefahren.

## 7. Verlauf

| Datum | Was |
|---|---|
| 04.11.2026 | BR6-01 erledigt (nur Werkzeug); F-BR6-06, E-BR6-13, -14. |
| 04.11.2026 | Q-BR6-01 bis -09 nach den Vorschlägen entschieden (E-BR6-04 bis -12); E-BR6-03 geändert: Umsetzung in dieser Sitzung. |
| 04.11.2026 | Konzept angelegt (E-BR6-01 bis -03); Befunde F-BR6-01 bis -05 am Code nachgesehen; Q-BR6-01 bis -09 offen; Spanne 500 bis 509 reserviert. |
