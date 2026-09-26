# Konzept R4 — Backlog-Runde 4 (Schritt 17)

**Kürzel:** `R4`. Arbeitspakete `R4-01 …`, Entscheidungen `E-R4-NN`, Befunde
`F-R4-NN`, Fragen an die Betreiberin `Q-R4-NN`, Prüfpunkte `P-R4-NN` (im
Prüfdokument). Commit-Nachrichten beginnen mit dem Paket (`R4-02: …`).
Benennung nach `docs/Pruefablauf.md` 7; Runde 3 hieß `BR3`, und `BR` ist
seit dem 24.09.2026 der Bestandsriegel — daher nicht `BR4`.
**Rahmenplan:** Schritt **17** (Fahrplan: „Die kleinen Punkte seit Runde 3 —
Prüfmittel, Doku-Konsistenz, Streichlisten, `days.created_at`,
Demo-Reset-Takt, Statistik-Rest (Nr. 122)"; Voraussetzung „Merge von SD" —
erfüllt 26.09.2026, PR #92). Vorgriff: **BV** (gemergt 26.09.2026, PR #87,
Abschluss auf diesem Zweig). Nach 17 folgt 18 (Sicherheitsrunde II), dann
12 (P6-Review).
**Herkunft:** Konzeptsitzung am 26.09.2026 (Fable, R14). Die Liste der
Punkte liefert `python3 tools/steuerung/uebersicht.py --ziel 17` (Konzept
SD, E-SD-38): **55 Einträge** am 26.09.2026 — 54 aus SD-02 und Nr. 339 aus
dem Abschluss von BV (E-BV-19).
**Modell:** Konzept Fable (R14); Umsetzung **Opus** (K2), **kein
Fable-Schritt** (Fahrplanzeile 17).
**Fächerung (`CLAUDE.md` 7):** je Paket in Abschnitt 4; die Konzeptsitzung
hat den Befund auf Agenten gefächert (2.1).
**Versionsstufe:** legt die Umsetzung fest (K3). Pakete, die `server/`
anfassen, stufen Web; Pakete an `android/` stufen Android; Pakete nur an
`tools/` und `docs/` keine Zählung (`CLAUDE.md` 2).
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-R4-Backlog-Runde-4.md`
daneben (angelegt mit dem Konzept, gefüllt von der Umsetzung).
**Zweig:** `claude/schritt-17-hl9egt`, von `origin/main` `056781c`; die
Umsetzung läuft nach dem Merge des Konzept-PR auf einem eigenen Zweig
(E-R4-02).
**Backlog-Spanne:** **340 bis 349** (E-SD-21, eingetragen und gepusht mit
`17-00` vor jeder Vergabe). 339 hat der Abschluss von BV vergeben
(Q-BV-05, E-BV-19). Vergeben aus der Spanne: siehe Statusblock.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **26.09.2026 — Konzept in Arbeit** (Befund läuft, Abschnitt 2). |
> | Entschieden | **E-R4-01 bis E-R4-05** (Abschnitt 3), von der Betreiberin am 26.09.2026. |
> | Offen | Q-R4-NN nach dem Befund (Abschnitt 3); die Freigabe des Konzepts. |
> | Umsetzung | noch nicht begonnen. **R4-00** (Konzept, Spanne, Abschlüsse von SD, AR und BV auf diesem Zweig) läuft. |
> | Fable-Schritte | keine. |
> | Fächerung | Konzept: zwei Workflows mit je drei Sichtern, drei Gegenprüfern und einem Umfeld-Agenten, nur lesend (2.1). |
> | Nummern | 340 bis 349 reserviert; vergeben: — |

---

## 1. Auftrag

**Ziel:** Die offenen Backlog-Punkte mit `gehört zu: 17` abarbeiten oder
begründet an ihren richtigen Ort hängen — so, dass nach dem Merge
**kein Punkt mehr `17` trägt**: erledigt, ausgetragen, `nächste
Backlog-Runde`, ein anderer Schritt, `nach v1.0` oder `nicht umsetzen` mit
Begründung. Eine Backlog-Runde ist eine Aufräumrunde, keine Phase mit
neuer Funktion.

**Nicht Ziel:** alles, was Schritt 18 gehört (Sitzung, Zweitfaktor,
Serverschlüssel, `ingest.php`-Deadlock — Nr. 242, 247, 249, 233, 210, 228),
der P6-Review (12), neue Oberflächenbausteine ohne Mockup-Freigabe
(`CLAUDE.md` 5), Änderungen an der Auslieferungskette (PK-06 bis PK-08),
der Rahmenplan außer an den vier Anlässen.

**Warum jetzt und in dieser Reihenfolge:** 17 und 18 vor 12, damit der
Review aufgeräumte und gehärtete Seiten liest (Rahmenplan 3). Die Runde
läuft nach dem Merge von SD, weil bis dahin kein anderer Zweig Rahmenplan
oder Backlog schreiben durfte (E-SD-19) — und weil die Liste der Punkte
erst seit SD-02 an einer Stelle steht.

## 2. Befund

*(wird mit dem Ergebnis der Workflows gefüllt)*

## 3. Entscheidungen

| Nr. | Entscheidung | Von | Grund |
|---|---|---|---|
| E-R4-01 | **Das Konzept entsteht mit Fable, in dieser Sitzung; die Umsetzung in einer anderen Instanz mit Opus.** | Betreiberin, 26.09.2026 | R14 (Konzepte mit Fable) und K2 (Umsetzung Opus). Die Fahrplanzeile „kein Fable-Schritt" meint die Umsetzung, nicht das Konzept. |
| E-R4-02 | **Abschlüsse und Konzept gehen jetzt per PR auf `main`**; die Umsetzung läuft danach auf einem eigenen Zweig mit eigenem PR. | Betreiberin, 26.09.2026 | Der Rahmenplan auf `main` sagte bis dahin „SD läuft, PR #92 offen"; PK-06 bis PK-08 warteten mit ihrer Buchführung auf den SD-Merge (E-SD-19). Ein PR braucht seit PK-05 einen Prüfbericht (`Pruefablauf.md` 5) — für reine `docs/`-Änderungen die Stufe klein. |
| E-R4-03 | **Die Abschlüsse von SD, AR und BV (K9) werden in dieser Sitzung geschrieben**, je ein Commit, vor dem Konzept. | Betreiberin, 26.09.2026 | Sie sind reine Buchführung, und sie stehen im Rahmenplan-Kopf vor Schritt 17. Fassungen 131 bis 133. |
| E-R4-04 | **Nr. 339 (Nummernriegel im Prüfstand) gehört zu 17** — als Kandidat dieser Runde, nicht zu PK. | Betreiberin, 26.09.2026 (Q-BV-05, E-BV-19) | Prüfmittel sind der Kern der Runde; PK hat mit PK-06 bis PK-08 genug. |
| E-R4-05 | **Backlog-Spanne 340 bis 349**, eingetragen und gepusht, bevor eine Nummer vergeben wird. | Umsetzung des Konzepts (E-SD-21) | Zwei Kollisionen an einem Tag (F-BV-14, -18) hatten genau diese Lücke: eine Spanne, die nur auf dem eigenen Zweig stand. |

## 4. Arbeitspakete

*(wird mit dem Paketschnitt gefüllt)*

## 5. Was bei jeder Codeänderung mitläuft

`CLAUDE.md` 2, je Paket: Versionsstufe nach Berührung (Web in
`server/version.php` mit Kopfabsatz; Android in `android/version.properties`;
keine bei `tools/` und `docs/`), `docs/CHANGELOG.md` mit Begründung,
Dokumentation nachziehen (entfernte Funktionen austragen), Backlog:
erledigte Punkte wörtlich nach `Backlog-Erledigt.md` mit Schlusssatz,
Rahmenplan nur an den vier Anlässen. Die Prüfmittel laufen zuletzt; der
Prüfstand (`tools/pruefstand/pruefen.sh`) erzeugt den Bericht für die
Commit-Nachricht.

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
gelöscht `956c370`), `Konzept-Backlog-Runde-3.md` (gelöscht `5e501ae`,
Vorlage für Form und Paketschnitt).
