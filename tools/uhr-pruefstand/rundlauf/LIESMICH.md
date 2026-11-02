# Rundlauf der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/rundlauf-app`,
Adresse `127.0.0.1:8443`, `Rundlauf.mc` legt Einsatz und Ruhesegment an)
koppelt im Simulator an das Demo-Konto, sendet verschlossen, `pruefen.php` öffnet.
**Anlass: Nr. 43** — ob die Hüllen nach Kopplung, `ingest.php` und Datenbank
aufgehen, zeigt nur ein gesendetes Paket. **Anlass: Nr. 431** für die drei
letzten Arten — eine abgemeldete Uhr kam nicht mehr ans Senden.

## Aufruf

```bash
bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [ART] [geraet]   # Vorgabe fenix6pro
```

ART: `--nachholen`, `--schrumpfen`, `--reaktivieren`, `--neukoppeln`, `--fremdkonto`; im Prüfstand (`uhr-rundlauf`) alle fünf.

## Was es misst

Ohne ART: 600 Punkte an den Rändern in zwei Stücken, drei Phasen, Reanimation,
Ruhestück, Wert für Wert gegen die geöffneten Hüllen. `--nachholen`: ohne
Kontoschlüssel gekoppelt, nichts gesendet, START halten holt ihn. `--schrumpfen`:
zwei geparkte Pakete verworfen während der letzten Anfrage, kein Absturz.
`--reaktivieren`: Gerät deaktiviert (403, nichts kommt an), aktiviert (ohne
Anlass weiter nichts), Sync-Seite neu geöffnet (alles kommt an). `--neukoppeln`:
deaktiviert, Schlüssel geholt, gelöscht, trotz Rückstand neu gekoppelt an
dasselbe Konto — die Pakete kommen an. `--fremdkonto`: ebenso, das Konto trägt
einen anderen gültig versiegelten Schlüssel — nichts kommt an, Verwerfen nach Rückfrage.

## Was es braucht

Simulator, örtliche Anlage mit eigener CA (`Technik.md` 5.6), Demo-Konto mit Schlüsselpaar; stellt alles zurück, auch nach Abbruch.

## Erwartete Zahl

Je Lauf `12 Pruefungen, 0 Fehlschlaege`; dazu schrumpfen 3, reaktivieren 2, neukoppeln 1; fremdkonto allein 3 — je 0 Fehlschläge.

## Was es nicht kann

Venu 3s (Wischweg nicht ausgemessen), echtes GPS, Zeit und Speicher einer echten Uhr; Löschen und Sperren nur per SQL.
