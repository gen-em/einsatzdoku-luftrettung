# Einsatzdoku — Technische Dokumentation

*Stand: 24.08.2026 · Bedienung: `Handbuch.md` · Schnittstelle: `JSON-Vertrag.md` ·
Historie: `CHANGELOG.md`.*

## 1. Architekturüberblick

```
┌─────────────────┐  HTTPS POST /ingest.php   ┌──────────────────────────┐
│ Uhr-App         │  JSON (JSON-Vertrag)      │  Webspace                │
│ (derzeit Garmin,│ ────────────────────────► │  PHP ≥ 8.1  + MySQL      │
│  Monkey C)      │  X-Device-Id / X-Api-Key  │                          │
└─────────────────┘                           │  ingest.php   (Uhr-API)  │
                                              │  api/…        (Lese-API) │
┌─────────────────┐  HTTPS (Session-Login)    │  *.php        (Seiten)   │
│ Browser         │ ────────────────────────► │  update.php   (Migration)│
└─────────────────┘                           │  install.php  (Setup)    │
                                              └──────────────────────────┘
```

Grundsätze: Der Server ist **geräteneutral** (kennt nur den JSON-Vertrag);
Zeiten werden **UTC** gespeichert und in **Europe/Berlin** angezeigt; jede
Lese- und Schreiboperation ist nach `user_id` getrennt; die Uhr löscht lokale
Daten erst nach Server-Bestätigung.

## 2. Verzeichnisstruktur

```
<repo>/
├── docs/                  Handbuch, Technik, Changelog, Backlog, JSON-Vertrag,
│                          Design (Gestaltungsrichtlinie: Token, Schwellen,
│                          Symbole, Bausteine, Seitentypen — verbindlich),
│                          Lizenzen (Fremdbestandteile mit Version und Lizenz),
│                          Backup-Format, Export-Format,
│                          Geraete-Eingabe (gemessenes Eingabeverhalten je Uhr),
│                          Uhr-Layout (Layoutregeln der Uhr-Oberflächen),
│                          Rahmenplan (Steuerung) und Rahmenplan-Archiv (Werdegang),
│                          konzepte/ (laufende Konzepte und Prüfdokumente; darin
│                          erledigt/ mit dem Bestand bis S3 samt P3-Mockups)
├── server/                komplette Web-App (wird per FTPS deployt)
│   ├── version.php        WEB_VERSION (einzige Stelle für die Versionsnummer)
│   ├── db.php             PDO, Helfer (e/asset/favicon_tags/logo_src/fmt_local/local_to_utc),
│   │                       Einstieg der Wartung huckepack (run_cleanup_if_due)
│   ├── ui.php             Seitenhülle (ui_seite_start/-_ende), Kopf-/Seitenleisten,
│   │                       Fußzeile, Meldungszeile, Abbruchseite, Krypto-Rüstzeug
│   ├── auth_guard.php     Session/CSRF/Rollen (Rolle+Existenz je Anfrage aus der DB,
│   │                       Sitzungszähler, ist_admin())
│   ├── auth_salt.php      KDF-Salt (mit Pseudo-Salt gegen User-Enumeration)
│   ├── login/logout/reset_request.php   Auth-Flows
│   ├── pw_handling.php    Passwortvergabe über Einmal-Link: Erstvergabe (erzeugt
│   │                      Inhalts- + Wiederherstellungsschlüssel) und Reset
│   ├── index.php          Tagesübersicht (Karte + Tabelle)
│   ├── einsatz.php        Einsatzansicht · einsatz_form.php Nachtragen/Bearbeiten
│   ├── zeitraum.php       Jahres-/Monatsübersicht (Karte, Statistik, Tabelle)
│   ├── suche.php          Suche über den gesamten Bestand (filtert im Browser, s. u.)
│   ├── mission_fields.php Zentraler Feldkatalog der Zusatzfelder
│   ├── mission_fields_lib.php  Abgeleitete Sichten auf den Feldkatalog
│   │                       (mf_tagesspalten() = Spalten der Tagestabelle,
│   │                        mf_optionen() = Wert/Beschriftung eines Auswahlfelds,
│   │                        mf_ort_spalten() = Koordinatenspalten eines Ortsfelds,
│   │                        mf_show_if() + mf_gates_erfuellt() = Sichtbarkeit)
│   ├── tageszuordnung_lib.php  Einsatz verschieben · Datum eines Tages ändern
│   ├── einsatz_verschieben.php  die zugehörige Seite
│   ├── einstellungen.php  Profil/Standorte/Rettungsmittel/Backup/Geräte
│   │                       (Reiter `?t=`; `t=stammdaten` ist die Weiche auf
│   │                        den alten, geteilten Punkt „Standortdaten")
│   ├── import.php         Import/Export (eigene Seite, erscheint als Eintrag
│   │                      der Einstellungs-Leiste)
│   ├── admin_users.php + admin_user.php  NutzerInnen (Liste · Kontoseite)
│   │                       Die Liste ist seit Web 9.9.0 serverseitig gesucht,
│   │                       gefiltert und seitenweise (50 je Seite), mit
│   │                       Statuskacheln und Sammelleiste
│   │                       Die Kontoseite ist seit Web 9.8.0 die Drehscheibe
│   │                       eines Kontos: Kontodaten, Geräte, Backups
│   │                       dieses Kontos, Abonnement (Platz für R33), Löschung
│   ├── admin_stammdaten.php  Systemweite Stammdaten aller sechs Typen
│   │                       (Reiter `?t=standorte` / `?t=rettungsmittel`;
│   │                        seit Web 9.10.0 EIN Menuepunkt „Stammdaten
│   │                        systemweit" mit Segmentwahl in der Titelzeile)
│   ├── stammdaten_ui.php  Zeile und Anlegen-Formular der Stammdatenlisten —
│   │                       eine Fassung fuer die Kontoansicht
│   │                       (einstellungen.php) und die Adminansicht
│   │                       (admin_stammdaten.php), seit Web 9.10.0
│   ├── diensttag_neu.php  Diensttag von Hand anlegen · diensttag_datum.php Datum ändern
│   │                       · diensttag_zusammenfuehren.php  mehrfach gestartete Dienste
│   │                         wieder zu einem Diensttag vereinen
│   ├── diensttag_lib.php  Diensttage anlegen, zuordnen, einfrieren, auflisten
│   ├── nachbearbeitung.php + nachbearbeitung_lib.php  einmalige Nachträge nach der Migration
│   ├── einsatz_loeschen.php · diensttag_loeschen.php · papierkorb.php  Löschen mit Vorschau
│   ├── ingest.php         Uhr-/Fremdquellen-Endpunkt (Auth, Idempotenz)
│   ├── pair.php           Gerätekopplung per Code, und Trennen einer
│   │                      bestehenden Kopplung (JSON-Vertrag 1a/1b)
│   ├── geraete_lib.php    Liest den Block `geraet` einer Kopplung — die
│   │                      EINZIGE Stelle, die ihn auslegt (Uhr- und
│   │                      Handy-Form), und die Beschriftungen der Gerätelisten
│   ├── geraetemodelle.php Teilenummer → Modellname und Geräteart. **ERZEUGT**
│   │                      (`tools/geraetemodelle/`), nicht von Hand ändern
│   ├── spur_lib.php       Spurpunkte lesen und schreiben — die EINZIGE Stelle,
│   │                       die `track_points`/`track_blobs` anfasst (4.97)
│   ├── gpx_lib.php        GPX 1.1 aus einer oder mehreren Spuren — die
│   │                       EINZIGE Stelle, die GPX schreibt (4.97b)
│   ├── gpx.php            der Abruf. Bewusst NICHT unter api/: Ein Link, den
│   │                       eine Nutzerin anklickt, braucht bei Sitzungsende
│   │                       die Anmeldeseite und kein JSON (4.97b)
│   ├── tag_spuren.php     die Spuren eines Diensttages, einzeln oder mehrere
│   │                       auf einmal abrufbar: Karte plus chronologische
│   │                       Liste, Einsätze UND Ruhesegmente (4.97b)
│   ├── jobs.php           Einstieg der Hintergrundjobs: Kommandozeile, Adresse
│   │                       mit Token, huckepack auf einer Anfrage (4.97a)
│   ├── jobs_lib.php       Katalog und Ausführung der Jobs (Häppchen, Zustand,
│   │                       Sperre)
│   ├── backup_lib.php     Backup-Serialisierung (Kern mit oder ohne Spuren)
│   │                       · trash_lib.php Papierkorb-Logik
│   ├── adminbackup_lib.php  Admin-Backups: Ablage (ZIP, Fassung 2),
│   │                       Übersicht, Freigabe, Speichergrenze, Auftrag (A8, S2/AP6)
│   ├── admin_sicherungen.php  Adminseite dazu — seit Web 9.10.0 nur noch
│   │                       Regeln, Ablage und Backups ohne Konto;
│   │                       die Konten stehen in admin_users.php, die
│   │                       Pakete eines Kontos auf dessen Kontoseite
│   │                       · sicherungen/ die Ablage selbst
│   │                       (entsteht nur auf dem Server, im Deploy ausgenommen)
│   │                       · sicherungen/komplett/ die Komplett-Backups
│   │                       · sicherungen/eingang/ was wiederhergestellt
│   │                         werden soll — von Hand dorthin gelegt
│   ├── sicherungsziel_lib.php  Backup-Ziele (S2/AP7): Schnittstelle
│   │                       `Zielweg` und drei Adapter — FTP und FTPS über
│   │                       ext/ftp, SFTP über phpseclib; dazu Pflege in der
│   │                       Tabelle backup_targets, „Verbindung prüfen" und
│   │                       der Versandschub
│   ├── admin_sicherungsziele.php  Adminseite dazu: Ziele anlegen und prüfen,
│   │                       Serverschlüssel nachtragen, Versand ein/aus
│   ├── komplett_lib.php   Komplett-Backup der Installation (S2/AP8):
│   │                       eigener SQL-Dump in Häppchen (ein Statement je
│   │                       Zeile, INSERT-Stapel bis 1 MB, einspielbare
│   │                       Reihenfolge), gzip, Siegel EDKOMP1; dazu Ablage,
│   │                       Aufbewahrung, Zeitplan und die Wege heraus
│   ├── admin_komplettsicherung.php  Adminseite dazu: erzeugen mit Fortschritt,
│   │                       Zeitplan, Stände herunterladen (unverschlüsselt
│   │                       für mysql, oder unter einer Passphrase), löschen
│   ├── wiederherstellen.php  Der Rückweg — die Lücke zwischen install.php
│   │                       und update.php. Nur bei LEERER Datenbank, mit
│   │                       Nachweisdatei, liest aus sicherungen/eingang/,
│   │                       spielt in Durchgängen ein. Kein Hochladen
│   ├── serverkrypto_lib.php  Der Serverschlüssel aus config.php (32 B) und
│   │                       die Versiegelung `edsk1:` (AES-256-GCM, Zweck in
│   │                       den Zusatzdaten). Das EINZIGE Geheimnis, das der
│   │                       Server selbst hat — es öffnet keine Patientendaten
│   ├── vendor/            fremde Bibliotheken, die auf dem SERVER laufen
│   │                       (phpseclib3, ParagonIE/ConstantTime), gesperrt per
│   │                       .htaccess, geladen über vendor/laden.php;
│   │                       Herkunft und Prüfsummen in HERKUNFT.md
│   ├── validate_lib.php   Gemeinsame Prüfschicht für Einsatzdaten (alle vier Schreibwege)
│   ├── ratelimit_lib.php  Ratenschutz (Konto + IP, in der Datenbank)
│   ├── session_lib.php    Sitzungsende mit Räumung im Browser (Abmelden, Ablauf,
│   │                       gelöschtes Konto, Passwortwechsel)
│   ├── email_lib.php      E-Mail: Normalisierung, Prüfung, Dublettenerkennung
│   │                       (ohne Abhängigkeiten — auch für install.php)
│   ├── impressum.php · datenschutz.php   die beiden OEFFENTLICHEN Seiten
│   │                      (R32) — zwei Zeilen je Datei, der Rest steht in
│   │                      rechtstext_seite.php
│   ├── rechtstext_seite.php  die gemeinsame Seite dahinter: liest die Sitzung,
│   │                      ohne sie zu erzwingen (Leerzustand mit Admin-Weg)
│   ├── rechtstexte_lib.php   Ablage, Pruefung und der eingeschraenkte
│   │                      Markdown-Renderer rt_html() — die EINZIGE Stelle des
│   │                      Projekts, an der aus einer Eingabe HTML wird
│   ├── admin_rechtstexte.php  Editor dazu (Administration)
│   ├── apk_lib.php        Was in server/apk/ liegt — Name, Größe, Fassung,
│   │                       Datum, SHA-256 (S4/A1, siehe 4.97g)
│   │                       · apk.php liefert die Datei aus
│   │                       · apk/ die Dateien selbst (entstehen nur auf dem
│   │                         Server, im Deploy ausgenommen)
│   ├── install.php        Serverinstallation · update.php Migrations-Runner
│   ├── smtp.php           SMTPS-Versand + Abschluss der Antwort vor langsamer Arbeit
│   ├── api/               day.php · mission.php · range.php · suchindex.php ·
│   │                      backup_data.php (`?teil=kopf` und
│   │                      `?teil=eintraege&ab=&anzahl=`, ohne Parameter die
│   │                      volle Nutzlast) · backup_restore.php (der Kopf) ·
│   │                      backup_eintraege_restore.php (ein Eintragsfenster) ·
│   │                      backup_spuren.php und backup_spuren_restore.php
│   │                      (die Spurteile der Fassung 4, S2/AP5) ·
│   │                      import_commit.php (Abgleich + Übernahme des Imports) ·
│   │                      schneiden.php (Einsatz aus einem Ruhesegment schneiden
│   │                      und zurücknehmen, S4/A2b — siehe 4.97e) ·
│   │                      gpx_import.php (GPX herein, S4/A3 — siehe 4.97f) ·
│   │                      export_data.php (nur lesend, Rohdaten für den Export) ·
│   │                      adminbackup_freigabe.php (freigegebenes Backup für die NutzerIn)
│   ├── assets/            style.css (Schriften werden lokal ausgeliefert, s. u.),
│   │                      crypto.js (WebCrypto), unlock.js (Entsperrdialog, s. u.),
│   │                      zeitfeld.js (Zeiteingabe im 24-Stunden-Format, s. u.),
│   │                      keyguard.js (Bindung/Lebensdauer des Inhaltsschlüssels),
│   │                      pwquality.js (Passwortgüte), patient.js, daylist.js, confirm.js,
│   │                      html.js (HTML-Maskierung, die eine Fassung für alle Seiten),
│   │                      missiontable.js (gemeinsame Einsatztabelle, s. u.),
│   │                      map_fullscreen.js + map_layers.js (gemeinsame Leaflet-Controls, s. u.),
│   │                      import.js (Pipeline) + import_profiles.js (Formate) + import_ui.js (Bedienung),
│   │                      export.js (alle drei Exportprofile, Aufbau im Browser),
│   │                      ortsfeld.js (Ortsfeld-Komponente: Bezeichnung + optionale
│   │                       Koordinaten, sechs Verwendungen, s. u.),
│   │                      luftlinie.js (gestrichelte Verbindung ohne GPS-Track, s. u.),
│   │                      schneiden.js (Karte „Ruhesegmente" und Schneide-Bereich
│   │                       der Tagesansicht, S4/A2b — siehe 4.97e),
│   │                      geo.js (EdGeo: Marker-Satz und Spurfarben der Karten, s. u.),
│   │                      ortswahl.js (Geolocation + Kartendialog am Ortsfeld, s. u.),
│   │                      blatt.js (Aktions- und Sortierblätter) + schublade.js (mobile Leiste),
│   │                      dialog.js (öffnet Dialoge, die im Markup stehen, und füllt sie
│   │                       aus `data-w-*` des öffnenden Knopfes — ein Dialog für viele Zeilen),
│   │                      symbol.js (edSymbol() — dieselbe Zeichenkette wie ui_symbol()
│   │                       in PHP; kein Zeichen liegt als Inline-Pfad im Code)
│   │   └── vendor/        xlsx.full.min.js — SheetJS Community Edition 0.18.5, Apache-2.0 ·
│   │                      zipjs.min.js — zip.js 2.8.34, BSD-3-Clause (ZIP + AES-256) ·
│   │                      leaflet/ — Leaflet 1.9.4, BSD-2-Clause (Karten; CSS, JS, images/);
│   │                      alle lokal vendoriert (kein CDN), Herkunft und SHA-256 im Dateikopf
│   │   └── fonts/         Bricolage Grotesque 500/600 und Open Sans 400/600/700 als woff2,
│   │                      je Subset latin und latin-ext (@fontsource, OFL-1.1)
│   │   └── images/        Logos als SVG (farbig + weiss) je Hubschrauber und Fahrzeug,
│   │       │                favicon.png + favicon-fahrzeug.png (erzeugt aus den
│   │       │                Logodateien, s. tools/logos/); das Fahrzeug-Logo ist bis
│   │       │                zur Zulieferung ein PLATZHALTER (gestrichelter Rahmen)
│   │       └── symbole/    44 Zeichen als je eine SVG-Datei (Tabler Icons, MIT;
│   │                       ein eigener Entwurf), 24 x 24, currentColor, Anker
│   │                       <g id="i">; dazu LICENSE-tabler-icons.txt und
│   │                       LIESMICH.md mit der Zuordnung Datei -> Tabler-Name ->
│   │                       Verwendung. Eingebunden per Verweis, nicht eingebettet
│   ├── favicon.ico        Browser-Symbol im Wurzelverzeichnis
│   ├── config.example.php Vorlage der config.php (die selbst nur auf dem Server
│   │                      liegt und vom Deploy ausgenommen ist)
│   ├── demo/              Fixture des Demo-Kontos (fixture.json.gz) — das
│   │                      EINZIGE Erzeugnis der Phase P1, das ausgeliefert
│   │                      wird; erzeugt von tools/referenzdatensatz/fixture/
│   ├── demo_lib.php       Demo-Konto: anlegen, zurücksetzen, entfernen,
│   │                      Reset-Fälligkeit (Abschnitt 4.99a)
│   ├── admin_demo.php     die zugehörige Adminseite
│   ├── schema.sql         Voll-Schema für Neuinstallationen
│   ├── migrations/        Migrationen als nachlesbare SQL-Dateien (ausgeführt wird über update.php)
│   └── .htaccess          HTTPS-Zwang, Dateisperren, Sicherheits-Kopfzeilen
├── watch/                 Connect-IQ-Projekt (Monkey C)
│   ├── manifest.xml, monkey.jungle
│   ├── resources/         Vorgabe für alle Geräte
│   ├── resources-<gerät>/ geräteabhängige Überschreibungen (Launcher-Icon)
│   └── source/            s. Abschnitt 5
├── android/               Handy- und Wear-OS-App (Kotlin, Compose) — S4
│   ├── handy/             das Telefon: Kopplung, Aufzeichnung, Dienstklammer,
│   │                      Phasen und Einsätze, Senden an ingest.php
│   ├── uhr/               Wear OS: dasselbe Bedienbild am Handgelenk, aber
│   │                      OHNE Zugangsdaten — sie spricht nur mit dem Handy
│   ├── gemeinsam/         Quelltext, den beide Module einbinden (E-S4-24):
│   │                      Nachrichtenformat, Data-Layer-Weg, Kennungen,
│   │                      Modus, Phasen, Farben, Bildmarke
│   ├── gradle/            libs.versions.toml — die vollständige Liste der
│   │                      Fremdbestandteile, eine Nummer je Bestandteil
│   ├── werkzeuge/         Farb-, Kontrast-, Bildmarken- und Stromprüfung
│   ├── mockups/           Vorher/Nachher-Bilder aus dem Prüfstand
│   └── LIESMICH.md        Bauanleitung, Entscheidungen, Prüfstand
├── tools/                 Werkzeuge, werden nicht ausgeliefert
│   ├── abmelde-probe/     zeigt, was der Abmeldeweg im sessionStorage
│   │                      zurücklässt — Beleg zu V-10 (s. LIESMICH.md)
│   ├── eingabe-probe/     Connect-IQ-Probe zum Ausmessen des Eingabe-
│   │                      verhaltens neuer Zielgeräte (s. Abschnitt 5.2)
│   ├── fristprobe/        belegt die Angleichung der Schlüsselfrist (R44, S6):
│   │                      spielt eine Schicht durch und zählt, wie oft der
│   │                      Inhaltsschlüssel neu entpackt werden muss — vorher
│   │                      17, nachher 1 (s. LIESMICH.md)
│   ├── geraetemodelle/    erzeugt server/geraetemodelle.php (Teilenummer auf
│   │                      Modellname) aus den Connect-IQ-Gerätedateien und
│   │                      löst mit `nachaufloesen.php` bestehende Zeilen
│   │                      nachträglich auf. Braucht eine Zuarbeit, die nicht
│   │                      im Repositorium steht (s. LIESMICH.md)
│   ├── geraeteprobe/      hält das Auslesen des Kopplungsblocks `geraet`
│   │                      gegen beide Geräteformen und gegen Unsinn (R42, S6);
│   │                      ohne Datenbank und ohne Gerät (s. LIESMICH.md)
│   ├── gpxprobe/          prüft den GPX-Abruf (S2/AP4): gültig gegen das
│   │                       vendorierte amtliche GPX-1.1-XSD, Punkt für Punkt
│   │                       gegen die browsergebauten Referenzdateien,
│   │                       Kennzeichnung in Datei, Dateiname und Seite
│   │                       (s. LIESMICH.md)
│   ├── ingestprobe/       prüft die Uhr-Schnittstelle nach der Ausdünnung
│   │                      (S2/AP3) über ECHTES HTTP: Nachzügler an Stufe 2
│   │                      werden angenommen, Punkte hinter einer Stufe-3-Spur
│   │                      verworfen UND quittiert. Legt ihr eigenes Konto an
│   │                      und räumt es ab (s. LIESMICH.md)
│   ├── jobprobe/          prüft den Job-Rahmen (S2/AP2): dass alle drei
│   │                      Auslöser denselben Rückstand abtragen, dass die
│   │                      gemeldete Zahl stimmt, dass die Sperre greift und
│   │                      verfällt, und dass der Huckepack-Weg wenig und
│   │                      selten trägt. Legt eigene Waisen an und räumt hinter
│   │                      sich auf — ändert am Bestand nichts (s. LIESMICH.md)
│   ├── maskierungs-probe/ Vorher/Nachher-Probe zur Maskierung der
│   │                      Einsatztabelle (Backlog Nr. 22, s. LIESMICH.md)
│   ├── messstand/         stellt ein Konto mit 5000 Einsätzen her — aus der
│   │                      Referenz-Backup vervielfältigt und über den
│   │                      REGULÄREN Wiederherstellungsweg eingespielt — und
│   │                      misst daran die Zielzahlen von S2: Suche,
│   │                      Tagesansicht, Sichern, Speicherspitzen,
│   │                      Tabellengrößen. Browserprobe unter CPU-Drossel 6×.
│   │                      Riegel: füllt nur ein Konto mit dem Präfix
│   │                      „messstand" (s. LIESMICH.md)
│   ├── referenzdatensatz/ erfundener Beispielbestand (16 Diensttage,
│   │   │                  87 Einsätze) — Demo-Konto UND Regressionsreferenz
│   │   ├── quelldaten/    die Wahrheit: je Diensttag ein JSON, dazu Schema
│   │   │                  und Prüfung (Abdeckungsmatrix, keine realen Namen)
│   │   ├── generator/     erzeugt Ingest-Payloads, Formulardaten, CSV, GPX;
│   │   │                  fester Zufallssamen, zwei Läufe gleiches Ergebnis
│   │   ├── einspielen/    spielt alles über die REGULÄREN Wege ein, kein SQL
│   │   ├── browser/       was es nur im Browser gibt: CSV-Import, Angriffs-
│   │   │                  werte (P-07), Exporte, Umläufe, Papierkorb-Mischfall,
│   │   │                  Abnahme der Demo-Funktion
│   │   ├── referenz/      die eingecheckten Referenz-Exporte
│   │   ├── vergleich/     Vergleichswerkzeug und Kreislauftests
│   │   └── fixture/       erzeugt server/demo/fixture.json.gz
│   ├── design/            erzeugt die Tabellen von docs/Design.md aus den
│   │                      Quellen: Token aus :root, Schwellen aus den
│   │                      @media-Bloecken, Symbole aus dem Vorrat, Bausteine
│   │                      aus ui.php. Eine abgeschriebene Tabelle stimmt am
│   │                      Tag des Abschreibens und danach nie wieder
│   │                      (s. LIESMICH.md)
│   ├── logos/             erzeugt die Favicons AUS den Logodateien, damit beide
│   │                      nicht auseinanderlaufen (s. LIESMICH.md)
│   ├── pruefkonten/       legt einen Bestand von 300+ Konten mit gemischten
│   │                      Backup-Staenden an (fester Zufallsstartwert) —
│   │                      fuer Seitenwechsel, Filter und Sammelauswahl der
│   │                      NutzerInnen-Liste (P-P3-16)
│   ├── rechtstexte/       Angriffsprobe fuer den Markdown-Renderer der
│   │                      Rechtstexte: 81 Proben in acht Gruppen plus eine
│   │                      Positivlisten-Schranke ueber JEDE erzeugte Ausgabe
│   │                      (s. LIESMICH.md)
│   ├── screenshots/       nimmt alle Seiten in acht Breiten von 360 bis 1920 px
│   │                      auf, je Seite ein Kontaktbogen; misst dabei
│   │                      waagerechten Überlauf, Konsolenfehler und Knopfhöhen.
│   │                      Seit Web 9.10.1 prueft er nach JEDEM Aufruf, ob er
│   │                      die richtige Seite vor sich hat, und meldet sich bei
│   │                      Bedarf neu an; ein nicht aufloesbarer Platzhalter
│   │                      ergibt kein Bild (F-P3-AQ).
│   │                      kontrast.py rechnet die Kontraste der Token nach
│   │                      (s. LIESMICH.md)
│   ├── spurprobe/         prüft den Rundlauf des Blob-Formats SPUR1 über den
│   │                      ganzen Referenzbestand: Punkte → Blob → Punkte, dazu
│   │                      Kopf, Ablehnung fremder Fassungen und die Frage, ob
│   │                      die Leser vor und nach der Verdichtung dasselbe
│   │                      liefern. Verdichtet in einer Transaktion, die sie
│   │                      zurückrollt — ändert nichts (s. LIESMICH.md)
│   ├── stilvergleich/     rechnet nach, dass eine Änderung an style.css das
│   │                      Erscheinungsbild nicht verändert: Kaskadenvergleich
│   │                      plus berechnete Stile im Browser, 13 Breiten.
│   │                      Ruhte waehrend P3, in O12 neu geeicht; ab P4 wieder
│   │                      Pflicht bei CSS-Umbauten (s. LIESMICH.md)
│   ├── komplettprobe/     fährt den ganzen Zyklus des Komplett-Backups
│   │                      (S2/AP8): erzeugen in Häppchen, versiegeln, öffnen,
│   │                      in eine LEERE Datenbank einspielen und Tabelle für
│   │                      Tabelle vergleichen, aufs Backup-Ziel schieben.
│   │                      76 Erwartungen. Arbeitet in einer Kopie unter /tmp,
│   │                      liest aber aus der ECHTEN Datenbank
│   ├── versandprobe/      prüft die drei Backup-Ziel-Adapter (S2/AP7)
│   │                      gegen ECHTE Server auf 127.0.0.1: Rundlauf je
│   │                      Protokoll, Fingerabdruck als Riegel, Fehlerfälle,
│   │                      Versiegelung der Zugangsdaten. 115 Erwartungen.
│   │                      ZWEI Sätze Gegenstellen, und beide werden
│   │                      gebraucht: gegenstellen.py (pyftpdlib/paramiko,
│   │                      portabel) und echte_gegenstellen.sh (vsftpd und
│   │                      OpenSSH, braucht root) — vsftpd kennt kein MLSD und
│   │                      fährt damit als einziges den Rückfallzweig der
│   │                      Verzeichnisliste. Was sie NICHT prüfen kann — ein
│   │                      echtes Ziel im Internet — steht an erster Stelle
│   │                      ihrer LIESMICH.md
│   ├── uhr-bilder/        rastert Launcher-Symbole und Bildmarken der Uhr
│   │                      aus den beiden SVG unter server/assets/images/.
│   │                      Das Rezept ist aus den vorhandenen Dateien
│   │                      zurückgerechnet und reproduziert sie bitgleich
│   │                      (s. LIESMICH.md)
│   ├── uhr-pruefstand/    baut SDK und Simulator auf einem nackten Linux-
│   │                      Rechner auf, übersetzt die Uhr-App und startet
│   │                      sie ohne Fensteroberfläche (s. Abschnitt 5.2b)
│   ├── vollstaendigkeit/  prüft, ob beim Redesign etwas verlorengegangen ist
│   │                      (jede Klasse des alten Stylesheets hat eine Regel
│   │                      oder steht mit Begründung auf der Streichliste) und
│   │                      ob jeder Wert in :root steht. Drei Hilfslisten mit
│   │                      Begründungspflicht: streichliste.md, ausnahmen.md,
│   │                      ohne-regel.md (s. LIESMICH.md)
│   ├── freigabeprobe/    der Freigabeweg MIT Wiederherstellungsschlüssel
│   │                      (E20): Kasten erscheint, falscher Schlüssel wird
│   │                      abgewiesen, richtiger schlüsselt um. Die Krypto
│   │                      entsteht im Browser über assets/crypto.js — PHP
│   │                      legt sie nur ab (s. LIESMICH.md)
│   ├── wiederherstellungs-probe/
│   │                      Grenzfälle von edbak_restore(), die der Kreislauf
│   │                      nicht herstellen kann: Papierkorb-Mischfall,
│   │                      kaputte Datei, Adminpaket Fassung 2, Speichergrenze
│   │                      und der Auftrag „Alle sichern" (E-S1-04/19, S2/AP6,
│   │                      Backlog Nr. 31/35; s. LIESMICH.md)
│   └── wortliste/         zählt nach, ob sichtbare Texte und normative
│                          Dokumentation neutral von Land und Luft sprechen:
│                          Sperrliste, Ausnahmeliste mit Begründungen, drei
│                          Zahlen je Bereich (s. LIESMICH.md)
└── .github/workflows/deploy.yml   FTPS-Deploy (nur server/, exkl. config)
```

## 3. Datenmodell (MySQL)

| Tabelle | Zweck / Besonderheiten |
|---|---|
| `users` | Login (E-Mail = Username), Rolle `user`/`admin`; Löschen kaskadiert alles; **Browser-Schlüsselableitung** (`kdf_salt` + `kdf_iter` = Rundenzahl je Konto) und **E2E-Schlüssel-Hüllen** `pat_wrap_pw`/`pat_wrap_rc` (Inhaltsschlüssel passwort- bzw. wiederherstellungsverpackt), dazu `pat_key_check` = im Browser gerechnete Prüfsumme des Inhaltsschlüssels (NULL bei Altbestand — ein gültiger Zustand); `session_epoch` = Zähler, mit dem ein Passwortwechsel offene Sitzungen beendet (**seit Web 4.5.0 in Gebrauch**). `password_hash` ist NULL, solange das Passwort noch nicht gesetzt wurde — ein solches Konto kann sich nicht anmelden. Die **Sortierregel der E-Mail-Spalte ist ausdrücklich festgelegt** (`utf8mb4_unicode_ci`); ohne das hinge die Anmeldung an der Standardregel der jeweiligen Installation. Seit Web 4.5.0 schreibt und sucht der Code zusätzlich kleingeschrieben (`email_lib.php`), hängt also nicht mehr von der Sortierregel ab; **Bestandszeilen bleiben unverändert**, die ci-Regel trifft sie ohnehin. Seit Web 9.7.0 dazu **`logo_wahl`** (`''` = Standard der Installation, sonst `hubschrauber` / `fahrzeug` / `wechselnd`, E-P3-20) — der Leerstring ist die Vorgabe, damit ein späterer Wechsel des Installationsstandards bestehende Konten erreicht. Seit Web 9.8.0 dazu **`last_login`** (DATETIME NULL) — der Zeitpunkt der letzten **Anmeldung**, geschrieben von `login.php` und sonst nirgends; Kontoseite und NutzerInnen-Liste zeigen ihn. Der Bestand bekommt bei der Migration NULL und nicht NOW(): Der Wert wäre sonst erfunden, und zwar genau in der Spalte, mit der man ungenutzte Konten sucht. NULL erscheint als „—“ |
| Backup | `backup_lib.php` | Das Format ist seit Web 4.5.2 **aufgezählt** statt „alles, was in der Tabelle steht". Neue Spalten sind damit nicht mehr automatisch enthalten — sie einzutragen ist eine Entscheidung. Draußen: `id`/`user_id`/`device_id` (interne Verweise) und `other_resources` (tote Altspalte seit der Migration `2026_07`). **Bekannt:** `site_ele_m` ist im Backup, kommt beim Einspielen aber nicht zurück — der Einspielweg schreibt nur die Felder aus `mission_fields.php` plus `pat_blob`. |
| `password_resets` | Token-Hashes (sha256); 1 h bei „Passwort vergessen“, 24 h bei Neuanlage und Installation; der Job `aufraeumen` entsorgt Altbestand. Seit Web 4.4.0 gilt **höchstens ein offener Token je Konto**: Eine neue Anforderung entwertet alle vorherigen. Seit Web 4.5.0 entwertet auch **jeder Passwortwechsel** alle offenen Token des Kontos — der 24-Stunden-Einladungslink entsteht auf einem anderen Weg und hätte den soeben gewählten Zustand sonst überschreiben können |
| `devices` | Upload-Zugang je Gerät: `device_id` (öffentlich, seit Web 4.5.1 aus **16** statt 4 Zufallsbytes — Bestandsgeräte behalten die kurze Kennung) + `api_key_hash`; **`active`-Flag** (deaktivieren statt löschen); virtuelle Geräte `manual-<userId>` für Handeinträge (dauerhaft inaktiv, aus Listen gefiltert). Seit Web 4.4.0 **höchstens `MAX_GERAETE` (5) echte Geräte je Konto**, aktive wie deaktivierte — die virtuellen zählen nicht mit. Seit Web 12.9.0 dazu die **Gerätekennung** (R42): `geraet_art` (`uhr`/`handy`/`sonstiges`), `geraet_modell` (aufgelöster Klarname, **VARCHAR(191)** — die Gerätedateien liefern Sammelnamen bis 153 Zeichen; die zunächst gewählten 64 waren geraten und sind mit Web 12.9.1 nachgezogen) und `geraet_teil` (die Rohangabe des Geräts — bei Garmin die Teilenummer, beim Handy Hersteller und Modell). **Alle drei sind dauerhaft NULL-bar**, und das ist keine Nachlässigkeit: Vier Wege legen ein Gerät an — Kopplung, Handanlage, virtuelles Gerät, Demo-Bestand —, und nur die Kopplung weiß etwas über das Gerät. Ein `NOT NULL DEFAULT 'unbekannt'` hätte daraus eine Aussage gemacht, wo keine ist; „unbekannt" ist eine Sache der Anzeige. **Bestandsgeräte bleiben leer**, bis sie neu koppeln — die Angabe entsteht ausschließlich beim Koppeln, und eine bereits gekoppelte Uhr wird nicht rückwirkend gefragt. **Drei Spalten statt der in R42 genannten zwei:** Die Rohangabe steht daneben, weil der Modellname aus einer erzeugten Tabelle stammt und ein künftiges Gerät sonst unwiederbringlich auf „unbekannt" fiele. Siehe Abschnitt 5 |
| `missions` | Einsatz; `UNIQUE(device_id, client_ref)` = Idempotenz-Anker; **`day_id`** = Fremdschlüssel auf `days` (bis Web 5.10.0: die Spalte `day` mit dem Kalenderdatum); **`manual`-Marker** — ausschließlich Schutz vor Uhr-Überschreiben, NICHT „von Hand angelegt"; **`origin`** (`watch`/`manual`/`import`) = Herkunft, wird beim Anlegen gesetzt und nie wieder geändert; **`edited`** = wurde nach dem Anlegen verändert; `deleted_at`/`deleted_with_day` (Papierkorb); Zusatzfelder lt. `mission_fields.php`; **`site_ele_m`** = berechnete Einsatzort-Höhe (kein Formularfeld, siehe `site_elevation_lib.php`); **`crew_override`** = abweichende Besatzung je Einsatz; die Namen liegen seit Web 6.0.0 in **`mission_crew`** (`mission_id, role_code, name`) statt in fünf festen Spalten — die Tagescrew in `day_crew` bleibt die einzige Wahrheit, solange der Haken nicht gesetzt ist (siehe Abschnitt 4); **`pat_blob`** = E2E-Chiffretext (Name, Geburtsdatum, Alter, Diagnose, Einsatzort, seit Web 2.9.0 auch die Einsatznummer, seit Web 3.3.0 auch die Beschreibung des Einsatzortes — Klartext-Ortsspalten existieren seit der Pflicht-Migration nicht mehr) |
| `mission_phases` | Phasen-Zeitstempel **2–9** (Mehrfach-Einträge erlaubt und erwünscht — eine erneut gesetzte Phase ist eine Korrektur, keine Dublette) inkl. Position. Eine Phase 10 gibt es nicht; der Abschluss läuft über `final` und `ended_at` |
| `resus_sessions` / `resus_events` | Reanimationen: **mehrere Sitzungen je Einsatz**, Ereignisse typisiert |
| `rest_segments` | Ruhe-Track-Segmente (gleiches Idempotenz-Schema wie Einsätze) |
| `track_points` | GPS-Punkte für Einsätze **und** Segmente; PK `(owner_type, owner_id, seq)`; bewusst ohne FK (polymorph) → der Job `waisen` entfernt Waisen (4.97a). **Seit Web 10.0.0 nur noch der Eingangspuffer der Uhr** (Stufe 1): Sobald ein Paket abgeschlossen ist, wandern die Punkte in `track_blobs`. Gelesen wird ausschließlich über `spur_lib.php`, nie direkt — siehe Abschnitt 4.97 |
| `track_blobs` | Dieselben Punkte als **Blob** (Format SPUR1), eine Zeile je Spur, PK `(owner_type, owner_id)`. `stufe` 2 = verlustfrei, 3 = ausgedünnt; `n_original` = Punktzahl **vor** jeder Ausdünnung und damit die Grundlage der Fortsetzungsmarke der Uhr. Wie `track_points` ohne FK (polymorph) — die Löschwege räumen deshalb ausdrücklich mit, der Job `waisen` ist nur das Sicherheitsnetz. Der Grund für die Tabelle ist die Menge: gemessen **62,4 Byte je Punkt als Zeile gegen 3,58 als Blob** |
| `bases` / `vehicles` / `crew_presets` | Stammdaten: Standorte (mit optionalen Koordinaten), Rettungsmittel (`kind` = `air`/`ground`, dazu `vehicle_roles` und `vehicle_capabilities`) und Besatzungsnamen je Rolle. `vehicles` ersetzt `aircraft` seit Web 6.0.0. **Jeder Eintrag gehört genau einem Standort** (`base_id`, E15) — es gibt keine standortübergreifenden Stammdaten. `user_id` NULL = **zentral** (vom Admin gepflegt), sonst persönlich |
| `vehicle_roles` / `vehicle_capabilities` | Besetzte Rollen und Fähigkeiten (`winch`, `bergwacht`) je Rettungsmittel. Die Rollenkennungen stammen aus dem festen Katalog `CREW_ROLES` in `db.php`, nicht aus der Datenbank — deshalb VARCHAR und kein ENUM |
| `user_bases` | Auswahl **zentraler** Standorte je NutzerIn (E16). Nur ausgewählte erscheinen in den Auswahllisten; eigene Standorte brauchen hier keine Zeile |
| `resources` | Vorbelegung „Andere Rettungsmittel" ; `user_id` NULL = zentral, sonst persönlich |
| `mission_resources` | Rettungsmittel-Zuordnung je Einsatz (eigene Zeilen, einzeln entfernbar) |
| `bw_units` | Bergwacht-Bereitschaften; `user_id` NULL = zentral, sonst persönlich |
| `transport_dests` | Vorbelegung „Zielklinik" (Datalist-Vorschläge, `missions.transport_dest` bleibt Freitext ohne FK), seit Web 6.1.0 mit optionalen Koordinaten; `base_id` = Standort; `user_id` NULL = zentral, sonst persönlich |
| `user_defaults` | Nutzerbezogene Standard-Vorbelegung für Diensttage (`kind` in `base`/`vehicle`, `item_id` verweist auf `bases.id` bzw. `vehicles.id`, persönlich oder zentral); ersetzt die entfallenen Alt-Spalten `bases.is_default`/`aircraft.is_default` |
| `days` | Diensttag. Seit Web 6.0.0 eine **eigene Zeile mit eigener Kennung** statt eines Kalendertags: Jeder Druck auf „Einsatztag starten" erzeugt einen; mehrere je Kalendertag sind zulässig (E9). Trägt echte `started_at`/`ended_at` und den beim Zuordnen **eingefrorenen** Snapshot aus Standort und Rettungsmittel (`kind`, `base_name`, `base_lat`, `base_lon`, `vehicle_name`) — Stammdatenänderungen wirken nur in die Zukunft (E8). `kind IS NULL` = neutral, noch nicht zugeordnet (E26) |
| `day_refs` | Uhr-Kennungen eines Diensttags (`device_id`, `day_ref`). Bewusst eine eigene Tabelle: Nach dem Zusammenführen trägt ein Diensttag legitim **mehrere** Kennungen, und `ingest.php` findet damit ohne jede Umleitungslogik den richtigen Tag. Von Hand angelegte Diensttage haben hier keine Zeile |
| `day_crew` / `mission_crew` | Besatzung je Rolle, normalisiert (E7). Die **Zeilenmenge** von `day_crew` ist der eingefrorene Rollensatz des Diensttags — auch leere Zeilen gehören dazu, denn sie sagen, welche Rollen der Dienst anbot |
| `day_capabilities` | Eingefrorene Fähigkeiten des Diensttags. Wird der Windenhaken am Rettungsmittel später entfernt, verlieren alte Einsätze ihre Windenfelder nicht (A13e) |
| `pair_codes` | Kopplungscodes für die Uhr: **6 Zeichen** aus 32 (`PAIR_CHARS` in `db.php`, ohne 0/O und 1/I), **10 Minuten** gültig, **genau einmal** einlösbar, höchstens **ein offener Code je Konto**; die Einmaligkeit wird durch die Reihenfolge „entwerten, dann prüfen“ in `pair.php` durchgesetzt statt bloß zugesichert; Ratenschutz über `rate_limits`; der Job `aufraeumen` entsorgt Altbestand |
| `deleted_refs` | Sperrliste gelöschter `client_ref`s (90 Tage) gegen Wieder-Upload durch die Uhr; `owner_type` unterscheidet Einsatz und Ruhe-Segment — die Liste gilt für **beide** |
| `rate_limits` | Ratenschutz: Versuche je `topf` (login/salt/reset/pair) und `merkmal` (`ip:…` oder `id:…`), mit Zeitfenster und Sperrfrist; liegt bewusst in der Datenbank und nicht in der Sitzung — eine Zählung, die der Aufrufer durch Wegwerfen seines Cookies zurücksetzen kann, ist keine. Seit Web 4.4.0 sind **alle vier Töpfe in Gebrauch**. Bei `salt` und `reset` zählt **jede** Anfrage, nicht nur eine fehlgeschlagene: Beide Endpunkte kennen kein Scheitern, begrenzt wird die Menge (`rate_zaehlen()`). Der Job `aufraeumen` entsorgt Altbestand |
| `rechtstexte` | Impressum und Datenschutzerklärung dieser Installation (R32, seit Web 9.11.0). `schluessel` = `impressum` / `datenschutz`, `inhalt` = Markdown-Quelle (`MEDIUMTEXT`; NULL oder leer = Leerzustand), `stand_am` = das im Editor **von Hand** gesetzte Standdatum (NULL = keine Standzeile). **Nicht in `app_state`:** Dessen Wert ist `VARCHAR(190)`, eine Datenschutzerklärung hat 8 000 bis 20 000 Zeichen — und ohne strict mode kürzt MySQL still |
| `app_state` | Schlüssel/Wert (z. B. `salt_secret`, seit Web 10.1.0 `jobs_token` = Geheimnis für `jobs.php?token=…`, `adminbackup_intervall`, `adminbackup_last`, seit Web 9.8.0 `adminbackup_aufbewahrung` = Zahl der Pakete je Konto, 0/fehlend = Vorgabe **2**, vorher 3; seit Web 12.0.0 `adminbackup_grenze_gb` = Speichergrenze der Ablage (fehlend = 2), `adminbackup_schwellen` = Warnschwellen in Prozent (fehlend = 70,90), `adminbackup_schwellen_gemeldet` und `adminbackup_schwellen_offen` = je Schwelle einmal melden, `adminbackup_auftrag` = Zeiger des Auftrags „Alle sichern"; seit Web 12.1.0 `versand_auto` = Versand auf die Backup-Ziele ein/aus (S2/AP7); seit Web 9.10.0 `adminbackup_mail` = Erinnerung an die Administration ein/aus, `adminbackup_mail_last` = Datum der letzten Erinnerung, `logo_standard` = Logo dieser Installation (`hubschrauber` / `fahrzeug`, fehlend = Hubschrauber)). Die Wartungsmarken `last_cleanup` und `last_cleanup_ok` sind mit Web 10.1.0 entfallen — ihre Auskunft steht vollständiger in `jobs` |
| `missions.letzter_punkt_am` / `rest_segments.letzter_punkt_am` | Wann zuletzt ein Punkt **eintraf** (seit Web 10.2.0, S2). Nicht `track_points.ts` — das ist die Aufzeichnungszeit. Die Karenz aus E-S2-06 braucht die Ankunftszeit: Die Uhr setzt `final` in *jedem* Teilstück, ein spät hochgeladener Puffer wäre über `MAX(ts)` gerechnet im Moment des Eintreffens schon 14 Tage still. NULL = noch nie gemessen; der Verdichtungsjob trägt es beim ersten Hinsehen nach |
| `track_cuts` | Sperrvermerke des Schneidewerkzeugs (seit Web 12.5.0, S4/A2), eine Zeile je Schnitt: `owner_type`/`owner_id` = Quelle, `mission_id` = der herausgeschnittene Einsatz, `von_ts`/`bis_ts` = der gesperrte **Zeitraum**. `ingest.php` verwirft Punkte darin — sonst kehrte eine Nachlieferung aus dem Gerätepuffer in die Quelle zurück und der Schnitt löste sich still wieder auf. Wie `track_points` ohne FK (polymorph); die Löschwege räumen ausdrücklich mit. Siehe Abschnitt 4.97e |
| `jobs` | Zustand der Hintergrundjobs (seit Web 10.1.0, S2), eine Zeile je Job. `zustand` = Fortsetzungsmarke als JSON, `rueckstand` = was noch aussteht (für die Wartungsseite), `letzter_ausloeser` = `cli` / `token` / `anfrage`, `letzter_fehler` = warum der letzte Lauf scheiterte, `laeuft_seit` = Sperre gegen zwei gleichzeitige Läufe — bewusst ein **Zeitstempel und kein Flag**, sonst bliebe ein abgestürzter Lauf für immer gesperrt. Siehe Abschnitt 4.97a |
| `backup_targets` | Backup-Ziele (seit Web 12.1.0, S2/AP7): FTP-, FTPS- oder SFTP-Gegenstelle je Zeile. `geheim` (Passwort oder Passphrase) und `schluessel` (privater SSH-Schlüssel) stehen **versiegelt** darin (`edsk1:`, `serverkrypto_lib.php`); der Schlüssel dazu liegt in `config.php` und damit **nicht im Dump**. Welches Feld gilt, sagt der Inhalt: Steht in `schluessel` etwas, wird damit angemeldet und `geheim` ist dessen Passphrase. `fingerabdruck` = SHA-256 des Hostschlüssels (nur SFTP, Riegel gegen einen untergeschobenen Server). `letzter_fehler` steht dort, damit ein seit Wochen scheiternder Versand in der Oberfläche auffällt. Nicht zu verwechseln mit `transport_dests` — das sind Zielkliniken |
| `schema_migrations` | Buchführung des Migrations-Runners |

Skalierung: ~2.000–2.500 Punkte je Einsatz; Indizes `(user_id, day)` und der
Punkte-PK tragen das auf Jahre problemlos (~1 Mio. Punkte/Jahr).

## 4. Zentrale Abläufe

**Upload & Idempotenz** (Details: `JSON-Vertrag.md`): Die Uhr sendet je
Einsatz/Segment eine `client_ref` (seit Uhr 1.7.0 aus Präfix, einem
fortlaufenden Zähler im Gerätespeicher und einem Zufallsanteil — **kein
Zeitstempel mehr**, siehe `JSON-Vertrag.md` Abschnitt 8) und Punkte ab
`seq_from`; der Server
antwortet mit `next_seq` (erste noch fehlende Sequenz). Wiederholungen sind
unschädlich (`INSERT IGNORE` auf den Punkte-PK, Upsert auf `client_ref`).
Phasen/Rea werden je Upload **vollständig ersetzt** (kein Delta). Die Uhr darf
lokal erst löschen, wenn `final` bestätigt und `next_seq` = Punktzahl.

**Ende-zu-Ende-Verschlüsselung (Pflicht):** Beim Login leitet der Browser per
PBKDF2-SHA256 (Rundenzahl je Konto, `users.kdf_iter`) aus Passwort + `kdf_salt` zwei Werte ab: ein
Auth-Token (ersetzt das Passwort gegenüber dem Server, wird dort gehasht
gespeichert) und einen Datenschlüssel (bleibt im Browser, `sessionStorage`).
Ein zufälliger **Inhaltsschlüssel** (256 Bit, nicht vom Passwort abgeleitet)
verschlüsselt `pat_blob` (`{last, first, dob, dx, age, mission_no,
loc:{addr,lat,lon}, site_desc}`, AES-256-GCM) und liegt doppelt verpackt in `users`: mit dem Datenschlüssel
(`pat_wrap_pw`) und mit dem aus dem Wiederherstellungsschlüssel abgeleiteten
Schlüssel (`pat_wrap_rc`). Weil der Inhaltsschlüssel vom Passwort getrennt ist,
kostet ein Passwortwechsel kein Neuverschlüsseln — nur die Hülle wird erneuert.

**Formatkennung (seit Web 5.1.0, M2-10).** Jeder von `EdCrypto.encrypt()`
erzeugte Chiffretext beginnt mit `edk1:` — sowohl `pat_blob` als auch die
beiden Hüllen. Ohne eine solche Kennung gäbe es beim nächsten Verfahrenswechsel
kein Merkmal, an dem sich alt von neu unterscheiden ließe; man müsste raten,
und ein falscher Rateversuch sieht aus wie ein falscher Schlüssel.

Ein Textpräfix statt eines Kennungsbytes, weil der Doppelpunkt nicht zum
base64-Zeichenvorrat gehört: Die Kennung ist damit auch in der Datenbankspalte
auf den ersten Blick zu erkennen, ohne etwas zu entschlüsseln.

**Beim Lesen großzügig, ohne Umstellung des Bestands.** Ein Chiffretext ohne
Kennung ist die erste Fassung. Der Server kann die Kennung nicht nachtragen —
er hat den Schlüssel nach Bauart nicht. Beide Formen stehen deshalb dauerhaft
nebeneinander; ein Datensatz bekommt die Kennung, wenn er das nächste Mal
gespeichert wird. Eine **unbekannte** Kennung meldet „mit einer neueren Fassung
verschlüsselt" statt „Schlüssel passt nicht".

Serverseitig prüfen `PAT_BLOB_RE` und `WRAP_RE` (beide `validate_lib.php`)
beide Formen. `WRAP_RE` stand bis Web 5.0.1 dreifach im Projekt — als Konstante
in `pw_handling.php` und wortgleich in `einstellungen.php` und
`api/kdf_upgrade.php`; eine davon beim Nachziehen zu vergessen hätte einen
Passwortwechsel scheitern lassen.

Beide Hüllen entstehen **gemeinsam mit dem Passwort** in `pw_handling.php`
(siehe unten). Ein anmeldbares Konto ohne Hüllen kann es dadurch nicht geben;
die früher in `auth_guard.php` erzwungene Ersteinrichtung entfällt seit
Web 2.7.0 ersatzlos. Passwort-Ändern re-wrappt clientseitig **und atomar**:
Lässt sich der Inhaltsschlüssel nicht umpacken, wird auch das Passwort nicht
geändert. Eine Admin-Passwortvergabe existiert bewusst nicht.

**Stille Anhebung der Rundenzahl (seit Web 5.0.0, M2-01 Schritt 4).** Steht ein
Konto noch auf einer niedrigeren Rundenzahl als `KDF_ITER_ZIEL`, wird sie beim
nächsten Anmelden im Hintergrund angehoben. Der Weg führt über ein Vormerkfach
im `sessionStorage` (`edkvor`), weil Passwort und Schlüsselhülle nie gleichzeitig
vorliegen: Bei der Anmeldung hat der Browser das Passwort, aber nicht die Hülle;
auf der ersten angemeldeten Seite ist es umgekehrt.

1. `login.php` leitet für **jede** vom Salz-Endpunkt genannte Rundenzahl ab und
   legt Datenschlüssel und Token je Zahl ins Vormerkfach. Welche gilt, weiß es
   nicht — der Endpunkt darf es nicht verraten.
2. Der Server wählt anhand von `users.kdf_iter` das passende Token.
3. Die erste Seite, die den Inhaltsschlüssel braucht, kennt `KDF_ITER`, nimmt
   den zugehörigen Datenschlüssel aus dem Fach, packt den Inhaltsschlüssel um
   und schickt ihn mit beiden Token an `api/kdf_upgrade.php`.

Der Endpunkt verlangt das **alte** Token als Nachweis (er setzt den Hash, gegen
den sich das Konto anmeldet — ohne Nachweis wäre er ein Weg, aus einer
übernommenen Sitzung ein beliebiges Passwort zu setzen), akzeptiert nur Werte
aus `KDF_ITER_LISTE`, lehnt Senkungen ab, verlangt eine unveränderte
`pat_key_check` und erhöht `session_epoch` **nicht** — anders als der
Passwortwechsel, denn hier hat sich das Passwort nicht geändert.

Nach erfolgreicher Anhebung ist `PAT_WRAP` auf der laufenden Seite **veraltet**.
Der Inhaltsschlüssel wird deshalb direkt abgelegt (`EdCrypto.setContentKey()`)
und an die alte Hülle gebunden; beim nächsten Seitenaufbau verwirft
`EdKeyGuard` ihn wegen der abweichenden Bindung und entpackt ihn aus der neuen
Hülle. Ohne das erschiene der Entsperrdialog unmittelbar nach jedem Anmelden —
das Gegenteil einer stillen Anhebung.

Ein Fehlschlag ändert nichts: Das Konto behält seine Rundenzahl und versucht es
beim nächsten Anmelden erneut. Gemeldet wird er nicht.

**Entsperren des Inhaltsschlüssels in der Sitzung (`assets/unlock.js`, ab Web
3.0.0):** Anmeldung und Inhaltsschlüssel haben unterschiedliche Lebensdauern —
die Anmeldung hängt am PHP-Sitzungscookie (`SESSION_TIMEOUT_S`, 30 min
Inaktivität), der Schlüssel dagegen am `sessionStorage` des jeweiligen Tabs
(`edk` = Datenschlüssel, `pck` = Inhaltsschlüssel). Der Zustand „angemeldet,
aber gesperrt" tritt daher regelmäßig auf: Link im neuen Tab, Browser-Neustart,
Passwort-Reset ohne Wiederherstellungsschlüssel (der Wrap passt dann nicht mehr,
`getContentKey()` liefert `null`).

**Beide Fristen messen dasselbe — seit Web 12.9.0 auch wirklich (R44).** Sie
standen von Anfang an beide auf 30 Minuten, und der Kommentar in `keyguard.js`
sagte ausdrücklich, sie sollten gleich sein. Sie waren es nicht: `auth_guard.php`
schreibt `last_seen` bei **jeder Anfrage** — eine Inaktivitätsfrist —, während
`keyguard.js` seinen Zeitstempel nur beim **Entpacken** setzte und ihn beim
Treffer im Zwischenspeicher nicht anfasste. Das war eine absolute Frist ab dem
Entsperren. `contentKey()` erneuert den Zeitstempel jetzt bei jedem Treffer.

**Was der Fristablauf kostete, und was nicht.** Der R44-Eintrag schrieb ihm den
Entsperrdialog zu; das ist im Rahmenplan-Archiv am 01.09.2026 berichtigt.
`verwerfeInhalt()` lässt `edk` bewusst liegen, und `getContentKey()` entpackt
den Inhaltsschlüssel daraus **ohne Passwort** neu — der Ablauf kostete ein
**stilles Neu-Entpacken**. Zahl dazu: acht Stunden Dienst, alle fünf Minuten
eine Seite, 97 Aufrufe ohne Pause — **vorher 17 Neu-Entpackungen, nachher 1**
(`tools/fristprobe/`, dort auch die Gegenprobe, dass die Frist weiterhin
greift). Der Dialog fällt an der Stelle darüber: wenn `getContentKey()` `null`
liefert, also in genau den drei aufgezählten Fällen. **Das bleibt so.**

Die Angleichung ist damit Aufräumen und kein Heilmittel — richtig bleibt sie:
Zwei Uhren, die dieselbe Zahl tragen und Verschiedenes messen, sind eine Falle
für den nächsten, der sich auf den Kommentar verlässt. Die Gegenrichtung — die
Sitzung ebenfalls absolut befristen — hätte aktive NutzerInnen mitten in der
Arbeit abgemeldet. Läuft die Frist wirklich ab, endet die **Sitzung**, und die
nächste Anfrage landet auf der Anmeldeseite, die die Schlüssel ohnehin räumt.

`unlock.js` exportiert genau eine Funktion:

```
EdUnlock.ensureContentKey(wrap, kdfSalt) -> Promise<string|null>
```

Sie liefert den Inhaltsschlüssel, wenn er in der Sitzung liegt; sonst zeigt sie
einen modalen Dialog und leitet den Schlüssel neu ab:

1. `EdCrypto.deriveKeys(passwort, kdfSalt)` → `dataKeyHex`
2. `EdCrypto.decrypt(dataKeyHex, wrap)` versuchen. Gelingt es, war das Passwort
   richtig — die Echtheitsprüfung steckt bereits in AES-GCM, ein separater
   Abgleich entfällt.
3. `EdCrypto.setDataKey(dataKeyHex)`, danach `EdCrypto.getContentKey(wrap)`.

Bei Abbruch kommt `null` zurück; die aufrufende Seite verhält sich dann wie
bisher im gesperrten Zustand. Kein neuer Endpunkt, keine Passwortübertragung —
der Ablauf ist vollständig clientseitig, `auth_guard.php` stellt `$kdfSalt` und
`$patWrapPw` jeder eingeloggten Seite ohnehin bereit.

Drei Punkte, die bei Änderungen zu beachten sind:

- **Nur ein Dialog gleichzeitig.** Solange einer offen ist, hängen sich weitere
  Aufrufe an dasselbe Promise (Modulvariable `laufend`). Ohne das öffnete
  `import.php` mehrere Dialoge übereinander, weil `import_ui.js` (drei Stellen)
  und `export.js` auf derselben Seite laufen.
- **Escape während der Ableitung wird unterdrückt** (`cancel`-Ereignis mit
  `preventDefault()`), sonst nimmt die aufrufende Seite „abgebrochen" an,
  während die Rechnung weiterläuft. Die PBKDF2-Runden dauern je nach Gerät
  0,3–1 s; solange sind Knöpfe und Feld gesperrt und es steht ein Wartehinweis.
- **Kein `window.prompt` als Rückfallebene.** Fehlt `<dialog>` oder das Salt,
  kommt `null` zurück — ein Prompt zeigte das Passwort im Klartext.

Aufrufstellen: `index.php`, `zeitraum.php`, `einsatz.php` (bezieht den Wrap aus
der API-Antwort `m.pat_wrap`, das Salt zusätzlich aus PHP), `einsatz_form.php`,
`import.php` über `assets/import_ui.js` und `assets/export.js`, sowie
`einstellungen.php` im Backup-Block. **Nicht** umgestellt ist der
Passwortwechsel im Profil-Block von `einstellungen.php`: Er leitet Schlüssel in
einem anderen Zusammenhang ab (Re-Wrap mit dem alten Passwort) und hat mit dem
Entsperren nichts zu tun.

Jeder Sperrhinweis trägt einen Entsperr-Knopf, der denselben Ablauf erneut
anstößt. Wichtig dabei: Die Funktionen hinter diesen Knöpfen müssen ein
zweites Mal aufrufbar sein, ohne doppelt zu zeichnen — überall gegeben, weil
ohne Schlüssel vorher weder Pin noch Zeile entsteht. Seit Web 9.3.0 ist der
Sperrhinweis auf allen drei Seiten eine **Meldung** (`.meldung meldung-info`
mit Schloss und Knopf), keine Zeile in der Feldliste mehr.

**Schutz beim Speichern (`einsatz_form.php`):** Ist `PAT_CK` null, verlässt der
Submit-Handler die Blob-Erzeugung vorzeitig (`if (f.dataset.patDone === '1' ||
!PAT_CK) return;`). Ein Speichern im gesperrten Zustand lässt den vorhandenen
`pat_blob` also unangetastet. Dieses Verhalten ist beim Entsperr-Umbau bewusst
erhalten geblieben und darf nicht wegfallen — sonst löscht ein Speichern ohne
Schlüssel die Patientendaten.

**Koordinaten stehen getrennt vom Textfeld (seit Web 3.3.0).** `#locaddr` ist
Bezeichnungsfeld *und* Eingabeweg für Koordinaten. Bestätigte Koordinaten
landen deshalb **nicht** mehr im Textfeld, sondern als Chip darunter
(`#locchips`, Klassen `.rmchip`/`.rmx`); Wertträger bleiben die versteckten
Felder `#loclat`/`#loclon`. Der `input`-Zuhörer leert diese beiden Felder
**bewusst nicht** mehr — die frühere Zeile war eine Aufräumregel gegen einen
hängenden Kartenpin, und genau sie würde eine über den Koordinaten getippte
Bezeichnung beim ersten Buchstaben vernichten. Wer sie als „vergessene
Aufräumzeile" wiederherstellt, baut den alten Fehler wieder ein. Entfernt
werden die Koordinaten nur über das Kreuz am Chip oder durch Auswahl eines
anderen Adressvorschlags. **Solange `#loclat` belegt ist, steigt der
`input`-Zuhörer früh aus** — weder Formaterkennung noch Photon-Anfrage laufen,
und die Vorschlagsliste wird geleert und verborgen. Grund: Beide
Vorschlagszweige schreiben `#loclat`/`#loclon` beim Übernehmen neu und würden
die bestätigten Koordinaten überschreiben. Placeholder und Meldungszeile folgen
demselben Zustand, damit das Feld nicht defekt wirkt. Sind Koordinaten gesetzt
und das Textfeld leer,
verhindert eine Prüfung vor dem Verschlüsseln das Absenden — sie sitzt hinter
dem `PAT_CK`-Riegel, damit sie bei gesperrter Verschlüsselung nicht zuschlägt
(dort sind die Felder leer und der Blob wird ohnehin nicht angefasst).

**Einsatzort-Feld (`einsatz_form.php`):** Erkennt beim Tippen zusätzlich zur
Adresssuche (Photon) vier Koordinatenformate — Dezimalgrad, Grad/Dezimal-
minuten, Grad/Minuten/Sekunden und Plus-Code-Vollcodes — und wandelt sie
clientseitig um, ohne dabei einen Netzwerk-Request auszulösen. Ein Treffer
erscheint als Eintrag in derselben Vorschlagsliste wie ein Adresstreffer
(`#locsuggest`); erst die Auswahl setzt `lat`/`lon` und normalisiert den
Feldtext — derselbe Ablauf wie bei Photon-Adressvorschlägen, kein
Sonderfall im Formular. Formaterkennung/Parser liegt in
`assets/locparse.js` (reine Funktionen, keine DOM-/Fetch-Abhängigkeiten);
die Plus-Code-Dekodierung nutzt die gevendorte Bibliothek
`assets/openlocationcode.js` (`google/open-location-code`, Apache-2.0).

**Karten-Controls (`assets/map_fullscreen.js`, `assets/map_layers.js`, ab Web
2.5.0):** Beide Dateien exportieren je eine Funktion
(`attachFullscreenControl(map)` / `attachBaseLayers(map)`) und kapseln ihren
Zustand vollständig in Closures — keine globalen Variablen, damit mehrere
Karten pro Seite (aktuell max. eine) nicht kollidieren würden. Alle drei
Kartenseiten (`index.php`, `einsatz.php`, `zeitraum.php`) rufen dieselben
zwei Funktionen auf, kein Duplikat-Code je Seite.

`attachFullscreenControl` nutzt primär die native Fullscreen-API auf dem
Karten-Container (inkl. `webkit`-Präfix); wo diese für beliebige Elemente
nicht verfügbar ist (v. a. iOS Safari), greift ein CSS-Overlay-Fallback
(Klasse `map-fs`, `position:fixed`, `z-index:2000` — höher als alle
bestehenden UI-Ebenen) mit eigener ESC-Behandlung. In beiden Fällen folgt
ein verzögerter `map.invalidateSize()`-Aufruf, sonst bleibt die
Kacheldarstellung nach dem Umschalten unvollständig.

`attachBaseLayers` ergänzt den bisherigen OSM-Standardlayer um zwei
topographische Varianten mit Höhenlinien (OpenHikingMap über
`tile.openmaps.fr`, OpenTopoMap über `tile.opentopomap.org`) sowie seit
Web 7.0.0 ein **Satellitenbild** (Esri „World Imagery" über
`server.arcgisonline.com`) und hängt Leaflets eingebautes
`L.control.layers()` an — kein zusätzliches Plugin.
Wie beim bisherigen OSM-Layer werden dabei ausschließlich Kartenkacheln
anhand des sichtbaren Ausschnitts angefragt, keine Standort- oder
Patientendaten (gleiches Datenschutzprinzip wie beim Verzicht auf
What3Words in der Ortssuche). Die beiden topographischen Anbieter sind
spendenfinanzierte Community-Server ohne Verfügbarkeitsgarantie; die
Attribution enthält deshalb die jeweils geforderten Hinweise (inkl.
Spenden-Link bei OpenHikingMap, CC-BY-SA-Hinweis bei OpenTopoMap). Esri
verlangt die Nennung der Bildquellen, die ebenfalls in der Attribution
steht.

**Achtung Platzhalterfolge:** Der Esri-Dienst erwartet `{z}/{y}/{x}`, nicht
`{z}/{x}/{y}` wie die drei anderen. Vertauscht liefert er kommentarlos falsche
oder leere Kacheln. Der Layer ist **nicht** Standard: Er lädt deutlich größere
Kacheln, und die Karte soll beim Öffnen einer Einsatzansicht schnell dastehen.

**Marker-Satz und Spurfarben (`assets/geo.js`, ab Web 9.2.0):** Das
`EdGeo`-Modul liefert alles, was auf einer Einsatzkarte steht, aus einer
Hand: `markerStandort()`/`markerZiel()` (weiße Schilder mit Haus- bzw.
Klinik-Symbol; `ring: 'start' | 'ende' | 'beide'` legt Farbringe für
Dienstbeginn und -ende darum), `markerEinsatzort()` (oranger Kreis mit
Einsatzort-Symbol), `markerPunkt()` (kleiner Farbpunkt, z. B. Abfahrt) und
`pfeile()` (Richtungspfeile alle 140 Bildschirm-Pixel auf einer Spur, neu
verteilt bei jedem Zoom; der `remove`-Handler der Ebene räumt den Zuhörer
ab). Alle Marker sind `divIcon`s mit CSS-Klassen (`.geo-schild`,
`.geo-kreis`, `.geo-ring-*`, `.geo-pfeil`) — Form und Farbe stehen im
Stylesheet, nicht im Skript. Die **Spurfarben** kommen als Token aus
`:root` (`--spur-1 … --spur-8`, `--spur-ruhe`); `EdGeo.spurFarbe(i)` liest
sie per `getComputedStyle`, JS enthält keinen Farbwert.

Der Phasenmarker-Toggle in `einsatz.php` ist als eigenes `L.Control`
(Position `topleft`, unterhalb des Vollbild-Controls) umgesetzt statt als
DOM-Button unter der Karte — dadurch im Vollbildmodus mitbedienbar. Marker
werden beim Laden erzeugt, aber standardmäßig nicht der Karte hinzugefügt
(`phasesVisible = false`, keine Persistenz); die Hover-/Klick-Kopplung zur
Phasentabelle bindet sich über Leaflets `'add'`-Ereignis des Markers, da das
zugehörige DOM-Element erst beim tatsächlichen Hinzufügen zur Karte
entsteht.

> **Historie:** Ältere Konten mit `kdf_ver = 0` (Passwort ging im Klartext zum
> Server) wurden in Web 2.1.0 vollständig entfernt; die Spalte `kdf_ver` selbst
> ist in Web 2.7.0 entfallen, da sie nur noch geschrieben, aber nie gelesen wurde. Es gibt keinen
> unverschlüsselten Anmeldeweg mehr; Browser ohne Web-Krypto erhalten eine
> klare Fehlermeldung. `auth_salt.php` liefert Salts, für unbekannte Adressen
> ein deterministisches Pseudo-Salt gegen User-Enumeration.

**Der Zurücksetzen-Token steht nicht in der Adresszeile** (ab Web 4.5.0).
`pw_handling.php` nimmt ihn beim ersten Aufruf aus dem Parameter, legt ihn in
eine Sitzung und ruft sich ohne Parameter neu auf; dazu `Referrer-Policy:
no-referrer` und `Cache-Control: no-store`. Zwei Entscheidungen daran sind
nicht beliebig:

* **Eigener Sitzungsname** (`EDPWSESS`), nicht der der Anwendung. Sonst würde
  eine parallel offene, angemeldete Sitzung im selben Browser die Attribute
  ihres Cookies mitgeändert bekommen.
* **`SameSite=Lax`**, nicht `Strict`. Der Link wird im Mailprogramm angeklickt,
  also von einer fremden Seite aus; ein `Strict`-Cookie käme bei der
  Weiterleitung nicht zurück und die Seite wäre eine Sackgasse. `Lax` hält
  fremde POST-Anfragen trotzdem ab.

Blockiert der Browser Cookies, wird genau das gesagt („Cookie nötig") statt
„Link ungültig" — sonst forderte die Person einen zweiten, ebenso wirkungslosen
Link an.

**Passwortvergabe (`pw_handling.php`):** Die einzige Stelle, an der ein Passwort
über einen Einmal-Link gesetzt wird. Der Server bestimmt die Betriebsart allein
aus dem Kontostand — nie aus dem, was der Browser mitschickt:

- **Erstvergabe** (`pat_wrap_rc IS NULL`): Das Konto hat noch keinen
  Inhaltsschlüssel. Der Browser erzeugt ihn zusammen mit dem
  Wiederherstellungsschlüssel, zeigt letzteren **einmalig** an und lässt ihn per
  Haken bestätigen; die Passwortfelder werden dabei schreibgeschützt, damit die
  bereits berechnete Hülle zum Passwort passt. Erst danach wandern
  Token-Hash, Salz und **beide** Hüllen gemeinsam in die Datenbank.
- **Reset** (`pat_wrap_rc` vorhanden): verlangt das neue Passwort **und** den
  Wiederherstellungsschlüssel. Der Browser entpackt damit den Inhaltsschlüssel
  und verpackt ihn für das neue Passwort neu; `pat_wrap_rc` bleibt unberührt,
  der bekannte Wiederherstellungsschlüssel gilt also weiter.

Geschrieben wird in beiden Fällen in **einer Transaktion**. Passt der Schlüssel
nicht, bricht der Vorgang im Browser ab, bevor etwas gesendet wird — das Konto
bleibt unverändert. Denselben Weg nutzt auch `install.php`: Der Installer legt
den Administrator **ohne** Passwort an und zeigt auf der Erfolgsseite den
Einmal-Link.

**Backup (portabel):** Der Browser holt den Bestand in Stücken:
`api/backup_data.php?teil=kopf` gibt Stammdaten, Diensttage und die Zahl der
Einträge, `?teil=eintraege&ab=…&anzahl=250` dann Fenster von Einsätzen und
Ruhesegmenten — ohne Punktlisten, geschützte Angaben weiterhin als
Chiffretext. Er entschlüsselt sie je Fenster mit dem Inhaltsschlüssel, ersetzt
sie durch Klartext, holt die Spuren blockweise als SPUR1-Blobs
(`api/backup_spuren.php`, 25 Kennungen je Anfrage) und schreibt daraus ein ZIP
mit versiegelten Teilen (**Containerfassung 4**, seit Web 11.1.0):
`manifest.edbak`, `kopf.edbak`, `eintraege/NNNN.edbak`, `spuren/NNNN.edbak`.
Jedes Teil ist ein AES-GCM-Container; die Zusatzdaten binden
Backup-Kennung, Teilname und Nummer, und abgeleitet wird **einmal je
Vorgang**.

**Warum 250 Einträge je Fenster:** Der Rückweg schickt genau diese Fenster als
POST zurück, und `client_max_body_size` steht bei nginx in der Vorgabe auf
1 MB. Gemessen am 10 797-Einträge-Bestand: 250 ergeben 0,44 MB je Fenster in
44 Anfragen, 500 ergäben 0,87 MB. Der Abrufendpunkt nimmt höchstens 1000 je
Anfrage und weist mehr mit 400 ab; der Browser zählt zusätzlich nach, wie
viele ein Fenster gebracht hat.

Beim Einspielen öffnet der Browser Manifest und Kopf, schickt den Kopf an
`api/backup_restore.php` und bekommt die Zuordnung der Diensttage zurück
(`day_map`). Dann gehen die Eintragsfenster an
`api/backup_eintraege_restore.php` — die Angaben vorher mit dem Schlüssel des
**Zielkontos** neu verschlüsselt —, und der Server antwortet je Fenster mit
der Zuordnung `spur_ref` → angelegter Datensatz. Zuletzt gehen die Blobs an
`api/backup_spuren_restore.php` — geprüft, und Vorhandenes übersprungen.

**Die Warnung vor unlesbaren Angaben steht vor dem ersten Schreiben.** Ob es
etwas zu warnen gibt, kann der Einspielweg bei Fassung 4 nicht mehr selbst
sehen: Die Einträge liegen zu diesem Zeitpunkt versiegelt in ihren Teilen. Das
Manifest trägt deshalb `unlesbar` — die Zahl der Einsätze, deren geschützte
Angaben beim *Sichern* nicht zu entschlüsseln waren. Fehlt sie, wird gewarnt.

Dadurch sind Backups zwischen Konten übertragbar; der Server sieht nie
Klartext. Die einteiligen Fassungen 2 und 3 werden weiterhin **gelesen** und
nicht mehr geschrieben (bis NaDoku 1.0, Backlog Nr. 46). Aufbau:
`docs/Backup-Format.md`.

**Versionierung & Zwischenspeicher:** `WEB_VERSION` steht ausschließlich in
`server/version.php` und erscheint in der Fußzeile. `asset($pfad)` (in
`db.php`) hängt an jede Stylesheet- und Skript-Adresse einen Erkennungswert an;
ändert er sich, lädt der Browser die Datei neu — das manuelle Leeren des
Zwischenspeichers entfällt. **Beim Ausliefern immer die Version erhöhen.**

Seit Web 5.4.0 ist dieser Erkennungswert der **Zeitstempel der jeweiligen
Datei** statt der globalen Version (Backlog Nr. 9). Vorher entwertete jede
Versionserhöhung den Zwischenspeicher *aller* Dateien, auch der unveränderten:
Eine Korrekturfassung, die eine Zeile im Stylesheet ändert, ließ Besucher
sämtliche Skripte erneut laden. `WEB_VERSION` bleibt der Rückfall, wenn eine
Datei nicht gefunden wird — dann ist der Verweis ohnehin falsch, und eine
Adresse ohne wechselnden Erkennungswert wäre der unangenehmere Fehler.
Verträglich mit dem Auslieferungsweg (Prüfschritt P8): Der FTP-Deploy überträgt
nur inhaltlich geänderte Dateien und führt dafür auf dem Server eine
Zustandsdatei mit Prüfsummen; unveränderte Dateien behalten ihren Zeitstempel,
übertragene bekommen den Zeitpunkt des Hochladens. Der Zeitstempel muss also
nicht erhalten bleiben — er ist Änderungsmarke, nicht Datum. Zwei Fälle, in
denen einmalig alles neu geladen wird: die erste Auslieferung nach dieser
Umstellung und ein Deploy, bei dem die Zustandsdatei auf dem Server fehlt.

`favicon_tags()`
erzeugt zentral die Symbol-Verweise (PNG mit Version, ICO im Wurzelverzeichnis,
apple-touch-icon), wurzelbezogen über `SCRIPT_NAME`. `logo_src()` liefert das
Login-/Einrichtungslogo und prüft dabei, ob die in der Konfiguration angegebene
Datei existiert, sonst die mitgelieferte SVG-Bildmarke.

**Bestätigungen:** `assets/confirm.js` fängt Formulare und Links mit
`data-confirm` ab und zeigt ein `<dialog>` im Seiteninhalt statt
`window.confirm()` — native Dialoge lassen sich pro Seite dauerhaft
unterdrücken, was die Rückfrage wirkungslos machen würde. Eingebunden über
`ui_footer()`. Sicherheitskritische Löschungen hängen ohnehin nicht daran,
sondern an den serverseitigen Zwischenseiten.

**Verlassen-Warnung & Strg-Enter:** `assets/forms.js` ist reines Opt-in per
Attribut (`data-dirty-track`, `data-submit-on-ctrl-enter`), global über
Event-Delegation (kein Einbinden pro Feld nötig). Das reguläre Absenden setzt
das Dirty-Flag zurück, bevor `beforeunload` greifen kann — auch bei
Formularen, die selbst per `fetch()` speichern (`preventDefault()` in deren
eigenem Handler ändert daran nichts, das Submit-Ereignis feuert davor).
Eingebunden auf `einsatz_form.php`, `index.php` (`#dayform`) und
`diensttag_neu.php`.

**Tages- und Einsatzzuordnung korrigieren (ab Web 5.6.0, Block A5).**
`tageszuordnung_lib.php` trägt beide Handlungen; die Seiten
`einsatz_verschieben.php` und `diensttag_datum.php` bringen nur Markup und
Rückfrage mit. Sie sehen sich ähnlich und sind ausdrücklich verschieden:

| | `tz_einsatz_verschieben()` | `tz_tag_datum_aendern()` |
|---|---|---|
| Anlass | Fehlzuordnung eines Einsatzes | falsch gestellte Uhr |
| Umfang | ein Einsatz | ganzer Tag samt Anhang |
| Zeitstempel | **bleiben** | **wandern mit** |
| Tabellen | `missions` (+ ggf. `days`) | `days`, `missions`, `rest_segments`, `mission_phases`, `resus_sessions`, `resus_events`, `track_points` |

Drei Punkte, die beim Lesen leicht untergehen:

* **Verschoben wird um den Abstand der Ortsmitternachte**, nicht um
  `Tage × 86400` Sekunden. Läuft die Verschiebung über eine Zeitumstellung,
  ist der Abstand um eine Stunde größer oder kleiner — genau darum bleibt die
  dokumentierte Ortszeit stehen. Eine feste Sekundenzahl verschöbe sie.
* **`track_points.ts` trägt die Unix-Epoche**, nicht ein `DATETIME`, und die
  Spalte ist `UNSIGNED`. Eine Rückwärtsverschiebung unter null wäre ein
  Datenbankfehler mitten in der Transaktion; sie wird vorher geprüft und
  benannt.
* **Papierkorb-Einträge wandern mit.** Sie hängen seit Web 6.0.0 über
  `day_id` am Diensttag; blieben sie liegen, kämen sie beim Wiederherstellen
  an einem Tag zurück, den es so nicht mehr gibt.

**Was mit dem Tagesschlüssel entfallen ist (Web 6.0.0).** Bis dahin galt
`uq_user_day`: je Kalendertag genau ein Diensttag. Daran hingen eine
Kollisionsprüfung beim Umdatieren, eine Liste belegter Daten und
`tz_tag_zustand()`. Seit E9 ist ein belegtes Zieldatum der **vorgesehene** Fall
— mehrere Diensttage je Kalendertag sind zulässig —, und alle drei sind
ersatzlos entfallen.

**Auskunft zum Ziel (ab Web 5.10.0).** Beide Seiten sagten bis dahin erst
**nach** dem Absenden, worauf die Wahl hinausläuft. Jetzt steht es unter dem
Feld:

* `einsatz_verschieben.php` wählt einen **vorhandenen** Diensttag aus einer
  Liste — mit Datum, Dienstbeginn, Rettungsmittel, Standort und Zahl der
  Einsätze. Angelegt wird dort nichts mehr: Welchem Dienst ein Einsatz gehört,
  ist eine Auswahl, keine Nebenwirkung.
* `diensttag_datum.php` beziffert, **was mitwandert** — Einsätze (mit denen im
  Papierkorb), Ruhesegmente, Trackpunkte, Phasenzeiten, Start und Ende des
  Diensttags. Bis Web 6.0.0 nannte die Seite stattdessen, was am gewählten
  Datum bereits liegt; diese Liste ist mit dem Tagesschlüssel entfallen, weil
  mehrere Diensttage je Kalendertag seit E9 der vorgesehene Fall sind. Seit
  P3/O11 steht die Aufstellung als Zeilen mit Plakette statt als Aufzählung.

Beides ist **rein anzeigend**, und das ist keine Nachlässigkeit: Die Listen
sind auf 400 Einträge gedeckelt und veralten, sobald in einem zweiten Fenster
etwas entsteht. Ein gesperrter Absendeknopf hätte daraus eine Schranke gemacht,
die falsch liegen kann. Wo der Deckel gegriffen hat, sagt die Auskunft nichts —
statt etwas Falsches zu sagen. Geprüft wird weiterhin dort, wo geschrieben
wird.

Warum ein Einsatz nicht zurückgezogen wird (Prüfschritt P5): `ingest.php:150`
führt beim Upsert ein `ON DUPLICATE KEY UPDATE`, das die Spalte `day` **nicht**
mitschreibt. Außer den beiden Funktionen hier schreiben nur `ingest.php` und
`einsatz_form.php` (beim Anlegen), `api/import_commit.php` (beim Import, und
beim Überschreiben nur auf ausdrückliche Wahl in der Import-Maske) sowie
`backup_lib.php` (nur einfügend) auf `day`. Kein automatischer Weg fasst den
Tag eines bestehenden Einsatzes an.

**Abbrechen (ab Web 5.5.0, Block A4.1):** Ein Verweis mit
`data-cancel-form="<id des formulars>"` fragt vor dem Verlassen nach — aber
**nur**, wenn das genannte Formular das Dirty-Flag trägt. Dieselbe Quelle wie
die Verlassen-Warnung, bewusst keine zweite: Zwei Kennzeichen für dieselbe
Frage laufen auseinander. Die Rückfrage selbst kommt aus `assets/confirm.js`
(`window.edConfirm`); dessen eigener Weg `data-confirm` passt hier nicht, weil
er bedingungslos fragt. Nach einem bestätigten Abbruch wird das Flag gelöscht,
bevor navigiert wird — sonst käme direkt hinterher noch die `beforeunload`-
Abfrage des Browsers, und zweimal dasselbe zu fragen heißt, die erste Frage
nicht ernst zu nehmen. Der Text lässt sich je Verweis über
`data-cancel-confirm` setzen. Nach außen gibt die Datei
`window.EdForms.istGeaendert(form)` und `.vergessen(form)`, damit eigene
Abbruchwege nicht doch ein zweites Kennzeichen einführen.

**Seitenleiste und Schublade (P3/O2):** `ui_geruest_start()` gibt Kopfleiste,
Leiste und Inhalt in einem Zug aus; `ui_geruest_ende()` schließt sie, setzt die
Fußzeile **außerhalb** von `<main>` und lädt die vier Skripte des Gerüsts.
Drei Leisteninhalte teilen sich dasselbe Markup: `ui_leiste_diensttage()`,
`ui_leiste_einstellungen()` und — für die Suche — der von der Seite selbst
gefüllte Filterblock (`leiste => 'filter'`, danach `ui_leiste_ende()`).

**Die Grundformen des Stylesheets (Abschnitt 17, seit Web 9.12.0).** Bis dahin
hieß dieser Abschnitt **Rohschicht** und war ausdrücklich befristet: Solange
P3 die Seiten Paket für Paket umbaute, stand auf jeder noch nicht umgebauten
Seite Markup mit Klassennamen, für die es keine Regel mehr gab. Zwei Klassen
waren dafür begründete Ausnahmen (`.alert`, `.muted`), dazu Elementregeln für
`table`/`th`/`td`, `fieldset`/`legend` und `hr`. Mit O11 sind alle fünf
gefallen — die letzte Tabelle ohne eigene Regel (`.imp-table` im Import)
trägt jetzt `.tabelle`, und
`fieldset`, `legend` und `hr` kommen in der Anwendung nirgends mehr vor.

Geblieben ist, was **Grundform** ist und keine Übergangslösung:
`input`/`select`/`textarea` (ein Eingabefeld gibt es auch außerhalb von
`.feld` — im Suchfeld, im Auswahlkästchen einer Zeile, in einem Filter, und es
muss dort dieselbe Höhe, denselben Rahmen und dieselbe Farbe haben), Kästchen
und Radios, das Muster `<label>Text <input></label>` (46 Stellen, überwiegend
in den Filterreihen der Suche und im Einsatzformular), `summary` und
`code`/`kbd`/`pre`.

**Die Eintrittskarte bleibt eng: nur Elementnamen.** Eine Klasse dort
einzutragen hieße, das Redesign zurückzunehmen — dafür gibt es die Bausteine
in den Abschnitten davor. Und eine Falle hat der Abschnitt: Seine Regeln
haben Spezifität (0,1,1) und schlagen damit jede bloße Klasse. Wer ein
Kästchen über eine Klasse ausblenden will, braucht
`input[type=checkbox].meine-klasse` — dreimal ist genau das schiefgegangen
(F-P3-AP, F-P3-AZ).

**Es gibt keine zweite Leiste.** Unter 1024 px liegt dieselbe `<aside
class="leiste">` als Schublade über dem Inhalt, darüber steht sie fest daneben;
der Unterschied ist ausschließlich CSS (Abschnitt 4 und 18 des Stylesheets).
`assets/schublade.js` öffnet und schließt sie und hält den Fokus darin. Der
Mechanismus hängt an der **Klasse**, nicht an der Funktion, die die Diensttage
ausgibt — hinge er an der Funktion, bliebe die Suchseite als einzige ohne
mobiles Menü (Vormerkliste aus Konzept P0, 10.5).

Die Tage sind serverseitig nach Jahr und Monat gruppiert
(`<details>`-Verschachtelung); welches Jahr und welcher Monat offen sind,
bestimmt PHP anhand des gewählten bzw. des jüngsten Tages — kein JavaScript
nötig, da jede Navigation ohnehin einen Seitenaufruf auslöst.
`assets/daylist.js` erzwingt nur noch das Akkordeon-Verhalten (ein offenes
Element je Ebene). Die frühere Trennung der Klickbereiche ist entfallen: Die
ganze Zeile klappt, und der Weg in die Zeitraumübersicht ist ein eigenes
Symbol am rechten Rand (`.akkordeon-uebersicht`, 44 px).

**Geschützte Zusatzfelder & berechnetes Alter:** `assets/patient.js` (EdPat)
berechnet das Alter aus dem Geburtsdatum bezogen auf den **Einsatztag** und
liefert Namens-/Datumsformatierung. Genutzt von Formular, Einsatzansicht,
Tages- und Zeitraumübersicht, Suche und Export. Name und Geburtsdatum erscheinen
nur in der Einsatzansicht, nie in den Tabellenübersichten. Das Alter wird nur
dann als Wert gespeichert, wenn es **nicht** aus einem Geburtsdatum ableitbar
ist.

> **`alterAm` vs. `alterAnzeige`.** `alterAm(dob, tag)` kennt nur das
> Geburtsdatum und liefert ohne eines `null`; `alterAnzeige(pat, tag)` fällt
> danach auf den gespeicherten `age` zurück. Überall, wo ein Alter **angezeigt**
> wird, gehört `alterAnzeige` hin — mit `alterAm` bleibt die Angabe bei
> unbekannten Personen leer, also genau dort, wo sie von Hand eingetragen wurde.
> `alterAm` ist für Entscheidungen gedacht: im Formular, um das Eingabefeld zu
> sperren, und in `import_ui.js`, um ein gerechnetes Alter nicht in den
> `pat_blob` zu schreiben. Web 3.4.0 hatte an dieser Stelle in `export.js` die
> falsche der beiden Funktionen (behoben in 3.5.0).

**`site_desc` hat `mission_fields.php` verlassen (Web 3.3.0).** Die Beschreibung
des Einsatzortes liegt seither als eigener Schlüssel auf oberster Ebene des
`pat_blob` — nicht innerhalb von `loc`, weil `loc` nur bei gefüllter Adresse
entsteht und eine Beschreibung ohne Ortsangabe sonst verloren ginge. Mit dem
Eintrag in der Definitionsliste sind zugleich Formularausgabe,
Formularauswertung, `api/mission.php` und die Backup-Wiederherstellung
verschwunden, die alle generisch über `$FIELDS` laufen.

Die gleichnamige Klartextspalte ist mit Web 3.3.1 gefallen (Migration
`2026_08_05_site_desc_entfernt`); der Altbestand wurde vorher über eine
vorübergehende Seite als Textdatei gesichert und von Hand nachgetragen. **Ein
Wiedereintragen in `mission_fields.php` würde daher nicht nur den Klartext
zurückholen, sondern gegen eine nicht mehr vorhandene Spalte schreiben.** Die
CSV-Kopfzeile `site_desc` wird beim Import weiterhin angenommen und dem
verschlüsselten Block zugeordnet, damit Exportdateien bis Web 3.2.0 lesbar
bleiben (`assets/import_profiles.js`).

**Rettungsmittel:** `other_resources` hat in `mission_fields.php` den Sondertyp
`resources` und **keine** `missions`-Spalte. Vorbelegungen stehen in
`resources`, die Zuordnung je Einsatz in `mission_resources` (eigene Zeilen,
einzeln entfernbar). Das Löschen einer Vorbelegung lässt dokumentierte Einsätze
unverändert. Backup exportiert/importiert beide.

**Effektive Besatzung (Crew-Override, ab Web 2.6.0):** Die Besatzung wird
einmal je Diensttag in `day_crew` gepflegt (bis Web 5.10.0: fünf Spalten
`days.crew_*`). Ein einzelner Einsatz kann davon abweichen (fachlicher Anlass:
Pilotenwechsel oder Fahrerwechsel im laufenden Dienst) — dafür trägt `missions` die Spalte
`crew_override` (0/1), die Namen liegen in `mission_crew`.
**Bewusst redundanzfrei:** Ohne Abweichung gibt es in `mission_crew` keine
Zeile; es gibt keine Kopie der Tagesbesatzung am Einsatz. Die Regel lautet je
Rolle `crew_override = 1 AND mission_crew.name IS NOT NULL ?
mission_crew.name : day_crew.name`. Sie ist **einmal** implementiert, in
`api/mission.php`, das das Ergebnis als `crew_effektiv`
(`{rolle: {label, name, abw}}`, nur belegte Rollen) liefert; `einsatz.php`
rendert es unverändert in der Karte „Besatzung".

> **Seit Web 6.0.0 (Notarzt-Erweiterung).** Die Besatzung ist normalisiert (E7):
> Aus den Spalten `crew_p1 … crew_other` in `days` und `missions` sind Zeilen in
> `day_crew (day_id, role_code, name)` und `mission_crew (mission_id, role_code,
> name)` geworden. Die COALESCE-Regel ist unverändert, sie läuft jetzt über zwei
> **Tabellen** statt über zwei Spaltensätze. Welche Rollen ein Diensttag
> überhaupt anbietet, sagt die Zeilenmenge in `day_crew` — sie ist der beim
> Anlegen eingefrorene Rollensatz (E8). Der Rollenkatalog selbst steht als
> `CREW_ROLES` in `db.php`, nicht in der Datenbank (E4).
>
> **Damit ist auch die Falle verschwunden, die den separaten Ladeweg erzwang**:
> `missions` und `days` tragen keine gleichnamigen Spalten mehr. Die days-Zeile
> wird weiterhin separat geladen, aber aus einem anderen Grund — sie wird je
> Einsatz nur einmal gebraucht.

Das Leeren beim Entfernen des Hakens erledigt die generische
Checkbox-Kindlogik in `einsatz_form.php` ohne Sonderfall (Kinder werden bei
Haken = 0 auf NULL gesetzt).

**Freitext statt Auswahl (ab Web 5.5.0, Block A4.2).** Die fünf Felder sind
`'type' => 'text'` mit `suggest_src => 'crew:<rolle>'`: ein `<datalist>` mit
den Vorbelegungen der Rolle aus `crew_presets`, wie überall mit
`(user_id = ? OR user_id IS NULL)`, aber ohne Schranke. Fachlicher Grund: Wer
aushilft, steht typischerweise **nicht** in den Stammdaten — genau der Anlass,
aus dem eine abweichende Besatzung überhaupt eingetragen wird.

Bis dahin waren es `select` mit `options_src`. Dort brauchte es eine
Sonderregel, damit ein gespeicherter Wert, der nicht mehr in den Stammdaten
steht, nicht still verloren geht: Er wird beim Rendern der Liste vorangestellt.
Diese Regel gilt weiterhin für alle verbliebenen `options_src`-Selects (also
`bw_unit`); für die Besatzungsfelder ist sie gegenstandslos geworden — ein
Textfeld zeigt seinen Wert, ob er in einer Liste steht oder nicht.

Am Einlesen war dafür nichts zu ändern (Prüfschritt P4): `readField()`
behandelt `select` mit `options_src` und `text` identisch — leer wird NULL,
sonst auf `max` gekürzt. Die Spalten bleiben `VARCHAR(120)`, das `maxlength`
des Textfeldes zieht die Grenze jetzt auch sichtbar.

Die Uhr kennt keine Besatzung; `ingest.php` ist davon unberührt.

**Rollenfilter der Besatzungsfelder (ab Web 2.7.1):** Welche Rollen im
Einsatzformular erscheinen, bestimmt der **eingefrorene Rollensatz des
Diensttags** — die Zeilenmenge in `day_crew`, nicht der heutige Stand des
Rettungsmittels (E8). Der Satz stammt von den Häkchen in `vehicle_roles`, wie
sie beim Zuordnen galten. Deklariert wird das je Feld über `role_gate` in
`mission_fields.php`; `einsatz_form.php` lädt die Rollen einmal über
`dt_crew()` und setzt beim Rendern nur das `hidden`-Attribut.

Dieselbe Mechanik tragen zwei weitere Filter: **`cap_gate`** prüft die
eingefrorenen Fähigkeiten (`day_capabilities`) und steuert damit Winde und
Bergwacht, **`kind_gate`** die Art des Diensttags. Alle drei laufen über
`mf_gates_erfuellt()`.
**Nicht gerenderte Felder wären ein Datenverlust-Pfad** — der Browser sendet
sie dann nicht mit, und `readField()` liest fehlend als leer und überschreibt
den Bestand mit NULL. Deshalb wird immer gerendert und nur versteckt (`hidden`
verhindert das Absenden nicht). Zwei Rückfallregeln: Ein Feld mit Wert bleibt
sichtbar (sonst unerreichbar nach einem Wechsel des Rettungsmittels am
Diensttag). Ein Diensttag **ohne** Rettungsmittel zeigt keine Rollen (E26) —
anders als bis Web 5.10.0, wo dann alle fünf Flugrollen erschienen: Mit zwei
Arten gibt es keine sinnvolle Vorgabe mehr, und geraten wird nicht.

Der **Unterschied zwischen Verstecken und Leeren** ist die wichtigste Regel
dieses Bereichs und steht ausführlich in Abschnitt 4.98b: Ein durch `role_gate`,
`kind_gate` oder `cap_gate` **gefiltertes** Feld behält seinen Inhalt und wird
nur versteckt; ein durch `show_if` **ausgeschlossenes** Unterfeld wird geleert.

Das Diensttag-Formular filtert nach demselben Rollensatz, dort aber
clientseitig (`index.php`, `updateCrewFields()` aus der Antwort von
`api/day.php`), weil das Rettungsmittel im Formular selbst gewechselt werden
kann. Im Einsatzformular steht es fest, daher serverseitig.

**Einsatzort-Höhe:** `site_elevation_lib.php` (`compute_site_elevation()`) ist
die **einzige Implementierung** — Referenzzeitpunkt Phase 5 „Ankunft
PatientIn", Fallback Phase 6, Toleranz 300 s (Konstante
`SITE_ELE_TOLERANCE_S`) zum zeitlich nächstgelegenen `track_points.ele`.
Aufgerufen von `ingest.php` (nach jedem Uhr-Upload), `einsatz_form.php` (nach
manuellem Speichern — Phasen ändern sich, der Track bleibt gleich),
`backup_lib.php` (nach Restore — aus den gerade eingespielten Phasen/Track neu
berechnet statt aus der Datei übernommen) und `update.php` (Backfill bei der
Migration). Kein Formularfeld, daher nicht in `mission_fields.php`.

Auf dem Uhr-Weg und beim Wiedereinspielen läuft die Berechnung **nach** dem
Abschluss der Transaktion und in einem eigenen Fehlerblock: Die Höhe ist ein
Komfortwert, und ein Fehler darin darf weder einen Upload noch eine
Wiederherstellung kosten (seit Web 4.6.0 auch beim Wiedereinspielen, vorher
stand der Aufruf dort innerhalb der Transaktion). Der Unterschied zwischen
beiden Wegen ist die Meldung: Der Uhr-Weg schweigt (die Uhr kann mit der
Auskunft nichts anfangen), das Wiedereinspielen zählt die Fehlschläge und gibt
sie als `hoehe_fehler` zurück — dort wertet ein Mensch aus, was angekommen
ist.

**Zeitraum-API:** `api/range.php` liefert alle Einsätze eines Jahres oder Monats
**bewusst ohne Trackpunkte** — bei einem ganzen Jahr wären das
Hunderttausende Koordinaten. Die Karte der Zeitraumansicht (Einsatzort-Pins)
nutzt stattdessen die Koordinaten im `pat_blob`, die der Browser für die
Tabellenspalten ohnehin entschlüsselt — keine zweite Entschlüsselung, keine
Serveränderung nötig. Zusätzlich liefert die API `winch_cycles` und
`site_ele_m` je Einsatz (Grundlage der Statistiktabelle) sowie `tage` neu aus
der `days`-Tabelle statt `COUNT(DISTINCT day)` aus `missions` — zählt also
auch einsatzfreie Diensttage mit (Divisor der Durchschnittswerte). Gezählt
werden **Zeilen, nicht Kalendertage**: Zwei Dienste an einem Tag sind seit
Web 6.0.0 zwei Diensttage. Die geschützten Angaben entschlüsselt der Browser
wie überall selbst.

Seit Web 6.2.0 kommen dazu: die **Art des Diensttags** (`kind`) und
`false_alarm` je Einsatz sowie `tage_art` mit den Diensttagen nach Art
(`air` / `ground` / `neutral`). Damit entscheidet der Browser über Tableiste,
Kachelsatz und Divisor, ohne je Tab nachzuladen — `tage_art` wird in SQL
gerechnet und nicht aus der Einsatzliste, weil ein Diensttag ohne Einsatz dort
nicht auftaucht, aber mitzählt.

**Fehlerbehandlung der Lese-/Schreib-APIs:** `api/range.php`, `api/day.php`,
`api/mission.php`, `api/suchindex.php` und `api/backup_data.php` kapseln ihre Datenbankzugriffe in
try/catch (Muster ursprünglich aus `api/backup_restore.php`) und antworten bei
einer Ausnahme mit `{"error": "<endpunkt>", "meldung": "<Exception-Message>"}`
statt eines leeren HTTP 500 — wichtig z. B. direkt nach einem Deploy mit
DB-Änderung, aber vor dem Aufruf von `/update.php`. Neue Endpunkte sollten
demselben Muster folgen. Die jeweiligen Frontends (`zeitraum.php`,
`einsatz.php`, `index.php`, `suche.php`) zeigen `error`+`meldung` in einer
Fehlerbox an.

**Suche (`suche.php`, `api/suchindex.php`, ab Web 3.1.0).** Gefiltert wird
vollständig im Browser. Das ist keine Optimierung, sondern eine Folge der
Ende-zu-Ende-Verschlüsselung: Einsatznummer, Name, Geburtsdatum, Diagnose und
Einsatzort liegen im `pat_blob`, der Server sieht davon nur Chiffretext und
kann darin nicht suchen. Zusätzlich wäre ein Suchbegriff wie ein Nachname
selbst schon ein Patientendatum — er darf den Browser gar nicht verlassen.
`api/suchindex.php` nimmt deshalb **keine Suchparameter entgegen**; es liefert
den kompletten aktiven Bestand der angemeldeten Person.

Mengengerüst: erwartet werden 50–80 Einsätze pro Jahr, nach zwei Jahrzehnten
also unter etwa 1 600 Datensätze — für einen einmaligen Abruf je Sitzung
unproblematisch. Trackpunkte und Phasenlisten sind bewusst **nicht** enthalten;
sie wären um Größenordnungen größer als alles andere und werden zum Filtern
nicht gebraucht. Der Endpunkt kommt mit fünf Abfragen aus, unabhängig von der
Zahl der Einsätze (kein N+1): Einsätze, Diensttage, Tagesbesatzung, abweichende
Besatzung, weitere Rettungsmittel.

Der Index liefert nur, was die Suche auch auswertet. Mit den Filtern Herkunft,
Reanimation, Reanimations-Ereignis und Höhe Einsatzort (Web 5.3.0) sind die
Felder `origin`, `site_ele_m`, `resus_count` und `resus_types` aus dem Index
entfallen, samt der beiden Abfragen über `resus_sessions` und `resus_events`.
Die Spalten selbst bleiben unverändert; Export, Einsatzansicht und Zeitraum-
Übersicht beziehen sie über eigene Endpunkte.

Eine Falle, die dort dokumentiert ist und bei Änderungen zu beachten bleibt:

- **~~days wird nicht per JOIN angebunden.~~** `missions` und `days` trugen
  beide `crew_p1`…`crew_other`; ein JOIN hätte sie überschrieben.
  **AUFGEHOBEN mit Web 6.0.0** (Konzept-Notarzt-Erweiterung, Abschnitt 4.11):
  Mit der Normalisierung der Besatzung (E7) gibt es keine gleichnamigen Spalten
  mehr, und damit entfällt der Grund. `missions.day_id = days.id` ist ab jetzt
  der **vorgesehene** Weg — er wird in `api/range.php`, `api/export_data.php`,
  `api/import_commit.php` und `trash_lib.php` benutzt. Die Regel steht hier nur
  noch, damit sie nicht als Halbwissen zurückkehrt.

  Die effektive Besatzung folgt unverändert der COALESCE-Regel: Einsatzwert nur,
  wenn `crew_override = 1` **und** die Rolle in `mission_crew` belegt ist, sonst
  die Besatzung des Diensttags aus `day_crew`.

`start_min` (Minuten seit Mitternacht, Grundlage des Alarmzeitfilters) wird aus
derselben `fmt_local()`-Umrechnung abgeleitet wie `start_hhmm`, damit Anzeige
und Filter nicht auseinanderlaufen können. Standort, Rettungsmittel und Art
stammen seit Web 6.0.0 aus den **Snapshot-Spalten des Diensttags**
(`base_name`, `vehicle_name`, `kind`) — nie aus den Stammdaten. Damit sind auch
Dienste auffindbar, deren Rettungsmittel inzwischen umbenannt oder gelöscht
wurde; der frühere Rückfall auf die Alt-Freitextspalten `days.base` /
`days.aircraft` ist entfallen, weil die Migration deren Inhalt genau dorthin
gerettet hat (Konzept, Berichtigung B6).

Seit Web 6.2.0 führt der Index zusätzlich `transport_mode`, `na_escort` und
`false_alarm` — die Klartextfelder der Etappe 2, **soweit die Suche sie
auswertet**. `dest_lat`/`dest_lon` und `start_src` bleiben draußen: Nach einer
Koordinate oder nach der Herkunft eines Abfahrtorts wird nicht gefiltert, und
der Index führt grundsätzlich nur, was die Suche auch benutzt.

**Filterzustand im URL-Fragment.** Der gesamte Zustand steht hinter dem `#`,
nie im Query-String: Fragmente werden nicht an den Server gesendet und landen
damit nicht im Zugriffsprotokoll. Geschrieben wird mit `history.replaceState`,
nicht über `location.hash` — sonst wüchse die Chronik mit jedem Tastendruck im
Suchfeld. Die Parameternamen sind Teil bereits verschickter Links und dürfen
**nicht** umbenannt werden:

| Kurz | Filter | Kurz | Filter |
|------|--------|------|--------|
| `q`  | Freitext | `st` | Standort |
| `dv` / `db` | Datum von / bis | `ac` | Rettungsmittel (hieß bis Web 5.10.0 „Maschine") |
| `zv` / `zb` | Alarmzeit von / bis | `art` | Art des Diensttags (`air` / `ground` / `neutral`) |
| `wd` | Wochentage (`1`=Mo … `7`=So, kommagetrennt) | `c1`…`c5` | Besatzung P1, P2, HEMS, FR, Sonstige |
| `wi` | Windeneinsatz (`j`/`n`) | `crew_driver`, `crew_trainee` | Besatzung Fahrer, Praktikant |
| `cv` / `cb` | Cycles von / bis | `rm` | Weiteres Rettungsmittel |
| `pv` / `pb` | Cycles mit Patient von / bis | `av` / `ab` | Alter von / bis |
| `lv` | Luftverladung (`j`/`n`) | `kv` / `kb` | Strecke von / bis (km) |
| `bw` | Bergwacht (`j`/`n`) | `ev` / `eb` | Einsatzdauer von / bis (min) |
| `bu` | Bergwacht-Bereitschaft | `s` | Sortierspalte |
| `ta` | Transportart (`air` / `ground` / `ambulant`) | `sd` | Sortierrichtung (`a`/`d`) |
| `nb` | NA-Begleitung (`j`/`n`) | | |
| `tz` | Transportziel | | |
| `se` | Sekundärtransport (`j`/`n`) | | |
| `sr` | Schockraum (`j`/`n`) | | |
| `fe` | Fehleinsatz (`j`/`n`) | | |

**Die Blöcke sind mit Web 7.0.0 neu geschnitten** — `einsatz` (Datum,
Alarmzeit, Wochentag, Strecke, Dauer, Fehleinsatz), `patient` (Alter),
`transport`, `wer`, `bergrettung` (Bergwacht **und** Winde). Die Gruppen `zeit`,
`winde`, `bergwacht` und `werte` gibt es nicht mehr. **Die Kurznamen sind
unverändert geblieben**: Sie stehen in verschickten Links, und nur die Zuordnung
`kurz → gruppe` hat gewechselt. Das wirkt allein darauf, welcher Block bei einem
geteilten Link aufgeht.

`art`, `ta`, `nb` und `fe` sind mit Web 6.2.0 dazugekommen. `art` und `ta`
tragen **gespeicherte Werte, nicht Beschriftungen** — sie stammen aus
`dt_art_symbole()` beziehungsweise aus dem Feldkatalog und nicht aus einer
zweiten Aufzählung in `suche.php`. Der neutrale Diensttag heißt im Fragment
`neutral`, weil sein Schlüssel im Katalog leer ist und der leere Wert im
Auswahlfeld schon für „(egal)" vergeben ist.

Die Zeitraum-Übersicht nutzt dasselbe Fragment für **einen** Wert: `t` ist der
gewählte Tab (`mix` / `air` / `ground`, siehe unten). Ein Fragment, das von Hand
geändert wird, wirkt auch auf der offenen Seite — `zeitraum.php` horcht auf
`hashchange`, weil ein Wechsel von `#t=ground` auf `#t=air` für den Browser
keine neue Seite ist und sonst Adresszeile und Bildschirm auseinanderliefen.

**Zurückgezogene Kurznamen (bis Web 5.2.0).** `hk` (Herkunft), `re`
(Reanimation), `rt` (Reanimations-Ereignisse), `hv` / `hb` (Höhe Einsatzort).
Sie werden **nicht neu vergeben**: Ein alter geteilter Link mit `hk=manual`
würde sonst unbemerkt einen anderen Filter setzen. `fragmentLesen()` verwirft
unbekannte Parameter still, alte Links führen also zu keinem Fehler — sie
ignorieren nur den entfallenen Teil.

Ein neuer Filter braucht drei Dinge: einen Eintrag in der Liste `FILTER` in
`suche.php` (mit `gruppe`), sein Feld im passenden `<details class="filtergruppe">`
der Filterspalte und seine Zeile in `trifft()`. Auslesen, Schreiben ins
Fragment, Wiederherstellen, das Zählen aktiver Filter und das Aufklappen der
Blöcke bei einem geteilten Link leiten sich alle aus `FILTER` ab. Die Gruppen
sind `einsatz`, `patient`, `transport`, `wer` und `bergrettung`; der Freitext
steht in der Hauptspalte und hat keine Gruppe.

Zwei Sichtbarkeitsregeln, beide gegen den **gesamten** Bestand geprüft (nicht
gegen die Trefferliste — sonst hüpfte die Spalte beim Tippen):
`GRUPPE_NUR_WENN` blendet einen ganzen Block aus (derzeit `bergrettung`),
`FELD_NUR_WENN` ein einzelnes Feld (derzeit `fe` = Fehleinsatz). Letzteres kam
mit Web 7.0.0 dazu: Der Fehleinsatz steht jetzt in einem Block, der bleiben
muss. Beide Regeln haben dieselbe Ausnahme — ein Filter aus einem geteilten
Link bleibt sichtbar, auch wenn der eigene Bestand nichts dazu hat.

**Layout (ab Web 9.5.0, O6).** Die Filter stehen in der **gemeinsamen**
`.leiste` — derselben, die sonst die Diensttage trägt; `suche.php` ruft
`ui_days_sidebar()` **nicht** auf (einzelne Diensttage sind bei einer Suche
über den Gesamtbestand ohne Nutzen), sondern baut den Leisteninhalt selbst.
Bis Web 9.4.0 hatte die Suche eine eigene Spalte (`.layout-suche`,
`.filterspalte`); das war die einzige Seite ohne mobiles Menü, weil der
Schubladenmechanismus an `.leiste` hängt — auf dem Handy stand die volle
Filterliste vor dem Ergebnis. Beide Klassen sind gestrichen.

Zwei Folgen davon:

- **Der Ereignisanker ist die Leiste, nicht eine Klasse am Behälter.** Der
  Zuhörer hängt an `#leiste` (`input` **und** `change`) und entscheidet am
  Ereignisziel (`ev.target.closest('input, select')`). Der alte Selektor
  `.filterspalte input, .filterspalte select` traf nach dem Wegfall der
  Klasse in O2 **nichts** — kein Filter wirkte mehr, ohne dass irgendetwas
  einen Fehler meldete (F-P3-AG). Ein Klassenname am Behälter ist als
  Ereignisanker zu leicht zu verlieren.
- **Die Filterblöcke sind dieselben `<details class="akkordeon">` wie die
  Diensttage.** `daylist.js` steigt ohne `.leiste-liste[data-akkordeon]`
  von selbst aus, die Filtergruppen werden deshalb **nicht** gegenseitig
  verkoppelt — mehrere Blöcke lassen sich gleichzeitig öffnen. Jede Gruppe
  trägt eine `.filterzahl` (blaue Plakette), die zählt, wie viele ihrer
  Felder gesetzt sind; `zeigeFilterzustand()` schreibt sie, den Zähler am
  Filterknopf und die Plakettenzeile `#filterplaketten` aus **einer**
  Quelle, damit die drei Anzeigen nicht auseinanderlaufen können.

Der Leistenfuß trägt „Filter zurücksetzen" und — nur als Schublade
(`nur-schublade`) — „n Treffer zeigen" mit der Zahl der laufenden Suche;
der Knopf schließt über `data-schublade="zu"`.

**Boolesche Freitextsuche (`assets/suchtext.js`, Baustein B10, ab Web 7.0.0).**
`EdSuchtext.pruefer(q)` liefert ein Prädikat über den (bereits
kleingeschriebenen) Heuhaufen eines Einsatzes, oder `null` bei leerer Eingabe.
Grammatik: rekursiver Abstieg über `oder → und → nicht → primär`, also ODER
schwächer als UND; Terme sind Wörter, Phrasen in Anführungszeichen und geklammerte
Ausdrücke. Schreibweisen: `UND`/`AND`/`&` (oder schlicht ein Leerzeichen),
`ODER`/`OR`/`|`, `NICHT`/`NOT`/`!` sowie ein freistehendes `-` vor einem Begriff.
Ein `-` mitten im Wort bleibt Teil des Wortes („St.-Anna"); maßgeblich ist das
Zeichen **davor**, nicht das vorherige Token.

Der Parser bemängelt **nichts**: Die Trefferliste rechnet bei jedem Tastendruck
neu, und `(sturz` ist auf dem Weg zu `(sturz ODER fraktur)` unvermeidlich.
Fehlende schließende Klammern gelten als gesetzt, ein Operator ohne rechte Seite
wird übergangen, und ein gar nicht deutbarer Ausdruck fällt auf die alte
UND-Regel zurück (`einfach()`). Das Prädikat entsteht **einmal je Eingabe** in
`anwenden()`, nicht je Einsatz — bei 1 600 Datensätzen wäre das sonst 1 600 Mal
dieselbe Arbeit.

Bei gesperrtem Inhaltsschlüssel bleiben die geschützten Felder aus dem
Heuhaufen der Freitextsuche und der Altersfilter ist abgeschaltet — sonst wäre
mit gesetztem Altersfilter jeder Einsatz ein Nicht-Treffer. Nach dem Entsperren
wird der Heuhaufen neu gebaut und sofort neu gefiltert.

**Trefferhervorhebung (`woerter()`/`hervor()`, ab Web 9.5.0).** `woerter(q)`
läuft über denselben Zerleger und sammelt die **positiven Literale** —
Wörter und Phrasen, die nicht unter einem NICHT stehen; Operatoren fallen
weg. `hervor(maskiert, liste)` setzt darin `<mark class="treffer">`.

Zwei Punkte, an denen es schiefgehen könnte:

- **`hervor()` bekommt den Text BEREITS MASKIERT** und darf ihn nur noch
  umschließen. Anders herum — erst hervorheben, dann maskieren — würde das
  eigene `<mark>` mitmaskiert; und maskiert man gar nicht, wäre ein `<` aus
  Diagnose oder Einsatzort plötzlich Markup. Das betrifft **verschlüsselte**
  Felder, also fremden Klartext im eigenen DOM.
- **Verneintes wird nicht hervorgehoben.** Ein `-winde` bezeichnet nichts,
  was im Text stehen soll; eine Markierung dort behauptete einen Treffer,
  der die Zeile gerade ausgeschlossen hätte.

Die Prüflogik ist unberührt: `pruefer()` und `woerter()` teilen sich den
Zerleger, aber `woerter()` liefert nur eine Liste — kein Prädikat, keine
Entscheidung über Treffer.

**Gemeinsame Einsatztabelle (`assets/missiontable.js`, ab Web 3.1.0).**
`zeitraum.php` und `suche.php` zeigen dieselbe Liste; Spalten, Sortierung und
Zeilenaufbau stehen deshalb genau einmal dort. `EdMissionTable.erzeuge()` baut
Kopf und Rumpf in ein übergebenes `<table>`; die Formatierer (`fmtTag`,
`fmtDur`, `fmtKm`, `extractOrt`, `esc`) sind zusätzlich einzeln exportiert,
weil `zeitraum.php` sie auch für Karten-Popups und Kacheln braucht. `esc` und
`escape` zeigen seit Web 4.6.0 beide auf `EdHtml.escape` (`assets/html.js`);
die Datei muss deshalb **vor** `missiontable.js` geladen werden. Eine neue
Spalte ist ein Eintrag in `SPALTEN` und erscheint auf beiden Seiten.

Seit Web 9.5.0 baut derselbe Aufruf wahlweise **Tabelle und Kacheln** aus
demselben Zeilenbestand (`opts.kacheln` = Zielelement, `opts.kachelOpts`):
Die Tabelle liegt in `.nur-ab-720`, die Kachelliste in `.nur-unter-720`, und
weil beide aus einer Zeichnung stammen, können sie nicht auseinanderlaufen.
`opts.hervor` reicht die Hervorhebungsfunktion an die geschützten Zellen und
an die Kachel durch — sie ist der einzige Weg, auf dem fremder Text als HTML
in eine Zelle kommt, und sie bekommt ihn deshalb maskiert (siehe oben).

Die **Streifenspalte** (`key: 'col'`) erscheint über `nurWenn` nur, wenn
Zeilen eine Spurfarbe tragen; sie trägt als einzige Spalte ein
`style="background:…"`, weil die Farbe aus den Daten kommt (dieselbe
begründete Ausnahme wie beim Kachelstreifen aus O3 — eine Farbe je Einsatz
kann keine CSS-Regel sein).

Eine Rücksicht auf `zeitraum.php`, die sonst als Regression auffiele:
`onAfterDraw` wendet dort die Hervorhebung der Extremwert-Kacheln erneut an —
die Zeilen sind nach jedem Zeichnen neu und hätten ihre Markierung sonst
verloren. (Die frühere zweite Rücksicht, `pfeilInitial: false`, gibt es seit
M6-10 nicht mehr: Der Sortierpfeil steht auf beiden Seiten von Anfang an.)

**Seitengrösse (`opts.seite`, ab Web 5.10.0).** Ohne diese Option zeichnet die
Tabelle jede Zeile — so verhält sich `zeitraum.php` weiterhin. `suche.php`
setzt **200**: Dort steht beim Öffnen der gesamte Bestand zur Auswahl, und
`anwenden()` zeichnet bei **jedem** Tastendruck im Suchfeld neu; bei einigen
tausend Einsätzen ist der Aufbau der `<tr>` die teuerste Einzelheit der Seite.

Begrenzt wird ausschliesslich die Anzeige — sortiert und gezählt wird über die
volle Liste, geschnitten wird erst danach (`sortiert.slice(0, sichtbar)`).
`onAfterDraw` bekommt deshalb **zwei** Zahlen, `(gesamt, gezeigt)`; ohne
Seitengrösse sind sie gleich, und ein Aufrufer, der nur die erste liest, bleibt
richtig. Die Nachladezeile (`.mehrzeile`) erzeugt der Baustein selbst und hängt
sie hinter das `<table>` — sonst müsste jede Seite, die eine Seitengrösse
setzt, auch noch ein Element dafür vorsehen, und die erste, die es vergisst,
begrenzt still. `setData()` setzt auf die erste Seite zurück (neuer Filter =
neue Liste), ein Sortierwechsel **nicht** (dieselbe Liste, andere Reihenfolge).
Der Fokus wandert nach dem Nachladen nur dann in die erste neue Zeile, wenn die
Schaltfläche dabei verschwindet; sonst bliebe die Tastaturbedienung an einem
Element hängen, das es nicht mehr gibt.

Weil `suche.php` die Bestandszahl aus denselben zwei Zahlen baut, steht ihr
Text in `onAfterDraw` und nicht in `anwenden()` — das Nachladen zeichnet neu,
ohne dass sich ein Filter geändert hätte. Seit Web 9.5.0 steht sie als
`.karte-zahl` im Kopf der Trefferkarte und nennt „n von m" nur bei gesetztem
Filter; `onAfterDraw` bekommt dafür als dritten Wert die sortierte Liste, aus
der die Streckensumme fällt.

**Filterblöcke nach Bestand (`GRUPPE_NUR_WENN` in `suche.php`, ab Web 5.10.0).**
Ein Eintrag je Block: die Bedingung, unter der er gebraucht wird (heute `winde`,
`bergwacht` und seit Web 6.2.0 `einsatz`). Geprüft wird der **gesamte** Bestand,
nicht die aktuelle Trefferliste — sonst verschwände ein Block, sobald ein
anderer Filter die betreffenden Einsätze gerade ausschliesst, und die Spalte
spränge beim Tippen. Ein Block, in dem ein Filter gesetzt ist (geteilter Link),
bleibt sichtbar; `gruppenSichtbarkeit()` läuft deshalb beim Start **nach**
`fragmentLesen()` und erneut nach „Filter zurücksetzen".

**Spalten nach Bestand (`nurWenn` in `assets/missiontable.js`, ab Web 6.2.0).**
Dieselbe Überlegung eine Ebene tiefer: Eine Spalte, die im ganzen Bestand leer
bleibt, kostet auf schmalen Geräten Platz und sagt nichts. `nurWenn` bekommt den
Bestand und entscheidet, ob die Spalte überhaupt erscheint — heute `art` (mehr
als eine Art vorhanden), `winch`, `bw` und `fehl`. Welche Liste der Bestand ist,
sagt die Seite mit `setSpaltenBestand()`: `suche.php` setzt ihn **einmal** auf
den Gesamtbestand (sonst käme und ginge die Windenspalte beim Tippen),
`zeitraum.php` bei jedem Tabwechsel auf die Einsätze des Tabs. Ohne den Aufruf
gilt die Trefferliste selbst. **Sortiert** wird weiterhin über alle Spalten,
auch über verborgene: Ein geteilter Link kann nach einer Spalte sortieren, die
der eigene Bestand nicht zeigt — die Reihenfolge stimmt dann trotzdem, nur der
Pfeil hat keinen Kopf.

**Artsymbole an einer Stelle.** `dt_art_symbole()` (`diensttag_lib.php`) liefert
🚁 / 🚑 / ◌ samt Textalternative. `dt_art_symbol()` greift darauf zu, und
`zeitraum.php` wie `suche.php` setzen die Liste **vor** `missiontable.js` als
`ART_SYMBOLE` — dasselbe Muster wie `CREW_ROLLEN` in `import.php`.
`missiontable.js` führt einen Rückfall, damit die Datei für sich lauffähig
bleibt; er ist die Notlösung, nicht die Quelle.

**Ansicht nach Art in der Zeitraum-Übersicht (ab Web 6.2.0, seit Web 9.6.0
eine Segmentwahl).** Die Wahl erscheint nur, wenn im Zeitraum **beide** Arten
vorliegen; maßgeblich sind die Diensttage (`tage_art` aus `api/range.php`),
nicht die Einsätze — ein bodengebundener Dienst ohne einen einzigen Einsatz
ist trotzdem einer. Liegt nur eine Art vor, bestimmt sie allein die
**Beschriftung** der Kacheln; gezeigt wird in diesem Fall alles, auch die
Einsätze neutraler Diensttage, denn sonst fehlten sie in der einzigen Ansicht,
die es dann gibt. Der Hinweis auf mitgezählte neutrale Diensttage steht
überall dort, wo sie tatsächlich mitzählen — in „Gemischt" und in einer
Ansicht ohne Wahl.

Aus der Tableiste (`.arttabs` mit `<button role="tab">`) ist mit O7 der
**Segment-Baustein** geworden (`ui_segment`, Radios in einer Gruppe). Zwei
Folgen: Der Wechsel mit den Pfeiltasten kommt vom Browser — der eigene
`keydown`-Handler ist entfallen —, und gehorcht wird `change`, nicht `click`;
sonst löste die Tastaturbedienung nichts aus.

**Drei Kachelsätze (`KACHELSATZ` in `zeitraum.php`, ab Web 9.6.0).** Sie
entstehen im Browser statt fest im HTML zu stehen: Welche es gibt und wie sie
heißen, hängt an der Ansicht und bei den Windenkacheln zusätzlich am Bestand.
Luft führt zehn Kacheln, Boden acht, **Gemischt vier** (`KACHELN_BODEN.slice(0,4)`)
— Kilometer, Dauern und Fehleinsätze lassen sich über beide Arten nicht
sinnvoll addieren, ebenso wenig wie höchster Einsatzort und Windenzahlen, die
dort nie standen. `SPALTEN_JE_SATZ` gibt die Spaltenzahl ab 720 px (4 oder 5),
damit keine Reihe halb leer bleibt.

Jede Kachel trägt `wert` und `einheit` **getrennt** — der Baustein setzt die
Einheit kleiner, und das geht nur als eigenes Element. Vier Kacheln je Satz
sind mit `mobil: true` markiert und unter 720 px sichtbar; der Rest trägt
`.kennzahl-mehr` und steht hinter „Weitere Statistik (n)". Welche vier, sagt
die Kachel und nicht ihre Position: In der Luftansicht sind es die
Winden-Cycles statt des Durchschnitts. Fällt eine markierte Kachel am Bestand
weg (keine Windeneinsätze), rückt die nächste des Satzes nach. Die Ereignisse der Extremwert-Kacheln
werden deshalb beim Erzeugen vergeben, nicht am Raster delegiert — `mouseenter`
steigt nicht auf, und `mouseover` feuerte zusätzlich bei jedem Wechsel zwischen
Wert und Beschriftung innerhalb derselben Kachel. Die Karten-Pins werden beim
Wechsel der Ansicht **verworfen und neu gesetzt** (`pinLayer`), nicht
versteckt: Ein Pin ohne Bildschirmposition lässt kein `setStyle()` zu —
derselbe Stolperstein wie beim Ausgangsausschnitt der Karte.

Die Hervorhebung des Extremwert-Trägers wirkt auf **Tabelle und Kacheln**
zugleich (`.hl-extrem` an beiden), weil unter 720 px die Kachelliste an die
Stelle der Tabelle tritt; sie ist seit Web 9.6.0 orange statt rot. Die beiden
Farben dafür standen als Hexwerte im Skript und kommen jetzt aus `:root`,
wie in `geo.js`.

**Standorte des Zeitraums (`bases` in `api/range.php`, ab Web 9.6.0).** Die
Zeitraumkarte trägt das Standort-Haus (E-P3-40). Der Endpunkt liefert dafür
die eingefrorenen Standorte der Diensttage des Zeitraums, nach Koordinate auf
sechs Nachkommastellen **entdupliziert** — ein Monat mit fünf Diensten
derselben Wache hat einen Standort, nicht fünf übereinander. Sie sind
**Klartext** wie `kind` und `vehicle_name` (Snapshot-Spalten in `days`, E8)
und brauchen deshalb keinen Inhaltsschlüssel: Die Karte zeigt das Haus auch
im gesperrten Zustand. In den Artenansichten stehen nur die Standorte dieser
Art; Standorte ohne Art bleiben immer stehen, weil sie zu beidem gehören
könnten. Trackpunkte liefert der Endpunkt weiterhin **nicht** — bei einem
ganzen Jahr wären das hunderttausende Koordinaten.

**Logo-Wahl je Profil (E-P3-20, ab Web 9.7.0).** `users.logo_wahl` trägt die
Wahl, die Sitzung ihr **Ergebnis**. Der Unterschied ist die ganze Sache:
`logo_aufloesen()` (session_lib.php) macht aus `wechselnd` genau einmal —
bei der Anmeldung — ein `hubschrauber` oder `fahrzeug`, und `$_SESSION`
trägt danach diesen Wert. Würde stattdessen die Wahl in der Sitzung stehen
und `ui_logo()` bei jedem Aufruf würfeln, spränge das Logo beim Blättern von
Seite zu Seite.

`logo_stamm()` ist die **eine Stelle**, an der aus der Sitzung ein
Dateistamm wird; `ui_logo()` (Kopfleiste) und `favicon_tags()` (db.php,
Browser-Symbol) fragen beide dort. Damit können sie nicht auseinanderlaufen —
zwei getrennte Abfragen wären zwei Gelegenheiten dafür. Die `.ico` in der
Wurzel bleibt unverändert: Sie ist der Rückfall für Browser ohne PNG-Icon,
und eine zweite je Logo wären zwei Dateien für einen Fall, den heute kaum ein
Browser braucht.

`auth_guard.php` lädt `session_lib.php` deshalb **fest** und nicht mehr nur
im Abbruchzweig. Ohne Sitzung — Anmeldung, Einrichter — liefert `logo_stamm()`
den Standard, und genau das soll die Anmeldeseite zeigen.

**Papierkorb (Soft-Delete):** Einsätze, Ruhesegmente und Diensttage tragen
`deleted_at`; alle Lesepfade (Übersicht, Tages-/Einsatz-/Zeitraum-API,
Tagesliste, **Export**) filtern darauf. **Das Backup nicht mehr** — seit Web
8.0.0 führt es den Papierkorb und spielt ihn als Papierkorb zurück
(`docs/Backup-Format.md` 2 und 3). `trash_lib.php` bündelt Umfangsermittlung,
weiches Löschen, Wiederherstellen und endgültiges Entfernen; der Job
`aufraeumen` (`jobs_lib.php`) räumt nach `TRASH_DAYS` (**90**) endgültig ab. Beim Löschen eines
Diensttags werden dessen Einsätze/Segmente mit `deleted_with_day = 1` markiert —
sie hängen am Tag und kehren mit ihm zurück.

**Ein aktiver Eintrag an einem gelöschten Diensttag ist ausgeschlossen** (seit
Web 8.0.0, Backlog Nr. 33). Er wäre halb sichtbar: in Suche und Einsatzseite
ja, in Tagesübersicht, Zeitraum, Export und Nachbearbeitung nicht (alle joinen
`days`), im Formular nicht zu öffnen — und beim endgültigen Löschen des Tages
bliebe er ohne `day_id` zurück. Vier Stellen halten das:
`trash_restore_mission()` lehnt ab, solange der Diensttag im Papierkorb liegt
(und liefert dafür einen Grund statt `void`); `dt_zu_dayref()` und der
`$vorhandenerDayId`-Zweig in `ingest.php` übergehen gelöschte Tage, sodass die
Uhr einen **neuen** Tag auslöst (die Dienstkennung in `day_refs` wird auf ihn
umgebogen); `trash_purge_day()` nimmt **alles** am Tag mit statt nur das
Gelöschte, und die Rückfrage nennt das Aktive vorher einzeln
(`trash_aktiv_am_tag()`); und beim Einspielen eines Backups gilt E-S1-19.
Altbestand meldet `update.php` unter „Einsätze ohne Diensttag" — als Bericht,
nicht als Migration. `ingest.php` quittiert Uploads für
Einträge im Papierkorb, verwirft sie aber; erst das endgültige Löschen schreibt
die Referenz nach `deleted_refs`. Schwere Löschungen laufen über serverseitige
Zwischenseiten mit Umfangsanzeige statt über Browser-Dialoge.

**Schutz bearbeiteter Einsätze:** Beim Ingest wird vor dem Upsert der
`manual`-Marker geprüft. Ist er gesetzt, werden Metadaten/Phasen/Rea **nicht**
angefasst; Trackpunkte laufen weiter ein (append-only). Gesetzt wird der
Marker beim Speichern im Bearbeitungsformular, bei Handanlage und beim
Import. Die **Herkunft** eines Einsatzes (`origin`: `watch`/`manual`/`import`)
ist davon unabhängig — sie wird einmalig beim Anlegen gesetzt und danach nie
mehr verändert, auch nicht durch einen erneuten Import. Ob ein Einsatz nach
dem Anlegen verändert wurde, steht separat in `edited`. Ein von der Uhr
aufgezeichneter, später bearbeiteter Einsatz bleibt also `origin='watch'`
und bekommt `edited=1` — er wird in der Einsatzansicht als „Uhr" +
„editiert" angezeigt, nicht als „manuell" (Abschnitt Handbuch 4.2).

**Zeitbehandlung:** Speicherung UTC (`DATETIME`), Anzeige über `fmt_local()`
(Europe/Berlin). Das Formular rechnet lokale Eingaben nach UTC um; Zeiten
„nach Mitternacht" (kleiner als die vorherige) erhalten +1 Tag.

> **PHP-Falle:** Numerische
> Array-Schlüssel werden zu Ganzzahlen; unter `strict_types` bricht `e()` dann
> ab. Bei Jahr/Monat-Gruppierungen überall `(string)`-Umwandlung und `str_pad`.

**Import fremder Einsatzlisten** (`import.php`, Web 2.8.0): Läuft bis auf den
letzten Schritt vollständig im Browser — nicht aus Bequemlichkeit, sondern
zwingend: Die Dateien enthalten Name, Geburtsdatum, Diagnose und Einsatzort,
und diese Angaben dürfen den Rechner nur verschlüsselt verlassen. Ein
Datei-Upload ist damit ausgeschlossen. Kette:

1. `assets/vendor/xlsx.full.min.js` (SheetJS 0.18.5, Apache-2.0, lokal
   vendoriert) liest xlsx/xls/csv/ods.
2. `assets/import_profiles.js` beschreibt **deklarativ**, wo die Daten stehen
   und wie jede Quellspalte auf ein Zielfeld abgebildet wird
   (Blatt, Kopfzeile, `expectedHeaders`, `columns` mit Parserkette,
   `params` für Angaben, die die Datei nicht enthält). Ein weiteres Format
   heißt: einen Eintrag ergänzen — an der Pipeline ändert sich nichts.
3. `assets/import.js` ist reine Rechenlogik ohne Oberfläche und ohne
   Netzverkehr: Parser-Registry, Profilerkennung über Kopfzeilen-Treffer,
   zeilenweise Prüfung (`ok`/`warn`/`error`), Gruppierung nach Diensttag.
   Die Tagesbesatzung ist die der frühesten Zeile; abweichende spätere Zeilen
   werden zu `crew_override` am einzelnen Einsatz.
4. `assets/import_ui.js` zeigt die Review-Tabelle, nimmt Korrekturen entgegen
   (jede Änderung rechnet die Prüfung komplett neu), löst Konflikte auf und
   verschlüsselt die Patientendaten mit `EdCrypto`.
5. `api/import_commit.php` kennt zwei Aktionen. `check` gleicht mit dem
   Bestand ab und bekommt dafür seit Web 2.9.0 **nur** Datum und Uhrzeit zu
   sehen — die Einsatznummer liegt verschlüsselt im `pat_blob` und wird dem
   Server nicht mehr im Klartext übergeben. Für den Nummernabgleich liefert
   `check` deshalb je vorhandenem Einsatz den `pat_blob` mit; `import_ui.js`
   entschlüsselt ihn lokal (`bestandEinsatznummernIndex`) und vergleicht dort.
   Dadurch werden Nummerndubletten nur noch innerhalb der Diensttage erkannt,
   die in der Importdatei vorkommen — der Preis der Verschlüsselung. Tag und
   Alarmzeit bleiben als zweites, uneingeschränktes Merkmal wirksam.
   `commit` schreibt in **einer** Transaktion.
6. **`commit` mit `dup: 'overwrite'` löscht nichts, was die Datei nicht
   kennt** (seit Web 5.8.0, Prüfschritt P10). Die Felder unter der
   Export-Schranke — `crew_p1`…`crew_other`, `bw_info`, `other_ema`, `notes`,
   `site_ele_m`, `pat_blob` — stehen im `UPDATE` als `COALESCE(?, spalte)`,
   dasselbe Muster, das der Diensttag-Pfad seit jeher benutzt. Vorher schrieb ein
   Rückimport ohne personenbezogene Angaben `NULL` über einen vollständigen
   Bestand. Die Phasen werden weiterhin komplett ersetzt; liefert die Datei zu
   einer Phase keine Koordinaten, erbt die neue Zeile die der bisherigen
   gleicher Nummer (der Reihe nach). Der Preis: Felder unter der Schranke
   lassen sich per Import nicht mehr gezielt **leeren**.

Zwei Fallstricke, die dort bewusst gelöst sind:

- **Excel-Zeiten niemals über `Date` einlesen.** Excel speichert Uhrzeiten als
  Bruchteil eines Tages ab 1899; ein daraus gebautes JavaScript-Datum bekommt
  die damalige Zonenzeit aufgerechnet (Mitteleuropa: 53 Minuten). Aus 10:41
  würde lautlos 09:48. `import.js` zerlegt die Rohzahl selbst
  (`XLSX.SSF.parse_date_code`), ohne Zeitzonenbezug.
- **Jeder importierte Einsatz braucht eine Phasenzeile (Phase 2).** Das
  Einsatzformular rekonstruiert Beginn und Ende aus den Phasen; ohne sie ließe
  sich ein importierter Einsatz nicht mehr bearbeiten.

Importierte Einsätze hängen am selben virtuellen Gerät `manual-<userId>` wie
von Hand angelegte (`final=1, manual=1, origin='import'`) — dadurch
überschreibt die Uhr sie nie, und in der Geräteliste tauchen sie nicht auf.
Ein erneuter Import auf einen bereits bestehenden Einsatz ändert `origin`
nicht (Herkunft bleibt unveränderlich), setzt aber `edited=1`.
`local_to_utc()` ist dafür von `einsatz_form.php` nach `db.php` gewandert;
zwei Kopien derselben Zeitrechnung wären die sicherste Art, sich später eine
Stunde Versatz einzuhandeln.

**Export** (`api/export_data.php` + `assets/export.js`, seit Web 2.10.0): Der
Endpunkt ist **ausschließlich lesend** und bewusst von `api/range.php` getrennt
— jenes bedient `zeitraum.php` und wurde schlank gehalten; eine Erweiterung
hätte diese Seite mitverändert. `action=meta` liefert Diensttage, Einsätze
(inklusive Phasen, weiterer Rettungsmittel, Reanimation und der *Anzahl*
Trackpunkte) und Ruhesegmente; `action=track` liefert die Punkte blockweise für
höchstens 25 IDs. Zeitstempel gehen als UTC nach ISO 8601 hinaus, die Umrechnung
in Ortszeit passiert im Browser — so nutzen Excel- und CSV-Profil dieselbe
Quelle. Obergrenze 5000 Einsätze je Anfrage.

**Herkunft und Bearbeitungsstatus** stammen ausschliesslich aus
`missions.origin` und `missions.edited`. Bis Web 3.3.2 berechnete
`api/export_data.php` die Spalte `herkunft` bei jedem Export neu aus `manual`
und dem Präfix von `client_ref` — eine Regel aus der Zeit vor der Migration
`2026_07_30_herkunft_bearbeitungsstatus`. Sie lieferte für genau einen Fall
etwas Falsches: Ein von der Uhr aufgezeichneter und danach im Formular
bearbeiteter Einsatz bekommt `manual = 1` und erschien deshalb als „manuell",
obwohl `origin` korrekt auf `watch` stand. Die Ableitung ist ersatzlos
entfallen, `client_ref` wird im Export nicht mehr gelesen. Die Abbildung auf die
deutschen Ausgabewerte steht in `EXPORT_ORIGIN_LABEL`.

**Die gleichlautende Ableitungsregel in `backup_lib.php`
(`edbak_origin_edited()`) bleibt bestehen** — dort ist sie nötig, weil Backups
der Formatversion 3 und älter die beiden Spalten nicht kennen. Diese Doppelung
ist gewollt und darf nicht als Rest der alten Logik entfernt werden.

Der gesamte Dateiaufbau läuft im Browser, weil der `pat_blob` nur dort
entschlüsselt werden kann. Ohne den Haken werden die betroffenen Felder schon
serverseitig **nicht selektiert**, nicht erst im Browser weggelassen.

**Der Haken heißt seit Web 5.8.0 „Personenbezogene Angaben einschließen"**
(Block A9). Der Schlüssel im Request bleibt `patient` — er ist der Vertrag
zwischen `export.js` und `export_data.php` —, aber er schaltet jetzt Besatzung
(Einsatz und Diensttag), `bw_info`, `other_ema`, die Notizen, `site_ele_m`, die
Phasenkoordinaten und den `pat_blob` gemeinsam ab; `action=track` wird ganz
abgewiesen. Die lokale Variable im Endpunkt heißt deshalb `$pers` und nicht
`$patient`. Zusätzlich entfernt `entpersonalisieren()` in `export.js` dieselben
Felder ein zweites Mal, bevor eines der drei Profile den Bestand sieht — eine
Stelle für alle drei, damit ein viertes Profil die Schranke nicht vergessen
kann. Was drin bleibt und warum, steht in `Export-Format.md`, Abschnitt 0.
Verpackt wird mit zip.js (AES-256 nach WinZip, `encryptionStrength: 3`);
ZipCrypto ist ausgeschlossen. Feldlisten und Konventionen: `Export-Format.md`.

Stolpersteine, die dabei aufgefallen sind:

- **SheetJS typisiert Datumszellen als `'n'`, nicht als `'d'`.** Eine Prüfung
  auf `cell.t === 'd'` greift nie; das deutsche Datumsformat wird dann still
  verworfen. `cell.z` wird deshalb ohne Typprüfung gesetzt.
- **Fette Schrift und Fensterfixierung kann die freie SheetJS-Ausgabe nicht
  schreiben.** `!freeze` wird beim Schreiben ignoriert, `cell.s` landet nicht in
  der `styles.xml` — beides sind kostenpflichtige Pro-Funktionen. Excel
  (Standard) verzichtet darauf, statt eine Datei zu erzeugen, die es vorgibt.
- **Der Spaltensatz des CSV hängt nicht am Haken.** Ohne Haken bleiben die
  betroffenen Spalten vorhanden und leer. Ein wechselnder Spaltensatz würde
  jeden einlesenden Importer zwingen, zwei Fälle zu unterscheiden. `felder.csv`
  trägt seit Web 5.8.0 eine Spalte `personenbezogen`; sie kommt aus dem
  Schlüssel `pers` am Feldkatalog, damit ein neues Feld die Kennzeichnung nicht
  stillschweigend vergessen kann. Nur der Ordner `tracks/` fehlt ganz — er ist
  keine Spalte, und ein leerer Ordner wäre keine Auskunft, sondern eine Frage.
- **Die Formatauswahl `#exp_fmt` ist ein `<select>`, kein Optionsfeld.** In
  `export.js` wird sie ausschließlich über `gewaehltesFormat()` gelesen. Wird
  daraus wieder ein `input[name="exp_fmt"]:checked`, liefert `querySelector`
  `null`, `syncFormat()` wirft beim `DOMContentLoaded` — und weil die
  Registrierung des Klick-Zuhörers die **letzte** Anweisung im Init-Block ist,
  bleibt „Export erstellen" danach vollständig tot, ohne sichtbare Meldung.
  Genau das ist in Web 3.1.1 passiert (behoben in 3.2.0). Beim Umbau von
  Bedienelementen auf dieser Seite gehören Markup und Skript zusammen.
- **Die Marker im Dateinamen gehören nur nach aussen.** `dateiName()` hängt
  seit Web 3.6.0 `mit-pers`/`ohne-pers` (bis Web 5.7.0: `mit-pat`/`ohne-pat`),
  `verschl`/`unverschl` und eine Kennung
  des Kontos an. Die Namen **innerhalb** des CSV-Archivs (`einsaetze.csv`,
  `felder.csv`, `LIESMICH.txt`, `tracks/`) bleiben davon unberührt: Sie sind
  Teil des Formats, und `import_ui.js` sucht im Archiv nach dem
  `archiveMember` des Profils — ein Marker daran würde den Rückimport
  verschlossener Archive brechen. Die Kontokennung kommt über `KONTO_NAME`
  und `KONTO_MAIL` aus `import.php` (Quelle: `auth_guard.php`); die
  Bereinigung zu einem dateisystemsicheren Segment (`slug()`) passiert im
  Browser.
- **Excel (Standard) und `export_excel_v1` sind aneinander gebunden.** Die
  Spaltenbeschriftungen in `SPALTEN_A` (export.js) müssen Wort für Wort den
  `expectedHeaders` des Importprofils (import_profiles.js) entsprechen, sonst
  lässt sich der eigene Export nicht mehr sauber zurücklesen: Der Importer
  meldet die abweichenden Spalten als unbekannt und lässt die zugehörigen
  Felder leer. Beide Listen folgen dem Wortlaut aus `mission_fields.php`.

**Rückimport** (`export_csv_v1`, `export_excel_v1`): Die Pipeline aus Web 2.8.0
bleibt unverändert, die neuen Formate sind reine Profileinträge plus zusätzliche
Parser (`isoTs`, `pipeList`, `jsonRea`, `dateIso`, `ganzzahl`, `dezimal`,
`dashLeer`). Drei Erweiterungen waren nötig:

- `api/import_commit.php` schrieb bisher nur Phase 2. Es schreibt jetzt alle
  Phasen 2–9 samt Koordinaten und die Reanimationsdokumentation — aber **nur,
  wenn die Nutzlast sie enthält**. Formate ohne diese Angaben verhalten sich
  unverändert, und eine vorhandene Reanimationsdokumentation wird von einem
  Format, das Reanimationen gar nicht kennt, nicht gelöscht.
- `explicitCrew` am Profil: Nennt die Datei Tages- und Einsatzbesatzung getrennt
  und sagt selbst, ob abgewichen wurde, rechnet `gruppiere()` das nicht noch
  einmal aus. Ohne das Flag bliebe die alte Heuristik (früheste Zeile = Tagescrew)
  und ein Einsatz, dessen abweichende Besatzung zufällig der Tagesbesatzung
  gleicht, verlöre sein `crew_override`.
- `emptyDayRows` am Profil: Im Excel (Standard) steht ein Diensttag ohne Einsatz
  als eine
  Zeile mit Datum und lauter `-`. Ohne diese Unterscheidung entstünde daraus
  beim Rückimport ein Einsatz ohne Alarmzeit. Solche Zeilen legen den Diensttag an
  und keinen Einsatz.

Pflichtangaben werden beim Rückimport **nach** der Parserkette geprüft: Das
Füllzeichen `-` ist beim Einlesen nicht leer, wird aber zu `null` — die Prüfung
vor der Kette sieht das nicht.

**Hintergrundjobs:** siehe Abschnitt 4.97a.

**Sicherheit:** HTTPS erzwungen (.htaccess), Session-Cookies
HttpOnly/Secure/SameSite=Strict, CSRF für Formulare (`csrf_field`) und
JSON-POSTs (Header `X-CSRF`), PDO Prepared Statements durchgängig,
Passwörter/Schlüssel nur als Hash, Ratenschutz an **allen** ohne Anmeldung
erreichbaren Endpunkten — Anmeldung, Salz-Abfrage, Zurücksetzen-Anforderung,
Kopplung (s. 4.99) —, Ingest mit Größen- (512 KB) und Wertevalidierung,
sensible Dateien per .htaccess gesperrt, Referrer-Policy
`strict-origin-when-cross-origin` (OSM-Kacheln).

### Die Antwortzeit als Auskunft

Vier Endpunkte antworten für „gibt es nicht" und „gibt es" absichtlich
gleichlautend. Wortgleichheit allein genügt aber nicht: Wo der eine Zweig
rechnet und der andere nicht, ist die **Dauer** dieselbe Auskunft, nur leiser.
Drei Fälle gab es, alle seit Web 4.4.0 geschlossen:

| Endpunkt | Was den Unterschied machte | Wie er geschlossen ist |
|---|---|---|
| `login.php` | bei unbekannter Adresse lief keine bcrypt-Prüfung | Prüfung gegen `AUTH_VERGLEICHSWERT`, dazu `rate_gleiche_dauer()` |
| `ingest.php` | bei unbekannter Gerätekennung lief keine bcrypt-Prüfung | dasselbe |
| `reset_request.php` | bei vorhandenem Konto lief ein vollständiges Mailgespräch | Antwort wird **vor** dem Versand abgeschlossen, dazu 0,5 s Mindestdauer |
| `auth_salt.php` | der unbekannte Zweig macht *mehr* Arbeit (zweite Abfrage, HMAC) | 50 ms Mindestdauer, drei Größenordnungen über dem Unterschied |

**Wie der Mailversand aus der Antwortzeit herauskommt.** `antwort_abschliessen()`
(`smtp.php`) beendet die Antwort, dann erst läuft `smtp_send()`. Zwei Wege, in
dieser Reihenfolge:

1. `fastcgi_finish_request()` (PHP-FPM) bzw. `litespeed_finish_request()`
   (LiteSpeed) — verbindlich.
2. Sonst Längenangabe und angekündigtes Verbindungsende. Der Gegenpart hat den
   Rumpf damit vollständig und wartet üblicherweise nicht weiter — aber
   „üblicherweise" ist keine Zusicherung, weil ein vorgelagerter Server puffern
   darf.

Welcher Weg auf der eigenen Installation greift, steht auf der **Wartungsseite
unter „Umgebung"**. Es ist die Eigenschaft, an der die Gleichheit beider Zweige
hängt, und sie ließ sich sonst nirgends ablesen.

**Bewusst keine Warteschlange.** Es gibt keinen Cronjob; die Wartung läuft
huckepack, höchstens einmal täglich. Eine Warteschlange hätte den Link zum
Zurücksetzen genau so lange liegen lassen, bis zufällig jemand eine Seite
aufruft. Der Preis des gewählten Weges: Auf Hosts ohne FPM oder LiteSpeed
bleibt der PHP-Arbeitsprozess nach dem Abschluss der Antwort noch bis zum
Zeitlimit des Versands belegt. Bei fünf Anforderungen je Stunde und Konto ist
das klein, aber nicht null — deshalb steht das Zeitlimit bei der Kopplung, wo
die Uhr wartet, auf fünf statt fünfzehn Sekunden.

### Was ein Gerät beim Koppeln über sich meldet — seit Web 12.9.0 gespeichert

Die Kopplung sendet neben dem Code einen Block `geraet`. Er kommt in **zwei
Formen**, weil die Geräte Verschiedenes über sich wissen: Die Garmin-Uhr
(seit 1.9.0) schickt ihre **Teilenummer** samt Displaymaßen, Touch, Firmware,
Plattform- und App-Fassung; die Android-Handy-App (seit 0.2.0) schickt
**Hersteller und Modell** statt der Teilenummer, dazu die API-Stufe. Feldliste
und Begründungen: `docs/JSON-Vertrag.md`, Abschnitt 1a.

**Bis Web 12.9.0 hat `pair.php` den Block stillschweigend verworfen** — ein
Jahr lang. Jede Kopplung aus dieser Zeit ist für die Statistik verloren; R42
hat das vorhergesehen und in Kauf genommen.

**Gespeichert werden drei Spalten, nicht zehn.** `geraet_art`,
`geraet_modell` und `geraet_teil` an `devices` (Abschnitt 3). Displaymaße,
Firmware, `ciq`/`sdk` und `app` kommen an und werden **nicht** gespeichert:
R36 lässt die Gerätekennung als die eine benannte Ausnahme der Formel „es wird
nichts Neues erfasst" zu, und die Ausnahme ist die Frage „welches Gerät", nicht
„in welchem Zustand". Backlog Nr. 59 hatte die weiteren Felder vorgeschlagen;
sie sind damit erledigt und fallen weg.

**Die Auflösung liegt auf dem Server.** Die Uhr kennt ihren Modellnamen nicht,
`DeviceSettings` führt ihn nicht — eine Modelltabelle auf einem Gerät mit
128 kB wäre der falsche Platz. Die Teilenummer ist dagegen eindeutig und gegen
die Gerätedateien der Uhr-Plattform auflösbar (325 Teilenummern auf 173
Modelle, samt Geräteart). Die Tabelle steht in `server/geraetemodelle.php` und
ist **erzeugt**: `tools/geraetemodelle/erzeugen.py`, aus denselben Dateien, mit
denen `tools/uhr-pruefstand/geraeteklassen.py` arbeitet.

**Stand: 325 Teilenummern auf 173 Modelle** (Web 12.9.1) — dieselbe Zahl, die
oben aus der Uhr-Seite stammt, und damit unabhängig bestätigt. **28 davon sind
keine Uhren**: 20 Edge, 8 Outdoor-Handgeräte.

> **Die Gerätedateien liegen nicht im Repositorium** — sie gehören Garmin und
> werden nur vom SDK-Manager ausgeliefert. Ihre Bereitstellung kommt als
> `CIQ_GERAETE_URL` herein und **muss erfragt werden**
> (`tools/uhr-pruefstand/LIESMICH.md`). Ohne sie erzeugt
> `erzeugen.py --leer` eine gültige, leere Tabelle: Die Anwendung läuft
> vollständig, löst aber nichts auf — jede Teilenummer landet unverändert in
> `geraet_teil`, und die Geräteliste zeigt „Uhr · 006-B4261-00" statt
> „Uhr · Venu 3S". Verloren geht dabei nichts; genau dafür steht die Rohangabe
> in einer eigenen Spalte, und `nachaufloesen.php` trägt später nach.

**Der Modellname ist ein Sammelname.** Die Gerätedateien führen je Teilenummer
die **Hardware**, und Garmin verkauft dieselbe Hardware unter mehreren Namen —
der längste Eintrag hat 153 Zeichen. Gespeichert wird der volle Name (die
Zählung in P5 soll Hardwaregruppen zählen), gekürzt wird erst für die Anzeige
auf sein erstes Glied: „Uhr · fēnix 6X Pro …".

**Bei der Geräteart schlägt die Tabelle die Selbstauskunft.** Die Uhr-App
sendet `art` fest als `"uhr"` — eine Connect-IQ-App läuft nur auf
Garmin-Geräten, und Uhr von Radcomputer unterscheiden kann sie nicht. Die
Gerätedateien können es. Ein Edge, der sich „uhr" nennt, hätte die Statistik
sonst still verfälscht.

**Der Block ist eine Selbstauskunft, keine geprüfte Wahrheit.** Er kommt von
einem Gerät, das sich beim Server erst vorstellt. `geraete_lib.php` schneidet
zu, statt zu glauben: Längen auf die Spaltenbreite (mit `mb_substr` — ein an
der falschen Stelle abgeschnittenes UTF-8-Zeichen macht die Spalte unlesbar),
Steuerzeichen zu Leerzeichen, eine Geräteart außerhalb der drei erlaubten
Werte zu `NULL`. **Eine Kopplung scheitert nie an einer Statistikangabe**
(JSON-Vertrag 1a): Ein Block, der gar keiner ist, ergibt drei leere Werte und
keinen Fehler. Nachweis ohne Datenbank: `php tools/geraeteprobe/probe.php`.

**Was bewusst nicht gesendet wird:** `uniqueIdentifier` (Uhr), `ANDROID_ID`,
IMEI und Seriennummer (Handy) — dauerhafte Gerätekennungen, die für eine
Stückzahl-Statistik nicht gebraucht werden und in einer kleinen Gruppe mehr
Personenbezug schaffen, als die Frage rechtfertigt.

**Die Auswertung ist P5** (Geräteverteilung im Betriebslage-Dashboard, R38).
Vorher muss die **Datenschutzerklärung die Erhebung benennen** (Backlog
Nr. 80) — bei einer Anwendung, deren Versprechen die
Ende-zu-Ende-Verschlüsselung ist, gehört das nicht als Nebenprodukt
eingeführt. Der Text entsteht nach R60/Schritt 10 aus einer Bestandsaufnahme
des gesamten Projekts, vor v1.0.

**Der Name des Geräts folgt der Art.** Beim Koppeln vergibt `pair.php` als
Bezeichnung „Uhr", „Handy" oder „Gerät". Bis Web 12.9.0 stand dort fest
„Uhr" — seit der Handy-App war das schlicht falsch. Wo keine Art gemeldet
wird, bleibt es bei „Uhr": Ein Gerät ohne Block ist eine Uhr-Fassung vor
1.9.0, und etwas anderes konnte damals nicht koppeln.

### Geräte je Konto

Höchstens **fünf** (`MAX_GERAETE` in `db.php`), geprüft beim Koppeln
(`pair.php`) und beim manuellen Anlegen (`einstellungen.php`). Gezählt werden
aktive **und** deaktivierte — ein deaktiviertes Gerät ist ein weiterhin
vorhandener Zugangsdatensatz, der sich mit einem Klick wieder scharf schalten
lässt. Löschen gibt einen Platz frei, Deaktivieren nicht.

Nicht mitgezählt wird das virtuelle Gerät `manual-<konto>` („Manuelle
Einträge"). Es entsteht von selbst beim Anlegen oder Importieren eines
Einsatzes, ist dauerhaft deaktiviert und taucht schon in der Geräteliste nicht
auf (`GERAETE_ECHT_SQL`). Zählte es mit, nennten Grenze und angezeigte Liste
verschiedene Zahlen.

Ist die Grenze erreicht, wird **gar kein Kopplungscode mehr erzeugt** — sonst
wäre er beim Einlösen verbraucht, ohne dass ein Gerät entsteht (`pair.php`
entwertet vor der Prüfung, und das ist dort richtig so). Wird trotzdem einer
eingelöst, antwortet `pair.php` mit 409 und `error: device_limit`.

**Hinweis bei neuen Geräten,** zwei Spuren: eine E-Mail an den Kontoinhaber
unmittelbar nach der Kopplung (erreicht die Person auch dann, wenn sie sich
gerade nicht anmeldet — genau der Fall eines abgefangenen Codes), und ein
Hinweis auf der Startseite sowie im Geräte-Reiter für alles, was in den letzten
`GERAETE_NEU_TAGE` Tagen hinzugekommen ist.

**Die Prüfsumme des Inhaltsschlüssels (`users.pat_key_check`).** Der Server
kann die Schlüsselhüllen nicht öffnen und daher nicht erkennen, ob eine neu
gespeicherte Hülle denselben Inhaltsschlüssel enthält wie die alte. Enthielte
sie einen anderen, wäre jeder vorhandene Datensatz endgültig unlesbar. Die im
Browser gerechnete Prüfsumme schließt genau diese eine Lücke, ohne dass der
Server etwas über den Schlüssel lernt — er vergleicht zwei Hashwerte, und der
Schlüssel ist 256 Bit Zufall.

Sie wird geprüft beim Passwortwechsel, beim Zurücksetzen über den
Wiederherstellungsschlüssel und beim Einspielen eines Backups; gesetzt wird
sie bei der Ersteinrichtung und bei jedem Setzen des Passworts. **`NULL` ist ein
gültiger Zustand** (Konten vor Web 4.0.0): Der Server kann sie nicht
nachträglich berechnen, also werden solche Konten angenommen und bekommen sie
beim nächsten Mal.

**Jede JSON-Antwort trägt `Cache-Control: no-store`** (ab Web 4.5.2, in
`json_out()`). Vorher setzte den Kopf genau ein Endpunkt; vier weitere liefern
denselben Chiffretext aus. Der Inhalt ist verschlüsselt, die Hülle darum herum
— Datum, Uhrzeit, Einsatznummer, Koordinaten — nicht. Der Kopf gehört deshalb
an die Stelle, durch die jede Antwort geht, nicht in die Zuständigkeit des
einzelnen Endpunkts. `api/backup_data.php` gibt direkt aus und setzt ihn eigens.

Die nur **lesenden** Endpunkte (`range`, `suchindex`, `mission`) weisen seit
4.5.2 alles außer GET mit 405 ab; `day.php` kennt GET und POST und weist alles
Übrige ab.

**Vier Wege, auf denen eine Sitzung endet** (ab Web 4.5.0). Abmelden, Ablauf
der Frist, gelöschtes Konto und Passwortwechsel — alle laufen über
`session_lib.php`. Der Grund für die gemeinsame Fassung: Eine reine
Weiterleitung per Kopfzeile führt nie JavaScript aus, die Schlüssel im
`sessionStorage` bleiben also liegen. Der Abmeldeweg löste das von Anfang an
richtig, der Ablaufpfad nicht — und weil die Lösung nur an einer der beiden
Stellen stand, war der Unterschied nicht zu sehen.

Bei Abrufen unter `server/api/` antwortet `auth_guard.php` stattdessen mit
**401 und JSON** (`session_verwerfen()`): Ein `fetch()`, das JSON erwartet,
sähe in der HTML-Seite nur einen Syntaxfehler beim Auswerten. Die Schlüssel
räumt dort die nächste Seitenanfrage, die ohnehin auf der Anmeldeseite landet.

**Rolle und Existenz des Kontos kommen bei jeder Anfrage aus der Datenbank**
(ab Web 4.5.0). Vorher stand die Rolle in der Sitzung, einmal bei der Anmeldung
geschrieben: Ein Rollenentzug wirkte erst nach dem nächsten Anmelden, ein
gelöschtes Konto arbeitete weiter. Die Nutzerzeile wurde ohnehin bei jeder
Anfrage gelesen — sie steht jetzt nur früher, und `$_SESSION['role']` gibt es
nicht mehr. Die eine Rollenprüfung heißt `ist_admin()`; `require_admin()` setzt
darauf auf, ebenso die Anzeigeentscheidungen in `ui.php`.

**Der Sitzungszähler (`users.session_epoch`).** Jeder Passwortwechsel erhöht
ihn in derselben Transaktion wie das Passwort; jede Anfrage vergleicht ihren
mitgeführten Stand dagegen. Wer noch den alten trägt, wird abgemeldet. Beides
in einer Transaktion ist wichtig: Ein erhöhter Zähler ohne geändertes Passwort
spülte alle Sitzungen hinaus, ohne dass etwas geschehen wäre; ein geändertes
Passwort ohne erhöhten Zähler ist genau der Zustand, den es zu beheben galt.

Die Sitzung, die den Wechsel auslöst, zieht den neuen Stand mit und bleibt
bestehen — sie soll ja nicht sich selbst aussperren. Sitzungen aus der Zeit vor
4.5.0 führen den Wert nicht mit; sie werden beim ersten Zugriff übernommen,
statt beim Aufspielen alle Angemeldeten auszusperren.

**Format der Client-Kennung.** `client_ref` wird von vier Stellen erzeugt, und
an ihrem Präfix hängt Verhalten: `m-`/`r-` (Uhr, Einsatz/Ruhe-Segment), `man-`
(Formular), `imp-` (Import), `bak-` (Wiedereinspielen ohne eigene Kennung).
Beim endgültigen Löschen wird die Kennung gesperrt, damit eine Uhr den
Datensatz nicht nachliefert — für `man-` bewusst nicht, dort gibt es keine Uhr.
Die verbindliche Beschreibung steht im JSON-Vertrag, Abschnitt 8.

**Zwei Stellen, an denen die Gleichheit von Antworten zählt.** Der
Salt-Endpunkt (`auth_salt.php`) und die Kopplung (`pair.php`) sind ohne
Anmeldung erreichbar. Bei `pair.php` gilt das seit Web 9.15.0 für **beide**
Anliegen: Der Trennen-Zweig läuft im unbekannten Fall gegen
`AUTH_VERGLEICHSWERT`, wie `ingest.php` — sonst wäre aus der Antwortdauer
ablesbar, welche Gerätekennungen es gibt, und die Kennung ist die Hälfte
dessen, was ein Upload braucht. Beide müssen für "gibt es" und "gibt es nicht"
Antworten liefern, die sich in **Länge, Zeichenvorrat, Aufbau und Dauer**
nicht unterscheiden. Beim Salt war es zuletzt die Länge, die alles verriet:
Ein echtes Salt hat 32 Hexzeichen, das Pseudo-Salt hatte 64. Wer hier etwas
ändert, prüft bitte beide Zweige nebeneinander.

**Der Aufruf einer Seite darf nichts verändern.** `update.php` führt
Migrationen erst auf eine bestätigte Absendung mit Formular-Token aus; der
Aufruf zeigt nur an, was anstünde. Migrationen können Spalten löschen, und
eine unwiderrufliche Handlung auf einen GET hin ist immer falsch — auch dann,
wenn nur Verwaltende die Seite erreichen.

### 4.97 Spurspeicherung: drei Stufen und ein Format (ab Web 10.0.0, S2)

**Spurpunkte sind 93 % des Bestands.** Gemessen am Referenzdatensatz kostet
eine Zeile in `track_points` **62,4 Byte**; derselbe Punkt als Blob kostet
**3,58** — ein Siebzehntel. Bei 5000 Einsätzen sind das 194 statt 3300 MB.
Deshalb liegen die Punkte seit S2 in drei Stufen:

| Stufe | Wo | Wann |
|---|---|---|
| 1 | Zeilen in `track_points` | solange die Uhr an dem Paket noch sendet |
| 2 | verlustfreier Blob in `track_blobs` | sobald das Paket abgeschlossen ist |
| 3 | ausgedünnter Blob | sechs Monate nach Einsatzende |

Stufe 1 bleibt, und zwar aus einem Grund: Der Upload der Uhr kommt in
Teilstücken, ist idempotent und wiederholbar. Dafür ist eine Zeilentabelle
richtig; ein Blob müsste bei jedem Teilstück neu geschrieben werden.

**Die Wanderung zwischen den Stufen macht `jobs.php` (AP2/AP3), nicht dieser
Abschnitt.** Was hier steht, ist das Format und der Zugriffsweg.

#### Der Zugriffsweg: `spur_lib.php`, und sonst nichts

Es gab sechs Stellen, die `track_points` per SQL lasen, jede mit einer eigenen
Projektion. Bliebe das so, müsste jede von ihnen die Stufen kennen — und die
erste, die es vergisst, zeigt eine leere Spur, ohne dass es auffällt. Alle
sechs sind umgestellt:

| Verbraucher | Funktion | Ausgabeform |
|---|---|---|
| Tagesansicht (`api/day.php`) | `spur_lesen_viele()` | `[lat, lon]` |
| Einsatzansicht (`api/mission.php`) | `spur_lesen()` | `[lat, lon]` + `ts` für `track_idx` |
| Export (`api/export_data.php`) | `spur_lesen_viele()`, `spur_zahlen()` | `[lat, lon, ele, ts]` |
| Backup, Nutzlast ≤ 7 (`backup_lib.php`) | `spur_lesen_viele()` | `[seq, lat, lon, ele, ts]` |
| Backup, Fassung 4 (`api/backup_spuren.php`) | `spur_fuer_sicherung_viele()` | SPUR1-Blob, roh |
| Rückweg der Fassung 4 (`api/backup_spuren_restore.php`) | `spur_blob_pruefen()`, `spur_blob_schreiben()` | — |
| Einsatzort-Höhe (`site_elevation_lib.php`) | `spur_lesen()` | ein Punkt |
| Umdatierung (`tageszuordnung_lib.php`) | `spur_min_ts()`, `spur_zeit_verschieben()` | — |

Dazu die Schreib- und Löschseite: `spur_naechste_seq()` liefert die
Fortsetzungsmarke der Uhr (`ingest.php`), `spur_loeschen()` entfernt **Zeilen
und Blob** und wird von jedem Löschweg gerufen. Seit Web 12.5.0 kommt
`spur_teilen()` dazu — der Schnitt, siehe Abschnitt 4.97e.

**`spur_lesen_viele()` setzt beide Stufen zusammen.** Zwischen Verdichtung und
Ausdünnung darf die Uhr Punkte nachreichen; sie landen als Zeilen *hinter* dem
Blob. Wer nur eine der beiden Stellen liest, zeigt eine Spur, der das Ende
fehlt — ohne Fehlermeldung.

**Das Umdatieren eines Diensttags** war bis Web 9.14.0 ein einziges
`UPDATE track_points SET ts = ts + ?`. An einem Blob geht das vorbei: Die
Zeilen wanderten, die Blobpunkte blieben stehen, und die Spur hätte danach
zwei Zeitrechnungen. `spur_zeit_verschieben()` schreibt den Blob deshalb neu.

#### Das Format SPUR1

Kopf unkomprimiert, 13 Byte:

```
'SP' | Fassung(1) | Stufe(1) | Auflösung(1) | n_original(uint32 LE) | n(uint32 LE)
```

Danach ein zlib-Strom (Stufe 9) über die Nutzlast, **spaltenweise**:

```
Breite-Differenzen (int32 LE × n)
Länge-Differenzen  (int32 LE × n)
Bitfeld ⌈n/8⌉ Byte — Bit gesetzt = dieser Punkt hat eine Höhe
Höhen-Differenzen  (int32 LE × Anzahl gesetzter Bits)
Zeit-Differenzen   (int32 LE × n)
```

Spaltenweise und nicht punktweise: Nebeneinander stehen dann Werte derselben
Größenordnung — lauter kleine Breitendifferenzen, dann lauter kleine
Längendifferenzen. zlib findet darin Muster; in der Reihenfolge
`lat,lon,ele,ts,lat,lon,…` findet es keine.

`seq` wird **nicht** gespeichert: Die Verdichtung setzt Lückenlosigkeit voraus,
die Position im Blob *ist* die Nummer.

#### Die Auflösung ist eine Zusage, kein Rechenweg

| Größe | Faktor | Auflösung |
|---|---|---|
| Breite, Länge | ×10⁶ | ≈ 0,11 m |
| Höhe | ×10 | 0,1 m |
| Zeit | ×1 | 1 s |

**Keine Festkomma-Kodierung ist bitgleich gegen einen beliebigen `DOUBLE`.**
„Verlustfrei" in Stufe 2 heißt deshalb: verlustfrei *innerhalb dieser
Auflösung*. Sie steht als Kennung im Kopf, und ein Leser, der eine ihm
unbekannte Kennung findet, **verweigert die Arbeit** — sonst deutete er Zahlen
mit dem falschen Faktor, und zwar lautlos.

Die Höhe in ganzen Metern abzulegen wäre nicht nur ungenauer gewesen, sondern
hätte den Mechanismus stillgelegt: 74,4 % der Punkte des Referenzbestands
tragen eine Nachkommastelle, die Rundlaufprüfung hätte bei drei von vier
Spuren angeschlagen, und der Verdichtungsjob hätte nie eine Zeile gelöscht.
Der Preis der Zehntelmeter sind 7 % Blobgröße.

#### Die Rundlaufprüfung ist die letzte Instanz vor einem DELETE

Die Verdichtung löscht Zeilen. Was danach fehlt, ist weg — es gibt keine
zweite Quelle. `spur_rundlauf_pruefen()` schreibt den Blob, liest ihn sofort
wieder und vergleicht Punkt für Punkt; erst bei Gleichheit dürfen die Zeilen
gehen. Verglichen wird gegen den **quantisierten** Sollwert, nicht gegen die
rohe `DOUBLE`-Spalte: Die Prüfung belegt, dass Kodieren und Dekodieren
zueinander passen und kein Punkt verlorengeht, seine Stelle wechselt oder
seine Reihenfolge verliert — nicht eine Genauigkeit, die das Format nie
zugesagt hat.

Nachgemessen wird sie mit `php tools/spurprobe/probe.php`; der Lauf arbeitet in
einer Transaktion, die er am Ende zurückrollt, und ändert deshalb nichts.

#### Stufe 3: die Ausdünnung (ab Web 10.2.0, E-S2-05)

Sechs Monate nach Einsatzende wird der verlustfreie Blob durch einen
ausgedünnten ersetzt. **Das Original ist danach weg** — es gibt keine zweite
Quelle. Entsprechend viel Prüfung steht davor.

**Das Verfahren** ist Douglas-Peucker, dreidimensional, mit zwei getrennten
Toleranzen: 2 m waagerecht, 3 m senkrecht (`SPUR_TOL_WAAGERECHT_M` /
`SPUR_TOL_SENKRECHT_M`). Erhalten bleiben immer: der erste und der letzte
Punkt sowie **je Phasenzeitpunkt der zeitnächste** — ohne diesen Schutz ginge
die Höhenermittlung des Einsatzorts (`SITE_ELE_TOLERANCE_S` = ±300 s) leer aus.

**Die beiden Toleranzen sind EIN Lauf, nicht zwei.** Das Abstandsmaß je
Kandidatenpunkt lautet

```
s = max( waagerecht / 2 m , senkrecht / 3 m )      behalten, wenn s > 1
```

`s ≤ 1` gilt genau dann, wenn *beide* Toleranzen eingehalten sind — und `s`
liefert zugleich die eine Zahl, die Douglas-Peucker für die Wahl des
Teilungspunkts braucht.

Die naheliegende Alternative — zwei getrennte Läufe, Behaltelisten vereinigen —
ist **falsch**: Die Vereinigung erzeugt einen dritten Streckenzug, für den
keiner der beiden Läufe etwas zugesagt hat. Am Referenzbestand gemessen:
**8,62 m waagerecht und 4,16 m senkrecht** bei zugesagten 2 und 3. Sie behält
dabei sogar mehr Punkte, sieht also nach der sicheren Wahl aus.

Dass die zweite Toleranz nötig ist, ist ebenfalls gemessen: Rein
zweidimensional liegt der schlimmste verworfene Punkt **82,76 m** neben dem
Höhenprofil.

**Pflichtpunkte sind Abschnittsgrenzen, keine Nachträge.** Global ausdünnen und
die geschützten Punkte hinterher einfügen bricht die Zusage — ein nachträglich
eingefügter Punkt knickt den Weg zu sich hin. Gemessen: 46 von 181
Referenzspuren betroffen, **11 mit Zusageverletzung**. Abschnittsweise: 0.

**Fehlende Höhen** (das Bitfeld im Format erlaubt sie) laufen über eine
Ankerreihe (`spur_hoehenanker()`): Lücken werden über die *Zeit* zwischen den
nächsten gemessenen Nachbarn linear gefüllt, die Ränder konstant fortgesetzt.
Trägt die Spur gar keine Höhe, entfällt der Höhentest ganz.

Die naheliegende Regel „fehlt einem Sehnenende die Höhe, entfällt der Höhentest
für diesen Abschnitt" ist eine Falle: Ein einzelner höhenloser Punkt an einer
waagerechten Ecke wird zum Teilungspunkt und damit zum Sehnenende *beider*
Teilstücke — danach ist der Höhentest dort tot. Im Prüffall verschwindet so
eine 150-m-Spitze vollständig, und eine Prüfung, die solche Abschnitte
überspringt, meldet dafür 0,0 m Verlust.

#### Douglas-Peucker ist quadratisch — der Deckel und der Stapel

Der schlechteste Fall ist nicht konstruiert: Die Uhr nimmt einen Punkt auf,
sobald 15 m **oder** 10 s vergangen sind (`watch/source/Track.mc`). Ein längerer
Schwebeflug mit GPS-Rauschen über 2 m ergibt genau den Zickzack, in dem kein
Punkt wegfallen darf. Gemessen für **eine** Spur:

| Punkte | ohne Deckel |
|---|---|
| 2 000 | 0,198 s |
| 5 000 | 1,219 s |
| 10 000 | 4,340 s |
| 20 000 | 18,658 s |
| **50 000** | **114,50 s** |

Die Häppchenbudgets sind 3 / 20 / 300 s. Auf dem Token-Weg liefe das in
`max_execution_time`, und ein Zeitablauf ist **kein `Throwable`**: Der `catch`
im Job-Rahmen fängt ihn nicht, `laeuft_seit` bleibt stehen, der Job ist eine
Stunde gesperrt — und stirbt dann wieder. Dauerhafter, unsichtbarer Stillstand
mit `letzter_fehler = NULL`.

Zwei Vorkehrungen:

- **`SPUR_DP_ABSCHNITT_MAX` = 1000.** Zusätzlich zu den Pflichtpunkten wird
  alle 1000 Punkte eine Abschnittsgrenze gesetzt. Zulässig, weil zusätzliche
  Grenzen nur zusätzliche *behaltene* Punkte erzeugen — die Zusage wird nie
  schwächer. Derselbe Zickzack sinkt damit auf **2,40 s**. Am Normalfall kostet
  er nichts: eine glatte 50 000-Punkte-Spur braucht *mit* Deckel 0,031 s und
  behält 786 Punkte, *ohne* 0,161 s und 816. Am Referenzbestand greift er gar
  nicht (längster Abschnitt 804 Punkte).
- **Iterativ mit ausdrücklichem Stapel**, und immer die *größere* Hälfte auf den
  Stapel. Dann ist jedes fortgesetzte Teilstück höchstens halb so lang wie das
  vorige, und der Stapel hat nie mehr als ⌈log₂ n⌉ = 16 Einträge statt 50 000.
  Rekursiv wären es 38 MB VM-Stapel (797 Byte je Rahmen, gemessen) gegen ein
  Z3-Budget von 64 MB.

Beides fällt an einer Prüfung am Referenzbestand **nicht** auf: Dort ist die
größte erreichte Rekursionstiefe 23. `spur_ausduenn_dauer_s()` rechnet daraus
eine obere Schranke, mit der ein Häppchen *vorher* entscheiden kann, ob es eine
Spur noch schafft (vorhergesagt 2,29 s, gemessen 2,40 s).

#### Die Rundlaufprüfung der Stufe 3 ist eine andere

`spur_rundlauf_pruefen()` allein ist hier **wertlos**: Die Behalteliste stammt
aus `spur_dekodieren()` des Stufe-2-Blobs, ihre Werte liegen also schon auf der
Formatauflösung; `spur_quantisieren()` ist darauf ein Nulloperator, und der
Vergleich geht *immer* auf. Er wäre grün, auch wenn die Ausdünnung die halbe
Spur an der falschen Stelle wegwirft — und er ist die letzte Instanz vor dem
Ersetzen eines Blobs.

`spur_ausduennung_pruefen()` prüft deshalb fünf Dinge:

1. **Nichts erfunden** — jeder behaltene Punkt ist wertgleich mit einem Punkt
   der Eingabe an genau diesem Index.
2. **Reihenfolge und Zeit bleiben** — Indizes streng aufsteigend, Zeit nicht
   fallend.
3. **Die Ränder bleiben** — Index 0 und n−1.
4. **Die Zeitanker bleiben** — zu jedem Schutzzeitpunkt der Index, den die
   Verbraucher wählen würden (der *früheste* mit kleinstem |Δt|, mit `<`, wie
   `site_elevation_lib.php` und `api/mission.php` es tun).
5. **Die Genauigkeit ist eingehalten** — für *jeden* verworfenen Punkt gilt
   gegen den **endgültigen** Streckenzug, unabhängig nachgemessen und nicht aus
   der Buchführung der Rekursion übernommen, waagerecht ≤ 2 m und senkrecht
   ≤ 3 m.

Punkt 5 ist der Kern. Er kostet O(n) mit einem mitwandernden Segmentzeiger und
fängt jede der 11 Zusageverletzungen des „global plus einfügen"-Wegs.

#### Was die Ausdünnung wirklich spart

Weniger, als die Punktzahl vermuten lässt. Sie entfernt genau die
**vorhersagbaren** Punkte; die verbleibenden Differenzen sind größer und lassen
sich schlechter packen.

| Bestand | Punkte bleiben | Bytes bleiben |
|---|---|---|
| Referenzkonto (156 Spuren, 47 078 Punkte) | 40,7 % | **73,6 %** |
| Messstand (4973 Spuren, 1 628 340 Punkte) | 31,6 % | **57,4 %** |

Stufe 2 kostet gemessen 3,90 Byte je Punkt, Stufe 3 **2,24 Byte je
Originalpunkt** (7,10 je behaltenem). Wer den Erfolg an der Punktzahl misst,
misst das Falsche. Beide Stufen halten E-S2-24 mit Abstand: **1,60 MB je 1000
Einsätzen** gegen 3 MB Zielwert.

#### Was nach der Ausdünnung nicht mehr gilt

- **Eine geänderte Phasenzeit sagt über die Ortshöhe nichts mehr.** Die
  behaltenen Punkte wurden für die *damaligen* Phasenzeiten geschützt.
  `compute_site_elevation()` läuft bei jedem Speichern und schrieb bis Web
  10.1.0 bedingungslos, auch `NULL` — wer einen zwei Jahre alten Einsatz
  öffnet und eine Phase um zehn Minuten verschiebt, hätte die Höhe still
  verloren. Auf Stufe 3 wird ein vorhandener Wert deshalb **nicht mehr durch
  `NULL` ersetzt**; ein neu gefundener Wert wird sehr wohl geschrieben. Auf
  Stufe 1 und 2 bleibt es beim bisherigen Verhalten, denn dort trägt die Spur
  alle Punkte und ein leeres Ergebnis ist die Wahrheit.
- **Die angezeigte Punktzahl sinkt.** `spur_zahlen()` liefert
  `n_gespeichert`; Export, Papierkorb und Tageszuordnung zeigen danach die
  ausgedünnte Zahl. Das ist richtig — die Datei hat wirklich weniger Punkte —,
  gehört aber ins Handbuch, sonst liest es sich wie Datenverlust.
- **Reanimationszeitpunkte sind nicht geschützt.** E-S2-05 nennt nur
  Phasenzeitpunkte, und heute bindet nichts `resus_sessions` an die Spur. Wer
  die Hervorhebung später darauf ausweitet, findet für Alteinsätze keinen
  passenden Punkt mehr. Bewusste Grenze, keine Nachlässigkeit.

#### Zwei Grenzen, weil es zwei Fragen sind

`LIMIT_TRACKPUNKTE` galt bis Web 9.14.0 an zwei Stellen, die Verschiedenes
meinen. Seit Web 10.0.0 sind es zwei Konstanten (`validate_lib.php`):

| Konstante | gilt für | Wert | Verhalten |
|---|---|---|---|
| `LIMIT_TRACKPUNKTE_ANFRAGE` | die Punkte **einer Anfrage** (`ingest.php`) | 2000 | kappt und meldet |
| `LIMIT_TRACKPUNKTE_SPUR` | die Punkte **einer ganzen Spur** (`backup_lib.php`) | 50 000 | **lehnt die Spur ab** |

Die Uhr sendet in Stücken zu 500 (`UPLOAD_CHUNK_POINTS`), 2000 sind also
vierfache Reserve. Beim Zurückspielen war dieselbe Zahl dagegen ein
Datenverlust: Was die Uhr über viele Anfragen aufbauen darf, wurde bei 2000
gekappt — die Datei trug die ganze Spur, zurück kam ihr Anfang. Eine halbe
Spur sieht aus wie eine ganze; eine abgelehnte sieht man.

---

### 4.97a Hintergrundjobs: drei Auslöser, ein Katalog (ab Web 10.1.0, S2)

Diese Anwendung hat bewusst **keinen Cron als Voraussetzung**: Sie soll auf
einfachem Webspace laufen, und dort gibt es oft keinen. Der einzige Zeitgeber
war bis Web 10.0.0 `run_cleanup_if_due()` — huckepack auf der Anfrage der
ersten Nutzerin des Tages. Das trug, solange die Arbeit klein war.

Mit S2 bleibt sie das nicht. Schon die damalige Waisenprüfung war ein
Anti-Join über die ganze Spurtabelle und kostete gemessen **4,07 s bei
9,46 Mio. Zeilen** — in genau der Anfrage, die jemand gerade gestellt hatte.
Bei der Zielmenge Z2 (190 Mio. Zeilen) wären es Minuten.

Deshalb ein Rahmen: `server/jobs_lib.php` (Katalog und Ausführung),
`server/jobs.php` (Einstieg), Tabelle `jobs` (Zustand).

#### Die drei Auslöser (E-S2-17)

**Einer genügt.** Eingerichtet werden muss keiner — dann läuft die Arbeit
weiter huckepack mit. Die Wartungsseite (`update.php`) zeigt alle drei mit
fertigem Befehl bzw. fertiger Adresse.

| Auslöser | Aufruf | Zeitbudget je Lauf | gedacht für |
|---|---|---|---|
| `cli` | `* * * * * php …/server/jobs.php` | `JOB_BUDGET_CLI` = 300 s | der **empfohlene** Regelfall |
| `token` | `https://…/jobs.php?token=…` | `JOB_BUDGET_TOKEN` = 20 s | Hoster ohne CLI-Cron, aber mit „Cronjob per URL" |
| `anfrage` | `auth_guard.php` → `run_cleanup_if_due()` | `JOB_BUDGET_ANFRAGE` = 3 s | Rückfall, immer eingeschaltet |

Die Budgets sind kein Geschmack: 300 s, weil auf der Kommandozeile niemand
wartet und meist keine Laufzeitgrenze gilt; 20 s, weil das unter der
`max_execution_time` liegt, die geteilter Webspace üblicherweise setzt
(dieselbe Überlegung wie bei „Alle sichern"); 3 s, weil eine Seite, die
zwanzig Sekunden braucht, weil sie nebenbei aufräumt, kaputt ist — auch wenn
kein Zeitlimit greift.

Am Huckepack-Weg gilt zusätzlich ein **Mindestabstand** von
`JOB_ANFRAGE_PAUSE_S` = 5 Minuten je Job. Ohne ihn liefe ein nicht-täglicher
Job bei *jeder* angemeldeten Anfrage, und jede Seite trüge bis zu drei
Sekunden Wartung mit. Für `cli` und `token` gilt er nicht: Dort bestimmt der
Zeitplan die Häufigkeit, und wer jede Minute aufruft, will das auch.

`jobs.php` lädt **ausdrücklich nicht** `auth_guard.php`. Der würde den
Huckepack-Weg auslösen und damit den Job aus dem Job heraus starten. Der Abruf
über die Adresse legitimiert sich mit dem Token, nicht mit einer Sitzung — ein
Zeitplandienst hat keine.

#### Das Token

32 Byte Zufall, hex, in `app_state` unter `jobs_token`; erzeugt beim ersten
Lesen. **Nicht** in `config.php`: Die Anwendung schreibt diese Datei genau
einmal, bei der Einrichtung; sie danach anzufassen hieße, auf jedem Webspace
Schreibrecht auf die eigene Konfiguration zu brauchen — und
Bestandsinstallationen hätten kein Token, ohne dass jemand sähe, warum.

Wer das Token hat, kann die Wartung anstoßen; mehr nicht. Er kann damit weder
Daten lesen noch schreiben. `jobs.php` prüft es mit `hash_equals`, hinter dem
Ratenschutz-Topf `pair` (zehn Fehlversuche in zehn Minuten), und gleicht die
Antwortzeit mit `rate_gleiche_dauer()` an — „Token gibt es gar nicht" darf
nicht schneller kommen als „Token ist falsch". Gemessen: **403 / 403 / 200**
für kein, falsches und richtiges Token, die beiden 403 in je **0,351 s**.

**Der Ratenschutz zählt je IP, und das hat eine Folge, die man kennen sollte:**
Nach zehn Fehlversuchen von derselben Adresse antwortet der Endpunkt zehn
Minuten lang `429` — auch auf den *richtigen* Aufruf. Wer einen
Zeitplan-Eintrag mit falschem Token stehen hat, sperrt damit seinen eigenen
Zeitplan aus; nach dem Berichtigen dauert es zehn Minuten, bis er wieder
greift. Das ist gewollt: Die Alternative wäre ein Endpunkt, an dem sich ein
Token ungebremst durchprobieren lässt. Auf der Wartungsseite gibt es
„Neues Token erzeugen"; das alte wird damit ungültig, und ein bestehender
Zeitplan-Eintrag läuft danach ins Leere. Der Hinweis steht am Knopf.

#### Häppchen, Zustand, Sperre

Jeder Job bekommt `$zeitLinks()` und hört auf, wenn das Budget zu Ende ist.
Wo er stehengeblieben ist, merkt er sich als JSON in `jobs.zustand`. Der
nächste Lauf — gleich welcher Auslöser — macht dort weiter.

Die **Sperre** gegen zwei gleichzeitige Läufe ist ein bedingtes `UPDATE`, nicht
`SELECT`-dann-`UPDATE`: Letzteres hätte ein Zeitfenster, in dem zwei Anfragen
beide zu dem Schluss kommen, sie dürften. `laeuft_seit` ist bewusst ein
**Zeitstempel und kein Flag** — ein Lauf, der mitten im Häppchen abstürzt
(Speichergrenze, Zeitablauf, abgebrochene Verbindung), ließe ein Flag für immer
stehen, und der Job liefe nie wieder, stillschweigend. Nach
`JOB_SPERRE_VERFALL_S` = 1 h gilt eine Sperre als verwaist.

#### Der Katalog

| Job | täglich? | was er tut |
|---|---|---|
| `aufraeumen` | ja, höchstens 1×/Kalendertag | Kopplungscodes, Sperrliste gelöschter Kennungen, Ratenschutz-Zähler, Papierkorb, Passwort-Tokens, Erinnerung an die Administration |
| `verdichtung` | nein | Stufe 1 → 2: abgeschlossene Spuren in den verlustfreien Blob (seit Web 10.2.0) |
| `ausduennen` | nein | Stufe 2 → 3: sechs Monate nach Einsatzende ausdünnen (seit Web 10.2.0) |
| `waisen` | nein, läuft solange Rückstand da ist | Spurpunkte und Blobs ohne Eigentümer — **bereichsweise** über den Primärschlüssel |

Jeder Aufräumschritt hat weiterhin seinen eigenen Fehlerblock: Einer, der
scheitert, hält die anderen nicht auf (das war schon seit Web 4.5.1 so und
bleibt). Der Unterschied ist, dass das Ergebnis jetzt in `jobs` landet und
nicht nur im Fehlerprotokoll des Webspace.

**Die Reihenfolge ist Absicht.** `jobs_lauf()` arbeitet den Katalog der Reihe
nach ab und überspringt, was ins Restbudget nicht mehr passt. `waisen` ist ein
Sicherheitsnetz und kein Hauptweg — die eigentliche Arbeit gehört deshalb nach
vorn, sonst bekäme sie am Huckepack-Weg (3 s) nur noch den Rest.

#### Verdichtung und Ausdünnung als Jobs (ab Web 10.2.0)

**Der Einstieg der Verdichtung kommt von der PUNKTSEITE**, wie beim
Waisenjob — und das ist keine Bequemlichkeit. Die Menge „`final = 1` und
Ankunft älter als 14 Tage" enthält *jeden je abgeschlossenen* Einsatz, auch
alle längst verdichteten; sie wächst monoton, und ein Index darauf fände bei Z2
Millionen Zeilen, von denen 99,9 % nichts mehr zu tun haben. Der Punkteinstieg
dagegen **räumt seinen eigenen Vorrat ab**: Eine verdichtete Spur hat keine
Zeilen mehr und erscheint nie wieder. Übrig bleibt nur der Rückstand. Der nötige
Index existiert bereits — der Primärschlüssel.

**Blockgröße 200, und gelesen wird Spur für Spur.** `JOB_WAISEN_BLOCK` (2000)
ist hier falsch: Der Waisenjob materialisiert nie Punkte, die Verdichtung muss
jede Kandidatenspur wirklich lesen. Gemessen kostet eine Punktliste in PHP 237
bis 294 Byte je Punkt; 200 Spuren des Messstands (524 Punkte im Mittel)
gebündelt zu halten sprengt ein `memory_limit` von 64 MB — nachgemessen mit
`Allowed memory size exhausted`. Spur für Spur gelesen ist die Spitze die
**einer** Spur: gemessen 4,0 MB.

**Ablauf je Spur.** Erst der Umriss (`spur_umriss()`, eine Abfrage für den
ganzen Block: Zeilenzahl, kleinste und größte Nummer, größter Zeitstempel,
Blobstufe). Daraus wird entschieden, **ohne einen Punkt gelesen zu haben**:
Eigentümer weg → der Waisenjob räumt · im Papierkorb → nicht anfassen · Stufe 3
→ nicht anfassen und zählen · über `LIMIT_TRACKPUNKTE_SPUR` → ablehnen und
benennen · `letzter_punkt_am` fehlt → nachtragen · Karenz nicht abgelaufen →
liegen lassen · Lücke → benennen. Erst wer alles passiert, kostet einen
Punktzugriff.

Dann: lesen, kodieren, Rundlauf prüfen — **alles außerhalb der Transaktion**,
denn `spur_rundlauf_pruefen()` braucht kein PDO. Schlägt sie an, geschieht gar
nichts. Die Transaktion selbst ist zwei Anweisungen lang, **erst Blob, dann
Zeilen**: Der Zwischenzustand ist im Code vorgesehen (`spur_lesen_viele()`
übergeht Zeilen unterhalb `n_original` als Rest eines abgebrochenen Laufs),
umgekehrt wäre ein Abbruch Datenverlust.

**Die Ausdünnung geht über den Primärschlüssel von `track_blobs`**, nicht über
den Index `stufe_alter (stufe, geaendert_am)`. Der trägt das Änderungsdatum des
*Blobs*, nicht das Einsatzende, und ist als Näherung in beide Richtungen
falsch: Das Einspielen eines Backups schreibt einen frischen `geaendert_am`
auf zwei Jahre alte Punkte, und `spur_zeit_verschieben()` schreibt ihn bei
jeder Umdatierung neu. Bezugsgröße ist `COALESCE(ended_at, started_at)` —
`started_at` ist in beiden Tabellen `NOT NULL`, und bei sechs Monaten Frist ist
der Unterschied zwischen Beginn und Ende Rauschen (und geht in die sichere
Richtung).

**Nachzügler gehen vor.** Eine Spur mit Stufe-1-Zeilen wird *nicht* ausgedünnt;
sie gehört der Verdichtung, die Blob und Nachzügler zu einem neuen
verlustfreien Blob zusammenführt. Sonst nummerierte der Blob 0 … n_gespeichert−1
und die Nachzügler begännen bei `n_original` — eine Nummernlücke, die der
Rückweg des Backups nicht verträgt.

**Verkettet wird nicht.** Konzept 3.1.4 sah vor, dass die Ausdünnung im selben
Häppchen hinterherläuft, wenn die Frist schon abgelaufen ist. Dagegen sprach:
zwei unwiderrufliche Schritte mit zwei verschiedenen Rundlaufbegriffen in einem
Budgetfenster, deren Scheitern sich hinterher nicht mehr zuordnen lässt.
Getrennt kostet es einen Jobzyklus. Entschieden am 31.08.2026; das Konzept ist
an dieser Stelle fortgeschrieben.

Gemessen am Messstand (5345 Einsätze, 3,3 Mio. Punkte, `memory_limit=64M`):
Verdichtung **9395 Spuren in 44,3 s**, 2 936 497 Zeilen entfernt, Spitze
4,0 MB · Ausdünnung **4973 Spuren in 15,2 s**, Spitze 4,0 MB.

#### Die Jobs anhalten (ab Web 10.2.0)

`php jobs.php --pause <Sekunden>` (0 hebt auf). Die Pause gilt für **alle drei
Auslöser** — sonst räumte ein Cron weg, was gerade gemessen wird — und läuft
von selbst ab (`JOB_PAUSE_MAX_S` = 2 h); eine Pause ohne Ende wäre eine, die
jemand vergisst. Die Wartungsseite zeigt sie als eigene Plakette an, damit eine
laufende Pause nicht wie ein arbeitender Job aussieht.

**Warum es sie gibt.** Seit die Jobs Zeilen löschen und Blobs ersetzen, ändern
sie den Bestand, während eine Messung darüber läuft. Der Kreislauf spielt ein
Backup in ein frisches Konto und exportiert es sofort wieder; die
wiederhergestellten Einsätze sind alt, der Verdichtungsjob hält sie für reif,
und was älter als sechs Monate ist, wird ausgedünnt. Der Vergleich misst dann
nicht mehr „kommt zurück, was hineinging", sondern „hat der Job dazwischen
zugeschlagen". Beim ersten Lauf nach AP3 ging es gut, aber nur **zufällig** —
nachgemessen verdichtete ein Lauf ohne Pause 125 Spuren des Umlaufkontos.

Im Betrieb ist sie ebenfalls nützlich: Wer ein großes Backup einspielt, will
die Jobs so lange still haben.

#### Die Waisensuche läuft bereichsweise (E-S2-18)

Statt eines Anti-Joins über alles wandert eine Marke über den Primärschlüssel:
je Häppchen höchstens `JOB_WAISEN_BLOCK` = 2000 Eigentümerkennungen, aus
`track_points` **und** `track_blobs` (eine Waise kann als Zeile, als Blob oder
als beides dastehen). Am Tabellenende fängt die Marke wieder von vorn an — ein
Netz, das einmal durchläuft und dann liegen bleibt, ist keines.

**Ehrlich gemessen ist das bei 3,31 Mio. Zeilen nicht schneller** (je fünf
Läufe, `memory_limit=64M`, Speicherspitze 2,0 MB):

| | Dauer |
|---|---|
| Anti-Join über alles (alt, nur lesend) | **0,78–0,90 s** |
| bereichsweise, ein vollständiger Durchlauf (neu) | **0,85–1,05 s** |

Der Gewinn ist ein anderer und liegt woanders: Der neue Weg ist **begrenzt**
(Zeitbudget), **fortsetzbar** (Marke in `jobs.zustand`) und liegt **nicht mehr
auf dem Weg einer Anfrage**. Genau das ist bei Z2 der Unterschied zwischen
„läuft eben nebenher" und „die Seite hängt minutenlang, und niemand weiß
warum".

Seit AP1 räumen die Löschwege ohnehin selbst ab (`spur_loeschen`, F-S2-B).
Dieser Job ist das Sicherheitsnetz, nicht der Hauptweg.

#### Der angezeigte Rückstand ist der Fortschritt, nicht die Waisenzahl

Die naheliegende Zahl wäre „Eigentümer ohne Zeile in `missions`" — und die
kostet genau den Vollscan, den dieser Job abschafft. Für eine Anzeige ist das
der falsche Preis. Angezeigt wird deshalb, wie viele Kennungen die Marke noch
vor sich hat; beide Abfragen dafür laufen auf dem Primärschlüssel.

Zwei Fehler steckten hier beim ersten Anlauf, beide beim Messen aufgefallen:

- Der Rückstand las den Zustand **aus der Tabelle**, während der frische noch
  nicht geschrieben war — der Job meldete direkt nach einem vollständigen
  Durchlauf „Rückstand 33093", also die ganze Tabelle als ausstehend. Die
  Rückstandsfunktion bekommt den Zustand jetzt übergeben.
- Eine Marke von 0 war nicht von „noch nie gelaufen" zu unterscheiden. Der
  Zustand hält deshalb zusätzlich `durch`, ob der Durchlauf zu Ende kam.

#### Sichtbarkeit

`update.php` zeigt je Job letzten Lauf, Auslöser, Rückstand und letzten
Fehler. `letzter_fehler` steht in der Tabelle und nicht nur im
Fehlerprotokoll: Auf geteiltem Hosting kommt an dieses Protokoll nicht jede
Betreiberin heran, und ein dauerhaft scheiternder Job soll auffallen. Die
Wartung bleibt **gegenüber der Anfrage still** — sie darf keine Seite
kaputtmachen.

Die Marken `last_cleanup` und `last_cleanup_ok` in `app_state` sind damit
entfallen; ihre Auskunft steht vollständiger in `jobs`.

---

### 4.97b GPX-Abruf je Spur und je Auswahl (ab Web 10.3.0, S2/AP4, E-S2-09)

Eine Spur lässt sich einzeln herunterladen — je Einsatz aus dessen
Aktionsmenü, und je Einsatz **und Ruhesegment** über `tag_spuren.php`; auf
derselben Seite lassen sich mehrere ankreuzen und als **eine** Datei laden.
Der Bauplatz ist `server/gpx_lib.php`; er ist die **einzige** Stelle, die GPX
schreibt.

| Adresse | liefert |
|---|---|
| `gpx.php?art=mission&id=42` | eine Spur |
| `gpx.php?art=rest&id=17` | eine Ruhespur |
| `gpx.php?tag=7&auswahl[]=mission-42&auswahl[]=rest-17` | beide in einer Datei |

Der Auswahlweg nimmt `auswahl[]` (so schickt es das Formular) genauso wie
`auswahl=mission-42,rest-17` (so tippt man es von Hand). Beide gehen durch
dieselbe Prüfung, dieselbe Datentrennung und dieselbe Bau-Funktion
(`gpx_bauen_viele()`) — sonst wäre die Auswahl ein zweiter, schwächerer Weg an
denselben Bestand.

#### Warum serverseitig — und warum das die erste ausgelieferte Datei ist

Bis Web 10.2.0 entstand **jede** Datei, die auf der Platte einer Nutzerin
landet, im Browser aus einem Blob. Das hat einen Grund und keinen Zufall: Ihr
Inhalt ist Ende-zu-Ende verschlüsselt, der Server **kann** ihn nicht
zusammensetzen.

Für eine Spur gilt das nicht. Spurpunkte liegen im Klartext (Backlog Nr. 43),
und die Stufe, die E-S2-09 sichtbar verlangt, kennt ohnehin nur der Server
(`spur_stand()`). Der Browser hätte beides nicht: `api/mission.php` liefert die
Spur als bloße Paare `[lat, lon]` — ohne Höhe, ohne Zeit, ohne Stufe. Ein
browsergebautes GPX bräuchte also einen neuen, breiteren Abrufweg, nur um
danach zusammenzusetzen, was auf dem Server schon beieinander liegt.

Den Ausschlag gibt ein Sicherheitsargument: Der **Dateiname** landet im
Downloadordner, in einem Backup, vielleicht in einer Mail. Serverseitig gebaut
**kann** er keine geschützte Angabe tragen — der Server kann Diagnose, Alter
und Einsatzort nicht lesen. Browserseitig gebaut könnte er es.

`gpx.php` prüft die **Datentrennung selbst**: `spur_lib.php` prüft kein
Eigentum, es nimmt `owner_type` und `owner_id` und liest, was da ist. Erst
gegen `user_id` und `deleted_at` filtern, dann lesen — dasselbe Muster wie in
`api/export_data.php`. Und „gehört nicht mir" antwortet **404 wie „gibt es
nicht"**: Ein eigener Code verriete, dass es die Kennung anderswo gibt.

#### Warum der Abruf nicht unter `api/` liegt

Er lag dort zuerst, und das war falsch. `ist_api_aufruf()` (`auth_guard.php`)
entscheidet **allein am Pfad**: Enthält er `/api/`, gilt die Anfrage als
`fetch()` eines Skripts und bekommt bei abgelaufener Sitzung JSON 401 statt
der Anmeldeseite. Diese Annahme stimmte, solange nichts in der Oberfläche nach
`api/` **verlinkte** — der GPX-Abruf ist der erste `<a href>`, den eine
Nutzerin selbst anklickt. Nach einer Mittagspause hätte sie
`{"error":"session_ende"}` im Browserfenster gesehen.

#### Drei Schranken, die es NICHT gibt — und warum

- **Keine A9-Schranke wie im Export.** `api/export_data.php` verweigert
  Spurpunkte, solange der Haken „personenbezogene Angaben" fehlt: Ein Export
  *ohne* diese Angaben ist eine Datei zum Weitergeben, und eine Spur endet am
  Einsatzort. Hier gibt es diese anonyme Fassung gar nicht — es gibt nur den
  einen Abruf, und der *ist* die personenbezogene Fassung. Es gäbe keinen
  Haken zu umgehen.
- **Keine Sperre auf den Inhaltsschlüssel.** Die Einsatzansicht zeichnet
  dieselbe Spur bereits auf ihre Karte, ohne dass jemand entsperrt haben muss
  — die Punkte sind Klartext. Eine Sperre hier wäre Theater: Sie verweigerte
  die Datei und zeigte den Weg daneben weiter an. Dass die Spur überhaupt
  unverschlüsselt liegt, ist ein bekannter offener Punkt (**Backlog Nr. 43**)
  und gehört dorthin, nicht in eine halbe Maßnahme an dieser Stelle.
- **Keine Mengengrenze aus Rechtsgründen** — es sind die eigenen Spuren. Die
  Grenze von `GPX_AUSWAHL_MAX` = 100 Spuren je Auswahl steht aus einem anderen
  Grund da: Die Datei entsteht vollständig im Arbeitsspeicher, weil ihre Länge
  in die Kopfzeile gehört. Gemessen mit der größten Spur des Referenzbestands
  (1063 Punkte) kosten hundert Spuren 9,7 MB Datei bei 23,4 MB Spitze — im
  Budget von 64 MB (Z3). Dazu ein **Ratenschutz** im Topf `pair`, und zwar nur
  auf Fehlgriffe: Ein gelungener Abruf geht nicht aufs Kontingent, sonst träfe
  die Bremse die Spurenseite eines Tages mit zwölf Einträgen. Gezählt wird,
  was auf ein Abtasten fremder Kennungen hindeutet.

**Was die Datei bedeutet, sagt die Oberfläche.** Der Eintrag in der
Einsatzansicht fragt vor dem Herunterladen zurück — wie der große Export es
tut —, und über der Liste in `tag_spuren.php` steht derselbe Satz. Eine
Ruhespur ist dabei ausdrücklich mitgemeint: Sie zeigt den Aufenthalt der
Besatzung zwischen den Einsätzen.

#### Die Reihenfolge im GPX ist nicht frei

GPX 1.1 beschreibt die Kindelemente als `xsd:sequence`, nicht als
`xsd:choice`. Zwei Stellen kommen darauf an:

| Typ | Folge | heißt |
|---|---|---|
| `metadataType` | name, **desc**, author, copyright, link, **time**, … | `<desc>` steht **vor** `<time>` |
| `trkType` | **name**, cmt, **desc**, src, link, number, type, ext, **trkseg** | `<desc>` steht zwischen `<name>` und `<trkseg>` |

Wer `<desc>` hinten anhängt, schreibt eine Datei, die wohlgeformt ist, die
manche Programme klaglos lesen — und die gegen das Schema durchfällt. Kein
XML-Parser meldet das.

#### Die Kennzeichnung steht an drei Stellen

E-S2-09 verlangt, dass sichtbar ist, welche Fassung der Spur die Datei trägt:

1. **In der Datei**, als `<desc>` in `<metadata>` und in `<trk>` — mit Zahl:
   „ausgedünnt — 113 von ursprünglich 443 Punkten (Douglas-Peucker, 2 m
   waagerecht / 3 m senkrecht)".
2. **Im Dateinamen** (`einsatz_000001_2026-01-17_0605_ausgeduennt.gpx`). Das
   ist die einzige Kennzeichnung, die das Verschieben in einen anderen Ordner
   überlebt.
3. **Auf der Seite**, vor dem Herunterladen. Eine Auszeichnung, die nur in der
   Datei steht, sieht erst, wer sie schon hat.

Der Dateiname wird auf `[A-Za-z0-9._-]` beschränkt (`gpx_dateiname()`): Er geht
durch eine HTTP-Kopfzeile, ein Dateisystem und womöglich ein Archiv; ein
Anführungszeichen oder ein Zeilenumbruch darin wäre eine Einladung zur
Kopfzeilen-Einschleusung.

#### Die Spurenseite des Diensttages

**Ruhesegmente hatten in der Oberfläche keine Identität.** In der Tagesansicht
waren sie eine schwarze Linie, ohne Zeile, ohne Popup; `api/day.php` lieferte
nicht einmal ihre Kennung. *(Seit Web 12.6.0 gilt das nur noch für die Karte
selbst: Die Tagesansicht führt sie als eigene Karte „Ruhesegmente" — dort wird
geschnitten, siehe 4.97e.)* Ein Knopf je Ruhesegment hätte nirgendwo hingekonnt
— die Abnahme verlangt den Abruf aber „je Einsatz **und** je Ruhesegment".

`tag_spuren.php` gibt beiden dieselbe Identität: die Karte des Tages, darunter
jede Spur als eigene Zeile — nummeriert wie in der Tagesansicht, mit Stufe,
Punktzahl und Abruf. Zeigen hebt die zugehörige Linie hervor, ein Klick zoomt
auf sie.

**Die Liste steht chronologisch, nicht nach Art gruppiert.** Der erste Entwurf
listete erst alle Einsätze und dann alle Ruhezeiten — die Reihenfolge, in der
die beiden Abfragen im Code stehen. So liest sich aber kein Diensttag: Er
verläuft in *einer* Folge, Ruhezeit, Einsatz, Ruhezeit, Einsatz. Zwei Gruppen
zwingen dazu, zwischen ihnen hin und her zu rechnen, um zu sehen, was worauf
folgte. Die laufende Nummer der Einsätze („Einsatz 3") und die Farben auf der
Karte zählen weiter nur die Einsätze durch und bleiben davon unberührt.

**Serverseitig gerendert**, und das ist Absicht: Die Liste besteht aus dem
vorhandenen Baustein `ui_zeile()`. Sie im Browser aus Zeichenketten
nachzubauen hieße, dasselbe Markup ein zweites Mal zu pflegen. An den Browser
geht nur, was die Karte braucht: die Punktfolgen, und von denen nur der Ort —
weder Höhe noch Zeit.

Ohne Spur steht der Eintrag trotzdem in der Liste, aber ohne Abruf: Wer einen
Einsatz sucht und ihn hier nicht fände, hielte die Liste für unvollständig.

#### Mehrere Spuren in einer Datei

Jede Zeile trägt ein Auswahlkästchen, die Sammelleiste darunter lädt die
angekreuzten als eine Datei. **Kein neuer Baustein:** das Kästchen sitzt in
`ui_zeile(['vorn' => …])`, die Leiste ist `ui_speichern_leiste()` — dieselben
zwei Bausteine wie die Sammelaktion der NutzerInnen-Liste (P3/O9b). Ein
Eintrag ohne Spur bekommt ein abgeschaltetes Kästchen statt gar keines: Ein
fehlendes ließe die Zeile um seine Breite nach links rutschen.

**Mehrere `<trk>`, kein zusammengeklebtes `<trkseg>`.** GPX 1.1 erlaubt
beliebig viele `<trk>` in einem Dokument. Zwei Spuren in *ein* `<trkseg>`
geschrieben ergäbe eine Datei, die jedes Kartenprogramm klaglos öffnet und in
der es eine gerade Linie vom Ende der einen zum Anfang der nächsten zieht —
einen Weg, den niemand gefahren ist. Auch mehrere `<trkseg>` in *einem* `<trk>`
wären falsch: Die meinen Abschnitte **einer** Aufzeichnung mit einer Lücke
dazwischen, nicht zwei verschiedene Fahrten.

| Frage | Antwort |
|---|---|
| Reihenfolge | chronologisch über beide Arten hinweg — dieselbe Folge wie die Liste auf der Seite, aus der ausgewählt wurde. Sortierschlüssel dort wie hier: Beginn, Art, Kennung |
| Name je Spur | derselbe wie beim Einzelabruf (`Einsatz 42 — 10.05.2026 07:09`), damit man dieselbe Spur in beiden Dateien wiederfindet |
| Kennzeichnung | jede Spur nennt ihre Stufe an ihrem `<trk>`; der Kopf sagt, was die Datei als Ganzes ist („3 Spuren — 436 Punkte insgesamt · teils ausgedünnt") |
| Dateiname | `diensttag_2026-05-10_3-spuren_gemischt.gpx` — Datum, Anzahl und Stufe (`original`, `ausgeduennt` oder `gemischt`) |
| Speicher | `gpx_bauen_viele()` nimmt einen **Generator**, keine Liste: Eine dekodierte Spur kostet rund 4 MB, hundert gleichzeitig sprengten das Budget. Deshalb entstehen die `<trk>` zuerst und der Kopf danach — die Gesamtzahl kennt man erst am Ende, das `<metadata>` steht aber vorn |

**Streng bei der Form, nachsichtig beim Bestand.** Was nicht genau
`mission-<Zahl>` oder `rest-<Zahl>` ist, kommt nicht von dieser Seite: 400.
Eine wohlgeformte Kennung, die zu diesem Tag und diesem Konto nicht gehört,
fällt beim Lesen heraus, ohne dass die ganze Datei scheitert — sie kann aus
einem Tab stammen, der seit einer Löschung offen steht; wie viele Spuren
wirklich drin sind, sagen Dateiname und `<desc>`. Ausgeforscht wird dabei
nichts: Die Abfrage filtert auf `user_id` **und** `day_id`, eine fremde
Kennung liefert also nie einen Treffer, gleich ob es sie gibt. Bleibt gar
nichts übrig, ist es doch ein Fehlgriff: 404, und er zählt.

**Ohne JavaScript** bleibt der Einzelabruf. Die Kästchen stehen in einem
gewöhnlichen GET-Formular und würden auch ohne Skript absenden, aber die
Sammelleiste erscheint erst, wenn etwas ausgewählt ist — und das entscheidet
das Skript.

#### Was der Abruf nicht tut

- **Kein leeres GPX.** Eine Datei mit null Punkten sieht aus wie eine Spur, die
  es gibt — und in einem Kartenprogramm wie ein Fehler des Programms. Der Abruf
  antwortet 404.
- **Keine anonyme Fassung.** Der große Export bindet GPX-Spuren an die
  personenbezogenen Angaben, weil eine Spur am Einsatzort endet. Hier gibt es
  diese Wahl nicht; stattdessen steht der Satz über der Liste, an der Stelle,
  an der jemand herunterlädt.

---

### 4.97c Backup-Ziele: das Backup verlässt das Haus (ab Web 12.1.0, S2/AP7, E-S2-22)

Bis Web 12.0.0 entstanden die Admin-Backups unter `server/sicherungen/`
und blieben dort. Das ist die Rückfallebene für einen gelöschten Einsatz —
aber nicht für den Fall, für den man Backups macht: dass dieser Server weg
ist. Ab Web 12.1.0 gehen sie auf eine **Gegenstelle**.

#### Der Name

**Backup-Ziel**, nicht Transportziel. `transport_dests` gibt es seit Web 4;
das sind die Zielkliniken einer Patientin, gepflegt unter Stammdaten. Zwei
Dinge unter einem Wort, zwei Klicks voneinander entfernt — das lässt sich in
einer Fehlermeldung nicht mehr auflösen (Konzept-S2, F-S2-G).

#### Eine Schnittstelle, drei Adapter

`server/sicherungsziel_lib.php` beschreibt mit `Zielweg`, was ein Ziel können
muss: `verbinden`, `trennen`, `ordner`, `senden`, `holen`, `liste`,
`loeschen`, `fingerabdruck`. Alle Pfade sind **relativ zum Grundpfad des
Ziels** — kein Adapter nimmt einen absoluten Pfad, sonst wäre der Grundpfad
eine Empfehlung und keine Grenze.

| Adapter | Protokoll | Grundlage |
|---|---|---|
| `ZielFtp` | FTP und FTPS | PHP-Erweiterung `ftp` (`ftp_ssl_connect`) |
| `ZielSftp` | SFTP | phpseclib 3 (`server/vendor/`, docs/Lizenzen.md 3a) |

Ein Adapter für FTP **und** FTPS, weil sich genau eine Zeile unterscheidet.
Das Komplettbackup aus AP8 benutzt dieselbe Schnittstelle und weiss vom
Protokoll nichts; ein vierter Adapter (WebDAV, Backlog) soll sie nicht
anfassen.

#### Was die drei taugen

| | verschlüsselt | erkennt den Server wieder |
|---|---|---|
| **SFTP** | ja | **ja** — Fingerabdruck des Hostschlüssels |
| **FTPS** | ja | nein |
| **FTP** | **nein** | nein |

Der mittlere Fall wird leicht überschätzt: **`ext/ftp` prüft das Zertifikat
nicht.** Nachgemessen in `tools/versandprobe/` gegen eine Gegenstelle mit
selbst ausgestelltem Zertifikat ohne Vertrauenskette — die Verbindung kommt
zustande. Schutz gegen Mitlesen ja, Schutz gegen einen untergeschobenen Server
nein.

Bei SFTP wird der Fingerabdruck beim ersten `Verbindung prüfen` übernommen und
danach bei jeder Verbindung verglichen. Passt er nicht, bricht es ab — **vor**
der Anmeldung. Belegt wird das nicht an der Fehlermeldung, sondern an der
Gegenstelle: Sie schreibt jeden Anmeldeversuch mit, und ihr Protokoll bleibt
unverändert (3 Zeilen vorher, 3 nachher). Wer sich bei einem untergeschobenen
Server anmeldet, hat sein Passwort schon abgegeben, auch wenn er danach
abbricht.

Wechselt die Gegenstelle ihren Schlüssel, ist der gespeicherte Abdruck falsch
— und ein falscher Abdruck blockiert jede Verbindung und sieht aus wie ein
Angriff. Dafür gibt es „Hostschlüssel vergessen"; ausserdem wirft die Seite
ihn von selbst weg, wenn Rechnername oder Port geändert werden.

#### Der Serverschlüssel (E-S2-21)

`server/serverkrypto_lib.php`. 32 Byte Zufall in **`config.php`**, nicht in der
Datenbank:

```
edsk1:base64( nonce(12) ‖ prüfsumme(16) ‖ chiffre )     AES-256-GCM
Zusatzdaten: 'edsk1|sicherungsziel:<id>:<feld>'
```

Der Zweck in den Zusatzdaten bindet die Chiffre an **dieses** Ziel und
**dieses** Feld: Ein versiegeltes Passwort von Ziel 3 lässt sich nicht als
Passwort von Ziel 7 einsetzen, obwohl beide denselben Schlüssel benutzen.

Warum `config.php` und nicht die Datenbank: Der Zweck ist der Fall „jemand hat
die Datenbank". Für das Komplettbackup (AP8) wird es zwingend — dessen Dump
enthält jede Tabelle.

`sk_oeffnen()` gibt bei Misserfolg **`null`** zurück und unterscheidet nicht,
warum. Jeder Aufrufer muss das sagen; ein stillschweigend leeres Passwort wäre
die schlechteste Antwort, denn der Versand liefe dann in ein „Zugang
verweigert", und niemand käme auf die Ursache.

Neue Installationen bekommen den Schlüssel vom Installer. Bestehende tragen
ihn auf der Seite „Backup-Ziele" nach — ein Knopf, wenn `config.php`
beschreibbar ist, sonst eine Zeile von Hand. Der Knopf **ergänzt und ersetzt
nie** (ein Überschreiben machte jedes versiegelte Feld unlesbar), schreibt in
eine Nebendatei mit Endung `.php` — eine `config.php.tmp` läge im
Wurzelverzeichnis des Webservers als lesbarer Text mit dem Datenbankpasswort
—, führt sie zur Gegenprobe aus und vergleicht sie mit der geltenden Fassung,
schiebt sie erst dann an ihren Platz und verwirft danach den
OPcache-Eintrag. Ohne diesen letzten Schritt zeigt die nächste Anfrage wieder
„Serverschlüssel fehlt": OPcache prüft den Zeitstempel sekundengenau.

#### Der Versand

Ein Joblauf (`versand`, Katalog in `jobs_lib.php`) und ein Knopf „Jetzt
versenden". Beide gehen durch `sz_versand_schub()`; der Job fragt zusätzlich
den Schalter `app_state.versand_auto`, der Knopf nicht — dort hat gerade
jemand geklickt, und das ist die Zustimmung.

**Was „neu" ist, wird am Ziel abgelesen** — Verzeichnisliste, Name **und
Grösse** — und nicht in einer Merkliste geführt. Eine Merkliste behauptet
„schon versandt" auch dann noch, wenn die Datei am Ziel gelöscht, das Ziel neu
aufgesetzt oder der Pfad geändert wurde; diese Art Lüge fällt erst auf, wenn
man das Backup braucht. Die Grösse gehört dazu, weil eine abgebrochene
Übertragung sonst mit richtigem Namen und falscher Länge für immer als
erledigt gälte.

**Es wird nur ergänzt.** Auf dem Ziel löscht diese Anwendung nie — auch nicht
im Sinne der Aufbewahrung „zwei je Konto", die für die Ablage auf diesem
Server gilt (Backlog Nr. 49).

Die Zeit wird **je Konto** geprüft, nicht je Ziel: Ein Schub, der mitten in
einer Übertragung von der Zeit eingeholt wird, hinterlässt am Ziel eine halbe
Datei. Die Reserve ist mit 25 s gross, weil ein SFTP-Verbindungsaufbau in
reinem PHP über eine Sekunde kostet; am Huckepack-Weg (3 s) fängt der Job
deshalb gar nicht erst an.

Der Schalter sagt **ob**, nicht **wann**. Wann etwas läuft, entscheidet der
eingerichtete Auslöser (Abschnitt 4.97a). Eine zweite Uhr in der Datenbank
wäre eine zweite Wahrheit.

#### „Verbindung prüfen" prüft mehr als die Anmeldung

Verbinden → Probedatei schreiben → Verzeichnis lesen → zurückholen → Byte für
Byte vergleichen → löschen → trennen. Jeder Schritt steht einzeln in der
Oberfläche. Eine Anmeldung, die klappt, sagt nichts über Schreibrechte; woran
es scheitert, erführe man sonst nachts, ohne Zuschauer.

#### Gemessen (S2/AP7)

64 Pakete zu zusammen 63,89 MB aus 33 Kontoordnern, gegen örtliche
Gegenstellen:

| | Dauer | PHP-Speicherspitze (Budget Z3: 64 MB) |
|---|---|---|
| FTP | 0,13 s | 2,0 MB |
| FTPS | 0,68 s | 2,0 MB |
| SFTP | 3,08 s | 8,0 MB |

Alle 192 angekommenen Dateien byteweise mit dem Original verglichen: **0
Abweichungen**. Zweiter Lauf: 0 Dateien, 0,19 s. Eine am Ziel auf 1 000 Byte
gekürzte Datei wurde beim nächsten Lauf **einzeln** erneut geschickt (1 von
64). Mit einem Budget von 2 s teilte sich derselbe Lauf in zwei Schübe
(34 + 30) und war danach vollständig.

`tools/versandprobe/` deckt Adapter, Fingerabdruck-Riegel, Fehlerfälle und
Versiegelung ab: **115 Erwartungen**, gefahren gegen zwei Sätze Gegenstellen
— pyftpdlib/paramiko und **vsftpd/OpenSSH**. Beide werden gebraucht: vsftpd
kennt kein `MLSD` und fährt damit als einziges den Rückfall auf `NLST` +
`SIZE`; pyftpdlib fährt den Hauptweg. Gegen die echten Server: FTP 0,35 s,
FTPS 1,85 s, SFTP 0,68 s für dieselben 64 Pakete, 64 von 64 byteweise gleich.

**Der Grundpfad bedeutet je Protokoll etwas anderes.** vsftpd sperrt den
Nutzer in sein Heimverzeichnis — dort ist `/` die Wurzel. OpenSSH tut das
nicht — dort ist `/` die Wurzel des Dateisystems. Ein Ziel mit „Pfad = /" legt
seine Backups bei SFTP also dorthin, wohin der Nutzer im Dateisystem
zeigt, und nicht in ein Heimverzeichnis.

Was sie nicht prüfen kann — ein echtes Ziel im Internet —, steht an erster
Stelle ihrer `LIESMICH.md`.

---

### 4.97d Komplett-Backup der Installation (ab Web 12.2.0, S2/AP8, E-S2-19 bis E-S2-21)

Das Admin-Backup (Abschnitt „Admin-Backups") sichert ein **Konto**.
Diese hier sichert die **Installation**: alle Konten, Stammdaten, Geräte,
Schlüsselhüllen, `app_state`, den Migrationsstand — jede Tabelle, die in
dieser Datenbank steht. Der Fall, gegen den sie hilft, ist nicht „jemand hat
sich vertan", sondern „der Webspace ist weg".

Alles darin steht in `server/komplett_lib.php`; die Oberfläche ist
`admin_komplettsicherung.php`, der Rückweg `wiederherstellen.php`.

#### Was nicht drin ist

`config.php`. Sie trägt das Datenbankpasswort und den Serverschlüssel — also
genau das, womit sich diese Datei öffnen lässt. Beides in dieselbe Datei zu
legen hiesse, das Schloss an den Schlüssel zu binden. Sie gehört ins
Wiederanlaufpaket (Abschnitt 7).

Ebenfalls nicht drin: die Dateiablage unter `sicherungen/`. Die Kontopakete
sichern nichts, was nicht ohnehin in der Datenbank steht, und würden die Datei
vervielfachen.

#### Warum ein eigener Dump und nicht `mysqldump`

Auf geteiltem Webspace gibt es keine Kommandozeile und kein `exec()`;
`mysqldump` ist dort nicht vorhanden und nicht nachrüstbar. Der Dump entsteht
in PHP, über genau die Verbindung, die die Anwendung ohnehin hat.

#### Die Form: ein Statement je Zeile

Damit lässt sich die Datei zeilenweise abarbeiten — vom Rückweg dieser
Anwendung genauso wie von `mysql` oder phpMyAdmin. Ein mehrzeiliges Statement
bräuchte einen SQL-Zerleger, und ein selbstgebauter SQL-Zerleger ist die Sorte
Code, die genau einmal falsch liegt: wenn ein Semikolon in einer Zeichenkette
steht.

Daran hängt eine Bedingung: **Kein Literal darf je einen echten Zeilenumbruch
enthalten.** `komp_quote()` bildet `\n`, `\r`, `\0`, `\x1a`, `'`, `"` und
`\` ab; die Datei setzt dazu passend `SQL_MODE` ohne `NO_BACKSLASH_ESCAPES`.
Binärspalten (`track_blobs.blob_daten`) gehen hexadezimal hinaus (`0x…`), eine
leere als `''` — `0x` ohne Ziffern wäre kein gültiges Literal.

Weiter: INSERT-Stapel bis **1 MB**, Tabellen in einspielbarer Reihenfolge
(topologisch nach Fremdschlüsseln, mit `FOREIGN_KEY_CHECKS = 0` als Gürtel
daneben), Kopfkommentare mit Version, Migrationsstand, Zeitpunkt und
Datenbankserver, und am Ende eine **Endmarke** (`-- EDKOMP-ENDE`). Sie ist der
Beleg, dass die Datei nicht mitten im Erzeugen abgebrochen ist; ohne sie wäre
ein halber Dump von einem ganzen nicht zu unterscheiden.

#### Drei Schichten

    1. SQL-Text     ein Statement je Zeile, INSERT-Stapel bis 1 MB
    2. gzip         je Häppchen ein eigenes gzip-Glied
    3. EDKOMP1      AES-256-GCM je 256-KB-Block

**Warum je Häppchen ein eigenes gzip-Glied.** Der Dump entsteht in Häppchen
über mehrere Anfragen (E-S2-20: „nie als Array am Stück"). Ein
`deflate_init()`-Zustand lässt sich zwischen zwei Anfragen nicht aufbewahren —
er ist keine Zahl, sondern ein Fenster über die letzten 32 KB. Deshalb
schliesst jedes Häppchen sein Glied ab und das nächste hängt ein neues an.
Aneinandergehängte gzip-Glieder sind gültiges gzip; `gunzip`, `zcat` und PHPs
`gzopen()`/`gzread()` lesen darüber hinweg. Gemessen kostet es 3 045 Byte auf
45,8 MB.

**Zwei PHP-Funktionen können das nicht, und beide schweigen dabei.**
Nachgemessen an einer Datei aus 15 Gliedern mit 122 469 394 Byte Klartext:
`gzdecode()` liefert 13 573 234 Byte (11 %), `inflate_add()` ebenfalls
13 573 234 — beide ohne Fehler, beide sehen aus wie eine ganze Datei. Nur
`gzopen()`/`gzread()` liefert alles. In der Anwendung wird deshalb
ausschliesslich dieser Weg benutzt; der Rückweg entpackt über eine
Zwischendatei. (Python ist hier gutmütiger: `gzip.open()` **und**
`gzip.decompress()` lesen alle Glieder.)

Aufgefallen ist es beim Lauf und nicht beim Lesen: Die erste Fassung des
Rückwegs schob jeden entsiegelten Block durch `inflate_add()` und brach mit
„data error" ab. Bei einem Dump aus einem Zug wäre der Fehler nie aufgetreten
— die Komplettprobe fährt darum ausdrücklich einen aus vierzehn Häppchen.

**Warum die Versiegelung ein zweiter Gang ist.** Der Dump wächst zeilenweise,
die Versiegelung arbeitet in Blöcken fester Grösse. Beides zugleich hiesse,
einen halb gefüllten Block zwischen zwei Anfragen aufbewahren zu müssen. So
ist der Zustand der Versiegelung eine einzige Zahl — der Blockindex —, und
Block *i* deckt die Klartextbytes [i·256 KB, (i+1)·256 KB). Der Klartext-Dump
liegt für die Dauer des Baus im Bauordner und wird gelöscht, sobald die
versiegelte Fassung steht.

#### Das Format EDKOMP1

    "EDKOMP1\n"                                       8 Byte
    <Kopfzeile als JSON>"\n"                          eine Zeile, kein \n darin
    je Block:  <4 Byte Länge, big endian>
               <12 Byte Nonce><16 Byte Prüfsumme><N Byte Chiffre>

Zusatzdaten je Block: `edkomp1|<SHA-256 von Magie+Kopfzeile>|<Index>|<0|1>`.

Beides ist nötig. **Ohne Zähler** liessen sich zwei Blöcke vertauschen, und
die Prüfsumme jedes einzelnen bliebe richtig. **Ohne die Endemarkierung**
liesse sich die Datei hinten abschneiden, und was übrig bleibt, wäre eine
gültige, kürzere Backup. Der **Dateikopf** hängt über seinen SHA-256 an
jedem Block: Wer ihn ändert — etwa den Vermerk „mit Passphrase" —, macht damit
jeden Block unlesbar.

Der Schlüssel ist entweder der **Serverschlüssel** aus `config.php`
(Regelfall, `kdf: null`) oder aus einer **Passphrase** abgeleitet (PBKDF2,
`KDF_ITER_ZIEL` = 320 000 Runden, dieselbe Zahl wie im Browser). Was gilt,
steht im Kopf; raten muss das niemand.

#### Zwei Wege heraus

- **Herunterladen** gibt den Dump **unverschlüsselt** als `.sql.gz` — die
  Fassung für `mysql` und phpMyAdmin (E-S2-20). Sie geht an die
  Administratorin, die sich eben angemeldet hat und ohnehin jede Zeile dieser
  Datenbank sehen kann.
- **Versiegelt herunterladen** liefert dieselbe Datei unter einer Passphrase.
  Sie wird nicht doppelt verschlüsselt, sondern Block für Block *umgesiegelt*;
  der Speicherbedarf bleibt bei einem halben Megabyte, gleich wie gross die
  Datei ist. **Eine PBKDF2 je Vorgang** (Z3), nicht eine je Block.
- Was **von selbst** hinausgeht — der Versand aufs Backup-Ziel (4.97c) —
  ist immer die versiegelte Fassung.

Der Download ist die eine begründete Ausnahme vom Z3-Budget „Serveranfrage
≤ 30 s": Das Budget gilt der *Arbeit*, ein Download rechnet nicht, sondern
schiebt Bytes. Ein Abbruch nach 30 s wäre kein Schutz, sondern ein Backup,
das sich bei langsamer Leitung nicht abholen lässt.

**Ohne Serverschlüssel wird gar nicht erst gesichert.** Eine unversiegelte
Abschrift jeder Tabelle in `sicherungen/` liegen zu lassen unterliefe die
Ende-zu-Ende-Zusage an der Stelle, an der es am wenigsten auffiele.

#### Der Cursor ist aufgefächert, und das ist gemessen

Der Dump liest jede Tabelle blockweise über den Primärschlüssel. Gemessen an
`track_points` mit 917 331 Zeilen:

| Bedingung | Plan | Dauer |
|---|---|---|
| `WHERE (a,b) > (?,?)` — Zeilenkonstruktor | `type=index` | 0,1486 s |
| `WHERE a > ? OR (a = ? AND b > ?)` — aufgefächert | `type=range` | 0,0010 s |

MariaDB macht aus dem Zeilenkonstruktor keinen Bereichszugriff, sondern läuft
den Index von vorn ab — bei 459 Häppchen also 459-mal die halbe Tabelle.
**Eine Ausnahme:** Steht eine ENUM-Spalte **vorn** im Primärschlüssel, hilft
auch das Auffächern nichts (`type=index`, 0,0125 s); mit `=` festgenagelt
greift der Bereichszugriff wieder (0,0005 s). Führende ENUM-Spalten werden
deshalb über ihre Werteliste durchlaufen — das betrifft `track_points` und
`track_blobs` mit je zwei Werten.

#### Der Schnappschuss ist nicht scharf

`mysqldump --single-transaction` hält einen Lesestand über den ganzen Lauf.
Das geht nur **innerhalb einer Verbindung**, und dieses Backup läuft über
viele Anfragen. Eine Zeile, die währenddessen entsteht, kann enthalten sein
oder nicht. Was **nicht** passieren kann, ist eine übersprungene Altzeile: Der
Cursor läuft über den Primärschlüssel und nicht über `LIMIT/OFFSET`, ein
gelöschter Vorgänger verschiebt ihn also nicht.

Sichtbar wird das an genau einer Stelle: Die Tabelle `jobs` weicht nach einer
Rückspielung ab, weil das Backup seinen eigenen Fortschritt dort
mitschreibt. Das ist die harmloseste mögliche Stelle — und zugleich eine, die
Folgen hat, siehe gleich.

#### Wiederanlauf: zwei Fälle, die auseinandergehalten werden

Der Fortsetzungszustand steht in `jobs.zustand` und wird vom Job-Rahmen erst
**nach** einem geglückten Häppchen gespeichert. Daraus folgen zwei Zweige:

1. **Die Baudatei ist LÄNGER, als der Zustand kennt.** Ein Häppchen ist
   mittendrin abgebrochen; seine Zeilen stehen schon da, der Zustand zeigt
   davor. Der nächste Lauf schneidet auf die gemerkte Länge zurück. Ohne das
   käme das zweite `DROP TABLE` derselben Tabelle in die Datei und würde beim
   Einspielen wegwerfen, was das erste Häppchen eingefügt hat — ein
   Backup, das vollständig aussieht und es nicht ist.
2. **Die Baudatei ist KÜRZER** (oder weg). Dann wird von vorn begonnen. Der
   Fall tritt regelmässig nach einer Wiederherstellung auf: Die eingespielte
   Datenbank trägt den Stand „Dump läuft" samt einem Bauordner, den es auf dem
   neuen Server nie gab. Ohne diesen Zweig hinge der nächste Lauf mitten in
   der Tabellenliste an eine leere Datei an.

#### Der Job steht nach dem Versand

Im Katalog (4.97a) kommt `komplett` **nach** `versand`. Davor wäre er am
rechten Platz — was entsteht, ginge im selben Lauf hinaus —, nur bekäme jeder
Job hinter ihm nur noch, was die schwerste Arbeit der Anwendung übrig lässt.
Ein Versand, der wochenlang nicht drankommt, wäre der teurere Fehler. Der
Preis ist ein Lauf Verzögerung; zusätzlich begrenzt sich der Job auf
`KOMP_LAUF_MAX_S` = 120 s, damit auch `waisen` noch zum Zug kommt.

Der **Plan** (aus / täglich / wöchentlich / monatlich) sagt nicht *wann*,
sondern *ob*: Er legt fest, wie alt der jüngste Stand höchstens sein darf.
Wann gearbeitet wird, entscheidet der eingerichtete Auslöser. Zwei Uhren
nebeneinander wären zwei Wahrheiten (wie E-S2-17).

#### Der Rückweg

`wiederherstellen.php` füllt die Lücke zwischen `install.php` (verweigert
sich, sobald es eine `config.php` gibt) und `update.php` (verlangt eine
Anmeldung, die es ohne Konten nicht geben kann). Drei Schranken:

1. **Die Datenbank muss leer sein** — und zwar fürs *Anfangen*. Ab dem
   zweiten Durchgang ist sie es nicht mehr, weil der erste sie füllt; wer
   einen Arbeitsstand hat, hat ihn auf einer leeren Datenbank begonnen.
2. **Ein Nachweis** wie beim Einrichter (M1-11): eine Datei mit zufälligem
   Namen im Anwendungsverzeichnis, deren Kennung einzutragen ist.
3. **Die Datei kommt aus `sicherungen/eingang/`**, nicht aus einem Formular.
   Es gibt hier bewusst kein Hochladen.

Der Ablauf hat zwei Gänge: **A** entsiegelt und entpackt nach
`eingang/.arbeit/dump.sql`, **B** spielt zeilenweise ein und merkt sich den
**Byteversatz im Klartext**. Genau dafür gibt es Gang A: In einer gepackten
Datei kostet ein Sprung an Position *n* das Entpacken der ersten *n* Byte, bei
jedem Durchgang neu. Der Klartext wird nach dem letzten Durchgang sofort
gelöscht — er ist eine unverschlüsselte Abschrift jeder Tabelle.

Die `SET`-Zeilen (`FOREIGN_KEY_CHECKS`, `UNIQUE_CHECKS`, `SQL_MODE`) werden
**je Durchgang neu gesetzt**: Sie gelten je Verbindung, und jeder Durchgang
ist eine neue Anfrage mit einer neuen. In der Datei stehen sie trotzdem — für
`mysql` und phpMyAdmin.

**Migrationen laufen dort nicht mit.** `update.php` ist seit M6-01
zweistufig, weil Migrationen Spalten löschen können. Eine Seite ohne
Anmeldung, die sie nebenbei mitlaufen liesse, nähme genau diese Absicherung
heraus. Die Seite vergleicht stattdessen die Web-Fassung des Dumps mit der
laufenden und schickt zur Wartung.

#### Gemessen (S2/AP8)

Am Messbestand: 5 000 Einsätze, **1 121 802 Zeilen** in 34 Tabellen.

| | Wert |
|---|---|
| Erzeugen | 8,5 s in **14 Häppchen** (Budget 0,6 s je Häppchen) |
| Speicherspitze | **26 von 64 MB** (Z3) |
| SQL / versiegelt | 122,5 MB → **43,7 MB** |
| Längste Zeile | 1 048 566 Byte |
| Öffnen (175 Blöcke) | 0,05 s, Spitze 4 MB |
| Auspacken über den Rückweg | 1,24 s |
| Einspielen | 784 Anweisungen in 6,0 s |
| Rundlauf | **34 von 34** Schemata zeichengleich, **34 von 34** Prüfsummen gleich (`CHECKSUM TABLE EXTENDED`) |

`tools/komplettprobe/` fährt den ganzen Zyklus: **76 Erwartungen**,
einschliesslich Versand auf eine echte FTP-Gegenstelle, „halbe Datei liegt
dort", abgeschnitten an einer Blockgrenze, veränderter Dateikopf und beide
Wiederanlauf-Zweige. Was sie nicht prüfen kann — die Oberfläche, eine volle
Platte, ein echter Absturz mitten in der Anfrage, der Migrationslauf — steht
an erster Stelle ihrer `LIESMICH.md`.

---

### 4.97e Schneiden: ein Zeitbereich wandert (ab Web 12.5.0, S4/A2, E-S4-53)

Wer einen vergessenen Einsatz nachträgt, hat sein Problem mit dem Formular
nicht gelöst: Die **Spur** des Einsatzes liegt im Ruhesegment, in dem das
Gerät zu der Zeit aufgezeichnet hat. Das Schneidewerkzeug holt sie dort
heraus.

#### Die Punkte wandern

`spur_teilen($pdo, $quelleTyp, $quelleId, $zielTyp, $zielId, $vonTs, $bisTs)`
verschiebt alle Punkte mit `von_ts ≤ ts ≤ bis_ts` von der Quelle zum Ziel.
Beide Spuren stehen danach als Blob da.

**Kopieren wurde verworfen** (E-S4-53). Die Punkte lägen doppelt — bei rund
9 500 behaltenen Punkten je Zwölf-Stunden-Dienst spürbar —, und das
Ruhesegment behielte die Einsatzfahrt in sich: Wer es später ansieht, sähe
eine Ruhezeit, in der jemand 40 km gefahren ist.

Zwei Eigenschaften der Funktion sind nicht offensichtlich und beide nötig:

- **Sie ergänzt das Ziel, sie ersetzt es nicht.** Beim Schneiden ist das Ziel
  ein frisch angelegter Einsatz und leer; beim **Rückgängig** ist es das
  Ruhesegment, das seit dem Schnitt weitergelaufen ist. `spur_blob_schreiben()`
  ersetzt einen Blob vollständig — dessen neue Punkte wären ohne Mischen weg,
  ohne Fehlermeldung.
- **Sie schreibt auch dann einen Blob, wenn nichts übrigbleibt** — einen
  leeren, 21 Byte. Ohne ihn fände `spur_naechste_seq()` weder Zeile noch Blob
  und antwortete 0; das Gerät begänne den Dienst von vorn. Wer aus einem
  kurzen Segment schneidet, nimmt häufig alles.

Läuft bereits eine Transaktion, schließt sich `spur_teilen()` ihr an. Das ist
der Regelfall: Einsatz anlegen, schneiden, vermerken — das gilt zusammen oder
gar nicht.

#### Der Sperrvermerk, und warum `n_original` ihn nicht ersetzt

Das Gerät weiß vom Schnitt nichts. Hatte es die Punkte des geschnittenen
Zeitraums noch im Puffer — ein Funkloch reicht —, liefert es sie nach.

Die naheliegende Abwehr ist `n_original`: `spur_lesen_viele()` übergeht jede
Zeile mit `seq < n_original` (Abschnitt 4.97), der Schnitt müsste die Grenze
also nur hochsetzen. **Das trägt nicht.** `ingest.php` vergibt die
Sequenznummern aus `seq_from` — der Marke, die das Gerät zuletzt bekommen
hat. Gepufferte Punkte kommen deshalb **oberhalb** der Grenze an und laufen
glatt daran vorbei; `n_original` fängt nur die *Wiederholung* schon
gelieferter Punkte ab.

Was die Nachzügler kenntlich macht, ist ihre `ts`. Deshalb hält `track_cuts`
einen **Zeitraum** und keinen Sequenzbereich: Den gibt es beim Schnitt noch
nicht, weil es die betreffenden Punkte noch nicht gibt.

| | fängt ab | wäre sonst die Folge |
|---|---|---|
| `n_original` im Blob | Wiederholung bereits gelieferter Punkte; hält die Fortsetzungsmarke | Das Gerät sendet den ganzen Dienst noch einmal — der Schnitt löscht Zeilen, und die Marke fiele mit ihnen zurück |
| `track_cuts` (Zeitraum) | Nachlieferung aus dem Gerätepuffer | Die geschnittenen Punkte kehren in die Quelle zurück, der Schnitt löst sich still wieder auf |

Beide Böden bleiben also, und sie tun Verschiedenes.

#### Der Weg durch `ingest.php`

`schnitte_lesen()` holt die Vermerke **einmal je Upload**, vor der
Punktschleife — es ist der heißeste Schreibweg der Anwendung, eine Abfrage je
Punkt wäre der falsche Preis. In der Schleife entscheidet
`schnitt_gesperrt($schnitte, $ts)`; eine Spur hat üblicherweise null Vermerke,
und dann kostet das einen Test gegen ein leeres Feld.

Die Prüfung liegt **hinter** der `n_original`-Prüfung und **vor** der
Wertprüfung. Ein `ts`, das keine Zahl ist, wird zu 0 und fällt aus jedem
Sperrbereich heraus — die Sperre entscheidet nie über einen Punkt, den sie
nicht versteht.

Verworfene Punkte werden **genannt** (`cut_points` in der Antwort) und
trotzdem **quittiert**: Die Fortsetzungsmarke wandert über sie hinweg, sonst
liefert das Gerät endlos nach — dieselbe Regel wie bei der Sperrliste
`deleted_refs`. `cut_points` steht bewusst neben und nicht in
`dropped_points`: Dort steht die Ausdünnung („diese Spur ist fertig
verdichtet"), hier etwas anderes („diesen Zeitraum hat jemand
herausgeschnitten"). Eine Vertragsänderung ist das nicht, der Client muss
damit nichts tun.

#### Die Vermerke gehören ebenfalls hinter `spur_lib.php`

`schnitt_vermerken()`, `schnitte_lesen()`, `schnitt_gesperrt()`,
`schnitte_zum_einsatz()`, `schnitte_loeschen()`, `schnitte_loeschen_quelle()`
— aus demselben Grund wie bei den Punkten (CLAUDE.md 4): Wer die Tabelle
unmittelbar liest, bekommt früher oder später eine halbe Auskunft, etwa indem
er den Vermerk zum Ziel löscht und den zur Quelle stehenlässt.

`track_cuts` hängt an keinem Fremdschlüssel — polymorph wie `track_points` und
`track_blobs`, aus demselben Grund und mit demselben Preis. Die Löschwege
räumen ausdrücklich mit: Papierkorb (`trash_lib.php`, beide Richtungen —
Vermerk *zum* Einsatz und Vermerk *am* Einsatz), Kontolöschung
(`admin_user.php`) und der Waisenjob als Sicherheitsnetz (`jobs_lib.php`).
Bleibt ein Vermerk stehen, sperrt er einen Zeitraum für immer, und zwar
unsichtbar — die Oberfläche zeigt ihn nicht.

**Nicht mit abgeräumt wird beim Schnitt selbst:** `spur_teilen()` ruft intern
`spur_loeschen()` für die Quelle, und das darf die Vermerke nicht anfassen —
sonst löschte der zweite Schnitt an einem Segment die Sperre des ersten.

#### Die Bedienung (ab Web 12.6.0)

Die Tagesansicht führt die Ruhesegmente als eigene Karte — Zeitraum, Dauer,
Punktzahl, und wo Punkte da sind, **„Schneiden"**. An der Zeile klappt der
Schneide-Bereich auf: Zeitleiste, Beginn und Ende (Pflicht), drei Phasenzeiten
(optional). Gebaut wird er in `assets/schneiden.js`, gestaltet nach
`docs/Design.md` 9.17.

**`api/schneiden.php` kennt zwei Aktionen**, beide POST mit `X-CSRF`:

| Aktion | Nutzlast | tut |
|---|---|---|
| `schneiden` | `rest_id`, `beginn`, `ende` (`hh:mm`), `beginn_tag`/`ende_tag`, `phasen` | legt den Einsatz an, verschiebt die Punkte, vermerkt den Schnitt |
| `rueckgaengig` | `mission_id` | gibt die Punkte zurück, löscht Vermerk und Einsatz |

Der Einsatz entsteht auf dem **Bestandsweg** — virtuelles Gerät
`manual-<userId>`, `origin = 'manual'`, `manual = 1`, `client_ref` mit Präfix
`cut-`, wörtlich wie in `einsatz_form.php`. Daran hängt, ob er durch
Backup, Export und Papierkorb kommt (R24), und ob `ingest.php` seine Phasen
später noch anfasst. Alles läuft in **einer** Transaktion; `spur_teilen()`
schließt sich ihr an, statt eine eigene mitzubringen.

**Was der Endpunkt nicht tut:** Einsatzfelder füllen. Einsatzort, Alter und
Diagnose sind Ende-zu-Ende-verschlüsselt und entstehen im Browser; ein
Endpunkt, der sie annähme, bräuchte Klartext.

**Ein Schnitt ohne Punkte wird abgelehnt** (409). Er entstünde beim zweiten
Schnitt über denselben Bereich oder über eine Aufzeichnungslücke, und heraus
käme ein leerer Einsatz, den das Rückgängig nicht anfassen kann: Ohne
gewanderte Punkte gibt es keinen Vermerk, und ohne Vermerk keinen Weg zurück.

**Das Rückgängig hält an, was am Einsatz hängt** — abweichende Besatzung,
Rettungsmittel, Reanimation, `edited`, `pat_blob`. Der Grund ist nicht
Vorsicht, sondern Arithmetik: Es *löscht* den Einsatz. Ein Einsatz mit Inhalt
geht über den Papierkorb, wo die Frist läuft.

> **Zeiten gehen fertig formatiert hinaus.** `api/day.php` liefert je Segment
> `start_hhmm`/`end_hhmm` (App-Zeitzone), `von_ts`/`bis_ts` (Epochensekunden
> für die Balkengeometrie) und `start_tag`/`end_tag` (Kalendertage hinter dem
> Diensttag, für Dienste über Mitternacht). Der Browser rechnet nur in
> Minuten.
>
> Das ist die Linie der ganzen Anwendung, und diese Stelle hatte sie einmal
> verlassen: Die erste Fassung schickte `started_at` roh als UTC, und der
> Browser rechnete mit `new Date(…)` in *seine* Zone. Auf einem Rechner in der
> Zone der Anwendung fällt das nie auf; im Prüfcontainer ist sie UTC, und der
> Schnitt griff zwei Stunden daneben und nahm **null Punkte** mit — mit
> Erfolgsmeldung.

#### Nachweis

`tools/spurprobe/probe.php`, **Teil 6** — auf einer eigens angelegten Kulisse
in einer zurückgerollten Transaktion. Der Bestand liefert diesen Fall nicht:
Er braucht eine Spur, die beim Schnitt absichtlich nur zur Hälfte geliefert
ist. **20 Erwartungen, alle erfüllt.** Die Kernzahlen: 350 Punkte geliefert,
50 geschnitten; von 250 nachgelieferten Punkten **50 gesperrt, 200
angenommen**; Segment danach 500 Punkte, Einsatz 50. Nach dem Rückgängig 550
und 0, ohne einen zeitlichen Rücksprung in der vereinigten Spur.

Die Bedienung ist im Browser abgenommen (Chromium, lokale Installation):
**28 Erwartungen, alle erfüllt** — Schneiden, Rückgängig und die Grenzfälle
(Unsinn im Feld, Zeit außerhalb des Segments, Ende vor Beginn, zweiter Schnitt
über denselben Bereich). Segment 61 → 48 Punkte, Einsatzliste 3 → 4, nach dem
Rückgängig wieder 61 und 3. Bei 390 px: waagerechter Überlauf 0, alle
Bedienelemente 44 px.

### 4.97f GPX herein: der Weg zurück (ab Web 12.7.0, S4/A3, E-S4-18)

Das Gegenstück zum Abruf (4.97b). Eine Aufzeichnung, die auf einem anderen
Gerät entstanden ist, kommt damit in die Anwendung: über **„···" → „GPX
importieren"** in der Tagesansicht, als Dialog (`Design.md` 9.11).

#### Zwei Ziele

| Ziel | wird | wofür |
|---|---|---|
| `ruhe` | ein **Ruhesegment** | Die Datei ist die Aufzeichnung eines ganzen Dienstes; die Einsätze schneidet man danach heraus (4.97e). Der Regelfall. |
| `einsatz` | ein **Einsatz** | Die Datei *ist* genau ein Einsatz; die Phasenzeiten trägt man danach im Formular nach. |

Beide entstehen auf dem Bestandsweg: virtuelles Gerät `manual-<userId>`,
`client_ref` mit Präfix **`imp-`** wie beim CSV-Import (daran hängt die
Sperrliste `deleted_refs`), beim Einsatz zusätzlich `origin = 'import'` und
`manual = 1`. Die Spur wird **gleich als Blob** abgelegt (Stufe 2,
`n_original` = volle Punktzahl): Eine importierte Spur ist fertig — es kommt
nichts mehr nach, denn ihr „Gerät" ist eine Datei.

#### Der Leser wohnt beim Schreiber

`gpx_lesen()` steht in `gpx_lib.php`, direkt unter `gpx_bauen()`. GPX hat
damit genau **eine** Stelle in dieser Anwendung, die es kennt. Ein Leser, der
woanders wohnt, läuft früher oder später mit anderen Annahmen als der
Schreiber — und das fällt erst auf, wenn eine Datei durch den einen Weg
hinaus und den anderen nicht wieder hinein kommt.

**Gelesen wird auf dem Server**, anders als beim CSV-Import. Der Unterschied
ist der Inhalt: Beim CSV stehen Patientendaten in der Datei, die der Server
nie sehen darf, also *muss* der Browser lesen. Eine GPX-Datei enthält nichts
Verschlüsseltes. Und die Ablehnungsregeln sind verbindlich; eine verbindliche
Regel im Browser ist keine.

Die Datei kommt als Zeichenkette im JSON-Körper, **nicht als Dateiupload**.
Diese Anwendung hat nirgends ein `$_FILES`; ein erster Upload-Weg brächte
`upload_max_filesize`, `post_max_size`, temporäre Verzeichnisse und deren
Rechte mit — vier Stellschrauben auf geteiltem Hosting für einen Vorgang, den
eine Zeichenkette genauso trägt. Der Browser liest mit `FileReader`.

#### Was abgelehnt wird — und warum jede Ablehnung einen Satz mitbringt

| Fall | Antwort |
|---|---|
| kein gültiges XML | 422, mit der Fehlerstelle des Parsers |
| Wurzelelement ≠ `<gpx>` | 422, mit dem tatsächlichen Namen |
| **`<!DOCTYPE>` vorhanden** | 422 — siehe unten |
| kein Punkt hat `<time>` | 422, mit der Punktzahl und der Begründung |
| kein `<trkpt>` | 422 („Wegpunkte und Routen liest dieser Import nicht") |
| > `LIMIT_TRACKPUNKTE_SPUR` (50 000) | 422, mit Grenze **und** Umrechnung in Stunden |
| > `GPX_DATEI_MAX` (12 MB) | 422, mit Größe und Grenze |

`gpx_lesen()` wirft mit einem Satz, der einer BedienerIn etwas sagt, und der
Endpunkt reicht ihn unverändert durch. Das ist Absicht: „Import
fehlgeschlagen" ließe jemanden dreimal dieselbe Datei wählen, ohne je zu
erfahren, dass ihr die Zeitstempel fehlen.

> **Kein DOCTYPE — die XXE-Abwehr steht vor dem Parser, nicht darin.**
> `libxml_disable_entity_loader()` gibt es seit PHP 8 nicht mehr, externe
> Entitäten lädt libxml seither von sich aus nicht — aber **interne**
> expandiert es weiterhin, und daraus baut man eine Entitätenbombe ohne eine
> einzige externe Referenz. Eine GPX-Datei braucht keinen DOCTYPE; wer einen
> mitschickt, bekommt eine Absage statt einer Auslegung. Dazu `LIBXML_NONET`:
> kein Netzzugriff, unter keinen Umständen (CLAUDE.md 4 gilt auch für einen
> Parser).

#### Toleranz, wo sie richtig ist

Angenommen werden **GPX 1.0** und Dateien **ohne Namensraum**: Die Elemente,
um die es geht, heißen in beiden Fassungen gleich und bedeuten dasselbe. Auf
1.1 zu bestehen hieße, Dateien abzulehnen, die inhaltlich in Ordnung sind.

Mehrere `<trkseg>` oder `<trk>` werden zu **einer** Spur zusammengeführt und
**nach Zeit sortiert**, die Sequenz danach neu vergeben — der Blob speichert
Differenzen und verlässt sich auf eine aufsteigende Zeitfolge (4.97), und die
Dateireihenfolge muss nicht die zeitliche sein.

Einzelne unbrauchbare Punkte fallen heraus, ohne die Datei zu verwerfen; ihre
Zahl steht in der Antwort (`ohne_zeit`, `verworfen`) und in der Rückmeldung an
die BedienerIn. Die Koordinaten gehen dabei durch `pruef_breite()` /
`pruef_laenge()` — eine eigene Bereichsprüfung hier wäre eine zweite Wahrheit
darüber, was ein gültiger Breitengrad ist (CLAUDE.md 4).

> **Attribute über `attributes()`, nie über `$el['lat']`** — eine Falle, in
> die dieses Paket getreten ist. Nach `children($ns)` schaltet SimpleXML die
> Namensraum-Umgebung eines Knotens um, **auch für Attribute**. `$pt['lat']`
> sucht danach ein `lat` im GPX-Namensraum, und ein unpräfigiertes Attribut
> liegt in **keinem** (XML-Namens-Spezifikation 6.2). Das Ergebnis war kein
> Fehler, sondern ein leerer String: Jeder Punkt fiel durch die
> Koordinatenprüfung, und die Meldung lautete „enthält keinen einzigen
> Trackpunkt" — bei 61 vorhandenen.

#### Nachweis

**Der Leser: 17 Erwartungen, alle erfüllt** — Rundlauf über den Schreiber
(61 Punkte hinaus, 61 zurück, 0 Abweichungen), sieben Ablehnungsfälle mit
Prüfung der Meldung, GPX 1.0, Namensraum-freie Dateien, zeitliche Sortierung
über zwei Segmente. **9 000 Punkte (0,78 MB) in 0,13 s.**

**Im Browser: 17 Erwartungen, alle erfüllt** — Import als Ruhesegment (6 → 7,
54 Punkte) und als Einsatz (4 → 5), beide Ablehnungsfälle mit sichtbarer
Begründung, keine unerwarteten Konsolenfehler.

**Der Rundlauf der Abnahme: 12 Erwartungen, alle erfüllt** — importierte Spur
→ GPX-Abruf → erneut gelesen: 54 Punkte, **0 Abweichungen** gegen die
Quelldatei, für Segment *und* Einsatz.

### 4.97g Die Android-App verteilen (ab Web 12.8.0, S4/A1, E-S4-16)

Die App wird über die Anwendung selbst verteilt, nicht über einen App-Store.
Die Karte **„NAdoku für Android"** auf dem Geräte-Reiter zeigt, was in
`server/apk/` **liegt** — Name, Größe, Fassung (aus dem Dateinamen), Datum
und den gerechneten SHA-256.

**Von Hand gepflegt wird nichts.** Eine Versionsangabe, die jemand eintippt,
stimmt am Tag des Eintippens und danach nie wieder. Die Prüfsumme entsteht bei
jedem Aufruf neu (bei 7 MB wenige Millisekunden); ein zwischengespeicherter
Wert wäre genau die Zahl, die nach einem Austausch der Datei noch die alte
nennt.

Die Fassung kommt aus dem **Dateinamen** (`nadoku-1.0.0.apk`), nicht aus dem
APK. Sie dort zu lesen hieße, ein ZIP zu öffnen und das Android-Binär-XML des
Manifests zu entschlüsseln — dafür gäbe es keine Bibliothek im Haus, und eine
neue Abhängigkeit für eine Anzeige wäre der falsche Preis (CLAUDE.md 4).
Trägt der Name keine, steht keine da.

#### Zwei Ausnahmelisten, und beide sind nötig

| Ort | Eintrag | ohne ihn |
|---|---|---|
| `.gitignore` | `server/apk/` | Ein signiertes APK läge im Verlauf — ein Erzeugnis, kein Quelltext, bei jeder Fassung ein zweistelliges MB |
| `.github/workflows/deploy.yml` | `apk/**` und `apk/` | **Der nächste Push löschte die Dateien.** Die Action synchronisiert `server/` und entfernt, was nicht ausgenommen ist |

Der zweite ist der, den man vergisst. Dasselbe Muster wie `config.php` und
`sicherungen/`, inklusive der doppelten Schreibweise: Die Action prüft
Datei- und Verzeichnismuster getrennt.

Hochgeladen wird per FTPS durch die Betreiberin.

#### Der Name wird nicht geprüft, sondern gesucht

`apk.php` liest den Ordner (`apk_liste()`) und wählt aus dem **Gelesenen**
aus. Ein Pfad, den der Aufrufer zusammensetzt, kommt damit nie an `fopen()` —
auch keiner mit `..`, keiner mit einem Nullbyte und keiner mit einem
Zeilenumbruch für die `Content-Disposition`-Kopfzeile. Der Unterschied zu
„gefährliche Zeichen entfernen" ist, dass hier nichts vergessen werden kann.

`apk_liste()` nimmt nur `[A-Za-z0-9._-]+\.apk` an. Ein Verzeichnis, in das
jemand per FTP schreibt, ist kein vertrauenswürdiger Eingang; was nicht auf
das Muster passt, wird still übergangen (eine `.DS_Store` dort ist keine
Fehlermeldung wert).

Der Abruf liegt **neben** den Seiten und nicht unter `api/`, aus demselben
Grund wie `gpx.php` (4.97b): `ist_api_aufruf()` entscheidet am Pfad, und ein
`<a href>` bekäme dort nach einer Mittagspause `{"error":"session_ende"}` im
Browserfenster statt der Anmeldeseite. Nur angemeldet, GET, ohne CSRF (M3-11).
`Cache-Control: private, max-age=86400` und nicht `no-store`: Ein APK ist
unveränderlich, sobald es liegt — es trägt seine Fassung im Namen, und ein
Austausch bekommt einen neuen.

#### Nachweis

**Im Browser: 10 Erwartungen, alle erfüllt** (gegen eine 7-MB-Attrappe) —
Karte vorhanden, „7,0 MB · Fassung 1.0.0 · Stand …", Prüfsumme in 16
Vierergruppen, Download neutral, Datei kommt in 7 340 032 Byte an,
`?d=../config.php` läuft ins Leere, unbekannte Datei bekommt eine Seite statt
eines leeren 404.

**Nicht geprüft:** Die Deploy-Ausnahme ist am Workflow-Text abgeleitet, nicht
durchgespielt — ein Trockenlauf bräuchte FTP-Zugangsdaten. Und es gab kein
echtes APK.

### 4.98 Was im verschlüsselten Block liegt — und was nicht

Der Server kann `missions.pat_blob` nicht lesen. Genau deshalb muss an einer
Stelle stehen, **welche Felder darin liegen**: Wer das nicht weiß, kann weder
eine Auskunft nach Datenschutzrecht beantworten noch beurteilen, was ein
Datenbank-Abzug preisgibt — und niemand kann prüfen, ob ein neues Feld
versehentlich im Klartext gelandet ist.

**Im verschlüsselten Block** (`pat_blob`, AES-256-GCM, Schlüssel nur im
Browser), erzeugt in `einsatz_form.php`:

| Schlüssel | Inhalt |
|---|---|
| `last`, `first` | Nachname, Vorname |
| `dob` | Geburtsdatum |
| `age` | Alter — nur gespeichert, wenn es **nicht** aus `dob` folgt |
| `dx` | Diagnose |
| `mission_no` | Einsatznummer der Leitstelle |
| `loc.addr` | Adresse des Einsatzorts |
| `loc.lat`, `loc.lon` | Koordinaten des Einsatzorts |
| `site_desc` | Beschreibung des Einsatzorts (Zufahrt, Landestelle) |

Fehlende Schlüssel bedeuten „keine Angabe"; ein leerer Block wird als
`__CLEAR__` übertragen und löscht den vorhandenen.

> **`site_desc` ist ein aktives Feld, kein Altbestand.** Es sieht wie ein Rest
> der früheren Klartextspalte aus, ist aber Teil des verschlüsselten Blocks und
> wird an acht Stellen gelesen und geschrieben. Es zu entfernen zerstörte
> stillschweigend vorhandene Patientendaten.

**Im Klartext in der Datenbank** stehen dagegen: Zeiten und Phasen, Track,
Distanz und Steigung, Reanimationsereignisse, Besatzung, Einsatzmittel,
Diensttag- und Standortdaten. Das ist eine bewusste Entscheidung — diese Angaben
sind für Auswertung, Sortierung und Statistik nötig, die der Server leisten
muss. Sie sind für sich genommen nicht personenbeziehbar; **in Verbindung mit
Ort und Zeitpunkt eines Einsatzes können sie es aber werden.** Wer eine
Installation betreibt, sollte das wissen und den Datenbankzugang entsprechend
behandeln.

Die Zuordnung Datensatz ↔ Person entsteht ausschließlich über den
verschlüsselten Block.

### 4.98a Ortsfeld und Luftlinie (ab Web 6.1.0)

**Das Ortsfeld war keine Komponente.** Bis Web 6.0.0 stand das Einsatzort-Widget
ausgeschrieben in `einsatz_form.php`: rund 180 Zeilen, verdrahtet über die
festen Kennungen `locaddr`, `loclat`, `loclon`, `locstate` — Photon-Abfrage,
Plus-Code-Erkennung, Chip, Zustandszeile und die Prüfung „Koordinaten ohne
Bezeichnung" hingen alle daran. Mit Etappe 2 sind **sechs** Verwendungen
gefordert; sechs Kopien wären sechs Fassungen, die auseinanderlaufen.

Die Komponente besteht aus zwei Hälften, die dasselbe Präfix teilen:

| Hälfte | Datei | Aufgabe |
|---|---|---|
| Markup | `ui_ortsfeld()` in `ui.php` | erzeugt `<p>addr`, `<p>such`, `<p>lat`, `<p>lon`, `<p>suggest`, `<p>state`, `<p>chips`, `<p>dl` |
| Verhalten | `assets/ortsfeld.js` | `EdOrtsfeld.init({praefix, …})` |

Eine Verwendung ist damit ein PHP-Aufruf und ein `init()`. Die sechs:

| Verwendung | Präfix | Besonderheit |
|---|---|---|
| Einsatzort | `loc` | Textfeld = Suchfeld (die Adresse **ist** die Bezeichnung) |
| Manueller Abfahrtort | `start` | wie Einsatzort, eigener Blob-Schlüssel `start` |
| Zielklinik am Einsatz | `f_transport_dest_` | getrennte Suche, `<datalist>` aus den Stammdaten **mit Koordinaten** |
| Standort im Konto / zentral | `sdbase` / `adbase` | getrennte Suche, nur Zubehör (`feld => false`) |
| Zielklinik im Konto / zentral | `sdtd<id>` / `adtd<id>` | dito, Präfix trägt die Standortkennung — das Formular steht einmal je Standort auf der Seite |

**Zwei Bedienformen, ein Code.** Bei `getrennteSuche: false` sucht das
Textfeld beim Tippen; ein Adresstreffer wird zur Bezeichnung. Bei `true`
läuft die Suche **nur auf den Lupen-Knopf** (seit Web 9.4.0 — er ersetzt das
frühere zweite Suchfeld „Lokalisation …"), und der Treffer setzt **nur** die
Koordinaten — „Standort Kempten" ist keine Adresse, und eine Suche, die den
Namen überschriebe, nähme ihn weg. Alles übrige ist in beiden Formen
dasselbe: Chip statt Zahlen im Textfeld, lokale Formaterkennung vor jeder
Netzanfrage, Bestätigung statt sofortiger Übernahme, ruhende Suche bei
gesetzten Koordinaten, und die Prüfung „Koordinaten ohne Bezeichnung" beim
Absenden.

**Die Ortswahl** (`assets/ortswahl.js`, Web 9.4.0, E-P3-34): Der Pin-Knopf
am Ortsfeld (`ui_ortsfeld` mit `'ortswahl' => true` — Einsatzort und
manueller Abfahrtort) öffnet ein Blatt mit „Meine Position übernehmen"
(`navigator.geolocation`, nur über HTTPS) und „Auf der Karte wählen"
(Leaflet-Dialog mit **Fadenkreuz** in der Kartenmitte statt Klick-Marker —
auf dem Handy verdeckt der eigene Finger sonst genau die Stelle). Zur
Koordinate holt die **Photon-Umkehrsuche** eine Adresse; sie füllt das Feld
nur, wenn es leer ist (`EdOrtsfeld`-Steuerobjekt, `uebernehmen()`), und die
Anfrage trägt ausschließlich die Koordinate. Die Verwendungen registrieren
sich mit `EdOrtswahl.registriere(praefix, steuerobjekt)`.

**Die Luftlinie** (`assets/luftlinie.js`) zeichnet, was ohne GPS-Aufzeichnung
über den Weg bekannt ist: **Abfahrtort → Einsatzort → Zielklinik**, immer
gestrichelt — das Strichmuster, nicht die Farbe, trägt die Unterscheidung zum
aufgezeichneten Track. In der Einsatzansicht ist sie Max Blau; die
Tagesübersicht färbt sie seit Web 9.2.0 in der Spurfarbe ihres Einsatzes, weil
bei mehreren Einsätzen sonst nicht erkennbar wäre, welche Linie zu welchem
gehört. Drei Regeln, die sie nie verletzt:

* **Ein Track hat Vorrang.** Liegt er vor, unterbleibt die Linie; die
  Abfahrtortangabe bleibt gespeichert und wird lediglich nicht gezeichnet.
* **Ohne Einsatzort keine Linie** — auch dann nicht, wenn Abfahrtort und
  Zielklinik beide Koordinaten haben. Eine direkte Verbindung zwischen beiden
  hat nie stattgefunden.
* **Kein Ausweichen.** Fehlt die Koordinate der *gewählten* Quelle, entsteht
  keine Linie. Eine falsche ist schlechter als keine.

**Gespeichert wird die Regel, nicht der Ort.** `missions.start_src` trägt
`base`, `prev_site`, `prev_dest` oder `manual`; wo die Koordinate herkommt,
hängt daran — und mit ihr, ob sie im Klartext steht:

| Regel | Quelle | Sichtbarkeit | Wer löst auf |
|---|---|---|---|
| `base` | `days.base_lat/base_lon` (eingefroren) | Klartext | Server |
| `prev_dest` | `dest_lat/dest_lon` des Vorgängers | Klartext | Server |
| `prev_site` | Einsatzort des Vorgängers | verschlüsselt | Browser |
| `manual` | `pat_blob.start` | verschlüsselt | Browser |

Der Klartextwert verrät damit nur die **Regel**, keinen Ort. `api/mission.php`
liefert ausschließlich, was die gewählte Regel braucht — den Blob eines anderen
Einsatzes mitzuschicken, wo niemand ihn auswertet, wäre eine Datenweitergabe
ohne Zweck, auch innerhalb desselben Kontos. Auf der Tagesübersicht entfällt der
Umweg: Dort liegen die Einsätze des Tages ohnehin gemeinsam vor und sind bereits
entschlüsselt, der Vorgänger ist schlicht der davor in der Liste.

**Die Luftlinienlänge fließt in keine Kachel und in keinen Filter.** Zwei
Gründe, beide fachlich: Die Kacheln werden serverseitig aggregiert, und der
Einsatzort liegt verschlüsselt im `pat_blob` — dieselbe Grenze, an der auch die
serverseitige Suche endet. Und eine Luftlinie ist keine gefahrene Strecke;
beides in einer Summe machte „Einsatzkilometer gesamt" unbrauchbar.

### 4.98b Sichtbarkeit von Einsatzfeldern

Vier Schlüssel des Feldkatalogs entscheiden, ob ein Feld erscheint. Drei davon
**verstecken nur** — der vierte **leert**:

| Schlüssel | Frage | Verhalten |
|---|---|---|
| `role_gate` | Bietet der Diensttag diese Rolle an (`day_crew`)? | verstecken |
| `kind_gate` | Hat der Diensttag diese Art (`days.kind`)? | verstecken |
| `cap_gate` | Trägt der Diensttag diese Fähigkeit (`day_capabilities`)? | verstecken |
| `show_if` | Hat das übergeordnete Auswahlfeld einen ausgeschlossenen Wert? | **leeren** |

Der Unterschied ist kein Zufall. Ein *gefiltertes* Feld ist ein Feld, das an
diesem Dienst nicht vorkommt — sein Inhalt bleibt trotzdem gültig und wird
weiterhin gerendert, nur versteckt; ein bereits belegtes Feld bleibt sogar
sichtbar. Sonst käme man an einen Wert nicht mehr heran, wenn der Diensttag
später das Rettungsmittel wechselt. Ein *ausgeschlossenes Unterfeld* dagegen
wäre ein Widerspruch in den Daten: Transport „Ambulant" mit eingetragener
Zielklinik heißt, dass beides nicht stimmen kann. Es wird deshalb geleert, und
zwar sichtbar — das Feld verschwindet vor dem Speichern, nicht danach.

**Alle drei Filter fragen den DIENSTTAG, nie die heutigen Stammdaten.** Wird der
Windenhaken Jahre später am Hubschrauber entfernt, ändert das an dokumentierten
Einsätzen nichts: Gefragt ist `day_capabilities`, und das ist beim Zuordnen
eingefroren worden (E8).

**Was „belegt" heißt, hängt am Typ.** Ein Textfeld ist belegt, wenn etwas
drinsteht; ein Haken erst, wenn er gesetzt ist. Ohne diese Unterscheidung wäre
jede Checkbox eines bearbeiteten Einsatzes belegt — ihr Wert ist dann „0" und
nicht die leere Zeichenkette — und kein `cap_gate` hätte je gegriffen.

**Wert ≠ Beschriftung (ab Web 6.1.0).** `'options'` durfte bis dahin nur eine
Liste sein, und was dort stand, ging genau so in die Spalte. Mit der
Transportart geht das nicht mehr auf: Die Spalte ist ein
`ENUM('air','ground','ambulant')`, angezeigt gehört „Luft", „Boden",
„Ambulant". `mf_optionen()` ist die eine Stelle, die beide Schreibweisen
auflöst — eine Liste bleibt Wert = Beschriftung, eine Zuordnung trennt sie.

### 4.98c Gruppen des Einsatzformulars (ab Web 7.0.0)

Das Formular rendert nicht mehr den ganzen Katalog am Stück, sondern **Gruppe
für Gruppe**. Welche Gruppe ein Feld trägt, steht am Feld (`gruppe`), nicht in
einer zweiten Liste im Formular — sonst müsste ein neues Feld an zwei Stellen
nachgezogen werden.

| Schlüssel | Wirkung |
|---|---|
| `gruppe` | Formulargruppe (`einsatz`, `transport`, `bergrettung`, `mittel`, `besatzung`, `notizen`). Nur an Feldern der obersten Ebene; Unterfelder folgen ihrem Elternfeld. Ohne Angabe landet das Feld in der Auffanggruppe „Weitere Angaben" — es verschwindet also nicht. |
| `nebeneinander` | Unmittelbar aufeinanderfolgende Felder mit diesem Schlüssel teilen sich eine Zeile (`.fld-reihe`). Bewusst nur unmittelbare Nachbarn: Sonst hinge die Anordnung davon ab, was dazwischen steht. |
| `vorbelegt_bei` | Checkbox setzt sich, sobald ein anderes Feld einen genannten Wert annimmt — **nur solange niemand sie von Hand angefasst hat** und **nur beim Nachtragen**. Wirkt ausschliesslich im Browser. |
| `such_label` | Beschriftung des Suchfeldes eines Ortsfelds (`loc`). |

Drei Helfer in `einsatz_form.php` werten das aus: `$gruppeFelder()` holt die
Felder einer Gruppe, `$gruppeRendern()` gibt sie samt `.fld-reihe`-Klammerung
aus, `$gruppeSichtbar()` beantwortet, ob eine Gruppe überhaupt etwas zu zeigen
hat. Letzteres fragt nach **sichtbaren** Feldern (Gates plus „belegt", siehe
4.98b): Die Gruppe „Bergrettung" besteht aus zwei Feldern, die beide an einer
Fähigkeit hängen — an einem NEF-Dienst wäre sie ein Rahmen mit Überschrift und
nichts darin und fällt deshalb ganz weg.

Die Reihenfolge des Katalogs ist zugleich die des Formulars. Sie steuert
ausserdem die Spaltenfolge der Tagesübersicht (`mf_tagesspalten()`) und des
Exports; der **Import ist unberührt**, er ordnet über Spaltennamen zu.

**Das Bezugsdatum ist kein Formularfeld mehr.** Beim Bearbeiten kommt es aus
`started_at` in Ortszeit, beim Nachtragen aus dem Diensttag — und liegt die
erste eingetragene Phase **vor** dem Beginn des Dienstes
(`days.started_at` in Ortszeit), gilt der Folgetag. Verglichen werden Minuten,
nicht Zeichenketten: „1:30" ist eine gültige Eingabe (die Maske füllt die
führende Null erst beim Verlassen des Feldes) und stünde als Text hinter
„07:00" — der Tageswechsel griffe dann ausgerechnet im gedachten Fall nicht.

**Der Abfahrtort wird nur ohne Track gerendert.** Schwelle sind **zwei**
Trackpunkte, weil erst zwei eine Linie ergeben — dieselbe Bedingung, die die
Einsatzansicht für ihre Luftlinie anlegt. Das Skript des Formulars fragt
`start_src` deshalb überall auf Existenz ab, statt sie vorauszusetzen. Die
gespeicherte Regel bleibt in der Datenbank unangetastet.

### 4.99 Gemeinsame Bausteine

Die Anwendung hat vier unabhängige Schreibwege in dieselben Tabellen. Die
Prüftiefe verlief historisch **umgekehrt zur Vertrauenswürdigkeit der Quelle**:

Der Zustand vor Web 4.2.0 — die Reihenfolge nach Sorgfalt lautete Import →
Formular → Uhr → Backup, das nach Vertrauenswürdigkeit der Quelle genau
umgekehrt:

| Prüfung | Formular | Import | Uhr | Backup |
|---|---|---|---|---|
| Datumsformat | Muster | Muster | Muster | nein |
| Kalendertag existiert | nein | nein | nein | nein |
| Zeitstempel geprüft | ja | ja | ja | nein |
| Zeichenketten auf Spaltenlänge | ja | ja | teilweise | nein |
| Zahlenbereiche | ja | ja | nein | nein |
| Patientenblock-Muster | 16…8000 | 20…60000 | — | nein |
| Phasennummer 2–9 | ja | ja | ja | nein |
| Koordinaten ±90 / ±180 | — | ja | nein | nein |
| Reanimationsart gegen Liste | ja (ab 5.5.0) | ja | ja | nein |
| Mengenbegrenzungen | ja (ab 5.5.0) | ja | keine | keine |

**Seit Web 4.2.0 steht in allen vier Spalten „ja" — und zwar durch denselben
Baustein.** `validate_lib.php` ist die eine Stelle; die vier Wege rufen sie auf,
statt je eigene Regeln zu führen. Weicht ein Weg ab, ist das ein Fehler in
diesem Weg und nicht eine zulässige Eigenheit.

Zwei Ausnahmen sind gewollt und keine Lücke:

* Der **Patientenblock** wird auf dem Uhr-Weg nicht geprüft, weil die Uhr
  keinen sendet — sie kennt die geschützten Angaben nicht.
* **Koordinaten** kommen im Formular nicht vor; es erfasst sie nicht.

**Reanimationen im Formular (ab Web 5.5.0, Block A4.3).** Bis dahin galt auch
für Reanimationsarten „kommt im Formular nicht vor". Seit `einsatz_form.php`
`resus_sessions` und `resus_events` schreibt, benutzt es dieselben Bausteine
wie die anderen Wege: `pruef_reanimationsart()` gegen `RESUS_LABELS`, dazu
`LIMIT_REA_SESSION` und `LIMIT_REA_EREIGN`. Zwei Eigenheiten dieses Weges:

* `beginn` ist **keine** Ereignisart. Der Beginn steckt in
  `resus_sessions.started_at` (JSON-Vertrag 3.3); die Auswahl im Formular
  bietet ihn nicht an, und das Einlesen weist ihn zusätzlich ab.
* Die Zeitrechnung folgt den Phasen: Eine Zeit vor ihrer Bezugszeit gehört dem
  Folgetag. Bezug ist beim Beginn `missions.started_at`, bei jedem Ereignis
  das vorhergehende. Umgesetzt über `local_to_utc($day, $hhmm, $addDays)`.

Der Schreibweg ersetzt vollständig (`DELETE` je Einsatz, dann `INSERT`) — wie
`ingest.php`, nur ohne dessen Vergleich der Sitzungszahl: Was im Formular
steht, ist die Absicht der Person, und ein Formular kann nichts „unvollständig
nachliefern". Die Ereignisse räumt der Fremdschlüssel mit ab
(`ON DELETE CASCADE`).

Die Bausteine im Einzelnen:

| Baustein | Datei | Aufgabe |
|---|---|---|
| Prüfschicht | `validate_lib.php` | Wertebereiche, Längen, Formate, Mengen aller Einsatz- und Ruhesegmentfelder. Unterscheidet „Wert war ungültig" von „Wert war nicht vorhanden" (`Pruefliste`), damit ein Fehler nicht als Erfolg gemeldet werden kann. |
| Kalendertag | `validate_lib.php` | Ein unmöglicher Tag wird abgelehnt statt still verschoben (30. Februar → 2. März). Sichtbar nur über die Warnungsabfrage der Datumsklasse. |
| Ratenschutz | `ratelimit_lib.php` | Zählung je Konto **und** IP, in der Datenbank. Greift **vor** teuren Prüfungen (bcrypt, PBKDF2), Antwortzeit bei Misserfolg konstant. Seit Web 4.4.0 an allen vier Töpfen angewendet: `login`, `salt`, `reset`, `pair`. |
| Fester Vergleichswert | `AUTH_VERGLEICHSWERT` in `db.php` | Ein bcrypt-Hash ohne zugehöriges Geheimnis, damit auch der Zweig „Kennung unbekannt" eine Passwortprüfung rechnet. Ohne ihn beantwortet die Antwortzeit die Frage, welche Konten und Geräte es gibt. |
| Antwort abschließen | `antwort_abschliessen()` in `smtp.php` | Beendet die Antwort, bevor der Mailversand beginnt. Nimmt dem Versand die messbare Wirkung auf die Antwortzeit. |
| Schlüssel-Prüfsumme | `assets/crypto.js` | Erkennt, ob ein Inhaltsschlüssel zum Konto gehört. Der Server lernt dadurch nichts über den Schlüssel — er gewinnt nur die Fähigkeit, den einen Fehler zu erkennen, der alles kostet. |
| Schlüsselbindung | `assets/keyguard.js` | Bindet den zwischengespeicherten Inhaltsschlüssel an die Hülle, aus der er stammt, und lässt ihn nach derselben Frist ablaufen wie die Sitzung — **gleitend wie sie**: Jeder Treffer erneuert den Zeitstempel (R44, seit Web 12.9.0). Vorher war es eine feste Frist ab dem Entsperren, und genau daraus entstand der Entsperrdialog mitten in der Arbeit. **Muss vor `unlock.js` geladen werden.** |
| Fehlerantwort der Endpunkte | `db.php` | `json_fehler()` protokolliert den vollen Ausnahmetext und gibt nach außen nur eine achtstellige Kennung. `fehler_kennung()` für Stellen mit eigener Antwortform (`ingest.php`). |
| Zeitrechnung | `db.php` | **`TIMESTAMP` und `DATETIME` verhalten sich verschieden, und das ist bei jeder Zeitspalte mitzudenken.** `TIMESTAMP` rechnet MySQL beim Schreiben in UTC um und beim Lesen zurück — der gespeicherte Wert ist unabhängig von der Sitzungszone immer richtig (`pair_codes`, `devices.last_seen`/`created_at`, `users.created_at`, `missions.created_at`, `deleted_refs`). `DATETIME` speichert unverändert, was dasteht; dort entscheidet die Sitzungszone (`rate_limits`, `password_resets.expires_at`, sowie die Einsatz- und Papierkorbzeiten — Letztere werden aber über `local_to_utc()` bzw. `UTC_TIMESTAMP()` befüllt und waren nie zonenabhängig). |
| Zeitrechnung | `db.php` | Die Verbindung steht seit Web 4.5.2 ausdrücklich auf UTC (`SET time_zone = '+00:00'`). Ohne das käme die Zeitrechnung von `NOW()` aus einer Hoster-Einstellung, und `NOW()` und `UTC_TIMESTAMP()` liefen um den Zonenversatz auseinander. Der Unterschied im Code bleibt: `UTC_TIMESTAMP()` für den Papierkorb (90-Tage-Frist, `TRASH_DAYS`), `NOW()` für Kurzlebiges (Ratenschutz, Token, Kopplungscodes). Die **Anzeige** rechnet in PHP nach `$CFG['app']['timezone']` um. |
| Sitzungsende | `session_lib.php` | Eine Fassung für Abmelden, Ablauf, gelöschtes Konto **und** Passwortwechsel; räumt die Schlüssel im Browser und nennt den Grund. `session_verwerfen()` für Abrufe, die JSON erwarten. |
| E-Mail-Adressen | `server/email_lib.php` | Eine Fassung für Normalisierung (`email_normalisieren()`), Prüfung (`email_pruefen()`) und Dublettenerkennung (`ist_dublettenfehler()`). **Ohne Abhängigkeiten**, damit `install.php` sie vor der Ersteinrichtung laden kann. |
| Rollenprüfung | `auth_guard.php` | `ist_admin()` ist die einzige Stelle, an der die Frage gestellt wird; `require_admin()` und `ui.php` setzen darauf auf. |
| Maskierung | `assets/html.js` (`EdHtml.escape`) | Eine Fassung, auch in Attributpositionen sicher (fünf Zeichen statt drei). Seit Web 4.6.0 in einer eigenen Datei statt in `missiontable.js` — die wird nur von zwei Seiten geladen, gebraucht wird die Maskierung auf fünf. `EdMissionTable.escape`/`.esc` bleiben als Weiterleitung. **Nicht dasselbe** wie `xmlEscape()` in `export.js`: GPX ist XML mit eigenen Regeln. |
| Patientenanzeige | `assets/patient.js` | Eine Entschlüsselungsschleife statt fünf; unterscheidet sichtbar „keine Angaben" von „nicht lesbar". `entschluessleListe()` wird seit Web 4.6.0 von allen Aufrufern benutzt (Tages-, Zeitraum- und Suchansicht, Export, Import-Abgleich, Backup-Lauf) und schreibt je Einsatz `_pat` und `_patState`. |
| Migrationsschutz | `update.php` (`inhalt_zaehlen()`) | Destruktive Migrationen tragen `zerstoert` (Klartext, was verlorenginge) und optional `inhalt` (Spalten, deren Inhalt die Ausführung blockiert). Eine blockierte Migration hält die Kette **nicht** an — sie hat nichts getan, anders als ein Fehler. |
| Blockabfrage | `db.php` (`sql_in_bloecken()`) | Eine Abfrage je Tabelle statt einer je Datensatz, in Blöcken zu 1000 IDs. Benutzt von Export, Tagesansicht und Backup. Die Vorlage trägt `{IDS}` und ist **keine** Formatzeichenkette — ein Prozentzeichen im SQL bleibt ein Prozentzeichen. |
| Formatkennung des Chiffretexts | `assets/crypto.js` (`CHIFFRE_PRAEFIX`), `validate_lib.php` (`PAT_BLOB_RE`, `WRAP_RE`) | `edk1:` vor jedem Chiffretext. Schreiben immer, Lesen großzügig (keine Kennung = erste Fassung), unbekannte Kennung wird als solche gemeldet. Betrifft `pat_blob` **und** beide Schlüsselhüllen — sie kommen aus derselben Funktion. |
| Rundenzahl der Ableitung | `db.php` (`KDF_ITER_ZIEL`, `KDF_ITER_LISTE`), `users.kdf_iter` | Je Konto gespeichert und gelesen, nicht angenommen. `deriveKeys()` verlangt sie als **Pflichtparameter ohne Vorgabewert** — ein Vorgabewert ließe jede vergessene Aufrufstelle stillschweigend mit dem alten Wert rechnen, und das fiele erst bei der nächsten Anhebung auf. Der Salz-Endpunkt nennt jeder Adresse dieselbe **Liste**, damit er nicht verrät, welche Konten es gibt. **Beim Anheben von `KDF_ITER_ZIEL` muss der bisherige Wert in `KDF_ITER_LISTE` stehen bleiben**, sonst kann sich kein Bestandskonto mehr anmelden; die Wartungsseite meldet diesen Zustand unter „Schlüsselableitung". |
| Wiederherstellungsschlüssel | `assets/crypto.js` (`pruefeRecoveryCode()`) | Prüft Länge und Alphabet **vor** der Ableitung und unterscheidet Tippfehler von falschem Zettel. Ohne die Prüfung entsteht aus einer krummen Eingabe klaglos ein falscher Schlüssel, und die Meldung lautet in beiden Fällen „passt nicht". |
| Passwortgüte | `assets/pwquality.js` | Mindestlänge im Skript statt nur als HTML-Attribut, Stärkeanzeige, Abgleich gegen häufige Passwörter. Seit Web 4.7.0 an allen fünf Stellen eingebunden: Erstvergabe, Zurücksetzen, Passwortwechsel, Backup-Passwort, Export-Archivpasswort. Vorher lag der Baustein ungenutzt neben `minlength`-Attributen. |
| Seitenhülle | `ui.php` (`ui_seite_start()`, `ui_seite_ende()`) | Ab Web 7.1.0. Doctype, `<head>`, Eröffnung und Abschluss des `<body>` — vorher 28-mal von Hand, mit zwei Schreibweisen des Viewports und zwei Titeltrennern. Leaflet-CSS nur auf Kartenseiten und **vor** `style.css`, damit eigene Regeln die des Kartenwerks überschreiben. **Ohne Abhängigkeit auf oberster Ebene**, damit `install.php` sie vor der Ersteinrichtung laden kann; `asset()`, `e()` und `favicon_tags()` werden über `ui_asset()`/`ui_e()`/`ui_favicon()` nur benutzt, wo es sie gibt. **`install.php` lädt sie seit Web 9.10.1 am Dateianfang** — vorher stand das `require_once` in `render_page()` selbst, und weil die Aufrufer ihr Argument mit `ui_meldung_markup()` und `ui_knopf()` bauen (PHP wertet Argumente vor dem Aufruf aus), endete jeder Zweig in „Call to undefined function". Der Einrichter war damit seit Web 9.1.0 unbenutzbar (F-P3-AR). |
| Krypto-Rüstzeug der Seiten | `ui.php` (`ui_krypto_bootstrap()`) | Ab Web 7.2.0. Die Verweise auf `crypto.js`, `keyguard.js` und `unlock.js` samt `PAT_WRAP`, `KDF_SALT`, `KDF_ITER` und `KDF_ITER_ZIEL`; wahlweise `PAT_KEY_CHECK`, `CSRF` und `pwquality.js`. Vorher acht Blöcke in sieben Dateien — mit zwei Namen für dieselbe Hülle. Ein **zweiter Aufruf im selben Seitenaufbau gibt nichts aus und schreibt ins Fehlerlog**: Zwei Einbindungen von `crypto.js` wären ein `SyntaxError`, der das ganze zweite Skript verwirft. |
| Meldungszeile | `ui.php` (`ui_meldung()`) | Ab Web 7.2.0. Hinweis- und Fehlerzeile über dem Inhalt, vorher 21-mal in 13 Dateien. Der Ton (`info`/`ok`) ist Parameter, weil der Bestand beide kennt: `ok` meldet einen Vollzug (Stammdaten, Nachbearbeitung). |
| Abbruchseite | `ui.php` (`ui_abbruch()`) | Ab Web 7.2.0. Statt `exit('… nicht gefunden.')` eine richtige Seite mit Kopfleiste und Rückweg — 16 Stellen, darunter `require_admin()` und `csrf_check()` in `auth_guard.php`. Wortlaut und HTTP-Code unverändert; der API-Zweig von `require_admin()` antwortet weiter mit JSON. |
| Knopf | `ui.php` (`ui_knopf()`), `assets/style.css` (`.knopf` mit `-primaer/-neutral/-gefahr/-leise/-symbol`) | **Seit Web 9.0.0 (P3/O1) eine Höhe: 44 px**, mobil wie am Schreibtisch, auch für Zeilenaktionen — es gibt keine Kompaktvariante, was kleiner ist, ist kein Knopf, sondern ein Link mit Symbol (E-P3-22). Vier Arten nach **Bedeutung**, nicht nach Aussehen: `primaer` (die eine Haupthandlung), `neutral` (alles Übrige, auch „Bearbeiten"), `gefahr` (Löschen), `leise` (Abbrechen, Nebenwege). Die Vorgängerfamilie `.btn-primary/-danger/-yellow/-red/-plain/-edit` ist mit O11 vollständig verschwunden; ihr letzter Rest war der Export-Knopf in `import.php`, der damit seit Web 9.0.0 ungestaltet war (F-P3-BA). **Im Aktionsblatt heißen dieselben Arten anders** (`blatt-gefahr`, `blatt-anlegen`) — `ui_zeilenaktionen()` wählt danach, wo der Knopf steht; wer das übersieht, bekommt ein „Löschen", das nicht rot ist (F-P3-AX). |
| Boolesche Freitextsuche | `assets/suchtext.js` (`EdSuchtext.pruefer()`, `.woerter()`, `.hervor()`) | Ab Web 7.0.0. Zerlegt eine Sucheingabe in ein Prädikat über den Heuhaufen: UND / ODER / NICHT, Klammern, Phrasen. Ohne Operator verhält sie sich wie die alte Wortliste. Scheitert **nie** an einer Eingabe — die Trefferliste rechnet bei jedem Tastendruck, also ist eine halbfertige Eingabe der Normalfall. Ohne Kenntnis der Seite und darum ohne die Seite prüfbar. Seit Web 9.5.0 dazu `woerter()` (die positiven Literale einer Eingabe) und `hervor()` (setzt `<mark>` in **bereits maskierten** Text) für die Trefferhervorhebung. |
| Alter mit Einheit | `assets/patient.js` (`EdPat.alterText()`) | Ab Web 7.0.0. Unter einem Monat Tage, unter zwei Jahren Monate, darüber Jahre. Bei einem Säugling ist „0" keine Auskunft. Grundlage ist das Geburtsdatum; aus einem von Hand eingetragenen Alter lässt sich nur „Jahre" ableiten. |

**Grenzen des verschlüsselten Patientenblocks** (`PAT_BLOB_MIN`/`PAT_BLOB_MAX`
in `validate_lib.php`): 40 bis 60000 Zeichen, für alle vier Schreibwege
dieselben.

* Untergrenze **hergeleitet, nicht geschätzt**: AES-256-GCM legt 12 Byte
  Zufallswert davor und hängt 16 Byte Prüfwert an — auch bei leerem Klartext
  sind das 28 Byte, base64 also 40 Zeichen. Kürzer kann ein gültiger Block
  nicht sein.
* Obergrenze: 60000 Zeichen = 60000 Byte (base64 ist ASCII); die Spalte fasst
  65535 Byte, also 5535 Byte Luft, entsprechend rund 44972 Byte Klartext.
* Die Grenze bleibt bewusst erhalten. Ohne sie entscheidet die Datenbank, und
  ihre Entscheidung ist entweder ein Abbruch oder stilles Abschneiden — ein
  abgeschnittener Chiffretext ist **dauerhaft** unlesbar.

**Mehrfache Einträge derselben Phasennummer sind ausdrücklich erlaubt.** Eine
erneut gesetzte Phase ist eine Korrektur und damit eine Information, die
erhalten bleibt (so auch der JSON-Vertrag). Die Mengenbegrenzung
(`LIMIT_PHASEN`) ist deshalb bewusst hoch angesetzt und darf nicht als
Überlaufschutz für eine Entdoppelung herhalten.

**Warum die Passwortgüte nur im Browser geprüft werden kann.** Der Server
sieht das Passwort nie — er bekommt ausschließlich das daraus abgeleitete
Auth-Token. Das ist der Kern des Verfahrens, nicht eine Nachlässigkeit, und
die Kehrseite ist, dass er die Stärke prinzipiell nicht prüfen kann. Der
Schutz gegen einen Angreifer mit Zugriff auf die Ablaufumgebung (Hoster,
Datenbank, Protokolle) hängt damit allein an der Passwortwahl der Person. Das
ist eine bewusste Entscheidung und gehört genau so dokumentiert.

### 4.99a Demo-Konto (ab Web 7.3.0)

Ein Konto zum Ausprobieren: erfundene Daten, öffentliche Zugangsdaten,
Änderungen erwünscht — und alle 30 Minuten zurück auf den Ausgangsstand.

**Die Ausnahme, die dafür gemacht wird.** Das Projekt verspricht
Ende-zu-Ende-Verschlüsselung: Der Server sieht die geschützten Angaben nie im
Klartext, und das Schlüsselmaterial hängt am Passwort. Für dieses **eine**
Konto gilt das nicht — sein Schlüsselmaterial liegt in der Fixture auf dem
Server, damit ein Reset die Chiffretexte wieder lesbar macht.

Vertretbar ist das nur unter vier Bedingungen, und alle vier werden erzwungen,
nicht bloß zugesichert:

1. Das Konto trägt ausschließlich erfundene Daten.
2. Es hat die Rolle `user`; `demo_lib.php` schreibt sie bei jedem Anlegen und
   jedem Reset fest hin.
3. Jede Funktion arbeitet auf der Kennung aus `app_state.demo_user_id` und
   nimmt **keine** von außen entgegen — sie kann kein anderes Konto treffen.
4. Zugangsdaten und Geräteschlüssel sind ohnehin öffentlich. Es gibt nichts zu
   schützen, was nicht schon offenläge.

#### Die Fixture

`server/demo/fixture.json.gz`, erzeugt von
`tools/referenzdatensatz/fixture/erzeugen.php`. Drei Teile:

| Teil | Inhalt |
|---|---|
| `konto` | E-Mail, `password_hash`, `kdf_salt`, `kdf_iter`, `pat_wrap_pw`, `pat_wrap_rc`, `pat_key_check`, `account_key` |
| `geraete` | `device_id`, `api_key_hash`, `label` — **ohne** das virtuelle Gerät „Manuelle Einträge" (s. u.) |
| `daten` | inneres Backup-JSON — `pat_blob` als **Chiffretext**, Papierkorb eingeschlossen |

**Format 2 seit Web 8.0.0**: Der vierte Teil, `nachlauf`, ist entfallen
(unten). Pflicht sind weiterhin nur `konto` und `daten`; `demo_fixture_laden()`
bleibt tolerant, eine Fixture der Version 1 lässt sich also weiterhin
einspielen — ihr `nachlauf`-Block wird schlicht nicht mehr gelesen.

**Warum sie nicht aus einer `.edbak` kommen kann.** Die Backup-Datei trägt
die geschützten Angaben im **Klartext** — der Browser entschlüsselt vor dem
Versiegeln, damit sich ein Backup in jedes Konto einspielen lässt. Für die
Fixture wäre das genau falsch: Sie soll den Chiffretext unverändert mitführen
und daneben das Schlüsselmaterial, mit dem er lesbar ist. Erst dadurch kann
der Server das Konto **ohne jede Entschlüsselung** zurücksetzen — und erst
dadurch ist der Reset schnell genug, um bei jeder Anfrage zu laufen.

Die Quelle ist deshalb `edbak_build()`: dieselbe Funktion, die auch das
Backup aufbaut, aber serverseitig — dort steht `pat_blob` noch als
Chiffretext. Genau die Form, die `edbak_restore()` als Spalte wieder annimmt.
Der Erzeuger bricht ab, wenn er Klartext findet.

Gepackt abgelegt: roh rund 2,4 MB, im Wesentlichen 55 861 Spurpunkte als
JSON-Zahlen. Gepackt sind es rund 745 KB, und die Datei geht bei jedem Deploy
über FTPS mit.

#### Kein zweiter Einspielweg

Der Bestand wird über `edbak_restore()` eingespielt — dieselbe Routine wie bei
der Wiederherstellung eines Backups, mit derselben Prüfung. Ein eigener Weg
hätte eigene Fehler, und ausgerechnet der Weg, der am häufigsten läuft, wäre
der ungeprüftere.

Eine Erweiterung war dafür nötig, in `backup_lib.php`:

- **`edbak_restore()` ist verschachtelungsfähig.** Sie öffnet ihre Transaktion
  nur, wenn noch keine läuft. Der Demo-Reset muss mehr in dieselbe Klammer
  nehmen: Kontomaterial, Geräte und Bestand. Zerfiele das in mehrere
  Transaktionen, könnte ein Fehler in der Mitte ein Konto mit halbem Bestand
  hinterlassen — und der Reset läuft unbeaufsichtigt.

Eine zweite gab es bis Web 7.3.1: `edbak_build($userId, $mitPapierkorb)`. Sie
ist entfallen, weil der Papierkorb seit Web 8.0.0 ohnehin in jedem Backup
steht (`docs/Backup-Format.md` 2).

#### Der Papierkorb-Nachlauf ist entfallen (Web 8.0.0)

**Was es war.** Das Einspielen wertete `deleted_at` nicht aus; alle Einträge
kamen als aktive zurück. Danach legte ein Drehbuch (`demo_nachlauf()`,
Fixture-Block `nachlauf`) benannte Einsätze und Diensttage über die regulären
Löschwege wieder in den Papierkorb. Es musste **nach** dem Commit laufen, weil
`trash_delete_*()` je eine eigene Transaktion öffnen — der Reset zerfiel damit
in zwei Schritte, von denen der zweite fehlschlagen konnte.

**Warum es weg ist.** Seit Nutzlast 7 führt das Backup den Papierkorb, und
`edbak_restore()` bringt ihn als Papierkorb zurück. Der Reset ist wieder
**ein** Vorgang in **einer** Transaktion; die Zahlen für den Bericht kommen aus
`stats.papierkorb` der Einspielroutine. Die 90-Tage-Frist stempelt jeder Reset
frisch, weil beim Einspielen ohnehin der Einspielzeitpunkt gesetzt wird — das
Demo-Konto hält seinen Papierkorb also von selbst am Leben.

**Was das für eine alte Fixture bedeutet.** Sie bleibt lauffähig: Ihre `daten`
tragen `deleted_at` bereits (sie wurde mit dem damaligen Flag erzeugt), der
neue Rückweg macht daraus Papierkorbeinträge, und ihr `nachlauf`-Block wird
nicht mehr gelesen.

#### Der Reset

Anfragegetrieben nach dem Muster der Tageswartung (`run_cleanup_if_due()`),
mit einem Unterschied in der Reihenfolge: Hier wird zurückgesetzt, **bevor**
die Seite ihre Daten liest. Wer nach längerer Ruhe kommt, sieht den
Ausgangsstand und nicht die Hinterlassenschaft der letzten Besucherin.

Zwei Auslösepunkte:

| Stelle | wann |
|---|---|
| `auth_guard.php` | jede Web-Anfrage des Demo-Kontos |
| `ingest.php` | jeder Upload eines Demo-Geräts, **nach** der Geräteprüfung |

Die Prüfung in `ingest.php` steht bewusst hinter der Authentifizierung: Sonst
wäre die Rücksetzung ein Hebel für jeden, der die Adresse kennt.

Die Marke (`app_state.demo_letzter_reset`) wird **vor** der Arbeit gesetzt —
dasselbe Vorgehen wie bei der Tageswartung: Zwei gleichzeitige Anfragen sollen
nicht beide zurücksetzen. Höchstdrift 30 Minuten relativ zu jeder Aktivität;
ein Zeitdienst wird nicht vorausgesetzt.

Der Reset überschreibt auch **Konto- und Schlüsselmaterial** und zählt
`session_epoch` hoch. Damit bliebe selbst eine unerwartet gelungene Änderung
der Konto-Identität folgenlos — die zweite Linie hinter den Sperren unten.

#### Gesperrt ist ausschließlich die Identität

| Endpunkt | Verhalten |
|---|---|
| `einstellungen.php` (`profile`, `password`) | freundlicher Hinweis, keine Änderung |
| `api/kdf_upgrade.php` | stiller Erfolg (`uebersprungen: demo`) |
| `reset_request.php` | still abgewiesen — kein Link, keine E-Mail |

Das KDF-Upgrade antwortet mit Erfolg statt mit Fehler, weil der Browser es von
sich aus nach der Anmeldung aufruft: Ein Fehler stünde dort als Störung, wo es
keine gibt.

`reset_request.php` weist **still** ab. Die Antwort dieser Seite ist für jede
Adresse dieselbe; eine Sondermeldung für das Demo-Konto wäre die einzige
Stelle, an der die Seite verriete, welche Adressen es gibt.

Alles Übrige bleibt offen — ausdrücklich auch Geräteverwaltung, Kopplung und
Uploads. Die Anwendung soll ausprobierbar sein, das ist der Zweck.

**Warum überhaupt sperren, wenn der Reset ohnehin alles zurückholt?** Weil
zwischen zwei Rücksetzungen bis zu dreißig Minuten liegen. Wer in dieser Zeit
E-Mail oder Passwort ändert, sperrt die nächste Besucherin aus — und die
findet ein Konto vor, dessen öffentliche Zugangsdaten nicht mehr stimmen, ohne
zu erfahren warum.

#### Mengenbremse

Zwei neue Töpfe in `ratelimit_lib.php`, die **anders zählen** als die vier
bestehenden: nicht Fehlversuche, sondern **gelungene** Anmeldungen.

| Topf | Merkmal | Grenze | Fenster |
|---|---|---|---|
| `demo` | IP-Adresse | 20 | 1 Stunde |
| `demog` | global | 300 | 1 Stunde |

Ein Fehlversuchszähler liefe hier nie an — die Zugangsdaten sind öffentlich,
es gibt nichts zu erraten. Begrenzt werden soll die **Menge der Nutzung**: Das
Konto ist zum Ausprobieren da, nicht als Rechenzeit für Fremde.

Zwei Töpfe, weil die Grenzen verschieden sind und `RATE_GRENZEN` am Topf
hängt, nicht am Merkmal. Die Prüfung sitzt in `login.php` **vor** der teuren
Ableitung, wie jede Bremse dort.

#### Banner

`ui_demo_banner()` in `ui.php`, unmittelbar unter der Kopfleiste, auf jeder
Seite, nicht wegklickbar. Es nennt vier Dinge, und alle vier sind nötig: dass
die Daten erfunden sind, dass Ausprobieren erwünscht ist, dass alles
regelmäßig verworfen wird, und dass hier keine echten Daten hineingehören.

Ein Hinweis, den man einmal schließt, ist beim zweiten Besuch nicht mehr da —
und genau dann wäre er nötig.

## 5. Uhr-App (Monkey C) — Modulstruktur

| Datei | Verantwortung |
|---|---|
| `NAdokuApp.mc` | Einstieg; Restore-Kette bei Neustart (Model → Track → Cpr → Sync). Hieß bis Uhr 2.0.0 `HemsApp.mc` |
| `Model.mc` | Dienst-Klammer, Phasenlogik, Einsatz-/Segment-Lebenszyklus, Rea-Sitzungen, Persistenz (`state`) |
| `Track.mc` | GPS (15 m/10 s/1 s-Ausdünnung), Distanz/Anstieg, Anzeige-Polylinie (Cap 1000, Dichte-Halbierung), **Flash-Chunks à 200 Punkte**; `restore()` lädt Teil-Chunks zurück in den Puffer (verlustfrei) |
| `Cpr.mc` | Rea-Timer app-weit (1-s-Tick), 2:00-Zyklus, Ereignisse, **persistenter Zustand** (übersteht Neustart); drei Zustände: aus / laufend / pausiert |
| `Uploader.mc` | Job-Queue (fertige Einsätze → Segmente → aktive), Chunking ≤ 500, `next_seq`-Bestätigung, Purge inkl. Marken; `hasServer()`/`hasCredentials()` |
| `Input.mc` | Eingabemodell: `ActionDelegate` übersetzt Tasten, Wischgesten und Langdrücke einmal zentral in Aktionen (s. Abschnitt 5.1) |
| `DeviceProfile.mc` | je Profil eine eigene Fassung in `source-tasten5/` bzw. `source-tasten3/`; liefert `HAS_UP_DOWN` und die Bedienhinweise |
| `Ui.mc` | Geometrie relativ zur Displayhöhe (`s()` liefert bei 260 exakt den Ausgangswert), Markenfarben, Rea-Marker |
| `Nav.mc` | Pager: Uhr → Tempo → Statistik → Sync → Rea |
| `StartView.mc` | Startbildschirm „Dienst beginnen"; Hinweise zu Server-Adresse und Kopplung |
| `ClockView/SpeedView/StatsView/SyncView/CprView.mc` | Oberflächen + Delegates; erben von `ActionDelegate` und beschreiben nur noch die Aktionen |
| `SyncView.mc` | Sync-Status (Backlog = nur abgeschlossene Pakete), App-Version, Kopplung per START-Halten |
| `Pair.mc` | Kopplungscode-Eingabe → tauscht Code gegen Geräte-Zugang (`Storage 'cred'`) |
| `Const.mc` / `Util.mc` | `APP_VERSION`, Labels, Tuning-Werte; ISO-UTC, lokale Anzeige, Vibration |

Rückruf-Muster: `method()` existiert nur auf Objekten → kleine Träger-Klassen
(`TrackCb`, `CprCb`, `UploaderCb`) reichen Callbacks an die Module weiter.

> **Kartenseite entfernt (1.3.5):** `MapPage.mc` funktionierte am Gerät nicht
> zuverlässig und wurde gelöscht. Eine künftige Kartenansicht wird neu
> aufgebaut; die alte Fassung liegt in der Git-Historie.

> **Vor jeder Änderung an einer Oberfläche:** `Uhr-Layout.md` lesen. Dort
> stehen die Regeln zu Schriften, runden Displays und vertikalen Blöcken —
> jede davon, weil sie einmal verletzt wurde und der Fehler erst im Simulator
> aufgefallen ist.

**Was der Typprüfer verlangt (Stand 1.8.2).** Drei Eigenheiten kosten sonst
jedes Mal aufs Neue Zeit:

- **Die Null-Prüfung greift nur über lokale Variablen.** `if (mission == null)
  { return; }` überzeugt den Prüfer nicht, wenn danach `mission[...]` steht —
  bei einem Modul-Feld verfolgt er den Fluss nicht. Den Wert zuerst in eine
  lokale Variable holen, dann prüfen, dann benutzen. Dasselbe gilt für
  `info.position` und jedes andere Feld.
- **`Storage.getValue()` liefert einen Sammeltyp** über alles Speicherbare, von
  `BitmapResource` bis `ScanResult`. Jede Zuweisung daraus braucht eine
  Zusicherung — sinnvollerweise dieselbe Struktur, die das zugehörige `save()`
  geschrieben hat.
- **Arrays brauchen einen Elementtyp.** `Lang.Array` allein lässt offen, ob
  `x[i][2]` erlaubt ist. Für die Tupellisten `Lang.Array<Lang.Array>`, für die
  Warteschlangen `Lang.Array<Lang.Dictionary>`.

Beim Zusichern gilt: **lieber keine Angabe als eine falsche.** Der Punktpuffer
in `Track` führt Breite und Länge als `Double`, die Höhe als `Float` (die
fehlen darf) und den Zeitstempel als `Number` — `Lang.Array<Lang.Number>` wäre
bequem und unwahr; richtig ist `Lang.Array<Lang.Numeric or Null>`.
Lokale Variablen lassen sich übrigens **nicht** annotieren
(„Local variable types are inferred"); die Zusicherung gehört dann an die
Zuweisung.

### 5.1 Tastenbelegung je Geräteprofil

Die Zielgeräte unterscheiden sich in zwei Achsen: **fünf oder drei Tasten**
und **mit oder ohne Touch**. Daraus ergeben sich die Belegungen unten. Was das
einzelne Gerät technisch hergibt, steht gemessen in `Geraete-Eingabe.md` —
dieser Abschnitt beschreibt, was die App daraus macht.

**Profil A — fünf Tasten, kein Touch** (`fenix6pro`, `fr945`)

| Eingabe | Hauptseiten | Reanimationsseite | Listen und Menüs |
|---|---|---|---|
| kurz UP | Seite zurück | Seite zurück | Eintrag hoch |
| kurz DOWN | Seite vor | Seite vor | Eintrag runter |
| kurz START | — | Rea beginnen bzw. Untermenü | Eintrag wählen |
| lang START | Schnellmenü; Sync-Seite: Kopplung | Countdown neu starten | — |
| lang UP | — | Adrenalin | — |
| lang DOWN | — | Rhythmuskontrolle | — |
| BACK | App verlassen (Abfrage) | zurück zur Uhrseite | Liste schließen |
| START + beliebige Taste | Tastensperre, wirkungslos | Tastensperre, wirkungslos | — |

LIGHT wird auf beiden Geräten nicht zugestellt und ist unbelegt.

**Profil B — drei Tasten, Touch** (`venu3s`)

Nur zwei der drei Tasten sind für Connect-IQ-Apps erreichbar; die mittlere ist
systemseitig belegt. Ohne Touch ist das Gerät deshalb nicht bedienbar.

| Eingabe | Wirkung |
|---|---|
| kurz Action | wie kurz START in Profil A |
| lang Action | wie lang START in Profil A |
| lang Zurück | wie lang START in Profil A (zweiter Weg, s. u.) |
| kurz Zurück | wie BACK in Profil A |
| Wischen hoch / runter | Seite vor / zurück, in Listen Eintrag runter / hoch |
| Wischen rechts | wie BACK |
| Wischen links | unbelegt |
| Tippen | auf Hauptseiten wirkungslos; in Menüs kann es den markierten Eintrag wählen |

Ersatz für fehlende Tasten:

- **UP/DOWN** werden durch Wischen ersetzt. Das System leitet Wischgesten
  selbst in `onNextPage`/`onPreviousPage` um; eigener Wisch-Code ist weder
  nötig noch möglich (das Roh-Ereignis wird gar nicht erst zugestellt).
- **Lang UP und lang DOWN** haben auf der Venu keine Entsprechung. Adrenalin
  und Rhythmuskontrolle sind dort nur über das Rea-Untermenü erreichbar —
  ein Bedienschritt mehr.
- **Der lange Druck liegt bewusst doppelt**, auf Action *und* Zurück. Grund:
  Das Handbuch der Venu 3 nennt ein Steuerungsmenü nach zwei Sekunden Halten
  der Action-Taste. Im Simulator trat es nicht auf, auf echter Hardware ist es
  ungeprüft. Fängt die Uhr den langen Action-Druck ab, bleibt die App über den
  langen Zurück-Druck vollständig bedienbar. Beide Wege sind gegeneinander
  entprellt.
- **Die Tastensperre** (START + zweite Taste) entfällt auf der Venu, weil nur
  eine Taste sinnvoll erreichbar ist.

**Profil C — fünf Tasten mit Touch** (Fenix 7 und neuer) ist vorbereitet, aber
nicht ausgeliefert. Dafür existiert die App-Einstellung `touchEnabled`
(Vorgabe: an), mit der sich die Touchbedienung abschalten lässt — auf
Profil B hat sie keine Wirkung, weil das Gerät sonst unbedienbar wäre.

### 5.1a Pausenzustand der Reanimation

`Cpr.mc` kennt drei Zustände: `active=false` (keine Rea), `active=true,
paused=false` (läuft) und `active=true, paused=true` (angehalten). Die Pause
entsteht ausschließlich über „Rea BEENDEN" im Untermenü: Der 2:00-Countdown
steht, die Übersicht öffnet sich, und dort wird fortgesetzt oder endgültig
geschlossen.

Zwei Punkte sind bewusst so gebaut:

- **Die Gesamtdauer läuft während der Pause weiter.** Sie ist die tatsächlich
  verstrichene Reanimationszeit; ein Anhalten würde sie zu kurz dokumentieren.
  Nur der Countdown steht.
- **Die Pause wird nicht übertragen.** Sie ist ein reiner Bedienzustand, im
  JSON-Vertrag gibt es sie nicht. Persistiert wird sie trotzdem (`Storage`
  `"cpr"`, Schlüssel `"p"`), damit ein Neustart der App keine Entscheidung
  erzwingt oder unterschlägt.

`stopRecording()` schließt die Sitzung unabhängig davon, ob sie lief oder
pausiert war — Einsatzabschluss und Dienstende brauchen deshalb keine
Sonderbehandlung.

### 5.1b Geräteprofile im Jungle

`monkey.jungle` weist jedem Gerät seinen Quell- und Ressourcenpfad **vollständig**
zu:

```
fenix6pro.sourcePath   = source;source-tasten5
venu3s.sourcePath      = source;source-tasten3
venu3s.resourcePath    = resources;resources-icon70;resources-marke105
```

**Nicht** `$(<gerät>.sourcePath);source-tasten5` schreiben. Der Selbstbezug
fällt auf eine Vorgabe zurück, die alle `source*`-Ordner einsammelt; dann
landen beide `DeviceProfile.mc` im Build und der Compiler meldet
`Redefinition of 'HAS_UP_DOWN'`. Dasselbe gilt für `resourcePath` — sonst
bekäme die Fenix das Symbol der Venu 3s.

`base.sourcePath` steht auf dem Fünf-Tasten-Profil: Ein Gerät, das jemand ins
Manifest einträgt ohne hier eine Zeile zu ergänzen, baut damit gegen das
konservativere Profil.

**Die Ressourcenordner sind nach Größe geschnitten, nicht nach Gerät:**

| Ordner | Inhalt |
|---|---|
| `resources` | Grundordner — Launcher-Symbol 40 px, Bildmarke 73 px |
| `resources-icon<N>` | **nur** das Launcher-Symbol in N Pixeln (35, 36, 54, 56, 60, 61, 65, 70) |
| `resources-marke<K>` | **nur** die Bildmarke in einer Kachel von K Pixeln |

Getrennt, weil die beiden Größen nicht miteinander laufen: Ein Gerät mit
60-px-Symbol gibt es bei 360, 390, 416 und 454 Pixeln Displayhöhe. Die
Ordnerzahl gewinnt dabei nichts — getrennt 8 + 3, zusammengelegt ebenfalls 11 —,
wohl aber die Pflege: Zusammengelegt läge dieselbe 101er Kachel in fünf
Ordnern, und eine verschobene Stufengrenze schnitte den ganzen Satz neu.

Die Symbolgröße ist eine **Vorgabe des Geräts** (`launcherIcon.width` in
seiner `compiler.json`), keine Wahl; fehlt sie, skaliert `monkeyc` und meldet
es als Warnung.

Die Kachelgröße der Bildmarke kommt dagegen aus einer Entscheidung: vier
Stufen (60, 73, 101, 118) über die zehn vorkommenden Displayhöhen, Zielwert
27 % — das Verhältnis 70/260 des Bezugsgeräts, dem `Ui.s()` ohnehin jede Länge
folgt. Ein Bitmap kann `Ui.s()` nicht folgen (`dc.drawBitmap` zeichnet 1:1),
vorgerasterte Stufen holen das nach. Alle 99 Geräte liegen damit zwischen 25,0
und 28,8 %; vor Uhr 1.10.3 reichte die Spanne von 15 % bis 34 %, weil die
Zuordnung an der Symbolgröße hing. Begründung der Stufenzahl:
`tools/uhr-bilder/LIESMICH.md`.

Bilder erzeugen: `tools/uhr-bilder/erzeugen.sh`. Die passenden Jungle-Zeilen:
`tools/uhr-pruefstand/geraeteklassen.py --bloecke`.

### 5.2 Neue Zielgeräte prüfen — `tools/eingabe-probe`

Bevor ein Gerät in `watch/manifest.xml` aufgenommen wird, muss gemessen sein,
welche Tasten überhaupt bei der App ankommen, welche Behaviors das System
daraus ableitet und ob die Langdruck-Erkennung dort funktioniert. Datenblätter
reichen dafür nicht: Auf der Venu 3s ist eine der drei Tasten systemseitig
belegt und für Connect-IQ-Apps unsichtbar — das steht in keiner Übersicht.

Dafür liegt unter `tools/eingabe-probe/` ein eigenständiges Connect-IQ-Projekt.
Es wird nie ausgeliefert, hat eine eigene UUID und keine Berechtigungen. Es
protokolliert jedes Eingabeereignis mit Millisekunden-Stempel auf Konsole und
Display und startet bei jedem Tastendruck einen 1000-ms-Timer — denselben
Mechanismus, den die App über `Const.LONG_PRESS_MS` für Langdrücke benutzt.
Steht `HALTE-TIMER` im Protokoll vor dem `KeyReleased`, sind Langdrücke
möglich; steht es danach oder gar nicht, sind sie es nicht.

Messfolge und Auswertung: `tools/eingabe-probe/LIESMICH.md`.
Ergebnisse gehören nach `Geraete-Eingabe.md`.

**Build:** VS Code + Monkey-C-Erweiterung + Connect-IQ-SDK + JDK;
Entwickler-Schlüssel via „Generate a Developer Key". Ziele `fenix6pro`, `fr945`
und `venu3s` (s. Abschnitt 5.1 und `Geraete-Eingabe.md`),
Debug-Build; Sideload: `.prg` nach `GARMIN/Apps/`. Server-Adresse, Geräte-ID und
API-Schlüssel sind **App-Einstellungen ohne Vorgabewert** (Garmin Connect); die
Zugangsdaten füllt die **Kopplung per Code** (Web: Einstellungen → Geräte; Uhr:
Sync-Seite → START halten). Connect IQ bewahrt diese Einstellungen an der
App-Kennung auf — sie überstehen jedes Neukompilieren, gehen aber bei einem
Wechsel der Kennung verloren. `Const.APP_VERSION` bei Releases mitziehen
(Anzeige Sync-Seite). Die **App-Kennung im `manifest.xml` ist noch ein
Platzhalter** — für eine Store-Veröffentlichung muss eine eindeutige erzeugt
werden.

**Dienstende (Uhr):** „Einsatztag beenden" schließt Rea und Dienst, setzt den
Arbeitszustand zurück (Zähler, Phase, Tag) und beendet die App per
`System.exit()`; die Upload-Warteschlange bleibt erhalten. Der Wechsel zur
Sende-Ansicht läuft verzögert (Modul `EndDay`), weil ein direkter
`switchToView()` aus `ConfirmationDelegate.onResponse()` von der sich
schließenden Bestätigung wieder entfernt würde.

### 5.2b Uhr-App ohne Arbeitsplatz prüfen — `tools/uhr-pruefstand`

Der Build oben setzt einen eingerichteten Arbeitsplatz voraus. Damit war jede
Änderung am Monkey-C-Code aus einer Wegwerf-Umgebung heraus blind: kein
Kompilat, kein Simulatorlauf, nur Lesen. `tools/uhr-pruefstand/pruefstand.sh`
schließt diese Lücke — es baut SDK und Simulator auf einer nackten
Linux-Rechner auf und startet die App unter einem virtuellen X-Server.

Drei der vier nötigen Teile beschafft das Skript allein: das SDK von
`developer.garmin.com` (keine Anmeldung nötig), die Systembibliotheken aus den
Ubuntu-Quellen und den Entwickler-Schlüssel per `openssl` — für den Simulator
genügt jeder gültige Schlüssel, der des Arbeitsplatzes gehört nicht dorthin.

Der vierte Teil ist der Haken. **Gerätedateien (`Devices/`) und Zeichensätze
(`Fonts/`) liefert nur der SDK-Manager aus**, und der ist eine
Fensteranwendung mit Garmin-Anmeldung — auf einem Rechner ohne Bildschirm
nicht zu bedienen. Sie werden deshalb von einer selbst bereitgestellten Quelle
geholt, deren Adresse in `CIQ_GERAETE_URL` steht und bewusst **nicht** im
Repositorium: Es ist öffentlich, und die Dateien gehören Garmin. Wer den
Prüfstand neu aufsetzt, muss die Adresse also erfragen; die Quelle braucht eine
eingeschaltete Verzeichnisauflistung, weil das Skript mit `wget -r` an den Baum
geht. `aufbau` holt nur die drei Zielgeräte — für Stufe I und
`geraeteklassen.py` braucht es `CIQ_ZIELE=alle`. Fehlen die
Zeichensätze, übersetzt die App zwar, bricht aber beim ersten Zeichnen mit
`Invalid Font Specified` ab — der Fehler zeigt auf die eigene Zeile, liegt
aber an der Umgebung.

Zwei Eigenheiten sind der Erwähnung wert. Der Simulator ist gegen
`webkit2gtk 4.0` gebunden, das es in Ubuntu 24.04 nicht mehr gibt; das Skript
holt die 22.04-Stände und legt sie **neben** den Simulator, statt am System zu
drehen. Und Bedienung wird als X-Ereignis zugestellt — Tastendruck, Tipp,
Langdruck und Wischgeste kommen so bis in die App durch, gemessen mit der
Eingabe-Probe (s. `Geraete-Eingabe.md`).

Eine dritte Eigenheit ist beim Messen teuer geworden. Der Simulator führt zwei
Ablagen, die jedes neue Kompilat überleben — die App-Einstellungen und
`Application.Storage` — und **ihre Dateinamen sagen nichts darüber aus, zu
welcher App sie gehören**: Am 02.09.2026 legte ein und derselbe Lauf
`V2.SET` und `UUID_ALT.DAT` an, beide nach früher geladenen Kompilaten benannt.
Dagegen helfen `einstellungen-leeren` und `speicher-leeren`, die je eine Ablage
**ganz** räumen. Wer stattdessen die Datei löscht, deren Namen er erwartet,
trifft womöglich nichts und misst dann den Zustand des vorigen Laufs.

Die Grenzen bleiben die des Simulators, unverändert: keine echte Hardware,
keine Systemgesten, kein Server. Ein Lauf zeigt, dass es startet und wie es
aussieht — nicht, dass es richtig ist. Anleitung:
`tools/uhr-pruefstand/LIESMICH.md`.

## 5a. Android-Apps (Kotlin/Compose) — Handy und Wear OS

*Seit S4, Blöcke B und C. Die App zählt eigene Fassungen
(`android/version.properties`), unabhängig von `WEB_VERSION` und von der
Uhr-App aus Abschnitt 5.* Bauanleitung, Entscheidungen und der vollständige Prüfstand
stehen in `android/LIESMICH.md`; hier steht, wie es zusammenhängt.

### Zwei Module, ein Quelltext

| Modul | läuft auf | hat |
|---|---|---|
| `android/handy/` | Telefon | Kopplung, Aufzeichnung, Dienstklammer, Phasen und Einsätze, Senden an `ingest.php` — **und die Zugangsdaten** |
| `android/uhr/` | Wear OS | dasselbe Bedienbild am Handgelenk, **ohne** Zugangsdaten |
| `android/gemeinsam/` | beide | Nachrichtenformat, Data-Layer-Weg, Kennungen, Modus, Phasen, Farben, Bildmarke (E-S4-24) |

**Die Uhr kennt weder Serveradresse noch API-Schlüssel** (E-S4-11). Sie
schickt ihre Ereignisse an das Handy, und das Handy sendet. Das ist keine
Bequemlichkeit, sondern die Sicherheitsaussage der Bauform: Eine verlorene
Uhr gibt keinen Zugang preis. Der Prüfstand zählt die Schlüssel des
Nachrichtenformats nach — genau `uhr, nr, art, zeit, phase, einsatz_ref`,
kein `api_key`, keine `device_id`, keine Adresse. Diese Menge ist die
**Uhrmeldung**, also die Richtung Uhr → Handy; dort steht die
Sicherheitsaussage, und sie ist seit S4 unverändert. Die Standmeldung in der
Gegenrichtung wuchs mit Android 0.8.0 um einen Schlüssel (`ortung`, ein
Kurzcode des Ortungszustands) — auch er trägt kein Zugangsdatum, und ein
eigener Prüffall zählt beide Mengen getrennt nach.

Beide Module tragen dieselbe `applicationId` (`org.genem.nadoku`) und
**müssen mit demselben Schlüssel signiert sein** — der Wear Data Layer
stellt sonst nicht zu (E-S4-01). Der Signaturschlüssel entsteht außerhalb des
Repositoriums und wird verwahrt (E-R45-6); jede spätere Fassung braucht
denselben, sonst verlangt Android eine Neuinstallation.

### Der Weg zwischen Uhr und Handy

`MessageClient` des Wear Data Layer, drei Pfade: `/nadoku/ereignis`
(Uhr → Handy), `/nadoku/quittung` und `/nadoku/stand` (Handy → Uhr). Die
Standmeldung trägt seit Android 0.8.0 auch den Ortungszustand des Handys;
fehlt das Feld — eine ältere Handy-Fassung —, zeigt die Uhr dazu nichts an,
statt etwas zu behaupten. Die
Umsetzung steckt in **einer** Datei (`WearNachrichtenweg.kt`) hinter der
Schnittstelle `Nachrichtenweg`; alles darüber kennt nur die Schnittstelle.
Deshalb laufen die Prüffälle beider Module ohne Play-Dienste — sie benutzen
eine Attrappe. Zur Lizenzlage der proprietären Bibliothek:
`docs/Lizenzen.md` 6a.

**Zwei Böden gegen die Doppelzustellung** (E-S4-10, E-S4-09):

1. Eine **Ereignisnummer je Uhr**, fortlaufend. Das Handy quittiert bis zur
   höchsten **lückenlosen** Nummer — eine Lücke hält die Quittung an, statt
   über sie hinwegzuspringen.
2. Die **`wm-`-Kennung** des Einsatzes als zweiter Boden. Sie greift auch,
   wenn die Buchführung verlorengegangen ist (zurückgesetzte Uhr, neue
   Nummernreihe): Ein Ereignis mit bekannter Kennung landet im vorhandenen
   Einsatz, statt einen zweiten anzulegen.

Ohne Quittung wird dieselbe Nachricht **mit derselben Nummer** erneut
gesendet. Der Puffer der Uhr überlebt ihren Neustart.

### Ortungswächter und Nachsenden

*Seit Android 0.8.0 (S5, Paket E).*

**Der Vordergrunddienst misst, ob wirklich aufgezeichnet wird.** Bis 0.7.7
leitete die App ihre Aussage aus der **Freigabe** ab: erteilt → „Aufzeichnung
läuft · GPS an". Ob der Standort eingeschaltet ist, ob Positionen hereinkommen
und ob sie brauchbar sind, hat sie nie geprüft — drei Lagen, in denen im
Puffer nichts landet, sahen aus wie die vierte, in der es funktioniert.

Der `Ortungswaechter` ist eine **reine Zustandsmaschine ohne Android-Bezug**
(`handy/…/aufzeichnung/Ortungswaechter.kt`): Er bekommt Ereignisse und die
Zeit übergeben und liefert einen von sechs Zuständen plus die Entscheidung,
ob zu warnen ist. Deshalb ist er auf der JVM prüfbar — wie `Sendetakt` und
`Uhrbedienung`, und aus demselben Grund: Was auf einem Gerät nicht
nachstellbar ist, muss wenigstens in seiner Regel belegbar sein.

| Zustand | Bedingung | Es wird aufgezeichnet? |
|---|---|---|
| `FREIGABE_FEHLT` | `ACCESS_FINE_LOCATION` nicht erteilt | nein |
| `STANDORT_AUS` | GPS-Anbieter aus | nein |
| `SUCHT` | Anbieter an, noch kein Fund, Erstfix-Frist läuft (120 s) | noch nicht |
| `KEIN_SIGNAL` | kein Fund seit 120 s (nach Start) bzw. 60 s (nach einem Fund) | nein |
| `UNGENAU` | Funde kommen, aber seit 60 s keiner unter 100 m Streuung | nein |
| `OK` | brauchbarer Fund innerhalb der letzten 60 s | **ja** |

Drei Festlegungen dahinter, jede mit einem Grund:

- **Der GPS-Anbieter entscheidet, nicht `isLocationEnabled()`.** Im Modus
  „Stromsparen" ist der Standort an und GPS aus — aufgezeichnet wird nur mit
  GPS.
- **„Brauchbar" ist dieselbe Schwelle, nach der aufgezeichnet wird.**
  `Ausduenner.brauchbar()` ist öffentlich, damit Anzeige und Puffer nicht
  zwei Regeln führen. Die Garmin-Uhr hält es genauso (`SyncView.mc` gegen
  `Track.mc`): Eine Anzeige mit anderer Schwelle wäre irreführend.
- **Gemessen wird mit `SystemClock.elapsedRealtime()`**, nicht mit der
  Wanduhr und nicht mit der GPS-Zeit. Beide können springen; eine Frist, die
  einen Sprung mitmacht, meldet eine Lücke, die es nicht gab, oder
  verschweigt eine, die es gab.

**Was der Zustand steuert:** die Zustandszeile der Dienstansicht (drei
Farbstufen — Asphalt bei `OK`, gedämpft bei `SUCHT`, `rotTief` bei den vier
übrigen), den Text der Dauermeldung (derselbe Wortlaut — zwei Wortlaute für
denselben Zustand laufen auseinander), eine **Warnung auf eigenem Kanal**
(ID 3, `warnungen`, Vibration ohne Ton, Erinnerung alle 10 min) und den
Kurzcode in der Standmeldung an die Uhr.

**Ein zweiter Benachrichtigungskanal, weil Android die Einstellungen eines
Kanals nach dem Anlegen der Nutzerin überlässt.** Der Kanal „Aufzeichnung"
ist bewusst `LOW` und stumm; eine Warnung, die spürbar sein muss, kann nicht
an einer Einstellung hängen, die für die Dauermeldung gemacht wurde.

**Ein Dienst beginnt nicht mehr bei ausgeschaltetem Standort** (am Handy).
Erst die Freigabe, dann der Standort — solange einer der beiden Blöcke steht,
gibt es keinen Knopf „Dienst beginnen". Ein von der **Uhr** ausgelöster Start
wird dagegen durchgelassen: Dort kann niemand gefragt werden, und ein stilles
„nein" am Handgelenk erklärt nichts. Das Handy warnt stattdessen sofort und
schickt den Zustand mit der Quittung zurück; die Uhr zeigt „keine Ortung ·
keine Aufzeichnung" in der Zeile am unteren Rand — dort, wo auch
`dienst_schwebt` steht, und aus demselben Grund (Bedienelemente in die Mitte,
Statusanzeigen an den Rand).

**Der Ortungszustand liegt im Arbeitsspeicher** (`NAdokuApp.ortung`), nicht im
Puffer: Er ist ein Augenblickswert und überlebt einen Neustart bewusst nicht.
Eine wiederhergestellte Aussage über den GPS-Empfang von vorhin wäre
schlimmer als keine — sie sähe aus wie eine Messung. `null` heißt „es läuft
kein Dienst"; vor dem Dienst leitet die Oberfläche selbst ab, was sie für die
beiden Sperren braucht.

**Zwei Takte an einem Handler, mit getrennten Token.** Der Sendetakt (15 min)
und der Wächtertakt (10 s) laufen am selben `Handler`;
`removeCallbacksAndMessages(null)` hätte die ganze Warteschlange gelöscht und
den Wächter beim ersten Uhrereignis still umgebracht. Der Sendetakt wird
außerdem nur noch gestartet, wenn er nicht schon läuft — sonst schob jede
Uhrnachricht seine Frist vor sich her, und ein von der Uhr geführter Dienst
sendete bis zum Dienstende gar nicht.

**Nachsenden nach dem Dienstende** kommt mit Paket E2 und ist hier noch nicht
beschrieben.

### Was die App an den Server schickt

Nichts Neues: denselben JSON-Vertrag wie die Uhr-App aus Abschnitt 5. **Der
Ingest ist geräteneutral und bleibt es** — er sieht ein Gerät mit Kennung und
API-Schlüssel und weiß nicht, ob dahinter Monkey C oder Kotlin steckt. Die
Kennungspräfixe unterscheiden die Quellen (`am-`/`ar-`/`ad-` für das Handy,
`wm-` für die Wear-OS-Uhr); sie stehen seit Vertragsfassung 1.4 im
JSON-Vertrag, Abschnitt 8 — der Nachtrag hing an R42 und ist mit S6 erledigt.

**Die Kopplung ist seit Web 12.9.0 nicht mehr geräteneutral**, und das ist
Absicht: `pair.php` liest den Block `geraet` aus und hält fest, was gekoppelt
hat (R42). Die Neutralität gilt weiterhin für den Upload — dort entscheidet
nichts am Verhalten des Servers, welcher Client sendet. Beim Koppeln ist die
Geräteart die Auskunft selbst.

### Prüfen ohne Gerät

Es gibt keine Uhr und kein Telefon. Was trotzdem geht, steht in
`android/LIESMICH.md`; die Kurzform:

- **Prüffälle** über JUnit und Robolectric — auch gegen ein *echtes* SQLite
  und, wo eine lokale Installation läuft, gegen `ingest.php` selbst.
- **Bilder** über Robolectric im NATIVE-Grafikmodus. `captureToImage()` ist
  unter Robolectric strukturell unbrauchbar (Deadlock in
  `WindowCapture.forceRedraw`); der Weg darüber ist der einzige, der ohne
  Emulator Pixel liefert — und er kostet **null** neue Abhängigkeiten.
- **Ein Emulator läuft**, entgegen E-R45-8, aber ohne KVM (QEMU/TCG) und nur
  mit `-no-window`. `sys.boot_completed=1` lügt dabei; die Begründung, warum
  er trotzdem nicht der Hauptweg ist, steht in der `LIESMICH.md`.
- **Instrumentierte Prüffälle** (seit Android 0.7.6) laufen auf dem Emulator
  und schließen zwei Lücken, die die JVM nicht erreicht: den echten
  `AndroidKeyStore` und die Erreichbarkeit der Wearable-API. Sie gehen
  **an Gradle vorbei** (`adb shell am instrument`) — `connectedAndroidTest`
  scheitert auf einem softwareemulierten Gerät an einer ddmlib-Zeitgrenze.

#### Wo die Grenze zum Data Layer wirklich liegt

Sie lag nicht dort, wo sie dokumentiert war. Gemessen am 02.09.2026:

| bisher angenommen | gemessen |
|---|---|
| keine Play-Dienste im Container | `com.google.android.gms` **22.48.14** liegt im Wear-Abbild, `isGooglePlayServicesAvailable` = `SUCCESS` |
| Wearable-API nicht erreichbar | `NodeClient.localNode` liefert einen **lokalen Knoten mit Kennung** |
| Empfangsdienst ungeprüft | `HandyHorcher` ist registriert (`wear:`, `PREFIX /nadoku`) und löst für alle drei Pfade auf |

**Was tatsächlich fehlt, ist die Telefonseite.** Zwei Emulatoren zu koppeln
verlangt die Wear-OS-Companion-App auf dem Telefon; die kommt aus dem Play
Store und damit über eine Anmeldung mit einem Google-Konto. `adb forward
tcp:5601 tcp:5601` steht bereit, `com.google.android.wearable.app` liegt auf
der Uhr — es hakt an genau einem Schritt, und der ist jetzt benannt statt
vermutet.

**Was keiner dieser Wege ersetzt:** die **Zustellung** über den Data Layer.
Ob eine Nachricht ankommt, ob die beiden `WearableListenerService` mit einer
echten Nachricht das Richtige tun, ob Paket- und Signaturgleichheit im Feld
greift — das ist Gerätetest und steht aus.

## 6. Deployment

Push auf `main` mit Änderungen unter `server/` → GitHub Action
(`.github/workflows/deploy.yml`) lädt per **FTPS** hoch. **Auf dem Server liegt
dadurch 1:1 der Repositoriumsstand**; einzige Ausnahme ist die bei der
Installation erzeugte `config.php` (und `install.lock`), die nur auf dem Server
existieren. Secrets: `FTP_SERVER` (nackter Hostname!), `FTP_USERNAME`,
`FTP_PASSWORD`. `.gitignore` hält `watch/bin/`, `*.prg` und `config.php` aus dem
Repo.

## 7. Betrieb (Runbook)

**Demo-Konto einrichten (einmalig):** Fixture erzeugen —
`php tools/referenzdatensatz/fixture/erzeugen.php` auf der Maschine, auf der
der Referenzbestand liegt — dann `server/demo/fixture.json.gz` mit ausrollen
und im Adminbereich unter **Demo-Konto → anlegen**. Die Seite zeigt danach
die Bestandszahlen; sie müssen 15 Diensttage, 82 Einsätze, 95 Ruhesegmente,
5 im Papierkorb und 2 Geräte nennen. Mechanik: Abschnitt 4.99a.

**Demo-Konto sieht falsch aus / hängt:** Adminbereich → **Demo-Konto → Auf
Standard zurücksetzen**. Der Vorgang ist transaktional und dauert wenige
Sekunden. Er läuft ohnehin alle 30 Minuten von selbst — ausgelöst von der
nächsten Anfrage, nicht von einem Zeitdienst. Bleibt die Seite leer, fehlt
die Fixture; das sagt sie dann auch.

**Demo-Konto nach einem Datensatz-Update auffrischen:** Erst den
Referenzbestand neu einspielen
(`tools/referenzdatensatz/LIESMICH.md`, „Die drei Läufe"), dann die Fixture
neu erzeugen, dann ausrollen, dann im Adminbereich zurücksetzen. Die
Reihenfolge ist wesentlich: Eine Fixture aus einem halb eingespielten Bestand
sieht vollständig aus und ist es nicht.

**Ein Konto mit 5000 Einsätzen herstellen (Mengenprüfung, S2/R35):**
`cd tools/messstand && python3 messen.py --frisch`. Der Lauf legt das Konto
`messstand@gen-em.org` an, vervielfältigt das Referenz-Backup zu einer Folge
`.edbak`-Dateien und spielt sie über den **regulären** Wiederherstellungsweg im
Browser ein — kein SQL. Dauer je nach Rechner rund zehn Minuten; danach misst
er Suche, Tagesansicht, Sichern (Browser, CPU-Drossel 6×) sowie Tabellengrößen
und Speicherspitzen (Server). **Niemals gegen die Produktiv- oder
Referenzinstallation**: Der Riegel des Werkzeugs füllt nur Konten mit dem
Präfix `messstand` und verlangt für eine fremde Adresse ein ausdrückliches
`MESSSTAND_FREMDE_INSTALLATION=ja`. Einzelheiten und die Grenzen des
Prüfmittels: `tools/messstand/LIESMICH.md`.

**Hintergrundjobs einrichten (empfohlen, seit Web 10.1.0):** Nichts tun ist
erlaubt — dann läuft die Wartung huckepack auf den Anfragen mit, höchstens 3 s
je Anfrage und frühestens alle 5 Minuten je Job. Ab einigen hunderttausend
Spurpunkten sollte trotzdem ein echter Zeitgeber her. Adminbereich →
**`/update.php`** → Abschnitt **„Wann die Jobs laufen"**; dort stehen Befehl
und Adresse fertig zum Kopieren:

1. **Kommandozeile** (bevorzugt): `* * * * * php …/server/jobs.php`. Jede
   Minute ist unbedenklich — ein Lauf ohne Arbeit kostet zwei Abfragen. Die
   tägliche Aufräumarbeit läuft trotzdem nur einmal am Tag; das entscheidet der
   Job, nicht der Zeitplan. Einzelne Jobs: `php jobs.php waisen`, Hilfe:
   `php jobs.php --hilfe`.
2. **Abruf über die Adresse**, wo es keinen CLI-Cron gibt:
   `https://…/jobs.php?token=…`. **Die Adresse enthält ein Geheimnis** — nicht
   in eine Mail, nicht in ein Ticket. Ein neues Token macht das alte ungültig;
   ein bestehender Zeitplan-Eintrag läuft danach ins Leere.

**Spuren werden nicht verdichtet (Rückstand wächst):** `/update.php` → Karte
„Hintergrundjobs" → Zeile **„Spuren verdichten"**. Darunter steht, was
liegenbleibt und warum, mit Kennung: *Lücke in der Nummernfolge* (eine Uhr hat
ein Teilstück nie nachgeliefert — die Spur bleibt als Zeilen stehen, das ist
richtig so), *Zu viele Punkte* (über 50 000; aus einem Backup nicht
wiederherstellbar), *Punkte auf einer ausgedünnten Spur* (Erwartungswert **0** —
steht dort eine Zahl, nimmt `ingest.php` an, was es verwerfen sollte),
*Prüfung nicht bestanden* (es wurde nichts gelöscht und nichts ersetzt).

Ein Rückstand ohne diese Listen ist normal: Er zählt auch, was schlicht noch in
der **Karenz** ist (14 Tage ohne neuen Punkt nach `final`, sonst 60 Tage) oder
im Papierkorb liegt.

**Die Jobs vorübergehend anhalten** (vor einer großen Wiederherstellung, vor
einer Messung): `php jobs.php --pause 1800`, aufheben mit `--pause 0`. Die
Pause gilt für alle drei Auslöser, läuft nach höchstens zwei Stunden von selbst
ab, und die Wartungsseite zeigt sie an. **Sie ist kein Ersatz für ein
Backup:** Was der Ausdünnungsjob einmal ersetzt hat, ist weg.

**Nach dem Ausrollen von Web 10.2.0 auf einen gewachsenen Bestand:** Der erste
Verdichtungslauf trägt den ganzen Altbestand ab. Gemessen an 3,3 Mio. Punkten:
9395 Spuren in 44 s über die Kommandozeile. Am Huckepack-Weg (3 s je Anfrage,
frühestens alle fünf Minuten) dauert dasselbe Tage — wer den Altbestand zügig
abgetragen haben will, richtet vorher einen der beiden anderen Auslöser ein
oder ruft einmal `php jobs.php verdichtung` von Hand auf.

**Zeitplan-Eintrag antwortet `429` (`zu_viele_versuche`):** Das Token stimmt
nicht, und zehn Fehlversuche haben die IP für zehn Minuten gesperrt. Adresse
aus `/update.php` neu kopieren, dann **zehn Minuten warten** — vorher wird auch
der richtige Aufruf abgewiesen.

**Läuft die Wartung noch?** `/update.php` → Karte **„Hintergrundjobs"**: je Job
letzter Lauf, Auslöser, Rückstand und letzter Fehler. Plakette „scheitert" =
mindestens ein Job wirft dauerhaft; der Text steht darunter. Plakette
„Migration ausstehend" = die Tabelle `jobs` fehlt, also wurde `update.php` nach
dem Ausrollen von Web 10.1.0 nie ausgeführt. Ein wachsender **Rückstand** beim
Job `waisen` heißt nicht „kaputt", sondern „kommt am Huckepack-Weg nicht
hinterher" — dann Punkt 1 oder 2 oben einrichten.

**Gerät verloren / Schlüssel kompromittiert:** Web → „Geräte" (oder Verwaltung)
→ **Deaktivieren**. Wirkt sofort (Ingest antwortet `403`); Daten bleiben. Neue
Uhr = neues Gerät anlegen.

**Die Geräteliste sagt bei einem Gerät „Gerät unbekannt":** Kein Fehler.
Angaben über das Gerät entstehen **ausschließlich beim Koppeln** (seit Web
12.9.0) — bei einem Gerät, das vorher gekoppelt wurde, von Hand angelegt ist
oder eine ältere Client-Fassung trägt, gibt es nichts, was der Server wissen
könnte. Abhilfe ist **Neukopplung**, nicht Nachtragen: Es gibt keinen Weg,
die Angabe von Hand zu setzen, und das ist Absicht — sie soll eine Auskunft
des Geräts bleiben und keine Eingabe.

**Ein Uhrmodell erscheint als Teilenummer statt als Name** („Uhr ·
006-B4261-00"): Die Modelltabelle kennt diese Teilenummer nicht — entweder ist
das Gerät neuer als die Tabelle, oder sie wurde nie gefüllt. Zwei Schritte:

1. `python3 tools/geraetemodelle/erzeugen.py <Gerätedateien>` neu laufen lassen
   und ausrollen. Die Gerätedateien liefert nur der SDK-Manager; ihre
   Bereitstellungsadresse (`CIQ_GERAETE_URL`) steht nicht im Repositorium und
   **muss erfragt werden**.
2. `php tools/geraetemodelle/nachaufloesen.php` — zeigt, welche bestehenden
   Zeilen die neue Tabelle auflöst; `--schreiben` trägt es ein. **Braucht
   Shell-Zugriff**; ohne ihn holen die Geräte ihre Angabe bei der nächsten
   Kopplung nach.

**Nichts geht verloren, solange das offen ist** — die Rohangabe steht in
`devices.geraet_teil`. Zu beachten: Betroffen ist nicht nur der Modellname.
Solange die Tabelle die Teilenummer nicht kennt, steht in `geraet_art` die
**ungeprüfte Selbstauskunft** des Geräts, und die Garmin-App sendet dort fest
`"uhr"` — ein Radcomputer wäre bis zum Nachauflösen als Uhr gezählt.

**Code-Update mit DB-Änderung ausrollen:** pushen (Deploy läuft automatisch)
→ als Admin **`/update.php`** aufrufen → jede Zeile muss die Plakette
**„erledigt"** tragen. (Bis Web 9.11.1 stand dort ein ✔; seit P3/O11 sagt der
Status ein Wort — `erledigt` blau, `steht aus` orange, `blockiert` rot,
`Fehler` rot —, weil Schriftzeichen als Symbol ausgeschlossen sind, E-P3-18.)
Fehlgeschlagene Migrationen werden nicht verbucht und beim nächsten Aufruf
erneut versucht; Folge-Migrationen stoppen bis dahin. **Version in `version.php`
erhöhen** nicht vergessen, sonst sieht der Browser alte Dateien.

**Neue Zusatzfelder für Einsätze:** 1) Migration in `update.php` ergänzen
(`ALTER TABLE missions ADD COLUMN …`) und die ID zusätzlich in die
`skipped`-Liste in `schema.sql` eintragen, 2) Spalte auch ans `CREATE TABLE
missions` in `schema.sql` anfügen (sonst weichen Neuinstallation und
migrierter Bestand voneinander ab), 3) Eintrag in `mission_fields.php`.
Formular, Speichern, API und Detailanzeige übernehmen es dann automatisch.
Seit Web 5.4.0 gilt das auch für `day_col`: Der Schlüssel wird an genau einer
Stelle ausgewertet — `mf_tagesspalten()` in `mission_fields_lib.php` —, und
`api/day.php` liefert die Spalte daraufhin von selbst mit, `index.php` zeigt
und sortiert sie (Backlog Nr. 10). Optional bleibt eine Spaltenbreite in
`style.css` unter der Klasse `c-dc-<spalte>`; ohne sie greift die Vorgabe von
`.c-dc`. Die Gegenprobe lief in Web 5.10.0: Die Spalte „abw. Crew" wurde durch
das **Streichen zweier Schlüssel** wieder abbestellt, Kopf, Zeilen, Sortierung
und `SELECT` zogen von selbst nach. Spaltenbreiten nach Position
(`:nth-child`) gibt es im Stylesheet deshalb nicht mehr — sie zählen Spalten ab
und rutschen beim Streichen einer Spalte still auf die falsche.

**Backup (seit Web 12.2.0 aus der Anwendung heraus):** Adminbereich →
**Komplett-Backup** → *Jetzt sichern*, oder einen Zeitplan setzen
(täglich/wöchentlich/monatlich). Der Lauf schreibt jede Tabelle als
versiegelten SQL-Dump nach `server/sicherungen/komplett/`; der Versand aufs
Backup-Ziel nimmt ihn wie jedes andere Paket mit. Ein Hoster-Backup oder
ein `mysqldump` von aussen bleibt daneben zulässig und ist nicht überflüssig
— es läuft auf einem anderen Weg und fällt deshalb nicht mit demselben Fehler
aus. `config.php` ist in **keinem** dieser Backups enthalten; sie gehört
ins Wiederanlaufpaket (gleich darunter). Mechanik: Abschnitt 4.97d.

Die Uhr sendet nach einer Wiederherstellung fehlende jüngste Daten idempotent
nach, sofern lokal noch vorhanden.

**Wiederanlauf nach einem Totalausfall (seit Web 12.2.0).** In dieser
Reihenfolge; jeder Schritt setzt den vorigen voraus:

1. **Datenbank anlegen** — leer, aber vorhanden, utf8mb4.
2. **Anwendungsdateien hochladen** (der Deploy tut das, oder von Hand).
3. **`config.php` aus dem Wiederanlaufpaket** daneben legen. Datenbankzugang
   darin auf die neue Datenbank anpassen, den **`server_key` unverändert
   lassen** — er ist es, der das Backup öffnet.
4. **Die Backup-Datei** nach `server/sicherungen/eingang/` legen — per
   FTP, SFTP oder Dateimanager des Hosters. Vom Backup-Ziel holt man sie
   sich dorthin. Erkannt werden `.edk` (versiegelt), `.sql.gz` und `.sql`.
5. **`wiederherstellen.php` aufrufen.** Die Seite nennt eine Nachweisdatei im
   Anwendungsverzeichnis; deren Kennung eintragen, *Auspacken und prüfen*,
   dann *Einspielen* — so oft, bis 100 % erreicht sind. Jeder Durchgang macht
   dort weiter, wo der vorige aufhörte.
6. **Anmelden** — mit dem Administrationskonto aus dem Backup; die
   Passwörter sind dieselben wie vorher.
7. **Wartung (`update.php`) aufrufen** und den Migrationslauf ausführen.
   Nicht optional, wenn das Backup aus einer älteren Fassung stammt — die
   Seite sagt es dann auch. Der Lauf passiert dort und nicht in Schritt 5,
   weil Migrationen Spalten löschen können und dazwischen eine angemeldete
   Person und ein Knopf gehören (M6-01).
8. **Aufräumen** — auf `wiederherstellen.php` der gleichnamige Knopf. Er
   entfernt den ausgepackten Klartext-Dump und die Nachweisdatei. Beides hat
   danach auf dem Server nichts mehr verloren.

**Was dabei schiefgehen kann, und woran man es erkennt:**

| Meldung | Ursache | Behebung |
|---|---|---|
| „Diese Installation ist noch nicht eingerichtet" | keine `config.php` | Schritt 3 nachholen |
| „Die Datenbank antwortet nicht" | Zugangsdaten in `config.php` passen nicht, oder die Datenbank existiert nicht | Schritt 1 und 3 prüfen |
| „Diese Installation ist in Betrieb" | in der Datenbank stehen schon Konten | Datenbank leeren (bewusste Handlung beim Hoster) oder einzelne Konten über *Backups* zurückholen |
| „falscher Schlüssel, falsche Passphrase — oder der Dateikopf ist verändert" | der `server_key` in `config.php` ist nicht der, mit dem versiegelt wurde | den richtigen aus dem Wiederanlaufpaket eintragen |
| „Dieses Backup ist unvollständig — die Endmarke fehlt" | der Lauf ist beim Erzeugen abgebrochen | einen älteren Stand nehmen |
| „gescheitert an Anweisung *n*" | halb eingespielt; es wurde **nichts** zurückgenommen | Datenbank leeren und von vorn |

**Das Wiederanlaufpaket (seit Web 12.1.0, E-S2-21).** Getrennt von der
Anwendung aufbewahren — auf einem anderen Rechner, nicht im selben Backup:

1. **`server/config.php`.** Sie steht in `.gitignore` **und** in der
   Ausnahmeliste des Deploys; es gibt sie also nur auf dem Server.
2. **Der Serverschlüssel** darin (`'server_key' => '…'`, 64 Hexzeichen).
   Er versiegelt die Zugangsdaten der Backup-Ziele und — ab AP8 — das
   Komplettbackup. **Ohne ihn** sind die Zugangsdaten der Ziele neu
   einzutragen (verschmerzbar) und ein versiegeltes Komplettbackup **nicht
   mehr zu öffnen** (nicht verschmerzbar).
3. **Der Zugang zum Backup-Ziel** — Rechnername, Nutzer, Passwort bzw.
   privater Schlüssel. Er steht in der Datenbank, aber versiegelt; wer nur
   den Dump hat und den Serverschlüssel nicht, kommt an die Backups
   dort nicht heran. Das ist der Sinn der Sache und zugleich der Grund,
   ihn zusätzlich von Hand zu notieren.

**Probe-Wiederherstellung** ist ein Prüfpunkt und keine Formalie: einmal je
Halbjahr ein Paket vom Ziel holen und in ein Wegwerfkonto einspielen. Ein
Backup, das nie zurückgespielt wurde, ist eine Vermutung.

**Serverschlüssel nachtragen (bestehende Installation):** Adminbereich →
**Backup-Ziele**. Ist `config.php` beschreibbar, genügt der Knopf; sonst
zeigt die Seite die fertige Zeile zum Einfügen — **genau eine** eintragen, bei
jedem Neuladen steht dort eine andere. Danach die Zeile ins Wiederanlaufpaket.

**Backup-Ziel einrichten:** Adminbereich → **Backup-Ziele** → *Ziel
anlegen*. **SFTP wählen, wenn das Ziel es anbietet** — es ist das einzige der
drei Protokolle, das den Server am Hostschlüssel wiedererkennt. Danach
**Verbindung prüfen**: Der Lauf schreibt eine Probedatei, liest sie zurück,
vergleicht sie und löscht sie wieder; er beantwortet damit auch die Frage nach
den Schreibrechten. Beim ersten Mal wird der Hostschlüssel übernommen.
Zuletzt den Schalter *Backups automatisch versenden* setzen — **wie oft**
das geschieht, entscheidet der eingerichtete Job-Auslöser (Abschnitt 4.97a).

**„Der Server meldet sich mit einem ANDEREN Hostschlüssel":** Erst klären, ob
die Gegenstelle ihren Schlüssel tatsächlich gewechselt hat (beim Hoster
nachfragen, den Abdruck aus einer zweiten Quelle vergleichen —
`ssh-keyscan <host> | ssh-keygen -lf -` liefert dieselbe Schreibweise). Erst
dann **Hostschlüssel vergessen**; die nächste Prüfung übernimmt den neuen. Es
wurde nichts übertragen und kein Passwort gesendet.

**„Die Zugangsdaten dieses Ziels lassen sich nicht entschlüsseln":** In
`config.php` steht ein anderer Serverschlüssel als der, mit dem sie gespeichert
wurden. Entweder den alten wieder eintragen (Wiederanlaufpaket) oder die
Zugangsdaten am Ziel neu erfassen.

**Wartungsseite, Abschnitt „Schlüsselableitung" (seit Web 5.0.1):** Erscheint
**nur, wenn es etwas zu melden gibt** — Konten, deren `kdf_iter` nicht in
`KDF_ITER_LISTE` steht. Sie können sich nicht
anmelden, und an der Anmeldemaske ist die Ursache nicht zu erkennen — der
Browser leitet nur für die gelisteten Werte ab, das entstehende Token passt zu
keinem gespeicherten Hash. Behebung: den fehlenden Wert wieder in die Liste
aufnehmen.

**Externe Abhängigkeiten zur Laufzeit: keine (seit Web 5.2.0).** Bis dahin
holte jede Seite zwei Dinge aus dem Netz — die Schriften Bricolage Grotesque
und Open Sans von `fonts.googleapis.com`/`fonts.gstatic.com` (per `@import` in
`style.css`) und Leaflet von `unpkg.com`. Beides wird jetzt selbst
ausgeliefert:

* **Schriften** als woff2 in `server/assets/fonts/`, eingebunden per
  `@font-face` mit `font-display:swap`. Übernommen wurden nur die tatsächlich
  benutzten Schnitte (Bricolage 500/600, Open Sans 400/600/700), je in den
  Subsets latin und latin-ext; `unicode-range` trennt die beiden, latin-ext
  lädt also nur, wenn ein Zeichen daraus vorkommt. Wer einen weiteren Schnitt
  braucht, legt die Datei dazu **und** trägt sie in `style.css` ein — ohne
  `@font-face` nutzt die Datei nichts.
* **Leaflet 1.9.4** in `server/assets/vendor/leaflet/` (CSS, JS und die von der
  CSS referenzierten Bilder unter `images/`), nach demselben Muster wie
  SheetJS und zip.js: Herkunft und SHA-256 der Originaldatei stehen im
  Dateikopf.

Damit entfallen beide bisherigen Folgen: Es meldet kein Seitenaufruf mehr die
IP-Adresse an Google oder unpkg, und ein Werbeblocker oder strenger
Trackingschutz kann die Karte nicht mehr ausfallen lassen. Die
Ersatzschriftenliste in `style.css` bleibt trotzdem bestehen und bleibt normal
breit (siehe Web 5.1.1) — sie trägt jetzt nur noch den Fall einer fehlenden
Datei.

Nebeneffekt: Eine Content-Security-Policy (**Backlog Nr. 8**) lässt sich jetzt
überhaupt erst eng formulieren, weil keine fremde Quelle mehr erlaubt werden
muss.

**Zeiteingaben (seit Web 5.2.0).** Uhrzeiten werden **nicht** über
`<input type="time">` erfasst, sondern über Textfelder mit der Klasse
`zeitfeld`; `assets/zeitfeld.js` setzt Maske, `inputmode`, `pattern` und die
Rückmeldung im Browser. Grund: Das Anzeigeformat nativer Zeitfelder folgt der
Sprach- bzw. Regionseinstellung des Betriebssystems und zeigt dort, wo diese
auf 12 Stunden steht, „01:30 PM" — auch bei deutscher Oberfläche. Erzwingen
lässt sich das weder per HTML noch per CSS oder JavaScript. **Datumsfelder
bleiben nativ:** Dort ist die Anzeige kosmetisch (der übertragene Wert ist
immer ISO), und ein selbstgebauter Kalender wäre mobil schlechter zu bedienen.

Die Prüfschicht liegt weiterhin auf dem Server: `local_to_utc()` in `db.php`
prüft Muster **und** Wertebereich. `zeitfeld.js` ist Bequemlichkeit im
Browser, kein Backup. Betroffen sind die Phasenzeiten im Einsatzformular
(`ph_time[]`, dynamisch erzeugt) und die beiden Alarmzeit-Filter der Suche;
letztere sind reine Clientfilter und erreichen den Server nie.

**Neuinstallation:** leere DB + `server/` hochladen → `index.php` leitet zum
Installer. Der Installer fragt **kein** Admin-Passwort mehr ab; er legt den
Zugang ohne Passwort an und zeigt auf der Erfolgsseite einen 24 h gültigen
Einmal-Link auf `pw_handling.php`, über den Passwort und
Wiederherstellungsschlüssel im Browser entstehen. Nach Erfolg sperrt
`install.lock`; `install.php` danach löschen.

**Dateizugriffsnachweis (seit 4.7.0, M1-11):** Der Installer legt beim ersten
Aufruf eine Datei `install-nachweis-<32 Hexzeichen>.txt` im Verzeichnis
`server/` an und verlangt diese Kennung im Formular. Sie steht im **Dateinamen**
und nicht nur im Inhalt — bei Einfachhosting liegt `server/` im
Web-Wurzelverzeichnis, und eine Datei mit festem Namen wäre abrufbar. Die
`.htaccess` sperrt sie zusätzlich.

Die Kennung hängt an der **Datei**, nicht an der Sitzung: Eine vorhandene wird
übernommen. Sonst ließe jeder Aufruf der Seite eine weitere Datei liegen, und
niemand wüsste mehr, welche gilt. Nach erfolgreicher Einrichtung wird die Datei
gelöscht; sie darf auch jederzeit von Hand entfernt werden (der nächste Aufruf
legt eine neue an).

Ein Häkchen „Vorhandene Tabellen vorher löschen“ gibt es **nicht mehr** — es
war die einzige Stelle im Projekt, an der ein unangemeldeter Aufruf jede
Tabelle der Datenbank hätte leeren können. Für eine Neuinstallation auf einer
belegten Datenbank: leere Datenbank anlegen oder die vorhandene beim Hoster
leeren.

**Deploy schlägt fehl:** Actions-Log lesen. `ENOTFOUND` = `FTP_SERVER`-Secret
prüfen (nur Hostname, kein Schema/Pfad). Auth-Fehler = Zugangsdaten;
SFTP-only-Hoster brauchen einen anderen Workflow.

**„Der Fix wirkt nicht":** Zuerst prüfen, ob auf dem Server wirklich der
aktuelle Code liegt — den Quelltext der betroffenen Seite ansehen (Version in
der Fußzeile, `?v=`-Anhang an den Assets). Mehrfach lag die Ursache an
veralteten Dateien, nicht am Code.

**Karte zeigt „Access blocked":** Referrer-Policy prüfen
(muss `strict-origin-when-cross-origin` sein), Hard-Reload.

**Diagnose Uhr lädt nicht hoch:** Web „Geräte" → „Zuletzt gesehen"; Gerät
aktiv? Einstellungen der Uhr-App (Server-Domain, ID, Schlüssel) gesetzt?
Uhr online (Handy-Kopplung/WLAN)? Anzeige „Sync ausstehend" verschwindet nach
erfolgreichem Upload. *Bei Garmin liegen diese Einstellungen in Garmin Connect.*

## 8. Backlog

Die offenen Punkte stehen in einer eigenen Datei: **`Backlog.md`**. Dort sind
sie durchnummeriert; Verweise aus Code und Dokumentation nennen die Nummer
(z. B. „Backlog Nr. 10").

---

## Admin-Backups (A8, seit Web 5.9.0)

**Zweck.** Administration soll Konten sichern und wiederherstellen können, ohne
Einblick in die Daten zu bekommen. Der Serverteil war im Kern vorhanden:
`edbak_build()` liefert das vollständige Datenpaket und behält `pat_blob` als
Chiffretext, `edbak_restore()` übernimmt ihn unverändert.

**Ablage.** `server/sicherungen/<kontokennung>/`, je Ordner eine
`konto.json` (Begleitdatei **und** Verzeichnis) und höchstens `n` Pakete
`<zeitstempel>_<zufall>.zip` — `n` ist seit Web 9.8.0 eine Einstellung
(`app_state.adminbackup_aufbewahrung`, `edbak_aufbewahrung()`, **Vorgabe 2
seit Web 12.0.0**, vorher 3). Nicht in der Datenbank: Ein Paket liegt bei
größeren Beständen im zweistelligen MB-Bereich, `max_allowed_packet` liegt auf
geteiltem Webspace oft unveränderlich bei 16 MB — und ein Backup im selben
Behälter wie das Gesicherte ist keine Rückfallebene.

**Zwei Schranken gegen den Abruf über den Browser**, dasselbe Muster wie bei der
Nachweisdatei der Ersteinrichtung (M1-11): eine `.htaccess` mit
`Require all denied`, die `edbak_ablage_bereit()` bei **jedem** Schreibzugriff
nachlegt, und der nicht erratbare Ordnername.

**Ein Paket ist seit Web 12.0.0 ein ZIP** (Aufbau: `docs/Backup-Format.md` 5).
Gebaut, gelesen und eingespielt wird in Fenstern zu 250 Einträgen — dieselbe
Zahl wie bei dem Nutzer-Backup, hier aber aus einem anderen Grund: Über die
Leitung geht nichts, es zählt allein der Speicher. **Gemessen** am
5000er-Bestand: 1077,6 MB → **24,0 MB von 64**, Datei 94,28 → 11,42 MB, Dauer
19,81 → 14,13 s. Mit `memory_limit=64M` (Z3) brach der Lauf vorher ab.

Ein Umweg, den erst die Messung erzwungen hat: `ZipArchive::addFromString()`
hält jede übergebene Zeichenkette bis zum `close()` im Speicher (34,6 MB
Inhalt → 42,0 MB Spitze), `addFile()` streamt von der Platte (**2,0 MB**). Die
Teile entstehen deshalb einzeln in einem Bauordner `.bau-<8 Hex>/` und gehen
von dort ins Archiv. Bleibt ein solcher Ordner nach einem Abbruch liegen,
räumt `edbak_baureste_aufraeumen()` ihn weg — vor jedem Löschen des
Kontoordners, und er zählt gegen die Speichergrenze mit.

**`ext/zip` ist damit Voraussetzung.** `edbak_ablage_bereit()` prüft es bei
jedem Schreibzugriff, `install.php` seit Web 12.0.0 schon vor der Einrichtung
(zusammen mit `zlib`, `openssl`, `mbstring` — vorher prüfte der Installer gar
keine Erweiterung).

**Speichergrenze und Warnschwellen** (E-S2-15, seit Web 12.0.0): Vorgabe 2 GB
und 70/90 %, beides im Adminbereich. Geprüft **vor** dem Bau; erreicht heißt
abgelehnt mit Meldung, nie still verdrängt. Gezählt wird das **ganze**
Verzeichnis. Ohne eingerichtetes SMTP (`smtp_eingerichtet()`) steht statt der
Mail ein dauerhafter Hinweis im Adminbereich. Einzelheiten:
`docs/Backup-Format.md` 5b.

**`sicherungen/` steht in der `exclude`-Liste von `.github/workflows/deploy.yml`.**
Das ist keine Feinheit: Der FTP-Deploy synchronisiert `server/` und löscht alles,
was nicht ausgenommen ist. Deshalb wird die `.htaccess` auch zur Laufzeit
erzeugt und nicht mitgeliefert — eine mitgelieferte käme im ausgenommenen Ordner
nie an.

**`users.account_key`** (Migration `2026_08_16_kontokennung`) ist der
Ordnername: `bin2hex(random_bytes(8))`, bei der Kontoanlage vergeben, danach
unveränderlich, `UNIQUE`. Warum weder E-Mail-Adresse noch `users.id` in Frage
kommen, steht ausführlich im Kopf der Migration — die Kurzfassung: Die Adresse
ändert sich und ist personenbezogen, und der `AUTO_INCREMENT`-Zähler kann nach
einem Serverneustart zurückfallen, sodass ein neues Konto den Ordner eines
gelöschten erbt.

**In der Oberfläche erscheint die Kennung nie** — auch nicht in verborgenen
Formularfeldern. Dort steht `edbak_handgriff()`, die gekürzte Prüfsumme der
Kennung: stabil über mehrere Tabs, ohne Zustand in der Sitzung, und nicht
zurückzurechnen. Die Kennung ist die zweite Schranke; eine Schranke, die auf
jeder Verwaltungsseite im Quelltext mitläuft, ist keine.

**Der Weg beim Zurückspielen entscheidet sich am Vergleich der Kennungen**
(`edbak_weg()`), nicht an einer Einschätzung im Einzelfall:

| Fall | Weg |
|---|---|
| Kennung im Paket = Kennung des Zielkontos | direkt einspielen |
| Kennungen weichen ab, Paket enthält geschützte Angaben | **gesperrt**, stattdessen Freigabe für die NutzerIn |
| Kennungen weichen ab, Paket enthält **keine** geschützten Angaben | direkt einspielen — es gibt nichts umzuschlüsseln |
| Geschützte Angaben vorhanden, aber `pat_wrap_rc` fehlt | ganz gesperrt: Der Inhaltsschlüssel ist von niemandem mehr zu öffnen |

Die dritte Zeile ist der Befund aus Prüfschritt **P6**: Konten mit
`pat_wrap_rc IS NULL` gibt es regulär — jedes eingeladene Konto zwischen Anlage
und erster Passwortvergabe. Sie haben keinen Inhaltsschlüssel und damit auch
keine geschützten Angaben; die Sperre aus E20 hätte dort keinen Zweck, weil ihre
Begründung nicht zutrifft.

**Der Nutzerweg** (`api/adminbackup_freigabe.php` + `einstellungen.php?t=backup`)
läuft vollständig im Browser: Wiederherstellungsschlüssel →
`EdCrypto.recoveryKeyHex()` → `pat_wrap_rc` öffnen → **alter** Inhaltsschlüssel
→ je Einsatz `pat_blob` öffnen und mit dem **eigenen** Inhaltsschlüssel neu
verschliessen → zurück über den vorhandenen Endpunkt `api/backup_restore.php`.
Der letzte Schritt ist Absicht: Das Feld `daten` **ist** ein Backup — dieselbe
Nutzlast wie in einer `.edbak` (seit Web 8.0.0 Version 7) —, und ein zweiter
Rückspielpfad wäre eine zweite Stelle, an der dieselben Fehler zu machen sind.

### Aufbewahrung und Verdrängung (seit Web 9.8.0)

`edbak_verdraengen()` läuft nach **jedem** Sichern eines Kontos und entfernt,
was über der Aufbewahrung liegt. Keine Altersgrenze: Bei rein manueller
Auslösung würde sie genau das letzte vorhandene Backup entfernen, wenn lange
kein neues erzeugt wurde — also in der Lage, in der man es braucht.

**Zwei Pakete sind ausgenommen**, seit die Zahl einstellbar ist:

| Ausnahme | Grund |
|---|---|
| das **jüngste** Paket | Bei einer Aufbewahrung von 0 räumte das Sichern sonst alles weg, was es gerade angelegt hat — das gilt seit Web 12.0.0 auch dann, wenn es noch Fassung 1 ist |
| ein **freigegebenes** Paket | Die NutzerIn bekommt es im eigenen Backup-Bereich angeboten; es unter ihr wegzuräumen hieße, einen Weg anzubieten, der beim Klick ins Leere läuft |

**Fassung-1-Pakete gehen beim ersten neuen Lauf mit** (Entscheidung vom
31.08.2026) — aber erst, nachdem das neue Paket geschrieben **und wieder
gelesen** wurde (`edbak_paket_kopf_lesen()` als Gegenprobe in
`edbak_sicherung_erzeugen()`). Ein ZIP, das sich nicht öffnen lässt, ist genau
der Fall, in dem man den alten Stand noch braucht.

### Der Auftrag „Alle sichern" (seit Web 12.0.0)

Er läuft **in Schüben**: von der Schaltfläche, solange die Anfrage Zeit hat
(`SICHERN_BUDGET`, 20 s), und vom Wartungsjob `adminbackup` weiter. Der
Merkzettel steht in `app_state.adminbackup_auftrag` und ist **ein Zeiger,
keine Liste** — `app_state.v` ist `varchar(190)`, eine Liste von Kennungen
passt dort nicht hinein (Aufbau: `docs/Backup-Format.md` 5c).

Der Job arbeitet **nur auf Auftrag**; nächtliche Backups je Konto sind
ausdrücklich abgelehnt (E-S2-19). Seine Reserve ist mit 15 s so groß, dass er
am Huckepack-Weg (`JOB_BUDGET_ANFRAGE` = 3 s) gar nicht erst anfängt — eine
Anfrage einer NutzerIn soll kein fremdes Backup mittragen.

**Der Job-Rahmen misst seit Web 12.0.0 auch den Speicher**
(`jobs_speicher_knapp()`, `JOB_SPEICHER_DECKEL_MB` = 48 von 64). Bis dahin
zählte nur die Zeit; das reichte, solange jeder Job in Blöcken über Zeilen
lief. Der Backup-Job ist anders: Ein einzelnes Konto kostet beim
5000er-Bestand 24 MB, und das ist die Größe, an der es klemmt.

Die zweite Ausnahme folgt derselben Regel wie
`edbak_verzeichnis_abgleichen()`, das eine Freigabe auf eine nicht mehr
vorhandene Datei löscht: Eine Freigabe und die Datei dazu gehören zusammen.

### Die NutzerInnen-Liste (E-P3-41, seit Web 9.9.0)

`admin_users.php` zeigt vier Statuskacheln, eine Suche, fünf Filter, sechs
sortierbare Spalten und **50 Konten je Seite**. Zwei Kacheln, zwei Filter und
eine Spalte hängen am **Backup-Stand**, und der steht im Dateisystem.

Deshalb genau zwei Zugriffe je Seitenaufruf, beide unabhängig von der Zahl der
Konten je Zeile:

| | |
|---|---|
| eine Abfrage | alle Konten mit `LEFT JOIN devices` und `COUNT`; die `GROUP BY`-Liste nennt alle nicht aggregierten Spalten ausdrücklich, weil MySQL mit `ONLY_FULL_GROUP_BY` sonst abbricht, wo MariaDB durchlässt |
| ein Verzeichnisdurchlauf | `edbak_staende()`: ein `scandir` der Ablagewurzel plus je Ordner eine kleine `konto.json` |

Konten, die nie gesichert wurden, haben gar keinen Ordner und kosten nichts.
Gemessen an **304 Konten**: 3,2 ms Ablage, 3,3 ms Abfrage, 3,2 ms Werten, 103 ms
der ganze Aufruf.

**Gesucht, gefiltert und sortiert wird danach im Speicher.** Nicht aus
Bequemlichkeit: Zwei Filter (überfällig, nie gesichert) und eine Sortierung
kennen kein SQL. Eine halbe Filterung in SQL und eine halbe in PHP wären zwei
Wege für dieselbe Frage — und der zweite hätte die falschen Zahlen. Der Browser
bekommt in jedem Fall höchstens 50 Zeilen. Die Grenze davon steht in
`docs/Backlog.md` Nr. 37: Bei einigen tausend Konten braucht der
Backup-Stand eine Spalte in der Datenbank.

**Die Kacheln zählen den ganzen Bestand, die Filterzahlen die laufende Suche.**
Absicht: Die Kacheln sagen, wie es um die Installation steht; die Zahl an einer
Filterplakette beantwortet „was bringt mir dieser Filter jetzt?".

**Sortiert wird serverseitig**, weil eine Sortierung im Browser bei 50 Zeilen je
Seite eine Sortierung der *Seite* wäre. Der Zustand steht deshalb in der Adresse
(`?sort=…&dir=ab`), und die Spaltenköpfe sind Verweise mit `aria-sort` — die
erste Stelle im Bestand, die es trägt.

Der **Sortierschlüssel** schreibt Umlaute nach deutscher Lesart aus
(ae/oe/ue/ss, dieselbe Regel wie `slug()` in `assets/export.js`) und führt
übrige Akzente auf den Grundbuchstaben zurück. Ohne das stünde „Ömer" hinter
„Zeller": Kleingeschrieben wird aus Ö ein ö, und ö liegt in der Byte-Reihenfolge
hinter z. Bewusst **kein `Collator`** — die intl-Erweiterung ist auf geteiltem
Webspace nicht verlässlich da, und eine Sortierung, die je nach Installation
anders ausfällt, ist schlimmer als eine, die überall gleich näherungsweise ist.

**Die Auswahl der Sammelleiste liegt im `sessionStorage`**, nicht in der
Adresse: Eine Adresse mit dreihundert Kennungen wäre unbrauchbar lang und stünde
im Verlauf und im Zugriffsprotokoll des Servers. Beim Absenden wandert sie als
kommagetrennte Zeichenkette in ein verstecktes Feld. Nach einer ausgeführten
Sammelaktion wird sie geleert — sonst sicherte der nächste Klick dieselben
Konten noch einmal.

`app_state`-Marken werden **je Anfrage einmal** gelesen
(`edbak_marken_speicher()`, ein `static` hinter einer Funktion mit Rückgabe per
Referenz, damit `edbak_marke_setzen()` den neuen Wert nachziehen kann). Ohne das
holte die Liste das Erinnerungsintervall je Zeile aus der Datenbank: bei 304
Konten 304 Abfragen und 27,7 ms.

### Die Kontoseite (E-P3-41, seit Web 9.8.0)

Alles zu **einem** Konto liegt auf `admin_user.php?id=…`: Kontodaten (ein
Formular, ein Speichern), Geräte, die Backups **dieses** Kontos, ein
reservierter Platz für das Abonnement (R33) und die Löschung als
Gefahrenzone. `admin_sicherungen.php` behält die Regeln — und seit Web 9.10.0
nur noch sie (Abschnitt „Backups: was auf welcher Seite steht").

Der Grund ist nicht nur Bedienung, sondern Menge: `edbak_uebersicht()` liest
für **jedes** Konto ein Verzeichnis und eine Begleitdatei, um eine Zeile zu
zeigen — Arbeit, die mit der Zahl der Konten wächst, obwohl man immer nur ein
Konto ansieht. `edbak_konto_stand($userzeile)` liest genau einen Ordner und
liefert `stand` (`aktuell` · `ueberfaellig` · `nie` · `ohne_kennung`), die
Pakete, die Freigabe und das Alter des jüngsten Backups.

Der Zeitpunkt kommt dabei aus dem jüngsten **vorhandenen** Paket, nicht aus
`konto.json`: Wird eine Datei von Hand aus dem Ordner entfernt, bliebe die
Marke stehen und meldete einen Stand, den es nicht mehr gibt.

**Die Plakette „lesbar"** an einer Paketzeile ist eine echte Prüfung —
`edbak_paket_lesen()` liest die Datei und decodiert sie. Das ist vertretbar,
weil die Aufbewahrung die Zahl der Pakete je Konto begrenzt (Vorgabe drei) und
die Seite immer nur ein Konto zeigt. In einer Liste über alle Konten wäre
dieselbe Prüfung untragbar — deshalb steht sie hier und nicht dort.

**Einspielen, Freigeben und Löschen** stehen in Dialogen (`assets/dialog.js`):
Das Markup steht in der Seite, der öffnende Knopf trägt die Werte des Falls
(`data-w-datei`, `data-w-zeit`), das Skript setzt sie in die Felder mit
`data-fuell`. Ein Dialog für alle Zeilen statt eines je Zeile. Geprüft wird
weiterhin **serverseitig** — die abgetippte Adresse muss stimmen; ein
Browser-Dialog ließe sich umgehen.

Das Einspielen zielt auf **dieses** Konto. Ein Auswahlfeld mit allen Konten
stünde für einen Fall, den es auf dieser Seite nicht gibt: Wer ein Backup
in ein fremdes Konto bringen will, gibt es frei; ein Paket ohne Konto findet
man unter „Backups ohne Konto".

**Grenze, die im Handbuch steht und hier wiederholt gehört:** Ohne
Wiederherstellungsschlüssel ist ein neu aufgesetztes Konto nicht
wiederherstellbar. Das ist kein Mangel der Umsetzung, sondern die Folge der
Ende-zu-Ende-Verschlüsselung — der Schlüssel existiert nirgends sonst.

### Die Regelseite (seit Web 9.10.0)

`admin_sicherungen.php` trägt seit O9c nur noch, was für **alle** Konten gilt.
Die Aufteilung über die drei Seiten:

| Frage | Seite |
|---|---|
| Wie steht es um die Installation? Welche Regeln gelten? | `admin_sicherungen.php` |
| Welche Konten sind überfällig? Mehrere auf einmal sichern | `admin_users.php` (Filter `?f=ueberfaellig` / `?f=nie`) |
| Die Backups **eines** Kontos: einspielen, freigeben, löschen | `admin_user.php?id=…` |

Vier Kacheln (`edbak_stand_zaehlen()`, `edbak_ablage_zahlen()`), die Karten
**Regeln**, **Ablage** und — zugeklappt — **Backups ohne Konto**
(`edbak_verwaiste()`).

**Warum die Ablagezahlen hier Verzeichnisse lesen dürfen und in der Liste
nicht:** Eine Größe in Bytes steht in keiner Begleitdatei, sie steht nur an den
Dateien. Diese Seite existiert, um genau das zu beantworten, und wird selten
geöffnet; die Liste dagegen ist der Weg zu einem Konto und wird ständig
aufgerufen.

**„Alle sichern" hat ein Zeitbudget** (`SICHERN_BUDGET = 20.0` Sekunden), keine
Stückzahl. Die fälligen Konten werden nach Alter des letzten Backups
sortiert, das älteste zuerst. Wer nicht mehr hineinpasst, ist beim nächsten
Klick der älteste — die Reihenfolge sorgt selbst dafür, dass wiederholtes
Klicken konvergiert. Gemessen: 222 ms je Konto mit 82 Einsätzen, 7 ms für ein
leeres.

### Die wöchentliche Erinnerung an die Administration (seit Web 9.10.0)

**Es gibt keinen Cron.** Einziger Zeitgeber ist `run_cleanup_if_due()`
(`db.php`), der huckepack auf der ersten Anfrage des Tages läuft — aus
`auth_guard.php` (Web) oder `ingest.php` (Uhr). Die Erinnerung hängt dort als
letzter Aufräumschritt (`edbak_erinnerung_planen()`).

Daraus folgt, und das steht auch auf der Seite: höchstens einmal je Woche, nur
wenn es überfällige oder nie gesicherte Konten gibt, **und nur, wenn die
Anwendung an dem Tag benutzt wurde**. Wird sie zwei Wochen nicht angefasst,
kommt die Mail zwei Wochen später.

Der Schritt **plant** nur; verschickt wird nach der Antwort
(`register_shutdown_function`). Die Marke `adminbackup_mail_last` wird **vor**
dem Versand gesetzt: Der teurere Fehler ist die doppelte Mail, nicht die
ausgefallene — die nächste kommt in sieben Tagen.

**Inhalt:** Adressen und Alter, sortiert (nie gesichert zuerst, dann das
Älteste). Keine Namen, keine Zahlen aus den Konten — eine Mail liegt
unverschlüsselt im Postfach und auf jedem Server dazwischen.

Abschalten: Einstellungen → Backups → „Erinnerung an Admins per E-Mail".
Beim Einschalten wird `adminbackup_mail_last` geleert, damit die erste
Erinnerung nicht im Rhythmus einer abgeschalteten Zeit hängt.

### Das Logo der Installation (E-P3-19/20, einstellbar seit Web 9.10.0)

Drei Ebenen, von unten nach oben:

1. `LOGO_STANDARD_VORGABE` in `session_lib.php` — Hubschrauber. Gilt, solange
   es keine Datenbank gibt (Einrichter) oder nichts gesetzt ist.
2. `app_state.logo_standard` — der Standard **dieser Installation**, gesetzt in
   der Wartung (`update.php`). `logo_standard()` liest ihn je Anfrage einmal
   und fängt jede Ausnahme ab: Das Logo ist Zierde, kein Zugang.
3. `users.logo_wahl` — die Wahl **eines Kontos** (`''` = folgt dem Standard,
   `hubschrauber`, `fahrzeug`, `wechselnd`).

**In der Sitzung steht die Wahl, nicht ihr Ergebnis.** `logo_sitzung_setzen()`
löst nur `wechselnd` auf (dort fällt der Würfel je Anmeldung, sonst spränge das
Logo beim Blättern); der Leerstring bleibt stehen und wird erst in
`logo_stamm()` aufgelöst. Damit wirkt eine Umstellung des Standards **sofort**,
auch für bereits angemeldete Konten — und nur bei denen, die keine eigene Wahl
getroffen haben.

**`logo_src()`** ist die Fassung für die beiden Seiten **ohne** Sitzung
(Anmeldung, Passwort setzen). Sie folgt seit Web 9.10.0 ebenfalls der Wahl;
`$CFG['app']['logo_path']` gewinnt nur noch, wenn dort eine **fremde** Datei
steht (F-P3-AN). `pw_handling.php` lädt dafür `session_lib.php`.

**Der Platzhalterhinweis** auf der Wartungsseite fragt die Datei, nicht eine
Zahl im Code: `logo_platzhalter_liegt()` liest die ersten 400 Byte von
`gen-em_logo_fahrzeug.svg` und `…_weiss.svg` und sucht das Wort „PLATZHALTER"
im Kopfkommentar. Er verschwindet damit von selbst, sobald die echten Dateien
liegen — sie ersetzen den Platzhalter 1:1 (gleicher Name, gleicher `viewBox`).


### Rechtstexte: Impressum und Datenschutz (R32, seit Web 9.11.0)

**Die Anwendung liefert keinen Rechtstext mit.** Was darin steht, ist Sache des
Betreibers; die Anwendung stellt zwei öffentliche Seiten, einen Editor und die
Verweise in jeder Fußzeile. Der Leerzustand ist die Auslieferung.

| Datei | Aufgabe |
|---|---|
| `rechtstexte_lib.php` | Ablage (`rt_lesen`, `rt_speichern`, `rt_pruefen`) und der Renderer `rt_html()` |
| `rechtstext_seite.php` | Die öffentliche Seite — beide Dokumente teilen sie sich |
| `impressum.php`, `datenschutz.php` | Zwei Zeilen: Schlüssel setzen, Seite laden |
| `admin_rechtstexte.php` | Editor, ein Formular für beide Texte |
| `tools/rechtstexte/` | Angriffsprobe für `rt_html()` |

#### `rt_html()` — erst maskieren, dann Struktur erkennen

Die einzige Stelle des Projekts, an der aus einer Eingabe HTML wird. Der Ablauf:

1. **Säubern** — CRLF zu LF; C0-Steuerzeichen außer `\n` und `\t`; Zero-Width
   (U+200B–200F, U+FEFF); Zeilen- und Absatztrenner (U+2028/2029);
   **Bidi-Steuerung** (U+202A–202E, U+2066–2069). Letztere sind kein Zierrat:
   Mit ihnen lässt sich ein Linktext bauen, der etwas anderes anzeigt, als im
   Ziel steht („Trojan Source").
2. **UTF-8 prüfen** — schlägt es fehl, kommt der Leerstring zurück.
3. **Maskieren** — `htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`,
   genau einmal, über den ganzen Text. **`ENT_SUBSTITUTE` ausdrücklich**, anders
   als `e()` in `db.php`: Ohne den Schalter liefert PHP seit 8.1 bei ungültigem
   UTF-8 den Leerstring — bei einem Feldwert unauffällig, bei einem Rechtstext
   verschwände die ganze Seite wortlos.
4. **Blöcke zeilenweise** — `#`/`##` → `<h2>`, `###` → `<h3>`, `- `/`* ` →
   `<ul>`, `1. ` → `<ol>`, Leerzeile trennt Absätze, mehrere Zeilen ohne
   Leerzeile bleiben **ein** Absatz mit `<br>` (im Impressum stehen Anschriften
   so untereinander).
5. **Inline** — genau ein Muster, `[Text](Ziel)`.

**Erzeugt werden ausschließlich** `h2 h3 p br ul ol li a` und **ein** Attribut
(`href`). Das ist keine Absichtserklärung, sondern geprüft: Die Angriffsprobe
hält jede Ausgabe gegen diese Liste.

**Linkziele stehen auf einer Positivliste** (`rt_ziel_erlaubt()`): `https://`,
`http://`, `mailto:`, eine eigene `.php` mit optionalem Abfrageteil, ein Anker.
Alles andere fällt durch — auch protokollrelative Adressen wie
`//fremde.example/…`, die relativ aussehen und es nicht sind. Ein abgelehntes
Ziel lässt die ganze Konstruktion **als Text** stehen; stilles Schlucken machte
aus einem Fehler eine Unsichtbarkeit.

#### Die öffentliche Seite kennt die Sitzung, ohne sie zu erzwingen

`rechtstext_seite.php` lädt **nicht** `auth_guard.php` — der leitet
Nichtangemeldete auf die Anmeldung um, und das ist bei einem Impressum falsch.
Sie ruft stattdessen selbst `session_start()` (das nimmt ein vorhandenes Cookie
an und meldet niemanden an) und liest die Rolle **aus der Datenbank**, nicht aus
der Sitzung — dieselbe Regel wie im Guard (M1-05): Eine zurückgenommene
Adminrolle würde sonst bis zur nächsten Anmeldung weitergelten.

Ohne `config.php` leitet sie auf `install.php` um, wie `login.php` es tut. Ein
Impressum ist das erste, was jemand auf einer frischen Installation aufruft — es
darf keinen weißen Fehler zeigen.

#### Grenzen

- **Keine Content-Security-Policy.** Backlog Nr. 8 bleibt offen; sie wäre die
  zweite Verteidigungslinie hinter dem Renderer.
- **Kein Versionsstand der Texte.** Wer den Text überschreibt, überschreibt ihn;
  eine Historie gibt es nicht. Für ein Dokument, dessen alte Fassung
  rechtlich zählen kann, ist das eine bewusst offene Stelle.
- **Die Vorschau zeigt den gespeicherten Stand**, nicht das Getippte.
