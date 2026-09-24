# Mockups zum Konzept RW — Runde M-RW-01

Eine Runde, zwei Dateien (Konzept RW, Q-RW-08). Gebaut am 24.09.2026 gegen
den Zweig `claude/p5c-mockups-konzept-4yeomf` (`42c644c`, Web 20.41.0).
**Kein Anwendungscode** — nichts unter `server/` ist angefasst.

**Stand: freigegeben am 24.09.2026, unverändert.** Die Entscheidungen der
Runde stehen im Konzept (E-RW-13, Abschnitt 7).

**Aus dem freigegebenen M-P5c-02b, nicht frei gezeichnet:** Stilblock und
Symbole sind aus `konzept-p5c/mockups/M-P5c-02b-zweitfaktor.html`
übernommen (das ist der Stand, der mit AP5 in `style.css` kommt); die
Anmeldekarte, das Einrichtungstor und die Karte „Zweitfaktor" sind dessen
Rahmen. **Eingefügt ist nur das Neue.** Die HTML-Dateien binden das echte
`server/assets/style.css` über einen relativen Pfad ein; das Erzeugerskript
ist nicht Teil der Lieferung.

## 1. Die Dateien

| Datei | Was |
|---|---|
| `M-RW-01-rueckweg.html` / `.png` | **1** Code-Schritt und Schritt „Wiederherstellungscode" mit dem dritten Verweis „Gerät und Codes verloren?" · **2** der Schlüsselschritt in vier Lagen: leer, Schlüssel vollständig, Tippfehler (Zeichen benannt, M2-06), Schlüssel passt nicht · **3** nach dem Erfolg: Rolle user (angemeldet, Hinweis auf das Profil) und Pflichtrolle (unmittelbar das Einrichtungstor aus M-P5c-02b, Meldung oben) · **4** die Karte „Zweitfaktor" mit der Zeile „Rückweg mit dem Wiederherstellungsschlüssel" und dem Knopf „Rückweg erneuern" in drei Lagen: eingerichtet, ab der nächsten Anmeldung, Pflichtrolle |
| `M-RW-01-rueckweg-handy.html` / `.png` | Code-Schritt, Schlüsselschritt (vollständig, passt nicht) und Kartenzeile bei 376 px |

## 2. Was die Runde entscheiden soll

Keine eigene Frage über Q-RW-01 bis -10 hinaus. Freizugeben ist die Form:
der dritte Verweis, der Schlüsselschritt als Schwester der
Passwort-Reset-Seite, die zwei Wege nach dem Erfolg, die Kartenzeile mit
Plakette und der Knopf „Rückweg erneuern". Die Statuszeile
„Rückweg-Prüfung" (Q-RW-09) ist eine gewöhnliche Statuszeile und hat kein
Bild — wie die Zeilen in M-P5c-02 (d).

## 3. Gemessen

| Was | Ergebnis |
|---|---|
| Waagerechter Überlauf | **0 px** Seite, **0** von **11** Rahmen (1440 px) und **0** von **4** Rahmen (400 px) |
| Knopfhöhe | 1440 px, Zeigergerät: **16 von 16** Knöpfe **36 px** (Sollwert am Zeigergerät ab 1024 px, `CLAUDE.md` 5) · 400 px: **6 von 6** mindestens 44 px |
| Ressourcen, Konsole | **0** fehlende Ressourcen, **0** Konsolenfehler in beiden Dateien (Chromium 141 über einen örtlichen Dateiserver) |
| Neue Klassen, Symbole, Farben | **keine** — deshalb kein Lauf von `kontrast.py`; alle Flächen und Töne sind Bestand oder aus M-P5c-02b |
| Bilder | 1440 px: 653 KB, 400 px: 246 KB — **nicht** farbreduziert (ImageMagick fehlt im Container, `Sandbox-Setup.md` 1) |

**Nicht gemessen:** Firefox und WebKit (die Runde M-P5c-02 hat es auch
nicht; die Motoren starten hier, F-RW-02, aber ein Bild aus ihnen wäre
ohne Vergleichsstand). Kein QR-Test — der QR-Code ist der aus M-P5c-02b.

## 4. Bauart

Erzeugt mit einem Python-Skript aus dem HTML-Stand von M-P5c-02b (Stil,
Symbole, QR-Pfad übernommen), aufgenommen mit Playwright 1.56 / Chromium
141 bei 1440 bzw. 400 px, `de-DE`, ganze Seite. **Nur im Mockup:** der
beige Rahmen, die Rahmentitel, die Fußzeile auf 20.41.0. Namen, Schlüssel
und Codes sind erfunden.
