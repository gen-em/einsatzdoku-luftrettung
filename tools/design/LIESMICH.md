# Die Tabellen von `docs/Design.md` aus den Quellen erzeugen

Wird **nicht ausgeliefert** (`tools/` ist vom Deploy ausgenommen).

## Wozu

`docs/Design.md` ist die verbindliche Gestaltungsrichtlinie. Vier ihrer
Kapitel bestehen im Kern aus einer Tabelle, und jede dieser Tabellen zählt
etwas, das sich mit dem Code ändert: 87 Token, 19 Medienblöcke, 44
Symboldateien, 32 Bausteine.

**Eine abgeschriebene Tabelle ist am Tag nach dem Schreiben falsch.** Wer ein
Token ergänzt, denkt nicht an das Dokument; wer eine Schwelle verschiebt,
findet die Stelle nicht. Nach drei Paketen stimmt keine Zahl mehr, und die
Richtlinie ist das, was sie am wenigsten sein darf: eine Quelle, der man nicht
trauen kann.

Dieses Werkzeug liest die Zahlen deshalb aus dem Quelltext und setzt daraus
fertiges Markdown. Was es ausgibt, wird in `Design.md` eingesetzt — mit dem
Vermerk, der über jeder erzeugten Tabelle steht:

```
<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->
```

## Aufruf

```
python3 tools/design/tabellen.py token       # Kapitel 4
python3 tools/design/tabellen.py schwellen   # Kapitel 7
python3 tools/design/tabellen.py symbole     # Kapitel 8
python3 tools/design/tabellen.py bausteine   # Kapitel 9
```

Jeder Aufruf schreibt nach `stdout`. Kein Aufruf ändert eine Datei — das
Einsetzen ist Handarbeit und soll es bleiben, weil beim Einsetzen die
**Prosa** daneben mitgelesen wird.

## Was woher kommt

| Aufruf | Quelle | liest |
|---|---|---|
| `token` | `server/assets/style.css`, Block `:root` | Name, Wert, Zahl der `var()`-Verweise. Die Gruppenüberschriften (**Flächen**, **Schrift**, …) stammen aus den Kommentaren `/* ---- Flächen ---- */` im Stylesheet selbst — es gibt keine zweite Gruppenliste, die veralten könnte. |
| `schwellen` | dieselbe Datei, die `@media`-Blöcke | Abfrage und Zahl der Regelblöcke je Breite |
| `symbole` | `server/assets/images/symbole/` und die Verweise darauf | Dateiname, Tabler-Name, Verwendungen |
| `bausteine` | `server/ui.php` | Funktionsname, Klasse, Zeilennummer, Zahl der Unterklassen im Stylesheet |

## Die eine Stelle, die nicht mechanisch ist

Der Klassenname eines Bausteins folgt in der Regel dem Funktionsnamen. Zwei
Schritte reichen für die meisten: `ui_` abschneiden, und die Endungen
`_start`, `_ende`, `_markup` abschneiden — so wird aus `ui_karte_start` die
Klasse `.karte` und aus `ui_meldung_markup` die Klasse `.meldung`.

Neun Bausteine folgen dieser Regel nicht, fünf weitere haben gar keine eigene
Klasse. Beide Gruppen stehen als benannte Abbildung **`ABWEICHEND`** im
Skript:

| Funktion | Klasse |
|---|---|
| `ui_zeilenaktionen()` | `.zeile-aktionen` |
| `ui_speichern_leiste()` | `.speichern` |
| `ui_abbruch()` | `.rahmen` |
| `ui_geruest_start()` / `_ende()` | `.inhalt` |
| `ui_leiste_diensttage()` / `ui_leiste_einstellungen()` | `.leiste-liste` |
| `ui_einstellungen_uebersicht()` | `.uebersicht-block` |
| `ui_ortsfeld()` | `.ortsfeld-zeile` |
| `ui_seite_start()`, `ui_seite_ende()`, `ui_favicon()`, `ui_logo()`, `ui_krypto_bootstrap()` | keine — Hüllenfunktionen ohne eigenes Element |

Sie stehen dort **sichtbar und mit Kommentar**, statt als stille Heuristik,
die beim nächsten Baustein rät. Wer einen Baustein hinzufügt, dessen Klasse
den beiden Schritten oben nicht folgt, trägt ihn dort ein.

Eine zweite Feinheit steht im Skript daneben: Trägt die Klasse selbst keine
Regel, wohl aber ihre **Familie**, zählt die Familie. `.ortsfeld` hat keine
Regel, `.ortsfeld-zeile` schon — der Baustein ist gestaltet, nur trägt seine
Hülle keine eigene Regel.

## Grenzen

- **Es zählt Bausteine in `ui.php`.** Ein Baustein, der anderswo lebt (das
  Aktionsblatt sitzt in `assets/blatt.js`), fällt dieser Zählung nicht auf.
  `Design.md` beschreibt ihn deshalb von Hand — Kapitel 9.11.
- **Es liest Text, keine Laufzeit.** Ein Token, das nur JavaScript über
  `getComputedStyle` holt, erscheint als „ungenutzt". Die Zeile „Ungenutzt"
  der Tokentabelle ist deshalb **keine Streichliste**: Kapitel 4 erklärt zu
  jedem Namen darin, warum er dort steht. Genau diese Frage hat in O12 den
  Fund F-P3-BC gebracht — zwei Token waren wirklich unbenutzt, und dahinter
  stand eine Leiste, die zwei Pakete lang zu schmal war.
- **Es prüft nicht, ob die Richtlinie stimmt**, sondern nur, ob ihre Zahlen
  stimmen.

## Wann laufen lassen

Nach jeder Änderung an `:root`, an den Medienblöcken, am Symbolvorrat oder an
`ui.php` — und die betroffene Tabelle in `Design.md` ersetzen. Im selben
Paket, nicht später (`CLAUDE.md` §9).
