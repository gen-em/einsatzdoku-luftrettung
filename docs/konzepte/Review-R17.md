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
Funktionsnamen; **die Leser laufen mit Opus**, Fable führt zusammen und
prüft die Hoch- und Mittel-Befunde am Quelltext nach (E-R17-05). R17-00,
-02, -03, -04 seriell: erzählender Text, ein Ton.
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

**Schritt 12b (seit 20.10.2026):** Rahmenplan Schritt **12b** — „P6, Stücke
2 bis 12: Review und Bereinigung" (R17, R69, R86); Voraussetzung „12a
gemergt" erfüllt am 20.10.2026 (PR #116, #117; `main` bei `f17380c`, Web
23.0.2, Uhr 4.0.0, Android 0.18.0). Auftrag, Lesweise und Stücke stehen in
**Abschnitt 7**, die Pakete R17-05 bis R17-17 in Abschnitt 4, die Befunde
der Stücke in 2.3a. **Modell:** Fable für jedes Paket des Reviews; **Fable
liest jedes Stück selbst** (E-R17-14) — Unter-Agenten nur lesend für
mechanische Vorarbeit. **Versionsstufe:** keine für den Review (nur
`docs/`); die Pakete nach der Freigaberunde stufen nach `CLAUDE.md` 2.
**Zweig:** `claude/lucid-cannon-nvv86i`, aufgesetzt auf `origin/main` bei
`f17380c`. **Backlog-Spanne:** **400 bis 409** (E-SD-21, zuerst gepusht in
`e99a855`). **Prüfdokument:** `Pruefdokument-R17-Review.md` wird
fortgeschrieben (E-R17-15). **Zweig von Schritt 12** war
`claude/exciting-brown-0n9et2` (Absatz darüber), gemergt mit PR #103.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **20.10.2026 — R17-09 erledigt: Stück 5, Verwaltung und Admin, gelesen.** 31 Dateien, 18 322 Zeilen (7.3.5), jede von Fable selbst. **Vier Befunde** (2.3a): F-R17-43 (niedrig — „Neue Codes erzeugen" in `einstellungen.php` verlangt keinen frischen Code: Wer eine Sitzung erbeutet, erzeugt zehn neue Wiederherstellungscodes und liest sie ab — ein dauerhafter zweiter Faktor, genau das, was E-SR-31 beim Passkey verhindert), F-R17-44 (niedrig — `zeitraum.php` prüft den Monat nur auf zwei Ziffern: `m=13` schreibt eine PHP-Warnung in den Reiter System und einen Titel ohne Monat), F-R17-45 (niedrig — die CSV der Gerätemodelle schreibt die Selbstauskunft der Geräte ohne Formelschutz; ein Modellname mit `=` am Anfang wird in Excel zur Formel), F-R17-42 (Hinweis — `DEMO_GESPERRT` in `admin_user.php` lässt `konto_status`, `konto_grenzen` und `totp_zuruecksetzen` durch, und der Reset holt eine Sperre nicht zurück). **F-R17-07 und -08 bestätigt** (Nr. 371: drei Schreibwege gegen ein BetreiberIn-Konto ohne Riegel; der Klartext-Dump steht nicht in `ZF_FRISCH_HANDLUNGEN`), **Nr. 381 bestätigt** (Suche und Zeitraum holen und entschlüsseln den ganzen Bestand, bevor sie 200 zeigen), F-R17-02 und -06 unverändert. Dazu **P-R17-09** (Produktiv: Protokoll nach fremden `totp_codes_erneuert`). Aufräumposten: keine Karteileiche (140 Funktionen, alle genannt), ein toter Zweig, ein Erststart-Vermerk vor dem `INSERT`, drei Handgriffe ohne Treffermeldung, 1 012 Kommentarverweise. PHPStan: 201 Meldungen im Stück, 181 Rauschen, 20 gelesen, 0 Befunde. **Als Nächstes: R17-10 — Stück 6, Betrieb** (7.3). Davor: **20.10.2026 — R17-08 erledigt: Stück 4, Einsatz, Diensttag, Spur, Ingest, gelesen.** 36 Dateien, 20 019 Zeilen (7.3.4), jede von Fable selbst. **Vier Befunde** (2.3a): F-R17-39 (mittel — `einsatz.php` liest `n_gespeichert` aus `spur_stand()`, das es seit Web 23.0.0 nicht mehr liefert: Nach dem Wartungslauf, der eine Spur packt oder ausdünnt, rechnet die Seite null Punkte, der Menüeintrag „GPS-Daten als GPX" und die Plakette fehlen, und jeder Aufruf schreibt eine PHP-Warnung in den Reiter System; die GPX-Probe misst nur eine frische Spur), F-R17-38 (niedrig — die Umdatierung in `tageszuordnung_lib.php` fängt zwei Variablen ein, die es noch nicht gibt: zwei PHP-Warnungen je Umdatierung, sonst richtig), F-R17-40 (niedrig — der Import überspringt ein Datum ganz, sobald IRGENDEIN Diensttag dieses Datums im Papierkorb liegt, auch wenn daneben ein aktiver steht), F-R17-41 (Hinweis — `dup: overwrite` ohne `overwrite_id` umgeht die Kennungsprüfung des Imports). Die drei Altbefunde des Stücks (F-R17-09, -13, -28) bestehen, soweit dieses Stück sie sieht; **K-10 beantwortet** (gegenstandslos: seit S11-05d liest der Browser die GPX-Datei, der Server sieht kein XML mehr); Nr. 23 gelesen (kein Schreibweg dieses Stücks nimmt eine Reanimationsart an — sie liegt in der Hülle, und der Browser prüft sie; der eine Aufrufer von `pruef_reanimationsart()` ist der Rückweg der Sicherung, Stück 2). Dazu **P-R17-08** (Produktiv: Einsatz mit gepackter Spur, Reiter System). Aufräumposten: keine Karteileiche (193 Funktionen, alle genannt), eine tote Zelle, ein ungenutztes `use`, eine Phase 10, ein doppelter Abruf, 1 242 Kommentarverweise. PHPStan: 101 Meldungen im Stück, 87 Rauschen, 14 gelesen, 5 davon die zwei Befunde F-R17-38 und -39. **Als Nächstes: R17-09 — Stück 5, Verwaltung und Admin** (7.3). Davor: **20.10.2026 — R17-07 erledigt: Stück 3, Zugang und Sitzung, gelesen.** 31 Dateien und der API-Teil von `db.php`, 11 238 Zeilen (7.3.3), jede von Fable selbst. **Zwei Befunde** (2.3a): F-R17-36 (mittel — `rate_merkmale()` hängt an jeden Konto-Topf die Adresse mit derselben Schwelle: Der Anschluss ist bei 10 Fehlversuchen gesperrt, nicht bei 50, `login_ip` greift nie, `Technik.md` 5e.2 sagt das Gegenteil; beim Schlüsselblatt sperren 3 Fehlversuche den Anschluss trotz „je Konto"), F-R17-37 (niedrig — der Notzugang zählt nur die eingetippte Adresse, kein Anschluss, kein globaler Zähler: bcrypt-Last ohne Grenze). **F-R17-32 verschärft:** dieselbe Lücke an drei weiteren Stellen in `einstellungen.php`. Die drei Altbefunde des Stücks (F-R17-22, -23, -31) bestehen; **K-7 und K-15 beantwortet** (Adresswechsel im Profil mit Passwort, Link an die neue und Warnung an die alte Adresse; `csrf_ok()` hat den `is_string`-Riegel). P-R17-04 bleibt bei der Betreiberin, dazu **P-R17-06 und -07** (Dump von vor S7; Anschlusssperren auf Produktiv). Aufräumposten: zwei Karteileichen, eine zweite Schreibweise des Merkmals, 608 Kommentarverweise. PHPStan: 31 Meldungen im Stück, 22 Rauschen, 9 gelesen, 0 Befunde. **Als Nächstes: R17-08 — Stück 4, Einsatz, Diensttag, Spur, Ingest** (7.3). Davor: **20.10.2026 — R17-06 erledigt: Stück 2, Kryptographie und Schlüssel, gelesen.** 38 Dateien, 20 053 Zeilen (7.3.2), jede von Fable selbst (E-R17-14). **Drei Befunde und ein Hinweis** (2.3a): F-R17-32 (mittel — `api/kdf_upgrade.php` prüft das alte Anmelde-Token ohne Ratenschutz, die Geschwister haben ihn), F-R17-33 (niedrig — die Endmarken-Prüfung in `wiederherstellen.php` vergleicht zweimal dieselbe Zeichenkette; die S7-Umstellung hat die alte Schreibweise mitersetzt, vor der ihr eigener Kommentar warnt), F-R17-34 (niedrig — der Rückweg schreibt Standortnamen ohne die Prüfschicht), F-R17-35 (Hinweis — ein Paketteil ohne `edsk1:` kommt roh zurück, auch in einem Fassung-3-Paket). Die sieben Altbefunde des Stücks (F-R17-01, -02, -06, -10, -19, -24, -26) am Stand Web 23.0.2 nachgelesen: **alle sieben bestehen unverändert** (7.3.2). K-3 beantwortet (Mindestlänge 12 = `PW_MIN_LAENGE`). Aufräumposten: eine Karteileiche (`serverschluessel_zeile()`), drei tote Zweige seit Web 23, 987 Kommentarverweise. Vorsortierung örtlich mit PHPStan 2.3.1 (Stufe 4): 88 Meldungen im Stück, 73 Rauschen, 15 gelesen, eine davon ein Befund (F-R17-33). **Als Nächstes: R17-07 — Stück 3, Zugang und Sitzung** (7.3). Davor: **20.10.2026 — Schritt 12b läuft; R17-05 Buchführung erledigt.** Rahmenplan Fassung 167 (Fahrplanzeile 12b auf „Umsetzung", Kopf: Nr. 387 gemergt, `main` bei Web 23.0.2), Spanne 400 bis 409 zuerst gepusht (`e99a855`), gemergte Spannen 12a und 19 aus dem Backlog-Kopf gestrichen (die Decke `backlog-kopf` riss sonst mit 61 Zeilen), dieses Dokument mit Abschnitt 7 (Auftrag, Lesweise, elf Stücke mit Umfang und zugehörigen Posten) und den Paketen R17-05 bis R17-17, Prüfdokument fortgeschrieben (E-R17-15). Drei Entscheidungen der Betreiberin E-R17-14 bis -16 (Fable liest selbst; Prüfdokument fortschreiben; Vorsortierung örtlich). **Als Nächstes: R17-06 — Stück 2, Kryptographie und Schlüssel** (7.3). Davor: **11.10.2026 — Schritt 12 abgeschlossen.** Die Betreiberin hat das Modell freigegeben (P-R17-01); Erledigt-Zeile in Rahmenplan 8 (Fassung 156), Fahrplanzeile heraus, **PR #103** gegen `main`. Dieses Dokument bleibt für 12b (E-R17-02): Die Stücke 2 bis 12 schreiben mit denselben Zählern fort (nächste Nummern: E-R17-14, F-R17-32, Q-R17-09). Davor: **R17-04: die Antworten sind eingearbeitet, das Modell wartete auf die Freigabe (P-R17-01).** Acht Entscheidungen E-R17-06 bis -13: kein Sofortpaket (alles in 12b), Argon2id nein, `CryptoKey` ja in 12b (Nr. 370), S11 mit Zweck Server und der Klartextliste aus E-R17-10, Admin-Riegel (Nr. 371), Nr. 263 offen für 12b, der Krypto-Review gelöscht (Stand je K-Nummer in 3.4). Nr. 146 erledigt. Rahmenplan Fassung 155: Zuarbeit „Umgebung `produktion` beschränken" (F-R17-04). **Als Nächstes: Freigabe des Modells durch die Betreiberin, dann Erledigt-Zeile, Fahrplanzeile heraus, Pull Request.** Davor: **R17-03: acht Fragen an die Betreiberin gestellt (2.2).** Q-R17-01 (Sofortpaket für F-R17-02 und -03? Zusage F-R17-01 sofort berichtigen?), -02 und -03 (Nr. 146: Argon2id nein, `CryptoKey` ja in 12b), -04 und -05 (S11: Zweck Server; was Klartext bleibt), -06 (Admin gegen BetreiberIn-Konten: nein), -07 (Nr. 263: nicht umsetzen), -08 (Krypto-Review bleibt bis 12b). **Als Nächstes: die Antworten, dann R17-04** (Einarbeiten, Prüfdokument, Freigabe, Erledigt-Zeile, PR). Davor: **R17-02 erledigt: das Modell steht.** `docs/Bedrohungsmodell.md` nach der Gliederung in 3.1: fünf Werte (1), sechzehn Angreiferlagen A bis P (2), dreizehn Zusagen mit Grenze (3), die Bausteine in vier Tabellen gegen die Lagen (4), zwanzig Restrisiken mit Zuordnung (5), die Entwurfsfragen für S11 mit Empfehlungen zu Nr. 146 und Nr. 263 (6). Eingetragen im Bereich `c` der Textprobe mit zwei begründeten Ausnahmen (Fachbegriff Spur, Plattformnennung Garmin — das Modell unterscheidet die Uhren an ihren Sicherheitseigenschaften); verlinkt aus `README.md`, `Technik.md` (2, 4.98, 4.99b) und `CLAUDE.md` 4. **Als Nächstes: R17-03** — die Fragen an die Betreiberin; dort hält der Schritt an. Davor: **R17-01 erledigt: die Bestandsaufnahme.** Drei lesende Leser (Opus) haben den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegen die Doku gelesen; Fable hat die Hoch- und Mittel-Befunde am Quelltext nachgeprüft und das Inventar verdichtet: **55 Bausteine** (3.2), **31 Befunde** (2.3) — fünf **hoch** (die S10-Zusage überzeichnet das Offline-Raten, F-R17-01; die Freigabe liefert einem Admin den Klartext jedes Kontos ohne Spur, F-R17-02; Gradle erbt den App-Signaturschlüssel, F-R17-03; ein Probelauf fordert `produktion` von jedem Zweig an, F-R17-04; die Pflichtstücke des Wiederanlaufs sind seit dem 14.09.2026 offen, F-R17-05), fünfzehn mittel, acht niedrig, zwei Hinweise. Was keiner sicher lesen konnte, steht im Prüfdokument 1. **Als Nächstes: R17-02** (das Modell schreiben). Davor: **R17-00 erledigt** (Spanne, Fahrplanzeile, dieses Dokument, Prüfdokument). |
> | Entschieden | E-R17-01 bis E-R17-16 (Abschnitt 2.1) — **E-R17-14 bis -16 von der Betreiberin am 20.10.2026** (Fächerung, Prüfdokument, Vorsortierung); E-R17-01 bis -03 und **E-R17-06 bis -13 (die Antworten auf Q-R17-01 bis -08)** von der Betreiberin am 11.10.2026, E-R17-04 am 28.09.2026, E-R17-05 aus der Umsetzung. |
> | Offen | **12b:** die Stücke 2 bis 12 (R17-06 bis -16), dann die Freigaberunde (R17-17) über die 31 Befunde aus 2.3, die Befunde der Stücke (2.3a), die offenen Posten aus 3.4 (K-3, K-7, K-10, K-15, AN-5) und die zehn Backlog-Punkte mit Ziel 12b (Nr. 21, 77, 188, 263, 341, 370, 371, 380, 381, 382); danach der Paketschnitt. Schritt 12 ist abgeschlossen (PR #103 gemergt). |
> | Umsetzung | **R17-00 erledigt** 11.10.2026 (`fca8549`). **R17-01 erledigt** 11.10.2026 (`3b6f330`; Inventar I-01 bis I-55, Befunde F-R17-01 bis -31; Prüfdokument 1 und 2 fortgeschrieben). **R17-02 erledigt** 11.10.2026 (`docs/Bedrohungsmodell.md`, Textprobe-Bereich, Verweise; keine neuen Befunde — das Modell nennt F-R17-01 bis -31 an Ort und Stelle). **R17-03 beantwortet** 11.10.2026 (Q-R17-01 bis -08 → E-R17-06 bis -13). **R17-04 in Arbeit:** Antworten eingearbeitet (Modell 5 und 6, Backlog 146 → erledigt, 263 → 12b, Nr. 370 und 371 neu, Rahmenplan Fassung 155, Krypto-Review nach 3.4 und gelöscht); **Freigabe 11.10.2026, Abschluss geschrieben** (Rahmenplan Fassung 156, PR #103). **R17-05 erledigt** 20.10.2026 (Spanne `e99a855`; Buchführung im Commit mit dem Prüfbericht). **R17-06 erledigt** 20.10.2026 (Stück 2: 38 Dateien gelesen, F-R17-32 bis -35, sieben Altbefunde bestätigt, K-3 beantwortet; 7.3.2; Prüfbericht in der Commit-Nachricht). **R17-07 erledigt** 20.10.2026 (Stück 3: 31 Dateien gelesen, F-R17-36 und -37, F-R17-32 verschärft, drei Altbefunde bestätigt, K-7 und K-15 beantwortet, P-R17-07; 7.3.3; Prüfbericht in der Commit-Nachricht). **R17-08 erledigt** 20.10.2026 (Stück 4: 36 Dateien gelesen, F-R17-38 bis -41, drei Altbefunde nachgelesen, K-10 beantwortet, Nr. 23 gelesen, P-R17-08; 7.3.4; Prüfbericht in der Commit-Nachricht). **R17-09 erledigt 20.10.2026** (Stück 5 gelesen, F-R17-42 bis -45 in 2.3a, F-R17-07 und -08 bestätigt, Nr. 371 und 381 bestätigt, F-R17-02 und -06 unverändert, P-R17-09; 7.3.5; Prüfbericht in der Commit-Nachricht). |
> | Fable-Schritte | Schritt 12: alle. **12b:** alle Pakete des Reviews (R17-05 bis -17); die Pakete nach der Freigaberunde: Kryptographie mit Fable, alles andere mit Opus (K8, E-PV-5 (7)), je Paket im Paketschnitt markiert. |
> | Fächerung | **R17-01 gefahren:** drei lesende Leser mit **Opus** (Server und Browser 129 Werkzeugaufrufe, 15 min; Uhr und Android 71, 10 min; Kette und Betrieb 105, 11 min), ohne Nebenwirkung — Leser 3 hat über `gh api` nur gelesen (Rulesets, Zweigregeln; Umgebungen und Organisation mit 403 verweigert). Zusammenführung und Nachprüfung Fable, seriell. Die übrigen Pakete seriell. **12b (E-R17-14):** keine Leser — Fable liest jedes Stück selbst, seriell; Unter-Agenten nur lesend für mechanische Vorarbeit (Aufruferlisten, Verweiszählung, ungenutzte Regeln), ohne Nebenwirkung, je Stück im Prüfprotokoll genannt. |
> | Nummern | 370 bis 379 reserviert; vergeben: **370** (Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`, 12b, E-R17-08), **371** (Admin darf BetreiberIn-Konten nicht anfassen, 12b, E-R17-11). **12b: 400 bis 409** reserviert (R17-05, `e99a855`); vergeben: noch keine. |

---

## 1. Auftrag

**Ziel von Schritt 12:** ein Dokument, kein Code — das **Bedrohungsmodell**
der Anwendung, als Stück 1 des R17-Reviews (R69). Es sammelt ein, was heute
verteilt liegt (`Technik.md` 4.97 bis 4.99q, der Krypto-Review vom
05.09.2026 — seit R17-04 nur noch in der Git-Historie, Stand in 3.4 —,
SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18), und sagt
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
| E-R17-14 | **Fable liest in 12b jedes Stück selbst**, seriell. Unter-Agenten nur für mechanische, lesende Vorarbeit (Aufruferlisten, Verweiszählung, ungenutzte Regeln), nie für die Lesung selbst. | Betreiberin, 20.10.2026 (Vorschlag Fable, Option a) | R86 will den Code **einmal, in Endfassung, von Fable** gelesen; Opus-Leser mit Nachprüfung (E-R17-05) wären ein Opus-Review mit Stichproben. E-R17-05 war für eine Bestandsaufnahme gegen die Doku richtig, für die Lesung des Codes nicht. Preis: Fable-Credits über elf Pakete. |
| E-R17-15 | **`Pruefdokument-R17-Review.md` wird fortgeschrieben**, kein eigenes Prüfdokument für 12b. | Betreiberin, 20.10.2026 (Vorschlag Fable) | Dieselben Zähler (P-R17-NN) und dieselbe Logik wie E-R17-02 für das Konzept; die offenen Punkte P-R17-03 bis -05 bleiben neben den neuen sichtbar statt in zwei Dateien. |
| E-R17-16 | **Maschinelle Vorsortierung nur örtlich** — PHPStan oder Psalm für toten PHP-Code, `vulture` für die Python-Werkzeuge, Android Lint, Compiler-Warnungen der Uhr, wo der Container es kann; nichts davon ins Repositorium, nichts in die Kette. Gelesen wird trotzdem alles (E-PV-5 (2)). | Betreiberin, 20.10.2026 (Vorschlag Fable) | Ein Werkzeug im Repositorium braucht einen Anlass und eine Anleitung (`Pruefablauf.md` 6.1, 6.2) — für eine einmalige Lesung ist das Aufwand ohne Riegel. Was die Vorsortierung liefert, steht je Stück mit Mittel und Zahl im Prüfprotokoll (5) und im Prüfdokument (2); ihre Grenzen in Prüfdokument 5. |
| E-R17-01 | **Das Modell ist eine eigene Datei, `docs/Bedrohungsmodell.md`**, normative Dokumentation neben `Technik.md` — kein Abschnitt darin. Es wird im selben Paket, in dem es entsteht, in den Bereich `c` der Textprobe eingetragen (`Pruefablauf.md` 6.6) und aus `README.md`, `Technik.md` und `CLAUDE.md` 4 verlinkt. | Betreiberin, 11.10.2026 (Vorschlag Fable) | Planung v1.0 ließ die Wahl offen („eigener Abschnitt oder eigene Datei, entscheidet der Review"). `Technik.md` hat 13 600 Zeilen; das Modell hat andere Leser (Betreiberin, Datenschutz, Review) und muss ohne die Technik lesbar sein. |
| E-R17-02 | **Kürzel `R17`; dieses Dokument bleibt nach Schritt 12 stehen.** Es ist das Konzept von 12 (Statusblock, Pakete, Prüfprotokoll) und zugleich die Sammelstelle des ganzen Reviews; 12b schreibt mit denselben Zählern fort. Die Erledigt-Zeile von 12 nennt es; gelöscht wird es erst mit dem Abschluss von 12b. | Betreiberin, 11.10.2026 | Abweichung von R62 („das Konzept wird gelöscht"), weil der Rahmenplan es so vorsieht („Review-R17.md beginnt damit", „Fortsetzung aus 12") und eine Fundliste, die über 12a hinweg zwei Dateien hätte, zwei Zählungen hätte. |
| E-R17-03 | **Push nach jedem Paket auf `claude/exciting-brown-0n9et2`** (K7); **kein Pull Request vor der Freigabe des Modells** durch die Betreiberin. | Betreiberin, 11.10.2026 | K7 und `CLAUDE.md` 8; der PR ist die Phase, nicht ein Paket. |
| E-R17-06 | **Kein Sofortpaket vor 12a.** F-R17-01, -02 und -03 bleiben „hoch" und gehen als Pflichtpakete in die Freigaberunde von 12b; die überzeichnete S10-Zusage (F-R17-01) bleibt bis dahin in `CLAUDE.md` 4, `Technik.md` 3 und SP-3 stehen — `CLAUDE.md` 4 sagt seit R17-02, dass bei Abweichung das Modell gilt. | Betreiberin, 11.10.2026 (Q-R17-01: „Alles erst in 12b") | Fable hatte F-R17-02 und -03 als kleines Sofortpaket und die Berichtigung der Zusage sofort empfohlen (R69: Bruch einer Zusage → Sofortpaket). Preis der Entscheidung: F-R17-02 (ein Admin liest den Klartext jedes Kontos ohne Spur) bleibt bis nach S11 offen; die Anlage hat heute ein Konto. |
| E-R17-07 | **Argon2id wird nicht weiterverfolgt.** Nr. 146 damit zur Hälfte erledigt; die Begründung steht im Modell 6.5. | Betreiberin, 11.10.2026 (Q-R17-02) | Nach S10 bringt das Raten ohne Anmeldung keinen Datenschlüssel; mit dem Pfeffer aus F-R17-01 bräuchte auch das Raten `config.php`. Ein WASM-Fremdbestandteil mit 64 MB je Ableitung verteidigte dann nur noch gegen Lage B. |
| E-R17-08 | **Der Inhaltsschlüssel wird ein nicht-extrahierbarer `CryptoKey`** — als Paket in 12b (Fable), nach der scharfen CSP (F-R17-23). Backlog **Nr. 370**. Nr. 146 damit erledigt. | Betreiberin, 11.10.2026 (Q-R17-03) | Ein XSS könnte weiter entschlüsseln, den Schlüssel aber nicht mitnehmen — und ein mitgenommener Schlüssel gilt dauerhaft (F-R17-10). Erste Linie CSP, zweite Linie `CryptoKey`. |
| E-R17-09 | **S11 hat den Zweck „Server":** Verschlüsselung vor dem Upload; das verlorene Gerät bleibt benanntes Restrisiko (Modell R13, R14), geparkte Pakete auf der Garmin bekommen eine Frist (F-R17-28). „Beim Schreiben" ist ein eigener späterer Schritt, falls gewünscht. | Betreiberin, 11.10.2026 (Q-R17-04) | SP-9 Punkt 6 („ein Verlust kostet das Trennen, nicht mehr") gilt nur bei Verschlüsselung beim Schreiben, und die baut die Anzeige der Uhr um (`Track.readPoints()`). S11 wird kleiner und ehrlicher. |
| E-R17-10 | **In S11 bleiben Klartext:** Zeiten, `seq`, Kennungen, Strecke, Anstieg, Stammdaten, Besatzung, Einsatzmittel. **Verschlüsselt werden** Spur, Phasenkoordinaten, Reanimationsverlauf und das Transportziel; die Zählung „Fahrten je Klinik" wandert in den Browser und rechnet nach dem Entsperren (F-SP-7). | Betreiberin, 11.10.2026 (Q-R17-05) | Der Server rechnet mit Koordinaten nirgends (`Konzept-V1-Ortsdaten.md` 3.1); R78 (6) hat das Transportziel ausdrücklich in den Umfang genommen. |
| E-R17-11 | **Ein Admin darf BetreiberIn-Konten nicht sperren, löschen oder umadressieren.** Riegel in `admin_user.php`, Zeile in der Rollenprobe; kleines Paket in 12b. Backlog **Nr. 371**. | Betreiberin, 11.10.2026 (Q-R17-06) | F-R17-07: Heute ist nur die letzte BetreiberIn geschützt; die Matrix 4.99p misst `{ziel}` = `user`. |
| E-R17-12 | **Nr. 263 bleibt offen, Ziel 12b.** Der Code-Review liest die Wache in Stück 10 (Werkzeuge) und entscheidet dann über die Abhilfe; das Modell benennt die Grenze (G5, 6.6) ohne Stand. | Betreiberin, 11.10.2026 (Q-R17-07: „Offen lassen für 12b") | Fable hatte `nicht umsetzen` empfohlen (eine Hash-Schnittstelle wäre ein neuer Angriffsweg). Die Betreiberin will die Lesung von 12b abwarten. |
| E-R17-13 | **`Review-Krypto-Sicherheit.md` wird mit R17-04 gelöscht.** Die Git-Historie behält sie (zuletzt in `bc08eda`); der Stand je K-Nummer und AN-Nummer steht in 3.4, damit 12b weiß, was noch zu lesen ist. | Betreiberin, 11.10.2026 (Q-R17-08: „Jetzt löschen") | Fable hatte „bleibt bis 12b" empfohlen. Ihr Abschnitt 2 ist durch das Modell ersetzt, ihre Befunde sind teils erledigt, teils in 3.4 übergeben. |
| E-R17-05 | **Die Leser von R17-01 laufen mit Opus, die Zusammenführung mit Fable.** Ihre Berichte bleiben außerhalb des Repositoriums; was zählt, steht verdichtet in 3.2 und 2.3, und jeder Befund trägt, wer ihn gelesen hat. | Fable, 11.10.2026 | Eine Messung ist lesend und gut fächerbar (`CLAUDE.md` 7); drei Leser mit Fable kosteten das Dreifache für eine Vorarbeit, die ohnehin nachgeprüft wird. Die Rohberichte (rund 100 kB) sind Lesungen mit Unsicherheiten; ins Repositorium gehört nur, was gegengelesen oder als Lesung gekennzeichnet ist. |
| E-R17-04 | **Schritt 12 läuft nicht parallel zu 18**, sondern danach, wie der Rahmenplan es vorsah. | Betreiberin, 28.09.2026 | Fable hatte das Parallelfahren vorgeschlagen (das Modell ist zu neun Zehnteln unabhängig von 18) und den Preis genannt (Nachlese-Paket, R-Nummer, Konflikte in den Steuerungsdokumenten). Die Betreiberin hat die Reihenfolge behalten; 18 war am 05.10.2026 gemergt. |

### 2.2 Fragen an die Betreiberin (Q)

Gestellt mit R17-03 am 11.10.2026. Je Frage die Lage, die Empfehlung und
ihr Preis; die Antwort wird E-R17-NN und geht mit R17-04 ins Modell
(Abschnitt 6) und in die Backlog-Kopfzeilen. **Alle acht sind am 11.10.2026
beantwortet** (E-R17-06 bis -13); vier wie empfohlen, vier anders (Q-01,
-07, -08) oder mit anderem Weg.

| Nr. | Frage | Lage | Empfehlung | Preis | Antwort |
|---|---|---|---|---|---|
| Q-R17-01 | **Sind F-R17-01, -02 und -03 „kritisch"** — Bruch einer Zusage, also Sofortpaket **vor** 12a (R69) — oder „hoch", also Pflichtpaket in 12b? | F-R17-01: Die Zusage „kein Offline-Angriff mehr" ist falsch, der Code tut, was er soll — die Zusage bricht, nicht die Anlage. F-R17-02: Ein Admin liest den Klartext jedes Kontos ohne Spur — bricht die Kontentrennung, die Nr. 286 gerade herstellen wollte. F-R17-03: Gradle sieht den Signaturschlüssel — bricht E-PK-63 und ist nicht rücksetzbar | **F-R17-02 und F-R17-03 ins Sofortpaket**, beide klein (Freigabe nur an das Herkunftskonto oder dieselbe Adresse, mit Protokoll, Mail und frischem Code; Gradle in einem eigenen Schritt ohne `APK_*`). **F-R17-01 zweiteilig:** die Zusage in `CLAUDE.md` 4, `Technik.md` 3 und SP-3 **sofort** berichtigen (Doku, kein Code), den Pfeffer vor bcrypt als Pflichtpaket in 12b (Fable). F-R17-04 und -05 sind Zuarbeiten von dir, jetzt | Ein Sofortpaket vor 12a kostet eine Opus-Sitzung und eine Auslieferung; ohne sie liegt F-R17-02 bis nach S11 offen | **Alles erst in 12b** — E-R17-06 |
| Q-R17-02 | **Nr. 146, Argon2id statt PBKDF2** — weiterverfolgen? | Nach S10 bringt das Raten ohne Anmeldung keinen Datenschlüssel; mit dem Pfeffer aus F-R17-01 braucht das Raten `config.php` — Argon2id verteidigte dann nur noch gegen Lage B, die alles hat. Preis wäre ein WASM-Fremdbestandteil, 64 MB je Ableitung, dritter Ableitungsweg | **Nein.** Nr. 146 damit zur Hälfte erledigt; die Begründung steht im Modell 6.5 | keiner | **Nein** — E-R17-07 |
| Q-R17-03 | **Nr. 146, Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`** — bauen? | Ein XSS könnte weiter entschlüsseln, den Schlüssel aber nicht mitnehmen — und ein mitgenommener Schlüssel gilt dauerhaft (F-R17-10). Umbau mittel: IndexedDB statt `sessionStorage`, Lebensdauermodell, alle `getContentKey`-Aufrufer | **Ja, als Paket in 12b** (Fable), **nach** der scharfen CSP (F-R17-23): erste Linie CSP, zweite Linie `CryptoKey` | eine Fable-Sitzung in 12b; das Lebensdauermodell „ein Tab, ein Schlüssel" ändert sich (Handbuch) | **Ja, in 12b nach der CSP** — E-R17-08, Nr. 370 |
| Q-R17-04 | **S11: Zweck „Server" oder „Server und Gerät"?** (F-R17-13) | SP-9 verschlüsselt vor dem Upload: Das schließt Datenbank, Backup, Paket, Transport — nicht das verlorene Gerät, dessen Puffer Klartext bleibt (Garmin samt Reanimation, ohne Frist). „Beim Schreiben" baut die Anzeige der Uhr um (`Track.readPoints()`) und die Phasenkoordinate des Handys | **Zweck Server.** S11 benennt das Gerät als Restrisiko und gibt geparkten Paketen eine Frist (F-R17-28); „beim Schreiben" ein eigener späterer Schritt | S11 wird kleiner und ehrlicher; das Gerät bleibt, was es heute ist | **Zweck Server** — E-R17-09 |
| Q-R17-05 | **S11: Was darf Klartext bleiben?** | Der Server braucht Zeiten, `seq`, Kennungen, Strecke und Anstieg (Zahlen), Stammdaten; Besatzung und Einsatzmittel sind nicht Teil von R78. Die Zählung „Fahrten je Klinik" braucht das Transportziel im Klartext — R78 hat es ausdrücklich in den Umfang genommen | **Zeiten, `seq`, Kennungen, Strecke, Anstieg, Stammdaten, Besatzung, Einsatzmittel bleiben Klartext; das Transportziel wird verschlüsselt und die Zählung je Klinik wandert in den Browser** (F-SP-7) | die Statistik je Klinik rechnet nach dem Entsperren, nicht mehr serverseitig | **Wie empfohlen** — E-R17-10 |
| Q-R17-06 | **Darf ein Admin ein BetreiberIn-Konto sperren, löschen oder umadressieren?** (F-R17-07) | Heute ja, nur die letzte BetreiberIn ist geschützt; die Rollenprobe misst den Fall nicht | **Nein** — Riegel in `admin_user.php`, Zeile in der Rollenprobe; Paket in 12b | klein | **Nein, Riegel bauen** — E-R17-11, Nr. 371 |
| Q-R17-07 | **Nr. 263 (Grenze der Integritätswache): Stand `nicht umsetzen`?** | Die Abhilfe wäre eine Schnittstelle zum Hashen der eigenen Quelldateien — ein neuer Angriffsweg für ein Problem, das der Vergleich nur teilweise löst (Modell G5, 6.6) | **Ja**, `nicht umsetzen` mit der Begründung im Modell; stattdessen F-R17-15 (Anleitung, Alarmweg) und F-R17-18 (lesende Probe gegen Produktiv) | die Grenze bleibt und ist benannt | **Offen lassen für 12b** — E-R17-12 |
| Q-R17-08 | **`Review-Krypto-Sicherheit.md` — bleibt sie?** Der Rahmenplan sagt, sie „kann im Bedrohungsmodell aufgehen" | Ihr Abschnitt 2 ist durch das Modell ersetzt, ihre Befunde K-1 bis K-16 sind teils erledigt, teils in 12b zu lesen (F-R17-30) | **Bleibt bis zum Abschluss von 12b** als Protokoll mit Datum im Kopf; 12b trägt je K-Nummer den Stand nach und löscht sie mit P6 | keiner | **Jetzt löschen** — E-R17-13; der Stand je K-Nummer steht in 3.4 |

### 2.3 Befunde (F)

Je Befund: Nummer, **Schweregrad** (kritisch / hoch / mittel / niedrig /
Hinweis), Fundstelle (Funktionsname oder Abschnitt, nicht Zeilennummer),
was gesehen wurde, Wirkung, Empfehlung. **Der Review behebt nichts** (K4,
R69). Wege: kritisch → Sofortpaket vor allem anderen; alles andere →
Freigaberunde und Pflichtpaket in P6 (12b). Die Spalte **geprüft** sagt,
wer die Stelle gelesen hat: *Fable* heißt am Quelltext nachgeprüft, *Leser*
heißt Lesung des Lesers (Opus), nicht gegengelesen. Der Schweregrad ist ein
Vorschlag; **ob F-R17-01, -02 und -03 als Bruch einer Zusage „kritisch"
sind und ins Sofortpaket gehören, fragt R17-03 die Betreiberin** (Q).

| Nr. | Grad | Fundstelle | Gesehen → Wirkung | Empfehlung | geprüft |
|---|---|---|---|---|---|
| F-R17-01 | **hoch** | `CLAUDE.md` 4, `Technik.md` 3 („ein Abzug allein genügt seither nicht mehr"), Kopf `serverkrypto_lib.php`, SP-3 — gegen `login.php`, `pw_handling.php`, `api/kdf_upgrade.php` (`password_hash($token)`) | Das Anmelde-Token sind die PBKDF2-Bytes 32–63 **ohne** Anteil, bcrypt-gehasht in `users.password_hash`. Wer nur die Datenbank hat, rät weiter offline (je Kandidat PBKDF2 600 000 + bcrypt); der Anteil verhindert nur das Öffnen der Hülle **aus dem Abzug**. Ein gefundenes Passwort führt über eine Anmeldung (Konten ohne Zweitfaktor) zum Anteil und damit zu allen `pat_blob`. Die Zusage „kein Offline-Angriff auf das Passwort mehr" ist **falsch**; richtig ist „kein Öffnen der Hülle aus dem Abzug allein" | (a) Die Zusage in `CLAUDE.md` 4, `Technik.md` 3, SP-3 berichtigen — sofort, Doku. (b) Pfeffer vor bcrypt: Server hasht `HMAC(konto_anteil, token)` statt `token` — dann braucht auch das Offline-Raten `config.php`. Kleiner Umbau in drei Dateien plus stille Umstellung beim nächsten Anmelden (Muster `kdf_upgrade`). Fable-Schritt | Fable |
| F-R17-02 | **hoch** | `admin_user.php` Aktion `freigeben`; `adminbackup_lib.php` `edbak_freigeben()`; `api/adminbackup_freigabe.php`; `Review-Krypto-Sicherheit.md` 2 (I), 4.3 („reicht nur Chiffretext heraus") | Ein Admin gibt das Paket **jedes** Kontos an **jedes** Konto frei, auch an sein eigenes (Auswahl: alle außer dem Besitzer; nur die Zieladresse ist abzutippen); **kein** `protokoll()`, **keine** Mail an das Herkunftskonto, **kein** frischer Code (nicht in `ZF_FRISCH_HANDLUNGEN`). Der Endpunkt entsiegelt und liefert dem Zielkonto Klartext-JSON aller Einträge, Diensttage und Spuren samt `pat_wrap_rc`, `pat_key_check`, Adresse und Name. `pat_blob` bleibt zu. **Wirkung:** Lage I liest still Ort, Zeit, Klinik, Reanimation, Besatzung, `bw_info`, `days.notes` jedes Kontos — genau den Abzug, den Nr. 286 der Rolle admin genommen hat | Freigabe nur an das Herkunftskonto selbst oder an ein Konto mit **derselben** Adresse (der Zweck ist „Konto neu aufgesetzt"), Protokolleintrag mit Urheber und Ziel, Mail an Herkunfts- und Zieladresse, frischer Code. Rollenprobe um den Fall erweitern | Fable |
| F-R17-03 | **hoch** | `tools/kette/appbau.sh` `android_bauen()`; `auslieferung.yml` Job `android`, Schritt „Bauen, signieren, Zertifikat prüfen" — gegen `Technik.md` 4.97g, `android/LIESMICH.md` 5, Kopf `appbau.sh` (E-PK-63 „Gradle sieht den Schlüssel nie"), `Pruefablauf.md` 2.5 | Die vier `APK_*`-Geheimnisse stehen im `env:` des Schritts; `./gradlew … assembleRelease` läuft darin als Kindprozess und erbt Keystore (Base64) und beide Passwörter — `System.getenv()` genügt jedem Plugin und jeder Abhängigkeit zur Bauzeit; Gradle-Abhängigkeiten ohne `verification-metadata.xml`. Die Zusage hält nur für die Datei auf der Platte. Der Schlüssel liegt auch bei Google und ist nicht rücksetzbar: Verlust = jede spätere Fassung „eine andere App" | Gradle in einem **eigenen Schritt ohne** die `APK_*`-Werte fahren (oder `env -i` mit Allowliste), Signatur im Folgeschritt; `gradle/verification-metadata.xml` erzeugen und prüfen lassen; Doku berichtigen. Selbstprobe von `appbau.sh` um „Umgebung des Gradle-Aufrufs enthält kein `APK_`" erweitern | Fable |
| F-R17-04 | **hoch** | `auslieferung.yml` `workflow_dispatch` (Probelauf, App-Probelauf); `ausliefern-lauf.yml` `environment: ${{ inputs.umgebung }}`; Rahmenplan 6.1 („Umgebung `staging` beschränken" offen; für `produktion` nennt kein Dokument eine Zweigbeschränkung) | Ein Probelauf fordert die Umgebung `produktion` **von jedem Zweig** an, mit der Workflow-Datei dieses Zweigs. Alle übrigen Riegel (Tag = Fassung, grüne Läufe, Abstammung, Backup-Tor, Trockenlauf) stehen in dieser Datei, die der Zweig mitbringt. Zwischen einem Konto mit Push-Recht (C2) und den Produktiv-Geheimnissen (FTP, `JOBS_TOKEN`, App-Schlüssel) steht **ein Klick** der Betreiberin auf einen Lauf, dessen Inhalt sie nicht liest | Deployment-Branch-Regel für `produktion`: nur Tags `web-v*`, `android-v*`, `uhr-v*` (und für Probeläufe `main`); dasselbe für `staging` (6.1). Zuarbeit der Betreiberin, kein Code. Die Doku nennt die Regel als Voraussetzung der Freigabe | Fable |
| F-R17-05 | **hoch** | Rahmenplan 6.1 (seit 14.09.2026: Server-Anteil anlegen, Schlüsselblatt drucken, Wiederanlaufpaket ergänzen; seit 25.09.2026: Komplett-Stände öffnen); 6.3 (Probe-Wiederherstellung) | Die vier Pflichtstücke des Wiederanlaufs (`CLAUDE.md` 4: „Pflicht, nicht Empfehlung") sind laut Steuerung auf Produktiv nicht erledigt. Solange der Anteil nicht angelegt ist, gilt die S10-Zusage auf Produktiv nicht; solange kein Blatt gedruckt ist, ist der Verlust von `config.php` ein Passwort-Reset für alle — und ohne geöffneten Stand ist kein Rückfallstand belegt. Das Modell kann nur sagen, was die Anlage **könnte**, nicht, was sie tut | Die vier Zeilen in 6.1 abarbeiten, mit Datum; danach einen Komplett-Stand auf einem Wegwerf-Webspace einspielen (Prüfliste S2). Kein Code | Fable (6.1 gelesen; den Zustand von Produktiv kann niemand hier messen) |
| F-R17-06 | mittel | `pw_handling.php` (Reset-Zweig: nur `key_check == pat_key_check`); `admin_user.php` (`konto` Adresswechsel ohne Bestätigung, `pw_reset`, `totp_zuruecksetzen`); `Technik.md` 3 („Eine Admin-Passwortvergabe existiert bewusst nicht"); Review 2 (I) | Kette für ein `user`-Konto: Paket an sich freigeben (F-R17-02) → `pat_key_check`; Adresse auf eine eigene ändern; Setz-Link; auf `pw_handling.php` neues Token, neues Salz und eine beliebige formgerechte Hülle mit bekanntem `key_check` senden — der Server nimmt sie an, der Wiederherstellungsschlüssel war nie im Spiel; 2FA zurücksetzen. Ergebnis: Anmeldung, Klartext und Geräte des Kontos übernommen; `pat_blob` bleibt zu, `pat_wrap_pw` zerstört (Opfer braucht den Zettel). Spuren: Protokoll, Mail an die alte Adresse. 4.99q benennt die Schwäche von `pat_key_check` richtig, Abschnitt 3 und die Review nicht | Mit F-R17-02 entfällt der erste Schritt, nicht die Kette: Adresswechsel durch Admin mit Bestätigung der **alten** Adresse (oder Karenz), Setz-Link nur an bestätigte Adressen, frischer Code für Adresswechsel und Setz-Link. Den Satz in `Technik.md` 3 auf „nicht im Browser" eingrenzen | Fable |
| F-R17-07 | mittel | `admin_user.php` Aktionen `konto`, `pw_reset`, `konto_status`, `user_delete`; Matrix 4.99p (`{ziel}` = `user`) | Ein Admin kann ein **zweites BetreiberIn-Konto** sperren, löschen oder umadressieren; Riegel gibt es nur für Rollenwechsel und die letzte BetreiberIn. Ob das gewollt ist, steht nirgends | Entscheiden (Q in R17-03): Admin darf BetreiberIn-Konten nicht anfassen — dann Riegel in `auth_guard`/`admin_user.php` und Rollenprobe. Oder gewollt — dann in 4.99p sagen | Leser |
| F-R17-08 | mittel | `admin_komplettsicherung.php` (`herunterladen`, `art=klar`); `db.php` `ZF_FRISCH_HANDLUNGEN`; `Technik.md` 4.97d („geht an die AdministratorIn, die … jede Zeile sehen kann") | Die BetreiberIn lädt den **entsiegelten** SQL-Dump (Hashes, Hüllen, `app_state`-Geheimnisse, alle Klartextspalten aller Konten, offene Mailrümpfe) **ohne frischen Code** — eine übernommene Sitzung oder ein gemerkter Rechner genügt für einen Lage-A-Abzug, während das Schlüsselblatt den frischen Code verlangt. 4.97d nennt die falsche Rolle (seit Nr. 286 BetreiberIn) und eine Begründung, die in der Anwendung nicht gilt | Download „klar" in `ZF_FRISCH_HANDLUNGEN`; 4.97d berichtigen | Leser |
| F-R17-09 | mittel | `geocoder_lib.php` (Vorgabe an), `assets/geocoder.js` (`?q=`, `/reverse?lat=&lon=`), `map_layers.js`; `CLAUDE.md` 4 („Keine fremde Quelle zur Laufzeit"; „Klartext dieser Felder geht nie …"); `Technik.md` 4.98 | Getippte Einsatzadresse und gewählte Koordinate der **geschützten** Felder `loc.*`/`start.*` gehen im Klartext an `photon.komoot.io`; der Kartenausschnitt um den Einsatzort an vier Kacheldienste. Das Handbuch (12.5) sagt es, die beiden Dokumente, die die Zusage formulieren, nicht; „keine fremde Quelle" meint Skripte, nicht Verbindungen | In `CLAUDE.md` 4 und `Technik.md` 4.98 einen Satz: welche geschützten Angaben an welche Dritten gehen und wo der Schalter steht. Das Modell führt es als Lage G | Leser (Handbuch 12.5 bestätigt) |
| F-R17-10 | mittel | `crypto.js` (CK nie neu); `einstellungen.php` Passwortwechsel; `api/schluessel_erneuern.php`; `Technik.md` 4.99q („heute wechselt er nie") | Der Inhaltsschlüssel wird nie rotiert: Wer ihn einmal hatte (XSS, Tab, Sitzung), liest mit jedem späteren Abzug alle alten **und neuen** Blöcke; ein Passwortwechsel heilt nichts. Dazu ersetzt, wer Passwort und Sitzung hat, `pat_wrap_rc` über `api/schluessel_erneuern.php` durch eine eigene Hülle — nur Protokoll (für `user` unsichtbar), **keine Mail**, anders als beim Rückweg | Als Restrisiko ins Modell (5); Mail bei `schluessel_erneuern`; eine Umschlüsselung des Bestands ist S11-Material (dort wird ohnehin neu verschlüsselt) | Leser |
| F-R17-11 | mittel | `watch/source/Uploader.mc` `_serverUrl()` | `if (u.find("http") != 0) { u = "https://" + u; }` — ein eingetragenes `http://…` bleibt `http://`. `JSON-Vertrag.md` nennt `https://`; Android erzwingt es doppelt (Nr. 142). Mit `http://` gingen `X-Api-Key`, Spur, Phasen und Reanimation ab dem Telefon unverschlüsselt | `http://` auf `https://` heben oder abweisen; eine Zeile | Fable |
| F-R17-12 | mittel | `watch/resources/settings/properties.xml` (`serverUrl`, `deviceId`, `apiKey`); `Uploader.mc` `credentials()`; `Communications.makeWebRequest`; `Technik.md` 4.99b, 4.99a2; `android/LIESMICH.md` („Warum kein Pinning") | Drei Dinge, die kein Modell nennt: (1) Die Serveradresse der Garmin ist in der Garmin-Connect-App änderbar — wer dorthin kommt, lenkt Schlüssel und alle ungesendeten Daten an einen fremden Server; (2) der Alt-Weg legt den API-Schlüssel in die App-Einstellungen von Garmin Connect; (3) **jede** Garmin-Anfrage mit Schlüssel und Klartext-Rumpf wird von der Begleit-App des Telefons ausgeführt, die TLS-Prüfung liegt außerhalb der App | Als Lage M (Transport über die Begleit-App) ins Modell; Alt-Weg abschaffen (die Kopplung füllt ihn seit Uhr 3.0.0 nicht mehr, F-R17-29); Serveradresse nur noch in der Kopplung setzen oder gegen die gekoppelte Anlage prüfen | Leser |
| F-R17-13 | mittel | `Vorbereitung-Sicherheitspaket.md` SP-9 Punkt 6 („ein Verlust kostet das Trennen, nicht mehr"); `Review-Krypto-Sicherheit.md` 2 Lage H („Lokal: Spuren und Zeiten"); `Model.mc`, `Track.mc`, `puffer.db` | SP-9 verschlüsselt **vor dem Upload**; der Puffer auf dem Gerät (Spur-Chunks, Phasen, auf der Garmin auch der **Reanimationsverlauf**) bleibt Klartext, bis das Paket bestätigt ist. SP-9 schützt Server und Datenbank, nicht das verlorene Gerät; für Lage H müsste beim **Schreiben** verschlüsselt werden, was `Track.readPoints()` (Polylinie, Teil-Chunk) und `Puffer.punktNaheZeit()` (Phasenkoordinate per SQL) umbaut. Ferner: Der öffentliche Schlüssel nur in der `bestaetigen`-Antwort ginge verloren — beide Geräte schließen die Kopplung auch über `status` = `gekoppelt` ab | Entwurfsfrage für S11 (Modell 6): Zweck „Server" oder „Server **und** Gerät"? Lage H im Modell um den Reanimationsverlauf ergänzen. Schlüsselweg auch in `status`/`gekoppelt` und `ingest`-Antwort | Leser |
| F-R17-14 | mittel | Zweig `produktion` (`GET …/rules/branches/produktion` = `[]`, 11.10.2026); `.claude/settings.json`; `CLAUDE.md` 3 („wird nicht von Hand bewegt"); Rahmenplan 6.1 (Schutz offen) | Der Zeiger, gegen den die Wache vergleicht, hat **keine** Regel; die Deny-Liste sperrt nur Merge und Auto-Merge — `push_files`, `create_branch`, `run_workflow`, `cancel_workflow_run`, `delete_workflow_run_logs` bleiben, alle unter der Identität der Betreiberin. Wer den Zeiger auf einen manipulierten Stand schiebt, macht die Wache zur Falschmeldung | Ruleset für `produktion`: nur die App der Kette darf schieben (oder: Bypass nur `github-actions`); `push_files` und `actions_run_trigger` in die Deny-Liste. Zuarbeit | Fable (API) |
| F-R17-15 | mittel | `tools/integritaetswache/LIESMICH.md`; `Technik.md` 7 (Runbook „Wenn sie rot wird"); `wache.py` (Alarmtext) | Die Anleitung beschreibt eine andere Wache: „jede Datei unter `server/`", „FTPS-Zugang", Ausnahmeliste mit `doku/`. Tatsächlich HTTPS ohne Zugangsdaten, nur `assets/` und `login.php`. Runbook und Alarmtext verweisen auf einen Abschnitt „Wenn sie rot wird", den es nicht gibt — im Ernstfall liest die Betreiberin eine falsche Anleitung | LIESMICH neu schreiben, den Abschnitt anlegen; Alarmweg klären (an wen geht ein roter geplanter Lauf?) | Fable |
| F-R17-16 | mittel | `tools/kettenaufrufe/LIESMICH.md` („jede `uses:`-Zeile") gegen `pruefen.py` (kein „uses"); `CLAUDE.md` 3 („acht Pfade, die Zahl ist der Prüfwert", „keine fremde Zeile ohne SHA") | Weder das SHA-Pinning noch die Ausnahmeliste misst ein Werkzeug — nur der Handbefehl aus `CLAUDE.md`. Zwei Prüfwerte ohne Prüfmittel, und die Anleitung des einen Werkzeugs behauptet das Gegenteil | Riegel in Stufe 1: `uses:` ohne 40-Hex-SHA = rot; Ausnahmeliste gegen `.gitignore` und eine Sollliste = rot. LIESMICH berichtigen | Leser |
| F-R17-17 | mittel | `auslieferung.yml` Jobs `android`, `uhr`; `CLAUDE.md` 3 („Der Produktionslauf verlangt einen grünen Stufe-1-Lauf") | Für App-Tags gibt es weder Stufe-1-Nachweis noch Abstammungsprüfung — ein `android-v*` von einem nie geprüften Commit braucht nur Tag = Fassung und die Freigabe. `CLAUDE.md` 3 liest sich, als gälte das Tor für alle drei Tags | Tore `freigabe.py`/`baumsuche.py` auch vor die App-Jobs; bis dahin den Satz in `CLAUDE.md` 3 eingrenzen | Leser |
| F-R17-18 | mittel | Stufe 2 „Punktdateien gesperrt?"; `CLAUDE.md` 3 („Staging belegt kein Plattformverhalten von Produktiv") | Die Sperre der Punktpfade (`.deploy-state-*.json`, `.sitzungen/`, `.well-known`) ist **nur auf Staging** gemessen — bei einem anderen Hoster. Auf Produktiv läuft nichts davon; die 403 dort ist nie belegt | Lesende Probe gegen Produktiv (drei `GET`, Erwartung 403) in die Wache oder in den Produktionslauf nach dem Abgleich | Leser |
| F-R17-19 | mittel | `wiederherstellen.php` (ruft `edbak_ablage_bereit()` nicht); `admin_komplettsicherung.php` (Dateiname `…-YYYY-MM-DDTHH-MM-SS.sql.gz`); Runbook 7 Schritt 4 und 8 | Auf frischem Webspace fehlt `sicherungen/.htaccess` (entsteht erst zur Laufzeit); eine nach `eingang/` gelegte **Klartext**-`.sql.gz` (vom Runbook erlaubt) ist per HTTP abrufbar, ihr Name sekundengenau ratbar; Schritt 8 räumt laut Text nur Dump und Nachweis, nicht die Quelldatei | `wiederherstellen.php` ruft `edbak_ablage_bereit()` zuerst; Runbook: nur `.edk` in `eingang/`, Quelldatei im Aufräumschritt | Fable |
| F-R17-20 | mittel | Job `stufe2` (`pip install cffi cryptography`, `npm install playwright@1.56`); `tools/uhr-pruefstand/pruefstand.sh` `sdk_holen` (SDK ohne Prüfsumme); `android/gradle/` (keine `verification-metadata.xml`); E-PK-54 | Die 14 Aktionen sind gepinnt, die Pakete in `run:` nicht — im selben Job wie Staging-Passwort, `STAGING_TOTP`, `JOBS_TOKEN`; das SDK im Job mit dem Garmin-Schlüssel (Schlüssel erst im Folgeschritt). E-PK-54 begründet „kein Cache", nicht „keine Fassungsbindung" | `pip`/`npm` mit festen Fassungen und Hashes (`--require-hashes`, `package-lock`), SDK-Prüfsumme, Gradle-Verifikation (mit F-R17-03) | Leser |
| F-R17-21 | niedrig | `mail_lib.php` `mail_konto_entfernen()` gegen `konto_lib.php` `konto_loeschen()` | Der Docblock verlangt den Aufruf bei jeder Kontolöschung; es gibt **keinen** Aufrufer. Die Adresse eines gelöschten Kontos bleibt bis 30 Tage in der Warteschlange und in den Komplett-Ständen dieser Zeit | Aufruf in `konto_loeschen()`; Probe | Fable |
| F-R17-22 | niedrig | `jobs.php`, `tor.py adresse_bauen()`, `api/health.php` (`?token=`); `pw_handling.php`, `adresse_bestaetigen.php` (erster Abruf); `mail_lib.php` `mail_einreihen()` (Rumpf) | `JOBS_TOKEN` und Health-Token reisen als GET-Parameter in jedes Hoster-Zugriffsprotokoll; der erste Abruf jedes Setz- und Bestätigungslinks ebenso (Einladung 24 h gültig); offene Setz-Links stehen im Klartext in `mail_warteschlange.text` und damit in jedem Komplett-Stand. `Technik.md` 4.98 „Klartext-Reste" nennt den Rumpf nicht; `pw_handling.php` nennt das Log richtig | Jobs- und Health-Token als Kopfzeile statt Parameter; Rumpf der Warteschlange nach Zustellung leeren (geschieht) und in 4.98 nennen; Hoster-Logdauer in die Betriebsakte | Leser |
| F-R17-23 | niedrig | `kopfzeilen_lib.php` `kopf_csp_scharf()` (Vorgabe aus), `KOPF_HSTS_VORGABE = 1`; `Technik.md` 5c | CSP und `frame-ancestors` wirken erst nach dem Umschalten durch die BetreiberIn; HSTS 1 Tag. Dokumentiert — aber ob Produktiv umgeschaltet ist, steht nicht im Repositorium; Lage D ist bis dahin ungebremst | Stand von `csp_scharf` und `hsts_tage` auf Produktiv in 6.1 abfragen; Vorgabe nach der Probezeit auf scharf | Leser |
| F-R17-24 | niedrig | `Technik.md` 4.99 Zeile „Schlüsselbindung"; `keyguard.js` `verwerfeInhalt()`; `crypto.js` `merkeAbleitungen()` | Nach der Frist läuft nur `pck` ab; `edk` bleibt im `sessionStorage` bis Navigation oder Tabende; `edkvor` trägt nach jeder Anmeldung das Anmelde-Token (passwortgleich gegenüber dem Server) bis zur ersten Seite mit CK-Bedarf | Zeile in 4.99 berichtigen; `edkvor` nach dem Abholen sofort räumen (prüfen, ob es geschieht); Nr. 146 (`CryptoKey`) | Leser |
| F-R17-25 | niedrig | `CLAUDE.md` 4 Klartextliste („vollständig zu zitieren"); `mission_fields.php` (`bw_info` „Namen / Infos", `other_ema`) | Die Liste nennt `bw_info`, `other_ema`, `other_resources`, Schockraum, Winde, Stammdaten-Koordinaten und die Altbestand-Spalte `missions.notes` nicht; `Technik.md` 4.98 deckt die Klasse ab. `bw_info` ist der naheliegende Ort für einen Bedienfehler mit Namen (Kleinzeile „Klartext" steht) | Liste in `CLAUDE.md` 4 auf 4.98 verweisen lassen statt sie zu wiederholen; das Modell führt die vollständige Liste (I-07) | Leser |
| F-R17-26 | niedrig | Kopf `serverkrypto_lib.php`; `config.example.php`; Review 2 (B) | Der Serverschlüssel versiegelt auch **TOTP-Geheimnisse** und **Protokollarchive** — für Lage B heißt das: `config.php` + Datenbank öffnen den zweiten Faktor aller Konten. Steht in 4.99q, nicht an den Kopfstellen | Kopf und Beispielkonfiguration ergänzen; im Modell (4) als Wirkung von B | Leser |
| F-R17-27 | niedrig | `docs/Lizenzen.md` 6a („an genau einer Stelle: `WearNachrichtenweg.kt`"); `HandyHorcher.kt`, `UhrHorcher.kt` (`WearableListenerService`, `MessageEvent`) | Die Play-Dienste-Bibliothek steckt in **drei** Dateien; die Schnittstelle deckt das Senden, der Empfang kann prinzipbedingt nicht dahinter. `CLAUDE.md` 4 „auf eine Datei begrenzt" ist zu eng | Satz in `Lizenzen.md` 6a und `CLAUDE.md` 4 auf „Senden hinter der Schnittstelle, Empfang in zwei Horchern" berichtigen | Leser |
| F-R17-28 | niedrig | `watch/source/Uploader.mc` `istGeparkt()`, `verwerfen()`; `Model.mc` | Geparkte Pakete (400) und nach `abgemeldet` **alle** Pakete bleiben auf der Garmin ohne Frist; Android räumt nach 30 Tagen (AN-2). Ortsdaten liegen länger auf der Uhr, als irgendwo steht | Frist wie Android, oder im Handbuch sagen, dass Trennen im Web nichts auf der Uhr löscht | Leser |
| F-R17-29 | niedrig | Sammelbefund veralteter Stellen: `Technik.md` 4.99b Nr. 2 („bis Paket C"), 5a (Signaturschlüssel „außerhalb"), 6.1 („zwölf" `uses:`; „keine App-Tags"), 6.4 Schritt 2 („auf diesem Commit"), 6.5 (sieben Pfade, `.sitzungen/` „noch nicht", „beide Schritte", `STAGING_TOTP` fehlt); `.gitignore`-Kommentar; `Pruefablauf.md` 2.3 („15 Schritte"); `JSON-Vertrag.md` 1/1a (Klartext „nur auf der Uhr", „vom Admin vergeben", Platzhalter „‹Fassung›"); `watch/resources/settings/settings.xml` („wird beim Koppeln gesetzt"); `Vorbereitung-Play-Console.md` 4.2 (`WAKE_LOCK`); `docs/rechtstexte/AVV.md` Anlage III („Schlüssel in zwei Teilen"); SP-4 gegen Ruleset (0 Reviews, Bypass) | Je Stelle sagt die Doku einen Stand, den der Code nicht mehr hat. Keine davon ist für sich ein Sicherheitsproblem; zusammen lesen sie sich als Anlage, die anders ist als die beschriebene | Ein Aufräumpaket in 12b (Kommentardurchgang, R13); AVV-Satz als Rechtstext vor der nächsten Fassung berichtigen | Leser |
| F-R17-30 | Hinweis | `Review-Krypto-Sicherheit.md` 0, 2, 3, 4 (Stand Web 14.2.2) | Rundenzahl, Datenschlüssel mit Anteil, CSP, Zweitfaktor, Rückweg, Notzugang, K-4/K-7/K-8/K-9/K-11/K-15, Lage H (72 h), Lage I (Komplett nur BetreiberIn, aber Freigabe) — die Review ist an vielen Stellen überholt, ihre Zeilennummern stimmen nicht mehr. Das Modell ersetzt ihren Abschnitt 2 | Die Review bleibt als Protokoll (Datum im Kopf); das Modell verweist auf sie als Vorläufer, nicht als Quelle | Leser |
| F-R17-31 | Hinweis | Sammelhinweis: `pw_handling.php` liefert den Anteil an jeden gültigen Link (ohne Passwort; in SP-3 benannt); `ui_krypto_bootstrap()` trägt Hülle, Salz, Runden, Anteil in jede angemeldete Seite (gespeicherte Seite = Offline-Raten); `auth_salt.php`: Pseudosalz je Adresse stabil, echtes Salz ändert sich beim Reset; Zustandsdatei der Auslieferung im Webroot (Repositorium öffentlich); Klartext-Dump im Bauordner des Komplettbackups über viele Anfragen (dokumentiert); Wear-Uhr ohne Absenderprüfung (Signaturgleichheit vorausgesetzt); Handy wertet jedes 400 als fehlerhaft, 403 als „später"; Kommentar `netzwerk_pruefstand.xml` „zwei Adressen" bei dreien; Metadaten-Abfluss (Takt und Größe des Uploads verraten Dienst und Phasen auch bei TLS) | Kein Befund gegen Code oder Doku, aber Wissen, das ins Modell gehört (3, 5) | ins Modell; nichts weiter | Leser |

### 2.3a Befunde der Stücke 2 bis 12 (Schritt 12b)

Zählung fortlaufend ab **F-R17-32**. Jeder Befund ist von Fable am
Quelltext gelesen (E-R17-14); die Spalte „geprüft" entfällt deshalb, an
ihre Stelle tritt das **Stück**. Schweregrade und Wege wie in 2.3:
**kritisch** (ausnutzbar, Datenverlust, Bruch einer Zusage) → Sofortpaket;
**hoch** → Pflichtpaket vor v1.0; **mittel**, **niedrig**, **Hinweis** →
die Freigaberunde entscheidet; eine Kategorie „nach v1.0" gibt es nicht
(E-PV-5 (7)). Karteileichen, ungenutzter Code und die Fundstellen des
Kommentardurchgangs stehen **nicht** hier, sondern je Stück in 7.3 — sie
sind Aufräumarbeit, kein Befund, solange sie nichts kaputtmachen. Ein
Befund, der einen offenen Backlog-Punkt bestätigt oder einen der 31 aus 2.3
verschärft, nennt die Nummer und bekommt keine neue.

| Nr. | Grad | Stück | Fundstelle | Gesehen → Wirkung | Empfehlung |
|---|---|---|---|---|---|
| F-R17-32 | **mittel** | 2 | `api/kdf_upgrade.php`, Block „Nachweis" (`password_verify($altToken, …)` → 403 `nachweis`); zum Vergleich `api/schluessel_erneuern.php` und `api/kontopaar.php` (`rate_erlaubt('login', $email)` davor, `rate_misserfolg` beim Fehlschlag, Kommentar „ein Orakel") | Der Endpunkt tauscht Salz, Rundenzahl und Hüllen und verlangt dafür das alte Anmelde-Token. Er prüft es mit `password_verify()`, aber **ohne** `rate_erlaubt()`/`rate_misserfolg()` — die zwei Geschwister, die dasselbe Token prüfen, zählen in den Topf `login`, dieser nicht. Wer eine gültige Sitzung hat (Lage D, F; Modell 2), rät das Passwort hier online, ohne Sperre, gebremst nur von bcrypt (Größenordnung Sekundenbruchteile je Versuch); das Anmeldeformular und die Geschwister sperren nach wenigen Fehlversuchen. Ein gefundenes Passwort öffnet mit dem Anteil der Sitzung alle `pat_blob` (F-R17-01). Zusage „Ratenschutz, wo geraten werden kann" (7.2) gebrochen. **Verschärft in Stück 3 (R17-07):** dieselbe Lücke dreimal in `einstellungen.php` — Adresswechsel (`old_token`), Kontolöschung (`pw_loesch`), Passwortwechsel (`old_token`), je `password_verify()` gegen den Hash ohne einen `rate_*`-Aufruf (`grep` über die Datei: vier `password_verify`, die einzigen `rate_*` gehören zum Kopplungscode). Mit einer erbeuteten Sitzung rät man das Passwort also an vier Stellen ohne Sperre; die Seite liest Stück 5 ganz | `rate_erlaubt('login', $email)` vor der Prüfung (429 wie die Geschwister), `rate_misserfolg('login', $email)` vor dem 403 — an allen vier Stellen, besser als EINE Funktion `passwort_nachweis($userId, $token)` in `auth_guard.php`, die Topf und Vergleich zusammen trägt (dann gibt es keine fünfte Abschrift); ein Paket mit F-R17-01 (b). Die Zweitfaktorprobe um den Fall erweitern (ein dritter Fehlversuch → 429) |
| F-R17-33 | niedrig | 2 | `wiederherstellen.php` `wh_auspacken()`, Block „IST DIE DATEI VOLLSTÄNDIG?" (`$eigen = str_contains(…, 'Komplett-Backup der Installation') \|\| str_contains(…, 'Komplett-Backup der Installation')`); Erzeuger `komp_dump_schreiben()` in `komplett_lib.php`; Rahmenplan-Archiv-2 Schritt 4 (S7) | Der Kommentar sagt: zwei Schreibweisen, die alte („Komplettsicherung der Installation", bis Web 12.9.2) muss stehen bleiben, sonst gilt ein Dump von vor S7 als **fremd** und die Endmarke wird nicht verlangt — „der gefährlichste Fehlschluss, den eine Umbenennung anrichten kann". Genau das ist geschehen: Die Umstellung des Backup-Begriffs (S7, 03.09.2026) hat **beide** Zeichenketten ersetzt, die Zeilen sind wortgleich (PHPStan: `function.impossibleType`, „will always evaluate to false"). Wirkung: Ein Dump, der vor dem 03.09.2026 erzeugt und beim Erzeugen abgebrochen wurde, wird **ohne** Endmarkenprüfung eingespielt — halb, und die Seite zeigt dazu „fremder Dump". Betroffen sind nur Dumps aus der Zeit vor S7; ob noch einer liegt, weiß die Betreiberin (P-R17-06). Die Prüfliste S7 (Punkt 4, „Komplett-Backup aus der Zeit vor diesem Deploy einspielen") hat das nicht gesehen, weil ein **vollständiger** alter Dump auch als fremder durchläuft | Die zweite Zeichenkette auf `Komplettsicherung der Installation` setzen (eine Zeile) — oder, wenn kein Dump von vor S7 mehr existiert, am v1.0-Schnitt (R60) auf eine Zeile kürzen und den Kommentar mitnehmen. In beiden Fällen: eine Probe, die einen Dump mit altem Kopf und ohne Endmarke abweisen sieht (`tools/proben/komplett/`), und das Register `bestand` lernt die Regel „zwei gleichlautende Zweige in einem `\|\|`" nicht — das sieht PHPStan, nicht die Kette (7.4) |
| F-R17-34 | niedrig | 2 | `backup_lib.php` `edbak_restore()`, Block „Stammdaten" (`$name = (string)$b['name']` → `INSERT IGNORE INTO bases`); dagegen `pruef_rettungsmittel()` für `vehicles`, `pruef_text()` für Gerätekennungen, `pruef_kalendertag()` für Diensttage im selben Lauf | Standortname und Koordinaten eines Backups gehen ohne `pruef_text()`/`pruef_breite()`/`pruef_laenge()` in die Datenbank — kein Leerraumschnitt, keine Längengrenze, keine Zahlprüfung; der Rest desselben Laufs geht über die Prüfschicht. Zusage „alle Schreibwege, ohne Ausnahme" (`CLAUDE.md` 4) hat hier eine. Wirkung: ein Name über der Spaltenlänge bricht im **strikten** SQL-Modus den ganzen Lauf mit 500 ab (nichts geschrieben, Meldung ohne Feld), im laxen wird er still gekappt und passt danach nicht mehr zum `base_ref` der Rettungsmittel — die bleiben ohne Standort, gezählt als „übersprungen". Kein Fremdzugriff (eigenes Konto, gebundene Parameter), kein Verlust | `pruef_text($b['name'], <Spaltenlänge>, 'bases.name', $pruef)` und `pruef_breite`/`pruef_laenge` für die Koordinaten, Fehler zählen wie bei den Rettungsmitteln; vier Zeilen. Dieselbe Lesung für `bw_units` und `transport_dests`, falls sie dort ebenso stehen (nicht nachgelesen — der Block ist lang) |
| F-R17-35 | Hinweis | 2 | `adminbackup_lib.php` `edbak_teil_oeffnen()` („Ein Eintrag ohne `edsk1:` ist Fassung 2 und kommt unverändert zurück"); Aufrufer `edbak_paket_teil_lesen()`, `edbak_paket_kopf_lesen()`, `edbak_paket_einspielen()` | Das Präfix entscheidet je Teil, nicht die Fassung des Pakets: In einem Fassung-3-Paket wird ein Teil, dem jemand das Siegel abgenommen und den Klartext hingelegt hat, ohne Prüfung angenommen — das Siegel bindet Server, Konto, Paket und Teilnamen (AAD), der Rückfall auf Fassung 2 hebt die Bindung für diesen Teil auf. Wer das kann, hat Dateizugriff (Lage B) und damit ohnehin `config.php`; deshalb Hinweis, kein Befund. Bleibt stehen, bis es keine Fassung-2-Pakete mehr gibt | Beim Paketschnitt: das Manifest trägt die Fassung; ab Fassung 3 den Rückfall abschalten (`edbak_teil_oeffnen()` bekommt die Fassung mit, ein unversiegelter Teil ist dann `null`). Zwei Zeilen und eine Probe |
| F-R17-36 | **mittel** | 3 | `ratelimit_lib.php` `rate_merkmale()` (hängt immer `ip:` an), `rate_misserfolg()` und `rate_erlaubt()` (laufen über alle Merkmale eines Topfes mit EINEM `max`), `rate_erfolg()`; Aufrufer `login.php` (`rate_misserfolg('login', $email)`), `api/schluesselblatt_pruefen.php` (`rate_erlaubt('blatt', $email)`), `reset_request.php`; Kommentar in `RATE_GRENZEN` zu `login_ip` („`login` zählt dann nur noch das Konto") gegen den Kommentar im Fehlzweig von `login.php` („zählt seit jeher BEIDE Merkmale … das bleibt so"); `docs/Technik.md` 5e.2 („10 Fehlversuche je Konto, 50 je Anschluss"); `tools/proben/raten/probe.php` (misst `login_ip` nur isoliert über `klopfen('login_ip', …)`) | Ein Topf hat EINE Schwelle, aber `rate_merkmale($konto)` liefert zwei Merkmale — Adresse und Konto —, und `rate_misserfolg()` sperrt jedes davon bei `versuche >= max`. Im Topf `login` (max 10) ist damit nach zehn Fehlversuchen **von einem Anschluss** — gleich an welchen Konten — die Adresszeile gesperrt, `rate_erlaubt('login', $email)` findet sie und meldet „von diesem Anschluss"; mit Leiter 15, 20, 30, 60 Minuten. Der Topf `login_ip` (50, E-P5a-04, R37: Klinik-NAT) greift nie, weil 10 vor 50 kommt, und die Einstellung `ratenschutz_login_ip_max` ist unterhalb von `ratenschutz_login_max` wirkungslos. Dasselbe beim Schlüsselblatt: `blatt` (max 3) sperrt nach drei Fehlversuchen die Adresse — „je Konto und nicht je Adresse, damit eine gesperrte BetreiberIn die andere nicht mitsperrt" steht daneben und gilt nicht; und bei `reset` (5/h) sind es fünf Reset-Anfragen je Anschluss und Stunde. Wirkung: Verfügbarkeit (Lage A, Modell 2 — ein Fremder sperrt mit zehn falschen Passwörtern an erfundenen Adressen jeden Anschluss, hinter dem eine Klinik sitzt, für bis zu einer Stunde; die Sammelmail meldet es als Angriff), und die Doku verspricht eine Zahl, die der Code nicht hält. Die Probe `raten` hat es nicht gesehen, weil sie den Topf `login_ip` allein klopft, nie den Pfad `rate_misserfolg('login', $email)` | In `login.php` den Konto-Topf mit ausdrücklicher Merkmalsliste rufen — `rate_erlaubt('login', null, [rate_merkmal_kennung($email)])`, ebenso `rate_misserfolg` und `rate_erfolg` (das Muster von `ingest`/`ingest_ip` und `totp`); `login_ip` trägt dann allein die Adresse. Für `blatt` dasselbe; für `reset` entscheiden (die Adresszählung ist dort vielleicht gewollt — dann in den Kommentar). Oder grundsätzlicher: `rate_merkmale()` abschaffen und jeden Topf mit seiner Merkmalsliste rufen — dann kann der Fehler nicht wiederkommen. Probe `raten`: einen Fall „zehn Fehlversuche von einer Adresse an zehn Konten → Adresse NICHT gesperrt". `Technik.md` 5e.2 bleibt dann wahr; bis dahin ist sie falsch (Stück 11). Zwölf Zeilen und ein Probenfall |
| F-R17-37 | niedrig | 3 | `zweitfaktor_notweg.php` (`$merkmale = [rate_merkmal_kennung($email)]`, `rate_erlaubt('notweg', null, $merkmale)`, `rate_misserfolg('notweg', …)`; `notweg_passwort_passt()` mit `password_verify()` oder Blindvergleich); zum Vergleich `login.php` (`login_ip`, `rate_global_misserfolg()`), `reset_request.php` (Adresse im Merkmal), `pair.php` (Topf je IP) | Der Notzugang zählt **nur die eingetippte Adresse** — fünf je Stunde, mit Leiter. Wer die Adresse je Anfrage wechselt (erfundene reichen, der Blindvergleich rechnet trotzdem), bekommt je Anfrage eine bcrypt-Rechnung von rund einer Viertelsekunde CPU, ohne Anschlussgrenze und ohne den globalen Zähler, den die Anmeldung führt; Sitzung und Formular-Token holt sich ein Skript mit einem GET. Kein Zugang, kein Geheimnis — aber Rechenlast von einer Adresse auf einem Webspace mit wenigen Arbeitern (Lage A, Verfügbarkeit). Jeder andere unangemeldete Weg mit Passwort- oder Schlüsselvergleich hat eine Adressbremse (Anmeldung, Reset, Kopplung) oder eine Rechenaufgabe (Registrierung) | Zweites Merkmal `rate_merkmal_ip()` in den Topf (eine Liste wie bei `login_ip`, Schwelle etwa 20/h) und `rate_global_misserfolg()` im Fehlzweig, damit die Verlangsamung auch hier greift — drei Zeilen; ein Fall in der Probe `raten` |
| F-R17-38 | niedrig | 4 | `tageszuordnung_lib.php` `tz_tag_datum_aendern()`, der Rahmen `db_transaktion($pdo, function (PDO $pdo) use ($dayId, $delta, $mIds, $neuTag, $platzhalter, $rIds, $userId, $werte)`; die beiden Namen entstehen erst IM Rahmen, in der Zerlegung `foreach (sql_in_bloecke_sql($mIds) as [$platzhalter, $werte])`; `systemmeldung_lib.php` `system_fehler_behandeln()` (wirft nicht, trägt ein) | Das `use` fängt zwei Variablen ein, die an der Stelle nicht existieren. PHP 8 meldet das als Warnung „Undefined variable $platzhalter" und „… $werte" — bei JEDER Umdatierung eines Diensttags zwei Zeilen im Reiter System (der Behandler wirft nicht; nachgemessen mit `php -r`: Warnung, Lauf geht weiter). Die Funktion arbeitet richtig, weil die Zerlegung die Namen im Rahmen neu belegt. Seit Schritt 15 AP10 (22.09.2026); PHPStan sieht es als vier Meldungen (`closure.unusedUse` ×2, `variable.undefined` ×2). Wirkung: Lärm im Reiter System, der eine echte Warnung verdeckt | Die beiden Namen aus dem `use` streichen — zwei Wörter; die Meldungen von PHPStan verschwinden mit |
| F-R17-39 | **mittel** | 4 | `einsatz.php`, Block „DER SPURZUSTAND, EINMAL" (`$spurStand = spur_stand(…)`, `$spurPunkte = $spurStand['stufe'] === 1 ? spur_zahlen(…) : $spurStand['n_gespeichert'] + max(0, $spurStand['naechste_seq'] - $spurStand['n_original'])`, `$hatSpur = $spurPunkte > 0`; daran der Menüeintrag „GPS-Daten als GPX" und die Konstante `SPUR` für die Plakette); `spur_lib.php` `spur_stand()` (liefert `stufe`, `n_original`, `naechste_seq` — kein `n_gespeichert`, seit S11-12), `spur2_stand()`; `api/wartungslauf.php` und `assets/wartungslauf.js` (packen und dünnen aus, angestoßen von `index.php`); zum Vergleich `tag_spuren.php` (`array_sum(array_column($stuecke, 'punkte'))`) | `n_gespeichert` war das Feld des SPUR1-Blobs und ist mit Web 23.0.0 (S11-12, `0a6b926`) aus `spur_stand()` verschwunden; die Zeile in `einsatz.php` liest es weiter. Solange eine Spur nur Gerätestücke hat (Stufe 1), greift der erste Zweig, und alles stimmt — die GPX-Probe misst genau diesen Fall. Sobald der Wartungslauf die Spur gepackt (Stufe 2) oder ausgedünnt (Stufe 3) hat — und den stößt `index.php` bei jeder Anmeldung an —, greift der zweite: `null + max(0, naechste_seq − n_original)`, und das ist bei einer gepackten Spur ohne Nachzügler **0**. Folge auf jedem Konto, dessen Spuren der Wartungslauf erreicht hat: `$hatSpur` ist falsch, der Menüeintrag „GPS-Daten als GPX" fehlt, die Plakette „GPS-Daten (ausgedünnt)" fehlt, `SPUR.n` ist 0 — und jeder Aufruf der Einsatzseite schreibt „Undefined array key n_gespeichert" in den Reiter System. Die Karte zeichnet die Spur trotzdem (sie kommt aus der API). Kein Datenverlust, der Abruf geht weiter über die Spurenseite des Tages; aber ein Weg, den das Handbuch nennt, ist auf Produktiv seit Web 23.0.0 für jede gepackte Spur fort, und der Reiter System füllt sich. PHPStan: `offsetAccess.notFound` | Den Dreisatz durch `spur_zahlen(db(), 'mission', [$mid])[$mid] ?? 0` für ALLE Stufen ersetzen (die Summe der `punkte` ist seit SPUR2 die Zahl, die die Seite will — dieselbe Rechnung wie `tag_spuren.php`); eine Zeile. Die GPX-Probe um einen Fall erweitern, der die Spur vorher packt (`spur2_wartung_tauschen()` im Prüfstand), damit der zweite Zweig je gemessen wird. P-R17-08 sagt, ob Produktiv schon betroffen ist |
| F-R17-40 | niedrig | 4 | `api/import_commit.php` `import_commit()`, Block „Diensttage": `$imPapierkorb` (alle Daten mit `deleted_at IS NOT NULL`), dann je Tag `if (isset($imPapierkorb[$tag])) { $tageUebersprungen++; continue; }` VOR `$vorhandenQ` (der erste aktive Tag des Datums); Block „Einsätze": `$dayIdByDate[$tag] ?? 0` → `ohne_diensttag`; `check`: `SELECT … FROM days WHERE … deleted_at IS NULL AND day IN (…)` | Ein Kalendertag darf seit E9 mehrere Diensttage tragen, und die Sperre fragt nur: Liegt zu diesem DATUM ein Tag im Papierkorb? Liegt dort einer — etwa der zweite Tag einer Doppelanlage von Garmin und Handy, den jemand gelöscht hat —, überspringt der Import das Datum ganz, obwohl daneben ein aktiver Diensttag steht, den `check` dem Browser eben noch gemeldet hat. Alle Einsätze des Datums zählen dann unter `ohne_diensttag`, der Tag unter `days_skipped_trash`; die Meldung sagt „Papierkorb", die NutzerIn sieht in der Leiste einen aktiven Tag. Bis die Frist den Papierkorb leert (30 Tage), ist das Datum nicht importierbar. Verhaltensfehler, kein Datenverlust (es wird nichts übernommen) | Die Papierkorb-Sperre nur dann, wenn `$vorhandenQ` KEINEN aktiven Tag findet — die Abfrage zuerst, die Sperre in den `$dayId === 0`-Zweig; drei Zeilen. Ein Fall in der Importprobe (ein gelöschter und ein aktiver Tag am selben Datum) |
| F-R17-41 | Hinweis | 4 | `api/import_commit.php` `import_commit()`: die Vorprüfung `if (($m['dup'] ?? '') !== 'overwrite') { … preg_match('/^imp-[0-9a-f]{24}$/', $ref) … }` gegen den Zweig `if (($m['dup'] ?? '') === 'overwrite' && !empty($m['overwrite_id'])) { … } else { … 'client_ref' => (string)$m['client_ref'] … $insE->execute($p); }` | Die Kennungsprüfung läuft für alles außer `overwrite`; der INSERT-Zweig greift aber auch für `overwrite` OHNE `overwrite_id`. Eine Nutzlast mit `dup: 'overwrite'` und ohne Kennung legt so einen Einsatz mit leerer oder beliebiger `client_ref` an — ungeprüft gegen Form und Dublette. Nur das eigene Konto, nur mit einer Nutzlast von Hand (der Browser schickt `overwrite` stets mit Kennung); die Hülle bleibt an die mitgeschickte Kennung gebunden, der Browser öffnet sie damit. Kein Schaden für Dritte, aber zwei Prüfungen, die an der Zweigentscheidung vorbeigehen | Die Prüfung an dieselbe Bedingung hängen wie den Zweig (`dup === 'overwrite' && !empty(overwrite_id)`), oder `overwrite` ohne `overwrite_id` mit 400 abweisen; zwei Zeilen |
| F-R17-42 | Hinweis | 5 | `admin_user.php`: `DEMO_GESPERRT = ['konto', 'sichern', 'einspielen', 'freigeben', 'widerrufen', 'paket_loeschen', 'user_delete']` gegen die Schreibwege `konto_status`, `konto_grenzen`, `totp_zuruecksetzen`; das Markup sagt „Das Demo-Konto lässt sich nicht sperren" und blendet den Knopf aus; `demo_lib.php` kennt `status` nicht | Die Liste deckt drei Handlungen nicht: Ein POST von Hand sperrt das Demo-Konto (`konto_status`), setzt ihm Grenzen oder setzt den — ohnehin gesperrten — Zweitfaktor zurück; nur die Oberfläche hält den Knopf fern. Eine Sperre bleibt, weil der Reset den Status nicht zurücksetzt: Das öffentliche Konto ist zu, bis die Verwaltung es von Hand öffnet. Nur Admin und BetreiberIn, nur von Hand; kein Datenzugang | `konto_status` und `konto_grenzen` in `DEMO_GESPERRT` aufnehmen (eine Zeile), `totp_zuruecksetzen` ebenso — oder der Reset setzt `status` mit zurück. Die Rollenprobe (`tools/proben/rollen/`) prüft das Demo-Konto bisher nicht; ein Fall dort |
| F-R17-43 | niedrig | 5 | `einstellungen.php`, Zweig `$action === 'zf_codes_neu'` → `totp_codes_erneuern($userId)`; davor nur `csrf_check()`; `ZF_FRISCH_HANDLUNGEN` (`db.php`) kennt `totp_ausschalten`, `passkey_anlegen`, `passkey_entfernen`, nicht `zf_codes_neu`; `totp_codes_erneuern()` schreibt `protokoll('totp_codes_erneuert')`, keine Mail | Mit einer erbeuteten Sitzung lassen sich zehn neue Wiederherstellungscodes erzeugen und ablesen, die alten werden ungültig. Die Codes sind ein dauerhafter zweiter Faktor — E-SR-31 verlangt genau deshalb für das Anlegen eines Passkeys einen frischen Code („Wer eine fremde Sitzung erbeutet hat, soll sich damit keinen dauerhaften zweiten Faktor anlegen"); die Codes blieben dabei außen vor. Grenzen: Ohne Passwort öffnen die Codes nichts (Lage G im Modell, Passwort plus Sitzung); die Handlung steht im Protokoll; die rechtmäßige Person merkt es, wenn ihre Codes nicht mehr gelten — aber erst dann, und ohne Mail | `zf_codes_neu` in `ZF_FRISCH_HANDLUNGEN` aufnehmen und vor dem Zweig `zweitfaktor_frisch_verlangen('totp_codes_neu')` rufen — dasselbe Muster wie `zf_ausschalten`, zwei Zeilen; dazu die Mail, die `passkey_entfernt` schon hat (`mail_einreihen()`), damit der Wechsel die Person erreicht. Modell 3 (Zusage Zweitfaktor) um den Satz ergänzen |
| F-R17-44 | niedrig | 5 | `zeitraum.php`: `if ($monat !== '' && !preg_match('/^\d{2}$/', $monat)) { $monat = ''; }`, danach `$MONATSNAMEN[(int)$monat]` und `mktime(0, 0, 0, (int)$monat, 1, (int)$jahr)`; `api/range.php` prüft `^(0[1-9]\|1[0-2])$` | `zeitraum.php?y=2026&m=13` (ebenso `00`, `99`) besteht die Formprüfung: PHP-Warnung „Undefined array key 13" (im Reiter System als Art PHP), Titel „ 2026" ohne Monat, `mktime()` rechnet den Monat ins nächste Jahr; erst `api/range.php` weist ab, die Seite zeigt „Nicht geladen." Nachgerechnet mit `php -r`. Kein Datenzugang; ein Aufruf von Hand oder ein alter Verweis | Dieselbe Prüfung wie `api/range.php` (`^(0[1-9]\|1[0-2])$`), eine Zeile — oder die Weiche auf `index.php` wie beim Jahr |
| F-R17-45 | niedrig | 5 | `betrieb_statistik.php`, Zweig `export=csv`: `fputcsv($out, [$m['name'], $m['hersteller'], $m['art'], …], ';', '"', '')`; `$m['name']` aus `geraet_modell` oder `geraet_teil`, der Selbstauskunft des Geräts beim Koppeln (`pair.php`, `_geraet_kuerzen()` in `geraete_lib.php` entfernt nur Steuerzeichen und kürzt) — der einzige `fputcsv`-Schreiber im Bestand | Ein Modellname, der mit `=`, `+`, `-` oder `@` beginnt, wird in Excel und LibreOffice zur Formel (CSV-Injektion); den Namen setzt das Gerät, also eine veränderte App an einem beliebigen Konto, und die Datei öffnet die BetreiberIn. Excel fragt vor DDE und `HYPERLINK` nach; die Wirkung ist eine Täuschung oder ein Abfluss der Tabelle, kein Zugriff auf die Anlage. Die CSV der NutzerIn schreibt `assets/export.js` im Browser aus eigenen Daten (Stück 7) | Zellen, die mit `=`, `+`, `-`, `@`, Tabulator oder Wagenrücklauf beginnen, mit einem führenden Apostroph oder Tabulator entschärfen — als kleine Hilfsfunktion in `format_lib.php`, damit der nächste Export sie bekommt; dazu ein Fall in der Rollen- oder Statistikprobe mit einem Modellnamen `=1+1` |

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
2. **Angreiferlagen** — die neun aus dem Krypto-Review vom 05.09.2026 (A bis
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

### 3.2 Inventar der Quellen (R17-01, 11.10.2026)

**Wie es entstanden ist.** Drei lesende Leser (Opus, je einer für Server
und Browser, für Uhr und Android, für Kette und Betrieb) haben den Code bei
Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegen die Doku gelesen und je
Baustein Fundstelle, Verhalten, Doku-Abgleich, Probe und Angreiferlage
geliefert; Fable hat die Hoch- und Mittel-Befunde am Quelltext nachgeprüft
(Spalte „geprüft" in 2.3) und das Inventar verdichtet. **Was keiner der
drei sicher lesen konnte, steht im Prüfdokument, Abschnitt 1** — nicht hier.
Die Spalte **Modell** nennt den Abschnitt von `docs/Bedrohungsmodell.md`
nach der Gliederung in 3.1 (1 Schutzziele · 2 Angreifer · 3 Zusagen und
Grenzen · 4 Bausteine · 5 Restrisiken · 6 Entwurfsfragen S11). Fundstellen
nennen Funktionen, keine Zeilen.

**Server und Browser**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-01 | Passwortableitung | `assets/crypto.js` `deriveKeys()`; `db.php` `KDF_ITER_ZIEL` | PBKDF2-HMAC-SHA256, 600 000 Runden, 16-Byte-Salz je Konto; Bytes 0–31 „Hälfte" (bleibt im Browser), 32–63 Anmelde-Token. `auth_salt.php` liefert für jede Adresse Salz oder HMAC-Pseudosalz in gleicher Form | `proben/anteil`, `proben/raten` | 4 |
| I-02 | Server-Anteil (S10) | `serverkrypto_lib.php` `kdf_anteil()`, `konto_anteil()`; `crypto.js` `datenschluessel()`; `ui.php` `ui_krypto_bootstrap()` | Anteil je Konto = HMAC(`kdf_anteil`, `konto:<id>`), geht als HKDF-Salz in den Datenschlüssel; `kdf_anteil` nur in `config.php`. Ausgeliefert an jede angemeldete Sitzung **und** an jeden gültigen Reset- oder Einladungslink (`pw_handling.php`); Demo ohne | `proben/anteil` | 4, F-R17-01 |
| I-03 | Anmelde-Token beim Server | `login.php`, `pw_handling.php`, `api/kdf_upgrade.php` | `users.password_hash` = bcrypt(Anmelde-Token); das Token enthält **keinen** Anteil. Blindvergleich gegen `AUTH_VERGLEICHSWERT`, Mindestdauer 0,35 s | keine | 4, F-R17-01 |
| I-04 | Inhaltsschlüssel und Hüllen | `crypto.js` `huelleBauen()`, `huelleOeffnen()`, `contentKeyCheck()`; `validate_lib.php` `WRAP_PW_RE`, `WRAP_RC_RE`, `RW_PRIVAT_RE` | CK 256 Bit Zufall im Browser; `pat_wrap_pw` als `edka1:<Anteilkennung>:…`, `pat_wrap_rc` immer `edk1:` (Code 20 Zeichen ≈ 98 Bit, SHA-256 ohne Streckung), `pat_blob` AES-256-GCM `edk1:`, `pat_key_check` 128 Bit. **Der CK wird nie rotiert**; ein Passwortwechsel packt nur die Hülle um | `proben/anteil`, `proben/rueckweg` | 4, F-R17-10 |
| I-05 | Schlüssel zur Laufzeit | `crypto.js` `setDataKey()`, `getContentKey()`, `merkeAbleitungen()`; `keyguard.js` | `sessionStorage`: `edk` und `pck` als Hex, `edkvor` (PBKDF2-Hälften **und** Anmelde-Token) nach jeder Anmeldung bis zur ersten Seite, die den CK braucht. Keyguard verwirft nach 30 min nur `pck`; `edk` bleibt bis Navigation oder Tabende | `proben/frist`, `proben/abmelden` | 4, 6 (Nr. 146), F-R17-24 |
| I-06 | Inhalt des Blocks | `einsatz_form.php`, `mission_fields.php` (`'store' => 'pat'`), `mf_ist_spalte()` | Elf Schlüssel nach `Technik.md` 4.98; `notes` über den Katalog; Server prüft nur die Form (`PAT_BLOB_RE`) | `quelltext/kennzeichnung` | 3 |
| I-07 | Klartextspalten mit Orts- oder Personenbezug | `schema.sql` | `missions`: Zeiten, Strecke, `site_ele_m`, Transportziel mit Koordinate, Schockraum, Winde, `bw_unit`, **`bw_info`** („Namen / Infos"), **`other_ema`**, `other_resources`, `notes` (Altbestand), Gerät; `mission_phases.lat/lon`; `mission_crew.name`; `resus_*`; `days` (Zeiten, Standort, `notes`); `day_crew.name`; `track_points`, `track_blobs` (SPUR1); Stammdaten; `users.email/name`; `protokoll_ereignisse.text` | keine | 3, F-R17-25 |
| I-08 | Anmeldung und Ratenschutz | `login.php`; `ratelimit_lib.php` `RATE_GRENZEN`, `rate_gleiche_dauer()` | Sperre vor bcrypt (`login` 10/15 min, `login_ip` 50/15 min, Leiter, globale Verlangsamung), Login-CSRF, Antwortgleichheit | `proben/raten`, `proben/sitzung` | 4 |
| I-09 | Sitzung und Bindung | `sitzung_lib.php` `sitzung_starten()`, `sitzung_binden()`; `auth_guard.php` | Cookie `Secure`/`HttpOnly`/`SameSite=Strict`, `use_strict_mode`, `session_regenerate_id` nach Erfolg; Bindung `EDBIND` (32 Byte, in der Datei nur SHA-256); Ablage `server/.sitzungen/` 0700; Rolle und Epoch je Anfrage aus `users`; 30 min Inaktivität | `proben/sitzung`, `quelltext/sitzungshaertung` | 4 |
| I-10 | Registrierung | `registrieren.php` `pow_aufgabe_neu()`, `pow_ok()`, `reg_stempel()` | 16-Bit-Rechenaufgabe in der Sitzung, signierter Zeitstempel, Wegwerfliste, Mindestdauer, gleiche Antwort | `proben/raten`, `proben/csp-browser` | 4 |
| I-11 | Zweitfaktor | `totp_lib.php`; `passkey_lib.php`; `db.php` `ZF_FRISCH_HANDLUNGEN` | TOTP (Geheimnis unter dem Serverschlüssel), Passkeys ohne PRF, „Gerät merken" (`EDGERAET`, SHA-256 in `vertraute_geraete`), frischer Code für acht Griffe — **nicht** für Komplett-Download, Freigabe, Adresswechsel, Setz-Link. Der Zweitfaktor sitzt vor der Sitzung, nicht vor dem Datenschlüssel | `proben/zweitfaktor`, `proben/passkey` | 4, F-R17-02, -08 |
| I-12 | Rückweg und Notzugang | `rueckweg_lib.php`; `zweitfaktor_notweg.php`; `nachweis_lib.php` | `rw_privat` als `edk1:` unter dem CK, Signatur über serverseitige Herausforderung; Notzugang verlangt Datei, Datenbankwert und Passwort, antwortet gleich und gleich lang | `proben/rueckweg`, `proben/zweitfaktor` Teil 8 | 4 |
| I-13 | Reset per Mail | `reset_request.php`; `konto_lib.php` `reset_token_ausstellen()`; `pw_handling.php` | Token 32 Byte, als SHA-256 gespeichert, 1 h (Einladung 24 h); die Seite liefert `pat_wrap_rc` und den Anteil des Kontos; der Server prüft beim Reset Form, Rundenzahl, Anteilkennung und **nur `pat_key_check`** — den Wiederherstellungsschlüssel prüft allein der Browser | `proben/anteil`, `proben/mail` | 4, F-R17-06 |
| I-14 | Verwaltung am fremden Konto | `admin_user.php` (Aktionen `konto`, `pw_reset`, `totp_zuruecksetzen`, `konto_status`, `user_delete`, `freigeben`) | Admin: Adresse sofort ändern (Hinweismail an die alte), Setz-Link, 2FA-Reset, sperren, löschen, Paket an **jedes** Konto freigeben; Riegel gegenüber BetreiberIn-Konten nur Rollenwechsel und „letzte BetreiberIn". Support: nur `pw_reset`, `verifikation`, `device_aus` an `user` | `proben/rollen` | 2 (I, K), F-R17-02, -06, -07 |
| I-15 | Serverschlüssel | `serverkrypto_lib.php` `sk_versiegeln()`, `sk_oeffnen_mit_wem()`; Job `schluesselwechsel` | `edsk1:` AES-256-GCM mit Zweck in der AAD; versiegelt Ziel-Zugänge, Kontopakete, **TOTP-Geheimnisse**, **Protokollarchive**; Komplettbackup mit eigener Blockversiegelung; Wechsel als Vorgang mit `server_key_alt`; öffnet keine Hülle | `proben/schluesselwechsel`, `proben/komplett` | 4, F-R17-26 |
| I-16 | `config.php` und `app_state` | `config.example.php`; `install.php` | 16 `konfig()`-Schlüssel: DB, SMTP, `health_token`, `server_key(_alt)`, `kdf_anteil(_alt)`; die Anwendung schreibt die Datei selbst (Schlüsselgriffe). **In der Datenbank** und damit in jedem Abzug: `salt_secret`, `reg_secret`, `notzugang_geheim`, `jobs_token` | `proben/anteil` | 3, 4 |
| I-17 | Konto-Backup `.edbak` | `crypto.js` `sealBackup()`; `backup_lib.php` `edbak_build()` | Browser entschlüsselt und versiegelt **Klartext** mit dem Backup-Passwort (PBKDF2, AES-GCM, AAD je Teil); enthält `pat_key_check` | `proben/container`, `proben/wiederherstellung` | 4 |
| I-18 | Kontopaket und Freigabe | `adminbackup_lib.php` `edbak_freigeben()`; `api/adminbackup_freigabe.php` | Paket Fassung 3 (Klartextfelder, `pat_blob` als Chiffre, `pat_wrap_rc`, `pat_key_check`, Adresse, Name) unter dem Serverschlüssel; Freigabe an jedes Konto außer dem Besitzer, bestätigt nur durch Abtippen der Zieladresse, **ohne Protokoll, Mail, frischen Code**; der Endpunkt entsiegelt und liefert Klartext-JSON der Einträge, Diensttage und Spuren | `proben/freigabe`, `proben/rollen` | 4, F-R17-02 |
| I-19 | Komplettbackup | `komplett_lib.php`; `admin_komplettsicherung.php`; `wiederherstellen.php` | Dump aller Tabellen (ohne `rate_limits`, `sicherheit_ereignisse`), `EDKOMP1` je 256 KiB unter Serverschlüssel oder Passphrase (PBKDF2 600 000); Klartext-Dump im Bauordner während des Baus; Download „klar" entsiegelt (nur BetreiberIn, Protokoll, **kein frischer Code**); Vorgabe 2 Stände | `proben/komplett`, `proben/wiederherstellung` | 4, 5, F-R17-08 |
| I-20 | Backup-Ziele | `sicherungsziel_lib.php` `ZielFtp`, `ZielSftp` | SFTP mit gemerktem Hostschlüssel; FTPS über `ext/ftp` **ohne Zertifikatsprüfung**; Zugänge versiegelt; drüben wird nie gelöscht außer per Löschregel; Protokollarchive gehen ohne Aufbewahrungsregel mit | `proben/versand` | 3, 4 |
| I-21 | Spuren | `spur_lib.php`; `gpx.php` | `track_points`/`track_blobs` (SPUR1) im Klartext; Abruf hinter `auth_guard` mit `user_id` | `proben/spur`, `proben/gpx` | 3, 6 |
| I-22 | Demo-Konto | `demo_lib.php`; `server/demo/fixture.json.gz` | Schlüsselmaterial in der Fixture, Passwort öffentlich, kein Anteil, Rolle `user` fest, Kennung nur aus `app_state`, Reset 30 min nach Änderung; Anmeldung abschaltbar | `referenzdatensatz/fixture/riegelprobe.php` | 3 |
| I-23 | Ingest und Kopplung | `ingest.php`; `pair.php`; `kopplung_lib.php`; `db.php` `geraet_schluessel_hash()` | Geräteschlüssel 192 Bit Zufall, gespeichert als SHA-256; Blindvergleich; Ersetzfenster 72 h nur für eigene Einsätze (`device_id` + `client_ref`); Kopplung nach 4.99b (Code weist nichts aus, Rückbestätigung am Gerät) | `proben/ingest`, `proben/kopplung`, `proben/geraete` | 2 (H), 4 |
| I-24 | Rollen und Kontentrennung | `auth_guard.php`; `einsatz_lib.php` `einsatz_laden()`; Matrix 4.99p | `user_id` in den gelesenen Abfragen (Stichproben: `api/mission.php`, `api/suchindex.php`, `gpx.php`, `api/pat_anheben.php`); die Rollenprobe misst `{ziel}` = `user`, nicht Admin → BetreiberIn | `proben/rollen` | 2 (I, J, K), F-R17-07 |
| I-25 | Protokoll und Systemmeldungen | `protokoll_lib.php` `protokoll()`; `systemmeldung_lib.php` `system_melden()` | Adressen der Konten im Text, Verwaltung 365 Tage (90–1095), übrige 30; keine geschützten Felder, keine Tokens, keine Passwörter gefunden; Archiv versiegelt | `proben/protokoll` | 3 |
| I-26 | CSP und Kopfzeilen | `kopfzeilen_lib.php` `kopf_csp()`, `kopf_hsts()`; `.htaccess` | Nonce-CSP (`default-src 'none'`, Geocoder und vier Kacheldomains), **Vorgabe Report-Only**; HSTS Vorgabe 1 Tag; `nosniff`, `DENY`, Referrer-Policy | `proben/csp-browser`, `quelltext/csp` | 4, 5, F-R17-23 |
| I-27 | Adresssuche und Kacheln | `geocoder_lib.php`; `assets/geocoder.js`, `ortsfeld.js`, `map_layers.js` | Photon (`photon.komoot.io`, Vorgabe **an**) bekommt getippte Adresse und Koordinate der geschützten Felder `loc.*`/`start.*`; Kacheln von OSM, openmaps.fr, OpenTopoMap, Esri | `proben/csp-browser` | 3 (Lage G), F-R17-09 |
| I-28 | Suchindex und Export | `api/suchindex.php`; `api/export_data.php`; `assets/export.js` | Index je Konto, Block als Chiffre; Export im Browser entschlüsselt, ZIP-AES optional, sonst Klartextdatei auf dem Gerät | keine | 3 |
| I-29 | Speicherdauern | `trash_lib.php`; `jobs_lib.php` (Aufräumen); `konto_lib.php` | Papierkorb 90 d; Protokoll 365/30 d; `sicherheit_ereignisse` 30 d; Mail 30 d (Rumpf bei Zustellung geleert); Sitzungen 30 min + Karenz; Selbstlöschung 30 d; unbestätigte Konten 48 h; Kontopakete 2, Komplett-Stände 2; **Ziele unbegrenzt**; Spuren ab 6 Monaten ausgedünnt; auf Produktiv kein Cron (huckepack) | `proben/jobs`, `quelltext/jobregister` | 3 |
| I-30 | Unangemeldet sichtbar | `ui.php`; `api/health.php`; `jobs.php`; `.htaccess` | Fassung in Fußzeile und `data-webversion`; `health.php` und `jobs.php` mit Token als GET-Parameter; `.htaccess` sperrt `config.php`, `schema.sql`, `db.php`, `smtp.php`, `auth_guard.php`, `install.lock`, Punktpfade, `apk/`, `demo/`, Nachweismuster — nicht `wartung.lock`, `ueberlast.json`, `migrations/` | `proben/raten`, `proben/wartung` | 2, 3, F-R17-22 |

**Uhr und Android**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-31 | Garmin: Zugangsdaten | `watch/source/Pair.mc` `_fertig()`; `Uploader.mc` `credentials()`; `resources/settings/properties.xml` | `Storage "cred"` = Kennung und Schlüssel **im Klartext**; Rückfall auf die App-Einstellungen `deviceId`/`apiKey` (Alt-Weg, in Garmin Connect eingetippt); schwebende Daten bis zum Ja nur im RAM | keine; Uhr-Prüfstand übersetzt und fotografiert nur | 2 (H), 4, F-R17-12 |
| I-32 | Garmin: Serveradresse und Transport | `Uploader.mc` `_serverUrl()`, `serverBase()`; `Communications.makeWebRequest` | Adresse aus den App-Einstellungen (in Garmin Connect änderbar); ohne `http`-Anfang wird `https://` vorangestellt, **`http://` bleibt**; jede Anfrage läuft über die Garmin-Begleit-App des Telefons; kein Pinning | keine | 2 (G, M), F-R17-11, -12 |
| I-33 | Garmin: Puffer | `Model.mc` `save()`; `Track.mc` `_flush()`, `readPoints()`; `Cpr.mc` | Laufender und alle unbestätigten Einsätze mit Phasen (Koordinate) und **Reanimationssitzungen**, Spurpunkte in Chunks à 200 — alles Klartext im Gerätespeicher; Gesendetes wird nach Bestätigung gelöscht, **Geparktes bleibt ohne Frist**, nach Trennen im Web bleibt alles bis zur Neukopplung | keine | 2 (H), 6, F-R17-13, -28 |
| I-34 | Garmin: Krypto-Fähigkeit | `watch/manifest.xml` `minApiLevel="3.1.0"`; `grep Toybox.Cryptography` = 0 | Kein Hash, keine Verschlüsselung, kein HMAC im Code; Zufall nur `Math.rand()` für `client_ref`. Was `Toybox.Cryptography` auf den 99 Zielgeräten kann, ist **im Container nicht prüfbar** (kein SDK) — SP-9 behauptet es | keine; Stufe I des Uhr-Prüfstands könnte es mit einer Probe-App messen | 6 |
| I-35 | Handy: Tresor | `tresor/Schluesseltresor.kt`; `tresor/Tresorschluessel.kt` | `tresor.bin` als JSON unter AES-256-GCM, Schlüssel `nadoku-tresor` im AndroidKeyStore, `setUserAuthenticationRequired(false)`, kein StrongBox, kein `setUnlockedDeviceRequired`; schwebende Kopplungsdaten in der Datei | `SchluesseltresorTest` (8), `GeraetTresorTest` (4, nur auf Gerät) | 4 |
| I-36 | Handy: Puffer | `puffer/Puffer.kt`; `Raeumung` | `puffer.db` (SQLite) im Klartext: Punkte, Phasen mit Koordinate, Dienste; **keine Reanimation**; Abgewiesene nach 30 Tagen und beim Trennen geräumt; kein SQLCipher | `PufferMigrationTest`, `AbgewieseneTest` | 2 (H), 6 |
| I-37 | Handy: Backup, Netz, Komponenten | `AndroidManifest.xml`; `res/xml/datensicherung.xml`; `kopplung/Serveradresse.kt`; `src/release/res/xml/netzsicherheit.xml` | `allowBackup=false`, Übertragungsregeln schließen alles aus; Adresse fest aus `BuildConfig`, Release immer `https`, Klartext verboten, nur System-CAs, kein Pinning (Nr. 143); acht Rechte, exportiert nur Launcher und Data-Layer-Horcher; Logs ohne Koordinaten und Schlüssel | `ServeradresseTest`; Manifest-Lesung | 4 |
| I-38 | Wear-OS-Uhr | `uhr/src/main/AndroidManifest.xml`; `gemeinsam/…/Uhrnachricht.kt`; `uhr/…/funk/UhrHorcher.kt`, `Uhrpuffer.kt` | **Keine** Berechtigung, kein Netz, kein Tresor; Format ohne Koordinaten und ohne Schlüssel (`NachrichtenformatTest`); Puffer nur Ereigniszeiten und Phasennummern; Empfang von Quittung und Stand **ohne Absenderprüfung** (das Handy prüft) | `NachrichtenformatTest` (11), `UhrfunkTest` | 4 |
| I-39 | Data Layer | `gemeinsam/…/WearNachrichtenweg.kt`; `handy/…/HandyHorcher.kt`; `uhr/…/UhrHorcher.kt` | `MessageClient.sendMessage` an alle `connectedNodes`; Play-Dienste-Typen in **drei** Dateien (Senden hinter der Schnittstelle, Empfang prinzipbedingt nicht); Handy prüft Absender und Zeitfenster | `UhrannahmeTest`, Attrappe; `DataLayerErreichbarTest` (Gerät) | 3 (Lage L), F-R17-27 |
| I-40 | Signatur und Bau | `tools/kette/appbau.sh` `android_bauen()`; `auslieferung.yml` Job `android` | Ein RSA-4096-Schlüssel signiert Handy- und Wear-APK, derselbe liegt seit 26.09.2026 bei Google; `./gradlew` läuft im Schritt, dessen `env:` Keystore (Base64) und beide Passwörter trägt — **Gradle und jedes Plugin erben sie**; keine `verification-metadata.xml`; Garmin-Entwicklerschlüssel nur im Bauschritt, SDK ohne Prüfsumme | `appbau.sh --selbstprobe` (prüft das Zertifikat, nicht die Umgebung) | 2 (C3, L), 4, F-R17-03 |
| I-41 | Transport Gerät → Server | `docs/JSON-Vertrag.md`; `Sendekoerper.kt`; `Uploader.mc` `_send()` | `X-Device-Id`/`X-Api-Key` statisch je Anfrage, keine Signatur über den Rumpf, kein Zeitstempel; Rumpf im Klartext: Spur, Phasen mit Koordinate, Zeiten, Strecke, Garmin dazu Reanimation; Replay idempotent über `client_ref`+`seq`; **Takt und Größe** verraten Dienstbeginn, Phasen, Ende auch bei intaktem TLS | `proben/ingest`, `SendekoerperTest` | 3, 5 |

**Kette und Betrieb**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-42 | Auslöser und Freigabe | `auslieferung.yml` `on:`; `ausliefern-lauf.yml` `environment:` | Push auf `main` → Staging; Tags `web-v*`, `android-v*`, `uhr-v*`; `workflow_dispatch` **auf jedem Ref** mit Probelauf-Schaltern — ein Probelauf fordert die Umgebung `produktion` an, mit der **Workflow-Datei des Zweigs**; alle übrigen Riegel stehen in dieser Datei; wer freigeben darf, ist nicht im Repositorium | Probelauf von Hand | 2 (C2), 4, F-R17-04 |
| I-43 | Tore des Produktionslaufs | `ausliefern-lauf.yml`; `tools/kette/freigabe.py`, `baumsuche.py`, `tor.py` | Grüner Stufe-1-Lauf auf demselben **Baum**, grüner Staging-Job auf dem Commit, Abstammung vom Zeiger, Backup-Tor (bis 40 × 20 s, prüft Frische, nicht Lesbarkeit), Tag = `WEB_VERSION`, Nachprüfung der gemeldeten Fassung, Wartung bleibt bei offener Migration an. **Nicht für `android` und `uhr`**: dort nur Tag = Fassung und Freigabe | Selbstproben in Stufe 1 | 4, F-R17-17 |
| I-44 | FTPS-Transport | `SamKirkland/FTP-Deploy-Action@110f9186…` (v4.4.0); Ausnahmeliste | Acht Pfade, die drei Verzeichnisse doppelt — stimmt mit `CLAUDE.md` 3; Löschliste nur aus der eigenen Zustandsdatei, die mit `FTP_ZIELPFAD=/` **im Webroot** liegt (Punktregel der `.htaccess` als einzige Schranke); das FTP-Konto sieht `config.php`, `sicherungen/`, `.sitzungen/` | **kein Prüfmittel zählt die Liste** | 4, F-R17-16, -31 |
| I-45 | Pinning und Lieferkette | vier Workflows; `tools/uhr-pruefstand/pruefstand.sh`; Job `stufe2` | 14 von 14 fremden `uses:`-Zeilen an einer SHA (nachgezählt 11.10.2026); **ungepinnt in `run:`**: `pip install cffi cryptography`, `playwright@1.56`, Gradle-Abhängigkeiten, Connect-IQ-SDK — teils im selben Job wie Staging-Passwort, TOTP-Geheimnis, `JOBS_TOKEN` | kein Werkzeug (`kettenaufrufe` liest keine `uses:`) | 4, F-R17-16, -20 |
| I-46 | Geheimnisse der Kette | Workflows; `Technik.md` 6.5; `Sandbox-Setup.md` 4 | `staging`: FTP (3), `JOBS_TOKEN`, Prüfkonto, Passwort, `STAGING_TOTP`; `produktion`: FTP (3), `JOBS_TOKEN`, vier `APK_*`, `UHR_ENTWICKLERSCHLUESSEL_B64`; Repositorium: `CIQ_GERAETE_URL`, `WACHE_BASIS`. Fork-PRs bekommen keine; ein PR aus dem Repositorium fährt seine eigene Workflow-Datei. Staging-Zugänge liegen auch in der Arbeitsumgebung | Stufe-1-Schritt „eine Ebene höher?" (6 Namen) | 3, 4 |
| I-47 | `JOBS_TOKEN` | `jobs.php`; `jobs_lib.php`; `tor.py` | 64 Hex im Klartext in `app_state` (Betrieb → Hintergrundjobs), als GET-Parameter; kann Komplettbackup anstoßen, Wartung schalten, Jobs anhalten; in jedem Komplett-Dump und in jedem Hoster-Zugriffslog eines Kettenlaufs | `tor.py --selbstprobe` | 3, 4, F-R17-22 |
| I-48 | Zweigschutz und Deny-Liste | Rulesets (API, 11.10.2026); `.claude/settings.json` | „Main Protect": PR-Pflicht, **0 Pflicht-Reviews**, Stufe 1 strikt; Merge-Bypass eine Person; **kein Tag-Ruleset**; Zweig `produktion` **ohne Regel** (`rules/branches/produktion` = `[]`); Deny-Liste sperrt nur Merge und Auto-Merge — `push_files`, `create_branch`, `run_workflow`, `cancel_workflow_run` bleiben | gemessen 21.09.2026 (Abwesenheit) | 2 (C2), 4, F-R17-14 |
| I-49 | Integritätswache | `tools/integritaetswache/wache.py`; `integritaet.yml` | Täglich und nach jeder Auslieferung; holt über HTTPS **ohne Zugangsdaten** `server/assets/` und vergleicht SHA-256 gegen den Zeiger; für `login.php` die Menge der Inline-Skripte, Formulare, Umlenkungen; sieht keinen PHP-Code, keine `config.php`, kein `auto_prepend_file`, keine andere Passwortseite (Nr. 358); Alarm = roter Lauf an die Person der letzten `cron`-Änderung | `wache.py --selbstprobe` | 3 (Nr. 263), 4, F-R17-15 |
| I-50 | Wiederanlaufpaket und Schlüsselblatt | `Technik.md` 7; `betrieb_schluesselblatt.php`; Rahmenplan 6.1 | Vier Stücke (`config.php`, Serverschlüssel, Server-Anteil, Zugang zum Ziel), Blatt in zwei Ausdrucken; laut 6.1 **offen seit 14.09.2026**: Anteil anlegen, Blatt drucken, Paket ergänzen; Komplett-Stände auf Produktiv nie geöffnet (seit 25.09.2026 offen); Probe-Wiederherstellung nie gefahren | keine | 5, F-R17-05 |
| I-51 | Mailweg | `mail_lib.php` `mail_einreihen()`, `mail_konto_entfernen()`; `smtp.php` | Warteschlange hält Adresse, Betreff und **Rumpf mit Setz-Links im Klartext** bis zur Zustellung, dann geleert, 30 Tage; SMTP-Zugang in `config.php`; keine geschützten Felder in Mails; `mail_konto_entfernen()` hat **keinen Aufrufer** | `proben/mail` | 3, F-R17-21, -22 |
| I-52 | Wiederherstellen ohne Anmeldung | `wiederherstellen.php` | Schranken: leere Datenbank, Nachweisdatei, Quelle nur `sicherungen/eingang/`; ruft `edbak_ablage_bereit()` **nicht** — auf frischem Webspace fehlt `sicherungen/.htaccess`; eine Klartext-`.sql.gz` dort trägt einen sekundengenau ratbaren Namen | `proben/wiederherstellung` (örtlich) | 4, F-R17-19 |
| I-53 | Staging gegen Produktiv | `Technik.md` 6.3a; Rahmenplan 6a | Zwei Hoster seit 20.09.2026; Staging ohne Echtdaten, eigene Schlüssel; Stufe 2 schreibt auf Staging; die Sperre der Punktdateien ist **nur auf Staging** gemessen; Produktiv-Hoster nur im AVV-Entwurf benannt | Stufe 2 | 3, F-R17-18 |
| I-54 | Die eine Person | `Technik.md` 7 „Notweg"; Rahmenplan 6.2, 6.3 | An der Betreiberin hängen Freigabe, Merge-Bypass, Schlüsselblatt, Betriebsakte, Hoster- und FTP-Zugänge, GitHub-Umgebungen, Play Console, Connect-IQ-Store; Notzugang braucht FTP **und** Datenbank; bei Ausfall keine Auslieferung, kein Merge, kein Schlüsselnachtrag | `proben/zweitfaktor`, `rueckweg` (Stufe 2) | 2 (P), 5 |
| I-55 | Der Hoster | `Technik.md` 6.3a; `docs/rechtstexte/AVV.md` | Liest alles auf der Platte: `config.php`, Sitzungsdateien, Sicherungen, Datenbank, Zugriffsprotokolle mit Tokens; verschlossen bleibt `pat_blob` ohne Passwort oder Zettel — **bis er Code ändert** und das nächste Passwort mitliest; die Wache sieht davon `assets/` und `login.php` | Wache (teilweise) | 2 (O), 3 |

### 3.3 Lage in einem Absatz (R17-01)

Gegen den klassischen Einbruch — Datenbank, Dateisystem, Hoster — hält die
Verschlüsselung des Blocks, und sie hält genau so lange, wie das Passwort
stark ist (I-03: das Offline-Raten bleibt, I-02 verhindert nur das Öffnen
der Hülle aus dem Abzug). Alles neben dem Block liegt offen und
rekonstruiert den Einsatz (I-07). Gegen aktiven Zugriff auf Server oder
Kette (C, C2, C3) steht zwischen Push-Recht und Produktiv **ein Klick**
(I-42, I-48), und Gradle sieht den Signaturschlüssel (I-40). Innerhalb der
Anwendung liest ein Admin über die Freigabe den Klartext jedes Kontos
(I-18), ohne Spur. Die Geräte tragen Schlüssel und Klartextpuffer ohne
Verschlüsselung at rest (I-31, I-33, I-36); SP-9 würde den Server schützen,
nicht das verlorene Gerät. Und die Pflichtstücke des Wiederanlaufs sind laut
Rahmenplan seit vier Wochen offen (I-50).

### 3.4 Der Krypto-Review vom 05.09.2026 — Stand je Befund (R17-04)

Die Datei `docs/konzepte/Review-Krypto-Sicherheit.md` (Stand Web 14.2.2,
Uhr 3.0.2, Android 0.13.0) ist mit R17-04 gelöscht (E-R17-13; zuletzt in
`bc08eda`). Ihr Abschnitt 2 ist durch das Modell ersetzt; ihre Befunde
stehen hier mit dem Stand bei Web 21.14.0, damit Schritt 12b weiß, was
noch zu lesen ist. **„erledigt" heißt: im Code nachgelesen (Inventar
I-NN); „offen für 12b" heißt: nicht nachgelesen, Stück genannt.**

| Nr. | Befund (Kurzform) | Stand bei Web 21.14.0 |
|---|---|---|
| K-1 | Klartext-Ortsdaten | Weg C erledigt (Web 15.6.0, Nr. 138); Weg B ist S11 (12a, E-R17-09, -10); Modell G3, I-07 |
| K-2 | keine CSP; Schlüssel als Hex im `sessionStorage` | CSP mit Nonce gebaut (P5a/AP4), **Vorgabe Report-Only** (F-R17-23, I-26); `CryptoKey` entschieden für 12b (E-R17-08, Nr. 370) |
| K-3 | 320 000 Runden; Passwortstärke einzige Schranke | 600 000 Runden erledigt (SP-1, I-01); Mindestlänge in `pwquality.js` **nicht nachgelesen — offen für 12b, Stück 2**; die Rechnung des Angreifers steht im Modell 4.1 und F-R17-01 |
| K-4 | Adminpakete unversiegelt, `ftp` | erledigt (S10: Fassung 3 versiegelt, `ftp` abgeschafft; I-18); FTPS weiter ohne Zertifikatsprüfung (I-20, Modell 4.4) |
| K-5 | kein Zweitfaktor für NutzerInnen | erledigt (TOTP für alle, 10c/AP5; Passkeys SR-09; I-11) |
| K-6 | Einsatzort an Photon und Kachelserver | Handbuch 12.5 und Textbaustein nennen es; Schalter vorhanden; die Zusage-Texte nicht → **F-R17-09** |
| K-7 | E-Mail-Wechsel ohne Passwort; Admin-Adresswechsel | Profilwechsel: P5b (Double-Opt-In) — **nicht nachgelesen, offen für 12b, Stück 3**; der Admin-Adresswechsel ohne Bestätigung steht → **F-R17-06** |
| K-8 | Login-CSRF | erledigt (Nr. 127, I-08) |
| K-9 | `apk/` und `demo/` unversperrt | erledigt (`.htaccess` 403, I-30) |
| K-10 | DOCTYPE-Sperre im GPX-Import umgehbar (UTF-16) | **gegenstandslos seit S11-05d** (R17-08, 20.10.2026): Der Server liest keine GPX-Datei mehr — `api/gpx_import.php` nimmt verschlossene Stücke und prüft Form, Menge und Kennung; die Datei liest `assets/gpx.js` im Browser, weist ein Nullbyte (UTF-16) vor dem Dekodieren ab und sucht `<!DOCTYPE` im dekodierten Text. Die Regeln sind dort „Bequemlichkeit, keine Schranke" (Kopfkommentar von `api/gpx_import.php`): Wer sie umgeht, schadet dem eigenen Konto |
| K-11 | Auskunft in `wiederherstellen.php` | erledigt (nur „in Betrieb", I-30); neu daneben F-R17-19 |
| K-12 | Klartext-Freitextfelder ohne Hinweis | erledigt (Kleinzeile an jedem Klartext-Freitextfeld, Riegel `kennzeichnung`; Nr. 109 Notizen ins Blob); die Liste in `CLAUDE.md` 4 ist kürzer als der Bestand → F-R17-25 |
| K-13 | Reste im Klartext (Reset-Token, Setz-Link, Bauordner) | Bauordner wird geräumt (Web 15.6.0); Reset-Token im Zugriffslog bleibt, dazu neu der Mailrumpf → **F-R17-22**; Klartext-Dump im Bauordner während des Baus bleibt (F-R17-31) |
| K-14 | verlorene Garmin-Uhr | Ersetzfenster 72 h (Web 15.6.0, I-23), Handbuch-Kasten; Rest: Klartextpuffer samt Reanimation ohne Frist → **F-R17-13, -28**; Serveradresse und Begleit-App → **F-R17-12** |
| K-15 | Header und Escaping (HSTS, Permissions-Policy, `JSON_HEX`, `csrf_check`, `querySelector`) | `JSON_HEX_*`, Permissions-Policy, Referrer-Policy erledigt (I-26); HSTS Vorgabe 1 Tag → F-R17-23; `csrf_check`-Cast und `suche.php` (Nr. 153 erledigt laut Backlog-Erledigt) — **`csrf_check` nicht nachgelesen, offen für 12b, Stück 3** |
| K-16 | Push auf `main` ist Deploy; Branch-Schutz, Org-2FA | Kette mit Freigabe (R67, PK); Ruleset ohne Pflicht-Review (F-R17-29); Org-2FA **unbelegt** (Prüfdokument 1, P-R17-04); neu: **F-R17-04** (Probelauf von jedem Zweig), **F-R17-14** (Zeiger ohne Regel) |
| AN-1 | HTTP-Ausnahme im Release | erledigt (Nr. 142, `netzsicherheit.xml`, I-37) |
| AN-2 | abgewiesene Pakete bleiben | erledigt für Android (30 Tage, I-36); **für die Garmin offen → F-R17-28** |
| AN-3 | kein Pinning, nicht festgehalten | erledigt (Nr. 143 dokumentiert, I-37) |
| AN-4 | Data-Layer-Empfang ohne Absenderprüfung | Handy prüft seit 0.14.0 (Nr. 144, I-39); die Wear-Uhr nicht (F-R17-31) |
| AN-5 | kein R8, Wrapper ohne Prüfsumme | Wrapper mit `distributionSha256Sum` (Nr. 145); **R8/`isMinifyEnabled` nicht nachgelesen — offen für 12b, Stück 9** |

**Aus ihrer Prüfliste (Abschnitt 7) ist noch nicht abgehakt:** Org-2FA und
die Regeln der Umgebungen (P-R17-04), die Passwortstärke der echten Konten
(Einweisung, Modell R1), `aapt2 dump permissions` der Wear-Release-APK
(P-R17-05), die Datenschutzerklärung der Installation mit Photon und den
Kacheldiensten (Betreiberin, Rechtstexte). Die übrigen Punkte sind durch
Proben oder Riegel ersetzt (`proben/sitzung`, `proben/frist`,
`proben/anteil`, `proben/raten`, Stufe 2 „Punktdateien") oder erledigt.

## 4. Arbeitspakete

| Paket | Inhalt | Fächerung | Abnahme |
|---|---|---|---|
| **R17-00 Buchführung** | Spanne 370–379, Fahrplanzeile (Rahmenplan Fassung 154), dieses Dokument mit Statusblock, Prüfdokument; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-01 Bestandsaufnahme** | Die verstreuten Stellen einsammeln und **gegen den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegenlesen**, nicht gegen die Doku: `Technik.md` 4.97, 4.97c/d, 4.98, 4.99a/a2/b/q, `Review-Krypto-Sicherheit.md` 2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18 (Sitzungsbindung, Gerät merken, Passkeys, Schlüsselwechsel, Notzugang, Rechenaufgabe), Kette und ihre Geheimnisse, Keystore des Handys, Geräteschlüssel der Uhr, Play App Signing. Ergebnis: Inventartabelle in 3.2, Befunde, wo Doku und Code auseinandergehen. | **lesend, drei Leser** (Server; Clients; Kette und Betrieb), ohne Nebenwirkung; Fable führt zusammen. | Inventar vollständig gegen die Liste im Fahrplan (Schritt 12: „Verschlüsselung, Container 4, SPUR1, Komplettbackup und Serverschlüssel, Demo, Handy, Kopplung, SP-9 und Nr. 146, Signatur, Kette, Nr. 109, Nr. 263"); jede Zeile mit Fundstelle. |
| **R17-02 Das Modell schreiben** | `docs/Bedrohungsmodell.md` nach 3.1; Bereich `c` der Textprobe; Verweise aus `README.md`, `Technik.md` (4.99b und der Sicherheitsabschnitt verweisen hin, statt zu wiederholen), `CLAUDE.md` 4. | seriell | Modell vollständig gegen 3.1; Textprobe 0 neue Treffer; Verweise stehen. |
| **R17-03 Fragen an die Betreiberin** | Q-R17-NN mit Empfehlung und Preis: Nr. 146 (Argon2id; `CryptoKey`), S11-Entwurfsfragen, Nr. 263. **Halt:** wartet auf die Antworten. | seriell | Jede Frage beantwortet und als E-R17-NN eingetragen; Nr. 146 erledigt, Nr. 263 mit Stand. |
| **R17-04 Abschluss** | Antworten ins Modell einarbeiten, Prüfdokument mit Mittel und Zahl, Prüfstand `klein`, Freigabe der Betreiberin; danach Erledigt-Zeile (Rahmenplan 8), Backlog-Kopfzeilen, Verlaufszeile, Pull Request gegen `main`. | seriell | Freigabe erteilt; PR gestellt. |
| **R17-05 Buchführung 12b** | Spanne 400–409 (zuerst gepusht), Rahmenplan Fassung 167 (Fahrplanzeile, Kopf), Abschnitt 7 und 2.3a hier, Prüfdokument fortgeschrieben, E-R17-14 bis -16; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-06 Stück 2 — Kryptographie und Schlüssel** | Server- und Browser-Krypto samt S11 (7.3, Stück 2); K-3 (`pwquality.js`); Nr. 370 als Entwurfsfrage. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 38 Dateien gelesen (7.3.2), F-R17-32 bis -35 in 2.3a, sieben Altbefunde bestätigt, K-3 beantwortet, Nr. 370 mit Verwenderzahl notiert; Prüfprotokoll mit Zahl (5). |
| **R17-07 Stück 3 — Zugang und Sitzung** | Anmeldung, Sitzung, Zweitfaktor, Registrierung, Kopplung, Ratenschutz, CSRF; jeder HTTP-Einstieg des Stücks gegen die Checkliste (7.2); K-7, K-15. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 31 Dateien und der API-Teil von `db.php` gelesen (7.3.3), F-R17-36 und -37 in 2.3a, F-R17-32 verschärft, F-R17-22, -23, -31 bestätigt, K-7 und K-15 beantwortet, P-R17-07; Prüfprotokoll mit Zahl (5). |
| **R17-08 Stück 4 — Einsatz, Diensttag, Spur, Ingest** | Ingest, SPUR2, Diensttag, Einsatz, Nachbearbeitung, GPX-Import, Prüfschicht, Feldkatalog; K-10. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 36 Dateien gelesen (7.3.4), F-R17-38 bis -41 in 2.3a, F-R17-09, -13, -28 nachgelesen (soweit das Stück sie sieht), K-10 beantwortet, Nr. 23 gelesen, P-R17-08; Prüfprotokoll mit Zahl (5). |
| **R17-09 Stück 5 — Verwaltung und Admin** | Einstellungen, Admin-Seiten, Stammdaten, Rechtstexte, Papierkorb, Suche, Zeitraum, APK-Ausgabe, Statistik; Nr. 371, 381; F-R17-02, -06, -07, -08 in Endfassung. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 31 Dateien gelesen (7.3.5), F-R17-42 bis -45 in 2.3a, F-R17-07 und -08 bestätigt (Nr. 371, 381), F-R17-02 und -06 unverändert, P-R17-09; Prüfprotokoll mit Zahl (5). |
| **R17-10 Stück 6 — Betrieb** | `update.php` und Migrationen, Wartung, Jobs, Sicherungsziele, Mail, Installation, `db.php`, `ui.php`, Kopfzeilen, `.htaccess`; Nr. 77; F-R17-19, -21. | wie R17-06 | wie R17-06 |
| **R17-11 Stück 7 — Browser-Skripte und Stylesheet** | alle `assets/*.js`, `style.css`, die Köpfe unter `vendor/`; der `innerHTML`-Durchgang (Prüfdokument 1 (11)); Nr. 341, 380, 382; F-R17-24. | wie R17-06 | wie R17-06 |
| **R17-12 Stück 8 — Uhr** | `watch/` vollständig (Krypto der Gerätehülle, Puffer, Uploader, Kopplung, Anzeige); F-R17-11, -12, -13, -28. | wie R17-06; Compiler-Warnungen nur, wenn das SDK im Container steht | wie R17-06 |
| **R17-13 Stück 9 — Android** | beide Module, Gradle, Manifeste, Ressourcen; AN-5 (R8); F-R17-03, -27, -31. | wie R17-06; Android Lint örtlich | wie R17-06 |
| **R17-14 Stück 10 — Werkzeuge und Workflows** | `tools/` und `.github/`; je Werkzeug: prüft es noch, was der Code tut?; Nr. 188, 263; F-R17-04, -14 bis -18, -20. | wie R17-06; `vulture` örtlich | wie R17-06 |
| **R17-15 Stück 11 — Doku gegen Code** | die normative Doku gegen den Stand Web 23.0.2, Uhr 4.0.0, Android 0.18.0; F-R17-09, -25, -26, -29, -30. | wie R17-06 | wie R17-06 |
| **R17-16 Stück 12 — Zusammenführung** | Fundliste sortiert (alle Grade, 2.3 und 2.3a), Sofortpaket ausgewiesen, Kommentardurchgang und Karteileichen je Codebasis gebündelt, Vorschlag für den Paketschnitt, die R5-Ausnahmeliste als Zuarbeit für P7. | seriell | Jede Nummer hat einen Vorschlag; der Paketschnitt ist ein Entwurf mit Fable-Markierung je Paket. |
| **R17-17 Freigaberunde** | Q-R17-NN je Fund mit Empfehlung und Preis. **Halt:** wartet auf die Antworten der Betreiberin; danach E-R17-NN, Backlog-Kopfzeilen, der Paketschnitt wird verbindlich, Rahmenplan (Reihenfolge, R-Nummer, Fahrplanzeile). | seriell | Jede Frage beantwortet und als E-R17-NN eingetragen; Paketschnitt steht. |

Die Pakete **nach** der Freigaberunde (Sofortpaket, Pflichtpakete,
Aufräumpakete je Codebasis, Kommentardurchgang) bekommen ihre Zeilen mit
R17-17 — der Paketschnitt folgt der Freigaberunde, nicht umgekehrt
(E-PV-5 (8)).

## 5. Prüfprotokoll

Je Paket: was gemessen wurde, mit Mittel und Zahl. Die Prüfliste für die
Betreiberin steht im Prüfdokument.

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| R17-09 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `php -r` (Undefined array key beim Monat 13, F-R17-44) · `grep` über `ZF_FRISCH_HANDLUNGEN` gegen die `zf_`-Zweige von `einstellungen.php` (F-R17-43), über `DEMO_GESPERRT` gegen die Handlungen von `admin_user.php` (F-R17-42), über `fputcsv` und `_geraet_kuerzen()` (F-R17-45), über `api/range.php` (Monatsprüfung) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 31 Dateien, 18 322 Zeilen (27 PHP-Dateien im Kern, 2 `api/`-Einstiege, 2 Skripte); 17 HTTP-Einstiege (15 Seiten, 2 `api/`), dazu `rechtstext_seite.php` hinter vier Hüllen; 4 Einträge in 2.3a (3 niedrig, 1 Hinweis); F-R17-07, -08 bestätigt, Nr. 371, 381 bestätigt, F-R17-02, -06 unverändert · PHPStan 201 Meldungen im Stück: 181 `variable.undefined` (Rauschen aus `auth_guard.php`), 0 `class.notFound`, 20 gelesen: 0 Befunde, 7 veraltete Rückgabeformen (`totp_zustand()` liefert `geraete`, `statistik_zeitraum()` `bis_utc`, `edbak_auftrag_schub()` `meldungen`), 10 Absicht (leere Store-Adressen, immer wahre Weichen), 2 Fehlalarme (Schließung mit `&$absatz`), 1 toter Zweig (7.3.5) · 140 Funktionen der 15 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 0 ohne Nennung (18 nur in der eigenen Datei, Seitenhelfer; 27 davon JavaScript in `suche.php`, `zeitraum.php`, `einstellungen.php`) · 1 012 Kommentarverweise in 31 Dateien · `ZF_FRISCH_HANDLUNGEN`: 8 Handlungen, `zf_codes_neu` fehlt · `DEMO_GESPERRT`: 7 von 10 Schreibwegen · `fputcsv`: 1 Schreiber, 0 Formelschutz · Steuerung: siehe Commit-Nachricht von R17-09 | 20.10.2026 |
| R17-08 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `php -r` (Warnung beim `use` einer undefinierten Variablen, F-R17-38) · `git log -S` (seit wann `n_gespeichert` fehlt, F-R17-39) · `grep` gegen `tools/proben/gpx/probe.mjs`, `demo_lib.php` und `assets/wartungslauf.js` (misst die Probe eine gepackte Spur? wer stößt den Wartungslauf an?) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 36 Dateien, 20 019 Zeilen (22 PHP-Dateien im Kern, 8 `api/`-Einstiege, 6 Skripte); 20 HTTP-Einstiege (12 Seiten, 8 `api/`); 4 Einträge in 2.3a (1 mittel, 2 niedrig, 1 Hinweis); 3 Altbefunde nachgelesen, 3 bestehen (soweit das Stück sie sieht) · PHPStan 101 Meldungen im Stück: 87 `variable.undefined` (`$userId`, `$patReady`, `$patWrapPw` aus `auth_guard.php`, Rauschen), 0 `class.notFound`, 14 gelesen: 5 sind zwei Befunde (F-R17-38 vier Meldungen, F-R17-39 eine), 9 Absicht oder harmlos (7.3.4) · 193 Funktionen der 14 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 0 ohne Nennung · 1 242 Kommentarverweise in 36 Dateien · Probe `gpx`: 1 Abruf über `[data-gpx-laden]`, an einer frischen Spur der Stufe 1 (`demo_lib.php` nennt keine Stufe); `wartungslauf.js` ruft `api/wartungslauf.php` aus `index.php` · Steuerung: siehe Commit-Nachricht von R17-08 | 20.10.2026 |
| R17-07 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `grep` gegen `tools/proben/raten/probe.php` und `docs/Technik.md` 5e.2 (F-R17-36) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 31 Dateien, 11 238 Zeilen, dazu `db.php` Zeilen 480 bis 830 (JSON-Hülle, Eingang der Endpunkte) und `wegwerfdomains.txt` nur auf Form (8 883 Zeilen); 2 Einträge in 2.3a (1 mittel, 1 niedrig), 1 Altbefund verschärft; 3 Altbefunde nachgelesen, 3 bestehen · PHPStan 31 Meldungen im Stück: 22 `variable.undefined` (`$row`, `$userId`, `$userRole`, `$einwilligungOffen` aus `auth_guard.php` in `zweitfaktor.php` 16 und `einwilligung.php` 6, Rauschen), 0 `class.notFound`, 9 gelesen: 0 Befunde, 1 Fehlalarm (`jobs.php`, Closure), 8 Absichtsvergleiche (7.3.3) · 169 Funktionen der 15 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 2 ohne Nennung (`sitzung_bindung_loeschen()`, `adresse_vormerkung_loeschen()`) · 608 Kommentarverweise in 31 Dateien · Probe `raten`: `login_ip` an 4 Stellen, alle isoliert, 0 über den Pfad der Anmeldung · Steuerung: siehe Commit-Nachricht von R17-07 | 20.10.2026 |
| R17-06 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `git log -G`/`-S` · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 38 Dateien, 20 053 Zeilen gelesen; 4 Einträge in 2.3a (1 mittel, 2 niedrig, 1 Hinweis); 7 Altbefunde nachgelesen, 7 bestätigt · PHPStan über `server/` (ohne `vendor/`, `assets/`, `demo/`, `doku/`): 506 Meldungen, davon 88 in Stück 2 — 29 `variable.undefined` (`$userId` aus `auth_guard.php`, Rauschen), 44 `class.notFound` (phpseclib und ConstantTime liegen unter `vendor/`, ausgeschlossen), 15 gelesen: 1 Befund (F-R17-33), 6 tote Zweige (7.3.2), 8 Absichtsvergleiche · 334 PHP-Funktionen des Stücks gegen `server/`, `tools/`, `docs/` gezählt: 1 ohne Nennung (`serverschluessel_zeile()`); `crypto.js`, `keyguard.js`, `pwquality.js`, `kontopaar.js`, `schluessel.js`: 0 Methoden ohne Verwender · 987 Kommentarverweise in 38 Dateien (Zahl je Datei in 7.3.2) · Steuerung: 21 Decken, 0 gerissen · 0 neue Nummern · 0 neue Treffer · 150/0 · Prüfstand `klein`: Bericht in der Commit-Nachricht von R17-06 | 20.10.2026 |
| R17-05 | `decken.py` · `uebersicht.py --ziel 12b` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 21 Decken, 0 gerissen (die Decke `backlog-kopf` riss zwischendurch mit 61 Zeilen — behoben durch Streichen der gemergten Spannen 12a und 19; `fahrplan-vokabular` riss mit „läuft" — Statuswort auf „Umsetzung") · 69 offene Einträge, 0 ohne Grammatik, 0 ohne Ziel, 10 zu Ziel 12b · 0 neue Nummern, 15 Zweige verglichen, 0 Überschneidungen · 0 neue Treffer, 0 durchgerutschte Fallen · 0 Befunde · 150/0 · Prüfstand `klein`: Bericht in der Commit-Nachricht von R17-05 | 20.10.2026 |
| R17-04 | `decken.py` · `uebersicht.py --ziel 12` und `--ziel 12b` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · `grep` auf den gelöschten Dateinamen · Prüfstand `klein` | 21/0 · Ziel 12: 0, Ziel 12b: 7 · 2 neue Nummern, 0 Überschneidungen · 0 neue Treffer · 0 Befunde · 150/0 · 7 Nennungen der gelöschten Datei, alle hier als Fundstelle oder Geschichte · Prüfstand 23 grün (Baum `ea29dca…` = `30d7226`); der Abschluss trägt seinen Bericht in der eigenen Commit-Nachricht | 11.10.2026 |
| R17-03 | `decken.py` · `uebersicht.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 21/0 · 62/0/0 · 0 · 0 neue Treffer · 0 Befunde · 150/0 · Prüfstand: Commit-Nachricht von R17-03 | 11.10.2026 |
| R17-02 | `textprobe.py --bereich c` (mit dem neuen Dokument) · `textprobe.py --selbstprobe` · `decken.py` · `bestand.py` · `linkprobe.py` · `pysyntax.py` · Prüfstand `klein` | Textprobe: erst 52 neue Treffer (43 `luft`: Garmin, Connect IQ, Garmin Connect, Spur; 9 `hausform`: Betreiberin), nach Hausform-Korrektur und zwei begründeten Ausnahmen **0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen**; Selbstprobe 4/4 · 21 Decken, 0 gerissen · 0 Befunde · 150 Verweise, 0 Abweichungen · 63 Werkzeuge, 0 Fehler · Prüfstand: Commit-Nachricht von R17-02 | 11.10.2026 |
| R17-01 | drei Leser (Opus, lesend) · Nachprüfung Fable am Quelltext · `gh api` lesend (Rulesets, `rules/branches/produktion`) · `decken.py` · `uebersicht.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 55 Bausteine, 31 Befunde (5 hoch, 15 mittel, 8 niedrig, 2 Hinweise); **11 Befunde von Fable am Quelltext nachgeprüft** (F-R17-01 bis -06, -11, -14, -15, -19, -21), 20 als Lesung gekennzeichnet; `rules/branches/produktion` = `[]`; Werkzeuge: siehe Prüfdokument 2; Prüfstand: Commit-Nachricht von R17-01 | 11.10.2026 |
| R17-00 | `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | 21 Decken, 0 gerissen (die Verlaufszeile riss einmal mit 333 Zeichen, gekürzt auf 283) · 62 Einträge, 0 ohne Grammatik, 0 ohne Ziel, 2 zu Ziel 12 · 0 neue Nummern, 0 Überschneidungen gegen 8 Zweige · 0 neue Treffer · 0 Befunde · 150 Verweise, 0 Abweichungen. Prüfstand `klein`: siehe Commit-Nachricht von R17-00 | 11.10.2026 |

## 6. Quellen

- `docs/Rahmenplan.md` 3 (Schritt 12, 12a, 12b; „Warum so"), 7 (R17, R69,
  R78, R86).
- `docs/konzepte/Konzept-Planung-v1.0.md` 4e (Befund, Optionen, E-PV-5).
- Der Krypto-Review vom 05.09.2026 (`docs/konzepte/Review-Krypto-Sicherheit.md`
  bis `bc08eda`; **gelöscht mit R17-04**, E-R17-13; Stand je Befund in 3.4).
- `docs/konzepte/Vorbereitung-Sicherheitspaket.md` (SP-3b, SP-7, SP-9).
- `docs/konzepte/Konzept-V1-Ortsdaten.md` (Vorstudie Weg B, Fragen in 6
  beantwortet).
- `docs/Backlog.md` Nr. 146, Nr. 263, Nr. 43 (Weg B, gehört zu 12a);
  `Backlog-Erledigt.md` Nr. 109 (Notizen ins Blob) und Nr. 138 (Weg C).
- `docs/Technik.md` 4.97 bis 4.99q; `CLAUDE.md` 3 und 4.

## 7. Schritt 12b — Stücke 2 bis 12: Review und Bereinigung

### 7.1 Auftrag

**Ziel (Rahmenplan 3, Schritt 12b):** sauberer Code, Verhalten unverändert
außer bei Funden. Der Review liest den Stand **nach S11** — Web 23.0.2,
Uhr 4.0.0, Android 0.18.0 — in den Stücken 2 bis 12 aus E-PV-5 (2), damit
der Code **genau einmal und in seiner Endfassung** gelesen wird (R86); die
Krypto auf Uhr und Handy, SPUR2, die Spurfunktionen im Browser und die
Nutzlastfassung der Sicherung sind deshalb mit im Umfang. Gesucht werden
**Bugs, Sicherheitslücken, ungebrauchter Code, Karteileichen und Probleme
jeder Art** (R69); gelesen wird gegen das Bedrohungsmodell
(`docs/Bedrohungsmodell.md`: Schutzziele, Lagen A bis P, Zusagen mit
Grenze). Dazu der **Kommentardurchgang** (R13, R31): Kommentare erklären das
Warum in Worten; Beschlussnummern (R…, E-…), Backlog-Nummern, Fassungen
(„seit Web 9.7.0"), Konzept- und Paketnamen verschwinden aus dem Code — wo
ein Grund ohne den Verweis unverständlich würde, wird er ausgeschrieben, wo
er nur Geschichte war, entfällt er. Die Rückverfolgung leisten Changelog
und Rahmenplan-Archiv.

**Der Review behebt nichts** (K4, E-PV-5 (6)) — auch nicht „eine Zeile".
Jeder Fund wird Befund (2.3a) oder Aufräumposten (7.3) und geht in die
**Freigaberunde** (R17-17): Die Betreiberin entscheidet je Fund; dann
Sofortpaket für Kritisches, Pflicht- und Aufräumpakete für den Rest.
**v1.0 wird nicht erklärt, solange ein Fund offen ist.** Kryptographie-
Änderungen sind Fable-Schritte, alles andere Opus (K8).

**Abnahme (Rahmenplan 3):** Review vollständig (elf Stücke, jede Datei der
Listen in 7.3 gelesen), Pakete abgenommen, Wortliste 0/0/0.

**Nicht Ziel:** Umbenennung, Doku-Neufassung, Manifest, Changelog neu —
das ist P7 (Schritt 13). Nicht Ziel ist auch eine zweite Bestandsaufnahme
gegen die Doku: Die 31 Befunde aus 2.3 bleiben stehen und werden in ihrem
Stück **in Endfassung nachgelesen** (steht ein Befund nach S11 anders, sagt
es die Zeile in 2.3a mit Verweis), nicht neu erhoben.

### 7.2 Lesweise je Stück

Jedes Stück ist ein Paket (R17-06 bis -16) und liefert vier Dinge:

1. **Die Dateiliste, abgehakt.** 7.3 nennt je Stück die Dateien; eine
   Datei, die nicht gelesen wurde, steht dort als nicht gelesen, mit Grund
   (Prüfdokument 1). Fable liest selbst (E-R17-14); ein Unter-Agent darf
   Aufrufer suchen, Verweise zählen, ungenutzte Regeln listen — lesend,
   ohne Nebenwirkung —, und das Ergebnis steht mit Mittel und Zahl im
   Prüfprotokoll (5). Vorsortierung örtlich (E-R17-16), was der Container
   kann; was er nicht kann, ist ein Befund mit Zahl im Prüfdokument 1.
2. **Befunde** in 2.3a — mit Grad, Stück, Fundstelle als Funktionsname,
   Wirkung gegen das Modell (welche Lage, welche Zusage), Empfehlung und
   geschätztem Aufwand in der Empfehlung.
3. **Aufräumposten** in 7.3 je Stück: Karteileichen (Funktionen, Felder,
   Regeln, Dateien ohne Verwender — mit der Gegenprobe, **wer** sie nicht
   mehr ruft; bei Antwortverträgen, Export und Backup gilt Nr. 21: „niemand
   liest es" reicht dort nicht), ungebrauchter Code, und die Fundstellen
   des Kommentardurchgangs **als Zahl je Datei**, nicht als Liste jeder
   Zeile — die Liste erzeugt das Aufräumpaket selbst.
4. **Prüfprotokoll** (5) und Prüfdokument (2) mit Mittel und Zahl;
   Statusblock, Commit, Push (K7).

**Die Checkliste für jeden HTTP-Einstieg** (E-PV-5, U3) — Seiten im
Wurzelverzeichnis von `server/` und alles unter `api/`:

| Punkt | Frage |
|---|---|
| Rolle | Wer darf hierher? `auth_guard`-Aufruf, Rollenprüfung, Besitzprüfung je Datensatz (IDOR) |
| CSRF | Jeder schreibende Weg hinter `csrf_check()` oder `EdApi`; kein GET, das schreibt |
| Eingabe | Jede Einsatzangabe über `validate_lib.php`; Form und Größe verschlossener Werte; Typen, Längen, Listen |
| Ausgabe | Jede Ausgabe kodiert (`htmlspecialchars`, `JSON_HEX_*`, im Browser kein `innerHTML` mit Fremdtext) |
| Fehlerweg | Was verrät eine Ausnahme, ein 500, ein Timing? Antwortgleichheit, wo das Modell sie verlangt (Anmeldung, Reset, Registrierung) |
| Rate | Ratenschutz, wo geraten werden kann |
| Datenbank | nur gebundene Parameter; `mf_spalten()` statt Spaltenliste; Transaktion über `db_transaktion()` |
| Eine Stelle | die elf Wege aus `CLAUDE.md` 4 (Register `tools/zaehlung/`) — kein zweiter Eingang |

Für Uhr und Android tritt an ihre Stelle die Modulliste aus E-PV-5 (U3):
Geheimnis- und Schlüsselablage, Puffer, Sendeweg, Kopplung,
Vordergrunddienst, Nachrichtenweg — vollständig; der Rest nach der
Checkliste sinngemäß (Eingabe, Fehlerweg, Verwender).

### 7.3 Die Stücke — Umfang, zugehörige Posten, Stand

Die Dateilisten sind der Stand bei `f17380c` (20.10.2026); eine Datei, die
während 12b dazukommt, wird dem Stück nachgetragen. Die Zuordnung folgt
E-PV-5 (2), ergänzt um das, was seit dem 03.09.2026 entstanden ist (P5,
S10, 18, S11). **Eine Datei gehört zu genau einem Stück**; wer sie in
einem anderen braucht, liest sie dort nach, trägt sie aber nicht doppelt.
Die Spalte „Posten" nennt, was das Stück außer der Lesung mitbringt: offene
Befunde aus 2.3 zum Nachlesen in Endfassung, offene K- und AN-Posten aus
3.4, Backlog-Punkte mit Ziel 12b.

| Stück | Paket | Umfang | Posten | Stand |
|---|---|---|---|---|
| 2 Kryptographie und Schlüssel | R17-06 | `serverkrypto_lib.php`, `auth_salt.php`, `pw_handling.php`, `komplett_lib.php`, `backup_lib.php`, `adminbackup_lib.php`, `wiederherstellen.php`, `demo_lib.php`, `geraetehuelle_lib.php`, `kontopaar_lib.php`, `schluesselwechsel_lib.php`, `schluessel_teile.php`, `totp_lib.php`, `passkey_lib.php`, `rueckweg_lib.php`, `betrieb_schluesselblatt.php`, `codeblatt.php`, `notfallblatt.php`, `zweitfaktor_teile.php`; `api/kdf_upgrade.php`, `api/pat_anheben.php`, `api/schluessel_erneuern.php`, `api/adminbackup_freigabe.php`, `api/backup_*.php`; Browser: `crypto.js`, `keyguard.js`, `pwquality.js` und die Skripte der Gerätehülle und des Schlüsselpaars | F-R17-01, -02, -06, -10, -19, -24, -26; K-3 (Mindestlänge in `pwquality.js`); Nr. 370 (`CryptoKey`) als Entwurfsfrage für den Paketschnitt | **gelesen** 20.10.2026 (R17-06; Ergebnis in 7.3.2) |
| 3 Zugang und Sitzung | R17-07 | `login.php`, `logout.php`, `session_lib.php`, `sitzung_lib.php`, `auth_guard.php`, `ratelimit_lib.php`, `reset_request.php`, `pair.php`, `jobs.php`, `zweitfaktor.php`, `zweitfaktor_notweg.php`, `registrieren.php`, `bestaetigen.php`, `adresse_bestaetigen.php`, `einwilligung.php`, `einwilligung_lib.php`, `konto_lib.php`, `einstieg_lib.php`, `kopplung_lib.php`, `kopfzeilen_lib.php`, `netz_lib.php`, `wegwerfdomains.txt`, die CSRF-Prüfung in `db.php`; die zugehörigen `api/`-Einstiege und Browser-Skripte | F-R17-22, -23, -31 (Anteil an jeden Link); K-7 (Adresswechsel), K-15 (`csrf_check`); P-R17-04 bleibt bei der Betreiberin | **gelesen** 20.10.2026 (R17-07; Ergebnis in 7.3.3) |
| 4 Einsatz, Diensttag, Spur, Ingest | R17-08 | `ingest.php`, `spur_lib.php`, `tag_spuren.php`, `diensttag_*.php`, `tageszuordnung_lib.php`, `einsatz.php`, `einsatz_form.php`, `einsatz_lib.php`, `einsatz_loeschen.php`, `einsatz_verschieben.php`, `nachbearbeitung.php`, `nachbearbeitung_lib.php`, `import.php`, `validate_lib.php`, `mission_fields.php`, `mission_fields_lib.php`, `nachweis_lib.php`, `geraete_lib.php`; die zugehörigen `api/`-Einstiege (Spur, Phasen, Reanimation, Export) und Browser-Skripte (Spurfunktionen, Karte, Formular) | F-R17-09 (Photon, Kacheln), -13, -28 (Fristen, soweit Server); K-10 (DOCTYPE/UTF-16 im GPX-Import); Nr. 23 nur lesen (gehört zu 13) | **gelesen** 20.10.2026 (R17-08; Ergebnis in 7.3.4) |
| 5 Verwaltung und Admin | R17-09 | `einstellungen.php`, `konten_einstellungen_lib.php`, `admin_users.php`, `admin_user.php`, `admin_demo.php`, `admin_installation.php`, `admin_komplettsicherung.php`, `admin_protokoll.php`, `admin_rechtstexte.php`, `admin_sicherungen.php`, `admin_sicherungsziele.php`, `stammdaten_ui.php`, `rechtstexte_lib.php`, `rechtstext_seite.php`, `papierkorb.php`, `trash_lib.php`, `suche.php`, `zeitraum.php`, `apk.php`, `apk_lib.php`, `geraetemodelle.php`, `geraetemodelle_lib.php`, `statistik_lib.php`, `betrieb_statistik.php`, `erststart_karte.php`, `blatt_dialog.php`, `rueckfrage_dialog.php`; zugehörige `api/` und Skripte | F-R17-07, -08; Nr. 371 (Admin-Riegel), Nr. 381 (Suche und Zeitraum) | **gelesen** 20.10.2026 (R17-09; Ergebnis in 7.3.5) |
| 6 Betrieb | R17-10 | `update.php`, `migration_lib.php`, `migrations/`, `schema.sql`, `wartung_lib.php`, `jobs_lib.php`, `sicherungsziel_lib.php`, `speicher_lib.php`, `smtp.php`, `email_lib.php`, `mail_lib.php`, `install.php`, `db.php` (außer CSRF), `transaktion_lib.php`, `konfig_lib.php`, `plattform_lib.php`, `php_mindest.php`, `status_lib.php`, `systemmeldung_lib.php`, `umgebung_lib.php`, `instanz_lib.php`, `protokoll_lib.php`, `protokoll_archiv_lib.php`, `ankuendigung.php`, `ankuendigung_lib.php`, `betrieb_*.php` (außer Statistik und Schlüsselblatt), `ui.php`, `format_lib.php`, `zip_lib.php`, `doku_lib.php`, `doku_seite.php`, `hilfe.php`, `ueber.php`, `index.php`, `version.php`, `config.example.php`, `.htaccess`, `api/health.php`; die Rechtstext-Seiten (`impressum.php`, `datenschutz.php`, `avv.php`, `nutzungsbedingungen.php`) | F-R17-05 (Stand bei der Betreiberin), -19, -21; Nr. 77 (`update.php` in Unterseiten) | offen |
| 7 Browser-Skripte und Stylesheet | R17-11 | alle 57 Dateien unter `server/assets/` (`*.js`), `style.css`, die Köpfe unter `assets/vendor/` und `assets/fonts/` (Herkunft, SHA-256); der `innerHTML`-Durchgang; was in den Stücken 2 bis 5 schon gelesen ist, wird hier nur auf Verwender und tote Zweige gelesen | F-R17-24 (`edk`, `edkvor`); Nr. 341 (sechs Regeln, Fokus-Zweig), 380 (Seitenaufbau), 382 (erste Antwort) | offen |
| 8 Uhr | R17-12 | `watch/source/*.mc` (20 Dateien), `watch/resources/` (Einstellungen, Strings, Manifest), `watch/monkey.jungle`; Modulliste: Geheimnis und Gerätehülle, Puffer samt Reanimation, Uploader, Kopplung, Anzeige | F-R17-11 (`http://`), -12 (Begleit-App, Einstellungen), -13, -28 (Frist); AN-2 für die Garmin | offen |
| 9 Android | R17-13 | `android/` beide Module (98 Kotlin-Dateien), `build.gradle.kts`, `libs.versions.toml`, Manifeste, `netzsicherheit.xml`, `netzwerk_pruefstand.xml`, Ressourcen, die Prüffälle; Modulliste: Keystore, Nachrichtenweg und Horcher, SQLite-Puffer, Vordergrunddienst, Kopplung, Sendeweg | F-R17-03 (Gradle und `APK_*`), -27 (drei Dateien mit Play-Diensten), -31 (Wear ohne Absenderprüfung, 400/403); AN-5 (R8, `isMinifyEnabled`) | offen |
| 10 Werkzeuge und Workflows | R17-14 | `tools/` (22 Ordner, 433 Dateien) und `.github/workflows/` (vier Dateien); je Werkzeug: prüft es noch, was der Code tut? Anlass-Zeile, Anleitung nach `Pruefablauf.md` 6.2; die Kette gegen das Modell (Lagen C, K) | F-R17-04, -14, -15, -16, -17, -18, -20; Nr. 188 (Dokumentenprobe), Nr. 263 (Wache, E-R17-12) | offen |
| 11 Doku gegen Code | R17-15 | `README.md`, `CLAUDE.md`, `docs/Technik.md`, `Handbuch.md`, `JSON-Vertrag.md`, `Export-Format.md`, `Backup-Format.md`, `Geraete-Eingabe.md`, `Design.md`, `Lizenzen.md`, `Bedrohungsmodell.md`, `Pruefablauf.md`, `Sandbox-Setup.md`, `android/LIESMICH.md`, die `LIESMICH.md` der Werkzeuge (Zahlen gegen `Pruefablauf.md` 6.11), `docs/rechtstexte/` nur auf technische Aussagen | F-R17-09, -25, -26, -29 (Sammelbefund veralteter Stellen), -30 | offen |
| 12 Zusammenführung | R17-16 | kein Code: Fundliste sortiert (2.3 und 2.3a), Sofortpaket ausgewiesen, Aufräumposten je Codebasis gebündelt, Kommentardurchgang als Zahl je Codebasis, Vorschlag für den Paketschnitt mit Fable-Markierung, R5-Ausnahmeliste als Zuarbeit für P7 | Nr. 21 (die A4-Kandidaten entstehen aus den Karteileichen-Listen neu) | offen |

Die Unterabschnitte **7.3.2 bis 7.3.12** entstehen mit dem jeweiligen Paket
und halten je Stück: die abgehakte Dateiliste, die Aufräumposten
(Karteileichen mit Gegenprobe, ungebrauchter Code), die Kommentarfundstellen
als Zahl je Datei und die Ergebnisse der Vorsortierung.

#### 7.3.2 Stück 2 — Kryptographie und Schlüssel (R17-06, 20.10.2026)

**Dateiliste, abgehakt — 38 Dateien, 20 053 Zeilen, jede von Fable
vollständig gelesen** (E-R17-14; kein Unter-Agent, die Zähler unten sind
`grep`). Drei Dateien, die 7.3 dem Stück zuschlug, lesen sich im
Zusammenhang des Zugangs und gehen nach **Stück 3**:
`api/rueckweg_anlegen.php`, `api/passkey_anlegen.php`,
`api/schluesselblatt_pruefen.php` (dort als Einstiege des Zweitfaktors).
Die Bibliotheken dahinter (`rueckweg_lib.php`, `passkey_lib.php`,
`totp_lib.php`) sind hier gelesen. Die Spalte rechts ist die Zahl der
Fundstellen des Kommentardurchgangs (Muster: Paketkürzel, E-/F-/R-Nummern,
„bis/seit Web N", „Nr. NNN"; Orientierung, kein Prüfwert — das Muster
trifft auch Fachbegriffe, die so aussehen).

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `serverkrypto_lib.php` | 1 569 | 100 | `api/kdf_upgrade.php` | 240 | 10 |
| `auth_salt.php` | 127 | 5 | `api/pat_anheben.php` | 152 | 3 |
| `pw_handling.php` | 709 | 42 | `api/schluessel_erneuern.php` | 138 | 4 |
| `komplett_lib.php` | 1 744 | 45 | `api/kontopaar.php` | 145 | 5 |
| `backup_lib.php` | 2 563 | 140 | `api/adminbackup_freigabe.php` | 130 | 10 |
| `adminbackup_lib.php` | 2 587 | 109 | `api/backup_data.php` | 80 | 5 |
| `wiederherstellen.php` | 780 | 21 | `api/backup_eintraege_restore.php` | 71 | 4 |
| `demo_lib.php` | 822 | 45 | `api/backup_restore.php` | 131 | 13 |
| `geraetehuelle_lib.php` | 205 | 12 | `api/backup_spuren.php` | 90 | 5 |
| `kontopaar_lib.php` | 156 | 4 | `api/backup_spuren_restore.php` | 89 | 5 |
| `schluesselwechsel_lib.php` | 1 320 | 90 | `assets/crypto.js` | 1 035 | 37 |
| `schluessel_teile.php` | 101 | 4 | `assets/keyguard.js` | 161 | 5 |
| `totp_lib.php` | 647 | 45 | `assets/unlock.js` | 579 | 26 |
| `passkey_lib.php` | 992 | 83 | `assets/pwquality.js` | 682 | 7 |
| `rueckweg_lib.php` | 391 | 23 | `assets/schluessel.js` | 185 | 5 |
| `betrieb_schluesselblatt.php` | 220 | 23 | `assets/kontopaar.js` | 197 | 6 |
| `codeblatt.php` | 128 | 6 | `assets/schluesselblatt.js` | 249 | 4 |
| `notfallblatt.php` | 213 | 14 | `assets/rueckweg.js` | 161 | 10 |
| `zweitfaktor_teile.php` | 91 | 1 | `assets/passkey.js` | 173 | 11 |

**Summe 987 Verweise.** Die fünf Dateien mit den meisten tragen 522 davon
(`backup_lib`, `adminbackup_lib`, `serverkrypto_lib`,
`schluesselwechsel_lib`, `passkey_lib`) — dort ist der Kopfkommentar
Geschichte des Pakets, nicht Beschreibung des Codes, und dort beginnt das
Aufräumpaket.

**Was die Lesung ergeben hat, in einem Absatz.** Die Kryptographie selbst
hält, was das Modell sagt: Die Hüllen (`edk1:`, `edka1:`, `edsk1:`,
Gerätehülle Fassung 1, EDKOMP1, Konto-Backup Fassung 4) tragen Zweck oder
Bindung in den Zusatzdaten, der Wiederherstellungsschlüssel bleibt vom
Anteil fern (`WRAP_RC_RE`, `RW_PRIVAT_RE`, geprüft an jeder Schreibstelle
einschließlich der Demo-Fixture), `pat_blob` und Stücke gehen auf keinem
Weg im Klartext über den Server, und jeder Schreibweg der Backups prüft
Eigentum je Kennung gegen `user_id`. Die vier Einträge in 2.3a sind
Ränder: ein fehlender Ratenschutz an einem von drei Geschwistern
(F-R17-32), eine Prüfung, die eine Umbenennung abgeschaltet hat
(F-R17-33), eine Lücke in der Prüfschicht des Rückwegs (F-R17-34), ein
Rückfall je Teil statt je Paket (F-R17-35). Nichts davon ist kritisch,
nichts ein Sofortpaket; F-R17-32 gehört in das Paket zu F-R17-01 (b).

**Die sieben Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen —
alle bestehen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-01 (hoch) | unverändert: `password_hash($neuToken, …)` in `api/kdf_upgrade.php` und `pw_handling.php`, Token ohne Anteil |
| F-R17-02 (hoch) | unverändert: `edbak_freigeben()` ruft weder `protokoll()` noch eine Mail; `freigeben` steht nicht in `ZF_FRISCH_HANDLUNGEN` (acht Handlungen gezählt) |
| F-R17-06 (mittel) | unverändert: Reset-Zweig in `pw_handling.php` prüft allein `key_check` gegen `pat_key_check` |
| F-R17-10 (mittel) | unverändert: `api/schluessel_erneuern.php` schreibt `protokoll('verwaltung', 'schluessel_erneuert', …)`, keine Mail |
| F-R17-19 (mittel) | unverändert: `edbak_ablage_bereit()` wird in `wiederherstellen.php` nicht gerufen (0 Treffer) |
| F-R17-24 (niedrig) | bestätigt, mit einer Präzisierung: `edkvor` wird von `unlock.js` beim ersten Schlüsselbedarf, beim Abmelden und bei jedem Anmeldeversuch geräumt (`vergissAbleitungen()`), liegt also einen Seitenwechsel lang; `edk` bleibt, wie beschrieben |
| F-R17-26 (niedrig) | unverändert: der Kopf von `serverkrypto_lib.php` nennt die Protokollarchive, nicht die TOTP-Geheimnisse; `config.example.php` keines von beiden |

**K-3 beantwortet** (3.4): `pwquality.js` verlangt `MIN_LAENGE = 12`,
dieselbe Zahl wie `PW_MIN_LAENGE` in `db.php`; dazu die Liste häufiger
Passwörter, eine Anteilsregel gegen Wiederholungen und Reihen. Ein
zufällig erzeugtes Passwort wird nicht gegen den Rest gemessen — richtig,
es ist die stärkere Wahl. Kein Befund.

**Nr. 370 (`CryptoKey`) — Notiz für den Paketschnitt:** `getContentKey()`
hat **vier Verwenderdateien** mit zusammen elf Nennungen; der Schlüssel
liegt als Hex unter `pck` im `sessionStorage`, daneben das
`schluesselfach` (ein `CryptoKey`-Zwischenspeicher, den `clearSession()`
leert). Der Umbau ersetzt das Hex durch einen nicht-extrahierbaren
`CryptoKey` in IndexedDB; das Lebensdauermodell „ein Tab, ein Schlüssel"
bleibt (E-R17-08).

**Aufräumposten (kein Befund):**

- **Karteileiche, mit Gegenprobe:** `serverschluessel_zeile()` in
  `serverkrypto_lib.php` — 0 Nennungen in `server/`, `tools/` und `docs/`
  außer der Definition (seit S10 baut `config_eintrag_zeile()` die Zeile
  direkt; der Umschlag ist übrig geblieben).
- **Tote Zweige seit Web 23 (S11-12), von PHPStan bestätigt:**
  `edbak_build()` in `backup_lib.php` — die Vollausgabe mit `missions` und
  `rest_segments` am Ende der Funktion und die Zeile `if (!$nurKopf)
  $data['_spur_index'] = …` sind unerreichbar, weil der Riegel am Kopf nur
  noch Kopf oder Fenster durchlässt (`deadCode.unreachable`,
  `booleanNot.alwaysFalse`, `if.alwaysTrue`); `komp_schub_lauf()` in
  `komplett_lib.php` — das letzte `return` nach dem Siegel-Zweig
  (`deadCode.unreachable`). Dazu `$cursor === []` und `$stand === 'siegel'`
  in derselben Datei, `$u['pat_wrap_pw'] !== null` in `kdf_upgrade.php`,
  `$m['blob_alt'] !== null` in `pat_anheben.php`, `$kennungAlt !== null`
  in `serverkrypto_lib.php`: Vergleiche, die der Typ schon entscheidet —
  Gürtel und Hosenträger, lesbar, kein Fehler; das Aufräumpaket entscheidet
  je Stelle.
- **Register (Stück 10 prüft die Decke):** `api/kdf_upgrade.php` ruft
  `$pdo->beginTransaction()` direkt statt `db_transaktion()` — eine der
  Stellen, die die Registerzeile „Transaktion" oberhalb von null hält.
- **Bewusst wirkungslos:** der Parameter `csrf` in `schluessel.js` (die
  Kopfzeile setzt `EdApi`); ein Aufrufer reicht ihn noch durch.
- **Beobachtung ohne Posten:** `pw_handling.php` nimmt im Reset-Zweig
  `key_check = ''` an, wenn das Konto noch kein `pat_key_check` trägt
  (Altkonten) — dieselbe Klasse wie F-R17-06, dort mit abgedeckt.
- **Kommentardurchgang:** 987 Fundstellen (Tabelle oben); das Aufräumpaket
  erzeugt die Zeilenliste selbst.

**Vorsortierung, was sie gebracht hat:** PHPStan hat den einen Befund
geliefert, den die Lesung übersehen hätte (F-R17-33 — zwei wortgleiche
Zeichenketten in einem `||` liest ein Mensch als zwei verschiedene, weil
der Kommentar es sagt), und die toten Zweige bestätigt. Die 44
`class.notFound` sind kein Befund, sondern die Grenze der Konfiguration
(`vendor/` ausgeschlossen, phpseclib und ConstantTime ohne Autoloader);
die 29 `variable.undefined` sind `$userId` aus `auth_guard.php` in
jedem `api/`-Einstieg — ein Muster, das PHPStan ohne Bootstrap-Datei nicht
versteht. Beides steht im Prüfdokument 5.

#### 7.3.3 Stück 3 — Zugang und Sitzung (R17-07, 20.10.2026)

**Dateiliste, abgehakt — 31 Dateien, 11 238 Zeilen, jede von Fable
vollständig gelesen; dazu aus `db.php` der Teil, den 7.3 dem Stück
zuschlägt** (die JSON-Hülle `json_kopf()`, `json_roh_out()`, `json_out()`,
der Eingang `api_methode()`, `api_rumpf()`, `json_fehler()`, Zeilen 480 bis
830; der Rest von `db.php` ist Stück 6) **und `wegwerfdomains.txt` nur auf
Form** (8 883 Zeilen, eine Domain je Zeile — Inhalt liest niemand). Die
drei Einstiege, die Stück 2 hierher verwiesen hat (`api/passkey_anlegen.php`,
`api/rueckweg_anlegen.php`, `api/schluesselblatt_pruefen.php`), sind hier
gelesen; dazu `api/kopplung_stand.php` und `api/csp_bericht.php` und die
fünf Skripte, die nur dieses Stück verwendet. Die Verwender aus Stück 2
(`unlock.js`, `passkey.js`, `rueckweg.js`) sind nicht noch einmal gezählt.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `login.php` | 1 324 | 114 | `einwilligung_lib.php` | 222 | 8 |
| `logout.php` | 18 | 0 | `konto_lib.php` | 807 | 33 |
| `session_lib.php` | 483 | 23 | `einstieg_lib.php` | 382 | 11 |
| `sitzung_lib.php` | 755 | 58 | `kopplung_lib.php` | 223 | 6 |
| `auth_guard.php` | 712 | 59 | `kopfzeilen_lib.php` | 559 | 16 |
| `ratelimit_lib.php` | 1 642 | 77 | `netz_lib.php` | 143 | 5 |
| `reset_request.php` | 149 | 7 | `api/passkey_anlegen.php` | 121 | 14 |
| `pair.php` | 459 | 36 | `api/rueckweg_anlegen.php` | 141 | 6 |
| `jobs.php` | 386 | 12 | `api/schluesselblatt_pruefen.php` | 260 | 6 |
| `zweitfaktor.php` | 330 | 20 | `api/kopplung_stand.php` | 58 | 1 |
| `zweitfaktor_notweg.php` | 368 | 26 | `api/csp_bericht.php` | 143 | 2 |
| `registrieren.php` | 528 | 44 | `assets/api.js` | 220 | 4 |
| `bestaetigen.php` | 101 | 3 | `assets/kopplung.js` | 199 | 3 |
| `adresse_bestaetigen.php` | 61 | 2 | `assets/pow.js` | 80 | 4 |
| `einwilligung.php` | 179 | 3 | `assets/pow-worker.js` | 159 | 4 |
| | | | `assets/zweitfaktor.js` | 26 | 1 |

**Summe 608 Verweise.** Drei Dateien tragen 250 davon (`login.php`,
`ratelimit_lib.php`, `auth_guard.php`); die Checkliste aus 7.2 ist an
jedem der 19 HTTP-Einstiege des Stücks durchgegangen.

**Was die Lesung ergeben hat, in einem Absatz.** Der Zugang ist sorgfältig
gebaut, und die Sorgfalt ist an den richtigen Stellen: eine Sitzungsart je
Zweck mit festen Cookie-Parametern und `use_strict_mode` an genau einer
Stelle, die Bindung ohne Übernahme alter Sitzungen, die Rolle aus der
Zeile statt aus der Sitzung, ein halber Stand zwischen Passwort und Code,
der für jeden Endpunkt „nicht angemeldet" ist, konstante Antwortdauer in
jedem Fehlerzweig, der etwas verraten könnte (Anmeldung, Reset,
Registrierung, Kopplung, Notzugang, Schlüsselblatt), `hash_equals()` an
jedem Geheimnis, kein offener Umleiter (`zweitfaktor_zurueck()` lässt nur
Seiten der Liste zu), der Rückweg schaltet den Zweitfaktor erst nach Tor
und Sitzung ab, `csrf_ok()` nimmt Feld oder Kopfzeile und keinen Typ, den
es nicht erwartet. Die beiden Befunde sind Ränder derselben Bibliothek: Der
Ratenschutz zählt an einer Stelle zu viel (die Adresse in jedem Konto-Topf,
F-R17-36) und an einer anderen zu wenig (der Notzugang ohne Adresse,
F-R17-37) — und F-R17-32 aus Stück 2 hat in `einstellungen.php` drei
Geschwister. Nichts davon öffnet ein Konto; F-R17-36 kann eine Klinik
aussperren und widerspricht der Doku, und das ist der Grund für „mittel".

**Die Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-22 (niedrig) | unverändert: `jobs.php` nimmt `token` aus `$_GET` (oder `$_POST`), `adresse_bestaetigen.php` den Token aus `$_GET`; der erste Abruf jedes Links steht im Zugriffsprotokoll des Hosters |
| F-R17-23 (niedrig) | unverändert: `KOPF_HSTS_VORGABE = 1`, `kopf_csp_scharf()` liefert ohne Einstellung `false` (Report-Only); ob Produktiv umgeschaltet ist, sagt nur P-R17-04 |
| F-R17-31 (Hinweis) | für die Server-Anteile bestätigt (`ui_krypto_bootstrap()` ist Stück 7, die Wear-Prüfung Stück 9); nichts Neues |

**K-7 beantwortet** (3.4): Der Adresswechsel im Profil (`einstellungen.php`)
verlangt das Passwort (`old_token` gegen den Hash), schreibt die Adresse
**nicht** sofort, sondern merkt sie vor (`adresse_vormerken()`), schickt
den Bestätigungslink an die **neue** und eine Warnung an die **alte**
Adresse (`profil_adresswechsel_melden()`); erst der Klick schreibt
(`adresse_bestaetigen()`, das UNIQUE auf `email` fängt die Dublette dort).
Das ist das Double-Opt-In aus P5b. Der Adresswechsel durch die Verwaltung
ohne Bestätigung bleibt F-R17-06. Was der Passwortnachweis dort nicht hat,
ist ein Topf — das ist die Verschärfung von F-R17-32.

**K-15 beantwortet** (3.4): `csrf_ok()` in `session_lib.php` liest das Feld,
sonst die Kopfzeile `X-CSRF`, und prüft `is_string()` vor `hash_equals()`
— `csrf[]=x` ist „kein Token" und kein TypeError mehr. `csrf_check()` in
`auth_guard.php` antwortet 403 als JSON unter `api/`, sonst als Seite.
Header und Escaping: `kopfzeilen_seite()` setzt `nosniff`,
`Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, CSP mit Nonce
(`default-src 'none'`, `script-src 'self' 'nonce-…'`, `style-src-attr
'unsafe-inline'` mit Begründung, `img-src data:` mit Begründung) und HSTS
nur über HTTPS; `kopfzeilen_json()` den schmalen Satz. Kein Befund.

**P-R17-04** bleibt bei der Betreiberin (GitHub-Umgebungen, Zweitfaktor der
Organisation, Produktiv-Einstellungen); dieses Stück kann keinen der drei
Punkte aus dem Repositorium heraus beantworten.

**Aufräumposten (kein Befund):**

- **Karteileichen, mit Gegenprobe (0 Nennungen in `server/`, `tools/`,
  `docs/` außer der Definition):** `sitzung_bindung_loeschen()` in
  `sitzung_lib.php` (das Beenden ruft `sitzung_cookie_loeschen('bindung')`
  und `session_destroy()` direkt); `adresse_vormerkung_loeschen()` in
  `konto_lib.php` (der im Kommentar genannte „Abbruch durch die NutzerIn"
  hat keinen Knopf — entweder er kommt, oder die Funktion geht).
- **Zweite Schreibweise eines Merkmals:** `rate_konto_freigeben()` baut
  `'id:' . mb_substr(mb_strtolower($konto), 0, 180)` selbst statt
  `rate_merkmal_kennung()` zu rufen — läuft auseinander, sobald jemand die
  Normalisierung dort ändert (dieselbe Klasse wie F-R17-36, nur ohne
  Wirkung heute).
- **Kommentarwiderspruch** in `ratelimit_lib.php` gegen `login.php` (siehe
  F-R17-36) — mit dem Befund zu bereinigen, nicht vorher.
- **Register (Stück 10 prüft die Decke):** `pair.php` ruft
  `$pdo->beginTransaction()` direkt statt `db_transaktion()`;
  `login.php`, `zweitfaktor_notweg.php` und `kopplung.js` rufen `fetch()`
  direkt — zwei davon unangemeldet (kein Token, `EdApi` nicht geladen), die
  dritte ein GET; Stück 10 sieht nach, wie die Registerzeile sie zählt.
- **PHPStan, 9 gelesene Meldungen, keine ein Befund:** `db.php` 212/238
  (`!== []` gegen eine Konstante — Gürtel und Hosenträger), `jobs.php` 358
  (Fehlalarm: die Closure wird neu ausgewertet), `konto_lib.php` 362
  (`elseif`-Kette, die letzte Bedingung ist durch die vorigen bestimmt),
  `pair.php` 173/216 (`is_array($b)` zweimal, das zweite Mal schon
  entschieden), `registrieren.php` 232 (der Rest-Bit-Zweig von `pow_ok()`
  ist mit `POW_BITS = 16` unerreichbar — parametrisch richtig, heute tot).
- **Kommentardurchgang:** 608 Fundstellen (Tabelle oben).

**Vorsortierung, was sie gebracht hat:** nichts, was die Lesung nicht
hatte — die beiden Befunde sind Verhaltensfragen über drei Dateien hinweg
(welche Merkmale ein Aufruf bildet), die kein statisches Werkzeug sieht.
PHPStan hat die zwei Karteileichen nicht gefunden (Stufe 4 kennt keine
„ungenutzte Funktion"); die hat der `grep` über die Definitionen.

#### 7.3.4 Stück 4 — Einsatz, Diensttag, Spur, Ingest (R17-08, 20.10.2026)

**Dateiliste, abgehakt — 36 Dateien, 20 019 Zeilen, jede von Fable
vollständig gelesen:** die 22 PHP-Dateien des Kerns, die acht
`api/`-Einstiege des Stücks und die sechs Skripte, die Spur, Karte und
Formular tragen. `ortsfeld.js`, `ortswahl.js`, `locparse.js`,
`zeitfeld.js`, `vorschlagsliste.js` und `geocoder.js` sind Verwender des
Formulars und gehören zu Stück 7; `wartungslauf.php` und
`wartungslauf.js` sind Stück 6 (dort mit F-R17-39 im Blick).

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `ingest.php` | 1 314 | 117 | `mission_fields_lib.php` | 617 | 39 |
| `spur_lib.php` | 1 449 | 65 | `nachweis_lib.php` | 172 | 7 |
| `tag_spuren.php` | 468 | 10 | `geraete_lib.php` | 436 | 19 |
| `diensttag_datum.php` | 152 | 6 | `api/day.php` | 585 | 52 |
| `diensttag_lib.php` | 1 502 | 95 | `api/mission.php` | 304 | 28 |
| `diensttag_loeschen.php` | 111 | 3 | `api/range.php` | 278 | 30 |
| `diensttag_neu.php` | 199 | 10 | `api/schneiden.php` | 496 | 37 |
| `diensttag_zusammenfuehren.php` | 390 | 19 | `api/suchindex.php` | 272 | 29 |
| `tageszuordnung_lib.php` | 334 | 19 | `api/export_data.php` | 579 | 63 |
| `einsatz.php` | 1 148 | 69 | `api/gpx_import.php` | 174 | 12 |
| `einsatz_form.php` | 2 484 | 158 | `api/import_commit.php` | 913 | 62 |
| `einsatz_lib.php` | 192 | 19 | `assets/geo.js` | 229 | 14 |
| `einsatz_loeschen.php` | 92 | 3 | `assets/gpx.js` | 277 | 5 |
| `einsatz_verschieben.php` | 187 | 7 | `assets/schneiden.js` | 711 | 20 |
| `nachbearbeitung.php` | 387 | 17 | `assets/ortsdaten.js` | 519 | 19 |
| `nachbearbeitung_lib.php` | 371 | 34 | `assets/patient.js` | 382 | 14 |
| `import.php` | 321 | 24 | `assets/forms.js` | 170 | 5 |
| `validate_lib.php` | 1 273 | 55 | | | |
| `mission_fields.php` | 531 | 57 | | | |

**Summe 1 242 Verweise.** Drei Dateien tragen 370 davon (`einsatz_form.php`,
`ingest.php`, `diensttag_lib.php`); die Checkliste aus 7.2 ist an jedem der
20 HTTP-Einstiege des Stücks durchgegangen (12 Seiten, 8 `api/`).

**Was die Lesung ergeben hat, in einem Absatz.** Der Datenweg vom Gerät
bis zur Seite hält, was `CLAUDE.md` 4 verspricht, und zwar an den Stellen,
die es tragen: `ingest.php` weist Klartext-Ortsdaten mit `400 klartext` ab,
prüft Stück und Hülle über die Prüfschicht, zählt den Eingang mit
ausdrücklicher Merkmalsliste (das Muster, das F-R17-36 für die Anmeldung
empfiehlt), hält das Ersetzfenster an `created_at` und die Sperrliste für
beide Arten; jeder Schreibweg des Formulars, des Imports und des Schnitts
verlangt die Hülle und hält die Phasenzeiten des Browsers gegen die eigene
Rechnung, statt eine Hülle mit verrutschten Orten abzulegen; die Besitzprüfung
steckt in `einsatz_laden()` und `dt_laden()` und sonst in der Abfrage
(`user_id = ?` in jedem `SELECT`, `UPDATE` und `DELETE`, der ein Konto
betrifft); Spalten kommen aus dem Register, Tabellennamen aus Konstanten,
Werte an Platzhalter; CSRF steht vor jedem `POST`; der Export zieht seine
Schranke serverseitig (`NULL AS`, kein Blob, keine Spur ohne das Flag); die
Hüllen bindet der Browser an Art, Kennung und Platz und öffnet die gepackten
vor den Gerätestücken. Die vier Befunde liegen nicht im Schutz, sondern im
Verhalten: Eine Zeile, die ein Feld aus dem alten Spurformat liest, das es
seit Web 23 nicht gibt (F-R17-39, und das ist der Grund für „mittel" — der
GPX-Abruf fehlt auf Produktiv an jeder gepackten Spur, und der Reiter System
füllt sich), ein `use` mit zwei Namen zu viel (F-R17-38), eine
Papierkorb-Sperre im Import, die das Datum statt den Tag fragt (F-R17-40),
und eine Kennungsprüfung, die an der Zweigentscheidung vorbeigeht
(F-R17-41). Nichts davon öffnet fremde Daten oder verliert eigene.

**Die Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen — soweit das
Stück sie sieht:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-09 (mittel) | besteht: `einsatz_form.php` sagt unter dem Titel „Felder mit Schloss ver- und entschlüsselt dein Browser, alle anderen liegen lesbar auf dem Server" und verweist auf das Handbuch — dass eine getippte Adresse an Photon geht, steht weder dort noch in `CLAUDE.md` 4 oder `Technik.md` 4.98; `geocoder.js` und `ortsfeld.js` liest Stück 7, die Dokumente Stück 11 |
| F-R17-13 (mittel) | für den Server bestätigt: `ingest.php` nimmt nur Hüllen (`400 klartext`), die Spur liegt vom Eingang an verschlossen; der Klartext-Puffer auf dem Gerät ist Stück 8 und 9 (Lage H) |
| F-R17-28 (niedrig) | hier nicht sichtbar: Fristen auf der Uhr sind Stück 8; die Serverseite (Ersetzfenster 72 h, Sperrliste `deleted_refs`) hat keine Frist für geparkte Pakete und braucht keine |

**K-10 beantwortet** (3.4): gegenstandslos seit S11-05d — siehe die Zeile
dort. **Nr. 23 nur gelesen** (gehört zu Schritt 13): Seit S11 nimmt kein
Schreibweg des Servers eine Reanimationsart entgegen — Ingest, Formular,
Import und Schnitt bekommen die Reanimation nur in der Hülle, und der Server
öffnet sie nicht; `pruef_reanimationsart()` in `validate_lib.php` hat in diesem Stück
keinen Aufrufer mehr; der eine, der bleibt, ist `edbak_restore()` in
`backup_lib.php` (Stück 2, die Reanimation aus einer Sicherung). Geprüft
wird die Art sonst im Browser,
`EdOrtsdaten.einsatzZeiten()` gegen `RESUS_LABELS` ohne `beginn`. Ob der
Vertrag 3.3 damit stimmt, ist eine Frage an Stück 11, nicht an den Code.

**Aufräumposten (kein Befund):**

- **Karteileichen:** keine — 193 Funktionen der 14 PHP-Dateien mit
  Definitionen, jede außerhalb ihrer Datei genannt (`grep` gegen `server/`,
  `tools/`, `docs/`). Die Nennung in `docs/` zählt mit; wer schärfer zählen
  will, nimmt `docs/` heraus.
- **Eine tote Zelle:** `diensttag_loeschen.php` baut sein Posten-Array mit
  `null` an dritter Stelle und liest es mit `$klein ?? ''` — der Wert ist
  immer `null` (PHPStan `nullCoalesce.variable`). Entweder eine Kleinzeile
  hinein oder die Spalte heraus.
- **Ein ungenutztes `use`:** `einsatz_form.php`, der Rahmen
  `db_transaktion(…, function … use (…, $readField, …))` fängt `$readField`
  ein und ruft es nicht (PHPStan `closure.unusedUse`).
- **Eine Phase, die es nicht gibt:** `einsatz_form.php`, „Phase hinzufügen"
  rechnet `Math.min(last + 1, 10)`; das `<select>` kennt 2 bis 9, und nach
  einer Zeile mit Phase 9 fällt die neue auf Phase 2 statt auf 9. `9` statt
  `10`.
- **Ein doppelter Abruf:** `einsatz_form.php` holt `api/mission.php` beim
  Speichern ein zweites Mal (`ortSpurPunkte()`), obwohl `spurHolen()` die
  Antwort nach dem ersten Öffnen des Kartendialogs schon hält — zweimal die
  ganze Einsatzzeile samt Stücken je Speichern eines Einsatzes mit Spur.
  Eine Antwort für beide.
- **Register (Stück 10 prüft die Decke):** `ingest.php`,
  `diensttag_zusammenfuehren.php`, `api/day.php` und
  `api/import_commit.php` rufen `$pdo->beginTransaction()` direkt statt
  `db_transaktion()` — bei `ingest.php` mit Grund (die Wiederholschleife bei
  Gedrängel braucht den Rahmen selbst); `nachbearbeitung_lib.php` fragt
  `information_schema` von Hand (`nb_spalte_nullbar()`, `nb_moeglich()`)
  statt über `db_spalte_nullbar()`; `einsatz_form.php` ruft `fetch()` für
  zwei GETs ohne Token. Stück 10 sieht nach, wie die Registerzeilen sie
  zählen.
- **PHPStan, 14 gelesene Meldungen:** 5 sind die zwei Befunde
  (`tageszuordnung_lib.php` 231 bis 233 → F-R17-38; `einsatz.php` 44 →
  F-R17-39). Die übrigen 9: `diensttag_loeschen.php` 76 (die tote Zelle
  oben), `einsatz_form.php` 230 und 251 (M5-11: Gürtel und Hosenträger,
  Absicht), 512 (das `use` oben), `ingest.php` 509 (`isset` und `!== null`
  doppelt, harmlos) und 1268 (`?? false` auf gesetzter Variable, harmlos),
  `api/import_commit.php` 414 (`?? 0` auf vorhandenem Schlüssel, harmlos),
  `validate_lib.php` 448 und 449 (Rückfall für PHP vor 8.2, Absicht).
- **Kommentardurchgang:** 1 242 Fundstellen (Tabelle oben).

**Vorsortierung, was sie gebracht hat:** diesmal einen Befund. F-R17-39
stand als `offsetAccess.notFound` in der PHPStan-Liste, und erst die Lesung
hat daraus die Wirkung gemacht (welcher Zweig wann greift, was der
Wartungslauf tut, was die Probe misst); F-R17-38 stand als vier Meldungen
da. Die beiden anderen Befunde sind Verhaltensfragen über mehrere Blöcke
hinweg, die kein statisches Werkzeug sieht. Der Aufruferzähler hat nichts
gefunden, und das ist auch ein Ergebnis: Das Stück trägt keinen ungenutzten
Code.

#### 7.3.5 Stück 5 — Verwaltung und Admin (R17-09, 20.10.2026)

**Dateiliste, abgehakt — 31 Dateien, 18 322 Zeilen, jede von Fable
vollständig gelesen:** die 27 PHP-Dateien des Kerns (die Liste aus 7.3),
dazu die zwei `api/`-Einstiege und die zwei Skripte, die zu keinem anderen
Stück gehören: `api/rechtstext_vorschau.php` und `assets/rechtstext_vorschau.js`
(Vorschau der Rechtstexte), `api/rueckfrage.php` und `assets/rueckfrage.js`
(die Konto-Rückfrage, die auch die Kontoseite bedient). `schluessel.js`,
`schluessel_teile.php`, `zweitfaktor_teile.php`, `totp_lib.php`,
`passkey_lib.php`, `rueckweg_lib.php` sind Stück 2 und 3 und dort gelesen;
`kopplung.js`, `dialog.js`, `kartenfilter.js`, `ortsfeld.js` und die
Zip-Bibliothek sind Stück 7; `adminbackup_lib.php`, `komplett_lib.php`,
`sicherungsziel_lib.php` sind Stück 2 und 6. `geraetemodelle.php` ist eine
erzeugte Tabelle (325 Teilenummern) und auf Form gelesen.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `einstellungen.php` | 5 460 | 380 | `suche.php` | 1 282 | 61 |
| `konten_einstellungen_lib.php` | 448 | 15 | `zeitraum.php` | 953 | 72 |
| `admin_users.php` | 907 | 38 | `apk.php` | 85 | 6 |
| `admin_user.php` | 1 544 | 88 | `apk_lib.php` | 85 | 3 |
| `admin_demo.php` | 350 | 19 | `geraetemodelle.php` | 346 | 1 |
| `admin_installation.php` | 273 | 18 | `geraetemodelle_lib.php` | 198 | 4 |
| `admin_komplettsicherung.php` | 484 | 24 | `statistik_lib.php` | 295 | 19 |
| `admin_protokoll.php` | 301 | 5 | `betrieb_statistik.php` | 830 | 52 |
| `admin_rechtstexte.php` | 275 | 14 | `erststart_karte.php` | 165 | 6 |
| `admin_sicherungen.php` | 762 | 42 | `blatt_dialog.php` | 100 | 5 |
| `admin_sicherungsziele.php` | 742 | 41 | `rueckfrage_dialog.php` | 96 | 1 |
| `stammdaten_ui.php` | 506 | 42 | `api/rechtstext_vorschau.php` | 70 | 2 |
| `rechtstexte_lib.php` | 483 | 10 | `api/rueckfrage.php` | 94 | 1 |
| `rechtstext_seite.php` | 123 | 5 | `assets/rechtstext_vorschau.js` | 112 | 2 |
| `papierkorb.php` | 309 | 16 | `assets/rueckfrage.js` | 255 | 6 |
| `trash_lib.php` | 389 | 14 | | | |

**Summe 1 012 Verweise.** `einstellungen.php` trägt 380 davon allein — die
Datei ist mit 5 460 Zeilen die längste des Bestands und führt vier Reiter
mit 24 Handlungszweigen; die Checkliste aus 7.2 ist an jedem der 17
HTTP-Einstiege des Stücks durchgegangen (15 Seiten, 2 `api/`; dazu
`rechtstext_seite.php` hinter den vier Hüllen `impressum.php`,
`datenschutz.php`, `nutzungsbedingungen.php`, `avv.php`, ohne Anmeldung
und mit der Rolle aus der Datenbank).

**Was die Lesung ergeben hat, in einem Absatz.** Das Stück hält die
Rollen- und Besitzgrenzen, die es verspricht: Jede Admin-Seite steht hinter
`require_admin()` oder `require_betreiberin()`, jede Handlung hinter
`csrf_check()`, jeder Schreibweg auf Stammdaten, Geräte und Papierkorb
trägt `user_id = ?` in der Anweisung, und die Dateinamen der Sicherungen
laufen durch `komp_name_gueltig()`, `edbak_paketname_gueltig()` und
`protokoll_archiv_name_gueltig()`, bevor ein Pfad entsteht; das APK wird
nicht geprüft, sondern aus der Liste gewählt. Die Rechtstexte maskieren
den ganzen Text, bevor sie Struktur erkennen, und lassen Links nur über
eine Positivliste zu; die Vorschau rendert auf dem Server und zählt im
Topf `rt_vorschau`. Suche und Zeitraum schicken keinen Suchbegriff an den
Server, halten den Filterzustand im Fragment und maskieren jede Zelle über
`EdHtml.escape`. Passwort- und Adresswechsel verlangen das aus dem Passwort
abgeleitete Token, die Kontolöschung das Passwort selbst, und der
Passwortwechsel hebt den Sitzungszähler in derselben Transaktion. Die vier
Befunde liegen daneben: eine Handlung des Zweitfaktors, die das Muster von
E-SR-31 nicht mitbekommen hat (F-R17-43), eine Monatsprüfung, die zwei
Ziffern genügen lässt (F-R17-44), ein CSV-Schreiber ohne Formelschutz an
einem Wert, den das Gerät setzt (F-R17-45), und eine Sperrliste des
Demo-Kontos, die drei Handlungen vergisst (F-R17-42). Nichts davon öffnet
fremde Daten; F-R17-43 ist das einzige, das die Zusage „Zweitfaktor" berührt
— und nur mit einer Sitzung, die schon erbeutet ist.

**Die Altbefunde und Posten des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-07 (mittel), Nr. 371 | **bestätigt:** `admin_user.php` kennt gegenüber einem BetreiberIn-Konto zwei Riegel, den Rollenwechsel und „die letzte BetreiberIn"; `konto` (Adresse ändern), `konto_status` (sperren) und `user_delete` (mit frischem Code) laufen für ein zweites BetreiberIn-Konto bei Rolle `admin` durch. `admin_users.php` prüft beim Anlegen die Rolle, nicht beim Bearbeiten. Nr. 371 bleibt offen für 12b |
| F-R17-08 (mittel) | **bestätigt:** `ZF_FRISCH_HANDLUNGEN` hält acht Handlungen (`schluessel`, `schluesselblatt`, `rollenwechsel`, `totp_zuruecksetzen`, `user_delete`, `totp_ausschalten`, `passkey_anlegen`, `passkey_entfernen`); `admin_komplettsicherung.php` gibt die ganze Datenbank als entsiegeltes SQL (`art=klar`) nach `require_betreiberin()` und `csrf_check()` heraus, mit Protokolleintrag, ohne frischen Code — das Löschen eines Kontos verlangt ihn, der Dump aller Konten nicht |
| F-R17-02 (hoch) | unverändert, auch von der Verwaltungsseite her: `admin_sicherungen.php` `freigeben` ruft kein `protokoll()` und keine Mail, `freigeben` steht nicht in `ZF_FRISCH_HANDLUNGEN` |
| F-R17-06 (mittel) | unverändert: `admin_user.php` `konto` schreibt die Adresse ohne Nachweis und ohne Bestätigung an die alte; der Weg des Profils (`einstellungen.php`, Token plus Link an die neue und Warnung an die alte Adresse) ist der bessere und gilt nur für die eigene |
| Nr. 381 | **bestätigt:** `suche.php` holt den ganzen Bestand (`api/suchindex.php`), entschlüsselt jeden Einsatz (`EdPat.listeLaden(missions)`), baut je Einsatz den Heuhaufen und zeigt dann 200; `zeitraum.php` ebenso über `api/range.php` mit `entschluesselePat()` über alle und 200 Zeilen in der Tabelle; die Karte zeichnet jeden Pin. Der Weg steht im Backlog (E-S11-27) |

**Nur gelesen, kein Befund:** `admin_protokoll.php` zeigt dem Support über
`?konto=<id>` die Anmeldeadresse jedes Kontos als Pille, auch die der
Admins und BetreiberInnen, deren Kontoseiten ihm `admin_user.php`
vorenthält — dieselbe Auskunft tragen die Zeilen des Protokolls über
`urheber_user_id` ohnehin; eine Zeile für die Berechtigungsmatrix
(`Technik.md` 4.99p), Stück 11. `apk.php` liefert ohne CSRF per `GET`, was
nichts ändert (M3-11), und mit `Cache-Control: private`. Die Rückfrage
(`api/rueckfrage.php`) verschiebt nur Fristen und begründet, warum die
Sitzung reicht.

**Aufräumposten (kein Befund):**

- **Karteileichen:** keine — 140 Funktionen der 15 PHP-Dateien mit
  Definitionen, jede genannt (`grep` gegen `server/`, `tools/`, `docs/`);
  18 davon nur in der eigenen Datei (die Helfer der Seiten `admin_users.php`,
  `betrieb_statistik.php`, `rechtstexte_lib.php`), 27 sind JavaScript in
  `suche.php`, `zeitraum.php` und `einstellungen.php`.
- **Ein toter Zweig:** `einstellungen.php`, Standortseite, `if (!$sdBases)`
  mit der Meldung „Noch kein Standort verfügbar" — die Seite setzt
  `$sdBases = [$seiteB]` zwei Zeilen darüber, der Zweig ist nie wahr
  (PHPStan `booleanNot.alwaysFalse`). Heraus.
- **Ein Vermerk vor dem Schreiben:** `einstellungen.php`, `base_save` ruft
  `erststart_erledigt($userId, ERSTSTART_STANDORT)` VOR dem `INSERT IGNORE`
  — eine Dublette, die nichts anlegt, hakt den Erststart-Schritt trotzdem
  ab. Den Aufruf hinter `$sdNeuId()`.
- **Drei Handgriffe ohne Treffer:** `toggle`, `rename` und `delete` in
  `einstellungen.php` melden „Status geändert.", „Bezeichnung gespeichert."
  und „Gerät gelöscht." auch dann, wenn `WHERE id = ? AND user_id = ?`
  keine Zeile getroffen hat (`rowCount()` wird nicht gelesen). Dasselbe
  Muster wie bei den Stammdaten, nur ohne deren Dublettenmeldung.
- **Veraltete Rückgabeformen:** drei `@return`-Annotationen nennen nicht
  alles, was zurückkommt — `totp_zustand()` (`geraete`),
  `statistik_zeitraum()` (`bis_utc`, nur bei `eigen`),
  `edbak_auftrag_schub()` (`meldungen`); sieben PHPStan-Meldungen, kein
  Fehler im Lauf. Nachziehen, sonst meldet die nächste Vorsortierung dieselben
  sieben wieder.
- **Register (Stück 10 prüft die Decke):** `einstellungen.php` liest
  `$_GET['t']` nach `isset()` noch einmal mit `??` (eine Zeile ohne Wirkung);
  `admin_komplettsicherung.php` fragt `$error === null` in einem Block, der
  nur mit `$error === null` erreichbar ist. Beides PHPStan, beides harmlos.
- **PHPStan, der Rest:** 201 Meldungen in den Dateien des Stücks, 181
  `variable.undefined` (`$userId`, `$userRole`, `$row`, `$patReady` aus
  `auth_guard.php`, Rauschen); 20 gelesen — 0 Befunde, 7 die Rückgabeformen
  von oben, 10 Absicht (die leeren Konstanten `CONNECT_IQ_URL` und
  `PLAY_TEST_URL`, Weichen, die heute immer gleich entscheiden), 2
  Fehlalarme (`rt_html()` ändert `$absatz` und `$liste` über `&` in
  Schließungen, PHPStan sieht die Zuweisung nicht), 1 der tote Zweig.

### 7.4 Vorsortierung — Mittel und Grenzen

Nach E-R17-16 örtlich und nicht im Repositorium. Geplant: PHPStan (Stufe
nach Probe) oder Psalm für ungenutzte Funktionen und tote Zweige unter
`server/`; `vulture` für `tools/`; Android Lint über `appbau.sh` oder
`gradlew lint`; die Compiler-Warnungen der Uhr, falls das Connect-IQ-SDK im
Container steht (Prüfdokument 1 sagt: bisher nicht). Dazu eigene Zähler für
den Kommentardurchgang (Muster für R-, E-, F-, Nr.- und Fassungsverweise je
Datei). Grobzählung am 20.10.2026, Orientierung und kein Prüfwert: rund
3 000 Treffer unter `server/`, 90 in `watch/`, 530 in `android/`, 1 100 in
`tools/` und `.github/` — das Muster trifft auch Fassungsköpfe
(`version.php` ist seiner Natur nach Geschichte) und Wörter, die zufällig
so aussehen. Was ein Mittel lieferte und was es nicht sieht, steht je
Stück in 7.3 und im Prüfdokument 2 und 5.

**Gefahren für Stück 2 (R17-06, 20.10.2026):** PHPStan 2.3.1 als Phar
im Arbeitsverzeichnis, Stufe 4, über `server/` ohne `vendor/`, `assets/`,
`demo/`, `doku/` — 506 Meldungen insgesamt, 88 in den Dateien des Stücks
(Aufschlüsselung in 5 und 7.3.2). Die Stufe ist die höchste, die ohne
Bootstrap-Datei und ohne Typangaben am Altbestand lesbar bleibt; höher
meldet sie Typen, die der Code bewusst offen lässt. **Nicht gefahren:**
`vulture` — es liest Python, nicht PHP, und ist für `tools/` (Stück 10)
aufgehoben; Android Lint und Uhr-Compiler kommen mit den Stücken 8 und 9.
Die eigenen Zähler (`grep` über die Funktionsnamen des Stücks gegen
`server/`, `tools/`, `docs/`; das Verweismuster je Datei) stehen in 7.3.2
mit Zahl. Die Zählung ist eine Liste, kein Spürsinn: Sie findet keine
Funktion, die über eine Zeichenkette gerufen wird, und zählt einen
Fachbegriff mit, der wie ein Verweis aussieht.

**Gefahren für Stück 3 (R17-07, 20.10.2026):** dieselbe PHPStan-Konfiguration;
31 Meldungen in den Dateien des Stücks (22 Rauschen, 9 gelesen, 0 Befunde;
Aufschlüsselung in 7.3.3). Dazu der Aufruferzähler über 169 Funktionen
(2 Karteileichen) und ein `grep` gegen die Probe `tools/proben/raten/` und
`docs/Technik.md` 5e.2, der F-R17-36 gegen Prüfmittel und Doku gehalten hat.

**Gefahren für Stück 4 (R17-08, 20.10.2026):** dieselbe PHPStan-Konfiguration;
101 Meldungen in den Dateien des Stücks (87 Rauschen, 14 gelesen, 5 davon
die zwei Befunde F-R17-38 und -39; Aufschlüsselung in 7.3.4). Dazu der
Aufruferzähler über 193 Funktionen (0 Karteileichen), `php -r` für das
Verhalten eines `use` mit undefinierter Variable, `git log -S` für das
Alter von F-R17-39 und ein `grep` gegen die Probe `tools/proben/gpx/`,
`demo_lib.php` und `assets/wartungslauf.js`, der gezeigt hat, warum die
Probe grün ist, während Produktiv den Fehler hat.

**Gefahren für Stück 5 (R17-09, 20.10.2026):** dieselbe PHPStan-Konfiguration;
201 Meldungen in den Dateien des Stücks (181 Rauschen, 20 gelesen, 0 Befunde;
Aufschlüsselung in 7.3.5). Dazu der Aufruferzähler über 140 Funktionen
(0 Karteileichen; 18 nur in der eigenen Datei genannt, das ist bei
Seitenhelfern der Regelfall), `php -r` für den Monat 13 (F-R17-44), und
vier `grep`-Gegenproben, die je eine Liste gegen die Handlungen halten, die
sie decken soll: `ZF_FRISCH_HANDLUNGEN` gegen die `zf_`-Zweige (F-R17-43),
`DEMO_GESPERRT` gegen die Handlungen von `admin_user.php` (F-R17-42),
`fputcsv` gegen die Herkunft seiner Zellen (F-R17-45), die Monatsprüfung
von `api/range.php` gegen die von `zeitraum.php` (F-R17-44). Alle vier
Befunde sind Listen, die eine Zeile vergessen — der Verweiszähler und
PHPStan finden so etwas nicht, nur die Lesung gegen die Gegenliste.
