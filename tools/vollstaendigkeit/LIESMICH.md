# Vollständigkeitsprüfung des Stylesheets

Entstanden in P3 (Konzept, Anlage E). Sie beantwortet die Frage, die der
Stilvergleich in einem Redesign nicht beantworten kann.

## Warum es sie gibt

`tools/stilvergleich/` misst, **ob sich etwas geändert hat**. Das ist die
richtige Frage nach einer Aufräumrunde und die falsche in P3: Dort ändert
sich alles, und das Werkzeug liefert Tausende Abweichungen, die niemand
gegen einen Plan hält. Es ruht während P3 (Vermerk in seiner `LIESMICH.md`)
und wird in O12 neu geeicht.

Diese Prüfung stellt zwei andere Fragen:

1. **Ist etwas verlorengegangen?** Das alte Stylesheet kannte 220 Klassen.
   Jede muss am Ende entweder eine Regel im neuen haben oder mit Begründung
   auf der Streichliste stehen. Eine Klasse, die stillschweigend verschwindet,
   nimmt eine Anzeige mit — und das fällt erst auf, wenn jemand die Seite
   aufruft, auf der sie stand.
2. **Steht jeder Wert an der einen Stelle?** Farben, Schriftgrößen und Maße
   gehören in `:root` und nirgends sonst. Das alte Stylesheet hatte 78
   Hexwerte außerhalb, 21 verschiedene Schriftgrößen und die Kopfhöhe fünfmal
   als `50px` fest verdrahtet.

## Aufruf

```
python3 tools/vollstaendigkeit/pruefen.py                 # prüfen
python3 tools/vollstaendigkeit/pruefen.py --ausfuehrlich  # alle Fundstellen
python3 tools/vollstaendigkeit/pruefen.py --vorher        # Sollmenge neu setzen
```

Kein PHP, keine Datenbank, kein Browser — nur Python 3. Rückgabewert ≠ 0,
sobald ein Befund vorliegt.

`--vorher` überschreibt `vorher-klassen.txt` mit den Klassen des **jetzigen**
Stylesheets. Das ist ein Werkzeug für den Anfang einer Phase, nicht für
zwischendurch: Wer es mitten in P3 laufen lässt, erklärt den Zwischenstand
zur Sollmenge und verliert damit genau die Auskunft, um die es geht.

## Die fünf Prüfungen

| Nr. | Was | Sollwert |
|---|---|---|
| 1 | Klassen ohne Gegenstück — aus `vorher-klassen.txt` gegen Stylesheet und `streichliste.md`; dazu die Gegenrichtung (im Markup benutzt, nirgends beschrieben) gegen `ohne-regel.md` | 0 |
| 2 | Werte außerhalb der Token — Hexfarben, `rgb()`, Schriftgrößen, Pixelmaße, `50px`-Reste, `style="…"` in PHP/JS | 0 außer `ausnahmen.md` |
| 3 | Symbole — Inline-SVG mit Pfaden, Unicode-Zeichen als Symbol, Emoji, Verweise auf fehlende Dateien, Dateien ohne Anker `id="i"` | 0 |
| 4 | Knopfregel — jede Höhenangabe an einer `.knopf`-Regel kommt aus `--knopf` | 0 |
| 5 | **Zusagen** — Regeln, die bisher nur im Kopf standen. **Drei:** **native Dialoge** (kein `confirm()`/`alert()`/`prompt()`, auch nicht als `window.`-Aufruf), **Seite ohne Gerüst** (wer `ui_seite_start(` ruft, ruft auch `ui_geruest_start(` **und** `ui_geruest_ende(`) und **fremde Quelle** (jede absolute Adresse in eigenem Quelltext, seit Backlog Nr. 179). Alle drei zählen nur außerhalb von Kommentaren; Ausnahmen mit Grund in `zusagen.md`, in **beide** Richtungen geprüft. Dazu ein **Hinweis**: Gerüst ohne Seitenhülle | 0 Befunde · 0 ungenutzte Ausnahmen |

**Warum „fremde Quelle" jede absolute Adresse meldet und nicht nur die
Ladekonstrukte.** Die naheliegende Regel wäre, nach `src=`, `<link href=`,
`fetch(`, `url()` und `@import` zu suchen — also nach dem, was tatsächlich
etwas lädt. Sie wurde gebaut und gemessen (13.09.2026): **0 Treffer**, während
**fünf** echte Laufzeitquellen im Code standen. Die Kartenkacheln gehen über
`L.tileLayer(...)`, die Anschrift des Adressdienstes ist eine PHP-Konstante —
beides sieht kein Ladekonstrukt. Gemeldet wird deshalb **jede** absolute
Adresse in `server/**/*.{php,js,css}` (ohne `vendor/`, `fonts/`, `demo/`), und
`zusagen.md` trägt die Begründung. **Das verschiebt die Arbeit in die Liste,
und das ist der Punkt:** Sie nennt heute 15 Adressen mit ihrer Art — vier
gewollte Kachelserver, ein Rückfall im Kartendialog, der Adressdienst als
Vorgabe, sechs Navigationsziele (Lizenz- und Spendenhinweise; ein `<a href>`
lädt nichts), ein XML-Namensraum und zwei Beispieltexte. Wer eine sechzehnte
einführt, muss sie eintragen und begründen. **Die Grenze steht im Code:** Auf
`.css` wird die JS-Lesart des Kommentar-Abtasters angewendet; ein unquotiertes
`url(//host)` hielte er für einen Kommentaranfang. Heute gibt es keines
(gemessen: 0 absolute Adressen in den Stylesheets). **Und was diese Prüfung
NICHT leistet:** Sie sieht den Quelltext, nicht die Laufzeit. Eine
Content-Security-Policy schickt die Anwendung nicht (Backlog Nr. 181).

**Warum das Kriterium `ui_seite_start(` heißt und nicht „bindet die Wache ein".** Die naheliegende Regel („bindet `require_admin()` oder `auth_guard.php` ein und ruft kein Gerüst") liefert **15** Treffer, und alle 15 sind richtig so: Bibliotheken, Endpunkte ohne Seite, Seiten vor der Anmeldung, der Notausgang. Ein Mittel, das mit 15 Rot anfängt, wird nie wieder gelesen. `ui_seite_start()` dagegen ist der Anfang **jeder** Seitenhülle — wer ihn ruft, gibt eine Seite aus. **Der Hinweis in der Gegenrichtung hat sich sofort bezahlt gemacht:** Er zeigte auf `apk.php`, dessen 404-Seite ohne `<!doctype>` und ohne Stylesheet hinausging (behoben mit Web 19.3.1).

Dazu die **Ausgabe**: je Prüfung Zahl und Liste mit `Datei:Zeile`, Rückgabewert ≠ 0 bei Befund. Sie war bis Web 19.3.1 als „Prüfung 5" mitgezählt — sie prüft aber nichts, sie zeigt. Seit Backlog Nr. 47 steht an der Fünf eine echte Prüfung.

Zusätzlich als **Hinweis** (kein Befund): Regeln im Stylesheet, deren Klasse
im Markup nicht vorkommt, und Symboldateien, auf die nichts verweist. Beides
kann richtig sein — eine Klasse kann zur Laufzeit zusammengesetzt werden, ein
Symbol kann für ein späteres Paket schon dabeiliegen.

## Die vier Hilfslisten

`streichliste.md`, `ausnahmen.md` und `ohne-regel.md` sind Markdown-Tabellen,
damit ein Mensch sie liest. Alle drei verlangen eine **Begründung**; ein
Eintrag ohne Grund ist keiner, sondern ein weggedrücktes Ergebnis.

| Liste | wofür | Vermerke |
|---|---|---|
| `streichliste.md` | Klassen des **alten** Stylesheets, die es nicht mehr gibt — je mit dem Baustein, der sie ersetzt | `[bleibt]` für die wenigen, die im Markup stehen bleiben (Skriptanker) |
| `ausnahmen.md` | Werte, die außerhalb der Token stehen dürfen — Geometrie statt Gestaltung | — |
| `zusagen.md` | Stellen, an denen eine **Zusage** bewusst nicht gilt — vier Spalten: Prüfung, Datei, Muster, Grund. Die zweite Spalte ist die **Datei**, nicht die Zeile: Eine Zeilennummer altert mit dem nächsten Paket (genau daran ist der Eintrag zu `phasen-name` in `ohne-regel.md` gealtert) | — |
| `ohne-regel.md` | Klassen im Markup, die **keine** Regel brauchen | `[bleibt]` = begründet, kein Befund · `[offen]` = Frage offen, bleibt Befund unter eigener Überschrift |

Die erste Spalte von `ausnahmen.md` ist der **Eigenschaftsname**, nicht die
Zeilennummer — so überlebt die Liste jede Umsortierung des Stylesheets.

**Warum `ohne-regel.md` überhaupt nötig war** (O12, Backlog Nr. 39): Die
Gegenprobe „im Markup, aber ohne Regel" hat in O11 einen echten Fund gemacht —
der Export-Knopf trug `btn-primary`, eine Klasse ohne Regel, und war 23 px
hoch statt 44 (F-P3-BA). Nur stand dieser eine Fund zwischen 28 falschen:
acht Bruchstücken zusammengesetzter Klassennamen und zwanzig Skriptankern.
Eine solche Liste wird nach dem dritten Mal nicht mehr gelesen, und dann
findet sie auch den echten Fund nicht. Seit O12 zählt sie die begründeten
Fälle nur noch, meldet die ungeklärten einzeln — und meldet **ihre eigenen
verwahrlosten Einträge**: Wessen Klasse inzwischen eine Regel hat oder aus dem
Markup verschwunden ist, steht als „Eintrag ungenutzt" da.

## Was das Werkzeug nicht kann

- **Es misst Text, keine Darstellung.** Ob eine Regel richtig aussieht, sagt
  nur der Browser. Dafür ist `tools/screenshots/` da.
- **Klassen aus zusammengesetzten Zeichenketten** (`'imp-' + art`) erkennt es
  nicht als Literal und meldet sie in der Gegenrichtung nicht. Das ist
  Absicht: Die erste Fassung zählte jedes Wort im Quelltext als Klasse und
  kam auf 14 784 — eine Zahl, mit der niemand etwas anfangen kann. Gemeldet
  wird nur, was als Literal belegt ist.
- **Der Vorher-Stand ist die Klassenmenge des alten Stylesheets**, nicht die
  des Markups. Das ist die rauschfreie Menge; die Markupseite läuft als
  Gegenprobe mit.

## Erhebung vom 26.08.2026 (Beginn P3)

| | |
|---|---|
| Klassen im alten Stylesheet | **220** |
| Klassen im Markup als Literal belegt | 208 |
| davon ohne Regel und ohne Streichung | **22** — Bestandsfund, siehe Konzept 9.2 |
| Hexfarben außerhalb `:root` | **78** |
| `rgb()`/`rgba()` außerhalb `:root` | 8 |
| Schriftgrößen außerhalb der Skala | 71 Stellen |
| Pixelmaße außerhalb der Token | 154 |
| `50px`-Reste | **5** |
| `style="…"` in PHP/JS | **14** |
| Inline-SVG mit Pfaden | **5** |
| Unicode-Zeichen als Symbol | 147 |
| Emoji im Markup | 80 |
