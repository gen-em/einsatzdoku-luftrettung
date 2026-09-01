# Lizenzen — was in dieser Anwendung von anderen stammt

Diese Datei zählt **jeden** Bestandteil auf, der nicht in diesem Projekt
geschrieben wurde: Bibliotheken, Schriften, Symbole und die beiden Dienste,
die der Browser zur Laufzeit anspricht. Sie ist zugleich die Antwort auf die
Frage, die ein Impressum stellt — *woher kommt, was hier läuft?*

Entstanden in P3/O12. Wer eine Abhängigkeit hinzufügt, austauscht oder
entfernt, pflegt sie im selben Arbeitspaket nach (`CLAUDE.md`, Abschnitt 9).

---

## 1. Die Anwendung selbst

**GNU Affero General Public License, Version 3** (`LICENSE` im
Wurzelverzeichnis). Der Lizenzhinweis steht in der Fußzeile jeder Seite:
„© Gen-EM · Open Source · AGPL-3.0".

Die AGPL ist bewusst gewählt und nicht bloß eine GPL mit anderem Buchstaben:
Sie verlangt die Herausgabe des Quelltexts auch dann, wenn die Software nicht
*ausgeliefert*, sondern nur über ein Netz *betrieben* wird. Genau das ist der
Normalfall dieser Anwendung.

**Die Lizenzen mischen sich nicht.** Fremdbestandteile behalten ihre eigene
Lizenz — die Symbole bleiben MIT, Leaflet bleibt BSD-2, die Schriften bleiben
OFL. Keine dieser Lizenzen verlangt, dass der Anwendungscode ihre Bedingungen
übernimmt; alle vier sind mit der AGPL verträglich.

---

## 2. Grundsatz: keine fremde Quelle zur Laufzeit

Es gibt **kein CDN, keine Google Fonts, kein externes Skript**. Jede
Bibliothek und jede Schrift liegt im Repositorium und wird vom eigenen Server
ausgeliefert.

Das ist keine Vorliebe, sondern folgt aus dem Zweck der Anwendung: Sie
verschlüsselt Diagnose, Alter und Einsatzort im Browser, damit sie den Server
nie im Klartext erreichen. Eine Schrift, die bei jedem Seitenaufruf von einem
fremden Server geholt wird, meldet demselben Server die IP-Adresse und den
Zeitpunkt — und damit, *dass und wann* jemand Einsatzdaten bearbeitet. Das ist
ein Bruch in derselben Linie, nur eine Etage tiefer (Backlog Nr. 12).

Die **beiden Ausnahmen** sind Kartendienste, und sie sind in Abschnitt 6
einzeln begründet: Kartenkacheln und Adresssuche lassen sich nicht mitliefern.

Jede vendorierte Datei trägt im Kopf **Herkunft, Version und SHA-256**. Bei
einem Update wird die Datei komplett ausgetauscht und der Kopf mitgezogen —
nicht hineingepatcht.

---

## 3. Bibliotheken

Alle unter `server/assets/vendor/`.

| Bibliothek | Version | Lizenz | Datei | wofür |
|---|---|---|---|---|
| **Leaflet** | 1.9.4 | BSD-2-Clause | `vendor/leaflet/leaflet.js`, `leaflet.css`, `images/` | Die Karten auf Tagesübersicht, Einsatzansicht, Zeitraum und in der Ortswahl |
| **SheetJS Community Edition** (`xlsx`) | 0.18.5 | Apache-2.0 | `vendor/xlsx.full.min.js` | Excel-Export und -Import; läuft **im Browser**, die Datei entsteht dort |
| **zip.js** | 2.8.34 | BSD-3-Clause | `vendor/zipjs.min.js` | Das verschlüsselte Archiv des Exports |

**Prüfsummen** (SHA-256, wie im Dateikopf vermerkt):

| Datei | SHA-256 |
|---|---|
| `leaflet.js` | `db49d009c841f5ca34a888c96511ae936fd9f5533e90d8b2c4d57596f4e5641a` |
| `xlsx.full.min.js` | `c9506197caf809a075b6dee1da0d36fb19da7158ffe8a88e7b0c96c5d8623c99` |
| `zipjs.min.js` | `52351e49074131fca386e6b13913e1c0bad5e66af7a2b87a815c0d0ca8714982` |

Alle drei sind über das npm-Paketarchiv bezogen (`npm pack <paket>@<version>`)
und unverändert übernommen. Leaflet bringt fünf PNG-Dateien mit
(`vendor/leaflet/images/`) — Marker, Markerschatten und das Ebenensymbol; sie
gehören zur Bibliothek und stehen unter derselben Lizenz.

**Warum SheetJS und zip.js im Browser laufen und nicht auf dem Server:** Ein
Export enthält die entschlüsselten Angaben. Entstünde er auf dem Server,
müssten sie dorthin — und damit wäre die Ende-zu-Ende-Verschlüsselung an
genau der Stelle aufgehoben, an der jemand seine Daten mitnehmen will.

### 3a. Bibliotheken, die auf dem Server laufen

Alle unter `server/vendor/` — getrennt von `assets/vendor/`, weil sie **nicht**
an den Browser ausgeliefert werden. Eine `.htaccess` im Ordner sperrt den
Abruf; geladen werden sie über `server/vendor/laden.php`, einen
zwanzigzeiligen PSR-4-Lader. Es gibt keinen Composer: Auf einem Webspace läuft
kein `composer install`, und ein erzeugter Composer-Autoloader mit absoluten
Pfaden gehört nicht ins Repositorium.

| Bibliothek | Version | Lizenz | Verzeichnis | wofür |
|---|---|---|---|---|
| **phpseclib** | 3.0.57 | MIT | `vendor/phpseclib3/` | Der SFTP-Adapter der Sicherungsziele (S2/AP7) |
| **constant_time_encoding** | 2.7.0 | MIT | `vendor/ParagonIE/ConstantTime/` | Von phpseclib vorausgesetzt (genau eine Stelle: `Common/Functions/Strings.php`) |

Herkunft, Commit-Kennung und die Anleitung zum Austausch stehen in
`server/vendor/HERKUNFT.md`; die Lizenztexte liegen daneben
(`LIZENZ-phpseclib.txt`, `LIZENZ-constant-time-encoding.txt`). Übernommen sind
nur die Quellverzeichnisse — Tests, Build-Dateien und `composer.json` nicht.

**Die Prüfsummen stehen nicht im Dateikopf, sondern in zwei Listen.** Bei 338
bzw. 11 Dateien wäre ein Kopfkommentar je Datei von Hand nicht zu pflegen und
beim ersten Austausch falsch. Nachrechnen:

```sh
cd server/vendor
sha256sum -c phpseclib3.sha256              # 338 Dateien
sha256sum -c ParagonIE-ConstantTime.sha256  #  11 Dateien
```

Das ist die einzige Abweichung von der Regel in Abschnitt 2, und sie ist eine
Verschärfung: Eine Liste prüft jede Datei, ein Kopfkommentar behauptet etwas
über eine.

**Warum überhaupt eine Fremdbibliothek und nicht `ext/ssh2`:** Die Erweiterung
ist auf geteiltem Webspace praktisch nie vorhanden und lässt sich dort nicht
nachinstallieren. phpseclib ist reines PHP und läuft überall, wo diese
Anwendung läuft. Geladen wird es **nur** vom SFTP-Adapter — eine Seite, die
keine SFTP-Verbindung aufbaut, lädt keine einzige dieser Dateien.

FTP und FTPS brauchen keine Bibliothek: Sie laufen über die PHP-Erweiterung
`ftp`, die zum Sprachumfang gehört.

---

## 4. Schriften

Beide unter `server/assets/fonts/`, eingebunden über `@font-face` in
`server/assets/style.css`, Abschnitt 1.

| Schrift | Schnitte | Lizenz | Herkunft |
|---|---|---|---|
| **Bricolage Grotesque** | 500, 600 | SIL Open Font License 1.1 | npm `@fontsource/bricolage-grotesque` 5.3.0 |
| **Open Sans** | 400, 600, 700 | SIL Open Font License 1.1 | npm `@fontsource/open-sans` 5.3.0 |

Übernommen wurden **ausschließlich die tatsächlich benutzten Schnitte**, je in
den Subsets `latin` und `latin-ext` — zehn `.woff2`-Dateien. Wer einen
weiteren Schnitt braucht, legt die Datei dazu *und* trägt sie im Stylesheet
ein; ohne `@font-face` nutzt eine Datei im Ordner nichts.

`unicode-range` trennt die beiden Subsets, sodass `latin-ext` nur geladen
wird, wenn ein Zeichen daraus vorkommt. `font-display:swap` zeigt sofort die
Ersatzschrift und tauscht danach — eine Seite, die auf eine Schrift wartet,
ist auf einem Einsatzfahrzeug keine.

Die OFL erlaubt Nutzung, Einbettung und Weitergabe, auch kommerziell. Ihre
eine ernste Bedingung: Eine **veränderte** Schrift darf den ursprünglichen
Namen nicht weiterführen. Hier wird nichts verändert.

---

## 5. Symbole

**Tabler Icons** (tabler.io/icons), **MIT-Lizenz**. Lizenztext:
`server/assets/images/symbole/LICENSE-tabler-icons.txt`
(© 2020–2026 Paweł Kuna).

44 Dateien unter `server/assets/images/symbole/`, je Zeichen eine Datei,
24 × 24, Strich 2 px, Farbe über `currentColor`. Jede Datei trägt im Kommentar
ihren Tabler-Namen; die Zuordnungstabelle steht in der `LIESMICH.md` daneben.
Eine erzeugte Übersicht liefert `python3 tools/design/tabellen.py symbole`.

**Eine** Datei stammt nicht von Tabler: `luftlinie.svg` ist ein eigener
Entwurf im selben Stil (24er-Raster, 2 px, runde Enden) und im Dateikopf als
solcher gekennzeichnet. Zeichen aus anderen Bibliotheken werden nicht
gemischt; die Begründung steht in `docs/Design.md`, Kapitel 8.

MIT erlaubt Nutzung, Änderung und Verbreitung, auch in kommerziellen Produkten
und Diensten; einzige Pflicht ist die Mitlieferung des Lizenztexts. Die
Symbole bleiben unter MIT, der Anwendungscode unter AGPL-3.0.

---

## 6. Dienste zur Laufzeit — die beiden Ausnahmen

Hier verlässt eine Anfrage den eigenen Server. Beide Fälle sind unvermeidlich
und beide sind eng gefasst.

### 6.1 Kartenkacheln

Der Browser lädt die Kartenbilder direkt beim jeweiligen Anbieter. Drei
Ebenen stehen zur Wahl (`server/assets/map_layers.js`):

| Ebene | Anbieter | Daten unter |
|---|---|---|
| Standard | `tile.openstreetmap.org` | **ODbL** — © OpenStreetMap-Mitwirkende |
| Topografisch | `tile.opentopomap.org` | **CC-BY-SA**, Daten © OpenStreetMap-Mitwirkende |
| Luftbild | `server.arcgisonline.com` (Esri World Imagery) | Esri-Nutzungsbedingungen |

Die **Namensnennung steht in der Karte selbst** — Leaflet zeigt sie unten
rechts, und `map_layers.js` setzt sie je Ebene. Das ist keine Höflichkeit,
sondern die Bedingung, unter der die Daten benutzt werden dürfen: Die ODbL
verlangt die Nennung der Quelle bei jeder öffentlichen Darstellung.

**Was dabei übertragen wird:** die Kachelkoordinaten — also mittelbar der
Kartenausschnitt, den jemand ansieht, und die IP-Adresse. Keine Einsatzdaten,
keine Kennungen, keine Patientenangaben. Wer auch das vermeiden will, betreibt
einen eigenen Kachelserver und trägt ihn in `map_layers.js` ein.

### 6.2 Adresssuche und Rückwärtssuche

**Photon** (`photon.komoot.io`), betrieben von komoot, Daten aus
OpenStreetMap unter **ODbL**. Angesprochen von `server/assets/ortsfeld.js`
(Suche nach einer Adresse) und `server/assets/ortswahl.js` (Koordinaten →
Adresse).

**Was dabei übertragen wird:** die eingetippten Buchstaben bzw. die gewählten
Koordinaten. Das ist der Grund, warum die Suche **nicht bei jedem Tastendruck**
läuft und warum das Feld ausdrücklich freiwillig ist: Wer keine Adresse sucht,
löst keine Anfrage aus. Der Einsatzort selbst wird verschlüsselt gespeichert;
gesucht wird, bevor daraus ein gespeicherter Wert wird.

---

## 7. Werkzeuge — nicht ausgeliefert

Alles unter `tools/` ist Prüf- und Erzeugungswerkzeug und **wird nicht auf den
Server geladen** (der Deploy nimmt den Ordner aus). Es benutzt Playwright mit
Chromium sowie Python-Standardbibliothek und Pillow. Diese Abhängigkeiten
sind Teil der Entwicklungsumgebung, nicht der Anwendung, und stehen deshalb
nicht in dieser Aufstellung.

Für die Uhr-App gilt dasselbe in die andere Richtung: `watch/` ist Monkey C
und benutzt ausschließlich das Connect-IQ-SDK von Garmin.

### 7.1 Das GPX-1.1-Schema (seit Web 10.3.0)

Eine Ausnahme von „hier steht nur, was ausgeliefert wird" — sie steht
trotzdem in dieser Aufstellung, weil es eine **fremde Datei** ist und die
Herkunft nachweisbar bleiben soll.

| | |
|---|---|
| Datei | `tools/gpxprobe/gpx11.xsd` |
| Herkunft | `https://topografix.com/GPX/1/1/gpx.xsd`, bezogen am 31.08.2026 |
| Größe | 26 665 Byte, **byteweise unverändert** |
| SHA-256 | `9e4d1988b862edbe556305b130f8f6f1b29864fefd0dc02d5dab04ccdd1f34d6` |
| Urheber | TopoGrafix (Dan Foster), GPX 1.1 |
| Zweck | `tools/gpxprobe/` validiert damit die erzeugten GPX-Dateien |

**Die Prüfsumme steht hier und in `tools/gpxprobe/LIESMICH.md`, nicht im
Dateikopf.** Ein Kommentar in der Datei änderte sie, und sie ist der Punkt: Die
Probe rechnet sie bei jedem Lauf nach. Ein Schemalauf gegen ein verändertes
Schema belegt nichts.

**Und Git schreibt sie nicht um.** Die `.gitattributes` des Projekts setzen
`* text=auto eol=lf`; das Schema hat 788 CRLF und wäre auf 25 877 Byte
geschrumpft — die Arbeitskopie hier unverändert, jeder frische Klon aber mit
falscher Prüfsumme. Die Zeile `tools/gpxprobe/gpx11.xsd -text` hält es
byteweise so, wie es kam. **Wer die Datei austauscht, prüft beides nach:**
Summe hier und Größe nach einem Klon.

**Zur Laufzeit wird sie nie geladen.** Sie liegt unter `tools/`, der Deploy
nimmt den Ordner aus, und die Anwendung kennt sie nicht. Die Zusage aus
Abschnitt 2 bleibt unberührt.

---

## 8. Was hier NICHT steht

- **Der Referenzdatensatz** (`tools/referenzdatensatz/`) ist erfunden. Namen,
  Diagnosen und Orte sind frei erdacht; die Straßengeometrie stammt aus
  OpenStreetMap (ODbL) und ist dort im Ordner vermerkt.
- **Die Logos** (`server/assets/images/gen-em_logo_*.svg`) sind eigene
  Dateien des Projekts und stehen unter der Projektlizenz. Regeln für ihren
  Einsatz: `docs/Design.md`, Kapitel 2.
- **Der Inhalt von Impressum und Datenschutzerklärung** ist Sache des
  Betreibers. Die Anwendung liefert keinen Text mit (R32); sie stellt nur die
  Seiten und den Editor.

---

## Änderungsverlauf

| Fassung | Was |
|---|---|
| Web 10.3.0 (S2/AP4) | Abschnitt 7.1: das vendorierte GPX-1.1-Schema von TopoGrafix, mit Herkunft und SHA-256. Es liegt unter `tools/` und wird zur Laufzeit nie geladen. |
| Web 9.13.0 (P3/O12) | Erstfassung. Zusammengetragen aus den Dateiköpfen unter `server/assets/vendor/`, dem Stylesheet-Kommentar zu den Schriften, `LICENSE-tabler-icons.txt` und den Adressen in `map_layers.js`, `ortsfeld.js` und `ortswahl.js`. |
