# Einsatzdoku — Handbuch

*Stand: 23.07.2026 · Für die technische Struktur siehe `Technik.md`, für
Änderungen `CHANGELOG.md`.*

## 1. Was ist die Einsatzdoku?

Die Einsatzdoku dokumentiert Hubschraubereinsätze direkt vom Handgelenk: Eine
Garmin-Uhr-App erfasst Einsatzphasen mit Zeitstempeln, GPS-Tracks und
Reanimations-Ereignisse und lädt alles automatisch auf einen eigenen Server.
Die Web-Oberfläche (luftrettung.net) zeigt Flugtage mit Karte, Einsatz-Details
und Reanimations-Protokollen — und erlaubt Nachtragen und Bearbeiten von Hand.

**Patientendaten sind geschützt.** Nachname, Vorname, Geburtsdatum, Alter,
Diagnose und der Einsatzort werden **Ende-zu-Ende-verschlüsselt** gespeichert:
Der Browser ver- und entschlüsselt sie mit einem Schlüssel aus deinem
Login-Passwort, der Server sieht nur Chiffretext (Abschnitt 5). Notizen und
Freitextfelder sind davon **nicht** erfasst — dort gehören keine
Patientendaten hinein.

---

## 2. Die Uhr-App

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

Zwei Timer: oben klein und **lila** die Gesamtdauer seit Rea-Beginn, mittig
groß der **2:00-Countdown** für den Zyklus. Bei 0:00 vibriert die Uhr zweimal
kurz; der Countdown bleibt rot auf 0:00 stehen, bis er neu gestartet wird.

| Taste | Wirkung |
|---|---|
| kurz START | Reanimation **beginnen** / Countdown manuell neu starten |
| lang UP | **Adrenalingabe** dokumentieren |
| lang DOWN | **Rhythmuskontrolle** dokumentieren (setzt Countdown auf 2:00) |
| lang START | Untermenü öffnen |
| kurz UP/DOWN | Oberfläche wechseln (Timer laufen weiter) |
| BACK | zurück zur Hauptanzeige (Timer laufen weiter) |

**Untermenü** (farbcodiert, endlos scrollbar): Defibrillation, Intubation,
Amiodaron, Sonographie, ROSC, Tod — je ein Zeitstempel; **Übersicht** zeigt
alle Zeiten der laufenden Rea; **„Rea beenden"** (rot) schließt die
Reanimation nach Sicherheitsabfrage. Danach startet **kurz START** eine
*neue* Reanimation — mehrere pro Einsatz sind möglich, jede bekommt im Web
ihre eigene Tabelle. Bei Einsatzende wird eine laufende Rea automatisch
geschlossen.

### 2.4 Datenübertragung

Die Uhr lädt selbstständig hoch: Einsätze bei Phase 10, den Ruhe-Track etwa
stündlich, den Rest beim Dienstende. Ohne Verbindung puffert die Uhr sicher im
Speicher und sendet später nach — gelöscht wird lokal erst, wenn der Server
den vollständigen Empfang bestätigt hat. Den aktuellen Stand zeigt die
**Sync-Seite**.

---

## 3. Die Web-Oberfläche — Überblick

Die Kopfleiste zeigt links die GenEM-Bildmarke mit „Einsatzdokumentation
Luftrettung – *Name*" (Name im Profil setzbar, sonst E-Mail), rechts die Menüs
**Übersicht**, **Administration** (nur Admin) und **⚙ Einstellungen** (Profil,
Standortdaten, Backup, Geräte; Abmelden fragt sicherheitshalber nach). Nach
30 Minuten ohne Aktivität meldet das System automatisch ab. Die Kopfleiste
bleibt beim Scrollen oben stehen.

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
  dicker.
- **Tabelle** der Einsätze: Nr., Beginn, Dauer, **Einsatzort** (Ortschaft aus
  der verschlüsselten Adresse), **Alter**, **Diagnose**, Winde, Bergwacht,
  Sekundärtransport, Kilometer. Alle Spalten sind zentriert und in
  abwechselnden Zeilenfarben; ein Klick auf eine Zeile öffnet den Einsatz, ein
  Klick auf einen Spaltenkopf sortiert. Die Dauer rechnet von der Alarmierung
  bis Phase 9; fehlt Phase 9, steht dort „kein Ende".
- **„+ Einsatz nachtragen"** öffnet das Eingabeformular für diesen Tag,
  **„Tag löschen"** entfernt den gesamten Flugtag (Abschnitt 7).

### 4.2 Einsatzansicht

Titel „Einsatz N · Uhrzeit" (N = Nummer des Tages nach Alarmierungszeit),
darunter **Bearbeiten** und **Löschen**. Es folgt eine Karte mit dem Track
(Start grün, Ende rot) und — sofern vorhanden — dem Einsatzort-Pin aus den
lokal entschlüsselten Koordinaten. Auf dem Track sitzen **Phasen-Nummern** an
den GPS-Positionen der Zeitstempel. Zeigt man auf eine Phasenzeile oder einen
Kartenpunkt, leuchtet das Gegenstück orange auf (am Handy: antippen).

Die geschützten Angaben — **Name, Geburtsdatum, Alter, Diagnose, Einsatzort** —
erscheinen mit einem Schloss-Symbol 🔒 in der Feldliste und **nur hier**, nicht
in den Übersichten. Darunter die Phasen-Tabelle und je Reanimation eine eigene
Zeiten-Tabelle.

### 4.3 Einsätze nachtragen und bearbeiten

Das Formular dient beidem. Phasen werden als Zeilen erfasst (Phase wählen,
Uhrzeit eintragen, Zeilen hinzufügen/entfernen — auch dieselbe Phase mehrfach).
**In chronologischer Reihenfolge eintragen**; Zeiten nach Mitternacht werden
automatisch dem Folgetag zugerechnet.

**Geschützte Angaben** (Abschnitt 6) stehen gebündelt unter „PatientInnendaten
& Einsatzort". Der Einsatzort hat ein Suchfeld: Ab drei Buchstaben erscheinen
Adressvorschläge (OpenStreetMap); die Auswahl eines Vorschlags speichert die
Koordinaten und setzt den Pin auf den Karten. Freitext ohne Vorschlag geht
auch — dann ohne Pin.

Dazu die weiteren Zusatzfelder: Einsatznummer, Transportziel, Beschreibung
Einsatzort (nur in der Detailansicht), **Windeneinsatz** (Haken öffnet Cycles,
Cycles mit Patient, Luftverladung), **Bergwacht** (Haken öffnet Bereitschaft
aus den Stammdaten plus Namen/Infos), Sekundärtransport, Schockraum, Anderer
Notarzt, **Weitere Rettungsmittel** (Abschnitt 8.2) und Notizen.

Beim Bearbeiten eines **Uhr-Einsatzes** gilt: Nach dem Speichern ist er als
„manuell" markiert — die Uhr überschreibt ihn dann nicht mehr (nur der
GPS-Track wird weiter ergänzt). Das Formular weist vorher darauf hin.
Reanimations-Zeiten lassen sich im Formular derzeit nicht erfassen.

### 4.4 Einsatztage-Leiste, Jahres- und Monatsübersicht

Die Leiste links ist nach **Jahr → Monat → Tage** gruppiert. Es ist immer nur
ein Jahr geöffnet und darin ein Monat (standardmäßig der jüngste); ein anderes
Jahr anzuklicken schließt das vorherige automatisch. Springst du auf einen Tag
in einem anderen Zeitraum, klappt die Leiste automatisch dorthin auf.

Ein Klick auf die **Jahreszahl** oder den **Monatsnamen** öffnet eine Tabelle
aller Einsätze dieses Zeitraums — mit Datum statt Tagesnummer, ohne Karte,
sortierbar. Die Kopfzeile nennt Anzahl der Einsätze, Flugtage und
Gesamtkilometer; jede Zeile führt zum Einsatz. Ein Klick auf das **Dreieck**
davor klappt dagegen nur die Unterpunkte auf oder zu.

### 4.5 Flugtag von Hand anlegen

Lief die Uhr an einem Tag nicht, legst du den Flugtag über **+ Flugtag
anlegen** unten in der Einsatztage-Leiste an. Danach lassen sich Maschine,
Besatzung und nachgetragene Einsätze wie gewohnt erfassen.

---

## 5. Verschlüsselung der Patientendaten (Pflicht)

Nachname, Vorname, Geburtsdatum, Alter, Diagnose und Einsatzort sind
**Ende-zu-Ende-verschlüsselt**: Der Browser ver- und entschlüsselt mit einem
Schlüssel aus deinem Login-Passwort; der Server speichert nur Chiffretext. Es
gibt kein zweites Passwort und keinen Schalter — die Verschlüsselung ist
Pflicht.

**Ersteinrichtung:** Beim ersten Anmelden führt das System einmalig auf die
Einrichtungsseite. Dort wird dein **Wiederherstellungsschlüssel** erzeugt und
**nur dieses eine Mal** angezeigt — ausdrucken und sicher ablegen, dann per
Haken bestätigen.

**Unbedingt wissen:**

- Normales Passwort-Ändern (mit altem Passwort) ist völlig unkritisch — die
  Daten bleiben ohne Zutun lesbar.
- Bei „Passwort vergessen" verlangt die Seite mit dem neuen Passwort zugleich
  den **Wiederherstellungsschlüssel**. Damit übernimmt der Browser die
  verschlüsselten Angaben auf das neue Passwort, sodass nach dem Zurücksetzen
  sofort alles lesbar ist. Passt der Schlüssel nicht, wird **nichts** geändert
  — das alte Passwort gilt weiter. **Ohne den Schlüssel sind die Angaben
  unwiederbringlich verloren**, auch Admins können nicht helfen (deshalb gibt
  es keine Admin-Passwortvergabe).
- Verschlüsselte Felder sind serverseitig nicht durchsuchbar; der Schutz wirkt
  gegen Datenbank-Diebstahl und Mitleser, prinzipbedingt nicht gegen einen
  vollständig übernommenen Server.
- Zeigt eine Seite „gesperrt", genügt ab- und neu anmelden bzw. der
  Entsperr-Link.

**Alter aus Geburtsdatum:** Das Alter berechnet die Anwendung aus dem
Geburtsdatum, bezogen auf den **Einsatztag**, nicht auf heute — ein Einsatz von
vor Jahren zeigt weiterhin das damalige Alter. Bei gesetztem Geburtsdatum ist
das Feld gesperrt und mit „aus Geburtsdatum" gekennzeichnet. Ist kein
Geburtsdatum bekannt (bei unbekannten Personen der Regelfall), bleibt das Alter
von Hand eintragbar. **Name und Geburtsdatum erscheinen bewusst nur in der
Einsatzansicht**, nie in den Übersichten.

---

## 6. Backup

Unter **⚙ Einstellungen → „Backup"** lädst du alle deine Daten als einzelne
verschlüsselte Datei (`.edbak`) herunter — Passwort frei wählbar, mindestens
8 Zeichen, wird nirgends gespeichert (ohne Passwort ist die Datei wertlos).

Ver- und Entschlüsselung passieren **in deinem Browser**; der Server sieht die
Inhalte nie. Deshalb lässt sich ein Backup auch **in ein anderes Konto**
einspielen: Beim Import werden die geschützten Angaben automatisch mit dem
Schlüssel des Zielkontos neu verschlüsselt.

Der Import ergänzt nur, was fehlt — Vorhandenes bleibt unangetastet, und
mehrfaches Einspielen derselben Datei ist gefahrlos. Während Export und Import
zeigt eine Statuszeile den Fortschritt und am Ende die Zahl der übernommenen
Einsätze, Ruhesegmente und Flugtage.

Der Aufbau der Datei ist in `docs/Backup-Format.md` vollständig beschrieben —
sie lässt sich damit auch ohne dieses Programm entschlüsseln.

---

## 7. Löschen und Papierkorb

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
wachsen also nicht wieder an. Erst beim endgültigen Löschen wird die Referenz
dauerhaft gesperrt, sodass die Uhr sie nicht neu anlegt.

Alle Rückfragen erscheinen als Fenster **innerhalb der Seite**, nicht als
Browser-Dialog. Das ist Absicht: Bei Browser-Dialogen lässt sich „keine
weiteren Dialoge dieser Seite anzeigen" ankreuzen — danach würden Löschungen
kommentarlos durchlaufen. Seiteneigene Fenster kann der Browser nicht
abschalten.

Stammdaten (Standorte, Maschinen, Besatzung, Rettungsmittel, Bergwacht) und
Geräte werden direkt nach einer kurzen Rückfrage gelöscht — sie sind schnell
wieder angelegt. **Bereits dokumentierte Flugtage bleiben davon unberührt:**
Besatzungsnamen und Bergwacht-Angaben stehen ohnehin als Text im Flugtag, und
beim Löschen einer Maschine oder eines Standorts wird deren Name vorher in die
betroffenen Flugtage übernommen. Ein **Nutzerkonto** zu löschen verlangt
zusätzlich das Abtippen der E-Mail-Adresse und geht nicht über den Papierkorb.

---

## 8. Stammdaten (Standortdaten)

Unter **⚙ Einstellungen → „Standortdaten"** pflegst du deine Vorbelegungen. Die
fünf Bereiche sind aufklappbare Abschnitte und starten zugeklappt.

### 8.1 Standorte, Hubschrauber, Besatzung, Bergwacht

Standorte, Hubschrauber (Kennung plus Häkchen, welche Rollen an Bord sind),
Namenslisten je Rolle und Bergwacht-Bereitschaften. Am Flugtag wählst du
Maschine und Standort dann per Dropdown; die beim Hubschrauber angehakten
Rollen erscheinen als Besatzungs-Dropdowns mit deinen Vorbelegungen. Mit
„Als Standard" (★) markierte Maschine und Standort werden bei neuen Flugtagen
vorbelegt.

### 8.2 Andere Rettungsmittel

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

---

## 9. Geräte

Unter **⚙ Einstellungen → „Geräte"** verwaltet jede/r die eigenen Uhren:
**„Gerät anlegen"** erzeugt Geräte-ID und API-Schlüssel — der Schlüssel wird
**nur einmal** angezeigt, also sofort notieren bzw. eintragen. **Deaktivieren**
sperrt den Upload sofort (z. B. bei Verlust); alle bereits hochgeladenen Daten
bleiben erhalten, und **Aktivieren** schaltet dasselbe Gerät wieder frei.

---

## 10. Administration (nur Admin)

NutzerInnen anlegen (verschickt automatisch den Passwort-Setz-Link) und löschen
(**Achtung:** entfernt alle Daten der Person unwiderruflich). Ein Klick auf eine
NutzerIn öffnet die Editierseite: Rolle wechseln, E-Mail ändern und die
verbundenen Geräte einsehen (aktivieren/deaktivieren/löschen — Löschen lässt
hochgeladene Daten bestehen).

Nach Code-Updates mit Datenbank-Änderungen einmal **`update.php`** aufrufen
(siehe Technik-Doku, Betrieb).

---

## 11. Eine neue Uhr einrichten (Kurzanleitung)

1. App auf die Uhr laden (siehe `Technik.md`). Die Server-Adresse trägst du in
   Garmin Connect ein; die Domain genügt (z. B. `luftrettung.net`).
2. Im Web unter **⚙ Einstellungen → „Geräte" → „Kopplungscode erzeugen"** —
   der 5-Zeichen-Code ist 60 Minuten gültig und einmal verwendbar.
3. Auf der Uhr auf der Sync-Seite **START halten**, den Code eintippen und
   bestätigen — die Uhr meldet „Gekoppelt ✓" und ist einsatzbereit. Das Gerät
   erscheint im Web in der Geräteliste.
4. Alternative ohne Code: Gerät manuell anlegen und Geräte-ID/API-Schlüssel
   in die Connect-Einstellungen eintragen (nur nötig, wenn die Kopplung nicht
   möglich ist).
