# Prüfdokument — Korrekturstufe Backlog Nr. 148 und 149 (Web 15.5.2)

*Nach K9. Erstellt am 06.09.2026, Zweig `claude/backlog-148-149-web-md24ve`.
Das Prüfprotokoll unten beantwortet „ist es belegt?"; die **Prüfliste** in
Abschnitt 4 beantwortet „was muss ich noch tun?".*

> | | |
> |---|---|
> | Stufe | **Web 15.5.2** — Korrekturstufe, keine Migration, kein neuer Weg durch die Anwendung |
> | Punkte | Backlog **Nr. 148** (Zusammenführen-Knopf auf 404) und **Nr. 149 (b)** (Updates-Seite zählt anders als Status und Menü); **149 (a)** ist eine Regel im Runbook, kein Code |
> | Neu entstanden | `tools/linkprobe/` · `tools/wartungsprobe/` Teil 6 · Backlog **Nr. 150** und **Nr. 151** |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI und `php -S`), MariaDB 10.11.14, Chromium über Playwright; lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto), erreicht über **https://127.0.0.1:8443** |
> | Ergebnis | Alles Maschinelle grün; **zwei Punkte bleiben für den Auftraggeber** (Abschnitt 4). Die Stufe ist danach adversarisch gegen sich selbst gelesen worden — sechzehn Funde, alle behoben oder eingeordnet (Abschnitt 2a) |

---

## 0. Was nicht geprüft werden konnte — und warum

Steht bewusst vor der Prüfliste (K9).

- **Der Produktivzustand selbst.** Der Fall aus Nr. 149 ist im Prüfstand
  **nachgebaut** (Migration laufen lassen, danach ihre Zeile aus
  `schema_migrations` löschen), nicht an der echten Installation gemessen.
  Der Nachbau ist deckungsgleich, soweit die Anwendung ihn sehen kann — sie
  liest ausschließlich `schema_migrations.id` und die `skip`-Prüfung des
  Katalogs —, aber es bleibt ein Nachbau. **Deshalb ist Punkt 1 der
  Prüfliste kein Formalakt.**
- **Der phpMyAdmin-Notweg (Nr. 149 a).** Er ist im Runbook beschrieben, nicht
  gefahren: Der Container hat kein phpMyAdmin, und die beiden SQL-Anweisungen
  sind auf dieser Installation längst gelaufen. Die Anweisungen selbst sind
  wörtlich aus dem Katalog übernommen, nicht abgeschrieben.
- **Der Erstdeploy-Fall selbst** — eine Installation *ohne* die Rolle
  `betreiberin`, auf der Betrieb → Updates den Zugang verweigert — ist
  **nicht** hergestellt worden. Er ließe sich nachbauen (ENUM zurücksetzen),
  wäre aber ein Eingriff ins Rollenmodell der Prüfinstallation für eine
  Aussage, die niemand bezweifelt. Was daraus folgt, ist die Regel im
  Runbook, und die ist Text.
- **Der Bilderlauf am Fingergerät** ist gefahren, der **Kontrastlauf**
  ebenfalls — beide Zahlen stehen unten. Nicht gefahren: der volle Bilderlauf
  über alle **46** Seiten der Liste. Fotografiert sind die **drei**, die die
  Änderung berührt (Status, Updates, Updates im Wartungsmodus); ein Lauf über
  alle 46 hätte 368 Bilder und eine große Zahl geliefert, die nichts über
  diese drei sagt.
- **Nicht fotografiert ist `index.php`** (Seite `10-tagesuebersicht`), obwohl
  Nr. 148 dort sitzt. Absicht: Die Änderung ist ein `href` — im Bild wäre sie
  nicht zu sehen. Belegt ist sie durch den Browserlauf mit Klick und durch
  die Linkprobe, beides unten mit Zahl.
- **Der Stilvergleich entfällt** — es ist keine Zeile in
  `server/assets/style.css` geändert worden. Die Plakette „nicht nötig"
  benutzt `.plakette-neutral`, die es seit P3 gibt; kein neuer Baustein, kein
  neuer Token, keine Mockup-Freigabe nötig (`CLAUDE.md` 5).
- **Nicht geprüft, weil nicht berührt:** Uhr und Android-Apps. Keine Zeile in
  `watch/` oder `android/`; ihre Zählungen bleiben stehen (`CLAUDE.md` 2:
  drei Zählungen, drei Auslieferungen).

---

## 1. Maschinell geprüft — Mittel und Zahl

Jede Zahl nennt, **was** sie gemessen hat (`CLAUDE.md` 6).

| Mittel | Was es gemessen hat | Zahl |
|---|---|---|
| `php -l` | die **fünf** geänderten PHP-Dateien: `server/index.php`, `server/migration_lib.php`, `server/betrieb_updates.php`, `server/version.php`, `tools/wartungsprobe/probe.php` | **0 Syntaxfehler** |
| `tools/linkprobe/probe.py` (neu) | jede Adresse `<seite>.php?<name>=` unter `server/` — PHP **und** JavaScript, jeder Parameter **innerhalb desselben Zeichenkettenliterals**, nicht nur der erste — gegen die `$_GET`/`$_REQUEST`/`filter_input`-Zugriffe der Zielseite | **98 Zielseiten, 132 Verweise, 0 unbekannte Abweichungen**, 10 dynamisch gelesen, 1 bekannte Abweichung mit Nummer (Nr. 151), 0 tote Zeilen |
| dieselbe, **Gegenprobe**: heutiger Baum, in dem allein die eine Zeichenkette auf `?ziel=` zurückgedreht ist | ob das Prüfmittel den Fehler findet, den es finden soll | **1 Abweichung**: `server/index.php:181 diensttag_zusammenfuehren.php?ziel= [FEHLT] die Seite liest: d, q` — **181, nicht 173:** Im echten Stand `37d457b` steht der Verweis in Zeile 173; die acht Kommentarzeilen der Behebung verschieben ihn. Der Backlog nennt 173 und meint denselben Verweis |
| `tools/wartungsprobe/probe.php` | den Wartungsmodus wie bisher **und** neu in Teil 6 die Zählweise der Migrationen (Nr. 149) | **50 Erwartungen, 0 nicht erfüllt** (vorher 43) |
| dieselbe, **Gegenprobe** gegen den Stand vor der Behebung | ob Teil 6 den Fehler findet | **50 Erwartungen, 4 nicht erfüllt** — 24 (Karte nennt „1 Update"), 25 (Plakette), 26 (Knopf), 27 (Meldung nach dem Klick) |
| `tools/wortliste/wortliste.py` | alle fünf Bereiche nach der **letzten** Änderung: 98 Dateien (a, `server/*.php`), 32 (b, JavaScript), **8 (c, normative Dokumentation)**, 2 (d, Android), 35 (e, Uhr) | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (86 Ausnahmeregeln, 86 gegriffen) |
| `tools/vollstaendigkeit/pruefen.py` | Klassen, Werte außerhalb der Token, Symbole, Knopfregel | **300 vorher (`main`, 37d457b), 300 nachher — unverändert.** Erklärung unten |
| `tools/screenshots/aufnehmen.mjs --nur 45-,46` | `betrieb_status.php`, `betrieb_updates.php` und dieselbe Seite in der Wartungsfassung — 3 Seiten × 8 Breiten, **beide Bedienhöhen**, gefahren **mit ausstehender Migration**, damit die Bilder den geänderten Weg zeigen | Zeiger: **24 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe** (44/36 px) · Finger: **24 Bilder, 0/0/0** (44 px) |
| `tools/screenshots/kontrast.py` | die Kontrastpaare der Token | **21 Paare gerechnet, 0 verfehlt** |
| Migrationsregister gegengezählt | `migrationen_katalog()` gegen die `skipped`-Liste in `schema.sql` | **43 = 43**, Kennungsmengen identisch (`diff` leer) — unverändert, es kommt keine Migration hinzu |

### Browserläufe (Chromium über Playwright, angemeldet, 1280 px)

**Nr. 148 — mit zwei zeitlich überlappenden aktiven Diensttagen im
Prüfkonto** (05.09.2026 06:00–18:00 und 07:30–19:00, Überschneidung
630 Minuten; `dt_ueberlappungen()` meldet sie beidseitig):

| | vorher (`?ziel=`) | nachher (`?d=`) |
|---|---|---|
| Warnung sichtbar | ja | ja |
| `href` des Knopfes | `…?ziel=17` | `…?d=17` |
| HTTP nach dem Klick | **404** | **200** |
| Titel der Zielseite | „Nicht gefunden" | „Diensttag aufnehmen" |
| Text „Diensttag nicht gefunden" | **ja** | nein |
| geöffneter Tag im Titel | — | Unterzeile: „Aufnehmender Diensttag: **05.09.2026 08:00** · NEF 1 · Wache Nord — dieser bleibt" |

Konsolenfehler: **10, alle Kartenkacheln** (`tile.openstreetmap.org`,
`ERR_CONNECTION_RESET` — der Container hat keinen Zugang dorthin). **0
Fehler aus der Anwendung.** Der Bilderlauf filtert genau diese Quelle
ohnehin heraus.

**Nr. 149 — Register ohne `2026_09_05_rolle_betreiberin`, Schema aktuell:**

| Messpunkt | vor dem Klick | nach dem Klick |
|---|---|---|
| Betrieb → Status | „1 Migration steht aus" | „Alles aktuell · 43 ausgeführt · zuletzt 2026_09_05_rolle_betreiberin" |
| Menüzähler an „Updates" | **1** | 1 → **keiner**, sobald der 60-s-Zwischenspeicher ausgelaufen ist (nachgemessen mit geleertem Speicher: **kein Zähler**) |
| Karte „Ausstehende Updates" | **1 Update**, Plakette „steht aus" | „alles aktuell" |
| Plakette der Zeile | **nicht nötig** (neutral) | — |
| Knopf „Ausstehende ausführen" | **da** | weg |
| Karte „Ausgeführt" | 42 | 43 |
| Meldung nach dem Klick | — | „Die ausstehenden Updates wurden angewendet — unten steht je Eintrag, was geschehen ist." |
| Registereintrag | fehlt | `skipped` |

Konsolenfehler: **0**. Zum Vergleich derselbe Zustand **vor** der Behebung,
am Bild belegt: Menüzähler „1" neben einer Karte, die „alles aktuell"
meldet, ohne Knopf — und „Ausgeführt 43" neben „Datenbankstand · 42
verbucht".

**Kommandozeile:** `php update.php` gegen denselben Zustand — 43 Zeilen,
Rückgabe 0, die Zeile
`OK  Web 15.0.0  2026_09_05_rolle_betreiberin  Nicht nötig (Schema bereits
aktuell) — als erledigt vermerkt.`, Registereintrag danach `skipped`.
**Der neue Status erscheint dort nie**, und das ist richtig: Die
Kommandozeile ruft den Lauf immer ausführend auf, und dann ist die Migration
verbucht, bevor eine Zeile gedruckt wird. Ihr `printf` gibt den Status
generisch aus (`%-6s`, `SKIP` sind vier Zeichen) — eine Änderung war nicht
nötig, und eine vorsorgliche wäre eine Zeile ohne Fall gewesen.

### Die Zahl der Vollständigkeitsprüfung braucht eine Erklärung

Sie steht **nicht** auf null, und zwar seit P3: **300 Befunde** sind der
Ausgangsstand (54 Klassen ohne Gegenstück, 6 als *offen* vermerkt, 13
`style="…"`-Attribute, 227 Unicode-Zeichen als Symbol im Markup; die übrigen
**fünfzehn** Zähler auf 0, dazu zwei Hinweiszähler — 55 Regeln ohne
Markup-Fund und 25 Symboldateien ohne Verweis —, die keine Befunde sind). Wer hier „0 Befunde" als Ziel meldete, meldete etwas
Falsches. Gemessen wird deshalb die **Differenz**: gegen `main` (37d457b)
**300**, nach dieser Stufe **300** — unverändert.

Ein Zwischenstand war 301. Ursache war ein Auslassungszeichen (`…`) in einem
neuen Codekommentar in `index.php`: Das Werkzeug zählt Unicode-Zeichen im
Markup und kann einen PHP-Kommentar innerhalb eines HTML-Blocks nicht davon
trennen. Das ist kein Fehler des Werkzeugs — es ist die Grenze, an der es
arbeitet. Der Kommentar sagt dasselbe jetzt ohne das Zeichen, und die Zahl
steht wieder bei 300. Festgehalten, weil ein stillschweigend um eins
gestiegener Ausgangsstand genau die Art Drift ist, die eine Streichliste
unbrauchbar macht.

### Gegenprobe zum Bilderlauf

Nach seiner eigenen `LIESMICH.md`: **24 Dateien, 24 verschiedene
Prüfsummen** je Lauf (Zeiger und Finger getrennt). Keine zwei Aufnahmen
zeigen dasselbe Bild — das ist die Probe gegen F-P3-AQ, wo 176 von 248
Bildern die Anmeldeseite zeigten und der Lauf trotzdem „0 Überlauf" meldete.
Am Bild nachgesehen (`46-betrieb-updates-390.png`): Fußzeile **v15.5.2**,
Karte „Ausstehende Updates · 1 Update · steht aus", die Zeile mit der grauen
Plakette **„nicht nötig"**, der Knopf, und „Ausgeführt 42" neben
„Datenbankstand · 42 verbucht".

---

## 2. Drei Funde an den Prüfmitteln selbst

**Keiner davon kam aus dem grünen Lauf.** Zwei fielen bei der Frage auf,
*worüber* die Zahl gerechnet war; einer erst in der Gegenprobe gegen den
alten Stand. Sie stehen hier, weil sie die Art Fehler sind, die eine grüne
Zahl wertlos macht (`CLAUDE.md` 6).

**Die Linkprobe las nur den ersten Parameter je Adresse.** `?t=rettungsmittel&ev=`
sind zwei; der erste Entwurf sah nur `t`. **Elf** Verweise fielen so durch,
und gemeldet worden wäre trotzdem eine runde Zahl. Behoben, bevor sie
eingecheckt wurde — die Zahl stieg von 120 auf 131 Verweise und von 4 auf
**10** dynamisch gelesene.

**Und sie übersah `&amp;` als Trenner.** Im Markup steht der Trenner
maskiert: `ui.php:644` baut `zeitraum.php?y=…&amp;m=…`. Die Probe fand dort
`y` und nicht `m` — sie hätte also einen falschen Namen an dieser Stelle nie
gemeldet. Es ist genau eine Adresse in diesem Bestand, und `zeitraum.php`
liest `m` tatsächlich; der Fund ist keine Abweichung, sondern eine **blinde
Stelle des Prüfmittels**. Behoben in einem Nachtragscommit — die Zahl stieg
von 131 auf **132**.

Beide Male dasselbe Muster, und es ist das Muster, vor dem `CLAUDE.md` 6
warnt: Das Werkzeug meldete eine grüne Zahl, ohne zu sagen, worüber sie
gerechnet war. Erst die Frage „**was** hast du gemessen?" hat die Lücken
gezeigt. Dieselbe Frage hat noch zwei Dinge ergeben, die jetzt in der
LIESMICH stehen: Die Zusage „jeder Parameter der Adresse" gilt nur
**innerhalb desselben Zeichenkettenliterals** — wo PHP die Adresse
zusammensetzt (`'?export=csv&sort=' . e($sort) . '&richtung='`), endet der
Abgleich. Und die Zahl der Zielseiten hing an `server/config.php`, die gar
nicht im Repositorium liegt: im Arbeitsbaum 99, im frischen Klon 98. Sie ist
jetzt ausgeschlossen, und **98** ist aus einem Klon reproduzierbar.

**Die Erwartung „der Knopf ist da" war grün, obwohl kein Knopf da war.** Sie
suchte den Wortlaut „Ausstehende ausführen" — und der steht auf derselben
Seite ein zweites Mal: als Schritt 4 im fünfstufigen Ablauf der Karte
„Wartungsmodus". Gemessen in der Gegenprobe gegen den alten Stand: Erwartung
26 grün bei fehlendem Knopf, also genau im Fehlerfall. Sie misst jetzt gegen
`form="migform"`, das nur der Knopf trägt; die Gegenprobe meldet seither 4
statt 3 Fehlschläge.

---

## 2a. Der adversariale Durchgang — und was er gefunden hat

Nach dem Bauen ist die ganze Stufe **gegen sich selbst gelesen** worden: vier
unabhängige Prüfer über Zahlen, Querverweise, Code und dieses Dokument, mit
der Anweisung, zu suchen was falsch ist und jede Behauptung nachzurechnen.
Das Ergebnis war **kein leeres Blatt** — und die Funde stehen hier, weil ein
Prüfdokument, das nur seine grünen Zahlen zeigt, die halbe Auskunft ist.

Was davon **die Stufe selbst betraf** und behoben ist:

| Fund | Wo | Behoben |
|---|---|---|
| „30 Seiten" für den Bilderlauf — es sind **46** (`seiten.json` nachgezählt) | Changelog, dieses Dokument | ja, 46 |
| Die vier Fundstellen in **Backlog Nr. 150** trugen Zeilennummern, die **diese Stufe selbst** ungültig gemacht hat: `Technik.md` wurde länger, `CHANGELOG.md` um 153 Zeilen | Backlog, Rahmenplan Abschnitt 5 | ja — jetzt Abschnittsangaben (`Technik.md` 4.97a und 7, Changelog-Eintrag Web 10.1.0), die nicht verrutschen |
| `php -l` „die **vier** geänderten PHP-Dateien" — es sind fünf, `version.php` fehlte | dieses Dokument | ja |
| „alle übrigen **zehn** Prüfungen auf 0" — es sind fünfzehn, dazu zwei Hinweiszähler | dieses Dokument | ja |
| Überschrift „Drei Funde", erster Satz „**Beide**" — beim Nachtrag stehengeblieben | dieses Dokument | ja |
| Die Linkprobe zählte **99 Zielseiten**, weil `server/config.php` mitlief — die Datei steht in `.gitignore` und liegt nie im Repositorium. Aus einem frischen Klon wären es 98 gewesen | Werkzeug und sechs Dokumente | ja — Datei ausgeschlossen, **98** ist reproduzierbar |
| „acht übersehene Verweise" — es sind **elf** (131 − 120) | Werkzeug, LIESMICH, dieses Dokument | ja |
| Die Zusage „**jeder** Parameter der Adresse" gilt nur innerhalb desselben Zeichenkettenliterals | Werkzeug, LIESMICH, dieses Dokument | ja, zurückgenommen und die Grenze benannt |
| Die Vorbedingung von Teil 6 prüfte `offen === 0`, nicht `blockiert === 0` — eine blockierte Migration hätte die Messung verfälscht | `wartungsprobe/probe.php` | ja |
| Erwartung 25 maß nur den **Text** „nicht nötig", nicht den Ton — ein orange gesetzter Status wäre grün geblieben | `wartungsprobe/probe.php` | ja, misst jetzt `plakette-neutral` |
| Erwartung 24 maß `1 Update` als Teilzeichenkette — „11 Updates" hätte auch gepasst | `wartungsprobe/probe.php` | ja, mit Abgrenzung |
| Der Kasten der Wartungsprobe sagte, Teil 6 fasse als **erstes** das Register an — Erwartung 16 (`php update.php`) tut das seit jeher | `wartungsprobe/LIESMICH.md` | ja, und was ein **harter Abbruch** hinterlässt, steht jetzt dabei |
| „Acht Zeilen tiefer steht `skip`" — es sind 28 | `migration_lib.php`, `Technik.md` | ja, Zahl gestrichen statt gepflegt |
| R57 stand im Programmentscheidungs-Register weiter mit „führt auf 404" im Präsens; der Schritt-6-Block nannte Nr. 148 als laufenden Fund | Rahmenplan | ja, beide auf „behoben mit Web 15.5.2" |
| Abschnitt 10 lief 30, **33, 32, 31** — die neue Zeile war vor die beiden älteren geraten | Rahmenplan | ja, jetzt 31, 32, 33 |
| `Technik.md` trug „Stand: 05.09.2026", obwohl diese Stufe sie an vier Stellen ändert; zwei Wörter darin ohne Umlaute | `Technik.md` | ja |

**Was das über die Stufe sagt.** Der Code war richtig — kein Prüfer hat an
`migration_lib.php`, `betrieb_updates.php` oder `index.php` einen Fehler im
Verhalten gefunden. Falsch waren **Zahlen und Verweise in der Buchführung**,
und dreimal die Prüfmittel selbst: zu großzügig formuliert, an einer Stelle
nicht reproduzierbar, an zwei Stellen zu grob gemessen. Das ist genau die
Sorte Fehler, die ein grüner Lauf nicht zeigt.

**Ein Fund ist nicht behoben, sondern eingeordnet:** Der Kartenkopf trägt bei
einer reinen „nicht nötig"-Zeile weiterhin die orange Plakette „steht aus",
während die Zeile darunter neutral ist. Das ist gewollt — der Kopf zählt, was
der Knopf ausführen würde, und der Knopf führt *alle* ausstehenden aus. Steht
in Abschnitt 3.

---

## 3. Was bewusst stehen bleibt

- **Die Warnung „Es gibt noch KEIN Komplett-Backup"** erscheint auch dann,
  wenn nur ein Vermerk nachzutragen ist und nichts gelöscht werden kann. Das
  bleibt: Der Knopf führt *alle* ausstehenden Migrationen aus, und die
  Vorsicht gilt dem Knopf, nicht der einzelnen Zeile. Auf dem Produktivserver
  steht ohnehin ein Komplett-Backup — dort erscheint die ruhige Fassung mit
  Zeitpunkt und Alter.
- **Der Anzeigestatus heißt `skip`**, obwohl die Registerspalte bereits
  `skipped` kennt und beide Verschiedenes bedeuten (`skip` = noch nicht
  verbucht, `skipped` = verbucht). So entschieden am 06.09.2026, weil der
  Backlog-Eintrag ihn so nennt; die Verwechslungsgefahr ist an beiden Stellen
  im Code kommentiert und in `Technik.md` als Kasten festgehalten.
- **Nr. 151 ist nicht mitbehoben** (K4). Die Behebung berührt die
  Import-Schnittstelle und ist damit mehr als ein Name.
- **Der Kartenkopf bleibt orange, die Zeile ist neutral.** Steht nur eine
  „nicht nötig"-Migration an, meldet die Karte „1 Update · steht aus"
  (orange), während die Zeile darunter die neutrale Plakette trägt. Das sieht
  widersprüchlich aus und ist es nicht: Der Kopf zählt, was der Knopf täte,
  und der Knopf führt **alle** ausstehenden aus — die nächste kann eine
  Spalte löschen. Die Zeile beschreibt dagegen genau diese eine Migration.
  Aus demselben Grund bleibt die Backup-Warnung stehen.

---

## 4. Prüfliste für den Auftraggeber — zwei Punkte, nach dem Deploy

**Beide Punkte sind fällig.** Web 15.5.2 ist am 06.09.2026 als **PR #36** auf
`main` gemergt, und damit ist der Deploy gelaufen. Erste Probe: Die Fußzeile
jeder Seite muss **`v15.5.2`** nennen; steht dort noch 15.5.1, ist der Deploy
nicht durchgekommen oder der Browser zeigt alte Dateien (dann einmal hart neu
laden).

> **Was danach noch nachkommt, ändert an diesen zwei Punkten nichts.** Der
> Nachtrag aus dem adversarialen Durchgang (Abschnitt 2a) liegt auf dem
> Arbeitszweig und betrifft Zahlen, Verweise und die Prüfmittel; sein einziger
> Anteil unter `server/` ist ein Kommentar. Was du gleich bedienst, ist der
> Stand, der geprüft wurde.

### Punkt 1 — Betrieb → Updates: den Registervermerk nachholen

**Bedienweg**

1. Als BetreiberIn anmelden, **Betrieb → Updates** öffnen.
2. In der Karte **„Ausstehende Updates"** steht **1 Update**, und darunter
   die Zeile *„Dritte Rolle ‚BetreiberIn'; alle vorhandenen Admins werden
   BetreiberInnen (R75)"* mit der grauen Plakette **„nicht nötig"** und der
   Kennung `2026_09_05_rolle_betreiberin`.
3. **„Ausstehende ausführen"** drücken.

**Erwartetes Ergebnis**

- Die Meldung lautet *„Die ausstehenden Updates wurden angewendet — unten
  steht je Eintrag, was geschehen ist."*
- Die Karte meldet danach **„alles aktuell"**, der Knopf ist weg.
- In **„Ausgeführt"** steht die Zeile mit *„Nicht nötig (Schema bereits
  aktuell) — als erledigt vermerkt."*; die Karte **„Fassung"** nennt als
  Datenbankstand `2026_09_05_rolle_betreiberin`.
- **Betrieb → Status**, Zeile „Updates": **„Alles aktuell"**, blau.
- Der Zähler am Menüpunkt „Updates" verschwindet.

**Woran ein Scheitern zu erkennen ist**

- **Die Karte meldet „alles aktuell", obwohl Status und Menü eine 1 zeigen,
  und es gibt keinen Knopf** → 15.5.2 ist nicht ausgeliefert. Fußzeile
  prüfen.
- **Die Zeile trägt eine rote Plakette „Fehler"** → der Anzeigestatus kommt
  auf der Seite an, aber die Plakettenauswahl kennt ihn nicht; dann ist eine
  der beiden Stellen in `betrieb_updates.php` nicht mitgekommen.
- **Die Meldung lautet „Es war nichts anzuwenden"** → der Vermerk ist zwar
  geschrieben (das prüfst du in „Ausgeführt"), aber die Meldung stammt vom
  alten Stand.
- **Der Zähler am Menüpunkt steht nach dem Klick noch auf 1** → **kein
  Fehlschlag.** Er liegt bis zu **60 Sekunden** in einem Zwischenspeicher.
  Eine Minute warten, neu laden. Steht er dann immer noch, ist etwas anderes
  offen — die Seite Updates sagt, was.
- **Betrieb → Updates antwortet mit 403** → die Rolle fehlt dem Konto. Dann
  ist der Fall aus Nr. 149 (a) eingetreten; der Notweg steht in
  `docs/Technik.md`, Abschnitt 7.

**Danach, als Zugabe:** Verwaltung → NutzerInnen ansehen — jedes frühere
Admin-Konto muss **„BetreiberIn"** heißen. Das hakt zugleich P-02 der
S8-Prüfliste ab.

### Punkt 2 — Der Knopf „Diensttage zusammenführen"

**Bedienweg**

1. Einen Diensttag öffnen, auf dem die **Überschneidungswarnung** steht
   („Dieser Diensttag überschneidet sich zeitlich mit …"). Gibt es gerade
   keinen: Die Warnung erscheint, sobald zwei eigene Diensttage sich um mehr
   als **eine Viertelstunde** überschneiden — etwa nachdem Uhr und Handy
   denselben Dienst aufgezeichnet haben. Notfalls einen zweiten Diensttag von
   Hand mit überlappender Zeit anlegen und ihn danach löschen.
2. Im Kasten der Warnung auf **„Diensttage zusammenführen"** drücken.

**Erwartetes Ergebnis**

- Die Seite **„Diensttag aufnehmen"** öffnet sich (HTTP 200).
- Die Unterzeile nennt den **geöffneten** Tag: *„Aufnehmender Diensttag:
  &lt;Datum, Uhrzeit&gt; · &lt;Rettungsmittel&gt; · &lt;Standort&gt; — dieser
  bleibt."*
- In der Karte **„Aufzunehmender Diensttag"** steht der andere Tag zur Wahl.
- **Es wird nichts zusammengeführt.** Die Seite zeigt erst eine Vorschau und
  fragt nach; von hier führt kein Klick unmittelbar in eine Zusammenführung.

**Woran ein Scheitern zu erkennen ist**

- **Eine Fehlerseite „Nicht gefunden" mit dem Text „Diensttag nicht
  gefunden"** → der alte Stand. Genau das war Nr. 148; dann ist 15.5.2 nicht
  ausgeliefert.
- **Die Seite öffnet sich, aber die Unterzeile nennt einen anderen Tag als
  den, von dem du kamst** → der Verweis trägt die falsche Kennung. Bitte mit
  beiden Datumsangaben melden.
- **Die Warnung erscheint gar nicht** → dann überschneiden sich die Tage um
  weniger als eine Viertelstunde, oder einer liegt im Papierkorb. Das ist
  kein Fehler dieser Stufe (Handbuch 4.5b).

---

## 5. Grenzen der benutzten Prüfmittel

- **`tools/linkprobe/` prüft Namen, nicht Werte.** Ein `?d=<Datum>` an einer
  Seite, die dort eine *Kennung* erwartet, ist für sie in Ordnung — er ist es
  nicht. Genau das ist die zweite Hälfte von Nr. 151. Drei weitere Grenzen,
  alle in ihrer LIESMICH: Sie sieht keinen Verweis, dessen **Ziel** erst zur
  Laufzeit entsteht (fünf in `admin_stammdaten.php`, von Hand nachgesehen und
  richtig); sie liest nur, was in **einem** Zeichenkettenliteral zusammensteht
  (`'?export=csv&sort=' . e($sort) . '&richtung='` fällt durch); und einen
  Verweis **ohne** Parameter sieht sie gar nicht an.
- **`tools/wartungsprobe/` Teil 6 misst die Zählweise, nicht die
  Migrationen.** Ob eine Migration richtig migriert, steht anderswo. Und er
  misst nur, was über HTTP sichtbar ist: Statuscode und Markup.
- **Der Bilderlauf misst Überlauf, Konsolenfehler und Knopfhöhen — keine
  Plakettentexte.** Eine falsche rote „Fehler"-Plakette fände er nicht; dafür
  ist Erwartung 25 der Wartungsprobe da.
- **Er belegt außerdem nur etwas, wenn die Prüfinstallation eine ausstehende
  Migration hat.** Sonst zeigten die Aufnahmen „Alles aktuell" und maßen den
  geänderten Weg gar nicht (der Fall F-P3-AQ). Die beiden Bilderläufe dieser
  Stufe sind deshalb **mit** ausstehender Migration gefahren. Den Zustand
  *ohne* zeigen die Einzelbilder des Browserlaufs (Abschnitt 1), nicht der
  Bilderlauf — für ihn gibt es dazu keine Zahl.
- **Es gibt keine automatisierten Tests für den Webteil** (`CLAUDE.md` 6).
  Alles oben ist entweder ein eigenes Prüfmittel oder ein Browserlauf.
