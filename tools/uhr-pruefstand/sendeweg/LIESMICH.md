# Sendeweg der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/sendeweg-app`)
hält jede Anfrage vor dem Absenden an und spielt die Antworten des Servers
selbst vor. **Anlass: Nr. 570, 573, 575, 577, 580** — Kopplung im Dienst, laufende Pakete,
`next_seq`, Wiederholungsstück; 401/403 ohne Fehlerfeld, Frist; Dienst ohne Kopplung,
Trennen; Vergleichswert, Fassung 2; Zähler am Inhalt, kein Senden ohne Herkunft;
seit KA-22 nur noch Fassung 2.

## Aufruf

```bash
bash tools/uhr-pruefstand/sendeweg/sendeweg.sh [geraet]   # Vorgabe fenix6pro
SENDEWEG_WATCH=<verzeichnis> bash tools/uhr-pruefstand/sendeweg/sendeweg.sh   # Gegenprobe
```

## Was es misst

31 Prüfungen in 28 Fällen, je Fall mit leerem Speicher: `Pair.start()` im Dienst
(ungekoppelt, gekoppelt; Gegenprobe: Schlüssel holen bleibt erlaubt), ein
laufender Einsatz nach Verlust der Zugangsdaten und Kopplung mit anderem
Schlüssel, ein Stück nach verlorener Antwort, eine Quittung über das
Gesendete hinaus, eine Antwort ohne `next_seq`; seit KG-06 401 und 403 ohne
Fehlerfeld, 401 `auth` (Gegenprobe), die abgelaufene Frist mit letztem Versuch;
seit KG-08 kurz START ungekoppelt und `Pair.onTrennen(200)` ohne neue Sitzung;
seit KG-03b Wert erst nach `offenlegen`, anderes Paar, 202, abgelehnt,
`huelle: 2`, leeres Schlussstück in Fassung 2; seit KG-09/-10
`z` beim Ablegen, gleich über zwei Sendungen, höher beim Abschluss; ohne Paar oder
Selbsttest kein Senden, Trennen trotz Rückstand, Merker aus `status` bleibt.
Seit KA-22 (Uhr 6.0.0) koppeln die Sendefälle mit Fassung 2 (eine echte Kopplung
im zweiten Takt, ihr Paar je Fall); ohne Merker `huelle: 2` kein Senden (war: Fassung 1),
ohne Paar kein `start`, `400 schluessel` und `beansprucht` ohne N_b mit Meldung.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keinen Server (`127.0.0.1:1`).

## Erwartete Zahl

`Sendeweg: 31 Pruefungen, 0 Fehlschlaege`, rund 1 min. Gegen Uhr 5.0.0 übersetzt sie nicht; drei eingebaute Fehler (KG-09/-10) je 1 bis 3 rot; `Uploader.mc` aus Uhr 5.1.0 → 1 rot, `Pair.mc` aus Uhr 5.1.0 → 3 rot (KA-22).

## Was es nicht kann

Den echten Server (das misst `uhr-rundlauf`), keine echte Uhr, kein echter Funkabriss.
