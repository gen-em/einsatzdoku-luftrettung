# Design — Gestaltungsrichtlinie der Einsatzdokumentation

Verbindliche Quelle für alles Sichtbare der Weboberfläche: Farben, Schriften,
Maße, Bausteine, Symbole, Seitentypen. Sie löst `docs/Branding.md` ab
(P3/O12) und nimmt daraus alles auf, was noch gilt.

**Geltungsbereich:** die Weboberfläche (`server/`). Die Uhr-App hat ihre
eigenen Regeln in `docs/Uhr-Layout_Regeln.md`; wo es um Marke und Logo geht,
gilt Kapitel 2 auch für sie.

> **Die technische Wahrheit steht im Stylesheet.** Was hier als Wert steht,
> ist entweder *erzeugt* (Kapitel 4, 7, 8, 9 — `tools/erzeugen/design.py`
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
2. **`tools/quelltext/vollstaendigkeit.py`** — keine Hexwerte außerhalb `:root`,
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
> Platzhalterbild, und `tools/erzeugen/logos.mjs` fotografierte es früher
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

### 2.5 Das Logo trägt die Markenwerte — zurzeit nicht (Backlog Nr. 62)

Der offene Punkt B1 aus `docs/Branding.md` war mit P3/O12 **erledigt**: Dort
war festgehalten, dass die Logodateien von den Markenwerten abwichen (rotes
Rotorblatt `#E3322B` statt `#D63338`, blaues `#587ABC` statt `#4280E5`,
oranges `#F7941D` statt `#FF8F1F`, Rumpf `#1D0E0A` statt `#1A0500`), und
nachgemessen in `gen-em_logo_helicopter.svg` stimmten `#1A0500`, `#4280E5`,
`#D63338`, `#FF8F1F`.

**Das gilt nicht mehr.** Der Commit „Update Logos" hat mit neuen
Vektorvorlagen die alten Werte zurückgebracht (Backlog Nr. 62, aufgenommen
31.08.2026). Nachgemessen am 13.09.2026: `gen-em_logo_helicopter.svg` führt
`#587ABC`, `#E3322B`, `#F7941D` und Korpus `#1D0E0A`;
`gen-em_logo_helicopter_weiss.svg` trägt dieselben alten Farbelemente;
`gen-em_logo_nef.svg` den alten Korpuswert. **Die PNG-Fassungen sind
dieselbe Lage** (nachgemessen am 13.09.2026, Bildpunkte dekodiert):
`gen-em_logo_helicopter.png` und `gen-em_logo_helicopter_weiss.png` tragen
die alten Farbwerte — um ein bis zwei Stufen je Kanal verschoben, weil sie
gerastert sind (`#1C0B0B`, `#E4302C`, `#577ABC`) —, `gen-em_logo_nef.png`
den alten Korpuswert. **Richtig sind allein die beiden Fassungen ohne
Korpus:** `gen-em_logo_nef_weiss.svg` und `gen-em_logo_nef_weiss.png`. Es
gibt keine korrigierte Quelle.
**Entschieden am 12.09.2026: neue Vorlagen in den Markenfarben anfordern**
(Rahmenplan Abschnitt 6, Zuarbeit); bis sie vorliegen, bleibt dieser Absatz
so stehen, damit hier keine falsche Zusage steht. Nach der Behebung alle
Fassungen samt Ableitungen (PNG, Favicons, Uhr-Bilder) nachmessen und den
Absatz zurückdrehen.

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

**Orange heißt seit Web 19.6.0 auch „hier ist gerade etwas offen"** (E-MR-21,
E-MR-25). Ein Öffner, dessen Blatt offen steht, trägt `--orange-hell` mit
`--orange-tief` darauf — dieselbe Sprache wie die aktive Kennzahl und das
angesprungene Sprungziel. Das ist keine zweite Bedeutung neben „hier wird
gehandelt", sondern deren Fortsetzung: Der Knopf **ist** gerade die Handlung,
und das Blatt darunter gehört zu ihm. Die Regel steht am Attribut
(`[data-blatt][aria-expanded="true"]`) und gilt damit für alle vier Bauarten
von Öffnern — auch für die nächste.

### 3.2 Warum es je drei Töne gibt

Jede Kernfarbe kommt dreifach vor, und die drei sind nicht austauschbar:

| Endung | Rolle | Beispiel |
|---|---|---|
| — (`--orange`) | **Fläche und Strich.** Nie Schrift. | Primärknopf, aktiver Rand |
| `-tief` | **Schrift.** Blau und Rot tief erreichen 4,5:1 auf Schnee; **Orange tief 4,32:1** — als Schrift nur groß oder fett (3.4). | Textlink, Fehlertext |
| `-hell` | **Fläche unter Schrift.** | Meldung, Plakette |

Das ist der Fund F-P3-J: Orange erreicht auf Schnee 2,2:1 und ist als Schrift
unbenutzbar; die Marke gibt aber keinen dunkleren Ton her. Statt den
Markenwert zu ändern, bekommt jede Farbe eine dunkle Textfassung
(`--orange-tief` `#C25A00`, `--blau-tief` `#1F4E9C`, `--rot-tief` `#9E2226`).

*Bis R4-08 stand in der Tabelle „dunkel genug für 4,5:1 auf Schnee" für
alle drei. Für Orange tief stimmte das nie (4,32:1); das Werkzeug rechnete
das Paar ohnehin mit 3,0 als „nur groß oder fett". Die Ableitung aus dem
Stylesheet fand dazu die weiße Schrift auf Orange tief im Hover des
Primärknopfs (4,42:1 bei 15 px); die BetreiberIn hat entschieden, dass der
Ton passt, und keine Farbe geändert (E-R4-30).*

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
| Orange tief auf Rauch | 4,04:1 | 3,0 | nur groß oder fett; Strich des aktiven Reiters (9.37) |
| Orange tief auf Orange hell | 3,81:1 | 3,0 | Warnung, Auftakt fett |
| Rot auf Schnee | 4,68:1 | 3,0 | Gefahrknopf: Rand und Schrift ab 18 px |
| Blau als Fokusring | 3,77:1 | 3,0 | Rand |
| Linie stark auf Schnee | 5,66:1 | 3,0 | Rand von Bedienelementen |
| Linie stark auf Rauch | 5,30:1 | 3,0 | Rand von Bedienelementen |
| Dunkelblau auf Orange hell | 11,74:1 | 4,5 | aktives Sprungziel, aktive Kennzahl |
| Weiß auf Rot | 4,78:1 | 4,5 | Kopfleiste einer Anlage mit Etikett (9.36) |
| Schnee auf Rot | 4,68:1 | 4,5 | Name in dieser Kopfleiste |
| Orange hell auf Rot | 4,12:1 | 3,0 | Strich des aktiven Kopfpunkts auf Rot |
| Asphalt auf Sand | 11,80:1 | 4,5 | Nummer eines offenen Schritts (Erststart) |
| Blau auf Blau hell | 3,19:1 | 3,0 | Strich am Zitat im Handbuch |
| Blau tief auf Rauch | 7,32:1 | 4,5 | Übersichtszeile der Einstellungen unter dem Zeiger |
| Dunkelblau auf Orange | 5,97:1 | 4,5 | oranger Zähler, Zeichen im Einsatzort-Kreis |
| Dunkelblau auf Sand | 8,15:1 | 4,5 | neutraler Zähler |
| Primärschrift auf Orange | 5,97:1 | 4,5 | Zahl des aktiven Filters, gewählte Segmenttaste |
| Rauch auf Dunkelblau | 12,48:1 | 4,5 | Fußzeile auf dunklem Grund |
| Rot auf Rosa | 3,87:1 | 3,0 | Rahmen der roten Kennzahl |

**33 Paare, 0 verfehlt** (Web 21.1.6). **Seit R4-08 ist die Liste nicht mehr
das Maß:** `kontrast.py` leitet die Paare aus den Regeln des Stylesheets ab
(Vorder- und Hintergrund in derselben Regel, dazu jede Farbe, die allein
steht) und meldet jedes, das weder hier noch unter den Ausnahmen steht —
Backlog Nr. 116. Der erste Lauf fand **14**: die acht Paare oben, die ihren
Sollwert erreichen, den Strich am Vorschlag (2,09:1 — seither Orange tief)
und fünf, die jetzt als Ausnahme stehen. Was die Ableitung nicht sieht: eine
bekannte Schrift auf einer neuen Fläche, die eine Elternregel setzt. Hier
standen bis Web 20.38.0 21 Paare, bis R4-08 25.

**Acht Ausnahmen, jede mit Grund** — sie stehen im Werkzeug selbst, damit
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
- **Weiß auf Orange tief (4,42:1)** im Hover des Primärknopfs — 15 px,
  Gewicht 600, also normale Schrift. Die BetreiberIn hat den Ton angenommen
  (E-R4-30); in Ruhe steht der Knopf mit 5,97:1 da.
- **Linie auf Rauch (1,27:1).** Rahmen von Codeblock, `<pre>` und
  Tagesgruppe des Imports — Zierrat wie „Linie auf Schnee".
- **Orange als Strich auf Orange hell (1,97:1).** Aktiver Leisteneintrag,
  aktive Kennzahl, Kennzahl im Ton orange: Der Strich begleitet die Fläche
  und trägt den Zustand nicht allein — dieselbe Sprache wie oben.
- **Orange tief auf Orange (1,93:1).** Rand des hervorgehobenen Phasenpunkts
  auf der Karte; die orange Fläche ist das Zeichen.
- **Spurfarbe als Farbschlüssel.** Die Linie der Legende zeigt die Farbe der
  Spur; was sie bedeutet, sagt die Beschriftung daneben.

---

## 4. Token

**Diese Tabelle ist erzeugt, nicht abgeschrieben.**
`python3 tools/erzeugen/design.py token` liest sie aus `:root` in
`server/assets/style.css`. Der Grund ist derselbe wie bei jeder
abgeschriebenen Zahl: Sie stimmt am Tag des Abschreibens und danach nie
wieder — und eine Gestaltungsrichtlinie, deren Farbwerte von denen der
Anwendung abweichen, ist schlimmer als keine, weil man ihr glaubt.

Die Gliederung stammt ebenfalls aus dem Stylesheet: Es ordnet seinen
`:root`-Block mit Kommentarzeilen der Form `---- Flächen ----`, und das
Werkzeug übernimmt sie, statt eine zweite Gliederung danebenzustellen, die
auseinanderlaufen kann.

<!-- ERZEUGT von tools/erzeugen/design.py — nicht von Hand ändern. -->

101 Token in 15 Gruppen, alle aus `:root` in `server/assets/style.css`. Die Spalte **benutzt** zählt die `var()`-Verweise im übrigen Stylesheet.

**Flächen**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--schnee` | `#FFFCFA` | 37 |  |
| `--rauch` | `#F7F5ED` | 39 |  |
| `--sand` | `#D4C7AD` | 15 |  |

**Schrift**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--asphalt` | `#1A0500` | 24 |  |
| `--dunkelblau` | `#1A2E4D` | 64 |  |
| `--gedaempft` | `#6E6459` | 71 |  |
| `--auf-dunkel` | `#FFFFFF` | 10 | Schrift auf Dunkelblau, 13,62:1 |

**Linien**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--linie` | `#E3DAC6` | 41 |  |
| `--linie-stark` | `var(--gedaempft)` | 12 |  |

**Orange — Handeln**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--orange` | `#FF8F1F` | 30 |  |
| `--orange-tief` | `#C25A00` | 18 |  |
| `--orange-hell` | `#FFEBD6` | 23 |  |

**Blau — Auswählen und Erklären**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--blau` | `#4280E5` | 14 |  |
| `--blau-tief` | `#1F4E9C` | 18 |  |
| `--blau-hell` | `#D9ECFD` | 6 |  |

**Rot — Aufmerksamkeit**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--rot` | `#D63338` | 14 |  |
| `--rot-tief` | `#9E2226` | 19 |  |
| `--rosa` | `#FCE2D6` | 7 |  |

**Primärknopf**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--knopf-primaer-flaeche` | `var(--orange)` | 2 |  |
| `--knopf-primaer-schrift` | `var(--dunkelblau)` | 3 |  |

**Schriftskala**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--groesse-1` | `12px` | 9 |  |
| `--groesse-2` | `13px` | 56 |  |
| `--groesse-3` | `15px` | 15 |  |
| `--groesse-4` | `16px` | 16 |  |
| `--groesse-5` | `19px` | 12 |  |
| `--groesse-6` | `24px` | 5 |  |
| `--groesse-titel` | `28px` | 2 |  |
| `--zeile-eng` | `1.3` | 5 | Titel, Kacheln |
| `--zeile` | `1.55` | 3 | Oberfläche |
| `--zeile-lesen` | `1.6` | 4 | Fließtext in der Lesespalte |

**Abstände**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--abstand-1` | `4px` | 81 |  |
| `--abstand-2` | `8px` | 117 |  |
| `--abstand-3` | `12px` | 158 |  |
| `--abstand-4` | `16px` | 65 |  |
| `--abstand-5` | `24px` | 35 |  |

**Radien**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--radius-klein` | `6px` | 29 | Plakette, Kästchen, Eingabefeld |
| `--radius` | `10px` | 20 | Knopf, Meldung |
| `--radius-gross` | `12px` | 8 | Karte, Blatt, Dialog |

**Maße**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--kopf` | `56px` | 12 |  |
| `--knopf` | `44px` | 45 |  |
| `--leiste` | `260px` | 2 | Seitenleiste ab 1200 |
| `--leiste-schmal` | `220px` | 2 | Seitenleiste 1024–1199 |
| `--leiste-filter` | `280px` | 1 | Filterleiste der Suche ab 1200 |
| `--leiste-filter-schmal` | `240px` | 1 | Filterleiste 1024–1199 |
| `--rahmen` | `1680px` | 3 | Leiste und Inhalt als Einheit |
| `--lesespalte` | `760px` | 5 | Fließtext |
| `--schublade` | `320px` | 1 | Höchstbreite der mobilen Schublade |
| `--blatt-zeile` | `50px` | 1 | Zeilenhöhe im Aktionsblatt |
| `--unterpunkt` | `28px` | 2 | Sprungmarke unter dem Menüpunkt |
| `--listensuche-breit` | `36rem` | 1 | Höchstbreite des Suchfelds einer Liste |
| `--uebersicht-spalte` | `240px` | 1 | schmalste Spalte der Einstellungs-Übersicht |
| `--suchfeld` | `48px` | 2 | das große Suchfeld |
| `--symbol-klein` | `16px` | 6 | Zusatzzeichen an einer Beschriftung |
| `--symbol-winzig` | `calc(var(--symbol-klein) - var(--abstand-1))` | 4 | 12 px, im Chip |
| `--ziel-chip` | `calc(var(--symbol-gross) + var(--abstand-1))` | 2 | 28 px, Treffziel |
| `--symbol-text` | `1em` | 2 | Symbol im Fliesstext |
| `--symbol` | `20px` | 13 | Symbolgröße in der Zeile |
| `--symbol-gross` | `24px` | 14 | Symbolgröße im Knopf und Kartenkopf |
| `--strich` | `1px` | 57 | Haarlinie |
| `--strich-stark` | `2px` | 36 | Aktivstrich, Randstrich, Fokus |
| `--radius-rund` | `999px` | 20 | Zähler, Griff, Punkt — voll rund |
| `--schalter-breit` | `46px` | 2 | der Schalter aus E-P3-28 … |
| `--schalter-hoch` | `26px` | 4 | … 26 hoch, damit er in eine |
| `--schalter-punkt` | `20px` | 4 | 44-px-Zeile passt und greifbar bleibt |
| `--geo-kreis` | `28px` | 2 | Einsatzort-Kreis auf der Karte |
| `--geo-schild` | `30px` | 2 | Kästchen für Standort und Zielklinik |
| `--geo-ring` | `3px` | 6 | Randstärke des Farbrands Start/Ende |
| `--geo-ringpunkt` | `calc(var(--abstand-4) - var(--strich-stark))` | 2 | Ring ohne Schild (14 px) |
| `--geo-symbol` | `calc(var(--symbol) - var(--strich-stark))` | 2 | Symbol im Kartenschild (18 px) |
| `--balken` | `8px` | 1 | Höhe des Speicherbalkens |
| `--logo-kachel` | `var(--kopf)` | 2 | Vorschau-Kachel der Installation … |
| `--balken-punkt` | `10px` | 2 | Farbpunkt in seiner Legende |
| `--anmeldekarte` | `400px` | 3 | Karte der Anmeldung (E-P3-38) |
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
| `--karte-gross` | `min(60vh, 520px)` | 2 |  |

**Spurfarben (P3/O3, E-P3-40)**

| Token | Wert | benutzt | |
|---|---|--:|---|
| `--spur-1` | `var(--orange)` | 1 |  |
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
| `--dauer` | `.24s` | 9 |  |
| `--schleier` | `rgba(26,46,77,.55)` | 2 | Dunkelblau, halbdurchsichtig |
| `--schatten` | `0 2px 8px rgba(26,5,0,.10)` | 8 |  |
| `--schatten-hoch` | `0 8px 28px rgba(26,5,0,.22)` | 3 |  |
| `--auf-dunkel-leise` | `rgba(255,255,255,.55)` | 2 |  |
| `--auf-dunkel-flaeche` | `rgba(255,255,255,.14)` | 3 |  |
| `--auf-dunkel-strich` | `rgba(255,255,255,.35)` | 1 |  |

**Ungenutzt:** `--spur-2`, `--spur-3`, `--spur-4`, `--spur-5`, `--spur-6`, `--spur-7`, `--spur-8`, `--spur-ruhe`, `--s-handy`, `--s-leiste`, `--s-zwei`, `--s-karte-neben`.

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
Bilderlauf misst jedes `.knopf` in allen zehn Breiten **gegen den Sollwert der
emulierten Eingabeart**; Abweichung ist ein Fehler, kein Geschmack.

**Der Fokusring ist sichtbar und liegt an der richtigen Stelle.** Zwei
Pixel Blau mit Abstand. Wo ein Bedienelement aus einem *ausgeblendeten*
Eingabefeld und einer sichtbaren Beschriftung gebaut ist (Schalter, Segment,
Wahlliste), gehört der Ring an die **Beschriftung** — am unsichtbaren Feld
wäre die Tastaturbedienung unsichtbar.

**Nichts läuft waagerecht aus dem Bild.** Auf keiner Seite und in keiner
Breite darf `scrollWidth > innerWidth` gelten. Was breit ist, scrollt in
seinem eigenen Behälter (`.tabelle-scroll`) oder wird zur Kachel.

**Bewegung ist kurz und einheitlich** (`--dauer` **.24 s**, bis Web 19.5.1
.18 s) — und wer sie abbestellt hat (`prefers-reduced-motion`), bekommt keine.
Der Wert ist mit dem Aktionsblatt gewachsen (E-MR-22): Eine Auffahrt aus der
unteren Bildkante war bei 180 ms eher ein Aufblitzen als eine Bewegung. Er
gilt für **alle** Nutzer des Tokens; ein zweiter Wert nur fürs Blatt wäre die
Stelle, an der die Anwendung anfängt, verschieden schnell zu sein.

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

### Erklärtext: ein Satz je Karte, der Rest steht im Handbuch

*Seit Web 21.1.0 (P5c/AP9, E-P5c-06, -49, -128).* Wer eine Seite zum
zehnten Mal öffnet, liest den Text darauf nicht mehr und muss trotzdem daran
vorbei. Deshalb gilt unter **Verwaltung** und **Betrieb**:

- **Je Karte höchstens ein Satz**, der sagt, was hier passiert — als
  `<p class="feld-hinweis">`, dahinter der Verweis ins Handbuch
  (`<a href="hilfe.php#…">`) auf die Stelle, an der das Erklärende steht.
  Die Sprungmarke ist die, die `doku_marke()` aus der Überschrift bildet; die
  Ankerprüfung im Tor hält jeden Verweis dagegen.
- **Der Seitenkopf** (`ui_titelzeile(['unter' => …])` oder
  `<p class="seiten-erklaerung">`) trägt höchstens einen Satz.
- **Eine Kleinzeile bleibt eine Zeile** — unter einem Feld wie unter einer
  Zeile (`'klein'`): höchstens ein Satz.
- **Warnungen bleiben Meldungen** (`ui_meldung()`), keine Absätze.
- **Keine Karte „Was hier gilt"** mehr (bis Web 21.0.0 nach R74 (5) die
  zugeklappte Karte am Seitenende). Ihr Inhalt steht im Handbuch am Ende des
  Kapitels, und jede Karte der Seite verweist auf ihre Stelle.

Ausgenommen sind, weil sie Zustand und keine Erklärung sind: der Satz eines
**Leerzustands** („Zurzeit keine."), die **Befundzeilen** der Statusseite
(`status_erhebung()`, `plattform_pruefen()`), Meldungen, Dialoge und
Kopierwerte. Der Bereich **Einstellungen** folgt der Regel noch nicht
(E-P5c-06); drei Seiten außerhalb tragen die Karte „Was hier gilt" bis
Schritt 17 (Nr. 287).

**Gezählt wird so** (E-P5c-128): S = fester Kartentext in Sätzen, Sb_max =
die meisten bedingten Sätze, die gleichzeitig sichtbar sind; Soll
**S + Sb_max ≤ 1**. Ein Satz endet mit `.`, `!` oder `?`; ein Stück ohne
Satzzeichen zählt ab vier Wörtern. Kartentitel, Vorschau, Zeilentitel,
Beschriftungen, Zwischenüberschriften, Link- und Knopftexte sind nie Sätze.
Belegt wird die Zählung mit einer unabhängigen Gegenprobe.

### Text, den niemand im Repositorium kontrolliert, bricht selbst um

**Regel:** Ein Baustein, dessen Inhalt aus der **Datenbank** oder aus einer
**Markdown-Datei** kommt, bringt seinen Umbruch selbst mit. Wer ihn baut,
darf nicht voraussetzen, dass der Text umbruchfreundlich ist.

| Baustein | Inhalt kommt aus | Regel |
|---|---|---|
| `.text` | `rechtstexte` (Datenbank) | `overflow-wrap:break-word` |
| `.doku-text code` | `docs/*.md` (Repositorium) | `overflow-wrap:anywhere` |
| `.doku-text table` | dieselbe | rollt waagerecht in sich |

**Warum zwei verschiedene Härten.** Beide brechen ein Wort, das sonst
überliefe. `anywhere` senkt zusätzlich die **intrinsische Mindestbreite** —
in einer Karte mit `max-width` fängt der Absatz dann an, auch dort zu
brechen, wo er es nicht müsste. Für Fließtext ist das falsch, für eine
Festbreitenschrift, die ohnehin nicht schön umbricht, ist es richtig.

> **Woher die Regel kommt** (Backlog Nr. 221, Web 20.24.2): Die
> Datenschutzerklärung und die Nutzungsbedingungen schoben sich bei 360 px um
> **127 px** nach rechts. Verursacher waren eine Mailadresse und zwei
> Adressen im Fließtext — gemessen als `<p>` mit `scrollWidth` 350 gegen
> `clientWidth` 302.
>
> Der Text stammt von der **BetreiberIn**, nicht aus dem Repositorium. Sie
> soll eine Adresse hinschreiben dürfen, ohne zu wissen, wie breit ein Handy
> ist. Die Regel gehört deshalb an den Baustein und nicht in eine Anleitung
> für das Schreiben von Rechtstexten.

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
| **1024** | Schublade → feste Leiste; die Hauptpunkte wandern in die Kopfleiste; das Aktionsblatt wird ein Aufklappmenü; im Band bis 1199 px zeigt der Nebentext der Leiste **nur noch einen Kurznamen** (`.eintrag-neben.kurz`), das Akkordeon rückt je Ebene **4 statt 12 px** ein und der Abstand in der Diensttagszeile geht von 8 auf 4 px |
| **1200** | Leiste 260 px (Filterleiste 280), Zweispalter: Einsatzansicht, Formularkarten, Kontoseite; der Nebentext der Leiste steht wieder für **jeden** Namen, Akkordeon-Einrückung und Zeilenabstand gehen auf 12 bzw. 8 px zurück |
| **1600** | Die Karte steht neben Diensttag-Daten und Tabelle |

Dazu **eine** Ausnahme nach unten: `@media (max-width:479px)` lässt in der
Wahlliste den Zusatz unter den Text rutschen — „zurzeit Hubschrauber (RTH)"
neben „Standard der Installation" sprengt sonst jede Zeile.

Und **eine Höhenschwelle** (seit Web 21.1.0, E-P5c-131; seit 21.1.3 bei
950 statt 800 px, F-P5c-164): `@media (min-width:1024px) and
(max-height:949px)` nimmt die Sprungmarken aus der festen Leiste (9.25).
Sie ist keine Stufe der Breitenskala und steht deshalb nicht in `--s-*`;
die erzeugte Tabelle führt sie als eigene Zeile, und die Summe darunter
zählt nur Breiten.

<!-- ERZEUGT von tools/erzeugen/design.py — nicht von Hand ändern. -->

| Abfrage | Regelblöcke |
|---|--:|
| `@media (min-width:1600px)` | 3 |
| `@media (min-width:1200px)` | 5 |
| `@media (hover: hover) and (pointer: fine) and (min-width:1024px)` | 1 |
| `@media (min-width:1024px) and (max-height:949px)` | 1 |
| `@media (min-width:1024px)` | 3 |
| `@media (min-width:720px)` | 14 |
| `@media screen and (min-width:720px)` | 1 |
| `@media (max-width:479px)` | 1 |

Zusammen 29 Medienblöcke über 5 verschiedene Breiten: 479 px, 720 px, 1024 px, 1200 px, 1600 px.

### Verhalten je Baustein

| Baustein | < 720 | 720–1023 | 1024–1199 | 1200–1599 | ≥ 1600 |
|---|---|---|---|---|---|
| Kopfleiste | Menüknopf, Logo, Zahnrad | wie < 720 | Hauptpunkte sichtbar | wie 1024 | wie 1024 |
| Leiste / Schublade | Schublade | Schublade | Leiste 220 | Leiste 260 | Leiste 260 |
| Nebentext der Leiste | jeder Name | jeder Name | **nur Kurznamen** | jeder Name | jeder Name |
| Akkordeon-Einrückung je Ebene | 12 px | 12 px | **4 px** | 12 px | 12 px |
| Abstand in der Diensttagszeile | 8 px | 8 px | **4 px** | 8 px | 8 px |
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
| Kennzahlen `.kennzahl-raster-3` | **3** | **3** | **3** | **3** | **3** |
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

<!-- ERZEUGT von tools/erzeugen/design.py — nicht von Hand ändern. -->

| Datei | Herkunft (Tabler-Name) | Nennungen im Code |
|---|---|--:|
| `abmelden.svg` | Tabler Icons „logout" (MIT) | 2 |
| `aktualisieren.svg` | Tabler Icons „refresh" (MIT) | 1 |
| `balken.svg` | Tabler Icons „chart-bar" (MIT) | 3 |
| `bergwacht.svg` | Tabler Icons „mountain" (MIT) | 32 |
| `datenbank.svg` | Tabler Icons „database" (MIT) | 11 |
| `drucken.svg` | Tabler Icons „printer" (MIT) | 3 |
| `einsatzort.svg` | Tabler Icons „map-pin-plus" (MIT) | 1 |
| `fahrzeug.svg` | Tabler Icons „ambulance" (MIT) | 21 |
| `geraet-entkoppeln.svg` | Tabler Icons „link-off" (MIT) | 1 |
| `gruppe.svg` | Tabler Icons „users" (MIT) | 22 |
| `haken.svg` | Tabler Icons „check" (MIT) | 38 |
| `haus.svg` | Tabler Icons „home" (MIT) | 4 |
| `hilfe.svg` | Tabler Icons „help-circle" (MIT) | 3 |
| `hinweis.svg` | Tabler Icons „info-circle" (MIT) | 37 |
| `hubschrauber.svg` | Tabler Icons „helicopter" (MIT) | 23 |
| `kalender.svg` | Tabler Icons „calendar" (MIT) | 4 |
| `karte-breit.svg` | Tabler Icons „arrows-horizontal" (MIT) | 1 |
| `karte-gross.svg` | Tabler Icons „arrows-vertical" (MIT) | 1 |
| `karte.svg` | Tabler Icons „map-2" (MIT) | 15 |
| `klinik.svg` | Tabler Icons „building-hospital" (MIT) | 3 |
| `kolben.svg` | Tabler Icons „flask" (MIT) | 3 |
| `korb.svg` | Tabler Icons „trash" (MIT) | 22 |
| `luftlinie.svg` | — | 0 |
| `lupe.svg` | Tabler Icons „search" (MIT) | 13 |
| `mail.svg` | Tabler Icons „mail" (MIT) | 15 |
| `menu.svg` | Tabler Icons „menu-2" (MIT) | 1 |
| `ohne-zuordnung.svg` | Tabler Icons „circle-dashed" (MIT) | 2 |
| `ordner-plus.svg` | Tabler Icons „folder-plus" (MIT) | 1 |
| `pfeil-hoch.svg` | Tabler Icons „arrow-up" (MIT) | 6 |
| `plus.svg` | Tabler Icons „plus" (MIT) | 22 |
| `position.svg` | Tabler Icons „current-location" (MIT) | 5 |
| `profil.svg` | Tabler Icons „user" (MIT) | 18 |
| `protokoll.svg` | Tabler Icons „list" (MIT) | 20 |
| `punkte.svg` | Tabler Icons „dots" (MIT) | 32 |
| `reanimation.svg` | Tabler Icons „activity" (MIT) | 0 |
| `rechtstexte.svg` | Tabler Icons „file-text" (MIT) | 2 |
| `schliessen.svg` | Tabler Icons „x" (MIT) | 14 |
| `schloss-offen.svg` | Tabler Icons „lock-open" (MIT) | 5 |
| `schloss.svg` | Tabler Icons „lock" (MIT) | 21 |
| `server.svg` | Tabler Icons „server" (MIT) | 9 |
| `sicherung.svg` | Tabler Icons „archive" (MIT) | 21 |
| `sonstiges.svg` | Tabler Icons „dots-circle-horizontal" (MIT) | 78 |
| `sortieren.svg` | Tabler Icons „arrows-sort" (MIT) | 4 |
| `standort.svg` | Tabler Icons „map-pin" (MIT) | 26 |
| `status.svg` | Tabler Icons „activity" (MIT) | 49 |
| `stern.svg` | Tabler Icons „star" (MIT) | 7 |
| `stift.svg` | Tabler Icons „pencil" (MIT) | 8 |
| `tausch.svg` | Tabler Icons „arrows-exchange" (MIT) | 12 |
| `uhr.svg` | Tabler Icons „device-watch" (MIT) | 277 |
| `uhrzeit.svg` | Tabler Icons „clock" (MIT) | 3 |
| `veranstaltung.svg` | Tabler Icons „ticket" (MIT) | 11 |
| `vollbild.svg` | Tabler Icons „maximize" (MIT) | 1 |
| `warnung.svg` | Tabler Icons „alert-triangle" (MIT) | 29 |
| `werkzeug.svg` | Tabler Icons „tool" (MIT) | 0 |
| `winkel.svg` | Tabler Icons „chevron-down" (MIT) | 19 |
| `zahnrad.svg` | Tabler Icons „settings" (MIT) | 2 |
| `ziel-fern.svg` | Tabler Icons „cloud-upload" (MIT) | 1 |
| `zurueck.svg` | Tabler Icons „arrow-left" (MIT) | 32 |

58 Dateien in `server/assets/images/symbole/`, dazu `LICENSE-tabler-icons.txt` und `LIESMICH.md`.
**Nirgends genannt:** `luftlinie`, `reanimation`, `werkzeug`.

## 9. Bausteine

Alle in `server/ui.php`. **Der Vorrat ist die Antwort auf die Freigaberegel:**
Wer eine Seite baut, sucht hier, statt etwas Neues zu erfinden.

> **Wo die freigegebenen Bilder liegen.** Die Mockups, die unten als Herkunft
> genannt sind, liegen neben ihrem Konzept unter `docs/konzepte/konzept-…/`.
> **M-P5c-01a bis -01f, M-P5c-02a bis -02e und M-RW-01 sind mit dem
> Abschluss von P5c gelöscht** (25.09.2026, Q-P5c-54) — sie stehen in der
> Git-Historie, letzter Stand `ae829e6` (`docs/konzepte/konzept-p5c/`,
> `docs/konzepte/konzept-rw/`).

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
| der NutzerIn etwas sagen | `ui_meldung()` / `ui_meldung_markup($ton, …)` | ein `<p>` in Rot |
| einen Zustand an einer Zeile zeigen | `ui_plakette($text, ['ton' => …])` | ein farbiges Wort |
| eine Zahl groß zeigen | `ui_kennzahl()` | eine Überschrift mit Zahl |
| ein Eingabefeld mit Beschriftung | `ui_feld()` | `<label>Text <input></label>` |
| ein Ja/Nein | `ui_schalter()` | ein Kästchen |
| eine aus **wenigen kurzen** Möglichkeiten | `ui_segment()` | ein `<select>` |
| eine aus mehreren **mit Erklärung** | `ui_wahlliste()` | Radios von Hand |
| eine Adresse mit Koordinaten | `ui_ortsfeld()` | ein Textfeld |
| **Vorschläge zu einem Textfeld** | `EdVorschlaege.init()` (9.28) | eine `<datalist>` — der Browser zeichnet sie über dem Feld, mobil oft gar nicht |
| einen Seitenkopf mit Rückweg | `ui_titelzeile()` | ein `<h1>` |
| „gibt es nicht" / „kein Zugriff" | `ui_abbruch($code, $text)` | `exit('… nicht gefunden.')` |
| ein Zeichen | `ui_symbol('name')` / `edSymbol('name')` | ein Emoji, ein Unicode-Zeichen, ein Inline-Pfad |
| die Art eines Diensttags zeigen | `ui_artzeichen($kind)` | ein Wort oder ein Emoji |
| einen Hinweis unter einem Feld | `<p class="feld-klein">` | `<small>` |
| einen Hinweis vor einem Feld | `<p class="feld-hinweis">` | dasselbe wie oben |
| einen Zusatz **in** einer Beschriftung | `<span class="feld-klein-inline">` | Klammern im Beschriftungstext |
| einen Satz oben auf der Seite | `ui_titelzeile(['unter' => …])` oder `<p class="seiten-erklaerung">` — **höchstens einen Satz** (Kapitel 6, Erklärtext) | zwei Absätze Vorrede |
| erklären, **wie** etwas funktioniert | ein Absatz im Handbuch am Kapitelende, in der Karte ein Satz mit Verweis `hilfe.php#…` (Kapitel 6, Erklärtext) | eine Karte „Was hier gilt", mehrere Absätze in der Karte |
| einen Knopf am Ende eines Formulars | `ui_knopf()` in `<div class="listen-form-fuss">` | einen blanken `<button>` |
| einen **Wert zum Abschreiben oder Kopieren** (Kennung, Schlüssel, Prüfsumme, Adresse) | `ui_codeblock_lang()` — beide Stufen und wann welche: 9.18 | ein `<code>` im Fließtext |
| eine **Liste von Einmalcodes** zeigen oder einen **Code eintippen** lassen | `.codeblock-liste` im Codeblock, `ui_feld([… 'klasse' => 'feld-code'])`: 9.38 | eine Tabelle, ein Feld in Normalschrift |
| eine Seite, die **gedruckt** wird (genau eine A4-Seite) | `.blatt-druck`: 9.39 | das Gerüst mit `@media print` |
| eine **Füllung gegen eine Grenze** zeigen | `.speicher-balken` mit seinen drei Schwellen (9.19) | ein `<progress>` oder ein eigener Balken |
| eine **Zahl an einem Menüpunkt** („hier ist etwas zu tun") | `ui_zaehler()` — drei Töne wie die Ampel (9.25) | eine Zahl in Klammern hinter dem Text |
| mehrere Karten **nebeneinander** | einen der drei Wege aus 9.26 — und lies dort erst, welcher | ein eigenes Raster je Seite |
| **Sprungmarken** von Karte zu Karte | die Unterpunkte der Einstellungsleiste; sie entstehen von selbst aus den Karten mit `id` (9.25) | ein Inhaltsverzeichnis von Hand |
| ein **Inhaltsverzeichnis am Seitenkopf**, wo es keine Leiste gibt (Handy) | `ui_kennzahl(['href' => '#k-…'])` in `.kennzahl-raster-3` (9.10) | Pillen, Knöpfe oder eine eigene Liste |
| in eine **lange Liste** hineinspringen | `ui_sprungliste()` — ab sechs Einträgen, und nur wo die Einträge ein Zeichen tragen (9.31) | dieselben Namen ein zweites Mal aufzählen |
| eine **lange Liste durchsuchen** | `ui_kartenfilter()` — ab sechs Einträgen, filtert im Browser (9.32) | ein zweites `.suchfeld` (das ist 48 px hoch und gehört der Seitensuche) |
| an das **Ende eines langen Abschnitts** einen Rückweg | `ui_nach_oben()` (9.33) | einen gedämpften Textverweis ohne `.knopf` |
| einen **Streifen über dem Inhalt**, der auf jeder Seite gilt (Umgebung, Ankündigung, Demo, Datenschutz) | `ui_hinweise()` — die eine Reihe mit fester Reihenfolge (9.36) | einen weiteren Streifen unter der Kopfleiste oder einen eigenen Aufruf in der Seite |
| zwischen **gleichrangigen Sichten einer Seite** wechseln | `ui_reiter()` — serverseitig, jeder Reiter ein Verweis (9.37) | ein Segment mit sieben Wörtern oder eine zweite Reihe Filterpillen |
| **Angaben zu einem Listeneintrag**, die nicht in den Satz passen | `ui_zeile(['daten' => [[Schlüssel, Wert], …]])` — die Zeile klappt auf (9.2) | ein Blatt mit „⋯“ oder eine zweite Zeile darunter |
| **Suche, Filter und Seitenwahl** einer langen Liste | `ui_listenkopf()` und `ui_listenfuss()` (9.18a) | dasselbe Markup ein zweites Mal von Hand |

<!-- ERZEUGT von tools/erzeugen/design.py — nicht von Hand ändern. -->

| Baustein | Klasse | Regel im Stylesheet | `ui.php` |
|---|---|---|--:|
| `ui_seite_start()` | — | Hüllenfunktion, kein eigenes Element | 54 |
| `ui_seite_ende()` | — | Hüllenfunktion, kein eigenes Element | 164 |
| `ui_favicon()` | — | Hüllenfunktion, kein eigenes Element | 231 |
| `ui_symbol()` | `.symbol` | ja (+10 Unterklassen) | 284 |
| `ui_logo_masse()` | `.logo-masse` | **keine** | 387 |
| `ui_kopf()` | `.kopf` | ja (+25 Unterklassen) | 449 |
| `ui_geruest_start()` | `.inhalt` | ja | 541 |
| `ui_leiste_ende()` | `.leiste` | ja (+17 Unterklassen) | 612 |
| `ui_geruest_ende()` | `.inhalt` | ja | 636 |
| `ui_leiste_diensttage()` | `.leiste-liste` | ja | 686 |
| `ui_zaehler()` | `.zaehler` | ja (+2 Unterklassen) | 915 |
| `ui_leiste_einstellungen()` | `.leiste-liste` | ja | 1076 |
| `ui_einstellungen_uebersicht()` | `.uebersicht-raster` | ja | 1148 |
| `ui_fuss_seite()` | `.fuss-seite` | ja | 1235 |
| `ui_hinweise()` | `.hinweise` | ja | 1283 |
| `ui_umgebung_hinweis()` | `.hinweis-umgebung` | ja | 1311 |
| `ui_ankuendigung()` | `.meldung-ankuendigung` | ja | 1346 |
| `ui_demo_hinweis()` | `.demo-hinweis` | ja | 1399 |
| `ui_datenschutz_hinweis()` | `.datenschutz-hinweis` | **keine** | 1449 |
| `ui_meldung_markup()` | `.meldung` | ja (+19 Unterklassen) | 1516 |
| `ui_knopf()` | `.knopf` | ja (+17 Unterklassen) | 1563 |
| `ui_codeblock_lang()` | `.codeblock-lang` | ja | 1616 |
| `ui_plakette()` | `.plakette` | ja (+5 Unterklassen) | 1642 |
| `ui_karte_start()` | `.karte` | ja (+38 Unterklassen) | 1687 |
| `ui_karte_ende()` | `.karte` | ja (+38 Unterklassen) | 1771 |
| `ui_nach_oben()` | `.nach-oben` | ja | 1806 |
| `ui_sprungliste()` | `.sprungliste` | ja | 1848 |
| `ui_kartenfilter()` | `.kartenfilter` | ja (+4 Unterklassen) | 1896 |
| `ui_zeile()` | `.zeile` | ja (+34 Unterklassen) | 1960 |
| `ui_zeile_mehr()` | `.zeile-mehr` | ja | 2015 |
| `ui_reiter()` | `.reiter` | ja (+9 Unterklassen) | 2064 |
| `ui_listenkopf()` | `.listenkopf` | ja | 2109 |
| `ui_listenfuss()` | `.listenfuss` | ja | 2181 |
| `ui_titelzeile()` | `.titelzeile` | ja (+6 Unterklassen) | 2223 |
| `ui_aktionen()` | `.aktionen` | ja (+2 Unterklassen) | 2265 |
| `ui_feld()` | `.feld` | ja (+25 Unterklassen) | 2334 |
| `ui_schalter()` | `.schalter` | ja (+27 Unterklassen) | 2399 |
| `ui_segment_markup()` | `.segment` | ja (+23 Unterklassen) | 2443 |
| `ui_wahlliste()` | `.wahlliste` | ja | 2496 |
| `ui_zeilenaktionen()` | `.zeile-aktionen` | ja | 2540 |
| `ui_speichern_leiste()` | `.speichern` | ja (+4 Unterklassen) | 2639 |
| `ui_kennzahl()` | `.kennzahl` | ja (+21 Unterklassen) | 2701 |
| `ui_abbruch()` | `.rahmen` | ja (+1 Unterklassen) | 2742 |
| `ui_csrf_bootstrap()` | `.csrf-bootstrap` | **keine** | 2836 |
| `ui_geocoder_bootstrap()` | `.geocoder-bootstrap` | **keine** | 2877 |
| `ui_geocoder_hinweis()` | `.geocoder-hinweis` | **keine** | 2907 |
| `ui_ortsfeld()` | `.ortsfeld-zeile` | ja | 2919 |
| `ui_tabellen_bootstrap()` | `.tabellen-bootstrap` | **keine** | 3069 |
| `ui_krypto_bootstrap()` | — | Hüllenfunktion, kein eigenes Element | 3148 |

49 Funktionen mit Markup in `server/ui.php`, davon 4 Hüllenfunktionen ohne eigenes Element.
**Ohne Regel im Stylesheet:** `ui_logo_masse()`, `ui_datenschutz_hinweis()`, `ui_csrf_bootstrap()`, `ui_geocoder_bootstrap()`, `ui_geocoder_hinweis()`, `ui_tabellen_bootstrap()` — jede davon ist zu prüfen: entweder ein Behälter, der zu Recht keine Gestaltung braucht, oder eine Lücke.

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

> **Die drei Zustände sind nicht frei kombinierbar, und der Unterschied fällt
> still aus** (notiert 16.09.2026, P5a/AP8). Eine **klappbare** Karte zeigt
> **weder `plakette` noch `aktion`**: `ui_karte_start()` kehrt im
> `<details>`-Zweig zurück, bevor beides ausgegeben wird. Sie kennt dort nur
> Winkel, Titel, `zahl` und `vorschau`. Wer `vorschau` setzt und eine Plakette
> mitgibt, verliert die Plakette — ohne Fehlermeldung, und kein Bild zeigt es,
> weil der zugeklappte Kartenkopf ohnehin anders aussieht.
>
> **Wer beides braucht, nimmt eine offene Karte.** Was in die Plakette
> gehörte, steht dann nicht zusätzlich in der Vorschau: Die Vorschau ist der
> Ersatz für den Inhalt, nicht für den Zustand.

**Eine zweite Kopfaktion gibt es nicht** (E-P3-25). Was mehr braucht, bekommt
ein Aktionsmenü.

**Das Schloss am Titel** (`ui_karte_start(['geschuetzt' => true])`, Web 19.1.1)
für eine Karte, deren Inhalt vollständig Ende-zu-Ende-verschlüsselt ist, ohne
dass ein einzelnes Feld das Zeichen tragen kann — der Fall der Karte
„Notizen" im Einsatzformular, die genau ein Feld enthält und dessen
Beschriftung ausblendet (sie ist der Kartentitel). Das Zeichen steht **im
`<h2>`**, nicht als eigenes Element daneben: `.karte-kopf` ist ein Flex-Kasten
mit `gap`, und `.symbol-schutz` bringt sein `margin-left` selbst mit — daneben
bekäme es den Abstand zweimal. Es ist derselbe Baustein wie an einer
Feldbeschriftung, an derselben Stelle: rechts vom Wort.

```html
<h2 class="karte-titel">Notizen<svg class="symbol symbol-schutz">…</svg></h2>
```

**Variante „Bereichskarte"** (`.karte-bereich`, seit Web 21.1.0, P5c/AP9,
freigegeben mit M-P5c-01c, E-P5c-29). Der Kopf liegt auf **Rauch** und trägt
ein rundes **Bereichszeichen** (`.bereich-zeichen`: `--knopf` im Durchmesser,
Dunkelblau, Symbol in `--auf-dunkel`), den Titel und die Zahl — **als Gruppe
mittig**. Aufruf: `ui_karte_start(['titel' => …, 'zahl' => …, 'klasse' =>
'karte-bereich', 'symbol' => 'profil'])`; die Option `symbol` gibt das Zeichen
vor dem Titel aus. Abgesetzt wird über das Zeichen und die Mitte, **nicht über
eine Farbe mit Bedeutung** — Orange heißt „hier stehst du", Hellblau
„erledigt", Rot „Gefahr", und ein getönter Kopf las sich im Mockup als Meldung
oder Kopfleiste. Kontraste, gerechnet: Weiß auf Dunkelblau 13,6 : 1,
Dunkelblau auf Rauch (Titel) 12,5 : 1, Gedämpft auf Rauch (Zahl) 5,3 : 1.

- **Ein Verwender:** die Einstellungs-Übersicht (9.27). Ein zweiter braucht
  einen Grund.
- **Keine Kopfaktion, keine Plakette.** Mittig gesetzt hätte eine Aktion
  keinen Platz, und eine Plakette würde die Gruppe aus der Mitte schieben.
- **Nur offen.** Eine klappbare Karte kennt die Option nicht.

```html
<section class="karte karte-bereich">
  <div class="karte-kopf">
    <span class="bereich-zeichen"><svg class="symbol">…</svg></span>
    <h2 class="karte-titel">Verwaltung</h2>
    <span class="karte-zahl">5</span>
  </div>
  <div class="karte-inhalt">…</div>
</section>
```

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

**Die aufklappbare Zeile `.zeile-mehr` (seit Web 20.39.0, P5c/AP2,
E-P5c-26, Bild M-P5c-01a).** Hat ein Eintrag Angaben, die nicht in den Satz
passen — die `daten` eines Protokolleintrags —, bekommt `ui_zeile()` den
Schlüssel `daten` (eine Liste von Schlüssel-Wert-Paaren) und gibt statt des
`<div>` ein `<details class="zeile-mehr">` aus, dessen `<summary>` die Zeile
**ist**. Die Angaben stehen aufgeklappt darunter als `<dl class="zeile-daten">`
auf Rauch, in der festen Schrift, `--groesse-2`. Der Winkel steht in der
Aktionsspalte und dreht sich beim Öffnen (`--dauer`).

```html
<details class="zeile-mehr">
  <summary class="zeile">
    <div class="zeile-text">…</div>
    <div class="zeile-plaketten">…</div>
    <div class="zeile-aktionen"><svg class="symbol zeile-winkel">…</svg></div>
  </summary>
  <dl class="zeile-daten"><dt>weg</dt><dd>probe</dd></dl>
</details>
```

**Kein „⋯"-Blatt auf dem Handy:** Aufklappen ist keine Handlung, sondern
Lesen, und gehört deshalb nicht zu den Zeilenaktionen (9.3).

**Die Gegenregel ist Pflicht, nicht Zierde.** Das `<summary>` ist immer
erstes Kind seines `<details>`; `.zeile:first-child` nähme ihm den oberen
Innenabstand, und aufklappbare Zeilen wären **13 px niedriger** als feste
(F-P5c-13, gemessen in M-P5c-01). Die Gegenregel steht an
`.zeile-mehr > summary.zeile` (Spezifität 0,2,1 gegen 0,2,0); erste und
letzte Zeile der Liste behandelt `.zeile-mehr:first-child` bzw.
`:last-child`. Die Trennlinie trägt das `<details>`, nicht das `<summary>` —
sonst stünde sie aufgeklappt zwischen Zeile und Angaben. Der Bedienweg
`admin-protokoll-zeilen` misst beide Fassungen gleich hoch.

**`aktionsspalte => true`** hält die leere Aktionsspalte auch in Zeilen
**ohne** Angaben, damit alle Plaketten einer Liste auf einer Kante enden
(Vorgabe 17.09.2026). Ohne den Schlüssel bleibt eine Zeile ohne Aktionen,
wie sie war.

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
> Literal nirgends auftaucht. Behoben mit Web 10.3.0. Seit P5b/AP9 hält
> `tools/quelltext/` (`vollstaendigkeit`) jeden übergebenen Ton gegen das
> Stylesheet, seit R4-05 auch die Zweige einer Bedingung und die Vorgabe im
> Baustein (Backlog Nr. 36).
>
> **Und es ist ein zweites Mal passiert — `ok`.** In S10/AP3 beim Gegenlesen
> gefunden, bevor es ausgeliefert wurde: Die neue Karte „Schlüssel des
> Servers" sollte `plakette-ok` tragen, und die Klasse gibt es im Stylesheet
> nicht. Sie steht seit Längerem an **zwei Stellen im Bestand** und war dort
> nie aufgefallen. Wer einen Zustand „alles in Ordnung" meint, nimmt `blau` —
> das ist der Ton dafür (9.23). Die zwei Altstellen sind mit P5b/AP9
> behoben.

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
Platzhalter ist ein Beispiel, kein Vorschlag: Steht dort der Name der echten
Station, liest ein Teil der NutzerInnen das als die erwartete Antwort und
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

**Das Auswahlfeld ist der andere Sonderfall** (seit Web 19.5.1). Ein
`<select>` trägt `contain: paint`, und das ist keine Feinheit: WebKit rechnet
den **längsten Eintrag** in den Überlauf des Kastens mit, auch wenn der Kasten
ihn abschneidet. Gemessen auf `import.php` bei 360 px: `scrollWidth` 366 gegen
`innerWidth` 360 — nur in WebKit, und **kein einziges Element** der Seite ragte
hinaus. Der längste Eintrag hatte 53 Zeichen; die drei anderen Auswahlfelder
derselben Seite mit 17, 17 und 30 liefen nicht über (Backlog Nr. 185).
`overflow:clip` am Feld hilft nicht, `max-width:100%` auch nicht — nur die
Malbegrenzung. Sie kostet nachgemessen **ein** Pixel am fokussierten Feld
(318 × 60 px Ausschnitt, Abweichung 6 von 255, die Rundung des Fokusrings); der
Ring selbst bleibt stehen, weil Malbegrenzung Inhalt schneidet und nicht
Umriss. **Nur `select`** — ein Textfeld hat keinen Inhalt, der breiter wäre als
sein Kasten.

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

**Das Raster** (`.kennzahl-raster`) folgt dem Satz: zwei Spalten unter 720 px,
darüber vier oder fünf, je nach Zahl der Kacheln (Kapitel 7). Eine Ausnahme
gibt es, und sie hat einen eigenen Namen: **`.kennzahl-raster-3` steht in
jeder Breite auf drei Spalten**, auch am Handy. Sie ist für ein
Inhaltsverzeichnis gedacht und nicht für Zahlen, die man vergleicht — drei
Kacheln zu je rund 110 px tragen eine Zahl und ein Wort, und mehr sollen sie
dort nicht. Die Regel liegt bewusst **ohne** Medienabfrage im Stylesheet und
nach der Vierspalten-Regel des 1024er Bandes; nur so gewinnt sie überall
(S9/AP5, M-S9-07).

**Als Inhaltsverzeichnis** trägt jede Kachel ein `href` auf die Kennung einer
Karte (`#k-rettungsmittel`). Das ist der Weg für eine Seite, die am Handy
keine Leiste hat — am Schreibtisch entstehen dieselben Ziele noch einmal als
Unterpunkte der Leiste (9.25), und beide holen sie aus denselben `id`.

### 9.11 Dialog und Blatt

**Eine Karte im Schleier — in jeder Breite.**

> *Hier stand bis Web 17.0.0: „Am Schreibtisch eine Karte im Schleier, mobil
> ein Blatt von unten (E-P3-27). Dasselbe Markup, das Stylesheet
> entscheidet." Das stimmt nicht und hat nie gestimmt: Zu `.dialog` gibt es
> im Stylesheet **keine einzige Medienabfrage**. Das **Blatt** (`.blatt`,
> Abschnitt 10 des Stylesheets) ist ein eigener Baustein mit eigenem Skript
> und eigenem Markup — es steht neben dem Dialog, es ist nicht seine mobile
> Form. Berichtigt in S9/AP5-4, als der Satz beim Bau der
> Stammdaten-Dialoge zum ersten Mal jemanden in die Irre geführt hat.*

**Der Kopf trägt Titel und, wo nötig, eine Unterzeile** (`.unterzeile`, seit
Web 17.0.0): Sie sagt, worauf sich der Dialog bezieht — der Standort, zu dem
das neue Rettungsmittel gehört. Das gehört nicht in den Titel („Rettungsmittel
an Standort Talwang anlegen" bricht um) und nicht ins Formular (es ist kein
Feld, es ist der Zusammenhang). Dieselbe Rolle wie `.titelzeile-unter` an
einer Seite. **Der Titel bleibt 24 px** (`h2`, `--groesse-6`) — M-S9-07
zeichnet 19 px, aber die zwölf vorhandenen Dialoge tragen alle ein `h2` im
Kopf und sprängen mit; das Mockup bleibt hier folgenlos.

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

**Ein Formulardialog rollt in sich** (seit Web 17.0.0). Kopf und Fuß stehen
fest, `.dialog-inhalt` bekommt `overflow-y:auto`; die Höhe ist auf
`100dvh` minus 24 px begrenzt. Ohne das kappt die Browservorgabe unten ab —
und unten steht der Fuß mit der Hauptschaltfläche. Der Rettungsmittel-Dialog
(vier Felder, fünf Rollenhaken, zwei Fähigkeitshaken) ist am Handy höher als
das Glas; gemessen bei 390 × 780 px: Dialog **756 px**, Fuß bei **768 px**
sichtbar, Rollweg **279 px**.

> **`display` steht an `.dialog[open]`, nicht an `.dialog`** (Fund F-S9-U-27).
> Der Browser versteckt einen geschlossenen Dialog mit
> `dialog:not([open]){display:none}` — das ist eine Regel des Browsers, und
> die verliert gegen **jede** Regel des Stylesheets, ganz gleich wie
> spezifisch. `.dialog{display:flex}` machte damit alle fünf Dialoge einer
> Standortseite sichtbar: als Kästen am Seitenende, mit ausfüllbaren Feldern
> und absendenden Knöpfen. Aufgefallen an einem Vollseitenbild; im
> Fensterausschnitt standen sie unter der Falz. Dieselbe Falle wie bei
> `.zeile-knoepfe` gegen `.nur-ab-720`. Die Klickprobe misst es seither
> (`ap5-dialoge-sind-zu`).

**Ein Dialog kann einen zweiten öffnen.** Das Ortsfeld im Zielklinik-Dialog
trägt den Pin-Knopf, und der öffnet den Kartendialog (9.29) — zwei modale
Dialoge übereinander. Das trägt der Browser von sich aus: Der zweite kommt in
die oberste Ebene, der erste bleibt darunter stehen und ist nach dem
Schließen wieder da. Geprüft am 09.09.2026 im Browser, 1280 px.

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

**Das Blatt fährt auf, und der Öffner bleibt markiert** (seit Web 19.6.0,
Backlog Nr. 124, Weg b; E-P3-27 fortgeschrieben). Zwei Dinge ändern sich
gegenüber der ersten Fassung, und beide betreffen die Frage „wo bin ich
gerade?":

- **Die Bewegung.** Das Blatt steht im Ruhezustand um seine eigene Höhe unter
  dem Bildrand (`transform: translateY(100%)`) und wird von `.blatt-auf`
  heraufgeholt, in `--dauer` (240 ms, `ease-out`). Ohne sie las sich das
  Erscheinen wie ein Seitenwechsel: Die halbe Fläche war plötzlich eine
  andere, und niemand wusste, woher sie kam. **Am Schreibtisch nicht** — dort
  ist das Blatt ein Aufklappmenü direkt unter dem Knopf, und
  `translateY(100%)` hieße dort „um die eigene Höhe nach unten", also neben
  die Sache. Der 1024er Block setzt deshalb `transform:none; transition:none`.
- **Die Markierung.** Solange sein Blatt offen ist, trägt der Öffner
  `--orange-hell` mit `--orange-tief` (Kapitel 3.1). Sie hängt am Attribut,
  nicht an einer Klasse — siehe dort.

**Für wen das wichtig ist, der `blatt.js` anfasst:** Die Reihenfolge ist kein
Geschmack. Beim Öffnen erst `hidden=false`, die Klasse erst im **nächsten**
Frame — beides im selben Frame rechnet der Browser zusammen und zeichnet nur
den Endzustand. Beim Schließen umgekehrt: erst die Klasse weg, `hidden` erst
nach der Rückfahrt; `display:none` hält keine Bewegung an, es beendet sie.
Und das Skript fragt die **gerechnete** `transition-duration` des Blattes:
Ist sie ~0 — Aufklappmenü am Schreibtisch, oder Bewegung abbestellt —, geht
`hidden` sofort, sonst nach `transitionend` mit einem Nachlauf als Sicherung.
Ein Blatt, das im Fluss hängen bliebe, wäre unsichtbar, aber klickbar und im
Vorlesebaum.

### 9.13 Ortsfeld

**Zweck:** Eine Bezeichnung plus optionale Koordinaten, mit Adresssuche,
Vorschlagsliste und Kartenwahl. Gegenstück zu `assets/ortsfeld.js`: Die
Funktion erzeugt die Elemente, das Skript belebt sie — **beide bilden ihre
Kennungen aus demselben Präfix.**

**Die Trefferliste ist seit Web 15.7.0 ein eigener Baustein** (9.28): Das
Ortsfeld sagt, *was* darin steht (erkannte Koordinate, Stammdaten, Adressen);
*wie* es dasteht, entscheidet die Liste. Der Schlüssel `datalist` ist damit
ersatzlos entfallen — Stammdaten gehen als `vorschlaege` an
`EdOrtsfeld.init()`, nicht als Markup in die Seite.

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
Lupe suchte. Drei Grenzen fassen das ein und stehen seit Web 15.8.0 in
`assets/geocoder.js` (vorher in `ortsfeld.js`): **400 ms** Ruhe nach dem
letzten Tastendruck, **ab drei Zeichen**, **höchstens eine offene Anfrage**
(eine laufende wird abgebrochen). Die Lupe umgeht die Entprellung, nicht die
Mindestlänge.

> **Das ist eine Auskunft an Dritte, und sie steht in `docs/Lizenzen.md` 6.2.**
> Die Adresssuche geht an einen Photon-Dienst; jede Anfrage trägt die
> eingetippten Buchstaben dorthin. Stehen bereits Koordinaten, ruht die Suche
> ganz — die Formaterkennung läuft lokal und hat Vorrang.

**Die Kleinzeile darunter nennt den Dienst — einmal je Seite** (Web 15.8.0,
`ui_geocoder_hinweis()`, `.loc-datenschutz`). Nicht je Feld: Auf der
Standortseite steht ein Ortsfeld je Standort **und** je Zielklinik, das wären
zehn gleiche Sätze und mehr. Zehnmal derselbe Datenschutzhinweis ist keine
Auskunft mehr, sondern Tapete. Sie steht am **ersten** Ortsfeld der Seite und
ist auf die Seite bezogen formuliert; ist die Adresssuche aus, fehlt sie ganz
— sie sagt aus, dass etwas hinausgeht, und dann geht nichts hinaus.

**Der Pin-Knopf steht in beiden Formen** (Web 15.8.0). Bis dahin rendete ihn
nur `feld => true`; die Nur-Lage-Fassung hatte deshalb keine Karte, und
Backlog Nr. 70 („Karte für Standorte") war genau das. Der Block steht jetzt
einmal in `ui_ortsfeld()` und wird zweimal ausgegeben.

**Stammdaten stehen sofort da, Adressen entprellt.** Die Stammdaten liegen im
Browser; auf sie zu warten wäre eine Wartezeit ohne Grund, und sie erscheinen
ab dem ersten Zeichen. Ist die Adresssuche aus, bleibt die Stammdatengruppe
stehen — sie kommt aus dem eigenen Bestand und hat mit dem Dienst nichts zu
tun.

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
| groß | `.codeblock-wert` | Werte, die jemand **abschreibt**: Kopplungscode, Wiederherstellungsschlüssel | `--groesse-5`, 600, gesperrt (`.06em`) |
| klein | `.codeblock-wert-lang` | sechzig bis hundert Zeichen, die jemand **kopiert**: Cron-Zeile, Token-Adresse, Setz-Link, Einladungslink, Geräte-ID, API-Schlüssel, APK-Prüfsumme | `--groesse-2`, 400, ohne Sperrung, `word-break:break-all` |

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
steht an den Stellen mit langen Werten: Cron-Zeile und Token-Adresse
(Hintergrundjobs), Setz-Link (Kontoseite), Einladungslink
(NutzerInnen-Liste), Geräte-ID und API-Schlüssel (Geräte) sowie die
SHA-256-Prüfsumme des APK. **Die große Stufe bleibt den Werten, die
abgeschrieben werden:** dem Kopplungscode, dem Wiederherstellungsschlüssel —
und seit Web 20.1.0 dem Schlüsselblatt (Kasten unten).

> *Die Liste hieß bis Web 20.1.0 „alle sieben Stellen" und nannte die
> **Serverschlüssel-Zeile (Backup-Ziele)** mit. Die Karte ist mit S10/AP3
> nach Betrieb → Servereinstellungen gezogen und nennt dort nur noch die
> Kennung statt des Werts; ein Wertekasten steht da nicht mehr. Sechs
> Stellen.*

**Kein Symbol am Knopf.** Der Vorrat (Kapitel 8) hat keines für „kopieren",
und ein neues bräuchte Freigabe mit Mockup. Das Wort tut es. Ohne
Zwischenablage-Berechtigung markiert der Knopf den Wert und sagt „markiert —
Strg+C"; die Rückmeldung steht **im Knopf** und nicht daneben, weil ein Kasten,
der aufklappt, den Rest der Seite verschiebt.

> *Dieser Absatz stand bis Web 16.3.0 am Ende von 9.18a und handelte dort vom
> Kopieren-Knopf, den 9.18a gar nicht kennt. Zurückgeschoben, als 9.18a beim
> Anlegen des Kartenfilters gegengelesen wurde.*

> *Seit Web 21.1.0 stehen die Werte von Schlüssel- und Notfallblatt nicht
> mehr im Wertekasten, sondern als nummerierte Gruppen im Druckblatt (9.39);
> `.blatt-wert` ist entfallen. Der folgende Absatz beschreibt den Stand bis
> Web 21.0.0 — die Begründung für „groß und gesperrt" gilt für die Gruppen
> unverändert.*

> **Und seit Web 20.1.0 gibt es eine dritte Stelle für die große Stufe — das
> Schlüsselblatt** (S10/AP3). 64 Hexzeichen sind ein langer Wert, und nach
> der Regel oben gehörten sie in die kleine Stufe. Sie stehen trotzdem groß
> und gesperrt, weil das Blatt **abgeschrieben wird, nicht kopiert**: Es ist
> ein Ausdruck auf Papier, es gibt dort keine Zwischenablage, und wer einen
> Schlüssel abtippt, braucht jede Ziffer einzeln unterscheidbar. **Die
> Unterscheidung der Stufen ist also nicht die Länge, sondern was mit dem
> Wert geschieht** — abschreiben oder kopieren; die Länge ist nur der
> häufigste Hinweis darauf. Die Zusatzklasse `.blatt-wert` ändert daran zwei
> Dinge: Der Wert **darf** umbrechen (sechzehn Vierergruppen passen in keine
> Zeile von 210 mm) und er bricht **nur zwischen** den Gruppen
> (`word-break: keep-all`) — `break-all` der kleinen Stufe zerschnitte eine
> Gruppe mitten durch, und dann zählt beim Abtippen niemand mehr nach.
> Und **keinen Kopieren-Knopf**: Auf einem Ausdruck ist er ein Kasten, der
> nichts tut, und am Bildschirm zeigte er den Wert einer Stelle, an der er
> nicht hingehört.

### 9.18a Kopf einer langen Liste: Suche über den Filtern

*Sie ist **kein** Filterfeld.* Hier steht nur, wie `.listensuche` und
`.filterreihe` zueinander liegen; beide arbeiten **serverseitig** (ein
`<form method="get">` und Verweise, ohne Skript). Ein Feld, das eine bereits
gerenderte Liste im Browser ausdünnt, ist der Kartenfilter — 9.32.

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

**Seit Web 20.39.0 ein Weg: `ui_listenkopf()` und `ui_listenfuss()`** (P5c/AP2,
R83, F-P5c-54). Bis dahin standen Suchfeld, Filterpillen und Seitenwahl nur
als handgeschriebenes Markup in `admin_users.php`; die Protokollseite wäre
die zweite Kopie gewesen. Beide Seiten benutzen jetzt die Helfer, und das
Register (`tools/zaehlung/register.php`, Zeile „Listenkopf und Reiter")
hält die Klassen außerhalb von `ui.php` auf **null**.

- **`ui_listenkopf()`** — Suchfeld (Kennung = Name des Feldes, wie bisher),
  versteckte Felder für das, was die Suche mitnehmen soll, Filterpillen mit
  optionaler Zahl und optional ein **Auswahlfeld** in der Filterreihe
  (`.filterreihe .feld-eingabe`: so hoch wie ein Filter, nur so breit, wie
  es muss). Mit Skript (`assets/listenkopf.js`) schickt das Auswahlfeld
  sofort ab und der Knopf „Filtern" ist verborgen; ohne Skript steht er da.
- **Eine Pille mit Kreuz** (`kreuz => true`) ist ein Filter aus der Adresse,
  der sich zurücknehmen lässt — der Kontofilter der Protokollseite: aktiv,
  mit `schliessen`-Zeichen, der Verweis nimmt ihn weg. **Keine neue
  Darstellung:** Es ist die aktive `.listenfilter`-Pille mit einem Symbol
  aus dem Vorrat.
- **`ui_listenfuss()`** — die Zählung und ab zwei Seiten die Seitenwahl:
  erste, letzte und die Nachbarn der aktuellen Seite, dazwischen eine
  Ellipse.


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
Fläche. `--balken-punkt: 10px` ist `--radius` — die **mittlere** Rundung der Skala
(die kleinste ist `--radius-klein` mit 6 px, siehe Kapitel 4; der Satz nannte
sie bis Web 16.3.0 falsch die kleinste) — und ergibt einen Punkt, der neben
13-px-Text lesbar ist, ohne ihn zu überragen.

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
Diensttage-Leiste. **Seit Web 21.1.0 nach Option 1 „Linie"** (P5c/AP9,
E-P5c-29, Nr. 244, Mockup M-P5c-01c): Der Winkel steht **rechts**, die
Überschrift eine Stufe größer (`--groesse-4`) in **Dunkelblau**, und vor jedem
weiteren Block stehen eine Trennlinie und `--abstand-3` Luft. Geändert sind
nur Regeln an `.leiste-gruppe`; Markup, Aufklappen und das Merken des
Zustands bleiben.

> **Bis Web 21.0.0 stand der Winkel links**, mit der Begründung „zwei Leisten
> mit demselben Mechanismus sollen denselben Griff haben" (E-S8-07). Option 1
> hebt sie **für dieses Menü** auf: Links stand der Winkel genau dort, wo die
> Einträge ihr Symbol tragen, und zwischen Überschrift und Eintrag lag eine
> einzige Gewichtsstufe (Bricolage ist nur in 500 und 600 eingebunden) — die
> Überschrift las sich wie ein Eintrag. **Die Diensttage-Leiste bleibt, wie
> sie ist:** Winkel links, Jahreszeilen in `--groesse-4`, keine Trennlinie. Sie
> trägt `.leiste-gruppe` nicht.

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

**Unter 950 px Fensterhöhe fallen sie in der festen Leiste weg** (seit Web
21.1.0, E-P5c-131, dort unter 800 px; seit 21.1.3 unter 950, F-P5c-164;
Kapitel 7). Gemessen bei 1280 × 720 mit der BetreiberIn (18 Einträge): Mit
Sprungmarken waren auf 6 von 14 Seiten alle Einträge ohne Rollen
erreichbar, ohne sie auf 14 von 14 — die Servereinstellungen tragen acht
Marken. Die Einträge sind die Wege durch die Anwendung, die Marken nur Wege
durch eine Seite. In der Schublade (unter 1024 px) bleiben sie. **Warum 950
und nicht 800:** Zwischen 800 und 946 px standen die Marken, und drei
Listen passten nicht — Servereinstellungen braucht rund 947 px, Status 891,
Updates 835. Nachgemessen bei 1280 px Breite und acht Höhen von 800 bis
1000 px: mit 800 bei 800 px 11 von 14 Seiten vollständig, bei 900 px 13 von
14; mit 950 überall 14 von 14. Bei 950 px stehen alle acht Marken der
Servereinstellungen, bei 949 keine. Gemessen wird „ohne
Rollen erreichbar" so: Ein Eintrag zählt, wenn er im Bild steht **oder** der
Kopf seiner zugeklappten Gruppe. Einträge einer zugeklappten Gruppe haben in
Chromium eine Box (36 px hoch, Lage wie offen) — wer nach der Box fragt, zählt
sie mit (F-P5c-157).

**Auf einer zweispaltigen Seite sind es zwei Marken, nicht eine.** `menue.js`
bestimmt die oberste sichtbare Karte je `.form-spalte` — sonst bliebe die
rechte Spalte, in der man gerade liest, unmarkiert. Das betrifft die sechs
Seiten mit `.form-raster` (9.26); überall sonst ist es genau eine.

Sie entstehen im Browser aus den Karten der Seite, nicht aus PHP. Der Grund
steht im Kopf von `assets/menue.js`: Die Leiste wird vor dem Inhalt
gezeichnet, die Seite müsste ihre Kartentitel also zweimal nennen.
**Voraussetzung ist eine `id` an der Karte** — mit dem Vorsatz `k-`; ohne
sie ist die Karte kein Sprungziel und erscheint nicht.

### 9.26 Zwei Kartenspalten — zwei Wege, und wann welcher

| Klasse | ab | wer teilt auf | wofür |
|---|---|---|---|
| `.form-raster` + `.form-spalte` | 1200 | die Seite, im Markup | Karten mit thematischer Ordnung: links Server und E-Mail, rechts Jobs und Backups |
| `.karten-raster` | 1200 | der Browser (Mehrspaltensatz) | eine Reihe gleichrangiger Karten ohne Ordnung |
| `.form-raster` + `.form-raster-links-breit` | 1200 | die Seite, im Markup, **3 : 2** | links eine Tabelle „… je Zeitraum" mit bis zu fünf Zahlenspalten, rechts eine Karte mit Zeilen — heute die Statistik |

**Variante `.form-raster-einspaltig`** (seit Web 20.41.0, P5c/AP4, freigegeben
mit M-P5c-02c, E-P5c-66): eine Spalte, höchstens Lesespalte plus
`--abstand-5` breit. Für eine Seite, deren zweite Spalte **für diese Rolle**
leer bliebe — die Kontoseite des Supports, der weder Konto-Backups noch
die Gefahrenzone sieht, und seit Web 21.1.0 die Installation, für alle
Rollen. Ohne sie stünde die linke Spalte ab 1200 px halb so
breit neben einer Leere. Sie steht im Stylesheet **hinter** der Regel von
`.form-raster` und gewinnt deshalb bei gleicher Spezifität; die eine
`.form-spalte` darin bleibt, `menue.js` liest sie.

**Variante `.form-raster-links-breit`** (seit Web 20.47.0, P5c/AP7,
freigegeben mit M-P5c-01b, E-P5c-18): zwei Spalten im Verhältnis **3 : 2**
statt gleich. Gleich geteilt läuft die Tabelle mit fünf Fenstern über und
rollt in ihrer `.tabelle-scroll` — gemessen: 40 px bei 1200, 20 px bei 1240,
ab 1280 px nicht mehr. Mit 3 : 2 passt sie bei 1200, 1280, 1366 und 1440 px
ohne Rollen, in allen drei Motoren (36 Messungen, 0 Überläufe; P5c/AP7,
Commit `d519fac`). Wie die einspaltige
Variante steht sie im Stylesheet hinter `.form-raster` und gewinnt bei
gleicher Spezifität; die zwei `.form-spalte` bleiben.

`.karten-raster` nimmt die Karten **direkt** als Kinder und lässt sie
fließen; `break-inside:avoid` hält jede zusammen. Eine Karte, die dazukommt,
braucht keine Zuordnung — dafür lässt sich keine erzwingen.

**Wann zwei Spalten?** Nicht nach Anzahl, sondern nach Höhe. Gemessen an
Betrieb → Updates: vier Karten, einspaltig 1206 px, zweispaltig 977 px. Ab
vier Karten ohne thematische Ordnung lohnt es sich; darunter nicht.

**Es waren bis Web 19.3.0 drei Wege.** `.zweispalter` tat dasselbe wie
`.form-raster` unter einem zweiten Namen — Grid, zwei gleiche Spalten,
`align-items:start` — und hatte genau **einen** Verwender (die
Installationsseite) gegen fünf. Er ist gestrichen, die Seite trägt jetzt
`.form-raster` mit `.form-spalte` wie die anderen fünf (Backlog Nr. 125).

Ganz gleich waren die beiden Regeln übrigens nicht: `.zweispalter` setzte
`gap: var(--abstand-4)` für beide Richtungen, `.form-raster` setzt
`gap: 0 var(--abstand-4)` — Spaltenabstand ja, **Zeilenabstand null**. Das
fällt nicht auf, solange ein Raster genau zwei Kinder in einer Zeile hat, und
das ist auf allen sechs Seiten so. Es ist der Grund, warum „identisch" hier
nur für das gilt, was man sieht.

**`.form-spalte` trägt keine CSS-Regel** (nachgezählt: 0 Treffer im
Stylesheet) und ist trotzdem Pflicht: `assets/menue.js` liest die Klasse, um
in der Leiste je Spalte die oberste sichtbare Karte zu markieren. Ein
`.form-raster` ohne `.form-spalte`-Kinder ist deshalb eine halbe Bauform.

### 9.27 Die Einstellungs-Übersicht in drei Spalten

`.uebersicht-raster` mit einer Bereichskarte je Block, ab 1024 px als
Grid mit `repeat(auto-fit, minmax(var(--uebersicht-spalte), 1fr))`. Die Zahl
der Spalten ergibt sich damit aus der Zahl der Blöcke und die aus der Rolle —
eine für eine NutzerIn, zwei für eine Admin, drei für eine BetreiberIn, ohne
dass das Stylesheet die Rolle kennt.

**Je Block eine Bereichskarte** (9.1, seit Web 21.1.0): Der Bereichsname ist
der Kartentitel, die Zahl der Einträge steht daneben, davor das Zeichen —
`profil` (Einstellungen), `gruppe` (Verwaltung), `server` (Betrieb). Sie
doppeln je einen Eintrag darunter; das ist mit der Freigabe in Kauf genommen.
Darin die Übersichtszeilen (`.uebersicht-zeile`: Symbol, Text, Zähler,
Winkel).

> **Bis Web 21.0.0 stand der Bereichsname als gesperrte Versalzeile über einer
> titellosen Karte** (`.uebersicht-block`, Mockup 07), und die des ersten
> Blocks (`.uebersicht-block-erst`) nur nebeneinander — gestapelt hätte
> „EINSTELLUNGEN" die Seitenüberschrift wiederholt. Beide Klassen sind
> entfallen, mit ihnen der Behälter `.uebersicht-gruppe` um Überschrift und
> Karte: Die Karte ist jetzt selbst das Kind des Rasters. Die Sonderregel für den ersten Block braucht es nicht mehr: Ein
> Kartentitel benennt die Karte, nicht die Seite, und steht deshalb in jeder
> Breite.

### 9.28 Vorschlagsliste (`.vorschlaege`)

*Neu mit Web 15.7.0 (S9/AP1, E-S9-07). Mockup
`docs/konzepte/konzept-s9/mockups/M-S9-03-vorschlagsliste.html`, freigegeben
am 07.09.2026.* Gebaut wird sie in `assets/vorschlagsliste.js`, nicht in
`ui.php`: Sie entsteht beim Tippen im Browser, es gibt keine PHP-Seite, die
sie ausgeben könnte — nur das leere `<ul class="vorschlaege" hidden>`. In der
erzeugten Bausteintabelle (Kapitel 9, Anfang) steht sie deshalb nicht; sie
liest `ui.php`.

**Zweck:** Treffer unter einem Eingabefeld anbieten — aus mehreren Quellen,
in Gruppen, mit Tastatur.

```html
<div class="loc-widget">            <!-- oder .rmbox, .feld-vorschlag -->
  <input type="text" autocomplete="off">
  <ul class="vorschlaege" hidden>
    <li class="vorschlaege-gruppe">Zielkliniken</li>
    <li class="vorschlag aktiv" data-art="stamm">
      <svg class="symbol">…</svg>
      <span class="vorschlag-text">
        <span class="vorschlag-haupt"><b>Klin</b>ik Talwang</span>
        <span class="vorschlag-neben">Stammdaten · mit Koordinate</span>
      </span>
    </li>
  </ul>
</div>
```

```js
const steuer = EdVorschlaege.init({ feld, behaelter, liste, beiWahl });
steuer.zeige([{ titel: 'Zielkliniken', eintraege: [
  { haupt: 'Klinik Talwang', neben: 'Stammdaten · mit Koordinate',
    symbol: 'klinik', art: 'stamm', wert: satz }
] }], 'Klin');
```

**Sie ersetzt vier Dinge auf einmal:** die Photon-Liste des Ortsfelds
(`.loc-suggest`), die Liste der weiteren Rettungsmittel (`.rmlist`/`.rmopt`),
die freie Eingabe darin (`.rmneu`) und **jede** native `<datalist>`. Der Grund
für den letzten Punkt steht in Backlog Nr. 68: Der Browser zeichnet sie über
dem Feld, am Transportziel also über der eigenen Liste, und auf dem Handy oft
gar nicht.

**Übernommen wird auf `mousedown` mit `preventDefault()`, nie auf `click`.**
Das ist die eine Regel, die dieser Baustein nicht zur Wahl stellt, und sie hat
eine Nummer: Backlog Nr. 102. Ein Mausklick ist `mousedown` → `blur` →
`mouseup` → `click`; wer die Taste länger hält als der Blur-Aufschub (150 ms),
findet den Eintrag beim `mouseup` schon versteckt, und `click` fällt nie.
Gemessen bei 300 ms gehaltener Maus: vorher 0 von 3 Übernahmen, nachher 3 von
3 (`tools/bedienprobe/`).

**Ob eine Gruppenzeile erscheint, entscheidet der Aufrufer** — er setzt
`titel` oder lässt ihn weg. Die Regel ist nicht mechanisch: Das Besatzungsfeld
trägt „Vorlagen des Standorts" auch als einzige Gruppe, weil die Zeile sagt,
woher die Namen kommen und damit, dass ein Name daneben erlaubt ist. Der
Einsatzort zeigt allein Adressen und trägt keine Zeile — eine Überschrift ohne
Gegenstück ist keine Gliederung. Die weiteren Rettungsmittel tragen keine,
weil die freie Eingabe darunter keine zweite Gruppe ist, sondern eine
Handlung.

**Zustände und Maße:**

| | |
|---|---|
| `.vorschlaege` | schwebt (`position:absolute`, **`z-index:35`**); der Behälter trägt `position:relative` |
| `.vorschlaege-gruppe` | Herkunftszeile auf Rauch, `--groesse-1`, versal — keine Bedienhöhe, sie ist nicht anzufassen |
| `.vorschlag` | `min-height: var(--knopf)` — folgt beiden Bedienhöhen von selbst (44/36, R76) |
| `.vorschlag.aktiv`, `:hover` | Rauch mit `--abstand-1` **Orange tief** links — **eine** Markierung für Zeiger und Pfeiltaste. Bis Web 21.1.5 stand dort `--orange` (2,09:1 auf Rauch); der Strich steht allein, und dann gilt 3.1 (4,04:1, R4-08) |
| `.vorschlag-haupt` | eine Zeile, mit Ellipse; der getippte Teil in `<b>` |
| `.vorschlag-neben` | die Herkunft, gedämpft, `--groesse-2` |
| `.vorschlag-neu` | die freie Eingabe: `--orange-tief`, Kopfschrift — sie ist eine Handlung, kein Datensatz |

**Kein neues Token.** Alle Werte kommen aus der Skala; die Höhe ist `--knopf`.

> **`--knopf` ist die Untergrenze, nicht das Sollmaß.** Eine Zeile mit
> Haupt- **und** Herkunftszeile misst gemessen rund **51 px** — in beiden
> Bedienstufen dieselbe, weil der Text höher ist als der Knopf. Nur eine
> **einzeilige** Zeile folgt der Umschaltung sichtbar (gemessen 44 px bei
> 390 px, 36 px bei 1280 px am Zeiger). Wer die Bedienhöhe belegen will,
> misst deshalb ein Besatzungsfeld und kein Transportziel.

> **Ebene 35 — zwischen Speichern-Leiste und Kopfleiste.** Sie stand zuerst
> auf 20, dem Wert der alten `.rmlist`. Die klebende Speichern-Leiste liegt
> auf **30** und deckte damit genau die untersten Trefferzeilen zu: gemessen
> **61 px Überlappung**, und `elementFromPoint` traf in der Schnittfläche die
> Leiste. Nach oben ist sie ebenso begrenzt — die Kopfleiste liegt auf **40**
> und bleibt darüber, denn eine Vorschlagsliste, die über sie malt, verdeckt
> den Weg aus der Seite heraus. 35 ist der Platz dazwischen, und er ist der
> einzige. Gefunden hat es der Auftraggeber am Bild; die Klickprobe misst es
> seither in beide Richtungen.

> **Sie schiebt nicht, sie schwebt.** Die alte `.loc-suggest` stand im Fluss
> und drückte beim Tippen alles darunter nach unten — auf einem 390-px-Schirm
> sprang das halbe Formular, und der Eintrag, auf den man zielte, war beim
> Loslassen woanders. Wer einen neuen Behälter baut, gibt ihm
> `position:relative`; ohne einen positionierten Vorfahren hängt die Liste am
> Seitenanfang.

### 9.29 Kartendialog (`.dialog-karte`)

*Suchfeld, Spur und Legende neu mit Web 15.8.0 (S9/AP2, E-S9-06). Mockup
`docs/konzepte/konzept-s9/mockups/M-S9-04-kartendialog.html`, freigegeben am
07.09.2026. Der Dialog selbst gibt es seit Web 9.4.0.* Gebaut wird er in
`assets/ortswahl.js`, nicht in `ui.php` — er entsteht auf Knopfdruck im
Browser und wird beim Schließen wieder entfernt; in der erzeugten
Bausteintabelle steht er deshalb nicht.

**Zweck:** Einen Punkt auf der Karte wählen. **Ein** Dialog für fünf
Einbauorte: Einsatzort, manueller Abfahrtort, Transportziel und die
Lagefelder von Standort und Zielklinik in den Einstellungen. Bis Web 18.0.0
war der fünfte der Standort der Systemverwaltung (R39); die Bedienprobe misst
seither vier davon (`wege/ap2.mjs`), die Zielklinik nicht.

```html
<dialog class="dialog dialog-karte">
  <div class="dialog-kopf"><h2>Auf der Karte wählen</h2>
    <div class="dialog-suche">           <!-- nur bei EdGeocoder.an() -->
      <div class="ortsfeld-zeile">…Feld + Lupe…</div>
      <ul class="vorschlaege" data-liste hidden></ul>
    </div>
  </div>
  <div class="dialog-inhalt">
    <div class="ortswahl-karte"><div class="geo" data-karte></div>
      <span class="ortswahl-kreuz" aria-hidden="true"></span></div>
    <p class="feld-hinweis">Karte verschieben, bis das Kreuz auf dem Ort steht.</p>
    <div class="legende" data-legende hidden>
      <span><span class="legende-linie"></span> Aufzeichnung</span>
      <span><span class="geo-ringpunkt"></span> Start</span>
      <span><span class="geo-ringpunkt geo-ringpunkt-ende"></span> Ende</span>
    </div>
  </div>
  <div class="dialog-fuss">…Abbrechen · Übernehmen…</div>
</dialog>
```

**Das Suchfeld steht im Kopf, nicht im Inhalt.** Das ist keine Formsache: Im
Inhalt schöbe die aufklappende Trefferliste die Karte nach unten, und das
Kreuz wanderte unter dem Finger weg. Im Kopf legt sich die Liste **über** die
Karte (`.vorschlaege` schwebt, 9.28), und die Karte steht still. Dazu fällt
der obere Innenabstand des Inhalts weg (`.dialog-karte .dialog-inhalt`) —
sonst stünde zwischen Feld und Karte zweimal Luft.

**Ein Treffer setzt das Kreuz, mehr nicht** (F1). Die Karte fährt hin
(`setView`, Zoom 15), der Name wandert ins **Suchfeld**, damit man sieht,
wonach sie steht — ins Formular geschrieben wird nichts. Erst „Übernehmen"
übernimmt. So lässt sich ein Treffer noch von Hand nachjustieren, statt eine
ungefähre Adresse als Tatsache zu speichern.

**Die Zeichnung ist die aufgezeichnete Spur, sonst nichts.** Linie in
`EdGeo.spurFarbe(0)`, Ringpunkte an Anfang und Ende (`EdGeo.markerRing`) —
**keine Luftlinie, keine Schilder für Standort und Klinik**: Sie gehören
nicht in einen Auswahldialog, sie würden das Kreuz verdecken. Die Legende
erscheint mit der Spur und nicht vorher; ohne Aufzeichnung (Stammdaten haben
keinen Einsatz) bleibt sie versteckt.

> **`fitBounds` nur bei leerem Feld — und nur, solange niemand geschoben
> hat.** Steht schon eine Koordinate, ist sie die Aussage und bleibt der
> Mittelpunkt; die Spur ist dann Zusatz. Und weil die Spur nachgeladen wird,
> könnte sie einer NutzerIn, die inzwischen selbst gezielt hat, die Karte
> unter dem Kreuz wegreißen — `dragstart`/`zoomstart` setzen deshalb ein
> Merkzeichen, das den Einpassvorgang abbestellt.

**Kein neues Token für den Dialog selbst.** `.legende-linie` ist
`--abstand-5` × `--abstand-1`, also **24 × 4 px** in `--spur-1`; die
Ringpunkte sind die aus M-S9-01 und tragen ihre Größe selbst (9.30), die
Schrift ist `--groesse-2` in `--gedaempft`.

> **Die Legende erbt die Ringpunkte, also auch ihre Änderungen.** Mit Web
> 15.9.0 sind sie von 16 auf 14 px gegangen und ihr Rand von 3 auf 2 px
> (M-S9-01 V1) — die Legende im Dialog ist damit mitgeschrumpft, ohne dass
> hier eine Zeile stand. Wer die Zeichen ändert, sehe sie neben dem 13-px-Text
> dieser Zeile an.

### 9.30 Kartenzeichen (`.geo-*`)

*Maße neu gefasst mit Web 15.9.0 (S9/AP3, E-S9-12). Mockup
`docs/konzepte/konzept-s9/mockups/M-S9-01-kartenschilder.html`, Variante V1,
freigegeben am 06.09.2026.* Gebaut werden sie in `assets/geo.js` als
Leaflet-`divIcon`; das Stylesheet zeichnet, das Modul setzt nur die Maße, die
Leaflet als Zahl braucht.

**Zweck:** Standort, Zielklinik, Einsatzort, Anfang und Ende der Aufzeichnung
und ihre Richtung auf einer Karte kenntlich machen — auf 160 px Kartenhöhe am
Handy genauso wie auf 600 px am Schreibtisch.

| Zeichen | Klasse | Außenmaß | woraus |
|---|---|---|---|
| Schild ohne Aufzeichnung | `.geo-schild-kasten` | **32 px** | `--geo-schild` 30 + 2 × 1 px Schnee |
| Schild mit Start **oder** Ende | `+ .geo-ring-start` / `-ende` | **32 px** | Farbrand liegt **innen** (`border-box`) |
| Schild mit beidem | `+ .geo-ring-beide` | **38 px** | 30 + 2 × (1 Schnee + 2 Rot + 1 Schnee) |
| Einsatzort | `.geo-kreis` | **28 px** | `--geo-kreis`, kein Rand |
| Ring ohne Schild | `.geo-ringpunkt` | **14 px** | `--geo-ringpunkt`, Rand `--strich-stark` |
| Ring ohne Schild, beides | `+ .geo-ringpunkt-beide` | **20 px** | 14 + 2 × (1 Schnee + 2 Rot) |
| Abfahrtort | `.geo-punkt` | **12 px** | `--abstand-3` |
| Richtungspfeil | `.geo-pfeil` | **20 px** | `--symbol` |

**Der Farbring IST der Rand, er liegt nicht darum herum.** Bis Web 15.8.0 lag
er als zweiter und dritter `box-shadow` außerhalb des dunkelblauen Randes —
ein Standort mit Doppelring maß dadurch **60 px** und deckte auf der
Handykarte mehr als ein Drittel der Höhe. Jetzt ersetzt der Farbrand den
dunkelblauen. Ohne Aufzeichnung bleibt der Rand dunkelblau: Er sagt dann
nichts über die Spur, er trennt nur.

**Außen liegt am Schild immer 1 px Schnee** (F6) — die Trennlinie, damit Blau
nicht auf Kartengrün und Rot nicht auf Braun stößt. Der Einsatzort-Kreis
bekommt sie **nicht**: Er ist orange, und Orange kommt auf keiner der drei
Kartenebenen vor; die freigegebene Maßleiste nennt für ihn 28 px, mit
Trennlinie wären es 30.

> **R76 gilt hier nicht** (S3/AP7): Ein Kartenzeichen ist eine Zeichnung, kein
> Bedienelement, und die 44/36-px-Regel gilt für das, was man **drückt**.
> Untergrenze am Finger ist statt dessen **24 px** (WCAG 2.5.8) — und die
> gilt für alles, was ein Popup öffnet. Der Ringpunkt ist 14 px groß und
> öffnet eines: Er sitzt deshalb in einer durchsichtigen 24-px-Fläche
> (`.geo-ringpunkt-feld`). Die Zeichnung bleibt klein, der Finger trifft
> trotzdem.

**Die Maße stehen zweimal, und das ist Absicht.** Leaflet braucht sie als
Zahl (`iconSize`, `iconAnchor`), das Stylesheet als Token. Wer eines ändert,
ändert beides — sonst wandert der Anker, und zwar ohne Fehlermeldung.
Betroffen: `--geo-schild` / `SCHILD_PX`, `--geo-kreis` / `KREIS_PX`,
`--geo-ringpunkt` / `RINGPUNKT_PX`.

> **Ein `<span>` ohne `display` ist kein Kasten.** `.geo-pfeil` trug seine
> Drehung als `transform:rotate()` und `.geo-punkt` seine Größe als
> `width`/`height` — beides wirkt an einem nicht ersetzten Inline-Element
> **nicht**. Die Pfeile zeigten dadurch bis Web 15.8.0 ausnahmslos nach Norden
> (Backlog Nr. 72), der Abfahrtort maß 4 × 18 px statt 12 × 12 und zeigte
> seine Farbe nie (Nr. 162). Beide haben jetzt einen ausdrücklichen Kasten.
> **Der berechnete Stil verrät das nicht:** `getComputedStyle` meldet die
> Drehmatrix auch dort, wo sie nichts tut. Nachweisbar ist es nur an der
> Geometrie — ein 20-px-Kasten mit `rotate(45deg)` misst 28,3 px, wenn die
> Drehung greift, und 20 px, wenn nicht.

**Kein neues Token außer zwei abgeleiteten.** `--geo-ringpunkt` ist
`--abstand-4` minus `--strich-stark`, `--geo-symbol` ist `--symbol` minus
`--strich-stark` — die Rechnung steht im Stylesheet und **ist** ihre
Herkunft. 14 px und 18 px stehen auf keiner Skala des Projekts, und die Skala
ist geschlossen; eine begründete Ableitung ist der Weg, den Kapitel 6 dafür
vorsieht.

### 9.31 Sprungliste (`.sprungliste` / `.sprungziel`)

**Zweck:** in eine **lange Liste** hineinspringen. Eine umbrechende Zeile
runder Pillen über der Liste, jede mit Artzeichen und Namen; ein Klick führt
zur Zeile, und die Zeile färbt sich (`:target`, unten). Freigegeben mit
M-S9-05 und M-S9-06.

**Ab sechs Einträgen** (`SD_HILFE_AB`, `stammdaten_ui.php`). Darunter sieht
man die ganze Liste, ohne zu rollen, und die Sprungliste wäre dieselbe
Aufzählung ein zweites Mal.

**Nur wo die Einträge ein Zeichen tragen.** Auf der Standortseite bekommen die
Rettungsmittel eine Sprungliste und keine der übrigen fünf Listen: An ihrem
Artzeichen erkennt man sie in einer Pillenreihe wieder. Eine Reihe aus zwölf
Namen ohne Zeichen ist keine Orientierung — sie ist eine zweite Liste. Wer
eine Liste ohne Zeichen durchsuchbar machen will, nimmt den Kartenfilter
(9.32).

| Was | Wert | Herkunft |
|---|---|---|
| Höhe der Pille | **44 px** am Finger, **36 px** am Zeiger ab 1024 px | `--knopf` — dieselbe Bedienhöhe wie ein Knopf |
| Rundung | voll | `--radius-rund` |
| Fläche / Rand | Schnee auf `--linie-stark` | ein **Ziel**, kein Wert — der Koordinaten-Chip macht es umgekehrt |
| Schrift | Bricolage 600, 13 px | `--schrift-kopf`, `--groesse-2` |
| Artzeichen | 16 px, gedämpft | `--symbol-klein` |
| Zielzustand | Orange-hell auf `--orange` | `.sprungziel.aktiv` |

**Sie ist ein `<nav>` mit `<a>`, kein Knopf.** Ein Sprungziel ist Navigation:
Es ändert nichts, es steht im Verlauf, und die Zurück-Taste bringt einen
zurück. Dieselbe Überlegung trägt die Filterreihe der Suchseite.

**Der Zielzustand heißt `.aktiv` und nicht `.ziel`.** Das Mockup schreibt
`.ziel`; `.aktiv` ist in dieser Anwendung seit Langem das Wort für „hier
stehst du" — Kopfleiste, Leiste, Kennzahl, Listenfilter, Blattzeile und
Seitenknopf tragen es, und `.kennzahl.aktiv` ist Zeichen für Zeichen dieselbe
Deklaration. Ein zweiter Name für denselben Zustand ist eine zweite Sprache.

**Die angesprungene Zeile** trägt `.zeile:target` — Orange-hell, bis an den
Kartenrand gezogen (negativer Außenabstand von genau `--abstand-4`, dem
Innenabstand der Karte; die Mockups schreiben `--abstand-3` und lassen so je
Seite 4 px Weiß stehen). **Ohne Skript**, und es überlebt den Rücksprung aus
dem Verlauf — `.zeile-hervor` (Spurliste) sagt dasselbe, wird aber von Hand
gesetzt und ist nach einem Neuladen weg.

**Kein `scroll-margin-top`.** `html` trägt `scroll-padding-top`, und das gilt
für jedes Sprungziel der Seite. Die zweite Angabe war einmal gebaut und
addierte sich: gemessen 140 statt 72 px. Nachgemessen an der Standortseite
sitzt die angesprungene Zeile bei **72 px** — die Kopfleiste misst 56.

**Wann nicht:** für Sprungmarken von Karte zu Karte (das sind die Unterpunkte
der Leiste, 9.25, und am Handy die Kennzahlen, 9.10) und für eine Liste, die
kürzer ist als sechs Einträge.

### 9.32 Kartenfilter (`.kartenfilter`)

**Zweck:** eine **lange Liste in einer Karte** durchsuchen. Ein Feld mit Lupe
über der Liste; Tippen blendet aus, was nicht passt — im Browser, ohne
Anfrage, ohne Neuladen, ohne Adressänderung. Freigegeben mit M-S9-06.

**Ab sechs Einträgen**, dieselbe Schwelle wie die Sprungliste
(`SD_HILFE_AB`). Darunter ist die Liste kürzer als das Feld darüber.

| Was | Wert | Herkunft |
|---|---|---|
| Höhe | **44 px** am Finger, **36 px** am Zeiger ab 1024 px | `--knopf` |
| Lupe | 20 px, gedämpft, links, `pointer-events:none` | `--symbol`, `--abstand-3` |
| Innenabstand links | Lupe plus Luft | `calc(--abstand-3 + --symbol + --abstand-2)` |
| Löschkreuz | rechts, in Knopfgröße | `--knopf` |

**Er heißt nicht `.filterfeld`.** Das Stylesheet führt seit P3 `.filterfelder`
(Mehrzahl) als Innenabstand einer aufgeklappten Filtergruppe der Suchseite.
Zwei Klassen, die sich um ein „r" unterscheiden und Verschiedenes meinen, sind
derselbe Fehler, den `.listenfilter-zahl` einmal ausdrücklich umgangen hat.

**Er ist nicht das große Suchfeld.** `.suchfeld` ist **48 px** hoch
(`--suchfeld`) — die eine benannte Ausnahme von der 44/36-Regel, weil es die
Haupthandlung *seiner* Seite ist. Ein Filter in einer von sechs Karten ist das
nicht: Hier gilt die Regel, nicht die Ausnahme. Übernommen ist von dort, was
dort schon richtig ist — die Lupe absolut links in einem
`align-items:center`-Behälter (also **ohne** `top`, das sich in der zweiten
Bedienhöhe um 4 px verrechnete), das Löschkreuz und die Beschriftung für die
Vorlesesoftware.

**Vier Dinge, die ein naiver Filter falsch macht** und die der Baustein
deshalb mitmacht (`assets/kartenfilter.js`):

1. Die **verborgenen POST-Formulare** stehen *neben* der Zeile, nicht darin.
   Wer über alle Kinder filtert, versteckt sie mit — und dann zeigt das
   Aktionsmenü einer sichtbaren Zeile über `form=` auf ein Formular mit
   `display:none`. Gefiltert wird ausschließlich über `.zeile`.
2. Ein **Zwischentitel** ohne sichtbare Zeile bleibt sonst stehen und lässt
   die Karte leer statt gefiltert aussehen. Er geht mit seiner Gruppe.
3. Die **Anlegen-Formulare** sind verborgen, solange gefiltert wird — heute
   ein Netz, kein Weg: Bis Web 16.3.0 stand in der Besatzungskarte eines je
   Rolle, und unter einem Treffer stünden sonst vier verwaiste. Mit Web 17.0.0
   sind sie samt `sd_form()` entfallen; angelegt wird im Dialog, und dessen
   Öffner steht im **Kartenkopf**, also außerhalb der gefilterten Liste. Die
   Regel bleibt stehen, weil sie eine Absicht beschreibt (ein Filter ist ein
   **Lesezustand**) und die nächste Liste mit einem Formular darin sie
   geschenkt bekommt. Die Klickprobe misst seither beides: **0 Formulare**
   und **„Anlegen" sichtbar** — auch bei null Treffern.
4. Der **Leerzustand** steht als verborgener Absatz im Markup und sagt, wie
   man den Filter wieder leert. Ein Text, den das Skript zusammensetzt, liefe
   an der Wortliste vorbei.

**Kein Zustand in der Adresse.** Der Filter ist eine Lesehilfe, kein
Standpunkt: Er soll nach dem Neuladen weg sein und keine Adresse erzeugen, die
jemand teilt und die beim Empfänger eine halbe Liste zeigt.

**Wann nicht:** für einen Bestand, der *nicht* vollständig im Dokument steht
(Tausende Einsätze, seitenweise geladen) — das ist die Filterreihe der
Suchseite, und die filtert auf dem Server.

### 9.33 „Zum Anfang" (`.nach-oben`)

**Zweck:** der Rückweg am Ende eines langen Abschnitts. Eine Standortseite mit
zehn Rettungsmitteln und drei Dutzend Zielkliniken ist mehrere Bildschirme
lang; wer unten ankommt, will nicht dorthin zurückwischen, wo das
Inhaltsverzeichnis steht. Rechtsbündig, mit Luft darüber, am Ende **jeder**
Karte.

**Der Knopf ist `.knopf knopf-leise`, kein Textverweis** — und das ist kein
Geschmack, sondern eine Messfrage: Der Bilderlauf misst Bedienhöhen an
`.knopf`. Ein gedämpfter 13-px-Verweis (so das Mockup) wäre aus seiner Messung
gefallen, und in dieser Richtlinie stünde eine 44/36-Zusage, die kein
Prüfmittel deckt. Genau so ist der Export-Knopf vier Monate ungestaltet
geblieben (F-P3-BA).

**Das Ziel ist `#inhalt`** — die Kennung, die `ui_leiste_ende()` ohnehin an
das `<main>` hängt. Zuerst stand dort `#seitenanfang`, eine Kennung, die es in
der Anwendung nirgends gibt: Der Knopf sprang nach nirgendwo, ohne Fehler und
ohne Meldung (F-S9-U-17). Eine zweite Kennung für dieselbe Stelle anzulegen
hieße, sie zweimal zu benennen; das Inhaltsverzeichnis steht als erstes
Element im `<main>`, der Sprung landet also dort, wo das Mockup hinwill.

**Eigene Funktion und keine Option an `ui_karte_ende()`:** Die hat als
einziger Baustein kein `array $o`, dafür 115 Aufrufstellen.

### 9.34 Kopfzeile einer Tagesgruppe (`.imp-daygroup`)

**Zweck:** die Überschrift über einer Gruppe von Zeilen **innerhalb** einer
Tabelle. Bisher nur in der Importvorschau, wo die eingelesenen Einsätze nach
Diensttagen gruppiert erscheinen. Freigegeben mit M-MR-01, Variante A
(F-MR-1, Mockup-Runde 9c).

```html
<div class="tabelle-scroll imp-roll">          <!-- Größencontainer -->
 <table class="tabelle">
  <tr class="imp-daygroup"><td colspan="…">
  <div class="imp-kopfzeile">
    <span class="imp-tag">17.01.2026</span>
    <span class="imp-rest">Besatzung … · 2 Einsätze · Diensttag vorhanden</span>
    <span class="plakette plakette-orange"><svg class="symbol symbol-klein">…</svg>
      abweichende Besatzung: Weber → Muster</span>
    <select class="imp-daymode">…</select>
  </div>
  </td></tr>
 </table>
</div>
```

**Der Unterschied zur Datenzeile ist die Schriftfamilie, nicht das Gewicht.**
Vorher stand dort `<strong>` — dasselbe, was auch eine betonte Zelle trägt.
Was eine Überschrift in dieser Anwendung ausmacht, ist die Kopfschrift; das
Gewicht allein trägt sie nicht. Dazu Rauch als Fläche und eine kräftige
Oberlinie (`--strich-stark`), damit die Gruppe sichtbar beginnt.

**Ein Zustand an der Gruppe ist eine Plakette, keine eigene Regel.** Die
Warnung „abweichende Crew" war Fließtext und bekam in Variante B des Mockups
eine eigene Regel; freigegeben ist Variante A — die vorhandene
`.plakette-orange` mit dem Symbol `warnung`, in Rot (`.plakette-rot`) für die
Gruppe „Nicht zuordenbar". Das ist dieselbe Form wie überall sonst, und eine
LeserIn erkennt sie ohne Lernen. **Die eine Abweichung:** In dieser Kopfzeile
darf die Plakette **umbrechen** (`white-space:normal`) — sie trägt hier einen
Satz, keine Vokabel, und wäre bei 360 px sonst breiter als das Gerät.

**Wann nicht:** für eine Überschrift, die **über** einer Tabelle steht statt
in ihr — das ist der Kartentitel (`ui_karte_start()`). Diese Kopfzeile gibt
es nur, weil die Gruppen sich eine Tabelle teilen müssen, damit die Spalten
fluchten.

**Wie sie das Sichtfenster findet — und warum das der Kern ist.** Die Zelle
ist so breit wie die **Tabelle**, nicht wie das Sichtfenster: in der
Importvorschau gemessen 2653 px gegen 342 px am Handy. Web 19.4.0 hat das
übersehen und `flex-wrap` gesetzt; in einer 2653 px breiten Zeile bricht aber
nichts um, und alles nach dem Datum stand außerhalb (Backlog Nr. 182).

Seit Web 19.4.1 trägt der Rollbereich `container-type: inline-size` über eine
**eigene Klasse** (`.imp-roll`), und die Kopfzeile nimmt mit `width:100cqi`
die **sichtbare** Breite an; `position:sticky; left:0` hält sie am linken Rand,
während die Datenzeilen darunter durchlaufen. Das Polster wandert dafür von der
Zelle in die Kopfzeile — Fläche und Oberlinie bleiben an der Zelle und laufen
über die ganze Tabellenbreite, damit das Band durchgehend bleibt.

Drei Sätze für den nächsten, der das braucht:

- **`width:100%` wäre falsch** — das ist die Breite der Zelle, also 2653 px.
  Nur `cqi` kennt den Rollbereich.
- **Eine gemessene Zahl aus JavaScript braucht es nicht.** Beide Fassungen
  sind bei sechs Fensterbreiten auf den Pixel gleich; die CSS-Fassung kommt
  ohne `ResizeObserver` aus.
- **Die Container-Eigenschaft gehört nicht an `.tabelle-scroll`.** Die Klasse
  trägt neun Stellen auf sechs Seiten, und `container-type` bringt
  `contain: layout inline-size` mit — eine globale Eigenschaft für ein
  örtliches Problem.

**Der Preis:** Am Handy wird der Kopf hoch — 231 px bei 400 px im ungünstigsten
Fall (zwei abweichende Rollen mit langen Namen), rund 130 px bei einer. Das ist
gewollt: Der Text ist der Grund, warum jemand hinsieht.

### 9.35 Kartengröße (`.geo-gross`, Knopf `map-ctrl-groesse`)

*Nachgetragen mit Web 19.5.1. Der Baustein ist mit Web 19.5.0 entstanden
(Mockup-Runde 9c / AP3, Mockups M-MR-03 und M-MR-04, F-MR-7 bis F-MR-10) —
die Kapitelpflicht aus 1.3 ist dort **übersehen** worden; nur die erzeugten
Tabellen sind nachgezogen. Das hier holt es nach.*

**Wozu.** Die Karte der Tagesübersicht hatte zwei Zustände: ihre Höhe nach
Fensterbreite (`--karte-mobil` 160, `--karte-tablet` 220, `--karte-desktop`
300 px) und Vollbild. Dazwischen lag nichts — wer mehr von der Spur sehen
wollte, musste die Seite verlassen und wiederkommen und verlor dabei den Blick
auf die Einsatzliste. Der dritte Zustand liegt dazwischen.

**Ein Zustand, zwei Wirkungen je Breite.** Die Klasse `.geo-gross` heißt
überall dasselbe, das Stylesheet entscheidet, was sie tut:

| Breite | Wirkung |
|---|---|
| bis 1599 px | Die Karte wird **höher** — `--karte-gross`, also `min(60vh, 520px)` |
| ab 1600 px | Die Karte wird **breit** — das Raster fällt über `.tag-raster:has(.geo-gross)` auf eine Spalte, die Karte verlässt die rechte Spalte und liegt in voller Inhaltsbreite über der Liste, weiterhin 520 px hoch |

Die Schwelle 1600 steht damit an **einer** Stelle, im Stylesheet. Ein Knopf,
der je Breite etwas anderes täte, hätte sie ein zweites Mal im Code.

**Der Knopf trägt beide Symbole.** `karte-gross.svg` (senkrechte Pfeile) bis
1599 px, `karte-breit.svg` (Querpfeile) darüber; beide liegen im Markup, das
Stylesheet blendet je Breite eines aus (`.karte-groesse .symbol-hoch` /
`.symbol-breit`). Ein Tausch per JavaScript hätte die Schwelle ein drittes Mal
gebraucht. Die Beschriftung wechselt **nicht** — „Karte vergrößern" bzw.
„verkleinern" deckt beide Wirkungen, das Symbol daneben sagt welche. Der
Zustand steht in `aria-pressed`.

**Wo er sitzt und wo nicht.** Nur auf der Tagesübersicht
(`attachGroessenControl()` wird dort einzeln gerufen). Einsatzansicht,
Spurenseite und Zeitraumübersicht haben keine Liste unter der Karte, die vom
Höherwerden etwas hätte; ihr Vollbildknopf bleibt unberührt.

**Der Zustand wird je Gerät gemerkt**, nicht je Konto — `localStorage`,
Schlüssel `nadoku.karte-gross`, der erste dieser Anwendung. Wer am Schreibtisch
groß arbeitet, will das am Handy nicht zwangsläufig. Lesen und Schreiben sind
abgefangen; kommt nichts zurück, steht die Karte klein da.

**Ein Satz für den nächsten, der eine Karte umschaltet:** Leaflet muss es
erfahren. Nach dem Umschalten läuft `map.invalidateSize()` mit 60 ms Verzug —
ohne das bleibt die neue Fläche grau. Gemessen: 10 → 15 Kacheln, 0 px
unbedeckt nach 200 ms.

### 9.36 Streifen über dem Inhalt (`.hinweise`) und die Kopfleiste mit Etikett (`.kopf-umgebung`)

*Seit Web 20.38.0 (P5c/AP1, E-P5c-05, -13, -55, -59, -60, -66). Freigegeben
mit der Mockup-Runde M-P5c-02 (a), Variante A des aktiven Kopfpunkts.*

**Kein neuer Baustein, sondern ein Behälter und zwei Varianten.** Die
Streifen gab es schon — den Demo-Hinweis und den Datenschutz-Hinweis
(`.demo-hinweis`), dazu die Meldung (9.5). Neu ist, dass bis zu vier davon
**in einer Reihe** stehen, mit fester Reihenfolge:

| Streifen | Klasse | Ton | wegklickbar |
|---|---|---|---|
| Umgebung | `.demo-hinweis.hinweis-umgebung` | Rot tief auf Rosa (6,27 : 1), Symbol `server` | nein |
| Ankündigung | `.meldung.meldung-info` / `.meldung-warn` + `.meldung-ankuendigung` | wie die Meldung | ja, je Sitzung |
| Demo | `.demo-hinweis` | Orange hell | nein |
| Datenschutz | `.demo-hinweis` | Orange hell | nein |

**Die Reihe `.hinweise`** ist ein Spaltenbehälter mit `--abstand-2`
zwischen den Streifen und `--abstand-5` darunter; die Kinder geben ihren
eigenen Außenabstand ab. Gestapelt ohne Behälter ergäben zwei
`--abstand-5` untereinander 32-px-Lücken zwischen Zeilen, die
zusammengehören. **Sie steht an der Stelle des Demo-Hinweises**, als erstes
Kind von `main.inhalt` — nicht unter der Kopfleiste, wo ein Streifen die
klebende Leiste verschob (F-P3-G). Auf den Anmeldeseiten steht sie über der
Karte und ist so breit wie sie (`.anmeldung .hinweise`, `--anmeldekarte`).
Ohne Streifen gibt `ui_hinweise()` nichts aus, auch keinen leeren Behälter.

**Die Ankündigung** ist die vorhandene Meldung mit einem Kreuz in
`.meldung-aktion`. `.meldung-ankuendigung` hält das Kreuz **oben rechts**:
Die Meldung bricht sonst um, sobald der Text seine Mindestbreite (16rem)
nicht bekommt, und das Kreuz landete allein in einer Zeile unten links. Hat
der Text mehr als einen Satz, steht der erste als fetter Auftakt.

**Die Kopfleiste einer Anlage mit Etikett** (`.kopf-umgebung`) ist eine
Variante von `.kopf`: Fläche `--rot` statt `--dunkelblau` (Weiß darauf
4,78 : 1), der Name in `--schnee` statt `--sand` (Sand hätte auf Rot
2,86 : 1), und der **aktive Kopfpunkt** gestrichen in `--orange-hell`
(4,12 : 1). Das gewohnte `--orange` hätte auf Rot 2,10 : 1 und fiele unter
die 3 : 1 für einen Bedienzustand (WCAG 1.4.11); `--orange-hell` bleibt in
der Farbe, die „hier stehst du" heißt. Weiß wäre die Alternative gewesen
(Variante B, nicht gewählt). **Die Farbe ist eine geschlossene Liste** —
heute nur `rot` (`UMGEBUNG_FARBEN`). Eine zweite braucht eine Regel hier,
drei Paare in `kontrast.py` und eine Freigabe mit Mockup.

**Kein neues Token, kein neuer Farbwert, kein neues Symbol** — `server`,
`schliessen` und `mail` lagen im Vorrat.


### 9.37 Reiter (`.reiter`, `.reiter-punkt`, `.reiter-abgesetzt`, `.reiter-rahmen`)

*Seit Web 20.39.0 (P5c/AP2, E-P5c-25). **Neuer Baustein**, freigegeben mit
der Mockup-Runde M-P5c-01a (20.09.2026).*

**Zweck:** Wechsel zwischen gleichrangigen Sichten **einer** Seite — etwa
die Reiter der Protokollseite (Verwaltung, Sicherheit, E-Mail, Jobs,
Sicherung, Ziele, System, dazu Archiv; hier stand bis Web 21.1.0 ein Reiter
„Fehler", den es nie gab). Serverseitig: Jeder Reiter ist ein Verweis, der
Parameter steht in der Adresse, und ohne Skript funktioniert alles. **Drei
Verwender:** Protokoll (AP2, hier entstanden), Statistik (AP7), Rechtstexte
(AP9, seit Web 21.1.0 — der einzige, dessen Reiter ein Formular schützen).

```php
ui_reiter(['label' => 'Bereiche des Protokolls', 'punkte' => [
    ['text' => 'Verwaltung', 'href' => '?r=verwaltung', 'aktiv' => true],
    ['text' => 'Jobs',       'href' => '?r=jobs'],
    ['text' => 'Archiv',     'href' => '?r=archiv', 'abgesetzt' => true],
]]);
```

| Teil | Darstellung |
|---|---|
| `.reiter` | Reihe mit Grundlinie `--strich` in `--linie`, `--abstand-4` darunter |
| `.reiter-punkt` | Kopfschrift 600, `--gedaempft`, `min-height: var(--knopf)` (44/36 px), nie umbrechend |
| `.reiter-punkt.aktiv` | `--dunkelblau`, Unterstrich `--strich-stark` in `--orange-tief`, `aria-current="page"` |
| `.reiter-abgesetzt` | am rechten Rand (`margin-left:auto`) — die Ablage („Archiv"), kein Ereignisreiter |
| `.reiter-rahmen` | Behälter für den Verlauf am Rand; `.rollt` / `.rollt-links` setzt `assets/reiter.js` |

**Orange heißt „hier stehst du"** — dieselbe Auszeichnung wie der aktive
Punkt der Kopfleiste, **aber in der tiefen Stufe** (E-P5c-79). Der Strich
steht auf Rauch, und `--orange` hätte dort **2,09 : 1**, unter den 3 : 1 für
einen Bedienzustand (WCAG 1.4.11). Die Schrift wechselt zwar mit, von
Gedämpft nach Dunkelblau, aber auch diese beiden liegen nur **2,35 : 1**
auseinander — zusammen tragen zwei schwache Zeichen kein starkes.
`--orange-tief` auf Rauch hat **4,04 : 1** (3.4). Das ist die Regel aus der
Ausnahme „Orange als Fläche": Wo ein oranger Strich allein steht, tritt
`--orange-tief` an seine Stelle. Das Mockup M-P5c-01a zeigte `--orange`;
entschieden am 24.09.2026.

**Warum nicht das Segment und nicht die Filterpillen** (M-P5c-01a, geprüft
und verworfen): Das Segment ist für wenige kurze Möglichkeiten; sieben
Wörter passen bei 400 px nicht. Die Pillen stehen eine Zeile tiefer als
Zeitraumfilter — zwei Reihen gleich aussehender Pillen mit verschiedener
Bedeutung wären die Verwechslung.

**Schmal rollt die Reihe in ihrem eigenen Behälter** (`overflow-x:auto`,
Rollbalken verborgen); die Seite selbst läuft nie waagerecht aus dem Bild
(Grundregel Kapitel 6). `assets/reiter.js` holt den aktiven Reiter beim
Laden ins Bild und setzt den Verlauf (`--abstand-5` breit, nach `--rauch`)
an den Rand, hinter dem noch etwas liegt. Ohne Skript rollt die Reihe
trotzdem, nur ohne Verlauf.

**Seiten mit Reitern tragen keine Unterpunkte in der Leiste:** `menue.js`
baut keine, wenn `#inhalt` eine `.reiter`-Reihe enthält. Zwei Wege zu
denselben Sichten wären einer zu viel.

**Attribute je Reiter** (`attr`) reicht der Baustein durch — gebraucht für
die Rückfrage bei ungespeichertem Text in den Rechtstexten (AP9,
`data-cancel-form`, F-P5c-57).

**Die Rechtstexte: Reiter über Feld und Vorschau** (seit Web 21.1.0, Mockup
M-P5c-01d Variante 2, freigegeben 20.09.2026). Unter den Reitern steht
`.form-raster` — ab 1200 px links die Karte mit Feld und Stand, rechts die
Karte „Vorschau", darunter gestapelt. Keine neue Darstellung, nur vorhandene
Bausteine und eine Klasse:

| Teil | Darstellung |
|---|---|
| Karte „Vorschau" | `ui_karte_start()` mit `plakette` — der Zustand steht als Plakette im Kopf: „gespeicherter Stand" `blau`, „wird aktualisiert …" `neutral`, „ungespeichert" und „nicht aktuell" `orange` |
| `.vorschau-rollt` | der Kasten in der Karte: `max-height` = Höhe des Textfelds (18 Zeilen, `--zeile` × `--groesse-3` + `--abstand-5`), rollt in sich (`overscroll-behavior:contain`); `.text` darin ohne Lesebreite, weil die Spalte schon schmal ist |
| Fehlfall | `EdHtml.meldung('warn', …, {auftakt})` über dem Kasten; die letzte Vorschau bleibt stehen |
| `.rechtstext-fuss` | Plakette „öffentlich"/„leer" und der Pfad der öffentlichen Seite nebeneinander |

**`.vorschau` ist entfallen.** Bis Web 21.0.0 stand unter jedem Feld auf
„Installation" ein gestrichelter Kasten mit dem gespeicherten Stand; mit der
eigenen Seite hatte die Klasse keinen Verwender mehr.

**Kein neues Token, kein neuer Farbwert.** Neues Symbol dieses Pakets ist
`protokoll.svg` (Kapitel 8) — für den Menüpunkt, nicht für den Reiter; ein
Reiter trägt kein Zeichen.

### 9.38 Zweitfaktor: QR-Code, Einrichtung, Codefeld, Codeliste (`.qr`, `.zweitfaktor-einrichtung`, `.feld-code`, `.codeblock-liste`, `.anmeldung-schritt`)

*Seit Web 20.42.0 (P5c/AP5, E-P5c-41). **Neue Bausteine**, freigegeben mit
der Mockup-Runde M-P5c-02 (b), 23.09.2026 (E-P5c-66).*

**Zweck:** die Einrichtung des Zweitfaktors (Karte „Zweitfaktor" unter
Einstellungen → Profil und das Einrichtungstor `zweitfaktor.php`) und der
Code-Schritt der Anmeldung. Tor und Karte teilen ihre Teile über
`zweitfaktor_teile.php` (`zf_einrichtung()`, `zf_codes()`) — eine Stelle,
damit beide dieselben drei Wege in die App zeigen: scannen, am Handy öffnen,
abtippen.

| Teil | Darstellung |
|---|---|
| `.qr` | SVG, halbe Anmeldekarte breit (`calc(var(--anmeldekarte) / 2)`, 200 px); `max-width:100%` |
| `.qr-grund` / `.qr-modul` | `fill: var(--schnee)` / `fill: var(--asphalt)` — ein Rechteck, ein Pfad |
| `.zweitfaktor-einrichtung` | Raster: QR links, Text rechts ab 720 px; darunter gestapelt, der QR zuerst. In `.anmeldung` immer gestapelt und mittig |
| `.feld-code` | an der Hülle `.feld`: Eingabe in der festen Schrift, `--groesse-5`, Sperrung `.06em` — wie der Codeblock, damit Abschreiben und Eintippen gleich aussehen |
| `.codeblock-liste` | Erweiterung des Codeblocks: zehn nummerierte Codes in zwei Spalten, Schrift wie `.codeblock-wert` (`--groesse-4`, 600, `--dunkelblau`), Nummer gedämpft |
| `.anmeldung-schritt` | Zwischenüberschrift in der Anmeldekarte (Code-Schritt, Einrichtungstor), `--groesse-5` |

**Nur die Modulmatrix kommt aus der Bibliothek** (`qrcode-generator`,
`docs/Lizenzen.md` 3). Das SVG baut `assets/qr.js` im Browser aus
`data-qr` — ein Rechteck `.qr-grund`, ein Pfad `.qr-modul`, Farben über die
Token, kein `style="…"`. Der Server gibt ein leeres, verborgenes `<svg>` aus;
ohne JavaScript bleibt es verborgen, und die beiden anderen Wege daneben
tragen allein. Fehlerstufe M, vier Module Ruhezone.

**Kein neues Token, kein neuer Farbwert, keine neue Größe:** 200 px ist die
halbe Anmeldekarte, die Sperrung die des Codeblocks.

**„Weiter" erst nach dem Haken** (Einrichtungstor, Schritt 2): Der Knopf
steht im Markup frei und wird von `assets/zweitfaktor.js` gesperrt, bis
„Ich habe die Codes gesichert." angehakt ist — umgekehrt wäre das Tor ohne
Skript eine Sackgasse.

**Der Druckknopf „Codeblatt drucken"** ist das vorhandene Druckformular
`.rf-druck` im Codeblock (wie beim Wiederherstellungsschlüssel), volle
Breite des Kastens. Im Einrichtungstor zeigt das Mockup ihn schmal — dort
stand das Formular im Mockup verschachtelt in einem anderen, und der Parser
hat es verworfen; die Karte (b) desselben Mockups zeigt die richtige
Darstellung (F-P5c-107).

**Der Rückweg mit dem Wiederherstellungsschlüssel — ohne neuen Baustein**
(Konzept RW, M-RW-01, freigegeben 24.09.2026, E-RW-13; seit Web 20.44.0 bzw.
20.45.0). Der **Schlüsselschritt** in `login.php` ist `.anmeldung-schritt`
über einem gewöhnlichen `ui_feld` und der `.zustandszeile` für die
Sofortprüfung — dieselbe Form wie das Schlüsselfeld der Passwort-Reset-Seite.
Die **Erfolgskarte** ist `.anmeldung-schritt`, eine Meldung im Ton `ok` und ein
Knopf. Die Zeile **„Rückweg mit dem Wiederherstellungsschlüssel"** in der
Karte „Zweitfaktor" ist eine `.zeile` (9.2) mit Plakette (9.23: blau
„eingerichtet", neutral „ab der nächsten Anmeldung"); **„Rückweg erneuern"**
öffnet den Dialog mit dem Aufbau des Passwortabschnitts von „Neuen
Wiederherstellungsschlüssel erzeugen". Keine neue Klasse, kein neues Symbol,
keine neue Farbe.

### 9.39 Druckblatt (`.blatt-druck`)

*Seit Web 20.42.0 (P5c/AP5, E-P5c-08). **Neuer Baustein**, freigegeben mit
M-P5c-01f (20.09.2026), die Codeliste mit M-P5c-02 (b), die Umgebungszeile
mit M-P5c-02 (e).*

**Zweck:** eine Seite, die gedruckt wird — **genau eine A4-Seite**. Drei
Verwender: das **Codeblatt** des Zweitfaktors (`codeblatt.php`, seit AP5), das
**Schlüsselblatt** (`betrieb_schluesselblatt.php`) und das **Notfallblatt**
(`notfallblatt.php`), beide seit Web 21.1.0 (P5c/AP9). Die Seite baut ihre
Hülle selbst (kein Gerüst), `<body class="blatt-seite">`, darin
`<main class="blatt-druck">`. Das **Logo** ist beim Schlüsselblatt der
Standard der Installation — das Blatt gehört der Anlage —, bei Code- und
Notfallblatt die Wahl des Kontos, ohne Sitzung der Standard (E-P5c-30).

| Teil | Darstellung |
|---|---|
| `.blatt-kopf` | Bildmarke (11 mm hoch) + `.blatt-marke` (Kurzname der Installation, Kopfschrift `--groesse-6`) links, `.blatt-kopf-rechts` (Adresse, Konto, Druckzeit; `--groesse-2`, gedämpft) rechts; darunter `--strich-stark` in `--dunkelblau` |
| `.blatt-umgebung` | nur mit `app.umgebung`: Rahmen `--strich-stark` in `--rot-tief`, Text in `--rot-tief` — ohne Fläche, weil der Browser Flächen nicht druckt (E-P5c-50) |
| `.blatt-druck h1` / `h2` | `--groesse-titel` / `--groesse-4` |
| `.blatt-druck .meldung` | die vorhandene Meldung, auf Papier mit Rand `--strich` in `--linie-stark` |
| `.blatt-kachel` | Rahmen `--strich-stark` in `--dunkelblau`, Fläche `--rauch` (im Druck ohne), nicht umbrechend; Kopf mit `.blatt-kachel-name` und `.blatt-kachel-neben` |
| `.blatt-codes` | zehn Einmalcodes in zwei Spalten, feste Schrift `--groesse-5`, Sperrung `.08em`; davor die Nummer, **davor das Kästchen** zum Abhaken (E-P5c-65) |
| `.blatt-druck-gruppen` + `.blatt-druck-gruppe` | ein Schlüssel in **nummerierten Vierergruppen**: acht je Zeile (Schlüsselblatt, 16 Gruppen), mit `.blatt-druck-gruppen-5` fünf in einer Zeile und eine Stufe größer (Notfallblatt). Feste Schrift `--groesse-5`, Sperrung `.08em`; die Nummer (`data-nr`) steht **darüber**, `--groesse-1`, gedämpft — die Rückfrage zum Schlüsselblatt fragt „Gruppe 11". Der Name trägt `-druck-`, weil `.blatt-gruppen` schon vergeben ist (F-P5c-44) |
| `.blatt-fuss` | Blattname · Installation · Webversion · Lizenz, rechts „Seite 1 von 1" |

**`@page` gilt für jede gedruckte Seite** (A4, 14 mm / 18 mm): Eine
Seitenregel lässt sich nicht an eine Klasse binden, und ein benannter
Seitentyp trägt nicht in jedem Browser. Bis Web 20.41 gab es keine Vorgabe.
Am Bildschirm sieht das Blatt ab 720 px aus wie das Blatt — 210 mm breit,
mit denselben Rändern.

**Gemessen** — PDF aus Chromium ohne Hintergrundgrafiken, belegte Höhe bei
658 px Satzbreite von 1017 px: Codeblatt **1 Seite**; Schlüsselblatt mit zwei
Werten **1 Seite, 742 px**; Notfallblatt **1 Seite, 711 px**; **Härtefall**
des Schlüsselblatts (drei Werte, Kurzname 83 Zeichen, Adresse 62) **1 Seite,
978 px**, auf Staging mit Umgebungszeile **1013 px** — 4 px Luft.

> **Die Zeile „Mehr" des Schlüsselblatts ist knapp, und das ist gemessen**
> (Web 21.1.0). Mit der langen Sprungmarke `#karte-schluessel-des-servers-…`
> lief sie im Härtefall auf drei Zeilen und das Blatt auf zwei Seiten
> (1032 px). Der Rückfall aus M-P5c-02 — die Umgebungszeile in den Kopf —
> half nicht: Neben einem langen Kurznamen wird die rechte Kopfspalte dadurch
> höher (1051 px). Geholfen hat die kurze Marke `#das-schluesselblatt`. Wer
> dem Blatt eine Zeile hinzufügt, misst den Härtefall neu.
>
> **Eine Falle beim Messen:** `page.pdf()` druckt mit der Medienart, die
> zuletzt per `emulateMedia` gesetzt wurde. Wer vorher `screen` gesetzt hat,
> bekommt die Bildschirmfassung — mit A4-Mindesthöhe und Rand — und misst
> zwei Seiten, wo eine ist.

## 10. Seitentypen und das Rezept für eine neue Seite

### 10.1 Sechs Typen

| Typ | Hülle | Leiste | Beispiele |
|---|---|---|---|
| **Inhaltsseite** | `ui_geruest_start(['leiste' => 'diensttage'])` | Diensttage | Tagesübersicht, Einsatzansicht, Formular, Papierkorb, Zeitraum |
| **Einstellungsseite** | `ui_geruest_start(['leiste' => 'einstellungen'])` | Einstellungsmenü | Profil, Standorte, Geräte, Konto-Backups, Installation, Betrieb |
| **Suchseite** | `ui_geruest_start(['leiste' => 'filter'])` | Filter, von der Seite gefüllt | Suche |
| **Öffentliche Lesespalte** | `ui_kopf(['menue' => false])` + `.rahmen rahmen-lesespalte` | keine | Impressum, Datenschutz, Abbruchseite, Einrichter (seit O10; bis Web 20.37.2 stand er hier fälschlich unter der Anmeldehülle) |
| **Anmeldehülle** | `.anmeldung-body` + `<main class="anmeldung">` | keine | Anmeldung, Passwort vergessen, Passwort setzen, Registrierung und Bestätigung, Abmeldeseite |
| **Druckseite** | `.blatt-seite` + `.rahmen rahmen-lesespalte` | keine | Schlüsselblatt (`betrieb_schluesselblatt.php`) |

**Die Anmeldehülle stapelt.** `.anmeldung` ordnet ihre Kinder
**untereinander** und mittig an: die Karte, auf der Anmeldeseite darunter die
vier Verweise `.fuss-anmeldung` auf Dunkelblau. Seit Web 20.37.3 (Nr. 289);
bis dahin galt die Vorgabe „nebeneinander", und die Verweise standen rechts
der Karte — bei 390 px war die Karte dadurch rund 200 px breit.

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
>
> **Seit Web 20.13.0 gibt es sie zweimal** (P5a/AP9): Neben „Wartung" steht
> „Ausgelastet" — dieselbe Lage aus anderem Grund, nämlich eine Datenbank,
> die keine Verbindung mehr annimmt. Sie benutzt **dieselben** Bausteine,
> und deshalb entsteht das Markup beider seither in **einem** Gerüst
> (`stoerung_seite_html()`): Rahmen, Lesespalte, Logo, Stylesheet und der
> Verzicht auf jedes Skript sind bei beiden dieselbe Überlegung, und zweimal
> geschrieben wären sie beim nächsten Mal zweierlei. Der einzige Unterschied
> im Markup ist der **fehlende Knopf** „Zur Verwaltung": Im Wartungsmodus
> antwortet `betrieb_updates.php` ausdrücklich, bei einer Überlast antwortet
> sie so wenig wie jede andere Seite — ein Verweis dorthin führte ins selbe
> 503.
>
> **Seit Web 20.40.0 dreimal** (P5c/AP3, E-P5c-58): die **Fehlerseite** (500)
> für eine Ausnahme, die niemand gefangen hat. Dasselbe Gerüst, statt
> `.meldung-warn` eine `.meldung-fehler` mit der Kennung, darunter der
> Meldeweg („Melde diese Kennung an …" mit der Kontaktadresse, sonst „Nenne
> diese Kennung …") und ein Verweis zur Startseite — die antwortet hier ja.
> Kein neuer Baustein. Ihr Markup steht in `system_fehlerseite()`
> (`systemmeldung_lib.php`), weil sie auch dann stehen muss, wenn `ui.php`
> selbst der Fehler ist.

> *Seit Web 21.1.0 bauen alle drei Blätter auf dem Druckblatt `.blatt-druck`
> (9.39) statt auf der Lesespalte; `.blatt-wert` ist entfallen, und den
> Seitenumbruch mitten im Wert verhindert jetzt `.blatt-kachel`. Der folgende
> Absatz beschreibt den Stand bis Web 21.0.0.*

> **Die Druckseite ist der zweite Sonderfall — und das erste `@media print`
> des Projekts** (Web 20.1.0, S10/AP3). Sie ist kein neuer Baustein: Sie
> benutzt die **Lesespalte** wie Impressum und Datenschutz, dazu Meldung
> (`.meldung-warn`), Wertekasten (`.codeblock-wert`), Feldhinweis und Knopf.
> Was sie zu einem eigenen Typ macht, ist ihr
> **Zweck** — sie wird gedruckt, nicht gelesen. Das Schlüsselblatt ist bis
> auf Weiteres die einzige; sie entsteht nur da, wo der Ausdruck der Zweck
> ist und nicht eine Bequemlichkeit.
>
> **Drei Regeln im Druckblock, mehr nicht** (Stylesheet, Abschnitt 26). Ein
> Druck-Stylesheet, das jede Seite umgestaltet, ist eine zweite Oberfläche
> mit eigenen Fehlern — und niemand sieht sie sich an, weil niemand druckt.
> Gestaltet wird die eine Seite, die gedruckt werden soll:
>
> | Regel | Was sie tut | Warum |
> |---|---|---|
> | `.nur-bildschirm { display: none }` | nimmt Knöpfe und Rückweg vom Papier | Ein Knopf auf einem Ausdruck ist ein Kasten, der nichts tut |
> | `background: none` auf Blattseite, Rahmen, Inhalt | keine Fläche auf Papier | `--schnee`/`--rauch` drucken als grauer Kasten und kosten Tinte. **`none`, nicht Weiß:** Die Skala ist geschlossen, und einen Token für Weiß gibt es nicht — `--schnee` ist `#FFFCFA`. Was durchscheint, ist das Papier |
> | `break-inside: avoid` auf `.blatt-wert`, `break-after: avoid` auf `h2` | kein Seitenumbruch mitten im Wert | Ein Schlüssel über zwei Blätter ist beim Abtippen die Stelle, an der eine Gruppe verlorengeht — und ein halb abgetippter Schlüssel sieht aus wie ein falscher |
>
> **`.blatt-wert` ist `.codeblock-wert` mit zwei Änderungen:** Er darf
> umbrechen (sechzehn Vierergruppen passen in keine Zeile von 210 mm) und er
> bricht **nur zwischen** den Gruppen (`word-break: keep-all`).
> `break-all` zerschnitte eine Gruppe mitten durch, und dann zählt beim
> Abtippen niemand mehr nach. Gemessen bei 718 px (210 mm abzüglich Rand) in
> `media: print`: **16 Gruppen, 0 zerschnitten, 0 waagerechter Überlauf**
> (`tools/proben/anteil/betriebslauf.mjs`, Abschnitt 2).

### 10.2 Rezept: eine neue Inhaltsseite

1. `ui_seite_start(['titel' => '…'])`
2. `ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage'])`
3. `ui_titelzeile(['titel' => '…', 'zurueck' => […]])` — **nicht** ein blankes
   `<h1>`; der Rückweg gehört dazu.
4. `ui_meldung($hinweis, $fehler)` direkt darunter.
5. `<p class="seiten-erklaerung">` (oder `'unter'` der Titelzeile) —
   höchstens **ein Satz**; alles Erklärende steht im Handbuch, und die Karte
   verweist darauf (Kapitel 6, Erklärtext). Wer die Seite zum zehnten Mal
   öffnet, liest sie nicht mehr und muss trotzdem daran vorbei.
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
| `tools/quelltext/vollstaendigkeit.py` | Ist etwas verlorengegangen? Steht jeder Wert an der einen Stelle? |
| `tools/screenshots/aufnehmen.mjs` | Sieht es in allen zehn Breiten so aus, wie es soll? Überlauf, Konsolenfehler, Knopfhöhen; rollende Behälter genannt. |
| `tools/screenshots/kontrast.py` | Erreicht jedes Farbpaar der Token seinen Sollwert? |
| `tools/erzeugen/design.py` | Erzeugt die Tabellen dieses Dokuments aus den Quellen. |
| `tools/quelltext/textprobe.py` | Sprechen Oberfläche und Dokumentation neutral von Land und Luft? |
| `tools/stilvergleich/` | Hat sich am Erscheinungsbild etwas geändert, das nicht geplant war? |
| `tools/bedienprobe/probe.mjs` | Tut ein Bedienelement, was es soll — wenn man es **bedient**? Je Weg eine Zahl. |

**Der Stilvergleich hat während P3 geruht** und ist in O12 neu geeicht: Die
Frage „hat sich etwas geändert?" ist in einer Phase, in der sich alles ändert,
keine. An seine Stelle traten `vollstaendigkeit` und `screenshots`. Ab P4
wacht er wieder — und dann gilt: **Bei einer beabsichtigten
Gestaltungsänderung ist das Ergebnis keine Null, sondern eine Liste.** Sie
wird gegen die Liste der geplanten Änderungen gehalten; jede Abweichung
darüber hinaus ist unbeabsichtigt und wird geklärt, bevor committet wird.

**Und bis S9 hat kein Prüfmittel je ein Element bedient.** Der Bilderlauf
fotografiert, die Vollständigkeit liest das Stylesheet, die Linkprobe folgt
Adressen. Zwei gemeldete Fehler sind genau dort hindurchgelaufen — ein Knopf,
der auf 404 führte (Nr. 148), und eine Trefferliste, die jeden Klick verlor,
der länger als 150 ms dauerte (Nr. 102). Die **Klickprobe** (S9, E-S9-16)
schließt diese Lücke, und ihr erster Befund ist ein Satz über Werkzeuge:
`locator.click()` hält die Taste rund 10 ms und findet Nr. 102 deshalb
**nicht**. Ein Prüfmittel misst, was es tut, nicht was es meint.

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
| **Web 21.1.6 (R4-08)** | **Der Strich am aktiven Vorschlag ist `--orange-tief`** (9.x Vorschlagsliste): Er steht allein auf Rauch, und dort erreichte Orange 2,09:1 — die Regel aus 3.1, derselbe Tausch wie beim Reiter (E-P5c-79). **3.4 um acht Paare und fünf Ausnahmen ergänzt**: `kontrast.py` leitet die Paare seither aus dem Stylesheet ab (Nr. 116). **3.2 berichtigt**: „-tief = 4,5:1 auf Schnee" galt für Orange tief nie (4,32:1, E-R4-30). **Kein neues Token, kein neuer Farbwert, kein neuer Baustein.** |
| **Web 20.39.0 (P5c/AP2)** | **9.37 neu — Reiter**, freigegeben mit M-P5c-01a (E-P5c-25): Wechsel zwischen gleichrangigen Sichten einer Seite, serverseitig, schmal rollend im eigenen Behälter; Seiten mit Reitern tragen keine Unterpunkte in der Leiste. Der Strich des aktiven Reiters steht in `--orange-tief` statt im `--orange` des Mockups (2,09 → 4,04 : 1 auf Rauch, E-P5c-79); Kapitel 3.4 nennt die Rolle beim Paar „Orange tief auf Rauch". **9.2 um die aufklappbare Zeile `.zeile-mehr` ergänzt** (E-P5c-26) samt Gegenregel zu `.zeile:first-child` (F-P5c-13) und dem Schlüssel `aktionsspalte`. **9.18a um die Helfer `ui_listenkopf()` und `ui_listenfuss()`** — Suche, Filterpillen (eine mit Kreuz), Auswahlfeld und Seitenwahl sind ein Weg; `admin_users.php` ist umgezogen, das Register hält die Klassen außerhalb von `ui.php` auf null (F-P5c-54). Kapitel 9.0 um drei Zeilen. **Ein neues Symbol** — `protokoll.svg`, Tabler „list" (Vorrat 57 → 58), für den Menüpunkt Verwaltung → Protokoll. **Kein neues Token, kein neuer Farbwert.** Die erzeugten Tabellen neu erzeugt: Bausteine **44 → 48** Funktionen (`ui_zeile_mehr()`, `ui_reiter()`, `ui_listenkopf()`, `ui_listenfuss()`), Medienblöcke **24 → 25** (die Protokollliste ab 720 px), Symbole **57 → 58**. In Kapitel 7 stand unter der erzeugten Schwellentabelle eine **zweite, veraltete Summenzeile** („22 Medienblöcke") — ein Rest eines früheren Einsetzens von Hand; beim Ersetzen bis zur nächsten Überschrift ist sie gefallen. |
| **Web 20.38.0 (P5c/AP1)** | **9.36 neu — Streifen über dem Inhalt und Kopfleiste mit Etikett**, freigegeben mit M-P5c-02 (a). Ein Behälter `.hinweise` für bis zu vier Streifen in fester Reihenfolge (Umgebung → Ankündigung → Demo → Datenschutz), zwei Varianten vorhandener Bausteine (`.hinweis-umgebung` am Hinweisstreifen, `.meldung-ankuendigung` an der Meldung) und eine Variante der Kopfleiste (`.kopf-umgebung`, aktiver Punkt in `--orange-hell`, Variante A, E-P5c-59). Kapitel **3.4** um die drei Paare auf Rot ergänzt und um das Paar „Dunkelblau auf Orange hell", das seit Web 16.3.0 im Werkzeug stand und hier fehlte — **25 Paare, 0 verfehlt**. Kapitel 9.0 um eine Zeile. **Die vier erzeugten Tabellen neu erzeugt** — sie standen auf einem älteren Stand: Bausteine **40 → 44** Funktionen (drei aus diesem Paket, dazu `ui_tabellen_bootstrap()` aus Schritt 15, das bis dahin fehlte), Verwendungszahlen der Token und Symbole nachgezogen; `tools/erzeugen/design.py` kennt die beiden neuen Funktionen in seiner Liste der abweichenden Namen. **Kein neues Token, kein neuer Farbwert, kein neues Symbol.** |
| **Web 20.1.0 (S10/AP3)** | **10.1 um einen sechsten Seitentyp ergänzt: die Druckseite** — und mit ihr das **erste `@media print` des Projekts** (Stylesheet, Abschnitt 26, drei Regeln). Kein neuer Baustein: Das Schlüsselblatt benutzt Lesespalte, Meldung, Wertekasten, Feldhinweis und Knopf; was es zum eigenen Typ macht, ist sein Zweck. Eine neue Klasse mit Regel — `.blatt-wert`, `.codeblock-wert` mit zwei Änderungen (darf umbrechen, bricht **nur zwischen** den Vierergruppen). **Kein neues Token, kein neuer Farbwert, kein neues Symbol:** Die Flächenregel im Druck setzt `background: none` statt eines weißen Hexwerts, weil die Skala geschlossen ist und es keinen Token für Weiß gibt. Kapitel **9.6** um den dritten durchgerutschten Plakettenton ergänzt (`ok`, zwei Altstellen ohne Regel, Backlog Nr. 36). Gemessen: Vollständigkeit **Hexfarben außerhalb `:root` 0**, Befunde 329 → 335 (die sechs sind das `→` der Pfadschreibweise *Betrieb → Servereinstellungen*); Bilderlauf `43b`, `45`, `48`, `48a` in acht Breiten, **beide Bedienhöhen**, je **0 Überlauf / 0 Knöpfe falscher Höhe**; Kontraste **22 Paare, 0 verfehlt**; Druckansicht bei 718 px (210 mm) **16 Gruppen, 0 zerschnitten, 0 Überlauf**. |
| **Web 19.6.0 (Mockup-Runde 9c / AP4)** | **9.12 um zwei Absätze ergänzt** (E-P3-27 fortgeschrieben): Das Blatt **fährt auf** (`translateY(100%)` → `.blatt-auf`, `--dauer`, am Schreibtisch ausdrücklich nicht), und der **Öffner bleibt markiert**, solange sein Blatt offen ist. Kapitel **3.1** sagt dazu, dass Orange seither auch „hier ist gerade etwas offen" heißt — als Fortsetzung von „hier wird gehandelt", nicht als zweite Bedeutung. Die Markierung steht **am Attribut** `[data-blatt][aria-expanded="true"]` und erreicht damit alle vier Bauarten von Öffnern (6 × `ui_aktionen()`, 9 × `ui_zeilenaktionen()`, der Pin-Knopf des Ortsfelds, 3 handgeschriebene Sortierblatt-Knöpfe) — E-MR-25. **`--dauer` von .18 s auf .24 s** für die ganze Anwendung (E-MR-22, F-MR-12); der Grundsatz in Kapitel 6 nennt den neuen Wert. **Kein neues Token** — die Fassung D4 benutzt `--orange-hell` und `--orange-tief`, das Kontrastpaar „Orange tief auf Orange hell" war schon gerechnet. **Kein neues Symbol, kein neuer Baustein.** Gemessen in drei Motoren, fünf Öffner, mit und ohne abbestellte Bewegung: Fläche und Schrift überall richtig, nach `Escape` `hidden=true`, `aria-expanded=false`, Fokus zurück am Knopf; mit abbestellter Bewegung 0,01 ms und kein Zwischenbild. |
| **Web 19.5.1 (Mockup-Runde 9c / AP3b)** | **9.35 nachgetragen** (siehe dort — die Kapitelpflicht aus 1.3 war mit 19.5.0 übersehen worden) und **9.7 um einen Absatz ergänzt:** Ein `<select>` bekommt `contain:paint`, weil WebKit den längsten Eintrag in den Überlauf des Kastens rechnet und `import.php` bei 360 px dadurch um 6 px überlief (Backlog Nr. 185) — gefunden vom ersten dreifachen Bilderlauf. **Kein neues Token, kein neues Symbol, kein neuer Baustein.** Gemessen in drei Motoren: Kaskade **758 → 759 Regeln, 0 entfallen, 1 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**; berechnete Stile **46 150 Elementmessungen, 104 Abweichungen** — 8 Auswahlfelder × 13 Breiten, und die einzige geänderte Eigenschaft ist `contain: none → paint`. Chromium, Firefox und WebKit melden dieselben Zahlen. |
| **Web 19.5.0 (Mockup-Runde 9c / AP3)** | **Neues Token `--karte-gross`** (`min(60vh, 520px)`; Tabelle 100 → 101) und **zwei neue Symbole** — `karte-gross.svg` und `karte-breit.svg`, Tabler „arrows-vertical" und „arrows-horizontal" (Vorrat 53 → 55). Der Baustein dazu ist erst mit 19.5.1 als **9.35** beschrieben worden; diese Zeile hält fest, dass er mit 19.5.0 entstanden ist. **Kein neuer Farbwert.** |
| **Web 19.4.2 (Mockup-Runde 9c / AP2)** | **Zwei neue abgeleitete Token** — `--symbol-winzig` (`calc(var(--symbol-klein) - var(--abstand-1))`, 12 px) und `--ziel-chip` (`calc(var(--symbol-gross) + var(--abstand-1))`, 28 px); dazu `--symbol-text` (`1em`) und die Klasse `.symbol-text` für ein Symbol, das **im Satz** steht und mit der Schrift wächst (Tabelle 97 → 100). Das Entfernen-Zeichen des Chips ist ein Symbol geworden, sein Treffziel wächst von 17 × 15 auf **28 × 28 px** (F-MR-6a/6b, E-MR-18: 6 px zum Text wie zum Rand). **Kein neuer Farbwert, kein neues Symbol** — `schliessen` und `warnung` lagen im Vorrat. |
| **Web 19.4.1 (Mockup-Runde 9c / Nr. 182)** | **9.34 fortgeschrieben:** Aus „Was sie nicht kann" wird „Wie sie das Sichtfenster findet". Die Kopfzeile nimmt über eine Container-Abfrage (`container-type:inline-size` an `.imp-roll`, `width:100cqi`) die **sichtbare** Breite statt der Tabellenbreite an und heftet sich mit `position:sticky;left:0` an den linken Rand. **Kein JavaScript** — beide Fassungen (Container-Abfrage und gemessene Zahl) sind bei sechs Fensterbreiten auf den Pixel gleich. Freigegeben mit **M-MR-05, F-MR-14 = Weg B**; Weg C (je Gruppe eine eigene Tabelle) ist nach einer Kartierung mit **58 Befunden, 22 davon „bricht"** verworfen worden — er hätte die Spaltenflucht gebrochen, die in der Abnahme von Nr. 182 steht. Gemessen: 7 Breiten von 360 bis 1920 px, Datum/Besatzung/Plakette/Auswahl in **jeder** im Sichtfenster, **0** waagerechter Überlauf, keine Konsolenfehler. **Kein neues Token, kein neues Symbol, kein neuer Baustein.** |
| **Web 19.4.0 (Mockup-Runde 9c / AP1)** | **9.34 neu — Kopfzeile einer Tagesgruppe** (`.imp-daygroup`), freigegeben mit M-MR-01 Variante A (F-MR-1/F-MR-2/F-MR-3). Die Kopfzeile der Importvorschau war eine Datenzeile mit `<strong>`; sie trägt jetzt Rauch, eine kräftige Oberlinie und das Datum in Kopfschrift, und das Datum steht deutsch. Die Warnung „abweichende Crew" ist eine `.plakette-orange` geworden — `imp-warn` ist ersatzlos gestrichen, weil eine zweite Darstellung für „Zustand, der Aufmerksamkeit will" den Vorrat vergrößert hätte, ohne etwas zu können. **Eine begründete Abweichung am Baustein Plakette:** in dieser Kopfzeile darf sie umbrechen. Gemessen: `pruefen.py` „im Markup ohne Regel, als `[offen]` vermerkt" **2 → 0**, Sollmenge ohne Gegenstück **52 → 50**, Hexfarben außerhalb `:root` **0**; im Browser 400/720/1280 px, **0** waagerechter Überlauf, keine Konsolenfehler. **Kein neues Token, kein neues Symbol.** |
| **13.09.2026 (Textpflege, keine Auslieferung)** | **2.5** berichtigt: „B1 erledigt, nachgemessen" traf seit dem Commit „Update Logos" nicht mehr zu (Backlog Nr. 62). Der Absatz sagt jetzt den gemessenen Stand vom 13.09.2026 und die Entscheidung vom 12.09.2026, neue Vorlagen anzufordern. **Zwei Nachbesserungen am selben Tag:** Der Absatz nannte die weiße Fassung in Prosa statt beim Dateinamen und war damit der einzige Treffer der Wortliste außerhalb der Ausnahmeliste (jetzt `gen-em_logo_helicopter_weiss.svg`, 0 Treffer) — und er zählte `gen-em_logo_nef.png` zu den richtigen Dateien, obwohl sie den **alten** Korpuswert `#1D0E0A` trägt, genau wie die `.svg` daneben, die derselbe Absatz als falsch führt. Alle acht Dateien sind nachgemessen (SVG-Farbwerte und dekodierte Bildpunkte der PNG): richtig sind die beiden Fassungen **ohne** Korpus, `gen-em_logo_nef_weiss.svg` und `gen-em_logo_nef_weiss.png`. Die beiden PNG der Luftmarke tragen die alten Werte um ein bis zwei Stufen je Kanal verschoben, weil sie gerastert sind. |
| **Web 16.1.1 (S9)** | Kapitel 7: Im Band 1024–1199 px rückt das Akkordeon je Ebene **4 statt 8 px** ein, und der Abstand der Diensttagszeile geht von 8 auf **4 px** (Freigabe M-S9-11, Weg 2). Gemessen: dem Nebentext stehen dort **64–79 px** statt 48–63 zur Verfügung — dreizehn Kurznamen, **keiner** mehr mit Auslassungszeichen (vorher zehn). Der Abstand ist mit `:not(.leiste-gruppe)` eingegrenzt, weil die Zeilenklasse auch Leistenfuß, Schubladen-Hauptpunkte und Einstellungsmenü trägt; nachgemessen bleiben die bei 8 px. **Keine neue Schwelle, kein neues Token** — 4 px ist `--abstand-1`. |
| **Web 17.1.1 (S9/AP5-6)** | **9.32 Kartenfilter**, Punkt 3 berichtigt: Die Anlegen-Formulare in der Liste gibt es seit Web 17.0.0 nicht mehr — die Regel bleibt als Netz stehen, und die Klickprobe misst seither **0 Formulare UND „Anlegen" sichtbar**, auch bei null Treffern. Punkt 4 nachgezogen: Der Leerzustand sagt jetzt „Leere den Filter, um wieder alle zu sehen" statt „…, um etwas anzulegen" — der alte Satz beschrieb eine Sackgasse, die es nicht mehr gibt. **Keine Regel im Stylesheet berührt.** |
| **Web 17.0.0 (S9/AP5-4)** | **9.11 Dialog** um drei Absätze ergänzt: Ein Formulardialog **rollt in sich** (Kopf und Fuß fest, `.dialog-inhalt` mit `overflow-y:auto`, Höhe `100dvh` minus 24 px) — der Rettungsmittel-Dialog ist am Handy höher als das Glas, und ohne die Angabe kappte die Browservorgabe den Fuß mit „Anlegen“ ab; **`display` gehört an `.dialog[open]`** (Fund F-S9-U-27: eine Regel des Browsers verliert gegen jede Regel des Stylesheets, und `.dialog{display:flex}` machte alle fünf geschlossenen Dialoge einer Standortseite sichtbar); und **ein Dialog kann einen zweiten öffnen** (Ortsfeld → Kartendialog, zwei modale Ebenen, vom Browser getragen). Die Zahlen: Kaskade **741 → 743 Regeln, 0 entfallen, 10 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**; berechnete Stile **45 500 Elementmessungen, 936 Abweichungen**, sämtlich an `dialog`, `.dialog`, `.dialog-kopf`, `.dialog-inhalt`, `.dialog-fuss` und dem `<form>` darin — also genau die zehn neuen Regeln; Pseudoprobe dieselben 936. **Kein neues Token, kein neuer Baustein, kein neues Symbol**; der Vorrat bleibt bei 39 Funktionen. |
| **Web 16.3.0 (S9/AP5-3)** | **Drei neue Kapitel: 9.31 Sprungliste** (Pille mit Artzeichen, ab sechs Einträgen, Zielzustand `.aktiv` statt `.ziel`, angesprungene Zeile über `:target`), **9.32 Kartenfilter** (Feld mit Lupe, filtert im Browser; heißt nicht `.filterfeld` wegen `.filterfelder`, und ist 44/36 hoch statt 48 wie `.suchfeld`) und **9.33 „Zum Anfang"** (nachgetragen — der Baustein steht seit Web 16.2.0 in der erzeugten Tabelle und hatte keinen Prosa-Eintrag). Kapitel 9.0 um **vier** Zeilen ergänzt: Die eine Zeile „Sprungmarken → Unterpunkte der Leiste, **nicht** von Hand" beschrieb allein die Seitenebene und verbot dem Wortlaut nach, was AP5 mit Mockup gebaut hat. Kapitel 7 und 9.10: die Ausnahme `.kennzahl-raster-3` — drei Spalten in **jeder** Breite. Kapitel 4: neues Kontrastpaar „Dunkelblau auf Orange hell" (21 → **22** gerechnete Paare); die Kombination steht seit O6 an `.kennzahl.aktiv` und `.listenfilter.aktiv` in der Anwendung und war nie gerechnet. **Kein neues Token, kein neues Symbol.** Zwei Berichtigungen beim Gegenlesen: Der Absatz „Kein Symbol am Knopf" stand in 9.18a und gehört zu 9.18; `--radius` (10 px) ist nicht „die kleinste Rundung der Skala" — das ist `--radius-klein` (6 px). |
| **Web 16.1.0 (S9/AP4a)** | Kapitel 7 (Schwellen und Verhalten je Baustein): Der Nebentext der Leiste hat **drei** Zustände statt zweier — unter 1024 px jeder Name, im Band 1024–1199 px nur ein Kurzname (`.eintrag-neben.kurz`), ab 1200 px wieder jeder; im Band rückt das Akkordeon je Ebene 8 statt 12 px ein. Gemessen: Der Datumstext ist dort **76 bis 83 px** breit (Bricolage Grotesque setzt Ziffern **proportional** — `tabular-nums` nennt `.zahl,td,th,time,output`, nicht `.eintrag-text`), dem Nebentext bleiben **48 bis 55 px**, und „BW Hoch" braucht 55: **4 von 13** Datumsangaben tragen ihn ganz, 9 mit Auslassungszeichen; ohne die Einrückung keine einzige. **Keine neue Schwelle** — beide Regeln liegen in vorhandenen Medienblöcken —, **kein neues Token**: 8 px ist `--abstand-2`. Kapitel 9.7 unberührt: Die Wahlliste trägt den neuen Zusatz mit ihrem vorhandenen `zusatz`-Schlüssel. |
| **Web 15.9.0 (S9/AP3)** | Neues Kapitel **9.30 Kartenzeichen** — der Farbring ist jetzt der Rand, alle acht Außenmaße als Tabelle, die 24-px-Untergrenze am antippbaren Ringpunkt und die Warnung, dass ein `<span>` ohne `display` kein Kasten ist (Backlog Nr. 72 und Nr. 162). Zwei **abgeleitete** Token (`--geo-ringpunkt`, `--geo-symbol`); `--geo-ring` bedeutet nun Randstärke statt Schattenschrittweite. Kapitel 8: Symbolvorrat **49 → 52** (Bergwacht, Veranstaltung, Sonstiges). Kapitel 9.29 berichtigt: `.legende-linie` misst **24 × 4 px**, nicht 22 × 4. |
| **Web 15.8.0 (S9/AP2)** | Neues Kapitel **9.29 Kartendialog** — Suchfeld im Kopf (und warum nicht im Inhalt), Spur mit Ringpunkten und Legende, `fitBounds` nur bei leerem Feld. Kapitel 9.13 nachgezogen: Die drei Grenzen der Adressabfrage stehen jetzt in `assets/geocoder.js`, die Kleinzeile `.loc-datenschutz` steht **einmal je Seite**, und den Pin-Knopf rendern seither **beide** Formen von `ui_ortsfeld()` (Backlog Nr. 70). Kein neues Token. |
| **Web 15.7.0/15.7.1 (S9/AP1)** | Neues Kapitel **9.28 Vorschlagsliste** — ein Baustein für vier abgelöste Fassungen, Übernahme auf `mousedown` (Backlog Nr. 102), Gruppenzeile nach Entscheidung des Aufrufers, `z-index: 35` zwischen Speichern-Leiste und Kopfleiste. Kapitel 9.13: Der Schlüssel `datalist` ist ersatzlos entfallen. |
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
