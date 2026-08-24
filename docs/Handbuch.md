# Einsatzdoku — Handbuch

*Stand: 03.08.2026 · Für die technische Struktur siehe `Technik.md`, für
Änderungen `CHANGELOG.md`.*

## 1. Was ist die Einsatzdoku?

Die Einsatzdoku dokumentiert Notarzteinsätze direkt vom Handgelenk — luft-
gebunden wie bodengebunden (RTH, NEF, NAW): Eine
Garmin-Uhr-App erfasst Einsatzphasen mit Zeitstempeln, GPS-Tracks und
Reanimations-Ereignisse und lädt alles automatisch auf einen eigenen Server.
Die Web-Oberfläche (luftrettung.net) zeigt Diensttage mit Karte, Einsatz-Details
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
Der Diensttag läuft, bis du ihn über das Schnellmenü mit **„Einsatztag beenden"**
(Sicherheitsabfrage) schließt; dabei werden Restdaten hochgeladen. Das Datum
des Diensttags ist das Datum des Dienstbeginns — auch bei Diensten über
Mitternacht.

**Jeder Druck auf START erzeugt einen eigenen Diensttag.** Zwei Dienste an einem
Kalendertag sind damit möglich und vorgesehen — etwa ein Hubschrauberdienst am
Tag und ein NEF-Nachtdienst am Abend. Beide erscheinen im Web als getrennte
Zeilen, unterschieden durch die Uhrzeit des Dienstbeginns.

Wurde die App während **eines** Dienstes versehentlich mehrfach gestartet, sind
daraus mehrere Diensttage geworden. Im Web lassen sie sich wieder
zusammenführen (Abschnitt 4.5a).

**Die Uhr kennt die Einsatzart nicht.** Sie fragt weder nach Standort noch nach
Rettungsmittel; beides trägst du im Web nach. Bis dahin ist der Diensttag
*neutral* — Zeiten, Phasen, Track und Reanimation werden trotzdem vollständig
erfasst.

Ein Neustart der Uhr oder der App mitten im Dienst ist unkritisch: Phase,
Track und eine laufende Reanimation werden nahtlos fortgesetzt.

### 2.2 Die Oberflächen

Mit **kurz UP/DOWN** blätterst du im Kreis durch: **Uhr → Tempo → Statistik →
Sync → Reanimation**.

**Uhr (Hauptanzeige):** groß die Uhrzeit, darunter klein das Datum, darunter
die aktuelle Phase (Zahl + Name). Läuft eine Reanimation, umschließt ein roter
Ring die Anzeige — auf einen Blick erkennbar.

- **kurz START** schaltet zur nächsten Phase (mit Zeitstempel und Position):
  1 Frei → 2 Alarmierung (= Einsatzbeginn) → 3 Ausrücken → 4 Ankunft Einsatzort →
  5 Ankunft PatientIn → 6 Transportbeginn → 7 Ankunft Klinik →
  8 Übergabezeit → 9 Endzeit → 10 Beendigung (= Einsatzende, zurück zu 1).

  Phase 3 hieß bis Uhr 1.7.0 „Abflug", Phase 7 „Landung Krankenhaus". Seit
  1.8.0 sind beide neutral benannt, weil dieselbe Uhr auch am NEF läuft.
  Nummerierung, Bedeutung und Reihenfolge sind unverändert.
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
Zahnrad liegen Profil, **Standorte**, **Rettungsmittel**, Backup,
Import / Export, Geräte und Abmelden (fragt sicherheitshalber nach); Admins
finden dort zusätzlich die Rubrik **Administration** mit NutzerInnenverwaltung,
**Standorte systemweit** und **Rettungsmittel systemweit** (Abschnitt 11).
Bis Web 6.3.0 hieß der Punkt für beides zusammen „Standortdaten"; der alte Link
führt weiterhin zu „Standorte". Nach 30 Minuten ohne Aktivität meldet das System automatisch
ab. Die Kopfleiste bleibt beim Scrollen oben stehen.

Die **Einsatztage-Leiste** links begleitet alle Inhaltsseiten — auch
Einsatzansicht und Formular. Sie ist nach Jahr und Monat gruppiert
(Abschnitt 4.4).

**Wenn ein Link ins Leere führt.** Ein Lesezeichen auf einen gelöschten
Einsatz, eine Adresse aus einer alten E-Mail, ein Diensttag im Papierkorb:
Dann erscheint eine Seite mit der Kopfleiste, einem kurzen Satz („Einsatz nicht
gefunden.") und einem Rückweg zur Übersicht. Seit Web 7.2.0 sieht das überall
gleich aus; vorher stand dort nur der Satz auf weißem Grund, ohne Menü und ohne
Weg zurück.

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

### 3.2 Demo-Konto — ausprobieren, ohne etwas kaputtzumachen

Es gibt ein Konto, in dem sich alles gefahrlos ausprobieren lässt:

| | |
|---|---|
| Adresse | `demo@gen-em.org` |
| Passwort | `nadokudemo0815` |

**Alle Daten darin sind frei erfunden.** Die Orte, Kliniken, Rettungsmittel
und Besatzungsnamen gibt es nicht; die Diagnosen gehören zu niemandem. Der
Datensatz ist so gebaut, dass jede Funktion der Anwendung darin vorkommt —
Luft- und Bodeneinsätze, Windeneinsätze, Bergwacht, Reanimationen, ein Dienst
über Mitternacht, ein Diensttag ohne Einsatz, ein gefüllter Papierkorb.

**Ausprobieren ist ausdrücklich erwünscht.** Ändere Einsätze, lege neue an,
lösche welche, pflege Stammdaten, koppele eine Uhr. Es geht nichts verloren,
was jemandem fehlen würde.

**Alle 30 Minuten setzt sich das Konto selbst zurück.** Danach ist der
Ausgangsstand wieder da und deine Änderungen sind fort — auch die, die du
gerade noch gebraucht hättest. Ein Banner am oberen Rand erinnert daran und
nennt, wann es das nächste Mal so weit ist.

**Was im Demo-Konto nicht geht:** E-Mail-Adresse und Passwort lassen sich
nicht ändern, und „Passwort vergessen" führt für diese Adresse zu nichts.
Beides ist Absicht — die Zugangsdaten sind öffentlich und müssen es bleiben,
damit die nächste Person hereinkommt. Alles andere ist offen.

> **Niemals echte Patienten- oder Einsatzdaten in diesem Konto erfassen.**
> Es ist die einzige Stelle der Anwendung, an der die Verschlüsselung bewusst
> ausgesetzt ist: Das Schlüsselmaterial liegt dort auf dem Server, damit die
> Rücksetzung funktioniert. Für erfundene Daten ist das unproblematisch — für
> echte wäre es das nicht.

Wird das Konto gerade von sehr vielen gleichzeitig genutzt, ist die Anmeldung
vorübergehend gesperrt. Die Meldung sagt, ab wann es wieder geht; ein eigenes
Konto ist davon nicht betroffen.

## 4. Einsätze ansehen und bearbeiten

### 4.1 Tagesübersicht

Startseite nach der Anmeldung. Links die Liste der Diensttage; der neueste ist
vorausgewählt. Liegen mehrere Diensttage auf einem Kalendertag, steht bei jedem
zusätzlich die Uhrzeit des Dienstbeginns — sonst ließen sie sich nicht
unterscheiden. Vor dem Namen des Rettungsmittels steht ein Zeichen für die Art:
🚁 luftgebunden, 🚑 bodengebunden, ◌ noch ohne Zuordnung.

Pro Tag:

- **Diensttag-Daten** (aufklappbar): Standort, Rettungsmittel, Besatzung,
  Notizen — direkt editier- und speicherbar. Die Kopfzeile zeigt eine
  Kurzfassung.

  Welche **Besatzungsrollen** hier stehen, ergibt sich aus dem gewählten
  Rettungsmittel: luftgebunden Pilot 1, Pilot 2, HEMS-TC, Flugretter und
  Sonstige, bodengebunden Fahrer, Praktikant und Sonstige. Ein Diensttag ohne
  Rettungsmittel zeigt keine Rollen — trag Standort und Rettungsmittel nach,
  dann erscheinen sie.
- **Karte** mit allen Einsätzen des Tages (jeder in eigener Farbe, beginnend
  mit Orange/Blau/Rot) und dem Ruhe-Track in Schwarz. Die Einsatzort-Pins
  tragen die Farbe des jeweiligen Einsatzes. Die Karte zoomt automatisch so,
  dass alle Tracks sichtbar sind; Tracklinien werden beim Rauszoomen etwas
  dicker. Oben links lässt sich die Karte per Klick auf **Vollbild**
  stellen (erneuter Klick oder ESC verlässt den Vollbildmodus wieder), oben
  rechts zwischen vier Kartenebenen umschalten: **Standard**, **Wanderkarte**
  (mit Höhenlinien), **Topographisch** und — seit Web 7.0.0 —
  **Satellitenbild**. Das Luftbild zeigt, was Höhenlinien nicht leisten: ob
  der Einsatzort auf einer Wiese, im Wald oder auf einem Parkplatz lag. Es ist
  bewusst nicht der Standard, weil es deutlich größere Kacheln lädt. Beide
  Controls stehen auf allen drei Kartenseiten der Anwendung zur Verfügung.
- **Tabelle** der Einsätze: Nr., Beginn, Dauer, **Einsatzort** (Ortschaft aus
  der verschlüsselten Adresse), **Alter**, **Diagnose**, Winde, Bergwacht,
  Sekundärtransport, Kilometer. Winde und Bergwacht stehen nur an einem
  Diensttag, dessen Rettungsmittel sie führt. Den **Fehleinsatz** führt diese
  Tabelle bewusst nicht — er steht im Einsatz selbst; auswerten lässt er sich
  in der Zeitraum-Übersicht und der Suche. Alle Spalten sind zentriert und
  in abwechselnden Zeilenfarben; ein Klick auf eine Zeile öffnet den Einsatz,
  ein Klick auf einen Spaltenkopf sortiert. Die Dauer rechnet von der
  Alarmierung bis Phase 9; fehlt Phase 9, steht dort „kein Ende".
  Eine Spalte **abw. Crew** gab es von Web 5.4.0 bis 5.9.0; sie ist wieder
  entfallen, weil der Haken an den allermeisten Tagen in keiner Zeile stand.
  Ob für einen Einsatz eine vom Diensttag abweichende Besatzung eingetragen ist,
  steht vollständig in der Einsatzansicht unter **Besatzung** — mit „(abw.)"
  an der betroffenen Rolle (Abschnitt 5). Das Feld selbst ist unverändert.
- **„+ Einsatz nachtragen"** unter der Tabelle öffnet das Eingabeformular für
  diesen Tag. Oben rechts steht das Menü **Aktionen** (seit Web 5.10.0; vorher
  standen die Einträge als Schaltflächen unter der Tabelle) mit
  **„Datum ändern"** — korrigiert das Datum des ganzen Tages (Abschnitt 4.2a) —,
  **„Anderen Diensttag aufnehmen"** — führt zwei Diensttage zusammen
  (Abschnitt 4.5a) — und **„Tag löschen"** — entfernt den gesamten Diensttag
  (Abschnitt 8). Das Menü lässt sich wie das der Einsatzansicht vollständig mit
  der Tastatur bedienen; Escape schliesst es wieder.

### 4.2 Einsatzansicht

Titel „Einsatz N · Uhrzeit" (N = Nummer des Tages nach Alarmierungszeit),
darunter das Menü **Aktionen** mit **Bearbeiten**, **Verschieben** und
**Löschen** (seit Web 5.6.0; vorher standen dort zwei Schaltflächen). Das Menü
lässt sich vollständig mit der Tastatur bedienen: Tabulator auf den Kopf, Enter
oder Leertaste öffnet, Tabulator läuft weiter durch die Einträge, Escape
schließt wieder. In der Kopfzeile darunter stehen
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
gültige Besatzung: normalerweise die Besatzung des Diensttags, bei einer
abweichenden Besatzung (Abschnitt 4.3) die abweichende Person. Geänderte
Rollen sind blau mit **„(abw.)"** gekennzeichnet, unveränderte stehen ohne
Zusatz daneben. Rollen ohne Eintrag werden weggelassen; ist gar keine Besatzung
hinterlegt, entfällt der Block ganz.

Es folgen die Phasen-Tabelle und je Reanimation eine eigene Zeiten-Tabelle.

### 4.2a Falsche Tageszuordnung korrigieren

Es lassen sich zwei Dinge korrigieren: ein Einsatz, der beim falschen
Tag gelandet ist, und ein ganzer Diensttag, der am falschen Datum steht. Das sind
**zwei verschiedene Fälle**, und die Wahl entscheidet darüber, was mit den
Uhrzeiten passiert.

**Ein einzelner Einsatz gehört zum falschen Tag.** Seine Uhrzeiten stimmen —
nur die Zuordnung nicht. Der klassische Fall ist der Dienst über Mitternacht:
Beim Nachtragen landet ein Einsatz auf dem Kalendertag, an dem er begann,
obwohl er zum Diensttag davor gehört. Auf der Einsatzseite: **Aktionen →
Verschieben**, Zieltag wählen, fertig. Die **Uhrzeiten bleiben unverändert**.

Liegt der Zieltag im Papierkorb, wird die Verschiebung abgelehnt; hol ihn erst
zurück. Ein späterer Upload derselben Uhr zieht den Einsatz nicht wieder auf den
alten Tag.

**Gewählt wird ein Diensttag, nicht mehr ein Datum.** Seit Web 6.0.0 können auf
einem Kalendertag mehrere Diensttage liegen — das Datum benennt den Zieltag
also nicht mehr eindeutig. Die Auswahl nennt deshalb je Tag Datum und
Dienstbeginn, Rettungsmittel, Standort und die Zahl der dort liegenden
Einsätze.

**Die Uhr war falsch gestellt.** Dann sind Datum *und* Uhrzeit falsch, und der
ganze Tag steht am falschen Datum. In der Tagesübersicht: **Aktionen → „Datum
ändern"**.
Hier **wandern alle Zeitstempel mit** — Einsätze, Ruhesegmente, Phasenzeiten,
Reanimationsprotokolle und die GPS-Spur. Die abgelesenen Uhrzeiten bleiben
dabei stehen; verschoben wird nur das Datum, auch über eine Zeitumstellung
hinweg.

Bevor etwas geschieht, zeigt die Seite, was betroffen ist: wie viele Einsätze,
Ruhesegmente und Trackpunkte, und ob Einträge aus dem Papierkorb dabei sind
(die wandern mit). Danach kommt die übliche Rückfrage. Alles davon geschieht
**gemeinsam oder gar nicht** — bricht etwas ab, steht der Tag unverändert am
alten Datum.

**Ein belegtes Zieldatum ist kein Hindernis mehr.** Bis Web 5.10.0 wurde die
Änderung abgelehnt, wenn dort schon ein Tag stand — der Kalendertag war der
Schlüssel. Seit Web 6.0.0 sind mehrere Diensttage an einem Datum der vorgesehene
Fall; sie stehen danach in der Leiste links untereinander, unterschieden durch
die Uhrzeit des Dienstbeginns. Sollen die beiden **ein** Dienst werden, ist das
ein eigener Vorgang: Abschnitt 4.5a.

### 4.3 Einsätze nachtragen und bearbeiten

Das Formular dient beidem. Es ist seit Web 7.0.0 in **benannte Gruppen**
gegliedert, jede mit eigenem Rahmen und Überschrift, in dieser Reihenfolge:

1. **PatientInnendaten** — Einsatznummer, Nachname, Vorname, Geburtsdatum,
   Alter, Diagnose
2. **Einsatz** — Sekundärtransport und Fehleinsatz nebeneinander, Einsatzort,
   Beschreibung des Einsatzorts, Abfahrtort
3. **Transport** — Transportart, NA-Begleitung, Transportziel, Schockraum
4. **Bergrettung** — Bergwacht, Windeneinsatz
5. **Weitere Rettungsmittel** — Fahrzeuge, weiterer Notarzt
6. **Abweichende Besatzung**
7. **Notizen**
8. **Einsatzphasen**
9. **Reanimation**

Die Gruppe **Bergrettung** fehlt ganz, wenn der Diensttag weder Winde noch
Bergwacht mitbringt und im Einsatz nichts dazu eingetragen ist.

**Kein Feld „Einsatzdatum" mehr.** Es stand früher direkt unter dem Diensttag
und zeigte in aller Regel dasselbe Datum ein zweites Mal. Der Fall, für den es
gedacht war — der Einsatz **nach Mitternacht** an einem Dienst, der am Vortag
begann —, wird jetzt erkannt: Liegt die erste Phase vor dem Beginn des Dienstes,
gehört der Einsatz dem Folgetag. Weicht das Einsatzdatum vom Datum des Dienstes
ab, steht es oben ausdrücklich daneben. Beim **Bearbeiten** bleibt das
gespeicherte Datum unangetastet; verschoben wird ein Einsatz über
**Aktionen → Verschieben**.

Phasen werden als Zeilen erfasst (Phase wählen, Uhrzeit eintragen, Zeilen
hinzufügen/entfernen — auch dieselbe Phase mehrfach).
**In chronologischer Reihenfolge eintragen**; Zeiten nach Mitternacht werden
automatisch dem Folgetag zugerechnet. Der Block steht seit Web 7.0.0 **unten**,
direkt über der Reanimation: Beim Bearbeiten — dem häufigeren Fall — stehen die
Phasen meist schon vollständig da und schoben alles andere nach unten.

**NA-Begleitung ist bei „Luft" vorbelegt.** Ein Lufttransport ohne Notarzt an
Bord ist die Ausnahme. Der Haken setzt sich, sobald du „Luft" wählst — und nur,
solange du ihn nicht selbst angefasst hast: Deine ausdrückliche Entscheidung
gilt danach dauerhaft.

Das gilt beim Nachtragen **und** beim Bearbeiten. Ein gespeicherter Wert wird
dabei nie überschrieben: Der Haken setzt sich ausschliesslich, wenn du die
Transportart gerade umstellst — beim blossen Öffnen eines Einsatzes passiert
nichts.

Uhrzeiten stehen immer im **24-Stunden-Format `HH:MM`**, unabhängig davon, wie
dein Gerät sonst eingestellt ist. Du kannst einfach die Ziffern tippen: aus
`930` wird `09:30`, aus `9` wird `09:00`, der Doppelpunkt setzt sich von
selbst. Ergibt die Eingabe keine gültige Uhrzeit, färbt sich das Feld rot, und
gespeichert wird sie nicht. Datumsfelder bleiben die gewohnten Kalenderfelder
deines Geräts. Trägst du eine Zeile nachträglich mit
einer früheren Uhrzeit ein, sortiert sich die Liste nach dem Speichern von
selbst richtig ein.

**Strg-Enter** (bzw. Cmd-Enter auf macOS) sendet das Formular ab, ohne die
Maus zu benutzen — in Notizen bleibt einfaches Enter ein Zeilenumbruch.
Verlässt du die Seite mit ungespeicherten Änderungen, fragt der Browser vorher
nach; das gilt auch für die Diensttag-Formulare.

**Geschützte Angaben** (Abschnitt 5) verteilen sich auf die Gruppen
„PatientInnendaten" (Person und Diagnose) und „Einsatz" (Einsatzort,
Beschreibung, manueller Abfahrtort). Ist der Schlüssel in dieser Sitzung
gesperrt, sind alle diese Felder gesperrt — die übrigen bleiben bedienbar.
Beim Geburtsdatum reicht auch eine zweistellige Jahreszahl
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



**Abfahrtort.** Unmittelbar unter dem Einsatzort steht, von wo aus ausgerückt
wurde — **aber nur, wenn dieser Einsatz keine GPS-Aufzeichnung hat** (seit
Web 7.0.0). Liegt ein Track vor, zeichnet die Karte den tatsächlich
zurückgelegten Weg; die Auswahl bliebe dann folgenlos und steht deshalb gar
nicht erst da. Eine früher gespeicherte Regel bleibt in den Daten erhalten.

Gespeichert wird dabei nicht die Koordinate, sondern die **Regel**:

| Auswahl | Woher die Koordinate kommt |
|---|---|
| Standort | Koordinaten des Standorts dieses Diensttags |
| Letzter Einsatzort | Einsatzort des vorherigen Einsatzes desselben Diensttags |
| Letzte Zielklinik | Zielklinik des vorherigen Einsatzes desselben Diensttags |
| Manueller Ort | eigene Adresssuche, wie beim Einsatzort |
| *(nichts gewählt)* | keine Linie |

Die beiden Vorgänger-Auswahlen bilden zwei verschiedene Abläufe ab: Nach einem
Transport steht das Rettungsmittel an der **Zielklinik** des Vorgängers, ohne
Transport noch an dessen **Einsatzort**. Fehlt die jeweilige Koordinate, entsteht
schlicht keine Linie — es wird **nicht** stillschweigend auf eine andere Quelle
ausgewichen, weil eine falsche Linie schlechter wäre als keine.

Liegt **kein** aufgezeichneter Track vor und sind Abfahrtort und Einsatzort
bekannt, zeichnet die Karte eine **gestrichelte Luftlinie** zwischen beiden, bei
belegter Zielklinik mit Koordinaten über drei Punkte. Ihre Länge steht an der
Linie und ist ausdrücklich als Luftlinie benannt. Ein echter Track hat immer
Vorrang; trifft er später ein, bleibt die Abfahrtortangabe gespeichert und wird
nur nicht mehr gezeichnet. In **keine** Kachel und **keinen** Filter fließt die
Luftlinienlänge ein — eine Luftlinie und eine gefahrene Strecke sind nicht
dieselbe Größe.

**Transport.** Die **Transportart** (Luft, Boden, Ambulant) — bis Web 6.3.0
schlicht „Transport" — steuert, was darunter erscheint: Bei Luft und Boden die **NA-Begleitung**, die **Zielklinik** samt
Koordinaten und den **Schockraum**; bei „Ambulant" — die Patientin wurde nicht
transportiert — entfallen alle drei. Ein zuvor eingetragenes Transportziel wird
dabei geleert, und die Änderung ist sichtbar: Ein Transportziel an einem Einsatz
ohne Transport wäre ein Widerspruch in den Daten.

Dazu die weiteren Zusatzfelder: **Fehleinsatz / Storno / Abbruch** (ein Haken,
ohne Unterauswahl), **Windeneinsatz** (Haken öffnet Cycles,
Cycles mit Patient, Luftverladung), **Bergwacht** (Haken öffnet Bereitschaft
aus den Stammdaten plus Namen/Infos), Sekundärtransport, Anderer
Notarzt, **Weitere Rettungsmittel** (Abschnitt 9.2) und Notizen.

**Winde und Bergwacht erscheinen nur**, wenn das Rettungsmittel des Diensttags
sie führt (Abschnitt 9.1). Wird ein Haken dort später abgewählt, verlieren
bereits dokumentierte Einsätze nichts: Ihr Diensttag hat die Fähigkeit beim
Anlegen eingefroren.

**Abweichende Besatzung.** Normalerweise gilt für jeden Einsatz die Besatzung
des Diensttags — sie wird einmal am Tag eingetragen und muss am Einsatz nicht
wiederholt werden. Wechselt jedoch während des Dienstes jemand (typisch: ein
Pilotenwechsel am Nachmittag), setzt du am betroffenen Einsatz den Haken
**„Abweichende Besatzung"**. Darunter erscheint je Rolle des Diensttags ein
Textfeld mit Vorschlagsliste: Sobald du hineinklickst oder zu tippen beginnst, schlägt das
Feld deine Besatzungs-Vorbelegungen und die zentralen Stammdaten der jeweiligen
Rolle vor (Abschnitt 9.1 bzw. 8.4).

**Seit Web 5.5.0 ist jeder Name eintragbar**, auch einer, der nicht in den
Stammdaten steht. Das ist der eigentliche Anlass für dieses Feld: Wer aushilft,
ist oft niemand, der regelmäßig auf diesem Rettungsmittel arbeitet. Die
Vorschläge bleiben die bequeme Abkürzung, sie sind nur keine Schranke mehr.

Gezeigt werden **nur die Rollen, die der Diensttag führt** — dieselben, die auch
oben in den Diensttag-Daten stehen. Ein NEF zeigt Fahrer, Praktikant und
Sonstige, ein Hubschrauber mit Pilot 1 und HEMS-TC nur diese beiden. Ein
Diensttag ohne Rettungsmittel zeigt keine. Steht in einer eigentlich nicht
vorgesehenen Rolle bereits ein Eintrag — etwa weil der Diensttag nachträglich auf
ein anderes Rettungsmittel umgestellt wurde —, bleibt sie sichtbar, damit du sie
weiterhin ändern kannst.

Es müssen **nur die tatsächlich abweichenden Rollen** ausgefüllt werden. Alle
übrigen bleiben leer und werden weiterhin vom Diensttag übernommen — so steht
dieselbe Person nie doppelt in der Datenbank. Entfernst du den Haken wieder,
werden die Felder geleert und der Einsatz erbt vollständig die Tagescrew.
In der Einsatzansicht (Abschnitt 4.2) zeigt der Block „Besatzung" immer das
Ergebnis beider Ebenen.

Ist eine früher eingetragene Person inzwischen aus den Stammdaten entfernt
worden, bleibt ihr Name im Feld trotzdem stehen und geht beim nächsten
Speichern nicht verloren.

**Reanimation.** Ganz unten im Formular steht der Abschnitt **Reanimation** —
seit Web 5.5.0, vorher konnten diese Zeiten nur von der Uhr kommen. „+
Reanimation hinzufügen" legt einen Block an: oben der **Reanimationsbeginn**,
darunter „+ Ereignis hinzufügen" für jedes weitere Ereignis, jeweils Art
(Zugang, Adrenalingabe, Rhythmuskontrolle, Defibrillation, Intubation,
Amiodaron, Sonographie, ROSC, Tod) und Uhrzeit. Das ✕ am Beginn entfernt die
ganze Reanimation, das ✕ an einer Ereigniszeile nur diese.

Gab es an einem Einsatz **mehrere Reanimationen**, legst du einfach mehrere
Blöcke an. Eine Zeile ohne Uhrzeit wird nicht gespeichert — du musst eine
versehentlich hinzugefügte Zeile also nicht erst wieder entfernen. Zeiten nach
Mitternacht werden wie bei den Phasen automatisch dem Folgetag zugerechnet.
In der Einsatzansicht erscheinen die Einträge in derselben Tabelle wie die von
der Uhr gelieferten; ein Unterschied ist dort nicht zu sehen und auch keiner
vorhanden.

**Abbrechen.** Unter dem Speichern-Knopf steht **Abbrechen** — beim Bearbeiten
führt es zurück zum Einsatz, beim Nachtragen zur Tagesansicht. Hast du im
Formular etwas eingetragen, fragt es vorher nach; ein unverändertes Formular
verlässt du ohne Rückfrage. Dasselbe gilt beim Anlegen eines Diensttags.

Beim Bearbeiten eines **Uhr-Einsatzes** gilt: Nach dem Speichern überschreibt
die Uhr ihn nicht mehr (nur der GPS-Track wird weiter ergänzt), und die
Einsatzansicht zeigt zusätzlich zum Herkunftskennzeichen „Uhr" das
Bearbeitungskennzeichen „editiert" (Details siehe Abschnitt 4.2). Das
Formular weist vorher darauf hin. Das betrifft auch die Reanimationszeiten:
Trägst du sie im Formular ein, bleiben sie so stehen — eine später
nachliefernde Uhr ersetzt sie nicht mehr.

Nach dem **Neuanlegen** eines Einsatzes zeigt die Einsatzansicht den Button
„Weiteren Einsatz nachtragen" — er öffnet die Neuanlage direkt für denselben
Diensttag. Beim Bearbeiten eines bestehenden Einsatzes erscheint er nicht.

### 4.4 Einsatztage-Leiste, Jahres- und Monatsübersicht

Die Leiste links ist nach **Jahr → Monat → Tage** gruppiert. Es ist immer nur
ein Jahr geöffnet und darin ein Monat (standardmäßig der jüngste); ein anderes
Jahr anzuklicken schließt das vorherige automatisch. Springst du auf einen Tag
in einem anderen Zeitraum, klappt die Leiste automatisch dorthin auf.

Ein Klick auf die **Jahreszahl** oder den **Monatsnamen** öffnet eine Übersicht
dieses Zeitraums: eine Karte mit einem Pin je Einsatzort (sofern Koordinaten
vorhanden und die geschützten Angaben entsperrt sind), darunter die
Statistik-Kacheln und schließlich die Tabelle aller Einsätze mit Datum statt
Tagesnummer, sortierbar. Die Durchschnittswerte rechnen mit **allen angelegten
Diensttagen** des Zeitraums, auch mit einsatzfreien.

**Getrennt nach Art.** Liegen im Zeitraum luft- *und* bodengebundene Diensttage,
steht über der Ansicht eine Leiste mit drei Reitern: **Gemischt** (aktiv),
**Luftrettung** und **Bodengebundener Rettungsdienst**. Der Reiter filtert
alles gemeinsam — Kacheln, Einsatztabelle und Karte. Liegt nur eine Art vor,
gibt es keine Leiste; dann bestimmt sie allein die Beschriftung.

| Reiter | Kacheln |
|---|---|
| **Luftrettung** | Einsätze, Flugtage, Ø Einsätze/Flugtag, Sekundärtransporte, Flugkilometer gesamt, längste Flugstrecke, längste Einsatzdauer, höchster Einsatzort — dazu Anzahl und Ø Winden-Cycles, sofern im Zeitraum tatsächlich Windeneinsätze dokumentiert sind |
| **Bodengebunden** und **Gemischt** | Einsätze, Diensttage, Ø Einsätze/Diensttag, Sekundärtransporte, **Fehleinsätze**, Einsatzkilometer gesamt, längste Einsatzstrecke, längste Einsatzdauer |

Der Luftrettungs-Reiter behält also die gewohnte Flugterminologie; für eine rein
luftgebundene Nutzung sieht die Auswertung aus wie immer. Höchster Einsatzort und
Windenzahlen fehlen in „Gemischt", weil sie sich über beide Arten nicht sinnvoll
addieren lassen.

**Diensttage ohne Zuordnung** zählen in „Gemischt" mit — die Summe der beiden
Artenreiter ist dann kleiner. Genau deshalb weist „Gemischt" ihre Anzahl aus und
verlinkt auf das Nachtragen; ohne den Hinweis wäre die Abweichung nicht
erklärbar.

Der gewählte Reiter steht im Adressteil hinter dem `#` und bleibt beim Teilen
eines Links erhalten.

Die Kacheln **„Längste Flugstrecke"** (bzw. „Längste Einsatzstrecke"),
**„Längste Einsatzdauer"** und **„Höchster Einsatzort"** sind interaktiv: Zeigt man darauf, leuchten der
zugehörige Karten-Pin (rot) und die zugehörige Tabellenzeile (rosa) auf. Ein
Klick fixiert diese Hervorhebung und springt zur Tabellenzeile — praktisch,
um den Extremwert-Einsatz auf einen Blick zu finden. Ein zweiter Klick auf
dieselbe Kachel oder ein Klick auf eine freie Stelle der Seite löst die
Fixierung wieder.

Jede Zeile der Einsatztabelle führt zum Einsatz; ein Klick auf das
**Dreieck** davor klappt dagegen nur die Unterpunkte auf oder zu.

#### Statistik rechnet nach Diensttag, die Suche nach Einsatzdatum

Das ist der wichtigste Unterschied zwischen den beiden Seiten, und er fällt nur
bei **Diensten über Mitternacht** auf.

Ein Einsatz um 01:30 Uhr, der zu einem am Vortag begonnenen Dienst gehört, zählt
in der **Statistik zum Vortag** — dorthin, wo der Dienst begann. Die
**Einsatzsuche** findet denselben Einsatz unter seinem **echten Datum**, also
dem des Folgetags.

Beides ist beabsichtigt. Eine Dienststatistik, die einen Nachtdienst auf zwei
Kalendertage aufteilt, wäre unbrauchbar; eine Suche, die einen Einsatz nicht
unter dem Tag findet, an dem er stattfand, ebenso. Wer beide Zahlen
nebeneinanderlegt und einen Unterschied sieht, hat also keinen Fehler gefunden.

**Zeitraum-Übersicht oder Suche?** Die beiden Seiten zeigen dieselbe
Einsatztabelle, beantworten aber verschiedene Fragen. Die Zeitraum-Übersicht
ist auf *einen* Monat oder *ein* Jahr festgelegt und liefert dafür Karte und
Kennzahlen — sie beantwortet „wie war dieser Zeitraum?". Die **Suche**
(Abschnitt 4.6) geht über den gesamten Bestand, kennt rund 30 Filter bis hin zu
Diagnose, Besatzung und Alter, hat dafür aber weder Karte noch Kennzahlen — sie
beantwortet „wo war nochmal der eine Einsatz mit …?". Ein Zeitraum lässt sich
in der Suche über „Datum von / bis" nachbilden; Kennzahlen dazu gibt es aber
nur in der Zeitraum-Übersicht.

### 4.5 Diensttag von Hand anlegen

Lief die Uhr an einem Tag nicht, legst du den Diensttag über **+ Diensttag
anlegen** unten in der Einsatztage-Leiste an. Neben dem Datum gehören dort
**Standort** und **Rettungsmittel** hin; daraus ergeben sich Art, Rollen und die
sichtbaren Einsatzfelder. Beides ist freiwillig — ohne sie bleibt der Diensttag
neutral und funktioniert trotzdem —, aber mit ihnen entsteht alles sofort statt
später beim Nachtragen.

Weil mehrere Dienste an einem Kalendertag möglich sind, gehört zu jedem eine
**Uhrzeit des Dienstbeginns**; ohne Angabe gilt 00:00. Nur an ihr lassen sich
zwei Diensttage desselben Datums in der Leiste auseinanderhalten.

### 4.5a Zwei Diensttage zusammenführen

Wurde die App während eines Dienstes versehentlich mehrfach gestartet, sind aus
einem tatsächlichen Dienst mehrere Diensttage geworden. Sie lassen sich wieder
zu einem machen.

Der Einstieg liegt **im Zieltag**: Öffne den Diensttag, der bleiben soll, und
wähle **Aktionen → „Anderen Diensttag aufnehmen"**. Damit ist die Richtung
eindeutig — wichtig, weil der Vorgang **nicht umkehrbar** ist.

Danach in zwei Schritten:

1. **Aus der Liste** der zeitlich benachbarten Diensttage (drei Tage vor und
   nach diesem) den auszuwählen, der aufgenommen werden soll. Zu jedem stehen
   Rettungsmittel, Standort und die Zahl der Einsätze, Ruhesegmente und
   Uhr-Kennungen — daran lassen sich zwei Bruchstücke desselben Dienstes
   auseinanderhalten. Liegt der gesuchte Tag weiter entfernt, korrigiere zuerst
   sein Datum (Abschnitt 4.2a).
2. **Vorschau bestätigen.** Sie zeigt den entstehenden Zeitraum, die Art und
   was alles wandert. Widersprechen sich die beiden Tage bei Rettungsmittel,
   Standort oder Besatzung, wählst du hier, was gelten soll; vorbelegt ist
   immer der Tag, der bleibt.

Danach hängen Einsätze, Ruhesegmente und Uhr-Kennungen am Zieltag, sein Zeitraum
umschließt beide, und Notizen sind aneinandergehängt — nichts wird
überschrieben. Ein späterer Upload der Uhr mit einer Kennung des aufgenommenen
Tages landet von selbst richtig.

**Was nicht geht, und warum:**

- **Luftgebunden und bodengebunden lassen sich nicht zusammenführen.** Ein
  Einsatz mit Windendokumentation verlöre an einem bodengebundenen Diensttag
  seine Felder. Ein Diensttag *ohne* Zuordnung passt dagegen zu beidem und
  übernimmt die Art des anderen.
- **Es gibt keinen Weg zurück und keinen Papierkorb.** Dort läge ein leerer
  Tag, dessen Wiederherstellung die Einsätze nicht zurückholen könnte — sie
  hängen dann am aufnehmenden Tag.
- **Aufteilen gibt es nicht.** Ein versehentlich zusammengeführter Tag lässt
  sich nur von Hand wieder trennen, indem einzelne Einsätze verschoben werden
  (Abschnitt 4.2a).

Eine Rolle, die der gewählte Besatzungssatz nicht besetzt, der andere aber
schon, wird von dort übernommen: Ein eingetragener Name geht nicht verloren.

### 4.6 Suche

Über **Suche** in der Kopfleiste durchsuchst du deinen gesamten Bestand — nicht
nur einen Tag oder einen Zeitraum. Die Trefferliste hat dieselben Spalten wie
die Zeitraum-Übersicht, lässt sich genauso über die Spaltenköpfe sortieren, und
ein Klick auf eine Zeile öffnet den Einsatz. Die Zeile hebt sich dabei hervor,
sobald der Zeiger darüber steht. Ohne Maus geht es auch: Mit der Tabulatortaste
springst du von Zeile zu Zeile, Enter oder Leertaste öffnen den Einsatz.

**Suchbegriff.** Das obere Feld durchsucht Einsatznummer, Name, Geburtsdatum,
Diagnose, Einsatzort, Transportziel, Beschreibung des Einsatzorts,
Bergwacht-Bereitschaft und -Infos, weiteren Notarzt, weitere Rettungsmittel,
Standort, Rettungsmittel, Besatzung und Notizen. Groß- und Kleinschreibung spielt
keine Rolle, Wortteile genügen. Gibst du mehrere Wörter ein, müssen **alle**
vorkommen — aber nicht im selben Feld. „müller kempten" findet also auch einen
Einsatz, bei dem Müller die Besatzung und Kempten das Transportziel ist. Das
Geburtsdatum findest du in beiden Schreibweisen, „12.03.1985" ebenso wie
„1985-03-12".

**Und / Oder / Nicht.** Seit Web 7.0.0 lassen sich Begriffe verknüpfen. Wer das
nicht braucht, merkt nichts davon — ohne Operator verhält sich die Suche exakt
wie bisher.

| Eingabe | Bedeutung |
|---|---|
| `sturz fraktur` | beide Begriffe — das Leerzeichen heißt UND |
| `sturz ODER fraktur` | mindestens einer (`OR` und <code>&#124;</code> ebenso) |
| `bergwacht -winde` | der erste ja, der zweite nicht (`NICHT`, `NOT`, `!` ebenso) |
| `"zwei wörter"` | genau diese Folge, Leerzeichen eingeschlossen |
| `(sturz ODER fraktur) oberstdorf` | Klammern binden zusammen |

Ohne Klammern bindet **UND stärker als ODER**: `a b ODER c` heißt
`(a UND b) ODER c` — die Lesart, die man aus Suchmasken kennt. Ein Minus zählt
nur dann als Ausschluss, wenn es frei vor einem Begriff steht; „St.-Anna" bleibt
ein Wort. Eine halbfertige Eingabe wird **nicht bemängelt** — die Trefferliste
rechnet bei jedem Tastendruck neu, und `(sturz` ist auf dem Weg zu
`(sturz ODER fraktur)` unvermeidlich; sie wird gedeutet, so gut es geht. Die
Kurzhilfe steht aufklappbar direkt unter dem Suchfeld.

**Weitere Filter.** In der linken Spalte — dort, wo auf den anderen Seiten die
Einsatztage stehen. Auf der Suchseite gibt es die nicht, weil es hier gerade um
den Gesamtbestand geht. Die Filter liegen seit Web 7.0.0 in fünf Blöcken, die
danach schneiden, **worüber** gefiltert wird:

| Block | Enthält |
|---|---|
| **Einsatz** | Datum, Alarmzeit, Wochentag, Strecke, Einsatzdauer, Fehleinsatz |
| **Patient** | Alter von / bis |
| **Transport** | Transportart, NA-Begleitung, Transportziel, Sekundärtransport, Schockraum |
| **Beteiligte** | Standort, Rettungsmittel, Art, Besatzung je Rolle, weiteres Rettungsmittel |
| **Bergrettung** | Bergwacht, Bereitschaft, Winde samt Cycles und Luftverladung |

Vorher waren es sechs, darunter ein Block „Werte" mit Alter, Strecke und Dauer —
das war nie ein Gegenstand, sondern eine Datenart. Alter gehört zur Patientin,
Strecke und Dauer zum Einsatz. Die Kurznamen in geteilten Links sind unverändert
geblieben, alte Links funktionieren also weiter.

Jeder Block klappt einzeln auf und zu; beim Öffnen der Seite sind alle
zugeklappt, damit die Spalte ruhig bleibt. Öffnest du einen geteilten Link, gehen genau die Blöcke
auf, in denen etwas gesetzt ist. Alle gesetzten Filter gelten gleichzeitig
(UND); leere Felder schränken nichts ein. Die Auswahllisten für Standort,
Rettungsmittel, Besatzung, Bergwacht-Bereitschaft, weitere Rettungsmittel und Zielklinik
enthalten nur, was in deinem Bestand tatsächlich vorkommt.

**Bergrettung nur, wenn es sie gibt.** Der Block erscheint nur dann, wenn
wenigstens ein Einsatz deines Bestandes eine Winden- oder eine
Bergwacht-Angabe trägt. Wer nie windet und nie mit der Bergwacht arbeitet, hat
diese acht Felder also gar nicht erst in der Spalte stehen — sie könnten dort
nur Filter setzen, die garantiert null Treffer ergeben. Dasselbe gilt seit
Web 7.0.0 für das einzelne Feld **Fehleinsatz**: Es steht in einem Block, der
bleiben muss, und erscheint deshalb feldweise nur, wenn im Bestand einer
dokumentiert ist. Maßgeblich ist der
**gesamte** Bestand, nicht die aktuelle Trefferliste: Die Spalte verändert sich
also nicht, während du filterst. Öffnest du einen geteilten Link, der einen
dieser Filter setzt, bleibt der Block sichtbar — sonst wäre ein Filter gesetzt,
den du nicht finden und nicht zurücknehmen könntest.

Eine Besonderheit:

- **Alarmzeit** darf über Mitternacht gehen. „von 22:00 bis 06:00" findet die
  Nachteinsätze. Eingabe wie überall als `HH:MM`; Ziffern genügen.

**Entfallene Filter (ab Web 5.3.0).** Herkunft, Reanimation,
Reanimations-Ereignis sowie Höhe Einsatzort von/bis gibt es in der Suche nicht
mehr. Die Angaben selbst bleiben vollständig erhalten — sie stehen weiterhin in
der Einsatzansicht und im Export. Ältere geteilte Links funktionieren weiter;
der entfallene Teil wird dabei stillschweigend übergangen.

Unten in der Filterspalte steht **Filter zurücksetzen** und darunter, wie viele
Filter gerade gesetzt sind. Über der Trefferliste steht, wie viele Einsätze von
wie vielen angezeigt werden.

**Wie viele Zeilen auf einmal?** Die Liste zeigt **200 Treffer**; darunter
liegen dann die Schaltflächen **„Weitere 200 anzeigen"** und **„Alle N
anzeigen"**. Sie erscheinen nur, wenn tatsächlich etwas fehlt. Bis Web 5.9.0
gab es keine Grenze — beim Öffnen stand der gesamte Bestand als Tabelle da, und
jeder Tastendruck im Suchfeld baute ihn neu auf; bei einigen tausend Einsätzen
war das eine spürbare Pause. Begrenzt ist allein die **Anzeige**: Gesucht,
gefiltert, sortiert und gezählt wird weiterhin über deinen gesamten Bestand.
Die Zeile über der Tabelle nennt deshalb unverändert die wahre Trefferzahl und
dazu, wie viele davon gerade stehen. Welche 200 das sind, entscheidet die
Sortierung — voreingestellt sind die neuesten zuerst. Sortierst du um, bleibt
eine erweiterte Ansicht erweitert; änderst du einen Filter, fängt die Liste
wieder bei den ersten 200 an. Die Zeitraum-Übersicht ist davon nicht betroffen,
sie zeigt weiterhin jede Zeile.

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

> **Eine Ausnahme, und nur diese eine:** Im **Demo-Konto** (Abschnitt 3.2)
> liegt das Schlüsselmaterial auf dem Server. Anders ließe sich das Konto
> nicht alle 30 Minuten zurücksetzen, ohne dass die verschlüsselten Angaben
> danach unlesbar wären. Dort stehen ausschließlich erfundene Daten — und
> deshalb gehören dort auch keine echten hinein.

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
  der Import gesperrt und der Export nur ohne personenbezogene Angaben
  möglich.

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
Einsätze, Ruhesegmente und Diensttage.

**Der Papierkorb ist Teil der Sicherung.** Was du gelöscht hast und was noch in
der 90-Tage-Frist liegt, steht in der Datei und kommt beim Einspielen wieder
**als Papierkorb** zurück — nicht als aktiver Bestand. Es gibt dafür keinen
Haken zum Abwählen: Eine Sicherung ist ein Abbild, und der Papierkorb ist kein
Abfall, sondern ein Zustand, aus dem sich zurückholen lässt. Vorher war das
anders, und das war der schlechtere Weg: Wer am Tag nach einem versehentlichen
Löschen sicherte und die Datei später zurückspielte, verlor genau das, was er
retten wollte.

Eines ändert sich beim Einspielen: **Die 90 Tage beginnen neu.** Übernommen
wird, *dass* etwas gelöscht war, nicht *wann*. Sonst könnte eine ältere
Sicherung Einträge mitbringen, deren Frist längst abgelaufen ist — der nächste
Aufräumlauf entfernte sie endgültig, ohne dass du sie je zu sehen bekommen
hättest.

Wer die Sicherung in eine Installation **vor** dieser Fassung einspielt, sollte
wissen: Die nimmt die Datei zwar an, kennt den Papierkorb darin aber nicht und
legt seine Einträge als aktive Einsätze und Diensttage an. Dort also
anschließend nachsehen.

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

### 6.1 Sicherung durch die Administration

Seit Web 5.9.0 kann die Administration zusätzlich **Sicherungen aller Konten**
anlegen. Das ist eine Rückfallebene für den Fall, dass in einem Konto etwas
schiefgeht — sie ersetzt dein eigenes Backup nicht.

**Die Administration sieht dabei keine Inhalte.** In der Sicherung stecken die
geschützten Angaben genau so verschlüsselt wie in der Datenbank; lesbar werden
sie erst in einem Browser, der den Schlüssel hat. Die Übersicht in der
Administration zeigt Zeitpunkt, Anzahl der Einsätze, Diensttage und
Ruhezeiten, davon die Anzahl im Papierkorb, und die Dateigröße — mehr nicht.

**Wenn dein Konto weiterbesteht**, spielt die Administration eine solche
Sicherung unmittelbar zurück; du musst nichts tun. Eingespielt wird immer
**ergänzend**: Was schon da ist, bleibt unverändert.

**Wenn dein Konto neu aufgesetzt wurde**, geht das nicht — und zwar aus einem
Grund, der sich nicht umgehen lässt: Die geschützten Angaben der alten Sicherung
hängen am alten Inhaltsschlüssel, und den öffnet allein dein
**Wiederherstellungsschlüssel**. Die Administration gibt die Sicherung dann für
dein Konto frei. Unter **⚙ Einstellungen → „Backup"** erscheint danach ein
Abschnitt *Für dich freigegebene Sicherung*: Dort gibst du deinen
Wiederherstellungsschlüssel ein, dein Browser schlüsselt die Angaben auf deinen
neuen Schlüssel um und spielt sie ein. Solange du eine Freigabe nicht eingelöst
hast, kann die Administration sie zurücknehmen.

**Grenzen des Verfahrens** — sie gehören genannt, bevor man sich darauf verlässt:

- Es ist eine Rückfallebene gegen **selbstverschuldete Probleme im Konto**,
  **kein Schutz gegen Kontoverlust**. Ohne Wiederherstellungsschlüssel ist ein
  neu aufgesetztes Konto **nicht** wiederherstellbar — auch die Administration
  kann daran nichts ändern, weil der Schlüssel nirgends sonst existiert.
- Die einzige Voraussetzung ist deshalb nichttechnisch: **Verwahre deinen
  Wiederherstellungsschlüssel.** Er wird bei der Ersteinrichtung einmalig
  angezeigt und danach nie wieder (Abschnitt 5).
- Es wird **nicht automatisch** gesichert. Wann eine Sicherung entsteht,
  entscheidet die Administration von Hand; es gibt nur eine Erinnerung.
- Je Konto liegen höchstens **drei** Sicherungen. Die vierte verdrängt die
  älteste — nach Alter wird dagegen nie etwas entfernt.
- Wird dein Konto gelöscht, entscheidet die Administration dabei ausdrücklich,
  ob die Sicherungen mitgehen. Die Vorgabe ist: **mitlöschen**.

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
es überschreiben. Außerdem wählst du Rettungsmittel und Standort für Diensttage, die
neu angelegt werden — bestehende Tage bleiben davon unberührt, und beides
lässt sich später je Tag in der Tagesübersicht ändern.

**2. Prüfen und korrigieren.** Die Tabelle zeigt jede Zeile der Datei, nach
Diensttagen gruppiert. **Gelb** ist ein Hinweis (die Zeile geht durch, sieh sie
dir aber an), **Rot** ein Fehler. Jede Zelle ist direkt änderbar; nach jeder
Änderung wird sofort neu geprüft. Fehlerhafte Zeilen blockieren nur sich
selbst: Entweder du korrigierst sie oder du hakst „überspringen" an. Solange
eine Fehlerzeile weder korrigiert noch übersprungen ist, bleibt der Import
gesperrt.

Zwei Sonderfälle werden dabei erkannt:

- **Dubletten.** Ein Einsatz, dessen Einsatznummer schon vergeben ist oder für
  den es an diesem Tag bereits einen Einsatz zur selben Alarmzeit gibt. Der
  Abgleich über die Einsatznummer erkennt seit Web 2.9.0 nur noch Dubletten
  **innerhalb der Diensttage, die in der Importdatei vorkommen** — die Nummer
  liegt verschlüsselt vor und wird dafür lokal in deinem Browser mit den
  vorhandenen Einsätzen abgeglichen. Tag und Alarmzeit bleiben unabhängig
  davon wirksam. Du wählst je Zeile: überspringen (Voreinstellung),
  überschreiben oder trotzdem anlegen. Gelöschte Einsätze im Papierkorb
  zählen bewusst nicht als vorhanden.

  **„Überschreiben" löscht nichts, was die Datei nicht kennt** (seit Web
  5.8.0). Liefert die Datei zu einem Feld nichts, bleibt der gespeicherte Wert
  stehen. Das betrifft die Besatzung, Bergwacht-Infos, den weiteren Notarzt, die
  Notizen, die Höhe des Einsatzortes, die Patientendaten und die Koordinaten der
  Phasen — also genau die Angaben, die ein Export **ohne** personenbezogene
  Angaben leer lässt. Vorher hätte ein solcher Rückimport sie im Bestand
  gelöscht. Die Kehrseite: Ein Feld lässt sich per Import nicht mehr gezielt
  **leeren**; das geht im Einsatzformular.
- **Abweichende Besatzung.** Als Besatzung des Diensttags gilt die des ersten
  Einsatzes des Tages. Steht bei einem späteren Einsatz jemand anderes — der
  klassische Pilotenwechsel im laufenden Dienst —, trägt dieser Einsatz
  automatisch eine abweichende Besatzung (Abschnitt 4.3). Gibt es den Diensttag
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
„Personenbezogene Angaben einschließen" schickt der Server sie gar nicht erst
mit.

Zu wählen sind Zeitraum (Von–Bis oder Alles) und Format.

**Ein Zeitraum braucht beide Grenzen.** Wer nur „Von" ausfüllt und „Bis" leer
lässt, bekommt seit 4.5.1 eine Rückfrage. Vorher wurde die halbe Angabe
stillschweigend übergangen und **der gesamte Bestand** ausgeleitet — ohne
Hinweis, nur mit einer größeren Datei als erwartet. Beide Felder leer heißt
weiterhin „alles"; das ist eine bewusste Angabe.


- **CSV (Standard)**: ein Archiv mit allen Feldern, die das System kennt, dazu
  Diensttage, Ruhezeiten, eine Feldbeschreibung und auf Wunsch die GPX-Tracks. Das
  ist das Format für Auswertungen und für den Rückweg. Es führt je Einsatz auch
  die **Herkunft** (Uhr, manuell, importiert) und den **Bearbeitungsstatus** mit
  — dieselben beiden Angaben, die in der Einsatzansicht als Kennzeichen stehen
  (Abschnitt 4.2). Die beiden Exceltabellen führen sie nicht.
- **Excel (Standard)**: eine Zeile je Einsatz, deutsche Spaltenbeschriftungen,
  alle Zeiten in Ortszeit. Zum Ansehen, Sortieren und Filtern. Ein Diensttag ohne
  Einsatz erscheint als eine Zeile mit Datum und lauter Bindestrichen.
- **Excel (GuteSeele)**: das gewohnte Listenlayout zur Weitergabe an Dritte. Bei
  mehreren Jahren entsteht je Jahr ein Blatt.

Die Namen sind dieselben wie im Auswahlfeld des Imports — was hier
herausgeschrieben wird, lässt sich dort unter demselben Namen wieder
einlesen.

**Personenbezogene Angaben einschließen** ist standardmäßig aus. Der Haken
hieß bis Web 5.7.0 „Patientendaten einschließen" und schaltete auch nur diese
ab. Seit Web 5.8.0 deckt er **alles** ab, was auf einen Menschen zeigt:

- die Patientendaten — Einsatznummer, Name, Geburtsdatum, Alter, Diagnose,
  Einsatzort mit Adresse und Koordinaten,
- die **Besatzung** — die des Diensttags und die tatsächliche des Einsatzes,
  auch im Blatt *Diensttage*,
- **Bergwacht: Namen / Infos** und den **weiteren Notarzt**,
- die **Notizen** von Einsatz und Diensttag,
- die **Koordinaten der Phasen** (Phase 4 ist „Ankunft Einsatzort", Phase 5
  „Ankunft PatientIn" — das *ist* der Einsatzort), die **Höhe des
  Einsatzortes** und die **GPX-Tracks**.

Der letzte Punkt war der Anlass für die Erweiterung: Bis Web 5.7.0 nannte ein
Export „ohne Patientendaten" den Einsatzort trotzdem, nur in einer anderen
Spalte. Wer eine solche Datei weitergab, gab mehr weiter, als der Name der
Option versprach.

**Was ausdrücklich drin bleibt** — damit es nicht als Versehen gelesen wird:

- **Transportziel** und **Bergwacht-Einheit**. Beides sind Einrichtungen, keine
  Personen. Das Transportziel ist zusammen mit Datum und Uhrzeit trotzdem ein
  Hinweis auf eine bestimmte Aufnahme; die Entscheidung, es zu behalten, ist
  bewusst getroffen und steht deshalb hier.
- **Weitere Rettungsmittel** („RTW Kempten") — Organisationskennungen.
- Der **Verlauf einer Reanimation** ohne Angabe, wen sie betraf. Ohne ihn
  entfiele der Grund, Reanimationen überhaupt zu erfassen.
- Die **Zeitpunkte** der Phasen. Sie tragen Alarmzeit, Endzeit und Dauer.
- Der Haken **abweichende Besatzung**. Er sagt nur, *dass* sie abwich — sonst
  wäre nicht mehr zu erkennen, dass die leeren Namensspalten leer *gemacht*
  wurden.

Wird der Haken gesetzt, kommt vorher ein Hinweis: Ab dem Speichern schützt die
Verschlüsselung dieser Anwendung die Daten nicht mehr, sie stehen lesbar in der
Datei. Ist die Verschlüsselung gerade gesperrt (nach einem Neustart des
Browsers), lässt sich der Haken nicht setzen; ein Export ohne personenbezogene
Angaben bleibt möglich. Über „Entsperren“ im Hinweis daneben lässt sich die
Sperre aufheben (siehe Abschnitt 5).

**Der Spaltensatz bleibt gleich.** Beim CSV bleiben die betroffenen Spalten
stehen und leer — ein Programm, das die Datei einliest, muss deshalb nicht zwei
Fälle unterscheiden; `felder.csv` sagt je Feld, ob es unter die Schranke fällt.
Bei **Excel (Standard)** entfallen die Spalten dagegen ganz: Dort liest ein
Mensch, und eine dauerhaft leere Spalte wäre nur Ballast. Bei **Excel
(GuteSeele)** bleiben sie leer stehen, weil das Layout mit dem Empfänger
vereinbart ist.

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

**Mit Passwort schützen** ist seit Web 5.7.0 **vorbelegt** — der Schutz ist der
Normalfall, nicht die Ausnahme. Abwählen bleibt jederzeit möglich; nur muss man
es jetzt bewusst tun statt es zu vergessen. Verschlüsselt wird mit AES-256,
mindestens zehn Zeichen, dieselbe Prüfung wie beim Anmeldepasswort. Anders als
beim Backup wird hier **nicht** angeboten, das Kontopasswort zu verwenden: Eine
Exportdatei ist zum Weitergeben gedacht.

Exportierst du **ohne** personenbezogene Angaben, erscheint unter dem Kästchen
ein Hinweis. Bis Web 5.7.0 sagte er, die Datei sei trotzdem personenbezogen —
das stimmte damals und stimmt seit Web 5.8.0 nicht mehr. Was bleibt, sind
**Betriebsangaben**: Einsatzzeiten, Transportziele, weitere Rettungsmittel und
der Verlauf einer Reanimation. Kein Personenbezug, aber auch nichts, was ohne
Weiteres in fremde Hände gehört. Der Schutz schaltet sich deshalb **nicht** von
selbst ab; die Entscheidung bleibt bei dir. Zum Öffnen wird
ein Zusatzprogramm gebraucht: **7-Zip** unter Windows, **Keka** oder **The
Unarchiver** unter macOS — der Windows-Explorer und das macOS-Archivprogramm
können solche Archive nicht öffnen. Beide Programme sind kostenlos.

Das Passwort wird nirgends gespeichert und lässt sich nicht zurücksetzen. Geht
es verloren, lässt sich die Datei **nicht mehr öffnen** — die Daten darin sind
dann endgültig nicht mehr lesbar. Es gibt keinen Weg daran vorbei, auch nicht
über die Anwendung.

**Der Dateiname sagt, was drin ist.** Er ist so aufgebaut:

```
luftrettungsdokumentation_export_06-08-2026_standard_mit-pers_verschl_philipp-mueller.zip
```

Der Reihe nach: der Tag der Erstellung, das gewählte Format (`standard`,
`guteseele` oder `csv`), ob personenbezogene Angaben enthalten sind
(`mit-pers` oder `ohne-pers`), ob die Datei verschlüsselt ist (`verschl` oder
`unverschl`) und
zuletzt das Konto, aus dem der Export stammt — der Name aus den Einstellungen,
und wenn dort keiner steht, die E-Mail-Adresse. So ist auch Wochen später und
in einem Ordner voller Exporte zu sehen, welche Datei vorsichtig zu behandeln
ist, ohne dass man sie öffnen muss.

Zwei Feinheiten:

- Die Angabe zur Verschlüsselung gilt immer für **genau diese Datei**. Ein
  passwortgeschütztes Excel steckt in einem Archiv `…_verschl.zip`, die
  Tabelle darin heißt `…_unverschl.xlsx` — denn sobald sie entpackt ist, liegt
  sie offen.
- **Ältere Dateien tragen `mit-pat` bzw. `ohne-pat`.** Sie behalten ihren
  Namen, und er ist für sie auch richtig: Sie stammen aus einer Zeit, in der
  der Haken nur die Patientendaten abschaltete. Eine alte Datei `ohne-pat`
  enthält also Besatzungsnamen und Einsatzkoordinaten, eine neue `ohne-pers`
  nicht. Genau dafür wurde der Marker umbenannt.
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

Einsätze und ganze Diensttage landen beim Löschen zunächst im **Papierkorb** und
bleiben dort **90 Tage** wiederherstellbar; danach räumt das System sie
automatisch endgültig weg.

- **Einsatz löschen:** in der Einsatzansicht über „Löschen". Es erscheint eine
  Seite, die vorher zeigt, was mitgeht (Phasen, Reanimationen, Trackpunkte).
- **Diensttag löschen:** unten auf der Tagesübersicht. Achtung — das entfernt
  **den kompletten Tag**: alle Einsätze, Ruhesegmente, Tracks, Reanimationen
  und die Diensttag-Angaben. Beim Wiederherstellen kehrt alles gemeinsam zurück.
- **Papierkorb:** eigene Seite, erreichbar über das Papierkorb-Symbol unten in
  der Einsatztage-Leiste (ausgegraut, solange nichts darin liegt) — je eine
  Tabelle für gelöschte Diensttage und einzeln gelöschte Einsätze, mit
  „Wiederherstellen" und „Endgültig löschen". Endgültiges Löschen fragt noch
  einmal nach und ist unwiderruflich.

Solange etwas im Papierkorb liegt, nimmt der Server Nachlieferungen der Uhr
für diese Einsätze zwar entgegen, verwirft sie aber — gelöschte Einsätze
wachsen also nicht wieder an. Beim endgültigen Löschen kommt die Referenz auf
eine Sperrliste, sodass die Uhr sie nicht neu anlegt.

**Ein Diensttag im Papierkorb nimmt keine Änderungen an.** Trägst du Rettungsmittel,
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

Stammdaten (Standorte, Rettungsmittel, Besatzung, weitere Rettungsmittel, Bergwacht,
Transportziele) und
Geräte werden direkt nach einer kurzen Rückfrage gelöscht — sie sind schnell
wieder angelegt. **Bereits dokumentierte Diensttage bleiben davon unberührt:**
Besatzungsnamen und Bergwacht-Angaben stehen ohnehin als Text im Diensttag, und
beim Löschen eines Rettungsmittels oder eines Standorts wird dessen Name vorher in die
betroffenen Diensttage übernommen. Ein **Nutzerkonto** zu löschen verlangt
zusätzlich das Abtippen der E-Mail-Adresse und geht nicht über den Papierkorb.

---

## 9. Stammdaten (Standorte und Rettungsmittel)

Deine Vorbelegungen liegen seit Web 7.0.0 hinter **zwei** Menüpunkten. Der
Schnitt folgt der Tätigkeit:

| Menüpunkt | Inhalt |
|---|---|
| **⚙ Einstellungen → Standorte** | Eigene Standorte anlegen und bearbeiten, **vordefinierte** Standorte auswählen. Und sonst nichts. |
| **⚙ Einstellungen → Rettungsmittel** | Was an den ausgewählten Standorten hängt: Rettungsmittel samt Rollen und Fähigkeiten, Besatzungs-Vorbelegungen, Zielkliniken, weitere Rettungsmittel, Bergwacht-Bereitschaften. |

Bis Web 6.3.0 hieß beides zusammen „Standortdaten" — der Name passte auf keinen
der beiden Teile. Ein alter Link (`?t=stammdaten`) führt weiterhin zu
„Standorte".

Unter „Rettungsmittel" steht **je Standort ein Block**, darin je Datenart ein
eigener aufklappbarer Abschnitt mit der Zahl der Einträge im Kopf. Alles startet
zugeklappt. Nach dem Speichern öffnet sich der Weg bis zu der Stelle wieder, an
der du getippt hast, und die Seite springt dorthin.

### 9.1 Standorte, Rettungsmittel, Besatzung, Bergwacht

**Der Standort ist der Anker.** An ihm hängen Rettungsmittel, Zielkliniken,
weitere Rettungsmittel, Bergwacht-Bereitschaften und Besatzungs-Vorbelegungen —
jeder Eintrag gehört genau **einem** Standort. Eine Zielklinik, die von zwei
Standorten angefahren wird, ist deshalb zweimal anzulegen. Das ist der Preis
dafür, dass in den Auswahllisten genau die Einträge des Standorts stehen, der
am Diensttag hinterlegt ist, und sonst nichts.

Zu einem **Standort** lassen sich Koordinaten hinterlegen — freiwillig. Sie sind
die Quelle des Abfahrtorts „Standort" (Abschnitt 4.3). Erfasst werden sie wie
der Einsatzort: Adresse suchen, Koordinatenpaar oder Plus Code eintippen, der
Vorschlag darunter übernimmt sie.

Ein **Rettungsmittel** ist entweder **luftgebunden** oder **bodengebunden**.
Diese Wahl entscheidet über alles Weitere:

| Art | Wählbare Rollen | Fähigkeiten |
|---|---|---|
| Luftgebunden | Pilot 1, Pilot 2, HEMS-TC, Flugretter, Sonstige | Winde, Bergwacht — zwei getrennte Häkchen |
| Bodengebunden | Fahrer, Praktikant, Sonstige | keine |

**Die Art ist Pflicht und nicht vorbelegt** (seit Web 7.0.0). Vorher stand
„luftgebunden" von selbst da — an einem Standort mit NEF war das die falsche
Vorgabe, die niemand bemerkt, und sie fiel erst auf, wenn im Einsatzformular
Windenfelder erschienen. Ohne Auswahl wird die Eingabe abgewiesen. Die Rollen
darunter erscheinen erst, wenn die Art feststeht, und bleiben freiwillig: Ein
Rettungsmittel ohne angehakte Rolle lässt sich anlegen.

Angehakt werden die Rollen, die tatsächlich besetzt werden. Die Notärztin selbst
ist keine Rolle — sie ist die Nutzerin. Winde und Bergwacht sind zwei getrennte
Häkchen, weil ein Hubschrauber eine Winde führen kann, ohne in einer
Bergwachtkooperation zu stehen, und umgekehrt.

**Die Besatzungspflege zeigt nur die Rollen, die es am Standort gibt.** Eine
Rolle erscheint dort, sobald mindestens ein Rettungsmittel dieses Standorts sie
führt. Vorher standen an einem reinen NEF-Standort vier leere Flugrollen mit
vier Eingabezeilen. Hast du zu einer Rolle bereits Einträge und löschst später
das zugehörige Rettungsmittel, bleibt sie sichtbar — sonst kämst du an deine
eigenen Einträge nicht mehr heran.

**Änderungen an den Stammdaten wirken nur in die Zukunft.** Beim Anlegen eines
Diensttags werden Art, Rollensatz, Fähigkeiten, Bezeichnungen und
Standortkoordinaten **eingefroren**. Wird ein Rettungsmittel später umbenannt,
umgebaut oder gelöscht, ändert sich an bereits dokumentierten Diensttagen
nichts — auch nicht bei einem Tippfehler im Namen. Ein Diensttag ist ein
abgeschlossener Dienstnachweis, kein Blick auf den heutigen Stammdatenbestand;
wer eine alte Bezeichnung korrigieren will, tut das am Diensttag selbst.

Mit **„★ Standard"** markiertes Rettungsmittel und Standort werden bei neuen
Diensttagen vorbelegt — das gilt auch für vom Admin zentral hinterlegte
Einträge (s. 9.4). Bei **Standorten** ließ sich das bis Web 6.3.0 nur für eigene
Einträge setzen; die Schaltfläche fehlte bei den vordefinierten, obwohl der
Server es längst erlaubte. Ein Konto, das ausschliesslich mit vordefinierten
Standorten arbeitet — der Regelfall an einer Station —, konnte damit gar keine
Vorbelegung setzen. Jetzt steht sie bei jedem **ausgewählten** vordefinierten
Standort. (Nicht ausgewählte bleiben aussen vor: Was nicht in den Auswahllisten
steht, kann auch keine Vorbelegung sein.)

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

### 9.3 Transportziele (Zielkliniken)

Vorbelegung für das Feld **Zielklinik** im Einsatz. Anders als bei den
Rettungsmitteln bleibt das Feld dort ein einfaches Textfeld mit Vorschlagsliste
(Tastatur-Pfeiltasten bzw. Antippen) — Freitext ist weiterhin uneingeschränkt
möglich.

Zu jeder Zielklinik lassen sich **Koordinaten** hinterlegen, auf denselben drei
Wegen wie beim Einsatzort (Adresssuche, Koordinatenpaar, Plus Code) und auf drei
Ebenen: zentral durch die Administration, hier im eigenen Konto und einmalig am
einzelnen Einsatz. Wird ein Vorschlag mit hinterlegten Koordinaten übernommen,
sind sie vorbelegt und lassen sich am Einsatz überschreiben.

Koordinaten sind **freiwillig**. Ohne sie bleibt die Zielklinik ein gültiger
Eintrag; es entstehen lediglich kein Pin und keine Luftlinie. Eine spätere
Korrektur wirkt nur auf neue Einsätze — am Einsatz ist die Koordinate
eingefroren.

### 9.4 Vordefinierte (systemweite) Stammdaten

Der Admin kann alle sechs Bereiche zusätzlich **systemweit** hinterlegen (siehe
Abschnitt 11). **Vordefinierte Standorte erscheinen erst in deinen
Auswahllisten, wenn du sie unter „Standorte → Vordefinierte Standorte" angehakt
hast** — sonst stünden in einem gemeinsam genutzten System alle Standorte aller
Häuser in jeder Liste. Der Block hieß bis Web 6.3.0 „Zentrale Standorte
auswählen"; „zentral" beschrieb die Verwaltung, nicht den Nutzen.
Abwählen entfernt keine Daten; bereits dokumentierte Diensttage bleiben
unverändert.

Solche Einträge erscheinen mit dem Kennzeichen **„zentral"**, stehen automatisch in allen Vorbelegungen zur
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

Unter **„Standorte systemweit"** und **„Rettungsmittel systemweit"** pflegt der
Admin dieselben sechs Bereiche wie eine NutzerIn unter Standorte und
Rettungsmittel (9.1–9.3), jedoch für **alle** Konten gemeinsam (siehe 9.4). Die
Zweiteilung ist seit Web 7.0.0 dieselbe wie in der Kontoansicht; vorher hieß
beides zusammen „Zentrale Stammdaten". Namensgleiche Einträge werden auch hier
abgelehnt; existieren bereits persönliche Einträge mit demselben Namen bei
einzelnen NutzerInnen, weist ein Hinweis darauf hin (keine Blockade).

Unter **„Sicherungen"** stehen alle vorhandenen Sicherungen **als Tabelle** —
eine Zeile je Sicherung mit Zeitpunkt, Herkunft, Umfang und Zustand. Die
Formulare zum Einspielen, Freigeben und Löschen klappen hinter der jeweiligen
Zeile auf. Bis Web 6.3.0 stand je Sicherung ein eigener Kasten mit vollständigem
Formular; bei mehreren Konten waren das schnell mehrere Bildschirmseiten. An den
Rückfragen ändert das nichts: Vor dem Einspielen ist weiterhin die E-Mail-Adresse
des Zielkontos abzutippen.

Nach Code-Updates mit Datenbank-Änderungen einmal **`update.php`** aufrufen
(siehe Technik-Doku, Betrieb). Die Seite läuft **zweistufig**: Der Aufruf zeigt
nur an, was anstünde, und ändert nichts; erst der Knopf **„Updates jetzt
anwenden"** führt sie aus. Vorher eine Sicherung erstellen — Migrationen können
Spalten und die darin enthaltenen Daten unwiderruflich entfernen.

Seit Web 7.0.0 steht der **Zustand zuerst** (Schlüsselableitung, Umgebung,
Aufräumjob) und die Updatetabelle darunter — das ist die Auskunft, wegen der man
die Seite im Betrieb öffnet. Die Tabelle steht **auf dem Kopf**: neueste Einträge
oben, mit einer Spalte **„Web"**, die die Fassung nennt, mit der das Update
ausgeliefert wurde. Der Startknopf steht **über** der Tabelle. Ausgeführt werden
die Updates weiterhin in ihrer ursprünglichen Reihenfolge — sie bauen
aufeinander auf; gedreht ist allein die Anzeige.

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

**Wenn die Kopplung nicht klappt**, sagt die Uhr seit Version 1.7.0, woran es
liegt und was hilft — in zwei kurzen Zeilen:

| Meldung auf der Uhr | Was zu tun ist |
|---|---|
| „Zu viele Geräte" | Im Web ein nicht mehr genutztes Gerät löschen, dann neuen Code erzeugen. |
| „Zu viele Versuche" | Kurz warten. Weiteres Eintippen verlängert die Sperre nur. |
| „Code ungültig/abgelaufen" | Im Web einen neuen Code erzeugen — er gilt 10 Minuten und nur einmal. |
| „Keine Verbindung" | Telefon in Reichweite? Bluetooth an? Der Code ist noch gültig. |

Vorher stand dort in all diesen Fällen nur „Kopplung fehlgeschlagen" mit einer
Zahl.
