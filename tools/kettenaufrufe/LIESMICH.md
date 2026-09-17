# Kettenaufrufe — passt jeder Aufruf zu seinem Werkzeug?

```
python3 tools/kettenaufrufe/pruefen.py            # prüfen
python3 tools/kettenaufrufe/pruefen.py --probe    # Selbstprobe
python3 tools/kettenaufrufe/pruefen.py --liste    # nur zeigen, was es sieht
```

Rückgabewert `0` = keine Befunde, `1` = Befunde, `2` = die Prüfung selbst kaputt.

## Wofür

Die Auslieferungskette ruft Werkzeuge dieses Projekts auf. **Ob ein Aufruf zur
Schnittstelle des Werkzeugs passt, zeigte sich bisher erst, wenn der Schritt
lief** — und viele Schritte laufen selten: Stufe 2 erst mit einer
eingerichteten Staging-Anlage, der Produktionslauf erst beim ersten Tag.

Am 16./17.09.2026 sind **drei** Aufrufe beim jeweils ersten echten Lauf
gescheitert. Alle drei hatten gültiges YAML und saubere Shell-Syntax:

| Aufruf | Fehler |
|---|---|
| `kreislauf.py --basis … --konto … --passwort …` | `--art` fehlt (Pflicht), `--passwort` gibt es nicht |
| `aufnehmen.mjs --konto … --passwort …` | beide Schalter unbekannt — **still verworfen** |
| `pruefstand.sh aufbau` | ruft `apt-get` ohne `sudo` |

Die ersten beiden Klassen fängt diese Prüfung **vor** dem Lauf, in Stufe 1, wo
sie Sekunden kostet. Die dritte nicht — sie ist kein Schnittstellenfehler.

## Wie sie die Schnittstelle ermittelt

**Sie führt kein Werkzeug aus.** Ein `--help` in einem Prüfschritt wäre ein
Programmstart mit allem, was daran hängt — Datenbank, Netz, Schreibrechte. Sie
**liest** stattdessen den Quelltext:

| Art | gelesen wird |
|---|---|
| `.py` | `add_argument("--x", …)`, dazu `required=True`; ersatzweise ein `sys.argv`-Handparser |
| `.mjs` | `wert('--x'`, `flag('--x'` und eine etwaige Menge `BEKANNT` |
| `.sh` | die Zweige des `case`-Verteilers — also auch die **Unterbefehle** |
| `.php` | `$argv`-Vergleiche auf `--x` |

## Was sie NICHT kann — und sagt

**Was sie im Quelltext nicht findet, kann sie nicht wissen.** Ein Werkzeug,
das seine Schalter zur Laufzeit zusammensetzt, ist für sie unsichtbar; ein
Aufruf mit einer Variablen als Schalter (`--$x`) ebenso. **Beides zählt sie als
UNGEPRÜFT und sagt es**, statt zu schweigen — eine Prüfung, die nichts meldet,
weil sie nichts sehen konnte, ist gefährlicher als gar keine.

Sie prüft **Schnittstellen, nicht Verhalten**. Ob ein Werkzeug auf einem
GitHub-Läufer andere Rechte braucht als im Wegwerf-Container (`sudo`), ob ein
Pfad dort existiert (`PLAYWRIGHT_MODUL`), ob ein Geheimnis gesetzt ist — nichts
davon sieht sie. Das zeigt weiter nur der Lauf.

Und sie liest **kein YAML**: Die `run:`-Blöcke werden mit einem Zeilenleser
herausgeholt, damit Stufe 1 ohne Zusatzpaket auskommt. `working-directory`
kennt sie deshalb nicht — `./gradlew` ist als Sonderfall eingetragen.

**Sie sieht nur Aufrufe in `run:`-Blöcken, nicht die zwischen zwei
Werkzeugen.** Seit Web 20.16.0 ruft `kreislauf.py` seinerseits
`tools/kette/tor.py pause`; dieser Aufruf steht in Python und nicht im
Arbeitslauf, also außerhalb ihrer Reichweite. Wer `--sekunden` in `tor.py`
umbenennt und den Aufrufer vergisst, bekommt von ihr weiter „0 Befunde".
**Gedeckt ist diese eine Stelle stattdessen von der Selbstprobe in
`tools/kette/tor.py`** (Fall 6), die den ganzen Weg `main()` → `rufen()` →
`adresse_bauen()` fährt und die abgerufene Adresse prüft. Das ist kein Ersatz
für eine allgemeine Lösung, sondern die Ansage, wo die Grenze liegt und wer
dahinter wacht.

## Die Selbstprobe

**Eine Prüfung, die nichts meldet, ist zweideutig:** Entweder ist alles gut,
oder sie sieht an der falschen Stelle hin. `--probe` legt ihr zehn Fälle hin —
fünf, die sie finden **muss** (genau die Fehler vom 16./17.09.2026), und fünf
Gegenproben, die sie **nicht** melden darf: die berichtigten Aufrufe, eine
Zeile ohne Werkzeug, ein auskommentierter Aufruf.

Stufe 1 fährt **erst die Selbstprobe, dann die Prüfung**. In dieser Reihenfolge,
damit ein defektes Prüfmittel nicht als grüner Lauf durchgeht.

## Drei Fehlalarme beim ersten Lauf, und was daraus folgte

Die erste Fassung meldete drei Befunde gegen die echte Kette — **alle drei
falsch**:

1. **Fortsetzungszeilen.** Der Kreislauf-Aufruf steht über fünf Zeilen mit `\`
   am Ende. Wer zeilenweise liest, sieht `kreislauf.py` ohne ein einziges
   Argument und meldet jedes Pflichtargument als fehlend.
2. **`./gradlew`** liegt in `android/`, nicht an der Wurzel — der Schritt setzt
   `working-directory: android`.
3. **`wache.py` und `kontrast.py` nehmen kein argparse**, sondern lesen
   `sys.argv` von Hand. Sie galten als unlesbar.

Dazu zwei Quellen von Rauschen: eine Adresse (`"$STAGING_URL/install.php"`) und
PHP-Code in `php -r "…"` wurden für Aufrufe gehalten, ebenso Prosa in einer
Fehlermeldung.

**Eine Prüfung mit Fehlalarmen wird nach dem dritten Mal abgeschaltet** — das
ist der eigentliche Schaden, und deshalb stehen alle fünf Fälle als Kommentar
im Quelltext, wo der nächste sie findet.
