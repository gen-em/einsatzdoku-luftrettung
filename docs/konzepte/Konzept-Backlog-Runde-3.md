# Konzept — Backlog-Runde 3

*Erstellt am 13.09.2026 (Fable) gegen `gen-em/einsatzdoku-luftrettung`, Zweig
`main`, Stand **Web 19.3.0 · Uhr 3.1.0 · Android 0.15.0**. Grundlage sind die
Backlog-Durchsicht vom 12.09.2026 (`Backlog-Durchsicht-2026-09-12.md`,
sechzehn Entscheidungen) und der Auftrag vom 13.09.2026, der Teil 1 und 2
(Backlog und Rahmenplan Fassung 46) abgenommen hat. Format nach K1;
Prüfdokument nach K9 liegt als Vorlage daneben
(`Pruefdokument-Backlog-Runde-3.md`).*

> | | |
> |---|---|
> | Paket | **Backlog-Runde 3** — Schritt 9 des Fahrplans, parallel zu allem anderen |
> | Punkte | **Block A:** Nr. 91, 94, 117, 67 (Unterpunkt), 41 (drei Streichungen) · **Block B:** Nr. 47, 58 · **Block C:** Nr. 173, 174 |
> | Erledigt danach | 91, 94, 117, 47, 58, 173, 174 nach *Erledigt*; 67 und 41 bleiben offen mit Vermerk |
> | Nicht drin | Nr. 57 (eigenes Paket), Mockup-Runde 9c (Nr. 41 Regeln, 42, 45, 124), Nr. 21 (P6), Nr. 62 (Zuarbeit), Nr. 169 (P5), die 26 Klärungspunkte |
> | Modell | Opus (K2). **Keine Fable-Schritte.** Zwei Haltepunkte (Abschnitt 3) |
> | Versionsnummer | legt die Umsetzung fest (K3). Nur AP4 fasst `server/` an; Uhr und Android bleiben unberührt |
> | Reihenfolge | A → B → C → Abschluss (AP10). Block C zuletzt, damit die Regression gegen die neue Referenz läuft |

## Stand (13.09.2026, von der Umsetzung geführt)

| | |
|---|---|
| **In Arbeit** | **nichts mehr** — die Runde ist gebaut und geprüft. Offen ist die **Freigabe** und danach der **Merge auf `main`** (deployt sofort, Abschnitt 3 von `CLAUDE.md`) |
| **Erledigt** | **Block A** (AP1–AP5), **Block B** (AP6, AP7), **Block C** (AP8, AP9), der **Abschluss** (AP10) und die Nachträge **AP11** und **AP12** — alle neun Punkte des Auftrags, dazu **Nr. 176, 178, 179 und 180** auf Anweisung |
| **Offen** | nichts aus dem Auftrag. **Vier neu entstandene Punkte sind auf Anweisung mitbehoben** — alle vier betreffen Prüfmittel: **176** (Rauschfilter des Bilderlaufs, AP11), **178** (dieselbe Lücke, breiter, in `tools/kopplungsprobe/`), **179** (die Zusage „keine fremde Quelle zur Laufzeit" hatte kein Messmittel) und **180** (die Kopplungsprobe misst gegen einen Sollwert, den es seit Web 15.5.0 nicht mehr gibt — rot seit dem 06.09.2026); 178 bis 180 in **AP12**. **Zwei bleiben offen, beide als Entscheidung:** **177** (doppelte Fassungsnummern im Rahmenplan, zurückgestellt — rein dokumentarisch) und **181** (die Content-Security-Policy; sie braucht Ausnahmen für vier Kachelserver und den Adressdienst, dessen Anschrift eine Einstellung ist) |
| **H-BR3-1** | **Ausgelöst und aufgelöst** (AP7): Die Gegenrichtung der neuen Prüfung fand einen echten Fehler — die 404-Seite von `apk.php` ohne Seitenhülle. Gemeldet, belegt, behoben; **nicht** auf die Ausnahmeliste gesetzt |
| **H-BR3-2** | **Ausgelöst und aufgelöst** (AP9): Die Vormessung ergab 0 statt 2 Rettungsmittel ohne Standort. Angehalten, gemeldet — und die erste Diagnose war **falsch** (siehe AP9 unten). Nach der Berichtigung lief die Kette durch |
| **Prüfstand** | **steht seit AP4**: MariaDB 10.11.14 (nachinstalliert), PHP 8.4.19, `socat`-TLS auf 8443, volle Installation mit Demo-Konto (88 Einsätze, 16 Diensttage, 2 Geräte), Chromium über Playwright. Aufbau: `lokal_starten.sh` + `lokal_einrichten.sh` |
| **Stufe der Runde** | **Web 19.3.1 — Korrektur** (E-BR3-14), festgelegt vor AP1; `version.php` wird mit AP4 hochgestuft, dem ersten Paket, das `server/` anfasst |
| **Hakt es?** | Nein. Sechs Abweichungen vom Konzept sind entschieden und begründet: E-BR3-13 (Abschnitt-5-Zeilen wandern je Paket, nicht gesammelt in AP10), E-BR3-14 (Stufe), E-BR3-15 (die Abnahme von AP2 war so nicht erfüllbar), E-BR3-16 (der Bilderlauf lief über **alle** Seiten, nicht nur über die zwei berührten) , E-BR3-17 (ein elftes Paket, das das Konzept nicht hatte — Nr. 176) und E-BR3-18 (ein zwölftes — Nr. 178, 179, 180) |
| **Zweig** | `claude/backlog-runde-3-umsetzung-woqxjm`, nach jedem Paket gepusht |

---

## 0. Warum dieser Schnitt

Neun Punkte, die nichts miteinander zu tun haben außer ihrer Größe. Der
Auftrag hatte drei Blöcke vorgeschlagen — billige Umsetzungen, zwei
Prüfmittel, Textpflege. Zwei Dinge sind anders:

- **Die Textpflege ist weg.** Entscheidung 16 hat Fable überlassen, ob sie
  eine eigene Kleinstufe wird; sie ist mit Teil 1 des Auftrags am
  13.09.2026 vollständig im Backlog aufgegangen (sechs Korrekturen, sieben
  Zahlen, sechs Fundstellen, `Design.md` 2.5). Es gibt nichts mehr zu tun.
- **Nr. 173 und 174 sind kein Block-A-Fall.** 173 ist billig — Regeln aus
  zwei JSON-Dateien. 174 aber heißt, die **Referenzdatei der Kreisläufe neu
  zu erzeugen**: die dreistufige Einspielkette auf dem Prüfstand fahren
  (MariaDB, PHP, TLS, Browser — `tools/referenzdatensatz/LIESMICH.md`,
  „Die drei Läufe"). Das sind keine zwei Zeilen, und beide Punkte fassen
  dieselben Dateien an. Sie bilden deshalb den **Block C** und laufen
  zuletzt, weil sie die Vergleichsgrundlage der Regressionspflicht (R24)
  ändern.

Was den Schnitt außerdem trägt: Kein Punkt braucht eine Freigabe nach
`Design.md` 1. Die eine Gestaltungsfrage — das Kriterium für Nr. 58 — ist
in diesem Konzept entschieden (E-BR3-06), nicht an die Umsetzung delegiert.

---

## 1. Befund — gemessen am 13.09.2026

Jede Zahl hier ist am ZIP von `main` gemessen, nicht aus dem Backlog
abgeschrieben. Wer umsetzt, misst vorher noch einmal — die Zählung von heute
ist morgen genauso alt.

### 1.1 Block A

**Nr. 91 — `WatchUi.Confirmation` im Bildabzug.** Die Lehre aus S5/C steht
nirgends: `tools/uhr-pruefstand/LIESMICH.md` erwähnt `Confirmation` **null**
Mal. Was aufzuschreiben ist, steht vollständig im Backlog-Eintrag: Der
Bildabzug zeigt nicht, welche der beiden Schaltflächen gewählt ist; ein
`Return` ohne weitere Taste **bestätigt** (Vorauswahl `Confirm`); BACK räumt
den Dialog weg, **ohne** `onResponse` zu rufen; ein Rundlauf, der eine
Ablehnung belegen will, misst sie an der Wirkung (Datenbank: kein Gerät,
keine Sitzung), nicht am Bild. Passender Ort: die LIESMICH hat den Abschnitt
„Bedienung simulieren" mit dem Unterabschnitt „Tasten sind heikler als Maus"
— dort gehört ein weiterer Unterabschnitt hin.

**Nr. 94 — „bitgleich" gegen „pixelgleich".** Zwei Stellen: der Kopfkommentar
von `tools/uhr-bilder/erzeugen.sh` („BITGLEICH (geprueft mit `compare -metric
AE`") und `tools/uhr-bilder/LIESMICH.md` im Abschnitt „Warum es dieses
Werkzeug gibt" („bitgleich (`compare -metric AE` liefert 0)"). Acht Zeilen
weiter sagt dieselbe LIESMICH „pixelgleich" und widerlegt sich damit selbst:
`compare -metric AE` zählt abweichende Bildpunkte, es belegt Pixel-, nicht
Bitgleichheit — PNG trägt einen Zeitstempel-Chunk. **Entschieden ist ein
Wort** (Durchsicht 12.09.2026), nicht `-define png:exclude-chunk=time`.

**Nr. 117 — ob eine NutzerIn je ein Backup gezogen hat.** Entschieden:
**nicht erheben** (Entscheidung 6). `users` trägt keine solche Spalte; die
Kennzahlen messen über `edbak_konto_stand()` allein die Konto-Backups der
Verwaltung, und S8 hat sie ehrlich benannt („Konto-Backup überfällig", „nie
Konto-Backup"). Das Handbuch erklärt in 11.2 „Die Liste der NutzerInnen" die
vier Zahlen — sagt aber nicht, was sie **nicht** messen. Ein Absatz.

**Nr. 67, Unterpunkt — `kdf_upgrade.php`.** Zeile 69 steigt für das
Demo-Konto mit `json_out(['ok' => true, 'uebersprungen' => 'demo'])` aus,
Zeile 70 prüft `HTTP_X_CSRF` (gemessen 13.09.2026; bei Aufnahme 66/67). Heute
folgenlos, weil hinter dem Ausstieg nichts steht. Der einzige Aufrufer ist
`assets/unlock.js`, und er schickt `X-CSRF` immer mit — der Tausch ändert
also für den Regelfall nichts, schließt aber die Lücke, bevor jemand hinter
den Ausstieg etwas schreibt. **Der Hauptpunkt (API-Zweig in `csrf_check()`)
bleibt bei P5.**

**Nr. 41, drei Streichungen.** `rea-kopf` und `rea-beginn` stehen in
`einsatz_form.php` (`kopf.className = 'phasen-eingabe rea-kopf'`,
`lab.className = 'rea-beginn'`), `phasen-name` zweimal in `einsatz.php`
(`<span class="phasen-name">`). Keine hat eine Regel, kein Skript liest sie;
alle drei stehen mit `[offen]` in `tools/vollstaendigkeit/ohne-regel.md`.
`pruefen.py` meldet heute **5** `[offen]`; die zwei anderen (`imp-warn`,
`imp-daygroup`) bekommen in der Mockup-Runde 9c eine Regel.

### 1.2 Block B

**Nr. 47 — natives `confirm()`.** Gemessen über `server/**/*.php` und
`server/assets/*.js` (Kommentare ausgenommen): **zwei** Aufrufe von
`window.confirm(`, null von `alert(`, null von `prompt(`. Beide sind
berechtigte Rückfälle für den Fall, dass `edConfirm` nicht geladen ist:
`assets/confirm.js` (im Rückfall der Dialogfunktion selbst, für Browser ohne
`<dialog>`) und `assets/forms.js` (die Formularrückfrage, falls `confirm.js`
fehlt). Der Backlog-Eintrag nannte bis zum 13.09.2026 nur eine — eine
Ausnahmeliste mit einem Eintrag wäre beim ersten Lauf rot gewesen.

**Nr. 58 — Seite ohne Gerüst.** Die naive Regel des Backlog-Eintrags („bindet
`require_admin()` oder `auth_guard.php` ein und ruft `ui_geruest_start()`
nicht") liefert heute **15** Treffer, alle berechtigt: Bibliotheken
(`db.php`, `demo_lib.php`, `migration_lib.php`, `session_lib.php`,
`wartung_lib.php`, `auth_guard.php` selbst), Endpunkte ohne Seite
(`ingest.php`, `gpx.php`, `jobs.php`, `logout.php`), Seiten vor der
Anmeldung (`login.php`, `wiederherstellen.php`, `rechtstext_seite.php`,
`admin_rechtstexte.php`) und der Notausgang `update.php`. Ein Mittel, das mit
15 Rot anfängt, wird nie wieder gelesen.

Das Kriterium „gibt eigenes Markup aus" hat im Code einen Marker:
`ui_seite_start()` (`ui.php`), der Anfang jeder Seitenhülle. **37** Dateien
rufen es. Davon rufen **7** kein `ui_geruest_start()`: `install.php`,
`login.php`, `pw_handling.php`, `rechtstext_seite.php`, `reset_request.php`,
`session_lib.php`, `wiederherstellen.php` — dem Namen nach alles Seiten vor
oder außerhalb der Anmeldung, die das Gerüst (Diensttag-Leiste, Menü) gar
nicht haben dürfen. Umgekehrt ruft `apk.php` `ui_geruest_start()` ohne
`ui_seite_start()`, und `version.php` nennt `ui_geruest_start()` in einem
Kommentar — beides muss die Prüfung richtig einordnen (siehe E-BR3-06).

### 1.3 Block C

**Nr. 173 — tote Regeln.** `tools/referenzdatensatz/vergleich/ausnahmen/
edbak_umlauf.json` trägt zwei Regeln mit „GEMESSEN 143x" zu den Notizen,
`csv_umlauf.json` eine zu `missions.notes`; dazu die Regel zur Nutzlastnummer
(`kopf.version` „nach 11"). Beide Kreisläufe erfüllen ihr Kriterium
(edbak 287 687 Einzelvergleiche / 0 unerklärt, csv 9 120 / 0 unerklärt,
Stand Backlog-Runde 2) und melden dabei **3 bzw. 2 ungenutzte Regeln**
(`vergleichen.py` zählt sie als `ungenutzte_regeln`). Die Regeln beschreiben
den Übergang aus S9/AP7, den der neue Referenzbestand nicht mehr kennt.

**Nr. 174 — Referenzbestand ohne „Rettungsmittel ohne Standort".** Die
Quelldaten sind repariert: `tools/referenzdatensatz/quelldaten/
stammdaten.json` trägt zwei Einträge mit `"ohne_standort": true`. Die
Referenzdateien unter `referenz/` (`einsatzdoku-backup-2026-09-12.edbak`
und der CSV-Export vom 12.09.2026) stammen aus dem Neubau davor und führen
weiterhin sechs Rettungsmittel **mit** Standort; die `.edbak` ist verschlüsselt
und hier nicht gegenprüfbar. Damit fehlt dem Kreislauf der Fall
`base_ref: null` — seit E-S9-18 einer von zweien, kein Sonderfall.

---

## 2. Entscheidungen

Die Entscheidungen vom 12.09.2026 stehen im Protokoll und im Backlog; hier
nur, was dieses Paket braucht, plus der Schnitt vom 13.09.2026.

| Nr. | Entscheidung | Quelle |
|---|---|---|
| **E-BR3-01** | Nr. 117: **nicht erheben.** Das Handbuch sagt, dass die Anwendung es nicht weiß und was die Kennzahlen messen | Entscheidung 6, 12.09.2026 |
| **E-BR3-02** | Nr. 41: `rea-kopf`, `rea-beginn`, `phasen-name` werden **gestrichen** — aus dem Markup entfernt und **mit Begründung** auf `streichliste.md`; `imp-warn` und `imp-daygroup` bekommen eine Regel in der Mockup-Runde 9c | Entscheidung 7, 12.09.2026 |
| **E-BR3-03** | Die drei Streichungen laufen **in Block A** mit, nicht in 9c: Sie brauchen keine Freigabe, und `pruefen.py` meldet danach 2 statt 5 `[offen]` | Auftraggeber 13.09.2026 |
| **E-BR3-04** | Nr. 94: **ein Wort** — „bitgleich" wird an beiden Stellen zu „pixelgleich". Kein `exclude-chunk`; die stärkere Zusage wird nicht eingelöst, weil niemand sie braucht | Durchsicht 12.09.2026 (Zuspitzung) |
| **E-BR3-05** | Nr. 47 und 58 werden **eine neue Prüfgruppe „5 Zusagen" in `tools/vollstaendigkeit/pruefen.py`**, nicht ein eigenes Werkzeug. Grund: Das Mittel liest bereits `server/**/*.php` und `server/assets/*.js`, hat Bericht, Listenleser und Ausnahmemechanik; Nr. 36 (Klassennamen, die nur JS sucht) kann später in dieselbe Gruppe. Ausnahmen mit Begründung in einer neuen Liste **`tools/vollstaendigkeit/zusagen.md`** (Muster: `ausnahmen.md` — erste Spalte ist die Datei bzw. das Muster, nicht die Zeile) | Fable 13.09.2026, bestätigt |
| **E-BR3-06** | **Kriterium für Nr. 58:** Jede Datei, die `ui_seite_start(` aufruft, muss auch `ui_geruest_start(` **und** `ui_geruest_ende(` aufrufen — oder mit Begründung in `zusagen.md` stehen. Aufrufe zählen nur außerhalb von Kommentaren (`/* */`, `//`, `#`). Gegenrichtung als **Hinweis**, nicht Befund: `ui_geruest_start(` ohne `ui_seite_start(` (heute `apk.php`). Erwartet beim ersten Lauf: **7 Ausnahmen, 0 Befunde** | Fable 13.09.2026, bestätigt |
| **E-BR3-07** | **Kriterium für Nr. 47:** `confirm(`, `alert(`, `prompt(` — auch als `window.`-Aufruf — in `server/**/*.php` und `server/assets/*.js`, außerhalb von Kommentaren und außerhalb von Zeichenketten, die `edConfirm`/`edAlert`/`edPrompt` heißen (Wortgrenze davor). Erwartet: **2 Ausnahmen** (`confirm.js`, `forms.js`), **0 Befunde** | Fable 13.09.2026, bestätigt |
| **E-BR3-08** | Nr. 67: **nur der Unterpunkt** (Tausch in `kdf_upgrade.php`). Der Punkt bleibt offen, Zuordnung P5; der Backlog-Eintrag bekommt den Vermerk | Auftrag 13.09.2026 |
| **E-BR3-09** | Nr. 173 und 174 bilden **Block C** und laufen **zuletzt**; die Regressionsläufe des Abschlusses laufen gegen die **neue** Referenz | Fable 13.09.2026, bestätigt |
| **E-BR3-10** | Nr. 174: Die Referenzdateien werden über die **reguläre Kette** neu erzeugt (Erzeugen → Einspielen → Exportieren), nicht von Hand berichtigt — die `.edbak` ist versiegelt, und der Einspielweg ist selbst ein Prüfling (S2/AP5) | Backlog Nr. 174, Nr. 46 |
| **E-BR3-11** | **Textpflege:** kein Block — mit Teil 1 des Auftrags am 13.09.2026 erledigt | Entscheidung 16, ausgeführt |
| **E-BR3-12** | **Keine Fable-Schritte.** Zwei Haltepunkte (Abschnitt 3), an denen die Umsetzung anhält und berichtet, statt zu entscheiden | Fable 13.09.2026 |
| **E-BR3-13** | **Die Zeile in Rahmenplan Abschnitt 5 wandert mit ihrem Paket, nicht gesammelt in AP10.** AP10 (4) hatte „sieben Zeilen raus" am Ende vorgesehen. Das geht nicht: Der Rahmenplan verlangt seit Fassung 45/46, dass **wer einen Backlog-Punkt austrägt, die Selbstprüfzahl im selben Zug nachrechnet** — und die Zahl wird rot, sobald ein Punkt den Backlog verlässt und seine Zeile stehen bleibt. Nach AP1 gemessen: **58 = 58**. AP10 rechnet am Ende nur noch nach, statt auszutragen | Umsetzung 13.09.2026, aus Rahmenplan Abschnitt 5 |
| **E-BR3-15** | **Die Abnahme von AP2 ist berichtigt: `grep -ci bitgleich tools/uhr-bilder/` = 0 ist nicht erfüllbar.** Das Konzept verlangte in AP2 beides — den Zusatz in der LIESMICH, „dass `compare -metric AE` genau das misst und ein PNG wegen des Zeitstempel-Chunks nie bitgleich ist" (E-BR3-04), **und** null Vorkommen des Wortes. Der Zusatz lässt sich ohne das Wort nicht schreiben; er verneint es. Neues Sollmaß, das die Absicht trifft: **null Vorkommen, die die Bitgleichheit behaupten** — jedes verbliebene ist Verneinung oder datierte Rückschau. Gemessen nach AP2: **3 Vorkommen, 0 Behauptungen** | Umsetzung 13.09.2026 |
| **E-BR3-14** | **Die Stufe der Runde ist `Web 19.3.1` — eine Korrektur.** K3 und Abschnitt 5 hatten die Wahl der Umsetzung überlassen. Begründung: Von den neun Punkten fassen genau zwei `server/` an, und beide sind nach der Zählweise in CLAUDE.md 2 Korrektur — die CSRF-Prüfung vor den Demo-Ausstieg setzen (AP4, Fehlerbehebung) und drei Klassen ohne Regel aus dem Markup streichen (AP5, Feinschliff). Keine neue Funktion, kein neues Feld, keine Migration. Die Überschrift im Changelog steht seit AP1, damit die `tools/`- und `docs/`-Punkte einen Ort haben; `version.php` steigt erst mit AP4 | Umsetzung 13.09.2026 (K3) |
| **E-BR3-16** | **Der Bilderlauf des Abschlusses läuft über ALLE Seiten, nicht über die zwei berührten.** AP10 (1) hatte „Bilderlauf auf den zwei berührten Seiten" vorgesehen. Der Vergleich vor/nach auf diesen Seiten hat AP5 aber schon geführt (0 abweichende Bildpunkte auf vier Seiten) — AP10 beantwortet die andere Frage: ob die Runde irgendwo **sonst** etwas kaputtgemacht hat. Dafür reichen zwei Seiten nicht. Gemessen: 45 Seiten, 360 Bilder, 0/0/0 | Umsetzung 13.09.2026, AP10 |
| **E-BR3-17** | **Nr. 176 wird in DIESER Stufe behoben, nicht in der nächsten Runde — als elftes Paket (AP11).** Die Umsetzung hatte den Fund nach K4 nur eingetragen, mit der Begründung, er ändere das Messmittel, mit dem diese Runde ihre Zahlen belegt. Der Auftraggeber hat den Gegenschluss gezogen: **Weil** das Messmittel die Zahlen dieser Stufe trägt, gehört es in diese Stufe. Eine Zahl wird nicht belastbar, indem das ungenaue Mittel eine Backlog-Nummer bekommt. Keine Versionsstufe — nur `tools/` und `docs/` | Auftraggeber 13.09.2026, nach AP10 |
| **E-BR3-18** | **Die drei Funde des Nachtrags (178, 179, 180) werden in derselben Stufe abgearbeitet — als AP12.** Auch hier hat der Auftraggeber entschieden („alles drei einfach kurz abarbeiten"), und die Begründung ist dieselbe wie bei E-BR3-17: Alle drei sind Prüfmittel, und Prüfmittel tragen die Zahlen dieser Stufe. **Ausdrücklich NICHT mitgemacht:** die Content-Security-Policy (Nr. 181). Sie ist die Laufzeitseite von Nr. 179, braucht Ausnahmen für vier Kachelserver und den Adressdienst — dessen Anschrift eine Einstellung ist — und ist damit eine Festlegung, keine Korrektur | Auftraggeber 13.09.2026, nach AP11 |

---

## 3. Offene Fragen und Haltepunkte

**Keine offene Frage an den Auftraggeber.** Zwei Stellen, an denen die
Umsetzung **anhält und berichtet**, statt selbst zu entscheiden:

- **H-BR3-1 (AP7):** Findet die Prüfung nach E-BR3-06 eine Datei außerhalb
  der sieben, oder stellt sich beim Lesen heraus, dass eine der sieben eine
  **angemeldete** Seite mit fehlendem Gerüst ist (also ein echter Fund wie
  seinerzeit `tag_spuren.php`) — dann ist das ein Fehler in der Anwendung,
  keine Ausnahme. Anhalten, Fund benennen, nicht auf die Liste setzen.
- **H-BR3-2 (AP9):** Zeigt der neue Referenzlauf Abweichungen, die nicht
  Nr. 174 sind (die beiden Rettungsmittel), oder scheitert die Kette an einer
  Stufe — anhalten. Die Referenz ist die Vergleichsgrundlage aller späteren
  Regressionen; eine Referenz, die einen unverstandenen Zustand einfriert,
  ist schlimmer als die alte.

Alles andere ist entschieden oder beim Bauen zu messen.

---

## 4. Arbeitspakete

Jedes Paket ist einzeln abnehmbar. Nach jedem Paket: Abschnitt 8 dieses
Dokuments fortschreiben (Stand, Probleme, Lösungen), Prüfdokument
nachziehen, geänderte Dateien ausgeben. Die Pflichten aus `CLAUDE.md` 2
stehen bei jedem Paket dabei — sie sind Teil der Änderung, kein Nachklapp.

### Block A — billig

#### AP1 · Nr. 91 — die Lehre zu `WatchUi.Confirmation` aufschreiben

*Ziel:* Die nächste Instanz sucht nicht wieder eine halbe Stunde an der
Tastensteuerung.

*Was zu tun ist:* In `tools/uhr-pruefstand/LIESMICH.md`, Abschnitt
„Bedienung simulieren", nach „Tasten sind heikler als Maus" ein
Unterabschnitt **„`WatchUi.Confirmation`: die Auswahl ist im Bild nicht zu
sehen"** mit den vier Sätzen aus dem Befund (1.1): kein sichtbarer
Unterschied zwischen `Cancel` und `Confirm`, `Up`/`Down` ändern nichts
Sichtbares; `Return` allein bestätigt; BACK räumt ohne `onResponse` weg; eine
Ablehnung wird an der Wirkung gemessen (Datenbank), nicht am Bild. Dazu der
Verweis auf den Rundlauf, der es so macht.

*Nicht:* keine Änderung an `pruefstand.sh`, kein neuer Prüfweg.

*Abnahme:* `grep -c Confirmation tools/uhr-pruefstand/LIESMICH.md` ≥ 1; der
Abschnitt nennt alle vier Aussagen. Backlog Nr. 91 nach *Erledigt* mit
Fundstelle.

*Pflichten:* keine Versionsstufe (nur `tools/`), Changelog-Satz unter der
Stufe dieser Runde, Backlog.

#### AP2 · Nr. 94 — ein Wort

*Was zu tun ist:* „BITGLEICH" im Kopfkommentar von
`tools/uhr-bilder/erzeugen.sh` und „bitgleich" in
`tools/uhr-bilder/LIESMICH.md` („Warum es dieses Werkzeug gibt") werden
**„pixelgleich"** — mit dem Zusatz in der LIESMICH, dass `compare -metric
AE` genau das misst und ein PNG wegen des Zeitstempel-Chunks nie bitgleich
ist (E-BR3-04).

*Abnahme:* `grep -ci bitgleich tools/uhr-bilder/` liefert **0**; die Wortliste
(`tools/wortliste/`) bleibt bei 0 Treffern außerhalb der Ausnahmen. Backlog
Nr. 94 nach *Erledigt*.

*Pflichten:* keine Versionsstufe, Changelog-Satz, Backlog.

#### AP3 · Nr. 117 — ein Absatz im Handbuch

*Was zu tun ist:* In `docs/Handbuch.md` 11.2 „Die Liste der NutzerInnen",
direkt nach dem Absatz über die vier Zahlen, ein Absatz, der sagt: Alle vier
messen **Konto-Backups der Verwaltung** — den Stand des jüngsten Pakets im
Kontoordner. Ob eine NutzerIn selbst je ein Backup heruntergeladen hat,
**weiß die Anwendung nicht**: Die Datei entsteht im Browser, der Server sieht
sie nie, und es wird bewusst nicht erhoben (Ende-zu-Ende-Zusage). Wer das
wissen will, fragt die NutzerIn. Ton wie das übrige Handbuch: erklärend, kein
Warnkasten.

*Nicht:* keine Spalte, kein Zeitstempel, kein Text in der Oberfläche.

*Abnahme:* Der Absatz steht; `docs/Handbuch.md` nennt an keiner Stelle mehr
ein „letztes Backup der NutzerIn" als Messwert (grep nach „zuletzt
Backup"/„letztes Backup" sichten). Backlog Nr. 117 nach *Erledigt* mit
Verweis auf den Abschnitt.

*Pflichten:* keine Versionsstufe, Changelog-Satz, Backlog.

#### AP4 · Nr. 67, Unterpunkt — zwei Zeilen tauschen

*Was zu tun ist:* In `server/api/kdf_upgrade.php` die CSRF-Prüfung
(`hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')`)
**vor** den Demo-Ausstieg (`demo_ist_demo($userId)`) setzen. Den
Kopfkommentar der Datei um einen Satz ergänzen, warum die Reihenfolge so ist
(die Prüfung gilt für jeden Aufrufer, auch den, der sofort wieder geht).

*Vorher messen:* `assets/unlock.js` schickt `X-CSRF` bei diesem Aufruf mit —
am 13.09.2026 ja (Header `'X-CSRF': CSRF`). Wenn nicht mehr: anhalten.

*Nicht:* nichts an `csrf_check()` — das ist der Hauptpunkt und bleibt P5.

*Abnahme:* Anmeldung mit dem Demo-Konto auf dem Prüfstand läuft durch
(`kdf_upgrade` antwortet `uebersprungen: demo`); ein Aufruf **ohne** Header
bekommt jetzt auch für das Demo-Konto **403** (`curl`, Sitzungscookie des
Demo-Kontos). Backlog Nr. 67: Unterpunkt als erledigt vermerkt, Punkt bleibt
offen (P5); Rahmenplan Abschnitt 5 unverändert.

*Pflichten:* **Versionsstufe Web** (die Umsetzung entscheidet Korrektur
oder Neben — die ganze Runde ist eine Stufe), `version.php`-Kopf, Changelog
mit Begründung, `Technik.md` nur, wenn der Endpunkt dort mit der
Reihenfolge beschrieben ist (prüfen).

#### AP5 · Nr. 41 — drei Klassen streichen

*Was zu tun ist:*
1. `einsatz_form.php`: `rea-kopf` aus `kopf.className`, `rea-beginn` aus
   `lab.className` entfernen (die Elemente bleiben, ihr Aussehen kommt von
   `phasen-eingabe` bzw. der Elementregel für `label` — das war der Befund).
2. `einsatz.php`: `class="phasen-name"` an beiden `<span>` entfernen; das
   `<span>` selbst bleibt, falls ein Skript den Text greift — vorher prüfen,
   ob es ein `querySelector('.phasen-name')` gibt (am 13.09.2026: nein).
3. `tools/vollstaendigkeit/ohne-regel.md`: die drei `[offen]`-Zeilen
   entfernen.
4. `tools/vollstaendigkeit/streichliste.md`: drei Zeilen mit **Begründung**
   („Klasse ohne Regel und ohne Leser; die Kopfzeile einer Reanimationssitzung
   soll sich nicht von einer Phasenzeile abheben — Entscheidung 7 vom
   12.09.2026"), Paket „Backlog-Runde 3".

*Abnahme:* `python3 tools/vollstaendigkeit/pruefen.py`: „im Markup ohne
Regel, als [offen] vermerkt" = **2** (nur `imp-warn`, `imp-daygroup`);
„auf der Streichliste, aber noch im Markup" = **0**; „ohne-regel.md: Eintrag
ungenutzt" = **0**. Bilderlauf auf der Einsatzbearbeitung mit einer
Reanimation: **0 Pixel** Unterschied gegen den Stand davor (Stilvergleich
oder `compare -metric AE` auf den Bildern) — die Klassen hatten keine Regel,
also darf sich nichts bewegen. Backlog Nr. 41: Vermerk, dass die
Streichungen erledigt sind und die zwei Regeln bei 9c liegen; **bleibt
offen**.

*Pflichten:* `server/` ist berührt → in derselben Web-Stufe wie AP4;
Changelog; `Design.md` nur, wenn dort eine der drei Klassen genannt ist
(prüfen; am 13.09.2026 nicht).

### Block B — zwei Prüfmittel

#### AP6 · Nr. 47 — natives `confirm()` fernhalten

*Was zu tun ist:* In `tools/vollstaendigkeit/pruefen.py` eine neue Gruppe
**„5 Zusagen"** (E-BR3-05) mit der Prüfung **„native Dialoge"** nach
E-BR3-07. Neue Liste `tools/vollstaendigkeit/zusagen.md` mit Kopftext (welche
Zusage, warum eine Liste) und Tabelle `| Datei | Muster | Grund |`; zwei
Einträge: `assets/confirm.js` (`window.confirm` — Rückfall für Browser ohne
`<dialog>`), `assets/forms.js` (`window.confirm` — Rückfall, wenn
`edConfirm` nicht geladen ist). Ausgabe wie die anderen Gruppen: Zahl und
Liste mit `Datei:Zeile`; ein Eintrag der Liste, der nicht mehr im Code
vorkommt, ist ein Befund („Ausnahme ungenutzt") — wie bei `ohne-regel.md`.

*Nicht:* kein Umbau der bestehenden vier Gruppen; kein Treffer wird durch
Umschreiben des Codes vermieden.

*Abnahme:* Lauf meldet „native Dialoge: 0" mit „2 Ausnahmen"; ein
Probe-`window.confirm('x')` in einer beliebigen PHP-Datei macht den Lauf rot
(Rückgabewert ≠ 0) und wird danach wieder entfernt. `LIESMICH.md` des
Werkzeugs: Tabelle „Die fünf Prüfungen" um die Gruppe ergänzt (und die
Zählung im Kopf berichtigt — es sind dann fünf Prüfungen plus Ausgabe).
Backlog Nr. 47 nach *Erledigt*.

*Pflichten:* keine Versionsstufe (nur `tools/`), Changelog-Absatz mit
Begründung, `Technik.md` Abschnitt Prüfmittel, falls die Gruppen dort
aufgezählt sind (prüfen).

#### AP7 · Nr. 58 — Seite ohne Gerüst

*Was zu tun ist:* Zweite Prüfung der Gruppe „5 Zusagen": **„Seite ohne
Gerüst"** nach E-BR3-06. Kommentarfreier Text je Datei (ein Helfer
`ohne_php_js_kommentare()` neben dem vorhandenen `ohne_kommentare()` für
CSS). Sieben Einträge in `zusagen.md`, **jeder mit dem Grund aus der Datei
selbst** — nicht „Seite vor der Anmeldung" als Sammelgrund, sondern je Datei
der Satz, warum sie kein Gerüst hat (`login.php`: Anmeldung, keine Sitzung;
`install.php`: vor der ersten Anmeldung; `reset_request.php` und
`wiederherstellen.php`: Zugang ohne Sitzung; `rechtstext_seite.php`:
öffentliche Rechtstexte; `pw_handling.php` und `session_lib.php`: prüfen, was
sie rendern und ob es außerhalb der Sitzung liegt — **wenn nicht: H-BR3-1**).
Gegenrichtung `ui_geruest_start(` ohne `ui_seite_start(` als Hinweis
(`apk.php` — nachsehen, warum, und den Grund als Kommentar in `apk.php`
lassen, nicht auf die Liste).

*Abnahme:* Lauf meldet „Seite ohne Gerüst: 0" mit „7 Ausnahmen", Hinweis
„Gerüst ohne Seitenhülle: 1"; ein Probe-`ui_seite_start()` ohne Gerüst in
einer neuen Datei macht den Lauf rot. `tag_spuren.php` — der Anlass des
Punktes — steht nicht auf der Liste und ist kein Befund. Backlog Nr. 58 nach
*Erledigt*; im Eintrag das Kriterium mit einem Satz nennen, damit die naive
Regel nicht wieder vorgeschlagen wird.

*Pflichten:* wie AP6.

### Block C — Referenzbestand

#### AP8 · Nr. 173 — tote Regeln entfernen

*Was zu tun ist:* Beide Kreisläufe laufen lassen und die als ungenutzt
gemeldeten Regeln **namentlich** aus `vergleich/ausnahmen/edbak_umlauf.json`
(drei) und `csv_umlauf.json` (zwei) entfernen. In das Feld `beschreibung`
der jeweiligen Datei (oder den Änderungsverlauf, falls die Datei einen
trägt) ein datierter Satz: welche Regeln entfernt wurden und warum sie
gegenstandslos sind (Notizen seit dem Neubau des Referenzbestands von Anfang
an im Block; Nutzlastnummer auf beiden Seiten gleich).

*Vorher messen:* Die Zahl der ungenutzten Regeln **vor** dem Entfernen
(erwartet 3 / 2). Ist sie anders, erst verstehen, dann streichen.

*Abnahme:* `kreislauf.py --art edbak --frisch` und `--art csv --frisch`:
**0 unerklärte Abweichungen, 0 ungenutzte Regeln**, Einzelvergleiche in der
Größenordnung von Runde 2 (edbak ≈ 287 000, csv ≈ 9 100 — die genaue Zahl
ins Prüfdokument). Backlog Nr. 173 nach *Erledigt*.

*Pflichten:* keine Versionsstufe, Changelog-Absatz, Backlog. **Achtung:**
Diese Abnahme ist vorläufig — AP9 ersetzt die Referenz, und dann laufen beide
Kreisläufe noch einmal (AP10).

#### AP9 · Nr. 174 — Referenzdateien neu erzeugen

*Was zu tun ist:* Die drei Läufe aus `tools/referenzdatensatz/LIESMICH.md`
in dieser Reihenfolge: (1) `quelldaten/pruefen.py`, `generator/erzeugen.py`,
`generator/pruefen.py` — die Prüfung der Quelldaten muss die zwei
Rettungsmittel ohne Standort zeigen (Abdeckungsmatrix); (2) Einspielen über
`einspielen/lokal_starten.sh` und `einspielen.py` in allen Stufen samt
`browser/csv_import.mjs`; (3) `browser/referenz_export.mjs` — beide
Referenzdateien neu. Die alten Dateien unter `referenz/` werden **ersetzt**
(Dateinamen tragen das Datum; der Verweis in der LIESMICH und in
`kreislauf.py`, falls dort ein Name steht, zieht nach). Die Altformat-Referenz
unter `referenz/altformat/` bleibt unberührt (Nr. 46).

*Vorher messen:* In der frisch eingespielten Installation stehen zwei
Rettungsmittel unter „Ohne Standort" (Einstellungen → Rettungsmittel, oder
`vehicles.base_id IS NULL` = 2 in der Prüfstand-Datenbank).

*Abnahme:* Beide Kreisläufe gegen die **neue** Referenz: **0 unerklärt,
0 ungenutzt**; im edbak-Umlauf kommt ein Rettungsmittel mit `base_ref: null`
unverändert zurück (Abnahmekriterium aus Nr. 174 — als eigene Zeile im
Prüfdokument, mit der Zahl der Rettungsmittel ohne Standort vorher/nachher
= 2/2). Klickprobe (`tools/klickprobe/probe.mjs`) 40 von 40 — sie hat den
Verlust gefunden und muss den Zustand jetzt bestätigen. Backlog Nr. 174
nach *Erledigt*.

*Bei Abweichung:* H-BR3-2.

*Pflichten:* keine Versionsstufe, Changelog-Absatz (die Referenz ist Teil
des Repositoriums; wer später vergleicht, muss wissen, seit wann sie
`base_ref: null` trägt), `tools/referenzdatensatz/LIESMICH.md`
(„Der Bestand in Zahlen" nachziehen), Backlog.

### Abschluss

#### AP10 · Regression, Dokumente, Übergabe

1. **Regressionspflicht R24:** beide Kreisläufe gegen die neue Referenz —
   Zahlen ins Prüfdokument. Dazu die üblichen Mittel mit ihren Zahlen (Muster:
   `Pruefdokument-Backlog-Runde-2.md`, Abschnitt 2): Wortliste,
   Vollständigkeit (jetzt mit Gruppe 5 — die Gesamtzahl der Befunde sinkt um
   die drei `[offen]`), Linkprobe, Wartungsprobe, Klickprobe, Bilderlauf
   auf den zwei berührten Seiten (Einsatzbearbeitung, Einsatzansicht).
2. **Dokumente auf Konsistenz:** `Technik.md` (Prüfmittel-Abschnitt,
   Verzeichnisstruktur, falls `zusagen.md` dort gelistet gehört),
   `Handbuch.md` (AP3), beide `LIESMICH.md` (AP1, AP2, AP6/7), Changelog
   (eine Stufe, erklärende Prosa mit Begründung je Punkt).
3. **Backlog:** sieben Punkte nach *Erledigt* (91, 94, 117, 47, 58, 173,
   174) mit Vermerk; 67 und 41 mit Vermerk offen; Kopf: kein neuer
   Absatz nötig, es sind keine Nummern vergeben worden.
4. **Rahmenplan:** Abschnitt 5 — sieben Zeilen raus, Selbstprüfzahl mit dem
   Einzeiler nachgerechnet (**52 = 52**, wenn kein Punkt dazukommt);
   Abschnitt 8 — Zeile „Backlog-Runde 3"; Abschnitt 10 — Fassung;
   Fahrplan Schritt 9 — Status.
5. **Prüfdokument** nach K9 fertigstellen: Vorlage
   `Pruefdokument-Backlog-Runde-3.md` mit Zahlen füllen, Nicht-Prüfbares
   zuerst, Prüfliste für den Auftraggeber.
6. **Ausgabe:** ZIP mit der Ordnerstruktur des Repositoriums, nur geänderte
   und neue Dateien; dieses Konzept mit fortgeschriebenem Abschnitt 8.

---

## 5. Was bei jeder Codeänderung mitläuft (CLAUDE.md 2)

- **Eine Web-Stufe** für die ganze Runde (AP4 und AP5 berühren `server/`);
  ob Korrektur oder Neben, entscheidet die Umsetzung (K3). Uhr und Android:
  keine Stufe — nichts berührt.
- **Changelog:** ein Eintrag für die Stufe, je Punkt ein Absatz mit
  Begründung — auch für die `tools/`- und `docs/`-Punkte, die keine Stufe
  auslösen (sie stehen unter derselben Überschrift).
- **Doku nachziehen:** Handbuch (AP3), Technik (Prüfmittel), die LIESMICHs.
  Entferntes wird ausgetragen (die drei Klassen, falls irgendwo genannt).
- **Backlog pflegen:** siehe AP10 (3).

---

## 6. Prüfprotokoll

Die Zahlen stehen im Prüfdokument (K9), `Pruefdokument-Backlog-Runde-3.md`,
Abschnitt 2; ob die Abnahme je Paket erfüllt ist, sagt Abschnitt 8 dieses
Dokuments. **Abnahme aller zehn Pakete erfüllt** (13.09.2026), mit zwei
berichtigten Sollwerten (E-BR3-15, E-BR3-16) und zwei Sollzahlen des Konzepts,
die zu hoch bzw. zu niedrig waren (AP5: 331 → **330**; AP7: 37 → **36**
Aufrufer von `ui_seite_start(`).

**Was die Runde belegt, in einer Zeile:** Wortliste **0/0** · Vollständigkeit
**330** Befunde, `[offen]` **2**, Gruppe 5 **0/2/0** und **0/7/0** · Linkprobe
**117/0** · Wartungsprobe **55/0** · Spurprobe **45/0** · Kontraste **22/0** ·
Klickprobe **40/40** · Kreisläufe **287 687/0** und **9 120/0** gegen die neue
Referenz · Bilderlauf **360 Bilder / 45 Kontaktbögen, 0/0/0** · `php -l`
**100/0** · Selbstprüfzahl **54 = 54** · Backlog-Nummernmenge gegen `dabd7a3`
**0 verloren, 0 doppelt**.

**Und was sie nicht belegt** (ausführlich im Prüfdokument, Abschnitt 0 und 5):
kein Simulatorlauf für Nr. 91 und 94, kein Produktivstand. **Der Vorbehalt zu
„0 Konsolenfehler" ist mit AP11 weg** — er lautete „0 außerhalb dreier
Fehlerarten, die der Rauschfilter verschluckt", und genau diese drei Arten
zählt der Filter jetzt mit, wenn sie auf der eigenen Basis auftreten (Nr. 176;
Selbstprobe 10 von 10, dieselbe Zahl 0 nach dem erneuten Lauf). Was bleibt: ein
Verbindungsfehler auf einer **fremden** Adresse ist weiterhin stumm — dass zur
Laufzeit keine fremde Quelle angefragt wird, messen andere.

---

## 7. Fehlerfunde (K4)

Was beim Bauen auffällt und nicht zu dieser Runde gehört, steht hier mit
Datei, Symptom und Vermutung — und wird **nicht** nebenbei behoben. Am Ende
entscheidet der Auftraggeber, was davon ein Backlog-Punkt wird.

**F-BR3-02 · `apk.php` lieferte seine 404-Seite ohne Seitenhülle — gefunden
und behoben in AP7.** Steht hier der Vollständigkeit halber, obwohl er nicht
offen ist: Er gehört nicht zum Auftrag von Nr. 58, sondern ist sein erstes
Ergebnis. Die Datei rief `ui_geruest_start()`, `ui_geruest_ende()` und
`ui_seite_ende()`, aber nie `ui_seite_start()`; damit fehlten `<!doctype>`,
`<head>`, `<title>` und das Stylesheet. Belegt am Prüfstand (HTML beginnt mit
`<header class="kopf">`, `document.compatMode` = `BackCompat`, Times New
Roman) und mit Bild. Behoben mit einer Zeile unter Web 19.3.1, Kommentar
daneben. **Nicht auf der Ausnahmeliste** — H-BR3-1: ein Fund gehört behoben,
nicht erklärt.

**F-BR3-07 · Die Kopplungsprobe ist seit dem 06.09.2026 rot, und niemand hat
sie gefahren.** (AP11, 13.09.2026 — jetzt **Backlog Nr. 180**, nach K4 nicht
mitbehoben.) Um Nr. 178 zu belegen, musste die Kopplungsprobe einmal laufen.
Sie meldete **25 Erwartungen, 1 nicht erfüllt**: „Alle sichtbaren Knöpfe 44 px
— 6 Knöpfe, 36 px". `rundlauf.mjs:214` verlangt `every(h => h === 44)`, einen
fest verdrahteten Sollwert; seit Web 15.5.0 (E-S8-09, R76) gelten **zwei**, und
bei 1280 px am Zeigergerät sind 36 px richtig. **Der Fehler liegt im
Prüfmittel, nicht in der Anwendung** — belegt vom Bilderlauf, der beide
Sollwerte kennt und über 360 Aufnahmen **0** Knöpfe falscher Höhe meldet. Die
unangenehme Zahl ist das Datum: Der Sollwert wurde am 06.09.2026 zweigeteilt,
der Rundlauf ist seither rot, und aufgefallen ist es am 13.09.2026 nur, weil
ein anderer Punkt zufällig dazu führte, ihn zu fahren. Er steht in keiner
Reihe von Mitteln, die nach einem Paket laufen — das ist der eigentliche
Befund.

**F-BR3-06 · Die Selbstprobe zu Nr. 176 war blind — mein eigener Fehler,
gefunden von der Gegenprüfung, behoben im selben Paket.** (AP11, 13.09.2026.)
Der erste Entwurf hatte zehn Fälle und meldete **10 von 10 auch dann, wenn man
Klasse 1 oder Klasse 3 aus `istRauschen()` löschte** (gemessen, beide
Mutationen). Der Grund: Alle verwerfenden Fälle mit Fundstelle trugen einen
Kachelgastgeber in der URL — also fing sie Klasse 1, und fiel die weg, fing sie
Klasse 3. Die Probe belegte damit genau die Unterscheidung **nicht**, um die
der ganze Nachtrag geht; sie bestätigte sich selbst. Das ist wörtlich die
Falle aus `CLAUDE.md` 6 („Eine grüne Zahl ist erst dann ein Beleg, wenn sie das
Gemessene benennt") und widersprach dem Sollwert, den ich für diese Probe
selbst hingeschrieben hatte („die Probe muss am alten Stand rot werden, sonst
prüft sie nichts"). Behoben mit fünf weiteren Fällen (11 bis 15), von denen
jeder **eine** Klasse trägt, und mit einer Mutationsprobe, deren Rezept in der
LIESMICH steht. **Zwei weitere Funde derselben Gegenprüfung sind Code:** Klasse
2 verwarf Verbindungsabbrüche auf der Seitenadresse selbst — also genau die
drei Codes von Nr. 176 —, und eine unlesbare Fundstelle (`<anonymous>`) fiel
zur stillen Seite, während die leere gezählt wurde. Beides behoben und mit
Fällen belegt. **Und zwei sind Zahlen:** „53 = 53" und „173 Einträge" im
Prüfdokument waren geschrieben, bevor Nr. 178 und 179 angelegt waren, und nicht
neu gemessen — derselbe Fehler, den diese Runde in AP10 schon beschrieben hat.

**F-BR3-05 · Dieselbe Lücke, größer, in der Kopplungsprobe.** (AP11,
13.09.2026 — jetzt **Backlog Nr. 178**, nach K4 **nicht** mitbehoben.) Nachdem
Nr. 176 behoben war, lag die Frage auf der Hand, ob die anderen
Browserwerkzeuge denselben Fehler tragen. **Zwei nicht:**
`tools/referenzdatensatz/browser/papierkorb_misch.mjs` prüft Text **und**
Fundstelle und führt im Muster nur Gastgebernamen plus zwei Codes, die auf
`127.0.0.1` nicht vorkommen können; sein Kommentar nennt `tools/screenshots,
istRauschen` als Vorbild — der Bilderlauf war also das schwächere von beiden,
und das ist jetzt umgekehrt. `tools/messstand/browserprobe.mjs` hängt an
`requestfailed` und hat die Adresse immer dabei. **Eines doch, und breiter:**
`tools/kopplungsprobe/rundlauf.mjs` filtert
`/tile\.openstreetmap\.org|ERR_ABORTED|Failed to load resource/` nur über den
Text — die dritte Alternative verwirft **jede** Ressourcenmeldung. Gemessen an
acht gebauten Fällen: **4 von 8** falsch, alle vier verschluckte echte Fehler,
darunter ein **404** und ein **500** auf der eigenen Basis. Für die feuert
`requestfailed` nicht, sie stehen nur in der Konsole — das Werkzeug könnte
einen Serverfehler mitten im Kopplungsrundlauf nicht sehen. **Nicht
mitbehoben**, weil die Kopplungsprobe ihre eigene Abnahme braucht (ein
eingeschleuster 500er im Rundlauf) und dafür ein gekoppeltes Gerät am Stand —
das ist ein Paket, kein Nachtrag.

**F-BR3-03 · Der Rauschfilter des Bilderlaufs verschluckte auch lokale
Fehler — gefunden in AP10, auf Anweisung behoben in AP11.** (13.09.2026 —
**Backlog Nr. 176**, erledigt.) `istRauschen()` in
`tools/screenshots/aufnehmen.mjs` prüfte `KACHELRAUSCHEN` gegen den
Meldungstext **und** gegen die Fundstelle. In diesem einen Muster standen neben
den Kartenhosts drei Fehlercodes — `ERR_CONNECTION_RESET`,
`ERR_CONNECTION_CLOSED`, `ERR_ABORTED`. Ein Abruf auf dem **eigenen** Server,
der mit einem dieser drei scheiterte, wurde deshalb als Kartenrauschen
weggeworfen. Am alten Muster nachgerechnet: **3 von 5** gebauten Fällen mit
lokaler Fundstelle wurden verschluckt, zwei gezählt
(`ERR_CONNECTION_REFUSED`, HTTP 500). Der Bericht konnte „0 Konsolenfehler"
melden für eine Seite, auf der das Stylesheet nicht angekommen ist.

**Zuerst nach K4 nicht behoben, dann auf Anweisung doch** — und die Begründung
hat sich dabei gedreht: Ich hatte argumentiert, der Punkt ändere das
Messmittel, mit dem diese Runde ihre Zahlen belegt, und gehöre deshalb in die
nächste Runde. Der Auftraggeber hat genau daraus den Gegenschluss gezogen:
**Weil** das Messmittel die Zahlen dieser Stufe trägt, gehört es in diese
Stufe. Das ist das stärkere Argument — eine Zahl, die mit einem ungenauen
Mittel gemessen wurde, wird nicht dadurch belastbar, dass das Mittel eine
Nummer im Backlog hat. Umgesetzt als **AP11** (Abschnitt 8), belegt in drei
Richtungen; die Zahl der Runde hält.

**F-BR3-04 · Der Änderungsverlauf des Rahmenplans führt sechs Fassungsnummern
doppelt.** (AP10, 13.09.2026 — jetzt **Backlog Nr. 177**.) Abschnitt 10 trägt
zwischen der Zeile „30" und der Zeile „47" sechs Zeilen mit den Nummern **35,
36, 37, 39, 38, 37**; dieselben Nummern trägt der Block darunter ein zweites
Mal, mit anderem Inhalt. Ein Verweis auf „Fassung 38" ist damit nicht
auflösbar. Ursache ist dieselbe wie bei den Fassungen 39, 41 und 42:
verschiedene Sitzungen am selben Tag im selben Dokument. **Nicht behoben,**
weil das Umnummerieren historischer Zeilen eine Festlegung ist und keine
Korrektur — die Entscheidung (eigene Folge oder „Zwischenstände ohne
Fassung") gehört dem Auftraggeber.

**F-BR3-01 · Kein Fund am Code, sondern am Vorgehen: Ein Backlog-Punkt
auszutragen ist gefährlicher, als es aussieht.** (AP3, 13.09.2026.) Das
Skript, mit dem ich AP1 und AP2 ausgetragen habe, suchte das Ende eines
Eintrags an der **nächsten Nummer** — „Nr. 117 endet, wo Nr. 118 anfängt". Das
ist falsch, sobald der Nachfolger schon unter *Erledigt* steht: Nr. 118 liegt
dort seit Runde 2, das Blockende landete rund 300 Zeilen zu tief, und die
Löschung hätte den halben Erledigt-Teil mitgenommen. Aufgefallen ist es nur,
weil danach ein Lookup ins Leere lief und das Schreiben deshalb ausblieb —
nicht weil eine Prüfung es gemeldet hätte.

AP1 und AP2 sind nachgemessen und unbeschädigt (Nr. 92 und Nr. 95 stehen noch
in *Offen*, deshalb traf es dort zu): gegen `dabd7a3` **keine Nummer
verloren**, keine Duplikate, 171 Einträge. Das Werkzeug sucht jetzt den
nächsten Eintrag **innerhalb des Offen-Teils**, welche Nummer er auch trägt.

**Was daraus für die Runde folgt:** Nach jedem Austragen wird nicht nur die
Selbstprüfzahl gerechnet, sondern die **Nummernmenge gegen `dabd7a3`
verglichen** (verloren / doppelt / dazugekommen). Die Selbstprüfzahl allein
hätte diesen Fehler **nicht** gefunden: Sie zählt die offenen Punkte, und die
wären ja richtig gewesen — verschwunden wären erledigte. Das ist dieselbe
Lehre wie bei Backlog Nr. 170: Eine Zahl belegt nicht, dass nichts fehlt.
**Für den Auftraggeber:** kein Handlungsbedarf, nichts ist kaputt; die Zeile
steht hier, weil die Runde noch vier Punkte austrägt (47, 58, 173, 174) und
weil die nächste Instanz dieselbe Falle findet.

---

## 8. Stand der Abarbeitung

Wird von der umsetzenden Instanz nach jedem Paket fortgeschrieben.

| AP | Punkt | Stand | Probleme / wie gelöst |
|---|---|---|---|
| AP1 | 91 | **erledigt** 13.09.2026 | Zwei Dinge kamen dazu, die das Konzept nicht vorsah. **Erstens:** Der Befund sagte „BACK räumt den Dialog weg, ohne `onResponse` zu rufen" — beim Lesen von `watch/source/PairView.mc` zeigt sich die Folge, die daraus erst den Nutzen macht: `KoppelnDelegate` hat **nur** `onResponse`, also läuft das dort stehende `Pair.ablehnen(...)` bei BACK gar nicht. BACK ist damit **kein Ersatz für „Nein"**; es sind zwei Prüffälle, nicht einer. Das steht jetzt so in der LIESMICH. **Zweitens:** Die Zeile zu Nr. 91 in Rahmenplan Abschnitt 5 nannte als Fundstelle `tools/uhr-bilder/` — das ist das Werkzeug von Nr. 94. Sie hätte die nächste Instanz in die falsche Datei geschickt; mit dem Austragen ist sie weg. Abnahme erfüllt: `grep -c Confirmation` = **3** (war 0), alle vier Aussagen belegt, Selbstprüfzahl **58 = 58** |
| AP2 | 94 | **erledigt** 13.09.2026 | **Drei Dinge, die das Konzept nicht hatte.** (1) **Ein Prüfmittel hing an dem Wort:** `tools/s5-anker/anker.py` verankerte die Stelle wörtlich an `sie BITGLEICH \(geprueft` und hätte nach der Änderung „NICHT GEFUNDEN" gemeldet. Gelöst, indem der Anker auf den Teil des Satzes umgestellt wurde, den die Streitfrage nicht berührt (`geprueft mit \`compare -metric AE\``) und von `uhrbilder.bitgleich` in `uhrbilder.wortlaut` umbenannt — so hält er auch die nächste Umformulierung aus. Gemessen: Anker „unveraendert", nicht gefundene Anker unverändert 7. (2) **Die Abnahme war unerfüllbar** — siehe E-BR3-15. (3) **Der Backlog-Eintrag war ungenau:** Die Selbstwiderlegung lag nicht zwischen `erzeugen.sh` und der LIESMICH, sondern **innerhalb der LIESMICH** (an `HEAD` gemessen Zeile 27 gegen 35, acht Zeilen). Beim Austragen berichtigt. **Eigener Fehler, korrigiert:** Ich hatte zuerst einen sieben Zeilen langen Begründungsblock in `erzeugen.sh` geschrieben — das Konzept sagt dort „ein Wort" und verortet die Begründung in der LIESMICH; der Block ist auf Wort plus Verweis gekürzt, sonst stünde dieselbe Erklärung zweimal. **Nicht geprüft:** `erzeugen.sh` ist nicht gelaufen (kein ImageMagick, kein `rsvg-convert` im Container); reiner Kommentar, `bash -n` trägt. Selbstprüfzahl **57 = 57** |
| AP3 | 117 | **erledigt** 13.09.2026 | **Der Auftrag enthielt eine falsche Zahl.** Das Konzept schrieb „Alle **vier** messen Konto-Backups der Verwaltung". Die vier Zahlen über der Liste sind aber **Konten, Admins, Konto-Backup überfällig, nie Konto-Backup** — nur die letzten zwei haben mit Backups zu tun. Die Vier des Backlog-Eintrags meinte etwas anderes: zwei Kennzahlen plus Filter plus Erinnerungsmail. Wörtlich umgesetzt hätte das Handbuch etwas Falsches behauptet. Der Absatz sagt jetzt „zwei der vier Zahlen" und zählt die vier Stellen einzeln auf. **Zwei Absätze statt einem**, weil zwei verschiedene Aussagen zu machen sind (was gemessen wird / was nicht) und der zweite die sichtbare Folge braucht: Ein Konto mit zuverlässigen eigenen Backups steht genauso unter „nie Konto-Backup" wie eines ohne. **Am Code gegengeprüft:** `users` 16 Spalten ohne Backup-Zeitpunkt, `edbak_konto_stand()` liest nur den Kontoordner, `edbak_faellige_konten()` filtert auf dieselben Stände. Selbstprüfzahl **56 = 56** |
| AP4 | 67 (Unterpunkt) | **erledigt** 13.09.2026, Web **19.3.1** | **Die Vormessung war richtig und nötig:** `unlock.js:200` schickt `X-CSRF`, und es ist der einzige Aufrufer — kein Haltepunkt. Zusätzlich am Code belegt, dass die linke Seite von `hash_equals()` hier nie leer ist (`auth_guard.php:24` ruft `csrf_token()` bei jeder angemeldeten Anfrage), sonst hätte der leer/leer-Fall den Tausch wertlos gemacht. **Drei Dinge, die Arbeit machten.** (1) Der Tausch trennte den Demo-Kommentar von seiner Zeile — er stand plötzlich über der CSRF-Prüfung und las sich, als erklärte er sie; er ist mitgezogen. (2) **Der Container hatte keinen Datenbankserver.** MariaDB ist nachinstalliert, danach lief `lokal_einrichten.sh` durch. Dabei paniert einmal das Python-`cryptography` (Rust-Bindung für 3.12 gebaut, `python3` ist 3.11.15); nach dem apt-Lauf war es über drei Läufe stabil — Ursache nicht abschließend isoliert. (3) **Mein erster A/B-Vergleich war verfälscht:** ohne Wartezeit nach `git stash pop` lieferte der laufende `php -S` noch den alten Stand (reproduzierbar: 0 s → 200, 4 s → 403). Nicht opcache — `opcache.enable_cli` ist Off. **Regel für AP5:** nach einer Serveränderung erst messen, wenn der Server neu gestartet ist. **Abnahme erfüllt** (Zahlen im Prüfdokument). `Technik.md` nannte die Reihenfolge nicht — geprüft wie verlangt; ein Halbsatz ist dort trotzdem ergänzt, damit „stiller Erfolg" nicht als unbedingte Zusage gelesen wird |
| AP5 | 41 (Streichungen) | **erledigt** 13.09.2026, Web **19.3.1** | **Eine Abnahmezahl des Konzepts war zu niedrig.** AP10 (1) sagte „die Gesamtzahl der Befunde sinkt um die drei `[offen]`", also 334 → 331. Gemessen sind es **330**: `rea-kopf` stand als einzige der drei im alten Stylesheet und war deshalb **zusätzlich** ein Befund „ohne Gegenstück" — der fällt mit dem Streichlisten-Eintrag weg (53 → 52). Vorher am Prüfmittel nachgelesen, nicht hinterher erklärt. **Der Bildvergleich brauchte zwei Anläufe.** Der erste meldete **3079** abweichende Bildpunkte auf **allen vier** Seiten — dieselbe Zahl auf verschiedenen Seiten kann nicht von Klassen kommen, die nur auf manchen stehen. Der Rahmen aller Abweichungen (y 117–126, x 808–1204) zeigte die Ursache: der Demo-Hinweis zählt bis zum nächsten Reset herunter („in ca. 10 Minuten" gegen „in ca. 8"). Mit geschwärztem Zähler **0 auf allen vier**. Die Bounding-Box ist dabei der stärkere Beleg als die Maske: **alle** abweichenden Punkte lagen in diesem Streifen, es gibt also anderswo keinen. **Beim Austragen berichtigt:** `ohne-regel.md` nannte für `phasen-name` `einsatz.php:631`, es sind 764 und 805. **Zwei der drei standen nie im alten Stylesheet** — sie stehen trotzdem auf der Streichliste, damit die Prüfung ihre Rückkehr meldet; die Liste führt schon 10 von 135 solcher Einträge, das ist etablierte Praxis und keine Neuerung |
| AP6 | 47 | **erledigt** 13.09.2026 | **Der Kommentar-Abtaster war die eigentliche Arbeit, nicht die Prüfung.** Ein regulärer Ausdruck hätte nicht getragen: `//` steht in jeder URL, `#` in jeder Farbe, `/*` in mancher Zeichenkette — zu grob streicht er Code weg, zu fein lässt er Kommentare stehen, und beides ist lautlos falsch. Der Abtaster geht zeichenweise und merkt sich den Zeichenketten-Zustand; die Umbrüche bleiben, sonst zeigte jede Fundstelle daneben. Vorher nachgesehen, was er **nicht** können muss: keine PHP-Attribute `#[...]`, keine JS-Privatfelder, kein Heredoc im eigenen Code (alle Treffer liegen unter `server/vendor/`, das ist ausgenommen) — steht als Grenze an seinem Kopf. **Zwei Konstruktionsfehler in meinem ersten Entwurf, beide vor dem Lauf behoben:** Die Kopfzeile der neuen Liste wäre von `liste_lesen()` als Datenzeile gelesen worden (die Funktion überspringt nur bestimmte Spaltennamen — „Prüfung" ist jetzt dabei), und ich hatte die Prüfung über ein `name + ':'`-Präfix in der ersten Spalte erkannt statt über eine eigene Spalte; jetzt vier Spalten, die erste sagt die Prüfung. **Eine Gegenprobe war zuerst wertlos:** „Rückgabewert ≠ 0" beweist nichts, wenn ohnehin 330 Befunde stehen — die Probe zählt jetzt die Gruppe selbst (0 → 1 Befund, Gesamtzahl 330 → 331). Alle Zahlen im Prüfdokument |
| AP7 | 58 | **erledigt** 13.09.2026 | **H-BR3-1 ist ausgelöst worden — aber nicht dort, wo das Konzept ihn erwartet hat.** Die sieben Ausnahmen sind alle echt; `pw_handling.php` und `session_lib.php`, die beiden zu prüfenden, halten stand (die eine sagt im Dateikopf „keine Sitzung", die andere rendert **nach** `session_destroy()`). Der Fund kam aus der **Gegenrichtung**, die das Konzept nur als Hinweis vorgesehen hatte: `apk.php` ruft Gerüst und `ui_seite_ende()`, aber nie `ui_seite_start()` — seine 404-Seite ging ohne Doctype, Titel und Stylesheet hinaus, Quirks-Modus, Times New Roman. Am Prüfstand belegt (HTML beginnt mit `<header>`, `compatMode` BackCompat) und mit Bild. Nach Rückfrage behoben, **nicht** auf die Liste gesetzt; der Hinweis steht seither auf **0** statt auf 1. **Zwei Zahlen des Konzepts stimmten nicht ganz:** `ui_seite_start(` rufen **36** Dateien, nicht 37 (das Konzept zählte mit Kommentaren — `version.php` nennt es in einem), und der erwartete Hinweis „1" ist nach der Behebung 0. **Ein stiller Fehler beim Bauen:** Beim ersten Lauf griff keine der sieben Ausnahmen, weil `pruefen.py` ASCII beschriftet („Geruest") und die Liste Markdown („Gerüst") — sieben Befunde, alle erklärt, ohne dass irgendwo „Vergleich fehlgeschlagen" stand. Der Vergleich löst Umlaute jetzt auf |
| AP8 | 173 | **erledigt** 13.09.2026 | Ohne Überraschung — die einzige der zehn Zeilen, die genau so lief, wie das Konzept sie beschrieben hat. Vorher gemessen: **3 und 2** ungenutzte Regeln, die erwarteten Zahlen; die Namen holt `bericht.json` des jeweiligen Laufs (`ungenutzte_regeln`), nicht das Auge. Alle fünf beschrieben den S9/AP7-Übergang. Danach: edbak **287 687 / 0 / 16 / 0**, csv **9 120 / 0 / 1 021 / 0** — die Zeile „ungenutzte Regeln" druckt das Werkzeug nur noch, wenn es welche gibt. **Vorläufig**, wie das Konzept sagt: AP9 ersetzt die Referenz, AP10 lässt beide erneut laufen |
| AP9 | 174 | **erledigt** 13.09.2026 | **H-BR3-2 hat genau das getan, wofür er da ist — und meine erste Diagnose war trotzdem falsch.** Die Vormessung ergab 0 statt 2 ohne Standort; ich habe angehalten und gemeldet. **Meine Diagnose lautete: die Anwendung ist kaputt** — Web 17.0.0 habe beim Dialog-Umbau den Haken „ohne Standort" und die Bedingung im Speicherweg entfernt. Der Auftraggeber hat den Weg „wiederherstellen" freigegeben; beim Einbauen zeigte der Kommentar in `stammdaten_ui.php:262`, dass der Haken **absichtlich** durch den ersten Eintrag der Auswahlliste ersetzt wurde (Web 16.3.0, mit Begründung). Die Anwendung war nie kaputt. **Serveränderung zurückgenommen**, der Fehler saß allein in `einspielen.py`: Es schickte das seit 16.3.0 tote Feld `ohne_standort=1` **neben** einer echten Kennung; jetzt `base_id=0`. **Was ich daraus mitnehme:** Ich hatte die Historie gelesen (16.0.0 führt ein, 17.0.0 entfernt) und daraus geschlossen, statt zuerst zu prüfen, wie die Sache **heute** gebaut ist. Der Kommentar, der alles erklärte, stand 100 Zeilen über der Stelle, die ich gelesen hatte. **Ein zweiter Stolperstein:** `lauf.json` hält den Zustand der Stufen; nach dem Neuaufsetzen der Datenbank hielten sie ihre Arbeit für erledigt (`ingest` sendete 0 Anfragen), und die Kette brach ab. Vor einem Neuaufbau muss er weg. Alle Abnahmezahlen erfüllt, Selbstprüfzahl **52 = 52** — die Zahl, die AP10 vorhersagt |
| AP10 | Abschluss | **erledigt** 13.09.2026 | **Der Bilderlauf lief über alles, nicht über zwei Seiten (E-BR3-16).** Das Konzept verlangte ihn „auf den zwei berührten Seiten" — der Vergleich vor/nach war in AP5 aber schon geführt (0 abweichende Bildpunkte auf vier Seiten). Was in AP10 fehlte, war die andere Frage: Hat die Runde irgendwo sonst etwas kaputtgemacht? Die beantwortet nur der ganze Lauf: **45 Seiten × 8 Breiten = 360 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knopfhöhen.** **Eine Zahl war zuerst nicht zu erklären und ist es jetzt teilweise:** Ein früherer Lauf hatte 15 Konsolenfehler gemeldet und Abrufe, die auf dem **eigenen** Server scheiterten. Nachgemessen mit einem eigenen Rundlauf: **14 gescheiterte Abrufe, alle `ERR_ABORTED`, alle in der ersten Ladung nach der Anmeldung, 0 in den folgenden, 0 Konsolenmeldungen daraus** — die nächste Navigation räumt das noch ladende Dokument ab, ein Messartefakt und kein Serverfehler (jede Datei einzeln HTTP 200, 20 gleichzeitige Anfragen je Port 20 von 20). Die **15** selbst sind nicht mehr messbar: `aufnehmen.mjs` löscht `ausgabe/` bei jedem Start, der alte Bericht ist weg. Steht so im Prüfdokument, statt als „aufgeklärt" zu gelten. **Dabei ein echter Fund, nicht behoben (K4):** Der Rauschfilter des Bilderlaufs prüft drei Fehlercodes gegen den Meldungstext, ohne die Fundstelle anzusehen — **3 von 5** gebauten Fällen mit lokaler Fundstelle werden verschluckt. Das ist **Nr. 176** — **in AP11 behoben**, siehe die Zeile darunter; bis dahin hieß „0 Konsolenfehler" genau: 0 außerhalb dieser drei Arten. **Ein zweiter Fund in der Buchführung:** Der Änderungsverlauf des Rahmenplans führt **sechs** Fassungsnummern doppelt (35, 36, 37, 39, 38, 37) — **Nr. 177**, nicht mitbehoben, weil Umnummerieren historischer Zeilen eine Festlegung ist. **Eine Gegenprobe hat sich gelohnt:** 360 Bilder, aber nur **356 verschiedene**. Die vier Doppel sind erklärt — `11-tagesuebersicht-schublade` ist ab 1024 px Bild für Bild `10-tagesuebersicht`, weil `.nur-schublade` dort auf `display:none` steht. Ohne die Probe wäre „360 Bilder" eine Zahl ohne Aussage geblieben (F-P3-AQ). **Und eine Annahme aus AP4 ist berichtigt:** `api/kdf_upgrade.php` ist vom Browser auf diesem Stand **gar nicht** erreichbar — `unlock.js:184` ruft nur bei abweichender Rundenzahl, `KDF_ITER_LISTE` hat seit Nr. 155 einen Eintrag, und alle 4 Konten stehen auf 600 000 (SQL nachgezählt). Prüflistenpunkt 1 sagt das jetzt und bleibt trotzdem stehen: Beim nächsten Anheben des Zielwerts wird der Weg wieder scharf |
| AP11 | 176 (Nachtrag) | **erledigt** 13.09.2026 | **Nicht im Konzept vorgesehen** — der Auftraggeber hat den Fund aus AP10 zur Behebung angewiesen, mit dem Argument, das den meinen umdreht: Weil `istRauschen()` das Messmittel aller Bildzahlen dieser Stufe ist, gehört es in diese Stufe und nicht in die nächste Runde. **Die Umsetzung war klein, die Beweisführung nicht.** Drei Klassen statt einer Musterzeile, Herkunftsvergleich über `URL.origin` statt `startsWith` (verträgt einen Schrägstrich in `--basis`, rechnet Vorgabeports mit). Zwei Randfälle bewusst entschieden und im Code begründet: eine Meldung **ohne** Fundstelle wird **gezählt** (nicht zuordenbar → lieber eine Zeile zu viel als eine stille Lücke), und der Fehlercode wird nur noch im Wortlaut gesucht, weil er in keiner URL vorkommt. **Belegt in drei Richtungen, weil eine nicht gereicht hätte:** Eine neue Selbstprobe (`--selbstprobe`, zehn gebaute Fälle mit Sollwert) meldet **10 von 10** — das zeigt aber nur, dass der neue Code tut, was ich erwarte. Deshalb zweitens dieselben zehn Fälle durch die **alte** Funktion, **wörtlich aus `git show origin/main` geholt statt abgeschrieben**: **6 von 10**, falsch sind die drei lokalen Abbrüche und der Fall ohne Fundstelle. Und drittens, weil beides gebaute Fälle sind: am **laufenden Browser** — Seite geladen, **PHP-Server angehalten** (socat blieb, die Basis also dieselbe), dann Symbol und API-Aufruf nachgeladen. Chromium meldete zwei Fehler auf der eigenen Basis (`ERR_EMPTY_RESPONSE` für das Symbol, `ERR_CONNECTION_RESET` für `api/day.php`); der alte Filter verwarf **einen**, der neue **keinen**. **Ein Nebenbefund daraus:** Der Symbolabruf scheiterte mit `ERR_EMPTY_RESPONSE`, und der Code stand nie im Muster — der Fehler war also nur für einen Teil der Abbrüche unsichtbar, nicht für alle. **Die Zahl der Runde hält:** Abschlusslauf mit der neuen Regel unverändert 45 Seiten, 360 Bilder, 0/0/0. Dazu berichtigt: Die LIESMICH behauptete seit P3, gefiltert werde „über die Fundstelle, nicht über den Wortlaut" — für die drei Codes war das unwahr. **Und der Prüfstand war zwischendurch weg:** php und socat waren zwischen zwei Läufen verschwunden (0 Prozesse, keine Ports); `lokal_starten.sh` hat ihn zweimal wieder hochgefahren, einmal nach dem absichtlichen Anhalten. Wer eine Messung wiederholt, prüft vorher, ob der Stand noch steht. **Und zuletzt die Frage, die sich nach jeder Behebung stellt: tragen die Geschwister denselben Fehler?** Drei Browserwerkzeuge durchgesehen — `papierkorb_misch.mjs` prüft Text **und** Fundstelle und nennt den Bilderlauf als Vorbild (der war also das schwächere von beiden, jetzt umgekehrt), `messstand/browserprobe.mjs` hängt an `requestfailed` und hat die Adresse immer dabei, **`kopplungsprobe/rundlauf.mjs` aber verwirft mit „Failed to load resource" jede Ressourcenmeldung** — gemessen 4 von 8 gebauten Fällen falsch, darunter ein 404 und ein 500 auf der eigenen Basis. Das ist **Nr. 178** und F-BR3-05, nach K4 nicht mitbehoben. **Und der Nachtrag ist gegengeprüft worden — vier Blickwinkel, fünf eigene Fehler, keinen davon von einem Prüfmittel gefunden.** Der schwerste ist F-BR3-06: **Die Selbstprobe war blind.** Sie meldete 10 von 10 auch bei gelöschter Klasse 1 oder 3, weil jeder verwerfende Fall einen Kachelgastgeber in der URL trug — sie bestätigte sich selbst. Jetzt fünfzehn Fälle, jede Klasse trägt einen, und eine Mutationsprobe hält es fest (sechs Läufe, jedes Mal 14 von 15). Dazu: Klasse 2 verwarf Verbindungsabbrüche auf der Seitenadresse selbst (jetzt nur Statuscodes); eine unlesbare Fundstelle fiel zur stillen Seite, während die leere gezählt wurde (jetzt `herkunft()` mit drei Antworten); zwei Buchführungszahlen waren nicht nachgemessen (53 statt 55, 173 statt 175 — der Fehler aus `CLAUDE.md` 6, den diese Runde schon einmal beschrieben hat); und zwei Sätze waren zu weit gefasst. **Beim Nachfahren der Kopplungsprobe fiel Nr. 180 auf** (F-BR3-07): Sie verlangt 44 px, seit Web 15.5.0 gelten zwei Sollwerte — rot seit dem 06.09.2026, gefahren erst heute |
| AP12 | 178, 179, 180 | **erledigt** 13.09.2026 | **Auch nicht im Konzept — der Auftraggeber hat „alles drei kurz abarbeiten" angewiesen.** Reihenfolge nach Abhängigkeit, nicht nach Nummer: erst **180**, weil die Kopplungsprobe grün sein muss, bevor sie als Regressionsmaß für **178** taugt. **180** ist eine Zeile Rechnung (`(!FINGER && BREITE >= 1024) ? 36 : 44`) und zwei Schalter, aber mit einer Falle: Die Eingabeart fällt nach dem ersten Vollseiten-Abzug zurück (S8/AP7), und dieser Rundlauf macht mehrere — sie wird vor der Messung erneut gesendet, und nur im Fingerlauf. Gemessen 25/0 in beiden Bedienhöhen. **178** ist die Trennung in drei Kanäle; hier hat die Lehre aus AP11 unmittelbar gewirkt: Die erste Fassung der Selbstprobe hatte elf Fälle, und die **Mutationsprobe zeigte zwei grüne Mutationen** — beide Zweige von `herkunft()` blieben ungedeckt, weil der Fall mit leerer Fundstelle schon an der ersten Zeile herauskommt. Fall 12 und 13 dazu, dann sechs Mutationen mit je 12 von 13. **Diesmal vor dem Melden gemessen, nicht nach dem Melden korrigiert.** Dazu die Abnahme am laufenden Stand mit einer Wegwerfdatei (HTTP 500): erscheint; Kachel im selben Lauf: erscheint nicht; Rundlauf 25/0; Datei gelöscht, `git status` sauber. **179 war der einzige Punkt mit einer echten Entwurfsfrage.** Der naheliegende Ausdruck — nur die Ladekonstrukte — wurde gebaut und meldete **0 Treffer**, während fünf echte Laufzeitquellen im Code standen; die Kacheln gehen über `L.tileLayer(...)`, der Adressdienst ist eine PHP-Konstante. **Ein Prüfmittel, das seine eigene Sache nicht findet, ist schlimmer als keines** — deshalb meldet es jede absolute Adresse, und die Arbeit liegt in der Ausnahmeliste: 15 Einträge mit Art und Grund (vier Kachelserver, ein Rückfall, der Adressdienst als Vorgabe, sechs Navigationsziele, ein XML-Namensraum, zwei Beispieltexte). Zwei Kleinigkeiten beim Bauen: Das Muster übersah zuerst `{s}.tile.opentopomap.org` (Platzhalter im Gastgebernamen), und ein Ausnahme-Muster mit Pfad (`openmaps.fr/donate`) griff nicht, weil der Treffer beim Gastgeber endet — jetzt `//openmaps.fr`, damit es nicht auch auf `tile.openmaps.fr` passt. **Offen geblieben ist die Laufzeitseite**: keine CSP (Nr. 181), ausdrücklich als Festlegung ausgewiesen und nicht nebenbei gemacht |

---

## 9. Quellen

- `docs/konzepte/Backlog-Durchsicht-2026-09-12.md` — die sechzehn
  Entscheidungen (6, 7, 16 und die Zuspitzungen zu 94, 47, 58, 173, 174).
- `docs/Backlog.md`, Stand 13.09.2026 — Einträge 41, 47, 58, 67, 91, 94,
  117, 173, 174 mit den nachgemessenen Zahlen.
- `docs/Rahmenplan.md` Fassung 46 — Schritt 9 (Backlog-Runde), 9c
  (Mockup-Runde), Abschnitt 5, K1–K9, R24.
- `tools/vollstaendigkeit/LIESMICH.md` und `pruefen.py` — Bauart des
  Prüfmittels, das die Gruppe 5 bekommt.
- `tools/referenzdatensatz/LIESMICH.md` — die drei Läufe.
- `docs/konzepte/Pruefdokument-Backlog-Runde-2.md` — Muster und Vorzahlen
  für die maschinellen Prüfungen.
