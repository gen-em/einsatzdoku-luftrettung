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
| **In Arbeit** | **AP5** — Nr. 41, drei Klassen aus dem Markup streichen |
| **Erledigt** | **AP1** (Nr. 91), **AP2** (Nr. 94), **AP3** (Nr. 117), **AP4** (Nr. 67 Unterpunkt) — Block A zu vier Fünfteln |
| **Offen** | AP5 bis AP10 |
| **Prüfstand** | **steht seit AP4**: MariaDB 10.11.14 (nachinstalliert), PHP 8.4.19, `socat`-TLS auf 8443, volle Installation mit Demo-Konto (88 Einsätze, 16 Diensttage, 2 Geräte), Chromium über Playwright. Aufbau: `lokal_starten.sh` + `lokal_einrichten.sh` |
| **Stufe der Runde** | **Web 19.3.1 — Korrektur** (E-BR3-14), festgelegt vor AP1; `version.php` wird mit AP4 hochgestuft, dem ersten Paket, das `server/` anfasst |
| **Hakt es?** | Nein. Drei Abweichungen vom Konzept sind entschieden und begründet: E-BR3-13 (Abschnitt-5-Zeilen wandern je Paket, nicht gesammelt in AP10), E-BR3-14 (Stufe) und E-BR3-15 (die Abnahme von AP2 war so nicht erfüllbar) |
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

Leer bis zur Umsetzung. Die Zahlen gehören ins Prüfdokument (K9); hier nur
der Verweis und, je AP, ob die Abnahme erfüllt ist (Abschnitt 8).

---

## 7. Fehlerfunde (K4)

Was beim Bauen auffällt und nicht zu dieser Runde gehört, steht hier mit
Datei, Symptom und Vermutung — und wird **nicht** nebenbei behoben. Am Ende
entscheidet der Auftraggeber, was davon ein Backlog-Punkt wird.

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
| AP5 | 41 (Streichungen) | offen | |
| AP6 | 47 | offen | |
| AP7 | 58 | offen | |
| AP8 | 173 | offen | |
| AP9 | 174 | offen | |
| AP10 | Abschluss | offen | |

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
