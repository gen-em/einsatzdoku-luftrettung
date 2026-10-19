# Mockups zum Konzept S11

Je Darstellung eine HTML-Datei mit den Token aus `server/assets/style.css`
(Auszug im `<style>`), dazu das gerenderte Bild. Die Schriften kommen aus
`server/assets/fonts/` (relativer Pfad), keine fremde Quelle.

| Datei | Was | Stand |
|---|---|---|
| `M-S11-01-ladeanzeige.html` / `.png` | Ladeanzeige, solange der Browser Ortsdaten öffnet oder verschließt (E-S11-27): Einsatzansicht Ist gegen A (Ladeplatz) und B (Laufband); A in vier Zuständen (lädt, wartet auf den Schlüssel, öffnet, Fehler); lange Läufe mit Zahl und Balken (Export, GPX-Archiv, Wartungslauf); Handy 400 px und „Bewegung reduzieren"; Kontraste nachgerechnet | 13.10.2026, **freigegeben 13.10.2026: Variante A**, Schwelle 300 ms, Phasenkarte sofort, Balken mit Zahl (E-S11-35) |
| `M-S11-02-wartungslauf.html` / `.png` | Wo der Wartungslauf erscheint (S11-07, E-S11-68): 1 Startseite still und Karte „GPS-Daten verdichten“ im Reiter Backup (läuft, fertig, Fehler) · 2 nur still · 3 Zustandszeile oben auf der Startseite | 17.10.2026, **freigegeben 17.10.2026: Variante 1** (Reiter Backup, unter den Backup-Karten, läuft von selbst, Titel wie gezeichnet) |

**Bild:** gerendert mit Chromium (Playwright) bei 1280 px, der Ring für das
Bild angehalten, danach auf 256 Farben reduziert (Floyd-Steinberg).
