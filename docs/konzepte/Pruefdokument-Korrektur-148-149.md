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
> | Ergebnis | Alles Maschinelle grün; **zwei Punkte bleiben für den Auftraggeber** (Abschnitt 4) |

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
  über alle 30 Seiten. Die Änderung berührt zwei Seiten, und ein Lauf über
  alle 30 hätte eine große Zahl geliefert, die nichts über sie sagt.
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
| `php -l` | die vier geänderten PHP-Dateien: `server/index.php`, `server/migration_lib.php`, `server/betrieb_updates.php`, `tools/wartungsprobe/probe.php` | **0 Syntaxfehler** |
| `tools/linkprobe/probe.py` (neu) | jede Adresse `<seite>.php?<name>=` unter `server/` — PHP **und** JavaScript, **alle** Parameter je Adresse, nicht nur der erste — gegen die `$_GET`/`$_REQUEST`/`filter_input`-Zugriffe der Zielseite | **99 Zielseiten, 131 Verweise, 0 unbekannte Abweichungen**, 10 dynamisch gelesen, 1 bekannte Abweichung mit Nummer (Nr. 151), 0 tote Zeilen |
| dieselbe, **Gegenprobe** gegen den Stand vor der Behebung | ob das Prüfmittel den Fehler findet, den es finden soll | **1 Abweichung**: `server/index.php:181 diensttag_zusammenfuehren.php?ziel= [FEHLT] die Seite liest: d, q` |
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
`style="…"`-Attribute, 227 Unicode-Zeichen als Symbol im Markup; alle übrigen
zehn Prüfungen auf 0). Wer hier „0 Befunde" als Ziel meldete, meldete etwas
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

## 2. Zwei Funde an den Prüfmitteln selbst

Beide sind erst durch die **Gegenprobe** sichtbar geworden, nicht durch den
grünen Lauf. Sie stehen hier, weil sie die Art Fehler sind, die eine grüne
Zahl wertlos macht (`CLAUDE.md` 6).

**Die Linkprobe las nur den ersten Parameter je Adresse.** `?t=rettungsmittel&ev=`
sind zwei; der erste Entwurf sah nur `t`. Acht Verweise fielen so durch, und
gemeldet worden wäre trotzdem eine runde Zahl. Behoben, bevor sie eingecheckt
wurde — die Zahl stieg von 120 auf **131** Verweise und von 4 auf **10**
dynamisch gelesene.

**Die Erwartung „der Knopf ist da" war grün, obwohl kein Knopf da war.** Sie
suchte den Wortlaut „Ausstehende ausführen" — und der steht auf derselben
Seite ein zweites Mal: als Schritt 4 im fünfstufigen Ablauf der Karte
„Wartungsmodus". Gemessen in der Gegenprobe gegen den alten Stand: Erwartung
26 grün bei fehlendem Knopf, also genau im Fehlerfall. Sie misst jetzt gegen
`form="migform"`, das nur der Knopf trägt; die Gegenprobe meldet seither 4
statt 3 Fehlschläge.

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

---

## 4. Prüfliste für den Auftraggeber — zwei Punkte, nach dem Deploy

**Vorbedingung: Web 15.5.2 ist auf `main` und der Deploy ist durch.** Die
Fußzeile jeder Seite muss `v15.5.2` nennen; steht dort noch 15.5.1, ist der
Deploy nicht durch oder der Browser zeigt alte Dateien (dann einmal hart neu
laden).

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
  nicht. Genau das ist die zweite Hälfte von Nr. 151. Und sie sieht keinen
  Verweis, dessen **Ziel** erst zur Laufzeit entsteht; es gibt einen
  (`admin_stammdaten.php:571`), er ist von Hand nachgesehen und richtig.
- **`tools/wartungsprobe/` Teil 6 misst die Zählweise, nicht die
  Migrationen.** Ob eine Migration richtig migriert, steht anderswo. Und er
  misst nur, was über HTTP sichtbar ist: Statuscode und Markup.
- **Der Bilderlauf misst Überlauf, Konsolenfehler und Knopfhöhen — keine
  Plakettentexte.** Eine falsche rote „Fehler"-Plakette fände er nicht; dafür
  ist Erwartung 25 der Wartungsprobe da.
- **Er belegt außerdem nur etwas, wenn die Prüfinstallation eine ausstehende
  Migration hat.** Sonst zeigten die Aufnahmen „Alles aktuell" und maßen den
  geänderten Weg gar nicht (der Fall F-P3-AQ). Die Bilder dieser Stufe sind
  in **beiden** Zuständen aufgenommen.
- **Es gibt keine automatisierten Tests für den Webteil** (`CLAUDE.md` 6).
  Alles oben ist entweder ein eigenes Prüfmittel oder ein Browserlauf.
