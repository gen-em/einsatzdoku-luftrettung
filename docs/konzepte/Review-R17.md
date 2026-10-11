# Review R17 — Sammelstelle des Bug- und Sicherheitsreviews (P6)

**Kürzel:** `R17`, nach Planung v1.0 (E-PV-5 (e): „Entscheidungen als
E-R17-n im Review-Dokument"). Arbeitspakete `R17-NN`, Entscheidungen
`E-R17-NN`, Befunde `F-R17-NN`, Fragen an die Betreiberin `Q-R17-NN`,
Prüfpunkte `P-R17-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`R17-01: …`). Benennung nach `docs/Pruefablauf.md` 7. **Die Nummern
laufen über alle zwölf Stücke des Reviews fort:** Stück 1 (dieses Dokument
ab Abschnitt 3) ist Schritt 12, die Stücke 2 bis 12 sind Schritt 12b und
schreiben hier weiter — mit denselben Zählern, ohne Neubeginn.
**Rahmenplan:** Schritt **12** — „P6, Stück 1: das Bedrohungsmodell" (R17,
R69, R86); Voraussetzung „18 gemergt" erfüllt am 05.10.2026 (PR #101).
**Herkunft:** Konzeptsitzung ab dem 28.09.2026 (Fable, R14); begonnen am
11.10.2026 nach dem Abschluss von 18. Die Punkte liefert
`python3 tools/steuerung/uebersicht.py --ziel 12`: **zwei Einträge** am
11.10.2026 — Nr. 146 (Fragen an das Bedrohungsmodell) und Nr. 263 (Grenze
der Integritätswache).
**Modell:** **Fable** für jedes Paket dieses Schritts (R17, R69: „mit
Fable"); es gibt keine Umsetzung mit Opus, weil kein Paket Code schreibt.
**Fächerung (`CLAUDE.md` 7):** nur in R17-01, lesend — drei Leser für
drei Flächen (Server: Krypto, Backup, Sitzung; Clients: Uhr und Android;
Kette und Betrieb), ohne Nebenwirkung, jeder liefert Fundstellen mit
Funktionsnamen. R17-00, -02, -03, -04 seriell: erzählender Text, ein Ton.
**Versionsstufe:** keine. Berührt sind `docs/` und eine Zeile in
`tools/quelltext/textprobe.py` (der neue Bereich); nach `CLAUDE.md` 2 stuft
das keine der drei Zählungen hoch, und der Changelog bekommt keinen Eintrag.
Der Prüfstand fährt `klein`.
**Ablage:** dieses Dokument (Statusblock, Pakete, Entscheidungen, Befunde,
Fragen — und ab Schritt 12b die Fundliste der Stücke 2 bis 12);
`Pruefdokument-R17-Review.md` daneben; **das Modell selbst als
`docs/Bedrohungsmodell.md`** (E-R17-01), dauerhaft, neben `Technik.md`.
**Zweig:** `claude/exciting-brown-0n9et2`, aufgesetzt auf `origin/main`
bei `05a863e` (Web 21.14.0, Uhr 3.1.0, Android 0.17.0; 18 gemergt und
abgeschlossen). Push nach jedem Paket (K7); der Pull Request kommt erst nach
der Freigabe des Modells durch die Betreiberin (E-R17-03).
**Backlog-Spanne:** **370 bis 379** (E-SD-21, eingetragen mit R17-00 vor
jeder Vergabe). Vergeben aus der Spanne: siehe Statusblock.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **11.10.2026 — R17-00 erledigt:** Spanne 370–379 reserviert, Fahrplanzeile 12 geschrieben (Rahmenplan Fassung 154), dieses Dokument und das Prüfdokument angelegt. **Als Nächstes: R17-01** (Bestandsaufnahme, gefächert auf drei lesende Leser). |
> | Entschieden | E-R17-01 bis E-R17-04 (Abschnitt 2.1) — alle von der Betreiberin, 28.09.2026 (E-R17-04) und 11.10.2026 (E-R17-01 bis -03). |
> | Offen | keine Frage offen. Die Fragen an die Betreiberin kommen mit R17-03 (Nr. 146, S11-Entwurfsfragen, Nr. 263). |
> | Umsetzung | **R17-00 erledigt** 11.10.2026. R17-01 bis R17-04 offen. |
> | Fable-Schritte | alle; keine Opus-Umsetzung in diesem Schritt. |
> | Fächerung | R17-01 lesend auf drei Leser (Abschnitt 4); die übrigen Pakete seriell. |
> | Nummern | 370 bis 379 reserviert; vergeben: keine. |

---

## 1. Auftrag

**Ziel von Schritt 12:** ein Dokument, kein Code — das **Bedrohungsmodell**
der Anwendung, als Stück 1 des R17-Reviews (R69). Es sammelt ein, was heute
verteilt liegt (`Technik.md` 4.97 bis 4.99q, `Review-Krypto-Sicherheit.md`
2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18), und sagt
an einem Ort: **was geschützt wird, gegen wen, womit, und wo die Grenzen
liegen.** Es ist der **Eingang des Konzepts S11** (Schritt 12a): Es sagt,
was Klartext bleiben darf, wie der Schlüssel aufs Gerät kommt und welche der
Fragen aus Nr. 146 vor S11 zu entscheiden sind. Und es ist danach die
Referenz, gegen die die Stücke 2 bis 12 (Schritt 12b) lesen — ein Review
prüft gegen Schutzziele und Angreifer, und die müssen vorher an einem Ort
stehen (Planung v1.0, 4e.1).

**Abnahme (Rahmenplan 3, Schritt 12):** Modell vollständig, Fragen aus
Nr. 146 entschieden, Freigabe der Betreiberin.

**Nicht Ziel:** Code, auch nicht „kleine" Behebungen — was das Modell an
Lücken zeigt, wird Befund (F-R17-NN) und geht in die Freigaberunde von 12b
oder, wenn es eine Zusage bricht, ins Sofortpaket (R69, K4). Nicht Ziel
ist auch das Konzept S11 selbst: Das Modell beantwortet dessen
Entwurfsfragen, es entwirft nicht. Und nicht Ziel ist die Doku-Pflege
außerhalb des Modells: Wo das Modell eine Stelle in `Technik.md` ersetzt,
bekommt die Stelle einen Verweis, keinen Umbau.

**Was das Modell leisten muss, in einem Satz je Leserin:** Die
**Betreiberin** liest, welches Risiko sie trägt und welches nicht; der
**Review** liest, wogegen er prüft; das **S11-Konzept** liest, was es
verschlüsseln muss und was bleiben darf; **VVT und TOMs** (R41, Schritt 14)
lesen daraus ihre technischen Maßnahmen ab.

## 2. Entscheidungen und Fragen

### 2.1 Entscheidungen (E)

| Nr. | Entscheidung | Wer, wann | Begründung |
|---|---|---|---|
| E-R17-01 | **Das Modell ist eine eigene Datei, `docs/Bedrohungsmodell.md`**, normative Dokumentation neben `Technik.md` — kein Abschnitt darin. Es wird im selben Paket, in dem es entsteht, in den Bereich `c` der Textprobe eingetragen (`Pruefablauf.md` 6.6) und aus `README.md`, `Technik.md` und `CLAUDE.md` 4 verlinkt. | Betreiberin, 11.10.2026 (Vorschlag Fable) | Planung v1.0 ließ die Wahl offen („eigener Abschnitt oder eigene Datei, entscheidet der Review"). `Technik.md` hat 13 600 Zeilen; das Modell hat andere Leser (Betreiberin, Datenschutz, Review) und muss ohne die Technik lesbar sein. |
| E-R17-02 | **Kürzel `R17`; dieses Dokument bleibt nach Schritt 12 stehen.** Es ist das Konzept von 12 (Statusblock, Pakete, Prüfprotokoll) und zugleich die Sammelstelle des ganzen Reviews; 12b schreibt mit denselben Zählern fort. Die Erledigt-Zeile von 12 nennt es; gelöscht wird es erst mit dem Abschluss von 12b. | Betreiberin, 11.10.2026 | Abweichung von R62 („das Konzept wird gelöscht"), weil der Rahmenplan es so vorsieht („Review-R17.md beginnt damit", „Fortsetzung aus 12") und eine Fundliste, die über 12a hinweg zwei Dateien hätte, zwei Zählungen hätte. |
| E-R17-03 | **Push nach jedem Paket auf `claude/exciting-brown-0n9et2`** (K7); **kein Pull Request vor der Freigabe des Modells** durch die Betreiberin. | Betreiberin, 11.10.2026 | K7 und `CLAUDE.md` 8; der PR ist die Phase, nicht ein Paket. |
| E-R17-04 | **Schritt 12 läuft nicht parallel zu 18**, sondern danach, wie der Rahmenplan es vorsah. | Betreiberin, 28.09.2026 | Fable hatte das Parallelfahren vorgeschlagen (das Modell ist zu neun Zehnteln unabhängig von 18) und den Preis genannt (Nachlese-Paket, R-Nummer, Konflikte in den Steuerungsdokumenten). Die Betreiberin hat die Reihenfolge behalten; 18 war am 05.10.2026 gemergt. |

### 2.2 Fragen an die Betreiberin (Q)

Kommen mit R17-03, je Frage mit Empfehlung und Preis; die Antwort wird
E-R17-NN. Vorgesehen: die zwei Fragen aus Nr. 146 (Argon2id;
nicht-extrahierbarer `CryptoKey`), die Entwurfsfragen für S11 (was Klartext
bleiben darf; wie der Schlüssel aufs Gerät kommt; SP-9 gegen das Modell),
Nr. 263 (Stand setzen). *Noch keine gestellt.*

### 2.3 Befunde (F)

Je Befund: Nummer, **Schweregrad** (kritisch / hoch / mittel / niedrig /
Hinweis), Fundstelle (Funktionsname oder Abschnitt, nicht Zeilennummer),
Wirkung, Empfehlung, geschätzter Aufwand. **Der Review behebt nichts** (K4,
R69). Wege: kritisch → Sofortpaket vor allem anderen; alles andere →
Freigaberunde und Pflichtpaket in P6 (12b). *Noch keiner.*

## 3. Stück 1 — das Bedrohungsmodell

Das Ergebnis dieses Stücks steht in `docs/Bedrohungsmodell.md`. Hier steht,
**wie** es entstanden ist und **was** dabei herausgekommen ist, das nicht ins
Modell gehört: die Inventartabelle (R17-01), die Befunde und die Fragen.

### 3.1 Gliederung des Modells (Entwurf, wird mit R17-02 endgültig)

0. **Zweck, Geltung, Pflege** — für wen, welcher Stand (Web, Uhr, Android,
   Datum), wann es fortgeschrieben wird.
1. **Schutzziele und Werte** — was geschützt wird: Patientendaten,
   Ortsdaten, Konten und Schlüssel, Integrität der Anlage und der
   Auslieferung, Verfügbarkeit; je Wert, welche Eigenschaft (Vertraulichkeit,
   Integrität, Verfügbarkeit) und welche Zusage (`CLAUDE.md` 4).
2. **Angreiferlagen** — die neun aus `Review-Krypto-Sicherheit.md` 2 (A bis
   I), erweitert um: Kette und GitHub (Push-Recht, Actions, Secrets),
   Google und Play (Signaturschlüssel, Data Layer), fremde NutzerIn
   derselben Anlage (Mehrbenutzer seit P5), Support-Rolle, die Betreiberin
   selbst; je Lage, was sie hat und was sie erreicht.
3. **Zusagen und ihre benannten Grenzen** — E2E-Felder und Klartextliste
   (Zustand, nicht Ziel; S11), Demo-Konto, Signaturschlüssel bei Google,
   Speicherdauern, Geheimnisse der Kette, die Grenze der Integritätswache
   (Nr. 263), Photon und Kachelserver, Regel „Migration ohne Rechte".
4. **Bausteine gegen Angreifer** — die Matrix: Schlüsselhierarchie mit
   Server-Anteil, Container 4, SPUR1, Komplettbackup, Serverschlüssel und
   sein Wechsel, Demo-Konstruktion, Keystore des Handys, Geräteschlüssel der
   Uhr, Kopplungsweg, Sitzung und Sitzungsbindung, Zweitfaktor mit Passkeys,
   Rückweg und Notzugang, CSP, Ratenschutz und Rechenaufgabe, Rollen und
   Berechtigungsmatrix, Kette mit Toren, Zeiger und Wache.
5. **Restrisiken** — benannt, je eines mit Zuordnung: hingenommen (und
   warum), Backlog-Nummer, oder S11.
6. **Entwurfsfragen für S11** — was Klartext bleiben darf, wie der Schlüssel
   aufs Gerät kommt (SP-9 gegen das Modell), die Antworten auf Nr. 146.

### 3.2 Inventar der Quellen (R17-01)

*Kommt mit R17-01: je Quelle der Zielabschnitt des Modells und der Befund,
wo Doku und Code bei Web 21.14.0 auseinandergehen.*

## 4. Arbeitspakete

| Paket | Inhalt | Fächerung | Abnahme |
|---|---|---|---|
| **R17-00 Buchführung** | Spanne 370–379, Fahrplanzeile (Rahmenplan Fassung 154), dieses Dokument mit Statusblock, Prüfdokument; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-01 Bestandsaufnahme** | Die verstreuten Stellen einsammeln und **gegen den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegenlesen**, nicht gegen die Doku: `Technik.md` 4.97, 4.97c/d, 4.98, 4.99a/a2/b/q, `Review-Krypto-Sicherheit.md` 2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18 (Sitzungsbindung, Gerät merken, Passkeys, Schlüsselwechsel, Notzugang, Rechenaufgabe), Kette und ihre Geheimnisse, Keystore des Handys, Geräteschlüssel der Uhr, Play App Signing. Ergebnis: Inventartabelle in 3.2, Befunde, wo Doku und Code auseinandergehen. | **lesend, drei Leser** (Server; Clients; Kette und Betrieb), ohne Nebenwirkung; Fable führt zusammen. | Inventar vollständig gegen die Liste im Fahrplan (Schritt 12: „Verschlüsselung, Container 4, SPUR1, Komplettbackup und Serverschlüssel, Demo, Handy, Kopplung, SP-9 und Nr. 146, Signatur, Kette, Nr. 109, Nr. 263"); jede Zeile mit Fundstelle. |
| **R17-02 Das Modell schreiben** | `docs/Bedrohungsmodell.md` nach 3.1; Bereich `c` der Textprobe; Verweise aus `README.md`, `Technik.md` (4.99b und der Sicherheitsabschnitt verweisen hin, statt zu wiederholen), `CLAUDE.md` 4. | seriell | Modell vollständig gegen 3.1; Textprobe 0 neue Treffer; Verweise stehen. |
| **R17-03 Fragen an die Betreiberin** | Q-R17-NN mit Empfehlung und Preis: Nr. 146 (Argon2id; `CryptoKey`), S11-Entwurfsfragen, Nr. 263. **Halt:** wartet auf die Antworten. | seriell | Jede Frage beantwortet und als E-R17-NN eingetragen; Nr. 146 erledigt, Nr. 263 mit Stand. |
| **R17-04 Abschluss** | Antworten ins Modell einarbeiten, Prüfdokument mit Mittel und Zahl, Prüfstand `klein`, Freigabe der Betreiberin; danach Erledigt-Zeile (Rahmenplan 8), Backlog-Kopfzeilen, Verlaufszeile, Pull Request gegen `main`. | seriell | Freigabe erteilt; PR gestellt. |

## 5. Prüfprotokoll

Je Paket: was gemessen wurde, mit Mittel und Zahl. Die Prüfliste für die
Betreiberin steht im Prüfdokument.

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| R17-00 | `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | 21 Decken, 0 gerissen (die Verlaufszeile riss einmal mit 333 Zeichen, gekürzt auf 283) · 62 Einträge, 0 ohne Grammatik, 0 ohne Ziel, 2 zu Ziel 12 · 0 neue Nummern, 0 Überschneidungen gegen 8 Zweige · 0 neue Treffer · 0 Befunde · 150 Verweise, 0 Abweichungen. Prüfstand `klein`: siehe Commit-Nachricht von R17-00 | 11.10.2026 |

## 6. Quellen

- `docs/Rahmenplan.md` 3 (Schritt 12, 12a, 12b; „Warum so"), 7 (R17, R69,
  R78, R86).
- `docs/konzepte/Konzept-Planung-v1.0.md` 4e (Befund, Optionen, E-PV-5).
- `docs/konzepte/Review-Krypto-Sicherheit.md` (Eingang; Bedrohungsmodell in
  2, Verfahren in 3, Befunde in 4).
- `docs/konzepte/Vorbereitung-Sicherheitspaket.md` (SP-3b, SP-7, SP-9).
- `docs/konzepte/Konzept-V1-Ortsdaten.md` (Vorstudie Weg B, Fragen in 6
  beantwortet).
- `docs/Backlog.md` Nr. 146, Nr. 263, Nr. 43 (Weg B, gehört zu 12a);
  `Backlog-Erledigt.md` Nr. 109 (Notizen ins Blob) und Nr. 138 (Weg C).
- `docs/Technik.md` 4.97 bis 4.99q; `CLAUDE.md` 3 und 4.
