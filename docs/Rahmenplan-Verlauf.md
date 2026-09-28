# Rahmenplan — Änderungsverlauf (ab Fassung 125)

Eine Zeile je Fassung von `docs/Rahmenplan.md`: Anlass (Schritt, Paket oder
R-Nummer), höchstens zwei Sätze, höchstens 300 Zeichen. **Eine Berichtigung
steht nur hier:** An Ort und Stelle wird ersetzt, die Zeile sagt, was falsch
war (Konzept SD, E-SD-13). Fassungen 1–15: `Rahmenplan-Archiv.md`;
Fassungen 16–124: `Rahmenplan-Archiv-2.md` (dessen Abschnitt 10 hält den
Verlauf bis zum Schnitt). Die Fassungszählung läuft fortlaufend weiter.

| Fassung | Datum | Anlass | Was |
|---|---|---|---|
| 146 | 28.09.2026 | 18, SR-01 | Umsetzung von 18 beginnt auf `claude/pr95-stufe-18-ztactt` (Opus), auf Wunsch der Betreiberin gestapelt auf dem offenen Konzept-PR #95 statt nach dessen Merge (E-SR-38). |
| 145 | 28.09.2026 | 18, Konzept-PR | `main` aufgenommen (17 gemergt, PR #94, `f4ac705`), Konzept-PR zu 18 gestellt; Nr. 250 nach SR-02, Nr. 344 nach SR-03 (E-SR-37). Berichtigt: Die Fassungen 137 bis 142 dieses Zweigs heißen seit dem Merge 139 bis 144 — 17 hatte 137 und 138 auch vergeben. |
| 144 | 27.09.2026 | 18, Q-SR-12/-13 | Nachfassung beantwortet: Passkey nur zusätzlich zu TOTP (E-SR-35; Nr. 351 „Passkey allein" nach v1.0), Fable-Gegenlesung auch für SR-09 (E-SR-36, H-SR-08); das Konzept hat keine offene Frage. Entscheidung der Betreiberin am 27.09.2026. |
| 143 | 27.09.2026 | 18, Nachfassung | Fahrplanzeile 18: Passkeys als zweiter Faktor neben TOTP dazu (Nr. 350, Paket SR-09, E-SR-29) — neun Pakete; Passkeys mit PRF als Passwortersatz nicht weiterverfolgt (E-SR-28, Nr. 146). Entscheidung der Betreiberin am 27.09.2026; Q-SR-12, -13 offen. |
| 142 | 27.09.2026 | R86, Reihenfolge | P6 in zwei Hälften um S11 (R86): 12 das Bedrohungsmodell, 12b die Stücke 2 bis 12 nach 12a; Reihe 17 → 18 → 12 → 12a → 12b → 13 → 14; Nr. 21, 77, 188, 341 nach 12b. Bis Fassung 141 stand 12a nach dem ganzen Review, S11 wäre nie gegengelesen worden. |
| 141 | 27.09.2026 | 18 freigegeben | Konzept SR von der Betreiberin freigegeben (E-SR-27); Fahrplanzeile 18 auf „freigegeben". Konzept-PR nach dem Merge von 17; Umsetzung danach auf eigenem Zweig mit Opus, Fable liest SR-03 gegen (H-SR-06). |
| 140 | 27.09.2026 | 18, Q-SR-07/-11 | Fahrplanzeile 18: Nr. 228 (Proof-of-Work) wird fest gebaut statt „nur auf Anlass" (Entscheidung der Betreiberin, E-SR-26), dazu ein frischer Code vor kritischen Handlungen (E-SR-20); Konzept SR mit acht statt sechs Paketen. |
| 139 | 27.09.2026 | 18 beginnt | Fahrplanzeile 18 auf „Konzept": `Konzept-SR-Sicherheitsrunde-II.md` (Kürzel SR) auf `claude/gallant-mccarthy-yacnzk`, aufgesetzt auf dem 17er-Zweig (R4-10); die Zeile nennt Nr. 232 und 251 mit (acht Punkte mit Ziel 18); Spanne 350–359. |
| 138 | 28.09.2026 | 17-Abschluss | Erledigt-Zeile 17 (PR #94, `a6908a8`); Fahrplanzeile und Reihenfolge ohne 17, Konzept gelöscht; 6.1: `update.php`, Tag `web-v21.6.1`, Prüfliste R4. Berichtigt: Der Kopf zählte seit Fassung 135 27 statt 28 Posten. |
| 137 | 28.09.2026 | R4-26 | Fahrplanzeile 17 auf „gebaut" (R4-01 bis R4-26; Web 21.6.0 mit Migration, Android 0.17.0). Berichtigt: Registerzeile R4 nannte 21 Diensttage und 103/106 Einsätze — seit R4-16 sind es 22 und 106/109. |
| 136 | 26.09.2026 | R4-03 | Zuarbeit erledigt: P-BR-09 (6.12 beim nächsten Prüfmittel befolgen) mit `nummern.py` gegangen — ein Fund in Schritt 6, berichtigt (F-R4-23); aus der Zeile „Prüfliste BR" in 6.1 gestrichen. |
| 135 | 26.09.2026 | R4-01 | Umsetzung von 17 beginnt auf `claude/schritt-17-konzept-mockups-q0yjcm` (Opus); Konzept mit PR #93 gemergt und samt Fragen und Mockups freigegeben (E-R4-14 ff.). Zuarbeit in 6.1: die zwei toten Zweige löscht die Betreiberin (Q-R4-01). |
| 134 | 26.09.2026 | 17 beginnt | Fahrplanzeile 17 auf „Konzept": `Konzept-R4-Backlog-Runde-4.md` (Kürzel R4) auf `claude/schritt-17-hl9egt`, Voraussetzung SD erfüllt, 55 Einträge mit Ziel 17; Umsetzung nach dem Merge des Konzept-PR (E-R4-02). |
| 133 | 26.09.2026 | BV-Abschluss | Erledigt-Zeile BV (PR #87, `d34908b`; gemergt vor AR, das BV aufnahm); Fahrplanzeile weg; Konzept gelöscht (mit E-BV-19, -20: Nr. 339, Handbuch 11.5a); P-BV-04, -07, -09 als Zuarbeit in 6.1. |
| 132 | 26.09.2026 | AR-Abschluss | Erledigt-Zeile AR (PR #88, `f5bddc2`, Android 0.16.0); Fahrplanzeile und die AR-Zeile in Abschnitt 4 weg; Konzept gelöscht; Gerätetests als Zuarbeit in 6.1; P-PK-28 im Prüfdokument PK belegt (Nr. 284). |
| 131 | 26.09.2026 | SD-Abschluss | Erledigt-Zeile SD (PR #92, `056781c`); Fahrplanzeile und SD-Sperre in Abschnitt 4 weg; Konzept gelöscht; Nr. 177, 193, 196, 199, 294 nach `Backlog-Erledigt.md`; Stand von `main` gemessen; Kopf: Abschlüsse und Konzept 17 auf `claude/schritt-17-hl9egt`. |
| 130 | 26.09.2026 | SD-04 | Abschluss: Stand von `main` gemessen (`f5bddc2`, Android 0.16.0), AR gemergt, Abschnitt 5 endgültig (Kopfzeilen und `uebersicht.py`), Kopf und 9 ohne „ab SD-03"; SD-Fahrplanzeile „gebaut", PR offen. |
| 129 | 26.09.2026 | SD-03 | Berichtigt: Die Fahrplanzeile „Betriebsübergang" trug keinen Status („—"); `decken.py` verlangt das erste Wort aus dem Vokabular — jetzt „offen — beginnt mit v1.0 (nach den Schritten 13 und 14)". |
| 128 | 26.09.2026 | SD-M1 | Durchsicht der Betreiberin (E-SD-34): 27 Zuarbeiten erledigt und gestrichen (6.1: 17, 6.2: 7, 6.3: 3), 6a Schritte 7–9 abgehakt. Schritt 6 Teil C ist nicht mehr blockiert (D-U-N-S, Play-Konto, Play App Signing erledigt); R65 und der Kopf nachgezogen. |
| 127 | 26.09.2026 | SD-01 | Berichtigt: K7 und R40 (2) nannten den Push auf `main` als Produktiv-Deploy — seit Web 20.4.0 Staging, Produktiv über den Tag. Android ist 0.15.1, nicht 0.15.0; S7 ist PR #27; „Branch-Schutz" nennt nur noch den 2FA-Zwang. |
| 126 | 26.09.2026 | SD-01 | Ergänzt (E-SD-09): Fahrplanzeilen für AR, BV, PK; Kette II, 11 und 6 (Teil C) bleiben; Erledigt-Zeilen für S4-Merge, S6, S5-Konzept aus ihren Fahrplanzeilen. Neue Zuarbeiten: Tag für 10c, Kette-II-Prüfpunkte 26–28 (6.1). |
| 125 | 26.09.2026 | SD-01 | Der Schnitt (E-SD-01): Fassung 124 liegt wörtlich in `Rahmenplan-Archiv-2.md`, dieser Verlauf beginnt, der Rahmenplan ist nach Konzept SD 4 neu geschrieben — Kopf 15 Zeilen, Fahrplan nur offene Schritte, Zuarbeiten in drei Gruppen, Kurzregister, Erledigt als Tabelle. |
