# Konzept P2 — Terminologie

Programm: Gen-EM NAdoku (Rahmenplan, Phase P2, nach S1 und vor P3)
Dieses Dokument: Phasenkonzept nach K1 — Befund, Entscheidungen (E), offene
Fragen (F), Wortliste mit Grenzfällen, Arbeitspakete mit Abnahmekriterien,
Prüfprotokoll, Fehlerfunde. Es ist die Übergabeeinheit an die umsetzende
Claude-Code-Instanz und wird von ihr fortgeschrieben (Abschnitt 11).

Ablage im Repositorium: `docs/Konzept-P2-Terminologie.md` (E-P2-17); das
Prüfdokument nach K9 daneben als `docs/Pruefdokument-P2-Terminologie.md`.

Keine Versionsnummern in diesem Dokument (K3); die Umsetzung stuft die
Version selbst — der Umfang begründet nach der Zählweise in `CLAUDE.md` 2
eine **Korrekturversion** (keine neue Funktion, kein Feld, keine Migration,
keine Uhr-Auslieferung; E-P2-16). Standardmodell der Umsetzung ist Opus;
**P2 enthält keinen Fable-Schritt** (K2/K8, E-P2-09).
Fehlerfunde werden gesammelt, nicht sofort behoben (K4) — mit der in
E-P2-05 benannten Ausnahme für Sachfehler in den Texten, die P2 ohnehin
anfasst.
Je Arbeitspaket ein Commit (deutsche Nachricht); gepusht wird einmal am
Phasenende nach ausdrücklicher Bestätigung — ein Push auf `main` deployt
sofort (K7).
**Regressionspflicht R24 gilt** (Abschnitt 8). R27 greift nicht: P2 berührt
weder Papierkorb noch Rückspielweg noch Diensttag-Zuordnung.

---

## 1. Ziel

Neutraler Wortlaut Land/Luft in Oberfläche und Dokumentation, **bevor** die
neue Oberfläche (P3) entsteht — damit das Redesign fertige Texte übernimmt
und nicht selbst Wortwahl entscheiden muss.

1. **Eine Wortliste mit Grenzfällen nach R3** liegt vor (Abschnitt 5) und ist
   als Sperr- und Ausnahmeliste maschinell prüfbar (E-P2-12). Sie ist zugleich
   die Zulieferung für die R5-Ausnahmeliste in P6 (Abschnitt 5.4).
2. **Die Weboberfläche** spricht in allen sichtbaren Texten und Hilfetexten
   neutral; luftspezifische Begriffe stehen nur noch dort, wo sie Luftfahrt
   bezeichnen (Rollen, Winde, Luftrettungs-Tab).
3. **Das README beschreibt beide Einsatzarten** und nennt die Uhr-Plattform
   als das, was sie ist: die derzeit einzige, nicht die einzig mögliche.
4. **Handbuch, Export-Format und Technik-Doku** stimmen in den berührten
   Absätzen wieder mit dem Code überein — einschließlich der Sachfehler, die
   die Erhebung neben der Wortwahl gefunden hat (E-P2-05).
5. **Die Kopplungstexte sind plattformneutral** (R12): Das Web beschreibt den
   Ablauf gerätefrei; Tastenwege stehen je Uhr im Handbuch (E-P2-02).

Nicht Ziel von P2: Bezeichner im Code, Kommentare, DB-Spalten, Format- und
Vertragsfelder (R5, R13), der Reponame, die Uhr-Quelltexte (E-P2-07), das
Logo (P3), der Produktname (P6).

## 2. Befund (statische Analyse, Stand `main` = Web 8.0.0, Commit e29d593)

- **B-P2-01 — Der Rahmenplan ist bei S1 überholt.** `main` steht auf
  **Web 8.0.0** (Merge PR #5, 24.08.2026 19:45). Der Rahmenplan (Kopf, R23,
  S1-Text, Statusübersicht, Zuarbeiten) sagt „noch nicht gepusht". Die
  Voraussetzung „P2 kann beginnen, sobald der Deploy freigegeben ist"
  (Abschnitt 5 des Rahmenplans) ist damit erfüllt. Berichtigung in
  Abschnitt 10.
- **B-P2-02 — Die Basiszahl „~290 Fundstellen" zählt Teilstrings.**
  Nachgezählt auf Web 8.0.0 (ohne `CHANGELOG.md`):

  | Begriff | Rahmenplan | Teilstring-Treffer | davon echtes Wort | Bemerkung |
  |---|---|---|---|---|
  | flug | 93 | 135 | ~130 | Flugtag, Flugspur, Flugkilometer, Flugrolle … |
  | rth | 65 | 92 | **2** | 90-mal „dorthin", „northeast", „earth" |
  | hems | 40 | 62 | 62 | davon 15 PHP (Rollencode/DB), 18 JS, Rest Doku |
  | pilot | 35 | 50 | 50 | überwiegend Rollenbeschriftung |
  | hubschrauber | 21 | 47 | 47 | 11 davon in Generatoren unter `tools/` |
  | luftrettung | 16 | 30 | 30 | |
  | maschine | 15 | 32 | 13 | 19-mal „maschinell", „maschinenlesbar", „Rechner" |
  | heli | 8 | 13 | **0** | Logo-Dateiname und „naheliegend" |

  Echte Treffer rund **330**, davon nach grober Klassifizierung etwa **80 %
  Kommentare, Bezeichner, Formatfelder oder ausdrückliche Historie**, die
  nach R5/R13 nicht angefasst werden. Die Arbeit ist deutlich kleiner als die
  Zahl suggeriert — und deutlich stärker Dokumentation als Oberfläche.
- **B-P2-03 — Der größte Teil ist seit Web 6.0.0–6.3.0 erledigt.** Flugtag →
  Diensttag, Hubschrauber → Rettungsmittel, Maschine → Rettungsmittel, Abflug →
  Ausrücken, Landung Krankenhaus → Ankunft Klinik, „Flug km" → „km",
  `luftrettungsdokumentation_` → `einsatzdokumentation_` (Web 6.2.0),
  Kopfleiste „Einsatzdokumentation Notarzt" (`ui.php` 185), alle E-Mails
  (`pair.php`, `reset_request.php`, `admin_users.php`), Seitentitel
  („Einsatzdoku"), die Phasen der Uhr (Uhr 1.8.0, `Const.mc`). Der
  Feldkatalog `mission_fields.php` ist neutral; Winde, Luftverladung und
  Bergwacht erscheinen nur an luftgebundenen Rettungsmitteln
  (`VEHICLE_CAPABILITIES`, `db.php` 380). Das Rollenmodell trennt sauber
  (`CREW_ROLES`, `db.php` 363: vier Luft-, zwei Boden-, eine gemeinsame Rolle).
- **B-P2-04 — In der Weboberfläche sind sieben Stellen sichtbar luftlastig
  oder plattformgebunden** (Zeilen Stand e29d593):

  | Nr. | Datei:Zeile | Heutiger Wortlaut (gekürzt) | Befund |
  |---|---|---|---|
  | W1 | `einstellungen.php:2216–2223` | „Sync-Seite der Uhr → **START gedrückt halten** → Code eintippen; die Sync-Seite erreichst du vom Startbildschirm mit DOWN" | Garmin-Tastenweg; für die Venu 3s (zwei Tasten, Touch) schon heute falsch |
  | W2 | `einstellungen.php:2259–2260` | „Beide Werte in den Connect-IQ-Einstellungen der Uhr-App eintragen (… z. B. `luftrettung.net`)" | Markenname und Produktivdomain als Beispiel; die im Rahmenplan genannte Stelle |
  | W3 | `import.php:154–155` | „Ohne personenbezogene Angaben entfallen die GPX-Tracks — eine Flugspur endet am Einsatzort." | Bodentrack endet ebenso am Einsatzort |
  | W4 | `assets/import_profiles.js:378–385` | Warntext Excel-Rückimport: „… die Phasen Abflug, Ankunft Einsatzort, …, Landung Krankenhaus und Übergabezeit, …, der Track (und damit auch die Flugkilometer) …" | **Sachlich falsch**, nicht nur luftlastig: Die Phasen heißen seit 6.0.0 „Ausrücken" und „Ankunft Klinik", die Spalte „Kilometer" |
  | W5 | `einstellungen.php:1451` | „Vorschläge für das Feld „Weitere Rettungsmittel" im Einsatz (RTW, NEF, weitere Hubschrauber …)" | „weitere" setzt voraus, dass das eigene ein Hubschrauber ist |
  | W6 | `admin_stammdaten.php:574` vs. `einstellungen.php:1280` | Platzhalter „z. B. Christoph 17" (Admin) vs. „z. B. NEF Kempten 1" (NutzerIn) | inkonsistent; jede Seite zeigt nur eine Art |
  | W7 | `assets/pwquality.js:50` | Schwachwortliste: `hubschrauber`, `christoph`, `luftrettung` — kein bodengebundenes Gegenstück | vollständigkeitshalber |

  Alle übrigen Treffer in `server/` sind Kommentare, Rollenbeschriftungen,
  Formatfelder, Import-Profil-Kopfzeilen, Migrationsbeschriftungen in
  `update.php`, Fußzeilen-Links auf das Repositorium oder der bewusst
  luftsprachige Luftrettungs-Tab (`zeitraum.php` 53, 195–217; E32).
- **B-P2-05 — Die Dokumentation ist weiter vom Code entfernt als die
  Oberfläche.** Der Changelog zu Web 6.2.0 behauptet: „Verwaiste Verweise auf
  „Flugtag", „Hubschrauber" und eine Phase 10 gibt es in der Dokumentation
  nicht mehr." Das trifft nicht zu:
  - **README** (46 Zeilen) ist vollständig alt: Titel „HEMS Einsatzdoku",
    „Dokumentation von Hubschraubereinsätzen", „zeigt Flugtage", Uhr nur als
    „Fenix 6 Pro" (das Handbuch kennt drei Modelle). Kein Wort von
    Bodeneinsätzen. Die Doku-Tabelle nennt das S1-Konzept und -Prüfdokument
    nicht.
  - **Handbuch** (Stand 03.08.2026): Z. 202 sagt, die Kopfleiste zeige
    „Einsatzdokumentation Luftrettung" — der Code sagt „Notarzt". Z. 384: die
    Einsatzansicht zeige „Flugkilometer" — sie zeigt „km". Z. 1369:
    Beispieldateiname `luftrettungsdokumentation_export_…` — seit 6.2.0
    falsch. Z. 1414–1415: zitiert den falschen Warntext aus W4. Z. 12 und
    1760: `luftrettung.net` als „die" Weboberfläche bzw. als Beispiel. Z. 10,
    388: „Garmin-Uhr-App", „Von der Garmin-Uhr aufgezeichnet" außerhalb des
    Uhr-Kapitels. Z. 1243 „Flugzeiten", Z. 1468 „den Flug zu verlieren".
  - **Export-Format.md** Abschnitt 2 (Z. 165–199): Die Excel-Spaltentabelle
    listet **29** feste Spalten mit „Pilot 1 / Pilot 2 / HEMS / Flugretter"
    und „Flugkilometer"; darunter „29 Spalten, davon 7 geschützte". Der Code
    (`export.js` 264–309) schreibt **31** Spalten: 24 feste plus **sieben**
    Rollenspalten aus dem Katalog (Beschriftung `CREW_LABELS`, also
    „HEMS-TC", nicht „HEMS"), Spalte „Kilometer", und **16** geschützte
    (sieben Grunddaten, sieben Rollen, Höhe, Notizen). Abschnitt 5.2
    (Z. 708–710) zitiert den Warntext aus W4. Z. 343 „Eine Flugspur endet am
    Einsatzort". Die Behauptung aus 6.2.0, die Feldlisten seien „aus
    `assets/export.js` erzeugt", gilt für Abschnitt 3.8, nicht für 2.
  - **Technik.md** (Stand 26.07.2026): Verzeichniswurzel heißt `hems/`
    (Z. 29), Architekturbild „Garmin Fenix 6 / Connect-IQ-App" (Z. 10–11),
    Runbook Z. 2238 „Connect-IQ-Einstellungen". Die Suchparameter-Tabelle
    (Z. 846–849) ist bereits korrekt (`c1…c5` historisch, `crew_driver`,
    `crew_trainee` ergänzt).
- **B-P2-06 — Bewusst luftspezifisch und nach R3 zu behalten:** die
  Rollenbeschriftungen Pilot 1, Pilot 2, HEMS-TC, Flugretter (Fachrollen);
  der Luftrettungs-Tab der Zeitraumübersicht mit seinen zehn Kacheln
  (Flugtage, Ø Einsätze / Flugtag, Flugkilometer gesamt, Längste Flugstrecke,
  Ø Winden-Cycles / Flugtag; E32, bestätigt in E-P2-04); Winde, Luftverladung,
  Bergwacht als Fähigkeiten und Einsatzfelder; „luftgebunden" als Art; die
  Fahrzeugtypen RTH/NEF/NAW/RTW; die Kopfzeilen der Import-Profile für
  Fremd- und Altformate (`Hubschrauber`, `Flugkilometer`, `flugtag`, `HEMS`,
  `Pilot` — sie müssen alte und fremde Dateien **erkennen**); die
  Generatoren unter `tools/referenzdatensatz/` (sie erzeugen
  Hubschraubertracks und beschreiben das).
- **B-P2-07 — Nicht P2, sondern P6** (Übergabeliste in Abschnitt 10.3):
  der Repositoriums-Link `einsatzdoku-luftrettung` in drei Fußzeilen
  (`login.php` 284, `pw_handling.php` 323, `ui.php` 673) und in
  `lokal_starten.sh` 14; die Einstiegsklasse `HemsApp` der Uhr;
  `config.example.php` mit `dbname=hems`/`user=hems`; die
  Migrationsbeschriftungen in `update.php` („Flugtage mit editierbaren
  Feldern", „Standard-Maschine …" — historische Beschriftungen, entfallen
  mit R11); versionshistorische Kommentare (R13); `JSON-Vertrag.md` 303
  („Connect-IQ-Payload-Limit" — Vertragsreview nach R12); die ~240 Treffer
  im Changelog (Historie, bleibt).
- **B-P2-08 — Prüfmittel sind unberührt.** Keines der Browser-Skripte
  (`angriffswerte.mjs`, `demo_pruefen.mjs`, `papierkorb_misch.mjs`,
  `kreislauf_*.mjs`, `sichtpruefung.mjs`), keines der Kreislaufwerkzeuge und
  nicht der Stilvergleich verankert sich an einem Luftbegriff (Textsuche über
  alle Skripte: 0 Treffer). P2 ändert kein CSS, kein Datenformat, keinen
  Schreibweg — die Kreisläufe müssen auf 0 bleiben.
- **B-P2-09 — Das P0-Konzept liegt nicht vor.** `Konzept-P0-Aufraeumen.md`
  ist weder im Repositorium noch beim Auftraggeber verfügbar (F-P2-07). Die
  Aufteilung „A5: sichtbare Texte → P2" hat damit keine Liste hinterlassen;
  Grundlage von P2 ist die Erhebung in diesem Abschnitt (E-P2-08).

## 3. Entscheidungen

Alle F-Fragen wurden **vor** Konzepterstellung entschieden (24.08.2026) und
sind hier als E überführt.

| Nr. | Entscheidung |
|---|---|
| E-P2-01 | **Geltungsbereich nach Fundort-Klassen** (Abschnitt 5.1): P2 ändert die Klassen **A** (sichtbare Web-Texte und Hilfetexte) und **B** (normative Dokumentation: README, Handbuch, Export-Format, Technik). Die Klassen C–H bleiben unangetastet; jede hat einen benannten Grund. Ein Treffer der Sperrliste in A oder B ist ein Fehler, ein Treffer in C–H ist keiner. |
| E-P2-02 | **Kopplungstexte gerätefrei** (F-P2-01, Option b). Das Web beschreibt den Ablauf ohne Tastennamen („auf der Uhr: Sync-Seite → Gerät koppeln → Code eintippen") und verweist für den Tastenweg auf Handbuch 2.0, wo je Uhr eine Tabelle steht; ein Garmin-Zusatz steht in Klammern, ausdrücklich als Beispiel der derzeitigen Plattform. Handbuch 12 wird entsprechend gegliedert: gerätefreie Schritte, Garmin-Zusatz je Schritt. Begründung: Der heutige Tastenweg gilt nur für Fenix/Forerunner — für die Venu 3s war der Webtext bereits falsch (B-P2-04 W1). |
| E-P2-03 | **Beispieldomain `nadoku.beispieldomain.de`** (F-P2-02) an allen Beispielstellen in Web und Doku. Die Produktivdomain wird in der Dokumentation nicht mehr genannt — jede Installation hat ihren eigenen Server (`properties.xml`). Dass die Beispieldomain den künftigen Namen trägt, ist beabsichtigt und unproblematisch: Sie ist Platzhalter, kein Produktname. Die Uhr-Quelltexte nennen weiterhin `einsatz.beispiel.de` — Nachzug mit der Uhr (E-P2-07). |
| E-P2-04 | **Der Luftrettungs-Tab behält seine Flugterminologie** (F-P2-03; E32 bestätigt). Die zehn Kacheln in `zeitraum.php` 195–217 und die Beschreibung in Handbuch 4.4 bleiben; die Ausnahme steht namentlich in der Ausnahmeliste des Prüfwerkzeugs. R3 in Reinform: Der Tab ist per Definition luftgebunden. |
| E-P2-05 | **Sachfehler in berührten Texten werden in P2 mit berichtigt, ohne Backlog-Einträge** (F-P2-04). Betrifft W4 und die in B-P2-05 genannten Falschaussagen (Excel-Spaltentabelle, Warntext, Dateiname, Kopfleiste, Kilometer). Begründung: dieselben Absätze; ein zweiter Durchgang in P6 wäre teurer. Der Gesamtabgleich in P6 (R16) bleibt davon unberührt. Ausnahme von K4, hier ausdrücklich beschlossen. |
| E-P2-06 | **`Export-Format.md` 2 wird von Hand berichtigt** (F-P2-05); ein Erzeugungswerkzeug entsteht nicht — das wäre ein Werkzeug, kein Wortlaut. Die Abnahme zählt gegen `export.js` nach (P-P2-08). |
| E-P2-07 | **Uhr-Quelltexte bleiben außen vor** (F-P2-06): `HemsApp.mc`, Kommentare, `settings.xml`/`properties.xml`, `Uploader.mc` 204. P2 liefert keine Uhr aus. Der Nachzug wird als **Plan** in Abschnitt 10.4 festgehalten, damit er in die Rahmenplan-Fortschreibung eingeht. |
| E-P2-08 | **Grundlage ist die eigene Erhebung** (F-P2-07): Das P0-Konzept steht nicht zur Verfügung; Abschnitt 2 ersetzt die dort vermutete Liste. |
| E-P2-09 | **Kein Fable-Schritt in P2** (F-P2-08). Wortliste und Ersetzung sind Sorgfalt, keine Denktiefe; alle Pakete Standardmodell (K2). |
| E-P2-10 | **Historische Nennung mit Versionsangabe bleibt.** Sätze der Bauart „hieß bis Web 5.10.0 „Flugtag"" bleiben in Code-Kommentaren wie in der Doku stehen — sie erklären alte Exporte und Sicherungen. In der Dokumentation gilt: Der alte Begriff steht nur in einem Satz, der ausdrücklich sagt, dass er alt ist. R13 regelt die Kommentare in P6. Die Ausnahmeliste des Prüfwerkzeugs erkennt solche Sätze an der Versionsangabe. |
| E-P2-11 | **R5 wird eingehalten:** DB-Spalten, Rollencodes (`p1`, `p2`, `hems`, `fr`), Spalten- und Feldnamen in CSV/Excel/edbak (`tag_crew_hems`, `winch`, Blatt „Diensttage" …), der JSON-Vertrag, die `expectedHeaders` und Spaltenschlüssel der Import-Profile, URL-Parameter (`ac`, `c1`…`c5`), CSS-Klassen und -IDs bleiben unangetastet. Die Beschriftung einer Excel-Spalte ist ein Formatfeld (sie wird beim Rückimport wiedererkannt) — auch dort keine Änderung. Kandidaten für die P6-Ausnahmeliste: Abschnitt 5.4. |
| E-P2-12 | **Prüfwerkzeug `tools/wortliste/`** (Name in der Umsetzung): Sperrliste und Ausnahmeliste aus Abschnitt 5, grep-basiert, Ergebnis ist eine **Zahl je Bereich** plus die Liste der Treffer außerhalb der Ausnahmen und die Zahl **ungenutzter** Ausnahmen (wie `kreislauf.py`: „0 ungenutzte Regeln"). Es wird **zuerst gegen den Vorher-Stand** gefahren und muss dort durchfallen (R27-Grundsatz: ein Prüfmittel, von dem niemand weiß, ob es scheitern kann, ist keines). Es bleibt im Repositorium und läuft in P3 und P6 mit — P3 erzeugt neue Texte, P6 benennt um. Vom Deploy ausgenommen (`tools/`). |
| E-P2-13 | **Produktname bis P6:** kurz „Einsatzdoku", lang „Einsatzdokumentation Notarzt" — so, wie es Code und E-Mails seit Web 6.x sagen. README, Handbuch und Technik folgen dem Code. Kein „NAdoku" vor P6 (außer in der Beispieldomain, E-P2-03). |
| E-P2-14 | **Beispiele zeigen beide Arten.** Platzhalter des Rettungsmittel-Namens auf beiden Stammdatenseiten gleichlautend „z. B. Christoph 17 oder NEF Kempten 1"; Aufzählungen wie „(RTW, NEF, RTH …)" nennen die Typen nebeneinander; Beispiele für Besatzungswechsel nennen Pilot **und** Fahrer. Wo ein einzelnes Beispiel reicht, wird es abgewechselt, nicht immer Luft. |
| E-P2-15 | **Garmin-Nennung nach Kontext.** In plattformspezifischen Teilen bleibt sie (Handbuch 2 „Die Uhr-App", Technik 5 „Uhr-App (Monkey C)", Build-Anleitung, `Geraete-Eingabe.md`, `Uhr-Layout_Regeln.md`, `tools/eingabe-probe`) — dort ist Garmin der Gegenstand. In plattformübergreifenden Texten (Web, Handbuch 1/3/4/7/8/10/12, Technik 1/2, Runbook, README) heißt es „Uhr" bzw. „Uhr-App"; wo die Plattform erstmals eingeführt wird, „derzeit für Garmin-Uhren (Connect IQ)". „Connect-IQ-Einstellungen" und „Garmin Connect" außerhalb der Uhr-Kapitel werden zu „Einstellungen der Uhr-App" (bei Garmin: Garmin Connect / Connect IQ). |
| E-P2-16 | **Versionsstufung:** Korrekturversion begründet (K3: Nummer in der Umsetzung). Keine Migration; `update.php` muss nach dem Deploy nicht aufgerufen werden. Keine Uhr-Auslieferung. |
| E-P2-17 | **Ablage:** Konzept und Prüfdokument unter `docs/`, neben denen von S1. README-Doku-Tabelle nennt beide (wie die S1-Dokumente, B-P2-05). |
| E-P2-18 | **Schwachwortliste** (`pwquality.js`): um bodengebundene Gegenstücke ergänzen (`notarztwagen`, `rettungswagen`, `notfallsanitaeter`, `rettungsdienst`) sowie `einsatzdoku` und `nadoku`. Kurzformen (`nef`, `rth`, `naw`) nur, wenn die Prüfung ganze Wörter vergleicht — in D2 am Code zu klären (`normal()`/Vergleichsweise in `pwquality.js`); vergleicht sie Teilstrings, bleiben Dreibuchstabler draußen, weil sie zu viele gute Passwörter träfen. |
| E-P2-19 | **Die Ausnahmeliste kennt eine Klasse, die in 5.1 nicht steht: `Homonym`.** Sie steht für Stellen, an denen ein Sperrwort schlicht etwas anderes bedeutet — die „Maschine" im Runbook ist ein Rechner, „Maschinen" als Zielgruppe eines Formats meint maschinelle Verarbeitung, der „Flugmodus" ist eine Einstellung des Geräts. Eine Fundort-Klasse wäre dafür die falsche Auskunft: Es geht nicht darum, *wo* der Treffer steht, sondern dass er keiner ist. Entschieden in D1, weil die Alternative — solche Stellen unter C oder D zu führen — die Klassen entwertet hätte. |
| E-P2-20 | **In der Ausnahmeliste erklärt die erste passende Regel den Treffer.** Notwendig, weil sich Regeln überschneiden: Die allgemeine Regel „Feldnamen und Kopfzeilen der Import-Profile" (Klasse D) passt auch auf die Zeile der Schwachwortliste in `pwquality.js`, deren Grund ein ganz anderer ist (Klasse F). Ohne feste Reihenfolge wäre nicht vorhersagbar, welche Begründung im Bericht steht. Folge für die Pflege: das Besondere steht oben, das Allgemeine unten. |

## 4. Offene Fragen

Keine. Alle F-Fragen (F-P2-01 bis F-P2-08) sind entschieden und als E-P2-02
bis E-P2-09 überführt (K6). Neue Fragen während der Umsetzung hier eintragen
und vor Umsetzung des betroffenen Pakets entscheiden lassen.

Zur Nachvollziehbarkeit die Fragen und ihre Antworten:

| F | Frage | Antwort → E |
|---|---|---|
| F-P2-01 | Plattformneutralität der Kopplungstexte: nur Marken ersetzen (a) oder gerätefrei mit Verweis auf Handbuch 2.0 (b)? | **b** → E-P2-02 |
| F-P2-02 | `luftrettung.net` in Web und Doku? | Beispieldomain **`nadoku.beispieldomain.de`** → E-P2-03 |
| F-P2-03 | Kacheln des Luftrettungs-Tabs? | **bleibt** → E-P2-04 |
| F-P2-04 | Sachfehler der Doku in P2 oder als Backlog nach P6? | **in P2, kein Backlog** → E-P2-05 |
| F-P2-05 | `Export-Format.md` 2 von Hand oder erzeugt? | **von Hand** → E-P2-06 |
| F-P2-06 | Uhr-Quelltexte? | **außen vor, Plan vermerken** → E-P2-07, Abschnitt 10.4 |
| F-P2-07 | Liegt das P0-Konzept vor? | **nein** → E-P2-08 |
| F-P2-08 | Fable-Schritt? | **keiner** → E-P2-09 |

## 5. Wortliste mit Grenzfällen (R3)

Die Wortliste ist das fachliche Ergebnis von P2. Sie hat drei Teile: die
Fundort-Klassen (wo gilt was), die Begriffe (was wird woraus), und die
Zulieferung an P6.

### 5.1 Fundort-Klassen

| Klasse | Fundort | Regel | Grund |
|---|---|---|---|
| **A** | Sichtbare Texte und Hilfetexte der Weboberfläche: HTML-Text in `server/*.php`, Zeichenketten in `server/assets/*.js` (ohne `vendor/`), die der Browser anzeigt (Beschriftungen, Meldungen, Platzhalter, `title`/`aria-label`, Warntexte) | **ersetzen** nach 5.2 | Ziel der Phase |
| **B** | Normative Dokumentation: `README.md`, `docs/Handbuch.md` (außer Kapitel 2), `docs/Export-Format.md`, `docs/Technik.md` (außer Kapitel 5 und Build), `docs/Backup-Format.md`, `docs/JSON-Vertrag.md` — jeweils der beschreibende Text, nicht die Feldnamen | **ersetzen** nach 5.2 | Ziel der Phase |
| **C** | Historische Nennung mit Versionsangabe („hieß bis Web 5.10.0 …"), in Code-Kommentaren wie in der Doku | **bleibt** | E-P2-10; erklärt alte Dateien |
| **D** | Gespeicherte und vertragliche Namen: DB-Spalten und -Werte, Rollencodes, CSV-/Excel-/edbak-Felder und -Spaltenbeschriftungen, JSON-Vertrag, `expectedHeaders` und Spaltenschlüssel der Import-Profile, URL-Parameter, CSS-Klassen/IDs, Dateinamen | **bleibt** | R5, E-P2-11 |
| **E** | Bezeichner und Kommentare im Code (`$flugtag…`, `HemsApp`, Docblocks), `update.php`-Migrationsbeschriftungen, Fußzeilen-Link auf das Repositorium, `config.example.php` | **bleibt**, P6 | R13, R11, Reponame (Abschnitt 10.3) |
| **F** | Luftfahrtliche Fachbegriffe, die Luftfahrt bezeichnen: Rollen Pilot 1/2, HEMS-TC, Flugretter; Winde, Windenzyklen, Luftverladung, Bergwacht; „luftgebunden"; RTH; die Kacheln des Luftrettungs-Tabs | **bleibt** | R3, E-P2-04 |
| **G** | Plattformspezifische Uhr-Dokumentation: Handbuch 2, Technik 5 und Build, `Geraete-Eingabe.md`, `Uhr-Layout_Regeln.md`, `CLAUDE.md` Kopf, `tools/eingabe-probe/` | **bleibt** Garmin | E-P2-15; Gegenstand ist die Garmin-App |
| **H** | Uhr-Quelltexte `watch/`, Generatoren und Prüfmittel unter `tools/`, `docs/CHANGELOG.md`, Konzept- und Prüfdokumente | **bleibt** | E-P2-07; Werkzeuge beschreiben Hubschraubertracks; Historie |

### 5.2 Begriffe

Spalte „Regel": **E** = ersetzen (Klassen A/B) · **B** = bleibt · **K** =
kontextabhängig (beide Spalten gefüllt).

| Begriff | Bedeutung | Regel | Ersatz in A/B | Bleibt wo / warum |
|---|---|---|---|---|
| Flugtag | Diensttag | E | Diensttag | Klasse C/D/E (Kommentare, `2026_07_17_flugtage`, Profilschlüssel `flugtag`); Kachel „Flugtage" im Luftrettungs-Tab (F) |
| Hubschrauber | das eigene Rettungsmittel allgemein | K | Rettungsmittel | als Fahrzeugtyp neben RTW/NEF: **RTH**; Profil-Kopfzeile `Hubschrauber` (D); Logo-Beschreibung in `Branding.md` (P3) |
| Maschine | Rettungsmittel | K | Rettungsmittel | „Maschine" = Rechner (Technik 2095, `lokal_starten.sh`) bleibt; Migrationsbeschriftung (E) |
| Flugkilometer, Flugstrecke, „Flug km" | Strecke des Einsatzes | K | Kilometer, km, Einsatzstrecke | Kacheln des Luftrettungs-Tabs (F, E32); Profil-Kopfzeile `Flugkilometer` (D); Feldliste `strecke_m` bereits neutral |
| Flugspur | aufgezeichnete Spur | E | Track | — (der Begriff „GPX-Track" ist im Web etabliert) |
| Flugzeiten | Phasenzeitstempel | E | Phasenzeiten | — |
| Flug (als Einheit: „den Flug verlieren") | Aufzeichnung eines Dienstes/Einsatzes | E | die Aufzeichnung / den Dienst | — |
| Abflug | Phase 3 | E | Ausrücken | CSV-Spalte hieß `phase_03_abflug` bis 6.0.0 (C); `ALT_PHASE_SLUGS` (D) |
| Landung Krankenhaus / Landung KKH | Phase 7 | E | Ankunft Klinik | wie oben |
| Flugrolle(n) | die vier Luftrollen | E | Luftrollen / Rollen des luftgebundenen Rettungsmittels | Kommentare (E) |
| Pilot 1, Pilot 2, HEMS-TC, Flugretter | Rollen | B | — | Fachrollen (F); Beschriftung im Katalog, in Exportspalten, Suchfiltern |
| Pilotenwechsel (als Beispiel) | Besatzungswechsel im Dienst | K | „Pilotenwechsel oder Fahrerwechsel" | E-P2-14; in Kommentaren (E) |
| Luftrettung | (a) als Produktbezeichnung: „Einsatzdokumentation Luftrettung", `luftrettungsdokumentation_`; (b) als Einsatzart/Tab | K | (a) Einsatzdokumentation Notarzt / `einsatzdokumentation_` | (b) Tab „Luftrettung", „Luftrettungs-Reiter", Handbuch 1 „luftgebunden wie bodengebunden" (F); Konzept-/Changelog-Nennungen (H) |
| `luftrettung.net` | Beispiel-/Produktivdomain | E | `nadoku.beispieldomain.de` | Uhr-Kommentar `Uploader.mc` 204 (H, Plan 10.4) |
| HEMS | (a) Produktname „HEMS Einsatzdoku"; (b) Rolle HEMS-TC; (c) Code `hems` | K | (a) Einsatzdoku | (b) F; (c) D — Rollencode, DB-Spalte `crew_hems`, `dbname=hems` (E) |
| Garmin, Connect IQ, Garmin Connect | Plattform | K | „Uhr", „Uhr-App", „Einstellungen der Uhr-App"; bei Ersteinführung „derzeit für Garmin-Uhren (Connect IQ)" | Klasse G vollständig; `manifest.xml`; Build-Doku |
| START / DOWN / BACK (Tastennamen) | Bedienweg | K | im Web: gerätefrei, Garmin-Zusatz in Klammern (E-P2-02) | Handbuch 2.0 (Tabelle je Uhr), 2.1–2.4 (G) |
| Fenix 6 Pro (als „die" Uhr) | Plattform | E | „Garmin-Uhren (Fenix 6 Pro, Forerunner 945, Venu 3s)" bei Ersteinführung, sonst „Uhr" | Technik 5, Handbuch 2.0 (G) |
| Christoph 17 (als einziges Beispiel) | Beispielname | K | „Christoph 17 oder NEF Kempten 1" | Referenzdatensatz nutzt fiktive Rufnamen (H) |
| Basis (als Standort) | Standort | — | — | bereits ersetzt (Web 6.x); `base_id` (D) |
| RTH, NEF, NAW, RTW | Fahrzeugtypen | B | — | F; werden nebeneinander genannt (E-P2-14) |
| Winde, Windeneinsatz, Windenzyklen, Luftverladung, Bergwacht | Fähigkeiten/Felder | B | — | F; erscheinen nur luftgebunden |
| luftgebunden / bodengebunden / neutral | Art des Diensttags | B | — | F; das Vokabular der Anwendung |
| `einsatzdoku-luftrettung` | Reponame | B | — | E; Umzug ins neue Repo ist P6 (R11) |
| `HemsApp` | Einstiegsklasse Uhr | B | — | H; Plan 10.4 |

### 5.3 Nicht in der Wortliste

Bewusst **nicht** aufgenommen, weil keine Luftbegriffe: „Diensttag",
„Rettungsmittel", „Standort", „Track", „Einsatzort" — sie sind das Ziel, nicht
der Ausgangspunkt. Ebenfalls nicht: Begriffe des Referenzdatensatzes
(„Alpenfalke", „Hochkreuth") — fiktiv und in Klasse H.

### 5.4 Zulieferung an P6 (R5-Ausnahmeliste)

R5 sieht in P6 eine kurze Liste gespeicherter Namen vor, die bei
Bodeneinsätzen **aktiv irreführen**. Ergebnis der Erhebung: **P2 findet
keinen Kandidaten.** Alle gespeicherten oder vertraglichen Namen, die
Allgemeines bezeichneten (`flugtag`, `hubschrauber`, `phase_03_abflug`,
`phase_07_landung_krankenhaus`, Blatt `Flugtage`, Dateipräfix
`luftrettungsdokumentation_`), sind in Web 6.0.0–6.2.0 bereits umbenannt, mit
Wiedererkennung der alten Namen im Import. Was luftsprachig bleibt, bezeichnet
Luftfahrt (Rollencodes `p1`/`p2`/`hems`/`fr`, `winch*`, `kind = 'air'`) und
ist bei Bodeneinsätzen leer statt irreführend. Der Suchparameter `ac`
(aircraft) ist unsichtbar und in verschickten Links gebunden. P6 kann die Liste
damit als leer beschließen oder eigene Kandidaten aus dem Redesign (P3)
nachtragen.

## 6. Arbeitspakete

Reihenfolge ist verbindlich für D1 (das Prüfwerkzeug muss den Vorher-Stand
messen, bevor irgendetwas geändert wird) und D6 (Abschluss). D2–D5 sind
voneinander unabhängig; empfohlene Reihenfolge D2 → D3 → D4 → D5, weil das
Handbuch (D4) die neuen Webtexte (D2) zitiert und die Format-Doku (D5) den
neuen Warntext aus D2 spiegelt.

### D1 — Wortliste festschreiben und Prüfwerkzeug

**Umfang:** neu `tools/wortliste/` (Skript, Sperrliste, Ausnahmeliste,
`LIESMICH.md`); `docs/Technik.md` 2 (Verzeichnisbaum). Der Deploy lädt nur
`server/` hoch (`deploy.yml` 28) — `tools/` ist damit von selbst ausgenommen.

**Vorgehen:**
1. Sperrliste aus 5.2 als Muster mit Wortgrenzen (`\bflug`, `\brth\b`,
   `hubschrauber`, `luftrettung`, `\bmaschine\b`, `hems`, `pilot`, `garmin`,
   `connect[- ]?iq`, `garmin connect`, `abflug`, `landung`, `christoph`,
   `luftrettung\.net`, `fenix`, `\bstart\b.*halten`, `\bdown\b`); je Muster
   der Grund aus 5.2. Teilstring-Fallen ausdrücklich ausschließen
   („dorthin", „northeast", „maschinell", „naheliegend").
2. Bereiche: **(a)** `server/*.php`, `server/api/*.php` — ohne
   Blockkommentare, `//`-Zeilen, Docblocks und `<?php /* … */ ?>`-Blöcke;
   **(b)** `server/assets/*.js` ohne `vendor/`, ohne Kommentare; **(c)**
   `README.md`, `docs/Handbuch.md`, `docs/Export-Format.md`,
   `docs/Technik.md`, `docs/Backup-Format.md`, `docs/JSON-Vertrag.md`,
   `docs/Branding.md`. Nicht: `CHANGELOG.md`, Konzept-/Prüfdokumente,
   `Geraete-Eingabe.md`, `Uhr-Layout_Regeln.md`, `Backlog.md`, `watch/`,
   `tools/` (Klassen G/H).
3. Ausnahmeliste: je Eintrag Datei (oder Muster), Zeilenmuster, Klasse aus
   5.1, Grund. Mindestens: Luftrettungs-Tab (`zeitraum.php` 195–217, Handbuch
   4.4 Tabelle, `api/range.php` Kommentar ist ohnehin Kommentar),
   `CREW_ROLES`-Beschriftungen und alle Nennungen „Pilot 1/2", „HEMS-TC",
   „Flugretter" als Rolle, Import-Profil-Kopfzeilen (`expectedHeaders`,
   Spaltenschlüssel), Sätze mit Versionsangabe („bis Web", „seit Web",
   „hieß") nach E-P2-10, Handbuch Kapitel 2 und Technik Kapitel 5 als
   Ganzes, Fahrzeugtypen (RTH/NEF/NAW/RTW), Fußzeilen-Link (Klasse E),
   `update.php`-Beschriftungen (E), `config.example.php` (E).
4. Ausgabe: je Bereich Zahl der Treffer gesamt, Zahl außerhalb der Ausnahmen
   (mit Datei:Zeile:Wortlaut), Zahl **ungenutzter** Ausnahmen. Rückgabewert
   ≠ 0, wenn eine der beiden letzten Zahlen > 0.
5. **Lauf gegen den Vorher-Stand** (`main` e29d593, vor jeder Änderung):
   Zahlen ins Prüfprotokoll (P-P2-01). Erwartung: außerhalb der Ausnahmen
   **deutlich > 0**, in der Größenordnung von 25–40 (die Stellen aus B-P2-04
   und B-P2-05). Weicht die Zahl stark ab, ist die Ausnahmeliste zu weit
   oder zu eng — klären, nicht anpassen, bis es passt.
6. `LIESMICH.md`: Aufruf, Bedeutung der Zahlen, wie eine Ausnahme begründet
   wird, Hinweis auf P3/P6-Mitführung (E-P2-12). Eintrag in `Technik.md` 2
   (Verzeichnisbaum) und `CLAUDE.md` 6 (Prüfen: „Wortliste bei jeder
   Textänderung" — ein Satz, analog zum Stilvergleich).

**Abnahme:** Werkzeug fällt gegen den Vorher-Stand durch (Zahl > 0,
protokolliert); Ausnahmeliste hat 0 ungenutzte Einträge gegen den Vorher-Stand
**nach** Abschluss von D5 (vorher dürfen Ausnahmen ungenutzt sein, wenn sie
Stellen betreffen, die erst entstehen — z. B. der Garmin-Zusatz in Klammern);
jeder Ausnahme-Eintrag nennt Klasse und Grund.

### D2 — Weboberfläche (Klasse A)

**Umfang:** `einstellungen.php` (Geräte, Stammdaten), `admin_stammdaten.php`,
`import.php`, `assets/import_profiles.js`, `assets/pwquality.js`.

**Vorgehen** (W-Nummern aus B-P2-04; Wortlaut ist Vorschlag, die Umsetzung
darf glätten, nicht den Sinn ändern):
1. **W1** `einstellungen.php` 2216–2223, Kopplungsanleitung nach E-P2-02:
   „Erzeuge einen Code und gib ihn auf der Uhr ein: **Sync-Seite → Gerät
   koppeln → Code eintippen**. (Auf Garmin-Uhren: Sync-Seite vom
   Startbildschirm mit DOWN, dann START gedrückt halten; Tastenwege je Uhr im
   Handbuch, Abschnitt 2.0.) Die Uhr holt sich ihre Zugangsdaten dann selbst
   — kein Abtippen langer Schlüssel. …" Rest unverändert.
2. **W2** `einstellungen.php` 2259–2260: „Beide Werte in den Einstellungen
   der Uhr-App eintragen (als Server genügt die Domain, z. B.
   `nadoku.beispieldomain.de`)."
3. **W3** `import.php` 155: „Ohne personenbezogene Angaben entfallen die
   GPX-Tracks — ein Track endet am Einsatzort." Den PHP-Kommentar darüber
   (152) mitziehen, weil er denselben Satz führt (Kommentaränderung nebenbei
   erlaubt, wenn die Zeile ohnehin angefasst wird).
4. **W4** `import_profiles.js` 378–385, Warntext: „Diese Datei enthält
   nicht alle Felder, die das System kennt. Nach dem Import bleiben leer: die
   Phasen Ausrücken, Ankunft Einsatzort, Ankunft PatientIn, Transportbeginn,
   Ankunft Klinik und Übergabezeit, sämtliche Koordinaten, die
   Reanimationsdokumentation, der Track (und damit auch die Kilometer) sowie
   ein von Hand eingetragenes Alter ohne Geburtsdatum. Für einen
   vollständigen Rückweg nutze den CSV-Export, für eine echte
   Wiederherstellung das Backup." Der Kommentar in 375 verweist auf
   „SPEC_Export.md 7.2" — heißt `docs/Export-Format.md` 5.2; berichtigen.
   **`expectedHeaders` und Spaltenschlüssel unangetastet** (E-P2-11).
5. **W5** `einstellungen.php` 1451: „(RTW, NEF, RTH …)". Dieselbe Stelle in
   `admin_stammdaten.php` prüfen (systemweite Stammdaten führen denselben
   Block?) und gleichziehen.
6. **W6** Platzhalter beider Seiten (`admin_stammdaten.php` 574,
   `einstellungen.php` 1280): „z. B. Christoph 17 oder NEF Kempten 1"
   (E-P2-14).
7. **W7** `pwquality.js` 50 nach E-P2-18; vorher die Vergleichsweise prüfen
   und im Kommentar der Liste festhalten, warum Dreibuchstabler drin oder
   draußen sind.
8. Sichtprüfung im Browser (`/chrome`): Einstellungen → Geräte (mit und ohne
   erzeugtem Code, mit manuell angelegtem Gerät), Einstellungen →
   Rettungsmittel (Platzhalter), Administration → Rettungsmittel systemweit,
   Import/Export (GPX-Hinweis erscheint nur ohne personenbezogene Angaben —
   Haken wegnehmen), Rückimport einer Excel-Standard-Datei (Warntext).
   Konsole ohne Fehler.

**Abnahme:** Wortlisten-Werkzeug meldet für Bereich (a) und (b) **0**
Treffer außerhalb der Ausnahmen; die fünf Bedienwege aus Schritt 8 zeigen den
neuen Wortlaut; `browser/angriffswerte.mjs` unverändert 42/0; der
Excel-Rückimport der Referenz-Exportdatei verhält sich wie vor D2 (gleiche
Zähler in der Import-Rückmeldung).

### D3 — README (Klasse B)

**Umfang:** `README.md`.

**Vorgehen:** Neufassung, gleiche Länge (rund 50 Zeilen), gleiche
Gliederung (Kopf, Dokumentation, Schnellstart):
1. Titel „Einsatzdoku" (E-P2-13). Erster Absatz: Dokumentation von
   **Notarzteinsätzen, luft- wie bodengebunden (RTH, NEF, NAW)** — Uhr-App
   (derzeit für Garmin-Uhren: Fenix 6 Pro, Forerunner 945, Venu 3s) erfasst
   Phasen, GPS-Tracks und Reanimations-Ereignisse; Web-App zeigt
   **Diensttage**, Einsätze und Rea-Protokolle; Ende-zu-Ende-Verschlüsselung
   und Backup wie bisher.
2. Demo-Absatz unverändert (R25).
3. Doku-Tabelle: Zeilen für `docs/Konzept-S1-Sicherung-Import.md`,
   `docs/Pruefdokument-S1-Sicherung-Import.md`, `docs/Konzept-P2-Terminologie.md`,
   `docs/Pruefdokument-P2-Terminologie.md` und `tools/wortliste/LIESMICH.md`
   ergänzen; `docs/Geraete-Eingabe.md` und `docs/Uhr-Layout_Regeln.md` fehlen
   ebenfalls — ergänzen.
4. Schnellstart: „Uhr" ohne Markennamen im Satzanfang; Build-Zeile bleibt
   Garmin-spezifisch (Klasse G), aber eingeleitet mit „Uhr (Garmin,
   Connect IQ):". `GARMIN/Apps/` ist ein Pfad, bleibt.

**Abnahme:** Wortlisten-Werkzeug 0 für `README.md`; der erste Absatz nennt
beide Einsatzarten; jede in der Tabelle genannte Datei existiert
(`ls`-Nachweis); Links im Markdown laufen.

### D4 — Handbuch (Klasse B, Kapitel 2 ausgenommen)

**Umfang:** `docs/Handbuch.md` — alle Kapitel außer 2; Stand-Datum.

**Vorgehen** (Zeilen Stand e29d593):
1. **Kapitel 1** (Z. 8–13): „Eine Uhr-App (derzeit für Garmin-Uhren)
   erfasst …"; „Die Web-Oberfläche zeigt …" ohne Domain (E-P2-03).
2. **Kapitel 3** (Z. 201–202): Kopfleiste „Einsatzdokumentation Notarzt –
   *Name*" — wie `ui.php` 185.
3. **4.2** (Z. 384): „Datum, Zeitraum, Kilometer und …"; (Z. 388)
   „Von der Uhr aufgezeichnet".
4. **4.3** (Z. 634): „typisch: ein Pilotenwechsel oder Fahrerwechsel am
   Nachmittag" (E-P2-14).
5. **4.4** (Z. 711–735): **unverändert** — beschreibt den Luftrettungs-Tab
   und begründet seine Wortwahl (E-P2-04). In die Ausnahmeliste.
6. **7** (Z. 1243): „Track und die übrigen Phasen fehlen naturgemäß".
   **7.1** (Z. 1369): `einsatzdokumentation_export_06-08-2026_standard_mit-pers_verschl_philipp-mueller.zip`.
   **7.2** (Z. 1414–1415): „die Phasen Ausrücken bis Übergabe, alle
   Koordinaten, die Reanimationsdokumentation, der Track samt Kilometern …" —
   deckungsgleich mit W4.
7. **8** (Z. 1468): „verwerfen hieße, die Aufzeichnung zu verlieren".
8. **10** (Z. 1648–1672): Text ist gerätefrei; nur prüfen.
9. **12** (Z. 1757–1786) nach E-P2-02 neu gliedern: vier gerätefreie
   Schritte (App laden, Server-Adresse in den Einstellungen der Uhr-App
   eintragen — z. B. `nadoku.beispieldomain.de`; Code im Web erzeugen; auf
   der Uhr Sync-Seite → Gerät koppeln → Code; Alternative manuell). Je
   Schritt in Klammern der Garmin-Weg („bei Garmin: Garmin Connect", „bei
   Garmin: START halten; Venu 3s: Action lang — Tabelle in 2.0"). Die
   Meldungstabelle („Zu viele Geräte" …) bleibt — sie ist Wortlaut der
   Uhr-App, nicht der Plattform.
10. **Kapitel 2** bleibt inhaltlich; **2.0** ist die Referenz, auf die
    E-P2-02 verweist — prüfen, dass die Tabelle den Kopplungs-Tastenweg
    (START lang / Action lang) tatsächlich enthält (Z. 33–39: ja, „lang
    START" → „lang Action oder lang Zurück"). Falls ein Satz fehlt, ergänzen.
11. Gesamtdurchsicht: Das Werkzeug findet Wörter, keine Perspektive. Kapitel
    1, 3, 4, 7, 8, 9 einmal ganz lesen auf Sätze, die nur von Luft aus
    gedacht sind, ohne ein Sperrwort zu enthalten (Beispiel: „landet am
    Einsatzort"). Funde in Abschnitt 11 protokollieren.
12. Stand-Datum auf das Datum der Umsetzung.

**Abnahme:** Wortlisten-Werkzeug 0 für das Handbuch außerhalb der
Ausnahmen; jeder Abschnittsverweis, der durch die Umgliederung von 12 berührt
ist (aus Web D2 auf 2.0; aus 10 auf 12; aus README auf 4 — **prüfen, ob
„Handbuch, Abschnitt 4" im README je gestimmt hat**: Kopplung steht in 10
und 12), läuft; Z. 202 stimmt mit `ui.php` 185 wörtlich überein.

### D5 — Format- und Technik-Dokumentation (Klasse B)

**Umfang:** `docs/Export-Format.md`, `docs/Technik.md`; `docs/Backup-Format.md`,
`docs/JSON-Vertrag.md`, `docs/Branding.md`, `CLAUDE.md` nur Durchsicht.

**Vorgehen:**
1. **`Export-Format.md` 2** (Z. 160–199): Tabelle neu nach `export.js`
   264–309 — **31** Spalten: 1–13 wie heute; **14–20 die sieben
   Rollenspalten in Katalogreihenfolge** (Pilot 1, Pilot 2, HEMS-TC,
   Flugretter, Fahrer, Praktikant, Sonstige Besatzung), alle mit `*`;
   21–29 Sekundärtransport … Höhe Einsatzort (m) `*`; 30 **Kilometer**;
   31 Notizen `*`. Satz darunter: „31 Spalten, davon 16 geschützte." Der
   Satz Z. 162 („Seit Web 5.8.0 gehören dazu auch die fünf Besatzungsspalten")
   → „die Besatzungsspalten (seit Web 6.0.0 sieben, aus dem Rollenkatalog)".
   Hinweis ergänzen, dass die Rollenspalten aus `CREW_ROLES` entstehen und
   bei bodengebundenen Diensttagen die Luftrollen leer bleiben. Die
   Beschriftungen selbst sind Formatfelder und ändern sich nicht (E-P2-11).
2. **`Export-Format.md` 5.2** (Z. 708–710): Warntext wörtlich wie W4.
   Z. 721: „Die Spalte `Kilometer` (bis Web 5.10.0 `Flugkilometer`) wird
   ebenfalls verworfen, weil …" (E-P2-10).
3. **`Export-Format.md` 3.5** (Z. 343): „Ein Track endet am Einsatzort".
4. **`Export-Format.md` 3.8, 4, 5.1**: Feldnamen unverändert (D); nur
   Fließtext prüfen. Z. 420 „fünf Flugrollen" steht in einem Satz mit
   Versionsangabe — bleibt (C).
5. **`Technik.md`**: Z. 10–11 Architekturbild „Uhr-App (derzeit Garmin, Monkey C)";
   Z. 29 Verzeichniswurzel `<repo>/` statt `hems/`; Z. 124/132
   „Connect-IQ-Projekt" bleibt (Klasse G — das Verzeichnis **ist** ein
   Connect-IQ-Projekt); Z. 651 „Pilotenwechsel" → „Pilotenwechsel oder
   Fahrerwechsel"; Z. 2238 Runbook „Einstellungen der Uhr-App (bei Garmin:
   Connect IQ) — Server-Domain, ID, Schlüssel"; Kapitel 5 und Build-Absätze
   unverändert (G); `tools/wortliste/` in den Verzeichnisbaum (aus D1);
   Stand-Datum.
6. **Durchsicht ohne Änderung erwartet:** `Backup-Format.md` 117/198 (C),
   `JSON-Vertrag.md` 77 (Beispiel beider Arten, ok), 303 („Connect-IQ-Payload-Limit"
   — Vertrag, P6 nach R12; **nicht** ändern, in 10.3 übergeben), 315 (C),
   `Branding.md` 136–144 (Logo, P3), `CLAUDE.md` Kopf (G; „Einsatzdokumentation
   Notarzt" ist bereits richtig).
7. Konsistenz gegenlesen: Handbuch 7.2 ↔ Export-Format 5.2 ↔ W4 (drei
   Fassungen desselben Textes müssen gleich lauten); Handbuch 7.1 ↔
   Export-Format 1 (Dateiname); README-Doku-Tabelle ↔ tatsächliche Dateien.

**Abnahme:** Wortlisten-Werkzeug 0 für Bereich (c); Nachzählung der
Excel-Spalten an einer **erzeugten** Datei (Referenz-Export aus dem
Demo-Konto, Profil Standard, mit personenbezogenen Angaben): 31 Spalten,
Reihenfolge und Beschriftungen wie in der Tabelle; ohne personenbezogene
Angaben: 15 Spalten (31 − 16) — Zahl protokollieren (P-P2-08).

### D6 — Abschluss

**Umfang:** `server/version.php`, `docs/CHANGELOG.md`, `docs/Backlog.md`
(nur Durchsicht: P2 erledigt keinen Backlog-Punkt und legt keinen an,
E-P2-05), Konzept- und Prüfdokument, Kreisläufe.

**Vorgehen:**
1. Wortlisten-Werkzeug gegen den Endstand: alle Bereiche 0 außerhalb der
   Ausnahmen, 0 ungenutzte Ausnahmen (P-P2-02).
2. Demo-Konto zurücksetzen; beide Kreisläufe (`kreislauf.py --art edbak` /
   `--art csv`, ohne `--ausnahmen`, dieselbe Datei auf beiden Seiten, R24);
   `browser/angriffswerte.mjs`; `browser/demo_pruefen.mjs`. Zahlen ins
   Prüfprotokoll.
3. Versionsstufung (Korrektur, E-P2-16); Changelog-Eintrag in Prosa mit
   Begründung: was die Erhebung ergab (die Zählung aus B-P2-02, die
   6.2.0-Behauptung aus B-P2-05), was geändert wurde, was bewusst stehen
   bleibt (Luftrettungs-Tab, Rollen, Formatfelder) und warum; Hinweis
   „keine Migration, kein `update.php`".
4. Prüfdokument nach K9 (Vorlage `docs/Pruefdokument-S1-Sicherung-Import.md`):
   Kurzfassung; was nicht geprüft werden konnte (Produktivserver, echte Uhr,
   README-Darstellung auf GitHub); maschinelle Prüfungen mit Zahlen
   (Wortliste vorher/nachher, Kreisläufe, Angriffswerte, Spaltenzählung);
   Browserprüfung; Prüfliste für den Auftraggeber (Bedienweg, erwartetes
   Ergebnis, Fehlschlag-Erkennung) — mindestens: Geräteseite lesen,
   Excel-Rückimport-Warntext lesen, GPX-Hinweis, Platzhalter, README auf
   GitHub, Handbuch 12 einmal mit der Uhr in der Hand durchgehen.
5. Abschnitt 11 dieses Dokuments vollständig; Abschnitt 10 liefert die
   Rahmenplan-Berichtigungen für die Fortschreibung durch den Auftraggeber.

**Abnahme:** P-P2-01 bis P-P2-10 mit Ist-Zahlen belegt; Doku konsistent;
Commit je Paket; **kein Push** ohne Freigabe (K7).

## 7. Prüfprotokoll (Soll)

Wird von der umsetzenden Instanz mit Ist-Zahlen fortgeschrieben; Bedienwege
und Fehlschlag-Erkennung gehören ins Prüfdokument (K9).

| Nr. | Prüfung | Soll |
|---|---|---|
| P-P2-01 | Wortlisten-Werkzeug gegen den **Vorher-Stand** (e29d593) | Treffer außerhalb der Ausnahmen **> 0**, Zahl protokolliert (Erwartung 25–40); belegt, dass das Werkzeug scheitern kann |
| P-P2-02 | Wortlisten-Werkzeug gegen den **Endstand** | alle Bereiche **0** außerhalb der Ausnahmen; **0** ungenutzte Ausnahmen; Zahl der Ausnahmen protokolliert |
| P-P2-03 | Kreislauf edbak (`kreislauf.py --art edbak`) | 0 unerklärte Abweichungen; Vergleichsumfang wie S1 (286 739 Einzelvergleiche, 16 erwartet) |
| P-P2-04 | Kreislauf CSV (`--art csv`) | 0 unerklärte Abweichungen (8 797 Einzelvergleiche, 859 erwartet, 0 ungenutzte Regeln — wie S1) |
| P-P2-05 | Dauer-Regression R20 (`browser/angriffswerte.mjs`) | 42 Einzelprüfungen, 0 Befunde |
| P-P2-06 | Demo (`browser/demo_pruefen.mjs`) | 16/16 unverändert |
| P-P2-07 | Excel-Rückimport (Browser) | Warntext neu; Import-Rückmeldung (angelegt/aktualisiert/übersprungen) identisch zum Lauf vor D2 mit derselben Datei — Zahlen beider Läufe protokolliert |
| P-P2-08 | Excel-Export gegen `Export-Format.md` 2 | erzeugte Datei: 31 Spalten mit Angaben, 15 ohne; Reihenfolge und Beschriftungen identisch zur Tabelle |
| P-P2-09 | Sichtprüfung der fünf Bedienwege (D2 Schritt 8) | neuer Wortlaut sichtbar; Konsole 0 Fehler; Anzahl geprüfter Seiten protokolliert |
| P-P2-10 | Querverweise | jede berührte Abschnittsnummer (README → Handbuch; Web → Handbuch 2.0; Handbuch 10 ↔ 12; Handbuch 7.2 ↔ Export-Format 5.2) läuft; Zahl der geprüften Verweise |

Stilvergleich (`tools/stilvergleich/`) ist **nicht** erforderlich — P2
ändert `style.css` nicht. Ändert ein Paket wider Erwarten doch eine
CSS-Regel, gilt `CLAUDE.md` 6.

## 8. Regressionspflicht (R24)

Vor Abschluss beide Kreisläufe fahren und die Zahlen ins Prüfdokument
tragen. Sollstand nach S1: **edbak 0, CSV 0.** P2 berührt keinen
Schreibweg; eine neue unerklärte Abweichung wäre ein Befund von P2 und muss
erklärt werden, bevor gepusht wird. Vor jedem Vergleichslauf wird das
Demo-Konto zurückgesetzt; Aufruf ohne `--ausnahmen`, dieselbe Datei auf
beiden Seiten. R27-Prüfmittel (`wiederherstellungs-probe`,
`papierkorb_misch.mjs`) sind nicht Pflicht (kein Papierkorb-Bezug), dürfen
aber mitlaufen.

## 9. Fehlerfunde (gesammelt, K4)

### 9.1 Funde aus der Konzepterstellung

Nach E-P2-05 in P2 behoben, kein Backlog-Eintrag. Die Nummern dienen der
Nachvollziehbarkeit in Abschnitt 11.

| Nr. | Fund | Fundort | Behoben in |
|---|---|---|---|
| F-P2-A | Warntext des Excel-Rückimports nennt Phasen und Spalte mit Namen, die seit Web 6.0.0 nicht mehr gelten (Abflug, Landung Krankenhaus, Flugkilometer) | `import_profiles.js` 378–385; Handbuch 1414; Export-Format 708 | D2, D4, D5 |
| F-P2-B | Excel-Spaltentabelle der Format-Doku falsch: 29 statt 31 Spalten, „7 geschützte" statt 16, feste Luftrollen statt Katalog, „HEMS" statt „HEMS-TC", „Flugkilometer" statt „Kilometer" | `Export-Format.md` 165–199 | D5 |
| F-P2-C | Handbuch beschreibt die Kopfleiste als „Einsatzdokumentation Luftrettung", die Einsatzansicht mit „Flugkilometer" und den Export-Dateinamen mit `luftrettungsdokumentation_` | Handbuch 202, 384, 1369 | D4 |
| F-P2-D | Kopplungsanleitung im Web nennt den Tastenweg von Fenix/Forerunner als allgemein; für die Venu 3s (seit Uhr 1.x unterstützt) stimmt er nicht | `einstellungen.php` 2216–2223 | D2 |
| F-P2-E | Platzhalter des Rettungsmittel-Namens auf NutzerInnen- und Admin-Seite verschieden | `einstellungen.php` 1280, `admin_stammdaten.php` 574 | D2 |
| F-P2-F | Kommentar verweist auf ein nicht existierendes Dokument („SPEC_Export.md 7.2") | `import_profiles.js` 375 | D2 |
| F-P2-G | Changelog Web 6.2.0 behauptet vollständige Doku-Neutralität; F-P2-A bis F-P2-C widerlegen das. Kein Fehler im Code, ein Fehler im Vorgehen (Behauptung ohne Zählung). Der Changelog wird nicht rückwirkend geändert; der P2-Eintrag nennt es | `CHANGELOG.md` 1831–1836 | D6 (Nennung) |
| F-P2-H | README-Schnellstart verweist für die Kopplung auf „Handbuch, Abschnitt 4" — die Kopplung steht in 10 und 12 | `README.md` 43 | D3 |
| F-P2-I | Rahmenplan-Basiszahl zählt Teilstrings (B-P2-02) | Rahmenplan P2-Text | Abschnitt 10.2 |

### 9.2 Funde während der Umsetzung

Mit Fundort, Wirkung, und ob blockierend (dann sofort, sonst gesammelt, K4).

| Nr. | Fund | Fundort | Wirkung | Behandlung |
|---|---|---|---|---|
| F-P2-J | **Die Sicherungsbeschreibung nennt einen Rollencode, den es nicht gibt.** Das JSON-Schema führt `"roles": ["p1", "p2", "tc", "other"]` und `"crew": { "p1": …, "p2": null, "tc": …, "other": null }`. Die Anwendung kennt `p1`, `p2`, **`hems`**, **`fr`**, `driver`, `trainee`, `other` (`db.php` `CREW_ROLES`, `schema.sql` 123). `tc` kommt in keinem Quelltext vor; die beiden Luftrollen `hems` und `fr` fehlen im Beispiel. | `docs/Backup-Format.md` 179, 225 | Wer eine Sicherung von Hand liest oder ein Werkzeug dagegen baut, sucht einen Schlüssel, den keine Datei führt. Kein Fehler im Code. | **Gesammelt, nicht behoben.** Kein Terminologiefund: `tc` ist keine Luftlastigkeit, sondern eine falsche Angabe. Die Ausnahme von K4 in E-P2-05 gilt für Texte, die P2 ohnehin anfasst — `Backup-Format.md` steht in D5 ausdrücklich nur zur Durchsicht. Empfehlung: Backlog-Eintrag oder Mitnahme in den Gesamtabgleich R16 (P6). |
| F-P2-K | **Vier Stellen der Klassen A/B, die die Erhebung in Abschnitt 2 nicht einzeln aufführt.** `einstellungen.php` 2301 (Platzhalter der Gerätebezeichnung „z. B. Fenix 6 Pro" — Markenmodell als einziges Beispiel auf einer plattformübergreifenden Seite), `Handbuch.md` 1230 (zweiter „Pilotenwechsel", derselbe Fall wie 634), 1571 („vier leere Flugrollen"), 1596 („RTW, NEF oder weitere Hubschrauber" — wörtlich dieselbe Formulierung wie W5). | s. Fundort | Ohne sie bliebe die Abnahme „0 Treffer außerhalb der Ausnahmen" unerreichbar, ohne dass ein Grund dafür genannt wäre. | **In P2 behoben**, D2 und D4, nach E-P2-14/15 — dieselbe Begründung wie für W5 und W6. |

## 10. Statuspflege und Rahmenplan-Berichtigungen

Zur Übernahme in den Rahmenplan durch den Auftraggeber (Abschnitt 8 des
Rahmenplans: neue R-Nummern anhängen, nie umnummerieren).

### 10.1 Statusberichtigung S1

Kopf, R23, S1-Text, Statusübersicht und Zuarbeiten sagen „noch nicht
gepusht"/„Deploy offen". `main` steht seit 24.08.2026 19:45 auf Web 8.0.0
(Merge PR #5). Zu ändern: „umgesetzt und **ausgeliefert**"; die Zuarbeit
„Deploy-Freigabe S1" ist erledigt; offen bleiben Prüfliste S1 (13 Punkte)
und der Blick auf `update.php` nach dem Deploy sowie P-12 aus P1.

### 10.2 Berichtigung der P2-Basiszahl

Der P2-Phasentext nennt „~290 Fundstellen — flug 93, rth 65, hems 40, pilot
35, hubschrauber 21, luftrettung 16, maschine 15, heli 8". Die Zahlen für
`rth` und `heli` sind Teilstring-Treffer (echt: 2 bzw. 0), `maschine` zur
Hälfte. Vorschlag für den Phasentext: „Wortliste mit Grenzfällen nach R3
(Erhebung im Konzept P2, Abschnitt 2 und 5; die im Rahmenplan genannte
Basiszahl zählte Teilstrings)".

### 10.3 Übergabeliste an P6

Mit dem Vertragsreview (R12), der Umbenennung und R13 zu erledigen —
**nicht** in P2:

| Stelle | Was | Warum P6 |
|---|---|---|
| `login.php` 284, `pw_handling.php` 323, `ui.php` 673, `tools/referenzdatensatz/einspielen/lokal_starten.sh` 14 | Link/Pfad `einsatzdoku-luftrettung` | Reponame; neues Repo nach R11 |
| `server/config.example.php` 5–6 | `dbname=hems`, `user=hems` | Installationsbeispiel; mit der Umbenennung |
| `server/update.php` 167–169, 297, 386, 490 | Migrationsbeschriftungen mit Flugtag/Maschine/Hubschrauber | historisch; Migrationen entfallen mit R11 |
| Kommentare in `server/` (rund 60 Stellen: `db.php` 337, `einsatz_form.php` 797, `version.php` 27/86/139, `suche.php` 342, `export.js` 512–515, 712, 960 …) | „hieß bis Web x.y.z" | R13 |
| `docs/JSON-Vertrag.md` 303 | „Connect-IQ-Payload-Limit" | Vertragsreview R12; Grenze ist plattformbedingt, der Vertrag soll sie plattformneutral begründen |
| `docs/Branding.md` 136–144, `server/assets/images/gen-em_logo_helicopter*.svg` | Hubschrauber-Bildmarke | P3 (NEF-Logo, Nutzerwahl) |
| `docs/CHANGELOG.md` | ~240 Luftbegriffe | Historie; Neustart ab v1.0 (R15) |
| R5-Ausnahmeliste | Abschnitt 5.4: P2 findet keinen Kandidaten | Beschluss in P6 |

### 10.4 Plan: Uhr-Quelltexte (Vorschlag für eine neue R-Nummer)

P2 lässt `watch/` unangetastet (E-P2-07). Die Uhr trägt dennoch drei
Reste, die mit dem Programmziel nicht zusammenpassen und eine **eigene
Uhr-Auslieferung** brauchen:

| Stelle | Rest | Vorschlag |
|---|---|---|
| `watch/source/HemsApp.mc`, `manifest.xml` (`entry="HemsApp"`) | Einstiegsklasse heißt HEMS | Umbenennung in **P6** zusammen mit `@Strings.AppName` („Einsatzdoku" → neuer Name): Beides ändert das Manifest, also einmal bauen, einmal ausliefern |
| `watch/resources/settings/settings.xml`, `properties.xml` | Beispieldomain `einsatz.beispiel.de` | mit derselben Auslieferung auf `nadoku.beispieldomain.de` (E-P2-03) |
| `Uploader.mc` 204, `Model.mc` 17–20, `StartView.mc` 86, `Uploader.mc` 186, `Util.mc` 27 | Kommentare mit `luftrettung.net`, „Flugtag", „Hubschrauberdienst", „Garmin Connect" | R13-Analogon für die Uhr in P6; Garmin-Nennung in der Uhr ist Klasse G und bleibt |

Vorschlag für den Rahmenplan: **P6 nimmt die Uhr-Quelltexte ausdrücklich in
die Umbenennung auf** (AppName, Einstiegsklasse, Beispieldomain,
Kommentare); die Uhr-Auslieferung wird damit Teil des v1.0-Schnitts. P7 bleibt
davon unberührt (neue Plattformen, nicht die bestehende). Alternativ, falls
vor P6 ohnehin eine Uhr-Auslieferung ansteht (Backlog Nr. 11, 14 in P4):
dort mitnehmen, dann ohne die Umbenennung.

### 10.5 Statuszeile P2 (nach Abschluss)

Vorlage: „P2 | fertig, im Repo fortgeschrieben (`docs/Konzept-P2-Terminologie.md`)
| umgesetzt (Web x.y.z, Push offen/erfolgt) | Sechs Pakete D1–D6; neues
Prüfmittel `tools/wortliste/` (läuft in P3 und P6 mit); keine Migration; Uhr
unverändert (Plan 10.4). Offen: Prüfliste aus dem Prüfdokument."

## 11. Umsetzungsstand

*Wird von der umsetzenden Instanz je Paket fortgeschrieben: erledigt / offen,
aufgetretene Probleme und ihre Lösung, dabei gefallene Entscheidungen (als
E-P2-19 ff. in Abschnitt 3 nachtragen), Prüfstand mit Zahlen. Vorlage je
Paket wie in `docs/Konzept-S1-Sicherung-Import.md`, Abschnitt 10.*

### Prüfumgebung

Umgesetzt am 24./25.08.2026 in einer Claude-Code-Sitzung auf einem
Linux-Container (Ubuntu 24.04). Vorgefunden: PHP 8.4.19 (CLI, mit
`pdo_mysql`, `gd`, `zip`, `mbstring`), Python 3.11.15, Node 22.22.2,
Chromium 1194 (Playwright). MariaDB 10.11.14 war **nicht** vorhanden und
wurde für diese Sitzung nachinstalliert; die lokale Installation läuft über
`tools/referenzdatensatz/einspielen/lokal_starten.sh` (MariaDB, eingebauter
PHP-Server auf 127.0.0.1:8080, `socat`-TLS davor auf 8443).

Ausgangsstand: `main` = Commit `e29d593`, Web 8.0.0. Arbeitszweig
`claude/umsetzung-iuha68`.

### D1 — Wortliste festschreiben und Prüfwerkzeug (erledigt)

**Ergebnis.** Neu: `tools/wortliste/` mit `wortliste.py` (Bereiche, Suche,
Bericht), `zerlegen.py` (Kommentare entfernen), `sperrliste.json`
(21 Muster, 9 Teilstring-Fallen), `ausnahmen.json` (33 Regeln) und
`LIESMICH.md`. Ergänzt: `docs/Technik.md` 2 (Verzeichnisbaum) und
`CLAUDE.md` 6 (ein Absatz „Wortliste bei jeder Textänderung", analog zum
Stilvergleich). Dieses Konzept liegt jetzt unter
`docs/Konzept-P2-Terminologie.md` (E-P2-17).

**Probleme und ihre Lösung.**

1. **Der Zerleger sah vierhundert Zeilen JS-Kommentar nicht.** Die erste
   Fassung las die HTML-Anteile einer PHP-Datei jeweils *zwischen* zwei
   PHP-Inseln. Ein `<script>`-Block enthält aber fast immer `<?= … ?>` —
   das öffnende `<script>` steht damit in einem Stück, der Rest des Blocks
   in einem anderen, und die Kommentare des Blocks werden nie gesucht. In
   `index.php` blieben so vierhundert Zeilen Kommentar im Befund stehen
   (Bereich a meldete 59 statt 50 Treffer, darunter neun Kommentarzeilen).
   Gelöst mit zwei Durchgängen: erst jede PHP-Insel zeilentreu leeren, dann
   den Rest als ein zusammenhängendes HTML-Dokument lesen. Der Fall ist als
   sechzehnte Probe festgeschrieben, damit er nicht zurückkommt.
2. **Ein regulärer Ausdruck als Kommentar-Entferner scheidet aus.** Der
   naheliegende Einzeiler `re.sub(r'//.*$', '', zeile)` löscht die Hälfte
   jeder Zeile mit einer URL — also sichtbaren Text. Ein so verschwundener
   Treffer fällt niemandem auf, weil das Werkzeug dann null meldet.
   Deshalb ein Zerleger, der Zeichenketten, Template-Literale, reguläre
   Ausdrücke, Heredoc und Nowdoc kennt, mit einer Selbstprobe aus sechzehn
   Fällen (`--probe`).
3. **Die Sperrliste fand Tastennamen überall.** `\bstart\b` traf jedes
   „starten". Die Tastenmuster werden deshalb **groß geschrieben** gesucht
   (`START`, `DOWN`, `BACK`); „Drop-down" und „Countdown" fallen damit von
   selbst heraus, und die Fallenliste belegt es mit einer Zahl.

**Entscheidungen** (als E-P2-19 und E-P2-20 in Abschnitt 3 nachgetragen):
die Klasse `Homonym` in der Ausnahmeliste und die Reihenfolgeregel „die
erste passende Regel erklärt den Treffer".

**Prüfstand.**

| Prüfung | Mittel | Ergebnis |
|---|---|---|
| Selbstprobe des Zerlegers | `python3 wortliste.py --probe` | **16/16** bestanden; jede Probe prüft zusätzlich, dass Zeilenzahl und Länge unverändert bleiben |
| P-P2-01, Lauf gegen den Vorher-Stand (`e29d593`, vor jeder Änderung) | `python3 wortliste.py` | **286 Treffer gesamt**, davon **233 durch Ausnahmen erklärt**, **53 außerhalb der Ausnahmen in 44 Zeilen**; **0 ungenutzte Ausnahmen**; **0 durchgerutschte Fallen**; Rückgabewert **1** |
| davon Bereich (a) `server/*.php` (59 Dateien) | — | 50 Treffer, 9 außerhalb (8 Zeilen) |
| davon Bereich (b) `server/assets/*.js` (23 Dateien) | — | 45 Treffer, 3 außerhalb (3 Zeilen) |
| davon Bereich (c) Dokumentation (7 Dateien) | — | 191 Treffer, 41 außerhalb (33 Zeilen) |
| Teilstring-Fallen | — | 23 Vorkommen (dorthin 5, earth 1, maschinell 3, maschinenlesbar 1, Drop-down 1, Countdown 12), **keines** als Treffer gezählt |

**Zur erwarteten Größenordnung (25–40).** Das Werkzeug zählt *Treffer*, das
Konzept zählte *Stellen*. Die 53 Treffer verteilen sich auf 44 Zeilen und
diese auf **30 zusammenhängende Stellen** — sechs in Bereich (a), eine in
(b), dreiundzwanzig in (c). Das liegt im erwarteten Bereich. Dass es nicht
genau 27 sind (7 aus B-P2-04 + 20 aus B-P2-05), hat zwei benennbare Gründe,
keine Unschärfe der Ausnahmeliste:

- Eine Zeile kann mehrere Muster treffen. `Handbuch.md` 1760 trifft vier
  (`luftrettung`, `luftrettung.net`, `garmin`, `garmin connect`),
  `README.md` 3 trifft drei. Umgekehrt zieht sich eine Stelle über mehrere
  Zeilen: der Kopf des README über fünf, sein Schnellstart über vier.
- Vier Stellen hat die Erhebung im Konzept nicht einzeln aufgeführt,
  obwohl sie zu Klasse A/B gehören: `einstellungen.php` 2301 (Platzhalter
  „z. B. Fenix 6 Pro"), `Handbuch.md` 1230 (zweiter Pilotenwechsel), 1571
  („vier leere Flugrollen"), 1596 („weitere Hubschrauber" — dieselbe
  Formulierung wie W5). Sie werden in D2 und D4 mit erledigt und in
  Abschnitt 9.2 als Funde geführt.

**Offen nach D1:** nichts. Das Werkzeug fällt gegen den Vorher-Stand durch
(Rückgabewert 1) — es kann also scheitern; die Ausnahmeliste hat null
ungenutzte Einträge; jeder Eintrag nennt Klasse und Grund.


### D2 — Weboberfläche (offen)

### D3 — README (offen)

### D4 — Handbuch (offen)

### D5 — Format- und Technik-Dokumentation (offen)

### D6 — Abschluss (offen)
