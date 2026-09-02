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
  `spuren/NNNN.edbak` — *nach AP5b:* `manifest.edbak`, `kopf.edbak`,
  `eintraege/NNNN.edbak`, `spuren/NNNN.edbak`. Jedes Teil ist ein AES-GCM-Container wie bisher
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

  > **Nachtrag AP5b (31.08.2026).** Dieser eine Kern hat den Betrieb nicht
  > erlebt. Er ist beim 5000er-Bestand 10,5 MB und ginge auf dem Rückweg als
  > **ein** POST gegen ein Limit, das niemand kennt (nginx: 1 MB in der
  > Vorgabe). Er zerfällt deshalb in `kopf.edbak` (Stammdaten, Diensttage,
  > `eintraege_gesamt`) und `eintraege/NNNN.edbak` (je 250 Einträge). Der
  > Rest dieses Abschnitts gilt unverändert. Maßgeblich ist ab hier
  > `docs/Backup-Format.md` 1.
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
*Während der Umsetzung dazugekommen* (Rückfrage vom 31.08.2026, nicht Teil
der ursprünglichen Abnahme): eine **Mehrfachauswahl** auf der Spurenseite, die
die ausgewählten Spuren als eine Datei mit mehreren `<trk>` ausgibt.

**AP5 — Containerfassung 4: Sichern und Wiederherstellen.** Nach 3.2;
einschließlich Altformat-Lesepfad, Fortschrittsanzeigen, Kreislauf- und
Referenzpflege (3.2.5).
*Abnahme:* Kreislauf `edbak` gegen die neue Fassung-4-Referenz **0
unerklärt** (R24); R11-Abnahme: die einteilige 7.x-Referenz spielt in eine
frische Installation ein; Fassung-4-Datei mit fehlendem/vertauschtem Teil
wird mit verständlicher Meldung abgewiesen (AAD-Prüffall); Sichern und
Wiederherstellen des 5 000er-Kontos innerhalb der Zielzahlen E-S2-24 unter
Drossel; `tools/wiederherstellungs-probe/` grün auf beiden Formaten.

**AP5b — Der Kern in Fenstern** (kam bei der Abnahme von AP5 dazu, nicht
geplant). Der Kern der Fassung 4 ist beim 5000er-Bestand 10,5 MB und ginge
als **ein** POST zurück; er zerfällt in `kopf.edbak` und
`eintraege/NNNN.edbak`. Neuer Abrufweg `?teil=`, neuer Rückweg
`api/backup_eintraege_restore.php`, `unlesbar` im Manifest.
*Abnahme:* alle drei Kreisläufe **0 unerklärt**; größtes Fenster unter
0,5 MB gemessen; Serverspitze am Messstand gemessen; die Prüfmittel warten
auf die Meldung und nicht auf einen Wortlaut.

**AP6 — Admin-Sicherungen und Speicherverwaltung.** Nach 3.3; Grenze,
Schwellen, Warnmail (E-S2-15), Aufbewahrung 2 je Konto + manuell.
*Abnahme:* „Alle sichern“ über 20 Messstand-Konten läuft in Schüben mit
Fortschritt und Wiederaufnahme; Grenzfall erzeugt Ablehnung mit Meldung;
Schwellenüberschreitung erzeugt genau eine Mail (bzw. Adminhinweis ohne
SMTP).

> **„Manuell mehr je Konto" ist nicht umgesetzt.** Die Aufbewahrung ist eine
> Zahl für die ganze Installation (`app_state.adminbackup_aufbewahrung`); ein
> Wert je Konto hätte einen Ablageort gebraucht, den es nicht gibt — weder in
> `konto.json` noch als Spalte. Das steht hier statt in einer Fußnote, weil
> E-S2-14 es ausdrücklich nennt. Ein Paket lässt sich stattdessen einzeln vor
> der Verdrängung schützen, indem man es freigibt; das ist ein Umweg und kein
> Ersatz. **Backlog Nr. 48.**

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
| F-S2-D | Die Rückfrage vor dem Einspielen kam bei Fassung 4 immer — und die Prüfmittel bemerkten den Abbruch nicht | behoben in AP5b |
| F-S2-E | Eine Datei mit Nutzlast 8 **und** Punktlisten verlor alle Spuren, ohne ein Wort — und der Messstand schrieb genau solche Dateien | behoben vor AP6 |
| F-S2-F | Der Kasten „Für dich freigegebene Sicherung" war für niemanden zu sehen: eine Kennung, die es im Markup nicht gab, und ein `catch`, der alles schluckte | behoben in AP6 |
| F-S2-G | Das Konzept nennt die FTP-Gegenstelle „Transportziel" — dieser Name ist seit Web 4 vergeben (`transport_dests` = Zielklinik). Zwei Dinge, ein Wort, zwei Klicks voneinander entfernt | umbenannt in AP7: **Sicherungsziel** |
| F-S2-H | Ein abgebrochenes Häppchen des Dumps hätte beim nächsten Lauf ein zweites `DROP TABLE` derselben Tabelle in die Datei geschrieben — beim Einspielen wäre weggeworfen worden, was das erste Häppchen eingefügt hat | behoben in AP8 (der Zustand führt die Länge des gültigen Teils) |
| F-S2-I | Der Neuanlauf bei verschwundenem Baustand lief in ein `count(null)`; er ist genau der Zweig, der nach einer Wiederherstellung greift | behoben in AP8, gefunden von `tools/komplettprobe/` |
| F-S2-J | Die Schranke „leere Datenbank“ galt vor jedem Durchgang der Wiederherstellung — leer ist die Datenbank aber nur vor dem ersten. Abbruch bei 91 % mit der Meldung „Diese Installation ist in Betrieb“ | behoben in AP8 (die Schranke gilt fürs Anfangen) |

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

### F-S2-D — Eine Warnung ohne Anlass, und ein Prüfmittel, das sie nicht sah

**Gefunden** beim Abschluss von AP5b: Der Kreislauf `edbak` lief 300 Sekunden
und meldete nichts. Zwei Fehler übereinander, und der zweite verdeckte den
ersten.

**Der erste: eine Warnung, die im Regelfall kommt.** Vor dem Einspielen fragt
die Anwendung nach, wenn eine Sicherung Einsätze enthält, deren geschützte
Angaben schon beim *Erstellen* nicht zu entschlüsseln waren — die kommen hier
ebenfalls unlesbar an. Beim Altformat wird gezählt: `uebernommenFremd > 0`.
Bei Fassung 4 liegen die Einträge zum Zeitpunkt der Frage noch versiegelt in
ihren Teilen, und die Frage steht **vor dem ersten Schreiben** und muss dort
bleiben. Übrig blieb als Bedingung „die Datei stammt aus einem anderen Konto"
— und das ist der Regelfall des Einspielens.

Wer eine Sicherung aus einem anderen Konto einspielte, bekam also eine
Warnung vor einem Verlust, der nicht stattfindet: Die Angaben liegen im
Klartext in der Datei und werden gleich für dieses Konto neu verschlüsselt.

**Behoben,** indem der Erzeuger die Zahl mitschreibt: `unlesbar` im Manifest.
Er weiß sie — er hat sie beim Sichern gezählt. Fehlt das Feld, wird gefragt;
„nicht erhoben" ist etwas anderes als „keine".

**Der zweite: das Prüfmittel wartete auf Wörter.** `kreislauf_edbak.mjs`
brach seine Warteschleife bei `/fertig|eingespielt|fehlgeschlagen|Fehler|
falsch/` im Text von `#impstate`. Der Abbruchtext lautet „Abgebrochen — es
wurde nichts übernommen." und enthält keines dieser Wörter. Der Lauf wartete
deshalb seine vollen 100 Runden zu 3 s ab und ging danach zum Export über —
**aus einem leeren Konto**. Der Vergleich hätte den Unterschied gemeldet, aber
erst zwanzig Minuten später und als Datenbefund, nicht als das, was es war.

Dass die Rückfrage überhaupt unbeantwortet blieb, hat einen dritten Grund:
Beide Rückfragen des Sicherungsbereichs benutzten noch das **native**
`window.confirm`. Playwright weist native Dialoge stillschweigend ab. Das
stand zugleich im Widerspruch zur Zusage des Handbuchs („Alle Rückfragen
erscheinen als Fenster **innerhalb der Seite**") und zum Grund, aus dem es
`assets/confirm.js` gibt.

**Behoben, dreifach:**

1. Der Abbruch ist jetzt eine Meldung wie jede andere (`melde(…, 'warn')`),
   kein Fortschrittstext.
2. Die Prüfmittel warten auf **das Meldungselement** (`#impstate .meldung`)
   und lesen seinen Ton; nur `meldung-ok` gilt als bestanden. Umgestellt:
   `kreislauf_edbak.mjs`, `papierkorb_misch.mjs`, `messstand/einspielen.mjs`.
3. Beide Rückfragen laufen über `window.edConfirm`. Dass nichts eine dritte
   verhindert, steht als Backlog Nr. 47.

> **Die Lehre ist nicht „diese Wortliste war unvollständig".** Sie war es
> zweimal, und beim nächsten neuen Meldungstext wäre sie es wieder. Die
> Anwendung unterscheidet Zwischenstand und Ergebnis bereits im Markup; ein
> Prüfmittel, das stattdessen Wörter rät, prüft seine eigene Vermutung.

### F-S2-E — Nutzlast 8 mit Punktlisten: 91 208 Punkte, kein Wort

**Gefunden** beim Aufbau des Prüfbestands für AP6. Ein frisch gefülltes
Prüfkonto hatte 164 Einsätze und 190 Ruhesegmente — und **null Spuren**. Der
Einspiellauf hatte „164 übernommen" gemeldet.

**Zwei Fehler, und der eine verdeckt den anderen.**

*Der Auslöser liegt im Werkzeug.* `tools/messstand/vervielfaeltigen.py` baut
den Großbestand, indem es die Nutzlast der Referenz vervielfältigt und als
einteilige `.edbak` versiegelt. Die Fassungsnummer hat es dabei aus der
Referenz **geerbt**. Das ging gut, solange die Referenz Nutzlast 7 war; seit
Web 11.0.0 ist sie Fassung 4 mit Nutzlast **8**. Herausgekommen ist damit
etwas, das es nicht gibt: eine einteilige Datei, die `version: 8` nennt und
ihre Punkte trotzdem als `track` in den Einträgen trägt.

*Der Schaden liegt in der Anwendung.* `edbak_restore()` entscheidet zwischen
Verweisweg und Punktweg **an der Fassung** — mit gutem Grund, denn eine Spur
ohne Punkte sieht aus wie ein Verweis, und die umgekehrte Verwechslung würde
eine echte Fassung-8-Datei stillschweigend um alle Spuren bringen. Der
Kommentar an der Stelle sagt das auch so. Nur: Die Kehrseite war nicht
bedacht. Eine Datei mit Fassung 8 **und** Punktlisten lief in den Verweisweg,
fand keine `spur_ref` — und die Punkte fielen weg, ohne dass irgendetwas es
sagte.

**Gemessen:** 164 Einsätze angelegt, **91 208 Punkte verloren**, Meldung
„fertig". Nach der Behebung des Werkzeugs derselbe Lauf: **66 848**
Einsatzpunkte in der Datenbank (der Rest liegt an den Ruhesegmenten).

**Behoben, beidseitig:**

- Das Werkzeug **setzt** die Fassung, statt sie zu erben: Die Datei ist
  Nutzlast 7 und wird auch so ausgezeichnet.
- Die Anwendung **sagt es**. Der Verweisweg meldet eine Punktliste über die
  gemeinsame Prüfschicht — dort, wo die Nutzerin die Ablehnungen ohnehin
  liest. Abgewiesen wird die Datei nicht: Der übrige Bestand ist brauchbar,
  und ihn wegen der Spuren zu verweigern hieße, aus einem Teilverlust einen
  Totalverlust zu machen.

**Belegt** in `tools/wiederherstellungs-probe/`, Teil 7 — vier Erwartungen
samt Gegenprobe, dass eine richtige Fassung-8-Datei die Meldung **nicht**
bekommt.

> **Warum das hier steht und nicht nur im Changelog.** Eine Anwendung, die
> Daten verliert und „fertig" meldet, ist gefährlicher als eine, die
> abbricht. Dass der Weg hierher über ein Prüfwerkzeug führte, ist die zweite
> Auskunft: Backlog Nr. 46 hatte notiert, dass der Messstand am Altformat
> hängt — die Rechnung kam früher als gedacht, und sie kam still.

### F-S2-F — Die Freigabe war für niemanden zu sehen

**Gefunden** beim Prüfen des Freigabewegs für AP6. Eine Sicherung war
freigegeben, der Endpunkt antwortete richtig — und im Browser erschien nichts.

**Zwei Fehler, und der zweite hat den ersten unsichtbar gemacht.**

`freigabeLaden()` blendet die Frage nach dem Wiederherstellungsschlüssel aus,
wenn das Paket keine geschützten Angaben enthält:

```js
document.getElementById('freigabecodelabel').hidden = !d.freigabe.braucht_schluessel;
fgBox.hidden = false;
```

**Die Kennung `freigabecodelabel` gab es im Markup nicht.** `ui_feld()` vergibt
eine Kennung nur am Eingabefeld selbst (`freigabecode`), nicht an der Hülle
aus Beschriftung, Feld und Erklärung. `getElementById()` lieferte `null`, der
Zugriff warf — und die Zeile darunter, die den Kasten sichtbar macht, kam nie
zur Ausführung.

Der `TypeError` landete im `catch` von `freigabeLaden()`, und der war leer:

```js
} catch (e) {
  /* Still bleiben: Wer keine Freigabe hat, soll auf dieser Seite auch
     keinen Fehler über eine Funktion lesen, die ihn nichts angeht. */
}
```

Der Gedanke ist richtig. Nur hat der Block danach **jeden** Fehler geschluckt,
auch den, der die Funktion abschaltet.

**Wie lange.** Die Kennung steht seit Einführung des Freigabewegs im Skript
und war im Markup nie vorhanden — der Weg dürfte nie funktioniert haben.
Auffallen konnte es nicht: Die Karte ist im Regelfall verborgen (wer keine
Freigabe hat, soll sie nicht sehen), und *verborgen, weil es nichts gibt* sieht
genauso aus wie *verborgen, weil das Skript abgestürzt ist*.

**Was daran schwer wiegt.** Der Freigabeweg ist nicht Zierrat. Er ist der
**einzige** Weg, eine Sicherung mit geschützten Angaben in ein neu
aufgesetztes Konto zu bringen: Die Administration darf es nicht (E20), weil
sie den Inhaltsschlüssel nicht hat; nur die NutzerIn kann mit ihrem
Wiederherstellungsschlüssel umschlüsseln. Wer in diese Lage kam, bekam den
Kasten dafür nicht zu sehen.

**Behoben:**

- Die Hülle trägt jetzt die Kennung (`<div id="freigabecodelabel">` um den
  `ui_feld()`-Aufruf).
- Der `catch` bleibt still zur NutzerIn hin, schreibt aber in die Konsole.
  Damit fällt ein solcher Fall dem Bilderlauf und jeder Browserprüfung auf,
  ohne dass jemand ohne Freigabe etwas merkt.

**Belegt** im Browser: Freigabe eines Fassung-2-Pakets (600 Einträge,
3 Eintragsteile, 1 Spurteil) in ein frisches Konto — Kopf, drei Fenster, ein
Spurteil, **600 Einsätze mit 600 Spuren übernommen**, 0 Konsolenfehler,
Meldung in `meldung-ok`.

> **Die Lehre steht neben der von F-S2-D.** Dort hat ein Prüfmittel auf
> Wörter gewartet, hier hat ein `catch` einen Absturz zu einem leeren
> Bildschirm gemacht. Beide Male war der Fehler nicht, dass etwas kaputt war,
> sondern dass nichts es gesagt hat.

### F-S2-G — Zwei Dinge unter einem Wort: „Transportziel" (entschieden, 01.09.2026)

**Gefunden** beim Beginn von AP7, vor der ersten Zeile Code. E-S2-22 spricht
von „Transportzielen FTP, FTPS, SFTP". Diesen Namen trägt in dieser Anwendung
seit Web 4 aber schon etwas anderes: die Tabelle `transport_dests` — die
**Zielklinik einer Patientin**, gepflegt unter *Stammdaten systemweit*, gefiltert
in der Suche, ausgewiesen im Export.

**Warum das nicht bloss ein Wortstreit ist.** Beide Dinge leben im
Adminbereich, zwei Klicks voneinander entfernt. Eine Meldung wie „Das
Transportziel liess sich nicht speichern" hätte danach zwei mögliche
Bedeutungen, und dieselbe Zweideutigkeit steckte in jeder Überschrift, jedem
Handbuchsatz und jedem Backlog-Eintrag der nächsten Jahre. Das ist die Art
Verwechslung, die man nachträglich nicht mehr auflösen kann, weil beide
Bedeutungen bereits in Texten stehen.

**Entschieden:** Die Gegenstelle heisst **Sicherungsziel** — in der
Oberfläche, im Code (`sicherungsziel_lib.php`, Präfix `sz_`) und in der
Dokumentation. Die Tabelle heisst `backup_targets`, nicht `transport_dests`.
Die Konzeptvorgabe E-S2-22 bleibt inhaltlich unangetastet; nur ihr Wort wird
ersetzt. Die Zielseite sagt es zusätzlich in ihrer Unterzeile, und das
Handbuch tut es auch — wer beides kennt, soll es nicht erst durch Ausprobieren
auseinanderhalten müssen.

**Die Alternative wäre gewesen**, das ältere Ding umzubenennen (Zielklinik).
Sie schied aus: `transport_dests` steht in Stammdaten, Suche, Export, Import,
API und Uhr-Vertrag; eine Umbenennung dort wäre ein Vielfaches an Änderung
gewesen, und zwar an Wegen, die mit AP7 nichts zu tun haben.

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

---

### AP4 — GPX-Abruf (erledigt, Web 10.3.0)

**Erledigt.** `server/gpx_lib.php` als einzige Stelle, die GPX schreibt,
`server/gpx.php` als Abruf (einzeln **und** als Auswahl), ein Eintrag im
Aktionsmenü der Einsatzansicht, die neue Seite `server/tag_spuren.php` für
Einsätze **und** Ruhesegmente, `tools/gpxprobe/` samt vendoriertem
GPX-1.1-Schema. Keine Migration. Backlog Nr. 3 ist damit erledigt.

#### Drei Entscheidungen, alle vorgelegt

| Frage | Entscheidung (31.08.2026) |
|---|---|
| Ruhesegmente haben in der Oberfläche keine Identität — wo steht ihr Abruf? | **Eine eigene Seite je Diensttag**, erreichbar aus dem Aktionsmenü des Tages: Karte plus Liste aller Spuren, nummeriert, mit der Karte verknüpft, einzeln herunterladbar |
| Das amtliche GPX-XSD war aus der Arbeitsumgebung nicht erreichbar | **topografix.com ist freigegeben** — das Schema ist vendoriert, der Schemalauf ist gebaut |
| Lässt sich eine Mehrfachauswahl einbauen, die die Spuren kombiniert ausgibt? | **Ja, gebaut** — ein Kästchen je Zeile, eine Sammelleiste, eine Datei mit mehreren `<trk>`. Nachgereicht kam die Frage, ob die Liste nicht besser chronologisch stünde als nach Art gruppiert: **ja**, beide Reihenfolgen sind jetzt dieselbe |

Die zweite Antwort hat einen Abnahmepunkt gerettet: „GPX validiert gegen
Schema" wäre sonst nicht erfüllbar gewesen. Bezogen wurde es von
`https://topografix.com/GPX/1/1/gpx.xsd` — **ohne `www.`**; mit `www.` wies der
Proxy die Verbindung ab.

#### Wo die Datei entsteht — und warum das eine Architekturfrage war

Bis Web 10.2.0 entstand **jede** Datei, die auf der Platte einer Nutzerin
landet, im Browser aus einem Blob. Kein Zufall: Ihr Inhalt ist Ende-zu-Ende
verschlüsselt, der Server **kann** ihn nicht zusammensetzen.

Für eine Spur gilt das nicht. Drei Gründe für den Server:

1. Er hat die Punkte im Klartext **und** die Stufe (`spur_stand()`). Der
   Browser hat beides nicht — `api/mission.php` liefert bloße Paare
   `[lat, lon]`, ohne Höhe, ohne Zeit, ohne Stufe.
2. Ein browsergebautes GPX bräuchte also einen neuen, breiteren Abrufweg, nur
   um danach zusammenzusetzen, was auf dem Server schon beieinander liegt.
3. **Der Dateiname.** Serverseitig gebaut *kann* er keine geschützte Angabe
   tragen — der Server kann Diagnose, Alter und Einsatzort nicht lesen.
   Browserseitig gebaut könnte er es, und das wäre ein neuer Weg, auf dem
   Klartext das Haus verlässt.

#### Die Falle, die kein Parser meldet

GPX 1.1 beschreibt die Kindelemente als `xsd:sequence`, nicht als
`xsd:choice`. `<desc>` steht in `<metadata>` **vor** `<time>` und in `<trk>`
zwischen `<name>` und `<trkseg>`. Wer sie hinten anhängt, schreibt eine Datei,
die wohlgeformt ist, die manche Programme klaglos lesen — und die gegen das
Schema durchfällt. Genau das misst die Abnahme.

#### Mehrfachauswahl: mehrere `<trk>`, kein zusammengeklebtes `<trkseg>`

Die naheliegende Umsetzung wäre gewesen, die ausgewählten Punktfolgen
aneinanderzuhängen. Sie ist falsch, und der Fehler wäre nicht aufgefallen,
weil die Datei gültig bleibt: Jedes Kartenprogramm zöge eine gerade Linie vom
Ende der einen Spur zum Anfang der nächsten — quer über das Land, einen Weg,
den niemand gefahren ist. Auch mehrere `<trkseg>` in *einem* `<trk>` wären
falsch: Die meinen Abschnitte **einer** Aufzeichnung mit einer Lücke
dazwischen. GPX 1.1 erlaubt beliebig viele `<trk>` nebeneinander; das ist der
richtige Weg, und die Probe misst ihn ausdrücklich (3 `<trk>`, je ein
`<trkseg>`).

Vier weitere Entscheidungen:

- **Kein neuer Baustein.** Kästchen in `ui_zeile(['vorn' => …])`, Leiste als
  `ui_speichern_leiste()` — dieselben zwei Bausteine wie die Sammelaktion der
  NutzerInnen-Liste (P3/O9b). Kein neues CSS, keine neue Farbe.
- **Eine Zeichenstelle für zwei Zustände.** Auf die Deckkraft der Linien
  wirken das Zeigen *und* die Auswahl. Zwei Funktionen, die beide daran
  drehen, überschreiben einander — nach einem Zeigen wäre die Auswahl von der
  Karte verschwunden. Es gibt deshalb *eine* Funktion, die beide Zustände
  liest; geprüft ist das im Browser, an den gemessenen Deckkräften aller 15
  Linien in drei Zuständen.
- **Eine Mengengrenze aus Speichergründen** (100), nicht aus Rechtsgründen:
  Die Datei entsteht vollständig im Arbeitsspeicher, weil ihre Länge in die
  Kopfzeile gehört. `gpx_bauen_viele()` nimmt dafür einen **Generator** und
  keine Liste — sonst lägen alle dekodierten Spuren gleichzeitig im Speicher
  (rund 4 MB je Spur, AP3). Gemessen: hundert Spuren der größten Art des
  Bestands (1063 Punkte) → 9,7 MB Datei, 23,4 MB Spitze von 64 MB.
- **Streng bei der Form, nachsichtig beim Bestand.** `mission-abc` → 400.
  Eine wohlgeformte Kennung, die nicht zu diesem Tag und Konto gehört, fällt
  beim Lesen heraus (sie kann aus einem alten Tab stammen); Dateiname und
  `<desc>` sagen, wie viele Spuren wirklich drin sind. Ausgeforscht wird dabei
  nichts, weil die Abfrage ohnehin auf `user_id` **und** `day_id` filtert.
  Bleibt nichts übrig: 404, und er zählt.

#### Chronologisch statt nach Art — eine Rückfrage, die recht hatte

Die Liste stand zuerst nach Art gruppiert: erst alle Einsätze, dann alle
Ruhezeiten. Das war keine Entscheidung, sondern die Reihenfolge der beiden
Abfragen im Code. Ein Diensttag verläuft aber in *einer* Folge — Ruhezeit,
Einsatz, Ruhezeit, Einsatz —, und zwei Gruppen zwingen dazu, zwischen ihnen
hin und her zu rechnen. Seite und Datei sortieren jetzt beide nach Beginn,
Art und Kennung; die laufende Nummer der Einsätze und die Farben der Karte
zählen weiter nur die Einsätze durch und bleiben unberührt.

**Die erste Fassung der Prüfung hätte den Unterschied nicht gesehen:** Im
Prüfkonto lagen alle Ruhesegmente hinter allen Einsätzen, dort sieht eine
gruppierte Liste genauso aus wie eine chronologische. Die Probe legt jetzt
eigens ein Ruhesegment **vor** dem ersten Einsatz an.

#### Beinahe: Git hätte das vendorierte Schema verändert

Beim Einchecken meldete Git `CRLF will be replaced by LF` für
`tools/gpxprobe/gpx11.xsd`. Die `.gitattributes` setzen `* text=auto eol=lf`,
das Schema hat 788 CRLF: 26 665 Byte wären zu 25 877 geworden, und die
Prüfsumme, mit der die Probe **jeden Lauf** beginnt, hätte nicht mehr
gestimmt.

**Warum das hier niemandem aufgefallen wäre:** Die Arbeitskopie bleibt, wie
sie kam. Auf diesem Rechner wäre die Erwartung weiter grün gewesen; rot erst
auf dem nächsten frisch geklonten Arbeitsplatz — also dort, wo niemand mehr
weiß, woher die Datei kam. Dieselbe Sorte Fund wie die farblose Plakette
darunter: eine grüne Zahl, die etwas anderes misst, als ihre Beschriftung
sagt. Behoben mit `tools/gpxprobe/gpx11.xsd -text`, nachgerechnet über
`git checkout-index` in ein leeres Verzeichnis.

#### Ein Fehler aus AP2 und AP3, gefunden beim Lesen

**`.plakette-warn` gibt es im Stylesheet nicht.** Gültig sind `neutral`,
`orange`, `blau`, `rot`; der Ton `warn` wurde an drei Stellen übergeben, zwei
davon aus dieser Phase (Rückstand der Jobs, Zählerlisten). Die Plaketten
standen ohne Hintergrund da, als bloßer Text.

Zwei Dinge daran sind der eigentliche Befund:

- **Das Prüfmittel kann es nicht finden.** Der Klassenname wird
  zusammengesetzt (`'plakette-' . $ton`) und taucht als Literal nirgends auf.
  `tools/vollstaendigkeit/` kennt Klassen im Markup und Klassen im Stylesheet
  — keine, die zur Laufzeit entstehen. Backlog Nr. 36 beschreibt dieselbe
  Lücke seit P3/O6 von der anderen Seite und ist um diesen Fall erweitert,
  samt einem billigen Sonderweg: Die Bausteine mit geschlossenem Wertevorrat
  kennen ihre erlaubten Werte selbst.
- **Ich hatte das Bild angesehen und es übersehen.** Der Screenshot der
  Wartungsseite lag mir in AP2 vor, die farblose Plakette war darauf zu sehen.
  Ein Bild anzusehen ist nur dann eine Prüfung, wenn man weiß, wonach man
  sieht. Das ist die Grenze des Bilderlaufs, und sie gehört neben seine
  grünen Zahlen.

#### Was die Gegenprobe gefunden hat

Ein Workflow hat den Entwurf aus vier Blickwinkeln gegengelesen (Datenabfluss,
Rechte, stille Verfälschung, Projektregeln). Vier Befunde waren zu
entscheiden:

| Befund | Entscheidung |
|---|---|
| Der Export verweigert Spurpunkte ohne den Haken „personenbezogene Angaben" (A9) — der Abruf nicht | **Verneint.** Der Abruf hat keine anonyme Fassung; es gäbe keinen Haken zu umgehen |
| Der Abruf braucht keinen Inhaltsschlüssel, obwohl die Seite „Einsatzort bleibt verborgen" verspricht | **Verneint.** Die Karte derselben Seite zeigt die Spur bereits ohne Schlüssel. Eine Sperre wäre Theater; die Ursache ist Backlog Nr. 43 |
| Kein Ratenschutz, keine Mengenbremse | **Behoben.** Ratenschutz im Topf `pair`, aber nur auf Fehlgriffe |
| Der Link zeigt als erster der Oberfläche nach `api/` — Sitzungsende ergäbe JSON statt Anmeldeseite | **Behoben.** Der Abruf liegt jetzt neben den anderen Seiten |

Dazu zwei Nachbesserungen am Text, beide zu Recht angemahnt: Der Eintrag in
der Einsatzansicht fragt vor dem Herunterladen zurück (der große Export tut
das schon), und der Hinweis über der Liste nennt jetzt auch Ruhespuren — sie
zeigen den Aufenthalt der Besatzung zwischen den Einsätzen, und davon stand
dort nichts.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Gültig gegen das amtliche GPX-1.1-XSD | `gpxprobe` Teil 0/3/6 | Stufe 2, Stufe 3, Ruhesegment — **je gültig** |
| Prüfsumme des vendorierten Schemas | dieselbe, bei jedem Lauf | `9e4d1988…`, 26 665 Byte |
| Punkt für Punkt gegen die browsergebauten Referenzdateien | `gpxprobe` Teil 2 | **146 Dateien, 174 804 Einzelvergleiche, 0 Abweichungen** |
| Sind die Referenzdateien selbst schemagültig? | dieselbe | **171 Dateien, 0 ungültig** |
| Punktzahl entspricht der Stufe | `gpxprobe` Teil 3 | 300 von 300 · **56 von 300** |
| Kennzeichnung in der Datei | dieselbe | je 2 von 2 (`<metadata>`, `<trk>`) |
| Kennzeichnung im Dateinamen | dieselbe | `…_original.gpx` / `…_ausgeduennt.gpx` |
| Kennzeichnung auf der Seite | `gpxprobe` Teil 5 | Stufe und Punktzahl stimmen mit der Datei überein |
| Ohne Spur kein toter Menüeintrag | dieselbe | „Spur als GPX" fehlt — richtig |
| Datentrennung | `gpxprobe` Teil 1/4/6 | unangemeldet 302 · fremder Einsatz **404 statt 403** · Papierkorb 404 · fremder Diensttag 404 |
| Grenzfälle | `gpxprobe` Teil 4 | keine Spur → 404 statt leerem GPX · unbekannte Art 400 · Kennung 0 400 |
| Ruhesegment über die Spurenseite | `gpxprobe` Teil 6 | eigener Abruf, schemagültig, `ruhezeit_…gpx` |
| Auswahl aus drei Spuren | `gpxprobe` Teil 7 | schemagültig · **3 `<trk>`, je 1 `<trkseg>`** |
| Auswahl gegen die Einzelabrufe | dieselbe | **436 = 436 Punkte, 1744 Einzelvergleiche, 0 Abweichungen** |
| Kennzeichnung in der Auswahl | dieselbe | 1× ausgedünnt, 2× Original an den `<trk>`; der Kopf nennt beide |
| Reihenfolge auf der Seite | dieselbe | chronologisch, gegen die Datenbank — **eine Ruhezeit vor dem ersten Einsatz steht auch davor** |
| Reihenfolge in der Datei | dieselbe | gleich der Seite, gegen die Datenbank geprüft |
| Grenzfälle der Auswahl | dieselbe | Eintrag ohne Spur → kein leeres `<trk>` · fremde Kennung fällt heraus · nur fremde → 404 · `mission-abc` → 400 · > 100 → 400 · fremder Diensttag → 404 |
| Speicher bei 100 Spuren der größten Art | dieselbe | Datei **9,7 MB**, Spitze **23,4 MB von 64 MB** |
| Bedienzustand der Auswahl | Chromium, 1280 und 360 px | Leiste ab dem ersten Haken · „2 Spuren als eine Datei" · Download `diensttag_2026-05-10_2-spuren_original.gpx` mit **2 `<trk>`** · Deckkräfte in drei Zuständen richtig · 0 Konsolenfehler |
| Spurenseite, acht Breiten (360–1920 px) | `aufnehmen.mjs --nur 21a` | 8 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px**; Gegenprobe: 16 Bilder → 16 verschiedene Prüfsummen |
| Einsatzansicht, acht Breiten | `--nur 12-` | dieselben Nullen |
| Hexfarben, Pixelmaße, Schriftgrößen außerhalb der Token | `vollstaendigkeit` | **je 0** |
| Unter `api/` gibt es den Abruf nicht | `gpxprobe` Teil 4 | **404** — sonst wäre Sitzungsende JSON statt Anmeldeseite |
| Ratenschutz auf Fehlgriffe | dieselbe | 12 Fehlgriffe → **429**, gelungener Abruf zählt nicht |
| `gpxprobe/probe.php` gesamt | — | **75 Erwartungen, 0 nicht erfüllt** |
| Vendoriertes Schema nach simuliertem Klon | `git checkout-index` | **26 665 Byte, Summe `9e4d1988…`** — ohne die neue `.gitattributes`-Zeile wären es 25 877 |
| Wortliste (R28) | `wortliste.py` | 0 / 0 / 0 |

**Der Punkt-für-Punkt-Vergleich belegt mehr als der Schemalauf.** Ein Schema
sagt, dass die Datei richtig *aufgebaut* ist; es sagt nichts darüber, ob die
richtigen Punkte darin stehen. Zwei unabhängige Umsetzungen — PHP auf dem
Server, JavaScript im Browser —, die auf denselben Bestand dieselbe Datei
schreiben, sagen genau das.

**Nicht geprüft:**

- **Fremde Kartenprogramme.** Dass eine schemagültige Datei in QGIS, BaseCamp
  oder Komoot so aussieht wie gemeint, sagt kein Schema.
- **Andere Browser als Chromium.** WebKit (Safari, iOS) und Gecko stehen in
  dieser Umgebung nicht zur Verfügung.
- **Die Auswahl ohne JavaScript.** Die Kästchen sind ein gewöhnliches
  GET-Formular, aber die Sammelleiste blendet das Skript ein; ohne Skript
  bleibt der Einzelabruf. Nicht gemessen, weil kein Prüfmittel hier ohne
  Skript lädt.
- **Eine Spur über 50 000 Punkten.** Der Bestand hat keine.
- **Nebenläufigkeit** zwischen Abruf und Ausdünnungsjob. Die Probe hält die
  Jobs an, statt den Fall herzustellen.

### AP5 — Containerfassung 4 (erledigt, Web 11.0.0)

**Erledigt.** Container, Sichern, Wiederherstellen, Kreislauf, Dokumentation
und die Abnahme am 5000er-Bestand. Das Prüfdokument nach K9 gehört ans
Phasenende (AP10).

#### Vier Entscheidungen, alle vorgelegt

| Frage | Entscheidung (31.08.2026) |
|---|---|
| Trägt ein Teil weiterhin das Fassungsbyte `0x03` oder bekommt es `0x04`? | **`0x04`.** Die Zusage „AAD = die ersten 13 Bytes" stimmt für ein Teil nicht mehr; wer eines einzeln öffnet, bekäme sonst die Meldung für ein falsches Passwort |
| Base64 im Spurteil (Konzeptwortlaut) oder binär mit Längenpräfix? | **Base64.** Der Teil bleibt JSON und damit von Hand lesbar; der Aufschlag verschwindet größtenteils in der gzip-Schicht des Containers |
| Baut der Messstand seinen Großbestand in der Altfassung oder braucht es einen SPUR1-Kodierer in Python? | **Weder noch** — s. u. „Der Messstand bleibt, wie er ist" |
| Stufe-1-Spuren beim Sichern im Vorbeigehen kodieren, obwohl `Backup-Format.md` das Gegenteil zusagt? | **Ja, und die Zusage wird fortgeschrieben.** Ohne das müsste die Datei zwei Spurformen führen |

Dazu eine Richtungsentscheidung von außerhalb dieses Pakets: **Das Altformat
wird mit NaDoku 1.0 abgeschafft** (Backlog Nr. 46). Fassung 4 ist damit nicht
die Zweitform, sondern der Standard — die Prüfmittel werden auf sie gebaut.

#### Was der Kreislauf gefunden hat

Zwei Fehler, beide in dieser Umsetzung, beide von den Prüfmitteln und nicht
vom Lesen gefunden:

1. **`spur_umriss()['gesamt']` meint etwas anderes, als es hier gebraucht
   wurde.** Es ist die höchste Punktnummer plus eins — bei einer ausgedünnten
   Spur also die Zahl **vor** der Ausdünnung (443 statt 148). Der Kern hätte
   für jede ausgedünnte Spur eine Punktzahl genannt, die es in ihr nicht gibt.
   Aufgefallen beim Nachmessen gegen den Demo-Bestand, nicht beim Schreiben.
2. **Die Höhe des Einsatzortes fiel beim Wiederherstellen weg.**
   `compute_site_elevation()` rechnet sie aus der Spur; bei Nutzlast 7 lagen
   die Punkte in derselben Anfrage, bei Fassung 4 kommen sie erst danach.
   **79 von 87 Einsätzen** kamen ohne `site_ele_m` zurück. Kein Datenverlust
   — die Angabe ist abgeleitet —, aber ein stiller Unterschied zwischen
   Sicherung und Wiederherstellung, und genau die sucht ein Kreislauf.

Dazu eine Zahl, die etwas anderes maß als ihre Beschriftung: „Das ZIP packt
nicht noch einmal" verglich Dateigrößen und schlug fehl (+57,7 %). Bei drei
Teilen zu 500 Byte ist der ZIP-Rahmen größer als jede Ersparnis; gemessen war
der Rahmen. Jetzt wird das Verfahren je Eintrag gelesen (`0` = gespeichert).

#### Der Messstand bleibt, wie er ist — und warum das die richtige Wahl ist

Vorgelegt war die Frage, ob `vervielfaeltigen.py` künftig Fassung 4 baut. Die
erste Antwort darauf war „ja, mit wörtlich übernommenen Blobs". Beim
Durchdenken der Abnahme hat sie sich als überflüssig erwiesen:

Der Messstand **erzeugt** einen Bestand; **gemessen** wird das Sichern und
Wiederherstellen dieses Bestands. Der Weg dorthin ist der Altformat-Lesepfad —
und der muss bis NaDoku 1.0 ohnehin funktionieren, ist also selbst ein
Prüffall (R11). Die Kette lautet damit:

```
Altformat vervielfältigen  →  einspielen (Altweg, R11)
                           →  sichern (Fassung 4, GEMESSEN)
                           →  wiederherstellen (Fassung 4, GEMESSEN)
```

Beide gemessenen Schritte laufen in Fassung 4. Ein Python-Schreiber für den
Container wäre eine zweite Umsetzung des Formats, die bei jeder Änderung
mitgezogen werden müsste — für nichts, was er belegt.

**Was das kostet, gehört gesagt:** Der Messstand hängt damit am Altformat und
muss zum Stichtag NaDoku 1.0 umgebaut werden. Das steht in Backlog Nr. 46 bei
den Dingen, die dann fallen.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Container gegen drei unabhängige Umsetzungen | `containerprobe/probe.mjs` | **31 Erwartungen, 0 nicht erfüllt** |
| Punkt für Punkt PHP → Chromium → Python | dieselbe | **9000 Einzelvergleiche, 0 Abweichungen** |
| Die Bindung der Teile | dieselbe | vertauscht · falsche Nummer · fremde Sicherung · verfälschtes Byte · falsches Passwort — **je abgewiesen** |
| Gegenprobe zur Bindung | dieselbe | dasselbe fremde Teil geht **mit seiner eigenen Kennung** auf |
| Beide Sicherungen tragen einzeln | dieselbe | Prüfsumme allein: gefangen · Zusatzdaten allein (Manifest nachgezogen): gefangen |
| Schadensfälle am Archiv | dieselbe | fehlend · vertauscht · fremd · verfälscht · überzählig · ohne Manifest — **je benannt** |
| ZIP ohne Kompression | dieselbe | Verfahren je Eintrag **0**, alle vier |
| Base64 für 2 MB | dieselbe | 2 796 204 Zeichen im Rundlauf; der alte Wandler scheitert daran |
| **Kreislauf `edbak`** (Fassung 4 rein → raus) | `kreislauf.py --art edbak` | **252 882 Einzelvergleiche, 0 unerklärt** (16 erwartet) |
| **Kreislauf `edbak-alt`** (Altformat rein, R11) | `--art edbak-alt` | **287 282 Einzelvergleiche, 0 unerklärt** (560 erwartet, kein Spurpunkt darunter) |
| Kreislauf `csv` | `--art csv` | 8797, **0 unerklärt** (859 erwartet) |
| Der Rückweg schreibt Blobs, keine Zeilen | SQL im Umlaufkonto | 181 Blobs mit **48 981 Punkten**, **0 Zeilen** in `track_points` |
| Neue Datei gegen die alte Nutzlast | Punktvergleich | **244 905 Einzelvergleiche, 0 Abweichungen** |
| Sicherung des Demo-Bestands im Browser | Chromium | 87/100/16, 181 Spuren, 48 981 Punkte — **212,9 kB in 0,2 s, 0 Konsolenfehler** |
| Kern ohne Spuren gegen Kern mit Spuren | `edbak_build()` | **183 878 statt 2 248 092 Byte (8,2 %)** |
| Referenzdatei | Dateigröße | **218 KB statt 739 KB** (70 % weniger) |
| Das dokumentierte Python-Rezept | von Hand gegen die Referenz gefahren | öffnet Manifest, Kern und **181 Blobs** |
| spur/job/ingest/gpx/Wiederherstellung | die Proben | 25 · 24 · 24 · 75 · 30, **je 0 nicht erfüllt** |
| Wortliste (R28) | `wortliste.py` | 0 / 0 / 0 |

#### Die Abnahme am 5000er-Bestand — und was sie gefunden hat

**Der Kern hat es zuerst nicht geschafft.** Die Spuren waren gelöst, der Kern
nicht:

| | Kern | PHP-Speicherspitze |
|---|---|---|
| vorher (Punktlisten in der Nutzlast) | 94,3 MB | **1076 MB** |
| Fassung 4, erster Stand | 10,5 MB | **92 MB** |
| Fassung 4, nach dem Umbau | 10,5 MB | **37,5 MB** |

Mit `php -d memory_limit=64M` brach der erste Stand ab — auf einem Webspace
mit 64 MB wäre die Sicherung eines 5000er-Kontos gescheitert. Zwei Eingriffe,
**kein Formatwechsel**: je Eintrag kodieren und freigeben (92 → 75,5 MB), und
die vier Kindabfragen in Fenstern zu 500 statt über das ganze Konto
(75,5 → 37,5 MB). Die Abfragen bleiben gebündelt; das N+1 aus M5-12 kommt
nicht zurück.

> **Der große Umbau war nicht nötig.** Vorgelegt war die Frage, ob Fassung 4
> auch den Kern in Teile zerlegen muss. Die Antwort lautet nein, und sie ist
> gemessen: 37,5 von 64 MB. Bei etwa 8500 Einsätzen wäre die Grenze wieder
> erreicht — dann ist es die richtige Frage, und dann mit einer Zahl.

**Zwei Fehler, die erst dieser Bestand gezeigt hat:**

1. **Eine Mengengrenze an zwei Orten, die nicht zusammenpassten.** Der Browser
   bündelte die Spurteile nach *Größe* (1,5 MB), der Endpunkt deckelt nach
   *Anzahl* (500). Kurze Ruhespuren sind klein genug, dass in 1,5 MB weit mehr
   als 500 passen — das Einspielen brach ab. Der Browser bündelt jetzt nach
   beidem; dass die Zahlen gleich bleiben, hält die Wiederherstellungsprobe
   fest (Teil 6).
2. **Die Wiederaufnahme trug nicht** — der schwerere der beiden. Bricht das
   Einspielen zwischen Kern und Spurteilen ab, ist beim zweiten Anlauf jeder
   Eintrag „bereits vorhanden", und die Spurkarte blieb **leer**: Sie wurde
   nur beim Anlegen gefüllt. **10 431 Spuren** meldeten „ohne zugehörigen
   Einsatz" und wären nie mehr einzuspielen gewesen. Die Karte wird jetzt auch
   für übersprungene Einträge gefüllt; nachgewiesen mit einem nachgestellten
   Abbruch (4636 Spuren gelöscht → 4636 wieder eingespielt).

| Was | Mittel | Zahl |
|---|---|---|
| Sicherung des 5000er-Bestands, Drossel 6× | `browserprobe.mjs` | **43,6 s** · Halde **58 MB** · JSON **9,39 MB** · PBKDF2 **1** · Datei **10,42 MB** |
| dieselben Größen in der Ausgangsmessung (AP0) | | 109,8 s · 508 MB · 138,25 MB · 1 · 40,5 MB |
| Alle vier Z3-Überschreitungen aus AP0 | | **weg** |
| Serverseitige Spitze beim Kernbau | `memory_limit=64M` | **37,5 MB von 64** |
| Wiederherstellung des 5000er-Bestands | Kreislauf | 5002 / 5795 / 915 · **10 431 Spuren mit 2 108 077 Punkten in 9 Teilen** |
| Vergleich danach | `vergleichen.py` | **10 991 557 Einzelvergleiche, 0 unerklärt** |
| Wiederaufnahme | nachgestellter Abbruch | 4636 gelöscht → **4636 wieder eingespielt** |
| Wiederherstellungsprobe | `php probe.php` | **38 Erwartungen, 0 nicht erfüllt** (vorher 30) |

**Noch nicht geprüft** (steht hier und nicht in einer Fußnote):

- **Ein Abbruch mitten in einer Anfrage.** Der nachgestellte Abbruch löscht
  Spuren zwischen zwei vollständigen Läufen; ein Netzabriss mitten im POST ist
  etwas anderes.
- **Andere Browser als Chromium.** WebKit und Gecko stehen in dieser Umgebung
  nicht zur Verfügung.
- **Echte Hardware** statt CPU-Drossel.
- **Die Admin-Sicherungen.** Sie schreiben weiter das einteilige Format; das
  ist AP6.


### AP5b — Der Kern in Fenstern (erledigt, Web 11.1.0)

AP5 hatte die Punktlisten aus der Nutzlast geholt. Übrig blieb ein
`kern.edbak`, und der ist beim 5000er-Bestand 10,5 MB — auf dem Rückweg **ein**
POST gegen `client_max_body_size`, das bei nginx in der Vorgabe auf 1 MB
steht. Dieses Paket zerlegt auch ihn.

**Der Aufbau.** `kopf.edbak` (Stammdaten, Diensttage, `eintraege_gesamt`) +
`eintraege/NNNN.edbak` (je 250 Einträge, Einsätze **und** Ruhesegmente in der
Ordnung, auf die sich `spur_ref` bezieht) + die Spurteile wie bisher. Der
Abruf bekam `?teil=kopf` und `?teil=eintraege&ab=&anzahl=`, der Rückweg den
neuen Endpunkt `api/backup_eintraege_restore.php`; `api/backup_restore.php`
nimmt jetzt den Kopf und liefert die Zuordnung der Diensttage (`day_map`)
zurück, die der Server bei jedem Fenster **gegen das Konto prüft**, statt sie
zu glauben.

**Warum 250 und nicht 500.** Gemessen am 10 797-Einträge-Bestand: 500 ergeben
ein größtes Fenster von 0,87 MB — unter der Grenze, aber ohne Reserve; 250
ergeben 0,44 MB in 44 Anfragen. Der Endpunkt nimmt höchstens 1000 und weist
mehr mit 400 ab.

`kern.edbak` gibt es damit nicht mehr. Web 11.0.0 ist nie ausgeliefert worden;
beide Leser — Browser und `vergleich/lesen.py` — weisen ein `kern`-Teil
ausdrücklich ab, statt großzügig zu sein. Ein zweiter Leser, der mehr
akzeptiert als der erste, prüfte ein Format, das es nicht gibt.

#### Was dabei aufgefallen ist (nicht im Umbau)

1. **Eine Warnung ohne Anlass, ein Prüfmittel, das den Abbruch nicht sah, und
   zwei native `confirm()`** — drei Fehler übereinander, ausführlich als
   **F-S2-D** in Abschnitt 8. Kurz: Die Rückfrage vor dem Einspielen kam bei
   Fassung 4 im Regelfall; der Kreislauf verneinte sie stillschweigend und
   wartete danach 300 s auf Wörter, die es nicht gab. Behoben durch
   `unlesbar` im Manifest, durch Warten auf **die Meldung** statt auf einen
   Wortlaut (drei Werkzeuge umgestellt) und durch `window.edConfirm`.

2. **Die Exportschleife zählte nicht nach.** Sie rückt um `FENSTER` weiter,
   gleichgültig wie viel zurückkam; ein zu kurz geliefertes Fenster hätte
   Einträge aus der Datei fallen lassen, während die Meldung „Fertig" lautet.
   Der Browser zählt jetzt nach; die Wiederherstellungsprobe hält Fenstergröße
   und Endpunktgrenze zusammen.

3. **Die Containerprobe zählte Teilnummern ab.** `spuren/0001.edbak` war
   Teil 2 — solange vor den Spurteilen genau ein Teil lag. Sie leitet die
   Nummern jetzt aus der Teileliste ab und hat eine Gegenprobe bekommen: *an
   seinem richtigen Platz* geht dasselbe Teil auf. Ohne sie belegte jeder
   Fehlschlag die Bindung.

#### Eine Zahl war falsch weitergetragen worden

In der Begründung dieses Pakets stand zunächst „92 MB gegen ein Budget von
64". Beim Nachmessen am **tatsächlichen** Stand von Web 11.0.0 (Git-Worktree
auf `HEAD`) waren es **37,5 MB**: Die 92 MB stammen aus AP5 und beschreiben
den Stand *vor* den Fenstern der Kindtabellen. Der Umbau ist trotzdem richtig
— aber wegen des POST und wegen des Wachstums, nicht wegen eines Abbruchs, den
es nicht gab. Alle Stellen, die die Zahl trugen, sind berichtigt
(`api/backup_data.php`, `api/backup_eintraege_restore.php`, `version.php`,
`einstellungen.php`, `Backup-Format.md`).

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Speicher, Kern am Stück | `memory_get_peak_usage(true)` | Demo (187 Einträge) **4,0 MB** · Messstand (10 797) **39,5 MB** |
| Speicher, in Fenstern zu 250 | dasselbe, `memory_limit=64M` | Demo **4,0 MB** · Messstand **10,0 MB** |
| Fenstergrößen am Messstand | | Kopf 0,51 MB · 44 Fenster · größtes **0,44 MB** · Summe 9,96 MB |
| Kreislauf `edbak` (Fassung 4 → frisches Konto → Fassung 4) | `kreislauf.py` | **252 882** Einzelvergleiche, **0** unerklärt, 16 erwartet |
| Kreislauf `edbak-alt` (Altformat → Fassung 4) | `kreislauf.py` | **287 282** Einzelvergleiche, **0** unerklärt, 560 erwartet |
| Kreislauf `csv` | `kreislauf.py` | **8 797** Einzelvergleiche, **0** unerklärt, 859 erwartet |
| Containerprobe (PHP → Chromium → Python) | `probe.mjs` | **32 Erwartungen, 0 offen**; 9 000 Punktvergleiche; **zwei** Eintragsteile |
| Wiederherstellungsprobe | `php probe.php` | **40 Erwartungen, 0 offen** (vorher 38) |
| Spurprobe | `php probe.php` | **25 Erwartungen, 0 offen** |
| Neue Referenzdatei in der endgültigen Aufteilung | `referenz_export.mjs` | 218 735 B · 187 Einträge · 16 Diensttage · 181 Spuren · 48 981 Punkte · 0 Konsolenfehler |

#### Die Abnahme am 5000er-Bestand, in der neuen Aufteilung

Nicht nur die Serverzahlen: der ganze Weg über die Oberfläche, gegen das
Messstand-Konto (5002 Einsätze, 10 797 Einträge, 2 108 077 Spurpunkte).

**Sichern** (`browserprobe.mjs`, CPU-Drossel 6×):

| | AP0 (Ausgangsmessung) | AP5 (Web 11.0.0) | **AP5b** | Z3 |
|---|---|---|---|---|
| Dauer | 109,8 s | 43,6 s | **44,77 s** | ≤ 5 min |
| Halde im Browser | 508 MB | 58 MB | **45 MB** | ≤ 100 MB |
| größte JSON-Zeichenkette | 138,25 MB | 9,39 MB | **2,30 MB** | ≤ 10 MB |
| PBKDF2 je Vorgang | 1 | 1 | **1** | 1 |
| größte Antwort | — | — | **0,43 MB** | — |
| Datei | 40,5 MB | 10,42 MB | **10,48 MB** | ≤ 25 MB |

Die größte Zeichenkette fällt noch einmal auf ein Viertel: Sie ist jetzt ein
Fenster und nicht mehr der ganze Kern. Die Dauer bleibt gleich — 44 statt 43
Anfragen mehr kosten nichts Messbares.

**Serverseitig** (`serverprobe.py`, beide Wege getrennt gemessen, jeder im
eigenen Prozess):

| Weg | Dauer | Paket | Spitze |
|---|---|---|---|
| `edbak_build()` am Stück, **mit** Punktlisten (Admin-Sicherung, AP6) | 6,95 s | 94,28 MB | **1077,6 MB** |
| in Fenstern zu 250 (Sicherung der NutzerIn, ab Web 11.1.0) | 1,12 s | Kopf 0,51 MB, größtes Fenster 0,44 MB | **10,0 MB von 64** |

Die zweite Zeile ist neu in der Serverprobe. Ohne sie las sich das Protokoll,
als brauche jede Sicherung ein Gigabyte — das gilt seit AP5b nur noch für die
Admin-Sicherung, und genau das ist AP6.

**Einspielen und Rundlauf** (`kreislauf.py` gegen ein frisches Konto):

| Was | Zahl |
|---|---|
| Sicherung eingespielt | 5002 Einsätze · 5795 Ruhesegmente · 915 Diensttage · **10 431 Spuren** |
| erneut gesichert | 10 797 Einträge · 10 431 Spuren mit **2 108 077 Punkten** in **54 Teilen** · 10,5 MB |
| Vergleich | **10 991 557 Einzelvergleiche, 0 unerklärt, 0 erwartet** |
| Konsolenfehler | **0** |

54 Teile = 1 Kopf + 44 Eintragsteile + 9 Spurteile. Eine Regel der
Ausnahmeliste blieb ungenutzt (`days[].refs[].device_id`), und das ist
richtig: Das Quellkonto ist selbst durch Wiederherstellungen entstanden, seine
Diensttage tragen deshalb schon keine Gerätekennung mehr.

**Noch nicht geprüft** (steht hier und nicht in einer Fußnote):

- **Ein Fenster, das zu kurz zurückkommt.** Die neue Schranke ist durch Lesen
  und durch eine Prüfung auf ihr Vorhandensein belegt, nicht durch einen
  herbeigeführten Fall.
- **Ein Abbruch mitten in einer Anfrage** — unverändert wie in AP5.
- **Andere Browser als Chromium**, **echte Hardware** statt CPU-Drossel und
  **die Admin-Sicherungen** (AP6) — unverändert wie in AP5.

### AP6 — Admin-Sicherungen und Speicherverwaltung (erledigt, Web 12.0.0)

Die Admin-Sicherung war der letzte Weg, der das Budget sprengte — und zwar
nicht knapp. **Gemessen** am 5000er-Konto, vor dem Umbau:

| | |
|---|---|
| Dauer | 19,81 s |
| Paket | 94,28 MB |
| Speicherspitze | **1077,6 MB** |
| mit `memory_limit=64M` (Z3) | **Abbruch** in `spur_lib.php:218` |

Auf genau der Sorte Webspace, für die diese Anwendung gebaut ist, war die
Admin-Sicherung eines großen Kontos also **unmöglich**. Der Grund stand in
einer Zeile: `json_decode(edbak_build($userId), true)` — derselbe Bestand als
Zeichenkette, als Feld und beim Schreiben noch einmal als Zeichenkette.

#### Vor der Umsetzung: eine Bestandsaufnahme

Sechs Leser über `adminbackup_lib.php`, die Oberfläche, den Job-Rahmen, Mail
und Einstellungen, die Ablage und die Dokumentation; jeder Befund danach von
einem zweiten Durchgang gegengelesen, der ihn **widerlegen** sollte.
**43 Befunde hielten, 5 wurden verworfen.** Die vier Entscheidungen, die daraus
folgten, hat die Auftraggeberin am 31.08.2026 getroffen:

| Frage | Entscheidung |
|---|---|
| Aufbewahrung 2 (Konzept) oder 3 (Code)? | **2**, wie E-S2-14 sagt |
| Ablageform des mehrteiligen Rohpakets | **Ein unversiegeltes ZIP je Sicherung** |
| Vorhandene einteilige Pakete | **Beim ersten neuen Lauf ersetzen** |
| Zuschnitt | **Alles anfassen, was der Umbau berührt** |

#### Das Paket

`manifest.json` · `kopf.json` · `eintraege/NNNN.json` (je 250) ·
`spuren/NNNN.json`. Gepackt, anders als beim Nutzerformat — dort sind die
Teile bereits gzip *und* verschlüsselt, hier ist es blankes JSON.

**Ein Umweg, den erst die Messung erzwungen hat.** `ZipArchive::addFromString()`
hält jede übergebene Zeichenkette bis zum `close()` im Speicher; damit läge am
Ende doch wieder alles gleichzeitig da. Gemessen an 34,6 MB Inhalt, **je
eigener Prozess** (die Spitze ist prozessweit — im selben Lauf gemessen hätte
der zweite Wert nichts gesagt):

| | Inhalt | Spitze |
|---|---|---|
| `addFromString` | 34,6 MB | **42,0 MB** |
| `addFile` | 34,6 MB | **2,0 MB** |

Die Teile entstehen deshalb einzeln in einem Bauordner und gehen von dort ins
Archiv.

#### Was der Umbau erzwungen hat

- **`edbak_paket_lesen()` verweigert Fassung 2.** Ein mehrteiliges Paket am
  Stück zu lesen wäre dieselbe Spitze, nur beim Lesen. Eingespielt wird über
  `edbak_paket_einspielen()`; die Entscheidung *ob* liest nur das Manifest.
- **`geschuetzte` im Manifest.** `edbak_paket_hat_geschuetzte()` sah in die
  Einsatzliste des Pakets; im gefensterten Kern steht sie dort nicht mehr.
  Ohne die Zahl hätte die Funktion still `false` geliefert und die Sperre aus
  E20 ausgehebelt.
- **Der Freigabeweg läuft in Fenstern.** Er reichte das ganze Paket in *einer*
  Antwort heraus und nahm es in *einem* POST zurück.

#### Zwei Fehler, die dabei ans Licht kamen

**F-S2-F — die Freigabe war für niemanden zu sehen** (Abschnitt 8). Eine
Kennung, die es im Markup nie gab, und ein `catch`, der alles schluckte.

**Die Warteschlange passte nicht in die Spalte.** `app_state.v` ist
`varchar(190)`; die erste Fassung des Auftrags legte die Kennungen aller
offenen Konten hinein — bei 31 Konten schon 350 Zeichen. Das INSERT
scheiterte, `edbak_marke_setzen()` schluckte es, und die Schaltfläche meldete
**„0 von 0 Konten gesichert"**. Behoben in beide Richtungen: Der Auftrag ist
jetzt ein **Zeiger**, und die Marke sagt, wenn sie nicht schreiben konnte.

Damit fällt „älteste Sicherung zuerst" weg. Das war ohnehin keine Reihenfolge,
sondern ein Ersatz für den fehlenden Merkzettel — gerechnet wurde in *Tagen*,
und bei Gleichstand war sie beliebig. Zugesagt ist jetzt etwas Belastbareres:
**jedes Konto genau einmal**, und ein Abbruch verliert höchstens das laufende.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Admin-Sicherung, 5000er-Konto | `memory_get_peak_usage(true)` | 19,81 s · 1077,6 MB · 94,28 MB → **14,13 s · 24,0 MB von 64 · 11,42 MB** |
| dieselbe mit `memory_limit=64M` | | vorher **Abbruch**, jetzt **läuft durch** |
| Admin-Sicherung, Demokonto (187 Einträge) | | 28,1 → **4,0 MB**, 2,14 → **0,22 MB** |
| `ZipArchive`: addFromString gegen addFile | je eigener Prozess | 42,0 MB gegen **2,0 MB** bei 34,6 MB Inhalt |
| Rundlauf des Adminpakets | `wiederherstellungs-probe` Teil 8 | Sicherung → frisches Konto: Einträge, Spuren, Wiederaufnahme, fehlendes Teil |
| Speichergrenze und Schwellen | Teil 9 | Ablehnung mit Meldung, Reste zählen mit, ohne SMTP ein Hinweis |
| Auftrag „Alle sichern" | Teil 10 | Zeiger, Wiederaufnahme, Marke **64 von 190 Zeichen** |
| `wiederherstellungs-probe` gesamt | `php probe.php` | **76 Erwartungen, 0 offen** (vor AP6: 44) |
| „Alle sichern" über 31 Konten | Browser | **31 von 31** in 18,3 s, 0 Konsolenfehler |
| Freigabeweg, Fassung 2 | Browser | 600 Einträge · 3 Eintragsteile · 1 Spurteil → **600 Einsätze mit 600 Spuren**, 0 Konsolenfehler |
| Bilderlauf `34-` und `43-` | `aufnehmen.mjs` | 16 Bilder, **16 verschiedene Prüfsummen**, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |

**Noch nicht geprüft** (steht hier und nicht in einer Fußnote):

- ~~**Der Freigabeweg MIT Wiederherstellungsschlüssel.**~~ **Nachgeholt** mit
  `tools/freigabeprobe/` (01.09.2026): **14 Erwartungen, 0 offen.** Die Probe
  stellt sich ein Konto her, dessen Wiederherstellungsschlüssel sie kennt —
  Hülle, Prüfsumme und Chiffretext entstehen dabei **im Browser** über
  `assets/crypto.js`, PHP legt sie nur ab. Belegt sind: Der Kasten erscheint;
  er fragt nach dem Schlüssel; ein **falscher** wird abgewiesen und schreibt
  **nichts**; mit dem richtigen kommen Einsatz und Spur an, der Chiffretext
  ist ein **anderer** als in der Quelle, und er öffnet sich mit dem Schlüssel
  des **Zielkontos** zu demselben Klartext.
- **Der Joblauf am echten Auslöser.** Der Schub ist über die Probe belegt
  (mit einer Uhr, die tickt — die erste Fassung der Prüfung gab eine Konstante
  zurück und prüfte damit nichts); ein vollständiger Durchlauf über `jobs.php`
  am Cron ist nicht gefahren.
- **Die Warnmail selbst.** Ohne eingerichtetes SMTP ist der Hinweisweg
  geprüft, der Versandweg nicht.
- **Eine erreichte Speichergrenze im Betrieb.** Geprüft mit einer künstlich
  auf 1 KB gesetzten Grenze, nicht mit einer vollen Platte.

---

### AP7 — Sicherungsziele FTP, FTPS, SFTP (erledigt, Web 12.1.0)

**Was gebaut wurde.** E-S2-22 verlangt eine Transport-Schnittstelle mit drei
Adaptern, Pflege im Adminbereich mit „Verbindung prüfen" und einen Push über
den Job-Einstieg; E-S2-21 verlangt den Serverschlüssel in `config.php`, der
die Zugangsdaten schützt. Beides steht.

Neu: `server/serverkrypto_lib.php` (Serverschlüssel und `edsk1:`-Versiegelung),
`server/sicherungsziel_lib.php` (Schnittstelle `Zielweg`, `ZielFtp`,
`ZielSftp`, Datenbankseite, Verbindungsprüfung, Versandschub),
`server/admin_sicherungsziele.php`, `server/vendor/` (phpseclib 3.0.57 und
constant_time_encoding 2.7.0, MIT, mit Lader und Prüfsummenlisten), Migration
`2026_09_01_sicherungsziele` (Tabelle `backup_targets`), Job `versand`.

#### Sieben Entscheidungen, die dabei gefallen sind

**1. „Sicherungsziel" statt „Transportziel" (F-S2-G).** Der Name des Konzepts
war vergeben. Ein zweites Ding unter demselben Wort, zwei Klicks entfernt, ist
in einer Fehlermeldung nicht mehr aufzulösen.

**2. Der Serverschlüssel liegt in `config.php`, nicht in der Datenbank.** Der
Zweck ist der Fall „jemand hat die Datenbank"; für AP8 wird es zwingend, weil
der Dump jede Tabelle enthält. Der Zweck (Zielkennung und Feldname) geht in die
Zusatzdaten der Verschlüsselung — damit lässt sich eine Chiffre nicht von einem
Ziel auf ein anderes umhängen.

**3. Der Nachtrag in `config.php` schreibt, statt nur eine Zeile zu zeigen.**
Der Weg von Hand bleibt als Rückfall. Geschrieben wird ergänzend (nie
ersetzend — sonst wäre jedes versiegelte Feld unlesbar), über eine Nebendatei
mit Endung `.php` (eine `config.php.tmp` läge im Wurzelverzeichnis des
Webservers als lesbarer Text mit dem Datenbankpasswort), mit Gegenprobe durch
Ausführen und anschliessendem Verwerfen des OPcache-Eintrags.

**4. Ein Adapter für FTP und FTPS.** Es unterscheidet sie genau eine Zeile.
Zwei Klassen wären zwei Orte für dieselbe Fehlerbehandlung.

**5. Der Fingerabdruck wird VOR der Anmeldung geprüft.** Wer sich bei einem
untergeschobenen Server anmeldet, hat sein Passwort schon abgegeben, auch wenn
er danach abbricht. Gemessen wird das an der Gegenstelle, nicht an der
Fehlermeldung.

**6. „Neu" wird am Ziel abgelesen, nicht in einer Merkliste geführt.** Eine
Merkliste behauptet „schon versandt" auch dann noch, wenn die Datei am Ziel
gelöscht oder das Ziel neu aufgesetzt wurde. Verglichen werden Name **und**
Grösse — sonst gälte eine abgebrochene Übertragung für immer als erledigt.
Der Preis ist eine Verzeichnisabfrage je Konto und Ziel (**Backlog Nr. 50**).

**7. Auf dem Ziel wird nie gelöscht.** Die Aufbewahrung „zwei je Konto" gilt
für die Ablage hier. Ein Versand, der drüben aufräumt, trüge einen Fehler von
hier mit hinüber — und genau davor soll das Ziel schützen (**Backlog Nr. 49**).

#### Drei Funde aus der eigenen Prüfung

- **`SFTP::TYPE_REGULAR` gibt es nicht.** phpseclib definiert
  `NET_SFTP_TYPE_REGULAR` als *globale* Konstante beim ersten Erzeugen eines
  Objekts. Der Fehler schlug erst beim **Auflisten** zu — eine leere
  Verzeichnisliste hätte den Versand still alles doppelt schicken lassen.
- **Der Serverschlüssel war nach dem Schreiben eine Anfrage lang unsichtbar.**
  OPcache prüft den Zeitstempel sekundengenau. Die Seite meldete „steht jetzt
  in config.php", die nächste Anfrage zeigte wieder „Serverschlüssel fehlt".
- **Zwei Fehlermeldungen waren unbrauchbar.** Ein falsches FTP-Passwort meldete
  „Authentication failed" ohne das Wort Passwort (das Muster kannte nur „Login
  incorrect"); ein geschlossener Port meldete „kam nicht zustande" ohne
  nächsten Schritt — `ftp_connect()` gibt dort `false` zurück und schweigt.

Dazu zwei Funde im **Bestand**, beide von den Prüfmitteln und nicht vom
Auge:

- Das Handbuch nannte weiterhin „höchstens drei Sicherungen je Konto". Die
  Zahl ist seit AP6 zwei; drei Dokumente waren damals nachgezogen worden,
  dieses vierte nicht. Berichtigt.
- Die **Tokentabelle in `docs/Design.md` war stehengeblieben**: `--rauch`
  stand auf 22 Verwendungen, der Erzeuger zählt 23. Nachgerechnet an einem
  Arbeitsbaum auf HEAD — die Abweichung bestand schon **vor** AP7 und ist
  nicht durch dieses Paket entstanden. Alle vier erzeugten Tabellen sind jetzt
  neu gesetzt und stimmen Zeichen für Zeichen mit der Ausgabe von
  `tools/design/tabellen.py` überein. Genau dafür gibt es das Werkzeug, und
  genau so fällt eine abgeschriebene Zahl auf.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| Serverschlüssel, Versiegelung, Zweckbindung | `versandprobe` Teil 1 | 12 Erwartungen — fremder Schlüssel, veränderte Chiffre, 100 000 Zeichen |
| Namen, Pfade, Fehlertexte | Teil 2 | 11 Erwartungen |
| FTP-Rundlauf gegen einen echten Server | Teil 3 | 10 Erwartungen, 5 014 Byte hin und zurück |
| FTPS-Rundlauf | Teil 4 | 10 Erwartungen — **und der Befund: `ext/ftp` nimmt ein selbst ausgestelltes Zertifikat an** |
| SFTP-Rundlauf und Fingerabdruck | Teil 5 | 12 Erwartungen, Abdruck stimmt mit dem des Servers überein |
| Unerwarteter Hostschlüssel | Teil 6 | Abbruch — **Anmeldeprotokoll der Gegenstelle: 3 Zeilen vorher, 3 nachher** |
| Privater Schlüssel, Passphrase | Teil 7 | 6 Erwartungen |
| Fehlerfälle (Passwort, Port, Pfad, Datei) | Teil 8 | 14 Erwartungen, jede Meldung nennt das richtige Wort |
| „Verbindung prüfen" für alle drei | Teil 9 | 13 Erwartungen, **0 Rückstände** auf den Gegenstellen |
| Datenbank: anlegen, ändern, löschen | Teil 10 | 14 Erwartungen — Passwort **nie** im Klartext in der Spalte |
| `versandprobe` gesamt | `php probe.php` | **106 Erwartungen, 0 offen** |
| Versand, 64 Pakete / 63,89 MB / 33 Ordner | CLI, `memory_get_peak_usage(true)` | FTP **0,13 s · 2,0 MB** · FTPS **0,68 s · 2,0 MB** · SFTP **3,08 s · 8,0 MB** von 64 |
| Angekommene Dateien | `cmp` je Datei | **192 von 192 byteweise gleich, 0 Abweichungen** |
| Zweiter Lauf | | **0 gesendet**, 0,19 s |
| Halbe Datei am Ziel (auf 1 000 Byte gekürzt) | | **1 von 64** erneut geschickt, danach wieder byteweise gleich |
| Wiederaufnahme bei 2 s Budget | | **2 Schübe (34 + 30)**, danach vollständig |
| Joblauf über die Befehlszeile | `php jobs.php` | `versand fertig · erledigt 64 · Rückstand 0` |
| Bedienweg im Browser | Playwright | Schlüssel anlegen → Ziel anlegen → prüfen (**6 Schritte**) → falsches Passwort → versenden (**64 Dateien, 63,9 MB**) → Rückstand **0** |
| Oberfläche | dasselbe | **0 px** waagerechter Überlauf, **0** Seitenfehler, Bedienelemente **44 px** |
| Wortliste | `wortliste.py` | **0 Treffer, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| Vollständigkeit | `pruefen.py` | **260 Befunde — unverändert gegenüber AP6** |
| Kontraste | `kontrast.py` | **21 Paare gerechnet, 0 verfehlt** |
| Bilderlauf `43-` und `43b-` | `aufnehmen.mjs` | 16 Bilder, **16 verschiedene Prüfsummen**, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Erzeugte Tabellen in `Design.md` | `tabellen.py` | alle **vier** Zeichen für Zeichen gleich |
| Regression | die vorhandenen Proben | `wiederherstellung` **76**, `spur` **25**, `jobs` **24** — je 0 offen |

#### Nachtrag vom 01.09.2026: die Probe läuft jetzt auch gegen echte Server

Der erste Stand prüfte gegen **Nachbauten** (pyftpdlib, paramiko). Auf die
Frage, ob sich das nicht härter machen lässt, ist ein zweiter Satz
Gegenstellen entstanden: `tools/versandprobe/echte_gegenstellen.sh` stellt
**vsftpd** und **OpenSSH** hin — die Server, die auf einem Webspace
tatsächlich laufen. Beide Sätze werden gebraucht, und zwar nicht aus
Gründlichkeit, sondern weil sie verschiedene Zweige treffen:

**vsftpd kennt kein MLSD.** Damit ist der Rückfall auf `NLST` + `SIZE` in
`ZielFtp::liste()` zum ersten Mal gefahren worden — gegen pyftpdlib allein
wäre er nie an die Reihe gekommen. Die Probe *misst* seither, welchen Weg sie
genommen hat, statt ihn zu vermuten.

**Was der zweite Satz gefunden hat, obwohl der erste grün war:** Zwei
Fehlermeldungen blieben halb englisch. vsftpd sagt „Could not create file",
pyftpdlib sagt „Not enough privileges"; die Übersetzung kannte beide nicht,
und die Meldung für ein verweigertes Schreiben endete deshalb im Original.
Beide Wortlaute stehen jetzt im Muster. Das ist genau der Ertrag, den ein
zweiter Satz Gegenstellen bringen soll: Jede Umsetzung hat ihr eigenes
Vokabular.

**Vier Nebenwege waren bis dahin unbelegt** und sind es nicht mehr (Teil 11):
der Listenweg ohne MLSD, **aktives** FTP (der Schalter war nur in einer
Stellung gefahren), ein Grundpfad mit Unterordnern statt `/` (im Betrieb der
Normalfall — `sz_pfad()` setzt dann zwei Bestandteile zusammen statt einen),
und ein verweigertes Schreiben.

**Der Hostschlüsselwechsel ist jetzt echt.** Statt eines erfundenen
Fingerabdrucks startet OpenSSH mit einem zweiten Schlüssel neu. Ergebnis: Die
Verbindung bricht ab, die Meldung nennt beide Abdrücke, und das
Anmeldeprotokoll des Servers steht **vorher wie nachher auf 46 Zeilen** — es
ging kein Passwort hinaus. Nebenbei eine unabhängige Gegenprobe der eigenen
Rechnung: Der von `sz_fingerabdruck()` errechnete Wert ist zeichengleich mit
dem von `ssh-keygen -lf`.

**Ein Unterschied, der im Betrieb Schaden anrichten kann**, ist dabei
aufgefallen und in der LIESMICH festgehalten: vsftpd sperrt den Nutzer in sein
Heimverzeichnis, dort ist `/` die Wurzel. OpenSSH tut das nicht — dort ist `/`
die Wurzel des Dateisystems. Derselbe Eintrag „Pfad = /" bedeutet je nach
Protokoll etwas anderes.

| Was | Mittel | Zahl |
|---|---|---|
| `versandprobe` gegen die Nachbauten | `probe.php` | **115 Erwartungen, 0 offen** |
| `versandprobe` gegen vsftpd und OpenSSH | `probe.php --echt` | **115 Erwartungen, 0 offen** |
| Listenweg | gemessen je Lauf | pyftpdlib **MLSD**, vsftpd **Rückfall NLST+SIZE** |
| Hostschlüsselwechsel (echter zweiter Schlüssel) | OpenSSH-Protokoll | abgewiesen, **46 → 46** Anmeldezeilen |
| Fingerabdruck gegen `ssh-keygen -lf` | unabhängige Rechnung | zeichengleich |
| Versand 64 Pakete / 63,9 MB, echte Server | `memory_get_peak_usage(true)` | FTP **0,35 s** · FTPS **1,85 s** · SFTP **0,68 s**, Spitze 2,0 bzw. 8,0 MB von 64 |
| Angekommene Dateien (OpenSSH) | `cmp` je Datei | **64 von 64 byteweise gleich** |

**Noch nicht geprüft** (steht hier und nicht in einer Fußnote):

- **Ein echtes Ziel im Internet — die Abnahme nach Abschnitt 9 steht aus.**
  Aus dem Behälter, in dem gearbeitet wurde, gehen nur Verbindungen auf Port
  443 hinaus. Nachgemessen mit `github.com:22` als Gegenkontrolle: ein sicher
  offener Port, der ebenso abgewiesen wird — es ist eine Portsperre und keine
  Eigenschaft eines Ziels. `ftp.luftrettung.net` ist in der Freigabeliste (der
  Tunnel wird auf 443 aufgebaut, auf 21 und 22 sofort zurückgesetzt). **Die
  Abnahme je Protokoll gehört auf die Maschine der Betreiberin oder auf den
  Produktivserver.**
- ~~**Ein echter Server mit eigenen Eigenheiten.**~~ **Nachgeholt** (siehe
  Nachtrag oben): vsftpd und OpenSSH, fehlendes MLSD, aktives FTP und ein
  tiefer Grundpfad sind belegt. Offen bleibt, was diese beiden auch nicht
  haben: ProFTPD, IIS-FTP, ein Pfad mit ungewöhnlichen Zeichen, ein Server
  hinter NAT mit einem Portbereich, den die Firewall nicht durchlässt.
- **Eine langsame oder abreissende Leitung.** Alles lief über Loopback. Der
  Abbruch mitten in der Datei ist *nachgestellt* (gekürzte Datei am Ziel),
  nicht erlebt.
- **Ein volles Ziel.** Geprüft ist der *nächstliegende* Fall — ein
  Verzeichnis, in das nicht geschrieben werden darf; der Weg durch den Adapter
  ist derselbe, die Ursache eine andere. Eine wirklich volle Platte ist nicht
  hergestellt worden.
- **Der Versand über den Cron-Auslöser.** Über die Befehlszeile ist er
  gefahren, am eingerichteten Zeitdienst nicht.

---

### AP8 — Komplettsicherung der Installation (erledigt, Web 12.2.0)

**Was gebaut wurde.** E-S2-19 bis E-S2-21 verlangen eine Sicherung der ganzen
Installation als eigener SQL-Dump, versiegelt mit dem Serverschlüssel aus AP7,
erzeugt in Häppchen über den Job-Einstieg, mit einem Rückweg über die App und
einem Runbook-Kapitel. Alles davon steht.

Neu: `server/komplett_lib.php` (Dump, Siegel EDKOMP1, Auftrag, Zeitplan),
`server/admin_komplettsicherung.php`, `server/wiederherstellen.php`,
`tools/komplettprobe/`. Der Job `komplett` steht im Katalog; Versand,
Speicherbuchführung und Menü sind angeschlossen. **Keine Migration** — die
Komplettsicherung liegt im Dateisystem, ihr Zustand in `jobs.zustand`.

#### Neun Entscheidungen, die dabei gefallen sind

**1. Ein eigener Dump statt `mysqldump`.** Auf geteiltem Webspace gibt es
keine Kommandozeile und kein `exec()`. Das war in E-S2-20 vorgesehen; hier ist
die Bestätigung, dass es auch nötig ist.

**2. Ein Statement je Zeile — und daran hängt alles andere.** Damit lässt sich
die Datei zeilenweise abarbeiten, ohne SQL-Zerleger. Die Folge ist eine harte
Bedingung: Kein Literal darf je einen echten Zeilenumbruch enthalten.
`komp_quote()` ist deshalb eine eigene, lesbare Abbildung und nicht
`PDO::quote()` — jenes braucht eine offene Verbindung, die der Rückweg später
nicht mehr hat.

**3. Der Cursor ist aufgefächert, nicht ein Zeilenkonstruktor.** Gemessen an
`track_points` (917 331 Zeilen): `WHERE (a,b) > (?,?)` gibt `type=index` und
0,1486 s, `WHERE a > ? OR (a = ? AND b > ?)` gibt `type=range` und 0,0010 s.
Bei 459 Häppchen ist das der Unterschied zwischen 40 s und einer Sekunde.
**Eine Ausnahme:** Eine ENUM-Spalte VORN im Primärschlüssel verhindert den
Bereichszugriff auch bei aufgefächerter Bedingung; sie wird deshalb über ihre
Werteliste festgenagelt.

**4. Je Häppchen ein eigenes gzip-Glied.** Ein `deflate`-Zustand lässt sich
zwischen zwei Anfragen nicht aufbewahren. Aneinandergehängte Glieder sind
gültiges gzip — kosten 3 045 Byte auf 45,8 MB — und `gzopen()`/`gzread()`
liest darüber hinweg. Der Preis ist eine **Falle**, in die die erste Fassung
des Rückwegs prompt gelaufen ist: `gzdecode()` und `inflate_add()` sehen nur
das erste Glied. Nachgemessen: 13 573 234 statt 122 469 394 Byte, ohne Fehler.

**5. Die Versiegelung ist ein zweiter Gang.** Der Dump wächst zeilenweise, das
Siegel arbeitet in Blöcken fester Grösse; beides zugleich hiesse, einen halb
gefüllten Block zwischen zwei Anfragen aufzubewahren. So ist der Zustand der
Versiegelung eine einzige Zahl.

**6. Blockzähler UND Endemarkierung in den Zusatzdaten, plus der Dateikopf.**
Jedes einzeln reicht nicht: Ohne Zähler liessen sich Blöcke vertauschen, ohne
Endemarkierung liesse sich die Datei hinten abschneiden, ohne die Bindung an
den Kopf liesse sich der Kopf austauschen. Alle drei sind in der Probe
nachgestellt.

**7. Der unverschlüsselte Download ist kein Widerspruch zu E-S2-21.** Jene
Entscheidung sagt „Pflicht, sobald die Datei das Haus verlässt". Der Download
geht an die Administratorin, die sich eben angemeldet hat und ohnehin jede
Zeile sehen kann; E-S2-20 verlangt zugleich ausdrücklich eine Fassung, die
`mysql` einspielen kann. Was von selbst hinausgeht — der Versand —, ist immer
versiegelt. **Ohne Serverschlüssel wird gar nicht erst gesichert.**

**8. Der Job steht NACH dem Versand.** Davor wäre er am rechten Platz, aber
jeder Job hinter ihm bekäme nur, was die schwerste Arbeit der Anwendung übrig
lässt. Ein Versand, der wochenlang nicht drankommt, wäre der teurere Fehler.
Der Preis ist ein Lauf Verzögerung.

**9. Der Migrationslauf gehört nicht auf den Rückweg** — Abweichung von
E-S2-20, und die einzige. `update.php` ist seit M6-01 zweistufig, weil
Migrationen Spalten löschen können; eine Seite ohne Anmeldung, die sie
nebenbei mitlaufen liesse, nähme genau diese Sicherung heraus. Die Seite
vergleicht stattdessen die Web-Fassung und schickt zur Wartung; das Runbook
führt den Schritt als eigenen auf. **Backlog Nr. 54** hält die Frage offen.

#### Drei Fehler, die erst der Lauf gezeigt hat

**F-S2-H — Der Wiederanlauf hätte eine halbe Sicherung erzeugt.** Der Zustand
wird vom Job-Rahmen erst nach einem geglückten Häppchen gespeichert. Bricht
eines mittendrin ab, stehen seine Zeilen schon in der Datei und der Zustand
zeigt davor — der nächste Lauf schriebe sie ein zweites Mal, samt `DROP TABLE`
der laufenden Tabelle. Beim Einspielen würfe dieses zweite `DROP` weg, was das
erste Häppchen eingefügt hat. Behoben: Der Zustand führt die Länge des
gültigen Teils, und jedes Häppchen schneidet zuerst darauf zurück.

**F-S2-I — Der Neuanlauf lief in ein `count(null)`.** Die Erstbelegung des
Zustands stand vor dem Zweig, der bei verschwundenem Baustand ebendiese Marken
löscht. Der Zweig ist der, der **nach einer Wiederherstellung** greift: Die
Sicherung schreibt ihren eigenen Fortschritt mit, die eingespielte Datenbank
trägt also „Dump läuft" samt einem Bauordner, den es nie gab. Gefunden von der
Komplettprobe, Teil 8.

**F-S2-J — Die Schranke „leere Datenbank" blockierte die Wiederherstellung
selbst.** Sie galt vor jedem Durchgang; leer ist die Datenbank aber nur vor
dem ersten. Mit einem Budget von vier Sekunden brach die Wiederherstellung bei
**91 %** ab und meldete „Diese Installation ist in Betrieb". Bei grosszügigem
Budget reicht ein Durchgang, und der Fehler wäre nie aufgetreten. Behoben: Die
Schranke gilt fürs Anfangen; wer einen Arbeitsstand hat, hat ihn auf einer
leeren Datenbank begonnen.

#### Prüfstand

**Maschinell.** `tools/komplettprobe/probe.php` — **76 Erwartungen, 0 nicht
erfüllt** (mit `--pruefdb` und `--ziel`). `tools/versandprobe/` als Regression
nach den Änderungen an `sicherungsziel_lib.php`: **115 Erwartungen, 0 nicht
erfüllt**. Wortliste **0/0/0**, Vollständigkeit **260 Befunde** (unverändert
gegenüber dem Stand vor AP8), Kontraste **21 Paare, 0 verfehlt**.

**Im Browser.** `tools/komplettprobe/klickweg.mjs` — **17 Prüfungen, 0
Befunde**: Bestätigungsdialog, Lauf mit Rückmeldung, beide Downloads samt
Inhaltsprüfung (gzip-Magie bzw. `EDKOMP1` und `"pbkdf2"` im Kopf), Abweisung
einer zu kurzen Passphrase, Zeitplan setzen und zurückstellen, keine
Konsolenfehler. Dazu der Bilderlauf über beide neuen Seiten in acht Breiten:
**16 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** — nach einem
Fund: Der Knopf „Versiegelt herunterladen" stand in einer `.fld-reihe` statt
im Fussbereich und schob die Seite bei 360, 390 und 420 px auf (+74/+59/+44).

**Der volle Zyklus**, wie die Abnahme ihn verlangt:

| Schritt | Ergebnis |
|---|---|
| Erzeugen (5 000 Einsätze, 1 121 802 Zeilen, 34 Tabellen) | 8,5 s in **14 Häppchen** |
| Speicherspitze je Häppchen | **26 von 64 MB** (Z3) |
| SQL / versiegelt | 122,5 MB → **43,7 MB** |
| Längste Zeile | 1 048 566 Byte (Ziel: ≤ 1 MB je Stapel) |
| Auf ein FTP-Ziel geschoben | 1 Datei, 43,7 MB, byteweise gleich |
| Von Hand mit `mysql` eingespielt | 6,9 s, Rückgabewert 0 |
| Über `wiederherstellen.php` eingespielt | **6 Durchgänge** (Budget 4 s), 784 Anweisungen |
| Rundlauf | **34 von 34** Schemata zeichengleich, **34 von 34** Prüfsummen gleich |

Die Rückspielung ist auf **drei** Wegen gefahren: `mysql` von Hand, die
Komplettprobe zeilenweise, und `wiederherstellen.php` im Browser gegen einen
PHP-Server auf 127.0.0.1 — dort auch die Sonderfälle: falscher Nachweis,
abgeschnittene Datei, Passphrase-Fassung mit richtiger und falscher
Passphrase, und die Schranke „in Betrieb" auf einer gefüllten Datenbank.

**Der dokumentierte Python-Weg ist gefahren worden,** nicht abgeschrieben:
`docs/Backup-Format.md` 6.6 öffnet beide Fassungen und liefert byteweise
denselben Klartext wie PHP (cryptography 50.0.1).

#### Was nicht geprüft werden konnte

- **`wiederherstellen.php` im Browser.** Die Seite ist über `curl` gegen einen
  echten PHP-Server gefahren — mit Formularen, Sitzung, Nachweis und allen
  Sonderfällen — und in acht Breiten fotografiert. Angeklickt hat sie niemand,
  und ein Klickweg dafür ist auch nicht leicht zu bauen: Er bräuchte eine
  **leere** Datenbank. Der Bedienweg gehört deshalb in die Prüfliste des
  Prüfdokuments.
- **Ein echtes Sicherungsziel im Internet.** Wie in AP7: Der Behälter lässt
  nur Port 443 hinaus.
- **Eine volle Platte.** Die Speichergrenze ist als Rechnung geprüft, nicht
  als Zustand.
- **Ein echter Absturz mitten in der Anfrage.** Nachgestellt ist er, erlebt
  nicht.
- **Ein Migrationslauf nach einer Wiederherstellung.** Er läuft dort nicht
  mit (Entscheidung 9) und ist auch nicht von Hand nachgefahren worden.
- **Eine andere Datenbank als MariaDB 10.11.** Der Rundlauf ist auf einem
  Server gemessen.

---

### AP9 — Die Suche (erledigt, Web 12.1.1)

**Was E-S2-16 verlangt** — Schlüssel einmal je Sitzung importieren,
Entschlüsselung in Stapeln von rund 200, kein Umbau des Suchindex — ist
gebaut. Beides sind wenige Zeilen, beide wirken auf ihren eigenen Zahlen, und
**beide zielen am Engpass vorbei.** Das ist der eigentliche Ertrag dieses
Pakets.

#### Der Reihe nach

**1. Der Schlüsselimport lief 4 880-mal für denselben Schlüssel.**
`aesKey()` in `crypto.js` rief bei jedem `encrypt()` und jedem `decrypt()`
ein eigenes `crypto.subtle.importKey()`. Jetzt liegt die **Promise** des
Imports in einem Fach — nicht der fertige Schlüssel, denn 200 gleichzeitige
Aufrufe würden sonst 200 eigene Importe starten, weil noch keiner fertig ist.
Geräumt wird das Fach in `clearSession()`.

**2. Die Schleife kostete mehr als die Krypto.** Gemessen: 4 880
Entschlüsselungen 387 ms, die Runden durch die Ereigniswarteschlange darum
herum 1 954 ms. Mit Stapeln zu 200: **958 ms**.

**3. Und trotzdem bewegt sich die Gesamtzeit kaum.** 4,11 → 3,77 s bis zu
lesbaren geschützten Spalten. Denn zwischen „erste Zeile im DOM" (3,67 s) und
„lesbar" (3,77 s) liegen **0,1 s** — die Entschlüsselung der *angezeigten*
Zeilen war nie das Problem. Die Zeit geht davor drauf: Antwort holen,
auswerten, je Einsatz den Heuhaufen bauen, filtern, sortieren.
**Backlog Nr. 51**, ausdrücklich nicht nebenbei.

#### Zwei Berichtigungen an der Ausgangsmessung

**Die Suche entschlüsselt alle 5 002 Einträge, nicht 200.** AP0 hielt das
Gegenteil fest. Gezählt: `entschluessleListe` bekommt 5 002,
`crypto.subtle.decrypt` läuft 4 880-mal. Gedeckelt ist die **Anzeige**.

**Die Zeiten 4,53 s (Suche) und 4,81 s (Tagesansicht) sind zu hoch, weil das
Prüfmittel sich selbst mitgemessen hat.** `entsperren()` wartete vier
Sekunden auf einen Entsperr-Dialog, der bei entsperrter Sitzung nie kommt —
mitten im gemessenen Abschnitt. Gemessen wurde `max(4 s, tatsächliche
Dauer)`. Dass ausgerechnet die beiden auffälligen Werte dicht über vier
Sekunden liegen, war das Warnzeichen, das niemand gelesen hat.

> **Und die Falle hat beim Nachmessen sofort wieder zugeschnappt.** Mein
> erster Anlauf setzte das Limit auf acht Sekunden und maß dreimal 8,46 s —
> für den alten Stand, für den neuen und sogar mit vollständig
> abgeschalteter Entschlüsselung. Drei gleiche Zahlen aus drei verschiedenen
> Ständen sind kein Messergebnis, sondern eine Konstante; ich habe sie
> trotzdem erst einmal berichtet. Die Regel aus `CLAUDE.md` Abschnitt 6
> gilt gegen das Prüfmittel wie gegen den Code.

Behoben: Das Warten auf den Dialog rennt gegen die Abschlussbedingung des
Schritts. Kommt der Dialog zuerst, wird entsperrt; ist der Schritt zuerst
fertig, wurde keine Sekunde dafür verbraucht.

#### Prüfstand

| Was | Mittel | Zahl |
|---|---|---|
| `importKey`-Aufrufe je Suchlauf | Haken um `crypto.subtle` | **4 880 → 1** |
| `entschluessleListe` | dasselbe | **1 954 → 958 ms** |
| entschlüsselte Einträge | gezählt | **5 002** (4 880 mit Block), Anzeige 200 |
| erste Zeile im DOM | Playwright, Drossel 6× | 4,02 → **3,67 s** |
| geschützte Spalten lesbar | dasselbe | 4,11 → **3,77 s** (Ziel ≤ 5 s) |
| PBKDF2 auf der Suchseite | Zähler | **0** — unverändert |
| Kreislauf `edbak` | `kreislauf.py` | **252 882 Einzelvergleiche, 0 unerklärt** |
| Kreislauf `edbak-alt` (Altformat, R24) | `kreislauf.py` | **287 282 Einzelvergleiche, 0 unerklärt** |
| Kreislauf `csv` | `kreislauf.py` | **8 797 Einzelvergleiche, 0 unerklärt** |

Alle Zeiten: Median aus drei Läufen, beide Stände unmittelbar nacheinander
gemessen, Drossel 6×.

**Noch nicht geprüft** (steht hier und nicht in einer Fußnote):

- **Eine ruhige Maschine.** Alle Zeiten sind gemessen, während die
  Bestandsaufnahme für AP8 mit mehreren Agenten lief. Der *Vergleich* trägt
  (beide Stände unter denselben Bedingungen), die *absolute* Zahl ist nach
  oben verzerrt. Der Zielwert von 5 s ist damit gehalten, aber mit unbekannter
  Reserve — die Abnahme gehört auf eine unbelastete Maschine.
- ~~**Die Tagesansicht.**~~ **Nachgemessen** — und das ist der grösste
  einzelne Ertrag dieses Pakets. Der vollständige Lauf mit der berichtigten
  Wartelogik:

| Schritt | AP0 (mit dem Messfehler) | jetzt | Ziel (E-S2-24) |
|---|---|---|---|
| Startseite, 500 Tagesverweise | 1,36 s | 1,39 s | — |
| **Tagesansicht bis zur gezeichneten Spur** | **4,81 s** | **1,17 s** | ≤ 3 s — **gehalten**, nicht 62 % darüber |
| Suche bis zur ersten Trefferanzeige | 4,53 s | **3,81 s** | ≤ 5 s |
| Sicherung erstellen | 109,8 s | **42,21 s** | ≤ 5 min |

  **Die Tagesansicht war nie über dem Ziel.** Der Befund „62 % darüber"
  aus AP0 löst sich vollständig auf — er war der Timeout. Bei der Sicherung
  geht ein Teil der Verbesserung auf AP5b und AP6 zurück, bei Tagesansicht
  und Suche ist es der Messfehler.
- **Ein Konto mit 200 Treffern und aktivem Filter.** Gemessen wurde der
  Aufbau ohne Filter. Ob ein Freitextfilter über 5 002 entschlüsselte
  Einträge in der Drossel erträglich bleibt, ist offen — und genau die Frage,
  an der Backlog Nr. 51 hängt.
