# Proben gegen die örtliche Installation

Zwanzig Prüfungen gegen die laufende Anlage (E-PK-24).

## Aufruf

```bash
bash tools/proben/proben.sh <name> [zusatz…]   # alle · --liste
```

## Was es misst

`ingest` Annahme und Prüfschicht · `spur` beide Spurablagen ·
`jobs` Hintergrundjobs und Register · `kopplung` Handy und Uhr ·
`wartung` Wartungsmodus und Torwächter · `raten` Ratenschutz ·
`mail` Versand und Fehlerweg · `versand` Sicherungsziele ·
`komplett` Komplettsicherung · `wiederherstellung` Rückweg ·
`gpx` Export gegen das Schema · `geraete` Gerätevertrag ·
`verbindung` Verbindungsgrenze · `anteil` Server-Anteil ·
`rechtstexte` Texte gegen die Quelle · `freigabe` Schlüsselweitergabe ·
`container` Format der Sicherungsdatei · `frist` Inhaltsschlüssel ·
`abmelden` was liegen bleibt · `csp-browser` Richtlinie zur Laufzeit.

**Den Anlass je Probe** (Grundsatz 5) trägt der Kopfkommentar ihrer Datei.

## Was es braucht

Eine örtliche Installation (`bash tools/sandbox/hochfahren.sh`); die
Node-Proben zusätzlich einen Browser aus `/opt/pw-browsers`.

## Erwartete Zahl

`alle` fährt **19** und lässt `versand` aus (verlangt einen Pfad; der
Grund steht im Läufer). Stand 22.09.2026: **13 von 19 grün**; die sechs
roten sind Sachbefunde, keine Verdrahtung — Prüfpunkt P-PK-20.

## Was es nicht kann

Nichts ohne Anlage — dafür `tools/quelltext/`. Kein Bilderlauf, keine
Bedienprobe, kein Mengenlauf; die haben eigene Ordner.
