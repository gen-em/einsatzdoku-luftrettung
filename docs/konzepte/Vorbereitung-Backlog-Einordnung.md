# Vorbereitung — Einordnung des Backlogs vor v1.0

**Zielpfad:** `docs/konzepte/Vorbereitung-Backlog-Einordnung.md` — dasselbe
Muster wie `Vorbereitung-P5-Plattformprofil.md`: eine Sammlung von Befund,
Vorschlag und gefallenen Entscheidungen, aus der die Rahmenplan-Fassung und
die Kopfzeilen des Backlogs entstehen. Kein Konzept nach K1, deshalb keine
Fahrplanzeile (E-SD-09); kein Code.
**Grundlage:** `docs/Rahmenplan.md` Fassung 190 (02.11.2026, Stand `main`
`2563fde`, PR #128), `docs/Backlog.md` (63 offene Einträge, höchste Nummer
429 reserviert, nächste freie 430), `Rahmenplan-Archiv-2.md` 3 (Volltext der
Schritte 6, 13, 14 und des Betriebsübergangs), `python3
tools/steuerung/uebersicht.py` (gemessen 02.11.2026).
**Status:** Entwurf, Runde 1 — Befund und Vorschlag; die Fragen in
Abschnitt 5 sind offen. Nichts ist in Rahmenplan oder Backlog geschrieben.
**Modell:** Fable (Konzept, R14).

**Bezeichner:** Befunde heißen **F-BE-nn**, Fragen an die Betreiberin
**Q-BE-nn**, Entscheidungen **E-BE-nn**. Nummern zweistellig, nie wiederverwendet.

---

## 0. Auftrag

Anlass (02.11.2026): „Mit dem Backlog beginnen — er muss vor 1.0 fertig
sein. Überblick, sortiert nach Web / Uhr / Android und nach Bereichen, damit
sichtbar wird, was zusammen abgearbeitet wird; dazu die Backlog-Punkte, die
sich in offenen Rahmenplan-Schritten besser unterbringen lassen. Ziel ist die
Aktualisierung des Gesamtplans mit Einordnung der Backlog-Punkte."

**Was dieses Dokument liefert:** (1) die Matrix aller 63 offenen Punkte nach
Auslieferung und Bereich (Abschnitt 2), (2) die Abweichungen zwischen
Rahmenplan und Backlog-Kopfzeilen (Abschnitt 3), (3) einen Vorschlag für die
neue Zuordnung und für zwei neue Fahrplanschritte (Abschnitt 4), (4) die
Fragen, die vor dem Schreiben zu entscheiden sind (Abschnitt 5).

**Was es nicht liefert:** die Rahmenplan-Fassung selbst und die geänderten
Kopfzeilen — die entstehen nach den Entscheidungen in Abschnitt 5, auf
demselben Zweig, als eine Fassung (Anlass 3 „Reihenfolge ändert sich" und
Anlass 4 „Zuarbeit"; `CLAUDE.md` 2, Punkt 5).

## 1. Befund zur Prämisse „fertig vor 1.0"

**F-BE-01 — Der Rahmenplan sieht keinen leeren Backlog zu v1.0 vor.** Das
Vokabular der Kopfzeilen kennt `nach v1.0` als Ziel (12 Einträge tragen es,
drei davon mit Stand `nicht umsetzen`), dazu `Pflegeaufgabe` (7) und den
Stand `nur auf Anlass` (3). Schritt 13 führt ausdrücklich „Backlog mit
dauerhaften Nummern übernehmen" — der Backlog zieht mit in `gen-em/nadoku`
um (R68). Was vor v1.0 erledigt sein muss, hat bisher niemand als Regel
festgelegt; es ergibt sich aus den Zielen `13`, `14` und `nächste
Backlog-Runde`. **Die Frage, die der Auftrag wirklich stellt, ist deshalb:
Welche der 27 Punkte unter `nächste Backlog-Runde` und welche der 7
Pflegeaufgaben müssen vor v1.0 liegen — und in welchem Schritt?** Abschnitt 4
schlägt eine Antwort vor; Q-BE-01 fragt sie ab.

**F-BE-02 — Die Zählung je Ziel (uebersicht.py, 02.11.2026):**

| Ziel | Einträge |
|---|---|
| 13 — P7 Gesicht v1.0 | 7 |
| 14 — P8 Schnitt | 7 |
| nächste Backlog-Runde | 27 |
| Zuarbeit | 3 |
| Pflegeaufgabe | 7 |
| nach v1.0 | 12 |
| **Summe** | **63** |

## 2. Matrix — Auslieferung und Bereich

Eine Zeile je Punkt. **Ausl.** sagt, welche der drei Zählungen eine
Erledigung hochstuft (`W` Web, `U` Uhr, `A` Android, `K` Kette `.github/`,
`T` Werkzeuge `tools/`, `D` nur Dokumente/Steuerung). **Bereich** ist die
Zusammenfassung, nach der sich Arbeitspakete schneiden lassen. **Ziel** ist
die heutige Kopfzeile; **Vorschlag** die aus Abschnitt 4 (`=` unverändert).

### 2.1 Web — Server und Browser

| Nr. | Ausl. | Bereich | Stand | Ziel | Vorschlag | Kurz |
|---|---|---|---|---|---|---|
| 392 | W | Anmeldung/Schlüssel | offen | nächste Runde | **BR5** | Schlüsselpaar entsteht nur, wer auf der ersten Seite nach der Anmeldung bleibt — Geräte können sonst nicht senden |
| 354 | W | Anmeldung/Protokoll | offen | nächste Runde | **BR5** | `protokoll()` schluckt Deadlock in Transaktion; Passkeys weg bei `totp_abschalten()` |
| 358 | W T | Anmeldung/Notzugang | offen | nächste Runde | **BR5** | Token-Ableitung zweimal; Wache vergleicht die zweite Passwortseite nicht |
| 355 | W | Eingabeprüfung (R83) | offen | nächste Runde | **BR5** | `(string)$_POST[…]` an 109 Stellen → `post_text()` |
| 345 | W | Verwaltung/Installation | offen | nächste Runde | **BR5** | Installationsseite meldet Erfolg ohne Rückgabewert |
| 357 | W | Betrieb/Jobs | offen | nächste Runde | **BR5** | Fehlertext eines Jobs trägt den Klassennamen |
| 346 | W | Oberfläche/Meldungen (R83) | offen | nächste Runde | **BR5** | `patwarn` und Papierkorb-Hinweis noch von Hand |
| 356 | W | Oberfläche/Betrieb | offen | nächste Runde | **BR5** | Zwei Abschnitte der Karte „Schlüssel des Servers" ohne Abstand |
| 361 | W | Oberfläche/Geräte | offen | nächste Runde | **BR5** | APK-Karte unterscheidet Handy und Wear-OS-Uhr nicht (stirbt mit Welle 1) |
| 383 | W T D | Sicherung/Plattform | offen | Pflegeaufgabe | **BR5** | Komplettsiegel rot unter PHP 8.3.6 (Ubuntu 24.04 LTS) — Ursache oder Mindestfassung |
| 342 | W | Oberfläche/Tabellen (Mockup) | offen | nächste Runde | **13** | Einsatztabellen von Suche und Zeitraum rollen am Schreibtisch — Gestaltungsfrage |
| 23 | D | Vertrag | offen | 13 | = | JSON-Vertrag 3.3 nennt `beginn`, kein Schreibweg nimmt es an |
| 62 | W D | Gestaltung/Assets | offen | 13 | = (+ Zuarbeit Vorlagen) | Logodateien mit alten Farbwerten — wartet seit 12.09.2026 auf neue Vorlagen |
| 280 | D | Lizenzen (Zusage) | offen | Pflegeaufgabe | **13** | OpenHikingMap in keiner Lizenzliste — „keine fremde Quelle, die niemand aufgeschrieben hat" |
| 409 | W U A K T | Code-Kommentare (R69) | offen | 13 | = | ~500 erzählende Sätze ohne Kennung, 384 Kennungen in Zeichenketten |
| 46 | W T | Sicherung/Altformat | teilweise | 14 | = (Fahrplanzeile 13 berichtigen) | Adminpakete Fassung 1/2 und `konto.json` lesen noch drei Stellen |
| 187 | W T | Anmeldung/Anhebung | offen | 14 | = | Drei Anhebungswege samt Endpunkten und Prüfmitteln entfernen |
| 324 | W | Sicherung/Altbestand | offen | 14 | = | Einmal-Skript für den eigenen Bestand |
| 252 | W | Datenmodell/Migrationen | offen | 14 | = | 57 Schemaabfragen von Hand — erledigt sich mit R66 (Register neu) |
| 276 | W | Datenmodell | offen | 14 | = | `missions.other_resources` löschen — in die Grundfassung von `schema.sql` |
| 400 | D | Rechtstexte | offen | 14 | = | AVV, Nutzungsbedingungen, Datenschutzerklärung auf den Stand nach S11 |
| 230 | W D | Betrieb/Status | teilweise | Pflegeaufgabe | = | Stand der Wegwerfdomain-Liste auf Betrieb → Status |
| 202 | W | Zentralisierung Paket 6 | nur auf Anlass | Pflegeaufgabe | = (Begründung veraltet) | Beifang — „jeder Anlass träfe Dateien von 18"; 18 ist seit 05.10.2026 fertig |
| 232 | W T | Betrieb/Fristen | nur auf Anlass | nächste Runde | **Pflegeaufgabe** | Fristen der Rückfragen nie im Betrieb abgelaufen; Prüfschritt mit gestellter Zeit |
| 80 | W D | Statistik/Datenschutz | teilweise | Zuarbeit | = | Gerätekennung in der Datenschutzerklärung; `missions.geraet_art` nicht bestellt |
| 37 | W | Mengen | teilweise | nach v1.0 | = | Konto über Jahre — Vorschneiden im Suchindex |
| 50 | W | Sicherung/Versand | offen | nach v1.0 | = | Versand liest je Konto ein Verzeichnis |
| 52 | W | Sicherung/Ziele | offen | nach v1.0 | = | WebDAV als viertes Ziel |
| 55 | W | Sicherung/Komplett | offen | nach v1.0 | = | Kein scharfer Schnappschuss |
| 99 | W | Betrieb/Update | offen | nach v1.0 | = | Fassungsprüfung auf Klick |
| 198 | W | Auswertung | nicht umsetzen | nach v1.0 | = | Windendienste nur luftgebunden |
| 200 | W | Mail | nicht umsetzen | nach v1.0 | = | Bounce-Postfach |
| 351 | W | Zweitfaktor | zurückgestellt | nach v1.0 | = | Passkey allein |

### 2.2 Uhr (Garmin, `watch/`) und Android (`android/`)

| Nr. | Ausl. | Bereich | Stand | Ziel | Vorschlag | Kurz |
|---|---|---|---|---|---|---|
| 154 | A | Sendebericht | offen | 13 | **GR** | `kept_points`, `kept_meta` nicht gelesen — Übergangenes sieht wie Erfolg aus |
| 157 | A | Kopplung | offen | 13 | **GR** | Sackgasse „Schlüssel abgewiesen" / „Gerät trennen" |
| 201 | U A | Antwortauswertung | offen | 13 | **GR** | `Retry-After` auswerten (`Uploader.mc`, `Sendeantwort.kt`) |
| 229 | U A D | Antwortauswertung/Vertrag | zurückgestellt | 13 | **GR** (Clients) + **13** (Vertrag) | 403 `gesperrt` als „abgemeldet"; Handy kennt 403 nicht; Fehlertabelle im Vertrag ohne 403/507 |
| 402 | U | Sync-Seite | offen | nächste Runde | **GR** | Unterer Block überlappt ab vier Zeilen (Venu 3s) |
| 96 | U A | Wartungsmodus | offen | nach v1.0 | = | Clients sagen nicht, dass gewartet wird |
| 90 | T | Uhr-Prüfstand | offen | nach v1.0 | = | Simulator kann keinen Verbindungsabriss herstellen |
| 100 | K | Play-API | offen | nach v1.0 | = | Upload aus der Kette auf den Test-Track |
| 240 | A T K | Rundlauf in der Kette | offen | nächste Runde | **PR** | Die drei Rundlaufklassen laufen in Stufe 1 nie |

### 2.3 Kette (`.github/`) und Auslieferung

| Nr. | Ausl. | Bereich | Stand | Ziel | Vorschlag | Kurz |
|---|---|---|---|---|---|---|
| 265 | K T | Verweise Kette → Doku | offen | nächste Runde | **14** | Fehlermeldungen nennen Schritte und Kennungen, die es nicht mehr gibt (F-PK-45) |
| 290 | K T | Einrichtung im Tor | offen | nächste Runde | **14** | Keine Stufe lässt `install.php` gegen eine leere Datenbank laufen |
| 234 | K T | Deploy-Weg im Tor | teilweise | nächste Runde | **14** | Kein Prüfmittel fährt Deploy → Anmeldung → `update.php` |
| 213 | K D | Zustandsdatei | teilweise | Zuarbeit | **14** | Alte Datei je Anlage löschen — das Neuaufsetzen erledigt es; Rest ist Doku |
| 262 | K | Atomare Auslieferung | offen | 14 | **nach v1.0** | Hängt am Hoster (keine Symlinks über FTPS); P8 wechselt den Hoster nicht |
| 261 | — | Staging-Aufbewahrung | offen | Zuarbeit | = (steht in 6.1) | Aufbewahrung auf 5 setzen — Betreiberin |
| 264 | K | Transport-Aktion | nur auf Anlass | Pflegeaufgabe | = | Fremd-Aktion ablösen — nur bei Abbrüchen |

### 2.4 Werkzeuge und Prüfstand (`tools/`, keine Auslieferung)

| Nr. | Ausl. | Bereich | Stand | Ziel | Vorschlag | Kurz |
|---|---|---|---|---|---|---|
| 403 | T | Prüfstand/Freigabeprobe | offen | nächste Runde | **PR** | Freigabeprobe in Chromium bricht im Gesamtlauf ab — macht PRs rot |
| 352 | T | Steuerung/`nummern.py` | offen | nächste Runde | **PR** | Scheinkollisionen während eines offenen Merges |
| 347 | T | Jobprobe/Waisen | offen | nächste Runde | **PR** | Fremde Waisen, Quelle unbekannt |
| 385 | T | Zweitfaktorprobe | offen | Pflegeaufgabe | **PR** | Hinterlässt je Lauf eine Sperre |
| 384 | T | Sandbox/PHP 8.4 | offen | Pflegeaufgabe | **PR** | `aufbauen.sh` prüft die PHP-Fassung nicht |
| 343 | T | Sandbox/Docker-Spiegel | offen | nächste Runde | **PR** | `plattform.sh` ohne Rückfall auf den Spiegel |
| 237 | T | Bilderlauf/Täter-Finder | offen | nächste Runde | **PR** | Verursacher-Spalte leer |
| 411 | T | Stilvergleich/3 Engines | offen | nächste Runde | **PR** | Geplante Abweichungen je Engine verschieden |
| 410 | T | Messstand/Eichlauf | offen | nächste Runde | **PR** | Eichlauf misst den Renderer nicht |
| 300 | T | Plattformmatrix `haupt` | offen | nächste Runde | **PR** | Hauptstufe fährt die Matrix nur zur Hälfte — oder `Pruefablauf.md` 3 zurücknehmen |
| 353 | T | Schemaprobe migriert/frisch | offen | nächste Runde | **14** (mit R66 entscheiden) | Ohne alte Migrationen zu v1.0 gegenstandslos, bei der ersten neuen wieder nötig |
| 388 | T | Demo-Fixture | offen | nächste Runde | **14** | Fixture seit S11 nicht aus dem Referenzkonto erzeugbar — Demo-Konto nach Runbook braucht sie |
| 391 | T | Browserwerkzeuge | offen | nächste Runde | **14** | Elf Werkzeuge ruft kein Prüfstand — in eine Probe hängen oder löschen: das ist die Inventur nach R68 |
| 263 | T | Integritätswache | nicht umsetzen | nach v1.0 | = | Grenze benannt (G5) |

## 3. Abweichungen zwischen Rahmenplan und Kopfzeilen

**F-BE-03 — Nr. 46 steht in zwei Schritten.** Die Fahrplanzeile 13 führt
„Altformat der Sicherung weg (Nr. 46)", die Kopfzeile sagt `gehört zu: 14`,
und Nr. 187 verlangt „in einem Paket mit Nr. 46, sobald keine Anlage mit
Altbestand mehr herüberkommt (Nr. 324)" — das ist P8. Die Kopfzeile hat
recht; die Fahrplanzeile 13 trägt den Stand vor R4-01. → Verlaufszeile,
Nr. 46 aus der Zeile 13 streichen.

**F-BE-04 — Schritt 13 trägt vier Client-Punkte, die mit „Gesicht v1.0"
nichts zu tun haben.** Nr. 154, 157, 201, 229 stehen unter 13 mit dem
Vermerk „nächste Android-Stufe" bzw. „nächste Uhr-Stufe" — zugeordnet, weil
P7 ohnehin alle drei Zählungen ausliefert, nicht weil P7 sie braucht.
Rahmenplan 4 sagt: Ein Paket, das nur `android/` oder nur `watch/` anfasst,
kann immer laufen. Sie blockieren nichts und könnten parallel zur
Konzeptsitzung 13 laufen. → Vorschlag GR (Abschnitt 4.2).

**F-BE-05 — Nr. 262 hängt an einer Fähigkeit des Hosters, die P8 nicht
ändert.** Der Eintrag sagt selbst „P8 oder ein Hosterwechsel"; P8 ist der
Schnitt auf derselben Anlage. → `nach v1.0`, Stand `nur auf Anlass`
(Hosterwechsel).

**F-BE-06 — Drei Pflegeaufgaben sind keine.** Nr. 280 ist ein Verstoß gegen
die Zusage „keine fremde Quelle zur Laufzeit" (Kartenquelle ohne
Lizenzeintrag) und gehört in die Doku-Neufassung von 13, die `Lizenzen.md`
ohnehin anfasst. Nr. 383 ist ein möglicher Datenverlust auf dem
Ubuntu-LTS-PHP — eine Komplettsicherung, die nicht aufgeht — und muss vor
der Selbsthoster-Doku (R72) beantwortet sein. Nr. 385 ist ein Fehler eines
Prüfmittels, der ein anderes rot färbte; Pflege heißt dagegen „bei
Gelegenheit".

**F-BE-07 — Die Begründung von Nr. 202 ist veraltet.** „Jeder Anlass träfe
Dateien von 18" — Schritt 18 ist seit dem 05.10.2026 gemergt. Der Punkt
bleibt Pflegeaufgabe; der Satz wird ersetzt.

**F-BE-08 — Nr. 229 steht `zurückgestellt` in Schritt 13.** Ein
zurückgestellter Punkt in einem Schritt, der „als Nächstes" dran ist, ist
ein Widerspruch im Vokabular: Entweder er wird dort gebaut (dann `offen`)
oder er gehört nicht dorthin. Der Vertragsteil (Fehlertabelle 403/507) ist
in 13 richtig (Vertrag v1), der Clientteil in GR.

**F-BE-09 — Drei Prüfmittel-Punkte sind in Wahrheit P8-Arbeit.** Nr. 391
(„in eine Probe hängen oder löschen") ist wörtlich die Inventur nach R68
(„Durchsicht von `tools/` … mit Begründung je Weglassung"). Nr. 290 und 234
messen genau den Weg, den P8 einmal von Hand geht (frische Anlage,
`install.php`, Übernahme) — ein Prüfmittel, das dort entsteht, hat seinen
Fall gleich vor sich. Nr. 388 (Demo-Fixture) braucht das Demo-Konto „nach
Runbook" in der frischen Anlage.

**F-BE-10 — Nr. 62 wartet seit dem 12.09.2026 auf eine Zuarbeit, die
nirgends als Zuarbeit steht.** „Neue Vorlagen anfordern" ist entschieden;
Abschnitt 6.2 des Rahmenplans führt die Vorlagen nicht. → Zeile in 6.2
„vor 13".

## 4. Vorschlag

### 4.1 Grundsatz (Q-BE-01)

**Vor v1.0 liegt, was v1.0 festschreibt oder was Daten gefährdet:** der
Vertrag v1 (23, 229-Vertrag), die Zusagen (280), Rechtstexte (400),
Sicherheit und Schlüssel (392, 354, 358, 355), die Sicherung (383, 46, 187,
324), das Datenmodell (252, 276), die Selbsthoster-Doku (R72). **Nach v1.0
darf liegen, was Komfort oder Prüfmittel ist** und keinen Fehler aufhält —
die zwölf heutigen `nach v1.0` bleiben, 262 kommt dazu. **Die
Prüfmittel-Punkte liegen dazwischen:** Sie stufen keine Zählung hoch und
können jederzeit laufen, auch parallel; drei davon (403, 352, 385) färben
heute Läufe rot und sollten vor dem nächsten großen Schritt weg.

### 4.2 Zwei neue Fahrplanschritte und eine Prüfmittel-Runde

| Schritt | Kennung (Vorschlag) | Inhalt | Ausl. | Voraussetzung | parallel zu |
|---|---|---|---|---|---|
| 21 | **GR — Geräterunde** | 154, 157, 201, 229 (Clientteil), 402; dazu die Gerätetests der Prüfliste S11 und AR als Abnahme | U, A | Wear-OS-Uhr für den Gerätetest (6.2) | Konzeptsitzung 13 (Rahmenplan 4) |
| 22 | **BR5 — Backlog-Runde 5, Web** | 392, 354, 358, 355, 345, 357, 346, 356, 361, 383 | W | — | GR |
| — | **PR — Prüfmittel-Runde** (nur Werkzeug, wie RP/BR) | 403, 352, 347, 385, 384, 343, 237, 411, 410, 300, 240, 232 | T | — | allem, was nicht den Prüfstand fährt |

**Reihenfolge:** PR (403/352/385 zuerst) → BR5 und GR nebeneinander → 13 →
14. Begründung: P7 schreibt die Doku-Neufassung über das Verhalten, das
v1.0 hat — die Fehler sollten vorher weg sein, sonst dokumentiert P7 den
Fehler oder P7 und BR5 fassen dieselben Seiten an. Alternativ (Q-BE-02):
BR5 **in** 13 als erstes Arbeitspaket — spart einen Schritt, macht 13
größer.

### 4.3 Neue Zuordnung je Schritt

| Ziel | heute | Vorschlag |
|---|---|---|
| 13 — P7 | 23, 62, 154, 157, 201, 229, 409 | 23, 62, 229 (Vertrag), 280, 342, 409 |
| 14 — P8 | 46, 187, 252, 262, 276, 324, 400 | 46, 187, 213, 234, 252, 265, 276, 290, 324, 353, 388, 391, 400 |
| 21 — GR | — | 154, 157, 201, 229 (Clients), 402 |
| 22 — BR5 | — | 345, 346, 354, 355, 356, 357, 358, 361, 383, 392 |
| PR | — | 232, 237, 240, 300, 343, 347, 352, 384, 385, 403, 410, 411 |
| Zuarbeit | 80, 213, 261 | 80, 261 (+ neue Zeile 6.2: Logo-Vorlagen für 62) |
| Pflegeaufgabe | 202, 230, 264, 280, 383, 384, 385 | 202, 230, 264 |
| nach v1.0 | 37, 50, 52, 55, 90, 96, 99, 100, 198, 200, 263, 351 | dieselben + 262 |
| nächste Backlog-Runde | 27 Einträge | **leer** |

Summe: 6 + 13 + 5 + 10 + 12 + 2 + 3 + 13 = **64** — eine mehr als 63, weil
229 in zwei Hälften steht (eine Nummer, zwei Ziele: Kopfzeile trägt GR, der
Vertragsteil wird als Satz in Nr. 23 aufgenommen; Q-BE-04).

### 4.4 Was im Rahmenplan zu schreiben wäre (Fassung 191)

1. Fahrplanzeilen 21 (GR) und 22 (BR5) nach K5; PR als Zeile ohne Zählung
   (wie RP und BR in Abschnitt 8).
2. Reihenfolge in Abschnitt 3: PR → 21 ‖ 22 → 13 → 14; Abschnitt 4
   (Parallelität): 21 neben 22 und neben der Konzeptsitzung 13.
3. Fahrplanzeile 13 ohne „Nr. 46" (F-BE-03); mit „Nr. 280, 342".
4. 6.2: Logo-Vorlagen (F-BE-10).
5. Eine Programmentscheidung **R90**: der Grundsatz aus 4.1 — was vor v1.0
   liegt und was nicht (Q-BE-01).
6. Verlaufszeile 191 mit den Berichtigungen F-BE-03, F-BE-07.
7. Backlog-Kopfzeilen nach 4.3; Spanne für neue Nummern erst, wenn ein
   Zweig welche vergibt (keine in dieser Runde).

## 5. Fragen an die Betreiberin

| Nr. | Frage | Vorschlag |
|---|---|---|
| Q-BE-01 | Gilt der Grundsatz aus 4.1 als Regel (R90)? Oder soll der Backlog vor v1.0 bis auf `nach v1.0` wirklich leer sein — dann kommen auch die Pflegeaufgaben und PR vor 13 | Grundsatz 4.1 |
| Q-BE-02 | BR5 als eigener Schritt 22 vor 13 — oder als erstes Arbeitspaket in 13? | eigener Schritt; 13 bleibt eine Doku- und Gesichtsphase |
| Q-BE-03 | GR als eigener Schritt 21 parallel zur Konzeptsitzung 13 — oder in 13 bleiben? | eigener Schritt; braucht die Wear-OS-Uhr aus 6.2 |
| Q-BE-04 | Nr. 229 in zwei Hälften: Kopfzeile GR, Vertragsteil als Satz in Nr. 23? | ja |
| Q-BE-05 | Nr. 262 nach `nach v1.0`, `nur auf Anlass` (Hosterwechsel)? | ja |
| Q-BE-06 | PR vor allem anderen (wegen 403, 352, 385) — oder nur diese drei vorziehen und den Rest nach v1.0? | nur die drei vorziehen; der Rest parallel, ohne Platz in der Reihe |
| Q-BE-07 | Nr. 342 (Tabellen rollen, Mockup) nach 13 — P7 ist die Gestaltungsphase? | ja |

## 6. Entscheidungen

Noch keine.

## 7. Protokoll

| Datum | Was |
|---|---|
| 02.11.2026 | Runde 1: Backlog (63), Rahmenplan 190 und Archiv-2 gelesen; Matrix, Abweichungen F-BE-01 bis -10, Vorschlag 4.1 bis 4.4, Fragen Q-BE-01 bis -07. Nichts geschrieben außer dieser Datei. |
