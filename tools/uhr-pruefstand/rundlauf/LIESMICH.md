# Rundlauf der Uhr

Eine **Prüffassung** der Garmin-App (`$CIQ_BASIS/rundlauf-app`, Adresse `127.0.0.1:8443`,
`Rundlauf.mc` legt Einsatz und Ruhesegment an) koppelt im Simulator an das Demo-Konto,
sendet verschlossen, `pruefen.php` öffnet. **Anlass: Nr. 43** (Hüllen über den ganzen Weg),
**Nr. 431** (die drei letzten Arten: abgemeldete Uhr), **Nr. 577** (Kopplung mit Vergleichswert
und Fassung 2, `fassung2.py` rechnet mit der Referenz — seit KA-22 in allen Arten; bis Uhr 5.1.0
massen die übrigen die alte Kopplung ohne Geräteschlüssel).

## Aufruf

```bash
bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [ART] [geraet]   # Vorgabe fenix6pro
```

ART: `--nachholen`, `--schrumpfen`, `--reaktivieren`, `--neukoppeln`, `--fremdkonto`; im Prüfstand (`uhr-rundlauf`) alle fünf. `RUNDLAUF_ANTWORTEN=1` schreibt jede Antwort samt Rumpf in die Konsole (`RL-ANTWORT`, KG-06).

## Was es misst

Ohne ART: 600 Punkte an den Rändern in zwei Stücken, drei Phasen, Reanimation,
Ruhestück, Wert für Wert gegen die geöffneten Hüllen. `--nachholen`: Konto ohne
Schlüssel beansprucht — die Uhr sagt Nein, kein Gerät, nichts kommt an; Schlüssel zurück,
START halten koppelt neu, alles kommt an. `--schrumpfen`:
zwei geparkte Pakete verworfen während der letzten Anfrage, kein Absturz.
`--reaktivieren`: Senden 30 s angehalten, Gerät deaktiviert (403, nichts kommt an), aktiviert (ohne
Anlass weiter nichts), Sync-Seite neu geöffnet (alles kommt an). `--neukoppeln`:
Senden 30 s angehalten, gelöscht, trotz Rückstand getrennt (seit KG-08
8 s ohne Code), mit zweitem Halten neu gekoppelt an dasselbe Konto — die Pakete kommen an. `--fremdkonto`: ebenso, das Konto trägt
einen anderen gültig versiegelten Schlüssel — nichts kommt an, Verwerfen nach Rückfrage.

## Was es braucht

Simulator, örtliche Anlage mit eigener CA (`Technik.md` 5.6), Demo-Konto mit Schlüsselpaar; stellt alles zurück, auch nach Abbruch.

## Erwartete Zahl

Je Lauf `15 Pruefungen, 0 Fehlschlaege` und `Kopplung mit Vergleichswert: 3` (neukoppeln 6); dazu nachholen 2, schrumpfen 3, reaktivieren 2, neukoppeln 2; fremdkonto `Kopplung mit Vergleichswert: 6` und 4 — je 0 Fehlschläge (seit KA-22; bis dahin in Fassung 1 je 12).

## Was es nicht kann

Venu 3s (Wischweg nicht ausgemessen), echtes GPS, Zeit und Speicher einer echten Uhr; Löschen und Sperren nur per SQL.
