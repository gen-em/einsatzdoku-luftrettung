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

### 1.3 Was der Zwischenstand nach O2 noch nicht ist

**Die Hülle ist neu, die Seiteninhalte sind es nicht.** Web 9.1.0 bringt
Kopfleiste, Schublade, Leiste, Fußzeile und den Bausteinvorrat. Was
**innerhalb** der Seiten steht, ist bis auf die Artzeichen unverändert: die
Einsatztabelle, die Feldliste der Einsatzansicht, das Formular, die
Verwaltungslisten, die Kacheln der Zeitraumübersicht. Sie folgen Paket für
Paket in O3 bis O11.

Sichtbare Folgen, die **kein Fehler** sind:

- Tabellen scrollen auf schmalen Geräten waagerecht in ihrem eigenen Kasten,
  statt zur Kachel zu werden (O3, O8, O9).
- Meldungen im Seiteninhalt tragen noch **kein Symbol** — sie erscheinen
  seit Web 9.1.1 in den Farben des Meldungs-Bausteins (Übergangsregel,
  F-P3-T); das Symbol kommt mit `ui_meldung()` im jeweiligen Paket.
- Knöpfe im Seiteninhalt sind noch die alten `.btn-*`; sie erscheinen als
  Rohform aus der Übergangsschicht.
- Auf der Tagesübersicht fehlen bei 360 px weiterhin Einsatzort und Diagnose —
  das ist die Kachel aus E-P3-32 und gehört zu O3.

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

### 5.4 Ab O3

*(wächst mit den Arbeitspaketen)*

---

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
