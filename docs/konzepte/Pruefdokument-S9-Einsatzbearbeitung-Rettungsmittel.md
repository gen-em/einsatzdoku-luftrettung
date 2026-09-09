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
> | Stand | 08.09.2026 — **AP1 bis AP4a gebaut und geprüft** (Web 15.7.0, Korrekturstufe 15.7.1, 15.8.0, 15.9.0, 16.0.0, 16.1.0, 16.1.1), **AP5 in Arbeit**: Teil 1 (Menü und Standortliste, Web 16.2.0/16.2.1), Teil 2 (die Standortseite, 16.2.2) und Teil 3 (die neuen Bausteine, **16.3.0**) stehen, Teile 4 bis 6 offen. AP6 bis AP8 offen. Die Mockups M-S9-08 bis -10 sind am 08.09.2026 freigegeben, ihre Umsetzung steckt in AP4a — bis auf Frage 6 (Standort löschen), die in AP5 Teil 5 gehört |
> | Geprüft | P-01 bis P-15 und P-32 vollständig · P-23 je Paket für die berührten Seiten (AP1 sieben, AP2 drei dazu, AP3 sechs dazu, AP4 elf), nie als Gesamtlauf über alle · P-25 als Gesamtlauf. Dazu **E-S9-13**, das im Konzept keine Abnahme hatte — siehe F-S9-P-12 |
> | Offen | P-17 bis P-22, P-26, P-28 bis P-31, P-33 (AP5 Teile 4 bis 6, AP6 bis AP8). **P-16 (Sprungliste ab sechs) ist mit Teil 3 erfüllt** — nicht als Bild, wie das Konzept es verlangte, sondern als Klickprobe, die den Fall herstellt und zurückstellt; die Begründung steht in Abschnitt 1. **P-24 (Stilvergleich) ist für AP4a und für AP5 Teile 1+2 und 3 erfüllt** — jedes Mal gegen den Stand vor dem Paket |
> | Fragen | **zwölf gestellt, zwölf entschieden — keine offen.** Am 07.09.2026: Diensttags-Besatzung → AP1 · zwanzig Backlog-Punkte im Abschluss · Pfeile im Kartendialog → **nein** · Überschrift im Dialogkopf → **lassen** · Umfang von E-S9-03 → „alles Sichtbare“ · `veranstaltung.svg` → **Tabler „ticket“**. Am **08.09.2026** nach den Mockups M-S9-08 bis -10: Kachel → **Ist lassen** · Plakettenzeile → **nein** · Leiste unter 1200 px → **Variante 2** · Zusammenführen → **(c)** · Standort löschen → **(b)**; dazu **Frage 2** aus AP1 (die Streichliste bleibt, wie AP1 sie gebaut hat) und **Frage 7** aus AP5 Teil 2: Die Standortseite behält ihre **sechs** Karten (fünf ohne luftgebundenes Rettungsmittel), und die Abnahmezahl „Unterpunkte der Leiste = vier“ ist auf sechs bzw. fünf **berichtigt** — in Konzept AP5, in der Abnahme und in Backlog Nr. 152 |
> | Fehlerfunde | **achtunddreißig, alle behoben** — F-S9-P-01 bis -04, -07 bis -14 (Abschnitt 2; -05 und -06 mit AP2 abgeräumt) und **F-S9-U-01 bis -26** (Konzept, Abschnitt 5). **Zehn davon aus AP5**, und keiner war im Browser zu sehen: U-17 „Zum Anfang" sprang auf eine Kennung, die es nicht gibt (0 von 6 Knöpfen mit Ziel) · U-18 nach jedem Speichern landete man auf der Standortliste statt auf der Seite · U-19 die Karte „Ohne Standort" erschien auf **jeder** Standortseite und machte aus sechs Unterpunkten sieben · U-20 die Buchführung von Teil 1 stand an drei Stellen still · U-21 der Stilvergleich zeigte **8 von 22** abweichenden Elementen und sagte nicht, dass die Liste unvollständig ist · U-22 `Design.md` 9.0 verbot dem Wortlaut nach, was AP5 mit Mockup baut · U-23 der Streichlisten-Auftrag hatte keinen Gegenstand, **und seine Begründung im Konzept war falsch** (eine tote Streichlistenzeile ist still, nicht gemeldet) · U-24 jede Karte trug ihren Titel **zweimal** · U-25 die Sprungziel-Pille wäre aus der Bedienhöhenmessung des Bilderlaufs gefallen · U-26 zwei Fehler in `Design.md`, gefunden beim Gegenlesen |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI), MariaDB 10.11.14, Chromium über Playwright; lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — 88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto, 6 Rettungsmittel in vier Typen (seit AP4; vorher 3), 8 Zielkliniken, 8 weitere Rettungsmittel, 15 Besatzungs-Vorbelegungen an zwei Standorten |

---

## 0. Was **nicht** geprüft werden konnte, und warum

Das steht hier oben und nicht in einer Fußnote.

> *Bis Teil 3 stand hier: „Die Verwaltungsseite ist von AP5 nicht berührt —
> nichts daran ist umgebaut, und nichts daran ist geprüft." **Mit Teil 4
> (Web 17.0.0) ist sie umgebaut**: dieselbe Liste, dieselbe Standortseite,
> dieselben sechs Karten, dieselben fünf Dialoge. Der doppelte Kartentitel
> ist dort ebenfalls entfallen. Was weiterhin fehlt, steht im nächsten
> Absatz.*

**Die Standortseite der Verwaltung ist nicht fotografiert.** Der
Referenzbestand hat **keinen einzigen systemweiten Standort** (`bases` mit
`user_id IS NULL` ist leer), und ohne einen gibt es die Seite nicht: Der
Platzhalter `__ADMIN_STANDORT__` des Bilderlaufs bleibt unauflösbar, der Lauf
meldet für `42a-stammdaten-standortseite` ausdrücklich **„OHNE BILD"**. Das
ist keine Verschlechterung, nur eine lautere Fassung desselben Zustands: Der
alte Eintrag `42a-stammdaten-rettungsmittel` zeigte einen **leeren** Reiter
und lieferte trotzdem acht Bilder mit der Meldung „kein Überlauf" — acht
Bilder von nichts. Was die Seite deckt, ist die Klickprobe
(`ap5-verwaltung-besatzung-anlegen`): Sie legt einen systemweiten Standort
samt Rettungsmittel an, misst sechs Karten, fünf Dialoge und die Landung auf
`#crew-<id>`, macht ein Bild und räumt alles wieder ab. **Was fehlt, ist der
Blick auf die volle Seite in acht Breiten.** Behebung: ein systemweiter
Standort im Generator des Referenzbestands — das berührt Fixture, Prüfsummen
und beide Kreisläufe und gehört in ein eigenes Paket (**Backlog Nr. 166**).
**Prüfliste Punkt 31.**


**Die Sprungliste ist nur mit sechs Marken gesehen worden.** Der
Referenzbestand hat drei Rettungsmittel am größten Standort; die sechste
Marke ist für den Prüflauf über das Formular angelegt und danach wieder
gelöscht worden. Wie eine Reihe aus zwölf oder zwanzig Marken bei 360 px
umbricht, ist **gerechnet und nicht gesehen** — der Bilderlauf fotografiert
denselben Standort mit drei Rettungsmitteln und zeigt dort gar keine Liste.
**Prüfliste Punkt 30.**

**`:target` misst kein Stilvergleich.** Der Zustand steht in keiner der vier
Proben, und die Pseudoprobe ersetzt ihn nicht (sie kennt `:hover`, `:focus`,
`:active`, `:disabled` — kein `:target`). Belegt ist die Hervorhebung allein
durch die Klickprobe, die sie im Browser anklickt und die Fläche misst
(**rgb(255, 235, 214)**). Wer die Regel ändert, hat kein zweites Netz.

**Ein Produktivbestand mit gewachsener Historie (AP4).** Die Migration
`2026_09_07_rettungsmittel_typ` ist gegen den Referenzbestand gefahren — 16
Diensttage, 6 Rettungsmittel, 2 Standorte, ein Konto. Eine Installation mit
mehreren Konten, Hunderten Diensttagen und Rettungsmitteln, die zwischendurch
gelöscht wurden, hat sie nicht gesehen. Zwei Stellen sind dort anders:
`UPDATE days d JOIN vehicles v …` läuft über alle Zeilen (auf dem Prüfstand
16), und `nb_moeglich()` fragt vier Tabellen ab, deren Nullbarkeit auf einer
Installation stehen kann, die A12 nie zu Ende gebracht hat.
**Prüfliste Punkt 11 und 12.**

**Eine Installation, die A12 nie abgeschlossen hat.** Auf dem Prüfstand
trugen alle vier Tabellen der zweiten Stufe schon NOT NULL — der Zweig „die
Nachbearbeitung existiert noch" ist damit **nicht** gefahren worden, nur der
Zweig „sie ist erledigt". Gemessen ist, dass `nb_moeglich()` nach der
Migration wieder `false` liefert (vorher: `true`, mit zwei falschen offenen
Punkten) und dass ein Standard-Rettungsmittel ohne Standort weiterhin gefunden
wird (0 → 1 → 0 mit Gegenprobe). Nicht gemessen ist, wie sich die Seite auf
einer Installation verhält, die noch offene Einträge in den anderen vier
Tabellen führt. **Prüfliste Punkt 12.**

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

**Das Fenster zwischen Deploy und `update.php`** (neu mit AP2). Beide Leser
der Kontospalte fangen eine fehlende Spalte ab und liefern die Vorgabe „an" —
belegt ist das nur durch **Lesen des Codes**, nicht durch Messen: Der
Prüfstand hat die Migration angewendet, und einen Bestand ohne die Spalte
wieder herzustellen hieße, die Migration zurückzunehmen. **Was das kostet:**
Ob eine Seite mit Ortsfeld in diesem Fenster wirklich fehlerfrei antwortet,
ist nicht gemessen. **Prüfliste Punkt 8.**

**Ein eigener Photon-Dienst.** Das Feld „Dienst" nimmt jede `https://`-Adresse
mit Rechnernamen an; ob ein selbst betriebener Photon dieselben Felder liefert
und die Herkunft der Anwendung erlaubt (CORS), ist hier nicht prüfbar — es gibt
keinen. **Prüfliste Punkt 9.**

**Die Kartenzeichen gegen die echte Karte.** `kontrast.py` rechnet Token
gegen Token — gegen Kartengrün, Waldbraun oder ein Luftbild kann es nichts
messen, und genau dort steht der Ring. Dafür ist die 1-px-Schneelinie da, und
die ist mit AP3 von 3 auf 1 px **dünner** geworden. **Prüfliste Punkt 11.**

**Ein Finger auf einem 14-px-Ring.** Die Antippfläche ist auf 24 px gebracht
und gemessen; ob das am Handschuh reicht, sagt keine Zahl. **Prüfliste
Punkt 12.**

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
| P-04 | Geocoder nur im Bootstrap | grep | 1 | **erfüllt.** `grep -rn "komoot" server/assets/` = **0** (vorher 2: `ortsfeld.js` für die Suche, `ortswahl.js` für die Umkehrsuche). Die Vorgabe steht **einmal**, in `server/geocoder_lib.php` (`GEOCODER_VORGABE`); `assets/geocoder.js` trägt **keine** Rückfalladresse — fehlt der Bootstrap, ist `an()` falsch und es geht nichts hinaus | 07.09.2026 |
| P-05 | Dialog aus fünf Einbauorten | Klickprobe | 5/5 | **5 von 5** mit Leaflet-Karte darin — Einsatzort, manueller Abfahrtort (Einsatz **ohne** Aufzeichnung, sonst gibt es das Feld nicht), Transportziel, Standort im Konto, Standort systemweit (Rolle `admin`). Dazu: Ein Treffer im Suchfeld lässt das Formular unberührt — **Feld leer, 0 Chips**, der Name steht im **Suchfeld**; erst „Übernehmen" schreibt: **1 Chip** (F1). Über zwei Breiten und beide Bedienhöhen **4 × 5 von 5** | 07.09.2026 |
| P-06 | Kontoschalter aus → keine Anfrage | Netzwerkprotokoll | 0 | **0 Anfragen** an `photon.komoot.io` bei Tippen, Kartenwahl und „Übernehmen" — dazu **0 Adressvorschläge, 0 Suchfelder im Dialog, 0 Hinweiszeilen**. **Mit Gegenprobe:** derselbe Weg bei eingeschaltetem Schalter ergibt **2 Anfragen** (Vorwärtssuche und Umkehrsuche) und 1 Suchfeld — ohne diese Gegenprobe wäre die Null der Beleg dafür, dass die Probe nicht hinsieht. Geschaltet wird über die **Formulare**, nicht per SQL | 07.09.2026 |
| P-07 | Spur im Dialog, Karte auf der Spur | Bild | 1 mit, 1 ohne | **erfüllt.** Einsatz mit Aufzeichnung (309 Punkte): **1 Linie, 4 Ringpunkte** (2 auf der Karte, 2 in der Legende), **Legende sichtbar, 0 Pfeile**; bei leerem Ortsfeld steht die Karte auf der Spur (Bild `ap2-spur-im-dialog`). Ohne Aufzeichnung — die Standort-Stammdaten — bleibt die Legende versteckt und die Karte auf dem Rückfallpunkt; belegt im Weg `ap2-dialog-fuenf-einbauorte`, der beide Standortseiten fährt | 07.09.2026 |
| P-08 | Schildmaße | Browser-Messung | Doppelring ≤ 40 px | **erfüllt, alle sieben Maße getroffen.** Nachgemessen im Browser (Klickprobe `ap3-schildmasse`, Außenmaß **einschließlich** der Schattenringe): **ohne 32 · Start 32 · Ende 32 · beide 38 · Einsatzort 28 · Ringpunkt 14 · Ring beide 20 px** — vorher 36/48/48/60/32/16/28. Der Doppelring liegt damit bei **38 px** statt 60, die Forderung war ≤ 40. Symbol im Schild **18**, im Kreis **16** px. Dazu die **Antippfläche des Ringpunkts: 24 px** — siehe F-S9-P-13 | 07.09.2026 |
| P-09 | Kontrast der Ringe | `kontrast.py` | ≥ AA je Paar | **erfüllt — mit einer Berichtigung der Schwelle.** Gesamtlauf **21 Paare, 0 verfehlt**; die Ringfarben sind unverändert (`--blau` auf `--schnee` **3,77:1**, `--rot` auf `--schnee` **4,68:1**). Anmerkung 8 des Mockups verlangt „≥ 4,5:1“ — das ist die Schwelle für **Text**. Für ein grafisches Zeichen gilt WCAG 1.4.11 mit **3:1**, und beide bestehen sie. Wer 4,5 als Kriterium abhakt, meldet einen Fehlschlag, den es nicht gibt. **Gegen die Karte** kann `kontrast.py` nichts rechnen — dafür ist die 1-px-Trennlinie da, und die ist von 3 auf 1 px dünner geworden. **Prüfliste Punkt 11** | 07.09.2026 |
| P-10 | Pfeile in Spurrichtung | Winkel je Pfeil | 6/6 | **erfüllt, gemessen an der Bildschirmmatrix.** **12 von 12** Pfeilen in 30-Grad-Schritten treffen ihren Sollwinkel auf **0,1 Grad** genau (0/30/60/…/330), dazu **2 von 2** auf der Spur des Referenzeinsatzes. **Vorher:** Matrix `a=0,833 b=0 c=0 d=0,833` bei behaupteten 90 und 135 Grad — reine Skalierung, kein Drehanteil. **Die Sollzahl „6“ ist nicht reproduzierbar** und deshalb durch 12 ersetzt: Die Pfeilzahl folgt `floor(Spurlänge_px / 140)` und hängt damit an der Fensterbreite; bei 1280 × 900 sind es 2. Siehe F-S9-P-14 | 07.09.2026 |
| P-11 | Windenkacheln nach Fähigkeit | Klickprobe | 2 Zeiträume | **erfüllt, drei Fälle statt zwei.** Januar 2026 mit Windeneinsatz: **2 Kacheln**. Januar **ohne** Windeneinsatz, Fähigkeit steht: **2 Kacheln mit „0 Winden-Cycles“ und „0,0 Ø Winden-Cycles / Flugtag“** — genau der Fall, den PS-4 verlangt. November 2026 ohne Fähigkeit: **0 Kacheln**. Der mittlere Fall ist **über die Oberfläche hergestellt** (Windenhaken am Einsatz herausgenommen, im `finally` zurückgesetzt — nachgezählt: 1 Windeneinsatz im Januar, wie vorher), nicht per SQL und nicht im Referenzbestand. `faehigkeiten` aus der API: `{"winch":true,"bergwacht":true}` | 07.09.2026 |
| P-12 | Wording „GPS-Daten" | Wortliste | 0/0/0 | **erfüllt, jetzt mit der Regel.** Die Sperrliste trägt seit AP3 das Muster `spur` (**großgeschrieben** — es soll das deutsche Substantiv treffen, nicht den Bezeichner; kleingeschrieben ergab beim ersten Versuch **472** Treffer, von denen keine zwanzig eine Anzeige waren). Dazu **sechs begründete Ausnahmen**: `spur_lib.php`, „Spurteil“ (Name im Sicherungsarchiv), die Beschriftungen vergangener Migrationen, der GPX-Fachbegriff, die fünf Fachdokumente und — **befristet, Klasse D** — die fünf Android-Texte, die Schritt 9a übernimmt. Ergebnis: **0 Treffer außerhalb der Ausnahmen, 96 Regeln, 96 gegriffen, 0 ungenutzt, 0 durchgerutschte Fallen**. Umbenannt: **72 sichtbare Zeichenketten in 18 Dateien**; `grep -c "Spur" docs/Handbuch.md` **41 → 0**. Der frühere Gesamtlauf: fünf Bereiche, 98 + 33 + 8 + 2 + 35 Dateien, **493 Treffer, 493 durch Ausnahmen erklärt, 0 außerhalb**, 86 Ausnahmen mit 0 ungenutzten, 0 durchgerutschte Teilstring-Fallen | 07.09.2026 |
| P-13 | Migration Typ/Kurzname | SQL vorher/nachher | n = n | **erfüllt.** Frische Installation aus `schema.sql` und migrierte Datenbank in `vehicles` und `days` **strukturgleich** (`SHOW CREATE TABLE`, Unterschied nur das entfernte `AUTO_INCREMENT`); Nachfüllung **16 von 16** Diensttagen mit `vehicle_typ` — alle `standard`, `vehicle_kurz` **16 × NULL**, weil kein Dienst des Referenzbestands ein Rettungsmittel mit Kurznamen fährt; den Bergwacht-Fall mit Kurznamen belegt der Klickprobe-Weg `ap4-zuordnen-friert-ein` | 07.09.2026 |
| P-14 | Kreisläufe csv und edbak | `kreislauf.py` | 0 unerklärt | **erfüllt — edbak 287 771 · csv 9 118 · edbak-alt 287 781 Einzelvergleiche, je 0 unerklärt** (16 / 1 021 / 653 erwartet, 0 ungenutzte Regeln; `--frisch`, dreimal). Dazu Aufwärtskompatibilität: Eine Nutzlast-9-Datei spielt **4 von 6** Rettungsmitteln als `standard` ein und überspringt die **2** ohne Standort, die es in einer echten 9er-Datei nicht geben konnte | 07.09.2026 |
| P-15 | Register | Zählung | n = n | **45 = 45** — Katalog in `migration_lib.php` gegen die `skipped`-Einträge am Ende von `schema.sql` | 07.09.2026 |
| P-16 | Sprungliste ab sechs | ~~Bild~~ **Klickprobe** | 5 → nein, 6 → ja | **erfüllt** (AP5-3): bei 5 keine Liste, bei 6 eine mit 6 Pillen / 6 Artzeichen / 6 gültigen Zielen, 36 px hoch. Das Mittel ist ein anderes als geplant — am Referenzbestand (3 Rettungsmittel je Standort) zeigt ein Bild die Schwelle nicht |
| P-17 | Rollen sofort | Klickprobe | Felder = Rollen | offen — AP6 |
| P-18 | Anderes Rettungsmittel such- und filterbar | Klickprobe | Name in Filterliste | offen — AP6 |
| P-19 | R27-Proben | Wiederherstellung, Mischfall | 0 Abweichungen | offen — AP6 |
| P-20 | Anhebung der Notizen | Vergleichsskript | n / 0 / n | offen — AP7 |
| P-21 | Suche findet Notiz nur entsperrt | Klickprobe | 1/1 und 0/1 | offen — AP7 |
| P-22 | Export mit/ohne `pers` | Exportdatei | Spalte da / leer | offen — AP7 |
| P-23 | Bilderlauf | 8 Breiten × 2 Höhen | 0/0/0/0 | **für die sechzehn berührten Seiten erfüllt.** *AP1 (sieben):* 10, 11, 13, 31, 32, 42, 42a. *AP2 (drei dazu):* 30, 43a, 48. *AP3 (sechs dazu):* 12-einsatzansicht, 14-zeitraum, 14a-zeitraum-monat, 21a-tag-spuren, 35-import-export, 44-demo-konto, 47-betrieb-jobs — dazu erneut 10 und 11, weil die Kartenzeichen dort stehen. AP3-Lauf über **zehn Seiten × 8 Breiten = 80 Einzelbilder, 10 Kontaktbögen**, **beide Läufe gefahren und beide 0/0/0**: Zeigergerät (44/36 px) und Fingergerät (44 px), je **Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0**. Im Zeigerlauf ist die Sitzung einmal neu aufgebaut worden (Demo-Reset alle 30 Minuten, das Werkzeug fängt ihn ab und meldet ihn — kein Fehlschlag). **Der volle Lauf über alle 30 Seiten steht mit AP8 aus.** | 07.09.2026 |
| P-24 | Stilvergleich | `stilvergleich` | Abweichungen erklärt | **für AP4a erfüllt.** Kaskade **712 → 715 Regeln: 0 entfallen, 3 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**; berechnete Stile **47 710 Elementmessungen** (drei Proben × dreizehn Breiten), **40 Abweichungen** — alle bei **1024 und 1100 px**, also den einzigen gemessenen Breiten im geänderten Band, und alle auf die zwei neuen Regeln zurückführbar (Einrückung 12 → 8 px und `.eintrag-neben.kurz` von `none` auf `block`, dazu die Folgemaße). Die Pseudoprobe gegen die umgeschriebenen Stylesheets zeigt dasselbe Muster (19 253 Messungen, 30 Abweichungen). Für AP1 bis AP4 steht er weiter aus — dort wurde er mit dem Hinweis übersprungen, er „ruhe bis P4". Das trägt nicht: `CLAUDE.md` 6 lässt ihn ab P4 wieder wachen, und P3 ist abgeschlossen; AP1 bis AP3 haben `style.css` allerdings nur ergänzt, nicht umgebaut, weshalb der Nachlauf in AP8 gehört und nicht eilt | 08.09.2026 |
| P-25 | Vollständigkeit | `vollstaendigkeit` | Zahl erklärt | **300 → 298 → 304 Befunde**, jede Bewegung erklärt. *AP1 (300 → 298):* `loc-suggest`, `rmlist`, `rmopt` verlieren ihre Regel und stehen auf der Streichliste (45 → 42 mit Regel, 121 → 125 gestrichen); `rmneu` wandert von „ohne Gegenstück" (54 → 53) ebenfalls dorthin und fällt aus `ohne-regel.md` (6 → 5 als `[offen]`). *AP2 (298 → 304):* **+6 Unicode-Pfeile** (227 → 233) — durchweg das `→` in deutschen Sätzen, die einen Menüweg nennen („Betrieb → Servereinstellungen", „Einstellungen → Profil"), dieselbe Redeweise wie die 227 vorhandenen. **+1 dann 0** Klassen ohne eingetragenen Grund: `loc-datenschutz` ist ein **Anker ohne Gestaltung** (die Kleinzeile trägt `.feld-klein`) und steht mit Begründung in `ohne-regel.md` als `[bleibt]`. Alle übrigen Zahlen unverändert — 0 Hexfarben außerhalb `:root`, 0 Schriftgrößen außerhalb der Skala, 0 Pixelmaße außerhalb der Token, 0 Knopfhöhen ohne `--knopf`, 0 ungenutzte Einträge in den Hilfslisten | 07.09.2026 |
| P-26 | Wartungsprobe | `wartungsprobe` | 44/0 | offen — AP8 |
| P-27 | Was am Gerät bleibt | Prüfliste | — | **Abschnitt 3** |
| P-28 | Anlegen → Landung auf der neuen Zeile | Klickprobe | 3/3 | offen — AP5 |
| P-29 | Filterfeld | Klickprobe | Zeilenzahl vor/nach | offen — AP5 |
| P-30 | Menü ohne „Rettungsmittel" | Vollständigkeit, Bild | 0 / 4 | offen — AP5 |
| P-31 | Handbuch 6 gegen die Menüstruktur | grep | 0 | offen — AP5 |
| P-32 | Hinweis am Ortsfeld, Datenschutztext | Bild, grep | 2 Bilder; 1 Treffer | **erfüllt.** Hinweis bei „an": **1 Zeile bei 3 Ortsfeldern** einer Einsatzseite, Text nennt `photon.komoot.io` (Bild `ap2-hinweis-am-ortsfeld`); bei „aus": **0 Zeilen** (Bild `ap2-kontoschalter-aus`). Installationsschalter aus → Kontoschalter **gesperrt**, Grund genannt, Plakette „Adresssuche aus" (Bild `ap2-installation-graut-konto-aus`). Datenschutztext: `grep -n geocoder_host server/admin_installation.php` = **2 Treffer** (Fließtext und Textbaustein) — **Abweichung vom Konzept**, siehe Abschnitt 5 | 07.09.2026 |
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

### Was AP2 zusätzlich gemessen hat

| Was | Zahl |
|---|---|
| Wege der Klickprobe, gefahren | **40** (10 Wege × 2 Breiten × 2 Eingabearten, AP1 und AP2 zusammen), **40 erfüllt, 0 verfehlt** |
| Bilder der Klickprobe | **36**, Breite und Eingabeart im Dateinamen |
| Der Dienstname im ausgelieferten Browserstand | `grep -rn "komoot" server/assets/` = **0** (vorher **2**). `grep -rn "GEOCODER_VORGABE" server/` = **3** — Definition, Formularhinweis und Prüffunktion, alle in PHP |
| Der Schlüssel `'such'`, den `ui_ortsfeld()` nie las | `grep -rn "'such'" server/` = **0** (vorher 4, F-S9-P-06) |
| Migration | `2026_09_07_adresssuche_konto` auf dem Prüfstand angewendet: **„Erfolgreich angewendet"**, Register **44 = 44** (Katalog gegen `schema_migrations`). Der Sprungpunkt für `schema.sql` steht als `('2026_09_07_adresssuche_konto', 'skipped')` |
| Beide Leser ohne die Spalte | `geocoder_konto_an()` und `geocoder_state()` fangen `Throwable` und liefern die Vorgabe. **Nicht am laufenden System gemessen** — der Prüfstand hat die Spalte; belegt ist nur der Codepfad durch Lesen. Prüfliste Punkt 8 |
| Kontraste der Token | **21 Paare gerechnet, 0 verfehlt** (unverändert — AP2 führt kein neues Farbpaar ein; `.legende-linie` benutzt `--spur-1`, die Ringpunkte die Farben aus M-S9-01) |
| Neue Token | **0.** Legende, Suchfeld und der ausgegraute Schalter kommen mit der bestehenden Skala aus |
| Neue Symboldateien | **0.** Das Suchfeld im Dialog benutzt `lupe`, dieselbe Zeichnung wie am Ortsfeld |
| PHP-Syntax | `php -l` über alle berührten Dateien: **0 Fehler**; `node --check` über alle berührten Skripte: **0 Fehler** |
| Linkprobe (läuft mit) | **134 Verweise, 0 unbekannte Abweichungen**, 1 bekannt mit Nummer (Nr. 151), 0 tote Zeilen. Die zwei neuen sind die Formularziele der beiden Schalter |
| Wortliste | fünf Bereiche, **99 + 34 + 8 + 2 + 35 = 178 Dateien**, **0 Treffer außerhalb der Ausnahmen in 0 Zeilen**, 86 Ausnahmen mit **0 ungenutzten**, **0** durchgerutschte Teilstring-Fallen. Die beiden neuen Dateien (`geocoder_lib.php`, `assets/geocoder.js`) sind in den Bereichen a und b mitgezählt |

### Was AP3 zusätzlich gemessen hat

| Was | Zahl |
|---|---|
| Wege der Klickprobe, gefahren | **6 von 6** erfüllt (AP3 allein); mit AP1 und AP2 zusammen **16 Wege** |
| Sichtbare Zeichenketten umbenannt | **72** in **18 Dateien**; `grep -c "Spur" docs/Handbuch.md` **41 → 0** |
| Nicht umbenannt, mit Begründung | **18 Zeilen** in vier Gruppen: `spur_lib.php` (10, Code), „Spurteil" (4, Name im Sicherungsarchiv), Migrationsbeschriftungen (2, Geschichte), der GPX-Fachbegriff (1) — dazu ein Zitat des alten Wortlauts im Handbuch |
| Wortliste | **0 Treffer außerhalb der Ausnahmen** in fünf Bereichen, **96 Regeln, 96 gegriffen, 0 ungenutzt, 0 durchgerutschte Fallen**. Das Muster ist **großgeschrieben**; kleingeschrieben ergab **472** Treffer, davon keine zwanzig eine Anzeige |
| Symbolvorrat | **49 → 52 Dateien**; „davon im Code verwendet" bleibt **24**, der Hinweis „Symboldatei ohne Verweis" steigt **25 → 28**. Das ist **kein Rückschritt**: Die Artzeichen kommen als Variable (`$sym['symbol']`) durch, und die Prüfung erkennt nur wörtliche Namen — `hubschrauber`, `fahrzeug` und `ohne-zuordnung` stehen aus demselben Grund schon vorher darin. Hinweise zählen nicht in `BEFUNDE` |
| Vollständigkeit | **304 = 304**, unverändert trotz drei neuer Dateien und zweier neuer Token. Keine neue Klasse ohne Regel, keine Pixelzahl außerhalb der Token |
| Neue Token | **zwei, beide abgeleitet:** `--geo-ringpunkt` = `--abstand-4` − `--strich-stark` (14 px), `--geo-symbol` = `--symbol` − `--strich-stark` (18 px). Die Rechnung steht im Stylesheet und **ist** die Herkunft; 14 und 18 stehen auf keiner Skala des Projekts, und die Skala ist geschlossen |
| Erzeugte Tabellen in `docs/Design.md` | alle vier neu erzeugt (`tools/design/tabellen.py`), nicht nachgetippt: Token **177 Zeilen**, Schwellen 12, Symbole 59, Bausteine 43 |
| Kontraste | **21 Paare, 0 verfehlt** — unverändert, AP3 führt kein neues Farbpaar ein |
| Linkprobe | **134 Verweise, 0 unbekannte Abweichungen**, 1 bekannt mit Nummer, 0 tote Zeilen |
| PHP- und JS-Syntax | `php -l` über alle berührten Dateien **0 Fehler**; `node --check` über alle berührten Skripte **0 Fehler** |

**Was der CSS-Weg für Nr. 72 nebenbei vermieden hat.** Das Konzept lässt zwei
Wege zu: `display` am `<span>` **oder** die Drehung in das SVG. Der zweite
hätte `edSymbol()` einen Winkelparameter gegeben — und `pfeil-hoch.svg` ist
zugleich die **Sortierrichtung von sieben Tabellenköpfen**, sechs davon über
`.symbol-oben` gedreht. Das wäre eine zweite Drehmechanik neben der
vorhandenen Klassendrehung gewesen, an genau dem Erzeuger, der beide baut.
Der CSS-Weg lässt sie unberührt.

**Gegenprobe zur Null bei P-06.** Eine Zahl, die auch dann null wäre, wenn die
Probe gar nicht hinsähe, belegt nichts. Der Weg
`ap2-kontoschalter-aus-keine-anfrage` fährt deshalb **denselben** Weg zweimal
— erst mit eingeschaltetem Schalter (**2 Anfragen**, 2 Vorschläge, 1
Suchfeld), dann mit ausgeschaltetem (**0 / 0 / 0**) — und stellt den Schalter
im `finally` zurück. Gezählt wird gegen den Rechnernamen aus
`window.GEO_DIENST`, nicht gegen ein Wortmuster: Ein `/geocod/`-Muster zählte
die eigene Datei `assets/geocoder.js` mit und meldete „1 Anfrage" für etwas
aus dem eigenen Haus.

**Gegenproben an den Wegen, die AP1 umgebaut, aber nicht geändert hat** — sie
belegen, dass der Umbau nichts mitgenommen hat (Browserlauf, 07.09.2026, je
0 Skriptfehler):

| Weg | Ergebnis |
|---|---|
| Koordinatenpaar am Einsatzort (`47.7261, 10.3170`) | **1 Eintrag, allein in der Liste**, `data-art="koordinate"`, Text „Koordinaten übernehmen (Dezimalgrad): 47.72610, 10.31700". Übernahme leert das Textfeld und setzt den Chip; die Zustandszeile sagt „Koordinaten gesetzt — dieses Feld ist die Bezeichnung. Zum Suchen erst entfernen." |
| Plus-Code-**Kurzform** (`4HJM+7Q Kempten`) | **0 sichtbare Listen**, Zustandszeile „Plus-Code-Kurzform erkannt — bitte Vollcode eingeben …" — unverändert |
| Nur-Lage-Ortsfeld der Stammdaten (Einstellungen → Standorte, `sdbase`) | Tipp „Tal": **1 Liste, 0 Gruppenzeilen** (nur Adressen — richtig nach F9), 2 Einträge; Übernahme mit gehaltener Maus setzt **nur den Koordinatenchip** und lässt das Namensfeld unangetastet (getrennte Suche) |

---

### AP4 — Rettungsmittel: Typ, Kurzname, Standort optional

| Soll (Konzept, AP4) | Ist | Mittel |
|---|---|---|
| Migration mit Nachfüllen; `schema.sql` und Register gegengezählt | Register **45 = 45**; frische Installation aus `schema.sql` und migrierte Datenbank in `vehicles` und `days` **strukturgleich** (`SHOW CREATE TABLE`, Unterschied nur das entfernte `AUTO_INCREMENT`); Nachfüllung **16 von 16** Diensttagen | `php update.php`, `mysql`, eigenes Zählskript |
| `validate_lib.php`: Typ, Betriebsart nach Typ, Kurzname (16), Standort optional | **8 von 8** Fällen wie festgelegt: Rollen bei Standard gefiltert (`driver` bei Luft verworfen), Rollen bei Bergwacht **ganz** verworfen, Betriebsart bei Veranstaltung von `air` auf `ground` gezwungen (mit Meldung in der Prüfliste), Winde damit weggefallen, Kurzname 20 → 16 Zeichen gekappt, Standard ohne Standort abgelehnt, unbekannter Typ abgelehnt, fehlender Name abgelehnt | eigenes Prüfskript gegen `pruef_rettungsmittel()` |
| Alle drei Schreibwege über die eine Prüfung | Konto (`einstellungen.php`), Verwaltung (`admin_stammdaten.php`) und Sicherung (`backup_lib.php`) rufen `pruef_rettungsmittel()`; `grep` auf `INSERT INTO vehicles`/`UPDATE vehicles`/`INSERT IGNORE INTO vehicles` findet **keinen vierten** außerhalb von `migration_lib.php` | `grep`, Lesen |
| `dt_zuordnen()` friert Typ und Kurznamen ein | Tag #1 nach der Zuordnung: `vehicle_typ='bergwacht'`, `vehicle_kurz='BW Hoch'`, `base_id=NULL` — und die Leiste zeigt „BW Hoch" mit Tooltip „Bergwacht Hochkreuth — Bergwacht, luftgebunden" | eigenes Skript, Klickprobe `ap4-zuordnen-friert-ein` |
| Backup Nutzlast 10, Export, Import, Kreisläufe **0 unerklärt** | **edbak 287 771 · csv 9 118 · edbak-alt 287 781 Einzelvergleiche, je 0 unerklärt** (16 / 1 021 / 653 erwartet, **0 ungenutzte Regeln**) | `vergleich/kreislauf.py --frisch`, dreimal |
| Aufwärtskompatibilität | Eine Nutzlast-9-Datei (Felder entfernt) spielt **4 von 6** Rettungsmitteln als `typ='standard'` ein und überspringt **2** — die beiden ohne Standort, die es in einer echten 9er-Datei nicht geben konnte | eigenes Prüfskript gegen `edbak_restore()` |
| Referenzbestand mit je einem Rettungsmittel je Typ, einem Kurznamen, einem ohne Standort | **3 → 6 Rettungsmittel**, alle über das Formular angelegt: Bergwacht (Luft, Standort, „BW Hoch", 0 Rollen, 2 Fähigkeiten), Veranstaltung (auf Boden gezwungen, ohne Standort, 0 Fähigkeiten), Sonstiges (Boden, ohne Standort, „Reserve") | `einspielen.py --stufen stammdaten` |
| Demo-Fixture neu | Neu erzeugt, **55 861 Spurpunkte** unverändert, `web_version` 16.0.0; Demo-Zurücksetzen **42 Stammdaten, 0 übersprungen** (vorher wären die zwei ohne Standort übersprungen worden) | `fixture/erzeugen.php`, `demo_zuruecksetzen()` |
| Leiste zeigt den Kurznamen (Bild) | **15 Einträge, 15 mit Nebentext, 0** bei denen der Nebentext länger ist als der Tooltip; Bild `ap4-kurzname-in-der-leiste` | Klickprobe |
| Klickprobe | **6 von 6** Wegen erfüllt, **0 Rückstände** im Bestand nach dem Lauf | `tools/klickprobe/` |
| Wortliste | **0 Treffer, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 96 Regeln, 96 gegriffen — über fünf Bereiche mit 99 + 34 + 8 + 2 + 35 Dateien | `tools/wortliste/` |
| Vollständigkeit | **304 = 304.** Ein Zwischenstand stand auf 305; der eine Mehrbefund war ein Auslassungszeichen, das ich selbst in einen Kommentar von `version.php` gesetzt hatte — gegen den Stand von AP3 im eigenen Arbeitsbaum verglichen und ersetzt | `tools/vollstaendigkeit/`, `git worktree` |
| Kontraste | **21 Paare gerechnet, 0 verfehlt** | `tools/screenshots/kontrast.py` |
| Linkprobe | **140 Verweise, 0 unbekannte Abweichungen**, 1 bekannte mit Nummer (Nr. 151) — vorher 134 Verweise | `tools/linkprobe/` |
| Bilderlauf | **11 berührte Seiten, 88 Einzelbilder je Lauf, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** — je einmal als Zeigergerät (44/36 px) und als Fingergerät (44 px) | `tools/screenshots/aufnehmen.mjs` |

**Zwei Zahlen, die dieser Lauf NICHT belegt** — und das steht hier, weil eine
grüne Zahl sagen muss, was sie gemessen hat:

- Am **Diensttag** belegen die Kreisläufe nur `vehicle_typ = 'standard'`
  (16 von 16) und `vehicle_kurz = NULL` (16 von 16): Alle 16 Dienste des
  Referenzbestands fahren ein Standard-Rettungsmittel ohne Kurznamen. Dass ein
  Bergwacht-Tag mit Kurznamen den Weg ebenfalls übersteht, belegt der
  Klickprobe-Weg `ap4-zuordnen-friert-ein` — nicht der Kreislauf.
- Der **Bilderlauf** hat 11 der 46 Seiten fotografiert, nämlich die berührten.
  Die übrigen 35 sind unverändert geblieben und nicht neu aufgenommen worden.


### AP4a — Kurzname im schmalen Band, Typ beim Zusammenführen

| Soll (Konzept, AP4a) | Ist | Mittel |
|---|---|---|
| `.eintrag-neben.kurz` bleibt unter 1200 px sichtbar, sobald ein Kurzname gesetzt ist | **Erfüllt für das Band 1024–1199 px** — und nur dort ist etwas zu tun: Unter 1024 px stand der Nebentext schon immer (F-S9-U-09). Gemessen über neun Breiten mit einem gesetzten Kurznamen: 390/800/1023 px **15 von 15** sichtbar · 1024/1100/1199 px **1 von 15**, nämlich der Kurzname · 1200/1280/1920 px 15 von 15 | eigenes Messskript gegen die laufende Anwendung |
| Das Akkordeon rückt dort je Ebene 8 statt 12 px ein | **8 px im Band, 12 px darunter und darüber**, an `.leiste-liste .akkordeon-inhalt` gemessen. Seit Web **16.1.1** rückt das Akkordeon je Ebene **4 px** ein, dazu **4 px** Abstand in der Zeile (Freigabe M-S9-11, Weg 2). Dem Nebentext stehen dadurch **64 bis 79 px** zur Verfügung — mit der ersten Fassung (Einrückung 8 px) waren es 48 bis 63, ohne jede Regel stand er gar nicht. „BW Hoch" braucht **55**, „NEF 76/1" **53**: beide stehen an **jedem** Datum ganz. Gemessen **13 Kurznamen, 0 Ellipsen** (vorher 10). Leistenfuß, Schubladen-Hauptpunkte und Einstellungsmenü bleiben bei 8 px — der Abstand ist mit `:not(.leiste-gruppe)` eingegrenzt, nachgemessen bei 1100 und 1280 px. Die Schwankung kommt vom Datum daneben — es ist **76 bis 83 px** breit, weil Bricolage Grotesque Ziffern **proportional** setzt und `.eintrag-text` nicht unter der `tabular-nums`-Regel steht; und es schrumpft nicht (`flex:1 0 auto`). **Eine erste Messung meldete 57 px und war an einer einzigen, zufällig schmalen Datumsangabe genommen** — gefunden von der adversarischen Gegenprobe (F-S9-U-13). Das Einstellungsmenü bleibt in **jeder** Breite bei **0 px** — `.leiste-gruppe > .akkordeon-inhalt` ist spezifischer | eigenes Messskript, Klickprobe |
| Zeile „Typ" in „Der Diensttag danach" | **Vorhanden**, Plakette blau wie „Art". Sie nennt **beide** Typen („Standard oder Bergwacht") samt Kleinzeile, solange zwei Rettungsmittel zur Wahl stehen — die einzelne Plakette des Mockups wäre falsch, sobald jemand das andere wählt (F-S9-U-11) | Klickprobe `ap4a-typ-beim-zusammenfuehren`, Bild |
| Typ und Kurzname als Zusatz der Wahlzeilen | **4 Wahlzeilen**, davon **1** mit Typ und **1** mit Kurznamen („Bergwacht · BW Hoch · 28.03.2026 20:00, wird aufgenommen"). **0 Überlauf** bei 1280, 700 und 390 px; Zeilenhöhen 39 / 44 / 71–92 px | Klickprobe, eigenes Messskript |
| `dt_merge_pruefen()` bleibt unverändert | **Unverändert** — `git diff` zeigt an der Funktion keine Zeile; zwei Diensttage verschiedenen Typs sind im Browser zusammenführbar (der Weg fährt genau diesen Fall) | `git diff`, Klickprobe |
| Klickprobe | **10 von 10** Wegen erfüllt — zwei Wege × fünf Breiten (390, **1024, 1100, 1199**, 1280), je als Zeiger- und als Fingergerät; der Sollwert der Einrückung im Band steht seit Web 16.1.1 auf **4 px**. **0 Rückstände**: 16 Diensttage, 88 Einsätze, 6 Rettungsmittel wie vorher, **0** Tage mit Bergwacht-Typ, Kurznamen oder ohne Rettungsmittel. Der Weg liest seit F-S9-U-16 nach jedem Zuordnen nach und scheitert, wenn der Bestand nicht steht; Gegenprobe des gehärteten Rahmens: die zwölf Wege aus **AP4 laufen weiter 12 von 12** | `tools/klickprobe/`, neue Datei `wege/ap4a.mjs` |
| Stilvergleich | **Zwei Läufe.** AP4a gegen Web 16.0.0: Kaskade **0 entfallen, 3 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**, berechnete Stile **47 710 Elementmessungen, 40 Abweichungen**. Web 16.1.1 gegen 16.1.0: Kaskade **0 entfallen, 2 neu, 1 anderer Endwert** (die Einrückung von 8 auf 4 px), **0 Reihenfolgeumkehrungen**; berechnete Stile **47 749 Elementmessungen, 14 Abweichungen**. Beide Male liegen **alle** Abweichungen bei 1024 und 1100 px — den einzigen gemessenen Breiten im Band — und lassen sich auf die genannten Regeln zurückführen. Im Katalog ändert **genau ein** `.eintrag` seinen Abstand, nämlich der im Akkordeon: die Eingrenzung `:not(.leiste-gruppe)` greift | `tools/stilvergleich/` |
| Bilderlauf | **18 berührte Seiten** (die sechzehn mit Leiste, dazu zwei Einstellungsseiten wegen der Abstandsregel), **144 Einzelbilder + 18 Kontaktbögen je Lauf, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** — je einmal als Zeiger- (44/36 px) und als Fingergerät (44 px). Gegenprobe: **162 Dateien, 158 verschiedene Prüfsummen**; die vier Doppelten sind Seite 10 gegen Seite 11 ab 1024 px, wo es die Schublade nicht gibt | `tools/screenshots/aufnehmen.mjs` |
| Wortliste | **0 Treffer, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 96 Regeln, 96 gegriffen, über fünf Bereiche | `tools/wortliste/` |
| Vollständigkeit | **304 = 304** — die neue Klasse `kurz` erzeugt keinen Befund, weil sie eine Regel hat; 8 px ist `--abstand-2`, also kein Literal außerhalb `:root` | `tools/vollstaendigkeit/` |
| Kontraste, Linkprobe | **21 Paare, 0 verfehlt** · **140 Verweise, 0 unbekannte Abweichungen** | `kontrast.py`, `tools/linkprobe/` |
| `Design.md` erzeugt | Neu erzeugt; **21 Medienblöcke über 5 Breiten unverändert** — beide Regeln liegen in vorhandenen Blöcken, es gibt **keine fünfte Schwelle** | `tools/design/tabellen.py alle` |

**Was dieser Lauf NICHT belegt** — und das steht hier, weil eine grüne Zahl
sagen muss, was sie gemessen hat:

- **Der Bilderlauf sagt zum Kurznamen nichts.** Seine einzige Breite im
  geänderten Band ist **1024 px**, und kein Diensttag des Demo-Bestands trägt
  einen Kurznamen. „0 Überlauf" heißt dort: Die Leiste läuft nicht über —
  nicht, dass ein Kurzname sichtbar wäre. Das belegt allein die Klickprobe,
  die sich den Zustand selbst herstellt.
- **1199 px steht in keinem Prüfmittel außer der Klickprobe.** Der Bilderlauf
  kennt 1024 und 1280, der Stilvergleich 1024 und 1100. Die obere Kante des
  Bandes ist deshalb eigens in den Klickprobe-Lauf aufgenommen worden.
- **Ein Kurzname mit 16 Zeichen ellipsiert weiterhin**, im Band wie am
  Schreibtisch: „Sanitätsdienst S" braucht 95 px, verfügbar sind 55 (Band)
  bzw. 89 (260-px-Leiste). Das ist unverändertes Verhalten und keine Folge
  dieses Pakets.
- **Am mehrfachen Tag bleibt der Kurzname auch ab 1200 px eine Ellipse**
  (gemessen 35 px von 55). Das ist Bestandsverhalten seit Web 16.0.0 und
  wurde hier nur gemessen, nicht behoben — es zu beheben hieße
  `.eintrag-text` schrumpfen zu lassen, und dann ellipsierte das Datum.
  **Prüfliste Punkt 25.**

### AP5 — Standortseiten, Teile 1 und 2 (Web 16.2.0 / 16.2.1 / 16.2.2)

**Teil 3 bis 6 stehen noch aus** (Sprungziel und Filterfeld, die drei Dialoge
und die Verwaltungsseite, Standort löschen Variante b, Buchführung). Was hier
steht, ist der Stand nach Teil 2 — nicht die Abnahme von AP5.

| Soll (Konzept, AP5) | Ist | Mittel |
|---|---|---|
| Menü ohne „Rettungsmittel"; „Standorte" ist die Liste, `t=standort&s=<id>` die Seite | **Erfüllt.** Liste **3 Karten mit `id` / 3 Unterpunkte** in der Leiste (`standorte`, `zentrale`, `sd-ohne`), Standortseite **6 / 6** (`standort`, `rettungsmittel`, `besatzung`, `zielkliniken`, `weitere`, `bergwacht`). Zwei Weichen: `t=stammdaten` und `t=rettungsmittel` führen auf die Liste, eine unbekannte Kennung ebenso — geprüft mit `dt_base_erlaubt()` **vor** der ersten Zeile Ausgabe | Browser, eigenes Messskript |
| Unterpunkte der Leiste entstehen aus den Karten-`id` (`menue.js`, unverändert) | **Erfüllt, und der Zähler hat einen Fehler gefunden:** Vor der Berichtigung standen **sieben** Unterpunkte bei sechs Karten — die Karte „Ohne Standort" hing unter jeder Standortseite statt auf der Liste (F-S9-U-19). `menue.js` ist unverändert | eigenes Messskript |
| „Zum Anfang" am Ende jeder Karte | **6 von 6**, und **6 von 6 mit einem Ziel, das auf der Seite steht**. Vorher **0 von 6**: Das Ziel war `#seitenanfang`, eine Kennung, die es in der Anwendung nicht gibt (F-S9-U-17). Jetzt `#inhalt`, die Kennung des `<main>` | eigenes Messskript (`getElementById` je Knopfziel) |
| Nach dem Speichern zurück an die Stelle, an der getippt wurde | **4 von 4 Rundläufen** auf der Standortseite: Zielklinik anlegen → `t=standort&s=29#sd-29-td`, Besatzung anlegen → `#sd-29-crew`, beide löschen → dieselben Anker. Vorher ging jeder dieser Wege auf die **Liste** — die Umleitung setzte den abgeschafften Reiternamen zusammen, und die Weiche fing ihn ab (F-S9-U-18). Bestand nach dem Rundlauf **sauber** (0 Probeeinträge) | Browser, eigenes Messskript |
| Karte „Ohne Standort" | **2 Einträge** (Referenzbestand), auf der **Liste**. „Bearbeiten" führt auf die Seite des ersten Standorts, wo das Formular steht — der Haken ist darin gesetzt; AP5-4 löst das mit einem Dialog auf | Klickprobe `ap4-ohne-standort-sichtbar` |
| Klickprobe (Gegenprobe: die vorhandenen Wege überstehen den Umbau) | **24 von 24** Wegen erfüllt, 0 verfehlt (AP1 bis AP4a, 1280 px, Zeigergerät). **0 Rückstände**: 21 Rettungsmittel, 61 Diensttage, 346 Einsätze wie vorher. Die AP4-Wege lesen jetzt **alle** Standortseiten und die Liste — mit nur der ersten Seite zählten sie 5 statt 6 und meldeten den Referenzbestand als verkleinert. **Ein Lauf davor meldete 9 von 48** und war kein Befund an der Anwendung: Das Demo-Konto hatte sich mitten im Lauf zurückgesetzt (Standortkennungen 29/30 → 31/32) | `tools/klickprobe/`, `wege/ap4.mjs` überarbeitet |
| Linkprobe | **128 Verweise, 0 unbekannte Abweichungen**, 1 bekannte mit Nummer (Nr. 151). Zwischenstand **126** — die zwölf ausgeschriebenen `t=rettungsmittel`-Adressen sind zu `sd_seite()` geworden, und eine zusammengesetzte Adresse sieht die Probe nicht. Die Adresse steht darin jetzt **als ganze Zeichenkette**; damit prüft die Probe wieder, dass `einstellungen.php` `t` **und** `s` liest | `tools/linkprobe/` |
| Stilvergleich, Kaskade (gegen den Stand vor AP5) | Regeln **717 → 725**; **0 entfallen, 14 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**. Die 14 sind die zwölf Regeln der verweisenden Zeile aus Teil 1 (`a.zeile`, `:hover`, `:focus-visible`, `.zeile-weiter`) und die zwei aus Teil 2 (`.nach-oben`, `.kennzahl-raster-3`) | `tools/stilvergleich/kaskade.py` |
| Stilvergleich, berechnete Stile | **47 840 Elementmessungen, 338 Abweichungen** über 13 Fensterbreiten: `seiten.html` **5 Elemente je Breite**, `katalog.html` **21**, `js_markup.html` **0**. Dazu die Pseudoprobe: **19 526 Messungen, 286 Abweichungen**, 22 Elemente je Breite. **Alle erklärt** und in zwei Gruppen: (a) die neuen Regeln selbst — `a.zeile` verliert das Linkblau (`rgb(31,78,156)` → `rgb(26,5,0)`), `a.zeile:hover` bekommt Rauch, `.zeile-haupt` darin Blau-tief, `.zeile-weiter` Sand und `flex:0 0 auto`, `.nach-oben` 16 px Rand und rechtsbündig (die zwei `<a>` darin erben es), `.kennzahl-raster-3` drei Spalten statt zwei (mobil) bzw. vier (Schreibtisch); (b) **Folgen der Dokumenthöhe** — `html`, `body` und zwei Behälter werden um **genau 16 px** höher, und sieben absolut gesetzte Elemente weiter unten im selben langen Katalogdokument verschieben sich um denselben Betrag. Keine Abweichung außerhalb dieser beiden Gruppen | `tools/stilvergleich/` |
| Bilderlauf | **3 berührte Seiten** (Standortliste, Standortseite, Geräte), **24 Einzelbilder + 3 Kontaktbögen je Lauf, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** — je einmal als Zeigergerät (44/36 px) und als Fingergerät (44 px). Gegenprobe gegen den Fund F-P3-AQ („248 Bilder, 176 davon die Anmeldeseite"): **24 Dateien, 24 verschiedene Prüfsummen**, und die Bilder bei 1280 px von Hand angesehen — die Liste zeigt drei Karten und die Zeilen mit Winkel und drei Zahlen, die Standortseite Rückweg, drei Kennzahlen, sechs Karten und sechsmal „Zum Anfang" | `tools/screenshots/aufnehmen.mjs` |
| Wortliste, Vollständigkeit, Kontraste | Wortliste **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 96 Regeln, 96 gegriffen, über fünf Bereiche; Vollständigkeit **304 = 304**; Kontraste **21 Paare, 0 verfehlt** | `tools/wortliste/`, `tools/vollstaendigkeit/`, `tools/screenshots/kontrast.py` |
| `docs/Design.md` neu erzeugt | Bausteinvorrat **36 → 37 Funktionen** — `ui_nach_oben()` fehlte seit Teil 1; `.zeile` **12 → 15** Unterklassen; acht Token- und Symbolzahlen um eins bis sieben berichtigt (F-S9-U-20). Der Prosa-Eintrag zu „Zum Anfang" in Kapitel 9 kommt mit Teil 3, wie im Konzept vorgesehen | `tools/design/tabellen.py alle` |

### AP5 Teil 3 — die neuen Bausteine (Web 16.3.0)

| Soll (Konzept, AP5) | Ist | Mittel |
|---|---|---|
| Sprungliste ab sechs sichtbar, bei fünf nicht | **Erfüllt, und beide Richtungen gemessen:** bei **5** Rettungsmitteln keine Liste, bei **6** eine mit **6 Pillen, 6 Artzeichen, 6 gültigen Zielen**, Pillenhöhe **36 px** am Zeigergerät bei 1280 px. **Das Konzept verlangt dafür ein Bild — das geht am Referenzbestand nicht:** Der größte Standort hat drei Rettungsmittel, und der Bilderlauf nimmt genau ihn; ein Bild könnte nur zeigen, dass keine Liste da ist, und das sähe auch dann so aus, wenn die Schwelle bei zwanzig läge. Der Weg stellt den Fall deshalb über das Formular her und räumt ihn in `finally` wieder ab; Bestand danach unverändert | Klickprobe `ap5-sprungliste-ab-sechs`, zwei Bilder |
| Die angesprungene Zeile ist hervorgehoben (`:target`, M-S9-05) | **Erfüllt.** Klick auf die erste Pille → Adresse `#veh-108`, Zeile gefunden, Fläche **rgb(255, 235, 214)** (= `--orange-hell`), Oberkante **72 px** bei einer Kopfleiste von **56 px** — sie sitzt also darunter, und zwar **ohne** eigenes `scroll-margin-top`; `scroll-padding-top` an `html` leistet es allein (die zweite Angabe war einmal gebaut und addierte sich auf 140 px). **`:target` misst kein Stilvergleich**: Der Zustand steht in keiner der vier Proben, und die Pseudoprobe ersetzt ihn nicht. Diese Zahl ist der einzige Beleg | Klickprobe `ap5-sprung-faerbt-die-zeile`, Bild |
| Filterfeld: Zeilenzahl sinkt auf die Treffer | **Erfüllt, und mehr als das gemessen.** Besatzung Hochkreuth **11 Zeilen / 5 Rollen / 5 Anlegen-Formulare** → nach „kro" **1 / 1 / 0** → nach „zzzz" **0 / 0** mit sichtbarem Leerzustand → nach **Escape** wieder **11 / 5 / 5**. Die drei Zahlen sind Absicht: Ein Zwischentitel ohne sichtbare Zeile ließe die Karte leer statt gefiltert aussehen, und die Anlegen-Formulare stehen in dieser Karte **je Rolle** — unter einem Treffer stünden sonst vier verwaiste. Gemessen an `offsetParent`/`getClientRects()`, nicht an `hidden`: Das Attribut steht im DOM auch dann, wenn die Regel fehlte | Klickprobe `ap5-filter-blendet-aus`, zwei Bilder |
| Artzeichen in der Rettungsmittel-Zeile (F-S9-K-04) | **Erfüllt: 3 von 3** Zeilen, jede mit Textalternative („luftgebunden", „luftgebunden", „Bergwacht, luftgebunden") — der **Typ** steht mit darin, sonst sähe eine Bergwacht aus wie ein NEF. Vorher: null. Der Kommentar an der Stelle behauptet seit Web 7.0.0 das Gegenteil, und `dt_art_symbol()` wurde dafür sogar berechnet und nie benutzt | Klickprobe `ap5-artzeichen-in-der-zeile`, Bild |
| Unterpunkte der Leiste = sechs (bzw. fünf ohne Luft), drei auf der Liste | **Erfüllt** — und die Karten tragen jetzt den Vorsatz `k-`, den `Design.md` 9.25 vorschreibt und den der übrige Bestand an dreißig Stellen führt. Die Mockups zeichnen `#standort`; ein Bild ist keine Namensregel | eigenes Messskript, Bilderlauf |
| Gegenprobe: der Umbau bricht nichts | **24 von 24** vorhandenen Wegen erfüllt (AP1 bis AP4a), **0 Rückstände**: 21 Rettungsmittel, 61 Diensttage, 346 Einsätze wie vorher | `tools/klickprobe/` |
| Stilvergleich | Gegen Web 16.2.2: Kaskade **725 → 740 Regeln**, **0 entfallen, 58 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen**. Berechnete Stile **48 360 Elementmessungen, 650 Abweichungen** über 13 Breiten — `seiten.html` **10** Elemente je Breite, `katalog.html` **40**, `js_markup.html` **0**; Pseudoprobe **19 981 Messungen, 520 Abweichungen** (40 je Breite), darin `.sprungziel.pchover` und `.kartenfilter-x.pchover` — die Bedienzustände der neuen Bausteine sind also gemessen. **Alle Abweichungen erklärt**, in zwei Gruppen: die fünfzehn neuen Regeln an ihren eigenen Elementen, und Höhenfolgen im langen Katalogdokument (`html`/`body` und absolut gesetzte Elemente weiter unten). Keine außerhalb | `tools/stilvergleich/` |
| Bilderlauf | **3 berührte Seiten, 24 Einzelbilder + 3 Kontaktbögen je Lauf, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** in beiden Bedienhöhen. **Die Auswahl ist erweitert**: Sie lautet jetzt `.knopf, .sprungziel` — die Pille ist `--knopf` hoch, trug die Klasse aber nicht und wäre aus der Messung gefallen (F-S9-U-25) | `tools/screenshots/aufnehmen.mjs` |
| Wortliste, Vollständigkeit, Kontraste, Linkprobe | Wortliste **0/0/0** bei 96 Regeln — im **ersten** Lauf **1 Treffer**, und zwar in einem Satz, den dieses Paket neu geschrieben hatte („Hubschrauber, Fahrzeug, Berg …" im Handbuch); neutral gefasst, dann null. Vollständigkeit **304 = 304**. Kontraste **21 → 22 Paare, 0 verfehlt** — neu „Dunkelblau auf Orange hell", die Kombination steht seit O6 an `.kennzahl.aktiv` und `.listenfilter.aktiv` und war nie gerechnet. Linkprobe **128 Verweise, 0 unbekannte Abweichungen** | die vier Prüfmittel |
| `Design.md` nachgezogen | **Drei neue Kapitel** (9.31 Sprungliste, 9.32 Kartenfilter, 9.33 „Zum Anfang"), Kapitel 9.0 von einer auf **fünf** Zeilen zum Thema Springen und Filtern, Kapitel 7 und 9.10 um die Dreispalten-Ausnahme, Änderungsverlauf. Erzeugte Tabellen neu: Bausteinvorrat **37 → 39 Funktionen**. Zwei Berichtigungen beim Gegenlesen (F-S9-U-26) | `tools/design/tabellen.py alle` |

### AP5 Teil 4 — die Dialoge und die Verwaltungsseite (Web 17.0.0)

| Soll (Konzept, E-S9-19) | Ist | Mittel |
|---|---|---|
| Die Eingabe unter der Liste entfällt; „Anlegen" im Kartenkopf, „Bearbeiten" im Zeilenmenü | **Erfüllt.** `grep -c "sd_form" server/` **0** (vorher 9 Aufrufe in zwei Dateien); die Standortseite trägt **5 Anlegen-Öffner** (`dlg-veh`, `dlg-crew`, `dlg-td`, `dlg-res`, `dlg-bw`) und je Zeile einen Bearbeiten-Öffner. Fünf GET-Parameter (`ev`, `ec`, `et`, `er`, `ew`) und die Schließung `$pickIn()` sind ersatzlos entfallen | Browser, eigenes Messskript |
| Der Dialog gehört zur Karte und trägt den Standort in der **Unterzeile**, nicht als Feld | **Erfüllt.** Gemessen: Titel „Rettungsmittel anlegen", Unterzeile „Standort Luftrettungsstation Hochkreuth", Knopf „Anlegen". Beim Typ **Standard** steht der Standort als Satz unter den Feldern („Standort: … — der Dialog gehört zu seiner Seite"), bei den drei übrigen Typen als **Auswahl** | Klickprobe `ap5-dialog-anlegen-landet-auf-der-zeile`, `ap5-dialog-typ-steuert-die-felder` |
| Feldfolge Rettungsmittel: Bezeichnung, Kurzname, **Typ vor Betriebsart**, Rollen und Fähigkeiten nur bei Standard, Fähigkeiten nur bei Luft | **Erfüllt und in beide Richtungen gemessen.** Standard + Luft: **5 sichtbare Rollen** (die Luftrollen plus „Sonstige"), Fähigkeiten **an**, Standortauswahl **aus**, Standortsatz **an**. Veranstaltung: „luftgebunden" **gesperrt**, „bodengebunden" **gesetzt**, Hinweis „bei Veranstaltung fest" **sichtbar**, **0 Rollen**, Fähigkeiten **aus**, Hinweis „keine Vorlagen" **an**, Standortauswahl **an** | Klickprobe `ap5-dialog-typ-steuert-die-felder`, zwei Bilder |
| Die Regeln kommen aus **einer** Quelle, nicht aus einer abgetippten zweiten | **Erfüllt.** Das Seitenskript liest `VEHICLE_TYPEN` über `json_js()` — dieselbe Konstante, aus der `pruef_rettungsmittel()` entscheidet. Zwei Fassungen von vier Regeln gibt es nicht | Lesen (`server/einstellungen.php`, `server/admin_stammdaten.php`) |
| Die Sperre im Browser ist **Anzeige**, nicht Prüfung — der Server entscheidet | **Erfüllt und eigens belegt.** Drei AP4-Wege setzen Betriebsart und Standort **ohne Ereignis** am Dialog vorbei und senden ab; der Server weist ab bzw. erzwingt: „Veranstaltung" gewählt luftgebunden → gespeichert **bodengebunden**; „Standard" ohne Standort → **1 Fehlermeldung**, die den Standort nennt, **0 angelegt** | Klickprobe `ap4-veranstaltung-boden`, `ap4-standard-braucht-standort`, `ap4-typen-anlegen` |
| Fehler bleiben **im** Dialog | **Erfüllt.** Nach einer abgelehnten Zielklinik: Dialog **offen**, Meldung **im** Dialog („Diese Zielklinik gibt es an diesem Standort schon."), **0** Meldungen am Seitenkopf, und das Namensfeld trägt noch **den eingegebenen Wert**. Auch der leere Pflichtname (mit entferntem `required` abgeschickt): „Bitte eine Bezeichnung eintragen." im Dialog | Klickprobe `ap5-dialog-fehler-bleibt-im-dialog`, Bild; Browser |
| Erfolg landet auf der neuen Zeile, **ohne** zusätzliche Erfolgsmeldung | **Erfüllt.** Zielklinik anlegen → Adresse **`#td-143`**, Zeile gefunden, Fläche **rgb(255, 235, 214)**, **0 Meldungen** am Seitenkopf. Rettungsmittel anlegen → `#veh-144`, Oberkante **72 px** bei 56 px Kopfleiste. Besatzung in der Verwaltung → `#crew-259` | Klickprobe (drei Wege), Bilder |
| „Standort anlegen" landet auf der neuen Standortseite | **Erfüllt.** Verwaltung: nach dem Anlegen steht die Adresse auf **`?t=standort&s=37#k-standort`**. Im Konto derselbe Weg | Klickprobe `ap5-verwaltung-besatzung-anlegen` |
| „Bearbeiten" füllt den Dialog aus dem Öffner, **ohne** die Seite neu zu laden | **Erfüllt: 7 von 7 Schlüsseln.** Kennung 146, Name „Alpenfalke 1", Kurzname leer, Typ `standard`, Betriebsart `air`, Rollen `fr,hems,other,p1,p2`, Fähigkeiten `bergwacht,winch` — und **die Adresse ist unverändert**. Titel „Rettungsmittel bearbeiten", Knopf „Änderung speichern" | Klickprobe `ap5-dialog-bearbeiten-ist-vorbelegt`, Bild |
| Die geschlossenen Dialoge nehmen keinen Platz ein | **Erfüllt — nach einer Berichtigung (F-S9-U-27).** Gemessen: **5 Dialoge im Markup, davon sichtbar 0**, jeder mit `display:none`; nach dem Öffnen sichtbar **genau einer**. Vor der Berichtigung waren alle fünf sichtbar, als Kästen am Seitenende | Klickprobe `ap5-dialoge-sind-zu` |
| Ein Formulardialog ist am Handy bedienbar | **Erfüllt.** 390 × 780 px: Dialog **756 px** hoch, Fuß bei **768 px** im Bild, Inhalt rollt (**Rollweg 279 px**), Knopfhöhe **44 px**. Am Zeigergerät bei 1280 × 900 px: **876 px** Höchsthöhe, Inhalt rollt bei aufgeklappten Rollen | Browser, eigenes Messskript, zwei Bilder |
| Das Ortsfeld im Dialog funktioniert samt Kartendialog | **Erfüllt.** Pin-Knopf öffnet das Blatt **innerhalb** des Dialogs (`position:absolute`, 256 px breit, ganz im Bild); „Auf der Karte wählen" öffnet den Kartendialog als **zweite** modale Ebene — gemessen: `dialog[open]` = `dlg-td` **und** `dialog dialog-karte`. **0 `pageerror`** | Browser, eigenes Messskript, Bild |
| Verwaltung → Stammdaten: dieselben Dialoge, dieselbe Landung | **Erfüllt.** Die Seite hat keine zwei Reiter mehr: `?t=standorte` ist die Liste, `?t=standort&s=<id>` die Seite mit **6 Karten** (`k-standort`, `k-rettungsmittel`, `k-besatzung`, `k-zielkliniken`, `k-weitere`, `k-bergwacht`), **3 Kennzahlen**, **5 „Zum Anfang"**, **5 Dialogen**. Die Bergwacht-Karte erscheint erst mit einem luftgebundenen Rettungsmittel — gemessen 5 Karten vorher, 6 nachher | Klickprobe `ap5-verwaltung-besatzung-anlegen`, Browser, Bild |
| Backlog Nr. 163 (systemweite Besatzung ließ sich nicht anlegen) | **Behoben und gemessen.** Anlegen → `#crew-259`, Zeile hervorgehoben, **0 Fehlermeldungen**. Vorher: „Bitte Rolle und Namen angeben." bei ausgefüllter Rolle und ausgefülltem Namen — seit Web 9.10.0 | Klickprobe `ap5-verwaltung-besatzung-anlegen` |
| Klickprobe (Gegenprobe: der Umbau bricht nichts) | **34 von 34** Wegen erfüllt, 0 verfehlt. Davon **6 neu** in AP5-4; die **24** älteren laufen weiter, **0 Rückstände** im Bestand. Drei AP4-Wege mussten umgestellt werden — ihr Bedienweg (Formular unter der Liste) gibt es nicht mehr | `tools/klickprobe/` |
| Stilvergleich | Kaskade **741 → 743 Regeln**, **0 entfallen, 10 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen** — die zehn sind genau die Dialog-Regeln (`max-height`, `display`/`flex-direction` an `[open]` und am `<form>`, `min-height`, `overflow-y`, `flex:0 0 auto` an Kopf und Fuß). Berechnete Stile **45 500 Elementmessungen, 936 Abweichungen**, Pseudoprobe **dieselben 936** — sämtlich an `dialog`, `.dialog`, `.dialog-kopf`, `.dialog-inhalt`, `.dialog-fuss` und dem `<form>` darin. **Keine Abweichung außerhalb** | `tools/stilvergleich/` |
| Wortliste, Vollständigkeit, Kontraste, Linkprobe | Wortliste **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 96 Regeln, 96 gegriffen, 35 Dateien. Vollständigkeit **316 → 317**; der eine Unterschied ist **ein Menüpfeil „→"** in einem Kommentar von `version.php` („Verwaltung → Stammdaten"), dieselbe Art wie die sechs aus AP2. Kontraste **22 Paare, 0 verfehlt**. Linkprobe **130 Verweise, 0 unbekannte Abweichungen**, 1 bekannte mit Nummer | die vier Prüfmittel |
| Bilderlauf | **6 von 7** berührten Seiten, **48 Einzelbilder + 6 Kontaktbögen, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** (Zeigergerät, 44/36 px). Die **siebte** — die Standortseite der Verwaltung — konnte **nicht** fotografiert werden, siehe Abschnitt 0 | `tools/screenshots/aufnehmen.mjs` |
| `Design.md` nachgezogen | 9.11 um drei Absätze (Rollen des Inhalts mit Zahlen, `display` an `[open]`, Dialog über Dialog), Änderungsverlauf um eine Zeile. Erzeugte Tabellen neu — Bausteinvorrat **unverändert 39 Funktionen**, kein neues Token, kein neues Symbol | `tools/design/tabellen.py alle` |

### AP5 Teil 5 — Standort löschen, Variante b (Web 17.1.0)

| Soll (M-S9-10 b) | Ist | Mittel |
|---|---|---|
| Rettungsmittel ohne Standortpflicht überleben das Löschen ihres Standorts | **Erfüllt.** Standort mit einem **Standard**- und einem **Bergwacht**-Rettungsmittel und einer Zielklinik: Nach dem Löschen steht das Bergwacht-Rettungsmittel unter „Ohne Standort", das Standard-Rettungsmittel und die Zielklinik sind fort. Karte „Ohne Standort" **3 Einträge** (2 aus dem Referenzbestand + 1 überlebtes) | Klickprobe `ap5-standort-loeschen-variante-b` |
| Der Fremdschlüssel bleibt `ON DELETE CASCADE`; die Ausnahme ist Anwendungslogik | **Erfüllt.** `grep -n "ON DELETE" server/install.php server/update.php` unverändert; das `UPDATE vehicles SET base_id = NULL` läuft **vor** dem `DELETE FROM bases`, in **derselben Transaktion** (`beginTransaction()` … `commit()`, mit `rollBack()` im Fehlerfall) | Lesen (`server/einstellungen.php`, `server/admin_stammdaten.php`) |
| Die Regel steht an einer Stelle | **Erfüllt.** Weder das `UPDATE` noch die Rückfrage kennen die Typennamen: Beide lesen `VEHICLE_TYPEN[...]['standort']` — dieselbe Angabe, aus der `pruef_rettungsmittel()` entscheidet, ob ein Typ ohne Standort angelegt werden darf. Drei Funktionen in `db.php`, **zwei** Aufrufstellen | Lesen (`server/db.php`) |
| Die Rückfrage trennt die Zahl und nennt das Überlebende mit Namen | **Erfüllt und wörtlich gemessen:** „Standort „KP Löschprobe" löschen? **2 eigene Stammdatensätze** dieses Standorts (…) werden mitgelöscht. **1 Rettungsmittel ohne Standortpflicht — KP Bergwacht Probe — bleibt bestehen** und steht danach unter „Ohne Standort". Bereits dokumentierte Diensttage bleiben unverändert." Der Name des mitgelöschten Rettungsmittels steht **nicht** darin — das prüft der Weg eigens | Klickprobe, Bild |
| Danach die Landung auf dem Überlebenden, hervorgehoben | **Erfüllt.** Adresse **`#veh-208`**, Fläche **rgb(255, 235, 214)**, die zugeklappte Karte „Ohne Standort" ist **offen** (das Ankerskript öffnet die Vorfahren) | Klickprobe, Bild |
| Der Bestand bleibt nach dem Prüflauf unverändert | **Erfüllt: 0 Probeeinträge.** Der Weg legt Standort, zwei Rettungsmittel und eine Zielklinik an und räumt in `finally` auch das Überlebende wieder ab — es hängt nach dem Löschen an keinem Standort mehr und würde sonst dauerhaft stehenbleiben | Klickprobe |
| Klickprobe insgesamt | **35 von 35** Wegen erfüllt, 0 verfehlt | `tools/klickprobe/` |
| Wortliste, Vollständigkeit, Kontraste, Linkprobe | Wortliste **0 Treffer außerhalb der Ausnahmen** bei 96 Regeln, 96 gegriffen; Vollständigkeit **317 = 317**; Kontraste **22 Paare, 0 verfehlt**; Linkprobe **130 Verweise, 0 unbekannte Abweichungen** | die vier Prüfmittel |
| Stilvergleich und Bilderlauf | **Nicht gefahren, und das ist begründet:** Dieses Paket fasst `style.css` nicht an (0 Zeilen) und ändert kein Markup, das ein Bild zeigte — die Rückfrage ist ein `data-confirm`-Text, den `confirm.js` erst beim Klick zeichnet, und der Bilderlauf klickt nicht. Was zu sehen ist, zeigen die zwei Bilder der Klickprobe | — |

**Ein Fund beim Bauen, gefunden von der Klickprobe.** Der erste Entwurf von
`stammdaten_loeschfrage()` leitete die deutsche Adjektivendung aus der
Zeichenkette ab (`rtrim('eigene','e') . 'r'`) und schrieb **„Ein eigenr
Stammdatensatz"** — bei der systemweiten Fassung „systemweitr". Der Weg
verglich den Satz **wörtlich** und meldete es; vier Formen stehen jetzt
ausgeschrieben. *Die Lehre: Ein Prüfmittel, das nur „eine Meldung erschien"
zählt, hätte das durchgelassen.*

**Eine Frage, die dieses Paket nicht beantwortet** (siehe Abschnitt 4): Ein
Rettungsmittel, das einer **NutzerIn** gehört und an einem **systemweiten**
Standort hängt, geht weiterhin mit, wenn die Verwaltung diesen Standort
löscht — auch wenn sein Typ keinen Standort braucht. Der Grund ist keine
Entscheidung, sondern ein Zuschnitt: Die Rückfrage der Verwaltung zählt seit
jeher nur den systemweiten Bestand und sagt daneben, wie viele Konten den
Standort gewählt haben; sie könnte einen Satz über fremde Rettungsmittel
nicht belegen, ohne vorher etwas zu zählen, was sie sonst nirgends zählt.

### AP5 Teil 6 — der Prüflauf über zwei Breiten und beide Bedienhöhen (Web 17.1.1)

**Das ist die Abnahme von AP5.** Die Teile 1 bis 5 haben je für sich gemessen;
hier läuft alles noch einmal, und zwar in **beiden** Bedienhöhen — 44 px am
Fingergerät und unter 1024 px, 36 px am Zeigergerät ab 1024 px (R76). Ein
Prüfmittel, das nur eine Breite kennt, misst die halbe Anwendung.

| Mittel | Zahl | Bemerkung |
|---|---|---|
| Klickprobe, Zeigergerät | **70 von 70** Wegen erfüllt, 0 verfehlt | 35 Wege × 2 Breiten (390 und 1280 px) |
| Klickprobe, Fingergerät | **70 von 70** Wegen erfüllt, 0 verfehlt | dieselben Wege, `Emulation.setTouchEmulationEnabled` |
| Bestand danach | **21 Rettungsmittel · 8 Standorte · 61 Diensttage · 346 Einsätze** und **0** Probeeinträge | `name LIKE 'KP %'` in `vehicles`, `bases`, `transport_dests`, `crew_presets` — je 0 |
| Bilderlauf, beide Bedienhöhen | **48 Einzelbilder + 6 Kontaktbögen je Lauf**, **0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen** | 6 von 7 berührten Seiten; die siebte siehe Abschnitt 0 |
| Wortliste | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** | 96 Regeln, 96 gegriffen, 35 Dateien, fünf Bereiche |
| Vollständigkeit | **316 → 318** | beide Unterschiede benannt: ein Menüpfeil „→" und ein „⋯" in Kommentaren von `version.php` — dieselbe Art wie die sechs aus AP2 |
| Kontraste | **22 Paare, 0 verfehlt** | |
| Linkprobe | **130 Verweise, 0 unbekannte Abweichungen**, 1 bekannte mit Nummer (Nr. 151) | |
| Stilvergleich | **nicht erneut gefahren** — seit dem Lauf zu 17.0.0 ist `style.css` unverändert (`git diff` 0 Zeilen) | die Zahlen stehen bei Teil 4 |

**Drei Funde des Prüflaufs, alle behoben.**

**F-S9-U-31 — Der Leerzustand des Kartenfilters sagte einen Satz, den Teil 4
falsch gemacht hatte.** „Kein Eintrag passt dazu. Leere den Filter, um etwas
anzulegen." stimmte, solange die Anlegen-Formulare in der Liste standen. Seit
Web 17.0.0 steht „Anlegen" im **Kartenkopf**, also über dem Filter — es ist
auch bei null Treffern erreichbar, und die Klickprobe misst genau das. Der
Satz beschrieb eine Sackgasse, die es nicht mehr gibt. *Die Lehre: Wer eine
Bedienung umbaut, muss die Sätze mitlesen, die sie erklären — der Text stand
zwei Dateien weiter und lief durch jede grüne Zahl hindurch.*

**F-S9-U-32 — Zwei Wege der Klickprobe galten nur bei 1280 px.** Der Lauf über
zwei Breiten war der erste; beide Wege waren nie an einem schmalen Fenster
gefahren worden.
*(a)* „Bearbeiten" steht unter 720 px im **Aktionsblatt** und nicht in der
Zeile (`ui_zeilenaktionen`: Knopfreihe am Schreibtisch, „⋯" plus Blatt am
Handy). Der Öffner ist dort im Markup, aber verborgen; ein Klick lief in die
Zeitgrenze. Der Weg geht jetzt den Weg, den eine Person geht.
*(b)* Die **Richtungspfeile** auf der Spur gibt es bei 390 px gar nicht:
`geo.js` zeichnet einen alle 140 px und keinen, wenn die ganze Spur kürzer
als zwei Abstände ist (E-P3-33/40, „herausgezoomt verschwinden sie von
selbst"). Der Weg maß **0 von 0** und meldete das als Fehlschlag — richtig
nach der Doktrin der Probe („ein Weg, der nichts misst, ist verfehlt"), falsch
in der Sache. Er misst jetzt die **Länge der Spur am Bildschirm**
(`getTotalLength()` am Spurpfad — genau die Zahl, aus der `geo.js` entscheidet)
und erwartet Pfeile, wenn die Schwelle es sagt: gemessen **190 px** im 390er
Fenster (0 Pfeile, richtig) und **358 px** bei 1280 px (2 Pfeile).

**F-S9-U-33 — Der Bilderlauf nannte für acht fehlende Aufnahmen den falschen
Grund.** „OHNE BILD: 8 Aufnahmen — Sitzung nicht zu halten" stand da, wo in
Wahrheit ein Platzhalter nicht auflösbar war (die Standortseite der
Verwaltung, Backlog Nr. 166). Zwei verschiedene Befunde liefen in eine Liste
und bekamen den Text des zweiten. Jede ausgefallene Aufnahme trägt jetzt
ihren Grund, und die Zusammenfassung zählt nach Grund: **„8× Platzhalter
`__ADMIN_STANDORT__` nicht auflösbar"**. *Eine Zahl, die den falschen Grund
nennt, schickt die nächste Suche in die falsche Richtung — das ist schlimmer
als gar keine Zahl.*

**Eine Absicherung beim Gegenlesen (kein Fund im Betrieb, aber einer im
Code).** Das `UPDATE` aus Teil 5, das den Rettungsmitteln ohne
Standortpflicht den Standort abnimmt, lief **vor** der Prüfung, ob der
Standort überhaupt der eigene ist. Das `DELETE` darunter schützt sich selbst
(`AND user_id = ?`) und tut bei einer fremden Kennung nichts — das `UPDATE`
davor tat etwas: Ein abgeschicktes `base_del` mit der Kennung eines
**zentralen** Standorts hätte den eigenen Rettungsmitteln dort den Standort
abgenommen, ohne dass ein Standort gelöscht worden wäre. Beide Seiten prüfen
jetzt zuerst. **Gegengeprobt** mit einer erfundenen Kennung über ein von Hand
gebautes Formular: Meldung statt Datenänderung, **2 Einträge unter „Ohne
Standort" vorher und 2 nachher**.

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

*Behoben mit Web 15.7.1:* Ebene **35** — über der Speichern-Leiste (30),
unter der Kopfleiste (40). Nach oben ist sie ebenso begrenzt und aus
demselben Grund: Eine Vorschlagsliste, die über die Kopfleiste malt,
verdeckt den Weg aus der Seite heraus. Die Klickprobe hat dafür den Weg
`ap1-liste-ueber-speichern-leiste` bekommen, der **beide** Richtungen misst;
er ist gegen den alten Stand gefahren worden und meldete dort „oben liegt:
speichern-innen".

**F-S9-P-08 — Der Bootstrap schrieb `const`, das Modul las `window`.**
*Gefunden mit der Klickprobe, 07.09.2026 — im Browser war nichts zu sehen.*
`ui_geocoder_bootstrap()` gab die beiden Werte als `const GEO_AN` /
`const GEO_DIENST` aus, nach dem Vorbild von `ui_krypto_bootstrap()`. Dort
geht das gut, weil seine Leser **Inline-Skripte derselben Seite** sind: Ein
`const` auf oberster Ebene liegt im globalen **lexikalischen** Bereich, den
jedes Skript sieht — aber es wird **keine Eigenschaft von `window`**.
`assets/geocoder.js` ist eine eigene Datei und liest `global.GEO_DIENST`;
die stand damit auf `undefined`, `EdGeocoder.an()` lieferte `false`, und der
Kartendialog kam **ohne Suchfeld** — auf einer Seite, deren Hinweiszeile
daneben sagte, die Suche sei an.

**Warum es keine Konsolenprüfung findet:** Wer in der Konsole `GEO_DIENST`
eintippt, bekommt den lexikalischen Wert und damit genau die Antwort, die er
erwartet. Der erste Browserlauf dieses Pakets hat das so bestätigt — und
nichts gemessen. Erst der Weg `ap2-treffer-setzt-nur-das-kreuz` fiel darüber,
weil er ein Element **suchte**, das es nicht gab. *Behoben:*
`window.GEO_AN` / `window.GEO_DIENST`; die Begründung steht im Kopf der
Funktion, damit der nächste Bootstrap nicht denselben Weg geht.

**F-S9-P-09 — Nach dem Speichern zeigte die Seite den alten Schalterstand.**
*Gefunden mit der Klickprobe, 07.09.2026.* Betrieb → Servereinstellungen
meldete „Adresssuche ausgeschaltet gespeichert." — und ließ den Schalter auf
**an** stehen, samt Plakette „an". Erst ein Neuladen zeigte die Wahrheit; in
der Datenbank stand der neue Wert von Anfang an. Ursache:
`geocoder_state()` hält einen Zwischenspeicher je Anfrage, und
`geocoder_installation_setzen()` **liest vor dem Schreiben** (um zu wissen, ob
sich etwas ändert) — damit war der Speicher gefüllt, und die Ausgabe
derselben Anfrage las ihn wieder aus. Der Kommentar an
`geocoder_state_setzen()` hatte das sogar beschrieben und mit „ein Neuladen
der Seite folgt ohnehin" abgetan; es folgt keines. *Behoben:* Der Setzer
zieht den Speicher nach (`geocoder_state($k, true)`), und das Schreiben des
Kontoschalters ist als `geocoder_konto_setzen()` in dasselbe Modul gewandert,
damit keine Seite daran vorbei schreibt.

**F-S9-P-10 — Das Demo-Konto konnte seine Adresssuche nicht abschalten.**
*Gefunden mit der Klickprobe, 07.09.2026.* Der Schalter stand in der Karte
„Datenschutz" **im Formular des Profils**, und der Demo-Wächter
(`einstellungen.php`, `demo_ist_demo()`) verwirft `action=profile` ganz —
damit die öffentliche Anmelde-Adresse und das Passwort des Demo-Kontos stehen
bleiben. Gemessen: Häkchen entfernt, „Profil speichern" gedrückt, Spalte
`users.adresssuche` unverändert **1**, dazu die Fehlermeldung des Wächters.
Ausgerechnet das Konto, an dem alle die Anwendung ausprobieren, hätte seine
eigene Adresssuche nicht abschalten können — und der Satz des Wächters
(„Alles andere darfst du gern ausprobieren") hätte nicht mehr gestimmt.
*Behoben:* eigene Handlung, eigenes Formular (`action=datenschutz`), eigener
Speichern-Knopf in der Karte — dieselbe Trennung wie in `betrieb_server.php`.
Ein Tippfehler in der E-Mail-Adresse weist damit auch den Schalter nicht mehr
mit ab.

**F-S9-P-11 — Zwei Zeichen ohne Kasten, drei Zeilen auseinander.**
*Gefunden bei der Aufklärung zu Backlog Nr. 72, 07.09.2026.* `.geo-pfeil`
trug seine Drehung, `.geo-punkt` seine Größe — beide an einem `<span>`, und
an einem nicht ersetzten Inline-Element wirkt weder `transform` noch `width`.
Die Pfeile zeigten deshalb ausnahmslos nach Norden (Nr. 72, gemeldet), der
Abfahrtort maß **4 × 18 px statt 12 × 12** und zeigte seine Spurfarbe nie
(**Nr. 162, in keinem Backlog-Punkt**).

**Nachweisbar nur an der Geometrie.** `getComputedStyle` meldet die
Drehmatrix auch dort, wo sie nichts bewirkt — wer den Fehler in den
Entwicklerwerkzeugen prüft, bekommt „sieht richtig aus" zurück. Die
Kontrollprobe: Ein 20-px-Kasten mit `rotate(45deg)` misst **28,3 px**
(= 20·√2), wenn die Drehung greift, und **20 px**, wenn nicht; gemessen
waren 20. Die Bildschirmmatrix des SVG lautete `a=0,833 b=0 c=0 d=0,833` bei
behaupteten 90 Grad. *Behoben:* beide bekommen einen ausdrücklichen Kasten;
nachher 12 von 12 Pfeilen auf 0,1 Grad genau und der Punkt bei 12 × 12 px mit
sichtbarer Farbe.

**F-S9-P-12 — E-S9-13 hatte keine Abnahme.** *Gefunden von der Gegenprobe der
Aufklärung.* Die Abnahme von AP3 nennt sechs Kriterien — Schildmaße,
Kontrast, Pfeile, Windenkacheln, Wortliste, `grep`. **Keines betrifft die
Artzeichen.** Drei neue Dateien, eine geänderte Signatur, Vorrat 49 → 52, ein
Tooltip: nichts davon war abzuhaken, und das Prüfdokument hatte dafür keinen
P-Punkt (`grep -ci "artzeichen\|dt_art_symbol"` = 0). Wer AP3 nach der Liste
abgeschlossen hätte, hätte es mit drei ungeprüften Dateien abgeschlossen.
*Behoben:* Der Weg `ap3-artzeichen-sechs` prüft alle sechs Zeichen auf
Ladbarkeit, Anker `id="i"`, Pfade und Herkunftsangabe im Kopf — **6 von 6**.
Ein fehlender Anker ist der einzige stille Fehler dieses Wegs: Der Browser
malt dann nichts und sagt nichts.

**F-S9-P-13 — Der Ringpunkt ist antippbar und wurde kleiner als die
Untergrenze, die derselbe Beschluss nennt.** *Gefunden von der Gegenprobe.*
E-S9-12 sagt in einem Atemzug: „Bedienhöhe R76 gilt nicht (Schilder sind
Zeichnungen), **als Untergrenze am Finger gilt 24 px (WCAG 2.5.8)**" — und
setzt den Ringpunkt von 16 auf **14 px**. Er ist kein reines Bild: `geo.js`
hängt ihm ein Popup an, und `einsatz.php` gibt ihm einen Titel („Start und
Ende der Aufzeichnung"). Beide Werte liegen unter 24; der Fehler ist also
älter als dieses Paket, wird von ihm aber verstärkt. *Behoben, ohne die
Freigabe anzutasten:* Die **Zeichnung** bleibt 14 px, wie das Mockup sie
abgenommen hat, und sitzt in einer durchsichtigen **24-px-Fläche**
(`.geo-ringpunkt-feld`). Gemessen: Zeichnung 14, Antippfläche 24.

**F-S9-P-14 — Die Abnahmezahl „sechs Pfeile" ist nicht reproduzierbar.**
*Gefunden bei der Aufklärung.* Die Pfeilzahl folgt
`floor(Spurlänge_in_Bildschirmpixeln / 140)` und hängt damit an der
Fensterbreite und an der Zoomstufe, die `fitBounds` wählt. „Sechs Pfeile"
heißt: 840 px ≤ Spurlänge < 980 px — bei 1280 × 900 sind es auf dem
Referenzeinsatz **2**. Eine Abnahme, die eine Zahl festschreibt, ohne die
Fensterbreite mitzuschreiben, ist bei einem anderen Bildschirm falsch.
*Ersetzt durch:* **12 von 12** Pfeilen in 30-Grad-Schritten in einer
Aufstellung fester Winkel (reproduzierbar, unabhängig von der Karte)
**plus** „jeder Pfeil auf der Spur trifft seinen Sollwinkel" (2 von 2 bei
1280 px). Die zweite Zahl bleibt fensterabhängig, die erste nicht.

### Funde, die AP2 abgeräumt hat

**F-S9-P-05 — Die Beschriftung eines Adresstreffers steht zweimal im Code.**
`photonLabel()` in `assets/ortsfeld.js` und `label()` in `assets/ortswahl.js`
sind wortgleich. **Gehört in AP2**, wo beide Photon-Aufrufe in
`assets/geocoder.js` zusammenlaufen (E-S9-05) — dort ist es eine Zeile, hier
wäre es eine dritte Fassung.
**Erledigt mit AP2:** `EdGeocoder` beschriftet einmal; `grep -n "function
label\|photonLabel" server/assets/*.js` findet die Funktion nur noch als
Erwähnung im Kopfkommentar von `geocoder.js`.

**F-S9-P-06 — Der Schlüssel `such` wird von `ui_ortsfeld()` nicht gelesen.**
Vier Aufrufe setzen `'such' => true` (`admin_stammdaten.php:504, 724`;
`einstellungen.php:1304, 1635`); die Funktion hat den Zweig seit O5 nicht
mehr. Der Kopfkommentar von `ortsfeld.js` nannte bis AP1 ebenfalls ein
Element `<p>such`, das nirgends gesucht wird, und den tatsächlich gesuchten
Lupen-Knopf nicht. **Kein Schaden, aber eine Lüge in der Dokumentation.**
AP2 fasst dieselben Aufrufe an (zweite Einbauform, E-S9-06 c) und räumt es
dort mit.
**Erledigt mit AP2:** `grep -rn "'such'" server/` = **0**; an der Stelle steht
jetzt `'ortswahl' => true`, das die Funktion tatsächlich liest.

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

- [ ] **7 — Der echte Adressdienst im Kartendialog.**
  *Weg:* Einsatz öffnen → Pin am Einsatzort → „Auf der Karte wählen" → im
  Suchfeld einen realen Ort tippen → einen Treffer wählen → **das Kreuz von
  Hand ein Stück verschieben** → „Übernehmen".
  *Erwartet:* Die Karte springt auf den Treffer, sein Name steht im
  **Suchfeld**; das Formular bleibt bis „Übernehmen" unverändert. Nach
  „Übernehmen" steht die Koordinate des **Kreuzes** im Chip — nicht die des
  Treffers —, und das Bezeichnungsfeld füllt sich mit der Adresse aus der
  Umkehrsuche, sofern es leer war.
  *Scheitern erkennbar an:* Der Treffer schreibt schon etwas ins Formular
  (dann greift F1 nicht), oder die Koordinate im Chip ist die des Treffers
  statt die des verschobenen Kreuzes.

- [ ] **8 — Der Weg nach dem Deploy: erst hochladen, dann `update.php`.**
  *Weg:* Nach dem Merge auf `main` **vor** dem Aufruf von `update.php` eine
  Seite mit Ortsfeld öffnen (z. B. ein Einsatzformular), dann `update.php`
  aufrufen und dieselbe Seite erneut.
  *Erwartet:* **Beide Male keine Fehlermeldung.** Vor der Migration fehlt die
  Spalte `users.adresssuche`; beide Leser fangen das ab und liefern die
  Vorgabe „an". Nach `update.php` steht in Betrieb → Updates die Migration
  `2026_09_07_adresssuche_konto` als angewendet.
  *Scheitern erkennbar an:* Eine weiße Seite oder eine Datenbankmeldung beim
  ersten Aufruf. **Das ist der einzige Punkt dieses Pakets, der auf dem
  Prüfstand nicht messbar war** — dort ist die Spalte da.

- [ ] **9 — Ein eigener Photon-Dienst (nur falls einer betrieben wird).**
  *Weg:* Betrieb → Servereinstellungen → Karte „Adresssuche" → im Feld
  „Dienst" die eigene Adresse eintragen, speichern; danach in einem Ortsfeld
  tippen.
  *Erwartet:* Die Vorschläge kommen; der Hinweis unter dem Ortsfeld, die
  Karte „Datenschutz" im Profil und der Textbaustein auf der
  Installationsseite nennen ab sofort **den neuen Rechnernamen**.
  *Scheitern erkennbar an:* Die Vorschläge bleiben aus (dann antwortet der
  Dienst nicht im Photon-Format oder verweigert die Herkunft — die
  Browserkonsole nennt CORS), oder irgendwo steht noch der alte Name.

- [ ] **11 — Die Kartenzeichen gegen die echte Karte, nicht gegen Schnee.**
  *Weg:* Einen Einsatz mit Aufzeichnung öffnen, die Karte auf **alle drei
  Ebenen** stellen (Standard, Topografisch, Luftbild) und in jeder auf Start-
  und Endring sehen — am Handy und am Schreibtisch.
  *Erwartet:* Blau und Rot heben sich ab; die 1-px-Schneelinie trennt Farbe
  und Karte.
  *Scheitern erkennbar an:* Der blaue Ring verschwimmt auf dem Luftbild oder
  im Wald. **Das kann kein Werkzeug messen:** `kontrast.py` rechnet Token
  gegen Token, nicht gegen Kartenkacheln, und die Trennlinie ist von 3 auf
  1 px dünner geworden. Blau liegt bei 3,77:1 gegen Schnee — über der Schwelle
  für grafische Zeichen (3:1), aber unter der für Text.

- [ ] **12 — Den Ringpunkt mit dem Finger treffen.**
  *Weg:* Am Handy einen Einsatz öffnen, dessen Aufzeichnung abseits von
  Standort und Zielklinik beginnt oder endet, und den kleinen Ring antippen.
  *Erwartet:* Das Popup „Start der Aufzeichnung" öffnet sich beim ersten
  Tipp. Die Zeichnung ist 14 px, die Antippfläche 24 px — beides gemessen.
  *Scheitern erkennbar an:* Man trifft daneben oder muss zoomen. Dann ist
  24 px zu wenig, und die Fläche muss über die Zeichnung hinauswachsen (sie
  ist durchsichtig, das kostet nichts als eine Zahl).

- [ ] **13 — Die Richtungspfeile auf einer Ost-West-Strecke.**
  *Weg:* Einen Einsatz mit Aufzeichnung öffnen, deren Weg deutlich nach Osten
  oder Westen führt — und **zwei Zoomstufen** durchgehen.
  *Erwartet:* Die Pfeile zeigen in Fahrtrichtung, auch nach dem Zoomen (sie
  werden bei jedem `zoomend` neu verteilt, an anderen Stellen und mit anderen
  Winkeln).
  *Scheitern erkennbar an:* Pfeile, die nach Norden zeigen. **Auf einer
  Nord-Süd-Strecke sieht ein kaputter Pfeil richtig aus** — genau daran ist
  Nr. 72 so lange vorbeigelaufen.

- [ ] **14 — „GPS-Daten" im Betrieb und im Sicherungsweg.**
  *Weg:* Betrieb → Hintergrundjobs ansehen; einen Export mit GPS-Daten
  starten und einen Import mit einer `.edbak`-Datei.
  *Erwartet:* Die Jobnamen heißen „GPS-Daten verdichten", „GPS-Daten
  ausdünnen", „Verwaiste GPS-Daten"; die Fortschrittsmeldungen sagen
  „GPS-Daten werden übertragen".
  *Scheitern erkennbar an:* Eine Meldung sagt noch „Spur" — dann ist eine
  Zeichenkette übersehen worden. Der Weg `ap3-wording-gps-daten` der
  Klickprobe sieht nur vier Seiten an, nicht den ganzen Sicherungsweg.

- [ ] **10 — Der Datenschutztext.**
  *Weg:* Verwaltung → Installation → unter dem Feld für die
  Datenschutzerklärung den Textbaustein **kopieren**, in die Erklärung
  einfügen, speichern, „Ansehen".
  *Erwartet:* Der Abschnitt „Adresssuche" steht auf der öffentlichen Seite
  und nennt die eingetragene Dienstadresse.
  *Scheitern erkennbar an:* Der Kopieren-Knopf tut nichts (dann fehlt
  `assets/kopieren.js`), oder der eingefügte Text erscheint als eine
  einzige Zeile (dann sind die Absatzumbrüche beim Einfügen verlorengegangen
  — der eingeschränkte Markdown braucht Leerzeilen zwischen Absätzen,
  Handbuch 11.5).

- [ ] **15 — Ein Rettungsmittel je Typ anlegen und wiederfinden (AP4).**
  *Weg:* Einstellungen → Rettungsmittel → in einem Standortblock nacheinander
  drei Rettungsmittel anlegen: Typ **Bergwacht** mit Kurznamen und Standort,
  Typ **Veranstaltung** mit Haken „Ohne Standort" und Art *luftgebunden*,
  Typ **Sonstiges** mit Haken „Ohne Standort".
  *Erwartet:* Alle drei erscheinen; die beiden mit Haken stehen am Ende der
  Seite in der Karte **„Ohne Standort"**; das Zeichen des
  Veranstaltungs-Eintrags sagt „Veranstaltung, **bodengebunden**", obwohl
  luftgebunden gewählt war; bei allen dreien steht in der Kleinzeile „keine
  Rollen".
  *Scheitern erkennbar an:* Ein Eintrag fehlt ganz (dann greift die
  Ladebedingung `base_id IN (…)` noch), oder die Veranstaltung steht als
  luftgebunden da (dann erzwingt die Prüfschicht die Betriebsart nicht).

- [ ] **16 — Ein Rettungsmittel ohne Standort ändern (AP4).**
  *Weg:* In der Karte „Ohne Standort" bei einem Eintrag **„Bearbeiten"**
  wählen, den Kurznamen ändern, speichern.
  *Erwartet:* Das Formular öffnet sich im **ersten** Standortblock, der Haken
  „Ohne Standort" ist gesetzt, nach dem Speichern steht der neue Kurzname in
  der Karte — und der Eintrag ist **nicht** in den Standortblock gewandert.
  *Scheitern erkennbar an:* „Bearbeiten" fehlt oder öffnet nichts (dann ist
  der Verweis ins Leere gebaut), oder das Rettungsmittel hat nach dem
  Speichern plötzlich einen Standort (dann geht der verborgene
  Standortschlüssel des Formulars vor dem Haken).

- [ ] **17 — Einen Diensttag einem Rettungsmittel ohne Standort zuordnen (AP4).**
  *Weg:* Tagesübersicht → Diensttag-Daten → „Bearbeiten" → als Rettungsmittel
  eines **ohne Standort** wählen → speichern.
  *Erwartet:* Das Standortfeld **leert sich sichtbar**, sobald das
  Rettungsmittel gewählt ist; der gespeicherte Tag zeigt keinen Standort.
  *Scheitern erkennbar an:* Im Standortfeld bleibt die Vorbelegung stehen und
  wird mitgespeichert — dann friert der Diensttag einen Standort ein, den sein
  Rettungsmittel gar nicht hat, und niemand hat ihn gewählt.

- [ ] **18 — Der Kurzname in der Leiste über die Breiten (AP4, AP4a).**
  *Weg:* Einen Diensttag einem Rettungsmittel **mit** Kurznamen zuordnen —
  einen, der **allein** auf seinem Datum liegt. Dann die Diensttage-Leiste bei
  **390 px**, bei **1100 px** und bei **1280 px** ansehen.
  *Erwartet:* Bei **390 px** (Schublade) steht der Kurzname; bei **1100 px**
  steht er ebenfalls, und bei den übrigen Tagen steht **gar kein** Nebentext;
  bei **1280 px** stehen alle Namen. Der Tooltip nennt überall die volle
  Bezeichnung.
  *Scheitern erkennbar an:* Bei 1100 px steht der Kurzname als „BW Ho…"
  (dann fehlt die 8-px-Einrückung des Akkordeons) oder gar nicht (dann greift
  die Klasse `kurz` nicht). Steht bei 1100 px auch ein **voller** Name, ist
  die Regel zu weit gefasst.

- [ ] **23 — Der Kurzname am Tag, der sein Datum teilt (AP4a).**
  *Weg:* Zwei Diensttage auf **denselben Kalendertag** legen und einem davon
  ein Rettungsmittel **mit** Kurznamen zuordnen. Leiste bei **1100 px**.
  *Erwartet:* Dieser Tag zeigt **keinen** Nebentext — seine Zeile trägt Datum
  *und* Uhrzeit, und dafür ist die 220 px schmale Leiste zu eng (gemessen
  3 px für einen Namen, der 55 braucht).
  *Scheitern erkennbar an:* Rechts steht ein einzelnes Auslassungszeichen
  oder ein abgeschnittener Buchstabe. Dann greift die Bedingung `!$mehrfach`
  nicht.

- [ ] **24 — Der Typ im Vergleichsdialog (AP4a).**
  *Weg:* Zwei Diensttage derselben **Betriebsart**, aber mit Rettungsmitteln
  **verschiedenen Typs** (etwa Standard und Bergwacht) zusammenführen wollen —
  bis zur Vorschau gehen, **nicht** bestätigen.
  *Erwartet:* „Der Diensttag danach" hat eine Zeile **Typ**, die beide nennt
  („Standard oder Bergwacht") und darunter „Folgt dem Rettungsmittel, das du
  unten wählst." Im Widerspruch „Rettungsmittel" trägt jede der beiden Zeilen
  ihren Typ und, wo vorhanden, den Kurznamen.
  *Scheitern erkennbar an:* Die Zeile nennt nur **einen** Typ. Dann behauptet
  sie etwas, das die Wahl darunter ändert — die Seite lädt beim Klick nicht
  neu. Und: Lassen sich die beiden Tage gar nicht erst zusammenführen, ist
  versehentlich eine Typ-Bedingung in `dt_merge_pruefen()` gelandet.

- [ ] **25 — Bekannter Rest: Kurzname am mehrfachen Tag ab 1200 px (AP4a).**
  *Weg:* Denselben Bestand wie in Punkt 23, aber bei **1280 px** ansehen.
  *Erwartet (heute):* Der Kurzname steht da, aber abgeschnitten — gemessen
  35 px für 55. Das ist **kein Fehler dieses Pakets**, sondern Verhalten seit
  Web 16.0.0: `.eintrag-text` schrumpft nicht, und Datum plus Uhrzeit
  brauchen 128 px statt 76.
  *Zu entscheiden:* ob das so bleibt. Es zu ändern hieße, den Datumstext
  schrumpfen zu lassen — dann ellipsiert das Datum statt des Namens. Wenn es
  stören soll, gehört ein Backlog-Punkt dafür angelegt.

- [ ] **26 — Der Kurzname im schmalen Band, nach der Freigabe (Web 16.1.1).**
  *Weg:* Mehreren Diensttagen mit **verschiedenen Datumsangaben** dasselbe
  Rettungsmittel mit Kurznamen zuordnen, Leiste bei **1100 px**.
  *Erwartet:* Der Kurzname steht überall **vollständig** — gemessen 13 von 13
  ohne Auslassungszeichen. Das Datum ist je nach Ziffern 74 bis 83 px breit,
  dem Namen bleiben 64 bis 79 px. Ein Name über rund 64 px („RTH Murnau"
  misst 76) steht an den schmalen Datumsangaben ganz und an den breiten mit
  Auslassungszeichen; das ist erwartet.
  *Scheitern erkennbar an:* „BW Ho…" bei 1100 px — dann fehlt eine der beiden
  4-px-Regeln. Oder: Der **Leistenfuß** („Diensttag anlegen", „Papierkorb")
  und das **Einstellungsmenü** rücken bei 1100 px enger zusammen als bei
  1280 px — dann ist die Abstandsregel nicht mit `:not(.leiste-gruppe)`
  eingegrenzt und trifft Zeilen, die kein Platzproblem haben.
  *Nichts mehr offen.* **Weg 3** aus dem Mockup M-S9-11 — im schmalen Band das
  **Jahr** aus dem Datum nehmen — ist am **08.09.2026 verworfen**. Er hätte
  rund 30 px gebracht und auch den längsten erlaubten Kurznamen getragen,
  ändert aber, was in einer Zeile *steht*, abhängig von der Fensterbreite;
  sein Preis steht als eigener Rahmen im Mockup (nach einem Sprung aus der
  Suche ist die Jahreszeile weggerollt, und dann fehlt das Jahr ganz). Das
  Datum behält sein Jahr in jeder Breite.

  *Die drei Wege im Mockup, gemessen am laufenden Programm:*
  **Weg 1** — das Akkordeon im Band noch einmal 4 px zurück (`--abstand-1`,
  bringt 8 px): **12 von 12** „BW Hoch" stehen ganz. **Weg 2** — dazu die
  Abstände der Zeile von 8 auf 4 px (weitere 8 px): ebenfalls 12 von 12, also
  **kein einziger mehr als Weg 1**, bei zusätzlicher Gestaltungsänderung.
  **Weg 3** — das Jahr aus dem Datum (spart rund 30 px): 12 von 12 **und der
  16-Zeichen-Kurzname dazu**.
  **Entschieden vom Auftraggeber am 08.09.2026: Weg 2**, gebaut mit Web
  16.1.1; **Weg 3 verworfen**. Die zweite Messung hat dabei eine Aussage von mir berichtigt: „Weg 2
  bringt keinen einzigen Kurznamen mehr als Weg 1" galt nur für den geprüften
  Bestand, der allein 7- und 16-Zeichen-Namen kannte. Über die verfügbare
  Breite gemessen bringt Weg 2 **8 px an jedem Datum** (64–79 statt 56–71)
  und damit rund ein Zeichen mehr, das überall sicher steht.
  *Scheitern erkennbar an:* nichts — hier ist nichts kaputt, hier ist etwas
  zu entscheiden.

- [ ] **27 — Die Standortseite am Gerät (AP5, Teile 1 und 2).**
  *Weg:* Einstellungen → **Standorte**. Eine Zeile antippen. Auf der Seite
  eine **Zielklinik** anlegen, danach eine **Besatzung**, dann beide wieder
  löschen. Am Ende einer Karte **„Zum Anfang"** drücken. Am Schreibtisch (ab
  1024 px) zusätzlich die Unterpunkte in der Leiste links durchgehen.
  *Erwartet:* Der Tipp irgendwo in der Zeile führt auf die Seite — nicht nur
  der Name. Nach jedem Speichern und jedem Löschen steht man **wieder auf
  derselben Standortseite**, an der Stelle, an der man getippt hat.
  „Zum Anfang" springt an den Seitenkopf. Die Leiste zeigt **sechs**
  Unterpunkte (Standort, Rettungsmittel, Besatzung, Zielkliniken, Weitere
  Rettungsmittel, Bergwacht) — an einem Standort ohne luftgebundenes
  Rettungsmittel **fünf**, ohne „Bergwacht".
  *Scheitern erkennbar an:* Nach dem Speichern steht man auf der
  **Standortliste** statt auf der Seite (dann ist die Umleitung wieder auf
  den alten Reiternamen gefallen). Oder „Zum Anfang" tut **nichts** (dann
  zeigt es auf eine Kennung, die es nicht gibt — beides ist ohne
  Fehlermeldung passiert, F-S9-U-17 und -18). Oder die Leiste zeigt
  **sieben** Unterpunkte, der letzte „Ohne Standort" — dann steht die Karte
  wieder auf der Standortseite statt auf der Liste.

- [ ] **28 — Die Verwaltungsseite ist noch die alte (AP5, bekannter Rest).**
  *Weg:* Als Administratorin: **Stammdaten systemweit**.
  *Erwartet:* Sie sieht aus wie bisher — zwei Reiter „Standorte" und
  „Rettungsmittel", je Standort eine zugeklappte Karte. Das ist **kein
  Fehler**, sondern der Stand: Die Umstellung auf Liste und Seite steht in
  Teil 4, zusammen mit den Dialogen, weil beide dasselbe Markup anfassen.
  *Scheitern erkennbar an:* Ein Verweis von dort führt auf eine Seite, die es
  nicht gibt, oder ein Formular landet nach dem Speichern im Nichts. Die
  Kontoseite und die Verwaltungsseite teilen sich zwei Bausteine
  (`sd_zeile()`, `sd_form()`); wenn dort etwas kaputtgegangen ist, dann hier.

- [ ] **29 — Die Sprungliste und der Filter am Gerät (AP5, Teil 3).**
  *Weg:* An einem Standort mit **sechs oder mehr** Rettungsmitteln:
  Einstellungen → Standorte → Standort öffnen. Eine Marke der Reihe über der
  Liste antippen. Danach in der **Besatzung** ins Filterfeld tippen (mit
  Handschuh), erst einen Namensteil, der trifft, dann einen, der nicht
  trifft; wieder leeren (Kreuz rechts oder Esc). Am Handy **und** am
  Schreibtisch.
  *Erwartet:* Der Tipp auf die Marke springt zur Zeile; die Zeile ist orange
  hinterlegt und steht **unter** der Kopfleiste, nicht dahinter. Beim Filtern
  bleiben nur passende Zeilen; eine Rollenüberschrift ohne Treffer
  verschwindet mit, und die **Hinzufügen-Formulare sind währenddessen
  weg**. Bleibt nichts übrig, steht ein Satz da, der sagt, wie man wieder
  zum Anlegen kommt. Nach dem Leeren ist alles zurück.
  *Scheitern erkennbar an:* Die angesprungene Zeile sitzt **hinter** der
  Kopfleiste (dann fehlt `scroll-padding-top`, oder jemand hat ein
  `scroll-margin-top` nachgerüstet und beides addiert sich). Oder eine
  Rollenüberschrift steht ohne Zeilen da — dann sieht die Karte leer aus,
  obwohl sie nur gefiltert ist. Oder das Feld ist am Handschuh zu niedrig:
  gemessen sind **44 px am Finger, 36 px am Zeigergerät ab 1024 px**, aber
  gemessen ist nicht getroffen. **Der Filter ist am Handy der wichtigere von
  beiden** — dort ist die Liste am längsten.

- [ ] **30 — Die Sprungliste an einem echten Bestand (AP5, Teil 3).**
  *Weg:* An einem Standort mit **zwölf oder mehr** Rettungsmitteln — also an
  einem, den der Prüfstand nicht hat. Die Standortseite bei 360 px öffnen.
  *Erwartet:* Die Marken brechen über mehrere Zeilen um und bleiben lesbar;
  die Karte läuft nicht seitlich über.
  *Scheitern erkennbar an:* Waagerechtes Rollen der Seite, oder eine Marke,
  deren Name abgeschnitten ist. **Gemessen ist die Reihe nur mit sechs
  Marken** (der Prüfstand hat drei Rettungsmittel je Standort, die sechste
  wurde für den Prüflauf angelegt und wieder gelöscht); zwölf sind
  gerechnet, nicht gesehen.

- [ ] **31 — Die Standortseite der Verwaltung an einem echten Bestand (AP5, Teil 4).**
  *Weg:* Als Administratorin `admin_stammdaten.php` öffnen, einen
  systemweiten Standort anlegen, ein luftgebundenes Rettungsmittel mit
  Rollen, eine Zielklinik mit Lage, eine Besatzungs-Vorbelegung und eine
  Bergwacht-Bereitschaft eintragen. Danach bei **360 px** und am
  Schreibtisch ansehen.
  *Erwartet:* Sechs Karten, drei Kennzahlen, „Zum Anfang" je Karte; jeder
  Eintrag landet nach dem Speichern auf seiner eigenen, orange
  hervorgehobenen Zeile; die Kleinzeile der Liste nennt, wie viele Konten
  den Standort gewählt haben.
  *Scheitern erkennbar an:* Eine Karte fehlt, ein Dialog öffnet leer, oder
  die Seite springt nach dem Speichern auf die Liste statt auf die Zeile.
  **Diese Seite ist nicht fotografiert** — der Referenzbestand hat keinen
  systemweiten Standort (Abschnitt 0, Backlog Nr. 166). Gemessen ist sie
  nur über die Klickprobe, die den Fall selbst herstellt und wieder abräumt.

- [ ] **32 — Ein Dialog mit vielen Rollen am kleinen Gerät (AP5, Teil 4).**
  *Weg:* Am Handy die Standortseite öffnen, „Anlegen" in der Karte
  Rettungsmittel, Betriebsart **luftgebunden** wählen — dann stehen fünf
  Rollen- und zwei Fähigkeitshaken im Dialog. Bis zum Fuß rollen und
  speichern.
  *Erwartet:* Kopf und Fuß bleiben stehen, der Inhalt rollt, „Anlegen" ist
  jederzeit erreichbar. Gemessen bei 390 × 780 px: Dialog 756 px, Fuß bei
  768 px, Rollweg 279 px.
  *Scheitern erkennbar an:* Der Fuß ist nicht zu sehen oder rollt aus dem
  Bild; die Seite hinter dem Dialog rollt statt des Dialoginhalts.
  **Ein echtes Gerät kann hier mehr sagen als der emulierte Ausschnitt** —
  die Adressleiste mobiler Browser klappt beim Rollen ein und aus, und
  `100dvh` rechnet das mit.

- [ ] **33 — Ein Rettungsmittel von einem Standort auf einen anderen ziehen (AP5, Teil 4).**
  *Weg:* Ein Rettungsmittel vom Typ **Bergwacht**, **Veranstaltung** oder
  **Sonstiges** bearbeiten und im Feld „Standort" einen anderen Standort
  wählen (oder „Ohne Standort").
  *Erwartet:* Es steht danach auf der Seite des neuen Standorts — bzw. in
  der Karte „Ohne Standort" auf der Liste —, und der Sprung führt dorthin.
  Bereits dokumentierte Diensttage bleiben unverändert.
  *Scheitern erkennbar an:* Es bleibt, wo es war, oder es verschwindet aus
  beiden Listen. **Das ging bis Web 16.3.0 gar nicht** — der Haken „Ohne
  Standort" konnte nur zwischen „dieser Standort" und „keiner" wechseln.

- [ ] **34 — Einen Standort mit Rettungsmitteln ohne Standortpflicht löschen (AP5, Teil 5).**
  *Weg:* Einen Standort wählen, an dem **beides** hängt — mindestens ein
  Rettungsmittel vom Typ Standard und eines vom Typ Bergwacht,
  Veranstaltung oder Sonstiges. Auf seiner Seite „Löschen" wählen und die
  Rückfrage **lesen**, bevor du bestätigst.
  *Erwartet:* Die Rückfrage nennt zwei Zahlen — wie viele mitgehen und wie
  viele bleiben — und die bleibenden **mit Namen**. Nach dem Bestätigen
  öffnet sich die Karte „Ohne Standort" von selbst, und die überlebende
  Zeile ist orange hinterlegt. Kurzname, Betriebsart und Fähigkeiten sind
  unverändert; die Vorbelegung (Stern) hängt weiter am Rettungsmittel, wenn
  sie dort hing.
  *Scheitern erkennbar an:* Die Rückfrage nennt nur eine Zahl; oder das
  Rettungsmittel ist nach dem Löschen fort. **Bereits dokumentierte
  Diensttage müssen in jedem Fall unverändert bleiben** — das ist die
  wichtigere Hälfte der Prüfung und mit einem Blick in die Diensttage-Leiste
  zu sehen.

- [ ] **19 — Nach dem Kurznamen suchen (AP4).**
  *Weg:* Suche öffnen, den **Kurznamen** eines Rettungsmittels eintippen, das
  an mindestens einem Diensttag hängt.
  *Erwartet:* Die Einsätze dieses Diensttags erscheinen.
  *Scheitern erkennbar an:* Kein Treffer, obwohl die volle Bezeichnung
  trifft — dann fehlt `vehicle_kurz` im Suchindex.

- [ ] **20 — `update.php` nach dem Deploy (AP4).**
  *Weg:* Nach dem Ausrollen als BetreiberIn Verwaltung → Updates öffnen und
  die Migration `2026_09_07_rettungsmittel_typ` ausführen.
  *Erwartet:* „Erfolgreich angewendet"; danach zeigt die Rettungsmittel-Seite
  bei jedem Eintrag das Feld **Typ** mit „Standard".
  *Scheitern erkennbar an:* Die Seite meldet einen Datenbankfehler, oder das
  Feld „Typ" fehlt — dann ist die Migration nicht gelaufen, und die Anwendung
  schreibt in Spalten, die es nicht gibt. **Ohne diesen Aufruf läuft die
  Anwendung ins Leere.**

- [ ] **21 — Ein Backup aus Web 15.9.0 einspielen (AP4).**
  *Weg:* Eine `.edbak`-Datei aus einem Stand **vor** 16.0.0 in ein frisches
  Konto einspielen.
  *Erwartet:* Alle Rettungsmittel kommen an und tragen Typ **Standard**;
  der Bericht nennt keine übersprungenen Stammdaten.
  *Scheitern erkennbar an:* Rettungsmittel fehlen (dann greift die
  Standortprüfung zu streng), oder der Import bricht ab.

- [ ] **22 — Die Nachbearbeitung auf einer Installation, die A12 nie
  abgeschlossen hat (AP4).**
  *Weg:* Nur wenn es eine solche Installation gibt: „Zuordnung offen" in der
  Seitenleiste öffnen.
  *Erwartet:* Der Abschnitt „Eigene Einträge ohne Standort" zeigt weiterhin
  offene Zielkliniken, Besatzungs-Vorbelegungen, weitere Rettungsmittel und
  Bergwacht-Bereitschaften — Rettungsmittel nur noch, wenn ihr Typ
  „Standard" ist. Die Rückfrage vor dem Knopf nennt **vier** Tabellen.
  *Scheitern erkennbar an:* Die Rückfrage nennt fünf Tabellen, oder ein
  Bergwacht-Rettungsmittel ohne Standort steht als offener Punkt — beides
  hieße, die Entkopplung ist unvollständig.

---

## 4. Fragen an den Auftraggeber

Stellen, an denen Konzept und Auftrag einander widersprechen oder das Konzept
schweigt. Jede ist vorläufig entschieden **und** revidierbar; der Preis einer
Gegenentscheidung steht dabei.

### Neu aus AP5 Teil 4 und 5 (09.09.2026)

**Frage 8 — Geht ein fremdes Rettungsmittel mit, wenn die Verwaltung einen
systemweiten Standort löscht?** Variante b (M-S9-10) rettet die
Rettungsmittel ohne Standortpflicht, und zwar auf beiden Seiten: Im Konto die
eigenen, in der Verwaltung die systemweiten. **Nicht gerettet werden die
Rettungsmittel einzelner NutzerInnen, die an einem systemweiten Standort
hängen** — sie gehen mit ihm, auch wenn ihr Typ keinen Standort braucht.

Das ist kein Versehen, sondern ein Zuschnitt: Die Rückfrage der Verwaltung
zählt seit jeher **nur** den systemweiten Bestand („5 systemweite
Stammdatensätze werden mitgelöscht") und sagt daneben, wie viele Konten den
Standort gewählt haben. Sie könnte einen Satz über fremde Rettungsmittel
nicht belegen, ohne vorher etwas zu zählen, was sie sonst nirgends zählt —
und dann stünde in der Rückfrage einer Administratorin, wie viele
Rettungsmittel fremder Konten sie gerade löscht, was eine neue Auskunft über
fremde Bestände wäre.

*Drei Wege:* **(a)** so lassen, wie es ist, und den Satz ins Handbuch
schreiben. **(b)** Auch die fremden retten, ohne sie zu zählen — die
Rückfrage bliebe, wie sie ist, und die Rettung geschähe still. **(c)** Auch
die fremden retten **und** in der Rückfrage nennen („… und 3 Rettungsmittel
in 2 Konten bleiben bestehen").
*Empfehlung: **(b)*** — der Grund für die Rettung ist derselbe (der Typ
braucht keinen Standort), und wer den Standort löscht, hat kein Interesse
daran, fremde Rettungsmittel mitzunehmen. Die zusätzliche Auskunft aus (c)
gehört nicht in eine Löschrückfrage.
*Wenn (a):* Der Satz steht im Handbuch 9.4 und in der Löschrückfrage der
Verwaltung — heute steht er in keiner von beiden.

**Frage 9 — Bekommt der Referenzbestand einen systemweiten Standort?** Ohne
einen ist die Standortseite der Verwaltung nicht zu fotografieren (Abschnitt
0, Backlog Nr. 166). *Empfehlung: ja* — einer mit einem Rettungsmittel, einer
Zielklinik und einer Besatzungs-Vorbelegung genügt, und er deckt zugleich die
Anzeige „systemweit" in der Kontoansicht ab, die heute an keiner Stelle
gemessen ist. Das berührt Fixture, Prüfsummen und die beiden Kreisläufe
(`edbak`, `csv`) und gehört deshalb in ein eigenes Paket.

### Neu aus AP5 (08.09.2026)

**Frage 7 — Die Standortseite hat sechs Karten, nicht vier. Bleibt es
dabei?**
*Das Konzept zählt vier auf, die Anwendung hat sechs Listen.* Konzept AP5 und
Backlog Nr. 152 nennen „vier Karten (Standort, Rettungsmittel, Besatzung,
Zielkliniken)", und die Abnahme verlangt „Unterpunkte der Leiste = **vier**".
Am Standort hängen aber **sechs** Stammdatenlisten: dazu **Weitere
Rettungsmittel** (Vorschläge für das Feld „Weitere Rettungsmittel" im Einsatz)
und **Bergwacht** (Bereitschaften; erscheint nur an einem Standort mit
luftgebundenem Rettungsmittel, E29).

**Entschieden am 08.09.2026 vom Auftraggeber: es bleibt bei sechs Karten**
(fünf ohne luftgebundenes Rettungsmittel), **und die Abnahmezahl ist
berichtigt.** Zwei Listen wegzulassen hieße nicht, sie loszuwerden — sie
tragen Daten und wären von der Seite aus nicht mehr erreichbar; die einzige
Alternative wäre, sie in eine der vier hineinzufalten, und das ist eine neue
Darstellung mit Mockup. Gemessen sind **6 Karten / 6 Unterpunkte** an einem
Standort mit Luft.

*Nachgezogen sind vier Stellen:* Konzept AP5 (die Beschreibung der
Standortseite in Abschnitt 3.8 **und** die Aufzählung im Arbeitspaket), die
**Abnahme** desselben Pakets („Unterpunkte der Leiste = sechs an einem
Standort mit luftgebundenem Rettungsmittel und fünf ohne, drei auf der
Liste"), und **Backlog Nr. 152**. Ohne diese Berichtigung hätte AP5 am Ende
gegen eine Zahl gemessen, die nie stimmen konnte.

*Drei kleinere Fragen aus AP5 kommen mit den Teilen 3 und 4* und stehen hier
nur, damit sie nicht verlorengehen: (a) Bekommt „Standort anlegen" einen
eigenen Dialog oder eine eigene Seite — das Konzept sagt es nicht; (b) das
Sprungziel der Rettungsmittel-Pille soll ein Artzeichen tragen — reicht dafür
`ohne-zuordnung.svg` aus dem Vorrat, oder wird es ein 53. Symbol; (c)
`Design.md` 9.25 schreibt für Karten-Kennungen das Präfix `k-` vor, Konzept
und Mockups schreiben `#standort`, `#rettungsmittel` ohne Präfix — die Seite
folgt bisher den Mockups.

### Neu aus AP3

**Frage 6 — `veranstaltung.svg` bei 18 px mit Strich 2: lesbar genug?**
*Beide Mockups zeichnen mit einem dünneren Strich als die Anwendung.*
`M-S9-01` und `M-S9-02` setzen `stroke-width:1.8`; die Anwendung liefert **2**
(`style.css`, und jede der 52 SVG-Dateien). Die freigegebenen Bilder sind
damit rund **11 % dünner gestrichen** als das, was ausgeliefert wird — und
zwar genau auf den Symbolen, die AP3 von 20 auf **18 px** verkleinert.

Das trifft die eine Stelle, an der das Mockup seiner eigenen Freigabe
widerspricht: Anmerkung 4 von M-S9-02 empfiehlt für „Veranstaltung" das
Zeichen **„ticket"** und nennt „building-stadium" bei 20 px „einen Klumpen";
die Freigabe hat trotzdem building-stadium gewählt.

*Gebaut ist:* **building-stadium**, wie freigegeben. Angesehen bei 18 und
20 px mit Strich 2 (Bild im Bericht): Fünf der sechs Zeichen tragen klar;
`veranstaltung` ist das schwächste — die vier Pfade (Oval, zwei Türme,
Körper) laufen bei 18 px ineinander. Erkennbar bleibt es als eigene Form,
aber es ist nicht so ruhig wie die anderen fünf.
*Preis einer Gegenentscheidung:* eine Datei tauschen (Tabler „ticket"), eine
Zeile in `dt_typ_symbole()`, drei Zeilen Dokumentation. **Fällig erst mit
AP4** — vorher erreicht kein Bedienweg das Zeichen.

**Entschieden vom Auftraggeber am 07.09.2026: Tabler „ticket".** Getauscht
mit AP4 (Web 16.0.0): eine Datei (`veranstaltung.svg`, vier Zeilen), der
Dateiname und damit der Aufruf unverändert; Herkunft in `Design.md` 8 und
`Lizenzen.md` nachgezogen. Messung dazu im Changelog: „ticket" hält seine
Binnenfläche von 96 px bis 16 px unverändert, „building-stadium" verliert
bei 18 px zwei seiner vier Binnenflächen auf einen einzelnen Pixel und
schließt bei 16 px zwei ganz. Damit weicht die Umsetzung an dieser Stelle
von der Freigabe von M-S9-02 ab — bewusst, mit Zahl, und im Konzept
(E-S9-13, Abschnitt 6) vermerkt.

**Frage 7 — Der Umfang von E-S9-03 war größer als seine Aufzählung.**
**Entschieden vom Auftraggeber am 07.09.2026: alles Sichtbare.** Das Konzept
sagt „Überall, wo die NutzerIn liest" und zählt dann fünf Stellen auf;
gemessen waren es **72 sichtbare Zeichenketten in 18 Dateien** — dazu Betrieb
→ Hintergrundjobs, der Sicherungs- und Importweg, Komplettsicherung,
Admin-Demo und mehrere Fehlermeldungen. Alle sind umbenannt.

Für die **Android-App** (fünf sichtbare Texte) hat der Auftraggeber
entschieden: **Schritt 9a nimmt sie mit**, weil er ohnehin an der App
arbeitet. Der Handzettel dafür steht in Abschnitt 6.

### Aus AP2

**Frage 4 — Pfeile auf der Spur im Kartendialog: ja oder nein?**
**Entschieden vom Auftraggeber am 07.09.2026: nein.** Der gebaute Stand
bleibt, es ändert sich keine Zeile; die Sollzahl der Klickprobe (**0 Pfeile**)
gilt weiter. Damit steht auch fest, wie der Widerspruch zu lesen ist: Der
Konzepttext geht vor, die Anmerkung 3 des Mockups ist an dieser Stelle
überholt.

*Zwei Stellen der freigegebenen Unterlage sagen Verschiedenes.* Der
Konzepttext ist eindeutig: E-S9-06 (b) verlangt „Linie in der ersten
Spurfarbe, Ringpunkte für Start und Ende (`EdGeo.markerRing`), **keine
Pfeile, keine Luftlinie**". Die Anmerkung 3 des Mockups M-S9-04 sagt das
Gegenteil: „Spur: … erste Spurfarbe, **Pfeile wie in der Einsatzansicht**
(nach Nr. 72 gedreht), Ringpunkt blau = Start, rot = Ende", und das Bild des
Zustands D zeichnet tatsächlich einen Pfeil.

*Gebaut ist:* **ohne Pfeile** — dem Konzepttext gefolgt, weil er die
normative Stelle ist; die Zeile „Zur Freigabe" des Mockups nennt die Pfeile
nicht mit. Die Klickprobe misst es (`ap2-spur-im-dialog`: **0 Pfeile**), das
heißt eine Gegenentscheidung ändert auch diese Erwartung.

*Preis der Gegenentscheidung:* klein — `EdGeo.pfeile(karte, gruppe, spur)`
existiert und wird in der Einsatzansicht so aufgerufen; es wären zwei Zeilen
in `ortswahl.js`, eine Zeile in der Legende, eine geänderte Sollzahl im Weg
und ein Satz in `Design.md` 9.29. *Meine Empfehlung:* **ohne Pfeile lassen.**
Im Auswahldialog wird ein Punkt gewählt, keine Fahrt gelesen; die Pfeile
liegen auf der Linie und damit genau dort, wo das Fadenkreuz hin soll.

**Frage 5 — Der Kopf des Kartendialogs ist jetzt eine Überschrift.**
**Entschieden vom Auftraggeber am 07.09.2026: lassen.** Der gebaute Stand
bleibt.

*Das Konzept schweigt, das Mockup zeigt es so.* Der Dialog trug seinen Titel
seit Web 9.4.0 als **nackten Text** (`<div class="dialog-kopf">Auf der Karte
wählen</div>`) — als einziger Dialog der Anwendung; alle übrigen schreiben
`<div class="dialog-kopf"><h2>…</h2></div>`. Das Mockup M-S9-04 zeigt eine
Überschrift, und weil das Suchfeld ohnehin in denselben Kopf einzieht, ist
der Titel mit umgestellt worden.

*Was sich sichtbar ändert:* Der Titel steht jetzt in der Kopfschrift und in
`--groesse-6` statt in der Fließtextgröße — wie in jedem anderen Dialog. Das
Mockup setzt lokal `--groesse-5`; das ist eine Näherung im Mockup und nicht
die Regel des Stylesheets, deshalb ist ihr nicht gefolgt worden.
*Preis der Gegenentscheidung:* eine Zeile zurück. **Meine Empfehlung:**
so lassen — ein Dialog, der als einziger keine Überschrift hat, ist ein
Ausreißer, kein Entwurf.

### Aus AP1

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
**Entschieden vom Auftraggeber am 08.09.2026: so lassen, wie AP1 gebaut
hat.** Es ändert sich keine Zeile — weder im Code noch in den Hilfslisten.

Die Streichliste (`tools/vollstaendigkeit/streichliste.md`) führt **Klassen
des alten Stylesheets**; `datalist` ist ein HTML-Element, stand nie in
`vorher-klassen.txt` und ist nie eine Klasse gewesen. Ein Eintrag dort wäre
eine tote Zeile, und die Prüfung meldet tote Zeilen.
*Entschieden:* Auf die Streichliste kommen die **vier Klassen**, die
tatsächlich verschwinden — `loc-suggest`, `rmlist`, `rmopt`, `rmneu` —, jede
mit Grund und Paket; `rmneu` fällt zugleich aus `ohne-regel.md`, wo es als
`[offen]` stand. Der Ausbau der `<datalist>` ist statt dessen mit einer Zeile
in `Design.md` 9.0 („nimm X, nicht Y") und im Baustein 9.28 festgehalten,
der ausdrücklich sagt, er ersetze auch **jede** native `<datalist>` — mit
dem Grund aus Backlog Nr. 68. Gemessen wird der Ausbau unabhängig davon
durch die Abnahmezahl von AP1: `grep -c datalist server/` = **0** außerhalb
von Kommentaren, vorher **12** Treffer in sechs Dateien.
*Verworfen wurden dabei:* eine Zeile „datalist" trotzdem eintragen (dann
meldet die Vollständigkeit eine tote Zeile und braucht eine Ausnahme, die
nichts prüft), und ein zweiter Abschnitt „Nicht-Klassen" in der
Streichliste, der nicht gegengezählt wird (ehrlich, kostet aber eine
Änderung am Prüfskript für einen einzigen Eintrag).

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

### Aus AP4 (07.09.2026)

**Frage 3 — „Kacheln und Plaketten zeigen den Kurznamen" hat keine Stelle.**
E-S9-09 nennt drei Orte für den Kurznamen: Leiste, Kacheln, Plaketten.
Umgesetzt ist die **Leiste**. Die beiden anderen gibt es nicht: Die
Einsatzkachel (`EdMissionTable.kachel()`) zeigt Zeit, Artzeichen, Ort,
Diagnose, Dauer, Alter und Plaketten — **nie** einen Rettungsmittelnamen; und
es gibt keine Plakette, die ein Rettungsmittel benennt. Dort etwas einzufügen
wäre eine **neue Darstellung** und braucht nach `Design.md` ein Mockup.
*Zur Entscheidung:* (a) so lassen — der Kurzname gilt für die Leiste; (b) in
AP5 ein Mockup für eine Rettungsmittel-Angabe an der Kachel; (c) Backlog
Nr. 69 auf den Rest zurückschneiden.

**Entschieden vom Auftraggeber am 08.09.2026: (a) so lassen** — für 3 a
**und** 3 b. Die Einsatzkachel bekommt keinen Rettungsmittelnamen (weder
als Plakette im Fuß noch als zweite Zeile der Zeitspalte), die
Plakettenzeile der Einsatzansicht behält die volle Bezeichnung. Am
gebauten Stand (Web 16.0.0) ändert sich **keine Zeile**. Folge für die
Buchführung: E-S9-09 ist auf die Leiste zurückgeschnitten (Konzept), und
Backlog Nr. 69 wandert im Abschluss mit diesem Rest nach *Erledigt* — der
Teil „Kacheln und Plaketten" ist nicht offen, sondern abgelehnt.
*Preis:* Wer die Suche nach einem Rettungsmittel filtert, sieht am
Trefferbild weiterhin nicht, welches gefahren wurde; die Auskunft steht
einen Klick weiter in der Einsatzansicht.

*Mockup M-S9-08 (Fable, 07.09.2026), Rahmen „Frage 3 a" und „Frage 3 b":*
Die Einsatzkachel bei 390 px im Ist und in zwei Varianten — **A** Kurzname
als neutrale Plakette am Anfang des Fußes (ohne Kurznamen die volle
Bezeichnung), **B** Kurzname als zweite Zeile der Zeitspalte (12 px,
gedämpft, `max-width: 9em`; ohne Kurznamen bleibt die Zeile leer). Gemessen
am Render: Kachel **Ist 92 · A 124 · B 92 px** — A wächst um ein Drittel,
weil der Fuß schon bei „BW Hoch" umbricht (20 → 53 px); B bleibt in der
Höhe, die Ort und Diagnose ohnehin vorgeben. Regel in beiden Varianten: nur
wo `artDatum` steht (Suche, Zeitraum), nie auf der Tagesübersicht, die ein
Rettungsmittel in der Titelzeile nennt. Dazu **3 b**, die Plakettenzeile der
Einsatzansicht bei 700 und 358 px — die einzige Stelle, an der ein
Rettungsmittel heute in einer Plakettenzeile steht: Der Kurzname spart bei
358 px eine Zeile (drei → zwei), bei 700 px nichts.
*Empfehlung:* 3 a **offen** — A, B oder Ist, jeweils mit der Regel „nur wo
`artDatum`"; 3 b **nein**, die Einsatzansicht ist ein Lesezustand ohne
Platzdruck, dort gehört die volle Bezeichnung hin. Zu (c): Nr. 69 wird mit
der Antwort auf 3 a zurückgeschnitten oder nicht — vorher nicht.

**Frage 4 — Der Kurzname hilft erst ab 1200 px.** Seine Begründung ist der
knappe Platz; `.eintrag-neben` ist aber unter 1200 px ausgeblendet
(`style.css`, mit eigener Begründung dort). Am Handy — wo der Platz am
knappsten ist — steht er also gar nicht. *Zur Entscheidung:* (a) so lassen,
der Kurzname ist eine Schreibtisch-Hilfe; (b) den Nebentext schmal sichtbar
machen, wenn ein Kurzname da ist (Gestaltungsänderung, Mockup nötig).

*Mockup M-S9-08, Rahmen „Frage 4":* Dieselbe Leiste (Januar 2026, fünf
Diensttage, der erste fährt „Bergwacht Hochkreuth" mit Kurzname „BW Hoch")
in fünf Rahmen: 260 px Ist, 220 px Ist, 220 px **Variante 1** (nur
`.eintrag-neben.kurz` bleibt sichtbar), 220 px **Variante 2** (dazu das
Akkordeon unter 1200 px je Ebene 8 statt 12 px eingerückt), Schublade
320 px. Gemessen am Render, 13 px Open Sans: „BW Hoch" braucht **55 px**;
frei neben dem Datum sind bei 260 px **55**, bei 220 px in Variante 1 **51**
(→ „BW Ho…"), in Variante 2 **55** (passt), in der Schublade **55** px. Ein
16-Zeichen-Kurzname („Sanitätsdienst S", 95 px) ellipsiert in jeder der
Leisten — auch bei 260 px, also auch heute. Preis von Variante 1 und 2: die
Regel in `style.css` („Bei 220 px Leistenbreite bliebe von ihm ohnehin nur
eine Ellipse") wird für Kurznamen eingelöst statt widerlegt — sie gilt
weiter für volle Namen; wer keinen Kurznamen vergibt, sieht die Leiste wie
heute.
*Empfehlung:* **Variante 2** — Variante 1 scheitert gemessen an vier Pixeln.

**Entschieden vom Auftraggeber am 08.09.2026: Variante 2.** Der Kurzname
bleibt unter 1200 px sichtbar, das Akkordeon rückt dort je Ebene 8 statt
12 px ein. **Gebaut mit Web 16.1.0** (S9/AP4a) — mit zwei Berichtigungen:
Der Nebentext war nur im Band **1024–1199 px** ausgeblendet, nicht „unter
1200 px" (F-S9-U-09); und an einem Diensttag, der sich sein Datum teilt,
bleibt er auch weiterhin aus, weil dort Datum und Uhrzeit stehen und dem
Kurznamen 3 px von 55 blieben (F-S9-U-10). Prüflistenpunkt 18 ist
entsprechend umgestellt.

**Frage 5 — Sollen Diensttage verschiedenen Typs zusammenführbar sein?**
`dt_merge_pruefen()` prüft heute nur die **Betriebsart**. Zwei Tage, von denen
einer Bergwacht und einer Standard ist, lassen sich zusammenführen; der
Zieltag bekommt den Typ des Gewinners. Das ist nicht falsch, aber es ist auch
nicht entschieden. *Zur Entscheidung:* (a) so lassen; (b) den Typ wie die
Betriebsart prüfen und bei Abweichung ablehnen; (c) ihn in die
Widerspruchsliste des Vergleichsdialogs aufnehmen, damit die NutzerIn wählt.

*Mockup M-S9-09 (Fable, 07.09.2026):* Beide Schritte des Zusammenführens bei
390 px, je Ist gegen Variante. **Schritt 1** (Kandidaten): Ist — der
Bergwacht-Tag steht wählbar da, ohne dass sein Typ genannt wird; **(b)** —
er wandert nach „Nicht wählbar", mit demselben Satzbau und derselben roten
Plakette, die heute die Betriebsart benutzt. **Schritt 2** (Vorschau): Ist —
„Der Diensttag danach" nennt die Art, nicht den Typ, und der Widerspruch
„Rettungsmittel" steht schon heute da, sobald zwei Rettungsmittel
aufeinandertreffen — der Typ folgt still dem Gewinner; **(c)** — eine Zeile
„Typ" in der Vorschau (Plakette blau wie „Art", Kleinzeile „folgt dem
gewählten Rettungsmittel") und Typ samt Kurzname als `wahl-zusatz` in den
beiden Wahlzeilen. Kein neuer Baustein, kein neuer Schritt; unter 480 px
rutscht der Zusatz unter den Text (Regel aus `style.css`, in der
Handy-Fassung nachgestellt: bei 400 px **0 Elemente über dem Rand**, vor
Übernahme der Regel 1 mit 46 px). Was (b) kostet: den Fall, für den das
Zusammenführen gebaut ist — zwei angelegte Tage, wo einer gemeint war —,
für Dienste zu verbieten, die morgens Alpenfalke 1 und nachmittags die
Bergwacht-Bereitschaft gefahren haben; die Begründung der Betriebsart
(andere Rollen, andere Felder) trägt hier nicht, denn Rollensatz und
Fähigkeiten hängen am Diensttag, und die Wahl regelt sie bereits.
*Empfehlung:* **(c)** — der Typ ist eine Eigenschaft des Rettungsmittels, die
Wahl des Rettungsmittels gibt es schon, und die Auskunft kostet eine Zeile
und einen Zusatz.

**Entschieden vom Auftraggeber am 08.09.2026: (c).** Diensttage
verschiedenen Typs bleiben zusammenführbar; `dt_merge_pruefen()` bleibt
unverändert. Neu sind die Zeile „Typ" in der Vorschau und der Typ samt
Kurzname als Zusatz der beiden Wahlzeilen. **Gebaut mit Web 16.1.0**
(S9/AP4a) — die Zeile nennt allerdings **beide** Typen, solange zwei
Rettungsmittel zur Wahl stehen (F-S9-U-11).

**Frage 6 — Der Fremdschlüssel auf den Standort.** AP4 hat
`ON DELETE CASCADE` bewusst **unverändert** gelassen: Wer einen Standort
löscht, löscht seine Rettungsmittel weiterhin mit — auch die vom Typ
Bergwacht, die ohne Standort bestehen dürften. `ON DELETE SET NULL` würde sie
stattdessen standortlos machen, aber ebenso jedes Standard-Rettungsmittel —
also einen Zustand herstellen, den die Prüfschicht nie anlegen würde.
*Zur Entscheidung:* (a) so lassen (Fassung von AP4); (b) beim Löschen eines
Standorts die Rettungsmittel ohne Standortpflicht behalten und nur die
übrigen mitnehmen — das braucht Anwendungslogik statt eines Fremdschlüssels
und gehört dann in AP5 zur Standortseite.

*Mockup M-S9-10 (Fable, 07.09.2026):* Der Rückfragedialog (`confirm.js`,
`role="alertdialog"`) bei 512 und 358 px, Ist gegen **(b)** — derselbe
Baustein, dieselben Knöpfe, ein Satz und eine Zahl anders: „6 eigene
Stammdatensätze … werden mitgelöscht" wird zu „5 … werden mitgelöscht.
1 Rettungsmittel ohne Standortpflicht — Bergwacht Hochkreuth — bleibt
bestehen und steht danach unter „Ohne Standort"." Danach die Karte „Ohne
Standort" mit 2 (a) gegen 3 Einträgen (b), der neue trägt `:target` wie
nach dem Anlegen (M-S9-07). Technisch ist (b) keine Fremdschlüssel-Frage:
`vehicles.base_id → bases` bleibt `ON DELETE CASCADE`; vor dem `DELETE FROM
bases` läuft in derselben Transaktion `UPDATE vehicles SET base_id = NULL
WHERE base_id = ? AND typ <> 'standard'` — genau die Rettungsmittel, für
die `VEHICLE_TYPEN[typ]['standort']` falsch ist —, als eine Funktion neben
`pruef_rettungsmittel()`, gerufen von beiden Stellen (Konto, Verwaltung).
Das Rettungsmittel behält Kurznamen, Betriebsart, Fähigkeiten und seine
Diensttage; es verliert die Vorschlagslisten, die es über den Standort
hatte, und die Vorbelegung (`user_defaults`) geht mit dem Standort — wie
heute. Preis: Ein Rettungsmittel überlebt das Löschen seines Standorts
anders als seine Nachbarn; deshalb sagt die Rückfrage es vorher, mit Namen.
*Empfehlung:* **(b)**, Umsetzung in **AP5** mit der Standortseite, wo das
Löschen ohnehin neu verdrahtet wird (E-S9-18, Aktionsmenü). Wer (a) wählt,
lässt AP4 stehen; dann sollte die Rückfrage wenigstens sagen, dass auch die
Rettungsmittel ohne Standortpflicht mitgehen.

**Entschieden vom Auftraggeber am 08.09.2026: (b).** Rettungsmittel ohne
Standortpflicht überleben das Löschen ihres Standorts und werden in der
Rückfrage mit Namen genannt. Der Fremdschlüssel bleibt `ON DELETE CASCADE`;
die Ausnahme ist Anwendungslogik vor dem `DELETE`, in derselben
Transaktion, an **beiden** Löschwegen (Konto und Verwaltung). **Noch nicht
gebaut** — Umsetzung in AP5 mit der Standortseite. Bis dahin gilt der Stand
von Web 16.0.0: Wer einen Standort löscht, verliert auch seine
Bergwacht-Rettungsmittel.

---

## 5. Abweichungen von den Mockups

Wo Mockup und Umsetzung auseinandergehen, steht es hier (Konzept, Abschnitt 6:
„die Abweichung wird im Prüfdokument genannt").

| Stelle | Mockup / Konzept | Umsetzung | Grund |
|---|---|---|---|
| **Konzept AP5 / Backlog 152:** „vier Karten" auf der Standortseite | Standort, Rettungsmittel, Besatzung, Zielkliniken — und die Abnahme verlangt „Unterpunkte der Leiste = **vier**" | **sechs** Karten: dazu **Weitere Rettungsmittel** und **Bergwacht** (letztere nur an einem Standort mit luftgebundenem Rettungsmittel, E29 — dort sind es fünf) | Es gibt **sechs** Stammdatenlisten am Standort, nicht vier (`stammdaten_ui.php` nennt sie im Kopf). Zwei davon in der Aufzählung wegzulassen heißt nicht, sie loszuwerden: Sie tragen Daten, sie hängen an `base_id`, und ohne Karte wären sie von der Seite aus **nicht erreichbar**. Das Konzept zählt an dieser Stelle auf, was neu gestaltet wird, und übergeht die zwei, an denen sich nichts ändert. **Am 08.09.2026 vom Auftraggeber entschieden: es bleibt bei sechs, und die Abnahmezahl ist berichtigt** — in Konzept AP5 (zwei Stellen und die Abnahme) und in Backlog Nr. 152. Die Zeile bleibt hier stehen, weil das Konzept den Widerspruch trug, nicht die Umsetzung |
| **M-S9-05/-06:** `.sprungziel.ziel` | ein eigener Klassenname für den Zielzustand | **`.sprungziel.aktiv`** | `.aktiv` ist in dieser Anwendung seit Langem das Wort für „hier stehst du" — Kopfleiste, Leiste, Kennzahl, Listenfilter, Blattzeile und Seitenknopf tragen es, und `.kennzahl.aktiv` ist **Zeichen für Zeichen dieselbe Deklaration**. M-S9-07 benutzt im selben Bedienweg beide Namen nebeneinander (`.kennzahl.aktiv` für die Kachel, `.sprungziel.ziel` für die Pille) |
| **M-S9-06:** `.filterfeld` | so heißt das neue Feld | **`.kartenfilter`** | `.filterfelder` (Mehrzahl) ist seit P3 vergeben — der Innenabstand einer aufgeklappten Filtergruppe der Suchseite. Zwei Klassen, die sich um ein „r" unterscheiden und Verschiedenes meinen, sind derselbe Fehler, den `.listenfilter-zahl` einmal ausdrücklich umgangen hat |
| **M-S9-06:** Lupe mit `top:12px` | fester Abstand von oben | **kein `top`**, der Behälter zentriert (`align-items:center`) | Gerechnet: Bei 44 px Feldhöhe ist (44−20)/2 = 12, also mittig. Im Zeigerband desselben Mockups gilt aber `--knopf: 36px`, und dort wäre es 8 — die Lupe säße **4 px zu tief**. Der Bestand macht es an `.suchfeld-lupe` schon richtig |
| **M-S9-06:** `padding-left:40px`, `left:12px` | feste Pixelmaße | **Token** (`--abstand-3`, `calc(--abstand-3 + --symbol + --abstand-2)`) | Die Vollständigkeitsprüfung meldet jedes Pixelmaß außer 0 außerhalb von `:root`; wörtlich übernommen wären es **307 statt 304** Befunde |
| **M-S9-05/-07:** `:target`-Fläche mit `margin: 0 calc(--abstand-3 * -1)` | −12 px | **−16 px** (`--abstand-4`) | `.karte-inhalt` hat `--abstand-4` (16 px) Innenabstand. Mit −12 bliebe je Seite 4 px Weiß stehen, und die Hervorhebung reichte nicht an den Kartenrand |
| **M-S9-05:** `scroll-margin-top` an der Zeile | hält die Zeile unter der Kopfleiste | **nichts hinzugefügt** | `html` trägt `scroll-padding-top`, und das gilt für jedes Sprungziel der Seite. Die zweite Angabe war schon einmal gebaut und addierte sich: gemessen 140 statt 72 px. **Nachgemessen: 72 px** bei 56 px Kopfleiste — sie sitzt richtig. M-S9-07 nennt an derselben Stelle bereits `scroll-padding-top` |
| **M-S9-06:** `.nach-oben` als gedämpfter 13-px-Verweis, in `div.karte-fuss-rechts` | ein Textverweis | **`.knopf knopf-leise` in `p.nach-oben`** (so seit Web 16.2.0 gebaut) | Der Bilderlauf misst Bedienhöhen an `.knopf`. Ein Textverweis fiele aus der Messung, und in `Design.md` stünde eine 44/36-Zusage ohne Prüfmittel. `.karte-fuss-rechts` entfällt damit — `text-align:right` am Absatz leistet dasselbe, und eine zweite Klasse für dieselbe Sache ist eine zweite Klasse |
| **Konzept AP5:** Anker `#dest-<id>` | so heißen die Zielklinik-Kennungen | **`#td-<id>`** | Den Vorsatz `dest` gibt es in dieser Anwendung nirgends: Die fünf Listen heißen `veh`, `crew`, `td`, `res`, `bw`, und die verborgenen Formulare tragen dieselben Namen (`f-td-3-del`). Berichtigt ist das **Konzept** |
| **M-S9-06:** Karten-`id` ohne Vorsatz (`#standort`) | `href="#standort"` … `#zielkliniken` | **`k-standort` … `k-bergwacht`** | `Design.md` 9.25 schreibt den Vorsatz `k-` für jede Karte vor, die Sprungziel sein soll; der übrige Bestand hält sich an dreißig Stellen daran. Ein Mockup ist ein Bild, keine Namensregel — und eine Regel, die man für die sechs neuesten Karten aufweicht, ist ab dann keine |
| **Konzept AP5:** Filterfeld in Besatzung und Zielkliniken | zwei Karten | **vier** (dazu Weitere Rettungsmittel und Bergwacht), jeweils ab sechs Einträgen | Dieselbe Listenform mit demselben Problem. Zwei Sorten Liste auf einer Seite — die eine filterbar, die andere nicht — wären schwerer zu erklären als eine Regel: ab sechs Einträgen bekommt jede Liste ihr Hilfsmittel, die Rettungsmittel die Sprungliste, alle übrigen den Filter |
| **M-S9-06:** die Zahl-Variante der Pille (`.sprungziel .zahl`) | Pillen als Inhaltsverzeichnis, mit Zahl | **nicht gebaut** | M-S9-07 zieht sie ausdrücklich zurück („die Pille verliert die Zahl-Variante wieder"); das Inhaltsverzeichnis sind seit Teil 2 drei Kennzahl-Kacheln. `.zahl` ist im Stylesheet ohnehin vergeben (`font-variant-numeric`) |
| **M-S9-09:** Zusatz der Wahlzeilen | „Standard · bleibt" — der Typ **ersetzt** den Diensttag | „Standard · 28.03.2026 06:30, bleibt" — der Typ steht **vor** dem Diensttag | Der Diensttag sagt, WOHER die Angabe kommt, und die beiden anderen Widersprüche (Standort, Besatzung) nennen ihn ebenfalls. Ihn nur beim Rettungsmittel zu streichen, machte die drei Listen uneinheitlich. Gemessen: **0 Überlauf** bei 1280, 700 und 390 px |
| **M-S9-09:** Zeile „Typ" | eine Plakette („Standard") mit der Kleinzeile „folgt dem gewählten Rettungsmittel" | **beide** Typen („Standard oder Bergwacht"), solange zwei Rettungsmittel zur Wahl stehen | Ein Mockup ist ein Standbild. Die Seite lädt beim Klick auf ein Radio **nicht** neu (`$wahl` kommt aus dem POST, Schritt 2 wird per GET gerendert) — eine einzelne Plakette zeigte also weiter den Typ des Zieltags, während `dt_zusammenfuehren()` den des Quelltags schreibt. **F-S9-U-11** |
| **M-S9-08:** Rahmen „Schublade 320 px" | als **Variante** beschriftet: nur der Kurzname sichtbar | **unverändert gelassen** — unter 1024 px stehen weiter alle Namen | Der Rahmen zeigte den Ist-Stand als Variante: `.eintrag-neben` war nie unter 1024 px ausgeblendet, sondern nur im Band 1024–1199 px (**F-S9-U-09**). Der Schublade die vollen Namen zu nehmen, wäre ein Verlust gewesen, den niemand beschlossen hat |
| **M-S9-08:** Kurzname im Band | jeder Kurzname bleibt sichtbar | **außer** an einem Diensttag, der sich sein Datum teilt | Dort trägt die Zeile Datum und Uhrzeit; gemessen blieben dem Kurznamen **3 px von 55** — eine Ellipse ohne Buchstaben. **F-S9-U-10** |
| Hervorhebung des getippten Teils | `<b>` im Bild; die Anmerkung nennt daneben `EdSuchtext.hervor` als „dieselbe Hervorhebung" | `<b>` | Die beiden Angaben des Mockups widersprechen einander: `EdSuchtext.hervor` erzeugt `<mark class="treffer">` mit oranger Fläche. Die Fläche trägt in der **Suche** eine Aussage („hier steht dein Wort in einem langen Text"); in einer Vorschlagsliste steht das Wort am Anfang, und die Fläche käme unter die Zeilenmarkierung zu liegen. Gefolgt ist dem **Bild**, das freigegeben wurde |
| Dichte Stufe | `.dicht .vorschlag{padding-top:2px;padding-bottom:2px}` | keine eigene Regel | `2px` ist kein Token, und die Skala ist geschlossen (`Design.md` 5). Gemessen ändert die Regel nichts am Ergebnis: Eine zweizeilige Zeile ist mit **und** ohne sie höher als beide Bedienhöhen (51 px), eine einzeilige folgt `--knopf` von selbst (44/36, gemessen). Die Regel hätte eine Zahl eingeführt, die nichts bewirkt |
| Leerer Zustand | `.vorschlaege-leer` („keine Treffer") | nicht gebaut | Der Baustein versteckt die Liste, wenn sie leer ist — so verhielten sich beide Vorgänger, und das Konzept verlangt nichts anderes. Eine Zeile „keine Treffer" wäre eine neue Darstellung ohne Freigabe |
| **M-S9-04:** Legende als `<div>` statt `<p>` | `<div class="legende">` | `<div class="legende">` | Keine Abweichung — hier nur festgehalten, weil der erste Entwurf ein `<p>` benutzte: `.dialog-inhalt p` trägt eigene Ränder, und die Legende ist keine Aussage in Sätzen, sondern eine Zeichenerklärung |
| **M-S9-04:** Pfeile auf der Spur | Anmerkung 3: „Pfeile wie in der Einsatzansicht"; Zustand D zeichnet einen | **keine Pfeile** | Der **Konzepttext** E-S9-06 (b) verlangt ausdrücklich „keine Pfeile, keine Luftlinie", und er ist die normative Stelle; die Zeile „Zur Freigabe" des Mockups nennt die Pfeile nicht mit. **Vom Auftraggeber bestätigt am 07.09.2026** (Frage 4) |
| **M-S9-04:** Kopf des Dialogs | `<h2>` im Kopf | `<h2>` im Kopf | Umgestellt vom bisherigen nackten Text. Sichtbare Folge: Kopfschrift, `--groesse-6` statt Fließtextgröße. **Vom Auftraggeber bestätigt am 07.09.2026** (Frage 5) |
| **M-S9-04:** Suchfeld im Kopf, Innenabstand | Zustand A–C: `.dialog-suche` im Kopf, `.dialog-inhalt` mit `padding-top:0` (inline) | dasselbe, als Regel `.dialog-karte .dialog-inhalt{padding-top:0}` | Ein Inline-Stil im Mockup ist eine Notiz, keine Regel; im Stylesheet steht sie auf den Kartendialog begrenzt. Kein neues Token |
| **E-S9-05:** Absatz im Datenschutztext | Konzept: „Vorlage in `rechtstexte_lib.php`, Abschnitt zur Adresssuche mit Dienstadresse als Platzhalter" | **Textbaustein zum Kopieren** auf Verwaltung → Installation | `rechtstexte_lib.php` hat **keine** Vorlagen und kann keine haben: Die Anwendung liefert grundsätzlich keinen Rechtstext mit (`admin_installation.php`, Handbuch 11.5) — ein eingesetzter Absatz wäre eine Rechtsauskunft, die dieses Projekt nicht gibt. Sie kann ihn nur **bereitlegen**: fertiger Abschnitt mit der eingetragenen Dienstadresse, Kopieren-Knopf, sichtbar nur solange die Suche an ist |
| Behälter des Besatzungsfeldes | `<div class="loc-widget">` mit Label daneben | `<label class="feld-vorschlag">` wie bisher | Das Mockup benutzt `.loc-widget` nur, um `position:relative` zu bekommen; `.loc-widget` trägt daneben die Abstände des Ortsfelds. Die Feldstruktur des Formulars bleibt damit unberührt — eine Umstellung auf `ui_feld()` wäre eine Änderung ohne Auftrag |

---

## 6. Handzettel für Schritt 9a — die fünf Android-Texte

Der Auftraggeber hat am 07.09.2026 entschieden, dass die Android-App die
Umbenennung nicht in S9/AP3 bekommt, sondern in **Schritt 9a**, der ohnehin
an ihr arbeitet. Dies ist der Zettel dafür; er kann als Ganzes an die
9a-Instanz gegeben werden.

> **Auftrag.** In `android/handy/src/main/res/values/strings.xml` heißt die
> Aufzeichnung an fünf Stellen noch „Spur". Die Weboberfläche sagt seit
> Web 15.9.0 **„GPS-Daten"** (Entscheidung E-S9-03 des Konzepts S9,
> Backlog Nr. 110); dieselbe Person liest beides.
>
> | Schlüssel | heute | Vorschlag |
> |---|---|---|
> | `warnung_akku_niedrig` | „…dann bricht die Spur ab, ohne dass es jemand merkt." | „…dann brechen die GPS-Daten ab, ohne dass es jemand merkt." |
> | `dienst_kanal_zweck` | „Zeigt an, dass die Spur des laufenden Dienstes aufgezeichnet wird." | „Zeigt an, dass die GPS-Daten des laufenden Dienstes aufgezeichnet werden." |
> | `modus_nur_aufzeichnen_hinweis` | „Die ganze Spur wird aufgezeichnet." | „Es werden durchgehend GPS-Daten aufgezeichnet." |
> | `ortung_fehlt_hinweis` | „Ohne Ortungsfreigabe zeichnet die App keine Spur auf." | „Ohne Ortungsfreigabe zeichnet die App keine GPS-Daten auf." |
> | `warnung_standort_aus` | „…sonst bleibt die Spur dieses Dienstes leer." | „…sonst bleiben die GPS-Daten dieses Dienstes leer." |
>
> In `android/uhr/…/strings.xml` steht „Spur" nur in einem **Kommentar**
> (Zeile 37) — er bleibt; die Wortliste liest Kommentare ohnehin nicht.
>
> **Was mitläuft:** `android/version.properties` hochstufen,
> `./gradlew build` (0 Lint-Fehler, 0 Fehlschläge), **Emulatorlauf mit
> Bildern** (CLAUDE.md 6 — Pflicht bei jeder Änderung an einem der beiden
> Module), `docs/CHANGELOG.md` mit Präfix `Android`, `android/LIESMICH.md`.
>
> **Und diese Zeile hier löschen:** In `tools/wortliste/ausnahmen.json` steht
> die Ausnahme **`spur-android-wartet-auf-9a`** (Bereich `d`, Klasse D). Sie
> ist befristet und hält die Wortliste nur so lange grün, bis 9a die fünf
> Texte umgestellt hat. Wer sie stehen lässt, hat eine Ausnahme, die nichts
> mehr erklärt — und die Wortliste meldet sie beim nächsten Lauf als
> **ungenutzt**, was ein Fehlschlag ist.
