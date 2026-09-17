# Prüfdokument P5b — Konto und Registrierung

**Zum Konzept** `docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md`.
Dieses Dokument beantwortet **„was muss ich noch tun?"** — das Prüfprotokoll
im Konzept beantwortet „ist es belegt?". Es bleibt liegen, bis seine Prüfliste
abgehakt ist, und wird dann gelöscht (`CLAUDE.md` 7).

**Stand:** in Arbeit. Zweig `claude/magical-dirac-we2y1z`.

| Paket | Stand | Version |
|---|---|---|
| Vorarbeit | erledigt — P5a-Merge, Prüfstand, F4 | 20.15.3 |
| **AP1** Protokoll-Schreibweg und Einstellungen | **erledigt** | **20.16.0** |
| **AP2** Lebenszyklus-Bibliothek | **erledigt** | **20.17.0** |
| **AP3** Registrierung | **erledigt** | **20.22.0** |
| **AP4** Einwilligungen | **erledigt** | **20.19.0** |
| **AP5** Selbstlöschung, E-Mail-Wechsel | **erledigt** | **20.20.0** |
| **AP6** Mengengrenze, Aufbewahrung je Konto | **erledigt** | **20.21.0** |
| **AP7** Demo-Anmeldung | **erledigt** | **20.18.0** |
| **AP8** Handbuch-Seiten | **erledigt** | **20.23.0** |
| **AP9** Onboarding, Rückfragen | **erledigt** | **20.24.0** |
| AP10 Abschluss | offen | — |

**Die Mockups sind seit dem 17.09.2026 freigegeben** — damit ist die einzige
Abhängigkeit im Haus aufgelöst, und AP3, AP8 und AP9 brauchen keine Pause mehr.
Dazu gehört eine Berichtigung: Diese Zeile sagte bis zum 17.09.2026, die
Mockups entstünden **mit Opus statt Fable**, weil der Auftraggeber die
Stopp-Punkte freigegeben hatte. Das galt für **zwei** Entwürfe, die auf diesem
Zweig entstanden sind (`M-P5b-01-dokumentseite`,
`M-P5b-02-registrierung-onboarding`). Der Auftraggeber hat daraufhin am
17.09.2026 das vollständige Paket aus der Konzeptsitzung nachgereicht — **fünf
Darstellungen, mit Fable gebaut** (M-P5b-01, -02a bis -02d), vier davon
zusätzlich bei 376 px: **9 HTML, 9 PNG** und `LIESMICH.md`, gegen das echte
`style.css` gerendert. Die beiden Opus-Entwürfe sind damit abgelöst
und aus dem Repositorium entfernt; ihre Historie bleibt in Git. **Die
Gestaltung kommt also doch aus dem vorgesehenen Modell** — der Umweg hat
einen halben Abend gekostet und steht hier, damit die nächste Instanz bei einem
Fable-Schritt zuerst fragt, ob das Ergebnis schon vorliegt, statt es zu bauen.

**Was mit den beiden abgelösten Opus-Entwürfen verschwunden ist**, und was
davon bleibt: Sie schlossen mit 27 offenen Punkten. Die meisten waren
Gestaltungsfragen, die das Fable-Paket beantwortet. **Zwei waren Messungen am
Bestand**, und die sind einzeln nachgegangen worden, statt sie mit den Dateien
zu löschen:

- **`.eintrag-text` mit `flex:1 0 auto`** — der Entwurf hielt das für einen
  Fehler und maß 467 px seitlichen Lauf. **Es ist keiner:** Die Eigenschaft
  steht so im Stylesheet (`style.css`, Zeile 1015) und ist im Kommentar bei
  Zeile 2068 ausdrücklich begründet — samt der Messung, die dahinter steht
  (dreizehn Datumsangaben bei 1024 und 1199 px, 48 bis 79 px für den
  Nebentext). Der volle Bilderlauf misst **53 Seiten, 0 Überlauf**. Keine
  Backlog-Nummer.
- **Die Fußzeile der Anmeldeseite bei 360 px** — vier Verweise brauchen dort
  331 von 336 px, also **fünf Pixel Luft**. AP4 bringt zwei weitere Verweise
  (Nutzungsbedingungen, AVV) mit; die Zeile bricht dann um. Das ist **kein
  Fehler von heute, sondern eine Abnahmebedingung für AP8**: Die neuen
  Mockups sind bei **376 px** gerendert, nie bei 360 — der schmalsten Breite
  des Bilderlaufs. **In der Prüfliste unten als eigener Punkt.**

**Vier Gestaltungsvorgaben des Auftraggebers vom 17.09.2026** gelten für die
Umsetzung und nicht nur für die Mockups (Konzept Abschnitt 6): Zeilenaktionen
**und Plaketten** rechtsbündig in einer Spalte; Kartenfuß links das Häkchen,
rechts „Später"; alles vertikal zentriert und an seinem Element ausgerichtet;
die Bezeichnung „Vereinbarung zur Auftragsverarbeitung (AVV)" bleibt. Sie sind
in AP3, AP8 und AP9 **Abnahmekriterium**, nicht Geschmackssache.

---

## 0. Was NICHT geprüft werden konnte, und warum

Dieser Abschnitt steht vorn, nicht in einer Fußnote (`CLAUDE.md` 7).

| Was | Warum nicht | Was stattdessen |
|---|---|---|
| **Die Wartungsprobe** um den Protokoll-Fehlfall erweitert (Abnahme AP1) | Das Werkzeug `tools/wartungsprobe/` prüft den Wartungsmodus, nicht das Protokoll; eine Erweiterung wäre ein Umbau am Werkzeug, den AP1 nicht rechtfertigt | Der Fehlfall ist **von Hand gemessen** und in Abschnitt 3 mit Zahlen belegt: Tabelle umbenannt → `protokoll()` meldet `false`, Zähler 0 → 1, Handlung läuft weiter (`users` lesbar, 2 Konten), Tabelle zurück → Schreiben geht wieder, Zähler bleibt bis zum Quittieren stehen |
| Das Protokoll unter **echter Last** | Es gibt auf diesem Prüfstand keine | Die drei Indizes sind nach den drei Fragen gelegt, die 10c stellen wird; gemessen wird, wenn 10c die Abfragen hat |
| **`cmark-gfm`** (Markdown-Prüfung, Abnahme AP8) | Im Container nicht vorhanden | Wird in AP8 nachinstalliert |
| **Der Uhr-Simulator** mit gesperrtem Konto (Abnahme AP2 nennt ihn) | Der Prüfstand braucht rund 500 MB SDK und einen Simulatorlauf je Fall; `CIQ_GERAETE_URL` ist gesetzt, der Aufbau war für diesen einen Fall nicht verhältnismäßig | **Gegen `ingest.php` selbst gemessen**, mit echten HTTP-Aufrufen und demselben Schlüsselverfahren (`geraet_schluessel_hash()`): Die Uhr sieht genau diese Antwort. Was der Simulator zusätzlich zeigte, wäre die **Anzeige** auf dem Gerät — und die ist ausdrücklich unverändert (die Uhr sagt „abgemeldet", der Grund im Rumpf ist für die nächste Uhr-Stufe) |
| **Die Fristen sind nie abgelaufen** (Abnahme AP9 verlangt „Prüfkonten-Lauf mit gestellter Uhr") | Ein halbes Jahr abzuwarten geht nicht, und die Bibliothek holt ihre Zeit selbst (`UTC_DATE()`, `DateTimeImmutable('now')`) — sie lässt sich nicht auf einen anderen Tag stellen | **`rueckfrage_naechste` wurde gestellt**, nicht die Uhr: auf gestern, dann gemessen, was die Anwendung daraus rechnet. Die Runden 0 → 1 → 2 → 2 und das Datum 2027-03-17 sind belegt; ein Zeitzonenfehler von einem Tag fiele so **nicht** auf. Als **Backlog Nr. 224** eingetragen, mit dem Weg, wie es zu schließen wäre |
| **Der Dialog in einer echten Sitzung über Tage** | Dieselbe Ursache | Ersatzweise vier Runden mit je **frischer Browsersitzung** gefahren, damit die Sitzungsmerkmale (`rueckfrage_gezeigt`, `blatt_gezeigt`) wirklich neu sind und nicht nur nicht gesetzt |
| **Firefox und WebKit** für die neuen Dialoge | Der Bilderlauf lief nur auf Chromium; die drei Engines kosten die dreifache Zeit und AP9 hat keine neue CSS-Bauform, die sich zwischen ihnen unterscheiden könnte | Chromium in **8 Breiten**. Was ungeprüft bleibt: `<dialog>`-Verhalten in WebKit — `showModal()` und `cancel` sind dort seit Jahren vorhanden, aber nicht von mir gemessen |
| **Der Dialog ohne JavaScript** | Er erscheint dann gar nicht, und das ist kein Mangel, sondern die Bauform: Die Erneuerung rechnet im Browser und kann keinen serverseitigen Ersatzweg haben | Die Erststart-**Karte** dagegen ist ohne JavaScript vollständig bedienbar (ein gewöhnliches Formular) — **nicht gemessen**, aber am Markup nachgelesen: kein `data-`-Haken, kein Skript beteiligt |

---

## 1. Die Umgebung, in der geprüft wurde

Gemessen am 16.09.2026 im Wegwerf-Container der Sitzung.

| | |
|---|---|
| Ausgangsstand | Zweig auf `claude/butte-umsetzen-5opi9u` gehoben (P5a, 26 Commits), Web **20.15.1** |
| PHP | 8.4.19 |
| MariaDB | 10.11.14 — **nachinstalliert** über `tools/containeraufbau/aufbau.sh datenbank`, im Abbild nicht enthalten |
| Node | 22.22.2 · Python 3.11.15 |
| Browser-Engines | Chromium, Firefox, WebKit aus `/opt/pw-browsers` |
| Installation | `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — 2 Konten, **106 Einsätze**, 21 Diensttage, 40 Tabellen, erreichbar unter `https://127.0.0.1:8443/` |
| Android-SDK | **fehlt** (`/opt/android-sdk` nicht vorhanden) — für P5b ohne Belang, das Konzept sieht keine Client-Stufe vor (Abschnitt 0) |
| `CIQ_GERAETE_URL` | gesetzt — der Uhr-Prüfstand wäre aufbaubar, wird aber nur dort gebraucht, wo eine Abnahme den Simulator nennt |
| `cmark-gfm` | **fehlt** — wird für die Markdown-Prüfung in AP8 gebraucht und dort nachinstalliert |

**Die Installation aufzusetzen war selbst der erste Befund** — siehe Abschnitt 2.

---

## 2. Fehlerfunde der Umsetzung

Zählung fortlaufend ab F4; F1 bis F3 stehen im Konzept, Abschnitt 5.

### F4 — Die Anwendung ließ sich nicht mehr installieren (Backlog Nr. 215)

**Gefunden:** 16.09.2026, beim Aufbau des Prüfstands, noch vor AP1.
**Behoben:** Web 20.15.3.

`install.php` antwortete **HTTP 500 mit leerem Rumpf** — kein Formular, keine
Meldung. Gemessen: `curl http://127.0.0.1:8080/install.php` → `HTTP 500,
0 Byte`.

Die Kette, jedes Glied für sich richtig: `install.php` lädt `ui.php`, damit
ihr Formular aussieht wie die Anwendung → `ui_seite_start()` lädt seit
P5a/AP4 `kopfzeilen_lib.php`, damit die Kopfzeilen vor der ersten
Ausgabezeile stehen → `kopfzeilen_lib.php` lud `db.php` → `db.php` verlangt
`config.php` hart. Vor der Einrichtung gibt es keine `config.php`.

**Der Fehler lag auf `claude/butte-umsetzen-5opi9u` und wäre mit P5a auf
`main` gegangen.** Nachgemessen: `git diff origin/claude/butte-umsetzen-5opi9u
HEAD -- server/` war zum Zeitpunkt des Funds leer — der Merge hat ihn nicht
verursacht. Auf `main` trat er nicht auf, weil `ui.php` dort
`kopfzeilen_lib.php` nicht lädt.

**Warum kein Prüfmittel ihn gesehen hat** — und das ist die Lehre, die über
den Fall hinausgeht: Er trifft ausschließlich die Installation, die noch nicht
stattgefunden hat. Jede bestehende Anlage läuft weiter. Und jedes Prüfmittel
des Projekts setzt eine laufende Installation *voraus*, statt eine
einzurichten. Der Weg, den eine Betreiberin genau einmal geht, ist damit der
einzige, den niemand geht.

**Behoben** in `kopfzeilen_lib.php`, nicht in `db.php`: dort ist das harte
`require` richtig. `is_file()` vor dem `require`, `function_exists()` vor den
vier `app_state`-Aufrufen, Rückfall auf dieselben Vorgaben wie bei fehlender
Tabelle (Report-Only, 1 Tag HSTS). Nachgemessen, dass die Datei aus `db.php`
nur `app_state_lesen()` und `app_state_setzen()` braucht.

**Gegenprobe nach der Behebung:** `install.php` → **HTTP 200, 8 505 Byte**,
mit Formular-Token und angelegter Nachweisdatei; `lokal_einrichten.sh` läuft
bis „fertig" durch.

**Offen bleibt die Wache** (Backlog Nr. 214, Teil „Zu tun"): ein Prüfschritt,
der die Einrichtung selbst fährt. Ohne ihn fällt dieselbe Lücke beim nächsten
Umbau der Ladekette wieder auf.

### F5 — Backlog Nr. 48 falsch gelesen (Aufbewahrung je Konto)

**Gefunden:** 17.09.2026, beim Abnehmen von AP6 — durch erneutes Lesen des
Backlog-Eintrags, nicht durch ein Werkzeug.
**Behoben:** vor dem Commit von Web 20.21.0, also ohne dass die falsche
Deutung je ausgeliefert wurde.

**Was schiefging.** AP6 nennt „Aufbewahrung je Konto (Nr. 48)". Ich hatte das
als **Aufbewahrungsfrist für Einsätze** gelesen und ein Feld
`users.aufbewahrung_tage` gebaut — eine Frist, nach der Einsätze verschwinden.
Nr. 48 meint aber die **Zahl der Sicherungspakete je Konto**: Die Installation
hält eine Vorgabe (`adminbackup_aufbewahrung`, Standard 2), und ein einzelnes
Konto soll davon abweichen dürfen.

**Warum das mehr ist als ein falscher Name.** Die falsche Deutung hätte eine
**stillschweigend löschende** Einstellung in die Kontoverwaltung gestellt, an
einer Stelle, an der niemand mit Datenverlust rechnet — und sie hätte unter
einer Backlog-Nummer gestanden, die etwas ganz anderes wollte. Ein Feld mehr
ist ein Fehler; ein Feld, das löscht und falsch beschriftet ist, ist ein
Schaden.

**Behebung.** Feld zu `users.backup_pakete` berichtigt, Migration neu
gefahren, `edbak_aufbewahrung_konto()` in `adminbackup_lib.php` ergänzt und
`edbak_verdraengen()` darauf umgestellt; das falsche Feld aus der
Servereinstellungs-Karte entfernt; `docs/Technik.md` und `docs/Handbuch.md`
berichtigt.

**Gegenprobe:** 4 Fälle, 4 bestanden (Abschnitt 3).

**Was daraus folgt:** Eine Backlog-Nummer im Konzept ist ein **Verweis**, kein
Titel. Wer sie umsetzt, liest den Eintrag — sonst setzt er den eigenen
Eindruck um und hat dafür eine fremde Nummer.

---

### F6 — Die Profilseite brach aus ihrem Seitengerüst aus (Backlog Nr. 217)

**Gefunden:** 17.09.2026, beim **Ansehen** des Bildes zu AP6 — nicht durch
eine Zahl.
**Entstanden:** 07.09.2026 (S9/AP4). **Behoben:** Web 20.21.1.

**Was schiefging.** Auf `einstellungen.php?t=profil` stand ein
`ui_karte_ende()` zu viel. Es schloss keine Karte, sondern gab ein
`</div></section>` ohne Gegenstück aus. Für ein `</div>` ohne offenes `div`
nimmt der Parser das nächste, das er findet — `div.rahmen`. Damit endeten
`form`, `main.inhalt` und `rahmen` mitten auf der Seite; **Datenschutz,
Passwort ändern, Was dein Konto hält** und **Konto löschen** hingen danach
direkt am `body`.

**Gemessen bei 1440 px:** `left` 0 statt 276, Breite 1440 statt 1148 — die
Karten liefen unter der Seitenleiste hindurch über die volle Fensterbreite.
Nach der Behebung stehen alle sechs Karten bei `left` 276 / Breite 1148.

**Nicht funktional.** „Profil speichern" postete weiter: Ein Knopf behält
seinen Formularbezug aus dem Parsen, auch wenn das `form`-Element implizit
geschlossen wurde (`button.form` zeigte auf `pfform`, alle zehn Felder waren
dabei).

**Der eigentliche Befund ist das Prüfmittel.** Der Bilderlauf meldete für
diese Seite in **allen drei Engines**: „kein Überlauf, 0 Konsolenfehler, 0
falsche Knopfhöhen". `scrollWidth` blieb gleich `innerWidth` — es lief nichts
über, es lag nur falsch. Drei Nullen neben einer kaputten Seite, zehn Tage
lang. Genau der Fall, vor dem `CLAUDE.md` 6 warnt.

**Was daraus gebaut wurde:** Der Bilderlauf zählt seither je Seite die Karten,
die nicht in `main.inhalt` hängen, und nennt sie beim Titel. Die Zahl nennt
beide Seiten — „n geprüft · m außerhalb" —, weil eine Seite ohne Karten sonst
dieselbe Null meldete wie eine geprüfte.

**Gegenprobe, dass die neue Zahl nicht selbst eine leere Null ist:** mit wieder
eingebautem Fehler meldete derselbe Lauf unverändert „Überlauf 0 ·
Konsolenfehler 0 · Knöpfe falscher Höhe 0" **und** „6 Karten geprüft · **4
außerhalb** von main.inhalt". Ohne den Fehler: 6 geprüft, 0 außerhalb.

### F7 — Ein Meldungskasten trug einen Ton, den es nicht gibt (Nr. 218)

**Gefunden:** 17.09.2026 von `tools/vollstaendigkeit/` („im Markup ohne
Regel"). **Behoben:** Web 20.21.1.

In `betrieb_server.php` stand von Hand `class="meldung meldung-blau"`. Die
Töne heißen `fehler`, `warn`, `ok`, `info`, `schutz`; `meldung-blau` hat keine
Regel im Stylesheet — der Kasten stand weiß und ohne Symbol da, ohne jede
Fehlermeldung. `ui_meldung_markup()` wirft bei einem unbekannten Ton; hier
war von Hand gebaut, weil der Knopf in einem eigenen Formular steckt, und
damit fiel der Schutz weg. Berichtigt zu `meldung-info` samt `role="status"`
und Symbol.

**Zahl:** „im Markup ohne Regel, Grund nicht eingetragen" **1 → 0**.

**Im Browser nachgemessen**, indem der Zustand hergestellt wurde, der den
Kasten überhaupt zeigt (Registrierung erst auf „offen", dann auf „nur auf
Einladung", Demo-Anmeldung an): Klassen `meldung meldung-info`,
`role="status"`, Hintergrund `rgb(217, 236, 253)` (das Token `--blau-hell`),
Schrift `rgb(31, 78, 156)`, Symbol vorhanden, Knopf vorhanden, **0
Konsolenfehler**.

**Dabei ein zweiter Mangel derselben Stelle:** Der Kasten führte **zwei**
`<p>`. `.meldung` ist eine Flexzeile — der zweite Absatz stellte sich
**neben** den ersten und schob den Knopf in eine eigene Zeile darunter.
`ui_meldung_markup()` gibt aus genau diesem Grund immer **einen** Absatz aus.
Zusammengeführt; danach steht der Knopf neben dem Text, wie beim Baustein.

### F8 — Zwei Seiten fehlten in der Gerüst-Ausnahmeliste

`einwilligung.php` und `adresse_bestaetigen.php` lassen das Seitengerüst mit
Absicht weg (das Tor, weil seine Bereichsnavigation auf gesperrte Seiten
führte; die Bestätigungsseite, weil sie ohne Sitzung läuft) — standen aber
nicht in `tools/vollstaendigkeit/zusagen.md`. Eine begründete Abweichung, die
als Befund mitläuft, ist ein Befund weniger, der auffällt. Beide eingetragen.

**Zahl:** „Seite ohne Gerüst" **2 → 0**, Ausnahmen 7 → 9, ungenutzte 0.

### F9 — Die Schwelle der Vollständigkeitsprüfung stand an zwei Stellen verschieden

`docs/Technik.md` nannte **366**, `pruefung.yml` lief mit **377**. Eine
Schwelle an zwei Stellen läuft auseinander, und die dokumentierte war die
falsche. Berichtigt, mit dem Hinweis, dass die Zahl in der Kette steht und
nicht in der Dokumentation.

**Dabei ausgezählt, was diese Prüfung misst** (Backlog Nr. 219): von 319
Befunden sind **195 `…`** und **104 `→`** — zusammen 299 Satzzeichen in Prosa.
Nur **20** sind Zeichen, die wirklich statt eines Symbols stehen. Solange die
Zeichenliste beides in einen Topf wirft, kann die Schwelle nur steigen; sie tut
es seit P3 mit jeder Phase.

**Was P5b beigetragen hat: +18.** Zwölf davon waren **Zierde** in Kommentaren
und in einem `error_log()` — entfernt (Pfeil zu `->`, Auslassung zu `...`).
Die verbleibenden **zehn** stehen in sichtbarem Text und folgen dem Hausstil
(„Unter Einstellungen → Konto"). Schwelle jetzt **387**.

**Nebenbei behoben:** Scheitert die Anmeldung des Bilderlaufs, steht jetzt die
Meldung der Seite dabei. Beim Einbauen der Gegenprobe kam zweimal „Anmeldung
als demo@gen-em.org gescheitert" ohne Grund — es war der **Ratenschutz**
(Demo-Topf, ausgelöst durch die wiederholten Läufe), also richtiges Verhalten
der Anwendung. Ohne die Meldung rät die nächste Instanz.

---

### F10 — Die Wortliste sah die Rechtstexte nie an (Web 20.22.2, E-P5b-25)

Bereich **c** der Wortliste ist eine feste Liste von acht Dateien.
`docs/rechtstexte/` stand nicht darin, weil die drei Entwürfe erst am
16.09.2026 entstanden sind. Nach ihrer Überarbeitung am 17.09.2026 lief die
Wortliste und meldete **0 Treffer** — und hatte **keine Zeile davon gelesen**.

**Das ist derselbe Fehler wie B-S4-06 bei der Android-App**, und er wiegt hier
schwerer: Die Rechtstexte sind Entwürfe in `docs/`, aber ihr Ziel ist die
Tabelle `rechtstexte`, und von dort rendert die Anwendung sie als eigene
Seiten. Es ist sichtbarer Text, der nur noch nicht eingespielt ist.

**Nachgetragen, dann gemessen: 14 Treffer** in den drei Dateien — **11× „Spur"**
(die Sperrliste sagt ausdrücklich: „Was eine NutzerIn LIEST, heisst
GPS-Daten"), **2× „Pilotinnen und Piloten"** in der Kategorienliste der AVV,
**1× „Station"** statt „Standort". **Alle umformuliert, keine Ausnahme
eingetragen.** Voller Lauf danach 0 Treffer, 0 ungenutzte Ausnahmen, 0
durchgerutschte Fallen über **11 Dateien statt 8**.

**Die Lehre steht jetzt in `tools/wortliste/LIESMICH.md`:** Eine feste
Dateiliste altert still und immer in dieselbe Richtung.

---

### F11 — Die Häkchen wurden verlangt und vergessen (Backlog Nr. 223)

`registrieren.php` prüfte seit Web 20.22.0 alle drei Häkchen als Pflicht und
legte danach das Konto an. Dazwischen fehlte eine Zeile: **Es entstand nie ein
Eintrag in `konto_einwilligungen`.** Der einzige Schreibweg,
`einwilligung_setzen()`, wurde im ganzen Server genau einmal aufgerufen — am
Tor beim Login. E-P5b-05 verlangt „Gespeichert je Konto mit Fassungskennung und
Zeit".

**Der zweite Fund an derselben Stelle war der unangenehmere:** Die
Registrierung prüfte `stand_am` nicht — der Begriff kam in der Datei kein
einziges Mal vor. Solange die geprüften Texte fehlen (der geplante Zustand bis
E-P5b-24), musste eine Registrierende den Haken „Ich nehme die Vereinbarung zur
Auftragsverarbeitung (AVV) an" setzen, während `avv.php` daneben „noch keine
Vereinbarung zur Auftragsverarbeitung hinterlegt." zeigte.

**Entschieden (E-P5b-25):** Festgehalten wird **bei der Registrierung**, weil
dort der Vertrag geschlossen wird; das Tor holt nur eine *neue* Fassung nach.
Neu ist `einwilligung_in_kraft()`; Prüfung und Markup ziehen aus derselben
Liste.

**Gemessen am 17.09.2026** gegen die wiederhergestellte lokale Installation:

| Fall | Erwartet | Gemessen |
|---|---|---|
| Drei Texte in Kraft, alle gehakt | 3 Zeilen mit geltender Fassung | **3 Zeilen**, `stand_am` je `2026-09-16 10:00:00`, vorher 0 |
| Kein Text in Kraft | kein Haken, Konto entsteht, keine Zeile | **0 Häkchen** im Formular, Konto `unbestaetigt`, **0 Zeilen** |
| Tor danach, Texte wieder in Kraft | erstes Konto in Ruhe, zweites vollständig gefasst | erstes `sperrt=[] hinweis=[]`, zweites `sperrt=[nutzungsbedingungen,avv] hinweis=[datenschutz]` |
| Ein Text in Kraft, nicht gehakt | Absage, kein Konto | Meldung kam, **0 Konten** angelegt |
| Ein Text in Kraft, gehakt | genau 1 Zeile mit *dieser* Fassung | **1 Zeile**, `stand_am` `2026-09-17 09:00:00` |

**Dabei ein Folgefehler gefunden, von mir eingebaut:** Die Absage lautete fest
„Ohne **alle drei** lässt sich kein Konto anlegen" — bei einem einzigen
geltenden Text stand dort „Es fehlt noch: Nutzungsbedingungen. Ohne alle drei
…". Die Meldung zählt jetzt mit; nachgemessen für eins („Es fehlt noch … Ohne
diese Zustimmung"), zwei und drei („Es fehlen noch … Ohne diese
Zustimmungen").

---


---

### F12 — Ich habe einen Stand gepusht, der im Prueftor durchgefallen waere

Commit `51a4930` (AP8, Teil 1) ist committet und gepusht worden, **ohne dass
die Vollstaendigkeitspruefung lief**. Gefahren wurde nur die Wortliste. Der
neue `doku_lib.php` brachte sechs Auslassungszeichen in eigenen Kommentaren
mit; damit steht dieser Stand bei **394 Befunden**, die Schwelle in
`pruefung.yml` bei **388**. Ein Prueftor Stufe 1 auf diesem Commit waere rot.

**Aufgefallen ist es erst einen Schritt spaeter**, und zwar zufaellig: Beim
Messen des naechsten Pakets stimmte die Ausgangszahl nicht mit dem ueberein,
was ich erwartet hatte. Erst ein `git stash` gegen HEAD zeigte, dass nicht mein
Arbeitsstand zu hoch war, sondern der bereits gepushte.

**Das ist kein Fehler des Werkzeugs, sondern meiner Reihenfolge.** CLAUDE.md 6
sagt es wortwoertlich: „Die Pruefmittel laufen zuletzt, nicht zwischendurch."
Ich habe bei einem Zwischen-Commit gedacht, ein halbes Paket brauche nur die
halbe Pruefung. Behoben mit Web 20.23.0 (jetzt **388, auf der Schwelle**) —
aber der Commit dazwischen bleibt in der Historie stehen.

**Was daraus folgt:** Auch ein Zwischen-Commit laeuft durch alle Pruefmittel,
oder er wird nicht gepusht. Ein halbes Paket ist kein halber Stand — auf dem
Zweig liegt er ganz.

---

### F13 — Drei Funde in AP8, alle in derselben Arbeit entstanden

**(a) Bilder im Handbuch waeren kaputt gewesen.** `![](bilder/x.png)` loeste zu
`/bilder/x.png` auf, also `server/bilder/`. Die Ursache ist eine Asymmetrie:
Den TEXT liest PHP aus dem Dateisystem, und `../docs/` ist dort normal; ein
BILD holt der BROWSER, und der sieht nur `server/`. **24 Konsolenfehler** im
Bilderlauf; auf dem Server waeren es drei kaputte Bilder in einem Handbuch
gewesen, das ohne sie noch lesbar ist — also drei Fehler, die niemandem
auffallen. Behoben: relative Bildquellen zeigen auf `doku/`, die Kette kopiert
`docs/bilder/` dorthin.

**(b) 43 Tabellen schoben die Seite nach rechts.** Bei 360 px ist die schmalste
368 px breit; gemessen **+124 px** Ueberlauf. Sie scrollen jetzt waagerecht wie
die Codebloecke.

**(c) Und der Bilderlauf zeigte auf den Falschen.** Er nannte `code (626 px)`
als Verursacher — ein `<code>` in einem scrollenden `<pre>`, das die Seite
gar nicht schiebt. Ich habe daraufhin den Code umbrechen lassen, und die Zahl
blieb bei **+124 px**, weil sie nie von dort kam. Erst eine eigene Messung im
Browser nannte die Tabellen. Der Taeter-Finder ueberspringt jetzt, was in einem
scrollenden Kasten steckt.

**Zwei weitere Maengel am Bilderlauf, dabei behoben:** Ein misslungener Abzug
wurde still verschluckt (`.catch(() => {})`) und stuerzte den Kontaktbogen eine
Funktion spaeter mit `ENOENT` ab; jetzt nennt er seinen Grund. Und eine
unbekannte Rolle in `seiten.json` (ich hatte `"user"` geschrieben, es gibt
`aus`, `demo`, `admin`) scheiterte an `undefined`; jetzt sagt sie, welche
Rollen es gibt.

**Der Befund, der ueber dieses Paket hinausgeht:** `setSafeMode(true)` schuetzt
die Zusage „keine fremde Quelle zur Laufzeit" NICHT. Nachgemessen — Parsedown
liefert MIT SafeMode fuer `![B](https://fremd.example/b.png)` ein
`<img src="https://fremd.example/...">`. SafeMode prueft das SCHEMA, nicht die
HERKUNFT.

---

### F14 — Sieben Funde in AP9, alle in der eigenen Arbeit

Sie stehen einzeln, weil jeder eine andere Lehre trägt.

**(a) `blatt.js` gab es schon — ich habe es überschrieben.** Die neue Datei
für die Betreiber-Rückfrage sollte `blatt.js` heißen; unter dem Namen liegt
seit Web 19.6.0 das **Aktionsmenü**, das mobil von unten auffährt
(`window.edBlatt`). Beide sind gültiges JavaScript, keine Seite lädt beide —
**der Fehler hätte keinen Fehler erzeugt**, nur ein verschwundenes
Aktionsmenü. Aufgefallen an `git status`, nicht an einem Bild und nicht an
einer Meldung. Wiederhergestellt aus `HEAD`, umbenannt in
`schluesselblatt.js`. *Lehre:* „Blatt" heißt in diesem Haus zweierlei; vor
`Write` auf einen neuen Dateinamen gehört ein `ls`.

**(b) Der Zwischenspeicher ließ die Runde stehen.** `einstieg_zustand()` hat
einen statischen Merker. `einstieg_faellig()` füllt ihn am Anfang der Anfrage,
`rueckfrage_beantwortet()` schreibt danach und liest dabei die Runde — aus dem
Merker, also die alte. Gemessen in vier Durchgängen: 0 → 1 → 1 → 1. Im Betrieb
wäre das erst nach einem halben Jahr aufgefallen, und dann als „die Anwendung
fragt zu oft". `einstieg_vergessen()` in allen fünf Schreibfunktionen;
nachgemessen 0 → 1 → 2 → 2.

**(c) Eine weggeklickte Frage verdeckte alle folgenden.**
`einstieg_faellig()` gibt nur die **erste** fällige heraus. Wer die
Schlüsselblatt-Frage vertagte, sah bis zur nächsten Anmeldung auch die
fällige Konto-Rückfrage nicht — die Fälligkeit selbst war ja unverändert.
Die Funktion bekommt jetzt mit, was die Sitzung schon gezeigt hat.

**(d) Das Sitzungsmerkmal stand beim Ausgeben, nicht beim Antworten.** Ich
hatte `$_SESSION['rueckfrage_gezeigt']` gesetzt, während die Seite den Dialog
ausgab — „gezeigt ist gezeigt". Zwei Gründe dagegen, und der zweite ist der,
der es aufgedeckt hat: Ein abgebrochener Seitenaufruf hätte die Frage für die
ganze Sitzung verbraucht; und **der Bilderlauf öffnet dieselbe Seite in acht
Breiten** — genau ein Bild hätte den Dialog gezeigt, sieben nicht, und der
Lauf hätte „8 Bilder, 0 Überlauf" gemeldet. Dieselbe Falle wie F-P3-AQ, nur
andersherum. Gesetzt wird es jetzt von `api/rueckfrage.php`, bei jeder
Antwort — auch bei `weggeklickt`, das der Browser beim Schließen sendet.

**(e) Die Meldung nannte 10 Minuten, gesperrt wurde 15.** Ich hatte
`'sperre' => 600` in den Topf `blatt` geschrieben und den Dialogtext daraus
gerechnet. Gemessen in `rate_limits`: `gesperrt_bis` **+15 Minuten** — denn
`rate_leiter_anwenden()` überholt `sperre` mit der ersten Sprosse der Leiter
(900 s). Die Zahl steht jetzt auf 900, und der Text fragt
`rate_stufe_dauer()` statt selbst zu rechnen. *Lehre:* Eine Zahl, die in
einer Meldung steht, muss aus derselben Quelle kommen wie die Wirkung.

**(f) Der dritte Fehlversuch meldete „gesperrt" und ließ die Felder stehen.**
`schluesselblatt.js` behandelte nur den Status **429** („schon gesperrt"),
nicht `rest === 0` („mit diesem Versuch gesperrt"). Wer dann weitertippt,
verlängert die Sperre über die Leiter, ohne es zu wollen. Beide Fälle räumen
jetzt die Felder weg.

**(g) Die Aufschlüsselung der Vollständigkeitsschwelle war erfunden.** Ich
habe die zehn neuen Befunde auf fünf Dateien verteilt und in den Kommentar
der Prüfkette geschrieben — ohne nachzuzählen. Tatsächlich stammen sie aus
**drei** Dateien, und `api/schluesselblatt_pruefen.php` allein liefert fünf
davon; die drei Dateien, die ich genannt hatte, liefern **keinen**. Gemessen
mit `--ausfuehrlich` gegen denselben Lauf auf `HEAD`. *Lehre:* Eine
Aufschlüsselung, die nicht gemessen ist, ist schlimmer als keine — sie sieht
aus wie ein Beleg.


## 3. Was maschinell geprüft wurde — Mittel und Zahl

| Mittel | Wann | Zahl | Befund |
|---|---|---|---|
| `php -l server/kopfzeilen_lib.php` | F4 | 0 Syntaxfehler | — |
| `curl install.php` vor/nach F4 | F4 | HTTP 500/0 Byte → **HTTP 200/8 505 Byte** | behoben |
| `lokal_einrichten.sh` | Prüfstand | 2 Konten, 106 Einsätze, 21 Diensttage, 40 Tabellen | steht |
| Wegwerfliste (E-P5b-23) | vor AP3 | **8 870** Zeilen; **8/8** bekannte Wegwerfanbieter enthalten; **0/10** echte Provider- und Klinikdomains fälschlich | Zahlen des Konzepts bestätigt |
| `php -l` über alle berührten Dateien | AP1 | 6 Dateien, **0 Syntaxfehler** | — |
| Migration `2026_09_16_protokoll_ereignisse` | AP1 | `php update.php` → **erfolgreich angewendet**, 15 Migrationen im Register | Tabelle mit 9 Spalten und 3 Indizes steht |
| **Bereinigung mit gestellten Fristen** (Abnahme AP1) | AP1 | 5 Prüffälle, **3 von 3 Fristfällen richtig**: 31 d E-Mail **weg**, 31 d Verwaltung **bleibt**, 366 d Verwaltung **weg**; Gegenproben 29 d E-Mail und 364 d Verwaltung bleiben. Erwartet `b,d,e` — gemessen `b,d,e` | **bestanden** |
| **Fehlfall V7** (Abnahme AP1) | AP1 | `protokoll()` → `false`, Zähler **0 → 1**, Handlung läuft weiter, Statuskarte rot; nach Rückbau Schreiben wieder `true`, Zähler bleibt bis zum Quittieren | alle **drei** Stufen belegt |
| Schreibweg allgemein | AP1 | 3 Einträge über 3 Reiter; unbekannter Reiter landet unter `system` **mit** `error_log`-Meldung; Zählkarte zählt 3 | — |
| **Bilderlauf, drei Engines** | AP1 | `betrieb_status.php` und `betrieb_server.php` in **8 Breiten** (360–1920): **Chromium** 16 Bilder 0/0/0 · **Firefox** 16 Bilder 0/0/0 · **WebKit** 16 Bilder 0/0/0. Gemessen wird Überlauf, Konsolenfehler und Knopfhöhe (44/36 px am Zeigergerät) | — |
| `grep -rn "INSERT INTO password_resets" server/` (Abnahme AP2) | AP2 | **2 Treffer, beide in `konto_lib.php`** — vorher 4 Dateien | **bestanden** |
| Übergangstabelle (Abnahme AP2) | AP2 | **6 von 6** Übergängen wie festgelegt; `gesperrt → wartet` und `aktiv → unbestaetigt` werden abgewiesen, Status bleibt unverändert | **bestanden** |
| Token-Regel (Abnahme AP2) | AP2 | Nach Anlage + 2× Ausstellen: **1** offener Token (erwartet 1) | **bestanden** |
| Entsperren räumt auf (Abnahme AP2) | AP2 | `gesperrt_seit`, `gesperrt_grund`, **`loeschung_am`** alle NULL nach dem Entsperren | **bestanden** |
| **Migration `konto_lebenszyklus`** | AP2 | `php update.php` → erfolgreich; Bestand (2 Konten) auf **`aktiv`**, `bestaetigt_am` **NULL** wie vorgesehen | **bestanden** |
| **`ingest.php` mit gesperrtem Konto** (Abnahme AP2) | AP2 | aktiv → **200**; gesperrt → **403** `{"error":"konto","grund":"gesperrt"}`; `wartet` → 403 `grund=wartet`; `unbestaetigt` → 403 `grund=unbestaetigt` | **bestanden** |
| **Uhr verliert nichts** (Abnahme AP2) | AP2 | Ein während der Sperre abgewiesener Upload, nach dem Entsperren erneut gesendet: **2 Einsätze im Konto** (erwartet 2) — Punkte vorher = nachher | **bestanden** |
| **Ratenschutz zählt die Absage nicht** | AP2 | 5 Uploads mit gesperrtem Konto → **0 Zeilen** in `rate_limits`. Wichtig, weil eine Sperre der Kennung den Rückstand nach dem Entsperren blockierte | **bestanden** |
| **Ingestprobe** (Regression) | AP2 | **83 Erwartungen, 0 nicht erfüllt** — die Statusprüfung hat den Ingest-Weg nicht verändert | **bestanden** |
| **Demo-Anmeldung aus** (Abnahme AP7) | AP7 | **Im Browser gemessen** (nicht mit curl — ohne die dort abgeleiteten Token scheitert jede Anmeldung): Demo mit **richtigem** Passwort 1241 ms, Demo mit falschem 1270 ms, erfundene Adresse 1263 ms; alle drei **dieselbe Meldung**, Spanne **29 ms** (Soll < 50). Das Adminkonto meldet sich in derselben Lage normal an | **bestanden** |
| Einwilligung: sechs Fälle (Abnahme AP4) | AP4 | leer → verlangt nichts · Text ohne Standdatum → verlangt nichts · mit Standdatum → **2 sperren, 1 weist hin** · nach drei Häkchen → frei · **zweite Fassung am selben Tag sperrt erneut** (F3) · neue Datenschutzerklärung sperrt nicht | **6 von 6 bestanden** |
| **Das Tor im Browser** (Abnahme AP4) | AP4 | Anmeldung → `/einwilligung.php`; `suche.php` → zurück ans Tor; **`import.php` bleibt offen**; 3 Häkchen; nach dem Absenden → `/index.php`; danach `suche.php` frei | **bestanden** |
| **`ingest.php` unberührt vom Tor** (Abnahme AP4) | AP4 | Ingestprobe bei **leerer** `konto_einwilligungen` (alle Konten am Tor): **83 Erwartungen, 0 nicht erfüllt** | **bestanden** |
| `tools/rechtstexte/pruefen.php` | AP4 | **81 Proben, 0 fehlgeschlagen**, dazu 65 Ausgaben gegen die Tag- und Attributliste — mit den zwei neuen Schlüsseln unverändert grün | **bestanden** |
| **Karenz und Löschung** (Abnahme AP5) | AP5 | Antrag → `gesperrt`/`selbstloeschung`, Termin **30,0 Tage**; noch nicht fällig → 0 Konten; **Rückzug → Bestand unverändert** (bases 1, days 1 vorher wie nachher); Uhr vorgestellt → Job löscht 1, **Kaskade räumt alle Reste** | **bestanden** |
| **Adresswechsel** (Abnahme AP5) | AP5 | Vormerken → **alte Adresse gilt weiter**, Frist 24,0 h; falscher Token ändert nichts; richtiger Token wechselt und räumt die Vormerkung; **zweiter Klick auf denselben Token** wird abgewiesen; **belegte Adresse** wird beim Klick abgewiesen, alte bleibt | **6 von 6 bestanden** |
| **Protokoll ohne Adressen** (E-P5b-16) | AP5 | Der Eintrag `adresse_geaendert` enthält **kein `@`** | **bestanden** |
| **Mengengrenze greift** (Abnahme AP6) | AP6 | Grenze 3 Einsätze: Uploads **`200, 200, 200, 507`**; Rumpf der Absage `{"error":"kontingent","grund":"einsaetze"}`; **Ratenzähler 0 Zeilen** (die Absage ist kein Fehlversuch); nach Anheben auf 10 kam die abgewiesene Aufzeichnung nach — **4 Einsätze** im Konto | **6 von 6 bestanden** |
| **Leer heißt „die Vorgabe gilt"** (Abnahme AP6) | AP6 | Ohne eigene Zahl gilt die Installationsvorgabe; eigene Zahl 3 schlägt die Vorgabe 5000; Anheben der Vorgabe wirkt auf Konten **ohne** eigene Zahl sofort | **bestanden** |
| **Backlog Nr. 48 — Aufbewahrung je Konto** | AP6 | 4 Fälle, **4 bestanden**: ohne eigene Zahl gilt die Installationszahl (2); eigene Zahl 7 schlägt sie; ein Ordner ohne Konto fällt auf 2 zurück; leere Kennung ebenso. Dabei **eine Fehldeutung berichtigt** (Abschnitt 2, F5) | **bestanden** |
| **Verwaister Mengen-Cache** (Fehlerfund) | AP6 | Aufräumjob über den Altbestand: **3 verwaiste `mengen:`-Einträge gefunden, 0 danach**; `konto_loeschen()` räumt sie seither an der Wurzel | **bestanden** |
| **Ingestprobe** (Regression) | AP6 | **83 Erwartungen, 0 nicht erfüllt** — die Mengengrenze hat den Ingest-Weg nicht verändert | **bestanden** |
| **Bilderlauf, drei Engines** | AP6 | `einstellungen.php` und `admin_user.php` (Füllstandskarten) in **8 Breiten**: **Chromium** 24 Bilder 0/0/0 · **Firefox** 24 Bilder 0/0/0 · **WebKit** 24 Bilder 0/0/0 (Überlauf / Konsolenfehler / Knopfhöhe) | — |
| **Bilderlauf, voller Lauf** (Nr. 217) | 20.21.1 | **53 Seiten, 424 Einzelbilder, 53 Kontaktbögen** (Chromium): Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0 · **149 Karten geprüft, 0 außerhalb von `main.inhalt`** | **bestanden** |
| **Gegenprobe der neuen Zählung** | 20.21.1 | Fehler wieder eingebaut: dieselben drei Nullen **und** „6 Karten geprüft · **4 außerhalb**". Ohne Fehler: 6 geprüft, 0 außerhalb | die Zahl ist keine leere Null |
| **Kartenbreiten im echten Fenster** (Nr. 217) | 20.21.1 | 1440 px, Profilseite: vorher `left` 0 / Breite 1440 bei vier Karten, nachher **alle sechs `left` 276 / Breite 1148** | **bestanden** |
| **Formularbezug des Knopfs** (Nr. 217) | 20.21.1 | `button.form` → `pfform`, **10 Felder** im Formular — das Speichern war nie unterbrochen | kein Funktionsschaden |
| **Bilderlauf, drei Engines** (berührte Seiten) | 20.21.1 | `einstellungen.php`, `admin_user.php`, `betrieb_server.php` in 8 Breiten: **Chromium** 24 Bilder · **Firefox** 24 · **WebKit** 24 — je 0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen, **19 Karten geprüft, 0 außerhalb** | **bestanden** |
| **Vollständigkeitsprüfung** | 20.21.1 | **387 Befunde** (main: 377). „im Markup ohne Regel" 1 → **0**, „Seite ohne Gerüst" 2 → **0**, Ausnahmen 9, **0 ungenutzt** | **bestanden** |
| **Mockups gegen die Sperrliste** | Paketaufnahme | Die **zehn** Textdateien des Mockup-Ordners (neun HTML und `LIESMICH.md`) gegen alle **24 Muster** der Sperrliste, dazu die 9 Teilstring-Fallen: **0 Treffer** — kein `hubschrauber`, `heli`, `luftrettung`, `basis`, `station`, `pilot`, `christoph`, `garmin`, `flug`, kein großgeschriebenes `Spur`, kein Tastenname. Ihr Text wird in AP3, AP8 und AP9 zu Oberflächentext, deshalb jetzt geprüft und nicht erst dann | **bestanden** |
| **Mockups in einem echten Browser** | Paketaufnahme | Die Bilder des Pakets stammen aus `wkhtmltoimage` (QtWebKit, kein `:has()`, kein woff2). Gegenprobe in **Chromium** über fünf Dateien: `--knopf` löst auf (also greift das echte `style.css`), Schrift **Open Sans**, `scrollWidth` **gleich** `innerWidth` bei 1440 bzw. 860 px, **0 Konsolenfehler, 0 Ladefehler**. Die drei relativen Verweise (`style.css`, zwei Logo-SVG) zeigen alle auf vorhandene Dateien | **bestanden** |
| **Neutrale Antwort** (Abnahme AP3) | AP3 | **120 Aufrufe**, je 40 für freie / bekannte / Wegwerfadresse: Mediane **507,5 / 507,7 / 507,6 ms**, **Spanne 0,2 ms** (Soll < 50). Gesperrter Topf: 508,1 ms, Abweichung **0,6 ms** — auch er antwortet mit derselben Karte | **bestanden** |
| **Fünf Ausgänge, eine Antwort** (Abnahme AP3) | AP3 | frei → Konto `unbestaetigt` + Mail `registrierung`; belegt → **kein** zweites Konto, Mail `registrierung_bekannt`; Wegwerf → 0 Konten, **0 Mails**; zu schnell abgeschickt → 0 Konten; Honeypot gefüllt → 0 Konten. **Alle fünf mit derselben Karte „Danke"** | **5 von 5 bestanden** |
| **Topf je Zieladresse** (Abnahme AP3) | AP3 | Zähler nach vier Versuchen **3** (Grenze 3/24 h), vierter Versuch `rate_reg_ziel_erlaubt()` = **false**; Merkmal `ziel:ade8cf84a27…` — **kein `@`** in der Tabelle | **bestanden** |
| **Der ganze Weg im Browser** (Abnahme AP3) | AP3 | Registrieren → Mail → `pw_handling.php` → Wiederherstellungsschlüssel (24 Zeichen) → Weiterleitung auf `bestaetigen.php?s=wartet`; `pat_wrap_pw` und `pat_wrap_rc` gesetzt, `bestaetigt_am` gesetzt, Sammelmail eingereiht. Anmeldung währenddessen abgewiesen mit „wartet auf Freischaltung". **0 Konsolenfehler** über den ganzen Weg | **bestanden** |
| **Freischaltung** (Abnahme AP3) | AP3 | Filter „Wartet auf Freischaltung" findet 1 von 1, Plakette in der Liste; Knopf → Status **`aktiv`**, Mail **`freigeschaltet`** eingereiht; danach kommt das Konto herein (landet am Einwilligungstor) | **bestanden** |
| **Betriebsart `offen`** (Abnahme AP3) | AP3 | Derselbe Weg endet auf `bestaetigen.php?s=aktiv`, Status **`aktiv`**, **0** Sammelmails (es wartet niemand) | **bestanden** |
| **Betriebsart `einladung`** (Abnahme AP3) | AP3 | `registrieren.php` zeigt kein Formular, sondern „nimmt Registrierungen nur auf Einladung an"; die Anmeldeseite führt **keinen** Verweis dorthin | **bestanden** |
| **Verfall mit gestellter Uhr** (Abnahme AP3) | AP3 | Vier Konten zurückdatiert: unbestätigt 47 h **bleibt**, 49 h **weg**; wartend 29 Tage **bleibt**, 31 Tage **weg**. Job meldet Rückstand 2, löscht 2. Verfallmail **nur** an den Wartenden (1 Mail) | **4 von 4 bestanden** |
| **Die Wegwerfliste selbst** | AP3 | 8 883 Domains, 126 389 Byte: **0** Großbuchstaben, **0** Kommentar-/Leerzeilen, **0** Doppelte; **8 von 8** Wegwerfanbietern enthalten, **0 von 10** echten Provider- und Klinikdomains getroffen. Gegenprobe: eingeschleuste `charite.de` → Rückgabewert **1**, nicht geschrieben; Großbuchstabe + Doppelung → nicht geschrieben | **bestanden** |
| **Bilderlauf, drei Engines** | AP3 | `registrieren.php` und `bestaetigen.php` in 8 Breiten: **Chromium / Firefox / WebKit** je 0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen | **bestanden** |
| **Wortliste** | AP1 bis AP7 | **alle fünf Bereiche**: (a) **117** PHP-Dateien (hier stand 111 — am 17.09.2026 nachgemessen), (b) 36 JS, (c) 8 Dokumente, (d) 2 Android, (e) 35 Uhr — **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 99 Regeln | — |

---

| **Schlüsselwechsel: öffnet der neue, und hört der alte auf?** (Abnahme AP9) | AP9 | Zweimal hintereinander über den Dialog erneuert, dann im Browser gerechnet: **Code B gegen die neue Hülle → OEFFNET**; **Code A gegen die neue Hülle → OEFFNET NICHT (`OperationError`)**; **Code A gegen seine eigene alte Hülle → OEFFNET**. Die dritte Zeile ist die wichtige: ohne sie belegte der Fehlschlag nur, dass der Erzeuger kaputt sein *könnte* | **bestanden** |
| `pat_wrap_rc` trägt keinen Server-Anteil (E-S10-04) | AP9 | Nach der Erneuerung beginnt das Feld mit **`edk1:`**, nicht mit `edka1:`; Länge 129 | **bestanden** |
| Rundenzählung der Konto-Rückfrage | AP9 | Nach der ersten Antwort: `rueckfrage_naechste` = **2027-03-17** (= 6 Monate ab 2026-09-17), `rueckfrage_runde` = **1**, `rueckfrage_verschoben` = **0**. Vier Durchgänge: Runde **0 → 1 → 2 → 2** | **bestanden** |
| „Später" dreimal, dann nicht mehr | AP9 | Vier Runden mit je frischer Sitzung: verschoben **1 → 2 → 3 → 3**; in Runde 4 fehlt der Knopf und an seiner Stelle steht *„Dreimal verschoben — weiter geht es nicht."* | **bestanden** |
| Kontoseite als zweiter Verbraucher | AP9 | Einstellungen → Profil, Karte *Wiederherstellungsschlüssel*: Dialog zu → auf → Passwortabschnitt sichtbar, Schlüsselabschnitt verborgen; Code **5 Gruppen**; Esc in Abschnitt 3 **schließt nicht**; nach „Fertig" zu; beim **zweiten** Öffnen wieder der Passwortabschnitt (kein alter Wert). **0 Konsolenfehler** | **bestanden** |
| Falsches Passwort im Dialog | AP9 | Meldung *„Das Passwort ist nicht korrekt."*, Abschnitt bleibt stehen, nichts gesendet | **bestanden** |
| Betreiber-Rückfrage: Positionen und Kennung | AP9 | Zwei Anzeigen nacheinander: Gruppen **15/3 und 14/5**, dann **7/4 und 4/7** — je Anzeige neu gewürfelt, zwei verschiedene je Wert. Angezeigt wird nur die Kennung (`ffe054fe`), nie ein Wert | **bestanden** |
| Betreiber-Rückfrage: Fehlversuche und Leiter | AP9 | `zzzz` viermal: **„Noch 2 Versuche" → „Noch 1 Versuch" → gesperrt** (Felder weg, Knopf gesperrt) → vierter Aufruf **429**. In `rate_limits`: `versuche` **3**, `stufe` **1**, `gesperrt_bis` **+15 min**. Die Frage bleibt zwischen den Versuchen **unverändert** (Beschriftungen byteweise gleich) | **bestanden** |
| Betreiber-Rückfrage: richtige Antwort | AP9 | Vier Gruppen **in Großschreibung** eingetippt → angenommen; Dialog zu; `app_state.schluesselblatt_bestaetigt_am` gesetzt; Protokoll (Verwaltung) *„Schlüsselblatt bestätigt, von admin@gen-em.org"*; `sicherheit_ereignisse` trägt `blatt_fehlversuch` **ohne Werte** | **bestanden** |
| Notfallblatt: Formatprüfung | AP9 | `K7MQ-…-VNJ0` (Ziffer 0, nicht im Alphabet) → **abgewiesen**; `K7MQ-3RXP-9TWB` (zu kurz) → abgewiesen; **kleingeschrieben** → angenommen und normalisiert; `<script>…@x.org` als Kontoadresse → **nicht ausgegeben** | **bestanden** |
| Bilderlauf (P5b/AP9) | AP9 | `notfallblatt.php` mit und ohne Schlüssel, Konto-Rückfrage, Betreiber-Rückfrage in **8 Breiten** (360–1920): **24 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knopfhöhen** außerhalb 44/36 px | — |
| `tools/wortliste/` | AP9 | **5 Bereiche**: (a) 132 PHP-Dateien, (b) 40 JS, (c) 11 Dokumente, (d) 2 `strings.xml`, (e) 35 Uhr-Dateien. **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** | — |
| `tools/vollstaendigkeit/` | AP9 | **398** Befunde gegen die neue Schwelle 398. Der Zuwachs von 388: **+10, alle Unicode-Typografie**, nachgezählt gegen denselben Lauf auf HEAD — `api/schluesselblatt_pruefen.php` 5, `notfallblatt.php` 4, `blatt_dialog.php` 1. Die drei anderen Gruppen unverändert (50 / 10 / 8) | — |
| `tools/migrationsregister/` | AP9 | **0 Befunde, 0 ungenutzte Ausnahmen** | — |
| `tools/screenshots/kontrast.py` | AP9 | **22 Paare gerechnet, 0 verfehlt** | — |
| `php -l` / `node --check` über alle geänderten Dateien | AP9 | **0 Fehler** | — |

## 4. Was im Browser geprüft wurde

**AP1 — Betrieb → Servereinstellungen (1440 px, Chromium).** Die Karte
„Konten" steht zwischen *Sicherheitskopfzeilen* und *Ratenschutz*, trägt die
Plakette „nur auf Einladung" und erscheint **von selbst in der Sprungliste
der Seitenleiste**. Alle acht Felder sind belegt: Auswahl mit drei
Betriebsarten, Freischaltfrist 30, zwei Schalter, das mehrzeilige Feld für
eigene Domains, Grenzen 5000 / 250, Aufbewahrung leer, Protokollfrist 365.

**AP1 — Betrieb → Status (1440 px, Chromium).** Die Karte „Betriebsprotokoll"
steht zwischen *Plattform* und *Was hier gilt*, mit allen **sechs** Reitern,
je Reiter „0 heute · 0 gesamt" und der Frist in der Unterzeile
(Verwaltung 365, übrige 30).

**Was dabei auffiel und behoben wurde:** Im Hinweistext der Wegwerfadressen
standen Backticks um einen Dateinamen — sichtbarer Text geht durch `ui_e()`,
die Zeichen wären als Literal erschienen. Ersetzt durch eine Formulierung
ohne Dateinamen.

**Beim Bilderlauf aufgefallen und behoben:** Steht ein Rechtstext in Kraft und
hat das Prüfkonto nicht zugestimmt, landet **jede** Aufnahme auf
`einwilligung.php` statt auf der Seite, die gemessen werden soll. Der Lauf
meldete es selbst („OHNE BILD: 32 Aufnahmen — 32× Seite leitete auf die
Anmeldung um", dazu 52 Konsolenfehler in Firefox), aber die nächste Instanz
hätte davor gestanden wie vor einem Rätsel. `aufnehmen.mjs` klickt das Tor
jetzt durch — **geklickt und nicht übergangen**: Das Tor ist echtes Verhalten
der Anwendung, und ein Bilderlauf, der es aushebelte, misste eine Anwendung,
die es so nicht gibt.

**Ebenfalls aufgefallen:** Nach dem Eintragen einer Migration, die noch nicht
gelaufen ist, schaltet der Torwächter den **Wartungsmodus** ein — die
Demo-Anmeldung scheitert dann mit „NAdoku wird gerade aktualisiert". Das ist
korrektes Verhalten (P5a/AP3) und kein Fehler; es steht hier, weil es beim
Prüfen zweimal wie einer aussah.

**AP2 — Verwaltung → Kontoseite.** Die Karte „Status" steht zwischen „Konto"
und „Geräte", trägt die Plakette des Zustands (blau/orange/rot) und zeigt je
nach Zustand einen anderen Weg: „Freischalten" bei *wartet*, „Entsperren" bei
*gesperrt*, sonst das Grundfeld mit dem Knopf „Sperren". Beim eigenen Konto
und beim Demo-Konto steht statt dessen der Satz, warum es hier nicht geht.

---

## 4a. Was das Konzept anders beschrieb, als es ist

Neun Stellen, an denen die Bestandsaufnahme das Konzept berichtigt hat. Sie
stehen hier, weil sie beim nächsten Lesen sonst wieder Verwirrung stiften.
**Die letzten vier sind mit AP9 dazugekommen**, die zwei davor mit dem Paket
vom 17.09.2026; diese sechs betreffen das freigegebene Konzept und die
freigegebenen Mockups selbst. Ihr Wortlaut ist **nicht** geändert worden, weil
er der freigegebene Stand ist; die Abweichung steht stattdessen hier.

| Konzept sagt | Tatsächlich | Folge |
|---|---|---|
| 1.5: „Die Anmeldeseite hat keine Fußzeile mit Verweisen" | Sie **hat** eine — `ui_fuss_seite(['dunkel' => true])` mit Impressum und Datenschutz | AP8 **ergänzt** die Fußzeile um zwei Verweise, statt eine zu bauen |
| 1.2 und E-P5b-15 nennen `admin_rechtstexte.php` als Editor | Die Datei ist seit S8/AP3 eine 19-zeilige Weiterleitung; der Editor steht in **`admin_installation.php`** | Der Protokolleintrag bei Textänderung sitzt dort |
| E-P5b-11: „alle vier Token-Stellen ziehen um" | Stimmt — aber `install.php` läuft **ohne `config.php`** und mit eigener PDO-Verbindung | `konto_lib.php` lädt `db.php` bedingt und nimmt ein `?PDO`; ohne das wäre `install.php` als fünfte Fassung stehengeblieben |
| Abschnitt 6: „Die Mockups M-P5b-02b sind mit **Punkt 1 und 2** als V1.1 neu gerendert" | Die `LIESMICH.md` des Mockup-Ordners nennt **drei**: „Aktionen und Plaketten rechtsbündig in einer Spalte, Häkchen links, „Später" rechts, **alles vertikal zentriert**" — das ist Punkt 1, 2 **und 3** | Am Bild nachgesehen (Chromium, 1440 px): Die Zeilen von M-P5b-02b sind vertikal zentriert. Wer die vier Vorgaben in AP9 abnimmt, nimmt **alle vier** ab und nicht zwei |
| Abschnitt 6 führt „zwei Fable-Schritte" (M-P5b-01, M-P5b-02) | Geliefert sind **fünf Darstellungen** — M-P5b-02 ist in **-02a bis -02d** zerlegt (Registrierung, Erststart, Rückfrage, Notfallblatt), vier davon zusätzlich bei 376 px: 9 HTML, 9 PNG | Kein Widerspruch in der Sache, aber wer nach „M-P5b-02" sucht, findet keine Datei. Die `LIESMICH.md` im Mockup-Ordner ist die führende Liste |

| AP9-Abnahme: „ein `grep` auf die Komponente: **zwei Aufrufer**, eine Definition" | **Ein** Aufrufer. `assets/schluessel.js` definiert `EdSchluessel.erneuern` einmal, und `assets/rueckfrage.js` ruft es einmal — dasselbe Skript bedient **beide** Dialoge, weil es an `[data-rueckfrage]` oder `[data-schluessel]` erkennt, welcher vor ihm steht | Die Zwei-Verbraucher-Regel (R83) ist damit **erfüllt, aber eine Ebene höher**: Zwei Seiten binden dasselbe Markup (`schluessel_teile.php`) und dasselbe Skript ein — `einstellungen.php` und `rueckfrage_dialog.php`. Der `grep` der Abnahme misst also das Falsche; zwei Aufrufer wären hier zwei Kopien derselben zwanzig Zeilen gewesen. **Gemessen:** 1 Definition, 1 Aufruf, **2 Einbinder** |
| E-P5b-10: „„später" wie oben" (= sieben Tage, dreimal) | Bei der **Betreiber**-Rückfrage nur bis zur nächsten Anmeldung | Der Schlüsselblatt-Stand ist **ein** Datum in `app_state` und heißt `schluesselblatt_bestaetigt_am`. Sieben Tage zu schieben hieße, dort ein Datum einzutragen, an dem nichts bestätigt wurde — und es gälte für **alle** BetreiberInnen. Die Begründung steht im Kopf von `blatt_dialog.php` und in `docs/Technik.md` 4.99o |
| Mockup M-P5b-02c, Zustand 3, und M-P5b-02d zeigen **64 Zeichen in 16 Gruppen** | Der Wiederherstellungscode hat **20 Zeichen in fünf Vierergruppen** (`newRecoveryCode()`) | Die Mockups übernehmen das Format des **Schlüsselblatts** (S10), aus dem die Vorlage stammt. Umgesetzt ist das echte Format; die Anmerkung steht in `notfallblatt.php` |
| Mockup M-P5b-02c: „Drei Knöpfe **in einer Reihe**" | Die **Zeichnung** daneben zeigt sie bei 512 px untereinander | Die Zeichnung hat recht, und zwar rechnerisch: `.dialog` ist höchstens 32 rem breit, „Nein — neuen Schlüssel erzeugen" allein über 260 px. Eine Reihe gäbe es an keiner Fensterbreite, weil der Dialog nicht mitwächst |

## 5. Prüfliste für die Betreiberin

Je Punkt: der Bedienweg, das erwartete Ergebnis und **woran ein Scheitern zu
erkennen ist**.

**Vorab, einmal:** Nach dem Einspielen muss eine Administratorin `update.php`
aufrufen. Die Phase bringt **sechs** Migrationen mit
(`2026_09_16_protokoll_ereignisse`, `…_konto_lebenszyklus`,
`…_einwilligungen`, `…_adresswechsel_bestaetigt`, `…_konto_grenzen`,
`2026_09_17_erststart_rueckfragen` —
nachgezählt in `migration_lib.php`; hier stand zuerst vier, der
Adresswechsel aus AP5 fehlte, dann fünf bis AP9). Ein Aufruf verbucht alle
sechs. Bleibt der Aufruf aus, stehen die neuen Karten leer da oder
melden „Tabelle fehlt" — kein Datenverlust, aber nichts von dem, was unten
steht, ist dann zu sehen.

**Die Liste deckt AP1, AP2, AP4, AP5, AP6, AP7 und Nr. 217 ab.** AP3, AP8 und
AP9 stehen noch aus (Mockup-Freigabe); ihre Punkte kommen mit ihnen.

### AP1 — Protokoll

- [ ] **P1.1 — Die Zählkarte zählt.** *Betrieb → Status*, Karte
  „Betriebsprotokoll". Erwartet: je Reiter eine Zahl für die letzten 24 h.
  Danach ein Konto sperren und entsperren (*Verwaltung → NutzerInnen →
  Aktionen*) und die Seite neu laden. **Erwartet:** die Zahl bei
  *verwaltung* ist um mindestens 2 gestiegen.
  **Scheitern:** Die Zahl bleibt stehen, oder die Karte fehlt ganz — dann ist
  `update.php` nicht gelaufen.

- [ ] **P1.2 — Die Aufbewahrung lässt sich einstellen.** *Betrieb →
  Servereinstellungen*, Feld „Protokoll: Aufbewahrung Verwaltung".
  **Erwartet:** Werte zwischen 90 und 1095 Tagen werden angenommen, alles
  darunter oder darüber abgewiesen — mit einer Meldung, die die Spanne nennt.
  **Scheitern:** 30 wird angenommen. Die Untergrenze ist Absicht: Ein
  Nachweis, der nach einem Monat weg ist, ist kein Nachweis.

### AP2 — Lebenszyklus

- [ ] **P2.1 — Eine Sperre hält die Uhr nicht auf Dauer auf.** Ein Konto
  sperren, mit dessen Uhr (oder Handy) eine Aufzeichnung senden, dann
  entsperren und erneut senden lassen. **Erwartet:** Während der Sperre kommt
  nichts an, das Gerät behält die Aufzeichnung; nach dem Entsperren ist sie
  **vollständig** da — Einsätze und GPS-Punkte wie vorher.
  **Scheitern:** Der Einsatz fehlt, oder er ist da und die GPS-Spur ist kürzer
  als auf dem Gerät.

- [ ] **P2.2 — Entsperren räumt auf.** Ein Konto sperren, dann entsperren,
  dann die Kontoseite ansehen. **Erwartet:** Plakette „aktiv", kein
  Sperrgrund, **kein Löschtermin**.
  **Scheitern:** Irgendeine Spur der Sperre steht noch da — besonders ein
  Löschtermin, denn der führte 30 Tage später zur Löschung eines Kontos, das
  längst wieder aktiv ist.

### AP4 — Einwilligungen

- [ ] **P4.0 — Die Registrierung hält die Häkchen fest** (neu, Web 20.22.2,
  Backlog Nr. 223). Voraussetzung: Die Rechtstexte tragen ein **Standdatum**,
  und die Registrierung steht auf *offen*. Ein Konto über `registrieren.php`
  anlegen, dabei alle Häkchen setzen. Dann in *Verwaltung → NutzerInnen* das
  neue Konto öffnen. **Erwartet:** Es zeigt alle drei Dokumente als
  angenommen, mit der geltenden Fassung und einem Zeitpunkt — **noch bevor
  sich jemand angemeldet hat**.
  **Scheitern:** Die Dokumente stehen dort als offen. Dann schreibt die
  Registrierung wieder nicht, und die Annahme entsteht erst am Tor beim ersten
  Login — der alte Zustand.

- [ ] **P4.0b — Ohne Text kein Haken.** Bei **allen** Rechtstexten das
  Standdatum leeren und `registrieren.php` aufrufen. **Erwartet:** Das Formular
  zeigt **kein einziges Häkchen**, und eine Registrierung ohne Häkchen geht
  durch. Danach das Standdatum wieder setzen und mit diesem Konto anmelden:
  Jetzt kommt das Tor mit allen drei.
  **Scheitern:** Das Formular verlangt Häkchen für Dokumente, die
  `nutzungsbedingungen.php` und `avv.php` als leer anzeigen — dann prüft die
  Registrierung `stand_am` nicht, und jemand nimmt ein leeres Dokument an.

- [ ] **P4.1 — Das Tor kommt, wenn die Texte in Kraft sind.** In *Verwaltung →
  Installation* bei Nutzungsbedingungen und AVV ein **Standdatum** setzen.
  Dann abmelden und mit einem anderen Konto anmelden. **Erwartet:** Es landet
  auf der Seite „Bevor es weitergeht" mit drei Haken (zwei „annehmen", einer
  „zur Kenntnis nehmen").
  **Scheitern:** Es kommt direkt auf die Startseite — dann fehlt das
  Standdatum, oder die Migration ist nicht gelaufen.

- [ ] **P4.2 — Der Ausgang bleibt offen.** Am Tor, **ohne** zu haken, die drei
  Wege unten anklicken. **Erwartet:** *Daten ausleiten* öffnet
  `import.php`, *Abmelden* meldet ab, *Konto löschen* führt zu den
  Einstellungen. Alle drei funktionieren.
  **Scheitern:** Einer davon wirft zurück ans Tor. Das wäre Nötigung, nicht
  eine Einwilligung — und der Grund, warum diese drei Seiten überhaupt in der
  Ausnahmeliste stehen.

- [ ] **P4.3 — Eine neue Fassung sperrt erneut.** Nach dem Durchgang das
  Standdatum der Nutzungsbedingungen **auf denselben Tag, aber eine spätere
  Uhrzeit** setzen. Neu anmelden. **Erwartet:** Das Tor kommt wieder, mit der
  Plakette „neue Fassung" und der Zeile „du hast die Fassung vom … angenommen".
  **Scheitern:** Es kommt nicht — dann zählt die Anwendung nur Tage, und zwei
  Änderungen an einem Tag wären eine Fassung.

### AP5 — Selbstlöschung und Adresswechsel

- [ ] **P5.1 — Die Karenz hält.** Mit einem **Prüfkonto** (nicht dem eigenen)
  unter *Einstellungen → Konto löschen* die Löschung beantragen.
  **Erwartet:** Das Konto ist gesperrt, die Seite nennt einen Termin **30 Tage**
  voraus, und eine Anmeldung in dieser Zeit **nimmt die Löschung zurück** —
  danach ist der Bestand unverändert.
  **Scheitern:** Der Bestand ist nach dem Rückzug kleiner, oder die Anmeldung
  nimmt die Löschung nicht zurück.

- [ ] **P5.2 — Die Adresse wechselt erst nach Bestätigung.** Unter
  *Einstellungen → Profil* die E-Mail-Adresse ändern (mit Passwort).
  **Erwartet:** Es kommt eine Nachricht an die **neue** Adresse; **bis zum
  Klick gilt die alte** — eine Anmeldung mit der neuen schlägt fehl, mit der
  alten klappt sie. Nach dem Klick umgekehrt. Ein **zweiter** Klick auf
  denselben Link wird abgewiesen.
  **Scheitern:** Die Anmeldung mit der alten Adresse schlägt sofort fehl. Wer
  sich bei der neuen vertippt hat, wäre dann ausgesperrt — genau das soll die
  Bestätigung verhindern.

- [ ] **P5.3 — Im Protokoll steht keine Adresse.** *Betrieb → Status* nach
  einem Adresswechsel. **Erwartet:** Der Eintrag `adresse_geaendert` nennt
  **kein** `@`.
  **Scheitern:** Die alte oder neue Adresse steht im Klartext im Protokoll.

### AP6 — Mengengrenzen

- [ ] **P6.1 — Leer heißt „die Vorgabe gilt".** *Verwaltung → NutzerInnen →
  ein Konto*, Karte „Mengen und Grenzen". Die beiden Felder **leer** lassen.
  Dann in *Betrieb → Servereinstellungen* die Vorgabe ändern.
  **Erwartet:** Die Karte des Kontos zeigt sofort die **neue** Vorgabe.
  **Scheitern:** Sie zeigt die alte Zahl — dann steht die Vorgabe im Konto
  statt eines Leerfelds, und eine spätere Anhebung erreicht Bestandskonten nie.

- [ ] **P6.2 — Die Grenze greift und verliert nichts.** Bei einem Prüfkonto
  die Einsatzgrenze auf eine Zahl **unter** dem Bestand setzen. Mit der Uhr
  senden lassen. **Erwartet:** Der Server lehnt ab (`507`), die Uhr **behält**
  die Aufzeichnung. Grenze wieder anheben, erneut senden lassen: Die
  Aufzeichnung kommt **vollständig** nach.
  **Scheitern:** Die Uhr verwirft nach der Absage, oder die nachgelieferte
  Aufzeichnung ist unvollständig.

- [ ] **P6.3 — Aufräumen bleibt möglich.** Ein Konto an seiner Grenze:
  Einsätze bearbeiten, in den Papierkorb legen, endgültig löschen.
  **Erwartet:** Alles drei geht. Der Füllstand sinkt, sobald aus dem
  Papierkorb gelöscht wird — was **im** Papierkorb liegt, zählt nicht mit.
  **Scheitern:** Das Löschen wird ebenfalls abgewiesen. Dann ist die Grenze
  eine Falle, aus der niemand herauskommt.

- [ ] **P6.4 — Die Aufbewahrung je Konto (Nr. 48).** Bei einem Konto
  „Konto-Backups aufheben" auf 7 setzen, bei einem zweiten leer lassen.
  Mehrfach sichern. **Erwartet:** Beim ersten bleiben 7 Pakete stehen, beim
  zweiten die Zahl der Installation (Vorgabe 2).
  **Scheitern:** Beide verdrängen gleich — dann greift die eigene Zahl nicht.

### AP7 — Demo-Anmeldung

- [ ] **P7.1 — Abgeschaltet sieht man nichts.** *Betrieb →
  Servereinstellungen*, Demo-Anmeldung aus. Dann auf der Anmeldeseite die
  Demo-Adresse mit **richtigem** Passwort, mit **falschem** Passwort und eine
  **erfundene** Adresse probieren. **Erwartet:** dreimal **dieselbe** Meldung,
  und die Dauer liegt dicht beieinander.
  **Scheitern:** Die Demo-Adresse antwortet anders als die erfundene — dann
  verrät die Anmeldeseite, dass es dieses Konto gibt.

### AP9 — Onboarding und Rückfragen

- [ ] **P9.1 — Das Notfallblatt beim ersten Passwort.** Ein neues Konto
  anlegen (oder einladen), über den Link das Passwort setzen. Wenn der
  Wiederherstellungsschlüssel erscheint, auf **„Notfallblatt drucken"**
  drücken. **Erwartet:** ein neues Fenster mit dem Blatt — Schlüssel in fünf
  Vierergruppen, deine Kontoadresse, die Adresse dieser Installation, das
  Datum. Der Schlüssel auf dem Blatt ist **derselbe** wie auf der Seite
  darunter.
  **Scheitern:** Das Fenster zeigt *„Dieses Blatt lässt sich nicht
  nachträglich drucken"* — dann ist der Wert nicht mitgekommen, und das Blatt
  ist leer. **Dann nicht weitermachen**, sondern den Schlüssel von der Seite
  abschreiben, bevor du sie verlässt.

- [ ] **P9.2 — Die drei ersten Schritte.** Mit dem neuen Konto anmelden.
  **Erwartet:** über der Tagesübersicht die Karte *„Willkommen — drei
  Schritte, dann geht es los"*, darunter die **vollständige** Tagesübersicht.
  Ein Rettungsmittel anlegen, zurück zur Startseite. **Erwartet:** Schritt 2
  trägt einen Haken und den Namen des Rettungsmittels statt der Erklärung.
  **Scheitern:** Die Karte deckt die Seite zu, oder sie steht nach dem
  Anlegen unverändert da — im zweiten Fall merkt sich das Konto den Stand
  nicht.

- [ ] **P9.3 — Die Karte lässt sich loswerden.** Auf *Später* drücken, Seite
  neu laden. **Erwartet:** die Karte ist weg und bleibt weg, bis du dich
  neu anmeldest. Dann abmelden, anmelden, **„nicht mehr zeigen"** ankreuzen
  und *Später* drücken. **Erwartet:** Sie kommt auch nach erneutem Anmelden
  nicht wieder.
  **Scheitern:** Sie steht nach *Später* sofort wieder da — dann ist der
  Weg über Post/Redirect/Get gebrochen.

- [ ] **P9.4 — Einen neuen Schlüssel erzeugen (der wichtige Punkt).**
  *Einstellungen → Profil*, Karte *Wiederherstellungsschlüssel*, **„Neuen
  Schlüssel erzeugen"**, Passwort eingeben. **Erwartet:** ein neuer Code in
  fünf Vierergruppen; *Fertig* ist gesperrt, bis du den Haken setzt; Esc
  schließt den Dialog **nicht**. Notfallblatt drucken, Haken, *Fertig*.
  **Danach — und das ist der eigentliche Prüfpunkt:** abmelden, *Passwort
  vergessen*, den Link aus der Mail öffnen und den **neuen** Schlüssel
  eintippen. **Erwartet:** Das Passwort lässt sich setzen, und danach sind
  **alle Einsätze mit Patientendaten wieder lesbar**.
  **Scheitern:** Der neue Schlüssel wird abgelehnt, oder die Patientenfelder
  bleiben leer. Dann ist der Rückweg verloren — **mach diesen Punkt an einem
  Prüfkonto, nicht am eigenen.**

- [ ] **P9.5 — Der alte Zettel gilt nicht mehr.** Denselben Weg wie P9.4, aber
  mit dem **alten** Schlüssel. **Erwartet:** *„Der Wiederherstellungsschlüssel
  passt nicht. Es wurde nichts geändert."*
  **Scheitern:** Der alte Schlüssel wird angenommen — dann hat die Erneuerung
  die Hülle nicht ersetzt, und jedes alte Blatt öffnet weiter.

- [ ] **P9.6 — Falsches Passwort.** Im selben Dialog ein falsches Passwort
  eingeben. **Erwartet:** *„Das Passwort ist nicht korrekt."*, der Dialog
  bleibt stehen, es entsteht **kein** neuer Schlüssel.
  **Scheitern:** Es erscheint ein Code — dann wäre der Zettel gewechselt
  worden, ohne dass jemand das Passwort kannte.

- [ ] **P9.7 — Die Rückfrage kommt (braucht Geduld oder die Datenbank).**
  30 Tage nach der ersten Anmeldung erscheint beim Anmelden *„Hast du dein
  Notfallblatt noch?"*. Wer nicht warten will, setzt
  `users.rueckfrage_naechste` auf gestern. **Erwartet:** drei Knöpfe — *Ja,
  liegt sicher*, *Nein — neuen Schlüssel erzeugen*, *Später (7 Tage)*. Nach
  *Ja* steht in `rueckfrage_naechste` ein Datum **6 Monate** später und in
  `rueckfrage_runde` eine **1**.
  **Scheitern:** Die Runde bleibt auf 0 oder 1 stehen — dann fragt die
  Anwendung für immer im selben Abstand.

- [ ] **P9.8 — Dreimal „Später", dann nicht mehr.** Dreimal *Später* drücken
  (je mit neuer Anmeldung und zurückgesetzter Frist). **Erwartet:** Beim
  vierten Mal fehlt der Knopf, und an seiner Stelle steht *„Dreimal
  verschoben — weiter geht es nicht."*
  **Scheitern:** Der Knopf bleibt — dann lässt sich die Frage dauerhaft
  wegschieben, und sie ist wirkungslos.

- [ ] **P9.9 — Das Schlüsselblatt bestätigen (nur BetreiberIn).** Als
  BetreiberIn anmelden. **Erwartet:** *„Schlüsselblatt bestätigen"* mit vier
  Feldern und genannten Gruppennummern, dazu die achtstellige Kennung.
  Nachsehen: **Es steht nirgends ein Wert.** Die vier Gruppen vom Blatt
  abtippen (Groß/Klein egal) → der Dialog schließt, und im Protokoll (Reiter
  *Verwaltung*) steht *„Schlüsselblatt bestätigt, von …"*.
  **Scheitern:** Der Dialog nennt einen Schlüsselwert, oder er kommt nach der
  Bestätigung beim nächsten Anmelden sofort wieder.

- [ ] **P9.10 — Dreimal falsch sperrt.** Als BetreiberIn dreimal Unsinn
  eintragen. **Erwartet:** *„Noch 2 Versuche"* → *„Noch 1 Versuch"* →
  gesperrt, Felder verschwinden, Knopf gesperrt. Die genannte Dauer (15
  Minuten) muss zur tatsächlichen passen — nachsehen in *Betrieb → Status*
  oder in `rate_limits`. Im Sicherheitsprotokoll steht ein Eintrag **ohne
  Werte**.
  **Scheitern:** Es lässt sich unbegrenzt weiterraten, oder der Eintrag im
  Protokoll enthält eine Gruppe vom Blatt.

- [ ] **P9.11 — Die Positionen sind nicht immer dieselben.** Abmelden,
  anmelden, die Gruppennummern notieren; noch einmal. **Erwartet:** andere
  Nummern.
  **Scheitern:** Immer dieselben — dann genügt ein Zettel mit vier Gruppen,
  und die Frage prüft nichts mehr.

### Mockups — was vor AP3, AP8 und AP9 zu prüfen ist

- [ ] **PM.1 — Die Anmeldeseite bei 360 px, nach AP4/AP8.** Anmeldeseite an
  einem 360 px schmalen Fenster öffnen (Geräteleiste des Browsers, nicht nur
  das Fenster verkleinern). **Erwartet:** Die Fußzeile steht **einzeilig**.
  **Scheitern:** Sie bricht um. Vier Verweise brauchen dort 331 von 336 px;
  AP4 bringt Nutzungsbedingungen und AVV dazu, und die Mockups sind bei
  376 px gerendert — diese Breite ist in ihnen **nicht** geprüft.

- [ ] **PM.2 — Die vier Gestaltungsvorgaben, an jeder neuen Darstellung.**
  Erststart, Registrierung, Rückfrage-Dialog und Dokumentseite im Browser.
  **Erwartet:** Zeilenaktionen **und Plaketten** stehen rechtsbündig in
  **einer** Spalte untereinander (die Plakette „erledigt" an derselben
  Stelle, an der vorher der Knopf stand); im Kartenfuß steht das Häkchen
  links und „Später" rechts; Nummernplakette, Text und Aktion einer Zeile
  liegen auf einer Mittellinie.
  **Scheitern:** Eine Plakette sitzt hinter dem Text statt am rechten Rand —
  das war die Anmerkung, die M-P5b-02b zur Fassung V1.1 gemacht hat.

- [ ] **PM.3 — Die beiden neuen Symbole.** „hilfe" (Tabler
  „help-circle") und „drucker" (Tabler „printer") müssen mit AP8 bzw. AP9
  nach `server/assets/images/symbole/` — **mit Quellvermerk** und dem Anker
  `id="i"`. **Erwartet:** `python3 tools/vollstaendigkeit/pruefen.py` meldet
  weiterhin 0 für „Verweis auf fehlende Symboldatei" und 0 für
  „Symboldatei ohne Anker".
  **Scheitern:** Das Hilfe-Symbol im Kopf bleibt leer — und es steht auf
  **jeder** Seite.

- [ ] **PM.4 — Die zehn vorgeschlagenen Klassen vor dem Einbau messen.**
  `.fuss-anmeldung`, `.doku`, `.doku-nav`, `.doku-text`, `.doku-stand`,
  `.doku-handy`, `.erststart` mit `.schritt-nr`, `.gruppen`, `.blatt-druck`.
  **Erwartet:** Jede neue Farbe und jede neue Größe steht in `:root`, ist in
  `docs/Design.md` nachgetragen und mit `python3 tools/screenshots/kontrast.py`
  gegen die **tatsächliche** Fläche gerechnet (Schnee/Rauch, nicht Weiß).
  **Scheitern:** Ein Hexwert steht unmittelbar in einer Regel — die
  Vollständigkeitsprüfung meldet ihn unter „Hexfarben ausserhalb :root".

### Nr. 217 — Die Profilseite

- [ ] **P217.1 — Die Karten stehen in ihrer Spalte.** *Einstellungen → Profil*
  an einem breiten Fenster (ab 1024 px). **Erwartet:** **Alle** Karten —
  Angaben, Logo, Datenschutz, Passwort ändern, Was dein Konto hält, Konto
  löschen — sind gleich breit und beginnen an derselben linken Kante, rechts
  neben der Seitenleiste.
  **Scheitern:** Ab „Datenschutz" laufen sie über die volle Fensterbreite und
  unter der Seitenleiste hindurch. Genau so sah es zehn Tage lang aus.

---

## 6. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf misst statisches Markup**, keine Bedienzustände. Dass die
  Karte „Konten" *aussieht* wie vorgesehen, sagt nichts darüber, ob das
  Speichern die richtigen Werte schreibt — das ist von Hand geprüft
  (Abschnitt 3) und gehört in die Prüfliste (Abschnitt 5).
- **Die Wortliste liest sichtbaren Text ohne Kommentare.** Ein Luftbegriff in
  einem Kommentar fällt ihr nicht auf, und das ist richtig so — aber auch
  keine Aussage über Kommentare.
- **Kein Prüfmittel des Projekts richtet eine Installation ein.** Genau daran
  ist F4 dreizehn Auslieferungen lang vorbeigelaufen. Die Lücke bleibt
  bestehen (Backlog Nr. 215, Teil „Zu tun").
- **Die Fristen des Protokolls sind mit gestellten Zeitstempeln geprüft**, nicht
  über echte 365 Tage. Das ist die einzig mögliche Prüfung und gleichzeitig
  ihre Grenze: Sie belegt die SQL-Bedingung, nicht das Verhalten einer
  Datenbank, die ein Jahr lang gelaufen ist.
