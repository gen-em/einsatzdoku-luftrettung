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
> | Stand | 24.09.2026 — Fassung 1, zur Freigabe vorgelegt |
> | Entschieden | E-SD-01 bis E-SD-27 |
> | Offen | Q-SD-01 (Zuarbeiten-Durchsicht, mit SD-M1); die fünf Punkte zum Gegenlesen in 2.4 |
> | Umsetzung | nicht begonnen. **SD-00 sofort** (P5c-Instanz, vor AP2); **SD-01 bis SD-04 nach dem Merge des 10c-PR** auf eigenem Zweig von `main` (E-SD-19) |
> | Fable-Schritte | keine |
> | Nummern | keine vergeben |

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
- Die Instanz **wartet** hier; die Antwort geht als E-SD-28 ff. in dieses
  Konzept.

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
| SD-00 | — | — | — |
| SD-01 | — | — | — |
| SD-M1 | — | — | — |
| SD-02 | — | — | — |
| SD-03 | — | — | — |
| SD-04 | — | — | — |
