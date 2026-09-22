# Zählung — hält jede Sache ihre eine Stelle?

**Entstanden in Schritt 15 (Zentralisierung), AP1** — Konzept
Schritt 15 (Konzept Zentralisierung, mit dem Abschluss am 22.09.2026
gelöscht — Zusammenfassung im Rahmenplan Abschnitt 8),
Entscheidungen E-ZE-03 (das
Zählmittel entsteht hier, nicht in 10c AP3) und E-ZE-24 (ein Register, das
bleibt). Programmentscheidung **R83**.

```
php tools/zaehlung/zaehlen.php                # alle Zeilen messen
php tools/zaehlung/zaehlen.php --selbstprobe  # zählt die Zählung richtig?
php tools/zaehlung/zaehlen.php --stellen      # mit Datei und Zeile je Treffer
php tools/zaehlung/zaehlen.php --zeile=Z19 --stellen   # nur eine Zeile
```

Rückgabe **0** = jede Zeile auf oder unter ihrer Decke · **1** = eine
darüber · **2** = die Zählung kam nicht los (`server/` fehlt, `schema.sql`
unlesbar).

---

## 1. Wozu

R83 sagt: Zentralisiert wird beim **zweiten** echten Verbraucher. Der Beleg,
aus dem die Entscheidung entstand, ist `edbak_groesse_text()` — die Funktion
lag in `adminbackup_lib.php`, und neun fremde Dateien luden die
Backup-Bibliothek nur, um Bytes lesbar zu machen. 43 Aufrufe in zehn Dateien.
Das ist nicht an einem Tag passiert, und es ist niemandem aufgefallen.

Schritt 15 räumt rund vierzig solcher Muster auf. Dieses Werkzeug hält das
Ergebnis fest: **je Muster eine Registerzeile mit einer Decke.** Liegt der
Ist-Wert darüber, schlägt die Zählung an. So kommt eine zweite Stelle nicht
unbemerkt zurück.

**Eine Decke wird nicht angehoben, ohne dass es im Konzept steht.** Wer eine
zweite Stelle braucht, begründet sie dort — nicht in `register.php`.

## 2. Die drei Sichten

Jede Datei wird in drei Fassungen gelesen. Welche eine Registerzeile benutzt,
steht in ihrem Feld `sicht`.

| Sicht | Was drinsteht | Wofür |
|---|---|---|
| `php_ohne_zeichenketten` | Code ohne Kommentare **und** ohne Zeichenketteninhalt | Funktionsaufrufe |
| `php_mit_zeichenketten` | Code ohne Kommentare, Zeichenketten erhalten | SQL und Literale |
| `js_und_inline` | `.js` ganz, aus `.php` die `<script>`-Blöcke — beides ohne Kommentare | JavaScript |

**Warum nicht `grep`.** Ein `grep` über `session_start` findet jede Erwähnung
in jedem Kommentar; die erste Fassung von `tools/sitzungshaertung/` meldete
auf diesem Weg zwei Befunde, und beide waren Kommentarzeilen, die das
Werkzeug selbst beschrieben. Die PHP-Sichten entstehen mit dem **Tokenizer**,
nicht mit einem Muster.

**Warum die JS-Sicht eine eigene ist.** Eine breite Suche nach
`class="meldung` über den Quelltext zählt das PHP-Markup mit — 35 Erwähnungen
in 15 Dateien. In der Sicht `js_und_inline` sind es **8 in 5**. Das Konzept
(1.4) nennt die erste Zahl ausdrücklich „nicht belastbar"; die zweite ist die
Antwort auf die gestellte Frage.

**Zeilentreu.** Alle drei Sichten haben so viele Zeilen wie die Quelle: Was
wegfällt, wird durch Leerzeichen ersetzt, Zeilenumbrüche bleiben stehen. Nur
deshalb zeigt `--stellen` auf die Stelle im Original. Übernommen aus
`tools/wortliste/zerlegen.py`, wo dieselbe Regel und derselbe Grund steht;
die Selbstprobe misst sie über alle 519 Sichten des Bestands nach.

## 3. Das Register

`register.php` gibt ein Feld von Zeilen zurück. Eine Zeile:

```php
['kennung' => 'Z19', 'paket' => 'AP7',
 'beschreibung' => 'edbak_groesse_text( Aufrufe',
 'grund'        => 'Der R83-Beleg: neun fremde Dateien laden die …',
 'sicht'  => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel'  => ['art' => 'aufruf', 'namen' => ['edbak_groesse_text']],
 'start'  => 42, 'decke_jetzt' => 42, 'decke_ziel' => 0],
```

- **`start`** — der Bestand am 20.09.2026, gemessen an `main` `fd99989`.
- **`decke_jetzt`** — was heute erlaubt ist. Solange das Paket nicht gebaut
  ist, steht sie auf dem Startwert.
- **`decke_ziel`** — worauf das Paket sie setzt. **Das Paket schreibt
  `decke_jetzt` herunter, nicht die nächste Instanz nebenbei.** Ohne diese
  Trennung wäre die Zählung von AP1 bis AP9 durchgehend rot und damit wertlos.
- **`grund`** — warum es die Zeile gibt. Steht da für die Instanz, die in
  zwei Jahren eine rote Zeile vorfindet.
- **`bereich`** — `php` (alle PHP-Dateien) · `api` (nur `server/api/`) ·
  `js_und_inline`. Dazu `ausser` (Pfade, auch Verzeichnisse) und `nur`
  (genau eine Datei).
- **`zaehlt`** — `treffer` (Vorgabe) oder `dateien`. Zwei Zeilen zählen
  Dateien, weil die Sache eine Datei ist: „Seiten mit eigener
  `L.map(`-Präambel" (Z35) und „Bauten für relative Zeit" (Z20).

**Regelarten:** `aufruf` (echter Funktionsaufruf aus dem Tokenstrom) ·
`methode` (`$o->name(`) · `definition` · `muster` (PCRE über die Sicht,
mit `nicht` als Ausschluss auf der Trefferzeile) · `eigen` (eine benannte
Funktion `zh_regel_…()` in `zaehlen.php`, für alles, was ein Muster nicht
trifft — fünf Stück, jede mit ihrer Begründung im Kopfkommentar).

## 4. Was das Werkzeug **nicht** kann

Ausdrücklich, damit eine grüne Zahl nicht mehr behauptet, als sie trägt:

- **Es sagt nicht, ob ein Treffer erreichbar ist.** Eine Anweisung in einem
  `if`, das nie zutrifft, zählt mit.
- **Es sagt nicht, ob zwei Stellen dieselbe Sache tun.** Das behauptet das
  Register; die Prüfung dieser Behauptung ist Lesearbeit und steht im
  Konzept.
- **Es findet keinen neu erfundenen Namen.** Z34 (JS-Formatierer) hält eine
  **Namensliste** auf null, weil die vierzehn Definitionen `fmtTag`,
  `wertKmSumme` und `durationHHMM` heißen — es gibt kein Muster, das die
  vierzehn trifft und `wertLesen()` in `suche.php` ausläßt. Ein fünfzehnter
  Formatierer unter neuem Namen fällt dieser Zeile nicht auf. Dafür ist die
  Lesearbeit in AP8 da.
- **Z18 zählt Nähe, nicht Sinn.** Eine „Handliste" ist eine Aufzählung von
  zehn verschiedenen `missions`-Spaltennamen, zwischen denen nie mehr als
  vierzig Zeichen liegen — und deren Tabellenangabe `missions` lautet.
  `rest_segments` teilt sich zehn Spaltennamen mit `missions`; ohne die
  Tabellenprüfung meldete die Zeile zwei Ruhesegment-Anweisungen mit.
- **Der JS-Zerleger ist eine Heuristik**, keine ECMAScript-Grammatik —
  Portierung von `js_bereiche()` aus `tools/wortliste/zerlegen.py` samt deren
  Grenzen: Division und regulärer Ausdruck werden am zuletzt gesehenen
  bedeutungstragenden Zeichen unterschieden, verschachtelte `${…}` in
  Template-Literalen gelten als Teil der Zeichenkette. Im Zweifel bleibt
  stehen, was nicht sicher ein Kommentar ist.
- **`vendor/` ist draußen**, in `server/vendor/` wie in
  `server/assets/vendor/`. Fremdcode wird nicht zentralisiert.
- **Code, der zur Laufzeit entsteht** (`eval`, zusammengesetzte
  Funktionsnamen, JS aus einer PHP-Zeichenkette), ist unsichtbar. Kommt in
  `server/` nicht vor; sollte es einmal vorkommen, sieht die Zählung es nicht.

## 5. Wenn eine Zeile rot wird

1. `--zeile=Zxx --stellen` zeigt Datei und Zeile jedes Treffers.
2. Ist der Treffer eine **neue zweite Stelle**? Dann gehört der Code an die
   eine Stelle, die `grund` nennt.
3. Ist er eine **begründete Ausnahme**? Dann in `ausser` eintragen — **mit
   dem Grund in `grund`**, nicht stillschweigend.
4. Ist die Decke **falsch**? Dann gehört die Begründung ins Konzept, bevor
   sie hier steigt (E-ZE-24).

## 6. Eichung

Zwei Zahlen waren vorher bekannt und sind getroffen:
`error_log(` **77 Aufrufe in 32 Dateien**, `session_start(` **9 in 9**.
Bestand: **133 PHP-Dateien, 40 JS-Dateien**.

Die Selbstprobe fährt **29 Fälle** mit Sollergebnis, darunter „Aufruf im
Kommentar zählt nicht", „Aufruf in einer Zeichenkette zählt nicht",
„Methodenaufruf `->date(` zählt nicht", „eine URL mit `//` ist kein
Kommentar", „`<script<?= nonce() ?>>` — das `?>` beendet den Tag nicht"
(CLAUDE.md 6) und die Zeilentreue über alle 519 Sichten.
