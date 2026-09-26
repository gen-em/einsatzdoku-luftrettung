# Prüfdokument BV — Vorgriff auf Backlog-Runde 4

*Gehört zu `Konzept-BV-Backlog-Vorgriff.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Stand: BV-01 bis BV-08 gebaut, P-BV-02 und -03 gegengeprüft,
P-BV-05 erledigt; gemergt 26.09.2026 (PR #87, `d34908b`) — **vor** AR, das
den BV-Zweig aufgenommen hat (`af9df30`); P-BV-08 damit überholt. Q-BV-05
und -06 sind entschieden (E-BV-19, -20), der Abschluss nach K9 ist auf dem
Zweig von Schritt 17 geschrieben, das Konzept gelöscht. Dieses Dokument
bleibt, bis P-BV-04, -07 und -09 abgehakt sind.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Stufe 1 auf GitHub für diesen Zweig** | Bis zum Pull Request lief sie nicht — Stufe 1 läuft auf Arbeitszweigen nur beim PR (Nr. 281). Örtlich sind alle 17 Riegel je Paket grün, und `bericht.py lesen` hat jeden Kopf-Commit gegengelesen („in Ordnung, 19 Zahlen"). Ob das Tor dasselbe sagt, zeigt erst der PR-Lauf. **Stand 26.09.2026:** Über den Stand vor dem Aufnehmen, `f67c483`, lief er grün (Lauf 36180954206, 25.09.2026, dazu beide Schemaläufe); über den vereinigten Stand läuft er erst mit dem Push des Merge-Commits. | PR-Lauf von `pruefung.yml`, P-BV-01 |
| ~~**Der Abtaster gegen den P5c-Stand nach dem Merge**~~ | ~~Gefahren ist er gegen einen Abzug von P5c `3576a97` … Was P5c bis zum Merge noch an `server/` baut (AP6 bis AP9), kann niemand vorher messen.~~ **Gemessen am 26.09.2026** am vereinigten Stand: **0 Befunde** (Abschnitt 2, F-BV-15). Örtlich erfüllt; das Tor sagt es mit P-BV-01. | P-BV-01 |
| ~~P-BV-08~~ | ~~**Nach dem Merge von AR** (PR #88): BV nimmt `main` ein zweites Mal auf, nach `Pruefablauf.md` 5.3.~~ **Überholt 26.09.2026:** Die Betreiberin hat BV vor AR gemergt (PR #87 `d34908b`, dann PR #88 `f5bddc2`); AR hat den BV-Zweig aufgenommen (`af9df30`), Stufe 1 war grün. Nichts mehr zu tun. | Konflikte nur in `docs/Backlog.md` (Kopf, Ende *Offen*, Ende *Erledigt*) und `docs/CHANGELOG.md` (oben), alle additiv; AR-Einträge vor denen von BV, im Changelog BV oben (E-BV-14); keine Nummer doppelt; Stufe 1 grün. | Ein Konflikt in einer anderen Datei, eine Nummer doppelt, oder der PR zeigt nach dem Merge von AR „This branch has conflicts" und niemand nimmt auf. |
| P-BV-09 | **Den Textbaustein neu übernehmen** — nur, wenn er schon in der eigenen Datenschutzerklärung steht (hängt an P-P5c-40): Handbuch 11.5a öffnen, den Baustein kopieren, unter Verwaltung → Rechtstexte → Datenschutzerklärung den alten Abschnitt „Welche Daten verschlüsselt gespeichert werden" ersetzen. | Die Erklärung nennt den Abfahrtort unter „verschlüsselt" und die Höhe des Einsatzorts sowie die Koordinate des Transportziels unter „nicht verschlüsselt". | Die Vorschau zeigt „das Transportziel," ohne „samt Koordinate" — dann steht noch die alte Fassung dort. |
| **Das zweite Aufnehmen nach dem Merge von AR** | AR (PR #88) ist noch nicht gemergt und nimmt `main` selbst noch auf. Gemessen ist nur ein Probe-Merge gegen den heutigen Stand von AR, `83106eb` (F-BV-16): Konflikte nur in Backlog und Changelog, alle additiv. Was AR beim eigenen Aufnehmen noch ändert, sieht erst das zweite Aufnehmen. | P-BV-08 |
| **Eine eingebaute Selbstprobe der Vollständigkeitsprüfung** | Sie hat keine (`pruefen.sh`, Liste `SELBST`: „ein Rest, den E-PK-24 … erst noch einlöst"). Eine anzulegen hieße, `pruefen.sh` und `tools/quelltext/LIESMICH.md` zu ändern — beide hat P5c geändert. Die Abnahme von BV-01 ist deshalb von Hand gefahren und in Abschnitt 2 mit Zahl belegt, die Gegenproben sind nicht eingecheckt. | — |
| **Ob jeder Ersatz in der Streichliste genau stimmt** (BV-03) | Der entfernende Commit ist gemessen, der **Ersatz** aus dem Diff gelesen — eine Deutung. **Die Gegenprüfung hat gezeigt, wie berechtigt dieser Vorbehalt war:** 13 von 25 Zeilen trugen nicht oder nur teilweise (F-BV-12). Nach der Berichtigung beruhen sie auf den Belegen des Gegenprüfers, von mir stichprobenartig nachgemessen, **nicht jede einzeln** — und das Laufzeitverhalten der `c-dc-*`-Klassen ist gelesen (`missiontable.js`), nicht im Browser gesehen. | P-BV-02 |
| **Ob die 5 verbliebenen Symbolzeichen richtig sind** | Es sind Malzeichen im sichtbaren Text („3× …"); Nr. 279 hat sie am 23.09.2026 einzeln als Typografie gelesen. BV hat sie nicht noch einmal im Satz gelesen, nur gezählt und die Fundstellen notiert. | — |

---

## 2. Maschinell geprüft

| Paket | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| BV-00 | Durchsicht mit neun lesenden Agenten, jeder Kandidat von einem Gegenprüfer mit dem Auftrag, eine Kollision nachzuweisen | 119 offene Backlog-Nummern gegen den Dateiumfang von P5c (gebaut und geplant) | 90 ausgeschieden, 29 gegengeprüft: **7 geeignet, 18 bedingt, 4 ungeeignet** |
| BV-00 | `grep -oE '^[0-9]+\.' docs/Backlog.md \| … \| uniq -d` | keine Nummer zweimal nach dem Eintrag der Spanne | **leer** |
| BV-01 | `pruefen.sh vollstaendigkeit` vorher | Ausgangsmaß | **0 Befunde**, 64 Hinweise Klassen, 14 Unicode, 8 Emoji |
| BV-01 | Vergleich alter gegen neuen Abtaster über alle Quelldateien (Skript im Scratchpad, nicht eingecheckt) | geleerte Zeilen je Datei; Länge der Ausgabe gleich der Eingabe | 179 Dateien, **47 555 → 47 856** geleerte Zeilen, 21 Dateien anders; `einsatz_form.php` **851 → 877**; jede Ausgabe gleich lang |
| BV-01 | derselbe Vergleich zeilenweise, jede abweichende Zeile gelesen | was nur der alte leerte, was nur der neue leert | nur alt: **7** Zeilen, alle Fehlgriffe (`http://` im HTML, `#` in `href`, `&#…;`, `/^#/` in JS, eine URL in einer JS-Zeichenkette); nur neu: **368** Zeilen, Stichprobe über alle Zeilenanfänge — Kommentare |
| BV-01 | `pruefen.sh vollstaendigkeit` nachher | die drei Zusagen | erst **1 Befund** (`export.js:1110`, GPX-Namensraum, bis dahin verschluckt), nach dem Eintrag in `vollstaendigkeit-zusagen.md` **0**; Ausnahmen „fremde Quelle" 18 → 19 |
| BV-01 | Gegenprobe: `<a href="#" onclick="return confirm('GEGENPROBE-A')">` in `einsatz_form.php` eingeschleust, danach zurückgesetzt | findet die Zusage „native Dialoge" die Stelle? | alt **3**, neu **4** Treffer (2 Ausnahmen + 2 eingeschleuste; die zweite, in `export.js`, fanden beide) |
| BV-01 | `vollstaendigkeit.py` aus BV auf einem Abzug von P5c `3576a97`, Ausnahmeliste zusammengeführt | wird der Merge rot? | **0 Befunde**; mit P5c-eigener Fassung ebenfalls 0 |
| BV-01 | Symbolprüfung vorher/nachher, jede weggefallene Fundstelle gelesen | Unicode und Emoji in Kommentaren | Unicode **14 → 5**, Emoji **8 → 0**; 17 von 17 weggefallenen in Kommentaren |
| BV-01 | `python3 -m py_compile` | Übersetzbarkeit | ok |
| BV-01 | Prüfstand `pruefen.sh`, Stufe klein, Baum `a49362e` | 17 Riegel und Proben der Berührung | **17 grün, 0 rot, 0 nicht gemessen**, 59 s; `bericht.py lesen` „in Ordnung, 19 Zahlen" |
| BV-02 | `vollstaendigkeit.py` | Tonlisten, zusammengesetzte Klassen | Töne PHP = JS, **5**; Hinweis „im Markup nicht gefunden" **64 → 62**; **0** Befunde |
| BV-02 | Gegenprobe auf einer Kopie: `.meldung-schutz` umbenannt, Ton `neu` nur in `MELDUNG_SYMBOLE` | melden die neuen Befunde? | **3** neue Befunde (Listen verschieden 1, Klasse ohne Regel 2), dazu der bestehende am Aufruf |
| BV-02 | alte gegen neue Fassung, je Ton eine Kopie ohne dessen Regel | hätte die alte Fassung eine fehlende Regel übersehen? | **nein** — die Aufrufprüfung meldet in beiden: ok 17, info 23, warn 21, fehler 21, schutz **1** Treffer (F-BV-10) |
| BV-02 | `vollstaendigkeit.py` auf dem P5c-Abzug `3576a97` | wird der Merge rot? | **0** Befunde, Hinweis 74 → 72 |
| BV-02 | Prüfstand, Stufe klein, Baum `a836729` | 17 Riegel und Proben der Berührung | **17 grün, 0 rot, 0 nicht gemessen**, 36 s; Gegenlesung „in Ordnung" |
| BV-03 | `git fetch --unshallow` | Geschichte vor dem 05.09.2026 | **381 → 1 040** Commits |
| BV-03 | Anwesenheit je Klasse über 162 Commits von `main` (erste Eltern) und 25 Commits des P3-Zweigs, getrennt nach Stylesheet und Markup (`git grep -P`, Skript im Scratchpad) | wann verschwand die letzte Verwendung? | Stylesheet: alle 25 in O1 (`ecd5ff98`); Markup: je Klasse ein Commit aus O2 bis O9c, `c-dc-*` nie als Literal |
| BV-03 | Diff des entfernenden Commits, je Klasse gelesen, dazu die Abschnitte O4, O7, O9a bis O9c im P3-Konzept | wodurch ersetzt? | **25 von 25** rekonstruiert, **0** „nicht feststellbar"; **4** berichtigt auf `[bleibt]` (`c-dc-*`, zur Laufzeit gesetzt) |
| BV-03 | `vollstaendigkeit.py` | Streichliste nach dem Umbau | Platzhalter „Ersatzlos entfallen … Gemessen am 22.09.2026" **25 → 0**; **0** Befunde; `[bleibt]` maschinell erkannt (4 neue Zeilen beginnen mit dem Vermerk) |
| BV-03 | Prüfstand, Stufe klein, Baum `46856cc` | 17 Riegel und Proben der Berührung | **17 grün, 0 rot, 0 nicht gemessen**, 36 s; Gegenlesung „in Ordnung" |
| BV-04 | `git diff ba2ec57 3576a97 -- docs/Handbuch.md docs/Technik.md README.md`, Hunk-Anfänge gegen die geänderten Zeilen | trifft BV-04 eine Stelle, die P5c auch ändert? | Handbuch: P5c-Hunks ab Zeile 283, BV bis 30 und 1941–2030; Technik: nächster P5c-Hunk 3 Zeilen vor der Zeile `missions`, sonst mehr als 100 Zeilen Abstand; README: P5c **0** Hunks |
| BV-04 | `grep` nach `php …server/jobs.php` in `docs/`, `README.md`, Anleitungen | bleibt ein Befehl mit Repositoriumspfad? | **2** Treffer, beide gewollt: die neue Erklärung in 4.97a und der Fund-Eintrag Web 15.5.2 |
| BV-04 | `pruefen.sh handbuch`, `linkprobe`, `textprobe` | rendern die Dokumente, stimmen die Verweise, neue Wortlisten-Treffer? | **2** Dokumente / 0 Befunde; **122** Verweise / 0 Abweichungen; **0** Treffer außerhalb der Ausnahmen |
| BV-04 | Prüfstand, Stufe klein, Baum `5dddabc` | 17 Riegel und Proben der Berührung | **17 grün, 0 rot, 0 nicht gemessen**, 37 s; Gegenlesung „in Ordnung" |
| BV-05 | `hochfahren.sh --neu`, dann `proben.sh wiederherstellung` | Nr. 212 auf frischer Anlage | **111 Erwartungen, 0 nicht erfüllt**; Teil 10 „2 erledigt, 2 von 4 offen", Zeiger `cur=2`, Wiederaufnahme `2 -> 17` |
| BV-05 | GitHub-Schnittstelle, Läufe von `pruefung.yml` je Zweig | Nr. 281: läuft Stufe 1 auf Arbeitszweigen nur beim Pull Request? | BR-Zweig **3** Läufe, alle `pull_request`; BV-Zweig nach 4 Pushes ohne PR **0** |
| BV-05 | `grep` in `.github/workflows/` nach Uhr-Prüfstand, ConnectIQ, `monkeyc`, `actions/cache` | Nr. 222: baut die Kette die Uhr noch? | **0** Treffer; Kopf von `pruefung.yml`: „Android und Uhr baut Station B" |
| BV-05 | `bestandPruefen()` in `import_ui.js` und `EdApi.postJson()` in `api.js` gelesen, `git log -S` | Nr. 270: gilt eine 500 noch als Erfolg? | nein — `ok` verlangt `antwort.ok`; seit `0bee2fb` (Web 20.34.0) |
| BV-05 | offene Nummern im Backlog, `origin/main` gegen den Zweig; Nummern doppelt | Buchführung | **115 → 108** (8 ausgetragen: 40, 184, 212, 214, 222, 270, 274, 281; neu 329); doppelt **0** |
| P-BV-02 | Gegenprüfung durch eine unabhängige Instanz: `git grep` je Klasse als ganzes Token auf `<commit>^`, `<commit>`, `HEAD`; `git log -S` bis `HEAD`; Diffs an den Ersatzstellen gelesen | alle 25 Zeilen der Streichliste mit „Herkunft BV-03" | **12 trugen, 12 teilweise, 1 nicht**; Wiederkehr nach dem Commit **0**; Pakete **25 / 25** richtig |
| P-BV-02 | eigene Nachmessung der schwersten Angaben | `c-dc-*` im Markup, `.geo` in O1, `geo-hoch`, `btn-yellow`, `.listen-form`, `tr.clickable`, `settings-form`, `sd-zahl` | alle **bestätigt** (z. B. `.geo` im O1-Stylesheet **0**, in O2 **3**; `btn-yellow` **6** Stellen; `false_alarm` ohne `day_col`) |
| P-BV-02 | `vollstaendigkeit.py` nach der Berichtigung | Streichliste | **0** Befunde; 25 Zeilen: 15 „berichtigt nach der Gegenprüfung", 10 „bestätigt" |
| P-BV-03 | Gegenprüfung durch eine unabhängige Instanz gegen `CLAUDE.md` 4, `Technik.md` 4.98 und den Code (`mission_fields.php`, `einsatz_form.php`) | fünf Stellen (Handbuch 1, Handbuch 5 zweimal, README, Technik-Datenmodell) | **4 stimmen, 1 ungenau** (berichtigt); Befund: `start` im `pat_blob`, im Code selbst nachgesehen; zwölf weitere Aufzählungen ohne Notizen des Einsatzes |
| BV-06 | Code gelesen: `einsatz_form.php` (Block „MANUELLER ABFAHRTORT"), `import_ui.js` (`pat.start`, `pat.notes`, `pat.age`, `pat.site_desc`), `einsatz.php` (`dtGeschuetzt`), `suche.php` (Suchtext) | stimmen die neuen Aufzählungen mit dem Code? | `start` seit Web 6.2.0 (`7432ea2`) im Block; Schloss an **8** Zeilen + Katalogfelder; Notizen im Suchtext nach dem Entsperren; Import verschlüsselt Alter, Beschreibung, Notizen, Abfahrtort |
| BV-06 | `git diff ba2ec57 f747bf7` je geänderter Datei, Hunk-Anfänge | Konflikt mit P5c? | **0** Stellen in oder direkt neben einem P5c-Hunk (Handbuch Abschnitt 3 deshalb ausgelassen) |
| Aufnahme | `git merge --no-commit origin/main` (`29cf394`, P5c gemergt) | Konflikte | **9** in 4 Dateien: Backlog 6, Changelog 1, Technik 1, Probe 1; **0** im Code. Technik und Probe: Fassung von `main` (BV-Änderung dort überholt); Backlog und Changelog: beide Seiten (F-BV-15) |
| Aufnahme | `grep -oE '^[0-9]+\.' docs/Backlog.md \| … \| uniq -d`; Nummern ab 319 auf `main`, AR und dem Zweig | Nummern doppelt | vor dem Ausweichen: **329** zweimal (P5c/AP11 und BV-03, F-BV-14); danach **leer**, auch mit der neuen 332; AR 334 bis 337, keine Überschneidung |
| Aufnahme | Doppelte Leerzeilen im Backlog, `main` gegen den vereinigten Stand, je Fundstelle | hat das Auflösen fremden Text verändert? | `main` **24**, vereinigt **23** — die fehlende gehört zu Nr. 270, die BV ausgetragen hat (erster Versuch mit globalem Zusammenziehen hatte 24 entfernt, verworfen und neu aufgelöst) |
| Aufnahme | `git diff origin/main -- docs/CHANGELOG.md`, entfernte Zeilen | nimmt BV `main` etwas weg? | **1** Zeile, gewollt: die rückwirkend berichtigte Cron-Zeile im Eintrag Web 10.1.0 (Nr. 150) |
| Aufnahme | `pruefen.sh vollstaendigkeit` am vereinigten Stand | findet der Abtaster aus BV-01 etwas im neuen P5c-Code? | **0** Befunde; Hinweise „im Markup nicht gefunden" **73**; Unicode **6**, Emoji **0**; Töne PHP = JS **5**; Ausnahmen „fremde Quelle" 19, „Seite ohne Gerüst" 13, keine ungenutzt |
| Aufnahme | `git merge-tree --write-tree` des vereinigten Stands (loser Commit) gegen AR `83106eb` | kollidiert BV mit AR? | Konflikte nur in `docs/Backlog.md` (**4**) und `docs/CHANGELOG.md` (**1**), alle additiv; `docs/Lizenzen.md` ohne (F-BV-16) |
| Aufnahme | Prüfstand `pruefen.sh`, Stufe klein, 20 Riegel und Proben (mit P5c neu: `anker`, `rollenprobe` über `CLAUDE.md`) | Riegel und Proben der Berührung | Lauf 1, Baum `d55c9d5`: **1 rot** — `rollenprobe`, `Data truncated for column 'role'`: die Anlage stand auf dem Schema vor P5c (F-BV-17, Nr. 332). Nach `hochfahren.sh --neu` Lauf 2, derselbe Baum: **20 grün, 0 rot, 0 nicht gemessen**, 38 s. Der Bericht über den endgültigen Baum (mit diesem Eintrag und Nr. 332) steht in der Nachricht des Merge-Commits; dazu `bericht.py lesen --alle-riegel` mit den `--riegel` aus `pruefung.yml` — so, wie das Tor liest (Lehre aus F-P5c-172, Nr. 329) |
| Aufnahme 2 | `git merge --no-commit origin/main` (`a9d00ea`: #90 Web 21.1.3, #91 Stilvergleich) | Konflikte | **2**, beide additiv: Backlog Ende *Erledigt* (#91 mit Nr. 330, dahinter BV), Changelog oben (BV, dann #91 und 21.1.3); **0** im Code |
| Aufnahme 2 | Nummern ab 326 auf `main`, AR `71b1c0d` und dem Zweig; `uniq -d` | Nummern doppelt | vor dem Ausweichen **330** (#91 und BV, F-BV-18); danach **leer**; BV 331 bis 333, AR 334 bis 337 |
| Aufnahme 2 | `git diff --name-only 29cf394 a9d00ea \| grep -i migr` | bringt `main` Migrationen mit (Nr. 332)? | **0** — die Anlage vom ersten Aufnehmen hat das richtige Schema |
| Aufnahme 2 | Prüfstand, Stufe klein, über den Baum des Merge-Commits | Riegel und Proben der Berührung | Bericht in der Nachricht des Merge-Commits |
| BV-07 | Textbaustein 11.5a gegen `CLAUDE.md` 4 gelesen, Feld für Feld | nennt er dieselben Felder? | verschlüsselt: **11** Angaben wie die Zusage (neu: Abfahrtort); Klartext: Spur, Phasenkoordinaten, **Höhe**, Transportziel **samt Koordinate**, Zeiten, Reanimation, Besatzung, Notizen des Diensttags — gleich (F-BV-19) |
| BV-07 | `grep 'Diagnose, Alter und Einsatzort'` über `docs/` und `README.md`, ohne Konzepte, Changelog, Backlog | bleibt eine unvollständige Aufzählung? | **0** |
| BV-07 | `server/admin_rechtstexte.php`, Karte „Textbausteine" gelesen | steht der Baustein auch im Code (dann bräuchte es eine Web-Stufe)? | **nein** — 2 Bausteine (Adresssuche, Anmeldeversuche); der Verschlüsselungsbaustein steht nur im Handbuch |
| BV-07 | Prüfstand, Stufe klein | Riegel und Proben der Berührung | Bericht in der Nachricht des BV-07-Commits |

---

## 3. Im Browser geprüft

Nichts — BV berührt keine Datei, die der Browser lädt.

---

## 4. Prüfliste für die Betreiberin

| Nr. | Bedienweg | Erwartet | Woran ein Scheitern zu erkennen ist |
|---|---|---|---|
| ~~P-BV-03~~ | **Gegengeprüft am 25.09.2026** von einer unabhängigen Instanz (nur lesend), auf Weisung der Betreiberin. Ergebnis: Einstieg, Kapitel 5 (Aufzählung), README und Technik-Datenmodell **stimmen**; Kapitel 5, erster Absatz, nannte „Einsatzort" ohne „Adresse und Koordinate" — **berichtigt**. Dazu zwei Befunde über den Prüfpunkt hinaus (F-BV-13): der Abfahrtort (`start`) ist verschlüsselt, steht aber nicht in `CLAUDE.md` 4 und Technik 4.98 (**Q-BV-02**); zwölf weitere Aufzählungen ohne die Notizen des Einsatzes (**Q-BV-03**). | Dein eigener Blick bleibt möglich: Handbuch Abschnitt 1 und 5 lesen. | Eine Stelle nennt andere Felder als die andere. |
| P-BV-04 | Den Befehl aus `Technik.md` 4.97a gegen den Wertekasten auf **Betrieb → Hintergrundjobs** halten. | Die Dokumentation zeigt den Platzhalter `/pfad/zur/installation/jobs.php`, der Wertekasten den echten Pfad dieser Installation — ohne `server/`. | Der Wertekasten zeigt einen Pfad mit `server/` (dann stimmt der Befund aus Nr. 150 nicht) oder die Dokumentation noch `…/server/jobs.php`. |
| ~~P-BV-02~~ | **Gegengeprüft am 25.09.2026** von einer unabhängigen Instanz, alle 25 Zeilen (nicht nur drei), auf Weisung der Betreiberin. Ergebnis: **12 trugen, 12 nur teilweise, 1 nicht** — Commit und Paket stimmten überall, der Ersatz nicht. Jede schwere Angabe des Gegenprüfers vor der Übernahme nachgemessen; **15 Zeilen berichtigt**, 10 als bestätigt vermerkt (F-BV-12). | Dein eigener Blick bleibt möglich: eine der als „berichtigt nach der Gegenprüfung P-BV-02" markierten Zeilen gegen ihren Commit halten, Vorschlag `c-dc-winch` (heute `missiontable.js`, `spaltenSatz()`). | Der Commit enthält die Klasse nicht, oder der Ersatz steht dort nicht. |
| ~~P-BV-05~~ | ~~Im Prüfdokument PK den Punkt **P-PK-17** abhaken.~~ **Erledigt 25.09.2026** auf Weisung der Betreiberin: eingetragen in `Pruefdokument-PK-Pruefkette.md` (Zeile P-PK-17 und 5.6) und im Konzept PK. | Beleg: Lauf 281 — Push auf den Zweig von PR #85 bei offenem PR, genau ein Lauf, `pull_request`, kein `push`-Lauf. Gegenprobe: BV-Zweig, vier Pushes ohne PR, null Läufe; Öffnen von PR #87 ein Lauf. | — |
| P-BV-07 | Die zwei Rechtstexte selbst ansehen: `docs/rechtstexte/AVV.md` (Klartextliste bei den Datenkategorien) und `docs/rechtstexte/Nutzungsbedingungen.md` 2.6 (freiwillige Angaben). | Entscheiden, ob die Höhe des Einsatzorts (AVV) sowie die Notizen des Einsatzes und der Abfahrtort (Nutzungsbedingungen) ergänzt werden. Beide nennen Anlage II bzw. andere Stellen schon richtig. | — (deine Entscheidung; BV fasst Rechtstexte nicht an) |
| P-BV-06 | ~~Handbuch 11.5a und Abschnitt 3 (Nr. 194)~~ **erledigt mit BV-07, 26.09.2026.** Offen bleibt der Kopfkommentar in `server/jobs.php` (Nr. 150): mit der nächsten Web-Stufe, die die Datei ohnehin anfasst (E-BV-16). | Danach wandern Nr. 194 und 150 nach *Erledigt*. | Einer der beiden Punkte steht Wochen nach dem Merge noch offen. |
| P-BV-01 | Nach dem Merge von P5c und dem Aufnehmen von `main` in BV (**beides geschehen, 26.09.2026**): Stufe 1 des PR von BV ansehen, Schritt „Vollständigkeit". Örtlich: 0 Befunde. | grün, 0 Befunde | Rot mit einem Befund unter „5 Zusagen" in einer Datei, die P5c gebaut hat — dann hat der neue Abtaster dort etwas freigelegt, das der alte verschluckte. Das ist ein echter Fund, kein Fehler von BV: Stelle ansehen und entweder berichtigen oder mit Grund in `vollstaendigkeit-zusagen.md` eintragen. |

---

## 5. Grenzen der benutzten Prüfmittel

- Der Vergleich alt/neu misst **Unterschiede**, nicht Richtigkeit: Eine
  Zeile, die beide falsch behandeln, taucht darin nicht auf.
- Der Abtaster kennt kein Heredoc und keine Regex-Literale mit `//`
  (Docstring); im eigenen Code gibt es beides nicht.
