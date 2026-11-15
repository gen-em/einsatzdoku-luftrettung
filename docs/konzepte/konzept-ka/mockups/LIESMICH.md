# Mockups zum Konzept KA

Je Darstellung eine HTML-Datei, gerendert aus den Bausteinen von
`server/ui.php` und gestaltet mit `server/assets/style.css` (relativer
Pfad), dazu das Bild. Keine fremde Quelle.

| Datei | Was | Stand |
|---|---|---|
| `M-KA-01-kopplung.html` / `.png` | Kopplung mit Vergleichswert im Reiter Geräte (KA-06d, E-KA-44, E-KA-61): A Rückfrage · B Warten auf das Gerät · C Vergleichswert prüfen · D Am Gerät bestätigen · E Wert anders oder Zusage passt nicht · F ältere App ohne Geräteschlüssel. Nur vorhandene Bausteine | 13.11.2026, **freigegeben 13.11.2026** samt Zustand F (E-KA-63) |
| `M-KA-02-kopplung-handy.html` / `.png` | Kopplung mit Vergleichswert in der Android-App (KA-06c, E-KA-44): A Code · B Vergleichswert · C Ja gegeben, Browser offen · D Gekoppelt mit „Herkunft gesichert“ · E im Browser abgelehnt · F Konto ohne Schlüssel (F-KA-108). Nur vorhandene Bausteine der App; Farben aus `android/gemeinsam/res/values/rollen.xml`, Schrift ersatzweise Open Sans | 14.11.2026, **freigegeben 14.11.2026** (E-KA-66) |
| `M-KA-03-befunde.html` / `.png` | Befunde in der Einsatzansicht und „neu koppeln“ am Handy (KA-08, Web 25.0.0, E-KA-70, -80, -81): A offen nach dem Abschluss · B Uhrzeit prüfen mit Rückweg · C zurückgelegt · D vom Browser übernommen · E ohne Herkunft · F Handy ohne Geräteschlüssel · G gekoppelt (zum Vergleich). Nur vorhandene Bausteine (Meldung mit Aktion) | 15.11.2026, **zur Freigabe** |

**Bild:** gerendert mit Chromium (Playwright) bei 1328 px (M-KA-02: 1180 px), der Ring
angehalten, danach auf 256 Farben reduziert (Floyd-Steinberg).
