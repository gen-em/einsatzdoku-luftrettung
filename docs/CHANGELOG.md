# Changelog — Einsatzdoku

Format nach [Keep a Changelog](https://keepachangelog.com/de/).

**Weboberfläche** und **Uhr-App** werden getrennt gezählt, weil sie unabhängig
voneinander ausgeliefert werden: `server/version.php` bzw.
`watch/source/Const.mc`. Die Web-Version steht in der Fußzeile jeder Seite und
hängt an allen Stylesheet- und Skript-Adressen — nach einem Update lädt der
Browser sie dadurch von selbst neu. Die Uhr-Version steht auf der Sync-Seite.
Die Stände 1.0 bis 1.2 unten sind die frühen Spezifikations-Stände des
Gesamtprojekts, vor der getrennten Zählung.

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

### Geändert — Schnellmenü umsortiert, Reanimations-Bedienung
- **Schnellmenü der Hauptseite:** Beim Öffnen (lang START) ist jetzt die
  **Einsatzübersicht** vorausgewählt; ein Schritt nach oben liegt „Einsatztag
  beenden", nach unten folgen die Phasen 2, 3, 4 … Das Endlos-Scrollen durch
  alle Punkte bleibt erhalten.
- **Reanimation, kurz START:** löst bei laufender Rea kein Ereignis mehr aus,
  sondern öffnet das Untermenü. Ohne laufende Rea beginnt kurz START weiterhin
  die Reanimation.
- **Reanimation, lang START:** startet den 2:00-Countdown neu (bisher öffnete
  der lange Druck das Untermenü). Der Neustart steht zusätzlich als erster
  Menüpunkt „Timer neu starten" bereit.
- **Countdown-Neustart auch bei Defibrillation:** Wie die Rhythmuskontrolle
  setzt jetzt auch die Defibrillation (Menüauswahl) den 2:00-Countdown neu an.
- **Rea-Untermenü im Design des Schnellmenüs:** gleiche Zeilenhöhe und
  Darstellung wie auf der Hauptseite (fünf sichtbare Zeilen, gefüllte
  Auswahl); die Ereignisfarben bleiben erhalten, die Gruppen-Trennlinien
  entfallen.

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
