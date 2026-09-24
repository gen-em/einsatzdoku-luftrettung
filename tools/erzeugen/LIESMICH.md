# Erzeuger

Sieben Befehle, die Dateien **herstellen** — keine Prüfmittel (E-PK-24).
Anlass: entfällt — Erzeuger (E-BR-05)

## Aufruf

```bash
bash tools/erzeugen/erzeugen.sh <name> [zusatz…]   # --liste
```

**Kein `alle`:** Sie schreiben ins Repositorium, jeder einzeln und auf Zuruf.

## Was es misst

Nichts — es erzeugt:

| Name | erzeugt |
|---|---|
| `design` | die Tabellen in `docs/Design.md` (CLAUDE.md 5) |
| `logos` | die Favicons **aus** den Logodateien |
| `uhr-bilder` | die vorgerasterten Bildmarken der Uhr-App |
| `geraetemodelle` | `server/geraetemodelle.php` (+ `nachaufloesen`) |
| `wegwerfdomains` | die Liste der Wegwerf-Mailanbieter (Nr. 230) |
| `pruefkonten` | 300+ Konten in der örtlichen Anlage (P-P3-16) |

## Was es braucht

`design`, `logos`, `uhr-bilder` nur die Quellen. `geraetemodelle` und
`wegwerfdomains` brauchen **Netz**, `pruefkonten` eine Installation.

## Erwartete Zahl

Keine — ein Erzeuger meldet, was er geschrieben hat. Der Prüfwert steht
woanders: Ändert `design` etwas, war `docs/Design.md` von Hand angefasst
worden (CLAUDE.md 5). `wegwerfdomains` schreibt **erst auf Zuruf**.

## Was es nicht kann

Nichts prüfen — dafür `tools/quelltext/` und `tools/proben/`.
