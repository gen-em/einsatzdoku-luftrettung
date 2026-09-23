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

**Und die Anwendung erfüllt das sichtbar:** Die Fußzeile jeder Seite verlinkt
den Lizenztext im Quelltextverzeichnis —
`https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE`,
gesetzt in `ui_fuss_seite()` (`server/ui.php`). Das ist **kein
Fremdbestandteil und keine Laufzeitquelle**: Es wird nichts von dort geladen,
es ist ein Verweis. Es steht hier, weil die AGPL genau diesen Verweis
verlangt — und weil die Regelklasse `netz` sonst zu Recht fragt, was eine
fremde Adresse im Markup zu suchen hat.

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
| **phpseclib** | 3.0.57 | MIT | `vendor/phpseclib3/` | Der SFTP-Adapter der Backup-Ziele (S2/AP7) |
| **constant_time_encoding** | 2.7.0 | MIT | `vendor/ParagonIE/ConstantTime/` | Von phpseclib vorausgesetzt (genau eine Stelle: `Common/Functions/Strings.php`) |
| **Parsedown** | 1.7.4 | MIT | `vendor/Parsedown.php` | Rendert `docs/Handbuch.md` und `docs/Was-ist-NAdoku.md` für `hilfe.php` und `ueber.php` (P5b/AP8) |

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

**Parsedown folgt der Regel aus Abschnitt 2 und nicht der Ausnahme oben:** Es
ist **eine** Datei, also stehen Herkunft, Fassung und SHA-256
(`af4a4b29f38b5a00b003a3b7a752282274c969e42dee88e55a427b2b61a2f38f`) in ihrem
Kopfkommentar. Der eigene Kommentar steht **vor** dem Originalinhalt; an der
Bibliothek selbst ist nichts geändert. Nachrechnen (der Befehl sucht die Länge
des Kommentars, statt sie einzutippen — sonst stimmt er nur bis zur nächsten
ergänzten Zeile):

```sh
N=$(grep -n '^ \*/$' server/vendor/Parsedown.php | head -1 | cut -d: -f1)
{ echo '<?php'; tail -n +$((N+1)) server/vendor/Parsedown.php; } | sha256sum
```

**Es geht am Lader vorbei.** Die Klasse `Parsedown` trägt keinen Namensraum,
der PSR-4-Lader hätte also nichts zu tun; `doku_lib.php` bindet die Datei
direkt ein. Geladen wird sie nur, wenn `hilfe.php` oder `ueber.php` aufgerufen
wird — jede andere Seite rührt sie nicht an.

**Warum ein zweiter Markdown-Renderer neben `rt_html()`.** Das Projekt hat
seit P3 einen eigenen, handgeschriebenen: Er kennt Überschriften, Listen und
geprüfte Links, und das ist kein Mangel, sondern die Zusage (E-P3-38) — sein
Text kommt aus der Datenbank und wird von einer AdministratorIn getippt, jede
Erweiterung wäre dort eine Vertragsänderung. Das Handbuch braucht Tabellen,
Codeblöcke, Zitate, Fettung und Bilder; keines davon kann `rt_html()`, und
keines davon soll es können. Zwei Renderer sind zwei Angriffsflächen — der
Preis ist bewusst gezahlt, weil die **Quellen** verschieden sind: der eine
liest ein Formular, der andere das Repositorium. Aufgerufen wird Parsedown an
genau einer Stelle und immer mit `setSafeMode(true)` **und**
`setMarkupEscaped(true)`.

**Eine Einschränkung setzt das Projekt selbst durch, nicht die Bibliothek:**
`DokuMarkdown::inlineImage()` lässt nur relative Bildquellen zu.
Nachgemessen am 17.09.2026 — Parsedown liefert mit SafeMode für
`![B](https://fremd.example/b.png)` ein `<img src="https://fremd.example/…">`
und lädt damit eine fremde Quelle zur Laufzeit. SafeMode prüft das **Schema**,
nicht die **Herkunft**; die Zusage aus Abschnitt 1 hält an dieser Stelle allein
der eigene Überschreiber.

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

56 Dateien unter `server/assets/images/symbole/`, je Zeichen eine Datei,
24 × 24, Strich 2 px, Farbe über `currentColor`. Jede Datei trägt im Kommentar
ihren Tabler-Namen; die Zuordnungstabelle steht in der `LIESMICH.md` daneben.
Eine erzeugte Übersicht liefert `python3 tools/erzeugen/design.py symbole`.

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
| Wandern | `tile.openmaps.fr` (OpenHikingMap) | **ungeprüft** — siehe den Absatz darunter |
| Luftbild | `server.arcgisonline.com` (Esri World Imagery) | Esri-Nutzungsbedingungen |

**Die Namensnennung verweist auf** `wiki.openstreetmap.org`,
`www.openstreetmap.org`, `opentopomap.org`, `openmaps.fr` und `www.esri.com`.
Das sind Links im Attributionsband, keine Kachelquellen — abgerufen wird von
ihnen nichts.

> **Die Zeile „Wandern" ist unvollständig, und das steht hier statt einer
> Vermutung.** Die Ebene wurde mit `map_layers.js` eingebaut und in der
> Content-Security-Policy freigeschaltet (`kopfzeilen_lib.php` 183), **ohne
> je in dieser Liste zu stehen** — gefunden am 22.09.2026 von der neuen
> Regelklasse `netz` (PK-04/1c, Backlog Nr. 280).
>
> **Was belegt ist**, weil es im Quelltext steht: Die Kacheln kommen von
> `https://tile.openmaps.fr/openhikingmap/{z}/{x}/{y}.png`, und das
> Attributionsband nennt „© OpenHikingMap · © OpenStreetMap" samt einem
> Spendenlink.
>
> **Was NICHT belegt ist:** unter welchen Bedingungen die BetreiberIn die
> Kacheln bereitstellt. Ein Eintrag in dieser Liste behauptet mehr als das
> Attributionsband — er nennt Rechteinhaber und Bedingungen. **Der Versuch,
> es nachzusehen, ist am 23.09.2026 gescheitert:** Die Arbeitsumgebung lässt
> beide Abrufe nicht durch, `wiki.openstreetmap.org` und `openmaps.fr` je
> mit **HTTP 403 am CONNECT-Tunnel** des Ausgangsproxys. Pflicht ist der
> Versuch, nicht der Erfolg (`CLAUDE.md` 6) — die Zahl steht hier, die
> Vermutung nicht.
>
> **Zu tun bleibt zweierlei** (Nr. 280): die Bedingungen von einem Rechner
> mit Netzzugang nachsehen und hier eintragen — oder, wenn sie eine Nutzung
> wie diese nicht decken, die Ebene ausbauen. Bis dahin ist die Zusage
> „keine fremde Quelle zur Laufzeit" an dieser Stelle **eingehalten, aber
> nicht belegt**: Die Quelle ist bekannt und aufgeschrieben, ihre Erlaubnis
> nicht.

Die **Namensnennung steht in der Karte selbst** — Leaflet zeigt sie unten
rechts, und `map_layers.js` setzt sie je Ebene. Das ist keine Höflichkeit,
sondern die Bedingung, unter der die Daten benutzt werden dürfen: Die ODbL
verlangt die Nennung der Quelle bei jeder öffentlichen Darstellung.

**Was dabei übertragen wird:** die Kachelkoordinaten — also mittelbar der
Kartenausschnitt, den jemand ansieht, und die IP-Adresse. Keine Einsatzdaten,
keine Kennungen, keine Patientenangaben. Wer auch das vermeiden will, betreibt
einen eigenen Kachelserver und trägt ihn in `map_layers.js` ein.

### 6.2 Adresssuche und Rückwärtssuche

**Photon**, Daten aus OpenStreetMap unter **ODbL**. Angesprochen von
`server/assets/geocoder.js` — seit Web 15.8.0 die **einzige** Stelle, die
nach draußen fragt; `ortsfeld.js` (Suche nach einer Adresse), `ortswahl.js`
(Koordinaten → Adresse) und das Suchfeld des Kartendialogs rufen nur noch
dieses Modul.

**Welcher Photon-Dienst gefragt wird, entscheidet die Installation.** Die
Vorgabe ist `https://photon.komoot.io`, betrieben von komoot; sie steht
**einmal** in `server/geocoder_lib.php` (`GEOCODER_VORGABE`) und in keiner
ausgelieferten Browserdatei — `grep -rn "komoot" server/assets/` = 0. Unter
Betrieb → Servereinstellungen, Karte „Adresssuche", trägt die BetreiberIn eine
andere Adresse ein; wer einen eigenen Photon betreibt, hält die Anfragen damit
im eigenen Haus, ohne eine Zeile Code und ohne neue Auslieferung.

**Zwei Schalter davor** (ebenfalls seit Web 15.8.0): einer für die
Installation (`app_state`-Schlüssel `adresssuche`, Betrieb →
Servereinstellungen), einer je Konto (`users.adresssuche`, Einstellungen →
Profil, Karte „Datenschutz"). Beide stehen auf „an"; ist einer aus, unterbleibt
**jede** Anfrage an den Dienst — Vorwärtssuche, Umkehrsuche und das Suchfeld im
Kartendialog. Nachgemessen am Netzwerkprotokoll (`tools/klickprobe/`, Weg
`ap2-kontoschalter-aus-keine-anfrage`): eingeschaltet 2 Anfragen auf demselben
Weg, ausgeschaltet 0. Damit ist dies der einzige Laufzeitdienst des Projekts,
der sich **abschalten** lässt; die Kartenkacheln (6.1) sind die Karte selbst
und können es nicht.

**Was dabei übertragen wird:** die eingetippten Buchstaben bzw. die gewählten
Koordinaten.

**Das Tippen in einem Ortsfeld löst Suchanfragen aus** (seit Web 12.3.3).
Vorher stand hier, die Suche laufe nur auf ausdrückliches Auslösen; für die
Felder Standort und Zielklinik stimmt das nicht mehr. Sie suchen jetzt beim
Tippen, weil ein Klick auf die Lupe für einen Weg, den man zwanzigmal am Tag
geht, eine Handlung zu viel ist. Drei Grenzen fassen das ein — seit Web
15.8.0 in `server/assets/geocoder.js`, vorher in `ortsfeld.js`:

| Grenze | Wert | wozu |
|---|---|---|
| Entprellung | **400 ms** Ruhe nach dem letzten Tastendruck | beim flüssigen Tippen eines Ortsnamens entsteht **eine** Anfrage, nicht eine je Buchstabe |
| Mindestlänge | **3 Zeichen** | unter drei Zeichen sucht niemand ernsthaft |
| offene Anfragen | **höchstens eine** | eine laufende wird abgebrochen, bevor die nächste startet |

Photon ist ein **frei betriebener Gemeinschaftsdienst**. Eine Anfrage je
Tastendruck wäre Missbrauch seiner Gutmütigkeit — und jede Anfrage trägt die
eingetippten Buchstaben zu einem Dritten.

**Freiwillig bleibt das Feld:** Wer eine Koordinate von Hand einträgt, einen
Plus Code einfügt oder den Ort auf der Karte wählt, löst **keine** Anfrage
aus; die Formaterkennung läuft lokal und hat Vorrang. Stehen bereits
Koordinaten, ruht die Adresssuche ganz.

**Die Ende-zu-Ende-Verschlüsselung ist davon nicht berührt.** Gesucht wird,
**bevor** aus der Eingabe ein gespeicherter — und damit verschlüsselter —
Wert wird. Das war beim Klick auf die Lupe so und ist es beim Tippen.

**Wer den Dienst nennen muss, bekommt den Satz dazu.** Die Anwendung liefert
keinen Rechtstext mit (Handbuch 11.5); für den Datenschutztext liegt unter
Verwaltung → Installation ein Textbaustein zum Kopieren bereit, in dem die
tatsächlich eingetragene Dienstadresse schon steht. Dieselbe Adresse nennen
die Kleinzeile unter dem Ortsfeld und die Karte „Datenschutz" im Profil.

---

## 6a. Die Android-Apps (seit S4)

Handy- und Wear-OS-App liegen unter `android/` und werden **nicht über den
Deploy verteilt**, sondern als APK auf den Server gelegt (`server/apk/`,
Technik 4.97g). Ihre Fremdbestandteile sind deshalb eine eigene Aufstellung —
sie laufen auf dem Telefon, nicht auf dem Server und nicht im Browser.

**So wenige wie möglich, und das ist gemessen.** HTTP läuft über
`HttpURLConnection`, JSON über `org.json`, der Puffer über
`SQLiteOpenHelper` — alles drei **Bordmittel von Android**. Sie stehen
deshalb nicht in dieser Tabelle: Was mit dem Betriebssystem kommt, ist keine
Abhängigkeit des Projekts.

| Bestandteil | Version | Lizenz | wofür |
|---|---|---|---|
| **AndroidX / Jetpack Compose** (`core-ktx`, `lifecycle`, `activity-compose`, Compose-BOM, `wear.compose`, `wear-input`) | BOM 2025.06.01, Wear-Compose 1.4.1 | Apache-2.0 | Die gesamte Oberfläche beider Module |
| **play-services-wearable** | 20.0.1 | **proprietär** (Google APIs ToS) | **Ausschließlich** der Wear Data Layer — der Weg zwischen Uhr und Handy |

**Zwei statt vier seit Android 0.11.0.** Bis dahin standen hier auch
**CameraX** (`camera-core`, `camera2`, `lifecycle`, `view`, 1.4.2,
Apache-2.0) für das Kamerabild und **ZXing** (`core`, 3.5.4, Apache-2.0) für
die QR-Erkennung daraus. Beide hingen an einer einzigen Funktion: dem
Adress-QR der Kopplung (E-S4-15). Mit R63 kennt die App genau eine
Serveradresse, es gibt nichts mehr zu scannen — und damit keinen Verbraucher
mehr für die beiden. Sie sind aus `android/gradle/libs.versions.toml` und dem
Bauskript ausgetragen, die `CAMERA`-Berechtigung aus dem Manifest.

Der Eintrag bleibt als Absatz stehen und wird nicht bloß gelöscht: Eine
Abhängigkeit, die es einmal gab, ist eine Angabe über frühere Auslieferungen,
und die Fassungen 0.2.0 bis 0.10.1 haben sie mitgeführt.

Nur zum Prüfen, nichts davon liegt im APK der Anwendung: JUnit 4.13.2
(EPL-1.0), Robolectric 4.16.1 (Apache-2.0), `androidx.test` 1.7.0 / 1.3.0
(Apache-2.0) und seit Android 0.7.6 `androidx.test:runner` 1.7.0
(Apache-2.0) — der Läufer für die **instrumentierten** Fälle. Er wird in ein
eigenes Test-APK gepackt, das nur `am instrument` installiert.

Die vollständige, maschinenlesbare Liste steht in
`android/gradle/libs.versions.toml` — **eine** Datei, ein Eintrag je
Bestandteil. Wer eine Fassung hochzieht, zieht sie dort hoch und trägt sie
hier nach.

**Alle werden nur zur Bauzeit bezogen.** Die App lädt zur Laufzeit nichts
nach — der Grundsatz aus Abschnitt 2 gilt für sie unverändert. Sie spricht
mit genau einem Server: dem, den die BedienerIn bei der Kopplung eingetragen
hat.

### Die proprietäre unter ihnen — und warum sie verträglich ist

`play-services-wearable` ist **nicht** quelloffen. Das ist eine Aussage, die
in einer AGPL-Anwendung nicht nebenbei stehen darf, deshalb ausführlich:

**Sie steckt nicht im APK.** Google-Play-Dienste sind eine *Systemkomponente*
des Geräts. Was die App mitliefert, ist eine dünne Client-Schicht, die auf
den auf dem Gerät vorhandenen Dienst zugreift — dieselbe Lage wie bei jeder
anderen Android-Systemschnittstelle. Die AGPL verlangt die Quellen des
Werks; ein Systemdienst des Betriebssystems gehört nicht dazu (die
Ausnahme für „System Libraries" in GPL-3 §1, auf die die AGPL sich stützt).

**Sie ist eingegrenzt und die Grenze ist nachprüfbar.** Sie wird an genau
einer Stelle benutzt: `android/gemeinsam/quelle/…/WearNachrichtenweg.kt`, der
Umsetzung der Schnittstelle `Nachrichtenweg`. Alles darüber — Puffer,
Quittung, Nummernvergabe, Bedienbild — kennt nur diese Schnittstelle. Wer
den Data Layer ersetzen will (Bluetooth unmittelbar, ein anderer Hersteller),
schreibt eine zweite Umsetzung und rührt nichts anderes an. Genau diese
Trennung ist der Grund, warum die Prüffälle beider Module ohne Play-Dienste
laufen: Sie benutzen eine Attrappe derselben Schnittstelle.

**Sie überträgt nichts nach außen.** Der Data Layer verbindet Uhr und Handy
desselben Menschen. Einsatzdaten gehen von dort zum eingetragenen Server und
sonst nirgendwohin; die Uhr-App kennt weder Serveradresse noch Zugangsdaten
(E-S4-11).

**Der Preis, offen gesagt:** Eine Wear-OS-Uhr ohne Google-Play-Dienste — ein
degoogeltes System, eine Uhr in China — kann die Verbindung zum Handy nicht
herstellen. Die Handy-App bleibt davon unberührt und ist vollständig
benutzbar; die Uhr ist eine Bequemlichkeit, kein Erfordernis. Eine
freie Alternative zum Data Layer gibt es nicht: Er *ist* die Schnittstelle,
über die Wear OS Uhr und Telefon koppelt.

---

## 7. Werkzeuge — nicht ausgeliefert

Alles unter `tools/` ist Prüf- und Erzeugungswerkzeug und **wird nicht auf den
Server geladen** (der Deploy nimmt den Ordner aus). Es benutzt Playwright mit
Chromium sowie Python-Standardbibliothek und Pillow. Diese Abhängigkeiten
sind Teil der Entwicklungsumgebung, nicht der Anwendung, und stehen deshalb
nicht in dieser Aufstellung.

Für die Uhr-App gilt dasselbe in die andere Richtung: `watch/` ist Monkey C
und benutzt ausschließlich das Connect-IQ-SDK von Garmin.

Auch `android/werkzeuge/` gehört hierher: Farb-, Kontrast-, Bildmarken- und
Stromprüfung der Android-Module sind Prüfmittel und liegen in keinem APK.
Die Bauwerkzeuge (Gradle, das Android-Gradle-Plugin, der Kotlin-Compiler)
sind Entwicklungsumgebung wie Playwright und Pillow — die Fassungen stehen in
`android/gradle/libs.versions.toml`.

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

## 7a. Die Modelltabelle der Garmin-Geräte (seit Web 12.9.0)

**Eigener Abschnitt und nicht 7.2**, weil das Gegenteil von Abschnitt 7 gilt:
Diese Datei **wird ausgeliefert**. Sie gehört zur Anwendung, nicht zum
Werkzeug — und sie ist aus fremdem Material erzeugt. Beides zusammen macht
einen Eintrag nötig.

| | |
|---|---|
| Datei | `server/geraetemodelle.php` (erzeugt) |
| Erzeuger | `tools/erzeugen/geraetemodelle.py` |
| Quelle | Connect-IQ-Gerätedateien von Garmin (`compiler.json` je Gerät) |
| Übernommen | Teilenummer → Produktname und Gerätegruppe |
| Nicht übernommen | die Gerätedateien selbst, Auflösungen, Speichergrenzen, Schriften, Bilder |

**Was übernommen wird, sind Sachangaben, keine Datei.** Eine Teilenummer ist
eine Kennzeichnung eines Produkts, ein Produktname sein Name — beide stehen
auf der Verpackung und im Datenblatt. Die Tabelle ordnet das eine dem anderen
zu und ist damit ein Verzeichnis von Tatsachen, nicht eine Vervielfältigung
der Quelle.

**Die Gerätedateien liegen nicht im Repositorium.** Sie gehören Garmin, das
Repositorium ist öffentlich, und eine Bereitstellung für den eigenen Gebrauch
ist etwas anderes als eine Veröffentlichung — dieselbe Überlegung wie beim
Uhr-Prüfstand (`tools/uhr-pruefstand/LIESMICH.md`). Ihre Bereitstellungsadresse
(`CIQ_GERAETE_URL`) steht bewusst nicht hier. Sie liegt seit dem 03.09.2026 in den **Umgebungsvariablen der Arbeitsumgebung** und ist in einer eingerichteten Umgebung bereits gesetzt; erfragt werden muss sie nur, wenn `$CIQ_GERAETE_URL` leer ist.

**Das Erzeugungswerkzeug fällt unter Abschnitt 7** und wird nicht
ausgeliefert. Die erzeugte Datei ist reines PHP ohne Abhängigkeit; zur
Laufzeit wird nichts nachgeladen, die Zusage aus Abschnitt 2 bleibt unberührt.

---

## 7b. Die Wegwerfdomain-Liste (seit P5b/AP3)

**Wie 7a, und aus demselben Grund ein eigener Abschnitt:** Die Datei **wird
ausgeliefert**, sie gehört zur Anwendung und nicht zum Werkzeug, und sie
stammt vollständig aus fremdem Material. Anders als 7a ist sie keine
Zusammenstellung von Sachangaben, sondern die **Quelldatei selbst** — deshalb
zählt hier die Lizenz und nicht die Überlegung zum Verzeichnis von Tatsachen.

| | |
|---|---|
| Datei | `server/wegwerfdomains.txt` (übernommen) |
| Werkzeug | `tools/erzeugen/wegwerfdomains.py` |
| Quelle | `disposable-email-domains/disposable-email-domains`, Datei `disposable_email_blocklist.conf` |
| Lizenz | **CC0 1.0 Universal** (Public Domain Dedication) |
| Stand | 17.09.2026 — **8 883 Domains**, 126 389 Byte, SHA-256 `87bf7187…` |
| Übernommen | die Datei unverändert, eine Domain je Zeile |
| Nicht übernommen | nichts — es gibt nichts anderes in ihr |

**CC0 verlangt keine Namensnennung**, und der Eintrag steht trotzdem hier. Wer
in fünf Jahren wissen will, woher 8 883 Domainnamen in einem Repositorium
kommen, findet es sonst nicht mehr heraus; die Lizenzfrage ist nur der Anlass
für den Eintrag, nicht sein Zweck.

**Die Lizenzdatei des Projekts heißt `LICENSE.txt`**, nicht `LICENSE` — ein
Abruf auf `LICENSE` gibt 404 und ist kein Befund. Nachgesehen am 17.09.2026.

**Verworfen wurden zwei Alternativen** (E-P5b-23): `7c/fakefilter` (BSD-3,
10 686 Einträge, mit Kommentarzeilen und Doppelungen — die Leseseite müsste
normalisieren, und sie läuft bei jeder Registrierung) und
`FGRibreau/mailchecker` (MIT, 56 355 Einträge — sechsmal so groß und damit
sechsmal so viel Risiko, eine echte Domain zu treffen).

**Zur Laufzeit wird nichts geholt** (R36). Der Preis dafür ist, dass die Datei
altert, und zwar unbemerkt: Eine durchgelassene Registrierung sieht aus wie
eine richtige. Der Handgriff steht im Runbook (`docs/Technik.md` 7) und als
Backlog Nr. 230.

---

## 8. Was hier NICHT steht

- **Der Referenzdatensatz** (`tools/referenzdatensatz/`) ist erfunden. Namen,
  Diagnosen und Orte sind frei erdacht; die Straßengeometrie stammt aus
  OpenStreetMap (ODbL) und ist dort im Ordner vermerkt.
- **Die Logos** (`server/assets/images/gen-em_logo_*.svg`) sind eigene
  Dateien des Projekts und stehen unter der Projektlizenz. Regeln für ihren
  Einsatz: `docs/Design.md`, Kapitel 2.
- **Der Inhalt von Impressum und Datenschutzerklärung** ist Sache der
  BetreiberIn. Die Anwendung liefert keinen Text mit (R32); sie stellt nur die
  Seiten und den Editor.

---

## Änderungsverlauf

| Fassung | Was |
|---|---|
| Web 19.3.0 (Backlog-Runde 2) | Abschnitt 5: **ein neues Zeichen**, Tabler Icons **„mail"** (MIT), Outline, Strich 2 im 24-px-Raster wie der übrige Vorrat — `mail.svg`. Es trägt den Knopf „Testmail an mich" auf Betrieb → Status (Backlog Nr. 120). **52 → 53 Dateien.** Kein neuer Fremdbestandteil und kein neuer Laufzeitdienst: derselbe Satz, aus dem die 52 kommen. Gebraucht wurde es, weil der Vorrat kein Zeichen für „E-Mail" hatte (gemessen: 0 Treffer) und alle elf vorhandenen Kopfaktionen eines tragen — eine textnackte wäre eine neue Darstellung gewesen. |
| Web 16.0.0 (S9/AP4) | Abschnitt 5: `veranstaltung.svg` trägt jetzt Tabler Icons **„ticket“** statt „building-stadium“ — dieselbe Quelle, dieselbe Lizenz, eine andere Zeichnung. Grund ist die Lesbarkeit im Kartenschild: Gemessen am 07.09.2026 hält „ticket“ seine eine Binnenfläche von 96 px bis herunter auf 16 px unverändert, während „building-stadium“ bei 18 px zwei seiner vier Binnenflächen auf einen einzelnen Pixel verliert und bei 16 px zwei ganz schließt; der Deckungsgrad liegt bei 33 statt 26 Prozent. M-S9-02 hatte „ticket“ selbst empfohlen und „building-stadium“ bei 20 px „einen Klumpen“ genannt. **Die Zahl der Dateien bleibt 52.** |
| Web 15.9.0 (S9/AP3) | Abschnitt 5: **drei neue Zeichen**, alle Tabler Icons (MIT), Outline, Strich 2 im 24-px-Raster wie der übrige Vorrat — `bergwacht.svg` („mountain“), `veranstaltung.svg` („building-stadium“), `sonstiges.svg` („dots-circle-horizontal“). Sie stehen für die Diensttag-Typen aus M-S9-02. **49 → 52 Dateien.** Kein neuer Fremdbestandteil und kein neuer Laufzeitdienst: Es ist derselbe Satz, aus dem die 49 kommen. |
| Web 15.8.0 (S9/AP2) | Abschnitt 6.2 neu gefasst: Der Adressdienst ist **einstellbar** und **abschaltbar** geworden. Die Vorgabe `https://photon.komoot.io` steht nur noch in `server/geocoder_lib.php`; in den ausgelieferten Browserdateien kommt der Name nicht mehr vor. Zwei Schalter (Installation, Konto) können den Dienst ganz ausschalten — nachgemessen am Netzwerkprotokoll, nicht behauptet. |
| Web 12.9.0 (S6) | Abschnitt 7a: die erzeugte Modelltabelle `server/geraetemodelle.php`. Sie liegt im ausgelieferten Verzeichnis und stammt aus Garmins Connect-IQ-Gerätedateien — übernommen sind Teilenummern und Produktnamen als Sachangaben, nicht die Dateien selbst. |
| S4/D2 | `androidx.test:runner` 1.7.0 in Abschnitt 6a — der Läufer für die instrumentierten Prüffälle (Keystore, Wearable-Erreichbarkeit). Test-only, Apache-2.0, nicht im App-APK. |
| Android 0.11.0 (S4-Rest) | Abschnitt 6a: **CameraX und ZXing ausgetragen** (R63, Backlog Nr. 84). Der Adress-QR entfällt, damit ihr einziger Verbraucher; die `CAMERA`-Berechtigung geht mit. Vier Fremdbestandteile werden zwei, das APK wird um 1,81 MB kleiner. |
| S4/D1 | Abschnitt 6a: die Android-Apps. Vier Fremdbestandteile, drei davon Apache-2.0; die vierte (`play-services-wearable`) ist proprietär und bekommt eine eigene Begründung — sie steckt nicht im APK, ist auf **eine** Datei eingegrenzt, überträgt nichts nach außen, und der Preis (keine Uhr ohne Play-Dienste) steht dabei. |
| Web 10.3.0 (S2/AP4) | Abschnitt 7.1: das vendorierte GPX-1.1-Schema von TopoGrafix, mit Herkunft und SHA-256. Es liegt unter `tools/` und wird zur Laufzeit nie geladen. |
| Web 9.13.0 (P3/O12) | Erstfassung. Zusammengetragen aus den Dateiköpfen unter `server/assets/vendor/`, dem Stylesheet-Kommentar zu den Schriften, `LICENSE-tabler-icons.txt` und den Adressen in `map_layers.js`, `ortsfeld.js` und `ortswahl.js`. |
