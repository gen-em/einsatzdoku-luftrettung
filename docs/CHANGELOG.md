# Changelog — Einsatzdoku

Format nach [Keep a Changelog](https://keepachangelog.com/de/).

**Weboberfläche** und **Uhr-App** werden getrennt gezählt, weil sie unabhängig
voneinander ausgeliefert werden: `server/version.php` bzw.
`watch/source/Const.mc`. Die Web-Version steht in der Fußzeile jeder Seite. Bis
Web 5.3.0 hing sie zusätzlich an allen Stylesheet- und Skript-Adressen; seit
Web 5.4.0 steht dort der Zeitstempel der jeweiligen Datei, damit nach einem
Update nur die tatsächlich geänderten Dateien neu geladen werden. Die
Uhr-Version steht auf der Sync-Seite. Die Stände 1.0 bis 1.2 unten sind die
frühen Spezifikations-Stände des Gesamtprojekts, vor der getrennten Zählung.

## [Web 6.3.0 / Uhr 1.8.0] — 2026-08-18

**Die Notarzt-Erweiterung ist abgeschlossen.** Vierte und letzte Etappe
(Konzept: `docs/Konzept-Notarzt-Erweiterung.md`): Diensttage lassen sich
zusammenführen, die Uhr schickt eine Dienstkennung, und die Dokumentation ist
auf dem Stand des fertigen Umbaus.

**Keine Migration.** Wie die Etappen 2 und 3 arbeitet auch diese auf dem Schema
der 6.0.0 — `day_refs`, `mission_crew` und die Fremdschlüssel auf `days` liegen
seit damals. Wer auf 6.0.0, 6.1.0 oder 6.2.0 ist, spielt nur die Dateien ein.

### Zwei Diensttage zusammenführen

Wurde die App während **eines** Dienstes versehentlich mehrfach gestartet,
entstehen mehrere Diensttage für einen tatsächlichen Dienst. Sie lassen sich
wieder zu einem machen: **Aktionen → „Anderen Diensttag aufnehmen"** im
Diensttag, der bleiben soll.

Der Einstieg liegt bewusst **im Zieltag** und nicht in der Tagesliste. Damit ist
die Richtung eine Tatsache statt einer Lesart — wichtig, weil der Vorgang
**nicht umkehrbar** ist. Danach zwei Schritte: aus den zeitlich benachbarten
Diensttagen (drei Tage vor und nach diesem) den auszuwählen, der aufgenommen
wird, dann die Vorschau bestätigen. Zu jedem Kandidaten stehen Rettungsmittel,
Standort und die Zahl der Einsätze, Ruhesegmente und Uhr-Kennungen — daran
lassen sich zwei Bruchstücke desselben Dienstes auseinanderhalten.

Danach hängen Einsätze, Ruhesegmente und Uhr-Kennungen am Zieltag, sein Zeitraum
umschließt beide, und die Notizen sind aneinandergehängt. Widersprechen sich die
beiden Tage bei Rettungsmittel, Standort oder Besatzung, wird in der Vorschau
gewählt, was gilt; vorbelegt ist der Tag, der bleibt.

**Was nicht geht, und warum:**

- **Luftgebunden und bodengebunden schließen sich aus.** Ein Einsatz mit
  Windendokumentation verlöre an einem bodengebundenen Diensttag seine Felder.
  Unvereinbare Kandidaten stehen trotzdem in der Liste — gesperrt und mit
  Begründung. Eine fehlende Zeile sähe aus wie ein Fehler der Anwendung.
- **Ein noch nicht zugeordneter Diensttag passt zu beidem** und übernimmt die
  Art des anderen.
- **Kein Weg zurück, kein Papierkorb.** Dort läge ein leerer Tag, dessen
  Wiederherstellung die Einsätze nicht zurückholen könnte — sie hängen dann am
  aufnehmenden Tag.
- **Aufteilen gibt es nicht.**

**Uhr-Kennungen wandern mit.** Ein späterer Upload mit einer Kennung des
aufgenommenen Tages landet dadurch von selbst im Zieltag — ohne Umleitung und
ohne Sonderfall. Genau dafür liegen die Kennungen seit Web 6.0.0 in einer
eigenen Tabelle.

**Papierkorbeinträge wandern ebenfalls mit.** `missions.day_id` steht auf
`ON DELETE SET NULL`; ein zurückgelassener Einsatz verlöre beim Entfernen des
aufgenommenen Tags still seinen Diensttag und wäre verwaist.

### Uhr 1.8.0: Dienstkennung und neutrale Phasen

Die Uhr erzeugt bei „Einsatztag starten" eine **Dienstkennung** (`day_ref`) und
schickt sie an jedem Einsatz und Ruhesegment mit — gleiches Muster wie
`client_ref`, gleiche Idempotenz. Damit sagt sie, **welcher** Dienst gemeint
ist; aus dem Datum allein ließe sich das seit Web 6.0.0 nicht mehr ableiten, weil
mehrere Diensttage auf einem Kalendertag liegen können.

Die Uhr erfährt dabei weiterhin **nichts über die Einsatzart** — die Einordnung
geschieht ausschließlich im Web. Ein von ihr angelegter Diensttag ist neutral,
bis Standort und Rettungsmittel nachgetragen sind.

**Zwei Phasen heißen jetzt neutral:** Phase 3 „Ausrücken" statt „Abflug",
Phase 7 „Ankunft Klinik" statt „Landung KKH". Dieselbe Uhr läuft auch am NEF, wo
weder das eine noch das andere stattfindet. Der Server sagt das seit Web 6.0.0;
die Uhr zieht damit nach. Für die Übertragung ist es folgenlos — der Vertrag
kennt Nummern, keine Beschriftungen.

**Die Rückfallebene bleibt dauerhaft.** Eine Uhr ohne Kennung (1.7.0 und älter)
funktioniert unverändert weiter; ihre Uploads werden über `(Konto, Datum)`
zugeordnet. Ein Update des Webs darf keine Uhr außer Betrieb setzen, die niemand
aktualisiert hat. Auch der Umstieg **mitten im Dienst** ist abgefangen: Eine
unbekannte Kennung wird an den Diensttag gebunden, an dem der Datensatz schon
hängt, statt einen zweiten anzulegen.

Die Connect-IQ-App-ID ist unverändert — die Uhr aktualisiert sich, sie wird
nicht neu installiert.

### Fehleinsatz nicht mehr in der Tagestabelle

Die Spalte **Fehleinsatz** ist aus der Einsatztabelle der Tagesübersicht
entfallen. Der Haken steht im Einsatz selbst; auswerten lässt er sich
unverändert in der Zeitraum-Übersicht (Kachel „Fehleinsätze") und in der Suche.
Er ist selten gesetzt, und eine Spalte voller leerer Zellen kostet auf der
schmalsten Ansicht Breite, ohne etwas zu sagen.

### Dokumentation

Vier Dateien waren noch auf dem Stand vor 6.0.0 und sind es nicht mehr:

| Datei | Was |
|---|---|
| `JSON-Vertrag.md` | steigt auf **1.3**: `day_ref` samt Zuordnungsregeln und Rückfallebene, neutrale Phasenbeschriftungen, Präfix `d-` |
| `Backup-Format.md` | Nutzlastversion **6**: `day_refs`, `day_crew`, `mission_crew`, `day_capabilities`, `vehicles` samt Rollen und Fähigkeiten, `user_bases`, `base_id` der Stammdaten — und die benannte Ablehnung älterer Nutzlasten |
| `Export-Format.md` | **Berichtigung, nicht nur Ergänzung**: `phase_03_abflug` → `phase_03_ausruecken`, `phase_07_landung_krankenhaus` → `phase_07_ankunft_klinik`, dazu die neuen Spalten und die sieben Besatzungsspalten aus dem Rollenkatalog. Die Feldlisten sind jetzt aus `assets/export.js` erzeugt, damit sie nicht wieder abweichen |
| `Handbuch.md` | der fertige Stand: Diensttag statt Flugtag, Rettungsmittel statt Hubschrauber, Transport und Abfahrtort, die Tabs der Auswertung, das Zusammenführen — und der Unterschied zwischen Statistik (nach Diensttag) und Suche (nach echtem Einsatzdatum) |

Auch `Technik.md` ist nachgezogen: Die Schematabelle beschrieb noch `aircraft`,
`missions.day` und den Tagesschlüssel. **Verwaiste Verweise auf „Flugtag",
„Hubschrauber" und eine Phase 10 gibt es in der Dokumentation nicht mehr** —
mit zwei benannten Ausnahmen: den Kacheln des Luftrettungs-Tabs, die ihre
Flugterminologie behalten, und den Stellen, die ausdrücklich sagen, wie etwas
**früher** hieß.

### Betreiberhinweis

Die drei Dateien `flugtag_neu.php`, `flugtag_loeschen.php` und
`flugtag_datum.php` müssen auf dem Webspace **verschwinden**. Sie sind seit
6.0.0 durch die `diensttag_*.php` ersetzt, arbeiten auf dem alten Datenmodell
und waren über die Adresszeile erreichbar; ein FTP-Abgleich entfernt nicht
zwingend, was im Paket fehlt.

## [Web 6.2.0] — 2026-08-18

**Die Auswertung trennt jetzt nach Art — und die Suche kann danach filtern.**
Dritte von vier Etappen der Notarzt-Erweiterung (Konzept:
`docs/Konzept-Notarzt-Erweiterung.md`). Das Zusammenführen von Diensttagen, die
Uhr und die restliche Dokumentation folgen in Etappe 4.

**Keine Migration.** Wie Etappe 2 arbeitet diese auf dem Schema der 6.0.0. Wer
auf 6.0.0 oder 6.1.0 ist, spielt nur die Dateien ein.

### Zeitraum-Übersicht: drei Tabs, sobald beide Arten vorliegen

Monats- und Jahresübersicht teilen sich nach Art auf — aber nur, wenn es etwas
zu teilen gibt:

| Im Zeitraum | Anzeige |
|---|---|
| nur eine Art | keine Tableiste, Ansicht wie bisher |
| beide Arten | **Gemischt** (aktiv), Luftrettung, Bodengebundener Rettungsdienst |

Der Tab filtert die **ganze** Ansicht: Kacheln, Einsatztabelle und Karte. Er
steht im URL-Fragment hinter dem `#` und lässt sich damit verschicken; ein
Fragment wird nicht an den Server gesendet.

**Der Luftrettungs-Tab ist der heutige Bestand, unverändert** — dieselben zehn
Kacheln, dieselben Beschriftungen, dieselbe Flugterminologie. Für eine rein
luftgebundene Nutzung ändert sich an der Auswertung nichts. Die übrigen Tabs
sprechen neutral und führen acht Kacheln, darunter eine neue: **Fehleinsätze**.
Sie fehlt im Luftrettungs-Tab bewusst, obwohl der Haken auch luftgebunden zur
Verfügung steht; in „Gemischt" zählt sie luftgebundene Fehleinsätze mit, damit
die Zahl vollständig bleibt.

**„Gemischt" enthält auch die Diensttage ohne Zuordnung** und sagt das. Die
Summe der beiden Artentabs ist deshalb kleiner — ohne den Hinweis wäre die
Abweichung nicht erklärbar. Er verlinkt auf die Nachbearbeitung.

### Kacheln und Spalten nur bei passendem Bestand

Die **Windenkacheln** erscheinen ausschließlich, wenn im Zeitraum tatsächlich
Windeneinsätze dokumentiert sind — nicht schon, wenn das Rettungsmittel es
könnte. Damit lässt sich „null Windeneinsätze" nicht mehr von „Winde nicht
eingerichtet" unterscheiden; das ist beabsichtigt, weil eine Dauerkachel mit dem
Wert null nur Platz kostet.

Dieselbe Regel gilt jetzt für **Spalten der Einsatztabelle**: Winde, Bergwacht
und der neue Fehleinsatz erscheinen nur, wenn es sie im Bestand gibt. Für
Bergwacht gibt es weiterhin **keine** Kachel.

### Die Art als Symbol in der Einsatztabelle

🚁 luftgebunden, 🚑 bodengebunden, ◌ ohne Zuordnung — dieselben Zeichen wie in
der Tagesleiste, mit Textalternative. Die Spalte erscheint nur, wenn im Bestand
überhaupt mehr als eine Art vorkommt: Bei reiner Luftrettung stünde in jeder
Zeile dasselbe Zeichen.

### Suche: vier neue Filter

**Art** (bei Standort und Rettungsmittel), **Transportart** und
**NA-Begleitung** (bei Transport) sowie **Fehleinsatz** in einem neuen Block
„Einsatz". Der Block erscheint wie die Blöcke Winde und Bergwacht nur, wenn der
Bestand dazu etwas hergibt.

Die Fragment-Kurznamen der neuen Filter sind `art`, `ta`, `nb` und `fe`. Die
bestehenden sind unverändert — verschickte Links bleiben gültig.

### Export: die letzten zwei Spaltennamen aus der Luftrettung

Die Phasen 3 und 7 heißen serverseitig seit Web 6.0.0 „Ausrücken" und „Ankunft
Klinik". Im Export standen weiterhin `phase_03_abflug` und
`phase_07_landung_krankenhaus`; sie heißen jetzt `phase_03_ausruecken` und
`phase_07_ankunft_klinik`. **Der Rückweg bleibt offen:** Der Import erkennt
beide Schreibweisen, eine Exportdatei von gestern lässt sich unverändert wieder
einlesen.

Ebenfalls neutral: die Erläuterung zu `strecke_m` in `felder.csv` (war
„Flugstrecke") und der **Dateiname** des Exports — er beginnt mit
`einsatzdokumentation_export_` statt `luftrettungsdokumentation_export_`. Ein
Archiv mit bodengebundenen Einsätzen hieß sonst nach einer Rettungsart, die
darin nicht vorkommt.

### Entfernt: drei Seiten, die es seit Web 6.0.0 nicht mehr geben sollte

`flugtag_neu.php`, `flugtag_loeschen.php` und `flugtag_datum.php` wurden mit
Web 6.0.0 durch ihre `diensttag_*`-Nachfolger ersetzt — im Repository lagen sie
aber weiter und wurden mitgeliefert. Sie arbeiten auf dem **alten** Datenmodell
und wären beim Aufruf über die Adresszeile in einen Fehler gelaufen. Verlinkt
war keine von ihnen.

**Für die Betreiberin:** Sie müssen auch auf dem Webspace verschwinden. Ein
FTP-Abgleich entfernt nicht zwingend, was im neuen Paket fehlt.

### Unter der Haube

- `api/range.php` liefert je Einsatz die **Art des Diensttags** und den
  Fehleinsatz-Haken sowie die Diensttage **nach Art aufgeteilt**. Die Tabs
  rechnen damit ohne eine zweite Abfrage; der Divisor „Ø Einsätze / Flugtag"
  teilt im Luftrettungs-Tab nur durch luftgebundene Diensttage.
- Die **Artsymbole stehen an einer Stelle** (`dt_art_symbole()`) und gehen von
  dort an den Browser — wie der Rollenkatalog. Eine zweite Liste in JavaScript
  wäre die Stelle, an der beide beim nächsten Symbolwechsel auseinanderlaufen.
- Die **Kacheln entstehen im Browser** statt fest im HTML zu stehen: Welche es
  gibt und wie sie heißen, hängt am Tab und am Bestand.
- Der Suchindex führt die Klartextfelder der Etappe 2, soweit die Suche sie
  auswertet. Zielklinik-Koordinate und Abfahrtortregel bleiben draußen — nach
  ihnen wird nicht gefiltert.

---

## [Web 6.1.0] — 2026-08-18

**Die Einsatzfelder für beide Arten, und eine Karte auch ohne Uhr.** Zweite von
vier Etappen der Notarzt-Erweiterung (Konzept: `docs/Konzept-Notarzt-Erweiterung.md`).
Auswertung nach Art, Suche und das Zusammenführen von Diensttagen folgen in den
Etappen 3 und 4.

**Keine Migration.** Die Spalten dafür hat die Migration der Web 6.0.0 bereits
angelegt — bewusst in einem Zug, damit die späteren Etappen keine zweite
verlangen. Wer auf 6.0.0 ist, spielt nur die Dateien ein.

### Neue Felder am Einsatz

- **Transport** — Auswahl aus Luft, Boden, Ambulant. „Ambulant" heißt: nicht
  transportiert.
- **NA-Begleitung** — Haken unter der Transportart.
- **Fehleinsatz / Storno / Abbruch** — ein Haken, keine Unterauswahl. Er
  erscheint als eigene Spalte in der Tagestabelle.

**Zielklinik und Schockraum hängen jetzt an der Transportart** und entfallen bei
„Ambulant". Wer dort umschaltet, sieht die Felder verschwinden, **bevor**
gespeichert wird — und der Inhalt wird tatsächlich geleert, nicht nur verborgen.
Das ist der Unterschied zu den Feldern, die eine Art oder Fähigkeit ausblendet:
Die behalten ihren Inhalt, weil er gültig bleibt. Eine Zielklinik hinter
„Ambulant" wäre dagegen ein Widerspruch in den Daten.

### Windenfelder erscheinen nur, wo es eine Winde gibt

Ein bodengebundener Dienst zeigt keine Windenfelder mehr, ein Hubschrauber ohne
Bergwachtkooperation keine Bergwachtfelder. Maßgeblich sind die **eingefrorenen
Fähigkeiten des Diensttags**, nicht die heutigen Stammdaten: Wird der
Windenhaken Jahre später am Rettungsmittel entfernt, verlieren dokumentierte
Einsätze weder Daten noch Anzeige. Ein bereits belegtes Feld bleibt immer
sichtbar.

### Abfahrtort und Luftlinie — eine Karte auch ohne GPS-Aufzeichnung

Fällt die Uhr aus oder wird ohne sie gearbeitet, blieb die Karte bisher leer,
obwohl der Einsatzort bekannt war. Es fehlte der Gegenpunkt.

Zu jedem Einsatz lässt sich jetzt ein **Abfahrtort** bestimmen — als Auswahl,
nicht als Adresseingabe:

| Auswahl | Woher die Koordinate kommt |
|---|---|
| Standort des Diensttags | eingefrorene Standortkoordinate |
| Letzter Einsatzort | Einsatzort des vorherigen Einsatzes desselben Dienstes |
| Letzte Zielklinik | Zielklinik des vorherigen Einsatzes |
| Manueller Ort | eigene Adresssuche, wie beim Einsatzort |

Sind Abfahrtort und Einsatzort bekannt, zeichnet die Karte eine **gestrichelte
Luftlinie** mit Pin an jedem Ende und benannter Länge. Hat die Zielklinik
Koordinaten, verlängert sie sich um diesen dritten Punkt.

Drei Dinge, die sie bewusst **nicht** tut:

- **Ein aufgezeichneter Track hat immer Vorrang.** Trifft er später ein, bleibt
  die Abfahrtortangabe gespeichert und wird nur nicht mehr gezeichnet. Fällt er
  weg, erscheint die Linie wieder.
- **Ohne Einsatzort keine Linie** — auch dann nicht, wenn Abfahrtort und
  Zielklinik beide Koordinaten haben. Diese Verbindung hat nie stattgefunden.
- **Kein Ausweichen.** Fehlt die Koordinate der gewählten Quelle, entsteht keine
  Linie. Es wird nicht stillschweigend eine andere genommen.

**Die Luftlinienlänge fließt in keine Kachel und in keinen Filter.** Eine
Luftlinie und eine gefahrene Strecke sind nicht dieselbe Größe; beides in einer
Summe machte „Einsatzkilometer gesamt" unbrauchbar. Wer ohne Uhr dokumentiert,
hat damit weiterhin keine Kilometerzahlen in der Statistik.

**Sichtbarkeit:** Linie und Einsatzort-Pin erscheinen erst nach Freischalten des
Patientendatenschlüssels — ihr mittlerer Stützpunkt ist der Einsatzort. Der
**Zielklinik-Pin** ist davon ausgenommen: Der Klinikname steht ohnehin
unverschlüsselt am Einsatz, seine Koordinate folgt derselben Einstufung.

### Zielkliniken bekommen Koordinaten — auf drei Ebenen

Zentral durch die Administration, im eigenen Konto, und einmalig am Einsatz.
Wird ein Vorschlag übernommen, dessen Stammdatensatz Koordinaten führt, sind sie
vorbelegt und bleiben überschreibbar. Sie werden **am Einsatz eingefroren** und
nicht über den Namen aufgelöst: Ein umbenannter Stammdatensatz verlöre sie
sonst.

Koordinaten sind überall **freiwillig** (Einsatzort, Abfahrtort, Zielklinik).
Ohne sie entstehen lediglich kein Pin und keine Linie — kein Feld wird dadurch
unbrauchbar. Umgekehrt gilt überall dieselbe Regel: Koordinaten **ohne**
Bezeichnung werden abgewiesen, sonst stünde in den Listen ein Zahlenfragment.

### Adresssuche in der Standortpflege

Die Koordinatenfelder bei Standorten und Zielkliniken waren seit Web 6.0.0
zwei Zahlenfelder zum Eintippen. Jetzt steht dort dasselbe Ortsfeld wie am
Einsatz — mit Adresssuche, Plus-Code-Erkennung und Chip. Das Namensfeld bleibt
davon unberührt: Gesucht wird in einem eigenen Feld daneben, weil „Standort
Kempten" keine Adresse ist und die Suche den Namen sonst überschriebe.

### Export, Import und Sicherung

Die neuen Angaben gehen überall mit: `transport_art`, `na_begleitung`,
`fehleinsatz`, `ziel_lat`, `ziel_lon`, `abfahrt_regel` als Klartextspalten,
`pat_start_adresse`/`_lat`/`_lon` im verschlüsselten Teil. Der eigene
CSV-Export bleibt damit der verlustfreie Rückweg.

**Behoben (älter als diese Etappe):** Das Wiedereinspielen einer Sicherung
übernahm die Spalten aus dem Feldkatalog — Koordinatenspalten, die nicht so
heißen wie ihr Feld, und Spalten außerhalb des Katalogs fielen dabei heraus. In
6.0.0 betraf das nichts, weil es solche Spalten noch nicht gab; ab 6.1.0 wären
Zielklinik-Koordinate und Abfahrtortregel beim Rückweg verloren gegangen.
Zusätzlich stand die normalisierte Besatzung noch in dieser Liste: Eine Datei
mit einem Schlüssel `crew_p1` hätte nicht eine Zeile, sondern die **ganze**
Wiederherstellung scheitern lassen.

### Unter der Haube

- **Das Ortsfeld ist eine Komponente** (`assets/ortsfeld.js` + `ui_ortsfeld()`).
  Es war bis dahin über feste Element-Kennungen verdrahtet — rund 180 Zeilen in
  einer Datei. Sechs Verwendungen wären sechs Fassungen geworden.
- **Der Feldkatalog kann drei Dinge mehr:** ein Ortsfeld (`loc`) mit zwei
  Koordinatenspalten, wertabhängige Unterfelder unter einem Auswahlfeld
  (`show_if` — vorher gab es das nur unter Checkboxen), und Auswahlfelder, deren
  gespeicherter Wert nicht die Beschriftung ist.
- **Die Prüfung optionaler Koordinaten steht an einer Stelle**
  (`pruef_ortspaar`). Sie stand vorher zweimal ausgeschrieben und wäre mit
  dieser Etappe ein drittes und viertes Mal entstanden.

---

## [Web 6.0.0] — 2026-08-17

**Aus dem Flugtag wird der Diensttag.** Die Anwendung dokumentiert jetzt auch
bodengebundene Notarzteinsätze (NEF, NAW) — beides in einer Installation, ein
Konto kann beides mischen, auch am selben Kalendertag. Das Produkt heißt künftig
**Einsatzdokumentation Notarzt**.

Diese Auslieferung ist der **Umbau am Datenmodell samt Codeanpassung** (Etappen
1a und 1b des Konzepts). Einsatzfelder, Auswertung nach Art und das
Zusammenführen von Diensttagen folgen in den Etappen 2 bis 4; Grundlage und
Fortschritt stehen in `docs/Konzept-Notarzt-Erweiterung.md`.

### Vor dem Update lesen

- **Die Migration ist zwingend** (`2026_08_17_notarzt_erweiterung`, läuft über
  `server/update.php`). Vorher eine Sicherung ziehen.
- **Danach ein frisches Backup erstellen.** Sicherungen älterer Formatversionen
  werden nicht mehr eingelesen — die Nutzlast steigt von 5 auf 6, und einer
  alten Datei fehlen Angaben, die sich nicht erraten lassen (Kennung des
  Diensttags, Art, Rollensatz, Standortzuordnung, Uhr-Kennungen). Die Ablehnung
  ist ausdrücklich und benannt.
- **Drei Dateien sind umbenannt** und müssen auf dem Webspace verschwinden:
  `flugtag_neu.php`, `flugtag_loeschen.php`, `flugtag_datum.php` heißen jetzt
  `diensttag_*.php`.
- **Die Uhr-App bleibt unverändert** und funktioniert weiter. Sie kennt die
  Einsatzart nicht und braucht sie nicht zu kennen; ihre Uploads landen über die
  dauerhaft bestehende Rückfallebene `(Konto, Datum)` an einem Diensttag.
- **Blockiert die Migration**, steht in `days.crew` noch ein Freitext aus der
  Zeit vor den Rollenspalten. Sie verweigert dann und meldet es, statt zu
  entscheiden — der Inhalt gehört von Hand in die neue Struktur übertragen.

### Der Diensttag löst sich vom Kalendertag

Ein Diensttag hat jetzt eine eigene Kennung, echte Start- und Endzeiten und
**jeder Start erzeugt einen eigenen**. Ein Hubschrauberdienst am Tag und ein
NEF-Nachtdienst am Abend sind damit zwei Diensttage an einem Datum. Einsätze
hängen an der Kennung, nicht mehr am Datum.

Folgen im Alltag:

- Die Leiste links listet Diensttage. Liegen mehrere auf einem Kalendertag,
  steht die Uhrzeit des Dienstbeginns dabei; sonst nicht.
- Die Art erscheint als Symbol am Rettungsmittelnamen — 🚁 luftgebunden,
  🚑 bodengebunden, ◌ noch nicht zugeordnet. Jedes Symbol trägt eine
  Textalternative.
- Beim Umdatieren eines Diensttags gibt es **keine Kollisionsprüfung mehr**: Ein
  belegtes Zieldatum war die Folge des alten Tagesschlüssels und ist jetzt der
  vorgesehene Fall.
- „Einsatz verschieben" wählt einen **vorhandenen Diensttag** aus einer Liste,
  statt ein Datum entgegenzunehmen. Angelegt wird dort nichts mehr.
- **Statistik und Suche gehen bewusst auseinander:** Die Zeitraumübersicht rechnet
  nach Diensttag, die Einsatzsuche filtert nach dem echten Einsatzdatum. Ein
  Einsatz um 01:30 eines Dienstes vom Vortag zählt zum Vortag, ist in der Suche
  aber unter seinem eigenen Datum zu finden. Die Suche zeigt beide Daten.

### Der Standort ist der Anker der Stammdaten

Rettungsmittel, Besatzungs-Vorbelegungen, Zielkliniken, weitere Rettungsmittel
und Bergwacht-Bereitschaften gehören zu **genau einem Standort**. Eine
standortübergreifende Ebene gibt es nicht: Dieselbe Zielklinik an zwei
Standorten wird zweimal angelegt. Der Preis ist Doppelpflege, der Gewinn ein
Modell mit einer Regel statt mit zwei.

Die Standortdaten sind entsprechend gegliedert — erst die Standorte (eigene
anlegen, zentrale auswählen), dann je Standort ein Block mit seinen fünf
Datenarten. Zentrale Standorte erscheinen erst in den Auswahllisten, wenn man
sie auswählt.

Standorte und Zielkliniken haben jetzt optionale Koordinaten. Sie sind
freiwillig; ohne sie entsteht lediglich kein Pin. Die Adresssuche wie beim
Einsatzort folgt in einer späteren Etappe.

### Das Rettungsmittel entscheidet über Rollen und Felder

Aus dem Hubschrauber ist das **Rettungsmittel** geworden, mit einer Art
(luft- oder bodengebunden), angehakten Rollen und — nur luftgebunden — den
Fähigkeiten Winde und Bergwacht.

- Luftrollen unverändert: Pilot 1, Pilot 2, HEMS-TC, Flugretter, Sonstige.
- Bodenrollen: Fahrer, Praktikant, Sonstige. „Sonstige" ist dieselbe Rolle.
- Die Notärztin ist keine Rolle — sie ist die Nutzerin.

### Alles Abgeleitete wird beim Anlegen eingefroren

Art, Rollensatz, Fähigkeiten, Bezeichnungen und Standortkoordinaten werden beim
Zuordnen in den Diensttag **kopiert**. Wird ein Rettungsmittel später umbenannt,
bearbeitet oder gelöscht, ändert sich an bereits dokumentierten Diensttagen
**nichts** — auch nicht bei einem Tippfehler im Namen. Wer eine alte Bezeichnung
korrigieren will, tut das am Diensttag selbst.

Das gilt in beide Richtungen: Wird der Windenhaken Jahre später entfernt,
verlieren alte Einsätze ihre Windendokumentation nicht.

### Ein Diensttag ohne Zuordnung funktioniert

Ein von der Uhr angelegter Diensttag ist zunächst **neutral**: keine Art, keine
Rollen, keine artabhängigen Felder. Zeiten, Phasen, Track und
Reanimationsdokumentation werden trotzdem vollständig erfasst. Wird die
Zuordnung nachgetragen, erscheinen Rollen und Felder — ohne dass zuvor Erfasstes
verloren geht.

### Nachbearbeitung: die zwei Zuordnungen, die niemand erraten kann

Eine neue Seite (`nachbearbeitung.php`) zeigt, was die Migration nicht ableiten
konnte: Diensttage ohne Standort oder Rettungsmittel und Stammdatensätze ohne
Standort. Sie erscheint in der Leiste links, **solange etwas offen ist**, und
verschwindet danach von selbst. Erst wenn kein Stammdatensatz mehr offen ist —
in keinem Konto —, macht eine Administratorin den Standortbezug dort verbindlich
(`base_id` bekommt `NOT NULL`). Danach stimmen aktualisierte Installation und
Neuinstallation vollständig überein.

### Weiteres

- **Phasenbeschriftungen neutral:** Phase 3 heißt „Ausrücken" (war „Abflug"),
  Phase 7 „Ankunft Klinik" (war „Landung Krankenhaus"). Nummerierung und
  Bedeutung unverändert; die Uhr folgt in einer späteren Auslieferung und zeigt
  bis dahin die alten Wörter. Rein kosmetisch — übertragen werden Nummern.
- **Einsatztabelle, Suche und Export sprechen neutral.** „Flug km" heißt „km",
  „Hubschrauber" heißt „Rettungsmittel", „Flugtag" heißt „Diensttag". Die
  Kacheln der Zeitraumübersicht behalten die Flugterminologie — sie werden in
  Etappe 3 nach Art geteilt.
- **Exportformat:** Das Blatt „Flugtage" heißt „Diensttage" und führt Kennung,
  Dienstbeginn, Dienstende, Art und Fähigkeiten. Die Besatzungsspalten entstehen
  aus dem Rollenkatalog; die fünf Flugrollen behalten ihre Spaltennamen, damit
  der verlustfreie Rückweg über den CSV-Import bestehen bleibt. Alte Kopfzeilen
  („flugtag", „hubschrauber", „Flugkilometer") werden beim Import weiterhin
  erkannt.
- **Import:** Je Kalendertag wird höchstens ein Diensttag neu angelegt. Aus einer
  Tabelle lässt sich nicht ableiten, ob zwei Einsätze desselben Datums zu einem
  oder zu zwei Diensten gehören; wer sie trennen will, tut das danach über
  „Aktionen → Verschieben".
- **Uhr-Kennungen:** Der Diensttag kann mehrere tragen (`day_refs`). Damit findet
  ein späterer Upload auch einen Diensttag, der inzwischen in einen anderen
  aufgenommen wurde — und ein Backup bringt die Kennungen mit zurück, sodass
  nach einer Wiederherstellung kein Diensttag doppelt entsteht.
- **Zwei kleine Berichtigungen am Rand**, beide älter als diese Auslieferung:
  Die Adminsicherung zählte die Ruhesegmente unter dem falschen Schlüssel und
  meldete deshalb immer 0. Und die Wartungsseite schrieb bei jedem Aufruf zwei
  PHP-Warnungen je verbuchter Migration ins Fehlerprotokoll — angezeigt wurde
  trotzdem das Richtige, deshalb war es nie aufgefallen.

## [Web 5.10.0] — 2026-08-17

Feinschliff an der Oberfläche: **weniger Spalte, weniger Filter, weniger
Zeilen — und zwei Auskünfte, die es vorher erst nach dem Absenden gab.** Keine
Schemaänderung, keine Migration.

### Die Spalte „abw. Crew" ist wieder weg

Sie war seit Web 5.4.0 in der Tagesübersicht zu sehen. Im täglichen Gebrauch
trug sie nichts bei: An den allermeisten Tagen steht der Haken in keiner
einzigen Zeile, und Breite kostete sie trotzdem — in einer Tabelle, die auf
schmalen Geräten ohnehin knapp ist. Wo die abweichende Besatzung wirklich
interessiert, steht sie vollständig: in der Einsatzansicht unter „Besatzung",
mit „(abw.)" an der betroffenen Rolle. **Das Feld selbst bleibt unverändert**,
ebenso im Export und im Backup.

Umgesetzt durch das Streichen zweier Schlüssel in `mission_fields.php`.
Tabellenkopf, Zeilenaufbau, Sortierung und der `SELECT` in `api/day.php` zogen
von selbst nach — die Gegenprobe zu Backlog Nr. 10, diesmal rückwärts.

Bei der Gelegenheit entfernt: die Spaltenbreiten nach Position
(`#missions th:nth-child(…)`) im Stylesheet. Sie waren wirkungslos, weil die
Klassenregeln später stehen, aber sie zählten Spalten ab — genau die Sorte
Regel, die beim Streichen einer Spalte still auf die falsche rutscht.

### Flugtag-Aktionen stehen jetzt oben rechts

„Datum ändern" und „Tag löschen" standen als zwei Schaltflächen unter der
Tabelle, in einer Reihe mit „+ Einsatz nachtragen" — das Alltagsgeschäft neben
zwei Eingriffen in den Bestand. Auf der Einsatzseite ist diese Trennung seit
Web 5.6.0 gezogen; die Flugtagübersicht folgt ihr jetzt mit **demselben
Bauteil**: ein Menü **Aktionen** oben rechts, vollständig mit der Tastatur
bedienbar, Escape schliesst.

Das Verhalten (Schliessen daneben und mit Escape) steht dafür neu in
`assets/aktionsmenu.js` statt zweimal in je einem `<script>`. Es bindet jedes
`details.aktionsmenu` der Seite von selbst.

### Beim Verschieben steht jetzt da, wohin der Einsatz wandert

Unter dem Datumsfeld in `einsatz_verschieben.php` steht ab sofort, **welcher
Flugtag am gewählten Datum liegt** — Maschine, Standort und Zahl der Einsätze —
oder dass dort noch keiner angelegt ist und einer entsteht. Liegt am Zieldatum
ein Flugtag im **Papierkorb**, sagt die Seite das vorher: Er belegt sein Datum
weiterhin, und das Verschieben würde abgelehnt.

Ausdrücklich benannt ist dabei, was vorher offenblieb: Je Kalendertag gibt es
**genau einen** Flugtag (`days` trägt `UNIQUE KEY uq_user_day`). Das Datum
bestimmt den Zieltag also eindeutig; eine Auswahl zwischen mehreren Tagen
desselben Datums kann es nicht geben.

### Ein belegtes Zieldatum meldet sich vor der Rückfrage, nicht danach

Die Kollisionsprüfung der Umdatierung (E2) sass allein in
`tz_tag_datum_aendern()` — also hinter dem Absenden **und** hinter der
Rückfrage „Alle Zeitstempel wandern mit. Fortfahren?". Wer sie bejahte, bekam
als Antwort, dass gar nichts geschehen ist. `flugtag_datum.php` sagt jetzt
unter dem Feld, ob das gewählte Datum frei ist.

Beide Auskünfte sind **rein anzeigend**. Der Server bleibt massgeblich: Die
Listen sind gedeckelt und veralten in dem Augenblick, in dem in einem zweiten
Fenster etwas angelegt wird. Wo der Deckel gegriffen hat, sagt die Auskunft
nichts, statt etwas Falsches zu sagen.

### Winde und Bergwacht erscheinen nur, wenn es sie im Bestand gibt

Beides ist Sache eines Teils der Standorte. Wer nie windet, hatte trotzdem
sechs Winden-Felder in der Filterspalte stehen — Filter, die dauerhaft null
Treffer ergeben. Die beiden Blöcke fallen jetzt weg, wenn kein einziger Einsatz
des Bestandes sie trägt. Geprüft wird der **gesamte** Bestand, nicht die
aktuelle Trefferliste, damit die Spalte beim Tippen nicht hüpft. Setzt ein
geteilter Link einen Filter aus einem dieser Blöcke, bleibt er sichtbar — ein
gesetzter, aber unauffindbarer Filter wäre das schlechtere Ergebnis.

### Die Trefferliste der Suche zeigt 200 Zeilen auf einmal

Vorher gab es keine Grenze: Beim Öffnen stand der gesamte Bestand als Tabelle
da, und **jeder Tastendruck** im Suchfeld baute ihn neu auf. Bei einigen tausend
Einsätzen wurde daraus eine spürbare Pause zwischen Anschlag und Anzeige —
bezahlt für Zeilen, die niemand ansieht.

Begrenzt ist nur die **Anzeige**. Gefiltert, sortiert und gezählt wird
weiterhin über den vollständigen Bestand; die Zeile über der Tabelle nennt
unverändert die wahre Trefferzahl und dazu, wie viele davon gerade stehen.
Unter der Tabelle liegt das Nachladen („Weitere 200 anzeigen" / „Alle N
anzeigen") — sichtbar nur, wenn wirklich etwas fehlt. Ein Sortierwechsel nimmt
eine erweiterte Ansicht nicht zurück; ein neues Filterergebnis fängt wieder bei
der ersten Seite an.

Die Seitengrösse ist eine Option von `EdMissionTable.erzeuge()`
(`assets/missiontable.js`). Ohne sie zeichnet die Tabelle wie bisher jede Zeile
— die Zeitraum-Übersicht ist deshalb unverändert.

## [Web 5.9.0] — 2026-08-16

Block A8 der Verbesserungsrunde Web: **Admin-Sicherungen als Rückfallebene**.
Administration kann Konten sichern und wiederherstellen, ohne Einblick in die
Daten zu erhalten. **Schemaänderung mit Migration** — bitte nach dem Aufspielen
die Wartung aufrufen.

### Jedes Konto bekommt eine Kontokennung

Neue Spalte `users.account_key`, einmalig bei der Kontoanlage vergeben und
danach unveränderlich. Sie ist der Ordnername der Sicherung. Weder die
E-Mail-Adresse (sie ändert sich, sie ist personenbezogen, sie bringt
Zeichenprobleme mit) noch `users.id` (der Zähler kann nach einem Serverneustart
zurückfallen — ein neues Konto könnte den Ordner eines gelöschten erben) taugen
dafür. Die Migration trägt die Kennung für alle Bestandskonten nach; ein
zweiter Lauf ändert nichts.

### Sicherungen liegen im Dateisystem, nicht in der Datenbank

Unter `server/sicherungen/`, ein Ordner je Konto. Eine Sicherung, die im selben
Behälter liegt wie das Gesicherte, ist keine Rückfallebene — und ein Paket liegt
bei größeren Beständen im zweistelligen MB-Bereich, während `max_allowed_packet`
auf geteiltem Webspace oft bei 16 MB festliegt.

Zwei Schranken gegen den Abruf über den Browser: eine `.htaccess` mit
`Require all denied`, die bei jedem Schreibzugriff nachgelegt wird, und der
Ordnername selbst — die zufällige Kontokennung ist nicht zu erraten. **Der
Ordner steht in der `exclude`-Liste des Deploys**; ohne diesen Eintrag hätte die
nächste Auslieferung alle Sicherungen gelöscht.

### Auslösung ausschliesslich von Hand

Auf dieser Installation läuft kein Cron. Sichern lässt sich je Konto, für eine
Auswahl oder für alle in einem Zug. Je Konto bleiben höchstens **drei**
Sicherungen; die vierte verdrängt die älteste. **Keine Altersgrenze** — sie
würde genau die letzte vorhandene Sicherung entfernen, wenn lange keine neue
erzeugt wurde, also in der Lage, in der man sie braucht. Eine Erinnerung mit
einstellbarem Intervall erscheint in der Administration.

### Zurückspielen: der Vergleich der Kennungen entscheidet

- **Kennungen stimmen überein** — dasselbe Konto besteht weiter. Administration
  spielt unmittelbar ein, ohne Zutun der NutzerIn.
- **Kennungen weichen ab** — das Konto wurde neu aufgesetzt. Unmittelbares
  Einspielen ist **gesperrt**; die geschützten Angaben hängen an einem
  Inhaltsschlüssel, den nur der Wiederherstellungsschlüssel öffnet. Administration
  gibt die Sicherung stattdessen für ein Zielkonto frei, und die NutzerIn spielt
  sie im eigenen Backup-Bereich ein — ihr Browser schlüsselt dabei um. Eine noch
  nicht eingelöste Freigabe lässt sich widerrufen.

Vor jeder Rückspielung ist die E-Mail-Adresse des **Zielkontos** abzutippen,
serverseitig geprüft. Das Risiko ist nicht Datenverlust — eine Rückspielung
ergänzt und ersetzt nicht —, sondern das Einspielen fremder Daten in ein
falsches Konto. Die Rückmeldung nennt angelegte und übersprungene Einträge, die
übersprungenen nach Gründen getrennt.

### Verwaiste Sicherungen sind der eigentliche Anwendungsfall

Die Übersicht führt zwei Quellen zusammen: bestehende Konten aus der Datenbank
und **Ordner, zu deren Kennung kein Konto mehr existiert**. Genau diese wären in
einer Liste aus der Datenbank unsichtbar — dabei sind sie der Grund, aus dem es
die Funktion gibt. Name und Adresse stammen dort aus der Begleitdatei im Ordner.
Ein Ordner ohne lesbare Begleitdatei wird mit Hinweis aufgeführt, nicht
stillschweigend übergangen.

### Löschen, mit abgestufter Härte

Bleibt danach noch eine weitere Sicherung desselben Kontos, genügt die übliche
Rückfrage — die Löschung ist folgenlos. Ist es die letzte oder gehört sie zu
einem verwaisten Ordner, ist zusätzlich die E-Mail-Adresse abzutippen. Ist die
Begleitdatei unlesbar, tritt eine ausdrückliche Bestätigung an ihre Stelle.
Kein Papierkorb: Ein Papierkorb für Sicherungen wäre eine weitere Kopie
derselben Daten, die genau dann noch existiert, wenn jemand sie loswerden wollte.

### Die Kontolöschung entscheidet ausdrücklich über die Sicherungen

Die Löschmaske hat ein Auswahlfeld „Sicherungen dieses Kontos" bekommen,
vorbelegt mit **mitlöschen**. Der Warntext sagt nicht länger unbedingt zu, dass
nach der Löschung nichts mehr lesbar ist, sondern bindet diese Aussage an die
getroffene Wahl — sonst wäre sie unwahr geworden, sobald Sicherungen existieren.
Lassen sich die Sicherungen nicht entfernen, wird das Konto **nicht** gelöscht.

### Grenzen — ausdrücklich benannt

Das Verfahren ist eine Rückfallebene gegen selbstverschuldete Probleme im Konto,
**kein Schutz gegen Kontoverlust**. Ohne den Wiederherstellungsschlüssel ist ein
neu aufgesetztes Konto nicht wiederherstellbar. Beides steht im Handbuch.

### Kleinigkeiten

- Administration sieht zu keinem Zeitpunkt Klartext: In der Sicherung stecken
  die geschützten Angaben als Chiffretext, die Oberfläche zeigt nur Zeitpunkt,
  Anzahl und Größe.
- Die Kontokennung erscheint an keiner Stelle der Oberfläche — auch nicht in
  verborgenen Formularfeldern. Dort steht ein Handgriff, aus dem sich die
  Kennung nicht zurückrechnen lässt.
- `schema.sql` führte zwei Migrationen nicht in seiner Liste
  (`2026_08_13_zeitzonen_umstellung`, `2026_08_14_geraetename_ohne_datum`),
  obwohl der Kopf von `update.php` es verlangt. Nachgetragen.

## [Web 5.8.0] — 2026-08-16

Block A9 der Verbesserungsrunde Web. Die Einschränkung des Exports wird von den
Patientendaten auf **personenbezogene Angaben insgesamt** erweitert — und der
Rückweg über den Import so abgesichert, dass eine solche Datei im Bestand nichts
löscht. Keine Schemaänderung, keine Migration.

### Der Haken heißt jetzt „Personenbezogene Angaben einschließen"

Der Export kannte genau eine Schranke, und sie hieß „Patientendaten
einschließen". Alles andere ging immer mit — auch Angaben, die nicht dem
Patienten gehören, aber trotzdem einer Person. Ohne den Haken fehlen jetzt
zusätzlich:

- die **Besatzung**, die des Flugtags und die tatsächliche des Einsatzes,
  auch im Blatt *Flugtage*,
- **bw_info** („Bergwacht: Namen / Infos") und **other_ema** (anderer Notarzt),
- die **Notizen** von Einsatz und Flugtag,
- die **Koordinaten der Phasen**, die **Höhe des Einsatzortes** und die
  **GPX-Tracks**.

### Der Einsatzort stand bisher in einer anderen Spalte

Das war der Anlass. Phase 4 ist „Ankunft Einsatzort", Phase 5 „Ankunft
PatientIn" — diese Koordinaten *sind* der Einsatzort, und sie waren nicht
eingeschränkt, während `pat_ort_lat/lon` es war. Ein Export „ohne
Patientendaten" nannte den Ort also trotzdem. Dasselbe galt für die GPX-Spuren,
die dort enden, und für die aus dem Ort gerechnete Höhe.

### Drin bleiben, jeweils einzeln entschieden

Transportziel und Bergwacht-Einheit (Einrichtungen, keine Personen), weitere
Rettungsmittel (Organisationskennungen), der Verlauf einer Reanimation ohne
Angabe, wen sie betraf, die Zeitpunkte der Phasen (sie tragen Alarmzeit, Endzeit
und Dauer) und der Haken „abweichende Besatzung" — er sagt nur, *dass* sie
abwich. Die Entscheidungen stehen im Handbuch, damit sie nicht als Versehen
gelesen werden.

### Der Dateiname sagt, was drin ist

`mit-pers` bzw. `ohne-pers` statt `mit-pat`/`ohne-pat`. Der Dateiname ist die
Angabe, die man noch sieht, wenn die Datei längst in einem fremden Ordner liegt
— `ohne-pat` an einer Datei mit Besatzungsnamen wäre dort die falsche Auskunft.
Ältere Dateien behalten ihren Namen; für sie war er richtig.

### Ein Rückimport löscht keine Besatzung mehr

Beim Überschreiben eines vorhandenen Einsatzes setzte der Import bisher jede
Spalte unbedingt — ein leerer Wert aus der Datei kam als leeres Feld im Bestand
an. Wer eine Datei ohne personenbezogene Angaben zurückspielte und dabei
„überschreiben" wählte, hätte Besatzung, Notizen, Bergwacht-Infos und den
anderen Notarzt verloren. Diese Felder werden jetzt nur noch gesetzt, wenn die
Datei etwas liefert; die Phasenkoordinaten überleben das Ersetzen ebenfalls.
Der Preis: Ein solches Feld lässt sich per Import nicht mehr gezielt leeren —
das geht im Einsatzformular.

### Kleinigkeiten

- Die Schranke wirkt serverseitig: `api/export_data.php` liefert die
  betroffenen Felder gar nicht erst aus und weist Trackanfragen ohne den Haken
  ab. `assets/export.js` entfernt sie ein zweites Mal, bevor eines der Profile
  den Bestand sieht.
- `felder.csv` im CSV-Archiv hat eine Spalte `personenbezogen` bekommen. Damit
  ist am Archiv selbst ablesbar, welche leeren Zellen leer *gemacht* wurden.
- Die GPX-Wahl verschwindet ohne den Haken, mit Angabe des Grundes an ihrer
  Stelle.
- Der Hinweis unter dem Passwortkästchen (Web 5.7.0) sagte, die Datei sei ohne
  Patientendaten „trotzdem personenbezogen". Das stimmt nicht mehr; er benennt
  jetzt, was tatsächlich bleibt — Betriebsangaben wie Zeiten, Transportziele und
  Rettungsmittel. Vorbelegt bleibt der Schutz.

## [Web 5.7.0] — 2026-08-16

Sechster Block der Verbesserungsrunde Web (A6 „Einstellungen, Import, Export").
Vier kleinere Unstimmigkeiten auf den Nutzerseiten. Keine Schemaänderung, keine
Migration.

### Der Pfadverweis auf der Backup-Seite ist weg

Der Text zum Backup nannte `docs/Backup-Format.md` als Formatbeschreibung. Für
Nutzende ist dieser Pfad nicht erreichbar — er zeigt in das Quellverzeichnis
des Projekts, nicht auf den Server. Der Satz ist entfallen; die Datei und die
Verweise darauf im Code bleiben, sie richten sich an Entwicklerinnen.

### Kästchen und Text stehen auf einer Linie

Auf der Backup-Seite saß das Kästchen von „Mein Kontopasswort verwenden" höher
als der Text daneben. Die Ursache war nicht ein falscher Wert, sondern ein
fehlender: Die Klasse `.check` stand im Markup, hatte im Stylesheet aber
**überhaupt keine Regel**. Sie teilt sich jetzt eine Regel mit `.checklabel`,
die dasselbe schon richtig machte — beide stehen nebeneinander, damit sie nicht
wieder auseinanderlaufen. Wirkt überall, wo `.check` verwendet wird.

### Beim Export verschwinden die Zeitraumfelder, statt auszugrauen

Wählt man beim Export „Alles", waren die Felder „Von" und „Bis" bisher sichtbar
und ausgegraut. Ein Feld, das dasteht und sich nicht bedienen lässt, wirft die
Frage auf, was daran kaputt ist — beantwortet ist sie eine Zeile darüber, in
der Zeitraumwahl. Jetzt verschwindet die Zeile. Die Felder bleiben im Formular
und bleiben zusätzlich `disabled`, damit die Auswertung unverändert bleibt und
kein verborgenes Feld eine Browserprüfung auslösen kann, die niemand sieht.

### Der Passwortschutz ist vorbelegt

„Mit Passwort schützen" ist beim Öffnen der Exportseite angehakt, die
Passwortfelder sind sichtbar. In dieser Datei stehen die geschützten Angaben im
Klartext; der Schutz ist der Normalfall. Abwählen bleibt möglich — die
Vorbelegung dreht nur um, welche der beiden Entscheidungen man bewusst treffen
muss.

**Kein selbsttätiges Abschalten.** Wird „Patientendaten einschließen"
abgewählt, erscheint stattdessen ein Hinweis: Die Datei enthält dann keine
Patientendaten, ist aber weiterhin personenbezogen — Besatzungsnamen,
Bergwacht-Angaben, anderer Notarzt, Notizen und über die Phasen die Koordinaten
des Einsatzortes bleiben enthalten. Wer den Schutz weglässt, soll das
entscheiden und nicht als Nebenwirkung eines anderen Hakens erleben.

### Geändert

* `server/einstellungen.php` — Pfadverweis entfernt.
* `server/assets/style.css` — `.check` teilt sich die Regel mit `.checklabel`.
* `server/import.php` — Kennung für die Zeitraumzeile, Vorbelegung des
  Passwortschutzes, Hinweistext.
* `server/assets/export.js` — Zeitraumzeile ausblenden, Hinweis steuern.
* `server/version.php`, `docs/Handbuch.md`.

## [Web 5.6.0] — 2026-08-16

Fünfter Block der Verbesserungsrunde Web (A5 „Tages- und Einsatzzuordnung").
Keine Schemaänderung, keine Migration. Der Block macht Fehlzuordnungen
korrigierbar — und ist zugleich Vorarbeit für den Betrieb mit mehreren
Diensten je Kalendertag.

### Zwei Fehler, die bisher niemand beheben konnte

Bis hierher war die Tageszugehörigkeit endgültig. Das Datumsfeld im
Einsatzformular ist beim Bearbeiten `readonly`, und für das Datum eines ganzen
Flugtags gab es überhaupt keinen Weg. Wer sich beim Nachtragen vertan hatte
oder wessen Uhr falsch gestellt war, konnte den Fehler nur durch Löschen und
neu Erfassen beseitigen — samt GPS-Track, den es dann nicht mehr gibt.

Die beiden Fälle sehen sich ähnlich und sind es ausdrücklich nicht:

* **Ein einzelner Einsatz gehört zum falschen Tag** — seine Uhrzeiten stimmen.
  Typisch beim Nachtragen eines Dienstes über Mitternacht. Dafür gibt es jetzt
  auf der Einsatzseite **Aktionen → Verschieben**. Die Uhrzeiten bleiben
  unangetastet.
* **Die Uhr war falsch gestellt** — dann sind Datum *und* Uhrzeit falsch.
  Dafür gibt es in der Tagesübersicht **Datum ändern**. Hier wandern alle
  Zeitstempel mit.

Beide Seiten benennen den Unterschied und verweisen aufeinander. Wer den
falschen Weg öffnet, sieht das, bevor er handelt.

### Einsatz verschieben

Eine eigene Seite statt eines still freigeschalteten Datumsfeldes: Die
Nebenwirkung — der Einsatz wechselt die Tageszugehörigkeit — wäre an einem
plötzlich beschreibbaren Feld nicht zu sehen.

Existiert am Zieldatum noch kein Flugtag, wird einer angelegt, mit der
Standard-Vorbelegung für Standort und Maschine. Alles andere zwänge dazu, vor
dem Verschieben von Hand einen Tag anzulegen. Liegt der Zieltag im Papierkorb,
wird abgelehnt statt still wiederhergestellt — dieselbe Haltung wie beim
Speichern eines Flugtags.

Ein späterer Upload derselben Uhr zieht den Einsatz **nicht** zurück: Der
Upsert in `ingest.php` schreibt die Spalte `day` nicht mit.

### Datum eines Flugtags ändern

Verschoben werden `days`, `missions` und `rest_segments` samt allem, was daran
hängt: Phasenzeiten, Reanimationsprotokolle und die GPS-Spurpunkte — letztere
in der Unix-Epoche statt als Zeitstempel, und deshalb leicht zu übersehen.
Alles davon geschieht in **einer** Transaktion; ein Abbruch in der Mitte
hinterlässt den Tag unverändert am alten Datum.

Verschoben wird um den Abstand der beiden **Ortsmitternachte**, nicht um
`Tage × 86400` Sekunden. Der Unterschied wird genau dann sichtbar, wenn die
Verschiebung über eine Zeitumstellung läuft: Eine feste Sekundenzahl verschöbe
dann jede dokumentierte Uhrzeit um eine Stunde. So bleibt sie stehen — und die
abgelesene Uhrzeit ist das, was jemand dokumentiert hat.

Liegt am Zieldatum bereits ein Einsatztag, wird abgelehnt. Zusammengeführt wird
nicht: Das würfe Fragen zu widersprüchlichen Tagesangaben auf (Standort,
Maschine, Besatzung), die sich nicht automatisch beantworten lassen. Ein Tag im
**Papierkorb** belegt sein Datum dabei ebenso — `days` trägt einen eindeutigen
Schlüssel auf (Konto, Datum), und ein übergangener Papierkorb-Eintrag wäre ein
Datenbankfehler statt einer lesbaren Meldung.

Vor der Ausführung nennt die Seite, was betroffen ist: Zahl der Einsätze, der
Ruhesegmente, der Trackpunkte — Papierkorb-Einträge getrennt ausgewiesen, denn
sie wandern mit. Danach kommt die übliche Rückfrage.

### Ein Menü statt zweier Schaltflächen

Die Einsatzseite trug oben rechts **Bearbeiten** und **Löschen**. Mit
„Verschieben" wären es drei Schaltflächen nebeneinander geworden; stattdessen
gibt es jetzt ein Menü **Aktionen** mit diesen drei Einträgen. Gebaut aus
`<details>`/`<summary>`, damit die Tastaturbedienung vom Browser kommt und
nicht halb nachgebaut wird: Tabulator auf den Kopf, Enter oder Leertaste
öffnet, Tabulator läuft weiter durch die Einträge, Escape schließt und gibt den
Fokus zurück.

### Geändert

* `server/tageszuordnung_lib.php` — neu: beide Handlungen samt Prüfungen und
  Transaktion.
* `server/einsatz_verschieben.php`, `server/flugtag_datum.php` — neu.
* `server/einsatz.php` — Aktionsmenü, Rückmeldung nach dem Verschieben.
* `server/index.php` — „Datum ändern" in den Tagesaktionen, Rückmeldung nach
  dem Umdatieren.
* `server/assets/style.css` — Darstellung des Aktionsmenüs.
* `server/version.php`, `docs/Technik.md`, `docs/Handbuch.md`.

## [Web 5.5.0] — 2026-08-16

Vierter Block der Verbesserungsrunde Web (A4 „Einsatzformular"). Keine
Schemaänderung, keine Migration — `resus_sessions` und `resus_events` gibt es
seit Web 2.x, sie waren im Formular nur nicht erreichbar.

### Reanimationszeiten lassen sich nachtragen

Bis hierher konnten `resus_sessions` und `resus_events` **ausschließlich** von
der Uhr befüllt werden. Wer einen Einsatz von Hand nachtrug — weil die Uhr
nicht lief, weil sie ausfiel, weil der Einsatz aus einem Import stammt —,
konnte die Reanimation nicht dokumentieren. Genau die Einsätze, bei denen am
meisten passiert, waren die, bei denen am wenigsten zu erfassen war.

Das Formular hat jetzt einen Abschnitt **Reanimation**: Reanimationsbeginn und
darunter beliebig viele Ereignisse aus der bekannten Liste (Zugang,
Adrenalingabe, Rhythmuskontrolle, Defibrillation, Intubation, Amiodaron,
Sonographie, ROSC, Tod). Mehrere Reanimationen je Einsatz sind möglich — das
Datenmodell sah sie ohnehin vor. Jede Reanimation und jedes Ereignis lässt sich
einzeln wieder entfernen; beim Bearbeiten steht der vorhandene Bestand
vorbelegt da.

Zwei Regeln, beide von den Phasen übernommen: Eine Zeile ohne Uhrzeit ist kein
Ereignis und wird nicht gespeichert, und eine Zeit, die vor ihrer Bezugszeit
liegt, gehört dem Folgetag. Bezug ist beim Beginn die Alarmierung, bei jedem
Ereignis das vorhergehende — eine Reanimation von 23:50 bis 00:20 landet damit
richtig herum in der Datenbank. Gespeichert wird UTC, wie überall.

In der Einsatzansicht sind die so eingetragenen Zeiten von denen der Uhr nicht
zu unterscheiden: Es sind dieselben Tabellen und derselbe Endpunkt. Ein über
das Formular gespeicherter Einsatz trägt außerdem `manual = 1`; eine
nachliefernde Uhr überschreibt die Eingaben also nicht. (Backlog Nr. 1)

### Abweichende Besatzung nimmt jetzt Freitext an

Die fünf Felder unter „Abweichende Besatzung" waren reine Auswahlfelder über
die Vorbelegungen. Wer aushilft, steht dort aber oft nicht — und genau dieser
Fall ist der Anlass, überhaupt eine abweichende Besatzung einzutragen. Aus den
Auswahlfeldern sind Textfelder mit Vorschlagsliste geworden: Die Vorbelegungen
der jeweiligen Rolle erscheinen weiterhin, persönliche wie zentrale, aber jeder
andere Name lässt sich ebenfalls eintragen.

Ein Nebeneffekt, der vorher ein Ärgernis war: Ein gespeicherter Name, der
später aus den Stammdaten verschwindet, kann jetzt gar nicht mehr verloren
gehen — er steht einfach im Feld. Am Rollenfilter ändert sich nichts: Ein Feld,
das die Maschine des Flugtags nicht vorsieht, bleibt versteckt, ein bereits
belegtes bleibt sichtbar.

### Abbrechen — in beiden Formularen, in beiden Zuständen

Das Einsatzformular bot einen Abbrechen-Weg **nur** beim Bearbeiten. Wer einen
Einsatz nachtrug und es sich anders überlegte, kam nur über die Seitenleiste
oder den Zurück-Knopf des Browsers heraus. Beide Formulare — Einsatz und
Flugtag — haben den Weg jetzt in jedem Zustand.

Die Rückfrage erscheint **nur, wenn tatsächlich etwas eingegeben wurde**. Ein
unverändertes Formular zu verlassen fragt nichts; eine Rückfrage, die immer
kommt, wird weggeklickt und schützt dann auch dort nicht mehr, wo etwas zu
verlieren wäre. Woran „eingegeben" erkannt wird, ist dasselbe Kennzeichen, das
die Verlassen-Warnung des Browsers ohnehin führt. Das Ziel des Abbruchs ist
fest: beim Bearbeiten der Einsatz, beim Nachtragen die Tagesansicht, beim
Flugtag die Übersicht.

### Geändert

* `server/einsatz_form.php` — Abschnitt Reanimation (Anzeige, Einlesen,
  Speichern), Vorschlagsquellen `crew:<rolle>`, Abbrechen in beiden Zuständen.
* `server/mission_fields.php` — die fünf Crew-Felder von `select` auf `text`
  mit `suggest_src`; `suggest_src` im Kopfvertrag erweitert.
* `server/flugtag_neu.php` — Abbrechen mit Rückfrage.
* `server/assets/forms.js` — `data-cancel-form`, `window.EdForms`.
* `server/assets/style.css` — Darstellung der Reanimationszeilen.
* `server/version.php`, `docs/Backlog.md`, `docs/Technik.md`,
  `docs/Handbuch.md`.

## [Web 5.4.0] — 2026-08-16

Dritter Block der Verbesserungsrunde Web (A3 „Technische Schulden"). Keine
Schemaänderung, keine Migration. Der Block räumt zwei Altlasten aus, die den
kommenden Umbau für bodengebundene Einsätze sonst verteuert hätten, und bringt
das Backlog in einen widerspruchsfreien Zustand.

### Neu sichtbar: die Spalte „abw. Crew" in der Tagesübersicht

Die Tagesübersicht zeigt jetzt eine zehnte Spalte: **abw. Crew**. Sie setzt
einen Haken, wenn für den Einsatz eine vom Flugtag abweichende Besatzung
eingetragen ist — der Fall, für den es die Funktion seit Web 2.6.0 gibt
(Pilotenwechsel während eines Flugtags). Wer einen Tag nachträgt oder
durchsieht, erkennt so ohne Aufklappen, an welchen Einsätzen jemand anderes an
Bord war. Sortieren lässt sie sich wie jede andere Spalte.

Die Spalte war die ganze Zeit definiert, nur nicht angeschlossen — siehe unten.

### Ein Feldkatalog, eine Auswertung

`mission_fields.php` ist der zentrale Katalog der Zusatzfelder. Sein Schlüssel
`day_col` sollte sagen, welche Felder als Spalte in der Tagesübersicht
erscheinen; tatsächlich war er reine Dokumentation. Die Spalten standen an drei
Stellen fest verdrahtet: im SELECT und im JSON-Aufbau von `api/day.php` sowie
im Tabellenkopf und im Zeilenaufbau von `index.php`. Ein Eintrag im Katalog
allein bewirkte nichts — deshalb fehlte „abw. Crew".

Die neue Datei `server/mission_fields_lib.php` wertet den Katalog jetzt an
**einer** Stelle aus (`mf_tagesspalten()`); Tabellenkopf, Zeilen, Sortierung
und die Antwort von `api/day.php` leiten sich daraus ab. Ein neues Feld mit
`day_col` erscheint damit ohne weitere Codeänderung. Auch die Spaltenklassen im
Stylesheet sind nicht mehr an eine feste Reihenfolge gebunden: Sie heißen
`c-dc-<spalte>`, und `.c-dc` gibt eine Vorgabe vor, mit der eine neue Spalte
ohne eigenen Eintrag auskommt.

Am ausgelieferten JSON ändert sich für bestehende Felder nichts — `winch`,
`bergwacht` und `secondary` heißen weiterhin so; `crew_override` kommt hinzu.
(Backlog Nr. 10)

### Ein Update lädt nur noch, was sich geändert hat

`asset()` hängte bisher die globale Versionsnummer an jede Stylesheet- und
Skript-Adresse. Folge: Jede Versionserhöhung entwertete den Zwischenspeicher
**aller** Dateien. Eine Korrekturfassung, die eine Zeile im Stylesheet ändert,
ließ jeden Besucher sämtliche Skripte, die Schriften und Leaflet erneut laden —
nach der lokalen Auslieferung der Schriften (Web 5.2.0) sind das einige hundert
Kilobyte.

Jetzt steht dort der Zeitstempel der jeweiligen Datei. Unveränderte Dateien
behalten ihre Adresse und bleiben im Zwischenspeicher; geänderte bekommen eine
neue. Wird eine Datei nicht gefunden, tritt wie bisher die Versionsnummer an
ihre Stelle. Der Auslieferungsweg trägt das mit: Der FTP-Deploy überträgt nur
inhaltlich geänderte Dateien und führt dafür auf dem Server eine Zustandsdatei
mit Prüfsummen. Zweimal wird trotzdem einmalig alles neu geladen — bei dieser
Auslieferung selbst und bei einem Deploy, bei dem jene Zustandsdatei auf dem
Server fehlt. (Backlog Nr. 9)

**Für die Auslieferung unverändert:** Die Version in `server/version.php` wird
weiterhin bei jeder Auslieferung erhöht. Sie steht in der Fußzeile und ist die
Nummer, über die eine Meldung zugeordnet wird.

### Das Backlog widerspricht sich nicht mehr

`docs/Backlog.md` erklärt im Kopf, dass Nummern dauerhaft sind und Erledigtes
nach unten wandert — hielt sich aber selbst nicht daran. Aufgeräumt:

* Die **Nummer 5 war doppelt vergeben**. Sie bleibt beim Geräte-Limit; die
  Typprüfer-Warnungen im Uhr-Code haben die freie Nummer **13** bekommen.
* Das **Geräte-Limit (Nr. 5)** ist längst umgesetzt und steht jetzt unter
  *Erledigt*.
* Die **Nummern 4, 6 und 7** fehlen ersatzlos und sind nicht mehr
  rekonstruierbar. Statt sie stillschweigend zu übergehen, sagt eine Notiz im
  Kopf, dass sie dauerhaft frei bleiben.
* Neu aufgenommen als **Nr. 14**: der Kopplungsablauf der Uhr — vor einer
  Neukopplung die bestehende abfragen und trennen, damit bei einem Fehlschlag
  nicht stillschweigend weiter auf das vorherige Konto dokumentiert wird.
* Nach *Erledigt* verschoben: **Nr. 9** und **Nr. 10** aus diesem Block.

### Geändert

* `server/mission_fields_lib.php` — neu: abgeleitete Sichten auf den
  Feldkatalog, derzeit `mf_tagesspalten()`.
* `server/api/day.php` — Spaltenliste und Antwortaufbau aus dem Feldkatalog.
* `server/index.php` — Tabellenkopf, Zeilenaufbau und `sortVal()` aus dem
  Feldkatalog; neue Konstante `DAY_COLS`.
* `server/assets/style.css` — Spaltenklassen `c-dc`/`c-dc-<spalte>` statt
  `c-winde`/`c-bw`/`c-sek` für die Tagestabelle. Die gleichnamigen Klassen der
  Zeitraum- und Suchtabelle (`#rangetable`) sind unberührt.
* `server/db.php` — `asset()` auf Dateizeitstempel.
* `server/version.php`, `server/mission_fields.php` — Kommentare nachgezogen.
* `docs/Backlog.md`, `docs/Technik.md`, `docs/Handbuch.md`.

## [Web 5.3.0] — 2026-08-16

Zweiter Block der Verbesserungsrunde Web (A2 „Suche aufräumen"). Keine
Schemaänderung, keine Migration. **Keine Datenbankspalte entfernt** — alle
Angaben bleiben vollständig erhalten, sie sind nur nicht mehr Filterkriterium.

### Vier Filter entfallen

Aus der Suche entfernt: **Herkunft**, **Reanimation**,
**Reanimations-Ereignis** sowie **Höhe Einsatzort von/bis**. Sie waren im
Betrieb nie gesetzt worden und verlängerten die Filterspalte um Zeilen, an
denen der Blick jedes Mal vorbeimusste.

Die Angaben selbst sind unberührt: Herkunft und Bearbeitungsstand stehen
weiterhin in der Einsatzansicht, Reanimationszeiten ebenso, die Höhe des
Einsatzortes in der Feldliste und im Export. Entfallen ist ausschließlich die
Möglichkeit, danach zu filtern.

Ältere geteilte Links und Lesezeichen führen zu keinem Fehler. Der
Filterzustand steht im URL-Fragment, und beim Einlesen werden unbekannte
Parameter still verworfen — ein Link mit `hk=manual` setzt eben diesen einen
Filter nicht mehr, alle übrigen greifen wie bisher. Die Kurznamen `hk`, `re`,
`rt`, `hv` und `hb` bleiben deshalb dauerhaft gesperrt und werden nicht neu
vergeben.

### Die Filterspalte ist nach Themen geordnet

Der Block „Art des Einsatzes" war ein Sammelbecken: Windencycles standen neben
Bergwacht-Bereitschaft neben Schockraum. Er ist durch drei Blöcke ersetzt, die
je einen Zusammenhang tragen:

* **Winde** — Windeneinsatz, Cycles, Cycles mit Patient, Luftverladung
* **Bergwacht** — Bergwacht, Bereitschaft
* **Transport** — Transportziel, Sekundärtransport, Schockraum

Das **Transportziel** ist dabei aus „Beteiligte und Ziel" hierher gewandert;
jener Block heißt jetzt schlicht **Beteiligte**. Die Spalte hat damit sechs
Blöcke statt vier: Zeit, Winde, Bergwacht, Transport, Beteiligte, Werte. Am
Verhalten ändert sich nichts — jeder Block klappt einzeln auf, und bei einem
geteilten Link gehen genau die Blöcke auf, in denen etwas gesetzt ist. Weil
diese Zuordnung aus der Filterliste in `suche.php` abgeleitet wird, war dafür
kein zusätzlicher Code nötig.

### Der Suchindex trägt nur noch, was gebraucht wird

`api/suchindex.php` liefert den Bestand, über den der Browser lokal filtert.
Mit den vier Filtern sind auch die Felder `origin`, `site_ele_m`,
`resus_count` und `resus_types` aus diesem Paket entfallen — und damit zwei
Datenbankabfragen über `resus_sessions` und `resus_events`, die bei jedem
Öffnen der Suchseite liefen. Der Endpunkt kommt jetzt mit fünf Abfragen aus
statt mit sechs.

Die Spalten selbst bleiben unangetastet. Einsatzansicht, Zeitraum-Übersicht und
Export beziehen sie über eigene Endpunkte und sind nicht betroffen.

### Nebenbei aufgeräumt: tote CSS-Regel

Mit dem Filter „Reanimations-Ereignis" verliert die Klasse `.reatypen` ihr
letztes Element. Sie war in zwei Regeln mit `.wochentage` zusammengefasst; die
Selektoren sind entfernt, die Regeln für die Wochentage bleiben unverändert.

## [Web 5.2.0] — 2026-08-16

Erster Block der Verbesserungsrunde Web (A1 „Darstellung und Formate"): fünf
sichtbare Unstimmigkeiten der Oberfläche und die letzte externe Abhängigkeit im
laufenden Betrieb. Keine Schemaänderung, keine Migration.

### Kartenbedienung schob sich über die Kopfleiste

Beim Scrollen legten sich Zoom, Ebenenwahl und Quellenangabe der Karte über die
klebende Kopfleiste. Die Kartenflächen selbst verschwanden korrekt darunter —
das machte den Fehler erst auffällig.

Die Ursache steckte in Leaflets eigenem Stylesheet: `.leaflet-top` und
`.leaflet-bottom` stehen dort auf `z-index: 1000`, unsere Kopfleiste auf 900.
Die Bedienelemente liegen jetzt auf 800. Bewusst nicht niedriger: Der Wert muss
über der obersten Kartenebene (Popups, 700) bleiben, sonst verdeckt ein
geöffnetes Popup die Bedienung. Der Kartenvollbildmodus bleibt unberührt — er
bildet einen eigenen Stapelkontext, die Werte darin zählen nur untereinander.

### Uhrzeiten stehen jetzt überall im 24-Stunden-Format

Je nach Sprach- und Regionseinstellung des Betriebssystems zeigten die
Zeitfelder „01:30 PM" statt „13:30" — auch bei deutscher Oberfläche. Das ist
keine Einstellungssache der Anwendung: Das Anzeigeformat von
`<input type="time">` folgt ausschließlich dem System und lässt sich weder per
HTML noch per CSS oder JavaScript erzwingen. In einer Notfalldokumentation ist
eine Uhrzeit mit AM/PM eine Fehlerquelle.

Die Zeitfelder sind deshalb jetzt gewöhnliche Textfelder mit Maske. Die neue
Datei `assets/zeitfeld.js` setzt Format, Zifferntastatur auf dem Telefon und
Rückmeldung: Ziffern tippen genügt, der Doppelpunkt setzt sich selbst, aus
`930` wird beim Verlassen `09:30`. Was keine gültige Uhrzeit ergibt, färbt das
Feld rot und wird nicht gespeichert. Betroffen sind die Phasenzeiten im
Einsatzformular und die beiden Alarmzeit-Filter der Suche.

**Datumsfelder bleiben nativ.** Dort ist die Anzeige kosmetisch — der
übertragene Wert ist immer ISO —, und ein selbstgebauter Kalender wäre auf dem
Telefon deutlich schlechter zu bedienen.

Die verbindliche Prüfung lag und liegt auf dem Server: `local_to_utc()` prüft
Muster **und** Wertebereich („25:00" passt auf `\d{2}:\d{2}` und ergäbe sonst
stillschweigend den nächsten Tag). Die Maske im Browser ist Bequemlichkeit,
keine Sicherung.

### Der Hinweis „Koordinaten gesetzt …" bezog sich sichtbar auf nichts

Die Meldungszeile des Einsatzort-Feldes stand hinter dem Koordinaten-Chip und
in derselben Größe und Farbe wie jeder andere Nebentext. Beim Lesen war nicht
zu erkennen, worauf sie sich bezieht — sie sagt aber etwas über das Textfeld
darüber aus, nicht über den Chip.

Sie steht jetzt unmittelbar unter dem Feld, kleiner gesetzt und in Max Blau.
Genauer: in der eine Stufe dunkleren Variante, weil nur die bei dieser
Schriftgröße die Kontrastschwelle erreicht (4,6:1 statt 3,8:1). Der Fehlerfall
(„Bezeichnung fehlt") bleibt rot.

### Suchergebnisse sahen nicht anklickbar aus

Ein Klick auf eine Trefferzeile öffnete den Einsatz schon immer — nur sah man
es der Zeile nicht an. Der Grund war schlicht: `assets/missiontable.js` setzte
die Klasse `clickable`, und im Stylesheet gab es dazu keine einzige Regel.

Jetzt wechselt der Zeiger, und die Zeile hebt sich beim Überfahren hervor. Und
weil eine reine Mausfunktion keine Lösung ist: Die Zeilen sind mit der
Tabulatortaste erreichbar, Enter und Leertaste öffnen den Einsatz, der
Tastaturfokus ist sichtbar. Betrifft Suche und Zeitraum-Übersicht gemeinsam,
weil beide dieselbe Tabelle benutzen.

### Schriften und Leaflet kommen nicht mehr aus dem Netz

Bis hierher lud jede Seite zwei Dinge von fremden Servern: die Schriften von
Google, Leaflet von unpkg. Das hatte zwei Folgen, die im Code seit Längerem als
Warnung standen. Erstens meldete jeder Seitenaufruf die IP-Adresse an Google
beziehungsweise unpkg — in einer Anwendung, deren ganzer Zweck darin besteht,
dass Patientendaten den Browser nicht unverschlüsselt verlassen, war das der
letzte verbliebene Bruch in der Linie. Zweitens fiel bei blockiertem Abruf
(Werbeblocker, strenger Trackingschutz) die Karte vollständig aus.

Beides liegt jetzt lokal:

* **Schriften** als woff2 unter `assets/fonts/`, eingebunden per `@font-face`
  mit `font-display:swap`. Übernommen wurden nur die tatsächlich benutzten
  Schnitte — Bricolage Grotesque 500/600, Open Sans 400/600/700 —, je in den
  Subsets latin und latin-ext. `unicode-range` trennt die beiden: latin-ext
  lädt nur, wenn ein Zeichen daraus vorkommt, etwa in einem osteuropäischen
  Namen. Zusammen rund 170 KB, und der Browser holt davon im Regelfall nur
  einen Teil.
* **Leaflet 1.9.4** unter `assets/vendor/leaflet/` — CSS, JS und die von der
  CSS referenzierten Bilder. Nach demselben Muster wie SheetJS und zip.js:
  Herkunft und SHA-256 der Originaldatei stehen im Dateikopf.

Die Ersatzschriftenliste bleibt trotzdem bestehen und bleibt normal breit
(siehe Web 5.1.1). Sie trägt jetzt nur noch den Fall, dass eine Datei fehlt.

Nebeneffekt, der so vorgesehen war: Eine Content-Security-Policy (Backlog
Nr. 8) lässt sich erst jetzt eng formulieren, weil keine fremde Quelle mehr
erlaubt werden muss.

### Nebenbei aufgeräumt: totes Zahnradmenü

`style.css` trug noch die Regeln eines aufklappenden Zahnradmenüs in der
Kopfleiste, das es seit Längerem nicht mehr gibt — die Kopfleiste enthält
stattdessen einen einfachen Verweis auf die Einstellungen. Elf Zeilen ohne
zugehöriges Element sind entfallen. Aufgefallen ist das bei der Suche nach
Elementen, die über der Kopfleiste liegen (siehe oben).

## [Web 5.1.1] — 2026-08-14

Vier Rückmeldungen aus dem Betrieb. Keine Schemaänderung; **eine Migration**,
die Gerätenamen bereinigt.

### Die Oberfläche wirkte gedrungen — die Ersatzschrift war schuld

Kopfleiste, Knöpfe, Überschriften und Tabellenköpfe sahen zusammengeschoben
aus. Die Ursache stand in der Ersatzliste für die Überschriftenschrift:

```
--head:'Bricolage Grotesque','Arial Narrow',system-ui,sans-serif;
```

Bricolage Grotesque ist eine **normal breite** Grotesk, Arial Narrow eine
**schmale**. Solange die Webschrift geladen wurde, fiel das nicht auf. Kam sie
nicht durch — Werbeblocker, strenger Trackingschutz, Aussetzer bei
fonts.gstatic.com —, wurde die halbe Oberfläche schmal. Es sah nach einem
Gestaltungsfehler aus und war ein fehlgeschlagener Download.

Die Ersatzliste ist jetzt normal breit (Systemschrift, dann die üblichen je
Betriebssystem). Fällt die Webschrift aus, fällt es kaum noch auf.

**Der eigentliche Punkt bleibt bestehen und steht als Warnung im CSS:** Beide
Schriften kommen bei jedem Seitenaufruf von Google. Das meldet die IP-Adresse
jeder Nutzerin an Google — in einer Anwendung, deren ganzer Zweck darin
besteht, dass Patientendaten den Browser nicht unverschlüsselt verlassen, ist
das ein Bruch in der Linie. Behebung wäre, die vier woff2-Dateien selbst
auszuliefern.

### Gerätenamen: das Kopplungsdatum verschwindet auch aus dem Altbestand

Web 5.0.1 hatte nur die *Vergabe* geändert — neu gekoppelte Geräte heißen „Uhr“.
Bereits vorhandene trugen weiter „Uhr (gekoppelt 11.08.2026)“, und der Hinweis
auf der Startseite zeigte das Datum deshalb weiterhin zweimal.

Eine Migration bereinigt das. Geändert wird **nur**, was exakt dem automatisch
vergebenen Muster entspricht; ein selbst vergebener Name — „Uhr Philipp“,
„Christoph 17“, auch „Uhr (gekoppelt, alt)“ — bleibt unberührt. Es geht keine
Angabe verloren: Das Datum steht in `devices.created_at` und wird in der
Geräteliste als „seit …“ angezeigt.

Die Migration ist bewusst **nicht** als destruktiv gekennzeichnet: Hier wird
nichts vernichtet, sondern eine doppelt geführte Angabe auf ihre eine Quelle
zurückgeführt. Die rote Kennzeichnung bleibt den Fällen vorbehalten, in denen
wirklich etwas verlorengeht — sonst gewöhnt man sich an sie.

Beim Bauen ist der erste Entwurf gescheitert: Als SQL-`REGEXP` hätten Klammern
und Punkte doppelt maskiert werden müssen, einmal für PHP und einmal für die
Datenbank, und was am Ende bei MariaDB ankommt, war dem Quelltext nicht mehr
anzusehen. Die Migration läuft deshalb als Funktion, mit dem Muster als
gewöhnlichem regulärem Ausdruck an genau einer Stelle.

### Der Quittungsknopf steht jetzt im Rahmen des Hinweises

„Verstanden, das war ich“ stand als unterstrichener Text unter dem Absatz und
war kaum zu finden. Ein Hinweis, dessen Ausweg man nicht sieht, ist derselbe
Hinweis, der sich nicht wegklicken lässt. Er sitzt jetzt als Knopf im Rahmen,
rechts neben dem Text — gedeckt eingefärbt, damit er auffindbar ist, ohne mehr
Gewicht zu haben als die Warnung selbst.

### Die Wartungsseite meldet die Schlüsselableitung nur noch im Problemfall

Der Abschnitt zeigte auch eine Entwarnung („Alle Konten rechnen mit einer
Rundenzahl, die diese Fassung anbietet“). Sie ist entfallen: Eine Wartungsseite,
die Nicht-Probleme aufzählt, macht die echten Meldungen schwerer zu finden — und
wer sie liest, überfliegt beim nächsten Mal auch die Zeile, die zählt.

**Die Prüfung selbst bleibt.** Sie fängt den Fehler ab, den jemand macht, der
`KDF_ITER_ZIEL` anhebt und vergisst, den bisherigen Wert in `KDF_ITER_LISTE`
stehen zu lassen: Dann kann sich kein Bestandskonto mehr anmelden, und an der
Anmeldemaske ist die Ursache nicht zu erkennen.

### Nicht geändert: zwei Meldungen in der Browser-Konsole

Beide stammen **nicht** aus diesem Projekt und lassen sich hier nicht abstellen:

* `MouseEvent.mozPressure is deprecated` und `mozInputSource` beim Klick auf
  die Karte — aus `Util.js` von Leaflet 1.9.4. Ein Hinweis von Firefox an
  Leaflets Entwickler, keine Fehlfunktion. Er verschwindet mit einer künftigen
  Leaflet-Fassung.
* `Request for font "Cascadia Mono" blocked at visibility level 1` — Firefox
  verhindert, dass Webseiten die installierten Schriften auslesen. Die Zeile
  erscheint für jede in einer Schriftliste genannte Schrift, die nicht zum
  Grundbestand gehört. Auch das ist kein Fehler.

### Geprüft

16 automatische Prüfungen über echten HTTP-Verkehr, alle bestanden: Migration
(automatischer Name bereinigt, drei Varianten selbst vergebener Namen
unberührt, zweiter Lauf ohne Arbeit), Hinweis mit Knopf im Rahmen, Quittieren,
Wartungsseite mit und ohne Problem.

## [Web 5.1.0] — 2026-08-14

Paket P9c, Web-Teil: **M2-10, Formatkennung vor jedem Chiffretext.** Keine
Schemaänderung, keine Migration. Die beiden Uhr-Befunde aus P9c (M7-03 und die
409-Behandlung in `Pair.mc`) folgen als eigene Uhr-Auslieferung.

### Warum eine Kennung

Ein Chiffretext bestand aus Zufallswert und Nutzdaten — ohne jede Angabe
darüber, mit welchem Verfahren er entstanden ist. Wird das Verfahren je
gewechselt, und irgendwann wird es das, gibt es kein Merkmal, an dem sich alt
von neu unterscheiden ließe. Man müsste raten und am Fehlschlag erkennen, dass
man falsch geraten hat — nur sieht ein Fehlschlag beim Entschlüsseln genauso
aus wie ein falscher Schlüssel.

Der Sicherungscontainer macht es seit jeher richtig vor: Er trägt eine
Fassungsnummer im Kopf. Der Aufwand ist jetzt klein und wäre später groß.

### `edk1:` — ein Textpräfix, kein Kennungsbyte

Der Doppelpunkt gehört nicht zum base64-Zeichenvorrat. Die Kennung ist damit
auf den ersten Blick zu erkennen, auch in der Datenbankspalte, und ohne dass
irgendetwas entschlüsselt werden müsste. Ein Byte **innerhalb** der Daten wäre
von einem Zufallswert nur durch Ausprobieren zu unterscheiden — man müsste
beide Deutungen durchrechnen.

**Die Kennung gilt für jeden Chiffretext**, nicht nur für `pat_blob`: Auch die
beiden Schlüsselhüllen `pat_wrap_pw` und `pat_wrap_rc` kommen aus derselben
Funktion. Eine halb gekennzeichnete Verschlüsselung wäre genau die
Inkonsistenz, die der Befund abschaffen soll.

### Beim Lesen großzügig, ohne Umstellung des Bestands

Ein Chiffretext **ohne** Kennung ist die erste Fassung. Eine Umstellung des
Bestands gibt es nicht und kann es nicht geben: Der Server hat den Schlüssel
nach Bauart nicht und kann die Kennung deshalb nicht nachtragen. Beide Formen
stehen dauerhaft nebeneinander; ein Datensatz bekommt die Kennung, wenn er das
nächste Mal gespeichert wird.

Eine **unbekannte** Kennung meldet „wurde mit einer neueren Fassung des
Programms verschlüsselt" statt „Schlüssel passt nicht" — sonst sucht die
lesende Person den Fehler beim Schlüssel und findet ihn nie. Derselbe Gedanke
wie beim Sicherungscontainer in Web 5.0.0.

### Drei Kopien einer Prüfregel beseitigt

Das Muster für Schlüsselhüllen stand dreifach im Projekt: als Konstante
`WRAP_RE` in `pw_handling.php` und wortgleich als Zeichenkette in
`einstellungen.php` und `api/kdf_upgrade.php`. Mit der Kennung wären daraus
drei Stellen geworden, die einzeln nachzuziehen gewesen wären — und eine
vergessene hätte eine **gültige** Hülle abgewiesen, mit dem Ergebnis, dass ein
Passwortwechsel scheitert. `WRAP_RE` liegt jetzt neben `PAT_BLOB_RE` in
`validate_lib.php`.

### Zum Rückschritt

Ein Rückschritt auf 5.0.1 ist nur so lange gefahrlos, wie **kein
Patientendatensatz gespeichert und kein Passwort gewechselt** wurde. Danach
trägt der betroffene Chiffretext die Kennung, und die ältere Fassung liest sie
nicht. Bei einer Schlüsselhülle heißt das: kein Zugriff auf die geschützten
Angaben, bis das Passwort über den Wiederherstellungsschlüssel zurückgesetzt
wird.

### Behobene Review-Befunde

M2-10. Damit sind alle Web-Befunde des Reviews erledigt; offen bleiben nur
noch die beiden Uhr-Befunde M7-03 und die 409-Behandlung in `Pair.mc`.

### Geprüft

31 automatische Prüfungen, alle bestanden: 13 im Browser (Kennung schreiben,
beide Formen lesen, falscher Schlüssel bei beiden Formen, unbekannte Kennung
wird als solche gemeldet, Hüllen, Eindeutigkeit der Hüllen-Bindung), 18
serverseitig (beide Formen, Längengrenzen mit und ohne Kennung, fremde Kennung,
Doppelpunkt an falscher Stelle, Platz in der Spalte).

## [Web 5.0.1] — 2026-08-14

Vier Befunde aus dem Betrieb der Testinstallation. Keine Schemaänderung.

### Das Wiedereinspielen einer Sicherung brach hart ab

`api/backup_restore.php` antwortete mit 500, im Fehlerprotokoll stand
`Class "Pruefliste" not found`. `backup_lib.php` benutzt die Prüfschicht,
lud sie aber nie — sie stand nur dann zur Verfügung, wenn die aufrufende Seite
`validate_lib.php` zufällig schon eingebunden hatte. Der Sicherungs-Endpunkt
tat das nicht.

**Der Fehler bestand, seit die Prüfschicht eingeführt wurde (P3, Web 4.2.0).**
Aufgefallen ist er erst jetzt, weil er nur den Einspielweg trifft.

Bemerkenswert ist, warum ihn keine der 36 automatischen Prüfungen zum
Sicherungsweg gesehen hat: Das Prüfskript lud `validate_lib.php` selbst, bevor
es `backup_lib.php` einband — und verdeckte damit genau die Lücke, die es hätte
finden sollen. Die Prüfung lädt jetzt nur noch das, was auch
`api/backup_restore.php` lädt.

### Zeitraumansicht: Fehler in der Browser-Konsole beim Aufbau

`Uncaught TypeError: can't access property "subtract", this._point is
undefined` — bei jedem Aufbau der Seite. Die Karte bekam keinen
Ausgangsausschnitt. Ohne ihn nimmt Leaflet eine Ebene zwar entgegen, stellt sie
aber zurück und rechnet ihre Bildschirmposition nicht aus; das `setStyle()` der
Hervorhebung, die nach jedem Neuzeichnen der Tabelle über alle Pins läuft,
scheitert dann. `fitBounds()` kommt erst danach.

`index.php` löst das seit jeher mit einer Zeile. Sie fehlte auf der
Zeitraumansicht — und auf `einsatz.php` ebenfalls, dort bisher folgenlos, weil
`fitBounds()` rechtzeitig kommt. Beide haben sie jetzt.

### Der Gerätehinweis nannte das Datum zweimal und ließ sich nicht wegklicken

„Ein neues Gerät wurde mit deinem Konto verbunden: Uhr (gekoppelt 11.08.2026)
(11.08.2026 17:01)."

Der beim Koppeln vergebene Name enthielt das Datum, und der Hinweis gab
zusätzlich `created_at` aus. Der Name lautet jetzt nur noch „Uhr": Eine Angabe,
die an zwei Stellen geführt wird, läuft auseinander, sobald jemand das Gerät
umbenennt — dann steht ein Datum im Namen, das mit nichts mehr zusammenhängt.
Das Kopplungsdatum gehört der Zeile, nicht dem Namen; die Geräteliste zeigt es
ohnehin.

Der Hinweis stand außerdem sieben Tage lang auf der Startseite und war nicht
wegzuklicken. Eine Warnung, die man nicht loswird, wird nach dem dritten Mal
überlesen — und dann steht sie unbemerkt da, wenn sie einmal wirklich gemeint
ist. Es gibt jetzt „Verstanden, das war ich"; bestätigt wird je Zeitpunkt, ein
später gekoppeltes Gerät meldet sich erneut. Das Kennzeichen „neu" in der
Geräteliste bleibt unberührt — dort ist es keine Warnung, sondern eine Angabe.

### 310 000 ist aus der Rundenzahl-Liste entfernt

Alle Konten stehen auf 320 000 (`SELECT COUNT(*) FROM users WHERE kdf_iter =
310000` ergab 0). Die Anmeldung rechnet damit wieder nur einmal ab und ist so
schnell wie vor Web 5.0.0.

**Dazu neu auf der Wartungsseite: ein Abschnitt „Schlüsselableitung".** Er
meldet, wenn ein Konto eine Rundenzahl trägt, die `KDF_ITER_LISTE` nicht mehr
anbietet — solche Konten können sich nicht anmelden, und an der Anmeldemaske
ist die Ursache nicht zu erkennen. Genau das passiert, wenn jemand
`KDF_ITER_ZIEL` anhebt und vergisst, den bisherigen Wert in der Liste stehen zu
lassen. Der Abschnitt nennt die betroffene Zahl, die Anzahl der Konten und die
Behebung. In `db.php` steht die Anweisung jetzt als erstes im Kommentarblock,
nicht mehr am Ende.

### Behobene Befunde

Ohne Review-Kennung — alle vier stammen aus dem Betrieb.

### Geprüft

27 automatische Prüfungen, alle bestanden: 8 zum Wiedereinspielen über den
echten Ladeweg (ohne Vorladen der Prüfschicht), 11 zum Gerätehinweis,
8 zur Warnung der Wartungsseite. Dazu die 44 + 18 Prüfungen aus P9b erneut —
die stille Anhebung gegen eine Kopie des Servers mit zweielementiger Liste,
also gegen den Zustand nach einer künftigen Anhebung.

## [Web 5.0.0] — 2026-08-13

Paket P9b: die Rundenzahl der Schlüsselableitung wird änderbar (M2-01, Schritte
2 bis 4) und wandert in den Kopf der Sicherungsdatei (S7). Keine
Schemaänderung — die Spalte `users.kdf_iter` gibt es seit Web 4.0.0, sie wurde
bisher nur von keiner Zeile gelesen.

**Hauptversion, weil sich das Dateiformat der Sicherung ändert** und die
Anmeldung ein anderes Feld sendet. Beides ist rückwärtsverträglich; die Zählung
folgt dem Format, nicht dem Risiko.

### Warum das überhaupt nötig war

Die Rundenzahl stand als Konstante im Browser-Code. Sie anzuheben hätte
bedeutet: Aus demselben Passwort entsteht ein anderes Token, der gespeicherte
Hash passt nicht mehr — **alle Konten gleichzeitig ausgesperrt**, und zwar
unwiderruflich für die geschützten Angaben. Dieselbe Konstante steckte im
Schlüssel jeder Sicherungsdatei; eine Anhebung hätte auch jede bereits
erzeugte Datei unlesbar gemacht, ohne Fehlermeldung, die den Grund nennt.

Beides ist jetzt behoben. Der Zielwert steigt von 310 000 auf **320 000** —
absichtlich nur ein kleiner Schritt: Der Gewinn ist gering, aber der
Mechanismus läuft dadurch einmal für jedes Konto wirklich durch, unter
Beobachtung. Ein späterer Sprung auf einen deutlich höheren Wert ist danach
eine Zeile auf einem erprobten Weg statt ein Sprung ins Dunkle.

### Der Salz-Endpunkt nennt eine Liste, keinen Wert

`auth_salt.php` ist ohne Anmeldung erreichbar und muss für erfundene Adressen
genauso antworten wie für echte. Nennte er die Rundenzahl **des Kontos**, wäre
während einer Umstellung jede Adresse, die den alten Wert zurückliefert,
nachweislich ein echtes, seither nicht benutztes Konto — die Auskunftslücke,
die derselbe Endpunkt in Web 4.4.0 gerade geschlossen hatte, an neuer Stelle.

Er nennt deshalb **jeder Adresse dieselbe Liste**. Der Browser leitet für jeden
Eintrag ab und schickt alle Token; der Server nimmt das, das zur gespeicherten
Rundenzahl gehört. Es gibt weiterhin genau eine bcrypt-Prüfung — kein
Durchprobieren.

Der Preis steht im Handbuch: Solange die Liste zwei Einträge hat, dauert die
Anmeldung doppelt so lange. Das ist der Übergangszustand, nicht der
Dauerzustand. In `db.php` steht, wann ein Wert aus der Liste verschwinden darf
— nämlich erst, wenn `SELECT COUNT(*) FROM users WHERE kdf_iter = <Wert>` null
ergibt.

### Die Rundenzahl ist ein Pflichtparameter

`EdCrypto.deriveKeys(passwort, salt, runden)` hat **keinen Vorgabewert** und
wirft, wenn die Zahl fehlt. Das ist die wichtigste Sicherung des Umbaus: Ein
Vorgabewert ließe jede vergessene Aufrufstelle stillschweigend mit 310 000
rechnen — und weil heute noch alle Konten diesen Wert tragen, fiele das *nicht
auf*. Es fiele erst an dem Tag auf, an dem der Zielwert angehoben wird, und
dann als „Passwort falsch" bei Leuten, die richtig getippt haben.

Umgestellt sind sieben Ableitungen und zehn Aufrufe von `ensureContentKey()`.
`auth_guard.php` liefert `$kdfIter`, sieben Seiten geben ihn als `KDF_ITER`
aus. Erstvergabe, Zurücksetzen und Passwortwechsel bauen die Ableitung
vollständig neu auf und nehmen deshalb immer den Zielwert — der Passwortwechsel
ist damit die zweite Gelegenheit, bei der ein Konto die Anhebung mitnimmt.

### Die stille Anhebung

Steht ein Konto noch auf der alten Zahl, wird sie beim nächsten Anmelden im
Hintergrund angehoben. Der Weg führt über ein Vormerkfach, weil Passwort und
Schlüsselhülle nie gleichzeitig vorliegen: Bei der Anmeldung hat der Browser
das Passwort, aber nicht die Hülle; auf der ersten angemeldeten Seite ist es
umgekehrt. Dasselbe Verfahren benutzt der Passwortwechsel seit Web 4.5.0.

Der neue Endpunkt `api/kdf_upgrade.php` ist funktional eine Passwortänderung —
er setzt den Hash, gegen den sich das Konto anmeldet. Entsprechend abgesichert:

* **Das alte Token ist Pflicht.** Ohne Nachweis wäre der Endpunkt ein Weg, aus
  einer übernommenen Sitzung ein beliebiges Passwort zu setzen.
* **Nur Werte aus der Liste, nur nach oben.** Eine frei wählbare Rundenzahl
  wäre ein Weg, ein Konto auf einen absurd niedrigen Wert zu setzen, ohne dass
  jemand es merkt — die Anmeldung liefe weiter.
* **Die Prüfsumme des Inhaltsschlüssels muss gleich bleiben.** Es ist derselbe
  Schlüssel, nur anders verpackt.
* **`session_epoch` wird nicht erhöht.** Beim Passwortwechsel ist das
  Hinauswerfen anderer Sitzungen der Zweck; hier wäre es ein Fehler — das
  Passwort hat sich nicht geändert, und niemand verstünde, warum bei jeder
  Anmeldung stillschweigend alle anderen Fenster fliegen.

Ein Fehlschlag ändert nichts und meldet nichts: Das Konto behält seine
Rundenzahl und versucht es beim nächsten Anmelden erneut.

### Ein Fehler, den erst die Ende-zu-Ende-Prüfung gezeigt hat

Nach der Anhebung passte der neue Datenschlüssel nicht mehr zu der
Schlüsselhülle, die die Seite mitgebracht hatte — `PAT_WRAP` wird gerendert,
bevor die Anhebung läuft. Folge wäre gewesen: Der Entsperrdialog erscheint
unmittelbar nach jedem Anmelden und fragt nach dem Passwort. Also genau das
Gegenteil einer *stillen* Anhebung, und die Art Fehler, die im Betrieb niemand
meldet — man gewöhnt sich an die Abfrage.

Der Inhaltsschlüssel ist in dem Moment bekannt (er wurde eine Zeile vorher
entpackt, um ihn neu zu verpacken). Er wird jetzt abgelegt und an die Hülle der
laufenden Seite gebunden; beim nächsten Seitenaufbau verwirft `EdKeyGuard` ihn
wegen der abweichenden Bindung und entpackt ihn aus der neuen Hülle.

### Sicherungsdateien: Containerformat 3

Der Kopf wächst von 9 auf 13 Byte und trägt die Rundenzahl als 4 Byte, big
endian. Sie geht in die AAD ein und lässt sich damit nicht fälschen.

* **Fassung 2 wird weiterhin gelesen** — die Fassungsnummer ersetzt die
  fehlende Angabe (dort galt immer 310 000). Geschrieben wird sie nicht mehr.
* Eine Datei aus einer **neueren** Fassung meldet „stammt aus einer neueren
  Fassung, bitte die Anwendung aktualisieren" statt „Passwort falsch" — sonst
  sucht die lesende Person den Fehler an der falschen Stelle.
* `docs/Backup-Format.md` beschreibt beide Fassungen; das Python-Beispiel dort
  behandelt beide und wurde gegen eine echte Datei geprüft.

### Behobene Review-Befunde

M2-01 (Schritte 2–4), S7. Damit ist die Schemaänderung S1 aus P0 vollständig
eingelöst.

### Geprüft

79 automatische Prüfungen, alle bestanden: 43 zu Salz-Endpunkt, Anmeldung und
Anhebungs-Endpunkt (echter HTTP-Verkehr, echte Ableitung mit WebCrypto), 18 zum
vollständigen Browserweg von der Anmeldung bis zur abgeschlossenen Anhebung,
18 zum Containerformat.

Belegt sind unter anderem: identische Antwort des Salz-Endpunkts für bekannte
und erfundene Adressen; Anmeldung vor und nach der Anhebung; sechs Abwehrfälle,
nach denen Rundenzahl, Hülle und Sitzungszähler unverändert sind; dass die neue
Hülle denselben Inhaltsschlüssel enthält; dass eine von Hand gebaute
Fassung-2-Datei weiterhin aufgeht.

Nicht ohne Testinstallation prüfbar: die tatsächliche Dauer der Ableitung auf
den benutzten Geräten und das Verhalten in mehreren offenen Tabs.

## [Web 4.7.0] — 2026-08-13

Paket P9a der Review-Umsetzung: die vier Befunde aus P9, die weder die
Schlüsselableitung noch das Format des Chiffretexts berühren. Keine
Schemaänderung. P9b (Ableitungsrunden) und P9c (Formatkennung, Uhr-App)
folgen getrennt — die beiden gefährlichsten Änderungen des ganzen Reviews
sollen einzeln prüfbar bleiben.

### Migrationen löschen keine Spalte mehr, in der noch etwas steht

Vier Migrationen entfernen Spalten, und alle vier gehen davon aus, dass ihr
Inhalt anderswo gerettet wurde. Für die Betreiberinstallation stimmt das und
ist dokumentiert. Das Projekt liegt aber offen: Eine zweite Station verlor die
betroffenen Spalten in dem Moment, in dem jemand die Wartungsseite öffnete und
den Knopf drückte — ohne je gelesen zu haben, dass vorher etwas zu retten
gewesen wäre.

Die Migrationsliste kennt jetzt zwei zusätzliche Angaben. `zerstoert`
beschreibt in Klartext, was verlorenginge, und hebt die Zeile in der Vorschau
hervor. `inhalt` nennt die Spalten, deren Inhalt die Migration vernichten
würde — steht dort etwas, wird sie **nicht ausgeführt**, sondern mit Spalte und
Zeilenzahl gemeldet.

**Nicht jede destruktive Migration prüft den Inhalt**, und das ist Absicht. Bei
`phase10_entfernen` und der Zeitzonen-Migration ist das Löschen der Zweck; eine
Inhaltsprüfung hätte sie genau dort blockiert, wo sie gebraucht werden. Geprüft
wird nur, wo eine Spalte von Hand eingegebene Daten hielt: `loc_addr`,
`loc_lat`, `loc_lon`, `mission_no`, `site_desc`.

Zwei Punkte, die sich beim Bauen ergaben:

* Eine blockierte Migration **hält die Kette nicht an**, anders als ein Fehler.
  Sie hat nichts getan; die Datenbank steht exakt wie zuvor. Andernfalls käme
  auf einer Installation mit Altbestand in `site_desc` keine spätere Migration
  mehr durch — darunter die Sicherheitsbausteine aus `2026_08_08`. Ein
  Datenschutz, der die Sicherheitsupdates blockiert, wäre ein schlechter Tausch.
* Es gibt einen **Ausweg**: je blockierter Migration ein eigenes Häkchen
  „Daten sind gesichert — diese eine Migration trotzdem ausführen“. Ohne ihn
  säße der Betreiber fest. Auf der Kommandozeile gibt es die Stufe bewusst
  nicht; dort steht nur der Weg zur Wartungsseite. Ein `--force` wäre zu leicht
  aus einer Anleitung abgeschrieben.

Wurde eine Migration freigegeben, sagt die Ergebniszeile das ausdrücklich —
sie ist später der einzige Beleg dafür, dass jemand die Entscheidung bewusst
getroffen hat.

### Die Einrichtung kann keine Datenbank mehr leeren, und Fremde können sie nicht ausführen

Der Einrichtungs-Assistent hatte ein Häkchen „Vorhandene Tabellen vorher
löschen“. Es war die einzige Stelle im Projekt, an der ein **unangemeldeter**
Aufruf jede Tabelle der Datenbank hätte leeren können — abgesichert durch
nichts als die Annahme, dass diese Seite nur einmal und nur vom Betreiber
geöffnet wird. Es ist ersatzlos entfallen: Wer mit Altbestand neu einrichten
will, legt eine leere Datenbank an oder leert die vorhandene beim Hoster.
Beides sind bewusste Handlungen an der richtigen Stelle.

Dieselbe Annahme trug auch den Rest der Seite. Wer eine frisch hochgeladene
Installation vor ihrem Betreiber findet, richtete sie ein und war
Administrator. Deshalb legt die Seite jetzt eine Datei im Anwendungsverzeichnis
an und verlangt deren Kennung im Formular. Wer sie nennen kann, hat Zugriff
auf das Verzeichnis — und wer den hat, könnte die Anwendung ohnehin beliebig
verändern.

**Die Kennung steht im Dateinamen, nicht nur im Inhalt.** Bei Einfachhosting
liegt das Verzeichnis im Web-Wurzelverzeichnis; eine Datei mit festem Namen
wäre über die Adresszeile abrufbar, und der Nachweis wäre keiner. Einen Namen
aus 128 Bit Zufall kann nur nennen, wer das Verzeichnis sieht. Die `.htaccess`
sperrt die Datei zusätzlich — als zweite Schranke, nicht als erste.

Beim Prüfen zeigte sich ein Fehler im ersten Entwurf: Die Kennung hing an der
Sitzung, und **jeder** Aufruf der Seite ließ eine weitere Datei liegen. Wer
danach ins Verzeichnis sieht, findet mehrere und weiß nicht, welche seine ist.
Die Kennung hängt jetzt an der Datei; eine vorhandene wird übernommen statt
ersetzt. Das hält zugleich einen Ärger fern, den die erste Fassung geöffnet
hätte: Wer die Datei bei jedem Aufruf neu schreiben ließe, könnte einem
Betreiber mitten in der Einrichtung die Kennung unter den Händen wegziehen.

### Die Passwortprüfung wird endlich benutzt

Baustein B9 (`assets/pwquality.js`) entstand in P0 und lag seither an **keiner
einzigen Stelle** eingebunden da. Die Mindestlänge stand stattdessen als
HTML-Attribut und als Längenvergleich im Skript — ein Attribut hält niemanden
auf, der die Entwicklerwerkzeuge öffnet, und „zu kurz“ ist keine Begründung.

Jetzt an vier Stellen: Erstvergabe, Zurücksetzen, Passwortwechsel und die
beiden Dateipasswörter. Überall dieselbe Regel, mit Stärkeanzeige während der
Eingabe und einem Satz dazu, warum ein Passwort abgelehnt wird.

### Dateipasswörter: mindestens zehn Zeichen, und das Kontopasswort ist erlaubt

Sicherung (`.edbak`) und Export-Archiv verlangten acht Zeichen — weniger als
das Konto, obwohl in beiden Dateien dieselben Angaben stehen, im Export sogar
im Klartext. Beide liegen jetzt bei zehn und laufen über dieselbe Prüfung.

Beim **Backup** lässt sich zusätzlich das Kontopasswort verwenden. Ob es
stimmt, stellt der Browser selbst fest: Aus Passwort und Salz entsteht der
Datenschlüssel, und mit dem muss sich die gespeicherte Hülle öffnen lassen. Der
Server wird dafür nicht gefragt.

Beim **Export-Archiv** wird das bewusst **nicht** angeboten: Diese Datei ist
ausdrücklich zum Weitergeben gedacht. Wer sie mit seinem Kontopasswort
verschlüsselt, gibt es dem Empfänger mit.

Der Hinweis über dem Backup-Passwortfeld sagt außerdem jetzt, **was** in der
Datei steht — alle geschützten Angaben im Klartext — statt nur, dass die Datei
ohne Passwort wertlos sei. Das beantwortete die falsche Frage: Wer ein Passwort
wählt, muss wissen, was er damit schützt.

### Behobene Review-Befunde

M6-01, M1-11, M2-02, M2-03.

### Geprüft

74 automatische Prüfungen, alle bestanden: 24 zur Wartungsseite (Inhaltsprüfung
gegen eine Datenbank mit gefüllter `site_desc`-Spalte, über echten
HTTP-Verkehr), 23 zum Einrichter (eigener Webserver auf einer Kopie des
Verzeichnisses, echte Ersteinrichtung gegen eine Datenbank mit Fremdtabelle),
20 zur Passwortprüfung, 7 zur Inhaltszählung.

Nicht ohne Testinstallation prüfbar: Darstellung der Stärkeanzeige, das
Häkchen „Kontopasswort verwenden“ im Zusammenspiel mit dem Entsperrdialog und
das Verhalten der `.htaccess` auf einem echten Apache.

## [Web 4.6.0] — 2026-08-13

Paket P8c der Review-Umsetzung: Bündel 5 (Leistung) und Bündel 6. Zehn Befunde,
keine Schemaänderung. Damit ist P8 abgeschlossen; offen bleibt P9.

### Eine Sicherung braucht nicht mehr Tausende Abfragen

`edbak_build()` fragte je Einsatz die Phasen, die Rettungsmittel, die
Reanimationssitzungen und die Spurpunkte einzeln ab, dazu je Sitzung deren
Ereignisse. Gemessen an 43 Einsätzen mit je einer Reanimationssitzung:
**226 Abfragen vorher, 16 nachher** — und die 16 bleiben 16, egal wie groß der
Bestand ist. Auf die im Review genannten 1600 Einsätze hochgerechnet sind das
rund 8300 Abfragen gegen unverändert 16.

Dieselbe Sache in kleinerem Maßstab in der Tagesansicht: `api/day.php` holte
die Spurpunkte je Einsatz und je Ruhesegment einzeln, bei jedem Tageswechsel
neu.

Die chunkweise `IN(…)`-Abfrage gab es längst — sie stand in
`api/export_data.php`, mitsamt einem Kommentar, der genau diesen Weg
beschreibt. Sie stand eben **nur dort**, und deshalb ist ihr niemand gefolgt.
Jetzt liegt sie als `sql_in_bloecken()` in `db.php` und wird von allen drei
Stellen benutzt.

### Eine leere Phasenliste löscht keine Phasen mehr

Der Uhr-Weg ersetzte Phasen und Reanimationssitzungen, sobald der Schlüssel im
Datensatz vorhanden und ein Feld war. Eine **leere** Liste bestand beide
Prüfungen: Sie löschte den vorhandenen Stand und fügte nichts ein. Die Antwort
lautete „ok“.

Der Weg zu einer leeren Liste ist viel wahrscheinlicher ein Fehler beim Aufbau
der Nachricht als der Wunsch, eine dokumentierte Reanimation wieder
loszuwerden. Wer wirklich löschen will, tut das in der Weboberfläche.

**Die Regel geht bewusst weiter als der Befund.** Übergangen wird jede Liste,
die weniger gültige Einträge enthält als der gespeicherte Stand — eine halb
aufgebaute Nachricht ist derselbe Fehler wie eine leere, nur unauffällig: Sie
kommt mit drei Phasen an, wo acht stehen, und der Verlust fällt niemandem auf.
Für die Uhr ist das folgenlos: `Model.mc` fügt Phasen ausschließlich hinzu, ein
erneutes Setzen ist eine Korrektur und damit ein zusätzlicher Eintrag. Eine
kürzere Liste kann bei ihr gar nicht entstehen.

Gezählt wird **nach** der Prüfung: Zehn Einträge, von denen neun unbrauchbar
sind, sind ein Eintrag. Wird eine Liste übergangen, steht das als `kept_phases`
bzw. `kept_resus` in der Antwort — sonst wäre der übergangene Upload von einem
übernommenen nicht zu unterscheiden. `docs/JSON-Vertrag.md` beschreibt beide
Felder jetzt als durchgesetzt statt als Zielzustand.

### Ein Fehler in der Höhenberechnung kostet keine Wiederherstellung mehr

Die Einsatzort-Höhe wurde beim Wiedereinspielen **innerhalb** der Transaktion
und ohne eigenen Fehlerblock berechnet, je Einsatz in der Schleife. Ein Fehler
darin riss die gesamte Wiederherstellung mit sich — wegen eines Komfortwerts,
und ausgerechnet an der Stelle, an der die Eingangsdaten am wenigsten geprüft
sind. Auf dem Uhr-Weg stand derselbe Aufruf längst hinter dem Abschluss, mit
genau dieser Begründung.

Jetzt läuft er auch beim Wiedereinspielen nach dem Commit, je Einsatz
eingefasst. Anders als auf dem Uhr-Weg wird ein Fehlschlag aber **gezählt und
gemeldet** (`hoehe_fehler`): Die Uhr kann mit der Auskunft nichts anfangen,
eine Wiederherstellung wertet dagegen ein Mensch aus.

Nachgestellt und gemessen: Bei erzwungenem Fehler bleiben alle Einsätze
gespeichert, und die Meldung nennt die Zahl.

### CSV-Exporte führen keine Formeln mehr aus

Tabellenprogramme werten eine Zelle, die mit `=`, `+`, `-` oder `@` beginnt,
als Formel aus — auch in Anführungszeichen, denn das Quoting gehört zum
CSV-Format und nicht zum Zellinhalt. Fremder Text gelangt über zentrale
Stammdaten und über eingespielte Daten in die Textspalten, und Exportdateien
sind ausdrücklich zum Weitergeben gedacht.

Solche Werte tragen jetzt einen vorangestellten Apostroph. **Zahlen sind
ausgenommen**, damit eine negative Zahl eine Zahl bleibt — die Dateien sind
maschinenlesbar, und das ist keine Nebensache. `LIESMICH.txt` im Archiv und
`docs/Export-Format.md` beschreiben die Regel.

Der Weg über das Tabellenformat (Profile Standard und GuteSeele) ist **nicht**
betroffen: Dort entstehen echte Zellen vom Typ Zeichenkette, die nie als Formel
gelesen werden. Ein Apostroph wäre dort sichtbar. Dieser Unterschied steht als
Warnung im Code, damit ihn niemand der Einheitlichkeit halber einebnet.

### Der Wiederherstellungsschlüssel sagt jetzt, was an ihm nicht stimmt

Die Eingabe wurde normalisiert und gehasht, ohne zu prüfen, ob die Länge stimmt
oder die Zeichen überhaupt aus dem Alphabet stammen. Ein Tippfehler ergab
klaglos einen anderen Schlüssel, und die Meldung lautete „passt nicht“ — dieselbe
Meldung wie bei einem falschen Zettel. Das passiert in genau der Lage, in der
jemand ohnehin unter Druck steht.

Jetzt steht unter dem Feld sofort, welches Zeichen es im Schlüssel nicht gibt
oder wie viele Zeichen noch fehlen. Und ist der Schlüssel formal in Ordnung,
passt aber trotzdem nicht, sagt die Meldung genau das: kein Tippfehler,
sondern ein anderer Schlüssel.

Die Liste der nicht verwendeten Zeichen wird aus dem Alphabet **abgeleitet**
statt danebengeschrieben — sie lautet heute 0, 1, I, L, O und U. Beide Seiten
jeder klassischen Verwechslung fehlen also; deshalb sagt die Meldung
ausdrücklich **nicht**, welches Zeichen stattdessen gemeint sei.

Eine Streckung der Ableitung ist bewusst nicht dazugekommen: rund 98 Bit
Entropie, die Verzerrung durch die Restklassenbildung kostet davon 0,0 Bit.

### Die Sicherung nennt beim Einspielen ihr Herkunftskonto

Der Block mit Kontoname und Adresse steht seit dem ersten Dateiformat in jeder
Sicherung und wurde beim Einspielen nie angesehen. Wer zwei Konten betreut oder
eine Datei aus einer Übergabe bekommt, hatte keine Möglichkeit zu prüfen, ob es
die richtige ist — es blieb der Dateiname, und der nennt nur das Datum.

Die Angabe wird **angezeigt, nicht abgefragt**: Eine Sicherung in ein fremdes
Konto einzuspielen ist ein vorgesehener Vorgang, und eine Warnung vor etwas
Erlaubtem wird nach dem dritten Mal weggeklickt. Die Rückfrage bleibt dem Fall
vorbehalten, in dem tatsächlich etwas unlesbar bliebe — sie nennt jetzt
zusätzlich die Herkunftsadresse.

### Zwei Bausteine werden endlich benutzt

Beide entstanden in P0 und lagen seither ungenutzt neben ihren Kopien.

**Maskierung (B7).** Es gab vier Umsetzungen derselben Aufgabe in zwei
Bauarten, und alle vier maskierten drei Zeichen statt fünf — die
serverseitige Entsprechung `e()` maskiert beide Anführungszeichen mit. Für
Textpositionen reicht das, und Attributpositionen gibt es heute keine. Genau
deshalb war es gefährlich: Wer als Nächstes einen Wert in ein `title="…"`
schreibt, hat keinen Anhaltspunkt, dass die Fassung dafür nicht taugt.

Die kanonische Fassung stand in `missiontable.js` — einer Datei, die nur zwei
von fünf betroffenen Seiten laden. Sie ist deshalb nach `assets/html.js`
gewandert; `EdMissionTable.escape` und `.esc` bleiben als Weiterleitung.
`xmlEscape()` in `export.js` bleibt bewusst eigenständig: GPX ist XML und hat
eigene Regeln. Zwei Aufgaben, die sich ähneln, sind nicht dieselbe Aufgabe.

**Patientenanzeige (B8).** Fünf Seiten schrieben ihre Entschlüsselungsschleife
selbst aus und unterschieden sich dabei in Kleinigkeiten — welcher Zähler wann
hochgeht, ob `_pat` gesetzt wird. Genau solche Kleinigkeiten laufen beim
nächsten Mal auseinander. Alle benutzen jetzt `EdPat.entschluessleListe()`.

Dabei ist eine Lücke aufgefallen, die im Review nicht steht: **Der Export ließ
unlesbare Angaben stillschweigend leer.** Wer mit nicht passendem Schlüssel
exportiert, bekam eine Datei, die vollständig aussieht und es nicht ist. Jetzt
kommt vorher eine Rückfrage mit der Zahl der betroffenen Einsätze.

### Behobene Review-Befunde

M3-15, M5-12 (Bündel 5) · M5-04, M4-02, M5-05, M5-13, M6-03, M6-05, M6-06,
M2-06 (Bündel 6). Damit sind alle für P8 vorgesehenen Befunde erledigt.

### Geprüft

75 automatische Prüfungen gegen MariaDB 10.11 und echten HTTP-Verkehr, alle
bestanden: 36 zu `sql_in_bloecken`, Sicherungsaufbau und Wiedereinspielen,
25 zum Uhr-Weg, 14 zur Tagesansicht. Dazu die Maskierung des CSV-Exports und
die Prüfung des Wiederherstellungsschlüssels als reine Funktionsprüfungen.
Nicht ohne Testinstallation prüfbar: Darstellung, Karten-Popups, Dialoge und
das Zusammenspiel der Skripte im Browser.

## [Web 4.5.3] — 2026-08-13

Zwei Nachträge aus dem Betrieb, beide beim Durchprüfen von 4.5.1/4.5.2
gefunden.

### Die Anmeldesperre nannte 2 Stunden 15 Minuten statt 15 Minuten

Die Sperre selbst dauerte 15 Minuten — falsch war die **Meldung**. Sie kam
durch eine doppelte Umrechnung zustande: Der Endzeitpunkt wurde in der
Zeitzone des Datenbankservers geschrieben (Ortszeit), und die Anzeige las ihn
als Weltzeit und rechnete den Zonenversatz noch einmal drauf.

Dasselbe betraf vier weitere Anzeigen: „zuletzt gesehen" und „seit" im
Geräte-Reiter, dieselben Angaben auf der Admin-Nutzerseite, den Hinweis auf
neu gekoppelte Geräte auf der Startseite und die Spalte „angelegt" in der
Nutzerliste. Alle zeigten um den Zonenversatz zu späte Zeiten.

**Behoben ist das bereits mit 4.5.2**, wo die Zeitzone der Verbindung
ausdrücklich auf UTC gesetzt wurde. Was fehlte, war der Übergang: Zeilen aus
der Zeit davor tragen noch Ortszeit und wirken deshalb um den Zonenversatz in
der Zukunft — eine beim Umstieg laufende Anmeldesperre hielt entsprechend
länger. Eine Migration räumt diese Zeilen jetzt weg.

Betroffen war dabei nur ein Spaltentyp, und das ist der Punkt: `TIMESTAMP`
rechnet MySQL beim Schreiben selbst in Weltzeit um und beim Lesen zurück —
Kopplungscodes, Gerätezeiten und Anlegedaten waren also nie falsch
**gespeichert**, nur falsch **angezeigt**. `DATETIME` speichert dagegen, was
dasteht. Von den mit der Serverzeit gefüllten `DATETIME`-Spalten bleiben zwei:
die Ratenschutz-Zähler (werden geräumt) und die Gültigkeit offener
Passwort-Links. Letztere werden bewusst nicht angefasst — ein Einladungslink,
der jemandem unter den Händen ungültig wird, wäre der größere Schaden als
einer, der ein bis zwei Stunden zu lange lebt.

Die Einsatzzeiten und der Papierkorb waren nie betroffen; beide rechnen seit
jeher ausdrücklich in Weltzeit.

### Die Wartungsseite ist jetzt erreichbar

`update.php` war nur über die direkte Adresse aufzurufen — es gab keinen
Menüeintrag. Damit war die Auskunft aus 4.5.1 wertlos: Sie meldet, dass der
Aufräumjob dauerhaft scheitert, auf einer Seite, die niemand öffnet.

Unter **Administration** steht jetzt der Eintrag **Wartung**. Die Seite hat
außerdem die Seitenleiste bekommen, die ihr fehlte — wer dort landete, kam
vorher nur über den Zurück-Knopf wieder heraus. Am Verhalten ändert sich
nichts: Das bloße Aufrufen führt weiterhin keine Migration aus, es zeigt nur
an, was anstünde.

## [Web 4.5.2] — 2026-08-13

Zweiter Teil des Aufräumens: siebzehn Stellen ohne gemeinsames Thema außer
diesem — an jeder wich der Code von einer Regel ab, die er sonst überall
befolgt. Keine neuen Funktionen, keine Schemaänderung.

### Die Zeitzone der Datenbankverbindung hing am Hoster

Die Anwendung benutzt zwei Zeitfunktionen, und zwar mit Absicht: `UTC` für den
Papierkorb, dessen Frist über 30 Tage läuft, und die Serverzeit für alles
Kurzlebige — Ratenschutz-Fenster, Gültigkeit von Tokens und Kopplungscodes.

Welche Zeit die Serverzeit ist, war nirgends festgelegt. Sie kam aus der
Einstellung des Datenbankservers, also vom Hoster, und konnte sich bei einem
Serverumzug ändern, ohne dass hier jemand etwas tut. Steht sie auf einer
Ortszeit, laufen beide Zeitrechnungen um den Zonenversatz auseinander: Ein
Ratenschutz-Fenster, das in Ortszeit geschrieben und gegen UTC verglichen wird,
ist ein bis zwei Stunden zu früh oder zu spät abgelaufen.

Die Verbindung setzt jetzt ausdrücklich UTC. Der Unterschied im Code bleibt
stehen — er sagt, was gemeint ist. Die **Anzeige** ist davon unberührt; sie
rechnet weiter in die eingestellte Ortszeit um.

### Eine Notiz „0" verschwand beim Speichern

Beim Speichern der Flugtag-Angaben stand eine Prüfung auf Wahrheitswert. Die
Zeichenkette `0` ist in PHP unwahr, genau wie eine leere Eingabe — ein Feld,
in dem nur eine Null stand, wurde damit zu „nichts" und war nach dem Speichern
fort. Betroffen waren alle Felder des Flugtags, auch die Notiz. `00` kam
dagegen durch, was den Fehler schwer bemerkbar machte.

### Antworten der Oberfläche werden nicht mehr zwischengespeichert

Den Kopf gegen das Zwischenspeichern setzte genau ein Weg: die Sicherung. Vier
weitere liefern denselben verschlüsselten Inhalt aus — Tagesdaten, Zeitraum,
Suche, Einzeleinsatz. Der Inhalt ist verschlüsselt, die Hülle darum herum
(Datum, Uhrzeit, Einsatznummer, Koordinaten) nicht. An einem gemeinsam
genutzten Rechner reichte die Zurück-Taste, um eine Antwort aus dem Speicher
des Browsers zu holen, nachdem sich jemand abgemeldet hatte.

Der Kopf sitzt jetzt an der Stelle, durch die jede Antwort geht. Außerdem
weisen die nur lesenden Wege andere Anfragearten mit einer klaren Meldung ab,
statt sie wie eine Leseanfrage zu behandeln.

### Ein fehlgeschlagener Passwortwechsel hinterließ einen kaputten Zustand

Der Browser verwarf den alten Schlüssel und setzte den neuen, **bevor** der
Server überhaupt gefragt war. Lehnte der Server ab — falsches aktuelles
Passwort, abgelaufenes Formular, Ratenschutz —, lag im Tab danach ein
Schlüssel, der nicht zu den gespeicherten Angaben passte. Die geschützten
Angaben waren unlesbar, und zwar so, wie es aussieht, wenn es sie gar nicht
gäbe.

Der neue Schlüssel wandert jetzt in ein Vormerkfach und wird erst übernommen,
wenn der Server den Wechsel bestätigt hat. Bei einem Fehlschlag bleibt der
alte unberührt.

### Weitere Stellen

* **Der Einrichtungsassistent** sichert seine Sitzung jetzt wie die
  Anwendung — er führt das Datenbank-Passwort im Formular und lief bis eben
  ohne diese Vorkehrungen.
* **Zeilenumbrüche in einer Empfängeradresse** werden beim Mailversand
  abgewiesen. Eine solche Adresse hätte eigene Anweisungen und Kopfzeilen in
  die Nachricht einschleusen können. Die Konfiguration wird dabei einmal
  statt zweimal gelesen.
* **Eine gepackte Sicherung** auf einem Browser ohne Entpackfunktion meldete
  bisher „Passwort falsch oder Datei beschädigt" — die denkbar
  irreführendste Auskunft. Jetzt steht dort, woran es wirklich liegt.
* **Das Format der Sicherung ist jetzt aufgezählt** statt „alles, was in der
  Tabelle steht". Dabei fiel eine tote Altspalte auf: `other_resources` wurde
  seit der Umstellung auf einzeln entfernbare Rettungsmittel von niemandem
  mehr gefüllt, wanderte aber in jede Sicherung. Sie ist jetzt draußen; die
  Rettungsmittel selbst sind wie bisher enthalten.
* **Die Nutzerzeile** wird mit benannten Spalten gelesen. Der Hash des
  Anmeldetokens liegt damit nicht mehr bei jeder Anfrage im Speicher.
* **Vier Stellen mit Werten im SQL-Text** verwenden jetzt vorbereitete
  Anweisungen — es waren die einzigen Abweichungen von einer sonst lückenlosen
  Regel.
* **Der Sortierpfeil** erscheint auf allen Tabellen sofort. Die
  Zeitraumübersicht zeigte ihn erst nach dem ersten Klick, obwohl sie beim
  Öffnen sortiert ist.
* **Ein leeres `<section>`** in der Nutzerverwaltung, ein Kommentarkopf mit
  veralteter Seitenliste, eine befüllte aber nie gelesene Variable im Import
  und ein Platzhalterbau, der an einem literalen Prozentzeichen hart
  abgebrochen wäre: entfernt beziehungsweise berichtigt.
* **Die Weiterleitung von `geraete.php`** hat ein Ablaufdatum bekommen
  (Web 5.0.0) statt unbefristet liegen zu bleiben.
* **Höhenangaben** werden nur noch umgewandelt, wenn dabei auch eine Zahl
  herauskommt. Die Meereshöhe 0 ist ein gültiger Wert und war von einem
  Umwandlungsrest nicht zu unterscheiden.

### Bekannt und hier nicht geändert

Die Einsatzort-Höhe steht in der Sicherung, kommt beim Einspielen aber nicht
zurück — der Einspielweg kennt nur die eingebbaren Felder, und die Höhe wird
beim Uhr-Upload gerechnet. Das Aufzählen der Spalten hat diese Asymmetrie
sichtbar gemacht; sie zu beheben hieße, den Einspielweg zu ändern.

## [Web 4.5.1] — 2026-08-13

Aufräumen: fünfzehn Stellen, an denen die Fehlerbehandlung zu viel oder zu
wenig gefangen hat. Keine neue Funktion, keine Änderung am Schema.

### Der Aufräumjob hielt sich selbst auf

Die tägliche Wartung läuft huckepack auf Anfragen. Sie setzte ihre Tagesmarke,
bevor sie anfing (richtig — sonst räumen zwei gleichzeitige Anfragen doppelt
auf), und ihr Fehlerblock war leer. Zusammen ergab das eine Falle ohne
Ausgang: Scheiterte ein Schritt, brach der gemeinsame Block ab, alle folgenden
Schritte entfielen — und weil die Marke schon stand, lief an diesem Tag nichts
mehr. Am nächsten Tag begann es von vorn und scheiterte an derselben Stelle.
Dauerhaft, und nirgends stand etwas davon.

Am spürbarsten beim Papierkorb: Er stand als vorletzter Schritt. „Endgültig
nach 30 Tagen" wäre stillschweigend zu „nie" geworden.

Jetzt hat jeder der sieben Schritte seinen eigenen Fehlerblock, Fehler landen
im Fehlerprotokoll des Webspace, und eine zweite Marke hält fest, wann zuletzt
ein Lauf **vollständig** durchging. Die Wartungsseite zeigt beide an: Klaffen
sie auseinander, scheitert etwas dauerhaft.

### Ein Spurpunkt kann nicht mehr spurlos verschwinden

Beim Hochladen von der Uhr stand `INSERT IGNORE`. Gedacht war es für die
Wiederholung — lädt die Uhr dieselben Punkte erneut hoch, sollen bekannte
Sequenznummern übergangen werden. Unterdrückt hat es jeden Fehler.

Der Schaden war dauerhaft: Die Fortsetzungsmarke, die die Uhr zurückbekommt,
ist die höchste gespeicherte Nummer plus eins. Ein Punkt, der beim Einfügen
scheiterte, hinterließ eine Lücke — die Marke sprang darüber hinweg, die Uhr
setzte dahinter fort und sendete ihn **nie wieder**. Der Upload meldete dabei
Erfolg.

Jetzt wird nur noch der Schlüsselkonflikt übergangen. Jeder andere Fehler
bricht den Upload ab und rollt zurück; die Uhr versucht es beim nächsten Mal
mit derselben Marke erneut. Ein sichtbar gescheiterter Upload ist besser als
ein stillschweigend unvollständiger.

### Fehlermeldungen der Endpunkte nennen keine Interna mehr

Neun Endpunkte gaben den Text der Ausnahme unverändert nach außen — Tabellen-
und Spaltennamen, Teile der Abfrage, bei Verbindungsfehlern auch Hostnamen.
Das Skript zeigte den Text direkt an, er stand also auf dem Bildschirm und in
jedem Screenshot, den jemand zur Fehlersuche verschickte.

Für die Fehlersuche war er trotzdem unbrauchbar: Was auf dem Bildschirm stand,
stand nirgends sonst. Jetzt geht der volle Text ins Fehlerprotokoll, nach außen
geht eine achtstellige Kennung — kurz genug fürs Telefon, eindeutig genug fürs
Protokoll.

Der Einrichtungs-Assistent und die Wartungsseite zeigen ihre Fehler weiterhin
im Klartext. Beide laufen nur für Verwaltende, und bei der Ersteinrichtung gibt
es noch kein Protokoll, in dem man nachsehen könnte.

### Ein halber Zeitraum beim Export ergab den gesamten Bestand

Der Zeitraumfilter griff nur, wenn **beide** Grenzen gesetzt waren. Fehlte
eine, fiel die Bedingung stillschweigend weg. Wer „ab 01.01.2026" eingab, bekam
eine Datei mit allem seit Beginn — ohne Fehler, ohne Meldung, nur größer als
erwartet. Bei Patientendaten ist das keine Kleinigkeit. Beide Grenzen leer
heißt weiterhin „alles"; genau eine Grenze wird jetzt abgelehnt.

### Eine Monatsangabe außerhalb von 01–12 lieferte einen falschen Monat

Die Übersicht prüfte nur, dass zwei Ziffern kamen. `m=00` ergab den Dezember
des Vorjahres, weil die Datumsrechnung stillschweigend auf einen Ersatzwert
zurückfiel. Eine Übersicht, die einen anderen Monat zeigt als den angefragten,
ist schlimmer als eine, die sich weigert.

### Weitere Änderungen

* **Endgültiges Löschen im Papierkorb** prüft das Datumsformat jetzt genauso
  wie das Wiederherstellen. Vorher war ausgerechnet die unumkehrbare Handlung
  schwächer geprüft.
* **Gerätekennungen** entstehen aus 16 statt 4 Zufallsbytes. Vorhandene Geräte
  behalten ihre Kennung und müssen nicht neu gekoppelt werden.
* **Das virtuelle Gerät „Manuelle Einträge"** wird über Kennung *und*
  Nutzerkennung gesucht. Vorher trug allein der Name die Zugehörigkeit.
* **Kopplungscodes:** Ein neuer Versuch nur noch beim tatsächlichen
  Zusammentreffen zweier Codes. Vorher galt jeder Datenbankfehler als
  Kollision — bei fehlender Tabelle lief die Schleife fünfmal ins Leere und
  riet dann zum erneuten Versuch.
* **Der Migrationsbericht** unterscheidet ausgeführte von bereits erledigten
  Teilschritten. „Erfolgreich angewendet" stand vorher auch dort, wo nichts zu
  tun war.
* **Der Einrichtungs-Assistent** maskiert Fehlermeldungen an der Ausgabestelle
  statt an zwei von zehn Quellen.
* **Beim Passwortwechsel** entfällt ein Abschneiden der Schlüsselhülle, das nie
  greifen konnte — aber bei einer späteren Anhebung der Prüfgrenze
  stillschweigend Patientendaten unlesbar gemacht hätte.
* **Ein Zugriff auf die erste Phase** eines Einsatzes prüft selbst, ob es sie
  gibt.

### Technisch

* `fehler_kennung()` und `json_fehler()` in `db.php`.
* `ist_dublettenfehler()` (aus 4.5.0) trägt jetzt auch die Unterscheidung beim
  Spurpunkt-Upload und bei der Codeerzeugung.
* Zweiter Zustandsschlüssel `last_cleanup_ok` in `app_state` — kein
  Schemawechsel, die Tabelle nimmt beliebige Schlüssel auf.
* Bereits durch 4.2.0 erledigt und hier nur nachgewiesen: Spurpunkte müssen als
  Liste kommen (`ist_liste()`).

## [Web 4.5.0] — 2026-08-12

Bis hierher endete eine Sitzung nur durch Abmelden oder Zeitablauf. Weder ein
Rollenentzug noch ein gelöschtes Konto noch ein Passwortwechsel erreichten sie
— und das sind genau die drei Handgriffe, mit denen man jemandem den Zugang
nimmt.

### Rolle und Konto werden bei jeder Anfrage geprüft

Die Rolle wurde bei der Anmeldung **einmal** in die Sitzung geschrieben und nie
wieder nachgesehen. Wem die Administratorrolle entzogen wurde, behielt seine
Rechte, solange der Tab offen blieb. Wessen Konto gelöscht wurde, blieb
angemeldet und arbeitete weiter.

Beides kommt jetzt aus der Nutzerzeile — die bei jeder Anfrage ohnehin gelesen
wurde, nur eben erst weiter unten und nur für den Anzeigenamen. Der Rollenentzug
wirkt damit beim nächsten Klick. Ein gelöschtes Konto beendet die Sitzung mit
einer Meldung, statt sie stehen zu lassen.

### Ein Passwortwechsel beendet die anderen Sitzungen

Wer sein Passwort wechselt, **weil** er Missbrauch vermutet, will genau eines
erreichen: dass der andere draußen ist. Das erreichte er bisher nicht — eine
offene Sitzung hängt am Sitzungscookie, nicht am Passwort.

Jeder Passwortwechsel erhöht jetzt einen Zähler am Konto. Jede Anfrage
vergleicht ihren Stand dagegen; wer noch den alten trägt, wird abgemeldet und
bekommt den Grund genannt. Die Sitzung, die den Wechsel auslöst, zieht den
neuen Stand mit und bleibt bestehen — beim Weg über „Passwort vergessen" ist
ohnehin niemand angemeldet, dort fallen alle.

Gleichzeitig werden **alle** offenen Links zum Zurücksetzen entwertet, nicht
nur der gerade benutzte. Ein Einladungslink aus der Nutzerverwaltung ist 24
Stunden gültig und hätte den soeben gewählten Zustand sonst wieder
überschreiben können — mit einem Passwort, das jemand anders kennt.

### Der Zurücksetzen-Link steht nicht mehr in der Adresszeile

Der Token stand als Parameter in der Adresse und landete damit im Verlauf des
Browsers, im Zugriffsprotokoll des Webservers und in jedem Screenshot der
Seite. Wer ihn hat, kann das Passwort setzen.

Beim ersten Öffnen wandert er jetzt in eine eigene Sitzung, und die Seite ruft
sich ohne Parameter neu auf. Zusätzlich unterbindet die Seite das Mitsenden der
Herkunftsadresse und hält sich aus dem Zwischenspeicher.

Der dafür nötige Cookie trägt einen **eigenen Namen** und berührt die Sitzung
der Anwendung nicht. Wer Cookies für die Seite blockiert, bekommt das gesagt
(„Cookie nötig") statt eines irreführenden „Link ungültig".

### Nutzer anlegen sagt jetzt, was passiert ist

Drei Dinge an derselben Stelle:

* Eine bereits vorhandene Adresse führte zu einer ungefangenen Ausnahme — der
  Admin sah eine weiße Seite statt einer Auskunft.
* Konto und Setz-Token entstanden in zwei getrennten Schritten. Scheiterte der
  zweite, blieb ein Konto ohne jeden Weg zu einem Passwort zurück.
* Das Ergebnis des Mailversands wurde weggeworfen und in jedem Fall
  „verschickt" gemeldet. Bei einem Fehlschlag existierte das Konto, ein
  gültiger Token lag in der Datenbank — nur hatte niemand den Link.

Jetzt: Vorabprüfung mit verständlicher Meldung, Konto und Token in einer
Transaktion, und bei fehlgeschlagenem Versand wird der Link zur Weitergabe auf
anderem Weg angezeigt.

### „Adresse bereits verwendet" nur noch, wenn es stimmt

Beim Ändern einer E-Mail-Adresse wurde **jeder** Datenbankfehler als Dublette
gemeldet. Eine volle Platte, eine abgerissene Verbindung, ein Rechteproblem:
alles erschien als „diese Adresse wird bereits verwendet" und schickte die
Fehlersuche zuverlässig in die falsche Richtung. Geprüft wird jetzt der
tatsächliche Schlüsselkonflikt; alles andere bekommt eine ehrliche Meldung und
landet im Fehlerprotokoll.

### Eine Schreibweise für E-Mail-Adressen

Die Adresse ist die Kontokennung und wurde an acht Stellen unterschiedlich
behandelt — mal kleingeschrieben, mal nur von Leerzeichen befreit. Dass das
funktionierte, lag allein an der Sortierregel der Datenbank, nicht am Code.

Nebenbei behoben: Die Anmeldung meldete ihren Erfolg an den Zähler der
Salz-Abfrage mit der Adresse **wie getippt**, während die Salz-Abfrage
kleingeschrieben zählt. Wer „Max@…" tippte, gab seinen Versuch dort nie
zurück.

Bestehende Einträge werden nicht angefasst: Die Spalte trägt seit 4.0.0 eine
Sortierregel ohne Rücksicht auf Groß- und Kleinschreibung, der Vergleich trifft
also ohnehin.

### Sitzungsende bei Datenabrufen

Endet die Sitzung mitten in einem Abruf der Oberfläche, antwortet der Server
jetzt mit einem Fehlercode und JSON statt mit der HTML-Seite. Das Skript sah
vorher HTML, wo es JSON erwartete, und meldete irgendetwas Allgemeines statt
„die Sitzung ist beendet".

### Technisch

* Neu: `server/email_lib.php` — Normalisierung, Prüfung und Dublettenerkennung
  für E-Mail-Adressen, ohne Abhängigkeiten (auch von `install.php` nutzbar).
* `session_lib.php`: `session_verwerfen()` beendet eine Sitzung ohne Ausgabe.
* `auth_guard.php`: `ist_admin()` als einzige Rollenprüfung; `require_admin()`
  setzt darauf auf.
* Keine Migration nötig — `session_epoch` und die Sortierregel liegen seit
  4.0.0 im Schema.

## [Web 4.4.0] — 2026-08-12

Dieses Paket betrifft die Endpunkte, die **ohne Anmeldung** erreichbar sind:
Anmeldung, Salz-Abfrage, Passwort-Zurücksetzen, Kopplung und Upload der Uhr. Sie
alle sind Türen nach außen, und an allen fünf ließ sich bisher etwas ablesen
oder etwas beliebig oft wiederholen.

### Die Bremse bei der Anmeldung liegt nicht mehr im Browser des Aufrufers

Nach fünf Fehlversuchen kamen dreißig Sekunden Pause — gezählt in der Sitzung.
Wer das Cookie wegwarf, hatte wieder fünf Versuche frei; ein Programm, das gar
kein Cookie annimmt, verbrauchte nie eines. Das war keine Bremse, sondern eine
Bequemlichkeit gegen Vertippen.

Gezählt wird jetzt in der Datenbank, **je Kontokennung und je IP-Adresse**:
zehn Fehlversuche in fünfzehn Minuten, dann fünfzehn Minuten gesperrt. Die
Meldung nennt, ab wann es wieder geht. Eine erfolgreiche Anmeldung setzt die
Zähler zurück.

Bewusst in Kauf genommen: Wer eine E-Mail-Adresse kennt, kann das zugehörige
Konto durch Fehlversuche zeitweise sperren. Die Sperre ist kurz und ihr Ende
steht in der Meldung. Nur nach IP zu zählen hieße, ein über viele Rechner
verteiltes Durchprobieren einer einzelnen Adresse völlig ungebremst zu lassen.

### Ratenschutz auch auf Salz-Abfrage und Passwort-Zurücksetzen

Beide Endpunkte waren ohne Anmeldung und ohne jede Begrenzung erreichbar. Der
eine taugte damit als Adressenprüfer im Großen, der andere zusätzlich als
Mailschleuder auf fremde Postfächer. Gezählt wird jetzt jede Anfrage — beide
Endpunkte kennen kein Scheitern, begrenzt wird die Menge: dreißig Salz-Abfragen
je Viertelstunde, fünf Zurücksetzen-Anforderungen je Stunde.

### Beim Zurücksetzen gilt immer nur der zuletzt verschickte Link

Jede Anforderung legte bisher einen weiteren Token an, und alle blieben eine
Stunde gültig. Wer den Knopf zehnmal drückte, hatte zehn gültige Links in der
Welt, von denen jeder einzelne genügt. Jetzt entwertet ein neuer Link den
vorherigen — es gibt zu jedem Zeitpunkt höchstens einen. Die E-Mail sagt das
auch.

### Behoben — Die Antwortzeit verriet, welche Konten es gibt

Der Antworttext beim Zurücksetzen war für eine vorhandene und eine unbekannte
Adresse absichtlich derselbe. **Die Dauer war es nicht:** Bei einem vorhandenen
Konto lief ein vollständiges Mailgespräch, sonst kam die Antwort sofort. Eine
einzige Anfrage je Adresse genügte, um Konten zu finden — dieselbe Auskunft wie
eine unterschiedliche Meldung, nur leiser.

Die Antwort wird jetzt **abgeschlossen, bevor der Versand beginnt**. Gemessen
gegen einen Mailserver, der annimmt und nie antwortet (Zeitlimit fünfzehn
Sekunden): beide Zweige 0,51 Sekunden, Unterschied 0,0 %. Vorher wären es
15 Sekunden gegen 0,5 gewesen.

Wo die PHP-Anbindung des Webspace das nicht verbindlich zusagen kann, steht das
jetzt auf der Wartungsseite unter **Umgebung** — es ist die Eigenschaft, an der
die Gleichheit beider Zweige hängt, und sie ließ sich sonst nirgends ablesen.

### Behoben — Die Antwortzeit verriet auch, welche Geräte es gibt

Dieselbe Lücke beim Upload der Uhr und bei der Anmeldung: Bei unbekannter
Kennung kam die Abweisung sofort, bei bekannter lief erst eine
Passwortprüfung. Der Unterschied war ohne jede Zugangsdaten messbar — und eine
Gerätekennung ist die Hälfte dessen, was ein Upload braucht. Beide Wege prüfen
jetzt auch im unbekannten Fall gegen einen festen Vergleichswert. Gemessen:
Abweichung 1,1 % statt einer ganzen Passwortprüfung.

### Höchstens fünf Geräte je Konto, und ein Hinweis bei jedem neuen

Ein Gerät ist ein Satz Zugangsdaten. Ohne Obergrenze konnte ein Konto beliebig
viele davon ansammeln — ein eingeschleustes Gerät stünde einfach als weitere
Zeile in einer Liste, die niemand zählt.

- **Fünf Geräte je Konto**, geprüft beim Koppeln *und* beim manuellen Anlegen.
  Deaktivierte zählen mit, weil ihre Zugangsdaten bestehen bleiben; erst Löschen
  gibt einen Platz frei. Das virtuelle Gerät „Manuelle Einträge" zählt nicht mit
  — es steht schon in der Geräteliste nicht.
- Ist die Grenze erreicht, wird **gar kein Kopplungscode mehr erzeugt**. Sonst
  wäre der Code beim Einlösen verbraucht, ohne dass ein Gerät entsteht.
- **E-Mail an den Kontoinhaber**, sobald ein Gerät gekoppelt wurde, mit
  Gerätekennung, Zeitpunkt und dem Weg zum Entfernen. Sie erreicht die Person
  auch dann, wenn sie sich gerade nicht anmeldet — und genau das ist der Fall,
  um den es geht.
- **Hinweis auf der Startseite und im Geräte-Reiter** für Geräte, die in den
  letzten sieben Tagen hinzugekommen sind. Die zweite, langsamere Spur für alle,
  die ihre Post nicht lesen.

### Geändert

- `smtp_send()` nimmt ein Zeitlimit entgegen. Bei der Kopplung steht es auf
  fünf Sekunden: Die Uhr wartet auf die Antwort, und ihr Code ist bereits
  verbraucht — eine Kopplung darf nicht an einem langsamen Mailserver scheitern.
- Der Geräte-Reiter nennt den Zählstand („belegt: 3 von 5").
- Die Anmeldeseite meldet eine Sperre der Salz-Abfrage jetzt als solche. Vorher
  lief sie in den allgemeinen Fehlerzweig und behauptete, der Browser
  unterstütze die nötige Verschlüsselung nicht.

### Keine Datenbankänderung

Dieses Paket kommt ohne Migration aus.



### Ein Flugtag im Papierkorb wird nicht mehr stillschweigend übergangen

Drei Schreibwege führen zu einem Flugtag, und alle drei verhielten sich falsch,
wenn er im Papierkorb lag:

- **Formular:** Die Aktualisierung hatte keine Bedingung auf den Löschzustand.
  Sie überschrieb die Angaben und ließ den Tag gelöscht — die Eingabe verschwand
  spurlos, die Meldung lautete „Gespeichert." Jetzt wird abgelehnt und der Grund
  genannt.
- **Import:** Er holte den Tag stillschweigend aus dem Papierkorb zurück, samt
  alter Angaben. Jetzt wird er übersprungen und in der Meldung genannt.
- **Wiedereinspielen:** Es tat nichts — aber eben still, ohne Zählung und ohne
  Erwähnung. Jetzt wird der Fall benannt.

**Warum ablehnen und nicht zurückholen:** Das Löschen war eine bewusste
Handlung. Sie durch eine Nebenwirkung rückgängig zu machen, ist eine
Überraschung — und zwar eine, die niemand sieht. Der Papierkorb hat eine eigene
Wiederherstellungsfunktion.

Auch beim **Lesen** wird der Zustand jetzt gemeldet. Vorher lieferte die
Schnittstelle für einen gelöschten Tag schlicht nichts, nicht unterscheidbar
von „für diesen Tag wurde noch nichts eingetragen". Wer seine Angaben vermisste,
suchte den Fehler bei sich. Die Tagesansicht zeigt nun einen Hinweis.

### Behoben — Ein gelöschtes Ruhesegment kehrte immer wieder zurück

Die Sperrliste, die verhindert, dass die Uhr einen gelöschten Datensatz neu
anlegt, war an **beiden** Enden nur für Einsätze umgesetzt: Sie wurde nur für
Einsätze befüllt und nur im Einsatz-Zweig abgefragt. Ein endgültig gelöschtes
Ruhesegment wurde deshalb von der nächsten Nachlieferung wieder angelegt — und
beim erneuten Löschen wieder. Wer eine Uhr im Einsatz hat, kam aus dieser
Schleife nicht heraus.

Im selben Zweig fehlte auch die Prüfung auf „im Papierkorb", sodass ein
gelöschtes Ruhesegment weiter Spurpunkte sammelte.

Beide Prüfungen stehen jetzt **vor** der Fallunterscheidung und gelten damit für
beide Arten. Die Sperrliste unterscheidet über `owner_type` (seit Web 4.0.0),
welche Art gemeint ist — Einsätze und Ruhesegmente vergeben ihre Kennungen
unabhängig voneinander.

## [Web 4.3.0] — 2026-08-08

### Ein Flugtag im Papierkorb wird nicht mehr stillschweigend übergangen

Drei Schreibwege führen zu einem Flugtag, und alle drei verhielten sich falsch,
wenn er im Papierkorb lag:

- **Formular:** Die Aktualisierung hatte keine Bedingung auf den Löschzustand.
  Sie überschrieb die Angaben und ließ den Tag gelöscht — die Eingabe verschwand
  spurlos, die Meldung lautete „Gespeichert." Jetzt wird abgelehnt und der Grund
  genannt.
- **Import:** Er holte den Tag stillschweigend aus dem Papierkorb zurück, samt
  alter Angaben. Jetzt wird er übersprungen und in der Meldung genannt.
- **Wiedereinspielen:** Es tat nichts — aber eben still, ohne Zählung und ohne
  Erwähnung. Jetzt wird der Fall benannt.

**Warum ablehnen und nicht zurückholen:** Das Löschen war eine bewusste
Handlung. Sie durch eine Nebenwirkung rückgängig zu machen, ist eine
Überraschung — und zwar eine, die niemand sieht. Der Papierkorb hat eine eigene
Wiederherstellungsfunktion.

Auch beim **Lesen** wird der Zustand jetzt gemeldet. Vorher lieferte die
Schnittstelle für einen gelöschten Tag schlicht nichts, nicht unterscheidbar
von „für diesen Tag wurde noch nichts eingetragen". Wer seine Angaben vermisste,
suchte den Fehler bei sich. Die Tagesansicht zeigt nun einen Hinweis.

### Behoben — Ein gelöschtes Ruhesegment kehrte immer wieder zurück

Die Sperrliste, die verhindert, dass die Uhr einen gelöschten Datensatz neu
anlegt, war an **beiden** Enden nur für Einsätze umgesetzt: Sie wurde nur für
Einsätze befüllt und nur im Einsatz-Zweig abgefragt. Ein endgültig gelöschtes
Ruhesegment wurde deshalb von der nächsten Nachlieferung wieder angelegt — und
beim erneuten Löschen wieder. Wer eine Uhr im Einsatz hat, kam aus dieser
Schleife nicht heraus.

Im selben Zweig fehlte auch die Prüfung auf „im Papierkorb", sodass ein
gelöschtes Ruhesegment weiter Spurpunkte sammelte.

Beide Prüfungen stehen jetzt **vor** der Fallunterscheidung und gelten damit für
beide Arten. Die Sperrliste unterscheidet über `owner_type` (seit Web 4.0.0),
welche Art gemeint ist — Einsätze und Ruhesegmente vergeben ihre Kennungen
unabhängig voneinander.

## [Web 4.2.0] — 2026-08-08

### Alle vier Schreibwege prüfen jetzt gleich

Dieselben Tabellen werden über vier unabhängige Wege beschrieben: Formular,
Uhr, Import und Wiedereinspielen einer Sicherung. Jeder führte eigene
Prüfungen — und die Sorgfalt verlief **genau umgekehrt zur
Vertrauenswürdigkeit der Quelle**:

| | Formular | Import | Uhr | Sicherung |
|---|---|---|---|---|
| vorher | 5 von 9 | 8 von 9 | 5 von 9 | **0 von 9** |
| jetzt | alle | alle | alle | alle |

Ausgerechnet das Wiedereinspielen prüfte gar nichts — dabei kann die Datei aus
beliebiger Herkunft stammen, während der Uhr-Weg immerhin einen Schlüssel
verlangt. Seit dieser Auslieferung ruft jeder Weg dieselbe Prüfschicht auf
(`server/validate_lib.php`, seit Web 4.0.0 vorhanden).

### Behoben — Ein unmöglicher Kalendertag wurde stillschweigend verschoben

Die Datumsumwandlung liefert bei einem unmöglichen Tag kein Fehlerergebnis,
sondern rechnet weiter: Aus dem 30. Februar wird der 2. März. Sichtbar wird das
nur über die Warnungsabfrage der Datumsklasse, und die wurde nirgends
abgefragt. Ein Tippfehler in einer Importdatei verschob damit die Phasenzeiten
eines ganzen Einsatzes auf einen falschen Tag — ohne jede Meldung. Jetzt wird
ein solcher Tag abgelehnt, auf allen vier Wegen.

### Behoben — Das Wiedereinspielen brach beim ersten schlechten Wert komplett ab

Ein einziger ungültiger Wert ließ die **gesamte** Wiederherstellung scheitern,
statt die eine Zeile zu überspringen. Das ist die falsche Richtung: Wer eine
Wiederherstellung startet, hat meist keinen zweiten Versuch. Jetzt wird je
Datensatz übersprungen und am Ende gesagt, wie viele und warum.

Damit ist auch der letzte Weg geschlossen, über den **Phase 10** noch in die
Datenbank zurückkehren konnte.

### Behoben — Ein Import verlor genau die Korrektur, um die es ging

Beim Überschreiben eines vorhandenen Einsatzes wurde die Zugehörigkeit aus der
Zahl der geänderten Zeilen erschlossen. Die Datenbank liefert aber die Zahl der
**geänderten**, nicht der **getroffenen** Zeilen: Wer alle Werte auf das setzt,
was schon dasteht, bekommt null zurück. Daraus schloss der Code „gehört jemand
anderem" und übersprang den Einsatz — samt der danach folgenden Blöcke für
Phasen, Reanimation und Rettungsmittel.

Der praktisch wichtigste Fall ist zugleich der schlimmste: Jemand importiert
erneut, weil er **nur die Phasenzeiten korrigiert** hat. Die Kopfdaten sind
unverändert, also greift der Fehlschluss — und genau die Korrektur wird
verworfen. Gemeldet wurde „übersprungen", was nach „war schon da" klingt.
Jetzt wird die Zugehörigkeit direkt abgefragt.

### Behoben — Mehrfach gesetzte Phasen gingen beim Import verloren

Der Import verwarf die zweite Zeile mit derselben Phasennummer. Das
widerspricht dem JSON-Vertrag: Mehrfache Einträge sind ausdrücklich erlaubt,
weil eine erneut gesetzte Phase eine **Korrektur** ist und damit eine
Information. Der Uhr-Weg speicherte sie, der Import warf sie weg — dieselben
Daten ergaben je nach Weg einen anderen Bestand, und ein Rückimport der eigenen
Exporte verlor stillschweigend Zeilen.

Statt der Entdoppelung begrenzt jetzt eine Mengengrenze (500 Phasen je Einsatz).
Sie ist bewusst hoch: Sie schützt vor einer entgleisten Nutzlast und ist kein
Ersatz für die Entdoppelung.

### Behoben — Geschützte Angaben konnten unbemerkt ungespeichert bleiben

Passte der Chiffretext nicht zum erwarteten Muster, wurde die Spalte im
Formular einfach nicht in die Aktualisierung aufgenommen: kein Fehler, keine
Meldung, der bisherige Block blieb stehen. Wer eine Diagnose korrigierte und
„gespeichert" las, hatte danach die **alte** Diagnose in der Datenbank. Jetzt
wird gemeldet und nichts geändert.

Dieselbe Stelle war beim Passwortwechsel längst so gelöst — dort steht das
stille Übergehen sogar als früherer Fehler im Kommentar.

### Geändert — Eine Grenze für den Patientenblock statt dreier

40 bis 60000 Zeichen, auf allen vier Wegen. Vorher: 16…8000 im Formular,
20…60000 im Import, gar keine beim Wiedereinspielen. Die Untergrenze ist jetzt
hergeleitet statt geschätzt — AES-256-GCM legt 12 Byte Zufallswert davor und
hängt 16 Byte Prüfwert an, also mindestens 28 Byte oder 40 base64-Zeichen. Alle
drei alten Untergrenzen lagen darunter.

### Neu — Verworfene Werte werden genannt

Bisher verschwand ein verworfener Wert spurlos; der Upload meldete Erfolg, und
die Phase fehlte trotzdem.

- **Uhr:** Die Antwort enthält jetzt bei Bedarf `rejected` mit den Ursachen.
  `ok: true` zusammen mit `rejected` heißt: angekommen, aber nicht vollständig
  übernommen.
- **Import und Wiedereinspielen:** Die übersprungenen Datensätze werden nach
  Ursache aufgeschlüsselt. „40 übersprungen" war nicht deutbar — es konnte
  „alles war schon da" heißen (gut) oder „alles war kaputt" (schlecht). Vier
  verschiedene Gründe fielen in einen Zähler.

### Kleinigkeit

Die Meldung zu Koordinaten außerhalb des gültigen Bereichs lautete „außerhalb
von ±9" statt „±90" — nachlaufende Nullen wurden abgeschnitten. Eine
Fehlermeldung, die selbst falsch ist, kostet mehr Zeit als gar keine.

## [Web 4.1.2] — 2026-08-08

### Die Kette „unlesbarer Schlüssel" ist geschlossen

Fünf Befunde, die einzeln je harmlos aussehen und zusammen dazu führen können,
dass geschützte Angaben unbemerkt unlesbar werden und der Verlust erst auffällt,
wenn er nicht mehr rückgängig zu machen ist.

### Behoben — Ein Fehler beim Passwortwechsel konnte alle Angaben endgültig unlesbar machen

Beim Passwortwechsel wird der Inhaltsschlüssel im Browser aus der alten Hülle
geholt und in eine neue gepackt. Der Server kann keine der beiden öffnen — er
konnte deshalb **nicht erkennen, ob darin überhaupt derselbe Schlüssel steckt**.
Enthielte die neue Hülle einen anderen, wäre danach jeder vorhandene Datensatz
unlesbar, und zwar endgültig: Die alte Hülle ist dann überschrieben.

Jetzt sendet der Browser eine **Prüfsumme des Inhaltsschlüssels** mit
(`users.pat_key_check`, seit Web 4.0.0 vorhanden). Stimmt sie nicht mit der
gespeicherten überein, wird abgelehnt und **nichts geändert**. Der Server lernt
dadurch nichts über den Schlüssel — er vergleicht zwei Hashwerte, und der
Schlüssel selbst ist 256 Bit Zufall.

Konten aus der Zeit davor haben keine gespeicherte Prüfsumme. Sie werden weiter
angenommen und bekommen sie beim nächsten Setzen des Passworts — alles andere
sperrte sie aus, denn der Server kann sie nicht nachträglich berechnen. Die
Prüfung greift ebenso beim Zurücksetzen über den Wiederherstellungsschlüssel
und beim erstmaligen Einrichten.

### Behoben — Ein Kontowechsel im selben Tab konnte einen fremden Schlüssel durchreichen

Der Zwischenspeicher lieferte den Inhaltsschlüssel zurück, **ohne zu prüfen, ob
er zur übergebenen Hülle gehört**. Die Richtigkeit hing allein daran, dass jeder
Weg, auf dem das Konto wechseln kann, vorher aufräumt — vier Stellen taten das,
eine nicht. Ein Schlüssel aus Konto A entschlüsselt in Konto B nichts, und der
Fehlschlag sah aus wie „keine Angaben vorhanden".

Statt fünf Aufrufer zur Disziplin zu erziehen, korrigiert sich der
Zwischenspeicher jetzt selbst (`assets/keyguard.js`): Er merkt sich eine kurze
Kennung der Hülle, aus der der Schlüssel stammt, und verwirft ihn, wenn sie
nicht passt. Zusätzlich läuft er nach derselben Frist ab wie die Sitzung
(30 Minuten) — vorher hing er am Tab und überdauerte sie.

### Behoben — Unlesbare Einträge sahen aus wie leere

Fünf Stellen entschlüsselten je Datensatz und fingen den Fehlschlag ab, ohne
ihn nach außen sichtbar zu machen. Die Absicht ist richtig — ein unlesbarer
Datensatz darf die Liste nicht zerstören. Es fehlte die Unterscheidung:

| | vorher | jetzt |
|---|---|---|
| keine Angaben erfasst | `–` | `–` |
| vorhanden, nicht lesbar | `–` | **⚠** |

Dazu erscheint über der Liste ein Hinweis mit der Zahl der betroffenen Einträge.
Sind **alle** unlesbar, nennt er die wahrscheinliche Ursache (nicht passender
Schlüssel) und rät, den Wiederherstellungsschlüssel bereitzuhalten, bevor
weitere Schritte unternommen werden. In der Einzelansicht steht statt der
Angaben ein ausdrücklicher Absatz — dort sieht man nur einen Einsatz, und ein
stiller Fehlschlag wäre von „nichts erfasst" nicht zu unterscheiden.

Warum das zählt: Wer den Unterschied nicht sieht, merkt nicht, dass sein
Schlüssel nicht mehr passt.

### Behoben — Eine abgelaufene Sitzung ließ die Schlüssel im Browser liegen

Es gibt zwei Wege, auf denen eine Sitzung endet. Das Abmelden löste es richtig:
Eine reine Weiterleitung per Kopfzeile führt nie JavaScript aus, deshalb wurde
dort eine kurze Seite ausgeliefert, die die Schlüssel räumt. Der **Ablauf der
30-Minuten-Frist** tat genau das nicht — Daten- und Inhaltsschlüssel blieben im
Tab liegen, obwohl die Sitzung vorbei war. Wer seinen Rechner stehen lässt,
hatte eine abgelaufene Sitzung und einen liegengebliebenen Schlüssel.

Beide Wege laufen jetzt über dieselbe Funktion (`session_lib.php`), damit sie
nicht wieder auseinanderlaufen.

### Behoben — Nach Ablauf der Frist stand man ohne Erklärung auf der Anmeldeseite

Der Ablaufpfad hängte `?timeout=1` an die Adresse — einen Parameter, den die
Anmeldeseite gar nicht auswertete. Aus Sicht der NutzerIn verschwand die
Anwendung einfach. Jetzt steht dort, was passiert ist. Der alte Parametername
wird weiter erkannt, damit ein offener Tab mit alter Adresse nicht ins Leere
läuft.

### Geändert — Sicherungen tragen jetzt ihr Herkunftskonto

Seit Web 4.1.0 nimmt eine Sicherung den Chiffretext mit, wenn ein Einsatz sich
beim Erstellen nicht entschlüsseln ließ. Beim Einspielen war bisher nicht zu
entscheiden, ob diese Angaben im Zielkonto lesbar sein würden.

Der Dateikopf enthält deshalb jetzt `pat_key_check`, die Prüfsumme des
Inhaltsschlüssels der Herkunft. Stimmt sie mit dem Zielkonto überein, werden
die Angaben übernommen und sind wieder lesbar; die Meldung sagt es. Stimmt sie
nicht oder fehlt sie (ältere Dateien), fragt das Einspielen ausdrücklich nach
und nennt den Grund. Übernommen wird auch dann — die Angaben zu verwerfen wäre
schlechter —, aber nicht unbemerkt.

Damit ist das Kennzeichen `pat_unreadable` **benutzt** statt erzeugt, in die
Datei geschrieben und beim Einspielen weggeworfen. Der Zwischenzustand war der
schlechteste von dreien.

## [Web 4.1.1] — 2026-08-08

### Berichtigt — Der JSON-Vertrag beschrieb eine Phase, die es nicht gibt

Der Vertrag zwischen Uhr und Server (`docs/JSON-Vertrag.md`, jetzt Fassung 1.2)
führte an drei Stellen eine **Phase 10** auf: als Auslöser des Uploads, in der
Nummernreferenz und in der Regel, dass sie als letzter Eintrag mitgesendet
werde. Phase 10 wurde mit der Migration `2026_07_19_phase10_entfernen`
abgeschafft.

Das war nicht nur veraltet, sondern **irreführend**: Alle Schreibwege lehnten
Phasennummern außerhalb von 2 bis 9 bereits ab. Wer nach dem Vertrag
implementierte, sendete eine Phase 10, bekam keine Fehlermeldung und hatte
einen Eintrag weniger. Der Abschluss eines Einsatzes läuft über `final: true`
zusammen mit `ended_at` — beides zusammen, und keine Phase.

Ebenfalls entfernt: die Beschriftung „Beendigung Einsatz" für Phase 10 in
`db.php`. Sie ließ einen Altbestand als gültigen Zustand erscheinen; ohne sie
erscheint er als unbekannte Phase — und das ist er.

### Neu — Der Vertrag legt jetzt fest, was vorher offen war

- **Führende Listen.** Die Reanimationsarten liegen in Uhr-App und Server
  zusätzlich als Konstante vor. Welche Fassung gilt, stand nirgends. Jetzt
  gilt der Vertrag, und dort steht auch, dass eine neue Art an drei Stellen zu
  ergänzen ist.
- **Grenzen und Mengen** (Phasennummern, Koordinaten, Längen, Höchstzahlen je
  Einsatz) stehen erstmals an einer Stelle statt verteilt im Code.
- **Fehlende gegen leere Liste.** Ein fehlender Schlüssel heißt „dazu sage ich
  nichts", eine leere Liste „es gibt keine" — und löscht nichts. Der Grund ist
  der Weg dorthin: Eine leere Liste entsteht viel wahrscheinlicher durch einen
  Fehler beim Aufbau der Nachricht als durch die Absicht, eine dokumentierte
  Reanimation zu entfernen.
- **Format der Client-Kennung.** Sie wird von vier Stellen erzeugt (`m-`, `r-`,
  `man-`, `imp-`, `bak-`), und an ihrem Präfix hängt Verhalten: Beim endgültigen
  Löschen kommt die Kennung auf eine Sperrliste — für `man-` bewusst nicht,
  dort gibt es keine Uhr, die etwas nachliefern könnte. Das stand nur im Code.

**Neu ist außerdem ein Abschnitt „Stand der Durchsetzung".** Er sagt offen,
welche Regeln der Server heute schon durchsetzt und welche noch nicht. Ein
Vertrag, der etwas zusichert, was der Code nicht einhält, ist schlimmer als gar
keiner. Der Abschnitt verschwindet, sobald alle Zeilen „durchgesetzt" lauten.

### Neu — Wo die geschützten Angaben liegen, steht jetzt in der Technikdoku

`Technik.md` listet auf, **welche Felder im verschlüsselten Block liegen**
(Name, Geburtsdatum, Alter, Diagnose, Einsatznummer, Adresse, Koordinaten,
Ortsbeschreibung) und welche im Klartext in der Datenbank stehen. Der Server
kann den Block nicht lesen — genau deshalb muss die Liste woanders stehen, sonst
lässt sich weder eine Auskunft nach Datenschutzrecht beantworten noch
beurteilen, was ein Datenbankabzug preisgibt.

Festgehalten ist dort auch, dass die Klartextangaben für sich genommen nicht
personenbeziehbar sind, **in Verbindung mit Ort und Zeitpunkt eines Einsatzes
aber werden können**.

### Geändert — Die Zahlen in den Uhr-Dokumenten nennen ihr Gerät

`Uhr-Layout.md` beschreibt Regeln, die an Geräten beobachtet und nicht aus
einer Spezifikation abgeleitet wurden. Ohne die Angabe, an welchem Gerät, ist
eine solche Regel beim nächsten Zielgerät nicht bewertbar: Wer nicht weiß, ob
„85 %" auf einem runden 260er oder einem runden 390er Display gemessen wurde,
kann nicht entscheiden, ob die Zahl auf einem eckigen 416er noch gilt. Ein
neuer Abschnitt 0 nennt die drei Prüfgeräte samt Profil und macht die Angabe
zur Konvention.

### Kleinere Berichtigungen

- Handbuch: „Einsätze bei Phase 10" → beim Abschluss des Einsatzes.
- Handbuch: Die **Sperrliste gegen Nachlieferungen hält 90 Tage** — bisher nur
  die Papierkorbfrist genannt. Relevant für eine lange abgeschaltete Uhr.
- `schema.sql`: Der Kommentar zu den Kopplungscodes nannte weiterhin 60 Minuten
  und sicherte Einmaligkeit zu, die erst seit Web 4.1.0 durchgesetzt ist.
- `update.php`: Der Kommentar zur Migration `site_desc` verwies auf eine
  Rettungsseite, die es seit Web 3.4.0 nicht mehr gibt.

## [Web 4.1.0] — 2026-08-08

### Sofortmaßnahmen aus dem Code-Review

Sieben Änderungen, alle klein, die drei der vier gefundenen Befundketten an je
einer Stelle unterbrechen. Sie bauen auf den Bausteinen aus Web 4.0.0 auf.

### Geändert — Kopplung der Uhr: 6 Zeichen, 10 Minuten, wirklich einmalig

Der Kopplungscode ist jetzt **sechs Zeichen** lang (vorher fünf) und **10
Minuten** gültig (vorher 60). Je Konto gibt es höchstens **einen offenen
Code**; ein neu erzeugter macht den vorherigen ungültig. Wiederholte
Fehlversuche werden abgewiesen.

Der Grund in Zahlen: Fünf Zeichen aus einem Alphabet von 32 sind 25 Bit, also
33,5 Millionen Möglichkeiten. Die einzige Bremse war eine feste Verzögerung von
0,3 Sekunden je Anfrage — die verzögert die *einzelne* Anfrage, behindert
parallele aber überhaupt nicht. Mit 2000 gleichzeitigen Verbindungen war der
gesamte Coderaum in **rund 1,4 Stunden** durchlaufbar, und die Codes waren eine
Stunde gültig. Sechs Zeichen sind 30 Bit (1,07 Milliarden); zusammen mit dem
Ratenschutz und der kürzeren Gültigkeit liegt die Trefferchance je Code jetzt
unter einem Millionstel Prozent. **Der Ratenschutz trägt dabei die Hauptlast** —
die Codelänge allein täte es nicht.

Das Prüfmuster bildet außerdem das tatsächliche Alphabet ab. Vorher ließ es
vier bis acht Zeichen zu und ausdrücklich auch `0`, `O`, `1` und `I` — die im
Alphabet bewusst fehlen, weil sie auf einem Uhrendisplay nicht zu unterscheiden
sind. Ein Muster, das mehr erlaubt, als der Erzeuger je ausgibt, prüft nichts.

Die Uhr-App braucht dafür keine Änderung.

### Behoben — Ein Kopplungscode war nicht wirklich einmalig

Bisher suchte erst eine Abfrage den Code und entwertete ihn dann — das Ergebnis
der Entwertung wurde nicht ausgewertet. Zwei gleichzeitige Anfragen mit
demselben Code fanden ihn deshalb **beide** gültig und legten **beide** ein
Gerät an. Die Dokumentation sicherte die Einmaligkeit zu, der Code setzte sie
nicht durch. Jetzt entwertet die Anfrage zuerst und nimmt den Code erst über
das Ergebnis dieser Entwertung als gültig an: Die Datenbank entscheidet, und
genau eine Anfrage gewinnt.

### Behoben — Eine Sicherung konnte Daten vernichten statt sie zu sichern

Ließ sich ein Einsatz beim Erstellen einer Sicherung nicht entschlüsseln, wurde
sein Chiffretext **trotzdem entfernt** — das Entfernen stand hinter dem
Fehlerblock und lief deshalb auch im Fehlerfall. Gemeldet wurde „Fertig". In
der Datenbank lagen die Daten noch und wären mit dem richtigen Schlüssel lesbar
gewesen; in der Datei waren sie weg.

Das ist die gefährlichste der gefundenen Ketten, weil sie erwartbares Verhalten
bestraft: Wer merkt, dass mit seinen Daten etwas nicht stimmt, erstellt als
Erstes eine Sicherung. Jetzt bleibt der Chiffretext in der Datei (Format
`.edbak`, Feld `pat_blob` neben `pat_unreadable`), und die Meldung nennt die
Zahl der betroffenen Einsätze deutlich. Zurück in dasselbe Konto gespielt, sind
die Angaben wieder lesbar.

### Behoben — Ein Serverfehler erzeugte eine echte, aber leere Sicherungsdatei

Antwortete der Server beim Datenabruf mit einem Fehler — was er ausdrücklich
vorsieht —, liefen alle Schleifen über nichts, und es entstand eine echte
`.edbak`-Datei mit korrektem Kopf und richtigem Passwort, die ausschließlich
die Fehlermeldung enthielt. Sie ließ sich öffnen und wäre erst beim Einspielen
als leer aufgefallen, möglicherweise Monate später. Jetzt wird der
Antwortstatus geprüft und abgebrochen, **bevor** eine Datei entsteht. Eine
fehlende Einsatzliste gilt dabei als Fehler, nicht als leerer Bestand; bei
tatsächlich leerem Bestand erscheint ein Hinweis statt „Fertig".

### Behoben — Die Anmeldeseite verriet, welche Konten existieren

Zu einer unbekannten E-Mail-Adresse liefert der Server ein Pseudo-Salt, damit
die Antwort nicht von der eines echten Kontos zu unterscheiden ist. Sie war es
aber: Ein echtes Salt hat **32** Hexzeichen, das Pseudo-Salt hatte **64**. Die
bloße Länge der Antwort sagte damit, ob zu dieser Adresse ein eingerichtetes
Konto existiert — die gesamte Vorkehrung war wirkungslos. Behoben durch
Zuschnitt auf 32 Zeichen; Zeichenvorrat und Verteilung stimmten bereits überein.

### Geändert — Die Wartungsseite führt beim Aufrufen nichts mehr aus

`update.php` läuft zweistufig: Der **Aufruf zeigt an**, welche Migrationen
anstünden und ändert nichts; erst der Knopf **„Updates jetzt anwenden"** führt
sie aus, mit Formular-Token. Der Rat, vorher eine Sicherung zu erstellen, steht
jetzt **vor** dem Lauf statt danach.

Vorher war der Aufruf der Seite bereits die Ausführung — auch aus dem Verlauf
heraus oder durch einen Vorschau-Abruf des Browsers. Unter den Migrationen sind
solche, die Spalten samt Inhalt löschen. Eine unwiderrufliche Handlung auf
einen GET hin ist immer falsch. Der Notausgang über die Kommandozeile
(`php update.php`) bleibt einstufig und gibt sein Ergebnis jetzt als Text aus.

### Geändert — Klartext bei der Ersteinrichtung

Die bisherige Aussage („der Server kann die Angaben nicht lesen") war richtig
und unvollständig. Ergänzt ist jetzt, was daraus folgt: **Die Stärke des
Passworts ist unmittelbar die Stärke der Verschlüsselung.** Weil der Server das
Passwort nie sieht, kann er seine Güte auch nicht prüfen und ein schwaches
nicht ausgleichen — es gibt keine zweite Hürde dahinter.

## [Web 4.0.0] — 2026-08-08

### Neu — Gemeinsame Bausteine und Schemaänderungen für die Review-Umsetzung

Ein Code-Review in sieben Durchgängen hat 117 Befunde ergeben, keinen davon
kritisch. Diese Auslieferung ist der **erste von mehreren Schritten** ihrer
Behebung. Sie legt ausschließlich die Grundlagen: neun gemeinsame Bausteine
und sechs Schemaänderungen.

**Für den laufenden Betrieb ändert sich nichts.** Das ist beabsichtigt: Die
Bausteine existieren und sind einsatzbereit, werden aber noch nicht verwendet.
Einzige Ausnahme ist der Ratenschutz, der ab der nächsten Auslieferung
gebraucht wird und deshalb bereits vollständig funktioniert.

**Neue Bausteine (`server/`, `server/assets/`)**

| Datei | Aufgabe |
|---|---|
| `validate_lib.php` | Eine Prüfschicht für Einsatzdaten — Wertebereiche, Längen, Formate, Mengen. Die Regeln stammen aus `api/import_commit.php` und sind dorthin gehoben, nicht neu erfunden. Enthält auch die Kalendertagsprüfung. |
| `ratelimit_lib.php` | Ratenschutz je Kontokennung **und** IP-Adresse, in der Datenbank statt in der Sitzung. |
| `session_lib.php` | Ein Sitzungsende für beide Wege (Abmelden und Ablauf), das die Schlüssel im Browser räumt und den Grund nennt. |
| `assets/keyguard.js` | Bindet den zwischengespeicherten Inhaltsschlüssel an die Hülle, aus der er stammt, und lässt ihn mit der Sitzungsfrist ablaufen. |
| `assets/pwquality.js` | Passwortgüte: Mindestlänge im Skript statt nur als HTML-Attribut, Stärkeanzeige, Abgleich gegen häufige Passwörter. |

Dazu erweitert: `assets/crypto.js` (Prüfsumme des Inhaltsschlüssels,
Hüllenkennung), `assets/patient.js` (eine Entschlüsselungsschleife, die
zwischen „keine Angaben" und „nicht lesbar" unterscheidet),
`assets/missiontable.js` (eine Maskierungsfassung, die auch in
Attributpositionen sicher ist).

**Warum die Kalendertagsprüfung nötig war.** Die Datumsumwandlung liefert bei
einem unmöglichen Tag kein Fehlerergebnis, sondern rechnet weiter: Aus dem
30. Februar wird der 2. März. Sichtbar wird das ausschließlich über die
Warnungsabfrage der Datumsklasse — und die wurde nirgends abgefragt. Ein
Tippfehler in einer Importdatei wurde so zu einem stillen Datumssprung.

**Warum es zwei Grenzen für den verschlüsselten Patientenblock gibt.** Die
Untergrenze ist jetzt hergeleitet statt geschätzt: AES-256-GCM legt 12 Byte
Zufallswert davor und hängt 16 Byte Prüfwert an — auch bei leerem Klartext
sind das 28 Byte, in base64 also 40 Zeichen. Kürzer *kann* ein gültiger Block
nicht sein. Im Umlauf waren bisher drei verschiedene Untergrenzen (16, 20 und
gar keine), alle unterhalb des überhaupt Möglichen. Die Obergrenze bleibt bei
60000 Zeichen: Ohne sie entscheidet die Datenbank, und ihre Entscheidung ist
entweder ein Abbruch oder stilles Abschneiden — ein abgeschnittener
Chiffretext ist dauerhaft unlesbar.

### Datenbank — sechs Änderungen in einer Migration

Anzuwenden über **Verwaltung → Datenbank-Update**.

| | Änderung |
|---|---|
| `users.kdf_iter` | Rundenzahl der Schlüsselableitung, je Konto. Bestand auf den heutigen Wert (310000) gesetzt. |
| `users.pat_key_check` | Prüfsumme des Inhaltsschlüssels. Bleibt für Bestandskonten leer — der Server kann sie nicht berechnen, er kennt den Schlüssel nicht. |
| `users.session_epoch` | Zähler, mit dem ein Passwortwechsel offene Sitzungen beenden kann. |
| `rate_limits` | Neue Tabelle für den Ratenschutz. Wird vom Aufräumjob mitentsorgt. |
| `deleted_refs.owner_type` | Die Sperrliste gilt jetzt auch für Ruhe-Segmente. Schlüssel entsprechend erweitert. |
| `users.email` | Sortierregel ausdrücklich festgelegt (`utf8mb4_unicode_ci`). |

**Zur Rundenzahl, weil es die heikelste Änderung ist:** Sie wird hier **nur
angelegt und gefüllt**. Kein Code liest sie, der Salt-Endpunkt bleibt
unverändert. Der Grund ist Vorsicht — ein Fehler an der Schlüsselableitung
sperrt nicht ein Konto aus, sondern alle gleichzeitig. Die drei Folgeschritte
(Salt-Endpunkt liefert die Zahl mit, Browser rechnet damit, stille Anhebung
bei der nächsten Anmeldung) folgen einzeln und jeweils rückwärtsverträglich.

**Zur Sortierregel:** Dass die Anmeldung heute trotz uneinheitlicher
Normalisierung der E-Mail-Adresse funktioniert, lag allein an der
Standardsortierregel der Datenbank. Auf einer Installation mit
unterscheidender Sortierregel schlüge sie für jede Adresse fehl, die nicht
exakt wie beim Anlegen eingetippt wird — mit der Meldung „Anmeldung
fehlgeschlagen" und ohne Hinweis auf die Ursache. Das Projekt liegt offen;
diese Annahme sollte nicht ungeschrieben bleiben.

Nach der Migration melden sich bestehende Konten unverändert an, und
bestehende Sicherungsdateien lassen sich unverändert öffnen.

## [Web 3.6.0] — 2026-08-06

### Neu — Exportdateinamen sagen, was in der Datei steckt

Bisher hieß eine Exportdatei
`luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>`. Ob darin Patientendaten
lagen und ob sie verschlüsselt war, ließ sich erst nach dem Öffnen sagen — in
einem Ordner mit mehreren Exporten die falsche Reihenfolge, weil genau diese
beiden Angaben darüber entscheiden, wie die Datei zu behandeln ist. Der Name
lautet jetzt:

```
luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>_<inhalt>_<schutz>_<konto>.<endung>

luftrettungsdokumentation_export_06-08-2026_standard_ohne-pat_unverschl_philipp-mueller.xlsx
luftrettungsdokumentation_export_06-08-2026_csv_mit-pat_verschl_philipp-mueller.zip
```

`<inhalt>` ist `mit-pat` oder `ohne-pat`, `<schutz>` ist `verschl` oder
`unverschl`. **Beide Marker stehen immer da, auch im Negativfall** — fehlte der
Negativfall, wäre eine Datei ohne Patientendaten nicht von einer Datei aus
einem Stand vor dieser Regel zu unterscheiden.

`<schutz>` beschreibt die Datei, an der er steht, nicht den Vorgang: Bei den
Excel-Profilen mit Passwort liegt in einem Archiv `…_verschl.zip` eine Tabelle
`…_unverschl.xlsx`. Nach dem Entpacken ist sie offen, und das ist die Angabe,
auf die es beim Aufbewahren ankommt.

### Neu — Kontokennung im Exportdateinamen

`<konto>` benennt das Konto, aus dem der Export stammt: der Anzeigename aus den
Einstellungen, und wenn dort keiner hinterlegt ist, die E-Mail-Adresse. Beides
wird zu einem dateisystemsicheren Segment bereinigt — Kleinbuchstaben, Umlaute
nach deutscher Lesart ausgeschrieben (`Philipp Müller` → `philipp-mueller`),
übrige Akzente auf den Grundbuchstaben zurückgeführt, alles Weitere zu `-`
zusammengezogen, auf 40 Zeichen gekürzt. Bei der E-Mail-Adresse entfallen `@`
und Punkte (`max@gen-em.de` → `max-gen-em-de`); blieben die Punkte stehen, sähe
der Name nach mehrfacher Dateiendung aus. Bleibt von Name und Adresse nichts
übrig, steht `konto` da, damit das Segment nie leer ist.

Ein eigenes Nachnamenfeld gibt es nicht — `users` führt nur `email` und den
freien Anzeigenamen `name`. Deshalb wandert der vollständige Anzeigename in den
Namen und nicht sein letztes Wort: Eine Heuristik darauf bricht bei
Namenszusätzen („van der Berg\") und bei Konten, die gar keine Person benennen.

**Beim Weitergeben zu bedenken:** Der Dateiname nennt damit auch das Konto, im
Zweifel die E-Mail-Adresse. Einen Bezug auf eine bestimmte **Patientin oder
einen Patienten** enthält er weiterhin nicht — `mit-pat` sagt nur, *dass*
Patientendaten enthalten sind.

### Unverändert

- **Die Namen innerhalb der Archive.** `einsaetze.csv`, `felder.csv`,
  `LIESMICH.txt` und `tracks/` sind Teil des Formats; der Rückimport sucht im
  Archiv nach genau diesen Namen. Ein Marker daran hätte das Zurücklesen
  verschlüsselter CSV-Archive gebrochen.
- **Das Backup (`.edbak`).** Es ist immer verschlüsselt und enthält immer
  Patientendaten — Marker hätten dort keinen Informationswert.
- Alle Feldlisten und Spaltensätze der drei Profile.

### Dokumentation

- `Export-Format.md`: Namensschema als Tabelle mit allen Segmenten, Beispielen,
  der Bereinigungsregel und der Abgrenzung zwischen „Marker\" und
  „Patientenbezug\".
- `Handbuch.md` 7.1: neuer Absatz zum Dateinamen mit den beiden Feinheiten
  (Schutzmarker der inneren Datei, Umschreibung von Umlauten) und dem Hinweis
  zur Weitergabe.
- `Technik.md`: Stolperstein ergänzt, dass die Marker nur nach aussen gehören
  und die Archivnamen unberührt bleiben.

## [Web 3.5.0] — 2026-08-06

### Behoben — Alter fehlte im Excel-Export, wenn kein Geburtsdatum vorlag

Die Spalte „Alter" in Excel (Standard) las das Alter über `EdPat.alterAm()` und
damit ausschließlich aus dem Geburtsdatum. Bei unbekannten Personen — dem
Regelfall für ein von Hand eingetragenes Alter — stand dort ein Bindestrich,
obwohl die Einsatzansicht den Wert anzeigt. Sie nutzt `EdPat.alterAnzeige()`,
das nach dem Geburtsdatum auf den gespeicherten `age` zurückfällt; genau so
lesen es auch Tages- und Zeitraumübersicht sowie die Suche. `export.js` war die
einzige Stelle mit der falschen der beiden Funktionen und zieht nun nach.

### Neu — Spalte `pat_alter` im vollständigen CSV

`einsaetze.csv` führt das Alter jetzt als eigene Spalte, direkt hinter
`pat_geburtsdatum`. Sie trägt den **Rohwert** `pat_blob.age` und ist deshalb bei
einem Einsatz mit Geburtsdatum leer: Die Anwendung speichert das Alter nur,
wenn es sich nicht aus dem Geburtsdatum ergibt, und eine zweite, hineingerechnete
Quelle liefe auseinander, sobald jemand das Geburtsdatum korrigiert. Wer das
Alter auswerten will, rechnet es aus `pat_geburtsdatum` und `flugtag` und greift
auf `pat_alter` zurück, wenn das Geburtsdatum fehlt — dieselbe Reihenfolge wie in
der Anwendung. `Export-Format.md` hält das in einem eigenen Abschnitt (3.7) fest.

Der Rückimport übernimmt die Spalte (`pat_alter` → `pat_blob.age`). Beim Bauen
des verschlüsselten Blocks gilt dieselbe Regel wie im Formular: Ein Alter wird
nur gespeichert, wenn es nicht aus dem Geburtsdatum derselben Zeile folgt — das
fängt von Hand nachbearbeitete Dateien ab, in denen beides steht. Exportdateien
bis Web 3.4.0 ohne die Spalte lassen sich unverändert weiter einlesen; die
Formaterkennung zählt Treffer gegen 78 erwartete Spaltennamen bei einem
Schwellwert von 20.

Der CSV-Spaltensatz wächst damit von 76 auf 77 Spalten (`felder.csv` und
`einsaetze.csv` bleiben deckungsgleich). Excel (Standard) bleibt bei 29 Spalten
— das Alter stand dort schon, es war nur nicht immer gefüllt.

**Excel (GuteSeele) bleibt unverändert bei 13 Spalten.** Das Layout ist die
Absprache mit dem Empfänger und deckungsgleich mit dem Importprofil
`ch17_jahresliste`; eine zusätzliche Spalte würde beides verschieben. Das Feld
„Geb.dat" ist eine Datumsspalte und nimmt kein Alter auf.

**Grenze beim Rückweg über Excel (Standard):** Die dortige Spalte „Alter" führt
mal einen gerechneten, mal einen gespeicherten Wert und wird beim Import
weiterhin verworfen. Der Warnhinweis vor dem Import nennt sie jetzt ausdrücklich.
Für einen vollständigen Rückweg ist das CSV zuständig.

## [Web 3.4.0] — 2026-08-06

### Behoben — bearbeiteter Uhr-Einsatz stand im Export als „manuell"

Die Spalte `herkunft` im vollständigen CSV wurde bei jedem Export neu aus
`manual` und dem Präfix von `client_ref` berechnet — eine Regel aus der Zeit vor
der Spalte `missions.origin`. Wer einen von der Uhr aufgezeichneten Einsatz im
Formular korrigierte, bekam damit `manual = 1` und im Export „manuell", obwohl
`origin` korrekt auf `watch` stand. Von den vier möglichen Fällen war genau
dieser eine falsch.

Die Herkunft kommt jetzt aus `missions.origin`, der Ausgabewert entsteht über
eine feste Abbildung (`watch → uhr`, `manual → manuell`, `import → import`).
`client_ref` wird im Export nicht mehr gelesen. Die Einsatzansicht war schon
vorher richtig — der Export zieht damit nach, beide zeigen für denselben Einsatz
dasselbe.

Die gleichlautende Ableitungsregel in `backup_lib.php` bleibt bestehen: Backups
der Formatversion 3 und älter kennen `origin` und `edited` nicht, dort wird sie
weiterhin gebraucht.

### Neu — Spalte `edited` im vollständigen CSV

`einsaetze.csv` führt den Bearbeitungsstatus jetzt als eigene Spalte, direkt
hinter `manual`. Damit stehen drei Angaben nebeneinander, die drei verschiedene
Fragen beantworten: `herkunft` wie der Einsatz entstanden ist, `edited` ob er
danach verändert wurde, `manual` ob die Uhr ihn noch überschreiben darf.
`Export-Format.md` grenzt sie in einem eigenen Abschnitt (3.6) gegeneinander ab.

Beim Rückimport werden `herkunft` und `edited` **nicht** übernommen — beide
beschreiben, wie ein Datensatz in der Installation entstanden ist, aus der die
Datei stammt. Beim Einlesen entsteht er neu. Exportdateien bis Web 3.3.2 ohne
die Spalte lassen sich unverändert weiter einlesen; die Formaterkennung zählt
Treffer gegen 77 erwartete Spaltennamen bei einem Schwellwert von 20.

Die beiden Excelformate bleiben unverändert bei 29 Spalten und führen weder
Herkunft noch Bearbeitungsstatus. Die Übersichtstabelle ist zum Ansehen,
Sortieren und Filtern gedacht, und zusätzliche Spalten würden die
`expectedHeaders` des Importprofils `export_excel_v1` mitverändern.

**Grenze für Altbestand:** Für Einsätze von vor dem 30.07.2026 ließ sich
`edited` nur bei Uhr-Einsätzen zuverlässig herleiten. Von Hand angelegte und
importierte Einsätze starten mit `edited = 0`, auch wenn sie bearbeitet worden
sind — rückwirkend ist das nicht mehr feststellbar. In `Export-Format.md`
festgehalten, damit Auswertende die Spalte für diesen Bestand als „mindestens"
lesen und nicht als „genau".

### Geändert — Dokumentation der Exportformate

`Export-Format.md` benennt die drei Formate durchgängig so wie das Auswahlfeld:
CSV (Standard), Excel (Standard), Excel (GuteSeele). Die Bezeichnungen „Profil
A/B/C" stammten aus der ursprünglichen Spezifikation und tauchten in der
Anwendung nirgends auf; sie sind ersatzlos entfallen, im Export-Abschnitt von
`Technik.md` ebenso. Die Tastenprofile A/B/C der Uhr-App (Technik.md 5.1) sind
etwas anderes und bleiben.

Nebenbei behoben: Zwei Feldbeschreibungen enthielten ein unmaskiertes `|` und
sprengten damit die Tabellenspalte (`herkunft`, `weitere_rettungsmittel`), und
zwei Querverweise zeigten ins Leere (`rea_json` auf 4.4 statt 3.4, `crew_p1` auf
den Abschnitt zu `rea_json` statt auf die Regel zur effektiven Besatzung —
diese steht jetzt in 3.3).

## [Web 3.3.2] — 2026-08-05

### Behoben — Adresssuche überschrieb bestätigte Koordinaten

Nach dem Bestätigen von Koordinaten lief im Einsatzort-Feld beides weiter: die
Formaterkennung und die Adresssuche. Ein Klick auf einen Adressvorschlag setzte
`#loclat`/`#loclon` neu — die eben bestätigten Koordinaten waren damit
stillschweigend weg, obwohl der Chip nur die Bezeichnung erwarten ließ.

Solange Koordinaten gesetzt sind, ist das Textfeld jetzt reines
Bezeichnungsfeld: Der `input`-Zuhörer steigt früh aus, es gibt keine
Vorschlagsliste und keine Anfrage an Photon. Placeholder („Bezeichnung des
Einsatzortes") und Meldungszeile weisen darauf hin, damit das Feld nicht defekt
wirkt. Nach dem Entfernen der Koordinaten über das ✕ am Chip arbeitet die Suche
ab dem nächsten Tastenanschlag wieder unverändert.

## [Web 3.3.1] — 2026-08-05

### Entfernt — Klartextspalte `missions.site_desc` und die Seite „Beschreibungen sichern"

Der Altbestand von 13 Beschreibungen wurde über die Textdatei gesichert und von
Hand in den verschlüsselten Block nachgetragen. Damit hat die Spalte ihren
Zweck erfüllt: Die Migration `2026_08_05_site_desc_entfernt` entfernt sie
(`ALTER TABLE missions DROP COLUMN site_desc`), `schema.sql` führt sie nicht
mehr, und `site_desc_rettung.php`, der Leisteneintrag in `ui.php` sowie
`site_desc_rest_vorhanden()` sind weggefallen.

Der `pat_blob` ist davon **nicht** betroffen — sein Schlüssel `site_desc` trägt
die Beschreibung seit Web 3.3.0 und bleibt unverändert. Ebenso bleibt die
CSV-Kopfzeile `site_desc` beim Import erhalten, damit Exportdateien bis
Web 3.2.0 lesbar bleiben.

Ebenfalls entfallen: Die Zeile in `edbak_build()`, die die Spalte ausdrücklich
aus dem Backup entfernte. Sie war nur nötig, solange `SELECT *` sie noch
lieferte.

**Reihenfolge beim Einspielen:** erst die Dateien, dann `update.php` öffnen. Die
Migration läuft ohne Rückfrage, sobald die Seite aufgerufen wird.

## [Web 3.3.0] — 2026-08-05

### Neu — Bezeichnung zu Koordinaten

Wird der Einsatzort über Koordinaten oder einen Plus Code eingegeben, blieb
bisher kein lesbarer Ortsname übrig: Das Textfeld wurde beim Bestätigen mit der
normalisierten Zahlendarstellung überschrieben, und in allen Listen stand
danach ein Zahlenfragment.

Bestätigte Koordinaten erscheinen jetzt **unter** dem Textfeld als Chip mit
Kreuz zum Entfernen — dieselbe Darstellung wie bei den weiteren
Rettungsmitteln. Das Textfeld wird dabei geleert und gehört ab dann der
Bezeichnung. Damit die Zuordnung sichtbar bleibt, leert der `input`-Zuhörer die
versteckten Koordinatenfelder **nicht** mehr; eine getippte Bezeichnung
vernichtet die Koordinaten also nicht. Entfernt werden sie nur über das Kreuz
am Chip oder durch Auswahl eines anderen Adressvorschlags.

Sind Koordinaten gesetzt und das Textfeld leer, lässt sich der Einsatz nicht
speichern. Die Prüfung greift vor dem Verschlüsseln und nur bei entsperrter
Verschlüsselung — bei gesperrter bleibt der vorhandene Blob wie bisher
unangetastet. Ohne Koordinaten ist der Einsatzort weiterhin vollständig
freiwillig.

Bei einem gewählten Adressvorschlag ändert sich nichts am Ablauf: Das Label
steht im Textfeld und gilt als Bezeichnung; zusätzlich erscheint der Chip,
damit beide Wege gleich aussehen.

### Neu — Seite „Beschreibungen sichern"

`site_desc_rettung.php` gibt den verbliebenen Klartextbestand der Spalte
`missions.site_desc` als Textdatei aus: je Einsatz eine Zeile mit Datum, Beginn
in Ortszeit, interner Einsatznummer und Text. Damit lassen sich die alten Werte
von Hand nachtragen; ein automatischer Umzug ist nicht möglich, weil der
`pat_blob` ausschließlich im Browser entsteht.

Die Seite ist **vorübergehend**. Sie erscheint in der Einstellungsleiste nur,
solange überhaupt noch Werte vorhanden sind, und wird zusammen mit der Spalte
entfernt.

### Geändert — „Beschreibung Einsatzort" ist Ende-zu-Ende-verschlüsselt

Das Feld lag als Klartext in `missions.site_desc`. Es steht jetzt als eigener
Schlüssel `site_desc` auf oberster Ebene des `pat_blob` — nicht innerhalb von
`loc`, weil `loc` nur bei gefüllter Adresse entsteht und eine Beschreibung ohne
Ortsangabe sonst verloren ginge.

Im Formular steht das Feld nun im verschlüsselten Block direkt unter dem
Einsatzort; bei gesperrter Verschlüsselung ist es deaktiviert und wird beim
Speichern nicht verändert. In der Einsatzansicht erscheint es mit Schloss-Zeichen
unter dem Einsatzort statt in der generischen Zusatzfeldliste. **Die Suche
findet seinen Inhalt erst nach dem Entsperren** — dieselbe Bedingung, unter der
Diagnose und Einsatzort schon vorher standen.

Der Eintrag hat `mission_fields.php` verlassen; damit verschwindet das Feld
zugleich aus Formularausgabe, Formularauswertung, `api/mission.php` und der
Backup-Wiederherstellung, die alle generisch über `$FIELDS` laufen. Ebenfalls
entfernt: die Auswahl in `api/day.php`, `api/range.php`, `api/suchindex.php` und
`api/export_data.php` (in `day.php` und `range.php` wurde das Feld von keiner
Seite ausgewertet).

**Nichts wird gelöscht.** Die Spalte `missions.site_desc` bleibt bestehen, es
gibt keine Migration. Eine Löschmigration liefe beim Öffnen von `update.php`
sofort und würde den Klartext vor jeder Sicherung vernichten; das Entfernen der
Spalte ist eine eigene, spätere Auslieferung.

### Geändert — Export, Import, Backup

- CSV: `site_desc` entfällt aus dem ungeschützten Bereich; neu im geschützten
  Bereich hinter `pat_ort_lon` die Spalte **`pat_ort_beschreibung`**. Ohne den
  Haken „Patientendaten einschließen" ist sie vorhanden und leer.
- Rückimport: `pat_ort_beschreibung` wird dem verschlüsselten Block zugeordnet.
  Die alte Kopfzeile `site_desc` wird weiterhin angenommen und zeigt auf
  dasselbe Ziel, damit Exportdateien früherer Versionen lesbar bleiben.
- Backup-Format auf **Version 5**: Die Beschreibung steckt im Block `pat` und
  ist damit für den Server unsichtbar. `edbak_build()` liest die Einsätze mit
  `SELECT *` und entfernt die Klartextspalte deshalb ausdrücklich — sonst
  stünde sie weiterhin im Backup.
- Excel (Standard) und Excel (GuteSeele) führen die Beschreibung wie bisher
  nicht.

### Geändert — Beschriftungen im Formular

„Adresse Einsatzort" heißt jetzt **Einsatzort** mit dem Zusatz „Adresse,
Koordinaten oder Plus Code"; die Beschreibung trägt den Zusatz „Zufahrt,
Besonderheiten, Lage vor Ort". Ohne diese Zusätze waren die beiden
untereinanderstehenden Felder beim Ausfüllen nicht auseinanderzuhalten.

### Behoben — Import legte keine Einsätze mehr an

`api/import_commit.php` bereitete die INSERT-Anweisung für Einsätze mit **32
Spalten, aber nur 31 Werten** vor (`notes` hatte keinen Platzhalter). Da die
Datenbankverbindung ohne Prepare-Emulation und mit Ausnahmen arbeitet
(`db.php`), scheitert bereits das Vorbereiten der Anweisung — der gesamte
Import-Abschluss brach ab, für jedes Profil und unabhängig von der Datei. Der
Fehler bestand seit Web 2.10.0, als die zusätzlichen Felder angehängt wurden,
und fiel hier nur auf, weil dieselbe Anweisung für `site_desc` angefasst wurde.

### Behoben — Ortsspalte zeigte bei Koordinaten ein Fragment

`extractOrt()` (`assets/missiontable.js`) nahm den letzten Bestandteil nach dem
Komma und entfernte eine führende Postleitzahl. Aus `47.72800, 10.31600` wurde
damit `10.31600`. Die Zerlegung greift jetzt nur noch, wenn der letzte
Bestandteil überhaupt Buchstaben enthält — also nach einer Adresse mit Ortsteil
aussieht. Andernfalls wird der Text vollständig durchgereicht.

Wirkt gleichermaßen auf Einsatzliste, Zeitraum-Übersicht und Suche, weil alle
drei dieselbe Funktion verwenden. Altdatensätze mit Koordinatentext in `addr`
zeigen dadurch die vollständige Koordinate; ihre Bezeichnung tragen sie beim
nächsten Bearbeiten nach, eine Migration gibt es nicht.

## [Web 3.2.0] — 2026-08-05

### Behoben — „Export erstellen" reagierte überhaupt nicht mehr

Der Knopf hatte gar keinen Klick-Zuhörer. Der Fehler passierte schon beim Laden
der Seite, nicht beim Klick — deshalb blieb auch die Statuszeile darunter leer.

Mit Web 2.11.0 wurde die Formatauswahl in `import.php` von Optionsfeldern auf
ein Auswahlfeld (`<select id="exp_fmt">`) umgestellt. `assets/export.js` fragte
an drei Stellen weiterhin `input[name="exp_fmt"]:checked` ab. `querySelector`
liefert dafür `null`; `syncFormat()` warf beim `DOMContentLoaded` einen
`TypeError` und brach den Init-Block ab. Die Registrierung des Klick-Zuhörers
auf `#exp_go` ist dessen **letzte** Anweisung und kam damit nie zum Zug.
Mitbetroffen: die GPX-Zeile erschien beim Umschalten auf CSV nicht mehr, und der
Haken „Patientendaten einschließen" wurde bei gesperrter Verschlüsselung nicht
mehr gesperrt.

**Ursache im Repo:** Der Stand von `assets/export.js` aus der Auslieferung
Web 2.11.0 („Export-Fehlerbehebung", Commit `7237ee9`) wurde durch den
Folgecommit `1413ab5` mit einem älteren Arbeitsstand überschrieben —
`git diff c14caf7 1413ab5 -- server/assets/export.js` ergibt genau eine Zeile
Unterschied, die Datei entsprach also wieder Web 2.10.0. `import.php`,
`confirm.js` und `import_profiles.js` waren an diesem Commit nicht beteiligt und
behielten den korrigierten Stand — daher der Bruch. `docs/CHANGELOG.md`,
`docs/Export-Format.md` und `docs/Handbuch.md` wurden dabei ebenfalls
zurückgesetzt; der Changelog-Abschnitt zu Web 2.11.0 (Export) fehlte seitdem
vollständig. Die betroffenen Punkte sind unten unter „Geändert — Export"
aufgeführt, weil sie erst mit dieser Auslieferung tatsächlich im Repository
ankommen; der Eintrag zu Web 2.11.0 trägt einen entsprechenden Hinweis.

### Behoben — mit demselben Stand wiederhergestellt

- Die Rückfragen vor dem Export liegen wieder **innerhalb** der
  Fehlerbehandlung. Vorher stand ein Fehler im Dialog für einen völlig stummen
  Abbruch.
- `syncPasswordGate()` löschte mit `setState('')` bei jeder Umschaltung die
  Statuszeile — auch Erfolgs- und Fehlermeldungen des letzten Exports. Es räumt
  wieder nur die eigene Begründung weg.
- Fehlende Null-Absicherung beim Zusammenstellen der Tracks (`data.missions`
  bzw. `data.rests` ohne Inhalt) wieder eingesetzt.

### Behoben — Rückimport des eigenen Standard-Excel-Exports

Profil A schrieb die Spalte „Sekundäreinsatz" und drei Zusatzspalten, während
das Importprofil `export_excel_v1` bereits „Sekundärtransport" ohne diese
Spalten erwartete. Ein Standard-Excel-Export ließ sich dadurch nicht mehr sauber
zurücklesen: vier unbekannte Spalten, eine fehlende, und `secondary` ging still
verloren. Beide Listen stimmen wieder überein.

### Geändert — Export

- Dateiname einheitlich
  `luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>.<endung>` mit
  `<profil>` = `standard`, `guteseele` oder `csv`. Das Datum ist der Tag der
  Erstellung; der ausgewählte Zeitraum steht in der Datei selbst.
- **Profil A** hat drei Spalten weniger: „davon an PatientIn", „Lastaufnahme"
  und „Bergwacht-Zusatz". In einer Übersichtstabelle sind sie entbehrlich; im
  vollständigen CSV bleiben sie erhalten. Damit hat Profil A 29 statt 32
  Spalten (davon 7 geschützte).
- Begriffe an `mission_fields.php` angeglichen: „Sekundäreinsatz" heißt wieder
  „Sekundärtransport". Das Feld `winch_airload` hieß im Export irrtümlich
  „Lastaufnahme" — es heißt im Formular **Luftverladung**; die Beschreibung in
  `felder.csv` ist entsprechend korrigiert, ebenso „Cycles mit Patient" und
  „Bergwacht: Namen / Infos".
- Die Abschlussmeldung nennt beim CSV wieder die Zahl der enthaltenen
  GPX-Tracks — einschließlich des Falls „im gewählten Zeitraum sind zu keinem
  Einsatz Trackpunkte gespeichert". Ob ein Archiv Tracks enthält, war sonst erst
  nach dem Entpacken zu sehen.
- Klarere Aussage zum Passwort im Rückfragedialog: Es lässt sich nicht
  zurücksetzen, und ohne es ist die Datei nicht mehr zu öffnen.

### Dokumentation

- `Export-Format.md`: Profiltabelle und Dateinamensschema aktualisiert; die
  Spaltentabelle zu Profil A stand seit Web 2.11.0 auf 32 Spalten, obwohl der
  Fließtext daneben bereits 29 beschrieb — jetzt durchgängig 29.
- `Handbuch.md`: Formatnamen und ihre Reihenfolge an das Auswahlfeld
  angeglichen (CSV (Standard), Excel (Standard), Excel (GuteSeele)); Passwort-
  und Dateinamenshinweis ergänzt.
- `Technik.md`: zwei Stolpersteine ergänzt — die Bindung von `#exp_fmt` an
  `gewaehltesFormat()` und die Kopplung von `SPALTEN_A` an die
  `expectedHeaders` von `export_excel_v1`.

## [Uhr 1.7.0] — 2026-08-14

Der Uhr-Teil von P9c und damit **der letzte Punkt des Reviews**. Zwei Befunde,
beide ohne Serveranteil: Die App lässt sich unabhängig von der Weboberfläche
einspielen.

### Die Client-Kennung enthält keinen Zeitstempel mehr (M7-03)

Sie bestand aus Präfix plus Sekunden seit 1970 (`m-1785000000`). Das hatte zwei
Folgen:

1. **Springt die Uhrzeit zurück** — nach einem Zurücksetzen des Geräts, nach
   einem Zeitzonenwechsel im Flugmodus —, entstehen erneut Kennungen, die es
   schon gab. Der nächste Upload trifft dann einen **fremden alten Einsatz
   desselben Geräts** und überschreibt ihn. Für einen Angriff taugt das nicht,
   weil der eindeutige Schlüssel die Gerätekennung enthält; als Datenverlust
   im eigenen Bestand taugt es sehr wohl.
2. Sie verriet den Startzeitpunkt **auf die Sekunde**, auch wenn er später im
   Web korrigiert wurde.

Neu: Präfix, ein fortlaufender Zähler im Gerätespeicher und zwei Zufallswerte,
zum Beispiel `m-42-1837704912`. Der Zähler überlebt Neustarts und Zeitsprünge
und ist die eigentliche Zusicherung — er wiederholt sich nicht, ganz gleich,
was die Uhrzeit tut. Der Zufallsanteil verhindert, dass sich Reihenfolge oder
Zeitpunkt ablesen lassen.

**Der Zeitstempel ist ganz entfallen, nicht bloß ergänzt worden.** Das Konzept
sah vor, einen Zufallsanteil anzuhängen; damit wäre Punkt 2 bestehen geblieben.
Der Startzeitpunkt steht ohnehin als `started_at` im Datensatz — dort gehört er
hin, und dort ist er korrigierbar.

**Kennungen der alten Form bleiben gültig.** Es gibt keine Umstellung: Der
Server prüft das Format nicht, die Idempotenz hängt allein an der Gleichheit der
Zeichenkette. Eine Uhr, die beim Update noch ungesendete Daten im Puffer hat,
liefert sie unverändert nach.

### Kopplung: Meldungen, die sagen, was zu tun ist

`Pair.mc` unterschied nur 200 und 404. Alles andere endete in „Kopplung
fehlgeschlagen (409)" — einer Meldung, die den Zahlencode nennt und sonst
nichts. Ausgerechnet die 409 ist aber der Fall, den man selbst beheben kann: Es
sind bereits fünf Geräte verbunden, eines muss weg. Wer stattdessen eine Zahl
liest, tippt den Code mehrmals neu ein und läuft am Ende noch in die Sperre —
die dann ebenfalls nur als Zahl erschien.

Unterschieden werden jetzt: zu viele Geräte, zu viele Versuche, ungültiger
Code und fehlende Telefonverbindung. Entschieden wird am Feld `error` der
Antwort, nicht am Zahlencode: Der Schlüssel benennt die Ursache, der Code nur
ihre Klasse.

**Zweizeilig, weil eine Zeile nicht reicht.** Die Meldungszeile wird ohne
Umbruch gezeichnet — was breiter ist als das Display, fällt weg, ohne dass man
es merkt. In der Hinweisschrift sind das rund 26 Zeichen; „Zu viele Geräte —
erst eines im Web löschen" wäre genau um den Teil gekürzt worden, der sagt, was
zu tun ist. Deshalb zwei kurze Zeilen: **was** los ist, und **was hilft**. Die
zweite Zeile erscheint nur, wenn es etwas zu tun gibt, und geht in die
Platzberechnung des Bildschirms ein — der Block darüber weicht von selbst aus.

Für unbekannte Fehler wird die Meldung des Servers als zweite Zeile
übernommen, gekürzt auf die Displaybreite. So erscheint ein künftiger Fehlerfall
nicht wieder als nackte Zahl, nur weil die Uhr ihn noch nicht kennt. Für die
bekannten Fälle stehen die Texte in der App: Die Servermeldungen sind für die
Weboberfläche geschrieben, ganze Sätze ohne Umlaute und zu lang für ein
Uhrendisplay.

### Behobene Review-Befunde

M7-03 sowie die in P4 nachgetragene 409-Behandlung. **Damit ist der Review
vollständig abgearbeitet.**

### Geprüft

Nur soweit ohne Gerät möglich: Die Erzeugung der Kennung wurde nachgebaut und
mit 6 Prüfungen belegt — Form, 5001 Kennungen ohne Dublette, Neustart nach
einem Zeitsprung auf dieselbe Sekunde (die alte Fassung erzeugt dort eine
Dublette, die neue nicht), Zählerüberlauf.

**Nicht geprüft und nur auf dem Gerät prüfbar:** Übersetzung mit dem
Connect-IQ-SDK, `Math.rand`/`Math.srand` und `Storage` im echten Lauf, die
Darstellung der zweiteiligen Meldung auf den verschiedenen Displaygrößen.

## [Uhr 1.6.6] — 2026-08-03

### Behoben — Einrichtungshinweis lief weiter über den Rand

Der Hinweis war in der unteren Zone des Startbildschirms zentriert. Dort läuft
der Kreis so weit zu, dass **keine** Schriftgröße mehr gepasst hätte — die
automatische Schriftwahl hatte keine Wahl, die hineingegangen wäre.

- Der Hinweis hängt jetzt **unter dem Hauptblock** statt in der unteren Zone.
  Dort ist die nutzbare Breite noch deutlich größer.
- Er ist **einzeilig und kurz**: „Server fehlt" bzw. „Nicht gekoppelt". Eine
  zweite Zeile säße zwangsläufig tiefer und passte auf keinem der drei Geräte
  zuverlässig hinein, auch nicht in der kleinsten Schrift. Was zu tun ist,
  steht ausführlich auf der Sync-Seite — einen Schritt nach unten.

## [Uhr 1.6.5] — 2026-08-03

### Behoben — Schriftwahl maß an der falschen Stelle

Die mit 1.6.4 eingeführte Anpassung an die Displayrundung prüfte die Breite in
der **Mitte** der Textzeile. Der Kreis läuft aber schon innerhalb einer
einzigen Zeile spürbar zu: Eine Zeile unterhalb der Displaymitte ist an ihrer
Unterkante deutlich schmaler als in ihrer Mitte. Lange Bezeichnungen wie
„Ankunft PatientIn" wurden dadurch weiterhin angeschnitten, obwohl die
Prüfung sie durchgehen ließ.

`Ui.fitFont()` bekommt jetzt Oberkante und Höhe der Zeile und misst an der
Kante, die weiter von der Displaymitte entfernt liegt — unterhalb der Mitte
also unten, oberhalb der Mitte oben. Der Sicherheitsrand wurde von 10 auf 16
Bezugspixel erhöht. Betrifft Hauptanzeige, Startbildschirm, Sync-Seite und die
Rea-Übersicht.

### Geändert — Rea-Übersicht: zweiter Trennbalken

Die Liste läuft um; hinter dem letzten Zeitstempel folgt wieder „Rea beenden".
Dort stießen Zeiten und Entscheidungen unvermittelt aneinander. Am Listenende
steht jetzt ein zweiter grauer Balken **„Aktionen"**, sodass beide Übergänge
markiert sind.

## [Uhr 1.6.4] — 2026-08-03

### Geändert — Rea-Übersicht selbst gezeichnet

Die Übersicht war das letzte Systemmenü der App und passte weder zum übrigen
Bild noch zur Venu 3s. Sie wird jetzt wie Schnellmenü und Rea-Untermenü selbst
gezeichnet: gleiche Zeilenhöhe, fünf sichtbare Zeilen, gefüllte Auswahl.

- Ist die Reanimation pausiert, stehen **Rea beenden** (rot) und **Rea
  fortsetzen** (grün) oben; ein **schmaler Trennbalken „Zeiten"** in halber
  Zeilenhöhe schneidet sie von den Zeitstempeln ab.
- Der Trennbalken ist nicht anwählbar — das Blättern überspringt ihn.
- Läuft die Reanimation normal, entfallen Entscheidungen und Trennbalken; es
  bleibt die reine Zeitenliste.

### Behoben — Texte liefen über den Displayrand

Auf einem runden Display läuft der Kreis oben und unten zu. Eine Zeile, die
in der Mitte passt, wird dort abgeschnitten — „Ankunft Einsatzort" auf der
Hauptanzeige und die Einrichtungshinweise des Startbildschirms waren betroffen,
auf Fenix 6 Pro und Venu 3s.

- `Ui.chordW()` berechnet die tatsächlich nutzbare Breite in der jeweiligen
  Höhe, `Ui.fitFont()` wählt die größte Schrift, die dort noch hineingeht.
  Angewandt auf die Phasenbezeichnung, die Hinweiszeilen beider Seiten und die
  Einträge der Rea-Übersicht.
- Die Hinweise des Startbildschirms sind kürzer gefasst: „Server-Adresse
  fehlt / in Garmin Connect" statt „Server in Garmin Connect / eintragen".

### Behoben — Hinweisschrift auf der Venu 3s zu klein

Schriftgrößen sind Gerätekonstanten und skalieren **nicht** mit der
Displayhöhe. Auf der Venu 3s war `FONT_XTINY` im Verhältnis zum Display
deutlich kleiner als auf der Fenix. `Ui.fontHint()` wählt ab 320 Pixeln
Displayhöhe eine Stufe größer; betroffen sind Startbildschirm und Sync-Seite.

## [Uhr 1.6.3] — 2026-08-03

### Geändert — Sync-Seite folgt der Farblogik

- **Warnungen in Rot** statt Gelb: „Erst Server-Adresse setzen", „Gerät
  koppeln" und der letzte Übertragungsfehler. Letzterer war bisher hellgrau
  und damit kaum als Fehler zu erkennen.
- **„REA pausiert" in Blau**, wie auf allen anderen Oberflächen.

Damit gilt durchgängig: **Rot** heißt laufende Reanimation oder Warnung,
**Blau** heißt pausiert, **Grün** heißt erledigt.

### Behoben — Kopplungsmeldungen waren teils falsch eingefärbt

Die Farbe der Kopplungsmeldung wurde aus den ersten drei Zeichen des Textes
abgeleitet. Damit galt alles außer „Gekoppelt" als Fehler — auch der
Zwischenstand „Kopple…", der noch gar nichts aussagt. Zudem hätte die Prüfung
„Kopple…" und „Kopplung fehlgeschlagen" nicht auseinanderhalten können, wenn
sie auf mehr Zeichen erweitert worden wäre.

`Pair.mc` führt jetzt neben dem Text eine Statusart (`:ok`, `:busy`,
`:error`). Die Oberfläche wählt die Farbe danach und muss den Text nicht mehr
auseinandernehmen: Grün bei Erfolg, Hellgrau während des Kopplungsversuchs,
Rot bei Fehlschlag.

## [Uhr 1.6.2] — 2026-08-03

### Geändert — Farbgebung nach Markenvorgabe

Der pausierte Zustand hat jetzt durchgängig eine eigene Farbe, und Warnungen
sind als solche erkennbar.

- **Pausierte Reanimation ist blau:** der Schriftzug „PAUSE" auf der
  Reanimationsseite, der Ring der Hauptanzeige und der Hinweis „REA pausiert"
  auf Tempo- und Statistikseite. Vorher gelb bzw. rot — Rot ist der laufenden
  Reanimation vorbehalten.
- **Fortschrittsbalken** unter dem 2:00-Countdown in Markenblau statt Orange.
- **Einrichtungshinweise auf dem Startbildschirm** („Server in Garmin Connect
  eintragen", „Nicht gekoppelt") in Rot statt Gelb. Es sind Warnungen: Ohne
  Server-Adresse kann die Uhr nichts senden.

## [Uhr 1.6.1] — 2026-08-03

Korrekturen aus dem ersten Simulatordurchlauf von 1.6.0. **1.6.0 wurde nie
verteilt** — die Trennung dient allein dazu, Bauten auseinanderhalten zu
können.

### Behoben

- **„PAUSE" erschien als fünf leere Kästchen.** Der Text wurde in
  `FONT_NUMBER_MILD` gezeichnet. Die Ziffernschriften von Connect IQ enthalten
  ausschließlich Zahlen, Doppelpunkt und Punkt — Buchstaben haben dort kein
  Zeichen. Jetzt `FONT_LARGE` und in Rot, damit der angehaltene Zustand auf
  der Reanimationsseite nicht zu übersehen ist.
- **Rahmen des Fortschrittsbalkens** auf der Reanimationsseite lag als
  einziger Wert noch absolut bei 2 Pixeln und wäre auf der Venu 3s zu dünn
  geraten. Er skaliert jetzt wie alles andere mit der Displayhöhe.
- **Sync-Seite:** Der Mittelblock wurde im ganzen Bildschirm zentriert, der
  untere Block vom Rand aus gesetzt — bei drei Meldungen überlappten sie sich.
  Jetzt wird zuerst der untere Block bestimmt und der Mittelblock im freien
  Raum darüber zentriert.

### Geändert — Feinschliff der Geometrie

Die Blockhöhen wurden mit der vollen Schriftbox gerechnet. Bei den
Ziffernschriften bleibt deren Unterlänge leer, wodurch unter jeder Zahl eine
Lücke entstand und die Blöcke zu hoch wirkten. `Ui.numH()` rechnet jetzt mit
der sichtbaren Höhe.

- **Hauptanzeige:** Uhrzeit und Datum enger, Block etwas tiefer.
- **Geschwindigkeit:** „km/h" rückt an die Zahl heran.
- **Statistik:** Die Zahl sitzt optisch mittig zwischen „Heute" und
  „Einsätze".
- **Reanimation:** Countdown und Fortschrittsbalken sitzen mittig im
  50-%-Feld, die Gesamtdauer etwas tiefer im Kopfbalken, die Uhrzeit etwas
  höher im Fußbereich. Die Trennlinie über der Uhrzeit entfällt — der
  Fortschrittsbalken trennt bereits genug.
- **Rea-Übersicht:** „Rea beenden" steht über „Rea fortsetzen", darunter
  trennt eine Zeile „— Zeiten —" die Entscheidungen von den Zeitstempeln.

### Hinweis für spätere Zielgeräte

`monkey.jungle` weist Quell- und Ressourcenpfade jetzt **vollständig** zu.
Die Schreibweise `$(<gerät>.sourcePath);…` sieht nach „Vorgabe erweitern" aus,
ist aber ein Selbstbezug und sammelt alle `source*`-Ordner ein — der Compiler
meldet dann `Redefinition of 'HAS_UP_DOWN'`. Festgehalten in `Technik.md`
Abschnitt 5.1b.

## [Uhr 1.6.0] — 2026-08-03

### Neu — Forerunner 945 und Venu 3s werden unterstützt

Die App lief bisher nur auf der Fenix 6 Pro. Dazu kommen zwei Geräte mit
anderen Voraussetzungen: die FR945 mit kleinerem Display (240×240) und die
Venu 3s mit größerem Display (390×390), Touchscreen und nur **zwei** für Apps
erreichbaren Tasten — die mittlere ist systemseitig belegt und erreicht
Connect-IQ-Apps nicht.

- **Gemeinsames Eingabemodell (`Input.mc`).** Die Langdruck-Erkennung, die
  bisher in drei Oberflächen einzeln stand, liegt jetzt an einer Stelle,
  ebenso die Tastensperre und die geräteabhängige Menü-Taste. Die Oberflächen
  beschreiben nur noch, was bei welcher *Aktion* passieren soll.
- **Bedienung der Venu 3s:** Wischen hoch und runter blättert, Wischen rechts
  wirkt wie Zurück. Der lange Druck liegt bewusst doppelt — auf der
  Action-Taste *und* auf der Zurück-Taste. Grund: Das Handbuch der Venu 3
  nennt ein Steuerungsmenü nach zwei Sekunden Halten der Action-Taste. Im
  Simulator trat es nicht auf, auf echter Hardware ist es ungeprüft. Fängt die
  Uhr den langen Action-Druck ab, bleibt die App über den langen
  Zurück-Druck vollständig bedienbar.
- **Tippen auf den Bildschirm bleibt auf den Hauptseiten wirkungslos.** In den
  Menüs kann es den markierten Eintrag auswählen — bewusst hingenommen, weil
  Tasten- und Bildschirmauswahl technisch nicht unterscheidbar sind.
- **Adrenalin und Rhythmuskontrolle** sind auf der Venu 3s nur über das
  Rea-Untermenü erreichbar; die langen UP/DOWN-Drücke gibt es dort nicht.
- **Neue App-Einstellung „Touchbedienung verwenden"** (Vorgabe: an). Sie
  greift erst bei Uhren, die Touch **und** UP/DOWN haben (Fenix 7 und neuer),
  und wird auf der Venu 3s ignoriert — ohne Touch wäre sie unbedienbar.
- **Bedienhinweise passen sich dem Gerät an:** „START halten" auf der Fenix,
  „Action halten" auf der Venu; „DOWN drücken" wird zu „nach unten wischen".
- Neues Dokument `docs/Geraete-Eingabe.md` mit dem gemessenen Eingabeverhalten
  je Uhr, neues Werkzeug `tools/eingabe-probe` zum Ausmessen weiterer Geräte.

### Geändert — Alle Oberflächen neu ausgemessen

Die Maße waren fest auf 260×260 verdrahtet. Sie werden jetzt relativ zur
Displayhöhe gerechnet und ergeben bei 260 **exakt** die bisherigen Pixelwerte;
auf der Fenix 6 Pro ändert sich dadurch nichts.

- **Startbildschirm:** Bildmarke (70×70, auf der Venu 105×105) über dem Titel.
  „Einsatzdoku" im Markenorange, „START drücken" kleiner und im Markenblau,
  enger an „Dienst beginnen?" gerückt. Der Block sitzt vertikal zentriert in
  den oberen 75 % der Höhe; die Einrichtungshinweise haben die unteren 25 %
  für sich und lassen ihn nicht mehr springen.
- **Hauptanzeige:** alles vertikal zentriert, größerer Abstand zwischen Datum
  und Phasennummer.
- **Geschwindigkeit:** „km/h" rückt an die Zahl heran, der Absatz zur Distanz
  bleibt; alles vertikal zentriert.
- **Statistik:** vertikal zentriert.
- **Sync:** Die Überschrift „Sync" entfällt. Die GPS-Güte steht jetzt über der
  Hauptaussage, diese sitzt vertikal in der Mitte. Fehlergrund,
  Kopplungsmeldung, Einrichtungshinweis und Version bilden unten einen Block
  mit gleichbleibendem Abstand zum Rand.
- **Reanimationsseite:** oberes und unteres Feld je 25 % der Displayhöhe, das
  mittlere 50 %; jedes Feld trägt seinen Inhalt vertikal zentriert.

### Geändert — Reanimation beenden ist jetzt zweistufig

Bisher fragte „Rea BEENDEN" einmal nach und schloss die Sitzung. Wer sich
vertippte, hatte die Dokumentation zu.

- **„Rea BEENDEN" hält die Reanimation an** und öffnet die Übersicht. Dort
  stehen ganz oben **Rea fortsetzen** und **Rea beenden** — die Entscheidung
  fällt also mit den dokumentierten Zeiten vor Augen. Der
  Bestätigungsdialog entfällt dafür.
- Ohne Entscheidung bleibt die Reanimation **pausiert**; die Zurück-Taste
  schließt nur die Liste. Der Pausenzustand übersteht einen Neustart der App.
- Während der Pause steht der 2:00-Countdown. **Die Gesamtdauer läuft
  weiter** — sie ist die tatsächlich verstrichene Reanimationszeit und darf
  nicht zu kurz dokumentiert werden.
- Uhr-, Tempo-, Statistik- und Sync-Seite zeigen „REA pausiert" statt „REA
  läuft"; der rote Ring der Hauptanzeige wird gelb.
- Wird der Einsatz abgeschlossen oder der Dienst beendet, während eine Rea
  pausiert ist, wird sie automatisch geschlossen — wie bisher eine laufende.
- **Im Rea-Untermenü** steht „Übersicht" jetzt hinter „Rea BEENDEN" und damit
  einen Schritt nach oben vom Öffnungspunkt. „Übersicht" ist im Markenblau
  gehalten, damit sie sich an der Umlaufgrenze von „Timer neu starten"
  unterscheidet.

Am Datenmodell, am JSON-Vertrag und am Server ändert sich nichts. Die Pause
ist ein reiner Bedienzustand und wird nicht übertragen.

## [Uhr 1.5.0] — 2026-08-02

### Geändert — Reanimations-Bedienung

Diese Umbauten standen bereits im Eintrag „Uhr 1.4.0", wurden dort aber **nicht
ausgeliefert**: Der Code blieb auf dem alten Stand, nur der Changelog-Text lief
voraus. Der Eintrag 1.4.0 ist entsprechend richtiggestellt; ausgeliefert wird
das Beschriebene jetzt mit 1.5.0.

- **Kurz START öffnet bei laufender Reanimation das Untermenü.** Bisher setzte
  der kurze Druck den 2:00-Countdown neu an. Der häufigste Griff unter
  Reanimationsbedingungen ist aber das Dokumentieren eines Ereignisses, nicht
  der Timer — der kürzeste Weg gehört deshalb dorthin. Läuft **keine**
  Reanimation, beginnt kurz START sie weiterhin.
- **Lang START startet den Countdown neu** (bisher öffnete der lange Druck das
  Untermenü). Läuft keine Reanimation, ist der lange Druck **ohne Funktion** —
  er startet insbesondere keine Reanimation, damit ein zu langes Drücken beim
  Beginnen nicht unbemerkt ins Leere läuft und auch nichts Falsches auslöst.
- **Neuer erster Menüpunkt „Timer neu starten"** (weiß). Er setzt den
  Countdown neu an, ohne einen Zeitstempel zu schreiben. Weiß, weil er kein
  dokumentiertes Ereignis ist — die Farben bleiben den Ereignissen vorbehalten.
- **Defibrillation setzt den Countdown neu an.** Wie die Rhythmuskontrolle
  markiert sie damit den Beginn eines neuen Zyklus. Bisher schrieb sie nur
  einen Zeitstempel: Die dafür vorgesehene Funktion `Cpr.markDefi()` existierte
  zwar, wurde aber von keiner Stelle aufgerufen — die im Eintrag 1.4.0
  angekündigte Kopplung war also nie wirksam.
- **Rea-Untermenü im Design des Schnellmenüs:** gleiche Zeilenhöhe und
  Darstellung wie auf der Hauptanzeige (fünf sichtbare Zeilen statt vier,
  gefüllte Auswahl). Die Ereignisfarben bleiben erhalten, die
  Gruppen-Trennlinien entfallen. Ein einheitliches Menübild spart im Einsatz
  Umdenken. Sehr lange Beschriftungen fallen in der Auswahlzeile eine
  Schriftstufe zurück, statt am Feldrand abgeschnitten zu werden.

Das Untermenü hat damit zwölf statt elf Einträge. An Datenmodell, JSON-Vertrag
und Server ändert sich nichts; die Defibrillation wird unverändert als
Ereignis `defibrillation` übertragen.

## [Web 3.1.1] — 2026-07-29

### Geändert
- **Suchseite: Filter in der linken Spalte.** Die rund 30 Filter standen bisher
  in einem einzigen Aufklappbereich über der Trefferliste. Sie sitzen jetzt in
  der linken Spalte, aufgeteilt in vier einzeln aufklappbare Blöcke (Zeit, Art
  des Einsatzes, Beteiligte und Ziel, Werte), die beim Öffnen der Seite alle
  zugeklappt sind. Öffnet man einen geteilten Link, gehen genau die Blöcke auf,
  in denen ein Filter gesetzt ist. Das Freitextfeld bleibt oben in der
  Hauptspalte, „Filter zurücksetzen" wandert an den Fuß der Filterspalte.
- Die Einsatztage-Leiste entfällt auf der Suchseite. Einzelne Flugtage sind bei
  einer Suche über den Gesamtbestand ohne Nutzen; die Fläche wird für die
  Filter gebraucht. Alle übrigen Seiten behalten sie unverändert.

## [Web 3.1.0] — 2026-07-29

### Neu
- **Suche über den gesamten Einsatzbestand** (neuer Menüpunkt „Suche"). Ein
  Freitextfeld durchsucht Einsatznummer, Name, Geburtsdatum, Diagnose,
  Einsatzort, Transportziel, Beschreibung, Bergwacht-Angaben, anderen Notarzt,
  weitere Rettungsmittel, Standort, Maschine, Besatzung und Notizen; mehrere
  Wörter werden UND-verknüpft, dürfen aber in verschiedenen Feldern stehen.
  Dazu rund 30 weitere Filter (Zeitraum, Alarmzeit auch über Mitternacht,
  Wochentag, Winde samt Cycles und Luftverladung, Bergwacht, Sekundärtransport,
  Schockraum, Reanimation und Ereignisarten, Herkunft, Standort, Maschine,
  Besatzung je Rolle, Rettungsmittel, Transportziel, Alter, Flugstrecke,
  Einsatzdauer, Höhe des Einsatzorts).
- Der Filterzustand steht vollständig im URL-Fragment. Die Adresse lässt sich
  als Lesezeichen speichern oder weitergeben und stellt dieselbe Suche wieder
  her. Fragmente werden nicht an den Server gesendet — der Suchbegriff taucht
  damit in keinem Zugriffsprotokoll auf.
- Die Suche läuft vollständig im Browser: Der Bestand wird einmal je Sitzung
  geladen (neuer Endpunkt `api/suchindex.php`), danach kostet kein Tastendruck
  eine Serveranfrage. Anders ginge es nicht — die geschützten Angaben liegen
  Ende-zu-Ende-verschlüsselt, der Server kann nicht in ihnen suchen.

### Geändert
- Trefferliste und Zeitraum-Übersicht teilen sich jetzt einen gemeinsamen
  Baustein (`assets/missiontable.js`): gleiche Spalten, gleiche Sortierung,
  gleicher Zeilenaufbau. Die Zeitraum-Übersicht verhält sich unverändert,
  inklusive der Kopplung zwischen Extremwert-Kacheln, Karten-Pin und
  Tabellenzeile.
- Die Kopfleiste enthält zwischen „Übersicht" und dem Zahnrad den neuen
  Menüpunkt „Suche".

### Hinweis zur Herkunft
Der Filter „Herkunft" wertet die Spalte `origin` aus (von der Uhr / von Hand /
importiert), nicht `manual`. `manual` bedeutet seit Web 2.11.0 ausschließlich
„die Uhr überschreibt diesen Einsatz nicht mehr" und sagt nichts darüber aus,
wie er entstanden ist.

## [Web 3.0.0] — 2026-07-29

Haupt-Sprung, weil der Umgang mit dem Inhaltsschlüssel selbst umgebaut wurde:
Er lässt sich ab sofort mitten in der Sitzung wiederherstellen, statt nur beim
Anmelden zu entstehen.

### Neu
- **Geschützte Angaben entsperren, ohne sich neu anzumelden.** Sind die
  Ende-zu-Ende-verschlüsselten Angaben in der Sitzung gesperrt — weil ein Link
  in einem neuen Tab geöffnet, der Browser neu gestartet oder das Passwort ohne
  Wiederherstellungsschlüssel zurückgesetzt wurde —, fragt jetzt ein Dialog
  direkt auf der Seite nach dem Kontopasswort und gibt den Inhaltsschlüssel
  wieder frei. Das Ab- und Neuanmelden entfällt. Das Passwort wird
  ausschließlich im Browser verwendet und verlässt ihn zu keinem Zeitpunkt.
  Betroffen sind Tagesübersicht, Einsatzansicht, Einsatzformular,
  Zeitraumübersicht, Import, Export und das Backup in den Einstellungen.
- Jeder Sperrhinweis trägt einen Knopf **„Entsperren"**. Damit lässt sich der
  Dialog nach einem Abbruch jederzeit erneut öffnen.

### Geändert
- Die Sperrhinweise auf allen betroffenen Seiten verweisen nicht mehr auf
  „bitte ab- und neu anmelden", sondern auf das Entsperren an Ort und Stelle.
- **Einsatzformular:** Nach erfolgreichem Entsperren werden die zuvor
  gesperrten Eingabefelder wieder freigegeben und vorhandene verschlüsselte
  Angaben nachgeladen — ohne die Seite neu zu laden.

### Behoben
- **Tagesübersicht:** Bei gesperrtem Schlüssel erschien kein Hinweis, warum
  Einsatzort, Alter und Diagnose leer blieben. Das Skript sprach ein Element
  `#lockbanner` an, das es im Seitenaufbau von `index.php` gar nicht gab —
  die Anzeige scheiterte still. Der Hinweis ist jetzt vorhanden.

## [Web 2.11.0] — 2026-07-29

> **Nachtrag (Web 3.2.0):** Zu dieser Auslieferung gehörte ein zweiter Teil
> („Export-Fehlerbehebung") mit Änderungen an `assets/export.js`,
> `assets/confirm.js`, `assets/import_profiles.js` und `import.php`. Nur die
> letzten drei Dateien sind im Repository angekommen; `export.js` wurde vom
> Folgecommit mit einem älteren Arbeitsstand überschrieben, ebenso dieser
> Changelog-Abschnitt. Die Export-Seite war dadurch ab Web 2.11.0 nicht mehr
> bedienbar. Nachgeholt mit Web 3.2.0 — siehe dort.

### Neu
- **Zeitraum-Übersicht:** Die drei Extremwert-Kacheln „Längste Flugstrecke",
  „Längste Einsatzdauer" und „Höchster Einsatzort" sind jetzt interaktiv.
  Hovern hebt den zugehörigen Karten-Pin (rot) und die Tabellenzeile (rosa)
  hervor; ein Klick fixiert die Hervorhebung und springt zur Tabellenzeile.
  Ein zweiter Klick auf dieselbe Kachel oder ein Klick auf freie Fläche löst
  die Fixierung wieder.
- **Einsatzansicht:** Kopfzeile zeigt jetzt ein Herkunftskennzeichen (Uhr /
  manuell / importiert) und — falls zutreffend — zusätzlich „editiert".

### Geändert
- **Zeitraum-Übersicht:** Kachelsatz auf zehn Kacheln (2×5) umgestellt:
  „Windeneinsätze" entfällt, „Anzahl Winden-Cycles" ist neu; „Einsätze" und
  „Flugtage" sind jetzt eigene Kacheln. Die bisherige Textzusammenfassung
  über der Karte entfällt ersatzlos.
- **Einsatztage-Leiste:** Trefferfläche des Aufklapp-Dreiecks in Jahres- und
  Monatszeile vergrößert (mind. 28 × 28 px) — mit dem Finger jetzt zuverlässig
  zu treffen.
- **Einsatzansicht — Kopfzeile:** Zeitangaben durch einen Halbgeviertstrich
  getrennt (statt Bindestrich ohne Abstand); die Kilometerangabe trägt jetzt
  die Beschriftung „Flugkilometer".
- **Datenmodell:** `missions.manual` bedeutet ab sofort ausschließlich „Uhr
  überschreibt Metadaten/Phasen/Rea nicht mehr". Herkunft (`origin`:
  Uhr/manuell/import) und Bearbeitungsstatus (`edited`) sind neue, eigene
  Spalten. Migration und Bestandsdaten-Backfill sind automatisch.
- **Backup-Format:** Version auf 4 angehoben (zwei neue Felder `origin` und
  `edited` je Einsatz). Backups der Version 3 lassen sich weiterhin
  einspielen; die Werte werden dabei abgeleitet.

### Behoben
- **Einsatzansicht:** Das Kennzeichen „manuell" erschien fälschlich auch nach
  jeder nachträglichen Bearbeitung eines von der Uhr aufgezeichneten
  Einsatzes. Ursache: Die Spalte `missions.manual` trug zwei Bedeutungen
  gleichzeitig (Herkunft und Schutz vor Uhr-Überschreiben). Behoben durch
  Auftrennung in `manual`, `origin` und `edited` (siehe Datenmodell oben).
- **Wartung:** In der Bootstrap-Liste der Migrationen (`schema.sql`) fehlte
  die ID `2026_07_28_kdf_ver_entfernt`. Betraf ausschließlich frische
  Neuinstallationen (überflüssige, aber folgenlose Prüfung beim ersten
  Aufruf von `update.php`); bestehende Installationen waren nicht betroffen.

## [Web 2.10.0] — 2026-07-28

### Neu — Export (Excel · vollständiges CSV · GuteSeele) und Rückimport
- Auf der Seite **Import / Export** gibt es unterhalb des Importbereichs einen
  Exportblock. Der Aufbau der Datei passiert vollständig im Browser: Der Server
  liefert nur Rohdaten, die geschützten Angaben werden lokal entschlüsselt.
  Ohne Haken „Patientendaten einschließen" sendet der Server den `pat_blob`
  gar nicht erst mit.
- **Profil A — Standard-Excel** (`.xlsx`, ein Blatt „Einsätze"): eine Zeile je
  Einsatz, alle Zeiten in Ortszeit, leere Werte als `-`. Ein Flugtag ohne
  Einsatz erscheint als eine Zeile mit Hubschrauber, Standort und Datum. Ohne
  Patientendaten entfallen die sechs geschützten Spalten ersatzlos.
- **Profil B — vollständiges CSV** (`.zip`): `einsaetze.csv`, `flugtage.csv`,
  `ruhezeiten.csv`, `felder.csv`, `LIESMICH.txt` und auf Wunsch GPX-Tracks.
  Verlustfrei und Grundlage des Rückimports. Semikolon, UTF-8 mit BOM, CRLF,
  Zeitstempel nach ISO 8601 mit Zonenversatz. Der Spaltensatz ist **immer
  gleich**: Ohne Patientendaten bleiben die `pat_`-Spalten vorhanden und leer,
  damit ein einlesendes Programm nicht zwei Fälle unterscheiden muss.
- **Profil C — GuteSeele-Layout** (`.xlsx`): erhält das bisherige Listenlayout
  für die Weitergabe an Dritte; bei mehreren Kalenderjahren ein Blatt je Jahr.
- **Passwortschutz** (optional, alle Profile): AES-256 nach WinZip-Standard über
  die neu mitgelieferte Bibliothek zip.js 2.8.34. ZipCrypto wird nicht
  verwendet. Zum Öffnen wird 7-Zip (Windows) oder Keka/The Unarchiver (macOS)
  gebraucht — der Windows-Explorer kann solche Archive nicht öffnen. Das
  Passwort wird nirgends gespeichert und nicht an den Server gesendet.
- **Rückimport** über zwei neue Formate: `export_csv_v1` liest `einsaetze.csv`
  (auch direkt aus dem `.zip`, bei Bedarf nach Passwortabfrage) und übernimmt
  Phasen, Koordinaten, Reanimationsdokumentation und alle Einsatzfelder.
  `export_excel_v1` liest den Standard-Excel-Export und zeigt vorher an,
  welche Felder danach leer bleiben.
- Neuer, ausschließlich lesender Endpunkt `api/export_data.php`.
- Neu: `docs/Export-Format.md` mit der vollständigen Feldliste je Profil.

### Geändert
- `api/import_commit.php` schreibt jetzt **alle** Phasen 2–9 samt Koordinaten
  sowie die Reanimationsdokumentation, nicht mehr nur Phase 2. Formate, die
  diese Angaben nicht liefern, verhalten sich unverändert: Es wird weiterhin
  nur Phase 2 angelegt, und eine vorhandene Reanimationsdokumentation bleibt
  unangetastet.
- Die Prüftabelle in Schritt 2 zeigt die Spalten, die das erkannte Format
  vorgibt — das vollständige CSV hat 75 Spalten und wäre sonst unlesbar.

### Bekannte Eigenheit
- Beim Rundlauf über das CSV wird eine abweichende Besatzung nach dem
  Rückimport **ausdrücklich** in allen Rollen gespeichert, während vorher
  einzelne Rollen vom Flugtag geerbt wurden. Der Export schreibt die
  *effektive* Besatzung; die erneut exportierte Datei ist deshalb identisch,
  nur die Speicherung ist expliziter.

## [Web 2.9.0] — 2026-07-28

### Geändert — Einsatznummer verschlüsselt
- Die Einsatznummer (Leitstellen-Nummer) ist ein Fallbezeichner, über den sich
  bei der Leitstelle die betroffene Person ermitteln lässt — sie gehört damit
  zu den geschützten Angaben. Sie liegt jetzt Ende-zu-Ende-verschlüsselt im
  `pat_blob` statt im Klartext in `missions.mission_no`; die Spalte entfällt.
- Migration `2026_07_29_einsatznummer_verschluesselt` entfernt die Spalte
  `missions.mission_no` ersatzlos. Vom Betreiber bestätigt: In der
  Produktivinstanz war zum Zeitpunkt der Migration keine einzige
  Einsatznummer belegt, eine Übernahme ist deshalb nicht nötig — der Server
  könnte bestehende Klartextwerte mangels Schlüssel ohnehin nicht selbst in
  den `pat_blob` überführen. Aus demselben Grund gilt: Backups, die vor
  Web 2.9.0 erstellt wurden, enthalten die Einsatznummer noch als Klartextfeld
  auf Einsatzebene statt im `pat_blob` — Backups zählen ab dieser Version neu,
  ältere werden nicht mehr unterstützt.
- Das Formularfeld ist ins Feld für PatientInnendaten gewandert (jetzt an
  erster Stelle, oberhalb von Nachname) und wird nur noch clientseitig
  gespeichert.
- **Import bestehender Einsatzlisten:** Der Abgleich mit dem Bestand
  (`api/import_commit.php`, `action=check`) bekommt seit dieser Version nur
  noch Datum und Uhrzeit zu sehen, nicht mehr die Einsatznummer. Für den
  Nummernabgleich liefert `check` stattdessen die `pat_blob`s vorhandener
  Einsätze mit; der Browser entschlüsselt sie lokal. Dadurch werden
  Nummerndubletten **nur noch innerhalb der Flugtage erkannt, die in der
  Importdatei vorkommen** — der Preis der Verschlüsselung. Tag und Alarmzeit
  bleiben als zweites, uneingeschränktes Merkmal wirksam.
- `docs/Backup-Format.md`, `docs/Technik.md` und `docs/Handbuch.md`
  entsprechend nachgezogen.

## [Web 2.8.0] — 2026-07-27

### Neu — Import bestehender Einsatzlisten (Excel/CSV)

Neuer Eintrag **Einstellungen → Import / Export**: Eine vorhandene
Einsatzliste — etwa eine über Jahre gepflegte Excel-Jahresliste — lässt sich
in einem Durchgang übernehmen. Bedienung: `docs/Handbuch.md`, Abschnitt 7.

- **Die Datei wird nicht hochgeladen.** Lesen, Prüfen und Verschlüsseln
  passieren vollständig im Browser; der Server erhält Name, Geburtsdatum,
  Diagnose und Einsatzort ausschließlich als Chiffretext. Das ist keine
  Bequemlichkeit, sondern die einzige mit der Ende-zu-Ende-Verschlüsselung
  vereinbare Bauweise. Ist die Verschlüsselung gesperrt, bleibt der Import
  gesperrt — unverschlüsselt wird nichts gesendet.
- **Formate sind deklarativ beschrieben** (`assets/import_profiles.js`):
  Blatt, Kopfzeile, erwartete Überschriften und je Spalte das Zielfeld samt
  Parserkette. Ein weiteres Dateiformat heißt künftig, dort einen Eintrag zu
  ergänzen — an der Verarbeitung ändert sich nichts. Mitgeliefert ist das
  Profil „Einsatzdoku Christoph 17 (Jahresliste)".
- **Review-Tabelle mit Korrektur:** Jede Zeile wird geprüft und nach Flugtag
  gruppiert angezeigt, Hinweise gelb, Fehler rot, jede Zelle direkt änderbar
  mit sofortiger Neuprüfung. Fehlerhafte Zeilen blockieren nur sich selbst und
  lassen sich einzeln überspringen.
- **Dubletten** werden über die Einsatznummer, ersatzweise über Tag und
  Alarmzeit erkannt; je Zeile wählbar zwischen überspringen, überschreiben
  und trotzdem anlegen. Der Abgleich mit dem Bestand kommt mit Datum, Uhrzeit
  und Einsatznummer aus — Patientendaten sind dafür nicht nötig und werden
  auch nicht gesendet.
- **Pilotenwechsel im laufenden Dienst** wird automatisch abgebildet: Als
  Besatzung des Flugtags gilt die des ersten Einsatzes; abweichende spätere
  Zeilen erhalten eine abweichende Besatzung am Einsatz (aus Web 2.6.0).
- **Alles oder nichts:** Die Übernahme läuft in einer einzigen Transaktion.
  Bricht sie ab, bleibt kein halb eingespielter Jahresbestand zurück.
- Neu vendoriert: `assets/vendor/xlsx.full.min.js` — SheetJS Community
  Edition 0.18.5, Apache-2.0, lokal im Repo statt von einem fremden Server.
  Ein CDN-Aufruf würde verraten, wann jemand Einsatzdaten verarbeitet.

### Behoben — Excel-Uhrzeiten wären um 53 Minuten verschoben gewesen

- **Root Cause:** Excel speichert Uhrzeiten als Bruchteil eines Tages, gezählt
  ab 1899. Lässt man die übliche Bibliotheksfunktion daraus ein
  JavaScript-Datum bauen, rechnet der Browser die *damalige* Zonenzeit ein —
  für Mitteleuropa 53 Minuten. Aus einer Alarmzeit 10:41 wäre lautlos 09:48
  geworden, in jeder importierten Zeile. Die Rohzahl wird deshalb selbst
  zerlegt, ohne jeden Zeitzonenbezug.
- Beim Zerlegen mehrfacher Rettungsmittel wird nur an Komma und Semikolon
  getrennt, nicht am Schrägstrich — sonst zerfiele der Funkrufname „KE 71/1"
  in zwei Einträge.

### Behoben — Unmögliche Uhrzeiten wurden zum stillen Datumssprung

- **Root Cause:** `local_to_utc()` prüfte die Uhrzeit nur gegen das Muster
  `\d{2}:\d{2}`. Eine Eingabe wie „25:00" passt darauf, und die
  Datumsrechnung machte daraus klaglos den nächsten Tag 00:00 — der Einsatz
  wäre stillschweigend einen Tag verrutscht statt als Fehler aufzufallen.
  Jetzt wird zusätzlich der Wertebereich geprüft. Betrifft neben dem Import
  auch das Einsatzformular, das den Fall bereits sauber meldet („Ungültige
  Uhrzeit in den Phasen") — die Meldung kam bisher nur nie.

### Geändert — Intern

- `local_to_utc()` ist von `einsatz_form.php` nach `db.php` gewandert und
  steht jetzt neben seinem Gegenstück `fmt_local()`. Mit dem Import gibt es
  einen zweiten Aufrufer; zwei Kopien derselben Zeitrechnung wären die
  sicherste Art, sich später eine Stunde Versatz einzuhandeln.
- Importierte Einsätze hängen am selben virtuellen Gerät `manual-<userId>`
  wie von Hand nachgetragene (`final=1`, `manual=1`) — die Uhr überschreibt
  sie dadurch nie, und in der Geräteliste tauchen sie nicht auf.
- Jeder importierte Einsatz erhält eine Phasenzeile (Phase 2, Alarmierung).
  Ohne sie ließe er sich nicht im Einsatzformular öffnen, weil das Formular
  Beginn und Ende aus den Phasen rekonstruiert.
- `docs/JSON-Vertrag.md` grenzt seinen Geltungsbereich jetzt ausdrücklich auf
  die Strecke Uhr → Server ab; die Browser-Endpunkte unter `server/api/`
  stehen in `docs/Technik.md`, Abschnitt 4.
- Handbuch: neuer Abschnitt 7, die folgenden Abschnitte sind auf 8–12
  gerückt. Ein Querverweis auf die Verschlüsselung zeigte bislang auf das
  Backup-Kapitel und ist berichtigt.

## [Web 2.7.1] — 2026-07-27

### Verbessert — Abweichende Besatzung zeigt nur die Rollen der Maschine
- Der Haken „Abweichende Besatzung" öffnete bisher immer alle fünf Rollen.
  Jetzt erscheinen nur die, die der **Hubschrauber des Flugtags** laut
  Stammdaten überhaupt vorsieht — bei einer Maschine mit Pilot 1 und HEMS-TC
  also auch nur diese beiden. Dieselbe Regel gilt im Flugtag-Formular schon
  länger; beide folgen jetzt denselben Häkchen am Hubschrauber.
- **Kein Datenverlust dabei:** Eine nicht vorgesehene Rolle wird trotzdem
  eingeblendet, sobald bereits ein Wert darin steht. Sonst käme man an einen
  Eintrag nicht mehr heran, wenn der Flugtag später auf eine andere Maschine
  umgestellt wird. Ist am Flugtag noch kein Hubschrauber hinterlegt, werden
  wie bisher alle fünf Rollen gezeigt — der Haken wäre sonst funktionslos.

### Verbessert — Abweichungen sind farblich erkennbar
- Die Markierung **„(abw.)"** im Block „Besatzung" steht jetzt in Max Blau
  und halbfett statt in Grau. Verwendet wird eine um eine Stufe abgedunkelte
  Variante (`--blau-dark`): Reines Max Blau erreicht auf dem hellen
  Hintergrund ein Kontrastverhältnis von 3,8:1 und liegt damit unter der
  Schwelle von 4,5:1 für kleine Schrift — die dunklere Stufe erreicht 4,6:1
  und bleibt bei Sonnenlicht lesbar.

## [Web 2.7.0] — 2026-07-27

### Behoben — Neu angelegte Zugänge konnten kein Passwort setzen
- **Root Cause:** Der Link aus der Einladungsmail führte auf `reset_confirm.php`.
  Diese Seite kannte nur den Fall „bestehendes Konto, Passwort vergessen" und
  verlangte deshalb bedingungslos den Wiederherstellungsschlüssel. Ein frisch
  angelegtes Konto hat noch keinen — das Formular brach ab, bevor überhaupt
  etwas abgesendet wurde. Neue NutzerInnen konnten sich dadurch **nie** anmelden.
- Passwortvergabe und Passwort-Reset liegen jetzt gemeinsam in der neuen Datei
  **`pw_handling.php`**. Der Server bestimmt die Betriebsart allein aus dem
  Kontostand, nie aus dem, was der Browser mitschickt:
  - **Erstvergabe** (noch kein Inhaltsschlüssel): nur Passwortfelder. Der
    Browser erzeugt Inhalts- und Wiederherstellungsschlüssel, zeigt letzteren
    **einmalig** an und lässt ihn per Haken bestätigen; die Passwortfelder
    werden dabei schreibgeschützt, damit die bereits berechnete Hülle zum
    Passwort passt. Erst danach werden Passwort-Hash, Salz und **beide** Hüllen
    gemeinsam in einer Transaktion gespeichert.
  - **Reset** (Inhaltsschlüssel vorhanden): verlangt wie bisher den
    Wiederherstellungsschlüssel; `pat_wrap_rc` bleibt unberührt, der bekannte
    Schlüssel gilt also weiter.
- `einrichtung.php` und `reset_confirm.php` sind **entfallen**. Die früher in
  `auth_guard.php` erzwungene Ersteinrichtung nach dem ersten Anmelden entfällt
  ersatzlos: Ein anmeldbares Konto ohne Hüllen kann es nicht mehr geben.

### Behoben — Der Installer legte einen Administrator an, der sich nicht anmelden konnte
- **Root Cause:** `install.php` speicherte den Hash des **Klartext-Passworts**,
  während `login.php` seit der Umstellung auf Browser-Schlüsselableitung
  ausschließlich gegen das abgeleitete Auth-Token prüft. Beides konnte nie
  zusammenpassen — eine Neuinstallation war ohne Umweg über „Passwort
  vergessen" nicht benutzbar.
- Der Installer fragt jetzt **kein** Passwort mehr ab. Er legt den Zugang ohne
  Passwort an und zeigt auf der Erfolgsseite einen 24 h gültigen Einmal-Link
  auf `pw_handling.php`. Das Passwort verlässt damit auch bei der Installation
  nie den Browser.

### Behoben — Passwortwechsel konnte die geschützten Angaben unlesbar machen
- **Root Cause:** In `einstellungen.php` wurden Passwort-Hash und Schlüssel-Hülle
  in zwei getrennten Anweisungen geschrieben, und die Hülle nur „falls
  vorhanden". Schlug das Umpacken im Browser fehl, fing ein leeres `catch` das
  ab und das Formular wurde trotzdem abgeschickt: Das neue Passwort galt, die
  Hülle hing noch am alten — die geschützten Angaben waren nicht mehr lesbar.
- Der Wechsel läuft jetzt **atomar**: Lässt sich der Inhaltsschlüssel nicht
  umpacken, bricht der Browser ab und der Server ändert nichts. Beide
  Schreibvorgänge liegen in einer Transaktion.

### Behoben — Löschen über die Nutzer-Detailseite funktionierte nie
- **Root Cause:** In `admin_user.php` verglich die Sicherheitsabfrage die
  eingetippte E-Mail-Adresse mit `$u['email']`, obwohl `$u` erst **nach** der
  POST-Verarbeitung geladen wurde. Der Vergleich lief immer gegen einen leeren
  String, die Meldung „stimmt nicht überein" erschien auch bei korrekter
  Eingabe. Der Datensatz wird jetzt vor der Verarbeitung geladen und danach für
  die Anzeige aufgefrischt. (Der Löschen-Knopf in der Liste war nicht betroffen.)

### Behoben — Schlüssel blieben nach dem Abmelden im Browser
- `logout.php` beendete nur die PHP-Sitzung; Daten- und Inhaltsschlüssel
  blieben im `sessionStorage` liegen, weil die Weiterleitung per HTTP-Header
  geschah und damit nie JavaScript lief. Die vorhandene Funktion
  `EdCrypto.clearSession()` wurde nirgends aufgerufen.
- Abmelden räumt die Schlüssel jetzt ab. Zusätzlich verwerfen `login.php` und
  der Passwortwechsel Reste einer früheren Sitzung, bevor sie neue Schlüssel
  setzen — wichtig beim Kontowechsel im selben Tab.

### Geändert — E-Mail-Texte und Anmeldeseite
- Einladungs- und Reset-Mail (`admin_users.php`, `reset_request.php`) sind
  ausführlicher und nennen jetzt „Gen-EM Einsatzdokumentation Luftrettung" als
  Absender sowie `philipp@gen-em.org` als Kontakt bei Fragen/Problemen.
- `login.php`: Link zu „Passwort vergessen oder erstmalig setzen" heißt jetzt
  schlicht „Passwort vergessen?" — beide Fälle laufen ohnehin über denselben
  Weg (`reset_request.php` → `pw_handling.php`).

### Entfernt
- Spalte `users.kdf_ver` (Migration `2026_07_28_kdf_ver_entfernt`). Sie wurde an
  drei Stellen geschrieben, aber nirgends gelesen — seit dem Wegfall des
  Klartext-Logins in Web 2.1.0 gibt es nur noch einen Anmeldeweg.
- Toter Übernahme-Zweig in `backup_lib.php`: Bis Backup-Formatversion 1 enthielt
  die Datei die Schlüssel-Hüllen des Ursprungskontos, die beim Restore
  übernommen wurden. Seit Version 2 liegen die geschützten Angaben im (selbst
  verschlüsselten) Container als Klartext und werden vom Browser mit dem
  Schlüssel des **Zielkontos** verschlüsselt. Der Zweig konnte nur noch fremde
  Hüllen in ein Konto schreiben.

## [Web 2.6.0] — 2026-07-27

### Neu — Abweichende Besatzung je Einsatz
- Ein einzelner Einsatz kann jetzt von der Besatzung des Flugtags abweichen —
  gedacht für den Fall, dass während des Dienstes jemand wechselt (typisch:
  Pilotenwechsel am Nachmittag). Im Einsatzformular öffnet der Haken
  **„Abweichende Besatzung"** fünf Auswahlfelder (Pilot 1, Pilot 2, HEMS-TC,
  Flugretter, Sonstige), gefüllt aus den persönlichen **und** den zentralen
  Besatzungs-Vorbelegungen.
- Es müssen nur die tatsächlich abweichenden Rollen gefüllt werden; alle
  übrigen erbt der Einsatz weiterhin vom Flugtag. Bewusst redundanzfrei: Ohne
  Abweichung bleiben die neuen Spalten leer, es entsteht keine Kopie der
  Tagescrew am Einsatz. Haken entfernen leert die Felder wieder, der Einsatz
  erbt dann vollständig.
- Die Einsatzansicht zeigt dafür den neuen Block **„Besatzung"** mit dem
  Ergebnis beider Ebenen; abweichende Rollen sind mit „(abw.)" markiert,
  unbelegte Rollen entfallen.
- Neue Spalten `missions.crew_override` und `missions.crew_p1`…`crew_other`
  (Migration `2026_07_27_crew_override`).
- Die Uhr-App ist davon nicht betroffen — sie kennt keine Besatzung.

### Behoben — Zentrale Maschine oder Basis ging beim Speichern des Flugtags verloren
- Root Cause gefunden: Seit den zentralen Stammdaten (Web 2.4.x) baut
  `index.php` die Flugtag-Dropdowns aus persönlichen **und** zentralen
  Einträgen (`user_id IS NULL`), die Prüfung beim Speichern in `api/day.php`
  akzeptierte aber weiterhin nur persönliche. Eine ausgewählte zentrale
  Maschine oder Basis wurde dadurch stillschweigend auf „–" zurückgesetzt —
  ohne Fehlermeldung. Die Prüfung folgt jetzt derselben Regel wie die Liste,
  aus der ausgewählt wird.

### Behoben — Ausgeschiedene Personen und Bereitschaften gingen still verloren
- Stand in einem Auswahlfeld mit Stammdaten-Herkunft (Bergwacht-Bereitschaft,
  ab sofort auch Besatzung) ein Wert, der inzwischen aus den Stammdaten
  entfernt worden war, blieb das Feld beim Öffnen des Formulars unmarkiert —
  beim nächsten Speichern war der Wert weg. Ein solcher Altwert wird jetzt
  der Liste vorangestellt und bleibt erhalten.

### Behoben — Fehlende Migrations-ID in `schema.sql`
- Die ID `2026_07_26_zentrale_stammdaten` fehlte in der `skipped`-Liste am
  Ende von `schema.sql`. Folgenlos, weil die Sprungprüfung der Migration
  ohnehin griff, aber ein Verstoß gegen die dort dokumentierte Regel —
  nachgetragen. Neuinstallation und migrierter Bestand liefern jetzt
  nachweislich identische Tabellendefinitionen.

### Aufgeräumt
- `index.php`: In der Sortierfunktion der Tagestabelle standen die Zweige
  `winch` und `bw` doppelt (toter Code seit Einführung der Spalte
  Sekundärtransport) — entfernt, Verhalten unverändert.

## [Web 2.5.1] — 2026-07-26

### Behoben — Layer-Umschalter zeigte verzerrte Radiobuttons
- Root Cause gefunden: Die globale Regel `input,select{width:100%;padding:…;
  border:…}` (für Formular-Textfelder gedacht) griff auch in den neuen
  Kartenlayer-Umschalter (2.5.0) und zog dessen Radiobuttons zu breiten,
  unsichtbaren Kästchen mit dem Kreis am rechten statt am linken Rand.
  Behoben über die von Leaflet vergebene Klasse
  `.leaflet-control-layers-selector` (`width:auto`, kein Padding/Rahmen) —
  derselbe Musterfehler wie beim dokumentierten `.btn-primary`-Fall, hier für
  `input` statt `button`. Control zusätzlich optisch an die App angeglichen
  (Rahmen, Abstände, Akzentfarbe der Radiobuttons).

## [Web 2.5.0] — 2026-07-26

### Neu — Vollbildmodus für alle Karten
- Jede Karte (Tagesübersicht, Einsatzansicht, Zeitraum-Übersicht) hat jetzt
  oben links ein Vollbild-Control. Nutzt primär die native Fullscreen-API des
  Browsers; wo diese nicht auf beliebige Elemente anwendbar ist (u. a. iOS
  Safari), greift automatisch ein CSS-Overlay-Fallback mit eigener
  ESC-Behandlung. Als gemeinsame, wiederverwendbare Komponente umgesetzt
  (`assets/map_fullscreen.js`), keine neue externe Abhängigkeit.

### Neu — Umschaltbarer Kartenlayer mit topographischen Varianten
- Alle Karten bieten jetzt oben rechts einen Layer-Umschalter (Leaflet-
  Standardcontrol) zwischen dem bisherigen Standard-OSM-Layer und zwei
  Varianten mit Höhenlinien: „Wanderkarte (OpenHikingMap)" und
  „Topographisch (OpenTopoMap)". Beide sind reine Kachel-Layer ohne
  Standort- oder Patientendatenübertragung, ebenso gemeinsam umgesetzt
  (`assets/map_layers.js`).

### Geändert — Phasenmarker in der Einsatzansicht: Standard „Aus"
- Die zuvor deaktivierten Phasenmarker auf der Karte sind wieder aktiv,
  starten aber bei jedem Seitenaufruf ausgeblendet (keine Persistenz). Der
  Toggle („Phasen anzeigen"/„Phasen ausblenden") ist von unterhalb der Karte
  auf die Karte selbst gewandert (eigenes Control, unterhalb des
  Vollbild-Controls) und bleibt dadurch auch im Vollbildmodus bedienbar.
  Hover-/Klick-Kopplung zur Phasentabelle unverändert; löst keinen Fehler
  aus, wenn Marker gerade ausgeblendet sind.

## [Web 2.4.4] — 2026-07-26

### Geändert — Rollenspezifischer Cursor-Fokus bei Besatzung
- Der Cursor springt nach Anlegen/Bearbeiten/Löschen eines Besatzungs-Eintrags
  jetzt gezielt in das Namensfeld der **richtigen Rolle** (z. B. HEMS), nicht
  mehr immer in das erste (Pilot 1). Umgesetzt über einen rollenspezifischen
  Anker (`#besatzung-hems` usw.), gilt für Standortdaten und zentrale
  Stammdaten gleichermaßen.
- Hubschrauber-Tabelle (Standortdaten): Inhalt der Spalte „Rollen" ist jetzt
  ebenfalls zentriert (bisher nur die Spaltenüberschrift).

## [Web 2.4.3] — 2026-07-26

### Neu — Cursor-Fokus nach dem Anlegen
- Nach dem Anlegen, Bearbeiten oder Löschen eines Stammdaten-Eintrags (alle
  sechs Typen, Standortdaten **und** zentrale Stammdaten) springt der Cursor
  automatisch ins Namensfeld des jeweiligen Abschnitts — der nächste Eintrag
  lässt sich ohne Klick direkt eintippen.

### Geändert — Besatzung im Admin-Bereich rollengetrennt
- Die Besatzungs-Vorbelegungen unter „Zentrale Stammdaten" sind jetzt wie bei
  den Standortdaten nach Rolle getrennt (eigene Tabelle/Eingabefeld je Pilot 1,
  Pilot 2, HEMS, Flugretter, Sonstige) statt einem gemeinsamen Formular mit
  Rollen-Dropdown.
- Das Kennzeichen „systemweit" steht in den Standortdaten-Tabellen jetzt
  rechtsbündig in der Aktionen-Spalte (dort, wo bei persönlichen Einträgen
  „Bearbeiten"/„Löschen" stünden), nicht mehr direkt neben dem Namen.

## [Web 2.4.2] — 2026-07-26

### Behoben — Abschnitt bleibt nach Löschen/Bearbeiten zugeklappt
- Root Cause gefunden: Das Skript, das einen Standortdaten-Abschnitt nach dem
  Speichern/Löschen wieder aufklappt, war versehentlich nur innerhalb des
  Backup-Tabs eingebunden und lief daher auf dem Standortdaten-Tab nie. Jetzt
  unabhängig vom aktiven Tab eingebunden.

### Geändert — Spaltenüberschriften zentriert
- In den Tabellen Standorte und Hubschrauber (Standortdaten **und** zentrale
  Stammdaten) sind die Spaltenüberschriften jetzt zentriert. Ursache für das
  vorherige Fehlschlagen einer einfachen CSS-Regel: `table.data th` (Linksbündig)
  hat höhere Spezifität als eine einfache Klassenregel — behoben über
  `table.data.data-centered th`.

## [Web 2.4.1] — 2026-07-26

### Geändert — Feinschliff an den zentralen Stammdaten (2.4.0)
- Formularfehler bei den Hubschrauber-Rollen-Häkchen im Admin-Bereich behoben
  (falsche CSS-Verschachtelung erzeugte großen Abstand zwischen Kästchen und
  Beschriftung).
- Einstellungsmenü: „Administration" ist jetzt eine eigene, abgesetzte
  Überschrift mit den Punkten „NutzerInnenverwaltung" und „Zentrale
  Stammdaten" darunter.
- `admin.php` in `admin_users.php` umbenannt (klarere Abgrenzung zu
  `admin_user.php`, der Detailseite einer einzelnen NutzerIn).
- Überflüssige „Aktionen"-Spaltenüberschrift bei Standorte/Hubschrauber
  entfernt (Standortdaten und zentrale Stammdaten).
- Nach Anlegen/Bearbeiten/Löschen eines Stammdaten-Eintrags kehrt die Seite
  jetzt auch bei einer Fehlermeldung (z. B. Namenskonflikt) zum bearbeiteten
  Abschnitt zurück und klappt ihn wieder auf — bisher galt das nur bei Erfolg.
- Die Kennzeichnung „zentral" heißt jetzt „systemweit" (Badge, Warnhinweise,
  Fehlermeldungen, Leerzustände) — klarer verständlich als isoliertes Wort.
- Fehlermeldung bei Namenskonflikt zeigte den eingegebenen Namen nicht an
  (Ursache: Sonderzeichen direkt nach der Variable in der Zeichenkette);
  behoben durch Verkettung statt Interpolation.

## [Web 2.4.0] — 2026-07-26

### Neu — Zentrale (globale) Stammdaten durch Admin, Transportziele als Stammdaten
- Transportziele lassen sich wie die anderen Rettungsmittel unter
  *Einstellungen → Standortdaten* als Vorbelegung pflegen. Im Einsatzformular
  bleibt das Feld „Transportziel“ Freitext, erhält aber Autocomplete-
  Vorschläge (natives `<datalist>`) aus der eigenen und der zentralen Liste.
- Der Admin kann auf einer neuen Seite „Zentrale Stammdaten“ alle sechs Typen
  (Standorte, Hubschrauber, Besatzungen, Rettungsmittel, Bergwacht-
  Bereitschaften, Transportziele) zentral hinterlegen. Diese Einträge stehen
  automatisch allen NutzerInnen als Vorbelegung zur Verfügung und erscheinen
  in der persönlichen Übersicht mit dem Kennzeichen „zentral“ (nicht editier-
  oder löschbar).
- Beim Anlegen oder Umbenennen eines persönlichen Eintrags wird case-
  insensitiv gegen die zentrale Liste geprüft; bei Treffer wird gespeichert
  abgelehnt mit dem Hinweis „… ist bereits zentral hinterlegt“. Legt der Admin
  nachträglich einen Namen zentral an, der bei einzelnen NutzerInnen bereits
  persönlich existiert, erhält deren Zeile stattdessen den Warnhinweis
  „identisch mit zentralem Eintrag — kann gelöscht werden“ (beide Zeilen
  bleiben sichtbar).
- Die Standard-Vorbelegung (★) für Standort und Hubschrauber ist jetzt
  nutzerbezogen (neue Tabelle `user_defaults`) und funktioniert dadurch auch
  für zentrale Einträge — jede NutzerIn kann unabhängig von den anderen einen
  persönlichen oder zentralen Eintrag als eigenen Standard markieren.
- Backup: Export bleibt nutzerbezogen (Transportziele neu enthalten,
  Formatversion 2 → 3); Import überspringt Einträge, die zentral bereits
  vorhanden sind, und zählt sie in der Ergebnismeldung. Alt-Backups (Version 2)
  bleiben importierbar.

## [Web 2.3.4] — 2026-07-26

### Geändert — Koordinaten/Plus Code jetzt als Vorschlag statt Direktumschreiben
- Erkannte Koordinaten (Dezimalgrad, GDM, DMS) und Plus-Code-Vollcodes
  schreiben das Einsatzort-Feld nicht mehr sofort um, sondern erscheinen —
  wie ein Adresstreffer — als anklickbarer Eintrag in derselben Vorschlags-
  liste (z. B. „Koordinaten übernehmen (Dezimalgrad): 47.72610, 10.31700"
  bzw. „Plus Code übernehmen: 8FWH4HJM+7Q"). Erst mit der Auswahl werden
  `lat`/`lon` gesetzt und das Feld auf die normalisierte Darstellung
  aktualisiert. Ablauf dadurch für Adresse, Koordinate und Plus Code
  identisch. Kurzform- und Bereichsfehler-Hinweise bleiben als reine
  Statuszeilen-Meldung bestehen (kein Vorschlag, da nichts zu übernehmen).
- Keine Netzwerk-Anfrage weiterhin für alle vier Fälle (Koordinate, DMS,
  Plus Code, Kurzform/ungültig) — nur bei Adresstext wird wie bisher Photon
  angefragt.

## [Web 2.3.3] — 2026-07-26

### Neu — Einsatzort erkennt zusätzlich Grad/Minuten/Sekunden (DMS)
- Nachtrag zu 2.3.2: Das Einsatzort-Feld erkennt jetzt auch das Format
  **Grad/Minuten/Sekunden** (z. B. `47°39'11.6"N 10°21'34.3"E`), das im
  ursprünglichen Konzept bewusst ausgeschlossen war, um den Umfang klein zu
  halten. Umrechnung wie bei den übrigen Formaten vollständig lokal im
  Browser, keine Server-Änderung.

## [Web 2.3.2] — 2026-07-26

### Neu — Einsatzort akzeptiert Koordinaten und Plus Codes
- Das Einsatzort-Feld erkennt beim Tippen automatisch drei zusätzliche
  Formate und wandelt sie **vollständig lokal im Browser** in Koordinaten
  um, ohne die bestehende Adresssuche (Photon) zu verändern:
  **Dezimalgrad** (`47.7261, 10.3170`), **Grad/Dezimalminuten**
  (`47°43.57'N 010°19.02'E`) und **Plus-Code-Vollcodes** (`8FWH4HJM+7Q`).
  Wird eines der Formate erkannt, entfällt die Photon-Anfrage; die
  Statuszeile meldet das erkannte Format, ungültige bzw. unvollständige
  Werte werden als solche kenntlich gemacht.
- Neue Datei `assets/locparse.js` (reine Formaterkennung/Parser, keine
  DOM-/Netzwerk-Zugriffe) sowie die gevendorte Bibliothek
  `assets/openlocationcode.js` (`google/open-location-code`,
  Apache-2.0) für die Plus-Code-Dekodierung.
- Bewusste Ausschlüsse: **kein What3Words** (proprietär, nur per externer
  API dekodierbar — Datenschutz-Veto), **keine Plus-Code-Kurzformen**
  (bräuchten Geocoding eines Referenzorts), **kein Reverse-Geocoding**
  erkannter Koordinaten (kein Serverkontakt bei Koordinaten-/Plus-Code-
  Eingabe), kein UTM/MGRS und keine Grad/Minuten/Sekunden-Formate.
- Datenmodell (`pat_blob` → `loc: {addr, lat?, lon?}`) und die Konsumenten
  `einsatz.php`, `index.php`, `zeitraum.php` bleiben unverändert.

## [Web 2.3.1] — 2026-07-26

### Geändert — Lesbare Fehlermeldungen statt leerem HTTP 500
- `api/range.php`, `api/day.php`, `api/mission.php` und `api/backup_data.php`
  kapseln ihre Datenbankzugriffe jetzt in try/catch (Muster wie bisher schon
  bei `api/backup_restore.php`) und antworten bei einer Ausnahme mit
  `{"error": "...", "meldung": "..."}` statt eines leeren HTTP 500. Anlass:
  Nach dem Ausliefern von 2.3.0, aber **vor** dem Aufruf von `/update.php`,
  fehlte die Spalte `site_ele_m` noch — `zeitraum.php` zeigte dadurch nur
  „HTTP 500" ohne jeden Hinweis auf die Ursache.
- `zeitraum.php` und `einsatz.php` zeigen das Feld `meldung` jetzt mit an
  (`index.php` tat das für `api/day.php` bereits vorher).

## [Web 2.3.0] — 2026-07-26

### Neu — Karte und Statistik in der Zeitraum-Übersicht
- **Karte mit Einsatzort-Pins:** Monats- und Jahresansicht zeigen jetzt eine
  Leaflet-Karte mit einem Pin (Max Blau, weißer Rand) je Einsatz mit
  gespeicherten Koordinaten. Popup zeigt Datum und Adresse. Keine
  Trackpunkte (unverändert bewusst nicht ausgeliefert) und kein Clustering.
  Karte bleibt ausgeblendet, wenn kein Einsatz Koordinaten hat oder der
  Inhaltsschlüssel gesperrt ist.
- **Statistiktabelle** oberhalb der Einsatzliste mit acht Kennzahlen:
  durchschnittliche Einsätze/Flugtag, durchschnittliche Windenzyklen/Flugtag,
  Anzahl Windeneinsätze, Anzahl Einsätze, Anzahl Sekundärtransporte, längste
  Flugstrecke, längste Einsatzdauer, höchster Einsatzort. Divisor der
  Durchschnittswerte sind alle im Zeitraum angelegten Flugtage, **auch ohne
  Einsatz** — eine bewusste Semantikänderung der Kopfzeile (vorher nur Tage
  mit dokumentiertem Einsatz).
- **Neues Feld „Einsatzort-Höhe" (`site_ele_m`):** Höhe am Patientenkontakt
  (Phase 5, Fallback Phase 6), aus dem GPS-Track berechnet und in der
  Einsatz-Detailansicht angezeigt. Neuberechnung bei jedem Uhr-Upload,
  jedem manuellen Speichern und jedem Backup-Restore — eine einzige
  Implementierung (`site_elevation_lib.php`). Migration mit Backfill für
  Bestandseinsätze.
- **Button „Weiteren Einsatz nachtragen"** auf der Einsatzansicht direkt nach
  Neuanlage eines manuellen Einsatzes — führt zur Neuanlage für denselben
  Flugtag. Erscheint nicht beim Bearbeiten bestehender Einsätze.

### Neu — Verlassen-Warnung und Strg-/Cmd-Enter
- Einsatz-Formular, Flugtag-Metadaten und Flugtag-Anlage fragen jetzt beim
  Verlassen mit ungespeicherten Änderungen nach (Browser-Dialog); das
  reguläre Absenden löst keine Abfrage aus. Gemeinsamer Helfer
  `assets/forms.js`.
- **Strg-Enter** (bzw. Cmd-Enter auf macOS) sendet dieselben Formulare ab;
  in Textareas bleibt einfaches Enter ein Zeilenumbruch, die
  Enter-Sonderbehandlung im Einsatzort-Autocomplete ist unberührt.

### Behoben
- **Schockraum-Haken beim Transportziel** wurde nie angezeigt: Der
  Formular-Renderer gab Unterfelder nur bei Checkbox-Elternfeldern aus,
  „Transportziel" ist aber ein Textfeld. Der Haken erscheint jetzt stets
  sichtbar unter dem Feld, unabhängig von dessen Inhalt.
- **Phasenzeilen wurden nicht zeitlich einsortiert:** Ein nachträglich am
  Listenende ergänzter, zeitlich früherer Eintrag führte zu einer falschen
  Tagesüberschritt-Erkennung (`$dayOffset`) und einem falschen `started_at`.
  Die Zeilen werden vor der Verarbeitung nach Phasennummer sortiert (Phasen
  2–9 sind fachlich chronologisch); nach dem Speichern erscheint die Liste
  beim erneuten Öffnen automatisch sortiert.

### Geändert
- **Zusatz „(bei Einsatz)" beim Alter entfernt** — die Detailansicht zeigt
  nur noch die Zahl (Berechnung zum Einsatztag bleibt unverändert).
- **Zweistellige Jahreszahlen beim Geburtsdatum** (z. B. „23.04.33") werden
  jetzt korrekt interpretiert: gleitende Fensterregel 2000+JJ, bei
  Zukunftsdatum stattdessen 1900+JJ.
- Platzhaltertext „kurze Beschreibung (Detailansicht)" beim Feld
  „Beschreibung Einsatzort" entfernt.

## [Uhr 1.4.0] — 2026-07-25

### Geändert — Schnellmenü umsortiert
- **Schnellmenü der Hauptseite:** Beim Öffnen (lang START) ist jetzt die
  **Einsatzübersicht** vorausgewählt; ein Schritt nach oben liegt „Einsatztag
  beenden", nach unten folgen die Phasen 2, 3, 4 … Das Endlos-Scrollen durch
  alle Punkte bleibt erhalten.

> **Richtigstellung (nachgetragen mit Uhr 1.5.0).** Dieser Eintrag nannte
> ursprünglich vier weitere Punkte zur Reanimations-Bedienung (kurz START
> öffnet das Untermenü, lang START startet den Countdown neu, neuer Menüpunkt
> „Timer neu starten", Countdown-Neustart bei Defibrillation, Untermenü im
> Design des Schnellmenüs). Diese Änderungen waren geplant, sind mit 1.4.0
> aber **nicht** ausgeliefert worden — der Code blieb unverändert. Sie stehen
> jetzt in [Uhr 1.5.0]. Der Eintrag ist deshalb auf das tatsächlich
> Ausgelieferte gekürzt.

## [Web 2.2.3] — 2026-07-23

### Geändert — Favicon robuster eingebunden
- Der Verweis ist jetzt **wurzelbezogen** (`/assets/images/favicon.png`) statt
  relativ, damit die Auflösung unabhängig von der aufgerufenen Adresse ist.
  Der Pfad wird aus `SCRIPT_NAME` abgeleitet und funktioniert daher auch in
  einem Unterordner.
- `sizes="any"` an der `.ico` entfernt: Diese Angabe steht für skalierbare
  Symbole; manche Browser hätten die `.ico` dadurch bevorzugt und bei ihrem
  Fehlen gar kein Symbol angezeigt. Das PNG steht jetzt an erster Stelle,
  ergänzt um `apple-touch-icon` für iOS.

## [Web 2.2.2] — 2026-07-23

### Behoben
- **Papierkorb: Symbol und Text standen untereinander.** Ursache war ein
  Spezifitäts-Konflikt — `.daylist a` setzt `display:block`, steht weiter unten
  im Stylesheet und hat mehr Gewicht als `.trashlink`. Das beabsichtigte
  `display:flex` hat deshalb **nie** gegriffen; die frühere „leichte
  Verschiebung" war in Wahrheit reine Grundlinien-Ausrichtung. Die Regeln
  lauten jetzt `.daylist a.trashlink` und stehen nach `.daylist a`.
- Das Papierkorb-Symbol ist rund 30 % höher als die Schrift. Die `viewBox` im
  Markup ist dafür auf die Zeichnung zugeschnitten (vorher rundum Leerraum),
  sodass die Höhenangabe im Stylesheet der sichtbaren Größe entspricht.

### Geändert — Favicon
- Zusätzlich `favicon.ico` im Wurzelverzeichnis. Browser fragen diese Adresse
  von sich aus ab, unabhängig vom Verweis im Seitenkopf — damit erscheint das
  Symbol auch dann, wenn der Kopf-Verweis einmal ins Leere läuft oder der
  Browser ein früheres Fehlen zwischengespeichert hat.
- Die Verweise stehen jetzt zentral in `favicon_tags()` (in `db.php`) statt
  einzeln in 16 Dateien.

## [Web 2.2.1] — 2026-07-23

### Behoben
- **Kein Logo auf der Anmeldeseite.** Die in 2.2.0 neu ausgewertete Einstellung
  `logo_path` steht in bestehenden Installationen noch auf dem alten Wert
  `assets/logo.svg` — eine Datei, die es nie gab. Der Rückfallwert griff nicht,
  weil der Eintrag ja vorhanden war. Neue Hilfsfunktion `logo_src()` prüft jetzt,
  ob die angegebene Datei wirklich existiert, und nimmt sonst die mitgelieferte
  Bildmarke. Das stille Ausblenden bei Ladefehlern (`onerror`) ist entfallen,
  da es genau solche Fehler verdeckt.
- **Aufzählungspunkte im Einstellungsmenü.** Beim Umbau der Einsatztage-Leiste
  auf das Jahr/Monat-Akkordeon war die Regel `.daylist ul` entfallen, die auch
  das Einstellungsmenü entpunktet hatte.
- **„Abmelden" und „Abbrechen" im Bestätigungsdialog unterschiedlich groß.**
  `.btn-primary` trägt global `width:100%` und einen oberen Abstand für
  Formulare; im Dialog wurde beides nicht zurückgenommen. Dieselbe Ursache wie
  zuvor bei „+ Einsatz nachtragen" — die übrigen Knopf-Kontexte sind jetzt
  durchgesehen und abgesichert.
- **Papierkorb-Symbol und Beschriftung** sind jetzt an dieselbe Schriftgröße
  gekoppelt (1,4 em). Zuvor war das Symbol mit 24 px fast doppelt so hoch wie
  die 13-px-Schrift und wirkte dadurch versetzt, obwohl beide Kästen mittig
  zueinander standen.

## [Web 2.2.0] — 2026-07-23

### Geändert — Logos als Vektorgrafik
- Die Bildmarke liegt jetzt als **SVG** unter `assets/images/` (farbige und
  weisse Fassung, Originale der Gestaltung). Sie ist damit in jeder Größe und
  auf hochauflösenden Bildschirmen scharf — die bisherige Bildmarke in der
  Kopfleiste hatte mit 96×61 Pixeln zu wenig Reserve und wirkte dort leicht
  unscharf. Nebenbei sinkt die Datenmenge von rund 184 KB auf 11 KB.
- Favicon bleibt PNG (breiteste Unterstützung, Schärfe bei 64×64 belanglos)
  und liegt ebenfalls unter `assets/images/`.
- Alle Einbindungen laufen über `asset()`, tragen also die Version — nach
  einem Logo-Wechsel lädt der Browser es von selbst neu. Das ist gerade beim
  Favicon nützlich, den Browser sonst sehr hartnäckig zwischenspeichern.
- **Nebenbefund behoben:** Die Einstellung `logo_path` war seit jeher wirkungslos
  — Anmelde- und Einrichtungsseite banden das Logo fest ein, und der
  Vorgabewert zeigte auf eine nie existierende Datei (`assets/logo.svg`). Beide
  Seiten werten die Einstellung jetzt aus, mit dem neuen SVG als Rückfallwert.

## [Web 2.1.0] — 2026-07-22

### Behoben — Passwort zurücksetzen
- **Ein Reset machte das Konto unbrauchbar.** `reset_confirm.php` speicherte
  den Hash des rohen Passworts, während die Anmeldung den Hash des im Browser
  abgeleiteten Tokens erwartet — eine Anmeldung war danach unmöglich. Zusätzlich
  wurde der Inhaltsschlüssel nicht neu verpackt, sodass auch alle
  verschlüsselten Angaben unlesbar geworden wären.
- Der Reset verlangt jetzt den **Wiederherstellungsschlüssel**: Der Browser
  entpackt damit den Inhaltsschlüssel, leitet aus dem neuen Passwort Salz und
  Token ab und verpackt den Schlüssel neu. Server speichert alles in einer
  Transaktion — passt der Wiederherstellungsschlüssel nicht, bleibt das Konto
  unverändert.
- Kein Datenleck: Wer nur Zugriff auf das Postfach hat, kommt weiterhin nicht
  an die verschlüsselten Angaben.

### Entfernt — Unterstützung unverschlüsselter Konten
- Anmeldung, Salt-Endpunkt, Passwortwechsel und Zugriffsschutz kannten je einen
  Sonderweg für Konten ohne Browser-Schlüsselableitung (`kdf_ver = 0`). Da alle
  Konten umgestellt sind, sind diese Pfade entfallen — inklusive der Stelle, an
  der das Passwort einmalig im Klartext zum Server ging.
- Browser ohne Web-Krypto erhalten jetzt eine klare Meldung statt eines stillen
  Rückfalls auf den alten Weg.

## [Web 2.0.0] — 2026-07-22

### Versionierung eingeführt
- Die Weboberfläche hat jetzt eine eigene Version (`server/version.php`). Sie
  erscheint in der Fußzeile und hängt an allen Stylesheet- und Skript-Adressen,
  wodurch der Browser nach einem Update automatisch die neuen Dateien lädt —
  das manuelle Leeren des Zwischenspeichers entfällt.
- **Behoben:** Auf `zeitraum.php`, `papierkorb.php`, `flugtag_neu.php`,
  `einsatz_loeschen.php` und `flugtag_loeschen.php` stand die Fußzeile
  außerhalb des Inhaltsbereichs und war dadurch nicht sichtbar — Copyright und
  Lizenzhinweis fehlten auf diesen Seiten.

### Web
- **Neue geschützte Felder:** Nachname, Vorname und Geburtsdatum — wie
  Diagnose und Einsatzort Ende-zu-Ende-verschlüsselt im selben Container, also
  ohne Datenbankänderung und automatisch im Backup enthalten. Sie erscheinen
  nur in der Einsatzansicht, nicht in den Tabellenübersichten.
- **Alter wird aus dem Geburtsdatum berechnet** — bezogen auf den Einsatztag,
  nicht auf heute, und bei jeder Anzeige neu (kein Nachziehen bei Korrekturen).
  Ohne Geburtsdatum bleibt das Feld wie bisher von Hand eintragbar; die Spalte
  „Alter" in den Übersichten bleibt erhalten. Gemeinsame Berechnung in
  `assets/patient.js`, genutzt von Formular, Einsatzansicht, Tages- und
  Zeitraumübersicht.
- Papierkorb-Symbol und Beschriftung sind vertikal exakt mittig zueinander
  ausgerichtet (feste Zeilenhöhe hatte den Text nach oben versetzt).

- **Jahres- und Monatsübersicht:** Klick auf Jahreszahl oder Monatsnamen in der
  Einsatztage-Leiste öffnet `zeitraum.php` mit allen Einsätzen des Zeitraums als
  Tabelle (Datum statt Nummer, keine Karte, sortierbar, Zeile führt zum Einsatz)
  samt Kennzahlen. Das Dreieck klappt weiterhin nur auf und zu.
  Neuer Endpunkt `api/range.php` — bewusst ohne Trackpunkte, da bei einem
  ganzen Jahr sonst hunderttausende Koordinaten übertragen würden.
- **Standortdaten:** Nach dem Speichern wird jetzt gezielt zum jeweiligen
  Abschnitt umgeleitet — er klappt dadurch wieder auf, die Seite springt an die
  richtige Stelle, und ein Neuladen sendet das Formular nicht erneut ab.
- **Behoben:** „+ Einsatz nachtragen" lief weiterhin über die volle Breite. Der
  Knopf erbt aus dem Formular-Stil `width:100%`; in der Aktionsleiste fehlte das
  ausdrückliche `width:auto`, weshalb frühere Anläufe (Ausrichtung, Höhe) nichts
  bewirkten.

- **Kein Rahmen mehr an Aufklapp-Überschriften:** Der blaue Fokusrahmen passte
  nicht zur übrigen Form und umschloss bei geöffnetem Abschnitt den gesamten
  Inhalt. Ersetzt durch dieselbe dezente Färbung wie beim Überfahren mit der
  Maus — für Tastaturbedienung weiterhin erkennbar, ohne aufzufallen.
- **Fokusring bleibt nicht mehr nach Mausklicks stehen:** Er erscheint jetzt
  nur noch bei Tastaturbedienung (`:focus-visible`). Bei aufklappbaren
  Abschnitten umschloss er zuvor den gesamten geöffneten Bereich statt nur der
  Kopfzeile — dadurch wirkte die Umrandung von Jahr und Monat unterbrochen und
  überlagerte die Markierung des ausgewählten Tages. Bei Tastaturbedienung
  liegt der Ring nun innerhalb der Zeile.

- **Behoben: Übersicht blieb komplett leer.** Beim Gruppieren der Einsatztage
  wandelt PHP numerische Array-Schlüssel automatisch in Integer um („2026" →
  2026). Unter `strict_types` brach `e()` damit mit einem TypeError ab —
  mitten im Rendern der Leiste, sodass weder Tage noch Karte oder Tabelle
  erschienen. Zusätzlich schlug der Monatsvergleich ab Oktober fehl („12"
  wird zu 12, „07" bleibt Text), wodurch dort nie ein Monat aufgeklappt wäre.
  Beide Stellen wandeln jetzt ausdrücklich nach String.

- **Einsatztage-Leiste nach Jahr und Monat gruppiert:** Es ist immer genau
  ein Jahr geöffnet (echtes Akkordeon — ein anderes Jahr anklicken schließt
  das vorherige automatisch), darin genau ein Monat, standardmäßig der
  jüngste mit Einträgen. Springt man auf einen Tag in einem anderen
  Jahr/Monat (z. B. über den Papierkorb oder eine alte Verlinkung), klappt
  die Leiste automatisch dorthin auf.

- **Aktionsleiste und Papierkorb aufgeräumt:** Über mehrere Runden hatten
  sich für `.dayactions` und `.trashlink` mehrere, teils widersprüchliche
  Regeln im Stylesheet angesammelt. Zu einem einzigen Block zusammengeführt —
  „+ Einsatz nachtragen" und „Tag löschen" haben dadurch garantiert dieselbe
  Höhe, Schrift und Grundlinie; Papierkorb-Symbol und -Text sind horizontal
  zentriert und zueinander vertikal mittig ausgerichtet.
- **Kartenzoom vereinheitlicht:** Tagesübersicht und Einsatzansicht zoomen
  jetzt nach derselben Regel automatisch auf die Tracks (Rand proportional zur
  Kartengröße statt fester Pixelwert) und mit einer gemeinsamen Obergrenze —
  ein einzelner kurzer Track zoomt nicht mehr bis auf Gebäude-Ebene heran.
- **Max Blau** (Markenfarbe) sichtbarer eingesetzt: Fokusringe, Sortierpfeile
  in der Tagesübersicht, Kontrollkästchen und der „Flugtag anlegen"-Link
  nutzen jetzt Blau statt Orange — als ruhiger Gegenpart zu den
  Haupt-Aktionen (Orange) und Löschen (Rot).

- **Andere Rettungsmittel:** neue Vorbelegungsliste in den Standortdaten und
  Eingabe mit Vorschlägen im Einsatzformular (Suche ab zwei Zeichen, Klick
  übernimmt, freie Eingaben möglich). Jedes Rettungsmittel wird als eigener
  Datensatz gespeichert und lässt sich einzeln wieder entfernen; bisherige
  Freitexte werden bei der Migration automatisch aufgeteilt.
- **Standortdaten aufgeräumt:** Die fünf Bereiche sind jetzt aufklappbare
  Abschnitte und starten zugeklappt. Wer über einen Anker hineinspringt — etwa
  nach dem Speichern —, landet in einem automatisch geöffneten Abschnitt.
- **Flugtag von Hand anlegen** über die Einsatztage-Spalte, für Tage ohne Uhr.
- Kopfleiste bleibt beim Scrollen stehen; der Papierkorb ist beschriftet;
  „+ Einsatz nachtragen" und „Tag löschen" sind gleich hoch und gleich gesetzt.
- Kartenlinien durchgehend eine Stufe dünner, Einsatz- und Tagesansicht nutzen
  jetzt dieselbe Staffelung.

- Tagesübersicht besser lesbar: Zeilen abwechselnd schattiert, alle Spalten
  mittig ausgerichtet, Dauer kompakt gesetzt („3h 33min" statt „3 h 33 min"),
  damit die Spalte einzeilig bleibt. Bergwacht, Sekundär/Transport und
  „Flug km" haben mehr Luft bekommen; die Seite ist dafür 1200 px breit.

- **Neues Logo** (Hubschrauber-Bildmarke) für Kopfleiste, Login-,
  Einrichtungsseite und Favicon. Die Vorlagen wurden freigestellt (weißer
  Hintergrund → transparent, Kantenglättung erhalten); die weiße Fassung
  übernimmt die Maske der farbigen, damit beide deckungsgleich sitzen. Das
  Favicon liegt quadratisch mit Rand vor, damit es im Browser-Tab nicht
  verzerrt.

- Tagesübersicht: Spaltenüberschriften werden nicht mehr silbengetrennt —
  Winde, Bergwacht und „Flug km" stehen einzeilig, „Sekundär/Transport" bricht
  genau zwischen den Wörtern um; Alter ist so breit wie Beginn. Seitenbreite
  1150 px; die festen Spalten belegen rund 600 px, der Rest bleibt für
  Einsatzort und Diagnose.
- **Menüspalte bleibt stehen:** Die Einsatztage-Leiste nimmt die volle
  Fensterhöhe ein und scrollt bei vielen Tagen intern; der Papierkorb sitzt in
  einem festen Streifen darunter und ist dadurch immer sichtbar, ohne die Seite
  scrollen zu müssen.

- **Tagesübersicht zeigt Ladefehler an,** statt still leer zu bleiben: Liefert
  die Tages-API kein JSON (z. B. weil eine Migration fehlt), erscheint jetzt
  eine Meldung mit dem Anfang der Serverantwort. Vorher brach das Skript
  wortlos ab — Titel, Tabelle, Karte und der Löschknopf blieben leer.
- „Tag löschen" wird serverseitig eingeblendet und hängt nicht mehr am
  erfolgreichen Laden der Tagesdaten.
- Papierkorb: Aufbewahrung von 30 auf **90 Tage** verlängert; die Aktionen
  „Wiederherstellen" und „Endgültig löschen" sind gleich groß und bündig.
- Tagesübersicht: feste Tabellenaufteilung, damit die Spaltenbreiten wirklich
  greifen; Seitenbreite auf 1240 px erhöht, sodass Flugtag-Kasten, Karte und
  Tabelle gleich breit sind. Papierkorb-Symbol in fester Größe am unteren Rand
  der Einsatztage-Spalte.

- **Neue Felder:** „Sekundärtransport" (Haken, eigene sortierbare Spalte in der
  Tagesübersicht neben Bergwacht) und „Schockraum" (Haken beim Transportziel).
- **Papierkorb ist eine eigene Seite** und über ein Symbol unten in der
  Einsatztage-Spalte erreichbar — ausgegraut, solange er leer ist. Die
  Aktionen „Wiederherstellen" und „Endgültig löschen" sind jetzt Schaltflächen.
- Tagesübersicht: Spaltenbreiten in vier Stufen über Klassen statt Positionen
  (Farbe/Nr. sehr schmal; Alter, Winde, Bergwacht, Sekundärtransport schmal;
  Beginn, Dauer, Flugkilometer mittig und mittelbreit; Einsatzort und Diagnose
  bekommen den Rest). Neue Spalten verschieben dadurch keine Breiten mehr.
- Aktionsleiste unter der Tabelle: Schaltflächen nur so breit wie nötig,
  „Flugtag löschen" heißt jetzt „Tag löschen".
- Einsatzansicht: „Bearbeiten" und „Löschen" stehen rechts neben Titel und
  Uhrzeit statt darunter; Schaltflächen werden nicht mehr unterstrichen.
- „Abbrechen" auf den Löschseiten ist eine Schaltfläche statt eines Textlinks.
- **Altes Backup-Format entfernt:** Der serverseitige `.edbak`-Weg (Version 1)
  ist raus — Container-Funktionen in `backup_lib.php`, die Versionsweiche in
  `crypto.js`, der Import-Zweig samt Datei-Upload in `einstellungen.php` und
  Kapitel 4 der Formatdoku. Der Import prüft jetzt strikt die Dateikennung und
  lehnt alles andere mit klarer Meldung ab; damit kann kein zweiter Importweg
  mehr dazwischenfunken.
- Unter der Tagestabelle stehen jetzt zwei Schaltflächen: links „+ Einsatz
  nachtragen", rechts „Flugtag löschen" (weiterhin mit serverseitiger
  Bestätigungsseite).
- **Behoben:** Die neuen Seiten `flugtag_loeschen.php`, `einsatz_loeschen.php`
  und `papierkorb.php` banden `ui.php` ein zweites Mal ein (ohne `_once`),
  obwohl `auth_guard.php` sie bereits lädt — PHP brach mit „Cannot redeclare"
  ab, im Browser als Fehler 500 sichtbar.
- **Rückfragen laufen nicht mehr über Browser-Dialoge:** `window.confirm()` bot
  die Option „keine weiteren Dialoge dieser Seite anzeigen" — danach wären
  Löschungen ohne jede Nachfrage durchgelaufen. Alle Bestätigungen nutzen jetzt
  ein Fenster im Seiteninhalt (`assets/confirm.js`, `data-confirm`), das sich
  nicht abschalten lässt; „Abbrechen" ist vorausgewählt, Escape bricht ab.
- **Backup-Import schlug scheinbar fehl, obwohl er lief:** Der Formular-Handler
  brach das normale Absenden erst nach dem Einlesen der Datei ab. Bis dahin
  hatte der Browser das Formular längst mitgeschickt, sodass parallel der alte
  serverseitige Import lief und mit „Keine gültige Backup-Datei" antwortete —
  während der Browser-Import im Hintergrund korrekt durchlief. Das Absenden
  wird jetzt sofort unterbunden; Altformat-Dateien werden gezielt an den Server
  weitergereicht.
- **Papierkorb für Einsätze und Flugtage:** Gelöschtes wird zunächst nur
  markiert und bleibt 30 Tage wiederherstellbar (Anzeige unten auf der
  Übersicht, je Tabelle für Flugtage und Einsätze mit „Wiederherstellen" und
  „Endgültig löschen"). Der Aufräumjob entfernt Abgelaufenes automatisch.
- **Flugtag löschen** entfernt den kompletten Tag (Einsätze, Ruhesegmente,
  Tracks, Reanimationen, Flugtag-Angaben) und stellt ihn geschlossen wieder her.
- Schwere Löschungen laufen über eine **serverseitige Zwischenseite mit
  Umfangs-Anzeige** (ohne JavaScript wirksam) statt über einen Browser-Dialog.
- **Nutzer löschen** verlangt jetzt zusätzlich das Abtippen der E-Mail-Adresse;
  geprüft wird serverseitig.
- Uploads der Uhr für Einsätze im Papierkorb werden quittiert, aber verworfen;
  erst das endgültige Löschen sperrt die Referenz dauerhaft.
- **Backup läuft jetzt im Browser** (Format 2): Beim Export werden die
  geschützten Angaben lokal entschlüsselt und mit dem Backup-Passwort
  versiegelt; beim Import öffnet der Browser die Datei und verschlüsselt sie
  mit dem Schlüssel des **Zielkontos** neu. Damit lässt sich ein Backup in
  jedes Konto einspielen — der Server sieht zu keinem Zeitpunkt Klartext.
  Container: AES-256-GCM, PBKDF2 310 000 Runden, gzip, Kopf per AAD gebunden.
- Alt-Dateien (Format 1) werden am Kopf erkannt und weiterhin serverseitig
  importiert; ihre geschützten Angaben bleiben kontogebunden.
- Neue Endpunkte `api/backup_data.php` und `api/backup_restore.php`;
  `export_backup.php` entfällt.
- Ruhesegment-Tracks (Phase 1) auf der Tageskarte deutlich sichtbarer:
  warmes Grau statt Fast-Schwarz, kräftigere Linie mit Zoom-Anpassung.
- **Verschlüsselung ist jetzt Pflicht:** kein Modul-Schalter, keine
  Feldauswahl mehr — der Einstellungs-Reiter „PatientInnendaten" entfällt.
  Beim ersten Anmelden erzwingt das System die **Ersteinrichtung** mit
  einmalig angezeigtem Wiederherstellungsschlüssel (einrichtung.php); dieselbe
  Seite entsperrt nach einem Passwort-Reset per Wiederherstellungsschlüssel.
- Verschlüsselte Felder sind **Diagnose und Alter** (Nachname, Vorname und
  Geburtsdatum entfallen); der **Einsatzort** (Adresse + Koordinaten) wandert
  ebenfalls in den verschlüsselten Block — Klartext-Altbestände wurden per
  Migration verworfen (Spalten entfernt).
- Tagesübersicht: Spalten Nr. · Beginn · Dauer · **Einsatzort (Ortschaft aus
  der Adresse)** · **Alter** · **Diagnose** · Winde · Bergwacht · Kilometer;
  sortierbar außer Winde/Bergwacht. Karten-Pins entstehen jetzt aus den lokal
  entschlüsselten Koordinaten; Sperr-Banner mit Entsperr-Link nach Reset.
- **Admin-Passwortvergabe entfernt** (würde verschlüsselte Daten unlesbar
  machen); Hinweis auf „Passwort vergessen" + Wiederherstellungsschlüssel.
- Backup: exportiert die Schlüssel-Hüllen ohne Modul-Schalter; Alt-Backups
  mit Klartext-Ort werden beim Import toleriert (Ort wird verworfen).
- **Einsatzansicht komplett neu gebaut:** Bearbeiten-Link führt wieder zum
  richtigen Einsatz (die Seite hatte die Einsatz-ID verloren), volle Breite
  wie die Flugtag-Übersicht, Aktionsleiste nebeneinander.
- **Karten:** Einsatzort-Pins in der Farbe des jeweiligen Einsatzes (Ring in
  Trackfarbe); Tracklinien werden beim Rauszoomen dicker und nicht mehr
  vereinfacht — kurze Tracks bleiben auf der Tagesübersicht sichtbar.
- Überall „Flugtag" statt „Betriebstag" (Titel, Formular, Doku).
- Flugtag-**Notizen** stehen sichtbar im zugeklappten „Flugtag-Daten"-Kästchen.
- Standortdaten (vorher „Stammdaten", umbenannt): Hinweis „Rollen auf dem
  Hubschrauber:" vor den Häkchen.
- **Geräte umbenennbar** (gelber Bearbeiten-Button je Zeile).
- Administration: Name als eigene Spalte, ganze Zeile reagiert auf
  Hover/Klick; Abmelden fragt nach Bestätigung.
- **Backup (Export/Import):** Einstellungs-Reiter „Backup" sichert alle
  eigenen Daten (Einsätze inkl. Phasen/Reanimationen/Tracks, Ruhesegmente,
  Flugtage, Stammdaten, verschlüsselte PatientInnendaten samt
  Schlüssel-Hüllen) in eine einzelne `.edbak`-Datei — verschlüsselt mit frei
  wählbarem Passwort (AES-256-GCM, PBKDF2 200 000 Runden, manipulationssicher
  per GCM-Tag). Import ergänzt nur Fehlendes (Dubletten-Schutz über interne
  Referenzen), überschreibt nie. Formatbeschreibung: `docs/Backup-Format.md`.
- **PatientInnendaten-Modul (Ende-zu-Ende-verschlüsselt):** Felder Nachname,
  Vorname, Diagnose, Geburtsdatum, Alter (Alter automatisch aus Geburtsdatum,
  Stichtag Einsatzdatum; auch allein ausfüllbar). Ver- und Entschlüsselung
  ausschließlich im Browser (AES-256-GCM); der Login wurde auf
  Browser-Schlüsselableitung umgestellt (PBKDF2, 310 000 Runden) — der Server
  sieht das Passwort nie mehr und speichert nur Chiffretext. Eigener
  Einstellungs-Reiter: Aktivierung mit einmalig angezeigtem
  **Wiederherstellungsschlüssel**, Feldauswahl (Abwählen blendet nur aus),
  Modul an/aus, Zugriff-Wiederherstellen nach Passwort-Reset. Nachname-Spalte
  in der Tagesübersicht (lokal entschlüsselt, sortierbar). Bestehende Konten
  werden beim ersten Login transparent umgestellt.
- **Geräte-Kopplung per Kurzcode:** Im Web (Einstellungen → Geräte) einen
  5-Zeichen-Code erzeugen (60 Minuten gültig, einmal verwendbar), auf der Uhr
  am Startbildschirm **UP halten** und den Code eintippen — die Uhr holt sich
  ihre Zugangsdaten selbst und speichert sie dauerhaft. Geräte-ID und
  API-Schlüssel müssen nie mehr abgetippt werden; als einzige Einstellung
  bleibt die Server-Domain. Der bisherige Weg (manuell anlegen) bleibt als
  Alternative bestehen.
- **Stammdaten vereinheitlicht:** Alle vier Bereiche als helle Tabellen mit
  Aktionen in einer Zeile — Bearbeiten (gelb) und Löschen (rot); auch
  Besatzungs-Einträge sind jetzt umbenennbar; alles alphabetisch sortiert.
- **Standard-Maschine und Standard-Standort** (★): per „Als Standard" gesetzt;
  Flugtage ohne gespeicherte Auswahl werden damit vorbelegt.
- Kopfleiste: ⚙ ist jetzt ein Direktlink zu den Einstellungen (kein
  Aufklappmenü); mehr Abstand um Logo und Titel.
- **Sicherheit:** Automatische Abmeldung nach 30 Minuten Inaktivität (mit
  Hinweis auf der Login-Seite).
- **Einsatzfelder-Ausbau:** Feldsystem mit neuen Typen (Checkbox, Dropdown,
  bedingte Unterfelder, Tagesspalten-Flag). Neue Felder: Transportziel,
  Beschreibung Einsatzort, Windeneinsatz (Cycles 0–8, Cycles mit Patient,
  Luftverladung), Bergwacht (Bereitschaft aus Stammdaten + Namen/Infos),
  Anderer Notarzt, Weitere Rettungsmittel — alle als echte DB-Spalten.
- **Tagestabelle:** Spalten Nr./Einsatzort/Winde/Bergwacht, klickbare
  Spaltensortierung (Standard: Alarmierungszeit); Dauer strikt aus Phase 9 —
  ohne Phase 9 steht dort „kein Ende". Einsatz-Titel „Einsatz N · Zeit"
  (N = Tagesnummer nach Alarmierungszeit).
- **Einsatz löschen:** Button mit Bestätigung in der Einsatzansicht; Sperrliste
  verhindert Wiederanlage durch gepufferte Uhr-Daten (Einträge verfallen nach
  90 Tagen über den Aufräumjob).
- **Einsatzort:** Adressfeld mit Photon-Autocomplete (OSM, kostenlos, ohne
  Schlüssel) im Formular — auch für Uhr-Einsätze; Pin auf Einsatz- und
  Tageskarte.
- **Phasen-Marker:** Phasennummern an der GPS-Position auf dem Einsatz-Track
  (Kachel-Design, zoomfest, gestapelte versetzt), Umschalter unter der Karte;
  Hover-/Tipp-Kopplung in beide Richtungen zwischen Phasen-Tabelle und Karte.
- CSS-Fix: `hidden`-Attribut greift jetzt überall (u. a. Rollenfelder am
  Flugtag verschwinden korrekt).
- **Administration:** Klick auf eine NutzerIn öffnet die Editierseite (Rolle,
  E-Mail, neues Passwort, verbundene Geräte mit Aktivieren/Deaktivieren und
  Löschen). Admin-Geräteanlage ersatzlos entfernt (Selbstverwaltung genügt).
- **Geräte löschen ohne Datenverlust:** Löschen (mit Bestätigung, in
  Einstellungen → Geräte und auf der Admin-Editierseite) entfernt nur den
  Zugang — Einsätze und Tracks bleiben erhalten (Migration entkoppelt die
  Datenbank-Kaskade). Deaktivieren bleibt als sanfte Option.
- **Stammdaten** (Einstellungen → Stammdaten): Standorte, Hubschrauber mit
  Kennung und Rollen-Häkchen (Pilot 1/2, HEMS, Flugretter, Sonstige),
  Besatzungs-Vorbelegungen je Rolle, Bergwacht-Bereitschaften.
- **Flugtag mit Dropdowns:** Maschine und Standort aus den Stammdaten; die
  beim Hubschrauber angehakten Rollen erscheinen als Besatzungs-Dropdowns
  (gespeist aus den Vorbelegungen). Freitextfeld „Besatzung" entfällt; alte
  Freitext-Werte bleiben lesbar („alt"-Hinweis).
- **Web-Navigation neu:** Kopfleiste mit Vogel-Icon und „Einsatzdokumentation
  Luftrettung – Name" (Name im neuen Profil setzbar, sonst E-Mail); Menüs
  Übersicht / Administration / ⚙ Einstellungen (Profil, Geräte, Abmelden);
  „Verwaltung" heißt jetzt Administration. Geräte sind in die Einstellungen
  umgezogen (alte Adresse leitet weiter).
- **Profil:** Name und E-Mail änderbar; Passwortwechsel nur mit korrektem
  aktuellen Passwort (Migration: Namensfeld).
- Einsatztage-Leiste auf allen Inhaltsseiten (auch Einsatzansicht und
  Formular); Tagesklick öffnet die Übersicht des Tages. Einsatzansicht
  mittig. Fußzeile „© Gen-EM – OpenSource Software – AGPL-3.0" im
  Dokumentfluss rechts unter dem Inhalt, auch mobil.
- **Uhr-Paket:** Kartenmodus-Fix (Tasten werden im Browse-Modus ans System
  durchgereicht — Garmins Zoom/Verschieben erscheint); 2× Vibration nach
  „Dienst beginnen"; Rea-Gesamtdauer in Ziffernschrift (~50 % größer); neue
  **Statistik-Ansicht** (Einsätze/Alarmierungen des Tages); Hauptanzeige mit
  größerer, mittigerer Uhrzeit und Phase im unteren Drittel;
  **„Einsatztag beenden" sendet, bestätigt und schließt die App** — bei
  Sendeproblemen Rückfrage „Trotzdem beenden?" mit Warten-Option.
- Reanimation: Display bleibt während laufender Rea dauerhaft hell;
  Rea-Start vibriert 2×, Zyklusende 5× (statt 2×), Ereignis-Bestätigung
  kräftiger.
- Long-Press-Aktionen (Menüs, Adrenalin, Rhythmuskontrolle) feuern nach 1 s
  Halten sofort — nicht mehr erst beim Loslassen.
- **Einsatz-Abschluss statt Phase 10:** Nach Phase 9 „Einsatzende" bleibt der
  Einsatz offen; kurz START (oder grüner Menüpunkt) fragt „Einsatz beenden &
  senden?" — erst dann wird geschlossen und hochgeladen. Einsatzende/Dauer =
  Zeit der Phase 9. Migration löscht alte Phase-10-Zeitstempel und korrigiert
  Einsatzenden; Ingest und Formular akzeptieren nur noch Phasen 2–9.
- Uhr-Schnellmenü farbcodiert mit Endlos-Scrollen: Phasen 2–9,
  Einsatzübersicht (gelb), Einsatz abschließen (grün), Einsatztag beenden
  (rot); kurze Phasennamen auf der Uhr (Landung KKH, Übergabe, Einsatzende).
- Rea-Menü: neue Reihenfolge mit Rhythmuskontrolle (gelb, inkl.
  Countdown-Reset) und Adrenalin (pink) als Menüpunkte, „ENDE" statt „Rea
  beenden"; Direktkürzel lang UP/DOWN bleiben. „REA läuft" zusätzlich auf der
  Tempo-Seite.
- Server-URL in den Uhr-Einstellungen tolerant: „luftrettung.net" genügt.
- Uhr: Uhrzeit auf der Hauptanzeige deutlich größer, Phasenanzeige kleiner;
  Rea-Gesamtdauer größer; Kartenseite mit interaktivem Modus (kurz START =
  Garmins Zoom/Verschieben, BACK zurück zur Vorschau).
- Kosmetik-Paket Web: Einsatzformular mittig; Notizfeld im Eingabefeld-Stil;
  „Bearbeiten" als Button; Feldliste vertikal zentriert; „+ Phase hinzufügen"
  fokussiert das neue Dropdown; Fußzeile mit © Gen-EM und AGPL-3.0 auf allen
  Seiten.
- Navigation: Auf der Geräte-Seite tauschten „Geräte" und „Verwaltung" beim
  Klick die Plätze (abweichende Link-Reihenfolge).
- Migration „Mehrere Reanimationen": Ersatzindex vor dem Entfernen des
  UNIQUE (MySQL 1553); Runner überspringt bereits erledigte Einzelschritte.

### Installation
- `schema.sql` legt die Migrations-Buchführung (`schema_migrations`) an und
  trägt alle bisherigen Migrationen als erledigt ein — eine frische
  Installation ist sofort auf Endstand.
- Der Installer löschte beim Zurücksetzen nur neun alte Tabellen; die Liste
  wird jetzt aus `schema.sql` gelesen und bleibt automatisch vollständig.
- Neue Migration „Papierkorb" (`deleted_at`, `deleted_with_day`).


## [Uhr 1.3.6] — 2026-07-22

- **Einrichtung in der richtigen Reihenfolge (v1.3.6):** Fehlt die
  Server-Adresse, weist die Uhr jetzt darauf hin, sie in Garmin Connect
  einzutragen — vorher kam zuerst „Nicht gekoppelt", und der Kopplungsversuch
  scheiterte anschließend mit „Erst Server-Domain setzen". Neue Prüfung
  `Uploader.hasServer()`.
- Einstellungstexte neutral gefasst (Beispiel `einsatz.beispiel.de` statt der
  eigenen Domain) und der Hinweis ergänzt, dass Geräte-ID und API-Schlüssel
  beim Koppeln automatisch gesetzt werden.
- **Kartenseite entfernt (v1.3.5):** Sie funktionierte auf dem Gerät nicht
  zuverlässig und wurde vollständig aus dem Code genommen (`MapPage.mc`
  gelöscht, kein Rest im Pager). Der Pager läuft jetzt Uhr → Tempo →
  Statistik → Sync → Rea. Eine spätere Kartenansicht wird neu aufgebaut; die
  alte Fassung steckt bei Bedarf in der Git-Historie.
- **Neues Launcher-Icon (v1.3.4):** Hubschrauber-Bildmarke in 40x40, aus der
  hellen Fassung erzeugt — auf dem schwarzen App-Menü der Fenix bleibt damit
  die ganze Silhouette sichtbar (die farbige Fassung ist zur Hälfte dunkel und
  wäre dort halb verschwunden). Motiv mittig auf transparenter Fläche, also
  ohne Verzerrung.
- **Tastensperre öffnet nicht mehr das Schnellmenü (v1.3.3):** Kommt während
  des langen START-Drucks eine beliebige weitere Taste dazu, wertet die App das
  als Sperr-Kombination der Uhr — das Menü bleibt zu, und auch die Seitenwahl
  springt nicht an. Der lange Druck allein öffnet das Menü unverändert. Gleiche
  Absicherung in der Reanimations-Ansicht, wo langes UP/DOWN Adrenalin und
  Rhythmus markiert.
- **Rea-Menü neu:** groß umrahmte Felder (~4 je Seite, größere Schrift),
  Gruppen mit dünnen Trennlinien (Rhythmuskontrolle/Defibrillation ·
  Adrenalin/Amiodaron · **Zugang** [neues Ereignis]/Intubation/Sonographie ·
  ROSC/Tod · Übersicht), dicke Linie vor **„Rea BEENDEN"** (vorher „ENDE").
  Server und Doku kennen den Ereignistyp `zugang`.
- **Einsatzzähler:** Die Statistik zählt nur noch abgeschlossene Einsätze
  (Alarmierung + dokumentiertes Ende); der laufende zählt nicht mehr mit.
- **Sync-Seite:** Grün „Sync vollständig ✓", sobald kein Rückstand besteht —
  das konstruktionsbedingt immer offene laufende Ruhesegment zählt nicht mehr
  als „offenes Paket". Der Koppel-Hinweis erscheint nur noch ungekoppelt.
- App-Version 1.2.0 (Sync-Seite).
- **Geräte-Kopplung umgezogen:** Die Code-Eingabe liegt jetzt auf der
  Sync-/Versionsseite und startet mit **START gedrückt halten** (1 s) — die
  frühere „UP halten"-Geste auf dem Startbildschirm löste auf dem Gerät nicht
  zuverlässig aus. Der Startbildschirm zeigt ungekoppelt den Hinweis
  „Nicht gekoppelt — DOWN drücken"; die Kopplungs-Rückmeldung („Gekoppelt ✓")
  erscheint auf der Sync-Seite.
- **Absturz bei Ablauf des 2:00-Timers:** Das 5×-Vibrationsmuster überschritt
  Garmins Hardware-Limit von 8 Vibrationsprofilen. Muster jetzt gesplittet
  (3 + 2 Pulse); alle Vibrationsaufrufe zusätzlich abgesichert.
- **Karte:** Eigene Zoom-Steuerung statt des unzuverlässigen System-Browse-
  Modus — kurz START = Zoom-Modus, UP/DOWN zoomen um die Position, BACK
  zurück zum Track-Fit.
- **Sync-Diagnose:** Startbildschirm und Statistik-Seite zeigen den konkreten
  Fehlergrund („Keine Server-URL", „Zugangsdaten fehlen", HTTP-Codes) statt
  nur „Sync ausstehend".
- Statistik-Seite zeigt nur noch die Einsätze des Tages (Alarmierungs-Zähler
  entfernt); Zahl deutlich größer.
- Jeder Neustart des 2:00-Zyklus (Rhythmuskontrolle, manuell, Rea-Start)
  bestätigt mit 2× Vibration.


## [1.2] — 2026-07-18

### Hinzugefügt
- **Geräte-Selbstverwaltung** („Geräte"-Seite): NutzerInnen legen eigene Uhren
  an (Schlüssel einmalig sichtbar) und (de)aktivieren sie selbst.
- **Manuelle Einsätze:** Formular für Nachtragen und Bearbeiten
  (`einsatz_form.php`) mit dynamischen Phasenzeilen, Mitternachts-Logik und
  Zusatzfeldern; „+ Einsatz nachtragen" in der Tagesübersicht, „Bearbeiten"
  und „manuell"-Badge in der Einsatzübersicht.
- **Erweiterbare Zusatzfelder** über zentrale Definition
  (`mission_fields.php`); Startbestand: Einsatznummer, Notizen.
- Virtuelles, dauerhaft deaktiviertes Gerät „Manuelle Einträge" je NutzerIn
  als Träger von Handeinträgen.

### Geändert
- **Geräte werden deaktiviert statt gelöscht** — Upload-Schlüssel sofort
  gesperrt, alle Daten bleiben, Reaktivierung möglich; Löschen aus der
  Oberfläche entfernt. Ingest antwortet deaktivierten Geräten mit `403`.
- Manuell bearbeitete Einsätze sind vor Überschreiben durch Uhr-Uploads
  geschützt (`manual`-Marker); GPS-Punkte werden weiterhin ergänzt.
- Dokumentation neu strukturiert: Handbuch / Technik / Changelog;
  Anforderungskatalog als `archiv/Anforderungen_v1.2.md` eingefroren.

### Behoben
- **Datenverlust-Bug** in der Track-Persistenz: Teil-Chunks wurden nach einem
  Neustart mitten im Einsatz vom nächsten vollen Chunk überschrieben;
  zusätzlich konnten Upload-Lesezugriffe Punkte übersehen. Chunk-Ausrichtung
  jetzt garantiert, Tail-Lesen eindeutig.
- Reanimations-Timer überlebt App-/Uhren-Neustart (persistierter Zustand,
  epochenbasierte Fortsetzung).
- Einsatz-Kilometer werden bei Einsatzende eingefroren — verzögerte Uploads
  erhalten nicht mehr die Werte des Folgeeinsatzes.
- Ingest validiert `seq_from ≥ 0`.

## [1.1] — 2026-07-17

### Hinzugefügt
- **Tempo-Oberfläche** (aktuelle km/h + Einsatzdistanz), Seitenreihenfolge
  Uhr → Karte → Tempo → Rea.
- **Mehrere Reanimationen pro Einsatz:** „Rea beenden" (rot, mit Bestätigung)
  schließt eine Sitzung, erneuter START eröffnet die nächste; im Web je
  Sitzung eine eigene Tabelle. JSON-Vertrag v1.1 (`resus_sessions`).
- **Flugtag-Daten** in der Tagesübersicht: editierbare Felder Maschine,
  Basis/Standort, Besatzung, Notizen; Verknüpfung über (user_id, Datum).
- **Automatischer Aufräumjob** (max. 1×/Tag, ohne Cron): Trackpunkt-Waisen und
  alte Reset-Tokens.
- Web-Installer (`install.php`) mit Selbst-Sperre; Migrations-Runner
  (`update.php`) mit Buchführung; FTPS-Deploy per GitHub Actions;
  `.gitignore`.
- **GenEM-Branding**: Farbwelt, Bricolage Grotesque/Open Sans, Logo in
  Kopfleiste und Login, Favicon; Uhr-Launcher-Icon aus der Bildmarke.

### Geändert
- Schnellmenü der Hauptanzeige auf **lang START** verlegt (vorher lang UP).
- Rea-Untermenü selbst gezeichnet: farbcodierte Kacheln, Endlos-Scrollen,
  exakt zentrierte Beschriftung; Gesamt-Rea-Zeit lila; Bedien-Hinweistexte
  entfernt.
- Referrer-Policy auf `strict-origin-when-cross-origin` (OSM-Kacheln luden
  nicht).
- Lösch-Schutz der Uhr räumt Bestätigungsmarken mit auf.

### Behoben
- Monkey-C-Erstübersetzung: Modul-Callbacks über Träger-Klassen,
  `makeWebRequest`-Signatur, MapView-Pflichtaufrufe
  (`setScreenVisibleArea`, initiale Kartenfläche, keine Null-Flächen).

## [1.0] — 2026-07-16

### Hinzugefügt
- Erstes Gesamtsystem nach eingefrorener Spezifikation v1.0:
  - **Uhr-App** (Fenix 6 Pro): Dienst-Klammer („Dienst beginnen" /
    „Einsatztag beenden"), 10 Einsatzphasen mit Zeitstempeln und Position,
    Schnellmenü, Karten-Oberfläche mit Einsatz-Track (Anzeige-Cap 1000,
    Dichte-Halbierung), Reanimationsmodus (2:00-Zyklus, Vibration,
    Ereignis-Zeitstempel), GPS-Ausdünnung 15 m/10 s/max 1 s,
    Flash-Persistenz in Chunks, Offline-Puffer mit bestätigtem Löschen.
  - **JSON-Vertrag v1.0**: idempotente, inkrementelle Uploads
    (`client_ref`, `seq_from`/`next_seq`, 500-Punkte-Chunks).
  - **Web-App**: Login/Reset per Mail (eigener SMTPS-Client), Admin-Bereich
    (NutzerInnen, Geräte mit einmalig sichtbarem Schlüssel), Tagesübersicht
    mit Leaflet-Karte (Einsätze farbig, Ruhe-Track schwarz, Auto-Zoom ~75 %),
    Einsatzübersicht mit Phasen- und Rea-Tabelle.
