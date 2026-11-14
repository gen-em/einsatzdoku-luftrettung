# Rundlauf der Uhr

Eine **Prüffassung** der Garmin-App (`$CIQ_BASIS/rundlauf-app`, Adresse `127.0.0.1:8443`,
`Rundlauf.mc` legt Einsatz und Ruhesegment an) koppelt im Simulator an das Demo-Konto,
sendet verschlossen, `pruefen.php` öffnet. **Anlass: Nr. 43** (Hüllen über den ganzen Weg),
**Nr. 431** (die drei letzten Arten: abgemeldete Uhr), **Nr. 577** (ohne ART und `--schrumpfen`:
Kopplung mit Vergleichswert und Fassung 2, `fassung2.py` rechnet mit der Referenz; die übrigen
Arten messen die alte Kopplung).

## Aufruf

```bash
bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [ART] [geraet]   # Vorgabe fenix6pro
```

ART: `--nachholen`, `--schrumpfen`, `--reaktivieren`, `--neukoppeln`, `--fremdkonto`; im Prüfstand (`uhr-rundlauf`) alle fünf. `RUNDLAUF_ANTWORTEN=1` schreibt jede Antwort samt Rumpf in die Konsole (`RL-ANTWORT`, KG-06).

## Was es misst

Ohne ART: 600 Punkte an den Rändern in zwei Stücken, drei Phasen, Reanimation,
Ruhestück, Wert für Wert gegen die geöffneten Hüllen. `--nachholen`: ohne
Kontoschlüssel gekoppelt, nichts gesendet, START halten holt ihn. `--schrumpfen`:
zwei geparkte Pakete verworfen während der letzten Anfrage, kein Absturz.
`--reaktivieren`: Gerät deaktiviert (403, kein Schlüssel, nichts kommt an), aktiviert (ohne
Anlass weiter nichts), Sync-Seite neu geöffnet (Schlüssel, alles kommt an). `--neukoppeln`:
Schlüssel geholt, Senden 30 s angehalten, gelöscht, trotz Rückstand getrennt (seit KG-08
8 s ohne Code), mit zweitem Halten neu gekoppelt an dasselbe Konto — die Pakete kommen an. `--fremdkonto`: ebenso, das Konto trägt
einen anderen gültig versiegelten Schlüssel — nichts kommt an, Verwerfen nach Rückfrage.

## Was es braucht

Simulator, örtliche Anlage mit eigener CA (`Technik.md` 5.6), Demo-Konto mit Schlüsselpaar; stellt alles zurück, auch nach Abbruch.

## Erwartete Zahl

Je Lauf `12 Pruefungen, 0 Fehlschlaege` (in Fassung 2 ohne ART 15, dazu `Kopplung mit Vergleichswert: 3`); dazu schrumpfen 3, reaktivieren 2, neukoppeln 2; fremdkonto allein 4 — je 0 Fehlschläge (neukoppeln und fremdkonto je eine mehr seit KG-08).

## Was es nicht kann

Venu 3s (Wischweg nicht ausgemessen), echtes GPS, Zeit und Speicher einer echten Uhr; Löschen und Sperren nur per SQL.
