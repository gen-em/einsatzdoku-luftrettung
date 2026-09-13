# Prüfdokument — Mockup-Runde (Vorlage)

*Angelegt am 13.09.2026 (Fable) mit `Konzept-Mockup-Runde.md`; **die Zahlen
trägt die Umsetzung ein**, nachdem F-MR-1 bis F-MR-13 beantwortet sind.
Alles in `[eckigen Klammern]` ist auszufüllen.*

> | | |
> |---|---|
> | Stufe | **Web [x.y.0]** — Nebenstufe (neue Darstellungen). Keine Migration. Uhr und Android unberührt |
> | Punkte | Backlog **Nr. 41, 42, 45, 124** erledigt |
> | Neu entstanden | Token `--symbol-text`, `--karte-gross`; **`--dauer` von .18s auf .24s** (alle Bewegungen); Klassen `.symbol-text`, `.geo-gross`; Symbole `karte-gross.svg`, `karte-breit.svg` (54., 55.); Regel `.imp-daygroup`; Knopf offen = `--orange-hell`/`--orange-tief` (D4); Ausnahme `'✕'` in `ausnahmen.md`; Streichliste `imp-warn` |
> | Prüfumgebung | [Container: PHP, MariaDB, Chromium/Playwright — Fassungen] |
> | Ergebnis | [ ] |

## 0. Was NICHT geprüft werden konnte

- **Die Bewegung des Blatts (Nr. 124) auf einem echten Gerät.** Der
  Bilderlauf zeigt Ruhezustände; ob 240 ms auf einem S24 als „kommt von
  unten" gelesen werden, sagt erst Punkt 1 der Prüfliste — und ob die
  Schublade mit 240 statt 180 ms noch flink genug wirkt (Punkt 1a).
- **`localStorage` der Kartengröße über Browserwechsel** — gilt je Browser,
  nicht je Konto; so gewollt (F-MR-8). [Bestätigen.]
- [Weiteres.]

## 1. Zusagen, die sich geändert haben

- `Design.md` 9.12 (E-P3-27): Weg (b) ergänzt — Knopf markiert, Blatt fährt auf.
- P-P3-03 „null Unicode-Symbole": erreicht mit einer begründeten Ausnahme (`'✕'`-Rückfall).
- [Weiteres.]

## 2. Maschinell — Mittel und Zahl

| Mittel | Vorher (Backlog-Runde 3) | Soll | Ergebnis |
|---|---|---|---|
| `tools/vollstaendigkeit/pruefen.py` — `[offen]` | 2 | **0** | [ ] |
| — Unicode-Zeichen als Symbol, echte Treffer | 3 (alle bekannt) | **0** echte; Ausnahme `'✕'` eingetragen; Restzahl (Kommentare/Typografie) [n] genannt | [ ] |
| — Hexfarben außerhalb `:root` | 0 | **0** (Hover des Chips als Token) | [ ] |
| — Symboldateien | 53 | **54**, alle mit Anker `id="i"` und Verweis | [ ] |
| `tools/screenshots/aufnehmen.mjs` — Importvorschau, Einsatzformular, Suche, Tagesübersicht (klein/groß), Seiten mit `data-blatt` | — | 8 Breiten je Seite, **0** waagerechter Überlauf, Knopfhöhen ≥ 44 px | [ ] |
| `tools/screenshots/kontrast.py` | 0 verfehlt | **0** verfehlt (Orange-Symbol auf `--orange-hell`, Blau-hell-Knopf auf Dunkelblau: Werte nennen) | [ ] |
| `tools/stilvergleich/` (Browser) | — | Abweichungen nur auf den berührten Seiten, alle erklärt | [ ] |
| Klickprobe | 40 von 40 | **40 von 40** + [n] neue Wege (Blatt auf/zu, Karte groß/klein) | [ ] |
| Kreisläufe csv/edbak (R24) | 0 / 0 | **0 unerklärt, 0 ungenutzt** | [ ] |
| Wortliste | 0 / 0 | **0 / 0** | [ ] |

## 3. Im Browser

- Importvorschau mit einer abweichenden Crew: Plakette und Auswahl in einer Zeile (Desktop), zwei Zeilen (400 px). [ ]
- Koordinaten-Chip: Ziel 24 × 24 px (DevTools messen), Hover sichtbar, Entfernen wirkt. [ ]
- Suche mit einem unlesbaren Eintrag: Symbol sitzt auf der Grundlinie in 19/15/13 px. [ ]
- Tagesübersicht: groß ↔ klein, Kacheln füllen nach `invalidateSize`, ab 1600 px kein Knopf. [ ]
- Aktionsblatt auf `index.php` und in der Geräteliste (Zeilenaktion): Knopf markiert, Blatt fährt auf, `prefers-reduced-motion` ohne Bewegung. [ ]

## 4. Prüfliste — Auftraggeber

| # | Bedienweg | Erwartet | Wenn nicht |
|---|---|---|---|
| 1 | Am S24: Tagesübersicht, „⋯" tippen | Knopf färbt sich hell-orange (D4), Blatt kommt erkennbar von unten; nach „Abbrechen" beides zurück | Bewegung zu schnell/zu langsam: `--dauer` nachjustieren (nur der Wert) |
| 1a | Am S24: ☰ tippen (Schublade), ein Akkordeon auf- und zuklappen | Beides in 240 ms — fühlt sich gleich an wie das Blatt, nicht träge | Wenn träge: `--dauer` zurück auf .18s und fürs Blatt doch ein eigener Wert — dann als Entscheidung ins Konzept |
| 2 | Am S24: Tagesübersicht, Kartenknopf „vergrößern" | Karte wird deutlich höher, Liste bleibt darunter erreichbar; nach Neuladen bleibt der Zustand | Höhe unpassend: F-MR-7 nachjustieren |
| 3 | CSV-Import mit einer Datei, deren Crew von einem gespeicherten Tag abweicht | Tagesgruppe hat Kopfzeile, Warnung als orange Plakette mit Symbol | — |
| 4 | Einsatz bearbeiten, Koordinaten setzen, Chip-`×` tippen | Ziel trifft sich leicht, Koordinaten weg, Textfeld bleibt | Ziel zu klein: F-MR-6 nachjustieren |
| 5 | Freigabe des Abschlusses | — | — |

## 5. Grenzen · 6. Offen

- Der Bilderlauf misst Ruhezustände; die Bewegung ist nur am Gerät zu bewerten.
- [Fehlerfunde aus Konzept Abschnitt 6.]
