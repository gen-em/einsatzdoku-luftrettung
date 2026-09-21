# Mockups zum Konzept P5c — Runde M-P5c-01

Sechs Mockups, eine Runde (Konzept P5c, Abschnitt 6; Konzept freigegeben
20.09.2026). Gebaut gegen `origin/main` `862ca7f` (Web 20.25.0). **Kein
Anwendungscode** — nichts unter `server/` ist angefasst.

**Stand: abgeschlossen am 20.09.2026.** Nach mehreren Durchsichten des
Auftraggebers am selben Tag ist **alles entschieden und freigegeben**; (e)
ist entfallen. Die Entscheidungen stehen im Konzept P5c, Abschnitt 2.6
(E-P5c-25 bis -30) und Abschnitt 6.

Je Darstellung eine HTML-Datei, die das **echte** `server/assets/style.css`
einbindet (relativer Pfad) und die Schriften des Repositoriums nutzt; neue
Klassen stehen im `<style>`-Block der Datei unter „Vorschlag für style.css".
Dazu die gerenderten Bilder. Unter jedem Mockup stehen **Anmerkungen** — dort
die Begründungen, die Empfehlung und alles, was vom Konzepttext abweicht.

## 1. Die Dateien

| Datei | Was | Stand |
|---|---|---|
| `M-P5c-01a-protokoll.html` / `.png` | E-P5c-01, -02, -03, -10, -11, -12; F-P5c-6, -7: Verwaltung → Protokoll. Sicht **Admin** (vier Reiter, kein Archiv, eine Zeile mit `daten` aufgeklappt), Sicht **BetreiberIn** (sieben Reiter, Kennungssuche im Reiter System), **Archiv** (Kennung des Serverschlüssels, Versandstand, Download, Fall „anderer Schlüssel"), Ausschnitt Reiter Sicherheit mit IP | V1, **freigegeben 20.09.2026, unverändert** — mit den drei Abweichungen vom Konzepttext (Abschnitt 3, Nr. 1 bis 3) |
| `M-P5c-01a-protokoll-handy.html` / `.png` | dieselben Sichten bei 376 px; Reiterreihe rollt, Plakette unter dem Text | V1, freigegeben mit (a) |
| `M-P5c-01b-statistik.html` / `.png` | E-P5c-18, R38, R42: **Statistik mit drei Reitern** „NutzerInnen · Einsätze · Geräte"; je Reiter links die Tabelle „… je Zeitraum", rechts „was es gibt"; **eine** Zählung der Einsätze; **keine Unterpunkte in der Leiste** | **V3, freigegeben 20.09.2026.** V1 (`M-P5c-01b-betriebslage.*`: eigene Seite oder eine Seite „Betriebslage") verworfen — kein achter Eintrag, der Name gefiel nicht. V2 zeigte noch die Variante mit zwei Einsatzzählungen; entschieden: eine |
| `M-P5c-01b-statistik-handy.html` / `.png` | die drei Reiter bei 376 px | V3 |
| `M-P5c-01c-uebersicht.html` / `.png` | E-P5c-07, -29, Nr. 244: **Teil 1** Übersicht mit **Bereichskarten** — rundes Bereichszeichen, Bereichsname und Zahl mittig im Kopf, Kopf auf Rauch; Sicht BetreiberIn (drei Bereiche) und Admin (zwei). **Teil 2** das linke Menü: heute gegen **Option 1 „Linie"** — einmal mit einem Bereich zu, einmal alle drei offen. Alle Seitenrahmen zeigen beides | **V4, entschieden und freigegeben 20.09.2026.** Der Weg: V1 Überschriftenzeile gegen Karten (Karten gefielen) · V2 Sand und Dunkelblau als Kopf, Leiste „Linie" gegen „Band" (Linie gewählt) · V3 Hellblau gegen Sand (Hellblau und Dunkelblau verworfen) · acht Vorschläge ohne bedeutungstragende Farbe (K1–K4, N1–N4) · gewählt **K2 mit mittigem Kopf**. Die Varianten-Dateien sind entfernt |
| `M-P5c-01c-uebersicht-handy.html` / `.png` | die Bereichskarten bei 376 px; die Schublade heute und in Option 1 | V4 |
| `M-P5c-01d-rechtstext-vorschau.html` / `.png` | Nr. 121: Vorschau beim Tippen in zwei Varianten — 1 unter dem Feld auf „Installation", 2 eigene Seite „Rechtstexte" mit Vorschau rechts; dazu die Zustände der Vorschau | V1.1, **entschieden 20.09.2026: Variante 2** (eigene Seite). Variante 1 bleibt als Vergleich im Bild und ist der Rückfall unter 1200 px. V1.1: Seite „Rechtstexte" ohne Unterpunkte in der Leiste (Regel aus (b)) |
| `M-P5c-01d-rechtstext-vorschau-handy.html` / `.png` | beide Varianten bei 376 px | V1 |
| ~~`M-P5c-01e-zeitraum*`~~ | Nr. 198: Windenkacheln in der Bodenansicht | **entfällt** — entschieden 20.09.2026: bodengebunden keine Windenanzeige, auch nicht bei Bergwacht-Diensttagen. Dateien entfernt |
| `M-P5c-01f-schluesselblatt.html` / `.png` / `.pdf` | E-P5c-08, Nr. 246: Schlüsselblatt als eine A4-Seite, **ungünstigster Fall mit drei Werten**. Die HTML-Datei *ist* die Druckseite (Strg+P); Rahmen und Anmerkungen drucken nicht mit | V1.1 (Webversion in der Fußzeile ausgeschrieben), **freigegeben 20.09.2026** — samt nummerierten Gruppen und der gekürzten, nicht ausgelagerten Erklärung |
| `M-P5c-01f-notfallblatt.html` / `.png` / `.pdf` | dasselbe für das Notfallblatt, derselbe Baustein | **V1.1**, **freigegeben 20.09.2026** — ergänzt nach der ersten Durchsicht: Webversion in der Fußzeile |

## 2. Entscheidungen der Runde

**Offen ist nichts mehr.** Entschieden am 20.09.2026:

- (a) **freigegeben**, unverändert — mit den drei Abweichungen vom
  Konzepttext (Abschnitt 3, Nr. 1 bis 3).
- (b) **freigegeben:** Statistik mit drei Reitern „NutzerInnen · Einsätze ·
  Geräte", **eine** Zählung der Einsätze, keine Unterpunkte in der Leiste.
  Verworfen: eigene Seite, der Name „Betriebslage", zwei Zählungen.
- (c) **freigegeben:** Leiste **Option 1 „Linie"**; Übersicht mit
  **Bereichskarten** (Bereichszeichen, Kopf mittig, auf Rauch). Verworfen:
  Überschriftenzeile, „Band", getönte Köpfe in Hellblau und Dunkelblau, die
  sieben anderen Vorschläge.
- (d) **entschieden:** eigene Seite „Rechtstexte", Vorschau rechts.
- (e) **entfällt:** Nr. 198 wird nicht umgesetzt.
- (f) **freigegeben:** beide Blätter; Kopf = Bildmarke + Kurzname der
  Installation, nummerierte Gruppen, Webversion in der Fußzeile.

## 3. Befunde — gehen mit der Rückschreibung ins Konzept

**Freigegebene Abweichungen vom Konzepttext (mit (a)):**

1. **Archiv als abgesetzter Reiter** statt als Karte unter der Liste.
2. **Ein Suchfeld** für Text, Konto und (nur BetreiberIn) Fehlerkennung — das
   Konzept nennt vier Filter „Zeitraum · Art · Konto · Text".
3. **Handy: Plakette unter dem Text** — die eine Abweichung von „rechtsbündig
   in einer Spalte" (Vorgabe 17.09.2026), nur unter 720 px.

**Berichtigungen gegen den Stand auf main:**

4. **E-P5c-20 nennt `doku_html()` und „Verwaltung → Rechtstexte".** Die
   Funktion heißt `rt_html()` (`rechtstexte_lib.php`); `admin_rechtstexte.php`
   ist seit S8 eine Weiterleitung auf `admin_installation.php` — mit (d)
   Variante 2 wird die Adresse wieder zur Seite. Eine Vorschau gibt es bereits
   (gespeicherter Stand); neu ist das Mitlaufen. `rt_html()` kennt **kein
   Fett** — aufgefallen, weil die Vorschau mit dem echten Renderer erzeugt ist.
5. **E-P5c-18 nennt vier Herkünfte, `HERKUNFT_WERTE` hat sechs**
   (`watch`, `android`, `wear`, `manual`, `import`, `schnitt`).
6. **Die Geräteverteilung aus E-P5c-18 existiert seit S8** (Karten „Geräte"
   und „Gerätemodelle"). Neu sind nur „Aktiv" in „Konten je Zeitraum", die
   fünf Fenster in „Einsätze je Zeitraum" und „Herkunft der Einsätze".
7. **Nr. 244 meint die Leiste**, nicht (nur) die Übersicht — der
   Backlog-Nachtrag vom 18.09. nennt die „Einstellungen-Übersicht".
   Entschieden: Leiste nach Option 1. **Ursache, nachgemessen:** Überschrift und Eintrag stehen beide in
   Bricolage 15 px; die Überschrift soll 600 gegen 400 wiegen, aber Bricolage
   ist nur in **500 und 600** eingebunden — `font-weight:400` fällt auf 500.
   Dazu steht der Winkel der Überschrift dort, wo die Einträge ihr Symbol
   tragen.
8. **Nr. 198 wird nicht umgesetzt** (Entscheidung zu (e)). Folge, damit sie
   dasteht: Winden-Cycles bodengebundener Bergwacht-Diensttage erscheinen in
   keiner Ansicht der Zeitraumübersicht. E-P5c-20 verliert den Punkt; die
   beiden Befunde aus V1 dazu (`faehigkeiten` je Art, Divisor) entfallen.
9. **Es gibt keinen Reiter-Baustein.** (a) schlägt `.reiter` vor; Verwender
   sind jetzt drei: Protokoll, Rechtstexte, Statistik. **Seiten mit Reitern
   tragen keine Unterpunkte in der Leiste** (entschieden mit (b)):
   `menue.js` baut keine, wenn `#inhalt` eine `.reiter`-Reihe enthält.
10. **`.zeile:first-child` trifft jedes `<summary>`** — die aufklappbare Zeile
    war 13 px niedriger als die übrigen (54 gegen 67 px). Gegenregel im
    Vorschlag von (a).
11. **`einsatz.php` hält fest, dass der Vorrat bewusst kein Zeichen für
    „herunterladen" hat** — der Download-Knopf im Archiv steht ohne Symbol.
12. **E-P5b-09 („ohne Logo") ändert sich**; das heutige Notfallblatt schreibt
    fest „NAdoku". Entschieden: Bildmarke + `instanz_kurz()`.
13. **Datum-Zeit-Trenner:** Die Mockups (a) und (f) setzen „TT.MM.JJJJ, HH:MM"
    mit Komma, weil ` · ` in der Kleinzeile schon die Angaben trennt. Schritt 15
    hat gemessen, dass es heute drei Trenner gibt (Leerzeichen 25, ` · ` 11,
    Komma 1) und gibt die Entscheidung an **10c AP9** (F-ZE-3,
    `Konzept-Zentralisierung.md`). Das Mockup nimmt sie nicht vorweg.

## 4. Gemessen

| Was | Ergebnis |
|---|---|
| Waagerechter Überlauf der Seite | **0** in allen acht Bildschirmdateien (1440 und 400 px) |
| (b) Tabellen in `.tabelle-scroll` bei 1200, 1280, 1366, 1440 px | **kein Überlauf** mit dem Zweispalter 3 : 2. Gleich geteilt lief die Fünf-Fenster-Tabelle bei 1200 px um 54 px, bei 1280 px um 14 px über — deshalb die neue Layoutregel |
| (c) Kontraste, gerechnet nach WCAG | Bereichszeichen (Weiß auf Dunkelblau) 13,62 : 1 · Titel (Dunkelblau auf Rauch) 12,48 : 1 · Zahl (Gedämpft auf Rauch) 5,30 : 1 — derselbe Wert wie in Design.md, die Rechnung stimmt also mit dem Werkzeug überein. Verworfene Töne zum Nachlesen: Hellblau trug 11,26 / 4,78 : 1, Sand 8,15 : 1 (gedämpft 3,46 : 1 — wäre durchgefallen) |
| Schlüsselblatt, drei Werte, PDF aus Chromium | **1 Seite**; 911 von 1017 px Satzspiegel (**90 %**). Erster Wurf: 1047 von damals 1002 px = 104 %, **zwei Seiten** — gestrafft |
| Notfallblatt, PDF aus Chromium | **1 Seite**; 693 von 1017 px (**68 %**) |
| Härtefall Schlüsselblatt: drei Werte, Kurzname 83 Zeichen (`INSTANZ_MAX` ist 80), Adresse 62 Zeichen | **1 Seite**; 978 von 1017 px (**96 %**) |
| Druck ohne „Hintergrundgrafiken" (Vorgabe der Browser) | Kachel trägt durch den Rahmen; die Warnung hat auf dem Blatt einen Rand |
| Vorschlags-CSS aller Dateien | keine Hexwerte, keine `max-width`-Abfragen, px nur in den Schwellen 720 und 1200 |
| PHP-Meldungen im erzeugten Markup | keine |

**Nicht gemessen:** Firefox (nicht vorhanden — Abnahme von AP9 bleibt „zwei
Browser"). Safari beachtet `@page`-Ränder nur teilweise; nicht geprüft.
Kontraste der neuen Klassen sind vor der Übernahme mit
`tools/screenshots/kontrast.py` nachzumessen.

## 5. Bauart

**Markup:** erzeugt aus den **echten** `ui_*`-Funktionen — `server/ui.php` von
`862ca7f` unter PHP 8.3 geladen, mit Stubs für Rolle, Logo und Instanzname und
austauschbaren Menüdaten. Die Rechtstext-Vorschau kommt aus dem echten
`rt_html()`, die Leiste aus dem echten `ui_leiste_einstellungen()`. Von Hand
gebaut ist nur, was es noch nicht gibt (Reiter, aufklappbare Zeile,
Druckblatt) und was im Browser entsteht (Unterpunkte der Leiste — nach dem
Muster von `menue.js`). Das Bauwerkzeug ist nicht Teil der Lieferung.

**Symbole** sind wie in P5b als `<symbol>` eingebettet. **Bilder:** Chromium
141 (Playwright 1.56) bei 1440 bzw. 400 px (Druckseiten 1240 px), `de-DE`,
danach auf 256 Farben reduziert (Median-Cut, ohne Rasterung). Abweichung von
P5b (`wkhtmltoimage`): Chromium kennt woff2 und `:has()`, die Behelfe
entfallen. **PDF:** `page.pdf()` mit `preferCSSPageSize`, **ohne**
Hintergrundgrafiken.

**Nur im Mockup, nicht Teil des Vorschlags:** der beige Rahmen, der gerollte
Zustand der Reiterreihe in (a)-Handy (`.mock-gerollt`), die nicht klebende
Speichern-Leiste in (d), gestrichelte Platzhalter. Das Präfix `.l1` in (c) dient nur
dem Vergleich der Leiste mit „heute" in einer Datei — übernommen wird die
Regel **ohne** Präfix. Die Leiste in den Mockups (a), (b) und (d)
zeigt noch den heutigen Stand; maßgeblich für sie ist (c).
Anker wie `hilfe.php#schluesselblatt` sind Platzhalter. Alle Zahlen, Namen und
Schlüssel sind erfunden.

**Neues Symbol (Vorschlag):** `protokoll` (Tabler „list", MIT) — den Pfad bei
der Umsetzung **aus der Tabler-Quelle** nehmen, nicht aus dem Mockup.
`rechtstexte` liegt ungenutzt im Vorrat und wird mit (d) wieder gebraucht.

**Neue Klassen (Vorschlag):**

| Mockup | Klassen | Art |
|---|---|---|
| (a) | `.reiter`, `.reiter-punkt`, `.reiter-abgesetzt`, `.reiter-rahmen` | **neuer Baustein** — mit (a) freigegeben; in Design.md als eigener Abschnitt in Kapitel 9 |
| (a) | `.zeile-mehr`, `.zeile-winkel`, `.zeile-daten` | Erweiterung des Bausteins Zeile (9.2) |
| (a) | `.protokoll-liste`, `.archiv-liste` | Seitenklassen (Anordnung unter und ab 720 px) |
| (b) | `.form-raster-links-breit` | **neue Layoutregel** (3 : 2 ab 1200 px), freigabepflichtig |
| (c) | `.karte-bereich` (Kopf mittig, auf Rauch), `.bereich-zeichen`; neue Option `symbol` in `ui_karte_start()`; `.uebersicht-block` und `.uebersicht-block-erst` **entfallen** | **Variante „Bereichskarte" des Bausteins Karte** (Design.md 9.1) — freigegeben; ohne Kopfaktion, gedacht für die Einstellungen-Übersicht |
| (c) | geänderte Regeln an `.leiste-gruppe` (Option 1: Winkel nach rechts, `--groesse-4`, Dunkelblau, Trennlinie) | keine neue Klasse; gilt **nur** für das Einstellungsmenü, nicht für die Diensttage-Leiste |
| (d) | `.vorschau-kopf`, `.vorschau-rollt` | Erweiterung von `.vorschau` |
| (f) | `.blatt-druck`, `.blatt-kopf`, `.blatt-marke`, `.blatt-kopf-rechts`, `.blatt-kachel` (+ `-kopf`, `-name`, `-neben`), `.blatt-gruppen`, `.blatt-gruppen-5`, `.blatt-gruppe`, `.blatt-fuss`, `@page` | **neuer Baustein**; löst den Vorschlag `.blatt-druck` aus M-P5b-02d ab. Papiermaße in **mm** (210, 297, 14, 18, 11) und `letter-spacing:.08em` liegen außerhalb der Tokenskala — zur Freigabe |

## 6. Wie es weitergeht

1. Die Runde ist abgeschlossen; die Rückschreibung ins Konzept P5c ist
   **erfolgt** (20.09.2026): E-P5c-07, -08, -10, -18, -20 neu gefasst,
   **E-P5c-25 bis -30 neu** (Abschnitt 2.6), Befund 1.2/1.3 berichtigt,
   AP2/AP7/AP9 mit Abnahmen angepasst, Abschnitt 5 um F5 bis F7 ergänzt,
   Abschnitt 6 mit dem Ergebnis der Runde, Abschnitte 7 und 8
   fortgeschrieben; dazu der Backlog-Nachtrag (Nr. 244, 246) und die
   betroffenen Zeilen im Rahmenplan-Einschub (198, 244, 246).
2. Freigegebene Bausteine gehen mit AP2 (Reiter, aufklappbare Zeile), AP7
   (Layoutregel) und AP9 (Bereichskarte, Leiste, Vorschau, Druckblatt) in
   `docs/Design.md` und `style.css`.
