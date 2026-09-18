# Mockups zum Konzept P5b

Je Darstellung eine HTML-Datei, die das **echte** `server/assets/style.css`
einbindet (relativer Pfad) und die Schriften des Repositoriums nutzt; neue
Klassen (Vorschlag für `style.css`) stehen im `<style>`-Block der Datei unter
„Vorschlag für style.css". Dazu die gerenderten Bilder.

| Datei | Was | Stand |
|---|---|---|
| `M-P5b-01-dokumentseite.html` / `.png` | E-P5b-08: `hilfe.php` eingeloggt (Inhaltsverzeichnis links, sticky, Suchfeld, Markdown-Elemente im Stil der Anwendung), `ueber.php` öffentlich, Anmeldeseite mit Fußzeile in zwei Betriebsarten; Hilfe-Symbol im Kopf | 17.09.2026, **V1 freigegeben 17.09.2026** |
| `M-P5b-01-dokumentseite-handy.html` / `.png` | dieselben Seiten bei 376 px; Verzeichnis als aufklappbares „Inhalt" | 17.09.2026 |
| `M-P5b-02a-registrierung.html` / `.png` | E-P5b-05, -13, -15: `registrieren.php` (drei Häkchen, Honeypot sichtbar gemacht), Antwortzustand, `bestaetigen.php` wartet, `einwilligung.php` (Tor) | 17.09.2026, **V1 freigegeben 17.09.2026** |
| `M-P5b-02a-registrierung-handy.html` / `.png` | dieselben Karten bei 376 px | 17.09.2026 |
| `M-P5b-02b-erststart.html` / `.png` | E-P5b-09, -19: Erststart als Karte über der Tagesübersicht, offen und teils erledigt | 17.09.2026, **V1.1 freigegeben 17.09.2026** — Anmerkungen des Auftraggebers eingearbeitet: Aktionen und Plaketten rechtsbündig in einer Spalte, Häkchen links, „Später" rechts, alles vertikal zentriert (Konzept Abschnitt 6) |
| `M-P5b-02b-erststart-handy.html` / `.png` | bei 376 px | 17.09.2026 |
| `M-P5b-02c-rueckfrage.html` / `.png` | E-P5b-09, -10, -20, -21: Konto-Rückfrage in drei Zuständen (Frage, Passwort, neuer Schlüssel mit Druckknopf), Betreiber-Rückfrage mit vier Gruppenfeldern und Fehlzustand | 17.09.2026, **V1 freigegeben 17.09.2026** |
| `M-P5b-02c-rueckfrage-handy.html` / `.png` | drei Dialoge bei 376 px | 17.09.2026 |
| `M-P5b-02d-notfallblatt.html` / `.png` | E-P5b-09: Notfallblatt als Druckansicht A4 (794 px), Muster des Schlüsselblatts aus S10 | 17.09.2026, **V1 freigegeben 17.09.2026** |

**Bilder:** gerendert mit `wkhtmltoimage 0.12.6` (QtWebKit) bei 1440 bzw.
400 px (Notfallblatt 860 px), danach auf 256 Farben reduziert
(Floyd-Steinberg). QtWebKit kennt kein woff2 — für den Render lagen die
Schriften als TTF vor (aus den woff2 des Repositoriums umgewandelt); die
HTML-Dateien verweisen auf die woff2 und sehen in jedem aktuellen Browser
gleich aus. QtWebKit kennt auch kein `:has()`; die Häkchen-Zeilen tragen
deshalb im Mockup eine eigene Klasse `haken`, die Anwendung nutzt die
Regel aus `style.css`.

**Neue Symbole:** „hilfe" (Tabler „help-circle") und „drucker" (Tabler
„printer"), beide MIT wie der übrige Vorrat — bei der Umsetzung nach
`server/assets/images/symbole/` mit Quellvermerk.

**Neue Klassen (Vorschlag):** `.fuss-anmeldung`, `.doku`, `.doku-nav`,
`.doku-text`, `.doku-stand`, `.doku-handy`, `.erststart` mit `.schritt-nr`,
`.gruppen`, `.blatt-druck`. Kontraste sind mit `tools/screenshots/kontrast.py`
nachzumessen, bevor sie in `style.css` landen.
