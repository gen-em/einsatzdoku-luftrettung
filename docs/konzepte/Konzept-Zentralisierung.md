# Konzept — Zentralisierung: eine Stelle je Sache

**Rahmenplan:** Schritt 15 (R83). **Backlog:** 202 (Sammelnummer), 57
(Tagesübersicht baut ihre Tabelle zweimal). **Reihenfolge:** Kette II → 16 →
**15** → 10c. **Messbasis:** `origin/main` `862ca7f`, Web 20.25.0, gemessen am
20.09.2026 — nach dem Merge von 10a (PR #50) **und** 10b (PR #57), beide
gemeinsam nachgemessen; **nachgeprüft** an `fd99989` (Web 20.26.2, nach dem
Merge von Schritt 16) — Schlüsselzahlen unverändert, siehe 1.0. **Modell:** Konzept Fable (R14), Umsetzung Opus, **kein
Fable-Schritt, kein Mockup**. **Ablage:** dieses Dokument
(`docs/konzepte/Konzept-Zentralisierung.md`), Prüfdokument daneben
(`Pruefdokument-Zentralisierung.md`, entsteht mit AP1 und wächst je Paket).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 22.09.2026 — **AP1 bis AP8 erledigt** (zuletzt Web 20.34.0); **AP9 vermessen und angehalten**, **AP10 gebaut bis auf die AP9-abhaengigen Teile**. Freigegeben (Auftraggeber, 20.09.2026). Umfang am 20.09.2026 bestätigt; vier Entscheidungen nach der Messung vom Auftraggeber getroffen (E-ZE-01 bis -04, „alles wie empfohlen"); F-ZE-1 bis F-ZE-6 gelten mit der Freigabe. |
> | Entschieden | E-ZE-01 bis E-ZE-08 (Gespräch), E-ZE-10 bis E-ZE-24 (Nachmessen), **E-ZE-25** (Ultracode), **E-ZE-26** (`assets/format.js` in AP7, sechste Ausnahme), **E-ZE-27** bis **E-ZE-30** (die vier Zielzahlen von AP8, die begruendet ueber null enden: Z29 bei 2, Z37 bei 4, Z34 bei 5, Z36 bei 1) — alle 22.09.2026; E-ZE-09 ist nicht vergeben |
> | Offen | nichts im Konzept. **F-ZE-1 bis F-ZE-6** (Abschnitt 2.3) sind mit der Freigabe vom 20.09.2026 entschieden. Außerhalb des Konzepts: die Einschübe (Abschnitt 8, 9) sind noch nicht eingespielt |
> | Umsetzung | zehn Arbeitspakete, eines nach dem anderen, Zweig `claude/eager-euler-jlfi9i` (von `main` `fd99989`). **AP1 erledigt** (20.09.2026), **AP2** und **AP3 erledigt** (21.09.2026), **AP4 erledigt** (21.09.2026), **AP5**, **AP6** und **AP7 erledigt** (22.09.2026). **Voraussetzungen:** Kette II bis M1 und auf `main` — **offen, am 20.09.2026 nachgeprüft** (`claude/fervent-dirac-xirsqw` steht bei `af866c1`, AP4 gebaut, Beweislauf offen; 35 Commits nicht in `main`; M1 folgt auf AP4, AP5 und AP6 und ist ein Schritt der Betreiberin: Tag und Freigabe). Der Auftraggeber hat AP1 am 20.09.2026 freigegeben und wartet Kette II für AP2 ab — AP1 fasst `server/` nicht an. Schritt 16 gemergt — **erfüllt und nachgeprüft** (`main` `fd99989`, Web 20.26.2; `sitzung_lib.php` besteht, `sitzung_ablage()` wird in `db.php` Z. 586 und `install.php` Z. 144 gerufen — genau zwei Aufrufstellen, der Rest sind Kommentare). **Merge nach `main` nur auf Ansage** (löst einen Staging-Deploy aus) |
> | Nummern | Dieses Konzept vergibt **keine** Rahmenplan-Fassung, **keine** Backlog-Nummer und **keine** Version. Einschübe in Abschnitt 8 und 9 übernimmt die einspielende Instanz |
> | Steuerungsdokumente | **Seit 21.09.2026 auf `main`** (Kette II gemergt, PR #65/#68): Rahmenplan **Fassung 102**, Backlog mit der **Spanne 250–259 für Schritt 15** — 250–253 sind die vier Einträge aus Abschnitt 9 dieses Konzepts, **254–259 sind frei als Reserve für Funde der Umsetzung**. Das Konzept selbst ist mit Fassung 97 auf `main` eingespielt worden (unverändert; die fortgeschriebene Fassung liegt auf dem Umsetzungszweig). **Damit ist die Grundlage von AP1-f entfallen** — siehe dort; zur Neubewertung vorgelegt |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP1 Zählmittel, Register, Gegenprobe Paket 1 | **erledigt** 20.09.2026 | keine (nur `tools/` und `docs/`) | Selbstprobe **29 von 29** · Register **38 Zeilen, 0 über der Decke** · Eichung `error_log(` **77 in 32**, `session_start(` **9 in 9** · Bestand **133 PHP / 40 JS** · Gegenprobe Z30–Z33 **4× 0** · `php -l` **133/0** · Wortliste **0/0** · Vollständigkeit **398** (unverändert) · Kontraste **22/0** · Kettenaufrufe **30/0** · CSP **0** · Migrationsregister **0** |
> | AP2 Konfiguration und Sitzung | **erledigt** 21.09.2026 | **Web 20.27.0** (Neben: zwei neue Funktionen, keine Migration) | Z01 **9 → 1** · Z02 **2 → 1** · Z03 **7 → 1** · Z04 **46 → 0** · `konfig_lib` **13/13** · Cookie-Parameter **16 Zellen, 0 Abweichungen** · Ladezyklus **7/7** · Sitzungshärtung **0 Befunde, Selbstprobe 12/12** · Register **38 Zeilen, 0 über der Decke** · `php -l` **134/0** · Wortliste **0/0** · Vollständigkeit **398** · `error_log(` **77** (unverändert, E-ZE-05). **Im Browser gegen eine laufende Anlage:** Bilderlauf **496 Bilder, 0 Überlauf, 0 Konsolenfehler** · Prüfliste **A-1 bis A-12 gefahren** · F-ZE-2 **40 anonyme Abrufe → 0 neue Sitzungsdateien** · Härtung wirkt (untergeschobene Kennung verworfen) · A-9 **8/8** · A-12 **7/7** · Abmelde-Probe erfüllt · Kopplungsprobe **76/0** · Ratenprobe **50/0** · **Nachtrag: fünf Proben repariert (448 Erwartungen, 0 offen; Nr. 257)** (der eine Befund war nicht von AP2 und ist behoben, Nr. 254) |
> | AP3 API-Eingang und Flash | **erledigt** 21.09.2026 | **Web 20.28.0** (Neben: drei neue Funktionen, keine Migration) | Z05 **12 → 1** · Z06 **17 → 0** · Z07 **11 → 0** · Z08 **3 → 0** (Konzept erwartete 1, AP3-d) · Z09 **22 → 0** · `error_log(` **77** (unverändert, E-ZE-05) · Register **38 Zeilen, 0 über der Decke**, Selbstprobe **34/34** · `php -l` **135/0** · Wortliste **0/0/0** · Vollständigkeit **398** · Kettenaufrufe **0** · Sitzungshärtung **0**. **Gegen eine laufende Anlage:** Eingangsprobe **46 Zellen, 46 erfüllt** (davor **27 von 46** — die 19 Abweichungen sind F-ZE-5, aufgeschlüsselt im Protokoll; **beide Reihenfolge-Zellen schon davor grün**) · Flash-Probe **11/11** · Kreisläufe edbak **328 771/0/21**, csv **10 922/0/1 271** (Zahl für Zahl wie davor) · Ingestprobe **83/0** · Kopplungsprobe **76/0** · Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe **35/0** · Ratenprobe **50/0**. **GPX-Probe 95/4 — vor und nach dem Paket gleich**, Ursache ist der Demo-Reset dieser Anlage (Nr. 259) · Bilderlauf **496 Bilder, 0 Überlauf, 0 Konsolenfehler** |
> | AP4 Datenzugriff klein | **erledigt** 21.09.2026 | **Web 20.29.0** (Neben: neue Funktionen, keine Migration) | Z10 **27 → 2** (AP4-c: `jobs.php` bleibt, Gerätevertrag) · Z11 **7 → 0** · Z12 **12 → 2** (Startwert von 13 berichtigt, AP4-d; zweite Ausnahme AP4-b) · Z13 **4 → 0** · Z14 **9 → 5** (AP4-e, kein vierter Helfer) · Z15 **57 → 54** · Z38 **77 → 75** (AP4-g) · Register **38 Zeilen, 0 über der Decke**, Selbstprobe **34/34** · `php -l` **136/0** · Wortliste **0/0/0** · Vollständigkeit **398** · Kettenaufrufe **0** · Sitzungshärtung **0** · CSP **0** · Migrationsregister **0**. **E-ZE-17-Beleg: 7 Stellen in 5 Dateien nachgelesen, 0 brauchen `?PDO $pdo`.** **Gegen eine laufende Anlage:** Kreisläufe edbak **328 771/0/21**, csv **10 922/0/1 271** (Zahl für Zahl wie davor) · Ingestprobe **83/0** · Kopplungsprobe **76/0** · Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe **35/0** · Ratenprobe **50/0** · Wiederherstellungs-Probe **111/0** · Anteilprobe **55/55** · Versandprobe **135/0** · Klickprobe **48/48** · Bilderlauf **496 Bilder, 0 Überlauf, 0 Konsolenfehler** |
> | AP5 Transaktion und Kindtabellen | **erledigt** 22.09.2026 | **Web 20.30.0** (Neben: fünf neue Funktionen, keine Migration) | Z16 **33 → 9** (neun namentliche Ausnahmen, H-ZE-4 ausgesetzt, AP5-c) · Z17 **30 → 0** · Bauformen mit dem Tokenizer ausgezählt: **19 / 12 / 2**, dazu **42 rollBack, 14 mit Wache** (AP5-a) · Probe für `db_transaktion()` **10/10** (einschließlich der drei Verschachtelungsfälle) · **Messstand Import: 41,78 / 41,31 s gegen 41,71 / 41,47 s davor** · Kreisläufe edbak **328 771/0/21**, csv **10 922/0/1 271** · Ingestprobe **83/0** · Kopplungsprobe **76/0** · Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe **35/0** · Wiederherstellungs-Probe **111/0** · Ratenprobe **50/0** · Klickprobe **48/48** · `php -l` **136/0**. **Ein latenter Fehler behoben** (einsatz_form.php: rollBack auf eine bereits bestätigte Transaktion) |
> | AP6 Spaltenregister `missions` | **erledigt** 22.09.2026 | **Web 20.31.0** (Neben: vier neue Funktionen, keine Migration) | Z18 **12 → 3** (Ziel war ≤ 4; drei Abbildungen bleiben, jede mit Probe — AP6-b) · Register **41 Spalten, 9 Zwecke, 0 ohne Zweck und ohne Grund**, Schema↔Register **0/0** · erzeugte gegen eingefrorene Listen **9 von 9 gleich**, Zeichen für Zeichen · Vollständigkeitsprobe **38/22/30 Schlüssel, 0 fehlend, 0 überzählig, 0 tote Ausnahmen**, Selbstprobe **16/16** · **Byte-Vergleich gegen den Stand VOR AP6 (Web 20.30.0, `7a55192`): 447 291 Bytes, gleiche SHA-256** · `Export-Format.md`/`Backup-Format.md` **0 geänderte Zeilen** · Wegprobe **34/0** · Kreisläufe edbak **328 771/0/21**, **edbak-alt (altes Backup mit Schlüssel `manual`) 287 852/0/795**, csv **10 922/0/1 271** · Ingestprobe **83/0** · Kopplungsprobe **76/0** · Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe **35/0** · Ratenprobe **50/0** · Wiederherstellungs-Probe **111/0** · Klickprobe **48/48** · `php -l` **136/0** · Wortliste **0/0/0** · Vollständigkeit **398** · Kontraste **22/0** · Kettenaufrufe **45/0/0** · Register **38 Zeilen, 0 über der Decke**, Selbstprobe **34/34** · Bilderlauf **496 Bilder, 62 Kontaktbögen, 0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen** |
> | AP7 Zeit und Zahl (PHP) | **erledigt** 22.09.2026 | **Web 20.32.0** (Neben: zwei neue Dateien, keine Migration), **20.32.1** (Korrektur: „263 KB MB”, Problem 9) | Z19 **42 → 0** · Z20 **2 → 1** · Z21 **27 → 0** · Z22 **18 → 5** (AP7-c) · Z23 **10 → 2** (AP7-d) · Z24 **20 → 2** · Z25 **9 → 2** · Z26 **67 → 3** (AP7-e/-f) · Z27 **4 → 0** · **205 Stellen umgestellt, 15 namentlich stehengelassen, 38 Dateien, 36 require ergänzt, 2 entfernt** · Zeichengleichheit je Funktion nachgerechnet: `groesse_text` **3 017 Werte/0**, `groesse_kurz_text` **3 017/0**, `zahl_text` **28/0**, `prozent_text` **6 030/0**, `zeit_relativ` **10 811 Zeitpunkte/0**, `iso_utc` **5 000/0**, `EdFormat.groesse` gegen PHP **2 014/0** · ein Gegenleser allein **4 420 679 Vergleiche/0** · `php -l` **137/0** · Wortliste **0/0/0** · Vollständigkeit **398** · Kettenaufrufe **45/0/0** · Register **38 Zeilen, 0 über der Decke**, Selbstprobe **34/34** · Spaltenregister **16/16, 0** · Ingestprobe **83/0** · Kopplungsprobe **76/0** · Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe **35/0** · Ratenprobe **50/0** · Wiederherstellungs-Probe **111/0** · **Formvergleich 496 von 496 Seiten formgleich, 0 abweichende Schreibweisen** · **Klickprobe 48/48, 0 verfehlt** · Kreislauf **edbak 328 771/0**, **edbak-alt 287 852/0**, **csv 10 922/0** — je dieselbe Zahl wie vor dem Paket. **33 Agenten, 4,1 Mio Token** (E-ZE-25); die Gegenleser fanden **2 Fehler und 13 Nachlässigkeiten**, eine **zehnte Stelle fand erst der Kreislauf nach dem Commit** (Problem 9) |
> | AP8 JavaScript | **erledigt** 22.09.2026 — in sechs Unterpakete geschnitten (AP8a bis AP8f) | — | **Vermessung:** 7 lesende Agenten, 1,04 Mio Token, 308 Werkzeugaufrufe, 0 Dateiaenderungen · CSP-Schritt **bereits 0** (115 `<script>`-Stellen, 27 inline, 0 ohne Nonce; zweimal unabhaengig gemessen) · drei bestehende Fehler gefunden (Backlog Nr. 268–270) |
> |  AP8a `EdKarte.anlegen()` | **erledigt** 22.09.2026 | **Web 20.33.0** (Neben: eine neue Funktion, keine Migration) | Z35 **4 → 0** · Klickprobe **48/48** · Formvergleich **496 Seiten, 41 Befunde — alle auf dem CSP-Verstossprotokoll, drei Zeilen vom 21.09.2026, keine neue** · vier Karten im Browser: Kacheln, Umschalter, Vollbild, Groessenknopf nur auf der Tagesuebersicht, **0 Konsolenfehler** · Bilderlauf **496 Bilder, 0 Ueberlauf, 0 Konsolenfehler, 0 Knopfhoehen** · Register **38 Zeilen, 0 ueber der Decke**, Selbstprobe **34/34** |
> |  AP8b/c/e `EdApi`, `EdHtml.meldung()` | **erledigt** 22.09.2026 | **Web 20.34.0** (Neben: eine neue Datei, vier neue Funktionen, keine Migration) | Z28 **15 → 1** · Z29 **5 → 2** (E-ZE-27) · Z37 **8 → 4** (E-ZE-28) · 26 Stellen in zehn Dateien · `EdHtml.meldung()` gegen `ui_meldung_markup()`: **200 von 200 Faellen gleicher DOM-Baum** (160 auch quellgleich; die 40 unterscheiden sich nur in `&#39;` gegen `&#039;`, was `EdHtml.escape` seit Baustein B7 so erzeugt) · **20 Agenten, 2,38 Mio Token**, die Gegenleser fanden **23 Maengel, einen schwer** — und der war echt |
> |  AP8d `EdFormat`-Ausbau | **erledigt** 22.09.2026 | (in Web 20.34.0) | Z34 **14 → 5** (E-ZE-29) · 14 Definitionen in fuenf Dateien, dazu drei ungezaehlte Inline-Rechnungen und eine sechste km-Fassung, die erst eine Gegenprobe ueber das Muster der RECHNUNG fand (`luftlinie.js`, **57 143 Werte zeichengleich**) · drei sichtbare Aenderungen, je gemessen: zweistellige Minute **231/1441 (16,0 %)**, kein „60min“ mehr **720/86 401 (0,83 %)**, Tausenderpunkt der Streckensumme · **14 Agenten, 1,74 Mio Token**, 18 Maengel |
> |  AP8f `EdPat.listeLaden()` | **erledigt** 22.09.2026 | (in Web 20.34.0) | Z36 **4 → 1** (E-ZE-30) · drei Anzeigeseiten, `einstellungen.php` begruendet drausen · im Browser nachgemessen: **6/6, 96/96, 96/96 Eintraege entschluesselt, 0 unlesbar, 0 Konsolenfehler** |
> | AP9 Nr. 57 — eine Einsatztabelle | **vermessen, nicht gebaut** 22.09.2026 — wartet auf vier Entscheidungen | — | **Vermessung:** 4 lesende Agenten, 0 Dateiaenderungen · **Die Praemisse stimmt nicht:** `cap_gate` erreicht die Einsatztabelle NICHT (`mf_tagesspalten()` nimmt keinen Parameter, `mf_gates_erfuellt()` hat zwei Aufrufer, beide in `einsatz_form.php`; `capabilit` kommt in `index.php` nullmal vor) — **69 von 69 Diensttagen tragen heute die Windenspalte** · dazu vier Funde ausserhalb beider Dokumente, darunter eine Sortierung, die den Nachtdienst kippt |
> | AP10 Abschluss und Übergabe an 10c | **gebaut** 22.09.2026, bis auf die AP9-abhaengigen Teile | keine (nur `.github/`, `tools/`, `docs/`, `CLAUDE.md`) | Stufe-1-Schritt „Zentralisierung“ eingehaengt, **einmal absichtlich rot gesehen** (zwei Zeilen ueber der Decke, Rueckgabewert 1) und wieder gruen (0 ueber der Decke, Rueckgabewert 0) · Kettenaufrufe **0 Befunde, 0 ungeprueft** · `CLAUDE.md` 4 und `docs/Technik.md` 4.98a tragen die Regel · **Uebergabezahl `error_log(`: 75**, nicht 77 — die Rechnung steht im Protokoll |

---

## 0. Auftrag und Umfang

Im Web-Teil (`server/`, ohne `assets/vendor/` und `vendor/`) bekommt jede Sache
**eine** Stelle — nach dem Vorbild von `mission_fields.php`. Android und Uhr
sind ausdrücklich nicht Gegenstand. Grundlage ist der Befund aus Backlog
Nr. 202 (16.09.2026, sechs Pakete); seine Zahlen gelten hier nur als
Hypothese und sind in Abschnitt 1 **neu gemessen**.

**Warum vor 10c:** 10c baut Banner, Protokollseite, Fehlerbehandler,
Support-Rolle, Zweitfaktor, Health-Endpunkt, Betriebslage und Bounce-Postfach.
Jedes davon trifft nach diesem Schritt je Sache eine Stelle statt vier —
Abschnitt 6 nennt sie je 10c-Arbeitspaket.

**Grundregel (E-ZE-10):** Dieser Schritt ändert **kein Verhalten**. Wo Kopien
auseinandergelaufen sind, nennt das Konzept die Fassung, die gilt, und jede
sichtbare Folge steht hier als Entscheidung. Es gibt **zehn** benannte
Ausnahmen: Nr. 57 (E-ZE-01, entschieden), „heute" in der App-Zeitzone
(F-ZE-1), lesende Seiten starten keine Sitzung ohne Cookie (F-ZE-2), die
Fehlerschlüssel des API-Eingangs (F-ZE-5), die Altersangabe auf Betrieb →
Updates (E-ZE-23), die Größenangabe der heruntergeladenen Sicherungsdatei
(E-ZE-26) und — alle vier am 22.09.2026 aus AP8 — **der einheitliche
Satzbau der Fehlermeldungen** (AP8b-a), **die zweistellige Minute einer
Dauer**, **das Verschwinden von „60min"** und **der Tausenderpunkt der
Streckensumme** (alle drei AP8d-a). Nichts sonst.

> **Die Liste ist am 22.09.2026 von fünf auf zehn gewachsen**, und jeder
> Zuwachs ist einzeln beschlossen worden: die sechste nach der Messung von
> AP7 (E-ZE-26), die siebte bis zehnte nach der Messung von AP8, als
> feststand, dass der Satzbau an fünf Mustern und die Dauer an drei
> Schreibweisen auseinandergelaufen war. Keine ist dazugerutscht. Wer eine
> ältere Quelle liest, findet dort „genau fünf" oder „sechs" — das ist
> der Stand bis zum 22.09.2026.
>
> **Die drei Schreibweisen-Ausnahmen sind gemessen, nicht geschaetzt:**
> 231 von 1441 Minutenwerten (16,0 %), 720 von 86 401 Sekundenwerten
> (0,83 %), und bei der Streckensumme genau eine Seite von dreien.

**Nicht Gegenstand:**

- **Der Log-Helfer** — die `error_log()`-Aufrufe stellt **10c AP3** um
  (E-P5c-12). Schritt 15 schreibt nur ihre **Zahl** fort (E-ZE-05).
- Android, Uhr, `docs/JSON-Vertrag.md`: Die Geräte-Endpunkte (`ingest.php`,
  `pair.php`, `auth_salt.php`, `jobs.php`, `gpx.php`) behalten Fehlerschlüssel
  und Antwortformen **zeichengleich**.
- Sitzungsbindung per Cookie-Token (Schritt 18, E-SA-09).
- Erklärtexte, Menü, Druckseiten (10c AP9).
- Die bewussten PHP-JS-Spiegelungen (`PHASE_LABELS`, `RESUS_LABELS`,
  `dt_art_symbole()`) — sie bleiben, wie im Rahmenplan festgehalten.
- **Gelaufene Migrationen** in `migration_lib.php` (E-ZE-04).
- **Paket 6 (Beifang)** aus Nr. 202 bekommt **kein** Arbeitspaket (E-ZE-07).
- `post_ende()` / Umleiten nach POST auf den Admin-Seiten (F-ZE-4).
- Die Deadlock-Behandlung in `ingest.php` (Nr. 210, Schritt 18).

## 1. Befund — gemessen an `origin/main` `862ca7f`, 20.09.2026

### 1.0 Messverfahren

Gezählt wurde mit einem Zerleger, der PHP-Dateien in drei Sichten teilt:
**Code ohne Kommentare** (Zeichenketten erhalten — für SQL-Literale), **Code
ohne Kommentare und ohne Zeichenketteninhalt** (für Funktionsaufrufe) und den
**HTML-Anteil** außerhalb von `<?php … ?>` (für Inline-JavaScript). JS-Dateien
ohne Kommentare. Bestand: **132 PHP-Dateien, 40 JS-Dateien**. **Geeicht** an
zwei bekannten Zahlen: `error_log(` **77 Aufrufe in 32 Dateien**,
`session_start(` **9 in 9 Dateien** — beide getroffen.

Das Messwerkzeug dieser Sitzung war Python und liegt **nicht** im
Repositorium. AP1 baut das bleibende Zählmittel mit dem PHP-Tokenizer (Bauform
wie `tools/sitzungshaertung/pruefen.php`) und **misst alle Startwerte nach**.
**Weicht eine Zahl ab, gilt die des Werkzeugs**; die Abweichung wird in diesem
Dokument mit Grund nachgetragen (Rahmenplan Abschnitt 9: gemessen, nicht
fortgeschrieben).

**Nachprüfung an `main` `fd99989` (Web 20.26.2, Schritt 16 gemergt), 20.09.2026:**
133 PHP-Dateien (neu: `sitzung_lib.php`), 40 JS-Dateien. Unverändert:
`error_log(` 77 in 32 Dateien · `session_start(` 9 in 9 · `config.php`-Lesestellen
7 in 5 · `$CFG`-Zugriffe 44 in 11 · `app_state`-SQL 34 in 15 ·
`beginTransaction(` 33 in 22 · `edbak_groesse_text(` 43 in 10. Neu und wie
erwartet: `sitzung_ablage()` mit **zwei** Aufrufstellen (`db.php` Z. 586,
`install.php` Z. 144). Die drei lesenden Seiten tragen weiter **keine**
Cookie-Bedingung (FF-2 besteht fort).

### 1.0a Nachmessung mit dem Zählmittel — AP1, 20.09.2026, `main` `fd99989`

`tools/zaehlung/` misst seit AP1 alle 38 Registerzeilen mit dem PHP-Tokenizer
nach. **Bestand: 133 PHP-Dateien, 40 JS-Dateien** (`server/`, ohne `vendor/`)
— wie in 1.0. **Eichung getroffen:** `error_log(` **77 Aufrufe in 32 Dateien**,
`session_start(` **9 in 9**. **Selbstprobe 33 von 33.**

**Von 38 Zeilen bestätigen 32 die Zahl aus Abschnitt 1 aufs Stück.** Zwei
Zeilen hatte das Konzept offen gelassen („AP1 misst"), vier weichen ab. Es
gilt die Zahl des Werkzeugs; hier steht je Zeile der Grund.

| Zeile | Konzept | Zählmittel | Warum |
|---|---|---|---|
| **Z04** `$CFG` | 44 in 11 | **46 in 11** | Neun der elf Dateien stimmen aufs Stück; `db.php` zählt 11 statt 10 und `serverkrypto_lib.php` 18 statt 17. Bei `db.php` ist der Mehrtreffer erklärbar: Z. 9 ist `$CFG = require __DIR__ . '/config.php'` — die **Anlage** der globalen Variablen, kein Zugriff auf sie. Bei `serverkrypto_lib.php` ist er es nicht: Alle 18 sind echte Zugriffe (Z. 104, 105, 151, 152, 294, 295, 315, 316, 471, 472, 473, 694, 723, 798, 830, 831, 892, 893). Das Messwerkzeug der Konzeptsitzung liegt nicht im Repositorium (1.0) — vergleichbar ist deshalb die Zahl, nicht die Regel. **Folgenlos für die Abnahme:** Die Decke ist 0. |
| **Z19** `edbak_groesse_text(` | 43 in 10 | **42 in 10** | Die Regel zählt **Aufrufe**; die **Definition** in `adminbackup_lib.php` ist keiner. 42 Aufrufe + 1 Definition = 43. Die Dateiverteilung stimmt sonst aufs Stück. |
| **Z21** `number_format` deutsch | 26 in 11 | **27 in 12** | Der eine Mehrtreffer ist `betrieb_statistik.php:73`: `number_format((float)$n, $stellen, ',', '.')` — die **Nachkommastellen sind eine Variable**. Die Form ist dieselbe, der Aufruf gehört zu `zahl_text()`. |
| **Z18** Handlisten `missions` | 11 in 6 | **12 in 7** | Gleiche Sache, anderer Satz. **Neu gefunden:** `api/schneiden.php:173` (`INSERT INTO missions`, 11 Spalten) und `ingest.php:716` (`INSERT INTO missions`, 12 Spalten) — beides unstrittige Handlisten, die die Liste in 1.2 nicht nennt. **Nicht mitgezählt:** `api/mission.php` — der Rumpf von `json_out([…])` dort führt überwiegend **Felder des Diensttags und gerechnete Werte** (`day`, `mission_day`, `day_art_symbol`, `base_lat`, `start_hhmm`), nicht `missions`-Spalten; eine dichte Aufzählung von zehn Spaltennamen ist es nicht. |
| **Z07** „Rumpf kein JSON-Objekt" unter `api/` | „AP1 misst" | **11 in 11** | `payload` **8**, `format` **3**. Je Datei eine Stelle. Nicht mitgezählt ist `api/pat_anheben.php:120` (`!is_array($m)`) — das prüft ein **Element** der Liste, nicht den Rumpf, und bleibt nach E-ZE-15 stehen. |
| **Z37** Meldungs-Markup im JS | „AP1 misst" | **8 in 5** | `assets/import_ui.js` 2, je 1 `assets/schneiden.js`, `assets/unlock.js`, `einstellungen.php`, `zeitraum.php` 3. Zum Vergleich: Die breite Suche über den Quelltext, die 1.4 „nicht belastbar" nennt, ergibt **35 Erwähnungen in 15 Dateien** — sie zählt das PHP-Markup mit. |

**Ein Fund, der in Abschnitt 1.5 und FF-1 fehlt.** Die Regel „`date('…')` ohne
Zeitstempel" traf zunächst **fünf** statt vier Stellen. Die fünfte ist
`smtp.php:360`: `date(DATE_RFC2822)`, der `Date:`-Kopf einer ausgehenden Mail.
Sie ist **richtig, wie sie ist** — `DATE_RFC2822` schreibt den UTC-Versatz mit
(`+0200`), die Angabe ist in jeder Serverzeitzone vollständig. Sie ist kein
„heute" und gehört nicht zu `heute_lokal()`. Die Regel verlangt deshalb eine
**Zeichenkette** als Format; mit `date(KONSTANTE)` zählt sie nicht. Wer die
Zahl „14 `date(` in 9 Dateien" aus 1.5 ohne diese Bedingung nachzählt, kommt
auf eine andere.

### 1.1 Paket 1 — Marke, Mail, Link, Token: **durch** (Gegenprobe)

| Muster | 16.09. | 20.09. | Lage |
|---|---|---|---|
| `INSERT INTO password_resets` | 4 Stellen | **2, nur `konto_lib.php`** | erledigt (P5b AP2) |
| `base_url`-Verkettung von Hand | 5 | **0** — `'base_url'` nur in `install.php` (4, schreibt), `instanz_lib.php` (2, `app_url()`), `config.example.php` (1) | erledigt (P5a AP5) |
| Mailrahmen von Hand | 7 Versandstellen | **0** — `mail_rahmen(` 20 Aufrufe, 19 in `mail_lib.php`, 1 in `instanz_lib.php`; Versand über `mail_einreihen(` (19 Aufrufe, 14 Dateien), Texte aus `mail_katalog()` | erledigt (P5a AP5) |
| Token-Laufzeiten als SQL-Literal | 4 | **0** — `TOKEN_EINLADUNG_S`, `TOKEN_RESET_S` in `konto_lib.php` | erledigt (P5b AP2) |

10b hat hier **nichts neu verstreut**: Selbstregistrierung, Adresswechsel und
Freischaltung reihen über den Katalog ein.

### 1.2 Paket 2 — Datenzugriff: **alles gewachsen**

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| `app_state` direkt per SQL | 24 Stellen | **34 in 15 Dateien** | obwohl `app_state_lesen()` (22 Aufrufe, 10 Dateien) und `app_state_setzen()` (28, 12 Dateien) in `db.php` bestehen. Nach Operation: SELECT 15, INSERT 12, `INSERT IGNORE` 2, DELETE 5. Dateien: `jobs_lib.php` 6, `demo_lib.php` 5, `db.php` 4, `migration_lib.php` 3, je 2 `adminbackup_lib.php`, `auth_salt.php`, `geocoder_lib.php`, `registrieren.php`, `serverkrypto_lib.php`, je 1 `admin_installation.php`, `jobs.php`, `konten_einstellungen_lib.php`, `konto_lib.php`, `session_lib.php`, `smtp.php` |
| Wrapper-Paare um `app_state` | 5 | **4 Paare + Einzelne** | `edbak_marke_lesen/-setzen`, `geocoder_state/-setzen`, `schluessel_marke_lesen/-setzen`, `geraete_hinweis_stand/-bestaetigen`; dazu `demo_*`, `jobs_*`, `logo_*`, `smtp_versand_vermerken()` |
| „Geheimnis einmalig anlegen" | — | **2× baugleich** | `auth_salt.php` (`salt_secret`), `registrieren.php` `reg_geheimnis()` — SELECT, sonst `INSERT IGNORE`, dann erneut lesen |
| Virtuelles Gerät `manual-<userId>` | 4× zeichengleich | **4 baugleiche Blöcke „Gerät sicherstellen"** | `api/import_commit.php`, `api/schneiden.php` `schnitt_geraet()`, `api/gpx_import.php` `gpx_import_geraet()`, `einsatz_form.php`. Dazu **3 `NOT LIKE 'manual-%'`-Literale** (`admin_users.php`, `admin_user.php`, `betrieb_statistik.php`), obwohl `GERAETE_ECHT_SQL` und `geraet_virtuell()` in `db.php` bestehen. Literal `'manual-` außerhalb `db.php`: **7** |
| Einsatz per ID mit Besitzprüfung | 11 in 9 Dateien | **13 in 9 Dateien** | `trash_lib.php` 3, `api/schneiden.php` 2, `einsatz_form.php` 2, je 1 `api/import_commit.php`, `api/mission.php`, `einsatz.php`, `einsatz_verschieben.php`, `papierkorb.php`, `tageszuordnung_lib.php`. Spalten: `*`, `day_id`, `final`, `id, day_id`, …; Papierkorb-Bedingung in drei Fassungen (`IS NULL`, `IS NOT NULL`, keine). Gegenstück `dt_laden()`: 19 Aufrufe, 13 Dateien |
| Handlisten der `missions`-Spalten | 8 | **11 in 6 Dateien** | `api/export_data.php` 2 (SELECT 25 Spalten, Ausgabefeld 28), `api/import_commit.php` 3 (INSERT 27, UPDATE 26, Werteliste 19), `api/suchindex.php` 2 (19, 17), `backup_lib.php` 2 (`$missionSpalten` 30 mit Alias `uhr_gesperrt AS manual`, `$cols` im Einspielen), `api/range.php` 1, `api/mission.php` 1. Nicht gezählt: `migration_lib.php` (Historie), `mission_fields.php` (der Katalog) |
| Transaktionsrahmen | 21 Dateien | **`beginTransaction(` 33 in 22 Dateien**, `inTransaction(` 50, `rollBack(` 40 | verschachtelungsfeste Fassung (`$eigene = !$pdo->inTransaction()`) nur in `spur_lib.php` und `backup_lib.php`; **kein** `db_transaktion()` |
| Kindtabellen eines Einsatzes | 4 Schreibwege | **5 Schreibwege, 30 Anweisungen** | neu: `api/schneiden.php`. `mission_phases` INSERT 5/DELETE 4, `resus_sessions` 4/3, `resus_events` 4/0, `mission_resources` 3/2, `mission_crew` 3/2 (ohne `migration_lib.php`) — in `einsatz_form.php`, `api/import_commit.php`, `ingest.php`, `api/schneiden.php`, `backup_lib.php` |
| Schema-Abfrage „gibt es Tabelle/Spalte?" außerhalb `migration_lib.php` | — | **9 `information_schema` in 6 Dateien** | `komplett_lib.php` 2, `nachbearbeitung_lib.php` 2, `sicherungsziel_lib.php` 2, `ingest.php` 1, `speicher_lib.php` 1, `wiederherstellen.php` 1; die Helfer `_hat_tabelle/_hat_spalte/_hat_index/_fk_name` sind privat in `migration_lib.php` |
| Rollenvergleich von Hand (`=== 'admin'` …) | — | **7 in 3 Dateien**, davon **4 außerhalb `db.php`** | `admin_user.php` 3, `admin_users.php` 1; `rolle_darf_verwalten()`/`rolle_ist_betreiberin()` bestehen (12 Aufrufe, 7 Dateien) |

### 1.3 Paket 3 — API-Eingang, Sitzung, Flash

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| Dateien unter `api/` | 12 | **21** | alle prüfen die Methode selbst: `REQUEST_METHOD` 24× in 21 Dateien; `'error' => 'method'` **17× in 17 Dateien** unter `api/` (19 in `server/` gesamt) |
| JSON-Rumpf lesen (`php://input`) | 12 | **15** | 12 unter `api/` (darunter `api/csp_bericht.php`, Sonderfall), dazu `auth_salt.php`, `ingest.php`, `pair.php` (Gerätevertrag, bleiben) |
| Fehlerschlüssel „Rumpf ist kein JSON-Objekt" | uneinheitlich | **`payload` 14 (10 Dateien), `format` 4 (3)** — für dieselbe Sache | `leer` 4 (davon 3× leerer Rumpf mit `post_max_size`-Hinweis in drei Fassungen: `api/backup_restore.php`, `api/backup_eintraege_restore.php`, `api/backup_spuren_restore.php`), `zu_gross` 2, `too_large` 1. **Kein JS wertet diese Schlüssel aus** (gemessen: 0 Verbraucher; die Aufrufer lesen `data.meldung` und `data.error` nur als Wahrheitswert) |
| CSRF-Gatter | erledigt P5a | **erledigt** — `csrf_check(` 50 Aufrufe, 40 Dateien, 0 inline | bleibt eine Zeile je Datei |
| Sitzungsstart | 7 in 3 Varianten | **9 in 9 Dateien, 4 Varianten** | Tabelle unten |
| Flash-Meldung über die Sitzung | 3 Dateien | **22 Zugriffe in 3 Dateien**, kein Helfer | `einstellungen.php` 10, `nachbearbeitung.php` 8, `papierkorb.php` 4 |
| Verzögerte Antwort der unangemeldeten Endpunkte | 7 inline | **erledigt (P5a)** | `rate_gleiche_dauer()` einmal definiert, 16 Aufrufe in 7 Dateien; `json_out()`/`json_roh_out()` zentral |
| **Neu:** `config.php` lesen | — | **7 Lesestellen in 5 Dateien + 44 `$CFG`-Zugriffe in 11 Dateien**, kein Leser | `require … config.php`: `smtp.php` 3, `db.php` 1, `jobs.php` 1, `mail_lib.php` 1, `instanz_lib.php` 1 (Rückfall). `$CFG`/`global $CFG`/`$GLOBALS['CFG']`: `serverkrypto_lib.php` 17, `db.php` 10, `betrieb_schluesselblatt.php` 4, `plattform_lib.php` 3, je 2 `api/schluesselblatt_pruefen.php`, `migration_lib.php`, `tageszuordnung_lib.php`, je 1 `import.php`, `ingest.php`, `instanz_lib.php`, `netz_lib.php` |

**Die neun Sitzungsstarts und ihre vier Varianten:**

| Art | Dateien | Cookie-Parameter | Besonderheit |
|---|---|---|---|
| `app` | `auth_guard.php`, `login.php`, `session_lib.php` (`session_beenden()`) | `secure => true`, `samesite => 'Strict'` | — |
| `lesend` | `doku_seite.php`, `rechtstext_seite.php`, `notfallblatt.php` | `secure => !empty($_SERVER['HTTPS'])`, `Strict` | `@session_start()`, nur wenn `session_status() === PHP_SESSION_NONE`; **keine Cookie-Bedingung** (FF-2) |
| `einrichtung` | `install.php`, `wiederherstellen.php` | `secure => !empty($_SERVER['HTTPS'])`, `samesite => 'Lax'` | `install.php` lädt **kein** `db.php` |
| `passwort` | `pw_handling.php` (`pw_session_start()`) | `secure => true`, `Lax` | eigener Sitzungsname `PW_SESSION_NAME` (`EDPWSESS`) |

Alle neun setzen `session.use_strict_mode` davor; der Stufe-1-Schritt
„Sitzungshärtung" (`tools/sitzungshaertung/pruefen.php`) prüft genau das.

### 1.4 Paket 4 — JavaScript

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| JSON-POST mit Kopf `X-CSRF` | 15 in 6 Dateien | **15 in 6 Dateien** (unverändert) | `einstellungen.php` 6, je 2 `assets/export.js`, `assets/import_ui.js`, `assets/schneiden.js`, `assets/unlock.js`, `index.php` 1; zwei Schreibweisen des Tokens (`CSRF` und `typeof CSRF === 'string' ? CSRF : ''`) |
| **Neu aus 10b:** Formular-POST mit Feld `csrf` | — | **5 in 3 Dateien** | `assets/rueckfrage.js` 2, `assets/schluesselblatt.js` 2, `assets/schluessel.js` 1 — ein **zweiter** Transportweg neben dem Kopf; `csrf_check()` nimmt beide |
| `fetch(` gesamt | — | 33 in 16 Dateien | vier Fehlerschemata (`data.meldung ‖ data.error`, fester Satz, `e.message`, Status) |
| Formatierer-Definitionen (Tag, Dauer, km) | Datum 6×, Dauer 3 Schreibweisen, km 7× | **12 Definitionen in 4 Dateien** + 2 Dauer in `export.js` | `missiontable.js`: `fmtTag`, `fmtDur`, `fmtKm`, `fmtKmZahl`; `index.php`: `fmtDay`; `einsatz.php`: `fmtDay`, `fmtKm`, `fmtDauer`; `zeitraum.php`: `fmtKmDe`, `wertKm`, `wertKmSumme`, `fmtTagKurz` |
| Karten-Präambel (`L.map(`) | 4 Seiten | **4 Seiten + `ortswahl.js`** | `einsatz.php`, `index.php`, `tag_spuren.php`, `zeitraum.php`; `L.tileLayer(` ist schon zentral (`assets/map_layers.js`) |
| Rahmen um `EdPat.entschluessleListe()` | 3 | **6 Aufrufer in 6 Dateien** | Seiten: `index.php`, `suche.php`, `zeitraum.php`, `einstellungen.php`; Module: `assets/export.js`, `assets/import_ui.js` |
| Meldungs-Markup im JS nachgebaut | 6 | **nicht belastbar gemessen** | eine breite Suche nach `class="meldung` im HTML-Anteil zählt PHP-Markup mit (35 Erwähnungen, 15 Dateien). AP1 misst mit der Regel „Zeichenkette im JS-Kontext" |

### 1.5 Paket 5 — Zeit, Zahl, Migration

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| Bytes lesbar | 2 Funktionen | **`edbak_groesse_text()` in `adminbackup_lib.php`: 43 Aufrufe in 10 Dateien** | der Beleg aus R83, unverändert — neun fremde Dateien laden die Backup-Bibliothek dafür (`admin_sicherungen.php` 7, `betrieb_server.php` 6, `status_lib.php` 6, `admin_komplettsicherung.php` 5, `admin_sicherungsziele.php` 4, `wiederherstellen.php` 3, `speicher_lib.php` 2, `betrieb_sicherheit.php` 1, `betrieb_updates.php` 1) |
| Relative Zeit („vor … Minuten") | 3 | **2 Bauten** | `status_alter()` in `status_lib.php` (8 Aufrufe, nur dort) und eine Inline-Kopie in `betrieb_updates.php` mit **abweichender Rundung** (`max(1, …)`) |
| `number_format(` | 22 | **41 in 19 Dateien**, davon **26 in deutscher Form** (`, ',', '.'`) in 11 Dateien | — |
| Umrechnung Bytes → Anzeige von Hand | GB 7× | **18 Divisionen in 8 Dateien** | `betrieb_server.php` 4, `adminbackup_lib.php` 3, je 2 `admin_user.php`, `apk_lib.php`, `einstellungen.php`, `ingest.php`, `jobs_lib.php`, `gpx_lib.php` 1 (Byte-Literale insgesamt 33 in 12 Dateien; der Rest sind Grenzwerte, keine Anzeige) |
| Prozent von Hand | 5 Dateien | **10 in 5 Dateien** | `betrieb_statistik.php` 3, `speicher_lib.php` 3, `betrieb_server.php` 2, `adminbackup_lib.php` 1, `plattform_lib.php` 1 |
| ISO-UTC-Marke schreiben | 21 | **20 in 7 Dateien** (`gmdate('Y-m-d\TH:i:s\Z'`) | `komplett_lib.php` 9, `adminbackup_lib.php` 4, je 2 `gpx_lib.php`, `wiederherstellen.php`, je 1 `smtp.php`, `speicher_lib.php`, `wartung_lib.php`; `gmdate(` gesamt 50 in 24 Dateien |
| ISO-UTC-Marke lesen | 9 | **9 in 5 Dateien** (`str_replace(['T','Z'], …) . ' UTC'`) | `status_lib.php` 5, je 1 `admin_sicherungen.php`, `admin_user.php`, `adminbackup_lib.php`, `wartung_lib.php` |
| Datumsformat-Literale mit Tag.Monat | 45 in 4 Trennervarianten | **67 in 26 Dateien, 5 Varianten** | `'d.m.Y'` 29, `'d.m.Y H:i'` 25, `'d.m.Y · H:i'` 11, `'d.m.Y, H:i'` 1, `'d.m.Y H'` 1 |
| `date(` (Zeitzone des Servers) | 3 | **14 in 9 Dateien**; **`date_default_timezone_set` kommt nicht vor** | 6 davon ohne Zeitstempel („heute"): `diensttag_neu.php` 2, `einsatz_form.php` 1, `betrieb_statistik.php` 1, `install.php` 2 (FF-1) |
| `migration_lib.php` gegen `information_schema` | 40 inline | **57 Erwähnungen** bei 42 Helferaufrufen (`_hat_spalte` 27, `_hat_index` 10, `_hat_tabelle` 3, `_fk_name` 2) | gelaufene Migrationen — **bleiben** (E-ZE-04) |

### 1.6 Paket 6 — Beifang (kein Arbeitspaket)

Gemessen nur, was 10c berührt: **Kopien von `asset()`/`e()` in
`betrieb_schluesselblatt.php`: 0** — erledigt, beide stehen nur noch in
`db.php`. **Verwaltungsseiten-Auftakt:** 17 Seiten, je eine Zeile
`require_admin()` oder `require_betreiberin()` (`auth_guard.php`) — das
Rollengatter **ist** eine Stelle; der Rest des Auftakts bleibt Beifang.

### 1.7 Nr. 57 — gilt unverändert

`index.php` baut seine Zeilen weiter selbst (`tr.innerHTML = …`) und holt vom
Modul nur Zellbausteine (3 Verwendungen von `EdMissionTable.`); `suche.php` und
`zeitraum.php` nutzen das Modul ganz. **Spaltensatz:** Modul `col, art, day,
start, dur, site, age, dx, winch, bw, sec, fehl, km`; `index.php` führt
zusätzlich **`no` (Nr.)** und die Tagesspalten aus `mf_tagesspalten()`, es
fehlen ihm `art`, `day`, `fehl`. `mf_tagesspalten()` hat genau zwei
Verbraucher (`index.php`, `api/day.php`); `api/range.php` und
`api/suchindex.php` führen `winch`, `bergwacht`, `secondary` hart im SELECT.
Das Modul kennt `setSpaltenBestand()` — der Haken für `cap_gate` besteht also.

---

## 2. Entscheidungen

### 2.1 Aus dem Gespräch (E-ZE-01 bis -08)

**E-ZE-01 — Nr. 57: Das Modul gilt auch für Gleichstände und `sortable`**
(Auftraggeber, 20.09.2026). Die Entscheidung vom 12.09.2026 („`missiontable.js`
ist die Vorlage") deckte Beschriftung, Ausrichtung und Hakenreihenfolge. Sie
gilt jetzt ausdrücklich auch für (a) die **Sortierung bei Gleichständen** — das
Modul verlässt sich auf die stabile Sortierung; ein zweiter Klick auf eine
Spalte mit lauter gleichen Werten dreht die Zeilen **nicht mehr** — und (b)
`sortable` auf **jedem** Spaltenkopf samt `cursor:pointer` und Hover. **Kein
Mockup.**

**E-ZE-02 — Ein Konfigurationsleser `konfig()` kommt in den Umfang**
(Auftraggeber, 20.09.2026). Nicht in Nr. 202, aber gemessen (1.3) — und 10c
bringt drei neue Schlüssel (`app.umgebung`, `betrieb.health_token`,
`mail.postfach`), jeder wäre eine achte bis zehnte Lesestelle. Gebaut in AP2.

**E-ZE-03 — Das Zählmittel entsteht hier, nicht in 10c AP3** (Auftraggeber,
20.09.2026). `tools/zaehlung/` entsteht in AP1, der Stufe-1-Schritt in AP10.
10c AP3 baut **kein** Zählmittel mehr, sondern setzt die Zeile `error_log` des
Registers auf Soll ≤ 2 (Nr. 248 wird damit eine Registerzeile). Das ändert
**einen Satz** im freigegebenen P5c-Konzept — Wortlaut in Abschnitt 9.

**E-ZE-04 — Gelaufene Migrationen werden nicht umgebaut** (Auftraggeber,
20.09.2026; weicht bewusst von „alles wird angegangen" ab). P8 setzt das
Migrationsregister neu auf (R66); 57 Abfragen in Migrationen umzuschreiben,
die auf jeder Anlage schon gelaufen sind, ist Risiko ohne Ertrag. Stattdessen:
die Schema-Helfer werden **öffentlich** (`db_hat_tabelle()`, `db_hat_spalte()`,
`db_hat_index()` in `db.php`, AP4), die privaten `_hat_*` rufen sie nur noch
auf, und die **Regel** „neue Migrationen fragen das Schema nur über die
Helfer" steht in `CLAUDE.md` und als Registerzeile (Stand 57 als Decke: die
Zahl der `information_schema`-Erwähnungen in `migration_lib.php` darf nicht
steigen).

**E-ZE-05 — Log-Helfer nicht hier, seine Zahl schon** (Auftrag). Kein
Arbeitspaket stellt einen `error_log()`-Aufruf um. Wer Code verschiebt,
verschiebt die Zeile **unverändert** mit. Jedes AP nennt in seiner
Abnahme die Zahl der `error_log(`-Aufrufe; Start **77 in 32 Dateien**, die
Übergabezahl an 10c AP3 steht im Statusblock von AP10. Eine Abweichung von 77
ist zu erklären (welche Zeile, warum).

**E-ZE-06 — `sitzung_ablage()` wandert in `sitzung_starten()`** (Auftrag;
Konzept Sitzungsablage E-SA-02). Schritt 16 ruft sie an zwei Stellen
(`db.php`, `install.php`); mit AP2 ruft sie allein `sitzung_starten()`, die
beiden Aufrufe entfallen.

**E-ZE-07 — Paket 6 (Beifang) bleibt Liste mit Regel, kein Arbeitspaket.**
Es gilt, was der Rahmenplan sagt: nur zusammen mit Arbeit an der jeweiligen
Datei, kein Termin. Die Frage aus der Umfangsbestätigung, ob der
Verwaltungsseiten-Auftakt wegen der Support-Rolle (10c AP4) vorzuziehen sei,
ist mit der Messung beantwortet: **nein** — das Rollengatter ist schon eine
Stelle (1.6).

**E-ZE-08 — Nr. 57 gehört zu Schritt 15 und ist ein eigenes Arbeitspaket**
(Auftrag). AP9, nach AP6 und AP8.

### 2.2 Aus dem Nachmessen (E-ZE-10 bis -24)

**E-ZE-10 — Kein Verhalten ändert sich.** Wortlaut in Abschnitt 0. Prüfbar
gemacht durch: Kreisläufe csv und edbak **0 unerklärt**, Byte-Vergleich von
Export und Backup (AP6), Bilderlauf **0/0/0** außerhalb der in AP9 genannten
Abweichungen, alle Proben unter `tools/` grün wie zuvor.

**E-ZE-11 — Ort je Helfer (R83: Bibliotheksdatei statt Seite).**

| Helfer | Datei | Grund |
|---|---|---|
| `konfig()`, `konfig_verwerfen()` | **`konfig_lib.php`** (neu, **ohne Abhängigkeiten**) | `install.php` und `sitzung_lib.php` müssen sie ohne `db.php` laden können |
| `sitzung_starten()` | **`sitzung_lib.php`** (besteht seit Schritt 16; lädt nie `db.php` und zieht nur für die stündliche Schreibprobe `plattform_lib.php` nach) | `install.php` lädt kein `db.php`; `session_lib.php` lädt `db.php` und scheidet deshalb aus |
| `api_methode()`, `api_rumpf()` | **`db.php`**, neben `json_out()` | 10c AP6 (`api/health.php`) braucht den Eingang **ohne** `auth_guard.php`; CSRF bleibt deshalb draußen (E-ZE-15). **In AP3 auf zwei Funktionen aufgeteilt** (AP3-a): `csrf_check()` steht in allen elf Rumpf-Dateien zwischen Methodenprüfung und Rumpflesen, ein Aufruf hätte diese Reihenfolge gekehrt |
| `flash_setzen()`, `flash_holen()` | `session_lib.php` | hängt an der Sitzung |
| `app_state_*`, `db_transaktion()`, `db_hat_*()`, `geraet_virtuell_sicherstellen()`, `geraete_echt_sql()` | `db.php` | dort liegen die Nachbarn schon. **Seit AP4 gebaut** — dazu `app_state_zu_lang()`, `geraet_virtuell_kennung()` und `GERAET_VIRTUELL_MUSTER`. `db_hat_*()` nehmen ein `PDO` als **ersten** Parameter (AP4-a): `tools/schemaprobe/` fährt gegen eine andere Verbindung als `db()`. `db_transaktion(PDO $pdo, callable $fn): mixed` ist seit AP5 gebaut |
| `einsatz_laden()`, `einsatz_*_ersetzen()` | **`einsatz_lib.php`** (neu) | Gegenstück zu `diensttag_lib.php`. **Seit AP5 vollständig gebaut**, dazu `einsatz_anweisung()` (ein Anweisungs-Zwischenspeicher je Verbindung — `ATTR_EMULATE_PREPARES` ist `false`, jedes `prepare()` kostet einen Roundtrip). **`einsatz_laden(int $id, int $userId, array $o = []): ?array` ist seit AP4 gebaut**, Optionen `spalten` und `papierkorb` (`nein`/`ja`/`egal`); `einsatz_*_ersetzen()` folgt in AP5 |
| `mf_missions_register()`, `mf_spalten()` | `mission_fields_lib.php` | der Katalog ist das Vorbild |
| `groesse_text()`, `zeit_relativ()`, `zahl_text()`, `prozent_text()`, `iso_utc()`, `iso_utc_lesen()`, `heute_lokal()`, `datum_text()`, `datum_zeit_text()` | **`format_lib.php`** (neu; lädt nur `konfig_lib.php`) | kein Verbraucher soll dafür eine Fachbibliothek laden müssen — der R83-Beleg |
| `EdApi` | **`assets/api.js`** (neu) | in der Immer-Liste von `ui.php` (`ui_seite_*`) |
| `EdFormat` | **`assets/format.js`** (neu) | `missiontable.js` wird erster Verbraucher |
| `EdHtml.meldung()` | `assets/html.js` (besteht) | — |
| `EdKarte.anlegen()` | `assets/map_layers.js` (besteht) | dort liegt `L.tileLayer` schon |

Namen sind Vorschläge und am 20.09.2026 **auf Kollision geprüft: 0 Treffer**.
Ändert die Umsetzung einen Namen, trägt sie ihn hier und in Abschnitt 6 nach.

**E-ZE-12 — `sitzung_starten(string $art): bool` mit vier Arten.** `app`,
`lesend`, `einrichtung`, `passwort` — mit **genau** den Parametern der Tabelle
in 1.3. Ablauf: läuft schon eine Sitzung → `true`, nichts tun; sonst
`sitzung_ablage()`, `session_name()` (nur `passwort`),
`session_set_cookie_params()`, `ini_set('session.use_strict_mode', '1')`,
`session_start()` (bei `lesend` mit `@`). `PW_SESSION_NAME` zieht als Konstante
nach `sitzung_lib.php`. Die Drift bei `secure` (fest `true` gegen
HTTPS-abhängig) **bleibt**, wie sie ist — sie zu schließen ist eine
Sicherheitsentscheidung für Schritt 18, keine Zentralisierung; sie steht als
Kommentar an der Tabelle der Arten.

**E-ZE-13 — Der Stufe-1-Schritt „Sitzungshärtung" wird umgestellt.** Nach AP2
gibt es **einen** `session_start(`-Aufruf in `server/`. Die Prüfung verlangt
dann: (a) genau ein Aufruf, (b) er steht in `sitzung_lib.php`, (c) davor die
Härtungszeile, (d) **jeder weitere Aufruf irgendwo ist ein Befund**. Die
Selbstprobe wird entsprechend neu geschrieben (Fallzahl im Prüfdokument).

**E-ZE-14 — `konfig(string $pfad, mixed $vorgabe = null): mixed`.** Punktpfad
(`'app.timezone'`), Ergebnis in einer `static` gemerkt, fehlende `config.php`
→ `$vorgabe` (der Fall `install.php`). `konfig_verwerfen()` leert den Merker;
`config_gemerktes_verwerfen()` in `serverkrypto_lib.php` ruft sie mit auf
(derselbe Fehler wie S2/AP7 wäre sonst wieder da: die Seite, die gerade
geschrieben hat, zeigt den alten Stand). Die globale `$CFG` in `db.php`
entfällt, sobald ihre Zugriffszahl 0 ist — in AP2, nicht später.

**E-ZE-15 — `api_eingang(array $o = []): array`.** Optionen: `methode`
(`'POST'` Vorgabe, `'GET'` oder Liste), `rumpf` (`'json'` Vorgabe, `'keiner'`),
`max_bytes`. Er prüft die Methode, liest den Rumpf, prüft „leer" und „ist ein
JSON-Objekt" und gibt den Rumpf zurück. **CSRF gehört nicht hinein** — die
Zeile `csrf_check();` bleibt je Datei stehen: Sie ist schon eine Stelle, und
`api/health.php` (10c AP6) braucht den Eingang ohne Sitzung. **Inhaltliche**
Prüfungen nach dem Eingang (fehlender Schlüssel `eintraege`, falsches
`format`-Feld eines Backups, `leer` in `api/schneiden.php`) bleiben, wo sie
sind, mit ihren Schlüsseln. **Ausnahme, namentlich:** `api/csp_bericht.php`
(anderer Inhaltstyp, kein eigener Aufrufer). Fehlerschlüssel: F-ZE-5.

> **In AP3 auf zwei Funktionen aufgeteilt** — `api_methode(string|array
> $erlaubt = 'POST'): void` und `api_rumpf(array $o = []): array`. Grund,
> Messung und Freigabe stehen im AP3-Protokoll unter **AP3-a**: In allen elf
> Rumpf-Dateien steht `csrf_check()` zwischen Methodenprüfung und
> Rumpflesen, ein einziger Aufruf hätte diese Reihenfolge gekehrt und wäre
> eine sechste Verhaltensänderung gewesen. Alles Übrige dieses Absatzes
> gilt unverändert — auch, dass CSRF draußen bleibt. **`max_bytes` bekommt
> keinen Vorgabewert** (AP3-b).

**E-ZE-16 — Flash:** `flash_setzen(string $ton, string $text): void`,
`flash_holen(): ?array` (liest **und** löscht). Drei Seiten ziehen um; der
Sitzungsschlüssel heißt danach einheitlich `flash`. `post_ende()` aus Nr. 202
wird **nicht** gebaut (F-ZE-4).

**E-ZE-17 — `app_state`: sechs Funktionen, die Wrapper bleiben.** Zu
`app_state_lesen()` und `app_state_setzen()` kommen `app_state_mehrere(array
$k): array`, `app_state_setzen_mehrere(array $kv): bool`,
`app_state_loeschen(string ...$k): void` und `app_state_einmalig(string $k,
callable $erzeuger): string` (`INSERT IGNORE`, dann lesen — atomar, für die
beiden Geheimnisse). Die Wrapper (`edbak_marke_*`, `geocoder_state*`,
`schluessel_marke_*`, `geraete_hinweis_*`, `demo_*`, `jobs_*`, `logo_*`)
**behalten Namen und Signatur** — sie tragen Bedeutung und teils einen eigenen
Merker (`$frisch`) —, ihr Rumpf ruft die Helfer. **Ausnahmen, namentlich:**
`migration_lib.php` (`_tor_lesen()`, `migrationen_tor_merken()`,
`migrationen_tor_zuruecksetzen()` — E-ZE-04) und `job_aufraeumen()` in
`jobs_lib.php` (`DELETE a FROM app_state a …`, ein Verbund, kein Schlüsselzugriff).
**Falle:** **12 Stellen in 5 Dateien** arbeiten mit einem **übergebenen** `$pdo`
statt mit `db()` — `jobs_lib.php` 5, je 2 `auth_salt.php`, `demo_lib.php`,
`registrieren.php`, `konto_lib.php` 1 —, teils in einer Transaktion
(`demo_anlegen()`, `konto_loeschen()`, `jobs_pause()`). Vor
dem Umbau ist je Stelle zu belegen, dass `$pdo` dieselbe Verbindung ist wie
`db()`; wo nicht, bekommt der Helfer einen optionalen Parameter `?PDO $pdo`.

**E-ZE-18 — Virtuelles Gerät:** `geraet_virtuell_kennung(int $userId): string`
und `geraet_virtuell_sicherstellen(PDO $pdo, int $userId): int` neben
`geraet_virtuell()` in `db.php`; die vier Blöcke rufen sie, `schnitt_geraet()`
und `gpx_import_geraet()` entfallen. Für die drei `LIKE`-Literale
`geraete_echt_sql(string $alias = ''): string` (zwei der drei Stellen brauchen
den Tabellenalias `d.`, den die Konstante nicht trägt) und eine Konstante
`GERAET_VIRTUELL_MUSTER` für die Stelle, die das Muster als Parameter bindet.

**E-ZE-19 — `einsatz_laden(int $id, int $userId, array $o = []): ?array`.**
Optionen `spalten` (Vorgabe `'*'`) und `papierkorb` (`'nein'` Vorgabe ·
`'ja'` · `'egal'`). 12 der 13 Stellen ziehen um; **Ausnahme, namentlich:**
`trash_restore_mission()` (Verbund mit `days`).

**E-ZE-20 — `db_transaktion(PDO $pdo, callable $fn): mixed`.**
Verschachtelungsfest (`$eigene = !$pdo->inTransaction()`), `rollBack()` bei
jedem `Throwable` nur für die eigene Transaktion, danach weiterwerfen,
Rückgabewert der Funktion durchgereicht. AP5 zählt **zuerst** die Bauformen
der 33 Stellen aus und trägt das Ergebnis hier nach; umgestellt wird, was die
Bauform „beginnen · versuchen · bestätigen · bei Fehler zurückrollen und
werfen" hat. **Ausnahmen werden namentlich geführt**; gesetzt ist vorab
`ingest.php` (Deadlocks, Nr. 210, Schritt 18 — dort fasst Schritt 15 den
Rahmen nicht an). **Haltepunkt H-ZE-4**, wenn mehr als acht Ausnahmen bleiben.

**E-ZE-21 — Kindtabellen: vier Datenzugriffsfunktionen ohne Prüfpolitik** in
`einsatz_lib.php`: `einsatz_phasen_ersetzen()`, `einsatz_reas_ersetzen()`
(Sitzungen und Ereignisse), `einsatz_rettungsmittel_ersetzen()`,
`einsatz_besatzung_ersetzen()`. Sie nehmen geprüfte Werte und schreiben;
**was gültig ist, entscheidet weiter der Aufrufer**. `mission_crew` ist gegenüber
Nr. 202 dazugekommen (dieselben Schreibwege). `ingest.php` ist der heiße
Pfad und Gerätevertrag: dieselben Anweisungen in derselben Reihenfolge,
belegt durch Ingestprobe und Messstand.

**E-ZE-22 — Spaltenregister für `missions`.** `mf_missions_register()` führt
**jede** Spalte aus `schema.sql` genau einmal und sagt je **Zweck** —
`export`, `backup`, `import_neu`, `import_aendern`, `suchindex`, `range`,
`api_mission` —: dabei · nicht dabei **mit Grund** · dabei unter Alias
(`uhr_gesperrt AS manual`). `mf_spalten(string $zweck): array` liefert die
Liste. Die sieben **SQL-Listen** werden daraus erzeugt; die vier
**Abbildungen** (Ausgabefelder mit Umrechnung) dürfen von Hand bleiben, wenn
eine **Vollständigkeitsprobe** belegt, dass sie genau die Registerspalten
ihres Zwecks führen. Zwei Prüfungen tragen das Paket: (1) die erzeugten Listen
sind den heutigen Handlisten in **Menge und Reihenfolge gleich** (eingefrorene
Abbilder im Werkzeug), (2) **`schema.sql` minus Register = leer** — als
Stufe-1-Schritt neben dem Migrationsregister. Die bewussten Auslassungen aus
`backup_lib.php` (`id`, `user_id`, `device_id`, die tote `other_resources`)
wandern als Grund ins Register. `Export-Format.md` und `Backup-Format.md`
ändern sich **nicht**.

**E-ZE-23 — `format_lib.php`.** `edbak_groesse_text()` heißt dort
`groesse_text()`; der alte Name **entfällt** (43 Aufrufe). `status_alter()`
heißt `zeit_relativ()` und **ist die Fassung, die gilt**; die Kopie in
`betrieb_updates.php` entfällt. Die beiden sind auseinandergelaufen, und das
ist die **einzige sichtbare Folge dieses Pakets**: Auf Betrieb → Updates
steht beim Alter des jüngsten Komplett-Backups unter 90 Sekunden künftig
„gerade eben" statt „vor 1 Minuten", und zwischen 60 und 90 Minuten „vor 60 …
90 Minuten" statt „vor 1 Stunden" (die Kopie wechselt bei 3 600 s auf Stunden,
`status_alter()` bei 5 400 s).

> **Eine DRITTE Folge, nachgetragen am 22.09.2026** (Auftraggeber: „als dritte
> Folge in E-ZE-23 aufnehmen"), gefunden beim Auszählen von Z20 zu Beginn von
> AP7: Die Kopie **fängt eine unlesbare Zeitmarke nicht ab**. `strtotime()`
> liefert dort `false`, `time() - false` ist `time()`, und angezeigt würde
> „vor ~20 400 Tagen"; `zeit_relativ()` sagt an derselben Stelle
> **„unbekannt"**.
>
> **Sie ist keine sechste Ausnahme von E-ZE-10, sondern dieselbe, vollständig
> beschrieben.** Die Entscheidung lautet seit dem 20.09.2026 „die Kopie
> entfällt"; was sich dabei ändert, war nur unvollständig aufgezählt.
>
> **Auslösbar ist der Weg heute nicht.** Die einzige Eingabe ist
> `komp_zeit_aus_name()` (`komplett_lib.php`), das die Marke aus einem
> regexgeprüften Dateinamen baut und sonst `null` liefert — und `null` fängt
> der äußere Wächter in `betrieb_updates.php` ab, bevor gerechnet wird. Die
> Änderung geht außerdem in die richtige Richtung: von einer falschen Zahl zu
> einer ehrlichen Auskunft. Das Prüfdokument führt sie als unauslösbaren
> Fehlerweg.

`zahl_text()` ersetzt
`number_format(…, ',', '.')` (26 Stellen); die 15 übrigen `number_format`
(andere Form, etwa Koordinaten und GPX) bleiben. `iso_utc()` schreibt,
`iso_utc_lesen()` liest die Marke. Byte-**Anzeigen** laufen über
`groesse_text()`; Byte-**Grenzwerte** bleiben, wo sie sind. Datumsformate:
F-ZE-3.

**E-ZE-26 — `assets/format.js` entsteht schon in AP7, mit einer sichtbaren
Folge** (Auftraggeber, 22.09.2026). `einstellungen.php` rechnete die Größe der
heruntergeladenen Sicherungsdatei in einer **Inline-Zeile** aus —
`(blob.size / 1048576).toFixed(1).replace('.', ',')`, also **immer in MB**,
ohne Tausenderpunkt. Das ist eine vierte Fassung desselben Formatierers, und
zwar die einzige im Browser.

Sie zieht nach `server/assets/format.js` als `EdFormat.groesse()` und
übernimmt dabei die **dreistufige** Regel der PHP-Seite. Die Folge ist
sichtbar: Bei einer Sicherungsdatei **unter 1 MiB** steht künftig „312 KB"
statt „0,3 MB", **ab 1 GiB** „1,00 GB" statt „1.024,0 MB". Beides ist
erreichbar — ein leeres Konto sichert unter einem Megabyte.

**Der andere Weg wäre gewesen**, die MB-Form als eigene Funktion zu erhalten.
Das hätte kein Zeichen geändert, aber zwei Größenschreibweisen im Haus
festgeschrieben — und die Frage nur nach AP8 verschoben. Nachgemessen sind
die beiden Fassungen jetzt über **2 014 Byte-Werte** Zeichen für Zeichen
gleich (PHP gegen JavaScript, 0 Abweichungen).

**AP8 baut die Datei aus** (Z34). Bis dahin steht dort genau, was einen
Verbraucher hat.

**E-ZE-24 — Ein Register, das bleibt.** `tools/zaehlung/` führt je Muster eine
Zeile: Kennung, Beschreibung, Regel, Sicht, **Decke**, Ausnahmen. Der
Stufe-1-Schritt (AP10) schlägt fehl, wenn ein Ist-Wert **über** der Decke
liegt. So kommt eine zweite Stelle nicht unbemerkt zurück — der Fall, den R83
mit `edbak_groesse_text()` selbst belegt. Die Startdecken stehen in
Abschnitt 4.

**E-ZE-27 bis E-ZE-30 — vier Zielzahlen enden begruendet ueber null**
(Auftraggeber, 22.09.2026: „ehrliche Zahl statt runder Null“). Die
Zaehlzeilen sind ein **Messmittel**, kein Zielkatalog; eine Null, die nur
durch einen erzwungenen Umbau zustande kaeme, misst nichts mehr.

| | Zeile | Ende | Was dort steht |
|---|---|---|---|
| **E-ZE-27** | Z29 Feld `csrf` | **2** | die Zentrale selbst, plus `rueckfrage.js` — dort ist es kein Formularfeld, sondern eine Parameterweitergabe an `EdSchluessel.erneuern()`, also ein Fehlalarm des Musters. Sie faellt nur ueber eine Schnittstellenaenderung, und die Schnittstelle bleibt. |
| **E-ZE-28** | Z37 Meldungs-Markup | **4** | zweimal die Zentrale (zwei Treffer in einer Funktion: Tonklasse und Aktionszeile), dazu eine leere Huelle in `schneiden.js`, die ein Symbol bekaeme (Backlog Nr. 271), und ein `<p class="meldung">` in `unlock.js`, das gar keine Meldung ist (Backlog Nr. 272). |
| **E-ZE-29** | Z34 Formatierer | **5** | duenne Weiterleitungen, die je einen anderen **Leerwert** binden. Sie aufzuloesen hiesse, den Leerwert an fuenfzehn Aufrufstellen zu wiederholen statt an fuenf. |
| **E-ZE-30** | Z36 `entschluessleListe` | **1** | `einstellungen.php` teilt nur den Aufruf, nicht den Rahmen — und `hinweisUnlesbar()` wertet die ganze Liste aus, waehrend dort Fenster zu 250 aus tausenden kommen. |

**Was daraus folgt, gilt ueber Schritt 15 hinaus:** Wer eine Zaehlzeile auf
null zwingt, ohne die Stelle zu verstehen, bekommt eine gruene Zahl und eine
schlechtere Anwendung. Die Decke im Register traegt deshalb je Zeile den
ausgeschriebenen Grund, nicht nur den Wert.

**E-ZE-25 — Ultracode ab AP7: Freigabe ohne Festlegung im Konzept**
(Auftraggeber, 22.09.2026). Arbeit darf auf Unter-Agenten gefächert werden
(Workflow, „Ultracode"), wo sie sich fächern lässt; **was gefächert wurde,
steht hinterher im Paketbericht**, nicht vorher im Konzept. Die
Paketfestlegung wäre die sauberere Form und ist deshalb für **künftige**
Konzepte in `CLAUDE.md` 7 hinterlegt — dieses Konzept ist zu weit, um sie
rückwirkend einzuziehen.

**Was sich damit NICHT fächern lässt, und zwar aus Gründen dieses Projekts:**
Es gibt **eine** lokale Anlage und **einen** Demo-Bestand — zwei Agenten, die
gleichzeitig Proben fahren, messen einander. Prüfarbeit bleibt seriell.
Und Changelog, `version.php` und dieses Konzept sind erzählende Prosa mit
einem Ton; drei Agenten schreiben drei Töne. Gefächert wird die **Messung**
(lesend, ohne Nebenwirkung) und der **Umbau getrennter Dateien** — wo zwei
Zählzeilen dieselbe Datei anfassen, bleibt es seriell, sonst überschreiben
sie sich.

**AP6 ist davon nicht mehr berührt** — es war beim Beschluss fertig gebaut.

### 2.3 Zum Gegenlesen — mit der Freigabe vom 20.09.2026 entschieden

| # | Vorschlag | Folge | Die andere Wahl |
|---|---|---|---|
| **F-ZE-1** | `heute_lokal()` rechnet „heute" in `app.timezone` statt in der Zeitzone der `php.ini` (FF-1). Vier Stellen: `diensttag_neu.php` 2, `einsatz_form.php` 1, `betrieb_statistik.php` 1; `install.php` bleibt (keine `config.php`) | sichtbar nur, wo Server- und App-Zeitzone auseinanderliegen — dort ist es die Berichtigung | `date()` stehen lassen und den Fund ins Backlog — dann hängt „heute" weiter am Hoster (R81) |
| **F-ZE-2** | Art `lesend` startet die Sitzung **nur, wenn das Sitzungs-Cookie da ist** (FF-2) | anonyme Besucher von Handbuch, „Was ist NAdoku", Rechtstexten bekommen kein Cookie und keine Sitzungsdatei mehr; Angemeldete sehen den angemeldeten Kopf wie bisher | unverändert lassen — dann legt jeder Bot eine Datei in `.sitzungen/` an |
| **F-ZE-3** | Datum-Zeit-Trenner: Schritt 15 **benennt** die Varianten und ändert keinen Pixel — `datum_text()` (`d.m.Y`), `datum_zeit_text($utc, $trenner)` mit den Trennern Leerzeichen (25) und ` · ` (11); `'d.m.Y, H:i'` (1, `betrieb_schluesselblatt.php`) und `'d.m.Y H'` (1, `status_lib.php`) bleiben benannte Einzelfälle. **Die Vereinheitlichung geht an 10c AP9**, das alle elf ` · `-Seiten ohnehin mit Bilderlauf überarbeitet | 67 Literale → 0, Bilderlauf 0/0/0 | jetzt vereinheitlichen — das wäre eine Gestaltungsentscheidung ohne Mockup in einem Schritt, der keins hat |
| **F-ZE-4** | `post_ende()` (Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten) aus Nr. 202 wird **nicht** gebaut | bleibt als Backlog-Kandidat (Abschnitt 9) | bauen — ändert aber Wege durch die Anwendung und ist damit keine Zentralisierung |
| **F-ZE-5** | Fehlerschlüssel des API-Eingangs unter `api/`: `method` (405), `leer` (400, **ein** `post_max_size`-Hinweis statt drei Fassungen), `format` (400, Rumpf ist kein JSON-Objekt), `zu_gross` (413). `payload` für „kein JSON-Objekt" verschwindet aus `api/` | kein JS wertet die Schlüssel aus (gemessen 0); die Geräte-Endpunkte behalten `payload` und `too_large` (JSON-Vertrag, Android-Tests) | beide Schlüssel im Eingang weiterführen — dann bleibt die Drift, nur an einer Stelle |
| **F-ZE-6** | Nr. 57, **Spaltensatz bleibt je Seite, wie er ist**: Die Tagesübersicht behält „Nr." und bekommt weder „Tag" noch „Art" dazu; das Modul erhält die Spalte `no` und nimmt den Spaltensatz als Seitenparameter. Die Entscheidung vom 12.09. nennt den Spaltensatz nicht | 0 neue, 0 entfallende Spalten auf allen drei Seiten | das Modul bestimmt auch den Spaltensatz — dann verlöre die Tagesübersicht „Nr." und bekäme eine Spalte „Tag" mit lauter gleichen Werten |

---

## 3. Arbeitspakete

### 3.0 Reihenfolge und Regeln

AP1 zuerst (ohne Zählmittel keine Abnahme in Zahlen). AP2 vor allem anderen
in `server/` — daran docken Schritt 16 und 10c an. AP4 vor AP5 und AP6
(`einsatz_lib.php` entsteht dort). AP8 vor AP9 (`assets/format.js`), AP6 vor
AP9 (Spalten für `api/range.php` und `api/suchindex.php`). AP10 zuletzt.

Für **jedes** Paket gilt zusätzlich zu `CLAUDE.md`: Startwerte mit dem
Zählmittel nachmessen und hier eintragen · `error_log(`-Zahl nennen (E-ZE-05)
· Registerzeilen auf die neue Decke setzen · `docs/Technik.md` nachziehen
(neue Bibliothek, entfallene Funktion) · Statusblock fortschreiben · pushen.

**Haltepunkte:** **H-ZE-1** eine Migration wird nötig → anhalten (erwartet:
keine). **H-ZE-2** Byte-Vergleich in AP6 scheitert und die Ursache ist nicht
die Reihenfolge → anhalten. **H-ZE-3** Schritt 16 ist nicht gemergt, oder es
findet sich ein zehnter Sitzungsstart → anhalten vor AP2. **H-ZE-4** mehr als
acht Transaktions-Ausnahmen → melden vor dem Umbau.

### AP1 — Zählmittel, Register, Gegenprobe Paket 1 (E-ZE-03, -24)

`tools/zaehlung/zaehlen.php` (PHP-Tokenizer für `.php`; für `.js` und den
Inline-Anteil ein Kommentar-Entferner), `register.php` (die Zeilen aus
Abschnitt 4), `--selbstprobe`, `LIESMICH.md`. **Kein** Eingriff in `server/`.
Prüfdokument anlegen.
**Abnahme:** Selbstprobe mit benannter Fallzahl, darunter „Aufruf im
Kommentar zählt nicht", „Aufruf in Zeichenkette zählt nicht", „Methodenaufruf
`->date(` zählt nicht"; **Eichung:** `error_log(` = **77 in 32 Dateien**,
`session_start(` = **9 in 9** an `862ca7f` (oder der dann aktuelle Stand, mit
Differenz erklärt); alle Startwerte aus Abschnitt 4 nachgemessen, Abweichungen
eingetragen; die vier Zeilen der Gegenprobe Paket 1 (Z30–Z33) stehen auf ihrer
Decke; die Regel für das Meldungs-Markup im JS (1.4) ist gefunden und
gemessen.

**AP1 — erledigt 20.09.2026.** Gebaut: `tools/zaehlung/zaehlen.php` (778 Z.),
`register.php` (391 Z., 38 Zeilen), `LIESMICH.md`. **Kein Eingriff in
`server/`** — deshalb keine Versionsstufe und kein CHANGELOG-Eintrag
(CLAUDE.md 2.1: eine Änderung nur an `tools/` oder `docs/` stuft keine der drei
Zählungen hoch; vom Auftraggeber am 20.09.2026 so bestätigt).

*Was dabei entschieden wurde:*

- **AP1-a — Zwei Decken je Registerzeile** (`decke_jetzt`, `decke_ziel`). Ohne
  die Trennung stünde die Zählung von AP1 bis AP9 durchgehend auf Rot und wäre
  in genau der Zeit wertlos, in der sie gebraucht wird. Das Paket schreibt
  `decke_jetzt` herunter, nicht die nächste Instanz nebenbei.
- **AP1-b — Z34 (JS-Formatierer) wird gegen eine Namensliste gemessen, nicht
  gegen ein Muster.** Die vierzehn heißen `fmtTag`, `wertKmSumme`,
  `durationHHMM` — es gibt kein Muster, das sie trifft und `wertLesen()` in
  `suche.php` (liest ein Formularfeld) oder `fmtDe1()` in `zeitraum.php`
  (allgemeine Kommastelle) ausläßt. **Preis, ausdrücklich:** Die Zeile hält die
  vierzehn auf null; einen **neu erfundenen** Formatierer unter neuem Namen
  sieht sie nicht. Dafür ist die Lesearbeit in AP8 da. Steht so in `LIESMICH.md`
  und im Prüfdokument.
- **AP1-c — Z18 misst Nähe, nicht Anweisungsgrenzen**, und prüft die Tabelle.
  Eine Handliste ist eine Aufzählung von zehn verschiedenen
  `missions`-Spaltennamen mit höchstens 60 Zeichen Abstand. „Zehn Namen
  irgendwo in der Datei" wäre falsch — `id`, `final`, `notes` und `origin`
  stehen überall.
- **AP1-d — Z27 verlangt eine Zeichenkette als Format.** Begründung und Fund:
  Abschnitt 1.0a, letzter Absatz.
- **AP1-f — `docs/Rahmenplan.md` und `docs/Backlog.md` bleiben in ganz Schritt 15
  unberührt, und der Schritt vergibt keine Backlog-Nummer** (Auftraggeber,
  21.09.2026).
  > **NACHTRAG VOM 21.09.2026, wenige Stunden später: Die Grundlage ist
  > entfallen.** Kette II ist nach `main` gemergt (PR #65/#68), und die
  > einspielende Instanz hat den Einschub gefahren: `main` trägt jetzt
  > Rahmenplan **Fassung 102** und einen Backlog mit der **Spanne 250–259 für
  > Schritt 15** — 250–253 sind die vier Einträge aus Abschnitt 9,
  > **254–259 ausdrücklich „Reserve für Funde der Umsetzung von Schritt 15"**.
  > Der Umsetzungszweig hat `main` geholt; es gibt keine zwei
  > auseinandergelaufenen Fassungen mehr, und die Spanne, deren Fehlen der
  > zweite Grund war, ist da.
  >
  > **Was davon bleibt:** Schritt 15 vergibt weiterhin **keine
  > Rahmenplan-Fassung** — daran arbeiten parallel andere Zweige (`pk-m1-*`),
  > und E-ZE-03 weist die Einschübe ohnehin der einspielenden Instanz zu.
  > **AUFGEHOBEN AM 21.09.2026 (Auftraggeber), und zwar zur Hälfte:** Der
  > **Backlog wird wieder normal gepflegt** (CLAUDE.md 2.4); Funde der
  > Umsetzung nehmen ihre Nummer aus der reservierten Spanne **254–259**.
  > Der **Rahmenplan bleibt unberührt** — daran arbeiten parallel andere
  > Zweige (`claude/pk-m1-*`), und E-ZE-03 weist die Einschübe ohnehin der
  > einspielenden Instanz zu. AP2 hat davon Gebrauch gemacht: Nr. 241 und
  > Nr. 251 sind nachgezogen, eine neue Nummer war nicht nötig.

  **Warum AP1-f am 21.09.2026 entschieden wurde:** Der Umsetzungszweig kommt von `main` und trägt
  Rahmenplan **Fassung 80** und einen Backlog **ohne 241–249** (nachgemessen:
  0 Treffer); die gültigen Fassungen liegen bis zum Merge von Kette II auf
  `claude/fervent-dirac-xirsqw` (Fassung 96, 241–249 vergeben). Würde hier
  gepflegt, verschmölze Git später zwei auseinandergelaufene Fassungen
  derselben Datei. Der gefährliche Fall ist dabei **nicht** der Konflikt,
  sondern die *saubere* Verschmelzung: Genau das ist in diesem Projekt schon
  passiert — der Backlog-Kopf hält fest, dass zwei Zweige nebeneinander
  angehängt haben, die Datei **keinen Konflikt meldete**, und danach vier
  Nummern zwei verschiedene Punkte trugen, zwei davon gleichzeitig unter
  *Offen* und unter *Erledigt*. Backlog-Nummern sind dauerhafte Kennungen, auf
  die Code und Dokumentation verweisen.
  **Stattdessen:** Was in die Steuerungsdokumente gehört, sammelt dieses
  Konzept in Abschnitt 8 und 9 — fertig formuliert, **ohne Nummer**. Die
  einspielende Instanz sieht nach dem Merge von Kette II beide Zweige und
  nummeriert kollisionsfrei aus der Spanne ab **250** (E-ZE-03). Ein Fund
  eines Arbeitspakets, der eine Nummer bräuchte, geht denselben Weg; Abschnitt
  9 führt vier solche Einträge bereits so.
- **AP1-e — Z07 prüft die Rumpfvariable, nicht irgendein `is_array`.** Gezählt
  wird die Fehlerantwort, deren Bedingung `is_array()` auf eine Variable
  anwendet, die in derselben Datei aus `json_decode(` kommt. Damit bleibt
  `api/pat_anheben.php:120` (`!is_array($m)`, ein **Element** der Liste)
  draußen — E-ZE-15 läßt es stehen.

*Probleme und wie sie gelöst wurden:*

1. **Das Konzept lag auf keinem Zweig.** Abschnitt 8.0 sagte es; nachgeprüft
   über alle fünf Remote-Zweige — `Konzept-Zentralisierung.md` gab es nirgends.
   Ohne die Datei im Repositorium läßt sich der Statusblock nicht
   fortschreiben. **Gelöst:** mit AP1 unverändert nach
   `docs/konzepte/Konzept-Zentralisierung.md` auf den Umsetzungszweig gelegt,
   dann fortgeschrieben.
2. **Kette II ist nicht bis M1 durch.** Nachgeprüft am 20.09.2026:
   `claude/fervent-dirac-xirsqw` steht bei `af866c1` (AP4 gebaut, Beweislauf
   offen), 35 Commits nicht in `main`. **Gelöst:** Der Auftraggeber hat AP1
   freigegeben und wartet Kette II für AP2 ab. AP1 fasst `server/` nicht an,
   die Reihenfolge aus 3.0 bleibt unberührt.
3. **`rest_segments` teilt sich zehn Spaltennamen mit `missions`**
   (`user_id, client_ref, day_id, started_at, ended_at, final, geraet_art,
   geraet_modell, deleted_at, deleted_with_day`) — genau die Schwelle. Zwei
   Ruhesegment-Anweisungen wurden als Handlisten der Einsatztabelle gemeldet.
   **Gelöst** durch eine Tabellenprüfung vor und hinter dem Lauf — und zwar
   **zweimal**: Die erste Fassung suchte vorwärts ab dem **Ende** des Laufs.
   Bei einem `SELECT` steht die Tabelle hinter der Spaltenliste, und je größer
   die zugelassene Lücke, desto weiter reicht der Lauf über das `FROM` hinaus;
   `backup_lib.php:464` (`… FROM rest_segments`) rutschte bei Lücke 60 wieder
   durch, nachdem es bei Lücke 40 gefangen worden war. Gesucht wird jetzt ab
   dem **Anfang** des Laufs. Beide Fälle stehen in der Selbstprobe.
4. **`<script<?= kopf_nonce_attr() ?>>`** (CLAUDE.md 6). Die kurze Form
   `[^>]*` ist hier richtig, weil der Tokenizer die PHP-Inseln vorher durch
   Leerzeichen ersetzt — und **das steht als Kommentar daneben**, damit sie
   beim nächsten Durchgang nicht „mitkorrigiert" wird. Ein Prüffall der
   Selbstprobe hält es fest.
5. **Für Schritt 15 ist im Backlog-Kopf keine Nummernspanne eingetragen.**
   Die Regel dort lautet: „Jeder weitere Zweig, der Nummern vergibt, beginnt
   bei 250 und trägt seine Spanne hier ein, bevor er pusht." Um eine Spanne
   einzutragen, müsste der Zweig aber genau die Datei bearbeiten, deren
   Bearbeitung das Problem ist. **Gelöst mit AP1-f:** Schritt 15 vergibt
   keine Nummern, also braucht er keine Spanne. AP1 hat keine gebraucht; ein
   späterer Fund kommt ohne Nummer nach Abschnitt 9.

### AP2 — Konfiguration und Sitzung (E-ZE-02, -06, -12, -13, -14; F-ZE-2)

`konfig_lib.php` neu; 7 Lesestellen und 44 `$CFG`-Zugriffe ziehen um, `$CFG`
entfällt. `sitzung_starten()` in `sitzung_lib.php`; neun Starts ziehen um;
die zwei `sitzung_ablage()`-Aufrufe aus Schritt 16 entfallen;
`pw_session_start()` wird ein Einzeiler oder entfällt. Prüfwerkzeug
„Sitzungshärtung" umgestellt.
**Falle — Ladezyklus:** `db.php` ruft `sitzung_ablage()` **während des eigenen
Ladens**, und `sitzung_lib.php` zieht dabei `plattform_lib.php` nach (Kommentar
dort, Z. 262–274 an `fd99989`). `plattform_lib.php` liest heute
`$GLOBALS['CFG']` und bekommt mit diesem Paket `konfig()`. Deshalb gilt:
`konfig_lib.php` lädt **nichts**, und kein Weg von `sitzung_lib.php` oder
`plattform_lib.php` darf `db.php` erreichen — `require_once` verdeckte den
Zyklus, statt ihn zu melden.
**Abnahme:** Z01 `session_start(` **9 → 1**; Z02 `sitzung_ablage(` Aufrufe
**2 → 1**; Z03 `config.php`-Lesestellen **7 → 1**; Z04 `$CFG`-Zugriffe
**44 → 0**; Sitzungshärtung 0 Befunde mit neuer Selbstprobe; **alle Wege
einmal gegangen** (Liste aus Konzept Sitzungsablage, Abschnitt 3): Anmeldung
bis Tagesübersicht ohne Schleife, Passwort-Reset, Abmelden, Handbuch ·
Rechtstext · Notfallblatt angemeldet mit angemeldetem Kopf, `install.php` und
`wiederherstellen.php` im Prüfstand; **F-ZE-2:** anonymer Abruf von
`hilfe.php` → **0** `Set-Cookie`, **0** neue Dateien in `.sitzungen/`;
angemeldet → Kopf wie zuvor; Cookie-Parameter je Art gegen die Tabelle in 1.3
gemessen (4 Arten × 3 Attribute = **12 Zellen, 0 Abweichungen**);
Abmelde-Probe, Ratenprobe, Kopplungsprobe grün; `konfig()` ohne `config.php`
liefert die Vorgabe (Prüfstand `install.php`); nach `config_eintrag_schreiben()`
zeigt dieselbe Anfrage den neuen Wert.

**AP2 — erledigt 21.09.2026, Web 20.27.0.** Neu: `server/konfig_lib.php`
(`konfig()`, `konfig_alles()`, `konfig_verwerfen()`) und `sitzung_starten()`
samt Tabelle `SITZUNG_ARTEN` in `sitzung_lib.php`. Umgezogen: 7 Lesestellen,
46 `$CFG`-Zugriffe, 9 Sitzungsstarts. Entfallen: die globale `$CFG`,
`PW_SESSION_NAME` und `pw_session_start()` in `pw_handling.php`, die beiden
`sitzung_ablage()`-Aufrufe aus Schritt 16. `tools/sitzungshaertung/`
umgestellt (E-ZE-13). **Versionsstufe Neben** — zwei neue Funktionen, kein
Datenmodell, keine Migration, `update.php` nicht fällig.

*Was dabei entschieden wurde:*

- **AP2-a — `db.php` verlangt `config.php` weiterhin hart, und das ist der
  Grund, warum dieses Paket KEINE sechste Verhaltensänderung hat.**
  `konfig_lib.php` toleriert die fehlende Datei — es muss, weil `install.php`
  auf einer Anlage ohne sie läuft. Ohne eine ausdrückliche Prüfung hätte
  `db.php` das geerbt: Der bisherige Fatal (`require … config.php: Failed to
  open stream`) wäre zu `new PDO('')` geworden, also zur Meldung „Datenbank
  nicht erreichbar" für ein Problem, das nichts mit der Datenbank zu tun hat.
  Gemessen und dann verhindert: `db.php` bricht ohne `config.php` weiterhin
  ab, nur mit einem besseren Satz. **Eine sechste Ausnahme wäre nach dem
  Auftrag ein Haltepunkt gewesen; sie ist nicht entstanden.**
- **AP2-b — Ein `session_start(`, nicht zwei, und die Maske statt `@`.** Die
  naheliegende Fassung `$a['still'] ? @session_start() : session_start()`
  wären **zwei** Aufrufe gewesen — Z01 stünde dauerhaft auf 2 statt auf 1,
  und der Stufe-1-Schritt aus AP10 könnte „genau einer" nie prüfen. Das `@`
  der Art `lesend` ist deshalb eine Maske um den einen Aufruf
  (`error_reporting()` ohne `E_WARNING` und `E_NOTICE`, danach
  wiederhergestellt). Für die drei anderen Arten bleibt jede Warnung
  sichtbar.
- **AP2-c — `migration_lib.php` wird an EINER Stelle doch angefasst**,
  obwohl E-ZE-04 gelaufene Migrationen in Ruhe lässt. In
  `2026_07_22_tag_zuordnung` stand `global $CFG`; die Globale gibt es nicht
  mehr. Stehen geblieben wäre die Zeile **nicht neutral** — sie fiele auf
  `Europe/Berlin` zurück, und zwar still, und ordnete auf einer Anlage mit
  anderer Zeitzone die Tage falsch zu. E-ZE-04 schützt vor Risiko ohne
  Ertrag, nicht vor dem Weiterlaufen. Z15 (`information_schema` in
  `migration_lib.php`) ist davon unberührt und steht weiter auf 57.
- **AP2-d — `config_gemerktes_verwerfen()` wirft jetzt zuerst die
  Konfiguration weg.** Die vier Zeilen darunter lesen über `konfig()` nach;
  stünde das Gemerkte noch, holten sie sich genau den Stand zurück, den sie
  wegwerfen sollen. Das ist derselbe Fehler wie S2/AP7, nur eine Ebene
  tiefer.
- **AP2-e — Die Drift bei `secure` bleibt und wird sichtbar.** `app` und
  `passwort` fest, `lesend` und `einrichtung` HTTPS-abhängig — also die
  beiden Arten, die auf einer Anlage laufen können, deren HTTPS-Lage erst
  hergestellt wird. Backlog Nr. 251 ist entsprechend nachgezogen: Die
  Entscheidung für Schritt 18 ist jetzt eine Zeile in einer Tabelle.

*Probleme und wie sie gelöst wurden:*

1. **Die Sitzungshärtung wurde rot, und der Befund war sachlich falsch.**
   Das Werkzeug sucht `use_strict_mode` in den **zwölf** Zeilen vor dem
   Aufruf; mein Begründungskommentar stand dazwischen und schob sie aus dem
   Fenster. **Gelöst,** indem der Kommentar über die beiden Zeilen wanderte
   statt zwischen sie — nicht, indem die Schwelle gelockert wurde. Genau
   diese Falle steht seit Schritt 16 in `install.php` beschrieben; sie ist
   hier zum zweiten Mal zugeschnappt, und der Kommentar sagt das jetzt auch
   in `sitzung_lib.php`.
2. **Die Vollständigkeit sprang von 398 auf 399** — ein Auslassungszeichen
   (U+2026) in einem neuen Kommentar in `db.php`. **Dieselbe Falle, dieselbe
   Datei, einen Tag nach Schritt 16**, wo sie im Prüfdokument steht. Ersetzt
   durch drei Punkte, wieder 398. Der Wert ist die Schwelle und nicht null;
   wer ihn nicht kennt, hält 399 für unauffällig.
3. **E-ZE-06 hätte die Statusseite stumm machen können.**
   `sitzung_ablage_stand()` ist ein `static`, den nur `sitzung_ablage()`
   füllt — und `plattform_pruefen()` liest ihn. Fiele der Aufruf aus
   `db.php` weg, ohne dass ein Sitzungsstart ihn ersetzt, meldete die
   Statusseite dauerhaft „nicht gelaufen". **Vor dem Umbau belegt:** Beide
   Aufrufer von `plattform_pruefen()` haben vorher eine Sitzung gestartet —
   `betrieb_status.php` über `auth_guard.php` (`app`), `install.php` über
   `einrichtung` an Zeile 144, die Prüfung selbst erst an Zeile 317. Der
   Aufräumjob ist nicht betroffen: `sitzung_aufraeumen()` hängt an
   `sitzung_ablage_pfad()` (gerechnet), nicht an `session_save_path()`
   (gesetzt).
4. **Der Ladezyklus ist gemessen, nicht behauptet.** Über
   `get_included_files()`: `konfig_lib.php` zieht **keine** Datei nach,
   `sitzung_lib.php` beim Laden ebenfalls keine, `plattform_lib.php` zieht
   `email_lib.php` und `php_mindest.php` nach und **erreicht `db.php`
   nicht**. Der Zyklus ist durch E-ZE-06 kürzer geworden, aber nicht fort:
   `sitzung_starten()` wird aus Seiten gerufen, die `db.php` geladen haben.
   Die Bedingung gilt unverändert und steht als Kommentar in beiden Dateien.

*Nachtrag vom 21.09.2026 — die Browserprüfung ist nachgeholt.* Der
Auftraggeber hat angewiesen, die Prüfwerkzeuge zu beschaffen. Mit
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` steht seither eine
**vollständige Anlage in der Arbeitsumgebung** (MariaDB, `php -S`, socat für
TLS, Demo-Bestand: 106 Einsätze, 21 Diensttage, 2 Geräte). **Die Abnahmeliste
ist damit gefahren**, nicht mehr offen:

- **Bilderlauf, 62 Seiten in acht Breiten: 496 Einzelbilder, 0 Überlauf,
  0 Konsolenfehler, 0 Knöpfe falscher Höhe**, Rückgabe 0.
- **Die vier Sitzungsarten live über HTTPS:** `app` → `PHPSESSID` secure
  Strict · `passwort` → **`EDPWSESS`** secure Lax · `einrichtung` →
  `PHPSESSID` Lax. Alle HttpOnly.
- **F-ZE-2, der eigentliche Beleg:** 40 anonyme Abrufe → **0 neue
  Sitzungsdateien**; vier öffentliche Seiten HTTP 200 mit **0 `Set-Cookie`**.
  Angemeldet zeigen **5 von 5** dieser Seiten etwas anderes als abgemeldet —
  `impressum.php` sogar „Du bist mit Verwaltungsrechten angemeldet", die
  Rolle kommt also weiterhin aus der Datenbank.
- **Die Härtung wirkt** — das stand im Prüfdokument als „nicht messbar":
  Eine untergeschobene Sitzungskennung wird verworfen, es kommt eine andere
  zurück, und unter der vorgegebenen entsteht keine Datei.
- **A-9** (`konfig_verwerfen()` greift) **8/8**, **A-12** (Proxys über
  `konfig()`) **7/7**, Ladezyklus 7/7, `konfig_lib` 13/13.
- **Abmelde-Probe** V-10 erfüllt, **Kopplungsprobe** 76/0.

*Ein Fund im eigenen Werkzeug, und die Anlage hat ihn gefunden:* Nach dem
Einrichten meldete die Zählung **Z31 über der Decke** — 'base_url' außerhalb
der drei erlaubten Dateien, gefunden in `server/config.php`, wo `base_url`
selbstverständlich steht. Die Datei gehört nicht zum Repositorium (sie steht
in `.gitignore`), aber das Zählmittel liest das Dateisystem. **Die Folge wäre
gewesen: Eine rote Registerzeile, ohne dass sich eine Zeile Code geändert
hat** — und zwar nur bei der Instanz, die gerade eine Anlage eingerichtet
hat. `tools/wortliste/` löst genau das seit jeher mit einer Ausnahme und
einer Begründung; beide sind übernommen, in `tools/zaehlung/` **und** in
`tools/sitzungshaertung/`, deren Dateizahl dieselbe Schwankung hatte
(135 statt 134). Selbstprobe der Zählung jetzt **34 von 34** — der neue Fall
hält die Ausnahme fest.

*Zwei Funde, beide NICHT von AP2 — und beide vorher unsichtbar:*

- **Backlog Nr. 254, am selben Tag behoben:** Die Ratenprobe erwartete fünf
  Töpfe mit Leiter, es sind **sechs** (`blatt` kam mit Web 20.24.0, die
  Erwartung stammte aus 20.11.0). Der Befund stand einen Monat und auch auf
  `main`; die Probe braucht eine laufende Anlage und hängt nicht in Stufe 1,
  also hatte sie niemand gefahren. **Die Behebung ändert die Bauform, nicht
  nur die Zahl:** Die Liste `TOEPFE_MIT_LEITER` ist der Sollwert, und der
  Satz rechnet seine Zahl aus ihr — dieselbe Zahl stand vorher zweimal da,
  einmal als Wort und einmal als Aufzählung. Lauf danach: **50 Prüfungen, 0
  Befunde.**
- **Backlog Nr. 255, am selben Tag behoben:** `lokal_einrichten.sh` kopierte
  Handbuch und `docs/bilder/` nicht nach `server/doku/` — der Schritt steht
  nur in der Auslieferungskette. Der erste Bilderlauf meldete dadurch **48
  Konsolenfehler**, die es auf einer ausgelieferten Anlage nicht gibt. Nach
  dem Kopierschritt: 0.

*Was auch jetzt nicht geprüft ist:* das Verhalten auf einer **echten** Anlage
(anderer Hoster, andere `php.ini`) und `secure` an den HTTPS-abhängigen
Cookie-Arten — socat terminiert TLS, PHP sieht eine HTTP-Anfrage. Beides
steht im Prüfdokument unter N2-1 und N2-2.

*Nachtrag vom 21.09.2026, zweiter Teil — AP2 hatte einen Fehler, und die
Zählung hat ihn nicht gefunden.* Beim Fahren der Proben für AP3 fiel die
Ingestprobe auf. Ursache: **Fünf Prüfwerkzeuge unter `tools/` lasen oder
setzten die globale `$CFG`**, die AP2 entfernt hat. Eine Zuweisung an `$CFG`
scheitert nicht — sie tut nur nichts.

**Die Registerzeile Z04 hat 46 → 0 bestätigt, und der Umbau galt als
erledigt. Sie mass nur `server/`.** Das ist derselbe Fehler, den
`CLAUDE.md` 6 für die Wortliste beschreibt und den ich im Prüfdokument von
AP1 für die Wortliste sogar zitiert habe: Ein Lauf, der einen Bereich
übergeht, meldet keine Null — er meldet gar nichts.

| Werkzeug | Erwartungen | vorher offen | nachher |
|---|---|---|---|
| `ingestprobe` | 83 | 1 | **0** |
| `anteilprobe` | 55 | **22** | **0** |
| `versandprobe` | 135 | 5 | **0** |
| `wiederherstellungs-probe` | 104 → **111** | 1 (+7 übersprungen) | **0** |
| `komplettprobe` | 64 | 1 | **0** |
| **Summe** | **448** | **30** | **0** |

**Die Anwendung war nie kaputt** — `konfig('app.max_body_bytes')` liefert
exakt denselben Wert wie der alte Weg (524288, nachgemessen). Kaputt war das
Prüfwerkzeug, und zwar still.

*Behoben (Backlog Nr. 257):* `tools/konfig_stellen.php` — eine Stelle, fünf
Verbraucher, dieselbe Überlegung wie bei `tools/motor.mjs`. Sie geht den Weg
der Anwendung (`config.php` schreiben, `konfig_verwerfen()`) und legt den
Urstand **bytegleich** zurück, auch bei einem Abbruch. **Keine Hintertür in
`konfig_lib.php`** — ein zweiter Weg in Produktionscode, den nur Proben
benutzen, wäre genau das, was dieser Schritt abschafft.

*Zwei Fehler beim Bauen des Helfers, beide gemessen:* Die Abbruchsicherung
war je Aufruf angemeldet; Abschlussfunktionen laufen in Anmeldereihenfolge,
also überschrieb eine spätere den zurückgelegten Urstand — nach einem Lauf
der `versandprobe` stand ein **zufälliger `server_key`** in der `config.php`.
Und der erste Rückweg schrieb einen `var_export` statt der Bytes, womit der
**Kopfkommentar des Installers** verschwand. Beides behoben und in der Datei
begründet.

*Und die Zählung kann es künftig sehen:* **Z04 misst seit heute `server/`
und `tools/`** (Bereich `php_und_tools`). Die Zeile zählt eine Globale, die
es **nirgends** mehr geben darf. Ihr Startwert 46 galt für `server/`; die
Decke bleibt 0, und sie ist jetzt für beide Bereiche eingehalten.
`komplettprobe` trug ein lokales Feld namens `$CFG` — umbenannt in
`$kopieCfg`, weil ein lokaler Name, der wie die entfallene Globale aussieht,
die nächste Instanz in dieselbe Falle führt.

### AP3 — API-Eingang und Flash (E-ZE-15, -16; F-ZE-5)

`api_eingang()` in `db.php`; 11 der 12 Rumpf-Lesestellen unter `api/` und alle
Methodenprüfungen dort ziehen um. `flash_*()` in `session_lib.php`.
**Abnahme:** Z05 `php://input` unter `api/` **12 → 1** (`csp_bericht.php`);
Z06 `'error' => 'method'` unter `api/` **→ 0** außerhalb des Helfers; Z07
„kein JSON-Objekt" mit `payload`/`format` von Hand unter `api/` **→ 0**; Z08
`post_max_size`-Hinweis **3 → 1**; Z09 `$_SESSION['flash…']` außerhalb
`session_lib.php` **22 → 0**; je Endpunkt unter `api/` eine Anfrage mit
falscher Methode (405), leerem Rumpf (400 `leer`), Nicht-JSON (400 `format`) —
**21 Dateien, Zahl der Zellen im Prüfdokument**; Geräte-Endpunkte
**zeichengleich** (Ingestprobe, Kopplungsprobe, Android-`SenderTest`
unverändert grün); Kreisläufe 0 unerklärt; die drei Flash-Seiten im Browser
je einmal (Meldung erscheint einmal, nach Neuladen nicht mehr).

**AP3 — erledigt 21.09.2026, Web 20.28.0.** Neu: `api_methode()` und
`api_rumpf()` in `db.php` (neben `json_out()`), `flash_setzen()` und
`flash_holen()` in `session_lib.php`. Zwanzig der einundzwanzig Dateien unter
`server/api/` sind umgezogen, `api/csp_bericht.php` bleibt als benannte
Ausnahme. Drei Seiten ziehen auf den Flash um.

*Abnahmezahlen (alle gemessen, nicht geschätzt):*

| Zeile | Start | Ziel | gemessen |
|---|---|---|---|
| Z05 `php://input` unter `api/` | 12 | 1 | **1** (`csp_bericht.php`) |
| Z06 `'error' => 'method'` unter `api/` | 17 | 0 | **0** |
| Z07 „kein JSON-Objekt" von Hand | 11 | 0 | **0** |
| Z08 `post_max_size`-Hinweis unter `api/` | 3 | 1 | **0** |
| Z09 `$_SESSION['flash…']` | 22 | 0 | **0** |
| Z38 `error_log(` (E-ZE-05) | 77 | 77 | **77** |

*Entscheidungen, die in diesem Paket gefallen sind:*

- **AP3-a — Der Eingang wird ZWEI Funktionen, nicht eine.** E-ZE-15 nennt
  `api_eingang(array $o = []): array`, das Methode und Rumpf zusammen
  erledigt. Beim Messen zeigte sich, dass zwischen beiden in **allen elf**
  Rumpf-Dateien eine dritte Zeile steht, und zwar überall dieselbe:
  `api_methode()` → `csrf_check()` → `api_rumpf()`. Ein zusammengefasster
  Aufruf hätte das Rumpflesen vor die Token-Prüfung geschoben. Messbare
  Folgen: Ein Aufrufer ohne gültiges Token bekäme `400 leer` oder
  `400 format` statt `403 csrf`, und in `api/kdf_upgrade.php` liefe er am
  Demo-Ausstieg vorbei, der zwischen csrf und Rumpf steht — aus `200
  {"ok":true,"uebersprungen":"demo"}` würde `400 format`, und dafür braucht
  es nicht einmal ein falsches Token. Das wäre eine **sechste**
  Verhaltensänderung neben den fünf benannten gewesen (E-ZE-10), also ein
  Haltepunkt. Der Auftraggeber hat die Aufteilung am 21.09.2026 freigegeben.
  **Dieselbe Klasse von Fehler ist am 13.09.2026 schon einmal behoben
  worden** — der Kopfkommentar von `api/kdf_upgrade.php` erzählt es: Bis
  dahin stand der Demo-Ausstieg vor `csrf_check()`, und ein Aufruf ohne Token
  kam für das Demo-Konto mit 200 zurück, während jedes andere Konto 403 sah.
  **Abschnitt 6 ist entsprechend berichtigt:** 10c AP6 findet
  `api_methode('GET')` vor, nicht `api_eingang(['methode' => 'GET', 'rumpf'
  => 'keiner'])`.
- **AP3-b — `max_bytes` bekommt keinen Vorgabewert.** F-ZE-5 nennt `zu_gross`
  (413) als Schlüssel des Eingangs. Nachgemessen: Unter `api/` begrenzt
  **keine** Stelle die Rumpfgröße; die beiden vorhandenen `zu_gross` (in
  `export_data.php` und `import_commit.php`) sind **Inhalts**prüfungen auf
  Zeilenzahl und bleiben, wo sie sind. `app.max_body_bytes` (512 KB) ist die
  Grenze des **Geräte**-Eingangs in `ingest.php`; ein Konto-Backup ist
  zweistellig megabytegroß. Eine Vorgabe hätte hier eine Prüfung eingeführt,
  die es nicht gab. Die Option bleibt, der Vorgabewert entfällt.
- **AP3-c — Die drei Dateien mit dem Schlüssel `methode` ziehen mit um.**
  Freigegeben am 21.09.2026. `rueckfrage.php`, `schluessel_erneuern.php` und
  `schluesselblatt_pruefen.php` antworteten mit
  `http_response_code(405); echo json_encode(['error' => 'methode']); exit;`
  — deutscher Schlüssel, eigener Ausgabeweg, am gemeinsamen `json_out()`
  vorbei. Sie rufen jetzt `api_methode()`. Das ändert den Schlüssel auf
  `method` und liegt damit in F-ZE-5; kein JavaScript wertet ihn aus
  (nachgemessen über alle 40 Skripte: der einzige Vergleich auf `error` gilt
  `maintenance`). Der **Rest** ihres Ausgabewegs — 19 `echo json_encode()`
  ohne `nosniff` und ohne `no-store` — bleibt und steht als Backlog
  **Nr. 258**.
  **Nebenwirkung, benannt:** Ihre 405-Antwort geht jetzt durch `json_out()`
  und trägt deshalb zusätzlich `X-Content-Type-Options: nosniff`,
  `Referrer-Policy`, HSTS und `Cache-Control: no-store`. Das sind
  **Kopfzeilen, die dazukommen**, keine, die wegfallen; sie sind genau die,
  die diese drei Dateien nach Nr. 203 ohnehin tragen müssten, und sie
  betreffen nur den Fehlerfall „falsche Methode".
- **AP3-d — Z08 erreicht 0, nicht 1.** Das Konzept erwartete, dass ein
  `post_max_size`-Hinweis unter `api/` stehenbleibt. Der Hinweis steht jetzt
  in `api_rumpf()`, und das ist `db.php` — unter `api/` bleibt **keiner**.
  Die Registerzeile ist auf `decke_ziel` 0 gesetzt, mit Begründung im
  Register. Von den drei Fassungen gilt die **vollständigste** (mit
  `client_max_body_size`), weil F-ZE-5 „ein Hinweis statt drei Fassungen"
  sagt und nicht, welcher.
- **AP3-e — `api/adminbackup_freigabe.php` prüft die Methode jetzt vorn.**
  Die Datei hatte die Prüfung als **Auffangzeile am Dateiende**, hinter dem
  GET- und dem POST-Zweig; beide enden in `json_out()`, die Zeile war also
  nur für jede dritte Methode erreichbar. `api_methode(['GET', 'POST'])`
  steht jetzt vor beiden Zweigen. Erreichbar ist derselbe Satz Methoden,
  nachgemessen (`DELETE` → 405).
- **AP3-f — Der Flash bekommt EINEN Sitzungsschlüssel, und das ist
  nachgelesen, nicht angenommen.** E-ZE-16 legt `flash` fest. Vorher gab es
  `flash_notice` und `flash_error`, und `einstellungen.php` schrieb beide in
  zwei unabhängigen `if`-Zeilen — ein Schlüssel kann also nur tragen, was
  vorher zwei trugen, wenn nie beide gesetzt sind. Für **alle 24
  Handlungszweige** nachgelesen: Jeder ist eine `if/elseif/else`-Kette oder
  ein `try/catch`, in dem die `$notice`-Zuweisung die letzte Anweisung des
  `try` ist; eine Stelle prüft ausdrücklich `if ($error === null)`; und der
  Demo-Riegel am Anfang setzt `$action = ''`, sodass danach kein Zweig mehr
  läuft. `nachbearbeitung.php` hat zwei Handlungen, und `$action` kann nur
  eine sein. **Sie schließen einander aus.** Am Umleitungspunkt steht
  trotzdem `if ($error !== null) … elseif ($notice !== null) …` — der Fehler
  hätte Vorrang, wenn die Annahme je bräche.
- **AP3-g — `papierkorb.php` prüft beim Holen den Ton.** Die Seite hinterlegt
  nur Fehler und leitet auf sich selbst um; ein Hinweis kann dort nicht
  ankommen. Sie nimmt den Text trotzdem nur, wenn der Ton `error` ist — eine
  Erfolgsmeldung im Fehlerkasten wäre schlimmer als keine.

*Probleme und wie sie gelöst wurden:*

1. **Backlog Nr. 256 stand falsch im Backlog, und eine Freigabe hing daran.**
   Der Eintrag behauptete, vier Dateien unter `api/` prüften die
   Anfragemethode **nicht**; er war aus der Beobachtung geschlossen, dass die
   Registerzeile Z06 sie nicht zählt. Nachgemessen **in den Dateien**:
   **Alle 21 prüfen die Methode.** Z06 zählte drei davon nur deshalb nicht,
   weil ihr Fehlerschlüssel `methode` heißt statt `method`, und
   `csp_bericht.php` antwortet absichtlich mit einer stummen 204. Damit war
   die darauf gestützte Freigabe („den Eingang dort ohne Methodenprüfung
   einbauen") gegenstandslos. **Gelöst:** Der Eintrag ist auf den
   tatsächlichen Befund umgeschrieben, nach *Erledigt* verschoben und in AP3
   behoben worden; der verbliebene Teil steht als Nr. 258.
   *Die Lehre:* Eine Registerzeile, die nicht zählt, belegt **nicht**, dass
   es die Sache nicht gibt — sie belegt, dass das Muster nicht greift.
2. **`docs/Technik.md` behauptete eine Zahl, die nicht stimmte.** Dort stand,
   `grep -rn "Content-Type: application/json" server/` treffe „genau zwei
   Codezeilen". Gemessen: **sechs**. Die drei Überzähligen sind genau die
   Dateien aus AP3-c. **Gelöst:** Satz berichtigt, mit der Messung und dem
   Verweis auf Nr. 258 daneben.
3. **Die `$dayId`-Berechnung in `api/day.php` stand vor der
   `is_array()`-Prüfung.** `$dayId = isset($b['day_id']) ? … : 0;` lief auf
   `$b === null`, bevor geprüft wurde, ob überhaupt ein Feld da ist — das
   ging gut, weil `isset(null['x'])` `false` ergibt. Nach dem Umbau liefert
   `api_rumpf()` immer ein Feld, die Reihenfolge ist damit gegenstandslos.
   Der inhaltliche Schlüssel `payload` für „`day_id` fehlt oder ist ≤ 0"
   **bleibt** (E-ZE-15: inhaltliche Prüfungen behalten ihre Schlüssel).

*Prüfprotokoll AP3:*

- **Eingangsprobe gegen die laufende Anlage** (Einmalprobe, Befehlsfolge im
  Prüfdokument): **46 Zellen, 46 erfüllt, 0 offen** — je Endpunkt eine
  Anfrage mit falscher Methode (405 `method`), und bei den elf Endpunkten mit
  Rumpf zusätzlich leerer Rumpf (400 `leer`) und Nicht-JSON (400 `format`);
  dazu `csp_bericht.php` zweimal (204, leerer Rumpf) und **zwei
  Reihenfolge-Zellen**: POST ohne Token mit leerem Rumpf → `403 csrf` (nicht
  `400 leer`), GET ohne Token → `405 method` (nicht `403 csrf`).
- **Dieselbe Probe gegen den Stand vor AP3** (`git stash`): **46 Zellen, 27
  erfüllt, 19 offen.** Die 19 sind die Änderung, die F-ZE-5 beschreibt, und
  zwar aufgeschlüsselt: acht `payload` → `format`, sechs `payload` → `leer`,
  zwei `format` → `leer`, drei `methode` → `method`. **Beide
  Reihenfolge-Zellen waren schon vorher grün** — das ist der Beleg, dass die
  Aufteilung in zwei Funktionen die Reihenfolge wirklich erhält.
- **Flash-Probe gegen die laufende Anlage:** **11 Zellen, 11 erfüllt.** Je
  Seite: POST leitet um (302), die Meldung steht nach der Umleitung da, beim
  Neuladen ist sie fort; `papierkorb.php` zusätzlich „die Meldung trägt den
  Fehlerton". `nachbearbeitung.php` liefert auf dieser Anlage kein Formular
  aus (`nb_moeglich()` ist falsch, `base_id` ist längst `NOT NULL`) — das
  Token hängt an der **Sitzung**, nicht an der Seite, und kommt deshalb von
  `einstellungen.php`.
- **Kreisläufe:** `edbak` **328 771 Einzelvergleiche, 0 unerklärt, 21
  erwartet**; `csv` **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet**
  — beide Zahl für Zahl wie vor dem Paket. Sie laufen über
  `api/export_data.php`, `api/import_commit.php`, `api/backup_restore.php`,
  `api/backup_eintraege_restore.php`, `api/backup_spuren*.php` und
  `api/day.php`, also über sechs der elf umgebauten Eingänge.
- **Gerätevertrag:** Ingestprobe **83 Erwartungen, 0 offen**, Kopplungsprobe
  **76, 0 offen, 0 übergangen**. Keine der fünf Gerätedateien ist angefasst
  (`git diff --name-only` nennt sie nicht).
- **Weitere Proben:** Komplettprobe **64, 0 offen** · Spurprobe **45, 0
  offen** · Jobprobe **35, 0 offen** · Ratenprobe **50 Prüfungen, 0
  Befunde** · Migrationsregister **0 ungenutzte Ausnahmen** · CSP-Prüfung
  **0 Inline-Skripte ohne Nonce**.
- **Klickprobe: 48 von 48 Wegen erfüllt, 0 verfehlt** — im **zweiten** Lauf.
  Der erste meldete 42 von 48 und sagte selbst dazu, warum: „Der Demo-Reset
  lief um 20:36:04 UTC mitten in diesem Lauf. Verfehlte Wege sind verdächtig
  — bitte wiederholen." Mit `jobs_pause(3000)` davor ist der Lauf sauber.
  Das ist dieselbe Ursache wie bei der GPX-Probe (Nr. 259) — mit dem
  Unterschied, dass die Klickprobe es **sagt**.
- **GPX-Probe: 95 Erwartungen, 4 nicht erfüllt — und zwar VOR und NACH dem
  Paket gleich** (mit `git stash` gegengemessen). Ursache liegt nicht im
  Code: Das Demo-Konto ist auf dieser Anlage heute zurückgesetzt worden
  (`app_state.demo_letzter_reset`), die Einsatz-Kennungen passen seither
  nicht mehr zum Referenzexport vom 15.09.2026 — „190 von 204 ohne
  Gegenstück". Der punktweise Vergleich hat damit **0 von 204 Dateien**
  verglichen. Backlog **Nr. 259**.
- **Nicht gelaufen, mit Grund:** Der **Android-Prüfstand**, den Abschnitt 5
  für AP3 vorsieht. `./gradlew test` bricht mit „SDK location not found" ab —
  in diesem Container gibt es kein `/opt/android-sdk`, `ANDROID_HOME` ist
  leer; mit `--offline` scheitert er davor am nicht zwischengespeicherten
  Plugin `com.android.application:8.13.2`. Das ist ein **Befund mit Zahl**,
  kein übersprungener Punkt (CLAUDE.md 6). Belegt ist der Gerätevertrag
  stattdessen doppelt: keine der fünf Geräte-Dateien steht in
  `git diff --name-only`, und Ingest- und Kopplungsprobe messen **83/0** und
  **76/0**.
- **Mailprobe: 41 Prüfungen, 1 Befund — vor und nach dem Paket gleich**
  (`git stash` gegengemessen). Der Befund betrifft Pflichtwerte im
  Beispielsatz dreier Mailvorlagen und hat mit AP3 nichts zu tun.
- **Bilderlauf, 62 Seiten in acht Breiten:** **496 Einzelbilder, 62
  Kontaktbögen** · Überlauf **0** · Konsolenfehler **0** · Knöpfe falscher
  Höhe **0** · Karten im Seitengerüst **162 geprüft, 0 außerhalb von
  `main.inhalt`** — Zahl für Zahl wie nach AP2. AP3 bewegt keinen Pixel; das
  war zu erwarten und ist jetzt belegt statt behauptet.
- **Werkzeuge:** `php -l` **135 Dateien, 0 Fehler** (134 im Repositorium plus
  die lokale `config.php`) · Wortliste **0 Treffer, 0 ungenutzte Ausnahmen,
  0 durchgerutschte Fallen** · Vollständigkeit **398** (unverändert) ·
  Kettenaufrufe **0 Befunde, 0 ungeprüft** · Sitzungshärtung **1 echter
  `session_start()`, 0 Befunde** · Zählung **38 Zeilen, 0 über der Decke,
  Selbstprobe 34 von 34, Zeilentreue über 522 Sichten 0 Abweichungen**.

### AP4 — Datenzugriff klein (E-ZE-04, -17, -18, -19)

`app_state_*` vervollständigt, Wrapper-Rümpfe umgestellt; virtuelles Gerät;
`einsatz_lib.php` mit `einsatz_laden()`; vier Rollenvergleiche auf `rolle_*()`;
`db_hat_*()` öffentlich, sechs Dateien ziehen um, `_hat_*` rufen sie.
**Abnahme:** Z10 `app_state`-SQL außerhalb `db.php` und `migration_lib.php`
**27 → 1** (`job_aufraeumen()`); Z11 `'manual-` außerhalb `db.php` **7 → 0**;
Z12 Einsatz-laden-SQL außerhalb `einsatz_lib.php` **13 → 1**
(`trash_restore_mission()`); Z13 Rollenvergleich von Hand außerhalb `db.php`
**4 → 0**; Z14 `information_schema` außerhalb `migration_lib.php` und `db.php`
**9 → Zahl der Nicht-Existenz-Abfragen** (erwartet 3 bis 4: `komplett_lib.php`
2 und `speicher_lib.php` 1 fragen Spaltenlisten und Größen, nicht Existenz;
`nachbearbeitung_lib.php` fragt einmal `is_nullable` — beim Start nachmessen
und entscheiden, ob dafür ein vierter Helfer lohnt, R83); Z15 `information_schema` in `migration_lib.php`
**Decke 57**; der Beleg zu `$pdo` je Stelle (E-ZE-17) steht im Konzept;
Demo-Reset, Jobpause, Kontolöschung im Prüfstand je einmal; Kreisläufe 0
unerklärt; Komplettprobe und Migrationsregister grün.

**AP4 — erledigt 21.09.2026, Web 20.29.0.** Neu: vier `app_state`-Helfer,
`app_state_zu_lang()`, `db_hat_tabelle()/-spalte()/-index()`,
`geraet_virtuell_kennung()`, `geraet_virtuell_sicherstellen()`,
`geraete_echt_sql()` und `GERAET_VIRTUELL_MUSTER` — alle in `db.php`; dazu
`server/einsatz_lib.php` mit `einsatz_laden()`. `schnitt_geraet()` und
`gpx_import_geraet()` entfallen.

*Abnahmezahlen:*

| Zeile | Start | Ziel (Konzept) | gemessen |
|---|---|---|---|
| Z10 `app_state`-SQL | 27 | 1 | **2** (AP4-c) |
| Z11 Literal `'manual-'` | 7 | 0 | **0** |
| Z12 Einsatz per ID | 13 → **12** (AP4-d) | 1 | **2** (AP4-b) |
| Z13 Rollenvergleich von Hand | 4 | 0 | **0** |
| Z14 `information_schema` außerhalb | 9 | 3–4 | **5** (AP4-e) |
| Z15 `information_schema` in `migration_lib.php` | 57 | Decke 57 | **54** (AP4-f) |
| Z38 `error_log(` (E-ZE-05) | 77 | 77 | **75** (AP4-g) |

*Entscheidungen, die in diesem Paket gefallen sind:*

- **AP4-a — `db_hat_*()` nehmen ein `PDO`, und zwar zwingend.** Die
  naheliegende Form wäre `db_hat_tabelle(string $tabelle)` gewesen, wie
  `app_state_lesen()` sich seine Verbindung selbst holt. Sie wäre falsch:
  `tools/schemaprobe/probe.php` lässt Migrationen gegen ein **frisch
  angelegtes** Schema laufen (`frisch()`, vier Stellen), also gegen eine
  andere Verbindung als `db()`. Ein Helfer mit eigener Verbindung fragte dort
  das falsche Schema — **und zwar lautlos**, denn `DATABASE()` hätte
  geantwortet. Die Signatur folgt damit den privaten `_hat_*`, die sie ruft.
- **AP4-b — `api/import_commit.php` ist die zweite namentliche Ausnahme von
  Z12.** Das Konzept nennt nur `trash_restore_mission()`. Beim Lesen der
  zwölf Stellen zeigte sich eine zweite, die strukturell etwas anderes tut:
  Dort ist die Abfrage eine **einmal vorbereitete** Anweisung, die in der
  Import-Schleife neben `$insE` und `$updE` bis zu **3 000**-mal ausgeführt
  wird (Grenze `count($einsaetze) > 3000`). Ein Funktionsaufruf je Zeile
  bereitete sie 3 000-mal neu vor. Das ist keine zweite Stelle derselben
  Sache mehr, sondern ein Messstand-Thema — und AP5 bringt für genau diese
  Datei einen Messstand mit.
- **AP4-c — `jobs.php` ist die zweite namentliche Ausnahme von Z10, und
  zwar wegen des Gerätevertrags.** Der Endpunkt liest `app_state` in einem
  `try/catch`, dessen `catch` mit **`500 {"error":"datenbank"}`** antwortet.
  `app_state_lesen()` fängt selbst und liefert `null`; der Vergleich
  `hash_equals('', $token)` schlüge dann fehl und die Antwort wäre „Token
  falsch" statt „Datenbank weg". Das ist eine **geänderte Antwort an ein
  Gerät** — der Vertrag verbietet sie (Abschnitt 0). Die Stelle bleibt, wie
  sie ist.
- **AP4-d — Die Regel von Z12 verlangt jetzt ein `SELECT`; der Startwert ist
  auf 12 berichtigt.** Der AP1-Wert 13 enthielt
  `api/schneiden.php:319` — `DELETE FROM missions WHERE id = ? AND user_id =
  ?`, also **keinen Ladevorgang**. Die Zeile heißt „Einsatz per ID **laden**
  mit Besitzprüfung"; ein Löschen mit Besitzprüfung ist eine andere Sache und
  gehört nicht in `einsatz_laden()`. **Nachgemessen statt gerechnet:** die
  verschärfte Regel gegen den Stand **vor** AP4 gehalten ergibt **genau 12
  Stellen**, und die `DELETE`-Zeile ist nicht darunter.
- **AP4-e — Z14 erreicht 5, und es entsteht KEIN vierter Helfer**
  (Auftraggeber, 21.09.2026). Das Konzept erwartete 3 bis 4 und nannte für
  `nachbearbeitung_lib.php` **eine** `is_nullable`-Abfrage; gemessen sind es
  **zwei**. Beide liegen in **einer** Datei — R83 zentralisiert beim zweiten
  *Verbraucher*, nicht bei der zweiten Zeile derselben Datei. Und
  `nb_moeglich()` fragt vier Tabellen bewusst in **einer** Abfrage; der
  Kommentar dort nennt die Messung (**1,071 ms gegen 0,355 ms** je
  Seitenaufbau, weil die Frage aus der Seitenleiste kommt). Ein Helfer „ist
  diese eine Spalte nullbar?" nähme genau diese Zusammenfassung wieder
  auseinander.
- **AP4-f — Z15 fällt von 57 auf 54, die Decke folgt.** Die drei privaten
  `_hat_*` reichen nur noch an `db.php` durch und nennen `information_schema`
  nicht mehr. E-ZE-04 sagt „die Zahl darf nicht steigen" — sie fällt, und die
  Decke wird mit ihr gesenkt, damit sie nicht unbemerkt zurückkommt.
- **AP4-g — `error_log(` fällt von 77 auf 75, und hier ist die Aufteilung.**
  `db.php` **4 → 6** (`app_state_zu_lang()` 1, dazu je ein `catch` in
  `app_state_setzen_mehrere()` und `app_state_loeschen()`) ·
  `adminbackup_lib.php` **2 → 0** · `konto_lib.php` **3 → 2** ·
  `serverkrypto_lib.php` **1 → 0**. Die vier entfallenen Zeilen standen in
  `catch`-Blöcken, die nach dem Umbau **unerreichbar** wären: Die Helfer
  fangen selbst und protokollieren mit demselben Schlüsselnamen, also
  derselben Auskunft. **Kein Aufruf ist umgestellt worden** (E-ZE-05); die
  Übergabezahl an 10c AP3 ist damit **75** statt 77. Die Decke bleibt bei 77
  — sie ist eine Obergrenze, kein Sollwert.
  **Zwei Zwischenstände, die die Regel belegen:** Nach dem ersten Bauen stand
  Z38 auf **79** — die neuen Helfer brachten drei eigene Längenprüfungen mit,
  jede mit demselben Satz. Das war dieselbe Doppelung, die dieses Paket
  abschafft, nur frisch gebaut; `app_state_zu_lang()` ist die Antwort darauf.
  Und mit AP4-j fiel die Zahl von 76 auf 75, weil `app_state_einmalig()`
  seinen `catch` wieder verloren hat.

- **AP4-h — `EDBAK_MARKE_MAX` ist jetzt `APP_STATE_MAX`.** Zwei Konstanten
  mit derselben 190, beide die Spaltenbreite von `app_state.v`. Der **Name
  bleibt**: `tools/wiederherstellungs-probe/probe.php` prüft an drei Stellen
  gegen ihn (Zeilen 1098, 1099, 1151). Genau diese Falle hat in AP2 fünf
  Proben zerlegt — diesmal vorher nachgesehen.
- **AP4-i — Ein optionaler Parameter `?PDO $pdo` wird NICHT gebraucht**
  (der Beleg, den E-ZE-17 verlangt). Das Konzept nennt „12 Stellen in 5
  Dateien", die mit einem übergebenen `$pdo` arbeiten, und verlangt je Stelle
  den Nachweis, dass es dieselbe Verbindung ist wie `db()`. `db()` hält die
  Verbindung **statisch** (`db.php` Z. 42–43: `static $pdo = null;`), es gibt
  also je Anfrage genau eine. Und jede der fünf Dateien holt sie sich
  unmittelbar daraus:

  | Stelle | Herkunft des `$pdo` | Transaktion offen? |
  |---|---|---|
  | `demo_lib.php` `demo_anlegen()` (Z. 299) | `$pdo = db();` Z. 302 | **ja** |
  | `demo_lib.php` `demo_entfernen()` (Z. 568) | `$pdo = db();` Z. 572 | **ja** |
  | `konto_lib.php` `konto_loeschen()` (Z. 644) | `$pdo = db();` Z. 649 | nein |
  | `auth_salt.php` (Dateiebene) | `$pdo = db();` Z. 90 | nein |
  | `registrieren.php` `reg_secret()` | `$pdo = db();` in derselben Funktion | nein |
  | `jobs_lib.php` `jobs_pause()`, `jobs_token()` | `$pdo = db();` in derselben Funktion | nein |

  Die beiden Stellen **innerhalb einer offenen Transaktion** sind der Kern
  des Belegs: Weil `app_state_setzen()` und `app_state_loeschen()` dieselbe
  statische Verbindung nehmen, laufen sie in **derselben** Transaktion — ein
  Rollback nimmt sie mit, wie vorher. Eine zweite Verbindung hätte hier
  stillschweigend außerhalb geschrieben.

- **AP4-j — `app_state_einmalig()` fängt NICHTS, anders als seine vier
  Nachbarn.** Beim Durchsehen des Diffs fiel auf, dass `auth_salt.php` darin
  steht — und das ist eine der fünf Dateien des **Gerätevertrags**. Beide
  Aufrufer (`auth_salt.php`, `registrieren.php`) hatten **nie** einen
  `try/catch`: Fehlt `app_state`, brach die Anfrage ab. Die erste Fassung des
  Helfers fing die Ausnahme und lieferte ein frisch erzeugtes, **nicht
  gespeichertes** Geheimnis zurück — je Anfrage ein anderes. Die Pseudo-Salts
  einer unbekannten Adresse wären damit nicht mehr stabil gewesen, und genau
  ihre Stabilität ist ihr Zweck: Sie sollen von einem echten Salt nicht zu
  unterscheiden sein. **Gelöst:** Der Helfer liest selbst (nicht über
  `app_state_lesen()`, das die Ausnahme an der ersten Stelle schlucken würde)
  und fängt nichts. Beide Aufrufer verhalten sich damit wie vorher.

*Probleme und wie sie gelöst wurden:*

1. **Der erste Bau hob Z38 über die Decke (79 statt 77).** Meine drei neuen
   schreibenden Helfer brachten jeder eine eigene Längenprüfung mit
   demselben `error_log`-Satz mit — dieselbe Doppelung, die dieses Paket
   abschafft, nur frisch gebaut, und das Register hat sie sofort gemeldet.
   **Gelöst:** `app_state_zu_lang()`, eine Stelle für Prüfung und Satz; sie
   ersetzt zugleich die vierte Fassung in `edbak_marke_setzen()` samt deren
   eigener Konstante (AP4-h).
2. **`jobs.php` wäre beinahe mit umgezogen.** Es stand ohne Auffälligkeit in
   der Liste der 27 Stellen. Erst das Lesen des `catch` zeigte, dass dort
   eine **Antwort an ein Gerät** hängt. Der Gerätevertrag nennt `jobs.php`
   namentlich (Abschnitt 0); die Registerzeile hat das nicht gewusst, und
   eine Liste ist kein Ersatz fürs Lesen. **Gelöst:** namentliche Ausnahme,
   Begründung in der Registerzeile (AP4-c).
3. **Die Vollständigkeit sprang auf 399 — ein U+2026 in einem neuen
   Kommentar in `db.php`.** Dieselbe Falle, dieselbe Datei, zum **dritten**
   Mal: Schritt 16 hat sie dokumentiert, AP2 ist hineingelaufen, AP4 wieder.
   Ein „…" in einer PHP-Quelle zählt das Werkzeug als „Unicode-Zeichen als
   Symbol im Markup". **Gelöst:** durch drei Punkte ersetzt, Zahl wieder 398.
   *Die Lehre, die offenbar nicht wirkt, solange sie nur dokumentiert ist:*
   Wer einen Kommentar in `server/` schreibt, tippt keine Auslassungspunkte.
4. **Eine Kleinigkeit, die nur eine Messung klärt.** `app_state_lesen()`
   bildet ein SQL-`NULL` auf `null` ab; drei der abgelösten Wrapper
   (`edbak_marke_lesen()`, `geocoder_state()`, `logo_standard()`) bildeten es
   auf `''` ab, weil sie `(string)$wert` schrieben und nur `false` prüften.
   `app_state.v` ist `NULL`-bar. Nachgesehen: **Kein einziger Schreibweg
   bindet `null`** — alle `INSERT`s binden Zeichenketten. Der Unterschied ist
   damit unerreichbar; wo er erreichbar wäre, führte er ohnehin zum selben
   Ergebnis (`'' !== '0'` und `null !== '0'` sind beide wahr,
   `(string)null === ''` fällt in beiden Fällen auf die Vorgabe zurück).

*Prüfprotokoll AP4:*

- **Kreisläufe:** `edbak` **328 771 Einzelvergleiche, 0 unerklärt, 21
  erwartet**; `csv` **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet**
  — Zahl für Zahl wie vor dem Paket. Sie laufen über `einsatz_laden()`
  (zehn umgezogene Stellen), über `geraet_virtuell_sicherstellen()` im
  Importweg und über `app_state` in `edbak_marke_*`.
- **Bilderlauf, 62 Seiten in acht Breiten:** **496 Einzelbilder, 0 Überlauf,
  0 Konsolenfehler, 0 Knöpfe falscher Höhe, 162 Karten geprüft und 0
  außerhalb von `main.inhalt`** — Zahl für Zahl wie nach AP2 und AP3.
- **Klickprobe: 48 von 48 Wegen erfüllt, 0 verfehlt** — im dritten Lauf;
  die beiden davor hat der Demo-Reset entwertet (Nr. 259).
- **Helferprobe von Hand gegen die laufende Datenbank, 13 Zellen, 13
  erfüllt:** `app_state_einmalig()` zweimal gerufen liefert **denselben** Wert
  (`wert-cd54992d`) · `app_state_loeschen()` räumt ihn weg (`null` danach) ·
  `app_state_mehrere(['ap4_a','ap4_b','ap4_gibtsnicht'])` liefert **zwei**
  Einträge, den dritten nicht · `app_state_setzen()` mit 191 Zeichen liefert
  `false` und protokolliert „erlaubt sind 190" · `einsatz_laden()` mit
  `spalten` liefert genau die zwei Spalten, mit `papierkorb => 'ja'` auf einen
  aktiven Einsatz `null`, mit fremder Kontonummer `null` ·
  `geraet_virtuell_kennung(2)` = `manual-2` · `geraete_echt_sql()` =
  `device_id NOT LIKE 'manual-%'`, mit Alias `d.device_id NOT LIKE 'manual-%'`
  · `db_hat_tabelle()` true/false richtig, `db_hat_spalte()` und
  `db_hat_index()` true.
- **Gerätevertrag:** Ingestprobe **83/0** (sie fährt `db_hat_spalte()` auf
  `rest_segments.created_at`, die einzige umgezogene Stelle in `ingest.php`),
  Kopplungsprobe **76/0**.
- **Weitere Proben:** Komplettprobe **64/0** · Spurprobe **45/0** ·
  Jobprobe **35/0** (`jobs_token()`, `jobs_pause()`) · Ratenprobe **50
  Prüfungen, 0 Befunde** · Wiederherstellungs-Probe **111/0**
  (`EDBAK_MARKE_MAX`) · Anteilprobe **55/55** (`schluessel_marke_*`) ·
  Versandprobe **135/0** (`sz_tabelle_da()`, `sz_dateien_tabelle_da()`).

### AP5 — Transaktion und Kindtabellen (E-ZE-20, -21)

Zuerst die Bauformen der 33 Stellen auszählen und hier eintragen; dann
`db_transaktion()` und der Umbau; dann die vier `einsatz_*_ersetzen()`.
**Abnahme:** Z16 `beginTransaction(` außerhalb `db.php` **33 → Zahl der
namentlichen Ausnahmen** (≤ 8, H-ZE-4); Z17 Kindtabellen-Anweisungen
außerhalb `einsatz_lib.php` und `migration_lib.php` **30 → 0**; Probe für
`db_transaktion()`: Fehler in der inneren Funktion rollt die **äußere** nicht
zurück, wenn sie fremd ist, und wirft weiter (Fallzahl im Prüfdokument);
Ingestprobe, Spurprobe, GPX-Probe grün; Kreisläufe csv und edbak 0 unerklärt;
**Messstand:** Laufzeit von `ingest.php` innerhalb der Streuung der letzten
drei Läufe (Zahlen im Prüfdokument); Schneiden und Rückgängig im Browser je
einmal.

**AP5 — erledigt 22.09.2026, Web 20.30.0.** Neu: `db_transaktion()` in
`db.php`; `einsatz_phasen_ersetzen()`, `einsatz_reas_ersetzen()`,
`einsatz_rettungsmittel_ersetzen()`, `einsatz_besatzung_ersetzen()` und
`einsatz_anweisung()` in `einsatz_lib.php`.

*Abnahmezahlen:*

| Zeile | Start | Ziel (Konzept) | gemessen |
|---|---|---|---|
| Z16 `beginTransaction(` außerhalb `db.php` | 33 | ≤ 8 Ausnahmen | **9** (AP5-c) |
| Z17 Kindtabellen-Anweisungen | 30 | 0 | **0** |

*Die Bauformen, wie E-ZE-20 sie verlangt — vor dem Umbau ausgezählt:*

| Bauform | Anzahl |
|---|---|
| beginnen · versuchen · bestätigen · bei Fehler zurückrollen und **weitergeben** | **19** (12 werfen, 7 antworten selbst) |
| dasselbe, aber der `catch` **schluckt** und setzt eine Meldung | **12** |
| **gar kein `try`** | **2** |

Dazu **42 `rollBack()`-Aufrufe, 14 hinter einer Wache, 28 ohne**.

*Entscheidungen, die in diesem Paket gefallen sind:*

- **AP5-a — Die Bauformen sind mit dem Tokenizer ausgezählt, nicht mit einem
  Muster.** Drei Anläufe mit regulären Ausdrücken ergaben **drei verschiedene
  Verteilungen**, weil geschweifte Klammern in Kommentaren und Zeichenketten
  mitzählen und `inTransaction()` in mehreren Blöcken im erklärenden Kommentar
  steht. Erst `token_get_all()` lieferte eine Zahl, die zweimal dieselbe war.
  Das ist dieselbe Regel wie in `CLAUDE.md` 6 zum Tag-Rumpf, nur an anderer
  Stelle: **Wer Struktur aus dem Quelltext liest, liest sie nicht mit einem
  Muster.**
- **AP5-b — `db_transaktion()` fragt vor dem `rollBack()` nach, ob die
  Transaktion noch steht.** Das ist kein Übereifer, sondern der gemessene
  Zustand: 28 der 42 `rollBack()`-Aufrufe standen ohne Wache. Ein `rollBack()`
  auf einer Verbindung ohne offene Transaktion wirft — **aus dem `catch`
  heraus**, womit die ursprüngliche Ausnahme verlorengeht und im Protokoll
  „There is no active transaction" steht statt des Grundes. Zwei Anlässe
  reichen: Ein DDL-Befehl bestätigt in MySQL still, und der Rumpf darf selbst
  zurückgerollt haben.
- **AP5-c — Neun Ausnahmen statt acht; H-ZE-4 ausgesetzt** (Auftraggeber,
  22.09.2026). **Drei wegen Größe oder Vertrag:** `ingest.php` (vorab gesetzt,
  Nr. 210), `backup_lib.php` (**1153 Zeilen, 145 Variablen**) und
  `api/import_commit.php` (**542 Zeilen, 78 Variablen**). Eine `use`-Liste mit
  145 Einträgen, davon einige als Referenz, ist kein Zentralisieren, sondern
  ein Rewrite mit 145 Gelegenheiten, still etwas zu ändern. **Sechs wegen
  Bauform:** `pair.php` (Gerätevertrag; `commit()` **und** `rollBack()`
  mehrfach im `try`, Antwort mitten im Rahmen), `jobs_lib.php`
  (`spur_ausduennen_eine()`: drei `rollBack(); return …` als **regulärer**
  Weg), `diensttag_zusammenfuehren.php` (ebenso) sowie `api/day.php`,
  `api/kdf_upgrade.php` und `api/schneiden.php` (`rollBack(); json_out()` im
  `try`). `db_transaktion()` setzt voraus, dass der Rumpf durchläuft **oder**
  wirft.
- **AP5-d — Zwei Schalter statt zweier Funktionsformen** (E-ZE-21). Die fünf
  Schreibwege der Kindtabellen haben nicht dieselbe Form: Das Backup schreibt
  in einen **gerade erst angelegten** Einsatz (nichts zu löschen, Besatzung
  mit `INSERT IGNORE`), das Schneiden fügt im einen Zweig ein und löscht im
  anderen. Statt `einsatz_*_ersetzen()` **und** `einsatz_*_schreiben()`
  nebeneinander gibt es `loeschen` (Vorgabe `true`) und `ignorieren`. Eine
  leere Liste mit `loeschen => true` **ist** das Löschen — das ist der
  Rückgängig-Zweig des Schneidens. Drei Funktionsformen für eine Sache wären
  nach R83 eine Verallgemeinerung auf Verdacht.
- **AP5-e — `einsatz_anweisung()`: ein Anweisungs-Zwischenspeicher, und er
  ist Bedingung, nicht Feinschliff.** `db.php` setzt
  `ATTR_EMULATE_PREPARES => false` — **jedes** `prepare()` ist ein Roundtrip
  zum Server. `api/import_commit.php` bereitete seine sieben Anweisungen
  deshalb einmal vor und führte sie je Einsatz aus, bis zu **3 000**-mal; ein
  Funktionsaufruf, der selbst vorbereitet, machte daraus bis zu **21 000**
  Roundtrips. Der Zwischenspeicher hält die **Verbindung mit**, nicht nur ihre
  `spl_object_id`: Eine freigegebene PDO gäbe ihre Kennung an die nächste
  weiter, und der Speicher lieferte dann eine Anweisung an einer toten
  Verbindung — `tools/schemaprobe/` arbeitet mit mehreren Verbindungen
  nebeneinander.
- **AP5-f — Die Phasen bekommen immer fünf Spalten.** Das Formular schrieb
  drei (`mission_id, phase, occurred_at`) und ließ `lat`/`lon` auf ihrem
  Vorgabewert; nachgesehen in `schema.sql`: `lat DOUBLE NULL, lon DOUBLE NULL`
  **ohne** `DEFAULT`, der Vorgabewert ist also `NULL`. Ausdrücklich `NULL` zu
  schreiben legt denselben Wert ab — belegt durch den edbak-Kreislauf mit
  **328 771 Einzelvergleichen, 0 unerklärt**.

*Probleme und wie sie gelöst wurden:*

1. **Ein `$pdo->commit();` blieb beim Umbau im Rumpf einer Closure stehen**
   (`einstellungen.php`, Passwortwechsel). Ein zweites `commit()` hätte
   geworfen. **Gelöst:** entfernt — und danach eine Gegenprobe mit dem
   Tokenizer über **alle** `db_transaktion()`-Closures: **0 verirrte
   `commit`/`rollBack`**. Die Gegenprobe wäre ohne den Fund nicht entstanden.
2. **Z17 zählte die neue Bibliothek mit** und blieb nach dem ersten Umzug bei
   29 statt zu fallen: `server/einsatz_lib.php` stand nicht in der
   Ausnahmeliste der Registerzeile. Derselbe Fehler wie bei Z12 in AP4.
   **Gelöst:** eingetragen. *Die Lehre:* Wer eine Zeile auf „außerhalb von X"
   schreibt, trägt X ein, bevor X entsteht.
3. **Ein latenter Fehler in `einsatz_form.php`, gefunden und behoben.** Hinter
   dem `commit()` standen noch die Höhenermittlung und die
   Rettungsmittel-Zeilen — **innerhalb desselben `try`**, dessen `catch` ein
   unbedingtes `$pdo->rollBack()` hatte. Warf eine der beiden, rollte der
   `catch` eine **bereits bestätigte** Transaktion zurück; das wirft
   seinerseits, und statt „Speichern fehlgeschlagen." gab es eine 500.
   **Das ist eine Verhaltensänderung, und sie ist gewollt:** Der Fehlerweg
   liefert jetzt die vorgesehene Meldung. Sie tritt nur ein, wenn ohnehin
   schon etwas anderes fehlgeschlagen ist.

*Prüfprotokoll AP5:*

- **Probe für `db_transaktion()`, 10 Zellen, 10 erfüllt:** Rückgabewert
  durchgereicht (`42`) · bestätigt, der Wert steht · Fehler rollt zurück
  **und** wird weitergeworfen · danach keine Transaktion offen · **verschachtelt:**
  der innere Fehler wirft weiter, die **äußere** Transaktion steht noch, das
  äußere `rollBack()` nimmt die innere Arbeit mit, und die innere bestätigt
  nicht selbst.
- **Messstand `api/import_commit.php`** (csv-Kreislauf, Wanduhr): **41,71 s
  und 41,47 s davor**, **41,78 s und 41,31 s danach** — innerhalb der
  Streuung, der Anweisungs-Zwischenspeicher trägt.
- **Kreisläufe:** `edbak` **328 771 Einzelvergleiche, 0 unerklärt, 21
  erwartet** · `csv` **10 922, 0 unerklärt, 1 271 erwartet** — beide Zahl für
  Zahl wie vor dem Paket. Sie laufen über **vier** der fünf Schreibwege der
  Kindtabellen (Import, Backup, Formular mittelbar, Schneiden nicht).
- **Gerätevertrag:** Ingestprobe **83/0** — sie fährt den umgebauten
  Uhr-Eingang samt `einsatz_phasen_ersetzen()` und `einsatz_reas_ersetzen()`.
  Kopplungsprobe **76/0**; `pair.php` ist nicht angefasst.
- **Weitere Proben:** Komplettprobe **64/0** · Spurprobe **45/0** · Jobprobe
  **35/0** · Wiederherstellungs-Probe **111/0** · Ratenprobe **50/0**.
- **Klickprobe: zweimal 48 von 48 Wegen erfüllt, 0 verfehlt** — einmal nach
  dem Umbau der Transaktionsrahmen, einmal nach dem der Kindtabellen.
- **GPX-Probe 95/4** — unverändert der Befund aus Nr. 259 (Demo-Reset), nicht
  von diesem Paket.

### AP6 — Spaltenregister `missions` (E-ZE-22)

Register und `mf_spalten()`; sieben SQL-Listen erzeugt; vier Abbildungen mit
Vollständigkeitsprobe; Stufe-1-Schritt „Spaltenregister — `schema.sql` gegen
`mf_missions_register()`".
**Abnahme:** Z18 Handlisten **11 → ≤ 4**, jede verbleibende mit Probe;
erzeugte gegen eingefrorene Listen: **7 von 7 gleich in Menge und
Reihenfolge**; `schema.sql` minus Register **= 0 Spalten**; **Byte-Vergleich
am Referenzdatensatz:** Export (csv und JSON) und Konto-Backup vor und nach
dem Paket — **gleiche Prüfsumme**, abgesehen von Zeitstempeln der Erzeugung
(die Felder sind im Prüfdokument benannt und werden vor dem Vergleich
ausgeblendet); Kreisläufe 0 unerklärt; Einspielen eines **alten** Backups (mit
Schlüssel `manual`) gelingt; `Export-Format.md`, `Backup-Format.md`: **0
geänderte Zeilen**.

**AP6 — erledigt 22.09.2026, Web 20.31.0.** Neu in `mission_fields_lib.php`:
`mf_missions_register()`, `mf_missions_gruende()`, `mf_spalten()`,
`mf_spalten_sql()`. Neu unter `tools/`: `spaltenregister/pruefen.php` (in
Stufe 1) und `spaltenregister/wegprobe.py`.

*Abnahmezahlen:*

| Zeile | Start | Ziel (Konzept) | gemessen |
|---|---|---|---|
| Z18 Handlisten `missions` | 12 | ≤ 4, jede mit Probe | **3** (AP6-b) |
| erzeugte gegen eingefrorene Listen | — | 7 von 7 gleich | **9 von 9 gleich**, Zeichen für Zeichen (AP6-a) |
| `schema.sql` minus Register | — | 0 Spalten | **0** (beide Richtungen) |
| Byte-Vergleich gegen den Stand vor AP6 | — | gleiche Prüfsumme | **447 291 Bytes, SHA-256 identisch** (AP6-e) |
| `Export-Format.md`, `Backup-Format.md` | — | 0 geänderte Zeilen | **0** (`git diff` gegen `7a55192`) |

*Das Register in Zahlen:* **41 Spalten**, **neun Zwecke** (`export` 32 ·
`backup` 35 · `backup_restore` 15 · `import_neu` 31 · `import_aendern` 28 ·
`ingest_neu` 12 · `schnitt_neu` 11 · `suchindex` 21 · `range` 12), **0
Spalten ohne Zweck und ohne Grund**.

*Entscheidungen, die in diesem Paket gefallen sind:*

- **AP6-a — Neun Zwecke statt sieben, und drei davon hat erst die Messung
  gefunden.** Das Konzept nannte sieben Listen. Beim Nachzählen waren es
  **zwölf** Stellen: dazu der Uhr-Eingang (`ingest.php`), das Schneiden
  (`api/schneiden.php`), die Wiederherstellung aus dem Backup
  (`backup_lib.php`) und die namenlose Werteliste des Imports. Alle vier
  ließen sich aufnehmen, weil ihre **Spaltenliste** aus lauter Namen besteht —
  die festen Werte stehen in der WERTELISTE (`VALUES (?,?,1,'schnitt',…)`),
  nicht in der Spaltenliste. Sie hängen jetzt an der Spalte, nicht an ihrer
  Stelle im Satz.
- **AP6-b — Drei Abbildungen bleiben, nicht vier, und jede hat ihre Probe.**
  Das Konzept ließ vier zu. Übrig sind `api/export_data.php` (Zeile → Datei),
  `api/import_commit.php` (Datei → Zeile) und `api/suchindex.php` (Zeile →
  Indexfeld). Sie rechnen **jeden Wert einzeln** um — nach Ortszeit, auf eine
  Länge, in eine Beschriftung; ein `implode()` über Spaltennamen kann das
  nicht, und eine erzwungene Erzeugung verbärge mehr, als sie spart.
  `tools/spaltenregister/pruefen.php` belegt stattdessen, dass jede genau die
  Registerspalten ihres Zwecks führt. Zwei Ausnahmeklassen, beide mit
  Begründung **im Feld**: `abgeleitet` (Spalte wird verarbeitet, hat aber
  keinen eigenen Schlüssel — `started_at` wird im Suchindex zu `day`,
  `start_hhmm`, `start_min` und `duration_s`) und `fremd` (Schlüssel kommt aus
  einer anderen Tabelle). **Eine Ausnahme ohne Treffer ist selbst ein
  Befund** — sonst wächst die Liste zu und die Probe misst nichts mehr.
- **AP6-c — Die Position steht im Register, nicht die Reihenfolge der
  Einträge.** Die Listen sind in Menge **und** Reihenfolge eingefroren: Eine
  andere Reihenfolge ändert die Spaltenfolge im CSV-Export, also in einer
  Datei, die Menschen aufheben. `mf_spalten()` verlangt deshalb je Zweck eine
  lückenlose Folge ab 0 und **wirft** bei einer doppelten oder fehlenden
  Position, statt eine stillschweigend kürzere Liste zu liefern.
- **AP6-d — Zwei Wertelisten verlieren ihre Positionsbindung.** In
  `backup_lib.php` standen 15 Spalten oben und 15 Werte darunter; in
  `api/import_commit.php` stand eine **namenlose** Werteliste aus 22
  Einträgen, die auf zwei Anweisungen mit 31 und 28 Spalten passen musste.
  Beide Kommentare warnten davor, dass ein Einschub stumm alles dahinter
  verschiebt — und diese Warnung war die einzige Sicherung, die es gab;
  passiert ist es nie. Jetzt trägt jeder Wert seinen Spaltennamen. **Die
  Schreibreihenfolge im Quelltext bleibt die alte**, weil `pruef_text()`,
  `pruef_zahl()` und `edbak_geraet_art()` ihre Beanstandungen an den
  Prüfbericht anhängen: Wer sie umsortiert, sortiert den Bericht um, den
  jemand neben die Datei legt.
- **AP6-e — Der Byte-Vergleich ist gegen den Stand VOR AP6 gefahren, nicht
  gegen den letzten Commit.** `git checkout 7a55192 -- server/` legt den Stand
  Web 20.30.0 auf dieselbe laufende Anlage; verglichen werden die
  Serverantworten, die AP6 anfasst — Export mit und ohne personenbezogene
  Angaben, Suchindex, drei Monate der Zeitraumansicht. **447 291 Bytes,
  dieselbe SHA-256.** Gegen den letzten Commit allein hätte der Vergleich nur
  die halbe Änderung gesehen.
- **AP6-g — Was das Register beim Hinlegen der Listen sichtbar gemacht hat.**
  Zwei Befunde, beide **vorgefunden**, keiner von diesem Paket verursacht,
  beide nicht behoben (E-ZE-10: kein Verhalten ändern):
  **(1)** `other_resources` steht in keinem Zweck — die Spalte ist seit der
  Migration 2026_07 tot, wurde damals nur nicht gelöscht, und ging bis
  Web 12.x über `SELECT *` in jedes Backup. **(2)** `site_ele_m` steht im
  Backup, aber in **keiner** Einspielliste — weder in `backup_restore` noch
  unter den `$extraCols` aus dem Feldkatalog. Der Wert kommt trotzdem wieder,
  weil `edbak_restore()` ihn nach dem Bestätigen aus den Phasenkoordinaten
  **neu rechnet** (`compute_site_elevation()`). Deshalb meldet der
  edbak-Kreislauf auch nichts. Der Satz im Kopf von `backup_lib.php`
  („kommt beim Einspielen nicht zurück") ist so nicht vollständig und ist
  mit diesem Paket berichtigt worden — er beschrieb den Transport und
  verschwieg die Rechnung dahinter.
- **AP6-f — `api/mission.php` bekommt keinen Zweck.** Es liest `SELECT *` und
  gibt die Zeile weiter, wie sie ist. Ein Zweck wäre dort eine Liste, die
  niemand braucht — und die beim nächsten Spaltenzuwachs vergessen würde.

*Probleme und wie sie gelöst wurden:*

1. **Die Vollständigkeitsprobe hätte grün gemeldet, ohne etwas zu können.**
   Drei eingebaute Fehler zeigten, dass sie beißt (fehlender Schlüssel,
   fremder Schlüssel, tote Ausnahme — je ein Befund). Danach ist die Probe als
   `--selbstprobe` fest verdrahtet: **16 Zellen**, darunter vier Gegenproben,
   die **keinen** Befund ergeben dürfen. Sie hängt in Stufe 1 vor dem
   eigentlichen Lauf — dieselbe Reihenfolge wie beim Migrationsregister.
2. **Der Leser des Array-Literals muss über den Tokenstrom gehen.** An allen
   drei Abbildungen stehen Kommentare, die Spaltennamen nennen; ein Muster
   über den Quelltext zählte sie mit. `token_get_all()` sieht den Unterschied.
   Das ist dieselbe Regel wie in AP5 (AP5-a) und in `CLAUDE.md` 6.
3. **Z18 fiel von 9 auf 3, aber nicht nur, weil Listen verschwunden sind.**
   Bei `backup_lib.php` **nennt die Stelle weiterhin 15 Spalten** — als
   Schlüssel einer Wertekarte. Die Zählregel („zehn Spaltennamen dicht
   beieinander") greift dort nicht mehr, weil Kommentare den Lauf
   unterbrechen. *Das ist ehrlich zu sagen:* Die Zahl belegt für diese Stelle
   nicht, dass die zweite Liste fort ist — das belegt der Quelltext (es gibt
   nur noch eine) und die Wegprobe.
4. **Die Ellipse `…` in einem neuen Kommentar** trieb die Vollständigkeit von
   398 auf 399. Vierter Fall derselben Falle in diesem Schritt.
   **Gelöst:** durch das Wort ersetzt.
5. **Die Wegprobe brauchte zweimal einen Anlauf.** Beim ersten Lauf suchte sie
   Ruhesegmente über `COUNT(*)` auf `track_points` — und fand keines, weil die
   Punkte in diesem Bestand als Blob liegen (`CLAUDE.md` 4: nur über
   `spur_lib.php`). Beim zweiten war das Zeitfenster des vorigen Laufs schon
   geschnitten. **Gelöst:** Die Probe misst das punktreichste Segment über
   `spur_zahlen()` und gehört an ein frisches Wegwerfkonto; der Dateikopf sagt
   das.

*Prüfprotokoll AP6:*

- **Byte-Vergleich gegen den Stand vor AP6** (Web 20.30.0, `7a55192`, dieselbe
  laufende Anlage): **447 291 Bytes, SHA-256
  `fefb84e2…e40ced86`** — identisch. Sechs Abzüge einzeln: Export mit
  personenbezogenen Angaben **182 474 B**, ohne **146 895 B**, Suchindex
  **105 442 B**, Zeitraum 2026-01/05/09 **3 942 / 4 474 / 3 948 B**.
- **Die neun erzeugten Anweisungen gegen die alten**, Zeichen für Zeichen
  nachgerechnet statt angenommen: `ingest_neu` und `import_neu` **identisch**,
  `schnitt_neu`, `import_aendern`, `export` (mit **und** ohne
  personenbezogene Angaben) **identisch nach Umbruchnormierung**;
  Platzhalterzahlen **29 = 29** (INSERT) und **28 = 28** (UPDATE).
- **Registerprobe:** Schema 41, Register 41, **0** in die eine und **0** in
  die andere Richtung; **0** Spalten ohne Zweck und ohne Grund;
  Vollständigkeit der drei Abbildungen **38 / 22 / 30 Schlüssel, 0 fehlend, 0
  überzählig, 0 tote Ausnahmen**; Werteliste des Imports passt auf beide
  Anweisungen (**22 = 22**). **Selbstprobe 16 von 16.**
- **Wegprobe** (`tools/spaltenregister/wegprobe.py`, gegen ein frisches
  Umlaufkonto): **34 Erwartungen, 0 nicht erfüllt.** Sie fährt die zwei
  Anweisungen, die kein Kreislauf abdeckt — den Schnitt (11 Zellen: `origin`,
  `final`, `uhr_gesperrt`, virtuelles Gerät, gewanderte Punkte, Phasen, und
  als **Gegenprobe**, dass der Schnitt keine Einsatzfelder füllt) und den
  UPDATE-Zweig des Imports (22 Zellen in zwei Durchgängen: erst alles gesetzt,
  dann die vier Felder unter der Export-Schranke **weggelassen** — sie müssen
  stehenbleiben, `bw_unit` daneben muss geleert werden. **Säße die Schranke
  eine Spalte daneben, fiele genau diese Zelle.**)
- **Export-Schranke im Betrieb:** mit Flag **38 Schlüssel**, `site_ele_m` 85 ·
  `bw_info` 10 · `other_ema` 6 · `pat_blob` 96 belegt; ohne Flag **38
  Schlüssel**, alle vier **0** belegt, alles außerhalb der Schranke
  unverändert (`transport_dest` 77, `manual` 101, `source` 101, `geraet_art`
  90, `day` 101). Die Schlüsselmenge ist in beiden Fällen dieselbe.
- **Kreisläufe:** `edbak` **328 771 Einzelvergleiche, 0 unerklärt, 21
  erwartet** (zweimal gefahren) · `edbak-alt` — **das alte Backup mit dem
  Schlüssel `manual`** — **287 852, 0 unerklärt, 795 erwartet** · `csv`
  **10 922, 0 unerklärt, 1 271 erwartet**. Der csv-Umlauf fährt dabei den
  erzeugten INSERT (**101 Einsätze angelegt**) und den erzeugten Export (101
  exportiert), der edbak-Umlauf die erzeugte Wiederherstellung (**106
  Einsätze übernommen**).
- **Gerätevertrag:** Ingestprobe **83 Erwartungen, 0 nicht erfüllt** — sie
  fährt den erzeugten `ingest_neu`-INSERT.
- **Weitere Proben:** Kopplungsprobe **76/0** · Komplettprobe **64/0** ·
  Spurprobe **45/0** · Jobprobe **35/0** · Ratenprobe **50 Prüfungen, 0
  Befunde** · Wiederherstellungs-Probe **111/0** · Klickprobe **48 von 48
  Wegen erfüllt, 0 verfehlt**.
- **Stufe-1-Mittel:** `php -l` **136/0** · Wortliste **0 Treffer außerhalb der
  Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** ·
  Vollständigkeit **398** (unverändert) · Kontraste **22 Paare, 0 verfehlt** ·
  Kettenaufrufe **45 Aufrufe, 0 Befunde, 0 ungeprüft** · Zählung **38 Zeilen,
  0 über der Decke**, Selbstprobe **34/34**.
- **Bilderlauf: 496 Einzelbilder, 62 Kontaktbögen, 0 Überlauf, 0
  Konsolenfehler, 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px); 162 Karten im
  Seitengerüst, 0 außerhalb von `main.inhalt`.
- **GPX-Probe 95/4** — unverändert der Befund aus Nr. 259 (Demo-Reset dieser
  Anlage), nicht von diesem Paket.
- **`error_log(` unverändert bei 77** (E-ZE-05) — dieses Paket hat keine Zeile
  umgeschrieben.

### AP7 — Zeit und Zahl in PHP (E-ZE-23; F-ZE-1, F-ZE-3)

`format_lib.php`; Umzug der Aufrufer.
**Abnahme:** Z19 `edbak_groesse_text(` **42 → 0**, `function groesse_text`
**= 1**; Z20 relative Zeit von Hand **2 → 1**; Z21 `number_format(` in
deutscher Form außerhalb `format_lib.php` **27 → 0**; Z22 Byte-Division für
die Anzeige **18 → namentliche Ausnahmen** (beim Start auszählen: Anzeige
gegen Rechnung); Z23 Prozent von Hand **10 → 0**; Z24 ISO-UTC schreiben
**20 → 1**; Z25 ISO-UTC lesen **9 → 1**; Z26 Datumsformat-Literale
**67 → 0** außerhalb `format_lib.php`; Z27 `date(` ohne Zeitstempel außerhalb
`install.php` **4 → 0**; **Bilderlauf 8 Breiten 0/0/0** (kein Pixel bewegt
sich — der Beleg für F-ZE-3; ausgenommen ist allein die zeitabhängige
Altersangabe auf Betrieb → Updates, E-ZE-23 — im Bilderlauf maskiert); `heute_lokal()` mit gestellter Serverzeitzone
(UTC gegen `Europe/Berlin` um 23:30 UTC): **2 Fälle, 2 richtig**;
`zeit_relativ()` gegen `status_alter()` an den Grenzen 0 · 89 · 90 · 5 399 ·
5 400 · 172 799 · 172 800 s: **7 von 7 gleich**; gegen die entfallene Kopie
aus `betrieb_updates.php` weichen **genau** die zwei in E-ZE-23 benannten
Bereiche ab.

> **Zwei Startzahlen dieses Absatzes waren falsch und sind berichtigt**
> (22.09.2026, beim Beginn von AP7): Z19 stand auf **43**, gemessen sind
> **42**; Z21 stand auf **26**, gemessen sind **27**. Beide Zahlen stammen
> aus dem Befund von Nr. 202 und sind bei der Eichung in AP1 nachgemessen
> worden — die Registertabelle in Abschnitt 4 trug seither die richtigen,
> dieser Absatz nicht. **Es gilt das Werkzeug.**

**AP7 — erledigt 22.09.2026, Web 20.32.0.** Neu: `server/format_lib.php`
(dreizehn Funktionen) und `server/assets/format.js` (`EdFormat`, zwei
Funktionen). Entfallen: `edbak_groesse_text()`, `apk_groesse()`,
`plattform_groesse()`, `stat_zahl()`, `stat_anteil()`, `status_alter()` und
die Inline-Zeitkopie in `betrieb_updates.php`.

*Abnahmezahlen:*

| Zeile | Start | Ziel (Konzept) | gemessen |
|---|---|---|---|
| Z19 `edbak_groesse_text(` | 42 | 0 | **0** |
| Z20 relative Zeit von Hand | 2 | 1 | **1** (`format_lib.php` selbst) |
| Z21 `number_format(` deutsche Form | 27 | 0 | **0** |
| Z22 Byte-Division | 18 | „AP7 nennt" | **5** (AP7-c) |
| Z23 Anteil von Hand | 10 | 0 | **2** (AP7-d) |
| Z24 ISO-Marke schreiben | 20 | 1 | **2** (AP7-e) |
| Z25 ISO-Marke lesen | 9 | 1 | **2** (AP7-e) |
| Z26 Datumsformat-Literale | 67 | 0 | **3** (AP7-e, AP7-f) |
| Z27 `date(` ohne Zeitstempel | 4 | 0 | **0** |

**205 Fundstellen umgestellt, 15 namentlich stehengelassen, 38 Dateien
berührt, 36 `require`-Zeilen ergänzt und 2 entfernt.**

*Entscheidungen, die in diesem Paket gefallen sind:*

- **AP7-a — Der Umbau lief gefächert, das Messen auch** (E-ZE-25). Elf
  überschneidungsfreie Dateigruppen, jede mit einem **zweiten Agenten, der
  den Diff adversarisch gegenlas**. Vorher neun Messagenten, einer je
  Zählzeile, dazu zwei für Sonderfragen. Zusammen 33 Agenten, 4,1 Mio Token.
  Was das gebracht hat, steht unter *Probleme*: Zwei Fehler und dreizehn
  Nachlässigkeiten, die ein einzelner Durchgang nicht gefunden hätte — und
  drei Zahlen, die im Konzept falsch standen.
- **AP7-b — `fmt_local()` zieht nach `format_lib.php`**, unter demselben
  Namen. Nicht vorgesehen, aber zwingend: `datum_text()` und
  `datum_zeit_text()` bauen darauf auf, und `format_lib.php` darf `db.php`
  nicht laden — `install.php` erreicht sie über `plattform_lib.php`, bevor es
  eine `config.php` gibt. Die Alternative wäre die Rechnung ein zweites Mal
  gewesen. Alle 113 Aufrufer in 37 Dateien merken nichts, weil `db.php` die
  neue Datei lädt. **`local_to_utc()` bleibt in `db.php`:** Sie liest einen
  Formularwert, um damit zu rechnen — die andere Richtung.
- **AP7-c — Z22 landet bei 5, und die Zahl kommt aus der Auszählung**, wie
  das Konzept es vorsah. Vier Stellen in `betrieb_server.php` (eine ist ein
  Vergleichswert, der nie ausgegeben wird; drei sind **Formularwerte**, die
  derselbe POST-Zweig mit `is_numeric()` und `(float)` zurückliest — der
  **Punkt** als Dezimaltrenner ist dort Bedingung des Vergleichs, und
  `groesse_text()` schriebe ein Komma; das Formular wäre nicht mehr
  abzuschicken, zu merken erst beim Speichern). Dazu `gpx_lib.php`: ein
  `sprintf('%.1f')` mit Punkt statt Komma, dessen Umstellung eine weitere
  sichtbare Textänderung wäre.
- **AP7-d — Z23 landet bei 2 statt 0, und zwei Funktionen statt einer.** Von
  den zehn Handrechnungen runden **fünf ab** (sie lösen eine Schwelle aus),
  **drei kaufmännisch** (sie werden nur gelesen) und **zwei gar nicht** (sie
  rechnen eine CSS-Länge). Eine gemeinsame Funktion ohne Rundungsschalter
  verschöbe den Auslösezeitpunkt der Speicher-Warnmail um bis zu einen
  Prozentpunkt — das wäre eine weitere Ausnahme gewesen. Die zwei CSS-Längen
  bleiben: Ein gerundeter Strich wanderte um bis zu einen Prozentpunkt.
- **AP7-e — `wartung_lib.php` wird nicht angefasst**, drei Stellen (Z24, Z25,
  Z26). Ihr Dateikopf sagt als Eigenschaft 2 zu, **nichts** zu laden: Sie
  trägt den Wartungsmodus gerade dann, wenn der Rest ersetzt wird, und
  benutzt deshalb `function_exists()` statt `require`. Ein `require_once` auf
  `format_lib.php` bräche diese Zusage — und fiele erst auf, wenn eine
  Migration halb durchgelaufen ist.
- **AP7-f — Zwei Zeitstempel bleiben ohne Zonenumrechnung**
  (`admin_installation.php`, `rechtstexte_lib.php`). Beide formatieren einen
  Unix-Zeitstempel mit `date('d.m.Y', $ts)` und rechnen bewusst nicht in die
  App-Zeitzone um. Eine Umstellung auf `datum_text()` wäre eine weitere
  sichtbare Ausnahme gewesen. **F-ZE-1 nennt ausdrücklich nur `date()` OHNE
  Zeitstempel** — und die vier Stellen sind umgestellt (Z27 = 0).
- **AP7-g — Vertragsdateien werden umgestellt, weil die Zusage der ANTWORT
  gilt** (Auftraggeber, 22.09.2026). `gpx_lib.php`, `gpx.php`, `pair.php`,
  `ingest.php` und `jobs_lib.php` gehen über `format_lib.php`; die
  Bytegleichheit ist je Stelle nachgerechnet, nicht behauptet. Die beiden
  Zeilen in `pair.php` stehen nachweislich **hinter** `antwort_abschliessen()`
  und damit außerhalb der Antwortform.
- **AP7-h — Das Paar „X von Y MB" behält seine gemeinsame Einheit**
  (Auftraggeber, 22.09.2026) und wird `groesse_paar_text()`. Vier wortgleiche
  Stellen — Verwaltung, Kontoseite, Warnmail und die 507-Antwort an das
  Gerät. `groesse_text()` je Wert hätte aus „0 von 250 MB" ein „312 KB von
  250,0 MB" gemacht: genauer, aber eine sichtbare Änderung an vier Stellen
  **und** im Antworttext an das Gerät.

*Probleme und wie sie gelöst wurden:*

1. **Zwei Fehler, beide von den Gegenlesern gefunden.**
   **(a)** `tools/wiederherstellungs-probe/probe.php` rief an drei Stellen
   `edbak_groesse_text()`, das es nicht mehr gab. **Der Zähler misst nur
   `server/` und sah sie nie** — die Probe wäre erst beim nächsten Lauf mit
   „Call to undefined function" gescheitert. Behoben; die Probe läuft wieder
   (111/0). *Die Lehre:* Eine Zählzeile, deren Bereich `server/` ist, belegt
   nichts über `tools/`.
   **(b)** E-ZE-26 stand an drei Stellen dokumentiert und war im Code **nicht
   umgesetzt** — die JS-Zeile war absichtlich aus der Fächerung
   herausgenommen und danach liegengeblieben. Behoben.
2. **Ein Leerfall wäre nicht zeichengleich gewesen.** Ein Agent stellte
   `fmt_local($x,'d.m.Y') . ' um ' . fmt_local($x,'H:i')` auf
   `datum_zeit_text($x, ' um ')` um. Für jeden echten Zeitpunkt ist das
   gleich (75 086-mal nachgemessen), für den **leeren** nicht: alt „– um –",
   neu „–". Zurückgenommen auf die Zweiaufrufform. *Die Lehre:* Eine Funktion
   mit Frühausstieg ersetzt eine Verkettung nur dort, wo der Leerfall nicht
   vorkommt.
3. **Meine eigene Gegenprobe fand einen Unterschied im Nullfall.** Beim
   Umstellen zweier Prozentrechnungen in `wiederherstellen.php` wäre
   `100 * v / max(1, 0)` zu `prozent_wert(v, 0)` geworden — 100·v gegen 0.
   Der Fall ist nicht erreichbar (ohne Datei kein Versatz), aber
   Zeichengleichheit wird **belegt und nicht erschlossen**: `max(1, …)` bleibt
   stehen, 200 004 Fälle einschließlich Nullfall, 0 Abweichungen.
4. **Drei Zahlen im Konzept stimmten nicht.** Z19 stand auf 43 (sind 42 — die
   43. Fundstelle ist die Definition), Z21 auf 26 (sind 27), und „8 Aufrufe
   von `status_alter()`" sind 7, wieder wegen der Definition. Alle drei
   berichtigt; es gilt das Werkzeug.
5. **Meine eigene Arbeitsanweisung war an einer Stelle falsch.** Sie sagte
   „SIEBEN STELLEN bleiben stehen" und nannte dann sechs Zeilennummern. Der
   Agent hat nachgezählt, sechs gefunden und **nichts hinzuerfunden** — und
   es gemeldet.
6. **Der Baum war zwischen zwei Agenten rot.** Als der `adminbackup`-Agent
   `edbak_groesse_text()` entfernte, liefen drei noch nicht umgestellte
   Dateien ins Leere. Bei elf gleichzeitigen Agenten ist das unvermeidlich,
   aber es gehört gesagt: **Zwischen dem ersten und dem letzten Datei-Agenten
   ist der Arbeitsbaum nicht lauffähig.** Ein `git bisect` über diesen
   Zeitraum fände nichts.
7. **Die Auslassungspunkte, zum fünften Mal.** Ein `…` in den neuen
   Kommentaren trieb die Vollständigkeit von 398 auf 406. Behoben; die Zahl
   steht wieder auf 398.
8. **Ein Gegenleser hat sich geirrt, und das ist nachgemessen worden.** Er
   meldete einen latenten Fehler in `status_lib.php` (zwei verschiedene Leser
   für denselben Wert, einer davon blind für die ISO-Form). Nachgerechnet:
   `strtotime('<marke> UTC')` liest das abschließende `Z` sehr wohl — beide
   Leser liefern dasselbe, für beide Formen. Die Stelle ist trotzdem auf
   `iso_utc_lesen()` umgestellt, aber aus dem richtigen Grund: **ein Wert,
   zwei Leser.** Der Kommentar dort sagt beides.
9. **„263 KB MB" — der einzige Fehler, den nach dem Commit noch einer fand**
   (Web **20.32.1**). Die Fertigmeldung des Sicherns nannte die Einheit
   zweimal: `EdFormat.groesse()` bringt sie mit, das feste „ MB" hinter der
   Variablen war aus der alten Rechnung `(blob.size / 1048576).toFixed(1)`
   stehengeblieben — und der Variablenname `mb` passte noch zur alten
   Rechnung, weshalb er beim Gegenlesen niemanden stutzig machte. Beides
   behoben.
   *Warum ihn keine Prüfung hatte:* Der Formvergleich (496 Seiten,
   0 abweichende Schreibweisen) nimmt auf, was eine **aufgerufene Seite**
   anzeigt; dieser Satz entsteht erst nach einem tatsächlichen
   Sicherungslauf. Die 328 771 Einzelvergleiche des edbak-Kreislaufs prüfen
   den **Inhalt** der Datei, nicht den Satz darüber. Gefunden wurde er im
   **Protokoll** des Kreislaufs, das die Meldung mitschreibt.
   *Die Lehre — und sie gilt über AP7 hinaus:* **Meldungstexte, die erst
   nach einer Aktion entstehen, haben in diesem Projekt kein maschinelles
   Auge.** Wer einen davon umbaut, liest ihn einmal ganz. Nachgesehen wurde
   nach demselben Muster an allen Aufrufstellen der sechs Formatierer mit
   Einheit und an beiden `EdFormat`-Aufrufen — es war die eine Stelle.



### AP8 — JavaScript

`assets/api.js` (`EdApi.postJson(url, daten)`, `EdApi.postForm(url, felder)`,
beide liefern `{ ok, status, daten, meldung }` und hängen das CSRF-Token
selbst an), in der Immer-Liste von `ui.php`; `assets/format.js` (`EdFormat`);
`EdHtml.meldung(ton, text)`; `EdKarte.anlegen(el, o)`; ein Rahmen
`EdPat.listeLaden()` für die vier Seiten.
**Abnahme:** Z28 Literal `'X-CSRF'` im JS **15 → 1**; Z29 Feld `csrf` von Hand
**5 → 0**; Z34 Formatierer-Definitionen außerhalb `assets/format.js`
**12 (+2) → 0**; Z35 Seiten mit eigener `L.map(`-Präambel **4 → 0**; Z36
`EdPat.entschluessleListe(` in Seiten **4 → 0**; Z37 Meldungs-Markup im JS
**Startwert aus AP1 → 0**; **CSP-Schritt 0** (kein Inline-Skript ohne Nonce);
Klickprobe und Eingabe-Probe grün; Bilderlauf 0/0/0; im Browser je einmal:
Export, Import-Vorschau, Schneiden, Schlüssel erneuern, Rückfrage,
Schlüsselblatt-Prüfung, Entsperren mit KDF-Anhebung; ein provozierter
Serverfehler zeigt auf allen sechs JSON-POST-Seiten **denselben** Satzbau.


#### Die Vermessung vom 22.09.2026 — und was sie am Paket ändert

**Sieben lesende Agenten, 1,04 Mio Token, 308 Werkzeugaufrufe, 0
Dateiänderungen.** Je ein Thema: die 15 `X-CSRF`-Stellen, die 5
`csrf`-Feldstellen, die 14 Formatierer, die 8 Meldungsstellen, die 4
Kartenpräambeln, die 4 `EdPat`-Stellen, die Infrastruktur. Grund für die
Fächerung: Messen ist lesend und ohne Nebenwirkung — genau der Fall, den
`CLAUDE.md` 7 dafür vorsieht (E-ZE-25).

**Der Befund in einem Satz: Der zentralisierbare Kern ist schmal, und er
liegt fast überall VOR dem eigentlichen Vorgang.** Bei `EdApi` sind alle 15
Stellen im Aufruf zeichengleich — POST, genau zwei Kopfzeilen,
`JSON.stringify`, Token aus der globalen `CSRF`. Sie gehen **hinter** dem
`fetch` auseinander, auf sieben Achsen: vier Regeln dafür, was als Erfolg
gilt (8 von 15 prüfen `res.ok` gar nicht), zwei Politiken bei Nicht-JSON,
sechs Vorrangketten für die Meldung, fünf Satzbauten, sieben Anzeigewege,
und 14 von 15 zeigen im Netzfehler den englischen Browsertext.

**Daraus folgt der Schnitt in sechs Unterpakete.** Ein Paket mit sechs
Zentralen, fünfzig Stellen und vierzehn Dateien ließe sich nicht mehr
zurücknehmen, und die sechs sind verschieden weit:

| Unterpaket | Zentrale | Stellen | Reife |
|---|---|---|---|
| **AP8a** | `EdKarte.anlegen()` | Z35, 4 Seiten | **reif** — jeder Unterschied ist ein Parameter |
| **AP8b** | `EdApi.postJson()` | Z28, 15 Stellen | Entscheidung nötig (Satzbau) |
| **AP8c** | `EdApi.postForm()` | Z29, 4+1 Stellen | Entscheidung nötig (Schnittstelle `schluessel.js`) |
| **AP8d** | `EdFormat`-Ausbau | Z34, 14 (+3 ungezählte) | Entscheidung nötig (drei sichtbare Änderungen) |
| **AP8e** | `EdHtml.meldung()` | Z37, 7 (+4 ungezählte) | Entscheidung nötig (Rohmarkup, Tonliste) |
| **AP8f** | `EdPat.listeLaden()` | Z36, 3 von 4 | Entscheidung nötig (die vierte passt nicht) |

**Fächerung je Unterpaket (E-ZE-25):** Die **Vermessung** wurde gefächert,
sieben Agenten. Der **Umbau** läuft seriell — die Zentralen fassen einander
an (`EdApi` braucht `EdHtml` für seine Meldungen; `missiontable.js` steht in
Z34 **und** Z37), und Prüfarbeit ist nach `CLAUDE.md` 7 ohnehin nicht
fächerbar. Für AP8b und AP8d ist je ein gegenlesender Agent vorgesehen, wie
in AP7.

**Was die Vermessung an Zahlen dieses Konzepts berichtigt hat:**

- Z34 steht im Text oben auf „12 (+2)", im Register auf **14**. Es sind 14;
  es gilt das Werkzeug.
- Z29 **„5 → 0" ist so nicht erreichbar.** Nur 4 der 5 Treffer sind
  CSRF-Formularfelder; der fünfte (`rueckfrage.js:168`) ist eine
  Parameterweitergabe und fällt nur über eine Schnittstellenänderung an
  `schluessel.js` (AP8c).
- Z36 **„4 → 0" verlangt, dass `einstellungen.php` mit in den Rahmen geht.**
  Die Messung sagt: Die drei Anzeigeseiten teilen wirklich einen Rahmen,
  `einstellungen.php` teilt nur den Aufruf — mit einem harten Grund.
  `hinweisUnlesbar()` wertet die **ganze** Liste aus; dort kommen Fenster zu
  250 aus einem Bestand von tausenden, und der Satz „Keiner der Einträge …"
  wäre eine Falschaussage.
- **CSP-Schritt 0 ist bereits erfüllt**, nicht erst herzustellen: 115
  `<script>`-Stellen unter `server/`, 27 davon inline, **0 ohne Nonce**.
  Zweimal unabhängig gemessen — mit `tools/cspprobe/pruefen.php` (115
  Stellen, 5 Regeln, 0 Befunde) und mit einem eigenen Lauf über das in
  `CLAUDE.md` 6 vorgeschriebene Tag-Rumpf-Muster. Ohne dieses Muster meldet
  derselbe Lauf **9 falsche Treffer**, alle neun Fließtext in Kommentaren.

**Und eine Falle, die erst der Umbau auslöst** (A1 der Infrastrukturmessung):
`ui_geruest_ende()` steht auf 28 von 30 Seiten **vor** den Seitenskripten —
aber auf `einstellungen.php` und `import.php` **danach**. Diese beiden tragen
zusammen **8 der 15** `X-CSRF`-Stellen. Eine „Immer-Liste", die `api.js`
ausliefert, erreicht sie dort also zu spät. AP8b muss das lösen, bevor es
eine Zeile umstellt.

**Drei bestehende Fehler, die die Vermessung nebenbei gefunden hat** — keiner
von Schritt 15 verursacht, keiner in AP8 zu beheben (E-ZE-10), alle in den
Backlog:

- `einstellungen.php:4158` — ein `await fetch` ohne eigenes `catch` steht
  vor der Erfolgsmeldung im großen `try`.
- `assets/schluesselblatt.js` — bei Netzausfall bleibt der Knopf gesperrt,
  das Fehlerfeld leer, der Dialog offen. Eine stille Sackgasse.
- `assets/import_ui.js:258` — `res.ok` wird nicht geprüft; eine 500 mit
  wohlgeformtem JSON gilt als Erfolg, und der Dublettenabgleich läuft
  wortlos gegen einen leeren Bestand weiter.

#### AP8a — `EdKarte.anlegen()` (Z35)

**Erledigt 22.09.2026.** `EdKarte.anlegen(el, o)` steht in
`assets/map_layers.js`; die vier Seiten `einsatz.php`, `index.php`,
`tag_spuren.php` und `zeitraum.php` rufen es. **Z35 4 → 0.**

*Entscheidungen:*

- **AP8a-a — `mitte` und `zoom` haben keinen Vorgabewert.** Vier Seiten,
  zwei Ausschnitte: `[47.7, 10.3]`/Zoom 9 (Einsatz, Tagesspuren) gegen
  `[48.5, 10.5]`/Zoom 7 (Tag, Zeitraum). Ein Vorgabewert zöge die beiden
  auf einen, und Zoom 7 gegen 9 ist der Faktor 4 in der Fläche. Wer sie
  wegläßt, bekommt einen Wurf statt einer stillen Karte am falschen Ort.
- **AP8a-b — `leaflet` wird 1:1 durchgereicht.** `{ preferCanvas: true }`
  ist die einzige Leaflet-Option im ganzen Bestand und gilt nur für die
  Zeitraumansicht (mehrere hundert Pins). Sie für alle zu setzen wäre eine
  Änderung des Zeichenwegs auf drei Seiten. Belegt: `L.map(el, undefined)`
  ist mit `L.map(el)` gleich — Leaflets `setOptions` läuft mit
  `for (var i in undefined)` null Durchläufe, nachgerechnet.
- **AP8a-c — `groesse` hat die Vorgabe `false`.** Den dritten Kartenknopf
  gibt es nur auf der Tagesübersicht (Backlog Nr. 45).
- **AP8a-d — `setView()` kommt zuerst, und das ändert die Tagesübersicht.**
  Drei der vier Seiten setzten den Ausschnitt vor den Ebenen, `index.php`
  danach. Das Argument für „zuerst" steht ausgeschrieben in den Kommentaren
  von `einsatz.php` und `zeitraum.php`: Ohne festen Ausschnitt gilt die
  Karte Leaflet als nicht bereit und rechnet Pin-Positionen nicht aus — ein
  späteres `setStyle()` scheitert dann mit „this._point is undefined", und
  genau das ist auf der Zeitraumansicht passiert. **Benannte Änderung,
  nachgemessen** (siehe Prüfprotokoll).
- **AP8a-e — Der ausdrückliche `map.invalidateSize()` der Zeitraumansicht
  bleibt stehen.** Ihr Behälter trägt `hidden`, die Karte entsteht in einer
  Fläche der Größe 0, und der `ResizeObserver` aus `attachBaseLayers()`
  kommt asynchron. Wer den Ruf für „jetzt überflüssig" hält, macht die
  Kacheln dort wieder grau.
- **AP8a-f — `ortswahl.js` bleibt draußen.** Es ist ein Modul, keine Seite;
  das Register nimmt `server/assets` ausdrücklich aus.

*Probleme und wie sie gelöst wurden:*

1. **Meine eigene Kartenprobe fand die Zeitraumansicht nicht** — und der
   Fehler lag bei der Probe, nicht am Umbau. Sie rief `zeitraum.php?j=2026`;
   der Parameter heißt `y`, und ohne ihn leitet die Seite auf `index.php`
   um (Zeile 19). Mit `?y=2026` steht die Karte: Behälter da, 10 Kacheln,
   Umschalter da, 0 Konsolenfehler.

#### AP8b, AP8c und AP8e — EdApi und EdHtml.meldung (Z28, Z29, Z37)

**Erledigt 22.09.2026.** Die drei Unterpakete sind **zusammen gebaut** worden,
und das ist eine Abweichung vom Schnitt oben, die begründet gehört: `EdApi`
soll seine Meldung zeigen, und zeigen kann sie nur `EdHtml.meldung()`. Vier
der sechs betroffenen Dateien tragen beides — eine getrennte Umsetzung hätte
dieselben Funktionen zweimal angefasst und zweimal geprüft.

*Was entstanden ist:*

- **`server/assets/api.js`** (`EdApi.postJson`, `EdApi.postForm`) — der eine
  Weg, auf dem der Browser etwas an den Server schickt. Liefert
  `{ ok, status, daten, meldung }` und **wirft nie**.
- **`EdHtml.meldung(ton, text, o)`** in `server/assets/html.js` — das eine
  Meldungs-Markup. Fünf Töne, geschlossene Liste, Wurf bei einem sechsten.

| Zeile | Start | Ziel laut Konzept | Erreicht |
|---|---|---|---|
| Z28 `'X-CSRF'` im JS | 15 | 1 | **1** — die Zentrale selbst |
| Z29 Feld `csrf` von Hand | 5 | 0 | **2** (E-ZE-27) |
| Z37 Meldungs-Markup | 8 | 0 | **4** (E-ZE-28) |

*Entscheidungen, die in diesen Unterpaketen gefallen sind:*

- **AP8b-a — Der Satzbau ist einheitlich, und das ist die siebte benannte
  Ausnahme von E-ZE-10** (Auftraggeber, 22.09.2026: „in einem Zug
  vereinheitlichen"). Er lautet überall
  `<Vorgang> ist fehlgeschlagen: <Grund>`. Vorher waren es fünf Satzbauten,
  sechs Vorrangketten und sieben Anzeigewege. **Der Vorgangsname steht genau
  einmal**; wo der Aufrufer ihn bisher im `catch` anhängte, ist der Präfix
  dort entfallen.
- **AP8b-b — `error` wird nicht mehr als Satz ausgegeben.** Es trägt
  Maschinenwörter (`leer`, `format`, `zu_gross`, `method`), keine Sätze;
  sechs Stellen setzten es bis heute unverändert in den Fließtext. Es steht
  jetzt als Kennung in der Klammer eines Ersatzsatzes — diagnostisch
  erhalten, nicht als Satz ausgegeben.
- **AP8b-c — `hinweis` und `text` kommen in die Vorrangkette.** `hinweis`
  lasen bisher **4 von 15** Stellen — und genau dort steht der Satz zu
  `post_max_size`, den `api_rumpf()` bei einem zu großen Upload schickt.
  Derselbe zu große POST zeigte an einer Stelle den vollen Hinweis und an
  einer anderen „HTTP 400". `text` ist dasselbe unter anderem Namen: Zwei
  Endpunkte (`schluessel_erneuern.php`, `schluesselblatt_pruefen.php`)
  nennen ihr Satzfeld so, **9 Stellen** gegen **32** mit `meldung`, keiner
  schickt beides. Ohne diese Zeile hätten zwei Dateien weiter an der
  Zentrale vorbeigegriffen — beide taten es, und beide Griffe sind fort.
- **AP8b-d — `ok` ist ein Transport-Urteil, kein fachliches.** Es prüft
  `daten.ok !== false` und **nicht** `daten.ok === true`, weil die lesenden
  Endpunkte gar kein `ok` schicken. Folge: Wo ein Aufrufer bisher `daten.ok`
  gelesen hat, ist `ok` allein **schwächer** als seine alte Prüfung. Zwei
  Stellen in `schluesselblatt.js` prüfen `a.daten.ok` deshalb weiter mit.
- **AP8b-e — `api.js` und `format.js` stehen im `<head>`, nicht in der
  Immer-Liste.** Siehe *Probleme*, Punkt 1.
- **E-ZE-27 — Z29 endet bei 2, nicht bei 0** (Auftraggeber: „ehrliche Zahl
  statt runder Null"). Eine davon ist die Zentrale. Die andere ist
  `rueckfrage.js`: kein Formularfeld, sondern eine Parameterweitergabe an
  `EdSchluessel.erneuern()` — ein Fehlalarm des Zählmusters. Sie fiele nur
  über eine Schnittstellenänderung, und die Schnittstelle bleibt.
- **E-ZE-28 — Z37 endet bei 4, nicht bei 0.** Zwei davon sind die Zentrale
  selbst (zwei Treffer in einer Funktion: Tonklasse und Aktionszeile). Die
  dritte ist `schneiden.js`: eine **leere Hülle** mit `data-vorher` als
  Anker, die später per `textContent` befüllt wird; sie trägt kein Symbol,
  und eines einzusetzen wäre eine sichtbare Änderung im Schnittblock
  (Backlog Nr. 271). Die vierte ist `unlock.js` und **gar keine Meldung**:
  ein `<p class="meldung">` ohne Tonklasse, ohne Symbol, ohne `role` — ein
  Fehlalarm des Musters und zugleich ein Missbrauch der Klasse
  (Backlog Nr. 272).
- **AP8e-a — `EdHtml.meldung()` übernimmt die PHP-Tontabelle und wirft bei
  einem unbekannten Ton.** Im Bestand gab es **drei** Tabellen mit **drei**
  Umfängen: PHP fünf Einträge, `einstellungen.php` vier (ohne `schutz`),
  `import_ui.js` zwei Zweige (alles außer `fehler`/`warn` wurde zum
  Hinweiszeichen). Dass das ein Defekt war und kein Geschmack, steht in
  `import_ui.js` selbst: Zwanzig Zeilen unter der eigenen Tabelle stand die
  Erfolgsmeldung **von Hand** gebaut da, mit `edSymbol('haken')`
  ausgeschrieben — weil die eigene Tabelle für `ok` den Kreis-i geliefert
  hätte. Eine Umgehung ist der Beweis für den Defekt. **Kein heute
  erreichbarer Aufruf übergibt einen Ton außerhalb der fünf**; der Wurf ist
  neu und ändert kein sichtbares Bild.
- **AP8e-b — `o.roh` ist ein benanntes Loch in der Maskierung, mit genau
  einem Verbraucher.** Die Erfolgsmeldung des Imports trägt einen
  Zeilenumbruch, eine Kleinzeile und einen Link auf den ersten Tag. Ohne
  diesen Weg wäre die Stelle nicht umstellbar gewesen und hätte als achter
  Nachbau stehen bleiben müssen. Ein benanntes Loch ist besser als ein
  ungezählter Nachbau — die eingesetzten Werte werden an der Aufrufstelle
  einzeln maskiert.

*Probleme und wie sie gelöst wurden:*

1. **Die Ladereihenfolge — der schwerste Fehler des Pakets, und er war
   meiner.** `api.js` stand zuerst in der Immer-Liste von
   `ui_geruest_ende()`, und im Dateikopf stand daneben, das trage schon,
   weil jeder `EdApi`-Aufruf in einem Zuhörer stecke. **Der Satz war
   falsch.** Ein gegenlesender Agent hat ihn mit Zeilennummern widerlegt:
   Auf `einstellungen.php` (`ui_geruest_ende()` in Zeile 4672) und
   `import.php` (354) steht diese Liste **nach** den Seitenskripten, und auf
   genau diesen beiden läuft `unlock.js` seinen Sendeweg zur **Ladezeit** —
   `ck()` bzw. `sperrstatus()` führen über `ensureContentKey()` nach
   `loeseVormerkung()`. `EdApi` wäre dort undefiniert gewesen, und der
   `ReferenceError` wäre in einen **absichtlich stillen** `catch` gefallen:
   Die KDF-Anhebung hätte auf zwei Seiten aufgehört zu laufen, ohne dass
   irgendwo etwas erschienen wäre.
   Behoben: `api.js` steht im `<head>` (`ui_seite_start()`, 47 Dateien statt
   32), `format.js` später aus demselben Grund daneben. *Die Lehre steht im
   Dateikopf:* Eine Zusage über die Ladereihenfolge ist nur so viel wert wie
   die Liste der Aufrufer, die man dafür durchgegangen ist.
2. **Zwei Dateien griffen an der Zentrale vorbei, und der Grund war ein
   zweiter Feldname auf der Serverseite.** `schluesselblatt.js` hatte einen
   eigenen Helfer `fehlersatz()`, `schluessel.js` einen Griff auf
   `antw.daten.text` — beide lasen das Feld `text`, das die Vorrangkette
   nicht kannte, und **verloren dabei genau den Vorgangsnamen**, den der
   einheitliche Satzbau vorschreibt. Gemessen: `text` schicken **9 Stellen**
   in zwei Endpunkten, `meldung` **32** in allen übrigen, keiner beides.
   `text` ist in die Kette aufgenommen, beide Griffe sind fort. Die zwei
   Namen auf der Serverseite zusammenzuführen rührt an Antwortverträge und
   gehört nicht in dieses Paket.
3. **Ein `try/catch` entfiel, das mehr umschloss als den Aufruf.** In
   `uebernehmen()` (Import) lag der **gesamte** Erfolgsweg im alten `try`.
   Weil `EdApi` nicht mehr wirft, nahm der Agent es ganz heraus — und machte
   damit aus einem Fehler in der Ergebnisanzeige eine unbehandelte
   Ablehnung: Knopf gesperrt, Anzeige auf „Übernahme läuft …", **Daten
   gespeichert**, niemand erfährt es. Behoben mit einem eigenen `try` um den
   Anzeigeblock — und einem Satz, der stimmt: Der alte sagte „es wurde
   nichts gespeichert", und das war hier schon vor dem Umbau falsch.
4. **Der Vorgangsname stand zweimal im Satz, an zwei Stellen.** In
   `einstellungen.php` liegt die erste Sendestelle im `try` des ganzen
   Exportwegs, dessen `catch` seit jeher „Export fehlgeschlagen: " davorhängt
   — mit einem zweiten Namen aus `o.vorgang` las sich das „Der Export ist
   fehlgeschlagen: Das Laden der GPS-Daten ist fehlgeschlagen: …". In
   `schluesselblatt.js` dasselbe in der Konsolenzeile des Später-Knopfs.
   Beide Male ist der innere Name entfallen; den äußeren trägt der `catch`,
   einmal, für alle Fehlerquellen seines Knopfes.
5. **Eine Erfolgsregel wurde schwächer statt strenger.** `EdApi` prüft
   `daten.ok !== false`, nicht `=== true` — sonst könnten die lesenden
   Endpunkte, die gar kein `ok` schicken, nie erfolgreich sein. Wo ein
   Aufrufer bisher `daten.ok` **las**, ist `ok` allein damit schwächer: Eine
   200 mit leerem Rumpf hätte in `schluesselblatt.js` einen bedienbaren Knopf
   ohne Felder hinterlassen und `forEach` auf `undefined` geworfen. Zwei
   Stellen prüfen `a.daten.ok` deshalb weiter mit; die Eigenschaft steht
   jetzt im Kopf von `api.js`.
6. **`tools/cspprobe/` schlug grundlos an — und hängt in Stufe 1.** Ein
   Agent schrieb `<script src>` als Fließtext in einen JavaScript-Kommentar.
   Für PHPs Tokenizer ist ein JS-Kommentar Teil von `T_INLINE_HTML`; die
   Probe sah dort ein Skript ohne Nonce. Blockkommentare im Rumpf eines
   `<script>` werden jetzt ausgeräumt, mit zwei neuen Fällen in der
   Selbstprobe — einer davon belegt, dass ein echtes Skript **hinter** einem
   Kommentar weiter gefunden wird (10 von 10, 117 Stellen, 0 Befunde).

#### AP8d — `EdFormat` für Tag, Dauer und Strecke (Z34)

**Erledigt 22.09.2026.** Vierzehn Definitionen in fünf Dateien, dazu drei
ungezählte Inline-Rechnungen. **Z34 14 → 5.**

*Entscheidungen:*

- **AP8d-a — Die drei sichtbaren Änderungen sind einzeln freigegeben**
  (Auftraggeber, 22.09.2026: „Vereinheitlichen, Änderungen benennen"):
  zweistellige Minute (**231 von 1441 Minutenwerten, 16,0 %**), kein
  „60min" mehr (**720 von 86 401 Sekundenwerten, 0,83 %**), Tausenderpunkt
  in der Streckensumme.
- **AP8d-b — Der Leerwert ist ein Parameter, kein fester Wert.** Das ist die
  Lehre aus AP7. Gemessen waren **sechs** verschiedene Leer-Antworten im
  Bestand: `null`, der Leerstring, „kein Ende" und zweimal ein fertiges
  `<span class="dash">`. Ein fester Wert in der Zentrale wäre genau der
  AP7-Fehler gewesen.
- **AP8d-c — Markup bleibt an der Aufrufstelle.** Zwei Stellen geben im
  Leerfall einen Gedankenstrich als Markup zurück. Eine dritte schiebt ihr
  Ergebnis durch `esc()` — ein `<span>` aus der Zentrale stünde dort
  buchstäblich auf dem Bildschirm.
- **AP8d-d — Z34 endet bei 5, und die fünf sind kein zweiter Rechenweg.**
  Es sind dünne Weiterleitungen, die je einen anderen Leerwert binden. Sie
  aufzulösen hieße, den Leerwert an **fünfzehn** Aufrufstellen zu
  wiederholen statt an fünf; drei von ihnen stehen außerdem im
  Export-Objekt von `EdMissionTable`, das `zeitraum.php` als Alias nimmt.
- **AP8d-e — Zwei tote Formatierer sind gelöscht** (`fmtKm` in
  `missiontable.js`, `fmtKmDe` in `zeitraum.php`), je mit nachgemessener
  Aufruferzahl 0.
- **AP8d-f — Die Formprüfung der Zentrale kommt aus `EdPat.datumDe`**, der
  einzigen Datumsfassung im Bestand, die eine hatte. Die drei anderen
  warfen bei `null` eine `TypeError`. **Das Muster hat bewusst keinen
  Endanker:** Ein ISO-Zeitstempel ergibt jetzt das richtige Datum statt
  `14T10:00:00Z.08.2026`.

*Probleme und wie sie gelöst wurden:*

1. **Eine Löschung war dateiübergreifend halb fertig.** `missiontable.js`
   nahm `fmtKm` aus seinem Export-Objekt; `index.php` zerlegte den Namen
   weiter (`const { extractOrt, fmtDur, fmtKm, … } = EdMissionTable`). Kein
   Wurf — das Zerlegen einer fehlenden Eigenschaft wirft nicht —, sondern
   eine Bindung an nichts. Genau die Sorte Rest, die bleibt, wenn man eine
   Löschung nur in ihrer eigenen Datei zu Ende denkt. Gefunden beim
   Gegenlesen, behoben.
2. **Die Zählzeile sieht nur, was auf ihrer Namensliste steht.** Eine
   unabhängige Gegenprobe über das Muster der **Rechnung** — Meter durch
   1000 mit `toFixed`, ISO-Tag zerlegt, Sekunden in Stunden geteilt — hat
   zwei weitere Fassungen gefunden, die Z34 nie sehen konnte:
   `luftlinie.js` (die **sechste** km-Fassung; umgestellt, **57 143 Werte
   zeichengleich, 0 Abweichungen**) und `schneiden.js` mit einer **dritten**
   Dauer-Schreibweise („1 h 6 min" mit Leerzeichen) samt demselben
   Rundungsfehler. Die zweite ist **nicht** umgestellt: Es wäre eine
   sichtbare Änderung, und die drei sichtbaren dieses Pakets sind einzeln
   freigegeben worden — diese war nicht darunter (Backlog Nr. 273).
3. **Mein eigener Kommentar in `format.js` behauptete etwas Falsches.** Er
   sagte, ein ISO-Zeitstempel ergebe den Leerwert. Er ergibt das richtige
   Datum — nachgemessen, und vom Gegenleser gefunden. Berichtigt.

#### AP8f — `EdPat.listeLaden()` (Z36)

**Erledigt 22.09.2026. Z36 4 → 1.**

*Entscheidungen:*

- **AP8f-a — Der Rahmen deckt drei Schritte, nicht den ganzen Ablauf.**
  Schlüssel holen, Sperrbanner setzen, entschlüsseln, zählen — das war an
  drei Seiten wortgleich. Was **danach** kommt, sind vier verschiedene
  Nachläufe mit 66, 7, 14 und 14 Zeilen, und keine zwei gleich. Ein Rahmen,
  der das mit einem Schalter zusammenzöge, wäre kein Rahmen, sondern ein
  viertes Programm.
- **AP8f-b — Er gibt zurück, statt zu entscheiden** (`{ ck, zahl }`).
  Tagesübersicht und Zeitraum kehren bei fehlendem Schlüssel zurück, die
  **Suche nicht** — sie muss ihre Trefferliste auch gesperrt zeigen und
  dabei den Altersfilter sperren, sonst sähe er benutzbar aus. Ein
  eingebauter Ausstieg hätte ihr genau diesen Weg genommen.
- **AP8f-c — `zeigeUnlesbar()` ruft der Aufrufer**, nach seiner Schleife.
  Vorher gerufen stünde die Meldung über einer Tabelle, die es noch nicht
  gibt.
- **AP8f-d — `einstellungen.php` bleibt draußen** (Z36 endet bei 1,
  Auftraggeber 22.09.2026). Es teilt nur den Aufruf: kein Banner, kein
  `zeigeUnlesbar`, Schlüssel von außen, und die Liste ist ein **Fenster zu
  250** aus einem Bestand von tausenden. Daran hängt es: `hinweisUnlesbar()`
  wertet die **ganze** Liste aus und sagt „Keiner der Einträge ließ sich
  öffnen" — über ein Fenster gesagt wäre das eine Falschaussage.

### AP9 — Nr. 57: eine Einsatztabelle (E-ZE-01, -08; F-ZE-6)

`index.php` bezieht Kopf, Zeilen, Sortierung und Sortierblatt aus
`EdMissionTable`; die eigene Zeilenerzeugung, die drei Spaltenlisten und das
eigene Sortierblatt entfallen. `api/range.php` und `api/suchindex.php`
beziehen die bedingten Spalten aus dem Register (AP6).
**Erster Prüffall — `cap_gate`:** NEF-Tag ohne Fähigkeit → **keine** Winden-
und Bergwachtspalte; Tag mit `winch` → Spalte da; dasselbe in Suche und
Zeitraum gegen den Referenzdatensatz (Fälle und Zahlen im Prüfdokument).
**Abnahme:** `tr.innerHTML`-Zeilenerzeugung in `index.php` **1 → 0**;
Spaltenlisten für Einsatztabellen **4 (6 mit den beiden API-Dateien) → 1**;
Sortierblatt-Erzeuger **3 → 1**; **Bildvergleich Tagesübersicht** vorher und
nachher, 8 Breiten — erwartet sind **genau** die Abweichungen aus Nr. 57:
Kopf „Sekundärtransport" (64 → 42 px), Ausrichtung von Alter und Beginn,
Hakenreihenfolge; **jede weitere Abweichung ist ein Befund**; Suche und
Zeitraum **0/0/0**; Spaltensatz je Seite unverändert (F-ZE-6: Zählung der
Köpfe vorher = nachher); Gleichstände: sechs gleichwertige Zeilen, zweiter
Klick → Reihenfolge bleibt 1–6 (E-ZE-01); Kacheln unverändert.


#### Die Vermessung vom 22.09.2026 — und warum AP9 so nicht gebaut wird

**Vier lesende Agenten, je ein Thema, 0 Dateiänderungen.** Sie sind
unabhängig voneinander auf denselben Befund gestoßen, und er stellt die
Prämisse des Pakets in Frage.

> **`cap_gate` erreicht die Einsatztabelle nicht. Die „bindende
> Nebenbedingung" schützt etwas, das es nicht gibt.**
>
> Nachgemessen: `mf_tagesspalten()` (`mission_fields_lib.php:49`) nimmt
> **keinen Parameter**, kennt **keinen Diensttag**, wertet **kein
> `cap_gate`** aus und cacht statisch. `mf_gates_erfuellt()` hat **zwei**
> Aufrufer, beide in `einsatz_form.php` — **keinen** in einer der drei
> Tabellen. In `index.php` kommt die Zeichenfolge `capabilit` **nullmal**
> vor, heute wie am 12.09.2026.
>
> Der Bestand zeigt es: **69 von 69 Diensttagen tragen heute die
> Windenspalte.**

Daraus folgt dreierlei, und jedes einzelne ist ein Haltepunkt:

1. **Der erste Prüffall des Pakets kann nie fehlschlagen.** Er lautet
   „NEF-Tag ohne Fähigkeit → keine Winden- und Bergwachtspalte". Wer ihn
   nach dem Umbau prüft, findet die Spalten stehen — genau wie vorher — und
   hakt ab. Umformuliert zu „an einem Tag ohne Windenfähigkeit steht keine
   Windenspalte" ist er eine **neue Zusage, die heute verletzt ist**: also
   eine Funktionsänderung mit Freigabebedarf, keine Bewahrung.
2. **Das Modul blendet datengetrieben aus, nicht fähigkeitsgetrieben.**
   `nurWenn: liste => liste.some(m => m.winch)` versteckt die Spalte an
   einem Lufttag **mit** Windenfähigkeit, an dem niemand gewindet hat. Auf
   Suche und Zeitraum über einen großen Bestand ist das gewollt; an einem
   einzelnen Diensttag mit vier Einsätzen verschwindet die Spalte fast
   täglich — und wer nachtragen will, sieht nicht mehr, dass es sie gibt.
   **Drei Verhaltensweisen sind möglich** (immer zeigen, nach Fähigkeit,
   nach Bestand), und nur eine kann gelten. Das ist die **vierte**
   Freigabefrage, die Nr. 57 neben Beschriftung, Ausrichtung und
   Hakenreihenfolge nicht stellt.
3. **Die Abnahme des Bilderlaufs ist so nicht erfüllbar.** Sie erwartet
   „genau die Abweichungen aus Nr. 57; jede weitere ist ein Befund". Mit
   einer Fähigkeitsregel an der Tabelle verlören **34 von 69** Diensttagen
   zwei Spalten — der Lauf stünde voller Befunde, die keine sind, und der
   eine echte ginge darin unter.

**Dazu vier Funde, die in keinem der beiden Dokumente stehen:**

- **Die Sortierung nach „Beginn" kippt den Nachtdienst.** `index.php`
  sortiert über `m._no`, also über die laufende Nummer, die `api/day.php`
  nach `started_at` vergibt. Das Modul sortiert über die **Zeichenkette**
  `start_hhmm`. Ein Dienst über Mitternacht — laut `docs/Handbuch.md` „der
  klassische Fall" — hat Einsätze um 23:50 und um 01:10; heute stehen sie in
  dieser Reihenfolge, nach dem Umbau stünde 01:10 davor. **Still**: keine
  Meldung, keine Lücke, nur eine falsche Reihenfolge, die richtig aussieht.
- **„Nr." ist keine Zierspalte, sondern die Verbindung zur Karte.**
  `index.php` beschriftet Pins und Popups mit `Einsatz ${m._no}`. Das Modul
  kennt `_no` nicht. Fällt die Spalte weg, steht die Nummer im Kartenpopup
  und sonst nirgends mehr.
- **Das mobile Sortierblatt zeichnet im Modul nicht neu.** `setSort()` setzt
  Schlüssel und Richtung und ruft `zeichne()` **nicht**; es funktioniert
  heute nur auf `index.php`, weil die Seite von Hand nachzeichnet. Ein
  Umbau ohne diese Reparatur nimmt der Tagesübersicht eine Funktion — und
  zwar unter 720 px, wo das Blatt der einzige Weg zum Sortieren ist.
- **Der Feldkatalog verlöre seinen Griff auf die Tagestabelle.** Heute
  erscheint ein neuer Eintrag mit `day_col` ohne Codeänderung in Kopf,
  Zelle, Sortierung und `api/day.php`. Schreibt der Umbau die drei
  Hakenspalten fest in `SPALTEN`, ist `day_col` wieder reine Dokumentation
  — ein Rückschritt hinter Backlog Nr. 10 und eine Aufweichung von
  **„Feldkatalog statt Sonderfall"** (`CLAUDE.md` 4, eine feste Zusage).

**Und eine Messgrenze, die die Abnahme betrifft:** Der Demo-Bestand kann die
entscheidende Regel auf Bestandsebene **nicht** prüfen — „mindestens ein
Einsatz mit Haken" und „mindestens ein Diensttag mit Fähigkeit" liefern bei
allen fünf Konten dasselbe Ergebnis. Der unterscheidende Fall (Fähigkeit ja,
Einsatz nein) existiert nur **je Tag**: 12 Tage für `winch`, 9 für
`bergwacht`. Für Suche und Zeitraum müsste er gebaut werden.

**Stand: AP9 ist vermessen und wartet auf vier Entscheidungen.** Gebaut ist
nichts. Der Grund steht oben: Das Paket so auszuführen, wie es dasteht,
hieße, eine Funktion neu zu erfinden und sie für einen Erhalt zu halten —
und dabei eine Sortierung zu zerlegen, die heute stimmt.

### AP10 — Abschluss und Übergabe an 10c

Stufe-1-Schritt „Zentralisierung — hält jede Sache ihre eine Stelle?"
(`tools/zaehlung/`, eingetragen in `tools/kettenaufrufe`); `CLAUDE.md`:
Regeln „neue Migrationen nur über `db_hat_*()`", „Konfiguration nur über
`konfig()`", „Sitzung nur über `sitzung_starten()`", R83 mit Verweis auf das
Register; `docs/Technik.md`: Abschnitt „Eine Stelle je Sache" mit der Tabelle
aus 2.2/E-ZE-11; Abschnitt 6 dieses Konzepts gegen den gebauten Stand
nachgezogen; Prüfdokument fertig; Einschübe (Abschnitt 8, 9) an die
einspielende Instanz.
**Abnahme:** Register **alle Zeilen ≤ Decke**, Schritt in Stufe 1 grün und
einmal **absichtlich rot** gesehen (eine zweite Stelle eingebaut, Schritt
schlägt an — Selbstprobe der Kette); Kettenaufrufe 0/0; **Übergabezahl
`error_log(`** genannt und gegen 77 erklärt; Wortliste, Vollständigkeit,
Kontraste, CSP, Migrationsregister, `php -l` wie immer mit Zahl.

#### AP10 — was gebaut ist, und was auf AP9 wartet

**Gebaut am 22.09.2026.** Keine Versionsstufe: Das Paket fasst nur
`.github/`, `tools/`, `docs/` und `CLAUDE.md` an (CLAUDE.md 2.1).

*Erledigt:*

- **Der Stufe-1-Schritt hängt.** „Zentralisierung — hält jede Sache ihre
  eine Stelle?" in `.github/workflows/pruefung.yml`, mit vorgeschalteter
  Selbstprobe. `tools/kettenaufrufe/` prüft den Aufruf gegen die
  Schnittstelle des Werkzeugs: **0 Befunde, 0 ungeprüft.**
- **Einmal absichtlich rot gesehen** — die Abnahme verlangt es, und sie hat
  recht: Ein Prüfschritt, der nie angeschlagen hat, ist ein Versprechen.
  Eine Wegwerfdatei mit einer zweiten `L.map(`-Präambel und einem zweiten
  `'X-CSRF'` ließ **zwei Zeilen über die Decke** steigen, Rückgabewert **1**.
  Nach dem Entfernen: **0 über der Decke, Rückgabewert 0.**
- **`CLAUDE.md` Abschnitt 4** trägt die Regel „Eine Stelle je Sache" mit der
  Tabelle der acht Wege, dem Verweis auf das Register — und mit den drei
  Sätzen, die man dem Register nicht ansieht: dass nicht jede Decke null
  ist, dass es eine Liste ist und kein Spürsinn, und dass seine Zeilen
  verschieden scharf messen.
- **`docs/Technik.md` 4.98a** trägt denselben Stoff für die Technikseite,
  mit der Vorher-Zahl je Sache.
- **Die Übergabezahl an 10c: `error_log(` steht bei 75**, nicht bei 77.

*Die Rechnung dazu, weil E-ZE-05 sagt, dass Schritt 15 keinen einzigen
`error_log()`-Aufruf umstellt:*

| Datei | vorher | nachher | was geschah |
|---|---|---|---|
| `adminbackup_lib.php` | 2 | 0 | **umgezogen** nach `db.php` — AP4 hat das `app_state`-Schreiben dorthin gezogen (Z10). Dieselben zwei Sätze, Präfix von `adminbackup:` auf `app_state:` |
| `db.php` | 4 | 6 | die beiden von oben |
| `konto_lib.php` | 3 | 2 | **zusammengelaufen**: Die Stelle ruft jetzt `app_state_loeschen()`, und die Funktion „fängt und protokolliert selbst" |
| `serverkrypto_lib.php` | 1 | 0 | ebenso, über `app_state_setzen()` |
| **Summe** | **77** | **75** | |

**Kein Protokolleintrag ist verlorengegangen.** Zwei Aufrufstellen sind in
die zentralen `app_state`-Funktionen zusammengelaufen, die selbst
protokollieren; zwei sind mit ihrem Code umgezogen. Was sich geändert hat,
ist der **Wortlaut** zweier Meldungen — sie tragen jetzt das generische
`app_state:`-Präfix statt des Namens der aufrufenden Stelle. Das gehört
gesagt, weil eine Protokollzeile, nach der jemand greppt, nicht mehr so
heißt wie früher.

*Was auf AP9 wartet:*

- Abschnitt 6 dieses Konzepts gegen den gebauten Stand nachziehen.
- Das Prüfdokument abschließen (Abschnitt J).
- Die Erledigt-Zeile in `docs/Rahmenplan.md` Abschnitt 8.

Sie warten nicht aus Bequemlichkeit: Alle drei beschreiben einen Stand, und
der Stand ist unvollständig, solange AP9 auf seinen vier Entscheidungen
steht.

---

## 4. Register — Startwerte und Decken

Startwerte aus der Messung vom 20.09.2026 (Verfahren 1.0); **AP1 hat
nachgemessen, die sechs berichtigten Zeilen stehen in 1.0a**. „Decke" ist der
Wert nach dem genannten Paket und danach die Grenze in Stufe 1.

**Zwei Decken je Zeile, seit AP1** (`decke_jetzt`, `decke_ziel` in
`tools/zaehlung/register.php`): Solange ein Paket nicht gebaut ist, steht die
geltende Decke auf dem Startwert; das Paket schreibt sie auf das Ziel herunter.
Ohne diese Trennung wäre die Zählung von AP1 bis AP9 durchgehend rot und damit
wertlos. Die Spalte unten nennt das **Ziel**.

```yaml
# kennung: [beschreibung, sicht, start, decke, paket]
Z01: ["session_start( Aufrufe", php_ohne_zeichenketten, 9, 1, AP2]
Z02: ["sitzung_ablage( Aufrufe (nach Schritt 16)", php_ohne_zeichenketten, 2, 1, AP2]
Z03: ["config.php lesend einbinden", php_mit_zeichenketten, 7, 1, AP2]
Z04: ["$CFG / global $CFG / $GLOBALS['CFG']", php_mit_zeichenketten, 46, 0, AP2]
Z05: ["php://input unter api/", php_mit_zeichenketten, 12, 1, AP3]
Z06: ["'error' => 'method' unter api/ ausserhalb api_methode()", php_mit_zeichenketten, 17, 0, AP3]
Z07: ["kein-JSON-Objekt von Hand (payload|format) unter api/", php_mit_zeichenketten, 11, 0, AP3]
Z08: ["post_max_size-Hinweis unter api/", php_mit_zeichenketten, 3, 1, AP3]
Z09: ["$_SESSION['flash…'] ausserhalb session_lib.php", php_mit_zeichenketten, 22, 0, AP3]
Z10: ["app_state-SQL ausserhalb db.php und migration_lib.php", php_mit_zeichenketten, 27, 1, AP4]
Z11: ["Literal 'manual- ausserhalb db.php", php_mit_zeichenketten, 7, 0, AP4]
Z12: ["Einsatz laden mit Besitz per SQL ausserhalb einsatz_lib.php", php_mit_zeichenketten, 13, 1, AP4]
Z13: ["Rollenvergleich von Hand ausserhalb db.php", php_mit_zeichenketten, 4, 0, AP4]
Z14: ["information_schema ausserhalb migration_lib.php und db.php", php_mit_zeichenketten, 9, "AP4 nennt (erwartet 3 bis 4)", AP4]
Z15: ["information_schema in migration_lib.php", php_mit_zeichenketten, 57, 57, AP4]
Z16: ["beginTransaction( ausserhalb db.php", php_ohne_zeichenketten, 33, "AP5 nennt (<= 8)", AP5]
Z17: ["INSERT/DELETE auf Kindtabellen ausserhalb einsatz_lib.php, migration_lib.php", php_mit_zeichenketten, 30, 0, AP5]
Z18: ["Handlisten missions (>= 10 Spaltennamen dicht beieinander)", php_mit_zeichenketten, 12, 4, AP6]
Z19: ["edbak_groesse_text( Aufrufe", php_ohne_zeichenketten, 42, 0, AP7]
Z20: ["Bauten fuer relative Zeit von Hand ('vor …'), gezaehlt in Dateien", php_mit_zeichenketten, 2, 1, AP7]
Z21: ["number_format( deutsche Form ausserhalb format_lib.php", php_mit_zeichenketten, 27, 0, AP7]
Z22: ["Byte-Division fuer die Anzeige", php_ohne_zeichenketten, 18, "AP7 nennt", AP7]
Z23: ["Anteil von Hand (Teil * 100 / Ganzes)", php_ohne_zeichenketten, 10, 0, AP7]
Z24: ["gmdate('Y-m-d\\TH:i:s\\Z' …", php_mit_zeichenketten, 20, 1, AP7]
Z25: ["str_replace(['T','Z'] … (ISO lesen)", php_mit_zeichenketten, 9, 1, AP7]
Z26: ["Datumsformat-Literale mit d.m. ausserhalb format_lib.php", php_mit_zeichenketten, 67, 0, AP7]
Z27: ["date('…') mit Zeichenketten-Format, ohne Zeitstempel, ausserhalb install.php", php_ohne_zeichenketten, 4, 0, AP7]
Z28: ["JS: Literal 'X-CSRF'", js_und_inline, 15, 1, AP8]
Z29: ["JS: Feld csrf von Hand", js_und_inline, 5, 0, AP8]
Z30: ["Gegenprobe P1: INSERT INTO password_resets ausserhalb konto_lib.php", php_mit_zeichenketten, 0, 0, AP1]
Z31: ["Gegenprobe P1: 'base_url' lesend ausserhalb konfig_lib/instanz_lib/install/config.example", php_mit_zeichenketten, 0, 0, AP1]
Z32: ["Gegenprobe P1: mail_rahmen( ausserhalb mail_lib.php, instanz_lib.php", php_ohne_zeichenketten, 0, 0, AP1]
Z33: ["Gegenprobe P3: usleep( ausserhalb ratelimit_lib.php", php_ohne_zeichenketten, 0, 0, AP1]
Z34: ["JS: Formatierer-Definitionen ausserhalb assets/format.js", js_und_inline, 14, 0, AP8]
Z35: ["JS: Seiten mit eigener L.map(-Praeambel", js_und_inline, 4, 0, AP8]
Z36: ["JS: EdPat.entschluessleListe( in Seiten", js_und_inline, 4, 0, AP8]
Z37: ["JS: Meldungs-Markup von Hand", js_und_inline, 8, 0, AP8]
Z38: ["Uebergabe 10c: error_log( Aufrufe", php_ohne_zeichenketten, 77, 77, "alle; 10c AP3 setzt <= 2"]
```

## 5. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| `tools/zaehlung/` Register | jedes AP | alle Zeilen ≤ Decke; Selbstprobe grün |
| `php -l` über `server/` | jedes AP | 0 |
| Wortliste, Vollständigkeit, Kontraste, Kettenaufrufe, CSP, Migrationsregister | jedes AP | wie vor dem Paket, mit Zahl |
| Sitzungshärtung (umgestellt) | ab AP2 | 0 Befunde; genau 1 Aufruf |
| Kreisläufe csv und edbak | AP3–AP6, AP9 | 0 unerklärt |
| Byte-Vergleich Export und Backup am Referenzdatensatz | AP6 | gleiche Prüfsumme |
| Ingest-, Kopplungs-, Raten-, Abmelde-, Mail-, Spur-, GPX-Probe | AP2–AP5 | grün wie zuvor |
| Messstand | AP5 | `ingest.php` innerhalb der Streuung |
| Bilderlauf 8 Breiten | AP7, AP8 | 0/0/0 |
| Bildvergleich Tagesübersicht | AP9 | nur die drei benannten Abweichungen |
| Android `SenderTest`, `SendeantwortTest` | AP3 | unverändert grün (Gerätevertrag) |

**Nicht prüfbar, und so gesagt:** `heute_lokal()` auf einer echten Anlage mit
abweichender Serverzeitzone (nachgestellt mit gesetzter Zeitzone im
Prüfstand); F-ZE-2 gegen echte Bots (nachgestellt mit einem Abruf ohne
Cookie).

## 6. Andockstellen für 10c — was P5c nach Schritt 15 vorfindet

Bezug: `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`, AP1–AP11. **Zusage**
heißt: Name, Datei und Signatur gelten; ändert die Umsetzung von 15 etwas
daran, steht es nach AP10 hier.

| 10c-Paket | braucht | findet vor | aus |
|---|---|---|---|
| **AP1 Banner** | Umgebungsetikett aus `config.php` | **`konfig('app.umgebung')`**; der Mail-Präfix kommt aus demselben Leser (`konfig('mail.betreff_praefix')`) — die Statuswarnung „Präfix ohne Etikett" vergleicht zwei `konfig()`-Werte | AP2 |
| | Banner auf jeder Seite | `ui_seite_start()` ist **eine** Stelle (46 Aufrufe, 43 Dateien). **Nicht darüber laufen** und einzeln zu entscheiden: `install.php`, die Störungs- und Wartungsseiten in `wartung_lib.php`, `kopfzeilen_lib.php`, die Druckseiten `betrieb_schluesselblatt.php` und `notfallblatt.php`, `gpx_lib.php` — Schritt 15 fasst diese Hüllen nicht an | Bestand |
| **AP2 Protokollseite, Archiv** | Schreibweg | `protokoll()` in `protokoll_lib.php` (13 Aufrufe, 8 Dateien) — von 15 **unberührt** | Bestand |
| | Rollengatter je Reiter | `require_admin()`/`require_betreiberin()` (`auth_guard.php`), `rolle_*()` (`db.php`); **0 Rollenvergleiche von Hand** außerhalb `db.php` | AP4, **gebaut** (Z13 4 → 0) |
| | Zeile, Filter, Archivliste | `datum_zeit_text()`, `zeit_relativ()`, `zahl_text()`, `groesse_text()` aus `format_lib.php` | AP7 |
| | Archivtakt, Fristen | `app_state_lesen()`, `app_state_setzen()`, `app_state_mehrere()`, `app_state_setzen_mehrere()`, `app_state_loeschen()` | AP4, **gebaut** |
| | Archivlauf | `db_transaktion()` | AP5 |
| | ZIP schreiben | **R83: 10c AP2 ist der zweite Verbraucher und löst heraus.** Heute 4× `new ZipArchive` allein in `adminbackup_lib.php`; Schritt 15 baut **nichts**, nennt aber den Ort: **`zip_lib.php`** (neu, dort), `adminbackup_lib.php` zieht im selben Paket um. Registerzeile dafür legt 10c an | Bestand |
| **AP3 Fehlerprotokoll** | Zahl der umzustellenden Aufrufe | **Übergabezahl** aus AP10 (Start 77 in 32 Dateien; Schritt 15 stellt keinen um) | E-ZE-05 |
| | Zählmittel und Stufe-1-Schritt | `tools/zaehlung/`, Zeile **Z38**; 10c AP3 setzt die Decke auf **2** — Nr. 248 ist damit eine Registerzeile, kein neues Werkzeug | AP1, AP10 |
| | Behandler „früh in `db.php`" | der frühe Teil von `db.php` trägt nach AP2: `konfig_lib.php` laden, `wartung_tor()`. **Der `sitzung_ablage()`-Aufruf aus Schritt 16 steht dort nicht mehr** (E-ZE-06) | AP2 |
| | JSON-Fehler mit Kennung | `json_fehler()` (`db.php`, 18 Aufrufe, 14 Dateien) — unberührt; `api_rumpf()` fängt **keine** Ausnahmen | AP3 |
| **AP4 Support-Rolle** | eine Andockstelle | `ROLLEN`, `rolle_darf_verwalten()` in `db.php` — `rolle_darf_support()` kommt daneben; **kein** Handvergleich mehr in `admin_user.php`/`admin_users.php` | AP4, **gebaut**: die vier Vergleiche rufen jetzt `rolle_ist_betreiberin()` |
| | „je Handlung, nicht je Seite" | Der POST-Verteiler der Verwaltungsseiten (`$_POST['action']`) ist von 15 **nicht** zentralisiert (Beifang, E-ZE-07) — 10c AP4 trifft ihn je Seite | — |
| | Meldungen nach Handlungen | `flash_setzen(string $ton, string $text): void` und `flash_holen(): ?array` in **`session_lib.php`**, Töne `notice` und `error`, Sitzungsschlüssel `flash`. `flash_holen()` liest **und** löscht | AP3, **gebaut** |
| **AP5 Zweitfaktor** | Sitzung und Tor | **`sitzung_starten('app')`** ist der einzige Weg in die Anmeldesitzung (`login.php`, `auth_guard.php`, `session_beenden()`); das Tor für Pflichtrollen dockt in `auth_guard.php` **nach** dem Sitzungsstart an, neben dem Einwilligungstor (E-P5b-15) | AP2 |
| | Geheimnis einmalig, Einstellungen | `app_state_einmalig(string $k, callable $erzeuger): string` (`INSERT IGNORE`, dann zurücklesen), `konfig()` | AP4, AP2, **beide gebaut** |
| **AP6 Health** | Eingang ohne Sitzung | **`api_methode('GET')`** aus `db.php` — lädt **kein** `auth_guard.php`. **Der Name hat sich in AP3 geändert** (AP3-a): aus dem einen `api_eingang()` sind `api_methode()` und `api_rumpf()` geworden, weil `csrf_check()` dazwischen steht. `api/health.php` braucht nur die erste | AP3, **gebaut** |
| | Token, Dauer, Zustand | `konfig('betrieb.health_token')`, `rate_gleiche_dauer()` (Bestand), `migrationen_ausstehend()` (Bestand), `wartung_tor()` antwortet vorher mit 503 | AP2 |
| **AP7 Betriebslage** | Zahlen und Anteile | `zahl_text()`, `prozent_text()`, `zeit_relativ()` | AP7 |
| | „je echtem Gerät" | `geraete_echt_sql(string $alias = '')`, `GERAET_VIRTUELL_MUSTER`, dazu `geraet_virtuell_kennung()` und `geraet_virtuell_sicherstellen(PDO $pdo, int $userId)` | AP4, **gebaut** |
| | Migration für den Index | Regel „nur über `db_hat_index(PDO $pdo, string $tabelle, string $index)`"; Registerzeile Z15 (Decke seit AP4 **54**) schlägt sonst an | AP4 **gebaut**, AP10 |
| **AP8 R39-Rest** | Migration mit Vorzählung | `db_hat_spalte(PDO $pdo, string $tabelle, string $spalte)`; `migrationen_inhalt_zaehlen()` (Bestand) | AP4, **gebaut** |
| | Besatzung am Diensttag | `einsatz_besatzung_ersetzen()` besteht für **Einsätze**; die Diensttag-Besatzung (`day_crew`) ist von 15 nicht berührt | AP5 |
| **AP9 Aufräumen** | Datum-Zeit-Trenner | **übernimmt die Vereinheitlichung aus F-ZE-3**: nach 15 steht der Trenner an **einer** Stelle (`datum_zeit_text()`), 11 Aufrufer übergeben ` · ` — die Entscheidung ist danach eine Zeile | AP7 |
| | JS von `einstellungen.php` | sechs JSON-POSTs laufen über `EdApi`; Textarbeit dort trifft kein Fehlerschema mehr je Knopf | AP8 |
| **AP10 Bounce** | Betreff-Schlüssel, Postfach | `mail_einreihen()` ist **eine** Stelle (`mail_lib.php`; 19 Aufrufe, 14 Dateien) — der Schlüssel `[NAdoku #<id>]` entsteht dort; `konfig('mail.postfach')` | Bestand, AP2 |
| **AP11 Abschluss** | — | Das Register läuft in Stufe 1: Neuer 10c-Code, der eine zweite Stelle baut, macht die Kette rot. **Decken werden nicht angehoben, ohne dass es im Konzept steht** | AP10 |

**Andockstellen zu Schritt 16** (gemergt, `main` `fd99989`): `sitzung_lib.php`
besteht mit `sitzung_ablage()`; die zwei Aufrufstellen (`db.php` Z. 586,
`install.php` Z. 144) entfallen in AP2. FF-2 besteht an diesem Stand fort: Bis
AP2 legt jeder anonyme Abruf einer lesenden Seite eine Datei in `.sitzungen/`
an — auch die Messschritte und der Bilderlauf der Kette. Wer dort Dateien
zählt, rechnet das ein.

## 7. Gesammelte Fehlerfunde (K4)

| # | Fund | Wohin |
|---|---|---|
| FF-1 | **„Heute" hängt an der `php.ini`.** `date_default_timezone_set` kommt in `server/` nicht vor; `date('Y-m-d')` bestimmt in `diensttag_neu.php` die Vorgabe des neuen Diensttags und das `max` des Datumsfelds, in `einsatz_form.php` das `max` des Geburtsdatums, in `betrieb_statistik.php` einen Dateinamen. Auf einem Server in UTC ist zwischen 0 und 2 Uhr Ortszeit „heute" gestern | AP7, F-ZE-1 |
| FF-2 | **Drei öffentliche Seiten starten für jeden Besucher eine Sitzung** (`doku_seite.php`, `rechtstext_seite.php`, `notfallblatt.php`: `@session_start()` ohne Cookie-Bedingung). Das Konzept Sitzungsablage, Abschnitt 1, sagt „nur wenn ein Cookie da ist" — gemessen ist das nicht so. Mit Schritt 16 landet jede dieser Sitzungen als Datei in `.sitzungen/` | AP2, F-ZE-2; Hinweis an Schritt 16 (Abschnitt 6) |
| FF-3 | **R83 ist in P5a AP8–AP10 nicht angekommen:** `edbak_groesse_text()` liegt weiter in `adminbackup_lib.php` (43 Aufrufe, 10 Dateien), die relative Zeit steht zweimal — mit abweichender Rundung | AP7 |
| FF-4 | **10b hat einen zweiten CSRF-Transport im JS gebaut** (Formularfeld, 5× in 3 Skripten) neben den 15 Kopf-Stellen | AP8 |
| FF-5 | **Drei Trenner zwischen Datum und Zeit** (Leerzeichen 25, ` · ` 11, Komma 1) | AP7 benennt, 10c AP9 entscheidet (F-ZE-3) |
| FF-6 | Nr. 202 nennt für den Log-Helfer 39 Aufrufe in 19 Dateien, für den Sitzungsstart 7 in drei Varianten — gemessen 77 in 32 und 9 in vier. Der Eintrag wird mit den Zahlen aus Abschnitt 1 berichtigt | Abschnitt 9 |

## 8. Einschub Rahmenplan (Fassung und Wortlaut vergibt die einspielende Instanz)

### 8.0 Lage der Steuerungsdokumente — gemessen am 20.09.2026

**Bei der Messung** stand `claude/fervent-dirac-xirsqw` bei `46f727c`; der
Einschub vom 20.09.2026 war dort noch nicht eingespielt, und die Fassung 84,
die sein Auftrag nannte, hatte Kette II/AP2 schon vergeben.
**Stand `52eb540` (20.09.2026, später am Tag):** Der Einschub ist eingespielt
— als **Fassung 85** —, Backlog **241–249 reserviert und angelegt**, nächste
freie **250**, die drei Konzeptdateien eingecheckt; der Rahmenplan steht bei
**Fassung 95**, Fahrplanzeile 16 bei „erledigt und gemergt". **Dieses Konzept
liegt noch auf keinem Zweig**; Zeile 15 nennt es noch nicht.

### 8.1 Fahrplanzeile 15

- Konzept: „liegt vor: `docs/konzepte/Konzept-Zentralisierung.md` (Fable,
  20.09.2026, E-ZE-01 bis -24, F-ZE-1 bis -6, AP1–AP10, kein Fable-Schritt)".
- Inhalt: sechs Pakete → „zehn Arbeitspakete; Paket 1 aus Nr. 202 ist durch
  (Gegenprobe), Paket 6 bleibt Beifang, **dazu Nr. 57** und der
  Konfigurationsleser; **ohne** Log-Helfer (10c AP3) und **ohne** Umbau
  gelaufener Migrationen (E-ZE-04)".
- Voraussetzung: „Kette II bis M1; Schritt 16 gemergt — **erfüllt** (PR #61/#62)".
- Status: „Konzept freigegeben (20.09.2026), Umsetzung offen".

### 8.2 Schritt-15-Block (Abschnitt 3 des Rahmenplans)

Die Zahlen „Stand `main` 16.09.2026" durch den Verweis auf Abschnitt 1 dieses
Konzepts ersetzen; den Satz „Reihenfolge = Reihenfolge" der sechs Pakete durch
die AP-Folge aus 3.0. R83 bleibt unverändert; **kein neues R** — E-ZE-04 ist
eine Ausnahme von der Weisung „alles wird angegangen" und steht als Satz im
Block, nicht als Programmentscheidung.

### 8.3 Fahrplanzeile 10c

Voraussetzung bleibt „Schritte 16 und 15"; ergänzen: „Andockstellen in
`Konzept-Zentralisierung.md` Abschnitt 6; **AP3 baut kein Zählmittel**
(E-ZE-03)".

## 9. Einschub Backlog und Nachbarkonzepte (Nummern vergibt die einspielende Instanz)

**Bestehende Einträge:**

- **Nr. 202:** Zahlen nach Abschnitt 1 berichtigen (FF-6); Zuordnung je Paket
  „Schritt 15 AP…"; Paket 1 „durch — Gegenprobe in AP1"; Paket 3, Zeile
  Log-Helfer: „77 Aufrufe in 32 Dateien, 10c AP3".
- **Nr. 57:** Zuordnung „Schritt 15 AP9"; ergänzen: E-ZE-01 (Gleichstände,
  `sortable`) und F-ZE-6 (Spaltensatz je Seite).
- **Nr. 248** (P5c, Prüfmittel): Text „das Zählmittel entsteht mit AP3" →
  „das Zählmittel besteht seit Schritt 15 AP1 (`tools/zaehlung/`); 10c AP3
  setzt die Registerzeile Z38 auf Decke 2".
- **Nr. 210** (Deadlocks in `ingest.php`): Vermerk „`db_transaktion()` aus
  Schritt 15 AP5 fasst `ingest.php` nicht an — die Wiederholung bei Deadlock
  gehört hierher".

**Neu (ohne Nummer; die einspielende Instanz trägt im Backlog-Kopf eine
Spanne für Schritt 15 ein — diese vier Einträge plus Reserve für Funde der
Umsetzung —, wie bei 241–249):**

- *Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten*
  (`post_ende()` aus Nr. 202, F-ZE-4) — ändert Wege, deshalb nicht in
  Schritt 15. Zuordnung: Schritt 17.
- *Cookie-Attribut `secure` der Sitzung ist in zwei Arten HTTPS-abhängig, in
  zwei fest* (E-ZE-12) — nach AP2 eine Tabelle in `sitzung_lib.php`.
  Zuordnung: Schritt 18, zusammen mit der Sitzungsbindung.
- *Gelaufene Migrationen fragen das Schema 57× von Hand* (E-ZE-04) —
  erledigt sich mit dem neuen Migrationsregister in P8 (R66); bis dahin
  Decke 57 im Register.
- *Datum-Zeit-Trenner vereinheitlichen* (F-ZE-3, FF-5). Zuordnung: 10c AP9.

**Außerhalb der Spanne vergeben — 250 bis 259 sind voll** (254 bis 259 sind
Funde von AP2 bis AP5). Der Fund von AP6 hat auf Ansage des Auftraggebers
(22.09.2026, „260 nehmen") **Nr. 267** bekommen: 260 war zu dem Zeitpunkt
bereits belegt, und 260 bis 266 sind inzwischen von Kette II vergeben. Die
nächste freie war 267.

- **Nr. 267** — *Die tote Spalte `missions.other_resources` löschen.* Seit der Migration
  2026_07 liegen die weiteren Rettungsmittel als Zeilen in
  `mission_resources`; die Spalte wurde damals nur nicht entfernt und ging
  bis Web 12.x über `SELECT *` in jedes Backup. **Sie ist nicht verloren:**
  Das Spaltenregister führt sie mit genau dieser Begründung
  (`mf_missions_gruende()`), und `tools/spaltenregister/pruefen.php` würde
  anschlagen, wenn jemand die Begründung entfernte, ohne die Spalte zu
  löschen. Zuordnung: **P8 (R66)** — es ist eine Migration. Die Migration
  ist destruktiv und braucht `zerstoert` und `inhalt`
  (`migrationen_inhalt_zaehlen()`); Einzelheiten im Backlog-Eintrag.

**Ein Satz im freigegebenen P5c-Konzept** (E-ZE-03), AP3, Abnahme: „das
Zählmittel entsteht hier und geht in Stufe 1 (Nr. 248)" → „das Zählmittel
besteht seit Schritt 15 (`tools/zaehlung/`, Zeile Z38); AP3 setzt dessen Decke
auf 2 (Nr. 248)". Dazu in E-P5c-12 der Halbsatz „(Paket 3 aus Nr. 202, hier
erledigt)" → unverändert richtig.

**Ein Satz im Konzept Sitzungsablage**, Abschnitt 1: „die drei letzten mit
`@session_start()`, nur wenn ein Cookie da ist" → „… mit `@session_start()`,
**ohne** Cookie-Bedingung (nachgemessen 20.09.2026; die Bedingung kommt mit
Schritt 15 AP2, F-ZE-2)". Ist das Konzept Sitzungsablage nach `CLAUDE.md` 7
schon gelöscht, gehört der Satz in die Erledigt-Zeile von Schritt 16.
