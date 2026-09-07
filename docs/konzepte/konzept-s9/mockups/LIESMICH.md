# Mockups zum Konzept S9

Je Darstellung eine HTML-Datei mit den Token aus `server/assets/style.css`
(Auszug im `<style>`), dazu die gerenderten Bilder. Die Schriften kommen aus
`server/assets/fonts/` (relativer Pfad), keine fremde Quelle.

| Datei | Was | Stand |
|---|---|---|
| `M-S9-01-kartenschilder.html` / `.png` | Kartenschilder: Ist und drei Varianten, Maßleiste, Handy- und Desktop-Karte, Anmerkungen (E-S9-12) | 06.09.2026, **V1 freigegeben** (06.09.2026) |
| `M-S9-01-kartenschilder-handy.html` / `.png` | dieselben Varianten, nur Maßleiste und Handy-Karte — zum Lesen am Handy | 06.09.2026 |
| `M-S9-02-artzeichen.html` / `.png` | Artzeichen: Ist, Variante B (eigene Strichzeichnung), C (Bildmarken), C2 (Bildmarken und gefüllte Typzeichen); Zeichensatz aller Kandidaten bei 48 und 20 px; Leiste 260 px und Schublade 320 px (E-S9-13) | 06.09.2026, **freigegeben:** Luft und Boden bleiben Ist; Bergwacht „mountain", Veranstaltung „building-stadium", Sonstiges „dots-circle-horizontal" |
| `M-S9-02-artzeichen-handy.html` / `.png` | dieselben Inhalte untereinander, 400 px | 06.09.2026 |
| `M-S9-03-vorschlagsliste.html` / `.png` | Vorschlagsliste als ein Baustein: Transportziel (Zielkliniken oben, Adressen darunter), Besatzungsfeld (Vorlagen), weitere Rettungsmittel (mit freier Eingabe), Einsatzort (nur Adressen) — am Finger 44 px und am Zeiger 36 px (E-S9-07, E-S9-08) | 06.09.2026, **freigegeben** 07.09.2026 |
| `M-S9-03-vorschlagsliste-handy.html` / `.png` | dieselben Inhalte untereinander, 400 px | 06.09.2026 |
| `M-S9-04-kartendialog.html` / `.png` | Kartendialog in vier Zuständen: Spur mit Start/Ende bei leerem Feld, Suche mit Treffern, Koordinate gesetzt (Zoom 14), Adresssuche aus — Desktop 560 px und Handy 358 px (E-S9-06, PS-1, PS-11) | 07.09.2026, **freigegeben** 07.09.2026 |
| `M-S9-04-kartendialog-handy.html` / `.png` | dieselben vier Zustände als 358-px-Dialog untereinander | 07.09.2026 |
| `M-S9-05-sprungliste.html` / `.png` | Sprungliste in der Standortkarte „Kempten" (zehn Rettungsmittel): Pillen mit Artzeichen, ab sechs Einträgen; nach dem Sprung hervorgehobene Zeile; „Sonthofen" mit drei Einträgen ohne — 44 px am Finger, 36 px am Zeiger (E-S9-14, Nr. 44; Neu-Rendering von N1) | 07.09.2026, **freigegeben** 07.09.2026 |
| `M-S9-05-sprungliste-handy.html` / `.png` | dieselben Karten bei 376 px | 07.09.2026 |
| `M-S9-06-standortseiten.html` / `.png` | Standort zuerst (PS-12): Standortliste mit drei Zahlen je Standort und „Ohne Standort"; Standortseite „Kempten" mit Rückweg, Inhaltsverzeichnis (Pillen mit Zahl), vier Abschnitten, Sprungliste, Filterfeld, „Zum Anfang" — Handy 358 px, Desktop 1180 px mit Leiste und S8-Unterpunkten | 07.09.2026, **freigegeben** 07.09.2026 — das Inhaltsverzeichnis gilt in der Form aus M-S9-07 (Kennzahlen statt Pillen) |
| `M-S9-06-standortseiten-handy.html` / `.png` | Liste und vollständige Standortseite bei 376 px | 07.09.2026 |
| `M-S9-07-anlegen-dialoge.html` / `.png` | Nachträge zu M-S9-06: Inhaltsverzeichnis als Kennzahlen (9.10), die drei Anlegen-Dialoge (Rettungsmittel mit Typ und Betriebsart, Besatzungsmitglied, Zielklinik), Landung auf der neuen Zeile nach dem Anlegen (E-S9-18, E-S9-19) | 07.09.2026, **freigegeben** 07.09.2026 |
| `M-S9-07-anlegen-dialoge-handy.html` / `.png` | dieselben Inhalte untereinander, 400 px | 07.09.2026 |
| `M-S9-08-kurzname.html` / `.png` | Der Kurzname an drei Stellen (Fragen 3 und 4 aus AP4): Einsatzkachel bei 390 px — Ist, A Plakette im Fuß, B zweite Zeile der Zeitspalte; Plakettenzeile der Einsatzansicht bei 700 und 358 px; Leiste 260 Ist, 220 Ist, 220 Variante 1 (nur der Kurzname), 220 Variante 2 (Akkordeon 8 px eingerückt), Schublade 320. Gemessen: Kachel 92 / 124 / 92 px; „BW Hoch" 55 px gegen 55 / 51 / 55 / 55 px frei | 07.09.2026, **Freigabe offen** — Empfehlung: 3 a offen, 3 b nein, 4 Variante 2 |
| `M-S9-08-kurzname-handy.html` / `.png` | dieselben Inhalte untereinander, 400 px, ohne die 700-px-Rahmen und ohne Anmerkungen | 07.09.2026 |
| `M-S9-09-zusammenfuehren-typ.html` / `.png` | Zusammenführen bei verschiedenem Typ (Frage 5 aus AP4): Schritt 1 Ist gegen (b) ablehnen, Schritt 2 Ist gegen (c) Zeile „Typ" und Typ als Zusatz der Wahlzeilen — 390 px | 07.09.2026, **Freigabe offen** — Empfehlung: (c) |
| `M-S9-09-zusammenfuehren-typ-handy.html` / `.png` | dieselben vier Rahmen untereinander, 400 px; der Zusatz rutscht unter den Text (Regel aus `style.css` unter 480 px, ins Mockup übernommen) | 07.09.2026 |
| `M-S9-10-standort-loeschen.html` / `.png` | Standort löschen mit Rettungsmitteln ohne Standortpflicht (Frage 6 aus AP4): Rückfragedialog bei 512 und 358 px, Ist (6 mitgelöscht) gegen (b) (5 mitgelöscht, 1 bleibt und wird genannt); Karte „Ohne Standort" danach mit 2 gegen 3 Einträgen | 07.09.2026, **Freigabe offen** — Empfehlung: (b), in AP5 |
| `M-S9-10-standort-loeschen-handy.html` / `.png` | Dialog 358 px und Karte untereinander, 400 px, ohne den 512-px-Rahmen | 07.09.2026 |
| `M-S9-04-bg-dlg-*.png` | Kartenhintergründe der Dialoge (528 × 300 und 326 × 300 bei Zoom 11; 326 × 300 bei Zoom 14, Sulzberg) | — |
| `M-S9-01-bg-desktop.png`, `M-S9-01-bg-mobil.png` | Kartenhintergründe der beiden Rahmen (800 × 520 bei Zoom 12, 358 × 160 bei Zoom 11, Kempten) | — |

**Kartenhintergründe:** Kacheln von `tile.openstreetmap.org`, © OpenStreetMap-
Mitwirkende, ODbL — nur für diese Mockups zusammengesetzt; die Anwendung lädt
Kacheln wie bisher zur Laufzeit und nie aus dem Repositorium.

**Bilder bis M-S9-07:** gerendert mit `wkhtmltoimage 0.12.6` (QtWebKit) bei
1280 bzw. 400 px Breite, danach auf 256 Farben reduziert (Floyd-Steinberg), damit sie
unter 1 MB bleiben. QtWebKit kennt kein woff2 — für den Render lagen die
Schriften als TTF vor; die HTML-Dateien hier verweisen auf die woff2 des
Repositoriums und sehen in jedem aktuellen Browser gleich aus. Die
Maßangaben in den Maßleisten sind am Render nachgemessen (isolierte
Zustände ohne Schatten): Ist 36/48/60/32, V1 32/32/38/28, V2 32/32/32/28,
V3 28/34/40/28 px (ohne / Start / beide / Einsatzort).

**Bilder ab M-S9-08:** gerendert mit Chromium über Playwright (ganze Seite,
1280 bzw. 400 px, Gerätefaktor 1), das woff2 unmittelbar — keine
Farbreduktion nötig, die sechs Dateien liegen zwischen 169 und 514 KB.
Nachgemessen am Render: in allen sechs Dateien **0 Elemente über dem rechten
Rand** (722 / 210 / 238 Elemente bei 1280 px, 649 / 180 / 171 bei 400 px);
die Zahlen in den Anmerkungen von M-S9-08 (Kachelhöhen, freie Breite neben
dem Datum, Textbreite „BW Hoch") stammen aus `getBoundingClientRect()`
desselben Renders. Die Handy-Fassungen lassen die Rahmen weg, die breiter
als 376 px sind (700-px-Plakettenzeile, 512-px-Dialog), und die
Anmerkungen.

**Zeichen in M-S9-02:** Tabler Icons (MIT) aus dem Outline- und dem Filled-Satz
(`mountain`, `ticket`, `tent`, `building-stadium`, `confetti`,
`dots-circle-horizontal`, `help-circle`, `circle-dotted`), am 06.09.2026 aus
`github.com/tabler/tabler-icons` geholt; die gefüllte „…"-Scheibe und die
Strichzeichnung des Hubschraubers (Variante B) sind eigene Zeichnungen im
Tabler-Raster; die Bildmarken sind `server/assets/images/gen-em_logo_*.svg`,
einfarbig gesetzt. Was übernommen wird, kommt mit Herkunft in `Design.md` 8
und `Lizenzen.md`.
