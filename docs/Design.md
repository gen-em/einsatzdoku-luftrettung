# Design — Gestaltungsrichtlinie der Einsatzdokumentation

Verbindliche Quelle für alles Sichtbare der Weboberfläche: Farben, Schriften,
Maße, Bausteine, Symbole, Seitentypen. Sie löst `docs/Branding.md` ab
(P3/O12) und nimmt daraus alles auf, was noch gilt.

**Geltungsbereich:** die Weboberfläche (`server/`). Die Uhr-App hat ihre
eigenen Regeln in `docs/Uhr-Layout_Regeln.md`; wo es um Marke und Logo geht,
gilt Kapitel 2 auch für sie.

> **Die technische Wahrheit steht im Stylesheet.** Was hier als Wert steht,
> ist entweder *erzeugt* (Kapitel 4, 7, 8, 9 — `tools/design/tabellen.py`
> liest sie aus den Quellen) oder *Herkunft* (Kapitel 2: woher ein Markenwert
> stammt). Im Code wird ausschließlich über die Token aus `:root` in
> `server/assets/style.css` zugegriffen. Ein Hexwert gehört nie in eine Regel.

---

## 1. Zweck und Freigaberegel

### 1.1 Wofür diese Datei da ist

Damit niemand einen Farbwert rät, eine sechste Knopfvariante erfindet oder
eine neue Seite baut, die aussieht wie keine andere. Der Bestand vor Phase P3
hatte 78 Hexwerte außerhalb der Token, sechs Schaltflächenfamilien mit sechs
ortsgebundenen Größen und 32 Tabellen, von denen eine einzige einen
Überlaufbehälter hatte. Nichts davon war Absicht — es war das Ergebnis vieler
kleiner Entscheidungen ohne gemeinsame Grundlage.

### 1.2 Die Freigaberegel

> **Ein neuer Baustein oder eine neue Darstellung entsteht nur nach
> ausdrücklicher Freigabe mit Mockup. Bis dahin werden vorhandene Bausteine
> verwendet.**

Das steht so in `CLAUDE.md`, Abschnitt 9, und ist die wichtigste Regel dieses
Dokuments. Sie richtet sich nicht gegen neue Ideen, sondern gegen den
Normalfall, in dem eine neue Seite „nur schnell" eine eigene Kachel bekommt —
und drei Pakete später gibt es vier Kacheln, die sich in Abstand, Radius und
Schriftgröße unterscheiden.

**Was ohne Freigabe geht:** einen vorhandenen Baustein verwenden, ihn um eine
Option erweitern, die seiner Bauart entspricht (ein weiterer Ton, ein weiteres
Symbol), oder eine Regel korrigieren, die nachweislich falsch ist.

**Was eine Freigabe braucht:** ein neues Element mit eigener Klasse, eine neue
Farbe, eine neue Schriftgröße, eine neue Schwelle, ein Symbol aus einer
anderen Bibliothek, eine Seite, die sich in keinen der Typen aus Kapitel 10
einfügt.

### 1.3 Was bei jeder Gestaltungsänderung mitläuft

1. **Dieses Dokument** — im selben Arbeitspaket, nicht später.
2. **`tools/vollstaendigkeit/pruefen.py`** — keine Hexwerte außerhalb `:root`,
   keine Schriftgröße außerhalb der Skala, keine Klasse ohne Regel.
3. **`tools/screenshots/aufnehmen.mjs`** — die berührten Seiten in allen acht
   Breiten, mit gemessenem Überlauf und Knopfhöhen.
4. **`python3 tools/screenshots/kontrast.py`** — wenn eine Farbe berührt wurde.
5. Ab P4 zusätzlich **`tools/stilvergleich/`** (Kapitel 11).

---

## 2. Marke

### 2.1 Die Grundtöne

Sie sind gesetzt, nicht abgeleitet — sie kommen aus der Marke Gen-EM und
werden hier nur festgehalten.

| Name | HEX | Pantone | wofür |
|---|---|---|---|
| Schnee | `#FFFCFA` | — | Karten, Kästen, Eingabefelder |
| Rauch | `#F7F5ED` | — | die Seite unter den Karten |
| Sand | `#D4C7AD` | — | Mechanik: Blattgriff, ausgeschalteter Schalter |
| Asphalt | `#1A0500` | — | Fließtext |
| Dunkelblau | `#1A2E4D` | — | Kopfleiste, Überschriften, Logo-Grundton |
| Orange | `#FF8F1F` | 1495 C | Handeln |
| Blau | `#4280E5` | 2727 C | Auswählen und Erklären |
| Rot | `#D63338` | 1797 C | Aufmerksamkeit |

**Weiß ist keine Fläche.** In der Marke ist Weiß nur Logo- und Schriftfarbe
auf Dunkel. Im alten Stylesheet stand `#FFF` vierzehnmal als Fläche — das ist
der Grund, warum die Oberfläche stellenweise kalt wirkte, während der Rest
warm war. Als Token gibt es nur `--auf-dunkel` für Schrift auf Dunkelblau.

**Es gibt genau eine Graustufe**, `--gedaempft` `#6E6459`. Der Bestand hatte
daneben eine zweite, kühle Familie (`5B5F66`, `9AA0A6`, `8A96A8`, `C6CEDB` …)
— zwei Grauwelten in einer Oberfläche. Sie ist ersatzlos fort.

### 2.2 Schriften

Zwei quelloffene Schriften, beide **selbst ausgeliefert** aus
`server/assets/fonts/`. Kein Google Fonts, kein CDN — die Begründung steht in
`docs/Lizenzen.md`, Abschnitt 2.

| Schrift | Schnitte | Einsatz |
|---|---|---|
| **Bricolage Grotesque** | 500, 600 | Überschriften, Kopfleiste, Knöpfe, Kartentitel, Kennzahlen |
| **Open Sans** | 400, 600, 700 | Fließtext, Formularfelder, Tabelleninhalt |

Je Schnitt liegen die Subsets `latin` und `latin-ext` als `.woff2` vor.
**Ein Schnitt, der nicht in `@font-face` eingetragen ist, existiert nicht** —
die Datei allein genügt nicht.

**Die Ersatzliste bleibt normal breit.** Bricolage Grotesque ist eine normal
breite Grotesk; eine schmale Ersatzschrift (`Arial Narrow`) lässt bei
ausgefallenem Download die ganze Oberfläche gedrungen wirken und sieht nach
Gestaltungsfehler aus.

Drei Familien-Token: `--schrift-kopf` (Bricolage), `--schrift-text`
(Open Sans), `--schrift-fest` (Festbreite — nur dort, wo die
Schreibmaschinenschrift die Aussage *ist*: Kopplungscode,
Wiederherstellungsschlüssel, Geräte-ID).

Das gilt seit Web 13.1.0 **in beide Richtungen**: Der Kopplungscode wird nicht
mehr angezeigt, sondern abgetippt — das Gerät zeigt ihn, ein Mensch überträgt
ihn ins Web. Er steht deshalb nicht mehr in einem `.codeblock`, sondern in
einem Eingabefeld mit der Klasse `.feld-fest` (`style.css`, sie setzt allein
`--schrift-fest` auf `.feld-eingabe`). Die Begründung bleibt dieselbe und wiegt
im Feld eher schwerer: Wer sechs Zeichen von einem Uhrendisplay abliest und
tippt, muss jedes einzeln sehen — der Codeblock selbst bleibt für die übrigen
sechs Verwendungen (Wiederherstellungsschlüssel, Geräte-ID, API-Schlüssel,
Cron-Zeile, Serverschlüssel, Setz-Link).

### 2.3 Logo und Logo-Wahl

Es gibt **zwei** Bildmarken, und welche erscheint, ist einstellbar
(E-P3-19/20):

| Datei | Fassung | Rahmen |
|---|---|---|
| `server/assets/images/gen-em_logo_helicopter.svg` | Hubschrauber, farbig — heller Grund | 400,16 × 249,81 |
| `server/assets/images/gen-em_logo_helicopter_weiss.svg` | Hubschrauber, weiß — Kopfleiste | 400,16 × 249,81 |
| `server/assets/images/gen-em_logo_nef.svg` | Fahrzeug (NEF), farbig | 420 × 335 |
| `server/assets/images/gen-em_logo_nef_weiss.svg` | Fahrzeug (NEF), weiß | 420 × 335 |
| `server/assets/images/favicon_helicopter.png`, `favicon_nef.png` | Browser-Symbol je Wahl, 64 × 64 | — |

**Der Rahmen ist deckungsgleich mit der Zeichnung**, und das ist eine Zusage
(seit Web 12.4.2). Das NEF-Logo war bis dahin auf ein Quadrat gepolstert
(`viewBox="0 0 420 420"`, die Zeichnung 420 × 335 ab y = 42,5): oben und
unten je ein Zehntel leer. Skaliert wird aber über die **Höhe** — ein
Zehntel dieser Höhe war damit Luft, und das Bodenlogo erschien neben dem
Luftlogo schmaler **und** niedriger zugleich. Gemessen bei 34 px Höhe:
1 853 gegen 921 px² sichtbare Fläche, also das Doppelte.

Nach dem Beschnitt: **54,5 × 34 px gegen 42,6 × 34 px**, Flächenverhältnis
**1,28**. Die verbleibende Differenz ist der ehrliche Unterschied zweier
Motive — das eine liegt quer, das andere weniger — und **keine
Feinkorrektur wert** (E-S3-12 b, am Bild entschieden). Die Höhen sind gleich,
und das ist es, was das Auge in einer Zeile vergleicht.

> **Wer eine dieser SVG anfasst, prüft danach den Rahmen mit `getBBox()`.**
> Beim Luftlogo läuft ein blauer Streifen rund 156 Einheiten über den Rahmen
> hinaus; sichtbar ist er nicht — ein Clip schneidet ihn weg —, aber er wird
> es, sobald jemand den Rahmen weitet. Seit Web 12.4.2 hält ein zusätzlicher
> Rahmen-Clip ihn unabhängig davon drinnen.
>
> **Und XML verbietet `--` im Kommentar.** Eine SVG mit einem doppelten
> Bindestrich im Kommentar ist ungültig; der Browser zeigt sein
> Platzhalterbild, und `tools/logos/erzeugen.mjs` fotografierte es früher
> klaglos als Favicon. Das Werkzeug bricht heute ab (S3/AP11).

**Drei Ebenen der Wahl**, und sie greifen in dieser Reihenfolge:

1. **Das Konto** wählt im Profil: Hubschrauber, Fahrzeug, wechselnd oder
   „Standard der Installation".
2. **Die Installation** setzt ihren Standard unter Einstellungen → Wartung.
   Er gilt für die Anmeldeseite, für die Passwortseiten und für jedes Konto
   ohne eigene Wahl.
3. **Der Rückfall** ist der Hubschrauber.

„Wechselnd" wird **einmal bei der Anmeldung** ausgewürfelt und bleibt in der
Sitzung stehen. Ein Logo, das bei jedem Seitenaufruf wechselt, ist kein Logo,
sondern ein Flackern.

Eingebunden wird über `logo_src()` und `favicon_tags()` in `server/db.php`
bzw. `ui_logo()` in `server/ui.php`, nie über einen fest verdrahteten Pfad.

**Einsatzregeln**

- Auf Dunkelblau und auf den drei Kernfarben: **weiße Fassung**.
- Auf hellem, ruhigem Grund: **farbige Fassung**.
- Auf unruhigem oder dunklem Bild: Logo **neben** das Bild, nicht darauf.
- Sehr klein: einfarbige Fassung.
- **Weißraum:** rundum mindestens die Breite eines kleinen „e" der Wortmarke;
  in der Kopfleiste ist das über `.kopf-marke{gap:…}` und das Innenmaß der
  Leiste abgebildet.

Wird ein Logo ausgetauscht, ziehen **alle** Fassungen mit — auch die
Favicons und die Uhr-Icons (`watch/resources*/drawables/`).

### 2.4 Der Platzhalter

Das Fahrzeug-Logo ist zurzeit ein **Platzhalter**. Er steht dort, damit die
Logo-Wahl vollständig gebaut und geprüft werden kann, bevor die echte Datei
vorliegt; sie ersetzt ihn 1:1 — gleicher Name, gleiche Maße, kein Eingriff im
Code. Die Wartungsseite meldet den Zustand, solange er besteht
(`logo_platzhalter_liegt()`), und der Hinweis verschwindet von selbst.

### 2.5 Das Logo trägt die Markenwerte

Der offene Punkt B1 aus `docs/Branding.md` ist **erledigt.** Dort war
festgehalten, dass die Logodateien von den Markenwerten abwichen
(rotes Rotorblatt `#E3322B` statt `#D63338`, blaues `#587ABC` statt `#4280E5`,
oranges `#F7941D` statt `#FF8F1F`, Rumpf `#1D0E0A` statt `#1A0500`).

Nachgemessen in `gen-em_logo_helicopter.svg`: `#1A0500`, `#4280E5`, `#D63338`,
`#FF8F1F` — die Marke stimmt jetzt in beide Richtungen.

---

## 3. Farbrollen und abgeleitete Töne

### 3.1 Die drei Kernfarben tragen je eine Aussage

Das ist die wichtigste Farbregel der Anwendung, und sie ist nicht dekorativ:

| Farbe | sagt | wo |
|---|---|---|
| **Orange** | *Hier wird gehandelt* | Primärknopf, aktiver Menüpunkt, „+ Anlegen"-Wege, Hervorhebung, gewählte Zeile |
| **Blau** | *Hier wird ausgewählt oder erklärt* | Textlinks, Fokusring, Hinweismeldung, Plakette einer Auswahl |
| **Rot** | *Achtung* | Löschen, Fehler, „nie gesichert", „kein Ende erfasst" |

**Ein Höchstwert ist kein Fehler.** Die Hervorhebung der Extremwerte in der
Zeitraumübersicht war rot und ist orange geworden — Rot heißt in dieser
Oberfläche „Aufmerksamkeit", und ein Maximum verlangt keine.

**Farbe ist nie der einzige Träger einer Aussage.** Jeder Zustand hat
zusätzlich Text, Symbol oder Position. Eine Plakette trägt kein Häkchen: Ihr
Vorhandensein *ist* das Häkchen (E-P3-17).

### 3.2 Warum es je drei Töne gibt

Jede Kernfarbe kommt dreifach vor, und die drei sind nicht austauschbar:

| Endung | Rolle | Beispiel |
|---|---|---|
| — (`--orange`) | **Fläche und Strich.** Nie Schrift. | Primärknopf, aktiver Rand |
| `-tief` | **Schrift.** Dunkel genug für 4,5:1 auf Schnee. | Textlink, Fehlertext |
| `-hell` | **Fläche unter Schrift.** | Meldung, Plakette |

Das ist der Fund F-P3-J: Orange erreicht auf Schnee 2,2:1 und ist als Schrift
unbenutzbar; die Marke gibt aber keinen dunkleren Ton her. Statt den
Markenwert zu ändern, bekommt jede Farbe eine dunkle Textfassung
(`--orange-tief` `#C25A00`, `--blau-tief` `#1F4E9C`, `--rot-tief` `#9E2226`).

### 3.3 Zwei Linien, und der Unterschied ist keine Geschmacksfrage

| Token | Kontrast | wofür |
|---|---|---|
| `--linie` `#E3DAC6` | 1,36:1 | Trennt, schmückt, umrandet Karten — **rein zeichnerisch**. WCAG 1.4.11 nimmt dekorative Trenner ausdrücklich aus. |
| `--linie-stark` = `--gedaempft` | 5,66:1 | Begrenzt **Bedienelemente**: Eingabefeld, neutraler Knopf, Segmentwahl, Kästchen. Dort ist der Rand die einzige Auskunft darüber, wo das Element anfängt — und dafür verlangt WCAG 3:1. |

Das ist der Fund F-P3-K: Anlage G des Konzepts führt für Ränder 3:1, nennt
aber nur Farben, die darunter liegen. Ohne diese Trennung hätte jedes
Eingabefeld einen Rand von 1,4:1 — sichtbar für gute Augen, unsichtbar für
andere. Ein neuer Farbwert war dafür nicht nötig: Gedämpft ist ohnehin Token.

### 3.4 Kontraste

Gerechnet von `python3 tools/screenshots/kontrast.py` aus dem Stylesheet.
Zielwert ist WCAG AA: **4,5:1** für Fließtext, **3:1** für großen Text,
Ränder und Bedienelemente. Geprüft wird gegen die **tatsächliche Fläche** —
Schnee oder Rauch, nicht Weiß.

| Paar | Ist | Soll | Rolle |
|---|--:|--:|---|
| Asphalt auf Schnee | 19,29:1 | 4,5 | Fließtext |
| Asphalt auf Rauch | 18,05:1 | 4,5 | Fließtext |
| Dunkelblau auf Schnee | 13,33:1 | 4,5 | Titel, Symbole |
| Dunkelblau auf Rauch | 12,48:1 | 4,5 | Titel, Symbole |
| Gedämpft auf Schnee | 5,66:1 | 4,5 | Kleinzeile |
| Gedämpft auf Rauch | 5,30:1 | 4,5 | Kleinzeile |
| Blau tief auf Schnee | 7,82:1 | 4,5 | Textlink |
| Blau tief auf Blau hell | 6,61:1 | 4,5 | Hinweis, Vollzug |
| Rot tief auf Schnee | 7,58:1 | 4,5 | Fehlertext |
| Rot tief auf Rosa | 6,27:1 | 4,5 | Fehlermeldung |
| Asphalt auf Orange hell | 16,99:1 | 4,5 | Warnung, Plakette |
| Dunkelblau auf Blau hell | 11,26:1 | 4,5 | Plakette |
| **Dunkelblau auf Orange** | **5,97:1** | 4,5 | **Primärknopf** |
| Weiß auf Dunkelblau | 13,62:1 | 4,5 | Kopfleiste |
| Orange tief auf Schnee | 4,32:1 | 3,0 | nur groß oder fett |
| Orange tief auf Rauch | 4,04:1 | 3,0 | nur groß oder fett |
| Orange tief auf Orange hell | 3,81:1 | 3,0 | Warnung, Auftakt fett |
| Rot auf Schnee | 4,68:1 | 3,0 | Gefahrknopf: Rand und Schrift ab 18 px |
| Blau als Fokusring | 3,77:1 | 3,0 | Rand |
| Linie stark auf Schnee | 5,66:1 | 3,0 | Rand von Bedienelementen |
| Linie stark auf Rauch | 5,30:1 | 3,0 | Rand von Bedienelementen |

**21 Paare, 0 verfehlt.**

**Drei Ausnahmen, jede mit Grund** — sie stehen im Werkzeug selbst, damit
niemand sie aus Versehen weiterreicht:

- **Orange als Fläche auf Schnee (2,23:1).** Orange trägt nirgends allein:
  Der Primärknopf hat dunkelblaue Schrift darauf (5,97:1), der aktive
  Menüpunkt zusätzlich Fläche und Fettung.
- **Linie auf Schnee (1,36:1).** Zierrat, kein Bedienelement — siehe 3.3.
- **Sand auf Schnee (1,64:1).** Der Winkel des Akkordeons ist Mechanik, keine
  Botschaft; die aufklappbare Zeile daneben ist in Dunkelblau beschriftet.
  Sand als *Fläche* (Blattgriff, ausgeschalteter Schalter) fällt ohnehin nicht
  darunter, und Sand auf Dunkelblau erreicht 8,15:1.

  Die Ausnahme ist in O10 **kleiner** geworden: Die Versionsnummer der
  Fußzeile trug Sand ebenfalls, und dort stimmte die Begründung nicht — sie
  ist die Auskunft, mit der ein Fehlerbericht anfängt, also ein zu *lesender*
  Text. Sie steht jetzt in `--gedaempft` (5,30:1). **Wer diese Ausnahme
  künftig weiterreicht, prüfe zuerst, ob der Text gelesen werden soll.**

---

## 4. Token

**Diese Tabelle ist erzeugt, nicht abgeschrieben.**
`python3 tools/design/tabellen.py token` liest sie aus `:root` in
`server/assets/style.css`. Der Grund ist derselbe wie bei jeder
abgeschriebenen Zahl: Sie stimmt am Tag des Abschreibens und danach nie
wieder — und eine Gestaltungsrichtlinie, deren Farbwerte von denen der
Anwendung abweichen, ist schlimmer als keine, weil man ihr glaubt.

Die Gliederung stammt ebenfalls aus dem Stylesheet: Es ordnet seinen
`:root`-Block mit Kommentarzeilen der Form `---- Flächen ----`, und das
Werkzeug übernimmt sie, statt eine zweite Gliederung danebenzustellen, die
auseinanderlaufen kann.

<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->

95 Token in 15 Gruppen, alle aus `:root` in `server/assets/style.css`. Die Spalte **benutzt** zählt die `var()`-Verweise im übrigen Stylesheet.

**Flächen**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--schnee` | `#FFFCFA` | 34 |  |
| `--rauch` | `#F7F5ED` | 26 |  |
| `--sand` | `#D4C7AD` | 11 |  |

**Schrift**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--asphalt` | `#1A0500` | 20 |  |
| `--dunkelblau` | `#1A2E4D` | 51 |  |
| `--gedaempft` | `#6E6459` | 49 |  |
| `--auf-dunkel` | `#FFFFFF` | 7 | Schrift auf Dunkelblau, 13,62:1 |

**Linien**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--linie` | `#E3DAC6` | 29 |  |
| `--linie-stark` | `var(--gedaempft)` | 11 |  |

**Orange — Handeln**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--orange` | `#FF8F1F` | 28 |  |
| `--orange-tief` | `#C25A00` | 13 |  |
| `--orange-hell` | `#FFEBD6` | 18 |  |

**Blau — Auswählen und Erklären**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--blau` | `#4280E5` | 11 |  |
| `--blau-tief` | `#1F4E9C` | 15 |  |
| `--blau-hell` | `#D9ECFD` | 4 |  |

**Rot — Aufmerksamkeit**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--rot` | `#D63338` | 13 |  |
| `--rot-tief` | `#9E2226` | 15 |  |
| `--rosa` | `#FCE2D6` | 6 |  |

**Primärknopf**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--knopf-primaer-flaeche` | `var(--orange)` | 2 |  |
| `--knopf-primaer-schrift` | `var(--dunkelblau)` | 3 |  |

**Schriftskala**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--groesse-1` | `12px` | 6 |  |
| `--groesse-2` | `13px` | 38 |  |
| `--groesse-3` | `15px` | 11 |  |
| `--groesse-4` | `16px` | 10 |  |
| `--groesse-5` | `19px` | 6 |  |
| `--groesse-6` | `24px` | 3 |  |
| `--groesse-titel` | `28px` | 1 |  |
| `--zeile-eng` | `1.3` | 2 | Titel, Kacheln |
| `--zeile` | `1.55` | 2 | Oberfläche |
| `--zeile-lesen` | `1.6` | 2 | Fließtext in der Lesespalte |

**Abstände**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--abstand-1` | `4px` | 55 |  |
| `--abstand-2` | `8px` | 85 |  |
| `--abstand-3` | `12px` | 110 |  |
| `--abstand-4` | `16px` | 53 |  |
| `--abstand-5` | `24px` | 21 |  |

**Radien**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--radius-klein` | `6px` | 19 | Plakette, Kästchen, Eingabefeld |
| `--radius` | `10px` | 17 | Knopf, Meldung |
| `--radius-gross` | `12px` | 6 | Karte, Blatt, Dialog |

**Maße**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--kopf` | `56px` | 7 |  |
| `--knopf` | `44px` | 37 |  |
| `--leiste` | `260px` | 1 | Seitenleiste ab 1200 |
| `--leiste-schmal` | `220px` | 1 | Seitenleiste 1024–1199 |
| `--leiste-filter` | `280px` | 1 | Filterleiste der Suche ab 1200 |
| `--leiste-filter-schmal` | `240px` | 1 | Filterleiste 1024–1199 |
| `--rahmen` | `1680px` | 3 | Leiste und Inhalt als Einheit |
| `--lesespalte` | `760px` | 3 | Fließtext |
| `--schublade` | `320px` | 1 | Höchstbreite der mobilen Schublade |
| `--blatt-zeile` | `50px` | 1 | Zeilenhöhe im Aktionsblatt |
| `--unterpunkt` | `28px` | 2 | Sprungmarke unter dem Menüpunkt |
| `--listensuche-breit` | `36rem` | 1 | Höchstbreite des Suchfelds einer Liste |
| `--uebersicht-spalte` | `240px` | 1 | schmalste Spalte der Einstellungs-Übersicht |
| `--suchfeld` | `48px` | 2 | das große Suchfeld |
| `--symbol-klein` | `16px` | 2 | Zusatzzeichen an einer Beschriftung |
| `--symbol` | `20px` | 10 | Symbolgröße in der Zeile |
| `--symbol-gross` | `24px` | 9 | Symbolgröße im Knopf und Kartenkopf |
| `--strich` | `1px` | 38 | Haarlinie |
| `--strich-stark` | `2px` | 28 | Aktivstrich, Randstrich, Fokus |
| `--radius-rund` | `999px` | 16 | Zähler, Griff, Punkt — voll rund |
| `--schalter-breit` | `46px` | 2 | der Schalter aus E-P3-28 … |
| `--schalter-hoch` | `26px` | 4 | … 26 hoch, damit er in eine |
| `--schalter-punkt` | `20px` | 4 | 44-px-Zeile passt und greifbar bleibt |
| `--geo-kreis` | `32px` | 2 | Einsatzort-Kreis auf der Karte |
| `--geo-schild` | `36px` | 2 | Kästchen für Standort und Zielklinik |
| `--geo-ring` | `3px` | 10 | Ringstärke Start/Ende am Schild |
| `--balken` | `8px` | 1 | Höhe des Speicherbalkens |
| `--logo-kachel` | `var(--kopf)` | 2 | Vorschau-Kachel der Installation … |
| `--balken-punkt` | `10px` | 2 | Farbpunkt in seiner Legende |
| `--anmeldekarte` | `400px` | 1 | Karte der Anmeldung (E-P3-38) |
| `--zeile-frei` | `1.4em` | 1 | Mindesthöhe der Zustandszeile |
| `--balken-glied` | `28px` | 1 | ein Segment des Passwortstärke- … |
| `--strich-balken` | `6px` | 1 | … balkens, vier davon (E-P3-16) |
| `--karte-neben-breit` | `400px` | 4 | Kartenspalte ab 1600 px (E-P3-31) |

**Karte (Leaflet)**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--karte-mobil` | `160px` | 1 |  |
| `--karte-tablet` | `220px` | 1 |  |
| `--karte-desktop` | `300px` | 4 |  |

**Spurfarben (P3/O3, E-P3-40)**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--spur-1` | `var(--orange)` | 0 |  |
| `--spur-2` | `var(--blau)` | 0 |  |
| `--spur-3` | `var(--rot)` | 0 |  |
| `--spur-4` | `var(--dunkelblau)` | 0 |  |
| `--spur-5` | `var(--orange-tief)` | 0 |  |
| `--spur-6` | `var(--blau-tief)` | 0 |  |
| `--spur-7` | `var(--rot-tief)` | 0 |  |
| `--spur-8` | `#867146` | 0 |  |
| `--spur-ruhe` | `var(--gedaempft)` | 0 |  |

**Schwellen**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--s-handy` | `720px` | 0 |  |
| `--s-leiste` | `1024px` | 0 |  |
| `--s-zwei` | `1200px` | 0 |  |
| `--s-karte-neben` | `1600px` | 0 |  |

**Bewegung**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--dauer` | `.18s` | 7 |  |
| `--schleier` | `rgba(26,46,77,.55)` | 2 | Dunkelblau, halbdurchsichtig |
| `--schatten` | `0 2px 8px rgba(26,5,0,.10)` | 7 |  |
| `--schatten-hoch` | `0 8px 28px rgba(26,5,0,.22)` | 3 |  |
| `--auf-dunkel-leise` | `rgba(255,255,255,.55)` | 1 |  |
| `--auf-dunkel-flaeche` | `rgba(255,255,255,.14)` | 2 |  |
| `--auf-dunkel-strich` | `rgba(255,255,255,.35)` | 1 |  |

**Ungenutzt:** `--spur-1`, `--spur-2`, `--spur-3`, `--spur-4`, `--spur-5`, `--spur-6`, `--spur-7`, `--spur-8`, `--spur-ruhe`, `--s-handy`, `--s-leiste`, `--s-zwei`, `--s-karte-neben`.

## 5. Schriftskala

Sieben Stufen, in Pixeln, ohne Zwischenwerte. Sie ist **geschlossen**: Eine
Größe, die nicht in dieser Tabelle steht, gibt es nicht — die
Vollständigkeitsprüfung meldet jede.

| Token | Wert | wofür |
|---|---|---|
| `--groesse-1` | 12 px | Plakette, Kleinstzeile, Zähler |
| `--groesse-2` | 13 px | Kleinzeile, Hinweis, Tabelleninhalt |
| `--groesse-3` | 15 px | Fließtext der Oberfläche, Leistenüberschrift |
| `--groesse-4` | 16 px | Kartentitel, Knopf |
| `--groesse-5` | 19 px | Abschnittsüberschrift |
| `--groesse-6` | 24 px | Seitentitel |
| `--groesse-titel` | 28 px | Titel der Lesespalte |

Das ist die Antwort auf den offenen Punkt **B2** aus `docs/Branding.md`
(„keine geschlossene Größenskala; die Anwendung folgt einer Major-Third-Skala
nur lose, Stufen historisch gewachsen"). Erhoben waren dort **71**
Schriftgrößen außerhalb jeder Skala; heute sind es **0**.

**Zeilenabstand:** je größer der Text, desto enger die Zeile.

| Token | Wert | wofür |
|---|---|---|
| `--zeile-eng` | 1,3 | Titel, Kacheln |
| `--zeile` | 1,55 | Oberfläche |
| `--zeile-lesen` | 1,6 | Fließtext in der Lesespalte |

---

## 6. Grundregeln

Was für **jede** Seite gilt, unabhängig vom Baustein. Stylesheet, Abschnitt 3.

**Zwei Höhen für Bedienelemente — und eine Bedingung, die beide trennt**
(`--knopf`, E-S8-09, R76):

| | Höhe | wann |
|---|--:|---|
| Finger | **44 px** | überall, wo getippt wird — und überall unter 1024 px |
| Zeiger | **36 px** | `(hover: hover) and (pointer: fine) and (min-width: 1024px)` |

44 px ist die Vorgabe und kommt aus WCAG 2.5.8 und den Plattformvorgaben; sie
ist für die Fingerkuppe gerechnet. Ein Zeiger trifft ein 36 px hohes Ziel
genauso sicher — was er nicht hat, ist die Ungenauigkeit, für die die acht
Pixel da sind. Bezahlt werden sie in Formularhöhe: Das Einsatzformular hat
über dreissig Felder, acht Pixel je Zeile sind dort eine Bildschirmhöhe.

**Alle drei Bedingungen müssen gelten.** Die Breite allein genügt nicht — ein
Touch-Laptop mit 1920 px ist ein Fingergerät, ein iPad im Querformat meldet
1024 px. Die Medienmerkmale allein genügen auch nicht: Ein Zeiger an einem
schmalen Fenster bekommt die grosse Höhe, weil die Zeilen dort ohnehin knapp
sind.

**Was seine eigenen Token hat, ändert sich nicht:** Kopfleiste (`--kopf`, 56),
Schalter (`--schalter-*`), Zeile des Aktionsblatts (`--blatt-zeile`, 50 — gilt
nur unter 1024 px), grosses Suchfeld (`--suchfeld`, 48), Sprungmarke unter dem
Menüpunkt (`--unterpunkt`, 28).

Es gibt weiterhin keine Kompaktvariante innerhalb einer Eingabeart — was
kleiner ist, ist kein Knopf, sondern ein Link mit Symbol (E-P3-22). Der
Bilderlauf misst jedes `.knopf` in allen acht Breiten **gegen den Sollwert der
emulierten Eingabeart**; Abweichung ist ein Fehler, kein Geschmack.

**Der Fokusring ist sichtbar und liegt an der richtigen Stelle.** Zwei
Pixel Blau mit Abstand. Wo ein Bedienelement aus einem *ausgeblendeten*
Eingabefeld und einer sichtbaren Beschriftung gebaut ist (Schalter, Segment,
Wahlliste), gehört der Ring an die **Beschriftung** — am unsichtbaren Feld
wäre die Tastaturbedienung unsichtbar.

**Nichts läuft waagerecht aus dem Bild.** Auf keiner Seite und in keiner
Breite darf `scrollWidth > innerWidth` gelten. Was breit ist, scrollt in
seinem eigenen Behälter (`.tabelle-scroll`) oder wird zur Kachel.

**Bewegung ist kurz und einheitlich** (`--dauer` .18 s) — und wer sie
abbestellt hat (`prefers-reduced-motion`), bekommt keine.

**Symbole kommen aus dem Vorrat.** Kein Inline-Pfad im Code, kein
Unicode-Zeichen, kein Emoji (Kapitel 8).

**Farben nur über Token.** Kein Hexwert, kein `rgb()` mit festen Zahlen
außerhalb von `:root`. Erhoben vor P3: **78** Hexwerte außerhalb der Token;
heute **0**. Das ist der erledigte Backlog-Punkt Nr. 20.

**Spaltenbreiten nie über `:nth-child`.** Sie zählen Spalten ab und rutschen
beim Streichen einer Spalte still auf die falsche. Wo eine Spalte eine Breite
braucht, bekommt sie eine Klasse.

**Die Grundformen** (Stylesheet, Abschnitt 17) tragen, worauf die Bausteine
aufsetzen: `input`/`select`/`textarea`, Kästchen und Radios, das Muster
`<label>Text <input></label>`, `summary`, `code`/`kbd`/`pre`. Dort stehen
**ausschließlich Elementnamen** — eine Klasse dort einzutragen hieße, das
Redesign zurückzunehmen.

> **Eine Falle, die dreimal zugeschnappt ist** (F-P3-AP, F-P3-AZ): Die Regel
> `input[type=checkbox]` hat Spezifität (0,1,1) und schlägt damit **jede
> bloße Klasse**. Wer ein solches Kästchen über eine Klasse ausblenden will,
> braucht `input[type=checkbox].meine-klasse` — `.meine-klasse` allein
> verliert, und das Kästchen bleibt 20 × 20 px groß und fängt Klicks ab.

### Der vertikale Rhythmus

**Die Skala allein genügt nicht.** `--abstand-1` bis `--abstand-5` stehen
seit P3 und werden eingehalten — nachgemessen in S3/AP1: 269
Abstandsdeklarationen, davon **null** mit einem Rohwert. Trotzdem passten
die Abstände sichtbar nicht zusammen, und der Grund ist nicht die Skala,
sondern die fehlende Stufe darüber: eine Regel, die sagt, **welche Stufe
wo** gilt. Ohne sie wird die Wahl an jeder Stelle einzeln getroffen — und
fällt vierundsiebzigmal auf fünf verschiedene Werte, ohne dass ein Muster
dahintersteht (S3, Rückmeldungsliste vom 31.08.2026, Block A).

**Der Leitgedanke ist einer: Bindung ist kleiner als Trennung.** Was
zusammengehört, steht enger als das, was sich voneinander absetzt. Wo der
Abstand zwischen zwei Karten derselbe ist wie der zwischen zwei Feldern
*innerhalb* einer Karte, sagt die Fläche nichts mehr darüber, was wozu
gehört — genau das war der Befund.

| Beziehung | Stufe | Begründung |
|---|---|---|
| Beschriftung → ihr Feld | `--abstand-1` (4 px) | klebt am Feld; alles Größere ließe die Beschriftung zwischen zwei Feldern schweben |
| Überschrift → ihr Inhalt | `--abstand-2` (8 px) | bindet; die Überschrift gehört zum Inhalt darunter, nicht in die Mitte zwischen zwei Blöcke |
| Element → Element derselben Gruppe (Feld → Feld, Zeile → Zeile) | `--abstand-3` (12 px) | der Arbeitsabstand; zugleich die häufigste Wahl im Bestand |
| Gruppe → nächste Gruppe innerhalb einer Karte; Formular → Formularfuß | `--abstand-4` (16 px) | setzt ab, ohne zu trennen; deckungsgleich mit dem bestehenden `.listen-form-fuss` |
| Karte → Karte; Inhalt → nächste Abschnittsüberschrift | `--abstand-5` (24 px) | trennt; der Wechsel zwischen Sinneinheiten muss größer sein als jeder Abstand innerhalb |

**Zwei Präzisierungen, beide aus echten Fällen** (S3/AP1, F-S3-02 und
F-S3-03):

- **Zeile 3 gilt für Bausteine, nicht für Zeilen in einem Textblock.** Ein
  `<li>` im Fließtext ist eine Zeile, kein Element: Es gehört zum selben
  zusammenhängenden Text wie die Zeile darüber. Aufzählungen stehen deshalb
  enger (`--abstand-1`) — bekämen sie den Arbeitsabstand, stünden ihre
  Punkte so weit auseinander wie zwei Absätze, und genau die Bindung, die
  eine Liste zur Liste macht, wäre weg.
- **Trägt eine Überschrift Bedienelemente, gilt Zeile 4 statt Zeile 2.** Die
  Titelzeile (9.8) ist der Fall: Neben dem Titel stehen dort Knöpfe von
  44 px Höhe. Acht Pixel darunter stünde ein Knopf fast auf der ersten
  Karte — die Beziehung ist dann nicht „Überschrift → ihr Inhalt", sondern
  „Gruppe → nächste Gruppe" (`--abstand-4`). Das ist keine sechste Stufe,
  sondern dieselbe Zeile 4 auf einen Fall angewandt, den die Tabelle nicht
  benannt hatte.

**Woran erkenne ich die Beziehung?** Die Frage ist immer dieselbe: *Was ist
das Nächste, das folgt — gehört es noch zu mir, oder ist es das Nächste?*
Gehört es noch dazu, steht es enger; ist es das Nächste, steht es weiter.
Zwei Zeilen weiter unten dieselbe Frage erneut zu stellen, kostet nichts und
ist der ganze Trick.

**Wofür die Regel gilt und wofür nicht.** Sie regelt den **Zwischenraum**:
senkrechte `margin` und das `row-gap` einer Spalte oder eines Rasters. Sie
regelt **nicht die Polsterung** (`padding`) — die gehört zur Form des
Bausteins, nicht zum Verhältnis zweier Dinge zueinander. Wer `padding` nach
dieser Tabelle wählt, beantwortet die falsche Frage.

**Keine neuen Token.** Die fünf Stufen decken die fünf Beziehungen. Findet
sich eine Beziehung, die in keiner Zeile aufgeht, ist das eine Frage an das
laufende Konzept — keine stille sechste Stufe (E-S3-02).

**Das Anti-Muster dazu**: ein Abstand, der an der **Seite** hängt statt am
Baustein. Er wirkt einmal richtig und ist beim nächsten Baustein wieder weg;
die Stelle, an der die Rückmeldungsliste ihn fand, war „Profil speichern" in
`einstellungen.php` — ein nackter Knopf zwischen `ui_karte_ende()` und
`</form>`, obwohl es mit `.listen-form-fuss` längst einen Formularfuß gibt
(9.16).

---

## 7. Schwellen

**Vier Schwellen, und nur vier.** Sie stehen als Literale in Abschnitt 18 des
Stylesheets — Custom Properties funktionieren in `@media` nicht — und
zusätzlich als `--s-*` in `:root`, damit man sie nachlesen kann.

| Schwelle | was sich ändert |
|---|---|
| **720** | Handy → Tablet hoch: Einsatzkachel wird Tabelle, Zeilenaktionen werden Knopfreihe statt Blatt, Karte 220 px |
| **1024** | Schublade → feste Leiste; die Hauptpunkte wandern in die Kopfleiste; das Aktionsblatt wird ein Aufklappmenü |
| **1200** | Leiste 260 px (Filterleiste 280), Zweispalter: Einsatzansicht, Formularkarten, Kontoseite |
| **1600** | Die Karte steht neben Diensttag-Daten und Tabelle |

Dazu **eine** Ausnahme nach unten: `@media (max-width:479px)` lässt in der
Wahlliste den Zusatz unter den Text rutschen — „zurzeit Hubschrauber (RTH)"
neben „Standard der Installation" sprengt sonst jede Zeile.

<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->

| Abfrage | Regelblöcke |
|---|--:|
| `@media (min-width:1600px)` | 2 |
| `@media (min-width:1200px)` | 3 |
| `@media (hover: hover) and (pointer: fine) and (min-width:1024px)` | 1 |
| `@media (min-width:1024px)` | 2 |
| `@media (min-width:720px)` | 12 |
| `@media (max-width:479px)` | 1 |

Zusammen 21 Medienblöcke über 5 verschiedene Breiten: 479 px, 720 px, 1024 px, 1200 px, 1600 px.

### Verhalten je Baustein

| Baustein | < 720 | 720–1023 | 1024–1199 | 1200–1599 | ≥ 1600 |
|---|---|---|---|---|---|
| Kopfleiste | Menüknopf, Logo, Zahnrad | wie < 720 | Hauptpunkte sichtbar | wie 1024 | wie 1024 |
| Leiste / Schublade | Schublade | Schublade | Leiste 220 | Leiste 260 | Leiste 260 |
| Filterleiste (Suche) | Schublade + Knopf | Schublade + Knopf | 240 | 280 | 280 |
| Einsätze | Kachel | Tabelle | Tabelle | Tabelle | Tabelle |
| Zeilenaktionen | „⋯" + Blatt von unten | Knopfreihe | Knopfreihe, Blatt wird Aufklappmenü | wie 1024 | wie 1024 |
| Karte Startseite | 160 px über der Liste | 220 px oben | 220 px oben | 300 px oben | neben Daten + Tabelle, 400 px breit |
| Karte Einsatz | 160 zwischen Angaben und Phasen | 240 oben | 240 oben | rechts oben klebend | wie 1200 |
| Karte Zeitraum | 160 | 220 | 220 | 260 | 260 |
| Einsatzansicht Spalten | 1 | 1 | 1 | 2 | 2 |
| Formularkarten | 1 | 1 | 1 | 2 | 2 |
| Kontoseite | 1 | 1 | 1 | 2 | 2 |
| Kennzahlen | 2 Spalten, 4 + Aufklapper | 4/5 Spalten | 4/5 | 4/5 | 4/5 |
| Diensttag-Daten | 1 Spalte | 2 Spalten | 2 | 2 | schmal (Tabellenbreite) |
| Verwaltungslisten | Zeilen mit „⋯" | Zeilen mit Knopfreihe | wie 720 | wie 720 | wie 720 |
| Rahmen | — | — | — | — | max 1680, zentriert |
| Lesespalte | volle Breite | 760 | 760 | 760 | 760 |

Die Zeile **Verwaltungslisten** hieß bis O11 „Zeilen (CSS-Stapel) / Tabelle" —
es gibt keine Verwaltungstabelle mehr. Sechs von ihnen sind in O8, O9 und O11
zu Karten mit Zeilen geworden; geblieben sind die drei Einsatztabellen, die
unter 720 px zur Kachel werden, und die Importtabelle.

---

## 8. Symbole

Alle Zeichen der Oberfläche liegen als einzelne SVG-Dateien unter
`server/assets/images/symbole/`, je Zeichen eine Datei, 24 × 24, Strich 2 px,
runde Enden und Ecken, Farbe über `currentColor`. Grundlage ist **Tabler
Icons** (tabler.io/icons, MIT-Lizenz, Lizenztext in
`LICENSE-tabler-icons.txt` daneben). Jede Datei trägt im Kommentar den
Verwendungsort und die Quelle (Tabler-Name oder „eigener Entwurf") und ein
`<g id="i">` als Anker. Die Zuordnung Datei → Tabler-Name → Verwendung steht
in `LIESMICH.md` im selben Ordner.

**Einbindung:** in PHP `ui_symbol('haus')`, in JS `edSymbol('haus')`; beide
erzeugen dieselbe Zeichenkette. Kein Zeichen liegt als Inline-Pfad im Code,
kein Unicode-Zeichen (▸ ✓ ★ …) und kein Emoji dient als Symbol; die
Vollständigkeitsprüfung meldet Verstöße und Verweise auf fehlende Dateien.
Der Winkel liegt einmal vor (`winkel.svg`, zeigt nach unten) und wird per
Klasse gedreht; der Stern wird per Klasse gefüllt, wenn die Vorbelegung
gesetzt ist.

**Ein neues Zeichen:** (1) bei Tabler suchen, Outline-Variante; (2) Datei
unverändert übernehmen, auf einen deutschen Namen umbenennen, Kommentar mit
Verwendung und Tabler-Name ergänzen, `<g id="i">` setzen; (3) Zeile in
`LIESMICH.md`; (4) Freigabe wie für jeden neuen Baustein (Kapitel 1). Nur
wenn Tabler nichts Passendes hat, entsteht ein eigener Entwurf im selben Stil
(24er-Raster, 2 px, runde Enden), als „eigener Entwurf" gekennzeichnet.
Zeichen aus anderen Bibliotheken werden nicht gemischt.

**Lizenz:** MIT erlaubt Nutzung, Änderung und Verbreitung, auch in
kommerziellen Produkten und Diensten; einzige Pflicht ist die Mitlieferung des
Lizenztexts. Die Symbole bleiben unter MIT, der Anwendungscode unter
AGPL-3.0; siehe `docs/Lizenzen.md`.

### Der Vorrat

<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->

| Datei | Herkunft (Tabler-Name) | Nennungen im Code |
|---|---|--:|
| `abmelden.svg` | Tabler Icons „logout" (MIT) | 2 |
| `aktualisieren.svg` | Tabler Icons „refresh" (MIT) | 1 |
| `balken.svg` | Tabler Icons „chart-bar" (MIT) | 3 |
| `datenbank.svg` | Tabler Icons „database" (MIT) | 10 |
| `einsatzort.svg` | Tabler Icons „map-pin-plus" (MIT) | 1 |
| `fahrzeug.svg` | Tabler Icons „ambulance" (MIT) | 20 |
| `geraet-entkoppeln.svg` | Tabler Icons „link-off" (MIT) | 1 |
| `gruppe.svg` | Tabler Icons „users" (MIT) | 20 |
| `haken.svg` | Tabler Icons „check" (MIT) | 23 |
| `haus.svg` | Tabler Icons „home" (MIT) | 4 |
| `hinweis.svg` | Tabler Icons „info-circle" (MIT) | 19 |
| `hubschrauber.svg` | Tabler Icons „helicopter" (MIT) | 23 |
| `kalender.svg` | Tabler Icons „calendar" (MIT) | 4 |
| `karte.svg` | Tabler Icons „map-2" (MIT) | 14 |
| `klinik.svg` | Tabler Icons „building-hospital" (MIT) | 2 |
| `kolben.svg` | Tabler Icons „flask" (MIT) | 3 |
| `korb.svg` | Tabler Icons „trash" (MIT) | 21 |
| `luftlinie.svg` | — | 0 |
| `lupe.svg` | Tabler Icons „search" (MIT) | 8 |
| `menu.svg` | Tabler Icons „menu-2" (MIT) | 1 |
| `ohne-zuordnung.svg` | Tabler Icons „circle-dashed" (MIT) | 2 |
| `ordner-plus.svg` | Tabler Icons „folder-plus" (MIT) | 1 |
| `pfeil-hoch.svg` | Tabler Icons „arrow-up" (MIT) | 8 |
| `plus.svg` | Tabler Icons „plus" (MIT) | 16 |
| `position.svg` | Tabler Icons „current-location" (MIT) | 4 |
| `profil.svg` | Tabler Icons „user" (MIT) | 12 |
| `punkte.svg` | Tabler Icons „dots" (MIT) | 25 |
| `reanimation.svg` | Tabler Icons „activity" (MIT) | 0 |
| `rechtstexte.svg` | Tabler Icons „file-text" (MIT) | 1 |
| `schliessen.svg` | Tabler Icons „x" (MIT) | 9 |
| `schloss-offen.svg` | Tabler Icons „lock-open" (MIT) | 4 |
| `schloss.svg` | Tabler Icons „lock" (MIT) | 9 |
| `server.svg` | Tabler Icons „server" (MIT) | 6 |
| `sicherung.svg` | Tabler Icons „archive" (MIT) | 10 |
| `sortieren.svg` | Tabler Icons „arrows-sort" (MIT) | 4 |
| `standort.svg` | Tabler Icons „map-pin" (MIT) | 4 |
| `status.svg` | Tabler Icons „activity" (MIT) | 27 |
| `stern.svg` | Tabler Icons „star" (MIT) | 9 |
| `stift.svg` | Tabler Icons „pencil" (MIT) | 7 |
| `tausch.svg` | Tabler Icons „arrows-exchange" (MIT) | 11 |
| `uhr.svg` | Tabler Icons „device-watch" (MIT) | 277 |
| `uhrzeit.svg` | Tabler Icons „clock" (MIT) | 3 |
| `vollbild.svg` | Tabler Icons „maximize" (MIT) | 1 |
| `warnung.svg` | Tabler Icons „alert-triangle" (MIT) | 27 |
| `werkzeug.svg` | Tabler Icons „tool" (MIT) | 0 |
| `winkel.svg` | Tabler Icons „chevron-down" (MIT) | 17 |
| `zahnrad.svg` | Tabler Icons „settings" (MIT) | 1 |
| `ziel-fern.svg` | Tabler Icons „cloud-upload" (MIT) | 1 |
| `zurueck.svg` | Tabler Icons „arrow-left" (MIT) | 27 |

49 Dateien in `server/assets/images/symbole/`, dazu `LICENSE-tabler-icons.txt` und `LIESMICH.md`.
**Nirgends genannt:** `luftlinie`, `reanimation`, `werkzeug`.

## 9. Bausteine

Alle in `server/ui.php`. **Der Vorrat ist die Antwort auf die Freigaberegel:**
Wer eine Seite baut, sucht hier, statt etwas Neues zu erfinden.

### 9.0 Wenn du X willst, nimm Y

Die Tabelle ist der Einstieg. Steht dein Fall nicht darin, ist das der Moment
für eine Rückfrage — nicht für ein neues Element.

| Ich will … | nimm | nicht |
|---|---|---|
| einen Inhaltsblock mit Überschrift | `ui_karte_start()` / `ui_karte_ende()` | ein `<div>` mit eigener Klasse |
| einen Block, der zugeklappt anfängt | `ui_karte_start(['zu' => true, 'vorschau' => '…'])` | `<details>` von Hand |
| eine Liste von Einträgen | je Eintrag `ui_zeile()` in einer Karte | eine `<table>` |
| eine Liste **mit Handlungen** je Eintrag | `ui_zeile(['aktionen' => ui_zeilenaktionen([…])])` | Knöpfe direkt in die Zeile |
| Zahlen nebeneinander vergleichen | die drei Einsatztabellen (`.tabelle`) | eine neue Tabelle |
| Handlungen **der ganzen Seite** | `ui_aktionen()` neben dem Titel | eine Knopfreihe unter dem Titel |
| die eine Haupthandlung | `ui_knopf(['art' => 'primaer'])` | zwei primäre Knöpfe |
| eine Handlung, die löscht | `ui_knopf(['art' => 'gefahr'])`, im Blatt `blatt-gefahr` | roten Text |
| eine Rückfrage in **einem Satz** | `data-confirm="…"` (`assets/confirm.js`) | ein eigener Dialog |
| eine Rückfrage mit **Aufstellung** | eine eigene Seite mit Karte und Zeilen | einen Dialog mit viel Text |
| dem Nutzer etwas sagen | `ui_meldung()` / `ui_meldung_markup($ton, …)` | ein `<p>` in Rot |
| einen Zustand an einer Zeile zeigen | `ui_plakette($text, ['ton' => …])` | ein farbiges Wort |
| eine Zahl groß zeigen | `ui_kennzahl()` | eine Überschrift mit Zahl |
| ein Eingabefeld mit Beschriftung | `ui_feld()` | `<label>Text <input></label>` |
| ein Ja/Nein | `ui_schalter()` | ein Kästchen |
| eine aus **wenigen kurzen** Möglichkeiten | `ui_segment()` | ein `<select>` |
| eine aus mehreren **mit Erklärung** | `ui_wahlliste()` | Radios von Hand |
| eine Adresse mit Koordinaten | `ui_ortsfeld()` | ein Textfeld |
| einen Seitenkopf mit Rückweg | `ui_titelzeile()` | ein `<h1>` |
| „gibt es nicht" / „kein Zugriff" | `ui_abbruch($code, $text)` | `exit('… nicht gefunden.')` |
| ein Zeichen | `ui_symbol('name')` / `edSymbol('name')` | ein Emoji, ein Unicode-Zeichen, ein Inline-Pfad |
| die Art eines Diensttags zeigen | `ui_artzeichen($kind)` | ein Wort oder ein Emoji |
| einen Hinweis unter einem Feld | `<p class="feld-klein">` | `<small>` |
| einen Hinweis vor einem Feld | `<p class="feld-hinweis">` | dasselbe wie oben |
| einen Zusatz **in** einer Beschriftung | `<span class="feld-klein-inline">` | Klammern im Beschriftungstext |
| einen Erklärabsatz oben auf der Seite | `<p class="seiten-erklaerung">` — **einen**, keine zwei | zwei Absätze Vorrede |
| einen Knopf am Ende eines Formulars | `ui_knopf()` in `<div class="listen-form-fuss">` | einen blanken `<button>` |
| einen **Wert zum Abschreiben oder Kopieren** (Kennung, Schlüssel, Prüfsumme, Adresse) | `ui_codeblock_lang()` — beide Stufen und wann welche: 9.18 | ein `<code>` im Fließtext |
| eine **Füllung gegen eine Grenze** zeigen | `.speicher-balken` mit seinen drei Schwellen (9.19) | ein `<progress>` oder ein eigener Balken |
| eine **Zahl an einem Menüpunkt** („hier ist etwas zu tun") | `ui_zaehler()` — drei Töne wie die Ampel (9.25) | eine Zahl in Klammern hinter dem Text |
| mehrere Karten **nebeneinander** | einen der drei Wege aus 9.26 — und lies dort erst, welcher | ein eigenes Raster je Seite |
| **Sprungmarken** innerhalb einer langen Seite | die Unterpunkte der Einstellungsleiste; sie entstehen von selbst aus den Karten mit `id` (9.25) | ein Inhaltsverzeichnis von Hand |

<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->

| Baustein | Klasse | Regel im Stylesheet | `ui.php` |
|---|---|---|--:|
| `ui_seite_start()` | — | Hüllenfunktion, kein eigenes Element | 54 |
| `ui_seite_ende()` | — | Hüllenfunktion, kein eigenes Element | 108 |
| `ui_favicon()` | — | Hüllenfunktion, kein eigenes Element | 141 |
| `ui_symbol()` | `.symbol` | ja (+6 Unterklassen) | 194 |
| `ui_logo_masse()` | `.logo-masse` | **keine** | 297 |
| `ui_kopf()` | `.kopf` | ja (+19 Unterklassen) | 352 |
| `ui_geruest_start()` | `.inhalt` | ja | 427 |
| `ui_leiste_ende()` | `.leiste` | ja (+11 Unterklassen) | 498 |
| `ui_geruest_ende()` | `.inhalt` | ja | 522 |
| `ui_leiste_diensttage()` | `.leiste-liste` | ja | 562 |
| `ui_zaehler()` | `.zaehler` | ja (+2 Unterklassen) | 763 |
| `ui_leiste_einstellungen()` | `.leiste-liste` | ja | 886 |
| `ui_einstellungen_uebersicht()` | `.uebersicht-block` | ja (+2 Unterklassen) | 957 |
| `ui_fuss_seite()` | `.fuss-seite` | ja | 1041 |
| `ui_demo_hinweis()` | `.demo-hinweis` | ja | 1084 |
| `ui_meldung_markup()` | `.meldung` | ja (+13 Unterklassen) | 1151 |
| `ui_knopf()` | `.knopf` | ja (+16 Unterklassen) | 1197 |
| `ui_codeblock_lang()` | `.codeblock-lang` | ja | 1250 |
| `ui_plakette()` | `.plakette` | ja (+5 Unterklassen) | 1276 |
| `ui_karte_start()` | `.karte` | ja (+33 Unterklassen) | 1312 |
| `ui_karte_ende()` | `.karte` | ja (+33 Unterklassen) | 1370 |
| `ui_zeile()` | `.zeile` | ja (+12 Unterklassen) | 1386 |
| `ui_titelzeile()` | `.titelzeile` | ja (+6 Unterklassen) | 1428 |
| `ui_aktionen()` | `.aktionen` | ja (+2 Unterklassen) | 1470 |
| `ui_feld()` | `.feld` | ja (+18 Unterklassen) | 1539 |
| `ui_schalter()` | `.schalter` | ja (+16 Unterklassen) | 1604 |
| `ui_segment_markup()` | `.segment` | ja (+23 Unterklassen) | 1648 |
| `ui_wahlliste()` | `.wahlliste` | ja | 1701 |
| `ui_zeilenaktionen()` | `.zeile-aktionen` | ja | 1745 |
| `ui_speichern_leiste()` | `.speichern` | ja (+4 Unterklassen) | 1844 |
| `ui_kennzahl()` | `.kennzahl` | ja (+20 Unterklassen) | 1906 |
| `ui_abbruch()` | `.rahmen` | ja (+1 Unterklassen) | 1947 |
| `ui_ortsfeld()` | `.ortsfeld-zeile` | ja | 2004 |
| `ui_krypto_bootstrap()` | — | Hüllenfunktion, kein eigenes Element | 2157 |

34 Funktionen mit Markup in `server/ui.php`, davon 4 Hüllenfunktionen ohne eigenes Element.
**Ohne Regel im Stylesheet:** `ui_logo_masse()` — jede davon ist zu prüfen: entweder ein Behälter, der zu Recht keine Gestaltung braucht, oder eine Lücke.

### 9.1 Karte — der Inhaltsblock

**Zweck:** Jeder Inhaltsblock ist eine Karte. Titel in Bricolage, optionale
Zahl (gedämpft), genau **eine** Kopfaktion rechts.

```html
<section class="karte">
  <div class="karte-kopf">
    <h2 class="karte-titel">Titel</h2>
    <span class="karte-zahl">6</span>
    <span class="plakette plakette-rot">überfällig</span>
    <a class="karte-aktion karte-aktion-blau" href="…"><svg class="symbol">…</svg><span>Bearbeiten</span></a>
  </div>
  <div class="karte-inhalt">…</div>
</section>
```

**Zustände:** offen (`<section>`) · klappbar (`<details class="karte
karte-klappbar">` mit Winkel links und Vorschau rechts) · mit Plakette.

**Eine zweite Kopfaktion gibt es nicht** (E-P3-25). Was mehr braucht, bekommt
ein Aktionsmenü.

### 9.2 Zeile — der Listeneintrag

**Zweck:** Ein Eintrag einer Liste. Text links (fett plus Kleinzeile),
Plaketten, Aktionen rechts.

```html
<div class="zeile">
  <div class="zeile-vorn">…</div>        <!-- Auswahlkästchen, optional -->
  <div class="zeile-text">
    <span class="zeile-haupt">Alpenfalke 2</span>
    <span class="zeile-klein">Luftrettungsstation Hochkreuth · 4 Einsätze</span>
  </div>
  <div class="zeile-plaketten">…</div>
  <div class="zeile-aktionen">…</div>
</div>
```

**`vorn` ist nicht `aktionen`.** Was vorn steht, *wählt die Zeile aus*; was
rechts steht, *handelt an ihr*. Zwei Verwendungen: die NutzerInnen-Liste
(Sammel-Backup) und die Spurenliste des Diensttages (mehrere Spuren als eine
GPX-Datei, seit Web 10.3.0). Ein Eintrag, an dem es nichts auszuwählen gibt,
bekommt ein **abgeschaltetes** Kästchen und nicht gar keines — ein fehlendes
ließe die Zeile um seine Breite nach links rutschen, und die Liste sähe
verrutscht aus.

**`attr` (seit Web 10.3.0)** hängt fertige Attribute an — dieselbe
Zusatzoption, die `ui_knopf()` und `ui_aktionen()` schon haben. Gebraucht für
Zeilen, die mit etwas anderem auf der Seite verknüpft sind: die Spurenliste des
Diensttages trägt darüber `data-spur` und `tabindex`.

**`.zeile-hervor` (seit Web 10.3.0)** hebt eine Zeile hervor, solange etwas
anderes auf sie zeigt — Rauchfläche plus ein orangener Balken links in
`--strich-stark`. Kein neuer Farbwert, kein neues Maß, kein eigener Fokusring
(es gibt **einen** für die ganze Anwendung).

> **Nur für eine Verknüpfung, nicht für einen Zustand.** „Hervorgehoben" heißt
> *worauf gerade gezeigt wird*, nicht *was ausgewählt ist* und nicht *was
> wichtig ist*. Für einen Zustand ist die Plakette da (9.6).

### 9.3 Zeilenaktionen — dieselben Handlungen, zwei Formen

**Zweck:** Am Schreibtisch Knöpfe nebeneinander, unter 720 px **ein** „⋯",
das ein Blatt von unten öffnet (E-P3-26). Ein Dutzend Knöpfe untereinander
wäre auf dem Handy eine Bildschirmlänge je Zeile.

```html
<div class="zeile-knoepfe nur-ab-720">
  <button class="knopf knopf-neutral" form="f-x"><svg class="symbol">…</svg><span>Bearbeiten</span></button>
  <button class="knopf knopf-gefahr"  form="f-y"><svg class="symbol">…</svg><span>Löschen</span></button>
</div>
<div class="aktionen nur-unter-720">
  <button class="knopf knopf-symbol" data-blatt="za-1" title="Weitere Handlungen"><svg class="symbol">…</svg></button>
  <div class="blatt" id="za-1" hidden>
    <div class="blatt-griff"></div>
    <h2 class="blatt-titel">Alpenfalke 2</h2>
    <div class="blatt-liste">
      <button class="blatt-zeile"              form="f-x"><svg class="symbol">…</svg><span>Bearbeiten</span></button>
      <button class="blatt-zeile blatt-gefahr" form="f-y"><svg class="symbol">…</svg><span>Löschen</span></button>
    </div>
    <button class="knopf knopf-leise blatt-abbrechen" data-blatt-zu>Abbrechen</button>
  </div>
</div>
```

> **Zwei Vokabeln für dieselbe Sache, und sie sind nicht austauschbar**
> (F-P3-AX): Die Knopfreihe kennt `knopf-gefahr`, das Blatt `blatt-gefahr` —
> denn `.blatt-zeile` setzt seine Schriftfarbe selbst, mit gleicher
> Spezifität und später in der Datei. Wer das übersieht, bekommt ein
> „Löschen", das nicht rot ist. `ui_zeilenaktionen()` wählt danach, wo der
> Knopf steht.

> **Formulare stehen nur einmal im Markup.** Die meisten Zeilenaktionen sind
> POSTs mit Token. Sie zweimal auszugeben — einmal für den Knopf, einmal für
> das Blatt — wäre dieselbe Handlung an zwei Stellen; die nächste Änderung
> käme nur an einer an. Stattdessen steht das `<form>` einmal versteckt, und
> beide Knöpfe zeigen über `form="…"` darauf.

### 9.4 Knopf

**Vier Arten nach Bedeutung, nicht nach Aussehen** (E-P3-22):

| Art | Aussehen | wofür |
|---|---|---|
| `primaer` | Orange, dunkelblaue Schrift | die **eine** Haupthandlung einer Seite |
| `neutral` | Rahmen | alles Übrige, auch „Bearbeiten" |
| `gefahr` | roter Rahmen, rote Schrift | Löschen |
| `leise` | nur Schrift | Abbrechen, Nebenwege |
| `symbol` | 44 × 44, nur ein Zeichen | braucht `titel` fürs Vorlesen |

```html
<button class="knopf knopf-primaer" type="submit"><svg class="symbol symbol-gross">…</svg><span>Speichern</span></button>
<a class="knopf knopf-leise" href="…"><span>Abbrechen</span></a>
```

**Eine Höhe je Eingabeart: 44 px am Finger, 36 px am Zeiger ab 1024 px**
(Kapitel 6). Der Bestand hatte sechs Varianten und sechs ortsgebundene
Größen; `.btn-primary` trug global `width:100%` und wurde an zehn Stellen
zurückgenommen. Die zweite Stufe ist keine Rückkehr dazu: Sie hängt an einer
Medienabfrage, nicht am Ort.

### 9.5 Meldung

**Fünf Töne**, je mit Symbol und Rolle — und die Liste ist **geschlossen**:

| Ton | Fläche | Symbol | `role` | wofür |
|---|---|---|---|---|
| `info` | Blau hell | Hinweis | `status` | Auskunft |
| `ok` | Blau hell | Haken | `status` | Vollzug |
| `warn` | Orange hell | Warnung | `status` | Vorsicht |
| `fehler` | Rosa | Warnung | `alert` | etwas ist schiefgegangen |
| `schutz` | Rosa | Schloss | `status` | schutzbedürftige Daten, dauerhaft |

**`schutz` ist rot und trotzdem kein Fehler** (S3, Rückmeldung vom
01.09.2026). Er ist für den einen Fall da, in dem eine Meldung **dauerhaft**
steht und trotzdem die Farbe des Ernstfalls braucht: ein Datenschutzhinweis
an der Stelle, an der jemand gleich Daten herunterlädt. Er benutzt Fläche und
Schrift von `fehler` — **kein neuer Farbwert** —, aber `role="status"` statt
`alert`: Was bei jedem Aufruf der Seite dasteht, darf einen Vorleser nicht
jedes Mal unterbrechen. Das Symbol ist das **Schloss**, nicht die Warnung: Es
geht um Schutzbedürftigkeit, nicht um einen Fehlgriff.

> **Ein Ton, den es nicht gibt, ergab bis S3 einen ungestalteten Kasten.**
> `ui_meldung_markup()` setzte die Klasse aus dem übergebenen Wort zusammen;
> ein Tippfehler oder ein erfundener Ton führte zu `meldung-<wort>` ohne
> Regel im Stylesheet — weiß, ohne Fläche, ohne Fehlermeldung. Die
> Spurenseite trug so zwei Meldungen mit dem Ton „hinweis", den es nie gab.
> Die Vollständigkeitsprüfung sieht solche Klassen nicht, weil sie
> **zusammengesetzt** werden. Die Funktion prüft den Ton jetzt selbst und
> wirft bei einem unbekannten.

```html
<div class="meldung meldung-warn" role="status">
  <svg class="symbol symbol-gross">…</svg>
  <p><strong>Auftakt</strong> Text der Meldung.</p>
  <div class="meldung-aktion"><a class="knopf knopf-neutral" href="…">Weg</a></div>
</div>
```

Der Text braucht eine Mindestbreite, unter der die Aktion umbricht — sonst
quetscht ein breiter Knopf den Text auf ein Wort je Zeile (Fund aus O3).

### 9.6 Plakette

**Zweck:** Ein Zustand an einer Zeile oder in einem Kartenkopf. Vier Töne:
`neutral` · `orange` (Winde, Bergwacht) · `blau` (Sekundär, Rettungsmittel,
aktuell, freigegeben) · `rot` (Fehleinsatz, kein Ende, nie gesichert, leer).

```html
<span class="plakette plakette-blau">freigegeben</span>
```

**Plaketten tragen kein Häkchen: Ihr Vorhandensein ist das Häkchen.** Und sie
sind **kein Bedienelement** — wer eine anklickbar braucht, nimmt einen Knopf
(E-P3-17).

> **Es sind genau diese vier Töne.** Ein fünfter Wert erzeugt eine Klasse ohne
> Regel — die Plakette steht dann ohne Hintergrund da, als bloßer Text. Genau
> das ist passiert: `warn` wurde an drei Stellen übergeben und fiel niemandem
> auf, weil der Klassenname zusammengesetzt wird (`'plakette-' . $ton`) und als
> Literal nirgends auftaucht; `tools/vollstaendigkeit/` kann ihn deshalb nicht
> finden. Behoben mit Web 10.3.0, vermerkt in Backlog Nr. 36.

> **Plakette und Schloss schließen einander nicht mehr aus** (S3/AP6,
> E-S3-16). F-N1-B hatte in P3 entschieden: entweder die Plakette
> „verschlüsselt" am Kopf der Karte **oder** das Schloss an der einzelnen
> Zeile. Seit der Rückmeldung vom 31.08.2026 gilt beides nebeneinander, weil
> es zwei verschiedene Auskünfte sind: **Die Plakette sagt „hier stehen
> verschlüsselte Angaben", das Schloss sagt „diese hier."** Bei einer
> Schutzauskunft ist Redundanz kein Lärm.

### 9.7 Feld, Schalter, Segment, Wahlliste

Vier Eingabebausteine, und die Wahl zwischen ihnen ist keine Geschmacksfrage:

| Baustein | wann |
|---|---|
| **`.feld`** | Beschriftung plus Eingabe. Die Beschriftung steht in **Normalschrift** — im Bestand waren Feldnamen gesperrte Versalien, das prägende Stilmittel und zugleich das, was auf 360 px am meisten Breite kostete (E-P3-21). |
| **`.schalter`** | **eines** an oder aus. 44-px-Zeile, Beschriftung links, an in Orange. Abhängige Felder klappen darunter auf, eingerückt mit orangem Randstrich (E-P3-28). |
| **`.segment`** | **eine aus wenigen** kurzen Möglichkeiten nebeneinander („Gemischt / Luft / Boden"). |
| **`.wahlliste`** | **eine aus mehreren** mit Erklärung daneben. 44-px-Zeilen untereinander, die gewählte hell orange (E-P3-20). **Schlichte Liste, keine umrandeten Einzelzeilen** (seit Web 12.3.3): Vier Zeilen mit eigenem Rahmen auf eigener Fläche sahen aus wie vier Karten und sind eine Wahl. Erkennbar ist die Auswahl am gezeichneten Punkt und an der Fläche der gewählten Zeile — dafür braucht keine Zeile eine Umrandung. |

Alle vier sind aus **echten** `<input>` gebaut: Tastaturbedienung,
Vorlesezustand und Absenden kommen damit vom Browser und nicht aus einem
Skript.

```html
<div class="feld">
  <label class="feld-label" for="f-x">Datum <span class="feld-klein-inline">optional</span></label>
  <input class="feld-eingabe" type="date" id="f-x" name="x">
  <p class="feld-klein">Hinweis unter dem Feld.</p>
</div>
```

**Platzhalter tragen ausschließlich Phantasienamen** (E-S3-13). Ein
Platzhalter ist ein Beispiel, kein Vorschlag: Steht dort „z. B. Standort
Kempten", liest ein Teil der NutzerInnen das als die erwartete Antwort und
ein anderer als Aussage darüber, wer diese Anwendung betreibt. Beides ist
falsch. Orte, Personen, Kliniken und Rettungsmittel in Platzhaltern sind
deshalb **erfunden** — erkennbar erfunden, nicht bloß ein anderer echter
Ort. Die Regel gilt für jedes Formular der Anwendung, auch für den
Einrichter, und sie gilt ab S3 für jede neue Stelle.

**Ein gesperrtes Feld sieht gesperrt aus** (`.feld-eingabe:disabled`, seit
S8/AP7). Bis dahin nicht: `.feld-eingabe` setzt Fläche und Schrift selbst und
übermalte damit die Graufärbung, die der Browser einem `disabled` gibt — ein
einzeln gesperrtes Feld war von einem bedienbaren nicht zu unterscheiden
(F-S8-P-03). Es trägt jetzt die **Seitenfläche** statt der Kartenfläche,
gedämpfte Schrift und `cursor:not-allowed`. Die Fläche allein trägt die
Aussage nicht — Rauch auf Schnee sind 1,07:1 —, die Schrift trägt sie:
19,29:1 im bedienbaren Feld gegen 5,30:1 im gesperrten. Der Rand bleibt
`--linie-stark` und damit über den 3:1, die WCAG 1.4.11 für die Begrenzung
eines Bedienelements verlangt.

**Zwei Wege, ein Bild.** Ein einzelnes Feld bekommt `disabled` selbst; eine
ganze Gruppe steht in `.feldsatz-gesperrt` (ein `<fieldset>`, das nur
gruppiert, Web 12.4.1). Der Feldsatz dämpft die Gruppe samt Beschriftungen
über `opacity`, die Regel am Feld sagt, welches Element gemeint ist. Beide
greifen zugleich, und das ist gewollt.

**Das Dateifeld ist der eine Sonderfall.** `input[type=file]` stellt seinen
nativen Knopf auf die Textzeile, und die steht in einem 44 px hohen Feld ohne
senkrechte Polsterung ganz oben — gemessen 0 px Luft darüber, 19 px darunter.
Es gibt dafür genau eine Regel im Stylesheet, die die Zeilenhöhe auf den
Innenraum setzt; die 44 px bleiben dabei stehen. **`align-items` löst es
nicht:** Chromium legt den Shadow-Inhalt eines Eingabefeldes nicht in einen
Flex-Fluss, `display:flex` bleibt an dieser Stelle wirkungslos (nachgemessen).
Wer ein weiteres Dateifeld baut, nimmt `ui_feld()` mit `'art' => 'file'` und
bekommt die Regel mit; sie hängt am Attributselektor, nicht an einer
Zusatzklasse.

### 9.8 Titelzeile

**Zweck:** Rückweg, Titel, Unterzeile, Aktionen rechts — der Kopf fast jeder
Seite.

```html
<div class="titelzeile">
  <a class="rueckweg" href="…"><svg class="symbol symbol-links">…</svg><span>Zurück zum Diensttag</span></a>
  <div class="titelzeile-haupt">
    <div class="titelzeile-text"><h1>Titel</h1></div>
    <div class="titelzeile-aktionen">…</div>
  </div>
  <p class="titelzeile-unter">Unterzeile</p>
</div>
```

Die Unterzeile steht **nach** der Hauptzeile, nicht im Flex-Block: Sonst
bestimmt ihre Breite die des Titelblocks, und die Aktionen brechen unter einen
kurzen Titel („Einsatz 1"), obwohl neben ihm Platz ist (Fund aus O4).

**Der Abstand darunter ist `--abstand-4`, nicht `--abstand-2`** — die
begründete Ausnahme des vertikalen Rhythmus (Kapitel 6): Die Titelzeile ist
eine Überschrift, die Bedienelemente trägt, und der Abstand darunter muss
den 44-px-Knopf freistellen.

### 9.9 Speichern-Leiste

**Zweck:** Erscheint mit der ersten Änderung eines Formulars und klebt unten.
Hängt an `data-dirty-track` (`assets/forms.js`).

**Kein „Verwerfen".** Der Rückweg oben genügt, und ein Verwerfen-Knopf neben
einem Speichern-Knopf ist die Stelle, an der man sich vergreift (E-P3-29).

**Sie hat die Form der Karte** (E-R43-1, seit Web 12.2.3): derselbe Radius,
dieselbe Breite. Bis dahin brach sie mit einem negativen Rand seitlich aus dem
Inhalt aus und lief ohne Radius von Rand zu Rand — sie wirkte dadurch eckig
und breiter als die Karte darüber, obwohl sie zu ihr gehört. **Was bleibt, ist
alles, was die Funktion trägt:** der klebende Sitz, die Trennlinie nach oben
und der Schatten. Die Leiste soll auffallen, weil sie folgt, nicht weil sie
anders geschnitten ist.

**Der Knopf steht rechts, die Zählung links daneben.** Im Markup steht der
Hinweis zuerst — das ist zugleich die Vorlesereihenfolge („12 ausgewählt",
dann „Auswahl sichern"). Ausgerichtet wird über `justify-content:flex-end`,
**nicht über `order`**: Sonst liefen Seh- und Vorlesereihenfolge auseinander.

> **Nicht jedes Formular bekommt eine.** Sie gehört zu Formularen, die man
> *bearbeitet* und deren Stand man verlieren kann. Wo der Knopf das **Ziel des
> Weges** ist — „Diensttag anlegen", „Einsatz verschieben", „Datum ändern" —
> steht er am Ende des Formulars in `.listen-form-fuss`, wo man ihn sucht.
> `data-dirty-track` bleibt trotzdem: Es trägt auch die Verlassen-Warnung und
> die bedingte Abbrechen-Rückfrage.

**Zweite Verwendung: die Sammelleiste** (`kein_haken`, `form`, `zahl`).
Derselbe Baustein, anderer Anlass: Nicht ein schmutziges Formular blendet sie
ein, sondern eine **Auswahl** — und ihr Text ist deren Zahl und deshalb immer
sichtbar (der Hinweis eines Formulars erscheint erst ab 720 px). Zwei
Verwendungen: „Auswahl sichern" in der NutzerInnen-Liste (P3/O9b) und
„Auswahl als GPX" auf der Spurenseite des Diensttages (Web 10.3.0). Mit `form`
kann sie einem Formular an anderer Stelle der Seite gehören; `kein_haken`
hängt sie von `forms.js` ab, das dann nichts zu tun hätte.

### 9.10 Kennzahl

**Zweck:** Wert in Bricolage mit Einheit, darunter die Beschriftung. Ein
Klick öffnet die Liste, auf die sie sich bezieht — deshalb ein `<a>` und kein
`<div>`: Ein Klickziel, das kein Link ist, bedient weder Tastatur noch
Kontextmenü.

Töne wie bei der Plakette (`neutral` / `orange` / `rot`). Die Hervorhebung
der Extremwerte ist **orange, nicht rot** — siehe 3.1.

### 9.11 Dialog und Blatt

**Am Schreibtisch eine Karte im Schleier, mobil ein Blatt von unten**
(E-P3-27). Dasselbe Markup, das Stylesheet entscheidet.

```html
<dialog class="dialog" role="alertdialog">
  <div class="dialog-kopf"><h2>Bestätigen</h2></div>
  <div class="dialog-inhalt"><p>Wirklich löschen?</p></div>
  <div class="dialog-fuss">
    <button class="knopf knopf-leise">Abbrechen</button>
    <button class="knopf knopf-gefahr">Löschen</button>
  </div>
</dialog>
```

> **Der Rückfragedialog ist für eine Handlung da, die sich in einem Satz
> beschreiben lässt.** Was eine **Aufstellung** braucht — Einsätze, Phasen,
> Reanimationen, Ruhesegmente, Trackpunkte — bekommt eine eigene **Seite**.
> Ein Dialog, der einen halben Bildschirm Text trägt, ist keiner mehr; und
> der Weg zu einer Seite hat eine Adresse, die man zurückgehen kann.

### 9.12 Aktionsmenü der Seite

**Nicht zu verwechseln mit 9.3.** `ui_zeilenaktionen()` gehört zu **einer
Zeile**, `ui_aktionen()` zur **ganzen Seite** und steht neben dem Titel.

Mobil ein „⋯" mit Blatt von unten, am Schreibtisch **„Aktionen ▾"** als
Aufklappmenü — dasselbe Markup, das Stylesheet entscheidet (E-P3-27).

```php
ui_titelzeile([
  'titel'    => 'Sonntag, 27.12.2026',
  'aktionen' => ui_aktionen([
      'titel'     => 'Diensttag 27.12.2026',
      'eintraege' => [
        ['text' => 'Einsatz nachtragen', 'symbol' => 'plus', 'href' => '…', 'anlegen' => true],
        ['text' => 'Datum ändern',       'symbol' => 'kalender', 'href' => '…'],
        ['text' => 'Tag löschen',        'symbol' => 'korb', 'href' => '…', 'gefahr' => true],
      ],
  ]),
]);
```

**Der Anlegen-Weg steht als erste Zeile**, in Orange (`anlegen => true`);
„Löschen" steht unten, rot und durch eine Linie abgesetzt (`gefahr => true`).

**Ein Eintrag kann auch eine Handlung sein, nicht nur ein Weg.** „Passwort
zurücksetzen" ist ein POST — als `<a href>` wäre es entweder wirkungslos oder
ein Zustandswechsel auf ein GET hin. Dafür `'form' => 'kennung'`: Der Eintrag
wird ein `<button form="…">`, das Formular steht einmal versteckt auf der
Seite. Ein `<form>` **um** den Eintrag ginge nicht — das Blatt kann selbst in
einem Formular stehen.

### 9.13 Ortsfeld

**Zweck:** Eine Bezeichnung plus optionale Koordinaten, mit Adresssuche,
Vorschlagsliste und Kartenwahl. Gegenstück zu `assets/ortsfeld.js`: Die
Funktion erzeugt die Elemente, das Skript belebt sie — **beide bilden ihre
Kennungen aus demselben Präfix.**

```php
ui_ortsfeld([
  'praefix' => 'site', 'label' => 'Einsatzort',
  'hinweis' => 'Adresse, Koordinaten oder Plus Code',
  'ortswahl' => true, 'max' => 255,
]);
```

**Zwei Formen:** `feld => true` (Vorgabe) baut das ganze Widget samt eigener
Beschriftung; `feld => false` baut **nur das Zubehör** — Suchfeld,
Vorschlagsliste, Zustandszeile, Chip und die versteckten Koordinatenfelder —
für den Fall, dass das Bezeichnungsfeld schon existiert und die Kennung
`<praefix>addr` trägt.

> **Die Kennung `<praefix>addr` gehört dem Feld, in dem gesucht wird** —
> nicht irgendeinem Namensfeld daneben. Steht sie zweimal im Markup, findet
> `getElementById` das erste, und das zweite ist Zierde (F-P3-AI).

**Der Hinweistext ist kein Platzhalter.** „Adresse, Koordinaten oder Plus
Code" nennt das Format, nicht ein Beispiel. Wo das Ortsfeld doch einen
Platzhalter bekommt, gilt die Regel aus 9.7: erfundener Ort, kein echter
(E-S3-13).

**Es sucht beim Tippen** (seit Web 12.3.3, E-S3-06) — in **beiden**
Bedienformen, also auch bei Standort und Zielklinik, wo bis dahin nur die
Lupe suchte. Drei Grenzen fassen das ein und stehen als Konstanten oben in
`assets/ortsfeld.js`: **400 ms** Ruhe nach dem letzten Tastendruck, **ab drei
Zeichen**, **höchstens eine offene Anfrage** (eine laufende wird abgebrochen).
Die Lupe umgeht die Entprellung, nicht die Mindestlänge.

> **Das ist eine Auskunft an Dritte, und sie steht in `docs/Lizenzen.md` 6.2.**
> Die Adresssuche geht an Photon; jede Anfrage trägt die eingetippten
> Buchstaben dorthin. Stehen bereits Koordinaten, ruht die Suche ganz — die
> Formaterkennung läuft lokal und hat Vorrang.

### 9.14 Abbruchseite

**Zweck:** Der aufgerufene Datensatz existiert nicht, gehört einem anderen
Konto oder liegt im Papierkorb.

```php
ui_abbruch(404, 'Einsatz nicht gefunden.',
           ['zurueck' => 'index.php', 'zurueck_text' => 'Zur Startseite']);
```

Gibt Statuscode, Kopfleiste, Meldung, Rückweg und Fußzeile aus und beendet
das Skript. An 16 Stellen stand dafür einmal `exit('Einsatz nicht
gefunden.')` — nackter Text ohne Zeichensatzangabe, ohne Kopfleiste, ohne Weg
zurück. Der HTTP-Code stimmte, die Seite war trotzdem eine Sackgasse.

### 9.15 Symbol und Artzeichen

```php
ui_symbol('korb')                       // <svg class="symbol">…</svg>
ui_symbol('winkel', 'symbol-links')     // gedreht
ui_symbol('haken', 'symbol-gross', 'Erledigt')   // mit <title> zum Vorlesen
ui_artzeichen($tag['kind'])             // luftgebunden / bodengebunden / ohne
```

**Ein `<title>` macht das Symbol vorlesbar** — und ohne ihn ist es
`aria-hidden`. Das ist die richtige Vorgabe: Ein Symbol neben einem Wort, das
dasselbe sagt, doppelt nur.

> **Kein `<title>`, wo der Text daneben steht.** Ohne dritten Parameter
> setzt `ui_symbol()` `aria-hidden="true"`; mit ihm `role="img"` und einen
> `<title>`. Ein Symbol, das allein steht — das Artzeichen in einer Zeile —
> braucht ihn. Ein Symbol im Knopf neben seiner Beschriftung nicht: Sonst
> hört man den Text doppelt.
>
> Genau das ist in O11 einmal passiert und gemessen worden: Die Zeile „Art"
> der Zusammenführ-Vorschau trug Artzeichen **und** Plakette mit demselben
> Wort — ein Screenreader las „luftgebunden luftgebunden". Sichtbar war es
> nicht, denn ein `<title>` wird nicht gezeichnet.

### 9.16 Anti-Muster

Was in dieser Oberfläche schon einmal schiefgegangen ist — jedes davon ist
ein echter Fund, kein erfundenes Beispiel:

| Anti-Muster | was passiert | richtig |
|---|---|---|
| `.meine-klasse{width:0}` auf einem Kästchen | Verliert gegen `input[type=checkbox]` (0,1,1). Kästchen bleibt 20 × 20 px und fängt Klicks ab. | `input[type=checkbox].meine-klasse` (F-P3-AP, F-P3-AZ) |
| `knopf-gefahr` im Aktionsblatt | `.blatt-zeile` setzt die Schriftfarbe selbst und gewinnt. „Löschen" ist nicht rot. | `blatt-gefahr` (F-P3-AX) |
| Ein Baustein auf einem `<label>`, der dessen Grundform nicht zurücknimmt | `label` trägt `margin-bottom: --abstand-3`. In einem Rahmen ist das kein Abstand, sondern ein toter Streifen — bei jeder Segmentwahl 12 px (F-N1-L). | `margin:0` im Baustein, und nachmessen |
| `inset` nach `top` in derselben Regel | `inset` ist die Kurzform für alle vier Seiten und setzt das `top` davor auf `auto` zurück. Die Leiste klebte nicht mehr und lief über die Kopfleiste (F-N1-A). | `inset` zuerst, die einzelne Seite danach |
| Eine Regel, die denselben Wert setzt wie die Grundform | Sie tut nichts — bis ihre höhere Spezifität einen Baustein schlägt, der etwas anderes will (F-N1-L). | Löschen. Eine Dublette ist nie harmlos |
| Einen `z-index` aus einem anderen Zustand stehen lassen | `.leiste` brauchte 60 als Schublade und behielt es als Rasterspalte — über der Kopfleiste (40). | In jedem Zustand den nötigen Wert setzen, auch den zurücknehmenden |
| `data-confirm` **und** `data-dirty-track` am selben Formular | Der Browser fragt nach der bestätigten Rückfrage ein zweites Mal. | `confirm.js` sagt dem Dirty-Tracking ab (F-P3-AY) |
| `ui_speichern_leiste()` ohne `assets/forms.js` | Die Leiste erscheint **nie** — ohne jede Fehlermeldung. | `ui_seite_ende(['skripte' => ['assets/forms.js']])` |
| eine Klasse ohne Regel im Stylesheet | Das Element ist ungestaltet, und niemand merkt es. Der Export-Knopf war so vier Monate lang 23 px hoch. | Vollständigkeitsprüfung lesen, nicht nur zählen (F-P3-BA) |
| Spaltenbreite über `:nth-child` | Rutscht beim Streichen einer Spalte still auf die falsche. | eine Klasse |
| ein Unicode-Zeichen als Symbol (✔ ● ⚠) | Sieht auf jedem System anders aus und ist keine Grafik. | `ui_symbol()` (E-P3-18) |
| ein Token in `:root`, das niemand benutzt | Sieht aus wie eine Zusage und ist keine. Die Filterleiste war zwei Pakete lang zu schmal. | erzeugte Tokentabelle lesen (F-P3-BC) |
| eine Aufstellung in einem Rückfragedialog | Ein Dialog mit halbem Bildschirm Text ist keiner mehr. | eine eigene Seite |
| zwei primäre Knöpfe auf einer Seite | Keiner ist mehr die Haupthandlung. | einer `primaer`, der Rest `neutral` |
| ein Absendeknopf, der nackt im `<form>` steht | Er bekommt den Abstand, den zufällig das Element davor mitbringt — mal 12, mal 16, mal keinen. „Profil speichern" stand so zwischen `ui_karte_ende()` und `</form>`; die Durchsicht fand zwölf solche Stellen (S3/AP2). | ein `<div class="listen-form-fuss">` darum |
| ein Abstand, der an der Seite hängt statt am Baustein | Er wirkt an dieser einen Stelle und ist beim nächsten Baustein wieder weg. | die Stufe am Baustein setzen, nach der Rhythmustabelle (Kapitel 6) |

---

### 9.17 Schneide-Bereich und Zeitleiste

*Neu mit Web 12.6.0 (S4/A2b). Mockup `docs/mockups/S4-schneiden.html`,
freigegeben am 31.08.2026.* Gebaut wird er in `assets/schneiden.js`, nicht in
`ui.php`: Die Segmentliste entsteht im Browser aus `api/day.php`, es gibt
keine PHP-Seite, die ihn ausgeben könnte. In der erzeugten Bausteintabelle
(Kapitel 9, Anfang) steht er deshalb nicht — sie liest `ui.php`.

```
.schnitt-bereich      der aufklappbare Block unter einer Segmentzeile
  .schnitt-vorschau   der Kasten um die Zeitleiste
    .schnitt-leiste   die Leiste selbst (position:relative)
      .schnitt-bahn   die volle Dauer des Segments      — --sand
      .schnitt-weg    ein früher schon geschnittener Teil — --gedaempft, .35
      .schnitt-wahl   der gewählte Bereich               — --orange
      .schnitt-griff  die beiden Kanten                  — --dunkelblau
      .schnitt-marke  Uhrzeit am Griff
    .schnitt-raender  Anfang und Ende des Segments unter der Leiste
  .schnitt-felder     eine Reihe Zeitfelder (flex, 1 1 180px je Feld)
```

**Der orange Randstrich ist nicht neu.** `.schnitt-bereich` benutzt dieselbe
Form wie `.rea-ereignisse`: `border-left: var(--strich-stark) solid
var(--orange)` plus Innenabstand. Sie sagt „das hier gehört zur Zeile
darüber" (E-P3-28), und dafür gibt es keinen zweiten Weg.

**Kein neuer Farbwert und keine neue Größe.** Alles hier kommt aus den Token
in `:root`. Der Balken ist `--sand`, das Gewählte `--orange` — dieselbe
Aussage wie überall sonst („dieser Teil ist gemeint").

> **Die Zeitleiste ist HTML, kein SVG — und das ist ein Fund.** Die erste
> Fassung war ein `<svg viewBox="0 0 640 120">` mit `width:100%`, wie im
> Mockup. Ein `viewBox` skaliert aber **seine Beschriftung mit**: Auf 1280 px
> stand die Uhrzeit richtig, auf 390 px war sie sechs Pixel hoch. Dieselbe
> Zahl in zwei Größen, je nach Fenster — und die Schriftskala aus Kapitel 5
> gilt dann für sie nicht mehr.
>
> Mit Prozentbreiten auf gewöhnlichen Elementen bleibt der Text Text und nur
> der Balken skaliert. **Gemessen:** 390 px, Beschriftung 13 px, waagerechter
> Überlauf 0.

> **Sie ist eine Anzeige, keine Bedienung.** Geführt wird über die Zeitfelder;
> die Leiste zeigt das Ergebnis. Ein Ziehen an den Griffen wäre eine zweite
> Eingabe für dieselbe Zahl — die eine auf die Minute genau, die andere auf
> das Pixel —, und die beiden liefen auseinander. Der Mockup hält es ebenso
> („führend sind die Felder").

**Zeitfelder sind Textfelder mit `.zeitfeld`**, nie `<input type="time">`
(E1). Der Mockup zeigt fünfmal `type="time"`; das ist die eine Stelle, an der
die Umsetzung ihm bewusst nicht folgt — die Begründung steht im Kopf von
`assets/zeitfeld.js`: Ein `type="time"` zeigt sein Format nach der
Regionseinstellung des Betriebssystems und wird bei einer 12-Stunden-Region
zu „01:30 PM", auch auf deutscher Oberfläche. In einer Notfalldokumentation
ist das eine Fehlerquelle.

### 9.18 Wertekasten, zweite Stufe (`.codeblock-lang`)

*Neu mit Web 15.1.0 (S8/AP2). Entscheidung E-S8-10, Backlog Nr. 78, Mockup
`docs/konzepte/konzept-s8/mockups/06-hintergrundjobs.html`, freigegeben am
05.09.2026.*

Der Wertekasten hat zwei Stufen, und der Unterschied ist die **Länge des
Werts**:

| Stufe | Klasse | Wofür | Schrift |
|---|---|---|---|
| groß | `.codeblock-wert` | sechs Zeichen, die jemand abschreibt: Geräte-ID, Wiederherstellungsschlüssel | `--groesse-5`, 600, gesperrt (`.06em`) |
| klein | `.codeblock-wert-lang` | sechzig bis hundert Zeichen, die jemand kopiert: Cron-Zeile, Token-Adresse, Setz-Link, Serverschlüssel-Zeile, API-Schlüssel | `--groesse-2`, 400, ohne Sperrung, `word-break:break-all` |

```
.codeblock.codeblock-lang     der Kasten (flex, Wert links, Knopf rechts)
  .codeblock-text             die Textspalte (flex:1 1 auto, min-width:0)
    .codeblock-titel          optionale Kleinzeile darüber („Adresse")
    .codeblock-wert-lang      der Wert, data-kopierwert
  .knopf.knopf-leise          „Kopieren", data-kopieren, im Markup hidden
```

**Gebaut wird er mit `ui_codeblock_lang($wert, $titel)`** — der Knopf ist Teil
des Bausteins und nicht Sache der Seite. Ein Wert, den man kopieren soll, und
ein Knopf, der ihn kopiert, gehören zusammen; sonst baut ihn die nächste Seite
anders. Die Seite nimmt `assets/kopieren.js` in `ui_seite_ende(['skripte' =>
…])` mit.

**Der Knopf steht im Markup auf `hidden`** und wird vom Skript eingeblendet.
Ohne JavaScript gäbe es sonst einen Knopf, der nichts tut — und das ist
schlechter als keiner. Der Wert bleibt in beiden Fällen lesbar und markierbar.

**Seit Web 15.4.1 ist die Umstellung vollständig** (S8/AP6). Die kleine Stufe
steht jetzt an allen sieben Stellen mit langen Werten: Cron-Zeile und
Token-Adresse (Hintergrundjobs), Setz-Link (Kontoseite), Einladungslink
(NutzerInnen-Liste), Serverschlüssel-Zeile (Backup-Ziele), Geräte-ID und
API-Schlüssel (Geräte) sowie die SHA-256-Prüfsumme des APK. **Die große Stufe
bleibt zwei Werten:** dem Kopplungscode und dem Wiederherstellungsschlüssel —
beide sind kurz und werden abgeschrieben, nicht kopiert.

### 9.18a Kopf einer langen Liste: Suche über den Filtern

`.listenkopf` ist eine **Spalte, in jeder Breite**: oben `.listensuche` (mit
der Höchstbreite `--listensuche-breit`, 36 rem), darunter `.filterreihe` mit
`flex-wrap`.

**Bis Web 15.4.0 rückten beide ab 1024 px nebeneinander**, das Suchfeld auf
`flex:0 1 26rem`. Gemessen an der NutzerInnen-Liste mit fünf Filtern (zusammen
789 px): Bei 1440, 1280 und 1024 px fiel der letzte Filter allein in eine
zweite Zeile, während die erste halb leer blieb — ein Umbruch, der wie ein
Unfall aussah. Genau das war Backlog Nr. 73.

Untereinander ist der Umbruch Absicht: Die Reihe beginnt links, füllt die
Breite und bricht am Ende. Über 36 rem wird ein Eingabefeld nicht besser
lesbar, sondern nur breiter.

**Die Regel gilt für jede Liste mit Suche und Filtern**, nicht für die eine
Seite, auf der sie aufgefallen ist.

**Kein Symbol am Knopf.** Der Vorrat (Kapitel 8) hat keines für „kopieren",
und ein neues bräuchte Freigabe mit Mockup. Das Wort tut es. Ohne
Zwischenablage-Berechtigung markiert der Knopf den Wert und sagt „markiert —
Strg+C"; die Rückmeldung steht **im Knopf** und nicht daneben, weil ein Kasten,
der aufklappt, den Rest der Seite verschiebt.

### 9.19 Speicherbalken (`.speicher-balken`)

*Neu mit Web 15.1.0 (S8/AP2). Entscheidung E-S8-18, Mockup
`docs/konzepte/konzept-s8/mockups/07-servereinstellungen.html` Fassung 2,
freigegeben am 05.09.2026.*

Zwei Fragen, die eine Zahlenreihe nicht beantwortet: **Wie voll ist es, und
woraus besteht das?** „1,3 GB von 2 GB" sagt das Erste; erst die Segmente
sagen, dass davon 0,9 GB Konto-Backups sind und 0,4 GB Komplett-Stände — und
damit, an welcher Schraube man dreht.

```
.speicher-balken              8 px hoch (--balken), voll gerundet, flex
  > span.sb-*                 ein Segment je Art, Breite inline (gerechnet)
  > span.speicher-luecke      unsichtbarer Platzhalter bis zur Schwelle
  > span.speicher-marke       der Schwellenstrich (--strich-stark)
.speicher-legende             darunter, --groesse-2, gedämpft
  > span > i.sb-*             der Farbpunkt (--balken-punkt, voll gerundet)
```

| Klasse | Art | Farbe |
|---|---|---|
| `.sb-konto` | Konto-Backups | `--blau` |
| `.sb-komplett` | Komplett-Backups | `--dunkelblau` |
| `.sb-db` | Datenbank | `--orange` |
| `.sb-dateien` | Dateien der Anwendung | `--sand` |
| `.sb-frei` | frei (nur in der Legende) | `--linie` |

**Die Breite steht inline, die Farbe nicht.** Die Breite ist ein gerechneter
Wert und kann gar nicht anders als am Element stehen; die Farbe kommt aus einer
Klasse, damit kein Token im Markup landet (Grundregel 4). Die beiden
`style="width:…%"` sind der einzige neue Eintrag in der Zählung der
Vollständigkeitsprüfung.

**Dieselbe Art hat in beiden Balken dieselbe Farbe.** Wer von „Backups" nach
„Installation gesamt" schaut, soll die Konto-Backups wiedererkennen.

**Ohne Bezugsgröße keine Anteile.** Fehlt die Webspace-Angabe, werden die
Segmente anteilig **zueinander** gezeichnet, und die Legende nennt nur die
Summe: Der Balken zeigt dann die Zusammensetzung, nicht die Füllung. Alles
andere hieße, eine Bezugsgröße zu erfinden.

**Der Ton der Plakette folgt den Warnschwellen** — unter der ersten neutral
(blau), ab der ersten orange, ab der letzten rot. Eine Regel
(`speicher_ton()`) für Balken, Plakette und später die Statusseite; sonst
färbt sich der Balken orange, während der Status noch „in Ordnung" sagt.
Gemessen bei Schwellen 70/90: 36 % blau, 71 % orange, 88 % orange, 95 % rot,
100 % rot.

**Zwei neue Token, beide abgeleitet:** `--balken: 8px` ist `--abstand-2`, also
eine Stufe des Vierer-Rasters — ein Balken, der eine Zeile Grafik ist und keine
Fläche. `--balken-punkt: 10px` ist `--radius`, die kleinste Rundung der Skala,
und ergibt einen Punkt, der neben 13-px-Text lesbar ist, ohne ihn zu
überragen.

### 9.20 Lesespalte im Gerüst

*Neu mit Web 15.1.0.* `ui_geruest_start(['lesespalte' => true])` setzt
`.rahmen-lesespalte` und begrenzt die Inhaltsspalte auf `--lesespalte`
(760 px). Die Regel gab es seit P3 — sie war nur für Seiten **ohne** Leiste
gebaut (Anmeldung, Rechtstexte, Wiederherstellung) und über das Gerüst nicht
erreichbar.

**Wann.** Seiten mit wenigen Karten und viel Erklärtext: Betrieb →
Servereinstellungen ist die erste. Eine Formularzeile über 1600 px zu ziehen
macht sie nicht besser lesbar, sondern schlechter. Seiten mit vielen Karten
bekommen stattdessen die Zweispaltenregel (E-S8-18, ab AP5).

### 9.21 Logo-Vorschau der Installation (`.logo-vorschau`)

*Neu mit Web 15.2.0, Mockup 09 (freigegeben 05.09.2026).* Eine Kachel mit dem
gerade gültigen Logo, daneben der Satz, worauf es wirkt. Steht in der Karte
„Logo" auf **Verwaltung → Installation**, über der Segmentwahl.

```html
<div class="logo-vorschau">
  <div class="logo-kachel"><img src="…_weiss.svg" width="54" height="34" alt=""></div>
  <p class="feld-hinweis">Kopfleiste, Browser-Symbol und Anmeldeseite. …</p>
</div>
```

| Maß | Wert | Herkunft |
|---|---|---|
| Kachel | `--logo-kachel` = `--kopf` (56 px) | so hoch wie die Kopfleiste — dort sieht man das Logo täglich |
| Logo darin | 34 px hoch, Breite aus `ui_logo_masse(34)` | dieselbe Zahl wie in der Kopfleiste |
| Fläche | `--dunkelblau` | ebenfalls die der Kopfleiste. Auf Weiß stünde das Logo nirgends |
| Radius | `--radius` | wie Knopf und Meldung |

**Die Kachel zeigt das AUFGELÖSTE Logo**, nicht die Einstellung: Bei
„wechselnd" also das Ergebnis dieser Sitzung. Sonst zeigte sie bei einer der
drei Wahlmöglichkeiten gar nichts.

**Die Breite steht am `<img>`, nicht im Stylesheet.** Sie ist eine Eigenschaft
der Datei — Luft 400,16 × 249,81, Boden 420 × 335 —, und `width`/`height` am
Bild-Tag ist das Einzige, was der Browser vor dem Laden kennt (S3/AP11).

**Bricht die Erklärung um**, rutscht sie unter die Kachel (`flex-wrap`); die
Kachel bleibt unverändert groß.

### 9.22 Kopfaktion als Absendeknopf

*Neu mit Web 15.2.0.* `ui_karte_start(['aktion' => [… 'form' => 'f-sichern']])`
gibt statt des `<a class="karte-aktion">` ein
`<button type="submit" class="karte-aktion" form="…">` aus — gleiche Klasse,
gleiches Aussehen, gleicher 44-px-Anfassbereich.

**Warum es das braucht.** „Jetzt sichern" auf der Kontoseite ist ein POST, kein
Link. Ein `<form>` um den Knopf ginge nicht: Der Kartenkopf steht bereits in
einem Formular, und verschachtelte Formulare gibt es in HTML nicht. Das
`form="…"`-Attribut ist der Weg, den die Seite ohnehin für Blattzeilen und
Titelaktionen benutzt.

**Es bleibt bei EINER Kopfaktion je Karte** (E-P3-25). Was mehr braucht,
bekommt ein Aktionsmenü.

### 9.23 Die Ampel: was die vier Plakettentöne auf einer Statusseite heißen

*Neu mit Web 15.3.0 (S8/AP4, E-S8-16).* **Keine neuen Töne** — die vier gibt
es seit P3 (9.4). Neu ist, dass sie auf **Betrieb → Status** eine feste
Bedeutung tragen, und dass diese Bedeutung an einer Stelle steht.

| Ton | heißt | woran man es erkennt |
|---|---|---|
| **blau** | Es ist in Ordnung. | Der Normalzustand. Eine blaue Zeile fordert nichts. |
| **orange** | Es braucht Aufmerksamkeit, **arbeitet aber**. | Wartungsmodus an, Job mit Rückstand, Backup überfällig. |
| **rot** | Es **arbeitet nicht** — oder es geht dabei etwas verloren. | Serverschlüssel fehlt, Job mit Fehler, Ablage nicht beschreibbar. |
| neutral | Nicht eingerichtet, oder eine reine Zahl ohne Wertung. | Kein Backup-Ziel, PHP-Fassung. |

**Der Unterschied zwischen orange und rot ist nicht die Schwere, sondern die
Frage „läuft es noch?".** Ein überfälliges Konto-Backup ist ärgerlich, aber
die Anwendung arbeitet; ein fehlender Serverschlüssel heißt, dass kein
Komplett-Backup mehr entsteht. Wer diese Grenze verschiebt, macht die Farbe
zur Meinung — und dann liest sie niemand mehr.

**Null ist kein Befund.** Eine Zahl von 0 bekommt den neutralen Ton, nicht
den warnenden. „0 überfällig" in Orange behauptet ein Problem, wo gerade
keines ist (dieselbe Regel wie bei den Statuskacheln, O11).

**Eine Statusseite bewertet, eine Statistik zählt.** Was nichts fordert,
gehört nicht in die Ampel — `betrieb_statistik.php` trägt deshalb keine.

### 9.24 Zeilenkopf in einer Kennzahlentabelle

*Neu mit Web 15.3.0.* `.tabelle th[scope="row"]` steht **links**, nicht
mittig. `.tabelle th` ist auf `center` — richtig für die Kopfzeile, falsch für
die erste Spalte einer Tabelle, in der links die Beschriftung und rechts die
Zahlen stehen.

```html
<tr><th scope="row">Zuletzt angemeldet</th>
    <td class="zahl-spalte">9 <span class="zeile-klein">82 %</span></td></tr>
```

**`zeile-klein` in einer Tabellenzelle** ist Absicht und kein Missgriff: Die
Klasse ist der gedämpfte Zusatz in kleiner Schrift, und genau das ist der
Anteil unter der Zahl. Eine eigene Klasse dafür wäre eine zweite Regel mit
demselben Inhalt.

### 9.25 Die Einstellungsleiste: drei Blöcke, Zähler, Unterpunkte

Drei Bausteine, die nur hier vorkommen und zusammengehören (S8/AP5, E-S8-04,
E-S8-07, E-S8-15). Alle drei entstehen in `ui_leiste_einstellungen()` und
`assets/menue.js`.

**Der Block ist ein Akkordeon.** `<details class="akkordeon leiste-gruppe">`
mit `<summary class="akkordeon-zeile">` — derselbe Baustein wie in der
Diensttage-Leiste, nur mit Schriftgrad und Farbe der früheren
`.leiste-kopfzeile`. Der Winkel steht **links**, wie dort; das freigegebene
Mockup 01 zeichnet ihn rechts, und diese Abweichung ist Absicht: Zwei Leisten
mit demselben Mechanismus sollen denselben Griff haben.

Offen sind „Einstellungen" und der Block der aktiven Seite — **in jeder
Breite**. Der zugeklappte Block zeigt die Zahl seiner Einträge
(`.gruppen-zahl`, nur zugeklappt, `aria-hidden`): Sie sagt, was verborgen
ist, nicht was man sieht.

**Der Zähler** (`.zaehler`) steht rechts im Eintrag und nur über null. Drei
Töne, dieselben wie die Ampel in 9.23: rot (Grundform), `.zaehler-orange`,
`.zaehler-neutral`. Neutral steht auf Sand, nicht auf blassem Rot — ein
blasses Rot läse sich als „fast schlimm", die Zahl soll aber gar nichts
bewerten.

**Die Unterpunkte** (`.eintrag-unterliste` mit `.eintrag-unter`, Marke der
obersten sichtbaren Karte `.hier`) stehen unter dem aktiven Eintrag: die
Kartentitel der Seite als Sprungmarken, `--unterpunkt` (28 px) hoch, eine
Schriftstufe kleiner, ohne Symbol und ohne Randstrich. Sie sind **keine**
Menüpunkte zweiter Ordnung; wer sie dafür hält, sucht dahinter eine eigene
Seite. Die Markierung ist **fett, nicht orange**: Orange heißt in dieser
Oberfläche „hier stehst du" und gehört dem aktiven Menüpunkt.

Sie entstehen im Browser aus den Karten der Seite, nicht aus PHP. Der Grund
steht im Kopf von `assets/menue.js`: Die Leiste wird vor dem Inhalt
gezeichnet, die Seite müsste ihre Kartentitel also zweimal nennen.
**Voraussetzung ist eine `id` an der Karte** — mit dem Vorsatz `k-`; ohne
sie ist die Karte kein Sprungziel und erscheint nicht.

### 9.26 Zwei Kartenspalten — drei Wege, und wann welcher

| Klasse | ab | wer teilt auf | wofür |
|---|---|---|---|
| `.form-raster` + `.form-spalte` | 1200 | die Seite, im Markup | Karten mit thematischer Ordnung: links Server und E-Mail, rechts Jobs und Backups |
| `.zweispalter` + zwei `<div>` | 1200 | die Seite, im Markup | zwei Blöcke beliebigen Inhalts, nicht nur Karten |
| `.karten-raster` | 1200 | der Browser (Mehrspaltensatz) | eine Reihe gleichrangiger Karten ohne Ordnung |

`.karten-raster` nimmt die Karten **direkt** als Kinder und lässt sie
fließen; `break-inside:avoid` hält jede zusammen. Eine Karte, die dazukommt,
braucht keine Zuordnung — dafür lässt sich keine erzwingen.

**Wann zwei Spalten?** Nicht nach Anzahl, sondern nach Höhe. Gemessen an
Betrieb → Updates: vier Karten, einspaltig 1206 px, zweispaltig 977 px. Ab
vier Karten ohne thematische Ordnung lohnt es sich; darunter nicht.

`.form-raster` und `.zweispalter` tun dasselbe unter zwei Namen — das ist ein
Altbestand und steht als Aufräumpunkt im Backlog, nicht als Vorbild.

### 9.27 Die Einstellungs-Übersicht in drei Spalten

`.uebersicht-raster` mit einer `.uebersicht-gruppe` je Block, ab 1024 px als
Grid mit `repeat(auto-fit, minmax(var(--uebersicht-spalte), 1fr))`. Die Zahl
der Spalten ergibt sich damit aus der Zahl der Blöcke und die aus der Rolle —
eine für eine NutzerIn, zwei für eine Admin, drei für eine BetreiberIn, ohne
dass das Stylesheet die Rolle kennt.

Die Überschrift des ersten Blocks (`.uebersicht-block-erst`) steht nur
nebeneinander. Gestapelt stünde „EINSTELLUNGEN" unmittelbar unter der
Seitenüberschrift „Einstellungen"; in drei Spalten benennt sie die Spalte,
und ohne sie stünde eine namenlose neben zwei benannten. Im Markup steht sie
immer, damit ein Vorleseprogramm alle Blöcke gleich benennt.

## 10. Seitentypen und das Rezept für eine neue Seite

### 10.1 Fünf Typen

| Typ | Hülle | Leiste | Beispiele |
|---|---|---|---|
| **Inhaltsseite** | `ui_geruest_start(['leiste' => 'diensttage'])` | Diensttage | Tagesübersicht, Einsatzansicht, Formular, Papierkorb, Zeitraum |
| **Einstellungsseite** | `ui_geruest_start(['leiste' => 'einstellungen'])` | Einstellungsmenü | Profil, Standorte, Geräte, Konto-Backups, Installation, Betrieb |
| **Suchseite** | `ui_geruest_start(['leiste' => 'filter'])` | Filter, von der Seite gefüllt | Suche |
| **Öffentliche Lesespalte** | `ui_kopf(['menue' => false])` + `.rahmen rahmen-lesespalte` | keine | Impressum, Datenschutz, Abbruchseite |
| **Anmeldehülle** | `.anmeldung-body` + `<main class="anmeldung">` | keine | Anmeldung, Passwort setzen, Einrichter |

**Es gibt keine zweite Leiste.** Unter 1024 px liegt dieselbe
`<aside class="leiste">` als Schublade über dem Inhalt, darüber steht sie fest
daneben; der Unterschied ist ausschließlich CSS.

**Jede Seite hat eine Fußzeile** — auch vor der Anmeldung, mit Lizenz,
Versionsnummer und den Verweisen auf Impressum und Datenschutz. Die einzige
Ausnahme ist der Einrichter: Er läuft, bevor es eine Datenbank gibt, und die
beiden Rechtstextseiten brauchen eine.

> **Die Wartungsseite ist der Sonderfall, der die Regel bestätigt** (Web
> 13.2.0, S5 Paket W). Sie benutzt die **Lesespalte** — `.rahmen
> rahmen-lesespalte`, `.inhalt`, `.text`, `.meldung meldung-warn` —, aber
> **nicht `ui.php`**: Dessen Hülle zieht über `ui_favicon()` und
> `logo_stamm()` die Datenbank herein, und die ist im Wartungsfall genau
> das, was gerade umgebaut wird. Sie steht deshalb als eigenes Markup in
> `server/wartung_lib.php`.
>
> Zwei Folgen, die man kennen muss: **Sie hat keine Fußzeile** (die braucht
> `WEB_VERSION` und die Rechtstextseiten, und beide Verweise gingen ins 503),
> und **ihr Logo wird gewürfelt** statt aus `logo_stamm()` geholt — eine
> Installation mit eigenem Logo sieht während der Wartung eines der beiden
> Standardlogos. Kein neuer Baustein, keine neue Regel im Stylesheet; wer
> sie ändert, ändert die vorhandenen mit.

### 10.2 Rezept: eine neue Inhaltsseite

1. `ui_seite_start(['titel' => '…'])`
2. `ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage'])`
3. `ui_titelzeile(['titel' => '…', 'zurueck' => […]])` — **nicht** ein blankes
   `<h1>`; der Rückweg gehört dazu.
4. `ui_meldung($hinweis, $fehler)` direkt darunter.
5. `<p class="seiten-erklaerung">` — ein Absatz, keine zwei. Wer die Seite zum
   zehnten Mal öffnet, liest sie nicht mehr und muss trotzdem daran vorbei.
6. Inhalt in `ui_karte_start()` … `ui_karte_ende()`; Listen als `ui_zeile()`
   mit `ui_zeilenaktionen()`; Formulare aus `ui_feld()` und Geschwistern.
7. Der Hauptknopf: `ui_speichern_leiste()` **nur**, wenn man an der Seite
   arbeitet; sonst `ui_knopf()` in `.listen-form-fuss`.
8. `ui_geruest_ende()` und `ui_seite_ende(['skripte' => [...]])`.

> **`ui_geruest_ende()` bringt vier Skripte mit** — `symbol`, `schublade`,
> `blatt`, `confirm` —, aber **nicht** `forms.js`. Wer eine Speichern-Leiste
> oder ein `data-dirty-track` benutzt, trägt es in `ui_seite_ende()` nach;
> sonst erscheint die Leiste nie, und zwar **ohne jede Fehlermeldung**.

### 10.3 Danach

Prüfmittel laufen lassen (1.3), und zwar **zuletzt** — erst der Code, dann die
Dokumentation, dann die Werkzeuge. Ein Werkzeug, das vor der letzten Änderung
lief, misst einen Stand, den es nicht mehr gibt.

---

## 11. Prüfmittel

| Werkzeug | beantwortet |
|---|---|
| `tools/vollstaendigkeit/pruefen.py` | Ist etwas verlorengegangen? Steht jeder Wert an der einen Stelle? |
| `tools/screenshots/aufnehmen.mjs` | Sieht es in allen acht Breiten so aus, wie es soll? Überlauf, Konsolenfehler, Knopfhöhen. |
| `tools/screenshots/kontrast.py` | Erreicht jedes Farbpaar der Token seinen Sollwert? |
| `tools/design/tabellen.py` | Erzeugt die Tabellen dieses Dokuments aus den Quellen. |
| `tools/wortliste/wortliste.py` | Sprechen Oberfläche und Dokumentation neutral von Land und Luft? |
| `tools/stilvergleich/` | Hat sich am Erscheinungsbild etwas geändert, das nicht geplant war? |

**Der Stilvergleich hat während P3 geruht** und ist in O12 neu geeicht: Die
Frage „hat sich etwas geändert?" ist in einer Phase, in der sich alles ändert,
keine. An seine Stelle traten `vollstaendigkeit` und `screenshots`. Ab P4
wacht er wieder — und dann gilt: **Bei einer beabsichtigten
Gestaltungsänderung ist das Ergebnis keine Null, sondern eine Liste.** Sie
wird gegen die Liste der geplanten Änderungen gehalten; jede Abweichung
darüber hinaus ist unbeabsichtigt und wird geklärt, bevor committet wird.

**Und kein Prüfmittel sieht, wie es aussieht.** Die vierzehn Punkte der ersten
Rückmeldungsrunde nach P3 (Web 9.14.0) sind allesamt durch jedes Werkzeug
gelaufen: kein Überlauf, kein Konsolenfehler, kein Knopf ≠ 44 px, alle Werte
aus den Token. Vier davon waren echte Fehler — eine Leiste, die über die
Kopfleiste malt; ein toter Streifen unter *jeder* Segmentwahl; ein
verschwundenes Schloss; eine Einstellung, die nichts tat. Was sie gemeinsam
haben: Sie brechen nichts. **Die Prüfmittel sichern die Untergrenze, nicht die
Gestalt** — dafür braucht es einen Menschen, der hinsieht.

**Eine grüne Zahl ist erst dann ein Beleg, wenn sie das Gemessene benennt.**
Der Bilderlauf meldete nach O9c „248 Bilder, 0 Überlauf" — 176 davon zeigten
die Anmeldeseite (F-P3-AQ). Und die Knopfhöhenmessung sucht `.knopf`: Ein
Knopf ohne diese Klasse fällt ihr nicht auf, und genau so ist der Export-Knopf
vier Monate lang ungestaltet geblieben (F-P3-BA).

### Die Hilfslisten der Vollständigkeitsprüfung

Drei Markdown-Tabellen, damit ein Mensch sie liest. Alle drei verlangen eine
**Begründung** — ein Eintrag ohne Grund ist keiner, sondern ein weggedrücktes
Ergebnis.

| Liste | wofür | Vermerke |
|---|---|---|
| `streichliste.md` | Klassen des alten Stylesheets, die es nicht mehr gibt, je mit ihrem Ersatz | `[bleibt]` für die, die als Skriptanker im Markup bleiben |
| `ausnahmen.md` | Werte außerhalb der Token — Geometrie statt Gestaltung | — |
| `ohne-regel.md` | Klassen im Markup, die keine Regel brauchen | `[bleibt]` = kein Befund, nur eine Zahl · `[offen]` = bleibt Befund, unter eigener Überschrift |

**Warum es die dritte gibt.** Die Gegenprobe „im Markup, aber ohne Regel" hat
den Export-Knopf gefunden — und stellte diesen einen echten Fund neben
28 falsche. Eine Liste in diesem Verhältnis wird überflogen, nicht gelesen.
Seit O12 zählt sie die begründeten Fälle nur noch und meldet die ungeklärten
einzeln. Wer eine Klasse ohne Regel einführt (ein Skriptanker, ein Behälter),
trägt sie im **selben Paket** dort ein, mit Begründung und Fundstelle.

Und die Liste meldet ihre eigenen toten Einträge: Wessen Klasse inzwischen
eine Regel hat oder aus dem Markup verschwunden ist, steht als „Eintrag
ungenutzt" da. Ohne diese Rückfrage wird eine Ausnahmeliste in zwei Paketen
genau das, wogegen sie schützt.

---

## 12. Änderungsverlauf

| Fassung | Was |
|---|---|
| **Web 15.5.0 (S8/AP7)** | Kapitel 6: **zwei Höhen für Bedienelemente** — 44 px am Finger, 36 px am Zeiger ab 1024 px, an drei Medienmerkmalen zugleich (`hover`, `pointer`, `min-width`). Kapitel 9.4 nachgezogen. Kapitel 9.7: neue Regel `.feld-eingabe:disabled` (F-S8-P-03) und der Zusammenhang mit `.feldsatz-gesperrt`. Die erzeugten Tabellen zählen seither **ohne Kommentare**: Die Schwellentabelle hatte eine zusammengesetzte Abfrage verschluckt (20 → 21 Medienblöcke), die Bausteintabelle zählte Klassennamen aus Kommentaren als Unterklassen mit — elf Zeilen korrigiert, `ui_feld()` von „+24" auf **+18**. |
| **Web 12.4.2 (S3/AP11)** | Kapitel 2.3: Logotabelle auf die tatsächlichen Dateinamen gebracht (sie führte noch die Namen von vor dem NEF-Platzhalter-Ersatz) und um die Rahmenmaße ergänzt. Neue Zusage: **Rahmen = Zeichnung** — das Bodenlogo war auf ein Quadrat gepolstert, ein Zehntel seiner Höhe war leer. Dazu zwei Warnungen für den nächsten, der eine SVG anfasst (`getBBox()` prüfen; XML verbietet `--` im Kommentar). |
| **Web 12.4.1 (S3/AP10)** | Kapitel 9.7: neue Regel `.feldsatz-gesperrt` — ein `<fieldset>`, das nur gruppiert, für das `disabled`-Attribut. Die Elementregeln für `fieldset` sind mit O11 gefallen; ohne diese Rücknahme bringt der Browser Rahmen und Polsterung mit. |
| **Web 12.3.3 (S3/AP8)** | Kapitel 9.7: Die Wahlliste ist eine **schlichte Liste** — vier Zeilen mit eigenem Rahmen auf eigener Fläche sahen aus wie vier Karten und sind eine Wahl. Kapitel 9.13: Das Ortsfeld sucht **beim Tippen**, mit drei Grenzen (400 ms, drei Zeichen, eine offene Anfrage) und dem Verweis auf `Lizenzen.md` 6.2. |
| **Web 12.3.1 (S3/AP6)** | Kapitel 9.6: Plakette und Schloss schließen einander **nicht mehr aus** — Ablösung von F-N1-B. Die Plakette sagt „hier stehen verschlüsselte Angaben“, das Schloss sagt „diese hier“. |
| **Web 12.3.0 (S3/AP5)** | Kapitel 9.5: **fünfter Meldungston `schutz`** — rot wie `fehler`, aber `role="status"` und mit dem Schloss statt der Warnung, für einen Datenschutzhinweis, der dauerhaft steht. Dazu die Warnung, dass ein Ton, den es nicht gibt, bis dahin einen ungestalteten Kasten ergab. |
| **Web 12.2.4 (S3/AP4)** | Kapitel 5: Die Schriftskala führte die Leistenüberschrift noch bei 12 px, während das Stylesheet seit P3 13 px setzt — berichtigt und auf `--groesse-3` (15 px) nachgezogen. |
| **Web 12.2.3 (S3/AP3)** | Kapitel 9.9: Die Sammelleiste hat die **Form der Karte** (E-R43-1); Knopf rechts, Zählung links daneben, ausgerichtet über `justify-content` und ausdrücklich nicht über `order`. |
| **Web 12.2.2 (S3/AP1–AP2)** | Neuer Abschnitt „Der vertikale Rhythmus" in Kapitel 6: eine Stufe je Beziehung, mit dem Leitgedanken „Bindung ist kleiner als Trennung", der Abgrenzung Zwischenraum gegen Polsterung und zwei Präzisierungen aus echten Fällen (Überschrift mit Bedienelementen; Zeilen in einem Textblock). Platzhalter-Pflegeregel in 9.7, Querverweis in 9.13. Die Titelzeile (9.8) trägt den Abstand darunter jetzt als begründete Ausnahme. Zwei neue Anti-Muster in 9.16. |
| **Web 9.14.0** | Erste Rückmeldungsrunde nach P3. Neues Token `--symbol-klein` (16 px). Fünf neue Anti-Muster in 9.16, alle aus echten Funden dieser Runde. Kopfleiste: Wortzeichen „Gen-EM Einsatzdoku", Logo 34 px. Segmenttasten ohne geerbten Rand. Neue Regeln: `.symbol-schutz`, `.tagfeld-breit`, `.vehkind`, `.sd-liste`, `.loc-widget`. |
| **Web 9.13.0 (P3/O12)** | Erstfassung. Ersetzt `docs/Branding.md`. Farben, Schriften und Logo-Regeln von dort übernommen; die Abbildung auf CSS-Variablen (dort Abschnitt 1.3, mit `--ink`, `--navy`, `--accent`, `--muted`) ist entfallen — diese Token gibt es seit Web 9.0.0 nicht mehr. Die offenen Punkte B1 (Logo trägt nicht die Markenwerte), B2 (keine geschlossene Größenskala) und B3 (78 Hexwerte) sind **erledigt** und in 2.5, 5 und 6 als solche vermerkt. |
