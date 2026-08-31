# Konzept S2 — Mengen, Spurspeicherung und Sicherung

Zwischenpaket nach R34, zwischen P3 und P4. Konzeptstand: 30.08.2026,
Übergabefassung an die Umsetzung. Format nach K1; keine Versionsnummern (K3).
**Kein Fable-Schritt** (K2/K8): Die sicherheitsrelevanten Formatentscheidungen
(Containerfassung 4, Spur-Blob-Format, Komplettbackup-Verschlüsselung) sind in
diesem Konzept getroffen und begründet; der R17-Review in P6 prüft sie nach.
Umsetzung durchgehend mit dem Standardmodell.

Die Entscheidungen dieses Konzepts sind im Konzeptgespräch vom 28.–30.08.2026
gefallen (Fragen F1–F21 dort); die Zuordnung Frage → Entscheidung steht in
Abschnitt 2.13.

Ablage im Repositorium: `docs/Konzept-S2-Mengen-Spuren-Sicherung.md` (Muster
E-S1-18); das Prüfdokument nach K9 daneben als
`docs/Pruefdokument-S2-Mengen-Spuren-Sicherung.md`. Die umsetzende Instanz
schreibt dieses Dokument fort — Abschnitt 4 (Fragen der Umsetzung, F-S2-01 ff.),
Abschnitt 8 (Fehlerfunde, F-S2-A ff.) und Abschnitt 10 (Umsetzungsstand).

---

## 0. Auftrag und Zielmaße

Die Anwendung soll große Datenmengen tragen, ohne ihre Zusagen zu verlieren:
Ende-zu-Ende-Verschlüsselung der geschützten Angaben, vollständige GPS-Spuren,
Durchsuchbarkeit, Sicherung und Wiederherstellung durch die Nutzerin selbst,
Betrieb auf geteiltem Webspace und auf fünf Jahre alten Endgeräten.

Zielmaße (Grundlage aller Abnahmen):

- **Z1** 5 000 Einsätze in einem Konto: Suche, Ansichten, Sicherung und
  Wiederherstellung funktionieren auf dem Referenzgerät (Z3).
- **Z2** 500 Konten × 600 Einsätze je Installation; geteilter Webspace,
  **10 GB MySQL-Kontingent**, PHP-Weblimits (`post_max_size`,
  `max_execution_time`) gelten.
- **Z3** Referenzgerät: fünf Jahre alte Handys und Laptops, nachgestellt durch
  CPU-Drossel (Faktor 6) im Messstand. Budget je Browserschritt: ≤ 10 MB
  JSON-Zeichenkette, ≤ 100 MB Heap-Spitze, höchstens eine PBKDF2-Ableitung je
  Vorgang. Je Serveranfrage: POST ≤ 2 MB, Laufzeit ≤ 30 s, PHP-Speicherspitze
  ≤ 64 MB.
- **Z4** GPS-Spuren bleiben vollständig erhalten; die Ausdünnung nach E-S2-05
  ist eine entschiedene Präzisionsgrenze (2 m / 3 m), kein Verzicht.
- **Z5** Das E2E-Versprechen bleibt unverändert: Klartext der geschützten
  Angaben verlässt den Browser nicht; der Server sieht weiterhin nur
  `pat_blob`-Chiffretext.
- **Z6** R11 bleibt erfüllt: Die neue Wiederherstellung liest die **einteilige
  `.edbak` der 7.x/8.x-Linie** weiter ein (Abnahmedatei
  `tools/referenzdatensatz/referenz/`).

**Nicht Umfang von S2:** die Ablage der `pat_blob` je Einsatz (bleibt, E-S2-02)
· die Ansichten (laden bereits nur, was sie brauchen, B-S2-02) · der
JSON-Vertrag der Uhr (bleibt formal unverändert, E-S2-08) · Registrierung,
Rollen, Mengenbremse `ingest.php` (P5, R9/R10/R19) · automatische
Serversicherungen je Konto (entschieden: nein; Konto-Schlüsselpaar als
Backlog, E-S2-19).

---

## 1. Befund

**Methodik.** Referenzdatensatz vermessen (87 Einsätze, 100 Ruhesegmente,
55 861 Spurpunkte, `server/demo/fixture.json.gz`); Datenbankgrößen mit
MariaDB 10.11 und den 12-fach vervielfältigten echten Referenzspuren gemessen
(670 332 Zeilen ≈ 1 044 Einsätze); WebCrypto- und JSON-Messungen mit
Node-WebCrypto auf Server-CPU (fünf Jahre alte Geräte: erfahrungsgemäß Faktor
5–10 langsamer); Douglas-Peucker in Python über alle Referenzspuren.
**Vorbehalt:** Die Referenzspuren sind synthetisch und glatt; echte Uhrspuren
rauschen, die Ausdünnung behält dann mehr Punkte. Der Messstand (AP0) misst
deshalb am Ende gegen echte Gerätespuren nach, sobald welche vorliegen.

- **B-S2-01 — Mengenverteilung.** 93 % des Bestands sind Spurpunkte
  (42 B/Punkt als JSON, 62 B/Zeile in InnoDB). Ohne Spuren wiegt ein Einsatz
  ~1,7 KB (Stammdaten, Phasen, Rea, Besatzung, `pat_blob`). Median 443
  Punkte je Einsatz (0–1 133), dazu Ruhesegmente ~865 Punkte je Diensttag.
- **B-S2-02 — Zeilenkosten und Kontingent.** `track_points` kostet gemessen
  **40 MB je 1 000 Einsätze** (62 B/Zeile; Datei auf Platte 48 MB). 5 000
  Einsätze ≈ 220 MB — unkritisch. 300 000 Einsätze (Z2) ≈ **12–14 GB** —
  über dem Kontingent. Schlanke Spalten (INT statt DOUBLE) brächten nur
  −25 % (gemessen 46 B/Zeile). Die Ansichten selbst skalieren: Tag, Einsatz,
  Zeitraum und Export laden nur ihren Ausschnitt (Export: Spuren in Blöcken
  à 25 IDs, `server/assets/export.js` / `api/export_data.php`).
- **B-S2-03 — Sicherung und Wiederherstellung brechen lange vor 5 000.**
  `edbak_build()` (`server/backup_lib.php`) baut den gesamten Bestand als
  PHP-Array und `json_encode`t am Stück → sprengt `memory_limit`. Der Rückweg
  (`api/backup_restore.php`) ist **ein** POST, `json_decode` am Stück, **eine**
  Transaktion mit Einzel-INSERTs je Punkt. Der Browser
  (`server/einstellungen.php`) parst und stringifiziert denselben Berg vor dem
  Versiegeln. Gemessen: 3 Mio. Punkte = 154 MB Text, 2,8 s Parse, ~740 MB
  Heap auf Server-CPU; auf dem Referenzgerät Z3 ist bei ~10 MB je Schritt
  (≈ 400–500 Einsätze mit Spuren) Schluss, ab ~1 000 Einsätzen ist der stille
  Tab-Absturz wahrscheinlich.
- **B-S2-04 — Suche.** `entschluessleListe()` (`server/assets/patient.js`)
  entschlüsselt sequenziell und importiert den Schlüssel **je Datensatz** neu
  (`EdCrypto`-Hilfen in `assets/crypto.js`). Gemessen an 5 000 Blobs à
  ~400 B: 252 ms sequenziell mit Import je Aufruf, 92 ms mit einmal
  importiertem Schlüssel, 97 ms gestapelt — Server-CPU; Referenzgerät Faktor
  5–10. Übertragung ~2 MB (Chiffretext komprimiert nicht). Kritisch wird die
  Suche erst im fünfstelligen Bereich; die Korrektur ist klein (E-S2-16).
- **B-S2-05 — Wartung mit Vollscan.** Die tägliche, **anfragegetriebene**
  Wartung (`server/db.php`) räumt Waisen-Spurpunkte per `LEFT JOIN` über die
  **ganze** `track_points`-Tabelle. Bei Z2-Menge (190 Mio. Zeilen) läuft das
  Minuten — in der Anfrage der ersten Nutzerin des Tages.
- **B-S2-06 — Admin-„Alle sichern“.** Läuft heute als Einzelanfrage über alle
  Konten (Fund aus P3, bisher P5 zugeordnet) und überschreitet bei hunderten
  Konten die PHP-Laufzeit.
- **B-S2-07 — Kodierungs- und Ausdünnungsmessungen.** Ein Blob je Spur
  (32-Bit-Differenzen spaltenweise + zlib) kostet **3,33 B/Punkt → 2,1 MB je
  1 000 Einsätze** (÷20 gegenüber Zeilen); Varint wäre 3,11 B/Punkt, ist aber
  ein handgeschriebener Dekoder (verworfen, E-S2-04). Douglas-Peucker behält
  bei 2 m (2D) 33 % der Punkte, bei 2 m/3 m (3D mit Höhe) 37 %, bei 2 m/5 m
  36 %; kombiniert mit dem Blob bleiben **5,0 B je behaltenem Punkt → 1,1–1,2
  MB je 1 000 Einsätze**. Rechenzeit ~5 ms je Einsatz. Dekodieren ist nativ
  (`unpack`/`struct`/`DataView`): alle 181 Referenzspuren (56 000 Punkte) in
  6 ms.
- **B-S2-08 — Uhr-Randbedingungen.** Die Uhr sendet je Paket in Teilstücken
  (Referenzlauf: Median 3, bis 14), ab `seq_from`; der Server quittiert
  `next_seq`; die Uhr löscht lokal erst nach vollständiger Quittung. Punkte
  liegen auf der Uhr in Blöcken zu 200 je Speicherwert (8-KB-Grenze je Wert,
  `watch/source/Track.mc`); die Uhr dünnt selbst aus (≥ 15 m Abstand,
  höchstens alle 10 s, mindestens 1 s, `watch/source/Const.mc`).
  Nachlieferungen kommen praktisch binnen Stunden bis Tagen. Nach der
  Ausdünnung entstehen Zeitlücken bis 900 s zwischen behaltenen Punkten; die
  Höhenermittlung des Einsatzorts sucht ±300 s um den Referenzzeitpunkt
  (`SITE_ELE_TOLERANCE_S`, `server/site_elevation_lib.php`) — ohne Schutz der
  Phasenpunkte ginge sie leer aus (daher E-S2-05).
- **B-S2-09 — Betrieb.** Die Anwendung hat heute bewusst **keinen Cron**
  (anfragegetriebene Wartung, `docs/Technik.md` 7). `smtp_send()`
  (`server/smtp.php`) existiert und wird für Einladungen/Zurücksetzen genutzt.
  `sicherungen/` hat keine Grenzenlogik; Zielwert des Betreibers: 2 GB,
  konfigurierbar. Ausgehende Dateiübertragung aus der Anwendung gibt es nicht
  (FTPS existiert nur als Deploy-Weg der CI).

---

## 2. Entscheidungen

Nummern fortlaufend; die Herkunft aus den Konzeptfragen steht in 2.13.

### 2.1 Umfang

- **E-S2-01 — Vier Baustellen, sonst nichts.** Umgebaut werden
  (1) Spurspeicherung, (2) Sicherung/Wiederherstellung samt Admin-Sicherungen,
  (3) Komplettbackup samt Transportzielen, (4) Suche und Wartung. Die Ablage
  der geschützten Angaben und die Ansichten bleiben. *Begründung:* B-S2-01
  bis B-S2-06 — die Spurpunkte sind 93 % des Problems, `pat_blob` je Einsatz
  ist keines.
- **E-S2-02 — Kein Jahrescontainer für Patientendaten.** Je Einsatz bleibt
  ein eigener `pat_blob`. *Begründung:* Der Gewinn wären nur WebCrypto-Aufrufe
  (billig, B-S2-04); der Preis wäre Umverschlüsseln des ganzen Jahres bei
  jeder Änderung, Verlust eines Jahres bei gleichzeitigen Schreibern
  (last write wins) und Merge-Logik in jedem Schreibweg.

### 2.2 Spurspeicherung in drei Stufen

- **E-S2-03 — Stufenmodell.** Je Spur (Einsatz oder Ruhesegment) gilt:
  **Stufe 1** Zeilen in `track_points` als Eingangspuffer der Uhr →
  **Stufe 2** verlustfreier Blob, sobald das Paket abgeschlossen ist
  (Karenz E-S2-06; „verlustfrei“ heißt: innerhalb der in 3.1.2
  festgeschriebenen Auflösung — 0,11 m waagerecht, 0,1 m senkrecht, F-S2-01) → **Stufe 3** ausgedünnter Blob **sechs Monate nach
  Einsatzende**; das Original ist danach weg. *Begründung:* Zeilen sind für
  den idempotenten Uhr-Upload richtig (B-S2-08); Blobs sind für Bestand und
  Sicherung richtig (B-S2-07). Sechs Monate Originalvorhaltung: Beschluss
  vom 30.08.2026. Rollierende Kosten des Originalfensters bei Z2: ~40 MB —
  vernachlässigbar; als Zeilen wären es 1,5 GB gewesen.
- **E-S2-04 — Blob-Format „SPUR1“, bewusst ohne handgeschriebenen Dekoder.**
  Differenzen zum Vorgänger als **feste 32-Bit-Ganzzahlen, spaltenweise**
  (erst alle Breitengrad-Deltas, dann Längengrad, Höhe, Zeit), dann zlib.
  Lesen/Schreiben über `pack`/`unpack` (PHP), `DataView` (JS), `struct`
  (Python). Gemessen 3,33 B/Punkt; die 0,2 B Vorteil einer Varint-Kodierung
  rechtfertigen keinen eigenen Dekoder in drei Sprachen. Aufbau in 3.1.2.
- **E-S2-05 — Ausdünnung: Douglas-Peucker dreidimensional, 2 m horizontal /
  3 m Höhe.** Zusätzlich bleiben immer erhalten: erster und letzter Punkt
  sowie **je Phasenzeitpunkt des Einsatzes der zeitnächste Punkt**
  (B-S2-08, Höhenermittlung ±300 s). Das Verfahren erfindet und verschiebt
  keine Punkte; jeder behaltene Punkt liegt exakt auf der Originalspur.
  Messwerte: 37 % der Punkte bleiben (Referenz, synthetisch glatt; echte
  Spuren mehr). *Begründung Höhe als eigene Toleranz:* eine 2D-Ausdünnung
  würde das Höhenprofil eines Fluges einebnen.
- **E-S2-06 — Karenz vor der Verdichtung: `final` gesetzt und 14 Tage ohne
  neuen Punkt.** Zusätzlich gilt ein Paket ohne `final` als abgeschlossen,
  wenn 60 Tage kein Punkt mehr kam (verwaiste Aufzeichnung, z. B. Uhr
  verloren) — sonst blieben solche Zeilen ewig Stufe 1. Vorbedingung der
  Verdichtung: Lückenlosigkeit der `seq` (0 … n−1); bei Lücke keine
  Verdichtung, sondern Zähler auf der Wartungsseite.
- **E-S2-07 — Rundlaufprüfung vor dem Löschen.** Der Verdichtungsjob schreibt
  den Blob, dekodiert ihn sofort wieder und vergleicht Punkt für Punkt gegen
  die Zeilen; erst bei Gleichheit werden die Zeilen gelöscht (eine
  Transaktion je Spur). Verglichen wird gegen den **quantisierten** Sollwert
  der Zeile, nicht gegen die rohe `DOUBLE`-Spalte (F-S2-01): Die Prüfung
  belegt, dass Kodieren und Dekodieren zueinander passen und kein Punkt
  verlorengeht, seine Stelle wechselt oder seine Reihenfolge verliert — nicht
  eine Genauigkeit, die das Format nie zugesagt hat. Die Ausdünnung (Stufe 2 → 3) prüft entsprechend gegen
  die berechnete Behalteliste. Ein Blob trägt Formatkennung und
  Originalpunktzahl im Kopf.
- **E-S2-08 — Nachlieferungen der Uhr; Vertrag bleibt.** `next_seq` =
  Originalpunktzahl aus dem Blob-Kopf plus etwaige Stufe-1-Nachzügler.
  **Zwischen Verdichtung und Ausdünnung:** Punkte ab `next_seq` werden als
  Zeilen angenommen und beim nächsten Verdichtungslauf in den verlustfreien
  Blob eingearbeitet. **Nach der Ausdünnung:** Punkte werden **verworfen**;
  der Server antwortet `next_seq` so, als hätte er sie, damit die Uhr ihren
  Puffer leert (Beschluss 29.08.2026). Aus Sicht der Uhr ändert sich am
  JSON-Vertrag nichts; die Doku bekommt einen Nachtrag ohne Vertragsänderung.
- **E-S2-09 — GPX-Abruf je Einsatz (Backlog Nr. 3, hierher gezogen).** In den
  ersten sechs Monaten liefert er die Originalspur, danach die ausgedünnte —
  mit sichtbarer Kennzeichnung, welche von beiden. Backlog **Nr. 2**
  (Anzeige-Vereinfachung) wird durch die Ausdünnung für Bestände ab sechs
  Monaten gegenstandslos; für frische Einsätze ist keine zusätzliche
  Vereinfachung nötig (Tagesansicht ~6 000–10 000 Punkte, unter 1 ms
  Dekodierzeit, Leaflet unkritisch) — Nr. 2 wird mit dieser Begründung
  geschlossen.

### 2.3 Sicherung und Wiederherstellung

- **E-S2-10 — Containerfassung 4: ein ZIP mit versiegelten Teilen.** Endung
  bleibt `.edbak`. Inhalt: `manifest.edbak`, `kern.edbak`,
  `spuren/NNNN.edbak`. Jedes Teil ist ein AES-GCM-Container wie bisher
  (gleiches Salz, gleiche Runden in jedem Teilkopf — **eine** PBKDF2 je
  Vorgang); die GCM-Zusatzdaten (AAD) binden Sicherungskennung, Teilname und
  Teilnummer/Gesamtzahl, damit ein fehlendes, vertauschtes oder fremdes Teil
  beim Öffnen auffällt (Muster Cryptomator/age: Chunk-Index als
  authentifizierte Zusatzdaten). ZIP ohne eigene Kompression (Inhalte sind
  gzip+AES). Erkennung: `PK`-Magie + `manifest.edbak` = Fassung 4;
  `EDBAK2`-Magie = Altformat. *Begründung ZIP statt Einzeldatei mit
  Pakettabelle:* zip.js ist vendoriert und im Export erprobt, Teile sind
  einzeln prüf- und rettbar, das dokumentierte Python-Rezept bleibt je Teil
  gültig.
- **E-S2-11 — Erstellung vollständig im Browser; kein Serverjob, kein
  Zweitschlüssel.** Der Browser holt den Kern ohne Spuren, entschlüsselt
  `pat` wie heute, holt die Spur-Blobs **blockweise** (Muster Export, 25
  Verweise je Anfrage; Stufe-1-Spuren kodiert der Server dabei verlustfrei),
  versiegelt Teil für Teil und schreibt das ZIP streamend (zip.js).
  *Begründung:* Nach der Verdichtung wiegen die Spuren von 5 000 Einsätzen
  nur noch 5–10 MB — der frühere Vorschlag (Serverjob mit übergebenem
  Spurenschlüssel) ist damit überholt; E2E bleibt exakt wie heute, der
  Browser parst nie einen Punkt.
- **E-S2-12 — Wiederherstellung: Kern zuerst, dann Pakete.** Browser öffnet
  Manifest und Kern, verschlüsselt `pat` bei Fremdkonto um (bestehender Weg),
  sendet den Kern (ein POST); der Server legt an wie heute (D1-Regeln,
  Papierkorb nach S1) und liefert die Zuordnung Spurverweis → angelegter
  Datensatz zurück. Danach je Spurpaket: Browser entsiegelt (klein), sendet
  die Blobs der angelegten Datensätze (POST ≤ 2 MB); der Server schreibt
  Blob-Zeilen — Sekunden statt Millionen Punkt-INSERTs. Wiederaufnahme nach
  Abbruch: bereits vorhandene Blobs werden übersprungen; Fortschritt sichtbar.
  **Altformat (R11/Z6):** Die einteilige `.edbak` (Nutzlast 6/7) wird
  weiterhin gelesen; ihr Einspielen bleibt in den heutigen Mengengrenzen —
  wer große Bestände hat, zieht die Sicherung danach neu in Fassung 4.
  Eine Fassung-4-Datei in einer alten Anwendung scheitert sauber
  (keine `EDBAK2`-Magie).
- **E-S2-13 — Die Sicherung nimmt den Datenbankstand.** Im ersten halben Jahr
  also das Original, danach die ausgedünnte Fassung; kein zweiter Kodierweg.
- **E-S2-14 — Admin-Sicherungen auf dieselbe Paketbibliothek.** Rohformat wie
  bisher (`pat_blob` als Chiffretext, serverseitig, unversiegelt), aber
  paketiert statt am Stück; **„Alle sichern“ läuft in Schüben** über den
  Job-Einstieg mit Fortschrittsanzeige (aus P5 hierher gezogen, B-S2-06).
  **Aufbewahrung: Standard 2 je Konto**, manuell mehr je Konto möglich; bei
  Erreichen der Speichergrenze wird abgelehnt **mit Meldung**, nie still
  verdrängt.
- **E-S2-15 — Speichergrenze mit Warnschwellen.** Grenze für `sicherungen/`
  und Warnschwellen (GB-Werte) sind im Admin-Bereich einstellbar (Vorgabe
  2 GB); der Job prüft den Stand je Lauf und meldet je überschrittener
  Schwelle **einmal** per `smtp_send()` an die Admin-Adresse; ohne
  eingerichtetes SMTP stattdessen dauerhafter Hinweis im Admin-Bereich.

### 2.4 Suche, Jobs, Wartung

- **E-S2-16 — Suche.** Schlüssel einmal je Sitzung importieren (Cache in
  `EdCrypto`), Entschlüsselung in Stapeln (Größenordnung 200 parallel).
  Kein Umbau des Suchindex. Messziel: erste Trefferanzeige bei 5 000
  Einsätzen ≤ 5 s auf Z3.
- **E-S2-17 — Ein Job-Einstieg, drei Auslöser.** `jobs.php` arbeitet in
  Häppchen (jedes Häppchen hält die Z3-Serverbudgets ein) und kann per
  CLI-Cron, per zeitgesteuertem URL-Aufruf mit Token oder anfragegetrieben
  (wie die heutige Wartung) angestoßen werden. Die Wartungsseite zeigt
  letzten Lauf, Auslöser und Rückstände. *Begründung:* Damit bleibt die
  Hosterwahl offen; CLI ist der empfohlene Regelfall.
- **E-S2-18 — Wartung ohne Vollscan.** Die Waisenprüfung über `track_points`
  und `track_blobs` läuft nicht mehr als Tabellen-Anti-Join in einer
  Nutzeranfrage, sondern im Job, bereichsweise über den Primärschlüssel, als
  Sicherheitsnetz (die Löschwege löschen Spuren ohnehin mit). Alle Löschwege
  (Papierkorb-Endlöschung, Konto löschen, Einsatz/Segment löschen) löschen
  künftig Zeilen **und** Blob.

### 2.5 Komplettbackup und Transport

- **E-S2-19 — Kein nächtliches Konto-Backup; Komplettbackup der
  Installation stattdessen.** Automatische versiegelte Sicherungen je Konto
  ohne Browser sind abgelehnt (Beschluss 29.08.2026); ein Konto-Schlüsselpaar
  dafür wandert als Backlog-Punkt in die Zukunft. Das **Komplettbackup**
  umfasst die ganze Installation: alle Konten, Stammdaten, Geräte,
  Schlüsselhüllen, `app_state`, Migrationsstand; `config.php` bleibt draußen
  und gehört ins getrennt aufbewahrte Wiederanlaufpaket.
- **E-S2-20 — Form: eigener SQL-Dump plus App-Rückweg.** Ein Statement je
  Zeile, INSERT-Stapel ≤ 1 MB, Tabellen in einspielbarer Reihenfolge,
  Kopfkommentare (Version, Migrationsstand, Zeitpunkt), gzip — einspielbar
  mit `mysql`/phpMyAdmin **und** über die App-Seite „Installation
  wiederherstellen“ (nur bei leerer Datenbank, liest aus
  `sicherungen/eingang/` oder vom Transportziel, führt in Häppchen aus,
  danach Migrationslauf). Erzeugung in Häppchen über den Job-Einstieg
  (tabellenweise mit Fortsetzungszustand), nie als Array am Stück.
- **E-S2-21 — Verschlüsselung des Komplettbackups.** Serverschlüssel in
  `config.php` (32 B Zufall); versiegelt in Häppchen (AES-GCM je Häppchen,
  Zähler in den Zusatzdaten wie E-S2-10). Pflicht, sobald die Datei das Haus
  verlässt (Transportziel); beim Direktdownload optional zusätzlich eine
  Passphrase (dann PBKDF2-versiegelte Fassung). Der Schlüssel gehört ins
  Wiederanlaufpaket; das Runbook sagt es ausdrücklich. Derselbe
  Serverschlüssel schützt die Transport-Zugangsdaten in der Datenbank.
- **E-S2-22 — Transportziele FTP, FTPS, SFTP.** Eine Transport-Schnittstelle
  mit drei Adaptern: FTP/FTPS über die PHP-Erweiterung `ftp`
  (`ftp_ssl_connect`), SFTP über **phpseclib** (MIT, reines PHP, vendoriert;
  Eintrag in `docs/Lizenzen.md`). Pflege im Admin-Bereich (Protokoll, Host,
  Port, Nutzer, Passwort/Schlüssel, Pfad) mit „Verbindung prüfen“;
  Zeitplan für den Push im Admin-Bereich, Ausführung über den Job-Einstieg.
  WebDAV: Backlog.

### 2.6 Messstand und Budget

- **E-S2-23 — Messstand als Arbeitspaket 0, dauerhaft im Repo** (R35).
  Vervielfältiger erzeugt aus den Referenz-Quelldaten einen Großbestand
  (Ziel 5 000 Einsätze, Kennungen und Zeiten versetzt) als Folge einteiliger
  `.edbak`-Dateien à ~400–500 Einsätze — eingespielt über den **regulären**
  Wiederherstellungsweg, kein SQL vorbei an der Validierung (Geist von R4).
  Browserprobe (Playwright, CPU-Drossel 6×) misst Suche, Tagesansicht,
  Sichern, Wiederherstellen; Serverprobe misst Jobläufe, Speicherspitzen,
  Tabellen­größen. Vor dem Umbau wird der heutige Stand als Ausgangsmessung
  festgehalten (einschließlich der Stelle, an der er bricht).
- **E-S2-24 — Zielzahlen der Abnahme** (5 000-Einsätze-Konto, Drossel 6×):
  Suche erste Anzeige ≤ 5 s · Tagesansicht ≤ 3 s · Sicherung erstellen
  ≤ 5 min · Wiederherstellung in leeres Konto ≤ 15 min · Sicherungsdatei
  ≤ 25 MB · Spuren in der Datenbank ≤ 3 MB je 1 000 Einsätze nach Ausdünnung,
  ≤ 6 MB je 1 000 gesamt · kein Verstoß gegen die Z3-Budgets. Abweichungen
  nur mit Begründung im Prüfdokument.

### 2.13 Zuordnung der Konzeptfragen

| Frage (Gespräch) | Entscheidung |
|---|---|
| F1 Umfang | E-S2-01, E-S2-02 |
| F2 Automatische Serversicherung | E-S2-19 (nein; Schlüsselpaar → Backlog) |
| F3 Cron | E-S2-17 (beide Wege plus Rückfall) |
| F4 Speichergrenze | E-S2-15 (konfigurierbar, Warnmails) |
| F5 Container | E-S2-10 |
| F6/F7 Einordnung, Messstand | R34, E-S2-23/R35 |
| F8 Toleranzen | E-S2-05 (2 m / 3 m) |
| F9 Zielumgebung | Z2 (Webspace, 10 GB) |
| F10 Karenz | E-S2-06 (14 Tage) |
| F11 GPX | E-S2-09 |
| F12 Ausdünnungsfrist | E-S2-03 (6 Monate, Original danach weg) |
| F14 Nachlieferung | E-S2-08 (nach Ausdünnung verwerfen) |
| F15 Original in der Sicherung | E-S2-13 |
| F16–F21 Komplettbackup | E-S2-19 bis E-S2-22, E-S2-14/15 |

---

## 3. Ausarbeitung

### 3.1 Spurspeicherung

**3.1.1 Tabelle `track_blobs`.** Eine Zeile je Spur:
`owner_type` (`mission`/`rest`), `owner_id`, gemeinsamer Primärschlüssel;
`stufe` (2 = verlustfrei, 3 = ausgedünnt); `n_original` (Punktzahl vor jeder
Ausdünnung — Grundlage für `next_seq`); `n_gespeichert`; `blob` (MEDIUMBLOB);
`erstellt_am`, `geaendert_am`. `track_points` bleibt unverändert und führt nur
noch Stufe-1-Bestand (Eingangspuffer, Nachzügler).

**3.1.2 Blob-Format „SPUR1“.** Kopf unkomprimiert:
Magie `SP` (2 B) · Formatfassung `1` (1 B) · Stufe (1 B) · **Auflösungskennung
(1 B)** · `n_original` (uint32 LE) · `n_gespeichert` (uint32 LE). Danach ein
zlib-Strom über die Nutzlast: **spaltenweise** vier Reihen zu je
`n_gespeichert` Werten int32 LE als Differenzen zum Vorgänger (Startwert 0):
Breitengrad ×10⁶, Längengrad ×10⁶, Höhe ×10 (Zehntelmeter), Zeitstempel in
Sekunden. Vor der Höhenreihe ein Bitfeld (`⌈n/8⌉` Bytes): Bit gesetzt = Höhe
vorhanden; Differenzen laufen nur über vorhandene Werte.

Die **Auflösungskennung** ist mit F-S2-01 dazugekommen und trägt den Wert `1`
für „Grad ×10⁶, Höhe ×10“. Sie steht im Kopf, weil die Auflösung eine Zusage
des Formats ist und kein Rechenweg: Wer sie später ändert, ändert damit die
Bedeutung jedes bereits geschriebenen Blobs. Ein Leser, der eine ihm
unbekannte Kennung findet, verweigert die Arbeit, statt Zahlen mit dem
falschen Faktor zu deuten. Die Zusage lautet **0,11 m waagerecht, 0,1 m
senkrecht** — darunter liegt kein GPS-Empfänger, und die Ausdünnung nach
E-S2-05 arbeitet ohnehin mit 2 m / 3 m.

`seq` wird nicht gespeichert — die Verdichtung
setzt Lückenlosigkeit voraus (E-S2-06), die Position im Blob ist die `seq`.
Handlesbarkeit: PHP `unpack('l*', gzuncompress(...))`, Python
`struct.unpack`, JS `DataView` — das Rezept kommt als Abschnitt in
`docs/Backup-Format.md` (analog zum bestehenden Python-Rezept des
Containers).

**3.1.3 Leser über eine Bibliothek.** `server/spur_lib.php` ist die einzige
Stelle, die SPUR1 liest und schreibt: `spur_lesen(owner)` liefert Punkte aus
Blob **plus** etwaigen Stufe-1-Zeilen dahinter (Nachzügler), `spur_kodieren()`,
`spur_ausduennen()`, `spur_naechste_seq()`. Umzustellende Verbraucher:
Tagesansicht (`api/day.php`), Einsatz (`api/mission.php`), Export
(`api/export_data.php`), Sicherung, Höhenermittlung
(`site_elevation_lib.php`), GPX (neu). Löschwege löschen Blob und Zeilen
(E-S2-18).

**3.1.4 Jobs.** *Verdichtung:* wählt je Lauf eine begrenzte Zahl
abgeschlossener Stufe-1-Spuren (E-S2-06), kodiert, Rundlaufprüfung, löscht
Zeilen — eine Transaktion je Spur, wiederholbar. Existiert schon ein Blob
(Nachzügler-Fall), wird Blob + Zeilen zu einem neuen verlustfreien Blob
zusammengeführt.

> **Fortgeschrieben in AP3 (31.08.2026).** Hier stand: „liegt der Einsatz
> bereits jenseits der Sechsmonatsfrist, läuft unmittelbar die Ausdünnung
> hinterher." Das wird **nicht** gebaut. Zwei unwiderrufliche Schritte mit zwei
> verschiedenen Rundlaufbegriffen in einem Budgetfenster ergeben einen Job,
> dessen Scheitern sich hinterher nicht mehr zuordnen lässt. Die frisch
> verdichtete Spur trägt Stufe 2 und wird vom Ausdünnungsjob im nächsten
> Durchlauf gefunden; der Verlust ist ein Jobzyklus. *Ausdünnung:* wählt Stufe-2-Blobs
mit Einsatzende älter sechs Monate, rechnet die Behalteliste (E-S2-05),
prüft, ersetzt den Blob durch Stufe 3. Beide Jobs protokollieren Stückzahlen
auf der Wartungsseite.

**3.1.5 `ingest.php`.** Berechnet `next_seq` künftig über
`spur_naechste_seq()` (Blob-Kopf + Zeilen). Nach Ausdünnung: eingehende
Punkte ab `n_original` werden verworfen, die Antwort bestätigt sie
(E-S2-08); davor werden sie als Zeilen angenommen. Für die Uhr ist beides
ununterscheidbar vom heutigen Verhalten.

### 3.2 Sicherungscontainer Fassung 4

**3.2.1 Aufbau.** ZIP (Speichern ohne Kompression), Einträge:

- `manifest.edbak` — versiegelt; Klartext-JSON: Format/Fassung,
  Sicherungskennung (16 B Zufall, hex), Erstellzeit, Web-Version,
  Teileliste (Name, Art, SHA-256 des versiegelten Teils), Zahl der
  Spurpakete, Prüfwert des Inhaltsschlüssels wie bisher.
- `kern.edbak` — versiegelt; die heutige Nutzlast **ohne** Punktlisten;
  Nutzlast-Fassung steigt. Jedes spurtragende Objekt erhält eine
  fortlaufende `spur_ref` und die Angaben Stufe/`n_original`/`n`.
  Geschützte Angaben stehen wie bisher **entschlüsselt** im versiegelten
  Kern (E2E-Ablauf unverändert: der Browser entschlüsselt vor dem
  Versiegeln, verschlüsselt beim Einspielen um).
- `spuren/0001.edbak` … — versiegelt; je Teil eine Liste
  `{spur_ref, blob}` (SPUR1, Base64), chronologisch, an Spurgrenzen
  geschnitten, Ziel ≤ 2 MB Klartext je Teil.

**3.2.2 Versiegelung.** Jedes Teil ist der bestehende AES-GCM-Container;
Salz und Rundenzahl sind in allen Teilen identisch (eine PBKDF2, Schlüssel
wird je Vorgang zwischengespeichert). Zusatzdaten (AAD): für das Manifest
`EDBAK4|manifest`; für jedes weitere Teil
`EDBAK4|<sicherungskennung>|<name>|<nr>/<gesamt>` — Kennung und Gesamtzahl
stammen aus dem geöffneten Manifest. Damit fällt jedes fehlende, doppelte,
vertauschte oder aus einer anderen Sicherung stammende Teil kryptographisch
auf, nicht erst beim Datenvergleich.

**3.2.3 Sichern (Browser).** Kern holen (neuer Schalter „ohne Spuren“ an
`api/backup_data.php`) → `pat` stapelweise entschlüsseln → Kern versiegeln
und ins ZIP schreiben → Spur-Blobs blockweise holen (neuer Endpunkt
`api/backup_spuren.php`, 25 Verweise je Anfrage; Stufe-1-Spuren kodiert der
Server im Vorbeigehen verlustfrei) → Teile versiegeln und anhängen →
Manifest zuletzt (es kennt dann alle Prüfsummen) → Download. Fortschritt
je Teil sichtbar; Budgets nach Z3.

**3.2.4 Wiederherstellen (Browser + Server).** Manifest öffnen →
Vollständigkeit gegen die Teileliste prüfen → Kern öffnen → Fremdkonto-Fall
wie heute (Umverschlüsselung mit vorhandenem Weg) → Kern senden; der Server
legt an (D1-/Papierkorbregeln aus S1 unverändert) und liefert die Zuordnung
`spur_ref → owner`. Dann je Spurteil: öffnen, Blobs der angelegten
Datensätze senden (≤ 2 MB), Server schreibt `track_blobs`-Zeilen; vorhandene
werden übersprungen (Wiederaufnahme). Am Ende Höhenermittlung wie bisher
nachgelagert. Altformat: unverändert über den bestehenden Weg (E-S2-12).

**3.2.5 Prüf- und Vergleichswerkzeuge.** `tools/referenzdatensatz/vergleich/`
(Kreislauf `edbak`) lernt Fassung 4 (ZIP öffnen, Teile nach Rezept
entsiegeln); eine **neue Referenzdatei in Fassung 4** wird gezogen; die
bestehende einteilige Referenz **bleibt** als Abnahmedatei für R11/Altformat
liegen. `tools/wiederherstellungs-probe/` (R27) läuft gegen beide Formate.

### 3.3 Admin-Sicherungen und Speicherverwaltung

Die Paketbibliothek (Kern/Spurteile) bekommt einen Rohmodus ohne
Versiegelung (`pat_blob` bleibt Chiffretext); `adminbackup_lib.php` erzeugt
und liest künftig diese Form. „Alle sichern“ wird ein Joblauf: ein Konto je
Häppchen, Fortschritt und Fehler je Konto sichtbar, wiederaufnehmbar.
Aufbewahrung: 2 je Konto automatisch, manuell zusätzliche je Konto
(Kontoseite aus P3 als Ort, E-P3-41); Grenzen- und Schwellenprüfung mit
Warnmail nach E-S2-15.

### 3.4 Komplettbackup

Dump-Erzeuger als Jobfolge (tabellenweise, Fortsetzungszustand in der
Job-Tabelle), Ausgabe gzip in `sicherungen/komplett/`, versiegelt nach
E-S2-21. Direktdownload streamend; Push aufs Transportziel nach Zeitplan.
Rückweg „Installation wiederherstellen“ im Kontext von
`install.php`/`update.php`, nur bei leerer Datenbank, mit Fortschritt und
Wiederaufnahme; danach Migrationslauf. Runbook-Kapitel in `docs/Technik.md` 7:
Wiederanlaufpaket = `config.php` + Serverschlüssel + Zugang zum
Transportziel; Probe-Wiederherstellung als Prüfpunkt.

### 3.5 Gerätebudget (Z3) — verbindliche Prüfgrößen

Kein Browserschritt über 10 MB JSON-Zeichenkette · Heap-Spitze ≤ 100 MB ·
eine PBKDF2 je Vorgang · POST ≤ 2 MB · Serveranfrage ≤ 30 s ·
PHP-Speicherspitze ≤ 64 MB · Joblauf-Häppchen einzeln unter diesen Grenzen.
Der Messstand misst diese Größen ausdrücklich (Playwright-Metriken,
`memory_get_peak_usage`).

---

## 4. Offene Fragen

Keine zum Übergabezeitpunkt — F1–F21 der Konzeptphase sind entschieden und in
Abschnitt 2 überführt. Neue Fragen der Umsetzung werden hier als F-S2-XX
geführt und nach K6 vor dem betroffenen Arbeitspaket entschieden.

### F-S2-01 — Welche Auflösung schreibt SPUR1 fest? (entschieden, 31.08.2026)

**Die Frage.** E-S2-07 verlangt eine Rundlaufprüfung: Blob dekodieren, *Punkt
für Punkt* gegen die Zeilen vergleichen, „erst bei Gleichheit werden die Zeilen
gelöscht". 3.1.2 legt die Höhe aber „in Metern gerundet" ab. Beides zusammen
geht nicht auf — und zwar nicht theoretisch, sondern gemessen: **41 582 von
55 861 Punkten des Referenzbestands (74,4 %) tragen eine Höhe mit
Nachkommastelle** (699,7 · 702,7 · …). Die Gleichheit träte nie ein; der
Verdichtungsjob löschte keine einzige Zeile, und zwar stillschweigend.

Bei `lat`/`lon` liegt die Sache umgekehrt und ist deshalb tückischer: Der
Referenzbestand hat **exakt sechs Nachkommastellen** (0 Punkte darüber), die
Prüfung ginge dort also durch. Die Uhr aber formatiert nicht — sie reicht den
`Lang.Double` von Garmin durch (`watch/source/Track.mc`, Auflösung der
Halbkreis-Darstellung ≈ 8,4·10⁻⁸°). Auf echten Gerätespuren stiege dieselbe
Prüfung wieder aus. Die grüne Zahl käme aus dem synthetischen Bestand — genau
der Fehler, vor dem `CLAUDE.md` 6 nach O9c warnt.

**Der Kern.** Keine Festkomma-Kodierung ist bitgleich gegen einen beliebigen
`DOUBLE`. „Verlustfrei" kann in Stufe 2 deshalb nur heißen: *verlustfrei
innerhalb einer festgeschriebenen Auflösung*. Die Auflösung gehört damit in
das Format und in die Dokumentation, und die Rundlaufprüfung vergleicht gegen
den **quantisierten Sollwert**, nicht gegen die rohe Spalte.

**Gemessen** über alle 181 Spuren des Referenzbestands (55 861 Punkte):

| Variante | B/Punkt | je 1000 Einsätze | Punkte ≠ Original |
|---|---|---|---|
| A — 1e-6° / 1 m (Konzeptwortlaut) | 3,32 | 1,40 MB | 41 958 |
| **C — 1e-6° / 0,1 m** | **3,56** | **1,50 MB** | **0** |
| D — 1e-7° / 0,1 m | 4,11 | 1,74 MB | 0 |

Variante A bestätigt zugleich die Befundzahl B-S2-07 (3,33 B/Punkt) — das
Format als solches ist richtig gerechnet.

**Entschieden: Variante C.** SPUR1 schreibt `lat`/`lon` als Vielfache von
10⁻⁶ Grad (≈ 0,11 m) und die Höhe als Vielfache von 0,1 m fest. Begründung:

- Die Nachkommastelle der Höhe ist **vorhandene Angabe**, keine Zierde — drei
  von vier Punkten führen sie. Sie im „verlustfreien" Zustand wegzuwerfen und
  denselben Zustand dann sechs Monate lang als Original auszuliefern (GPX,
  AP4) wäre ein stiller Verlust an der Stelle, die ihn ausdrücklich nicht
  haben soll.
- Der Preis sind **7 % Blobgröße** (0,10 MB je 1000 Einsätze) gegen einen
  Zielwert von 3 MB (E-S2-24). Das ist kein Abwägungsfall.
- Und sie hält die Abnahme von AP1 wörtlich: Über den Referenzbestand kommt
  jeder Punkt bitgleich zurück, „0 Abweichungen" ist dort eine echte Null und
  keine gerundete.

Variante D wurde verworfen: Sie kostet 24 % und ist gegen einen `DOUBLE`
**ebenfalls nicht** bitgleich — sie verschöbe die Grenze, ohne sie
aufzuheben. Wo die Grenze liegt, gehört gesagt; 0,11 m liegt weit unter der
Genauigkeit jedes GPS-Empfängers und weit unter der Ausdünnungstoleranz von
2 m, die dasselbe Konzept in E-S2-05 beschließt.

**Folgen für die Umsetzung.**

1. Der Blob-Kopf trägt die Auflösung mit (3.1.2) — ein späterer Wechsel ist
   damit erkennbar und nicht stillschweigend.
2. Die Rundlaufprüfung (E-S2-07) vergleicht gegen den quantisierten Sollwert.
   Das ist keine Aufweichung: Sie prüft weiterhin, dass Kodieren und
   Dekodieren zueinander passen und kein Punkt verlorengeht oder die Stelle
   wechselt — nur nicht mehr gegen eine Genauigkeit, die das Format nie
   zugesagt hat.
3. `docs/Backup-Format.md` und `docs/Technik.md` nennen die Auflösung
   ausdrücklich als Eigenschaft von Stufe 2.

---

## 5. Arbeitspakete

Reihenfolge wie nummeriert; AP7 und AP9 sind unabhängig und können vorgezogen
werden. Je Paket ein Commit (K7), Konzeptfortschreibung im Paketabschluss.

**AP0 — Messstand und Ausgangsmessung** (`tools/messstand/`, dauerhaft, R35).
Vervielfältiger nach E-S2-23; Browser- und Serverprobe; LIESMICH mit
Bedienweg. Ausgangsmessung des heutigen Stands dokumentiert (einschließlich
Bruchstelle der einteiligen Sicherung).
*Abnahme:* 5 000-Einsätze-Konto reproduzierbar herstellbar; Messprotokoll
mit Ausgangszahlen liegt im Repo; die Probe läuft gegen das Demo-Konto einer
Entwicklungsinstallation, nie gegen die Referenz (Riegel wie
`demo_pruefen.mjs` nach S1).

**AP1 — Spurbibliothek und Blob-Format.** `spur_lib.php`, SPUR1 nach 3.1.2,
Migration `track_blobs`, Umstellung aller Leser und Löschwege,
Rundlaufprüfung als Funktion samt Prüfwerkzeug.
*Abnahme:* Rundlauf Zeilen → Blob → Punkte über den ganzen Referenzbestand
bitgleich (55 861 Punkte, 0 Abweichungen); Tagesansicht/Einsatz/Export
liefern vor und nach Verdichtung identische Ausgaben; Löschwege hinterlassen
weder Zeilen noch Blob.

**AP2 — Job-Einstieg und Wartung.** `jobs.php` mit drei Auslösern
(E-S2-17), Job-Tabelle mit Fortsetzungszustand, Anzeige auf der
Wartungsseite; Waisenprüfung bereichsweise statt Vollscan (E-S2-18).
*Abnahme:* Derselbe Rückstand wird über jeden der drei Auslöser vollständig
abgearbeitet; kein Häppchen verletzt die Z3-Servergrenzen; die heutige
Vollscan-Stelle in `db.php` ist ersetzt.

**AP3 — Verdichtung und Ausdünnung.** Jobs nach 3.1.4, `ingest.php` nach
3.1.5, Zähler für Lücken-Spuren.
*Abnahme:* Am Messstand-Konto erreichen alle Alt-Spuren Stufe 3, frische
Stufe 2; Spurgröße ≤ 3 MB je 1 000 Einsätze (E-S2-24); Nachlieferung vor der
Ausdünnung wird eingearbeitet (Prüffall mit nachgereichtem Teilstück),
nach der Ausdünnung verworfen und quittiert; die Uhr-Referenz-Payloads aus
P1 laufen unverändert durch (526 Anfragen, 0 Fehlversuche).

**AP4 — GPX-Abruf** (Backlog Nr. 3). Je Einsatz und je Ruhesegment; Kennzeichnung
Original/ausgedünnt (E-S2-09); Validierung gegen den P1-Datensatz.
*Abnahme:* GPX validiert gegen Schema; Punktzahl entspricht der jeweiligen
Stufe; Kennzeichnung sichtbar.

**AP5 — Containerfassung 4: Sichern und Wiederherstellen.** Nach 3.2;
einschließlich Altformat-Lesepfad, Fortschrittsanzeigen, Kreislauf- und
Referenzpflege (3.2.5).
*Abnahme:* Kreislauf `edbak` gegen die neue Fassung-4-Referenz **0
unerklärt** (R24); R11-Abnahme: die einteilige 7.x-Referenz spielt in eine
frische Installation ein; Fassung-4-Datei mit fehlendem/vertauschtem Teil
wird mit verständlicher Meldung abgewiesen (AAD-Prüffall); Sichern und
Wiederherstellen des 5 000er-Kontos innerhalb der Zielzahlen E-S2-24 unter
Drossel; `tools/wiederherstellungs-probe/` grün auf beiden Formaten.

**AP6 — Admin-Sicherungen und Speicherverwaltung.** Nach 3.3; Grenze,
Schwellen, Warnmail (E-S2-15), Aufbewahrung 2 je Konto + manuell.
*Abnahme:* „Alle sichern“ über 20 Messstand-Konten läuft in Schüben mit
Fortschritt und Wiederaufnahme; Grenzfall erzeugt Ablehnung mit Meldung;
Schwellenüberschreitung erzeugt genau eine Mail (bzw. Adminhinweis ohne
SMTP).

**AP7 — Transportziele.** Schnittstelle + Adapter FTP/FTPS (`ext/ftp`) und
SFTP (phpseclib, vendoriert; `docs/Lizenzen.md`); Admin-Pflege mit
„Verbindung prüfen“; Zugangsdaten unter dem Serverschlüssel (E-S2-21/22).
*Abnahme:* Push und Verbindungsprüfung gegen ein echtes Ziel je Protokoll
(Zuarbeit, Abschnitt 9); Zugangsdaten stehen nie im Klartext in der
Datenbank; Fehlerfälle (Host falsch, Passwort falsch, Platz voll) mit
verständlicher Meldung.

**AP8 — Komplettbackup.** Nach 3.4 samt Rückweg und Runbook (E-S2-19 bis
E-S2-21); Zeitplan im Admin-Bereich.
*Abnahme:* Voller Zyklus am Messstand: Komplettbackup erzeugen → auf
Transportziel schieben → leere Installation → „Installation
wiederherstellen“ → Kreisläufe und Stichproben grün; jedes Häppchen unter
den Z3-Servergrenzen; Dump zusätzlich von Hand mit `mysql` einspielbar.

**AP9 — Suche.** Schlüssel-Cache und Stapelung (E-S2-16).
*Abnahme:* 5 000 Einsätze, Drossel 6×: erste Anzeige ≤ 5 s; Messwert im
Prüfdokument, Vergleich zur Ausgangsmessung.

**AP10 — Abschluss.** Doku-Nachträge (Abschnitt 7), Backlog-Pflege,
Regressionsläufe (Abschnitt 6), Prüfdokument nach K9, Statuszeile im
Rahmenplan.
*Abnahme:* Prüfliste des Prüfdokuments vollständig; Wortliste 0/0/0 (R28);
alle Zahlen aus E-S2-24 eingetragen.

---

## 6. Prüf- und Regressionspflichten

- **R24:** Beide Kreisläufe (`csv`, `edbak`) vor Phasenabschluss; `edbak`
  gegen die neue Fassung-4-Referenz, zusätzlich der Altformat-Einspiellauf
  gegen die 7.x-Referenz (R11). Sollstand: 0 unerklärte Abweichungen.
- **R27:** `tools/wiederherstellungs-probe/` und `papierkorb_misch.mjs`
  laufen mit — S2 berührt den Rückspielweg unmittelbar.
- **R28:** `tools/wortliste/` läuft mit — S2 erzeugt neue sichtbare Texte
  (Wartungsseite, Admin-Bereich, Meldungen).
- **R35 (neu):** Der Messstand läuft mit seinen Zielzahlen (E-S2-24); die
  Ausgangsmessung aus AP0 ist der Vergleichsmaßstab.
- Prüfdokument nach K9 als eigene Datei
  (`docs/Pruefdokument-S2-Mengen-Spuren-Sicherung.md`): Kurzfassung,
  maschinelle Prüfungen samt Zahlen, nicht Prüfbares, abhakbare Prüfliste
  mit Bedienweg, erwartetem Ergebnis und Bedeutung eines Fehlschlags.

---

## 7. Dokumentations- und Backlog-Pflege

- `docs/Technik.md`: neues Kapitel Spurspeicherung (Stufen, SPUR1,
  Fristen), Jobs und Auslöser, Runbook Komplettbackup/Wiederanlauf,
  Fortschreibung des Bedrohungsmodells (unverändertes E2E, Serverschlüssel).
- `docs/Backup-Format.md`: Containerfassung 4 (Aufbau, AAD, Rezept zum
  Handöffnen je Teil), SPUR1-Rezept, Altformat-Hinweis; Warnhinweis analog
  E-S1-07 (alte Anwendungen lesen Fassung 4 nicht — sauberer Fehler).
- `docs/JSON-Vertrag.md`: Nachtrag ohne Vertragsänderung — serverseitige
  Ablage, `next_seq`-Semantik nach Verdichtung/Ausdünnung.
- Handbuch: Sicherung (neue Datei­form, unveränderte Bedienung), GPX-Abruf
  mit Kennzeichnung, Sechsmonatsregel der Originalspur, Admin-Kapitel
  (Grenze, Schwellen, Transportziel, Komplettbackup).
- `docs/Lizenzen.md`: phpseclib. `CLAUDE.md`: Pflegepflicht „SPUR1 nur über
  `spur_lib.php`“.
- Backlog: **Nr. 2 und Nr. 3 schließen** (Begründung E-S2-09) · neu:
  WebDAV-Transportadapter · neu: Konto-Schlüsselpaar für versiegelte
  Serversicherungen (aus E-S2-19).

---

## 8. Fehlerfunde

Sammelstelle nach K4; bei Übergabe leer.

| Nr. | Fund | Status |
|---|---|---|
| F-S2-A | Die Prüfmittel sind seit P3/O11 an sechs Stellen kaputt: Einspiellauf (4) und CSV-Kreislauf (2) | behoben in AP0 |
| F-S2-B | Kontolöschung lässt Spurpunkte als Waisen liegen — der Kommentar behauptet das Gegenteil | behoben in AP1 |
| F-S2-C | Die Wiederherstellung schneidet Spuren über 2000 Punkten ab; die Uhr darf sie aber beliebig lang aufbauen | behoben in AP1 (F-S2-02) |

### F-S2-A — Die Prüfmittel hängen an Markup, das P3 verändert hat

**Gefunden** beim Aufbau der Prüfumgebung für AP0: `einspielen.py` brach in der
Stufe `stammdaten` mit `KeyError: 'Luftrettungsstation Hochkreuth'` ab — für
einen Standort, der in der Datenbank längst lag.

**Ursache.** Vier Stellen des Werkzeugs lesen Markup der Anwendung, und alle
vier hängen an einer Schreibweise, die P3/O11 (`a7f371f`, Web 9.12.0) geändert
hat. Der Baustein `ui_feld()` rendert seither
`<select class="feld-eingabe" id="…" name="…">`; `ui_meldung_markup()` schreibt
`meldung-fehler` statt `alert-danger`; die Geräteliste führt je Gerät ein
eigenes `<form id="f-devdel-…">`; und der Kasten mit den Zugangsdaten eines
neuen Geräts benutzt den Baustein `codeblock` statt `<code>`.

| Stelle | Anker | Wirkung |
|---|---|---|
| Auswahllisten (`kennungen()`) | `<select name="…"` | **laut** — leeres Verzeichnis, `KeyError` zwei Zeilen später |
| Geräteschlüssel (`stufe_geraet()`) | `Geräte-ID: <code>…</code>` | **laut** — und der Schlüssel wird genau einmal angezeigt, das Gerät war damit verloren |
| Geräte aufräumen (`stufe_geraet()`) | benachbarte `<input>`-Felder | **still** — räumte nichts; jeder Lauf ließ ein totes Gerät zurück, bis die Grenze von fünf je Konto erreicht war und `add` stumm scheiterte |
| Fehlermeldung lesen (3 ×) | `alert-danger` | **still** — abgelehnte Formulare liefen als Erfolg durch |

**Die stillen sind die schlimmeren.** Ein Werkzeug, das eine Fehlermeldung
nicht mehr findet, meldet nicht „ich finde nichts“, sondern „kein Fehler“. Das
ist dieselbe Sorte Lüge, die `tools/screenshots/LIESMICH.md` nach F-P3-AQ
beschreibt — nur an einer Stelle, an der sie noch niemand gesucht hatte.

**Behoben** in AP0, ausschließlich unter `tools/`:

1. Die Auswahllisten werden über das **öffnende Tag als Ganzes** gesucht
   (`<select … name="…">`), die Attributreihenfolge ist damit egal. Findet der
   Leser die Liste nicht, **bricht er ab**, statt ein leeres Verzeichnis
   zurückzugeben.
2. Das Aufräumen der Geräte hängt an der **Formularkennung** `f-devdel-<id>`,
   die die Gerätekennung selbst trägt.
3. Der Schlüsselkasten wird über `codeblock-titel`/`codeblock-wert` gelesen;
   scheitert das, nennt die Meldung zusätzlich den Fehlertext der Seite und
   sagt ausdrücklich, dass das angelegte Gerät unbrauchbar ist.
4. Die Fehlermeldung wird an **einer** Stelle gelesen (`fehlertext()`), nicht
   mehr an vieren — beide Schreibweisen, die heutige und die alte. Dieselbe
   Markup-Änderung kann damit nicht ein drittes Mal an vier Orten zuschlagen.

#### Und derselbe Fund noch einmal: der CSV-Kreislauf

Beim Regressionslauf nach AP0 (R24) stellte sich heraus, dass auch
`browser/kreislauf_csv.mjs` seit P3 nicht mehr durchläuft. Ursache derselbe
Umbau, andere Stelle: `ui_segment()` und `ui_schalter()` rendern das
Kontrollkästchen mit `position:absolute; opacity:0; width:0; height:0` und
daneben ein `<label for="…">` als sichtbare Taste. `page.check()` klickt aber
das **Feld** und wartet, dass es sichtbar wird — vergeblich; der Lauf endete
nach 30 s mit „element is not visible", an einer Stelle, an der die Anwendung
vollkommen in Ordnung ist.

Betroffen waren vier Aufrufe in `kreislauf_csv.mjs` und drei in
`referenz_export.mjs`. **Nicht** betroffen ist `#rcok` beim Setzen des
Passworts — ein gewöhnliches, sichtbares Kästchen; es bleibt unangetastet.

Behoben über **eine** gemeinsame Stelle: `browser/bedienen.mjs` mit
`ankreuzen()` und `abwaehlen()`. Sie nehmen den kurzen Weg, wenn das Feld
sichtbar ist, klicken sonst die Beschriftung — und **belegen danach**, dass
sich der Zustand wirklich geändert hat. Ein Klick, der ins Leere geht, würde
sonst einen Lauf mit falschen Einstellungen weiterlaufen lassen und am Ende
eine Abweichung melden, deren Ursache niemand mehr findet.

**Was daraus folgt, über den Fund hinaus.** Zwischen P3 und heute lag keine
Prüfung, die den Einspiellauf oder den CSV-Kreislauf angefasst hätte — der
Datensatz war da, also schien er in Ordnung. Er war es nicht: Er war nur nicht
mehr **herstellbar**. Ein Referenzbestand, den man nicht neu bauen kann, ist
ein Einzelstück und kein Prüfmittel. Der Messstand aus AP0 fährt den
Einspiellauf deshalb bei jedem Aufbau mit; damit kann derselbe Bruch nicht
wieder ein halbes Jahr unbemerkt bleiben.

**Und eine Regel für die Prüfmittel selbst:** Wer die Bausteine in `ui.php`
anfasst, ändert damit die Angriffsfläche jedes Werkzeugs, das die Oberfläche
liest oder bedient. Beide Kreisläufe gehören in denselben Durchgang — sie sind
schnell, und sie sind die einzige Stelle, an der so ein Bruch auffällt.

### F-S2-B — Ein Konto löschen lässt seine Positionsdaten liegen

**Gefunden** bei der Bestandsaufnahme der Löschwege, nachgeprüft am Schema und
an der laufenden Datenbank.

`admin_user.php` löscht ein Konto mit einem einzigen `DELETE FROM users` und
verlässt sich auf die Fremdschlüsselkaskade. Darüber steht der Kommentar:

> `// FK-Kaskaden entfernen Einsätze, Segmente, Tracks, Geräte, Diensttage`

**Für „Tracks" stimmt das nicht.** `track_points` ist polymorph
(`owner_type`/`owner_id`) und trägt deshalb **keinen** Fremdschlüssel —
weder in `schema.sql` noch in der laufenden Datenbank
(`information_schema.KEY_COLUMN_USAGE` kennt zu dieser Tabelle nur den
Primärschlüssel). Die Punkte bleiben als Waisen liegen und verschwinden erst,
wenn `run_cleanup_if_due()` das nächste Mal läuft — also frühestens am
nächsten Kalendertag, und nur dann, wenn überhaupt jemand die Installation
aufruft (`db.php`, der einzige Zeitgeber). Auf einer wenig besuchten
Installation kann das dauern.

**Warum das mehr ist als eine Aufräumfrage.** Was dort liegen bleibt, sind
Positionsdaten — Wohnorte, Einsatzorte, Wege. Ein Konto zu löschen ist die
Handlung, mit der eine Nutzerin genau das aus der Welt schaffen will. Dass
es bis zu einen Tag länger dauert, ist vertretbar; dass niemand es weiß, ist
es nicht — und der Kommentar hat dafür gesorgt, dass niemand es wusste. Er
ist die eigentliche Ursache dieses Fundes.

**Und der Messstand hat es prompt selbst vorgeführt.** Beim Aufbau der
Ausgangsmessung wurde das Messstandkonto zweimal gelöscht und neu angelegt.
Danach standen in `track_points` **9 460 316** Zeilen statt der erwarteten
3 257 385 — **6 202 931 verwaiste Spurpunkte** (4 647 366 an Einsätzen,
1 555 565 an Ruhesegmenten), rund **380 MB**. Zwei Kontolöschungen, und die
Positionsdaten lagen noch vollständig da. Der Wartungsjob hat sie
anschließend entfernt: **15,18 s für 6 202 931 Zeilen** — in genau der
Anfrage, die die nächste Nutzerin stellt.

**Behoben in AP1**, nicht erst in AP2: Das Paket stellt ohnehin alle
Löschwege auf `spur_lib.php` um, und dort einen Weg auszulassen wäre
unbegründet gewesen. Alle Löschwege rufen jetzt `spur_loeschen()` — Zeilen
**und** Blob, ausdrücklich und vor der Kaskade. Der Wartungsjob bleibt das
Sicherheitsnetz und deckt seit AP1 beide Tabellen. Der Kommentar ist
berichtigt; er darf nicht behaupten, was das Schema nicht hergibt.

### F-S2-C — Dieselbe Zahl, zwei Bedeutungen: 2000 Punkte

**Gefunden** beim Durchsehen der Schreibwege für AP1, nachgeprüft am Code.

`LIMIT_TRACKPUNKTE = 2000` (`validate_lib.php`) wird an **zwei** Stellen
angewandt, und sie meinen nicht dasselbe:

| Stelle | Bezugsgröße | Wirkung |
|---|---|---|
| `ingest.php` | die Punkte **einer Anfrage** | Ein Paket der Uhr trägt selten mehr als ein paar hundert Punkte; die Grenze greift praktisch nie. Über viele Anfragen wächst eine Spur **unbegrenzt**. |
| `backup_lib.php` (`$spurSchreiben`) | die Punkte **einer ganzen Spur** | Beim Zurückspielen wird alles jenseits des 2000. Punktes **verworfen**. |

Damit gilt: **Was die Uhr aufbauen darf, kann die Wiederherstellung nicht
zurückbringen.** Die Sicherungsdatei enthält die Spur vollständig — `edbak_build()`
kennt keine Grenze —, aber beim Einspielen bleiben die ersten 2000 Punkte
übrig und der Rest fällt weg. In ein frisches Konto eingespielt ist der
Verlust endgültig.

**Wann das greift.** Die Uhr dünnt selbst aus: mindestens 15 m Abstand,
höchstens alle 10 s, mindestens 1 s (`watch/source/Const.mc`). 2000 Punkte
sind damit je nach Fahrtgeschwindigkeit zwischen **33 Minuten** (dichteste
Aufzeichnung) und **5,5 Stunden**. Ein langer Verlegungsflug, ein
Bergrettungseinsatz oder ein durchgehend aufgezeichnetes Ruhesegment eines
Zwölfstundendienstes können darüber liegen.

**Warum es bisher niemand gesehen hat.** Der Referenzbestand hat als längste
Spur **1133 Punkte** — die Grenze wird dort nie erreicht, und damit auch
nicht vom Kreislauf (R24) und nicht von der Wiederherstellungsprobe (R27).
Ein Prüfbestand, der den Grenzfall nicht enthält, prüft ihn nicht.

**Ganz still ist es nicht:** `pruef_menge()` meldet „mehr als 2000 Einträge —
Rest verworfen", und die Meldung steht in `$stats['rejected']` der
Rückmeldung. Wer sie liest, erfährt es. Nur ist das die eine Stelle, an der
niemand nachsieht, weil das Einspielen ansonsten „fertig" meldet.

**Was daraus für S2 folgt.** Z4 sagt zu: „GPS-Spuren bleiben vollständig
erhalten; die Ausdünnung nach E-S2-05 ist eine entschiedene Präzisionsgrenze,
kein Verzicht." Eine Grenze, die eine Spur beim Zurückspielen **köpft**, ist
mit dieser Zusage nicht vereinbar — und sie würde in die Blobs
weitergeschleppt, wenn AP1 sie unbesehen übernimmt.

**Entschieden als F-S2-02 (31.08.2026), umgesetzt in AP1:** Die Grenze bleibt als
Schutz gegen eine entgleiste Nutzlast, wird aber auf einen Wert gehoben, der
eine echte Spur nicht mehr trifft, und sie wird **je Spur** einheitlich
angewandt — mit einer Ablehnung statt einer Kappung: Eine Spur jenseits der
Grenze wird ganz zurückgewiesen und benannt, statt halb übernommen zu werden.
Eine halbe Spur sieht aus wie eine ganze; eine abgelehnte sieht man.
Die Zahl selbst gehört in dieselbe Entscheidung — sie hängt daran, was der
Blob je Spur tragen soll (`MEDIUMBLOB`, 16 MB, das sind bei 3,56 B/Punkt rund
4,7 Mio. Punkte; die Grenze wird also nicht vom Format gesetzt).

### F-S2-02 — Was geschieht mit einer Spur über 2000 Punkten? (entschieden, 31.08.2026)

**Die Frage** stellt sich aus F-S2-C: `LIMIT_TRACKPUNKTE` gilt beim Upload je
Anfrage und beim Zurückspielen je Spur, und im zweiten Fall kappt sie.

**Gemessen** an den 526 Anfragen des Referenzlaufs: höchstens **500** Punkte je
Anfrage (die Uhr sendet in Stücken zu `UPLOAD_CHUNK_POINTS = 500`), höchstens
**1133** je Spur. Die Grenze wird vom Referenzbestand also nirgends erreicht —
deshalb ist sie nie aufgefallen. Erreichbar ist sie trotzdem:
`THIN_MAX_GAP_S = 10` garantiert einen Punkt alle zehn Sekunden,
`THIN_MIN_GAP_S = 1` erlaubt einen je Sekunde; 2000 Punkte sind damit zwischen
**33 Minuten und 5,5 Stunden** Aufzeichnung.

**Entschieden:** zwei Konstanten statt einer.

| Konstante | gilt für | Wert | Verhalten |
|---|---|---|---|
| `LIMIT_TRACKPUNKTE_ANFRAGE` | die Punkte einer Anfrage | 2000 | kappt und meldet (wie bisher) |
| `LIMIT_TRACKPUNKTE_SPUR` | die Punkte einer ganzen Spur | 50 000 | **lehnt die Spur ab** |

50 000 Punkte sind 13,9 Stunden bei einem Punkt je Sekunde — länger als jeder
Dienst. Als Blob rund 178 KB; `MEDIUMBLOB` trägt 16 MB. Die Zahl schützt vor
Unfug, nicht vor Betrieb.

**Und sie lehnt ab, statt zu kappen.** Eine halbe Spur sieht aus wie eine
ganze; eine abgelehnte sieht man. Dafür gibt es `pruef_menge_streng()` neben
`pruef_menge()` — die Meldung steht wie bisher in der Ablehnungsliste der
Rückmeldung.

---

## 9. Zuarbeiten

| Was | Wofür | Wann |
|---|---|---|
| Zugangsdaten je eines echten FTP-, FTPS- und SFTP-Ziels (Testverzeichnis genügt) | AP7-Abnahme | vor AP7 |
| Bestätigung, dass SMTP auf der Produktivinstallation eingerichtet ist (sonst Adminhinweis-Pfad prüfen) | AP6 | vor AP6-Abnahme |
| Freigabe je F-S2-Entscheidung während der Umsetzung (K6) und Deploy-Freigabe am Phasenende (K7) | laufend | laufend |

---

## 10. Umsetzungsstand

Fortschreibung durch die umsetzende Instanz (K1). Je Arbeitspaket: was ist
erledigt, welche Probleme sind aufgetreten, wie wurden sie gelöst, welche
Entscheidungen sind dabei gefallen — dazu ein Prüfstand.

### Prüfumgebung

Die Umsetzung läuft gegen eine **frisch aufgebaute lokale
Referenzinstallation**; die Referenzinstallation aus P1 steht dieser Instanz
nicht zur Verfügung. Der Container brachte weder Datenbank noch die
Python-Abhängigkeiten mit — beides gehört zum Aufbau und ist hier
festgehalten, damit der nächste Aufbau nicht wieder gesucht werden muss:

| Was | Fassung / Befehl |
|---|---|
| MariaDB | 10.11.14 (`apt-get install mariadb-server`) — dieselbe Hauptfassung, gegen die B-S2-02 gemessen wurde |
| PHP | 8.4.19 mit `zlib`, `mysqli`, `pdo_mysql`, `openssl`, `ftp`, `sodium` |
| Python | 3.11 mit `jsonschema` und `cryptography` (die Systemfassung von `cryptography` ist unbrauchbar — `pyo3_runtime.PanicException`; `pip install --ignore-installed --force-reinstall cryptography` legt eine brauchbare daneben) |
| Playwright | global unter `/opt/node22/lib/node_modules/playwright`, Browser unter `/opt/pw-browsers` |
| Hochfahren | `sh tools/referenzdatensatz/einspielen/lokal_starten.sh` (MariaDB, PHP-Server, TLS davor) |
| Einrichten | `install.php` über HTTPS, Admin `admin@gen-em.org` / `adminlokal2026` — die Vorgabewerte von `einspielen.py` |

Aufbau des Bestands über die regulären Wege nach
`tools/referenzdatensatz/LIESMICH.md`, „Die drei Läufe“:

| Schritt | Ergebnis |
|---|---|
| `quelldaten/pruefen.py` | 5680 Einzelprüfungen, 78 Matrixzeilen, 0 offen, keine Befunde |
| `generator/erzeugen.py` | 16 Dienste, 87 Einsätze, 100 Ruhesegmente, 56 587 erzeugte Spurpunkte, 526 Ingest-Anfragen |
| `generator/pruefen.py` | 283 990 Einzelprüfungen, keine Befunde |
| `einspielen.py` (alle Stufen) | 526 Ingest-Anfragen, **0 Fehler**, 0 mit verworfenen Einzelwerten; Sperrlisten-Prüfschritt bestanden |
| `browser/csv_import.mjs` | 4 Einsätze, 0 Hinweise, 0 Fehler, 0 Konsolenfehler |

**Bestand danach, nachgezählt:** 87 Einsätze (5 im Papierkorb), 100
Ruhesegmente (5 im Papierkorb), 16 Diensttage (1 im Papierkorb), **55 861
Spurpunkte**, 3 Geräte. Das ist exakt der in
`tools/referenzdatensatz/LIESMICH.md` beschriebene Referenzzustand und exakt
die Zahl aus B-S2-01 — der Datensatz ist reproduzierbar.

**Der Weg dorthin war nicht glatt:** Der Einspiellauf war seit P3 kaputt
(F-S2-A). Ohne diese Reparatur gäbe es keine Prüfumgebung, und damit weder
eine Ausgangsmessung noch eine Abnahme.

### Drei Befundzahlen des Konzepts, unabhängig nachgemessen

Bevor auf einer Zahl gebaut wird, wird sie nachgerechnet. Alle drei
Kernzahlen des Befunds reproduzieren sich am echten Bestand:

| Befund | Konzept | nachgemessen | Messweg |
|---|---|---|---|
| B-S2-02 Zeilenkosten | 40 MB je 1000 Einsätze | **40,5 MB** | `information_schema`: 3,52 MB Daten für 87 Einsätze + 100 Ruhesegmente |
| B-S2-07 Blobgröße | 3,33 B/Punkt | **3,32 B/Punkt** | SPUR1 nach 3.1.2 über alle 181 Spuren, 55 861 Punkte |
| E-S2-05 Ausdünnung | 37 % bleiben | **37,6 %** | Douglas-Peucker 3D, 2 m / 3 m, über alle 181 Spuren |
| E-S2-24 Stufe 3 | 1,1–1,2 MB je 1000 | **1,09 MB** | dieselbe Rechnung, Blob über die Behalteliste |

Auch B-S2-05 stimmt wörtlich: `server/db.php` löscht verwaiste Spurpunkte mit
`DELETE tp FROM track_points tp LEFT JOIN missions m …` über die ganze
Tabelle, in der Anfrage der ersten Nutzerin des Tages.

Die eine Zahl, die **nicht** aufging, ist die Rundlaufprüfung — daraus wurde
F-S2-01.

### AP0 — Messstand und Ausgangsmessung (erledigt)

**Erledigt.** `tools/messstand/` steht, dauerhaft im Repositorium (R35), mit
sechs Schritten unter einer Klammer (`messen.py`): Konto, Bestand,
Einspielen, Browserprobe, Serverprobe, Protokoll. Die Ausgangsmessung liegt
als `tools/messstand/ausgangsmessung.md` daneben, die Rohdaten unter
`/tmp/messstand/messprotokoll.json`.

**Der Bestand entsteht über die regulären Wege.** `vervielfaeltigen.py` öffnet
die Referenz-`.edbak`, vervielfältigt ihre Nutzlast mit versetzten Zeiten und
eigenen Kennungen und versiegelt sie wieder (Container Fassung 3, geschrieben
von `edbak.py`; gelesen wird mit dem vorhandenen `vergleich/lesen.py` — ein
Format, ein Leser). `einspielen.mjs` spielt die Dateien im Browser über
`einstellungen.php?t=backup` ein. Kein SQL, kein Sonderendpunkt.

**Abnahme erfüllt:** 5002 Einsätze, 5795 Ruhesegmente, 915 Diensttage,
3 201 524 Spurpunkte, hergestellt in 21 Dateien und **245,1 s**, gemeldet
**5002 von 5002 erwarteten** — reproduzierbar mit `python3 messen.py --frisch`.
Der Riegel gegen die Referenz steht in `einspielen.mjs` (nur Konten mit dem
Präfix `messstand`, fremde Adresse nur mit ausdrücklicher Umgebungsvariable)
und in `kreislauf.konto_loeschen()`, das dafür ein benennbares Präfix bekommen
hat statt eines zweiten Löschwegs.

#### Was schiefging — dreimal dieselbe Falle, im Prüfmittel selbst

Der erste Lauf meldete drei Zahlen, die etwas anderes maßen, als sie
behaupteten. Sie stehen hier vollständig, weil sie zusammen die eigentliche
Lehre dieses Pakets sind:

1. **„5046 Einsätze eingespielt" — angelegt waren 4744.** Addiert worden war
   die *erwartete* Zahl aus dem Verzeichnis des Vervielfältigers, nicht die
   *gemeldete* der Anwendung. Die Anwendung hatte korrekt berichtet
   („254 Einsätze übernommen, 7 übersprungen — Diensttag liegt hier im
   Papierkorb"); nur hat niemand hingesehen. **Ursache** war die Regel D1: Die
   Referenz trägt einen Diensttag im Papierkorb, vervielfältigt trägt ihn jede
   Runde, und ab Runde 26 landeten spätere Runden mit ihren aktiven Tagen auf
   den Papierkorbdaten früherer. **Behoben zweifach:** Der Vervielfältiger
   lässt gelöschte Einträge draußen (der Papierkorb ist Sache von R24/R27,
   nicht eines Mengenprüfstands), und `einspielen.mjs` liest jetzt die
   Rückmeldung und meldet jede Abweichung zur Erwartung.
2. **„167 MB Spuren je 1000 Einsätze" — richtig sind 38.** Geteilt worden war
   die Größe der Tabelle *aller* Konten durch die Einsätze *eines* Kontos.
   Behoben über den Umweg der Zeilenkosten, die Bezugsgröße steht jetzt in der
   Ausgabe.
3. **„Startseite 25,6 s, Tagesansicht 30,7 s" — richtig sind 1,4 s und 4,8 s.**
   `waitUntil: 'load'` wartete auf die in dieser Umgebung gesperrten
   OpenStreetMap-Kacheln. Gemessen war die Netzsperre des Containers, nicht die
   Anwendung. Behoben: Die Kacheln werden ausdrücklich abgewiesen, gewartet
   wird auf den **Inhalt** (Tagesliste im Markup, Spurlinie auf der Karte).
   Nebenbei fielen damit auch die „20 Konsolenfehler" weg — es waren
   ausschließlich Kachelmeldungen ohne Adresse, die am Filter vorbeikamen.

Dazu zwei kleinere Berichtigungen: `information_schema.table_rows` ist bei
InnoDB eine Schätzung und lag um den Faktor 2,8 daneben (jetzt `COUNT(*)`);
und `data_length` zählt den belegten, nicht den benutzten Platz (jetzt
wahlweise mit `OPTIMIZE TABLE` davor, sonst mit ausdrücklichem Hinweis).

**Die Lehre**, und sie gilt für die ganze Phase: Ein Prüfmittel ist gegen die
Falle aus `CLAUDE.md` 6 nicht sicherer als das, was es prüft. Jede Zahl dieses
Messstands benennt jetzt, **was** sie gemessen hat.

#### Nebenbefund: die Reparatur der Prüfmittel (F-S2-A)

Ohne sie gäbe es keine Prüfumgebung und keinen Regressionslauf. Sechs Stellen
an drei Werkzeugen, drei davon still. Siehe Abschnitt 8.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Referenzbestand herstellbar | `quelldaten/pruefen.py`, `generator/*.py`, `einspielen.py`, `csv_import.mjs` | 5680 + 283 990 Einzelprüfungen, 526 Ingest-Anfragen, **0 Fehler**; 87/100/16/55 861 wie in B-S2-01 |
| Großbestand herstellbar | `messen.py --frisch` | **5002 von 5002**, 21 Dateien, 245,1 s |
| Rundlauf des Containers | `edbak.rundlauf_pruefen()` je Datei vor dem Ablegen | 21 × versiegelt, geöffnet, verglichen — 0 Abweichungen |
| Browser unter Drossel 6× | `browserprobe.mjs` | 5 Schritte, **0 Konsolenfehler** |
| Serverzahlen | `serverprobe.py --optimieren` | 62,4 B/Zeile (Befund: 62), 38,07 MB/1000 (Befund: 40) |
| Wartungsjob echt gefahren | `serverprobe.py --wartung-fahren` | 6 202 931 Zeilen in **15,18 s** |
| R24 Kreislauf `edbak` | `kreislauf.py --art edbak --frisch` | 286 739 Einzelvergleiche, **0 unerklärt** (16 erwartet) |
| R24 Kreislauf `csv` | `kreislauf.py --art csv --frisch` | 8797 Einzelvergleiche, **0 unerklärt** (859 erwartet) |
| R27 Wiederherstellungsprobe | `php probe.php` | 30 Erwartungen, **0 nicht erfüllt** |
| R27 Papierkorb-Mischfall | `papierkorb_misch.mjs` | 15 Einzelprüfungen, 0 Konsolenfehler, **keine Befunde** |
| R28 Wortliste | `wortliste.py` | **0 Treffer / 0 ungenutzte Ausnahmen / 0 Fallen** |

**Nicht geprüft** (steht ausführlich in `ausgangsmessung.md`, Kopf): echte
Hardware statt CPU-Drossel · die Wiederherstellung unter Drossel · Z2 mit
300 000 Einsätzen · das Zeichnen der Kartenkacheln · Haldenspitzen zwischen
zwei Abtastungen.

#### Entscheidungen, die dabei gefallen sind

- **Der Papierkorb bleibt aus dem Großbestand draußen** (Begründung oben).
  Damit trägt jede Runde genau 82 Einsätze, 95 Ruhesegmente, 15 Diensttage —
  eine Zahl, die sich vorher ausrechnen und hinterher nachzählen lässt.
- **Kein Versionssprung.** AP0 ändert nichts unter `server/` und nichts an der
  Uhr; `WEB_VERSION` bleibt 9.14.0. Der Changelog-Eintrag trägt deshalb keine
  Versionsnummer — dasselbe Muster wie beim Uhr-Prüfstand (Werkzeug-Eintrag
  vom 30.08.2026).
- **Die Referenz-`.edbak` wird nicht neu gezogen.** Sie ist die Vorlage des
  Vervielfältigers und die Abnahmedatei für R11; AP0 fasst sie nicht an.

### AP1 — Spurbibliothek und Blob-Format SPUR1 (erledigt, Web 10.0.0)

**Erledigt.** `server/spur_lib.php` steht als einzige Stelle, die SPUR1 liest
und schreibt; die Tabelle `track_blobs` ist migriert
(`2026_08_31_spur_blobs`); alle sechs SQL-Lesestellen und alle Löschwege sind
umgestellt; `tools/spurprobe/probe.php` prüft den Rundlauf nach.

**Hauptversion 10.0.0**, weil sich das Datenmodell ändert und eine Migration
zwingend ist. Nach dem Ausrollen muss eine Administratorin `update.php`
aufrufen — ohne die Tabelle scheitert jeder Spurzugriff.

#### Was gebaut wurde

| Was | Wo |
|---|---|
| Kodieren, Dekodieren, Kopf lesen | `spur_kodieren()`, `spur_dekodieren()`, `spur_kopf()` |
| Lesen über beide Stufen, gebündelt | `spur_lesen_viele()`, `spur_lesen()` |
| Punktzahl ohne Auspacken | `spur_zahlen()` |
| Fortsetzungsmarke der Uhr | `spur_naechste_seq()` |
| Schreiben, Löschen | `spur_blob_schreiben()`, `spur_loeschen()`, `spur_loeschen_nur_zeilen()` |
| Umdatieren | `spur_min_ts()`, `spur_zeit_verschieben()` |
| Rundlaufprüfung | `spur_rundlauf_pruefen()`, `spur_quantisieren()` |

Die Ausdünnung (`spur_ausduennen()`) gehört zu AP3 und ist hier bewusst
**nicht** vorweggenommen: Sie braucht die Phasenpunkte des Einsatzes, und die
kennt die Bibliothek erst, wenn der Job sie ihr gibt.

#### Drei Dinge, die beim Bauen aufgefallen sind

**1. PHPs Ganzzahldivision hat die Rundlaufprüfung stillgelegt.** Der erste
Lauf meldete Abweichungen bei **175 von 181** Spuren, mit der Meldung
„Punkt 9: Höhe weicht ab (erwartet 780, gelesen 780)". Dieselbe Zahl, ein
anderer Typ: `7800 / 10` ist in PHP `int(780)`, `round(780.0*10)/10` ist
`float(780.0)`, und `!==` prüft den Typ mit. Beide Seiten werden jetzt
ausdrücklich zu `float`. Hätte die Prüfung nicht über den ganzen Bestand
laufen müssen, wäre das erst im Betrieb aufgefallen — als Verdichtungsjob, der
nie eine Zeile löscht.

**2. `sql_in_bloecken()` hat eine andere Signatur, als ich annahm.** Sie nimmt
die SQL-Vorlage mit `{IDS}` und gibt Zeilen zurück, statt Blöcke zu liefern.
Die Bibliothek benutzt jetzt den vorhandenen Weg — bis auf die `DELETE`, wo
die Funktion nicht passt (sie liefert Zeilen, keine Trefferzahl); dort steht
die Blockung von Hand, mit derselben Blockgröße und einer Begründung daneben.

**3. Und wieder eine Zahl, die etwas anderes maß.** Die Spurprobe prüfte
„nach der Verdichtung stehen keine Zeilen mehr da" mit
`SELECT COUNT(*) FROM track_points` — also über alle Konten — und schlug an,
weil daneben das Messstandkonto 3,2 Mio. Zeilen hält. Verdichtet wurde ein
Konto. Dritter Fall derselben Sorte in dieser Phase; die Prüfung zählt jetzt
die Zeilen ihres Kontos.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Rundlauf Punkte → Blob → Punkte | `spurprobe/probe.php` | 181 Spuren, **55 861 Punkte, 0 Abweichungen** |
| Blobgröße | dieselbe | **3,58 B/Punkt** gegen 62,4 als Zeile |
| Kopf ohne Auspacken lesbar | dieselbe | 181 Blobs, 0 abweichend |
| Fremde Formatfassung/Auflösung wird abgelehnt | dieselbe | 3 Prüffälle |
| Leser vor/nach Verdichtung gleich | dieselbe | 0 Spuren abweichend |
| `edbak_build()` vor/nach Verdichtung gleich | dieselbe | Paket 2,42 MB, gleich |
| `next_seq` überlebt die Verdichtung | dieselbe | 0 abweichend |
| Löschweg lässt weder Zeile noch Blob | dieselbe | erfüllt |
| Tagesansicht/Einsatzansicht über HTTP | `api/day.php`, `api/mission.php` | 8 Antworten **byteweise gleich** |
| CSV- und GPX-Export aus Blobs | `vergleichen.py --art csv` | 9589 Vergleiche, **0 Abweichungen**, 171 GPX |
| Sicherung aus **vollständig verdichtetem** Konto | `vergleichen.py --art edbak` | 286 739 Vergleiche, **0 unerklärt**, 16 erwartet |
| Kreislauf `edbak` (R24) | `kreislauf.py --frisch` | 286 739 Vergleiche, **0 unerklärt** |
| Dokumentiertes Python-Rezept gegen echten Blob | von Hand | 1133 Punkte, **0 Abweichungen** |
| Karten im Browser | Playwright | 13 Spurlinien / 2713 Stützpunkte, **0 Konsolenfehler** |
| Wortliste (R28) | `wortliste.py` | **0 / 0 / 0** |

**Nicht geprüft:** die Ausdünnung (Stufe 3 — gehört zu AP3, `spur_ausduennen()`
gibt es noch nicht) · das Verhalten bei einer Spur über 50 000 Punkten (der
Referenzbestand hat keine; die Grenze ist rechnerisch belegt, nicht gefahren)
· der Nachzügler-Fall über einen echten Uhr-Upload nach der Verdichtung
(`spur_lesen_viele()` setzt Blob und Zeilen zusammen, geprüft ist bislang nur
der Weg ohne Nachzügler) — beides gehört zu AP3.

#### Entscheidungen, die dabei gefallen sind

- **zlib-Stufe 9 statt 6.** 3,58 gegen 3,66 Byte je Punkt, 0,25 gegen 0,21
  Sekunden für den ganzen Referenzbestand. Verdichtet wird einmal im
  Hintergrund, gelesen wird oft.
- **Die Auflösung steht im Kopf**, nicht nur im Code (3.1.2). Sie ist eine
  Zusage; wer sie ändert, ändert die Bedeutung jedes bereits geschriebenen
  Blobs.
- **`spur_loeschen_nur_zeilen()` ist eine eigene Funktion** neben
  `spur_loeschen()`. Die beiden sehen sich ähnlich und meinen
  Gegensätzliches; wer sie verwechselt, löscht eine Spur, die bleiben sollte.
- **Das Referenzkonto bleibt verdichtet.** Es ist der Zustand, den AP3 ohnehin
  herstellt, und alle folgenden Prüfungen laufen damit gegen Blobs statt
  gegen Zeilen — die schärfere Probe.

---

### AP2 — Job-Einstieg und Wartung ohne Vollscan (erledigt, Web 10.1.0)

**Erledigt.** `server/jobs_lib.php` (Katalog und Ausführung),
`server/jobs.php` (Einstieg mit drei Auslösern), Tabelle `jobs`
(`2026_08_31_jobs`), Wartungsseite umgebaut, `run_cleanup_if_due()` auf den
Rahmen zurückgeführt, `tools/jobprobe/` als Probe.

**Nebenversion 10.1.0**, obwohl es eine Migration gibt: Weder Datenmodell im
Sinne der Nutzdaten noch die Wege durch die Anwendung ändern sich — `jobs`
hält Betriebszustand. Nach dem Ausrollen muss `update.php` trotzdem laufen,
sonst arbeitet kein Job mehr.

#### Was gebaut wurde

| Was | Wo |
|---|---|
| Zeitbudgets je Auslöser | `JOB_BUDGET_CLI/TOKEN/ANFRAGE` = 300 / 20 / 3 s |
| Mindestabstand am Huckepack-Weg | `JOB_ANFRAGE_PAUSE_S` = 300 s |
| Verfall einer verwaisten Sperre | `JOB_SPERRE_VERFALL_S` = 3600 s |
| Katalog | `jobs_katalog()` — `aufraeumen` (täglich), `waisen` (laufend) |
| Ausführung mit Sperre und Zustand | `jobs_lauf()`, `jobs_einen_lauf()` |
| Anzeige | `jobs_zustand()` → Karte „Hintergrundjobs" in `update.php` |
| Token | `jobs_token()`, Schlüssel `jobs_token` in `app_state` |
| Bereichsweise Waisensuche | `job_waisen()`, `job_waisen_rueckstand()` |

**AP3 hängt Verdichtung und Ausdünnung in `jobs_katalog()` ein.** Der Rahmen
kennt sie nicht; er kennt nur den Katalog. Das ist der Zweck dieses Pakets.

#### Vier Dinge, die beim Bauen schiefgingen — alle beim Messen aufgefallen

**1. Der Huckepack-Weg wäre zur Last geworden.** Der Job `waisen` ist nicht
täglich, sondern läuft, solange es Rückstand gibt. Damit lief er bei **jeder**
angemeldeten Anfrage, mit bis zu 18 s Budget — also genau die Krankheit, die
dieses Paket heilen soll, nur schlimmer. Das Gegenmittel sind zwei Zahlen:
3 s Budget und 5 Minuten Mindestabstand, beide nur für `anfrage`. Gemessen
kostet die eine fällige Anfrage jetzt 887 ms, jede weitere 0,5–1,3 ms.

**2. `job_waisen()` stand zweimal in der Datei**, einmal mit einem wörtlichen
`$1` als Platzhalter — ein Rest aus einem abgebrochenen Schreibvorgang. PHP
hätte das beim Laden mit „Cannot redeclare" quittiert; aufgefallen ist es
trotzdem erst beim ersten Lauf, weil bis dahin niemand die Datei geladen hat.
Neu geschrieben.

**3. Die Rückstandszahl war der Vollscan, den das Paket abschafft.** Die erste
Fassung von `job_waisen_rueckstand()` zählte „Eigentümer ohne Zeile in
`missions`" — also genau den Anti-Join, der weg sollte, nur jetzt bei jeder
Anzeige. Sie misst jetzt den Fortschritt der Marke, beide Abfragen auf dem
Primärschlüssel.

**4. Und wieder eine Reihenfolge.** Danach meldete der Job direkt nach einem
**vollständigen** Durchlauf „Rückstand 33093" — die ganze Tabelle als
ausstehend. Zwei Ursachen übereinander: Die Rückstandsfunktion las den Zustand
aus der Tabelle, wo noch der **alte** stand (geschrieben wird er erst danach),
und eine Marke von 0 war nicht von „noch nie gelaufen" zu unterscheiden. Jetzt
bekommt sie den frischen Zustand übergeben, und der Zustand hält zusätzlich
fest, **dass** der Durchlauf zu Ende kam (`durch`).

Punkt 4 ist derselbe Fehler wie in AP0 (die Serverprobe schrieb ihre Datei,
bevor die abgeleiteten Werte entstanden waren). Das Muster ist inzwischen
benannt: **Wer eine Kennzahl aus einem Zustand ableitet, muss sagen, welchen
Stand dieses Zustands er meint.**

#### Die ehrliche Zahl zum bereichsweisen Scan

Bei 3 313 246 Zeilen, je fünf Läufe, `memory_limit=64M`:

| | Dauer |
|---|---|
| Anti-Join über alles (alt, nur lesend) | **0,78–0,90 s** |
| bereichsweise, vollständiger Durchlauf (neu) | **0,85–1,05 s** |

**Der neue Weg ist bei dieser Menge nicht schneller, eher etwas langsamer.**
Das ist kein Nebenbefund, sondern der Kern: E-S2-18 verspricht keine
Geschwindigkeit, sondern **Begrenztheit** (Zeitbudget), **Fortsetzbarkeit**
(Marke in `jobs.zustand`) und die **Trennung vom Anfrageweg**. Bei Z2
(190 Mio. Zeilen) ist das der Unterschied zwischen „läuft nebenher" und „die
Seite hängt minutenlang". Wer beim Abschluss der Phase eine Aussage über die
Geschwindigkeit braucht, muss sie bei Z2 messen, nicht hier.

Speicherspitze: 2,0 MB bei `memory_limit=64M` — die Häppchengröße
(`JOB_WAISEN_BLOCK` = 2000 Kennungen) hält den Speicher unabhängig von der
Tabellengröße.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Katalog, Tabelle, Tagesgrenze, Sperre, Token | `jobprobe/probe.php` | **24 Erwartungen, 0 nicht erfüllt** |
| Alle drei Auslöser tragen denselben Rückstand ab | dieselbe | je **10 Zeilen + 1 Blob → 0 + 0** |
| Gemeldete Zahl nachgerechnet | dieselbe | 6 Zeilen + 1 Blob → **„erledigt 7"** |
| Rückstand nach vollständigem Durchlauf | dieselbe | **0**, `durch={mission:true,rest:true}` |
| Sperre: zweiter Lauf abgewiesen, verwaiste verfällt | dieselbe | beide erfüllt |
| Huckepack: Budget und Mindestabstand | dieselbe | 1,0 s ≤ 3 s; zweiter Aufruf übersprungen, 0,001 s |
| Nichts mit Eigentümer verloren | dieselbe | 3 313 246 → **3 313 246** |
| Vollständiger Durchlauf, Z3-Rahmen | von Hand, `memory_limit=64M` | **0,85–1,05 s**, Spitze **2,0 MB** |
| Vergleich gegen den alten Anti-Join | dieselbe Tabelle, nur lesend | **0,78–0,90 s** |
| HTTP: ohne / falsches / richtiges Token | `curl` | **403 / 403 / 200**, beide 403 in **0,351 s** |
| Ratenschutz am Token-Weg | `curl`, 12 Versuche | ab dem **10.** Fehlversuch **429** |
| Kommandozeile | `php jobs.php`, `--hilfe`, `jobs.php waisen` | Rückgabewert 0, Bericht je Job |
| Wartungsseite, acht Breiten 360–1920 px | `screenshots/aufnehmen.mjs --nur 45-` | 8 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Gegenprobe zum Bild (F-P3-AQ) | angesehen | zeigt die Karte „Hintergrundjobs" mit beiden Jobs und den Plaketten `anfrage` / `cli` |
| Spurprobe (AP1, unberührt) | `spurprobe/probe.php` | 14 Erwartungen, 0 nicht erfüllt |
| Kreislauf `edbak` (R24) | `kreislauf.py --art edbak --frisch` | 286 739 Vergleiche, **0 unerklärt** (16 erwartet) |
| Kreislauf `csv` (R24) | `kreislauf.py --art csv --frisch` | 8797 Vergleiche, **0 unerklärt** (859 erwartet) |
| Wiederherstellungsprobe (R27) | `wiederherstellungs-probe/probe.php` | **30 Erwartungen, 0 nicht erfüllt** |
| Wortliste (R28) | `wortliste.py` | **0 / 0 / 0** |

**Nicht geprüft — und das gehört an den Anfang, nicht in eine Fußnote:**

- **Das Verhalten bei Z2** (190 Mio. Zeilen). Alle Zeitangaben oben stammen von
  3,3 Mio. Zeilen. Dass der bereichsweise Scan dort gewinnt, ist gerechnet,
  nicht gemessen. Ein Messstand dieser Größe steht nicht zur Verfügung.
- **Ein Häppchen, das am Budget abbricht.** Bei diesem Bestand läuft der
  Waisenjob in unter einer Sekunde durch; der Fall „Budget erschöpft, Marke
  steht, nächster Lauf macht weiter" ist im Code angelegt und über die
  Marke belegt, aber nie mit echtem Abbruch gefahren. **Das ist die Zusage,
  auf der AP3 aufbaut** — dort muss sie mit echter Verdichtungsarbeit
  nachgewiesen werden.
- **Ein echter Cron.** Der CLI-Weg ist von Hand gefahren, nicht aus einer
  Crontab. Was ein Hoster mit Umgebungsvariablen, Pfaden und PHP-Fassungen
  anstellt, ist damit nicht abgedeckt.
- **Der Absturz mitten im Häppchen.** Der Verfall der Sperre ist geprüft,
  indem `laeuft_seit` zurückdatiert wurde — nicht, indem ein Lauf wirklich
  abgestürzt ist.
- **Zwei gleichzeitige Läufe aus zwei Prozessen.** Geprüft ist die Bedingung
  im `UPDATE` nacheinander, nicht unter echter Nebenläufigkeit.

#### Entscheidungen, die dabei gefallen sind

- **`jobs.php` lädt `auth_guard.php` nicht.** Der würde den Huckepack-Weg
  auslösen und den Job aus dem Job heraus starten.
- **Das Token liegt in `app_state`, nicht in `config.php`.** Die Anwendung
  schreibt diese Datei genau einmal; sie danach anzufassen hieße, auf jedem
  Webspace Schreibrecht auf die eigene Konfiguration zu brauchen — und
  Bestandsinstallationen hätten kein Token.
- **`laeuft_seit` ist ein Zeitstempel, kein Flag.** Ein abgestürzter Lauf
  würde ein Flag für immer stehen lassen, und der Job liefe nie wieder —
  stillschweigend.
- **Die Sperre ist ein bedingtes `UPDATE`**, nicht `SELECT`-dann-`UPDATE`.
- **Der Rückstand ist der Fortschritt der Marke, nicht die Waisenzahl.** Die
  richtige Zahl kostet den Vollscan, den dieser Job abschafft.
- **Der Ratenschutz am Token-Weg sperrt auch den richtigen Aufruf.** Zehn
  Fehlversuche je IP, dann zehn Minuten Ruhe — bewusst in Kauf genommen; die
  Alternative wäre ein Endpunkt, an dem sich ein Token durchprobieren lässt.
  Steht als Betriebshinweis in `docs/Technik.md` (Runbook).
- **`tools/jobprobe/` läuft NICHT in einer zurückgerollten Transaktion**,
  anders als die Spurprobe. Die Sperre ist auf `COMMIT` angewiesen; ein
  Rollback würde über sie nichts beweisen. Stattdessen eigene Waisen oberhalb
  aller vergebenen Kennungen, mit Aufräumen im `finally`.

---

### AP3 — Verdichtung und Ausdünnung (erledigt, Web 10.2.0)

**Erledigt.** `spur_ausduennen()` samt eigener Prüfung in `spur_lib.php`, zwei
neue Jobs (`verdichtung`, `ausduennen`), Spalte `letzter_punkt_am`
(`2026_09_01_letzter_punkt_am`), `ingest.php` nach E-S2-08, Zähler und Listen
auf der Wartungsseite, Laufpause für die Prüfmittel, `tools/ingestprobe/` neu
und `tools/spurprobe/` um zwei Teile erweitert.

#### Vier Entscheidungen, die vom Konzept abweichen — alle vorgelegt

Am 31.08.2026 vorgelegt und beantwortet:

| Frage | Entscheidung |
|---|---|
| Lohnt Stufe 3, wenn sie nur 28 % der Bytes spart und Stufe 2 E-S2-24 schon hält? | **Wie geplant bauen.** E-S2-03 bleibt: sechs Monate, Original danach weg |
| Woher kommt die Ankunftszeit für die Karenz (E-S2-06)? | **Neue Spalte `letzter_punkt_am`** auf beiden Eigentümertabellen |
| Konzept 3.1.4: Ausdünnung unmittelbar nach der Verdichtung? | **Trennen**, zwei Katalogeinträge; 3.1.4 ist hier fortgeschrieben |
| `next_seq`-Untergrenze allgemein oder nur nach der Ausdünnung? | **Allgemein** (neue Nummer E-S2-25) |

**E-S2-25 — `next_seq` hat allgemein die Untergrenze `seq_from` + Zahl der
gesendeten Punkte.** Alles unterhalb ist *erledigt* — gespeichert oder
endgültig verworfen. Das behebt einen vorhandenen Fehler: Scheiterte der
**letzte** Punkt eines Teilstücks an der Wertprüfung, meldete der Server eine
Marke kleiner als die Punktzahl des Pakets; die Uhr räumt aber erst bei
`next_seq >= pointCount` auf und sandte dasselbe Stück endlos. Der Preis, offen
gesagt: Ein an der Prüfung gescheiterter Punkt kann danach nicht mehr berichtigt
nachkommen — für jeden Punkt außer dem letzten galt das ohnehin schon.

#### Was gebaut wurde

| Was | Wo |
|---|---|
| Behalteliste rechnen | `spur_ausduennen()`, iterativ, abschnittsweise |
| Örtlicher Meterrahmen, Höhenanker, Abstandsmaß | `spur_ortsrahmen()`, `spur_hoehenanker()`, `spur_dp_abstand()` |
| Pflichtpunkte (erster, letzter, je Phase der zeitnächste) | `spur_schutzpunkte()`, `spur_schutzzeiten()` |
| Eigene Prüfung der Stufe 3 | `spur_ausduennung_pruefen()` |
| Obere Schranke der Rechenzeit | `spur_ausduenn_dauer_s()` |
| Stufe und Zahlen in einem Blick | `spur_stand()`, `spur_ist_ausgeduennt()` |
| Umriss für die Kandidatenwahl | `spur_umriss()` |
| Die Jobs | `job_verdichtung()`, `spur_verdichten_eine()`, `job_ausduennen()`, `spur_ausduennen_eine()` |
| Laufpause | `jobs_pause()`, `jobs_pause_bis()`, `php jobs.php --pause` |

#### Die drei Fallen der Ausdünnung — jede mit ihrer Zahl

**1. Zwei Toleranzen sind EIN Lauf.** `s = max(waagerecht/2 m, senkrecht/3 m)`,
behalten wenn `s > 1`. Zwei getrennte Läufe mit vereinigten Behaltelisten
erzeugen einen dritten Streckenzug, für den keiner der beiden etwas zugesagt
hat: gemessen **8,62 m waagerecht und 4,16 m senkrecht** bei zugesagten 2 und 3
— und sie behalten dabei *mehr* Punkte, sehen also nach der sicheren Wahl aus.
Gegenprobe zur Notwendigkeit der Höhentoleranz: rein zweidimensional liegt der
schlimmste verworfene Punkt **82,76 m** neben dem Höhenprofil.

**2. Pflichtpunkte sind Abschnittsgrenzen, keine Nachträge.** Global ausdünnen
und hinterher einfügen: **46 von 181** Referenzspuren betroffen, **11 mit
Zusageverletzung**. Abschnittsweise: **0**.

**3. Eine fehlende Höhe darf den Höhentest nicht stilllegen.** Die naheliegende
Regel („fehlt einem Sehnenende die Höhe, entfällt der Test") verliert im
Prüffall eine 150-m-Spitze vollständig — und eine Prüfung, die solche
Abschnitte überspringt, meldet dafür **0,0 m Verlust**. Stattdessen eine
Ankerreihe über die Zeit.

#### Douglas-Peucker ist quadratisch, und der Fall tritt ein

Gemessen für **eine** Zickzack-Spur: 2 000 Punkte 0,198 s · 5 000 1,219 s ·
10 000 4,340 s · 20 000 18,658 s · **50 000 114,50 s**. Die Häppchenbudgets
sind 3 / 20 / 300 s.

Das Bedrohliche daran ist nicht die Dauer, sondern die Art des Abbruchs: Ein
Zeitablauf oder eine Speichergrenze ist **kein `Throwable`**. Der `catch` im
Job-Rahmen fängt ihn nicht, `laeuft_seit` bleibt stehen, der Job ist eine
Stunde gesperrt — und stirbt dann wieder. Dauerhafter Stillstand mit
`letzter_fehler = NULL`.

Zwei Vorkehrungen, beide gemessen: ein Deckel auf die Abschnittslänge
(114,50 s → **2,40 s**; am Normalfall kostet er nichts und ist dort sogar
schneller) und ein iterativer Lauf, der immer die größere Hälfte auf den Stapel
legt (Stapeltiefe ⌈log₂ n⌉ = 16 statt 50 000; rekursiv wären es 38 MB
VM-Stapel gegen ein Z3-Budget von 64 MB).

**Beides fällt an einer Prüfung am Referenzbestand nicht auf** — dort ist die
größte erreichte Rekursionstiefe 23. Das ist der eigentliche Befund: Der
Referenzbestand kann diese Klasse von Fehlern nicht finden.

#### Zwei Funde beim Lesen, beide behoben

**`spur_loeschen_nur_zeilen()` löschte zu viel.** Ohne `seq`-Obergrenze nahm sie
alle Zeilen eines Eigentümers, auch die, die während des Laufs eintrafen: Der
Job liest die Punkte, `ingest.php` committet dazwischen einen Upload, der Job
löscht — ein `DELETE` ist ein *current read* und sieht auch das. Punkte, die in
keinem Blob stehen, verschwanden still und wurden mit „ok" quittiert. Die
Obergrenze ist jetzt verpflichtend.

**Die Ortshöhe konnte still verschwinden.** `compute_site_elevation()` läuft bei
jedem Speichern und schrieb bedingungslos, auch `NULL`. Auf Stufe 3 wurden die
behaltenen Punkte für die *damaligen* Phasenzeiten geschützt; eine berichtigte
Phasenzeit hätte die Höhe kommentarlos gelöscht. Auf Stufe 3 wird ein
vorhandener Wert deshalb nicht mehr durch `NULL` ersetzt — ein neu gefundener
sehr wohl. Auf Stufe 1 und 2 unverändert.

#### Der teuerste Fund: die Jobs ändern, was gerade gemessen wird

Der Kreislauf spielt eine Sicherung in ein frisches Konto und exportiert sie
sofort wieder. Die wiederhergestellten Einsätze sind alt, der Verdichtungsjob
hält sie für reif, und was älter als sechs Monate ist, wird ausgedünnt. Der
Vergleich misst dann nicht mehr „kommt zurück, was hineinging", sondern „hat
der Job dazwischen zugeschlagen".

Der erste Lauf nach AP3 war **sauber — aber nur zufällig**: Der Mindestabstand
des Huckepack-Wegs hatte gerade gegriffen. Nachgemessen verdichtete ein Lauf
ohne Pause **125 Spuren** des Umlaufkontos. Eine Zahl, die vom Zufall abhängt,
ist kein Beleg; deshalb `jobs_pause()`, im Kreislauf gesetzt und im `finally`
wieder aufgehoben, sichtbar auf der Wartungsseite.

Dieselbe Erfahrung noch einmal, teurer: Der erste Ausdünnungslauf auf dieser
Entwicklungsinstallation hat **25 Spuren des Referenzkontos** ausgedünnt —
unwiederbringlich. Die Ausdünnung ist genau dafür gebaut; sie unterscheidet
nicht zwischen Bestand und Messinstrument. Die Spurprobe überspringt seither
ausgedünnte Spuren und sagt, wie viele; das Referenzkonto steht seither auf
156 Stufe-2- und 25 Stufe-3-Spuren.

**Die Lehre daraus gehört ins Vorgehen der Phase, nicht nur in dieses Paket:**
Ab AP3 verändert die Anwendung ihren eigenen Bestand im Hintergrund. Jedes
Prüfmittel, das den Bestand misst, muss die Jobs vorher anhalten — sonst misst
es die Jobs mit.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Zusage der Ausdünnung, ganzer Bestand | `spurprobe` Teil 4 | 156 Spuren, 47 078 Punkte, **0 Verletzungen** von 2,0 / 3,0 m |
| Höhenermittlung bleibt möglich | dieselbe | **527 von 528** Phasen im ±300-s-Fenster, vorher ebenso viele |
| Was sie spart, in Byte | dieselbe | 3,73 → 2,75 B je Originalpunkt (**26,4 %** bei 59,3 % weniger Punkten) |
| Rechenzeit je Spur | dieselbe | langsamste **0,0030 s**; Schranke für 50 000 Punkte: 2,50 s |
| Gleichstand, keine Höhe, Höhenfalle, Deckel, `n_original` | `spurprobe` Teil 5 | 6 Erwartungen, 0 nicht erfüllt |
| Gegenprobe „global plus einfügen" | von Hand | 46 Spuren betroffen, **11 Verletzungen**, bis 8,62 / 4,16 m |
| Gegenprobe „nur zweidimensional" | von Hand | größte senkrechte Abweichung **82,76 m** |
| Uhr-Schnittstelle über echtes HTTP | `ingestprobe` | **24 Erwartungen, 0 nicht erfüllt** |
| — Nachzügler an Stufe 2 werden angenommen | dieselbe | stored 10, kein `dropped_points` |
| — Punkte hinter Stufe 3 verworfen **und** quittiert | dieselbe | dropped 10, stored 0, `next_seq` 220 |
| — gemischtes Paket an der Grenze | dieselbe | nur die 6 oberhalb `n_original` zählen |
| — Endlosschleife behoben | dieselbe | `next_seq` 3 statt 2 |
| — Ortshöhe überlebt eine berichtigte Phasenzeit | dieselbe | 805 vorher, 805 nachher; Gegenprobe Stufe 2 schreibt NULL |
| Verdichtungslauf am Messstand | von Hand, `memory_limit=64M` | **9395 Spuren in 44,3 s**, 2 936 497 Zeilen weg, Spitze **4,0 MB** |
| Ausdünnungslauf am Messstand | dieselbe | **4973 Spuren in 15,2 s**, Spitze 4,0 MB, `n_original` unverändert |
| Stufenverteilung danach | SQL | Stufe 2: 4603 Spuren / 3,90 B je Punkt · Stufe 3: 4973 / **2,24 B je Originalpunkt** (31,6 % behalten) |
| Blobgröße je 1000 Einsätze | SQL | **1,60 MB** (E-S2-24: ≤ 3 MB) |
| Lückenzähler | Prüffall | Zeile `seq=5` entfernt → Spur **nicht** verdichtet, `mission:10094` in der Liste, 444 Zeilen unberührt |
| Jobrahmen unberührt | `jobprobe` | 24 Erwartungen, 0 nicht erfüllt |
| Kreislauf `edbak` (R24) | `kreislauf.py --frisch` | 286 739 Vergleiche, **0 unerklärt** |
| Kreislauf `csv` (R24) | dieselbe | 8797 Vergleiche, **0 unerklärt** |
| Wiederherstellungsprobe (R27) | `probe.php` | 30 Erwartungen, 0 nicht erfüllt |
| Wartungsseite, acht Breiten | `aufnehmen.mjs --nur 45-` | 8 Bilder, **0 Überlauf / 0 Konsolenfehler / 0 Knöpfe ≠ 44 px**; Bild angesehen, zeigt die Lückenzeile mit `mission:10094` |
| Wortliste (R28) | `wortliste.py` | **0 / 0 / 0** |

**Nicht geprüft — und das gehört an den Anfang:**

- **Ein Häppchen, das wirklich am Budget abbricht.** Beide Jobs laufen bei
  diesem Bestand durch (44 s bzw. 15 s über CLI mit 300 s Budget). Der Fall
  „Budget erschöpft, Marke steht, nächster Lauf macht weiter" ist im Code
  angelegt und über die Marke belegt, aber nie mit echtem Abbruch gefahren.
  **Das ist dieselbe offene Zusage wie nach AP2** — sie steht jetzt zum zweiten
  Mal hier und sollte in AP10 herstellbar gemacht werden.
- **Echte Nebenläufigkeit** zwischen Job und Upload. Die `seq`-Obergrenze ist
  die Vorkehrung dagegen; ein Nebenläufigkeitsprüfstand existiert im
  Repositorium nicht.
- **Eine Spur mit mehr als 50 000 Punkten.** Der Bestand hat keine. Das
  Verhalten ist über die Umriss-Prüfung belegt, nicht gefahren.
- **Der volle Referenz-Sendeplan** (526 Anfragen) gegen den geänderten
  `ingest.php`. Der Einspiellauf ließ sich nicht wiederholen, ohne das
  Referenzkonto neu aufzubauen; `tools/ingestprobe/` fährt stattdessen gezielte
  Grenzfälle über denselben Endpunkt. **Die Mengenprobe steht damit aus.**
- **Ein echter Cron.**

Dazu eine Zahl, die sich verschoben hat und benannt gehört: Die
Vollständigkeitsprüfung meldet **233 statt 232** Befunde. Der Unterschied ist
ein Auslassungszeichen in einem neuen Hinweissatz — richtige Typografie, kein
Gestaltungsbefund. Der Zähler trennt Prosa und Symbole nicht; das steht seit
P3/O12 im Backlog (Nr. 42) und ist dort mit dieser Runde fortgeschrieben.
