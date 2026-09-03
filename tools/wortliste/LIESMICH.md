# Wortliste (Arbeitspaket D1 der Phase P2)

Beantwortet eine Frage mit einer Zahl statt mit einem Eindruck:

> Steht in einem sichtbaren Text oder in der normativen Dokumentation ein
> Wort, das nur von der Luftrettung her gedacht ist?

Die Anwendung dokumentiert Notarzteinsätze **luft- wie bodengebunden**. Wo ein
Wort Luftfahrt bezeichnet, bleibt es; wo es aus Gewohnheit dasteht, gehört es
ersetzt. Dieses Werkzeug hält den Unterschied fest — nicht als Meinung,
sondern als Liste mit Begründungen.

## Aufruf

```
python3 wortliste.py                 # alle Bereiche
python3 wortliste.py --bereich a     # nur die PHP-Dateien des Servers
python3 wortliste.py --bereich d     # nur die Android-Apps
python3 wortliste.py --alle          # auch die erklärten Treffer zeigen
python3 wortliste.py --probe         # Selbstprobe des Zerlegers
python3 wortliste.py --bericht /tmp/wortliste.txt
```

Rückgabewert: `0` = sauber · `1` = Treffer außerhalb der Ausnahmen, ungenutzte
Ausnahmen oder eine durchgerutschte Teilstring-Falle · `2` = Fehler.

## Die Teile

| Datei | Aufgabe |
|---|---|
| `wortliste.py` | Bereiche zusammenstellen, suchen, Bericht schreiben |
| `zerlegen.py` | Kommentare aus PHP und JavaScript entfernen, zeilentreu |
| `sperrliste.json` | die gesuchten Muster und die Teilstring-Fallen |
| `ausnahmen.json` | erwartete Treffer: wo ein Luftbegriff bleiben soll, und warum |

## Die drei Zahlen

Der Bericht nennt je Bereich drei Zahlen. Zwei davon müssen null sein:

1. **Treffer gesamt** — wie oft ein Muster überhaupt angeschlagen hat. Diese
   Zahl darf groß sein; sie sagt für sich genommen nichts.
2. **Treffer außerhalb der Ausnahmen** — die eigentliche Antwort. Jeder
   einzelne wird mit Datei, Zeile, Muster und Wortlaut genannt. Zusätzlich
   steht dahinter, in wie vielen **Zeilen** sie stehen: Eine Zeile kann
   mehrere Muster treffen („Garmin Connect ein; die Domain genügt (z. B.
   `luftrettung.net`)" trifft vier).
3. **Ungenutzte Ausnahmen** — Regeln, die nichts erklärt haben. Sie sind
   nicht harmlos: Entweder beschreiben sie etwas, das es nicht mehr gibt,
   oder der Lauf hat die Stelle gar nicht angesehen — dann prüft er weniger
   als gedacht. Dieselbe Vorschrift wie bei den Ausnahmelisten des
   Kreislaufvergleichs (`tools/referenzdatensatz/vergleich/ausnahmen/`).

Dazu die **Teilstring-Fallen**: Wörter, die ein Sperrwort enthalten, ohne
eines zu sein — „dorthin" enthält `rth`, „maschinell" enthält `maschine`,
„naheliegend" enthält `heli`. Der Bericht zählt ihre Vorkommen und prüft,
dass keines als Treffer gezählt wurde. Diese Fallen sind der Grund, warum die
Basiszahl im Rahmenplan („rth: 65 Fundstellen") um den Faktor 30 danebenlag:
echt waren es zwei.

## Die Bereiche

| Bereich | Was |
|---|---|
| **a** | `server/*.php`, `server/api/*.php` — ohne Kommentare |
| **b** | `server/assets/*.js` ohne `vendor/` — ohne Kommentare |
| **c** | `README.md`, `docs/Handbuch.md`, `docs/Export-Format.md`, `docs/Technik.md`, `docs/Backup-Format.md`, `docs/JSON-Vertrag.md`, `docs/Design.md`, `docs/Lizenzen.md` |
| **d** | `android/*/src/main/res/values/strings.xml` — die sichtbaren Texte der Handy- und der Wear-OS-App (seit S4/D1) |
| **e** | `watch/resources*/**/*.xml` **und** `watch/source*/*.mc` — die sichtbaren Texte der Garmin-App (seit S5/C) |

### Die Regel dahinter

> **Jeder sichtbare Text der Anwendung läuft durch die Wortliste — gleich, in
> welchem Client er steht.** Ein Bereich fehlt nicht, weil ein Verzeichnis
> jung ist; er fehlt, weil ihn niemand eingetragen hat. Wer einen Client
> hinzufügt, trägt seine Textdateien im selben Paket ein, in dem der Client
> entsteht. **Ein Lauf, der einen Client übergeht, meldet keine Null — er
> meldet gar nichts.**

*Aufgestellt auf Ansage am 01.09.2026 (S4, Fund B-S4-06).* Der Anlass: Die
Android-Apps entstanden in S4/B1, und der Lauf nach dem letzten Paket meldete
**0 Treffer, ohne eine einzige Zeile der App angesehen zu haben** — genau der
Fall, vor dem `CLAUDE.md` 6 warnt („eine grüne Zahl ist erst dann ein Beleg,
wenn sie das Gemessene benennt").

Bereich **d** ist deshalb keine Erweiterung, sondern das Nachholen einer
Pflicht, die mit `android/` entstand. Er fand beim ersten Lauf **3 Treffer**
— alle drei Homonyme derselben Klasse, für die die Weboberfläche an
denselben Stellen bereits Ausnahmen führt (`android-bildmarke-alt`,
`android-logowahl`).

**Bei XML wird mehr weggeräumt als der Kommentar.** Sichtbarer Text steht
*zwischen* den Tags; `<string name="dienst_beginnen">` ist ein Bezeichner,
den niemand liest. Bliebe er stehen, meldete das Werkzeug jeden
Schlüsselnamen — und eine Liste, die zu neun Zehnteln aus Falschmeldungen
besteht, liest bald niemand mehr. Eine Ausnahmeregel für Bereich d bindet
ihre `zeile` deshalb an den **Text**, nicht an den Schlüsselnamen.

**Nicht geprüft**, und jedes mit Grund: `docs/CHANGELOG.md` (Historie — dort
stehen die alten Begriffe zu Recht), die Konzept- und Prüfdokumente,
`docs/Geraete-Eingabe.md` und `docs/Uhr-Layout_Regeln.md` (beschreiben die
Garmin-Uhr als Gegenstand — anders als ihre Texte, die seit S5/C in Bereich
`e` laufen), `docs/Backlog.md` und `tools/` selbst.
Die Zuordnung folgt den Fundort-Klassen im Konzept P2, Abschnitt 5.1.

Bereich **e** kam in S5/C dazu (Backlog 66, E-S5-40) und schließt den letzten
Client. Die sichtbaren Texte der Garmin-App sind die **ältesten des Projekts**
— bis dahin waren sie als einziger Client ungeprüft. Die frühere Begründung,
`watch/` „beschreibe die Garmin-Uhr als Gegenstand", trifft auf
`docs/Uhr-Layout_Regeln.md` zu, **nicht** auf die Texte der App selbst: Die
liest dieselbe Person, die auch die Weboberfläche liest.

**Er umfasst XML *und* Monkey C** (E-S5-54). Backlog 66 nannte nur
`watch/resources/**/*.xml`. Das sind vier Zeichenketten — der App-Name und die
drei Namen der Bildmarken-Wahl. Die eigentlichen Texte der Uhr stehen als
Literale im Quelltext: „Nicht eingerichtet", „Zu viele Geräte", „Sync
vollständig", „Code für das Web". Ein Bereich, der die XML ansieht und die
`.mc` übergeht, meldete wieder eine Null über etwas, das er nicht gelesen hat
— genau der Fall B-S4-06, der die Regel oben nötig gemacht hat.

Ein Bereich trägt deshalb erstmals **zwei Arten**: `"art": {".xml": "xml",
".mc": "monkeyc"}`. Eine Endung, die dort fehlt, bricht den Lauf ab, statt die
Datei still zu übergehen. Und `resources*` statt `resources`: Die
vorgerasterten Bildmarken und Launcher-Symbole liegen in Geschwisterordnern
(`resources-marke101`, `resources-icon54` …), die `monkey.jungle` je Gerät
zuweist — sie tragen heute keinen sichtbaren Text, aber ein Ordner, den
niemand ansieht, ist genau die Lücke, die dieser Bereich schließt.
`watch/manifest.xml` bleibt draußen: Der App-Name steht dort als Verweis
(`@Strings.AppName`), der Text selbst in `strings.xml`.

Der erste Lauf fand **2 Treffer in 34 Dateien**, beide dieselbe Sache:
`L_SELECT = "START"` und `L_SELECT_HOLD = "START halten"` in
`watch/source-tasten5/DeviceProfile.mc`. Das ist der Aufdruck auf dem Gehäuse
von Fenix und Forerunner — die Venu 3s heißt an derselben Stelle „Action" und
trifft deshalb nicht. Eine Ausnahme, Klasse G (`uhr-tastennamen`): Genau diese
Trennung sieht E-P2-02 vor, und die Oberflächen setzen den Namen über
`Input.lSelectHold()` ein, ohne ihn selbst zu kennen.

## Wie eine Ausnahme begründet wird

Drei Regeln, wörtlich dieselben wie bei den Ausnahmelisten des
Kreislaufvergleichs:

1. **Ohne Begründung keine Regel.** `wortliste.py` weist eine Regel ohne
   `begruendung` oder ohne `klasse` beim Laden zurück. Das ist nicht
   Pedanterie: Eine Ausnahme ohne Grund ist ein Filter, und ein Filter
   verdeckt genau das, wofür die Liste da ist.
2. **Vermeidbares ist keine Ausnahme.** Lässt sich der Treffer durch eine
   bessere Formulierung beseitigen, gehört er beseitigt — nicht hierher.
   Sonst schreibt die Ausnahmeliste eine Nachlässigkeit auf Dauer fest.
3. **Jede Regel muss greifen.** Siehe „ungenutzte Ausnahmen" oben.

Eine Regel besteht aus: `id`, `klasse`, `begruendung` (Pflicht) und den
Bedingungen `bereich`, `datei` (Glob), `muster` (welche Sperrmuster),
`zeile` (regulärer Ausdruck auf die Zeile), `von`/`bis` (Block innerhalb
einer Datei) oder `abschnitt` (Markdown-Überschrift bis zur nächsten
gleicher oder höherer Ebene). Fehlt jede Bedingung, gilt die Regel für die
ganze Datei.

**Die Reihenfolge zählt:** Geprüft wird von oben nach unten, die erste
passende Regel erklärt den Treffer. Deshalb steht das Besondere oben und das
Allgemeine unten — sonst schluckte die allgemeine Regel „Feldnamen und
Kopfzeilen" die besondere zur Schwachwortliste, und deren Begründung stünde
in der Liste, ohne je gelesen zu werden.

**Zeilenmuster statt Zeilennummern.** Eine Ausnahme, die auf Zeile 2216
zeigt, ist beim nächsten Absatz falsch, ohne dass es jemand merkt. Deshalb
kennt das Werkzeug keine Zeilennummern in Regeln.

Die `klasse` ist die Fundort-Klasse aus dem Konzept P2, Abschnitt 5.1
(C, D, E, F, G, H). Dazu kommt eine, die dort nicht steht: **`Homonym`** für
Stellen, an denen das Wort schlicht etwas anderes bedeutet — die „Maschine"
im Runbook ist ein Rechner, der „Flugmodus" eine Einstellung des Geräts.
Eine Fundort-Klasse wäre dafür die falsche Auskunft.

## Was der Zerleger nicht kann

`zerlegen.py` entfernt Kommentare, damit sie den Befund nicht füllen — ein
Kommentar ist Klasse E und bleibt bis P6 stehen. Er ist eine **Heuristik,
keine Grammatik**:

- Der JS-Teil unterscheidet Division und regulären Ausdruck am zuletzt
  gesehenen bedeutungstragenden Zeichen. `${…}` in Template-Literalen gilt
  als Teil der Zeichenkette.
- Der PHP-Teil folgt `<?php`/`?>`, kennt Heredoc, Nowdoc, `#`- und
  `//`-Kommentare (auch die von `?>` beendeten) und reicht `<script>`- und
  `<style>`-Blöcke an den JS- bzw. CSS-Teil weiter. Dazu wird in einem
  ersten Durchgang jede PHP-Insel geleert: Ein `<script>`-Block einer
  PHP-Seite enthält fast immer `<?= … ?>`, und wer die HTML-Anteile nur
  zwischen zwei Inseln betrachtet, findet die Kommentare des Blocks nicht
  mehr. Genau daran ist die erste Fassung gescheitert — in `index.php`
  blieben vierhundert Zeilen JS-Kommentar stehen.
- **Monkey C benutzt den JS-Teil.** Es kommentiert genauso (`//`, `/* */`) und
  kennt keine regulären Ausdrücke — die Unterscheidung Regex/Division läuft
  dort also immer auf Division hinaus. Zwei der Probefälle sind Monkey C,
  darunter die Division nach schließender Klammer (`(rest + 59) / 60`), die in
  den Uhr-Oberflächen der häufigste Fall ist.
- Im Zweifel bleibt Text stehen. Ein Treffer zu viel kostet eine Ausnahme,
  ein Treffer zu wenig kostet die Aussage.

`python3 wortliste.py --probe` fährt **einundzwanzig Fälle mit Sollergebnis**,
darunter die, an denen der naheliegende Einzeiler `re.sub(r'//.*$', …)`
scheitert: eine URL im sichtbaren Text, ein `//` in einer Zeichenkette, ein
regulärer Ausdruck mit `\/\/`, ein Nowdoc, die PHP-Insel im `<script>`-Block.
Jeder Fall prüft außerdem, dass die Zeilenzahl unverändert bleibt — sonst
zeigte jeder Befund auf die falsche Stelle.

## Was das Werkzeug nicht kann

**Es findet Wörter, keine Perspektive.** Ein Satz wie „das Rettungsmittel
landet am Einsatzort" enthält kein Sperrwort und ist trotzdem nur von der
Luft her gedacht. Dagegen hilft kein Muster, nur Lesen. Im Konzept P2 steht
das als eigener Arbeitsschritt (Paket D4, Schritt 11).

**Es ist keine Rechtschreibprüfung und kein Stilwerkzeug.** Es sagt nicht,
ob der Ersatz gut ist — nur, dass der alte Begriff weg ist.

## Mitführen in P3 und P6

Das Werkzeug bleibt im Repositorium und läuft weiter mit (E-P2-12):

- **P3** (neue Oberfläche) erzeugt neue Texte. Ein Lauf nach jedem
  Arbeitspaket zeigt, ob dabei ein Luftbegriff zurückgekommen ist.
- **P6** (Umbenennung) räumt die Klassen D und E auf. Dann fallen
  Ausnahmen weg — und die dritte Zahl („ungenutzt") sagt, welche.

`tools/` wird nicht ausgeliefert (`deploy.yml` lädt nur `server/` hoch); das
Werkzeug kommt dem Produktivserver nicht nahe.
