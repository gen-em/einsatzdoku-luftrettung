# Einsatzdoku — Handbuch

*Stand: 03.08.2026 · Für die technische Struktur siehe `Technik.md`, für
Änderungen `CHANGELOG.md`.*

## 1. Was ist die Einsatzdoku?

Die Einsatzdoku dokumentiert Hubschraubereinsätze direkt vom Handgelenk: Eine
Garmin-Uhr-App erfasst Einsatzphasen mit Zeitstempeln, GPS-Tracks und
Reanimations-Ereignisse und lädt alles automatisch auf einen eigenen Server.
Die Web-Oberfläche (luftrettung.net) zeigt Flugtage mit Karte, Einsatz-Details
und Reanimations-Protokollen — und erlaubt Nachtragen und Bearbeiten von Hand.

**Patientendaten sind geschützt.** Nachname, Vorname, Geburtsdatum, Alter,
Diagnose, der Einsatzort und seine Beschreibung werden
**Ende-zu-Ende-verschlüsselt** gespeichert:
Der Browser ver- und entschlüsselt sie mit einem Schlüssel aus deinem
Login-Passwort, der Server sieht nur Chiffretext (Abschnitt 5). Notizen und
Freitextfelder sind davon **nicht** erfasst — dort gehören keine
Patientendaten hinein.

---

## 2. Die Uhr-App

### 2.0 Unterstützte Uhren und ihre Bedienung

Die App läuft auf der **Fenix 6 Pro**, der **Forerunner 945** und der
**Venu 3s**. Fenix und Forerunner werden gleich bedient. Die Venu 3s hat nur
zwei Tasten, die Apps überhaupt erreichen — die mittlere ist von Garmin
belegt — und wird deshalb zusätzlich über den Touchscreen bedient:

| Auf Fenix / Forerunner | Auf der Venu 3s |
|---|---|
| kurz UP / DOWN | nach oben / unten wischen |
| kurz START | kurz Action (Taste oben rechts) |
| lang START | lang Action **oder** lang Zurück |
| lang UP / lang DOWN | nicht verfügbar — die Ereignisse liegen im Rea-Untermenü |
| BACK | kurz Zurück (Taste unten rechts) oder nach rechts wischen |

Der lange Druck liegt auf der Venu bewusst doppelt: Sollte die Uhr den langen
Druck der Action-Taste für ihr eigenes Steuerungsmenü abfangen, bleibt die App
über den langen Zurück-Druck vollständig bedienbar.

**Tippen auf den Bildschirm bewirkt auf den Hauptseiten nichts.** Das ist
Absicht — unter Einsatzbedingungen soll eine versehentliche Berührung nichts
auslösen. In Menüs kann ein Tippen den gerade markierten Eintrag auswählen.

Uhren mit Touchscreen **und** UP/DOWN-Tasten (Fenix 7 und neuer) werden noch
nicht ausgeliefert. Für sie gibt es in den App-Einstellungen bereits den
Schalter **„Touchbedienung verwenden"**; auf der Venu 3s hat er keine Wirkung,
weil sie ohne Touch unbedienbar wäre.

### 2.1 Dienst beginnen und beenden

Beim Öffnen der App erscheint **„Dienst beginnen?"**. Erst ein Druck auf
**START** aktiviert die App und die GPS-Aufzeichnung — vorher passiert nichts.
Der Flugtag läuft, bis du ihn über das Schnellmenü mit **„Einsatztag beenden"**
(Sicherheitsabfrage) schließt; dabei werden Restdaten hochgeladen. Das Datum
des Flugtags ist das Datum des Dienstbeginns — auch bei Diensten über
Mitternacht.

Ein Neustart der Uhr oder der App mitten im Dienst ist unkritisch: Phase,
Track und eine laufende Reanimation werden nahtlos fortgesetzt.

### 2.2 Die Oberflächen

Mit **kurz UP/DOWN** blätterst du im Kreis durch: **Uhr → Tempo → Statistik →
Sync → Reanimation**.

**Uhr (Hauptanzeige):** groß die Uhrzeit, darunter klein das Datum, darunter
die aktuelle Phase (Zahl + Name). Läuft eine Reanimation, umschließt ein roter
Ring die Anzeige — auf einen Blick erkennbar.

- **kurz START** schaltet zur nächsten Phase (mit Zeitstempel und Position):
  1 Frei → 2 Alarmierung (= Einsatzbeginn) → 3 Abflug → 4 Ankunft Einsatzort →
  5 Ankunft PatientIn → 6 Transportbeginn → 7 Landung Krankenhaus →
  8 Übergabezeit → 9 Endzeit → 10 Beendigung (= Einsatzende, zurück zu 1).
- **lang START** öffnet das **Schnellmenü**: eine Phase direkt anspringen
  (erneutes Setzen erzeugt einen *zusätzlichen* Zeitstempel — nichts wird
  überschrieben), „Einsatzübersicht Zeiten" (Liste aller Zeitstempel) und
  „Einsatztag beenden".
  Drückst du während des langen START-Drucks zusätzlich eine andere Taste,
  bleibt das Menü zu — die App erkennt daran die **Tastensperre** der Uhr. So
  kollidiert das Sperren nicht mehr mit dem Schnellmenü.
- **BACK** fragt nach, bevor die App verlassen wird.

**Tempo:** aktuelle Geschwindigkeit (km/h) groß, darunter die im Einsatz
zurückgelegten Kilometer.

**Statistik:** Kennzahlen des laufenden Dienstes.

**Sync:** Zeigt, ob alle abgeschlossenen Pakete beim Server angekommen sind
(grün „Sync vollständig" mit Haken) oder wie viele noch offen sind, bei
Problemen mit Fehlergrund. Darunter die **GPS-Güte**: „GPS gut" oder „GPS
ausreichend" (grün) heißt, dass Positionen aufgezeichnet werden; „GPS zu
schwach" (rot) bedeutet, dass die Uhr gerade keine Punkte speichert. Außerhalb
eines Dienstes steht dort „GPS aus". Unten die App-Version; mit **START
gedrückt halten** startest du hier die Geräte-Kopplung.

Ist noch keine Server-Adresse hinterlegt, weist die Uhr zuerst darauf hin, sie
in Garmin Connect einzutragen; erst danach erscheint der Kopplungshinweis.

**Reanimation:** siehe 2.3.

### 2.3 Reanimationsmodus

Zwei Timer: oben im schwarzen Kopfbalken die **Gesamtdauer** seit Rea-Beginn,
mittig groß der **2:00-Countdown** für den Zyklus, darunter ein
Fortschrittsbalken. Bei 0:00 vibriert die Uhr fünfmal kräftig; der Countdown
bleibt rot auf 0:00 stehen, bis er neu gestartet wird.

| Taste | Wirkung |
|---|---|
| kurz START, **keine** Rea läuft | Reanimation **beginnen** |
| kurz START, Rea **läuft** | **Untermenü** öffnen |
| lang START, Rea **läuft** | Countdown **neu starten** (2:00) |
| lang START, **keine** Rea läuft | ohne Funktion |
| lang UP | **Adrenalingabe** dokumentieren |
| lang DOWN | **Rhythmuskontrolle** dokumentieren (setzt Countdown auf 2:00) |
| kurz UP/DOWN | Oberfläche wechseln (Timer laufen weiter) |
| BACK | zurück zur Hauptanzeige (Timer laufen weiter) |

Der häufigste Griff während einer laufenden Reanimation ist das Dokumentieren
eines Ereignisses. Deshalb liegt das Untermenü auf dem **kurzen** Druck — der
lange Druck ist dem Countdown vorbehalten.

**Untermenü** (farbcodiert, endlos scrollbar, in derselben Darstellung wie das
Schnellmenü der Hauptanzeige) in dieser Reihenfolge:

| Eintrag | Wirkung |
|---|---|
| Timer neu starten | setzt den Countdown auf 2:00 — **ohne** Zeitstempel |
| Rhythmuskontrolle | Zeitstempel **und** Countdown-Neustart |
| Defibrillation | Zeitstempel **und** Countdown-Neustart |
| Adrenalin | Zeitstempel |
| Amiodaron | Zeitstempel |
| Zugang | Zeitstempel |
| Intubation | Zeitstempel |
| Sonographie | Zeitstempel |
| ROSC | Zeitstempel |
| Tod | Zeitstempel |
| Rea BEENDEN | hält die Reanimation an und öffnet die Übersicht (s. u.) |
| Übersicht | zeigt alle Zeiten der laufenden Rea |

Das Menü öffnet auf „Timer neu starten". Ein Schritt **nach oben** landet auf
„Übersicht", zwei Schritte auf „Rea BEENDEN".

**Reanimation beenden — zweistufig.** „Rea BEENDEN" schließt die Rea nicht
sofort, sondern **hält sie an** und öffnet die Übersicht. Ganz oben stehen dort
zwei Einträge:

- **Rea fortsetzen** — weiter mit frischem 2:00-Zyklus.
- **Rea beenden** — die Reanimation ist endgültig abgeschlossen.

So fällt die Entscheidung mit den dokumentierten Zeiten vor Augen, und ein
Vertippen schließt nichts mehr versehentlich. Triffst du keine Entscheidung
und gehst mit BACK zurück, bleibt die Rea **pausiert** — alle Seiten zeigen
das an, der rote Ring der Hauptanzeige wird gelb. Der Zustand übersteht auch
einen Neustart der Uhr.

Während der Pause steht der Countdown. Die **Gesamtdauer läuft weiter**: Sie
ist die tatsächlich verstrichene Reanimationszeit und würde sonst zu kurz
dokumentiert.

Nach dem Beenden startet **kurz START** eine *neue* Reanimation — mehrere pro
Einsatz sind möglich, jede bekommt im Web ihre eigene Tabelle. Bei Einsatzende
wird eine laufende oder pausierte Rea automatisch geschlossen.

### 2.4 Datenübertragung

Die Uhr lädt selbstständig hoch: Einsätze beim Abschluss des Einsatzes, den Ruhe-Track etwa
stündlich, den Rest beim Dienstende. Ohne Verbindung puffert die Uhr sicher im
Speicher und sendet später nach — gelöscht wird lokal erst, wenn der Server
den vollständigen Empfang bestätigt hat. Den aktuellen Stand zeigt die
**Sync-Seite**.

---

## 3. Die Web-Oberfläche — Überblick

Die Kopfleiste zeigt links die GenEM-Bildmarke mit „Einsatzdokumentation
Luftrettung – *Name*" (Name im Profil setzbar, sonst E-Mail), rechts die Menüs
**Übersicht**, **Suche** (Abschnitt 4.6) und **⚙ Einstellungen**. Hinter dem
Zahnrad liegen Profil, Standortdaten, Backup, Import / Export, Geräte und
Abmelden (fragt sicherheitshalber nach); Admins finden dort zusätzlich die
Rubrik **Administration** mit NutzerInnenverwaltung und Zentralen Stammdaten
(Abschnitt 11). Nach 30 Minuten ohne Aktivität meldet das System automatisch
ab. Die Kopfleiste bleibt beim Scrollen oben stehen.

Die **Einsatztage-Leiste** links begleitet alle Inhaltsseiten — auch
Einsatzansicht und Formular. Sie ist nach Jahr und Monat gruppiert
(Abschnitt 4.4).

### 3.1 Anmelden und Passwort

Anmeldung mit E-Mail-Adresse und Passwort. Über **„Passwort vergessen oder
erstmalig setzen"** kommt ein Link per E-Mail (1 Stunde gültig) — derselbe Weg
dient auch der Erst-Einrichtung nach dem Anlegen durch den Admin. Beim
Zurücksetzen wird zusätzlich der Wiederherstellungsschlüssel abgefragt
(Abschnitt 5); bei der Erst-Einrichtung entfällt das, weil noch keine
verschlüsselten Daten vorliegen.

**Es gilt immer nur der zuletzt verschickte Link.** Forderst du einen neuen an,
wird der vorherige damit ungültig. Nimm also die neueste E-Mail — eine ältere
führt zu „Link ungültig oder abgelaufen".

**Nach mehreren Fehlversuchen wird die Anmeldung vorübergehend gesperrt.** Die
Meldung nennt, ab wann es wieder geht. Die Sperre gilt für das Konto, nicht für
den Browser: Ein anderes Gerät oder ein neues Fenster hilft nicht. Sobald die
Anmeldung einmal gelingt, ist die Zählung zurückgesetzt.

Dasselbe gilt für „Passwort vergessen": Wer den Knopf zu oft drückt, bekommt
eine Zeit lang keine weitere E-Mail. Die Seite antwortet dabei unverändert —
sie verrät nie, ob es zu einer Adresse ein Konto gibt.

**Wenn eine Fehlermeldung eine Kennung nennt** — acht Zeichen aus Ziffern und
Buchstaben —, dann notiere sie. Der vollständige Fehlertext steht unter dieser
Kennung im Fehlerprotokoll des Webspace; ohne sie ist er dort nicht
wiederzufinden. Auf dem Bildschirm steht er bewusst nicht: Solche Texte nennen
Interna der Datenbank, die niemanden etwas angehen.

**Groß- und Kleinschreibung der E-Mail-Adresse spielt keine Rolle.**
`Max@Beispiel.de` und `max@beispiel.de` sind dasselbe Konto.

**Der Link aus der E-Mail braucht Cookies.** Beim ersten Öffnen nimmt die Seite
ihn aus der Adresszeile — er soll weder im Verlauf des Browsers noch in
Serverprotokollen stehen bleiben. Wer Cookies für die Seite blockiert, bekommt
statt der Passwortseite den Hinweis „Cookie nötig". Ein neuer Link hilft dann
nicht; die Einstellung muss geändert werden.

**Ein Passwortwechsel meldet alle anderen Sitzungen ab.** Wer sein Passwort
ändert, ist danach überall sonst ausgeloggt — auf dem zweiten Rechner, auf dem
Tablet, in einem anderen Browser. Am Gerät, an dem der Wechsel stattfindet,
bleibt man angemeldet. Wer den Verdacht hat, dass jemand anders Zugriff hat,
erreicht damit genau das Gewünschte. Noch offene Links zum Zurücksetzen werden
gleichzeitig ungültig.

Die abgemeldete Seite sagt, warum: „Das Passwort dieses Kontos wurde geändert."
Ebenso, wenn ein Konto von der Verwaltung gelöscht wurde — dann endet die
Sitzung beim nächsten Klick, nicht erst beim nächsten Anmelden.

**Schlägt ein Passwortwechsel fehl, ändert sich nichts.** Bis Web 4.5.1 konnte
ein abgelehnter Versuch — falsches aktuelles Passwort, zu lange offenes
Formular — dazu führen, dass die geschützten Angaben im selben Tab nicht mehr
lesbar waren, bis man sich neu anmeldete. Das ist behoben.

**Rollenänderungen wirken sofort.** Wird jemandem die Admin-Rolle gegeben oder
genommen, gilt das ab dem nächsten Klick; ein Ab- und Anmelden ist nicht nötig.

---

## 4. Einsätze ansehen und bearbeiten

### 4.1 Tagesübersicht

Startseite nach der Anmeldung. Links die Liste der Flugtage; der neueste ist
vorausgewählt. Pro Tag:

- **Flugtag-Daten** (aufklappbar): Maschine, Basis/Standort, Besatzung,
  Notizen — direkt editier- und speicherbar. Die Kopfzeile zeigt eine
  Kurzfassung.
- **Karte** mit allen Einsätzen des Tages (jeder in eigener Farbe, beginnend
  mit Orange/Blau/Rot) und dem Ruhe-Track in Schwarz. Die Einsatzort-Pins
  tragen die Farbe des jeweiligen Einsatzes. Die Karte zoomt automatisch so,
  dass alle Tracks sichtbar sind; Tracklinien werden beim Rauszoomen etwas
  dicker. Oben links lässt sich die Karte per Klick auf **Vollbild**
  stellen (erneuter Klick oder ESC verlässt den Vollbildmodus wieder), oben
  rechts zwischen **Standard-**, **Wander-** (mit Höhenlinien) und
  **Topo-Kartenlayer** umschalten — diese beiden Controls stehen auf allen
  drei Kartenseiten der Anwendung zur Verfügung.
- **Tabelle** der Einsätze: Nr., Beginn, Dauer, **Einsatzort** (Ortschaft aus
  der verschlüsselten Adresse), **Alter**, **Diagnose**, Winde, Bergwacht,
  Sekundärtransport, Kilometer. Alle Spalten sind zentriert und in
  abwechselnden Zeilenfarben; ein Klick auf eine Zeile öffnet den Einsatz, ein
  Klick auf einen Spaltenkopf sortiert. Die Dauer rechnet von der Alarmierung
  bis Phase 9; fehlt Phase 9, steht dort „kein Ende".
- **„+ Einsatz nachtragen"** öffnet das Eingabeformular für diesen Tag,
  **„Tag löschen"** entfernt den gesamten Flugtag (Abschnitt 8).

### 4.2 Einsatzansicht

Titel „Einsatz N · Uhrzeit" (N = Nummer des Tages nach Alarmierungszeit),
darunter **Bearbeiten** und **Löschen**. In der Kopfzeile darunter stehen
Datum, Zeitraum, Flugkilometer und am Ende genau ein **Herkunftskennzeichen**:

| Kennzeichen | Bedeutung |
|---|---|
| **Uhr** | Von der Garmin-Uhr aufgezeichnet |
| **manuell** | Von Hand nachgetragen (Abschnitt 4.5/4.3) |
| **importiert** | Über Import/Export eingespielt |

Wurde der Einsatz nach dem Anlegen verändert, erscheint zusätzlich das
Bearbeitungskennzeichen **„editiert"** — unabhängig von der Herkunft. Ein von
der Uhr aufgezeichneter, später bearbeiteter Einsatz zeigt also „Uhr" **und**
„editiert", nicht „manuell": „manuell" beschreibt ausschließlich, **wie** ein
Einsatz entstanden ist, „editiert" ob er danach verändert wurde.

Es folgt eine Karte mit dem Track
(Start grün, Ende rot) und — sofern vorhanden — dem Einsatzort-Pin aus den
lokal entschlüsselten Koordinaten. Auf dem Track sitzen an den
GPS-Positionen der Zeitstempel **Phasen-Nummern**, die standardmäßig
**ausgeblendet** sind — ein Control auf der Karte („Phasen anzeigen") blendet
sie ein, sofern mindestens eine Phase über GPS-Koordinaten verfügt; der
Zustand wird nicht gespeichert, nach einem Neuladen ist er wieder aus.
Zeigt man auf eine Phasenzeile oder einen eingeblendeten Kartenpunkt,
leuchtet das Gegenstück orange auf (am Handy: antippen).

Die geschützten Angaben — **Name, Geburtsdatum, Alter, Diagnose, Einsatzort,
Beschreibung Einsatzort** — erscheinen mit einem Schloss-Symbol 🔒 in der
Feldliste und **nur hier**, nicht
in den Übersichten. Ist aus dem GPS-Track eine Höhe am Patientenkontakt
ermittelbar, steht zusätzlich **„Höhe Einsatzort"** in der Feldliste.

Darunter folgt der Block **Besatzung**. Er zeigt die für **diesen Einsatz**
gültige Besatzung: normalerweise die Besatzung des Flugtags, bei einer
abweichenden Besatzung (Abschnitt 4.3) die abweichende Person. Geänderte
Rollen sind blau mit **„(abw.)"** gekennzeichnet, unveränderte stehen ohne
Zusatz daneben. Rollen ohne Eintrag werden weggelassen; ist gar keine Besatzung
hinterlegt, entfällt der Block ganz.

Es folgen die Phasen-Tabelle und je Reanimation eine eigene Zeiten-Tabelle.

### 4.3 Einsätze nachtragen und bearbeiten

Das Formular dient beidem. Phasen werden als Zeilen erfasst (Phase wählen,
Uhrzeit eintragen, Zeilen hinzufügen/entfernen — auch dieselbe Phase mehrfach).
**In chronologischer Reihenfolge eintragen**; Zeiten nach Mitternacht werden
automatisch dem Folgetag zugerechnet. Trägst du eine Zeile nachträglich mit
einer früheren Uhrzeit ein, sortiert sich die Liste nach dem Speichern von
selbst richtig ein.

**Strg-Enter** (bzw. Cmd-Enter auf macOS) sendet das Formular ab, ohne die
Maus zu benutzen — in Notizen bleibt einfaches Enter ein Zeilenumbruch.
Verlässt du die Seite mit ungespeicherten Änderungen, fragt der Browser vorher
nach; das gilt auch für die Flugtag-Formulare.

**Geschützte Angaben** (Abschnitt 5) stehen gebündelt unter „PatientInnendaten
& Einsatzort". Beim Geburtsdatum reicht auch eine zweistellige Jahreszahl
(z. B. „23.04.33") — die Anwendung ergänzt automatisch das plausible
Jahrhundert. Der Einsatzort hat ein Suchfeld: Ab drei Buchstaben erscheinen
Adressvorschläge (OpenStreetMap); die Auswahl eines Vorschlags speichert die
Koordinaten und setzt den Pin auf den Karten. Freitext ohne Vorschlag geht
auch — dann ohne Pin.

**Gespeicherte Koordinaten stehen unter dem Feld.** Sobald Koordinaten gesetzt
sind — egal ob über einen Adressvorschlag oder über eine der unten genannten
Eingabeformen —, erscheinen sie darunter als kleines Feld mit einem ✕ zum
Entfernen, genau wie bei den weiteren Rettungsmitteln. Das Textfeld bleibt
davon unberührt: Du kannst dort weiterschreiben, ohne die Koordinaten zu
verlieren.

**Solange Koordinaten gesetzt sind, sucht das Feld nicht mehr.** Es ist dann
reines Bezeichnungsfeld: keine Adressvorschläge, keine Erkennung weiterer
Koordinatenformate. Andernfalls würde ein Klick auf einen Vorschlag die
bestätigten Koordinaten stillschweigend überschreiben. Entfernst du sie über
das ✕, arbeitet die Suche ab dem nächsten Tastenanschlag wieder wie gewohnt.

Alternativ zur Adresse erkennt das Feld beim Tippen auch vier weitere
Formate — die Umwandlung erfolgt lokal im Browser, es wird dabei keine
Anfrage an einen externen Server gestellt. Wie bei einer Adresse erscheint
dann ein Eintrag in der Vorschlagsliste (z. B. „Koordinaten übernehmen
(Dezimalgrad): 47.72610, 10.31700"); erst mit dessen Auswahl werden
Koordinaten und Pin übernommen. **Das Textfeld wird dabei geleert** — es
gehört ab dann der Bezeichnung, die du selbst einträgst (z. B. „Talstation
Nebelhorn", „Wanderweg 401, Ostrachtal"). Ohne diese Bezeichnung lässt sich
der Einsatz nicht speichern; in den Listen stünde sonst nur eine Zahlenreihe
statt eines Ortsnamens. Bei einem Adressvorschlag bleibt es beim gewohnten
Verhalten: Das Label steht im Feld und gilt als Bezeichnung.

Die vier Formate:
- **Dezimalgrad**, z. B. `47.7261, 10.3170`
- **Grad/Dezimalminuten**, z. B. `47°43.57'N 010°19.02'E`
- **Grad/Minuten/Sekunden**, z. B. `47°39'11.6"N 10°21'34.3"E`
- **Plus Code** (Open Location Code), aber nur als **Vollcode**,
  z. B. `8FWH4HJM+7Q` — Kurzformen (z. B. `4HJM+7Q Kempten`) werden
  erkannt, aber nicht als Vorschlag angeboten; die Statuszeile weist dann
  darauf hin, den Vollcode einzugeben (in der Karten-App ohne Ortsangabe
  kopieren). Werte außerhalb des gültigen Bereichs (z. B. eine Breite über
  90°) werden ebenso als ungültig gemeldet statt als Vorschlag angeboten.

Direkt darunter steht **Beschreibung Einsatzort** (Zufahrt, Besonderheiten,
Lage vor Ort). Das Feld gehört seit Web 3.3.0 zum verschlüsselten Block: Bei
gesperrter Verschlüsselung ist es deaktiviert und bleibt beim Speichern
unverändert, und die Suche findet seinen Inhalt erst nach dem Entsperren.
Ausfüllen ist freiwillig.



Dazu die weiteren Zusatzfelder: Transportziel,
**Windeneinsatz** (Haken öffnet Cycles,
Cycles mit Patient, Luftverladung), **Bergwacht** (Haken öffnet Bereitschaft
aus den Stammdaten plus Namen/Infos), Sekundärtransport, Schockraum, Anderer
Notarzt, **Weitere Rettungsmittel** (Abschnitt 9.2) und Notizen.

**Abweichende Besatzung.** Normalerweise gilt für jeden Einsatz die Besatzung
des Flugtags — sie wird einmal am Tag eingetragen und muss am Einsatz nicht
wiederholt werden. Wechselt jedoch während des Dienstes jemand (typisch: ein
Pilotenwechsel am Nachmittag), setzt du am betroffenen Einsatz den Haken
**„Abweichende Besatzung"**. Darunter erscheinen die Auswahlfelder, gefüllt aus
deinen Besatzungs-Vorbelegungen und den zentralen Stammdaten (Abschnitt 9.1
bzw. 8.4).

Gezeigt werden **nur die Rollen, die der Hubschrauber des Flugtags vorsieht** —
dieselben Häkchen, nach denen sich auch das Flugtag-Formular richtet. Fliegt
die Maschine mit Pilot 1 und HEMS-TC, erscheinen auch nur diese beiden. Ist am
Flugtag noch kein Hubschrauber eingetragen, werden alle fünf gezeigt. Steht in
einer eigentlich nicht vorgesehenen Rolle bereits ein Eintrag — etwa weil der
Flugtag nachträglich auf eine andere Maschine umgestellt wurde —, bleibt sie
sichtbar, damit du sie weiterhin ändern kannst.

Es müssen **nur die tatsächlich abweichenden Rollen** ausgefüllt werden. Alle
übrigen bleiben leer und werden weiterhin vom Flugtag übernommen — so steht
dieselbe Person nie doppelt in der Datenbank. Entfernst du den Haken wieder,
werden die fünf Felder geleert und der Einsatz erbt vollständig die Tagescrew.
In der Einsatzansicht (Abschnitt 4.2) zeigt der Block „Besatzung" immer das
Ergebnis beider Ebenen.

Ist eine früher eingetragene Person inzwischen aus den Stammdaten entfernt
worden, bleibt ihr Name im Auswahlfeld trotzdem stehen und geht beim nächsten
Speichern nicht verloren.

Beim Bearbeiten eines **Uhr-Einsatzes** gilt: Nach dem Speichern überschreibt
die Uhr ihn nicht mehr (nur der GPS-Track wird weiter ergänzt), und die
Einsatzansicht zeigt zusätzlich zum Herkunftskennzeichen „Uhr" das
Bearbeitungskennzeichen „editiert" (Details siehe Abschnitt 4.2). Das
Formular weist vorher darauf hin. Reanimations-Zeiten lassen sich im
Formular derzeit nicht erfassen.

Nach dem **Neuanlegen** eines Einsatzes zeigt die Einsatzansicht den Button
„Weiteren Einsatz nachtragen" — er öffnet die Neuanlage direkt für denselben
Flugtag. Beim Bearbeiten eines bestehenden Einsatzes erscheint er nicht.

### 4.4 Einsatztage-Leiste, Jahres- und Monatsübersicht

Die Leiste links ist nach **Jahr → Monat → Tage** gruppiert. Es ist immer nur
ein Jahr geöffnet und darin ein Monat (standardmäßig der jüngste); ein anderes
Jahr anzuklicken schließt das vorherige automatisch. Springst du auf einen Tag
in einem anderen Zeitraum, klappt die Leiste automatisch dorthin auf.

Ein Klick auf die **Jahreszahl** oder den **Monatsnamen** öffnet eine Übersicht
dieses Zeitraums: eine Karte mit einem Pin je Einsatzort (sofern Koordinaten
vorhanden und die geschützten Angaben entsperrt sind), darunter zehn
Statistik-Kacheln — Einsätze, Flugtage, Ø Einsätze/Flugtag, Anzahl
Winden-Cycles, Ø Winden-Cycles/Flugtag, Sekundärtransporte, Flugkilometer
gesamt, längste Flugstrecke, längste Einsatzdauer, höchster Einsatzort — und
schließlich die Tabelle aller Einsätze mit Datum statt Tagesnummer,
sortierbar. Die Durchschnittswerte der Statistik rechnen mit **allen
angelegten Flugtagen** des Zeitraums, auch mit einsatzfreien.

Die drei Kacheln **„Längste Flugstrecke"**, **„Längste Einsatzdauer"** und
**„Höchster Einsatzort"** sind interaktiv: Zeigt man darauf, leuchten der
zugehörige Karten-Pin (rot) und die zugehörige Tabellenzeile (rosa) auf. Ein
Klick fixiert diese Hervorhebung und springt zur Tabellenzeile — praktisch,
um den Extremwert-Einsatz auf einen Blick zu finden. Ein zweiter Klick auf
dieselbe Kachel oder ein Klick auf eine freie Stelle der Seite löst die
Fixierung wieder.

Jede Zeile der Einsatztabelle führt zum Einsatz; ein Klick auf das
**Dreieck** davor klappt dagegen nur die Unterpunkte auf oder zu.

**Zeitraum-Übersicht oder Suche?** Die beiden Seiten zeigen dieselbe
Einsatztabelle, beantworten aber verschiedene Fragen. Die Zeitraum-Übersicht
ist auf *einen* Monat oder *ein* Jahr festgelegt und liefert dafür Karte und
Kennzahlen — sie beantwortet „wie war dieser Zeitraum?". Die **Suche**
(Abschnitt 4.6) geht über den gesamten Bestand, kennt rund 30 Filter bis hin zu
Diagnose, Besatzung und Alter, hat dafür aber weder Karte noch Kennzahlen — sie
beantwortet „wo war nochmal der eine Einsatz mit …?". Ein Zeitraum lässt sich
in der Suche über „Datum von / bis" nachbilden; Kennzahlen dazu gibt es aber
nur in der Zeitraum-Übersicht.

### 4.5 Flugtag von Hand anlegen

Lief die Uhr an einem Tag nicht, legst du den Flugtag über **+ Flugtag
anlegen** unten in der Einsatztage-Leiste an. Danach lassen sich Maschine,
Besatzung und nachgetragene Einsätze wie gewohnt erfassen.

### 4.6 Suche

Über **Suche** in der Kopfleiste durchsuchst du deinen gesamten Bestand — nicht
nur einen Tag oder einen Zeitraum. Die Trefferliste hat dieselben Spalten wie
die Zeitraum-Übersicht, lässt sich genauso über die Spaltenköpfe sortieren, und
ein Klick auf eine Zeile öffnet den Einsatz.

**Suchbegriff.** Das obere Feld durchsucht Einsatznummer, Name, Geburtsdatum,
Diagnose, Einsatzort, Transportziel, Beschreibung des Einsatzorts,
Bergwacht-Bereitschaft und -Infos, anderen Notarzt, weitere Rettungsmittel,
Standort, Maschine, Besatzung und Notizen. Groß- und Kleinschreibung spielt
keine Rolle, Wortteile genügen. Gibst du mehrere Wörter ein, müssen **alle**
vorkommen — aber nicht im selben Feld. „müller kempten" findet also auch einen
Einsatz, bei dem Müller die Besatzung und Kempten das Transportziel ist. Das
Geburtsdatum findest du in beiden Schreibweisen, „12.03.1985" ebenso wie
„1985-03-12".

**Weitere Filter.** In der linken Spalte — dort, wo auf den anderen Seiten die
Einsatztage stehen. Auf der Suchseite gibt es die nicht, weil es hier gerade um
den Gesamtbestand geht. Die Filter liegen in vier Blöcken: **Zeit**, **Art des
Einsatzes**, **Beteiligte und Ziel**, **Werte**. Jeder Block klappt einzeln auf
und zu; beim Öffnen der Seite sind alle zugeklappt, damit die Spalte ruhig
bleibt. Öffnest du einen geteilten Link, gehen genau die Blöcke auf, in denen
etwas gesetzt ist. Alle gesetzten Filter gelten gleichzeitig (UND); leere
Felder schränken nichts ein. Die Auswahllisten für Standort,
Maschine, Besatzung, Bergwacht-Bereitschaft, Rettungsmittel und Transportziel
enthalten nur, was in deinem Bestand tatsächlich vorkommt. Zwei Besonderheiten:

- **Alarmzeit** darf über Mitternacht gehen. „von 22:00 bis 06:00" findet die
  Nachteinsätze.
- **Reanimations-Ereignis** ist eine Mehrfachauswahl. Wählst du mehrere, muss
  der Einsatz alle davon enthalten.

Unten in der Filterspalte steht **Filter zurücksetzen** und darunter, wie viele
Filter gerade gesetzt sind. Über der Trefferliste steht, wie viele Einsätze von
wie vielen angezeigt werden.

**Gesperrte Verschlüsselung.** Sind die geschützten Angaben gesperrt
(Abschnitt 5), werden Einsatznummer, Name, Geburtsdatum, Diagnose, Einsatzort
und dessen Beschreibung nicht durchsucht, der Altersfilter ist abgeschaltet und die
entsprechenden Spalten bleiben leer. Alle übrigen Filter arbeiten normal
weiter. Über **Entsperren** im Hinweis oben nimmst du die Sperre auf, danach
sucht die Seite sofort mit den vollständigen Daten weiter — ohne Neuladen.

**Suche teilen oder aufheben.** Der komplette Filterzustand steht in der
Adresszeile hinter dem `#`. Du kannst die Adresse als Lesezeichen speichern
oder weitergeben; beim Öffnen sind dieselben Filter wieder gesetzt. Weil alles
hinter dem `#` steht, wird der Suchbegriff **nicht** an den Server übertragen
und taucht in keinem Server-Protokoll auf. Beim Weitergeben lohnt trotzdem ein
Blick: Ein Suchbegriff wie ein Nachname ist selbst ein Patientendatum, und die
empfangende Person sieht ihn in ihrer Adresszeile — die Treffer allerdings nur,
soweit sie ohnehin Zugriff auf die eigenen Daten hat. Fremde Einsätze werden
nie angezeigt.

**Wo gesucht wird.** Die Suche läuft vollständig in deinem Browser. Beim ersten
Öffnen holt die Seite deinen Bestand einmal vom Server; danach kostet kein
Tastendruck mehr eine Anfrage. Das ist keine Spielerei, sondern Bedingung: Die
geschützten Angaben liegen Ende-zu-Ende-verschlüsselt auf dem Server, er könnte
gar nicht darin suchen.

---

## 5. Verschlüsselung der Patientendaten (Pflicht)

Nachname, Vorname, Geburtsdatum, Alter, Diagnose, Einsatzort, die Beschreibung
des Einsatzortes und die Einsatznummer sind **Ende-zu-Ende-verschlüsselt**: Der Browser ver- und
entschlüsselt mit einem Schlüssel aus deinem Login-Passwort; der Server
speichert nur Chiffretext. Es
gibt kein zweites Passwort und keinen Schalter — die Verschlüsselung ist
Pflicht.

**Ersteinrichtung:** Sie passiert direkt beim Festlegen des Passworts. Wenn du
über den Einladungslink dein Passwort vergibst, erzeugt der Browser im selben
Schritt deinen **Wiederherstellungsschlüssel** und zeigt ihn **nur dieses eine
Mal** an — ausdrucken und sicher ablegen, dann per Haken bestätigen. Erst danach
wird gespeichert. Eine getrennte Einrichtungsseite nach dem ersten Anmelden gibt
es nicht mehr.

**Unbedingt wissen:**

- Normales Passwort-Ändern (mit altem Passwort) ist völlig unkritisch — die
  Daten bleiben ohne Zutun lesbar.
- Bei „Passwort vergessen“ verlangt die Seite mit dem neuen Passwort zugleich
  den **Wiederherstellungsschlüssel**. Damit übernimmt der Browser die
  verschlüsselten Angaben auf das neue Passwort, sodass nach dem Zurücksetzen
  sofort alles lesbar ist. Passt der Schlüssel nicht, wird **nichts** geändert
  — das alte Passwort gilt weiter. **Ohne den Schlüssel sind die Angaben
  unwiederbringlich verloren**, auch Admins können nicht helfen (deshalb gibt
  es keine Admin-Passwortvergabe).
- **Die Anmeldung kann nach einem Update eine Zeitlang länger dauern.** Wenn
  die Einstellungen der Verschlüsselung angehoben werden, rechnet der Browser
  eine Übergangszeit lang zweimal — er weiß noch nicht, welche Einstellung für
  dein Konto gilt, und der Server darf es ihm vor der Anmeldung nicht verraten.
  Sobald alle Konten nachgezogen sind, ist es wieder wie vorher. Es ist nichts
  kaputt.
- **Beim Abtippen hilft die Seite mit.** Unter dem Eingabefeld steht sofort,
  wenn etwas nicht stimmt: ein Zeichen, das im Schlüssel gar nicht vorkommt,
  oder eine unvollständige Länge. Die Zeichen **0, 1, I, L, O und U werden
  nicht verwendet** — genau weil sie beim Ablesen zu leicht zu verwechseln
  sind. Bindestriche sind eine Lesehilfe und dürfen weggelassen werden,
  Groß- und Kleinschreibung spielt keine Rolle.
  Ist der Schlüssel vollständig und passt trotzdem nicht, sagt die Meldung das
  ausdrücklich: Dann liegt kein Tippfehler vor, sondern es ist der Schlüssel
  eines anderen Kontos oder aus einer früheren Einrichtung.
- Verschlüsselte Felder sind serverseitig nicht durchsuchbar; der Schutz wirkt
  gegen Datenbank-Diebstahl und Mitleser, prinzipbedingt nicht gegen einen
  vollständig übernommenen Server.
- Zeigt eine Seite „gesperrt“, lässt sich das direkt dort beheben — siehe
  **„Gesperrt: entsperren statt neu anmelden“** weiter unten.

**Gesperrt: entsperren statt neu anmelden.** Die Anmeldung und der Schlüssel
für die geschützten Angaben haben unterschiedliche Lebensdauern. Der Schlüssel
gilt nur im jeweiligen Browser-Tab; die Anmeldung dagegen hält bis zu 30 Minuten
ohne Aktivität. Deshalb kommt es im Alltag regelmäßig vor, dass du angemeldet
bist, die geschützten Angaben aber gesperrt sind — typischerweise, wenn du einen
Link in einem **neuen Tab** öffnest oder den **Browser neu gestartet** hast.

In diesem Fall erscheint ein Fenster **„Geschützte Angaben entsperren“**, das
nach deinem Kontopasswort fragt. Nach der Eingabe sind Einsatznummer, Name,
Geburtsdatum, Alter, Diagnose und Einsatzort sofort wieder sichtbar — ohne die
Seite neu zu laden und ohne Ab- und Neuanmelden. Das Passwort wird dabei nur in
deinem Browser verwendet; es wird **nicht** an den Server geschickt. Die
Prüfung dauert je nach Gerät eine knappe Sekunde, solange steht „Schlüssel wird
abgeleitet …“.

Brichst du das Fenster ab, bleibt die Seite normal bedienbar — nur die
geschützten Angaben bleiben verborgen, und ein Hinweis sagt das. Der Knopf
**„Entsperren“** in diesem Hinweis öffnet das Fenster jederzeit erneut.

Zwei Dinge sind dabei wichtig:

- **Im Einsatzformular werden vorhandene verschlüsselte Angaben beim Speichern
  nicht angetastet, solange gesperrt ist.** Du kannst also einen Einsatz auch
  im gesperrten Zustand bearbeiten, ohne Patientendaten zu verlieren.
- **Import und Export der Patientendaten brauchen den Schlüssel.** Ohne ihn ist
  der Import gesperrt und der Export nur ohne Patientendaten möglich.

**Alter aus Geburtsdatum:** Das Alter berechnet die Anwendung aus dem
Geburtsdatum, bezogen auf den **Einsatztag**, nicht auf heute — ein Einsatz von
vor Jahren zeigt weiterhin das damalige Alter. Bei gesetztem Geburtsdatum ist
das Feld gesperrt und mit „aus Geburtsdatum" gekennzeichnet. Ist kein
Geburtsdatum bekannt (bei unbekannten Personen der Regelfall), bleibt das Alter
von Hand eintragbar. **Name, Geburtsdatum und Einsatznummer erscheinen
bewusst nur in der Einsatzansicht bzw. im Formular**, nie in den Übersichten.

In den Exporten schlägt sich das unterschiedlich nieder: **Excel (Standard)**
zeigt in der Spalte „Alter" immer den Wert, den auch die Einsatzansicht anzeigt
— gerechnet oder von Hand eingetragen. Das **CSV** führt daneben die Spalte
`pat_alter`, die nur das von Hand eingetragene Alter enthält und bei einem
Einsatz mit Geburtsdatum leer bleibt. So steht jede Angabe genau einmal in der
Datei und kann nicht auseinanderlaufen.

---

## 6. Backup

Unter **⚙ Einstellungen → „Backup"** lädst du alle deine Daten als einzelne
verschlüsselte Datei (`.edbak`) herunter — Passwort frei wählbar, mindestens
10 Zeichen, wird nirgends gespeichert. In dieser Datei stehen **alle
geschützten Angaben im Klartext**; zwischen ihnen und jedem, der die Datei in
die Hand bekommt, steht nur dieses Passwort.

Ver- und Entschlüsselung passieren **in deinem Browser**; der Server sieht die
Inhalte nie. Deshalb lässt sich ein Backup auch **in ein anderes Konto**
einspielen: Beim Import werden die geschützten Angaben automatisch mit dem
Schlüssel des Zielkontos neu verschlüsselt.

Der Import ergänzt nur, was fehlt — Vorhandenes bleibt unangetastet, und
mehrfaches Einspielen derselben Datei ist gefahrlos. Während Export und Import
zeigt eine Statuszeile den Fortschritt und am Ende die Zahl der übernommenen
Einsätze, Ruhesegmente und Flugtage.

**Das Backup-Passwort.** Mindestens zehn Zeichen, und die Seite sagt während
der Eingabe, wie stark das Gewählte ist. Wer mag, setzt stattdessen das Häkchen
**„Mein Kontopasswort verwenden“** und tippt sein Anmeldepasswort ein — dann
gibt es ein Passwort weniger zu verwahren, und die Datei ist genauso geschützt
wie die Daten in der Datenbank. Ob das Passwort stimmt, prüft der Browser
selbst; der Server bekommt es nicht zu sehen.

Nicht geeignet ist das Kontopasswort, wenn die Datei an jemand anderen gehen
soll — dann bekommt der Empfänger das Anmeldepasswort mit.

**Sicherungsdateien bleiben lesbar, auch nach einem Update.** In der Datei steht
seit 5.0.0 vermerkt, mit welchen Einstellungen sie verschlüsselt wurde. Ältere
Dateien lassen sich unverändert öffnen. Kommt eine Datei aus einer *neueren*
Fassung des Programms, sagt die Meldung genau das — und nicht „Passwort
falsch".

**Der Hinweis auf ein neu verbundenes Gerät lässt sich bestätigen.** Auf der
Tagesübersicht erscheint nach dem Koppeln einer Uhr ein Hinweis mit Name und
Zeitpunkt. Mit „Verstanden, das war ich" verschwindet er. Wird danach ein
weiteres Gerät verbunden, erscheint er erneut.

**Woher die Datei stammt, steht dabei.** Sobald die Sicherung geöffnet ist —
also nach Eingabe des Backup-Passworts —, nennt eine Zeile das Konto und den
Zeitpunkt, zu dem sie erstellt wurde. Stammt sie aus einem anderen Konto als
dem angemeldeten, steht das ausdrücklich da. Ein Abbruch ist das nicht: Eine
Sicherung in ein anderes Konto einzuspielen ist vorgesehen. Die Angabe ist
dafür da, die richtige Datei von einer ähnlich benannten zu unterscheiden.

Öffnet sich eine Sicherung nicht und die Meldung nennt den Browser, liegt es
weder an der Datei noch am Passwort: Sehr alte Browser können gepackte
Sicherungen nicht entpacken. Ein aktueller Browser öffnet dieselbe Datei ohne
Weiteres. (Bis Web 4.5.1 stand in diesem Fall „Passwort falsch oder Datei
beschädigt" — was beides nicht stimmte.)

Der Aufbau der Datei ist in `docs/Backup-Format.md` vollständig beschrieben —
sie lässt sich damit auch ohne dieses Programm entschlüsseln.

---

## 7. Import und Export

Unter **Einstellungen → Import / Export** lässt sich eine vorhandene
Einsatzliste (Excel oder CSV) übernehmen — etwa eine über Jahre gepflegte
Jahresliste.

Die Datei wird **nicht hochgeladen**. Sie wird in deinem Browser gelesen,
geprüft und dort verschlüsselt; der Server bekommt Name, Geburtsdatum,
Diagnose, Einsatzort und Einsatznummer nur als Chiffretext zu sehen. Das ist keine
Bequemlichkeit, sondern die einzige Möglichkeit, die die
Ende-zu-Ende-Verschlüsselung (Abschnitt 5) offen lässt. Aus demselben Grund
ist der Import gesperrt, solange die Verschlüsselung nicht bereitsteht — dann
hilft der Knopf „Entsperren“ im Hinweis über dem Importbereich
(siehe Abschnitt 5).

**1. Datei wählen.** Das passende Format wird an den Spaltenüberschriften
selbst erkannt. Angaben, die in der Datei fehlen, werden darüber abgefragt —
bei der Christoph-17-Jahresliste ist das die Jahreszahl, weil die Datumsspalte
nur „14.3." enthält. Vorgeschlagen wird das Jahr aus der Titelzeile; du kannst
es überschreiben. Außerdem wählst du Hubschrauber und Basis für Flugtage, die
neu angelegt werden — bestehende Tage bleiben davon unberührt, und beides
lässt sich später je Tag in der Tagesübersicht ändern.

**2. Prüfen und korrigieren.** Die Tabelle zeigt jede Zeile der Datei, nach
Flugtagen gruppiert. **Gelb** ist ein Hinweis (die Zeile geht durch, sieh sie
dir aber an), **Rot** ein Fehler. Jede Zelle ist direkt änderbar; nach jeder
Änderung wird sofort neu geprüft. Fehlerhafte Zeilen blockieren nur sich
selbst: Entweder du korrigierst sie oder du hakst „überspringen" an. Solange
eine Fehlerzeile weder korrigiert noch übersprungen ist, bleibt der Import
gesperrt.

Zwei Sonderfälle werden dabei erkannt:

- **Dubletten.** Ein Einsatz, dessen Einsatznummer schon vergeben ist oder für
  den es an diesem Tag bereits einen Einsatz zur selben Alarmzeit gibt. Der
  Abgleich über die Einsatznummer erkennt seit Web 2.9.0 nur noch Dubletten
  **innerhalb der Flugtage, die in der Importdatei vorkommen** — die Nummer
  liegt verschlüsselt vor und wird dafür lokal in deinem Browser mit den
  vorhandenen Einsätzen abgeglichen. Tag und Alarmzeit bleiben unabhängig
  davon wirksam. Du wählst je Zeile: überspringen (Voreinstellung),
  überschreiben oder trotzdem anlegen. Gelöschte Einsätze im Papierkorb
  zählen bewusst nicht als vorhanden.
- **Abweichende Besatzung.** Als Besatzung des Flugtags gilt die des ersten
  Einsatzes des Tages. Steht bei einem späteren Einsatz jemand anderes — der
  klassische Pilotenwechsel im laufenden Dienst —, trägt dieser Einsatz
  automatisch eine abweichende Besatzung (Abschnitt 4.3). Gibt es den Flugtag
  schon mit einer anderen Besatzung, entscheidest du je Tag, ob die
  gespeicherte gilt oder die aus der Datei.

**3. Übernehmen.** Der Import läuft als Ganzes: Entweder alle Zeilen werden
übernommen oder — falls unterwegs etwas schiefgeht — keine einzige. Am Ende
steht, wie viele Einsätze angelegt, überschrieben und übersprungen wurden,
mit einem Link auf den ersten importierten Tag.

Importierte Einsätze verhalten sich wie von Hand nachgetragene: Sie lassen
sich normal öffnen und bearbeiten, und die Uhr überschreibt sie nicht. Da eine
Liste in aller Regel nur die Alarmzeit kennt, tragen sie genau eine Phase
(Alarmierung); Track, Flugzeiten und weitere Phasen fehlen naturgemäß. Der
Rückimport der eigenen Exportformate ist da genauer — siehe „Zurücklesen" unten.

### 7.1 Export

Auf derselben Seite, unterhalb des Importbereichs, steht der **Export**. Er ist
zum Weiterverarbeiten in anderen Programmen gedacht — **nicht als Backup**. Für
eine vollständige Sicherung gibt es Abschnitt 6.

Wie beim Import passiert alles im Browser: Der Server liefert nur Rohdaten, die
geschützten Angaben werden erst auf deinem Rechner entschlüsselt. Ohne den Haken
„Patientendaten einschließen" schickt der Server sie gar nicht erst mit.

Zu wählen sind Zeitraum (Von–Bis oder Alles) und Format.

**Ein Zeitraum braucht beide Grenzen.** Wer nur „Von" ausfüllt und „Bis" leer
lässt, bekommt seit 4.5.1 eine Rückfrage. Vorher wurde die halbe Angabe
stillschweigend übergangen und **der gesamte Bestand** ausgeleitet — ohne
Hinweis, nur mit einer größeren Datei als erwartet. Beide Felder leer heißt
weiterhin „alles"; das ist eine bewusste Angabe.


- **CSV (Standard)**: ein Archiv mit allen Feldern, die das System kennt, dazu
  Flugtage, Ruhezeiten, eine Feldbeschreibung und auf Wunsch die GPX-Tracks. Das
  ist das Format für Auswertungen und für den Rückweg. Es führt je Einsatz auch
  die **Herkunft** (Uhr, manuell, importiert) und den **Bearbeitungsstatus** mit
  — dieselben beiden Angaben, die in der Einsatzansicht als Kennzeichen stehen
  (Abschnitt 4.2). Die beiden Exceltabellen führen sie nicht.
- **Excel (Standard)**: eine Zeile je Einsatz, deutsche Spaltenbeschriftungen,
  alle Zeiten in Ortszeit. Zum Ansehen, Sortieren und Filtern. Ein Flugtag ohne
  Einsatz erscheint als eine Zeile mit Datum und lauter Bindestrichen.
- **Excel (GuteSeele)**: das gewohnte Listenlayout zur Weitergabe an Dritte. Bei
  mehreren Jahren entsteht je Jahr ein Blatt.

Die Namen sind dieselben wie im Auswahlfeld des Imports — was hier
herausgeschrieben wird, lässt sich dort unter demselben Namen wieder
einlesen.

**Patientendaten einschließen** ist standardmäßig aus. Wird es gesetzt, kommt
vorher ein Hinweis: Ab dem Speichern schützt die Verschlüsselung dieser
Anwendung die Daten nicht mehr — Name, Geburtsdatum, Diagnose und Einsatzort
stehen dann lesbar in der Datei. Ist die Verschlüsselung gerade gesperrt (nach
einem Neustart des Browsers), lässt sich der Haken nicht setzen; ein Export ohne
Patientendaten bleibt möglich. Über „Entsperren“ im Hinweis daneben lässt sich
die Sperre aufheben (siehe Abschnitt 5).

**Wenn sich Angaben nicht entschlüsseln lassen, fragt der Export nach.** Passt
der Schlüssel für einzelne Einsätze nicht, blieben ihre Patientenspalten in der
Datei einfach leer — die Datei sähe vollständig aus, wäre es aber nicht.
Deshalb kommt in diesem Fall eine Rückfrage mit der Zahl der betroffenen
Einsätze. Sie ist ein Grund zum Innehalten: Vor einem Export gehört geklärt,
warum der Schlüssel nicht passt (Abschnitt 5).

**Formeln in CSV-Dateien.** Beginnt ein Textwert mit `=`, `+`, `-` oder `@`,
steht im CSV-Export ein Apostroph davor. Er gehört nicht zum Wert; er
verhindert, dass Excel oder LibreOffice die Zelle als **Formel** ausführen.
Zahlen sind ausgenommen, negative Werte bleiben also Zahlen. Die beiden
Excel-Formate brauchen das nicht — dort entstehen echte Textzellen.

**Mit Passwort schützen** verschlüsselt die Datei mit AES-256. Mindestens zehn
Zeichen, dieselbe Prüfung wie beim Anmeldepasswort. Anders als beim Backup wird
hier **nicht** angeboten, das Kontopasswort zu verwenden: Eine Exportdatei ist
zum Weitergeben gedacht. Das ist die
empfohlene Einstellung, sobald Patientendaten enthalten sind. Zum Öffnen wird
ein Zusatzprogramm gebraucht: **7-Zip** unter Windows, **Keka** oder **The
Unarchiver** unter macOS — der Windows-Explorer und das macOS-Archivprogramm
können solche Archive nicht öffnen. Beide Programme sind kostenlos.

Das Passwort wird nirgends gespeichert und lässt sich nicht zurücksetzen. Geht
es verloren, lässt sich die Datei **nicht mehr öffnen** — die Daten darin sind
dann endgültig nicht mehr lesbar. Es gibt keinen Weg daran vorbei, auch nicht
über die Anwendung.

**Der Dateiname sagt, was drin ist.** Er ist so aufgebaut:

```
luftrettungsdokumentation_export_06-08-2026_standard_mit-pat_verschl_philipp-mueller.zip
```

Der Reihe nach: der Tag der Erstellung, das gewählte Format (`standard`,
`guteseele` oder `csv`), ob Patientendaten enthalten sind (`mit-pat` oder
`ohne-pat`), ob die Datei verschlüsselt ist (`verschl` oder `unverschl`) und
zuletzt das Konto, aus dem der Export stammt — der Name aus den Einstellungen,
und wenn dort keiner steht, die E-Mail-Adresse. So ist auch Wochen später und
in einem Ordner voller Exporte zu sehen, welche Datei vorsichtig zu behandeln
ist, ohne dass man sie öffnen muss.

Zwei Feinheiten:

- Die Angabe zur Verschlüsselung gilt immer für **genau diese Datei**. Ein
  passwortgeschütztes Excel steckt in einem Archiv `…_verschl.zip`, die
  Tabelle darin heißt `…_unverschl.xlsx` — denn sobald sie entpackt ist, liegt
  sie offen.
- Enthält der Name Umlaute oder Leerzeichen, werden sie umgeschrieben
  (`Philipp Müller` → `philipp-mueller`), weil nicht jedes Betriebssystem und
  nicht jedes Programm damit zurechtkommt. Bei einer E-Mail-Adresse entfallen
  `@` und Punkte ebenso (`max@gen-em.de` → `max-gen-em-de`).

Bedenke beim Weitergeben: Die Kontokennung steht damit auch im Dateinamen.

Beim CSV nennt die Abschlussmeldung unter dem Knopf auch, wie viele GPX-Tracks
im Archiv liegen — „keine Tracks vorhanden" ist etwas anderes als „Tracks
vergessen", und das war vorher erst nach dem Entpacken zu sehen.

### 7.2 Zurücklesen

Beide Exceltabellen und das CSV-Archiv lassen sich wieder importieren — auch in
ein anderes Konto. Ein `.zip` kann direkt gewählt werden, die Tabelle darin wird
von selbst gefunden; bei einem geschützten Archiv wird nach dem Passwort gefragt.

- Das **CSV (Standard)** liest alles zurück: alle Phasen samt Koordinaten, die
  Reanimationsdokumentation und sämtliche Einsatzfelder. Nicht übernommen werden
  die internen Nummern der Einsätze (sie werden neu vergeben) und die
  GPX-Tracks — Tracks stammen von der Uhr, der Weg dafür ist das Backup.
- Beim **Excel (Standard)** steht vor dem Import, welche Felder danach leer
  bleiben: die Phasen zwischen Abflug und Übergabe, alle Koordinaten, die
  Reanimationsdokumentation, der Track samt Flugkilometern und ein von Hand
  eingetragenes Alter ohne Geburtsdatum. Diese Angaben stehen in der Datei nie
  drin — sie gehen nicht verloren, sie werden nur nicht befüllt. (Beim Alter ist
  es etwas anderes: Es steht in der Tabelle, lässt sich beim Einlesen aber nicht
  sicher von einem aus dem Geburtsdatum gerechneten Wert unterscheiden. Für den
  Rückweg ist auch hier das CSV zuständig.)

Für eine echte Wiederherstellung ist und bleibt das Backup der richtige Weg.

Die genaue Feldliste jedes Formats steht in `docs/Export-Format.md`.

---

## 8. Löschen und Papierkorb

Einsätze und ganze Flugtage landen beim Löschen zunächst im **Papierkorb** und
bleiben dort **90 Tage** wiederherstellbar; danach räumt das System sie
automatisch endgültig weg.

- **Einsatz löschen:** in der Einsatzansicht über „Löschen". Es erscheint eine
  Seite, die vorher zeigt, was mitgeht (Phasen, Reanimationen, Trackpunkte).
- **Flugtag löschen:** unten auf der Tagesübersicht. Achtung — das entfernt
  **den kompletten Tag**: alle Einsätze, Ruhesegmente, Tracks, Reanimationen
  und die Flugtag-Angaben. Beim Wiederherstellen kehrt alles gemeinsam zurück.
- **Papierkorb:** eigene Seite, erreichbar über das Papierkorb-Symbol unten in
  der Einsatztage-Leiste (ausgegraut, solange nichts darin liegt) — je eine
  Tabelle für gelöschte Flugtage und einzeln gelöschte Einsätze, mit
  „Wiederherstellen" und „Endgültig löschen". Endgültiges Löschen fragt noch
  einmal nach und ist unwiderruflich.

Solange etwas im Papierkorb liegt, nimmt der Server Nachlieferungen der Uhr
für diese Einsätze zwar entgegen, verwirft sie aber — gelöschte Einsätze
wachsen also nicht wieder an. Beim endgültigen Löschen kommt die Referenz auf
eine Sperrliste, sodass die Uhr sie nicht neu anlegt.

**Ein Flugtag im Papierkorb nimmt keine Änderungen an.** Trägst du Maschine,
Basis oder Besatzung für einen gelöschten Tag ein, wird das abgelehnt und du
bekommst einen Hinweis — die Angaben werden nicht gespeichert. Dasselbe gilt
für Import und das Einspielen einer Sicherung: Beide überspringen solche Tage
und sagen es. Der Grund: Das Löschen war eine bewusste Handlung, und sie
nebenbei rückgängig zu machen wäre eine Überraschung. Stelle den Tag zuerst
wieder her.

**Die Sperrliste hält 90 Tage**, danach räumt das System sie ebenfalls weg. Das
ist in der Praxis reichlich — eine Uhr, die 90 Tage lang keine Verbindung
hatte, gibt es im Betrieb nicht. Wer eine lange abgeschaltete Uhr wieder in
Dienst nimmt, sollte aber wissen, dass gepufferte Einsätze von damals wieder
auftauchen können, und nach dem ersten Abgleich kurz in die Tagesliste sehen.

Alle Rückfragen erscheinen als Fenster **innerhalb der Seite**, nicht als
Browser-Dialog. Das ist Absicht: Bei Browser-Dialogen lässt sich „keine
weiteren Dialoge dieser Seite anzeigen" ankreuzen — danach würden Löschungen
kommentarlos durchlaufen. Seiteneigene Fenster kann der Browser nicht
abschalten.

Stammdaten (Standorte, Maschinen, Besatzung, Rettungsmittel, Bergwacht,
Transportziele) und
Geräte werden direkt nach einer kurzen Rückfrage gelöscht — sie sind schnell
wieder angelegt. **Bereits dokumentierte Flugtage bleiben davon unberührt:**
Besatzungsnamen und Bergwacht-Angaben stehen ohnehin als Text im Flugtag, und
beim Löschen einer Maschine oder eines Standorts wird deren Name vorher in die
betroffenen Flugtage übernommen. Ein **Nutzerkonto** zu löschen verlangt
zusätzlich das Abtippen der E-Mail-Adresse und geht nicht über den Papierkorb.

---

## 9. Stammdaten (Standortdaten)

Unter **⚙ Einstellungen → „Standortdaten"** pflegst du deine Vorbelegungen. Die
sechs Bereiche sind aufklappbare Abschnitte und starten zugeklappt.

### 9.1 Standorte, Hubschrauber, Besatzung, Bergwacht

Standorte, Hubschrauber (Kennung plus Häkchen, welche Rollen an Bord sind),
Namenslisten je Rolle und Bergwacht-Bereitschaften. Am Flugtag wählst du
Maschine und Standort dann per Dropdown; die beim Hubschrauber angehakten
Rollen erscheinen als Besatzungs-Dropdowns mit deinen Vorbelegungen. Mit
„Als Standard" (★) markierte Maschine und Standort werden bei neuen Flugtagen
vorbelegt — das gilt auch für vom Admin systemweit hinterlegte Einträge (s. 8.4).

### 9.2 Andere Rettungsmittel

Hier legst du RTW, NEF oder weitere Hubschrauber als Vorbelegung an. Im
Einsatzformular tippst du im Feld **Weitere Rettungsmittel** mindestens zwei
Zeichen — dann erscheinen die passenden Einträge zum Anklicken. Jeder
übernommene Eintrag steht als eigenes Element mit kleinem Kreuz zum Entfernen;
mehrere sind möglich, doppelte werden abgewiesen. Steht etwas nicht in der
Vorbelegung, lässt es sich trotzdem übernehmen — es gilt dann nur für diesen
Einsatz.

Löschst du später ein Rettungsmittel aus der Vorbelegung, behalten bereits
dokumentierte Einsätze ihren Eintrag: Die Zuordnung wird je Einsatz gespeichert
und hängt nicht an der Liste.

### 9.3 Transportziele

Vorbelegung für das Feld **Transportziel** im Einsatz. Anders als bei den
Rettungsmitteln bleibt das Feld dort ein einfaches Textfeld mit Vorschlagsliste
(Tastatur-Pfeiltasten bzw. Antippen) — Freitext ist weiterhin uneingeschränkt
möglich.

### 9.4 Zentrale Stammdaten (vom Admin gepflegt)

Der Admin kann alle sechs Bereiche zusätzlich **systemweit** hinterlegen (siehe
Abschnitt 11). Solche Einträge erscheinen bei allen NutzerInnen mit dem
Kennzeichen **„systemweit"**, stehen automatisch in allen Vorbelegungen zur
Verfügung und lassen sich hier nicht bearbeiten oder löschen. Versuchst du,
einen persönlichen Eintrag mit demselben Namen anzulegen, wird das mit einem
Hinweis abgelehnt — der systemweite Eintrag steht dir ja bereits zur Verfügung.
Existiert umgekehrt schon ein persönlicher Eintrag, bevor der Admin denselben
Namen systemweit anlegt, bleibt dein Eintrag bestehen und erhält lediglich einen
Warnhinweis („identisch mit systemweitem Eintrag") — du kannst ihn dann bei
Bedarf löschen.

---

## 10. Geräte

Unter **⚙ Einstellungen → „Geräte"** verwaltet jede/r die eigenen Uhren:
**„Gerät anlegen"** erzeugt Geräte-ID und API-Schlüssel — der Schlüssel wird
**nur einmal** angezeigt, also sofort notieren bzw. eintragen. **Deaktivieren**
sperrt den Upload sofort (z. B. bei Verlust); alle bereits hochgeladenen Daten
bleiben erhalten, und **Aktivieren** schaltet dasselbe Gerät wieder frei.

**Höchstens fünf Geräte je Konto.** Die Seite zeigt den Zählstand („belegt:
3 von 5"). Deaktivierte Geräte zählen mit — ihre Zugangsdaten bestehen weiter
und lassen sich mit einem Klick wieder freischalten. Erst **Löschen** gibt
einen Platz frei. Ist die Grenze erreicht, lässt sich weder ein Gerät anlegen
noch ein Kopplungscode erzeugen; die Meldung sagt, was zu tun ist.

**Du wirst benachrichtigt, wenn ein Gerät hinzukommt.** Nach jeder erfolgreichen
Kopplung geht eine E-Mail an deine Adresse — mit Gerätekennung, Zeitpunkt und
dem Weg, das Gerät wieder zu entfernen. Zusätzlich steht auf der Übersicht und
im Geräte-Reiter ein Hinweis auf alles, was in den letzten sieben Tagen dazukam.

**Kommt dir ein Gerät unbekannt vor, lösche es.** Ab diesem Moment kann es
nichts mehr hochladen. Bereits hochgeladene Daten bleiben erhalten, damit du
sie in Ruhe ansehen kannst.

---

## 11. Administration (nur Admin)

NutzerInnen anlegen (verschickt automatisch den Passwort-Setz-Link) und löschen
(**Achtung:** entfernt alle Daten der Person unwiderruflich). Ein Klick auf eine
NutzerIn öffnet die Editierseite: Rolle wechseln, E-Mail ändern und die
verbundenen Geräte einsehen (aktivieren/deaktivieren/löschen — Löschen lässt
hochgeladene Daten bestehen).

**Beim Anlegen gibt es drei mögliche Antworten**, und die Seite sagt, welche
zutrifft:

- *Nutzer angelegt — Setz-Link per E-Mail verschickt.* Alles in Ordnung.
- *Es gibt bereits ein Konto mit dieser E-Mail-Adresse.* Es wurde nichts
  angelegt.
- *Nutzer angelegt — die E-Mail konnte NICHT verschickt werden.* Das Konto
  steht, nur der Versand scheiterte. Die Seite zeigt dann den Einladungslink
  an; er ist 24 Stunden gültig und muss auf einem anderen Weg weitergegeben
  werden. **Nur an die Person selbst** — wer den Link hat, kann das Passwort
  des Kontos setzen. Die Ursache des Fehlschlags steht im Fehlerprotokoll des
  Webspace.

**Rollenwechsel und Löschen wirken sofort**, auch bei jemandem, der gerade
angemeldet ist: Beim nächsten Klick gelten die neuen Rechte, ein gelöschtes
Konto wird abgemeldet. Ein Ab- und Anmelden ist nicht nötig.

Unter **„Wartung/Update"** steht seit 4.5.1 zusätzlich, wann der tägliche
Aufräumjob zuletzt **vollständig** durchgelaufen ist. Steht dort eine Warnung,
scheitert einer der Aufräumschritte dauerhaft — dann wird unter anderem der
Papierkorb nicht mehr geleert. Die Ursache steht im Fehlerprotokoll des
Webspace unter dem Suchwort `cleanup:`.

Unter **„Wartung"** stehen zwei Dinge: ob Datenbank-Updates anstehen (nach
dem Aufspielen einer neuen Fassung dort nachsehen) und ob der tägliche
Aufräumjob durchläuft. Das bloße Öffnen der Seite ändert nichts — sie zeigt
erst an, was anstünde, und wartet auf eine Bestätigung.

Unter **„Zentrale Stammdaten"** pflegt der Admin dieselben sechs Bereiche wie
unter Standortdaten (9.1–9.3), jedoch für **alle** NutzerInnen gemeinsam
(siehe 9.4). Namensgleiche Einträge werden auch hier abgelehnt; existieren
bereits persönliche Einträge mit demselben Namen bei einzelnen NutzerInnen,
weist ein Hinweis darauf hin (keine Blockade).

Nach Code-Updates mit Datenbank-Änderungen einmal **`update.php`** aufrufen
(siehe Technik-Doku, Betrieb). Die Seite läuft **zweistufig**: Der Aufruf zeigt
nur an, was anstünde, und ändert nichts; erst der Knopf **„Updates jetzt
anwenden"** führt sie aus. Vorher eine Sicherung erstellen — Migrationen können
Spalten und die darin enthaltenen Daten unwiderruflich entfernen.

Die Seite zeigt außerdem unter **„Schlüsselableitung"**, ob alle Konten mit
Einstellungen rechnen, die diese Programmfassung anbietet. Steht dort eine
Warnung, können sich die genannten Konten **nicht anmelden** — die Behebung
steht dabei.

Ein Update, das eine Spalte löscht, ist seit 4.7.0 in der Liste **mit ⚠
gekennzeichnet** und mit einem Satz versehen, was verlorenginge. Steht in einer
solchen Spalte noch etwas, wird das Update **nicht ausgeführt** — die Zeile
nennt stattdessen Spalte und Zeilenzahl. Alle übrigen Updates laufen trotzdem
durch.

Diese Daten lassen sich nicht automatisch in den verschlüsselten Block
überführen; er entsteht ausschließlich im Browser. Wer sie behalten will, trägt
sie vorher von Hand in den jeweiligen Einsatz ein (oder sichert sie außerhalb)
und setzt danach das Häkchen an genau dieser einen Zeile.

---

## 12. Eine neue Uhr einrichten (Kurzanleitung)

1. App auf die Uhr laden (siehe `Technik.md`). Die Server-Adresse trägst du in
   Garmin Connect ein; die Domain genügt (z. B. `luftrettung.net`).
2. Im Web unter **⚙ Einstellungen → „Geräte" → „Kopplungscode erzeugen"** —
   der **6-Zeichen-Code ist 10 Minuten gültig und genau einmal verwendbar**.
   Ein neu erzeugter Code macht einen vorher erzeugten ungültig, und es gibt
   je Konto immer höchstens einen offenen Code. Wird der Code zu oft falsch
   eingegeben, weist der Server weitere Versuche vorübergehend ab. Sind bereits
   fünf Geräte verbunden, lässt sich kein Code mehr erzeugen — erst ein nicht
   mehr genutztes Gerät löschen.
3. Auf der Uhr auf der Sync-Seite **START halten**, den Code eintippen und
   bestätigen — die Uhr meldet „Gekoppelt ✓" und ist einsatzbereit. Das Gerät
   erscheint im Web in der Geräteliste, und du bekommst eine E-Mail darüber.
4. Alternative ohne Code: Gerät manuell anlegen und Geräte-ID/API-Schlüssel
   in die Connect-Einstellungen eintragen (nur nötig, wenn die Kopplung nicht
   möglich ist).
