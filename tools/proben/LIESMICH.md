# Proben gegen die örtliche Installation

Zweiundzwanzig Prüfungen gegen die laufende Anlage (E-PK-24).

## Aufruf

```bash
bash tools/proben/proben.sh <name> [zusatz…]   # alle · --liste
```

## Was es misst

`ingest` Annahme und Prüfschicht · `spur` beide Spurablagen ·
`jobs` Hintergrundjobs und Register · `kopplung` Handy und Uhr ·
`wartung` Wartungsmodus und Torwächter · `raten` Ratenschutz ·
`mail` Versand, Fehlerweg und Rundmail · `versand` Sicherungsziele ·
`komplett` Komplettsicherung · `wiederherstellung` Rückweg ·
`gpx` Export gegen das Schema · `geraete` Gerätevertrag ·
`verbindung` Verbindungsgrenze · `anteil` Server-Anteil ·
`rechtstexte` Texte gegen die Quelle · `freigabe` Schlüsselweitergabe ·
`container` Format der Sicherungsdatei · `frist` Inhaltsschlüssel ·
`abmelden` was liegen bleibt · `csp-browser` Richtlinie zur Laufzeit ·
`rollen` Matrix aus `Technik.md` 4.99p · `protokoll` Archiv.

**Den Anlass je Probe** (Grundsatz 5) trägt der Kopfkommentar ihrer Datei.

## Was es braucht

Eine örtliche Anlage (`tools/sandbox/hochfahren.sh`); Node-Proben einen
Browser aus `/opt/pw-browsers`; `versand` die Pakete der Gegenstellen (`web`).

## Erwartete Zahl

`alle` fährt **22**; Stand 24.09.2026 **22 von 22 grün**. Wer ein Konto
braucht, legt es selbst an; `versand` startet seine Gegenstellen (RP-01).

## Was es nicht kann

Nichts ohne Anlage — dafür `tools/quelltext/`. Kein Bilderlauf, keine
Bedienprobe, kein Mengenlauf; die haben eigene Ordner.
