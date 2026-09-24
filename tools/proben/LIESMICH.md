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
`mail` Versand, Fehlerweg und Rundmail · `versand` Sicherungsziele samt der Archive des Protokolls (Teil 13) ·
`komplett` Komplettsicherung · `wiederherstellung` Rückweg ·
`gpx` Export gegen das Schema · `geraete` Gerätevertrag ·
`verbindung` Verbindungsgrenze · `anteil` Server-Anteil ·
`rechtstexte` Texte gegen die Quelle · `freigabe` Schlüsselweitergabe ·
`container` Format der Sicherungsdatei · `frist` Inhaltsschlüssel ·
`abmelden` was liegen bleibt · `csp-browser` Richtlinie zur Laufzeit ·
`rollen` die Berechtigungsmatrix aus `docs/Technik.md` 4.99p, Zelle für
Zelle · `protokoll` das Archiv des Protokolls: Häppchen, Inhalt ohne IP und
Adressen, Kennung, Aufbewahrung, Download.

**Den Anlass je Probe** (Grundsatz 5) trägt der Kopfkommentar ihrer Datei.

## Was es braucht

Eine örtliche Anlage (`tools/sandbox/hochfahren.sh`); Node-Proben einen
Browser aus `/opt/pw-browsers`; `versand` die Pakete der Gegenstellen (`web`).

## Erwartete Zahl

`alle` fährt **22**. `versand` startet ohne Pfad die Gegenstellen als
Nachbau selbst und hält sie danach an (RP-01); `freigabe`, `rollen` und
`protokoll` legen ihre Konten selbst an und löschen sie wieder. Stand
23.09.2026: **20 von 20 grün**; seit P5c/AP2 (24.09.2026) dazu `rollen`
**87 von 87** und `protokoll` **21 von 21** — die Gesamtzahl misst der
Prüfstand zu P5c/AP2.

## Was es nicht kann

Nichts ohne Anlage — dafür `tools/quelltext/`. Kein Bilderlauf, keine
Bedienprobe, kein Mengenlauf; die haben eigene Ordner.
