# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

Projekt: einsatzdoku-luftrettung → wird zu **Gen-EM NAdoku v1.0** (neues Repo)
Dieses Dokument: zentrale Steuerung des Programms. Es hält Phasen, Reihenfolge,
Status und programmweite Entscheidungen. Es wird fortgeschrieben, nie
„abgearbeitet".
Ausgangsstand des Codes: Web 7.0.2.
Stand auf `main` nach P0, Sofortpaket und P1: **Web 7.3.1** — gepusht und
damit ausgeliefert.
Stand nach S1: **Web 8.0.0** — gepusht und ausgeliefert; die Prüfliste ist
abgearbeitet, ebenso der offene Produktivlauf P-12 aus P1.
Stand nach P2: **Web 8.0.1** — umgesetzt, geprüft und ausgeliefert.
Stand nach P3: **Web 9.14.0** — umgesetzt, geprüft und ausgeliefert
(zwölf Pakete O1–O12 als Web 9.0.0 bis 9.13.0, dazu 9.14.0 als erste
Rückmeldungsrunde). Konzept, Prüfdokument und die neuen Dokumente
(`Design.md`, `Lizenzen.md`) liegen im Repo; aus dem P3-Konzept kamen R32
(Impressum und Datenschutzerklärung) und R33 (Servicemodell) hinzu.
Stand nach S2: **Web 12.2.0** — umgesetzt, geprüft und ausgeliefert
(elf Pakete AP0–AP10 als Web 10.0.0 bis 12.2.0, 31.08.–01.09.2026). Konzept
und Prüfdokument liegen im Repo; **vier Migrationen**, `update.php` nach dem
Deploy erforderlich. Der Wiederanlauf ist der einzige Punkt der Prüfliste,
den die Umsetzung nicht selbst belegen konnte.
Fassung 7 (30.08.2026): Zwischenpaket **S2 — Mengen, Spurspeicherung und
Sicherung** zwischen P3 und P4 eingefügt (R34, R35); das Konzept liegt vor
(`docs/Konzept-S2-Mengen-Spuren-Sicherung.md`). Backlog Nr. 2 und 3 wandern
nach S2, P5 gibt „Alle sichern in Schüben" an S2 ab.
Fassung 8 (30.08.2026): Ergebnisse des **Dienstbetriebs-Konzeptgesprächs**
(„Service mit 1000 Accounts: Struktur und Prozesse", geführt auf dem Stand
P3/O11) eingearbeitet — neue Programmentscheidungen **R36–R41**: Zielbild
Dienstbetrieb samt Hosting-Vorentscheidung (R36), Konto-Lebenszyklus und
Registrierungs-Sicherheitspaket (R37), Rollen/Administration/Betriebszahlen
mit festem Dashboard-Minimalumfang (R38), Wegfall der zentralen Stammdaten
(R39), Deploy-Umbau mit Staging ab P5 und einmaligem Neuaufsetzen am
P6-Schnitt samt Datenübernahme (R40), Recht und Betreiberorganisation vor
der Öffnung (R41). P5 ist entsprechend erweitert. **An S2 als nächstem
Schritt ändert sich nichts** — kein Ergebnis des Gesprächs greift in den
S2-Umfang ein; was das Gespräch fand und dort schon verortet war
(Job-Einstieg, Schübe, Speichergrenzen, Komplettbackup), bleibt dort.
Nachtrag gleichen Datums: **R42** — Gerätekennung bei der Kopplung
(Art Uhr/Handy/Sonstiges und genaue Bezeichnung). Die Uhr-Seite wird
unmittelbar beauftragt; die **Speicherung** läuft als Kleinstpaket sofort
mit (jede Kopplung davor ginge der Statistik verloren), die **Auswertung**
liegt im R38-Dashboard (P5).
Fassung 9 (31.08.2026): **R43** eingefügt — Zwischenpaket **S3
„Oberflächen-Nacharbeit und vertikaler Rhythmus"** nach S2, aus der
Rückmeldungsliste `ToDo_Layout.pdf` (19 Punkte am Stand Web 9.14.1). Kern
ist keine Sammlung von Einzelkorrekturen, sondern die fehlende
**Anwendungsregel** zur vorhandenen Abstandsskala; sie wird in
`docs/Design.md` geschrieben und über Klassen und Bausteine umgesetzt. Ein
echter Fehler ist dabei mitgefunden und in seiner Ursache geklärt
(Markerversatz auf der Karte). **Die drei offenen Punkte der Liste sind
mit dieser Fassung entschieden** (E-R43-1 bis E-R43-3, siehe R43): Die
Sammelleiste übernimmt die Kartenform, die Leistenüberschrift bleibt
linksbündig und wird größer, „Winden-Cycles / Flugtag" bleibt stehen.
S3 geht damit ohne offene Gestaltungsfrage in die Umsetzung.
Nachtrag gleichen Datums, aus zwei Beobachtungen am laufenden Betrieb:
**R44** — der Entsperrdialog erscheint trotz laufender Sitzung, weil die
beiden 30-Minuten-Fristen **Verschiedenes messen**; Ursache gefunden,
Behebung ins R42-Kleinstpaket gelegt. Dazu **S3 Punkt K** — die beiden
Logos haben unterschiedliche Seitenverhältnisse (1,60 : 1 gegen 1 : 1) und
werden nur über die Höhe skaliert; das Bodenlogo erscheint dadurch
sichtbar kleiner.

Fassung 10 (31.08.2026): **R45** eingefügt — Zwischenpaket **S4 „Handy-
und Uhr-Client (Android/Wear OS), Schneidewerkzeug und GPX-Import"** nach
S3. Anlass: NutzerInnen ohne Garmin-Uhr sollen ihre Spur aufzeichnen
können. Befund am Vertrag: `docs/JSON-Vertrag.md` ist ausdrücklich
clientneutral, und die Dauer-Aufzeichnung eines Dienstes passt ohne
Vertragsänderung auf die vorhandene Nachricht `rest_segment` — was fehlt,
ist das **Schneiden** eines Ruhesegments in Einsätze im Browser, und das
braucht jeder Client. Beschlüsse des Gesprächs (E-R45-1 bis E-R45-13 in
R45): Handy zeichnet auf, Uhr ist Fernbedienung; Uhr-App wird blind
gebaut; Schneiden in der Tagesansicht; Phasenkonflikte behalten beide
Einträge; Dienstbeginn an Handy oder Uhr. Nachtrag gleichen Datums:
**R46** — **die Apple Watch wird nicht gebaut, P7 entfällt**; Backlog
Nr. 13 wandert in die Uhr-Auslieferung von P6 (R29). R12 ist entsprechend
nachgetragen. Dazu in Abschnitt 5 eine Übersicht, **welche Pakete
parallel von getrennten Instanzen** bearbeitet werden können, weil sich die
berührten Dateien nicht überschneiden. Stand der Umsetzung mit dieser
Fassung: **R42-Kleinstpaket und S2 sind in Arbeit.**

Fassung 11 (31.08.2026): **R47** eingefügt — die **Garmin-Uhr-Auslieferung
ist vorgezogen und abgeschlossen** (Uhr 1.10.1 bis 1.11.1, dazu Web 9.15.0).
Damit sind **alle Monkey-C-Punkte des Programms erledigt, die vor P6 lagen**:
Backlog Nr. 11 und Nr. 14 (beide P4), Nr. 13 (P6 nach R46) und Nr. 60/61 aus
der P3-Zulieferung. Die R29-Klausel ist dabei ausgelöst und erfüllt worden;
offen bleibt an der Uhr allein die **Umbenennung** selbst, die R29
ausdrücklich in P6 hält. Nr. 14 hat außerdem eine **Serverseite** nach sich
gezogen (Web 9.15.0, `pair.php` mit zweitem Anliegen „trennen") — ohne
Migration. Einzelheiten in R47.

Fassung 12 (31.08.2026): **R29 ist vollständig abgeschlossen** und **R48**
eingefügt. Auf Anweisung vom 31.08.2026 („alle Uhr-Punkte abarbeiten,
Entscheidungen jetzt fällen") ist auch der Rest der Uhr-Umbenennung aus P6
vorgezogen worden: Die App heißt **NAdoku**, die Einstiegsklasse `NAdokuApp`
— und der **Platzhalter in der Anwendungs-ID** ist durch eine echte ersetzt
(Uhr 2.0.0). Damit trägt P6 **keine Uhr-Auslieferung mehr**. Bewusst in Kauf
genommen: Bis zum v1.0-Schnitt heißt die Uhr anders als das Web. Von den
Uhr-Punkten bleibt allein **Backlog Nr. 59** (Serverseite der
Gerätestatistik) — dort bewusst als eigenes Kleinstpaket nach R42, weil es
eine Schemaänderung mitbringt.

Fassung 13 (01.09.2026): **R49** eingefügt — Zwischenpaket **S5 „Kopplung
umgekehrt"** zwischen S2 und S3. Anlass: Der Kopplungscode wird heute im Web
erzeugt und **auf der Uhr eingetippt**. Geprüft wurden ein QR-Code auf der Uhr
und ein Direktlink in den Handy-Browser; beschlossen ist der einfachste Weg —
**das Gerät zeigt den Code, das Web nimmt ihn entgegen, das Gerät bestätigt
das Konto** (E-R49-1 bis E-R49-8): schwebende Zugangsdaten in einer
Sitzungstabelle statt halbfertiger Geräte, Kopfzeilen statt Code als Ausweis
des Geräts, Rückbestätigung am Gerät mit maskierter E-Mail, Ratenschutz
gedreht, der alte Weg entfällt, Vertragsabschnitt 1a wird **vor** dem zweiten
Client neu geschrieben, und **`nadoku.gen-em.org` ist Vorgabewert der
Server-Adresse** in Uhr- und Handy-App (Folge von R36: eine öffentliche
Installation). Wirkung auf S4: E-R45-2 geändert — der QR der Geräteseite
trägt nur noch die Adresse, das Kopplungsmodul von Block B wartet auf das
S5-Konzept. **Das S5-Konzept wird erst nach S2 und dem R42-Kleinstpaket
erarbeitet.** Dazu zwei Korrekturen am Dokument selbst: Die Fassungsvermerke
stehen wieder in aufsteigender Folge (12 stand vor 11 und 10), und der
Eintrag R6 ist wieder eine Tabellenzeile (er war über vier Zeilen umbrochen
und beendete die Tabelle im Rohtext). Stand der Umsetzung: **R42-Kleinstpaket
und S2 in Arbeit.**

Fassung 14 (01.09.2026): **R50** eingefügt — die **Terminologie-Umstellung
„Sicherung" → „Backup"**, verortet **nach S3**. Anlass: Auf der Sicherungsseite
stehen beide Wörter unmittelbar übereinander (Karten „Backup erstellen" und
„Backup einspielen", die Knöpfe darin „Sicherung erstellen" und „Sicherung
einspielen"). Beschluss vom 31.08.2026: alles auf „Backup", die klarere
Beschreibung. Die Vorlage samt Grenzen und Arbeitsliste liegt als
`docs/Umstellung-Backup.md` im Repositorium. Dazu der Stand der **zweiten
Rückmeldungsrunde** (Web-Oberfläche, vier Commits auf einem eigenen Zweig,
noch nicht ausgeliefert) und **zwei Berichtigungen an diesem Dokument**, beide
am Code nachgemessen: Der Satz, das R42-Kleinstpaket habe „mit S2 keine
gemeinsame Datei", ist als Merge-Aussage falsch, und die
Dringlichkeitsbegründung zu R44 („der Entsperrdialog stört täglich") trägt
nicht — Einzelheiten in R50 und an den berichtigten Stellen.

---

## 1. Zweck und Geltung

Das Programm führt die bestehende Einsatzdokumentation von einem
Luftrettungs-Werkzeug mit bodengebundener Erweiterung zu einem einheitlichen
Notarzt-Dokumentationswerkzeug (Land/Luft gleichrangig), mit neuem Namen,
neuer Oberfläche, Mehrbenutzerfähigkeit und Version 1.0 in neuem Repository.

Der Rahmenplan ist die führende Quelle für **Reihenfolge, Status und
programmweite Entscheidungen**. Fachliche Details stehen in den
Phasenkonzepten. Bei Widerspruch gilt der Rahmenplan für das „Wann und in
welcher Reihenfolge", das Phasenkonzept für das „Was und Wie".

## 2. Dokumentstruktur und Konventionen

- K1: Je Phase ein eigenes Konzeptdokument im bewährten Format: Befund,
  Entscheidungen (E-Nummern), offene Fragen (F-Nummern), Arbeitspakete mit
  Abnahmekriterien, Prüfprotokoll, gesammelte Fehlerfunde. Das Phasenkonzept
  ist die Übergabeeinheit an die umsetzende Claude-Code-Instanz.
- K2: Konzepte nennen keine Modellempfehlungen je Arbeitspaket.
  **Standardmodell der Umsetzung ist Opus.** Schritte, die Fable erfordern,
  werden im Konzept ausdrücklich als Fable-Schritt markiert.
- K3: Konzeptdokumente legen keine Versionsnummern fest; das geschieht in der
  Umsetzung.
- K4: Fehlerfunde während einer Phase werden gesammelt, nicht sofort behoben
  (Ausnahme: der Fund blockiert die laufende Arbeit).
- K5: Jede Phase endet mit lauffähigem Stand, aktualisiertem Phasenkonzept,
  aktualisiertem Prüfprotokoll und einem Statuseintrag in Abschnitt 6 dieses
  Dokuments.
- K6: F-Fragen werden vor Umsetzungsbeginn der betroffenen Arbeitspakete
  entschieden und als E-Eintrag ins Phasenkonzept überführt.
- K7: Claude Code hat Schreibzugriff auf das Repository. Je Arbeitspaket
  ein eigener **Commit** (deutsche Nachricht); **gepusht wird einmal am Ende
  der Phase**, nach ausdrücklicher Bestätigung — ein Push auf `main` deployt
  sofort auf den Produktivserver. Aktualisiertes Konzeptdokument und
  Prüfprotokoll gehören wie bisher zum Paketabschluss.
- K8: Nur vor einem **Fable-Schritt** pausiert die umsetzende Instanz und
  weist ausdrücklich darauf hin, damit das Modell umgestellt werden kann.
  Alle übrigen Schritte laufen ohne Modellnachfrage mit Opus.
- K9: **Jede Phase liefert zusätzlich ein Prüfdokument** (aus P0 als E15
  hervorgegangen; Muster: `Pruefdokument-P0-Aufraeumen.md`, Regel in
  Konzept P0, Abschnitt 11): getrennt vom Konzept, je Phase (nicht je
  Paket), mit Kurzfassung, maschinellen Prüfungen samt Zahlen, dem, was
  nicht geprüft werden konnte, und einer abhakbaren Prüfliste, in der jeder
  Punkt Bedienweg, erwartetes Ergebnis und die Bedeutung eines Fehlschlags
  nennt.

## 3. Programmentscheidungen

| Nr. | Entscheidung |
|---|---|
| R1 | Dokumentstruktur: Rahmenplan + Phasenkonzepte (statt eines Großdokuments). |
| R2 | Phasenfolge P0 → P1 → P2 → P3 → P4 → P5 → P6; P7 nach v1.0. **Stand nach Fassung 10:** dazwischen die Zwischenpakete S1 (nach P1), S2, S3, S4 (nach P3); **P7 entfällt** (R46). **Stand nach Fassung 13:** dazu S5 (zwischen S2 und S3, R49). |
| R3 | Terminologie: Luftrettungsbegriffe werden nur dort ersetzt, wo sie Allgemeines bezeichnen. Echte luftfahrtspezifische Fachfelder (z. B. Pilot, RTH als Rettungsmitteltyp) bleiben — das Werkzeug dokumentiert weiterhin auch Lufteinsätze. **Stand nach P2: ausgeführt und festgeschrieben.** Die Wortliste steht in `docs/Konzept-P2-Terminologie.md`, Abschnitt 5: acht Fundort-Klassen (was wird wo angefasst und warum nicht) und rund dreißig Begriffe mit Ersatz oder Begründung des Bleibens. Sie ist als Sperr- und Ausnahmeliste maschinell prüfbar (R28). Zwei Wörter hat die Erhebung erst in der Umsetzung gefunden: **„Basis" und „Station"** — beide bezeichnen den Standort, beide enthalten kein Luftwort (E-P2-21). |
| R4 | Der Referenzdatensatz wird **generiert** (nicht von Hand erfasst): 30–40 fiktive Einsätze verteilt über 2026, in drei Erfassungsarten — luftgebunden mit aufgezeichnetem Track, bodengebunden mit aufgezeichnetem Track, nachträglich erfasst nur mit Start-/Zielkoordinaten. Aufgezeichnete Einsätze entstehen als Payloads für die Uhr-Schnittstelle (`ingest.php`) und testen damit zugleich den Einspeiseweg. Der Einspielweg für Nachbearbeitungs-/Patientenfelder wird im P1-Konzept festgelegt; ein roher SQL-Weg an der Validierung und Verschlüsselung vorbei findet nicht statt. **Stand nach P1: erfüllt.** Der Umfang ist dabei auf **16 Diensttage und 87 Einsätze** festgelegt worden (Konzept P1, E-P1-22) — die Spanne „30–40" ist damit überholt. Begründung: ein Verhältnis von 11 Luft- zu 3 Bodendiensten hätte die bodengebundene Hälfte der Anwendung an einem Bruchteil der Fälle geprüft. |
| R5 | Gespeicherte Namen (DB-Spalten, Felder in Backup-/Exportformaten, Uhr-Vertrag) bleiben unangetastet. Einzige Ausnahme: eine kurze, in P6 zu entscheidende Liste von Feldern, die bei Bodeneinsätzen aktiv irreführen. **Zulieferung aus P2: die Liste ist leer.** Alle gespeicherten Namen, die Allgemeines bezeichneten (`flugtag`, `hubschrauber`, `phase_03_abflug`, Blatt `Flugtage`, Dateipräfix `luftrettungsdokumentation_`), sind bereits in Web 6.0.0–6.2.0 umbenannt worden, mit Wiedererkennung der alten Namen im Import. Was luftsprachig bleibt, bezeichnet Luftfahrt (`p1`/`p2`/`hems`/`fr`, `winch*`, `kind = 'air'`) und ist bei Bodeneinsätzen leer statt irreführend. P6 kann die Liste als leer beschließen oder Kandidaten aus P3 nachtragen (Konzept P2, 5.4). |
| R6 | Backlog-Zuordnung: Nr. 2 (Track-Vereinfachung), Nr. 3 (GPX-Export), Nr. 11 (Sync-Anzeige Uhr), Nr. 14 (Kopplungsablauf Uhr) → P4. Nr. 8 (CSP) → P5. Nr. 13 (Kosmetik Uhr) → P7. **Stand nach S2-Konzept: Nr. 2 und Nr. 3 → S2 (R34).** **Stand nach Fassung 10: Nr. 13 → P6, Uhr-Auslieferung nach R29 (R46).** **Stand nach Fassung 11: Nr. 11, 13 und 14 sind erledigt** — vorgezogen in der Uhr-Auslieferung nach R47. Von den Uhr-Punkten bleibt keiner offen; aus P4 bleibt Nr. 21 (A4-Restfunde) und die P3-Zulieferung. |
| R7 | Ein etwaiger Umbau der Ordnerstruktur (Ergebnis A6) wird **vor P3** ausgeführt, damit das Redesign in sauberer Struktur arbeitet. (Bewusst so entschieden; Alternative wäre der v1.0-Schnitt gewesen.) **Stand nach P0: gegenstandslos** — der Strukturreview hat mit Begründung gegen jeden Ordnerumbau entschieden (Konzept P0, E-A6-12); A7 entfällt. |
| R8 | Gestaltungsvorgabe Farben: Die drei Gründerfarben (Max Blau, Newroz Rot, Philipp Orange) treten deutlich präsenter auf als bisher — nicht nur Buttons, sondern Tabellen, Links, Rahmen und Akzente, wo es gut passt. In Summe ausgewogen über die Anwendung. |
| R9 | Registrierung mit drei Betriebsarten, vom Admin schaltbar: offen / offen mit Admin-Freischaltung / nur auf Einladung (wie bisher). Dazu ein Sicherheitspaket (E-Mail-Verifikation, Ratenschutz, Umgang mit Wegwerfadressen). |
| R10 | Für echte Mehrnutzerschaft wird in P5 ein Rollen- und Sichtbarkeitsmodell definiert — einschließlich dessen, was der Admin wegen der Verschlüsselung ausdrücklich **nicht** kann. |
| R11 | Kein Migrationspfad für Bestandsinstallationen zu v1.0. Stattdessen Abnahmekriterium: **v1.0 liest edbak-Sicherungen der 7.x-Linie ein.** Der einzige bestehende Account wird per edbak in eine frische v1.0-Installation übernommen. Alle sonstigen Altlasten entfallen. **Stand nach P1:** Die Abnahmedatei liegt vor — `tools/referenzdatensatz/referenz/` (edbak, Passwort `nadokudemo0815`). Sie ist **neu zu ziehen**, sobald R22 die Nutzlast der Sicherung auf Version 7 hebt. **Stand nach S1: erledigt.** Beide Referenzdateien sind aus einem frischen Einspiellauf neu erzeugt (S1/C7); die `.edbak` trägt Nutzlast 7 samt Papierkorb und ist die Abnahmedatei zu R11. Die Referenz-CSV wurde mitgezogen, weil sie aus derselben Installation stammen muss. |
| R12 | Weitere Uhren (Apple Watch, Wear OS): Im Programm wird nur die **Basisfähigkeit** hergestellt — Referenz-Payloads (P1), plattformneutrale Texte (P2), Vertragsreview und Festschreibung als v1 (P6). Client-Apps sind P7, nach v1.0, ohne Termin. **Stand nach P1:** Die Referenz-Payloads liegen vor (526 Ingest-Anfragen, plattformneutral erzeugt, alle gegen die Vertragsgrenzen geprüft). **Stand nach P2: plattformneutrale Texte erledigt.** Web und Doku beschreiben den Kopplungsablauf gerätefrei; der Garmin-Tastenweg steht als Zusatz in Klammern und je Uhr in Handbuch 2.0 (E-P2-02). Grund: Der bisherige Webtext nannte den Weg von Fenix/Forerunner als allgemeinen — für die Venu 3s war er falsch. Garmin bleibt dort stehen, wo Garmin der Gegenstand ist (Handbuch 2, Technik 5, Build; E-P2-15). **Offen für P6:** das Vertragsreview selbst, einschließlich `docs/JSON-Vertrag.md` 303 („Connect-IQ-Payload-Limit" — die Grenze ist plattformbedingt und soll plattformneutral begründet werden). **Stand nach Fassung 10 (R45):** Die Android-Handy-App und die Wear-OS-Uhr-App werden **vor** v1.0 gebaut, als Zwischenpaket S4; die Apple Watch wird **nicht** gebaut (R46), P7 entfällt. Der Vertrag bleibt dabei unverändert (Nachtrag ohne Vertragsänderung, wie in S2); das Vertragsreview in P6 findet damit einen **zweiten echten Client** vor, der die Garmin-Annahmen von selbst sichtbar macht. **Stand nach Fassung 13 (R49):** Der Vertragsabschnitt 1a (Kopplung) wird mit S5 neu gefasst — bewusst **vor** dem zweiten Client, damit dieser gleich das neue Protokoll umsetzt; „unverändert" gilt seither für den Ingest. Das Vertragsreview in P6 findet 1a damit bereits in der Fassung vor, die beide Clients benutzen. |
| R13 | Am v1.0-Schnitt werden versionshistorische Code-Kommentare („geändert in X.Y.Z weil …") durch normale erklärende Kommentare ersetzt. Die Historie lebt im alten Repo weiter. **Zulieferung aus P2:** Die betroffenen Stellen sind erhoben und in `docs/Konzept-P2-Terminologie.md`, 10.3 aufgelistet — rund 60 Kommentare in `server/`, dazu die Migrationsbeschriftungen in `update.php`, `config.example.php` (`dbname=hems`) und die Repo-Links in drei Fußzeilen. P2 hat sie ausdrücklich nicht angefasst: In der Dokumentation gilt, dass ein alter Begriff nur in einem Satz steht, der ausdrücklich sagt, dass er alt ist (E-P2-10). |
| R14 | Konzepterstellung: **Fable 5 / hohe Denktiefe**, solange Kontingent verfügbar; Opus 5 / hohe Denktiefe ist gleichwertiger Fallback ohne Qualitätsverlust. Mechanische Konzeptpflege (Status, kleine Anpassungen) braucht kein Fable. (Stand 23.08.2026) |
| R15 | Der Changelog wird ab v1.0 auf Bulletpoint-Format umgestellt (Neustart im neuen Repo). |
| R16 | Handbuch, README und Technik-Doku werden zu v1.0 vollständig und mit besonderer Sorgfalt neu gefasst — u. a. Screenshots im Handbuch und klickbare Kapitel/Unterkapitel. Die Anforderungen werden in einem **gesonderten Gespräch vor dem P6-Konzept** festgelegt. Screenshots setzen die finale Oberfläche voraus, daher Verortung in P6. **Stand nach P2:** Der Gesamtabgleich bleibt P6, aber P2 hat die Sachfehler in den Absätzen, die es ohnehin anfasste, gleich mit berichtigt (E-P2-05) — darunter die Excel-Spaltentabelle in `Export-Format.md` (29/7 statt tatsächlich 31/16), der Warntext des Excel-Rückimports, der Beispieldateiname und die Beschreibung der Kopfleiste im Handbuch. Anlass: Der Changelog zu Web 6.2.0 hatte vollständige Doku-Neutralität **behauptet, ohne zu zählen**; die Behauptung traf nicht zu. P6 findet die Doku damit näher am Code vor, aber nicht abgeglichen. |
| R17 | Vor dem v1.0-Schnitt läuft ein **Bug- und Sicherheitsreview durch Fable 5 / hohe Denktiefe**, ausdrücklich einschließlich Prüfung des Verschlüsselungsverfahrens (Verfahren, Schlüsselableitung, Umsetzung im Code). Eingangsschritt von P6, damit auch die P5-Ergebnisse (Registrierung, Rollen) mitgeprüft sind. |
| R18 | Konzeptarbeit bleibt in diesem Projektraum, Umsetzung in Claude Code — die Rollentrennung ist Qualitätssicherung: Konzept N+1 nimmt Umsetzung N ab. Ausnahme: Für reine Erhebungspakete (A4, A6) kann Claude Code einen **Befundlauf ohne Änderungen** ausführen, dessen Bericht hier zum Konzept verarbeitet wird. Entscheidung darüber fällt beim jeweiligen Paketstart. |
| R19 | **Mengenbremse für `server/ingest.php` — Entwurf in P5, Vorarbeit in P1.** Befund aus P0/A6: `ingest.php` ist der einzige anmeldungsfreie Endpunkt ohne Mengenbremse; `ratelimit_lib.php` führt in `RATE_GRENZEN` die Töpfe `login`, `salt`, `reset` und `pair`, für `ingest` keinen, und die Datei ruft weder `rate_erlaubt()` noch `rate_misserfolg()`. Der Endpunkt nimmt Uploads der Uhr und fremder Quellen entgegen; die Anmeldung läuft über `X-Device-Id` / `X-Api-Key` gegen einen bcrypt-Hash in `devices.api_key_hash`, bei unbekanntem Gerät folgt ein Blindvergleich gegen Zeitmessung und dann 401. **Offene Grundsatzfrage (vor dem Entwurf zu entscheiden): Soll die Bremse überhaupt kommen, oder bleibt die Asymmetrie zu den vier anderen Endpunkten bewusst bestehen?** Der Schlüssel ist ein langer Zufallswert und kein Passwort, die Dringlichkeit also geringer als bei `login` — begründungsbedürftig bleibt die Ausnahme trotzdem. Falls ja, sind vier Randbedingungen Vorgabe für den Entwurf: **(1)** Gezählt werden ausschließlich **fehlgeschlagene** Anmeldungen — die Uhr lädt einen Track in vielen aufeinanderfolgenden Teilstücken hoch (`next_seq`), eine Bremse auf erfolgreiche Aufrufe schnitte einen laufenden Dienst mittendrin ab. **(2)** Aussperrgefahr nach einem Schlüsselwechsel: Eine Uhr, die wiederholt einen veralteten Schlüssel sendet, sperrt sich für die Fensterdauer selbst aus — zu klären, ob hinnehmbar und wie die Betreiberin es erkennt. **(3)** Merkmalswahl Geräte-Kennung und/oder IP: Die Uhr sendet über wechselnde Mobilfunk-IPs, eine reine IP-Bremse trifft möglicherweise den Falschen. **(4)** Die Asymmetrie zu `login`/`salt`/`reset`/`pair` wird im Ergebnis ausdrücklich begründet — ob mit Bremse oder ohne. **Vorarbeit erbracht (P1):** Der Referenzlauf hat das Sendeverhalten der Uhr über 16 Diensttage nachgestellt und protokolliert (`tools/referenzdatensatz/einspielen/messprotokoll.md`, im Backlog Nr. 17 nachgetragen): 526 Anfragen ohne Fehlversuch, Spitze **14 Anfragen an einem Auslöser**, **174 Abstände von 0 Sekunden**, Median 1 020 s. Daraus folgt für den Entwurf: Eine Grenze muss den Stoß zulassen und über die Zeit deckeln — ein fester Mindestabstand je Anfrage wäre falsch. Dazu ein Präzedenzfall aus P1: Der Topf `demo` (E-P1-20) zählt als erster **erfolgreiche** Anmeldungen; die Bauart dafür steht also bereits in `ratelimit_lib.php`. |
| R20 | **Sofortpaket Backlog Nr. 22 — eigene Kleinauslieferung vor P1.** Das Altersfeld geht unmaskiert per `innerHTML` in die Einsatztabellen (`zelleGeschuetzt()` in `assets/missiontable.js`); über den Import ist damit Skriptausführung in dem Fenster möglich, in dem der Inhaltsschlüssel liegt. Umfang: Maskierung des Alters (`esc`), Durchsicht des Importpfads auf weitere unmaskierte Felder, Klärung, ob die Keyguard-Einträge `pckb`/`pckt` Schlüsselmaterial tragen und beim Abmelden geräumt werden müssen (Befund Konzept P0, Abschnitt 9.3), Berichtigung des Backlog-17-Textes (Zuordnung P5 nach R19, nicht „P1/P2"). Kein weiterer Umfang. Der P1-Datensatz nimmt einen Angriffswert im Altersfeld als Dauer-Regressionsfall auf. **Erledigt:** Das Sofortpaket ist als **Web 7.2.1** ausgeliefert (Backlog Nr. 22 erledigt, Nr. 17 berichtigt, Keyguard-Einträge geklärt, Importpfad durchgesehen; Prüfdokument `docs/Pruefung-Sofortpaket-22.md`). Der P1-Zweig hat dieselbe Lücke unabhängig gefunden (F-P1-I) und anders geschlossen; beim Zusammenführen hat die ausgelieferte Fassung von `main` Vorrang bekommen. Der Dauer-Regressionsfall steht im Datensatz und wird von `tools/referenzdatensatz/browser/angriffswerte.mjs` wiederholbar geprüft — 42 Einzelprüfungen über sechs Seiten, gegen den Stand davor sechs Befunde. |
| R21 | Backlog-Zuordnung der neuen Nummern: 17 → P5 (deckungsgleich mit R19) · 18 und 19 → in das Paket zu Nr. 21 · 20 → P3 (Palette) · 21 → P4, mit der Auflage, dass Felder mit Berührung zu `api/`, `ingest.php` oder der Uhr vor dem Entfernen gegen den JSON-Vertrag geprüft werden · 22 → Sofortpaket nach R20 · 23 → P5 (CSRF-Umfeld). **Berichtigung (Stand nach P1):** Einen Backlog-Eintrag zu `csrf_check()` gibt es nicht, und die Nummer 23 ist inzwischen anders belegt (JSON-Vertrag). Der Punkt bleibt inhaltlich in P5 — dort im Phasentext geführt — und bekommt beim nächsten Anfassen des Backlogs eine freie Nummer. Die übrige Zuordnung steht in R26. |
| R22 | **Der Papierkorb kommt in beide Sicherungen** — NutzerInnen- und Admin-Sicherung. Entschieden am Ende von P1 (Anweisung, nicht Vorschlag); ausgearbeitet in `tools/referenzdatensatz/Konzept-P1.md`, Abschnitt 10; geführt als Backlog Nr. 30. Heute filtert `edbak_build()` `deleted_at IS NULL`, eine Wiederherstellung leert den Papierkorb endgültig — gemessen am Referenzdatensatz: 5 Einsätze, 5 Ruhesegmente, 1 Diensttag → nichts. Die Arbeit steckt **nicht** im Sichern, sondern im Rückweg: `deleted_at` und `deleted_with_day` müssen geschrieben werden (ein bloßes Abschalten des Filters brächte den Papierkorb als **aktiven** Bestand zurück — schlimmer als gar nicht), die D1-Regel muss „Tag im Zielkonto im Papierkorb" von „Tag in der Datei im Papierkorb" unterscheiden, und die 30-Tage-Frist braucht eine Entscheidung. `deleted_refs` bleibt ausdrücklich draußen — die Sperrliste hängt an einer Gerätekennung, und Geräte stehen aus gutem Grund in keiner Sicherung. Hauptversion, Nutzlast der Sicherung steigt auf 7. **Zwei Berichtigungen (Stand nach S1):** (1) Die Frist beträgt **90 Tage**, nicht 30 — `TRASH_DAYS = 90` seit Web 6.x, Handbuch und Technik-Doku sagen es; der Satz oben war falsch (S1, B-S1-01). (2) Die Nutzlaststufung auf 7 ist **Kennzeichnung, keine Sperre**: `api/backup_restore.php` weist erst unterhalb von 6 ab, ein bereits ausgelieferter Stand nimmt eine v7-Datei also **an** und brächte den Papierkorb als aktiven Bestand zurück. Das ist weder prüfbar noch nachträglich behebbar und steht als Warnung in `docs/Backup-Format.md` 4 und im Handbuch 6 (S1, E-S1-07). **Erledigt in S1** (Web 8.0.0, ausgeliefert): Der Papierkorb steht in beiden Sicherungen und kommt als Papierkorb zurück — mit **frischer** Frist, weil `deleted_at` beim Einspielen auf den Einspielzeitpunkt gesetzt wird (S1, E-S1-03). |
| R23 | **Zwischenpaket S1 „Sicherung und Import" zwischen P1 und P2.** Umfang: R22 (Nr. 30) · Nr. 27 und Nr. 28 — die beiden Importfehler, die den CSV-Kreislauf offenhalten · Nr. 24 und Nr. 29 (die Ausnahmeliste in `Export-Format.md` 5.1 vervollständigen) · Nr. 25 entscheiden (`created_at` mitschreiben oder aus der Sicherung streichen) · Rückbau des Papierkorb-Teils im Demo-Nachlauf (E-P1-21), damit kein zweiter Weg stehen bleibt · Referenz-edbak neu ziehen, Fixture neu erzeugen, beide Kreisläufe neu messen. **Begründung der Verortung:** P2 und P3 sollen gegen ein Regressionsnetz laufen, das auf null steht; und die Referenzdatei wird so **einmal** neu gezogen statt zweimal. Eigenes Konzept nach K1, eigenes Prüfdokument nach K9. **Stand: umgesetzt und ausgeliefert** (Web 8.0.0 auf `main`); die Prüfliste ist abgearbeitet. Konzept und Prüfdokument liegen unter `docs/Konzept-S1-Sicherung-Import.md` und `docs/Pruefdokument-S1-Sicherung-Import.md`. **Der Umfang ist über das Konzept hinausgewachsen**, auf ausdrückliche Anweisung und nicht nebenbei: Nach dem Erreichen des Sollstands hat eine gegnerische Durchsicht des eigenen Stands (C8) und die Entscheidung der beiden daraus offenen Punkte (C9) fünf weitere Backlog-Punkte (Nr. 31–35) mit erledigt — darunter zwei Fehler, die **in dieser Phase eingebaut** worden und durch alle bestehenden Prüfungen gelaufen waren. Daraus sind zwei neue Prüfmittel entstanden (R27). |
| R24 | **Regressionspflicht ab S1.** Jede Phase führt vor ihrem Abschluss beide Kreisläufe (`tools/referenzdatensatz/vergleich/kreislauf.py`, Arten `csv` und `edbak`) und trägt die Zahlen ins Prüfdokument. Sollstand heute: **edbak 0** unerklärte Abweichungen, **CSV 6** (Backlog Nr. 27 und 28) — nach S1 beide 0. **Stand nach S1: erreicht.** Sicherung **286 739** Einzelvergleiche, **0 unerklärt**, 16 erwartet (vorher 269 439 — es wurde weniger verglichen, weil der Papierkorb gar nicht in der Datei stand); CSV **8 797** Einzelvergleiche, **0 unerklärt**, 859 erwartet, 0 ungenutzte Regeln. Die Proben aufs Exempel gehören **ohne** `--ausnahmen` und mit derselben Datei auf beiden Seiten gefahren — in S1 zweimal falsch aufgerufen, ohne dass die gemeldeten Zahlen davon abwichen. Eine **neue** unerklärte Abweichung gilt als Befund der laufenden Phase, nicht als Eigenart des Werkzeugs. Eine Abweichung, die sich beheben ließe, wird **nicht** in die Ausnahmeliste aufgenommen — das schriebe einen Fehler auf Dauer fest. Vor jedem Vergleichslauf wird das Demo-Konto zurückgesetzt. **Stand nach P2: gehalten.** Sicherung 286 739 Einzelvergleiche, 0 unerklärt, 16 erwartet; CSV 8 797 Einzelvergleiche, 0 unerklärt, 859 erwartet, 0 ungenutzte Regeln — beide Zahlen unverändert gegenüber S1, wie es bei einer Phase ohne Berührung eines Schreibwegs sein muss. Die Proben aufs Exempel diesmal richtig gefahren (edbak 12/12, CSV 10/10). **Ein Lehrstück aus P2:** Der edbak-Lauf lief zunächst in einen Zeitüberlauf — Ursache war keine Datenabweichung, sondern eine Ergänzung der Schwachwortliste, die das Demo-Passwort unbrauchbar machte und die Sicherung gar nicht erst entstehen ließ (F-P2-R). Die Regressionspflicht hat damit einen Fehler gefunden, der mit ihrem eigenen Gegenstand nichts zu tun hatte. |
| R25 | **Das Demo-Konto ist dauerhafter Bestandteil der Anwendung** und die einzige benannte Ausnahme vom Ende-zu-Ende-Versprechen (Konzept P1, E-P1-09): Zugangsdaten und Geräteschlüssel sind öffentlich, das Schlüsselmaterial liegt in `server/demo/`. README und Handbuch nennen die Ausnahme ausdrücklich und verweisen aufeinander. Mitzuführen in **P5** (Registrierung nach R9 und Rollenmodell nach R10 — das Demo-Konto darf in keiner Betriebsart versehentlich entstehen, verschwinden oder Rechte erben) und in **P6** (R17 prüft die Konstruktion ausdrücklich mit). **Nebenbedingung aus P2:** Das Demo-Passwort `nadokudemo0815` steht im README, im Handbuch und in jedem Prüfmittel — es muss durch die Passwortgüteprüfung kommen. Der Produktname darf deshalb erst dann in die Schwachwortliste, wenn zugleich ein neues Demo-Passwort gesetzt wird (E-P2-22, F-P2-R). Das ist eine Aufgabe für **P6**, wo der Name ohnehin kommt. **Nachtrag aus S3/AP10 (Web 12.4.1):** Auf der Kontoseite der Administration ist das Demo-Konto **gesperrt** — Ändern, Sichern, Einspielen, Freigeben und Löschen werden im Schreibweg abgewiesen, nicht bloß im Markup ausgegraut; die Karte „Sicherungen“ entfällt dort. Verwaltet wird es ausschließlich über den Reiter „Demo-Konto“. Grund: Was auf der Kontoseite eingetragen wird, überschreibt der 30-Minuten-Reset ohne Hinweis. Die Geräte-Aktionen bleiben offen, weil das Konto ausdrücklich zum Koppeln einer Uhr einlädt. Anzeigename ist **„Demo NutzerIn“**, gesetzt beim Anlegen und beim Zurücksetzen. |
| R26 | **Backlog-Zuordnung nach P1.** Neu aus P1: Nr. 23–30. Erledigt: Nr. 22 (Sofortpaket, Web 7.2.1) und Nr. 26 (CSV-Import über Mitternacht, Web 7.3.1). Zuordnung: **Nr. 24, 25, 27, 28, 29, 30 → S1** (R23) · **Nr. 23** (der JSON-Vertrag nennt eine Reanimationsart, die kein Schreibweg annimmt) **→ P6**, zusammen mit dem Vertragsreview nach R12 — eine Änderung am Vertrag ist eine Entscheidung, keine Korrektur nebenbei · **Nr. 17 → P5** wie bisher (R19), jetzt mit Messgrundlage. **Stand nach S1:** Nr. 24, 25, 27, 28, 29, 30 sind erledigt, dazu die in S1 selbst entstandenen Nr. 31–35. **S1 hinterlässt keinen eigenen offenen Punkt.** Offen bleiben damit die Punkte der früheren Zuordnungen: Nr. 2, 3, 11, 14 → P4 · Nr. 8 → P5 · Nr. 13 → P7 · Nr. 17 → P5 · Nr. 18, 19, 21 → P4 · Nr. 20 → P3 · Nr. 23 → P6. **Stand nach S2-Konzept: Nr. 2 und 3 → S2 (R34); im Übrigen unverändert.** **Stand nach Fassung 10: Nr. 13 → P6 (R46).** |
| R27 | **Zwei neue Prüfmittel aus S1, dauerhaft mitzuführen.** Der Kreislauf aus P1 kann die Fälle nicht herstellen, in denen sich die Regeln unterscheiden — sein Referenzbestand hat keinen Diensttag mit beiden Löscharten nebeneinander und keine kaputte Datei. Genau dort lagen die beiden schwersten Funde von S1, beide in dieser Phase eingebaut und durch alle bestehenden Prüfungen gelaufen. Deshalb: **`tools/wiederherstellungs-probe/`** (ruft `edbak_restore()` unmittelbar auf, misst den Zustand in der Datenbank; 30 Erwartungen, vier Teile) und **`tools/referenzdatensatz/browser/papierkorb_misch.mjs`** (Mischfall über die reguläre Oberfläche; 14 Einzelprüfungen). Beide sind gegen den jeweiligen Vorher-Stand gefahren worden und fallen dort durch — ein Prüfmittel, von dem niemand weiß, ob es scheitern **kann**, ist keines. Sie laufen künftig in jeder Phase mit, die den Papierkorb, den Rückspielweg oder die Diensttag-Zuordnung berührt; die Kreisläufe nach R24 ersetzen sie nicht. |
| R28 | **Drittes Prüfmittel aus P2, dauerhaft mitzuführen: `tools/wortliste/`.** Es prüft die Wortliste nach R3 maschinell — 23 Sperrmuster mit Wortgrenzen und Teilstring-Fallen, 44 begründete Ausnahmen, Ausgabe je Bereich: Treffer gesamt, Treffer außerhalb der Ausnahmen, **ungenutzte** Ausnahmen. Wie die Prüfmittel nach R27 ist es gegen den Vorher-Stand gefahren worden und fällt dort durch (53 Treffer in 44 Zeilen, Rückgabewert 1); gegen den Endstand steht es auf 0/0/0. **Es läuft in jeder Phase mit, die sichtbare Texte oder Dokumentation anfasst** — namentlich in P3 (das Redesign erzeugt neue Texte) und P6 (Umbenennung, Doku-Neufassung). Zwei Pflegeregeln aus der Umsetzung: In der Ausnahmeliste erklärt die **erste passende** Regel den Treffer, das Besondere steht deshalb oben und das Allgemeine unten (E-P2-20); und wer eine Regel hinzufügt, prüft, was sie **sonst noch** erklärt — eine zu weit gefasste Regel verdeckt echte Stellen, in P2 einmal passiert und aufgefallen. Die Klasse `Homonym` steht für Treffer, die schlicht etwas anderes bedeuten (die „Maschine" im Runbook ist ein Rechner; E-P2-19). Grenzen: Es prüft Wörter, keine Aussagen und keine Perspektive — die drei Stellen, die nur von der Luft her gedacht waren, ohne ein Sperrwort zu enthalten, hat eine Durchsicht gefunden, nicht das Werkzeug (F-P2-P). |
| R29 | **Die Uhr-Quelltexte werden in P6 mit umbenannt.** P2 hat `watch/` bewusst nicht angefasst (E-P2-07): Jede Änderung dort verlangt eine eigene Uhr-Auslieferung, und die soll **einmal** stattfinden statt zweimal. Offen bleiben damit die Einstiegsklasse `HemsApp` samt `manifest.xml` (`entry="HemsApp"`), die Beispieldomain `einsatz.beispiel.de` in `settings.xml`/`properties.xml` und die Kommentare mit `luftrettung.net`, „Flugtag" und „Hubschrauberdienst" (`Uploader.mc`, `Model.mc`, `StartView.mc`, `Util.mc`). Alles davon ändert das Manifest oder erfordert einen Build — deshalb zusammen mit `@Strings.AppName` in **P6**, als Teil des v1.0-Schnitts. Die Garmin-Nennungen **innerhalb** der Uhr bleiben: Dort ist Garmin der Gegenstand. **Ausnahme:** Steht vor P6 aus anderem Grund eine Uhr-Auslieferung an (Backlog Nr. 11 oder 14 in P4), werden Beispieldomain und Kommentare dort mitgenommen — die Umbenennung selbst bleibt P6. Einzelnachweis: `docs/Konzept-P2-Terminologie.md`, 10.4. **Stand nach Fassung 11: Die Ausnahme ist eingetreten und erfüllt.** Die Uhr-Auslieferung nach R47 hat die Beispieldomain (`settings.xml` — dort **sichtbar** in Garmin Connect —, `properties.xml`, `Uploader.mc`) auf `nadoku.beispieldomain.de` gezogen und die Kommentare in `Model.mc` nachgeführt (Uhr 1.11.1). `Flugmodus` in `Util.mc` bleibt: Das ist der Betriebszustand eines Geräts, in der Wortliste als `flugmodus` ausgenommen. **Stand nach Fassung 12: R29 ist erledigt** (R48). Auch die Umbenennung ist vorgezogen — `@Strings.AppName` = „NAdoku", Einstiegsklasse `NAdokuApp`, `entry="NAdokuApp"` (Uhr 2.0.0). Die Begründung, sie zu halten, war „einmal bauen, einmal ausliefern"; da für Nr. 11/14/47/48 ohnehin gebaut wurde, ist genau das eingetreten. **P6 trägt damit keine Uhr-Auslieferung mehr.** |
| R30 | **Backlog-Zuordnung nach P2: P2 hinterlässt keinen offenen Backlog-Punkt.** Die Phase hat keinen Punkt erledigt und keinen angelegt (E-P2-05); der Backlog ist unverändert. Die **vier Funde**, die P2 gefunden und bewusst nur gesammelt hat, werden nicht in den Backlog eingestellt, sondern in einer **Nacharbeit zu P2** vor oder mit dem P2-Deploy erledigt: **F-P2-J** (die Sicherungsbeschreibung führt den Rollencode `tc`, den kein Quelltext kennt, und lässt `hems` und `fr` weg) · **F-P2-S** (das Anlegen des Demo-Kontos scheitert an der global eindeutigen Gerätekennung `manual-2` aus der Fixture; die Administration zeigt den rohen SQLSTATE-Text) · **F-P2-L** (`.gitignore` 37 führt `*_rc.json`, die Einspielanleitung nennt `rc.json` — der Eintrag verfehlt genau die Datei, für die er gedacht ist) · **F-P2-Q** (zwei `.pyc` sind verfolgt, obwohl ignoriert). Begründung der Bündelung: Alle vier sind Einzeiler bis Kleinstpakete, zwei davon reine Repo-Hygiene ohne Deploy-Inhalt; ein Backlog-Eintrag kostete mehr Verwaltung als die Behebung. **Offen bleiben** damit unverändert die Punkte der früheren Zuordnungen: Nr. 2, 3, 11, 14 → P4 · Nr. 8 → P5 · Nr. 13 → P7 · Nr. 17 → P5 · Nr. 18, 19, 21 → P4 · Nr. 20 → P3 · Nr. 23 → P6. **Stand nach S2-Konzept: Nr. 2 und 3 → S2 (R34); im Übrigen unverändert.** **Stand nach Fassung 10: Nr. 13 → P6 (R46).** |
| R31 | **Personenbezogene Nennungen raus — der Kern davon ist ein Betriebsfehler, kein Datenschutzthema.** Erhebung (25.08.2026, gesamter Baum und alle 200 Commits): Die **private** Adresse `philipp@chadid.net` kommt **nirgends** vor, auch nicht in der Historie; als Commit-Autor steht die GitHub-Ersatzadresse. Was vorhanden ist, zerfaellt in drei Gruppen. **(1) Fest verdrahtete Support-Adresse — zu beheben.** `server/admin_users.php` 97, `server/reset_request.php` 103 und `server/pair.php` 219 schreiben in ausgehende E-Mails „Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.“ Die Anwendung steht unter AGPL: Jede fremde Installation nennt ihren eigenen NutzerInnen damit eine Adresse, die nicht ihr gehoert, und der Betreiber merkt es nicht — die Person, die dort antwortet, merkt es. Das ist eine **Betriebsangabe und gehoert in die Konfiguration**, mit der Admin-Adresse der Installation als Vorgabewert und einem festgelegten Verhalten, wenn keine gesetzt ist (den Satz weglassen, nicht leer ausgeben). Verortung: **P5**, zusammen mit den Admin-Optionen — es ist eine Einstellung, keine Textkorrektur. **(2) Namensbeispiele — mit dem Doku- und Kommentardurchgang.** „Uhr Philipp“ in zwei Kommentaren in `update.php` (R13) sowie `Philipp Müller` als Beispielname im Handbuch 1393 und im Beispieldateinamen (R16). Verortung: **P6**; ein realer Vorname als Beispiel ist unnoetig, wo ein erfundener dasselbe zeigt. **(3) Markenfarbnamen — bleiben.** `/* Philipp Orange */`, `/* Newroz Rot */` und `/* Max Blau */` in `server/assets/style.css` und `watch/source/Ui.mc` benennen korrekt die Herkunft des Hexwerts aus den Brand Guidelines; sie zu entfernen naehme dem naechsten Leser die Auskunft, woher der Wert stammt. Falls P3 sie ohnehin anfasst (Backlog Nr. 20, Hex-Literale auf Token), wandert der Name mit ins Token — er verschwindet nicht. **Abschliessende Pruefung in P6:** ein Durchgang ueber den ganzen Baum auf Namen und Adressen, bevor das neue Repositorium entsteht. Dort beginnt die Historie neu, und was dann nicht drinsteht, steht nie drin. Die Historie des **alten** Repositoriums laesst sich nicht nachtraeglich saeubern, ohne sie umzuschreiben; sie enthaelt nach dieser Erhebung aber nichts, was dort nicht stehen duerfte. |
| R32 | **Impressum und Datenschutzerklärung als zwei öffentliche, betreiberseitig editierbare Seiten.** Beide sind ohne Anmeldung erreichbar und in **jeder** Fußzeile verlinkt, einschließlich Anmelde-, Zurücksetz- und Abbruchseite. Der Inhalt ist eine **Betriebsangabe je Installation**: Die Anwendung liefert keinen Rechtstext mit, sondern je Seite ein Feld im Admin-Bereich; ist es leer, zeigt die Seite den Hinweis, dass der Betreiber den Text noch nicht hinterlegt hat — keine leere Seite. Format: **eingeschränktes Markdown** (Überschriften, Absätze, Listen, Links), serverseitig in wenige erlaubte Elemente übersetzt, kein HTML. Speicherung in der **Datenbank**, nicht in Dateien, damit Sicherung, Wiederherstellung und Update die Texte mitnehmen. **Verortung:** Seitenhülle, Fußzeile, die beiden Seiten **und der Admin-Editor in P3** — dort entsteht die Fußzeile neu, und die Links dürfen nicht bis P5 ins Leere zeigen; als eigenes Arbeitspaket mit ausdrücklich gekennzeichneter Funktionsänderung (P3 ist sonst ein Umbau ohne Verhaltensänderung). **P5** nimmt die beiden Felder in die Admin-Optionen auf, zusammen mit der Support-Adresse nach R31. Beschluss vom 25.08.2026 im P3-Konzeptgespräch. |
| R33 | **Servicemodell: Abonnements, Zahlungen und die dafür nötigen Kontooptionen.** Die Anwendung soll als Dienst mit Abomodellen betrieben werden können; dazu gehören Tarif, Laufzeit, Zahlungsstand und Rechnungen je Konto sowie die Verwaltung durch die Administration. **Verortung P5** (eigenes Konzeptkapitel, Konzept vor v1.0). P3 legt mit der **Kontoseite** (E-P3-41) die Stelle an, an der Verwaltungsaufgaben je Konto liegen — Kontodaten, Geräte, Sicherungen und ein reservierter Platz „Abonnement" —, sodass P5 dort einhängt statt eine weitere Seite zu bauen. Beschluss vom 26.08.2026 im P3-Konzeptgespräch. |
| R34 | **Zwischenpaket S2 „Mengen, Spurspeicherung und Sicherung" zwischen P3 und P4.** Anlass: Zieldimensionen 5 000 Einsätze je Konto und 500 Konten × 600 Einsätze auf geteiltem Webspace (10 GB MySQL-Kontingent) mit fünf Jahre alten Endgeräten. Befund (gemessen; Konzept S2, Abschnitt 1): 93 % des Bestands sind Spurpunkte — 40 MB je 1 000 Einsätze als Zeilen, bei Zielmenge 12–14 GB und damit über dem Kontingent; die einteilige Sicherung bricht auf alten Geräten bei ~400–500 Einsätzen (der Browser parst und stringifiziert den ganzen Bestand), serverseitig an `memory_limit`/`post_max_size`; die tägliche Wartung macht einen Vollscan über `track_points` in einer Nutzeranfrage. Kernentscheidungen: **dreistufige Spurspeicherung** — Zeilen als Eingangspuffer der Uhr → nach `final` + 14 Tagen verlustfreier Blob (32-Bit-Differenzen spaltenweise + zlib, 3,3 B/Punkt, ÷20) → sechs Monate nach Einsatzende ausgedünnter Blob (Douglas-Peucker dreidimensional, 2 m horizontal / 3 m Höhe; Phasenpunkte bleiben; Original danach verworfen; zusammen ~1,1 MB je 1 000 Einsätze). Nachlieferungen der Uhr werden bis zur Ausdünnung eingearbeitet, danach verworfen und quittiert — **der JSON-Vertrag bleibt unverändert**. **GPX-Abruf** je Einsatz (Backlog Nr. 3): im ersten Halbjahr Original, danach gekennzeichnet ausgedünnt; Nr. 2 wird durch die Ausdünnung geschlossen. **Sicherungscontainer Fassung 4**: ZIP mit versiegeltem Manifest, Kern und Spurpaketen; die GCM-Zusatzdaten binden Sicherungskennung und Teilnummer (fehlende oder vertauschte Teile fallen kryptographisch auf); eine PBKDF2 je Vorgang; Erstellung vollständig im Browser, Wiederherstellung Kern + Pakete mit POSTs ≤ 2 MB; **die einteilige `.edbak` der 7.x-Linie bleibt lesbar — R11 unberührt**. Admin-Sicherungen paketiert über dieselbe Bibliothek; **„Alle sichern" in Schüben** (aus P5 übernommen); Aufbewahrung Standard 2 je Konto, manuell mehr; **Speichergrenze für `sicherungen/` mit Warnschwellen und Mail**, im Admin-Bereich einstellbar (Vorgabe 2 GB). **Komplettbackup der Installation** (alle Konten, Schlüsselhüllen, `app_state`, Migrationsstand; `config.php` bleibt im Wiederanlaufpaket): eigener SQL-Dump in Häppchen, versiegelt mit Serverschlüssel aus `config.php`, Direktdownload und Push auf **FTP/FTPS/SFTP** (phpseclib vendoriert) nach Zeitplan, App-Rückweg „Installation wiederherstellen" bei leerer Datenbank, Runbook in Technik 7. **Ein Job-Einstieg mit drei Auslösern** (CLI-Cron, zeitgesteuerter URL-Aufruf mit Token, anfragegetrieben) — der bewusste Cron-Verzicht wird gelockert, nicht aufgegeben; Wartung ohne Vollscan; Suche mit Schlüssel-Cache und Stapeln. Ausdrücklich **nicht**: Jahrescontainer für `pat_blob`, automatische versiegelte Serversicherungen je Konto (Konto-Schlüsselpaar als Backlog), WebDAV (Backlog). E2E bleibt unverändert; alle Messwerte, Formate und Begründungen im Konzept (E-S2-01 bis E-S2-24). Beschlüsse vom 28.–30.08.2026. **Stand nach S2 (01.09.2026):** vollständig umgesetzt und ausgeliefert (Web 10.0.0 bis 12.2.0). Die Zielzahlen sind gehalten, zwei davon deutlich (Spuren 1,10 statt ≤ 3 MB je 1 000 Einsätze; Tagesansicht 1,17 statt ≤ 3 s). Backlog Nr. 2 und Nr. 3 sind geschlossen; neu hinzu kamen zehn Punkte (Nr. 46 bis 55). Eine Abweichung vom Beschluss ist benannt und begründet: Der Migrationslauf nach der Wiederherstellung läuft **nicht** in `wiederherstellen.php` mit, weil `update.php` seit M6-01 zweistufig ist — als Backlog Nr. 54 festgehalten. |
| R35 | **Viertes dauerhaftes Prüfmittel: der Messstand `tools/messstand/`** (aus S2, Arbeitspaket 0). Ein Vervielfältiger erzeugt aus den Referenz-Quelldaten reproduzierbar ein 5 000-Einsätze-Konto — eingespielt über die regulären Wege, kein SQL an der Validierung vorbei (Geist von R4) —, dazu eine Browserprobe mit CPU-Drossel (Faktor 6 als Ersatz für fünf Jahre alte Geräte) und eine Serverprobe (Laufzeiten, Speicherspitzen, Tabellengrößen). Zielzahlen in Konzept S2, E-S2-24 (u. a. Suche ≤ 5 s, Sicherung erstellen ≤ 5 min, Wiederherstellung ≤ 15 min, kein Browserschritt über 10 MB JSON). Er läuft in jeder Phase mit, die Spurspeicherung, Sicherungsformat, Suche oder andere Mengenpfade berührt; die Ausgangsmessung vor dem S2-Umbau ist der Vergleichsmaßstab. Wie die Prüfmittel nach R27/R28 gegen den Vorher-Stand gefahren: Dort dokumentiert er die Bruchstelle, statt zu bestehen. Riegel gegen Läufe auf der Referenzinstallation wie bei `demo_pruefen.mjs` (S1). **Stand nach S2:** steht und ist gefahren — Bestand 5 000 Einsätze / 1,12 Mio. Zeilen. Der Messstand hat dabei **sich selbst** als fehlerhaft erwiesen: `browserprobe.mjs` wartete vier Sekunden auf einen Entsperrdialog, der bei entsperrter Sitzung nie kommt, mitten im gemessenen Abschnitt — die Ausgangsmessung war an zwei Stellen `max(4 s, tatsächliche Dauer)`. Berichtigt; der Befund „Tagesansicht 62 % über dem Ziel" löst sich damit auf. |
| R36 | **Zielbild Dienstbetrieb.** Die Anwendung wird als offener Dienst mit Selbstregistrierung betrieben; Betriebsgröße bis 1 000 Konten (die technischen Zielmaße Z1–Z3 aus S2 bleiben der Maßstab der Mengenpfade). Grundlage: Dienstbetriebs-Konzeptgespräch vom 30.08.2026. Sein Befund: Der Anwendungscode ist weitgehend vorbereitet (Ratenschutz mit Enumerationsschutz, Sitzungszähler, Gerätelimit, NutzerInnen-Liste und Kontoseite aus P3, R32-Seiten); die drei großen Lücken liegen daneben — **Betriebsplattform** (Jobs liefert S2, Deploy/Staging R40), **Konto-Lebenszyklus** (R37) und **Recht/Support** (R41). Drei Grundsatzfestlegungen: **Keine Telemetrie** — Betriebszahlen ausschließlich aus vorhandenen Spalten (R38), es wird nichts Neues erfasst (eine benannte Ausnahme: die Gerätekennung nach R42 — eine einmalige Geräteeigenschaft beim Koppeln, keine Nutzungsdaten); das ist Verkaufsargument, nicht Verzicht. **Zielmarkt deutschsprachig**, Anzeigezeitzone Europe/Berlin — bewusste Festlegung statt stiller Annahme. **Hosting-Entscheidung vor dem P5-Konzept** (Zuarbeit): Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Grundschutz des Hosters, Verschlüsselung at rest; geteilter Webspace nach Z2 bleibt die Untergrenze, die die Anwendung tragen muss — viele heutige Behelfe (Huckepack-Wartung, Zeitbudgets) existieren nur wegen dieser Plattform, ein besserer Tarif löst eine ganze Problemklasse auf einmal. |
| R37 | **Konto-Lebenszyklus und Registrierungs-Sicherheitspaket — Konkretisierung zu R9, Verortung P5.** Beschlüsse vom 30.08.2026: **(1)** Eine Bibliothek für den Lebenszyklus: Das Anlegen (Konto + Kennung + Setz-Token in einer Transaktion) wandert aus `admin_users.php` heraus; Einladung und Selbstregistrierung nutzen denselben Code — sonst entsteht die zweite Fassung derselben Transaktion. **(2)** Kontostatus aktiv / unbestätigt / gesperrt; `auth_guard` prüft je Anfrage (Endegrund `gesperrt` neben dem vorhandenen `konto`), und **auch `ingest.php` weist gesperrte Konten ab** — die Uhr puffert und liefert nach Entsperrung idempotent nach. **(3)** Double-Opt-In; unbestätigte Konten verfallen nach 48 h über den S2-Job-Einstieg. **(4)** Die Registrierungsseite gibt **keine Kontoauskunft**: immer dieselbe Antwort; an eine bereits registrierte Adresse geht stattdessen eine „du hast hier schon ein Konto"-Mail (dasselbe Prinzip wie `reset_request.php`). Ratenschutz je IP, global **und je Zieladresse** (sonst ist die Registrierung eine Mailbomben-Schleuder; Töpfe nach dem Muster `demo`/`demog`); Demo-Adresse ausgeschlossen; statt CAPTCHA (wäre Fremdquelle) Honeypot-Feld plus Mindestausfülldauer, notfalls Proof-of-Work im Browser. **(5)** **Selbstlöschung** des Kontos mit 30 Tagen Karenz (deaktivieren → Mail → endgültig über den Job) statt sofortiger FK-Kaskade; die Admin-Löschung der Kontoseite bleibt. **(6)** **E-Mail-Wechsel** nur mit Bestätigungslink an die neue und Hinweismail an die alte Adresse (heute schreibt das Profil die Adresse ungeprüft um). **(7)** **Einwilligungen** (Nutzungsbedingungen, AVV, Datenschutz) als Bestandteil der Registrierung, gespeichert mit **Fassungskennung und Zeitstempel**; Re-Consent-Weg bei Textänderung (Anknüpfung: `rechtstexte`, `stand_am`). **(8)** Die **IP-Grenzwerte des Ratenschutzes** werden für NAT/CGNAT neu austariert — `login` sperrt heute nach 10 Fehlversuchen je 15 min auch je IP; hinter einem Klinik-NAT trifft das Unbeteiligte. **(9)** **Mail-Warteschlange** über den S2-Job-Einstieg — löst zugleich die in `ratelimit_lib.php` selbst als Notlösung bezeichnete Zeitmaskierung des Mailversands ab; SPF/DKIM/DMARC und Bounce-Postfach der Versanddomain sind Zuarbeit. **(10)** Geräteschlüssel-Prüfung in `ingest.php` von bcrypt auf SHA-256-Vergleich (der Schlüssel ist ein Zufallswert hoher Entropie, kein Passwort; spart 60–100 ms CPU je Upload; sanfte Migration beim nächsten erfolgreichen Upload) — zusammen mit der R19-Bremse zu entwerfen, ebenso eine **Mengengrenze je Konto** (Uhr und Import), damit ein kompromittierter Geräteschlüssel die Datenbank nicht fluten kann. **(11)** **Onboarding** für Selbstregistrierte: geführter Erststart (Standort → Rettungsmittel → Uhr), druckbares Notfallblatt für den Wiederherstellungsschlüssel, einmalige Besitz-Rückfrage nach ~30 Tagen — der Schlüsselverlust ist absehbar Support-Thema Nr. 1 und by design unheilbar; das gehört gesagt, bevor es passiert. |
| R38 | **Rollen, Administration und Betriebszahlen — Konkretisierung zu R10, Verortung P5.** Beschlüsse vom 30.08.2026: **Support-Rolle** neben `user`/`admin` (Metadaten sehen, Setz-Link neu senden, Gerät deaktivieren — kein Einspielen, kein Löschen, keine Stammdaten; `ist_admin()` nach M1-15 ist die eine Andockstelle). **Pflicht-Zweitfaktor** für Admin-Konten (TOTP nach RFC 6238, lokal ohne Fremdquelle implementierbar) und mindestens zwei Admin-Konten (Bus-Faktor). **Audit-Protokoll** administrativer Handlungen je Konto — schützt auch die Admins selbst. Kontoseite ergänzt Sperren/Entsperren und „Verifikationsmail erneut senden". **Ankündigungsbanner** (`app_state`) und Rundmail über die Warteschlange; an der Fehlerkennung steht künftig, wohin sie gemeldet wird; dazu eine Admin-Sicht aufs **Fehlerprotokoll** (Suche nach Kennung) und ein **Health-Endpunkt** für externes Monitoring. **Betriebslage-Dashboard mit festem Minimalumfang** (Beschluss 30.08.2026 — mehr ausdrücklich nicht): Konten gesamt und **aktiv in 24 h / 7 T / 30 T** („aktiv" = `users.last_login` **oder** `devices.last_seen` im Fenster — sonst zählte reine Uhr-Nutzung als tot); Einsätze gesamt und **in 24 h / 7 T / 30 T / 6 M / 1 J** (nach `started_at`, nicht `created_at` — ein Alt-Import verzerrte sonst die Aktivität; ohne Demo-Konto, ohne Papierkorb; dafür ein Index auf `missions(started_at)` — der vorhandene führt mit `user_id` und trägt die kontenübergreifende Zählung nicht). **Ergänzung vom 30.08.2026 (R42):** dazu die **Geräteverteilung** — Art (Uhr/Handy/Sonstiges) und genaue Bezeichnung, gezählt je echtem Gerät; Geräte ohne gespeicherte Kennung erscheinen als „unbekannt". Der Minimalumfang bleibt sonst unverändert. Alle Zahlen kommen aus vorhandenen Spalten (R36: keine Telemetrie; die Gerätespalten legt das R42-Kleinstpaket an); Baustein `ui_kennzahl` aus P3 existiert. |
| R39 | **Zentrale (systemweite) Stammdaten entfallen.** Beschluss vom 30.08.2026: Im Dienstbetrieb pflegt jede NutzerIn ihre Stammdaten selbst. Das Modell „Admin kuratiert zentrale Einträge für alle" (E15-Zentraleinträge mit `user_id IS NULL`, E16-Auswahl, `admin_stammdaten.php`) skaliert nicht auf 1 000 fremde Konten — die Betreiberin kann fremde Wachen weder kennen noch pflegen — und wird in **P5 zurückgebaut**, einschließlich Doku-Austragung (entfernte Funktionen werden ausgetragen; R16/P6 findet den Stand ausgetragen vor). Die eingefrorenen Diensttage (E8) sind vom Rückbau unberührt — genau dafür gibt es den Snapshot. Das im Gespräch ausgearbeitete Alternativmodell — **Regionen mit Unteradmins** (Region hängt am zentralen Standort und vererbt über E15 auf alle Untertypen; `user_regions` n:m, weil NotärztInnen in mehreren Bereichen arbeiten; Unteradmin als Zusatzbefugnis in eigener Tabelle, ausdrücklich ohne jeden Kontoeinblick; null Regionen = heutiges Verhalten) — ist **verworfen, aber festgehalten**: Es wandert beim nächsten Anfassen des Backlogs mit neuer Nummer hinein, falls der Bedarf mit organisierten Trägern (Wachen, Verbände) wiederkommt. |
| R40 | **Deploy-Umbau, Neuaufsetzen und Datenübernahme.** Beschlüsse vom 30.08.2026: Die heutige private Instanz wird nicht weiterbetrieben; ihr einziges Konto wird in die Dienst-Installation übernommen — der Weg dafür **ist** R11 (edbak in eine frische Installation; die Abnahmedatei liegt seit S1, S2 hält das Altformat lesbar, Z6). Zeitplan (Festlegung wie erbeten): **(1) S2 und P4 laufen noch mit dem heutigen Autodeploy** — die Produktivläufe (Messstand, Fixture, PHP-Grenzen des Hosters) brauchen ihn, und das Risiko ist klein, solange die Instanz privat ist. **(2) Mit Beginn von P5 endet der Autodeploy auf die Produktivinstallation:** Push auf `main` deployt nur noch auf eine **Staging-Installation** (zweites FTP-Ziel, Zuarbeit); Produktiv wird manuell nach Freigabe bespielt. Begründung: P5 fasst Anmeldung, Registrierung und Rollen an — die Änderungen mit dem größten Schadenspotenzial gehören nicht ungestuft auf den Server. K7 (committen je Paket, ein Push am Phasenende nach Freigabe) gilt unverändert; nur das Ziel des automatischen Deploys ändert sich. **(3) Am P6-Schnitt wird einmalig neu aufgesetzt:** frische v1.0-Installation aus dem neuen Repositorium, Übernahme des Bestandskontos per edbak (R11), Demo-Konto neu nach Runbook; danach **eine Probe des Komplettbackup-Zyklus (S2/AP8) auf der Produktivumgebung** — eine Sicherung, die nie probeweise wiederhergestellt wurde, ist eine Hoffnung, kein Backup. **(4) Das neue Repositorium startet mit Release-getriggerter Auslieferung** (`main` → Staging, Release-Tag → Produktion) mit vorgeschaltetem **CI-Prüftor** — Kreisläufe (R24), Wortliste (R28), Messstand (R35) laufen vor jedem Produktiv-Deploy; die Werkzeuge sind skriptbar und müssen nur in den Weg gestellt werden — und dokumentiertem **Rollback-Weg**. Weil Migrationen selten rückwärtsfähig sind, kommt (Verortung P5) ein **Wartungsmodus-Torwächter**: Erkennt die Anwendung eine ausstehende Migration, zeigt sie eine Wartungsseite, statt in Fehler zu laufen — bei vielen aktiven NutzerInnen ist das Fenster zwischen Deploy und `update.php`-Aufruf sonst ein sichtbarer Ausfall. |
| R41 | **Recht und Betreiberorganisation vor der Öffnung.** Beschlüsse vom 30.08.2026; die Mechanik liegt in P5 (R37.7), Texte und Unterlagen sind Zuarbeit, die abschließende Prüfung liegt in P6. **AVV** als Bestandteil der Registrierung: Die NotärztInnen sind Verantwortliche ihrer Dokumentation, der Betreiber wird Auftragsverarbeiter für jede einzelne — bei 1 000 Konten ist das der eigentliche Brocken der Öffnung. **§ 203 StGB** (Dienstleister als mitwirkende Person) in den Vertragstexten adressieren; **VVT und TOMs** dokumentieren (die E2E-Verschlüsselung ist das Kernargument); Breach-Prozess festlegen; **MDR-Abgrenzung** einmal prüfen und festhalten (reine Dokumentation, keine Diagnose-/Therapieunterstützung — vermutlich unkritisch, aber die Prüfung gehört ins Papier). Die **Datenschutzerklärung nennt ehrlich die Grenze der E2E**: Spur- und Phasenkoordinaten liegen im Klartext, und eine Flugspur *ist* der Einsatzort — bei 1 000 Konten ist die Datenbank ein flächendeckendes Lagebild; das Bedrohungsmodell (Fortschreibung in S2/AP10) benennt das ausdrücklich, der R17-Review (P6) prüft den Umgang mit Dumps und `sicherungen/` unter diesem Blick, und Speicherdauern (IP-Adressen in `rate_limits`: ein Tag per Aufräumjob; Hoster-Logs) gehören in die Erklärung. Dazu: **`security.txt`** und Meldeadresse auf den öffentlichen Seiten; **Betreiberhandbuch/Notfallplan** (Zugänge zu Hoster, Domain, Mail, Repositorium; zweiter Admin; Wiederanlaufpaket aus S2) als Teil der R16-Doku-Neufassung; eine einfache **externe Statusseite** (das In-App-Banner hilft beim Ausfall naturgemäß nicht); die Todesfall-/Erben-Grenze (ohne Passwort oder Wiederherstellungsschlüssel gibt es nichts herauszugeben) in die FAQ. **Öffnung in Wellen** über die R9-Betriebsarten: erst „nur auf Einladung", dann „offen mit Freischaltung", dann „offen" — jede Welle misst Support-Aufkommen, Zustellbarkeit und Last mit begrenztem Schadensradius. Die **Verteilung der Uhr-App** (Connect-IQ-Store statt Seitenladung) ist Teil des Betriebsübergangs nach v1.0 und wird mit P7/R12 geklärt. |
| R42 | **Gerätekennung bei der Kopplung — Speicherung sofort, Auswertung in P5.** Beschluss vom 30.08.2026: Beim Koppeln übermittelt das Gerät seine **Art** (Uhr / Handy / Sonstiges) und seine **genaue Bezeichnung** (bei Garmin das Gerätemodell). Die Uhr-Seite wird unmittelbar eingebaut (gesondert beauftragt, außerhalb dieses Plans). **Speicherung:** zwei Spalten an `devices` (Art, Modell); `pair.php` nimmt die Angaben entgegen — **fehlend ist zulässig** und ergibt „unbekannt", damit ältere Uhr-Fassungen unverändert koppeln; Nachtrag in `docs/JSON-Vertrag.md` (Kopplungsanfrage) und `docs/Geraete-Eingabe.md`. Verortung: **eigene Kleinauslieferung VOR S2**, zusammen mit der Uhr-Änderung — nach dem Muster von R20 (Sofortpaket Nr. 22, ausgeliefert als Web 7.2.1 vor P1): eigener Zweig, ein Commit, eigene Versionsstufe, eigener Deploy nach Freigabe. **Kein Konzept nach K1 und kein Prüfdokument nach K9** — das Paket ist klein genug, dass dieser Eintrag die Spezifikation ist. **Nicht in S2 einhängen:** S2 ist eine Phase aus elf Arbeitspaketen; ein Zweizeiler und eine Spaltenmigration darauf zu warten zu lassen, verzögert beides ohne Gewinn, und die berührten Dateien (`devices`, `pair.php`, `keyguard.js`) überschneiden sich mit keiner S2-Baustelle (Spuren, Sicherung, Jobs) — es gibt also auch nichts zusammenzuführen. **Zu beachten beim Deploy:** Das Paket bringt eine Schemaänderung mit; nach dem Hochladen muss eine Administratorin `update.php` aufrufen (Abschnitt 3 der Arbeitsanweisung) — die Angabe entsteht **nur beim Koppeln**; jede Kopplung vor der Speicherung ginge der Statistik verloren, und Bestandsgeräte bleiben „unbekannt", bis sie neu gekoppelt werden. Die Angabe stammt vom Gerät selbst und ist Herkunftsauskunft, keine geprüfte Wahrheit — für eine Statistik genügt das. **Auswertung:** im Betriebslage-Dashboard (R38, P5) als **Geräteverteilung** je Kategorie und je Bezeichnung, gezählt über echte Geräte (ohne das virtuelle Gerät `manual-%`, ohne Demo-Konto). Dies ist die eine benannte Ausnahme der R36-Formel „es wird nichts Neues erfasst": erfasst wird eine **einmalige Geräteeigenschaft beim Koppeln**, keine Nutzungsdaten. **Stand nach Fassung 11: Die Uhr-Seite ist ausgeliefert** (Uhr 1.9.0) — die Uhr sendet beim Koppeln einen Block `geraet` mit Teilenummer, Displaymaßen, Touch, Firmware, Connect-IQ- und App-Fassung; Feldliste und Begründungen stehen im JSON-Vertrag, Abschnitt 1a. Die Uhr sendet **die Teilenummer, nicht den Modellnamen** — den kennt sie nicht; die Auflösung (325 Teilenummern → 173 Modelle, samt Geräteart) gehört auf den Server. `uniqueIdentifier` wird bewusst **nicht** gesendet. **Die Serverseite steht weiter aus** (Backlog Nr. 59): `pair.php` verwirft den Block derzeit stillschweigend. Das Kleinstpaket vor S2 bleibt damit offen — und jede Kopplung bis dahin geht der Statistik verloren. |
| R43 | **Zwischenpaket S3 „Oberflächen-Nacharbeit und vertikaler Rhythmus" nach S2.** Anlass: die Rückmeldungsliste vom 31.08.2026 (`ToDo_Layout.pdf`, acht Seiten, 19 Punkte am ausgelieferten Stand Web 9.14.1). **Der Befund, der die Verortung als eigenes Paket rechtfertigt** — nachgemessen am Stylesheet, nicht vermutet: Die Frage der Liste („Warum passen die Abstände häufiger nicht? Gibt es dafür keine Definition?") hat eine genaue Antwort, und sie lautet nicht „jemand hat krumme Werte benutzt". Die **Werteskala wird eingehalten**: `--abstand-1` bis `--abstand-5` (4/8/12/16/24 px), 222 Deklarationen ziehen ihre Abstände aus diesen Token, ganze **zwei** Stellen tragen einen Rohwert (davon einer ist eine 1-px-Linie). Was fehlt, ist die Stufe darüber — eine **Anwendungsregel**, die sagt, *welche* Stufe *wo* gilt: nach einer Überschrift, zwischen Formular und nächstem Abschnitt, unter einer Knopfreihe. Ohne sie wird die Wahl an jeder der 222 Stellen einzeln getroffen, und 29-mal fällt sie auf `abstand-3`, 15-mal auf `abstand-4`, 15-mal auf `abstand-1`, ohne dass ein Muster dahinterstünde. Der von der Liste bemängelte Fall belegt es: In `einstellungen.php` steht „Profil speichern" als **nackter `ui_knopf()` zwischen `ui_karte_ende()` und `</form>`** — obwohl es mit `.listen-form-fuss` (`margin-top:var(--abstand-4)`) längst einen Baustein für den Formularfuß gibt. Der Abstand fehlt also nicht, weil eine Zahl falsch wäre, sondern weil an dieser Stelle **kein Baustein benutzt wird**. Daraus folgt die Arbeitsweise, die dieses Paket verbindlich macht (ausdrückliche Vorgabe: **kein CSS-Flickwerk**): **(1)** Der vertikale Rhythmus wird als **Regelwerk in `docs/Design.md`** festgeschrieben — je Beziehung (Überschrift → Inhalt, Inhalt → nächster Abschnitt, Formular → Fuß, Karte → Karte) genau eine Stufe der bestehenden Skala, mit Begründung; neue Token nur, wenn die fünf Stufen nachweislich nicht reichen. **(2)** Umgesetzt wird **an Klassen und Bausteinen** (`ui.php`, `stammdaten_ui.php`, die Kartenbausteine), nie als Einzelregel an einer Seite; wo ein Baustein fehlt, entsteht er, statt die Stelle zu flicken. **(3)** Eine Einzelkorrektur an einer Seite ist begründungspflichtig und wird im Konzept benannt. **(4)** Prüfmittel sind vorhanden und laufen mit: Stilvergleich (Soll-Ist-Liste gegen die geplanten Änderungen — bei einem Paket mit beabsichtigten Gestaltungsänderungen ist das Ergebnis keine Null, sondern eine abgeglichene Liste), `tools/screenshots/` über acht Breiten, die Vollständigkeitsprüfung des Stylesheets (Klassen ohne Regel **und** Regeln ohne Klasse — die Liste gehört gelesen, nicht nur gezählt, siehe F-P3-BA), Wortliste nach R28 für die geänderten Texte. **Ein echter Fehler in der Liste, Ursache bereits gefunden:** Die Marker von Standort und Zielklinik sitzen umso weiter östlich, je weiter herausgezoomt wird. `.geo-schild` ist eine Flex-**Spalte** mit `align-items:center`; das Icon-Wurzelelement wird damit so breit wie sein breitestes Kind — das **Namensschild** (`white-space:nowrap`), nicht der 44-px-Kasten. `geo.js` verankert aber mit `iconAnchor:[22,22]` bei `iconSize:null`, also auf der Kastenmitte. Bei „Klinikum Immenstadt" liegt der Kasten dadurch rund 50 px zu weit rechts — ein **konstanter Pixelversatz**, und genau das erklärt die Beobachtung: Dieselben 50 px sind herausgezoomt Kilometer und hereingezoomt Meter. Der Versatz wächst mit der Länge des Namens. Er verschwindet mit der ohnehin gewünschten Streichung der Beschriftung von selbst — die Behebung stellt trotzdem `iconSize` ausdrücklich, damit ein künftiges Beiwerk am Marker den Fehler nicht erneut einträgt. **Verortung:** eigenes Zwischenpaket **nach S2**, unabhängig von P4 und parallel zu ihm führbar; eigenes Konzept nach K1 und Prüfdokument nach K9, weil das Abstandsregelwerk eine Gestaltungsentscheidung vor der Umsetzung ist und nicht nebenbei fällt. **Kein Fable-Schritt** — die Gestaltungslinie steht in `docs/Design.md`; dieses Paket schreibt sie fort, es erfindet sie nicht. **Die drei offenen Punkte sind am 31.08.2026 entschieden und gehen ohne weitere Rückfrage in die Umsetzung:** **E-R43-1 — Die Sammelleiste übernimmt die Kartenform.** Keine Sonderform, keine begründete Abweichung: `.speichern` bekommt Radius und Breite der Karte darüber, damit am unteren Rand derselbe Baukasten steht wie im Inhalt. Der sticky Sitz und die Trennlinie nach oben bleiben — sie tragen die Funktion (die Leiste klebt), nicht die Form. Der negative Randausbruch (`margin: … calc(var(--abstand-3) * -1)`) entfällt damit; er war die Ursache der abweichenden Breite. **E-R43-2 — Die Leistenüberschrift bleibt linksbündig und wird größer.** Entscheidung gegen die horizontale Zentrierung, aus drei Gründen: `.leiste-kopfzeile` ist ein **geteilter Baustein an vier Stellen** (Diensttage, Einstellungen, Administration, Filter der Suche) — nur „Diensttage" zu zentrieren wäre der Sonderfall, den dieses Paket gerade abschaffen soll, und alle vier zu zentrieren bräche die linke Lesekante der Einstellungsleiste, deren Einträge mit Symbolen anfangen. Zweitens teilen Überschrift und Einträge darunter heute eine gemeinsame linke Kante; zentriert stünde jede Überschrift je nach Wortlänge woanders, und das Auge springt. Drittens — und das ist der eigentliche Punkt — ist „wirkt verloren" ein Problem von **Größe und Kontrast**, nicht von Ausrichtung: Die Zeile steht in `--groesse-2` und `--gedaempft`, also klein und grau; Zentrieren verschiebt sie, sichtbarer macht es sie nicht. Umgesetzt wird deshalb am Baustein: eine Stufe höher in der Schriftskala und kräftigere Farbe (`--asphalt` statt `--gedaempft`); ob Versalien und Sperrung bei der größeren Stufe bleiben, entscheidet die Umsetzung am Bild — gesperrte Versalien lesen sich ab einer gewissen Größe als Etikett, nicht als Überschrift. **Die Änderung trifft alle vier Stellen** und ist damit vier beabsichtigte Abweichungen im Stilvergleich; das ist gewollt, denn dieselbe Zeile ist an den anderen drei Stellen genauso leise. **E-R43-3 — „Winden-Cycles / Flugtag" bleibt.** Der Nebenbefund ist geprüft und entschieden: Die Kachel steht in der Luftrettungsansicht, und dort ist Flugsprache ausdrücklich zulässig (E-P2-04). Kein Eingriff, kein Eintrag in die Wortliste — die Ausnahmeliste nach R28 führt den Fall bereits als zulässig; sollte das Prüfmittel ihn dennoch melden, bekommt er dort eine Ausnahme mit dieser Begründung, nicht eine Umformulierung. |
| R44 | **Der Entsperrdialog erscheint mitten in der Arbeit, obwohl die Sitzung läuft — Ursache gefunden, Behebung vorgezogen.** Beobachtet am 31.08.2026 („in letzter Zeit immer wieder"); die Erwartung dahinter ist richtig: Läuft die Frist ab, soll **abgemeldet** werden, nicht nachentsperrt. **Befund am Code (Web 9.14.1), nicht vermutet:** Beide Fristen stehen auf 30 Minuten und sollen ausdrücklich gleich sein — `keyguard.js` sagt es im Kopf: „Muss zu SESSION_TIMEOUT_S in auth_guard.php passen. Bewusst gleich und nicht kürzer: Ein Schlüssel, der VOR der Sitzung abläuft, erzeugt einen Entsperrdialog mitten in der Arbeit und keinen Sicherheitsgewinn." Genau das tritt trotzdem ein, weil die beiden Uhren **Verschiedenes messen**: `auth_guard.php:73` schreibt `$_SESSION['last_seen'] = time()` bei **jeder** Anfrage — eine **Inaktivitätsfrist**, die sich mit jedem Klick erneuert. `keyguard.js` setzt seinen Zeitstempel dagegen nur beim **Entpacken** (`ablegen()`, aufgerufen aus `contentKey()` nach `EdCrypto.getContentKey()` und aus `binden()`); der Treffer im Zwischenspeicher gibt den Schlüssel in Zeile 91 zurück, **ohne den Zeitstempel zu erneuern**. Das ist eine **absolute Frist ab dem Entsperren**. Folge: Wer länger als 30 Minuten am Stück arbeitet, hält die Sitzung mit jedem Klick am Leben, verliert den Inhaltsschlüssel aber pünktlich zur halben Stunde — und bekommt den Dialog, den der Kommentar gerade verhindern wollte, danach wieder alle 30 Minuten. Je aktiver gearbeitet wird, desto sicherer tritt es ein. **Behebung:** Der Zeitstempel wird beim Treffer mit erneuert, damit auch der Schlüssel eine **Inaktivitätsfrist** führt und beide Uhren dasselbe messen. Das ist die kleinere und richtige Angleichung — die Gegenrichtung (Sitzung ebenfalls absolut) hieße, aktive NutzerInnen mitten in der Arbeit abzumelden. Das Versprechen bleibt damit, was es sein soll: Läuft die Frist ab, endet die Sitzung, und die nächste Anfrage landet auf der Anmeldeseite, die die Schlüssel ohnehin räumt. **Zweite, davon unabhängige Ursache — kein Fehler, aber zu benennen:** `sessionStorage` gilt je **Tab**. Wer die Anwendung in einem zweiten Tab öffnet, hat dort keinen Schlüssel und bekommt den Dialog, obwohl die Sitzung läuft. Das ist so gewollt (der Schlüssel soll nicht über Tabs wandern) und bleibt; es gehört ins Handbuch, damit der Dialog dort nicht als Fehler gelesen wird. **Verortung:** Behebung im **R42-Kleinstpaket** — also in derselben Kleinauslieferung **vor S2** (zwei Zeilen in `keyguard.js` plus Versionsstufe), nicht erst in S3 — der Dialog stört täglich, die Änderung ist klein und berührt keine der S3-Baustellen. Prüfung: eine Sitzung über mehr als 30 Minuten mit regelmäßiger Bedienung, danach ein Leerlauf über die Frist hinaus — im ersten Fall kein Dialog, im zweiten die Abmeldung. |
| R45 | **Zwischenpaket S4 „Handy- und Uhr-Client (Android/Wear OS), Schneidewerkzeug und GPX-Import" nach S3.** Anlass (31.08.2026): NotärztInnen ohne Garmin-Uhr sollen ihre GPS-Spur aufzeichnen und ihre Phasen dokumentieren können; die Verwaltung bleibt im Browser. **Befund am Vertrag, nicht vermutet:** `docs/JSON-Vertrag.md` ist ausdrücklich clientneutral („Wer einen neuen Client baut, implementiert gegen diesen Text"), die Anmeldung läuft je Gerät über `X-Device-Id`/`X-Api-Key` aus der Code-Kopplung (`pair.php`, sechs Zeichen, zehn Minuten, einmal einlösbar), und die Nachricht `rest_segment` trägt bereits eine Spur **ohne** Phasen mit fortlaufender `seq`. Ein Handy kann also heute einen ganzen Dienst als `rest_segment`-Kette senden, ohne dass am Server eine Zeile geändert wird. Was fehlt, ist das **Schneiden** im Browser: aus einem Ruhesegment einen Einsatz mit von Hand gesetzten Phasenzeiten machen. Das braucht jeder Client — auch die Garmin-Nutzerin, die den Knopf vergessen hat — und ist deshalb der eine Serverteil, an dem alle Varianten hängen. **Was es nicht gibt, ist eine App für Handy und alle Uhren zugleich:** Uhr-Apps sind immer nativ (Wear OS: Kotlin/Compose, watchOS: Swift), Flutter und Verwandte decken sie nicht ab; eine PWA scheidet aus, weil Android und iOS Hintergrund-GPS nach Minuten beenden — ein 12-Stunden-Dienst geht damit nicht. **Beschlüsse vom 31.08.2026 (E-R45-1 bis E-R45-10):** **E-R45-1 — Das Handy zeichnet auf, die Uhr ist Fernbedienung.** Der Vordergrunddienst des Handys führt das Dauer-GPS über den ganzen Dienst; die Wear-OS-App trägt nur die Phasenknöpfe (Phasen 2–9 nach Vertrag Abschnitt 7, **keine Reanimation** — die bleibt Garmin), setzt die Zeitstempel auf der Uhr, puffert bei Funkabriss und meldet über den Wear Data Layer ans Handy, das quittiert. Grund: Wear-OS-Uhren halten mit Dauer-GPS sechs bis zehn Stunden, keine Schicht; Garmin ist da eine andere Klasse. Die Phasenknöpfe stehen **zusätzlich auf dem Handy** als Rückfall, damit die App auch ohne Uhr vollständig ist. **E-R45-2 — Der JSON-Vertrag bleibt unverändert.** Dauer-Aufzeichnung als `rest_segment`-Kette, Einsätze mit Phasen als `mission` wie von der Garmin; neue **Präfixe der Client-Kennung** für Handy und Uhr-Fernbedienung als Nachtrag in Abschnitt 8 (beschrieben, vom Server wie bisher nicht geprüft); Kopplung über den vorhandenen Code-Weg, die App liest Server-Adresse und Code aus einem **QR-Code** der Geräteseite oder von Hand; beim Koppeln Art „Handy" und Modell nach R42. Live-Upload **über Mobilfunk** während des Dienstes, Teilstücke zu höchstens 500 Punkten, SQLite-Puffer auf dem Handy, Nachlieferung idempotent über `client_ref` — dasselbe Sendeverhalten wie die Uhr, damit die R19-Bremse und die Mengengrenze je Konto (R37.10) beide Clients gleich behandeln. **E-R45-3 — Schneidewerkzeug im Browser.** Ruhesegment öffnen, Einsatzbeginn und -ende setzen, Phasenzeiten von Hand eintragen → Einsatz mit echter Spur, der Rest bleibt Ruhesegment; Rückgängig, solange nichts Weiteres am Einsatz hängt. Es arbeitet auf der **S2-Spurspeicherung** (`spur_lib.php`, Zeilen wie Blob) und liegt deshalb zwingend **nach S2**. **E-R45-4 — GPX-Import** als Gegenstück zum GPX-Abruf aus S2/AP4 (Präfix `imp-` besteht), wahlweise als Ruhesegment zum Schneiden oder unmittelbar als Einsatz. Damit ist „ohne Uhr" ab dem Web-Teil von S4 mit **jeder** vorhandenen Tracker-App möglich, bevor die eigene App fertig ist; die eigene App ersetzt nur den Handgriff. **E-R45-5 — Kein iOS, kein watchOS im Programm.** Aus dem Container heraus ist keine iOS-App baubar (Swift auf Linux baut kein UIKit/CoreLocation); der Ausweg über einen macOS-Runner verlangte Apple-Developer-Konto, Signatur und jeden Gerätetest beim Auftraggeber. Die Apple Watch wird nicht gebaut — endgültig, siehe R46. **E-R45-6 — Verteilung ohne Store:** signiertes APK zum Herunterladen aus der Web-App (Seitenladung), für einen bekannten Kreis. Play Store (25 $ einmalig, für neue Konten Pflicht zu 12 Testern über 14 Tage vor Freigabe) ist eine Entscheidung des Betriebsübergangs nach v1.0 (wie die Connect-IQ-Verteilung, R41) und setzt die **Mengenbremse und Mengengrenze je Konto aus P5** voraus — ein öffentlich verteilter Client mit Geräteschlüssel ohne Bremse ist die Flutungsgefahr aus R37.10. Der Signaturschlüssel ist Zuarbeit und wird außerhalb des Repositoriums verwahrt. **E-R45-7 — Die Uhr-App wird blind gebaut.** Es liegt keine Wear-OS-Uhr vor; der Nachrichtenweg Uhr↔Handy wird mit Tests belegt, der Gerätetest folgt, wenn eine Uhr da ist (für das S24 die natürliche Wahl: Galaxy Watch4 oder neuer — die laufen Wear OS, ältere Tizen-Modelle nicht; Installation per ADB über WLAN nach Anleitung). Das Handy wird mit **einem** Dienst auf dem S24 geprüft; erfahrungsgemäß zwei bis drei Runden, weil Samsungs Akkuoptimierung („Apps im Tiefschlaf") Hintergrunddienste beendet — die App führt beim Erststart durch die Freistellung, ob sie hält, zeigt nur das Gerät. **E-R45-8 — Prüfung ohne Emulator.** Der Android-Emulator braucht KVM und steht im Container nicht zur Verfügung. Geprüft wird: Gradle-Build headless; Aufzeichnungs- und Sendelogik mit Robolectric gegen **synthetische Positionsströme aus den Referenz-Payloads** (P1/R4: 526 Ingest-Anfragen, plattformneutral erzeugt — sie sind genau die Eingabe, die ein zweiter Client wiedererzeugen muss); Server-Rundlauf gegen `ingest.php` im Container; der Web-Teil mit den vorhandenen Prüfmitteln (Kreisläufe R24 — geschnittene und importierte Einsätze müssen durch Sicherung und CSV kommen —, `tools/screenshots/`, Wortliste R28, Messstand R35 für das Schneiden auf dem 5 000-Einsätze-Konto). Nicht prüfbar aus dem Container: echtes GPS, Akku, Bluetooth. **E-R45-9 — Ablage im Repositorium:** `android/` neben `watch/`, ein Gradle-Projekt mit den Modulen Handy und Uhr; Fremdbestandteile in `docs/Lizenzen.md`; Handbuch-Kapitel und `docs/Geraete-Eingabe.md` gerätefrei nach dem P2-Muster (E-P2-02), Garmin und Wear OS je als Zusatz. Ob der APK-Bau in das CI-Prüftor nach R40.4 kommt, entscheidet das Konzept; die Umbenennung am P6-Schnitt (R29) trifft `android/` wie `watch/`. **E-R45-10 — Kein Fable-Schritt.** Neu ist keine Kryptographie, sondern die **Ablage des Geräteschlüssels auf dem Handy** (Android Keystore) und der QR-Kopplungsweg; beides prüft der R17-Review in P6 mit, zusammen mit dem Umstand, den R41 schon benennt: Spur- und Phasenkoordinaten liegen im Klartext — für die Handy-Spur gilt das genauso wie für die Uhr-Spur. **Verortung:** eigenes Zwischenpaket **nach S3** (der Browserteil folgt dem dort festgeschriebenen Rhythmus, und `spur_lib.php` aus S2 ist Voraussetzung), unabhängig von P4 und parallel zu ihm führbar, **vor P5** — die App braucht P5 nicht, ihre öffentliche Verteilung schon (E-R45-6). Eigenes Konzept nach K1 (die Arbeitspakete der Handy- und Uhr-App gehören mit ihren Abnahmekriterien dorthin, nicht in diesen Eintrag) und Prüfdokument nach K9; die Backlog-Punkte Nr. 11 (Sync-Anzeige) und Nr. 14 (Kopplungsablauf geteilter Geräte) aus P4 gelten sinngemäß für den neuen Client — S4 greift ihnen nicht vor, sondern übernimmt, was P4 dafür beschließt, oder legt den Punkt als offene Frage ins Konzept. **Nachtrag gleichen Datums, drei Fragen an das Konzept vorab entschieden:** **E-R45-11 — Das Schneiden sitzt in der Tagesansicht am Ruhesegment**, keine eigene Seite: Dort liegen Diensttag, Einsätze und Ruhesegmente schon nebeneinander, und der geschnittene Einsatz erscheint an der Stelle, an der er entsteht. **E-R45-12 — Phasenkonflikt: beide Einträge bleiben.** Liefern Uhr und Handy für dieselbe Phase je einen Zeitstempel, werden beide gesendet und gespeichert — der Vertrag erlaubt Mehrfacheinträge ausdrücklich und verbietet jedem Client das Entdoppeln (Abschnitt 3); die Bereinigung ist eine Korrektur im Browser, wie bei der Garmin auch. **E-R45-13 — Dienst starten und beenden an Handy oder Uhr.** Beide tragen den Knopf; die Dienstkennung `day_ref` erzeugt das Handy (es ist der Sender), die Uhr löst nur aus. Ein zweiter Start bei laufendem Dienst ist kein neuer Dienst, sondern eine Anzeige „läuft seit …". **Nachtrag Fassung 13 (R49) — E-R45-2 geändert:** Die Kopplung folgt dem umgekehrten Code-Weg aus S5 (Gerät zeigt Code, Web nimmt ihn entgegen, Rückbestätigung in der App); der QR-Code der Geräteseite trägt **nur noch die Server-Adresse** — Bequemlichkeit für Selbsthoster, kein Geheimnis darin —, und die Handy-App führt wie die Uhr `nadoku.gen-em.org` als Vorgabewert (E-R49-8). „Der Vertrag bleibt unverändert" gilt weiter für den Ingest; Abschnitt 1a wird mit S5 neu geschrieben, Block B setzt ihn um. Folge für die Reihenfolge: Das Kopplungsmodul von Block B wird **zuletzt** gebaut, nach dem S5-Konzept; die übrigen Pakete von B und C laufen weiter parallel. E-R45-10: Der R17-Review prüft statt „QR-Kopplungsweg" den S5-Weg und den Adress-QR. |
| R46 | **Die Apple Watch wird nicht gebaut; P7 entfällt.** Beschluss vom 31.08.2026, im Anschluss an R45: keine iOS- und keine watchOS-App, weder im Programm noch danach. Begründung wie E-R45-5 (nicht aus dem Container baubar, Apple-Developer-Konto, Signatur, jeder Gerätetest beim Auftraggeber) — nur ist das jetzt keine Verschiebung, sondern das Ende der Frage. Was von P7 übrig bleibt, ist **Backlog Nr. 13** (Kosmetik der Garmin-Uhr); es wandert in die **Uhr-Auslieferung von P6** (R29: AppName, Einstiegsklasse, Beispieldomain, Logo-Wahl — eine Auslieferung), wo ohnehin gebaut wird. R12 bleibt als Geschichte stehen; seine Basisfähigkeit (Referenz-Payloads, plattformneutrale Texte, Vertragsreview) ist mit S4 nicht umsonst gewesen, sondern der Grund, warum der zweite Client ohne Vertragsänderung auskommt. Die Phasenfolge nach R2 endet damit mit P6. **Stand nach Fassung 11: Backlog Nr. 13 ist erledigt** — nicht erst in P6, sondern vorgezogen mit der Uhr-Auslieferung nach R47, weil dort ohnehin gebaut wurde. Damit trägt die P6-Uhr-Auslieferung nur noch die Umbenennung nach R29. |
| R47 | **Die Garmin-Uhr-Auslieferung ist vorgezogen und abgeschlossen.** Beschluss und Umsetzung vom 31.08.2026. Ausgeliefert als **Uhr 1.10.1 bis 1.11.1**, dazu **Web 9.15.0**. Anlass: Für Backlog Nr. 60 (Logo-Wahl auf der Uhr, aus P3) stand ohnehin eine Uhr-Auslieferung an; alles andere, was einen Uhr-Build braucht, ist mitgenommen worden, statt es auf P4 und P6 zu verteilen — nach demselben Grundsatz, den R29 für die Umbenennung formuliert: **eine Auslieferung statt zweier.** **Was drin ist:** *Nr. 60* — Bildmarke wählbar (luft-/bodengebunden/wechselnd) als App-Einstellung statt als Server-Übertragung; die Uhr kennt die Kontoeinstellung nicht, und eine Einstellung, die man auf der Uhr sieht, gehört auch dorthin (1.10.0). *Nr. 11* — die Sync-Seite behauptet nicht mehr „Sync vollständig", wenn die Uhr gar nicht senden kann; der grüne Zustand setzt jetzt Server-Adresse **und** Kopplung voraus, sonst tritt „Nicht eingerichtet" an seine Stelle (1.10.1). *Nr. 61* — Launcher-Symbol in allen neun geforderten Größen (42 Compiler-Warnungen → 0, ohne ein Byte Zuwachs) und die Bildmarke in vier gerechneten Größenstufen: 25,0–28,8 % der Displayhöhe statt 15–34 % (1.10.2/1.10.3, Freigabe mit Mockup). *Nr. 14* — abfragen → trennen → neu koppeln, mit Serverseite (s. u.); dazu die beiden Entscheidungen, dass ein **Rückstand das Trennen verhindert** (offene Pakete gehören dem bisherigen Konto) und dass **lokal immer getrennt wird**, auch ohne Antwort vom Server (1.11.0). *Nr. 13* — die letzten vier Meldungen der strengen Typprüfung sind aufgelöst; sie galten als „nicht ohne Änderung der Datenstruktur lösbar", brauchten aber nur einen Cast auf die gemeinte Alternative des PolyType, und eine der vier war ein **falscher** Cast (`PropertyValueType` statt `Storage.ValueType`). Kosten: 0 Byte, gemessen (1.11.0). *R29-Mitnahme* — Beispieldomain und Kommentare (1.11.1). **Die Serverseite, die Nr. 14 nach sich zieht:** `pair.php` kennt ein zweites Anliegen `{"aktion":"trennen"}` mit den Kopfzeilen aus JSON-Vertrag Abschnitt 1; der Server **löscht** das Gerät statt es zu deaktivieren, sonst belegte es weiter einen der `MAX_GERAETE` Plätze. **Ohne Migration** — `update.php` muss niemand aufrufen. Neuer Vertragsabschnitt **1b**. **Neues Werkzeug:** `tools/uhr-bilder/erzeugen.sh` rastert Launcher-Symbole und Bildmarken aus den beiden SVG; sein Rezept ist aus den vorhandenen Dateien zurückgerechnet und reproduziert sie bitgleich — bis dahin lagen die PNG ohne Rezept im Repositorium, genau der Zustand, vor dem `tools/logos/LIESMICH.md` seit P3 warnt. **Prüfstand:** `tools/uhr-pruefstand/` läuft jetzt über **99 Geräte** mit strenger Typprüfung; Endstand 99 übersetzt, 0 fehlgeschlagen, **0 Warnungen, 0 Fehler**. **Was nicht geprüft werden konnte:** die Rückfrage vor dem Trennen ist nicht fotografiert (der Simulator beantwortet sie mit demselben Tastendruck, der sie auslöst — belegt über einen Konsolenmitschnitt), und der Trennen-Zweig in `pair.php` ist **nicht gegen eine Datenbank gelaufen**. Beides gehört in den Gerätetest. **Wirkung auf den Plan:** P4 verliert Nr. 11 und Nr. 14, P6 verliert Nr. 13; P6 behält die Umbenennung nach R29. Kein Konzept nach K1 und kein Prüfdokument nach K9 — dieser Eintrag und der Changelog sind die Spezifikation. |
| R48 | **Die Uhr-Umbenennung ist vorgezogen, und die Anwendungs-ID ist keine Platzhalterin mehr.** Beschluss vom 31.08.2026 im Anschluss an R47, auf ausdrückliche Anweisung („alle Uhr-Punkte abarbeiten, Entscheidungen jetzt fällen"). Ausgeliefert als **Uhr 2.0.0**. **(1) Der Name.** `@Strings.AppName`, der Titel auf dem Startbildschirm und die Beschriftung der Server-Einstellung tragen **„NAdoku"**; die Einstiegsklasse heißt `NAdokuApp` (`HemsApp.mc` → `NAdokuApp.mc`), die Köpfe aller 17 Quelldateien ziehen mit. Für das Gerätemenü die **Kurzform ohne „Gen-EM"** — der Träger gehört in den Store-Eintrag, nicht auf ein Uhrendisplay, und `nadoku.beispieldomain.de` führt dieselbe Kurzform seit P2 (E-P2-03). **Der Preis ist benannt und angenommen:** Bis zum v1.0-Schnitt heißt die Uhr anders als Web und Handbuch. Genau das wollte R29 vermeiden; die Abwägung fällt anders aus, weil ohnehin gebaut wurde und ein zweiter Uhr-Build in P6 damit entfällt. Das Handbuch sagt es an der Stelle, an der sonst jemand vergeblich sucht (Kapitel 12, Schritt 2). **(2) Die Anwendungs-ID.** Im Manifest stand `a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6` — von Hand durchgezählt, kein Zufallswert; der Kommentar darüber verlangte selbst den Austausch, die SDK-Beispiele tragen durchweg echte UUID, und der Connect-IQ-Store (R41) braucht eine. **Warum jetzt:** Die ID ist die Identität der App auf dem Gerät; ein Wechsel kostet jede gekoppelte Uhr eine Neukopplung. Heute ist das **eine** Uhr, nach der Öffnung wären es alle — der Aufwand wächst mit jedem Tag, der Nutzen bleibt gleich. Der Manifest-Kommentar hält Grund und Zeitpunkt fest und sagt, dass hier nicht mehr geändert wird. **Was dazu nicht behauptet wird:** Die Folge für den Gerätespeicher ist **nicht gemessen**. Der Simulator legt seinen App-Speicher unter `GARMIN/APPS/DATA/<DATEINAME>.DAT` ab — benannt nach der geladenen `.prg`, nicht nach der Anwendungs-ID; er kann die Frage also gar nicht beantworten. Die Annahme folgt der Plattformdokumentation. Der Gerätetest zeigt es. **Wirkung auf den Plan:** R29 ist abgeschlossen, **P6 trägt keine Uhr-Auslieferung mehr**. Von den Uhr-Punkten bleibt allein Backlog Nr. 59 (R42), bewusst als eigenes Kleinstpaket. **Stand nach Fassung 13:** S5 (R49) bringt eine weitere Uhr-Auslieferung — umgekehrte Kopplung und Vorgabewert der Server-Adresse. Sie hängt an keiner Phase außer S5; **P6 bleibt ohne Uhr-Auslieferung.** |
| R49 | **Zwischenpaket S5 „Kopplung umgekehrt" zwischen S2 und S3.** Anlass (01.09.2026): Die Kopplung verlangt heute, einen im Web erzeugten Sechs-Zeichen-Code **auf der Uhr** einzutippen (Sync-Seite → START halten → Zeichen für Zeichen mit den Tasten) — für eine einzelne Uhr erträglich, für 500 Konten und einen zweiten Client (S4) nicht. Geprüft und verworfen: ein **QR-Code auf der Uhr** (technisch machbar — der Server liefert die Modulmatrix, die Uhr malt; Version 3 passt mit 5 px je Modul auf 240-px-Displays, aber abhängig von Domainlänge, Ruhezone und MIP-Kontrast; Garmins Bildproxy scheidet aus, weil das Token einen Dritten passieren würde) und **`Communications.openWebPage`** (Link direkt in den Handy-Browser; plattformabhängig verlässlich, hätte Prototypen auf iOS und Android gebraucht). Beschlossen ist der einfachste Weg: **Das Gerät zeigt einen Code, das Web nimmt ihn entgegen** — der Device-Code-Flow, wie ihn Fernseher-Anmeldungen benutzen, mit dem Menschen als Transportweg. Kein QR, keine Displaygrenze, keine Kamera, keine Plattformfrage, und **ein** Serverprotokoll für Garmin und die Android-App. **Beschlüsse vom 01.09.2026:** **E-R49-1 — Ablauf.** `start`: Das Gerät sendet den `geraet`-Block (R42); der Server legt eine Kopplungssitzung an und antwortet mit Anzeigecode (6 Zeichen aus `PAIR_CHARS`, 10 Minuten) **und** den Zugangsdaten `device_id`/`api_key`. Das Gerät zeigt den Code („AB3 K7Q — im Web unter Einstellungen → Geräte eingeben"). Im Web ersetzt ein Feld „Code vom Gerät" den Knopf „Kopplungscode erzeugen"; eine Bestätigungsseite zeigt Art und Modell des Geräts — dafür wird die Serverseite von R42 (Backlog Nr. 59) tatsächlich gebraucht — und bindet die Sitzung ans Konto; hier greifen `MAX_GERAETE` und die Kopplungsmail. `status`: Das Gerät fragt nach; die Antwort „beansprucht" trägt das Kontolabel. `bestaetigen`: Das Gerät fragt „Mit … koppeln? Ja/Nein"; nach Ja legt der Server das Gerät an und aktiviert es, das Gerät speichert die Zugangsdaten und zeigt den Haken wie heute. **E-R49-2 — Schwebende Zugangsdaten statt schwebender Geräte.** Die Zugangsdaten entstehen bei `start`, liegen aber bis `bestaetigen` nur in der Sitzungstabelle (Nachfolgerin von `pair_codes`: Code, `device_id`, Schlüssel-Hash, `geraet`-Block, Frist, Zustand, beanspruchendes Konto); ein `devices`-Datensatz entsteht erst am Ende. So gibt es keine halbfertigen Geräte, die `MAX_GERAETE`, die Geräteliste oder `ingest.php` verwirren, und die Eigenschaft bleibt, dass der Server vom Schlüssel **nur den Hash** speichert — der Klartext geht wie heute genau einmal ans Gerät, nur eben schon bei `start`. **E-R49-3 — Der Code ist nur für den Menschen.** `status` und `bestaetigen` weisen sich mit den Kopfzeilen aus Vertragsabschnitt 1 (`X-Device-Id`/`X-Api-Key`) aus, nicht mit dem Code; wer den Code abliest, kann damit am Gerät nichts auslösen. **E-R49-4 — Rückbestätigung am Gerät, Label ist die maskierte E-Mail.** Das Kontolabel ist die E-Mail-Adresse des beanspruchenden Kontos, maskiert (`ph***@gen-em.de`) — immer vorhanden, für die Trägerin als eigene erkennbar, für einen Ableser wenig wert. Es wird **nur im Dialog** gezeigt und **nicht gespeichert**; die Uhr hält wie bisher allein `device_id` und `api_key`, die Sync-Seite zeigt danach „Gekoppelt" ohne Kontoangabe (entschieden gegen eine dauerhafte Anzeige: eine Personenangabe mehr auf der Uhr, und die Trennen-Rückfrage aus Nr. 14 kommt ohne aus). Nein am Gerät oder Fristablauf verwirft Sitzung und Zugangsdaten. **E-R49-5 — Zwei Angriffsflächen, zwei Tore.** Heute braucht, wer koppeln will, den Code aus dem Konto; künftig kann jeder eine Sitzung starten, und das Tor ist der Klick der Kontoinhaberin. (a) *Fremdes Gerät im eigenen Konto* („gib mal Code X ein"): Tor ist die Bestätigungsseite mit Art und Modell, die Kopplungsmail bleibt als Entdeckungsnetz. (b) *Eigenes Gerät im fremden Konto* (Code vom Handgelenk abgelesen und schneller eingegeben): Tor ist die Rückbestätigung nach E-R49-4. Das Bedrohungsmodell (S2/AP10) wird um beide Fälle fortgeschrieben. **E-R49-6 — Ratenschutz gedreht.** Der Topf `pair` zählt heute uhrseitige Fehlversuche je IP; künftig zählt die Code-Eingabe im Web **je Konto und je IP**, `start` wird **je IP** begrenzt, und es gibt eine **Obergrenze offener Sitzungen insgesamt** — sonst könnte jemand den Code-Raum (30 Bit) mit eigenen Sitzungen füllen und auf Vertipper hoffen. Frist 10 Minuten, Aufräumen über den S2-Job-Einstieg. Die Zahlen legt das Konzept fest. **E-R49-7 — Der alte Weg entfällt; Vertragsabschnitt 1a wird neu geschrieben.** Kein „Kopplungscode erzeugen" mehr; die manuelle Geräteanlage mit Eintrag von ID und Schlüssel in Garmin Connect bleibt als Rückfall. Abschnitt 1a wird durch die drei Anliegen ersetzt (1b „trennen" bleibt) — eine **Vertragsänderung**, bewusst **vor** dem zweiten Client, damit S4 gleich das neue Protokoll umsetzt und keine zweite Umstellung fällig wird; „der Vertrag bleibt unverändert" (E-R45-2) gilt weiter für den Ingest, für die Kopplung nicht mehr. Preis: Nach dem Server-Umstieg koppelt keine ältere Uhr-Fassung mehr — Bestand heute: eine Uhr. **E-R49-8 — Vorgabewert der Server-Adresse: `nadoku.gen-em.org`**, in der Uhr-App (`serverUrl` in `properties.xml`, bisher bewusst leer mit der Begründung „jede Installation hat ihren eigenen Server" — das war vor R36 richtig) **und** in der Android-App. Seit dem Zielbild Dienstbetrieb gibt es eine öffentliche Installation, und die App im Store ist die von Gen-EM; Selbsthoster überschreiben den Wert wie bisher in Garmin Connect bzw. in der App (dort auch per Adress-QR, E-R45-2 neu). Ergebnis für den Regelfall: App installieren → Sync-Seite → START halten → Code steht da → im Web eingeben → auf der Uhr bestätigen; Garmin Connect wird nie geöffnet. Einen anderen Weg, eine Connect-IQ-Einstellung von außen zu setzen, gibt es nicht — kein Link, kein QR, den Garmin Connect entgegennähme; Garmins Mobile SDK (Nachrichten einer eigenen Android-App an die Uhr) und ein zentraler Vermittlungsdienst sind geprüft und verworfen: ersteres hilft nur Garmin-Nutzern mit Android-App und nie auf iOS, letzteres ist Überbau und für Selbsthoster eine Abhängigkeit von Gen-EM. Die Domain samt TLS-Zertifikat ist Zuarbeit (Abschnitt 7); der Platzhalter `nadoku.beispieldomain.de` (E-P2-03) wird an den Stellen ersetzt, die S5 ohnehin anfasst. **Verortung:** eigenes Zwischenpaket **S5 nach S2, vor S3** (die S-Nummern zählen die Einfügung, nicht die Ausführung). Nicht als Kleinauslieferung vor S2 wie R42: Das R42-Kleinstpaket durfte vor S2, weil es *keine gemeinsame Datei* mit S2 hat; S5 hat mehrere — `einstellungen.php` (Geräteabschnitt gegen Sicherung), `docs/JSON-Vertrag.md` (S2/AP10 schreibt ihn), der Wartungsjob (räumt heute `pair_codes`, S2 baut ihn um), Migrationen, Technik und Handbuch. Vor S3, weil S3 `einstellungen.php` umbaut und den neuen Geräteabschnitt vorfinden soll, statt den alten nachzuarbeiten. Nach dem R42-Kleinstpaket, weil `pair.php`, `devices` und Abschnitt 1a dort geschrieben werden und E-R49-1 die Gerätespalten voraussetzt. **Das Konzept wird erst erarbeitet, wenn S2 und das R42-Kleinstpaket durch sind** (Anweisung 01.09.2026) — Konzept nach K1, Prüfdokument nach K9; die Beschlüsse dieses Eintrags gehen dort als E-S5-… ein, offen bleiben dort nur Zahlen, Wortlaute und der Paketschnitt. Schemaänderung → `update.php` nach dem Deploy. **Kein Fable-Schritt** — keine neue Kryptographie, das Protokoll ist ein bekanntes Muster; der R17-Review in P6 prüft es mit, wie E-R45-10 die Schlüsselablage auf dem Handy. **Wirkung auf S4:** E-R45-2 ist geändert (Nachtrag in R45); das Kopplungsmodul von Block B setzt Abschnitt 1a aus dem S5-Konzept um und wird deshalb in Block B **zuletzt** gebaut, während die übrigen Pakete von B und C parallel weiterlaufen. |
| R50 | **Terminologie-Umstellung „Sicherung" → „Backup" — Verortung nach S3.** Beschluss vom 31.08.2026: Die Anwendung nennt dieselbe Sache an zwei Stellen verschieden, und das wird vereinheitlicht — auf **„Backup"**, weil es die klarere Beschreibung ist. **Anlass, unmittelbar sichtbar:** Auf der Sicherungsseite (`einstellungen.php`, Reiter `backup`) heißen die Karten „Backup erstellen" und „Backup einspielen", die Knöpfe darin „Sicherung erstellen" und „Sicherung einspielen" — dieselbe Handlung, zwei Wörter, ein Bildschirm. **Der Befund, gemessen und nicht geschätzt (Stand Web 9.15.0):** Die Umstellung ist die **größere** Richtung, nicht die kleinere — 272 Treffer „Sicherung" gegen 60 „Backup" in `server/` (PHP und JS), dazu 407 in `docs/`. „Sicherung" ist heute die Hauptsprache; eine dokumentierte Entscheidung für eines von beiden gab es nie, P2 hat das Wortpaar nicht angefasst. Beruhigend ist die Aufschlüsselung: Nur **74 Fundstellen sind sichtbare Texte** in Zeichenketten, dazu rund zehn im HTML der Vorlagen; der große Rest steht in Kommentaren (`backup_lib.php` 20 von 20, `validate_lib.php` 5 von 5, `adminbackup_lib.php` 33 von 42). Die Kommentare gehen trotzdem mit: Einer, der „Sicherung" erklärt, während der Code daneben „Backup" sagt, ist genau die Drift, die diese Umstellung beseitigen soll. **Fünf Grenzen, je mit Grund — was NICHT umgestellt wird:** **(1)** `server/sicherungen/` bleibt. Das Verzeichnis steht in `.gitignore` **und** in der Ausnahmeliste des Deploys; der Kommentar dort sagt „ZWINGEND seit A8.2 … ohne diesen Eintrag löscht der nächste Deploy" die Admin-Sicherungen. Ein umbenanntes Verzeichnis fiele aus der Liste — der Bestand wäre beim nächsten Deploy weg. CLAUDE.md Abschnitt 3 hält denselben Eintrag fest. **(2)** `docs/CHANGELOG.md` (136 Treffer) wird **nicht rückwirkend** umgeschrieben: Jeder Eintrag beschreibt, was zu seiner Zeit gebaut wurde; ein Eintrag von Web 4.6.0, der nachträglich „Backup" sagt, behauptet etwas, das damals nicht dastand. Der neue Eintrag benutzt „Backup" und hält fest, dass ältere das alte Wort führen. **(3)** Abgeschlossene Phasendokumente bleiben samt Dateinamen (`Konzept-S1-Sicherung-Import.md` 37, `Pruefdokument-S1-…` 13, `Konzept-P3-Oberflaeche.md` 68, `Konzept-P2-Terminologie.md` 9) — sie sind Protokoll, nicht Oberfläche. **(4)** Fachbegriffe, die schon so heißen: `.edbak`, `sealBackup()`, `adminbackup_lib.php`. **(5)** Die Abgrenzung zum Export bleibt wörtlich („Dies ist kein Backup. Ein Export ist zum Weiterverarbeiten in anderen Programmen gedacht.") — sie wird durch die Umstellung stärker, nicht schwächer. **Drei Entscheidungen liegen vor, nicht drin:** das **Verb „sichern"** (25 Vorkommen, vier Knopfbeschriftungen „Alle sichern", „Auswahl sichern", „Jetzt sichern" ×2 — Empfehlung: stehen lassen, „Jetzt sichern" erzeugt ein Backup, und Deutsch hat für „backuppen" kein brauchbares Wort); der **Symbolname** `sicherung` (`assets/images/symbole/sicherung.svg`, sechs Verwendungen plus die **erzeugte** Symboltabelle in `Design.md` — Empfehlung: stehen lassen, ein Symbolname ist kein sichtbarer Text, der Gewinn ist null); der **Dateiname** `admin_sicherungen.php` (35 Verweise über 14 Dateien, darunter `tools/screenshots/seiten.json` und `tools/vollstaendigkeit/streichliste.md`; dazu lädt der FTPS-Deploy hoch, ohne aufzuräumen — die alte Datei bliebe auf dem Server erreichbar. Empfehlung: eigenes Paket mit eigenem Prüfweg oder gar nicht). **Verortung: nach S3, nicht nach S2.** Der erste Entwurf sagte „nach S2"; das ist zu früh. S5 baut den Geräteabschnitt von `einstellungen.php` um, S3 die Seite selbst — die Parallelisierungsübersicht in Abschnitt 5 führt S3 ausdrücklich als „nicht parallel zu S2" wegen genau dieser Datei. Wer die Texte vor S3 umstellt, lässt sie von S3 gleich wieder umschreiben. **Kein Konzept nach K1 und kein Prüfdokument nach K9** — die Vorlage `docs/Umstellung-Backup.md` ist die Spezifikation; sie trägt Befund, Grenzen, Arbeitsliste je Datei und die Prüfwege. **In einem Zug, nicht in Etappen:** Zehn der achtzehn betroffenen Dateien hält der S2-Zweig heute gleichzeitig (Stand AP5). Eine halb durchgeführte Terminologie-Umstellung ist schlechter als gar keine — vorher weiß man, dass zwei Wörter dasselbe meinen, nachher muss man raten, ob der Unterschied Absicht ist. **Prüfen:** Die Wortliste nach R28 ist Pflicht, **misst diese Umstellung aber nicht** — sie fragt nach Land- und Luft-Neutralität und kann Vollständigkeit hier nicht bestätigen; sie beantwortet die andere Frage, ob beim Umschreiben ein Luftwort neu hineingeraten ist. Der Vollständigkeitsbeleg ist eine eigene Gegenprobe (`grep -rc "Sicherung" server/ docs/`, jede verbliebene Fundstelle gegen die Grenzenliste). Dazu die Browserprüfung der vier betroffenen Seiten — eine Zeichenkette in einem selten erreichten Fehlerzweig fällt sonst nicht auf. **Nachgesehen, weil es naheliegt und nicht stimmt:** `tools/wortliste/ausnahmen.json` enthält „Sicherung" fünfmal, aber ausschließlich in den *Begründungstexten* von Ausnahmen für Luftwörter (`flugtage`, `hems`, „Flugtag", „Flugspur"). Keine Ausnahme wird durch die Umstellung ungenutzt; dort ist nichts zu tun. |

## 4. Phasen

### P0 — Aufräumen
**Ziel:** Weniger Fläche und tragfähige Struktur für alles Folgende.
**Inhalt:** Arbeitspakete A1–A4 aus dem Konzept „Aufräumen vor Mobilumbau"
(unverändert beschlossen). Neu: **A6 Strukturreview** — sind alle Dateien
nötig, was lässt sich zentralisieren und standardisieren; Ergebnis ist eine
Entscheidungsliste, ausgeführt wird ein etwaiger Ordnerumbau vor P3 (R7).
Das frühere A5 (Doku-Abgleich) ist aufgeteilt: sichtbare Texte → P2,
Gesamtabgleich → P6.
**Konzeptstand:** liegt in neuer Fassung vor (an das Programm angepasst;
A5 aufgeteilt, A6 Strukturreview und bedingtes A7 Strukturumbau ergänzt).
**Aus der Umsetzung hervorgegangen:** `tools/stilvergleich/` — rechnerischer
Nachweis unveränderten Erscheinungsbilds bei CSS-Umbauten; vom Deploy
ausgenommen. Einsatzregeln stehen in `CLAUDE.md`, Abschnitt 6.
**Umsetzungsstand:** A1–A3 umgesetzt (Web 7.1.0); A4 und A6 als Befund
vorgelegt, einzeln entschieden und als Nacharbeit N1–N6 umgesetzt
(Web 7.2.0). **A7 entfällt** (E-A6-12). Abweichungen von den
Konzeptvorgaben — Titeltrenner-Mehrheit, F2-Auflösung — sind im Konzept P0
als P-01/P-02 begründet und abgenommen. Offen: Bedienprüfung am laufenden
System (Prüfdokument, ~1,5 h; V-8/V-9 mit Testkonto) und Deploy-Freigabe
(K7).

### P1 — Referenzdatensatz und Demo-Account
**Ziel:** Ein generierter, vollständiger Beispieldatensatz als Demo-Account
**und** als Regressionsreferenz — das Projekt hat bisher keinerlei Tests.
**Inhalt:** 30–40 fiktive Einsätze über 2026 gemäß R4, mit
Abdeckungsmatrix: jedes dokumentierbare Feld und Szenario (Luft/Boden,
Diensttage mit mehreren Schichten, Reanimation, Standorte, Rettungsmittel,
Papierkorb-Fälle …) kommt mindestens einmal vor. Erzeugung der Tracks und
Payloads; Einspielskripte auf Basis der anwendungseigenen Wege; kanonische
Referenz-Exporte (Nutzer-Export und edbak) als Vergleichsdateien für den
Kreislauftest importieren → exportieren → vergleichen. Die vorhandene
Luftrettungs-Beispieldatei dient als inhaltliche Vorlage.
**Zu klären im Phasenkonzept (F):** Einspielkanal für verschlüsselte
Patienten-/Nachbearbeitungsfelder · Region der fiktiven Einsätze ·
Straßenrealismus der Bodentracks (Routing-Grundlage oder vereinfacht) ·
GPX als Quell- oder Zwischenformat.
**Vorarbeit zu R19:** Beim Erzeugen und Einspielen der Ingest-Payloads wird
das reale Aufrufverhalten festgehalten — Teilstücke je Dienst, zeitlicher
Abstand, Spitzen. Diese Zahlen sind die Bemessungsgrundlage für den
P5-Entwurf; ohne sie wäre jede Fenstergröße geraten. Erhebung, keine
Schutzmaßnahme.
**Dauer-Regressionsfall zu R20:** Mindestens ein Einsatz des Datensatzes
trägt im Altersfeld einen Angriffswert (HTML-/Skriptmarker), damit die
Maskierung der Einsatztabellen dauerhaft mitgeprüft wird.

**Umsetzungsstand:** **umgesetzt und ausgeliefert** (Web 7.2.2, 7.2.3, 7.3.0 und 7.3.1 auf
`main`). Alle sieben Arbeitspakete B1–B7 erledigt. Der Datensatz umfasst 16 Diensttage und
87 Einsätze, erzeugt aus eingecheckten JSON-Quelldaten und über die regulären Wege
eingespielt — 526 Ingest-Anfragen ohne einen Fehlversuch. Werkzeuge, Quelldaten,
Referenz-Exporte, Vergleichswerkzeug, Konzept und Prüfdokument liegen unter
`tools/referenzdatensatz/`; das Demo-Konto samt Anlegen, Zurücksetzen, automatischem
30-Minuten-Reset und Anmelde-Mengenbremse ist ausgeliefert.
**Funde:** vierzehn (F-P1-A bis F-P1-N). Drei Anwendungsfehler sind behoben — der
CSV-Rückimport verlor sechs Felder zwischen Anzeige und Absenden (Web 7.2.2), das
Altersfeld führte Skript aus (über das Sofortpaket, Web 7.2.1), der CSV-Import legte
Einsätze nach Mitternacht 24 Stunden zu früh ab (Web 7.3.1). Sechs stehen als Backlog
Nr. 23, 24, 25, 27, 28, 29 offen; vier betrafen nur den Generator dieser Phase, einer war
ein Fehler im Vorgehen.
**P-12 (Produktivlauf)** — mit dem S1-Deploy **erledigt**: Fixture-Deploy, PHP-Fassung des
Hosters, `post_max_size`/`memory_limit`, die Laufzeit des Resets innerhalb einer
Web-Anfrage und das Anlegen des Demo-Kontos auf der Produktivinstallation sind geprüft
(Prüfdokument P1, Abschnitt 1.1 und Prüfliste 4.1).
**P-08 (Kreislauf CSV)** blieb mit sechs benannten Abweichungen offen bis S1 —
**mit S1 geschlossen** (0 unerklärte Abweichungen, Backlog Nr. 27 und 28 behoben).
**P1 hinterlässt keinen offenen Punkt.**
**Bereinigter Konflikt:** Der P1-Zweig setzte auf Web 7.2.0 auf und kannte das parallel
ausgelieferte Sofortpaket nicht; beide Linien fanden dieselbe XSS-Lücke. Beim
Zusammenführen hat die Fassung von `main` (Web 7.2.1) Vorrang bekommen, die P1-Versionen
sind um eine Stelle gerückt und die Backlog-Nummern dieser Phase ebenso (23–30 statt
22–28). Beides ist im Konzept P1 festgehalten.

### S1 — Sicherung und Import (Zwischenpaket, zwischen P1 und P2)
**Ziel:** Das Regressionsnetz aus P1 auf null bringen und die Sicherung vollständig
machen, **bevor** P2 und P3 sich darauf verlassen.
**Inhalt:** Papierkorb in NutzerInnen- und Admin-Sicherung nach R22 (Nr. 30) · Backlog
Nr. 27 (mehrzeilige Notizen verlieren ihre Umbrüche) und Nr. 28 (`final = 0` und ein
leeres `ende` werden überschrieben) · Nr. 24 und Nr. 29 (Ausnahmen in `Export-Format.md`
5.1 nachtragen) · Nr. 25 entscheiden · Rückbau des Papierkorb-Teils im Demo-Nachlauf ·
Referenz-edbak neu ziehen, Fixture neu erzeugen, beide Kreisläufe neu messen.
**Entschieden vor Konzepterstellung (F → E, K6):** Die Frist beim Einspielen — `deleted_at`
wird auf den **Einspielzeitpunkt** gesetzt, die Einträge bekommen volle 90 Tage
(E-S1-03; damit übernimmt der Rückweg den *Zustand* aus der Datei, nicht den Zeitpunkt —
dieselbe Logik wie bei `herkunft`) · **keine** Wahlmöglichkeit auf der Sicherungsseite,
stattdessen nennen die Umfangsangaben den Papierkorbanteil (E-S1-02) · `created_at` wird
**mitgeschrieben** (E-S1-06) · der neue Parser für mehrzeilige Notizen gilt für **alle**
Notiz-Ziele, auch die Excel-Profile (E-S1-11).
**Heikelste Stelle:** die D1-Regel. „Tag im Zielkonto im Papierkorb" (überspringen, wie
bisher) und „Tag in der Datei im Papierkorb" (gemeinsam als Papierkorbeintrag anlegen)
sehen im Code heute gleich aus. Dort verdient die Prüfung den größten Aufwand.
**Version:** Hauptversion — die Nutzlast der Sicherung steigt auf 7. ~~ältere Stände weisen
solche Dateien ab~~ — **falsch, siehe R22:** Der Sprung kennzeichnet, sperrt aber nicht.
Die Abnahmedatei zu R11 ist danach neu zu ziehen.

**Umsetzungsstand:** **umgesetzt und ausgeliefert** — **Web 8.0.0** auf `main`,
eine Fassung für die ganze Phase. **Keine Migration** (die Spalten
`deleted_at`/`deleted_with_day` liegen seit jeher, sie standen nur leer in der Datei).
Neun Arbeitspakete: die sieben geplanten C1–C7, dazu **C8** (gegnerische Nachlese) und
**C9** (die beiden daraus offenen Entscheidungen). Ergebnis in vier Schichten: die
Sicherung ist vollständig · die beiden stillen Verluste des CSV-Rückwegs sind weg und
`created_at` kommt zurück · eine kaputte Datei kostet ihre Zeile statt den ganzen Lauf ·
ein aktiver Einsatz an einem gelöschten Diensttag ist ausgeschlossen, beim Einspielen wie
in der Oberfläche.
**Funde:** acht (F-S1-A bis F-S1-H). Alle behoben oder entschieden; **kein offener Punkt
bleibt zurück**. Zwei davon (F-S1-D, F-S1-E) sind in dieser Phase **eingebaut** worden und
durch alle bestehenden Prüfungen gelaufen — daraus die neuen Prüfmittel nach R27.
**Zwischenfall:** Ein Prüfskript (`browser/demo_pruefen.mjs`) lief versehentlich gegen die
Referenzinstallation und hat dort das Referenzkonto verändert. Der Referenzstand wurde
vollständig neu aufgebaut und nachgezählt (87/5 Einsätze, 100/5 Ruhesegmente, 16/1
Diensttage, 55 861 Spurpunkte — identisch zum dokumentierten Stand); das Skript hat einen
in beide Richtungen geprüften Riegel bekommen. Nebenwirkung: Die Terminbindung „Referenz
vor dem 20.11.2026 neu ziehen" ist **gegenstandslos** — der Datensatz ist aus den
Quelldaten reproduzierbar, C7 hat die Referenz aus einem frischen Einspiellauf gezogen.
**Abgeschlossen:** Deploy freigegeben und ausgeführt; die Prüfliste des Prüfdokuments
(dreizehn Punkte) ist abgearbeitet, ebenso der Blick auf `update.php` wegen des Berichts
„Einsätze ohne Diensttag". Mit demselben Deploy ist auch der aus P1 offene Produktivlauf
**P-12** erledigt (Fixture-Deploy, PHP-Fassung des Hosters, Laufzeit des Demo-Resets,
Anlegen des Demo-Kontos auf der Produktivinstallation). **S1 hinterlässt keinen offenen
Punkt.**

### P2 — Terminologie
**Ziel:** Neutraler Wortlaut Land/Luft in Oberfläche und Dokumentation, bevor
die neue Oberfläche entsteht.
**Inhalt:** Wortliste mit Grenzfällen nach R3; Ersetzung in sichtbaren Texten,
Hilfetexten und Doku; README beschreibt beide Einsatzarten; Uhr-Kopplungstexte
plattformneutral (bisher „Connect-IQ-Einstellungen").
**Berichtigung der Basiszahl:** Die hier früher genannten „~290 Fundstellen —
flug 93, rth 65, hems 40, pilot 35, hubschrauber 21, luftrettung 16,
maschine 15, heli 8" zählten **Teilstrings**. Als eigenes Wort kommt `rth`
zweimal vor (die 65 waren „dorthin", „northeast", „earth"), `heli` **null**mal
(Logo-Dateiname und „naheliegend"), `maschine` zur Hälfte („maschinell",
„maschinenlesbar"). Echte Treffer rund 330 — davon etwa vier Fünftel
Kommentare, Bezeichner, Formatfelder oder ausdrückliche Historie, die nach
R5/R13 gar nicht angefasst werden. Der weitaus größte Teil der Arbeit war
seit Web 6.0.0–6.3.0 ohnehin erledigt; was blieb, saß **stärker in der
Dokumentation als in der Oberfläche**. Erhebung: `docs/Konzept-P2-Terminologie.md`,
Abschnitt 2.

**Umsetzungsstand:** **umgesetzt und geprüft, noch nicht ausgeliefert** —
**Web 8.0.1** (Korrektur), auf dem Arbeitszweig. Keine Migration, keine
Uhr-Auslieferung, kein Fable-Schritt. Sechs Arbeitspakete D1–D6: Prüfwerkzeug
und Wortliste festschreiben · Weboberfläche · README · Handbuch · Format- und
Technik-Doku · Abschluss. Konzept und Prüfdokument liegen unter
`docs/Konzept-P2-Terminologie.md` und `docs/Pruefdokument-P2-Terminologie.md`.
**Ergebnis:** In der Oberfläche waren es sieben Stellen — die Kopplungstexte,
der GPX-Hinweis, der Warntext des Excel-Rückimports, zwei Aufzählungen, ein
Platzhalter, die Schwachwortliste. In der Dokumentation stand deutlich mehr,
und zwar nicht nur falsch gewählt, sondern **sachlich falsch**: Das README war
vollständig alt („HEMS Einsatzdoku", „Dokumentation von Hubschraubereinsätzen"),
das Handbuch beschrieb eine Kopfleiste, eine Kilometerangabe und einen
Dateinamen, die es so nicht mehr gab, und die Excel-Spaltentabelle in
`Export-Format.md` nannte 29 Spalten mit 7 geschützten, wo der Code 31 mit 16
schreibt. Diese Sachfehler sind mit berichtigt worden (E-P2-05), weil es
dieselben Absätze waren.
**Neues Prüfmittel:** `tools/wortliste/` — siehe **R28**; vorher 53 Treffer
außerhalb der Ausnahmen, nachher 0, 0 ungenutzte Ausnahmen.
**Funde:** neun aus der Konzepterstellung (F-P2-A bis F-P2-I) und zehn aus der
Umsetzung (F-P2-J bis F-P2-S). Erledigt sind fünfzehn; **vier gehen in die
Nacharbeit** (R30). Zwei verdienen Erwähnung: **F-P2-R** — eine Ergänzung der
Schwachwortliste machte das Demo-Passwort unbrauchbar und legte damit den
Regressionsapparat der eigenen Phase still; gefunden hat es kein Lesen,
sondern ein Zeitüberlauf im Kreislauf. **F-P2-O** — zwei Wörter fehlten in der
Sperrliste („Basis", „Station"), ohne sie hätte das Werkzeug null gemeldet und
zwei echte Stellen übersehen.
**Offen:** die Nacharbeit nach R30, die Deploy-Freigabe nach K7 und die
Prüfliste des Prüfdokuments (neun Punkte; Punkt 4.1 braucht eine Uhr).

### P3 — Oberflächen-Redesign (mobil-first)
**Ziel:** Schlanke, funktionale, freundliche, moderne Oberfläche; voll
mobiltauglich. Der frühere „Mobile-Umbau" geht hierin auf.
**Inhalt:** Designreview der gesamten UI durch Fable 5 — ein Komplettumbau
ist ausdrücklich erlaubt, falls sinnvoll. Gestaltungsvorgaben: R8
(Farbpräsenz), Markenmaterial (Brand-Guidelines, Schriften Bricolage
Grotesque / Open Sans). Früher Schritt: alle im Code eingebetteten Grafiken
und Symbole in eigene Dateien auslagern (zentrale Bearbeitbarkeit). Später
Schritt: neues NEF-Logo und -Favicon einbinden; Nutzerwahl RTH / NEF /
zufälliger Wechsel; Admin legt Standard fest, Nutzerwahl übersteuert ihn.
Festgelegt im P3-Konzept (25.08.2026): „zufällig" würfelt **je Anmeldung**,
nicht je Seitenaufruf; Kopfleiste und Favicon wechseln gemeinsam; die
Anmeldeseite zeigt den Standard der Installation; der Begriff ist **Logo**
(nicht „Bildmarke"). **Die Uhr folgt derselben Wahl** (siehe P6): Das Logo
im Startbild der Uhr (`watch/resources/drawables/logo.png`, `StartView.mc`)
wechselt nach der Wahl aus dem Profil — die Uhr bezieht sie mit den
übrigen Einstellungen vom Server; das Launcher-Icon der Uhr ist im
Manifest fest und kann nicht je Nutzer wechseln.
**Aus R32:** Fußzeile mit Lizenz, Impressum, Datenschutz und Version auf
jeder Seite (auch vor der Anmeldung), dazu die beiden öffentlichen Seiten
und ihr Admin-Editor — eigenes Arbeitspaket mit gekennzeichneter
Funktionsänderung.
**Eingaben nötig:** neues NEF-Logo und -Favicon (Zuarbeit Philipp; für die
Umsetzung vorerst ein Platzhalter in denselben Maßen und Fassungen, die
echte Datei ersetzt ihn 1:1). Impressums- und Datenschutztext der eigenen
Installation für die Abnahme (Inhalt Betreibersache, R32).
**Umsetzung (30.08.2026): abgeschlossen.** Statuszeile in Abschnitt 6;
Einzelheiten in `docs/Konzept-P3-Oberflaeche.md` Abschnitt 11 und
`docs/Pruefdokument-P3-Oberflaeche.md`. **Weiterhin offen:** die beiden
Zuarbeiten (NEF-Logo/Favicon — Platzhalter liegt; Impressums- und
Datenschutztext — Eingabe über den Editor) und die **Sichtprüfung in WebKit
und Firefox** — die Umsetzungsumgebung hatte nur Chromium, und die 44
Symbole hängen am externen Dateiverweis (`Pruefdokument` Abschnitt 1.1;
Bedienweg dort in 5.0).

**Aus dem P3-Konzept (26.08.2026):** Umsetzung **ohne Fable-Schritt** — der
Symbolvorrat kommt aus Tabler Icons (MIT, vendoriert wie Leaflet) und liegt
dem Konzept bei; ein neuer Baustein löst eine Freigabe aus, keinen
Modellwechsel. Gekennzeichnete Funktionsänderungen in P3: R32-Seiten und
-Editor, Logo-Wahl je Profil, Ortsfeld mit Positions- und Kartenwahl,
Phasen sortieren sofort, Kachelsätze der Zeitraumansicht, Standort- und
Zielpins auf allen Karten, **Kontoseite als Drehscheibe der Administration**
(E-P3-41: NutzerInnen mit Suche, Filtern und Seitenwechsel; Sicherungen je
Konto auf der Kontoseite; Sicherungsregeln mit Aufbewahrung je Konto und
Admin-Erinnerung). Neue Dokumente: `docs/Design.md` (Gestaltungsrichtlinie,
ersetzt `Branding.md`), `docs/Lizenzen.md`, Abschnitt „Pflegepflichten" in
`CLAUDE.md`. Neue Prüfmittel: Vollständigkeitsprüfung des Stylesheets und
`tools/screenshots/` (acht Breiten); der Stilvergleich ist während P3 aus
und wird am Ende neu geeicht.
**Pflichtlektüre für das P3-Konzept:** die Vormerkliste aus Konzept P0,
Abschnitt 10.5 (JS-erzeugte Oberfläche, Tabellen-Überlauf, Filterspalte,
Kopfhöhe dreifach verdrahtet) und der E-A6-02-Vorbehalt zur
Meldungs-Tonart. Dazu Backlog Nr. 20 (Hex-Literale auf Token, sobald die
Palette angefasst wird).
**Fable-Schritt:** das Designkonzept (R14); alles Übrige Standard Opus (K2).
**Prüfwerkzeuge:** Stilvergleich (`tools/stilvergleich/`) bei jeder
CSS-Arbeit — bei beabsichtigten Änderungen als Soll-Ist-Liste gegen die
geplanten Änderungen. Messbreiten für den Mobilteil unter 500 px erweitern
(bis ~360 px). Dazu **`tools/wortliste/` nach R28**: Das Redesign erzeugt neue
Texte, und für neue Texte gilt die Wortliste aus P2 genauso wie für alte.
**Aus P2 mitzunehmen:** Die Oberfläche ist sprachlich neutral übergeben —
diesen Stand nicht verlieren. Zwei Muster aus P2, die im Redesign leicht
zurückkehren: ein einzelnes Beispiel, das immer aus der Luftrettung stammt
(E-P2-14 verlangt beide Arten), und ein plattformgebundener Bedienweg im Web,
wo ein gerätefreier hingehört (E-P2-02). Der Luftrettungs-Tab behält
ausdrücklich seine Flugterminologie (E-P2-04).

### S2 — Mengen, Spurspeicherung und Sicherung (Zwischenpaket, zwischen P3 und P4)
**Ziel:** Die Anwendung trägt 5 000 Einsätze je Konto und 500 Konten je
Installation — auf geteiltem Webspace und fünf Jahre alten Endgeräten —,
ohne ihre Zusagen zu verlieren: E2E unverändert, GPS-Spuren vollständig,
Sicherung und Wiederherstellung durch die Nutzerin selbst.
**Konzept:** `docs/Konzept-S2-Mengen-Spuren-Sicherung.md` (Entscheidungen
E-S2-01 bis E-S2-24 mit Messwerten; Kernpunkte in R34). **Kein
Fable-Schritt** — die Formatentscheidungen sind im Konzept getroffen, der
R17-Review in P6 prüft sie nach.
**Inhalt in elf Arbeitspaketen:**
AP0 Messstand samt Ausgangsmessung (`tools/messstand/`, dauerhaft, R35) ·
AP1 Spurbibliothek `spur_lib.php` und Blob-Format SPUR1 (32-Bit-Differenzen
spaltenweise + zlib; Rundlaufprüfung vor dem Löschen der Zeilen; alle Leser
und Löschwege über die eine Bibliothek) ·
AP2 Job-Einstieg `jobs.php` mit drei Auslösern (CLI-Cron, URL mit Token,
anfragegetrieben), Anzeige auf der Wartungsseite, Wartung ohne Vollscan ·
AP3 Verdichtung (nach `final` + 14 Tagen; ohne `final` nach 60 Tagen Ruhe)
und Ausdünnung (sechs Monate nach Einsatzende; Douglas-Peucker 3D 2 m/3 m,
Phasenpunkte bleiben; Original danach weg); `ingest.php` beantwortet
`next_seq` aus Blob-Kopf plus Nachzügler-Zeilen — Nachlieferungen werden bis
zur Ausdünnung eingearbeitet, danach verworfen und quittiert ·
AP4 GPX-Abruf je Einsatz und Ruhesegment mit Kennzeichnung
Original/ausgedünnt (Backlog Nr. 3) ·
AP5 Sicherungscontainer Fassung 4 (ZIP: Manifest, Kern, Spurpakete; AAD
bindet Kennung und Teilnummer; Sichern vollständig im Browser mit
blockweisem Spurabruf, Wiederherstellen Kern + Pakete ≤ 2 MB je POST;
Altformat 7.x bleibt lesbar, R11; neue Fassung-4-Referenz, alte Referenz
bleibt als Altformat-Abnahme; Kreisläufe erweitert) ·
AP6 Admin-Sicherungen auf die Paketbibliothek, „Alle sichern" in Schüben,
Aufbewahrung 2 je Konto (manuell mehr), Speichergrenze mit Warnschwellen
und Mail über `smtp_send()` ·
AP7 Transportziele FTP/FTPS (`ext/ftp`) und SFTP (phpseclib, vendoriert;
Lizenzen.md), Admin-Pflege mit Verbindungsprüfung, Zugangsdaten unter dem
Serverschlüssel ·
AP8 Komplettbackup der Installation: SQL-Dump in Häppchen, versiegelt mit
Serverschlüssel aus `config.php`, Direktdownload und Zeitplan-Push aufs
Transportziel, App-Rückweg „Installation wiederherstellen" bei leerer
Datenbank, Runbook ·
AP9 Suche: Schlüssel einmal importieren, stapelweise entschlüsseln ·
AP10 Abschluss (Doku-Nachträge in Technik, Backup-Format, JSON-Vertrag
[Nachtrag ohne Vertragsänderung], Handbuch, Lizenzen, CLAUDE.md;
Backlog-Pflege; Prüfdokument).
**Zielzahlen der Abnahme** (5 000-Einsätze-Konto, CPU-Drossel 6×): Suche
≤ 5 s · Tagesansicht ≤ 3 s · Sicherung erstellen ≤ 5 min ·
Wiederherstellung ≤ 15 min · Sicherungsdatei ≤ 25 MB · Spuren ≤ 3 MB je
1 000 Einsätze nach Ausdünnung · kein Browserschritt über 10 MB JSON, kein
POST über 2 MB, keine Anfrage über 30 s, PHP-Spitze ≤ 64 MB.
**Regressionen:** Kreisläufe nach R24 (edbak gegen die Fassung-4-Referenz
plus Altformat-Einspiellauf), Prüfmittel nach R27, Wortliste nach R28 (neue
sichtbare Texte), Messstand nach R35.
**Backlog:** Nr. 2 und Nr. 3 werden hier geschlossen (R34); neu hinein:
WebDAV-Adapter, Konto-Schlüsselpaar für versiegelte Serversicherungen.
**Zuarbeiten:** FTP-/FTPS-/SFTP-Testziel, SMTP-Bestätigung (Abschnitt 7).

---

**Umgesetzt und ausgeliefert (01.09.2026), Web 10.0.0 bis 12.2.0.** Alle elf
Pakete gebaut. Vier Migrationen; `update.php` ist nach dem Deploy aufgerufen
worden — ohne sie scheitert jeder Spurzugriff, weil `track_blobs` fehlt.

**Die Zielzahlen sind gehalten, zwei davon deutlich:**

| | Ziel | gemessen |
|---|---|---|
| Spuren je 1 000 Einsätze | ≤ 3 MB | **1,10 MB** |
| Tagesansicht bis zur gezeichneten Spur | ≤ 3 s | **1,17 s** |
| Suche bis zur ersten Trefferanzeige | ≤ 5 s | **3,81 s** |
| Sicherung erstellen | ≤ 5 min | **42,2 s** |
| PHP-Speicherspitze je Häppchen | ≤ 64 MB | **26 MB** |

Browserwerte unter CPU-Drossel 6×, am 5 000-Einsätze-Konto.

**Ein Zahlenwert der Ausgangsmessung war falsch, und das ist der lehrreichste
Fund der Phase.** Nicht die Anwendung war zu langsam, sondern das Prüfmittel
log: `browserprobe.mjs` wartete vier Sekunden auf einen Entsperrdialog, der
bei entsperrter Sitzung nie kommt — **mitten im gemessenen Abschnitt**.
Gemessen wurde damit `max(4 s, tatsächliche Dauer)`. Die Ausgangsmessung nannte
„Tagesansicht 4,81 s" und „Suche 4,53 s"; beide liegen dicht über vier
Sekunden, weil beide der Zeitschranke entsprachen. Der Befund „Tagesansicht
62 % über dem Ziel", der diese Phase mit begründet hat, **löst sich
vollständig auf**. Insgesamt haben sich **fünf Prüfmittel** als etwas anderes
messend erwiesen, als draufstand; das steht in Kapitel 6.1 des Prüfdokuments,
weil es die Aussagekraft aller übrigen Zahlen betrifft.

**Zehn Fehler gefunden und behoben** (F-S2-A bis F-S2-J im Konzept,
Abschnitt 8). Die drei mit den größten Folgen:

- **F-S2-E** — Eine Sicherungsdatei mit Nutzlast 8 *und* Punktlisten verlor
  **alle Spuren, ohne ein Wort**. Der Messstand schrieb genau solche Dateien.
- **F-S2-H** — Ein abgebrochenes Häppchen der Komplettsicherung hätte beim
  nächsten Lauf ein zweites `DROP TABLE` derselben Tabelle in die Datei
  geschrieben; beim Einspielen wäre weggeworfen worden, was das erste
  Häppchen eingefügt hat.
- **F-S2-J** — Die Schranke „leere Datenbank" der Wiederherstellung galt vor
  *jedem* Durchgang. Leer ist die Datenbank aber nur vor dem ersten: Abbruch
  bei 91 %.

**Eine Abweichung vom Beschluss, benannt und begründet.** E-S2-20 sieht vor,
dass der Rückweg „danach einen Migrationslauf" ausführt. `wiederherstellen.php`
tut das **nicht**: `update.php` ist seit M6-01 zweistufig, weil Migrationen
Spalten löschen können, und eine Seite ohne Anmeldung, die sie nebenbei
mitlaufen ließe, nähme genau diese Sicherung heraus. Die Seite vergleicht
stattdessen die Web-Fassung und schickt zur Wartung; das Runbook führt den
Schritt als eigenen auf. Offen als **Backlog Nr. 54**.

**Was offen bleibt** (Einzelheiten im Prüfdokument, Kapitel 1):

- **Der Wiederanlauf ist nie an einem echten Ausfall geprobt worden.** Der
  ganze Zyklus ist maschinell gefahren — Rundlauf 34 von 34 Tabellen
  zeichengleich und prüfsummengleich —, aber auf einer Maschine, auf der
  Datei, Schlüssel und Datenbank griffbereit lagen. Der Ernstfall sieht
  anders aus. **Punkt 11 der Prüfliste**, und der wichtigste des Dokuments.
- **Kein echtes Sicherungsziel im Internet.** Aus dem Behälter gehen nur
  Verbindungen auf Port 443 hinaus (mit `github.com:22` gegengeprüft). Die
  Abnahme ist ein Klick auf „Verbindung prüfen" — Zuarbeit, Abschnitt 7.
- **Die Warnmail** bei erreichter Speichergrenze: ohne eingerichtetes SMTP
  ist der Hinweisweg geprüft, der Versandweg nicht.
- **`wiederherstellen.php` ist nicht angeklickt worden** — über `curl` gegen
  einen echten PHP-Server gefahren und fotografiert, aber ein Klickweg
  bräuchte eine leere Datenbank.
- Eine volle Platte, ein echter Absturz mitten in der Anfrage, eine andere
  Datenbank als MariaDB 10.11.

**Aus der Phase heraus entstanden:** die Backlog-Punkte **Nr. 46 bis 55**,
zwei dauerhafte Prüfmittel (`tools/versandprobe/`, `tools/komplettprobe/`)
und eine vendorierte Abhängigkeit (phpseclib 3.0.57, MIT, in
`docs/Lizenzen.md` eingetragen).

**Ein Nachtrag zur Zusammenführung.** S2 und die Uhr-Auslieferung hatten
nebeneinander Backlog-Nummern ab 46 vergeben; beim Zusammenführen meldete
`docs/Backlog.md` keinen Konflikt, weil die Einträge an verschiedenen Stellen
standen, und trug danach vier Nummern doppelt. Aufgelöst mit derselben PR wie
dieser Eintrag. **Für künftige Zusammenführungen** steht die Gegenprobe im
Kopf von `docs/Backlog.md`; sie gehört vor jeden Merge, der Backlog-Punkte
mitbringt.

### S5 — Kopplung umgekehrt (Zwischenpaket, zwischen S2 und S3)
**Ziel:** Ein Gerät koppelt sich, ohne dass jemand einen Code auf der Uhr
eintippt oder eine Server-Adresse einträgt: Das Gerät zeigt einen Code, das
Web nimmt ihn entgegen, das Gerät bestätigt das Konto. Ein Protokoll für die
Garmin-Uhr (hier) und die Android-App (S4, Block B).
**Konzept:** eigenes Dokument nach K1, Prüfdokument nach K9 — **erarbeitet
erst nach Abschluss von S2 und R42-Kleinstpaket** (R49). Die Beschlüsse
E-R49-1 bis E-R49-8 sind gefallen; das Konzept legt fest: Vertragsabschnitt
1a neu (`start`/`status`/`bestaetigen`, Antwortfelder, Fehlercodes, Zustände
der Sitzung), Sitzungstabelle und Migration, Zahlen des Ratenschutzes und
der Sitzungsobergrenze, Abfragetakt der Uhr, Wortlaut der Uhr-Anzeigen und
der Geräteseite (Wortliste R28), Paketschnitt mit Abnahmekriterien. **Kein
Fable-Schritt.**
**Inhalt in vier Blöcken (Schnitt im Konzept):**
**A — Server:** Sitzungstabelle statt `pair_codes` (Migration) · `pair.php`
mit drei Anliegen, `status`/`bestaetigen` mit Kopfzeilen-Ausweis ·
`devices`-Anlage erst bei `bestaetigen` · Ratenschutz je Konto/IP, `start`
je IP, Sitzungsobergrenze · Aufräumen über den S2-Job-Einstieg ·
Kopplungsmail wie heute · Bedrohungsmodell-Nachtrag (E-R49-5).
**B — Web:** Geräteseite in `einstellungen.php`: Feld „Code vom Gerät",
Bestätigungsseite mit Art und Modell (Backlog Nr. 59), Meldungen für
Fristablauf, Gerätelimit und zu viele Versuche; „Kopplungscode erzeugen"
entfällt; manuelle Anlage bleibt.
**C — Uhr** (`watch/`): Sync-Seite → START halten → `start` · Code-Anzeige
in Gruppen · Abfrage bis Frist oder Abbruch mit BACK ·
Rückbestätigungsdialog mit maskierter E-Mail, nichts davon gespeichert ·
Speichern und Haken wie bisher · Vorgabewert `serverUrl` =
`nadoku.gen-em.org` samt Kommentar in `properties.xml`/`settings.xml`
(E-R49-8) · Fehlertexte nach dem Muster von Uhr 1.7.0 (zwei Zeilen: woran es
liegt, was hilft).
**D — Doku und Abschluss:** JSON-Vertrag 1a neu (1b bleibt) · Handbuch 12
und 2.x (Tastenwege je Uhr) · Technik (`pair.php`, Tabellen, Ratenschutz,
Bedrohungsmodell) · `Geraete-Eingabe.md` · CHANGELOG Web und Uhr ·
Backlog-Pflege · Prüfdokument.
**Prüfung:** Uhr-Prüfstand `tools/uhr-pruefstand/` über alle Geräte mit
strenger Typprüfung · Simulator-Rundlauf gegen einen lokalen Server (`start`
→ Eingabe im Web → `status` → `bestaetigen`), dazu Nein-Fall, Fristablauf,
Gerätelimit · Ratenschutz-Proben (Konto, IP, Sitzungsobergrenze) ·
`tools/screenshots/` für die Geräteseite · Wortliste R28 für die neuen
Texte. Gerätetest: eine Kopplung mit der Uhr in der Hand — das ist zugleich
P2-Prüfpunkt 4.1, der mit S5 auf den neuen Ablauf umgeschrieben wird.
**Nicht Umfang:** die Android-Seite (S4 Block B setzt 1a um) · Auswertung der
Gerätestatistik (P5, R38) · Store-Verteilung.
**Backlog:** Nr. 59 (Serverseite der Gerätekennung, R42-Kleinstpaket) ist
Voraussetzung; kein bestehender Punkt wird geschlossen.
**Zuarbeiten:** DNS und TLS für `nadoku.gen-em.org` (Abschnitt 7), Freigabe
des Konzepts.

### S3 — Oberflächen-Nacharbeit und vertikaler Rhythmus (Zwischenpaket, nach S2)
**Ziel:** Die 19 Rückmeldungen vom 31.08.2026 (`ToDo_Layout.pdf`) abarbeiten —
**zentral über Klassen und Bausteine**, nicht als Einzelkorrekturen an
Seiten (R43). Der größte Teil der Liste ist nicht eine Sammlung von
Schönheitsfehlern, sondern die sichtbare Folge einer fehlenden Regel; sie
wird in diesem Paket geschrieben.
**Konzept:** eigenes Dokument nach K1, Prüfdokument nach K9. **Kein
Fable-Schritt.** Prüfmittel: Stilvergleich (Soll-Ist gegen die geplanten
Änderungen), `tools/screenshots/` (acht Breiten), Vollständigkeitsprüfung
des Stylesheets, Wortliste (R28). Als **Funktionsänderungen** gekennzeichnet
sind: Filterausblendung in der Suche, Autosuche im Ortsfeld, Sperrung des
Demo-Kontos, Höhenanzeige nur bei Luftrettung.

**A — Vertikaler Rhythmus (der Kern, R43).** Regelwerk in `docs/Design.md`:
welche Stufe der bestehenden Skala zwischen Überschrift und Inhalt, Inhalt
und nächstem Abschnitt, Formular und Fuß, Karte und Karte gilt. Umsetzung an
den Bausteinen; der freistehende Knopf „Profil speichern" in
`einstellungen.php` bekommt den vorhandenen Formularfuß-Baustein statt einer
eigenen Regel. Danach eine Durchsicht der übrigen Formularseiten auf
dasselbe Muster (freistehender Absendeknopf ohne Fuß).

**B — NutzerInnen-Liste (`admin_users.php`).** Die Spalten Rolle, Seit,
Zuletzt angemeldet, Geräte und Sicherung werden **zentriert** (Überschrift
und Inhalt gemeinsam — heute stehen sie links und die Überschrift steht
über nichts); die Spalte Konto bleibt linksbündig. Der **Zeilentrenner
reicht nicht über die „Öffnen"-Spalte** und wird auf die volle Tabellenbreite
gezogen. Beides gehört an die Tabellenklassen, nicht an `:nth-child`
(Grundregel aus `CLAUDE.md` 5).

**C — Sammelleiste (`ui_speichern_leiste`, `.speichern`).** Reihenfolge
umkehren: **Knopf rechts, Zählung („XX ausgewählt") links daneben** — heute
steht der Knopf im Markup zuerst und damit links. **Die Form ist entschieden
(E-R43-1): Die Leiste übernimmt die Kartenform** — Radius und Breite wie die
Karte darüber, keine begründete Abweichung. Der negative Randausbruch
(`margin: … calc(var(--abstand-3) * -1)`) entfällt; er war die Ursache
dafür, dass die Leiste „eckig und breiter" wirkte. Sticky Sitz und
Trennlinie nach oben bleiben — sie tragen die Funktion, nicht die Form.

**D — Demo-Konto auf der Kontoseite (`admin_user.php`).** Für das Demo-Konto
entfallen Sicherungsanzeige und Sicherungsaktionen (es wird zentral über den
Reiter „Demo-Konto" angelegt und zurückgesetzt, gesichert wird es nicht);
die Bearbeitung wird **gesperrt und sichtbar ausgegraut**, die Seite bleibt
aufrufbar. Anzeigename des Kontos: **„Demo NutzerIn"**. Berührt R25 (das
Demo-Konto ist dauerhafter Bestandteil) — die Sperre ist dort mitzuführen.

**E — Formularbausteine.** Die Logo-Wahl wird von umrandeten Einzelzeilen
auf eine **schlichte Liste** umgestellt (kompakter; betrifft den
Radiolisten-Baustein, nicht nur diese eine Stelle). **Platzhalter tragen
ausschließlich Phantasienamen** — „z. B. Standort Kempten" bevorzugt einen
realen Ort; die Regel gilt für **alle** Formulare und gehört als Pflegeregel
in `docs/Design.md`. **Ortsfeld sucht beim Tippen** (entprellt), ohne Klick
auf die Lupe — bei Standort und Zielklinik; die Lupe bleibt als
Auslöser bestehen.

**F — Navigation.** Die Menüpunkte der Seitenleiste sind durchgehend fett;
künftig **normal, fett nur der ausgewählte Punkt**. Die Überschrift
**„Diensttage" wirkt verloren** und wird größer gesetzt — **linksbündig,
ohne horizontale Zentrierung** (E-R43-2, entschieden). Umgesetzt am
geteilten Baustein `.leiste-kopfzeile`: eine Stufe höher in der
Schriftskala, Farbe von `--gedaempft` auf `--asphalt`. Der Baustein trägt
**vier** Zeilen (Diensttage, Einstellungen, Administration, Filter der
Suche); alle vier ziehen mit, und alle vier stehen als beabsichtigte
Abweichungen in der Soll-Ist-Liste des Stilvergleichs. Ob Versalien und
Sperrung bei der größeren Stufe bleiben, entscheidet die Umsetzung am
Bild.

**G — Einsatzansicht (`einsatz.php`).** Die **Höhe erscheint nur bei
Luftrettung**, dann aber beschriftet („Höhe 1917 m" statt „1917 m").
**Schlosssymbole vertikal zum Text zentrieren**; am Block „Einsatz" fehlt die
blaue Plakette „verschlüsselt", bei „Name/Geboren" fehlt das Schlosssymbol —
beides ergänzen. Der blaue Hinweisbalken „Geschützte Angaben sind entsperrt,
bis du dich abmeldest" entfällt (steht im Handbuch).

**H — Karte (`geo.js`, `.geo-*`).** **Keine Beschriftung** an Standort und
Zielklinik — nur das Symbol; der Kasten wird kleiner, vor allem durch
**weniger Weißraum zwischen Symbol und Rand** (das Symbol selbst bleibt
weitgehend gleich groß). Auf `index.php` erscheint **nur der Standort des
Rettungsmittels**, in derselben Größe wie in der Einsatzansicht, ohne
Beschriftung; **Zielkliniken werden dort nicht angezeigt**. Das orange
Einsatzort-Symbol verliert seine **weiße Umrandung** und wird etwas
verkleinert. Dazu der **Positionsfehler der Marker** (Ursache in R43
benannt): `iconSize` ausdrücklich setzen, damit der Anker auf der Kastenmitte
liegt und ein künftiges Beiwerk den Versatz nicht erneut einträgt.

**I — Kacheln und Tabellen.** Kennzahl-Kacheln der Zeitraumübersicht auf
**größeren Kacheln vertikal zentrieren**. In der Tagesübersicht (`index.php`)
muss die **Dauer auch in schmaler Spalte einzeilig** stehen („1h 06min"
bricht heute um); **Nr., Beginn und Alter werden in der Zelle zentriert**;
die Spaltenüberschrift „Sekundär Transport" wird zu **„Sekundär-" /
„transport"** (Trennstrich mit Umbruch).

**J — Suche (`suche.php`).** Filter erscheinen **nur, wenn im Bestand etwas
dahintersteht** — enthält kein Datensatz einen Praktikanten, entfällt das
Feld. Berührt den Feldkatalog-Gedanken: die Regel gehört an die
Filtererzeugung, nicht als Sonderfall je Rolle.

**K — Logogrößen Luft und Boden (`gen-em_logo_nef*.svg`, `ui.php`,
`.kopf-marke img`, `.anmeldung-logo`).** Beobachtet am 31.08.2026, **im
Browser nachgemessen** (Chromium, `svg.getBBox()` über die Wurzel — eine
erste, grobe Zählung der Koordinaten im Dateitext hatte das Gegenteil
nahegelegt und war falsch). Zwei Ursachen, die sich addieren:

**(1) Das Bodenlogo ist gar nicht quadratisch — es sitzt nur auf einer
quadratischen Fläche.** `viewBox="0 0 420 420"`, die tatsächliche Zeichnung
misst aber **420 × 335** und beginnt bei `y = 42,5`: **oben und unten je
42,5 Einheiten leer**, links und rechts null. Das Motiv hat also das
Verhältnis **1,254 : 1** und ist symmetrisch auf ein Quadrat gepolstert —
ein Artefakt des Exports, keine Gestaltungsentscheidung. Das Luftlogo ist
dagegen randlos: `viewBox="0 0 400,16 249,81"`, Zeichnung exakt so hoch wie
der Rahmen, Verhältnis **1,602 : 1**. Farb- und Weiß-Fassungen sind darin
jeweils identisch.

**(2) Skaliert wird allein über die Höhe** (`.kopf-marke img{height:34px;
width:auto}`, `.anmeldung-logo{height:56px;width:auto}`). Zusammen mit (1)
heißt das in der Kopfleiste: Das Luftlogo füllt seine 34 px Höhe ganz aus
und wird **54,5 × 34 px**; das Bodenlogo bekommt eine 34 × 34-Schachtel, in
der das Motiv aber nur **34 × 27,1 px** groß ist — es erscheint also
**schmaler und niedriger zugleich**. Sichtbare Fläche: **1 853 gegen
921 px², das Luftlogo bedeckt rund das Doppelte.** Auf der Anmeldeseite
dieselbe Relation.

**Behebung — zuerst am Bild, dann erst an der Regel.** Die Zeichnung wird
**nicht umgestaltet und nicht umgezeichnet**: Es wird nur der leere Rand
aus dem Rahmen genommen (`viewBox="0 42.5 420 335"` bzw. ein enger
Neuexport), für Farb- und Weiß-Fassung gleichermaßen. Danach füllen beide
Logos ihren Rahmen, und die vorhandene Höhenregel liefert von selbst ein
weitgehend ausgewogenes Bild (42,6 × 34 gegen 54,5 × 34 px; die
verbleibende Differenz ist der ehrliche Unterschied zweier Motive, kein
Fehler). **Ob danach noch eine Feinkorrektur nötig ist** — etwa eine
Größenangabe je Logoform statt einer gemeinsamen Höhe —, wird **am
korrigierten Bild entschieden und nicht vorab festgelegt**; nur wenn sie
kommt, wird sie als Regel in `docs/Design.md` hergeleitet, mit der
Kopfleistenhöhe von 56 px als Schranke.

**Zwei Nebenpunkte an derselben Stelle:** `ui.php` schreibt die Maße fest
als `width="54" height="34"` ins Bild-Tag — das ist das Verhältnis des
Luftlogos; für das andere reserviert der Browser den falschen Kasten, und
beim Laden springt das Layout. Die Maße gehören **je Logo** ausgegeben.
Und das Luftlogo trägt rund **156 Einheiten Zeichnung rechts außerhalb
seines Rahmens**, die beim Anzeigen weggeschnitten werden — sichtbar
folgenlos, aber eine Falle für jeden, der den Rahmen später anfasst; beim
Aufräumen der Dateien mit erledigen.

**Geltung:** alle vier Darstellungsorte (Kopfleiste sowie Anmeldung,
Passwortvergabe und Zurücksetzen über `.anmeldung-logo`). Die **Favicons**
(`favicon_helicopter.png`, `favicon_nef.png`) sind auf denselben Rand hin
zu prüfen — sie stammen aus denselben Vorlagen. Nachgemessen wird mit
`tools/screenshots/`, für beide Logowahlen.

**Der Beschnitt greift bis auf die Uhr durch — nachgemessen am 31.08.2026.**
Seit R47 werden die Uhr-Bildmarken aus **denselben** SVG gerastert
(`tools/uhr-bilder/erzeugen.sh`; das NEF steht dort auf 78 % der Kachelbreite,
weil sein Motiv 1,254 : 1 misst und der Hubschrauber 1,602 : 1 — der Faktor
ist genau das Verhältnis der beiden Verhältnisse und **bleibt nach dem
Beschnitt richtig**). Die erzeugten PNG ändern sich trotzdem: Probeweise mit
`viewBox="0 42.5 420 335"` gerastert, wird das Motiv **1 bis 2 Pixel kürzer**
und sitzt 0 bis 2 Pixel tiefer (gemessen über alle vier Kachelstufen 60, 73,
101, 118). Ursache ist die halbe Einheit in der Polsterung: Heute wird sie
zweimal gerundet — einmal beim Rastern des Quadrats, einmal beim Zentrieren
in die Kachel —, nach dem Beschnitt gar nicht mehr. Das Ergebnis ist also
*genauer*, aber eben anders.
**Folge für die Reihenfolge:** Wer die SVG beschneidet, **muss
`tools/uhr-bilder/erzeugen.sh` neu laufen lassen** — sonst passen die
eingecheckten Kacheln nicht mehr zu ihrer Quelle, und das fällt niemandem
auf. Die geänderten Kacheln brauchen einen Uhr-Build; sie reisen deshalb mit
der nächsten Uhr-Auslieferung mit, nicht mit S3. In S3 wird der Beschnitt
gemacht und der Generator gefahren; der Web-Teil wirkt sofort, die Uhr-Dateien
liegen bis dahin im Repositorium.
> **Berichtigt:** Hier stand „P6-Uhr-Auslieferung (R29)". Das ist seit R48
> (Fassung 12) überholt — **P6 trägt keine Uhr-Auslieferung mehr**; die
> nächste ist die von **S5** (E-S3-04).

**Nebenbefund — entschieden, kein Arbeitspunkt (E-R43-3):** Die Kacheln der
Zeitraumübersicht tragen „Flugkilometer gesamt" und „Ø Winden-Cycles /
Flugtag". Sie **bleiben unverändert**: Die Kacheln stehen in der
Luftrettungsansicht, und dort ist Flugsprache ausdrücklich zulässig
(E-P2-04). Meldet die Wortliste den Fall, bekommt er dort eine **Ausnahme
mit dieser Begründung** — keine Umformulierung (R28: ein Luftbegriff, der
bleiben soll, braucht einen Eintrag, kein Ausblenden).

**Umsetzung (01.–02.09.2026): abgeschlossen.** Web 12.2.2 bis 12.4.2, zwölf
Pakete AP1–AP12, keine Migration. Konzept und Prüfstände:
`docs/Konzept-S3-Oberflaechen-Nacharbeit.md`; Prüfliste:
`docs/Pruefdokument-S3-Oberflaechen-Nacharbeit.md`. Statuszeile in Abschnitt 6.

**Der Kern hat sich bestätigt, und schärfer als hier formuliert.** Der Text
oben nimmt an, ein Teil der Liste sei die Folge einer fehlenden Regel.
Nachgemessen: Von **269** Abstandsdeklarationen trägt **keine** einen
Rohwert — die Skala wurde nicht „überwiegend", sondern **ausnahmslos**
eingehalten. Von 74 Zwischenraum-Deklarationen waren **61 schon richtig**;
13 Regeln waren einzustellen. Der Befund in einer Zeile: `.karte` und
`.feld` trugen beide 16 px, Trennung war also genauso groß wie Bindung.

**Vier Punkte dieses Abschnitts trafen so nicht zu.** Sie sind nachgemessen
worden, statt sie erneut zu „beheben":

- **B, Zeilentrenner:** Er reicht **doch** über die volle Tabellenbreite —
  gemessen bei 1440 px von x = 0 bis 2227 von 2228 Bildpunkten. Der Punkt
  hat sich zwischen Web 9.14.1 und 12.2.4 von selbst erledigt. Die
  **Zentrierung** der fünf Spalten war der reale Teil und ist gemacht.
- **I, „Sekundär Transport":** Trägt seit F-N1-G das **weiche Trennzeichen**
  (`&shy;`), der Browser setzt den Bindestrich selbst. Nichts zu tun. Der
  **Dauer-Umbruch** dagegen war echt — und die Ursache eine andere als
  vermutet: `missiontable.js` setzt `zeit-spalte` längst, aber die
  Tagesübersicht baut ihre Zeilen in einem **zweiten, älteren Aufbau**
  zusammen, und dort fehlte die Klasse (Backlog Nr. 57).
- **G, Höhenanzeige:** Die Bedingung `kind === 'air'` stand bereits im Code;
  gefehlt hat nur das **Wort** vor der Zahl.
- **K, Favicons:** Sie tragen den leeren Rand **nicht** — beide sind korrekt
  auf ihre quadratische Fläche gesetzt. Neu abgeleitet wurden sie trotzdem.

**Die drei offenen Bildentscheide sind gefallen.** Versalien und Sperrung
der Leistenüberschrift **entfallen** (bei 15 px liest sich der gesperrte
Versalsatz als Etikett und konkurriert mit dem Eintrag darunter; bei 13 px
trug die Sperrung noch — sie war der Ersatz für die Größe, die jetzt da
ist). Eine **Feinkorrektur der Logogrößen ist nicht nötig**: 54,5 × 34 gegen
42,6 × 34 px, Flächenverhältnis von 2,01 auf **1,28**; die Höhen sind gleich,
und das ist es, was das Auge in einer Zeile vergleicht.

**Der Markerversatz ist gemessen behoben:** **51,7 px** vorher, **0,0 px**
nachher, über sechs Zoomstufen — mit Nachstellung des alten Zustands als
Gegenprobe.

**Fünf Funde über die Liste hinaus** (F-S3-A bis F-S3-E). Zwei davon kamen
aus der zweiten Rückmeldungsrunde am 01.09.2026, beide auf
`tag_spuren.php`: Die Seite lief **ohne Seitengerüst** — als einzige
angemeldete Seite ohne Leiste und ohne Innenabstand, linke Kante bei 0 statt
12 px —, und `ui_meldung_markup()` ergab bei einem Ton, den es nicht gibt,
einen **ungestalteten Kasten**. **Drei der fünf konnte kein Prüfmittel
finden**, und das ist der eigentliche Ertrag dieser Phase: Der Bilderlauf
misst *Überlauf*, und eine randlose Seite läuft nicht über; die
Vollständigkeitsprüfung sucht Klassen als *Literale*, und `'meldung-' . $ton`
entsteht zur Laufzeit; der Favicon-Generator schrieb bei einem Fehlschlag
klaglos eine Datei. Backlog **Nr. 57 und 58** halten die beiden fest, die
über S3 hinausreichen.

**Was offen bleibt** (Einzelheiten im Prüfdokument, Kapitel 1): Der
Bilderlauf ist nur für **eine** Logo-Wahl gefahren; die neu gerasterten
Uhr-Kacheln sind **nie übersetzt** worden (kein Garmin-SDK — gehört in die
S5-Auslieferung, E-S3-04); die Autosuche ist gegen einen **abgefangenen**
Photon geprüft; Bedienzustände fehlen durchgehend. Die Deploy-Freigabe steht
aus — ein Push auf `main` deployt sofort.

### S4 — Handy- und Uhr-Client (Android/Wear OS), Schneidewerkzeug und GPX-Import (Zwischenpaket, nach S3)
**Ziel:** NotärztInnen ohne Garmin-Uhr zeichnen ihre Spur mit dem Handy auf
und dokumentieren Phasen an einer Wear-OS-Uhr oder am Handy; wer gar keine
App hat, importiert eine GPX-Datei. In beiden Fällen entstehen im Browser
Einsätze mit echter Spur — geschnitten aus der Dauer-Aufzeichnung, mit von
Hand gesetzten Phasenzeiten. Die Verwaltung bleibt im Browser (R45).
**Konzept:** eigenes Dokument nach K1, Prüfdokument nach K9. **Kein
Fable-Schritt** (E-R45-10); der R17-Review in P6 prüft Schlüsselablage auf
dem Handy und die Kopplung nach S5 samt Adress-QR mit (R49). Die Beschlüsse
E-R45-1 bis E-R45-10 sind gefallen, dazu E-R45-11 bis E-R45-13 (Schneiden in der Tagesansicht am
Ruhesegment, Phasenkonflikte behalten beide Einträge, Dienstbeginn an
Handy oder Uhr mit `day_ref` vom Handy); das Konzept legt die
Arbeitspakete, Abnahmekriterien und die verbleibenden offenen Fragen fest,
namentlich den Umgang mit Nr. 11/14 aus P4.
**Inhalt in vier Blöcken (Schnitt der Arbeitspakete im Konzept):**
**A — Browser und Server:** Schneidewerkzeug Ruhesegment → Einsatz auf der
S2-Spurspeicherung, mit manuellen Phasenzeiten und Rückgängig ·
GPX-Import als Gegenstück zum GPX-Abruf (S2/AP4) · QR-Code auf der
Geräteseite (**nur die Server-Adresse**, für Selbsthoster — seit Fassung 13,
R49) · APK-Download aus der
Web-App · Nachtrag im JSON-Vertrag (Präfixe, ohne Vertragsänderung) ·
Handbuch und `Geraete-Eingabe.md` gerätefrei nach E-P2-02.
**B — Android-Handy-App** (`android/`, Kotlin): Kopplung nach dem
S5-Protokoll (R49: Vorgabeadresse `nadoku.gen-em.org`, Adress-QR zum
Überschreiben, Code vom Handy ins Web, Rückbestätigung in der App) mit
Art/Modell nach R42 — **in Block B zuletzt**, weil es Vertragsabschnitt 1a
aus dem S5-Konzept braucht · Vordergrunddienst mit Dauer-GPS über den Dienst,
Freistellung von der Akkuoptimierung beim Erststart · SQLite-Puffer ·
Live-Upload über Mobilfunk in Teilstücken ≤ 500 Punkte, idempotent ·
Phasenknöpfe (2–9, keine Reanimation) und Einsatzabschluss · Sync-Anzeige.
**C — Wear-OS-App** im selben Gradle-Projekt: Phasenknöpfe, Zeitstempel
auf der Uhr, Puffer bei Funkabriss, Data-Layer-Sync mit Quittung; **blind
gebaut** (E-R45-7).
**D — Abschluss:** Lizenzen, CLAUDE.md, Technik (neuer Client), Backlog-
Pflege, Prüfdokument.
**Prüfung** (E-R45-8): Gradle headless, Robolectric gegen synthetische
Positionsströme aus den Referenz-Payloads, Server-Rundlauf gegen
`ingest.php`, Kreisläufe R24 für geschnittene und importierte Einsätze,
Screenshots, Wortliste R28, Messstand R35 (Schneiden auf dem
5 000-Einsätze-Konto). Gerätetest: ein Dienst auf dem S24 durch den
Auftraggeber; Uhr, sobald eine vorliegt.
**Nicht Umfang:** iOS/watchOS (R46), Reanimation auf Handy/Uhr, Store-
Verteilung (Betriebsübergang nach v1.0, setzt P5 voraus), Verwaltung in der
App.
**Zuarbeiten:** Netzfreigaben für den Android-Build in Claude Code
(`dl.google.com`, `maven.google.com`, `repo1.maven.org`,
`plugins.gradle.org`, `services.gradle.org`), Signaturschlüssel des APK,
Dienst-Test am S24, Wear-OS-Uhr (blockiert nichts) — Abschnitt 7.

### P4 — Backlog-Funktionspunkte
**Ziel:** Die offenen Backlog-Punkte mit Nutzerwert abarbeiten.
**Inhalt:** ~~Nr. 11 Sync-Anzeige der Uhr~~ · ~~Nr. 14 Kopplungsablauf
geteilter Uhren~~ — **beide mit R47 vorgezogen und erledigt** (Uhr 1.10.1 und
1.11.0 / Web 9.15.0); sie lagen hier, weil sie Nutzerwert haben, und wurden
mitgenommen, weil für Nr. 60 ohnehin eine Uhr-Auslieferung anstand.
**Es bleibt:** Nr. 21 — die 43 A4-Restfunde, gebündelt mit Nr. 18 und 19 —
nach R21: Felder mit Vertrags- oder Uhrberührung nur nach Abgleich mit dem
JSON-Vertrag.
**Nr. 2 und Nr. 3 sind nach S2 gewandert (R34):** Nr. 3 ist dort der
GPX-Abruf auf der neuen Spurspeicherung; Nr. 2 erledigt sich dort durch die
Ausdünnung — für frische Einsätze ist keine Anzeige-Vereinfachung nötig
(Messwerte im S2-Konzept, B-S2-07/E-S2-09).
**Zulieferungen aus P3:** Kurzname je Rettungsmittel als Stammdatenfeld (für
Leiste, Kacheln und Plaketten) · „Auf der Karte setzen" für Standorte in den
Einstellungen · aus der Umsetzung die Backlog-Nummern **38** (Zähler der
offenen Zuordnungen holt Daten), **40** (55 Altklassen der Streichliste
endgültig austragen), **41** (sechs Klassen ohne Regel, Frage offen) und
**42** (drei Unicode-Symbole im Markup).

### P5 — Mehrbenutzer und Administration
**Ziel:** Die Anwendung trägt eine größere Nutzerbasis sicher.
**Inhalt:** Registrierung nach R9 samt Sicherheitspaket · Rollen- und
Sichtbarkeitsmodell nach R10 · Erarbeitung sinnvoller Admin-Optionen für
größere Nutzerbasis (eigenes Konzeptkapitel; darin einordnen: Logo-Standard
der Installation, Sicherungsregeln, Rechtstexte — alle in P3 vorerst bei
Wartung bzw. eigener Seite) · **Servicemodell nach R33** (Abonnements,
Zahlungen, Kontooptionen; hängt an der Kontoseite aus P3) ·
**Einordnung der S2-Admin-Optionen** (Speichergrenze und Warnschwellen
der Sicherungen, Transportziel, Zeitplan des Komplettbackups,
Sicherungsanzahl je Konto) in die Admin-Optionen — dasselbe Muster wie bei
R32; „Alle sichern" in Schüben ist mit S2 erledigt (R34) ·
Content-Security-Policy (Backlog Nr. 8) · Mengenbremse für `ingest.php`
nach R19 (Grundsatzentscheidung, dann ggf. Entwurf und Umsetzung) ·
**konfigurierbare Support-Adresse in den E-Mail-Vorlagen nach R31** — heute
steht dort fest `philipp@gen-em.org`, was jede fremde Installation an eine
Adresse verweist, die ihr nicht gehört · die Felder für Impressum und
Datenschutzerklärung aus P3 in die Admin-Optionen einordnen (R32) ·
der Backlog-Punkt zu `csrf_check()` ohne API-Zweig — Invariante festschreiben
oder Zweig ergänzen (Nummer neu zu vergeben, siehe R21/R26).
**Stand nach P1:** Die Messgrundlage für R19 liegt vor (Backlog Nr. 17), und der Topf
`demo` zeigt bereits, wie ein Zähler auf **erfolgreiche** Aufrufe aussieht. Das
Demo-Konto ist im Rollen- und Registrierungsmodell mitzudenken (R25).
**Erweiterung aus dem Dienstbetriebs-Gespräch (30.08.2026, R36–R41):**
Konto-Lebenszyklus und Registrierungs-Sicherheitspaket nach **R37**
(Lebenszyklus-Bibliothek, Kontostatus mit Wirkung bis in `ingest.php`,
Double-Opt-In, Registrierung ohne Kontoauskunft, Selbstlöschung mit Karenz,
E-Mail-Wechsel mit Bestätigung, Einwilligungen mit Fassungskennung,
Mail-Warteschlange, Geräteschlüssel auf SHA-256, Mengengrenze je Konto,
IP-Grenzwerte für NAT/CGNAT, Onboarding samt Notfallblatt) · Support-Rolle,
Admin-TOTP, Audit-Protokoll, Ankündigungsbanner, Fehlerprotokoll-Sicht,
Health-Endpunkt und das **Betriebslage-Dashboard im festen Minimalumfang**
nach **R38** (samt Geräteverteilung nach **R42** — die Spalten legt das
R42-Kleinstpaket vorab an, P5 baut nur die Auswertung) · **Rückbau der
zentralen Stammdaten** nach **R39** ·
**Wartungsmodus-Torwächter** für ausstehende Migrationen (R40.4) · die CSP
nach Backlog Nr. 8 um HSTS, `frame-ancestors` und `nosniff` ergänzt. Die
**Hosting-Entscheidung (R36) fällt vor dem P5-Konzept**; mit P5-Beginn endet
der Autodeploy auf Produktiv (R40.2, Staging-Ziel als Zuarbeit).

### P6 — v1.0-Schnitt „Gen-EM NAdoku"
**Ziel:** Neuer Name, sauberes neues Repository, Version 1.0.
**Inhalt:** Eingangsschritt: Bug- und Sicherheitsreview inkl.
Verschlüsselungsverfahren nach R17 · Umbenennung überall (Anwendung, Doku,
Titel) · Kommentare normalisieren nach R13 · Ausnahmeliste gespeicherter
Namen nach R5 entscheiden und ggf. umsetzen · Uhr-Vertrag reviewen
(versteckte Garmin-Annahmen) und als v1 festschreiben · vollständige
Doku-Neufassung nach R16 (Handbuch mit Screenshots und klickbaren Kapiteln,
README, Technik; Anforderungen aus gesondertem Gespräch) · Backlog mit
dauerhaften Nummern ins neue Repo übernehmen · Changelog neu ab v1.0 im
Bulletpoint-Format (R15), Historie bleibt im alten Repo · Abnahme nach R11:
frische Installation liest 7.x-edbak.
**Aus P1 mitzunehmen:** Der R17-Review prüft die Demo-Konstruktion ausdrücklich mit
(R25) · Backlog Nr. 23 (Reanimationsart `beginn`) wird mit dem Vertragsreview
entschieden (R26) · die Abnahme nach R11 läuft gegen die Referenz-edbak aus
`tools/referenzdatensatz/referenz/` — seit S1 in ihrer neuen Fassung (Nutzlast 7,
mit Papierkorb) und damit vorliegend.
**Aus P3 mitzunehmen:** `docs/Lizenzen.md` (Fremdbestandteile: Leaflet,
Tabler Icons, Schriften unter OFL, Photon und OpenStreetMap als Dienste)
auf Vollständigkeit prüfen und im Impressum-Kapitel des Handbuchs
verlinken · die Logo-Wahl auf der Uhr (siehe unten).
**Aus S2 mitzunehmen:** Der R17-Review prüft ausdrücklich mit: die
Containerfassung 4 (AAD-Bindung, Schlüsselableitung, eine PBKDF2 je
Vorgang), das Spur-Blob-Format SPUR1 und die Komplettbackup-Verschlüsselung
samt Handhabung des Serverschlüssels. Die R11-Abnahme läuft unverändert
gegen die einteilige 7.x-Referenz — den Altformat-Lesepfad liefert S2.
**Aus P2 mitzunehmen:** ~~Die **Uhr-Quelltexte** gehören in die Umbenennung
(R29).~~ **Stand nach Fassung 12: erledigt** — Beispieldomain und Kommentare
mit Uhr 1.11.1, Name und Einstiegsklasse mit Uhr 2.0.0 (R48). **P6 trägt
keine Uhr-Auslieferung mehr.** Zu beachten bleibt: Die Uhr heißt seit 2.0.0
**NAdoku**, Web und Handbuch noch „Einsatzdoku" — der Namensdurchgang hier
holt das nach und findet die Uhr bereits am Ziel vor. · **Aus P3 erledigt (R47):** die
Logo-Wahl auf der Uhr — allerdings **anders entschieden als hier
vorgezeichnet**: nicht „Bezug der Wahl vom Server", sondern eine
**App-Einstellung auf der Uhr** (Uhr 1.10.0). Die Uhr kennt die
Kontoeinstellung nicht, und eine Einstellung, die man auf der Uhr sieht,
gehört auch dorthin; eine Übertragung hätte einen Vertragsnachtrag verlangt,
für eine Zierde der falsche Preis. Das Launcher-Symbol bleibt fest (immer
luftgebunden — es wird beim Übersetzen eingebacken und kann einer
Laufzeit-Einstellung nicht folgen), liegt aber seit 1.10.2 in allen neun
geforderten Größen vor. · die **R5-Ausnahmeliste** ist zugeliefert und leer, sie ist
nur noch zu beschließen · die **Übergabeliste** für R13 und den Reponamen steht
fertig in `docs/Konzept-P2-Terminologie.md`, 10.3 (Kommentare, `update.php`,
`config.example.php`, drei Fußzeilen-Links) · das **Vertragsreview** schließt
`JSON-Vertrag.md` 303 ein (R12) · die **Doku-Neufassung** nach R16 findet die
Texte näher am Code vor, aber nicht abgeglichen · der **Produktname** darf erst
zusammen mit einem neuen Demo-Passwort in die Schwachwortliste (R25) · und
`tools/wortliste/` läuft bei der Umbenennung mit (R28).
**Vor dem Anlegen des neuen Repositoriums:** ein Durchgang über den ganzen
Baum auf Namen und Adressen nach **R31** — die Namensbeispiele in
`update.php` und im Handbuch heraus, die Support-Adresse als Konfiguration
bestätigt (aus P5), die Markenfarbnamen bewusst behalten. Im neuen Repo
beginnt die Historie bei null; was dort nicht hineingeschrieben wird, steht
nie drin.
**Aus dem Dienstbetriebs-Gespräch (R40/R41):** Am P6-Schnitt wird einmalig
**neu aufgesetzt** — frische v1.0-Installation, Übernahme des einen
Bestandskontos per edbak (R11), Demo-Konto neu nach Runbook, danach eine
Probe des Komplettbackup-Zyklus (S2/AP8) auf der Produktivumgebung. Das neue
Repositorium startet mit Release-getriggerter Auslieferung (`main` →
Staging, Tag → Produktion), CI-Prüftor (R24/R28/R35 vor jedem
Produktiv-Deploy) und dokumentiertem Rollback-Weg. Die Rechts- und
Betreiberunterlagen nach **R41** (AVV, VVT/TOMs, MDR-Abgrenzung,
Betreiberhandbuch/Notfallplan, `security.txt`, Statusseite) liegen zur
Öffnung vor; der R17-Review prüft zusätzlich den Umgang mit Dumps und
`sicherungen/` unter dem Blick der Klartext-Koordinaten (R41) sowie die
neuen P5-Bausteine (TOTP, Lebenszyklus, Registrierung — ohnehin R17-Umfang).
**Fable-Schritt:** der Bug- und Sicherheitsreview (R17) — Hinweis und
Pause davor (K8); alles Übrige Standard Opus (K2).

### P7 — entfällt (R46)
Bis Fassung 9: „Weitere Uhren (nach v1.0, ohne Termin)" — Apple Watch
und/oder Wear OS, dazu Backlog Nr. 13. **Wear OS und die Android-Handy-App
sind nach S4 vorgezogen (R45), die Apple Watch wird nicht gebaut (R46),
Nr. 13 geht in die Uhr-Auslieferung von P6 (R29).** Die Nummer bleibt
vergeben, damit ältere Verweise (R2, R6, R12, R26, R30) lesbar bleiben.
Die Store-Verteilung der Clients (Connect IQ, Play Store) ist
Betriebsübergang nach v1.0 (R41, E-R45-6), keine Phase.

## 5. Reihenfolge und Abhängigkeiten

Sicherheitsnetz vor Umbauten (P1 vor allem Folgenden) · Wortlaut vor Gestalt
(P2 vor P3) · Gestalt vor neuen Oberflächen-Funktionen (P3 vor P5) · Mengen vor
Mehrnutzern (S2 vor P5, R34) ·
Umbenennung zuletzt (P6). Etwaiger Strukturumbau aus A6 läuft zwischen P0
und P3 (R7). P4 ist von P3 unabhängig und kann bei Bedarf vorgezogen oder
parallel geführt werden; die Uhr-Punkte 11/14 berühren die Weboberfläche
nicht. Das Zwischenpaket S1 lief zwischen P1 und P2 (R23): Es bringt den CSV-Kreislauf
auf null und ändert das Format der Sicherung — beides soll vor den großen Umbauten
stehen, nicht danach. **S1 ist ausgeliefert**; beide Kreisläufe stehen auf null.
**P2 ist umgesetzt** und hält diesen Stand (R24). Die Regressionspflicht nach R24
und die Prüfmittel nach R27 und R28 gelten für jede folgende Phase.

**Als Nächstes S2** (R34), zwischen P3 und P4. Begründung der Stelle: Die
Paketbibliothek und der Job-Einstieg müssen vor P5 stehen — P5 baute „Alle
sichern" sonst doppelt, und die Verdichtung ist die Voraussetzung dafür,
dass die Nutzerbasis aus P5 auf dem 10-GB-Kontingent wachsen darf. Vor P6
ohnehin: Der R17-Review prüft das neue Containerformat mit, und die
R11-Abnahme bekommt ihren Altformat-Lesepfad aus S2. P4 bleibt unabhängig
und kann parallel oder danach laufen; die Uhr-Punkte 11/14 berühren die
S2-Baustellen nicht.

**Die Reihenfolge der nächsten Schritte lautet damit: R42-Kleinstpaket →
S2 → S5 → S3 → R50 → S4 (parallel dazu P4) → P5** (S5 seit Fassung 13,
R49; R50 seit Fassung 14). **Stand 02.09.2026: S2 ist umgesetzt und
ausgeliefert** (Web 10.0.0 bis 12.2.0), S3 ist umgesetzt (Web 12.2.2 bis
12.4.2); das Kleinstpaket ist in Arbeit. Die Reihenfolge ist damit an einer
Stelle **anders gelaufen als geplant**: S3 kam vor S5, nicht danach. Das war
möglich, weil S5 noch kein Konzept hat; die Abhängigkeit S5 → S3 bestand nur
über `einstellungen.php` und ist mit dem S3-Deploy erledigt. Das Kleinstpaket (R42 und R44) war **vor** S2
vorgesehen, als eigene Kleinauslieferung nach dem Muster R20 — es ist keine
Phase, hat kein Konzept und kein Prüfdokument, sondern besteht aus den beiden
R-Einträgen. Es stand vorn, weil beide Punkte mit jedem Tag Wartezeit teurer
werden: Die Gerätekennung fehlt jeder Kopplung, die vorher stattfindet (R42).
**Tatsächlich ist S2 zuerst fertig geworden**; der Vorrang des Kleinstpakets
ist damit verfallen, sein Grund aber nicht — jede Kopplung, die bis dahin
stattfindet, bleibt ohne Gerätekennung.

**Berichtigt mit Fassung 14, beides am Code nachgemessen.** *Erstens:* „Mit
S2 hat es keine gemeinsame Datei" ist als Merge-Aussage **falsch**. Richtig
ist die schwächere Fassung — keine gemeinsame **Fachdatei**: `pair.php`,
`keyguard.js`, `JSON-Vertrag.md`, `Geraete-Eingabe.md` und `watch/` fasst S2
in keinem seiner Pakete an, und das trägt weiterhin. Gemeinsam sind aber die
Buchführung (`version.php`, `CHANGELOG.md`) und **die beiden
Migrationsregister** (`schema.sql`, `update.php`), die R42 zwangsläufig
berührt. Gemessen (`git merge-tree`, Basis 862559e): zwei Dateien
konfliktieren zwischen `main` und dem S2-Zweig schon **ohne** R42, mit R42
werden es fünf. Alle mechanisch bis auf einen — die letzte Zeile der
`INSERT IGNORE`-Liste in `schema.sql`, wo S2 aus dem Semikolon ein Komma
macht und R42 dieselbe Zeile umschreiben muss. **Wer diesen Block mit „ours"
oder „theirs" auflöst, verliert die Migrationskennungen der anderen Seite —
und `update.php` schluckt die MySQL-Codes 1050/1060/1061/1091, die doppelt
angesetzte Migration läuft also still durch.** Der Produktivserver merkt
nichts; der Schaden trifft die nächste Neuinstallation. Beim Zusammenführen
deshalb **beide** Kennungen behalten und danach gegenzählen, dass jede `id`
aus `$MIGRATIONS` eine Zeile in der Liste hat.

*Zweitens:* Die Dringlichkeitsbegründung zu **R44** („der Entsperrdialog
stört täglich") **trägt nicht.** Die Beobachtung im R44-Eintrag stimmt — der
Zeitstempel wird beim Treffer im Zwischenspeicher nicht erneuert, die beiden
Uhren messen Verschiedenes. Die Folgerung stimmt nicht: `verwerfeInhalt()`
lässt den Datenschlüssel `edk` bewusst liegen (`keyguard.js`, Kommentar bei
der Funktion), und `EdCrypto.getContentKey()` entpackt daraus eine Zeile
später **ohne Passwort neu**. Der Fristablauf kostet alle 30 Minuten ein
stilles Neu-Entpacken, **keinen Dialog**. Der Dialog fällt nur, wenn
`contentKey()` null liefert — bei fehlendem `edk` oder nicht passender Hülle.
Das Wahrscheinlichste für „in letzter Zeit immer wieder" ist damit die
zweite, im R44-Eintrag als Nebensache geführte Ursache: `sessionStorage`
gilt je **Tab**. Die Behebung bleibt richtig und bleibt im Kleinstpaket
(Beschluss 01.09.2026), ist aber Aufräumen, kein Heilmittel — und die dort
vorgeschriebene Abnahme ist **vor und nach** der Änderung grün und belegt
nichts. Eine unterscheidende Probe müsste `sessionStorage.getItem('pckt')`
über die Frist beobachten: heute springt der Wert alle 30 Minuten, nach der
Änderung wandert er mit jeder Bedienung mit.

**Nach S2 folgt S5** (R49, Kopplung umgekehrt): nach S2 und dem
Kleinstpaket, weil es deren Dateien teilt (`pair.php`, `devices`,
`einstellungen.php`, JSON-Vertrag, Wartungsjob, Migrationen); vor S3, weil
S3 `einstellungen.php` umbaut und den neuen Geräteabschnitt vorfinden soll.
Das S5-Konzept entsteht **erst nach S2** (Anweisung 01.09.2026); bis dahin
gilt für S4 Block B: Das Kopplungsmodul wartet auf Abschnitt 1a, der Rest
läuft.

**Nach S5 folgt S3** (R43, Oberflächen-Nacharbeit): unabhängig von P4 und
parallel zu ihm führbar. Die Stelle **nach** S2 und S5 ist bewusst gewählt — S2
fasst `einstellungen.php` an (Sicherung, Rückspielen), S5 den
Geräteabschnitt derselben Seite, und dieselbe Seite trägt mehrere
S3-Punkte; umgekehrt wäre die Nacharbeit zweimal fällig. S3
liegt vor P5, weil P5 mit dem Betriebslage-Dashboard und der Registrierung
neue Oberfläche baut, die dem dann festgeschriebenen Rhythmus folgen soll.

**Nach S3 folgt R50** (Terminologie „Sicherung" → „Backup", Fassung 14):
unmittelbar im Anschluss und nicht früher, weil S5 den Geräteabschnitt von
`einstellungen.php` umbaut und S3 die Seite selbst — eine Umstellung davor
würde von beiden überschrieben. Kein eigenes Konzept; die Vorlage
`docs/Umstellung-Backup.md` ist die Spezifikation. Sie läuft **in einem Zug**:
Eine halb durchgeführte Umstellung ist schlechter als gar keine.

**Nach S3 folgt S4** (R45, Handy- und Uhr-Client): nach S2, weil das
Schneidewerkzeug auf `spur_lib.php` arbeitet; nach S3, weil sein Browserteil
dem festgeschriebenen Rhythmus folgen soll; vor P5, weil die App P5 nicht
braucht — nur ihre öffentliche Verteilung (Mengenbremse R19, Mengengrenze
R37.10). Unabhängig von P4 und parallel zu ihm führbar; die Blöcke B und C
(Handy, Uhr) berühren die Weboberfläche nicht und können schon während S3
laufen, Block A erst danach — ausgenommen das Kopplungsmodul von Block B, das
Vertragsabschnitt 1a aus dem S5-Konzept braucht (R49).

**Parallel führbar (Fassung 10)** — Pakete, deren berührte Dateien sich
nicht überschneiden, können gleichzeitig von getrennten Instanzen auf
eigenen Zweigen bearbeitet werden; gemeinsam ist nur die Reihenfolge des
Zusammenführens auf `main` (K7, ein Push je Paket nach Freigabe; jede
Migration verlangt danach `update.php`). **Jetzt parallel zum R42-Kleinstpaket**
(S2 und S3 sind zusammengeführt, ihre Sperren gelten nicht mehr)**:** die
Konzeptarbeit S4, das Konzept S3 ist fertig, die Vorarbeiten zu
P5 (Hosting-Entscheidung, Konzeptgespräch) und das Doku-Anforderungsgespräch
zu P6 (alles Konzept, kein Code) · **S4 Blöcke B und C** (Handy- und
Uhr-App, ausschließlich `android/`; die Doku-Nachträge in `Lizenzen.md`,
`Technik.md`, `CLAUDE.md` bleiben bis Block D liegen) · **P4 Nr. 11**
(Sync-Anzeige, nur `watch/`). **Nicht parallel:** S3 zu S2
(`einstellungen.php`, `index.php`, `einsatz.php`, `admin_user.php` auf
beiden Seiten) · S4 Block A zu S2 und S3 (`spur_lib.php`, Tagesansicht,
Geräteseite, JSON-Vertrag-Nachtrag — S2/AP10 und das Kleinstpaket schreiben
denselben Vertrag) · **S5** zum Kleinstpaket und zu S2 (`pair.php`,
`devices`, `einstellungen.php`, JSON-Vertrag, Wartungsjob, Migrationen) und
zu S3 (`einstellungen.php`) · das Kopplungsmodul von S4 Block B zu S5
(Abschnitt 1a; erst nach dem S5-Konzept) · P4 Nr. 14 zum Kleinstpaket (`pair.php`, `devices`) und
zu S2 (`ingest.php`) · P4 Nr. 21 zu allem (43 Restfunde quer durch
`server/`) — erst, wenn S2 und S3 zusammengeführt sind. Faustregel: Ein
Paket, das nur `android/` oder nur `watch/` anfasst, kann immer laufen;
alles, was `server/` oder `docs/JSON-Vertrag.md` schreibt, wartet auf das
Paket davor.

**Aus R40 (Fassung 8):** S2, S3, S4 und P4 laufen noch mit dem heutigen Autodeploy;
**mit P5-Beginn** deployt `main` nur noch auf Staging, Produktiv manuell
nach Freigabe; **am P6-Schnitt** einmaliges Neuaufsetzen mit Datenübernahme
per R11-edbak. Die **Öffnung des Dienstes** folgt nach v1.0 **in Wellen**
über die R9-Betriebsarten (R41). Die Hosting-Entscheidung (R36) fällt vor
dem P5-Konzept.

## 6. Statusübersicht

| Phase | Konzept | Umsetzung | Bemerkung |
|---|---|---|---|
| P0 | fertig (Fortschreibung durch Umsetzung) | umgesetzt und ausgeliefert (Web 7.1.0/7.2.0) | Bedienprüfung nach Prüfdokument offen (~1,5 h; V-8/V-9 mit Testkonto). Sofortpaket R20 erledigt (Web 7.2.1) |
| P1 | fertig, im Repo fortgeschrieben (`tools/referenzdatensatz/Konzept-P1.md`) | umgesetzt und ausgeliefert (Web 7.2.2–7.3.1) | **Abgeschlossen.** P-08 mit S1 geschlossen, P-12 mit dem S1-Deploy |
| S1 | fertig, im Repo fortgeschrieben (`docs/Konzept-S1-Sicherung-Import.md`) | **umgesetzt und ausgeliefert** (Web 8.0.0) | Zwischenpaket nach R23. Neun Pakete C1–C9, keine Migration. **Abgeschlossen:** Prüfliste (13 Punkte) abgearbeitet, `update.php` angesehen |
| P2 | fertig, im Repo fortgeschrieben (`docs/Konzept-P2-Terminologie.md`) | **umgesetzt, geprüft und ausgeliefert** (Web 8.0.1 auf `main`) | Sechs Pakete D1–D6, keine Migration, keine Uhr-Auslieferung. Neues Prüfmittel `tools/wortliste/` (R28). Vorher 53 Treffer außerhalb der Ausnahmen, nachher 0. Offen bleibt die Prüfliste (9 Punkte, `docs/Pruefdokument-P2-Terminologie.md` 4; Punkt 4.1 braucht eine Uhr). **Stand nach Fassung 11: Punkt 4.1 ist jetzt fällig** — mit R47 liegt ein aufspielbares Kompilat für die Fenix-Reihe vor, und der Punkt („die Kopplung, einmal mit der Uhr in der Hand") ist der wichtigste der Liste. Er prüft, ob die gerätefrei umgeschriebenen Texte in Web und Handbuch 12 zu dem passen, was die Uhr tatsächlich anzeigt — und die Texte haben sich mit 1.10.1 (dritter Zustand der Sync-Seite) und 1.11.0 (Rückfrage vor dem Trennen, Handbuch 12.1) seither noch einmal geändert |
| P3 | fertig, im Repo fortgeschrieben (Konzept Abschnitt 11 und Prüfdokument vollständig; Kurzweg-Prüfliste 5.0: 14 Punkte, ~1 Stunde) | **umgesetzt und ausgeliefert** — Web 9.0.0 bis 9.13.0 (zwölf Pakete, neunzehn Versionen, drei Migrationen: 9.7.0 Standorte, 9.8.0 Kontoseite, 9.11.0 Rechtstexte), dazu **9.14.0** als erste Rückmeldungsrunde (14 Punkte, 4 Fehler, alle behoben) | Endzahlen: 272 Bilder (34 Seiten × 8 Breiten), Überlauf 0, Konsole 0, Knöpfe ≠ 44 px 0; Kontraste 21 Paare / 0 verfehlt; Wortliste 0/0/0; Stilvergleich neu geeicht (12 beabsichtigte Abweichungen); Kreislauf edbak 0 unerklärt. **Offen:** Zuarbeiten (NEF-Logo, Rechtstexte), Sichtprüfung WebKit/Firefox (nur Chromium in der Umsetzung, Symbole am Dateiverweis), Reste als Backlog 38/40/41/42; Handbuch-Gesamtabgleich bleibt P6 (R16) |
| S2 | **fertig** (`docs/Konzept-S2-Mengen-Spuren-Sicherung.md`; E-S2-01 bis E-S2-24, Beschlüsse 28.–30.08.2026) | **umgesetzt und ausgeliefert** (Web 10.0.0 bis 12.2.0, 31.08.–01.09.2026) | Zwischenpaket nach R34. Elf Pakete AP0–AP10, alle gebaut. **Vier Migrationen** (`track_blobs`, `jobs`, `letzter_punkt_am`, `backup_targets`) — `update.php` nach dem Deploy erforderlich. Kein Fable-Schritt. Neue Prüfmittel: Messstand (R35), Fassung-4-Referenz, Versand- und Komplettprobe. **Offen: die Prüfliste des Prüfdokuments** (12 Punkte), darunter als wichtigster der Wiederanlauf; dazu die beiden Zuarbeiten (Abschnitt 7) |
| S5 | offen (R49; Beschlüsse E-R49-1 bis E-R49-8 vom 01.09.2026 gefallen) — **Konzept erst nach S2 und R42-Kleinstpaket** | offen | Zwischenpaket **zwischen S2 und S3** (R49). Vier Blöcke: Server (Sitzungstabelle statt `pair_codes`, `pair.php` mit drei Anliegen), Web (Geräteseite: Code vom Gerät, Bestätigungsseite mit Art/Modell), Uhr (Code-Anzeige, Rückbestätigung, Vorgabeadresse `nadoku.gen-em.org`), Doku. Migration → `update.php`. Vertragsänderung 1a **vor** dem zweiten Client. Kein Fable-Schritt. Zuarbeit: DNS/TLS `nadoku.gen-em.org` |
| S3 | **fertig** (`docs/Konzept-S3-Oberflaechen-Nacharbeit.md`; E-S3-01 bis E-S3-16, F-S3-01 bis F-S3-03 entschieden 01.09.2026) | **umgesetzt** (Web 12.2.2 bis 12.4.2, 01.–02.09.2026) — *noch nicht ausgeliefert* | Zwölf Pakete AP1–AP12, keine Migration. **Der Kern:** Die Abstandsskala wurde eingehalten (269 Deklarationen, **null** Rohwerte) — es fehlte die Anwendungsregel. Sie steht in `docs/Design.md` Kapitel 6; von 74 Zwischenraum-Deklarationen waren 61 schon richtig, 13 Regeln sind eingestellt. `.karte` und `.feld` trugen beide 16 px: Trennung war so groß wie Bindung. **Vier Funktionsänderungen** (Höhe nur bei Luft, Autosuche im Ortsfeld, Filterausblendung aus dem Feldkatalog, Demo-Konto gesperrt). **Der Markerversatz ist behoben und gemessen:** 51,7 px vorher, 0,0 px nachher über sechs Zoomstufen. **Zwei Punkte der Liste waren bereits erledigt** (Zeilentrenner über volle Breite, weiches Trennzeichen in „Sekundärtransport“) — nachgemessen statt angenommen. **Fünf Funde über die Liste hinaus** (F-S3-A bis F-S3-E), darunter zwei aus der Rückmeldung vom 01.09.2026: `tag_spuren.php` lief ohne Seitengerüst, und `ui_meldung_markup()` ergab bei einem unbekannten Ton einen ungestalteten Kasten. Backlog Nr. 56 erledigt, Nr. 57 und 58 neu. Uhr-Kacheln neu gerastert, **Auslieferung mit S5** (E-S3-04) |
| S4 | offen (R45; Beschlüsse E-R45-1 bis E-R45-10 vom 31.08.2026 gefallen) | offen | Zwischenpaket **nach S3**, vor P5, parallel zu P4 führbar. Vier Blöcke: Browser/Server (Schneidewerkzeug, GPX-Import, Adress-QR, APK-Download), Android-Handy-App, Wear-OS-App (blind gebaut), Abschluss. Neuer Werkzeugstack `android/`. Kein Fable-Schritt. Zuarbeiten: Netzfreigaben, Signaturschlüssel, Dienst-Test S24; Uhr blockiert nichts. **Stand nach Fassung 13:** Kopplung nach dem S5-Protokoll (R49, E-R45-2 geändert); das Kopplungsmodul von Block B wartet auf das S5-Konzept, B2/B3 laufen im Übrigen weiter |
| R50 | beschlossen (Fassung 14; Vorlage `docs/Umstellung-Backup.md` im Repo, Grenzen und Arbeitsliste stehen) | offen | Terminologie-Umstellung **nach S3**, vor S4 nicht zwingend. Kein Konzept, kein Prüfdokument. Drei Entscheidungen offen (Verb „sichern", Symbolname, Dateiname `admin_sicherungen.php`) — je mit Empfehlung in R50. **Vor der Umsetzung neu zählen:** Die Konfliktmessung gegen S2 hat ein Verfallsdatum, sie ist zwischen dem 31.08. und dem 01.09. von acht auf zehn gemeinsame Dateien gewachsen |
| P4 | offen | **teilweise erledigt** | Nr. 11 und Nr. 14 sind mit R47 vorgezogen und ausgeliefert (Uhr 1.10.1 / 1.11.0, Web 9.15.0). Es bleiben Nr. 21 (A4-Restfunde) und die P3-Zulieferungen |
| P5 | offen | offen | Enthält zusätzlich die konfigurierbare Support-Adresse (R31), das Servicemodell (R33) und die Einordnung der P3- und S2-Admin-Optionen; „Alle sichern" in Schüben ist an S2 abgegeben (R34). **Erweitert um R37–R39** (Konto-Lebenszyklus und Registrierungs-Sicherheitspaket, Support-Rolle/TOTP/Audit, Betriebslage-Dashboard im festen Minimalumfang, Rückbau zentrale Stammdaten) und den Wartungsmodus-Torwächter (R40.4). Davor: Hosting-Entscheidung (R36). Ab P5-Beginn Staging statt Autodeploy (R40.2) |
| P6 | offen | offen | Anforderungsgespräch Doku-Neufassung vorgelagert (R16). **Umfang geschrumpft: Uhr-Quelltexte und Uhr-Auslieferung sind mit R47/R48 vorweggenommen** (R29 erledigt). Bleibt: R5-Liste beschließen, Namensdurchgang (R31), Zulieferungen aus P2. **Dazu (Fassung 8):** einmaliges Neuaufsetzen mit Datenübernahme per R11-edbak, neue Auslieferungskette mit CI-Prüftor und Rollback-Weg (R40), Rechts- und Betreiberunterlagen zur Öffnung (R41) |
| P7 | — | — | **entfällt** (R46): Apple Watch wird nicht gebaut, Wear OS in S4, Nr. 13 in P6 |
| R42-Kleinstpaket | beschlossen (R42, R44) | **in Arbeit** (seit 31.08.2026) | **Eigene Kleinauslieferung vor S2** (Muster R20), zusammen mit der gesondert beauftragten Uhr-Änderung; kein Konzept, kein Prüfdokument, Schemaänderung → `update.php` nach dem Deploy. Inhalt: `devices`-Spalten Art/Modell, Annahme in `pair.php`, Doku-Nachtrag. Auswertung folgt in P5 (R38). **Dazu R44** — Zeitstempel des Inhaltsschlüssels beim Treffer erneuern, damit Sitzung und Schlüssel dieselbe Frist messen (zwei Zeilen in `keyguard.js`) |

## 7. Benötigte Zuarbeiten

| Was | Wofür | Wann |
|---|---|---|
| ~~Luftrettungs-Beispieldatei~~ | P1, inhaltliche Vorlage | **erledigt** (übergeben und verwendet) |
| ~~Produktivprüfung und Anlegen des Demo-Kontos (P-12)~~ | P1-Abschluss | **erledigt** mit dem S1-Deploy |
| ~~Deploy-Freigabe S1~~ · ~~Prüfliste S1 (13 Punkte)~~ · ~~Blick auf `update.php`~~ | S1-Abschluss | **erledigt** |
| ~~Nacharbeit zu P2 freigeben und umsetzen lassen (vier Funde, R30)~~ | vor oder mit dem P2-Deploy | **erledigt** |
| ~~Deploy-Freigabe P2 (Push auf `main` = sofortige Auslieferung, K7)~~ | P2-Abschluss | **erledigt** |
| Prüfliste P2 abarbeiten (9 Punkte, `docs/Pruefdokument-P2-Terminologie.md` 4) — Schwerpunkt: der gerätefrei umgeschriebene Kopplungstext, den niemand gegen ein Gerät gehalten hat | P2-Abnahme | nach dem Deploy |
| Ein echtes Uhr-Gerät — für P2-Prüfpunkt 4.1 (Kopplung nach dem neuen Text) und den aus S1 verbliebenen Gerätefall | P2-Abnahme | wenn ein Gerät verfügbar ist; blockiert nichts |
| Neues NEF-Logo und -Favicon | P3, Logo-Wahlfunktion | vor der P3-Abnahme — die Umsetzung baut mit einem Platzhalter in denselben Maßen |
| Impressums- und Datenschutztext der eigenen Installation (R32) | P3, Abnahme der beiden Seiten | vor der P3-Abnahme; blockiert die Umsetzung nicht |
| Zugangsdaten je eines echten FTP-, FTPS- und SFTP-Ziels (Testverzeichnis genügt) | S2, Abnahme der Sicherungsziele (AP7) | **weiterhin offen.** AP7 ist gebaut und gegen zwei Sätze örtlicher Gegenstellen belegt (je 115 Erwartungen, darunter vsftpd und OpenSSH); aus dem Behälter gehen nur Verbindungen auf Port 443 hinaus. Die Abnahme ist ein Klick auf „Verbindung prüfen“ |
| Bestätigung, dass SMTP auf der Produktivinstallation eingerichtet ist | S2, Warnmails (AP6) | **weiterhin offen** — der Hinweisweg ist geprüft, der Versandweg nicht |
| Netzfreigaben in Claude Code für den Android-Build: `dl.google.com`, `maven.google.com`, `repo1.maven.org`, `plugins.gradle.org`, `services.gradle.org` (E-R45-8) | S4, Blöcke B und C | vor Beginn der Umsetzung von S4 |
| Signaturschlüssel für das APK — von der Umsetzung erzeugt, vom Auftraggeber außerhalb des Repositoriums verwahrt (E-R45-6) | S4, APK-Download | mit dem ersten Build |
| Ein Dienst-Test mit der Handy-App auf dem Samsung S24 (Akkuoptimierung, echtes GPS, Mobilfunk-Upload); erfahrungsgemäß zwei bis drei Runden (E-R45-7) | S4, Abnahme Block B | nach dem ersten lauffähigen APK |
| Eine Wear-OS-Uhr (für das S24: Galaxy Watch4 oder neuer) für den Gerätetest der Uhr-App | S4, Abnahme Block C | wenn eine Uhr verfügbar ist; **blockiert nichts** — die Uhr-App wird blind gebaut |
| DNS-Eintrag `nadoku.gen-em.org` samt TLS-Zertifikat auf dem Hoster — zunächst auf die heutige Installation, am P6-Schnitt auf die neue (R40.3); Vorgabewert der Server-Adresse in Uhr- und Handy-App (E-R49-8) | S5 (Uhr-Build) und S4 Block B | vor dem S5-Uhr-Build; die Domain selbst ist entschieden |
| **Eine Probe-Wiederherstellung der ganzen Installation** auf einem Wegwerf-Webspace (Punkt 11 der S2-Prüfliste) — sie prüft zugleich, ob das Wiederanlaufpaket vollständig ist | S2-Abnahme, danach halbjährlich | **wichtigster offener Punkt aus S2.** Blockiert nichts; aber eine Sicherung, die nie zurückgespielt wurde, ist eine Vermutung |
| Gesondertes Anforderungsgespräch Doku-Neufassung (Screenshots, klickbare Kapitel, Struktur) | P6 | vor dem P6-Konzept |
| Hosting-Entscheidung für den Dienstbetrieb: Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Grundschutz, Verschlüsselung at rest (R36) | P5-Konzept | vor dem P5-Konzept |
| Zweites Deploy-Ziel: Staging-Installation samt FTP-Zugang (R40.2) | P5-Beginn (Ende des Produktiv-Autodeploys) | vor P5 |
| SPF/DKIM/DMARC der Versanddomain eingerichtet und ein Bounce-Postfach benannt (R37.9) | P5, Mailversand und Zustellbarkeit | vor der P5-Abnahme |
| Nutzungsbedingungen, AVV und Datenschutzerklärung des Dienstes — ggf. mit rechtlicher Prüfung (R41; Mechanik R37.7) | Öffnung des Dienstes | vor der ersten Registrierungswelle |
| Entscheidung über Wellenplan und Startumfang der Öffnung (R41, R9-Betriebsarten) | Betriebsübergang nach v1.0 | vor der Öffnung |
| Freigabe je Phasenkonzept und je F-Entscheidung | alle Phasen | laufend |

## 8. Pflege dieses Dokuments

Nach jedem Phasenabschluss: Statuszeile aktualisieren. Neue programmweite
Entscheidungen als R-Nummer anhängen, nie umnummerieren. Phaseninterne
Entscheidungen bleiben im Phasenkonzept.
