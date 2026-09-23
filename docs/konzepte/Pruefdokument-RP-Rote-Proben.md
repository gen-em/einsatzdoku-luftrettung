# Prüfdokument RP — Die roten Proben der Nebenstufe

Gehört zu `Konzept-RP-Rote-Proben.md`. Nach `CLAUDE.md` 7: was maschinell
geprüft wurde (Mittel und Zahl), was im Browser, was nicht und warum, und
eine abhakbare Prüfliste. Angelegt mit RP-01.

## 0. Was nicht geprüft werden konnte

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Versandprobe gegen echte Server** | Sie läuft gegen Nachbauten (`gegenstellen.py`, Python). vsftpd und OpenSSH (`echte_gegenstellen.sh`) brauchen root und Pakete, die der Prüfstand nicht nachlädt. Die Adapter sind damit gegen einen echten Protokollablauf geprüft, nicht gegen die Eigenheiten eines Webspace-Servers. | von Hand: `sh tools/proben/versand/echte_gegenstellen.sh …`, dann `proben.sh versand <wurzel> --echt` |
| ~~Die ganze Nebenstufe auf frischer Anlage~~ | **Nachgeholt am 23.09.2026:** 36 grün, 0 rot, 0 nicht gemessen, 1 239 s (5). | erledigt |
| **Der erste echte Nebensprung** | Gemessen ist die Nebenstufe mit einer vorgegebenen Berührung (`--datei server/index.php`), nicht an einem Zweig mit Versionssprung. Dass der Prüfstand die Stufe dort selbst erkennt, belegt PK-05 (F-PK-30), nicht dieser Lauf. | P5c AP1 (P-PK-33) |

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-RP-01 | Die Versandprobe startet und stoppt ihre Gegenstellen selbst | `bash tools/proben/proben.sh versand`, danach `pgrep -f "python3 tools/proben/versand/[g]egenstellen"` | 135 Erwartungen, 0 nicht erfüllt; danach **kein** Nachbau-Prozess | Die Probe ist grün, aber `pgrep` findet noch Prozesse — dann horchen die Ports 2121/2122/2222 weiter | **erledigt 23.09.2026** — 135/0 in 9 s, danach 0 Prozesse |
| P-RP-02 | Die Freigabeprobe braucht keinen Kreislauf mehr | auf einer frischen Anlage **allein**: `bash tools/proben/proben.sh freigabe` | 16/0; „Konto umlauf-freigabe@… angelegt" und „… geloescht"; danach keine Zeile mit dieser Adresse in `users` | „Zielkonto nicht gefunden", oder das Konto bleibt stehen | **erledigt 23.09.2026** — 16/0 in 17 s, danach 0 Zeilen |
| P-RP-03 | Die Wegprobe läuft am edbak-Umlaufkonto | `python3 tools/referenzdatensatz/vergleich/kreislauf.py --art edbak --frisch`, dann `python3 tools/spaltenregister/wegprobe.py` | Kreislauf 0 unerklärt; Wegprobe 34/0 | „Kein Ruhesegment mit genug GPS-Punkten" (falsches Konto) oder „Konto … nicht gefunden" (Kreislauf nicht gefahren) | **erledigt 23.09.2026** — 328 771 Vergleiche, 0 unerklärt, 31 s; Wegprobe 34/0 in 3 s |
| P-RP-04 | `nach` zieht die Voraussetzung mit | `python3 tools/pruefstand/auswahl.py --stufe neben --datei server/index.php --nur-proben` | `kreislauf-edbak` steht vor `spaltenregister-wegprobe` | die Wegprobe steht vor dem Kreislauf, oder er fehlt | **erledigt 23.09.2026** — Selbstprobe 27/0, Liste geprüft |
| P-RP-07 | Die neue csv-Referenz trägt die Hausform | `unzip -p tools/referenzdatensatz/referenz/*csv*.zip felder.csv \| grep -c "NotärztIn"` | mindestens 1 | 0 — dann ist wieder eine Referenz von vor der Hausform eingecheckt | **erledigt 23.09.2026** |
| P-RP-08 | Die Wiederherstellungsprobe misst den Auftrag auch auf frischer Anlage | `hochfahren.sh --neu`, dann `bash tools/proben/proben.sh wiederherstellung` | Teil 10: „hoert dann auf" grün, **WIEDERAUFNAHME gemessen** (nicht „nicht messbar"); danach 0 Konten `probe-auftrag-%` | „2 erledigt, 0 offen" — dann fehlen die Zusatzkonten; oder „nicht messbar" | **erledigt 23.09.2026** — auch auf frischer Anlage, im Lauf von P-RP-06 grün (1 s) |
| P-RP-05 | Ein zweiter Prüfstandlauf auf derselben Anlage bricht nicht an den Kreisläufen ab (F-RP-11) | `pruefen.sh --stufe neben --datei server/index.php --ohne-hochfahren` zweimal hintereinander | beide Läufe erreichen den Vergleich | „Konto besteht schon" im zweiten Lauf | **erledigt 23.09.2026** — nach dem Nebenstufenlauf, das Umlaufkonto bestand: „Vorhandenes Konto umlauf-edbak@gen-em.org gelöscht", 328 771 Vergleiche, 0 unerklärt, 28 s |
| P-RP-06 | Die Nebenstufe ist grün | `hochfahren.sh --neu`, dann `pruefen.sh --stufe neben --datei server/index.php --ohne-hochfahren` | 36 Proben, 0 rot, 0 nicht gemessen, Zeit je Probe in jeder Zeile | eine rote Probe, oder eine ohne Zeit | **erledigt 23.09.2026** — 36 grün, 0 rot, 0 nicht gemessen, 1 239 s; jede Zeile mit Zeit (5) |
| P-RP-09 | Die CSP-Probe schreibt nicht mehr ins Repositorium (F-RP-13) | `bash tools/proben/proben.sh csp-browser`, dann `git status --short` | 34/34; „Bild: /tmp/csp-kopfzeilen.png"; `git status` leer | eine geänderte Datei unter `tools/proben/csp-browser/` | **erledigt 23.09.2026** |
| P-RP-10 | Die Freigabeprobe lässt kein Paket liegen (F-RP-14) | `ls server/sicherungen \| wc -l` vor und nach `proben.sh freigabe` | gleiche Zahl | eine Zahl mehr — dann blieb das Paket der Quelle liegen | **erledigt 23.09.2026** — 9 → 9 |
| P-RP-11 | Alle Proben des Läufers | `bash tools/proben/proben.sh alle` | 20 von 20 grün, 0 ausgelassen | eine rote, oder „ausgelassen" | **erledigt 23.09.2026** — 20/20 in 100 s (vorher 13 von 19, `versand` ausgelassen) |

## 2. Messprotokoll RP-01 (23.09.2026)

| Mittel | Zahl |
|---|---|
| `proben.sh versand` (Nachbau) | 135 Erwartungen, 0 nicht erfüllt, 9 s; danach 0 Prozesse |
| `proben.sh freigabe` (eigenes Konto) | 16 / 0, 17 s; Konto danach gelöscht |
| `kreislauf.py --art edbak --frisch` | 328 771 Einzelvergleiche, 0 unerklärt, 31 s |
| `wegprobe.py` (ohne Schalter) | 34 / 0, 3 s |
| `kreislauf.py --art csv --frisch` | 10 922 Vergleiche, **3 unerklärt** (Hausform) — erwartet rot bis RP-02 |
| `proben.sh gpx` | 95 / **4** — der Pfad stimmt, dahinter F-RP-12; erwartet rot bis RP-02 |
| `auswahl.py --selbstprobe` | 27 Lagen / 0 (vorher 23) |
| `bericht.py lesen --selbstprobe` | 13 / 0 |
| `kettenaufrufe` | Selbstprobe 13/13; 75 Aufrufe, 0 Befunde, 2 ungeprüft (unverändert) |
| `quelltext/pruefen.sh alle` | 8 von 8 grün, Textprobe 0 |

**Zwei Fehler auf dem Weg**, beide gemessen und behoben: Die Nachbauten
liefen nach der Probe weiter (Kindprozesse von `gegenstellen.py`; jetzt
eigene Prozessgruppe), und das Kurzkonzept hatte der Wegprobe das
csv-Umlaufkonto zugewiesen, das keine GPS-Punkte trägt.

## 3. Messprotokoll RP-02 (23.09.2026)

| Mittel | Zahl |
|---|---|
| `proben.sh gpx` (Texte + neue Referenz) | 95 / 0, 2 s (vorher 95 / 4) |
| `proben.sh wartung` | 67 / 0, 1 s |
| `proben.sh mail` | 41 Prüfungen, 0 Befunde, 18 s |
| `proben.sh csp-browser` | 34 / 34, 25 s — neu: „Report-Only: ohne frame-ancestors", „Scharf: frame-ancestors none" |
| `referenz_export.mjs` auf frischer Anlage | 26 s, 0 Konsolenfehler; 204 GPX, 101 Einsätze, 209 Dateien (wie die alte) |
| `kreislauf.py --art csv --frisch` gegen die neue Referenz | 10 922 Vergleiche, 1 271 erwartet, **0 unerklärt**, 48 s |

**Nicht übernommen:** die mit erzeugte edbak-Referenz. Die alte ist grün
(0 unerklärt in RP-01), und E-RP-01 betrifft nur das csv-Archiv.

## 4. Messprotokoll RP-03 (23.09.2026)

| Mittel | Zahl |
|---|---|
| `proben.sh wiederherstellung` | **111 / 0**, 1 s (vorher 110 / 2) |
| Teil 10 | Auftrag 5 von 5 Konten; knapper Schub „2 erledigt, 3 von 5 offen"; Zeiger `cur=2`; Wiederaufnahme `cur 2 -> 4`, gut+feh 2 -> 3; Rückstand 2 |
| Zusatzkonten danach | 0 Zeilen `probe-auftrag-%` |

**Urteil: Erwartung, nicht Anwendung.** `edbak_auftrag_schub()` hört auf,
sobald die Uhr unter die Reserve fällt; die Probe gab ihr Zeit für zwei
Konten, und eine frische Anlage hat genau zwei. Kein Fehler in `server/`,
keine Versionsstufe.

**Nebenbei gefunden (F-RP-14, nicht behoben):** Die Freigabeprobe lässt je
Lauf das Sicherungspaket ihrer Quelle unter `server/sicherungen/` liegen.

## 5. Messprotokoll RP-04 und RP-05 (23.09.2026)

**Die Nebenstufe auf frischer Anlage** (`hochfahren.sh --neu` 26 s, dann
`pruefen.sh --stufe neben --datei server/index.php --ohne-hochfahren`):
**36 grün, 0 rot, 0 nicht gemessen, 1 239 s.** Ein erster Lauf war durch
einen Neustart des Containers abgebrochen und ist nicht gezählt.

| Probe | s | | Probe | s |
|---|---|---|---|---|
| bilderlauf (8 Breiten, 62 Seiten, 496 Bilder) | **769** | | kreislauf-csv | 43 |
| bedienprobe | **261** | | kreislauf-edbak | 28 |
| browserprobe-csp | 24 | | mailprobe | 18 |
| freigabeprobe | 15 | | ratenprobe | 12 |
| kopplungsprobe | 9 | | versandprobe | 9 |
| wortliste, vollstaendigkeit | je 5 | | zaehlung | 4 |
| übrige 20 | zusammen 22 | | | |

**Bilderlauf und Bedienprobe tragen 83 %.** Beide laufen absichtlich
nacheinander (Wartungsseiten schalten die ganze Anlage, die Bedienprobe ändert
den Bestand). Entschieden: Ziel rund 21 min (E-RP-05).

| Mittel | Zahl |
|---|---|
| `kreislauf.py --art edbak --frisch` bei **bestehendem** Umlaufkonto (P-RP-05) | Konto gelöscht, 328 771 Vergleiche, 0 unerklärt, 28 s |
| `proben.sh alle` (P-RP-11) | **20 von 20 grün, 0 ausgelassen**, 100 s |
| `proben.sh csp-browser` nach F-RP-13 | 34/34, Bild unter `/tmp`, `git status` leer |
| `proben.sh freigabe` nach F-RP-14 | 16/0; Ordner unter `server/sicherungen` 9 → 9 |

**Was dabei schiefging:** Nach einem Neustart des Containers liefen PHP,
TLS-Weiterleitung und MariaDB nicht mehr; zwei Proben meldeten
„ERR_CONNECTION_REFUSED". Nach `hochfahren.sh` (ohne `--neu`) grün — die
Ursache war die Anlage, nicht die Änderung.

