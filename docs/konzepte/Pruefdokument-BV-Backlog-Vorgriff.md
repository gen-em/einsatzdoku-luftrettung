# Prüfdokument BV — Vorgriff auf Backlog-Runde 4

*Gehört zu `Konzept-BV-Backlog-Vorgriff.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Stand: BV-01 bis BV-05 gebaut, Pull Request offen, 24.09.2026; wird mit jedem Paket
fortgeschrieben.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Stufe 1 auf GitHub für diesen Zweig** | Bis zum Pull Request lief sie nicht — Stufe 1 läuft auf Arbeitszweigen nur beim PR (Nr. 281). Örtlich sind alle 17 Riegel je Paket grün, und `bericht.py lesen` hat jeden Kopf-Commit gegengelesen („in Ordnung, 19 Zahlen"). Ob das Tor dasselbe sagt, zeigt erst der PR-Lauf. | PR-Lauf von `pruefung.yml` |
| **Der Abtaster gegen den P5c-Stand nach dem Merge** | Gefahren ist er gegen einen Abzug von P5c `3576a97` mit von Hand zusammengeführter Ausnahmeliste (`git merge-file`, ohne Konflikt) — 0 Befunde. Was P5c bis zum Merge noch an `server/` baut (AP6 bis AP9), kann niemand vorher messen. | P-BV-01 |
| **Eine eingebaute Selbstprobe der Vollständigkeitsprüfung** | Sie hat keine (`pruefen.sh`, Liste `SELBST`: „ein Rest, den E-PK-24 … erst noch einlöst"). Eine anzulegen hieße, `pruefen.sh` und `tools/quelltext/LIESMICH.md` zu ändern — beide hat P5c geändert. Die Abnahme von BV-01 ist deshalb von Hand gefahren und in Abschnitt 2 mit Zahl belegt, die Gegenproben sind nicht eingecheckt. | — |
| **Ob jeder Ersatz in der Streichliste genau stimmt** (BV-03) | Der entfernende Commit ist gemessen (Anwesenheit je Commit), der **Ersatz** ist aus dem Diff und dem P3-Konzept gelesen — eine Deutung, kein Messwert. Am unsichersten sind die vier `neu-*`-Zeilen: Das Anlegeformular der Stammdaten wurde in O9c als Ganzes ersetzt, die Zuordnung Klasse → neue Klasse ist dort eine Lesart. | P-BV-02 |
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

---

## 3. Im Browser geprüft

Nichts — BV berührt keine Datei, die der Browser lädt.

---

## 4. Prüfliste für die Betreiberin

| Nr. | Bedienweg | Erwartet | Woran ein Scheitern zu erkennen ist |
|---|---|---|---|
| P-BV-03 | Handbuch lesen: Abschnitt 1 („Was ist Gen-EM NAdoku?", Absatz „Patientendaten sind geschützt") und Abschnitt 5 (erster Absatz und die Aufzählung „Verschlüsselt sind nicht alle Daten …"). | Beide nennen die **Notizen des Einsatzes** als verschlüsselt und die des **Diensttags** als Klartext; keine Stelle sagt mehr „Notizen und Freitextfelder sind davon nicht erfasst". | Eine der beiden Stellen nennt andere Felder als die andere — oder als `Technik.md` 4.98. |
| P-BV-04 | Den Befehl aus `Technik.md` 4.97a gegen den Wertekasten auf **Betrieb → Hintergrundjobs** halten. | Die Dokumentation zeigt den Platzhalter `/pfad/zur/installation/jobs.php`, der Wertekasten den echten Pfad dieser Installation — ohne `server/`. | Der Wertekasten zeigt einen Pfad mit `server/` (dann stimmt der Befund aus Nr. 150 nicht) oder die Dokumentation noch `…/server/jobs.php`. |
| P-BV-02 | Stichprobe Streichliste: in `tools/quelltext/vollstaendigkeit-streichliste.md` drei der mit „Herkunft BV-03" markierten Zeilen wählen (Vorschlag: `rowlink`, `neu-form`, `c-dc-winch`) und je den genannten Commit ansehen (`git show <commit> -- server/`). | Die alte Klasse verschwindet in diesem Commit, und der genannte Ersatz steht an ihrer Stelle; `c-dc-winch` setzt `mission_fields_lib.php` heute noch (`'klasse' => 'c-dc-' . $col`). | Der Commit enthält die Klasse gar nicht, oder an ihrer Stelle steht etwas anderes als genannt — dann ist die Zeile eine Deutung, die nicht trägt. |
| P-BV-05 | Im Prüfdokument PK (`Pruefdokument-PK-Pruefkette.md`) den Punkt **P-PK-17** abhaken. | Beleg aus BV-05: drei PR-Läufe am BR-Zweig, null Läufe am BV-Zweig nach vier Pushes. | — (reines Abhaken) |
| P-BV-06 | **Nach dem Merge von P5c:** die zwei zurückgestellten Reste nachziehen lassen — den Textbaustein in Handbuch 11.5 (Nr. 194) und den Kopfkommentar in `server/jobs.php` (Nr. 150, mit der nächsten Web-Stufe). | Danach wandern Nr. 194 und 150 nach *Erledigt*. | Einer der beiden Punkte steht Wochen nach dem Merge noch offen. |
| P-BV-01 | Nach dem Merge von P5c und dem Aufnehmen von `main` in BV: Stufe 1 des PR von BV ansehen, Schritt „Vollständigkeit". | grün, 0 Befunde | Rot mit einem Befund unter „5 Zusagen" in einer Datei, die P5c gebaut hat — dann hat der neue Abtaster dort etwas freigelegt, das der alte verschluckte. Das ist ein echter Fund, kein Fehler von BV: Stelle ansehen und entweder berichtigen oder mit Grund in `vollstaendigkeit-zusagen.md` eintragen. |

---

## 5. Grenzen der benutzten Prüfmittel

- Der Vergleich alt/neu misst **Unterschiede**, nicht Richtigkeit: Eine
  Zeile, die beide falsch behandeln, taucht darin nicht auf.
- Der Abtaster kennt kein Heredoc und keine Regex-Literale mit `//`
  (Docstring); im eigenen Code gibt es beides nicht.
