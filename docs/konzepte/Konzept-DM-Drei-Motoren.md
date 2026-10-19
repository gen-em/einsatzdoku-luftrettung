# Konzept DM — Jede Browserprobe in drei Engines

**Kürzel:** `DM` („drei Motoren" — der Bestand nennt eine Engine seit Nr. 183
einen Motor, `tools/motor.mjs`). Arbeitspakete `DM-NN`, Entscheidungen
`E-DM-NN`, Befunde `F-DM-NN`, Fragen an die Betreiberin `Q-DM-NN`,
Prüfpunkte `P-DM-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`DM-02: …`). Benennung nach `docs/Pruefablauf.md` 7.
**Rahmenplan:** Schritt **19** — „DM — Jede Browserprobe in drei Engines"
(R87). Keine Voraussetzung; läuft neben der Reihe 12a → 12b.
**Herkunft:** Prüfpunkt P-S11-43 nach der Auslieferung von Web 23.0.0 auf
Staging am 19.10.2026. Die Betreiberin fragte, warum Firefox und Safari
nicht maschinell laufen — sie hatte drei Engines festgelegt. Die Antwort
war: festgelegt ja (Nr. 183, nur für Bilderlauf, Klickprobe und
Stilvergleich), durchgesetzt nirgends (Abschnitt 1). Kein eigenes
Konzeptgespräch: Plan, Fragen und Freigabe im selben Gespräch (E-DM-01 bis
-07), die Umsetzung folgt unmittelbar.
**Modell:** **Opus** für jedes Paket (CLAUDE.md 7, Standard).
**Fächerung (`CLAUDE.md` 7):** keine. Die Pakete fassen dieselben Läufer
(`proben.sh`, `pruefen.sh`, `pruefablauf.json`) an, und Prüfarbeit lässt
sich nicht fächern.
**Versionsstufe:** keine. Der Schritt fasst nur `tools/`, `docs/` und
`.github/` an (CLAUDE.md 2, Punkt 1).
**Ablage:** dieses Dokument; `Pruefdokument-DM-Drei-Motoren.md` daneben.
**Zweig:** `claude/s11-ortsdaten-schritt-12a-2cvh7h`, neu aufgesetzt auf
`origin/main` bei `467db64` (Web 23.0.0, Uhr 4.0.0, Android 0.18.0) — der
Name stammt aus S11, dessen PR #111 gemergt ist. Push nach jedem Paket (K7).
**Backlog-Spanne:** **390 bis 399** (E-SD-21, eingetragen mit DM-00 vor
jeder Vergabe).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **19.10.2026 — DM-00 in Arbeit** |
> | Entschieden | E-DM-01 bis E-DM-07 (Abschnitt 2), alle von der Betreiberin am 19.10.2026 |
> | Offen | keine Frage |
> | Umsetzung | DM-00 in Arbeit |
> | Halt | keiner vorgesehen |
> | Fable-Schritte | keine |
> | Fächerung | keine (Kopf) |
> | Nummern | 390 bis 399 reserviert; vergeben: keine. |

---

## 1. Auftrag und Ausgangslage

**Ziel:** Jede Browserprobe läuft in Chromium, Firefox und WebKit — örtlich
im Prüfstand in jeder Stufe und in Stufe 2 gegen Staging. Fehlt eine
Engine, ist die Probe rot, nicht übersprungen.

**Ausgangslage, gemessen am 19.10.2026:**

| Stelle | Stand |
|---|---|
| Festlegung | Nr. 183 (Mockup-Runde 9c): drei Engines für Bilderlauf, Klickprobe, Stilvergleich. `Pruefablauf.md` 3 verspricht für `haupt` den Bilderlauf in drei Engines und nennt es selbst „das Ziel, nicht der Stand" (Nr. 300). |
| Werkzeuge | 32 Dateien starten einen Browser; 10 wählen den Motor über `tools/motor.mjs`, 22 haben Chromium fest eingebaut. |
| Prüfstand | fährt **alle** nur in Chromium — auch die zehn umschaltbaren, weil niemand `--motor` übergibt. |
| Arbeitsumgebung | `/opt/pw-browsers` hält seit dem Abbild vom 11.10.2026 nur Chromium (F-S11-20). `aufbauen.sh` lädt bewusst nicht nach (`Sandbox-Setup.md` 1.2). |
| Stufe 2 | `auslieferung.yml` installiert nur Chromium; Kreislauf und Rückwegprobe laufen einmal. |
| Nur in Chromium möglich | CPU-Drossel über CDP (Messstand, Hüllenprobe `--drossel`), virtueller Passkey-Authenticator (Bedienprobe, Weg Passkey), CDP-Sitzung in der Kopplungsprobe. |

Grundsatz 7 („kein stilles Überspringen") hat das nicht aufgefangen, weil
keine Probe eine zweite Engine verlangte: Es fehlte nichts, was jemand
hätte bemerken können.

## 2. Entscheidungen

| Nr. | Entscheidung | Wer, wann |
|---|---|---|
| E-DM-01 | **Die Regel:** Jede Browserprobe läuft in Chromium, Firefox und WebKit. Fehlt eine Engine, ist die Probe rot. Sie wird Grundsatz 8 in `docs/Pruefablauf.md` 1, mit Verweis in `CLAUDE.md` 6, und als R87 Programmentscheidung. | Betreiberin, 19.10.2026 |
| E-DM-02 | **Umfang:** örtlicher Prüfstand **und** Stufe 2 gegen Staging. | Betreiberin, 19.10.2026 |
| E-DM-03 | **Beschaffung:** Die Sitzung lädt Firefox und WebKit selbst nach, und zwar im Standard-Setup (`tools/sandbox/aufbauen.sh web`, vom Sitzungshook gerufen). Der Satz „die Engines werden nicht nachgeladen" fällt. | Betreiberin, 19.10.2026 |
| E-DM-04 | **Safari:** Playwrights WebKit ist die Engine, nicht der Browser. Ein echter Safari bleibt Handprüfung in den Prüflisten. | Betreiberin, 19.10.2026 |
| E-DM-05 | **Was nur Chromium kann:** Die Probe läuft trotzdem in allen drei Engines; nur der Teil, der nur in Chromium geht (Drossel, virtueller Passkey), läuft dort allein. Der Bericht sagt es, und es steht in der Ausnahmeliste. | Betreiberin, 19.10.2026 |
| E-DM-06 | **Messstand:** bleibt als begründete Ausnahme bei Chromium — seine Grundlinie ist gedrosseltes Chromium, und Firefox und WebKit haben keine Drossel. | Betreiberin, 19.10.2026 |
| E-DM-07 | **Laufzeit:** alle Stufen in drei Engines, auch `klein`. Die längere Laufzeit ist angenommen. | Betreiberin, 19.10.2026 |

Nicht als Tests gezählt: der Erzeuger `tools/erzeugen/logos.mjs` und der
Helfer `tools/referenzdatensatz/einspielen/passwort_setzen.mjs`; der Helfer
läuft in der Engine der Probe, die ihn ruft.

## 3. Arbeitspakete

| Paket | Inhalt |
|---|---|
| DM-00 | Buchführung: dieses Dokument, Fahrplanzeile Schritt 19, R87, Verlaufszeile, Spanne 390–399; Push zuerst. |
| DM-01 | Standard-Setup: `aufbauen.sh web` lädt Firefox und WebKit nach, wenn sie fehlen, und holt deren Systembibliotheken; `docs/Sandbox-Setup.md` nachziehen. |
| DM-02 | Proben: die festen Werkzeuge gehen über `motor.mjs`; `proben.sh` und `pruefen.sh` fahren jede Browserprobe dreimal und berichten je Engine; Bilderlauf in jeder Stufe dreifach; Ausnahmen nach E-DM-05 und -06. |
| DM-03 | Riegel in Stufe 1: Jedes Werkzeug, das einen Browser startet, geht über `motor.mjs`; Ausnahmen nur über eine Liste mit Begründung. |
| DM-04 | Stufe 2: Firefox und WebKit auf dem Läufer; Kreislauf `edbak` und Rückwegprobe je Engine. |
| DM-05 | Regel in `Pruefablauf.md` 1 und `CLAUDE.md` 6, Doku, Prüfstand, Prüfdokument, PR. |

## 4. Befunde

| Nr. | Gewicht | Fundort | Befund | Folge |
|---|---|---|---|---|

## 5. Prüfprotokoll

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
