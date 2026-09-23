# Prüfdokument RP — Die roten Proben der Nebenstufe

Gehört zu `Konzept-RP-Rote-Proben.md`. Nach `CLAUDE.md` 7: was maschinell
geprüft wurde (Mittel und Zahl), was im Browser, was nicht und warum, und
eine abhakbare Prüfliste. Angelegt mit RP-01.

## 0. Was nicht geprüft werden konnte

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Versandprobe gegen echte Server** | Sie läuft gegen Nachbauten (`gegenstellen.py`, Python). vsftpd und OpenSSH (`echte_gegenstellen.sh`) brauchen root und Pakete, die der Prüfstand nicht nachlädt. Die Adapter sind damit gegen einen echten Protokollablauf geprüft, nicht gegen die Eigenheiten eines Webspace-Servers. | von Hand: `sh tools/proben/versand/echte_gegenstellen.sh …`, dann `proben.sh versand <wurzel> --echt` |
| **Die ganze Nebenstufe auf frischer Anlage** | Kommt mit RP-05; bis dahin sind die Proben einzeln belegt. | RP-05 |

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-RP-01 | Die Versandprobe startet und stoppt ihre Gegenstellen selbst | `bash tools/proben/proben.sh versand`, danach `pgrep -f "python3 tools/proben/versand/[g]egenstellen"` | 135 Erwartungen, 0 nicht erfüllt; danach **kein** Nachbau-Prozess | Die Probe ist grün, aber `pgrep` findet noch Prozesse — dann horchen die Ports 2121/2122/2222 weiter | **erledigt 23.09.2026** — 135/0 in 9 s, danach 0 Prozesse |
| P-RP-02 | Die Freigabeprobe braucht keinen Kreislauf mehr | auf einer frischen Anlage **allein**: `bash tools/proben/proben.sh freigabe` | 16/0; „Konto umlauf-freigabe@… angelegt" und „… geloescht"; danach keine Zeile mit dieser Adresse in `users` | „Zielkonto nicht gefunden", oder das Konto bleibt stehen | **erledigt 23.09.2026** — 16/0 in 17 s, danach 0 Zeilen |
| P-RP-03 | Die Wegprobe läuft am edbak-Umlaufkonto | `python3 tools/referenzdatensatz/vergleich/kreislauf.py --art edbak --frisch`, dann `python3 tools/spaltenregister/wegprobe.py` | Kreislauf 0 unerklärt; Wegprobe 34/0 | „Kein Ruhesegment mit genug GPS-Punkten" (falsches Konto) oder „Konto … nicht gefunden" (Kreislauf nicht gefahren) | **erledigt 23.09.2026** — 328 771 Vergleiche, 0 unerklärt, 31 s; Wegprobe 34/0 in 3 s |
| P-RP-04 | `nach` zieht die Voraussetzung mit | `python3 tools/pruefstand/auswahl.py --stufe neben --datei server/index.php --nur-proben` | `kreislauf-edbak` steht vor `spaltenregister-wegprobe` | die Wegprobe steht vor dem Kreislauf, oder er fehlt | **erledigt 23.09.2026** — Selbstprobe 27/0, Liste geprüft |
| P-RP-05 | Ein zweiter Prüfstandlauf auf derselben Anlage bricht nicht an den Kreisläufen ab (F-RP-11) | `pruefen.sh --stufe neben --datei server/index.php --ohne-hochfahren` zweimal hintereinander | beide Läufe erreichen den Vergleich | „Konto besteht schon" im zweiten Lauf | **offen** — RP-05 |
| P-RP-06 | Die Nebenstufe ist grün | `hochfahren.sh --neu`, dann `pruefen.sh --stufe neben --datei server/index.php --ohne-hochfahren` | 36 Proben, 0 rot, 0 nicht gemessen, Zeit je Probe in jeder Zeile | eine rote Probe, oder eine ohne Zeit | **offen** — RP-05 |

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
