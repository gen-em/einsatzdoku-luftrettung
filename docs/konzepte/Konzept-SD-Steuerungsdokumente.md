# Konzept SD — Steuerungsdokumente schneiden

**Kürzel:** `SD`. Arbeitspakete `SD-00 … SD-04`, Meilensteine der Betreiberin
`SD-M1 …`, Entscheidungen `E-SD-NN`, Befunde `F-SD-NN`, Fragen an die
Betreiberin `Q-SD-NN`, Prüfpunkte `P-SD-NN`. Commit-Nachrichten beginnen mit
dem Paket (`SD-02: …`). Benennung nach `docs/Pruefablauf.md` 7.
**Rahmenplan:** R51 (Steuerung und Archiv getrennt), R54 (Kurzregister),
R62/K5/K7/K9 (Lebenszyklus, Erledigt-Zeile, Prüfdokument), Abschnitt 9
(Pflege), `CLAUDE.md` 2, 7, 9. **Backlog:** Nr. 177 (Fassungen doppelt im
Verlauf), 188 (kein Prüfmittel für Verweise zwischen Dokumenten — nur
verwiesen, nicht erledigt), 193 (Register und Doku führen R42 als offen),
196 (Rendering ab Nr. 100), 199 (Nr. 5 fehlt). **Neue Nummern vergibt die
einspielende Instanz** — dieses Konzept vergibt weder Backlog-Nummern noch
Rahmenplan-Fassungen (K3-analog, E-SD-22).
**Herkunft:** Durchsicht der beiden Dokumente am 24.09.2026 in der
Konzeptsitzung; Befund gemessen an **Fassung 108** des Rahmenplans (Zweig
`claude/p5c-mockups-konzept-4yeomf`, Commit `5e501ae`; `main` stand bei
Fassung 107, `f4fe4a0`). Entscheidungen des Auftraggebers vom 24.09.2026:
„alles so wie empfohlen" (2.1, E-SD-01 bis -05).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2). **Kein Fable-Schritt.**
**Fächerung (`CLAUDE.md` 7):** je Paket in 3.2. Erzählender Text und die
Arbeit an derselben Datei bleiben seriell.
**Versionsstufe:** keine — nur `docs/`, `tools/`, `.github/`
(`CLAUDE.md` 2). Changelog-Eintrag als `[Werkzeug: …]` wie bei TB und PK.
**Ablage:** dieses Dokument; Prüfdokument daneben
(`Pruefdokument-SD-Steuerungsdokumente.md`, entsteht mit SD-01).
**Sofortteil:** `Einschub-SD-Sofort-2026-09-24.md` (SD-00, siehe 3.1).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **26.09.2026 — SD-04 gebaut, PR #92 offen.** Alle vier Pakete sind durch; es fehlt die Freigabe des Abschlusses durch die Betreiberin (dann Erledigt-Zeile, Verlaufszeile, Löschung dieses Konzepts — K9) und der Merge. `CLAUDE.md` 2, 7, 9 und Rahmenplan 5, 9, K5, K9, R62 tragen die neuen Sätze; Changelog-Eintrag `[Werkzeug: Rahmenplan und Backlog geschnitten (Konzept SD)]`; Rahmenplan Fassung 130 (Stand von `main` gemessen: `f5bddc2`, Android 0.16.0; AR gemergt). `tools/steuerung/` steht: `decken.py` hält **20 Decken** (0 gerissen; Selbstprobe 22 Fälle), `uebersicht.py` die Kopfzeilen (105, 0 ohne Grammatik, 0 ohne Ziel; Selbstprobe 7 Fälle); als Riegel `steuerung` in `pruefablauf.json` und als Schritt in `pruefung.yml`, `Pruefablauf.md` 4 neu erzeugt, 6.11 und 6.12 nachgezogen. Rahmenplan (SD-01: Fassung 124 wörtlich in `docs/Rahmenplan-Archiv-2.md`, Verlauf ab 125 in `docs/Rahmenplan-Verlauf.md`, Fassung 129 mit **459 Zeilen**) und Backlog sind geschnitten: `docs/Backlog.md` hält die **105 offenen Einträge** mit Kopfzeile in **1 788 Zeilen** (Fassung N: 11 329), Kopf 56 Zeilen, 52 Einträge gekürzt, 0 über 20 Zeilen, `cmark-gfm` 0 `<pre>` · 105 `<li>`; `docs/Backlog-Erledigt.md` trägt die 228 erledigten Einträge wörtlich (0 Unterschiede) und den Werdegang der Nummernvergabe. `tools/quelltext/bestand.py` liest beide Dateien (E-SD-37). Prüfdokument liegt daneben. |
> | Entschieden | E-SD-01 bis E-SD-27 (Konzept, 24.09.2026); die fünf Punkte zum Gegenlesen in 2.4 gelten ohne Widerspruch. **Aus der Umsetzung: E-SD-28 bis E-SD-33** (Abschnitt 2.5). **Von der Betreiberin am 26.09.2026: E-SD-34** (27 Zuarbeiten erledigt), **E-SD-35** (alle 40 Vorschläge), **E-SD-36** (Decke 500 reicht). **Aus SD-02: E-SD-37 bis E-SD-41, aus SD-03: E-SD-42 bis E-SD-44, aus SD-04: E-SD-45, E-SD-46, aus dem PR: E-SD-47** (Abschnitt 2.5). |
> | Offen | nichts an Fragen. **Die Freigabe des Abschlusses** (Prüfdokument 3.7); danach schreibt die Instanz Erledigt-Zeile und Verlaufszeile und löscht dieses Konzept (E-SD-45). P-SD-15 grün (Lauf 311), P-SD-16 erfüllt (Lauf 313: rot mit Datei und Zeile; der erste Versuch, Lauf 312, fand F-SD-13). Befunde F-SD-01 bis F-SD-12 in 2.5: F-SD-05 klärt sich nur an der Anlage; F-SD-06 und F-SD-08 sind Sache von Schritt 17; F-SD-07 ist mit SD-04 erledigt. |
> | Umsetzung | **SD-00 erledigt** 24.09.2026 (P5c-Instanz, Fassung 111). **SD-01 erledigt** 26.09.2026 auf `claude/serene-tesla-sqeno2` (von `claude/affectionate-newton-6pzfkc` `bdf1787`, der `origin/main` `d34908b` enthält; Fassungen 125 bis 127). **SD-M1 erledigt** 26.09.2026 (Fassung 128, `fd536e7`). `origin/main` `f5bddc2` (PR #88, AR) aufgenommen (`1596469`, Pruefablauf.md 5.3). **SD-02 erledigt** 26.09.2026 (`135863c`). **SD-03 erledigt** 26.09.2026 (Fassung 129, `61e8c79`). **SD-04 erledigt** 26.09.2026 (Fassung 130); **PR #92 offen**, nicht gemergt — eine Instanz mergt nie (`CLAUDE.md` 8). |
> | Fable-Schritte | keine. SD-01 lief mit Fable (Anweisung der Betreiberin, 26.09.2026); Abweichung von K2 zur Kenntnis. |
> | Fächerung | SD-01: das Lesen der 105 offenen Backlog-Einträge auf 32 Agenten (E-SD-33); alles Schreiben seriell. SD-02: keine — ein Skript setzt zusammen, die 52 Kürzungen sind von Hand geschrieben (E-SD-40). SD-03: keine (E-SD-44). |
> | Nummern | Rahmenplan-Fassungen 125 bis 130 vergeben; keine Backlog-Nummer vergeben (der neue Kopf nennt 339 als nächste freie; 338 blieb frei). |

---

## 1. Befund

Gemessen am 24.09.2026 an Fassung 108 (`5e501ae`). Wer die Umsetzung
beginnt, misst neu — die Zahlen hier sind der Anlass, nicht der Sollwert.

### 1.1 Rahmenplan.md

| Was | Wert |
|---|---|
| Zeilen · Wörter | 3 691 · 68 800 |
| Fassungen seit dem ersten Schnitt (R51, 02.09.2026) | 93 in 22 Tagen |
| Kopf (bis `## 1`) | 231 Zeilen |
| Abschnitt 3 Fahrplan | 1 320 Zeilen; 27 Tabellenzeilen, 12 davon mit erledigtem Schritt; längste Statuszelle 1 117 Zeichen |
| Abschnitt 3, Blöcke zu erledigten Schritten | rund 900 der 1 320 Zeilen |
| Abschnitt 5 Zuordnung | 96 Zeilen gegen **113 offene** Backlog-Punkte — 18 offene Nummern fehlen (250–252, 259, 265, 266, 270–277, 280, 281, 283, 284), Nr. 57 steht drin und ist erledigt. Die Selbstprüfzahl aus Fassung 46 schlägt fehl |
| Abschnitt 6 Zuarbeiten | 419 Zeilen, 126 Tabellenzeilen, 18 durchgestrichen, ohne Ordnung |
| Abschnitt 7 Register | 85 R-Zeilen; die Statusspalte trägt Absätze |
| Abschnitt 8 „Kurzübersicht" | 1 148 Zeilen, 27 Blöcke |
| Abschnitt 10 Verlauf | 109 Fassungszeilen, längste 4 056 Zeichen; Fassungen 31–50 in falscher Reihenfolge (Nr. 177) |
| Sätze der Art „berichtigt / hier stand / überholt / nachgemessen" | 121 |
| Blockquote-Zeilen | 223 |
| Konzepte ohne Fahrplanzeile | PK, RP, TB (nur in Abschnitt 8 und 10) |

**Dreimal dasselbe:** Ein erledigter Schritt steht in der Fahrplan-Tabelle
(Statuszelle), im Block darunter und in Abschnitt 8. Fassung 108 selbst
fügt einen Inline-Absatz „Berichtigt mit Fassung 108 — hier stand bis dahin
…" und einen Blockquote „ÜBERHOLT" über einen Absatz, der „als Werdegang
stehen bleibt", hinzu — das Muster läuft weiter.

### 1.2 Backlog.md

| Was | Wert |
|---|---|
| Zeilen · Wörter | 10 042 · 80 097 |
| Offen · Erledigt | 113 · 175 |
| Kopf (bis `## Offen`) | 249 Zeilen, fast nur Werdegang der Nummernkollisionen |
| Offen-Teil · Erledigt-Teil | 3 562 · 6 231 Zeilen |
| Offener Eintrag: Median | 23 Zeilen |
| Offene Einträge über 20 Zeilen | 66 (über 45 Zeilen: 18; Nr. 37 hat 211, Nr. 202 210, Nr. 80 113) |
| Folgezeilen mit vier Leerzeichen unter dreistelligen Nummern | 5 305 (Nr. 196: rendern auf GitHub als Codeblock) |
| Verweise „Backlog Nr. N" in Code und Doku | rund 1 300 |
| Verweise „Fassung N" auf den Rahmenplan außerhalb des Rahmenplans | 358 |

Kein Eintrag trägt Zuordnung, Stand oder Aufnahmedatum in fester Form.
Die Zuordnung steht im Rahmenplan (Abschnitt 5) ein zweites Mal — und dort
falsch.

### 1.3 Ursachen

1. **Drei Zwecke in einer Datei.** Steuerung (was ist als Nächstes dran),
   Register (dauerhafte Nummern) und Protokoll (was geschah, was war falsch)
   stehen zusammen. Der Changelog-Ton „erklärende Prosa mit Begründung"
   (`CLAUDE.md` 2) ist in die Steuerung eingewandert.
2. **Zu viele Schreibanlässe.** K5/R62 sehen den Statusblock im Konzept
   nach jedem Arbeitspaket vor und den Rahmenplan am Phasenende. Tatsächlich
   sind die Fassungen 88 bis 101 vierzehn Fassungen für zwei Arbeitspakete
   von Kette II. Jede Fassung zieht Kopfpflege, Zweigkollisionen und weitere
   Berichtigungen nach sich.
3. **Kein Deckel.** Der Schnitt bei Fassung 16 war richtig und einmalig.
   Ohne messbare Grenze war das Dokument drei Wochen später größer als
   vorher.

---

## 2. Entscheidungen

### 2.1 Grundsatzentscheidungen des Auftraggebers (24.09.2026)

- **E-SD-01 Schnitt statt Umschrift.** Der Rahmenplan wird wie bei R51
  geschnitten: Die zum Zeitpunkt des Schnitts gültige Fassung **N** wird
  **wörtlich und unverändert** als `docs/Rahmenplan-Archiv-2.md`
  eingefroren (Fassungen 16 bis N). Der neue `docs/Rahmenplan.md` beginnt
  mit Fassung N+1. Die 358 Verweise „Fassung NN" in anderen Dokumenten
  lösen ins Archiv-2 auf; nichts wird umgeschrieben. `Rahmenplan-Archiv.md`
  (Fassungen 1–15) bleibt, wie es ist.
- **E-SD-02 Abschnitt 5 entfällt; die Zuordnung steht im Backlog.** Jeder
  offene Backlog-Eintrag trägt eine Kopfzeile fester Form mit `gehört zu`
  und `Stand` (4.6). Die Übersicht je Schritt wird **erzeugt**
  (`tools/steuerung/`), nicht von Hand geführt. Rahmenplan Abschnitt 5
  bleibt als Verweisabschnitt von fünf Zeilen bestehen (E-SD-24).
- **E-SD-03 Backlog-Einträge werden gedeckelt und gekürzt.** Ein offener
  Eintrag hat höchstens **20 Zeilen** (Kopfzeile eingeschlossen). Die 66
  Einträge darüber werden einmalig gekürzt; der Werdegang bleibt in der
  Git-Historie und wird im Eintrag per Commit verwiesen (4.7) — dasselbe
  Prinzip wie die Konzeptlöschung nach R62. Erledigte Einträge ziehen
  **wörtlich** nach `docs/Backlog-Erledigt.md` um (E-SD-11).
- **E-SD-04 Längendecken sind ein roter Prüfschritt in Stufe 1.** Ein
  Werkzeug `tools/steuerung/` misst die Decken aus Abschnitt 5 dieses
  Konzepts; `pruefung.yml` bekommt den Schritt „Steuerungsdokumente —
  halten sie ihre Decken?". Die Zahlen stehen in der `LIESMICH.md` des
  Werkzeugs (`Pruefablauf.md` 6.11), hier nur als Vorschlag.
- **E-SD-05 Zeitpunkt.** SD-01 bis SD-04 laufen **nach dem Merge des
  10c-PR**, bevor ein weiterer Schritt den Rahmenplan anfasst (E-SD-19).
  Die Schreibregeln (E-SD-15) gehen **sofort** an die P5c-Instanz (SD-00),
  damit AP2 bis AP9 keinen weiteren Erzähltext in den Rahmenplan schreiben.

### 2.2 Aufbau des neuen Rahmenplans

- **E-SD-06 Drei Zwecke, drei Orte.** *Steuerung:* `Rahmenplan.md`.
  *Register:* R-Nummern in `Rahmenplan.md` 7, Backlog-Nummern in
  `Backlog.md` und `Backlog-Erledigt.md`. *Protokoll:* `Rahmenplan-Verlauf.md`,
  die beiden Archive, `CHANGELOG.md`, die Git-Historie. Was Protokoll ist,
  steht nicht in der Steuerung.
- **E-SD-07 Kopf nach Vorlage, höchstens 15 Zeilen** (4.1). Er hält
  Fassung, gemessenen Stand von `origin/main`, was läuft, was als Nächstes
  kommt, offene Merges, Zahl der fälligen Betreiberposten, ob `update.php`
  fällig ist. Kein Werdegang, keine Berichtigung, keine Leseanleitung über
  einen Satz hinaus.
- **E-SD-08 Fahrplan: nur offene Schritte, festes Statusvokabular.** Die
  Tabelle in Abschnitt 3 führt nur Schritte, die noch nicht erledigt sind.
  Der Status ist eines von `offen · Konzept · freigegeben · Umsetzung ·
  gebaut · gemergt · blockiert` plus Datum und Verweis, höchstens 240
  Zeichen. Mit dem Erledigt-Eintrag (E-SD-10) verlässt die Zeile die
  Tabelle. **Die Spalte „Modell" entfällt** — K2/K8 gelten programmweit,
  Fable-Schritte stehen im Konzept. Blöcke unter der Tabelle gibt es nur
  für offene Schritte, je höchstens 30 Zeilen: Inhalt, Voraussetzung,
  Konzept, Stand. Die Statuszelle wird **nicht je Arbeitspaket**
  fortgeschrieben; sie nennt Zweig und Konzept, der Statusblock des
  Konzepts sagt das Paket.
- **E-SD-09 Kein Konzept ohne Fahrplanzeile.** Jedes Konzept, das eine
  Instanz umsetzt, hat eine Zeile in Abschnitt 3 — auch Korrekturpakete
  (PK, RP, TB waren keine Zeile). Die Kennung ist der Name; eine
  Schrittnummer ist nicht Pflicht (wie „Kette II").
- **E-SD-10 Erledigt als Tabelle.** Abschnitt 8 wird eine Tabelle, eine
  Zeile je Schritt (4.4): Kennung, Versionen, Datum und PR, letzter Commit
  des Konzepts, Prüfdokument, Kern in einem Satz. **Prüfzahlen stehen im
  Prüfdokument**, nicht in der Erledigt-Zeile — das ändert den Wortlaut
  von K5 und R62 („Prüfzahlen" wird „Verweis auf das Prüfdokument"). Die
  27 Blöcke aus Fassung N werden in Zeilen überführt; ihr Volltext liegt im
  Archiv-2.
- **E-SD-11 Zuarbeiten in drei Gruppen, eine Zeile je Posten** (4.3):
  6.1 *jetzt* (blockiert etwas oder ist seit einem Merge fällig), 6.2 *vor
  Schritt X*, 6.3 *vor v1.0 oder vor der Öffnung*. Höchstens zwei Sätze,
  300 Zeichen; Bedienwege und Erwartungen stehen im Prüfdokument, auf das
  die Zeile verweist. Durchgestrichene Zeilen gibt es nicht mehr — erledigt
  heißt gelöscht, mit Verlaufszeile. Die 107 offenen Zeilen aus Fassung N
  werden 1:1 überführt und dann von der Betreiberin durchgesehen (SD-M1).
- **E-SD-12 Register bleibt, Status ein Satz** (4.5). R1 bis R85 bleiben
  als Zeilen; „Kern" höchstens 160 Zeichen, „Status" höchstens 160 Zeichen,
  dazu die Spalte „Volltext" (Archiv 3, Archiv-2 7 oder das Konzept). Wer
  einen R-Eintrag fortschreibt, ersetzt den Statussatz, statt anzuhängen.
- **E-SD-13 Verlauf in eigener Datei.** Abschnitt 10 wird
  `docs/Rahmenplan-Verlauf.md`: eine Zeile je Fassung, höchstens zwei
  Sätze, 300 Zeichen (4.8). Die Fassungszählung läuft fortlaufend weiter
  (N+1, N+2 …); die Zeilen bis N liegen im Archiv-2. Berichtigungen
  stehen **nur** hier: Wer etwas Falsches findet, ersetzt es an Ort und
  Stelle und schreibt in die Verlaufszeile, was falsch war. Der Rahmenplan
  selbst enthält keinen Satz der Form „hier stand bis Fassung …".
- **E-SD-14 Parallelität (Abschnitt 4)** führt nur geltende Sperren.
  Erfüllte und durchgestrichene Zeilen gehen ins Archiv-2.
- **E-SD-15 Vier Schreibanlässe, sonst keiner.** Der Rahmenplan wird
  geschrieben, wenn (1) ein Schritt beginnt oder endet, (2) eine
  Programmentscheidung fällt, (3) die Reihenfolge sich ändert, (4) eine
  Zuarbeit entsteht oder erledigt ist. Alles innerhalb eines Schritts —
  Arbeitspakete, Befunde, Messungen, Abnahmen — steht im Statusblock des
  Konzepts und im Prüfdokument. Der Wortlaut für `CLAUDE.md` steht in 4.10;
  K5 wird entsprechend angepasst („Abschnitt 3 während der Arbeit" heißt:
  eine Zeile beim Beginn, nicht je Paket).

### 2.3 Aufbau des neuen Backlogs

- **E-SD-16 Kopfzeile fester Form** für jeden offenen Eintrag (4.6):
  Nummer, Titel, `gehört zu`, `Stand`, `seit`. `Stand` ist eines von
  `offen · teilweise · zurückgestellt · nur auf Anlass · nicht umsetzen`.
  `gehört zu` ist eine Kennung aus der Fahrplan-Tabelle (mit oder ohne
  Paket, z. B. `10c AP7`), oder eines von `nächste Backlog-Runde · Zuarbeit
  · Pflegeaufgabe · nach v1.0`. Nr. 198 und Nr. 200 bleiben mit
  `Stand: nicht umsetzen` unter *Offen* (so entschieden in P5c).
- **E-SD-17 Einheitliche Einrückung: fünf Leerzeichen** für jede
  Folgezeile eines Eintrags, unabhängig von der Stellenzahl der Nummer.
  Das erledigt Nr. 196 mechanisch: Fünf Leerzeichen sind für `1. `, `37. `
  und `202. ` gleichermaßen Fortsetzung und nie Codeblock.
- **E-SD-18 Zwei Dateien, eine Zählung.** `Backlog.md` hält Kopf und
  *Offen*; `Backlog-Erledigt.md` hält *Erledigt* wörtlich (die 175
  Einträge aus Fassung N werden nicht umgeschrieben) sowie den Werdegang
  der Nummernvergabe aus dem alten Kopf. Der Prüfschritt „Backlog — keine
  Nummer zweimal" liest **beide** Dateien und meldet zusätzlich jede
  Nummer, die in beiden steht. Ein Eintrag, der künftig erledigt wird,
  zieht in Kurzform um: Kopfzeile mit `Stand: erledigt`, dazu eine Zeile
  „erledigt mit Web X.Y.Z (Datum)" — der Volltext bleibt in der Historie.
- **E-SD-19 Zeitpunkt und Zweig.** SD-00 läuft auf dem P5c-Zweig vor AP2
  (nur `CLAUDE.md`, Rahmenplan 9, eine Verlaufszeile). SD-01 bis SD-04
  laufen **nach dem Merge des 10c-PR** auf einem eigenen Zweig von `main`
  als eigener PR — nicht im 10c-PR, damit der Umbau von 13 000 Zeilen
  getrennt vom Web-Code gelesen werden kann. Der 10c-Erledigt-Eintrag steht
  dann schon auf `main` in der alten Form und wird wörtlich archiviert.
  **Während SD-01 bis SD-04 schreibt kein anderer Zweig Rahmenplan oder
  Backlog** (Sperre in Abschnitt 4; Schritt 17 und PK-06 bis -08 warten
  mit ihrer Buchführung, bis der SD-PR gemergt ist).
- **E-SD-20 Der Backlog-Kopf hält Regeln, keinen Werdegang** (4.9):
  höchstens 60 Zeilen — Nummern dauerhaft; 4, 6, 7 frei (5 nach E-SD-25);
  Prüfung gegen `origin/main` und offene PRs; die Reservierungstabelle.
  Der Werdegang (59–62, 63–67, 215→223, 267→276) geht wörtlich in den Kopf
  von `Backlog-Erledigt.md`.
- **E-SD-21 Reservierung als Regel.** Ein Zweig, der Backlog-Nummern
  vergeben könnte, trägt beim Anlegen eine Zehnerspanne in die
  Reservierungstabelle ein und pusht das **zuerst**; er vergibt keine
  Nummer außerhalb seiner Spanne. Das ist die Praxis seit 241–249 und
  250–259, nur nicht als Regel. Ein zusätzlicher Riegel ist nicht nötig:
  Seit „Require branches to be up to date" (Ruleset Main Protect) läuft der
  Doppelprüfschritt auf dem zusammengeführten Stand.

### 2.4 Regeln des Vorhabens

- **E-SD-22 Keine Nummern im Konzept.** Fassungen, Backlog-Nummern und
  die Fahrplanzeile SD vergibt die einspielende Instanz (K3-analog).
- **E-SD-23 Nichts wird umformuliert, was ins Archiv geht.** Archiv-2 und
  `Backlog-Erledigt.md` sind byteweise der alte Text (Prüfpunkt P-SD-02,
  P-SD-08). Gekürzt wird nur, was im neuen `Backlog.md` unter *Offen*
  steht, und nur mit Werdegang-Zeile.
- **E-SD-24 Abschnittsnummern bleiben.** Der neue Rahmenplan hat die
  Abschnitte 1 bis 10 wie bisher. 5 und 10 werden Verweisabschnitte
  („Die Zuordnung steht in den Backlog-Kopfzeilen; Übersicht:
  `python3 tools/steuerung/uebersicht.py`" bzw. „Der Verlauf steht in
  `Rahmenplan-Verlauf.md`"). Damit lösen die rund 80 Verweise „Rahmenplan
  Abschnitt N" in anderen Dokumenten weiter auf.
- **E-SD-25 Nr. 5.** Die Instanz sucht die Nummer in der Historie
  (`git log -S` auf `docs/Backlog.md`). Ist der Eintrag rekonstruierbar,
  steht er unter *Erledigt* mit Herkunftsvermerk wie 1, 9, 10 und 12; sonst
  wird 5 wie 4, 6 und 7 als dauerhaft frei geführt. Nr. 199 ist damit
  erledigt.
- **E-SD-26 Nr. 177 und 193** erledigen sich mit dem Schnitt: Der Verlauf
  bis N liegt im Archiv-2 (Reihenfolge dort ist Geschichte), der neue
  Verlauf beginnt sauber; R42 und R64 bekommen in SD-01 ihren Statussatz.
  Nr. 188 bleibt offen — dieses Konzept baut keine Verweisprobe, fährt aber
  einmalig eine von Hand (P-SD-05).
- **E-SD-27 Decken sind Prüfwerte, keine Ziele.** Eine Decke liegt so, dass
  der Schnitt sie mit Luft unterschreitet (Rahmenplan: Decke 500 Zeilen,
  beim Schnitt gemessen unter 400). Wer eine Decke anheben will, begründet
  es in der Verlaufszeile — nicht im Werkzeug nebenbei.

**Zum Gegenlesen** (Vorschläge, die über die fünf Entscheidungen hinausgehen;
ohne Widerspruch gelten sie):

1. Decke 20 Zeilen je offenem Eintrag heißt: 66 Einträge einmalig kürzen
   (Median ist 23). Preis: Messreihen in Nr. 37, 80, 202 sind danach nur
   noch per Commit-Verweis lesbar.
2. Decke des Rahmenplans 500 Zeilen statt „unter 400" — 400 ist das Ziel
   beim Schnitt, 500 die rote Linie (E-SD-27).
3. Die Spalte „Modell" der Fahrplan-Tabelle entfällt (E-SD-08).
4. Blockquotes im Rahmenplan: Decke 0. Warnungen stehen als fetter Satz.
5. SD-01 bis SD-04 als eigener PR nach dem 10c-Merge, nicht im 10c-PR
   (E-SD-19).

### 2.5 Entscheidungen und Befunde aus der Umsetzung (SD-01, 26.09.2026)

Von der umsetzenden Instanz entschieden, zur Kenntnis und zum Widerspruch.
Die Antworten der Betreiberin auf Q-SD-01 folgen als **E-SD-34 ff.** (das
Konzept sagte „E-SD-28 ff."; die Nummern sind belegt, die Reihenfolge nicht).

- **E-SD-28 6a bleibt, 6b wird Verweis.** Die Zehn-Schritte-Tabelle zur
  Staging-Einrichtung und die Werte der Variablen stehen weiter im
  Rahmenplan unter 6a: E-KH-21 bindet die Schrittnummern, und
  `auslieferung.yml` nennt „Rahmenplan 6a, Schritte 1 bis 3" in seinen
  Fehlermeldungen, `Technik.md` 6 zeigt dreimal dorthin. Die Kästen und der
  Werdegang liegen nur im Archiv-2. 6b ist ein Absatz, der auf
  `Pruefablauf.md` 2.3 (die drei Lagen) und Archiv-2 6b (die Maske) zeigt —
  `Pruefablauf.md` 2.3 nennt „Rahmenplan.md 6b" als Ort der Maske; SD-04
  entscheidet, ob der Satz dort auf Archiv-2 zeigen soll.
- **E-SD-29 Welche Zeilen der Fahrplan behält.** Neben den sieben offenen
  Schritten (12, 12a, 13, 14, 17, 18, Betriebsübergang) und SD stehen
  **Kette II** (M2 und Abschluss bei PK), **11** (Paketschnitte offen) und
  **6** (Teil C: Play Console, Android 1.0.0) in der Tabelle, weil ihre
  Konzepte liegen und Reste offen sind; **AR, BV, PK** sind neu (E-SD-09,
  P-SD-06: 9 von 9 Konzepten mit Zeile). Blöcke gibt es nur für 12 und 12a;
  13, 14 und der Betriebsübergang leben in ihrer Zeile — ihr Volltext liegt
  im Archiv-2, und die Decke von 500 Zeilen ließ keine drei Blöcke mehr zu.
- **E-SD-30 Drei Erledigt-Zeilen aus Fahrplanzeilen.** Schritt 1 (S4 Merge),
  2 (S6) und 3 (S5 Konzept) hatten in Fassung 124 keinen Block in Abschnitt
  8; ihre Erledigt-Zeile ist aus der Fahrplanzeile gebildet (Versionen,
  Datum, PR nach GitHub). Korrekturstufen (148/149, Web 21.1.3) bekommen keine
  Zeile — sie sind keine Schritte.
- **E-SD-31 Zuarbeiten eins zu eins, Erledigungen nur vermerkt.** 67 nicht
  durchgestrichene Zeilen sind überführt, zwei neue dazu (der Tag für 10c
  und die Kette-II-Prüfpunkte 26 bis 28 aus der Fahrplanzeile). Dreizehn
  Zeilen tragen den Vermerk „vermutlich erledigt" oder „löschen?" mit dem
  Grund; gelöscht ist keine — das entscheidet SD-M1 (E-SD-11). Die
  Gruppierung folgt der alten Spalte „Wann".
- **E-SD-32 Drei Fassungen in einem Commit.** 125 ist der Schnitt, 126 die
  Ergänzungen (E-SD-29, -30, neue Zuarbeiten), 127 die Berichtigungen (K7
  und R40 nannten den Push auf `main` als Produktiv-Deploy; Android auf
  `origin/main` ist 0.15.1; S7 ist PR #27 nach GitHub, nicht #28/#30;
  „Branch-Schutz" ist bis auf den 2FA-Zwang erledigt). Eine Verlaufszeile
  von 300 Zeichen trägt nicht alles; je Sache eine Zeile ist das Muster aus
  4.8.
- **E-SD-33 Fächerung in SD-01.** Das Konzept sah für SD-01 keine Fächerung
  vor. Die Sitzung lief mit der Ansage „Ultracode" der Betreiberin; deshalb
  ist das **Lesen** der 105 offenen Backlog-Einträge (Zuordnung, Stand,
  Aufnahmedatum, Zeilenzahl — Vorarbeit für SD-M1 (b) und SD-02) auf 32
  lesende Agenten gefächert worden: je Block von neun Einträgen zwei
  unabhängige Leser, ein Schiedsrichter bei Abweichung (8 Fälle). Alles
  Schreiben blieb seriell (`CLAUDE.md` 7: Messung ja, Text nein).

**Befunde.**

- **F-SD-01 Ziel < 400 Zeilen verfehlt: 487.** Die Decke 500 hält (13 Zeilen
  Luft). Das Konzept rechnete mit 27 Erledigt-Blöcken und schätzte die
  Zuarbeiten nicht: 85 Register-, 69 Zuarbeiten- und 34 Erledigt-Zeilen sind
  allein 188 Zeilen, dazu 6a (21) und 14 Fahrplanzeilen. Nach SD-M1 schrumpft
  6.1 (dreizehn Zeilen als vermutlich erledigt markiert). → Q-SD-02.
- **F-SD-02 Vier Aussagen der Fassung 124 waren falsch** und sind mit
  Verlaufszeile 127 ersetzt: K7 und R40 (2) (Produktiv-Deploy), der Kopf
  (Android 0.15.0 statt 0.15.1), die PR-Nummern von S7 (#28/#30 statt #27),
  die Zuarbeit „Branch-Schutz" (der Zweigschutz steht seit dem 21.09.2026).
- **F-SD-03 Abschnitt 5 der Fassung 124 war weiter auseinander als gezählt:**
  91 Zeilen gegen 105 offene Punkte; **22 offene Nummern ohne Zeile**, **8
  Zeilen zu nicht mehr offenen Nummern** (Prüfdokument 3.3). SD-02 ersetzt
  die Tabelle durch Kopfzeilen; bis dahin verweist Abschnitt 5 auf Archiv-2.
- **F-SD-04 Die Zahlen des Konzepts zum Backlog sind gealtert:** 105 offene
  Einträge statt 113 (BV, P5c und AR haben ausgetragen und angelegt), **55
  über 20 Zeilen** statt 66, **40 ohne genannten Schritt** statt „mindestens
  18", 11 ohne Aufnahmedatum im Text (21, 23, 36, 37, 50–53, 55, 272, 273 —
  `seit` kommt dort aus `git log`), 2 mit „umgesetzt, Prüfung offen" (213,
  227 — nicht 238/239, die sind seit Fassung 110 erledigt).

**Antworten der Betreiberin (SD-M1, 26.09.2026, anklickbar je vier Zeilen):**

- **E-SD-34 Q-SD-01 (a) — 27 Zuarbeiten sind erledigt und gestrichen**
  (Rahmenplan Fassung 128). Aus 6.1 (17): `update.php` nach dem Merge von
  10c · Secret `STAGING_TOTP` · Staging-Schritte 7 bis 9 (mit 6a) ·
  Signaturschlüssel des APK verwahren · eigenes Passwort prüfen ·
  2FA-Zwang in der GitHub-Organisation · D-U-N-S-Nummer · GitHub-Umgebungen
  und Pflichtfreigabe · GitHub-App und `CIQ_GERAETE_URL` · V1 bis V9 der
  Protokollierung · Nachträge an P5a · P5a-Reste · Data Layer auf echter
  Hardware · Dienst-Test am S24 · DNS und TLS für `nadoku.gen-em.org` ·
  offene Fragen aus Konzept BR · „Freigabe je Konzept" (Regel, kein Posten).
  Aus 6.2 (7): Play-Console-Organisationskonto · Play App Signing ·
  Fable-Instanz für den Review · Symbole für Handy- und Web-App ·
  Uhr-Darstellungen und Handy-Screenshots · NEF-Logo und Favicon ·
  Logovorlagen in den Markenfarben. Aus 6.3 (3): FTPS- und
  SFTP-Zugangsdaten · SMTP auf Produktiv · Sichtprüfung in WebKit und
  Firefox. 6a: Schritte 7, 8, 9 abgehakt. **Es bleiben 42 Zeilen** (6.1: 25,
  6.2: 5, 6.3: 12), ausdrücklich bestätigt offen — darunter der Tag für
  10c, alle Prüflisten aus P5c, RW, BR und Kette II, die fünf
  S10-Betriebsposten, die Abnahme S6 und die Datenschutzerklärung.
  **Folge für die Steuerung:** Schritt 6 Teil C ist nicht mehr blockiert
  (Fahrplanzeile auf „offen", R65 nachgezogen).
- **F-SD-05 Ein Widerspruch bleibt stehen, mit Absicht:** Die Zeile
  „`update.php` für S9/9a und P5b auf Produktiv" hat die Betreiberin als
  offen bestätigt, obwohl Konzept PK zu M1 (21.09.2026) „Migrationen von
  Hand ausgeführt" sagt und Produktiv seither Web 20.26.3 und höher fährt.
  Die Zeile steht mit diesem Hinweis in 6.1; klären lässt es sich nur unter
  Betrieb → Updates auf Produktiv.

- **E-SD-35 Q-SD-01 (b) — alle 40 Vorschläge übernommen** (Betreiberin,
  26.09.2026; Tabelle im Prüfdokument 3.3). SD-02 schreibt sie so in die
  Kopfzeilen: 17 für 21 Nummern (92, 170, 172, 207, 209, 216, 232, 239, 266,
  271, 272, 273, 275, 277, 283, 323, 333, 336 und die drei ohne Zeile in
  Abschnitt 5), PK für 213, 234, 236, 237, 240, 290; 13 für 23, 154, 157,
  201, 229; `nach v1.0` für 50, 51, 52, 55, 90; 18 für 210, 228; 12 für 263;
  14 für 187; `Zuarbeit` für 261; `Pflegeaufgabe` für 280. Der Stand kommt
  aus dem Text (228 und 232 „nur auf Anlass", 229 „zurückgestellt", 210,
  213, 234, 283 „teilweise").
- **E-SD-36 Q-SD-02 — Decke 500 reicht** (Betreiberin, 26.09.2026). E-SD-27
  bleibt: 500 ist der Prüfwert im Werkzeug, „unter 400" war ein Wunsch beim
  Schnitt und wird nicht weiterverfolgt. Nach SD-M1 misst der Rahmenplan
  **460 Zeilen**; SD-04 kürzt nicht weiter.

**Aus SD-02 (26.09.2026):**

- **E-SD-37 `bestand.py` liest beide Backlog-Dateien** (Vorgriff auf SD-03,
  „Doppelprüfung über zwei Dateien"). Die Regel `backlog` hält die
  Anlass-Zeilen der Prüfmittel gegen die Nummern im Backlog; sie nennen auch
  erledigte Nummern, und die stehen seit SD-02 in `Backlog-Erledigt.md`.
  Ohne die Änderung wäre der Riegel mit dem ersten SD-02-Commit rot geworden
  („Nr. … steht nicht im Backlog"). `backlog_zeilen()` liest jetzt
  `docs/Backlog.md` **und** `docs/Backlog-Erledigt.md` (fehlt die zweite,
  nur die erste — die Selbstprobe baut nur eine); eine Nummer in beiden
  Dateien ist damit dieselbe Doppelung wie zweimal in einer, und die
  Selbstprobe hat den Fall (141 Fälle, 0 Fehlschläge; vorher 140).
- **E-SD-38 Ziele: `17` statt „Backlog-Runde", erledigte Schritte
  umgelenkt.** Die Fahrplan-Kennung der Backlog-Runde 4 ist `17`; jeder
  Eintrag mit „Zuordnung: Backlog-Runde" trägt sie (54 Einträge tragen 17),
  das feste Wort `nächste Backlog-Runde` bleibt für die Zeit nach Schritt 17
  frei. Einträge, deren Text einen erledigten Schritt nannte, tragen das
  Ziel, das ihr offener Rest verlangt: 36 (P3) und 37 (S2) → 17; 80 (10c) →
  `Zuarbeit` (die Datenschutzerklärung, 6.3); 198 und 200 (10c, „wird nicht
  umgesetzt") → 17 mit `Stand: nicht umsetzen`; 202 (Schritt 15, der Rest
  ist Beifang) → 17, `nur auf Anlass`; 233 („S10c") → 18 (wie Archiv-2 5);
  260 (Kette II — AP5 liegt auf `main`, die Kommentare stehen noch) → 17;
  264 → PK und 265 → 17, beide `nur auf Anlass`; 295 und 335 (10c, P5c) →
  17; 21 (P6) → 12; 46 → 14 (ein Paket mit 187, Nr. 324 — die Lesung hatte
  „nach v1.0" aus dem Text geschlossen); 62 → 17, `offen` (die Logovorlagen
  liegen vor, E-SD-34).
- **E-SD-39 Erledigt im Text, nicht verschoben: `teilweise`.** Drei
  Einträge tragen nichts Offenes mehr — 140 (Wache, Deploy-Tor,
  Zweigschutz, 2FA), 227 (PK-04/1b) und 295 (`schritt_statistik()` im
  Messstand steht) —, und Konzept SD verschiebt keinen Eintrag (3.2, SD-02
  Schritt 6). Sie stehen mit `Stand: teilweise` und sagen im Text, dass nur
  die Verschiebung fehlt; Schritt 17 holt sie nach (F-SD-06). Nr. 177 steht
  `offen` statt `zurückgestellt`, weil SD ihn erledigt.
- **E-SD-40 Mechanik und Handarbeit.** Ein Skript im Scratchpad (nicht
  eingecheckt) setzt zusammen: alter Stand aus `HEAD`, je Eintrag die
  Kopfzeile aus Lesung, E-SD-35 und E-SD-38, Einrückung auf fünf
  Leerzeichen, der erste Absatz neu umbrochen, wo der Titel eine Zeile
  teilte; Reihenfolge aufsteigend nach Nummer (in Fassung N standen 259 vor
  258 und 276 vor 271). **52 Einträge über 20 Zeilen sind von Hand gekürzt**
  (nach 4.7; drei mit 21 Zeilen — 263, 266, 318 — fielen durch das
  Zusammenziehen des Titels auf 20 und bleiben ungekürzt); die 53 übrigen
  sind bis auf Kopfzeile, Einrückung und Umbruch unverändert, ihre
  „Zuordnung:"-Sätze bleiben stehen — maßgeblich ist die Kopfzeile. Zwei
  Texte sind gezielt geändert: Nr. 294 (Stand SD-02) und Nr. 297 (F-SD-09).
- **E-SD-41 Kopf ohne Werdegang, Werdegang wörtlich.** Der neue Kopf hält
  52 Zeilen (Decke 60): Zweck, Nummern, Kopfzeilen-Grammatik, Fundstellen,
  Reservierung (E-SD-21) mit Tabelle — eine Zeile, „ab 339 frei" (338 war
  AR reserviert und blieb frei; BV und AR sind gemergt, keine Spanne
  offen). Die Absätze zur Nummernvergabe aus dem alten Kopf (Zeilen 34 bis
  271 der Fassung N) stehen wörtlich in `Backlog-Erledigt.md` unter
  „Werdegang der Nummernvergabe", davor ein Absatz zu Nr. 5 (E-SD-25: in
  keiner Fassung seit `7154ec5` vergeben — dauerhaft frei).
- **F-SD-06 Drei Einträge sind erledigt und stehen unter *Offen*** (140,
  227, 295) — siehe E-SD-39; Liste für Schritt 17.
- **F-SD-07 E-SD-26 deckt Nr. 193 nur zur Hälfte.** Register (R42, R64)
  und Nr. 80 sind nachgezogen; die zwei Sätze in `docs/Technik.md` („**Die
  Auswertung ist P5** …") und `docs/Handbuch.md` 10 („Bevor eine Auswertung
  entsteht …") stehen noch (nachgemessen 26.09.2026). SD-04 zieht sie nach
  (nur `docs/`, keine Web-Stufe); Nr. 193 trägt bis dahin `Stand:
  teilweise`.
- **F-SD-08 GitHub zählt die Liste fort.** `cmark-gfm` macht aus den 105
  Einträgen eine Liste `<ol start="21">`; ein Browser zeigt 21, 22, 23 …
  statt 21, 23, 36 — bei jedem Eintrag außer dem ersten steht auf GitHub
  eine andere Nummer als im Markdown. Das war in Fassung N genauso und ist
  kein Fund von SD-02, aber einer, den P-SD-11 nicht sieht (es zählt `<li>`,
  nicht Nummern). Weg für Schritt 17: ein Element zwischen den Einträgen,
  das die Liste beendet, oder die Nummer im Titel wiederholen — beides
  ändert E-SD-16 und gehört nicht in dieses Paket.
- **F-SD-09 Ein Berichtigungsmuster im Backlog** (Nr. 297, „hier stand bis
  Web 21.1.0 …", der einzige Treffer) — die Klammer ist gestrichen, weil die
  Decke 0 ab SD-03 für `Backlog.md` gilt; der Eintrag ist sonst unverändert.
- **F-SD-10 Nr. 260 war veraltet:** Die Kommentare „beider FTPS-Schritte"
  in `wartung_lib.php` und `adminbackup_lib.php` stehen noch, obwohl
  Kette II/AP5 mit `ausliefern-lauf.yml` auf `main` liegt — der Eintrag
  wartete auf ein Paket, das längst gemergt ist. Er sagt es jetzt; die
  Zeilen brauchen eine Web-Stufe (17).

**Aus SD-03 (26.09.2026):**

- **E-SD-42 Zwanzig Decken, an einer Stelle.** `decken.py` führt die
  Tabelle aus Abschnitt 5 als zwanzig benannte Decken (die Fahrplan-Zelle
  „Status" als zwei: Länge und Vokabular; das Berichtigungsmuster je Datei;
  das Rendering als `<pre>` = 0 und `<li>` = Einträge). Die Zahlen stehen
  **im Werkzeug** und in seiner `LIESMICH.md`, nicht in `pruefung.yml`
  (6.11) und nicht mehr maßgeblich in diesem Konzept — Abschnitt 5 hier
  ist der Vorschlag, gültig ist `decken.py`. Doppelte Nummern innerhalb
  einer Datei misst weiter `bestand` (Regel `backlog`, seit E-SD-37 über
  beide Dateien); `decken.py` misst die Schnittmenge.
- **E-SD-43 Riegel und Muster zugleich; kein eigener Schritt „Backlog —
  keine Nummer zweimal".** `steuerung` steht in `riegel.proben` (läuft in
  jeder Stufe, wird im Tor mit `--riegel` gegengelesen) **und** als Muster
  (`docs/Rahmenplan*.md`, `docs/Backlog*.md`, `CLAUDE.md`), damit die
  erzeugte Tabelle in `Pruefablauf.md` 4 die Berührung nennt; `bestand`
  lässt beides zu. Den Schritt, den 3.2 für „Backlog — keine Nummer
  zweimal" vorsah, gibt es seit BR-03 nicht mehr als eigenen Schritt — die
  Prüfung ist die Regel `backlog` in `bestand`, und die liest seit E-SD-37
  beide Dateien. Die Rendering-Probe (Nr. 196) läuft in `decken.py`, nicht
  als Shell-Zeile im Workflow: eine Stelle, und der Prüfstand fährt sie
  örtlich mit (Grundsatz 2).
- **E-SD-44 Fächerung: keine.** Das Konzept erlaubte zwei Agenten für die
  zwei Werkzeugdateien und den Workflow; drei kleine Dateien mit einem
  gemeinsamen Vokabular sind seriell schneller geschrieben als abgestimmt.
- **F-SD-11 Eine Fahrplanzeile ohne Status.** Die Zeile „Betriebsübergang"
  trug in der Statuszelle nur „—"; das Wegwerfskript von SD-01 hatte „—"
  als erstes Wort durchgelassen, `decken.py` nicht (Vokabular nach
  E-SD-08). Berichtigt mit Fassung 129 („offen — beginnt mit v1.0 …"), die
  Verlaufszeile sagt es. Der erste Lauf des Werkzeugs hat damit einen
  Befund geliefert, bevor er grün war — Grundsatz 7 in Reinform.

**Aus SD-04 (26.09.2026):**

- **E-SD-45 Erledigt-Zeile und Löschung erst nach der Freigabe.** 3.2 las
  sich, als schriebe SD-04 die Erledigt-Zeile selbst; `CLAUDE.md` 7,
  Rahmenplan K5, R62 und 9 sagen einhellig „nach der Freigabe des
  Abschlusses". SD-04 setzt die Fahrplanzeile SD auf „gebaut, PR offen" und
  öffnet den PR; die Erledigt-Zeile (neue Form, E-SD-10), die Verlaufszeile
  dazu und die Löschung dieses Konzepts schreibt die Instanz, die die
  Freigabe bekommt — wie bei AR und BV, deren Abschlüsse ebenso offen
  stehen. Die Sperre in Abschnitt 4 sagt schon „bis der SD-PR gemergt ist"
  und braucht keine weitere Zeile.
- **E-SD-46 Verweisprobe vollständig, wo es billig ist.** P-SD-05 sah für
  „Backlog Nr. N" eine Stichprobe von 30 vor; ein Skript hält stattdessen
  **alle 773 Verweise** (202 verschiedene Nummern, in `docs/`, `server/`,
  `tools/`, `android/`, `watch/`, `.github/`, ohne Backlog, Changelog und
  Archive) gegen die Nummern beider Backlog-Dateien: **0 unauflösbar**.
  „Rahmenplan Abschnitt N": **30**, alle auf 1 bis 9 — die Abschnitte gibt
  es (E-SD-24), 5 und 10 sind Verweisabschnitte. `Rahmenplan-Archiv…md`:
  **37** Pfadnennungen, beide Dateien vorhanden. „Fassung NN" ist nicht
  zählbar zu prüfen — von 168 Treffern außerhalb der Archive meinen die
  meisten Container- und Nutzlastfassungen —, löst aber strukturell auf:
  1–15 im Archiv, 16–124 in Archiv-2 Abschnitt 10, 125–130 im Verlauf; die
  Doppelungen 35 bis 39 sind Nr. 177 und bleiben, wie sie sind.
- **F-SD-12 `Pruefablauf.md` 2.3 zeigte auf `Rahmenplan.md` 6b,** und 6b
  ist seit SD-01 nur noch ein Verweis auf `Rahmenplan-Archiv-2.md` 6b — ein
  Leser lief zwei Sprünge. Der Satz zeigt jetzt auf das Archiv (Zahl der
  „Fallen" weggelassen: 2.3 sagte drei, 6b vier — welche stimmt, klärt
  Schritt 17 mit Nr. 188). F-SD-07 ist erledigt: `Technik.md` und
  `Handbuch.md` 10 sagen, dass die Auswertung läuft und der Datenschutztext
  aussteht (Nr. 80); Nr. 193 kann mit dem Abschluss nach *Erledigt*.

**Aus dem Pull Request (26.09.2026):**

- **F-SD-13 Die Selbstprobe von `decken.py` setzte einen grünen Baum
  voraus.** P-SD-16 (Nr. 332 mit einer 21. Zeile, Lauf 312) färbte Stufe 1
  rot — aber an `decken.py --selbstprobe`, nicht an `--stellen`: Die
  Gegenprobe „die Kopie misst wie der Baum" verlangte null gerissene Decken,
  und der Fall `backlog-eintrag` verlangte eine NEUE rote Decke, die es auf
  einem schon roten Baum nicht gibt. Datei und Zeile des Risses nannte das
  Tor deshalb nicht. Genau dafür ist die Gegenprobe da (Grundsatz 7).
- **E-SD-47 Die Selbstprobe zählt Stellen, nicht Farben.** Je Decke die
  Zahl der Stellen vor und nach dem eingebauten Riss; ein Fall ist grün,
  wenn seine Decke danach mehr Stellen hat. Die Gegenprobe vergleicht die
  Kopie mit dem Baum, statt Grün zu verlangen. Damit läuft die Selbstprobe
  auf jedem Baum (örtlich auf dem roten: 22 Fälle, 0 Fehlschläge), und
  `--stellen` kommt im Tor immer dran. Die Reihenfolge im Schritt
  (Selbstprobe zuerst) bleibt — sie ist die Regel aus `Pruefablauf.md` 6.12.

---

## 3. Arbeitspakete

### 3.1 Übersicht

| Paket | Inhalt | Dateien | Wann |
|---|---|---|---|
| **SD-00** | Schreibregeln (E-SD-15) in `CLAUDE.md` und Rahmenplan 9; Verlaufszeile | `CLAUDE.md`, `docs/Rahmenplan.md` | **sofort**, P5c-Zweig, vor AP2 |
| **SD-01** | Rahmenplan schneiden: Archiv-2, Verlauf-Datei, neuer Rahmenplan nach Vorlagen | `docs/Rahmenplan.md`, `docs/Rahmenplan-Archiv-2.md`, `docs/Rahmenplan-Verlauf.md` | nach 10c-Merge |
| **SD-M1** | Betreiberin sieht Zuarbeiten (6.1–6.3) und unzugeordnete Backlog-Punkte durch | — | nach SD-01, vor SD-02 |
| **SD-02** | Backlog schneiden: Erledigt-Datei, Kopf, Kopfzeilen, Einrückung, Kürzung | `docs/Backlog.md`, `docs/Backlog-Erledigt.md` | nach SD-M1 |
| **SD-03** | Werkzeug `tools/steuerung/` (Decken, Übersicht), Stufe-1-Schritt, Doppelprüfung über zwei Dateien, `pruefablauf.json`, `Pruefablauf.md` 6.11 | `tools/steuerung/`, `.github/workflows/pruefung.yml`, `tools/pruefstand/pruefablauf.json`, `docs/Pruefablauf.md` | nach SD-02 |
| **SD-04** | Regeln nachziehen (`CLAUDE.md` 2/7/9, K5, K9, R62, Rahmenplan 9), Verweisprobe, Changelog `[Werkzeug: …]`, Prüfdokument, Erledigt-Eintrag SD, PR | `CLAUDE.md`, `docs/Rahmenplan.md`, `docs/CHANGELOG.md`, Prüfdokument | Abschluss |

### 3.2 Pakete

#### SD-00 — Schreibregeln sofort

- **Wer:** die P5c-Instanz auf ihrem Zweig, als nächster Commit vor AP2.
  Anweisung: `Einschub-SD-Sofort-2026-09-24.md`.
- **Was:** (a) `CLAUDE.md` 2 bekommt den Absatz aus 4.10 als fünften
  Punkt „Rahmenplan"; (b) Rahmenplan Abschnitt 9 bekommt denselben Inhalt
  als einen Spiegelstrich; (c) eine Verlaufszeile in Abschnitt 10 (nächste
  Fassung); (d) der Kopf des Rahmenplans wird ab dieser Fassung nicht mehr
  fortgeschrieben — nur Fassungsnummer und der Absatz „Stand" ändern sich,
  bis SD-01 schneidet.
- **Fächerung:** keine.
- **Abnahme:** `CLAUDE.md` 2 hat fünf Punkte; Abschnitt 9 hat den
  Spiegelstrich; die Verlaufszeile nennt „SD-00". Die Fassungen von AP2 bis
  zum 10c-Abschluss enthalten danach keinen neuen Satz der Form „hier stand"
  oder „berichtigt mit Fassung" außerhalb von Abschnitt 10 (P-SD-01).

#### SD-01 — Rahmenplan schneiden

- **Vorbedingung:** 10c-PR gemergt; `git fetch origin main`; kein anderer
  offener Zweig mit Änderungen an `docs/Rahmenplan.md` oder
  `docs/Backlog.md` (nachgesehen in den offenen PRs; Ergebnis ins
  Prüfdokument).
- **Schritte:**
  1. **N feststellen:** die Fassungsnummer von `docs/Rahmenplan.md` auf
     `origin/main`, Commit merken.
  2. **Archiv-2 anlegen:** `docs/Rahmenplan-Archiv-2.md` = Kopf nach 4.2
     plus **wörtlich** der gesamte Inhalt von `docs/Rahmenplan.md`@N ab
     dessen erster Zeile, einschließlich seiner Überschrift.
  3. **Verlauf-Datei anlegen:** `docs/Rahmenplan-Verlauf.md` nach 4.8 mit
     dem Hinweis, dass Fassungen 1–15 in Archiv 1 und 16–N in Archiv-2
     stehen; erste Zeile ist die Fassung N+1 (dieser Schnitt).
  4. **Neuen Rahmenplan schreiben** nach 4.1 bis 4.5, Abschnitt für
     Abschnitt aus Fassung N:
     - 1 Ziel und Nicht-Ziele: übernehmen.
     - 2 Regeln: K1–K9 und Dauerpflichten übernehmen; K5, K9 nach E-SD-10
       und E-SD-15 fassen; die Kursiv-Nachsätze („Bis PK-01 stand hier …",
       „Berichtigt mit Fassung 33 …") entfallen.
     - 3 Fahrplan: Reihenfolgesatz; Tabelle nur mit nicht erledigten
       Zeilen (Stand N: 10c, 17, 18, 12, 12a, 13, 14, Betriebsübergang, dazu
       PK-06 bis -08 und **SD**); Statuszelle neu nach Vokabular; Blöcke
       nur für diese Schritte, je ≤ 30 Zeilen.
     - 4 Parallelität: nur geltende Sperren; dazu die Sperre aus E-SD-19,
       solange SD läuft.
     - 5 Verweisabschnitt (E-SD-24).
     - 6 Zuarbeiten: alle **nicht** durchgestrichenen Zeilen aus N in die
       drei Gruppen, je ≤ 300 Zeichen mit Verweis auf das Prüfdokument, das
       den Bedienweg trägt. Die Zahl der Zeilen vor und nach dem Umbau ins
       Prüfdokument.
     - 7 Register: 85 Zeilen nach 4.5; R42 und R64 bekommen ihren
       Statussatz (Nr. 193).
     - 8 Erledigt: 27 Blöcke plus 10c als Zeilen nach 4.4.
     - 9 Pflege: Regeln aus 4.10, plus „Der Stand von `main` wird gemessen"
       (bleibt) und „Keine Berichtigung im Text" (neu).
     - 10 Verweisabschnitt (E-SD-24).
  5. Kopf nach 4.1 zuletzt, gemessen an `origin/main`.
- **Fächerung:** keine — eine Datei, ein Ton.
- **Abnahme:** P-SD-02 bis P-SD-06.

#### SD-M1 — Durchsicht durch die Betreiberin

- **Vorlage der Instanz:** (a) die drei Zuarbeiten-Gruppen aus dem neuen
  Abschnitt 6 mit `seit`-Datum je Zeile; (b) die Liste der offenen
  Backlog-Nummern, deren Eintrag keinen Schritt nennt (Stand N: mindestens
  250–252, 259, 265, 266, 270–277, 280, 281, 283, 284 — die Instanz liest
  jeden Eintrag; was dort steht, gilt), mit dem Vorschlag `nächste
  Backlog-Runde` je Nummer.
- **Antwort der Betreiberin (Q-SD-01):** welche Zuarbeiten erledigt sind
  (werden gelöscht, eine Verlaufszeile), welche Zuordnung die
  unzugeordneten Nummern bekommen.
- Die Instanz **wartet** hier; die Antwort geht als E-SD-34 ff. in dieses
  Konzept (E-SD-28 bis -33 sind seit SD-01 belegt, 2.5).
- **Vorlage (SD-01, 26.09.2026):** (a) `docs/Rahmenplan.md` Abschnitt 6 —
  6.1 mit 42, 6.2 mit 12, 6.3 mit 15 Zeilen, jede mit `seit`; dreizehn
  Zeilen tragen einen Vermerk „vermutlich erledigt" (Liste im Prüfdokument
  3.1). (b) Die 40 offenen Nummern ohne genannten Schritt mit der alten
  Zuordnung aus Abschnitt 5 und dem Vorschlag je Nummer: Prüfdokument 3.3.
  Vorgeschlagen sind 17 (nächste Backlog-Runde) für 21 Nummern, 13 für fünf,
  PK für sechs, `nach v1.0` für fünf, 18 für zwei, 12, 14, Zuarbeit und
  Pflegeaufgabe je einmal — der Standardvorschlag `nächste Backlog-Runde`
  greift nur, wo weder Abschnitt 5 noch der Inhalt etwas anderes nahelegt.

#### SD-02 — Backlog schneiden

- **Schritte:**
  1. `docs/Backlog-Erledigt.md` anlegen: Kopf nach 4.9 (b), darunter
     wörtlich der Abschnitt *Erledigt* aus `docs/Backlog.md`@N (ab
     `## Erledigt`), davor wörtlich die Werdegang-Absätze des alten Kopfes
     (die Absätze „Zu den Nummern …", „Diese elf trugen …", „Und er hat sie
     beim Merge …", „Zu Nr. 5 …" nach E-SD-25).
  2. `docs/Backlog.md` neu: Kopf nach 4.9 (a) mit der Reservierungstabelle
     (nur Spannen, die noch nicht vollständig gemergt sind); dann `## Offen`
     mit allen 113 Einträgen (Stand N; die Instanz zählt neu).
  3. Je Eintrag: Kopfzeile nach 4.6. `gehört zu` aus dem Text des Eintrags,
     aus Rahmenplan-Abschnitt 5 der Fassung N oder aus SD-M1; `Stand` aus
     dem Text (Vermerke „teilweise erledigt", „zurückgestellt", „nur auf
     Anlass", „wird nicht umgesetzt"); `seit` aus dem Text („Aufgenommen
     DD.MM.YYYY") oder aus `git log --reverse -S'^NNN. ' -- docs/Backlog.md`.
  4. Einrückung aller Folgezeilen auf fünf Leerzeichen (E-SD-17).
  5. Kürzung jedes Eintrags über 20 Zeilen nach 4.7: Befund, Wirkung, Weg
     oder Entscheidung bleiben; Messreihen, Werdegang („und ein drittes
     Mal", „teilweise erledigt in …") und Zitate anderer Dokumente
     entfallen; letzte Zeile ist die Werdegang-Zeile mit dem Commit von N.
     Zahlen, die im Text bleiben, tragen ihr Messdatum (Backlog-Regel seit
     13.09.2026).
  6. Kopfzeilen der Einträge, die in Fassung N ihre Erledigung im Text
     tragen, aber unter *Offen* stehen (Stand N: Nr. 238, 239 — „mit PR #60
     umgesetzt, Prüfung offen"): bleiben *Offen* mit `Stand: teilweise`,
     bis die Prüfliste abgehakt ist. Kein Eintrag wechselt in diesem Paket
     nach *Erledigt*.
- **Fächerung:** keine — eine Datei; die Kürzung der 66 Einträge ist
  seriell, weil jede Kürzung dieselbe Datei schreibt und derselbe Ton
  gehalten wird.
- **Abnahme:** P-SD-07 bis P-SD-12.

#### SD-03 — Prüfwerte

- **Werkzeug `tools/steuerung/`** (Python, wie `tools/screenshots/kontrast.py`):
  - `decken.py` misst die Tabelle in Abschnitt 5 dieses Konzepts gegen
    `docs/Rahmenplan.md`, `docs/Rahmenplan-Verlauf.md`, `docs/Backlog.md`,
    `docs/Backlog-Erledigt.md`. Rückgabe 0/1/2 wie `tools/zaehlung/`.
    `--stellen` nennt Datei und Zeile je Überschreitung; `--selbstprobe`
    baut je Decke eine Überschreitung in einer Kopie und erwartet rot
    (Grundsatz 7: keine grüne Zahl ohne Gegenstand).
  - `uebersicht.py` liest die Kopfzeilen aus `docs/Backlog.md`, prüft ihre
    Grammatik (4.6) und dass jedes `gehört zu` ein Ziel hat (Kennung aus
    der Fahrplan-Tabelle oder festes Wort), und gibt die offenen Punkte
    gruppiert nach Ziel aus — das ist der Ersatz für Abschnitt 5.
    `--pruefen` gibt nur den Prüfwert zurück (0 Kopfzeilen ohne Grammatik,
    0 ohne gültiges Ziel).
  - `LIESMICH.md` nach `Pruefablauf.md` 6.2: fünf Abschnitte, höchstens 40
    Zeilen, eine Zeile „Anlass: Nr. 177, 196, 199; Konzept SD 1.1 (121
    Berichtigungssätze, Abschnitt 5 mit 96 gegen 113)". Die Decken stehen
    **hier**, nicht in `pruefung.yml` (6.11).
- **`pruefung.yml`:** Schritt „Backlog — keine Nummer zweimal" liest beide
  Backlog-Dateien und meldet Nummern, die in beiden stehen; neuer Schritt
  „Steuerungsdokumente — halten sie ihre Decken?" ruft `decken.py` und
  `uebersicht.py --pruefen`. Rendering-Probe (Nr. 196): `cmark-gfm` (ist
  im Lauf bereits installiert) rendert `docs/Backlog.md`; die Zahl der
  `<pre>` muss 0 sein, die Zahl der `<li>` gleich der Zahl der Einträge.
- **`tools/pruefstand/pruefablauf.json`:** Muster `docs/Rahmenplan*.md`,
  `docs/Backlog*.md`, `CLAUDE.md` → `tools/steuerung/`, ab Stufe 1;
  Tabelle in `Pruefablauf.md` 4 neu erzeugen (`bericht.py erzeugen-doku`).
  `tools/kettenaufrufe/` läuft mit.
- **`Pruefablauf.md` 6.11:** Zeile „`tools/steuerung/` — 0 Decken
  überschritten, 0 Kopfzeilen ohne Grammatik, 0 Nummern in beiden Dateien,
  0 Einträge als Codeblock".
- **Fächerung:** die zwei Werkzeugdateien und der Workflow können auf zwei
  Agenten verteilt werden (getrennte Dateien); LIESMICH und Prüfablauf
  seriell danach.
- **Abnahme:** P-SD-13 bis P-SD-16.

#### SD-04 — Abschluss

- `CLAUDE.md` 2 Punkt 4 (Backlog: Kopfzeile, zwei Dateien, Reservierung
  E-SD-21), Punkt 5 (Rahmenplan-Anlässe, steht seit SD-00), Abschnitt 7
  (Erledigt-Eintrag als Zeile, Prüfzahlen im Prüfdokument), Abschnitt 9
  (Pflegepflichten: Steuerungsdokumente → `tools/steuerung/`).
- Rahmenplan 2.1 K5, K9 und 2.2 R62 nach E-SD-10/-15 gefasst; Abschnitt 9
  vollständig nach 4.10.
- **Verweisprobe von Hand** (P-SD-05): alle Vorkommen von „Rahmenplan
  Abschnitt N", „Rahmenplan-Archiv", „Backlog Nr. N" (Stichprobe je
  Nummernbereich) und „Fassung NN" in `docs/`, `server/`, `tools/`,
  `README.md`, `CLAUDE.md` lösen auf; Zahlen ins Prüfdokument.
- Changelog: ein Eintrag `[Werkzeug: Rahmenplan und Backlog geschnitten
  (Konzept SD)] — Datum`, im Ton von `CLAUDE.md` 2.
- Prüfdokument vollständig (K9); Erledigt-Zeile SD in Abschnitt 8 (neue
  Form); Verlaufszeile; Konzept nach K9 löschen, wenn die Freigabe des
  Abschlusses erteilt ist; PR öffnen. Danach hebt Abschnitt 4 die Sperre
  aus E-SD-19 auf.
- **Fächerung:** keine.
- **Abnahme:** P-SD-17 bis P-SD-20.

---

## 4. Vorlagen

Die Vorlagen sind verbindlich für Form und Decken; Wortlaut innerhalb der
Felder liegt bei der Instanz.

### 4.1 Kopf des Rahmenplans (≤ 15 Zeilen bis zur ersten `## `)

```
# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

**Fassung N+1 (DD.MM.YYYY)** · Steuerung: Reihenfolge, Status, programmweite
Entscheidungen. Verlauf: `Rahmenplan-Verlauf.md`. Fassungen 1–15:
`Rahmenplan-Archiv.md`; Fassungen 16–N: `Rahmenplan-Archiv-2.md`.

**Stand `origin/main`** (Commit `abc1234`, gemessen DD.MM.YYYY): Web X.Y.Z ·
Uhr X.Y.Z · Android X.Y.Z.
**Läuft:** Schritt … auf `claude/…` (Konzept `docs/konzepte/…`).
**Als Nächstes:** … — Reihenfolge in Abschnitt 3.
**Offene PRs:** … (oder „keine").
**Fällig bei der Betreiberin:** n Posten (Abschnitt 6.1). **`update.php`:** fällig/nicht fällig.

Kennungen sind Namen, keine Reihenfolge.
```

### 4.2 Kopf von `Rahmenplan-Archiv-2.md`

```
# Rahmenplan — Archiv 2 (Fassungen 16 bis N)

**Protokoll, nicht Steuerung.** Wörtlich und eingefroren: `docs/Rahmenplan.md`
in Fassung N (DD.MM.YYYY, Commit `abc1234`), ab der Trennlinie Zeichen für
Zeichen. Nicht fortgeschrieben. Verweise „Fassung 16" bis „Fassung N" und
„Rahmenplan Abschnitt N" aus Dokumenten vor dem DD.MM.YYYY meinen den Text
unten. Fassungen 1–15: `Rahmenplan-Archiv.md`.

## Wo die alten Abschnitte jetzt weiterleben

| alt (Fassung 16–N) | neu (ab Fassung N+1) |
|---|---|
| Kopf (Stand, Werdegang, Berichtigungen) | Kopf ≤ 15 Zeilen; Werdegang nur hier |
| 1, 2, 4, 9 | 1, 2, 4, 9 (gekürzt; K5, K9, R62 neu gefasst — Konzept SD) |
| 3 Fahrplan: Tabelle und Blöcke aller Schritte | 3 nur offene Schritte; erledigte nur hier |
| 5 Zuordnung der Backlog-Punkte | Kopfzeilen in `Backlog.md`; `tools/steuerung/uebersicht.py`; 5 ist Verweis |
| 6 Zuarbeiten samt erledigten | 6 nur offene, drei Gruppen; erledigte nur hier |
| 7 Register R1–R85 mit Volltext R51 ff. | 7 Kurzregister; Volltext R51–R85 nur hier |
| 8 Erledigt, 27 Blöcke | 8 Tabelle; Prüfzahlen im Prüfdokument, Volltext nur hier |
| 10 Änderungsverlauf 16–N | `Rahmenplan-Verlauf.md` ab N+1; 16–N nur hier |

---
[ab hier Fassung N wörtlich]
```

### 4.3 Fahrplan- und Zuarbeiten-Zeilen

Fahrplan (Abschnitt 3): `| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Status |`
— Inhalt ≤ 300 Zeichen (Backlog-Nummern nennt die Übersicht, nicht die
Zelle); Konzept = Pfad oder „gelöscht (K9), Historie `sha`"; Status =
Vokabular (E-SD-08) + Datum + Verweis, ≤ 240 Zeichen.

Zuarbeiten (Abschnitt 6, drei Gruppen 6.1/6.2/6.3): `| Was | Wofür | seit |
Bedienweg |` — Was ≤ 2 Sätze, ganze Zeile ≤ 300 Zeichen; Bedienweg = das
Prüfdokument und der Punkt darin.

### 4.4 Erledigt-Zeile (Abschnitt 8)

`| Kennung | Versionen | Datum · PR | Konzept (letzter Commit) | Prüfdokument | Kern |`
— Kern ein Satz, Zeile ≤ 400 Zeichen. Beispiel:
`| S10 — Sicherheit (9b) | Web 19.7.0–20.2.1 | 14.09.2026 · PR #45 | a00f6b5 | Pruefdokument-S10-Sicherheit.md (P-01–P-14 offen) | Server-Anteil am Datenschlüssel, Schlüsselblatt, ftp abgeschafft |`

### 4.5 Register-Zeile (Abschnitt 7)

`| Nr. | Kern | Status | Volltext |` — Kern ≤ 160 Zeichen, Status ≤ 160
Zeichen (ein Satz, wird ersetzt, nicht ergänzt), Volltext = „Archiv 3",
„Archiv-2 7" oder Konzeptpfad/E-Nummer.

### 4.6 Backlog-Kopfzeile (Pflicht, Grammatik wird geprüft)

```
NNN. **Titel.** · gehört zu: ZIEL · Stand: STAND · seit DD.MM.YYYY
```
- `ZIEL` = Kennung aus der Fahrplan-Tabelle, optional mit Paket
  (`10c AP7`), oder `nächste Backlog-Runde` · `Zuarbeit` · `Pflegeaufgabe`
  · `nach v1.0`.
- `STAND` = `offen` · `teilweise` · `zurückgestellt` · `nur auf Anlass` ·
  `nicht umsetzen`.
- Muster für das Werkzeug:
  `^(\d+)\. \*\*(.+?)\*\* · gehört zu: (.+?) · Stand: (offen|teilweise|zurückgestellt|nur auf Anlass|nicht umsetzen) · seit (\d{2}\.\d{2}\.\d{4})$`

### 4.7 Backlog-Eintrag (≤ 20 Zeilen, Einrückung fünf Leerzeichen)

```
NNN. **Titel.** · gehört zu: 17 · Stand: offen · seit 14.09.2026
     Befund: was ist, mit Fundstelle als Funktions- oder Abschnittsname.
     Wirkung: was es kostet oder wann es auffällt.
     Weg: was zu tun ist — oder „offen", oder die Entscheidung mit E-Nummer.
     Werdegang bis DD.MM.YYYY: `docs/Backlog.md@abc1234`, Nr. NNN.
```
Befund/Wirkung/Weg sind die empfohlene Gliederung, die Decke ist Pflicht.
Die Werdegang-Zeile steht nur in gekürzten Einträgen und nennt den Commit
der Fassung N.

### 4.8 Verlaufszeile (`Rahmenplan-Verlauf.md`)

`| Fassung | Datum | Anlass | Was |` — Anlass = Schritt/Paket/R-Nummer
(z. B. `10c Abschluss`, `R86`, `SD-01`); Was ≤ 2 Sätze; Zeile ≤ 300 Zeichen.
Eine Berichtigung: `| 112 | … | 10c | Fahrplanzeile 17 nannte 10c als Voraussetzung; richtig ist 10c-Merge. Ersetzt. |`

### 4.9 Backlog-Köpfe

(a) `Backlog.md` (≤ 60 Zeilen bis `## Offen`): Zweck; Nummern dauerhaft;
4, 6, 7 (und ggf. 5) frei; Kopfzeile Pflicht mit Vokabular; Einrückung fünf
Leerzeichen; Fundstellen als Funktionsnamen, Zahlen mit Messdatum; Regel
„keine Zeile beginnt mit Zahl und Punkt"; Reservierung nach E-SD-21 mit
Tabelle `| Spanne | Zweig | seit |`; Verweis auf `Backlog-Erledigt.md` und
`tools/steuerung/uebersicht.py`.

(b) `Backlog-Erledigt.md`: „Erledigte Punkte, wörtlich; Nummern werden
hier aufgelöst, nicht gepflegt"; darunter der Werdegang der Nummernvergabe
aus dem alten Kopf, dann `## Erledigt`.

### 4.10 Wortlaut für `CLAUDE.md` 2 (fünfter Punkt) und Rahmenplan 9

> **Rahmenplan schreiben — an vier Anlässen, sonst nicht.** (1) Ein Schritt
> beginnt oder endet (Fahrplanzeile, Erledigt-Eintrag). (2) Eine
> Programmentscheidung fällt (R-Nummer). (3) Die Reihenfolge ändert sich.
> (4) Eine Zuarbeit entsteht oder ist erledigt (Abschnitt 6). Alles
> innerhalb eines Schritts — Arbeitspakete, Befunde, Messungen, Abnahmen —
> steht im Statusblock des Konzepts und im Prüfdokument, nicht im
> Rahmenplan. **Eine Berichtigung ist eine Verlaufszeile, nie ein Absatz im
> Text:** Wer etwas Falsches findet, ersetzt es und schreibt in die
> Verlaufszeile, was falsch war. Der Kopf hält höchstens 15 Zeilen; die
> übrigen Decken misst `tools/steuerung/`.

Für SD-00 (vor dem Schnitt) lautet der letzte Satz: „Bis zum Schnitt
(Konzept SD) ändert sich am Kopf nur die Fassungsnummer und der Absatz
‚Stand'."

---

## 5. Decken (Vorschlag; gültig ist die `LIESMICH.md` des Werkzeugs)

| Gegenstand | Decke | Messung |
|---|---|---|
| `Rahmenplan.md` gesamt | 500 Zeilen (Ziel beim Schnitt < 400) | Zeilen |
| Rahmenplan Kopf bis erste `## ` | 15 Zeilen | Zeilen |
| Fahrplan-Zelle „Inhalt" | 300 Zeichen | je Tabellenzeile in Abschnitt 3 |
| Fahrplan-Zelle „Status" | 240 Zeichen; erstes Wort aus dem Vokabular | je Zeile |
| Block `### Schritt …` in Abschnitt 3 | 30 Zeilen | je Block |
| Zuarbeiten-Zeile (Abschnitt 6) | 300 Zeichen | je Tabellenzeile |
| Register-Zeile (Abschnitt 7) | Kern 160, Status 160 Zeichen | je Zeile |
| Erledigt-Zeile (Abschnitt 8) | 400 Zeichen | je Zeile |
| Berichtigungsmuster in `Rahmenplan.md`, `Backlog.md` | 0 | `hier stand`, `stand bis (zur )?Fassung`, `berichtigt mit Fassung`, `ÜBERHOLT` |
| Blockquote-Zeilen in `Rahmenplan.md` | 0 | `^>` |
| Verlaufszeile | 300 Zeichen | je Zeile |
| `Backlog.md` Kopf bis `## Offen` | 60 Zeilen | Zeilen |
| Offener Backlog-Eintrag | 20 Zeilen | je Eintrag, Kopfzeile eingeschlossen |
| Kopfzeilen ohne Grammatik (4.6) | 0 | Regex |
| `gehört zu` ohne gültiges Ziel | 0 | gegen Fahrplan-Tabelle und feste Wörter |
| Folgezeilen mit anderer Einrückung als fünf Leerzeichen | 0 | Regex |
| Doppelte Nummern über beide Backlog-Dateien | 0 | bestehender Schritt, erweitert |
| Nummern in beiden Backlog-Dateien | 0 | Schnittmenge |
| Backlog-Einträge, die als Codeblock rendern | 0 `<pre>` | `cmark-gfm` |

---

## 6. Prüfpunkte (für das Prüfdokument)

| Nr. | Paket | Prüfung | Erwartung |
|---|---|---|---|
| P-SD-01 | SD-00 | `grep -cE 'hier stand|berichtigt mit Fassung' docs/Rahmenplan.md` vor und nach jedem 10c-Paket | Zahl steigt nach SD-00 nicht mehr |
| P-SD-02 | SD-01 | `diff <(tail -n +K docs/Rahmenplan-Archiv-2.md) <(git show N:docs/Rahmenplan.md)` (K = Zeile nach der Trennlinie) | 0 Unterschiede |
| P-SD-03 | SD-01 | `wc -l docs/Rahmenplan.md`; Kopfzeilen bis `## 1` | < 400; ≤ 15 |
| P-SD-04 | SD-01 | Fahrplan-Zeilen alt (nicht erledigt) = neu; Zuarbeiten-Zeilen alt (nicht durchgestrichen) = neu; R-Zeilen 85 = 85; Erledigt-Zeilen 28 = 27 + 10c | jeweils gleich; Zahlen ins Prüfdokument |
| P-SD-05 | SD-01/04 | Verweisprobe von Hand: „Rahmenplan Abschnitt N" (≈ 80 Stellen), „Fassung NN" (358), „Backlog Nr." (Stichprobe 30 über alle Bereiche), Pfade `Rahmenplan-Archiv` | 0 tote Verweise; Liste der geprüften Stellen |
| P-SD-06 | SD-01 | Jedes Konzept in `docs/konzepte/`, das nicht Prüfdokument oder Vorbereitung ist, hat eine Fahrplanzeile | 0 ohne Zeile (E-SD-09) |
| P-SD-07 | SD-02 | `diff` des Erledigt-Teils gegen `git show N:docs/Backlog.md` ab `## Erledigt` | 0 Unterschiede |
| P-SD-08 | SD-02 | Offene Einträge alt = neu (113 zu Stand N; die Instanz misst) | gleich |
| P-SD-09 | SD-02 | `uebersicht.py --pruefen` | 0 Kopfzeilen ohne Grammatik, 0 ohne Ziel |
| P-SD-10 | SD-02 | Einträge > 20 Zeilen; Werdegang-Zeilen | 0; Zahl der Werdegang-Zeilen = Zahl der gekürzten Einträge (66 zu Stand N) |
| P-SD-11 | SD-02 | `cmark-gfm --to html docs/Backlog.md \| grep -c '<pre>'`; `<li>`-Zahl | 0; = Einträge |
| P-SD-12 | SD-02 | Doppelprüfung über beide Dateien; Schnittmenge | 0; 0 |
| P-SD-13 | SD-03 | `decken.py --selbstprobe` | jede Decke einmal rot (Zahl = Zahl der Decken) |
| P-SD-14 | SD-03 | `decken.py` auf dem Stand nach SD-02 | 0 Überschreitungen |
| P-SD-15 | SD-03 | Stufe 1 auf dem Zweig | Schritt „Steuerungsdokumente" grün; `tools/kettenaufrufe/` 0 Befunde; `Pruefablauf.md` 4 neu erzeugt |
| P-SD-16 | SD-03 | Gegenprobe im PR: eine 21. Zeile in einen Backlog-Eintrag einbauen, pushen, Stufe 1 lesen, zurücknehmen | rot mit Datei und Zeile |
| P-SD-17 | SD-04 | `CLAUDE.md` 2, 7, 9 und Rahmenplan 2.1/2.2/9 tragen die neuen Sätze; keine zweite normative Fassung derselben Regel | Lesen; Fundstellen ins Prüfdokument |
| P-SD-18 | SD-04 | Changelog-Eintrag `[Werkzeug: …]` vorhanden | ja |
| P-SD-19 | SD-04 | Textprobe (`tools/quelltext/textprobe.py`) — die neuen Dokumente laufen durch die Wortliste (`Pruefablauf.md` 6.6) | 0 neue Treffer |
| P-SD-20 | Betreiberin | Nach dem Merge: nächste Instanz, die einen Schritt beginnt, kommt mit Kopf, Fahrplan und Kopfzeile ohne Rückfrage zurecht | Beobachtung; Befund ggf. als Backlog-Punkt |

**Was nicht maschinell prüfbar ist und deshalb vorn im Prüfdokument
steht:** ob die Kürzung eines Backlog-Eintrags Sinn verloren hat (P-SD-10
zählt Zeilen, nicht Sinn) — die Instanz liest die 66 gekürzten Einträge
einmal gegen; ob die Zuarbeiten-Gruppen richtig sortiert sind (SD-M1).

---

## 7. Fragen an die Betreiberin

| Nr. | Frage | Wann | Stand |
|---|---|---|---|
| Q-SD-01 | Welche Zuarbeiten aus Fassung N sind erledigt; welche Zuordnung bekommen die unzugeordneten Backlog-Nummern (Liste liefert SD-01) | SD-M1 | offen |

---

## 8. Was die einspielende Instanz in Rahmenplan und Backlog einträgt

- **Fahrplanzeile SD** (E-SD-09): Kennung `SD — Steuerungsdokumente
  schneiden`, Voraussetzung „Merge von 10c; kein anderer Zweig an
  Rahmenplan/Backlog", Konzept dieser Pfad, Status nach Lage.
- **Reihenfolgesatz in Abschnitt 3:** `10c → SD → 17 → 18 → …`.
- **Sperre in Abschnitt 4:** solange SD läuft, schreibt kein anderer Zweig
  `docs/Rahmenplan.md` oder `docs/Backlog.md`.
- **Backlog:** ein Sammelpunkt „Steuerungsdokumente schneiden (Konzept SD)",
  `gehört zu: SD`; Nachträge an Nr. 177, 193, 196, 199 („erledigt sich mit
  SD", Umzug nach *Erledigt* mit dem SD-Abschluss); Nr. 188 bekommt den
  Satz „SD hat die Verweisprobe einmal von Hand gefahren (P-SD-05); das
  Mittel fehlt weiter".
- **Verlaufszeile** je Paket nur, wenn E-SD-15 einen Anlass sieht: SD-00
  (Regeländerung), SD-01 (Schnitt = Fassung N+1), SD-04 (Abschluss).
  SD-02 und SD-03 sind Paketarbeit und stehen im Statusblock.

---

## 9. Prüfprotokoll

Wird von der umsetzenden Instanz je Paket fortgeschrieben (K5); die
Abnahmezahlen der Prüfpunkte aus Abschnitt 6 kommen hierher, das
Nicht-Prüfbare zuerst.

| Paket | Stand | Commit | Zahlen |
|---|---|---|---|
| SD-00 | erledigt 24.09.2026 (P5c-Instanz) | `d85d898` (Rahmenplan Fassung 111) | P-SD-01: Fassungen 112 bis 124 tragen außerhalb von Abschnitt 10 keinen neuen Satz der Form „hier stand" (nachgezählt beim Schnitt: 0 neue Treffer nach Fassung 111; die alten stehen im Archiv-2) |
| SD-01 | erledigt 26.09.2026 | `758aaf4`; Nachtrag: Verlaufszeile 125 auf die Decke gekürzt (sie war 315 Zeichen lang, das Wegwerfskript hatte sie gemeldet und ich hatte nur 126 und 127 gekürzt) | P-SD-02 **0 Unterschiede** (3 928 = 27 + 3 901 Zeilen) · P-SD-03 **487 Zeilen** (< 500, nicht < 400 — F-SD-01), Kopf **15** · P-SD-04 Fahrplan **27 → 14** (11 + 3 neu), Zuarbeiten **67 → 69** (+ 2 neu), Register **85 = 85**, Erledigt **31 → 34** (+ 3 aus Fahrplanzeilen), 6a **10 = 10** · P-SD-06 **9 von 9** · Decken: Blockquotes **0**, Berichtigungsmuster **0**, Zellen **0 über der Decke** in 14 + 69 + 85 + 34 + 3 Zeilen · `cmark-gfm` **0 `<pre>`, 9 Tabellen** · Fächerung **105 Einträge, 97 einig, 8 Schiedsrichter, 40 ohne Schritt** · Prüfstand: Bericht in der Commit-Nachricht |
| SD-M1 | erledigt 26.09.2026 | `fd536e7` (Rahmenplan Fassung 128); `1596469` nimmt `origin/main` `f5bddc2` auf (PR #88, Pruefablauf.md 5.3) | Q-SD-01 (a) **69 Zeilen durchgesehen, 27 gestrichen, 42 bleiben**, 6a 3 abgehakt (E-SD-34) · Q-SD-01 (b) **40 von 40** Vorschläge übernommen (E-SD-35) · Q-SD-02 Decke 500 (E-SD-36) · Rahmenplan **460 Zeilen**, Zuarbeiten-Zeilen 0 über der Decke, Verlaufszeilen 4, 0 über der Decke |
| SD-02 | erledigt 26.09.2026 | `135863c` | P-SD-07 **0 Unterschiede** (Erledigt-Teil, 228 Einträge; Werdegang-Absätze 0) · P-SD-08 **105 = 105**, Nummernmenge gleich · P-SD-09 **105 von 105** Kopfzeilen mit Grammatik (`grep -P`; das Werkzeug kommt mit SD-03), Ziele: 17 × 54, PK × 9, nach v1.0 × 8, 18 × 7, 14 × 6, SD · 13 · 12 je 5, 12a · Zuarbeit · Pflegeaufgabe je 2 — alle Fahrplan-Kennung oder festes Wort · P-SD-10 **0 Einträge über 20 Zeilen**, **52 Werdegang-Zeilen = 52 gekürzte** · P-SD-11 `cmark-gfm` **0 `<pre>`, 105 `<li>`**, 1 Tabelle (Kopf) · P-SD-12 Schnittmenge **0**, doppelt **0 / 0**, `bestand.py` **0 Befunde**, Selbstprobe **141 Fälle, 0 Fehlschläge** · Decken: Kopf **56 Zeilen** bis `## Offen`, Einrückung **0** abweichend, Berichtigungsmuster **0**, Blockquotes 0 · Datei **11 329 → 1 788 Zeilen**, Erledigt-Datei 8 041 · Prüfstand: Bericht in der Commit-Nachricht |
| SD-03 | erledigt 26.09.2026 | `61e8c79` (Rahmenplan Fassung 129) | P-SD-13 `decken.py --selbstprobe` **22 Fälle, 0 Fehlschläge** — 20 Decken je einmal gerissen, 2 Gegenproben; `uebersicht.py --selbstprobe` **7 Fälle, 0 Fehlschläge** · P-SD-14 `decken.py` **20 Decken, 0 gerissen** (erster Lauf: 1 — F-SD-11, behoben mit Fassung 129); `uebersicht.py --pruefen` **105 Einträge, 0 ohne Grammatik, 0 ohne Ziel** · P-SD-15 `bestand` **0 Befunde**, `kettenaufrufe` **0 Befunde, 0 ungeprüft**, `Pruefablauf.md` 4 neu erzeugt (41 → 42 Zeilen), `pruefen.sh --trocken` wählt `steuerung` als Riegel und über das Muster; **Stufe 1 auf GitHub steht aus** (läuft erst mit dem PR, SD-04) · P-SD-16 Gegenprobe im PR: SD-04 · Prüfstand: Bericht in der Commit-Nachricht |
| SD-04 | erledigt 26.09.2026 | *wird beim Commit eingetragen* (Rahmenplan Fassung 130; PR #92) | P-SD-05 „Backlog Nr. N" **773 Verweise, 202 Nummern, 0 unauflösbar** (vollständig statt Stichprobe, E-SD-46) · „Rahmenplan Abschnitt N" **30, alle 1–9** · `Rahmenplan-Archiv…md` **37 Nennungen, 2 Dateien vorhanden** · „Fassung NN" strukturell (Archiv 1–15, Archiv-2 16–124, Verlauf 125–130) · P-SD-17 Fundstellen: `CLAUDE.md` 2 (Punkte 4, 5), 7, 9; Rahmenplan 2.1 K5, K9, 2.2 R62, 5, 9 — je Regel eine Fassung, die Konzeptfassung in 4.10 ist damit abgelöst · P-SD-18 Changelog-Eintrag **vorhanden** · P-SD-19 `wortliste` im Prüfstand **0** (Bereich c misst die 14 normativen Dokumente; Rahmenplan und Backlog sind Klasse H, `Pruefablauf.md` 6.6) · Rahmenplan **459 → 460 Zeilen**, Kopf **15** · F-SD-07 erledigt (`Technik.md`, `Handbuch.md` 10) · P-SD-15 **Lauf 311 grün** (Schritt „Steuerungsdokumente" und Gegenlesung) · P-SD-16 Lauf 312 **rot, aber an der Selbstprobe** (F-SD-13, E-SD-47), Lauf 313 **rot mit `docs/Backlog.md:1712: Nr. 332: 21 Zeilen`** · Prüfstand: Bericht in der Commit-Nachricht |
