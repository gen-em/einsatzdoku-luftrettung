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
> | Stand | **20.10.2026 — DM-00 bis DM-04 gebaut, dazu Web 23.0.1 (E-DM-09), Option B (E-DM-08) und die WebKit-Wiederholung (E-DM-10); DM-05 gemergt (PR #112).** Nachtrag F-DM-12 nach rotem Stufe-2-Lauf 131 |
> | Entschieden | E-DM-01 bis E-DM-10 (Abschnitt 2); E-DM-07 ist durch E-DM-08 ersetzt |
> | Offen | keine Frage (Q-DM-01, -02 beantwortet am 20.10.2026) |
> | Umsetzung | **DM-00 erledigt** 19.10.2026 (Fahrplan, R87, Spanne). **DM-01 erledigt** 19.10.2026: `aufbauen.sh web` lädt fehlende Engines nach, neun Bibliotheken (F-DM-01); gemessen 3 von 3 nach Beiseitelegen von Firefox und WebKit. **DM-02 gebaut:** 22 Werkzeuge auf `starten()` aus `motor.mjs` (Engine aus `MOTOR` oder `--motor`, Zeile `Motor: <name> <fassung>` im Protokoll); `pruefen.sh` fährt 16 Proben mit `\"motoren\": true` je Engine, berichtet `rot:<engines>` und `motoren=chromium+firefox+webkit`; `bericht.py` Lage 6 (16 Lagen, 0 Fehlschläge); Muster `motoren` wählt alle 16, wenn `motor.mjs` oder das Register berührt ist. Einzeln in Firefox und WebKit gefahren: 10 Proben, zwei rot und behoben (F-DM-02, -03). **DM-03 gebaut:** Regel `motoren` im Bestandsriegel, Register `tools/pruefstand/motoren.md` (31 Werkzeuge), Selbstprobe 177 Fälle, 0 Fehlschläge, 110 von 110 Befundstellen. |
> | Halt | aufgehoben 20.10.2026 |
> | Fable-Schritte | keine |
> | Fächerung | keine (Kopf) |
> | Nummern | 390 bis 399 reserviert; vergeben: 390 (Anlass des Riegels), 391 (F-DM-04), 392 (F-DM-02). Frei: 393 bis 399. |

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
| E-DM-07 | **Laufzeit:** alle Stufen in drei Engines, auch `klein`. Die längere Laufzeit ist angenommen. **Ersetzt durch E-DM-08.** | Betreiberin, 19.10.2026 |
| E-DM-08 | **Engines je Stufe (Option B):** `klein` fährt die Browserproben in Chromium allein, `neben` und `haupt` in allen drei; Stufe 2 dreifach. Grund: Der volle dreifache Lauf hielt im Container nicht durch (F-DM-11). Preis: Eine Korrektur, die nur in Firefox oder WebKit bricht, kommt in `klein` durch. Stufe 1 liest es je Stufe gegen (Lage 6). Das Weitermachen nach einem Abbruch wird nicht gebaut. | Betreiberin, 20.10.2026 |
| E-DM-09 | **Q-DM-01 (a):** Das Soll der Rechenaufgabe (Median unter 1 s) gilt in jeder Engine. Behoben in diesem Schritt als Web 23.0.1: SHA-256 im Worker selbst gerechnet, gegen WebCrypto gegengeprüft. | Betreiberin, 20.10.2026 |
| E-DM-10 | **Q-DM-02 (a):** Ein `goto`/`reload` in WebKit, der mit „WebKit encountered an internal error" scheitert, wird an einer Stelle (`motor.mjs`) einmal wiederholt und gezählt; ein zweiter Fehlschlag ist rot. Seitenwechsel nach einem Klick werden nicht wiederholt. | Betreiberin, 20.10.2026 |

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
| F-DM-02 | mittel | `server/assets/unlock.js` (`kontopaarPflegen()`); `tools/proben/freigabe/probe.mjs` | **Das Schlüsselpaar entsteht nur auf der ersten Seite nach der Anmeldung.** Die Freigabeprobe ging sofort weiter; in Chromium kam das Paar trotzdem an, in Firefox und WebKit in 2 von 2 Läufen nicht („nach 20 s noch keins", 10 von 23 verfehlt). Wartet sie auf der ersten Seite: Paar nach 1152 ms (Firefox), 596 ms (WebKit), 599 ms (Chromium), 23 von 23 in allen drei. | Probe wartet auf der ersten Seite (DM-02). Die Anwendung bleibt, wie sie ist: Backlog Nr. 392 — eine Änderung an `unlock.js` ist eine Web-Fassung und gehört nicht in diesen Schritt. |
| F-DM-03 | niedrig | `tools/proben/csp-browser/browserprobe.mjs` (`bildProbe`) | **Die CSP-Probe erkannte eine Sperre nur am Wortlaut von Chromium** („Refused to load the image"). Firefox und WebKit schreiben es anders; die Gegenprobe „img-src sperrt einen nicht gelisteten Host" war in beiden rot, obwohl die Richtlinie sperrte. | Gezählt wird jetzt das Ereignis `securitypolicyviolation` (`disposition` „enforce", `img-src`, Host); 37 von 37 in allen drei Engines. |
| F-DM-04 | niedrig | `tools/pruefstand/motoren.md` | **Elf Browserwerkzeuge ruft kein Prüfstand** — sie laufen nur von Hand, ob sie grün sind, weiß niemand. Darunter die Angriffswerte (R20). | Im Register als „von Hand" mit Grund; Backlog Nr. 391. |
| F-DM-05 | niedrig | `tools/motor.mjs` (`codeSchritt`) | **Ein Lauf der CSP-Probe in WebKit scheiterte am Zweitfaktor** („Code passt nicht", zweimal), unmittelbar nach einem Lauf in Firefox; der nächste Lauf ging durch. Vermutlich ein Code, den der Lauf davor im selben Zeitfenster verbraucht hatte. | Beobachtet; tritt es im Prüfstand auf, wo die Engines unmittelbar nacheinander laufen, ist es ein Befund mit Zahl. |
| F-DM-06 | mittel | `tools/bedienprobe/wege/ap2.mjs`, `ap3.mjs` | **Neun Wege fanden in Firefox und WebKit „keinen Einsatz mit Aufzeichnung"**, weil sie `dayMissions[].track` lasen, bevor `EdOrtsdaten` die Hüllen geöffnet hatte; in Chromium war es zur Stelle. | Sie warten bis 20 s, bis eine Spur offen ist (DM-02). Danach 73 und 75 von 77 in Firefox. |
| F-DM-07 | niedrig | `tools/bedienprobe/wege/betrieb_server.mjs` | **`page.pdf()` gibt es nur in Chromium**; der Weg `betrieb-server-schluesselwechsel` scheiterte in Firefox und WebKit daran. | Seitenzahl des Blatts nur in Chromium, benannt (E-DM-05, Register). |
| F-DM-08 | niedrig | `tools/motor.mjs` (`abgebrochenerSchriftabruf()`) | **Firefox meldet abgebrochene Schriftabrufe als Konsolenfehler** (`status=2152398850`, NS_BINDING_ABORTED), wenn ein Werkzeug schneller weiterlädt als die Schrift — Bilderlauf (Papierkorb) und Registrieren. | Eine Erkennung an einer Stelle; Bilderlauf Rauschklasse 4, Selbstprobe 17 von 17 (Fall 16 Rauschen, Fall 17 ein anderer Status bleibt Fehler). |
| F-DM-09 | niedrig | `tools/motor.mjs` (`harmloserResizeHinweis()`) | **WebKit wirft „ResizeObserver loop completed with undelivered notifications" als Seitenfehler** — Tagesübersicht, Schublade, Schnittvorschau bei 1920 px; wiederholbar. Der Beobachter in `map_layers.js` bündelt über requestAnimationFrame und ändert keine Größe; die Meldung ist der Hinweis der Spezifikation, kein Fehler. | Genau dieser Wortlaut zählt im Bilderlauf nicht; „loop limit exceeded" und alles andere bleibt Fehler (Gegenprobe 3 von 3). |
| F-DM-10 | niedrig | `tools/bedienprobe/wege/ap4a.mjs` | **`ap4a-kurzname-im-band` lief in Firefox im vollen Lauf in seine 15 s** beim Speichern des Diensttags, einzeln nie; die Meldung sagte nur „Timeout". | 30 s, und der Fehler nennt den Text in `#savestate`. Ob Firefox nur langsamer speichert, zeigt der nächste volle Lauf. |
| F-DM-11 | — | Container | **Der Container wurde zweimal mitten in langen Läufen neu gestartet** (Exit 137 im Warten, beide nach rund 40 bis 50 Minuten), wie in F-S11-62. Ein voller Prüfstand dauert mit drei Engines länger als vorher (gemessen bis zum Abbruch: Bedienprobe 412/332/350 s, Bilderlauf 520/447 s). | Zwischenstände werden nach jedem Stück committet. Ob der letzte Prüfstand in einem Zug durchkommt, ist offen. |
| F-DM-12 | hoch | `.github/workflows/auslieferung.yml`, Stufe 2 | **Der erste Lauf nach dem Merge (131, 20.10.2026) war in Stufe 2 rot, bevor er maß:** `playwright install --with-deps` nannte wegen `PLAYWRIGHT_HOST_PLATFORM_OVERRIDE=ubuntu24.04-x64` die Paketnamen von 24.04, und `libicu74`, `libxml2`, `libvpx9`, `libx264-164` gibt es auf 26.04 nicht (apt Rückgabe 100). Im Prüfdokument stand es als ungeprüft; hier ließ es sich nicht nachstellen. | Engines ohne `--with-deps`; Liste aus `install-deps --dry-run`, jeder fehlende Name durch das neueste Paket gleichen Stamms ersetzt und protokolliert. Örtlich: 78 von 78 aufgelöst, apt-Probelauf ok; mit erfundenen Namen 4 von 4 richtig ersetzt, 1 ohne Ersatz gemeldet. Auf 26.04 gemessen erst im nächsten Lauf. |
| F-DM-01 | mittel | `tools/sandbox/aufbauen.sh`; `Sandbox-Setup.md` 1 | **WebKit braucht im Abbild vom 19.10.2026 fünf Bibliotheken mehr** als die vier von PK-02: `libgtk-4-1`, `libevent-2.1-7t64`, `libgstreamer-plugins-bad1.0-0`, `libavif16`, `libgles2`. Nach dem Nachladen von WebKit meldete Playwright „Host system is missing dependencies" und nannte sie. Vier davon standen im Sitzungshook vor PK-02 — PK-02 hatte sie verworfen, weil sie damals schon im Abbild lagen. | In `aufbauen.sh` ergänzt (DM-01); danach 3 von 3. |

## 4a. Fragen an die Betreiberin

| Nr. | Frage | Stand |
|---|---|---|
| Q-DM-01 | **Beantwortet 20.10.2026: (a), E-DM-09.** **Die Rechenaufgabe der Registrierung braucht in Firefox meist über 1 s.** Soll aus SR-08: Median unter 1 s, ungedrosselt. Gemessen (je 9 Läufe): Chromium 482 ms, WebKit 371 ms; Firefox 1365, 544 und 1333 ms in drei vollen Läufen. Der Weg `registrieren` der Bedienprobe ist damit in Firefox meist rot. Drei Wege: (a) das Soll gilt in jeder Engine — dann ist es ein Befund an der Anwendung (Schwierigkeit oder Worker, eine Web-Fassung, eigener Schritt), und der Prüfstand bleibt rot, bis er behoben ist; (b) das Soll gilt je Engine mit eigener Grenze (z. B. Firefox 2 s), begründet und im Weg benannt; (c) gemessen wird überall, verlangt nur in Chromium, Firefox als Zahl im Bericht. | beantwortet |
| Q-DM-02 | **Beantwortet 20.10.2026: (a), E-DM-10.** **WebKit bricht sporadisch Seitenwechsel ab** („WebKit encountered an internal error" bei `goto` oder `waitForNavigation`), in jedem vollen Lauf der Bedienprobe an anderen Wegen: einmal 1 Weg (Zweitfaktor, Zeitüberschreitung beim Abmelden), einmal 3 (Sicherungsziele, Dialogfehler, frischer Code); einzeln gefahren sind sie grün. Dasselbe im Bilderlauf (Nachtdienst, einmal). Bekannt seit F-RW-23 (Nr. 301). Zwei Wege: (a) ein Seitenwechsel, der genau mit dieser Meldung scheitert, wird an einer Stelle in `motor.mjs` einmal wiederholt und im Bericht gezählt — eine zweite Meldung ist rot; (b) nichts wiederholen: WebKit-Läufe sind dann oft rot, ohne dass die Anwendung etwas falsch macht. | beantwortet |

## 5. Prüfprotokoll

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| DM-00 | `pruefen.sh --stufe klein`; `decken.py` | 22 grün, 0 rot · 21 Decken, 0 gerissen (erst 1: R87-Kern 168 Zeichen über 160, gekürzt) | 19.10.2026 |
| DM-02 | Einzelläufe `MOTOR=firefox|webkit bash tools/proben/proben.sh NAME` | frist, abmelden, container, huelle, gpx, kontopaar, ortsdaten, wartungslauf: je 2 von 2 grün, `Motor:`-Zeile je 1; freigabe 0 von 2 → nach F-DM-02 3 von 3 (23/23); csp-browser 0 von 2 → nach F-DM-03 3 von 3 (37/37) | 19.10.2026 |
| DM-02 | `bericht.py lesen --selbstprobe` | 16 Lagen, 0 Fehlschläge (13 rote, 3 grüne; neu: 6, 6b, grün 6) | 19.10.2026 |
| DM-03 | `bestand.py --selbstprobe`; `bestand.py`; Gegenprobe am echten Bestand | 177 Fälle, 0 Fehlschläge, 110 von 110 Befundstellen · 0 Befunde · Zeile der GPX-Probe gestrichen → `motoren-ohne-zeile` und `motoren-verwaist`; `chromium.launch()` eingesetzt → `motoren-direkt`; zurück → 0 | 19.10.2026 |
| DM-02 | Einzelläufe nach dem Neustart (20.10.2026) | Kreislauf `csv`: Chromium 0 unerklärt von 11 234, Firefox erst Hänger (F-DM-02), nach `paarAbwarten()` 0 von 11 234, WebKit 0 von 11 234 · Kreislauf `edbak`: Firefox und WebKit je 0 unerklärt von 347 566 · Stilvergleich Firefox und WebKit rc 0 · Bilderlauf klein Firefox 1 Konsolenfehler (F-DM-08), WebKit 3 + 1 Bild ohne Abzug (F-DM-09, einmalig) · Bedienprobe Firefox 66 → 73 → 75 von 77, WebKit 66 → 74 von 77 | 20.10.2026 |
| DM-01 | Firefox und WebKit nach `scratchpad/` verschoben, `aufbauen.sh web` | „nachladen: firefox webkit"; Chromium 141.0.7390.37, Firefox 142.0.1, WebKit 26.0; rc 0; 12 s. Nicht gemessen: Nachholen der fünf Bibliotheken aus einem Abbild ohne sie | 19.10.2026 |
