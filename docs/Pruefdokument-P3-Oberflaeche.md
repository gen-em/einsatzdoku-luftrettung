# Prüfdokument — P3 „Oberflächen-Redesign", Web 9.0.0 ff.

**Programm:** Gen-EM NAdoku · **Phase:** P3 · **Konzept:**
`Konzept-P3-Oberflaeche.md` · **Zweig:** `claude/konzept-p3-umsetzen-c4zctj`
**Stand:** 26.08.2026, nach Arbeitspaket **O2** und der Fable-Kontrolle (Web 9.1.1)

---

## 0. Wozu dieses Dokument

Das Konzept beantwortet die Frage **„ist die Änderung belegt?"**. Dieses
Dokument beantwortet die andere: **„was muss ich noch prüfen, und wie?"**

**Abschnitt 5 ist die Arbeitsliste.** Alles davor sagt, warum sie so
aussieht. Es wächst mit jedem Arbeitspaket; solange die Phase läuft, ist es
unvollständig — und sagt das an jeder Stelle, an der es das ist.

---

## 1. Was **nicht** geprüft werden konnte

Das steht hier vorn, nicht in einer Fußnote.

### 1.1 Nur Chromium — und daran hängt jedes Symbol

Die Umsetzungsumgebung hat **ausschließlich Chromium**. WebKit (Safari,
iPhone, iPad) und Gecko (Firefox) stehen nicht zur Verfügung und lassen sich
nicht nachinstallieren.

Das ist in dieser Phase kein Randthema, sondern **der wichtigste offene
Punkt**, und zwar aus einem konkreten Grund: Alle 44 Symbole der neuen
Oberfläche werden per **Verweis auf eine externe Datei** eingebunden —

```html
<svg class="symbol"><use href="assets/images/symbole/haus.svg?v=9.0.0#i"></use></svg>
```

In Chromium trägt das nachweislich (Abschnitt 3). Trägt es in Safari auf dem
iPhone **nicht**, dann fehlt auf dem Gerät, das das Konzept zum Normalfall
erklärt, **jedes einzelne Symbol** — und zwar lautlos: keine Fehlermeldung,
nur leere Stellen, wo Menü, Winkel, Häkchen, Warnzeichen und Artkennzeichen
stehen sollten.

**Deshalb ist P-1 in Abschnitt 5 der erste Punkt und Pflicht.** Er kostet
zwanzig Sekunden auf einem iPhone und entscheidet, ob die Einbindungsart
bleibt oder in O2 gegen serverseitiges Einbetten getauscht wird. Der Umbau
wäre klein — `ui_symbol()` und `edSymbol()` sind die einzigen beiden Stellen
—, aber er muss **vor** O3 geschehen, nicht danach.

### 1.2 Kein echtes Endgerät

Gemessen sind Fensterbreiten in Chromium, nicht Geräte. Was daran hängt und
hier **nicht** belegt ist:

- **Trefferflächen.** Die 44-px-Regel ist gerechnet (`getBoundingClientRect`),
  nicht mit einem Daumen geprüft.
- **Weiche Tastatur.** Wenn iOS oder Android die Tastatur einblendet,
  schrumpft der sichtbare Bereich — was mit einer klebenden Speichern-Leiste
  geschieht (O5), sagt nur ein Gerät.
- **Sicherer Bereich.** Notch und Home-Indicator (`env(safe-area-inset-*)`)
  sind nicht geprüft.
- **Zeigen ohne Maus.** `:hover`-Zustände gibt es auf einem Touchgerät nicht;
  wo ein Hinweis nur dort steht, fällt er weg.

### 1.3 Was der Zwischenstand nach O6 noch nicht ist

**Der Einsatzweg und die Suche sind neu, die Verwaltungs- und
Auswertungsseiten nicht.** Web 9.1.0 brachte Kopfleiste, Schublade, Leiste,
Fußzeile und den Bausteinvorrat; Web 9.2.0 (O3) die Tagesübersicht;
Web 9.3.0 (O4) die Einsatzansicht; Web 9.4.0 (O5) das Einsatzformular samt
Ortswahl; Web 9.5.0 (O6) die Suche. Was innerhalb der **übrigen** Seiten
steht, ist bis auf die Artzeichen unverändert: Zeitraum, Einstellungen,
Verwaltungslisten, Administration. Sie folgen Paket für Paket in O7 bis O11.

Sichtbare Folgen, die **kein Fehler** sind:

- Tabellen außerhalb von Startseite und Suche scrollen auf schmalen Geräten
  waagerecht in ihrem eigenen Kasten, statt zur Kachel zu werden (O7–O9).
- Meldungen im Seiteninhalt tragen noch **kein Symbol** — sie erscheinen
  seit Web 9.1.1 in den Farben des Meldungs-Bausteins (Übergangsregel,
  F-P3-T); das Symbol kommt mit `ui_meldung()` im jeweiligen Paket. Die
  Gerätemeldung der Startseite hat ihres seit O3.
- Knöpfe im Seiteninhalt der noch nicht umgebauten Seiten sind die alten
  `.btn-*`; sie erscheinen als Rohform aus der Übergangsschicht.
- Die Zeitraumübersicht trägt noch ihre alte Gestalt — das ist O7.

**Dieser Stand gehört nicht auf den Produktivserver.** Er liegt auf dem
Phasenzweig; die Deploy-Action greift nur bei einem Push auf `main`.

---

## 2. Was maschinell geprüft wurde

Jede Zeile nennt das Mittel **und** die Zahl. „Geprüft" ohne Zahl steht hier
nicht.

### 2.1 Nach O1 (Web 9.0.0)

| Was | Mittel | Ergebnis |
|---|---|---|
| Sollmenge des Stylesheets gesichert | `tools/vollstaendigkeit/pruefen.py --vorher` | **220 Klassen** aus den Selektoren des alten Stylesheets |
| Hexfarben außerhalb `:root` | dasselbe Werkzeug | **0** — vorher 78 |
| `rgb()`/`rgba()` mit festen Zahlen außerhalb `:root` | dasselbe | **0** — vorher 8 |
| Schriftgrößen außerhalb der Skala | dasselbe | **0** — vorher 71 Regeln mit 21 verschiedenen Werten |
| Pixelmaße außerhalb der Token | dasselbe | **0** — vorher 154; 5 begründete Ausnahmen in `ausnahmen.md` |
| `50px`-Reste (die alte Kopfhöhe) | dasselbe | **0** — vorher 5 |
| Symboldateien mit Anker `id="i"` | dasselbe | **44 von 44** |
| Kontraste der Token | `tools/screenshots/kontrast.py` | **21 Paare gerechnet, 0 verfehlt**; Primärknopf 5,97:1; 3 benannte Ausnahmen mit Grund |
| Bildaufnahme | `tools/screenshots/aufnehmen.mjs` | **232 Einzelbilder** (29 Seiten × 8 Breiten), 29 Kontaktbögen |
| Waagerechter Überlauf | dieselbe Aufnahme | **26 von 232** — alle bei 360–420 px, alle im Rohstand erwartet; Sollwert 0 am Ende der Phase |
| Konsolenfehler | dieselbe Aufnahme | **0** |
| Knopfhöhen ≠ 44 px | dieselbe Aufnahme | **0** — es gibt in diesem Stand noch keine `.knopf`-Regel |
| Wortliste (R28) | `tools/wortliste/wortliste.py` | **0** außerhalb der Ausnahmen, **0** ungenutzte Ausnahmen, **0** durchgerutschte Teilstring-Fallen |
| Syntax PHP | `php -l` über die geänderten Dateien | fehlerfrei |
| Syntax JS | `node --check` über die geänderten Dateien | fehlerfrei |

### 2.2 Nach O2 (Web 9.1.0)

| Was | Mittel | Ergebnis |
|---|---|---|
| Waagerechter Überlauf | `tools/screenshots/aufnehmen.mjs`, 29 Seiten × 8 Breiten | **0 von 232** — nach O1 waren es 26. Die Messung nennt seither auch das **überlaufende Element**; in jedem der 26 Fälle war es eine `<table>` im Seiteninhalt, keine Stelle des Gerüsts |
| Konsolenfehler | dieselbe Aufnahme | **0** |
| Knopfhöhen ≠ 44 px | dieselbe Aufnahme, nur sichtbare Knöpfe | **0** |
| Emoji im Markup | `tools/vollstaendigkeit/pruefen.py` | **9** — vorher 80. Alle neun in `einsatz.php` (O4) |
| Inline-SVG mit Pfaden | dasselbe | **3** — vorher 5. Karten-Pin zweimal, Vollbildknopf einmal (O3) |
| Klassen des alten Stylesheets | dasselbe | 220 Sollmenge · **25 auf der Streichliste** mit Grund · 194 offen (O3–O11) |
| Werte außerhalb der Token | dasselbe | weiterhin 0 / 0 / 0 / 0 / 0 |
| `style="…"`-Attribute | dasselbe | **13** — vorher 14 |
| Kontraste | `tools/screenshots/kontrast.py` | 21 Paare, **0 verfehlt** |
| Wortliste (R28) | `tools/wortliste/wortliste.py` | **0 / 0 / 0**; fünf neue Ausnahmen der Klasse *Homonym* |
| Syntax | `php -l` über alle 57 PHP-Dateien, `node --check` | fehlerfrei |

### 2.2a Nach der Fable-Kontrolle (Web 9.1.1)

Neun Funde (F-P3-Q bis F-P3-Y im Konzept, 9.2), alle behoben; danach
derselbe volle Lauf:

| Was | Ergebnis |
|---|---|
| Bildaufnahme | 232 Bilder — **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** |
| Werte außerhalb der Token | 0 / 0 / 0 / 0 / 0 |
| Streichliste | **38 Einträge** mit Grund, davon 35 aus der Sollmenge des alten Stylesheets (drei — u. a. `imp-pw` — waren nur Markup-Klassen ohne eigene Regel) |
| Dialoge im Browser | Bestätigungs- und Entsperrdialog bei 390 px fotografiert, gegen Mockup 11 gehalten |
| Syntax | `php -l` über alle 57 PHP-Dateien, `node --check` — fehlerfrei |

### 2.4 Nach O3 (Web 9.2.0)

Alle Läufe auf dem Endstand des Pakets (ein erster voller Bilderlauf hatte
Zwischenstände erwischt und wurde verworfen und wiederholt):

| Was | Ergebnis |
|---|---|
| Bildaufnahme | 232 Bilder, 29 Kontaktbögen — **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** (49 Regeln, 49 gegriffen) |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Vollständigkeit | Sollmenge 220 — 11 mit Regel, **51 auf der Streichliste** (54 Einträge gesamt), 158 offen auf noch nicht umgebauten Seiten |
| Bediensonden (Playwright, 390 px) | Sortierblatt: „Einsatzort" sortiert Kacheln und Tabelle **identisch**; Bearbeiten-Umschalter: Formular sichtbar, Leseansicht weg; Tagesblatt: fünf Einträge — je 0 Konsolenfehler |
| Karte im Browser | Zoom auf die Spuren (nicht Rückfallstufe 7), Kacheln geladen, Haus-/Klinik-Schild, orange Kreise, Pfeile — bei 390/1440/1920 |
| Syntax | `php -l` über alle PHP-Dateien, `node --check` über alle Skripte — fehlerfrei |

### 2.5 Nach O4 (Web 9.3.0)

| Was | Ergebnis |
|---|---|
| Bildaufnahme | 232 Bilder, 29 Kontaktbögen — **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Emoji im Markup | **0** (vorher 9 — die Schloss-Zeichen der Einsatzansicht) |
| Inline-SVG mit Pfaden | **0** (vorher 1 — der doppelte Karten-Pin) |
| Vollständigkeit | Sollmenge 220 — auf der Streichliste jetzt **64 Sollmengen-Klassen** (67 Einträge gesamt) |
| Bediensonden (Playwright, 1440) | Entsperr-Fluss (Abbruch/Passwort), „kein Ende", Reanimation × 2, Teilstück (4 → 5 Pfade), Luft-Einsatz mit Haus-Schild und Ringen, Luftlinien-Einsatz — Einzelheiten im Konzept, Abschnitt 11/O4 |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

### 2.6 Nach O5 (Web 9.4.0)

| Was | Ergebnis |
|---|---|
| Bildaufnahme | 232 Bilder, 29 Kontaktbögen — **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Rundlauf Formular | 5 Einsätze unverändert gespeichert — **0 Abweichungen** (API-Antwort samt entschlüsseltem Patientenblock, schlüsselstabil verglichen) |
| Kreislauf Sicherung | **286 739** Einzelvergleiche, **0 unerklärte** Abweichungen (16 erwartete) |
| Kreislauf CSV | **8 797** Einzelvergleiche, **0 unerklärte** Abweichungen (859 erwartete) — nach Werkzeug-Fix F-P3-AF |
| Bediensonden | Sofort-Sortierung, Zähler, Speichern-Leiste, Pin-Blatt, Geolocation (überstellt), Kartendialog — Einzelheiten im Konzept, Abschnitt 11/O5 |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

### 2.7 Nach O6 (Web 9.5.0)

| Was | Ergebnis |
|---|---|
| **Abnahme Suche (Kernzusage)** | **8 Proben · 8 identisch · 0 abweichend · 143 Treffer verglichen** — fünf Freitexte und drei Filterkombinationen, gleichzeitig gegen den Vor-P3-Stand `2e4f4fe` (zweiter Git-Arbeitsbaum, Port 8444) und die neue Fassung (8443), verglichen über die Einsatz-IDs der Trefferzeilen |
| Bildaufnahme | 232 Bilder, 29 Kontaktbögen — **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls** |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** (0 Treffer, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen; 49 Regeln, 49 gegriffen) |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Streichliste | Sollmenge 220 — **39 mit Regel, 73 gestrichen** (vier neue in O6), 0 zugleich gestrichen und beregelt |
| `style="…"`-Attribute | 16 (vorher 15): **+1** durch die Streifenspalte der Suchtabelle — dieselbe begründete Ausnahme wie beim Kachelstreifen aus O3, eine Farbe **aus den Daten** kann keine CSS-Regel sein |
| Bediensonde 1440 | Filter setzen und über die Plakettenzeile abwählen, Gruppenzahlen, Sortierblatt — 0 Konsolenfehler |
| Bediensonde 390 | Kacheln statt Tabelle (`true` / `false`); Schubladenfuß „82 Treffer zeigen" → nach Filter „6 Treffer zeigen", Knopfzahl 1 → nach Klick: Schublade zu, „6 von 82 · 59 km", 6 Kacheln |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

**Der Sollwert der Knopfhöhe ist ab O6 nicht mehr überall 44 px.** Löschkreuz
und Filterknopf stehen neben dem 48 px hohen Suchfeld und sind deshalb
ebenfalls 48 px hoch — ein 44er daneben stünde sichtbar schief. Das
Prüfwerkzeug duldet das nicht stillschweigend, sondern kennt die Ausnahme
beim Namen (`suchzwilling`) und nennt im Bericht je Ausnahme den Sollwert.

### 2.3 Der Vorher-Stand, zum Gegenhalten

Erhoben am Stand `main` 2e4f4fe (Web 8.0.1), bevor das Stylesheet ersetzt
wurde. Diese Zahlen sind der Maßstab, an dem sich das Ende der Phase messen
lässt:

| | vorher | Sollwert Ende P3 |
|---|---|---|
| Hexfarben außerhalb `:root` | 78 | 0 |
| Schriftgrößen außerhalb der Skala | 71 | 0 |
| Pixelmaße außerhalb der Token | 154 | 0 |
| `50px`-Reste | 5 | 0 |
| `style="…"`-Attribute in PHP/JS | 14 | 0 |
| Inline-SVG mit Pfaden | 5 | 0 |
| Unicode-Zeichen als Symbol | 147 | 0 |
| Emoji im Markup | 80 | 0 |
| Klassen im Markup ohne Regel | 22 | 0 |

---

## 3. Was im Browser geprüft wurde

Chromium, echtes Stylesheet, laufende lokale Instanz mit dem
Referenzdatensatz, Konsole mitgelesen.

| Fall | Ergebnis |
|---|---|
| Symbolprobe: alle 44 Zeichen in vier Zuständen — 24 px dunkelblau, 20 px orange tief, gedreht **und** gefüllt, im Primärknopf auf Orange | Alle 44 erscheinen; `currentColor` wirkt in allen vier Zuständen; die Drehung des Winkels wirkt (`menu` um 90° zeigt senkrechte Balken, `pfeil-hoch` um 90° zeigt nach rechts); **0 Konsolenfehler** |
| Logos: farbig auf Schnee, weiß auf Dunkelblau, je Hubschrauber und NEF-Platzhalter | Beide tragen die Markenfarben; der Platzhalter ist am gestrichelten Rahmen erkennbar |
| Favicons bei 16, 32 und 128 px | Bei 32 px beide klar erkennbar; bei 16 px der Hubschrauber besser als das NEF (der Platzhalterrahmen kostet Fläche) |
| Entschlüsselung nach der Anmeldung | Diagnose („Schädel-Hirn-Trauma bei Motorradunfall") und Einsatzort („Brunnengasse 66, 87411 Auwiesen") erscheinen im Klartext; kein Entsperrdialog |
| Kartenkacheln und Ortssuche | `tile.openstreetmap.org` und `photon.komoot.io` antworten mit HTTP 200 — Karte und Adresssuche sind in dieser Umgebung prüfbar |

---

## 4. Grenzen der Prüfmittel

`tools/vollstaendigkeit/LIESMICH.md` und `tools/screenshots/LIESMICH.md`
nennen sie vollständig. Die vier, die hier zählen:

1. **Die Vollständigkeitsprüfung misst Text, keine Darstellung.** Ob eine
   Regel richtig aussieht, sagt nur der Browser.
2. **Klassen aus zusammengesetzten Zeichenketten** (`'imp-' + art`) erkennt
   sie nicht. Das ist Absicht: Die erste Fassung zählte jedes Wort im
   Quelltext und kam auf 14 784 „Klassen".
3. **Die Bildaufnahme kennt nur die Bedienzustände, die in `seiten.json`
   stehen.** Ein geöffnetes Aktionsblatt, ein aufgeklappter Kartenkopf, ein
   Dialog: Was nicht in der Liste steht, ist nicht im Bild. Die Liste wächst
   mit den Paketen.
4. **Das Bild sagt nicht, ob es richtig ist**, sondern wie es aussieht. Der
   Abgleich gegen die Mockups bleibt Sichtprüfung.

---

## 5. Prüfliste

**Lesart:** *Weg* = was zu tun ist. *Erwartet* = was dastehen muss.
*Fehlschlag heißt* = was es bedeutet, wenn es nicht so ist — der wichtigste
Teil, denn sonst prüft man, **ob** etwas erscheint, statt **was**.

**⬤ Pflicht** — hier kann etwas kaputt sein, das kein Werkzeug gesehen hat.
**○ Sichtprüfung** — reine Bestätigung; eine Abweichung wäre überraschend.

### 5.1 Vor allem anderen

- [ ] **P-1 ⬤ Symbole auf einem iPhone.**
      *Weg:* Den Zweigstand irgendwo erreichbar machen (lokal genügt) und die
      Symbolprobe oder — ab O2 — eine beliebige Seite auf einem **iPhone in
      Safari** öffnen. Ersatzweise iPad, ersatzweise Safari auf einem Mac.
      *Erwartet:* Die Strichzeichnungen erscheinen, in der Farbe ihrer
      Umgebung.
      *Fehlschlag heißt:* Zweierlei, und beides ist ernst. Sind die Stellen
      **leer**, unterstützt WebKit den externen Verweis nicht — dann muss
      `ui_symbol()` den Dateiinhalt serverseitig einbetten, und `edSymbol()`
      braucht denselben Vorrat als Datenblock. Erscheinen **schwarze
      Klumpen**, kommen die Strichattribute aus `.symbol` nicht im geklonten
      Baum an — dann müssen sie zusätzlich als Attribute am Wirts-`<svg>`
      stehen.
      *Warum zuerst:* Ab O2 hängen alle Bausteine daran. Ein Wechsel der
      Einbindungsart ist jetzt eine Funktion; nach O11 ist er eine Phase.

- [ ] **P-2 ○ Dasselbe in Firefox.**
      *Weg:* Beliebige Seite in Firefox öffnen.
      *Erwartet:* wie in Chromium.
      *Fehlschlag heißt:* dasselbe wie P-1, aber weniger dringend — Firefox
      ist auf dem Handy selten.

### 5.2 Nach O1 (Web 9.0.0)

- [ ] **L-1 ○ Die Logos tragen die Markenfarben.**
      *Weg:* `server/assets/images/gen-em_logo_helicopter.svg` in einem
      Browser öffnen, daneben `docs/Branding.md` B1.
      *Erwartet:* Rot `#D63338`, Blau `#4280E5`, Orange `#FF8F1F`, Asphalt
      `#1A0500`. Kein `#E3322B`, kein `#587ABC`, kein `#F7941D`.
      *Fehlschlag heißt:* Die Korrektur aus O1 ist nicht angekommen — oder
      jemand hat die Datei aus einem alten Stand zurückgeholt.

- [ ] **L-2 ⬤ Der NEF-Platzhalter ist als solcher erkennbar.**
      *Weg:* `gen-em_logo_fahrzeug.svg` ansehen, dazu `favicon-fahrzeug.png`
      bei 16 und 32 px.
      *Erwartet:* Ein Rettungsfahrzeug in den Markenfarben, umgeben von einem
      **gestrichelten Rahmen** in Sand.
      *Fehlschlag heißt:* Fehlt der Rahmen, sieht der Platzhalter aus wie ein
      fertiges Logo — und geht als solches in eine Abnahme.
      *Zu entscheiden:* Ob die Zeichnung als Platzhalter taugt oder ob sie
      bis zur echten Datei ersetzt werden soll. Das ist eine
      Gestaltungsfrage, keine technische.

- [ ] **L-3 ○ Favicon und Logo passen zusammen.**
      *Weg:* `node tools/logos/erzeugen.mjs` laufen lassen, danach
      `git status`.
      *Erwartet:* Keine Änderung — die eingecheckten Favicons stammen aus den
      eingecheckten Logodateien.
      *Fehlschlag heißt:* Jemand hat eine Logodatei geändert, ohne die
      Favicons neu zu erzeugen. Genau so ist das Favicon zu den falschen
      Markenfarben gekommen.

- [ ] **W-1 ○ Die Prüfmittel laufen.**
      *Weg:*
      `python3 tools/vollstaendigkeit/pruefen.py` ·
      `python3 tools/screenshots/kontrast.py` ·
      `cd tools/wortliste && python3 wortliste.py`
      *Erwartet:* Die Zahlen aus Abschnitt 2.1.
      *Fehlschlag heißt:* Weicht eine Zahl ab, hat sich seit O1 etwas
      geändert, das niemand eingetragen hat.

- [ ] **W-2 ○ Die Bildaufnahme läuft.**
      *Weg:* `sh tools/referenzdatensatz/einspielen/lokal_starten.sh`, dann
      `node tools/screenshots/aufnehmen.mjs --nur 10-`.
      *Erwartet:* Acht Bilder und ein Kontaktbogen unter
      `tools/screenshots/ausgabe/`, 0 Konsolenfehler.
      *Fehlschlag heißt:* Meldet der Lauf „Anmeldung gescheitert", stimmen
      die Zugangsdaten der lokalen Installation nicht; meldet er „nicht
      aufgelöst", ist der Referenzdatensatz nicht eingespielt.

### 5.3 Nach O2 (Web 9.1.0)

- [ ] **G-1 ⬤ Die Schublade auf dem Handy.**
      *Weg:* Startseite auf einem schmalen Fenster (unter 1024 px) öffnen →
      Knopf mit den drei Strichen links in der Kopfleiste → Schublade.
      Nacheinander alle drei Schließwege probieren: das × oben links, die
      abgedunkelte Fläche daneben, die **Esc-Taste**.
      *Erwartet:* Die Schublade schiebt sich von links herein und trägt oben
      Startseite und Suche, darunter die Diensttage, unten „Diensttag anlegen"
      und Papierkorb. Alle drei Wege schließen sie. Solange sie offen ist,
      lässt sich die Seite dahinter **nicht** scrollen.
      *Fehlschlag heißt:* Bleibt der Hintergrund scrollbar, greift
      `overflow:hidden` am Körper nicht — auf einem Touchgerät scrollt dann
      unter dem Finger die falsche Ebene. Schließt Esc nicht, ist
      `schublade.js` nicht geladen.

- [ ] **G-2 ⬤ Die Schublade auf der Suchseite.**
      *Weg:* Dasselbe auf `suche.php`.
      *Erwartet:* Der Menüknopf ist da, und die Schublade enthält die
      **Filter**.
      *Fehlschlag heißt:* Fehlt der Knopf oder ist die Schublade leer, hängt
      der Mechanismus doch an der Diensttagsfunktion statt an der Klasse —
      genau die Falle, vor der die Vormerkliste aus Konzept P0 warnt.

- [ ] **G-3 ⬤ Tastatur in der Schublade.**
      *Weg:* Schublade mit der Tastatur öffnen (Tab bis zum Menüknopf, Enter),
      dann mehrfach Tab drücken.
      *Erwartet:* Der Fokus läuft im Kreis **innerhalb** der Schublade. Nach
      dem Schließen steht er wieder auf dem Menüknopf.
      *Fehlschlag heißt:* Wandert der Fokus hinter den Schleier, bedient man
      Elemente, die man nicht sieht.

- [ ] **G-4 ○ Die Leiste am breiten Bildschirm.**
      *Weg:* Fenster über 1024 px ziehen.
      *Erwartet:* Der Menüknopf verschwindet, die Leiste steht fest links,
      „Startseite" und „Suche" erscheinen in der Kopfleiste, die aktive Seite
      trägt einen orangen Strich darunter. Ab 1200 px steht der Name des
      Rettungsmittels neben dem Datum.
      *Fehlschlag heißt:* Bleibt der Körper nach dem Ziehen unscrollbar, räumt
      der Breitenwechsel den Schubladenzustand nicht ab.

- [ ] **G-5 ⬤ Das Akkordeon der Diensttage.**
      *Weg:* In der Leiste ein anderes Jahr anklicken — irgendwo auf der Zeile,
      nicht nur auf dem Winkel. Dann das **Balkensymbol** rechts in einer
      Monatszeile.
      *Erwartet:* Der Klick auf die Zeile klappt auf und zu und schließt das
      vorher offene Jahr. Der Klick auf das Balkensymbol öffnet die
      Monatsübersicht (`zeitraum.php`).
      *Fehlschlag heißt:* Springt der Klick auf die Zeile in die
      Zeitraumübersicht, ist die alte Aufteilung zurück — und auf einem
      Touchgerät kommt man dann nicht mehr ans Auf- und Zuklappen.

- [ ] **G-6 ○ Die Fußzeile steht auf jeder Seite.**
      *Weg:* Anmeldeseite, „Passwort vergessen", Abmeldeseite, eine
      Inhaltsseite, eine Einstellungsseite, `einsatz.php?id=0` (Abbruchseite).
      *Erwartet:* Überall unten „© Gen-EM · Open Source · AGPL-3.0 · v9.1.0" —
      auf der Anmeldung hell auf Dunkelblau, sonst gedämpft.
      *Fehlschlag heißt:* Fehlt sie auf einer Seite, ruft diese Seite
      `ui_geruest_ende()` nicht.

- [ ] **G-7 ○ Die Artzeichen sind gezeichnet, nicht getippt.**
      *Weg:* Leiste ansehen, dazu die Spalte „Art" in der Suche.
      *Erwartet:* Ein Hubschrauber, ein Rettungswagen und ein gestrichelter
      Kreis — **in derselben Farbe wie der Text daneben**, nicht in
      Systemfarben.
      *Fehlschlag heißt:* Erscheinen wieder 🚁 und 🚑, kommt `ART_SYMBOLE`
      aus einem alten Zwischenspeicher. Erscheint gar nichts, siehe P-1.

- [ ] **E-1 ⬤ Der Einrichter.**
      *Weg:* **Nicht am Produktivsystem.** In einer frischen Installation
      `install.php` öffnen.
      *Erwartet:* Kopfleiste mit Logo, Karte in der Lesespalte, Formular in
      Gruppen, Primärknopf orange mit dunkelblauer Schrift, Fußzeile unten.
      *Fehlschlag heißt:* Sieht die Seite unformatiert aus, findet sie
      `assets/style.css` nicht — der Pfad ist relativ und setzt voraus, dass
      `install.php` im selben Verzeichnis liegt wie die Anwendung.

- [ ] **G-8 ⬤ Die beiden Dialoge.**
      *Weg:* Abmelden anklicken (Rückfrage), und in einer **neuen**
      Registerkarte einen Einsatz mit Diagnose öffnen (Entsperrdialog).
      *Erwartet:* Beide erscheinen als Karte mit abgerundeten Ecken auf
      abgedunkeltem Grund; die Knöpfe unten sind 44 px hoch, „Abbrechen"
      leise, die Haupthandlung orange bzw. rot umrandet; im Entsperrdialog
      wechselt die Meldungszeile zwischen blauem Hinweis („Schlüssel wird
      abgeleitet …") und rosa Fehler.
      *Fehlschlag heißt:* Erscheint ein nackter weißer Kasten, lädt der
      Browser ein altes `confirm.js`/`unlock.js` aus dem Zwischenspeicher —
      hart neu laden. Diese beiden Dialoge stehen in **keiner**
      Screenshot-Seite; hier ist die Sichtprüfung der Beweis (dieselbe Lage
      wie in P0, Prüfdokument D-5).
- [ ] **G-9 ○ Balken-Link an zugeklappten Zeilen.**
      *Weg:* In der Leiste bei einem **zugeklappten** Monat auf das
      Balkensymbol rechts klicken.
      *Erwartet:* Die Monatsübersicht öffnet sich, ohne dass die Zeile vorher
      aufklappt.
      *Fehlschlag heißt:* Fehlt das Symbol an zugeklappten Zeilen, ist der
      Link wieder aus dem `<summary>` gerutscht (F-P3-R).
- [ ] **E-2 ○ Die Einstellungs-Übersicht.**
      *Weg:* Zahnrad in der Kopfleiste.
      *Erwartet:* Eine Liste mit Symbol, Text und Winkel; für Admins ein
      zweiter Block „Administration"; „Abmelden" getrennt am Ende; darunter der
      eigene Name.
      *Fehlschlag heißt:* Landet man direkt auf „Profil", greift die Weiche in
      `einstellungen.php` nicht.

### 5.4 Nach O3 (Web 9.2.0)

- [ ] **Kachelliste auf dem Handy.** *Weg:* Startseite bei < 720 px (oder
      Browserfenster schmal ziehen). *Erwartet:* Je Einsatz eine dreizeilige
      Kachel — Farbstreifen und Beginn, Einsatzort **fett** mit km-Zahl,
      Diagnose, darunter Dauer · Alter und ggf. Plaketten (Winde, Bergwacht,
      Sekundär, Fehleinsatz, „kein Ende" rot). *Fehlschlag heißt:* Erscheint
      stattdessen eine seitwärts scrollende Tabelle, greift die
      720-px-Schwelle nicht; fehlen Ort/Diagnose, ist die Entschlüsselung
      nicht gelaufen (dann muss der Sperrbanner mit „Entsperren" dastehen).
- [ ] **Sortieren mobil = Desktop.** *Weg:* Auf dem Handy das Pfeilsymbol im
      Einsätze-Kopf → „Einsatzort" wählen; dann dasselbe am Desktop per Klick
      auf den Spaltenkopf. *Erwartet:* Dieselbe Reihenfolge in Kacheln und
      Tabelle; zweite Wahl derselben Spalte kehrt sie um. *Fehlschlag heißt:*
      Weichen die Reihenfolgen ab, ziehen Kachel und Tabelle nicht aus
      demselben Zeilenbestand.
- [ ] **Lesezustand und Bearbeiten.** *Weg:* Startseite → Karte
      „Diensttag-Daten" → „Bearbeiten" → Feld ändern → „Speichern".
      *Erwartet:* Erst Leseansicht (oder „Noch keine Angaben"), nach dem
      Klick NUR das Formular, nach dem Speichern wieder NUR die aktualisierte
      Leseansicht. *Fehlschlag heißt:* Stehen Lese- und Formularzustand
      gleichzeitig da, ist der `[hidden]`-Wächter (F-P3-AA) verloren
      gegangen.
- [ ] **Aktionsblatt des Tages.** *Weg:* „···" neben dem Datum. *Erwartet:*
      Auf dem Handy ein Blatt von unten, am Desktop ein Menü am Knopf; fünf
      Einträge (Einsatz nachtragen, Diensttag-Daten bearbeiten, Datum ändern,
      Anderen Diensttag aufnehmen, Tag löschen), „Tag löschen" in Rot;
      Escape schließt. *Fehlschlag heißt:* Fehlt ein Eintrag oder führt
      „Diensttag-Daten bearbeiten" nicht zum aufgeklappten Formular, ist die
      Blatt-Verdrahtung gerissen.
- [ ] **Marker-Satz der Tageskarte.** *Weg:* Diensttag mit Einsätzen und
      Koordinaten öffnen (Referenzdatensatz: 27.12.2026). *Erwartet:* Karte
      zoomt auf die Spuren; Standort als Haus-Schild (mit Ringen an
      Beginn/Ende des Dienstes), Transportziele als Klinik-Schilder,
      Einsatzorte als orange Kreise, kleine Richtungspfeile auf den Spuren,
      Einsätze ohne Track als gestrichelte Linie in der Einsatzfarbe.
      *Fehlschlag heißt:* Zeigt die Karte halb Bayern, ist F-P3-Z zurück;
      fehlen die Schilder, liefert die API keine `base_lat/lon` bzw.
      `dest_lat/lon`.
- [ ] **Zweispalter ab 1600 px.** *Weg:* Fenster ≥ 1600 px breit ziehen.
      *Erwartet:* Karte rechts NEBEN Diensttag-Daten und Einsatzliste, von
      deren Oberkante bis unter die Tabelle; darunter (< 1600) liegt sie als
      Band ÜBER der Einsatzliste. *Fehlschlag heißt:* Bleibt sie in jeder
      Breite oben, greift das `.tag-raster` nicht.
- [ ] **Spaltenausrichtung der Tabelle (ab 720 px).** *Weg:* Startseite breit.
      *Erwartet:* Nr., Dauer, Alter und km rechtsbündig, Haken (Winde,
      Bergwacht, Sekundär) zentriert, Beginn/Ort/Diagnose linksbündig; ohne
      Ende steht die rote Plakette „kein Ende" in der Dauer-Spalte.
      *Fehlschlag heißt:* Alles linksbündig = die Spaltenklassen
      (`zahl-spalte`, `haken-spalte`) fehlen im Markup oder Stylesheet.

---

### 5.5 Nach O4 (Web 9.3.0)

- [ ] **Vier Karten statt Feldliste.** *Weg:* Einsatz aus der Tagesübersicht
      öffnen (Referenzdatensatz: 08.02.2026, Einsatz 1). *Erwartet:* Karten
      Einsatz (mit Plaketten am Fuß), PatientIn, Transport, Besatzung,
      Reanimation; leere Felder fehlen, leere Karten erscheinen nicht.
      *Fehlschlag heißt:* Eine leere Karte mit Titel ohne Inhalt = der
      Sichtbarkeits-Mechanismus (`zeile()`) greift nicht.
- [ ] **Kleinzeile unter dem Einsatzort.** *Weg:* Luft-Einsatz mit Track
      öffnen und entsperren. *Erwartet:* Unter der Adresse klein „731 m ·
      Strecke 5,5 km" (Höhe nur luftgebunden); bei einem Einsatz ohne Track
      zusätzlich „Luftlinie N km". *Fehlschlag heißt:* Höhe als eigene
      Zeile = die Zusammenführung nach dem Entsperren lief nicht.
- [ ] **Zustand der geschützten Angaben als Meldung.** *Weg:* Einsatzseite
      in NEUER Registerkarte öffnen (Adresse kopieren), den Entsperrdialog
      abbrechen. *Erwartet:* Blaue Meldung „gesperrt" mit
      Entsperren-Knopf; keine PatientIn-Karte, kein Einsatzort. Knopf →
      Passwort → Meldung „entsperrt", Karten füllen sich, oranger Kreis
      erscheint. *Fehlschlag heißt:* Bleibt nach dem Passwort beides
      stehen, klemmt der Bannerwechsel in `zeigePat()`.
- [ ] **Phasen mit Minutenabstand und Teilstück.** *Weg:* Einsatz mit
      Track; in der Phasen-Karte auf „Ankunft Einsatzort" zeigen bzw.
      tippen. *Erwartet:* Zeile orange; auf der Karte färbt sich der
      Spurabschnitt von der vorigen Phase bis zu dieser blau; im Kopf die
      Gesamtdauer, je Zeile „+N min". *Fehlschlag heißt:* Keine blaue
      Überlagerung = `track_idx` fehlt in der API-Antwort (harte
      Aktualisierung, dann `api/mission.php` prüfen).
- [ ] **Ringe nach Mockup 26.** *Weg:* Luft-Einsatz 08.02.2026 öffnen.
      *Erwartet:* Haus-Schild mit blauem Ring (Spur beginnt am Standort),
      Klinik-Schild mit rotem Ring (Spur endet dort); beginnt und endet
      eine Spur am selben Ort, ein Doppelring; abseits der Schilder ein
      kleiner Ringpunkt. *Fehlschlag heißt:* Ringe fehlen ganz = die
      Näheprüfung (200 m) griff nicht oder `base_lat` fehlt.
- [ ] **Klebende Spalte ab 1200 px.** *Weg:* Fenster ≥ 1200 px, Seite
      rollen. *Erwartet:* Karte und Einsatzphasen bleiben rechts oben
      stehen, die linken Karten rollen. *Fehlschlag heißt:* Rollt alles,
      fehlt `position:sticky` am `.einsatz-neben`.

### 5.6 Nach O5 (Web 9.4.0)

- [ ] **Karten und Schalter.** *Weg:* Einsatz öffnen → „Bearbeiten".
      *Erwartet:* Karten in der Reihenfolge PatientIn (mit Einsatzort),
      Einsatz (Schalter), Transport, Weitere Rettungsmittel, Abweichende
      Besatzung (zu, „vom Diensttag"), Notizen, Einsatzphasen, Reanimation
      (zu, „keine"); ab 1200 px zwei Spalten. Ein eingeschalteter Schalter
      zeigt seine Detailfelder hinter einer orangen Linie. *Fehlschlag
      heißt:* Häkchen statt Schalter = der Checkbox-Renderer ist zurück.
- [ ] **Phasen sortieren sich sofort.** *Weg:* In einer Phasenzeile die
      Uhrzeit auf einen späteren Wert ändern, Feld verlassen (Tab).
      *Erwartet:* Die Zeile rutscht sofort an die richtige Stelle; Zeilen
      ohne Zeit bleiben hinten; der Kopf zählt („8 von 9"); kein
      Hinweistext. *Fehlschlag heißt:* Ordnet erst das Speichern, fehlt der
      focusout-Weg.
- [ ] **Speichern-Leiste.** *Weg:* Beliebiges Feld ändern. *Erwartet:* Am
      unteren Rand erscheint die klebende Leiste („✓ Änderungen speichern";
      am Desktop mit „Strg + Enter"-Hinweis); nach dem Speichern ist sie
      weg. Ohne Änderung gibt es keinen Speichern-Knopf und keinen
      Abbrechen-Link — der Rückweg oben genügt. *Fehlschlag heißt:* Leiste
      dauerhaft sichtbar = das Dirty-Kennzeichen greift nicht.
- [ ] **Meine Position übernehmen.** *Weg:* Pin-Knopf am Einsatzort →
      „Meine Position übernehmen"; die Standortfreigabe des Browsers
      erlauben. *Erwartet:* Koordinaten-Chip erscheint; ein LEERES Feld
      bekommt die Adresse aus der Umkehrsuche, ein gefülltes bleibt.
      *Fehlschlag heißt:* Meldung „Position nicht verfügbar" trotz
      Freigabe = kein GPS/Netz — mit „Auf der Karte wählen" gegenprüfen.
- [ ] **Auf der Karte wählen.** *Weg:* Pin-Knopf → „Auf der Karte wählen";
      Karte verschieben, „Übernehmen". *Erwartet:* Dialog mit Fadenkreuz in
      der Mitte; übernommen wird die Kartenmitte; danach Chip + ggf.
      Adresse. *Fehlschlag heißt:* Grauer Kasten ohne Karte = Leaflet nicht
      geladen (harte Aktualisierung).
- [ ] **Lupe am Transportziel.** *Weg:* Im Feld „Kreisklinik …" stehen
      lassen, Lupe drücken. *Erwartet:* Vorschläge erscheinen; die Übernahme
      setzt NUR die Koordinaten, der Name im Feld bleibt. *Fehlschlag
      heißt:* Der Name wird ersetzt = die getrennte Suche ist verloren.
- [ ] **Unverändert speichern ändert nichts.** *Weg:* Einsatz öffnen →
      Bearbeiten → sofort speichern; Ansicht vergleichen. *Erwartet:* Alle
      Werte unverändert (nur „editiert" erscheint bei Uhr-Einsätzen).
      *Fehlschlag heißt:* Ein Feld leert sich = ein Renderer liefert den
      Bestandswert nicht mehr ins Formular.

### 5.7 Nach O6 (Web 9.5.0)

- [ ] **Der Filter wirkt überhaupt** (die Behebung von F-P3-AG). *Weg:*
      Suche öffnen, im Block „Einsatz" ein „Datum von" weit in der Zukunft
      setzen (z. B. 01.12.2026). *Erwartet:* Die Trefferzahl fällt sofort
      auf 0, die Liste ist leer. *Fehlschlag heißt:* Die Zahl bleibt beim
      vollen Bestand stehen — dann horcht der Zuhörer wieder ins Leere,
      und **kein** Filter dieser Seite wirkt (das war der Zustand seit
      O2). Zum Gegenprüfen: Freitext und Sortierung wirkten auch damals.
- [ ] **Filterzahl je Gruppe.** *Weg:* Im Block „Bergrettung" den
      Windeneinsatz auf „ja" stellen, dann die Gruppe **zuklappen**. *Erwartet:* Am zugeklappten
      Gruppenkopf steht eine blaue Plakette „1". *Fehlschlag heißt:* Keine
      Plakette = ein gesetzter Filter kann sich unsichtbar verstecken; das
      ist genau der Fall, den sie verhindert.
- [ ] **Plakettenzeile, einzeln abwählbar.** *Weg:* Zwei Filter setzen,
      dann über der Trefferliste bei einer Plakette das ✕ drücken.
      *Erwartet:* Nur dieser Filter fällt weg, das Feld in der Leiste
      steht wieder auf „Egal", die Trefferzahl steigt entsprechend.
      *Fehlschlag heißt:* Es fallen beide weg = das ✕ setzt zurück statt
      abzuwählen.
- [ ] **Mobiler Weg (360–719 px).** *Weg:* Fenster auf 390 px, Suche
      öffnen, „Filter" drücken. *Erwartet:* Die Schublade legt sich über
      die Seite; im Fuß stehen „Filter zurücksetzen" und „*n* Treffer
      zeigen" mit dem vollen Bestand; ein Filter ändert diese Zahl **sofort**, bevor man
      schließt; „n Treffer zeigen" schließt die Schublade, und darunter
      stehen genau so viele Kacheln — keine Tabelle. *Fehlschlag heißt:* Die
      Zahl im Fuß weicht von der Zahl über der Liste ab = beide werden
      aus verschiedenen Ständen gefüllt.
- [ ] **Tablet-Schwelle.** *Weg:* Breite 768, dann 1024. *Erwartet:* Bei
      768 ein Filterknopf neben dem Suchfeld und keine Leiste; bei 1024
      die Leiste dauerhaft links, kein Filterknopf. *Fehlschlag heißt:*
      Beides gleichzeitig = die Schwelle 1024 stimmt an einer Stelle
      nicht.
- [ ] **Trefferwörter hervorgehoben.** *Weg:* Entsperren, dann „sturz"
      suchen. *Erwartet:* In den Spalten Einsatzort und Diagnose ist
      „Sturz" gelb hinterlegt (Groß-/Kleinschreibung egal). *Fehlschlag
      heißt:* Sichtbares `<mark>` als Text = die Hervorhebung läuft vor
      dem Maskieren statt danach; dann wäre auch ein `<` aus den Daten
      Markup — sofort melden. Eine Zeile **ohne** Markierung ist dagegen
      richtig: Gesucht wird auch in Notizen, Besatzung und
      Rettungsmitteln, und die stehen nicht in der Liste.
- [ ] **Verneintes wird nicht markiert.** *Weg:* `bergwacht -winde`
      suchen. *Erwartet:* „Bergwacht" ist markiert, „winde" nirgends.
      *Fehlschlag heißt:* Ein markiertes „winde" behauptet einen Treffer,
      der die Zeile gerade **ausgeschlossen** hätte.
- [ ] **Farbstreifen = Kartenfarbe.** *Weg:* Einen Treffer merken, seinen
      Diensttag öffnen. *Erwartet:* Der Streifen links in der Suchzeile
      hat dieselbe Farbe wie die Spur dieses Einsatzes auf der Tageskarte.
      *Fehlschlag heißt:* Die Farben folgen der Listenreihenfolge (erste
      Zeile immer gleich) = der Streifen zählt Positionen statt Einsätze.
- [ ] **Geteilter Link.** *Weg:* Eine Suche mit Filtern aufbauen, die
      Adresszeile mitsamt `#…` kopieren, in einem neuen Fenster öffnen.
      *Erwartet:* Dieselben Filter, dieselben Treffer. *Fehlschlag heißt:*
      Leere Filter = das Fragment wird nicht mehr gelesen; alte geteilte
      Links wären damit wertlos.

## 6. Was bewusst **nicht** geprüft wird

| Bereich | Warum nicht |
|---|---|
| Uhr-App (`watch/`) | in P3 nicht angefasst; `Const.mc` zählt getrennt. Die Logo-Wahl auf der Uhr ist P6 (R29). |
| Datenmodell, Migrationen | O1 berührt kein Schema. Ab O8 gibt es Migrationen; sie bekommen dann eigene Punkte. |
| Rechenwege der Prüfschicht | `validate_lib.php` ist unberührt. |
| Export, Import, Sicherung | unberührt in O1. Die Kreisläufe (P-P3-11/12) laufen vor Abschluss der Phase. |

---

## 7. Wenn etwas nicht stimmt

1. **Hart neu laden** (Strg+Umschalt+R). Vieles, was nach Gestaltungsfehler
   aussieht, ist ein alter Zwischenspeicher — besonders bei den Symbolen, die
   ihren Erkennungswert aus `WEB_VERSION` beziehen.
2. **Browserkonsole ansehen** und die Meldung mitnotieren.
3. **Zurückrollen ist billig:** Die Phase liegt auf einem eigenen Zweig, und
   bis O8 gibt es keine Migration.
4. Fund melden mit: Punktnummer aus Abschnitt 5, Seite, Fensterbreite,
   Konsolenmeldung.
