# Konzept BV — Vorgriff auf Backlog-Runde 4, parallel zu P5c

**Kürzel:** `BV`. Arbeitspakete `BV-01 …`, Entscheidungen `E-BV-01 …`,
Befunde `F-BV-01 …`, Fragen an die Betreiberin `Q-BV-01 …`, Prüfpunkte
`P-BV-01 …` (im Prüfdokument). Commit-Nachrichten beginnen mit dem Paket
(`BV-02: …`). Benennung nach `docs/Pruefablauf.md` 7.
**Herkunft:** Anfrage der Betreiberin am 24.09.2026, ob sich während P5c
Backlog-Punkte abarbeiten lassen, die P5c nicht in die Quere kommen. Die
Durchsicht aller offenen Punkte steht in Abschnitt 2, die Freigabe in
Abschnitt 3.
**Rahmenplan:** Vorgriff auf **Schritt 17** (Backlog-Runde 4), dessen
Voraussetzung „Merge von 10c" **bewusst nicht abgewartet wird** (E-BV-01).
Schritt 9 („ab Schritt 1, parallel") trägt den Vorgriff nicht allein: Die
Zuordnung „Backlog-Runde" in Abschnitt 5 meint die Liste von Schritt 17.
**Modell:** Opus, in jedem Paket. **Kein Fable-Schritt.**
**Fächerung (`CLAUDE.md` 7):** keine, in keinem Paket — BV-01 und BV-02
ändern dieselbe Datei, BV-03 und BV-04 sind erzählender Text, BV-05 ist
Prüfarbeit auf der einen örtlichen Anlage.
**Versionsstufe:** keine. BV berührt nur `tools/` und `docs/`
(`CLAUDE.md` 2: eine solche Änderung stuft keine der drei Zählungen hoch).
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-BV-Backlog-Vorgriff.md`
daneben, entsteht mit BV-01. **Zweig:** `claude/intelligent-carson-q8f7ag`,
von `origin/main` `ba2ec57`.
**Backlog:** Spanne **329 bis 333** (E-BV-08), eingetragen im Kopf von
`docs/Backlog.md` vor dem ersten Push. Vergeben: **329** (BV-03), **330** (Gegenprüfung P-BV-02).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **24.09.2026 — gebaut, Pull Request offen.** Fassung 1 freigegeben (E-BV-01 bis -04); BV-01 bis BV-05 erledigt. **Gemergt wird nach P5c** (Empfehlung, Q-BV-01); danach Abschluss nach `CLAUDE.md` 7 (Erledigt-Zeile im Rahmenplan, Konzept löschen). |
> | Entschieden | **E-BV-01 bis -08** (Abschnitt 3). Aus der Umsetzung: **E-BV-09** (BV-01, zur Kenntnis — weicht vom Konzepttext ab). |
> | Offen | **Q-BV-01** (Merge-Zeitpunkt) — Empfehlung „nach P5c", Begründung in 3.1. **Q-BV-02** (Abfahrtort in `CLAUDE.md` 4) und **Q-BV-03** (zwölf weitere Aufzählungen), beide aus der Gegenprüfung P-BV-03 (3.2). |
> | Umsetzung | **BV-00 erledigt** (Konzept, Backlog-Spanne). **BV-01 erledigt** (Nr. 184, `b712aca`; Befunde F-BV-07 bis -09). **BV-02 erledigt** (Nr. 274, `65c5348`; Befund F-BV-10). **BV-03 erledigt** (Nr. 40, `121ee81`; Befund F-BV-11, Nr. 329 notiert). **BV-04 erledigt** (`e4a1931`; Nr. 214 ganz, Nr. 194 und 150 zum Teil). **BV-05 erledigt** (Nr. 222, 270, 281, 212 ausgetragen; Pull Request geöffnet). **Als Nächstes:** nach dem Merge von P5c `main` aufnehmen (`Pruefablauf.md` 5.3), dann Freigabe und Abschluss. |
> | Fable-Schritte | keine. |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Backlog | Dateien | Prüfstand | Commit | Abnahmezahlen |
> |---|---|---|---|---|---|---|
> | BV-00 Konzept | **erledigt 24.09.2026** | — | dieses Konzept, `docs/Backlog.md` (Kopf) | klein | | — |
> | BV-01 Kommentar-Abtaster | **erledigt 24.09.2026** | 184 | `tools/quelltext/vollstaendigkeit.py`, `vollstaendigkeit-zusagen.md` (+1 Ausnahme), `docs/Backlog.md`, `docs/CHANGELOG.md` | klein | `b712aca` | Zusagen **0** Befunde (nach 1 echtem Fund, F-BV-08); geleerte Zeilen **47 555 → 47 856** in 179 Dateien, `einsatz_form.php` **851 → 877**; nur-alt **7** Zeilen, alle Fehlgriffe; Gegenprobe `confirm(` alt **3**, neu **4**; P5c-Abzug `3576a97` **0**; Symbole Unicode **14 → 5**, Emoji **8 → 0**; Textprobe 0 neu |
> | BV-02 Zusammengesetzte Tonklassen | **erledigt 24.09.2026** | 274 | `tools/quelltext/vollstaendigkeit.py`, `docs/Backlog.md`, `docs/CHANGELOG.md` | klein | `65c5348` | Hinweis „im Markup nicht gefunden" **64 → 62** (P5c-Abzug 74 → 72), **0** Befunde; Tonlisten PHP = JS, **5** Töne; Gegenprobe (Regel weg + Ton nur in JS) **3** neue Befunde; alte gegen neue Fassung je Ton mit entfernter Regel: Aufrufprüfung meldet in **beiden** (F-BV-10) |
> | BV-03 Herkunft der Altklassen | **erledigt 24.09.2026** | 40 | `tools/quelltext/vollstaendigkeit-streichliste.md`, `docs/Backlog.md`, `docs/CHANGELOG.md` | klein | `121ee81` | **25 von 25** Herkünften rekonstruiert (O1 4, O2 2, O4 1, O7 2, O8a 1, O9a 1, O9b 2, O9c 12), **0** „nicht feststellbar"; ~~**4** Zeilen von „ersatzlos" auf `[bleibt]` berichtigt (F-BV-11)~~ — widerlegt; **Gegenprüfung P-BV-02: 12 trugen, 12 teilweise, 1 nicht; 15 Zeilen berichtigt** (F-BV-12); Platzhalter **25 → 0**; Prüfung **0** Befunde |
> | BV-04 Drei Doku-Punkte | **erledigt 24.09.2026** | 214 (erledigt), 194 und 150 (zum Teil, mit Vermerk offen) | `docs/Technik.md` (6.5b, 4.97a, Runbook 7, Datenmodell, Tabelle Sicherungsziel), `docs/Handbuch.md` (Einstieg, Kapitel 5 zweimal), `README.md`, `docs/CHANGELOG.md` (Eintrag Web 10.1.0 rückwirkend), `docs/Backlog.md` | klein | `e4a1931` | Nr. 214: **1** Absatz mit **3** Wegen; Nr. 194: **3** Handbuchstellen + README **2** + Technik **1**, 11.5 bleibt (E-BV-06); Nr. 150: **4** Stellen (3 im Eintrag gezählte + 1 neue), `server/jobs.php` bleibt (E-BV-05); `grep` auf Repositoriumspfad in Befehlen: **0** außer Erklärung und Fund-Eintrag 15.5.2; `handbuch` 2 Dokumente / **0**, `linkprobe` 122 Verweise / **0**, `textprobe` **0** neu; P5c-Hunks an den Stellen: **0** |
> | BV-05 Austragen und Abschluss | **erledigt 24.09.2026** | 222, 270, 281, 212 | `docs/Backlog.md`, `docs/CHANGELOG.md`, `tools/proben/wiederherstellung/probe.php` (Kopfkommentar), Prüfdokument | klein | | Wiederherstellungsprobe auf frischer Anlage **111 / 0** (Teil 10 „2 erledigt, 2 von 4 offen"); Läufe `pruefung.yml`: BR-Zweig **3**, alle `pull_request`; BV-Zweig nach 4 Pushes **0**; Workflows: **0** Treffer für Uhr-Aufbau oder Cache; `bestandPruefen()` über `EdApi.postJson()` seit `0bee2fb`; offene Backlog-Punkte **115 → 108** (−8 ausgetragen, +1 Nr. 329); keine Nummer doppelt |

---

## 1. Auftrag

**Ziel:** Während P5c auf seinem Zweig die Pakete RW-04, AP6 bis AP9 und
AP11 baut, die Backlog-Punkte abarbeiten, die **keine Datei berühren, die
P5c geändert hat oder noch ändern will** — außer der unvermeidlichen
Buchführung (`docs/Backlog.md`, `docs/CHANGELOG.md`).

**Nicht Ziel:** alles unter `server/` (Rahmenplan 4: „Alles, was
`server/` … schreibt, wartet auf das Paket davor"; dazu vergibt P5c die
Web-Fassungen 20.38.0 bis mindestens 20.45.0, und jede parallele Web-Stufe
schöbe sich darunter); neue Prüfmittel oder Riegel, die in Stufe 1 den Code
von P5c messen würden (E-BV-03); der Rahmenplan (E-BV-07).

**Warum eine eigene Runde und nicht Beifang in P5c:** P5c hat die meisten
dieser Punkte ausdrücklich abgelehnt (E-P5c-46: „nicht als Beifang"), und
die übrigen liegen in Dateien, die P5c nie anfasst.

## 2. Befund — die Durchsicht vom 24.09.2026

Gemessen an `origin/main` `ce42213` und am P5c-Zweig `f82e277` (RW-02), mit
neun lesenden Agenten: einer ermittelte den Dateiumfang von P5c (gebaut und
geplant), vier ordneten die offenen Backlog-Punkte ein, vier prüften jeden
Kandidaten mit dem Auftrag gegen, eine Kollision **nachzuweisen**. Während
der Durchsicht ist `main` auf `ba2ec57` (BR-Abschluss) und P5c auf `3576a97`
(RW-03, Web 20.45.0, `main` aufgenommen) weitergegangen; die Aussagen zu den
gewählten Punkten sind gegen diesen Stand nachgemessen (F-BV-02).

| Nr. | Was | Zahl oder Folge |
|---|---|---|
| F-BV-01 | **Offene Punkte und ihr Verbleib.** 119 offene Nummern (Vereinigung aus `main` und P5c). 90 scheiden aus: einer späteren Phase zugeordnet (P6, P7, S11, P8, Schritt 18, nach v1.0), Teil von P5c selbst (AP7, AP8, AP9, SD), nur auf Anlass, oder unter `server/`. 29 Kandidaten gegengeprüft: **7 geeignet, 18 bedingt, 4 ungeeignet.** Die Gegenprüfung hat 9 Einstufungen verschärft und 4 gelockert. | 119 / 29 / 7 |
| F-BV-02 | **P5c berührt die gewählten Dateien nicht.** Nachgemessen gegen `3576a97` mit `git diff --stat ba2ec57 3576a97`: `tools/quelltext/vollstaendigkeit.py` 0, `vollstaendigkeit-streichliste.md` 0, `tools/proben/wiederherstellung/probe.php` 0. `vollstaendigkeit-ohne-regel.md` +1 Zeile, `tools/quelltext/LIESMICH.md` 5 Zeilen (beide fasst BV nicht an). `docs/Technik.md` und `docs/Handbuch.md` hat P5c stark geändert (887 bzw. 483 Zeilen), aber nicht in den Abschnitten, die BV-04 anfasst (Hunks nachgesehen). | 0 Dateien im Kern |
| F-BV-03 | **Android und Uhr berührt P5c weder gebaut noch geplant** (`git diff` unter `android/`, `watch/`, `docs/JSON-Vertrag.md`: 0 Dateien). Deshalb geht Block C (Nr. 65, 116 Android-Hälfte, 284) an eine eigene Instanz, nicht an BV (E-BV-02). | 0 |
| F-BV-04 | **Der Klon war flach** — 381 Commits ab 05.09.2026; P3 (O2 bis O10, Ende August) lag davor. BV-03 braucht die volle Geschichte. `git fetch --unshallow`: **1 040** Commits ab 16.07.2026. | 381 → 1 040 |
| F-BV-05 | **Ausgangsmaß der Vollständigkeitsprüfung** (`pruefen.sh vollstaendigkeit`, `ba2ec57`): **0 Befunde**, 64 Hinweise „Regel im Stylesheet, im Markup nicht gefunden" (darunter `meldung-ok` und `meldung-schutz`, Nr. 274). Der Abtaster leert in 179 Dateien **47 555** Zeilen, in `einsatz_form.php` (2 330 Zeilen) **851**. | 0 / 64 / 851 |
| F-BV-06 | **Die Streichliste trägt 25 Platzhalter.** 176 Einträge, davon 25 mit dem Grund „Ersatzlos entfallen. Gemessen am 22.09.2026: Die Klasse steht in keiner PHP-, JS- oder CSS-Datei unter `server/` mehr." (Paket PK-04/1b) — das ist genau die Zeile, vor der Nr. 40 warnt: Sie sieht vollständig aus und sagt nicht, warum. Die Zahl im Backlog-Eintrag („53, gemessen 13.09.2026") ist überholt. | 25 von 176 |
| F-BV-07 | **Nr. 184 beschrieb den Mechanismus nur zur Hälfte** (BV-01). Am heutigen Stand verschluckte der alte Abtaster keine 800 Zeilen am Stück. Die falschen Negative kamen aus zwei Quellen, die der Eintrag nicht nannte: `#` und `//` im **HTML** galten als Kommentaranfang (`href="#…"`, `http://` im Text) — **7** Zeilen, alle einzeln gelesen —, und ein `"` in einem **Regex-Literal** (`/[;"\r\n]/`) brachte ihn in JavaScript aus dem Tritt; das deutsche Schlusszeichen `„…"` im Seiten-HTML tat dasselbe in PHP-Dateien. | Behoben: HTML bleibt unberührt; `'`/`"`-Ketten enden in JS und CSS am Zeilenende. |
| F-BV-08 | **Der erste Lauf fand, was verschluckt war:** die GPX-Namensraumadresse in `export.js` (`http://www.topografix.com/GPX/1/1`), eine Kennung, die nie abgerufen wird — dieselbe, die für `gpx_lib.php` längst mit Grund in der Ausnahmeliste steht. | Eingetragen in `vollstaendigkeit-zusagen.md` (18 → 19). Eine Datei, die P5c +1 Zeile an anderer Stelle hat; `git merge-file` ohne Konflikt. |
| F-BV-09 | **Nr. 279, auf die das Konzept für die Symbolprüfung verwies, ist erledigt** (PK-04/5, 23.09.2026). Der einzige Grund, den Abtaster dort nicht zu benutzen, war Nr. 184 selbst — so steht es im Kommentar der Prüfung. | E-BV-09. |
| F-BV-10 | **Nr. 274 übertrieb die Lücke** (BV-02). „Verschwände eine der beiden Regeln, meldete es niemand" stimmte nicht: Die Tonprüfung am Aufruf hätte jede der fünf Regeln gemeldet, weil jeder Ton mindestens einmal als Literal übergeben wird — gemessen je Ton mit entfernter Regel (ok 17, info 23, warn 21, fehler 21, schutz **1** Treffer an Aufrufen). Für `schutz` hing es an einem einzigen Aufruf (`tag_spuren.php`). | Gebaut wie geplant; der Gewinn ist die Unabhängigkeit vom Aufruf, nicht eine geschlossene Lücke. So steht es im Backlog und im Changelog. |
| F-BV-11 | ~~**Vier „ersatzlos entfallene" Klassen gibt es noch**~~ **— widerlegt von F-BV-12.** (BV-03) `c-dc-winch`, `c-dc-bergwacht`, `c-dc-secondary`, `c-dc-false_alarm`: `mission_fields_lib.php` setzt `'c-dc-' . $col` an jede Katalogspalte, als Anker ohne Regel. Die Messung vom 22.09.2026 (PK-04/1b) hat nur Literale gesucht — dieselbe Grenze, die Nr. 274 für die Meldungstöne beschreibt. | Als `[bleibt]` eingetragen. Ein Mittel, das zusammengesetzte Klassen aller Bausteine auflöst, gibt es nicht; BV-02 hat es nur für die Meldung gebaut (geschlossene Tonliste). Notiert als **Backlog Nr. 329** (28 von 62 Hinweisen tragen das Präfix eines Bausteins), nicht mitgemacht. |
| F-BV-12 | **Die Gegenprüfung P-BV-02 hat 13 von 25 Streichlisten-Zeilen eingeschränkt oder verworfen** (25.09.2026, eine Instanz, die sie nicht geschrieben hat, alle 25 gelesen). Commit und Paket stimmten in allen 25; der **Ersatz** trug in 12 nur teilweise und in 1 nicht: falsche Stellenzahl (`btn-yellow`), Bausteine, die es dort nicht gab (`ui_feld()` und `stammdaten_ui.php` bei `neu-form`/`neu-feld`), falsche Farben (`pwquality`), falsches Paket (`map`: O2, nicht O1), unvollständige Aufzählungen (`settings-form`, `stammblock`, `rowlink`). Und **F-BV-11 war falsch:** `c-dc-false_alarm` hat seit Web 6.3.0 keine Spalte; die drei übrigen `c-dc-*` erzeugt `mission_fields_lib.php` zwar, aber `missiontable.js` setzt sie seit Schritt 15 AP9b an kein Element, weil es diese Spalten selbst führt. PK-04/1b lag mit „ersatzlos" richtig. Jede Angabe des Gegenprüfers ist vor der Übernahme nachgemessen (Stichproben: `c-dc-*`, `.geo` in O1 = 0, `geo-hoch` ohne Regel, `btn-yellow` 6, `.listen-form` in `admin_stammdaten.php`, `tr.clickable`, fünf `settings-form`, `sd-zahl` bis `fdcc6c5f`). | 15 Zeilen berichtigt (12 teilweise, 1 nicht, 2 Nachträge), 10 als bestätigt vermerkt; `[bleibt]` bei `c-dc-*` zurückgenommen; Backlog Nr. 40, Nr. 329 und Changelog berichtigt; Nebenfund **Nr. 330** (Kommentar in `style.css` nennt O1 statt O2). **Lehre:** Eine Rekonstruktion aus Diffs ist eine Deutung; E-BV-02 hatte BV-03 ohne Gegenlesung geplant, und die Zahl „25 von 25" sah belegt aus. |
| F-BV-13 | **Die Gegenprüfung P-BV-03 bestätigt die fünf geänderten Stellen und findet ein elftes verschlüsseltes Feld, das die Zusage nicht nennt.** Der manuelle **Abfahrtort** (`start`: `addr`, `lat`, `lon`) liegt im `pat_blob` (`einsatz_form.php`, Block „MANUELLER ABFAHRTORT"; `import_ui.js`). `CLAUDE.md` 4 und die Tabelle in `Technik.md` 4.98 nennen ihn nicht; `Technik.md` 4.98c (Z. 4977), Handbuch 4.3 und die Rechtstexte (AVV, Datenschutz-Ergänzung) nennen ihn. Dazu **eine Stelle ungenau**, von BV-04 selbst geschrieben: Handbuch 5 nannte „Einsatzort" ohne „Adresse und Koordinate" — berichtigt. Und **zwölf weitere Stellen** zählen den verschlüsselten Umfang ohne die Notizen des Einsatzes auf (u. a. die Schlüsselliste in `Technik.md` 4.x, Z. 809; Handbuch Einsatzansicht, Suche, Entsperren, Import); zwei Klartextlisten ohne die Höhe des Einsatzorts (`Was-ist-NAdoku.md`, `AVV.md`). | Handbuch 5 berichtigt. **Q-BV-02** und **Q-BV-03** an die Betreiberin — `CLAUDE.md` 4 ist eine feste Zusage und wird nicht nebenbei geändert, und die Rechtstexte gehören ihr. |

## 3. Entscheidungen

| Nr. | Entscheidung | Von | Grund |
|---|---|---|---|
| E-BV-01 | **Der Vorgriff auf Schritt 17 ist freigegeben.** Er läuft parallel zu P5c und ist eine bewusste Abweichung vom Fahrplan (Voraussetzung „Merge von 10c"). | Betreiberin, 24.09.2026 | Die gewählten Punkte berühren keine P5c-Datei; der Preis ist Buchführung. |
| E-BV-02 | **Umfang: Block A (Nr. 184, 274, 40) und Block B (Nr. 214, 194, 150 Doku-Teil, 222, 270, 281, 212) hier.** Block C (Nr. 65, 116 Android-Hälfte, 284) läuft als **Android-Runde AR** in einer eigenen Instanz, nach einem Prompt vom 24.09.2026. | Betreiberin, 24.09.2026 | C braucht die Ausbaustufe `android` und den Emulator, beides ohne Berührung mit BV oder P5c. |
| E-BV-03 | **Nr. 36 nicht.** | Betreiberin, 24.09.2026 | Ein neuer Riegel mit Schwelle 0 würde nach dem Merge den JavaScript-Code von P5c messen, mitten in der Phase, ohne dass P5c ihn aus seinem Konzept kennt. |
| E-BV-04 | **Nr. 236 (`ubuntu-latest` → Ubuntu 26 am 19.10.2026) nicht in BV.** | Betreiberin, 24.09.2026 | P5c ist bis dahin gemergt; die Gegenprobe gehört dann auf `main`. Ist P5c am 05.10.2026 nicht gemergt, wird der Punkt wieder vorgelegt. |
| E-BV-05 | **Nr. 150 nur im Doku-Teil.** Der Kopfkommentar von `server/jobs.php` bleibt, Nr. 150 bleibt mit Vermerk offen. | Umsetzung | Eine Zeile unter `server/` verlangt eine Web-Stufe (`CLAUDE.md` 2) — unter den von P5c vergebenen Nummern. |
| E-BV-06 | **Nr. 194 nur im Einstieg und in Kapitel 5 des Handbuchs, dazu `README.md`.** Der Textbaustein in Handbuch 11.5 bleibt; Nr. 194 bleibt mit Vermerk offen. | Umsetzung | 11.5 ändert P5c (AP9 überarbeitet die Texte in Verwaltung und Betrieb); zwei Hände an einem Absatz, der in eine Rechtserklärung kopiert wird, sind einer zu viel. |
| E-BV-07 | **BV schreibt den Rahmenplan nicht.** Fahrplanzeile und Erledigt-Eintrag entstehen beim Aufnehmen von `main` nach dem P5c-Merge. | Umsetzung | P5c vergibt laufend Fassungsnummern; die 112 steht dort schon doppelt. Eine dritte Hand am Verlauf macht es schlimmer. |
| E-BV-08 | **Backlog-Spanne 329 bis 333 für BV, 334 bis 338 für AR.** | Umsetzung | 319 bis 328 hat P5c beim Aufnehmen von BR reserviert (Kopf des Backlogs auf `3576a97`). |
| E-BV-09 | **Die Symbolprüfung blendet Kommentare jetzt aus — gegen den Text von BV-01 in Abschnitt 4**, der das ausdrücklich ausschloss. | Umsetzung (BV-01) | F-BV-09: Der Ausschluss beruhte auf einer falschen Annahme. Gemessen: Unicode 14 → 5, Emoji 8 → 0, alle 17 weggefallenen in Kommentaren; die Prüfung bleibt Hinweis, kein Befund, und die Zahl steht in keinem anderen Dokument. Die Verweise auf Symboldateien liest sie weiter aus dem ganzen Quelltext. |

### 3.1 Q-BV-01 — Merge-Zeitpunkt (offen)

**Empfehlung: nach P5c.** Das Ruleset verlangt, dass ein PR den Kopf von
`main` enthält, und das nicht über „Update branch", sondern örtlich mit
Prüfstand und Bericht im Merge-Commit (`Pruefablauf.md` 5.3). Mergt BV
zuerst, muss P5c `main` zum zweiten Mal aufnehmen — wegen der Migrationen mit
dem Prüfstand in der Hauptstufe, und mit Konflikten in Backlog-Kopf,
Changelog und Rahmenplan beim großen Zweig. Mergt P5c zuerst, nimmt BV `main`
auf: Konflikte nur in der Buchführung, Prüfstand in der kleinen Stufe, und
die neuen Prüfungen aus BV-01 und BV-02 messen dabei gleich den neuen
P5c-Code. **Preis:** Die erledigten Punkte stehen ein paar Tage länger offen.

### 3.2 Q-BV-02 und Q-BV-03 — aus der Gegenprüfung P-BV-03 (offen)

**Q-BV-02: Der Abfahrtort in der Zusage.** Soll der manuelle Abfahrtort
(`start`) in `CLAUDE.md` 4 und in die Tabelle von `Technik.md` 4.98
aufgenommen werden? Er **ist** verschlüsselt; die Zusage nennt ihn nur nicht.
Die Änderung weicht nichts auf, sie beschreibt, was der Code tut.
*Empfehlung: ja, in BV, als eigener kleiner Schritt mit Verweis auf F-BV-13.*

**Q-BV-03: Die zwölf weiteren Aufzählungen.** Sollen die Stellen, die den
verschlüsselten Umfang ohne die Notizen des Einsatzes nennen, jetzt in BV
nachgezogen werden (nur `docs/` außerhalb der Rechtstexte; keine davon liegt in
einem Hunk von P5c, außer Handbuch Z. 403 in der Nähe eines solchen), oder als
Erweiterung von Nr. 194 nach dem P5c-Merge? Die zwei Rechtstexte (`AVV.md`,
`Nutzungsbedingungen.md` 2.6) gehören in jedem Fall der Betreiberin.
*Empfehlung: jetzt in BV, ohne Rechtstexte und ohne Handbuch Z. 403.*

## 4. Arbeitspakete

Für jedes Paket gilt: ein Commit je Paket, Nachricht beginnt mit `BV-0n:`;
danach Konzept (Statusblock) und Prüfdokument fortschreiben, pushen. Die
Prüfmittel laufen zuletzt (`Pruefablauf.md` 6.7). Changelog: ein Eintrag
`[Werkzeug: …]` ohne Versionsnummer, wie bei BR, fortgeschrieben je Paket.

### BV-01 Kommentar-Abtaster (Nr. 184)

`ohne_php_js_kommentare()` tastet in `.php`-Dateien nur noch die Bereiche ab,
die Code sind — `<?php … ?>`, `<?= … ?>` und `<script> … </script>`; alles
dazwischen ist HTML und bleibt unverändert. Das `<script`-Muster folgt
`Pruefablauf.md` 6.4 (`TAG_REST`). Die drei Kommentare zu Nr. 184 in
`pruefung_symbole()` werden nachgezogen. *(Hier stand: „ob die
Symbolprüfung den Abtaster jetzt benutzen darf, ist nicht Teil von BV-01
(Nr. 279 ist ein eigener Punkt)" — Nr. 279 ist erledigt, und die
Symbolprüfung benutzt ihn jetzt; E-BV-09.)*

**Abnahme:** An `einsatz_form.php` werden die Kommentare ab dem bisher
verschluckten Bereich erkannt, und im HTML dazwischen wird nichts geleert;
Zahl der geleerten Zeilen vorher/nachher je Datei und gesamt. Die drei
Zusagen-Prüfungen bleiben bei **0**, und eine testweise eingeschleuste
`confirm(`-Stelle im bisher verschluckten Bereich wird **gefunden**
(Gegenprobe, nicht eingecheckt). `pruefen.sh vollstaendigkeit` 0 Befunde.
Gegenprobe auf dem P5c-Stand (`git worktree`, nur lesend): ebenfalls 0 —
sonst wäre der Merge rot.

### BV-02 Zusammengesetzte Tonklassen (Nr. 274)

Die Vollständigkeitsprüfung liest die Töne, die `ui_meldung_markup()` und
`EdHtml.meldung()` annehmen, und zählt die daraus gebildeten Klassen
(`meldung-<ton>`) als im Markup belegt. **Abnahme:** `meldung-ok` und
`meldung-schutz` verschwinden aus dem Hinweis (64 → 62 oder weniger, mit
Namen); Gegenprobe: eine Kopie des Stylesheets ohne die Regel
`.meldung-schutz` → die Prüfung meldet sie (Befund, nicht Hinweis).

### BV-03 Herkunft der Altklassen (Nr. 40)

Für jede der 25 Platzhalterzeilen die Herkunft aus der Geschichte
rekonstruieren: in welchem Commit die letzte Verwendung verschwand, in
welchem Paket (O2 bis O10 oder später), und wodurch sie ersetzt wurde. Was
sich nicht klären lässt, bekommt „Herkunft nicht mehr feststellbar" — auch
das ist eine ehrliche Auskunft, „ersatzlos" wäre eine erfundene.
**Abnahme:** 25 von 25 Zeilen tragen Commit und Paket oder den Vermerk;
Zahl der rekonstruierten gegen die nicht feststellbaren; die Prüfung bleibt
bei 0 Befunden.

### BV-04 Drei Doku-Punkte (Nr. 214, 194, 150)

- **Nr. 214:** ein Satz in `docs/Technik.md` 6.5: Eine auf dem Server von Hand
  gelöschte Datei kommt nur zurück, wenn die Zustandsdatei mitgelöscht wird —
  und was das kostet (der nächste Lauf überträgt alles, bei angeschalteter
  Wartung). Nicht in Abschnitt 7: dort hat P5c Hunks.
- **Nr. 194:** Einstieg und Kapitel 5 des Handbuchs nennen die Notizen des
  Einsatzes als verschlüsselt und grenzen „Notizen und Freitextfelder sind
  davon nicht erfasst" auf den Diensttag ein; `README.md` ebenso. Wortlaut
  nach `CLAUDE.md` 4 — vollständig oder als Verweis.
- **Nr. 150, Doku-Teil:** `docs/Technik.md` 4.97a und Runbook 7 und der
  Changelog-Eintrag Web 10.1.0 bekommen den Platzhalter ohne `server/`
  (Entscheidung vom 12.09.2026) und den Satz zum Kopier-Knopf.

**Abnahme:** je Punkt die Stellen vorher/nachher mit Zahl;
`pruefen.sh handbuch`, `textprobe` und `linkprobe` ohne neuen Befund.

### BV-05 Austragen und Abschluss (Nr. 222, 270, 281, 212)

Vier Punkte sind in der Sache erledigt und stehen noch unter *Offen*. Sie
wandern mit Beleg nach *Erledigt*: 222 (die Kette baut die Uhr nicht mehr,
seit PK-05), 270 (`EdApi.postJson` wertet `res.ok` seit Web 20.34.0),
281 (Stufe 1 läuft auf Arbeitszweigen nur beim PR; Beleg aus den Läufen
von BR), 212 (nach einer Nachmessung auf frischer Anlage). Danach das
Prüfdokument mit abhakbarer Liste und der Pull Request — **nicht mergen**.

## 5. Prüfprotokoll-Soll

Ohne Versionssprung fährt der Prüfstand die **kleine Stufe**
(`Pruefablauf.md` 3): die billigen Riegel und die Proben der berührten
Dateien. Der Bericht gehört in die Nachricht des Kopf-Commits jedes Pakets
(`Pruefablauf.md` 5). Die Zahlen je Paket stehen im Statusblock und im
Prüfdokument.
