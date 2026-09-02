# Prüfdokument — P3 „Oberflächen-Redesign", Web 9.0.0 ff.

**Programm:** Gen-EM NAdoku · **Phase:** P3 · **Konzept:**
`Konzept-P3-Oberflaeche.md` · **Zweig:** `claude/konzept-p3-umsetzen-c4zctj`
**Stand:** 30.08.2026, **nach Abschluss der Phase** — Arbeitspaket O12,
Web 9.13.0

---

## 0. Wozu dieses Dokument

Das Konzept beantwortet die Frage **„ist die Änderung belegt?"**. Dieses
Dokument beantwortet die andere: **„was muss ich noch prüfen, und wie?"**

**Abschnitt 5 ist die Arbeitsliste.** Alles davor sagt, warum sie so
aussieht.

**Die Phase ist abgeschlossen; dieses Dokument ist es damit auch.** Es ist mit
den Arbeitspaketen gewachsen und war bis O11 an mehreren Stellen ausdrücklich
unvollständig. Diese Stellen sind mit O12 nachgezogen: Abschnitt 2 nennt jetzt
zu **jedem** Paket Mittel und Zahl, Abschnitt 5 führt die Bedienwege bis O12.

**Der Umfang von Abschnitt 5.** Zwölf Pakete ergeben eine lange Liste, und
eine lange Liste wird nicht abgearbeitet. Sie ist deshalb zweigeteilt:
**5.0 ist der kurze Weg** — vierzehn Punkte, die die Phase als Ganzes
abnehmen, in etwa einer Stunde. Die Abschnitte 5.1 bis 5.16 sind die
ausführliche Fassung je Paket; sie sind das Nachschlagewerk, wenn 5.0 etwas
findet, und die Prüfliste für den, der es genau wissen will.

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

### 1.3 Was am Ende der Phase offen ist

Die Phase ist fertig; das heißt nicht, dass alles null ist. Was **nicht**
erreicht wurde, steht hier — vorn, nicht in einer Fußnote.

**Der Sollwert „0 Unicode-Zeichen als Symbol" ist nicht erreicht.** Die
Prüfung meldet 158 Treffer. 155 davon sind erklärt: 76 stehen in Kommentaren
und erreichen nie einen Browser, der Rest ist richtige Typografie in
sichtbarem Text — die Auslassungspunkte der Fortschrittsmeldungen („Datei wird
verschlüsselt…"), die Pfad-Pfeile der Hinweise („Einstellungen → Papierkorb"),
das Malzeichen in „3× RTW", die Bereichsangabe „00:00…23:59". **Drei sind
echte Symbole im Markup** und stehen als Backlog Nr. 42:

| Stelle | was | warum nicht behoben |
|---|---|---|
| `einsatz_form.php:1416` | `✕` als Rückfall, falls `edSymbol()` beim synchronen Aufbau noch nicht geladen ist | Ein Netz, kein Fehler — mit Begründung im Code |
| `assets/ortsfeld.js:197` | `×` am Koordinaten-Chip | `.rmx` ist textgroß gebaut; ein SVG hineinzusetzen heißt, den Knopf neu zu bemaßen |
| `assets/patient.js:133` | `⚠` für einen nicht entschlüsselbaren Datensatz | Steht nicht nur in einer Zelle, sondern **im Satz** („… ist mit ⚠ gekennzeichnet"). Ein SVG im Fließtext ist eine Gestaltungsfrage |

**Sechs Klassen im Markup haben keine Regel, und ob sie eine brauchen, ist
offen** (Backlog Nr. 41). Der wahrscheinlichste echte Fund darunter:
`imp-warn` — der Hinweis „abweichende Crew (…)" in der Importvorschau sieht
aus wie Fließtext. Dazu `imp-daygroup` (eine Gruppenüberschrift, die aussieht
wie eine Datenzeile), `rea-kopf`, `rea-beginn`, `rmneu`, `phasen-name`.

**55 Altklassen stehen ohne Gegenstück da** (Backlog Nr. 40). Die
Streichliste sagt zu ihnen nicht, *warum* sie verschwunden sind. Das ist
Rekonstruktionsarbeit über zehn Pakete und in O12 bewusst zurückgestellt
worden — eine Liste mit 55 Einträgen „ersatzlos entfallen" sähe vollständig
aus und sagte nichts.

**Das Handbuch ist nicht vollständig nachgezogen.** Ausdrückliche
Entscheidung: Es beschreibt die Bedienung, und die ändert sich bis 1.0 noch.
Geändert wurde nur, was ohne Wert veraltet ist — 14 Unicode-Symbolzeichen im
Text und drei Bildschirmfotos. **Wer nach diesem Dokument prüft, prüft die
Anwendung, nicht das Handbuch.**

**Kein Prüfmittel misst, ob die Gestaltungsrichtlinie trägt.** `docs/Design.md`
ist in O12 entstanden und ab sofort verbindlich. Maschinell belegt sind ihre
**Tabellen** (sie stammen aus dem Quelltext, erzeugt von `tools/design/`) und
ihre **Verweise** (jede genannte Funktion existiert). Ob „nimm `ui_zeile()`,
nicht eine Tabelle" ein guter Rat ist, zeigt erst die erste Seite, die jemand
nach ihr baut.

### 1.4 Was der Stand ist, wenn er ausgeliefert wird

**Drei Migrationen** sind in dieser Phase entstanden. Nach dem Deploy muss
eine Administratorin **`update.php`** aufrufen — sonst steht die Anwendung:

| Migration | aus | wofür |
|---|---|---|
| `2026_08_27_logo_wahl` | O8a (Web 9.7.0) | Logo-Wahl je Konto |
| `2026_08_28_last_login` | O9a (Web 9.8.0) | `users.last_login` für die Kontenliste |
| Rechtstexte | O10 (Web 9.11.0) | Tabelle `rechtstexte` |

**Ohne erhöhte `WEB_VERSION` sieht der Browser alte Dateien.** Sie steht auf
9.13.0.

**Zwei Zuarbeiten fehlen** und sind keine Fehler der Umsetzung: das echte
NEF-Logo (bis dahin steht der Platzhalter aus O1, am gestrichelten Rahmen
erkennbar) und die Impressums- und Datenschutztexte der eigenen Installation
(einzugeben über den Editor aus O10; bis dahin zeigen beide Seiten ihren
Leerzustand mit Hinweis).

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

### 2.8 Nach O7 (Web 9.6.0)

| Was | Ergebnis |
|---|---|
| **Abnahme Kachelwerte (Kernzusage)** | **10 Proben · 10 ohne Abweichung · 88 Kachelwerte verglichen · 0 unerklärte Abweichungen** — fünf Zeiträume × zwei Artenansichten, gleichzeitig gegen den Vor-O7-Stand (zweiter Git-Arbeitsbaum, Port 8444) und die neue Fassung (8443) gegen **dieselbe** Datenbank; verglichen wurde der Zahlenwert, nicht der Text. Acht Abweichungen sind Schreibweise und einzeln begründet. |
| Bildaufnahme | **240 Bilder, 30 Kontaktbögen** (vorher 232/29) — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** — 48 Regeln (vorher 49), 48 gegriffen; die Ausnahme `luftrettungs-reiter` ist ausgetragen, weil der Reiter jetzt „Luft" heißt |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste — dazu **zwei Hexwerte weniger im Skript** (`zeitraum.php` las Rot und Blau fest, jetzt aus `:root`) |
| Streichliste | Sollmenge 220 — **40 mit Regel, 79 gestrichen** (sieben neue in O7) |
| Kachelsätze (1440) | Gemischt **4** Kacheln / 4 Spalten, Luft **10** / 5, Boden **8** / 4 — je Ansicht nachgezählt |
| Mobil (390) | je Ansicht genau **4** sichtbare Kacheln, „Weitere Statistik (6)" bzw. „(4)", nach Klick **8** sichtbar; Kacheln statt Tabelle; Segmentwahl **366 von 390 px** |
| Extremwert-Klick | Kachel `rgb(255,235,214)` mit Rahmen `rgb(255,143,31)`, Trägerzeile dieselbe Fläche, **derselbe** Einsatz |
| Syntax | `php -l` über alle geänderten Dateien — fehlerfrei |

**Ein Prüfmittel hat sich selbst geprüft und war falsch.** Das
Bilderwerkzeug rief `zeitraum.php` **ohne** `?y=` auf; die Seite leitet dann
auf die Startseite um. Der Kontaktbogen „14-zeitraum" zeigte seit O1
achtmal die Tagesübersicht, und die Prüfung meldete pflichtgemäß „0
Überlauf, 0 Konsolenfehler" für eine Seite, die sie nie gesehen hatte
(F-P3-AH). Behoben; die Übersicht steht jetzt mit zwei Seiten im Lauf (Jahr
und Monat), daher 232 → 240 Bilder. **Für die früheren Pakete heißt das:
Die Zahl 232 war richtig, aber eine der 29 Seiten war nicht die, die
draufstand.**

### 2.9 Nach O8a (Web 9.7.0) — **mit Migration**

**Diese Fassung braucht `update.php`.** Ohne die Spalte `users.logo_wahl`
scheitert jede Anmeldung, weil `login.php` sie mitliest. Lokal ist die
Migration gelaufen; auf dem Produktivserver ist sie ein Handgriff, den kein
Werkzeug hier ersetzen kann.

| Was | Ergebnis |
|---|---|
| **Logo-Wahl (Kernzusage)** | Anmeldeseite zeigt den Standard; „Standard" und „Hubschrauber" liefern dasselbe Logo; „Fahrzeug" wechselt **Kopfleiste und Favicon** gemeinsam. „Wechselnd": über **5 Seiten einer Sitzung stabil**, über **20 frische Anmeldungen 11 zu 9** — beide Logos kamen vor. 0 Konsolenfehler |
| Standorte, Desktop | anlegen · Vorbelegung setzen · zurücksetzen · bearbeiten · löschen — alle fünf Wege durchlaufen, Bestand danach unverändert |
| Standorte, mobil (390) | Knopfreihe verborgen, „⋯" sichtbar, Blatt mit Zeilentitel und denselben Einträgen |
| Lage-Feld (F-P3-AI) | Namensfeld, Suchfeld „Adresse oder Ort suchen", Lupe, zwei Koordinatenfelder — **alle vier wieder vorhanden** |
| Passwortstärke | 4 Segmente; 1 rot `rgb(214,51,56)` · 1–2 orange · 4 dunkelblau `rgb(26,46,77)`; leeres Feld = kein Markup |
| Bildaufnahme | 240 Bilder, 30 Kontaktbögen — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** — **53 Regeln** (vorher 48), 53 gegriffen; sechs neue für die Logo-Wahl (Klasse Homonym), die veraltete `logowahl-hubschrauber` ausgetragen |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

### 2.10 Nach O8b (Web 9.7.1)

| Was | Ergebnis |
|---|---|
| Rettungsmittel-Reiter (1440) | zwei Standort-Karten, zugeklappt mit Zahl; aufgeklappt fünf Abschnitte mit **2 / 11 / 5 / 5 / 3** Zeilen, je ein Formular. **0 doppelte Element-Kennungen**, 0 Konsolenfehler, **0** der alten Klassen noch im Markup |
| Geräte-Reiter | zwei Zeilen mit Plaketten und drei Handlungen; Löschrückfrage kommt **vollständig** an; 0 doppelte Kennungen |
| **Lage-Feld (F-P3-AI, jetzt wirklich)** | Vorschlag „Kempten (Allgäu), 87435 Kempten" erscheint, Übernahme setzt **47.7267 / 10.3167** als Chip, **Name unverändert** |
| **F-P3-AJ vermessen** | Antwort nach 0 ms: 30 ms → 1 Eintrag, 80 ms → 1, **160 ms → 0** (die Liste wurde gelöscht). Nach der Behebung: 1 Eintrag über den ganzen Verlauf. Antwort nach 250 ms: vorher wie nachher ab 300 ms 1 Eintrag |
| Bildaufnahme | 240 Bilder, 30 Kontaktbögen — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** — 57 Regeln, 57 gegriffen |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Streichliste | Sollmenge 220 — 45 mit Regel, **84 gestrichen** (fünf neue in O8b) |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

**Eine Behebung aus O8a war keine.** Das wiederhergestellte Lage-Feld trug
dieselbe Element-Kennung wie das Namensfeld; `getElementById` findet das
erste, also den Namen. Das Feld war da, sichtbar und beschriftet — und hing
an nichts. Der Prüfsatz aus 2.9 („alle vier wieder vorhanden") war richtig
und trug trotzdem nicht: Er hat gezählt, was dasteht, statt zu prüfen, was
geschieht. Seit O8b prüft die Sonde die **Wirkung** — Vorschlag, Übernahme,
Koordinaten, unveränderter Name.

### 2.11 Nach O8c (Web 9.7.2) — O8 vollständig

| Was | Ergebnis |
|---|---|
| **Import-Durchlauf** (Referenzarchiv) | entsperren → Datei wählen → Schritt 2 **und** 3 erscheinen; Bilanz „13 Diensttage, 82 Einsätze, 1 Hinweise, 0 Fehler, 82 Dubletten"; **96** Tabellenzeilen; „Import ausführen" bleibt **gesperrt** (0 Einsätze bereit — alles schon vorhanden). **Nichts übernommen**, Bestand unverändert |
| Zeilenwahl | „Nur Probleme" **15** · „Nur Dubletten" **96** · „Alle Zeilen" **96** |
| Backup-Reiter | drei Karten; der Kontopasswort-Schalter benennt das Feld um, blendet Wiederholung und Stärkebalken aus, zeigt den Hinweis; ein zu kurzes Passwort meldet sich **rot** |
| Doppelte Element-Kennungen | **0** auf beiden Seiten |
| Waagerechter Überlauf | **keiner** |
| Alte Klassen im Markup | **0** (`rolechecks`, `settings-form`, `alert`, `btn-plain`, `imp-wrap`) |
| Bildaufnahme | 240 Bilder, 30 Kontaktbögen — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls |
| Kontraste | 21 Paare, **0 verfehlt** |
| Wortliste | **0 / 0 / 0** — 57 Regeln, 57 gegriffen |
| Werte außerhalb der Token | 0 Hex, 0 rgb(), 0 Schriftgrößen, 0 Pixelmaße, 0 50px-Reste |
| Streichliste | Sollmenge 220 — 45 mit Regel, **87 gestrichen** |
| Syntax | `php -l` und `node --check` über alle geänderten Dateien — fehlerfrei |

### 2.12 Nach O9a (Web 9.8.0) — **mit Migration**

| Was | Ergebnis |
|---|---|
| Bedienproben Kontoseite | **29 Proben** (Playwright, 1440 px, Admin-Konto, gegen ein eigens angelegtes Probekonto), alle erwartungsgemäß, **0 Konsolenmeldungen** |
| Bibliotheksproben Sicherungsregeln | **14 Proben** gegen einen Probeordner mit fünf Paketen: Verdrängung hält das freigegebene und das jüngste Paket |
| Migration `2026_08_28_last_login` | Spalte von Hand gelöscht, `update.php` aufgerufen → Migration mit Klartextnamen genannt und ausgeführt; danach eine Anmeldung schreibt den Zeitpunkt, die übrigen Konten bleiben `NULL` |
| Kontolöschung | Konto **und** Sicherungsordner danach fort |
| Bildaufnahme | 240 Bilder (30 Seiten × 8 Breiten) — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des Solls |
| Wortliste | 203 Treffer, 203 erklärt, **0 außerhalb**, 0 ungenutzte Ausnahmen |
| Kontraste | 21 Paare, **0 verfehlt** |
| Syntax | `php -l` über alle geänderten Dateien — fehlerfrei |

### 2.13 Nach O9b (Web 9.9.0) — die Abnahme mit 304 Konten

Der einzige Ort der Phase, an dem eine **Menge** geprüft wurde.
`tools/pruefkonten/` legt 300 Konten mit festem Zufallsstartwert an, dazu die
vier des Referenzdatensatzes: 180 aktuell, 28 überfällig, 86 nie gesichert,
6 ohne Kontokennung, 6 Admins, 55 ohne Gerät, 44 nie angemeldet.

| Was | Ergebnis |
|---|---|
| Liste, Filter, Sortierung, Seitenwechsel | **35 Proben**, alle erwartungsgemäß, **0 Konsolenmeldungen**. Seite 1: 50 Zeilen, „Konten 1–50 von 304"; Seite 7: 4 Zeilen, „301–304 von 304"; `?s=99` fällt auf die letzte Seite, `?s=0` auf die erste |
| Anlegen, Sammelaktion, mobile Fassung | **19 Proben**. Zwei Konten „nie gesichert" stehen nach „Auswahl sichern" auf „aktuell"; unter 720 px ist die Tabelle `display:none` und dieselben 50 Zeilen stehen als Kacheln |
| Auswahl über Seitengrenzen | 2 Kästchen auf Seite 1 + 1 auf Seite 2 → „3 ausgewählt"; nach der Rückkehr sind auf Seite 1 wieder genau die beiden gesetzt |
| Umlaut-Sortierung | „Ömer Berger" an Position **211 von 304**, zwischen „Nora Vogel" und den P-Namen. Vorher stand es hinter allen 303 anderen |
| Mengen | `edbak_staende()` 3,2 ms für 209 Ordner; Kontenabfrage 3,3 ms für 304 Zeilen; ganzer Seitenaufruf **103 ms**. Ohne den Zwischenspeicher der Marken allein 27,7 ms für die 304 Wertungen |
| Bildaufnahme mit Testbestand | 16 Bilder (`40-nutzerinnen`, `41-kontoseite` × 8 Breiten) — 0/0/0; danach der volle Lauf ohne Testbestand: 240 Bilder, ebenfalls 0/0/0 |
| Gegenprobe Suchseite (F-P3-AM) | Gruppenzähler weiterhin `rgb(217, 236, 253)`, Filterknopf wieder 48 px |
| Syntax | `php -l`, `node --check` — fehlerfrei |

### 2.14 Nach O9c (Web 9.10.0) und der Berichtigung (Web 9.10.1)

**Zwei Zahlen aus O9c waren falsch, und beide aus demselben Grund: Sie waren
zu früh gemessen.** Das steht hier so ausführlich, weil es die Lehre der
ganzen Phase ist.

| Was | Ergebnis |
|---|---|
| Vollständigkeit | 220 Altklassen, 45 mit Regel, **95 auf der Streichliste** (+14), 80 ohne Gegenstück (vorher 88); „im Markup ohne Regel" **54** (vorher 68) |
| Wortliste | ~~58 Regeln, 0 Treffer~~ → **falsch.** Das Werkzeug lief **vor** dem Schreiben der Dokumentation; die danach entstandenen Logo-Abschnitte brachten **5 Treffer**. Berichtigt mit 9.10.1: 62 Regeln, 62 gegriffen, **0 Treffer** |
| Bilderlauf | ~~248 Bilder, 0 Überlauf~~ → **wertlos** (F-P3-AQ). 22 der 31 Seiten zeigten die **Anmeldeseite**, sechs weitere die Startseite. Gemessen war die Anmeldeseite in acht Breiten. Berichtigt mit 9.10.1: 248 Bilder, **248 verschiedene Prüfsummen**, 0/0/0 |
| Kontraste | 21 Paare, **0 verfehlt** |
| Syntax | `php -l` über 11 geänderte Dateien, 0 Fehler |

**Was daraus folgt und seither gilt** (`CLAUDE.md` §6): *Die Prüfmittel laufen
zuletzt, nicht zwischendurch.* Und: *Eine grüne Zahl ist erst dann ein Beleg,
wenn sie das Gemessene benennt.* Der Bilderlauf prüft seither selbst, ob eine
Seite auf die Anmeldung zurückgefallen ist, und fotografiert sie dann **nicht**,
sondern meldet sie.

### 2.15 Nach O10 (Web 9.11.0) — **mit Migration**

| Was | Ergebnis |
|---|---|
| Rechtstext-Renderer | `tools/rechtstexte/` (neu): **81 Proben, 0 fehlgeschlagen** — 15 Umfang, 12 rohes HTML, 13 Linkziele, 5 Attribut-Injektion, 6 nicht unterstützte Formen, 8 Zeichen und Kodierung, 6 Ränder, 16 übrige Funktionen. Dazu **65 Ausgaben gegen eine Positivliste** erlaubter Tags (`h2 h3 p br ul ol li a`) und Attribute (`href`) |
| Vollständigkeit | 220 Altklassen, 45 mit Regel, **99 auf der Streichliste** (+4), 76 ohne Gegenstück |
| Wortliste | 62 Regeln, 62 gegriffen, 0 ungenutzt, **0 Treffer** — gefahren **nach** der Dokumentation |
| Kontraste | 21 Paare, 0 verfehlt. Die Versionsnummer der Fußzeile stieg von **1,53:1 auf 5,30:1** |
| Bilderlauf | 34 Seiten × 8 Breiten = **272 Bilder**, 0/0/0. Neu: `04-impressum`, `05-datenschutz`, `43a-rechtstexte` |
| `schema.sql` | in eine Wegwerfdatenbank eingespielt nach dem Verfahren von `install.php`: 33 Anweisungen, 31 Tabellen, `rechtstexte` dabei |
| Syntax | `php -l`, `node --check`, JSON-Prüfung über `seiten.json` und `ausnahmen.json` |

### 2.16 Nach O11 (Web 9.11.1 und 9.12.0)

| Was | Ergebnis |
|---|---|
| Bilderlauf | 34 Seiten × 8 Breiten = **272 Bilder**; Überlauf **0**, Konsolenfehler **0**, Knöpfe ≠ 44 px **0** |
| Gegenprobe Prüfsummen | 272 Bilder, **271 verschiedene**. Die eine Dublette ist das Paar `10-tagesuebersicht` / `11-tagesuebersicht-schublade` bei 1024 px — ab 1024 px gibt es keine Schublade, beide fotografieren dieselbe Seite. Pixelweise nachgemessen; bei 1280/1440 px unterscheiden sie sich um einen 1 × 75 px großen Streifen |
| Vollständigkeit | Streichliste-im-Markup **5 → 0**; „ohne Gegenstück" 78 → **55**; „im Markup ohne Regel" 48 → **29**; Unicode 163 → **158**; Befunde gesamt 294 → **247** |
| Wortliste | **0 / 0 / 0** bei 62 Regeln, alle gegriffen |
| `papierkorb_misch.mjs` | **15 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler** — auf zwei frisch angelegten Umlaufkonten |
| `kreislauf.py --art edbak --frisch` | **286 739 Einzelvergleiche, 0 unerklärte Abweichungen**, 16 erwartete. Das Werkzeug selbst war dafür zu reparieren (F-P3-BB) |
| Export-Knopf, vorher/nachher | 23 px, transparent, Radius 0, Textschrift → **44 px**, `rgb(255,143,31)`, Radius 10 px, Bricolage (F-P3-BA) |
| Syntax | `php -l`, `node --check`, `ast.parse` über `kreislauf.py` — fehlerfrei |

### 2.17 Nach O12 (Web 9.13.0) — der Endstand

Alle Läufe **nach** der letzten Änderung, nicht zwischendurch.

| Was | Ergebnis |
|---|---|
| Wortliste | **0** Treffer außerhalb der Ausnahmen, **0** ungenutzte Ausnahmen, **0** durchgerutschte Fallen — bei **66 Regeln, alle 66 gegriffen**. 339 Treffer gesamt, alle erklärt. Bereich (c) liest 8 normative Dokumente (neu `Design.md` und `Lizenzen.md`, ausgetragen `Branding.md`) |
| Vollständigkeit | **224 Befunde** (O1: 294, O11: 247). Alle Wertefragen **0**. Neu: „im Markup ohne Regel, Grund nicht eingetragen" **29 → 0**, davon 6 als `[offen]` weitergeführt; „ohne-regel.md: Eintrag ohne Vermerk" 0, „Eintrag ungenutzt" 0 |
| `tools/design/tabellen.py` | vier Läufe: **87** Token in 15 Gruppen · **19** Medienblöcke über 5 Breiten · **44** Symboldateien · **32** Bausteine. Die Tabellen der Design-Kapitel 4, 7, 8 und 9 stammen aus diesen Läufen |
| Bilderlauf | 34 Seiten × 8 Breiten = **272 Bilder**; Überlauf **0**, Konsolenfehler **0**, Knöpfe ≠ 44 px **0** |
| Gegenprobe Prüfsummen | 272 Bilder, **272 verschiedene** — 0 Dubletten. Nach O11 war es eine; der Unterschied ist Aufnahmerauschen im erklärten Paar, kein Fortschritt |
| Gegenprobe Anmeldeseite | Das Werkzeug prüft sie selbst (seit F-P3-AQ): Eine Seite, die auf `login.php` zurückfällt, wird nicht fotografiert, sondern gemeldet — **0** solche Meldungen |
| Kontraste | **21 Paare, 0 verfehlt**; Primärknopf 5,97:1; drei benannte Ausnahmen mit Grund |
| Stilvergleich (Neueichung) | **53 638 Elementmessungen** über 4 Proben × 13 Breiten, 149 Eigenschaften je Element, **12 Abweichungen — alle zwölf die eine beabsichtigte** (`div.leiste-filter`, `width`, bei 1024/1100/1280/1440/1680/1920 px in `katalog.html` und `pseudo.html`) |
| `kaskade.py` | 625 → 627 Regeln; **0 entfallen, 2 neu** (die beiden geplanten), **0 mit anderem Endwert, 0 vertauschte Reihenfolgen** |
| Filterleiste (F-P3-BC) | im Browser bei fünf Breiten: Suche **240 / 240 / 280 / 280 / 280 px**, Tagesübersicht unverändert **220 / 220 / 260 / 260 / 260 px** |
| Syntax | `php -l` über `version.php` und `ui.php`; `py_compile` über `tabellen.py`, `proben.py`, `pruefen.py`; `node --check` über `stilvergleich.js` |

### 2.18 Der Vorher-Stand, zum Gegenhalten — und der Endstand daneben

Erhoben am Stand `main` 2e4f4fe (Web 8.0.1), bevor das Stylesheet ersetzt
wurde. Diese Zahlen sind der Maßstab, an dem sich das Ende der Phase messen
lässt:

| | vorher | Sollwert Ende P3 | **Ist am Ende** |
|---|---|---|---|
| Hexfarben außerhalb `:root` | 78 | 0 | **0** ✔ |
| `rgb()`/`rgba()` mit festen Zahlen | 8 | 0 | **0** ✔ |
| Schriftgrößen außerhalb der Skala | 71 | 0 | **0** ✔ |
| Pixelmaße außerhalb der Token | 154 | 0 | **0** ✔ |
| `50px`-Reste | 5 | 0 | **0** ✔ |
| `style="…"`-Attribute in PHP/JS | 14 | 0 | **5** — siehe unten |
| Inline-SVG mit Pfaden | 5 | 0 | **0** ✔ |
| Unicode-Zeichen als Symbol | 147 | 0 | **158**, davon 3 echte — Abschnitt 1.3 |
| Emoji im Markup | 80 | 0 | **0** ✔ |
| Klassen im Markup ohne Regel | 22 | 0 | **0** ohne eingetragenen Grund, 6 als `[offen]` |

Sieben von zehn Sollwerten sind erreicht, und zwar vollständig: **Kein
einziger Farbwert, keine Schriftgröße und kein Pixelmaß steht mehr außerhalb
der Token.** Das war der Kern des Umbaus, und es ist maschinell nachgezählt.

Die drei übrigen sind keine Nullen, und keine davon ist eine Ausrede:

- **`style="…"` — 5 statt 0**, und alle fünf nachgesehen: viermal
  `background:<farbe>` (`index.php:379`, `geo.js:91`, `missiontable.js:162`,
  `missiontable.js:253`) und einmal
  `transform:rotate(<winkel>deg)` (`geo.js:112`). Die Farbe ist in allen vier
  Fällen die **Spurfarbe eines Diensttags**, die `geo.js` über
  `getComputedStyle` aus `--spur-1` … `--spur-8` holt; der Winkel ist die
  gemessene Fahrtrichtung. Beides sind Werte, die es erst zur Laufzeit gibt —
  für eine Farbe, die von der Position in einer Liste abhängt, und für einen
  Winkel in Grad kann es keine Regel im Stylesheet geben. Die Farben **kommen
  aus den Token**, sie umgehen sie nicht.
- **Unicode als Symbol — 158 statt 0.** 155 sind Kommentare oder richtige
  Typografie, 3 sind echt und benannt (1.3, Backlog Nr. 42).
- **Klassen ohne Regel.** Der Sollwert ist erreicht, aber anders als gedacht:
  Die 29 Namen sind nicht verschwunden, sondern **begründet** — 23 mit
  `[bleibt]`, 6 mit `[offen]`. Der Unterschied ist wesentlich: Eine Zahl, die
  auf null gedrückt wird, sagt nichts; eine Liste mit 29 Begründungen sagt zu
  jedem Namen, warum er richtig ist.

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

**Dazu je Paket, im Konzept mit Fundstelle festgehalten:** 29 + 19 + 35
Bedienproben zur Administration (O9a/b), der Angriffsversuch gegen den
Rechtstext-Renderer im echten Weg (O10: `<script>`, `javascript:`-Link und
`onerror` gespeichert und auf der **öffentlichen Seite abgemeldet** geprüft —
kein `<script>`, kein `href="javascript`, kein `<img>`), acht Inhaltsseiten
bei 360/390/1280 px auf 23 gesuchte Altklassen (O11), die Filterleiste bei
fünf Breiten (O12, F-P3-BC).

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
5. **Die Knopfhöhenmessung sucht `.knopf`.** Ein Knopf ohne diese Klasse
   fällt ihr nicht auf — genau so ist der Export-Knopf mit `btn-primary` vier
   Monate lang 23 px hoch geblieben (F-P3-BA). Die Gegenprobe dafür ist die
   Liste „im Markup ohne Regel", und die gehört **gelesen**, nicht gezählt.
   Seit O12 ist sie lesbar (`ohne-regel.md`).
6. **Der Bilderlauf zeigt nur, was in `seiten.json` steht** — und bis 9.10.1
   nicht einmal das: 176 von 248 Bildern zeigten die Anmeldeseite, und die
   Zahl „0 Überlauf" war dennoch grün (F-P3-AQ). Er prüft das seither selbst.
   Die einfachste Gegenprobe bleibt trotzdem richtig: `ls *.png | wc -l` gegen
   `md5sum *.png | cut -d' ' -f1 | sort -u | wc -l`. Stehen dort zwei
   verschiedene Zahlen, zeigen mehrere Seiten dasselbe Bild.
7. **Die Seitenprobe des Stilvergleichs sieht keine bedingten Klassen aus
   PHP-Ausdrücken.** `entphp()` schneidet `<?= … ?>` heraus; eine Klasse, die
   dort entsteht (`$leiste === 'filter' ? ' leiste-filter' : ''`), fehlt in
   `seiten.html`. Aufgefallen in O12 am eigenen Eichlauf. `katalog.html`
   fängt solche Fälle, weil es je Selektor ein Element baut — wer eine Regel
   nur in der Seitenprobe prüft, prüft zu wenig.

---

## 5. Prüfliste

**Lesart:** *Weg* = was zu tun ist. *Erwartet* = was dastehen muss.
*Fehlschlag heißt* = was es bedeutet, wenn es nicht so ist — der wichtigste
Teil, denn sonst prüft man, **ob** etwas erscheint, statt **was**.

**⬤ Pflicht** — hier kann etwas kaputt sein, das kein Werkzeug gesehen hat.
**○ Sichtprüfung** — reine Bestätigung; eine Abweichung wäre überraschend.

### 5.0 Der kurze Weg — die Phase in einer Stunde

**Wer nur einmal prüfen will, prüft das hier.** Vierzehn Punkte, die die Phase
als Ganzes abnehmen. Alles darunter (5.1 bis 5.16) ist die ausführliche
Fassung je Paket — das Nachschlagewerk, wenn hier etwas auffällt.

Voraussetzung: eine Installation mit dem eigenen Datenbestand, **`update.php`
aufgerufen**, hart neu geladen (Strg+Umschalt+R).

- [ ] **K-1 ⬤ Ein Telefon, quer durch die Anwendung.** *Weg:* Auf einem
      echten Handy (nicht nur schmalem Fenster) anmelden und den ganzen Weg
      gehen: Tagesübersicht → Einsatz öffnen → bearbeiten → speichern →
      Suche → Zeitraum → Einstellungen. *Erwartet:* Nirgends muss man
      **seitwärts schieben**, um etwas zu lesen oder zu treffen.
      *Fehlschlag heißt:* Der Kern der Phase ist verfehlt. Die Zahl „0
      Überlauf" ist an Viewport-Breiten gemessen, nicht an einem Gerät — sie
      kennt weder Notch noch Adressleiste noch Systemschrift.
- [ ] **K-2 ⬤ Symbole in Safari.** *Weg:* Dieselbe Seite auf iPhone oder iPad
      öffnen. *Erwartet:* Die Strichzeichnungen erscheinen, in der Farbe
      ihrer Umgebung. *Fehlschlag heißt:* Leere Stellen = WebKit lädt den
      externen Symbolverweis nicht; schwarze Klumpen = die Strichattribute
      kommen im geklonten Baum nicht an. Beides ist ernst und in 5.1 (P-1)
      ausführlich beschrieben — **die gesamte Phase ist nur in Chromium
      geprüft.**
- [ ] **K-3 ⬤ Der Daumen trifft.** *Weg:* Auf dem Handy die kleinen Knöpfe
      antippen: das Zahnrad in der Kopfleiste, die drei Punkte an einer
      Einsatzzeile, das ✕ am Koordinaten-Chip. *Erwartet:* Jeder trifft beim
      ersten Versuch. *Fehlschlag heißt:* Die 44-px-Regel ist an einer Stelle
      nicht angekommen. Der Bilderlauf misst nur, was `.knopf` trägt.
- [ ] **K-4 ⬤ Nichts geht beim Speichern verloren.** *Weg:* Einen Einsatz mit
      **allen** Feldern öffnen (Diagnose, Alter, Einsatzort mit Koordinaten,
      Phasen, Reanimation), nichts ändern, speichern, neu laden.
      *Erwartet:* Jeder Wert steht unverändert da, die Karte zeigt denselben
      Punkt. *Fehlschlag heißt:* Ein Feld ist beim Umbau des Formulars aus
      dem Katalog gefallen — das wäre Datenverlust, nicht Gestaltung.
- [ ] **K-5 ⬤ Die Verschlüsselung trägt.** *Weg:* Nach der Anmeldung eine
      Einsatzansicht öffnen. *Erwartet:* Diagnose, Alter und Einsatzort
      stehen im **Klartext** da, ohne Entsperrdialog. *Fehlschlag heißt:*
      Punkte oder ein Warnzeichen = der Inhaltsschlüssel passt nicht. Sofort
      aufhören und **keine Sicherung erstellen**.
- [ ] **K-6 ⬤ Löschen fragt nach — einmal.** *Weg:* Einen Einsatz löschen und
      abbrechen; dann einen Diensttag löschen und abbrechen. *Erwartet:* Genau
      **eine** Rückfrage, danach steht alles noch da. *Fehlschlag heißt:*
      Zwei Rückfragen hintereinander (F-P3-AY) oder gar keine.
- [ ] **K-7 ⬤ „Löschen" ist rot.** *Weg:* Auf dem Handy an einer Einsatzzeile
      die drei Punkte antippen. *Erwartet:* Ein Blatt fährt von unten herein;
      der Eintrag „Löschen" ist **rot**, mit rotem Symbol. *Fehlschlag
      heißt:* Schwarz wie die übrigen — dann trägt der Eintrag `knopf-gefahr`
      statt `blatt-gefahr` (F-P3-AX), und die gefährlichste Handlung sieht
      aus wie die harmloseste.
- [ ] **K-8 ⬤ Die Verlassen-Warnung.** *Weg:* Ein Einsatzformular öffnen, ein
      Feld ändern, **ohne zu speichern** zurückgehen. *Erwartet:* Der Browser
      fragt nach; unten klebt eine Speichern-Leiste mit „Ungespeicherte
      Änderungen". *Fehlschlag heißt:* Kommentarloses Verlassen = dem
      Formular fehlt `forms.js` (F-P3-AV).
- [ ] **K-9 ⬤ Die Karte.** *Weg:* Einen Einsatz mit Spur öffnen; auf dem Handy
      den Vollbildknopf drücken. *Erwartet:* Kacheln laden, die Spur ist
      farbig, Vollbild geht auf **und wieder zu**. *Fehlschlag heißt:* Auf
      iOS ist der Rückfall aus F-P3-AW gebaut, aber nie in Safari geprüft —
      klemmt es dort, ist das genau dieser Fall.
- [ ] **K-10 ⬤ Sichern und Zurückholen.** *Weg:* Eine Sicherung erstellen,
      herunterladen, in einem **frischen** Konto oder einer Testinstallation
      einspielen. *Erwartet:* Die Zahlen der Bilanz stimmen; die
      zurückgeholten Einsätze tragen dieselben Werte. *Fehlschlag heißt:* Der
      Kreislauf ist mit 286 739 Einzelvergleichen maschinell geprüft — ein
      Fehler hier wäre einer im Weg, nicht im Format.
- [ ] **K-11 ○ Impressum und Datenschutz.** *Weg:* Abgemeldet
      `impressum.php` und `datenschutz.php` öffnen. *Erwartet:* Beide sind
      erreichbar. Ohne eingegebenen Text steht dort der **Leerzustand** mit
      Hinweis — das ist richtig, solange die Texte fehlen (1.4).
      *Fehlschlag heißt:* Weiterleitung auf die Anmeldung = die Seiten sind
      nicht öffentlich, und das ist ein Rechtsproblem.
- [ ] **K-12 ○ Das Logo stimmt.** *Weg:* Einstellungen → Profil, das Logo
      wechseln; Kopfleiste und Browser-Tab ansehen. *Erwartet:* Beide
      wechseln gemeinsam. *Fehlschlag heißt:* Nur eines wechselt = Favicon
      und Logo sind auseinandergelaufen (`tools/logos/`).
- [ ] **K-13 ○ Die Version steht in der Fußzeile.** *Weg:* Nach unten
      scrollen. *Erwartet:* **9.13.0**. *Fehlschlag heißt:* Eine kleinere
      Zahl = der Browser zeigt alte Dateien, und **alles darüber ist
      wertlos**. Erst hart neu laden, dann noch einmal anfangen.
- [ ] **K-14 ○ Der Warnhinweis im Import.** *Weg:* Import/Export öffnen, eine
      Datei mit abweichender Besatzung wählen, in Schritt 2 die Kopfzeile
      einer Tagesgruppe ansehen. *Erwartet:* „abweichende Crew (…)" fällt
      auf. *Fehlschlag heißt:* Es liest sich wie Fließtext — dann ist der
      offene Punkt aus 1.3 (`imp-warn`, Backlog Nr. 41) bestätigt, und das
      ist eine nützliche Antwort, kein Fehler dieser Prüfung.

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

### 5.8 Nach O7 (Web 9.6.0)

- [ ] **„Gemischt" zeigt vier Kacheln.** *Weg:* Jahresübersicht eines Jahres
      mit luft- **und** bodengebundenen Diensttagen öffnen, Segment
      „Gemischt". *Erwartet:* Genau vier Kacheln — Einsätze, Diensttage,
      Ø Einsätze / Diensttag, Sekundärtransporte —, alle in einer Reihe.
      *Fehlschlag heißt:* Acht Kacheln mit Kilometern und Dauern = der alte
      Bodensatz ist zurück, und die Summen mischen Flug- mit Fahrstrecken.
- [ ] **Luft und Boden sind unverändert.** *Weg:* Segment „Luft", dann
      „Boden". *Erwartet:* Luft 10 Kacheln in zwei Reihen zu fünf, Boden 8
      in zwei Reihen zu vier; die **Zahlen** dieselben wie vor der
      Umstellung. *Fehlschlag heißt:* Eine Zahl weicht ab — dann hat die
      Umstellung des Kachelsatzes eine Rechnung getroffen, was sie nicht
      sollte. (Anders als die Zahlen dürfen sich drei Schreibweisen
      geändert haben: Summen ohne Nachkomma, Tausendertrennung,
      „Winden-Cycles" statt „Anzahl Winden-Cycles".)
- [ ] **Vier Kacheln auf dem Handy.** *Weg:* Fenster auf 390 px, dieselbe
      Übersicht. *Erwartet:* Vier Kacheln zweispaltig, darunter „Weitere
      Statistik (6)" in der Luftansicht und „(4)" in der Bodenansicht; in
      „Gemischt" **kein** Knopf. Der Klick klappt den Rest auf, der Winkel
      dreht sich. *Fehlschlag heißt:* Alle Kacheln stehen sofort da = die
      mobile Kappung greift nicht, und die Seite ist wieder zwei
      Bildschirme lang, bevor die Karte kommt.
- [ ] **Der Extremwert findet seinen Einsatz.** *Weg:* Luftansicht, auf die
      Kachel „Längste Flugstrecke" klicken. *Erwartet:* Die Kachel wird hell
      orange mit orangem Rahmen, die zugehörige Zeile in der Liste ebenfalls
      hell orange, und die Liste rollt zu ihr. In der Beschriftung stand
      der Tag schon vorher („· 14.08."). *Fehlschlag heißt:* Die Zeile wird
      **rot** = die alte Hervorhebungsfarbe ist zurück; oder es wird eine
      andere Zeile markiert = Kachel und Zeile zeigen auf verschiedene
      Einsätze.
- [ ] **Standort-Haus auf der Karte.** *Weg:* Übersicht mit einem Standort,
      der Koordinaten trägt. *Erwartet:* Ein Haus-Symbol mit Namensschild,
      auch **ohne** Entsperren (der Standort ist Klartext). Das Schild ragt
      nicht aus der Karte. *Fehlschlag heißt:* Kein Haus trotz hinterlegter
      Koordinaten = `api/range.php` liefert `bases` nicht; oder das Schild
      liegt über den Kartenknöpfen = das Padding von `fitBounds` greift nicht.
- [ ] **Die Leiste weiß, wo man ist.** *Weg:* Monatsübersicht öffnen.
      *Erwartet:* Der Monat ist in der Leiste hell orange mit orangem Strich
      markiert, das Jahr darüber aufgeklappt; bei der Jahresübersicht trägt
      die Jahreszeile die Markierung. *Fehlschlag heißt:* Nichts ist
      markiert = der Zeitraum wird nicht durchgereicht.
- [ ] **Segmentwahl mit der Tastatur.** *Weg:* Mit der Tabulatortaste auf
      die Segmentwahl, dann Pfeil rechts/links. *Erwartet:* Die Ansicht
      wechselt, Kacheln, Karte und Liste ziehen mit. *Fehlschlag heißt:*
      Nichts geschieht = der Zuhörer horcht auf `click` statt auf `change`.
- [ ] **Nur eine Art im Zeitraum.** *Weg:* Einen Monat öffnen, in dem nur
      luftgebundene (oder nur bodengebundene) Diensttage liegen.
      *Erwartet:* **Keine** Segmentwahl — es gibt nichts zu wählen —, und
      die Kacheln tragen die Beschriftung dieser Art („Flugtage").
      *Fehlschlag heißt:* Die Wahl steht da mit zwei leeren Ansichten.

### 5.9 Nach O8a (Web 9.7.0)

**Vor allem anderen: `update.php` aufrufen.** Diese Fassung legt die Spalte
`users.logo_wahl` an. Läuft die Migration nicht, scheitert **jede
Anmeldung** — auch die eigene. *Fehlschlag heißt:* „Anmeldung
fehlgeschlagen" trotz richtigem Passwort, oder eine Fehlerseite statt der
Startseite.

- [ ] **Logo-Wahl greift.** *Weg:* Profil → Logo → „Fahrzeug (NEF)" →
      Profil speichern. *Erwartet:* Die Kopfleiste zeigt sofort das
      Fahrzeug, **und** das Symbol im Browser-Tab wechselt mit. *Fehlschlag
      heißt:* Nur eines von beidem wechselt — dann fragen Kopfleiste und
      Favicon nicht dieselbe Stelle.
- [ ] **„Wechselnd" springt nicht.** *Weg:* „Wechselnd" wählen, speichern,
      dann fünf Seiten durchklicken (Startseite, Suche, Zeitraum,
      Einstellungen, zurück). *Erwartet:* Dasselbe Logo auf allen fünf.
      *Fehlschlag heißt:* Es wechselt beim Blättern = es wird bei jedem
      Aufruf gewürfelt statt einmal bei der Anmeldung.
- [ ] **„Wechselnd" wechselt wirklich.** *Weg:* Abmelden und neu anmelden,
      mehrfach. *Erwartet:* Über mehrere Anmeldungen kommen beide Logos vor.
      *Fehlschlag heißt:* Immer dasselbe = der Würfel liegt an der falschen
      Stelle.
- [ ] **Die Anmeldeseite zeigt den Standard.** *Weg:* „Fahrzeug" wählen,
      abmelden, Anmeldeseite ansehen. *Erwartet:* Hubschrauber — dort ist
      niemand angemeldet, und die Wahl hängt am Konto.
- [ ] **Die Lage lässt sich wieder eingeben** (die Behebung von F-P3-AI).
      *Weg:* Standorte → „Standort hinzufügen" → im Feld „Lage (optional)"
      einen Ort suchen. *Erwartet:* Ein Eingabefeld mit Lupe ist da,
      Vorschläge erscheinen, die Übernahme setzt die Koordinaten als Chip —
      und **der Name im Feld darüber bleibt unberührt**. *Fehlschlag heißt:*
      Kein Feld unter „Name" = die Nur-Lage-Fassung ist wieder leer (das war
      der Zustand seit O5); oder der Name wird überschrieben = die getrennte
      Suche ist verloren.
- [ ] **Zeilenaktionen am Schreibtisch.** *Weg:* Standorte bei ≥ 720 px.
      *Erwartet:* Je Zeile „Als Vorbelegung" (orange, leise), „Bearbeiten",
      „Löschen" (rot umrandet) als Knöpfe; **kein** „⋯". *Fehlschlag heißt:*
      Beides gleichzeitig = eine `display`-Regel schlägt die Hilfsklasse.
- [ ] **Zeilenaktionen auf dem Handy.** *Weg:* Dieselbe Seite bei 390 px,
      „⋯" drücken. *Erwartet:* Ein Blatt von unten mit dem Namen der Zeile
      als Titel und denselben Einträgen; „Löschen" rot. *Fehlschlag heißt:*
      Die Knöpfe stehen untereinander in der Zeile = das Blatt greift nicht.
- [ ] **Löschen fragt beziffert zurück.** *Weg:* Einen Standort löschen, an
      dem Rettungsmittel hängen. *Erwartet:* Die Rückfrage nennt die **Zahl**
      der mitgelöschten Stammdatensätze und sagt, dass dokumentierte
      Diensttage bleiben. *Fehlschlag heißt:* „Standort löschen?" ohne Zahl.
- [ ] **Passwortstärke als Balken.** *Weg:* Profil → Neues Passwort, tippen.
      *Erwartet:* Vier Segmente füllen sich; rot bei zu kurz, orange in der
      Mitte, dunkelblau bei stark. Kein Grün, kein Gelb. *Fehlschlag heißt:*
      Eine gefärbte Textzeile ohne Balken = die alte Anzeige ist zurück.
- [ ] **Vordefinierte Standorte.** *Weg:* Die zweite Karte aufklappen.
      *Erwartet:* Sie ist zugeklappt, der Kopf nennt „n · m ausgewählt",
      jede Zeile trägt die Plakette „systemweit" und „Auswählen" bzw.
      „Abwählen". *Fehlschlag heißt:* Aufgeklappt = die Voreinstellung
      stimmt nicht.

### 5.10 Nach O8b (Web 9.7.1)

- [ ] **Ein Standort ist eine Karte.** *Weg:* Einstellungen → Rettungsmittel.
      *Erwartet:* Je Standort eine zugeklappte Karte mit der Zahl der
      Rettungsmittel im Kopf; aufgeklappt fünf Abschnitte (Rettungsmittel,
      Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht — letzterer
      nur bei einem luftgebundenen Rettungsmittel am Standort). *Fehlschlag
      heißt:* Aufgeklappt beim Öffnen = bei mehreren Standorten steht die
      Seite sofort mehrere Bildschirme hoch.
- [ ] **Die Lage lässt sich eintragen und ändert den Namen nicht.** *Weg:*
      Zielkliniken → „Zielklinik hinzufügen" → Name „Testklinik" eintragen,
      dann im Feld „Lage (optional)" einen Ort suchen und einen Vorschlag
      wählen. *Erwartet:* Koordinaten erscheinen als Chip, **im Namensfeld
      steht weiter „Testklinik"**. *Fehlschlag heißt:* Der Name wird durch
      die Adresse ersetzt = das Lage-Feld hängt am falschen Element (das war
      der Zustand in Web 9.7.0); oder es kommen keine Vorschläge = die
      Kennung stimmt nicht.
- [ ] **Die Vorschlagsliste bleibt stehen** (F-P3-AJ). *Weg:* Lage suchen,
      Lupe drücken, **nicht** weiterklicken. *Erwartet:* Die Vorschläge
      bleiben sichtbar, bis man einen wählt oder wegklickt. *Fehlschlag
      heißt:* Sie erscheinen kurz und verschwinden von selbst — dann greift
      der blur-Aufschub wieder, obwohl der Fokus zurückgekehrt ist. Am
      ehesten zu sehen, wenn die Antwort schnell kommt (Zwischenspeicher,
      zweite Suche nach demselben Ort).
- [ ] **Zwei gleichnamige Einträge, zwei Blätter** (F-P3-AK). *Weg:* An zwei
      Standorten je eine Zielklinik mit **demselben Namen** anlegen; auf
      390 px bei beiden nacheinander „⋯" drücken. *Erwartet:* Jedes „⋯"
      öffnet sein eigenes Blatt. *Fehlschlag heißt:* Es öffnet sich keines
      oder zwei gleichzeitig = die Kennungen kollidieren wieder.
- [ ] **Die Löschrückfrage nennt den Namen.** *Weg:* In einer beliebigen
      Stammdatenliste „Löschen" drücken. *Erwartet:* „… „Bergwacht
      Felsgrat" löschen?" — mit Namen. *Fehlschlag heißt:* „Eintrag
      löschen?" ohne Namen; bei elf Besatzungseinträgen ist das keine
      Rückfrage.
- [ ] **Kopplungscode groß und lesbar.** *Weg:* Geräte → „Kopplungscode
      erzeugen". *Erwartet:* Der Code steht in einem eigenen Kasten, groß
      und in Festbreite, darunter die Gültigkeit. *Fehlschlag heißt:*
      Fließtextgröße — er wird auf einer Uhr abgetippt, unter Zeitdruck.
- [ ] **„Als Vorbelegung" nur, wo es eine gibt.** *Weg:* Die fünf Listen
      durchsehen. *Erwartet:* Der Eintrag steht bei Standorten und
      Rettungsmitteln, nicht bei Besatzung, Zielkliniken, weiteren
      Rettungsmitteln und Bergwacht.

### 5.11 Nach O8c (Web 9.7.2)

- [ ] **Ein Fehlschlag sieht aus wie ein Fehlschlag.** *Weg:* Backup →
      „Backup erstellen", zwei verschiedene Passwörter eintippen,
      absenden. *Erwartet:* Eine **rote** Meldung mit Warnsymbol. *Fehlschlag
      heißt:* Grauer Text wie ein Zwischenstand — dann trägt die Meldung
      ihren Ton nicht.
- [ ] **Ein Ergebnis sieht aus wie ein Ergebnis.** *Weg:* Eine Sicherung
      tatsächlich erstellen. *Erwartet:* Eine **blaue** Meldung mit Haken und
      den Zahlen. Sind Blöcke unlesbar, ist es stattdessen eine **Warnung**
      (orange) — die Datei ist vollständig, aber ein Teil öffnet sich nur in
      diesem Konto. *Fehlschlag heißt:* Haken trotz unlesbarer Blöcke.
- [ ] **Fortschritt bleibt schlicht.** *Weg:* Beim Erstellen zusehen.
      *Erwartet:* „Daten werden geladen …" ohne Symbol und ohne Farbe.
      *Fehlschlag heißt:* Ein Haken neben einem Zwischenstand.
- [ ] **Die drei Schritte des Imports.** *Weg:* Import/Export öffnen.
      *Erwartet:* Nur „1. Datei wählen" ist da; nach dem Wählen einer Datei
      erscheinen „2. Prüfen und korrigieren" und „3. Übernehmen". *Fehlschlag
      heißt:* Alle drei von Anfang an = die Schrittfolge ist verloren.
- [ ] **Die Zeilenwahl zeigt, was gilt.** *Weg:* In Schritt 2 zwischen „Alle
      Zeilen", „Nur Probleme" und „Nur Dubletten" wechseln, auch mit den
      Pfeiltasten. *Erwartet:* Die gewählte Fläche ist orange hinterlegt, die
      Tabelle ändert sich. *Fehlschlag heißt:* Drei gleich aussehende Knöpfe
      = die Segmentwahl ist nicht angekommen; oder die Pfeiltasten tun nichts
      = es hängt an `click` statt an `change`.
- [ ] **Die Warnung steht vor der Passwortwahl.** *Weg:* Sicherung erstellen.
      *Erwartet:* Der orange Kasten „In dieser Datei stehen alle geschützten
      Angaben im Klartext" steht **über** den Passwortfeldern. *Fehlschlag
      heißt:* Darunter oder gar nicht.
- [ ] **Der Kopplungscode ist groß.** *(aus O8b, hier mitgeprüft)* Geräte →
      „Kopplungscode erzeugen". *Erwartet:* eigener Kasten, Festbreite,
      gesperrte Schrift.

### 5.12 Nach O9a (Web 9.8.0) — Kontoseite

- [ ] **⬤ Die Migration ist gelaufen.** *Weg:* Nach dem Deploy `update.php`
      als Administratorin aufrufen. *Erwartet:* `2026_08_28_last_login` wird
      mit Klartextnamen genannt und ausgeführt. *Fehlschlag heißt:* Ohne sie
      scheitert die Kontenliste — die Spalte `users.last_login` fehlt.
- [ ] **⬤ Die Verdrängung hält das Richtige.** *Weg:* Bei Aufbewahrung 3 vier
      Sicherungen erzeugen, eine davon vorher freigeben. *Erwartet:* Drei
      bleiben, **das freigegebene ist dabei**, und die Meldung nennt die
      Verdrängung. *Fehlschlag heißt:* Ein freigegebenes Paket verschwindet —
      dann verliert jemand einen Zugang, den er bekommen hat.
- [ ] **⬤ Löschen verlangt die abgetippte Adresse.** *Weg:* Auf der
      Kontoseite ein fremdes Konto löschen, erst mit falscher, dann mit
      richtiger Adresse. *Erwartet:* Falsch blockt; richtig führt zurück auf
      die Liste, **und der Sicherungsordner ist mit fort**. *Fehlschlag
      heißt:* Ein Ordner bleibt liegen = verwaiste Daten eines gelöschten
      Kontos.
- [ ] **○ Das eigene Konto lässt sich nicht selbst zerstören.** *Weg:* Die
      eigene Kontoseite öffnen. *Erwartet:* Kein Löschformular, keine
      Rollenrückstufung. *Fehlschlag heißt:* Man kann sich selbst aussperren.

### 5.13 Nach O9b (Web 9.9.0) — die Kontenliste

- [ ] **⬤ Auswahl über Seitengrenzen.** *Weg:* Auf Seite 1 zwei Konten
      auswählen, auf Seite 2 eines, zurück auf Seite 1. *Erwartet:* „3
      ausgewählt", und auf Seite 1 sind wieder genau die beiden gesetzt.
      *Fehlschlag heißt:* Die Auswahl hängt am DOM statt am Zustand — dann
      sichert eine Sammelaktion die falschen Konten.
- [ ] **⬤ Suche und Filterzahlen gehören zusammen.** *Weg:* Nach einem Namen
      suchen, der mehrfach vorkommt. *Erwartet:* Die **Filterzahlen** beziehen
      sich auf die Treffer, die **Kacheln** oben auf den Gesamtbestand.
      *Fehlschlag heißt:* Beide gleich = eine der Zahlen lügt, und man trifft
      Entscheidungen über den Bestand anhand einer Suche.
- [ ] **○ Umlaute sortieren richtig.** *Weg:* Nach Name sortieren.
      *Erwartet:* „Ömer" steht bei den O, nicht am Ende. *Fehlschlag heißt:*
      Byteweise Sortierung — alle Umlautnamen stehen hinter allen anderen.
- [ ] **○ Unter 720 px werden Zeilen zu Kacheln.** *Weg:* Fenster schmal
      ziehen. *Erwartet:* Dieselben Konten stehen als Kacheln da, das
      Auswahlkästchen zählt dort ebenso. *Fehlschlag heißt:* Eine waagerecht
      scrollende Tabelle.

### 5.14 Nach O9c (Web 9.10.0/9.10.1) — Regeln, Stammdaten, Demo, Wartung

- [ ] **⬤ Der Logo-Standard der Installation wirkt sofort.** *Weg:* Wartung →
      Standardlogo umstellen, **ohne** neu anzumelden die Kopfleiste ansehen;
      dann abmelden und die Anmeldeseite ansehen. *Erwartet:* Beide folgen dem
      neuen Standard; ein Konto mit **eigener** Wahl bleibt unberührt.
      *Fehlschlag heißt:* Die eigene Wahl wird überschrieben — dann ist der
      Standard keine Vorgabe, sondern ein Befehl.
- [ ] **⬤ Die Aufbewahrung ist einstellbar und gilt.** *Weg:* 3 → 5 → 3
      setzen, dazwischen sichern. *Erwartet:* Die Zahl wird gespeichert und
      die Verdrängung folgt ihr.
- [ ] **○ Stammdaten systemweit: zwei Reiter, ein Menüpunkt.** *Weg:*
      Stammdaten öffnen und zwischen Standorten und Rettungsmitteln wechseln.
      *Erwartet:* Zwei völlig verschiedene Listen unter einem Punkt.
- [ ] **○ Das Demo-Konto setzt sich zurück.** *Weg:* Im Demo-Konto etwas
      ändern und eine halbe Stunde warten. *Erwartet:* Der Ausgangsstand ist
      zurück.

### 5.15 Nach O10 (Web 9.11.0) — Anmeldung, öffentliche Seiten, Rechtstexte

- [ ] **⬤ Der Angriffsversuch prallt ab.** *Weg:* Als Administratorin im
      Rechtstext-Editor speichern:
      `<script>alert(1)</script>`, `[böse](javascript:alert(1))` und
      `<img src=x onerror=alert(1)>`. Dann die **öffentliche Seite abgemeldet**
      öffnen und den Quelltext ansehen. *Erwartet:* Kein `<script>`, kein
      `href="javascript`, kein `<img>`; `<script>` steht als **sichtbarer
      Text** da. *Fehlschlag heißt:* Ein `<script>` im Quelltext = jeder
      Besucher der Impressumsseite führt fremden Code aus. Das ist der
      Abnahmefall des Pakets, und er ist maschinell mit 81 Proben und 65
      Ausgaben gegen eine Positivliste belegt — diese eine Bedienprobe prüft,
      ob derselbe Weg auch im Browser gilt.
- [ ] **⬤ Die Seiten sind ohne Anmeldung erreichbar.** *Weg:* Abgemeldet
      `impressum.php` und `datenschutz.php` aufrufen. *Erwartet:* HTTP 200,
      Inhalt oder Leerzustand. *Fehlschlag heißt:* Weiterleitung auf die
      Anmeldung.
- [ ] **○ Der Leerzustand sagt, was fehlt.** *Weg:* Vor dem Eintragen der
      Texte. *Erwartet:* Zwei Plaketten „leer", keine Vorschau, die Meldung
      „Der Betreiber dieser Installation hat noch kein Impressum
      hinterlegt" — für Administratorinnen mit dem Weg zum Editor.
- [ ] **○ Die Anmeldekarte ist schmal.** *Weg:* Anmeldung bei 1440 px.
      *Erwartet:* 400 px breit, nicht 760.

### 5.16 Nach O11 und O12 (Web 9.12.0 / 9.13.0) — der Rest und der Abschluss

- [ ] **⬤ Keine Verwaltungstabelle scrollt mehr.** *Weg:* Bei **360 px**
      nacheinander öffnen: Papierkorb, Wartung, Diensttag zusammenführen,
      Zuordnung nachtragen. *Erwartet:* Karten mit Zeilen, kein seitliches
      Schieben, jede Handlung erreichbar. *Fehlschlag heißt:* Eine Tabelle,
      in der die Aktionsspalte rechts aus dem Bild läuft — genau das, was
      O11 beseitigt hat.
- [ ] **⬤ Löschbestätigungen zeigen Zahlen.** *Weg:* Einen Diensttag mit
      Einsätzen löschen (und abbrechen). *Erwartet:* Eine **Seite** mit
      Karte und Zeilen; je Zeile eine **Plakette mit der Zahl** (Einsätze,
      Phasen, Reanimationen, Ruhesegmente, Trackpunkte). *Fehlschlag heißt:*
      Ein Dialog mit einem halben Bildschirm Fließtext, oder die Zahlen
      stecken im Satz — dann findet man beim Überfliegen nicht, was man
      verliert.
- [ ] **⬤ Die Filterleiste der Suche ist breiter als die Tagesliste.**
      *Weg:* Suche und Tagesübersicht nebeneinander bei **1280 px** öffnen und
      die linke Leiste vergleichen. *Erwartet:* Suche **280 px**,
      Tagesübersicht **260 px**; bei 1024–1199 px 240 gegen 220.
      *Fehlschlag heißt:* Gleich breit = `leiste-filter` wird nicht vergeben
      (F-P3-BC), und die Filter brechen enger um, als sie müssten.
- [ ] **⬤ Der Export-Knopf sieht aus wie ein Knopf.** *Weg:* Import/Export →
      bis zum Export-Knopf durchgehen. *Erwartet:* 44 px hoch, orange, mit
      Radius — wie sein Nachbar „Import ausführen". *Fehlschlag heißt:*
      Blauer Text ohne Fläche = die Reparatur aus F-P3-BA ist verloren.
- [ ] **○ Die Schalter sind anklickbar, das Kästchen nicht.** *Weg:* Auf einen
      Schalter tippen, und zwar genau links davon. *Erwartet:* Er schaltet.
      *Fehlschlag heißt:* Ein toter Bereich von 20 × 20 px = das
      ausgeblendete Kästchen fängt wieder Klicks (F-P3-AZ).
- [ ] **○ Die Version stimmt.** *Weg:* Fußzeile. *Erwartet:* **9.13.0**.
- [ ] **○ Die Gestaltungsrichtlinie ist auffindbar.** *Weg:* `docs/Design.md`
      öffnen und Kapitel 9.0 lesen („Wenn du X willst, nimm Y").
      *Erwartet:* Für den Fall, den man gerade bauen will, steht dort eine
      Zeile. *Fehlschlag heißt:* Steht der Fall nicht darin, ist das **keine
      Erlaubnis, ein neues Element zu bauen**, sondern der Moment für eine
      Rückfrage — und eine Zeile, die der Richtlinie fehlt.

## 6. Was bewusst **nicht** geprüft wird

| Bereich | Warum nicht |
|---|---|
| Uhr-App (`watch/`) | in P3 nicht angefasst; `Const.mc` zählt getrennt. Die Logo-Wahl auf der Uhr ist P6 (R29). |
| Rechenwege der Prüfschicht | `validate_lib.php` ist über die ganze Phase unberührt geblieben. |
| Das **Handbuch** | ausdrücklich zurückgestellt bis vor 1.0 (1.3). Wer nach diesem Dokument prüft, prüft die Anwendung. |
| Mailversand | lokal kein SMTP. Geprüft ist der Fehlschlagzweig — der Einladungs- bzw. Setz-Link steht sichtbar auf der Seite —, nicht der gelungene Versand. |
| Sammelsichern über alle Konten | erzeugte bei 304 Konten 304 Ordner; das ist der Fall, für den F-P3-C ohnehin auf P5 verweist („Alle sichern" in Schüben). |
| Die blockierte Migration auf der Wartungsseite | verlangt eine destruktive Migration mit Daten. Die Zeile wurde im Markup gelesen, nicht bedient. |

**Und was in P3 sehr wohl geprüft wurde, obwohl es hier einmal stand:**
Datenmodell und Migrationen. Drei Migrationen sind entstanden (1.4); jede ist
einzeln gefahren worden — bei `2026_08_28_last_login` wurde die Spalte eigens
von Hand gelöscht, um den Weg wirklich zu betreten. `schema.sql` ist in O10 in
eine Wegwerfdatenbank eingespielt worden (33 Anweisungen, 31 Tabellen). Die
Kreisläufe sind gefahren: edbak mit **286 739 Einzelvergleichen**, 0
unerklärt.

---

## 7. Wenn etwas nicht stimmt

1. **Hart neu laden** (Strg+Umschalt+R). Vieles, was nach Gestaltungsfehler
   aussieht, ist ein alter Zwischenspeicher — besonders bei den Symbolen, die
   ihren Erkennungswert aus `WEB_VERSION` beziehen.
2. **Browserkonsole ansehen** und die Meldung mitnotieren.
3. **Zurückrollen ist nicht mehr billig.** Bis O8 gab es keine Migration;
   inzwischen sind es drei (1.4). Ein Rückschritt hinter Web 9.7.0 heißt,
   ein Schema zurückzubauen — vorher sichern, und im Zweifel fragen statt
   zurückrollen.
4. Fund melden mit: Punktnummer aus Abschnitt 5, Seite, Fensterbreite,
   Konsolenmeldung.
5. **Prüfen, ob es schon bekannt ist.** Abschnitt 1.3 nennt, was am Ende der
   Phase offen ist; Backlog Nr. 40, 41 und 42 nennen die Punkte mit Nummer.
   Ein Fund, der dort steht, ist keiner — er ist eine Bestätigung, und die
   ist auch etwas wert.
