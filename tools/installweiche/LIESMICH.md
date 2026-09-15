# Die Weiche in `install.php` — trägt sie noch?

```
php tools/installweiche/pruefen.php
php tools/installweiche/pruefen.php --selbstprobe
```

Rückgabewert: `0` = die Weiche trägt · `1` = Befund · `2` = Datei fehlt.
Nur PHP, keine Datenbank, kein Netz. Stufe 1 des Prüftors.

Geprüft werden **zwei** Dateien: `server/install.php` und
`server/php_mindest.php`. Die zweite lädt die Weiche, bevor sie vergleicht —
eine PHP-8-Zeile dort wäre genauso tödlich wie eine in `install.php`, und sie
fiele noch weniger auf, weil die Datei drei Zeilen lang ist.

## Wogegen

`server/install.php` beginnt seit Web 20.5.0 mit einer Versionsprüfung: Auf
PHP unter 8.2 zeigt sie eine Seite, die sagt, warum (PP-1, E-P5a-19).

Das nützt nur, solange die Datei auf jener alten Fassung überhaupt noch
**übersetzt** werden kann. **PHP übersetzt eine Datei vollständig, bevor es die
erste Zeile ausführt.** Eine einzige `match`-Anweisung irgendwo weiter unten,
und die Besucherin auf PHP 8.0 bekommt statt der Erklärung einen Parse Error —
also genau die stumme Wand, die die Prüfung verhindern soll.

**Der Fehler wäre nicht zu bemerken.** Auf dem Entwicklungsrechner läuft PHP
8.4, und dort übersetzt alles. Auffallen würde er auf der Installation einer
Fremden, die in diesem Moment keine Auskunft bekommt.

## Wie gemessen wird

Mit `token_get_all()`, nicht mit `grep`. Ein `grep` nach `match(` trifft
`preg_match(` und jeden Kommentar, der das Wort nennt — beides sind keine
Befunde, und **eine Prüfung mit falschem Alarm wird abgeschaltet**. Der
Tokenizer sieht dagegen genau das, was der Übersetzer sieht.

Gesucht wird nach:

| Token | gibt es ab |
|---|---|
| `T_MATCH` | 8.0 (`match`-Ausdruck) |
| `T_NULLSAFE_OBJECT_OPERATOR` | 8.0 (`?->`) |
| `T_ATTRIBUTE` | 8.0 (`#[Attribut]`) |
| `T_ENUM` | 8.1 (`enum`) |
| `T_READONLY` | 8.2 (`readonly`) |
| `: never` als Rückgabetyp | 8.1 |
| `: mixed` als Rückgabetyp | 8.0 |

## Grenzen — was sie nicht sieht

Konstrukte, die keinen eigenen Token haben, sondern nur eine andere Anordnung
bekannter Tokens sind:

- benannte Argumente — `foo(name: 1)`
- Eigenschaftenbeförderung — `function __construct(private int $a)`
- ein nachgestelltes Komma in einer Parameterliste
- `new` in Initialisierern

Wer eines davon einbaut, fällt hier **nicht** auf. Deshalb sagt der Kopf von
`install.php` die Regel zusätzlich im Klartext, und deshalb druckt das Werkzeug
diese Liste bei jedem Lauf mit aus — eine Grenze, die man nur in der
Dokumentation findet, ist keine.

## Was sie nicht leisten kann

`index.php` schützen. Jene Datei **ist** PHP-8-Code, und PHP übersetzt sie
ganz, bevor die Weiterleitung auf `install.php` in ihrer Zeile 4 zur Ausführung
käme. Wer auf PHP 8.0 die Startseite aufruft, sieht deshalb einen Parse Error;
der Weg für eine Ersteinrichtung ist `install.php` unmittelbar. Das steht so
auch im Kopf von `install.php`.

## Selbstprobe

Acht Fälle: vier, die anschlagen müssen (`match`, `?->`, `: never`, `enum`),
und vier, die **nicht** anschlagen dürfen (dieselben Wörter im Kommentar,
`preg_match`, `match` als Zeichenkette, ein gewöhnlicher Rückgabetyp). Die
zweite Hälfte ist die wichtigere: Eine Prüfung, die alles trifft, ist keine.

Stand 15.09.2026: **8 von 8 erfüllt**, 0 Befunde auf `server/install.php`
(623 Zeilen) und `server/php_mindest.php` (35 Zeilen).
