# Prüfdokument S9 — Einsatzbearbeitung und Rettungsmittel

Geführt nach K9, von AP1 an mitgeführt: Was ist geprüft, mit welchem Mittel
und mit welcher **Zahl**; was konnte nicht geprüft werden und warum; welche
Funde sind aufgetreten; und als Kernstück die **Prüfliste für den
Auftraggeber** — alles, was nur am Gerät geht.

Das Konzept liegt daneben
(`Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`) und trägt den Statusblock
der Umsetzung. Dieses Dokument bleibt, bis seine Prüfliste abgehakt ist
(R62).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 07.09.2026 — **AP1 gebaut und geprüft** (Web 15.6.0, Korrekturstufe **15.6.1**). AP2 bis AP8 offen |
> | Geprüft | P-01, P-02, P-03 vollständig · P-23 für die **sieben berührten** Seiten, nicht für alle 30 · P-12, P-25 als Gesamtlauf |
> | Offen | P-04 bis P-11, P-13 bis P-22, P-24, P-26 bis P-33 (AP2 bis AP8) |
> | Fehlerfunde | **fünf, alle behoben** — F-S9-P-01 bis F-S9-P-04 und **F-S9-P-07** (Abschnitt 2), dazu zwei gesammelte für AP2 (F-S9-P-05, -06); von den **drei Fragen** an den Auftraggeber (Abschnitt 4) sind zwei entschieden |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI), MariaDB 10.11.14, Chromium über Playwright; lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — 88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto, 8 Zielkliniken, 8 weitere Rettungsmittel, 15 Besatzungs-Vorbelegungen an zwei Standorten |

---

## 0. Was **nicht** geprüft werden konnte, und warum

Das steht hier oben und nicht in einer Fußnote.

**Der echte Adressdienst.** Der Prüfstand hat keinen Netzzugang zu
`photon.komoot.io` — die Egress-Sperre setzt Chromiums TLS-Handschlag zurück
(derselbe Befund wie bei den Kartenkacheln, F-P3-AC). Die Klickprobe arbeitet
gegen `tools/klickprobe/attrappe.mjs`, einen festen Katalog von zehn
erfundenen Orten mit derselben Obergrenze wie die echte Abfrage (`limit=6`).
**Was das kostet:** Ob der echte Dienst dieselben Felder liefert
(`name`, `street`, `housenumber`, `postcode`, `city`), ist damit nicht belegt
— nur, dass die Liste sie richtig verarbeitet, wenn sie kommen. Der Aufbau
der Anfrage ist unverändert geblieben (dieselbe Adresse, dieselben
Parameter), das Risiko ist also klein, aber es ist nicht null.
**Prüfliste Punkt 5.**

**WebKit.** Safari und iOS stehen in dieser Umgebung nicht zur Verfügung;
weder der Bilderlauf noch die Klickprobe können sie fahren. Das wiegt bei
diesem Paket mehr als sonst: Die Übernahme hängt an `mousedown` mit
`preventDefault()`, und iOS behandelt `mousedown` an Touch-Elementen anders
als Chromium. **Prüfliste Punkt 2.**

**Ein Finger, und erst recht ein Handschuh.** Die Klickprobe fährt eine Maus.
Sie kann die Bedienhöhe **messen** (und tut es, siehe P-03), aber nicht, ob
eine Zeile mit Handschuh zu treffen ist. **Prüfliste Punkt 1.**

**Ein Handgriff am Prüfstand, der genannt sein will.** Die Mengenbremse des
Demo-Kontos (20 Anmeldungen je Fenster, E-P1-20) ist während der Prüfläufe
angeschlagen und einmal per `DELETE FROM rate_limits` geleert worden — am
Wegwerf-Bestand, nicht an Produktivdaten. Die Ursache ist behoben (F-S9-P-04):
Die Probe meldet sich jetzt einmal je Prozess an. Der Handgriff steht hier,
weil er ein Eingriff war und keine Messung.

---

## 1. Prüfprotokoll — Soll und Ist

Die Nummern sind die des Konzepts, Abschnitt 4. Eine Zahl ohne Angabe, was sie
gemessen hat, ist keine Zahl.

| Nr. | Was | Mittel | Soll | **Ist** | Datum |
|---|---|---|---|---|---|
| P-01 | PS-2 mit gehaltener Maus | Klickprobe, 300 ms | 3/3 (vorher 0/3) | **vorher 0 von 3, nachher 3 von 3** — dieselbe Fassung der Probe gegen beide Stände, je drei Übernahmen (eine Vorbelegung des Standorts, zwei freie Eingaben); über zwei Breiten und beide Bedienhöhen **4 × 3 von 3** | 07.09.2026 |
| P-02 | keine `<datalist>` mehr | grep | 0 | **0** außerhalb von Kommentaren (`grep -rn datalist server/`; vorher 12 Treffer in sechs Dateien, davon 8 `<datalist>`-Elemente im gerenderten Markup einer Einsatzseite). Verbleibende 10 Nennungen sind sämtlich Kommentare, die die Ablösung erklären | 07.09.2026 |
| P-03 | eine Vorschlagsliste, Gruppenzeile, ≤ 2 Stammdaten, richtige Ebene | Bild, Klickprobe | 2 Breiten × 2 Höhen | **erfüllt.** Tipp „Klin" am Transportziel: **1 sichtbare Liste · 2 Gruppenzeilen · 2 Stammdatentreffer · 4 Adresstreffer · 0 `<datalist>`** (vorher: 1 Liste, 0 Gruppen, 0 erkennbare Herkunft, **8 `<datalist>`**). Bedienhöhe an der **einzeiligen** Zeile gemessen: 390 px → **44 px**, 1280 px Zeiger → **36 px**, 1280 px Finger → **44 px**. **16 Bilder** unter `tools/klickprobe/ausgabe/bild/`, Breite und Eingabeart im Dateinamen. **Ebene** (nach F-S9-P-07): in der Schnittfläche mit der klebenden Speichern-Leiste liegt **die Liste** oben — `z-index` Liste **35** · Leiste **30** · Kopfleiste **40**, gemessen mit `elementFromPoint` bei 61 bis 69 px Überlappung in allen vier Kombinationen | 07.09.2026 |
| P-04 | Geocoder nur im Bootstrap | grep | 1 | offen — AP2 |
| P-05 | Dialog aus fünf Einbauorten | Klickprobe | 5/5 | offen — AP2 |
| P-06 | Kontoschalter aus → keine Anfrage | Netzwerkprotokoll | 0 | offen — AP2 |
| P-07 | Spur im Dialog, Karte auf der Spur | Bild | 1 mit, 1 ohne | offen — AP2 |
| P-08 | Schildmaße | Browser-Messung | Doppelring ≤ 40 px | offen — AP3 |
| P-09 | Kontrast der Ringe | `kontrast.py` | ≥ AA je Paar | offen — AP3 (der Gesamtlauf steht: **21 Paare, 0 verfehlt**; die neuen Ringpaare kommen mit AP3) |
| P-10 | Pfeile in Spurrichtung | Winkel je Pfeil | 6/6 | offen — AP3 |
| P-11 | Windenkacheln nach Fähigkeit | Klickprobe | 2 Zeiträume | offen — AP3 |
| P-12 | Wording „GPS-Daten" | Wortliste | 0/0/0 | **Gesamtlauf erfüllt** (die Regel selbst kommt mit AP3): fünf Bereiche, 98 + 33 + 8 + 2 + 35 Dateien, **493 Treffer, 493 durch Ausnahmen erklärt, 0 außerhalb**, 86 Ausnahmen mit 0 ungenutzten, 0 durchgerutschte Teilstring-Fallen | 07.09.2026 |
| P-13 | Migration Typ/Kurzname | SQL vorher/nachher | n = n | offen — AP4 |
| P-14 | Kreisläufe csv und edbak | `kreislauf.py` | 0 unerklärt | offen — AP4 (AP1 fasst weder Datenmodell noch Format an) |
| P-15 | Register | Zählung | n = n | offen — AP4 |
| P-16 | Sprungliste ab sechs | Bild | 5 → nein, 6 → ja | offen — AP5 |
| P-17 | Rollen sofort | Klickprobe | Felder = Rollen | offen — AP6 |
| P-18 | Anderes Rettungsmittel such- und filterbar | Klickprobe | Name in Filterliste | offen — AP6 |
| P-19 | R27-Proben | Wiederherstellung, Mischfall | 0 Abweichungen | offen — AP6 |
| P-20 | Anhebung der Notizen | Vergleichsskript | n / 0 / n | offen — AP7 |
| P-21 | Suche findet Notiz nur entsperrt | Klickprobe | 1/1 und 0/1 | offen — AP7 |
| P-22 | Export mit/ohne `pers` | Exportdatei | Spalte da / leer | offen — AP7 |
| P-23 | Bilderlauf | 8 Breiten × 2 Höhen | 0/0/0/0 | **für die sieben berührten Seiten erfüllt** (10-tagesuebersicht, 11-…-schublade, 13-einsatzformular, 31-einstellungen-standorte, 32-einstellungen-rettungsmittel, 42-stammdaten-systemweit, 42a-stammdaten-rettungsmittel): je Lauf **56 Einzelbilder, 7 Kontaktbögen, 8 Breiten**; **Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0** — einmal als Zeigergerät (44/36) und einmal als Fingergerät (44). Gegenprobe auf gleiche Bilder: 56 Dateien, **52 verschiedene Prüfsummen**; die vier Doppelten sind die Tagesübersicht mit und ohne Schublade bei 1024, 1280, 1440 und 1920 px, wo die Schublade bauartbedingt nichts tut (`tools/screenshots/LIESMICH.md`). **Der volle Lauf über alle 30 Seiten steht mit AP8 aus.** | 07.09.2026 |
| P-24 | Stilvergleich | `stilvergleich` | Abweichungen erklärt | offen — AP8 (er ruht bis P4; siehe Abschnitt 4, Frage 3 des Konzepts dazu ist nicht offen — `CLAUDE.md` 6 lässt ihn ab P4 wieder wachen, und S9 liegt davor) |
| P-25 | Vollständigkeit | `vollstaendigkeit` | Zahl erklärt | **300 → 298 Befunde.** Der Unterschied ist vollständig erklärt: `loc-suggest`, `rmlist`, `rmopt` verlieren ihre Regel und stehen auf der Streichliste (45 → 42 mit Regel, 121 → 125 gestrichen); `rmneu` wandert von „ohne Gegenstück" (54 → 53) ebenfalls dorthin und fällt aus `ohne-regel.md` (6 → 5 als `[offen]` vermerkt). Alle übrigen Zahlen unverändert; 0 Klassen im Markup ohne eingetragenen Grund, 0 ungenutzte Einträge in den Hilfslisten | 07.09.2026 |
| P-26 | Wartungsprobe | `wartungsprobe` | 44/0 | offen — AP8 |
| P-27 | Was am Gerät bleibt | Prüfliste | — | **Abschnitt 3** |
| P-28 | Anlegen → Landung auf der neuen Zeile | Klickprobe | 3/3 | offen — AP5 |
| P-29 | Filterfeld | Klickprobe | Zeilenzahl vor/nach | offen — AP5 |
| P-30 | Menü ohne „Rettungsmittel" | Vollständigkeit, Bild | 0 / 4 | offen — AP5 |
| P-31 | Handbuch 6 gegen die Menüstruktur | grep | 0 | offen — AP5 |
| P-32 | Hinweis am Ortsfeld, Datenschutztext | Bild, grep | 2 Bilder; 1 Treffer | offen — AP2 |
| P-33 | Klartext-Hinweis an vier Feldern | Vollständigkeit | 4 + 1, 0 | offen — AP7 |

### Was AP1 zusätzlich gemessen hat

| Was | Zahl |
|---|---|
| Wege der Klickprobe, gefahren | **16** (4 Wege × 2 Breiten × 2 Eingabearten), **16 erfüllt, 0 verfehlt** |
| Bilder der Klickprobe | 16, Breite und Eingabeart im Dateinamen |
| Kontraste der Token | **21 Paare gerechnet, 0 verfehlt** (unverändert — AP1 führt kein neues Farbpaar ein) |
| Neue Token | **0.** Der Baustein kommt mit der bestehenden Skala aus; die Zeilenhöhe ist `--knopf` |
| Neue Symboldateien | **0.** Alle fünf benutzten Zeichen liegen im Vorrat: `klinik` (Zielklinik), `standort` (Tabler „map-pin", Adresse), `profil` (Besatzungsvorlage), `fahrzeug` (Vorbelegung Rettungsmittel), `plus` (freie Eingabe) |
| PHP-Syntax | `php -l` über alle berührten Dateien: **0 Fehler**; `node --check` über alle berührten Skripte: **0 Fehler** |
| Linkprobe (läuft mit) | **132 Verweise, 0 unbekannte Abweichungen**, 1 bekannt mit Nummer (Nr. 151), 0 tote Zeilen |

**Gegenproben an den Wegen, die AP1 umgebaut, aber nicht geändert hat** — sie
belegen, dass der Umbau nichts mitgenommen hat (Browserlauf, 07.09.2026, je
0 Skriptfehler):

| Weg | Ergebnis |
|---|---|
| Koordinatenpaar am Einsatzort (`47.7261, 10.3170`) | **1 Eintrag, allein in der Liste**, `data-art="koordinate"`, Text „Koordinaten übernehmen (Dezimalgrad): 47.72610, 10.31700". Übernahme leert das Textfeld und setzt den Chip; die Zustandszeile sagt „Koordinaten gesetzt — dieses Feld ist die Bezeichnung. Zum Suchen erst entfernen." |
| Plus-Code-**Kurzform** (`4HJM+7Q Kempten`) | **0 sichtbare Listen**, Zustandszeile „Plus-Code-Kurzform erkannt — bitte Vollcode eingeben …" — unverändert |
| Nur-Lage-Ortsfeld der Stammdaten (Einstellungen → Standorte, `sdbase`) | Tipp „Tal": **1 Liste, 0 Gruppenzeilen** (nur Adressen — richtig nach F9), 2 Einträge; Übernahme mit gehaltener Maus setzt **nur den Koordinatenchip** und lässt das Namensfeld unangetastet (getrennte Suche) |

---

## 2. Fehlerfunde

**F-S9-P-01 — PS-2 hat nicht nur nichts getan, es hat etwas weggenommen.**
*Gefunden im Vorher-Lauf der Klickprobe, 07.09.2026.* Das Konzept beschreibt
den Fehler als „Liste zu, nichts übernommen" (1.3). Gemessen ist er
schlimmer: Im ersten Durchgang ging die Zahl der gewählten Rettungsmittel von
**1 auf 0**. Nach dem `mousedown` versteckt der Blur-Aufschub die Liste, die
Seite rückt zusammen — und unter dem stehengebliebenen Zeiger liegt beim
`mouseup` das Kreuz eines bereits gewählten Chips, das den Klick bekommt. Der
Fehler konnte also stillschweigend eine Eingabe **löschen**, nicht nur eine
verweigern. *Behoben mit derselben Änderung* (Übernahme auf `mousedown`);
belegt durch den Nachher-Lauf, in dem alle vier bestehenden und neuen Chips
stehen bleiben.

**F-S9-P-02 — `locator.click()` findet PS-2 nicht.** *Gefunden beim Bau der
Klickprobe.* Playwrights `click()` hält die Taste rund 10 ms und läuft damit
unter dem 150-ms-Aufschub durch. Eine Probe, die so klickt, hätte „alles in
Ordnung" gemeldet. Die Probe fährt deshalb `mouse.move` → `mouse.down` →
**300 ms warten** → `mouse.up` von Hand. *Steht als Warnung im Kopf von
`probe.mjs` und in der `LIESMICH.md`* — es ist der Grund, warum es dieses
Werkzeug gibt.

**F-S9-P-03 — `fill('')` ist kein neutrales Leeren.** *Gefunden im ersten
Nachher-Lauf.* Playwright räumt ein Feld über die Tastatur; an einem
**bereits leeren** Feld kommt trotzdem ein `Delete` an. Das Chipfeld hört
genau darauf (Rücktaste im leeren Feld nimmt den letzten Chip zurück, Web
7.0.0, gewollt). Der Lauf maß daraufhin „1 → 1 Chips" und hielt die Übernahme
für gescheitert — sie hatte funktioniert, nur war vorher ein Chip
verschwunden, den das Werkzeug selbst gelöscht hatte. **Der Fehler lag im
Prüfmittel, nicht in der Anwendung.** *Behoben:* Geleert wird nur, was nicht
schon leer ist. Danach ist der Vorher-Lauf mit der **berichtigten** Probe
wiederholt worden — die 0 von 3 stammt aus diesem zweiten Lauf, nicht aus dem
ersten.

**F-S9-P-04 — Prüfläufe füllen die Mengenbremse des Demo-Kontos.** *Gefunden
beim vierten Lauf in Folge.* Vier Läufe mit je zwei Anmeldungen (`demo` und
`admin`) erschöpfen die 20 Anmeldungen je Fenster (E-P1-20); der fünfte Lauf
endete mit „Anmeldung gescheitert" — was wie ein kaputter Prüfstand aussieht
und keiner war. *Behoben:* Die Probe meldet eine Rolle erst an, wenn ein Weg
sie verlangt (AP1 braucht nur `demo`), und fährt **mehrere Breiten in einem
Prozess** — zwischen ihnen ändert sich nur die Fenstergröße, wie im
Bilderlauf. Der Fehlertext nennt die Bremse jetzt beim Namen.

**F-S9-P-07 — Die Vorschlagsliste lag hinter der Speichern-Leiste.**
*Gemeldet vom Auftraggeber am Bild, 07.09.2026 — nach der Abgabe von AP1 und
von keinem Prüfmittel gefunden.* Die Liste stand auf `z-index: 20`, dem Wert
der alten `.rmlist`, die als einzige der drei Vorgängerinnen überhaupt
schwebte; die klebende Speichern-Leiste liegt auf **30**. Gemessen:
**61 px Überlappung** bei 1280 px und **69 px** bei 390 px, und
`elementFromPoint` traf in der Schnittfläche `.speichern-innen` statt der
Liste — die untersten Trefferzeilen waren also nicht nur verdeckt, sondern
auch nicht anzutippen.

**Warum es durch alles durchgelaufen ist**, und das ist der eigentliche
Befund: Der Bilderlauf fotografiert die **Seite**, nicht die geöffnete Liste
an der ungünstigen Scrollposition; die Klickprobe fuhr die Übernahme, aber
sie fuhr sie dort, wo das Feld gerade stand. Beide meldeten Null, und beide
hatten recht — sie haben etwas anderes gemessen. Ein `z-index`, den man aus
dem Vorgänger übernimmt, ohne den Nachbarn zu prüfen, ist genau die Sorte
Fehler, die kein Werkzeug findet, das nicht danach sucht.

*Behoben mit Web 15.6.1:* Ebene **35** — über der Speichern-Leiste (30),
unter der Kopfleiste (40). Nach oben ist sie ebenso begrenzt und aus
demselben Grund: Eine Vorschlagsliste, die über die Kopfleiste malt,
verdeckt den Weg aus der Seite heraus. Die Klickprobe hat dafür den Weg
`ap1-liste-ueber-speichern-leiste` bekommen, der **beide** Richtungen misst;
er ist gegen den alten Stand gefahren worden und meldete dort „oben liegt:
speichern-innen".

### Funde, die stehen bleiben (K4 — gesammelt, nicht behoben)

**F-S9-P-05 — Die Beschriftung eines Adresstreffers steht zweimal im Code.**
`photonLabel()` in `assets/ortsfeld.js` und `label()` in `assets/ortswahl.js`
sind wortgleich. **Gehört in AP2**, wo beide Photon-Aufrufe in
`assets/geocoder.js` zusammenlaufen (E-S9-05) — dort ist es eine Zeile, hier
wäre es eine dritte Fassung.

**F-S9-P-06 — Der Schlüssel `such` wird von `ui_ortsfeld()` nicht gelesen.**
Vier Aufrufe setzen `'such' => true` (`admin_stammdaten.php:504, 724`;
`einstellungen.php:1304, 1635`); die Funktion hat den Zweig seit O5 nicht
mehr. Der Kopfkommentar von `ortsfeld.js` nannte bis AP1 ebenfalls ein
Element `<p>such`, das nirgends gesucht wird, und den tatsächlich gesuchten
Lupen-Knopf nicht. **Kein Schaden, aber eine Lüge in der Dokumentation.**
AP2 fasst dieselben Aufrufe an (zweite Einbauform, E-S9-06 c) und räumt es
dort mit.

---

## 3. Prüfliste für den Auftraggeber

Was nur am Gerät geht. Je Punkt: der Bedienweg, das erwartete Ergebnis, und
**woran ein Scheitern zu erkennen ist**.

- [ ] **1 — Die Liste mit Handschuhen treffen (Handy).**
  *Weg:* Einsatz öffnen → Karte „Transport" → in **Transportziel** „Klin"
  tippen. Mit Handschuh nacheinander einen Zielklinik- und einen
  Adresstreffer antippen.
  *Erwartet:* Jeder Tipp trifft die gemeinte Zeile; ein Zielklinik-Treffer
  setzt Name **und** Koordinatenchip, ein Adresstreffer nur den Chip.
  *Scheitern erkennbar an:* Der Tipp landet auf der Nachbarzeile, oder die
  Liste schließt ohne Übernahme. Beides heißt: Die Zeile ist zu niedrig oder
  zu dicht — gemessen sind 51 px bei zwei Textzeilen und 44 px bei einer,
  aber gemessen ist nicht getroffen.

- [ ] **2 — `mousedown` auf dem iPhone (Safari/WebKit).**
  *Weg:* Dasselbe am iPhone, dazu die Liste **Weitere Rettungsmittel**:
  „RT" tippen, einen Vorschlag antippen; danach denselben Vorschlag mit
  **gedrücktem und gehaltenem** Finger (rund eine Sekunde) wählen.
  *Erwartet:* In beiden Fällen entsteht ein Chip.
  *Scheitern erkennbar an:* Die Liste schließt und es entsteht kein Chip —
  dann behandelt WebKit `mousedown` an dieser Stelle anders als Chromium,
  und der Baustein braucht zusätzlich `pointerdown`. **Das ist der Punkt mit
  dem größten Restrisiko dieses Pakets**, weil der Prüfstand kein WebKit
  fahren kann.

- [ ] **3 — Die Besatzungsfelder auf dem Handy (der eigentliche Anlass von
  Nr. 68).**
  *Weg:* Einsatz öffnen → Karte **„Abweichende Besatzung"** aufklappen →
  Haken setzen → in ein Rollenfeld die ersten zwei Buchstaben einer
  hinterlegten Person tippen. Dasselbe in der Tagesübersicht unter
  „Diensttag-Daten bearbeiten".
  *Erwartet:* Unter dem Feld erscheint eine Liste mit der Überschrift
  „Vorlagen des Standorts" und den passenden Namen; ein Tipp setzt den Namen
  ins Feld, und die Speichern-Leiste erscheint.
  *Scheitern erkennbar an:* Es erscheint nichts (dann ist der Baustein nicht
  geladen — Konsole prüfen) oder der Name landet nicht im Feld.

- [ ] **4 — Tastaturbedienung am Schreibtisch.**
  *Weg:* Am Transportziel „Klin" tippen, dann **Pfeil ab** mehrmals,
  **Enter**; danach erneut tippen und **Esc**.
  *Erwartet:* Die Markierung (Rauch mit orangem Strich links) wandert durch
  **alle** Zeilen und springt am Ende wieder nach oben; Enter übernimmt die
  markierte; Esc schließt die Liste, ohne etwas zu übernehmen und ohne das
  Formular abzuschicken.
  *Scheitern erkennbar an:* Enter schickt das Formular ab (dann greift das
  `preventDefault` nicht), oder die Gruppenzeilen werden mit markiert (sie
  sind keine Einträge).

- [ ] **5 — Ein echter Adresstreffer.**
  *Weg:* Am Einsatzort einen realen Ort tippen (drei Zeichen genügen).
  *Erwartet:* Bis zu sechs Adressen mit Pin-Symbol, Hauptzeile
  (Name/Straße) und gedämpfter Zeile (PLZ Ort); ein Treffer setzt Feld und
  Koordinatenchip.
  *Scheitern erkennbar an:* Die Liste bleibt leer, obwohl die Netzverbindung
  steht (dann liefert der echte Dienst andere Feldnamen als die Attrappe),
  oder die zweite Zeile bleibt leer (dann fehlen `postcode`/`city`).

- [ ] **6 — Zwei Listen dürfen es nie wieder sein.**
  *Weg:* Am Transportziel tippen und **genau hinsehen**, ob über dem Feld
  eine zweite, vom Browser gezeichnete Liste erscheint.
  *Erwartet:* Nur die eine Liste unter dem Feld.
  *Scheitern erkennbar an:* Eine zweite Liste über dem Feld — dann ist
  irgendwo ein `list=`-Attribut oder ein `<datalist>` zurückgekommen
  (`grep -rn datalist server/` muss 0 außerhalb von Kommentaren melden).

---

## 4. Fragen an den Auftraggeber

Drei Stellen, an denen Konzept und Auftrag einander widersprechen oder das
Konzept schweigt. Alle drei sind für AP1 entschieden **und** revidierbar; sie
kosten nichts, wenn sie anders entschieden werden.

**Frage 1 — Die Besatzungsfelder des Diensttags: AP1 oder AP6?**
**Entschieden vom Auftraggeber am 07.09.2026: AP1.** Der gebaute Stand bleibt,
es ändert sich keine Zeile.

Das Konzept sagt in AP1 beides: „die des Diensttags folgen in AP6" (Aufzählung)
und „`grep -c datalist server/` = **0**" (Abnahme). Beides zugleich geht
nicht — die Liste des Diensttags war eine der acht `<datalist>`.
*Entschieden für AP1:* Die Liste in `renderCrewFields()` ist auf den Baustein
umgestellt; **der Weg dorthin bleibt unverändert** — die Felder entstehen
weiterhin erst nach dem Speichern. Was AP6 laut E-S9-11 zu tun hat
(`api/day.php?vorschau=`, Felder sofort bei `change`), ist damit
**unangetastet**. *Preis der Gegenentscheidung:* Bliebe die `<datalist>` bis
AP6 stehen, meldete P-02 nach AP1 nicht 0, sondern 1 — und die Abnahme des
Pakets wäre nicht erfüllt.

**Frage 2 — „`<datalist>` in der Streichliste" (Konzept, AP1).**
**Erläuterung nachgereicht 07.09.2026; Rückmeldung steht aus.** Der gebaute
Stand ist der unten beschriebene und läuft grün; eine Gegenentscheidung wäre
eine Zeile in einer Hilfsliste.

Die Streichliste (`tools/vollstaendigkeit/streichliste.md`) führt **Klassen
des alten Stylesheets**; `datalist` ist ein HTML-Element, stand nie in
`vorher-klassen.txt` und ist nie eine Klasse gewesen. Ein Eintrag dort wäre
eine tote Zeile, und die Prüfung meldet tote Zeilen.
*Entschieden:* Auf die Streichliste kommen die **vier Klassen**, die
tatsächlich verschwinden — `loc-suggest`, `rmlist`, `rmopt`, `rmneu` —, jede
mit Grund und Paket; `rmneu` fällt zugleich aus `ohne-regel.md`, wo es als
`[offen]` stand. Der Ausbau der `<datalist>` ist statt dessen mit einer Zeile
in `Design.md` 9.0 („nimm X, nicht Y") und im Baustein 9.28 festgehalten.

**Frage 3 — Achtzehn oder zwanzig Backlog-Punkte im Abschluss?**
**Entschieden vom Auftraggeber am 07.09.2026: zwanzig.** In AP8 wandern damit
auch **Nr. 132 und Nr. 137** nach *Erledigt*, je mit dem Vermerk „aus dem
Sofortpaket übernommen (E-S9-02 bzw. E-S9-05)". Das Konzept sagt in AP8 und
Abschnitt 8 „achtzehn"; diese Entscheidung geht vor.

Das Konzept sagt in AP8 und Abschnitt 8 „achtzehn Punkte nach *Erledigt*, 132
und 137 nur mit Vermerk"; der Auftrag sagt „alle zwanzig Punkte nach
*Erledigt*". Da 132 und 137 seit dem 07.09.2026 **inhaltlich** von S9 erledigt
werden (E-S9-02 und E-S9-05, Pakete AP7 und AP2), spricht mehr für zwanzig.
*Vorschlag:* zwanzig, mit dem Vermerk „aus dem Sofortpaket übernommen" am
Eintrag. **Fällig erst in AP8** — bis dahin ist nichts zu tun.

---

## 5. Abweichungen von den Mockups

Wo Mockup und Umsetzung auseinandergehen, steht es hier (Konzept, Abschnitt 6:
„die Abweichung wird im Prüfdokument genannt").

| Stelle | Mockup M-S9-03 | Umsetzung | Grund |
|---|---|---|---|
| Hervorhebung des getippten Teils | `<b>` im Bild; die Anmerkung nennt daneben `EdSuchtext.hervor` als „dieselbe Hervorhebung" | `<b>` | Die beiden Angaben des Mockups widersprechen einander: `EdSuchtext.hervor` erzeugt `<mark class="treffer">` mit oranger Fläche. Die Fläche trägt in der **Suche** eine Aussage („hier steht dein Wort in einem langen Text"); in einer Vorschlagsliste steht das Wort am Anfang, und die Fläche käme unter die Zeilenmarkierung zu liegen. Gefolgt ist dem **Bild**, das freigegeben wurde |
| Dichte Stufe | `.dicht .vorschlag{padding-top:2px;padding-bottom:2px}` | keine eigene Regel | `2px` ist kein Token, und die Skala ist geschlossen (`Design.md` 5). Gemessen ändert die Regel nichts am Ergebnis: Eine zweizeilige Zeile ist mit **und** ohne sie höher als beide Bedienhöhen (51 px), eine einzeilige folgt `--knopf` von selbst (44/36, gemessen). Die Regel hätte eine Zahl eingeführt, die nichts bewirkt |
| Leerer Zustand | `.vorschlaege-leer` („keine Treffer") | nicht gebaut | Der Baustein versteckt die Liste, wenn sie leer ist — so verhielten sich beide Vorgänger, und das Konzept verlangt nichts anderes. Eine Zeile „keine Treffer" wäre eine neue Darstellung ohne Freigabe |
| Behälter des Besatzungsfeldes | `<div class="loc-widget">` mit Label daneben | `<label class="feld-vorschlag">` wie bisher | Das Mockup benutzt `.loc-widget` nur, um `position:relative` zu bekommen; `.loc-widget` trägt daneben die Abstände des Ortsfelds. Die Feldstruktur des Formulars bleibt damit unberührt — eine Umstellung auf `ui_feld()` wäre eine Änderung ohne Auftrag |
