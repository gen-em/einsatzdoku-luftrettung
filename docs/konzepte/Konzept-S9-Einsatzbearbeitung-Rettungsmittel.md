# Konzept S9 — Einsatzbearbeitung und Rettungsmittel

**Rahmenplan:** Schritt 8 (Fassung 32), R73. **Backlog:** 101–113 (PS-1 bis
PS-10), 147 (PS-11), **152** (PS-12, 07.09.2026) — und seit dem 06.09.2026
dazu **44, 68, 69, 70, 72** (Beschluss des Auftraggebers, Begründung in
Abschnitt 2.2); seit dem 07.09.2026 **132 und 137** aus dem Sofortpaket
(Beschluss, Abschnitt 2.3).
**Vorbereitung:** `Vorbereitung-S9-Problemsammlung.md` (Fassung vom
06.09.2026, F1–F19 und F3–F6 beantwortet). **Modell:** Konzept Fable (R14),
Umsetzung Opus (K2). **Ablage:** dieses Dokument, das Prüfdokument daneben,
Mockups in `konzept-s9/mockups/`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 07.09.2026 — **Konzept freigegeben.** E-S9-01 bis -17 am 06.09.2026 bestätigt, E-S9-18 und -19 am 07.09.2026; alle sieben Mockups freigegeben (Abschnitt 6). PS-12 (Standortseiten, Backlog 152) am 07.09.2026 aufgenommen. Rahmenplan Fassung 34 trägt die Einschübe aus Abschnitt 7 |
> | Entschieden | E-S9-01 bis E-S9-19 (Abschnitt 2) |
> | Offen | nichts. **Stand `main` 07.09.2026:** Korrekturstufe 148/149 gemergt (Web 15.5.2, PR #36); Schritt 9a hat nicht begonnen. **Beschluss 07.09.2026:** Nr. 137 und 132 ganz nach S9 — S9 und 9a berühren sich in keiner Datei mehr und laufen parallel; **die Umsetzung kann sofort beginnen** (Auftrag: `Prompt-Umsetzung-S9.md`, außerhalb des Repositoriums) |
> | Umsetzung | **AP1 und AP2 erledigt** (07.09.2026, Web 15.6.0 / 15.6.1 / **15.7.0**, Zweig `claude/go-bwucrx`) — siehe „Stand der Umsetzung" unten. **AP3 ist beauftragt** (07.09.2026). Acht Arbeitspakete (Abschnitt 3), parallel zu 9a auf eigenem Zweig, Buchführung nach K7 |
> | Fable-Schritte der Umsetzung | **keine** — der einzige Fable-Vorbehalt der Vorbereitung (PS-8.2) ist im Konzept aufgelöst (Abschnitt 1.8) |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | **AP1** Vorschlagsliste und Klickprobe | **erledigt** 07.09.2026 | Web **15.6.0**, Korrektur **15.6.1** | Klickprobe PS-2 mit 300 ms gehaltener Maus **0 von 3 → 3 von 3** (dieselbe Fassung der Probe gegen beide Stände); `grep -rn datalist server/` **0** außerhalb von Kommentaren (vorher 12 in sechs Dateien, 8 davon im Markup einer Einsatzseite); Transportziel „Klin" **1 Liste · 2 Gruppen · 2 Stammdaten · 4 Adressen · 0 `<datalist>`** (vorher 1 Liste · 0 Gruppen · 8 `<datalist>`); Bedienhöhe an der einzeiligen Zeile gemessen **390 px → 44 px, 1280 px Zeiger → 36 px, 1280 px Finger → 44 px**; **16 von 16** Wegen erfüllt über zwei Breiten × zwei Bedienhöhen, 16 Bilder; Ebene der Liste **35** über der Speichern-Leiste (30) und unter der Kopfleiste (40), mit `elementFromPoint` in der Schnittfläche gemessen (F-S9-P-07, Web 15.6.1); Bilderlauf 7 Seiten, 56 Bilder je Lauf, **0/0/0** in beiden Bedienhöhen; Wortliste **0/0/0** in fünf Bereichen; Vollständigkeit **300 → 298 Befunde**; Kontraste **21 Paare, 0 verfehlt** |
> | **AP2** Geocoder und Kartendialog | **erledigt** 07.09.2026 | Web **15.7.0**, Migration `2026_09_07_adresssuche_konto` | `grep -rn "komoot" server/assets/` **0** (vorher 2); Kartendialog aus **5 von 5** Einbauorten mit Karte darin (Einsatzort, manueller Abfahrtort, Transportziel, Standort im Konto, Standort systemweit); Treffer im Suchfeld lässt das Formular unberührt — **Feld leer, 0 Chips**, nach „Übernehmen" **1 Chip** (F1); Spur im Dialog bei 309 Punkten **1 Linie · 4 Ringpunkte (2 Karte + 2 Legende) · Legende sichtbar · 0 Pfeile**, Karte auf der Spur bei leerem Feld (Bild); Kontoschalter **aus → 0 Anfragen** an `photon.komoot.io` bei Tippen, Kartenwahl und Übernehmen, **mit Gegenprobe „an → 2 Anfragen"**, dazu 0 Suchfelder und 0 Hinweiszeilen; Installationsschalter aus → Kontoschalter **gesperrt** mit Grund (Bild); Hinweis am Ortsfeld **1 bei 3 Ortsfeldern**, nennt den Dienst (Bild); Datenschutztext-Baustein nennt `geocoder_host()` (**2 grep-Treffer**); Klickprobe **40 von 40** Wegen über zwei Breiten × zwei Bedienhöhen, 36 Bilder; Bilderlauf **zehn berührte Seiten**, 64 + 16 Einzelbilder je Lauf, **0/0/0** in beiden Bedienhöhen; Wortliste **0/0/0** in fünf Bereichen (178 Dateien); Vollständigkeit **298 → 304**, der Unterschied vollständig erklärt (+6 Menüpfeile „→" in Fließtext, `loc-datenschutz` als Anker eingetragen); Kontraste **21 Paare, 0 verfehlt**; Linkprobe **134 Verweise, 0 unbekannte Abweichungen**; Register **44 = 44** |
> | AP3 bis AP8 | offen | — | — |
>
> **Fragen aus AP1** (Prüfdokument, Abschnitt 4): **zwei entschieden am
> 07.09.2026** — die Besatzungsfelder des Diensttags gehören zu **AP1** (so
> gebaut), und im Abschluss wandern **zwanzig** Backlog-Punkte nach
> *Erledigt*, also **auch Nr. 132 und Nr. 137** (siehe AP8 unten). Offen ist
> die dritte: die Zeile „`<datalist>` in der Streichliste" — dort stehen
> Klassen des alten Stylesheets, `datalist` ist ein HTML-Element; eingetragen
> sind statt dessen die vier Klassen, die tatsächlich verschwinden.
>
> **Ein Fund nach der Abgabe von AP1**, vom Auftraggeber am Bild gemeldet und
> behoben mit Web 15.6.1: Die Liste lag mit `z-index: 20` **hinter** der
> klebenden Speichern-Leiste (30) und verdeckte deren unterste Trefferzeilen
> (61 bis 69 px). Ebene jetzt 35; die Klickprobe misst sie in beide
> Richtungen (F-S9-P-07).
>
> **Drei Funde in AP2**, alle behoben und alle von der Klickprobe gefunden —
> keiner davon war im Browser zu sehen (Prüfdokument, Abschnitt 2):
> **F-S9-P-08** der Bootstrap schrieb `const`, das Modul las `window` — der
> Kartendialog kam ohne Suchfeld, während die Hinweiszeile daneben sagte, die
> Suche sei an; **F-S9-P-09** nach dem Speichern zeigte die Betriebsseite den
> **alten** Schalterstand („ausgeschaltet gespeichert." bei stehendem
> Schalter), weil der Zwischenspeicher der Anfrage nicht nachgezogen wurde;
> **F-S9-P-10** das Demo-Konto konnte seine Adresssuche nicht abschalten, weil
> der Schalter im Profilformular stand und der Demo-Wächter dieses ganz
> verwirft. Die beiden gesammelten Funde aus AP1 (F-S9-P-05, -06) sind
> abgeräumt.
>
> **Die zwei Fragen aus AP2 sind entschieden** (Auftraggeber, 07.09.2026):
> **Frage 4** — Pfeile auf der Spur im Kartendialog: **nein**. Der
> Konzepttext E-S9-06 (b) geht vor, Anmerkung 3 des Mockups M-S9-04 ist an
> dieser Stelle überholt; der gebaute Stand bleibt, die Sollzahl der
> Klickprobe (0 Pfeile) gilt weiter. **Frage 5** — die Überschrift im Kopf
> des Kartendialogs: **lassen**. Von den fünf Fragen der Umsetzung sind damit
> vier entschieden; offen ist allein Frage 2 aus AP1.

---

## 0. Auftrag und Umfang

**Ziel:** Die Problemsammlung vom 03.09.2026 wird in einem Zug analysiert,
konzipiert und umgesetzt — die Einsatzbearbeitung, die Ortsauswahl und die
Rettungsmittel. Dazu die fünf Backlog-Punkte, die in denselben Dateien
liegen und einzeln mehr kosten würden als zusammen (Abschnitt 2.2).

**Umfang (zwanzig Punkte):**

| Kennung | Backlog | Punkt | Typ |
|---|---|---|---|
| PS-1 | 101 | Adresssuche im Kartendialog | Erweiterung |
| PS-2 | 102 | Weitere Rettungsmittel: Auswahl wird nicht übernommen | Bug |
| PS-3 | 103 | Kompaktere Schilder auf der Karte | Gestaltung (Mockup) |
| PS-4 | 104 | Windenkacheln bei Nullwert | Anzeigelogik |
| PS-5 | 105 | Artzeichen Hubschrauber | Gestaltung (Mockup) |
| PS-6 | 106 | Klinik- und Adressvorschläge in einer Liste | Bug / UI |
| PS-7 | 107 | Transportziel per Koordinaten und Karte | Erweiterung |
| PS-8.1 | 108 | Schloss-Symbol und Legende | UI |
| PS-8.2 | 109 | Notizen verschlüsseln, Suche bleibt | Datenmodell |
| PS-9 | 110 | „Spur" → „GPS-Daten" | Wording |
| PS-10.1 | 111 | Neue Rettungsmittel-Typen | Erweiterung, Migration |
| PS-10.2 | 112 | Rettungsmittel nur für den Tag | Erweiterung |
| PS-10.3 | 113 | Rollen sofort nach Auswahl | Bug / Workflow |
| PS-11 | 147 | Aufgezeichnete Spur im Kartendialog | Erweiterung |
| — | 68 | `<datalist>` zeigt mobil nichts | Bug / Baustein |
| — | 69 | Kurzname je Rettungsmittel | Erweiterung, Migration |
| — | 70 | Karte für Standorte | Erweiterung |
| — | 72 | Richtungspfeile auf der Spur | Bug |
| — | 44 | Sprungliste bei vielen Rettungsmitteln | Gestaltung (Mockup) |
| PS-12 | 152 | Standortseiten: Standort zuerst, ein Menüpunkt, Kennzahlen als Inhaltsverzeichnis, Dialoge, Landung, Filterfeld | Umbau (Mockup) — aufgenommen 07.09.2026 |
| — | 137 | Photon: Hinweis am Feld, Datenschutztext, Installationsschalter (K-6, SP-12 a) | aus dem Sofortpaket übernommen 07.09.2026 |
| — | 132 | Klartext-Freitextfelder ohne Hinweis (K-12) | aus dem Sofortpaket übernommen 07.09.2026 |

**Nicht Umfang:** Selbstbetrieb eines Geocoders (Hosting-Entscheidung R36,
vor P5 — S9 macht ihn zur Eingabe, E-S9-05) · Verschlüsselung von Spur,
Phasen, Reanimation und Zielklinik (S11, R78 (6)) · die Support-Rolle und
alles Weitere aus R38 (P5) · das übrige Sofortpaket Sicherheit (9a: Nr.
127–131, 133–136, 138, 140, 142–145; läuft parallel, Abschnitt 2.3) · neue
Verwaltungsfunktionen.

**Feste Zusagen, die dieses Konzept berührt und nicht aufweicht**
(`CLAUDE.md` 4): Ende-zu-Ende-Verschlüsselung — S9 **erweitert** sie um die
Notizen; keine fremde Quelle zur Laufzeit — Photon ist eine Datenabfrage,
keine Quelle, und wird abschaltbar (E-S9-05); gemeinsame Prüfschicht
(`validate_lib.php`) — jeder neue Schreibweg läuft darüber; Feldkatalog
statt Sonderfall — S9 gibt dem Katalog einen Schlüssel für verschlüsselte
Felder, statt ein zweites Feld daran vorbeizubauen; Spuren nur über
`spur_lib.php` — PS-11 liest über `api/mission.php`, das ihn benutzt.

---

## 1. Befund

Am Code gelesen am 06.09.2026 (`main`, Web 15.5.1). Zeilenangaben gelten für
diesen Stand.

### 1.1 Die Ortsauswahl: eine Komponente, drei Lücken (PS-1, PS-7, PS-11, Nr. 70)

`assets/ortsfeld.js` ist seit Web 6.1.0 die eine Komponente für Bezeichnung
plus Koordinaten, `assets/ortswahl.js` (P3/O5) hängt daran das Blatt „Meine
Position übernehmen / Auf der Karte wählen" und den Kartendialog mit
Fadenkreuz. Beides ist **je Präfix registriert** — der Dialog kennt seinen
Aufrufer nur als Steuerobjekt (`felder[praefix]`, `ortswahl.js:38`). Was
fehlt:

- **Kein Suchfeld im Dialog** (PS-1). Er zeigt Karte, Kreuz, „Übernehmen".
- **Keine Spur** (PS-11). `einsatz_form.php:121–127` zählt sie (`$hatTrack`,
  Schwelle: mehr als ein Punkt) und blendet nur den Abfahrtort aus;
  `api/mission.php:137` liefert sie längst als `track`.
- **Kein Pin-Knopf am Transportziel** (PS-7). Das Feld ist ein Katalogfeld
  vom Typ `loc` (`mission_fields.php`, `transport_dest`) mit
  `suggest_src => 'transport_dests'`, aber ohne `ortswahl`; das Formular
  setzt `'ortswahl' => true` nur am Einsatzort und am manuellen Abfahrtort
  (`einsatz_form.php:1219, 1282`).
- **Keine Karte in den Stammdaten** (PS-7 zweite Stelle, Nr. 70). Zielklinik
  und Standort benutzen die **Nur-Lage-Fassung** von `ui_ortsfeld()`
  (`feld => false`, `ui.php:2060 ff.`; Aufrufe `einstellungen.php:1303,
  1634`, `admin_stammdaten.php:503, 723`), und in dieser Fassung gibt es den
  Pin-Knopf nicht — `$mitWahl` wird nur im `feld = true`-Zweig gerendert
  (`ui.php:2018–2056`).

**Koordinateneingabe gibt es überall schon** (F11 erfüllt): `assets/locparse.js`
erkennt Dezimalgrad, Grad/Minuten und Plus Codes in jeder Verwendung
(`ortsfeld.js:44`), auch in den Stammdaten. PS-7 ist damit allein die Karte.

**Geocoding.** Photon ist an zwei Stellen fest eingetragen: vorwärts
`https://photon.komoot.io/api/?lang=de&limit=6&q=` (`ortsfeld.js:82`, beim
Tippen ab drei Zeichen, 400 ms entprellt), rückwärts
`https://photon.komoot.io/reverse?lang=de&` (`ortswahl.js:34`, nach jeder
Kartenwahl). Die Komponente kennt die Option `adresssuche`
(`ortsfeld.js:118, 150`); die Umkehrsuche kennt keinen Schalter. Der
Krypto-Review (K-6) hat den Abfluss benannt, R78 (8) hat mit F-SP-4
entschieden: Hinweis, Datenschutztext, **Schalter je Installation** mit
Vorgabe „an" — Backlog 137, Schritt 9a. Selbstbetrieb ist eine
Hosting-Frage (SP-12 (c)) und liegt bei R36.

### 1.2 Die Vorschlagsliste der Zielklinik: zwei Listen übereinander (PS-6, Nr. 68)

Das Transportziel trägt **beide** Vorschlagsmechanismen zugleich: eine
native `<datalist>` mit den Stammdaten (`ui_ortsfeld()`, Schlüssel
`datalist`, `ui.php:2008, 2032`; gefüllt aus `transport_dests` des
Standorts, `einsatz_form.php:800–830`) **und** die eigene Photon-Liste
(`<p>suggest`, `ortsfeld.js:255–264, 360–380`). Der Browser öffnet die
`<datalist>` über dem Feld, die Komponente ihre Liste darunter — wer tippt,
sieht zwei Listen, und die native verdeckt die eigene. Mobil zeigt die
`<datalist>` nichts oder Unbrauchbares (Backlog 68, gemeldet an genau
diesem Feld und an den Besatzungsfeldern). Der Stammdatentreffer wird heute
außerdem nur bei **genauer** Namensgleichheit übernommen
(`pruefeVorschlag()`, `ortsfeld.js:271`).

**Wo sonst noch `<datalist>` steht** (Erhebung nach Nr. 68): die
Besatzungsfelder des Diensttags (`index.php`, `renderCrewFields()`,
Zeilen 987–1001, `dl_crew_<rolle>`), die Besatzungsfelder des Einsatzes
(Katalog `crew_<rolle>` mit `suggest_src => 'crew:<rolle>'`,
`mission_fields.php:232`, gerendert in `einsatz_form.php`), das
Transportziel (oben). Sonst nirgends — `bw_unit` ist ein `<select>`,
`other_ema` und `bw_info` sind reine Textfelder. Ein eigenes Muster, das
mobil trägt, existiert bereits zweimal: die Photon-Liste des Ortsfelds und
die Liste der weiteren Rettungsmittel (`einsatz_form.php:1905–1932`,
`.rmlist`).

### 1.3 Weitere Rettungsmittel: der Klick verliert gegen den Blur (PS-2)

`einsatz_form.php:1865–1957`. Die Trefferliste übernimmt auf **`click`**
(Zeile 1921, `b.addEventListener('click', () => hinzu(v))`); das
Eingabefeld versteckt die Liste **150 ms nach `blur`** (Zeile 1957). Ein
Mausklick ist `mousedown` → `blur` des Feldes → `mouseup` → `click`. Dauert
der Klick länger als 150 ms — am Schreibtisch mit Maus oder Touchpad
keine Seltenheit —, ist der Knopf beim `mouseup` schon `hidden`, und der
Browser feuert kein `click`, weil sich das Ziel geändert hat. Ergebnis:
Liste zu, nichts übernommen. Ein Fingertipp ist schneller als 150 ms —
deshalb nur Desktop (F2). **Das Ortsfeld macht es richtig:** `mousedown`
mit `preventDefault()`, „vor blur" (`ortsfeld.js:259–262`). Kein
Prüfmittel klickt; der Bilderlauf fotografiert.

### 1.4 Die Schilder auf der Karte (PS-3, Nr. 72)

Richtiggestellt am 06.09.2026: Gemeint sind die Marker der Einsatzansicht
(`einsatz.php:686–720`, `assets/geo.js:70–125`), nicht Formularknöpfe.
Gemessen am Stylesheet (`style.css:2131–2175`, Token `:root` 320–322):

| Element | Maß | Aufbau |
|---|---|---|
| Kästchen Standort / Zielklinik (`.geo-schild-kasten`) | **36 px** (`--geo-schild`) | Schnee-Fläche, `--strich-stark` (2 px) Dunkelblau als Rand, `--radius`, Schatten; Symbol 20 px |
| Ring Start **oder** Ende | 36 + 2 × (3 + 3) = **48 px** | `box-shadow`: 3 px Schnee, 3 px Blau/Rot (`--geo-ring`) |
| Doppelring (Start **und** Ende am selben Schild) | 36 + 2 × 12 = **60 px** | 3 px Schnee, 3 px Blau, 3 px Schnee, 3 px Rot |
| Einsatzort (`.geo-kreis`) | **32 px** (`--geo-kreis`) | Orange, rund, Schatten, kein Rand (seit S3/AP7) |
| Ringpunkt ohne Schild (`.geo-ringpunkt`) | 16 px | Rand 3 px Blau/Rot |

Der Befund des Auftraggebers („zu groß, vor allem die Umrandung") ist damit
eine Zahl: Das Standort-Schild mit Doppelring ist **fast doppelt so breit
wie der Einsatzort** und trägt drei Ränder übereinander — Randstrich,
Trennring, Farbring. Die weißen 3-px-Ringe sind die „Trennlinie", die F6
verlangt; sie sind so breit wie die Farbringe selbst. Die Maße stehen
doppelt: als Token im Stylesheet und als Zahl in `geo.js` (`SCHILD_PX`,
`KREIS_PX`), weil Leaflet den Anker als Zahl braucht — der Kopfkommentar
sagt es (`geo.js:98–101`).

**Nr. 72 ist belegt, nicht mehr vermutet.** Der Desktop-Screenshot der
Zuarbeit (`vorbereitung-s9/PS-3-ist-einsatzansicht-desktop.png`) zeigt auf
einer Spur, die nach Nordwesten und zurück läuft, **sechs Pfeile, alle
senkrecht nach oben**. Ursache wie im Backlog gelesen: `pfeilIcon()` dreht
mit `style="transform:rotate(…)"` auf einem `<span class="geo-pfeil">`,
und `transform` wirkt nicht auf nicht ersetzte Inline-Elemente.

### 1.5 Windenkacheln (PS-4)

`zeitraum.php:304–312`: Die beiden Windenkacheln tragen
`nurWenn: liste => liste.some(m => m.winch)` — eine **bewusste
Entscheidung** (E30, A13d: „nicht schon, wenn das Rettungsmittel es
könnte"). F7 kehrt sie um. `api/range.php` liefert Einsätze, Tage je Art
und Standorte, aber **keine Fähigkeiten** der Tage; `day_capabilities`
(eingefroren beim Zuordnen, E29) ist die Quelle, und sie fehlt in der
Antwort.

### 1.6 Das Artzeichen (PS-5)

`dt_art_symbole()` (`diensttag_lib.php:131–138`) ist die eine Stelle, an der
aus `days.kind` ein Symbol wird: `hubschrauber` für Luft, `fahrzeug` für
Boden, `ohne-zuordnung` für neutral — gerendert über `ui_artzeichen()`
(`ui.php:318`) in der Diensttage-Leiste (`ui.php:659`), im
Rettungsmittel-Select, in Kacheln. `hubschrauber.svg` ist Tabler
„helicopter" (MIT), 23 Nennungen (`Design.md` 8). PS-10.1 braucht drei
weitere Zeichen für die neuen Typen — dieselbe Stelle, dieselbe Frage.

### 1.7 „Spur · n Punkte" (PS-9)

`einsatz.php:596–606`: die Plakette der Spurfassung (S2/AP4, E-S2-09), in
zwei Formen — neutral „Spur · 852 Punkte", orange „Spur ausgedünnt · n von
n0 Punkten" (Stufe 3). „Spur" steht sonst im Aktionsmenü („Spur als GPX",
`einsatz.php:79`), als Seitenname (`tag_spuren.php`), in Handbuch (39
Stellen) und Wortliste; im Formular heißt dieselbe Sache bereits
„GPS-Aufzeichnung" (`einsatz_form.php:1272`). Zwei Wörter für ein Ding.

### 1.8 Notizen: Der Zielkonflikt existiert nicht (PS-8.2)

Die Vorbereitung hatte die Frage offen gelassen, ob Verschlüsselung und
gleichwertige Suche zusammen erreichbar sind — „hängt davon ab, wo die
übrigen durchsuchbaren Felder verschlüsselt werden". Die Antwort steht in
`api/suchindex.php` und `suche.php`:

- Der Index liefert **den ganzen Bestand einmal je Sitzung** ohne
  Suchparameter; der `pat_blob` reist als Chiffretext mit
  (`suchindex.php:18–21`).
- `suche.php` entschlüsselt nach dem Entsperren im Browser
  (`entschluesselePat()`, Zeile 1049) und baut je Einsatz einen Heuhaufen
  aus Klartext- **und** entschlüsselten Feldern — Einsatznummer, Name,
  Diagnose, Ortsbeschreibung, Adresse, Geburtsdatum (Zeilen 686–703).
- Der Server durchsucht **nichts** („konstruktionsbedingt unmöglich",
  `suche.php:15–17`); Suchbegriffe verlassen das Gerät nicht.

Notizen im `pat_blob` wären also **exakt so** durchsuchbar wie die
Diagnose: nach dem Entsperren, als Freitext, ohne Filter (F14/F18 verlangen
nichts anderes). Der Preis ist nicht die Suche, sondern der **Altbestand**:
Was heute in `missions.notes` steht, kann nur der Browser verschlüsseln.
Das Muster dafür gibt es — `api/kdf_upgrade.php` hebt die Rundenzahl still
beim Anmelden an (eine Transaktion, Nachweis, Zahl).

Was `notes` sonst berührt (vollständig, `grep -rn "\bnotes\b" server/`):
Katalog (`mission_fields.php:459`, Typ `textarea`, max 2000), Formular über
den Katalog, `api/mission.php`, `api/suchindex.php:54, 197`, Export
(`export.js:816`, Spalte `notizen`, Flag `pers`; `api/export_data.php`
liefert die Spalte nur mit `pers`), Import (`import_profiles.js:290, 454`,
Ziel `notes`; `import_ui.js:635`), Backup (`backup_lib.php:232`, Spalte in
der Einsatzzeile), `validate_lib.php`, Referenzbestand und Demo-Fixture.
**Uhr und Handy senden keine Notizen** (`docs/JSON-Vertrag.md`: kein
Treffer). Der Import baut den Blob heute schon aus Feldern mit
`sensitive: true` (`import_profiles.js:45`), der Export entschlüsselt ihn
(`export.js:234`) — beide brauchen nur eine Zuordnung mehr.

**Zweiter Fund:** Es gibt zwei Notizfelder — `missions.notes` und
`days.notes` (Diensttag, `index.php:292`, `api/day.php:114`). Beide bekommen
in 9a den Klartext-Hinweis (Nr. 132, K-12).

### 1.9 Kennzeichnung (PS-8.1)

Verschlüsselt sind: Einsatznummer, Nachname, Vorname, Geburtsdatum, Alter,
Diagnose, Ortsbeschreibung, Einsatzort mit Koordinate, manueller Abfahrtort
(`einsatz_form.php:1770–1800`). Das Formular sagt es an **einer** Stelle —
die Karte „PatientIn" trägt die Zahl „Ende-zu-Ende-verschlüsselt"
(`einsatz_form.php:1169`) — und sonst nirgends: Wer im Block „Einsatz" den
Einsatzort ausfüllt, sieht keinen Unterschied zum Transportziel darunter.
`schloss.svg` und `schloss-offen.svg` sind im Symbolvorrat (9 bzw. 4
Nennungen, Riegel und Entsperren). 9a bringt an die Klartext-Freitextfelder
den Hinweis „Klartext — keine Patientendaten" (Katalogschlüssel `hinweis`,
Nr. 132); das Symbol ist ausdrücklich hierher verschoben („Symbol kommt mit
Nr. 108").

### 1.10 Rettungsmittel: Die Luft/Boden-Unterscheidung ist tief (PS-10)

`vehicles.kind ENUM('air','ground')` (`schema.sql:103`) ist keine
Beschriftung, sondern Steuergröße. Sie entscheidet über:

| Was | Wo |
|---|---|
| Rollensatz (Pilot 1/2, HEMS-TC, Flugretter gegen Fahrer, Praktikant; Sonstige beide) | `CREW_ROLES`, `crew_roles_fuer_art()` (`db.php:416–454`) |
| Fähigkeiten (Winde, Bergwacht) nur luftgebunden | `einstellungen.php:565, 1759` |
| Kachelsätze und Reiter der Statistik (Luft / Boden / Gemischt) | `zeitraum.php:51, 254–315, 592–601, 794` |
| Artzeichen | `dt_art_symbole()` |
| Höhe des Einsatzorts | `einsatz.php:667` |
| Backup-Rückweg | `backup_lib.php:1060, 1320` |
| Diensttag-Schnappschuss | `days.kind`, eingefroren beim Zuordnen (E8) |

Ein dritter ENUM-Wert je neuem Typ träfe jede dieser Stellen. Die neuen
Typen (Bergwacht, Veranstaltung, Sonstiges) sind aber **keine dritte
Betriebsart** — sie fliegen oder fahren, und die Antwort vom 06.09.2026
sagt es: Bergwacht kann Luft sein.

**PS-10.2 ist im Datenmodell angelegt.** `days.vehicle_id` darf NULL sein
(`ON DELETE SET NULL`), `vehicle_name`, `base_name`, `base_lat/lon`, `kind`
sind Momentaufnahmen (E8), und **Suche und Filter lesen nur diese**
(`suchindex.php:75–84`; `suche.php:622` baut die Filterliste aus den
Namen des Index). Ein Tag mit Namen und Art, aber ohne `vehicle_id`, wäre
heute schon such- und filterbar. Was fehlt, ist der Eingabeweg im
Zuordnungsformular (`index.php:250–297`: zwei `<select>` aus Stammdaten,
sonst nichts) und die Prüfung in `dt_zuordnen()`.

**PS-10.3, die Ursache:** Die Besatzungsfelder entstehen im Browser aus
`meta.crew` (`renderCrewFields()`, `index.php:962`), und `meta.crew` ist die
Zeilenmenge in `day_crew`. Die füllt `dt_zuordnen()` **beim Speichern** aus
`vehicle_roles` (`api/day.php:104–112`). Vor dem ersten Speichern gibt es
keine Zeilen, also keine Felder — daher der Umweg. Das Rettungsmittel-Select
trägt schon `data-kind` und `data-base` je Option (`index.php:274–276`),
aber nicht die Rollen.

### 1.11 Wording (R74 (6))

Das Feld heißt „Transportziel" (Katalog, Formular, Hinweis auf der
Stammdatenseite: „Vorschläge für das Feld ‚Transportziel'",
`einstellungen.php:1595`); Stammdaten, Karte und Handbuch sagen
„Zielklinik" (Handbuch 16 gegen 14 Nennungen; `einsatz.php:712`
`m.dest_name || 'Zielklinik'`). Mit PS-7 (ein Ziel ohne Klinik wird
möglich) ist die Unterscheidung inhaltlich richtig — sie muss nur
ausgesprochen werden (E-S9-15).

### 1.12 Vertrag, Formate, Prüfmittel

- **JSON-Vertrag (R12):** unberührt. Uhr und Handy übertragen weder
  Rettungsmittel noch Ziele noch Notizen; Standort und Rettungsmittel werden
  in der Weboberfläche zugeordnet (`JSON-Vertrag.md:509`).
- **Backup-Format:** Nutzlast 9 (seit R64). PS-10.1 und Nr. 69 ändern
  `vehicles` und den Diensttag-Schnappschuss → **Nutzlast 10**, eine
  Formatänderung, ein Kreislauf (R24).
- **Export-Format:** `vehicles` und die Tageszeile bekommen Typ und
  Kurzname; `notizen` wandert aus `missions.notes` in den Blob-Teil
  (Flag `pers` bleibt).
- **Referenzbestand** (`tools/referenzdatensatz/`): Notizen im Klartext,
  Rettungsmittel ohne Typ — beides zieht mit den Kreisläufen nach; der
  Demo-Reset spielt die Fixture alle 30 Minuten ein und prüft damit den
  Rückweg dauerhaft.
- **Prüfmittel, die S9 braucht und die es nicht gibt:** ein Klick. Weder
  der Bilderlauf noch die Vollständigkeit noch die Wartungsprobe bedienen
  ein Element; PS-2 und Nr. 148 sind beide daran vorbeigelaufen. Das
  Prüfdokument nennt Browserläufe mit Zahl (Abschnitt 4).

---

## 2. Entscheidungen

### 2.1 Beschlüsse E-S9-01 bis E-S9-17

Aus F1–F19 der Vorbereitung (dort mit Datum) und den Antworten des
Auftraggebers vom 06.09.2026. Jede Entscheidung nennt den **Ort** nach R74.

**E-S9-01 — Notizen des Einsatzes werden Ende-zu-Ende verschlüsselt (PS-8.2, F14/F18).**
`missions.notes` wandert als Schlüssel `notes` in den `pat_blob`. Die Suche
ändert sich nicht — sie findet Notizen nach dem Entsperren wie die
Diagnose, als Freitext, ohne Filter. **Der Feldkatalog bekommt den
Schlüssel `'store' => 'pat'`** (neben `'crew'`): Ein Feld damit wird im
verschlüsselten Bereich des Formulars gerendert (ohne `name`, innerhalb des
Riegels `PAT_INPUTS`), beim Absenden in den Blob geschrieben, von
`api/mission.php`, `api/suchindex.php`, Export und Backup **nicht** als
Spalte geführt, vom Import als `sensitive` behandelt. Begründung gegen den
Weg des Einsatzorts (kein Katalogeintrag): Die Zusage „Feldkatalog statt
Sonderfall" (`CLAUDE.md` 4) ist jünger als die Ausnahme, und S11 verschiebt
die Zielklinik denselben Weg — dann trägt der Schlüssel. **Altbestand:**
still angehoben beim nächsten Entsperren, je Konto einmal
(`api/pat_anheben.php`: der Browser schickt je Einsatz den neuen Blob, der
Server setzt `notes` auf NULL — eine Transaktion, Zählung in der Antwort,
Muster `kdf_upgrade.php`); die Spalte bleibt NULL-fähig bis zum
Neuaufsetzen (P8, R60). Ein Konto, das sich nie entsperrt, behält
Klartext — das ist derselbe Zustand wie heute, nicht schlechter. **Der
Platzhalter „Freitext (keine Patientendaten!)" entfällt** mit der
Verschlüsselung. **Nicht verschlüsselt: `days.notes`** — Betriebsnotizen
des Diensttags, Klartext mit dem Hinweis aus Nr. 132 (Antwort 1 vom
06.09.2026). *Ort:* Einsatzformular, Karte „Notizen" wandert in den
verschlüsselten Bereich (Abschnitt 2.5).

**E-S9-02 — Schloss-Symbol und eine Legende (PS-8.1, F13).**
Jedes verschlüsselte Feld trägt links neben der Beschriftung
`schloss.svg` (vorhandenes Symbol, `symbol-klein`); die Klartext-Felder
tragen den **Hinweis aus Nr. 132** (am 07.09.2026 ganz hierher gewandert,
K-12): Katalogschlüssel `hinweis` mit dem einen Text „Klartext — keine
Patientendaten" an `bw_info`, `other_ema` und den Besatzungs-Freitexten
(`crew_*`), dazu am Diensttag-Notizfeld `days.notes` (`index.php`, außerhalb
des Katalogs); `notes` bekommt ihn **nicht**, es ist verschlüsselt
(E-S9-01). Kein zweites Symbol für „offen", damit die Karte nicht zum
Zeichenteppich wird. Die **Legende** ist
die zugeklappte Karte **„Was hier gilt"** am Ende des Formulars (R74 (5)):
drei Sätze — was das Schloss bedeutet, was Klartext bedeutet, dass der
Server das eine nie und das andere immer sieht. Die Karte „PatientIn"
behält ihre Zahl „Ende-zu-Ende-verschlüsselt". *Ort:* Einsatzformular;
kein neuer Baustein (Symbol am Label ist der Baustein „Beschriftung mit
Zusatz", `Design.md` 9).

**E-S9-03 — „Spur" heißt für die NutzerIn „GPS-Daten" (PS-9, F15).**
Überall, wo die NutzerIn liest: Plakette („GPS-Daten" ohne Zahl; die
ausgedünnte Fassung „GPS-Daten ausgedünnt", orange, **ohne Zahlen**),
Aktionsmenü („GPS-Daten als GPX"), Seite „Spuren" → „GPS-Daten",
Tagesübersicht, Handbuch. **Nicht** umbenannt: Code (`spur_lib.php`,
`spur_*`), `Technik.md`, JSON-Vertrag, Backup-Format — dort ist „Spur" der
Fachbegriff, und die Zusage in `CLAUDE.md` 4 nennt ihn. Die Wortliste
(R28) bekommt eine Regel für die sichtbaren Bereiche; Bereich `a` (PHP)
prüft nur sichtbare Zeichenketten, Kommentare bleiben. *Ort:* keine neue
Funktion.

**E-S9-04 — Windenkacheln nach Fähigkeit, nicht nach Zählung (PS-4, F7).**
Kehrt E30/A13d ausdrücklich um: Die beiden Windenkacheln erscheinen, sobald
**ein Diensttag im Zeitraum die Fähigkeit `winch` trägt**
(`day_capabilities`), auch mit Wert 0. `api/range.php` liefert dazu
`faehigkeiten: {winch: true|false, bergwacht: …}` aus einer EXISTS-Abfrage;
`nurWenn` liest sie statt der Einsatzliste. Begründung: „null
Windeneinsätze" ist eine Aussage über den Dienst, „Winde nicht
eingerichtet" eine über die Stammdaten — E30 hatte beides gleichgesetzt, um
Platz zu sparen; der Auftraggeber will die Aussage. *Ort:* Zeitraumübersicht,
Kachelsatz Luft; keine Änderung an Boden und Gemischt.

**E-S9-05 — Geocoding: eine Quelle, drei Stellen, zwei Schalter, eine Adresse (PS-1, F-SP-4, Antwort 5).**
Photon bleibt die Quelle für Vorschläge beim Tippen, Umkehrsuche nach der
Kartenwahl und die neue Dialogsuche. **Ein Modul** `assets/geocoder.js`
(`EdGeocoder.suche(q)`, `EdGeocoder.umkehr(lat, lon)`, `EdGeocoder.an()`)
löst die beiden fest eingetragenen Adressen ab; die drei Aufrufer kennen
nur das Modul. **Zwei Schalter — beide baut S9** (Nr. 137 ist am 07.09.2026 ganz
hierher gewandert): (1) **je Installation** — Betrieb → Servereinstellungen,
Karte **„Adresssuche"**, Schalter „Adresssuche im Internet", Vorgabe an,
`app_state`-Schlüssel `adresssuche`; (2) **je Konto** — Einstellungen →
Profil, Karte **„Datenschutz"**, Schalter „Adressvorschläge aus dem
Internet", Vorgabe an, Spalte `users.adresssuche`. Dazu aus SP-12 (a): der
**Hinweis am Feld** (Kleinzeile unter dem Ortsfeld, nur wenn die Suche an
ist: „Vorschläge und Umkehrsuche kommen von photon.komoot.io; getippter
Text verlässt das Gerät") und die **Nennung im Datenschutztext**
(Vorlage in `rechtstexte_lib.php`, Abschnitt zur Adresssuche mit
Dienstadresse als Platzhalter). Die Installation ist die Obergrenze: Ist sie aus, ist
der Kontoschalter ausgegraut mit dem Satz, wer ihn abgeschaltet hat. Aus
heißt: keine Vorschläge, keine Umkehrsuche, keine Dialogsuche —
Koordinaten, Plus Codes, „Meine Position" und die Karte bleiben; das
Suchfeld im Dialog erscheint dann nicht. **Eine Adresse:** die Karte
„Adresssuche" trägt das Feld „Dienst" (Vorgabe `https://photon.komoot.io`,
`app_state`), das Modul liest sie aus dem Krypto-Bootstrap
(`ui_krypto_bootstrap()`, dort stehen schon Konstanten für den Browser).
Wer nach der Hosting-Entscheidung (R36) einen eigenen Photon betreibt,
trägt die Adresse ein — die Anfragen mit dem Einsatzort verlassen dann das
eigene Haus nicht; kein Code, keine Auslieferung. **Nicht Umfang:**
Zwischenserver (SP-12 (b), „nicht wert"), Kachelserver (die Karte ist die
Anwendung). *Ort:* Betrieb → Servereinstellungen (Installation, R74 (1):
BetreiberIn, trifft alle); Einstellungen → Profil (Konto, R74 (1):
NutzerIn). **Berührung mit 9a:** Abschnitt 2.3.

**E-S9-06 — Der gemeinsame Kartendialog (PS-1, PS-7, PS-11, Nr. 70; F1, F11, F12).**
`ortswahl.js` bleibt die eine Umsetzung und bekommt: (a) ein **Suchfeld**
im Dialogkopf — Treffer aus `EdGeocoder.suche()`, ein Klick **setzt das
Kreuz** auf den Treffer (`setView`), übernimmt aber nichts (F1); ohne
Adresssuche fehlt das Feld; (b) die **aufgezeichnete Spur** des Einsatzes,
wenn der Aufrufer sie übergibt: Linie in der ersten Spurfarbe, Ringpunkte
für Start und Ende (`EdGeo.markerRing`), **keine Pfeile, keine Luftlinie**;
ist das Feld leer, öffnet die Karte auf der Spur (`fitBounds` mit Rand),
sonst auf der Koordinate wie heute (PS-11); das Formular übergibt die Spur
beim Registrieren (`EdOrtswahl.registriere(praefix, steuer, {spur})`) und
holt sie beim Laden über `api/mission.php` — **nicht** eingebettet, damit
S11 nur den Abholweg ändert; (c) die **zweite Einbauform**: `ui_ortsfeld()`
rendert Pin-Knopf und Blatt auch bei `feld => false` (Nur-Lage-Fassung),
damit Zielklinik und Standort in den Stammdaten (`einstellungen.php`,
`admin_stammdaten.php`) und der Standort nach Nr. 70 dieselbe Karte
bekommen; (d) **das Transportziel im Katalog:** `'ortswahl' => true` am
`loc`-Feld, generisch gerendert — ein per Karte gewähltes Ziel ist ein
**Ad-hoc-Wert des Einsatzes** (F12), kein Stammdatensatz; die Koordinate
liegt wie bisher im Klartext (`dest_lat/lon`, bis S11). Koordinateneingabe:
unverändert (F11 — sie existiert). *Ort:* der vorhandene Pin-Knopf am
Feld (R74 (3): Ausnahme eine Ebene tiefer), an fünf Stellen: Einsatzort,
manueller Abfahrtort, Transportziel, Zielklinik-Stammdaten (Konto und
Admin), Standort-Stammdaten (Konto und Admin). Mockup M-S9-04.

**E-S9-07 — Eine Vorschlagsliste als Baustein (PS-6, Nr. 68; F9, F10).**
Die `<datalist>` verschwindet aus der Anwendung. Ein Baustein
`assets/vorschlagsliste.js` (`EdVorschlaege`, aus der Photon-Liste des
Ortsfelds herausgelöst) zeigt Treffer in **Gruppen**: beim Transportziel
oben **höchstens zwei Stammdatentreffer** (F10), abgesetzt mit
Gruppenzeile, darunter die Adressvorschläge; Stammdatentreffer schon bei
**Teilübereinstimmung** (nicht mehr nur bei Namensgleichheit), ein Treffer
setzt Name und Koordinate. Stammdatentreffer **nur im Zielklinik-Kontext**
(F9): Einsatzort und Standort zeigen allein Adressen. Der Baustein
bedient `mousedown`, Pfeiltasten, Enter, Escape, und versteckt sich nach
`blur` — die drei heutigen Listen (Ortsfeld, Rettungsmittel, künftig
Besatzung) werden Verwendungen davon. *Verwendungen:* Transportziel,
Einsatzort und Abfahrtort (nur Adressen), Besatzungsfelder des Diensttags
(E-S9-11) und des Einsatzes (Katalog `suggest_src`), weitere Rettungsmittel
(E-S9-08). *Ort:* keine neue Seite; Baustein in `Design.md` 9. Mockup
M-S9-03 (Gruppenzeile ist neu).

**E-S9-08 — Weitere Rettungsmittel übernehmen auf `mousedown` (PS-2, F2).**
Die Liste wird eine Verwendung von E-S9-07 und erbt damit das
Ortsfeld-Muster (`mousedown` + `preventDefault`). Prüfung mit Zahl: ein
Browserlauf, der `mousedown` — 300 ms — `mouseup` fährt, vorher 0 von 3
Treffern übernommen, nachher 3 von 3. *Ort:* unverändert.

**E-S9-09 — Rettungsmittel bekommen einen Typ und einen Kurznamen (PS-10.1, Nr. 69; F16, Antwort 3).**
Zwei neue Spalten an `vehicles`: **`typ`** `ENUM('standard','bergwacht',
'veranstaltung','sonstiges') NOT NULL DEFAULT 'standard'` und **`kurz`**
`VARCHAR(16) NULL`. `kind` bleibt die **Betriebsart** (Luft/Boden) und
steuert weiter, was sie heute steuert (Rollen, Fähigkeiten, Kachelsatz,
Höhe). Die Phasen sind davon unberührt — ihre Beschriftungen sind seit
Web 6.0.0 neutral (E20/E21, `PHASE_LABELS` in `db.php`), damit die Uhr die
Art nicht kennen muss; ein Bergwacht-Dienst in der Luft braucht deshalb
nichts Eigenes. Regeln je Typ:

| Typ | Betriebsart | Rollen-Vorlagen | Fähigkeiten | Standort | Artzeichen |
|---|---|---|---|---|---|
| Standard | wählbar | nach Betriebsart (wie heute) | nach Betriebsart | Pflicht (wie heute) | Hubschrauber / Fahrzeug (E-S9-13) |
| Bergwacht | **wählbar** | **keine** | nach Betriebsart (Luft: Winde, Bergwacht anhakbar) | optional | eigenes Zeichen |
| Veranstaltung | **fest Boden** | **keine** | keine | optional | eigenes Zeichen |
| Sonstiges | **wählbar** | **keine** | nach Betriebsart | optional | eigenes Zeichen |

„Keine Rollen-Vorlagen" heißt: `vehicle_roles` bleibt leer, der Diensttag
bekommt keinen Rollensatz, das Zuordnungsformular zeigt keine
Besatzungsfelder (F19) — mit dem bestehenden Hinweistext, angepasst.
„Standort optional" heißt: `vehicles.base_id` wird NULL-fähig; ein
Rettungsmittel ohne Standort hat keine Vorschlagslisten (E15 — die hängen
am Standort) und steht in der Standortliste unter dem letzten Eintrag
„Ohne Standort" (E-S9-18). **Kurzname** (Nr. 69): bis 16 Zeichen, freiwillig; die
Diensttage-Leiste, die Kacheln und die Plaketten zeigen ihn, wenn er
gesetzt ist, sonst den Namen; Formulare und Export zeigen den vollen Namen.
**Der Diensttag friert beides ein:** `days.vehicle_typ`, `days.vehicle_kurz`
(E8). **Backup-Format Nutzlast 10, Export, Import, Kreisläufe (R24),
Referenzbestand** ziehen in einem Paket nach — eine Formatänderung (wie
R64/Nr. 63). *Ort:* Einstellungen → Standorte → Standortseite (Konto) und Verwaltung →
Stammdaten (Admin), im Dialog „Rettungsmittel anlegen" (E-S9-19): Typ als
Auswahl **vor** der Betriebsart, Betriebsart bei Veranstaltung ausgegraut
auf Boden.

**E-S9-10 — Ein Rettungsmittel nur für den Tag (PS-10.2; F17, Antwort 4).**
Das Zuordnungsformular der Tagesübersicht bekommt in der
Rettungsmittel-Auswahl den letzten Eintrag **„Anderes Rettungsmittel …"**;
er klappt drei Felder auf: **Bezeichnung** (Pflicht, bis 64 Zeichen),
**Typ** (E-S9-09) und **Betriebsart** (nach Typ), **Standort**: Auswahl aus
der Standortliste **oder** Freitext (bis 120 Zeichen) — ein Feld mit
Vorschlagsliste (E-S9-07), ein Treffer übernimmt den Datensatz samt
Koordinate, Freitext bleibt Freitext ohne Koordinate. Gespeichert wird
**nur am Tag**: `vehicle_id` NULL, `vehicle_name`, `vehicle_typ`, `kind`,
`base_id` (bei Treffer) oder NULL, `base_name`, `base_lat/lon` (bei
Treffer). Kein Stammdatensatz entsteht (F17); wer ihn will, legt ihn unter
Einstellungen an. Kein Rollensatz, keine Fähigkeiten (F19; `day_crew` und
`day_capabilities` bleiben leer). Suche und Filter greifen über die
Momentaufnahme — ohne Änderung. `dt_zuordnen()` bekommt den zweiten Weg
und prüft ihn über `validate_lib.php`. *Ort:* Tagesübersicht, Formular
„Diensttag-Daten bearbeiten" (R74 (2): Handlung am Diensttag liegt beim
Diensttag), eine Ebene tiefer als die Auswahl (R74 (3)). Kein Mockup —
Felder und Auswahl sind vorhandene Bausteine; der Bilderlauf zeigt den
aufgeklappten Zustand.

**E-S9-11 — Rollen sofort nach der Auswahl (PS-10.3, F19).**
Ein lesender Aufruf **`api/day.php?vorschau=<vehicle_id>`** liefert
Rollensatz (`vehicle_roles` mit Beschriftung) und Vorlagen (`crew_presets`
des Standorts) **ohne zu speichern**; `renderCrewFields()` ruft ihn bei
`change` des Selects und zeichnet die Felder sofort. Beim Speichern friert
`dt_zuordnen()` wie heute ein; Namen für Rollen, die das Rettungsmittel nicht
anbietet, werden weiterhin verworfen. Für „Anderes Rettungsmittel" und die
Typen ohne Vorlagen: keine Felder, ein Satz. Die Vorlagen kommen über
E-S9-07 (löst Nr. 68 an den Besatzungsfeldern). *Ort:* unverändert.

**E-S9-12 — Kartenschilder: kleiner, ein Rand, eine Trennlinie (PS-3; F3–F6).**
**Entschieden mit M-S9-01 (V1, freigegeben 06.09.2026):** der
**Farbring ersetzt den dunkelblauen Rand.** Kasten **30 px** (`--geo-schild`),
Rand **3 px** (`--geo-ring`): blau bei Start, rot bei Ende, dunkelblau (2 px)
ohne Aufzeichnung; „beide" = blauer Rand, 1 px Schnee, 2 px roter Außenring
(38 px statt 60). Außen immer **1 px Schnee** als Trennlinie zur Karte
(F6). Einsatzort-Kreis **28 px** (`--geo-kreis`), Symbole 18 px im Kasten,
16 px im Kreis; Ringpunkt ohne Schild 14 px mit 2 px Rand, „beide" mit 1 px
Schnee und 2 px Rot. Nachgemessen am Render: ohne 32, Start/Ende 32, beide
38, Einsatzort 28 px. Die Token `--geo-schild`,
`--geo-kreis`, `--geo-ring` tragen die neuen Werte, `geo.js` die Zahlen
(beide, wie der Kopfkommentar verlangt). **Nr. 72 dazu:** `.geo-pfeil` wird
`display:inline-block` (oder die Drehung wandert in das SVG), damit
`transform` greift — Sichtprüfung Pflicht, Vorher/Nachher-Bild. Bedienhöhe
R76 gilt nicht (Schilder sind Zeichnungen, S3/AP7), als Untergrenze am
Finger gilt 24 px (WCAG 2.5.8). *Ort:* Karte der Einsatzansicht,
Tagesübersicht, Zeitraumübersicht (alle drei zeichnen über `geo.js`).

**E-S9-13 — Artzeichen: ein Satz aus einer Hand (PS-5, PS-10.1; F8, F16).**
`dt_art_symbole()` wird zu `dt_art_symbol(kind, typ)`: Standard-Luft,
Standard-Boden, Bergwacht, Veranstaltung, Sonstiges je ein Zeichen, neutral
wie bisher. **Entschieden mit M-S9-02 (06.09.2026):** Der Hubschrauber
**bleibt Tabler „helicopter"**, das Fahrzeug Tabler „ambulance" — PS-5 ist
damit mit „Ist" beantwortet; die eigene Strichzeichnung (B) und die
Bildmarken (C, C2) sind gesehen und verworfen. Neu: `bergwacht.svg` =
Tabler „mountain", `veranstaltung.svg` = Tabler „building-stadium",
`sonstiges.svg` = Tabler „dots-circle-horizontal" — alle Outline, MIT,
Strich 2 im 24-px-Raster wie der übrige Vorrat; `ohne-zuordnung.svg`
bleibt der neutrale Tag. Herkunft in `Design.md` 8 und `Lizenzen.md`;
Symbolvorrat 49 → 52. Das Typzeichen zeigt die Betriebsart nicht (ein
Bergwacht-Dienst in der Luft trägt den Berg); der Tooltip nennt beide
(`$titel`, `ui.php:654`). *Ort:* eine Funktion, alle Stellen.

**E-S9-14 — Sprungliste bei vielen Rettungsmitteln (Nr. 44).**
Eine Zeile runder Marken unter der Überschrift „Rettungsmittel" eines
Standorts, ab **sechs** Einträgen, jede springt zum Eintrag; das Mockup
vom 30.08.2026 (`docs/mockups/N1-sprungliste.html`) wird gegen den heutigen
Stand (S3, S8) neu gerendert — M-S9-05 —, die Marken benutzen die Plakette
oder den Zähler-Baustein, nicht einen dritten Kreis. *Ort:* die Karte „Rettungsmittel" der Standortseite (E-S9-18), Konto und
Admin.

**E-S9-18 — Standort zuerst: eine Liste, eine Seite je Standort (PS-12; Auftrag 07.09.2026).**
*Freigegeben 07.09.2026 (M-S9-06 und M-S9-07).* Der Menüpunkt „Rettungsmittel"
entfällt; **„Standorte"** ist die eine Stammdatenseite: eine Karte mit einer
Zeile je Standort (Artzeichen, Name, Kleinzeile mit den drei Zahlen
Rettungsmittel · Besatzung · Zielkliniken, Stern für die Vorbelegung,
„systemweit" als Plakette, Winkel rechts), zuletzt **„Ohne Standort"** für
Rettungsmittel der Typen ohne Standort (E-S9-09). Nichts ist zugeklappt.
Die **Standortseite** (`einstellungen.php?t=standort&s=<id>`) trägt den
Rückweg „‹ Standorte", den Namen als Titel, ein Aktionsmenü (Löschen, Als
Vorbelegung) und vier Karten mit `id`: **Standort** (Bezeichnung, Lage als
Nur-Lage-Ortsfeld mit Pin — Nr. 70 —, Koordinaten-Chip, Speichern),
**Rettungsmittel** (Sprungliste ab sechs, E-S9-14), **Besatzung** (Filterfeld,
Rollen als Zwischentitel), **Zielkliniken** (Filterfeld). Oben ein
**Inhaltsverzeichnis aus drei Kennzahlen** (`ui_kennzahl()` mit `href`,
`Design.md` 9.10 — Wert in Bricolage, Beschriftung darunter; Raster mit
drei Spalten, eine Stylesheet-Zeile; die Kachel des Sprungziels wird
`.aktiv`) — **nicht** aus Pillen: Die Pille gehört allein der Sprungliste,
zwei Bausteine für zwei Aufgaben (Rückmeldung 07.09.2026, M-S9-07). Die
Karte „Standort" braucht keine Kachel, sie steht direkt darunter. Am Ende
jeder Karte **„Zum Anfang"** (gedämpft, 44 px, springt auf die
Kennzahlen). Am Desktop
liefern die vier Karten-IDs die Unterpunkte in der Leiste von selbst
(`menue.js`, S8). Das **Filterfeld** (Feld mit Lupe innen, filtert im Browser)
ist ein neuer Baustein neben „Zum Anfang"; die Pille bekommt die
Zahl-Variante. **Verwaltung → Stammdaten** (Admin) bekommt dieselbe Liste
und Seite (`sd_zeile()`). Begründung: Alles hängt am Standort
(`crew_presets.base_id`, `transport_dests.base_id`, E15) — die heutige
Seite „Rettungsmittel" ist die Standortseite mit falschem Namen und
zugeklappten Karten. Verworfen: ein Standortwechsler auf einer Seite (eine
Ebene, die man nicht sieht) und die Aufteilung nach Datenart (sauber im
Modell, aber man arbeitet an seinem Standort, nicht an „allen
Zielkliniken"). *Ort:* Einstellungen → Standorte (Konto), Verwaltung →
Stammdaten (Admin); Formate und Datenmodell unberührt; Handbuch 6 wird neu
geschrieben. Mockup M-S9-06.

**E-S9-19 — Anlegen und Bearbeiten im Dialog, Landung auf der neuen Zeile (PS-12; Auftrag 07.09.2026).**
*Freigegeben 07.09.2026 (M-S9-07).* Die Eingabe unter jeder Liste
(`sd_form()`) entfällt; „Anlegen" im Kartenkopf und „Bearbeiten" im
Zeilenmenü öffnen einen **Dialog** (`Design.md` 9.11), der zur Karte
gehört und den Standort in der Unterzeile trägt — kein Feld. Drei Dialoge:
**Rettungsmittel** (Bezeichnung, Kurzname, **Typ vor Betriebsart**,
Betriebsart als Radiozeile — bei Veranstaltung fest Boden —, Rollen und
Fähigkeiten nur bei Standard, Fähigkeiten nur bei Luft, Standort als
Auswahl nur bei den drei Typen), **Besatzungsmitglied** (Rolle, Name),
**Zielklinik** (Bezeichnung, Nur-Lage-Ortsfeld mit Lupe und Pin,
Koordinaten-Chip). Fehler bleiben im Dialog; Erfolg antwortet mit einem
Redirect auf die Standortseite **mit `#veh-<id>`** (`#crew-`, `#dest-`):
Die neue Zeile trägt die `id`, `:target` färbt sie Orange-hell (M-S9-05),
`scroll-padding-top` setzt sie unter die Kopfleiste; die Sprungmarke trägt
denselben Zustand. **Keine zusätzliche Erfolgsmeldung** am Seitenanfang —
die hervorgehobene Zeile ist die Bestätigung. „Standort anlegen" landet auf
der neuen Standortseite. Gilt für Verwaltung → Stammdaten ebenso. *Ort:*
die Karten der Standortseite. Mockup M-S9-07.

**E-S9-15 — Zwei Wörter, zwei Dinge (R74 (6)).**
**„Transportziel"** ist das Feld am Einsatz (es kann seit PS-7 ein Ort ohne
Klinik sein); **„Zielkliniken"** sind die Stammdatensätze, die es
vorschlagen. Handbuch, Karte (`m.dest_name || 'Transportziel'`) und
Stammdatenhinweis sagen es so; die Wortliste bekommt keine Regel, weil
beides bleibt.

**E-S9-16 — Prüfen heißt klicken.**
S9 liefert ein Prüfmittel **`tools/klickprobe/`** (Playwright, wie der
Bilderlauf): eine Liste von Bedienwegen mit erwartetem Ergebnis — Klick
auf einen Vorschlag mit gehaltener Maus (E-S9-08), Kartendialog öffnen und
Treffer setzen, Rettungsmittel wechseln und Felder zählen (E-S9-11),
`href`-Ziele der Tagesübersicht gegen die gelesenen Parameter (Nr. 148),
Anlegen im Dialog → Landung auf der neuen Zeile mit `:target` (E-S9-19),
Filterfeld tippen → Zeilenzahl (E-S9-18). Es
läuft zuletzt (`CLAUDE.md` 6) und nennt je Weg eine Zahl. Begründung: PS-2
und Nr. 148 waren beide ein Klick, den niemand getan hat.

**E-S9-17 — Migrationen und Formate, in einem Register.**

| Kennung (ohne Datum, K3) | Was | Paket |
|---|---|---|
| `…_rettungsmittel_typ` | `vehicles.typ`, `vehicles.kurz`, `vehicles.base_id` NULL-fähig; `days.vehicle_typ`, `days.vehicle_kurz`; Bestand: `typ = 'standard'`, `vehicle_typ` aus `vehicles` nachgefüllt (wie R64) | AP4 |
| `…_adresssuche_konto` | `users.adresssuche TINYINT(1) NOT NULL DEFAULT 1` | AP2 |
| keine | `missions.notes` bleibt als Spalte (NULL nach der Anhebung); `app_state`-Schlüssel `geocoder_url` | AP7, AP2 |

Backup-Format **Nutzlast 10** (AP4), Export-Format eine Fassung, JSON-Vertrag
unverändert, Migrationsregister beim Merge gegengezählt.

### 2.2 Warum fünf Backlog-Punkte dazukommen (Beschluss 06.09.2026)

| Nr. | Grund |
|---|---|
| 72 | dieselbe Datei wie PS-3 (`geo.js`), und der Fehler ist auf dem Zuarbeits-Screenshot belegt |
| 70 | dieselbe Komponente wie PS-7 — die zweite Einbauform des Pin-Knopfs bedient beide |
| 68 | PS-6 baut die eine Vorschlagsliste; sie nur an der Zielklinik einzusetzen und die Besatzungsfelder beim alten Muster zu lassen, wäre der Wildwuchs, den R74 verbietet |
| 69 | PS-10.1 ändert `vehicles`, den Diensttag-Schnappschuss und beide Formate ohnehin — zwei Formatänderungen kosten zwei Kreisläufe, eine kostet einen |
| 44 | PS-10.1 baut die Rettungsmittel-Seite um; das Mockup liegt seit dem 30.08. und braucht nur ein Neu-Rendern und eine Freigabe |

### 2.3 Verhältnis zu Schritt 9a (Sofortpaket Sicherheit) — Beschluss 07.09.2026

9a hatte am 07.09.2026 nicht begonnen. Damit S9 nicht auf etwas wartet,
das niemand angefangen hat, sind **Nr. 137 (Photon) und Nr. 132
(Klartext-Hinweis) ganz nach S9 gewandert** (E-S9-05, E-S9-02). Die
gemeinsamen Dateien — `ortsfeld.js`, `ortswahl.js`, `mission_fields.php`,
`betrieb_server.php` — gehören damit S9 allein; **9a und S9 berühren sich
in keiner Anwendungsdatei mehr** und laufen parallel auf eigenen Zweigen.
Was sich noch berührt, ist Buchführung: `CHANGELOG.md`, `Rahmenplan.md`,
`Backlog.md`, `version.php` — wer zweiter mergt, zieht nach (K7);
Versionsstufen vergibt, wer mergt, nicht wer beginnt. **9a behält:** Nr.
127–131, 133–136, 138 (Web), 140 (Integritätswache), 142–145 und den
Räumteil von 114 (Android). Die Vorbereitung des Sicherheitspakets trägt
den Vermerk (SP-12 (a), K-12 → S9).

### 2.4 Offene Fragen

**Keine.** Die letzte Kandidatin — ob ein Bergwacht-Dienst in der Luft
eigene Phasenbeschriftungen braucht — hat sich am Code erledigt:
`PHASE_LABELS` sind seit Web 6.0.0 neutral („Ausrücken", „Ankunft Klinik";
E20/E21), die Betriebsart spielt dort keine Rolle. Was noch aussteht, sind
Freigaben, keine Fragen: die fünf Mockups (Abschnitt 6).

### 2.5 Ort je Funktion (R74 (7))

| Funktion | Bereich | Seite | Ebene |
|---|---|---|---|
| Dialogsuche, Spur im Dialog | NutzerIn | Einsatzformular; Stammdaten | Pin-Knopf am Feld → Blatt → Dialog (Ausnahme, zwei Ebenen tief) |
| Kontoschalter Adresssuche | NutzerIn | Einstellungen → Profil | Karte „Datenschutz" |
| Dienstadresse Geocoder | BetreiberIn | Betrieb → Servereinstellungen | Karte „Adresssuche" (mit dem 9a-Schalter) |
| Notizen verschlüsselt | NutzerIn | Einsatzformular | verschlüsselter Bereich, Karte „Notizen" |
| Schloss und Legende | NutzerIn | Einsatzformular | am Label; Karte „Was hier gilt" (zu) |
| Typ, Kurzname, Standort optional | NutzerIn / Admin | Standortseite, Dialog „Rettungsmittel anlegen" | Dialogfelder |
| Anderes Rettungsmittel | NutzerIn | Tagesübersicht, „Diensttag-Daten bearbeiten" | letzter Eintrag der Auswahl, klappt auf |
| Rollen sofort | NutzerIn | dieselbe Stelle | — |
| Standortliste, Standortseite, Dialoge | NutzerIn / Admin | Einstellungen → Standorte; Verwaltung → Stammdaten | Liste → Seite je Standort (Rückweg), Dialog aus dem Kartenkopf |
| Sprungliste, Kennzahlen, Filterfeld, „Zum Anfang" | NutzerIn / Admin | Standortseite | in den Karten |
| Windenkacheln | NutzerIn | Zeitraumübersicht | Kachelsatz Luft |
| Schilder, Pfeile, Artzeichen, „GPS-Daten" | NutzerIn | Karten, Leiste, Plaketten | — |

---

## 3. Arbeitspakete

**Acht Pakete, eines nach dem anderen (K7).** Kein Fable-Schritt in der
Umsetzung (K2): Die Mockups sind vor Beginn freigegeben. Jedes Paket endet
mit Buchführung (`CLAUDE.md` 2), Statusblock hier, Push des Zweigs; die
Prüfmittel laufen zuletzt. Reihenfolge nach Abhängigkeit: die Bausteine
zuerst, weil fünf Pakete sie benutzen; die Formatänderung in der Mitte,
weil ihre Kreisläufe den Referenzbestand neu bauen; die Standortseiten
direkt danach, weil sie auf dem neuen Datenmodell sitzen; die
Verschlüsselung danach, weil sie den Referenzbestand noch einmal anfasst.

### AP1 — Vorschlagsliste und Klickprobe (E-S9-07, E-S9-08, E-S9-16; PS-2, PS-6, Nr. 68)

- `assets/vorschlagsliste.js` aus der Photon-Liste des Ortsfelds
  herausgelöst; Gruppen; Tastatur; `mousedown`.
- Ortsfeld benutzt sie (Transportziel mit Stammdatengruppe, Einsatzort und
  Abfahrtort nur Adressen); `<datalist>` aus `ui_ortsfeld()` entfernt.
- Weitere Rettungsmittel benutzen sie (PS-2 behoben).
- Besatzungsfelder des Einsatzes (Katalog `suggest_src`) benutzen sie;
  die des Diensttags folgen in AP6 mit E-S9-11.
- `tools/klickprobe/` mit den ersten drei Wegen.
- `Design.md` 9: Baustein „Vorschlagsliste" (ersetzt die Zeile zur
  Photon-Liste), `<datalist>` in der Streichliste.

**Abnahme:** Klickprobe PS-2 **3 von 3** mit 300 ms gehaltener Maus (vorher
0 von 3); `grep -c datalist server/` = **0** außerhalb von Kommentaren;
Zielklinik-Tipp „Klin" zeigt **eine** Liste mit Gruppenzeile und höchstens
zwei Stammdatentreffern (Bild bei 390 und 1280 px, beide Bedienhöhen);
Bilderlauf berührter Seiten 0/0/0/0.

### AP2 — Geocoder und Kartendialog (E-S9-05, E-S9-06; PS-1, PS-7, PS-11, Nr. 70)

- `assets/geocoder.js`; beide Photon-Adressen aus `ortsfeld.js` und
  `ortswahl.js` entfernt; Schalter und Dienstadresse aus dem Bootstrap.
- Migration `…_adresssuche_konto`; Karte „Datenschutz" auf Profil;
  **Karte „Adresssuche"** auf Betrieb → Servereinstellungen mit
  Installationsschalter (`app_state` `adresssuche`) und Feld „Dienst"
  (Nr. 137); Hinweis unter dem Ortsfeld; Absatz im Datenschutztext
  (`rechtstexte_lib.php`).
- Dialog: Suchfeld, Spur, `fitBounds`; zweite Einbauform in
  `ui_ortsfeld()`; Pin-Knopf an Transportziel (Katalog), Zielklinik- und
  Standort-Stammdaten (Konto und Admin).
- Handbuch: Ortsauswahl (ein Absatz je Einbauort), Datenschutz-Karte,
  Betriebshandbuch-Absatz zur Dienstadresse.

**Abnahme:** `grep -rn "komoot" server/assets/` = **0** (die Vorgabe steht
einmal in PHP, im Bootstrap); Klickprobe: Dialog aus fünf Einbauorten geöffnet (**5 von
5**), Treffer setzt das Kreuz ohne zu übernehmen, „Übernehmen" schreibt die
Koordinate; Spur sichtbar bei einem Einsatz mit Aufzeichnung, Karte auf der
Spur bei leerem Feld (Bild); Kontoschalter aus → im Netzwerkprotokoll
**0** Anfragen an den Geocoder bei Tippen, Kartenwahl und Dialog;
Installationsschalter aus → Kontoschalter ausgegraut (Bild); Hinweis
unter dem Ortsfeld sichtbar bei „an", weg bei „aus" (Bild);
Datenschutztext nennt die Dienstadresse (grep). Bilderlauf.

### AP3 — Karte und Zeichen (E-S9-03, E-S9-04, E-S9-12, E-S9-13; PS-3, PS-4, PS-5, PS-9, Nr. 72)

- Token und `geo.js` nach dem freigegebenen Mockup M-S9-01; Pfeile gedreht.
- `dt_art_symbol(kind, typ)` nach M-S9-02 — die Typen kommen erst in AP4,
  die Funktion nimmt den Parameter schon entgegen (Standard).
- `api/range.php` liefert `faehigkeiten`; Windenkacheln nach E-S9-04.
- Wording „GPS-Daten" in Oberfläche und Handbuch; Wortliste-Regel.

**Abnahme:** Schildmaße nachgemessen im Browser (Standort mit Doppelring
≤ 40 px statt 60); Kontrast der Ringe gegen Schnee und Karte ≥ AA
(`tools/screenshots/kontrast.py`); **sechs Pfeile auf dem Referenzeinsatz
zeigen in Spurrichtung** (Vorher/Nachher-Bild, Winkel als Zahl im
Prüfdokument); Windenkacheln bei einem Zeitraum mit Windenfähigkeit und 0
Windeneinsätzen sichtbar mit „0", bei einem ohne Fähigkeit nicht; Wortliste
0/0/0 mit der neuen Regel; Handbuch: `grep -c "Spur" docs/Handbuch.md` nennt
nur noch Verweise auf Technik.

### AP4 — Rettungsmittel: Typ, Kurzname, Standort optional (E-S9-09, E-S9-13; PS-10.1, Nr. 69)

- Migration `…_rettungsmittel_typ` mit Nachfüllen; `schema.sql` und
  Register gegengezählt.
- `validate_lib.php`: Typ, Betriebsart nach Typ, Kurzname (16), Standort
  optional; die Prüfregeln, die AP5 in den Dialogen zeigt.
- `dt_zuordnen()` friert Typ und Kurznamen ein; Leiste, Kacheln, Plaketten
  zeigen den Kurznamen; `dt_art_symbol(kind, typ)` (AP3) liefert das
  Zeichen je Typ.
- Backup Nutzlast 10, Export-Format, Import-Profile; Referenzbestand mit
  je einem Rettungsmittel je Typ, einem Kurznamen und einem ohne Standort;
  **beide Kreisläufe (R24) auf 0 unerklärt**; Demo-Fixture neu.
- Die heutigen Stammdatenformulare nehmen die neuen Felder minimal auf,
  damit der Kreislauf läuft; ihre Form kommt in AP5.

**Abnahme:** Kreisläufe `csv` und `edbak` **0 unerklärt** (Zahlen);
Register n = n; ein Rettungsmittel je Typ angelegt, zugeordnet, gesichert,
eingespielt — Typ, Kurzname und „ohne Standort" überleben den Rückweg
(Klickprobe); Leiste zeigt den Kurznamen (Bild).

### AP5 — Standortseiten (E-S9-14, E-S9-18, E-S9-19; PS-12, Nr. 44, Nr. 152)

- Menü: „Rettungsmittel" entfällt (`ui_einstellungen_punkte()`, Konto);
  „Standorte" wird die Liste (`t=standorte`), neue Seite `t=standort&s=`;
  Verwaltung → Stammdaten dieselbe Liste und Seite.
- Standortliste: Zeilen als Verweise mit Artzeichen, drei Zahlen, Stern,
  Plakette „systemweit", Winkel; „Ohne Standort" als letzter Eintrag;
  „Standort anlegen" in der Titelzeile.
- Standortseite: Rückweg, Titel, Aktionsmenü (Löschen, Als Vorbelegung);
  **Kennzahlen** als Inhaltsverzeichnis (Raster mit drei Spalten — eine
  Stylesheet-Zeile; `.aktiv` beim Sprung); vier Karten mit `id`
  (Standort mit Nur-Lage-Ortsfeld und Pin — E-S9-06 c, Nr. 70 —,
  Rettungsmittel mit Sprungliste ab sechs, Besatzung mit Filterfeld und
  Rollen als Zwischentitel, Zielkliniken mit Filterfeld); „Zum Anfang" am
  Ende jeder Karte; Unterpunkte in der Leiste entstehen aus den IDs
  (`menue.js`, unverändert).
- Drei Dialoge (Anlegen und Bearbeiten, `sd_form()` entfällt): Feldfolge
  nach E-S9-19; Fehler im Dialog; Erfolg → Redirect mit `#veh-`/`#crew-`/
  `#dest-<id>`, Zeile mit `id`, `:target` Orange-hell (Regel aus M-S9-05),
  keine zusätzliche Erfolgsmeldung; „Standort anlegen" landet auf der neuen
  Seite.
- Neue Bausteine in `Design.md` 9: Sprungziel (Pille mit Artzeichen),
  Filterfeld (Lupe im Feld, filtert im Browser), „Zum Anfang";
  Kennzahl-Raster mit drei Spalten als Variante von 9.10. Streichliste:
  `sd_form()`-Markup, zugeklappte Standortkarten.
- Artzeichen in der Rettungsmittel-Zeile (F-S9-K-04).
- Handbuch 6 (Stammdaten) neu geschrieben; Kapitel, die „Einstellungen →
  Rettungsmittel" nennen, nachgezogen (`grep -n "Rettungsmittel" docs/Handbuch.md`
  gegen die Menüstruktur).

**Abnahme:** Klickprobe: Rettungsmittel, Besatzungsmitglied und Zielklinik je
einmal angelegt → nach dem Reload steht die neue Zeile unter der Kopfleiste
(gemessene Position ≤ `--kopf` + `--abstand-4`) und trägt `:target`
(**3 von 3**); Filterfeld „Klin" → Zeilenzahl sinkt auf die Treffer (Zahl);
Sprungliste ab sechs sichtbar, bei fünf nicht (Bild); Menü ohne
„Rettungsmittel" (Vollständigkeit: Menüpunkte = Seiten); Unterpunkte der
Leiste = vier (Bild bei 1280); Bilderlauf beider Seiten in acht Breiten
und beiden Bedienhöhen; Stilvergleich gegen M-S9-05, -06, -07.

### AP6 — Tageszuordnung (E-S9-10, E-S9-11; PS-10.2, PS-10.3)

- `api/day.php?vorschau=`; `renderCrewFields()` bei `change`.
- „Anderes Rettungsmittel …" mit drei Feldern; `dt_zuordnen()` zweiter
  Weg über `validate_lib.php`; Standortfeld mit Vorschlagsliste.
- Besatzungsfelder des Diensttags über E-S9-07 (Rest von Nr. 68).
- Handbuch 4 (Diensttag zuordnen) und 6 (Rettungsmittel).

**Abnahme:** Klickprobe: Rettungsmittel wechseln → Felder ohne Speichern
sichtbar (Zahl je Rettungsmittel gleich `vehicle_roles`); Typ ohne Vorlagen
→ 0 Felder und der Satz; „Anderes Rettungsmittel" gespeichert → Tag ohne
`vehicle_id`, mit Namen, in Suche und Filter auffindbar (Klickprobe:
Filterliste enthält den Namen); Wiederherstellungsprobe und
Papierkorb-Mischfall (R27, Diensttag-Zuordnung berührt); Bilderlauf.

### AP7 — Verschlüsselung und Kennzeichnung (E-S9-01, E-S9-02; PS-8.1, PS-8.2)

- Katalogschlüssel `store => 'pat'` in `mission_fields_lib.php`; Formular
  rendert `notes` im verschlüsselten Bereich; Blob-Schlüssel `notes`.
- `api/mission.php`, `suchindex.php`, `export_data.php`, `backup_lib.php`,
  `validate_lib.php`, `import_ui.js`/`import_profiles.js`, `export.js`:
  Spalte raus, Blob rein; `hinweis` an `notes` ausgetragen.
- `api/pat_anheben.php` und die stille Anhebung beim Entsperren
  (`unlock.js`), einmal je Konto, mit Zählung.
- Schloss-Symbol an den verschlüsselten Feldern; Karte „Was hier gilt";
  **Katalogschlüssel `hinweis`** mit „Klartext — keine Patientendaten" an
  `bw_info`, `other_ema`, `crew_*` und am Diensttag-Notizfeld (Nr. 132).
- Referenzbestand: Notizen im Blob; Kreisläufe erneut.

**Abnahme:** Anhebungslauf am Referenzbestand: **n Einsätze mit Notizen
vorher, 0 Klartext nachher**, jede Notiz nach dem Entsperren wortgleich
(Vergleichsskript, Zahl); Suche findet ein Wort aus einer Notiz nach dem
Entsperren und **nicht** davor (Klickprobe); `SELECT COUNT(*) FROM missions
WHERE notes IS NOT NULL` = 0 nach der Anhebung; Kreisläufe 0 unerklärt;
Export mit `pers` enthält die Notiz, ohne `pers` nicht; Hinweis an vier
Katalogfeldern und am Diensttag-Notizfeld, **nicht** an `notes`
(Vollständigkeit zählt `hinweis`); Bilderlauf.

### AP8 — Abschluss

Handbuch gegengelesen (alle berührten Kapitel), `Technik.md` (Datenmodell,
Verzeichnisstruktur, neue Endpunkte, Runbook), `Export-Format.md`,
`Backup-Format.md`, `Design.md` (Token, Bausteine, Symbole, Streichliste),
`Lizenzen.md` (neue SVG), Backlog (**zwanzig** Punkte nach *Erledigt* —
Beschluss des Auftraggebers vom 07.09.2026; die achtzehn dieses Konzepts
**und** Nr. 132 und Nr. 137, je mit dem Vermerk „aus dem Sofortpaket
übernommen"),
Prüfdokument nach K9, Erledigt-Zeile Rahmenplan Abschnitt 8. Prüfmittel
zuletzt: Bilderlauf acht Breiten beide Bedienhöhen, Stilvergleich,
Vollständigkeit, Wortliste, Wartungsprobe, Klickprobe, Kreisläufe.

---

## 4. Prüfprotokoll-Soll

Je Paket oben; hier die Summe, die das Prüfdokument mit Zahl belegt:

| Nr. | Was | Mittel | Soll |
|---|---|---|---|
| P-01 | PS-2 mit gehaltener Maus | Klickprobe | 3/3 (vorher 0/3) |
| P-02 | keine `<datalist>` mehr | grep | 0 |
| P-03 | eine Vorschlagsliste, Gruppenzeile, ≤ 2 Stammdaten | Bild, Klickprobe | 2 Breiten × 2 Höhen |
| P-04 | Geocoder nur im Bootstrap | grep | 1 |
| P-05 | Dialog aus fünf Einbauorten | Klickprobe | 5/5 |
| P-06 | Kontoschalter aus → keine Anfrage | Netzwerkprotokoll | 0 |
| P-07 | Spur im Dialog, Karte auf der Spur | Bild | 1 Einsatz mit, 1 ohne |
| P-08 | Schildmaße | Browser-Messung | Doppelring ≤ 40 px |
| P-09 | Kontrast der Ringe | `kontrast.py` | ≥ AA je Paar |
| P-10 | Pfeile in Spurrichtung | Winkel je Pfeil | 6/6 |
| P-11 | Windenkacheln nach Fähigkeit | Klickprobe | 2 Zeiträume |
| P-12 | Wording „GPS-Daten" | Wortliste | 0/0/0 |
| P-13 | Migration Typ/Kurzname, Nachfüllen | SQL vorher/nachher | n Zeilen = n |
| P-14 | Kreisläufe csv und edbak | `kreislauf.py` | 0 unerklärt |
| P-15 | Register | Zählung | n = n |
| P-16 | Sprungliste ab sechs | Bild | 5 → nein, 6 → ja |
| P-17 | Rollen sofort | Klickprobe | Felder = Rollen |
| P-18 | Anderes Rettungsmittel such- und filterbar | Klickprobe | Name in Filterliste |
| P-19 | R27-Proben | Wiederherstellungsprobe, Mischfall | 0 Abweichungen |
| P-20 | Anhebung der Notizen | Vergleichsskript | n vorher, 0 Klartext nachher, n wortgleich |
| P-21 | Suche findet Notiz nur entsperrt | Klickprobe | 1/1 und 0/1 |
| P-22 | Export mit/ohne `pers` | Exportdatei | Spalte da / leer |
| P-23 | Bilderlauf | 8 Breiten × 2 Höhen | 0/0/0/0 |
| P-24 | Stilvergleich | `stilvergleich` | Abweichungen alle erklärt |
| P-25 | Vollständigkeit | `vollstaendigkeit` | Zahl vorher/nachher erklärt |
| P-26 | Wartungsprobe | `wartungsprobe` | 44/0 (nach Nr. 149) |
| P-27 | Was am Gerät bleibt | Prüfliste | Handy: Vorschlagsliste, Dialog, Kontoschalter; iPhone: `mousedown` |
| P-28 | Anlegen → Landung auf der neuen Zeile | Klickprobe | 3/3, Position ≤ `--kopf` + 16 px |
| P-29 | Filterfeld | Klickprobe | Zeilenzahl vor/nach dem Tippen |
| P-30 | Menü ohne „Rettungsmittel", Unterpunkte = 4 | Vollständigkeit, Bild | 0 Verweise auf `t=rettungsmittel`; 4 |
| P-31 | Handbuch 6 gegen die Menüstruktur | grep | 0 Nennungen „Einstellungen → Rettungsmittel" |
| P-32 | Hinweis am Ortsfeld nur bei aktiver Suche; Datenschutztext nennt den Dienst | Bild, grep | 2 Bilder; 1 Treffer |
| P-33 | Klartext-Hinweis an `bw_info`, `other_ema`, `crew_*`, `days.notes`; nicht an `notes` | Vollständigkeit | 4 + 1, 0 |

**Was der Prüfstand nicht kann und das Prüfdokument an den Anfang stellt:**
das echte Photon (der Prüfstand hat keinen Netzzugang; die Klickprobe
arbeitet gegen eine Attrappe), WebKit, ein Handy mit Handschuhen.

---

## 5. Gesammelte Fehlerfunde (K4)

Während des Konzepts, nicht behoben, weil sie die Arbeit nicht blockieren;
die Umsetzung nimmt sie im passenden Paket mit oder trägt sie ein:

- F-S9-K-01 — Stammdatentreffer der Zielklinik nur bei Namensgleichheit
  (`ortsfeld.js:271`): geht in E-S9-07 auf.
- F-S9-K-02 — `m.dest_name || 'Zielklinik'` als Rückfall am Schild
  (`einsatz.php:712`): E-S9-15.
- F-S9-K-03 — Die Karte „PatientIn" nennt „Ende-zu-Ende-verschlüsselt",
  der Einsatzort im Block „Einsatz" nicht (`einsatz_form.php:1169, 1199`):
  E-S9-02.
- F-S9-K-04 — Die Rettungsmittel-Zeile berechnet das Artzeichen
  (`$sym = dt_art_symbol(...)`, `einstellungen.php:1448`) und zeigt es
  nicht; die Art steht nur mittelbar in der Kleinzeile (Rollen). Mit den
  Typen aus E-S9-09 kommt das Zeichen als `zeile-vorn` in die Zeile
  (M-S9-05; gilt für Konto- und Admin-Seite, `sd_zeile()`).

---

## 6. Mockups — die Fable-Schritte dieses Konzepts

Je Darstellung eine Datei in `konzept-s9/mockups/`, gebaut mit den Token
aus `style.css`, Bilder für 390 und 1280 px; Freigabe einzeln, kein
Sammelabschluss (Regel seit S8).

| Nr. | Was | Für | Stand |
|---|---|---|---|
| M-S9-01 | Kartenschilder: Standort/Zielklinik mit Start, Ende, beidem, ohne; Einsatzort; Ringpunkt — **drei Varianten** (V1 Rand wird Ring, V2 zweigeteilt, V3 Doppelrand schmal) gegen den Ist-Stand, je in Handy- und Desktop-Karte, mit Maßleiste | E-S9-12 | **liegt vor** (`konzept-s9/mockups/M-S9-01-kartenschilder.html`, dazu `-handy`); nachgemessen Ist 36/48/60, V1 32/32/38, V2 32/32/32, V3 28/34/40 px (ohne/Start/beide); **V1 freigegeben 06.09.2026** — Token: `--geo-schild` 30 px, `--geo-kreis` 28 px, `--geo-ring` 3 px (= Randstärke), Symbol 18/16 px, 1 px Schnee außen; „beide" = blauer Rand + 1 px Schnee + 2 px Rot (38 px) |
| M-S9-02 | Artzeichen: Ist, B (eigene Strichzeichnung), C (Bildmarken einfarbig), C2 (Bildmarken und gefüllte Typzeichen); Bergwacht „mountain", Veranstaltung „ticket", Sonstiges „…"-Kreis, neutral bleibt — Leiste 260 px und Schublade 320 px, Zeichensatz bei 48/20 px | E-S9-13 | **freigegeben 06.09.2026:** Luft und Boden bleiben Ist (Tabler „helicopter", „ambulance"); Bergwacht „mountain", Veranstaltung „building-stadium", Sonstiges „dots-circle-horizontal" |
| M-S9-03 | Vorschlagsliste mit Gruppenzeile: Transportziel (zwei Zielkliniken, vier Adressen), Besatzungsfeld (drei Vorlagen), weitere Rettungsmittel (zwei Vorbelegungen, freie Eingabe), Einsatzort (nur Adressen, ohne Gruppenzeile) — 44 px am Finger, 36 px am Zeiger | E-S9-07, E-S9-08 | **liegt vor** (`konzept-s9/mockups/M-S9-03-vorschlagsliste.html`, dazu `-handy`); **freigegeben 07.09.2026** — Darstellung, höchstens zwei Stammdaten oben, bis sechs Adressen darunter |
| M-S9-04 | Kartendialog in vier Zuständen: Spur mit Ringpunkten bei leerem Feld (auf die Spur eingepasst), Suche mit Treffern über der Karte, Koordinate gesetzt (Zoom 14), Adresssuche aus (kein Suchfeld) — Desktop 560 px, Handy 358 px; Legende „Aufzeichnung · Start · Ende" | E-S9-06 | **liegt vor** (`konzept-s9/mockups/M-S9-04-kartendialog.html`, dazu `-handy`); **freigegeben 07.09.2026** — Darstellung; Regeln: Treffer setzt nur das Kreuz (F1), Karte auf der Spur nur bei leerem Feld |
| M-S9-06 | Standort zuerst (PS-12): Standortliste und Standortseite „Kempten" — Inhaltsverzeichnis mit Zahlen, vier Abschnitte, Sprungliste, Filterfeld, „Zum Anfang"; Handy und Desktop mit Leiste und S8-Unterpunkten | E-S9-18 | **freigegeben 07.09.2026** (`konzept-s9/mockups/M-S9-06-standortseiten.html`, dazu `-handy`) — Aufteilung, „Zum Anfang", Filterfeld; das Inhaltsverzeichnis gilt in der Form aus M-S9-07 (Kennzahlen statt Pillen) |
| M-S9-07 | Nachträge zu PS-12: Inhaltsverzeichnis als drei Kennzahlen; die drei Anlegen-Dialoge (Rettungsmittel Standard/Luft und Typ Veranstaltung, Besatzungsmitglied, Zielklinik); Landung auf der neuen Zeile nach dem Anlegen | E-S9-18, E-S9-19 | **freigegeben 07.09.2026** (`konzept-s9/mockups/M-S9-07-anlegen-dialoge.html`, dazu `-handy`) — Kennzahlen als Verzeichnis, Dialog statt Formular, Feldfolge, Landung per `:target` ohne Meldung |
| M-S9-05 | Sprungliste unter „Rettungsmittel" eines Standorts mit zehn Einträgen (Pille mit Artzeichen, ab sechs, Hervorhebung nach dem Sprung), Standort mit drei Einträgen ohne — Neu-Rendering von N1, 44/36 px | E-S9-14 | **liegt vor** (`konzept-s9/mockups/M-S9-05-sprungliste.html`, dazu `-handy`); **freigegeben 07.09.2026** — Pille mit Artzeichen, Schwelle sechs, Hervorhebung nach dem Sprung, Artzeichen in der Zeile (F-S9-K-04); gilt innerhalb der Standortseite (PS-12, M-S9-06) |

Alle sieben freigegeben (06./07.09.2026). Die HTML-Dateien sind die
Vorlage für die Umsetzung: Token, Klassen und Maße stehen in ihrem
`<style>`; wo Mockup und Konzepttext abweichen, gilt der Konzepttext
(Abschnitt 2), und die Abweichung wird im Prüfdokument genannt.

---

## 7. Einschub Rahmenplan (nach Freigabe des Konzepts)

**Abschnitt 3, Zeile 8:** Konzept „**liegt vor und ist freigegeben**
(Fable, 06./07.09.2026),
`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`, sieben
Mockups"; Inhalt um Nr. 44, 68, 69, 70, 72 und 152 (PS-12 Standortseiten)
ergänzt; Status „Umsetzung nach 9a".

**Abschnitt 3, Block Schritt 8, anhängen:** „**Konzept:** liegt vor;
E-S9-01 bis -19; acht Arbeitspakete (Bausteine · Geocoder und Dialog ·
Karte und Zeichen · Rettungsmittel mit Formatänderung Nutzlast 10 ·
Standortseiten · Tageszuordnung · Verschlüsselung der Notizen ·
Abschluss); der Zielkonflikt PS-8.2 ist aufgelöst — die Suche läuft im
Browser, Notizen werden verschlüsselt wie die Diagnose. **Dazu genommen**
(06.09.2026): Nr. 44, 68, 69, 70, 72; **PS-12 Standortseiten** (07.09.2026,
Nr. 152): der Menüpunkt „Rettungsmittel" entfällt, „Standorte" wird Liste
und Seite je Standort mit Kennzahlen, Dialogen und Landung auf der neuen
Zeile. Vorgabe an 9a: Nr. 137 auf Betrieb →
Servereinstellungen, Karte ‚Adresssuche', Schlüssel `adresssuche` in
`app_state`."

**Abschnitt 4:** S9-Umsetzung zu 9a bleibt; neu: „S9 zu S11 — `store =>
'pat'` ist der Schlüssel, den S11 für die Zielklinik benutzt".

**Abschnitt 5:** 44, 68, 69, 70, 72 → **S9**; **152 neu → S9**; **132 und
137 → S9** (07.09.2026, aus dem Sofortpaket).

**Abschnitt 7 — neue Programmentscheidung (Vorschlag R79):** „Geocoding
abschaltbar je Installation **und** je Konto, Dienstadresse als
Einstellung; Selbstbetrieb bleibt Hosting-Frage (R36)". Die Verschlüsselung
der Notizen ist keine R-Nummer — sie erweitert R25/Zusage 1 und steht als
E-S9-01.

**Abschnitt 10:** eine Zeile.

## 8. Einschub Backlog

**Nr. 152** (PS-12 Standortseiten, aufgenommen 07.09.2026, S9). 101–113,
147, 44, 68, 69, 70, 72: Vermerk „Konzept S9 liegt vor, E-S9-nn" am
Eintrag; nach der Umsetzung alle achtzehn nach *Erledigt*.

**Berichtigt am 07.09.2026 (Beschluss des Auftraggebers):** Auch **132 und
137** wandern nach der Umsetzung nach *Erledigt* — S9 erledigt sie
inhaltlich (E-S9-02 in AP7, E-S9-05 in AP2), nicht nur ergänzend. Damit sind
es **zwanzig** Punkte, je mit dem Vermerk „aus dem Sofortpaket übernommen".
