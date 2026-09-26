# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

**Fassung 127 (26.09.2026)** · Steuerung: Reihenfolge, Status, programmweite
Entscheidungen. Verlauf: `Rahmenplan-Verlauf.md`. Fassungen 1–15:
`Rahmenplan-Archiv.md`; Fassungen 16–124: `Rahmenplan-Archiv-2.md`.

**Stand `origin/main`** (Commit `d34908b`, gemessen 26.09.2026): Web 21.1.3 ·
Uhr 3.1.0 · Android 0.15.1.
**Läuft:** SD auf `claude/serene-tesla-sqeno2` (Konzept `docs/konzepte/Konzept-SD-Steuerungsdokumente.md`, Paket SD-01); daneben AR (PR #88 offen) und BV (gemergt, Abschluss offen) — Abschnitt 3.
**Als Nächstes:** SD-M1 (Durchsicht der Betreiberin), SD-02 bis SD-04, dann Schritt 17 — Reihenfolge in Abschnitt 3.
**Offene PRs:** #88 (AR, Android 0.16.0).
**Fällig bei der Betreiberin:** 42 Posten (Abschnitt 6.1). **`update.php`:** fällig — 10c bringt fünf Migrationen (6.1).

Kennungen sind Namen, keine Reihenfolge.

## 1. Ziel und Nicht-Ziele

Die bestehende Einsatzdokumentation wird von einem Luftrettungs-Werkzeug mit
bodengebundener Erweiterung zu **Gen-EM NAdoku v1.0**: ein
Notarzt-Dokumentationswerkzeug für Land und Luft gleichrangig, mit neuem
Namen, neuer Oberfläche, Mehrbenutzerfähigkeit und einem frischen Repositorium
`gen-em/nadoku` (R68). Betrieben wird es als **offener Dienst mit
Selbstregistrierung** bis 1 000 Konten (R36), mit einem zweiten Client neben
der Garmin-Uhr: Android-Handy und Wear-OS-Uhr (R45).

Feste Zusagen, die keine Phase aufweicht (Einzelheiten `CLAUDE.md` 4):
Ende-zu-Ende-Verschlüsselung der geschützten Felder · keine fremde Quelle zur
Laufzeit · **keine Telemetrie** — Betriebszahlen nur aus vorhandenen Spalten,
einzige Ausnahme die Gerätekennung beim Koppeln (R36, R42) · das Demo-Konto
als einzige benannte E2E-Ausnahme (R25).

**Nicht Ziel:** iOS und watchOS (R46) · Produktionsfreigabe der Clients in den
Stores vor v1.0 (Betriebsübergang, R41, R65; der interne Play-Test-Track ab
Schritt 6 ist Ziel) · ein Migrationspfad für Bestandsinstallationen (R11: v1.0
liest die 7.x-Sicherung genau einmal) · Rückwärtskompatibilität ab v1.0 (R60).

## 2. Regeln der Zusammenarbeit

### 2.1 Konventionen K1–K9

- **K1** Je Phase ein Konzept: Befund, Entscheidungen (E), Fragen an die
  Betreiberin (Q), Arbeitspakete mit Abnahmekriterien, Prüfprotokoll, Befunde
  (F); Benennung nach `docs/Pruefablauf.md` 7. Ablage `docs/konzepte/`,
  Mockups im Unterordner; Lebenszyklus in 2.2 (R62). **Ein Konzept benennt
  für jede neue Funktion ihren Ort** (R74); ohne benannten Ort kein Merge.
- **K2** Konzepte nennen keine Modellempfehlung je Arbeitspaket.
  **Standardmodell der Umsetzung ist Opus**; Fable-Schritte sind im Konzept
  ausdrücklich markiert.
- **K3** Konzepte legen keine Versionsnummern fest; das tut die Umsetzung.
- **K4** Fehlerfunde während einer Phase werden gesammelt, nicht sofort
  behoben — außer der Fund blockiert die laufende Arbeit.
- **K5** Jede Phase endet mit lauffähigem Stand, fortgeschriebenem Konzept
  und Prüfprotokoll und ihrem Eintrag hier: **eine Fahrplanzeile beim Beginn**
  (Zweig und Konzept, nicht je Paket) und **die Erledigt-Zeile nach der
  Freigabe** (Abschnitt 8). Pakete, Befunde, Messungen und Abnahmen sagt der
  Statusblock am Kopf des Konzepts, fortgeschrieben nach jedem Paket (R62).
- **K6** Q-Fragen werden vor Umsetzungsbeginn des betroffenen Pakets
  entschieden und als E-Eintrag ins Konzept überführt.
- **K7** Je Arbeitspaket ein Commit (deutsche Nachricht), **und der
  Arbeitszweig wird nach jedem Paket gepusht**. **Auf `main` kommt eine Phase
  einmal, am Ende, per Pull Request nach ausdrücklicher Bestätigung**; der
  Merge deployt auf Staging, Produktiv erreicht nur ein Tag (R67).
- **K8** Nur vor einem **Fable-Schritt** pausiert die umsetzende Instanz und
  weist darauf hin; alles Übrige läuft ohne Modellnachfrage mit Opus.
- **K9** Jede Phase liefert ein **Prüfdokument**, getrennt vom Konzept: das
  Nicht-Prüfbare zuerst, maschinelle Prüfungen mit Mittel **und** Zahl, eine
  abhakbare Prüfliste mit Bedienweg, Erwartung und Bedeutung eines
  Fehlschlags je Punkt. **Die Prüfzahlen stehen dort**, nicht in der
  Erledigt-Zeile (E-SD-10). Muster: `erledigt/Pruefdokument-S3-Oberflaechen-Nacharbeit.md`.

### 2.2 Dauerpflichten, die aus Entscheidungen erwachsen sind

- **Regressionspflicht (R24):** vor jedem Phasenabschluss beide Kreisläufe
  (`tools/referenzdatensatz/vergleich/kreislauf.py`, `csv` und `edbak`),
  Sollstand **0** unerklärte Abweichungen; Zahlen ins Prüfdokument.
- **Prüfmittel laufen mit** — welche Berührung welches Mittel ab welcher Stufe
  auslöst, steht an **einer** Stelle: `docs/Pruefablauf.md`. Die Entscheidungen
  dahinter bleiben hier verzeichnet (R27, R28, R35).
- **Prüfmittel laufen zuletzt**, nach der letzten Änderung, und jede grüne
  Zahl benennt, was sie gemessen hat (`CLAUDE.md` 6).
- **Modell (R14):** Konzepte mit Fable, mechanische Pflege ohne; Umsetzung
  nach K2/K8.
- **Deploy (R40, R67):** `main` deployt auf Staging, ein Tag `web-vX.Y.Z` nach
  Pflichtfreigabe und Backup-Tor auf Produktiv; am P8-Schnitt einmaliges
  Neuaufsetzen mit Datenübernahme per edbak (R11).
- **Backlog-Nummern sind dauerhaft.** Wer einen Zweig anlegt, der Nummern
  vergeben könnte, reserviert im Backlog-Kopf eine Spanne und pusht das
  zuerst; der Riegel `bestand` (Regel `backlog`) meldet jede doppelte Nummer.
- **Migrationsregister beim Merge:** `migration_lib.php` und `schema.sql`
  tragen je eine Liste der Kennungen; beim Zusammenführen **beide** Seiten
  behalten und gegenzählen — der Lauf schluckt doppelte Anlagen still.
- **Pflegepflichten** je Änderung nach `CLAUDE.md` 2 und 9.
- **Lebenszyklus eines Konzepts (R62).** Es entsteht in `docs/konzepte/`
  (Prüfdokument daneben) und bekommt eine Fahrplanzeile. Während der
  Umsetzung wird es nach **jedem Arbeitspaket** fortgeschrieben und der Zweig
  gepusht (K7). Nach der **Freigabe des Abschlusses** schreibt die umsetzende
  Instanz die Erledigt-Zeile (Versionen, Datum und PR, letzter Commit des
  Konzepts, Prüfdokument, Kern), die Reste nach Abschnitt 6, den Backlog in
  die Kopfzeilen, eine Verlaufszeile — und **löscht das Konzept**; die
  Fahrplanzeile geht mit, die Git-Historie behält es. Das **Prüfdokument
  bleibt, bis seine Prüfliste abgehakt ist**, und wird dann ebenso gelöscht.
  Der Bestand bis S3 liegt als Protokoll in `docs/konzepte/erledigt/`.

## 3. Fahrplan — die nächsten Schritte

Schrittnummern sind **Namen**, keine Reihenfolge; sie werden nie umvergeben.
**Die Reihenfolge der offenen Schritte ist:** **SD** (läuft) → **17** →
**18** → 12 → 12a → 13 → 14; der Betriebsübergang folgt auf v1.0. Daneben,
ohne Platz in der Reihe: **PK-06 bis PK-08** (die Kette gehört PK, dort endet
auch Kette II), der Merge von **AR**, der Abschluss von **BV**, die
Paketschnitte von **11** und Teil C von **6**. **Warum so:** 17 und 18 vor 12,
damit der Review aufgeräumte und gehärtete Seiten liest; 12a nach 12 und vor
der Öffnung, weil die Altbestand-Entscheidung ein einziges Konto voraussetzt;
SD zuerst, weil währenddessen kein anderer Zweig Rahmenplan oder Backlog
schreibt (Abschnitt 4).

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Status |
|---|---|---|---|---|---|
| 6 | **S4 — Rest, Teil C** | Play Console nach R65: interner Test-Track für Handy und Uhr unter einem Eintrag, Versionscode-Versatz, Signaturweg; Android 1.0.0 (E-R45-7). Die Vorbereitung ohne D-U-N-S und Schlüssel ist gebaut (Android 0.13.0), die Gerätetests sind erfolgt (24.09.2026) | D-U-N-S, Organisationskonto und Signaturweg (6.1, 6.2) | `docs/konzepte/Konzept-S4-Handy-Uhr-Client.md` 13; `Vorbereitung-Play-Console.md` | blockiert seit 04.09.2026 — Teile A bis C gemergt (PR #33, Abschnitt 8); Android 1.0.0 wartet auf die Zuarbeiten |
| 11 | **Planung v1.0** | Festlegungen vor dem Schnitt (R65 bis R73, entschieden 03.09.2026); Ergebnis sind die Konzepte der Phasen P6 bis P8 mit je eigenem Paketschnitt (P6 nach der Freigaberunde des Reviews) | keine | `docs/konzepte/Konzept-Planung-v1.0.md` | gemergt 03.09.2026 — Festlegungen entschieden (R65–R73); offen nur die Paketschnitte, je mit dem Konzept zu 12, 13 und 14 |
| 12 | **P6 — Review und Bereinigung** | Bedrohungsmodell und Bug- und Sicherheitsreview in zwölf Stücken (R17, R69); Freigaberunde; Sofort-, Pflicht- und Aufräumpakete; Kommentardurchgang (R13, R31); Fragen Nr. 146; R5-Ausnahmeliste | 18 gemergt; Nr. 43-Fragen beantwortet (R78) — erfüllt | neu; `docs/konzepte/Review-R17.md` entsteht im Review als Sammelstelle; Eingang `Review-Krypto-Sicherheit.md` | offen — nach 18 |
| 12a | **S11 — Ortsdaten verschlüsselt (Weg B)** (R78) | Konto-Schlüsselpaar (Nr. 53); Uhr und Handy verschlüsseln Spur, Phasenkoordinaten, Reanimationsereignisse und Zielklinik vor dem Upload; Spurfunktionen wandern in den Browser; Altbestand per Einmalwerkzeug (Nr. 43) | 12; **vor der Öffnung** | neu, nach K1 (Skizze SP-9 in `Vorbereitung-Sicherheitspaket.md`, Vorstudie `Konzept-V1-Ortsdaten.md`) | offen — nach 12 |
| 13 | **P7 — Gesicht v1.0** | Umbenennung überall (Langform in den System-E-Mails entscheiden), neues Demo-Passwort (R25); Vertrag v1 (R12, Nr. 23); Doku-Neufassung, Handbuch als HTML im Release (R16, R72); Web-App-Manifest (R70); Changelog neu (R15); Backlog-Übernahme; Altformat der Sicherung weg (Nr. 46); Kommentarregel (R69) | 12a | eigenes Konzept nach K1 | offen — nach 12a |
| 14 | **P8 — Schnitt** | Neuaufsetzen mit Übernahme per edbak (R40 (3), R60, Nr. 324); Migrationsregister neu (R66); Repo-Umzug und Inventur mit Begründung je Weglassung (R68); Kette im neuen Repositorium (R67, R40 (4)); Rechts- und Betreiberunterlagen (R41); Abnahme nach R11; Tags `web-v1.0.0`, `uhr-v…`, `android-v1.0.0` | 13 | eigenes Konzept nach K1 | offen — nach 13 |
| 17 | **Backlog-Runde 4** | Die kleinen Punkte seit Runde 3 — Prüfmittel, Doku-Konsistenz, Streichlisten, `days.created_at`, Demo-Reset-Takt, Statistik-Rest (Nr. 122); die Liste liefert `tools/steuerung/uebersicht.py` (ab SD-03) | Merge von SD | nach K1 wie Runde 3: kurzes Konzept mit Paketschnitt, kein Fable-Schritt | offen — nach SD; ein Vorgriff läuft als BV |
| 18 | **Sicherheitsrunde II** | Sitzungsbindung per Cookie-Token (Nr. 242, dazu „Gerät merken" beim Zweitfaktor), Serverschlüssel wechseln als Vorgang (Nr. 247), TOTP-Reset der einzigen BetreiberIn ohne Blatt (Nr. 249), Rest der Betreiber-Rückfrage (Nr. 233), `ingest.php`-Deadlock (Nr. 210); Nr. 228 bleibt „nur auf Anlass" | Merge von 17 | nach K1, Fable | offen — nach 17 |
| — | **Betriebsübergang** | Öffnung in Wellen über die Betriebsarten (R41); Produktionsfreigabe in den Stores mit Welle 1 (R65; nach MDR-Abgrenzung und Rechtsunterlagen); mit Welle 1 entfällt die Seitenladung (`apk.php`, Handbuch 10.1); Garmin-Uhr über den Connect-IQ-Store; halbjährliche Probe-Wiederherstellung | v1.0 | — | — |
| Kette II | **Härtung der Auslieferungskette** | Zeiger-Zweig und Integritätswache, Tor und Zielprobe, F3 behoben, eine Schrittfolge für beide Umgebungen, Abbruchverhalten, Hotfix-Weg; M1 erster grüner Produktivlauf, M2 Probe-Hotfix | — | `docs/konzepte/Konzept-Kette-Haertung.md` (E-KH-01 bis -30) | gemergt 21.09.2026 (PR #65, #66, #68); **M1 erreicht 21.09.2026** (`web-v20.26.3`, Lauf 35654132667); M2 und der Abschluss sind an PK übergeben (PK-07); Zuarbeiten in 6.1 |
| PK | **Prüfkette — jede Prüfung einmal, an ihrer Stelle** | Arbeitsumgebung als Station B mit Prüfstand und Bericht, Stufe 1 liest den Bericht gegen, Staging verschlanken, App-Auslieferung mit Signatur; PK-M2 erster Durchlauf der neuen Kette | — | `docs/konzepte/Konzept-PK-Pruefkette.md` (E-PK-01 bis -49) | Umsetzung — PK-01 bis PK-05 gemergt (PR #75, #81, #82, 23.09.2026); offen PK-06, PK-07 (übernimmt den Abschluss von Kette II), PK-08, PK-M2; Buchführung hier erst nach dem SD-Merge |
| SD | **Steuerungsdokumente schneiden** | Rahmenplan und Backlog schneiden: zweites wörtliches Archiv, Verlauf-Datei, Kopfzeilen im Backlog, Erledigt-Datei, Längendecken als Stufe-1-Schritt | Merge von 10c — erfüllt; kein anderer Zweig an Rahmenplan/Backlog | `docs/konzepte/Konzept-SD-Steuerungsdokumente.md` (E-SD-01 bis -27) | Umsetzung — SD-01 26.09.2026 auf `claude/serene-tesla-sqeno2` (diese Fassung); als Nächstes SD-M1 |
| AR | **Android-Runde** | AGP 9 und die Kette dahinter (Nr. 65), Kontrastwerkzeug prüft seine Vollständigkeit (Nr. 116, Android-Hälfte), Hausform-Zeile mit Versionsstufe und Emulatorlauf (Nr. 284); Android 0.16.0 | Merge nach 10c (E-AR-01) — erfüllt | `docs/konzepte/Konzept-AR-Android-Runde.md` (E-AR-01 bis -14) | gebaut 25.09.2026 — AR-01 bis AR-05; PR #88 offen (Merge vor dem Abschluss von BV, E-BV-13); danach Abschluss nach K9 |
| BV | **Vorgriff auf Backlog-Runde 4** | Punkte, die P5c nicht in die Quere kamen: Nr. 184, 274, 40, 214, 194; 150 zum Teil; 222, 270, 281, 212 ausgetragen | keine — bewusster Vorgriff auf 17 (E-BV-01) | `docs/konzepte/Konzept-BV-Backlog-Vorgriff.md` (E-BV-01 bis -18) | gemergt 26.09.2026 (PR #87, `d34908b`); offen Q-BV-05, Q-BV-06 und die Freigabe des Abschlusses, danach Erledigt-Zeile und Löschung (K9) |

### Schritt 12 — P6 Review und Bereinigung

**Ziel:** sauberer Code, Verhalten unverändert außer bei Funden. Eingang ist
der **Bug- und Sicherheitsreview mit Fable** (R17, R69), alles in zwölf
Stücken; Stück 1 ist das **Bedrohungsmodell** (Verschlüsselung, Container 4,
SPUR1, Komplettbackup und Serverschlüssel, Demo-Konstruktion, Schlüsselablage
auf dem Handy, Kopplungsweg, Klartext-Koordinaten mit SP-9 und Nr. 146,
Signaturschlüssel bei Google, Geheimnisse der Kette, Nr. 109).
**Kommentardurchgang:** keine Verweise auf Beschlüsse, Nummern, Fassungen oder
Konzepte mehr im Code (R13, R31). **Freigaberunde:** die Betreiberin
entscheidet je Fund; dann Sofortpaket für Kritisches, Pflicht- und
Aufräumpakete für den Rest — v1.0 wird nicht erklärt, solange ein Fund offen
ist. **Abnahme:** Review vollständig, Pakete abgenommen, Wortliste 0/0/0.

### Schritt 12a — S11 Ortsdaten verschlüsselt (Weg B)

**Ziel:** Der Einsatzort ist nicht mehr aus der Datenbank rekonstruierbar.
**Inhalt (R78, Nr. 43 und 53):** ein **Konto-Schlüsselpaar** (ECDH P-256),
privater Teil unter dem Inhaltsschlüssel gehüllt, öffentlicher Teil ans
Gerät; Uhr und Handy verschlüsseln Spur, Phasenkoordinaten,
Reanimationsereignisse und Zielklinik vor dem Upload (Garmin: ECDH,
AES-256-CBC, HMAC-SHA256; kein GCM); `seq` und Zeitstempel bleiben Klartext.
**Preis:** Ausdünnung Stufe 3, GPX-Abruf, Schneiden, Verschieben, Ortshöhe
und Zusammenführung wandern in den Browser oder entfallen; SPUR2 als Liste
versiegelter Stücke, weiter nur über `spur_lib.php`; Vertrag, Uhr- und
Android-Code, Backup Fassung 4. **Altbestand:** ein Einmalwerkzeug im Browser
für das eine Konto vor der Öffnung, danach entfernt. **Rang:** Web, Uhr,
Android Haupt. S11 baut auf `store => 'pat'` (E-S9-01) auf.

Die Schritte 13, 14 und der Betriebsübergang haben keinen Block: Ihre
Zeilen nennen den Inhalt, den Volltext hält `Rahmenplan-Archiv-2.md` 3.

## 4. Parallelität und Sperren

**Faustregel:** Ein Paket, das nur `android/` oder nur `watch/` anfasst, kann
immer laufen. Alles, was `server/`, `docs/JSON-Vertrag.md`, `schema.sql` oder
`update.php` schreibt, wartet auf das Paket davor. Gemeinsam ist immer die
Buchführung — dort sind Konflikte mechanisch, aber Migrationsliste und
Backlog-Nummern verlangen die Gegenproben aus 2.2.

| jetzt parallel möglich | nicht parallel |
|---|---|
| Konzeptarbeit zu allem; PK-06 bis PK-08 (Kette) zu 17 und 18 (`server/`) | **SD zu jedem anderen Zweig an `docs/Rahmenplan.md` und `docs/Backlog.md`** (E-SD-19) — bis der SD-PR gemergt ist, warten 17, PK-06 bis -08 und die Abschlüsse von AR und BV mit ihrer Buchführung dort |
| AR (nur `android/` und Buchführung) zu allem | 12 → 12a → 13 → 14 nacheinander, nichts parallel (R71) |
| — | S11 (12a) baut auf `store => 'pat'` (E-S9-01) auf, nicht daneben |

**Merge-Reihenfolge auf `main`:** ein Pull Request je Phase nach Freigabe
(K7); nach jeder Migration `update.php`; nach einem fremden Merge nimmt jeder
offene PR `main` nach `Pruefablauf.md` 5.3 auf, nicht über „Update branch".

## 5. Zuordnung der offenen Backlog-Punkte

Die Zuordnung steht in den Kopfzeilen der Backlog-Einträge (`gehört zu`,
`Stand`, `seit`; Konzept SD, E-SD-02, ab SD-02); die Übersicht je Schritt
erzeugt `python3 tools/steuerung/uebersicht.py` (ab SD-03). Bis SD-02 die
Kopfzeilen schreibt, gilt die Tabelle in `Rahmenplan-Archiv-2.md` 5 — Stand
Fassung 124, mit den dort genannten Lücken (Verlaufszeile 122).

## 6. Offene Abnahmen und Zuarbeiten

Eine Zeile je Posten, in drei Gruppen; Bedienweg und Erwartung stehen im
genannten Prüfdokument oder Runbook (`docs/Technik.md` 7), der Werdegang in
`Rahmenplan-Archiv-2.md` 6. Erledigt heißt gelöscht, mit Verlaufszeile. **Die
Zeilen sind aus Fassung 124 eins zu eins überführt und bis zur Durchsicht der
Betreiberin (SD-M1) ungeprüft**; wo eine Erledigung vermutet wird, steht es dabei.

### 6.1 Jetzt — blockiert etwas oder ist seit einem Merge fällig

| Was | Wofür | seit | Bedienweg |
|---|---|---|---|
| **`update.php` nach dem Merge von 10c** — fünf Migrationen (AP4, AP5, AP5b, AP7, AP8; AP8 zerstörend); Staging jetzt, Produktiv nach dem Tag, bis dahin Wartung an. Bis zur Auslieferung kein Konto mit Rolle admin (E-P5c-31) | 10c | 26.09.2026 | Runbook; P-P5c-35 bis -38 |
| **Secret `STAGING_TOTP`** in der Umgebung `staging`: der erste Stufe-2-Lauf nach dem Merge ist rot mit Ansage; `update.php` auf Staging, Prüfkonto durch das Einrichtungstor, Geheimnis eintragen, Lauf neu starten. Ohne es kommt kein Tag durch | 10c AP5 | 24.09.2026 | Archiv-2 6; P-P5c-20 ff. |
| **`app.umgebung` in die `config.php` von Staging** (`name` Staging, `farbe` rot) — nie auf Produktiv; danach Betrieb → Status, Zeile „Umgebung" | 10c AP1 (E-P5c-05) | 23.09.2026 | Runbook „Eine Testanlage kennzeichnen"; P-P5c-01 |
| **Tag `web-v21.1.3` setzen** — die Auslieferung von 10c auf Produktiv, nach `STAGING_TOTP` und `update.php` auf Staging; danach `update.php` auf Produktiv | 10c | 26.09.2026 | `Technik.md` 6; Prüfdokument P5c |
| **`betrieb.health_token`** in die `config.php` beider Anlagen, das Monitoring auf `/api/health.php?token=…` richten — ohne Eintrag antwortet der Endpunkt jedem mit 403 | 10c AP6 | 20.09.2026 | Runbook „Health-Endpunkt einrichten"; P-P5c-29 bis -31 |
| **Vorhandene Komplett-Backups auf Produktiv öffnen** (`gzip -t`) — ein Stand, der sich nicht öffnen lässt, ist kein Rückfallstand (Nr. 328). War „vor dem Merge" fällig; der Merge lief am 26.09.2026 — Stand? | 10c AP11 | 25.09.2026 | P-P5c-45 |
| **Prüfliste P5c/AP1:** Staging rot nach dem Merge, Produktiv blau nach dem Tag, Rundmail in einem echten Postfach, Ankündigung am Handy | 10c AP1 | 23.09.2026 | P-P5c-01 bis -04 |
| **Prüfliste P5c/AP2:** Archive unter Verwaltung → Protokoll → Archiv nach dem ersten Jobdurchlauf (beginnt beim ältesten Eintrag), Plakette „auf dem Ziel", einmal herunterladen — ob der Job ohne Cron in einer Woche drankommt, zeigt nur die Anlage | 10c AP2 | 24.09.2026 | P-P5c-05 bis -09 |
| **Prüfliste P5c/AP3 und AP4:** Reiter System mit echtem Fehler, Fehlerseite, stilles Fehlerprotokoll des Webspace; ein Support-Konto anlegen, einrichten, seine Sicht, der Admin-Weg vor `update.php` | 10c AP3, AP4 | 25.09.2026 | P-P5c-10 bis -19 |
| **Prüfliste P5c/AP5:** Einrichtung mit echter Authenticator-App, Codeblatt aus Chromium und Firefox gedruckt, Anmeldung mit Wiederherstellungscode, Zurücksetzen durch eine zweite BetreiberIn, Station D grün mit `STAGING_TOTP` | 10c AP5 | 24.09.2026 | P-P5c-20 ff. |
| **Prüfpunkte RW:** Statuszeile „Rückweg-Prüfung" auf Produktiv nach dem Tag, Rückwegprobe im ersten Stufe-2-Lauf, echter Rückweg auf Staging (einmal mit falschem Zettel), Mail im Postfach, Demo-Konto ohne Paar | 10c AP5b | 24.09.2026 | P-RW-01 bis -05 |
| **Prüfliste P5c/AP7:** nach `update.php` auf Staging `SHOW INDEX FROM missions` und ein `EXPLAIN`, die drei Reiter der Statistik an echten Zahlen, die Seite am Handy | 10c AP7 | 24.09.2026 | P-P5c-32 bis -34 |
| **Prüfliste P5c/AP8:** `update.php` auf Staging mit der zerstörenden Migration, ein Diensttag mit „Anderem Rettungsmittel" in Luft und Boden, Sicherungsziele ohne FTP-Plakette; auf Produktiv nach dem Tag dasselbe | 10c AP8 | 25.09.2026 | P-P5c-35 bis -38 |
| **Prüfliste P5c/AP9:** die eigene Datenschutzerklärung nachziehen (P-P5c-40, sofort), Rechtstexte mit Vorschau in Firefox und am Handy, beide Blätter auf Papier, Wartungsmodus nach einem echten Deploy, die Leiste bei 850 und über 950 px Höhe | 10c AP9 | 25.09.2026 | P-P5c-39 bis -44 |
| **Prüfliste BR:** P-BR-05 und -06 (Anlass-Zeilen und Nr. 304–313 lesen), P-BR-08 (beim nächsten Tag: das Produktionstor findet den Stufe-1-Lauf über `baumsuche.py`), P-BR-09 (beim nächsten Prüfmittel); freiwillig -03, -10, -11, -12 | BR | 24.09.2026 | `Pruefdokument-BR-Bestandsriegel.md` |
| **Zuarbeiten aus Kette II:** der Rückfallstand bei einer echten Auslieferung, der Hotfix über den ganzen Weg, die Aufbewahrung der Komplett-Stände auf Staging (Nr. 261: Vorgabe 2, Vorschlag 5) | Kette II, PK-07 | 21.09.2026 | `Pruefdokument-Kette-Haertung.md`, Prüfpunkte 26 bis 28 |
| **Staging fertig einrichten** — Schritte 7 bis 9 der Tabelle 6a (Absender und Präfix in `config.php`, Demo-Konto aus der Fixture, eigenes SFTP-Sicherungsziel); Zuarbeit Z7 aus Kette II, seit AP5 überfällig | Kette II | 20.09.2026 | 6a; `Pruefdokument-Kette-Haertung.md` |
| **Server-Anteil anlegen** — Betrieb → Servereinstellungen, Karte „Schlüssel des Servers"; ohne den Griff tut S10 nichts, die Statuszeile steht rot | S10 | 14.09.2026 | Archiv-2 6; Runbook |
| **Schlüsselblatt drucken** — zwei Ausdrucke, zwei Orte (Betriebsakte, Passwortmanager); Ablageort in der Betriebsakte vermerken | S10 | 14.09.2026 | Archiv-2 6 |
| **Einmal anmelden und auf Status nachsehen** — die Zeile „Server-Anteil" zählt die stille Umstellung je Konto mit | S10 | 14.09.2026 | Archiv-2 6 |
| **Ein `ftp`-Ziel, falls vorhanden, auf SFTP oder FTPS umstellen** — seit 10c AP8 fällt die Zielart ganz | S10 | 14.09.2026 | Archiv-2 6 |
| **Wiederanlaufpaket um den Server-Anteil ergänzen** — vier Stücke: `config.php`, Serverschlüssel, Server-Anteil, Zugang zum Backup-Ziel (E-S10-17) | S10 | 14.09.2026 | Archiv-2 6 |
| **Demo-Konto einmal „Auf Standard zurücksetzen"** (Adminbereich) — der Deploy legt nur die Fixture ab, das Konto zeigt bis zum nächsten Reset den alten Bestand | 9d | 15.09.2026 | Archiv-2 6 |
| **`update.php` für S9/9a (drei Migrationen) und P5b (sechs)** — auf Produktiv vermutlich mit M1 am 21.09.2026 von Hand erledigt (Konzept PK); bestätigen, dann löschen | S9, 9a, 10b | 10.09.2026 | Archiv-2 6 |
| **Abnahme S6:** je eine Kopplung mit Garmin-Uhr und Handy-App (Art und Modell in der Liste), eine Sitzung über 30 Minuten mit Bedienung, ein Leerlauf mit Abmeldung | S6 | 02.09.2026 | Archiv-2 6 |
| **`tools/geraetemodelle/nachaufloesen.php` auf Produktiv fahren** — oder feststellen, dass der Nachlöse-Job aus P5a AP11 (Nr. 80 Teil 1) die Zeile erledigt hat | Nr. 80, R42 | 14.09.2026 | Archiv-2 6 |
| **Das geplante Komplett-Backup einmal im Betrieb sehen** — Plan „täglich", ein Tag warten; erster Betriebsnachweis für Nr. 89 | S7 | 03.09.2026 | Archiv-2 6 |
| **Signaturschlüssel des APK verwahren** (RSA 4096, Zertifikat `078c…ad64`, übergeben 02.09.2026) — vermutlich erledigt; bestätigen | Schritt 6 | 02.09.2026 | Archiv-2 6 |
| **Passwort des eigenen Kontos prüfen** — zwölf Zeichen oder Passphrase, nirgends wiederverwendet; der Server kann es nicht prüfen (Nr. 136) | Krypto-Review | 06.09.2026 | — |
| **2FA-Zwang in der GitHub-Organisation** (Nr. 140, SP-4) — der Zweigschutz auf `main` steht seit dem 21.09.2026 (PK-M1) | 9a | 06.09.2026 | `Pruefablauf.md` 2.3 |
| **D-U-N-S-Nummer für die Gen-EM GbR** beantragen (kostenlos, bis zu vier Wochen); klären, ob die GbR als eGbR im Register steht | R65 | 03.09.2026 | Archiv-2 6 |
| **SPF/DKIM/DMARC der Versanddomain; `smtp.from` als echtes Postfach**, das die Betreiberin liest — Rückläufer liest die Anwendung nicht (E-P5c-51) | P5 | 15.09.2026 | Archiv-2 6 |
| **GitHub-Umgebungen `staging` und `produktion`, Pflichtfreigabe, Prüfkonto** — vermutlich erledigt: M1 lief durch Pflichtfreigabe und Backup-Tor; bestätigen, dann löschen | P5a | 16.09.2026 | Archiv-2 6, 6a |
| **GitHub-App auf dem Handy mit Push-Nachrichten; ob `CIQ_GERAETE_URL` als CI-Secret taugt** (bleibt bis PK-08, E-PK-48) — der Rest der Zeile (Umgebung `produktion`) ist mit M1 belegt | R67 | 03.09.2026 | Archiv-2 6 |
| **V1 bis V9 der Protokollierung** — alles gebaut (10b AP1, 10c AP2); offen allein die juristische Bestätigung der 30-Tage-Frist, die in der Zeile „Anwaltliche Prüfung" (6.3) steht — löschen? | 10c | 16.09.2026 | Archiv-2 6 |
| **Nachträge an die P5a-Instanz** (Mailrahmen, `app_url()`, SMTP-Log, AP4a) — übergeben 16.09.2026 und gebaut; löschen? | P5a | 16.09.2026 | Archiv-2 6 |
| **P5a-Reste nach dem Merge** — die Prüfliste (33 Punkte) ist abgearbeitet, das Prüfdokument am 24.09.2026 gelöscht; löschen? | P5a | 16.09.2026 | Archiv-2 6 |
| **Data Layer Uhr↔Handy auf echter Hardware** (zwischen zwei Emulatoren nicht prüfbar) — die S4-Prüfliste galt am 24.09.2026 als abgearbeitet („Gerätetest erfolgt"); bestätigen, dann löschen | Schritt 6 | 31.08.2026 | Archiv-2 6 |
| **Dienst-Test mit der Handy-App auf dem S24** (zwei bis drei Runden) — wie oben: vermutlich mit dem Gerätetest erledigt; bestätigen, dann löschen | Schritt 6 | 31.08.2026 | Archiv-2 6 |
| **DNS-Eintrag und TLS für `nadoku.gen-em.org`** — vermutlich erledigt: Produktiv antwortet dort (`PRODUKTION_URL`, Integritätswache); bestätigen, dann löschen | S5 | 03.09.2026 | Archiv-2 6 |
| **Offene Fragen aus Konzept BR** (vier): Positionsargumente in `kettenaufrufe`, Riegel für tote Werkzeugpfade, Messung des erzählenden Bestands (SD-03 baut sie für Rahmenplan und Backlog), `\|`-Maskierung in `bericht.py` | BR | 24.09.2026 | Archiv-2 6 |
| **Freigabe je Konzept und je Q-Frage** — laufend (K6) | alle | — | — |

### 6.2 Vor einem bestimmten Schritt

| Was | Wofür | seit | Bedienweg |
|---|---|---|---|
| **Google-Konto der GbR als Kontoinhaber, Play-Console-Organisationskonto** (25 USD), Identitätsprüfung; Entwicklername und öffentliche Kontaktadresse | 6 Teil C (R65), nach D-U-N-S | 03.09.2026 | `Vorbereitung-Play-Console.md` |
| **Signaturschlüssel bei Play App Signing hochladen, Upload-Schlüssel erzeugen** und außerhalb des Repositoriums verwahren | 6 Teil C, mit dem ersten Track-Release | 03.09.2026 | `Vorbereitung-Play-Console.md` |
| **Demo-Video des Vordergrunddienstes auf echtem Gerät** für die Standort-Deklaration — falls der interne Track sie verlangt (beim Einrichten prüfen) | 6 Teil C | 03.09.2026 | `Vorbereitung-Play-Console.md` |
| **Datensicherheitsformular der Play Console** — setzt die Datenschutzerklärung voraus (6.3) | 6 Teil C | 03.09.2026 | `Vorbereitung-Play-Console.md` |
| **Wear-OS-Uhr für den Gerätetest**, die Wear-OS-Prüfrunde und den Installationstest aus dem Track | 6 Teil C; Prüfliste AR | 31.08.2026 | `Pruefdokument-AR-Android-Runde.md` |
| **Fable-Instanz mit Repositoriumszugriff** für den Review in zwölf Sitzungen; `docs/konzepte/Review-R17.md` als Sammelstelle | vor 12 (R17, R69) | 03.09.2026 | Fahrplan, Schritt 12 |
| **Wahl der Symbole für Handy-App und Web-App** aus dem Entwurf im P7-Konzept (gleicher Hubschrauber, zwei Hintergrundfarben); ein iPhone für den Safari-Nachweis | mit dem Konzept zu 13 (R70) | 03.09.2026 | Fahrplan, Schritt 13 |
| **Drei repräsentative Uhr-Darstellungen benennen; Handy-Screenshots** aus dem Gerätetest mit dem Demo-Konto | mit dem Konzept zu 13 (R72) | 03.09.2026 | Fahrplan, Schritt 13 |
| **Neues NEF-Logo und -Favicon** (Platzhalter liegt) | vor 13 (R71) | 30.08.2026 | `Design.md` 2.5 |
| **Korrigierte Logovorlagen in den Markenfarben** (Nr. 62), SVG und PNG — die vorliegenden tragen die alten Werte; bis dahin passiert am Code nichts | vor 13, mit dem NEF-Logo | 12.09.2026 | `Design.md` 2.5 |
| **Impressums- und Datenschutztext der Installation** über den Editor eintragen (R32); für das Datensicherheitsformular der Play Console schon vor dem ersten Track-Release | vor 13 | 30.08.2026 | Handbuch 11.5 |
| **GitHub: `gen-em/nadoku` anlegen** (öffentlich, AGPL-3.0), Umgebungen `staging` und `produktion` mit Pflichtfreigabe, Zweigschutz; danach dieses Repositorium archivieren | mit dem Umzug in 14 (R68) | 03.09.2026 | Fahrplan, Schritt 14 |

### 6.3 Vor v1.0 oder vor der Öffnung

| Was | Wofür | seit | Bedienweg |
|---|---|---|---|
| **Datenschutzerklärung des Dienstes** — den Text gibt es nicht; die elf Bausteine aus P5b (`docs/rechtstexte/Datenschutz-Ergaenzung-P5.md`) und Handbuch 11.5a werden eingearbeitet, dazu die drei Zeilen darunter | Öffnung (R41) | 16.09.2026 | Archiv-2 6 |
| **Datenschutzerklärung: die Gerätekennung** (Art und Modell beim Koppeln, seit Web 12.9.0) — die Auswertung läuft seit Web 15.3.0, die Nennung steht aus | vor v1.0 | 14.09.2026 | Archiv-2 6 |
| **Datenschutzerklärung: die beiden Kopplungs-Mails** (nach jeder Kopplung mit Gerätebezeichnung, beim Trennen mit Geräte-ID) — die einzige Stelle, an der eine Fremdkopplung auffiele | vor v1.0 | 04.09.2026 | Archiv-2 6 |
| **Datenschutzerklärung: Photon (`photon.komoot.io`) und die vier Kachelanbieter**, dazu die Grenze der Verschlüsselung nach Weg C (Nr. 137, 138) | mit dem Text (R41) | 06.09.2026 | Archiv-2 6; Handbuch 11.5a |
| **Anwaltliche Prüfung der drei Rechtstext-Entwürfe** in `docs/rechtstexte/` (E-P5b-24), mit der 30-Tage-Frist für IP-Adressen (E-P5b-06) und der Rechtsform der GbR; mit S11 eine zweite Runde | vor dem Einspielen (es schaltet das Einwilligungstor scharf), vor Welle 1 | 16.09.2026 | Archiv-2 6 |
| **MDR-Abgrenzung nach R41 vorziehen:** vor der Produktionsfreigabe (Welle 1); für den internen Track beim Einrichten prüfen | R41, R65 | 03.09.2026 | Archiv-2 6 |
| **Betriebsakte der eigenen Installation ausfüllen** (Hoster, Domain, Mail, Aufsichtsbehörde, zweiter Admin, Ablageort des Wiederanlaufpakets, Play Console) — außerhalb des Repositoriums | R41, R72 | 03.09.2026 | Archiv-2 6 |
| **Wellenplan der Öffnung** | Betriebsübergang | 03.09.2026 | Archiv-2 6 |
| **Play-Store-Beitrittslink des internen Tests** für die Karte „App installieren" (Konstante `PLAY_TEST_URL`) | S8 AP6, vor der Produktionsfreigabe | 06.09.2026 | Archiv-2 6 |
| **Adresse der Uhr-App im Connect-IQ-Store**, falls veröffentlicht (Konstante `CONNECT_IQ_URL`) | S8 AP6 | 06.09.2026 | Archiv-2 6 |
| **Prüfliste S2** (12 Punkte), darunter die **Probe-Wiederherstellung der ganzen Installation** auf einem Wegwerf-Webspace — wichtigster offener Punkt, blockiert nichts; danach halbjährlich | S2 | 01.09.2026 | `erledigt/Pruefdokument-S2-Mengen-Spuren-Sicherung.md` |
| **Zugangsdaten je eines echten FTPS- und SFTP-Ziels; ein Klick auf „Verbindung prüfen"** (FTP fällt seit 10c AP8) | S2 | 01.09.2026 | Archiv-2 6 |
| **Bestätigung, dass SMTP auf Produktiv eingerichtet ist** | S2 | 01.09.2026 | Archiv-2 6 |
| **Sichtprüfung in WebKit und Firefox** (Symbole am Dateiverweis) | P3 | 30.08.2026 | Archiv-2 6 |
| **Bilderlauf für die zweite Logo-Wahl; Autosuche gegen den echten Photon; Bedienzustände** | S3 | 02.09.2026 | Archiv-2 6 |

### 6a. Staging einrichten — die zehn Schritte

Die Nummern sind gebunden (E-KH-21): `auslieferung.yml` nennt sie in seinen
Fehlermeldungen. Die Kette richtet nichts ein und legt `config.php` nie an.
Werdegang und Kästen: `Rahmenplan-Archiv-2.md` 6a.

| # | Schritt | Wo | Stand |
|---|---|---|---|
| 1 | Subdomain `staging-nadoku.gen-em.org` mit eigenem Verzeichnis, HTTPS | Hoster | ☑ 20.09.2026 |
| 2 | Leere Datenbank samt eigenem DB-Nutzer | Hoster | ☑ 20.09.2026 |
| 3 | FTPS-Konto, das nur das Staging-Verzeichnis sieht | Hoster | ☑ 20.09.2026 |
| 4 | Umgebung `staging`: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`; Variablen `FTP_ZIELPFAD`, `FTP_STATE_PFAD`, `STAGING_URL` | GitHub | ☑ 21.09.2026 (`FTP_STATE_PFAD` mit Kette II/AP6) |
| 5 | Push auf `main` — ab hier synchronisiert die Kette | — | ☑ 21.09.2026 (17 Schritte in 49 s) |
| 6 | `install.php` im Browser: `config.php`, BetreiberIn, `install.lock`; eigener Serverschlüssel und Server-Anteil | Browser | ☑ 20.09.2026 |
| 7 | `config.php`: `smtp` auf `staging@gen-em.org`, `mail.betreff_praefix` `[Staging]`; dazu `app.umgebung` (6.1) | FTP | ☐ Z7 |
| 8 | Demo-Konto aus der Fixture anlegen (Verwaltung → Demo-Konto) | Browser | ☐ Z7 |
| 9 | Eigenes SFTP-Sicherungsziel für Staging | Anwendung | ☐ Z7 |
| 10 | `JOBS_TOKEN` als Environment secret der Umgebung `staging` | GitHub | ☑ 20.09.2026 |

| Variable | Staging (lima-city) | Produktiv (Plesk) |
|---|---|---|
| FTP-Wurzel des Kontos / `FTP_ZIELPFAD` | `/`, eingesperrt / `/` | `/`, eingesperrt / `/` |
| `FTP_STATE_PFAD` | `../.deploy-state-staging.json` | `../.deploy-state-produktion.json` |
| `STAGING_URL` / `PRODUKTION_URL` | `https://staging-nadoku.gen-em.org` | `https://nadoku.gen-em.org` (= `WACHE_BASIS`) |
| Anwendungswurzel | `…/nadoku-staging` | `…/nadoku-produktion` |

### 6b. Zweigschutz für `main`

Gesetzt seit dem 21.09.2026 (PK-M1), „Require branches to be up to date" seit
dem 23.09.2026 (TB-M1). Die drei Lagen des Riegels: `Pruefablauf.md` 2.3. Die
Maske des Rulesets „Main Protect" und die vier Fallen: `Rahmenplan-Archiv-2.md` 6b.

## 7. Programmentscheidungen — Register

R1 bis R50 im Volltext: `Rahmenplan-Archiv.md` 3; R51 bis R85:
`Rahmenplan-Archiv-2.md` 7 oder das genannte Konzept. Neue Entscheidungen
werden hier kompakt angehängt; **der Statussatz wird ersetzt, nicht ergänzt**
(E-SD-12). Nummern werden nie neu vergeben.

| Nr. | Kern | Status | Volltext |
|---|---|---|---|
| R1 | Rahmenplan plus Phasenkonzepte statt eines Großdokuments | gilt; seit Fassung 16 mit Archiv (R51), seit Fassung 125 mit zweitem Archiv und Verlauf-Datei (Konzept SD) | Archiv 3 |
| R2 | Phasenfolge P0 → … → P6 mit Zwischenpaketen | überholt durch Abschnitt 3 | Archiv 3 |
| R3 | Luftbegriffe nur ersetzen, wo sie Allgemeines meinen; Luftfahrt-Fachfelder bleiben | gilt; Wortliste in Konzept P2, 5; Prüfmittel R28 | Archiv 3 |
| R4 | Referenzdatensatz wird generiert und über reguläre Wege eingespielt | erledigt (P1); seit 9d 21 Diensttage und 103 Einsätze in den Quelldaten, 106 im Bestand | Archiv 3 |
| R5 | Gespeicherte Namen bleiben; Ausnahmeliste in P7 beschließen (R71) | gilt; Liste zugeliefert und leer | Archiv 3 |
| R6 | Backlog-Zuordnung (alt) | überholt durch die Kopfzeilen im Backlog (Abschnitt 5) | Archiv 3 |
| R7 | Ordnerumbau vor P3 | gegenstandslos (E-A6-12) | Archiv 3 |
| R8 | Gründerfarben präsenter | erledigt in P3 (`Design.md`) | Archiv 3 |
| R9 | Registrierung in drei Betriebsarten plus Sicherheitspaket | erledigt in 10b (E-P5b-01, -13, -14): Vorgabe „nur auf Einladung", Einstellung seit Web 20.16.5, `registrieren.php` seit 20.22.0; Wellen (R41) fahren darüber | Archiv 3 |
| R10 | Rollen- und Sichtbarkeitsmodell, auch was der Admin nicht kann | erledigt in P5 (R38, R75; Support-Rolle und Tor je Handlung mit 10c AP4) | Archiv 3 |
| R11 | Kein Migrationspfad; v1.0 liest die 7.x-edbak genau einmal; Referenzdatei liegt | gilt; Abnahme in P8 (R71); seit R60 über ein Wegwerf-Formular | Archiv 3 |
| R12 | Weitere Clients: Basisfähigkeit, Vertragsreview in P7 (R71) | gilt; Payloads und Texte erledigt; Vertragsabschnitt 1a seit S5 nach E-R49-7 | Archiv 3 |
| R13 | Versionshistorische Kommentare am v1.0-Schnitt ersetzen | gilt, P6 — Kommentardurchgang des R17-Reviews (R69) | Archiv 3 |
| R14 | Konzepte mit Fable, mechanische Pflege ohne | gilt | Archiv 3 |
| R15 | Changelog ab v1.0 als Stichpunkte | gilt, P7 (R71) | Archiv 3 |
| R16 | Doku-Neufassung zu v1.0 mit Screenshots; Anforderungsgespräch vorher | gilt; Anforderungen R72, Umsetzung P7 | Archiv 3 |
| R17 | Bug- und Sicherheitsreview mit Fable vor v1.0 | gilt, Eingang von P6; Umfang und Form nach R69 | Archiv 3 |
| R18 | Konzept im Projektraum, Umsetzung in Claude Code | gilt | Archiv 3 |
| R19 | Mengenbremse `ingest.php`: Grundsatzfrage und vier Randbedingungen | erledigt: Grundsatzfrage ja (E-P5a-01, 15.09.2026), gebaut in P5a AP7 | Archiv 3 |
| R20 | Sofortpaket Nr. 22 (Altersfeld maskieren) | erledigt (Web 7.2.1) | Archiv 3 |
| R21 | Backlog-Zuordnung nach P0 | überholt durch die Kopfzeilen im Backlog (`csrf_check` ist Nr. 67) | Archiv 3 |
| R22 | Papierkorb in beiden Sicherungen | erledigt (S1, Web 8.0.0) | Archiv 3 |
| R23 | Zwischenpaket S1 | erledigt | Archiv 3 |
| R24 | Regressionspflicht: beide Kreisläufe je Phase, 0 unerklärt | gilt, dauerhaft | Archiv 3 |
| R25 | Demo-Konto dauerhaft, einzige E2E-Ausnahme; auf der Kontoseite gesperrt | gilt; in 10b umgesetzt (E-P5b-07, Web 20.18.0: abschaltbar ist die Anmeldung, nicht das Konto); P6 prüft die Konstruktion, P7 bringt das neue Passwort | Archiv 3 |
| R26 | Backlog-Zuordnung nach P1 | überholt durch die Kopfzeilen im Backlog | Archiv 3 |
| R27 | Prüfmittel Wiederherstellungsprobe und Papierkorb-Mischfall | gilt, dauerhaft | Archiv 3 |
| R28 | Prüfmittel Wortliste | gilt, dauerhaft | Archiv 3 |
| R29 | Uhr-Umbenennung in P6 | erledigt vorzeitig (R48, Uhr 2.0.0) | Archiv 3 |
| R30 | Nacharbeit zu P2 statt Backlog | erledigt | Archiv 3 |
| R31 | Support-Adresse konfigurierbar (P5), Namensbeispiele raus (P6), Farbnamen bleiben | gilt; Support-Adresse erledigt (P5), Namensbeispiele im Review (R69) | Archiv 3 |
| R32 | Impressum und Datenschutz als editierbare Seiten | erledigt in P3; seit 10c AP9 als eigene Seite „Rechtstexte" mit Vorschau | Archiv 3 |
| R33 | Servicemodell mit Abonnements | gilt, P5 | Archiv 3 |
| R34 | Zwischenpaket S2 | erledigt | Archiv 3 |
| R35 | Prüfmittel Messstand | gilt, dauerhaft; aus der Kette gestrichen (P5a AP9, Nr. 206), läuft örtlich vor einer Auslieferung | Archiv 3 |
| R36 | Zielbild Dienstbetrieb, keine Telemetrie, Hosting-Entscheidung vor P5 | gilt; Hosting entschieden 15.09.2026 (R81) | Archiv 3 |
| R37 | Konto-Lebenszyklus und Registrierungs-Sicherheitspaket (elf Punkte) | erledigt: (8), (9) in P5a; (1) bis (7), (10), (11) in 10b (Web 20.17.0 bis 20.24.0); der Proof-of-Work aus (4) ist nicht gebaut (Nr. 228) | Archiv 3 |
| R38 | Support-Rolle, Admin-TOTP, Audit, Dashboard im Minimalumfang | erledigt mit 10c (Web 20.38.0 bis 21.1.1; E-P5c-13 bis -18, -41, -42, -44); „Gerät merken" erst mit Nr. 242 in Schritt 18 | Archiv 3 |
| R39 | Zentrale Stammdaten entfallen; Regionen-Modell verworfen | erledigt mit 10c AP8 (Web 21.0.0): jeder Stammdatensatz gehört einem Konto, `user_bases` ist fort; Regionen als Nr. 71 festgehalten | Archiv 3 |
| R40 | Deploy-Umbau: Staging ab P5, Neuaufsetzen am P8-Schnitt, CI-Prüftor, Torwächter | (1) und (2) erledigt (Web 20.4.0, präzisiert durch R67); (3) und (4) in P8 | Archiv 3 |
| R41 | Recht und Betreiberorganisation vor der Öffnung; Öffnung in Wellen | gilt; Prüfung in P8 (R71); MDR-Abgrenzung vor Welle 1 (R65); Betreiberhandbuch generisch mit Notfall-FAQ (R72) | Archiv 3 |
| R42 | Gerätekennung beim Koppeln | erledigt: Uhr-Seite 1.9.0, Speicherung Web 12.9.0 (S6), Auswertung Web 20.47.0 (10c AP7 — Geräteverteilung und Herkunft je Einsatz) | Archiv 3 |
| R43 | Zwischenpaket S3 | erledigt | Archiv 3 |
| R44 | Inhaltsschlüssel führt eine Inaktivitätsfrist wie die Sitzung | erledigt (Web 12.9.0, S6) — als Aufräumen; der Dialog kommt vom tabweisen `sessionStorage` und steht im Handbuch | Archiv 3 |
| R45 | Zwischenpaket S4 mit E-R45-1 bis -13 | Schritt 1 und Schritt 6 Teile A bis C gemergt (02./04.09.2026); offen Android 1.0.0 (E-R45-7, Teil C); E-R45-6 ersetzt durch R65 | Archiv 3 |
| R46 | Keine Apple Watch; P7 entfällt | gilt | Archiv 3 |
| R47 | Garmin-Uhr-Auslieferung vorgezogen | erledigt (Uhr 1.10.1 bis 1.11.1, Web 9.15.0) | Archiv 3 |
| R48 | Uhr heißt NAdoku, echte Anwendungs-ID | erledigt (Uhr 2.0.0) | Archiv 3 |
| R49 | Zwischenpaket S5 „Kopplung umgekehrt" mit E-R49-1 bis -8 | erledigt (Schritte 3 und 5; Web 13.0.0 bis 13.2.0, Uhr 3.0.0) | Archiv 3 |
| R50 | „Sicherung" wird „Backup", in einem Zug, nach S3 | erledigt (S7, Web 12.9.3 und 12.9.4) | Archiv 3 |
| R51 | Rahmenplan in zwei Dateien: Steuerung und wörtliches Archiv | gilt; seit Fassung 125 drei Dateien und zwei Archive (Konzept SD, E-SD-01, -06) | Archiv-2 7 |
| R52 | Kennungen bleiben; S6 und S7 für die beiden R-Pakete; der Fahrplan trägt die Reihenfolge | gilt | Archiv-2 7 |
| R53 | P4 aufgelöst; Reste als Backlog-Runde ohne Konzept | gilt; Runden 1 bis 3 erledigt, Runde 4 ist Schritt 17 | Archiv-2 7 |
| R54 | R-Einträge nur als Kurzregister, Volltext im Archiv | gilt; seit Fassung 125 Kern und Status je höchstens 160 Zeichen (E-SD-12) | Archiv-2 7 |
| R55 | P0-Bedienprüfung und P2-Prüfliste überholt; P2-Punkt 4.1 geht in S5 | erledigt | Archiv-2 7 |
| R56 | S7: Verb „sichern", Symbolname und `admin_sicherungen.php` bleiben | gilt | Archiv-2 7 |
| R57 | Überlappende aktive Diensttage: Hinweis im Browser (Weg c) | erledigt (Web 13.3.0, Handbuch 4.5b); der 404 des Knopfes mit Nr. 148 behoben (Web 15.5.2) | Archiv-2 7 |
| R58 | Android-Bedienhöhe 48 dp in beiden Modulen; `CLAUDE.md` 5 ergänzen | erledigt (S4-Merge) | Archiv-2 7 |
| R59 | Vor v1.0 ein Planungsgespräch; Ergebnis sind die Konzepte P6–P8 (R71) | erledigt: vorgezogen und als R65 bis R73 entschieden (03.09.2026, Schritt 11) | Archiv-2 7 |
| R60 | Ab v1.0 keine Rückwärtskompatibilität; Neuaufsetzen; eine ältere Sicherung einmal über ein Wegwerf-Formular | gilt; Update-Weg entschieden (R66) | Archiv-2 7 |
| R61 | Zwischenpaket S8 „Einstellungen, Administration und Wartung" mit Konzept und Mockups | erledigt (Schritt 7, Web 15.0.0 bis 15.5.1) | Archiv-2 7 |
| R62 | Konzeptablage `docs/konzepte/` mit Lebenszyklus: Statusblock und Push je Paket; Erledigt-Zeile und Löschung nach der Freigabe; Prüfdokument bis zur Prüfliste | gilt; Regel in 2.2; Erledigt-Zeile seit Fassung 125 als Tabellenzeile, Prüfzahlen im Prüfdokument (E-SD-10) | Archiv-2 7 |
| R63 | Android-App kennt nur `nadoku.gen-em.org`; Adressfeld, Adress-QR und Adresswahl entfallen; Handy-App „Gen-EM NAdoku", Wear-Uhr „NAdoku" | erledigt (Android 0.11.0, Nr. 84 bis 86) | Archiv-2 7 |
| R64 | Herkunft und Gerät je Einsatz: Momentaufnahme an `missions` und `rest_segments`, `origin` mit sechs Werten, sichtbar im Dashboard | erledigt: Speicherung Web 14.0.0 (Nutzlast 9), Dashboard Web 20.47.0 (10c AP7); die Kachel Nr. 88 ist am 12.09.2026 verworfen | Archiv-2 7 |
| R65 | Store-Verteilung in zwei Stufen: Organisationskonto (D-U-N-S), interner Test-Track ab Schritt 6, Produktionsfreigabe als Welle 1; Play App Signing | gilt; Konto und Track offen (6.1, 6.2), Produktion im Betriebsübergang | `Konzept-Planung-v1.0.md`, E-PV-1 |
| R66 | Update-Weg ab v1.0: keine Selbstprüfung, kein Selbst-Update, Produktion nur auf Auslösung; Register beginnt bei v1.0 neu | gilt; Ausgeführte seit Web 20.39.0 im Protokoll (Q-P5c-53); `git pull` auf dem Server verworfen (E-KH-02); Neubeginn in P8 | `Konzept-Planung-v1.0.md`, E-PV-2 |
| R67 | Auslieferungskette: `main` → Staging, Tag → Produktion nach Pflichtfreigabe und Backup-Tor; Prüftor in Stufen; Rollback = voriger Tag | gilt; gebaut P5a, gehärtet Kette II (E-KH-01 bis -30), erster grüner Produktivlauf M1 am 21.09.2026 (`web-v20.26.3`); Rest bei PK | `Konzept-Planung-v1.0.md`, E-PV-3; Konzept Kette II |
| R68 | Ein Repositorium, frisch, öffentlich: `gen-em/nadoku` (AGPL-3.0) ohne Historie; Altrepositorium archiviert und verweist | gilt; Umzug in P8 mit dem Neuaufsetzen | `Konzept-Planung-v1.0.md`, E-PV-4 |
| R69 | R17-Review liest alles in zwölf Stücken mit Fable; Stück 1 Bedrohungsmodell; Funde in `Review-R17.md`, kritisch → Sofortpaket | gilt; Eingang von P6 | `Konzept-Planung-v1.0.md`, E-PV-5 |
| R70 | Web-App-Manifest allein, kein Service Worker; in P7 mit der Umbenennung; Nachweis am S24 und am iPhone | gilt; P7 | `Konzept-Planung-v1.0.md`, E-PV-6 |
| R71 | Drei Phasen vor v1.0: P6 Review, P7 Gesicht, P8 Schnitt — nacheinander, je ein Konzept | gilt; Schritte 12 bis 14 | `Konzept-Planung-v1.0.md`, E-PV-7 |
| R72 | Doku-Neufassung: vier Dokumente nach Zielgruppe plus Vertrag; Handbuch als HTML mit dem Release; höchstens ein Drittel des Umfangs | gilt; Umsetzung P7 | `Konzept-Planung-v1.0.md`, E-PV-8 |
| R73 | Problemsammlung als S9 (Schritt 8), Konzept mit Fable | erledigt (S9, Web 15.7.0 bis 19.1.1) | `Konzept-Planung-v1.0.md`, E-PV-9 |
| R74 | Ordnungsprinzip: jede Funktion hat genau einen Ort (wer, woran, wie oft); Ausnahmen eine Ebene tiefer; wer baut, benennt den Ort (K1) | gilt, dauerhaft; (5) seit 23.09.2026: je Karte höchstens ein Satz, Erklärtext im Handbuch (E-P5c-06, -49) | Archiv-2 7 |
| R75 | Rolle „BetreiberIn": dritte Rolle, kann alles, was ein Admin kann, sieht allein den Block Betrieb; das letzte Konto ist unlöschbar | erledigt (S8 AP1); vierte Rolle Support seit 10c AP4 | Archiv-2 7 |
| R76 | Bedienhöhe in zwei Stufen: 44 px, am Zeigergerät ab 1024 px 36 px; Android bleibt bei 48 dp (R58) | erledigt (S8 AP7, Web 15.5.0) | Archiv-2 7 |
| R77 | Drei Backup-Begriffe: Backup, Konto-Backup, Komplett-Backup; Verben sichern, einspielen, wiederherstellen | erledigt (S8 AP2, AP3) | Archiv-2 7 |
| R78 | Krypto-Review vorgezogen; acht Beschlüsse: Sofortpaket 9a, S10, Zweitfaktor, CSP, Weg B als S11, Deploy-Tor, Photon-Schalter, Ersetzfenster 72 h | gilt; 9a, S10, Zweitfaktor (10c AP5, QR-Code E-P5c-41) und CSP (P5a AP4) erledigt; Weg B offen als Schritt 12a | `Review-Krypto-Sicherheit.md`, `Vorbereitung-Sicherheitspaket.md` |
| R79 | Geocoding abschaltbar je Installation und je Konto; Dienstadresse als Einstellung | erledigt (S9 AP2) | Archiv-2 7 |
| R80 | Ob ein Rettungsmittel Fähigkeiten führen darf, entscheidet sein Typ mit (`veh_caps_erlaubt()`) | erledigt (9d AP0, Web 20.3.0); Übersichten seit Schritt 15 nach Betriebsart und Fähigkeit (E-ZE-31); Nr. 198 nicht umgesetzt | Archiv-2 7 |
| R81 | Plattformprofil hosterneutral in zwei Stufen (Muss, Empfohlen); `install.php` und Status prüfen es | gilt; gebaut in P5a AP2 | `Vorbereitung-P5-Plattformprofil.md`, E-PP-01 bis -04 |
| R82 | Schritt 10 (P5) in drei Teilkonzepten 10a, 10b, 10c mit je eigener Freigabe und eigenem Prüfdokument | erledigt (10a PR #50, 10b PR #57, 10c PR #89) | Archiv-2 7 |
| R83 | Zentralisiert wird beim zweiten echten Verbraucher; Bibliothek statt Seite | gilt; Schritt 15 erledigt, das Register `tools/zaehlung/` misst es in Stufe 1 | Archiv-2 7 |
| R84 | Kein Schritt der Auslieferungskette geht ungeprobt auf Produktiv; wer einen Schritt hinzufügt, sagt, wodurch er geprobt wird | gilt | `Konzept-Kette-Haertung.md`, E-KH-17 |
| R85 | Die Kette des Tags N spricht mit dem Server der Fassung N−1; ein unbekanntes Feld ist `unbekannt`, nie eine erfundene Null | gilt | `Konzept-Kette-Haertung.md`, E-KH-19 |

## 8. Erledigt

Eine Zeile je Schritt; Prüfzahlen stehen im Prüfdokument, der Volltext in
`Rahmenplan-Archiv-2.md` 8. `5e501ae` ist der letzte Stand der Dokumente, die
die Aufräumfassung vom 24.09.2026 gelöscht hat (Archiv-2, Verlaufszeile 110).

| Kennung | Versionen | Datum · PR | Konzept (letzter Commit) | Prüfdokument | Kern |
|---|---|---|---|---|---|
| P0 — Aufräumen | Web 7.1.0–7.2.0 | 23.08.2026 · PR #1, #2 | nicht im Repositorium | — | Toter Code raus, Seitenhülle an einer Stelle, Stylesheet entdoppelt; Prüfmittel `tools/stilvergleich/`; 43 Restfunde als Nr. 21 |
| Sofortpaket Nr. 22 | Web 7.2.1 | 23.08.2026 · PR #3 | — (R20) | `erledigt/Pruefung-Sofortpaket-22.md` | Altersfeld in den Einsatztabellen maskiert — Skriptausführung über den Import war möglich |
| P1 — Referenzdatensatz und Demo-Konto | Web 7.2.2–7.3.1 | 23.08.2026 · PR #4 | `erledigt/Konzept-P1.md` | `erledigt/Pruefdokument-P1.md` | Generierter Datensatz (16 Diensttage, 87 Einsätze), Demo-Konto mit Reset, Kreislaufvergleich als Regressionsnetz |
| S1 — Sicherung und Import | Web 8.0.0 | 24.08.2026 · PR #5 | `erledigt/Konzept-S1-Sicherung-Import.md` | `erledigt/Pruefdokument-S1-Sicherung-Import.md` | Papierkorb in beiden Sicherungen, CSV-Kreislauf auf 0, Prüfmittel Wiederherstellungsprobe (R27) |
| P2 — Terminologie | Web 8.0.1 | 25.08.2026 · PR #6 | `erledigt/Konzept-P2-Terminologie.md` | `erledigt/Pruefdokument-P2-Terminologie.md` | Wortlaut Land/Luft neutral in Oberfläche und Doku; Prüfmittel Wortliste (R28) |
| P3 — Oberflächen-Redesign | Web 9.0.0–9.14.0 | 30.08.2026 · PR #7, #8, #10 | `erledigt/Konzept-P3-Oberflaeche.md` | `erledigt/Pruefdokument-P3-Oberflaeche.md` | Mobil-first-Oberfläche mit `Design.md`, Tabler-Symbole vendoriert, Fußzeile mit Rechtstexten (R32), Kontoseite als Drehscheibe |
| Uhr-Auslieferung | Uhr 1.8.1–2.0.0, Web 9.15.0 | 31.08.2026 · PR #9, #11 | kein Konzept (R47, R48) | — | 99 Geräte, Gerätekennung beim Koppeln (R42), Name NAdoku mit echter Anwendungs-ID; Prüfmittel `tools/uhr-pruefstand/` |
| S2 — Mengen, Spuren, Sicherung | Web 10.0.0–12.2.0 | 01.09.2026 · PR #15 | `erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md` | `erledigt/Pruefdokument-S2-Mengen-Spuren-Sicherung.md` (Prüfliste offen, 6.3) | Spurpunkte als Blob SPUR1 über `spur_lib.php`, Container Fassung 4, Sicherungsziele, Komplettsicherung, Messstand (R35) |
| Zweite Rückmeldungsrunde | Web 12.2.1 | 01.09.2026 · PR #16 | — | — | Dateifeld mittig, Dateiname in den Abschlussmeldungen, Warnzeichen für `warn`; Nr. 56 |
| S3 — Oberflächen-Nacharbeit | Web 12.2.2–12.4.2 | 02.09.2026 · PR #17 | `erledigt/Konzept-S3-Oberflaechen-Nacharbeit.md` | `erledigt/Pruefdokument-S3-Oberflaechen-Nacharbeit.md` | Abstandsregel in `Design.md` 6, Ortsfeld sucht beim Tippen, Demo-Konto auf der Kontoseite gesperrt, Markerversatz behoben |
| 1 — S4 Merge | Web 12.8.0, Android 0.7.7 | 02.09.2026 · PR #22 | `Konzept-S4-Handy-Uhr-Client.md` (bleibt für Schritt 6) | Prüfliste S4 abgearbeitet 24.09.2026 | Der S4-Zweig auf `main`: Android-Handy und Wear-Uhr, Schneidewerkzeug, GPX-Import |
| 2 — S6 Gerätekennung und Schlüsselfrist | Web 12.9.0–12.9.2 | 02.09.2026 · PR #23, #24 | keins (R42, R44) | — (Abnahme offen, 6.1) | Gerätekennung in drei Spalten, 325 Teilenummern auf 173 Modelle, Inaktivitätsfrist des Inhaltsschlüssels (R44) |
| 3 — S5 Konzept | — | 03.09.2026 | Konzept S5, gelöscht 24.09.2026 (`5e501ae`) | — | E-R49-1 bis -8 ausgearbeitet; Freigabe mit E-S5-32 bis -47 |
| S7 — Backup-Begriff (Schritt 4) | Web 12.9.3–12.9.4 | 03.09.2026 · PR #27 | `Umstellung-Backup.md`, gelöscht (`7057e7b`) | gelöscht 24.09.2026 (`5e501ae`) | „Sicherung" heißt überall „Backup" (642 → 167 Stellen in `server/`); dabei Nr. 89: das geplante Komplett-Backup lief nie |
| S5 — Kopplung umgekehrt (Schritt 5) | Web 13.0.0–13.2.0, Uhr 3.0.0; Paket E Android 0.8.0–0.10.2 | 03.09.2026 · PR #28, #29, #31 | drei Konzepte, gelöscht (`5e501ae`) | gelöscht (`5e501ae`) | Das Gerät zeigt den Code, das Web nimmt ihn, das Gerät bestätigt; Geräteschlüssel als SHA-256 (E-S5-42); Wartungsmodus (Paket W); Ortungswächter und Dienstende der Handy-App |
| S4-Rest, Teile A bis C (Schritt 6) | Web 13.3.0–14.2.2, Android 0.11.0–0.13.0 | 04.09.2026 · PR #33 | Konzept S4 bleibt (Teil C offen); Konzept R64 gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Kopplungsmodul auf 1a, feste Server-Adresse (R63), Herkunft und Gerät je Einsatz (R64, Nutzlast 9), Play-Console-Vorbereitung |
| Uhr-Korrekturen vom Gerät | Uhr 3.0.1–3.0.2 | 05.09.2026 · PR #34 | — | — | Sync-Seite mit UP verlassen; `_fremdKey` blockierte START nach der Sync-Seite |
| S8 — Einstellungen, Verwaltung und Betrieb (Schritt 7) | Web 15.0.0–15.5.1 | 06.09.2026 · PR #35 | gelöscht (`fc470b0`) | gelöscht 24.09.2026 (`5e501ae`) | Drei Blöcke Einstellungen, Verwaltung, Betrieb; Rolle BetreiberIn (R75); Wartungsseite aufgelöst; zwei Bedienhöhen (R76); Ordnungsprinzip (R74) |
| 9a — Sofortpaket Sicherheit | Web 15.6.0, Android 0.14.0; dazu Uhr 3.1.0, Android 0.15.0 | 08.09.2026 · PR #37 | `Vorbereitung-Sicherheitspaket.md` (bleibt, R78) | gelöscht 24.09.2026 (`5e501ae`) | Rundenzahl 600 000, Login-CSRF, Ersetzfenster 72 h, Weg C, Integritätswache; Android: Klartextverbot im Release, Absenderprüfung, Prüfsumme der Gradle-Verteilung |
| S9 — Einsatzbearbeitung und Rettungsmittel (Schritt 8) | Web 15.7.0–19.1.1 | 10.09.2026 · PR #38, #39 | gelöscht 24.09.2026 (`5e501ae`; letzter inhaltlicher Commit `3e9849c`) | gelöscht 24.09.2026 (`5e501ae`) | Geocoder mit Kontoschalter, Rettungsmittel mit Typ und Kurznamen, Standort zuerst, Notizen des Einsatzes verschlüsselt (Nr. 109), Schloss und Klartext-Hinweis |
| Backlog-Runde 1 (Schritt 9) | Web 19.1.2 | 12.09.2026 · PR #40 | keins | — | Sieben Punkte (38, 93, 97, 151, 153, 156, 167); Nr. 171: im Wartungsmodus kam niemand mehr herein |
| Backlog-Runde 2 (Schritt 9) | Web 19.2.0–19.3.0 | 12.09.2026 · PR #41, #42 | keins | gelöscht 24.09.2026 (`5e501ae`) | Fünf Punkte (118, 119, 120, 125, 126); Nr. 57 vermessen, Nr. 174 gefunden |
| Backlog-Runde 3 (Schritt 9) | Web 19.3.1 | 13.09.2026 · PR #43 | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Sieben Punkte; Gruppe „Zusagen" in der Vollständigkeitsprüfung; der Prüfstand der Arbeitsumgebung entstand hier |
| Mockup-Runde 9c | Web 19.4.0–19.6.0 | 14.09.2026 · PR #44 | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Nr. 41, 42, 45, 124: Importvorschau, Chips mit Symbolen, dritte Kartengröße, Aktionsblatt; drei Motoren für Bilderlauf, Klickprobe und Stilvergleich (Nr. 183) |
| S10 — Sicherheit (Schritt 9b) | Web 19.7.0–20.2.1 | 14.09.2026 · PR #45, #46 | gelöscht (`a00f6b5`) | gelöscht 24.09.2026 (`5e501ae`) | Server-Anteil am Datenschlüssel (`kdf_anteil`, HKDF), Schlüsselblatt, Adminpakete versiegelt, `ftp` abgeschafft; Betriebsposten in 6.1 |
| Demo-Ausbau 9d | Web 20.3.0 | 15.09.2026 · PR #48 | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Referenzbestand 16 → 21 Diensttage, 88 → 106 Einsätze mit den S9-Typen im Betrieb; R80 |
| P5a — Kette und Fundament (Schritt 10a) | Web 20.4.0–20.15.2 | 16.09.2026 · PR #49, #50 | gelöscht (`bcbb04f`) | gelöscht 24.09.2026 (`5e501ae`) | `main` deployt auf Staging, ein Tag mit Pflichtfreigabe und Backup-Tor auf Produktiv; Plattformprüfung, Torwächter, CSP-Kopfzeilen, Mail-Warteschlange, Mengenbremse |
| P5b — Konto und Registrierung (Schritt 10b) | Web 20.15.3–20.24.0 | 18.09.2026 · PR #57 (danach #58, #59, #60) | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Protokoll-Schreibweg, Konto-Lebenszyklus in `konto_lib.php`, Selbstregistrierung in drei Betriebsarten, Einwilligungen, Selbstlöschung, Handbuch ohne Anmeldung, Erststart |
| 16 — Sitzungsablage | Web 20.26.0 | 20.09.2026 · PR #61, #62 | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Die Anwendung legt `.sitzungen/` selbst an (`0700`), Rückfall mit Anzeige, achter Schutzlistenpfad der Auslieferung |
| 15 — Zentralisierung | Web 20.27.0–20.37.0 | 23.09.2026 · PR #74 | gelöscht 22.09.2026 (`1c21bea`) | gelöscht 24.09.2026 (`5e501ae`) | Zehn Wege mit genau einem Eingang (`konfig()`, `sitzung_starten()`, `db_transaktion()`, `mf_spalten()`, `EdApi`, `EdFormat` …); Register `tools/zaehlung/` als Stufe-1-Schritt (R83) |
| RP — rote Proben der Nebenstufe | nur Werkzeug | 23.09.2026 · PR #83 | gelöscht 24.09.2026 (`5e501ae`) | gelöscht 24.09.2026 (`5e501ae`) | Neun von 36 Proben waren rot, keine ein Fehler der Anwendung; Nebenstufe 36 grün in 1 239 s (Nr. 292) |
| TB — Produktionstor vergleicht Baum statt Commit | nur Werkzeug | 23.09.2026 · PR #77 | gelöscht (`a72c7df`) | gelöscht 24.09.2026 (`5e501ae`) | Ein Tag wartet nicht mehr auf eine zweite Messung; Job `Schon gemessen?` auf `main`; „Require branches to be up to date" (TB-M1, Nr. 285) |
| BR — Bestandsriegel | nur Werkzeug | 24.09.2026 · PR #85, #86 | gelöscht (`d4e96e6`) | `Pruefdokument-BR-Bestandsriegel.md` (P-BR-05, -06, -08, -09 offen) | `tools/quelltext/bestand.py` misst den Werkzeugbestand in elf Regeln; 57 Befunde → 0; `Pruefablauf.md` 6.12 (Nr. 293) |
| P5c — Rollen, Sicherheit, Betriebslage (Schritt 10c) | Web 20.38.0–21.1.3 | 26.09.2026 · PR #89, #90 (Tag offen, 6.1) | gelöscht 25.09.2026 (`ae829e6`; mit Konzept RW) | Prüfdokumente P5c (P-P5c-01 bis -45) und RW (P-RW-01 bis -05) | Umgebungsbanner, Protokoll mit Archiv, Reiter System, Rolle Support, Zweitfaktor mit Rückweg, Health-Endpunkt, Statistik nach R38, Rückbau von R39, Ein-Satz-Regel |

## 9. Pflege dieses Dokuments

- **Rahmenplan schreiben — an vier Anlässen, sonst nicht** (Konzept SD,
  E-SD-15): (1) ein Schritt beginnt oder endet, (2) eine Programmentscheidung
  fällt, (3) die Reihenfolge ändert sich, (4) eine Zuarbeit entsteht oder ist
  erledigt. Arbeitspakete, Befunde, Messungen und Abnahmen stehen im
  Statusblock des Konzepts und im Prüfdokument (`CLAUDE.md` 2, Punkt 5).
- **Keine Berichtigung im Text.** Wer etwas Falsches findet, ersetzt es an
  Ort und Stelle und schreibt in die Verlaufszeile, was falsch war. Kein Satz,
  der einen früheren Wortlaut zitiert, kein Blockquote, kein Werdegang.
- **Der Kopf hält höchstens 15 Zeilen**; die übrigen Decken misst
  `tools/steuerung/` (ab SD-03) in Stufe 1. Jede Änderung ist eine
  Verlaufszeile mit der nächsten Fassungsnummer; der Kopf trägt die Nummer.
- **Der Stand von `main` wird GEMESSEN, nicht fortgeschrieben:** vor jeder
  Fassung `git fetch origin main`, die drei Versionsnummern **auf
  `origin/main`** ablesen (`server/version.php`, `watch/source/Const.mc`,
  `android/version.properties`), die offenen Pull Requests und
  `git log origin/main --merges` ansehen. Ohne `fetch` ist es eine Vermutung.
- **Status** eines Schritts: die Fahrplanzeile (offen · Konzept · freigegeben
  · Umsetzung · gebaut · gemergt · blockiert, Datum, Verweis); nach der
  Freigabe des Abschlusses eine Zeile in Abschnitt 8, die Reste nach 6, der
  Backlog in die Kopfzeilen — und die Fahrplanzeile geht. **Kein Konzept ohne
  Fahrplanzeile** (E-SD-09).
- **Programmentscheidungen** bekommen die nächste R-Nummer in Abschnitt 7;
  der Statussatz wird ersetzt, nicht ergänzt; die Begründung steht im
  Konzept. Nie umnummerieren. **Die Archive werden nicht fortgeschrieben.**
  Backlog-Nummern, Kennungen und R-Nummern bleiben, wie sie sind.

## 10. Änderungsverlauf

Der Verlauf steht in `Rahmenplan-Verlauf.md` (eine Zeile je Fassung ab 125);
die Fassungen 16 bis 124 in `Rahmenplan-Archiv-2.md` 10, die Fassungen 1
bis 15 in `Rahmenplan-Archiv.md`.
