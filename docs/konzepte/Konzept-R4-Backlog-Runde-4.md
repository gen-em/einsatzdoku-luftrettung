# Konzept R4 — Backlog-Runde 4 (Schritt 17)

**Kürzel:** `R4`. Arbeitspakete `R4-01 …`, Entscheidungen `E-R4-NN`, Befunde
`F-R4-NN`, Fragen an die Betreiberin `Q-R4-NN`, Haltepunkte `H-R4-NN`,
Prüfpunkte `P-R4-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`R4-02: …`). Benennung nach `docs/Pruefablauf.md` 7; Runde 3 hieß
`BR3`, und `BR` ist seit dem 24.09.2026 der Bestandsriegel — daher nicht
`BR4`.
**Rahmenplan:** Schritt **17** (Fahrplan: „Die kleinen Punkte seit Runde 3 —
Prüfmittel, Doku-Konsistenz, Streichlisten, `days.created_at`,
Demo-Reset-Takt, Statistik-Rest (Nr. 122)"; Voraussetzung „Merge von SD" —
erfüllt 26.09.2026, PR #92). Vorgriff: **BV** (gemergt 26.09.2026, PR #87,
Abschluss auf diesem Zweig). Nach 17 folgt 18 (Sicherheitsrunde II,
Voraussetzung „Merge von 17"), dann 12 (P6-Review).
**Herkunft:** Konzeptsitzung am 26.09.2026 (Fable, R14). Die Liste der
Punkte liefert `python3 tools/steuerung/uebersicht.py --ziel 17` (Konzept
SD, E-SD-38): **55 Einträge** am 26.09.2026 — 54 aus SD-02 und Nr. 339 aus
dem Abschluss von BV (E-BV-19).
**Modell:** Konzept Fable (R14); Umsetzung **Opus** (K2), **kein
Fable-Schritt** (Fahrplanzeile 17).
**Fächerung (`CLAUDE.md` 7):** je Paket in Abschnitt 4 („Fächerung:"); die
Konzeptsitzung hat den Befund auf Agenten gefächert (2.1).
**Versionsstufe:** legt die Umsetzung fest (K3). Pakete, die `server/`
anfassen, stufen Web (Korrektur oder Neben, je Paket in 4 vermerkt);
Pakete an `android/` stufen Android; Pakete nur an `tools/` und `docs/`
keine Zählung (`CLAUDE.md` 2). **Eine Migration** (R4-15) — nach dem Deploy
`update.php`.
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-R4-Backlog-Runde-4.md`
daneben (angelegt mit dem Konzept, gefüllt von der Umsetzung); **Mockups**
in `konzept-r4/mockups/` (M-R4-22, -23, -24 mit LIESMICH und Bildern).
**Zweig:** Konzept auf `claude/schritt-17-hl9egt` (von `origin/main`
`056781c`, gemergt mit PR #93); **Umsetzung auf
`claude/schritt-17-konzept-mockups-q0yjcm`**, von `origin/main` `05dfc12`
(E-R4-02, E-R4-14).
**Backlog-Spanne:** **340 bis 349** (E-SD-21, eingetragen und gepusht mit
`17-00` vor jeder Vergabe). 339 hat der Abschluss von BV vergeben
(Q-BV-05, E-BV-19). Vergeben aus der Spanne: siehe Statusblock.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **26.09.2026 — Umsetzung läuft** auf `claude/schritt-17-konzept-mockups-q0yjcm` (Opus). Konzept mit PR #93 gemergt und von der Betreiberin freigegeben, samt aller Fragen und der drei Mockups (E-R4-14 bis -26). **Als Nächstes: R4-03.** |
> | Entschieden | **E-R4-01 bis E-R4-28** (Abschnitt 3.1). Von der Betreiberin: E-R4-01 bis -05, -07 bis -10 (Konzeptsitzung), **E-R4-14 bis -26** (Umsetzungsbeginn, 26.09.2026). Aus dem Konzept: E-R4-06, -11, -12, -13; aus der Umsetzung: E-R4-27, -28. |
> | Offen | **keine Frage.** Zuarbeit der Betreiberin: die zwei toten Zweige löschen (E-R4-15, P-R4-06). |
> | Umsetzung | **R4-01, R4-02 erledigt** (26.09.2026). Offen: R4-03 bis R4-26 in Nummernfolge, R4-19 vor R4-16 (E-R4-27). |
> | Fable-Schritte | keine. |
> | Fächerung | Konzept: zwei Workflows mit je drei Sichtern, drei Gegenprüfern, einer mit dem Umfeld-Agenten — nur lesend (2.1). Umsetzung: nur R4-11 und R4-19 (E-R4-14); bisher keine. |
> | Nummern | 340 bis 349 reserviert; vergeben: **340** (R4-01, F-SD-08). 341 wird nicht gebraucht (E-R4-25). |
> | Befunde der Umsetzung | **F-R4-19** (Abnahmezahl R4-01 verzählt: 43, nicht 37), **F-R4-20** (Firefox startet ohne die vier Bibliotheken, WebKit nicht), **F-R4-21** (örtliche MariaDB ohne Zeitzonentabellen), **F-R4-22** (Prüfstand hielt `${ANDROID_HOME:-…}` für Pflicht — behoben in R4-02) — 2.4. |

---|---|
> | Stand | **26.09.2026 — Konzept vollständig, zur Freigabe vorgelegt; Konzept-PR #93 offen** (E-R4-02: Abschlüsse SD, AR, BV und dieses Konzept; jeder Push trägt einen Prüfbericht, `Pruefablauf.md` 5). Befund: 55 Punkte gesichtet und gegengeprüft (2.1). Paketschnitt: 26 Pakete (4). **Mockups für R4-22, R4-23, R4-24 liegen bei** (`konzept-r4/mockups/`, LIESMICH dort; zur Freigabe, Q-R4-16). |
> | Entschieden | **E-R4-01 bis E-R4-13** (Abschnitt 3). Von der Betreiberin am 26.09.2026: E-R4-01 bis -05 (Sitzung), E-R4-07 bis -10 (Q-R4-02, -04, -05, -10). Aus dem Konzept: E-R4-06, -11, -12, -13. |
> | Offen | **Q-R4-01, -03, -06 bis -09, -11 bis -15** — jede mit Empfehlung (3.2); nach K6 spätestens vor dem Paket, das sie braucht. **Q-R4-16** — die Freigabe der drei Mockups (H-R4-01). **Die Freigabe des Konzepts.** |
> | Umsetzung | noch nicht begonnen. **R4-00 erledigt** (Konzept, Spanne, Abschlüsse von SD, AR und BV auf diesem Zweig — Rahmenplan Fassungen 131 bis 134). |
> | Fable-Schritte | keine. |
> | Fächerung | Konzept: zwei Workflows mit je drei Sichtern, drei Gegenprüfern, einer mit dem Umfeld-Agenten — nur lesend (2.1). Umsetzung: je Paket in 4. |
> | Nummern | 340 bis 349 reserviert; vergeben: **340** (R4-01, F-SD-08), **341** (R4-06, Q-R4-15 — nur, wenn die Betreiberin sie will). |

---

## 1. Auftrag

**Ziel:** Die offenen Backlog-Punkte mit `gehört zu: 17` abarbeiten oder
begründet an ihren richtigen Ort hängen — so, dass nach dem Merge
**kein Punkt mehr `17` trägt**: erledigt, ausgetragen, `nächste
Backlog-Runde`, ein anderer Schritt, `nach v1.0`, `Pflegeaufgabe` oder
`nicht umsetzen` mit Begründung. Eine Backlog-Runde ist eine Aufräumrunde;
die drei Pakete mit neuer Darstellung (R4-22 bis R4-24) sind die Ausnahme,
die die Betreiberin entschieden hat (E-R4-07, E-R4-09); ihre Mockups liegen
beim Konzept (`konzept-r4/mockups/`) und warten auf die Freigabe
(Q-R4-16, `CLAUDE.md` 5).

**Nicht Ziel:** alles, was Schritt 18 gehört (Sitzung, Zweitfaktor,
Serverschlüssel, `ingest.php`-Deadlock — Nr. 242, 247, 249, 233, 210, 228),
der P6-Review (12), Änderungen an der Auslieferungskette (PK-06 bis
PK-08), eine Uhr-Auslieferung (die Uhr bleibt bei 3.1.0), der Rahmenplan
außer an den vier Anlässen.

**Warum jetzt und in dieser Reihenfolge:** 17 und 18 vor 12, damit der
Review aufgeräumte und gehärtete Seiten liest (Rahmenplan 3). Die Runde
läuft nach dem Merge von SD, weil bis dahin kein anderer Zweig Rahmenplan
oder Backlog schreiben durfte (E-SD-19) — und weil die Liste der Punkte
erst seit SD-02 an einer Stelle steht.

## 2. Befund

### 2.1 Wie gemessen

Am 26.09.2026 auf dem Arbeitszweig (`8e29cf0` bis `5a2a69a`), **nur
lesend** — keine Probe, kein Prüfstand, nichts geschrieben —, gefächert auf
Agenten (E-ZE-25): je Gruppe von neun bis zehn Punkten ein **Sichter**
(Eintrag vollständig lesen, am heutigen Code und an der Doku nachsehen, ob
das Problem noch besteht, einordnen, Dateien und Prüfmittel nennen) und
ein **Gegenprüfer** mit dem Auftrag, jede Einordnung zu **widerlegen**
(Trefferzahlen selbst nachrechnen, Dateilisten vervollständigen,
Kollisionen mit 18 und PK suchen); dazu ein **Umfeld-Agent** (Schritt 18,
PK-06 bis -08, Rahmenplan 4 und 6, tote Zweige, Vorlage Runde 3). Sechs
Gruppen: A Demo und Referenz · B Quelltext-Prüfmittel · C Prüfstand und
Android · D Server-Code · E Oberfläche · F Doku und Betrieb.

**Ergebnis der Gegenprüfung:** 55 von 55 Einordnungen zurück, **20
bestätigt, 35 korrigiert, 0 widerlegt.** Keine Korrektur hat die
Einordnung (umsetzen, austragen, umhängen) geändert; sie betrafen
Trefferzahlen (Nr. 36: 16 statt 20 Dateien; Nr. 321: 13 Breiten statt 8),
vollständigere Dateilisten (Nr. 297: `Design.md`, `Technik.md`; Nr. 62:
`uhr-bilder.sh`) und vor allem **übersehene Berührungen mit Schritt 18**
(Nr. 76, 158, 175, 250, 258, 277, 291, 299 — 2.3). Die Rohdaten liegen
nicht im Repositorium; die Einordnung je Punkt steht in 2.2, die
Trefferzahlen mit Messdatum stehen dort, wo sie gebraucht werden (4).

Drei Befunde zur Fächerung selbst:

- **F-R4-01 Ein Workflow fährt in diesem Container zwei Agenten
  gleichzeitig,** nicht sechzehn: die Grenze ist `CPUs − 2`, und der
  Container hat vier. Dreizehn Agenten in einem Workflow hätten sieben
  Runden gebraucht; zwei Workflows mit je drei Gruppen fuhren vier.
- **F-R4-02 Eine Unterbrechung des Zugs beendet die Agenten.** Der erste
  Anlauf (14 Agenten, ein Workflow) starb um 14:31 mit „Request interrupted
  by user" in beiden laufenden Agenten; gestartet hatten erst zwei. Wer
  einen Workflow laufen lässt, schreibt es der Betreiberin, bevor er den
  Zug beendet — eine normale Nachricht ist unschädlich, der Stopp-Knopf
  nicht. Ein Neustart des Containers dagegen ließ die fertigen Ergebnisse
  stehen (Ausgabedateien im Scratchpad).
- **F-R4-03 Die Gegenprüfung lohnt.** 35 von 55 Sichtungen brauchten eine
  Korrektur, acht davon an der Stelle, die den Paketschnitt bestimmt (die
  Sperrliste von 18). Ein Sichter allein hätte die Runde mit den Dateien
  von Schritt 18 verzahnt, ohne es zu wissen.

### 2.2 Die 55 Punkte — Einordnung

Spalte „Befund": was am 26.09.2026 auf dem heutigen Stand gilt (die
Einträge stammen aus August und September). Spalte „Paket": Abschnitt 4.

| Nr. | Punkt | Befund am 26.09.2026 | Einordnung | Paket |
|---|---|---|---|---|
| 36 | Prüfmittel für Klassennamen aus JavaScript | gilt teilweise: 62 Selektoren in 23 Dateien, 39 nicht-literale Tonübergaben in 16 Dateien übersprungen | umsetzen, mit 331 ein Paket | R4-05 |
| 37 | Konto wächst über Jahre | gilt teilweise: Zeitraumübersicht ohne Seitengrenze (0 `seite:` in `zeitraum.php`), Suche mit 200; zwei stille Kappungen (500/120); `post_max_size` ist dokumentiert | umsetzen (a)+(b), Rest nach v1.0 — Q-R4-03 | R4-17 |
| 62 | Logodateien mit alten Farbwerten | gilt: drei von vier SVG alt; keine Vorlage im Baum seit `6f316ee` (12.09.2026) | **umhängen → 13** (E-R4-08) | R4-01 |
| 76 | Demo-Reset alle 30 Minuten | gilt: `demo_reset_wenn_faellig()` rein zeitgesteuert, 9 `demo_*`-Schlüssel, keine Änderungsmarke; Setzstellen auf der 18-Liste | umsetzen (E-R4-10) | R4-14 |
| 92 | `bildreihe` fotografiert nur den Start | gilt: `bildreihe()` nimmt zwei Parameter, keine Tastenfolge; erster Abnehmer ist S11 (Uhr Haupt) | **umhängen → 12a** — Q-R4-12 | R4-01 |
| 95 | Rundlauffälle lassen Daten im Admin-Konto | gilt: `aufraeumen()` löscht 3 Tabellen per `mariadb`-CLI, Diensttage/Einsätze/Spuren bleiben (Code-Kommentar 18/10/28) | umsetzen — Android Korrektur | R4-21 |
| 114 | Abgewiesene Pakete verwerfen | gilt teilweise: Räumen gebaut (`abgewieseneRaeumen()`), der Bedienweg nicht | umsetzen mit Mockup (E-R4-09) | R4-22 |
| 116 | Kontrastwerkzeug ohne Vollständigkeit (Web) | gilt teilweise: `PAARE` 25 feste Einträge, keine Ableitung aus `style.css`, keine Selbstprobe; Android seit AR erledigt | umsetzen | R4-08 |
| 122 | Freier Zeitraum und Diagramme | gilt: feste Fenster (`STAT_FENSTER_*`), drei Reiter; `ui_listenkopf()` hat kein Datumsfeld; kein Diagramm-Baustein | umsetzen, zwei Pakete mit Mockup (E-R4-07) | R4-23, R4-24 |
| 140 | Push auf `main` ist Deploy | erledigt: Wache, Tor, Zweigschutz, 2FA (F-SD-06) | austragen | R4-01 |
| 150 | Cron-Pfad in `server/jobs.php` | gilt teilweise: Zeile 13 des Kopfkommentars (Zeile 64 ist Werdegang und richtig) | umsetzen als Beifang | R4-09 |
| 158 | `days` ohne `created_at` | gilt: `ingest_tag_offen()` fragt `MAX(created_at)` über Einsätze und Ruhesegmente; 4 `INSERT INTO days` | umsetzen, Migration — Q-R4-09 | R4-15 |
| 161 | Stück aus einer Aufzeichnung löschen | gilt: `trash_delete_day()` nimmt alles, `spur_lib.php` hat 34 Funktionen, keine schneidet; 12a verlegt die Spurfunktionen in den Browser | **umhängen → 12a** — Q-R4-12 | R4-01 |
| 170 | Kennzeichnung ohne Prüfmittel | gilt: `dtGeschuetzt()` 9 Treffer (einer aus `mf_pat_felder()`), `'geschuetzt' => true` 6, keine Sollliste | umsetzen — neues Prüfmittel | R4-06 |
| 172 | Wartungsprobe flackert | erledigt ohne Nummer: Median aus fünf seit P5c/AP9 (F-P5c-165, `3258916`) | austragen | R4-01 |
| 175 | `edbak_uebersicht()` ohne Aufrufer | gilt: 8 Treffer, 0 Aufrufer; zwei Kommentare und `Technik.md` im Präsens | umsetzen | R4-09 |
| 198 | Windendienste nur luftgebunden | Beschluss trägt (E-ZE-31, `d.kind = ?` mit `'air'`) | bleibt `nicht umsetzen`, Ziel `nach v1.0` — Q-R4-12 | R4-01 |
| 200 | Bounce-Postfach | Beschluss trägt (E-P5c-51); `mail_lage()` zählt bis drei Adressen | bleibt `nicht umsetzen`, Ziel `nach v1.0` — Q-R4-12 | R4-01 |
| 202 | Zentralisierung, Paket 6 | Stand trägt: sechs CRUD-Paare (nicht vier) in `einstellungen.php`; jeder Anlass träfe die 18-Liste | **umhängen → `Pflegeaufgabe`**, Eintrag auf Stand — Q-R4-12 | R4-01 |
| 207 | `gen-em.org` in `tools/` und `.github/` | gilt, gewachsen: 108 Treffer (96), `server/` 0; die 3 in `.github/` sind Kommentare zu echten Adressen und bleiben; E-PK-27 entschieden | umsetzen in `tools/` — Q-R4-07 | R4-19 |
| 209 | `Design.md`-Tabelle mit falschen Zeilen | Befund behoben (P5c), Ursache nicht: 38 von 49 Zeilen weichen um 1 ab | umsetzen — Erzeuger als Riegel, zuletzt | R4-25 |
| 216 | Doppelte Trennlinien im P5a-Prüfdokument | überholt: Dokument gelöscht (`f6cb5fd`), 0 doppelte Trennlinien in `docs/` | austragen | R4-01 |
| 232 | Fristen der Rückfragen nie abgelaufen | gilt, Anlass nicht eingetreten; alle Aufrufer von `einstieg_lib.php` auf der 18-Liste (Nr. 233) | **umhängen → 18**, `nur auf Anlass` — Q-R4-12 | R4-01 |
| 239 | `INSERT` ohne Backticks | gilt, breiter: 3 Missions-INSERTs mit `implode`, `mf_spalten()` quotiert nur den Alias | umsetzen, Weg A — Q-R4-14 | R4-12 |
| 250 | Umleiten nach POST | gilt: 12 Seiten mit POST ohne `Location`, ≈ 43 Zweige; `betrieb_server.php` gehört 18 | umsetzen, 11 Seiten | R4-11 |
| 258 | Drei `api/`-Dateien am `json_out()` vorbei | gilt teilweise: 19 `echo json_encode`, 12 Statuscodes, 0 Kopfzeilen; die drei Dateien auf der 18-Liste | umsetzen, mechanisch, zuerst | R4-09 |
| 259 | GPX-Probe blind nach Demo-Reset | gilt teilweise: Teil 2 hält an der numerischen ID (`preg_match … (\d+)`), 0 `demo_letzter_reset` | umsetzen | R4-14 |
| 260 | „beider FTPS-Schritte" in zwei Kommentaren | gilt: `wartung_lib.php:643`, `adminbackup_lib.php:24–30` (drei falsche Aussagen) | umsetzen als Beifang | R4-09 |
| 265 | `.github/` → Doku-Verweise ohne Prüfmittel | gilt, Anlass nicht eingetreten; `auslieferung.yml` gehört der Kette | **umhängen → PK**, `nur auf Anlass` — Q-R4-12 | R4-01 |
| 266 | `plattform_pruefen()` sagt „aus" | gilt: `function_exists()` 8×, OPcache und `posix_geteuid` werden zum Mangel | umsetzen — dreiwertiger Helfer | R4-09 |
| 271 | Meldungshülle im Schnittblock ohne Symbol | gilt: fünf `textContent`-Befüllungen (nicht drei), `EdHtml` schon gebunden | umsetzen | R4-13 |
| 272 | `<p class="meldung">` im Entsperrdialog | gilt: `unlock.js:106`, Ton nur zur Laufzeit; `html.js` fehlt in der Immer-Liste, aber auf allen sieben Krypto-Seiten | umsetzen | R4-13 |
| 273 | Dritte Schreibweise für Dauern | gilt: `dauer()` in `schneiden.js:93`, zwei Aufrufer; kein Bilderlauf zeigt den Schnittblock | umsetzen — Q-R4-06 | R4-13 |
| 275 | Kein Dienst über Mitternacht | **gilt teilweise — der Eintrag zählt falsch:** 2 von 20 (beide `ground`, NEF Talwang, auf einer Zeitumstellung), luftgebunden keiner | umsetzen, mit 323 | R4-16 |
| 277 | `await fetch` ohne `catch` in `einstellungen.php` | gilt, im Code als bewahrter Fehler markiert (Zeile 4395); die Freigabeprobe fährt den Fall nicht | umsetzen | R4-09 |
| 283 | Textprobe liest Kommentare nicht | gilt teilweise; die volle Namensliste (46 Namen) trifft mehr Kommentare als die zwei gezählten (F-R4-12) | umsetzen — Q-R4-08 | R4-07 (+ R4-09) |
| 287 | Karten „Was hier gilt" außerhalb Verwaltung/Betrieb | gilt: drei Karten (`import.php`, `einsatz_form.php`, `wiederherstellen.php`) plus Untertitel | umsetzen | R4-20 |
| 291 | Halbes Schema nach gescheiterter Einrichtung | gilt: ein `try` um `run_sql_file()` und `konto_anlegen()`, 41 von 42 Tabellen ohne `IF NOT EXISTS`; `install.php` gehört ab Zeile 397 dem Schritt 18 | umsetzen, Block 348–394 | R4-09 |
| 295 | Messstand ohne Statistik-Schritt | erledigt ohne Nummer: `schritt_statistik()` seit P5c/AP7 (`d519fac`); Text „Zuordnung: 10c AP7" widerspricht der Kopfzeile | austragen | R4-01 |
| 297 | Bilderlauf ohne Breiten, Admin, Rollbehälter | gilt teilweise: acht Breiten ohne 1200 und 1600 (zwei Schwellen aus `Design.md` 7), `rolle: admin` meldet als BetreiberIn, Support nie | umsetzen — Q-R4-13 | R4-08 |
| 299 | Kontolöschung an `konto_loeschen()` vorbei | gilt, Drift ist da: `admin_user.php` 591–686 schreibt selbst; dritte Stelle `demo_entfernen()` | umsetzen | R4-10 |
| 301 | `Sandbox-Setup.md` sagt, Firefox/WebKit starten nicht | gilt teilweise: ein Satz in Tabelle 1 (Server-Teil erledigt) | umsetzen (ein Satz), dann austragen | R4-01 |
| 318 | `pysyntax` sieht Escape-Folgen nicht | gilt: 2 von 63 Dateien; unter 3.11 kommt die Folge als `SyntaxError` an | umsetzen | R4-07 |
| 321 | Stilvergleich meldet Fremdes | gilt: `seitenprobe()` hängt alle Seiten in ein Dokument; 13 Breiten, 52 `setContent` | umsetzen, vor jeder `style.css`-Änderung | R4-08 |
| 322 | Demo-Reset trifft irgendeine Probe | gilt: nur ein `nach`, 29 Proben demo-empfindlich, Reihenfolge stille Voraussetzung | umsetzen, Weg A (nur `tools/`) | R4-04 |
| 323 | Referenz und Fixture mit Nutzlast 11 | gilt (Feldsatz: `user_bases = []`); die Fassungszahl 7 der Fixture ist Absicht | umsetzen, mit 275 | R4-16 |
| 327 | Rollenmatrix ohne BetreiberIn-Handlungen | gilt, schärfer: 70 Matrixzeilen, 1 zu `betrieb_*`; 7 GET und 26 POST fehlen; `schluesselblatt_pruefen.php` hat `csrf_check()` | umsetzen | R4-18 |
| 329 | Prüfstand misst mit Anlage, Stufe 1 ohne | gilt teilweise: Riegellisten stimmen (20 = 20); 12 PHP-Werkzeuge mit `require server/…` | umsetzen — zwei billige Regeln in `bestand.py` | R4-02 |
| 331 | Zusammengesetzte Klassen der Bausteine | gilt, gewachsen: 73 Hinweise (62), 29 mit Bausteinpräfix | umsetzen, mit 36 | R4-05 |
| 332 | Prüfstand gegen altes Schema | gilt: `hochfahren.sh` prüft nur HTTP 200, keine Schemafrage | umsetzen | R4-02 |
| 333 | `style.css`-Kommentar nennt O1 statt O2 | gilt: Zeile 3033 | umsetzen als Beifang | R4-09 |
| 334 | Android-Werkzeuge an keinem Lauf | gilt: 17 Nennungen, 0 in `tools/pruefstand/`; `stroeme.py` schreibt in den Baum (prüfen) | umsetzen — vier Proben | R4-04 |
| 335 | Ausbaustufe `android` an Plattform 36 | gilt: `pruefen.sh` prüft `android-36`, beide Module `compileSdk = 37`; `Sandbox-Setup.md` nennt die 36 zweimal | umsetzen | R4-02 |
| 336 | `Eingabefeld()` ohne Aufrufer | gilt: 1 Treffer (Definition); Paar „Cursor" in `kontraste.py` | umsetzen — Android Korrektur | R4-21 |
| 339 | Nummernriegel gegen alle Zweige | gilt: nichts davon existiert; Bausteine in `bestand.py` (`backlog_zeilen()`) | umsetzen — `tools/steuerung/nummern.py` | R4-03 |

### 2.3 Umfeld — was 17 nicht anfassen soll

**Schritt 18** (Nr. 242, 247, 249, 233, 210, 228) wird die folgenden
Dateien schreiben; wo ein Punkt der Runde dieselbe Datei anfasst, steht er
daneben. **Das ist ein Gebot, kein Verbot** (E-R4-11): 18 setzt den Merge
von 17 voraus und wird erst danach konzipiert, baut also auf dem Stand von
17 auf. Ein 17er-Paket an einer dieser Dateien bleibt klein, ändert nur
Zeilen, die 18 nicht umbauen wird, und steht in 4 mit dem Vermerk
„18-Liste".

| Datei | Schritt 18 | Punkt der Runde an derselben Datei |
|---|---|---|
| `server/ingest.php`, `server/diensttag_lib.php` | 210 (Deadlock) | **Nr. 158** (`ingest_tag_offen()`), **Nr. 76** (Setzstelle) |
| `server/api/rueckfrage.php`, `api/schluessel_erneuern.php`, `api/schluesselblatt_pruefen.php` | 233, 242 | **Nr. 258** (`json_out()`), Nr. 327 (liest) |
| `server/einstellungen.php` | 242 | **Nr. 277** (Freigabe-Handler, andere Stelle) |
| `server/admin_user.php`, `server/konto_lib.php` | 249, 228 | **Nr. 299** (`user_delete`), **Nr. 250** |
| `server/install.php` | 247 (ab Zeile 397) | **Nr. 291** (Block 348–394) |
| `server/auth_guard.php`, `server/demo_lib.php` | 242 | **Nr. 76** (Änderungsmarke), 259 und 322 nur bei Weg B (nicht gewählt) |
| `server/wartung_lib.php`, `server/db.php` | 210 | **Nr. 260** (Kommentar), Nr. 239 (kein Helfer in `db.php`) |
| `server/betrieb_server.php`, `betrieb_schluesselblatt.php`, `admin_*.php` | 233, 247 | **Nr. 250** — `betrieb_server.php` bleibt draußen |
| `server/schema.sql`, `server/migration_lib.php` | 242 („Gerät merken") | **Nr. 158** (Migration) |
| `adminbackup_lib.php` | frei halten | **Nr. 175** (Löschung), **Nr. 260** (Kommentar) |
| `sitzung_lib.php`, `session_lib.php`, `login.php`, `pw_handling.php`, `zweitfaktor*.php`, `totp_lib.php`, `rueckweg_lib.php`, `serverkrypto_lib.php`, `*_archiv_lib.php`, `sicherungsziel_lib.php`, `zip_lib.php`, `transaktion_lib.php`, `registrieren.php`, `ratelimit_lib.php` | 242/247/249/210/228 | keiner — frei halten |

**PK-06 bis PK-08** schreiben `.github/workflows/auslieferung.yml`,
`ausliefern-lauf.yml`, `integritaet.yml` (PK-06, -08), `CLAUDE.md` 3 und
6, `Technik.md` 6.2/6.3, `Rahmenplan.md` (Erledigt-Zeile Kette II) und
`Backlog.md` (Nr. 227) beim Abschluss PK-07, dazu die fünf Geheimnisse und
`signatur.properties` (PK-08). `pruefung.yml` gehört **nicht** dazu; 17
liest sie (R4-02), schreibt sie nicht. **Nr. 207** fasst `.github/` nicht
an (die drei Treffer dort bleiben, E-PK-27) — es bleibt in der Runde
(Korrektur der Annahme aus der ersten Fassung dieses Abschnitts).
Nr. 265 (`.github/` → Doku) geht an PK (Q-R4-12).

**Mittelbare Bezüge** (kein Punkt nennt einen Schritt wörtlich; Muster
`Schritt 1[2348]a?` über die Folgezeilen → 0): Nr. 62 → 13 (E-R4-08);
Nr. 140 → nichts Offenes mehr als die Verschiebung (F-SD-06); Nr. 114 →
Krypto-Review AN-2 (Eingang von 12); Nr. 283 → Gegenlesung PK-04;
**Nr. 295 widerspricht sich** — Kopfzeile `gehört zu: 17`, Text „Zuordnung:
10c AP7" (gemergt, erledigt); Nr. 332 → F-PK-39; Nr. 260, 265, 266 → Kette
II, die mit PK-07 endet.

**Rahmenplan 4 und 6:** Die Faustregel („alles, was `server/`, `schema.sql`
oder `update.php` schreibt, wartet auf das Paket davor") gilt für 17 wie
für jeden Schritt. Kein Posten in 6 nennt 17; fällig wird mit dem ersten
Prüfmittel der Runde **P-BR-09** („beim nächsten Prüfmittel", Prüfliste
BR — R4-02), und die Demo-Reset-Punkte (76, 259, 322) berühren den Posten
„Demo-Konto einmal „Auf Standard zurücksetzen"".

**P-SD-20 ist beantwortet** (Prüfdokument SD 3.7 (4): „Kommt die nächste
Instanz ohne Rückfrage zurecht?"): ja. Die Abschlüsse von SD, AR und BV und
dieses Konzept sind mit Kopfzeilen und unter den Decken geschrieben,
`decken.py` und `uebersicht.py` haben jede Fassung gemessen, ohne
Rückfrage. Eine Stelle war nicht beschrieben und ist jetzt entschieden
(**E-R4-06**): Wie ein erledigter Eintrag in `Backlog-Erledigt.md` aussieht
— Kopfzeile bleibt, `Stand: erledigt`, ein Schlusssatz mit Datum, Anlass und
Beleg als letzte Folgezeile. Kein Backlog-Punkt.

**Zwei tote Zweige** (F-R4-04): `claude/nice-lovelace-snlo8m` (Konzept RW,
inhaltsgleich über `5fb1d2a` auf `main`, dort mit `00cacef` gelöscht) und
`claude/pk05-tor-umbauen` (zwei Textcommits, inhaltsgleich über `dc90678`
und PR #82 auf `main`) tragen nichts Ungemergtes — Q-R4-01.

### 2.4 Befunde am Bestand

Was die Sichtung **anders** fand, als die Einträge sagen (jede Zahl vom
26.09.2026, nur lesend gemessen):

- **F-R4-05 Nr. 275 zählt falsch:** nicht 0, sondern 2 von 20 aktiven
  Diensttagen haben Einsätze auf zwei Kalendertagen (Tage 22 und 30, beide
  bodengebunden, NEF Talwang, beide auf einer Zeitumstellung). Was fehlt,
  ist ein **luftgebundener** Nachtdienst ohne Zeitumstellung.
- **F-R4-06 Nr. 209: der Befund ist behoben, die Ursache nicht.** Web
  20.38.0 hat die Tabellen neu erzeugt; heute weichen 38 von 49 Zeilen der
  Bausteintabelle wieder um genau 1 ab. Ohne Riegel läuft der Erzeuger
  nicht mit.
- **F-R4-07 Vier Punkte sind erledigt, ohne dass ihre Nummer genannt
  wurde:** Nr. 172 (Median aus fünf, P5c/AP9, `3258916`), Nr. 295
  (`schritt_statistik()`, P5c/AP7, `d519fac`), Nr. 140 (F-SD-06), Nr. 216
  (Dokument gelöscht, `f6cb5fd`). Wer einen Backlog-Punkt nebenbei
  erledigt, nennt die Nummer im Commit — sonst findet ihn nur eine Runde
  wie diese.
- **F-R4-08 Nr. 62: die Vorlagen sind nie angekommen.** E-SD-34 streicht
  „Logovorlagen in den Markenfarben" als erledigt; im Baum ist seit
  `6f316ee` (12.09.2026) keine Logodatei geändert. Entschieden: Schritt 13
  (E-R4-08).
- **F-R4-09 Nr. 299: die vorhergesagte Drift ist eingetreten.** Neben
  `konto_loeschen()` (Jobs, Selbstlöschung) und dem Zweig `user_delete` in
  `admin_user.php` löscht auch `demo_entfernen()` ein Konto selbst
  (`DELETE FROM users` dreimal im Code).
- **F-R4-10 Nr. 250: zwölf Seiten,** nicht „ein Teil": `admin_demo`,
  `admin_installation`, `admin_komplettsicherung`, `admin_rechtstexte`,
  `admin_sicherungen`, `admin_sicherungsziele`, `admin_users`,
  `betrieb_jobs`, `betrieb_server`, `betrieb_sicherheit`, `betrieb_status`,
  `betrieb_updates` nehmen POST an und leiten nicht um (≈ 43 Zweige).
- **F-R4-11 Nr. 239 ist breiter:** drei Missions-INSERTs bauen ihre
  Spaltenliste mit `implode` (`backup_lib.php`, `ingest.php`, ein dritter),
  `mf_spalten()` quotiert nur den Alias. Der eine Weg ist der Katalog, nicht
  die Aufrufer.
- **F-R4-12 Nr. 283: die Namensliste trifft mehr als zwei Kommentare.**
  `VERBOTENE_NAMEN` hat 46 Einträge; damit gelesen stehen in Kommentaren
  unter `server/` reale Stationen und Kliniken als Messbeispiele (eine
  reale Station in `style.css` und `version.php`, eine reale Klinik in
  `geo.js` und `version.php`, ein realer Rufname, der alte Platzhalter mit
  realem Ort als Zitat in `version.php`) — Q-R4-08. Die Namen stehen hier
  bewusst nicht; die Fundstellen liefert `textprobe.py` mit der vollen
  Liste (R4-07).
- **F-R4-13 Nr. 202: sechs CRUD-Paare,** nicht vier, in
  `einstellungen.php`; die „Ablage mit Zeitstempel" gibt es dreimal als
  `gmdate('Y-m-d\TH-i-s\Z')`. Der Eintrag wird auf Stand gebracht und an
  `Pflegeaufgabe` gehängt.
- **F-R4-14 Nr. 327: `api/schluesselblatt_pruefen.php` hat `csrf_check()`**
  — der Eintrag vermutete das Gegenteil; die Rollenmatrix hat für sieben
  GET- und 26 POST-Handlungen der BetreiberIn-Seiten keine Zeile.
- **F-R4-15 Nr. 122: `ui_listenkopf()` hat kein Datumsfeld.** Der freie
  Zeitraum ist selbst eine Bausteinerweiterung, kein „vorhandener Baustein";
  `Design.md` 9 kennt keinen Diagramm-Baustein. Beides braucht ein Mockup.
- **F-R4-16 Nr. 271: fünf Befüllungen,** nicht drei; **Nr. 321: 13
  Breiten,** nicht 8; **Nr. 36: 16 Dateien,** nicht 20; **Nr. 331: 73
  Hinweise,** nicht 62 — die Zahlen der Einträge sind gealtert, nicht falsch.
- **F-R4-17 Nr. 334: `stroeme.py` schreibt in den Baum**
  (`android/handy/src/test/resources/stroeme.txt` „erzeugt von
  werkzeuge/stroeme.py"). Eine Probe, die den Baum ändert, ändert den
  Baum-Hash des Berichts — R4-04 prüft das, bevor die Probe in die
  Ablaufdatei kommt.
- **F-R4-18 Nr. 170: der achte `dtGeschuetzt()`-Aufruf ist eine
  Katalogschleife** (`mf_pat_felder()` in `mission_fields_lib.php`) —
  „acht handgeschriebene" ist seit S9/AP7 überholt; der manuelle Abfahrtort
  hat in der Leseansicht keine beschriftete Zeile (nur das Kartenpopup,
  ohne Zeichen) — Q-R4-15.

**Befunde der Umsetzung** (gemessen, nicht gelesen):

- **F-R4-19 Die Abnahmezahl von R4-01 ist verzählt.** „`uebersicht.py
  --ziel 17` zählt 37" stimmt nicht mit 2.2 überein: Die Tabelle führt 42
  Punkte mit einem anderen Paket als R4-01, dazu kommt Nr. 340 — nach
  R4-01 sind es **43** (gemessen 26.09.2026). E-R4-12 („37 zu bauende
  Punkte") ist dieselbe Zahl und ebenso verzählt; der Paketschnitt ist
  davon nicht berührt, jeder der 42 steht in genau einem Paket.
- **F-R4-20 Firefox braucht die vier Bibliotheken nicht, WebKit schon.**
  Gemessen am 26.09.2026 für Nr. 301: die vier Pakete entfernt — Chromium
  und Firefox starten, WebKit bricht ab; wieder geholt — drei von drei.
  Dazu brachte das Abbild MariaDB, ImageMagick und `rsvg-convert` schon mit
  (apt-Verlauf 22.09.2026, der Sitzungshook vor PK-02); `Sandbox-Setup.md`
  1 sagt beides jetzt.
- **F-R4-21 Die örtliche MariaDB kennt keine benannten Zeitzonen.**
  `CONVERT_TZ(…, '+00:00', 'Europe/Berlin')` liefert NULL (Zeitzonentabellen
  nicht geladen). Die Anwendung ruft `CONVERT_TZ` nirgends (0 Treffer in
  `server/` und `tools/`); wer aber in Ortszeit misst wie Nr. 275, misst
  örtlich nichts. Nr. 275 ist deshalb über die UTC-Zeiten nachgerechnet (2
  von 20 bestätigt, Tage mit `id` 7 und 19); R4-16 rechnet ebenso.
- **F-R4-22 Der Prüfstand hielt eine Variable mit Vorgabe für Pflicht.**
  `pruefen.sh` suchte im Aufruf jeder Probe nach `${NAME` und meldete
  „Umgebungswert fehlt", wenn `NAME` nicht exportiert war — auch für
  `${ANDROID_HOME:-/opt/android-sdk}`, das seine Vorgabe mitbringt. Der
  Android-Bau war damit in jeder Sitzung ohne `export ANDROID_HOME` „nicht
  gemessen" (gefunden im ersten Lauf von R4-02, weil `android/LIESMICH.md`
  berührt war). Behoben in R4-02: Pflicht ist nur, was ohne Vorgabe steht.

## 3. Entscheidungen und Fragen

### 3.1 Entscheidungen

| Nr. | Entscheidung | Von | Grund |
|---|---|---|---|
| E-R4-01 | **Das Konzept entsteht mit Fable, in dieser Sitzung; die Umsetzung in einer anderen Instanz mit Opus.** | Betreiberin, 26.09.2026 | R14 (Konzepte mit Fable) und K2 (Umsetzung Opus). Die Fahrplanzeile „kein Fable-Schritt" meint die Umsetzung, nicht das Konzept. |
| E-R4-02 | **Abschlüsse und Konzept gehen per PR #93 auf `main`**; die Umsetzung läuft danach auf einem eigenen Zweig mit eigenem PR. | Betreiberin, 26.09.2026 | Der Rahmenplan auf `main` sagte bis dahin „SD läuft, PR #92 offen"; PK-06 bis PK-08 warteten mit ihrer Buchführung auf den SD-Merge (E-SD-19). Ein PR braucht seit PK-05 einen Prüfbericht (`Pruefablauf.md` 5) — für reine `docs/`-Änderungen die Stufe klein. |
| E-R4-03 | **Die Abschlüsse von SD, AR und BV (K9) werden in dieser Sitzung geschrieben**, je ein Commit, vor dem Konzept. | Betreiberin, 26.09.2026 | Sie sind reine Buchführung, und sie stehen im Rahmenplan-Kopf vor Schritt 17. Fassungen 131 bis 133. |
| E-R4-04 | **Nr. 339 (Nummernriegel im Prüfstand) gehört zu 17** — als Kandidat dieser Runde, nicht zu PK. | Betreiberin, 26.09.2026 (Q-BV-05, E-BV-19) | Prüfmittel sind der Kern der Runde; PK hat mit PK-06 bis PK-08 genug. |
| E-R4-05 | **Backlog-Spanne 340 bis 349**, eingetragen und gepusht, bevor eine Nummer vergeben wird. | Umsetzung des Konzepts (E-SD-21) | Zwei Kollisionen an einem Tag (F-BV-14, -18) hatten genau diese Lücke: eine Spanne, die nur auf dem eigenen Zweig stand. |
| E-R4-06 | **Form eines verschobenen Eintrags in `Backlog-Erledigt.md`:** Kopfzeile bleibt, `Stand: erledigt`, ein Schlusssatz mit Datum, Anlass und Beleg als letzte Folgezeile (fünf Leerzeichen). | Konzept (SD-Abschluss, 26.09.2026) | Konzept SD sagt „wörtlich", aber nicht, was mit dem Stand geschieht; `uebersicht.py` liest nur `Backlog.md`, `decken.py` misst in der Erledigt-Datei nur die Nummern. Ein Helfer im Scratchpad hat die fünf SD-Einträge so verschoben; ob er als `tools/steuerung/verschieben.py` eingecheckt wird, entscheidet R4-01. |
| E-R4-07 | **Nr. 122: freier Zeitraum UND Diagramme in 17, je als eigenes Paket mit Mockup; Diagramme als Inline-SVG ohne Bibliothek.** | Betreiberin, 26.09.2026 (Q-R4-02) | Die Fahrplanzeile nennt den Statistik-Rest; „keine fremde Quelle zur Laufzeit" lässt keine Diagrammbibliothek zu, die nicht vendoriert wäre — Inline-SVG braucht keine. Preis: zwei Mockup-Freigaben (H-R4-01). |
| E-R4-08 | **Nr. 62 geht an Schritt 13** (P7, „mit dem neuen NEF-Logo, vor P7"); 17 fasst keine Bilddatei an. | Betreiberin, 26.09.2026 (Q-R4-04) | Die Vorlagen liegen nicht vor (F-R4-08); ohne sie wäre jede Ableitung eine zweite Näherung. |
| E-R4-09 | **Nr. 114 in 17, mit Mockup:** Knopf „Verwerfen" mit Rückfrage nach dem Muster der Garmin-Uhr — Android Neben. | Betreiberin, 26.09.2026 (Q-R4-05) | Der Server hat das Paket mit 400 endgültig abgelehnt; „Nachreichen von Hand" wäre ein zweiter Weg an `validate_lib.php` vorbei. Android-Mockups liegen unter `android/mockups/`. |
| E-R4-10 | **Nr. 76 in 17:** Änderungsmarke in `app_state`, gesetzt bei POST des Demo-Kontos und bei angenommenem Upload; Reset nur bei gesetzter Marke, täglicher Pflichtreset als Netz. | Betreiberin, 26.09.2026 (Q-R4-10) | Die Fahrplanzeile nennt den Demo-Reset-Takt; die zwei Setzstellen sind je eine Zeile in Dateien der 18-Liste, und 18 baut auf 17 auf. |
| E-R4-11 | **Die Sperrliste von Schritt 18 ist ein Gebot, kein Verbot.** Pakete an diesen Dateien bleiben klein, ändern keine Zeile, die 18 umbaut, und sind in 4 vermerkt. | Konzept | 18 hat „Merge von 17" als Voraussetzung und wird danach konzipiert. Ein Verbot hätte acht Punkte der Runde (76, 158, 175, 250, 258, 277, 291, 299) ohne Not nach 18 geschoben. |
| E-R4-14 | **Konzept freigegeben; die Umsetzung arbeitet durch** — Halt nur an H-R4-01 bis -05, bei Problemen und vor dem PR; **Fächerung strikt nach Abschnitt 4** (nur R4-11, R4-19), auch bei eingeschaltetem Ultracode. Zweig `claude/schritt-17-konzept-mockups-q0yjcm`. | Betreiberin, 26.09.2026 (Freigabe des Umsetzungsplans) | Merge von PR #93 und Auftrag „Leg los"; `CLAUDE.md` 7: ohne Fächerungszeile keine Fächerung, und der Container fährt zwei Agenten gleichzeitig (F-R4-01). |
| E-R4-15 | Q-R4-01: **Die Betreiberin löscht die zwei toten Zweige selbst**; die Umsetzung löscht nichts. | Betreiberin, 26.09.2026 | Zuarbeit in Rahmenplan 6.1, P-R4-06. |
| E-R4-16 | Q-R4-03: (a) Seitengrenze und (b) benannte Kappungen in 17 (R4-17); Vorschneiden und Monatsvorwahl nach v1.0. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-17 | Q-R4-06: Die Dauer im Schnittblock folgt `EdFormat.dauer()` („1h 06min"). | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-18 | Q-R4-07: E-PK-27 in `tools/` (R4-19); die Ausnahme für `messstand@` fällt. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-19 | Q-R4-08: **E-P1-02 gilt auch für Kommentare.** Die acht Stellen unter `server/` werden umgeschrieben (R4-09), dazu ein realer Vorname als Beispiel eines Gerätenamens in `migration_lib.php`; das Fremdformat bleibt Ausnahme (E-PK-39). | Betreiberin, 26.09.2026 (nach Erklärung) | Die Dateien sind öffentlich; die Textprobe liest danach in der Klasse `namen` auch Kommentare (R4-07). |
| E-R4-20 | Q-R4-09: `days.created_at` mit Migration in 17 (R4-15). | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-21 | Q-R4-11: F-SD-08 ist **Nr. 340**, behoben in R4-25. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-22 | Q-R4-12: Umhängen wie vorgeschlagen — ausgeführt in R4-01. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-23 | Q-R4-13: Der Bilderlauf misst zusätzlich **1200 und 1600 px** — zehn Breiten. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-24 | Q-R4-14: **Weg A** — die Backticks in `mf_spalten()`. | Betreiberin, 26.09.2026 | wie empfohlen |
| E-R4-25 | Q-R4-15: **Der manuelle Abfahrtort bekommt in der Leseansicht schon in 17 eine beschriftete Zeile mit Schloss** (R4-06, Web Korrektur) — abweichend von der Empfehlung; Nr. 341 wird nicht vergeben. | Betreiberin, 26.09.2026 | Vorhandene Bausteine (`zeile()`, `dtGeschuetzt()` in `einsatz.php`), kein neuer — kein Mockup nötig (`CLAUDE.md` 5). |
| E-R4-26 | Q-R4-16: **Die drei Mockups sind freigegeben**, wie gezeigt; H-R4-01 ist erfüllt. | Betreiberin, 26.09.2026 | — |
| E-R4-27 | **R4-19 läuft vor R4-16**, direkt nach R4-15; sonst gilt die Nummernfolge. | Umsetzung, 26.09.2026 | R4-16 verlangt es selbst („Nach R4-19 und R4-15"); die Nummer allein hätte die Referenz zweimal erzeugt. |
| E-R4-28 | **Der Verschiebe-Helfer ist eingecheckt** (`tools/steuerung/verschieben.py`), ohne Selbstprobe. | Umsetzung (R4-01) | E-R4-06; eine Runde verschiebt Dutzende Punkte, je drei Stellen in zwei Dateien. Er hält nichts auf, deshalb keine Selbstprobe (`Pruefablauf.md` 6.3); die Anleitung liegt in `tools/steuerung/LIESMICH.md`. |
| E-R4-13 | **Die Diagramme der Statistik folgen vier Regeln:** eine Farbe je Diagramm (Blau), der Höchstwert in Orange **mit** Beschriftung, Schrift nur in Schrifttoken, die Tabelle bleibt daneben; drei Reihen verschiedener Größenordnung sind drei kleine Vielfache, keine Grafik mit drei Farben oder zwei Achsen; kein Kreis, keine Bibliothek (Inline-SVG aus PHP, Balken als HTML). | Konzept (M-R4-24) | `Design.md` 3.1/3.2 (Farbe trägt eine Aussage; Orange nie als Schrift, F-P3-J) und das dataviz-Verfahren (eine Achse, Farbe folgt der Sache, Tabellensicht); die zwei Töne haben die Palettenprüfung bestanden (Mockup-LIESMICH). |
| E-R4-12 | **Ein Backlog-Punkt = ein Paket, außer die Punkte teilen Dateien oder Erzeugnis.** Blöcke sind Reihenfolge, nicht Bündel (wie Runde 3). | Konzept | 26 Pakete für 37 zu bauende Punkte; jedes einzeln abnehmbar, jedes mit Abnahmezahl. Gebündelt sind nur: 36+331 (eine Funktion), 322+334 (eine Ablaufdatei), 318+283 (ein Werkzeugordner), 116+321+297 (drei Bildwerkzeuge, seriell auf der einen Anlage), 271+272+273 (zwei Skripte, eine Zählzeile), 76+259 und 275+323 (ein Demo-Bestand), die Kommentar- und Kleinstpunkte unter `server/` (R4-09). |

### 3.2 Fragen an die Betreiberin

**Alle beantwortet am 26.09.2026.** In der Konzeptsitzung: **Q-R4-02**
(E-R4-07), **Q-R4-04** (E-R4-08), **Q-R4-05** (E-R4-09), **Q-R4-10**
(E-R4-10). Zum Beginn der Umsetzung, einzeln: Q-R4-01 → E-R4-15 (anders als
empfohlen), -03 → -16, -06 → -17, -07 → -18, -08 → -19, -09 → -20, -11 →
-21, -12 → -22, -13 → -23, -14 → -24, -15 → -25 (anders als empfohlen),
-16 → -26. Die Tabelle bleibt als Frage mit Empfehlung stehen.

| Nr. | Frage | Empfehlung | Paket |
|---|---|---|---|
| Q-R4-01 | Die zwei toten Zweige `claude/nice-lovelace-snlo8m` und `claude/pk05-tor-umbauen` löschen (F-R4-04)? | Ja — nichts Ungemergtes; die Umsetzung löscht sie mit `git push origin --delete`, wenn die Antwort ja ist, sonst die Betreiberin auf GitHub. | R4-01 |
| Q-R4-03 | Nr. 37: (a) Seitengrenze 200 in der Zeitraumübersicht über den vorhandenen Baustein „Weitere N anzeigen" (Karte zeichnet weiter alle Pins), (b) die zwei stillen Kappungen (500/120) benennen — in 17? Suchindex-Vorschneiden und Monatsvorwahl nach v1.0? | Ja, (a)+(b) als ein Paket, kein Mockup (Baustein vorhanden); Rest nach v1.0, der Eintrag bleibt danach mit Ziel `nach v1.0`. | R4-17 |
| Q-R4-06 | Nr. 273: Die Dauer im Schnittblock der Startseite ändert sich sichtbar von „1 h 6 min" zu „1h 06min" (und 3599 s → „1h 00min"). Freigabe? | Ja — vierte Änderung gleicher Art nach AP8d; kein neuer Baustein. | R4-13 |
| Q-R4-07 | Nr. 207: E-PK-27 in `tools/` ausführen — Prüfkonten außer `demo@` nach `example.invalid`, Schema-`$id`s auf `urn:`; die Ausnahme für `messstand@gen-em.org` in `textprobe-ausnahmen.json` fällt? | Ja — sie widerspricht E-PK-27. Bestandsdaten (Referenz-edbak) werden in R4-16 ohnehin neu erzeugt; R4-19 läuft davor, damit die Referenz die neuen Adressen trägt. | R4-19 |
| Q-R4-08 | Nr. 283: Gilt E-P1-02 (keine realen Orte) auch für Kommentare — den zitierten alten Platzhalter in `version.php`, die Messbeispiele mit realer Station und Klinik, den realen Rufnamen (F-R4-12)? | Ja, alle umschreiben („ein realer Ort", „eine Station") — die Dateien sind öffentlich, und die Textprobe soll danach ohne Ausnahme grün sein. Die Kommentare unter `server/` sind Beifang in R4-09 (Web Korrektur). | R4-07, R4-09 |
| Q-R4-09 | Nr. 158: `days.created_at` mit Migration in 17 (Rückfall `started_at`, gekappt auf jetzt), Nutzlast unverändert? | Ja — die Fahrplanzeile nennt den Punkt; Preis: `update.php` nach dem Deploy, Prüfstand-Stufe haupt (Plattformmatrix) für dieses Paket. | R4-15 |
| Q-R4-11 | F-SD-08 (GitHub zählt die Backlog-Liste fort: 21, 22, 23 … statt 21, 23, 36) als **Nr. 340** aufnehmen und in 17 beheben? | Ja — der Weg (ein Element zwischen den Einträgen oder die Nummer im Titel) ändert E-SD-16 und die Grammatik in `uebersicht.py`; deshalb ein eigenes Paket, zuletzt (R4-25). | R4-01, R4-25 |
| Q-R4-12 | Umhängen: 232 → `18` (`nur auf Anlass`), 265 → `PK` (`nur auf Anlass`), 202 → `Pflegeaufgabe` (auf Stand gebracht), 161 → `12a`, 92 → `12a`, 198 und 200 → `nach v1.0` (Stand `nicht umsetzen` bleibt)? | Ja — Gründe in 2.2. 198/200 tragen `nach v1.0`, weil das Vokabular kein „nie" kennt und `17` nach der Runde leer sein soll. | R4-01 |
| Q-R4-13 | Nr. 297: Breiten 1200 und 1600 (die zwei ungemessenen Schwellen aus `Design.md` 7) statt 400 und 1366 aus dem Eintrag? Preis rund +12 % Laufzeit je Breite in neben/haupt. | Ja — jede Schwelle einmal knapp darüber messen; 1366 nur, wenn eine Laptop-Breite gewünscht ist. | R4-08 |
| Q-R4-14 | Nr. 239: Weg A — die Backticks um den Spaltennamen in `mf_spalten()` setzen (die eine Stelle, die aus dem Register SQL macht), statt jeden Aufrufer zu ändern? | Ja — deckt alle drei INSERTs und jedes SELECT ab, ohne `ingest.php` anzufassen (18-Liste); `komplett_lib.php` behält seine vier Kopien oder nimmt denselben Weg. | R4-12 |
| Q-R4-15 | Nr. 170 (F-R4-18): Soll der manuelle Abfahrtort in der Leseansicht eine beschriftete Zeile mit Schloss bekommen (heute nur Kartenpopup ohne Zeichen)? | Sollliste zuerst mit dem Ist schreiben; die Frage als **Nr. 341** notieren (`gehört zu: nächste Backlog-Runde`) — eine Oberflächenänderung außerhalb dieser Runde. | R4-06 |
| Q-R4-16 | **Die drei Mockups freigeben** — M-R4-22 (Verwerfen-Knopf mit Rückfrage), M-R4-23 (Zeitraumwahl: Pillen + Von/Bis in einer Reihe über den Reitern), M-R4-24 (Säulen je Woche, Balken je Herkunft, kleine Vielfache)? Bilder und Regeln in `konzept-r4/mockups/LIESMICH.md`. | Ja, wie gezeigt; Änderungswünsche werden vor dem jeweiligen Paket ins Mockup eingearbeitet (H-R4-01). Ohne Freigabe bleiben R4-22 bis R4-24 stehen, die übrigen 23 Pakete laufen. | R4-22, R4-23, R4-24 |

### 3.3 Haltepunkte

- **H-R4-01 Freigabe der Mockups vor R4-22, R4-23, R4-24.** Die Mockups
  liegen beim Konzept (`konzept-r4/mockups/`, Q-R4-16). Gebaut wird erst
  nach der Freigabe (`CLAUDE.md` 5, `Design.md` 1); verlangt die
  Betreiberin Änderungen, zieht die Umsetzung das Mockup nach und legt es
  erneut vor, bevor das Paket beginnt. Die Emulatorbilder der fertigen
  Android-Ansicht gehen nach `android/mockups/bilder/`.
- **H-R4-02 R4-15 ist eine Migration.** Nach dem Deploy muss eine
  Administratorin `update.php` aufrufen; die Kette lässt den Wartungsmodus
  an. Das steht in der Commit-Nachricht, im Changelog und im Prüfdokument —
  ausdrücklich, nicht in einer Fußnote.
- **H-R4-03 R4-16 friert die Vergleichsgrundlage ein.** Zeigt der neue
  Referenzlauf Abweichungen, die nicht Nr. 275 oder 323 sind, oder scheitert
  die Einspielkette: anhalten, berichten. Eine Referenz, die einen
  unverstandenen Zustand einfriert, ist schlimmer als die alte (H-BR3-2).
- **H-R4-04 R4-06 findet ein fehlendes Kennzeichen.** Dann ist das ein
  Fehler der Anwendung (Web Korrektur im selben Paket), keine Ausnahme in
  der Sollliste — so wie Web 19.1.1 zwei Schlösser nachgetragen hat.
- **H-R4-05 Ein Paket an einer Datei der 18-Liste wächst.** Verlangt eine
  Änderung dort mehr als das in 4 Vermerkte, anhalten und fragen, statt zu
  wachsen.

## 4. Arbeitspakete

Reihenfolge = Nummer. Jedes Paket ist einzeln abnehmbar; nach jedem: Statusblock
und Prüfdokument fortschreiben, Zweig pushen (K7), erledigte Nummern nach
`Backlog-Erledigt.md` (E-R4-06). Die Pflichten aus `CLAUDE.md` 2 stehen bei
jedem Paket („Stufe:"). **Prüfmittel zuletzt**, und jede grüne Zahl benennt,
was sie gemessen hat. Das Feld **Fächerung** ist die Zeile nach `CLAUDE.md`
7 — ohne sie wird nicht gefächert.

### Block A — Buchführung und Prüfstand (nur `tools/` und `docs/`)

**R4-01 Buchführung** — Nr. 140, 172, 216, 295, 301 austragen; Umhängen
nach Q-R4-12 und E-R4-08 (232 → 18, 265 → PK, 202 → Pflegeaufgabe, 161
und 92 → 12a, 198 und 200 → nach v1.0, 62 → 13); Nr. 340 anlegen
(F-SD-08); für 301 vorher den einen Satz in `Sandbox-Setup.md` 1
umschreiben; Nr. 202 und 275 im Text auf Stand bringen (F-R4-13, F-R4-05);
Q-R4-01 (Zweige löschen). Entscheiden, ob der Verschiebe-Helfer als
`tools/steuerung/verschieben.py` eingecheckt wird (Anlass: E-R4-06;
LIESMICH, Selbstprobe) — Empfehlung ja.
*Abnahme:* `uebersicht.py --ziel 17` zählt 37 (die zu bauenden), `decken.py`
0 gerissen, `bestand` Regel `backlog` 0.
*Stufe:* keine. *Fächerung:* keine.
**Erledigt 26.09.2026.** Ausgetragen 140, 172, 216, 295, 301 (Belege je
Schlusssatz in `Backlog-Erledigt.md`); umgehängt nach E-R4-08 und E-R4-22;
Nr. 202 und 275 auf Stand (275 nachgemessen, F-R4-21); Nr. 340 angelegt;
`Sandbox-Setup.md` 1 berichtigt (F-R4-20); Spanne 340–349 auf den neuen
Zweig, Zeile 339 gestrichen (gemergt); Rahmenplan Fassung 135 (Beginn,
Zuarbeit Zweige). Q-R4-01: nichts gelöscht (E-R4-15). Helfer eingecheckt
(E-R4-28). *Gemessen:* `uebersicht.py --ziel 17` **43** (F-R4-19 — nicht
37), `--pruefen` 97 Einträge / 0 / 0, `decken.py` 20 / 0 gerissen,
`bestand` 0 Befunde.

**R4-02 Prüfstand: Anlage, Schema, Ausbaustufe** — Nr. 329, 332, 335.
`hochfahren.sh` fragt nach dem Start `migrationen_ausstehend()` und ist bei
Rückstand rot mit der Ansage „`hochfahren.sh --neu`" (kein stilles
Neueinrichten); `pruefen.sh` liest `compileSdk` aus
`android/handy/build.gradle.kts` und prüft `platforms/android-<n>*`;
`aufbauen.sh android` installiert nur noch 37.0; zwei Regeln in
`bestand.py`: `ohne-anlage` (Proben mit `braucht: nichts` erreichen über
ihre `require`-Kette kein `db.php`/`config.php` — 12 PHP-Werkzeuge mit
`require server/…` als Ausgangsmaß) und `riegel-im-tor` (jeder Riegel aus
`pruefablauf.json` steht als `--riegel` in `pruefung.yml`; die Datei wird
gelesen, nicht geschrieben). Selbstproben für beide Regeln; P-BR-09 fällig.
*Abnahme:* Anlage auf altem Schema → rot mit Datei und Ansage; ohne
`android-37.0` → „Ausbaustufe android fehlt"; `bestand` beide Regeln 0,
Selbstprobe je ein roter Fall. *Stufe:* keine. *Fächerung:* keine (drei
Punkte, zwei Dateien).
**Erledigt 26.09.2026.** Die zwei Regeln heißen in `bestand.py` `anlage`
und `tor` — eine Kennung trägt ihre Regel vor dem ersten Bindestrich, ein
Name mit Bindestrich ginge nicht. `anlage` liest die Ladekette mit dem
PHP-Tokenizer und wertet Pfade nur über eine Positivliste aus; ein
`require` im Rumpf einer Funktion zählt nicht (Grenze im Kopf von
`bestand.py`). Die Schemafrage steht in `hochfahren.sh`, nicht in
`pruefen.sh` — dort fahren beide Wege durch, und `--ohne-hochfahren`
bleibt, was es heißt. *Gemessen:* `bestand` 0 Befunde (21 Proben ohne
Anlage, 10 PHP-Einstiege, 18 Dateien in der Ladekette, davon 7 unter
`server/`; 20 von 20 Riegeln im Tor); historischer Fehler nachgebaut
(`doku_lib.php` lädt `db.php`, `anker` fehlt im Tor) → **2 Befunde**
(`anlage-db`, `tor-fehlt`); Selbstprobe **155 / 0**, 93 von 93
Befundstellen (vorher 141 / 85); `hochfahren.sh` mit entfernter
Registerzeile → rot, rc 1, Kennung und Weg; zurück → 0 offen; `pruefen.sh`
mit beiseitegelegter Plattform 37.0 → „Ausbaustufe android fehlt
(Plattform android-37 aus compileSdk)", nicht gemessen, rc 1.
Beifang F-R4-22: `pruefen.sh` verlangt eine Umgebungsvariable nur noch,
wenn der Aufruf sie ohne Vorgabe nennt. **P-BR-09 ist hier nicht fällig**, anders als oben angenommen: R4-02 legt
kein neues Prüfmittel an, sondern erweitert eines. Das erste neue ist
`nummern` (R4-03); dort wird 6.12 befolgt und P-BR-09 abgehakt.

**R4-03 Nummernriegel** — Nr. 339. `tools/steuerung/nummern.py`: neue
Nummern des Arbeitsbaums gegen `origin/main` und alle Remote-Zweige (`git
fetch --prune`, `git show <ref>:docs/Backlog.md` und Erledigt), rot bei
Überschneidung mit Zweig und Nummer; Selbstprobe mit gestellter Doppelung;
Probe `nummern` in `pruefablauf.json` (Muster `steuerung`, `braucht:
nichts`, örtlich — Stufe 1 sieht die Zweige nicht); ein Satz in `CLAUDE.md`
2 Punkt 4 und im Kopf von `Backlog.md`.
*Abnahme:* Eine Nummer, die ein zweiter Zweig trägt, färbt den örtlichen
Lauf rot und nennt Zweig und Nummer; Selbstprobe N / 0. *Stufe:* keine.
*Fächerung:* keine.

**R4-04 Ablaufdatei: Demo-Marke und Android-Proben** — Nr. 322, 334.
`pruefablauf.json` je demo-empfindlicher Probe `demo: true` (aus der
29-Dateien-Liste abgebildet); `pruefen.sh` schiebt vor jeder solchen Probe
die Reset-Marke (`demo_reset_marke_setzen(time())`) und stellt sie am Ende
zurück; `auswahl.py --selbstprobe` kennt das Feld. Vier Android-Proben
(`android-kontraste`, `-farbabgleich`, `-bildmarken`, `-stroeme`) ans Ende
des Musters `android`, alle im Prüfmodus; vorher F-R4-17 klären: Schreibt
`stroeme.py` in den Baum, bekommt es einen Prüfmodus, der vergleicht statt
schreibt.
*Abnahme:* ein Prüfstand-Lauf `haupt` ohne Reset mitten in einer Probe
(Marke im Protokoll); `auswahl.py --abdeckung` 0 ohne Muster; die vier
Proben grün mit Zahl; Baum-Hash vor und nach dem Lauf gleich. *Stufe:*
keine. *Fächerung:* keine.

### Block B — Quelltext- und Bildprüfmittel (nur `tools/` und `docs/`)

**R4-05 Vollständigkeit II** — Nr. 36, 331. In `vollstaendigkeit.py`:
Prüfung „Selektoren" (Klassen aus `querySelector(All)`/`closest`/`matches`/
`getElementsByClassName` gegen `sicher ∪ vermutet ∪ Literal-Optionen ∪
zusammengesetzte Bausteinklassen`, Ausnahmeliste
`vollstaendigkeit-selektoren.md` mit Grund je Zeile); die nicht-literalen
Tonübergaben (39 in 16 Dateien) über Ternär-Auflösung oder eine geschlossene
Werteliste je Baustein; `pruefung_klassen()` liest je Baustein mit
geschlossenem Vorrat die Werte aus dem Quelltext (`ui_zaehler`, `ui_symbol`
Zusatzklassen, `pwq-0…4`, `karte-*`, `blatt-*`). Ausgangsmaß 73 Hinweise /
29 mit Präfix; Zahl danach benennen.
*Abnahme:* 0 Befunde; Gegenprobe: ein erfundener Selektor rot, eine
entfernte Regel rot; Hinweise 73 → N, benannt. *Stufe:* keine, außer die
Prüfung findet einen echten Fehler (dann Web Korrektur im selben Paket).
*Fächerung:* keine (eine Datei).

**R4-06 Kennzeichnungsprobe** — Nr. 170. Neues Prüfmittel
`tools/quelltext/kennzeichnung.php` mit Sollliste `kennzeichnung-soll.md`
(die neun Blob-Felder aus `CLAUDE.md` 4 / `Technik.md` 4.98 — Name,
Geburtsdatum, Alter, Diagnose, Einsatznummer, Adresse und Koordinate,
Beschreibung, Abfahrtort, Notizen — je Feld die Stelle in `einsatz.php`
und `einsatz_form.php`, an der Schloss oder Kartentitel stehen muss; die
Klartext-Freitextfelder mit ihrer Kleinzeile); Anlass-Zeile Nr. 170;
Riegel in `pruefen.sh`, `pruefablauf.json`, `pruefung.yml` **nicht** (PK) —
der Prüfstand übergibt ihn mit `--alle-riegel`, das Tor meldet den
fehlenden Riegel als rot: deshalb `pruefung.yml` um die eine
`--riegel`-Zeile ergänzen und das in der Commit-Nachricht sagen (die Datei
gehört nicht PK, 2.3). Q-R4-15 als Nr. 341 notieren. H-R4-04.
*Abnahme:* N von N Kennzeichen; Gegenprobe: ein entferntes Schloss rot mit
Feld und Datei. *Stufe:* keine (H-R4-04). *Fächerung:* keine.

**R4-07 pysyntax und Textprobe** — Nr. 318, 283. `pysyntax.py` fängt
`SyntaxWarning`/`DeprecationWarning` als Fehler (unter 3.11 kommt die Folge
als `SyntaxError`; beides abdecken), vierter Selbstprobefall; die zwei
Docstrings als Rohzeichenketten. `textprobe.py`: Regeln bekommen ein Feld
`sicht` (`sichtbar` = Vorgabe, `mit_kommentaren`); die Klasse `namen` läuft
in `a` und `b` mit Kommentaren, die vier anderen unverändert; Altbestand
benennen. Die Kommentare unter `server/`, die die volle Namensliste trifft
(F-R4-12), werden in **R4-09** umgeschrieben (Q-R4-08), danach ist die
Textprobe ohne neue Ausnahme grün — R4-07 misst deshalb nach R4-09 noch
einmal.
*Abnahme:* `pysyntax` 63 geprüft, 0 Fehler, 0 Warnungen, Selbstprobe 4/0;
`textprobe` fünf Klassen 0 neue Treffer, 0 ungenutzte Ausnahmen. *Stufe:*
keine. *Fächerung:* keine (ein Ordner, eine LIESMICH).

**R4-08 Kontrast, Stilvergleich, Bilderlauf** — Nr. 116, 321, 297.
(1) `kontrast.py` leitet die Token-Paare aus `style.css` ab und meldet
jedes Paar, das weder in `PAARE` noch in `AUSNAHMEN` steht; `--selbstprobe`
mit den zwei historischen Fehlern (E-AR-09, Web-Hälfte). (2) `stilvergleich`
misst die Seitenprobe je `data-quelle`-Block als eigenes Dokument
(Signatur um die Quelle ergänzt, `geplant.txt` einmal neu); **vor jeder
`style.css`-Änderung der Runde**. (3) Bilderlauf: Breiten nach Q-R4-13,
Rollen `admin` und `support` mit eigenen Prüfkonten (`pruefkonto.php`,
`lokal_einrichten.sh`), Rollbehälter als Kriterium; `Design.md` und
`Technik.md` nennen die neue Breitenzahl. Ausgangsmaß: 560 Bilder, 8
Breiten, `PAARE` 25.
*Abnahme:* (1) Paare gerechnet N, verfehlt 0, Selbstprobe 2/2; (2) fremde
Seite wächst → 0 ungeplant; (3) Bilder N (benannt), Überlauf 0, Knöpfe
falscher Höhe 0, zwei neue Rollen mit Bild. *Stufe:* keine, außer ein Paar
liegt unter AA (dann Web Korrektur am Stylesheet, `Design.md` nachziehen).
*Fächerung:* keine — drei Werkzeuge, aber je Werkzeug ein Lauf auf der
einen Anlage.

### Block C — Server (Web-Stufen; Pakete an Dateien der 18-Liste bleiben klein)

**R4-09 API-Ausgang, Fehlerwege, Kommentare** — Nr. 258, 277, 291, 266,
175, 150, 260, 333, dazu die Kommentare aus Q-R4-08. `json_out()` in den
drei `api/`-Dateien (19 Stellen, keine Logikzeile — 18-Liste);
`einstellungen.php` Freigabe-Handler: `frei.ok` lesen, bei Fehlschlag
Fertig-Meldung mit Ton `warn` statt Wurf (18-Liste, andere Stelle);
`install.php` Block 348–394: `SHOW TABLES` vor `run_sql_file()`, `try`
teilen (18-Liste, Block davor); `plattform_lib.php` dreiwertiger Helfer
`plattform_funktion()` — `null` heißt „nicht messbar", nicht „aus";
`edbak_uebersicht()` samt Docblock streichen, zwei Kommentare und
`Technik.md` in die Vergangenheit; Kommentarzeilen in `jobs.php`,
`wartung_lib.php`, `adminbackup_lib.php`, `style.css`, `version.php`,
`geo.js` berichtigen. `Technik.md`, Register `tools/zaehlung/register.php`
(Z-Zeile JSON-Ausgang).
*Abnahme:* `grep -rln "echo json_encode" server/api/` 0; Freigabeprobe mit
einem gescheiterten Einlösen (neuer Fall): Meldung `warn`, Daten
eingespielt; Einrichtung gegen eine Datenbank mit Tabellen → Abbruch mit
Zahl vor dem ersten `CREATE`; Statusseite auf lima-city: „nicht messbar";
`grep -rn edbak_uebersicht server/ docs/Technik.md` 0; Textprobe 0 neu.
*Stufe:* Web Korrektur. *Fächerung:* keine — ein Paket, ein Changelog-Ton.

**R4-10 Kontolöschung über eine Stelle** — Nr. 299. `konto_loeschen()`
bekommt den Weg als Parameter (`'verwaltung'` mit Entscheidung über die
Backups); `admin_user.php` `user_delete` ruft sie; `demo_entfernen()`
entscheidet die Umsetzung (dritte Stelle, F-R4-09) — Empfehlung: ebenfalls
über `konto_loeschen()`; Rollenprobe prüft das Protokolldetail `weg`.
18-Liste (`admin_user.php`, `konto_lib.php`): nur dieser Zweig.
*Abnahme:* `grep -rn "DELETE FROM users" server/` 1 (in `konto_lib.php`);
Rollenprobe grün mit dem Fall; Protokoll nennt den Weg. *Stufe:* Web
Korrektur. *Fächerung:* keine.

**R4-11 Umleiten nach POST** — Nr. 250. Elf Seiten (F-R4-10, ohne
`betrieb_server.php` — 18): je POST-Zweig (a) Zustandsänderung →
`flash_setzen()` + `Location` + `exit`, (b) Download bleibt, (c)
Ergebnisanzeige (Restlisten wie `sichern_auswahl`) → Flash mit Zahl;
Rollenprobe fährt jede Seite mit `FOLLOWLOCATION => false` und erwartet
302. Handbuch, wo eine Seite ihr Verhalten beschreibt.
*Abnahme:* die Schleife aus 2.2 liefert 1 (`betrieb_server.php`);
Rollenprobe N Zweige mit 302; Neuladen nach POST wiederholt nichts
(Bedienprobe je Seite). *Stufe:* Web Korrektur. *Fächerung:* **ja** — die
elf Dateien in drei Gruppen auf drei Agenten (getrennte Dateien); Rollen-
und Bedienprobe seriell danach.

**R4-12 Backticks über `mf_spalten()`** — Nr. 239 (Q-R4-14). Die Backticks
um den Spaltennamen in `mf_spalten()`; die drei INSERTs nutzen sie damit,
`komplett_lib.php` nimmt denselben Weg oder behält seine Kopien (sagen,
welches); `tools/spaltenregister/pruefen.php` kennt die Form; Registerzeile.
*Abnahme:* Kreisläufe `edbak` und `csv` 0; Wiederherstellungsprobe grün;
`grep -rn "implode(',', \$cols)" server/` 0. *Stufe:* Web Korrektur.
*Fächerung:* keine.

**R4-13 Meldungen und Dauer im Browser** — Nr. 271, 272, 273 (Q-R4-06).
`schneiden.js`: Hülle `<div data-vorher hidden>`, eine Funktion setzt
`EdHtml.meldung(ton, text)` — `fehler` für Gründe und Serverfehler, `info`
für den Erklärtext; `unlock.js`: `<div data-msg hidden>` mit
`EdHtml.meldung()`, Kommentar berichtigt; `html.js` in die Immer-Liste von
`ui.php` (sieben Krypto-Seiten laden es heute je einzeln); `dauer()` weg,
beide Aufrufe auf `EdFormat.dauer()`, `dauer` in `ZH_FORMATIERER`; Z34- und
Z37-Zeilen im Register. Ein Bilderlauf-Eintrag, der den Schnittblock mit
Segmenten zeigt (heute keiner).
*Abnahme:* Register Z37 sinkt um 2, Z34-Grund gekürzt; Bilderlauf zeigt
Schnittblock mit Symbol und rotem Grund; „1h 06min" im Bild. *Stufe:* Web
Korrektur. *Fächerung:* keine.

**R4-14 Demo-Reset mit Änderungsmarke; GPX-Probe resetfest** — Nr. 76
(E-R4-10), 259. Marke `demo_geaendert` in `app_state`, gesetzt in
`auth_guard.php` (POST des Demo-Kontos) und `ingest.php` (angenommener
Upload) — je eine Zeile, 18-Liste; `demo_reset_wenn_faellig()` prüft die
Marke, täglicher Pflichtreset; Banner (`ui.php`, `admin_demo.php`),
Handbuch, `Technik.md` 4.99a, Runbook. GPX-Probe Teil 2 hält an Typ und
`started_at` (Datum + Ortszeit) statt an der ID, Mehrdeutigkeit als eigene
Erwartung; Anlass-Zeile Nr. 259.
*Abnahme:* Demo ohne Änderung → kein Reset in 30 min, mit POST → Reset
fällig (Zweitfaktorprobe Teil 6 grün); GPX-Probe nach erzwungenem Reset
95 / 0 (heute 4 von 95 blind). *Stufe:* Web Korrektur (Neben, falls das
Banner eine neue Aussage bekommt). *Fächerung:* keine.

**R4-15 `days.created_at`** — Nr. 158 (Q-R4-09, H-R4-02). Migration nach
dem Vorbild von `missions.created_at` (Spalte, Rückfall `started_at`
gekappt auf jetzt, im `skip`-Register), aber mit `db_hat_spalte()`/
`db_spalte_nullbar()` statt `information_schema` (R83); `schema.sql`; die
vier `INSERT INTO days`; `ingest_tag_offen()` fragt den Tag; Rückweg
`backup_lib.php` setzt `created_at` wie der Rückfall; Nutzlast bleibt 12;
Ingestprobe Teil 9 mit spät nachliefernder Uhr; `Technik.md` 4.99a2 und
Datenmodell. 18-Liste (`ingest.php`, `schema.sql`, `migration_lib.php`):
nur diese Stellen.
*Abnahme:* Migrationsregister beide Seiten; Plattformmatrix (Stufe haupt)
viermal grün; Ingestprobe grün mit dem neuen Fall; Kreisläufe 0.
*Stufe:* **Web Neben, Migration — `update.php` nach dem Deploy.**
*Fächerung:* keine.

**R4-16 Referenzbestand: Nachtdienst, Fixture und Referenz neu** — Nr. 275,
323 (H-R4-03). Ein luftgebundener Nachtdienst als D22 (Einsätze vor und
nach Mitternacht, keine Zeitumstellung), Matrixzeile, `__TAG_NACHT__` in
`aufnehmen.mjs`/`seiten.json`, Bedienprobe-Weg „Sortierung nach Beginn";
dann einmal: Anlage frisch, Referenz-edbak im Browser, Fixture mit
`fixture/erzeugen.php`, CSV-Referenzexport neu; die zwei Ausnahmeregeln
streichen mit Satz in `beschreibung`; LIESMICH-Zahlen; Runbook-Zeile
`Technik.md` 11228; Eintrag 275 berichtigt (2 von 20). Nach R4-19 (neue
Prüfadressen) und R4-15 (neues Schema), damit alles nur einmal entsteht.
*Abnahme:* `quelldaten/pruefen.py` Matrix vollständig; Kreisläufe `edbak`
und `csv` **0 unerklärte Abweichungen und 0 ungenutzte Regeln**; GPX-Probe
grün; Bilderlauf und Stilvergleich mit dem neuen Tag ohne Ungeplantes.
*Stufe:* Web Korrektur (Fixture liegt unter `server/`; Neben, falls die
Demo-Fassung im Handbuch eine Zahl ändert). *Fächerung:* keine.

**R4-17 Zeitraumübersicht: Seitengrenze und benannte Kappungen** — Nr. 37
(Q-R4-03). `seite: 200` über `EdMissionTable` in `zeitraum.php` (Kopfzeile
bleibt Summe, Karte zeichnet alle Pins — sagen, dass das so ist); die zwei
stillen Kappungen (500/120) benennen wie im Verschiebe-Dialog; Handbuch;
Messstand-Schritt mit 4 000 Einsätzen als Zahl. Eintrag danach mit Ziel
`nach v1.0` (Vorschneiden, Monatsvorwahl).
*Abnahme:* Messstand Zeitraum unter 5 s bei 3 983 Einsätzen (heute 42,61 s);
Bilderlauf `zeitraum.php` mit „Weitere anzeigen". *Stufe:* Web Neben.
*Fächerung:* keine.

### Block D — Doku, Werkzeuge, Android, Oberfläche

**R4-18 Rollenmatrix der BetreiberIn-Seiten** — Nr. 327. `Technik.md` 4.99p:
sieben GET- und 26 POST-Zeilen (`betrieb_server/jobs/updates/status/
sicherheit/statistik`, `schluesselblatt_pruefen`), `{support}`-Zeilen; die
Rollenprobe misst sie (403/403/403/200 bzw. durch). Nur lesend an Dateien
der 18-Liste.
*Abnahme:* Matrix N Zeilen (heute 70), Rollenprobe N / 0; Gegenprobe: eine
Zeile mit falschem Sollwert rot. *Stufe:* keine, außer die Messung findet
eine Handlung vor dem Tor (dann Web Korrektur, sofort). *Fächerung:* keine.

**R4-19 Prüfadressen nach `.invalid`** — Nr. 207 (Q-R4-07). E-PK-27 in
`tools/` ausführen: Prüfkonten außer `demo@` nach `example.invalid` (41
Dateien), Schema-`$id`s auf `urn:`, `messstand@`-Ausnahme streichen,
LIESMICH-Anleitungen; `.github/` bleibt (drei Kommentare zu echten
Adressen). Vor R4-16.
*Abnahme:* `grep -rn "gen-em\.org" tools/ | grep -v demo@` 0; Textprobe
Regel `adressen` 0 ohne die gestrichene Ausnahme; Proben, die Konten
anlegen, grün. *Stufe:* keine. *Fächerung:* **ja** — die Ersetzung je
Werkzeugordner auf Agenten (getrennte Dateien); Proben und Textprobe
seriell danach.

**R4-20 Karten „Was hier gilt"** — Nr. 287. Drei Karten und der Untertitel
raus (`import.php`, `einsatz_form.php`, `wiederherstellen.php`); der Text
ans Ende des Handbuchkapitels (7, 4.3, 12.6) mit Sprungmarke; je Seite ein
Satz mit Verweis (E-P5c-06). Schloss und Kleinzeile bleiben (E2E-Zusage
unberührt); `Design.md` 9 nachziehen.
*Abnahme:* `grep -l "Was hier gilt" server/*.php | grep -v version.php` 0;
Riegel `anker` 0; Bilderlauf der drei Seiten. *Stufe:* Web Korrektur.
*Fächerung:* keine.

**R4-21 Android: Eingabefeld und Rundlauf** — Nr. 336, 95. `Eingabefeld()`
samt Kopfkommentar aus `Bausteine.kt`, Paar „Cursor" aus `kontraste.py`,
zwei Kommentare geprüft; Rundlauffälle räumen über die Anwendungswege ab
(`spur_loeschen()` je Einsatz und Ruhesegment, dann `missions`,
`rest_segments`, `days` — Muster `$aufraeumen` in `tools/proben/gpx/`), die
Kopplungshilfe merkt sich die `day_ref`s des Laufs; `mariadb`-CLI-Weg
bleibt für die drei Kopplungstabellen oder geht denselben Weg (sagen,
welches). Emulator nach `Pruefablauf.md` 6.9.
*Abnahme:* `./gradlew build` 0 Lint-Fehler, 0 Fehlschläge; Rundlauf gegen
die örtliche Anlage: danach 0 Diensttage, 0 Einsätze, 0 Spurpunkte im
Konto 1; `kontraste.py` 0 Befunde, Selbstprobe 5/5. *Stufe:* Android
Korrektur. *Fächerung:* keine.

**R4-22 Android: Abgewiesene verwerfen** — Nr. 114 (E-R4-09, H-R4-01).
Nach der Freigabe von **M-R4-22**: in `DienstAnsicht.kt` unter der roten
Zustandszeile ein neutraler Knopf „Abgewiesene verwerfen …" (ohne Zahl —
die steht in der Zeile darüber), Rückfrage über `Rueckfrage()` wie „Gerät
trennen" mit `<plurals>` im Titel („3 abgewiesene Pakete verwerfen?"),
Grund und Folge im Text, „Verwerfen" in Rot tief, „Behalten"; ruft
`puffer.abgewieseneRaeumen(null)`; danach die Quittung „N Pakete verworfen"
als Ergebniszeile; Prüffall; Emulatorbilder; `docs/Geraete-Eingabe.md`,
`android/LIESMICH.md`, Handbuch 10.
*Abnahme:* Prüffall grün; Bild mit Rückfrage; nach „Verwerfen" Zahl 0.
*Stufe:* Android Neben. *Fächerung:* keine.

**R4-23 Statistik: freier Zeitraum** — Nr. 122 (a) (E-R4-07, H-R4-01).
Nach der Freigabe von **M-R4-23**: eine Reihe `.zeitraumwahl` zwischen
Kennzahlen und Reitern — die vier festen Fenster als Listenfilter-Pillen,
daneben Von/Bis (`.feld-eingabe type=date`, Muster `suche.php`) und
„Anwenden" (mit Skript beim Verlassen des zweiten Feldes, ohne Skript der
Knopf); ein eigener Zeitraum steht als Pille mit Kreuz (9.18a). Tagesgrenzen
in Europe/Berlin, `stat_…`-Abfragen mit Ober- und Untergrenze über den
Index `missions(started_at)`; bei eigenem Zeitraum eine einspaltige
Zeitraumtabelle mit Wochen- und Tagesschnitt, Kennzahl und Kartenzahl „im
Zeitraum"; Rollenmatrix-Zeile; Messstand-Schritt `statistik` mit freiem
Zeitraum; Handbuch 12.2; `Design.md` 9 (Vorrat: `.zeitraumwahl` ist eine
Anordnung vorhandener Bausteine, kein neuer).
*Abnahme:* Messstand drei Reiter mit freiem Zeitraum unter 1 s bei 5 000
Einsätzen, `EXPLAIN` mit Index; Bilderlauf; Rollenprobe. *Stufe:* Web
Neben. *Fächerung:* keine.

**R4-24 Statistik: Diagramme als Inline-SVG** — Nr. 122 (b) (E-R4-07,
E-R4-13, H-R4-01). Nach der Freigabe von **M-R4-24**: Baustein „Diagramm"
in zwei Formen — Säulen je Woche als Inline-SVG aus PHP
(`.diagramm-saeulen`: eine Farbe, Höchstwert orange mit Zahl, Gitter,
`:hover`-Zahl ohne Skript) für „Einsätze je Zeitraum" und „Geräte je
Zeitraum", Anteile als HTML-Balken (`.diagramm-balken`) für die Herkunft,
„Konten je Zeitraum" als drei kleine Vielfache je Monat; alles aus den
schon gerechneten Zahlen (`statistik_lib.php` liefert je Woche bzw. Monat
eine Zahl dazu), keine Bibliothek, keine fremde Quelle — `Lizenzen.md`
bleibt unberührt; Farben nur über Token, Kontrast gegen Schnee/Rauch
(`kontrast.py` mit den neuen Paaren); die Tabelle bleibt neben jedem
Diagramm. Am Fingergerät unter 480 px: Achsentext über `.nur-breit` weg
oder halbes Fenster — die Umsetzung entscheidet nach dem Bilderlauf.
Baustein „Diagramm" in `Design.md` 9; Bilderlauf in allen Breiten;
Handbuch 12.2. Der Eintrag 122 ist danach erledigt.
*Abnahme:* Bilderlauf ohne Überlauf in 10 Breiten; `kontrast.py` 0
verfehlt; Rollenprobe; Handbuch. *Stufe:* Web Neben. *Fächerung:* keine.

**R4-25 Steuerung: Erzeuger als Riegel, Liste auf GitHub** — Nr. 209, 340
(Q-R4-11). Zuletzt, weil der Erzeuger nach der letzten Änderung an
`ui.php`/`style.css` läuft: `python3 tools/erzeugen/design.py alle`,
gegenlesen; Regel `design` in `bestand.py` (Erzeuger im Speicher gegen die
Datei, wie `tabelle`), Selbstprobe; Nr. 340: die Backlog-Einträge als
getrennte Listen (ein Element zwischen den Einträgen oder die Nummer im
Titel — E-SD-16 und die Grammatik in `uebersicht.py`/`decken.py` ändern;
das Prüfmittel `cmark-gfm` zählt danach `<ol start>` je Eintrag).
*Abnahme:* 49 von 49 Zeilen der Bausteintabelle stimmen; `bestand` Regel
`design` 0, Gegenprobe (eine Zeile in `ui.php` eingefügt) rot; GitHub
zeigt jede Nummer wie im Markdown (Probe mit `cmark-gfm`: N Listen, jede
`start=` gleich der Nummer). *Stufe:* keine. *Fächerung:* keine.

**R4-26 Abschluss** — beide Kreisläufe (R24), Doku-Konsistenz (Handbuch,
Technik, Design, Pruefablauf 6.11 Zahlen, LIESMICH der Werkzeuge),
Changelog gegengelesen, `uebersicht.py --ziel 17` **0 Einträge**, Prüfdokument
vollständig (Nicht-Prüfbares zuerst), Statusblock; nach der Freigabe:
Erledigt-Zeile, Verlaufszeile, Konzept löschen (6).
*Abnahme:* Kreisläufe 0/0, Prüfstand `haupt` 0 rot, Stufe 1 grün, kein
Punkt mit Ziel 17. *Stufe:* keine eigene. *Fächerung:* keine (erzählender
Text).

## 5. Was bei jeder Codeänderung mitläuft

`CLAUDE.md` 2, je Paket: Versionsstufe nach Berührung (Web in
`server/version.php` mit Kopfabsatz; Android in `android/version.properties`;
keine bei `tools/` und `docs/`), `docs/CHANGELOG.md` mit Begründung,
Dokumentation nachziehen (entfernte Funktionen austragen), Backlog:
erledigte Punkte wörtlich nach `Backlog-Erledigt.md` mit Schlusssatz
(E-R4-06), Rahmenplan nur an den vier Anlässen. Die Prüfmittel laufen
zuletzt; der Prüfstand (`tools/pruefstand/pruefen.sh`) erzeugt den Bericht
für die Commit-Nachricht; nach einem fremden Merge `Pruefablauf.md` 5.3.

## 6. Abschluss

Nach der Freigabe des Abschlusses (K9): Erledigt-Zeile in Rahmenplan 8
(eine Tabellenzeile, Prüfzahlen im Prüfdokument), Reste nach Abschnitt 6,
Backlog in die Kopfzeilen, Verlaufszeile, dieses Konzept löschen. Das
Prüfdokument bleibt, bis seine Prüfliste abgehakt ist.

## 7. Quellen

`docs/Rahmenplan.md` 3 (Fahrplanzeile 17), `docs/Backlog.md` (Kopfzeilen
`gehört zu: 17`), `tools/steuerung/uebersicht.py`, Konzept SD (E-SD-35,
E-SD-38, E-SD-39, F-SD-06, F-SD-08; gelöscht `831e3e7`), Konzept BV
(E-BV-19, E-BV-20; gelöscht `8e29cf0`), Konzept AR (E-AR-12: Nr. 334, 335;
gelöscht `956c370`), `Konzept-Backlog-Runde-3.md` (gelöscht `f6cb5fd`,
24.09.2026 — nicht `5e501ae`, wie die Erledigt-Zeile im Rahmenplan sagt;
letzte Fassung `git show f6cb5fd^:docs/konzepte/Konzept-Backlog-Runde-3.md`,
689 Zeilen; Vorlage für Form und Paketschnitt), Konzept PK (PK-06 bis
PK-08, E-PK-27), `docs/Pruefablauf.md` 4, 5, 7.
