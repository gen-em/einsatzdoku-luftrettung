# Konzept S4 — Handy- und Uhr-Client (Android/Wear OS), Schneidewerkzeug und GPX-Import (Zwischenpaket)

**Stand:** 31.08.2026 — Erstfassung, zur Freigabe.
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

## 4. Offene Fragen

- **F-S4-A — Mindest-Android-Stand.** Vorschlag: Android 8.0 (`minSdk 26`,
  E-S4-03). Zu bestätigen vom Auftraggeber mit Blick auf die tatsächlichen
  Geräte des Nutzerkreises — ein älteres Gerät im Kreis hieße: Stand senken
  und die Vordergrunddienst-Pfade dafür gesondert prüfen. **Zu entscheiden
  vor B1.**
- **F-S4-B — Feldform des `geraet`-Blocks für Handys.** Das
  R42-Kleinstpaket legt fest, welche Felder `pair.php` speichert (Art,
  Bezeichnung). Die Handy-Kopplung übernimmt diese Form; sollte das
  Kleinstpaket nur die Uhr-Felder (`teil` usw.) annehmen, wird der
  Handy-Zuschnitt (Hersteller + Modell statt Teilenummer, `teil: null`) im
  Vertrag als Nachtrag beschrieben und die Annahme in `pair.php` in A1
  ergänzt. **Klärt sich mit dem Merge des Kleinstpakets; zu entscheiden
  vor B2** (die App sendet die Felder ab der ersten Kopplung — jede
  Kopplung davor ginge der Statistik verloren, R42).
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

## 5. Arbeitspakete

Je Paket ein Commit (K7). Die Blöcke folgen dem Rahmenplan-Schnitt; B und C
fassen ausschließlich `android/` an, A ausschließlich `server/`, `docs/` und
den Vertrag, D die übergreifende Doku.

### Block B — Android-Handy-App (`android/handy/`)

**B1 — Gerüst und Probebau.**
Gradle-Projekt `android/` mit den Modulen `handy/` und `uhr/`, Anwendungs-ID
und Signaturkonzept (E-S4-01), Versionszählung, Lint; `LIESMICH.md` in
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
starten/beenden, Neustart-Wiederaufnahme (App-Absturz und Handy-Neustart
während des Dienstes), Erststart-Führung zur Akku-Freistellung.
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
Teil-Uploads.
*Abnahme:* Lebenszyklus-Matrix (Phase ohne Einsatz startet Einsatz;
Abschluss sendet `final` + `ended_at`; Segment davor/danach nahtlos);
`mission`-Ketten laufen gegen `ingest.php` wie in B4 (0/0/0); doppelte
Phaseneinträge bleiben erhalten (E-R45-12 nachgestellt).

### Block C — Wear-OS-App (`android/uhr/`)

**C1 — Gerüst und Bedienbild.**
Modul `uhr/`, Oberflächen nach E-S4-11 (Dienst, Phasen, Einsatzabschluss,
Verbindungszustand), blind gebaut.
*Abnahme:* Modul baut im selben Gradle-Lauf; die Bedienzustände sind als
Robolectric-Fälle belegt (Zahl im Prüfprotokoll); Bildschirmfotos gibt es
nicht (kein Emulator) — das steht so im Prüfdokument, nicht verschwiegen.

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
Download als neutrale Handlung, nicht als Primärknopf. **Die Freigabe
steht aus.**

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

Noch keine. Funde während der Umsetzung werden hier gesammelt und nicht
nebenbei behoben, außer sie blockieren die laufende Arbeit.

## 11. Statuspflege

Nach jedem Paket: dieses Konzept fortschreiben (erledigt, Probleme,
Entscheidungen, Prüfstand). Nach Phasenende: Prüfdokument
(`docs/Pruefdokument-S4-Handy-Uhr-Client.md`), Statuszeile im Rahmenplan
(Abschnitt 6), Changelog-Einträge mit den Präfixen `Web` (Block A) und
`Android` (Blöcke B/C). Die Entscheidung zu F-S4-A bis F-S4-C wird vor dem
jeweils betroffenen Paket eingeholt und hier als E-Eintrag nachgetragen
(K6).
