# Prüfdokument BV — Vorgriff auf Backlog-Runde 4

*Gehört zu `Konzept-BV-Backlog-Vorgriff.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Stand: BV-02 gebaut, 24.09.2026; wird mit jedem Paket
fortgeschrieben.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Abtaster gegen den P5c-Stand nach dem Merge** | Gefahren ist er gegen einen Abzug von P5c `3576a97` mit von Hand zusammengeführter Ausnahmeliste (`git merge-file`, ohne Konflikt) — 0 Befunde. Was P5c bis zum Merge noch an `server/` baut (AP6 bis AP9), kann niemand vorher messen. | P-BV-01 |
| **Eine eingebaute Selbstprobe der Vollständigkeitsprüfung** | Sie hat keine (`pruefen.sh`, Liste `SELBST`: „ein Rest, den E-PK-24 … erst noch einlöst"). Eine anzulegen hieße, `pruefen.sh` und `tools/quelltext/LIESMICH.md` zu ändern — beide hat P5c geändert. Die Abnahme von BV-01 ist deshalb von Hand gefahren und in Abschnitt 2 mit Zahl belegt, die Gegenproben sind nicht eingecheckt. | — |
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

---

## 3. Im Browser geprüft

Nichts — BV berührt keine Datei, die der Browser lädt.

---

## 4. Prüfliste für die Betreiberin

| Nr. | Bedienweg | Erwartet | Woran ein Scheitern zu erkennen ist |
|---|---|---|---|
| P-BV-01 | Nach dem Merge von P5c und dem Aufnehmen von `main` in BV: Stufe 1 des PR von BV ansehen, Schritt „Vollständigkeit". | grün, 0 Befunde | Rot mit einem Befund unter „5 Zusagen" in einer Datei, die P5c gebaut hat — dann hat der neue Abtaster dort etwas freigelegt, das der alte verschluckte. Das ist ein echter Fund, kein Fehler von BV: Stelle ansehen und entweder berichtigen oder mit Grund in `vollstaendigkeit-zusagen.md` eintragen. |

---

## 5. Grenzen der benutzten Prüfmittel

- Der Vergleich alt/neu misst **Unterschiede**, nicht Richtigkeit: Eine
  Zeile, die beide falsch behandeln, taucht darin nicht auf.
- Der Abtaster kennt kein Heredoc und keine Regex-Literale mit `//`
  (Docstring); im eigenen Code gibt es beides nicht.
