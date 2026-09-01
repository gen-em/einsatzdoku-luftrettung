# Konzept S4 — Handy- und Uhr-Client (Android/Wear OS), Schneidewerkzeug und GPX-Import (Zwischenpaket)

**Stand:** 31.08.2026 — **freigegeben** (Freigabe des Konzepts und des
A0-Mockup-Satzes am 31.08.2026; Erstfassung vom selben Tag).
**Grundlage:** Rahmenplan R45 und R46 mit den vorab gefallenen Beschlüssen
E-R45-1 bis E-R45-13; Befund am Code auf `main` = Web 9.15.0 / Uhr 2.0.0.
**Modell:** kein Fable-Schritt (E-R45-10); Umsetzung mit Opus nach K2.
**Einordnung:** Zwischenpaket **nach S2 und S3, vor P5**, unabhängig von P4
und parallel zu ihm führbar. Die Blöcke B und C (Handy- und Uhr-App, nur
`android/`) können bereits **während S3** laufen; Block A (Browser und
Server) erst danach — Begründung in Abschnitt 6.

Dieses Dokument ist die Übergabeeinheit an die umsetzende Instanz (K1). Es
wird während der Umsetzung fortgeschrieben; das Prüfdokument nach K9 entsteht
als eigene Datei daneben.

---

## 1. Ziel

NotärztInnen ohne Garmin-Uhr zeichnen ihre GPS-Spur mit dem **Handy** auf und
dokumentieren die Einsatzphasen an einer **Wear-OS-Uhr** oder am Handy selbst;
wer gar keine App hat, importiert eine **GPX-Datei** aus einer beliebigen
Tracker-App. In allen Fällen entstehen im Browser Einsätze mit echter Spur —
wo niemand einen Knopf gedrückt hat, **geschnitten** aus der
Dauer-Aufzeichnung, mit von Hand gesetzten Phasenzeiten. Die Verwaltung
bleibt im Browser; die Apps erfassen nur.

Die Rollenverteilung ist beschlossen (E-R45-1): **Das Handy zeichnet auf, die
Uhr ist Fernbedienung.** Der Vordergrunddienst des Handys führt das
Dauer-GPS über den ganzen Dienst; die Wear-OS-App trägt die Phasenknöpfe,
setzt die Zeitstempel auf der Uhr, puffert bei Funkabriss und meldet über den
Wear Data Layer ans Handy, das quittiert. Die Phasenknöpfe stehen zusätzlich
auf dem Handy, damit die App auch ohne Uhr vollständig ist.

Das Schneiden ist dabei nicht nur das Sicherheitsnetz für den vergessenen
Knopf, sondern ein **wählbarer Arbeitsmodus**: Wer im Einsatz keine Knöpfe
drücken kann oder will — oder keine Uhr hat —, wählt beim Dienstbeginn
„Nur aufzeichnen" und schneidet die Einsätze später im Browser heraus
(E-S4-20).

## 2. Befund (statische Analyse, Stand `main` = Web 9.15.0 / Uhr 2.0.0)

### 2.1 Der Vertrag trägt den zweiten Client heute schon

`docs/JSON-Vertrag.md` ist ausdrücklich clientneutral („Wer einen neuen
Client baut, implementiert gegen diesen Text"). Alles, was der Handy-Client
braucht, ist vorhanden und durchgesetzt:

- **Anmeldung je Gerät** über `X-Device-Id`/`X-Api-Key`; der Schlüssel
  entsteht bei der Code-Kopplung (`pair.php`: sechs Zeichen, zehn Minuten,
  einmal einlösbar, Ratenschutz, `MAX_GERAETE` = 5 je Konto, E-Mail an die
  KontoinhaberIn).
- **Trennen** als zweites Anliegen von `pair.php` (`{"aktion":"trennen"}`,
  seit Web 9.15.0) — der Server **löscht** das Gerät, hochgeladene Daten
  bleiben. Die Regeln der geteilten Uhr (Backlog Nr. 14) gelten wörtlich auch
  für ein geteiltes Handy: abfragen → trennen → neu koppeln, Rückstand
  sperrt das Trennen, lokal wird immer getrennt.
- **`rest_segment`** trägt eine Spur ohne Phasen mit fortlaufender `seq` —
  die Dauer-Aufzeichnung eines Dienstes ist damit heute schon sendbar, ohne
  dass am Server eine Zeile geändert wird.
- **`mission`** trägt Phasen 2–9, Teil-Uploads (`final: false`) und den
  Abschluss (`final: true` + `ended_at`). `lat`/`lon` einer Phase dürfen
  `null` sein (`ingest.php` prüft sie einzeln und nimmt `null` an).
- **Idempotenz** über Gerät + `client_ref`; Diensteinheit über `day_ref`
  (Präfix `d-`), Rückfallebene `(Konto, day)` dauerhaft.
- **Chunks:** Richtwert 500 Punkte je Anfrage, harte Grenze 512 KB Body
  (`413` → halbieren). Die Grenze ist bei der Garmin plattformbedingt
  begründet; für das Handy trägt dieselbe Zahl aus dem zweiten dort
  genannten Grund (Mobilfunk-Robustheit). Die plattformneutrale
  Neubegründung bleibt beim Vertragsreview in P6 (R12).

### 2.2 Der Garmin-Client als Verhaltensreferenz

Die Uhr-App (`watch/`) ist die Referenz dafür, wie sich ein Client dieses
Vertrags verhält. Der Handy-Client übernimmt dieses Verhalten bewusst —
nicht aus Bequemlichkeit, sondern weil Server-Mengenannahmen (R19-Messung:
Spitze 14 Anfragen an einem Auslöser, Median 1 020 s Abstand) und
Referenz-Payloads (526 Anfragen aus P1/R4) genau dieses Verhalten vermessen:

- **Lebenszyklus** (`Model.mc`): Eine gesetzte Phase 2–9 ohne laufenden
  Einsatz **startet** den Einsatz und schließt das laufende Ruhesegment;
  der Einsatzabschluss ist ein eigener Bedienschritt; danach beginnt das
  nächste Ruhesegment. Dienstende schließt beides (Sicherheitsnetz).
- **Ausdünnung** (`Track.mc`): 1-s-Abtastung, übernommen wird ein Punkt bei
  ≥ 15 m Bewegung oder ≥ 10 s Abstand.
- **Warteschlange** (`Uploader.mc`): fertige Einsätze → Segmente → aktives
  Paket; je Paket `next_seq`-Buchführung, Aufräumen erst nach `final` und
  vollständiger Bestätigung; Fehlerpfade: 400 = Paket als fehlerhaft
  markieren und nicht wiederholen, 401 = pausieren und anzeigen, 413 =
  Chunk halbieren, 5xx = später unverändert erneut.
- **Sync-Anzeige** (Backlog Nr. 11, Uhr 1.10.1): drei Zustände — „Nicht
  eingerichtet" (rot, mit nächstem Schritt), „Rückstand N", „vollständig"
  (nur wenn Server **und** Kopplung vorhanden).
- **Kennungen** (Vertrag 8, seit Uhr 1.7.0): Präfix + fortlaufender Zähler
  im Gerätespeicher + Zufallsanteil; kein Zeitstempel.

### 2.3 Was fehlt

Auf dem Server und im Browser fehlen vier Dinge, und sie sind der eine Teil,
an dem **alle** Client-Varianten hängen:

1. **Das Schneidewerkzeug**: aus einem Ruhesegment einen Einsatz mit von
   Hand gesetzten Phasenzeiten machen. Das braucht auch die Garmin-Nutzerin,
   die den Knopf vergessen hat.
2. **Der GPX-Import** als Gegenstück zum GPX-Abruf aus S2/AP4 — damit ist
   „ohne Uhr" schon ab Block A mit jeder vorhandenen Tracker-App möglich,
   bevor die eigene App fertig ist.
3. **Der QR-Code** auf der Geräteseite (Server-Adresse + Kopplungscode),
   damit die Kopplung am Handy ein Kameraschwenk ist statt Abtippen.
4. **Der APK-Download** aus der Web-App (E-R45-6: Verteilung ohne Store, für
   einen bekannten Kreis).

Dazu der beschreibende **Nachtrag im JSON-Vertrag** (Präfixe, Handy-Variante
des `geraet`-Blocks) — ohne Vertragsänderung (E-R45-2).

### 2.4 Abhängigkeiten von parallel laufender Arbeit

- **S2 (in Arbeit):** Das Schneidewerkzeug arbeitet auf der
  S2-Spurspeicherung (`spur_lib.php`); der GPX-Import ist das Gegenstück zum
  dortigen GPX-Abruf (AP4, Präfix `imp-` besteht); der Messstand (R35,
  5 000-Einsätze-Konto) ist das Prüfmittel für die Schneide-Antwortzeiten.
  Das S2-Konzept liegt auf dem S2-Zweig, nicht auf `main` — **Block A
  beginnt erst auf dem gemergten S2-Stand**, und die Einzelheiten der
  Schnittstelle (wie `spur_lib.php` Punktbereiche liest und schreibt) werden
  dort nachgeschlagen, nicht hier vermutet.
- **S3 (offen):** Der Browserteil folgt dem in S3 festgeschriebenen
  vertikalen Rhythmus — Block A deshalb nach S3.
- **R42-Kleinstpaket (in Arbeit):** legt die `devices`-Spalten Art/Modell an
  und nimmt den `geraet`-Block in `pair.php` entgegen. Auf `main` ist davon
  noch nichts; die Kopplung des Handys sendet die Felder in der Form, die
  das Kleinstpaket festlegt (F-S4-B).
- **P4 Nr. 11/14** sind mit R47 erledigt und ausgeliefert; ihre
  Entscheidungen werden sinngemäß **übernommen** (Abschnitt 2.2), nicht neu
  getroffen.

### 2.5 Prüfumgebung: was geht und was nicht

Der Android-Emulator braucht KVM und steht im Container **nicht** zur
Verfügung (E-R45-8). Geprüft wird deshalb:

- **Gradle-Build headless** im Container (Zuarbeit: Netzfreigaben für
  `dl.google.com`, `maven.google.com`, `repo1.maven.org`,
  `plugins.gradle.org`, `services.gradle.org`; dazu JDK und
  Android-Kommandozeilenwerkzeuge im Container).
- **Robolectric** gegen synthetische Positionsströme, rückgerechnet aus den
  Referenz-Payloads (P1/R4) — sie sind genau die Eingabe, die ein zweiter
  Client wiedererzeugen muss.
- **Server-Rundlauf** gegen `ingest.php` in der Container-Installation.
- Der Web-Teil mit den vorhandenen Prüfmitteln (Abschnitt 8).

**Nicht prüfbar aus dem Container** — das steht hier vorn, nicht in einer
Fußnote: echtes GPS, Akkuverhalten (namentlich Samsungs „Apps im
Tiefschlaf"), Mobilfunk-Upload, Bluetooth und der Data Layer auf echter
Hardware. Das Handy wird mit **einem Dienst auf dem S24** durch den
Auftraggeber geprüft (erfahrungsgemäß zwei bis drei Runden, E-R45-7); die
**Uhr-App wird blind gebaut** und am Gerät geprüft, sobald eine Wear-OS-Uhr
vorliegt (Galaxy Watch4 oder neuer; blockiert nichts).

### 2.6 Was die App nie sieht

Die Handy-App berührt die Ende-zu-Ende-Verschlüsselung nicht: Sie erfasst
Spur, Phasen und Dienstzeiten — dieselben Daten wie die Garmin-Uhr, und wie
dort liegen sie im Klartext (das benennt R41 als Eigenschaft des Dienstes;
der R17-Review in P6 prüft die Schlüsselablage auf dem Handy und den
QR-Kopplungsweg mit). Diagnose, Alter und Einsatzort entstehen ausschließlich
im Browser und erreichen die App nie. Das einzige Geheimnis auf dem Handy ist
der **Geräteschlüssel** (E-S4-13); die Uhr trägt gar keins (E-S4-11).

## 3. Entscheidungen

Die Programmentscheidungen E-R45-1 bis E-R45-13 sind gefallen und werden hier
nicht wiederholt. Die folgenden Entscheidungen füllen sie aus.

- **E-S4-01 — Name und Kennung: die App heißt NAdoku.** Wie die Garmin-Uhr
  seit 2.0.0 (R48); das Web zieht am P6-Schnitt nach. Anwendungs-ID
  `org.genem.nadoku` (der Bindestrich aus `gen-em.org` ist in Paketnamen
  nicht zulässig und entfällt). **Handy- und Uhr-Modul tragen dieselbe
  Anwendungs-ID und dieselbe Signatur** — der Wear Data Layer stellt
  Nachrichten nur zwischen Apps gleichen Pakets und gleicher Signatur zu;
  das ist eine Plattformbedingung, keine Wahl. Die ID wird wie bei der Uhr
  (Uhr 2.0.0) im Manifest als endgültig kommentiert.
- **E-S4-02 — Werkzeugstack:** Kotlin mit Jetpack Compose (Handy) und
  Compose for Wear OS (Uhr); **ein** Gradle-Projekt `android/` mit den
  Modulen `handy/` und `uhr/`; JDK 17. Kommentare und Doku deutsch,
  eigene Bezeichner deutsch, wo kein Framework-Zwang besteht (der Linie von
  `spur_lib.php` und `ist_admin()` folgend); Überschreibungen von
  Framework-Schnittstellen bleiben englisch. **Eine gemeinsame
  Versionszählung** für beide Module (sie entstehen aus einem Build und
  werden zusammen ausgeliefert), Changelog-Präfix **`Android`** neben `Web`
  und `Uhr`. Nummern legt die Umsetzung fest (K3).
- **E-S4-03 — Mindeststände:** Handy `minSdk 26` (Android 8.0):
  Vordergrunddienste mit Benachrichtigungskanal verhalten sich ab dort
  stabil, und ältere Geräte im Zielkreis sind nicht zu erwarten (F-S4-A
  bestätigt das); `targetSdk` aktuell — damit gelten die strengen Regeln für
  Vordergrunddienste vom Typ `location`, und die App deklariert
  `FOREGROUND_SERVICE_LOCATION`. Uhr `minSdk 30` (Wear OS 3, Galaxy Watch4
  aufwärts — ältere Tizen-Modelle laufen ohnehin nicht, E-R45-7).
- **E-S4-04 — So wenige Abhängigkeiten wie möglich.** HTTP über
  `HttpURLConnection`, JSON über `org.json` (beides Android-Bordmittel),
  Puffer über SQLite-Bordmittel (`SQLiteOpenHelper`, keine ORM). Dazu genau
  drei Fremdbestandteile, alle nur zur **Bauzeit** bezogen (der Grundsatz
  „keine fremde Quelle zur Laufzeit" gilt unverändert — die App lädt zur
  Laufzeit nichts nach): **AndroidX/Compose** (Apache-2.0), **ZXing**
  (Apache-2.0, QR-Erkennung aus dem Kamerabild) und
  **`play-services-wearable`** (proprietäre Google-Bibliothek) — Letztere
  ausschließlich für den Data Layer, den E-R45-1 festlegt und zu dem es auf
  Wear OS keinen Ersatz gibt. **Die Ortung läuft über den
  `LocationManager`, nicht über Play-Dienste:** Die Kernfunktion des Handys
  (aufzeichnen, senden) funktioniert damit auch auf Geräten ohne
  Google-Dienste; nur die Uhr-Anbindung braucht sie. Alle drei Bestandteile
  werden in `docs/Lizenzen.md` mit Herkunft und Lizenz nachgetragen; die
  Verträglichkeit der proprietären Wearable-Bibliothek mit der AGPL wird
  dort ausdrücklich begründet (Systembibliotheks-Charakter der
  Play-Dienste), nicht stillschweigend angenommen.
- **E-S4-05 — Aufzeichnung wie die Garmin:** Vordergrunddienst vom Typ
  `location` mit dauerhafter Benachrichtigung (Dienststand, laufende Phase);
  1-s-Abtastung, Ausdünnung 15 m / 10 s wie `Track.mc`. Damit bleiben die
  Datenmengen in dem Rahmen, den die R19-Messung und der S2-Messstand
  vermessen haben — ein Client mit eigener Abtastidee machte beide
  Messungen wertlos. `day` = lokales Datum des Dienstbeginns, Zeitstempel
  ISO 8601 UTC, Track-Punkte Unix-Epoche (Vertrag 2). Beim Erststart führt
  die App durch die **Freistellung von der Akkuoptimierung**; ob die
  Freistellung hält, zeigt nur das Gerät (E-R45-7).
- **E-S4-06 — Puffer und Warteschlange wie die Uhr:** SQLite-Puffer;
  Reihenfolge fertige Einsätze → Segmente → aktives Paket; Chunks ≤ 500
  Punkte; `next_seq`-Buchführung je Paket, gelöscht wird erst nach `final`
  und vollständiger Bestätigung. Fehlerpfade wörtlich nach Vertrag 5
  (400 markieren, 401 pausieren und anzeigen, 413 halbieren, 5xx Backoff);
  ein `ok: true` mit `rejected` oder `kept_*` wird angezeigt und nicht als
  reiner Erfolg behandelt.
- **E-S4-07 — Sendetakt ereignisgetrieben, nicht dauernd:** gesendet wird
  bei Phasenwechsel, Einsatzabschluss, Dienstende und Wiederverbindung,
  dazwischen alle 15 Minuten (Live-Upload über Mobilfunk, E-R45-2). Das
  entspricht dem gemessenen Garmin-Verhalten (Median 1 020 s) und bleibt
  damit verträglich mit der künftigen R19-Bremse und der Mengengrenze je
  Konto (R37.10), die beide Clients gleich behandeln sollen.
- **E-S4-08 — Lebenszyklus wie die Garmin** (Abschnitt 2.2): Phase 2–9 ohne
  laufenden Einsatz startet den Einsatz und schließt das Ruhesegment;
  Einsatzabschluss als eigener Schritt sendet `final: true` + `ended_at`;
  danach neues Ruhesegment; Dienstende schließt alles. Ein zweiter
  Dienststart bei laufendem Dienst ist kein neuer Dienst, sondern die
  Anzeige „läuft seit …" (E-R45-13).
- **E-S4-09 — Kennungs-Präfixe** (Nachtrag Vertrag 8, vom Server wie bisher
  nicht geprüft): Handy `am-` (Einsatz), `ar-` (Ruhesegment), `ad-`
  (Dienst); Uhr-Fernbedienung `wm-` (Einsatz, an der Uhr ausgelöst). Die
  Bauform ist die der Uhr seit 1.7.0: Präfix + fortlaufender Zähler im
  Gerätespeicher + Zufallsanteil, kein Zeitstempel. **`day_ref` erzeugt
  immer das Handy** (`ad-`, E-R45-13). Ein an der Uhr ausgelöster
  Einsatzbeginn trägt dagegen eine **auf der Uhr erzeugte** Kennung (`wm-`):
  Sie ist der Idempotenz-Anker über den Funkabriss — meldet die Uhr das
  gepufferte Ereignis nach verlorener Quittung erneut, erkennt das Handy
  den Einsatz an der Kennung wieder, statt einen zweiten anzulegen. Als
  Nebeneffekt bleibt am Datensatz ablesbar, auf welchem Weg er entstand.
- **E-S4-10 — Uhr↔Handy-Protokoll:** `MessageClient` des Data Layer.
  Jedes Uhr-Ereignis (Dienst starten/beenden, Phase 2–9, Einsatzbeginn,
  Einsatzabschluss) trägt den **Zeitstempel der Uhr** (E-R45-1) und eine
  fortlaufende Ereignisnummer; das Handy quittiert mit der höchsten
  übernommenen Nummer, die Uhr puffert bis zur Quittung und liefert
  identisch nach. Phasen-Koordinaten ergänzt das **Handy** aus der eigenen
  Spur (zeitlich nächster Punkt, Toleranz ± 30 s, sonst `null` — der
  Vertrag erlaubt das). Liefern Uhr und Handy für dieselbe Phase je einen
  Zeitstempel, werden **beide** gesendet und gespeichert (E-R45-12).
  Ein an der Uhr ausgelöster Dienststart wirkt erst mit Zustellung ans
  Handy — vorher läuft dort kein GPS; die Uhr zeigt den Unterschied
  (bestätigt / „Handy nicht erreichbar") an, `started_at` ist die
  Auslösezeit der Uhr.
- **E-S4-11 — Bedienumfang der Uhr:** Dienst starten/beenden, Phasen 2–9,
  Einsatzabschluss, Anzeige von Verbindungszustand, laufender Phase und
  „läuft seit". **Keine Reanimation** (bleibt Garmin, E-R45-1), **keine
  Kopplung und keine Zugangsdaten auf der Uhr** — sie spricht nur mit dem
  Handy, nie mit dem Server. Ein gestohlener Uhr-Speicher gibt damit
  nichts preis.
- **E-S4-12 — Kopplung und Trennen am Handy:** per QR (E-S4-15) oder von
  Hand (Server-Adresse + Code); die Adresse wird tolerant ergänzt wie bei
  der Uhr (`Uploader._serverUrl()`: Schema und Pfad). Beim Koppeln meldet
  die App `art: "handy"` und die Gerätebezeichnung (Hersteller + Modell aus
  `Build.*`) in der Feldform des R42-Kleinstpakets (F-S4-B); eine Kopplung
  darf an einer Statistikangabe nie scheitern (Vertrag 1a). Trennen nach
  den Nr.-14-Regeln: abfragen → trennen → neu koppeln, Rückstand sperrt,
  lokal wird immer getrennt und gesagt. Die Sync-Anzeige übernimmt die drei
  Zustände aus Nr. 11.
- **E-S4-13 — Schlüsselablage:** `device_id` und `api_key` liegen im
  App-privaten Speicher, verschlüsselt mit einem Schlüssel aus dem
  **Android Keystore** (nicht exportierbar, kein Backup-Transport:
  `allowBackup` bleibt aus, damit der Geräteschlüssel nicht in
  Gerätesicherungen wandert). Kein Klartext in Logs, keine Anzeige des
  Schlüssels in der Oberfläche. Der R17-Review prüft die Konstruktion in
  P6 mit (E-R45-10).
- **E-S4-14 — Nur HTTPS.** Androids Standard (kein Klartextverkehr) bleibt
  unangetastet; die App bietet keinen HTTP-Ausnahmeschalter. Die
  Garmin-Uhr ergänzt heute schon `https://`, und der Geräteschlüssel im
  Klartext über HTTP wäre die Kopplung nicht wert.
- **E-S4-15 — QR-Code:** Inhalt ist kompaktes JSON
  `{"server":"https://…","code":"AB3K7Q"}` — kein eigenes URL-Schema, denn
  gescannt wird **in** der App, nicht mit der Systemkamera. Erzeugt wird er
  im Browser auf dem Geräte-Reiter, beim bestehenden Kopplungscode, mit
  einer **vendorierten** JavaScript-Bibliothek unter
  `server/assets/vendor/` (Herkunft, Version und SHA-256 im Dateikopf, wie
  Leaflet; Eintrag in `docs/Lizenzen.md`). Kein Dienst, kein CDN — der
  Code-Wert verlässt den Browser nicht.
- **E-S4-16 — APK-Weg:** Das APK wird mit einem eigenen Schlüssel signiert;
  den Schlüssel erzeugt die Umsetzung beim ersten Build und übergibt ihn
  dem Auftraggeber zur Verwahrung **außerhalb** des Repositoriums
  (E-R45-6) — jede spätere Fassung muss mit demselben Schlüssel signiert
  sein, sonst verlangt Android eine Neuinstallation. Die Datei selbst wird
  **nicht** eingecheckt und **nicht** deployt: Sie liegt in
  `server/apk/` nur auf dem Server, das Verzeichnis kommt in `.gitignore`
  **und** in die Ausnahmeliste des Deploys (dasselbe Muster wie
  `config.php`); hochgeladen wird per FTPS durch den Betreiber. Die
  Download-Seite (Seitenladung, nur angemeldet) zeigt, was liegt: Name,
  Version, Größe, Datum und den vom Server gerechneten SHA-256 — wer der
  Seite nicht traut, kann die Prüfsumme nachrechnen. In das CI-Prüftor nach
  R40.4 kommt ab P5 der **unsignierte Baulauf** (`gradlew build` muss
  grün sein); signiert wird außerhalb der CI, weil der Schlüssel dort
  nichts verloren hat (Entscheidung nach E-R45-9).
- **E-S4-17 — Schneidewerkzeug in der Tagesansicht am Ruhesegment**
  (E-R45-11, `index.php` mit `api/day.php`): Ruhesegment öffnen,
  Einsatzbeginn und -ende auf der Spur setzen, Phasenzeiten von Hand
  eintragen → ein Einsatz mit echter Spur (`client_ref`-Präfix `man-` —
  im Web von Hand angelegt; kein Gerät liefert für ihn nach), der Rest
  bleibt Ruhesegment. **Rückgängig**, solange am Einsatz nichts Weiteres
  hängt (keine Nachbearbeitung, keine Patientenfelder, keine Reanimation);
  Rückgängig stellt den Segmentzustand wieder her. Der Schreibweg läuft
  über `api/` mit Sitzung und **`validate_lib.php`** — die gemeinsame
  Prüfschicht gilt ohne Ausnahme. Gearbeitet wird auf der
  S2-Spurspeicherung; die Idempotenz-Frage der Nachlieferung (F-S4-C) ist
  vor Umsetzungsbeginn von A2 zu entscheiden.
- **E-S4-18 — GPX-Import** (Gegenstück zum GPX-Abruf S2/AP4): angenommen
  wird GPX 1.1 mit `trk`/`trkseg`/`trkpt`; `ele` darf fehlen, **`time` ist
  Pflicht** — ohne Zeitstempel gibt es keine Punktreihenfolge, kein
  Schneiden und keine Phasenzeiten; eine Datei ohne Zeiten wird mit dieser
  Begründung abgelehnt, nicht still angenommen. Einstieg in der
  Tagesansicht: Die Datei wird einem Diensttag zugeordnet (vorhandener
  oder neuer) und wird wahlweise **Ruhesegment zum Schneiden** oder
  **unmittelbar Einsatz** (E-R45-4). Präfix `imp-` (besteht; Sperrliste
  greift damit wie beim übrigen Import). Mengengrenze je Datei legt die
  Umsetzung am S2-Messstand fest und schreibt sie in die Fehlermeldung —
  ein 24-h-Dienst in 10-s-Auflösung (~9 000 Punkte) muss sicher
  hineinpassen.
- **E-S4-19 — Texte und Doku gerätefrei** nach dem P2-Muster (E-P2-02): Web
  und Handbuch beschreiben Kopplung, Aufzeichnung und Schneiden gerätefrei;
  Garmin, Android und Wear OS stehen je als Zusatz. `docs/Geraete-Eingabe.md`
  bekommt einen Wear-Teil nach dem Muster der Garmin-Abschnitte — als
  „blind gebaut, am Gerät nachzumessen" gekennzeichnet, bis eine Uhr
  vorliegt. Das Launcher-Symbol beider Module entsteht aus der vorhandenen
  NAdoku-Bildmarke (Rezept `tools/uhr-bilder/`); eine neue Bildsprache
  entsteht nicht.
- **E-S4-20 — „Nur aufzeichnen" als wählbarer Modus** (Anweisung vom
  31.08.2026). Beim Dienstbeginn wählt die App zwischen **„Mit
  Phasenknöpfen"** und **„Nur aufzeichnen (später schneiden)"**; Vorgabe
  ist die zuletzt getroffene Wahl. Im Nur-Aufzeichnen-Modus zeigen Handy
  **und** Uhr keine Phasenknöpfe — kein versehentlicher Druck mit
  Handschuhen, ein Bildschirm ohne Frage; der ganze Dienst entsteht als
  eine `rest_segment`-Kette und die Einsätze werden im Browser
  herausgeschnitten (E-R45-3). Technisch ist der Modus kein Sonderweg,
  sondern der benannte Grundzustand: exakt das Verhalten eines Dienstes,
  in dem nie eine Phase gesetzt wird — am Vertrag, am Server und an der
  Sendelogik ändert er **nichts**, nur an dem, was der Bildschirm
  anbietet. Ein **Wechsel während des Dienstes** ist jederzeit möglich und
  verlustfrei: Er blendet die Knöpfe ein oder aus, bereits Gesendetes
  bleibt unberührt (ein Umstieg auf „mit Knöpfen" schließt das laufende
  Segment erst, wenn tatsächlich eine Phase gesetzt wird — wie bisher).
  Der Modus wandert über den Nachrichtenweg zur Uhr; sie zeigt dann nur
  Dienst beginnen/beenden.
- **E-S4-21 — Bedienmodell der Uhr: ein Durchlauf, eine Liste, eine
  Sperre** (Anweisung vom 31.08.2026). Vier Teile:
  **(a) Keine verlässliche Hardwaretaste.** Wear-OS-Uhren haben mindestens
  die Ein/Aus-Taste, und die ist für Apps grundsätzlich gesperrt. Freie
  Zusatztasten meldet die Plattform über die `WearableButtons`-Schnittstelle
  (androidx.wear.input) — manche Sportmodelle haben welche, auf der
  Galaxy-Watch-Linie ist **keine** zu erwarten (Home und Zurück sind
  systemgebunden); verlässlich weiß es erst die Abfrage am Gerät. Die App
  fragt ab und legt, wo eine freie Taste gemeldet wird, „nächste Phase"
  darauf — das Bedienbild hängt aber **nicht** daran: Es muss mit Touch
  allein vollständig sein. Lünette und Krone scrollen nur und lösen nie
  eine Handlung aus.
  **(b) Der Durchlauf** — dasselbe Modell wie START/Action an der Garmin:
  Im Einsatz trägt die Hauptseite **einen** großen Knopf „nächste Phase"
  (2 → … → 9), klein darunter die aktuelle Phase mit Zeit. Nach der
  letzten Phase wird derselbe Knopf zu **„Einsatz abschließen" mit
  Rückfrage** — erst die Bestätigung sendet `final` + `ended_at`; ein
  versehentlicher letzter Tipp beendet nichts.
  **(c) Die Phasenliste — Übersicht und Direktwahl in einem.** Halten des
  großen Knopfs öffnet die Liste: jede Zeile eine Phase mit ihren
  gesetzten Zeiten (**ansehen**), Tippen setzt die gewählte Phase jetzt
  (**direkt wählen**; eine erneut gesetzte Phase ist ein zweiter Eintrag,
  E-R45-12). Dort steht auch „Einsatz abschließen" für den vorzeitigen
  Abschluss, mit derselben Rückfrage.
  **(d) Die Sperre.** Nach kurzer Zeit ohne Bedienung (Richtwert 10 s;
  in den App-Einstellungen abschaltbar) sperrt die Anzeige: Phase und
  Zeit bleiben lesbar, unten steht ein Schloss, entsperrt wird durch
  **Halten** (~1 s); ein Tippen tut gesperrt nichts. Die Sperre gilt für
  Touch und eine etwaige freie Taste gleichermaßen. Im
  Always-on-Ruhezustand weckt die erste Berührung ohnehin nur das
  Display — die Sperre schließt die Lücke im aktiven Zustand.
  Haltedauer, Sperrfrist und Berührziele sind blind gewählt und am Gerät
  nachzumessen (E-R45-7); sie gehören in den Wear-Teil von
  `docs/Geraete-Eingabe.md`.
- **E-S4-22 — Marke in der App: alle drei Kernfarben und die Logo-Wahl**
  (Anweisung vom 31.08.2026). Zwei Teile:
  **(a) Farbrollen wie im Web** (Design.md 3.1; R8 sinngemäß — in Summe
  ausgewogen, nicht nur Dunkelblau und Orange): **Orange handelt**
  (Primärknöpfe, laufender Dienst), **Blau erklärt und bestätigt**
  (Verbindungs- und Sync-Zustand in Ordnung, gesetzte Phasenzeiten,
  erklärende Hinweise), **Rot warnt** („Nicht eingerichtet", abgewiesener
  Schlüssel, „Handy nicht erreichbar"). Die App führt die Werte einmal als
  Token, mit denselben HEX-Werten wie `:root` im Web; ein eigener Farbwert
  entsteht nicht. Drei feste Auftritte, damit die Rollen sichtbar werden
  (Anweisung vom 31.08.2026): Die **laufende Aufzeichnung** trägt auf
  jedem laufenden Bildschirm — Handy wie Uhr — einen **roten Punkt**, und
  **beendende Handlungen sind vollflächig rot** (weiße Schrift auf
  `--rot`): Einsatz abschließen, Dienst beenden, Gerät trennen und die
  Abschluss-Bestätigung der Uhr. Das ist bewusst großflächiger als der
  rote Rahmen des Web-Gefahrknopfs — auf einem Gerät im Einsatz muss die
  beendende Handlung ohne Lesen erkennbar sein; die Rückfrage (E-S4-21b)
  fängt den Fehltipp ab. **Auswahl- und Erklärflächen** (Moduswahl, Logo-Wahl,
  Hinweiskästen) stehen auf **Hellblau** (`--blau-hell`) — bewusst anders
  als die Wahlliste im Web, die hell-orange wählt (E-P3-20): In der App
  wählt Blau, damit die Rolle aus 3.1 sichtbar wird; der Unterschied
  steht mit der Freigabe zur Bestätigung. Und die **Bildmarke bleibt auch
  im laufenden Dienst sichtbar**: am Handy im Kopf jeder Ansicht (weiße
  Fassung), an der Uhr **gut sichtbar am oberen Rand** der laufenden
  Ansichten (Dunkelgrund-Fassung; Richtwert **ein Sechstel** der
  Displayhöhe — die Startseite trägt sie größer, nach der
  27-%-Stufung der Garmin).
  **(b) Beide Bildmarken in beiden Modulen, Wahl wie gehabt:** dieselbe
  Dreier-Wahl wie im Web-Konto (Design.md 2.3) und an der Garmin-Uhr
  (`logoWahl`) — in der App mit **Vorgabe „wechselnd"**, in den
  App-Einstellungen festlegbar auf RTH (Hubschrauber) oder NEF (Fahrzeug).
  „Wechselnd" wird **einmal je App-Start** gewürfelt und bleibt stehen
  (Design.md 2.3: „Ein Logo, das bei jedem Seitenaufruf wechselt, ist kein
  Logo, sondern ein Flackern"). Die Uhr hat keine eigenen Einstellungen:
  Sie übernimmt die **Einstellung** vom Handy über den Nachrichtenweg
  (E-S4-10) und würfelt „wechselnd" je Start selbst — wie die Garmin, ohne
  Abstimmungsbedarf zwischen den Geräten. Die Einsatzregeln aus Design.md
  2.3 gelten: im dunkelblauen App-Kopf die weiße Fassung, auf hellem Grund
  die farbige; die Uhr zeigt auf Schwarz die **Dunkelgrund-Fassung** — nur
  die farbtragenden Elemente, der dunkle Korpus entfällt bzw. wird weiß.
  Das sind genau die Fassungen der Garmin seit 1.10.3
  (`watch/resources/drawables/logo_luft.png` / `logo_boden.png`); die
  Wear-App nutzt dieselben Vorlagen. Die S3-Erkenntnis zur Skalierung
  (Punkt K: gleiche Höhe lässt die quadratische NEF-Marke kleiner wirken
  als die 1,60 : 1 breite Luftmarke) gilt in der App von Anfang an —
  gestuft wird über die Fläche, nicht die Höhe. Ressourcen sind die
  vorhandenen Dateien (`server/assets/images/gen-em_logo_*.svg`, Rezept
  `tools/uhr-bilder/`); eine neue Bildsprache entsteht nicht (E-S4-19).

## 4. Offene Fragen

- **F-S4-A — Mindest-Android-Stand.** Vorschlag: Android 8.0 (`minSdk 26`,
  E-S4-03). Zu bestätigen vom Auftraggeber mit Blick auf die tatsächlichen
  Geräte des Nutzerkreises — ein älteres Gerät im Kreis hieße: Stand senken
  und die Vordergrunddienst-Pfade dafür gesondert prüfen.
  **Entschieden am 31.08.2026: Android 8.0.** E-S4-03 gilt damit bestätigt
  und unverändert; die Frage ist geschlossen.
- **F-S4-B — Feldform des `geraet`-Blocks für Handys.** Das
  R42-Kleinstpaket legt fest, welche Felder `pair.php` speichert (Art,
  Bezeichnung). Die Handy-Kopplung übernimmt diese Form; sollte das
  Kleinstpaket nur die Uhr-Felder (`teil` usw.) annehmen, wird der
  Handy-Zuschnitt (Hersteller + Modell statt Teilenummer, `teil: null`) im
  Vertrag als Nachtrag beschrieben und die Annahme in `pair.php` in A1
  ergänzt. **Klärt sich mit dem Merge des Kleinstpakets; zu entscheiden
  vor B2** (die App sendet die Felder ab der ersten Kopplung — jede
  Kopplung davor ginge der Statistik verloren, R42).
  **Vorgelegt und entschieden am 31.08.2026: der Rückfall.** Befund vor B2:
  Das Kleinstpaket liegt **nicht** auf `main` — `pair.php` liest den Block an
  keiner Stelle aus, und `devices` hat keine Spalte für Art oder Modell
  (beides in der Container-Installation nachgesehen, nicht vermutet). Die
  Sorge aus R42 greift dabei **nicht**: Weil der Server den Block heute
  verwirft, ginge die Statistik für jede Kopplung vor dem Merge ohnehin
  verloren — unabhängig davon, was die App sendet. Zu entscheiden war damit
  allein, welche Feldnamen die App einbackt. Die Form steht als **E-S4-28**
  in Abschnitt 12; die Frage ist geschlossen.
- **F-S4-C — Schnitt gegen Nachlieferung: die Idempotenz darf nicht
  brechen.** `ingest.php` hängt Spurpunkte über `seq` an ein Segment an und
  ignoriert bekannte Sequenzen. Wandern beim Schneiden Punktbereiche vom
  Segment zum Einsatz, und liefert ein Gerät dieselben Sequenzen später
  identisch nach (der vorgesehene Nachzügler-Fall), dürfen sie weder
  doppelt entstehen noch den Schnitt überschreiben. Die Antwort hängt an
  der S2-Bauform der Spurspeicherung (Blob-Zeilen statt Punktzeilen):
  Kandidaten sind Kopieren mit Herkunftsvermerk statt Verschieben, oder
  eine Nachliefer-Sperre je geschnittenem Bereich. **Zu entscheiden vor
  A2, am gemergten S2-Stand; die Entscheidung wird als E-Eintrag
  nachgetragen** (K6) und mit einem eigenen Prüffall belegt.

- **F-S4-D — Garmin und Handy gleichzeitig im Dienst: es entstehen zwei
  Diensttage.** Aufgekommen als Rückfrage während C2, **gemessen** statt
  hergeleitet (lokale Container-Installation, zwei Geräte gekoppelt, beide
  senden denselben Einsatz für den 01.09.2026):

  | | Diensttag | Einsatz |
  |---|---|---|
  | Garmin (`d-41-…`, `m-41-…`) | **53** | 1 |
  | Handy (`ad-7-…`, `am-7-…`) | **54** | 1 |

  Die Zuordnung hängt an `day_refs`, und die Tabelle ist **je Gerät**
  geschlüsselt (`WHERE r.device_id = ? AND r.day_ref = ?`). Das Handy findet
  die Kennung der Garmin nicht und legt über `dt_anlegen()` einen zweiten
  Diensttag an; dasselbe gilt für die Einsätze, deren Idempotenz über
  `(device_id, client_ref)` läuft. **Es geht nichts verloren und nichts wird
  überschrieben — es ist alles doppelt:** zwei Diensttage, zwei
  Ruhesegment-Ketten, derselbe reale Einsatz zweimal, zwei Spuren desselben
  Wegs. In der Jahresstatistik zählt er doppelt.

  `dt_zusammenfuehren()` (Nachbarschaft 3 Tage) hängt die Datensätze um —
  danach steht die Doppelung in **einem** Tag statt in zweien. Zusammengeführt
  werden Tage, nicht Einsätze; das Nachräumen bleibt von Hand.

  **Der wahrscheinlichste Fall ist nicht der bewusste Parallelbetrieb, sondern
  der vergessene:** Die Garmin läuft im Spind weiter, das Handy zeichnet auf.
  Der zweite Diensttag trägt dann eine Spur, die im Schrank liegt.

  *Zu entscheiden vom Auftraggeber*, weil es den Betrieb betrifft und nicht
  den Code. Kandidaten, keiner davon vorweggenommen:
  (a) nichts tun und den Fall im Handbuch benennen — die Doppelung ist
      sichtbar und über das Zusammenführen auflösbar;
  (b) **Warnung beim Koppeln**: `pair.php` kennt seit R42 die Geräteart und
      könnte melden „an diesem Konto hängt bereits ein aufzeichnendes Gerät";
  (c) **Hinweis im Browser**, wenn zwei aktive Diensttage desselben Kontos
      sich zeitlich überlappen — er träfe auch den vergessenen Fall, den (b)
      nicht sieht.
  Der Weg über (c) ist der einzige, der den *vergessenen* Fall erwischt;
  (b) verhindert nur die absichtliche Doppelkopplung. Eine Umsetzung fiele
  in Block A oder später — **in B/C ist nichts davon enthalten.**


## 5. Arbeitspakete

Je Paket ein Commit (K7). Die Blöcke folgen dem Rahmenplan-Schnitt; B und C
fassen ausschließlich `android/` an, A ausschließlich `server/`, `docs/` und
den Vertrag, D die übergreifende Doku.

### Block B — Android-Handy-App (`android/handy/`)

**B1 — Gerüst und Probebau.**
Gradle-Projekt `android/` mit den Modulen `handy/` und `uhr/`, Anwendungs-ID
und Signaturkonzept (E-S4-01), Versionszählung, Lint; Farb-Token und beide
Bildmarken als Ressourcen (E-S4-22); `LIESMICH.md` in
`android/` mit Bauanleitung (Container und Arbeitsplatz). Erste Handlung ist
der **Probebau im Container** — er ist der Nachweis, dass die Netzfreigaben
tragen; scheitert er, stoppt Block B und die Freigabenliste wird
nachgezogen, bevor irgendetwas anderes entsteht.
*Abnahme:* `./gradlew build` läuft headless im Container durch (Fehler 0;
Zahl der Warnungen wird genannt, nicht versteckt); beide Module bauen ein
installierbares, unsigniertes APK; der Signaturschlüssel ist erzeugt und die
Übergabe an den Auftraggeber vermerkt.

**B2 — Kopplung, Trennen, Schlüsselablage.**
Code-Eingabe von Hand und QR-Scan (ZXing; das QR-Format aus E-S4-15 ist
damit vor Block A festgelegt und beidseitig baubar), `pair.php`-Kopplung mit
`geraet`-Block (F-S4-B), Ablage nach E-S4-13, Trennen nach E-S4-12,
Sync-Anzeige mit den drei Zuständen.
*Abnahme:* Robolectric-Fälle für Kopplung (Erfolg, 400, 404, 409
`device_limit`, 429), Trennen (Erfolg, 401, Rückstandssperre, lokales
Trennen ohne Antwort) und Schlüsselablage (kein Klartext im
App-Speicherabbild) — jede Zahl im Prüfprotokoll; Server-Rundlauf der
Kopplung gegen die Container-Installation.

**B3 — Aufzeichnung und Dienstklammer.**
Vordergrunddienst (E-S4-05), Ausdünnung, SQLite-Puffer, Dienst
starten/beenden mit der Moduswahl „Mit Phasenknöpfen / Nur aufzeichnen"
(E-S4-20), Neustart-Wiederaufnahme (App-Absturz und Handy-Neustart
während des Dienstes), Erststart-Führung zur Akku-Freistellung; die
App-Einstellungen (Logo-Wahl E-S4-22, Sperre der Uhr E-S4-21d).
*Abnahme:* synthetischer Positionsstrom → Punktfolge nach der
15 m/10 s-Regel (Soll-Zahlen je Strom im Prüfprotokoll);
Wiederaufnahme-Fälle; ein 12-h-Strom erzeugt eine Punktmenge in der
Größenordnung der Referenz-Diensttage.

**B4 — Senden.**
Warteschlange, Chunking, `next_seq`, Fehlerpfade und Anzeige von
`rejected`/`kept_*` (E-S4-06/07).
*Abnahme:* **Wiedergabe der Referenz-Payloads** — aus den Strömen von B3
erzeugt die Sendelogik Anfrageketten, die gegen `ingest.php` im Container
vollständig durchlaufen: 0 × `rejected`, 0 × `kept_*`, 0 × 400, `seq`
lückenlos, Chunkgrenze eingehalten; dazu die Funkabriss-Matrix (verlorene
Antwort, 401, 413-Halbierung, 5xx-Backoff, App-Neustart mitten in der
Kette) mit je einem Fall.

**B5 — Phasen und Einsätze am Handy.**
Phasenknöpfe 2–9, Einsatzbeginn/-abschluss, Lebenszyklus (E-S4-08),
Phasen-Koordinaten aus der eigenen Spur, `mission`-Uploads mit
Teil-Uploads; der Einsatzabschluss fragt nach, wie an der Uhr
(E-S4-21b); im Nur-Aufzeichnen-Modus sind die Knöpfe ausgeblendet und
der Moduswechsel während des Dienstes verlustfrei (E-S4-20).
*Abnahme:* Lebenszyklus-Matrix (Phase ohne Einsatz startet Einsatz;
Abschluss sendet `final` + `ended_at`; Segment davor/danach nahtlos);
`mission`-Ketten laufen gegen `ingest.php` wie in B4 (0/0/0); doppelte
Phaseneinträge bleiben erhalten (E-R45-12 nachgestellt); ein
Nur-Aufzeichnen-Dienst erzeugt genau eine Segmentkette und keine
`mission`, und der Moduswechsel mitten im Dienst ändert nichts an bereits
Gesendetem (je ein Fall).

### Block C — Wear-OS-App (`android/uhr/`)

**C1 — Gerüst und Bedienbild.**
Modul `uhr/`, Oberflächen nach E-S4-11 und dem Bedienmodell E-S4-21:
Durchlauf-Knopf mit Abschluss-Rückfrage, Phasenliste als Übersicht und
Direktwahl, Sperre, `WearableButtons`-Abfrage; Bildmarke auf der
Startseite nach der vom Handy übernommenen Logo-Wahl und die Farbrollen
nach E-S4-22; im Nur-Aufzeichnen-Modus
nur Dienst beginnen/beenden (E-S4-20). Blind gebaut.
*Abnahme:* Modul baut im selben Gradle-Lauf; die Bedienzustände sind als
Robolectric-Fälle belegt (Zahl im Prüfprotokoll), darunter je ein Fall:
Durchlauf 2 → 9 und Abschluss nur nach Rückfrage; Direktwahl setzt die
gewählte Phase, eine Korrektur wird zweiter Eintrag; die Sperre greift
nach Frist, gesperrtes Tippen tut nichts, Entsperren nur durch Halten;
mit gemeldeter Taste löst sie „nächste Phase" aus, ohne bleibt alles per
Touch bedienbar. Bildschirmfotos gibt es nicht (kein Emulator) — das
steht so im Prüfdokument, nicht verschwiegen.

**C2 — Nachrichtenweg mit Quittung und Puffer.**
Protokoll nach E-S4-10 auf beiden Seiten (Uhr sendet/puffert, Handy
übernimmt/quittiert/ergänzt Koordinaten); die Data-Layer-Schicht selbst
bleibt eine dünne Hülle, geprüft wird die Logik dahinter gegen eine
Transport-Attrappe.
*Abnahme:* Ereignis-Matrix mit je einem Fall — Funkabriss mit Nachlieferung,
Doppelzustellung nach verlorener Quittung (kein zweiter Einsatz, `wm-`-Anker
greift), Uhr-Neustart mit gefülltem Puffer, Phasenkonflikt Uhr/Handy (beide
Einträge gesendet), Dienststart an der Uhr ohne erreichbares Handy
(schwebend angezeigt, keine Aufzeichnungslücke verschwiegen). Ausdrücklich
**nicht** belegt: der echte Data Layer — Gerätetest, sobald eine Uhr
vorliegt.

### Block A — Browser und Server (nach S3, auf gemergtem S2-Stand)

**A0 — Mockup-Freigabe.** Schneidewerkzeug und GPX-Import-Einstieg sind
neue Darstellungen in der Tagesansicht; QR-Feld und Download-Seite bauen
auf vorhandenen Bausteinen. Vor der Umsetzung entsteht **ein** Mockup-Satz
(Tagesansicht mit Schneide-Bedienung, Import-Einstieg, Geräte-Reiter mit
QR, Download-Seite) und wird ausdrücklich freigegeben (`docs/Design.md` 1;
CLAUDE.md 5). Ohne Freigabe beginnt A2/A3 nicht.
**Stand 31.08.2026: Der Satz liegt vor** — `docs/mockups/S4-schneiden.html`
(Ruhesegment-Karte und Schneide-Bereich), `S4-gpx-import.html` (Dialog),
`S4-geraete-qr.html` (QR am Codeblock, APK-Karte), je mit Bildern in 900
und 390 px (Überlauf 0, Konsole 0); dazu `S4-app.html` als Vorschlag für
Handy- und Uhr-Bedienbild (nicht Design.md-gebunden, keine Freigabepflicht,
aber dieselbe Gelegenheit für Einwände). Drei Vorschläge darin gehen über
den Konzepttext hinaus und stehen mit der Freigabe zur Entscheidung:
Beginn/Ende Pflicht und Phasenzeiten optional im Schneide-Bereich (die
vollständige Phasenliste wohnt im Einsatzformular); Rückgängig als
Zeilenaktion am geschnittenen Einsatz, nicht im Schneide-Bereich; der
Download als neutrale Handlung, nicht als Primärknopf.
**Freigegeben am 31.08.2026** — ausdrücklich einschließlich der drei
Vorschläge und der hellblauen Auswahl in der App (E-S4-22a). A0 ist damit
erledigt; A2/A3 warten nur noch auf den gemergten S2/S3-Stand und die
F-S4-C-Entscheidung.

**A1 — QR, APK-Download, Vertrag-Nachtrag.**
QR auf dem Geräte-Reiter (E-S4-15), Download-Seite und `server/apk/`-Weg
samt `.gitignore`- und Deploy-Ausnahme (E-S4-16), Nachtrag im JSON-Vertrag:
Abschnitt 8 (Präfixe `am-`/`ar-`/`ad-`/`wm-`), Abschnitt 1a
(Handy-`geraet`-Block nach F-S4-B), Titel und Einleitung clientneutral
formuliert (der Vertrag heißt heute „Uhr → Server" — nach S4 sendet auch
ein Handy; redaktionell, keine Vertragsänderung, E-R45-2); ggf.
`pair.php`-Annahme des Handy-Blocks (F-S4-B).
*Abnahme:* Kopplung per QR gegen die Container-Installation im Browser
durchgespielt; Deploy-Ausnahme nachgewiesen (Trockenlauf der Action oder
Ableitung am Workflow-Text — was davon möglich war, steht im
Prüfprotokoll); Wortliste auf den geänderten Texten 0/0/0.

**A2 — Schneidewerkzeug** (nach Entscheidung F-S4-C).
Bedienung in der Tagesansicht, `api/`-Endpunkt über `validate_lib.php`,
Arbeit auf `spur_lib.php`, Rückgängig (E-S4-17).
*Abnahme:* Schneiden und Rückgängig im Browser durchgespielt (Chromium,
Konsole 0); der F-S4-C-Prüffall (Nachlieferung nach Schnitt) besteht; beide
Kreisläufe R24 auf 0 unerklärt — geschnittene Einsätze müssen durch
Sicherung und CSV kommen; `tools/wiederherstellungs-probe/` und
`papierkorb_misch.mjs` bestehen (das Schneiden fasst die
Diensttag-Zuordnung an, R27); Messstand R35: Schneide-Antwortzeit auf dem
5 000-Einsätze-Konto als Zahl.

**A3 — GPX-Import** (E-S4-18).
*Abnahme:* Import als Ruhesegment und als Einsatz im Browser durchgespielt;
Ablehnungsfälle (ohne `time`, über der Mengengrenze, kaputtes XML) mit
verständlicher Meldung; Kreisläufe R24 auf 0 unerklärt (importierte
Einsätze durch Sicherung und CSV); ein GPX-Abruf (S2) einer importierten
Spur ergibt die Punktfolge zurück (Rundlauf-Probe mit Zahl).

### Block D — Abschluss

**D1 — Doku, Lizenzen, Prüfdokument.**
Handbuch (gerätefreie Kapitel + Zusätze Android/Wear), `docs/Technik.md`
(neuer Client, Verzeichnisstruktur `android/`, Runbook APK-Weg),
`docs/Geraete-Eingabe.md` (Wear-Teil), `docs/Lizenzen.md` (E-S4-04,
E-S4-15), `CLAUDE.md` (Bauhinweise `android/`), `docs/CHANGELOG.md`,
Backlog-Pflege, Prüfdokument nach K9. Die Prüfmittel laufen **zuletzt**
(CLAUDE.md 6): Wortliste, Vollständigkeit, Screenshots der berührten
Seiten, Kontraste, Kreisläufe — erst nach der letzten Textänderung.
*Abnahme:* Prüfdokument liegt vor, mit Kurzfassung, Zahlen je Prüfmittel,
dem Nicht-Prüfbaren an erster Stelle (2.5) und der abhakbaren Prüfliste
für den Gerätetest (S24-Dienst, später Uhr) — je Punkt Bedienweg,
erwartetes Ergebnis und woran ein Scheitern zu erkennen ist.

## 6. Reihenfolge und Parallelität

**B1 → B2 → B3 → B4 → B5**, dann **C1 → C2** (C braucht das
Handy-Gegenstück aus B für den Nachrichtenweg; C1 kann nach B1 beginnen).
**A0 → A1 → A2 → A3** erst nach S3 und auf gemergtem S2-Stand; A2 nach der
F-S4-C-Entscheidung. **D1 zuletzt.**

B und C fassen ausschließlich `android/` an und können deshalb parallel zu
S2/S3 auf eigenem Zweig laufen (Rahmenplan, Abschnitt 5); die
Doku-Nachträge in `Lizenzen.md`, `Technik.md` und `CLAUDE.md` bleiben bis
D1 liegen, damit keine gemeinsame Datei entsteht. Block A wartet, weil
`spur_lib.php`, Tagesansicht, Geräteseite und der Vertrags-Nachtrag mit S2,
S3 und dem R42-Kleinstpaket kollidieren.

Ein Push auf `main` deployt `server/` sofort (CLAUDE.md 3); Änderungen nur
unter `android/` lösen kein Deploy aus. Gepusht wird einmal am Ende, nach
ausdrücklicher Bestätigung (K7). Eine Migration bringt S4 nach heutigem
Stand **nicht** mit (die `devices`-Spalten kommen aus dem R42-Kleinstpaket,
die Spurspeicherung aus S2); sollte F-S4-C doch eine Schemaänderung
verlangen, gilt die Ansage aus CLAUDE.md 3 (`update.php` nach dem Deploy —
ausdrücklich mit ankündigen).

## 7. Prüfprotokoll (Soll)

Wird in der Umsetzung mit Ist-Zahlen gefüllt; eine Prüfung ohne Zahl ist
keine Prüfung. Vorweg das **Nicht-Prüfbare** (2.5): echtes GPS, Akku,
Mobilfunk, Data Layer auf Hardware, Samsung-Tiefschlaf — offen bis zum
Gerätetest; die Uhr-App bleibt bis zum Vorliegen einer Uhr vollständig
ungerätetestet.

| Prüfung | Mittel | Soll |
|---|---|---|
| Baulauf | `gradlew build` headless im Container | Fehler 0; Warnungen gezählt und genannt |
| Vertragstreue Senden | Robolectric + Server-Rundlauf gegen `ingest.php` | alle erzeugten Ketten: 0 `rejected`, 0 `kept_*`, 0 × 400; `seq` lückenlos |
| Ausdünnung | synthetische Ströme aus Referenz-Payloads | Soll-Punktzahlen je Strom getroffen |
| Funkabriss/Fehlerpfade | Robolectric-Matrix (B4, C2) | jeder Fall einzeln grün, Zahl der Fälle genannt |
| Kopplung/Trennen | Robolectric + Container-Rundlauf | alle Antwortpfade aus Vertrag 1a/1b belegt |
| Schneiden | Browser + R24-Kreisläufe + R27-Mittel + Messstand R35 | Kreisläufe 0 unerklärt; R27-Mittel bestehen; Antwortzeit als Zahl |
| GPX-Import | Browser + R24-Kreisläufe + Rundlauf gegen GPX-Abruf | Kreisläufe 0 unerklärt; Punktfolge im Rundlauf identisch |
| Web-Oberfläche | `tools/screenshots/` (berührte Seiten), Konsole, Knopfhöhen | Überlauf 0, Konsole 0, Knöpfe ≠ 44 px: 0 |
| Texte | `tools/wortliste/` | 0 / 0 / 0 |
| Stylesheet | `tools/vollstaendigkeit/`; Stilvergleich, falls `style.css` berührt | keine verlorene Klasse; Abweichungen = geplante Liste |
| APK | `apksigner verify`, SHA-256 | Signatur gültig; Prüfsumme auf der Download-Seite = Datei |

Dazu die Regressionspflicht **R24** unverändert: Vor dem Abschluss beide
Kreisläufe (`csv` und `edbak`), Sollstand 0 unerklärte Abweichungen, Zahlen
ins Prüfdokument; eine neue unerklärte Abweichung ist ein Befund dieser
Phase. Der Gerätetest (ein Dienst auf dem S24; Uhr nach Verfügbarkeit)
läuft nach dem ersten lauffähigen APK als Zuarbeit und ist im Prüfdokument
als eigener, offener Abschnitt geführt — nicht als erledigt erzählt.

## 8. Nicht Umfang

- iOS und watchOS — endgültig (R46).
- Reanimation auf Handy oder Uhr — bleibt Garmin (E-R45-1).
- Store-Verteilung (Play Store, Connect IQ) — Betriebsübergang nach v1.0
  (E-R45-6, R41); sie setzt die Mengenbremse (R19) und die Mengengrenze je
  Konto (R37.10) aus P5 voraus.
- Mengenbremse für `ingest.php` — P5 (R19); S4 hält sich mit E-S4-07 an
  das vermessene Sendeverhalten, damit die spätere Bremse beide Clients
  gleich behandeln kann.
- Serverseite der Gerätestatistik (Backlog Nr. 46) — eigenes Kleinstpaket
  nach R42, weil es eine Schemaänderung mitbringt.
- Verwaltung in der App (Einsätze ansehen, bearbeiten, Patientenfelder) —
  die Verwaltung bleibt im Browser (R45).
- Track-Vereinfachung für die Darstellung und GPX-**Export** — S2
  (Backlog Nr. 2 und 3).

## 9. Zuarbeiten

Aus Rahmenplan Abschnitt 7, hier mit Verortung im Paketplan:

| Was | Wofür | Wann |
|---|---|---|
| Netzfreigaben `dl.google.com`, `maven.google.com`, `repo1.maven.org`, `plugins.gradle.org`, `services.gradle.org` | B1-Probebau (und jeden weiteren Baulauf) | **vor B1** |
| Entscheidung F-S4-A (Mindest-Android-Stand) | B1 | vor B1 |
| Mockup-Freigabe (A0) | A2/A3-Beginn | vor A2 |
| Verwahrung des Signaturschlüssels | jede spätere Auslieferung | mit dem ersten Build (B1) |
| Ein Dienst-Test auf dem S24 | Abnahme Block B | nach dem ersten lauffähigen APK; erfahrungsgemäß zwei bis drei Runden |
| Eine Wear-OS-Uhr (Galaxy Watch4 oder neuer) | Gerätetest Block C | wenn verfügbar; **blockiert nichts** |

## 10. Fehlerfunde (gesammelt, K4)

Funde werden hier gesammelt und nicht nebenbei behoben, außer sie
blockieren die laufende Arbeit.

### B-S4-01 — Logodateien tragen teilweise wieder die alten Farbwerte

Beim Einbinden der Bildmarken in die A0-Mockups nachgemessen (31.08.2026,
Stand `main` = Web 9.15.0), Füllwerte der SVGs:

| Datei | Befund |
|---|---|
| `gen-em_logo_helicopter.svg` | **alle vier Werte alt**: `#587abc`, `#e3322b`, `#f7941d`, Korpus `#1d0e0a` |
| `gen-em_logo_helicopter_weiss.svg` | Farbelemente alt (`#587abc`, `#e3322b`, `#f7941d`), Weiß in Ordnung |
| `gen-em_logo_nef.svg` | Farben richtig, Korpus alt (`#1d0e0a` statt `#1A0500`) |
| `gen-em_logo_nef_weiss.svg` | richtig |

`docs/Design.md` 2.5 erklärt B1 für erledigt („Nachgemessen in
`gen-em_logo_helicopter.svg`: `#1A0500`, `#4280E5`, `#D63338`, `#FF8F1F`")
— das trifft auf den heutigen Stand nicht mehr zu: Der Commit
„Update Logos" (`4ff25af`) hat neue Vektorvorlagen eingespielt und die
alten Werte damit teilweise zurückgebracht. Die PNG-Ableitungen, Favicons
und Uhr-Bilder sind **nicht** nachgemessen. Kein S4-Umfang (Web-Bestand);
nach K4 gesammelt. **Berührung mit S4:** B1 übernimmt die SVGs in die App
— liegt die Berichtigung bis dahin nicht vor, erbt die App die falschen
Werte; das gehört vor B1 entschieden. Bei der Behebung `Design.md` 2.5
mitziehen.
**Entschieden am 31.08.2026: bewusst liegen lassen.** Keine Behebung
vorab; B1 übernimmt den dann aktuellen Stand der Dateien. Damit der Fund
nicht verschwindet, ist er als **Backlog Nr. 49** eingetragen.

### B-S4-02 — Bedienhöhe: 44 px der Weboberfläche gegen 48 dp der Android-Vorgabe

Gefunden beim Bau der Handy-Bausteine (B1). `CLAUDE.md` 5 sagt: „Eine Höhe
für Bedienelemente: **44 px**, mobil wie am Schreibtisch. Es gibt keine
Kompaktvariante." Das App-Mockup (`docs/mockups/S4-app.html`) benutzt
entsprechend `var(--knopf)` = 44 px.

Androids eigene Vorgabe für Berührziele ist **48 dp**. Die vier Pixel
Unterschied wären an einem Schreibtisch gleichgültig; hier nicht — die App
wird **mit Handschuhen im Einsatz** bedient, und das ist genau der Fall, für
den die 48 dp gedacht sind. Zugleich sagt das Mockup selbst, die App folge
„den Android-Konventionen und übernehme aus der Marke Farben und
Zurückhaltung" — die 44 sind eine Web-Zahl, keine Markenzahl.

**Stand B1: 44 dp umgesetzt**, weil `CLAUDE.md` eindeutig ist und eine Zahl
nicht nebenbei geändert wird. Die Konstante steht an **einer** Stelle
(`BEDIENHOEHE` in `handy/src/main/java/.../Bausteine.kt`); eine Änderung ist
eine Zeile. **Zu entscheiden vor dem Gerätetest**, weil der S24-Dienst genau
das prüfen kann, was hier strittig ist.

### B-S4-03 — Das APK der Uhr ist 18 MB groß

Gemessen in B1: `uhr-release-unsigned.apk` = 18 005 460 B (Debug-Fassung
24 376 580 B), `handy-release-unsigned.apk` = 6 989 468 B. Das Uhr-APK ist
also **mehr als doppelt so groß wie das Handy-APK** — obwohl die Uhr-App
weniger tut.

Die Ursache ist bekannt und liegt nicht in eigenem Code: Compose for Wear OS
bringt einen eigenen vollständigen Satz Bausteine mit, und `isMinifyEnabled`
steht auf `false` (Begründung in `uhr/proguard-rules.pro`: ein lesbarer
Stapelauszug ist im Gerätetest mehr wert als ein kleineres APK).

Warum es trotzdem notiert wird: Eine Uhr hat wenig Speicher, und das APK wird
**per FTPS auf den Server gelegt und von dort heruntergeladen** (E-S4-16) —
über Mobilfunk. **Zu entscheiden vor der ersten Auslieferung**, nicht jetzt:
Kandidaten sind R8 für das Release der Uhr (Stapelauszüge dann über die
`mapping.txt`, die verwahrt werden müsste) oder das Weglassen der
Compose-Werkzeugvorschau im Release. Kein B1-Umfang — hier steht nur die
Zahl, damit sie nicht erst beim Hochladen auffällt.

### B-S4-04 — Die Akku-Freistellung verträgt sich nicht mit dem Play Store

Gefunden von Lint in B3: `REQUEST_IGNORE_BATTERY_OPTIMIZATIONS` verstößt gegen
die Inhaltsrichtlinie des Play Store; erlaubt sind dort nur wenige benannte
Fälle (Wecker, VoIP, Gerätesuche). Eine App, die eine GPS-Spur über zwölf
Stunden führt, steht nicht auf dieser Liste — obwohl genau sie die Freistellung
braucht: Ohne sie hält Android die Aufzeichnung an, und niemand merkt es.

**Heute ist das folgenlos:** S4 verteilt ohne Store (E-R45-6), und die
Store-Verteilung steht ausdrücklich nicht im Umfang (Abschnitt 8). Die Warnung
bleibt deshalb **stehen und gezählt**, statt stummgeschaltet zu werden — sie
ist die Erinnerung an eine Entscheidung, die beim Betriebsübergang nach v1.0
ansteht.

**Was dann zu entscheiden wäre:** die Freistellung nur noch als Hinweistext
anbieten (die NutzerIn stellt sie selbst in den Systemeinstellungen ein) statt
über den gezielten Dialog — das ist richtlinienkonform und einen Schritt
umständlicher. Kein S4-Umfang; nach K4 gesammelt.

### B-S4-05 — Eine nachträgliche Phase wäre liegengeblieben (behoben)

**Gefunden in C2**, als die Abnahme verlangte, dass beim Phasenkonflikt
Uhr/Handy **beide Einträge gesendet** werden. Gespeichert wurden sie; gesendet
nicht zwingend.

Der Sender fragt `Puffer.hatArbeit()`, und das prüft zwei Dinge: ob die
Metadaten je bestätigt wurden und ob es unbestätigte Spurpunkte gibt. Ein
laufender Einsatz wird während des Dienstes in Teilen hochgeladen; danach
steht `metadaten_bestaetigt = 1`. Kommt jetzt eine Phase dazu — vom Handy oder
von der Uhr —, ändert sich **keines von beiden**. Solange weiter Punkte
eintreffen, fällt es nicht auf; steht das Fahrzeug, bleibt die Phase liegen,
bis der Einsatz abgeschlossen wird. Im Nur-Aufzeichnen-Betrieb bis zum
Dienstende.

*Herkunft:* B5 (Web-Fassung nicht betroffen, die Regel gibt es nur im Client).
*Behoben in C2* und nicht gesammelt, weil die Abnahme von C2 sonst nicht
erfüllbar gewesen wäre: `phaseAnhaengen` nimmt die Metadaten-Bestätigung in
derselben Transaktion zurück, in der sie die Phase einfügt. Belegt durch
`UhrannahmeTest.einPhasenkonfliktErzeugtZweiEintraegeUndBeideGehenRaus`
(hatArbeit vorher **false**, nachher **true**) und im Rundlauf gegen
`ingest.php` durch drei Phaseneinträge auf dem Server.

### B-S4-06 — Die Wortliste erreicht `android/` nicht

`tools/wortliste/` prüft drei Bereiche: `server/*.php`, `server/assets/*.js`
und die normative Dokumentation — zusammen **101 Dateien**. Die sichtbaren
Texte der App stehen in `android/*/src/main/res/values/strings.xml` und sind
seit B1 **nie** durch das Werkzeug gelaufen; der Lauf nach C2 meldet 0 Treffer
und hat dabei keine einzige Zeile der App angesehen. Genau der Fall, vor dem
`CLAUDE.md` 6 warnt: eine grüne Zahl, die etwas anderes gemessen hat.

*Ersatzweise von Hand nachgezählt* (C2, eigenes Skript gegen dieselbe
`sperrliste.json`, Großschreibungsregel beachtet): **123 sichtbare Werte in
zwei Dateien, 3 Treffer** — zweimal „Bildmarke Rettungshubschrauber" (der
Alternativtext des Luftlogos) und einmal „RTH" (die Bezeichnung der Logowahl).
Alle drei gehören zur Klasse *Homonym*, für die die Weboberfläche an denselben
Stellen bereits Ausnahmen führt (`design-bildmarke`, `logowahl-option`,
`fahrzeugtypen`). Aus C2 stammt kein Treffer.

*Nicht behoben:* `tools/` liegt außerhalb des Schreibrahmens dieser
Umsetzung. Zu tun ist ein vierter Bereich in `sperrliste.json` samt drei
Ausnahmen — in Block D oder in einer eigenen Runde.

### B-S4-07 — Die Kopplungsseite verlangte, was der QR-Code mitbringt (behoben)

**Gefunden am ersten Bildschirmfoto der Handy-App**, nicht durch Lesen und
nicht durch einen Prüffall.

Der Hinweiskasten zeigte „Trage **zuerst** die Adresse deines Servers ein",
sobald das Adressfeld leer war — beim Erststart also immer. Darunter stand das
Adressfeld, und **erst darunter** der Knopf „QR-Code scannen". Die Seite las
sich damit als Reihenfolge: erst Adresse eintippen, dann scannen.

Genau das ist falsch: Der QR-Code trägt nach E-S4-15 **beides**, Adresse und
Code (`{"server":"…","code":"…"}`), und `KopplungAnsicht` füllt das Feld beim
Scannen selbst. Die App verlangte als ersten Schritt, was sie einem gerade
abnehmen wollte. Der Kopfkommentar derselben Datei sagte die Absicht seit B2
richtig — die Oberfläche sagte das Gegenteil.

*Behoben in Android 0.7.1*, mit vorhandenen Bausteinen (kein neuer Baustein,
keine Mockup-Pflicht): Das Adressfeld erscheint nur noch auf dem Weg „von
Hand"; der Hinweis nennt die Herkunft des Codes und dass der QR-Code die
Adresse mitbringt; der zweite Knopf heißt „Adresse **und** Code von Hand
eingeben", damit am Knopf ablesbar ist, was der QR-Weg erspart.
`sync_fehlt_server` ist ausgetragen.

**Was daran über die eine Zeile hinausgeht:** 214 Prüffälle konnten das nicht
sehen. Sie prüfen, was die Bedienung *entscheidet*, nicht was auf dem Glas
*steht*. Der Fund ist das erste Ergebnis der Bildprüfung und das Argument
dafür, sie einzurichten.

### B-S4-08 — Der Uhr-Knopf hielt die zugesagten 48 dp nicht (behoben)

**Gemessen, nicht gesehen** — das Bild sah gut aus:

| Uhr | Knopfhöhe | Soll |
|---|---|---|
| 192 dp (kleine Runduhr) | **35,5 dp** | 48 dp |
| 227 dp (Galaxy Watch) | 48,0 dp | 48 dp |

`UhrKnopf` setzt `heightIn(min = UHR_BEDIENHOEHE)` mit 48 dp (E-S4-41). Die
Grundspalte bekam aber die Displayhöhe als feste Obergrenze und **stauchte
ihre Kinder**, wenn der Inhalt nicht passte; `heightIn(min = …)` beugt sich
einer kleineren Elternbeschränkung. Betroffen war ausgerechnet die kleine
Uhr — also genau die, für die die 48 dp gedacht sind. Auf der großen stimmte
es, weil dort der Platz reichte; ein Prüflauf auf nur einer Größe hätte
nichts gemerkt.

*Behoben in Android 0.7.2:* Die Grundspalte bekommt `verticalScroll` und misst
ihre Kinder damit unbeschränkt. Der Knopf steht auf **48,0 dp auf beiden
Größen**, und was nicht aufs Glas passt, ist durch Wischen erreichbar statt
still abgeschnitten — auf Wear OS die übliche Bedienung, in `Phasenliste` seit
C1 benutzt. Die Zusicherung steht jetzt als Zahl im Prüffall
(`UhrBildTest.pruefeBedienhoehe`), nicht als Absicht im Quelltext.

### B-S4-08b — Der große Knopf wurde vom runden Glas beschnitten (behoben)

Aufgefallen am Emulator-Bild, gemessen im Prüfstand. Der Anteil des Inhalts,
der außerhalb des einbeschriebenen Kreises liegt:

| Uhr | vorher | nachher |
|---|---|---|
| 192 dp | **13,55 %** | **0,00 %** |
| 227 dp | 1,66 % | **0,00 %** |

Zeilenweise ausgewertet lag der Beschnitt **ausschließlich im Knopf**
(y 156–188 dp); alles darüber war im Kreis. Ursache war die Anordnung: Der
Inhalt war höher als das Glas, also begann die Spalte oben und schob den Knopf
an den unteren Rand — dorthin, wo der Kreis auf 55 dp zusammenläuft.

*Behoben in 0.7.3* durch zwei Maße und eine Reihenfolge, keinen neuen
Baustein: Der Knopf steht **über** der Verbindungszeile (nahe der Mitte, wo
der Kreis am breitesten ist), `MARKE_START` geht von 27 % auf 22 %, und „löst
am Handy aus" ist ersatzlos gestrichen — der Satz lag ohnehin außerhalb des
Glases und war nie zu sehen. Dieselbe Umstellung in der laufenden Ansicht: die
Verbindungszeile hinter beide Knöpfe.

**Ein dritter Eingriff wurde verworfen, weil die Messung ihn widerlegt hat.**
Ein `KNOPF_BREITENANTEIL = 0.86f` sollte Reserve für die Krümmung geben; er
stand schon im Code und in diesem Abschnitt, bevor er gemessen war. Das Bild
zeigte dann: Die Umstellung allein bringt den Knopf auf 0,00 %, und der
schmalere Knopf lässt „Dienst beginnen" **auf zwei Zeilen umbrechen** — er
löste ein gelöstes Problem und schuf ein neues. Entfernt.

Die Zahl steht jetzt als **Zusicherung** im Prüffall, und zwar für die
**Knopfflächen**, nicht für jeden Punkt. Das ist die genauere Frage: Ein Stück
Bildmarke am Rand ist unschön, ein gekappter Knopf ist ein Bedienelement, das
man nicht trifft. Die laufende Ansicht passt auf der 192-dp-Uhr ohnehin nicht
ohne Bildlauf (221 dp Inhalt auf 192 dp); dort auf jeden Punkt zu bestehen
hieße, sie auszudünnen, bis nichts mehr dasteht.

### B-S4-09 — Die Uhr behauptet „Handy verbunden", bevor sie je gesendet hat

Auf dem ersten Bild der Startseite steht **„Handy verbunden"** — ohne
gekoppeltes Handy, ohne eine einzige gesendete Nachricht. Ursache ist die
Vorgabe `handyErreichbar = true` in [Uhrzustand]; die Anzeige unterscheidet
nicht zwischen „zugestellt" und „noch nie versucht".

Das widerspricht **E-S4-47**, der in C2 ausdrücklich festhält, dass der
Unterschied angezeigt und nicht geglättet wird. Der Fehler ist harmloser als
B-S4-08 — er verschweigt keine Aufzeichnungslücke, weil vor dem Dienststart
nichts aufzuzeichnen ist —, aber er ist dieselbe Art Aussage: eine
Behauptung über etwas, das die Uhr nicht wissen kann.

*Behoben in 0.7.3* — mit genau dem dritten Zustand: `handyErreichbar` ist
`Boolean?`, und `null` heißt „noch nichts versucht". Die Startseite sagt dann
**„noch nichts gesendet"** in Sand statt „Handy verbunden" in Blau. Zwei
Prüffälle halten es fest: der Anfangszustand behauptet nichts, und der erste
Sendeversuch macht daraus eine Beobachtung — in beide Richtungen.

## 11. Statuspflege

Nach jedem Paket: dieses Konzept fortschreiben (erledigt, Probleme,
Entscheidungen, Prüfstand). Nach Phasenende: Prüfdokument
(`docs/Pruefdokument-S4-Handy-Uhr-Client.md`), Statuszeile im Rahmenplan
(Abschnitt 6), Changelog-Einträge mit den Präfixen `Web` (Block A) und
`Android` (Blöcke B/C). Die Entscheidung zu F-S4-A bis F-S4-C wird vor dem
jeweils betroffenen Paket eingeholt und hier als E-Eintrag nachgetragen
(K6).

---

## 12. Umsetzung — Stand und Prüfstand

Fortschreibung nach jedem Arbeitspaket (K5, CLAUDE.md 7). Bis Block D trägt
dieses Kapitel die Chronik: Die Einträge in `docs/CHANGELOG.md` mit dem Präfix
`Android`, die Backlog-Pflege und die Nachträge in `Lizenzen.md`, `Technik.md`,
`Handbuch.md` und `Geraete-Eingabe.md` folgen dort gesammelt (Abschnitt 6 —
so entsteht bis dahin keine Datei, an der zwei Zweige gleichzeitig arbeiten).

**Was bis zum Gerätetest offen bleibt** (steht hier vorn, nicht in einer
Fußnote): echtes GPS, Akkuverhalten, Mobilfunk-Upload, Bluetooth und der Data
Layer auf Hardware.

**Der Satz „es gibt keinen Emulator" (E-R45-8) stimmte nicht** und ist mit
0.7.2 berichtigt — Begründung und Zahlen in E-S4-49. Es gibt seither Bilder
beider Apps, und das erste hat sofort einen Fehler gezeigt (B-S4-07), das
zweite einen weiteren (B-S4-08). **E-R45-7 gilt unverändert:** Eine Uhr aus
Hardware gibt es nicht, und der Data Layer bleibt ungeprüft.
Die Uhr-App ist vollständig blind gebaut (E-R45-7).

### B1 — Gerüst und Probebau · Android 0.1.0 · erledigt

**Was entstanden ist.** Das Gradle-Projekt `android/` mit den Modulen `handy/`
und `uhr/`, beide auf Anwendungs-ID `org.genem.nadoku` (E-S4-01); die
Versionszählung in `android/version.properties` samt gerechnetem Versionscode;
die siebzehn Farb-Token als Android-Ressource; beide Bildmarken in vier
Fassungen; das Launcher-Symbol als adaptives Symbol; die ersten Bausteine der
Handy-Oberfläche (Kopfleiste, Karte, drei Knopfarten, Zustandszeile,
Hinweiskasten); `android/LIESMICH.md` mit Bauanleitung für Container und
Arbeitsplatz.

**Der Probebau ist die eigentliche Abnahme, und er trägt** — aber erst nach
zwei Nachträgen an der Zuarbeitenliste (siehe „Probleme").

#### Prüfstand B1

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew clean build`, headless | **BUILD SUCCESSFUL, 53 s** | fehlerfrei |
| Lint `handy` | `lintDebug`, Textbericht | **0 Fehler, 18 Warnungen** | 0 Fehler |
| Lint `uhr` | `lintDebug`, Textbericht | **0 Fehler, 0 Warnungen** | 0 Fehler |
| APK `handy` | `assembleRelease` unsigniert | **6 989 468 B, 120 Einträge** | baut |
| APK `uhr` | `assembleRelease` unsigniert | **18 005 460 B, 136 Einträge** | baut |
| Paketkennung beider APK | `aapt2 dump badging` | `org.genem.nadoku`, Code 100, Name 0.1.0, `targetSdk 36` — **identisch** | gleich (E-S4-01) |
| Berechtigungen `uhr` | `aapt2 dump badging` | **`WAKE_LOCK`, kein `INTERNET`** | kein Serverzugang (E-S4-11) |
| Signaturweg | `signatur.properties` gesetzt, `assembleRelease`, `apksigner verify` | **beide APK signiert, Zertifikat SHA-256 `078c…ad64` identisch** | dieselbe Signatur (E-S4-01) |
| Signaturschlüssel im Repositorium? | `git check-ignore -v` | **`*.jks` und `signatur.properties` greifen; `git status` sieht beide als ignoriert** | nie eingecheckt (E-S4-16) |
| Farb-Token gegen `:root` | `android/werkzeuge/farbabgleich.py` | **18 Web-Token, 17 App-Token, 0 Abweichungen, 0 eigene Farbwerte**, 1 nicht übernommen (`--spur-8`, Spurfarbe der Karte — die App hat keine Karte) | 0 / 0 (E-S4-22a) |
| Kontraste | `android/werkzeuge/kontraste.py` | **16 Paare, 0 unter dem Zielwert**; Kleinstwert `--rot` auf `--asphalt` = 4,12:1 (grafisches Objekt, Ziel 3:1) | AA (CLAUDE.md 5) |
| Bildmarken gegen Vorlage | `android/werkzeuge/bildmarken.sh pruefen` | **4 Dateien, 0 Abweichungen** | bitgleich |
| Gradle-Verteilung | Quervergleich Wrapper-Bezug ↔ Container-Gradle | **188 von 188 JAR bitgleich, 0 Abweichungen** | belegt |
| Dateien im Commit | `git add -An android/` | **41, keine Bauausgabe, kein Geheimnis** | sauber |

**Was der Prüfstand nicht sagt.** Kein Prüffall ist gelaufen — es gibt in B1
noch keinen (`test` meldet `NO-SOURCE`). Kein APK wurde installiert oder
gestartet; „baut" heißt hier gebaut, nicht lauffähig. Die achtzehn
Lint-Warnungen sind sämtlich derselbe Befund („eine neuere Fassung ist
verfügbar") auf `gradle/libs.versions.toml`; sie werden **nicht**
stummgeschaltet, weil eine unterdrückte Warnung später eine echte verdeckt.

#### Probleme und wie sie gelöst wurden

1. **Die Android-Kommandozeilenwerkzeuge lagen nicht im Container.** Die
   Zuarbeitenliste (Abschnitt 9) nennt sie zusammen mit dem JDK als vorhanden;
   JDK 21 und Gradle 8.14.3 waren da, das SDK nicht (`ANDROID_HOME` leer, kein
   Verzeichnis). Geholt von `dl.google.com` — der Domain, die ohnehin auf der
   Freigabeliste steht: `commandlinetools-linux-11076708`, dazu
   `platform-tools`, `platforms;android-36` und `build-tools;36.0.0`, rund
   460 MB. Der Ablauf steht in `android/LIESMICH.md` 2, damit die nächste
   Sitzung ihn nicht wiederfinden muss.
   **Nachtrag zur Zuarbeitenliste:** „Android-Kommandozeilenwerkzeuge im
   Container" ist keine Zuarbeit, sondern ein Schritt der Bauanleitung.

2. **Eine sechste Netzfreigabe fehlte auf der Liste, und ohne sie gibt es
   keinen Wrapper-Lauf.** Die fünf genannten Domains antworten alle. Der
   Gradle-Wrapper lädt seine Verteilung aber von `services.gradle.org`, und
   diese Adresse **leitet auf `github.com` weiter**
   (`gradle/gradle-distributions`). Im Container trägt die Weiterleitung; auf
   der Freigabeliste stand sie nicht. Eine **siebte**, `downloads.gradle.org`,
   ist gesperrt (403) — sie trägt die Prüfsummendatei der Verteilung.
   **Folge:** `gradle-wrapper.properties` führt **kein**
   `distributionSha256Sum`. Die Prüfsumme aus der Datei zu rechnen, die man
   gerade geladen hat, wäre keine Prüfung. Stattdessen wurde gegenseitig
   belegt: Die vom Wrapper geladene Verteilung und das im Container
   vorinstallierte Gradle 8.14.3 stammen aus zwei Quellen; ihre
   Programmbibliotheken sind **188 von 188 JAR bitgleich**. Wer ohne diese
   Sperre arbeitet, trägt die Prüfsumme von `gradle.org/release-checksums/`
   nach; der Hinweis steht in `LIESMICH.md` 2.1.

3. **Der erste Baulauf scheiterte an XML-Kommentaren.** In einem
   XML-Kommentar ist die Zeichenfolge `--` unzulässig; die deutschen
   Gedankenstriche im Kommentarstil dieses Repositoriums sind genau das. In
   vierzehn Dateien durch `—` ersetzt. Kein inhaltlicher Eingriff, aber eine
   Regel, die für jede künftige XML-Ressource gilt — sie steht deshalb hier
   und nicht nur im Commit.

4. **Zwei Lint-Fehler und drei Lint-Warnungen aus dem Gerüst selbst**, alle
   behoben statt stummgeschaltet: `MissingClass` (die Activity-Namen im
   Manifest sind relativ zum Namensraum, die Klassen liegen aber in
   Unterpaketen → `.handy.HauptActivity` bzw. `.uhr.UhrActivity`),
   `RedundantLabel`, `ObsoleteSdkInt` (der Ordner `mipmap-anydpi-v26` ist bei
   `minSdk` 26 bzw. 30 überflüssig) und eine ungenutzte Zeichenkette. Das
   adaptive Symbol liegt jetzt im Grundordner `mipmap/`.
   **Nebenbefund:** `mipmap-anydpi` **ohne** Versionsstufe wurde von AAPT2
   nicht als Ressource aufgenommen (`resource mipmap/symbol not found`) —
   deshalb `mipmap/` und nicht `mipmap-anydpi/`.

#### Entscheidungen, die in B1 gefallen sind

Sie füllen die vorhandenen E-Einträge aus und ersetzen keinen davon.

- **E-S4-23 — `compileSdk`/`targetSdk` ist 36, nicht 37; AGP bleibt bei
  8.13.2.** E-S4-03 verlangt „`targetSdk` aktuell". Aktuell wäre API 37 — es
  gibt dieses API aber **nur mit Nebenversionen** (`android-37.0`, `-37.1`,
  `-37.2`); eine schlichte `platforms;android-37` existiert nicht, und die
  Schreibweise dafür (`compileSdk` + `compileSdkMinor`) beherrscht erst AGP 9.
  AGP 9 ist ein Umbau der Bau-Sprache; ihn blind einzuführen, um eine
  API-Stufe zu gewinnen, die die App nicht braucht, ist der teure Weg zum
  kleinen Gewinn. **API 36 erfüllt den Zweck von E-S4-03 vollständig:** Die
  strengen Regeln für Vordergrunddienste gelten ab API 34, und die App
  deklariert `FOREGROUND_SERVICE_LOCATION` (ab B3). Der Wechsel auf AGP 9 samt
  API 37 ist eine eigene, absichtliche Runde — nicht ein Nebeneffekt von B1.
- **E-S4-24 — Der gemeinsame Quelltext bekommt kein drittes Modul.**
  E-S4-02 legt zwei Module fest. Beide brauchen dieselben Farb-Token,
  Bildmarken, Phasenlisten und später dasselbe Nachrichtenformat. Statt ein
  drittes Modul zu erfinden, wird `android/gemeinsam/` in **beide** Module
  eingebunden (`sourceSets[…].java.srcDir` und `.res.srcDir`). Der gemeinsame
  Teil wird zweimal übersetzt; das kostet Bauzeit und sonst nichts, und es
  spart eine Modulgrenze mit eigenem Manifest, eigener Versionierung und
  eigener Lint-Auswertung.
- **E-S4-25 — Die Versionszählung beginnt bei 0.1.0.** Eine 1.0.0 auf einem
  Stand, der noch nicht koppeln und nicht senden kann, wäre eine falsche
  Zusage. Die Nebennummer zählt bis dahin die Arbeitspakete (0.1.0 = B1 …
  0.7.0 = C2); die 1.0.0 gehört an das Ende des Gerätetests (E-R45-7). Der
  Versionscode wird aus der Nummer **gerechnet** (`Haupt·10000 + Neben·100 +
  Korrektur`) und nicht danebengeschrieben.
- **E-S4-26 — Kein eigener Farbwert, auch nicht für die Uhr.** Das
  App-Mockup zeigt für die Uhr reines Schwarz (`#000`) und eine Nebenschrift
  in `#B9B2A9`. Beide stehen nicht in `:root`, und E-S4-22a sagt „ein eigener
  Farbwert entsteht nicht". Genommen werden deshalb `--asphalt` (#1A0500) als
  Grund — auf einem OLED-Display derselbe abgeschaltete Bildpunkt und derselbe
  Stromverbrauch — und `--sand` (#D4C7AD) als Nebenschrift (11,80:1 auf
  Asphalt gegen 19,71:1 für Weiß). Der Entscheidungstext gilt, nicht die
  Skizze; auf dem Gerät ist der Unterschied nicht zu sehen, in der Skala
  schon.
- **E-S4-27 — Der Signaturweg ist belegt, nicht nur beschrieben.** Der
  Schlüssel (RSA 4096, PKCS#12, gültig bis 23.08.2056) ist erzeugt, liegt
  außerhalb des Repositoriums und wurde dem Auftraggeber als Datei übergeben.
  Der Bauweg findet ihn über `android/signatur.properties`; fehlt sie,
  entsteht ein unsigniertes Release — genau so läuft es im Container und im
  CI-Prüftor (E-R45-9). Beide Module wurden **einmal probeweise signiert**;
  `apksigner verify` bestätigt dasselbe Zertifikat für Handy und Uhr, was die
  Bedingung des Data Layer (gleiches Paket, gleiche Signatur) belegt.

#### Neue Fehlerfunde (K4, gesammelt in Abschnitt 10)

`B-S4-02` und `B-S4-03` — Beschreibung dort.

### B2 — Kopplung, Trennen, Schlüsselablage · Android 0.2.0 · erledigt

**Was entstanden ist.** Die Kopplung gegen `pair.php` mit Code-Eingabe von
Hand und QR-Scan (ZXing auf CameraX), das Trennen nach den Nr.-14-Regeln,
die verschlüsselte Schlüsselablage (E-S4-13), die Sync-Anzeige mit den drei
Zuständen aus Backlog Nr. 11 — und die Oberfläche dazu: Kopplungsbildschirm,
Scan-Ansicht, gekoppelte Ansicht mit Trennen.

**Vor Beginn geprüft (Haltepunkt).** Das R42-Kleinstpaket liegt **nicht** auf
`main`. Nachgewiesen an zwei Stellen, nicht vermutet: `server/pair.php` liest
`$b['geraet']` an keiner Stelle aus und trägt als Bezeichnung fest `'Uhr'`
ein; `DESCRIBE devices` in der frischen Container-Installation liefert
`id, user_id, device_id, api_key_hash, label, active, last_seen, created_at`
— **keine Spalte für Art oder Modell.** F-S4-B war damit offen und wurde
vorgelegt.

#### Prüfstand B2

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew clean build`, headless | **BUILD SUCCESSFUL** | fehlerfrei |
| Lint `handy` | `lintDebug` | **0 Fehler, 18 Warnungen** (alle „neuere Fassung verfügbar", keine andere Art) | 0 Fehler |
| Lint `uhr` | `lintDebug` | **0 Fehler, 0 Warnungen** | 0 Fehler |
| Prüffälle gesamt | `testDebugUnitTest` **und** `testReleaseUnitTest` | je **56 Fälle, 0 Fehlschläge, 0 Fehler**; ohne laufende Installation 6 übersprungen, mit ihr 0 | alle grün |
| Kopplung: Erfolg | Prüfserver + `HttpNetzweg` | **1 Fall** — Zugangsdaten im Tresor, POST auf `pair.php`, **ohne** Auth-Kopfzeilen | belegt |
| Kopplung: `geraet`-Block | Prüfserver, Körper nachgelesen | **1 Fall** — `art:"handy"`, `teil:null`, `hersteller`, `modell`, `br`, `ho`, `touch`, `fw`, `sdk`, `app`; **`ciq` fehlt** | E-S4-28 |
| Kopplung: Fehlerpfade | Prüfserver | **6 Fälle** — 400 `code`, 404 `invalid`, 409 `device_limit` (Servermeldung durchgereicht), 429 `zu_viele_versuche`, 500 `server`, keine Verbindung | Vertrag 1a |
| Kopplung: Vorprüfung | Prüfserver, Anfragezähler | **3 Fälle** — Vertipper und die vier ausgeschlossenen Zeichen (0, O, 1, I) gehen **gar nicht erst hinaus** (0 Anfragen); `200` ohne Zugangsdaten gilt nicht als Kopplung | belastet den Ratenschutz nicht |
| Trennen | Prüfserver | **5 Fälle** — Erfolg (Auth-Kopfzeilen gesetzt, `{"aktion":"trennen"}`), 401 trennt trotzdem lokal, ohne Antwort trennt lokal, **Rückstand sperrt vollständig** (0 Anfragen, Kopplung bleibt stehen), ohne Kopplung geschieht nichts | Vertrag 1b, Nr. 14 |
| **Server-Rundlauf** | echte Container-Installation, `pair.php` | **6 Fälle, alle grün** — echte Kopplung (`dev-` + 32 Hex, 36 Zeichen), Code nur einmal einlösbar, unbekannter Code abgewiesen, Trennen löscht das Gerät, getrennte Zugangsdaten werden abgewiesen, **`MAX_GERAETE` greift bei 5** und die Servermeldung kommt durch | Abnahme B2 |
| Zustand danach | `SELECT COUNT(*) FROM devices` | **0** — jeder Fall räumt hinter sich auf; 10 Codes verbraucht | Konto unverändert |
| Schlüsselablage | Robolectric | **8 Fälle** — Rundlauf, Löschen entfernt auch die Zwischendatei, **jeder Schreibvorgang mit neuem Zufallswert**, beschädigte Ablage gilt als nicht gekoppelt, fremder Schlüssel öffnet nicht, `toString()` verrät nichts | E-S4-13 |
| **Kein Klartext im Speicherabbild** | Robolectric, ganzes App-Verzeichnis durchsucht | **2 Dateien durchsucht** (`files/tresor.bin` 127 B, `shared_prefs/nadoku.xml` 146 B), **0 Treffer** für Kennung und Schlüssel | 0 |
| Server-Adresse | reine JVM | **9 Fälle** — Schema ergänzt, `http` → `https`, Endpunkt und Abfrage abgeschnitten, Unterverzeichnis bleibt, IPv4 und `localhost` gültig, 8 unbrauchbare Eingaben abgewiesen | E-S4-12/14 |
| QR-Inhalt | Robolectric | **4 Fälle** — Gutfall, Normalisierung, 6 fremde Codes geben `null` statt einer Ausnahme, 4 unvollständige ebenso | E-S4-15 |
| APK | `assembleRelease` | `handy` **8 987 656 B**, `uhr` **18 005 460 B**, beide unsigniert | baut |
| Fassung im APK | `aapt2 dump badging` | **`versionName='0.2.0'`, Code 200** | E-S4-25 |

**Was der Prüfstand nicht sagt** — an dieser Stelle und nicht in einer
Fußnote:

- **Der `AndroidKeyStore` ist ungeprüft.** Robolectric bringt ihn nicht mit
  (`KeyStoreException: AndroidKeyStore not found`), einen Emulator gibt es
  nicht (E-R45-8). Geprüft ist der ganze Umschlag — AES-256-GCM, frischer
  Zufallswert, Rundlauf, kein Klartext auf der Platte —, weil er in einer
  gemeinsamen Oberklasse liegt und im Prüfstand **dieselbe** Klasse läuft wie
  auf dem Gerät. Ungeprüft ist **genau eine überschriebene Methode**: dass der
  Schlüssel im Keystore entsteht und nicht exportierbar ist. Sie gehört auf
  die Prüfliste des Gerätetests.
- **Die Kamera ist ungeprüft.** `QrKamera` ist eine Hülle um CameraX. Geprüft
  ist, was dahinterliegt (`QrLeser` auf rohen Helligkeitswerten, `QrInhalt`);
  dass CameraX Bilder liefert und die Freigabefrage erscheint, zeigt erst das
  Gerät.
- **Der Rundlauf lief über Klartext-HTTP**, nicht über HTTPS: Die lokale
  Installation trägt ein selbstsigniertes Zertifikat, und es dem Prüfstand
  beizubringen hieße, ihm etwas beizubringen, was die App nie tun darf. Dass
  die App nur HTTPS spricht, ist in `ServeradresseTest` belegt.
- **Kein Bildschirmfoto** (Stand B3; seit 0.7.2 gäbe es eines, siehe
  E-S4-49). Die Oberfläche ist
  gebaut, aber nicht gesehen worden.

#### Probleme und wie sie gelöst wurden

1. **Der Server-Rundlauf brauchte erst eine Installation, und die gab es
   nicht.** Im Container fehlte MariaDB vollständig (`mysqld_safe: not
   found`, kein `/var/lib/mysql`); PHP 8.4 mit `pdo_mysql` war da. Aus dem
   Ubuntu-Archiv nachinstalliert (`archive.ubuntu.com` antwortet; nur die
   PPAs sind gesperrt), Datenbank angelegt, `install.php` über `curl` mit
   CSRF und Einrichtungsnachweis durchlaufen. **Nachtrag zur Zuarbeitenliste:
   „Server-Rundlauf gegen `ingest.php` in der Container-Installation" (2.5)
   setzt einen Datenbankserver voraus, der nicht im Bild ist.** Der Ablauf
   steht in `android/LIESMICH.md`; B4 braucht ihn wieder.

2. **Zwei echte Fehler in der Adress-Ergänzung, gefunden von den eigenen
   Prüffällen.** `HTTPS://…` blieb großgeschrieben stehen, und eine
   IPv4-Adresse wurde abgewiesen — die Rechnerprüfung verlangte eine
   Buchstaben-Endung. Beides behoben: Schema und Rechnername werden
   kleingeschrieben, der Pfad **nicht** (auf einem Linux-Server sind
   `/NAdoku/` und `/nadoku/` zwei Verzeichnisse), und die Rechnerprüfung
   kennt jetzt Namen, IPv4 und `localhost`.

3. **Die Rundlauf-Fälle hingen voneinander ab.** `MAX_GERAETE` ist 5, JUnit
   sichert keine Reihenfolge zu: Der Grenzfall füllte das Konto, und zwei
   Fälle danach scheiterten an `device_limit` — an einem Zustand, den ein
   anderer Prüffall hinterlassen hatte. Jetzt räumt jeder Fall hinter sich
   auf (`@After` trennt, was noch im Tresor steht; der Grenzfall merkt sich
   die Zugangsdaten und meldet alle wieder ab). Danach: 6 von 6 grün und
   **0 Geräte** am Konto.

4. **Lint fand zwei echte Lücken in der Oberfläche**, nicht nur
   Fassungshinweise — beide behoben:
   - **Das Trennen hatte keine Rückfrage.** Ein vollflächig roter Knopf
     (E-S4-22a) ohne Rückfrage ist schlimmer als keiner von beidem: Er zieht
     den Blick an, und ein Fehltipp löschte Kopplung samt Geräteschlüssel.
     Jetzt mit Rückfrage, wie beim Einsatzabschluss (E-S4-21b) und wie an der
     Garmin (`Pair.TrennenDelegate`).
   - **Ein fremder QR-Code meldete „Der Server hat einen Fehler gemeldet".**
     Es war aber nie eine Anfrage hinausgegangen. Dasselbe bei einer
     unbrauchbaren Server-Adresse. Beide haben jetzt eigene Zustände
     (`FremderQr`, `AdresseUnbrauchbar`) mit eigenen Texten.
   Aufgefallen sind sie als `UnusedResources`: Die Texte dafür waren
   geschrieben und wurden nirgends benutzt. Dazu drei kleinere Befunde
   (Mehrzahl über `<plurals>` statt zweier Zeichenketten, `SharedPreferences.edit`,
   eine wirklich überflüssige Zeichenkette). Von 34 Warnungen blieben 18 —
   und die 18 sind ausnahmslos Fassungshinweise.

#### Entscheidungen, die in B2 gefallen sind

- **E-S4-28 — Feldform des `geraet`-Blocks für Handys** (Antwort auf F-S4-B,
  entschieden am 31.08.2026 nach Vorlage des Befunds). Umgesetzt wird der in
  F-S4-B vorgeschlagene **Rückfall**: die Felder der Uhr, soweit sie an einem
  Handy dieselbe Bedeutung haben, dazu `hersteller`/`modell` an der Stelle der
  Teilenummer.

  | Feld | Uhr | Handy |
  |---|---|---|
  | `art` | `"uhr"` | **`"handy"`** |
  | `teil` | Teilenummer | **`null`** |
  | `hersteller` | — | **`Build.MANUFACTURER`** |
  | `modell` | — | **`Build.MODEL`** |
  | `br`, `ho` | Display in px | dito |
  | `touch` | vorhanden? | `true` |
  | `fw` | Firmware | **`Build.VERSION.RELEASE`** |
  | `ciq` | Uhr-Plattform | **entfällt**, dafür **`sdk`** (API-Stufe) |
  | `app` | App-Fassung | dito |

  `ciq` wird **weggelassen und nicht auf `null` gesetzt**: Ein Feld, das es
  für diese Geräteart nicht gibt, ist etwas anderes als eines, das das Gerät
  nicht beantworten kann. Der Vertrag stellt beides frei.
  **Was das kostet, wenn R42 anders entscheidet:** eine Zeichenkette je Feld
  in `Geraeteangabe.alsJson()`. Die Feldform steht an genau **einer** Stelle.
  **Was es heute nicht kostet:** nichts — `pair.php` verwirft den Block
  ungelesen, bis A1 (oder R42) ihn annimmt. Auch das ist im Rundlauf
  belegt: Die Kopplung gelingt mit Block, und `devices` hat keine Spalte
  dafür.
- **E-S4-29 — Kein Klartext-Ausweg für den Prüfstand.** Der Server-Rundlauf
  läuft über HTTP gegen `127.0.0.1`, weil die lokale Installation ein
  selbstsigniertes Zertifikat trägt. Der naheliegende Weg — das Zertifikat in
  den Vertrauensspeicher des Prüflaufs legen oder die Prüfung abschalten —
  wird **nicht** gegangen: Ein Prüfstand, der TLS-Prüfungen umgehen kann, ist
  die Vorlage für eine App, die es auch kann. Stattdessen ist die
  HTTPS-Pflicht dort belegt, wo sie wohnt (`ServeradresseTest`), und der
  Rundlauf prüft, was nur er prüfen kann: dass `pair.php` so antwortet, wie
  die App es erwartet.
- **E-S4-30 — Die Prüfumgebung bringt keine fremde Bibliothek mit.** Der
  übliche Weg für HTTP-Prüffälle wäre `MockWebServer` (OkHttp). Damit stünde
  eine vierte Fremdabhängigkeit in `libs.versions.toml` — der Datei, die die
  drei zugelassenen aufzählt (E-S4-04). Der Prüfstand bringt stattdessen
  sechzig Zeilen `ServerSocket` mit (`PruefServer`). Der Gewinn ist nicht die
  Ersparnis, sondern dass die Liste der Fremdbestandteile die Wahrheit sagt.

### B3 — Aufzeichnung und Dienstklammer · Android 0.3.0 · erledigt

**Was entstanden ist.** Der Vordergrunddienst vom Typ `location` mit
dauerhafter Benachrichtigung, die Ausdünnung nach der Regel der Uhr, der
SQLite-Puffer (er trägt schon die Tabellen für B4 und B5), die Dienstklammer
mit Moduswahl „Mit Phasenknöpfen / Nur aufzeichnen", die Wiederaufnahme nach
Absturz und Neustart, die Erststart-Führung zur Akku-Freistellung und die
App-Einstellungen (Logo-Wahl, Sperre der Uhr).

**Der Zustand liegt im Puffer, nicht im Arbeitsspeicher.** Das ist die
tragende Entscheidung dieses Pakets: Oberfläche und Vordergrunddienst sehen
denselben Dienst, und die Wiederaufnahme ist damit kein Sonderfall, sondern
die Regel — nach einem Absturz findet die App den Dienst vor, in dem sie
steckt.

#### Prüfstand B3

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew build`, headless | **BUILD SUCCESSFUL** | fehlerfrei |
| Lint `handy` | `lintDebug` | **0 Fehler, 19 Warnungen** (18 „neuere Fassung verfügbar", 1 `BatteryLife` → Fund B-S4-04) | 0 Fehler |
| Lint `uhr` | `lintDebug` | **0 Fehler, 0 Warnungen** | 0 Fehler |
| Prüffälle | `testDebugUnitTest` **und** `testReleaseUnitTest` | je **96 Fälle, 0 Fehlschläge, 0 Fehler** (6 übersprungen = Server-Rundlauf ohne Installation) | alle grün |
| **Ausdünnung** | 5 synthetische Ströme, **drei unabhängige Wege** verglichen | **5 von 5 Strömen auf den Punkt gleich**, 0 Abweichungen | Soll getroffen |
| — Reiseflug 60 m/s, 900 s | analytisch / `stroeme.py` / App | **901 roh → 901 behalten**, 53 862,8 m, 200,0 m Anstieg | jeder Punkt (60 m ≥ 15 m) |
| — Anfahrt 12 m/s, 600 s | dito | **601 → 301**, 7 192,0 m | jeder zweite (24 m ≥ 15 m) |
| — Stillstand 900 s | dito | **901 → 91**, 0,0 m | nur die 10-s-Bedingung |
| — Stadtfahrt (10× 60 s/8 m/s + 30 s Halt) | `stroeme.py` / App | **901 → 331**, 4 795,0 m | gemischt |
| — **12-h-Dienst** | `stroeme.py` / App | **43 201 → 9 505**, 341 636 m, 1 080 m Anstieg | Größenordnung |
| Größenordnung gegen die Referenz | `messprotokoll.json` | Referenz **56 587 Punkte / 16 Dienste = 3 537 je Diensttag**; App **9 505** = **2,69 ×** | 1 ≤ x ≤ 4 |
| Abstand zur 15-m-Schwelle | `stroeme.py` | kleinster Abstand **0,98 m** (Stadtfahrt) | keine Entscheidung an der letzten Nachkommastelle |
| Dienstklammer | Robolectric mit echtem SQLite | **13 Fälle** — Beginn legt `ad-`-Kennung, Tag und erstes `ar-`-Segment an; zweiter Start ist kein zweiter Dienst; Beenden schließt das Segment; Punkte landen im offenen Segment; `seq` lückenlos | E-S4-08, E-R45-13 |
| **Wiederaufnahme** | Robolectric | **3 Fälle** — Absturz der App (neues Exemplar findet den Dienst, schreibt in dasselbe Segment), Neustart des Handys (Puffer von der Platte neu geöffnet, kein Punkt verloren), erster Punkt nach dem Neustart wird genommen | Abnahme B3 |
| Moduswechsel verlustfrei | Robolectric | **1 Fall** — kein Punkt verloren, dasselbe Segment, es bleibt offen, kein zweites entsteht | E-S4-20 |
| Nur-Aufzeichnen-Dienst | Robolectric, 12-h-Strom | **genau 1 Ruhesegment, 0 Einsätze** | E-S4-20 |
| Kennungen | reine JVM | **8 Fälle** — Präfixe `am-`/`ar-`/`ad-`, Bauform Präfix-Zähler-Zufall(10), kein Zeitstempel, Zähler wird **vor** der Ausgabe gesichert, Überlauf, **2 000 Kennungen ohne eine einzige doppelte** | Vertrag 8, E-S4-09 |
| Zeitformate | reine JVM | **5 Fälle** — ISO/UTC/sekundengenau, Bruchteile fallen weg, Unix-Epoche für Spurpunkte, **`day` ist der lokale Tag** (Nachtdienst über Mitternacht geprüft) | Vertrag 2 |
| Farb-Token, Kontraste, Bildmarken | `werkzeuge/` | **0 / 0 / 0** Abweichungen; 16 Kontrastpaare, 0 unter dem Zielwert | unverändert |
| APK | `assembleRelease` | `handy` **9 052 248 B**, `uhr` 18 005 460 B | baut |

**Was der Prüfstand nicht sagt** — vorn, nicht in einer Fußnote:

- **Der Vordergrunddienst selbst ist ungeprüft.** Es gibt keinen Emulator
  (E-R45-8), kein GPS, keinen `LocationManager`, keine Möglichkeit, Samsungs
  „Apps im Tiefschlaf" nachzustellen. Geprüft ist die Logik dahinter —
  Ausdünnung und Dienstklammer — gegen synthetische Ströme. Ob der Dienst
  zwölf Stunden durchhält, sagt **nur der S24-Dienst**.
- **Die Akku-Freistellung ist ungeprüft.** Ob sie hält, zeigt allein das
  Gerät; der zweite Schalter („Apps im Tiefschlaf") ist herstellereigen und
  von keiner App erreichbar.
- **Die Genauigkeitsschwelle von 100 m ist blind gewählt.** Wie oft sie
  greift, zeigt erst ein Dienst im Feld — im Wald, in der Klinikeinfahrt.
- **Kein Bildschirmfoto.** Auch die neuen Ansichten sind gebaut, nicht gesehen.

#### Probleme und wie sie gelöst wurden

1. **Lint fand zwei Fehler, die erst auf dem Gerät aufgefallen wären.**
   `LocalDate.ofInstant` gibt es erst ab **API 34**, unser `minSdk` ist 26 —
   auf einem Android 8 bis 13 wäre die Bestimmung des Diensttages abgestürzt,
   und zwar beim **Dienstbeginn**. Ersetzt durch
   `augenblick.atZone(zone).toLocalDate()`. Und: Seit Android 12 muss
   `ACCESS_COARSE_LOCATION` **neben** `ACCESS_FINE_LOCATION` stehen, sonst
   lehnt das System die Freigabeanfrage ab. Beides behoben; aufgezeichnet wird
   weiterhin nur mit der genauen Ortung.

2. **Zwei Klassennamen im Manifest waren wieder relativ zum Namensraum
   gemeint.** `.NAdokuApp` und `.aufzeichnung.AufzeichnungsDienst` lösen gegen
   `org.genem.nadoku` auf, die Klassen liegen in `…handy`. Folge: **jeder
   einzelne Robolectric-Fall** schlug fehl (`ClassNotFoundException`), auch
   die aus B2, die zuvor grün waren. Derselbe Fehler wie bei der Activity in
   B1 — er wird beim nächsten Manifest-Eintrag wieder drohen und steht
   deshalb hier.

3. **Die Soll-Zahlen der Ausdünnung durften nicht aus der App stammen.** Eine
   Zahl, die aus derselben Umsetzung kommt, die sie belegen soll, belegt
   nichts. `android/werkzeuge/stroeme.py` rechnet sie deshalb mit einer
   **Portierung der Referenzregel** aus
   `tools/referenzdatensatz/generator/spur.py` nach, und für drei der fünf
   Ströme steht zusätzlich der **analytisch** erwartete Wert daneben (bei
   60 m/s jeder Punkt, bei 12 m/s jeder zweite, im Stand jeder zehnte). Der
   Kotlin-Prüffall erzeugt den Strom **noch einmal selbst** und vergleicht.
   Drei Wege, dieselben Zahlen.
   Damit die drei Wege nicht an der letzten Nachkommastelle auseinandergehen,
   enthalten die Ströme **keinen Zufall und keine Trigonometrie im Erzeuger**
   (nur +, −, ×, ÷ auf festen Dezimalzahlen), und die Geschwindigkeiten sind
   so gewählt, dass keine Entscheidung dicht an der 15-m-Schwelle liegt — der
   kleinste Abstand ist **0,98 m** und wird mitgemessen.

#### Entscheidungen, die in B3 gefallen sind

- **E-S4-31 — Der Zustand des Dienstes lebt im Puffer, nicht im
  Arbeitsspeicher.** Oberfläche und Vordergrunddienst sind zwei Prozessteile,
  die denselben Dienst sehen; ein Zustandsobjekt im Speicher wären zwei
  Wahrheiten, und SQLite ließe beide gleichzeitig schreiben. [Dienstklammer]
  liest deshalb bei jedem Zugriff aus dem Puffer, und die Oberfläche fragt im
  Sekundentakt nach, statt zu halten. **Der Preis** ist eine Abfrage je
  Sekunde, solange die Ansicht offen ist. **Der Gewinn** ist, dass die
  Wiederaufnahme kein Sonderfall mehr ist, sondern das Normalverhalten —
  belegt mit drei Prüffällen.
- **E-S4-32 — Die Genauigkeitsschwelle ist 100 m.** Die Uhr verwirft alles
  unterhalb von `QUALITY_POOR`; Android kennt keine Stufen, sondern einen
  geschätzten Fehler in Metern. 100 m ist bewusst großzügig: Bei dieser
  Streuung ist der Fund aus Funkzelle oder WLAN abgeleitet und läge weit
  jenseits der 15 m, um die es geht. Ein strengerer Wert würfe im Wald oder in
  der Klinikeinfahrt echte Punkte weg. **Blind gewählt, am Gerät
  nachzumessen** (E-R45-7).
- **E-S4-33 — Der Puffer trägt die Tabellen für B4 und B5 von Anfang an.**
  `phase` bleibt bis B5 leer, und `bestaetigt_seq` bis B4 auf 0. Der Grund:
  Eine Schemaänderung an einem Puffer, in dem ein **laufender Dienst** liegt,
  ist teurer als eine Tabelle, die eine Fassung lang leer steht — und
  `onUpgrade` wirft absichtlich eine Ausnahme, statt zu löschen: Hier liegt
  die einzige Kopie ungesendeter Aufzeichnungen.

### B4 — Senden · Android 0.4.0 · erledigt

**Was entstanden ist.** Die Warteschlange in der Reihenfolge der Uhr
(abgeschlossene Einsätze → Ruhesegmente → laufendes Paket), das Senden in
Teilstücken mit `next_seq`-Buchführung, die vier Fehlerpfade des Vertrags,
die Anzeige von `rejected`/`kept_*`, der 15-Minuten-Takt mit seinen
Ereignisauslösern — und die **echte Rückstandssperre** des Trennens, die seit
B2 auf eine Warteschlange gewartet hat.

#### Prüfstand B4

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew build` | **BUILD SUCCESSFUL** | fehlerfrei |
| Lint | `lintDebug` | `handy` **0 Fehler, 19 Warnungen** (18 Fassungshinweise + `BatteryLife`, Fund B-S4-04); `uhr` **0/0** | 0 Fehler |
| Prüffälle | `testDebugUnitTest` **und** `testReleaseUnitTest` | je **133 Fälle, 0 Fehlschläge, 0 Fehler**; ohne Installation 9 übersprungen, mit ihr **0** | alle grün |
| **Rundlauf 12-h-Dienst** | echtes `ingest.php` | **20 Anfragen, 9 505 von 9 505 Punkten**, `rejected` **{}**, `kept_*` **{}**, 400 **0**, Paket entsorgt, Rückstand **0** | 0/0/0 |
| **Rundlauf kurze Ströme** | echtes `ingest.php`, vier eigene Dienste | **5 Anfragen, 1 624 Punkte** (901+301+91+331), je Strom `rejected` {} und `kept_*` {} | 0/0/0 |
| **Rundlauf Nachzügler** | echtes `ingest.php` | dieselbe Kette ein zweites Mal: **angenommen, 0 × rejected** — die Idempotenz über (Gerät, `client_ref`, `seq`) trägt | Vertrag 2 |
| `seq` lückenlos am Server | SQL auf `track_points` | **9 Segmente, alle `lueckenlos`, alle ab `seq` 0** (9 505 · 901 · 901 · 601 · 601 · 331 · 301 · 91 …) | lückenlos |
| Chunkgrenze | Anfragen des Rundlaufs vermessen | **größtes Teilstück 27 789 B mit 500 Punkten**; Grenze 524 288 B → **5,3 %** ausgeschöpft | ≤ 500 Punkte, < 512 KB |
| `seq_from` fortlaufend | 901-Punkte-Kette, jede Anfrage nachgelesen | **0 → 500 → 901**, keine Lücke, kein Rücksprung | Vertrag 2 |
| Auth-Kopfzeilen | jede Anfrage nachgelesen | `X-Device-Id` und `X-Api-Key` an **jeder** Anfrage, POST auf `/ingest.php` | Vertrag 1 |
| **Funkabriss-Matrix** | Prüfserver, je ein Fall | **5 Fälle** — siehe unten | jeder einzeln grün |
| — verlorene Antwort | Gegenstelle fällt weg | „später erneut"; **nichts bestätigt, nichts markiert, Paket bleibt liegen** | unverändert erneut |
| — 401 | | **Lauf pausiert nach genau 1 Anfrage**; nichts bestätigt, **nicht** als fehlerhaft markiert | pausieren |
| — 400 | | als fehlerhaft markiert, **nicht wiederholt**, aus der Warteschlange, aber **nicht gelöscht** | nicht wiederholen |
| — 413 | dreimal abgewiesen | Chunk **500 → 250 → 125 → 62**, danach angenommen | halbieren |
| — 5xx | 503 | „später erneut", nichts markiert, nichts bestätigt | Backoff |
| — **App-Neustart mitten in der Kette** | Puffer von der Platte neu geöffnet | erster Lauf bestätigt 500, zweiter setzt **bei 500** fort; `seq_from` springt nie zurück, lässt nie eine Lücke | Wiederaufnahme |
| Warteschlangen-Reihenfolge | Puffer | abgeschlossener **Einsatz vor** abgeschlossenem Ruhesegment | E-S4-06 |
| Rückstand | Puffer | laufendes Segment **0**; abgeschlossen und ungesendet **1**; nach dem Senden **0**; ein 400-Paket zählt **nicht** mehr mit | Backlog Nr. 11 |
| Nachrichtenkörper | 8 Fälle gegen den Vertrag | `ended_at` null solange nicht `final`; `day_ref` fehlt ganz, wenn keines da ist; Spurpunkt ist ein **Array aus vier Werten**, `ele` darf null sein; Einsatz trägt Kennzahlen und Phasen; **`resus_sessions` geht gar nicht mit** | Vertrag 3/4 |
| Antwortauswertung | 5 Fälle | vier Fehlerpfade zugeordnet; **200 ohne `ok` ist kein Erfolg**; `rejected`/`kept_*` machen den Lauf unsauber | Vertrag 5 |
| Sendetakt | 4 Fälle | jedes Ereignis sendet sofort; der Takt wartet **900 s** | E-S4-07 |
| APK | `assembleRelease` | `handy` **9 052 248 B**, `uhr` 18 005 464 B | baut |

**Was der Prüfstand nicht sagt:**

- **Der 15-Minuten-Takt läuft im Prüfstand nicht ab.** [Sendetakt] beantwortet
  die Frage „ist es soweit?" und hält weder Uhr noch Faden — das ist prüfbar.
  Dass der `Handler` im Vordergrunddienst zwölf Stunden lang alle 15 Minuten
  auslöst, zeigt nur das Gerät.
- **Mobilfunk ist nicht Rückschleife.** Der Rundlauf lief über `127.0.0.1`;
  Paketverlust, Zellwechsel und die Zeitlimits eines realen Netzes sind damit
  nicht geprüft.
- **Kein Bildschirmfoto** (Stand B5; seit 0.7.2 überholt, siehe E-S4-49).

#### Probleme und wie sie gelöst wurden

1. **`HttpURLConnection` wiederholt einen abgebrochenen POST von selbst.** Im
   Neustart-Fall erschien dieselbe Anfrage zweimal am Prüfserver, ohne dass die
   App sie zweimal geschickt hätte. Das ist eine Eigenschaft der
   Java-Umsetzung, keine dieser App — und **schadet nicht**, weil die
   Idempotenz an (Gerät, `client_ref`, `seq`) hängt: Derselbe Punkt zweimal
   geschickt wird beim zweiten Mal ignoriert (Vertrag 2). Genau dafür ist sie
   da. Der Prüffall prüft deshalb, was die App zusichern **muss** — dass
   `seq_from` nie zurückspringt und nie eine Lücke lässt —, und nicht, was die
   Laufzeitumgebung tut. Der Rundlauf belegt es zusätzlich am echten Server:
   dieselbe Kette ein zweites Mal gesendet, **0 × rejected**, nichts doppelt.

2. **Ein Prüffall legte vier Dienste mit derselben Kennung an.** Er baute für
   jeden einen frischen Kennungszähler — der beginnt wieder bei 1 und liefert
   mit demselben Zufallsstartwert dieselbe Zeichenkette. Gefangen hat es die
   `UNIQUE`-Bedingung auf `dienst.dienst_ref`. Das ist genau der Fall, gegen
   den der **Zählerspeicher** in der App steht (Vertrag 8: „Der Zähler
   überlebt Neustarts und Zeitsprünge und ist die eigentliche Zusicherung") —
   der Prüfstand muss ihn also genauso führen wie die App. Behoben; der
   Kommentar an der Stelle nennt den Grund.

3. **Zwei Erwartungen im Prüffall waren falsch gerechnet**, nicht der Code:
   9 505 Punkte zu je 500 sind 19 volle Teilstücke und ein Rest — **20**
   Anfragen, nicht 21.

#### Entscheidungen, die in B4 gefallen sind

- **E-S4-34 — `resus_sessions` geht gar nicht mit, auch nicht als leere
  Liste.** Die Reanimation bleibt bei der Garmin (E-R45-1); das Handy
  dokumentiert sie nicht. Eine leere Liste hieße nach Vertrag 3.1 „es gibt
  keine" — der Server ließe den vorhandenen Stand zwar stehen, meldete es aber
  als `kept_resus`. Gemeldet würde damit etwas, das nie eine Aussage war. Ein
  **fehlender Schlüssel** heißt „dazu sage ich nichts", und nur das ist wahr.
  Belegt: In allen Rundläufen ist `kept_*` leer.
- **E-S4-35 — Die halbierte Chunk-Größe bleibt halbiert.** Nach einem 413
  halbiert die App (500 → 250 → …) und setzt **nicht** wieder hoch — wie die
  Uhr. Sie wieder hochzusetzen hieße, denselben 413 noch einmal zu
  provozieren; die Ersparnis wäre eine Anfrage, der Preis eine verlorene. Die
  Größe lebt deshalb im `Sender`, und der lebt in der `Application` — sie
  überdauert einen Sendelauf, aber nicht den Neustart der App. Das ist die
  richtige Länge: Ein 413 kommt von der Größe des Körpers, und die hängt am
  Server, nicht am Gerät.
- **E-S4-36 — Ein 400-Paket wird markiert, aber nicht gelöscht.** Der Vertrag
  sagt „nicht wiederholen"; er sagt nicht „wegwerfen". Ein gelöschtes Paket
  wäre eine Aufzeichnung, von der niemand mehr weiß, dass sie nicht angekommen
  ist. Es fällt deshalb aus der Warteschlange und aus dem Rückstand — es ist
  kein Rückstand mehr, sondern ein **Befund** —, bleibt aber im Puffer.
  (Was die Oberfläche daraus macht, gehört zu D1.)

### B5 — Phasen und Einsätze am Handy · Android 0.5.0 · erledigt

**Was entstanden ist.** Der Lebenszyklus aus `Model.mc`: Eine Phase 2–9 ohne
laufenden Einsatz startet ihn und schließt das Ruhesegment; der Abschluss ist
ein eigener Bedienschritt mit Rückfrage; danach beginnt nahtlos das nächste
Segment. Dazu die Phasen-Koordinaten aus der eigenen Spur, die
`mission`-Uploads mit Teil-Uploads, und die Oberfläche: ein großer Knopf für
die nächste Phase, darunter die Liste mit Direktwahl.

**Damit ist Block B abgeschlossen.**

#### Prüfstand B5

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew build` | **BUILD SUCCESSFUL** | fehlerfrei |
| Lint | `lintDebug` | `handy` **0 Fehler, 19 Warnungen** (18 Fassungshinweise + `BatteryLife`); `uhr` **0/0** | 0 Fehler |
| Prüffälle | `testDebugUnitTest` **und** `testReleaseUnitTest` | je **153 Fälle, 0 Fehlschläge, 0 Fehler**; ohne Installation 11 übersprungen, mit ihr **0** | alle grün |
| **Lebenszyklus-Matrix** | Robolectric mit echtem SQLite | **18 Fälle** — siehe unten | E-S4-08 |
| — Phase startet den Einsatz | | Phase 2 ohne Einsatz legt `am-`-Paket an, Phase steht drin | belegt |
| — Segment schließt nahtlos | | **Segmentende = Einsatzbeginn**, zeichengenau derselbe Zeitstempel; kein zweites Segment offen | kein Loch, kein Überlappen |
| — zweiter Phasendruck | | startet **keinen** zweiten Einsatz | belegt |
| — Phasen außerhalb 2–9 | 0, 1, 10, −3, 99 | **alle fünf abgewiesen**, kein Einsatz entsteht | Vertrag 7 |
| — Durchlauf | | 2 → 3 → … → 9, danach `naechstePhase()` = **null** (dort steht der Abschluss) | E-S4-21b |
| — **doppelte Einträge** | Phase 4 zweimal gesetzt | **zwei Einträge, beide Zeiten erhalten** | E-R45-12 |
| — `ended_at` | Phase 9 um 9:12, Abschluss um 9:17 | **9:12:40** — die Zeit der letzten Phase 9, nicht die des Knopfdrucks | E-S4-08 |
| — ohne Phase 9 | | Abschlusszeitpunkt als Rückfall | belegt |
| — nach dem Abschluss | | neues Segment, **Einsatzende = Segmentbeginn** | nahtlos |
| — Kennzahlen | | beim Abschluss **eingefroren** (62 335 m im Rundlauf), für das neue Segment auf 0 | belegt |
| — Dienstende als Sicherheitsnetz | offener Einsatz | wird **mitgeschlossen**, nichts bleibt offen | `Model.endService()` |
| — Nur-Aufzeichnen | 12-h-Strom | **0 Einsätze, 1 Ruhesegment** | E-S4-20 |
| — Moduswechsel | mitten im Dienst | schließt **nichts** von selbst; erst die Phase schließt das Segment; kein Punkt verloren | E-S4-20 |
| Phasen-Koordinate | Robolectric | aus der eigenen Spur; **null**, wenn nichts in ± 30 s liegt; darf aus dem **Ruhesegment** stammen | E-S4-10 |
| **Rundlauf `mission`** | echtes `ingest.php` | Dienst mit **3 Einsätzen und 4 Segmenten**, 2 707 Punkte, **10 Anfragen**, `rejected` **{}**, `kept_*` **{}**, 400 **0**, alle Pakete fertig, Rückstand **0** | 0/0/0 |
| — am Server nachgesehen | SQL | 8 Einsätze, **alle mit `final` und `ended_at`**, je 8 Phasen, `distance_m` 62 335 | belegt |
| **Rundlauf doppelte Phasen** | echtes `ingest.php` | Phase 4 dreimal gesetzt → am Server **`SELECT … HAVING COUNT(*) > 1` liefert `phase 4: 3 Einträge`**; `kept_phases` leer | E-R45-12 |
| APK | `assembleRelease` | `handy` **9 071 984 B**, `uhr` 18 005 460 B | baut |

**Was der Prüfstand nicht sagt:** unverändert alles aus B3 und B4 — kein
Emulator, kein GPS, kein Mobilfunk, kein Bildschirmfoto, kein Akkuverhalten.
Die Oberfläche des Phasenteils ist gebaut und **nicht gesehen worden**.

#### Probleme und wie sie gelöst wurden

1. **Ein echter Fehler in der Phasen-Koordinate, gefangen von einem
   Prüffall — und die Ursache ist eine Falle, die jedem SQLite-Zugriff
   droht.** Die Abfrage lautete

   ```sql
   WHERE ABS(p.zeit - ?) <= ?          -- Werte als Text gebunden
   ```

   `rawQuery` bindet **jeden** Wert als TEXT. Vergleicht SQLite eine **Spalte**
   mit Zahlen-Affinität gegen einen Text, wandelt es den Text vorher in eine
   Zahl um — deshalb tut `p.zeit >= '1784279400'` das Erwartete, und deshalb
   sind alle übrigen Abfragen des Puffers in Ordnung. Ein **Ausdruck** wie
   `ABS(p.zeit - ?)` hat aber **keine Affinität**, und dann gilt SQLites
   Typordnung: **Text ist immer größer als eine Zahl.** `ABS(…) <= '30'` war
   damit *immer wahr*.

   **Folge:** Jede Phase hätte die Koordinate des nächstbesten Punktes
   bekommen — auch die einer Stunde später. Auf dem Gerät wäre das nie
   aufgefallen: Es gibt immer einen Punkt, die Koordinate sähe plausibel aus,
   und falsch wäre sie nur um Kilometer. Behoben, indem an der **Spalte**
   gefiltert wird (`p.zeit >= ? AND p.zeit <= ?`) und die Zahl fürs Sortieren
   ausdrücklich mit `CAST(? AS INTEGER)` kommt. Der Kommentar an der Stelle
   nennt den Grund, weil die Falle bei der nächsten Abfrage wieder droht.

#### Entscheidungen, die in B5 gefallen sind

- **E-S4-37 — Es gibt kein „Einsatz beginnen".** Der Einsatz beginnt mit der
  ersten Phase, und der große Knopf trägt ohne laufenden Einsatz die Phase 2
  („2 · Alarmierung"). Ein eigener Startknopf wäre ein Bedienschritt mehr in
  dem Augenblick, in dem am wenigsten Zeit dafür ist — und er wäre der
  Schritt, den man vergisst. Dieselbe Entscheidung wie an der Garmin.
- **E-S4-38 — Die Ausdünnung läuft über die Paketgrenze hinweg weiter, die
  Kennzahlen nicht.** Beim Übergang Ruhesegment → Einsatz wird der Ausdünner
  **nicht** zurückgesetzt: Sonst entstünde direkt nach dem Schnitt ein zweiter
  Punkt am selben Ort. Strecke und Anstieg beginnen dagegen neu — sie gehören
  dem Einsatz, die Spur gehört dem Dienst.
- **E-S4-39 — Die Phasenliste zeigt alle Zeiten einer Phase, nicht die
  letzte.** Eine erneut gesetzte Phase ist eine Korrektur und damit eine
  Information (E-R45-12). Eine Anzeige, die nur die letzte zeigt, verschweigt
  genau das — und die NutzerIn hätte keinen Anhaltspunkt, dass sie zweimal
  gedrückt hat.

### C1 — Uhr: Gerüst und Bedienbild · Android 0.6.0 · erledigt

**Was entstanden ist.** Das Bedienmodell der Uhr nach E-S4-21, vollständig
und **als Zustandsmaschine ohne Oberfläche** — dazu die Ansichten darauf:
Startseite mit Bildmarke, Durchlaufknopf mit Abschluss-Rückfrage, Phasenliste
als Übersicht und Direktwahl, die Sperre, die Abfrage nach einer freien
Zusatztaste. Im Nur-Aufzeichnen-Modus zeigt die Uhr nur Dienst
beginnen/beenden.

**Warum die Zustandsmaschine.** Die Uhr ist blind gebaut: kein Emulator
(E-R45-8), keine Uhr (E-R45-7). Wäre das Bedienmodell in Composables
verteilt, wäre es **ungeprüft** — und die Abnahme verlangt Bedienzustände als
Prüffälle. Es ist deshalb eine reine Funktion (Zustand + Ereignis → Zustand +
Wirkungen); an der Oberfläche bleibt Zeichnen.

#### Prüfstand C1

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew build` — **beide Module in einem Lauf** | **BUILD SUCCESSFUL** | Modul baut mit |
| Lint `uhr` | `lintDebug` | **0 Fehler, 0 Warnungen** | 0 Fehler |
| Lint `handy` | `lintDebug` | **0 Fehler, 19 Warnungen** (18 Fassungshinweise + `BatteryLife`) | 0 Fehler |
| Prüffälle `uhr` | `testDebugUnitTest` | **21 Fälle, 0 Fehlschläge, 0 Fehler** | belegt |
| Prüffälle gesamt | beide Module | **174 Fälle** (153 Handy + 21 Uhr) | — |
| **(b) Durchlauf 2 → 9** | Zustandsmaschine | acht Drücke ergeben genau `PhaseSetzen(2)` … `PhaseSetzen(9)`; danach `naechstePhase` = **null** | E-S4-21b |
| **(b) Abschluss nur nach Rückfrage** | | erster Druck erzeugt die Frage und **keine Wirkung**; erst `Bestaetigt` liefert `EinsatzAbschliessen`; `Verworfen` führt zurück, der Einsatz läuft weiter | E-S4-21b |
| **(c) Direktwahl** | | `ListenwahL(3)` bei laufender Phase 7 → `PhaseSetzen(3)`, Liste schließt | E-S4-21c |
| **(c) Korrektur = zweiter Eintrag** | dreimal Phase 4 gewählt | **dreimal `PhaseSetzen(4)`** — nichts wird entdoppelt | E-R45-12 |
| **(c) Halten öffnet die Liste** | | `GrosserKnopfGehalten` → `PHASENLISTE`, keine Wirkung | E-S4-21c |
| **(d) Sperre greift nach Frist** | 10 000 ms | bei 9 999 ms **nicht** gesperrt, bei 10 000 ms gesperrt | E-S4-21d |
| **(d) gesperrtes Tippen tut nichts** | fünf Ereignisarten | **alle fünf**: keine Wirkung, Zustand unverändert | E-S4-21d |
| **(d) Entsperren nur durch Halten** | | 999 ms bleibt gesperrt, 1 000 ms entsperrt, sonst keine Wirkung | E-S4-21d |
| **(d) Sperre gilt auch für die Taste** | | gesperrte freie Taste wirkt nicht | E-S4-21d |
| **(d) abschaltbar** | `sperreAn = false` | sperrt nie | E-S4-21d |
| **(d) Startseite sperrt nicht** | ohne Dienst | sperrt nie — vor dem Dienst gibt es nichts zu verstellen | begründet |
| **(a) mit Taste** | | `FreieTaste` erzeugt **dieselbe Wirkung und denselben Zustand** wie der große Knopf | E-S4-21a |
| **(a) ohne Taste** | | ganzer Weg — Phase, Liste, Direktwahl, Abschluss — allein über Touch-Ereignisse durchgespielt | E-S4-21a |
| Nur-Aufzeichnen | | Phasendruck und freie Taste wirken **nicht**, die Liste öffnet nicht; Dienst beenden geht weiter | E-S4-20 |
| Dienst | | Beginnen ohne Rückfrage, **Beenden mit** | beendende Handlung |
| APK `uhr` | `assembleRelease` | **18 095 596 B**, unsigniert | baut |

**Bildschirmfotos gab es zu C1 nicht** — die Annahme E-R45-8 ist mit 0.7.2
widerlegt (E-S4-49); zum Zeitpunkt von C1 galt sie noch. Das
steht hier und wird im Prüfdokument wiederholt, statt verschwiegen zu werden.
Ungeprüft bleiben damit: die Rundung des Displays, Schriftgrößen,
Berührziele, ob die Bildmarke in der gewählten Größe trägt, ob `WearableButtons`
auf dem Gerät etwas meldet — und ob Haltedauer und Sperrfrist im Einsatz die
richtigen sind.

#### Probleme und wie sie gelöst wurden

1. **Die `--`-Regel aus B1 hat wieder zugeschlagen**, diesmal an `--sand` in
   einem Kommentar der neuen Vektordatei. Der Baulauf bricht dabei im
   Ressourcenschritt ab, nicht beim Übersetzen — die Meldung nennt Zeile und
   Spalte, aber nicht die Regel. Behoben mit demselben Fixer wie in B1. Die
   Regel steht im Konzept (B1, Punkt 3) und offenbar zu Recht.

2. **Das Schloss war ein Emoji.** Ob eine Uhr 🔒 in ihrer Systemschrift führt,
   weiß niemand — und ein fehlendes Zeichen wird zum leeren Kasten, ausgerechnet
   an der Stelle, die sagen soll „hier tut ein Tippen nichts". Ersetzt durch
   eine Vektordatei nach der Form des Mockups.

#### Entscheidungen, die in C1 gefallen sind

- **E-S4-40 — Das Bedienmodell der Uhr ist eine Zustandsmaschine ohne
  Oberfläche.** Zustand und Ereignis hinein, Zustand und **Wirkungen** heraus;
  die Wirkungen (`PhaseSetzen`, `EinsatzAbschliessen`, `DienstBeginnen`,
  `DienstBeenden`) sind das, was C2 über den Data Layer schickt. Das ist die
  Antwort auf „blind gebaut": Was ohne Gerät prüfbar sein soll, darf nicht in
  Composables wohnen. 21 Prüffälle belegen das Bedienbild, bevor es je jemand
  gesehen hat.
- **E-S4-41 — An der Uhr ist die Bedienhöhe 48 dp, am Handy bleibt sie 44.**
  Das ist kein Widerspruch zu `CLAUDE.md` 5, sondern dessen Zweck: Die 44 px
  sind eine Untergrenze für Maus und Finger am Schreibtisch. An der Uhr trifft
  ein Finger im Einsatz ein rundes Display, oft mit Handschuh — Androids
  Empfehlung von 48 dp ist dort die kleinere Zumutung. Am Handy bleibt der
  Unterschied als Fund **B-S4-02** offen und wird dort entschieden, nicht hier
  nebenbei.
- **E-S4-42 — Halten hat zwei Bedeutungen, und der Zustand entscheidet.**
  Entsperrt öffnet ein Halten die Phasenliste (E-S4-21c), gesperrt entsperrt
  es (E-S4-21d). Zwei Griffe für zwei Zwecke wären an einem Handgelenk einer
  zu viel; dass derselbe Griff im gesperrten Zustand etwas anderes tut, ist
  die einzige Stelle, an der die Sperre überhaupt bedienbar bleibt.

### C2 — Nachrichtenweg mit Quittung und Puffer · Android 0.7.0 · erledigt

**Was entstanden ist.** Das Protokoll nach E-S4-10 auf beiden Seiten. Die Uhr
puffert jedes Bedienereignis, bevor sie es sendet, und liefert unquittierte
Nachrichten **wortgleich** nach; das Handy übernimmt sie, quittiert mit der
höchsten **lückenlos** übernommenen Nummer, ergänzt die Phasen-Koordinate aus
der eigenen Spur und schickt seinen Anzeigestand zurück. Der Data Layer selbst
ist eine Hülle von rund fünfzig Zeilen ohne eine einzige Entscheidung.

**Wo die Naht liegt — und warum sie dort liegt.** Alles, was schiefgehen
kann, liegt **über** der Hülle: Puffer, Nummernvergabe, Quittung,
Nachlieferung, Doppelzustellung, die Buchführung am Handy. Das läuft auf der
JVM und ist geprüft. Die Hülle selbst ist im Container nicht ausführbar — der
Data Layer braucht zwei gekoppelte Geräte, und es gibt weder Uhr (E-R45-7)
noch Emulator (E-R45-8). Je weniger in ihr steht, desto kleiner ist der
ungeprüfte Rest; deshalb: Knoten holen, senden, Erfolg melden, sonst nichts.

**Das Nachrichtenformat liegt in `gemeinsam/`.** Ein Format, das zwei
Programme unabhängig voneinander lesen und schreiben, ist zwei Formate,
sobald sich eines ändert — und der Data Layer meldet keine unverstandene
Nachricht, er stellt sie zu und niemand tut etwas damit. Mit ihm sind
`Kennungen` (die `wm-`-Bauform entsteht jetzt auf der Uhr) und `Modus` (er
reist zur Uhr) nach `gemeinsam/` gewandert.

#### Prüfstand C2

| Prüfung | Mittel | Ist | Soll |
|---|---|---|---|
| Baulauf | `./gradlew build`, beide Module | **BUILD SUCCESSFUL** | baut |
| Lint `uhr` | `lintDebug` | **0 Fehler, 0 Warnungen** | 0 Fehler |
| Lint `handy` | `lintDebug` | **0 Fehler, 19 Warnungen** (18 Fassungshinweise + `BatteryLife`; **unverändert** — die neue Bibliothek bringt keine) | 0 Fehler |
| Prüffälle `uhr` | `testDebugUnitTest` | **47 Fälle, 0 Fehlschläge, 0 übersprungen** (21 aus C1 + 26 neue) | belegt |
| Prüffälle `handy` | ohne Rundlauf | **167 Fälle, 0 Fehlschläge, 11 übersprungen** | belegt |
| Prüffälle `handy` | **mit** lokaler Installation | **167 Fälle, 0 Fehlschläge, 0 übersprungen** | belegt |
| Prüffälle gesamt | beide Module | **214** (167 + 47) | — |
| **Funkabriss mit Nachlieferung** | Transport-Attrappe | zwei Ereignisse im Funkloch: **0 Bytes auf der Leitung**, beide gepuffert, `handyErreichbar = false`; nach Rückkehr **2 zugestellt**, Reihenfolge 1, 2 | E-S4-10 |
| … identisch nachgeliefert | Byte-Vergleich | die nachgelieferte Nachricht ist **zeichengleich** mit der ersten | E-S4-10 |
| … Abriss mittendrin | Attrappe stellt genau 1 zu | **1 zugestellt, 3 bleiben im Puffer**, der Dienststart war der erste | Reihenfolge |
| **Doppelzustellung nach verlorener Quittung** | Uhr-Seite | ohne Quittung wird dieselbe Nachricht mit **derselben Nummer** erneut gesendet | E-S4-10 |
| … kein zweiter Einsatz | Handy-Seite, echtes SQLite | zweimal dieselbe Meldung → **1 Einsatz, 1 Phase**, beide Male dieselbe Quittung | E-S4-10 |
| … der `wm-`-Anker greift auch ohne Buchführung | neue Nummer, gleiche Kennung | **1 Einsatz, 2 Phaseneinträge** (die zweite Nummer war neu — Korrektur, E-R45-12) | E-S4-09 |
| … auch bei abgeschlossenem Einsatz | Nachzügler nach `EINSATZ_ABSCHLIESSEN` | **kein zweiter Einsatz**; die Phase landet im selben, Reihenfolge 2, 9 | E-S4-09 |
| **Uhr-Neustart mit gefülltem Puffer** | neues Exemplar über derselben Ablage | **2 Nachrichten unverändert da**, gleiche Uhr-Kennung, nächste Nummer ist **3** und nicht 1 | E-S4-10 |
| **Phasenkonflikt Uhr/Handy** | Handy 05:10:00, Uhr 05:10:30 | **2 Einträge**, Quellen `handy` und `uhr`, **beide Zeiten erhalten** | E-R45-12 |
| … und beide werden **gesendet** | `hatArbeit` vor/nach | **false → true**: der bestätigte Einsatz wird wieder sendepflichtig (Fund B-S4-05) | Abnahme |
| … am Server angekommen | Rundlauf gegen `ingest.php` | **3 Phasenzeilen** in `mission_phases` (2 · 05:02:00 Uhr, 3 · 05:07:00 Handy, 3 · 05:07:30 Uhr), `verworfen={}`, `übergangen={}` | Vertrag 3 |
| **Dienststart ohne erreichbares Handy** | Uhr-Seite | `dienstLaeuft = true`, `dienstBestaetigt = **false**`, `dienstSchwebt = true`, `gepuffert = 1`; die Uhr zeigt „wartet aufs Handy · keine Aufzeichnung" | E-S4-10 |
| … Zustellung allein genügt nicht | nach `nachliefern()` | schwebt **weiter** — erst die Quittung (oder eine Standmeldung) beendet es | E-S4-10 |
| Lücke in der Nummernreihe | 1, dann 3, dann 2 | Quittung bleibt bei **1**, springt mit der 2 auf **3**; Einzelbuchung danach leer | E-S4-10 |
| Zurückgesetzte Uhr | neue Uhr-Kennung, Nummer 1 | wird **übernommen**, nicht als Doppelzustellung verworfen; zwei Stände nebeneinander | begründet |
| Zeitmaß | | jede Meldung trägt die **Uhrzeit der Uhr**; `started_at` des Dienstes ist die Auslösezeit (05:00) und nicht die Ankunft (05:20) | E-R45-1 |
| Keine Zugangsdaten im Format | Schlüssel gezählt | **genau** `uhr, nr, art, zeit, phase, einsatz_ref` — kein `api_key`, keine `device_id`, keine Serveradresse | E-S4-11 |
| Unbrauchbare Nachricht | vier Formen | **null** statt Ausnahme (ein Absturz beendete den Systemdienst) | robust |
| Schema-Migration 1 → 2 | Datenbank der alten Fassung von Hand angelegt | Dienst, Paket und **beide Punkte** überstehen sie; die neuen Tabellen sind benutzbar | kein Datenverlust |
| Farb-, Kontrast-, Bildmarken- und Stromprüfung | `werkzeuge/*` | 0 / 0 / 0 / 0 Abweichungen (16 Farbpaare, 4 Bildmarken, 5 Ströme) | unverändert |
| Wortliste | `tools/wortliste` | 0 Treffer — **misst `android/` aber nicht** (Fund B-S4-06); von Hand nachgezählt: 123 sichtbare Werte, 3 Treffer, alle aus B2/C1 und derselben Homonym-Klasse wie im Web | siehe Fund |
| APK | `assembleRelease`, unsigniert | Handy **9 598 911 B**, Uhr **19 491 794 B** | baut |

**Was nicht geprüft ist — und das steht hier, nicht in einer Fußnote:**

- **Der echte Data Layer.** Ob `WearNachrichtenweg` überhaupt zustellt, ob die
  beiden `WearableListenerService` gerufen werden, ob die Play-Dienste auf der
  Uhr vorhanden sind, ob Paket- und Signaturgleichheit im Feld greift
  (E-S4-01) — nichts davon ist ausführbar. Gerätetest.
- **Die Zeit im Funkloch.** Wie lange `Tasks.await` bei abgeschalteter Uhr
  wirklich hängt, ob fünf Sekunden zu lang oder zu kurz sind, ob die Uhr
  dabei spürbar hakt.
- **Der Nebenlauf.** `UhrApp` fährt einen einzigen Arbeitsfaden, damit
  Nummernvergabe und Dateischreiben sich nicht in die Quere kommen. Dass das
  auf dem Gerät reicht — und dass die Anzeige trotzdem flüssig bleibt —
  zeigt erst die Uhr.
- **Der Vordergrunddienst, den die Uhr auslöst.** `HandyHorcher` startet die
  Aufzeichnung, wenn der Dienst an der Uhr beginnt. Ob Android das aus einem
  vom System gestarteten Dienst heraus zulässt (Hintergrundstart-Regeln ab
  Android 12), ist eine Gerätefrage.
- **Bildschirmfotos gab es zu C2 nicht** (E-R45-8, mit 0.7.2 widerlegt —
  E-S4-49). Die schwebende Zeile ist weiterhin ungesehen: Sie erscheint erst
  bei unquittiertem Dienststart, und den stellt bislang kein Bildfall her.

#### Probleme und wie sie gelöst wurden

1. **Die Abnahme verlangte mehr als das Speichern.** „Phasenkonflikt
   Uhr/Handy (beide Einträge gesendet)" — beim Nachbauen zeigte sich, dass ein
   Einsatz, dessen Metadaten der Server schon bestätigt hatte, als erledigt
   galt: `hatArbeit()` zählt Punkte, nicht Phasen. Eine nachträgliche Phase
   wäre liegengeblieben. Behoben in `phaseAnhaengen` (Fund **B-S4-05**), belegt
   im Prüfstand und im Rundlauf.

2. **Die Buchführung durfte nicht bei der Nummer allein bleiben.** Der erste
   Entwurf führte nur die höchste übernommene Nummer. Zwei Fälle brachen ihn:
   eine **Lücke** in der Reihe (Quittung „bis 6" hieße für die Uhr, dass sie
   die fehlende 5 löschen darf) und eine **zurückgesetzte Uhr** (ihr Zähler
   beginnt wieder bei 1, und jedes Ereignis verschwände als vermeintliche
   Doppelzustellung — unbemerkt, weil alles weiter funktioniert). Beides ist
   jetzt im Schema abgebildet: Stand **je Uhr**, dazu eine Tabelle der
   vereinzelten Nummern oberhalb davon.

3. **Damit war eine Schema-Migration fällig** — die erste. `onUpgrade` warf
   bislang eine Ausnahme, und das war richtig so: Im Puffer liegt die einzige
   Kopie ungesendeter Aufzeichnungen. Jetzt steht dort ein Schritt 1 → 2, der
   nur anlegt und nichts anfasst; ein eigener Prüffall legt eine Datenbank der
   **alten** Fassung an und weist nach, dass Dienst, Paket und Punkte sie
   überstehen. Für alles andere wirft `onUpgrade` weiter.

4. **Ein Prüffall hat einen Prüffall gefunden.** Der Rundlauf verglich die
   Phasenquellen **nach** dem Senden — und fand nichts, weil ein vollständig
   bestätigtes Paket entsorgt wird. Das war kein Fehler im Code, sondern eine
   falsche Erwartung; sie ist umgestellt (vorher lesen) und die leere Abfrage
   danach ist jetzt selbst eine Zusicherung: Der Einsatz ist weg, also ist er
   angekommen.

5. **`Kennungen` musste umziehen.** Die `wm-`-Kennung entsteht laut E-S4-09
   auf der Uhr; die Klasse lag im Handy-Modul. Zwei Umsetzungen desselben
   Idempotenz-Ankers wären genau die Art Fehler, die man erst am Datensatz
   bemerkt. Sie liegt jetzt in `gemeinsam/`, wie `Modus` seit C1.

#### Entscheidungen, die in C2 gefallen sind

- **E-S4-43 — Die Uhr merkt vor dem Senden, und gelöscht wird erst nach der
  Quittung.** Umgekehrt — senden, und bei Misserfolg merken — verlöre genau
  die Ereignisse, bei denen das Senden nicht sauber scheitert, sondern hängen
  bleibt. Gespeichert werden die **fertigen Bytes**, nicht die Absicht
  dahinter: „identisch nachliefern" ist damit keine Zusage, die man einhalten
  muss, sondern eine, die man nicht brechen kann.
- **E-S4-44 — Quittiert wird die höchste *lückenlose* Nummer, und die
  Buchführung läuft je Uhr.** Beides ist begründet in „Probleme", Punkt 2. Die
  Uhr-Kennung entsteht bei der ersten Benutzung und lebt in derselben Datei
  wie der Puffer; sie ist kein Geheimnis und identifiziert kein Gerät, sondern
  **eine Einrichtung der App auf einer Uhr**.
- **E-S4-45 — Zwei Böden gegen den zweiten Einsatz.** Die Nummer fängt die
  Doppelzustellung ab; die `wm-`-Kennung fängt sie auch dann noch ab, wenn die
  Buchführung fehlt — nach einer Neueinrichtung der Handy-App etwa. Der
  Vertrag setzt beim Server dieselbe Art doppelten Bodens (Idempotenz über
  `client_ref`), und aus demselben Grund.
- **E-S4-46 — Ein Ereignis, das ins Leere läuft, gilt trotzdem als
  übernommen.** Eine Phase ohne laufenden Dienst bewirkt nichts — würde sie
  nicht quittiert, lieferte die Uhr sie für immer nach und käme nie weiter.
  Der Preis ist ein stillschweigend verworfenes Ereignis; der Fall entsteht
  nur, wenn Uhr und Handy über den Dienstzustand auseinanderlaufen, und dagegen
  steht die Standmeldung.
- **E-S4-47 — Der schwebende Dienststart wird angezeigt, nicht geglättet.**
  Solange das Handy nicht quittiert hat, läuft dort kein GPS. Die Uhr sagt
  „wartet aufs Handy · keine Aufzeichnung" statt „Dienst läuft" — eine
  Aufzeichnungslücke, die niemand bemerkt, ist die teuerste Art, freundlich
  zu sein.
- **E-S4-48 — Der Anzeigestand kommt vom Handy und führt.** Die Uhr besitzt
  den Zustand nicht. Nach einem Neustart ihrer App ist ihre Anzeige leer und
  der Dienst läuft weiter; die Standmeldung stellt sie wieder her. Nicht
  überschrieben werden die Sperre und eine offene Rückfrage — eine
  Standmeldung, die mitten in der Abschlussfrage umschaltet, beantwortete sie
  für den Menschen davor.

### Nachtrag zu C2 · Android 0.7.1

**Kein neues Arbeitspaket, eine Korrektur.** Behoben ist Fund **B-S4-07** (die
Kopplungsseite verlangte die Server-Adresse, die der QR-Code mitbringt).
Geändert: `KopplungAnsicht.kt`, `strings.xml`, `version.properties`.

| Prüfung | Mittel | Ist |
|---|---|---|
| Baulauf | `:handy:assembleDebug` | **BUILD SUCCESSFUL** |
| Lint `handy` | `:handy:lintDebug` | **0 Fehler, 19 Warnungen** — unverändert |
| Sichtbare Texte | eigene Zählung gegen `sperrliste.json` | 123 Werte, 3 Treffer, alle aus B2/C1 (Fund B-S4-06) |

**Noch nicht geprüft:** ein Bildschirmfoto der geänderten Seite. Die
Bildprüfung ist noch nicht eingerichtet — die Wege dorthin sind erprobt, aber
die Gegenprobe läuft noch (siehe unten). Bis dahin ist die Änderung *gelesen*,
nicht *gesehen*.

**Offen aus derselben Rückfrage:** **F-S4-D** — Garmin und Handy gleichzeitig
erzeugen zwei Diensttage. Gemessen, in Abschnitt 4 beschrieben, zu entscheiden
vom Auftraggeber. In B/C ist dazu nichts umgesetzt.

### Nachtrag zu C2 · Android 0.7.2 — das Bildmittel und was es sofort fand

**E-S4-49 — Bilder entstehen mit Robolectric im NATIVE-Grafikmodus; der
Emulator kommt dazu, wenn das runde Glas zur Frage steht.**

Die Annahme E-R45-8 („kein Emulator im Container") ist **widerlegt**. Fünf
Wege wurden erprobt, vier funktionieren, jeder mit eigener Gegenprobe:

| Weg | Übernahme in den Hauptbaum | Rundmaske |
|---|---|---|
| **Robolectric direkt** *(gewählt)* | **1 Datei, 0 Abhängigkeiten** | nein |
| **Emulator ohne KVM** *(gewählt, bei Bedarf)* | nichts am Projekt | ja, echt |
| Roborazzi 1.60.0 | 3 Katalogzeilen + 2 je Modul | ja |
| Paparazzi 2.0.0-alpha05 | 5 Stücke, Metadatenprüfung aus | ja |

**Warum diese zwei.** Der Direktweg kostet keine einzige neue Abhängigkeit
(CLAUDE.md 4) und läuft in Sekunden bei jedem Prüflauf mit — er hätte B-S4-08
gefunden, und er hat es. Der Emulator kostet am Projekt gar nichts und liefert
das runde Glas echt. Die beiden anderen bringen Fassungsrisiken für einen
Ertrag, den diese zwei schon liefern: Roborazzi ist ab 1.61.0 mit Kotlin
2.1.21 unbaubar, Paparazzi ist eine Alpha und verlangt
`-Xskip-metadata-version-check`, also das Abschalten einer Sicherung.

**Was der Emulator kostet** (gemessen): Boot 197–345 s, Installation eines
26-MB-APK 207 s, Kaltstart der App 17 s. `-no-window` ist **Pflicht**, nicht
Bequemlichkeit — die GUI-Binärdatei scheitert an fehlendem `libpulse.so.0`,
erst mit `-no-window` greift die `-headless`-Variante. Und
`sys.boot_completed=1` ist **kein** Bereitschaftssignal: Der erste Abzug ist
schwarz, weil SystemUI im ANR steht.

#### Prüfstand 0.7.2

| Prüfung | Mittel | Ist |
|---|---|---|
| Baulauf | `./gradlew build` | **BUILD SUCCESSFUL** |
| Prüffälle `handy` | | **167**, 0 Fehlschläge, 12 übersprungen (Rundlauf) |
| Prüffälle `uhr` | | **50**, 0 Fehlschläge (47 + 3 Bildfälle) |
| Prüffälle gesamt | | **217** |
| Lint | beide Module | **0 Fehler**; 19 Warnungen (Handy), 0 (Uhr) |
| **Bedienhöhe 192 dp** | `UhrBildTest` | **35,5 → 48,0 dp** (B-S4-08 behoben) |
| **Bedienhöhe 227 dp** | | 48,0 → **48,0 dp** (unverändert richtig) |
| Bildinhalt | eigene Zählung im Prüffall | 984–1 083 Farben, 19,3–24,0 % nicht-Grundfarbe |

**Was die Bilder nicht zeigen** — und das gehört dazu, weil es die Grenze des
neuen Mittels ist: Der Direktweg malt ein **Quadrat**; die runde Maske legt
das Gerät an. Beschnitt am Glasrand sieht er nicht. Er zeichnet mit
Robolectrics Schriften, nicht mit denen von Wear OS, und ohne
Hardwarebeschleunigung — Schatten und Elevation können fehlen. Und er bildet
**einen** Android-Stand ab (sdk=34), während die App auf 30 bis 36 läuft.

**Zwei Befunde stehen offen:** der Beschnitt des Knopfes am runden Glas
(im Emulator und in Paparazzi sichtbar, im Direktweg nicht) und die
abgeschnittene Zeile „löst am Handy aus". Beide sind Symptome derselben
Sache: Die Startseite ist auf 192 dp zu voll. Seit 0.7.2 wird nichts mehr
still gestaucht, aber der Inhalt braucht jetzt Bildlauf — eine Straffung der
Startseite wäre eine neue Darstellung und bräuchte ein Mockup mit Freigabe
(CLAUDE.md 5).

**Ein Prüfmittel hat sich selbst korrigiert:** Zwei der gemeldeten Bilder
waren **bytegleich** (`uhr-boden-192dp` und `uhr-ohne-sperre-192dp`), weil die
Sperre auf der Startseite gar nicht greift. Der Fall ist ersatzlos gestrichen
— ein Prüffall, der zweimal dasselbe malt, ist kein zweiter Beleg.

### Nachtrag · Android 0.7.3 — die Startseite passt ins Glas

**E-S4-50 — F-S4-D ist entschieden: Zwei Diensttage bleiben.** Laufen Garmin
und Handy gleichzeitig, entstehen zwei Diensttage am selben Kalendertag; jedes
Gerät führt seine eigene `day_ref`, und die Zuordnung ist je Gerät
geschlüsselt. Gemessen gegen die lokale Installation: zwei Zeilen in `days`,
je ein Einsatz, nichts überschrieben.

Das bleibt so, und zwar ohne Eingriff. Die Begründung ist, was die Doppelung
**ist**: zwei getrennte Aufzeichnungen desselben Dienstes, jede vollständig.
Sie automatisch zu verschmelzen hieße zu raten, welche der beiden Spuren die
richtige ist — und die Reanimation liegt ohnehin nur auf der Garmin (E-R45-1).
Wer beide Tage zu einem machen will, hat dafür `dt_zusammenfuehren()` im
Browser mit einer Vorschau; das ist die Stelle, an der ein Mensch entscheidet.
Ein stiller Automatismus wäre hier das Gegenteil von Dokumentation. Zu tun
bleibt ein Satz im Handbuch (Block D).

**E-S4-51 — Auf dem runden Glas entscheidet die Geometrie über die
Reihenfolge, nicht die Wichtigkeit.** Der Kreis ist in der Mitte am
breitesten. Bedienelemente gehören dorthin, Statusanzeigen darum herum — sie
werden gelesen, nicht getroffen. Deshalb steht der große Knopf jetzt **über**
der Verbindungszeile und nicht darunter. Das ist keine Umgewichtung: Der
Verbindungszustand bleibt sichtbar, er braucht nur keine Trefferfläche.

#### Prüfstand 0.7.3

| Prüfung | Mittel | Ist |
|---|---|---|
| Baulauf | `./gradlew build` | **BUILD SUCCESSFUL** |
| Prüffälle `uhr` | | **53**, 0 Fehlschläge (50 + 2 für B-S4-09 + 1 Bildfall) |
| Prüffälle `handy` | | **167**, 0 Fehlschläge |
| Prüffälle gesamt | | **220** |
| Lint | beide Module | **0 Fehler** |
| **Inhalt außerhalb des Glases, Startseite 192 dp** | `UhrBildTest` | **13,55 % → 0,00 %** |
| **Inhalt außerhalb des Glases, Startseite 227 dp** | | **1,66 % → 0,00 %** |
| **Knopffläche außerhalb des Glases**, alle drei Bilder | | **0,00 %** |
| laufende Ansicht, 192 dp, Gesamtinhalt | | 1,74 % (Marke und Text an den Ecken; **kein Knopf**) |
| Bedienhöhe, Startseite beide Größen | | 48,0 dp (unverändert, E-S4-41) |
| Bedienhöhe, laufende Ansicht | | 53,5 dp — „Einsatz abschließen" bricht um, also **über** der Zusage |
| F-S4-D nachgestellt | lokale Installation, `ingest.php` | **2 Diensttage**, je 1 Einsatz, 0 überschrieben |

**Das Mockup liegt bei** — `android/mockups/S4-uhr-startseite.html` samt
Bild, mit **echten** Prüfstandsbildern statt eines Nachbaus, die runde Maske
als CSS darübergelegt. Es liegt unter `android/`, weil `docs/mockups/`
außerhalb des Schreibrahmens dieser Umsetzung liegt; verschieben ist ein
Handgriff, sobald Block D die Dokumentation zusammenführt.

**Was offen bleibt und warum:**

- **B-S4-01** (Logodateien mit alten Farbwerten) — die Quelldateien liegen
  außerhalb von `android/`. Der Abgleich der App gegen ihre Vorlagen meldet
  0 Abweichungen; der Fund betrifft die Vorlagen selbst.
- **B-S4-02** (44 gegen 48 dp am Handy) — **Vorschlag: es bleibt bei 44 px.**
  `CLAUDE.md` 5 nennt eine Höhe für die ganze Anwendung, und die
  Weboberfläche hält sie. Die Uhr ist die begründete Ausnahme (E-S4-41), weil
  dort ein Finger im Einsatz ein rundes Display trifft. Eine zweite Zahl am
  Handy einzuführen bräuchte einen Grund, den es nicht gibt. Zur Bestätigung.
- **B-S4-03** (Uhr-APK 19 MB) — kleiner würde sie nur durch `isMinifyEnabled`,
  und ProGuard gegen Compose ist kein Nebenbei-Schritt. Eigene Runde.
- **B-S4-04** (Akku-Freistellung gegen Play-Store-Richtlinie) — eine
  Betriebsentscheidung, keine Codefrage.
- **B-S4-06** (Wortliste erreicht `android/` nicht) — `tools/` liegt
  außerhalb des Schreibrahmens.
