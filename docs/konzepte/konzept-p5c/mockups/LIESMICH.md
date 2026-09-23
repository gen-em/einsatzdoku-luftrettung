# Mockups zum Konzept P5c — Runden M-P5c-01 und M-P5c-02

> **Runde M-P5c-02** (Banner, Zweitfaktor, Support-Sicht, Bus-Faktor,
> Umgebungszeile auf den Blättern) steht in **Abschnitt 8** — gebaut und
> **freigegeben am 23.09.2026**. Die Abschnitte 1 bis 7 gehören zu M-P5c-01.

Sechs Mockups, eine Runde (Konzept P5c, Abschnitt 6; Konzept freigegeben
20.09.2026). Gebaut gegen `origin/main` `862ca7f` (Web 20.25.0). **Kein
Anwendungscode** — nichts unter `server/` ist angefasst.

**Stand: abgeschlossen am 20.09.2026.** Nach mehreren Durchsichten des
Auftraggebers am selben Tag ist **alles entschieden und freigegeben**; (e)
ist entfallen. Die Entscheidungen stehen im Konzept P5c, Abschnitt 2.6
(E-P5c-25 bis -30) und Abschnitt 6. **Nachtrag vom 23.09.2026** nach dem
Abgleich mit Fassung 2 des Konzepts: Abschnitt 7.

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
    Schritt 15, seit 22.09.2026 gebaut). Das Mockup nimmt sie nicht vorweg.

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
   `docs/Design.md` und `style.css`. **Geändert am 23.09.2026:** Das
   Druckblatt (`.blatt-druck`) entsteht schon in **AP5**, mit dem Codeblatt
   des Zweitfaktors als erstem Verwender; AP9 stellt die beiden Blätter
   darauf um.

## 7. Nachtrag vom 23.09.2026 — Abgleich und Fassung 2 des Konzepts

Das Konzept ist am 23.09.2026 gegen `8fd4553` abgeglichen worden (Fassung 2,
Abschnitt 0a; Belege in `../Abgleich-2026-09-23.md`). Was davon diese Runde
betrifft — **die Bilder bleiben freigegeben**, diese Punkte gelten bei der
Übernahme:

1. **Hausform.** Texte aus den Mockups folgen dem Binnen-I (E-PK-26/-37). Drei
   Stellen tun es nicht: „Sache des Betreibers" in (d) und (d)-Handy, „der
   Betreiber hat ihn nicht" in (f)-Notfallblatt. Der Code sagt schon „Sache der
   BetreiberIn" bzw. „Die BetreiberIn hat …". **Das Bild ist maßgeblich für
   die Form, nicht für die Schreibweise.**
2. **(d) Reiter „AVV".** Beschriftet wird aus `RT_TEXTE`; der vierte Reiter
   heißt „Vereinbarung zur Auftragsverarbeitung (AVV)" (Gestaltungsvorgabe 4
   vom 17.09.2026), die Reihe rollt unter 720 px.
3. **(c) Verwaltung hat sechs Einträge**, nicht fünf: NutzerInnen,
   Konto-Backups, Installation, Rechtstexte, Demo-Konto, Protokoll (E-P5c-28).
4. **(b) Reiter Geräte:** Der Satz, dass Wear-OS-Uhren dort bauartbedingt
   nicht erscheinen (Z-02 vom 05.09.2026), **bleibt** — das Bild zeigt ihn
   nicht. Die Karte „Konten" nach Rolle zeigt nach AP4 auch Support.
5. **(f) Klassennamen.** `.blatt-gruppen` und `.blatt-gruppe` **gibt es schon**
   (Betreiber-Rückfrage, P5b AP9); übernommen stünde die Rückfrage in acht
   statt zwei Spalten. Bei der Übernahme heißen sie `.blatt-druck-gruppen`,
   `.blatt-druck-gruppen-5`, `.blatt-druck-gruppe`.
6. **(f) Umgebungszeile.** Auf Staging trägt der Blattkopf eine Textzeile mit
   dem Namen der Umgebung (E-P5c-50) — das Bild dazu liefert M-P5c-02 (e).
7. **(a), (f) Datum und Zeit:** Das Komma ist entschieden (E-P5c-37) — Punkt 13
   in Abschnitt 3 ist damit erledigt.
8. **Firefox** ist in der Arbeitsumgebung inzwischen vorhanden, aber
   Playwright erzeugt PDF nur mit Chromium; der Druck aus Firefox bleibt Prüfpunkt der
   BetreiberIn.
9. **Benennung.** „F6" in Abschnitt 3 heißt im Konzept jetzt **F-P5c-13**,
   „F-P5c-6/-7" heißen **F-P5c-06/-07**.

Die nächste Runde, **M-P5c-02** (Banner, Zweitfaktor, Support-Sicht,
Bus-Faktor, Umgebungszeile auf den Blättern), steht in Abschnitt 8; der
Auftrag im Konzept, 6.2.

## 8. Runde M-P5c-02 — Banner, Zweitfaktor, Support, Bus-Faktor, Blätter auf Staging

Gebaut am 23.09.2026 gegen `origin/main` `8ae873c` (Web 20.37.1), Auftrag im
Konzept 6.2 (E-P5c-34): **Opus, aus dem HTML-Stand heraus, nicht frei
gezeichnet.** **Kein Anwendungscode** — nichts unter `server/` ist angefasst.
**Stand: freigegeben am 23.09.2026**, mit zwei Änderungen aus der
Durchsicht (8.2): Im Codeblatt steht das Kästchen vorn, und die Zeile
„Umgebung" in (d) nennt die Umgebung in beiden Richtungen. Die fünf Fragen
sind beantwortet; die Entscheidungen stehen im Konzept, 2.8 (E-P5c-59 bis
-66).

**„Aus dem HTML-Stand"** heißt hier: Jede Seite ist aus der örtlichen
Installation (Station B, `hochfahren.sh`) mit Playwright **abgegriffen** —
das ausgelieferte Markup, als BetreiberIn (`admin@gen-em.org`) bzw. im
Demo-Konto. Danach sind die Skripte entfernt, die Pfade auf
`server/assets/` umgebogen und die Symbole eingebettet. **Eingefügt ist nur
das Neue**; wo eine Rolle etwas wegnimmt (Support), ist es herausgeschnitten,
nicht nachgebaut. Die Blätter in (e) gehen von den freigegebenen Dateien aus
M-P5c-01f aus.

### 8.1 Die Dateien

| Datei | Was |
|---|---|
| `M-P5c-02a-banner.html` / `.png` | E-P5c-05, -13, -55, -56. **1** Tagesübersicht auf Staging im Demo-Konto mit allen vier Streifen (Umgebung → Ankündigung → Demo → Datenschutz) an der Stelle des Demo-Hinweises · **2** die Kopfleiste heute, auf Staging mit Variante A und B des aktiven Kopfpunkts · **3** Anmeldeseite auf Staging (Umgebung und Ankündigung über der Karte) · **4** Betrieb → Servereinstellungen, Karte „Ankündigung" mit Byte-Zähler und Rundmail-Knopf · **5** die Rückfrage vor der Rundmail mit der Zahl der Empfänger |
| `M-P5c-02a-banner-handy.html` / `.png` | Tagesübersicht und Anmeldeseite bei 376 px |
| `M-P5c-02b-zweitfaktor.html` / `.png` | E-P5c-15, -41 bis -43, -53, -54. **1** Einstellungen → Profil (Rolle user), Karte „Zweitfaktor" nach „Wiederherstellungsschlüssel" · **2** die Karte in vier Zuständen: einrichten (QR, Knopf zur App, Geheimnis zum Abtippen, Code bestätigen) → zehn Codes einmal sichtbar → eingeschaltet → Pflichtrolle ohne „Ausschalten" · **3** Anmeldung: Code-Schritt, falscher Code, Wiederherstellungscode · **4** das Einrichtungstor für Pflichtrollen, Schritt 1 und 2 |
| `M-P5c-02b-zweitfaktor-handy.html` / `.png` | Code-Schritt, Karte und Tor bei 376 px |
| `M-P5c-02b-codeblatt.html` / `.png` / `.pdf` | das Codeblatt auf dem Druckblatt aus M-P5c-01f: zehn Codes, nummeriert, das Kästchen zum Abhaken **vor** der Nummer (nach der Durchsicht; am Zeilenende schien das Kästchen der linken Spalte zur Nummer der rechten zu gehören). Die HTML-Datei *ist* die Druckseite |
| `M-P5c-02c-support.html` / `.png` | E-P5c-14, -40, -42. **1** NutzerInnen als Support (nur Rolle user, kein Anlegen, keine Sammelaktionen) · **2** Kontoseite als Support (Felder gesperrt, einspaltig, Leiste ohne Betrieb) · **3** Setz-Link, wenn die Mail nicht sofort hinausgeht — der Link wird nie gezeigt · **4** unbestätigte Registrierung: Bestätigungsmail erneut senden · **5** Kontoseite als BetreiberIn mit der Karte „Zweitfaktor" und der Rückfrage „Zurücksetzen" |
| `M-P5c-02d-busfaktor.html` / `.png` | E-P5c-16, -44, -56 und E-P5c-05, -55. **1** Betrieb → Status als einzige BetreiberIn, Präfix gesetzt und Etikett nicht — beide neuen Zeilen orange, Zähler nachgezogen, „Was hier gilt" schon entfernt · **2** die Zeile „Verwaltungskonten" in allen vier Fällen · **3** die Zeile „Umgebung" in allen vier Fällen — Plakette „Staging" bzw. „Produktiv", je ein Satz, wofür die Umgebung da ist (nach der Durchsicht) |
| `M-P5c-02e-schluesselblatt-staging.html` / `.png` / `.pdf` | E-P5c-50: das Schlüsselblatt aus M-P5c-01f auf Staging — Umgebungszeile unter dem Kopf, Adresse der Staging-Anlage |
| `M-P5c-02e-notfallblatt-staging.html` / `.png` / `.pdf` | dasselbe für das Notfallblatt; dazu der Pfad „Einstellungen → Profil" berichtigt (F-P5c-61) |

### 8.2 Entscheidungen der Runde

Entschieden am 23.09.2026, **in allen fünf Fragen wie im Bild**. Bei
Q-P5c-30 wich das Bild von der Empfehlung ab; die Antwort ist deshalb
ausdrücklich nachgefragt worden.

| Nr. | Frage | Vorschlag im Bild → **Entscheidung** |
|---|---|---|
| **Q-P5c-26** | (a) Der aktive Kopfpunkt auf der roten Kopfleiste: **A** Strich in `--orange-hell` (4,12 : 1) oder **B** in Weiß (`--auf-dunkel`, 4,78 : 1)? Das heutige Orange hätte 2,10 : 1 | beide im Bild, A empfohlen — bleibt beim Orange der Anwendung („hier stehst du") → **A** (E-P5c-59) |
| **Q-P5c-27** | (a) Steht die Ankündigung auch auf der Anmeldeseite oder erst nach dem Anmelden? | auf der Anmeldeseite — eine Wartung betrifft auch den, der sich gerade anmelden will → **ja** (E-P5c-60) |
| **Q-P5c-28** | (b) Ist das Einrichtungstor für Pflichtrollen eine eigene Seite in der Anmeldehülle, und heißt sie `zweitfaktor.php`? | ja und ja — keine andere Seite ist erreichbar, bevor der Zweitfaktor steht, also gehört das Tor nicht ins Gerüst → **ja, `zweitfaktor.php`** (E-P5c-61) |
| **Q-P5c-29** | (c) Heißt der Eintrag im Aktionsblatt künftig für alle Rollen „Setz-Link senden" statt „Passwort zurücksetzen"? | ja — es wird kein Passwort gesetzt, sondern ein Link verschickt → **ja** (E-P5c-62) |
| **Q-P5c-30** | (d) Der Menüzähler „Status" zählt Orange mit (F-P5c-59). Bleibt der Bus-Faktor orange, steht der Zähler auf einer Anlage mit einer BetreiberIn **dauerhaft** auf mindestens 1 | im Bild orange wie entschieden (E-P5c-44). Empfohlen war: Fall 1 und 2 neutral „keine Vertretung", nur Fall 3 orange → **orange wie im Bild, in allen drei Fällen**; der Zähler darf dauerhaft stehen (E-P5c-63) |

**Zwei Änderungen aus der Durchsicht:**

1. **(b) Codeblatt:** Das Kästchen steht vorn, vor der Nummer (E-P5c-65).
   Am Zeilenende stand das Kästchen der linken Spalte unmittelbar vor der
   Nummer der rechten und sah aus, als gehöre es zu ihr.
2. **(d) Zeile „Umgebung":** Die Plakette nennt die Umgebung — „Staging"
   bzw. „Produktiv", nicht „kein Etikett" —, der Satz sagt in beiden
   Richtungen gleichwertig, wofür sie da ist; beide blau (E-P5c-64).

Mit der Freigabe gelten außerdem die Vorschläge, die die Bilder ohne eigene
Frage zeigen (E-P5c-66): die Reihe `.hinweise` an der Stelle des
Demo-Hinweises; die Karte „Ankündigung" in Servereinstellungen; die Karte
„Zweitfaktor" nach „Wiederherstellungsschlüssel"; Support einspaltig mit
Knöpfen weg statt ausgegraut; beide Statuszeilen oben in der Karte
„Server", „Umgebung" immer sichtbar; die Umgebungszeile auf den Blättern.

### 8.3 Befunde — gehen mit der Freigabe ins Konzept

| Nr. | Befund | Wohin |
|---|---|---|
| **F-P5c-59** | Der Menüzähler zählt Orange und Rot (`status_lib.php`, Kopf der Menüzähler: „Status — orange + rot der Ampel"). E-P5c-44 begründet Orange damit, dass der Zähler bei Rot nie auf null stünde — in Orange steht er genauso | Q-P5c-30 → **bleibt orange** (E-P5c-63) |
| **F-P5c-60** | Die zwei Bedingungen aus E-P5c-16 sind eine: Wer weniger als zwei handlungsfähige Verwaltungskonten hat, hat auch weniger als zwei handlungsfähige BetreiberInnen. Die Ampel hängt allein an „zwei BetreiberInnen handlungsfähig"; die Zahl der Verwaltungskonten wählt nur den Text (Fall 1 oder 2). Die Abnahme von AP5 („2 handlungsfähige → keiner") ist so zu lesen: **2 BetreiberInnen** → keiner, 1 BetreiberIn und 1 Admin → Orange | AP5, Abnahme |
| **F-P5c-61** | `notfallblatt.php` schickt zweimal nach „Einstellungen → Konto" (Z. 169 und 213); den Reiter gibt es nicht, der Schlüssel liegt unter „Profil" (vgl. F-P5c-53). Das freigegebene M-P5c-01f trägt den Fehler mit; (e) berichtigt ihn | AP9 (Umbau des Notfallblatts) |
| **F-P5c-62** | **Eine neue Anlage lässt sich seit Web 20.30.0 nicht einrichten.** `install.php` legt die erste BetreiberIn über `konto_anlegen()` an, und das ruft seit c3b5bff (Schritt 15, AP5) `db_transaktion()` auf. `konto_lib.php` lädt `db.php` aber nur, wenn `config.php` schon da ist (Z. 82) — während der Einrichtung ist sie es nicht. Die Funktion fehlt, die Ausnahme wird gefangen, die Seite sagt „Einrichten gescheitert". Gemessen mit `hochfahren.sh --neu` (Station B): scheitert in Schritt 4. Stufe 1 richtet keine Anlage ein und merkt es deshalb nicht | **Backlog Nr. 288**, außerhalb von P5c |
| **F-P5c-63** | **Die Anmeldeseite stellt die vier Verweise neben die Karte statt darunter.** `.anmeldung` ist ein Flex-Behälter in Zeilenrichtung, `nav.fuss-anmeldung` steht darin hinter der Karte (`login.php` Z. 553) — der Kommentar dort will sie „direkt unter das Anmeldeformular". Bei 390 px drückt das die Karte auf rund 200 px. Seit d3832e4 (Web 20.23.0). (a) nimmt die Berichtigung im Rahmen „nur im Mockup" vorweg | **Backlog Nr. 289**, außerhalb von P5c |

**Zur Arbeitsumgebung:** Für den Abgriff war die örtliche Anlage neu
einzurichten, und genau das scheitert (F-P5c-62). Überbrückt wurde es mit
einer vorübergehenden Änderung an `install.php` und `db.php`, die nach dem
Einrichten **zurückgenommen** ist (`git status server/` leer). Der Abgriff
selbst lief gegen den unveränderten Stand.

### 8.4 Gemessen

| Was | Ergebnis |
|---|---|
| Waagerechter Überlauf | **0 px** Seite und **0** Rahmen mit Überlauf in allen sechs Bildschirmdateien (1440 und 400 px); **0** fehlende Ressourcen, **0** Konsolenfehler |
| QR-Code (b), gelesen mit `jsqr` 1.4.0 aus dem gerenderten Bild | **2 von 2** QR-Codes (Profilkarte und Tor) ergeben Zeichen für Zeichen die angezeigte otpauth-Adresse |
| Codeblatt (b), PDF aus Chromium ohne Hintergrundgrafiken | **1 Seite**; 748 von 1017 px Satzspiegel (**74 %**) |
| Schlüsselblatt auf Staging (e), drei Werte | **1 Seite**; 945 von 1017 px (**93 %**). Ohne die Zeile (M-P5c-01f, heute nachgemessen): 910 px — die Zeile kostet 35 px |
| **Härtefall** auf Staging: drei Werte, Kurzname 83 Zeichen, Adresse 62 Zeichen (wie in M-P5c-01f), mit Umgebungszeile | **1 Seite**; **1013 von 1017 px** (99,6 %) — **4 px Luft**. Hält, aber knapp: Die Abnahme von AP9 misst diesen Fall. Reicht es dort nicht, rückt die Zeile als erste Zeile in `.blatt-kopf-rechts` (spart die 35 px, bleibt Text). Die Messdatei ist nicht abgelegt |
| Notfallblatt auf Staging (e) | **1 Seite**; 727 von 1017 px (**71 %**); ohne die Zeile 692 px |
| Kontraste, gerechnet nach WCAG (gegengerechnet mit `kontrast.py`, s. u.) | Weiß auf `--rot` 4,78 : 1 · `--schnee` auf `--rot` (Name im Kopf) 4,68 : 1 · aktiver Kopfpunkt A `--orange-hell` auf `--rot` 4,12 : 1, B Weiß 4,78 : 1 (Soll ≥ 3 : 1; das heutige `--orange` hätte 2,10 : 1) · Umgebungsstreifen `--rot-tief` auf `--rosa` 6,27 : 1 · Umgebungszeile der Blätter `--rot-tief` auf `--schnee` 7,58 : 1. Verworfen: `--sand` auf `--rot` 2,86 : 1 |
| Vorschlags-CSS | keine Hexwerte, nur Token; (d) braucht keines |

**Nicht gemessen:** Firefox-Druck (Prüfpunkt der BetreiberIn, wie in
Abschnitt 7 Nr. 8). Eine echte Authenticator-App gegen den QR-Code — der
Code ist echt, aber gescannt hat ihn niemand; das ist Prüfpunkt von AP5.
Die Kontraste stehen noch nicht in der Paarliste von
`tools/screenshots/kontrast.py` — die bekommt sie mit AP1. Gegengerechnet
sind sie mit den Funktionen des Werkzeugs (`token_lesen()`, `kontrast()`):
**8 von 8 Paaren gleich** mit der Tabelle oben; der Lauf des Werkzeugs
selbst: 22 Paare, 0 verfehlt.

### 8.5 Bauart

Abgriff mit Playwright 1.56 / Chromium 141 aus der örtlichen Installation
(Web 20.37.1, `https://127.0.0.1:8443`), 1440 bzw. 390 px, `de-DE`. Bilder
aus Chromium bei 1440 bzw. 400 px (Druckseiten 1240 px), `--lang=de-DE`,
danach mit ImageMagick auf 256 Farben reduziert, ohne Rasterung (M-P5c-01:
Median-Cut; hier fehlt die Bibliothek dafür — 3,3 MB → 1,4 MB).
**PDF:** `page.pdf()` mit `preferCSSPageSize`, ohne Hintergrundgrafiken.
Der QR-Code ist die Modulmatrix aus `qrcode-generator` 2.0.4 (MIT), als ein
`<path>` gezeichnet; die Bibliothek selbst ist **nicht** vendoriert — das
entscheidet AP5 (Lizenz, Herkunft, SHA-256 nach `docs/Lizenzen.md`). Das
Bauwerkzeug ist nicht Teil der Lieferung.

**Nur im Mockup, nicht Teil des Vorschlags:** der beige Rahmen; die
Verweiszeile der Anmeldeseite unter der Karte (F-P5c-63); geöffnete Dialoge
(`.mock-offen`); die Blende am unteren Rand gekürzter Rahmen. Das Datum
„Sichtbar bis" in (a) ist im Bild ein Textfeld; im Bau ist es
`type="date"` wie in der Suche — Chromium zeichnet Datumsfelder in dieser
Umgebung in US-Form. Alle Namen, Adressen, Codes und Schlüssel sind
erfunden.

**Neues Symbol:** keines außer `protokoll` aus M-P5c-01 (in der Leiste des
Supports).

**Neue Klassen (Vorschlag):**

| Mockup | Klassen | Art |
|---|---|---|
| (a) | `.hinweise` (Reihe der Streifen), `.hinweis-umgebung` (Ton des Umgebungsstreifens), `.meldung-ankuendigung` (Schließen-Knopf rechts, bricht nicht um) | Erweiterung von Hinweisstreifen und Meldung (Design.md 9) |
| (a) | `.kopf-umgebung` samt aktivem Kopfpunkt (A oder B) und Name im Kopf | Variante der Kopfleiste; **freigegeben**, Variante A (E-P5c-59) |
| (b) | `.qr`, `.qr-grund`, `.qr-modul`; `.zweitfaktor-einrichtung` (zwei Spalten ab 720 px); `.feld-code`; `.codeblock-liste`; `.anmeldung-schritt` | `.qr` ist **neue Darstellung** — **freigegeben** (E-P5c-66); der Rest erweitert Feld, Codeblock und Anmeldekarte |
| (b) | `.blatt-codes` (Kästchen vorn über `order:-1`) | Erweiterung des Druckblatts aus M-P5c-01f |
| (c) | `.form-raster-einspaltig` | Variante des Formularrasters |
| (d) | — | keine; zwei gewöhnliche Statuszeilen |
| (e) | `.blatt-umgebung` | Erweiterung des Druckblatts; Text und Rahmen in `--rot-tief`, keine Fläche |

Für (b) und (e) gilt Abschnitt 7 Nr. 5 weiter: Bei der Übernahme heißen die
Gruppenklassen des Druckblatts `.blatt-druck-gruppen` usw.
