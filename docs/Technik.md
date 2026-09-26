# Gen-EM NAdoku — Technische Dokumentation

*Stand: 20.09.2026 · Bedienung: `Handbuch.md` · Schnittstelle: `JSON-Vertrag.md` ·
Historie: `CHANGELOG.md`.*

## 1. Architekturüberblick

```
┌─────────────────┐  HTTPS POST /ingest.php   ┌──────────────────────────┐
│ Uhr-App         │  JSON (JSON-Vertrag)      │  Webspace                │
│ (derzeit Garmin,│ ────────────────────────► │  PHP ≥ 8.2  + MySQL      │
│  Monkey C)      │  X-Device-Id / X-Api-Key  │                          │
└─────────────────┘                           │  ingest.php   (Uhr-API)  │
                                              │  api/…        (Lese-API) │
┌─────────────────┐  HTTPS (Session-Login)    │  *.php        (Seiten)   │
│ Browser         │ ────────────────────────► │  betrieb_*.php (Betrieb) │
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
│   │                       Einstieg der Wartung huckepack (run_cleanup_if_due).
│   │                       Verlangt config.php hart — die globale $CFG gibt
│   │                       es seit Web 20.27.0 nicht mehr. Bindet
│   │                       transaktion_lib.php ein
│   ├── transaktion_lib.php Der EINE Transaktionsrahmen db_transaktion()
│   │                       (Schritt 15 AP5, E-ZE-20). Bis Web 20.37.2 in
│   │                       db.php; eigene Datei seit 20.37.3, weil der
│   │                       Einrichter ihn ohne config.php braucht (Nr. 288).
│   │                       LAEDT SELBST NICHTS
│   ├── konfig_lib.php     Der EINE Leser fuer config.php (Schritt 15 AP2,
│   │                       E-ZE-02/-14): konfig('app.timezone'),
│   │                       konfig_alles(), konfig_verwerfen(). Punktpfad,
│   │                       Merker in einer static, fehlende Datei = Vorgabe.
│   │                       LAEDT SELBST NICHTS — Bedingung, nicht
│   │                       Sparsamkeit: install.php und sitzung_lib.php
│   │                       brauchen ihn, ohne db.php zu laden
│   ├── ui.php             Seitenhülle (ui_seite_start/-_ende), Kopf-/Seitenleisten,
│   │                       Fußzeile, Meldungszeile, Abbruchseite, Krypto-Rüstzeug,
│   │                       seit Web 20.38.0 die Reihe der Streifen über dem
│   │                       Inhalt (ui_hinweise(): Umgebung, Ankündigung, Demo,
│   │                       Datenschutz)
│   ├── umgebung_lib.php   Das Umgebungsetikett aus config.php (umgebung(),
│   │                       umgebung_praefix(), UMGEBUNG_FARBEN; P5c/AP1,
│   │                       E-P5c-05). LAEDT NUR konfig_lib.php — die
│   │                       Seitenhülle zieht es auch im Einrichter nach
│   ├── ankuendigung_lib.php  Ankündigung (drei app_state-Zeilen) und Rundmail
│   │                       an die erreichbaren Konten (P5c/AP1, E-P5c-13)
│   ├── ankuendigung.php   Das × im Streifen: merkt in der Sitzung, dass DIESE
│   │                       Ankündigung geschlossen ist. Ohne auth_guard —
│   │                       der Streifen steht auch über der Anmeldung
│   ├── auth_guard.php     Session/Rollen (Rolle+Existenz je Anfrage aus der DB,
│   │                       Sitzungszähler, ist_admin(), csrf_check()); seit
│   │                       Web 20.42.0 das Einrichtungstor des Zweitfaktors
│   │                       und 401 JSON für die API ohne Anmeldung (4.99q)
│   ├── totp_lib.php       Der Zweitfaktor: RFC 6238, Geheimnis versiegelt,
│   │                       Wiederherstellungscodes gehasht (P5c/AP5, 4.99q)
│   ├── rueckweg_lib.php   Der Rückweg beim Zweitfaktor über den
│   │                       Wiederherstellungsschlüssel: ECDSA-Prüfung
│   │                       (phpseclib), Nachricht, Selbsttest mit Marke,
│   │                       Stand des Paars je Konto (rw_zustand(); Konzept
│   │                       RW, 4.99q)
│   ├── zweitfaktor.php    Das Einrichtungstor für Pflichtrollen, in der
│   │                       Anmeldehülle (E-P5c-61)
│   ├── zweitfaktor_teile.php  QR, Geheimnis, Codefeld und Codeliste — EIN
│   │                       Markup für Tor und Profilkarte
│   ├── codeblatt.php      die zehn Wiederherstellungscodes auf Papier
│   │                       (`.blatt-druck`). SPEICHERT NICHTS — nicht
│   │                       nachdruckbar
│   ├── auth_salt.php      KDF-Salt (mit Pseudo-Salt gegen User-Enumeration)
│   ├── login/logout/reset_request.php   Auth-Flows; login.php seit Web 20.42.0
│   │                       mit dem Code-Schritt VOR der Sitzung (4.99q)
│   ├── pw_handling.php    Passwortvergabe über Einmal-Link: Erstvergabe (erzeugt
│   │                      Inhalts- + Wiederherstellungsschlüssel) und Reset
│   ├── index.php          Tagesübersicht (Karte + Tabelle)
│   ├── einsatz.php        Einsatzansicht · einsatz_form.php Nachtragen/Bearbeiten
│   ├── zeitraum.php       Jahres-/Monatsübersicht (Karte, Statistik, Tabelle)
│   ├── suche.php          Suche über den gesamten Bestand (filtert im Browser, s. u.)
│   ├── format_lib.php     Die EINE Stelle, an der aus einem Wert ein Text
│   │                       fuer Menschen wird (Schritt 15 AP7, ab Web
│   │                       20.32.0): Groesse, Zahl, Anteil, Datum, Uhrzeit,
│   │                       relative Zeit, die ISO-UTC-Marke und „heute".
│   │                       Laedt NUR konfig_lib.php — install.php erreicht
│   │                       sie ueber plattform_lib.php, bevor es eine
│   │                       config.php gibt. fmt_local() liegt seither hier
│   ├── mission_fields.php Zentraler Feldkatalog der Zusatzfelder
│   ├── mission_fields_lib.php  Abgeleitete Sichten auf den Feldkatalog
│   │                       (mf_tagesspalten() = Spalten der Tagestabelle,
│   │                        mf_optionen() = Wert/Beschriftung eines Auswahlfelds,
│   │                        mf_ort_spalten() = Koordinatenspalten eines Ortsfelds,
│   │                        mf_show_if() + mf_gates_erfuellt() = Sichtbarkeit,
│   │                        mf_missions_register() + mf_spalten() = das
│   │                        Spaltenregister von `missions`, Schritt 15 AP6)
│   ├── tageszuordnung_lib.php  Einsatz verschieben · Datum eines Tages ändern
│   ├── einsatz_verschieben.php  die zugehörige Seite
│   ├── einstellungen.php  Profil/Standorte/Backup/Geräte (Reiter `?t=`)
│   │                       Seit Web 16.2.0 führt `t=standorte` auf die LISTE
│   │                       der Standorte und `t=standort&s=<id>` auf die
│   │                       Seite EINES Standorts mit allem, was daran hängt;
│   │                       die Karte „Ohne Standort" steht seit Web 16.2.2
│   │                       auf der Liste. Zwei Weichen: `t=stammdaten` (der
│   │                       alte, geteilte Punkt „Standortdaten") und
│   │                       `t=rettungsmittel` (der bis Web 16.1.1 eigene
│   │                       Reiter) führen beide auf die Liste
│   ├── import.php         Import/Export (eigene Seite, erscheint als Eintrag
│   │                      der Einstellungs-Leiste)
│   ├── admin_users.php + admin_user.php  NutzerInnen (Liste · Kontoseite)
│   │                       Die Liste ist seit Web 9.9.0 serverseitig gesucht,
│   │                       gefiltert und seitenweise (50 je Seite), mit
│   │                       Statuskacheln und Sammelleiste
│   │                       Die Kontoseite ist seit Web 9.8.0 die Drehscheibe
│   │                       eines Kontos: Kontodaten, Geräte, Konto-Backups
│   │                       dieses Kontos mit Freigabe-Zustandszeile, Löschung
│   ├── stammdaten_ui.php  Zeile, Dialoge und Adresse der Stammdatenlisten
│   │                       fuer einstellungen.php, seit Web 9.10.0. Bis
│   │                       Web 17.1.1 diente sie ZWEI Ansichten — die
│   │                       zweite war admin_stammdaten.php (systemweite
│   │                       Stammdaten), mit Web 18.0.0 gestrichen (R39).
│   │                       `sd_zeile()`, `sd_seite()`, `sd_oeffner()` und die
│   │                       drei Dialogfunktionen; `sd_form()` ist mit
│   │                       Web 17.0.0 entfallen — angelegt wird im Dialog
│   ├── diensttag_neu.php  Diensttag von Hand anlegen · diensttag_datum.php Datum ändern
│   │                       · diensttag_zusammenfuehren.php  mehrfach gestartete Dienste
│   │                         wieder zu einem Diensttag vereinen
│   ├── diensttag_lib.php  Diensttage anlegen, zuordnen, einfrieren, auflisten
│   ├── einsatz_lib.php    Einsatz per Kennung laden, mit Besitzprüfung und
│   │                       Papierkorb-Option (Schritt 15/AP4, ab Web 20.29.0);
│   │                       dazu seit Web 20.30.0 die vier Kindtabellen
│   │                       (Phasen, Reanimation, Rettungsmittel, Besatzung)
│   ├── nachbearbeitung.php + nachbearbeitung_lib.php  einmalige Nachträge nach der Migration
│   ├── einsatz_loeschen.php · diensttag_loeschen.php · papierkorb.php  Löschen mit Vorschau
│   ├── ingest.php         Uhr-/Fremdquellen-Endpunkt (Auth, Idempotenz)
│   ├── pair.php           Gerätekopplung: VIER Anliegen an einem Endpunkt —
│   │                      start, status, bestaetigen, trennen (JSON-Vertrag
│   │                      1a/1b). Seit Web 13.0.0 zeigt das GERÄT den Code,
│   │                      ein Mensch gibt ihn im Web ein, das Gerät bestätigt
│   ├── geraete_lib.php    Liest den Block `geraet` einer Kopplung — die
│   │                      EINZIGE Stelle, die ihn auslegt (Uhr- und
│   │                      Handy-Form), und die Beschriftungen der Gerätelisten
│   ├── geraetemodelle.php Teilenummer → Modellname und Geräteart. **ERZEUGT**
│   │                      (`tools/erzeugen/ (geraetemodelle)`), nicht von Hand ändern
│   ├── geraetemodelle_lib.php
│   │                      Der Nachlöse-Kern (P5a/AP11, E-P5a-21): Fingerabdruck
│   │                      der Tabelle, Stand in `app_state`, `gm_nachaufloesen()`
│   │                      in Blöcken. Job UND Skript benutzen dieselbe Fassung
│   ├── spur_lib.php       Spurpunkte lesen und schreiben — die EINZIGE Stelle,
│   │                       die `track_points`/`track_blobs` anfasst (4.97)
│   ├── gpx_lib.php        GPX 1.1 aus einer oder mehreren Spuren — die
│   │                       EINZIGE Stelle, die GPX schreibt (4.97b)
│   ├── gpx.php            der Abruf. Bewusst NICHT unter api/: Ein Link, den
│   │                       eine NutzerIn anklickt, braucht bei Sitzungsende
│   │                       die Anmeldeseite und kein JSON (4.97b)
│   ├── tag_spuren.php     die Spuren eines Diensttages, einzeln oder mehrere
│   │                       auf einmal abrufbar: Karte plus chronologische
│   │                       Liste, Einsätze UND Ruhesegmente (4.97b)
│   ├── jobs.php           Einstieg der Hintergrundjobs: Kommandozeile, Adresse
│   │                       mit Token, huckepack auf einer Anfrage (4.97a)
│   ├── einwilligung_lib.php
│   │                      Welche Rechtstexte dieses Konto noch annehmen oder
│   │                      zur Kenntnis nehmen muss (P5b/AP4). Zwei sperren
│   │                      den Login, eine weist nur hin — der Unterschied ist
│   │                      die Rechtsnatur, nicht der Rang
│   ├── konto_lib.php      Lebenszyklus eines Kontos: anlegen, Token ausstellen,
│   │                      Status wechseln, loeschen (P5b/AP2). Die EINE Stelle
│   │                      fuer vier — Backlog Nr. 202 Paket 1. Laeuft auch
│   │                      ohne config.php, weil install.php sie braucht;
│   │                      laedt transaktion_lib.php deshalb unbedingt
│   ├── konten_einstellungen_lib.php
│   │                      Betriebsart der Registrierung, Fristen, Mengengrenzen,
│   │                      Demo-Anmeldung — die Werte der Karte „Konten"
│   │                      (P5b/AP1). Bibliothek und nicht Seite, weil die
│   │                      Verbraucher woanders sitzen: registrieren.php,
│   │                      login.php, ingest.php, der Verfalljob (R83)
│   ├── protokoll_lib.php  Das Betriebsprotokoll — Schreibweg, sechs Reiter,
│   │                      zwei Fristen, Bereinigung (P5b/AP1); seit Web
│   │                      20.39.0 auch der Leseweg: sieben Reiter der
│   │                      Seite, je Reiter eine bis drei Quellen (nie ein
│   │                      UNION), Katalog Art → Wort und Ton. KEIN
│   │                      Zugriffsprotokoll: siehe 4.99g
│   ├── systemmeldung_lib.php  Das Fehlerprotokoll (P5c/AP3): system_melden()
│   │                      für jede Stelle, die bis Web 20.39.0 error_log()
│   │                      rief; die drei Behandler, die db.php einrichtet;
│   │                      die Fehlerseite mit Kennung und Meldeweg. Lädt
│   │                      nichts; ohne Datenbank der Rückfall (4.99g)
│   ├── protokoll_archiv_lib.php  Das Archiv des Protokolls (P5c/AP2):
│   │                      alle 7 Tage ein versiegeltes ZIP nach
│   │                      sicherungen/protokoll/, in Häppchen; Aufbewahrung,
│   │                      Download entsiegelt. Ohne IP-Adressen (4.99g)
│   ├── admin_protokoll.php  Verwaltung → Protokoll: Reiter nach Rolle,
│   │                      Suche, Zeitraum, Art, Seitenwahl; Reiter Archiv
│   │                      nur für die BetreiberIn
│   ├── zip_lib.php        Der EINE Weg zu ZipArchive (P5c/AP2, R83):
│   │                      bauen (ungepackt oder gepackt), einen Eintrag
│   │                      lesen, lesend öffnen. Registerzeile Z39
│   ├── jobs_lib.php       Katalog und Ausführung der Jobs (Häppchen, Zustand,
│   │                       Sperre)
│   ├── backup_lib.php     Backup-Serialisierung (Kern mit oder ohne Spuren)
│   │                       · trash_lib.php Papierkorb-Logik
│   ├── adminbackup_lib.php  Konto-Backups: Ablage (ZIP, Fassung 3 — jeder
│   │                       Eintrag gzip UND mit dem Serverschlüssel
│   │                       versiegelt, die Begleitdatei konto.json daneben
│   │                       ebenso; der Siegelzweck bindet Konto, PAKETNAME
│   │                       und Teil, ein umbenanntes Paket ist unlesbar),
│   │                       Übersicht, Freigabe, Speichergrenze, Auftrag (A8, S2/AP6, S10/AP4)
│   ├── admin_sicherungen.php  Adminseite dazu — seit Web 9.10.0 nur noch
│   │                       Regeln, Ablage und Backups ohne Konto;
│   │                       die Konten stehen in admin_users.php, die
│   │                       Pakete eines Kontos auf dessen Kontoseite
│   │                       · sicherungen/ die Ablage selbst
│   │                       (entsteht nur auf dem Server, im Deploy ausgenommen)
│   │                       · sicherungen/komplett/ die Komplett-Backups
│   │                       · sicherungen/protokoll/ die Archive des
│   │                         Protokolls (seit Web 20.39.0)
│   │                       · sicherungen/eingang/ was wiederhergestellt
│   │                         werden soll — von Hand dorthin gelegt
│   ├── sicherungsziel_lib.php  Backup-Ziele (S2/AP7): Schnittstelle
│   │                       `Zielweg` und zwei Adapter — FTPS über ext/ftp,
│   │                       SFTP über phpseclib. `ftp` ist seit Web 20.2.0
│   │                       abgeschafft (S10/AP4), seit Web 21.0.0 auch im
│   │                       Schema (P5c/AP8). Dazu Pflege in der
│   │                       Tabelle backup_targets, „Verbindung prüfen" und
│   │                       der Versandschub
│   ├── admin_sicherungsziele.php  BetreiberIn-Seite dazu (Nr. 286): Ziele anlegen und prüfen,
│   │                       Serverschlüssel nachtragen, Versand ein/aus
│   ├── komplett_lib.php   Komplett-Backup der Installation (S2/AP8):
│   │                       eigener SQL-Dump in Häppchen (ein Statement je
│   │                       Zeile, INSERT-Stapel bis 1 MB, einspielbare
│   │                       Reihenfolge), gzip, Siegel EDKOMP1; dazu Ablage,
│   │                       Aufbewahrung, Zeitplan und die Wege heraus
│   ├── admin_komplettsicherung.php  BetreiberIn-Seite dazu (Nr. 286): erzeugen mit Fortschritt,
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
│   ├── php_mindest.php    die Weiche: PHP zu alt → lesbare Seite statt
│   │                      Parse-Fehler (P5a/AP2). Lädt NICHTS
│   ├── plattform_lib.php  Plattformprüfung — was die Anlage kann und was
│   │                      sie können muss (P5a/AP2, R81); Status und
│   │                      install.php lesen dieselbe Funktion
│   ├── ratelimit_lib.php  Ratenschutz (Konto + IP, in der Datenbank)
│   ├── instanz_lib.php    Der Name dieser Installation (P5a/AP5, Web 20.8.0):
│   │                       instanz_name() lang (Mailbetreff, Grussformel),
│   │                       instanz_kurz() kurz (Browsertab, Kopfleiste).
│   │                       LAEDT NICHTS — install.php, die Wartungsseite und
│   │                       das HTTPS-Tor brauchen die Vorgaben ohne
│   │                       Datenbank. Gepflegt unter Verwaltung -> Installation
│   ├── kopfzeilen_lib.php  Sicherheitskopfzeilen und CSP (P5a/AP4, Web 20.7.0):
│   │                       kopfzeilen_seite() in ui_seite_start(),
│   │                       kopfzeilen_json() in json_out() — EINE Stelle,
│   │                       deshalb auf jedem Webserver. Nonce je Anfrage,
│   │                       csp_scharf und hsts_tage als Einstellung,
│   │                       https_tor(). Siehe Abschnitt 5c
│   ├── netz_lib.php       Client-Adresse hinter einem Reverse Proxy (P5a/AP4):
│   │                       X-Forwarded-For NUR von vertrauenswuerdigen
│   │                       Proxys (config.php, netz.vertrauenswuerdige_proxys),
│   │                       byteweiser CIDR-Vergleich, letzter Eintrag der
│   │                       Kette. Dieselbe Liste traegt X-Forwarded-Proto
│   ├── api/csp_bericht.php  Sammelstelle fuer CSP-Meldungen (P5a/AP4). OHNE
│   │                       Anmeldung — ein Verstoss auf der ANMELDESEITE ist
│   │                       der interessanteste von allen. Topf `csp`,
│   │                       zusammengefasst per UNIQUE, ohne IP und ohne
│   │                       Abfrageteil der Adresse; Antwort immer 204
│   ├── api/health.php    Zustand der Anlage fuer das eigene Monitoring (P5c/AP6,
│   │                       4.99r). OHNE Anmeldung, mit Token aus config.php
│   │                       (betrieb.health_token, leer = aus); 200/503 mit
│   │                       acht Feldern, 403 einheitlich, Topf `health`
│   ├── api/kopplung_stand.php  Wartet dieses Konto noch auf ein Gerät, und
│   │                       hat es Ja gesagt? (S5, Web 13.1.0) GET, nimmt
│   │                       KEINE Eingabe — welche Sitzung gemeint ist, steht
│   │                       in der PHP-Sitzung. Fünf Zustände; das Skript
│   │                       assets/kopplung.js fragt im Takt und hört von
│   │                       selbst auf
│   ├── assets/kopplung.js  Die Geräteseite lädt nach, wenn das Gerät Ja
│   │                       gesagt hat (E-S5-53). Ohne sie bleibt der Weg
│   │                       vollständig — sie nimmt einen Handgriff ab
│   ├── kopplung_lib.php   Kopplungssitzungen (S5, Web 13.0.0): Frist-SQL,
│   │                       Anlegen mit Dublettenschleife, Suche nach Kennung
│   │                       und Code, Beanspruchen per UPDATE mit rowCount —
│   │                       die eine Auslegung für pair.php, einstellungen.php
│   │                       und die Kopplungsprobe
│   ├── wartung_lib.php    Wartungsmodus (S5 Paket W, Web 13.2.0): Schalter,
│   │                       Tor, Wartungsseite, 503-JSON, Balken,
│   │                       Ausnahmeliste. Lädt NICHTS — der Zustand ist eine
│   │                       Datei (`wartung.lock`), damit er auch bei
│   │                       umgebauter Datenbank greift. Seit Web 20.13.0
│   │                       steht die ZWEITE Störung daneben (P5a/AP9,
│   │                       Abschnitt 5e): „ausgelastet" — Erkennung der
│   │                       Fehlernummern, Zähler `ueberlast.json`, 503-Weg
│   │                       und das gemeinsame Gerüst beider Störungsseiten.
│   │                       Aus demselben Grund am selben Ort: Beide müssen
│   │                       ohne Datenbank antworten
│   ├── betrieb_sicherheit.php
│   │                       Betrieb → Status → **Sicherheit** (P5a/AP8,
│   │                       E-P5a-08): fünf Karten — aktive Sperren mit
│   │                       Knopf „Aufheben", Verlangsamung, Mengenbremse
│   │                       der Geräte, Ereignisse der letzten 30 Tage,
│   │                       Mailregel. UNTERSEITE, kein Menüpunkt: sie
│   │                       hält über `menue => betrieb_status` den Eintrag
│   │                       der Elternseite aktiv, wie `admin_user.php`.
│   │                       Die einzige Seite des Betriebsbereichs mit
│   │                       Sperrrecht — und die einzige, die etwas ändert
│   ├── betrieb_status.php  Betrieb → Status: die Karten aus status_lib.php
│   ├── logout.php         Abmelden (die Räumung steht in session_lib.php)
│   ├── betrieb_statistik.php
│   │                       Betrieb → Statistik: drei Reiter NutzerInnen ·
│   │                       Einsätze · Geräte (S8/AP4; P5c/AP7, Nr. 80, 191)
│   ├── statistik_lib.php  die Fenster der Statistik und ihre eine Zählung
│   │                      der Einsätze — Seite und Messstand lesen dieselbe
│   │                      Abfrage (P5c/AP7)
│   ├── status_lib.php     die Karten der Statusseite an einer Stelle —
│   │                      Server, Backups, Plattform, Sicherheit. In P5a
│   │                      viermal erweitert (AP2, AP9, AP10, AP11)
│   ├── site_elevation_lib.php
│   │                       Höhe über dem Einsatzort (`site_ele_m`)
│   ├── betrieb_updates.php  Betrieb → Updates (S8/AP2): Wartungsmodus,
│   │                       ausstehende Migrationen mit Vorschau und Lauf,
│   │                       ausgeführte Migrationen, Fassung
│   ├── betrieb_jobs.php   Betrieb → Hintergrundjobs (S8/AP2): Zustand je Job,
│   │                       die drei Auslöser mit Befehl und Adresse zum
│   │                       Kopieren (assets/kopieren.js), Regeln
│   ├── betrieb_server.php  Betrieb → Servereinstellungen (S8/AP2): Speicher
│   │                       der Installation als Balken, Grenze und Schwellen
│   │                       der Konto-Backups, Webspace-Angabe; seit S10/AP3
│   │                       die Karte „Schlüssel des Servers" (seit P5c/AP1
│   │                       unter der Ankündigung)
│   │                       (Serverschlüssel und Server-Anteil: anlegen,
│   │                       wechseln, alten entfernen, nachtragen, Neuanfang
│   │                       — genannt wird nur die Kennung, nie der Wert)
│   ├── betrieb_schluesselblatt.php
│   │                       Das Schlüsselblatt (S10/AP3): die EINE Seite,
│   │                       deren Zweck der Ausdruck ist. Ohne Gerüst, ohne
│   │                       Zwischenspeicher (`no-store`, `no-referrer`,
│   │                       `noindex`), Werte in nummerierten Vierergruppen,
│   │                       eine A4-Seite (`.blatt-druck`, P5c/AP9). Sie trägt
│   │                       beide Geheimnisse im Klartext — der zweite Ort,
│   │                       der überleben soll, was `config.php` nicht
│   │                       überlebt
│   ├── assets/blatt-drucken.js
│   │                       Sechs Zeilen: Der Knopf „Drucken" ist ohne
│   │                       JavaScript verborgen und ruft `window.print()`.
│   │                       Ein Knopf, der ohne Skript nichts tut, ist
│   │                       schlimmer als keiner
│   ├── speicher_lib.php   Was die Installation belegt (S8/AP2): Datenbank aus
│   │                       information_schema, Dateien per Verzeichnislauf,
│   │                       Stand in settings, Ton nach Schwellen. Gemessen
│   │                       wird im Aufräumjob, nicht beim Seitenaufruf
│   ├── assets/kopieren.js  Der Knopf „kopieren" an einem Wertekasten —
│   │                       Zwischenablage mit Rückfall auf Markieren; ohne
│   │                       JavaScript bleibt der Wert lesbar und markierbar
│   ├── assets/ankuendigung.js  Schließt die Ankündigung ohne Neuladen, wo die
│   │                       Seite ein Token trägt (sonst schickt das Formular
│   │                       selbst ab), und zählt im Formular die Byte mit
│   ├── assets/rechtstext_vorschau.js  Vorschau beim Tippen auf der Seite
│   │                       Rechtstexte: 0,4 s Ruhe, dann EdApi an den
│   │                       Endpunkt; nur die jüngste Antwort zählt; Plakette
│   │                       sagt, was dasteht (P5c/AP9)
│   ├── assets/reiter.js  Holt den aktiven Reiter ins Bild und setzt den
│   │                       Verlauf am Rand der rollenden Reiterreihe
│   │                       (P5c/AP2); ohne Skript rollt sie trotzdem
│   ├── assets/listenkopf.js  Das Auswahlfeld im Listenkopf schickt sofort
│   │                       ab und verbirgt den Knopf „Filtern“ (P5c/AP2)
│   ├── session_lib.php    Sitzungsende mit Räumung im Browser (Abmelden, Ablauf,
│   │                       gelöschtes Konto, Passwortwechsel); dazu seit
│   │                       Web 20.28.0 flash_setzen()/flash_holen() — die
│   │                       Meldung, die eine Umleitung überdauert
│   ├── sitzung_lib.php    WO die Sitzungen liegen — nicht, wie sie enden
│   │                       (Schritt 16, E-SA-01 bis -07; Backlog Nr. 241).
│   │                       Legt `.sitzungen/` mit 0700 an, richtet
│   │                       `session.save_path` darauf und stellt PHPs
│   │                       Zufallsräumung ab; trägt `SESSION_TIMEOUT_S` und
│   │                       den Räumteil des Aufräumjobs. LÄDT NICHTS —
│   │                       `db.php` ruft sie ohne Datenbankverbindung,
│   │                       `install.php` vor seinem `session_start()`.
│   │                       Nicht zu verwechseln mit der Zeile darüber
│   │                       · .sitzungen/ die Sitzungsdateien selbst
│   │                       (entstehen nur auf dem Server, im Deploy
│   │                       auszunehmen — Eintrag bei Kette II angemeldet).
│   │                       Gegen Abruf über die Adresszeile deckt sie die
│   │                       Punktregel in `.htaccess`, auf nginx allein 0700
│   ├── email_lib.php      E-Mail: Normalisierung, Prüfung, Dublettenerkennung
│   │                       (ohne Abhängigkeiten — auch für install.php)
│   ├── mail_lib.php       Nachrichtenkatalog, Warteschlange, Job `mail`
│   │                       (P5a/AP5, Web 20.8.0/20.9.0): zehn Einträge mit
│   │                       Betreff und Text, `mail_einreihen()` als einziger
│   │                       Versandweg, Wiederholungsleiter über 24 h,
│   │                       `mail_betriebsziele()` — der einzige Aufrufer von
│   │                       smtp_send() ausserhalb von smtp.php selbst
│   ├── impressum.php · datenschutz.php   die beiden OEFFENTLICHEN Seiten
│   │                      (R32) — zwei Zeilen je Datei, der Rest steht in
│   │                      rechtstext_seite.php
│   ├── rechtstext_seite.php  die gemeinsame Seite dahinter: liest die Sitzung,
│   │                      ohne sie zu erzwingen (Leerzustand mit Admin-Weg)
│   ├── rechtstexte_lib.php   Ablage, Pruefung und der eingeschraenkte
│   │                      Markdown-Renderer rt_html() — fuer Text aus der
│   │                      DATENBANK; die zweite Stelle, an der HTML entsteht,
│   │                      ist doku_lib.php (Text aus dem REPOSITORIUM)
│   ├── hilfe.php · ueber.php   Handbuch und „Was ist NAdoku" als Seiten der
│   │                      Anwendung (P5b/AP8) — oeffentlich, ohne Anmeldung;
│   │                      drei Zeilen je Datei, der Rest in doku_seite.php
│   ├── doku_seite.php    die gemeinsame Seite dahinter (Muster wie
│   │                      rechtstext_seite.php)
│   ├── doku_lib.php      findet die Markdown-Datei (erst ../docs/, dann
│   │                      doku/), rendert sie und baut das
│   │                      Inhaltsverzeichnis; DokuMarkdown setzt drei
│   │                      Hausregeln durch — Sprungmarken, rel="noopener",
│   │                      und Bilder NUR relativ
│   ├── doku/             leer im Repositorium (nur .gitignore). Hierher
│   │                      kopiert die Auslieferungskette Handbuch.md,
│   │                      Was-ist-NAdoku.md und bilder/ — der FTPS-Schritt
│   │                      laedt nur server/ hoch
│   ├── vendor/Parsedown.php  1.7.4, MIT — der Markdown-Parser fuer die
│   │                      beiden Dokumentseiten (docs/Lizenzen.md 3a)
│   ├── einstieg_lib.php  WAS NACH DER ANMELDUNG FAELLIG IST (P5b/AP9):
│   │                      Einwilligungstor, Schluesselblatt-Rueckfrage,
│   │                      Konto-Rueckfrage, Erststart — genau EINES, in
│   │                      dieser Reihenfolge (siehe 4.99o)
│   ├── erststart_karte.php   die drei Schritte ueber der Tagesuebersicht.
│   │                      Eine KARTE, kein Dialog: „Wer sie ignoriert,
│   │                      arbeitet trotzdem" (Mockup M-P5b-02b)
│   ├── rueckfrage_dialog.php  „Hast du dein Notfallblatt noch?" — Abschnitt 1
│   ├── schluessel_teile.php   Abschnitte 2 und 3 (Passwort, neuer Wert):
│   │                      EIN Markup, zwei Dialoge — Rueckfrage und
│   │                      Kontoseite (E-P5b-20, R83)
│   ├── blatt_dialog.php  die Betreiber-Rueckfrage zum Schluesselblatt
│   ├── notfallblatt.php  der Wiederherstellungsschluessel auf Papier.
│   │                      SPEICHERT NICHTS und kann nichts speichern —
│   │                      deshalb spaeter nicht erneut druckbar
│   ├── admin_installation.php  Wie diese Installation nach aussen auftritt
│   │                       (S8/AP3): Name, Adressen, Logo — eine Spalte;
│   │                       die Rechtstexte standen hier bis Web 21.0.0
│   ├── admin_rechtstexte.php  Die vier Rechtstexte, ein Reiter je Text, Feld
│   │                       links, Vorschau rechts (P5c/AP9, E-P5c-28); von
│   │                       Web 15.2.0 bis 21.0.0 eine Weiterleitung
│   ├── apk_lib.php        Was in server/apk/ liegt — Name, Größe, Fassung,
│   │                       Datum, SHA-256 (S4/A1, siehe 4.97g)
│   │                       · apk.php liefert die Datei aus
│   │                       · apk/ die Dateien selbst (entstehen nur auf dem
│   │                         Server, im Deploy ausgenommen)
│   ├── geocoder_lib.php   Adresssuche: die beiden Schalter (Installation und
│   │                       Konto) und die Dienstadresse — die EINZIGE Quelle
│   │                       dieser drei Werte (S9/AP2). Der Browser bekommt sie
│   │                       ueber ui_geocoder_bootstrap() aus ui_ortsfeld()
│   ├── install.php        Serverinstallation
│   ├── migration_lib.php  Migrationskatalog und Lauf (S8/AP2): Katalog,
│   │                       Register, Lauf, Stand, Inhaltszählung. Die EINZIGE
│   │                       Stelle mit den Migrationen selbst — update.php und
│   │                       betrieb_updates.php rufen sie nur auf
│   ├── update.php         Übergangsseite und CLI-Notausgang. Im Browser nur
│   │                       noch Wegweiser auf die drei Betriebsseiten;
│   │                       `php update.php` führt die Migrationen aus
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
│   │                      adminbackup_freigabe.php (freigegebenes Backup für die NutzerIn) ·
│   │                      kdf_upgrade.php (stille Anhebung der Rundenzahl) ·
│   │                      pat_anheben.php (stille Anhebung des Notiz-Altbestands
│   │                      in den verschlüsselten Block, ab Web 19.0.0 — siehe 4.98d) ·
│   │                      rueckweg_anlegen.php (das Schlüsselpaar des Rückwegs
│   │                      ablegen, nur mit Passwortnachweis; Konzept RW, 4.99q) ·
│   │                      rechtstext_vorschau.php (rendert das Getippte mit
│   │                      rt_html(), speichert nichts; Admin und BetreiberIn,
│   │                      Topf rt_vorschau — P5c/AP9)
│   ├── assets/            style.css (Schriften werden lokal ausgeliefert, s. u.),
│   │                      crypto.js (WebCrypto), unlock.js (Entsperrdialog, s. u.),
│   │                      zeitfeld.js (Zeiteingabe im 24-Stunden-Format, s. u.),
│   │                      keyguard.js (Bindung/Lebensdauer des Inhaltsschlüssels),
│   │                      pwquality.js (Passwortgüte), patient.js, daylist.js, confirm.js,
│   │                      menue.js (Einstellungsleiste: Blöcke klappen, Unterpunkte),
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
│   │                      geocoder.js (EdGeocoder: der EINE Weg zum Adressdienst —
│   │                       suche(), umkehr(), an(); keine Adresse im Code, s. u.),
│   │                      ortswahl.js (Geolocation + Kartendialog am Ortsfeld, s. u.),
│   │                      blatt.js (Aktions- und Sortierblätter) + schublade.js (mobile Leiste),
│   │                      dialog.js (öffnet Dialoge, die im Markup stehen, und füllt sie
│   │                       aus `data-w-*` des öffnenden Knopfes — ein Dialog für viele Zeilen),
│   │                      symbol.js (edSymbol() — dieselbe Zeichenkette wie ui_symbol()
│   │                       in PHP; kein Zeichen liegt als Inline-Pfad im Code),
│   │                      rueckweg.js (EdRueckweg: das Schlüsselpaar des Rückwegs
│   │                       erzeugen, verpacken, senden — Konzept RW, 4.99q)
│   │   └── vendor/        xlsx.full.min.js — SheetJS Community Edition 0.18.5, Apache-2.0 ·
│   │                      zipjs.min.js — zip.js 2.8.34, BSD-3-Clause (ZIP + AES-256) ·
│   │                      qrcode.js — qrcode-generator 2.0.4, MIT (nur die Modulmatrix
│   │                       des QR-Codes; das SVG baut assets/qr.js) ·
│   │                      leaflet/ — Leaflet 1.9.4, BSD-2-Clause (Karten; CSS, JS, images/);
│   │                      alle lokal vendoriert (kein CDN), Herkunft und SHA-256 im Dateikopf
│   │   └── fonts/         Bricolage Grotesque 500/600 und Open Sans 400/600/700 als woff2,
│   │                      je Subset latin und latin-ext (@fontsource, OFL-1.1)
│   │   └── images/        Logos als SVG (farbig + weiss) je Hubschrauber und Fahrzeug,
│   │       │                favicon.png + favicon-fahrzeug.png (erzeugt aus den
│   │       │                Logodateien, s. tools/erzeugen/ (logos)); das Fahrzeug-Logo ist bis
│   │       │                zur Zulieferung ein PLATZHALTER (gestrichelter Rahmen)
│   │       └── symbole/    55 Zeichen als je eine SVG-Datei (Tabler Icons, MIT;
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
│   ├── migrations/        Migrationen als nachlesbare SQL-Dateien (ausgeführt
│   │                      wird über Betrieb → Updates, siehe migration_lib.php)
│   └── .htaccess          HTTPS-Zwang, Dateisperren (auch Punktdateien, mit
│                      Ausnahme `.well-known/`), Sicherheits-Kopfzeilen
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
│   ├── motor.mjs          Motorwahl für die drei Browserproben (AP3b,
│   │                      Backlog Nr. 183): `--motor chromium|firefox|webkit`
│   │                      und die Firefox-Voreinstellung, ohne die Gecko den
│   │                      Media-Block der 36-px-Bedienhöhe nicht sieht.
│   │                      Eine Stelle für Bilderlauf, Klickprobe und
│   │                      Stilvergleich — dreimal geschrieben wäre sie
│   │                      zweimal richtig und einmal falsch
│   ├── sandbox/           stellt die Arbeitsumgebung her und fährt sie hoch:
│   │                      aufbauen.sh (web|android|uhr|plattform|alles),
│   │                      hochfahren.sh (--neu, --php 8.3), plattform.sh
│   │                      (PHP 8.3.33 und drei Datenbankfassungen als
│   │                      Abbilder). MISST JE STÜCK NACH — die drei
│   │                      Engines einzeln, die acht Umgebungswerte mit
│   │                      ihrer Länge, nie mit ihrem Wert. Ablösung von
│   │                      containeraufbau/ und dem Beschaffungsteil des
│   │                      SessionStart-Hooks (PK-02); Ausbaustufen und
│   │                      Grenzen in docs/Sandbox-Setup.md.
│   │                      konfig_stellen.php stellt eine Lage in
│   │                      `config.php` her und nimmt sie wieder zurück —
│   │                      vier Proben prüfen so, was die Anwendung mit
│   │                      einem anderen Schlüssel tut. Derselbe Weg wie in
│   │                      der Anwendung: Datei schreiben, dann
│   │                      `konfig_verwerfen()`. Bis Konzept BR lag sie lose
│   │                      unter tools/ (E-BR-03 (4))
│   ├── bedienprobe/       fährt Bedienwege im Browser und BEDIENT dabei
│   │                      Elemente (S9, E-S9-16): Playwright wie der
│   │                      Bilderlauf, aber `mouse.down()` — **300 ms halten** —
│   │                      `mouse.up()` statt `locator.click()`, das die Taste
│   │                      nur rund 10 ms hält und Backlog Nr. 102 deshalb
│   │                      nicht findet. Je Weg eine Zahl. Jedes Arbeitspaket
│   │                      legt seine Wege als eigene Datei unter `wege/` dazu;
│   │                      der Läufer kennt keinen einzelnen. Die Adressabfrage
│   │                      läuft gegen `attrappe.mjs` — der Prüfstand hat
│   │                      dorthin keinen Netzzugang, und ohne feste
│   │                      Trefferzahl wäre jeder Sollwert geraten. Braucht
│   │                      die lokale Installation (s. LIESMICH.md).
│   │                      `vendor/jsQR.js` (1.4.0, Apache-2.0) liest den
│   │                      QR-Code des Zweitfaktors aus einem Abzug — nur
│   │                      Prüfwerkzeug (E-P5c-87). `probekonto.mjs` legt
│   │                      ein Konto mit bekanntem Passwort an, für Wege,
│   │                      die weder Prüf- noch Demo-Konto brauchen können
│   │                      (Zweitfaktor einrichten) — seit Konzept RW auch
│   │                      eines MIT Schlüsselhülle (`passwortSetzen()`, über
│   │                      Einladung und `pw_handling.php` im Browser); es
│   │                      liegt neben `wege/`, weil der Läufer dort jede
│   │                      Datei als Wegdatei lädt
│   ├── zweitfaktor/       das Prüfkonto mit bekanntem Geheimnis
│   │                      (`pruefkonto.php`) und EIN Code-Rechner je Sprache
│   │                      (`totp.php`, `totp.mjs`, `totp.py`) mit gemeinsamem
│   │                      Zähler — kein Code gilt zweimal (E-P5c-43)
│   ├── schemaprobe/       läuft `schema.sql` und der Migrationskatalog auf
│   │                      der Datenbank, gegen die sie laufen sollen? Vier
│   │                      Installationsfälle, 19 Erwartungen, gegen eine
│   │                      LAUFENDE Datenbank. Fall 2 ist der Kern: Bestand
│   │                      anlegen, migrieren, hinterher Wert für Wert
│   │                      vergleichen — eine Migration, die Daten verliert,
│   │                      fällt dort auf und sonst nirgends. In Stufe 1 als
│   │                      eigener Auftrag mit einer MATRIX über MySQL 8.4.0
│   │                      und MariaDB 10.6: Der Stand vor Web 20.25.0 legt
│   │                      auf MariaDB 42 Tabellen an und scheitert auf MySQL
│   │                      mit 1064 (Nr. 238). Mit `--selbstprobe`
│   ├── erzeugen/          sieben Befehle, die Dateien HERSTELLEN statt zu
│   │                      prüfen (PK-04/3, E-PK-24): design (die Tabellen
│   │                      in Design.md), logos, uhr-bilder,
│   │                      geraetemodelle (+ nachaufloesen),
│   │                      wegwerfdomains, pruefkonten. Ein Läufer
│   │                      (`erzeugen.sh <name>`), OHNE `alle`: Sie
│   │                      schreiben ins Repositorium und werden einzeln
│   │                      auf Zuruf gefahren
│   ├── kette/             die Tore der Auslieferungskette (P5a/AP1):
│   │                      Backup-Tor, Wartung an/aus, Zustand und seit
│   │                      Web 20.16.0 die Job-Pause — gegen
│   │                      `jobs.php?aktion=…`. DER EINE CLIENT dieser
│   │                      Schnittstelle: Er kennt Adresse, Token,
│   │                      Zeitgrenze und den Umgang mit einer unlesbaren
│   │                      Antwort; wer daran vorbei eine eigene
│   │                      urllib-Zeile schreibt, baut einen zweiten Weg,
│   │                      den niemand pflegt. Das Backup-Tor verlangt
│   │                      `fertig` UND einen Stand, der jünger ist als der
│   │                      Laufbeginn; `--selbstprobe` weist an ELF Lagen
│   │                      nach, dass es auch zugeht — fuenf fuer das Tor,
│   │                      sechs fuer `pause` und die Fehlerantwort. Sie
│   │                      laeuft in Stufe 1 UND vor dem Tor. Dazu seit
│   │                      BR-03 baumsuche.py: die EINE Suche nach gruenen
│   │                      Stufe-1-Laeufen mit demselben Baum, fuer
│   │                      „Schon gemessen?" und das Produktionstor (E-BR-09)
│   ├── kettenaufrufe/     haelt JEDEN Werkzeugaufruf der Arbeitslaeufe und
│   │                      von pruefablauf.json gegen die tatsaechliche
│   │                      Schnittstelle des aufgerufenen Werkzeugs —
│   │                      `add_argument`, die Handparser (`wert('--x'`,
│   │                      `flag('--x'`), `BEKANNT`-Mengen, `case`-Zweige,
│   │                      Namenslisten der Sammellaeufer, `$argv`. FUEHRT
│   │                      KEIN WERKZEUG AUS, deshalb Stufe 1 (Nr. 217).
│   │                      Werkzeuge, deren Schnittstelle nicht aus dem
│   │                      Quelltext lesbar ist, zaehlt es als UNGEPRUEFT und
│   │                      nennt die Zahl. Mit `--probe` (Zahl in seiner
│   │                      LIESMICH)
│   ├── integritaetswache/ vergleicht die AUSGELIEFERTE Fassung mit dem
│   │                      ZEIGER `produktion` (`--stand`, 6.6a): jede Datei
│   │                      unter `server/assets/`
│   │                      über SHA-256, und auf `login.php` die GANZE Menge
│   │                      dessen, was den Weg des Passworts bestimmt —
│   │                      Skripte (zitiert oder nicht), Inline-Blöcke,
│   │                      Formulare, `<base>`, Umlenk- und Ereignis-
│   │                      attribute, `<meta http-equiv>`, Einbettungen,
│   │                      `javascript:`-Adressen; nichts darf fehlen,
│   │                      verändert sein oder dazukommen. Ohne eingecheckte
│   │                      Prüfsummen — der Deploy synchronisiert byteweise,
│   │                      also rechnet sie beide Seiten frisch. Läuft täglich
│   │                      und nach jedem Deploy als GitHub-Action
│   │                      (`integritaet.yml`); `--selbstprobe` beantwortet
│   │                      zuerst, ob sie eine Abweichung überhaupt erkennt
│   │                      (Backlog Nr. 140, SP-6)
│   ├── messstand/         stellt ein Konto mit 5000 Einsätzen her — aus der
│   │                      Referenz-Backup vervielfältigt und über den
│   │                      REGULÄREN Wiederherstellungsweg eingespielt — und
│   │                      misst daran die Zielzahlen von S2: Suche,
│   │                      Tagesansicht, Sichern, Speicherspitzen,
│   │                      Tabellengrößen. Browserprobe unter CPU-Drossel 6×.
│   │                      Riegel: füllt nur ein Konto mit dem Präfix
│   │                      „messstand" (s. LIESMICH.md)
│   ├── pruefstand/        der Ablaufplan aller Proben: `pruefablauf.json`
│   │                      nennt je Probe Aufruf, Voraussetzung und Bemerkung
│   │                      — und ist die EINE Stelle, an der eine Schwelle
│   │                      steht (Grundsatz 1). `pruefen.sh` fährt sie je
│   │                      Stufe, `auswahl.py` wählt nach Berührung aus,
│   │                      `bericht.py` schreibt die Zahlen (s. LIESMICH.md)
│   ├── quelltext/         dreizehn Prüfungen, die nur Quelltext lesen und im
│   │                      Tor laufen (PK-04, E-PK-24): installweiche,
│   │                      sitzungshaertung, csp, jobregister,
│   │                      migrationsregister, behandler (seit P5c/AP3),
│   │                      linkprobe, vollstaendigkeit, textprobe und — seit
│   │                      Konzept BR — bestand, der den Werkzeugbestand
│   │                      unter tools/ gegen Pruefablauf.md 6 hält, pysyntax
│   │                      und handbuch (bis BR-03 eigene Tor-Schritte), dazu
│   │                      seit P5c/AP9 anker (jeder Verweis hilfe.php#… trifft
│   │                      eine Überschrift des Handbuchs). Ein
│   │                      Läufer (`pruefen.sh <name>|alle|--selbstprobe`),
│   │                      ein LIESMICH. Vorher acht Ordner.
│   ├── referenzdatensatz/ erfundener Beispielbestand (21 Diensttage,
│   │   │                  106 Einsätze) — Demo-Konto UND Regressionsreferenz
│   │   ├── quelldaten/    die Wahrheit: je Diensttag ein JSON, dazu die zwei
│   │   │                  Geräteblöcke (geraete.json), die drei Schnitte,
│   │   │                  Schema und Prüfung (Abdeckungsmatrix, Sperrwörter
│   │   │                  in den Gerätenamen, keine realen Namen)
│   │   ├── generator/     erzeugt Ingest-Payloads, Formulardaten, CSV, GPX;
│   │   │                  fester Zufallssamen, zwei Läufe gleiches Ergebnis
│   │   ├── einspielen/    spielt alles über die REGULÄREN Wege ein, kein SQL;
│   │   │                  lokal_einrichten.sh baut eine Installation von Null
│   │   │                  auf (install.php über HTTP, Passwort im Browser,
│   │   │                  Demo-Konto), lokal_starten.sh fährt sie nur hoch;
│   │   │                  sitzungsprobe.py misst, dass sitzung.py BEIDE
│   │   │                  Hüllenfassungen öffnet (edk1: und edka1:, S10);
│   │   │                  demo_kennzeichnen.php vermerkt das frische Konto als
│   │   │                  Demo-Konto — VOR der ersten Anmeldung, sonst stellt
│   │   │                  unlock.js still auf edka1: um und die Fixture lässt
│   │   │                  sich am Ende nicht mehr erzeugen
│   │   ├── browser/       was es nur im Browser gibt: CSV-Import, Angriffs-
│   │   │                  werte (P-07), Exporte, Umläufe, Papierkorb-Mischfall,
│   │   │                  Abnahme der Demo-Funktion
│   │   ├── referenz/      die eingecheckten Referenz-Exporte
│   │   ├── vergleich/     Vergleichswerkzeug und Kreislauftests
│   │   └── fixture/       erzeugt server/demo/fixture.json.gz; riegelprobe.php
│   │                      misst die zwei Riegel darin (Zielrundenzahl,
│   │                      Hülle bleibt edk1: — sonst wäre das Demo-Konto auf
│   │                      der Produktivinstallation ausgesperrt, S10)
│   ├── proben/            vierundzwanzig Prüfungen gegen die laufende Anlage
│   │                      (PK-04/2, E-PK-24): ingest, spur, jobs,
│   │                      kopplung, wartung, raten, mail, versand,
│   │                      komplett, wiederherstellung, gpx, geraete,
│   │                      verbindung, anteil, rechtstexte, freigabe,
│   │                      container, frist, abmelden, csp-browser —
│   │                      seit Web 20.39.0 dazu rollen (die
│   │                      Berechtigungsmatrix aus 4.99p) und protokoll
│   │                      (Quellen, Archiv, Siegel), seit Web 20.42.0
│   │                      zweitfaktor (RFC-Vektoren, Code-Schritt, Tor),
│   │                      seit Web 20.43.0 rueckweg (Signaturen des
│   │                      Rückwegs, Selbsttest, Marke; Konzept RW).
│   │                      Gezählt am 24.09.2026 mit `proben.sh --liste`:
│   │                      24 — bis dahin stand hier „zweiundzwanzig",
│   │                      AP5 hatte die Zweitfaktorprobe nicht nachgetragen.
│   │                      Ein Läufer (`proben.sh <name>|alle|--liste`),
│   │                      ein LIESMICH. Vorher zwanzig Ordner.
│   ├── screenshots/       nimmt alle Seiten in acht Breiten von 360 bis 1920 px
│   │                      auf, je Seite ein Kontaktbogen; misst dabei
│   │                      waagerechten Überlauf, Konsolenfehler, Knopfhöhen
│   │                      und — seit Web 20.21.1 — Karten, die ausserhalb von
│   │                      main.inhalt haengen (Nr. 225).
│   │                      Seit Web 9.10.1 prueft er nach JEDEM Aufruf, ob er
│   │                      die richtige Seite vor sich hat, und meldet sich bei
│   │                      Bedarf neu an; ein nicht aufloesbarer Platzhalter
│   │                      ergibt kein Bild (F-P3-AQ). Welche rote Zeile als
│   │                      Konsolenfehler zaehlt, entscheidet istRauschen() in
│   │                      drei Klassen; --selbstprobe haelt die Funktion gegen
│   │                      fuenfzehn Faelle mit Sollwert, je einer traegt eine
│   │                      Klasse (Backlog Nr. 176; die Mutationsprobe dazu
│   │                      steht in der LIESMICH).
│   │                      kontrast.py rechnet die Kontraste der Token nach
│   │                      (s. LIESMICH.md)
│   ├── spaltenregister/   hält `schema.sql` gegen `mf_missions_register()`
│   │                      (beide Richtungen) und misst die Vollständigkeit der
│   │                      drei Abbildungen, die von Hand bleiben — jede muss
│   │                      genau die Registerspalten ihres Zwecks führen, jede
│   │                      Ausnahme eine Begründung im Feld, und eine Ausnahme
│   │                      ohne Treffer ist selbst ein Befund. pruefen.php
│   │                      hängt in Stufe 1 und ist rein lesend. wegprobe.py
│   │                      fährt die zwei Anweisungen, die kein Kreislauf
│   │                      abdeckt (Schnitt, UPDATE-Zweig des Imports) — sie
│   │                      SCHREIBT und gehört an ein Wegwerfkonto
│   │                      (Schritt 15 AP6, E-ZE-22; s. Dateikopf)
│   ├── stilvergleich/     rechnet nach, dass eine Änderung an style.css das
│   │                      Erscheinungsbild nicht verändert: Kaskadenvergleich
│   │                      plus berechnete Stile im Browser, 13 Breiten.
│   │                      Ruhte waehrend P3, in O12 neu geeicht; ab P4 wieder
│   │                      Pflicht bei CSS-Umbauten (s. LIESMICH.md)
│   ├── uhr-pruefstand/    baut SDK und Simulator auf einem nackten Linux-
│   │                      Rechner auf, übersetzt die Uhr-App und startet
│   │                      sie ohne Fensteroberfläche (s. Abschnitt 5.2b).
│   │                      Darunter die beiden Connect-IQ-Proben, die nur
│   │                      dort laufen (PK-04/4): `netzprobe/` — EINE Anfrage,
│   │                      kommt der Simulator an 127.0.0.1 heran, und trennt
│   │                      „kommt nicht raus" von „kommt raus, Antwort wird
│   │                      verworfen" — und `eingabe-probe/` zum Ausmessen des
│   │                      Eingabeverhaltens neuer Zielgeräte (Abschnitt 5.2)
│   └── zaehlung/          hält fest, dass jede Sache ihre EINE Stelle behält
│                          (Schritt 15, R83, E-ZE-24): je Muster eine
│                          Registerzeile mit einer Decke, gemessen mit dem
│                          PHP-Tokenizer in drei Sichten — Code ohne
│                          Kommentare, dasselbe ohne Zeichenketteninhalt, und
│                          das JavaScript (`.js` ganz, aus `.php` die
│                          <script>-Blöcke). Liegt ein Ist-Wert über der
│                          Decke, schlägt der Lauf an. Eine Decke wird nicht
│                          angehoben, ohne dass es im Konzept steht
│                          (s. LIESMICH.md)
├── .claude/hooks/session-start.sh  ruft beim Containerstart
│                          tools/sandbox/aufbauen.sh web — er beschafft nicht
│                          mehr selbst (PK-02). STARTET nichts, und schlägt
│                          nicht fehl: ein Mangel wird mit Zahl gemeldet
└── .github/workflows/     die Auslieferungskette (P5a/AP1, Abschnitt 6)
    ├── pruefung.yml       Stufe 1 ohne Installation: Arbeitszweige beim
    │                      Pull Request, main bei jedem Push; liest den
    │                      Prüfbericht gegen (PK-05)
    ├── auslieferung.yml   WANN ausgeliefert wird: Staging (Push auf main),
    │                      Stufe 2, Produktion (Tag, Pflichtfreigabe,
    │                      Backup-Tor), Zeiger
    ├── ausliefern-lauf.yml WAS dabei geschieht: die Schrittfolge, EINMAL,
    │                      für beide Umgebungen (Kette II/AP5, E-KH-14)
    └── integritaet.yml    die Wache — hängt am Anzeigenamen „Auslieferung"
                          (`deploy.yml` ist mit Web 20.4.0 gelöscht worden)
```

## 3. Datenmodell (MySQL)

| Tabelle | Zweck / Besonderheiten |
|---|---|
| `users` | Login (E-Mail = Username), Rolle `user`/`admin`; Löschen kaskadiert alles; **Browser-Schlüsselableitung** (`kdf_salt` + `kdf_iter` = Rundenzahl je Konto) und **E2E-Schlüssel-Hüllen** `pat_wrap_pw`/`pat_wrap_rc` (Inhaltsschlüssel passwort- bzw. wiederherstellungsverpackt), dazu `pat_key_check` = im Browser gerechnete Prüfsumme des Inhaltsschlüssels (NULL bei Altbestand — ein gültiger Zustand); `session_epoch` = Zähler, mit dem ein Passwortwechsel offene Sitzungen beendet (**seit Web 4.5.0 in Gebrauch**). `password_hash` ist NULL, solange das Passwort noch nicht gesetzt wurde — ein solches Konto kann sich nicht anmelden. Die **Sortierregel der E-Mail-Spalte ist ausdrücklich festgelegt** (`utf8mb4_unicode_ci`); ohne das hinge die Anmeldung an der Standardregel der jeweiligen Installation. Seit Web 4.5.0 schreibt und sucht der Code zusätzlich kleingeschrieben (`email_lib.php`), hängt also nicht mehr von der Sortierregel ab; **Bestandszeilen bleiben unverändert**, die ci-Regel trifft sie ohnehin. Seit Web 9.7.0 dazu **`logo_wahl`** (`''` = Standard der Installation, sonst `hubschrauber` / `fahrzeug` / `wechselnd`, E-P3-20) — der Leerstring ist die Vorgabe, damit ein späterer Wechsel des Installationsstandards bestehende Konten erreicht. Seit Web 9.8.0 dazu **`last_login`** (DATETIME NULL) — der Zeitpunkt der letzten **Anmeldung**, geschrieben von `login.php` und sonst nirgends; Kontoseite und NutzerInnen-Liste zeigen ihn. Der Bestand bekommt bei der Migration NULL und nicht NOW(): Der Wert wäre sonst erfunden, und zwar genau in der Spalte, mit der man ungenutzte Konten sucht. NULL erscheint als „—“. **Seit Web 20.17.0 der Lebenszyklus** (P5b/AP2, E-P5b-12): `status` (`unbestaetigt` / `wartet` / `aktiv` / `gesperrt`), `bestaetigt_am`, `gesperrt_seit`, `gesperrt_grund`, `loeschung_am`. **Der Bestand wird `aktiv`** — jeder andere Wert wäre eine Aussage über Konten, die es vor der Prüfung schon gab, und `unbestaetigt` sperrte sie am Tag nach dem Update alle aus. `bestaetigt_am` bleibt dort **NULL**: „die Frage stellte sich nicht", dieselbe Entscheidung wie bei `last_login`. Der Index `idx_status_loeschung` ist für die Verfalljobs, nicht für die Anzeige. **Seit Web 20.42.0 der Zweitfaktor** (P5c/AP5, E-P5c-54): `totp_geheimnis` (20 Byte, versiegelt mit `sk_versiegeln()`, Zweck `totp|<user_id>` — der Zweck verhindert das Umhängen auf ein anderes Konto), `totp_seit` (eingeschaltet, **erst nach einem bestätigten Code**; ein Geheimnis ohne `totp_seit` ist eine angefangene Einrichtung) und `totp_schritt` (der zuletzt angenommene Zeitschritt — kein Code gilt zweimal). Siehe 4.99q. **Seit Web 20.43.0 das Paar des Rückwegs** (Konzept RW, E-RW-05): `rw_oeffentlich` (öffentlicher Teil eines ECDSA-P-256-Paars, SPKI in Base64, 124 Zeichen), `rw_privat` (der private Teil als Chiffretext unter dem **Inhaltsschlüssel**, `edk1:`, nie `edka1:` — er muss mit dem Wiederherstellungsschlüssel allein aufgehen, wie `pat_wrap_rc`), `rw_seit` (wann es entstand). Leer heißt: noch kein Paar. Ein Abzug enthält den öffentlichen Teil und einen Chiffretext unter einem Schlüssel, den der Server nie kennt — er kann prüfen, nicht signieren (4.99q) |
| `totp_codes` | Die Wiederherstellungscodes des Zweitfaktors (seit Web 20.42.0, P5c/AP5): `user_id`, `hash` (`password_hash()`), `benutzt_am` (NULL = offen). **Nicht am Serverschlüssel** (E-P5c-42) — sie sind der Rückweg für genau den Fall, dass das Geheimnis nicht mehr zu öffnen ist. `ON DELETE CASCADE` mit dem Konto. Angenommen wird atomar (`UPDATE … WHERE benutzt_am IS NULL`, gültig bei `rowCount() = 1`) |
| Backup | `backup_lib.php` | Das Format ist seit Web 4.5.2 **aufgezählt** statt „alles, was in der Tabelle steht". Neue Spalten sind damit nicht mehr automatisch enthalten — sie einzutragen ist eine Entscheidung. Draußen: `id`/`user_id`/`device_id` (interne Verweise) und `other_resources` (tote Altspalte seit der Migration `2026_07`). **Bekannt:** `site_ele_m` ist im Backup, kommt beim Einspielen aber nicht zurück — der Einspielweg schreibt nur die Felder aus `mission_fields.php` plus `pat_blob`. |
| `password_resets` | **Seit Web 20.17.0 schreibt nur noch `konto_lib.php` hierher** (P5b/AP2, Backlog Nr. 202 Paket 1) — nachweisbar mit `grep -rn "INSERT INTO password_resets" server/`. Die Laufzeiten stehen als `TOKEN_EINLADUNG_S` / `TOKEN_RESET_S` statt als SQL-Literale, und „höchstens ein gültiger Token je Konto" gilt damit an **allen vier** Stellen statt an zweien. Token-Hashes (sha256); 1 h bei „Passwort vergessen“, 24 h bei Neuanlage und Installation; der Job `aufraeumen` entsorgt Altbestand. Seit Web 4.4.0 gilt **höchstens ein offener Token je Konto**: Eine neue Anforderung entwertet alle vorherigen. Seit Web 4.5.0 entwertet auch **jeder Passwortwechsel** alle offenen Token des Kontos — der 24-Stunden-Einladungslink entsteht auf einem anderen Weg und hätte den soeben gewählten Zustand sonst überschreiben können |
| `devices` | Upload-Zugang je Gerät: `device_id` (öffentlich, seit Web 4.5.1 aus **16** statt 4 Zufallsbytes — Bestandsgeräte behalten die kurze Kennung) + `api_key_hash`; **`active`-Flag** (deaktivieren statt löschen); virtuelle Geräte `manual-<userId>` für Handeinträge (dauerhaft inaktiv, aus Listen gefiltert). Seit Web 4.4.0 **höchstens `MAX_GERAETE` (5) echte Geräte je Konto**, aktive wie deaktivierte — die virtuellen zählen nicht mit. Seit Web 12.9.0 dazu die **Gerätekennung** (R42): `geraet_art` (`uhr`/`handy`/`sonstiges`), `geraet_modell` (aufgelöster Klarname, **VARCHAR(191)** — die Gerätedateien liefern Sammelnamen bis 153 Zeichen; die zunächst gewählten 64 waren geraten und sind mit Web 12.9.1 nachgezogen) und `geraet_teil` (die Rohangabe des Geräts — bei Garmin die Teilenummer, beim Handy Hersteller und Modell). **Alle drei sind dauerhaft NULL-bar**, und das ist keine Nachlässigkeit: Vier Wege legen ein Gerät an — Kopplung, Handanlage, virtuelles Gerät, Demo-Bestand —, und nur die Kopplung weiß etwas über das Gerät. Ein `NOT NULL DEFAULT 'unbekannt'` hätte daraus eine Aussage gemacht, wo keine ist; „unbekannt" ist eine Sache der Anzeige. **Bestandsgeräte bleiben leer**, bis sie neu koppeln — die Angabe entsteht ausschließlich beim Koppeln, und eine bereits gekoppelte Uhr wird nicht rückwirkend gefragt. **Drei Spalten statt der in R42 genannten zwei:** Die Rohangabe steht daneben, weil der Modellname aus einer erzeugten Tabelle stammt und ein künftiges Gerät sonst unwiederbringlich auf „unbekannt" fiele. Seit Web 20.11.0 dazu **`abgewiesen_seit`** (der **erste** Fehlversuch einer Serie, nicht der letzte) und **`abgewiesen_anzahl`** — der Vermerk der Mengenbremse aus P5a/AP7 (Abschnitt 5e.7). Beide werden beim nächsten gelungenen Upload geleert; ein Vermerk, der stehenbleibt, nachdem neu gekoppelt wurde, ist eine Falschmeldung. **Und seit Web 20.12.0 nach 30 Tagen auch ohne Upload** (Schritt `Geraetevermerke` im Aufräumjob): Den gelungenen Upload gibt es nicht mehr, wenn das Gerät ausgemustert ist — ohne den Schritt trüge ein verlorenes Gerät seine orange Plakette für immer. Siehe Abschnitt 5 |
| `missions` | Einsatz; `UNIQUE(device_id, client_ref)` = Idempotenz-Anker; **`day_id`** = Fremdschlüssel auf `days` (bis Web 5.10.0: die Spalte `day` mit dem Kalenderdatum); **`uhr_gesperrt`** — ausschließlich Schutz vor Uhr-Überschreiben, NICHT „von Hand angelegt"; hieß bis Web 20.24.2 `manual` und brach damit die Einrichtung auf MySQL 8.4.0–8.4.10 (reserviertes Wort, Backlog Nr. 238) — **in Sicherungs- und Exportdatei heißt das Feld weiter `manual`**, abgebildet per Alias; **`origin`** (`watch`/`manual`/`import`) = Herkunft, wird beim Anlegen gesetzt und nie wieder geändert; **`edited`** = wurde nach dem Anlegen verändert; `deleted_at`/`deleted_with_day` (Papierkorb); Zusatzfelder lt. `mission_fields.php`; **`site_ele_m`** = berechnete Einsatzort-Höhe (kein Formularfeld, siehe `site_elevation_lib.php`); **`crew_override`** = abweichende Besatzung je Einsatz; die Namen liegen seit Web 6.0.0 in **`mission_crew`** (`mission_id, role_code, name`) statt in fünf festen Spalten — die Tagescrew in `day_crew` bleibt die einzige Wahrheit, solange der Haken nicht gesetzt ist (siehe Abschnitt 4); **`pat_blob`** = E2E-Chiffretext (Name, Geburtsdatum, Alter, Diagnose, Einsatzort, seit Web 2.9.0 auch die Einsatznummer, seit Web 3.3.0 auch die Beschreibung des Einsatzortes, seit Web 6.2.0 auch der manuelle Abfahrtort, seit Web 19.0.0 auch die Notizen des Einsatzes — Klartext-Ortsspalten existieren seit der Pflicht-Migration nicht mehr; der ganze Katalog steht in 4.98) |
| `mission_phases` | Phasen-Zeitstempel **2–9** (Mehrfach-Einträge erlaubt und erwünscht — eine erneut gesetzte Phase ist eine Korrektur, keine Dublette) inkl. Position. Eine Phase 10 gibt es nicht; der Abschluss läuft über `final` und `ended_at` |
| `resus_sessions` / `resus_events` | Reanimationen: **mehrere Sitzungen je Einsatz**, Ereignisse typisiert |
| `rest_segments` | Ruhe-Track-Segmente (gleiches Idempotenz-Schema wie Einsätze) |
| `track_points` | GPS-Punkte für Einsätze **und** Segmente; PK `(owner_type, owner_id, seq)`; bewusst ohne FK (polymorph) → der Job `waisen` entfernt Waisen (4.97a). **Seit Web 10.0.0 nur noch der Eingangspuffer der Uhr** (Stufe 1): Sobald ein Paket abgeschlossen ist, wandern die Punkte in `track_blobs`. Gelesen wird ausschließlich über `spur_lib.php`, nie direkt — siehe Abschnitt 4.97 |
| `track_blobs` | Dieselben Punkte als **Blob** (Format SPUR1), eine Zeile je Spur, PK `(owner_type, owner_id)`. `stufe` 2 = verlustfrei, 3 = ausgedünnt; `n_original` = Punktzahl **vor** jeder Ausdünnung und damit die Grundlage der Fortsetzungsmarke der Uhr. Wie `track_points` ohne FK (polymorph) — die Löschwege räumen deshalb ausdrücklich mit, der Job `waisen` ist nur das Sicherheitsnetz. Der Grund für die Tabelle ist die Menge: gemessen **62,4 Byte je Punkt als Zeile gegen 3,58 als Blob** |
| `bases` / `vehicles` / `crew_presets` | Stammdaten: Standorte (mit optionalen Koordinaten), Rettungsmittel und Besatzungsnamen je Rolle. `vehicles` ersetzt `aircraft` seit Web 6.0.0 und trägt **zwei Achsen** (E-S9-09, Web 16.0.0): `kind` = `air`/`ground` ist die **Betriebsart** und steuert Rollenkatalog, Fähigkeiten, Kachelsatz und Höhe; `typ` = `standard`/`bergwacht`/`veranstaltung`/`sonstiges` ist die **Art des Dienstes**. Dazu `kurz` (Kurzname, bis 16 Zeichen, freiwillig) sowie `vehicle_roles` und `vehicle_capabilities`. **Welches Rettungsmittel Fähigkeiten führen darf, entscheidet seit Web 20.3.0 `veh_caps_erlaubt()` (`db.php`) aus Typ UND Betriebsart** — `kind = 'air'` bei `standard`/`veranstaltung`/`sonstiges` (E29), beide Betriebsarten beim Typ `bergwacht`. Die Spalte `faehigkeiten` in `VEHICLE_TYPEN` ist die Quelle; das Schema führt die Regel nicht. **Der Standortbezug ist verbindlich (E15) — bei Rettungsmitteln aber nur noch für den Typ `standard`:** `vehicles.base_id` ist NULL-fähig, die drei anderen Typen dürfen ohne Standort bestehen und haben dann keine Vorschlagslisten. Die Regel steht in `pruef_rettungsmittel()` (`validate_lib.php`), nicht im Schema. `user_id` = das Konto, seit Web 21.0.0 `NOT NULL` — siehe den Hinweis unter der Tabelle |
| `vehicle_roles` / `vehicle_capabilities` | Besetzte Rollen und Fähigkeiten (`winch`, `bergwacht`) je Rettungsmittel. Die Rollenkennungen stammen aus dem festen Katalog `CREW_ROLES` in `db.php`, nicht aus der Datenbank — deshalb VARCHAR und kein ENUM |
| `resources` | Vorbelegung „Andere Rettungsmittel"; `user_id` = das Konto |
| `mission_resources` | Rettungsmittel-Zuordnung je Einsatz (eigene Zeilen, einzeln entfernbar) |
| `bw_units` | Bergwacht-Bereitschaften; `user_id` = das Konto |
| `transport_dests` | Vorbelegung „Zielklinik" (Datalist-Vorschläge, `missions.transport_dest` bleibt Freitext ohne FK), seit Web 6.1.0 mit optionalen Koordinaten; `base_id` = Standort; `user_id` = das Konto |
| `user_defaults` | Nutzerbezogene Standard-Vorbelegung für Diensttage (`kind` in `base`/`vehicle`, `item_id` verweist auf `bases.id` bzw. `vehicles.id` des Kontos — ohne FK, weil es zwei Zieltabellen sind); ersetzt die entfallenen Alt-Spalten `bases.is_default`/`aircraft.is_default` |
| `days` | Diensttag. Seit Web 6.0.0 eine **eigene Zeile mit eigener Kennung** statt eines Kalendertags: Jeder Druck auf „Einsatztag starten" erzeugt einen; mehrere je Kalendertag sind zulässig (E9). Trägt echte `started_at`/`ended_at` und den beim Zuordnen **eingefrorenen** Snapshot aus Standort und Rettungsmittel (`kind`, `base_name`, `base_lat`, `base_lon`, `vehicle_name`, seit Web 16.0.0 auch `vehicle_typ` und `vehicle_kurz`) — Stammdatenänderungen wirken nur in die Zukunft (E8). `kind IS NULL` = neutral, noch nicht zugeordnet (E26) **Seit Web 18.1.0 kann die Momentaufnahme ohne Stammdatensatz bestehen** (E-S9-10): `vehicle_id IS NULL` bei gesetztem `vehicle_name` heißt „ein Rettungsmittel nur für diesen Tag“ — Bezeichnung, Typ, Betriebsart und der Standort (als Kennung oder als bloßer Name) stehen dann allein hier. Suche, Filter und Tagesliste lesen ohnehin die Momentaufnahme und finden es deshalb. `day_capabilities` bekommt dafür keinen Satz; `day_crew` seit Web 21.0.0 **die Rollen der gewählten Betriebsart** (Nr. 169, E-P5c-47 — bis dahin keinen, F19) |
| `day_refs` | Uhr-Kennungen eines Diensttags (`device_id`, `day_ref`). Bewusst eine eigene Tabelle: Nach dem Zusammenführen trägt ein Diensttag legitim **mehrere** Kennungen, und `ingest.php` findet damit ohne jede Umleitungslogik den richtigen Tag. Von Hand angelegte Diensttage haben hier keine Zeile |
| `day_crew` / `mission_crew` | Besatzung je Rolle, normalisiert (E7). Die **Zeilenmenge** von `day_crew` ist der eingefrorene Rollensatz des Diensttags — auch leere Zeilen gehören dazu, denn sie sagen, welche Rollen der Dienst anbot |
| `day_capabilities` | Eingefrorene Fähigkeiten des Diensttags. Wird der Windenhaken am Rettungsmittel später entfernt, verlieren alte Einsätze ihre Windenfelder nicht (A13e) |
| `pair_sessions` | Kopplungssitzungen (seit Web 13.0.0, S5): Das **Gerät** holt sich mit `start` eine Sitzung und zeigt den Code, ein Mensch gibt ihn im Web ein (`user_id` wird gesetzt: beansprucht), das Gerät bestätigt mit Ja — erst dann entsteht die `devices`-Zeile; bis dahin sind Kennung und Schlüssel **schwebend**. Code **6 Zeichen** aus 32 (`PAIR_CHARS` in `db.php`, ohne 0/O und 1/I), **eine Frist von 10 Minuten ab `erstellt_am` für alles**; Schlüssel als SHA-256; die Datenbank ist der Schiedsrichter (Beanspruchen per `UPDATE … WHERE user_id IS NULL`, gültig bei `rowCount() = 1`); keine Endzustände — bestätigt und verworfen werden gelöscht, verfallen entsorgt der Job `aufraeumen`; Obergrenze `PAIR_SITZUNGEN_MAX` (1000) über unverfallene Zeilen. Löste `pair_codes` ab (Code im Web erzeugt, an der Uhr getippt); Ratenschutz über `rate_limits` mit drei Töpfen |
| `deleted_refs` | Sperrliste gelöschter `client_ref`s (90 Tage) gegen Wieder-Upload durch die Uhr; `owner_type` unterscheidet Einsatz und Ruhe-Segment — die Liste gilt für **beide** |
| `rate_limits` | Ratenschutz: Versuche je `topf` und `merkmal` (`ip:…`, `id:…` oder `alle`), mit Zeitfenster und Sperrfrist; liegt bewusst in der Datenbank und nicht in der Sitzung — eine Zählung, die der Aufrufer durch Wegwerfen seines Cookies zurücksetzen kann, ist keine. **Die Töpfe stehen in `RATE_GRENZEN` und nirgends sonst** — und hier steht ihre Zahl absichtlich **nicht** mehr: Bis Web 20.10.0 stand „alle vier“ (falsch seit sechs Töpfen), bis 20.11.0 „es sind zwölf“ (falsch mit den beiden Ingest-Töpfen). Eine Zahl in einem Fließtext altert genauso still wie eine Aufzählung. Bei `salt` und `reset` zählt **jede** Anfrage, nicht nur eine fehlgeschlagene: Beide Endpunkte kennen kein Scheitern, begrenzt wird die Menge (`rate_zaehlen()`). Der Job `aufraeumen` entsorgt Altbestand. Seit Web 20.10.0 dazu **`stufe`** (0 = nie gesperrt, 1–4 = Sprosse der Sperrleiter) und **`stufe_bis`** (letzter Fehlversuch + 24 h; danach gilt die Stufe als 0, auch wenn die Zeile noch dasteht) sowie ein Index auf `gesperrt_bis` |
| `rechtstexte` | Die Rechtstexte dieser Installation (R32, seit Web 9.11.0). `schluessel` = `impressum` / `datenschutz`, seit Web 20.19.0 auch `nutzungsbedingungen` / `avv`, `inhalt` = Markdown-Quelle (`MEDIUMTEXT`; NULL oder leer = Leerzustand), `stand_am` = das im Editor **von Hand** gesetzte Standdatum (NULL = keine Standzeile). **Nicht in `app_state`:** Dessen Wert ist `VARCHAR(190)`, eine Datenschutzerklärung hat 8 000 bis 20 000 Zeichen — und ohne strict mode kürzt MySQL still |
| `app_state` | Schlüssel/Wert (z. B. `salt_secret`, seit Web 10.1.0 `jobs_token` = Geheimnis für `jobs.php?token=…`, `adminbackup_intervall`, `adminbackup_last`, seit Web 9.8.0 `adminbackup_aufbewahrung` = Zahl der Pakete je Konto, 0/fehlend = Vorgabe **2**, vorher 3; seit Web 12.0.0 `adminbackup_grenze_gb` = Speichergrenze der Ablage (fehlend = 2), `adminbackup_schwellen` = Warnschwellen in Prozent (fehlend = 70,90), `adminbackup_schwellen_gemeldet` und `adminbackup_schwellen_offen` = je Schwelle einmal melden, `adminbackup_auftrag` = Zeiger des Auftrags „Alle sichern"; seit Web 12.1.0 `versand_auto` = Versand auf die Backup-Ziele ein/aus (S2/AP7); seit Web 9.10.0 `adminbackup_mail` = Erinnerung an die Verwaltung ein/aus, `adminbackup_mail_last` = Datum der letzten Erinnerung, `logo_standard` = Logo dieser Installation (`hubschrauber` / `fahrzeug`, fehlend = Hubschrauber); seit Web 15.1.0 `speicher_db_bytes`, `speicher_dateien_bytes` und `speicher_stand` = die tägliche Messung aus `speicher_lib.php` sowie `webspace_gb` = Webspace laut Hosting als **Angabe** der BetreiberIn (fehlend = kein zweiter Bezug, siehe 4.99d); seit Web 15.3.0 `smtp_last` und `smtp_last_ok` = Zeitpunkt und Erfolg des letzten Mailversands, geschrieben von `smtp_send()` (siehe 4.99e); seit Web 20.5.0 `speicher_db_grenze_gb` = Kontingent der Datenbank laut Hosting; seit Web 20.6.0 `migration_tor_hash` und `migration_tor_offen` = der Zwischenspeicher des Torwächters; seit Web 20.7.0 `csp_scharf` = Content-Security-Policy scharf statt Report-Only und `hsts_tage` = Bindungsdauer von HSTS in Tagen, 0/1/7/365, fehlend = **1** (siehe 5c); seit Web 20.8.0 `instanz_name` und `instanz_kurz` = der Name dieser Installation, fehlend = „Gen-EM Einsatzdokumentation Notarzt" bzw. „Gen-EM NAdoku" (siehe 5d)). Die Wartungsmarken `last_cleanup` und `last_cleanup_ok` sind mit Web 10.1.0 entfallen — ihre Auskunft steht vollständiger in `jobs` |
| `csp_berichte` | Meldungen der Content-Security-Policy, **zusammengefasst**: UNIQUE über (`richtlinie`, `quelle`, `seite`), dazu `anzahl`, `erstellt`, `zuletzt`. Geschrieben von `api/csp_bericht.php` ohne Anmeldung; keine IP, kein Konto, kein Abfrageteil der Adresse. Der Job `aufraeumen` löscht nach 30 Tagen (seit Web 20.7.0, siehe 5c) |
| `missions.letzter_punkt_am` / `rest_segments.letzter_punkt_am` | Wann zuletzt ein Punkt **eintraf** (seit Web 10.2.0, S2). Nicht `track_points.ts` — das ist die Aufzeichnungszeit. Die Karenz aus E-S2-06 braucht die Ankunftszeit: Die Uhr setzt `final` in *jedem* Teilstück, ein spät hochgeladener Puffer wäre über `MAX(ts)` gerechnet im Moment des Eintreffens schon 14 Tage still. NULL = noch nie gemessen; der Verdichtungsjob trägt es beim ersten Hinsehen nach |
| `track_cuts` | Sperrvermerke des Schneidewerkzeugs (seit Web 12.5.0, S4/A2), eine Zeile je Schnitt: `owner_type`/`owner_id` = Quelle, `mission_id` = der herausgeschnittene Einsatz, `von_ts`/`bis_ts` = der gesperrte **Zeitraum**. `ingest.php` verwirft Punkte darin — sonst kehrte eine Nachlieferung aus dem Gerätepuffer in die Quelle zurück und der Schnitt löste sich still wieder auf. Wie `track_points` ohne FK (polymorph); die Löschwege räumen ausdrücklich mit. Siehe Abschnitt 4.97e |
| `protokoll_ereignisse` | Das **Betriebsprotokoll** (seit Web 20.16.5, P5b/AP1, V1). `reiter` = `verwaltung` / `email` / `jobs` / `sicherung` / `ziele` / `system`, dazu `art` (die maschinelle Kennung, nach der die Protokollseite filtert; Wort und Ton dazu im Katalog `PROTOKOLL_ARTEN`), `urheber_user_id` / `urheber_art`, `betroffen_user_id`, `text` und `daten` (JSON). **Betriebsereignisse, keine Datenzugriffe** — dass jemand einen Einsatz geöffnet, gelesen oder exportiert hat, steht hier nicht und soll hier nicht stehen. **Kein Fremdschlüssel auf `users`**, und das ist der wichtigste Satz dieser Zeile: Der häufigste Verwaltungseintrag ist „Konto gelöscht"; mit CASCADE löschte die Kontolöschung ihren eigenen Eintrag, mit RESTRICT verhinderte der Eintrag die Löschung. `urheber_user_id` ist **`0` und nicht NULL**, wenn kein Mensch gehandelt hat — `urheber_art` sagt, welche Art von Niemand (`cli` / `job`). **Zwei Fristen:** `verwaltung` 365 Tage (einstellbar 90–1095), alle übrigen 30 Tage fest; der Job `aufraeumen` räumt beide in einem Schritt. Die Seite Verwaltung → Protokoll (seit Web 20.39.0) liest sie zusammen mit fünf weiteren Tabellen, der Job `protokoll_archiv` archiviert sie. Siehe 4.99g |
| `konto_einwilligungen` | Welche Fassung eines Rechtstextes dieses Konto angenommen hat (seit Web 20.19.0, P5b/AP4). `(user_id, schluessel)` als Primärschlüssel — **eine Zeile je Konto und Dokument, nicht je Annahme**; eine neue überschreibt die alte. `stand_am` ist die **angenommene** Fassung, der Vergleich gegen `rechtstexte.stand_am` ist die ganze Prüfung. Der Verlauf steht im Protokoll und überlebt dort die Kontolöschung; `ON DELETE CASCADE` ist hier richtig, weil eine Einwilligung ohne Konto keinen Gegenstand hat. Siehe 4.99j |
| `sicherheit_ereignisse` | Was **war**, nicht was **ist** (seit Web 20.10.0, P5a/AP6). `art` = `sperre` / `verlangsamung` / `aufgehoben`, dazu `topf`, `merkmal`, `stufe`, `versuche`, `zeitpunkt`, `bis`, `wer`. **Ein Eintrag je Sperre, nicht je Fehlversuch** — ein Protokoll, das jeden Tippfehler verbucht, wird nicht gelesen. `merkmal` steht im **Klartext**, mit IP- und E-Mail-Adressen: Ohne sie wäre die Liste „irgendwo war irgendwer gesperrt" und damit wertlos (dieselbe Abwägung wie bei der Unzustellbar-Liste, E-P5a-39). **In die Komplettsicherung geht sie seit Web 20.39.0 ohne Zeilen** (`KOMP_OHNE_ZEILEN` in `komplett_lib.php`, P5c/AP2): nur das Schema, damit ein Einspielen die Tabelle anlegt. Die 30-Tage-Frist gilt damit auch für jeden Abzug. *Hier stand bis Web 21.1.0, sie liege in **jeder** Komplettsicherung, weil `komp_tabellen()` keine Ausnahmeliste habe — das stimmte bis Web 20.38.x.* Der Job `aufraeumen` löscht nach 30 Tagen, fest (E-P5a-09). **Gelesen wird sie seit Web 20.12.0 über `sicherheit_ereignisse()`** und gezeigt auf Betrieb → Status → Sicherheit (5e.8); geschrieben wird nur an den **Töpfen mit Leiter** (`'leiter' => true` in `RATE_GRENZEN`; seit Web 20.42.0 sieben, mit `totp`) — die übrigen sperren ohne Protokollzeile. *Hier stand „fünf" und „neun"; seit Web 20.11.0 waren es sechs mit Leiter, gezählt am 24.09.2026* |
| `mail_warteschlange` | Jede ausgehende Nachricht, eine Zeile (seit Web 20.8.0, P5a/AP5). `schluessel` = Eintrag aus `mail_katalog()`, `art` = `konto`/`geraet`/`betrieb` (das wird in P5c der Reiter im Protokoll), `zustand` = `offen` / `zugestellt` / `unzustellbar` / `zu_spaet` / `ueberholt`, `versuche`, `naechster_versuch`, `gueltig_bis` (ein Reset-Link gilt eine Stunde), `fehler` = Grund **samt Kennung**. **Was beim Endzustand geleert wird, hängt vom Zustand ab** (E-P5a-39): `zugestellt`, `zu_spaet` und `ueberholt` verlieren Adresse, Betreff und Rumpf — es bleibt „eine Nachricht dieser Art ging zu dieser Zeit hinaus". Bei `unzustellbar` **bleibt die Adresse stehen**, weil „die Einladung an X kam nie an" ohne X wertlos ist; der Rumpf fällt trotzdem, wegen des Tokens darin. Der Job `aufraeumen` löscht nach 30 Tagen |
| `job_laeufe` | Verlauf der Hintergrundjobs (seit Web 20.8.0), eine Zeile je Lauf, der etwas getan hat oder scheiterte — ein Leerlauf schreibt nichts, sonst füllte sich die Tabelle mit Nichts. `job`, `zeitpunkt`, `ausloeser`, `erledigt`, `fehler`. Der Job `aufraeumen` löscht nach 30 Tagen |
| `jobs` | Zustand der Hintergrundjobs (seit Web 10.1.0, S2), eine Zeile je Job. `zustand` = Fortsetzungsmarke als JSON, `rueckstand` = was noch aussteht (für die Wartungsseite), `letzter_ausloeser` = `cli` / `token` / `anfrage`, `letzter_fehler` = warum der letzte Lauf scheiterte, `laeuft_seit` = Sperre gegen zwei gleichzeitige Läufe — bewusst ein **Zeitstempel und kein Flag**, sonst bliebe ein abgestürzter Lauf für immer gesperrt. Siehe Abschnitt 4.97a |
| `backup_targets` | Backup-Ziele (seit Web 12.1.0, S2/AP7): FTPS- oder SFTP-Gegenstelle je Zeile. **`ftp` ist seit Web 20.2.0 abgeschafft** (S10/AP4, E-S10-14) und **seit Web 21.0.0 auch aus dem `ENUM`** (Migration `2026_09_25_ftp_entfernen`, P5c/AP8, E-P5c-124). Bis dahin behielt das Schema den Wert, und ein solches Ziel trug die rote Plakette *wird übergangen*; der Weg dafür ist mit dem Wert gefallen. `geheim` (Passwort oder Passphrase) und `schluessel` (privater SSH-Schlüssel) stehen **versiegelt** darin (`edsk1:`, `serverkrypto_lib.php`); der Schlüssel dazu liegt in `config.php` und damit **nicht im Dump**. Welches Feld gilt, sagt der Inhalt: Steht in `schluessel` etwas, wird damit angemeldet und `geheim` ist dessen Passphrase. `fingerabdruck` = SHA-256 des Hostschlüssels (nur SFTP, Riegel gegen einen untergeschobenen Server). `letzter_fehler` steht dort, damit ein seit Wochen scheiternder Versand in der Oberfläche auffällt. Nicht zu verwechseln mit `transport_dests` — das sind Zielkliniken |
| `schema_migrations` | Buchführung des Migrations-Runners |

**Jeder Stammdatensatz gehört einem Konto** (`user_id NOT NULL` in den
sechs Stammdatentabellen, seit Web 21.0.0). Bis dahin hieß `user_id IS NULL`
„zentral": ein Eintrag, der keinem Konto gehörte und allen angeboten wurde,
samt einer Tabelle, die zentrale Standorte den Konten zuordnete (E16). Die
Oberfläche dafür ist mit Web 18.0.0 gefallen (Rahmenplan R39), das Modell mit
P5c/AP8 (Backlog Nr. 168): Migration `2026_09_25_zentrale_stammdaten`, die
Abfragen fragen nur noch `user_id = ?`, die Nutzlast der Kontosicherung steigt
auf 12. **Die Migration hat eine Vorbedingung** (4.99, Zeile „Migrationsschutz"): Steht
noch eine Zeile ohne Konto da, sperrt sie, nennt Tabelle und Zahl und lässt
sich nicht freigeben. Die Fundliste des Rückbaus liegt in der Historie
(`git log -- docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md`).

Skalierung: ~2.000–2.500 Punkte je Einsatz; Indizes `(user_id, day)` und der
Punkte-PK tragen das auf Jahre problemlos (~1 Mio. Punkte/Jahr).

## 4. Zentrale Abläufe

**Upload & Idempotenz** (Details: `JSON-Vertrag.md`): Die Uhr sendet je
Einsatz/Segment eine `client_ref` (seit Uhr 1.7.0 aus Präfix, einem
fortlaufenden Zähler im Gerätespeicher und einem Zufallsanteil — **kein
Zeitstempel mehr**, siehe `JSON-Vertrag.md` Abschnitt 8) und Punkte ab
`seq_from`; der Server
antwortet mit `next_seq` (erste noch fehlende Sequenz). Wiederholungen sind
unschädlich (`INSERT IGNORE` auf den Punkte-PK, Upsert auf `client_ref`) —
**auch in der falschen Reihenfolge**: Seit Web 13.0.1 schreibt der Upsert
`ended_at`, `distance_m` und `ascent_m` mit `COALESCE(VALUES(x), x)` statt
bedingungslos, weil ein nicht-finales Paket diese drei Felder gar nicht trägt
und sie sonst auf NULL zurücksetzte, während `final` (mit `GREATEST`
geschützt) auf 1 blieb. Übrig blieb ein abgeschlossener Einsatz ohne Ende.
Gehalten von `tools/proben/ingest/` Teil 7.
Phasen/Rea werden je Upload **vollständig ersetzt** (kein Delta). Die Uhr darf
lokal erst löschen, wenn `final` bestätigt und `next_seq` = Punktzahl.

**Ende-zu-Ende-Verschlüsselung (Pflicht):** Beim Login leitet der Browser per
PBKDF2-SHA256 (Rundenzahl je Konto, `users.kdf_iter`) aus Passwort + `kdf_salt` zwei Werte ab: ein
Auth-Token (ersetzt das Passwort gegenüber dem Server, wird dort gehasht
gespeichert) und eine **Hälfte**, aus der der Datenschlüssel entsteht (bleibt
im Browser, `sessionStorage`).
Ein zufälliger **Inhaltsschlüssel** (256 Bit, nicht vom Passwort abgeleitet)
verschlüsselt `pat_blob` (`{last, first, dob, dx, age, mission_no,
loc:{addr,lat,lon}, site_desc, start:{addr,lat,lon}, notes}`, AES-256-GCM; Katalog in 4.98) und liegt doppelt verpackt in `users`: mit dem Datenschlüssel
(`pat_wrap_pw`) und mit dem aus dem Wiederherstellungsschlüssel abgeleiteten
Schlüssel (`pat_wrap_rc`). Weil der Inhaltsschlüssel vom Passwort getrennt ist,
kostet ein Passwortwechsel kein Neuverschlüsseln — nur die Hülle wird erneuert.

**Der Server-Anteil (seit Web 19.7.0, S10 / Schritt 9b, R78, E-S10-02 bis
E-S10-04).** Die PBKDF2-Hälfte ist seither **nicht mehr selbst** der
Datenschlüssel. Dazwischen steht eine zweite Ableitung:

```
kontoAnteil     = HMAC-SHA256(schlüssel = kdf_anteil (32 Byte aus config.php),
                              nachricht = "konto:" + users.id)          → 32 Byte
Datenschlüssel  = HKDF-SHA256(ikm  = PBKDF2-Hälfte (32 Byte),
                              salt = kontoAnteil (32 Rohbyte),
                              info = "edka1|dk")                        → 32 Byte
```

*Wozu.* Wer die Datenbank hat, hat Salz, Rundenzahl und Hülle — und konnte bis
dahin offline durchprobieren (Krypto-Review K-3, Weg 1). `kdf_anteil` steht in
`config.php` und **nicht** in der Datenbank; ein Abzug allein genügt seither
nicht mehr. Der Server gewinnt dabei nichts: Er kennt den Anteil, nicht die
Hälfte aus dem Passwort.

*Warum aus der Kontonummer und nicht aus dem Salz.* Passwortwechsel und Reset
würfeln das **neue** Salz im Browser; der Anteil dazu wäre in genau dem
Augenblick unbekannt, in dem die neue Hülle entsteht. Die Kontonummer ist
unveränderlich und schon da. Dass sie erratbar ist, kostet nichts — das
Geheimnis ist `kdf_anteil`, und HMAC sorgt dafür, dass aus dem Anteil eines
Kontos kein anderer zu bilden ist.

*Was **nicht** daran hängt:* `pat_wrap_rc`. Der Wiederherstellungsschlüssel
öffnet weiterhin ohne Anteil — das ist der Rückweg, wenn der Anteil verloren
geht. Ebenso unberührt: die PBKDF2-Ableitung selbst, das Auth-Token,
`auth_salt.php`, der Inhaltsschlüssel, jeder `pat_blob`, `pat_key_check`, das
`.edbak`-Format und der Freigabeweg.

*Die fünf Stellen im Browser* (E-S10-08). Alle gehen über dieselben drei
Funktionen in `crypto.js` — `datenschluessel()`, `huelleOeffnen()`,
`huelleBauen()` — statt über `deriveKeys().haelfteHex` + `decrypt()`:

| Stelle | Datei | öffnet mit | baut mit |
|---|---|---|---|
| Anmeldung (stille Umstellung) | `unlock.js`, `loeseVormerkung()` | Präfix der alten Hülle | aktuellem Anteil |
| Entsperrdialog | `unlock.js`, `frage()` | Präfix der Hülle | — |
| Passwortwechsel | `einstellungen.php` | Präfix der alten Hülle | aktuellem Anteil |
| Export-Passwortprobe | `einstellungen.php` | Präfix der Hülle | — |
| Erstvergabe und Reset | `pw_handling.php` | `pat_wrap_rc` (**ohne** Anteil) | aktuellem Anteil |

**Eine sechste kam beim Gegenlesen dazu:** `EdCrypto.getContentKey()` rief
`decrypt()` unmittelbar und wäre an jeder `edka1:`-Hülle gescheitert. Da
`EdKeyGuard.contentKey()` darauf aufsetzt, betrifft das **jede** Anzeigeseite
— und zwar erst beim zweiten Seitenaufbau, weil der erste den Schlüssel aus
dem Vormerkfach bekommt. Sie geht seit Web 20.0.0 ebenfalls über
`huelleOeffnen()`.

*Geöffnet wird nach dem Präfix der Hülle, nicht nach dem Zustand der
Installation.* Das ist der Unterschied zwischen „dieses Konto ist umgestellt"
und „diese Installation liefert einen Anteil aus": Während einer Rotation
gilt beides gleichzeitig, aber je nur für einen Teil der Konten. Wer statt des
Präfixes `ANTEIL_STAND` fragte, öffnete dann die Hälfte der Hüllen mit dem
falschen Schlüssel. **Gebaut** wird dagegen immer mit dem *aktuellen* Anteil
(`ANTEIL_KENNUNG`) — so stellt jeder Schreibweg nebenbei um.

*Die Umstellung selbst* läuft über `api/kdf_upgrade.php` (Abschnitt oben):
Der Browser öffnet die alte Hülle, baut sie mit dem aktuellen Anteil neu und
schickt sie mit dem Anmelde-Token als Nachweis. Ein Fehlschlag bleibt still —
die alte Hülle bleibt gültig. **`login.php` setzt den Datenschlüssel seit
Web 20.0.0 nie mehr selbst:** Ob die Hülle den Anteil braucht, steht in ihrem
Präfix, und das kennt erst die angemeldete Seite. Das Vormerkfach liegt
deshalb nach jeder Anmeldung einen Seitenwechsel lang im `sessionStorage`.

*Ausgeliefert wird nur an die angemeldete Sitzung* und nur der eigene Anteil:
`auth_guard.php` stellt `$kontoAnteile`, `$anteilKennung` und `$anteilStand`
bereit, `ui_krypto_bootstrap()` gibt sie als `KONTO_ANTEILE`,
`ANTEIL_KENNUNG` und `ANTEIL_STAND` aus. `pw_handling.php` tut dasselbe für
das Konto des eingelösten Einmal-Tokens. Es gibt keinen Endpunkt, der den
Anteil eines fremden Kontos herausgibt. **Das Demo-Konto bekommt keinen**
(`null` / `'demo'`, E-P1-19): Seine Hülle kommt aus der Fixture und muss auf
jeder Installation aufgehen.

*Die Kennung.* Acht Hexzeichen aus SHA-256 über die 64 kleingeschriebenen
Hexzeichen des Werts (`schluessel_kennung()`). Sie ist ein Vergleichsmerkmal,
kein Schutzmerkmal: Damit lässt sich prüfen, ob zwei Stellen dasselbe
Geheimnis meinen, ohne es zu zeigen. Sie steht im Präfix jeder Hülle, in
`app_state` und auf dem Schlüsselblatt. Der **Serverschlüssel** bekommt ab S10
dieselbe Kennung — nur zur Anzeige; seine Versiegelung `edsk1:` bleibt.

*Die fünf Lagen* (`anteil_zustand()`, E-S10-09). `app_state.kdf_anteil_kennung`
merkt sich, mit welchem Anteil die Hüllen gebaut werden; `config.php` sagt,
welchen die Installation hat. Erst der Vergleich beider ergibt eine Aussage:

| Lage | Bedingung | Verhalten |
|---|---|---|
| **nicht eingerichtet** | kein `kdf_anteil`, keine Marke | keine Auslieferung; Hüllen bleiben `edk1:`, alles läuft wie vor S10 |
| **bereit** | Wert = Marke | Auslieferung, stille Umstellung |
| **Rotation** | `kdf_anteil_alt` gesetzt | beide Anteile werden ausgeliefert; Umstellung je Konto beim nächsten Anmelden |
| **abweichend** | Wert ≠ Marke, oder Wert fehlt bei gesetzter Marke | **keine Auslieferung**; die Seite nennt die erwartete Kennung statt „Passwort falsch" |
| **Neuanfang** | frischer Wert, Marke mit ihm gesetzt | Konten mit alter Hülle setzen ihr Passwort über den Wiederherstellungsschlüssel neu — **kein Datenverlust** |

Dass im Zweifel **gar nichts** ausgeliefert wird, ist die eigentliche
Entscheidung: Ein Anteil, der nicht passt, ergäbe einen Datenschlüssel, der
nicht passt — und der Fehlschlag sähe für jede NutzerIn gleichzeitig aus wie
ein falsches Passwort. Belegt von `tools/proben/anteil/`.

*Bedient werden die Lagen auf einer Seite* (seit Web 20.1.0, S10/AP3):
**Betrieb → Servereinstellungen**, Karte „Schlüssel des Servers"
(`betrieb_server.php`, Anker `#k-schluessel`). Sechs Handlungen, alle über
POST mit CSRF und `require_betreiberin()`, alle in `serverkrypto_lib.php`:

| Handlung | Funktion | angeboten bei |
|---|---|---|
| Serverschlüssel anlegen | `serverschluessel_eintragen()` | Serverschlüssel `fehlt` |
| Server-Anteil anlegen | `anteil_anlegen()` | Anteil `fehlt` |
| Server-Anteil wechseln | `anteil_wechseln()` | `bereit` oder `rotation` **ohne** `kdf_anteil_alt` |
| Alten Anteil entfernen | `anteil_alt_entfernen()` | `kdf_anteil_alt` gesetzt **und** `anteil_zaehlung()['alt'] === 0` |
| … nachtragen (beide) | `anteil_nachtragen()`, `serverschluessel_nachtragen()` | jeweils `abweichend` |
| Server-Anteil neu erzeugen | `anteil_neuanfang()` | **nur** `abweichend` |

Drei Dinge daran sind Absicht und keine Geschmacksfrage:

- **Die Karte zeigt den Wert nicht**, nur die Kennung. Der Wert steht an
  genau einer Stelle in der Oberfläche: auf dem Schlüsselblatt, das man
  ausdruckt. Ein Wert, der auf jedem Bildschirm vollständig steht, steht
  früher oder später in einem Screenshot in einem Ticket.
- **Nachtragen schreibt nur bei Übereinstimmung.** `anteil_nachtragen()`
  rechnet die Kennung des eingegebenen Werts und vergleicht sie mit
  `anteil_zustand()['erwartet']`; passt sie nicht, wird nichts geschrieben
  und die Meldung nennt beide Kennungen. Leerzeichen, Bindestriche und
  Großschreibung werden vorher entfernt
  (`schluessel_eingabe_normalisieren()`) — das Blatt druckt in
  Vierergruppen, und wer sie mit abtippt, soll nicht dafür bestraft werden.
- **`anteil_wechseln()` sichert den alten Wert zuerst** und nimmt ihn bei
  einem Fehlschlag des zweiten Schreibvorgangs wieder zurück. Ein Wechsel,
  der auf halbem Weg stehenbleibt, wäre die Lage `abweichend` ohne Blatt.

*Der Neuanfang ist serverseitig einmalig.* `anteil_neuanfang()` prüft die Lage
selbst und weist alles ab, was nicht `abweichend` ist — ein F5 nach dem
Absenden erzeugt sonst einen zweiten neuen Anteil und macht die Konten, die
gerade zurückgesetzt wurden, ein zweites Mal ungültig.

*Das Schlüsselblatt* (`betrieb_schluesselblatt.php`) ist die einzige Seite der
Anwendung ohne Gerüst, deren Zweck der Ausdruck ist. `Cache-Control: no-store`,
`Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`; Werte in
Vierergruppen (`hex_vierergruppen()` in `db.php`). Sie steht in
`WARTUNG_AUSNAHMEN` — die Lage, in der man sie braucht, ist eine Wartungslage.
Das zugehörige `@media print` in `assets/style.css` (Abschnitt 26) war das
**erste** des Projekts. Seit Web 21.1.0 (P5c/AP9) baut das Blatt auf dem
Druckblatt `.blatt-druck` wie Code- und Notfallblatt (`Design.md` 9.39):
genau eine A4-Seite, je Wert eine Kachel mit sechzehn nummerierten Gruppen,
das Logo als Standard der Installation, auf einer Anlage mit Etikett die
Umgebungszeile; `.blatt-wert` ist entfallen.

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

**Die Hüllenkennung `edka1:` (seit Web 19.7.0, S10, E-S10-05).** Eine Hülle,
die am Server-Anteil hängt, lautet `edka1:<kennung>:<base64>` — die acht
Hexzeichen sind die Kennung des Anteils, mit dem sie gebaut wurde. Der
Chiffretext dahinter ist derselbe AES-256-GCM-Aufbau wie bisher.

*Warum nicht schlicht `edk2:`.* Zwei Gründe. `EdCrypto.decrypt()` weist jede
Kennung außer `edk1:` als „neuere Programmfassung" ab — die Fassung der
**Hülle** ist aber etwas anderes als die Fassung des **Verfahrens**. Und der
Browser braucht bei einer Rotation die Auskunft, mit *welchem* von zwei
Anteilen er öffnet; die Statusseite muss zählen können, wer noch auf dem alten
steht. Beides steht im Präfix und ist lesbar, ohne dass jemand etwas öffnet
(`SUBSTRING` in SQL, `huelle_anteil_kennung()` in PHP).

*Nur `WRAP_RE` nimmt die neue Kennung an, `PAT_BLOB_RE` bleibt eng.* Das ist
kein Versehen: Der Anteil steckt im Datenschlüssel, und der öffnet
ausschließlich die Hülle. Der Inhaltsschlüssel darin und damit jeder
`pat_blob` sind unverändert — **kein Datensatz wird angefasst**, wenn ein
Konto umstellt. `pat_wrap_rc` bekommt ebenfalls kein `edka1:`-Präfix.

Beide Hüllen entstehen **gemeinsam mit dem Passwort** in `pw_handling.php`
(siehe unten). Ein anmeldbares Konto ohne Hüllen kann es dadurch nicht geben;
die früher in `auth_guard.php` erzwungene Ersteinrichtung entfällt seit
Web 2.7.0 ersatzlos. Passwort-Ändern re-wrappt clientseitig **und atomar**:
Lässt sich der Inhaltsschlüssel nicht umpacken, wird auch das Passwort nicht
geändert. Eine Admin-Passwortvergabe existiert bewusst nicht.

**Der Wechsel der Anmeldeadresse verlangt seit Web 15.6.0 einen
Passwortnachweis** (Backlog Nr. 128, K-7). Bis dahin schrieb
`einstellungen.php` sie allein mit dem CSRF-Token um: Wer eine offene Sitzung
übernahm, konnte die Adresse auf seine eigene setzen, sich den Setz-Link
schicken lassen und das Konto übernehmen. Die geschützten Angaben blieben zu —
der Reset-Weg verlangt den Wiederherstellungsschlüssel —, aber die
Klartextfelder nicht, und die rechtmäßige Besitzerin war ausgesperrt.

Der Nachweis ist derselbe wie beim Passwortwechsel: `old_token`, im Browser
aus dem aktuellen Passwort abgeleitet (`EdCrypto.deriveKeys(pw, KDF_SALT,
KDF_ITER)`). Er wird **nur beim tatsächlichen Wechsel** verlangt — Name und
Logo bleiben frei —, und `session_epoch` bleibt unverändert: Es hat sich kein
Passwort geändert.

Auf beiden Wegen — Profil und Verwaltung (`admin_user.php`) — geht danach eine
**Hinweismail an die ALTE Adresse** (`profil_adresswechsel_melden()` in
`email_lib.php`). Sie ist die einzige Stelle, an der die Besitzerin von einem
unterschobenen Wechsel erfährt, und geht deshalb an die alte und nicht an die
neue: Die neue gehört im Missbrauchsfall dem anderen. Scheitert der Versand,
steht das im Protokoll (Reiter System) und der Wechsel bleibt bestehen — ihn
zurückzurollen, weil ein Mailserver klemmt, wäre die schlechtere Wahl. Die
**Bestätigung der neuen** Adresse (Double-Opt-In) kommt mit R37.6 in P5.

**Das Anmeldeformular trägt seit Web 15.6.0 ein Formular-Token** (Backlog
Nr. 127, K-8). Bis dahin war es das einzige Formular ohne: Eine fremde Seite
konnte einen abgemeldeten Browser per Top-Level-POST in ein **Angreiferkonto**
anmelden — Adresse und Token des Angreifers im Formular, abgeschickt per
Skript. Die geschützten Angaben sind davon nicht betroffen (ohne `edk` öffnet
sich keine fremde Hülle), aber was danach eingegeben wird, landet im fremden
Konto und ist dort lesbar.

`csrf_token()`, `csrf_field()` und `csrf_ok()` stehen deshalb in
`session_lib.php` und nicht mehr in `auth_guard.php`: Die eine Seite, die den
Schutz am nötigsten braucht, lädt `auth_guard.php` nicht. In `auth_guard.php`
bleibt `csrf_check()`, der Abbruchweg der angemeldeten Seiten. Das Token
entsteht **faul** — `session_lib.php` wird eingebunden, bevor
`sitzung_starten()` gelaufen ist.

Zwei Eigenschaften der Prüfung am Anmeldeformular: Sie steht **vor** allen
Zählern, damit ein abgelaufenes Formular keine Ratenstrafe auslöst (es ist
kein Fehlversuch), und sie antwortet mit der Anmeldeseite und der Meldung
„Das Formular ist abgelaufen. Bitte versuche es erneut." statt mit einer
403-Seite. Nach erfolgreicher Anmeldung wird das Token **neu gezogen**, wie
die Sitzungskennung: Ein vom Angreifer vorgesetztes Token überlebte den
Wechsel sonst.

Zwei Prüfmittel melden sich ohne Browser an und schicken das Feld seither
selbst: `tools/referenzdatensatz/einspielen/sitzung.py` (holt zuerst
`login.php`) und `tools/proben/gpx/probe.php` (tat den GET schon, las das Feld
aber nicht). Alle übrigen fahren einen echten Browser und schicken es von
selbst mit.

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

**Warum `ui_krypto_bootstrap()` seit Web 15.6.0 immer `CSRF` ausgibt.** Schritt 3
ruft einen API-Endpunkt und braucht dafür das Token. Bis dahin war das ein
Schalter, und drei von sieben Seiten stellten ihn (`index.php`, `import.php`,
der Sicherungsblock in `einstellungen.php`) — `suche.php`, `zeitraum.php`,
`einsatz.php` und `einsatz_form.php` nicht. Wer nach dem Anmelden zuerst dorthin
ging, bekam keine Anhebung; schlimmer, `loeseVormerkung()` **verwirft** das
Vormerkfach auch dann, und damit war sie für diese Sitzung verloren. Folgenlos
blieb das nur, solange `KDF_ITER_LISTE` einen einzigen Eintrag hatte. Mit der
Anhebung auf 600 000 wurde daraus ein Fehler, gemessen am Referenzbestand: Konto
auf 320 000, Anmeldung, `suche.php` — Fach weg, Rundenzahl unverändert, und beim
nächsten Anmelden dasselbe. Der Schalter ist deshalb wirkungslos gestellt; das
Feld `csrf` wird noch angenommen und ignoriert.

**Wann der Altwert aus `KDF_ITER_LISTE` verschwinden darf**, sagt die
Wartungsseite (Betrieb → Status, Zeile „Schlüsselableitung"): Sie nennt seit
Web 15.6.0 nicht nur verwaiste Rundenzahlen, sondern auch, **wie viele Konten
noch unter dem Zielwert stehen**. Solange dort eine Zahl steht, rechnet jede
Anmeldung zweimal ab — 298 ms plus 551 ms statt 551 ms, gemessen auf einem Kern
des Prüfcontainers. Steht keine mehr, darf der Altwert gestrichen werden —
**mit einer Ausnahme, die die Zeile selbst nennt:** Das Demo-Konto zählt dort
nicht mit. Es steht auf der Rundenzahl seiner Fixture, die stille Anhebung
überspringt es (`api/kdf_upgrade.php`, E-P1-19), und der Reset spielt die
Fixture alle 30 Minuten neu ein; solange die Fixture den Altwert trägt
(heute 320 000, Backlog Nr. 155), bleibt er in `KDF_ITER_LISTE`, sonst
könnte sich das Demo-Konto nicht mehr anmelden. Der Demo-Satz erscheint nur,
solange dieser Wert in der Liste steht; fehlt er, ist das Demo-Konto eines
der blockierten Konten der roten Zeile.

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
(`tools/proben/frist/`, dort auch die Gegenprobe, dass die Frist weiterhin
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

**Eine Karte entsteht über `EdKarte.anlegen(el, o)`** (`assets/map_layers.js`,
seit Web 20.33.0, Schritt 15 AP8a). Die Funktion macht, was auf allen
Kartenseiten gleich war: `L.map(el, o.leaflet)`, `setView(o.mitte, o.zoom)`,
`attachBaseLayers`, `attachFullscreenControl` — und bei `o.groesse`
zusätzlich `attachGroessenControl`. Sie gibt die Karte zurück.

| Parameter | Bedeutung | Vorgabe |
|---|---|---|
| `el` | Behälter-ID oder Element, 1:1 an `L.map()` | — |
| `mitte` | `[lat, lon]` des Ausgangsausschnitts | **keine** (Wurf) |
| `zoom` | Zoomstufe des Ausgangsausschnitts | **keine** (Wurf) |
| `groesse` | dritter Kartenknopf (`attachGroessenControl`) | `false` |
| `leaflet` | Optionsobjekt, 1:1 an `L.map()` | keine Optionen |

`mitte` und `zoom` haben **keinen** Vorgabewert, und das ist Absicht: Die vier
Kartenseiten benutzen zwei verschiedene Ausschnitte (`[47.7, 10.3]`/Zoom 9 auf
Einsatzansicht und Tagesspuren, `[48.5, 10.5]`/Zoom 7 auf Tagesübersicht und
Zeitraum). Ein Vorgabewert zöge sie auf einen, und eine Karte, die still am
falschen Ort steht, fällt niemandem auf.

**`setView()` kommt zuerst**, vor den Ebenen. Ohne festen Ausschnitt gilt die
Karte Leaflet als nicht bereit: Es nimmt eine Ebene entgegen, rechnet ihre
Bildschirmposition aber nicht aus, und ein späteres `setStyle()` scheitert mit
„this._point is undefined". Bis Web 20.32.1 setzte `index.php` den Ausschnitt
als einzige zuletzt.

**Karten-Controls (`assets/map_fullscreen.js`, `assets/map_layers.js`, ab Web
2.5.0):** Beide Dateien exportieren je eine Funktion
(`attachFullscreenControl(map)` / `attachBaseLayers(map)`) und kapseln ihren
Zustand vollständig in Closures — keine globalen Variablen, damit mehrere
Karten pro Seite (aktuell max. eine) nicht kollidieren würden. Die vier
Kartenseiten (`index.php`, `einsatz.php`, `tag_spuren.php`, `zeitraum.php`)
rufen sie seit Web 20.33.0 nicht mehr selbst, sondern über `EdKarte.anlegen()`;
der Ortswahl-Dialog (`assets/ortswahl.js`) ruft `attachBaseLayers` weiter
unmittelbar — er ist ein Modul, keine Seite, und legt seine Karte in einem
Dialog an.

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

**`attachBaseLayers` startet seit Web 15.3.1 auch die Größenüberwachung.**
Leaflet misst seinen Behälter **einmal**, beim Anlegen der Karte, und rechnet
danach mit dem gemerkten Wert; von selbst bemerkt es nur eine Änderung des
*Fensters*. Wächst der Behälter ohne Fensterwechsel, lädt es Kacheln nur für
den alten Ausschnitt — der Rest bleibt der Hintergrund von `.geo`, und
Herauszoomen hilft nicht, weil auch der Kachelbereich aus der gemerkten Größe
folgt. Genau das trat ab 1600 px auf, wo die Karte in der rechten Spalte des
Tagesrasters steht (E-P3-31) und mit der nachgeladenen Einsatztabelle wächst:
gemessen bei 1920 × 1080 ein Behälter von 400 × 840 px gegen eine gemerkte
Größe von 400 × 324 px, also 516 px ohne Kachel. Ein `ResizeObserver` auf dem
Behälter zieht `invalidateSize()` nach, gebündelt über
`requestAnimationFrame`. Er sitzt in `attachBaseLayers()`, weil das der
**eine** Aufruf ist, den jede Karte macht — eine eigene Datei bräuchte fünf
Einbindungen, und die vergessene fünfte fiele nicht auf, sondern zeigte eine
halbe Karte. Browser ohne `ResizeObserver` behalten das alte Verhalten.

**Marker-Satz und Spurfarben (`assets/geo.js`, ab Web 9.2.0):** Das
`EdGeo`-Modul liefert alles, was auf einer Einsatzkarte steht, aus einer
Hand — **acht Exporte**, hier vollständig:

| Export | Was | Klasse | Maß |
|---|---|---|---|
| `markerStandort()` / `markerZiel()` | weißes Schild mit Haus- bzw. Klinik-Symbol; `ring: 'start' \| 'ende' \| 'beide'` färbt seinen **Rand** | `.geo-schild-kasten`, `.geo-ring-*` | 32 px, mit beidem 38 |
| `markerEinsatzort()` | oranger Kreis mit Einsatzort-Symbol | `.geo-kreis` | 28 px |
| `markerRing()` | Ring **ohne** Schild — Anfang oder Ende der Aufzeichnung abseits von Standort und Ziel | `.geo-ringpunkt` in `.geo-ringpunkt-feld` | Zeichnung 14 px, Antippfläche 24 |
| `markerPunkt()` | kleiner Farbpunkt in der Spurfarbe, für den manuellen Abfahrtort | `.geo-punkt` | 12 px |
| `pfeile()` | Richtungspfeile alle 140 Bildschirm-Pixel auf einer Spur, neu verteilt bei jedem Zoom (der `remove`-Handler der Ebene räumt den Zuhörer ab) | `.geo-pfeil` | 20 px |
| `spurFarbe(i)` / `ruheFarbe()` | die Farbe, nicht die Zeichnung | — | — |

Alle Marker sind `divIcon`s; **Form und Farbe stehen im Stylesheet, nicht im
Skript** (`docs/Design.md` 9.30 beschreibt sie). Die **Spurfarben** kommen als
Token aus `:root` (`--spur-1 … --spur-8`, `--spur-ruhe`); `EdGeo.spurFarbe(i)`
liest sie per `getComputedStyle`, JS enthält keinen Farbwert.

> **Die Maße stehen zweimal** — als Token im Stylesheet und als Zahl in
> `geo.js`, weil Leaflet sie für `iconSize` und `iconAnchor` braucht. Wer
> eines ändert, ändert beides; sonst wandert der Anker, und es meldet sich
> nichts. Betroffen sind `--geo-schild`/`SCHILD_PX`, `--geo-kreis`/`KREIS_PX`
> und `--geo-ringpunkt`/`RINGPUNKT_PX`.

**Zwei Zeichen brauchten seit jeher einen Kasten und hatten keinen** (behoben
mit Web 15.9.0): `.geo-pfeil` trug seine Drehung und `.geo-punkt` seine Größe
an einem `<span>` ohne `display` — und an einem nicht ersetzten
Inline-Element wirken weder `transform` noch `width`. Die Pfeile zeigten
dadurch ausnahmslos nach Norden (Backlog Nr. 72), der Abfahrtort maß
4 × 18 px statt 12 × 12 und zeigte seine Farbe nie (Nr. 162). Nachweisbar war
beides nur an der **Geometrie**: `getComputedStyle` meldet die Drehmatrix
auch dort, wo sie nichts bewirkt. Gemessen wird deshalb die Bildschirmmatrix
des SVG (`getScreenCTM`), und dafür gibt es seit AP3 einen Weg der Klickprobe
(`ap3-pfeile-drehen`).

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
die AdministratorIn **ohne** Passwort an und zeigt auf der Erfolgsseite den
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
den Vorbelegungen der Rolle aus `crew_presets` des Kontos, aber ohne
Schranke. Fachlicher Grund: Wer
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

**Ein Diensttag mit einem Rettungsmittel nur für den Tag führt die Rollen
seiner Betriebsart** (Nr. 169, E-P5c-47, seit Web 21.0.0): bei `kind = 'air'`
`p1`, `p2`, `hems`, `fr`, `other`, bei `ground` `driver`, `trainee`, `other`
(`crew_roles_fuer_art()`) — `dt_tagesrettungsmittel_rollen()` in `diensttag_lib.php`, eine
Stelle für Zuordnen, Vorschau und Einsatzformular. **Der Typ zählt dabei
nicht** (E-P5c-126): `VEHICLE_TYPEN[…]['rollen']` steuert nur die
Rollen-Vorlagen gespeicherter Rettungsmittel, das Tagesrettungsmittel hat
keine Vorlage, aus der man wählen könnte. `einsatz_form.php` fragt
`dt_ist_tagesrettungsmittel()` und schneidet `dt_crew()` auf **genau diesen
Satz** zu. Ohne den Zuschnitt hingen die angebotenen Rollen davon ab, was dem
Tag *vorher* zugeordnet war — `dt_rollensatz_einfrieren()` löscht beim Wechsel
nur **leere** Rollen, ein benannter Name überlebt und brachte seine Rolle mit.
Die Namen bleiben davon unberührt: Sie stehen weiter in `day_crew` und in der
Leseansicht des Tages. **Bis Web 20.47.0 führte ein solcher Tag gar keine
Rollen** (E-S9-10, seit 18.1.1), und Besatzung ließ sich an ihm nicht erfassen;
die Migration `2026_09_25_tagesrettungsmittel_rollen` trägt den Satz für den
Bestand nach (E-P5c-123). Eine Wiederherstellung tut es nicht: Sie legt an, was
in der Datei steht (E8).

Dieselbe Mechanik tragen zwei weitere Filter: **`cap_gate`** prüft die
eingefrorenen Fähigkeiten (`day_capabilities`) und steuert damit Winde und
Bergwacht, **`kind_gate`** die Art des Diensttags. Alle drei laufen über
`mf_gates_erfuellt()`.
**`cap_gate` gilt im Formular ohne Rücksicht auf die Betriebsart** — auch ein
bodengebundener Bergwacht-Dienst bekommt seine Windenfelder. Das ist die
*Bearbeitung*; die *Auswertung* in Tages- und Zeitraumübersicht folgt seit
Web 20.35.0 einer engeren Regel (E-ZE-31; siehe „Zeitraum-API" weiter unten
in diesem Abschnitt). Wer nur eine der
beiden Stellen liest, hält die andere für einen Fehler.
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
clientseitig (`index.php`, `renderCrewFields()` aus der Antwort von
`api/day.php`), weil das Rettungsmittel im Formular selbst gewechselt werden
kann. Im Einsatzformular steht es fest, daher serverseitig.

**Seit Web 18.1.0 zeichnet es sie beim Wechsel sofort neu** (E-S9-11). Dafür
gibt es `api/day.php?vorschau=<vehicle_id>[&base=<base_id>]` — und seit
Web 21.0.0 `?vorschau=adhoc&art=air|ground[&base=<base_id>]` für ein
Rettungsmittel nur für den Tag (Nr. 169): einen **lesenden**
Aufruf, der Rollensatz (`vehicle_roles`) und Vorlagen (`crew_presets` des
Standorts) zu einer **noch nicht gespeicherten** Wahl liefert und nichts
schreibt. Eingefroren wird weiterhin erst beim Speichern durch `dt_zuordnen()`
(E8). Der Standort kommt mit, weil die Vorlagen an ihm hängen und Formular und
Rettungsmittel dort auseinanderfallen können; ohne ihn fällt die Vorschau auf
den Standort des Rettungsmittels zurück. `dt_vehicle_erlaubt()` und
`dt_base_erlaubt()` gelten auch hier — ohne sie beantwortete der Endpunkt für
jede Kennung, welche **Namen** an einem fremden Standort hinterlegt sind.

**Einsatzort-Höhe:** `site_elevation_lib.php` (`compute_site_elevation()`) ist
die **einzige Implementierung** — Referenzzeitpunkt Phase 5 „Ankunft
PatientIn", Fallback Phase 6, Toleranz 300 s (Konstante
`SITE_ELE_TOLERANCE_S`) zum zeitlich nächstgelegenen `track_points.ele`.
Aufgerufen von `ingest.php` (nach jedem Uhr-Upload), `einsatz_form.php` (nach
manuellem Speichern — Phasen ändern sich, der Track bleibt gleich),
`backup_lib.php` (nach Restore — aus den gerade eingespielten Phasen/Track neu
berechnet statt aus der Datei übernommen) und `migration_lib.php` (Backfill bei
der Migration). Kein Formularfeld, daher nicht in `mission_fields.php`.

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

**Seit Web 15.9.0 kommt `faehigkeiten` dazu** (S9/AP3, E-S9-04) — ein flaches
Objekt über `VEHICLE_CAPABILITIES`, heute `{winch, bergwacht}`, mit
Wahrheitswerten. Es sagt, welche Fähigkeiten die **Luft**-Diensttage des
Zeitraums tragen, gerechnet als `GROUP BY` über `day_capabilities` mit Join
auf `days`. Die Zeitraumübersicht entscheidet daran über die beiden
Windenkacheln — und **seit Web 20.35.0 auch über die beiden Tabellenspalten**
(E-ZE-31) —, statt sie aus der Einsatzliste zu erschließen: „null
Windeneinsätze" ist eine Aussage über den Dienst, „Winde nicht eingerichtet"
eine über die Stammdaten, und bis dahin waren beide nicht zu unterscheiden.

**„Luft" ist seit Web 20.35.0 die Regel und nicht mehr eine Lücke.** Hier
stand bis dahin, der Artfilter sei seit Web 20.3.0 eine Lücke (Backlog
Nr. 198), weil ein bodengebundener Bergwacht-Diensttag die Fähigkeiten
ebenfalls trägt und hier übergangen wird. Das ist entschieden worden, und
zwar so: Die **Auswertung** — Kacheln und Tabellenspalten in Tages- und
Zeitraumübersicht — folgt der Betriebsart **und** der Fähigkeit; die
**Bearbeitung** (`einsatz_form.php`, `einsatz.php`) folgt der Fähigkeit
allein, auch bodengebunden. Das Einsatzformular zeigt die Windenfelder
eines solchen Tages also weiter, die Haken bleiben eintragbar — ausgewertet
werden sie in Tages- und Zeitraumübersicht nicht. Vorgelegt wurden dafür die
Zahlen des Bestands: vier bodengebundene Bergwacht-Diensttage mit
Fähigkeiten, zwei davon mit einem dokumentierten Windeneinsatz.

**Die Suche macht es anders, und das ist ebenfalls entschieden.**
`api/suchindex.php` liefert seit Web 20.35.0 dasselbe `faehigkeiten`-Objekt,
aber **ohne Artfilter** — sie sucht im ganzen Bestand, nicht in einem
Zeitraum, und ein bodengebundener Bergwacht-Dienst ist genauso ein Treffer
wie ein luftgebundener. Wer die eine Abfrage ändert, ändert nicht
selbstverständlich die andere mit.

Drei Bedingungen der Abfrage sind nicht verhandelbar. `day_capabilities`
führt **weder `user_id` noch `deleted_at`** — der Join auf `days` trägt
Kontobezug und Papierkorb, sonst zählte die Antwort fremde und gelöschte
Diensttage mit. Und `d.kind = 'air'`: Die Migration
`2026_08_17_notarzt_erweiterung` hat seinerzeit **jedem** bestehenden
Diensttag beide Fähigkeiten gegeben, ohne nach der Art zu fragen (das
Gegenstück für `vehicle_capabilities` filtert dagegen auf `air`). Auf einem
gewachsenen Bestand trägt deshalb auch ein NEF-Tag von 2025 die Winde — ohne
diese Bedingung stünden die Kacheln in jedem Zeitraum, der einen Alttag
enthält, und die Anzeige sähe richtig aus, während sie nur die Altlast
zeigte.

`bergwacht` fährt mit, obwohl heute keine Kachel daran hängt: Der Schlüssel
spannt sich über den Katalog auf und wächst mit ihm; eine Antwort, die nur
die Hälfte nennt, müsste beim nächsten Verbraucher erweitert werden.

**Fehlerbehandlung der Lese-/Schreib-APIs:** `api/range.php`, `api/day.php`,
`api/mission.php`, `api/suchindex.php` und `api/backup_data.php` kapseln ihre Datenbankzugriffe in
try/catch (Muster ursprünglich aus `api/backup_restore.php`) und antworten bei
einer Ausnahme mit `{"error": "<endpunkt>", "meldung": "<Exception-Message>"}`
statt eines leeren HTTP 500 — wichtig z. B. direkt nach einem Deploy mit
DB-Änderung, aber vor dem Migrationslauf (Betrieb → Updates). Neue Endpunkte sollten
demselben Muster folgen. Die jeweiligen Frontends (`zeitraum.php`,
`einsatz.php`, `index.php`, `suche.php`) zeigen `error`+`meldung` in einer
Fehlerbox an.

**Suche (`suche.php`, `api/suchindex.php`, ab Web 3.1.0).** Gefiltert wird
vollständig im Browser. Das ist keine Optimierung, sondern eine Folge der
Ende-zu-Ende-Verschlüsselung: Einsatznummer, Name, Geburtsdatum, Diagnose,
Einsatzort und — seit S9/AP7 — die Notizen des Einsatzes liegen im `pat_blob`, der Server sieht davon nur Chiffretext und
kann darin nicht suchen. Zusätzlich wäre ein Suchbegriff wie ein Nachname
selbst schon ein Patientendatum — er darf den Browser gar nicht verlassen.
`api/suchindex.php` nimmt deshalb **keine Suchparameter entgegen**; es liefert
den kompletten aktiven Bestand der angemeldeten Person.

**Seit Web 20.35.0 kommt `faehigkeiten` mit** (Schritt 15 AP9a, E-ZE-31) —
dasselbe flache Objekt über `VEHICLE_CAPABILITIES` wie in `api/range.php`,
aber **ohne Artfilter**: Es sagt, welche Fähigkeiten im Bestand überhaupt
eingerichtet sind, luft- wie bodengebunden. Die Suchtabelle entscheidet
daran über die Spalten „Winde" und „Bergwacht", statt sie aus der Treffer- oder
Bestandsliste zu erschließen. Der Unterschied ist genau der Fall, den man
sucht: „null Windeneinsätze" ist etwas anderes als „Winde nicht
eingerichtet", und wer nachtragen will, muss die Spalte sehen.

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
| `pv` / `pb` | Cycles mit PatientIn von / bis | `av` / `ab` | Alter von / bis |
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

**Die Sichtbarkeit entsteht aus dem Feldkatalog, nicht aus zwei Listen.**
Bis S3 standen hier `GRUPPE_NUR_WENN` (ein ganzer Block) und `FELD_NUR_WENN`
(ein einzelnes Feld) — zwei handgepflegte Listen, also genau der
Einzelfall-Wildwuchs, den der Katalog abschaffen soll. Seither trägt jeder
Filter, der zu einer Katalogspalte gehört, sie als `spalte`; sichtbar ist er,
wenn der **gesamte** Bestand zu dieser Spalte etwas führt (nicht die
Trefferliste — sonst hüpfte die Spalte beim Tippen). Ein Filter **ohne**
Spalte — Zeitraum, Uhrzeit, Wochentag, Strecke, Dauer, Alter, Standort,
Rettungsmittel, Besatzung — ist immer sinnvoll und steht immer da. Ein
**Block** verschwindet, wenn alle seine Filter verschwunden sind; er braucht
keine eigene Bedingung mehr.

**Eine Ausnahme mit Herkunft: Felder an einer Fähigkeit** (ab Web 20.36.0,
E-ZE-35). `KATALOG_CAP` bildet Spalte auf Fähigkeit ab — erzeugt aus
`cap_gate` und **auf die Unterfelder vererbt**, sodass `winch_cycles` unter
`winch` ohne eigenen Eintrag mitgeht. Ein solches Feld folgt der **Fähigkeit**
statt dem Bestand: Winde und Bergwacht sind durchsuchbar, sobald irgendein
Diensttag die Fähigkeit führt — luft- wie bodengebunden. Vorher fiel der Block
`bergrettung` in einem Bestand mit eingerichteter, aber nie benutzter Winde
ganz weg, und damit war auch **„Winde: nein"** nicht zu suchen; der Filter
fehlte gerade in dem Fall, für den man ihn braucht.

Die Ausnahme für geteilte Links gilt weiter — ein Filter, den ein Fragment
gesetzt hat, bleibt sichtbar, auch wenn weder Fähigkeit noch Bestand ihn
rechtfertigen. `gruppenSichtbarkeit()` läuft deshalb beim Start **nach**
`fragmentLesen()` und erneut nach „Filter zurücksetzen".

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

**Filterblöcke: seit S3 katalogabgeleitet.** Der Abschnitt zur Suche weiter
oben beschreibt die geltende Regel und die Fähigkeits-Ausnahme aus Web 20.36.0.
Hier stand bis dahin `GRUPPE_NUR_WENN` — eine handgepflegte Liste mit einem
Eintrag je Block, die es seit S3 nicht mehr gibt.

**Spalten nach Bestand (`nurWenn` in `assets/missiontable.js`, ab Web 6.2.0).**
Dieselbe Überlegung eine Ebene tiefer: Eine Spalte, die im ganzen Bestand leer
bleibt, kostet auf schmalen Geräten Platz und sagt nichts. `nurWenn` bekommt den
Bestand und entscheidet, ob die Spalte überhaupt erscheint — heute `art` (mehr
als eine Art vorhanden), `col` und `fehl`. Welche Liste der Bestand ist,
sagt die Seite mit `setSpaltenBestand()`: `suche.php` setzt ihn **einmal** auf
den Gesamtbestand (sonst käme und ginge die Spalte beim Tippen),
`zeitraum.php` bei jedem Tabwechsel auf die Einsätze des Tabs. Ohne den Aufruf
gilt die Trefferliste selbst.

**Seit Web 20.37.0 baut das Modul alle drei Tabellen** (Schritt 15 AP9b,
Backlog Nr. 57). `index.php` hatte bis dahin einen eigenen Erzeuger: einen
handgeschriebenen `<thead>`, `DAY_COLS`, eine `switch`-Leiter in `sortVal()`
und ein eigenes Sortierblatt. Gezählt: Zeilenerzeugung `tr.innerHTML`
**1 → 0**, Spaltenlisten **4 → 1**, Sortierblatt-Erzeuger **3 → 1**.

Der Vorspann steht in **`ui_tabellen_bootstrap()`** (`ui.php`) und setzt
`ART_SYMBOLE`, `TYP_SYMBOLE` und `KATALOG_SPALTEN`; er muss **vor**
`assets/missiontable.js` stehen. Die drei Hakenspalten gleicht das Modul
gegen `KATALOG_SPALTEN` ab: **Der Katalog bestimmt, welche es gibt
(`day_col`), das Modul, wie sie aussehen und in welcher Reihenfolge**
(E-ZE-34). Damit heißt `day_col` ab jetzt „Spalte in **jeder**
Einsatztabelle" und nicht mehr „Spalte in der Tagesübersicht" — wer den
Schlüssel entfernt, nimmt die Spalte auch aus Suche und Zeitraumübersicht.
Ohne `KATALOG_SPALTEN` bleiben die drei Spalten unverändert; das ist ein
benannter Rückfall, damit eine Seite ohne Vorspann nicht still Spalten
verliert.

`opts.ohne` nennt die Spalten, die eine Seite **nicht** führt —
`index.php` gibt `['fehl', 'day']` an. Beides ist ausdrücklich, nicht
geraten: `day` stand in einem Zwischenstand als `nurWenn` („nur bei mehreren
Tagen") im Modul, und das nahm der **Zeitraumübersicht** ihre Datumsspalte,
sobald alle Treffer eines Monats auf einen Tag fielen — während sie weiter
nach ihr sortierte. Gefunden hat das der Bildvergleich.

**`start_sort` sortiert chronologisch** (E-ZE-32). `api/day.php`,
`api/range.php` und `api/suchindex.php` liefern seit Web 20.37.0 den
Zeitpunkt als `Y-m-d H:i` in Ortszeit. `start_hhmm` allein taugt nicht:
Bei einem Dienst über Mitternacht steht 01:10 als Zeichenkette vor 23:50,
und `day` hilft nicht überall — in `api/day.php` und `api/range.php` ist es
der **Diensttag**, für beide Einsätze derselbe. Bei Gleichstand entscheidet
`_no`, in beiden Richtungen. Das **mobile Sortierblatt** baut ebenfalls das
Modul (`opts.sortblatt`, `opts.sortlabel`); bis Web 20.37.0 stellte es auf
Suche und Zeitraumübersicht um, **ohne neu zu zeichnen**.

**`winch` und `bw` folgen seit Web 20.35.0 nicht mehr dem Bestand, sondern der
Fähigkeit** (E-ZE-31). `nurWenn` bekommt dafür ein **zweites** Argument, das
die Seite mit `setFaehigkeiten()` setzt: `zeitraum.php` reicht die
Luft-Fähigkeiten des Zeitraums durch und im Bodentab ausdrücklich `{false,
false}`, `suche.php` die Fähigkeiten des ganzen Bestands ohne Artfilter.
**Ohne den Aufruf gilt weiter der Bestand** — das ist ein benannter Rückfall
und kein Vergessen: Eine Seite, die das Modul einbindet und die Fähigkeiten
nicht kennt, soll ihre Spalten nicht stillschweigend verlieren. **Sortiert** wird weiterhin über alle Spalten,
auch über verborgene: Ein geteilter Link kann nach einer Spalte sortieren, die
der eigene Bestand nicht zeigt — die Reihenfolge stimmt dann trotzdem, nur der
Pfeil hat keinen Kopf.

**Überschneidende Diensttage (R57, E-S4-76, ab Web 13.3.0).**
`dt_ueberlappungen()` (`diensttag_lib.php`) liefert die Diensttage, die sich
mit einem gegebenen zeitlich überschneiden; `index.php` zeigt daraus einen
Hinweis in der Tagesübersicht. Der Fall ist **F-S4-D**: Zwei Geräte am selben
Dienst legen zwei Diensttage an, weil `day_refs` je Gerät geschlüsselt ist —
es geht nichts verloren, es steht alles doppelt.

Drei Festlegungen, jede mit einem Grund:

- **„Aktiv" heißt „nicht im Papierkorb", nicht „läuft noch".** Im Code sind
  beide Lesarten belegt (`deleted_at IS NULL` gegen `ended_at IS NULL`). Ein
  Tag im Papierkorb ist keine Doppelung mehr — wer ihn dorthin gelegt hat, hat
  den Fall entschieden. Ein **beendeter** Tag ist sehr wohl eine: Die Doppelung
  fällt in der Jahresstatistik auf, also lange nach dem Dienst.
- **Ein laufender Tag endet „jetzt".** `ended_at` ist NULL, solange der Dienst
  läuft; ohne diesen Ersatz überschnitte er sich mit nichts. Die Rechnung steht
  deshalb **in SQL** (`COALESCE(ended_at, NOW())`) und nicht in PHP: Das
  „jetzt" und der Vergleich müssen aus derselben Uhr kommen, sonst meldet die
  Funktion eine Überschneidung von Minuten, die es nie gab.
- **`DT_UEBERLAPPUNG_MIN` = 15 Minuten.** Der eigene Dienstwechsel
  überschneidet sich regelmäßig um Minuten; ein Hinweis, der dabei jedes Mal
  erscheint, wird überlesen. Der Auslöserfall — die vergessene Uhr im Spind —
  dauert Stunden.

**Serverseitig, nicht über `api/day.php`.** `index.php` kennt den gewählten Tag
bereits; die Abfrage kostet einen Index-Zugriff, und der Hinweis steht sofort
da statt nach dem Nachladen. Der Papierkorb-Hinweis daneben geht den anderen
Weg, weil er von `mitPapierkorb` abhängt — einer Angabe, die `dt_laden()` an
dieser Stelle gar nicht holt.

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
Altbestand meldete die Wartungsseite bis Web 15.0.0 unter „Einsätze ohne
Diensttag" — als Bericht, nicht als Migration. Der Bericht ist mit S8/AP2
ersatzlos entfallen (E-S8-17): Er stand seit dem Ausrollen von Web 5.4.0 auf
null und hätte auf jeder Installation, die je einen Migrationslauf gesehen hat,
auch nie wieder etwas anderes gezeigt. `ingest.php` quittiert Uploads für
Einträge im Papierkorb, verwirft sie aber; erst das endgültige Löschen schreibt
die Referenz nach `deleted_refs`. Schwere Löschungen laufen über serverseitige
Zwischenseiten mit Umfangsanzeige statt über Browser-Dialoge.

**Schutz bearbeiteter Einsätze:** Beim Ingest wird vor dem Upsert der
`uhr_gesperrt` geprüft. Ist es gesetzt, werden Metadaten/Phasen/Rea **nicht**
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
zwingend: Die Dateien enthalten Name, Geburtsdatum, Diagnose, Einsatzort,
Notizen und die übrigen geschützten Angaben aus 4.98, und diese Angaben dürfen den Rechner nur verschlüsselt verlassen. Ein
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
von Hand angelegte (`final=1, uhr_gesperrt=1, origin='import'`) — dadurch
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
`api/export_data.php` die Spalte `herkunft` bei jedem Export neu aus `uhr_gesperrt`
und dem Präfix von `client_ref` — eine Regel aus der Zeit vor der Migration
`2026_07_30_herkunft_bearbeitungsstatus`. Sie lieferte für genau einen Fall
etwas Falsches: Ein von der Uhr aufgezeichneter und danach im Formular
bearbeiteter Einsatz bekommt `uhr_gesperrt = 1` und erschien deshalb als „manuell",
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

**JSON in einem `<script>`-Block läuft über `json_js()`** (`db.php`, seit
Web 15.6.0, Backlog Nr. 135, K-15) — nicht über `json_encode()`. Der Baustein
setzt `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
JSON_UNESCAPED_UNICODE`.

**Warum es nicht reicht, dass `/` ohnehin maskiert wird.** Ein `</script>` im
Wert kann tatsächlich nicht entstehen — `json_encode()` schreibt `<\/script>`.
Der Seitenbruch kommt von der anderen Seite: `<!--<script>` in einem Wert
schiebt den HTML-Parser in den *double-escaped*-Zustand, und das nächste
**echte** `</script>` schließt den Block dann **nicht**. Gemessen mit einem
Profilnamen `<!--<script>` auf `import.php`: Vorher fehlten `KONTO_NAME`,
`APP_TZ` **und** `WEB_VERSION` im Browser — der ganze Block war verschluckt,
ohne Fehlermeldung. Nachher stehen alle drei, und der Name kommt Zeichen für
Zeichen an.

**Wo der Baustein nicht hingehört:** in API-Antworten, Dateiformate,
Zwischenspeicher und Protokolle. Dort ändern die Flaggen die **Bytes**, und an
Bytes hängen Prüfsummen (`komplett_lib.php` bindet den Dateikopf über SHA-256)
und Formatvergleiche. Gezählt am 07.09.2026: **79** Aufrufe von `json_encode()`
unter `server/`, davon **44 in einem `<script>`-Block** (alle umgestellt) und
**35 außerhalb** (unverändert).

**Was von K-15 offen bleibt:** `Strict-Transport-Security` ohne
`includeSubDomains` und die fehlende `Permissions-Policy` gehen mit der CSP
(Backlog Nr. 8, P5) — Kopfzeilen gehören in einen Zug. Der `querySelector` mit
einem Wert aus dem URL-Fragment in `suche.php` bleibt ebenfalls offen; er
bricht die Auswahl, ist aber kein XSS. Beides steht weiter unter Nr. 135
beziehungsweise Nr. 8. Der fehlende `(string)`-Cast in `csrf_check()` ist
dagegen mit Nr. 127 erledigt: Die Prüfung läuft jetzt über `csrf_ok()`, und
die castet.

**Sicherheit:** HTTPS erzwungen (.htaccess), Session-Cookies
HttpOnly/Secure/SameSite=Strict, CSRF für Formulare (`csrf_field`) — **seit
Web 15.6.0 auch am Anmeldeformular** (Backlog Nr. 127) — und für
JSON-POSTs (Header `X-CSRF`), PDO Prepared Statements durchgängig,
Passwörter/Schlüssel nur als Hash, Ratenschutz an **allen** ohne Anmeldung
erreichbaren Endpunkten — Anmeldung, Salz-Abfrage, Zurücksetzen-Anforderung,
Kopplung (s. 4.99 und 4.99b) —, Ingest mit Größen- (512 KB) und Wertevalidierung,
sensible Dateien und die Ordner `apk/` und `demo/` per .htaccess gesperrt
(Nr. 129), Referrer-Policy
`strict-origin-when-cross-origin` (OSM-Kacheln).

### Die Antwortzeit als Auskunft

Vier Endpunkte antworten für „gibt es nicht" und „gibt es" absichtlich
gleichlautend. Wortgleichheit allein genügt aber nicht: Wo der eine Zweig
rechnet und der andere nicht, ist die **Dauer** dieselbe Auskunft, nur leiser.
Drei Fälle gab es, alle seit Web 4.4.0 geschlossen:

| Endpunkt | Was den Unterschied machte | Wie er geschlossen ist |
|---|---|---|
| `login.php` | bei unbekannter Adresse lief keine bcrypt-Prüfung | Prüfung gegen `AUTH_VERGLEICHSWERT`, dazu `rate_gleiche_dauer()` |
| `ingest.php` | bei unbekannter Gerätekennung lief keine bcrypt-Prüfung | dasselbe — seit Web 13.0.0 gegen `GERAET_VERGLEICHSWERT` (SHA-256, E-S5-42): Der Unterschied wäre jetzt Mikrosekunden; der Blindvergleich bleibt, damit beide Zweige dieselben Schritte gehen |
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

Welcher Weg auf der eigenen Installation greift, stand bis Web 15.0.0 auf der
Wartungsseite unter „Umgebung". Es ist die Eigenschaft, an der die Gleichheit
beider Zweige hängt, und sie ließ sich sonst nirgends ablesen. **Seit
Web 15.3.0 steht sie auf Betrieb → Status**, in der Zeile „Antwort und
Versand" der Karte E-Mail. In Web 15.1.0 und 15.2.0 war sie vorübergehend
nicht abzulesen.

> **Hier stand bis Web 20.7.0 „bewusst keine Warteschlange"** — mit der
> Begründung, es gebe keinen Cronjob, und eine Warteschlange hätte den Link zum
> Zurücksetzen so lange liegen lassen, bis zufällig jemand eine Seite aufruft.
> **Die Begründung war richtig und die Folgerung falsch.** Sie vergleicht die
> Warteschlange mit einem Versand, der klappt. Klappt er nicht — und das ist
> der einzige Fall, in dem es auf den Unterschied ankommt —, dann ist die
> Nachricht ohne Warteschlange **weg**, und mit ihr liegt sie wenigstens da.
> Seit Web 20.8.0 gibt es sie (Abschnitt 4.99); der Reset-Link wird weiterhin
> **sofort** versucht, die Warteschlange fängt nur den Fehlschlag auf.

Der Preis des gewählten Weges bleibt: Auf Hosts ohne FPM oder LiteSpeed bleibt
der PHP-Arbeitsprozess nach dem Abschluss der Antwort noch bis zum Zeitlimit
des Versands belegt. Bei fünf Anforderungen je Stunde und Konto ist das klein,
aber nicht null — das Zeitlimit steht seit Web 20.9.0 als `MAIL_BUDGET_S`
(5 s) an **einer** Stelle statt an zehn.

### 4.99 Der Mailweg — Katalog, Warteschlange, Job (P5a/AP5)

**Was bis Web 20.7.0 galt.** Zehn Stellen riefen `smtp_send()` unmittelbar.
Scheiterte es, war die Nachricht weg — der Reset-Link, die Einladung, die
Warnung vor der vollen Platte. `smtp_versand_vermerken(false)` hielt nur fest,
*dass* etwas schiefging, nicht *was* und nicht *für wen*.

**Was gilt.** `mail_lib.php` ist der einzige Versandweg. Der einzige Aufrufer
von `smtp_send()` ist sie selbst.

```
mail_einreihen($schluessel, $empfaenger, $daten, $sofort = true)
   → Katalogeintrag suchen, Pflichtwerte prüfen, Adresse prüfen
   → überholte Zeilen derselben Art an dieselbe Adresse schließen
   → Zeile in mail_warteschlange
   → EINEN Versuch sofort — außer bei $sofort = false
   → 'zugestellt' | 'wartet' | 'abgelehnt'
```

**Nur einreihen** (`$sofort = false`, seit Web 20.38.0, F-P5c-29) gibt es für
die Rundmail: Der sofortige Versuch kostet bis zu `MAIL_BUDGET_S` je
Nachricht, und eine Rundmail an vierzig Konten hätte den Seitenaufruf bis zu
200 s aufgehalten. Die Antwort ist dann `wartet` oder `abgelehnt`, nie
`zugestellt`; der Job trägt die Zeilen hinaus, zehn je Lauf
(`MAIL_JE_LAUF`). Es ist ein Parameter an derselben Funktion, keine zweite:
Prüfung, Präfix, Frist und das Schließen überholter Zeilen bleiben eine
Stelle.

**Der Katalog** (`mail_katalog()`) führt **dreiundzwanzig** Einträge mit
`art`, `frist`, `pflicht`, `betreff` und `text` (hier stand bis Web 20.37.3
„zehn", der Stand von Web 20.8.0 — P5b hatte neun dazugebracht, 20.38.0
bringt `rundmail`, 20.41.0 `registrierung_erneut`, 20.42.0
`totp_zurueckgesetzt`, 20.44.0 `rueckweg_erneuert`; bis Web 21.1.0 stand
hier „zwanzig"). Der Name der Installation kommt aus `instanz_name()`, die
Kontaktzeile aus `instanz_kontakt()` — über `mail_rahmen()`, den alle
benutzen. Vorher gab es acht Mailtexte mit handgeschriebener Grußformel, und
einer davon fehlte das „Gen-EM" im Betreff.

**Die Leiter**: `MAIL_LEITER = [300, 1800, 7200, 28800, 86400]` — der Abstand
**zum vorigen Versuch**, nicht zum Einreihen. Fünf Versuche über 24 Stunden.

**Die Frist geht vor der Leiter.** Hat ein Eintrag eine `frist` (ein
Reset-Link gilt 3600 s), wird ein Versuch, der erst **nach** Ablauf fällig
wäre, gar nicht erst unternommen — die Zeile wird `zu_spaet`. Für
`passwort_reset` heißt das: **drei** Versuche, nicht fünf, denn die dritte
Sprosse läge bei 9300 s. Besser gar nichts als ein toter Link.

| Zustand | heißt | was geleert wird |
|---|---|---|
| `offen` | wartet auf den nächsten Versuch | — |
| `zugestellt` | der Mailserver hat sie angenommen | Adresse, Betreff, Rumpf |
| `unzustellbar` | fünf Versuche, alle gescheitert | **nur** Rumpf — die Adresse bleibt |
| `zu_spaet` | die Frist lief vor der Leiter ab | Adresse, Betreff, Rumpf |
| `ueberholt` | eine neuere Nachricht derselben Art entwertet sie | Adresse, Betreff, Rumpf |

**Der Job `mail`** steht als **erster** im Katalog von `jobs_lib.php`, und das
ist kein Zufall: `jobs_lauf()` arbeitet den Katalog der Reihe nach ab und
überspringt, was ins Restbudget nicht mehr passt — am Huckepack-Weg sind das
3 s für **alle** Jobs zusammen. Stünde `mail` hinter der Verdichtung, bekäme
er dort regelmäßig nichts, und die Statusseite meldete trotzdem „in Ordnung",
weil kein Fehler anliegt. Bei einer Warteschlange, in der ein Reset-Link
wartet, ist das der teuerste aller stillen Fehler.

Er ist **nicht** `taeglich`: Ein gescheiterter Lauf zählt trotzdem als Lauf,
und bei `taeglich` sperrte ein einziger Fehlschlag den Versand bis zum
nächsten Kalendertag.

> **`MAIL_MINDEST_S = 1.5` ist die Lehre aus einem stillen Totalausfall.** Die
> erste Fassung verlangte die vollen 5 s Restzeit, bevor sie anfing — am
> Huckepack-Weg stehen 3,0 s zur Verfügung, also war die Bedingung beim ersten
> Durchgang **immer** wahr. Der Job brach ab, bevor er eine einzige Nachricht
> versuchte; gemessen: „erledigt 0" bei drei fälligen Zeilen. Auf einer
> Installation ohne Cron wäre die Warteschlange nie geleert worden, und nichts
> hätte es gemeldet. Jetzt bekommt der Versuch die **Restzeit** als Budget,
> höchstens `MAIL_BUDGET_S`.

**Das Fehlerprotokoll nennt keinen Empfänger** (E-P5a-37). Bis Web 20.7.0
stand dort `SMTP: Versand an <Adresse> fehlgeschlagen` — die einzige Stelle mit
Personenbezug im Fehlerprotokoll, und sie widersprach der Zusage im Kopf von
`smtp.php`, die `betrieb_status.php` wiederholt. Jetzt nennt die Meldung eine
**Kennung** und den **Grund**; die Warteschlange schreibt dieselbe Kennung in
ihre Fehlerspalte. Wer einem Fehlschlag nachgeht, findet über die Kennung
beides zusammen — das Protokoll allein sagt nicht, wer gemeint war. Seit
Web 20.40.0 steht die Meldung im Reiter System (4.99g), unter derselben
Kennung.

**Nachweis:** `tools/proben/mail/` gegen eine eigene SMTPS-Gegenstelle — 41
Prüfungen, 0 Befunde (Stand P5a; die Zahl von heute nennt der Prüfbericht);
`tools/proben/jobs/` Teil 10 — 35 von 35. Seit Web 20.40.0 liest Abschnitt 13
den Reiter System statt einer `error_log`-Datei und hält die Kennung des
Eintrags gegen die Fehlerspalte der Warteschlange.

### Was ein Gerät beim Koppeln über sich meldet — seit Web 12.9.0 gespeichert

Das Gerät sendet den Block `geraet` mit `start` — der Anfrage, mit der es sich
eine Kopplungssitzung holt; bis Web 12.9.4 lag er neben dem eingetippten Code.
Er kommt in **zwei Formen**, weil die Geräte Verschiedenes über sich wissen: Die Garmin-Uhr
(seit 1.9.0) schickt ihre **Teilenummer** samt Displaymaßen, Touch, Firmware,
Plattform- und App-Fassung; die Android-Handy-App (seit 0.2.0) schickt
**Hersteller und Modell** statt der Teilenummer, dazu die API-Stufe. Feldliste
und Begründungen: `docs/JSON-Vertrag.md`, Abschnitt 1a.4.

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
ist **erzeugt**: `tools/erzeugen/geraetemodelle.py`, aus denselben Dateien, mit
denen `tools/uhr-pruefstand/geraeteklassen.py` arbeitet.

**Stand: 325 Teilenummern auf 173 Modelle** (Web 12.9.1) — dieselbe Zahl, die
oben aus der Uhr-Seite stammt, und damit unabhängig bestätigt. **28 davon sind
keine Uhren**: 20 Edge, 8 Outdoor-Handgeräte.

> **Die Gerätedateien liegen nicht im Repositorium** — sie gehören Garmin und
> werden nur vom SDK-Manager ausgeliefert. Ihre Bereitstellung kommt als
> `CIQ_GERAETE_URL` herein und steht **nicht im Repositorium**
> (`tools/uhr-pruefstand/LIESMICH.md`). Sie liegt seit dem 03.09.2026 in den **Umgebungsvariablen der Arbeitsumgebung** und ist in einer eingerichteten Umgebung bereits gesetzt; erfragt werden muss sie nur, wenn `$CIQ_GERAETE_URL` leer ist.
> Ohne sie erzeugt
> `erzeugen.py --leer` eine gültige, leere Tabelle: Die Anwendung läuft
> vollständig, löst aber nichts auf — jede Teilenummer landet unverändert in
> `geraet_teil`, und die Geräteliste zeigt „Uhr · 006-B4261-00" statt
> „Uhr · Venu 3S". Verloren geht dabei nichts; genau dafür steht die Rohangabe
> in einer eigenen Spalte, und der Job `nachaufloesen` trägt später nach —
> von selbst, sobald eine gefüllte Tabelle da ist (seit Web 20.15.0;
> `nachaufloesen.php` bleibt als Vorschau und Weg von Hand).

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
keinen Fehler. Nachweis ohne Datenbank: `php tools/proben/geraete/probe.php`.

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

**Der Name des Geräts folgt der Art.** Beim Anlegen des Geräts — seit Web
13.0.0 im Augenblick des Ja am Gerät — vergibt `pair.php` als Bezeichnung
„Uhr", „Handy" oder „Gerät". Bis Web 12.9.0 stand dort fest „Uhr" — seit der
Handy-App war das schlicht falsch. Wo keine Art gemeldet wird, bleibt es bei
„Uhr": Ein Gerät ohne Block war damals eine Uhr-Fassung vor 1.9.0, und etwas
anderes konnte nicht koppeln. Der Block ist auch heute freiwillig — eine
Kopplung scheitert nie an einer Statistikangabe; wo er fehlt, bleibt es bei
der Vorgabe.

### Geräte je Konto

Höchstens **fünf** (`MAX_GERAETE` in `db.php`), geprüft an **drei** Stellen:
beim Eingeben des Codes im Web, noch einmal beim Ja am Gerät (`pair.php`) —
dazwischen kann von Hand ein Gerät dazukommen — und beim manuellen Anlegen
(`einstellungen.php`). Gezählt werden
aktive **und** deaktivierte — ein deaktiviertes Gerät ist ein weiterhin
vorhandener Zugangsdatensatz, der sich mit einem Klick wieder scharf schalten
lässt. Löschen gibt einen Platz frei, Deaktivieren nicht.

Nicht mitgezählt wird das virtuelle Gerät `manual-<konto>` („Manuelle
Einträge"). Es entsteht von selbst beim Anlegen oder Importieren eines
Einsatzes, ist dauerhaft deaktiviert und taucht schon in der Geräteliste nicht
auf (`GERAETE_ECHT_SQL`). Zählte es mit, nennten Grenze und angezeigte Liste
verschiedene Zahlen.

Ist die Grenze erreicht, nimmt die Geräteseite den Code **gar nicht erst an**
und sagt es, bevor irgendetwas beansprucht ist. Der Code entsteht seit Web
13.0.0 auf dem Gerät, und beim Erzeugen weiß der Server noch nicht, zu welchem
Konto er gehören wird — die Grenze kann erst dort greifen, wo das Konto
feststeht. Deshalb wird sie zweimal geprüft, und beide Male sind nötig: bei
der Eingabe im Web und beim Ja am Gerät. Trifft das Ja auf ein volles Konto,
antwortet `pair.php` mit `409` und `error: device_limit` und **löscht die
Sitzung**; der Weg zurück ist ein Gerät löschen und von vorn beginnen
(E-S5-18). Beanspruchte, unbestätigte Sitzungen zählen nicht mit.

**Hinweis bei neuen Geräten,** zwei Spuren: eine E-Mail an den Kontoinhaber
unmittelbar nach der Kopplung — seit Web 13.0.0 beim **Ja am Gerät**, nicht
beim Klick im Web, denn davor gibt es das Gerät noch nicht (E-S5-20). Sie
erreicht die Person auch dann, wenn sie sich gerade nicht anmeldet — genau der
Fall, in dem jemand sie zur Eingabe eines fremden Codes bewegt hat. Dazu ein
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
einzelnen Endpunkts.

> **Der Satz stimmte bis Web 20.9.0 nur für die Antworten, die durch
> `json_out()` gingen** (Backlog Nr. 203, behoben mit Web 20.9.1). **Sieben**
> Stellen gingen daran vorbei, und zwei davon — die Rohausgaben in
> `api/export_data.php` — setzten den Kopf gar nicht. **Diese Antwort enthält
> GPS-Spurpunkte.** Seither gibt es drei Funktionen und eine Stelle:

| Funktion in `db.php` | tut |
|---|---|
| `json_kopf($code)` | setzt den Satz: `kopfzeilen_json()`, Status, `Content-Type: application/json; charset=utf-8`, `Cache-Control: no-store` |
| `json_roh_out($json, $code)` | ruft `json_kopf()`, gibt **fertigen Text** aus, `exit` |
| `json_out($daten, $code)` | ruft `json_roh_out((string)json_encode($daten), $code)` |

**Warum es `json_roh_out()` überhaupt gibt:** Die Rohausgaben **haben** den
Text schon. `api/export_data.php` baut ihn stückweise (ein Export kann
hunderte Megabyte umfassen), `api/backup_data.php` reicht Chiffretext durch,
`jobs.php` braucht eigene `json_encode`-Schalter. Sie sollen ihn nicht
dekodieren müssen, nur um ihn wieder zu kodieren.

**Warum es `json_kopf()` gibt:** `pair.php` antwortet an zwei Stellen und
**arbeitet dann weiter** — es schließt die Antwort ab
(`antwort_abschliessen()`) und reiht erst danach die Hinweismail ein, weil die
Uhr auf das `ok` wartet. Ein `never` schließt diese Stelle aus.

**Sieben Stellen umgestellt, nicht drei:** `api/export_data.php` (2×),
`api/backup_data.php`, `api/adminbackup_freigabe.php` — dazu `auth_salt.php`,
`jobs.php` und `pair.php`, die denselben Mangel hatten. `auth_salt.php`
liefert das Salt der Schlüsselableitung **je Konto** und ist unangemeldet
erreichbar; `pair.php` nennt die maskierte Adresse des Kontos. Eine
zwischengespeicherte Antwort ist dort nicht unsauber, sondern falsch; beiden
fehlten außerdem `nosniff` und `Referrer-Policy`.

**Eine Ausnahme, benannt:** `wartung_lib.php` setzt seinen Satz weiter selbst.
Die Wartungsseite ist ausdrücklich ohne Datenbank gebaut und darf `db.php`
nicht laden — `no-store` steht dort trotzdem.

> **Hier stand bis Web 20.28.0, `grep -rn "Content-Type: application/json"
> server/` treffe „genau zwei Codezeilen".** Gemessen sind es **sechs**: zwei
> in `wartung_lib.php`, eine in `db.php` — und drei in `api/rueckfrage.php`,
> `api/schluessel_erneuern.php` und `api/schluesselblatt_pruefen.php`, die
> ihre Antworten mit `echo json_encode()` selbst schreiben und deshalb weder
> `nosniff` noch `no-store` setzen. Das ist derselbe Mangel, den Web 20.9.1
> an sieben anderen Stellen behoben hat; diese drei arbeiten über `$_POST`
> und sind damals niemandem als „JSON-Endpunkt" aufgefallen. Backlog
> **Nr. 258**.

### Der Eingang der Endpunkte (ab Web 20.28.0)

Einundzwanzig Dateien unter `server/api/` fingen mit derselben Handarbeit an.
Seit Schritt 15 AP3 gibt es dafür zwei Funktionen in `db.php`, neben
`json_out()`:

| Funktion | tut | antwortet selbst mit |
|---|---|---|
| `api_methode($erlaubt = 'POST')` | prüft `$_SERVER['REQUEST_METHOD']` gegen eine Methode oder eine Liste | **405** `method` |
| `api_rumpf($o = [])` | liest `php://input`, prüft „leer" und „ist ein JSON-Objekt", gibt das Feld zurück | **400** `leer` (mit dem einen `post_max_size`-Hinweis), **400** `format`, **413** `zu_gross` nur wenn `max_bytes` gesetzt ist |

**Die Reihenfolge ist der Grund für zwei Funktionen und nicht eine.** In
allen elf Dateien mit Rumpf steht dazwischen eine dritte Zeile:

```
api_methode();   ->   csrf_check();   ->   $b = api_rumpf();
```

Ein Aufruf, der Methode und Rumpf zusammenfasst, schöbe das Rumpflesen vor
die Token-Prüfung: Ein Aufrufer ohne gültiges Token bekäme `leer` oder
`format` statt `csrf`, und in `api/kdf_upgrade.php` liefe er am
Demo-Ausstieg vorbei, der zwischen csrf und Rumpf steht. **CSRF gehört aus
einem zweiten Grund nicht in den Eingang:** Ein Endpunkt ohne Sitzung (10c
AP6, `api/health.php`) braucht `api_methode()` und sonst nichts.

**`max_bytes` hat keinen Vorgabewert.** Unter `api/` gab es an keiner Stelle
eine Grenze für die Rumpfgröße, und ein Konto-Backup kann zweistellig
megabytegroß sein. `app.max_body_bytes` (512 KB) ist die Grenze des
**Geräte**-Eingangs in `ingest.php`, nicht die der Weboberfläche.

**Inhaltliche Prüfungen bleiben beim Aufrufer**, mit ihren eigenen
Schlüsseln: ob `$b['eintraege']` da ist, ob `$data['format']` den richtigen
Wert hat, ob die Liste zu lang ist. Der Eingang beantwortet nur, ob
überhaupt ein Objekt angekommen ist.

**Eine Ausnahme, namentlich:** `api/csp_bericht.php` liest `php://input`
weiter selbst und antwortet auf alles mit einer stummen **204**. Ein
Berichts-Endpunkt sagt dem meldenden Browser nichts — weder über die
Methode noch über den Rumpf.

**Die Geräte-Endpunkte sind nicht beteiligt.** `ingest.php`, `pair.php`,
`auth_salt.php`, `jobs.php` und `gpx.php` liegen nicht unter `api/` und
behalten ihre Fehlerschlüssel (`payload`, `too_large`) zeichengleich — der
JSON-Vertrag und die Android-Prüffälle hängen daran.

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
Anmeldung erreichbar. Bei `pair.php` gilt das seit Web 13.0.0 für **alle
vier** Anliegen (seit 9.15.0 für beide): Die kopfzeilen-ausgewiesenen Zweige
laufen im unbekannten Fall gegen `GERAET_VERGLEICHSWERT`, wie `ingest.php`,
und `410`/`409` kommen ohne Verzögerung, weil sie die richtigen Zugangsdaten
voraussetzen (E-S5-31) — sonst wäre aus der Antwortdauer ablesbar, welche
Gerätekennungen es gibt, und die Kennung ist die Hälfte dessen, was ein
Upload braucht. Beide müssen für „gibt es" und „gibt es nicht" Antworten
liefern, die sich in **Länge, Zeichenvorrat, Aufbau und Dauer** nicht
unterscheiden; die Kopplungsprobe misst die beiden 401-Zweige nebeneinander.
Beim Salt war es zuletzt die Länge, die alles verriet: Ein echtes Salt hat 32
Hexzeichen, das Pseudo-Salt hatte 64. Wer hier etwas ändert, prüft bitte beide
Zweige nebeneinander.

**Der Aufruf einer Seite darf nichts verändern.** `betrieb_updates.php` führt
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

Nachgemessen wird sie mit `php tools/proben/spur/probe.php`; der Lauf arbeitet in
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
ersten NutzerIn des Tages. Das trug, solange die Arbeit klein war.

Mit S2 bleibt sie das nicht. Schon die damalige Waisenprüfung war ein
Anti-Join über die ganze Spurtabelle und kostete gemessen **4,07 s bei
9,46 Mio. Zeilen** — in genau der Anfrage, die jemand gerade gestellt hatte.
Bei der Zielmenge Z2 (190 Mio. Zeilen) wären es Minuten.

Deshalb ein Rahmen: `server/jobs_lib.php` (Katalog und Ausführung),
`server/jobs.php` (Einstieg), Tabelle `jobs` (Zustand).

#### Die drei Auslöser (E-S2-17)

**Einer genügt.** Eingerichtet werden muss keiner — dann läuft die Arbeit
weiter huckepack mit. **Betrieb → Hintergrundjobs** (`betrieb_jobs.php`) zeigt
alle drei mit fertigem Befehl bzw. fertiger Adresse, je in einem Wertekasten mit
Knopf „kopieren".

| Auslöser | Aufruf | Zeitbudget je Lauf | gedacht für |
|---|---|---|---|
| `cli` | `* * * * * php /pfad/zur/installation/jobs.php` | `JOB_BUDGET_CLI` = 300 s | der **empfohlene** Regelfall |
| `token` | `https://…/jobs.php?token=…` | `JOB_BUDGET_TOKEN` = 20 s | Hoster ohne CLI-Cron, aber mit „Cronjob per URL" |
| `anfrage` | `auth_guard.php` → `run_cleanup_if_due()` | `JOB_BUDGET_ANFRAGE` = 3 s | Rückfall, immer eingeschaltet |

**Der Pfad im Aufruf ist ein Platzhalter.** Auf einer Installation gibt es
keinen Ordner `server/` — die Kette legt dessen **Inhalt** in den Webroot.
Wer den Befehl mit `…/server/jobs.php` abtippt, bekommt „Could not open input
file" (Backlog Nr. 150, so beim Einrichten auf Produktiv passiert). Der
verlässliche Weg ist der Kopier-Knopf auf **Betrieb → Hintergrundjobs**: Der
Wertekasten baut den Befehl über `__DIR__` und kennt den Pfad dieser
Installation.

Die Budgets sind kein Geschmack: 300 s, weil auf der Kommandozeile niemand
wartet und meist keine Laufzeitgrenze gilt; 20 s, weil das unter der
`max_execution_time` liegt, die geteilter Webspace üblicherweise setzt
(dieselbe Überlegung wie bei „Alle sichern"); 3 s, weil eine Seite, die
zwanzig Sekunden braucht, weil sie nebenbei aufräumt, kaputt ist — auch wenn
kein Zeitlimit greift.

**Der Token-Weg nimmt seit P5a zusätzlich einen Parameter `aktion`** — er ist
damit auch der Einstieg der Auslieferungskette (E-P5a-12). Fünf Aktionen, alle
über dasselbe Token, alle unter demselben Ratenschutz:

| `aktion` | tut | Antwort |
|---|---|---|
| *(keine)* | alle fälligen Jobs, ein Häppchen | `{ok, jobs}` |
| `komplett` | **nur** das Komplett-Backup — und legt einen Auftrag an, wenn keiner steht | `{ok, fertig, bericht, komplett}` |
| `wartung_an` / `wartung_aus` | Wartungsmodus, Urheber `kette` | `{ok, wartung}` |
| `zustand` | Auskunft **ohne Nebenwirkung**: Version, Wartung, jüngster Komplett-Stand, Migration ausstehend | `{ok, version, wartung, komplett, migration_ausstehend}` |
| `pause` | Jobs anhalten (`sekunden=N`) oder freigeben (`sekunden=0`); seit Web 20.16.0 | `{ok, sekunden, grenze, bis, meldung}` |

**`pause` ist kein zweiter Mechanismus**, sondern ein vierter Aufrufer von
`jobs_pause()` — neben der Kommandozeile und den beiden Knöpfen unter Betrieb
→ Hintergrundjobs. Es gibt ihn, weil ein Prüfmittel, das gegen eine **ferne**
Installation misst, die Jobs sonst nicht stillstellen kann: `php jobs.php
--pause` braucht eine `config.php` auf demselben Rechner (Backlog Nr. 219).
`bis` ist dabei die Antwort auf die Frage und nicht die Wiederholung des
Wunsches — `jobs_pause()` deckelt auf `JOB_PAUSE_MAX_S`, wer 9999 schickt,
sieht dort 7200.

**Ein fehlendes `sekunden` ist ein Fehler (400), kein Vorgabewert.** `0` hebt
die Pause auf; ein vergessener Parameter gäbe sonst die Jobs frei und meldete
dafür `ok`.

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

**Der Topf `pair` gehört nicht `jobs.php` allein.** Drei Endpunkte zählen
darin: dieser, `pair.php` (401 an den kopfzeilen-ausgewiesenen Anliegen) und
`gpx.php` (die Freigabelinks der Spuren, sieben Zählstellen). Wer die Zahlen in
`RATE_GRENZEN` ändert, ändert alle drei — und eine Sperre, die hier greift,
kann von einem der anderen beiden stammen. Deshalb ruft seit Web 13.1.1 auch
keiner von ihnen mehr `rate_erfolg('pair')`: Ein Erfolg im einen darf die
Fehlversuche der anderen nicht löschen (4.99b, „Ein Topf, drei Verbraucher").

**Der Ratenschutz zählt je IP, und das hat eine Folge, die man kennen sollte:**
Nach zehn Fehlversuchen von derselben Adresse antwortet der Endpunkt zehn
Minuten lang `429` — auch auf den *richtigen* Aufruf. Wer einen
Zeitplan-Eintrag mit falschem Token stehen hat, sperrt damit seinen eigenen
Zeitplan aus; nach dem Berichtigen dauert es zehn Minuten, bis er wieder
greift. Das ist gewollt: Die Alternative wäre ein Endpunkt, an dem sich ein
Token ungebremst durchprobieren lässt. Auf Betrieb → Hintergrundjobs gibt es
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
| `mail` | nein | Nachrichten, deren erster Versuch scheiterte — fünf Versuche über 24 Stunden, danach steht die Nachricht als unzustellbar auf der Statusseite (4.99). Steht **ganz vorn** im Katalog: `jobs_lauf()` arbeitet ihn der Reihe nach ab, und am Huckepack-Weg sind 3 s für alle Jobs zusammen — ein Job dahinter bekäme dort regelmäßig nichts |
| `konto_loeschung` | nein | Konten, deren 30-Tage-Karenz abgelaufen ist, endgültig löschen (P5b/AP5, E-P5b-16) — **höchstens fünf je Lauf**, weil eine Löschung die Spuren von Hand räumt, einen Ordner im Dateisystem löscht und über vierzehn Tabellen kaskadiert. Steht weit vorn: im Regelfall eine Abfrage über einen Index, und wenn er etwas zu tun hat, ist es das, worauf jemand ein Recht hat |
| `aufraeumen` | ja, höchstens 1×/Kalendertag | **siebzehn Schritte** — Kopplungssitzungen, **Sitzungsdateien** (Schritt 16, E-SA-06 — der einzige Schritt, der das Dateisystem anfasst; er räumt `server/.sitzungen/` und nur `sess_*`), Sperrliste gelöschter Kennungen, Ratenschutz-Zähler, Sperrereignisse, **Gerätevermerke** (P5a/AP8), CSP-Berichte, Mail-Warteschlange, **Betriebsprotokoll** (P5b/AP1 — als einziger Schritt mit ZWEI Fristen, siehe 4.99g), Mengen je Konto, Verwaiste Kontomarken (P5b/AP6), Job-Verlauf, Papierkorb, Passwort-Tokens, Erinnerung an die Verwaltung, Speicher messen, Warnschwellen melden. **Maßgeblich ist `job_aufraeumen_schritte()`, nicht diese Zeile** — und seit Web 20.26.0 wird das nachgezählt statt zugesagt (`tools/quelltext/` `jobregister`, Stufe 1). Die Namen hier sind deshalb die Schlüssel aus dem Code, Zeichen für Zeichen |
| `konto_verfall` | nein | Registrierungen, die nicht bestätigt wurden, und Freischaltfristen, die abgelaufen sind (P5b) — **höchstens fünf je Lauf**, aus demselben Grund wie beim Löschjob darüber |
| `verdichtung` | nein | Stufe 1 → 2: abgeschlossene Spuren in den verlustfreien Blob (seit Web 10.2.0) |
| `ausduennen` | nein | Stufe 2 → 3: sechs Monate nach Einsatzende ausdünnen (seit Web 10.2.0) |
| `adminbackup` | nein, nur mit Auftrag | Konto-Backups aus der Sammelaktion „Alle sichern" |
| `protokoll_archiv` | nein, im Regelfall eine Abfrage auf die Marke | Die Einträge eines abgelaufenen Zeitraums (Vorgabe 7 Tage) versiegelt als ZIP nach `sicherungen/protokoll/`, in Häppchen zu 500 Zeilen; Archive nach Ablauf der Aufbewahrung (Vorgabe 365 Tage) löschen (P5c/AP2, 4.99g). Steht **vor** `versand`, damit ein fertiges Archiv im selben Lauf hinausgeht |
| `versand` | nein | Pakete auf die aktiven Backup-Ziele — und seit Web 20.14.0 die **Aufbewahrung dort** (4.97c); seit Web 20.39.0 auch die Archive des Protokolls, **ohne** Aufbewahrungsregel dort |
| `komplett` | nein, nach Plan | Komplett-Backup der Installation (4.97d) |
| `nachaufloesen` | nein, nur nach einer neuen Modelltabelle | Teilenummern bestehender Geräte erneut auflösen, in Blöcken von 200 (P5a/AP11, unten) |
| `waisen` | nein, läuft solange Rückstand da ist | Spurpunkte und Blobs ohne Eigentümer — **bereichsweise** über den Primärschlüssel |

> **Diese Tabelle hat dreimal gehinkt, und beim dritten Mal ist die Ursache
> behoben worden** (Backlog Nr. 208, Schritt 16, Web 20.26.0).
>
> Der Werdegang, weil er die Regel erklärt: In P5a/AP5 nannte sie sechs von
> zwölf Aufräumschritten. In P5a/AP11 fehlten **vier von acht Jobs**
> (`mail`, `adminbackup`, `versand`, `komplett`); die Fußnote an dieser
> Stelle behauptete danach, `mail` sei nachgetragen — **es stand trotzdem
> nicht da**. Gemessen am 20.09.2026 gegen `862ca7f`: **11 Jobs im Code
> gegen 9 hier** (`mail` und `konto_verfall` fehlten), **16 Aufräumschritte
> gegen „dreizehn"**, und die sichtbare Beschreibung im Katalog nannte
> **15 von 16**.
>
> Zweimal sind die Zahlen von Hand berichtigt worden, zweimal wuchs der
> Abstand wieder. Deshalb jetzt zweierlei: Die **sichtbare** Beschreibung
> unter Betrieb → Hintergrundjobs wird aus `array_keys(job_aufraeumen_schritte())`
> **erzeugt** und kann nicht mehr altern. Und diese Tabelle wird
> **nachgezählt** — `tools/quelltext/jobregister.php` hält Jobnamen, Schrittzahl
> und Schrittnamen gegen den Quelltext, mit dem Tokenizer und ohne
> Installation. Maßgeblich ist weiterhin der Code; neu ist, dass ein
> Auseinanderlaufen auffällt, statt bemerkt werden zu müssen.

Jeder Aufräumschritt hat weiterhin seinen eigenen Fehlerblock: Einer, der
scheitert, hält die anderen nicht auf (das war schon seit Web 4.5.1 so und
bleibt). Der Unterschied ist, dass das Ergebnis jetzt in `jobs` landet und
nicht nur in einem Protokoll — bis Web 20.39.0 dem des Webspace, seither dem
Reiter System (4.99g).

**Die Reihenfolge ist Absicht.** `jobs_lauf()` arbeitet den Katalog der Reihe
nach ab und überspringt, was ins Restbudget nicht mehr passt. `waisen` ist ein
Sicherheitsnetz und kein Hauptweg — die eigentliche Arbeit gehört deshalb nach
vorn, sonst bekäme sie am Huckepack-Weg (3 s) nur noch den Rest.

#### Der Nachlöse-Job (ab Web 20.15.0, P5a/AP11, E-P5a-21; Backlog Nr. 80)

**`pair.php` löst die Teilenummer einer Garmin-Uhr im Moment der Kopplung auf
— und nur dann.** Trifft sie dabei auf eine leere oder ältere Modelltabelle,
bleibt `geraet_modell` leer, und `geraet_art` steht auf der **ungeprüften
Selbstauskunft** des Geräts: Die Uhr-App sendet dort fest `"uhr"`, ein
Radcomputer wäre damit dauerhaft als Uhr gezählt.

Nachtragen ließ sich das seit Web 12.9.1 mit
`tools/erzeugen/geraetemodelle-nachaufloesen.php` — **über die Kommandozeile**. Auf
einem Webspace ohne SSH gibt es diesen Weg nicht; dort holten die betroffenen
Geräte ihre Angabe erst bei der nächsten Kopplung nach, also womöglich nie.
Die Zahl, die Backlog Nr. 80 auswerten will, hing damit daran, ob jemand SSH
hat.

| | |
|---|---|
| Bibliothek | `server/geraetemodelle_lib.php` — `gm_nachaufloesen(PDO, int $block, bool $schreiben, int $abId, ?array $tabelle)`. Sie ist der Kern, den sich Skript **und** Job teilen; das Skript behält die Vorschau |
| Auslöser | `sha256(serialize(GERAETE_MODELLE))` gegen den Wert in `app_state` (`geraetemodelle_stand`). **Ein Hash und kein Datum:** Ein Deploy fasst die Änderungszeit jeder Datei an, der Inhalt bleibt derselbe — ein Job, der nach jedem Deploy 300 Zeilen durchgeht, ist ein Job, der nichts tut und dafür Zeit verbraucht |
| Blockgröße | **200** Zeilen, mit Zeitbudget und Fortsetzungsmarke (`ab_id` im Jobzustand). **Eine Transaktion je Block**, nicht eine über alle: Eine über mehrere hielte Sperren über Sekunden und würde beim Zeitablauf zurückgerollt — dann wäre die Arbeit des gerade geschafften Blocks weg |
| Hash schreiben | **erst am Ende.** Bricht der Lauf mitten im Bestand ab, bleibt die Marke im Zustand und der alte Hash stehen. Wäre der Hash schon geschrieben, gälte der halb durchgegangene Bestand als erledigt — und zwar still |
| Drei Regeln, unverändert | nur ändern, was die Tabelle **wirklich** kennt · die **Rohangabe** nie anfassen · **Handy-Zeilen** bleiben unberührt (ihre Rohangabe ist der Klarname, und die Tabelle führt keine Handys — dieselbe Regel wie für jede andere unbekannte Angabe, kein Sonderfall) |
| Anzeige | Betrieb → Status, Karte **Server**, Zeile **Gerätemodelle**: „N Teilenummern · zuletzt nachgelöst … · X nachgezogen, Y unbekannt". **Orange „steht aus"**, wenn die Tabelle sich geändert hat und der Job noch nicht gelaufen ist — ohne diese Zeile wäre das ein Zustand, den niemand sieht |
| Prüfmittel | `tools/proben/geraete/` Teil 2, **gegen die Datenbank**: fünf Zeilen mit je einer Frage, zwei Tabellen in einem Lauf |

**`geraet_modell_aufloesen()` und `gm_nachaufloesen()` nehmen die Tabelle als
Parameter.** Das ist die Naht für die Probe: Sie misst gegen eine eigene,
kleine Tabelle statt gegen den ausgelieferten Bestand (325 Teilenummern, die
sich mit dem nächsten Lauf des Erzeugers ändern können) — und sie braucht
**zwei** Tabellen in einem Lauf, um zu messen, dass eine gewachsene Tabelle
genau die Zeilen ihrer neuen Teilenummer nachzieht. `null` heißt die
ausgelieferte Tabelle; am Regelfall ändert der Parameter nichts.

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

#### Die Jobs anhalten (ab Web 10.2.0, Knopf ab Web 19.3.0)

**Zwei Wege, ein Schalter.** `php jobs.php --pause <Sekunden>` (0 hebt auf) und
seit Web 19.3.0 der Knopf **„Jobs anhalten"** in der Karte „Zustand" auf
Betrieb → Hintergrundjobs, mit einer Dauerwahl aus 15 Min., 30 Min., 1 Std.
und 2 Std. Beide rufen `jobs_pause()` und schreiben denselben Schlüssel
`jobs_pause_bis` nach `app_state` — es gibt keinen zweiten Speicherort und
keine zweite Deckelung. Der Knopf ist dort nötig, wo es keine Kommandozeile
gibt: auf geteiltem Hosting ist das der Regelfall.

Die Pause gilt für **alle drei
Auslöser** — sonst räumte ein Cron weg, was gerade gemessen wird — und läuft
von selbst ab (`JOB_PAUSE_MAX_S` = 2 h); eine Pause ohne Ende wäre eine, die
jemand vergisst. Betrieb → Hintergrundjobs zeigt sie als eigene Plakette an,
damit eine laufende Pause nicht wie ein arbeitender Job aussieht; in der
Meldung daneben steht der Knopf **„Pause aufheben"**.

**Die Oberfläche nannte bis Web 19.3.0 die falsche Einheit.** An zwei Stellen
stand `php jobs.php --pause <Minuten>` — der Code rechnet in Sekunden, und
`jobs.php --hilfe` sagt es auch. Der Nachbarsatz rechnete `JOB_PAUSE_MAX_S /
3600` und kam damit auf „höchstens 2 Stunden", was nur mit Sekunden aufgeht;
wer den empfohlenen Befehl `--pause 60` im Glauben an Minuten abtippte, bekam
eine Minute Ruhe. Berichtigt.

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

`betrieb_jobs.php` zeigt je Job letzten Lauf, Auslöser, Rückstand und letzten
Fehler. `letzter_fehler` steht in der Tabelle und nicht nur im
Protokoll: An das Fehlerprotokoll des Webspace kam auf geteiltem Hosting
nicht jede BetreiberIn heran (seit Web 20.40.0 steht der Fehler auch im
Reiter System), und ein dauerhaft scheiternder Job soll auffallen, ohne dass
jemand sucht. Die
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

Bis Web 10.2.0 entstand **jede** Datei, die auf der Platte einer NutzerIn
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
**kann** er keine geschützte Angabe tragen — der Server kann Diagnose, Alter,
Einsatzort und die übrigen Angaben aus 4.98 nicht lesen. Browserseitig gebaut könnte er es.

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
NutzerIn selbst anklickt. Nach einer Mittagspause hätte sie
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
  Budget von 64 MB (Z3). Dazu ein **Ratenschutz** im Topf `pair` — demselben,
  den `pair.php` und `jobs.php` benutzen (4.99b) —, und zwar nur
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

Bis Web 12.0.0 entstanden die Konto-Backups unter `server/sicherungen/`
und blieben dort. Das ist die Rückfallebene für einen gelöschten Einsatz —
aber nicht für den Fall, für den man Backups macht: dass dieser Server weg
ist. Ab Web 12.1.0 gehen sie auf eine **Gegenstelle**.

#### Der Name

**Backup-Ziel**, nicht Transportziel. `transport_dests` gibt es seit Web 4;
das sind die Zielkliniken einer PatientIn, gepflegt unter Stammdaten. Zwei
Dinge unter einem Wort, zwei Klicks voneinander entfernt — das lässt sich in
einer Fehlermeldung nicht mehr auflösen (Konzept-S2, F-S2-G).

#### Eine Schnittstelle, zwei Adapter

`server/sicherungsziel_lib.php` beschreibt mit `Zielweg`, was ein Ziel können
muss: `verbinden`, `trennen`, `ordner`, `senden`, `holen`, `liste`,
`loeschen`, `fingerabdruck`. Alle Pfade sind **relativ zum Grundpfad des
Ziels** — kein Adapter nimmt einen absoluten Pfad, sonst wäre der Grundpfad
eine Empfehlung und keine Grenze.

| Adapter | Protokoll | Grundlage |
|---|---|---|
| `ZielFtp` | FTPS | PHP-Erweiterung `ftp` (`ftp_ssl_connect`) |
| `ZielSftp` | SFTP | phpseclib 3 (`server/vendor/`, docs/Lizenzen.md 3a) |

Der erste Adapter trug bis Web 20.2.0 **FTP und FTPS**, weil sich genau eine
Zeile unterscheidet; seither gibt es nur noch die verschlüsselte Hälfte
(unten). Das Komplettbackup aus AP8 benutzt dieselbe Schnittstelle und weiss
vom Protokoll nichts; ein dritter Adapter (WebDAV, Backlog) soll sie nicht
anfassen.

#### `ftp` ist abgeschafft (ab Web 20.2.0, S10/AP4, E-S10-14)

`SZ_PROTOKOLLE` und `SZ_PORTS` führen seither **nur noch `sftp` und `ftps`**,
und `sz_protokoll_erlaubt()` ist die eine Frage, die beide Listen stellt.
Geprüft wird **positiv gegen den Katalog**, nicht negativ gegen `ftp` — das
ist der Unterschied, auf den es ankommt: `sz_weg()` hatte genau einen
benannten Zweig (`sftp`), und alles Übrige fiel in `ZielFtp`, wo
`$prot === 'ftps'` über TLS entscheidet. FTPS war damit geschützt, ein
**unbekanntes oder leeres** Protokoll aber fiel still auf Klartext-FTP zurück.

**Seit Web 21.0.0 kennt auch das Schema den Wert nicht mehr** (Migration
`2026_09_25_ftp_entfernen`, P5c/AP8, E-P5c-124). Sie sperrt mit
Vorbedingung, solange ein FTP-Ziel dasteht, und nennt den Weg: unter
Betrieb → Backup-Ziele umstellen oder löschen (seit Web 21.1.0 auch im
Wartungsmodus offen, E-P5c-134; bis dahin nannte der Satz „Verwaltung →
Sicherungsziele", F-P5c-159). **Mit dem Wert ist der
Weg gefallen, der ein solches Ziel von 20.2.0 bis 20.47.0 umschiffte:** die
rote Plakette „wird übergangen" samt „Zuletzt übergangen", das gesperrte
Altziel-Formular ohne Vorauswahl, das Überspringen im Versandschub
(`uebersprungen`) und im Rückstand, die Zahl `uebergangen` im Bericht des
Versandjobs und in der Cron-Zeile, der orange Eimer „umzustellen" auf
Betrieb → Status. Was die Datenbank nicht annimmt, muss die Anwendung nicht
umschiffen. **Es bleibt der allgemeine Riegel in `sz_weg()`**: Ein
Protokoll, das diese Fassung nicht kennt, bekommt keine Verbindung — das
landet jetzt, wie jeder andere Wurf, in `fehler`.

#### Was die beiden taugen

| | verschlüsselt | erkennt den Server wieder |
|---|---|---|
| **SFTP** | ja | **ja** — Fingerabdruck des Hostschlüssels |
| **FTPS** | ja | nein |

Der zweite Fall wird leicht überschätzt: **`ext/ftp` prüft das Zertifikat
nicht.** Nachgemessen in `tools/proben/versand/` gegen eine Gegenstelle mit
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
Zusatzdaten: 'edsk1|<zweck>'
```

Der Zweck in den Zusatzdaten bindet die Chiffre an **die eine Stelle**, für
die sie gedacht ist. Vier Zwecke gibt es:

| Zweck | Wofür | seit |
|---|---|---|
| `sicherungsziel:<id>:<feld>` | Passwort und privater Schlüssel eines Backup-Ziels | Web 12.1.0 |
| `komplett:<datei>` | das Komplett-Backup der Installation | Web 15.3.0 |
| `adminpaket\|<konto>\|<paket>\|<teil>` | jeder Eintrag eines Konto-Backups, Fassung 3 | Web 20.2.0 |
| `adminkonto\|<konto>` | die Begleitdatei `konto.json` neben den Paketen | Web 20.2.0 |

Ein versiegeltes Passwort von Ziel 3 lässt sich damit nicht als Passwort von
Ziel 7 einsetzen, obwohl beide denselben Schlüssel benutzen — und ein Teil aus
Paket A nicht in Paket B unterschieben, obwohl beide demselben Konto gehören.
**Der Preis des Paketnamens:** Wer ein Paket umbenennt, macht es unlesbar
(`docs/Backup-Format.md` 5).

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
| FTP *(seit Web 20.2.0 abgeschafft, die Zahl bleibt als Vergleich)* | 0,13 s | 2,0 MB |
| FTPS | 0,68 s | 2,0 MB |
| SFTP | 3,08 s | 8,0 MB |

Alle 192 angekommenen Dateien byteweise mit dem Original verglichen: **0
Abweichungen**. Zweiter Lauf: 0 Dateien, 0,19 s. Eine am Ziel auf 1 000 Byte
gekürzte Datei wurde beim nächsten Lauf **einzeln** erneut geschickt (1 von
64). Mit einem Budget von 2 s teilte sich derselbe Lauf in zwei Schübe
(34 + 30) und war danach vollständig.

`tools/proben/versand/` deckt Adapter, Fingerabdruck-Riegel, Fehlerfälle und
Versiegelung ab: **135 Erwartungen** (115 bis S10/AP4 — die 116. weist ein
**leeres** Protokoll ab; Teil 12 mit der Aufbewahrungsregel kam in P5a/AP10
dazu und bringt 19), gefahren gegen zwei Sätze Gegenstellen
— pyftpdlib/paramiko und **vsftpd/OpenSSH**. Beide werden gebraucht: vsftpd
kennt kein `MLSD` und fährt damit als einziges den Rückfall auf `NLST` +
`SIZE`; pyftpdlib fährt den Hauptweg. Gegen die echten Server: FTP 0,35 s,
FTPS 1,85 s, SFTP 0,68 s für dieselben 64 Pakete, 64 von 64 byteweise gleich.

**Der Grundpfad bedeutet je Protokoll etwas anderes.** vsftpd sperrt die
NutzerIn in ihr Heimverzeichnis — dort ist `/` die Wurzel. OpenSSH tut das
nicht — dort ist `/` die Wurzel des Dateisystems. Ein Ziel mit „Pfad = /" legt
seine Backups bei SFTP also dorthin, wohin die NutzerIn im Dateisystem
zeigt, und nicht in ein Heimverzeichnis.

Was sie nicht prüfen kann — ein echtes Ziel im Internet —, steht an erster
Stelle ihrer `LIESMICH.md`.

#### Aufbewahrung auf dem Ziel (ab Web 20.14.0, P5a/AP10, E-P5a-03, Nr. 49)

**Der Versand ergänzt nur** — das galt bis Web 20.13.0 ohne Ausnahme und ist
seither die **Vorgabe**, nicht mehr die einzige Möglichkeit. Der Grund für die
Vorgabe bleibt: Der Zweck eines auswärtigen Ziels ist, den Ausfall dieses
Servers zu überleben, **samt eines Fehlers, der hier zu viel löscht**. Ein
Versand, der drüben aufräumt, trägt genau diesen Fehler mit hinüber.

| | |
|---|---|
| Anzeige | `sz_bestand(Zielweg $weg, int $zielId)` — liest je Ordner mit `liste()` und zählt: eigene Dateien und Bytes, ältester und jüngster Stand (aus dem Zeitstempel im Namen), **fremde** Dateien und Bytes. **Auf Knopfdruck**, nicht bei jedem Seitenaufruf: drei Ziele mal dreißig Konten sind neunzig Anfragen. Dieselbe Überlegung wie bei `sz_versand_rueckstand()` |
| Welche Ordner | die Kontokennungen, die **hier** liegen, plus `komplett`, plus — seit Web 20.39.0 — `protokoll`, plus alles, was das Versandprotokoll für dieses Ziel kennt. Der letzte Teil ist der wichtige: Ein gelöschtes Konto hat hier keinen Ordner mehr, drüben aber noch Sicherungen |
| Regel je Ziel | `backup_targets.behalten_konto` und `.behalten_komplett`. **`NULL` = aus**, nicht `0` — `0` hieße „nichts behalten" und räumte das Ziel leer |
| Protokoll | Tabelle `sicherungsziel_dateien` (ziel_id, ordner, datei, bytes, gesendet_am, geloescht_am, grund). Sie ist **Versand- und Löschprotokoll in einem**; zwei Tabellen dafür wären zwei Fassungen derselben Zeile. `ON DELETE CASCADE` am Ziel |
| Die drei Sicherungen | **Herkunft:** Namensmuster (`edbak_paketname_gueltig()`, `komp_name_gueltig()` bzw. `protokoll_archiv_name_gueltig()`) **und** eine Zeile im Versandprotokoll. **Menge:** nie unter N/M, gezählt nur über die eigenen Dateien. **Lauf:** nur nach einem Versand ohne Fehler und ohne Zeitüberschreitung |
| Anzeige der Löschungen | Betrieb → Status → **Sicherheit**, Karte „Löschungen auf Sicherungszielen" (30 Tage, mit Grund). Dazu die Zahl im Versandlauf und in der Jobzeile |
| Statuszeile | Karte **Backups**, Zeile „Aufbewahrung am Ziel": orange, wenn ein Ziel **ohne** Regel seit über 30 Tagen beschickt wird und dort **nie** etwas entfernt wurde (`sz_waechst()`, liest das Protokoll — keine Verbindung) |
| Prüfmittel | `tools/proben/versand/` Teil 12: fünf eigene Sicherungen, fünf fremde Dateien, Regel aus → 0 Löschungen; Regel an (N = 2) → 3 Löschungen, **alle fünf fremden bleiben**, sieben Dateien übrig, Protokollzeilen = Löschungen |

**Warum das Protokoll eine Tabelle ist und nicht `app_state`** (E-P5a-56).
Das Konzept sagt `app_state`. `app_state.v` ist `VARCHAR(190)`, und die Frage
lautet „hat **diese** Installation die Datei X auf Ziel Y geschickt?" — eine
Zeile je Datei und Ziel, bei einem gewachsenen Bestand tausende. In 190
Zeichen passt das nicht einmal für ein Konto.

**Was einmal entfernt wurde, geht nicht wieder hinüber** (E-P5a-57). Das ist
beim Bauen der Probe herausgekommen, nicht beim Nachdenken: Ohne diese Regel
räumt der zweite Lauf drei alte Sicherungen weg, der **dritte** schickt
dieselben drei wieder hinüber (sie liegen hier ja noch), und der vierte räumt
sie erneut weg. Ein Kreislauf, der bei jedem Job Bandbreite kostet und nie zur
Ruhe kommt — still, denn beide Seiten tun genau das, wofür sie gebaut sind.
Gemessen: dritter Lauf **3 gelöscht statt 0**. Der Preis, benannt: Wer die
Zahl später **anhebt**, bekommt die alten Stände nicht zurück.

**Die Archive des Protokolls sind die dritte Dateiart** (ab Web 20.39.0,
P5c/AP2, E-P5c-39). `sz_versand_schub()` hängt den Ordner `protokoll` hinten
an — nach Konten und Komplett-Ständen, weil die Archive klein sind und ein
Kontopaket, das nicht hinausgeht, schwerer wiegt —, und nur, wenn die
Einstellung „Archiv auf das Sicherungsziel" an ist (Vorgabe an).
**Auf dem Ziel gibt es für sie keine Aufbewahrungsregel:** rund 52 kleine
Dateien im Jahr. `sz_aufraeumen()` überspringt den Ordner
ausdrücklich — ohne die Zeile fiele er unter die Zahl **je Konto**, und die
Regel löschte drüben alle Archive bis auf die letzten zwei. Der Rückstand
(`sz_versand_rueckstand()`) zählt die Archive mit, wenn sie mitgehen.

**Ein Altbestand kommt trotzdem ins Protokoll.** Der Versand überspringt eine
Datei, die drüben schon liegt (gleicher Name, gleiche Größe) — und schreibt
seit AP10 trotzdem die Protokollzeile. Ohne das finge das Protokoll erst mit
dem nächsten **neuen** Paket an, und alles, was heute schon dort liegt, gälte
für immer als fremd. Was damit **nicht** erfasst wird: Sicherungen, die drüben
liegen und hier schon weggeräumt sind. Die bleiben unbekannt und werden nie
angefasst — die sichere Richtung.

---

### 4.97d Komplett-Backup der Installation (ab Web 12.2.0, S2/AP8, E-S2-19 bis E-S2-21)

Das Konto-Backup (Abschnitt „Konto-Backups") sichert ein **Konto**.
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
vervielfachen. Dasselbe gilt für die Archive des Protokolls
(`sicherungen/protokoll/`, 4.99g).

**Zwei Tabellen mit Schema, aber ohne Zeilen** (ab Web 20.39.0, P5c/AP2,
E-P5c-57, F-P5c-20): `sicherheit_ereignisse` und `rate_limits`
(`KOMP_OHNE_ZEILEN`). Beide führen IP-Adressen — die erste auch E-Mail-Adressen —
und verfallen bewusst nach 30 Tagen bzw. von selbst (E-P5a-09). Ein
Komplett-Stand liegt länger und geht außer Haus; mit Zeilen hielte er, was
die Anwendung gerade nicht halten will. **Ganz weglassen geht nicht:** Nach
einem Wiederanlauf aus einem Dump ohne die Tabellen scheitert
`ratelimit_lib.php` bei der ersten Anmeldung, weil es in eine Tabelle
schreibt, die es nicht gibt. Mit Schema und ohne Zeilen beginnt die neue
Installation mit leeren Zählern — der Zustand, den 30 Tage später ohnehin
jede hat. Der Dumpkopf sagt es in einer Zeile „OHNE ZEILEN: …" neben „NICHT
ENTHALTEN: config.php", und an der Stelle der Tabelle steht ein Kommentar
mit dem Grund. **Der Preis:** Wer aus einem Komplett-Stand wiederherstellt,
hat die Sperren und Sperrereignisse der letzten 30 Tage nicht mehr; eine
laufende Sperre ist aufgehoben.

**Ein Komplett-Stand schreibt einen Eintrag im Reiter Sicherung** des
Protokolls (`komplett_erzeugt`, mit Größe, Zeilen, Tabellen und den
verdrängten Ständen in `daten`), ebenso Herunterladen, Einspielen und
Löschen (4.99g). Es gibt keine Tabelle, die festhält, wann ein Stand
entstand — der Dateiname sagt es, bis die Aufbewahrung ihn verdrängt.

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

**Neben dem Index merkt sich der Zustand die gültige Länge der Datei**
(`siegel_bytes`) — die Blöcke sind verschieden lang, und das nächste
Häppchen schneidet zuerst darauf zurück, was ein abgebrochenes Häppchen
hinterlassen hat. Diese Länge wird **mitgezählt**, nach jedem vollständig
geschriebenen Block, und nicht mit `ftell()` erfragt: Auf einer Datei im
Anhängemodus zählt `ftell()` ab null, also nur, was die laufende Anfrage
geschrieben hat. Bis Web 21.1.1 stand dort genau das, und eine
Versiegelung über zwei Häppchen ergab eine Datei, die sich nie mehr öffnen
liess (Nr. 328). Ist die Datei kürzer als die gemerkte Länge oder fort,
beginnt die Versiegelung von vorn. Die Komplettprobe fährt beides fest
über mehrere Häppchen, statt es der Laufzeit zu überlassen.

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
`KDF_ITER_ZIEL` = 600 000 Runden, dieselbe Zahl wie im Browser). Was gilt,
steht im Kopf; raten muss das niemand — und deshalb bleibt eine ältere Datei
mit 320 000 im Kopf auch nach der Anhebung lesbar.

#### Zwei Wege heraus

- **Herunterladen** gibt den Dump **unverschlüsselt** als `.sql.gz` — die
  Fassung für `mysql` und phpMyAdmin (E-S2-20). Sie geht an die
  AdministratorIn, die sich eben angemeldet hat und ohnehin jede Zeile dieser
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
sich, sobald es eine `config.php` gibt) und dem Migrationslauf (verlangt eine
Anmeldung, die es ohne Konten nicht geben kann). Drei Schranken:

1. **Die Datenbank muss leer sein** — und zwar fürs *Anfangen*. Ab dem
   zweiten Durchgang ist sie es nicht mehr, weil der erste sie füllt; wer
   einen Arbeitsstand hat, hat ihn auf einer leeren Datenbank begonnen.
2. **Ein Nachweis** wie beim Einrichter (M1-11): eine Datei mit zufälligem
   Namen im Anwendungsverzeichnis, deren Kennung einzutragen ist.
3. **Die Datei kommt aus `sicherungen/eingang/`**, nicht aus einem Formular.
   Es gibt hier bewusst kein Hochladen.

**Sie gibt seit Web 15.6.0 unangemeldet keine Auskunft mehr** (Backlog
Nr. 131, K-11). Diese Seite **muss** ohne Anmeldung erreichbar sein — sie
arbeitet auf einer Installation, in der es noch kein Konto gibt. Zwei Stellen
nutzten das aus, ohne es zu wollen:

- Der **Datenbank-Fehlertext** stand wörtlich auf der Seite. Gemessen am Stand
  davor: `SQLSTATE[HY000] [1044] Access denied for user 'nadoku'@'localhost'
  to database 'gibtesnicht'` — Datenbanknutzer und -name für jeden Besucher.
  Jetzt steht dort eine **Fehlerkennung** (`fehler_kennung()`), unter der der
  volle Text im Fehlerprotokoll des Webspace liegt; die Seite sagt das auch.
  Dort und nicht im Reiter System, auch seit Web 20.40.0: Die Datenbank, in
  der das Protokoll liegt, ist ja die, die nicht antwortet (Rückfall, 4.99g).
- Die Karte „Diese Installation ist in Betrieb" nannte die **Kontenzahl**,
  fett. Gemessen: `2`. Für ihre Aussage — hier passiert nichts mehr — braucht
  sie die Zahl nicht; sie ist jetzt fort.

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

**Migrationen laufen dort nicht mit.** Der Migrationslauf ist seit M6-01
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

`tools/proben/komplett/` fährt den ganzen Zyklus: **72 Erwartungen mit allen
Schaltern** (`--pruefdb` und `--ziel`; ohne sie sind es 64, weil die Teile 7
und 10 dann mit `[ -- ]` ausfallen), einschliesslich Versand auf eine echte
FTPS-Gegenstelle, „halbe Datei liegt dort", abgeschnitten an einer
Blockgrenze, veränderter Dateikopf, beide Wiederanlauf-Zweige und seit S10
Teil 11: **Server-Anteil und Serverschlüssel stehen 0× im Dump, die Kennung
dagegen fährt mit**. Was sie nicht prüfen kann — die Oberfläche, eine volle
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
`manual-<userId>`, `origin = 'manual'`, `uhr_gesperrt = 1`, `client_ref` mit Präfix
`cut-`, wörtlich wie in `einsatz_form.php`. Daran hängt, ob er durch
Backup, Export und Papierkorb kommt (R24), und ob `ingest.php` seine Phasen
später noch anfasst. Alles läuft in **einer** Transaktion; `spur_teilen()`
schließt sich ihr an, statt eine eigene mitzubringen.

**Was der Endpunkt nicht tut:** Einsatzfelder füllen. Einsatzort, Alter,
Diagnose und die übrigen Angaben aus 4.98 sind Ende-zu-Ende-verschlüsselt und entstehen im Browser; ein
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

`tools/proben/spur/probe.php`, **Teil 6** — auf einer eigens angelegten Kulisse
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
`uhr_gesperrt = 1`. Die Spur wird **gleich als Blob** abgelegt (Stufe 2,
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
| **Nullbyte in der Datei** | 422 („kein Text — deutet auf UTF-16 hin") — siehe unten |
| **nicht UTF-8** | 422 („GPX schreibt UTF-8 vor") — siehe unten |
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

> **Erst die Kodierung, dann die Regex** (ab Web 15.6.0, Backlog Nr. 130,
> K-10). Die DOCTYPE-Sperre sucht die **Bytefolge** `<!DOCTYPE`. In einem
> UTF-16-Dokument steht dort `<\0!\0D\0O\0…`; die Regex fand nichts, libxml
> erkannte die Bytefolgemarke und las die Datei samt Dokumenttyp-Deklaration
> und interner Entität. Gemessen am Stand vor der Behebung: **ging durch, zwei
> Punkte**. Deshalb stehen jetzt zwei Prüfungen davor — kein Nullbyte, gültiges
> UTF-8.
>
> **Der Weg dorthin ist nicht theoretisch:** Der Endpunkt nimmt den
> Dateiinhalt als Zeichenkette im JSON-Körper, und JSON trägt über
> `\u0000`-Folgen jedes Byte unter 0x80. Ein angemeldeter Aufrufer baut ein
> UTF-16-Dokument damit von Hand; eine Dateiauswahl im Browser braucht es
> nicht.
>
> **Was das kostet — und was über den Dateidialog davon ankommt:** Auf dem
> JSON-Direktweg wird eine GPX-Datei in Latin-1 mit Umlauten abgewiesen, und
> die Meldung sagt, was zu tun ist. Über den Dateidialog kommen ihre Bytes
> nie an: Der Browser liest sie mit `readAsText()`, dekodiert nach UTF-8 und
> ersetzt ungültige Bytes durch U+FFFD — am Server ist das gültiges UTF-8
> (Gegenprüfung vom 07.09.2026, Fund 9; kein Datenfehler, der Name wird nicht
> gespeichert). Ihre **Kodierungsdeklaration** kommt aber unverändert an,
> und deshalb greift die Deklarationsprüfung des nächsten Absatzes auf
> beiden Wegen — die erste Fassung dieser Prüfung wies die Latin-1-Datei aus
> dem Dateidialog ab, die bis dahin importierte (zweite Gegenprüfung).
>
> **Und die Kodierungsdeklaration** (Nachbesserung 07.09.2026, Fund 7): UTF-7
> ist reines ASCII — gültiges UTF-8, kein Nullbyte, `<!DOCTYPE` steht darin
> als `+ADwAIQ-DOCTYPE` — und libxml liest es trotzdem als DOCTYPE, weil
> `encoding="UTF-7"` in der XML-Deklaration steht. Gemessen: DOCTYPE und
> interne Entität kamen durch, zwei Punkte, Name „LACHER". Die Deklaration
> darf deshalb nur eine Kodierung nennen, in der jedes ASCII-Zeichen sein
> eigenes Byte ist — UTF-8, ASCII und die Ein-Byte-Familien ISO-8859,
> Windows-125x, Latin, KOI8, Mac Roman; UTF-7, UTF-16/32 und EBCDIC werden
> abgewiesen. Nennt sie etwas anderes als UTF-8, wird die Deklaration auf
> UTF-8 umgeschrieben, denn die Bytes **sind** UTF-8 (geprüft) und libxml
> würde sie sonst nach der Deklaration lesen; von 935 Kodierungen aus `iconv -l`
> waren genau UTF-7 und UTF7 durchgekommen, die Liste schließt alle.
> `tools/proben/gpx/` Teil 8 hält neun Umgehungsversuche dagegen — **9 Proben,
> 0 durch** (am Stand davor: 9 Proben, 1 durch) —, und drei saubere Dateien
> gehen weiterhin durch (UTF-8, utf-8, ohne Deklaration).

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
| `.github/workflows/deploy.yml` (bis Web 20.3.0; dann `auslieferung.yml`, beide FTPS-Schritte; seit AP5 `ausliefern-lauf.yml`, einer) | `apk/**` und `apk/` | **Der nächste Push löschte die Dateien.** Die Action synchronisiert `server/` und entfernt, was nicht ausgenommen ist |

Der zweite ist der, den man vergisst. Dasselbe Muster wie `config.php` und
`sicherungen/`, inklusive der doppelten Schreibweise: Die Action prüft
Datei- und Verzeichnismuster getrennt.

Hochgeladen wird per FTPS durch die BetreiberIn.

#### Der Ordner selbst ist seit Web 15.6.0 gesperrt

`apk.php` verlangt eine Anmeldung — der **Ordner** tat das nicht, und die
Dateinamen sind vorhersagbar (`nadoku-0.13.0.apk`). Bis dahin stand hier „nur
angemeldet"; das galt für die Seite, nicht für das Verzeichnis (Backlog
Nr. 129, K-9). Dasselbe für `server/demo/`, wo `fixture.json.gz` das
Schlüsselmaterial des Demo-Kontos trägt — harmlos, weil sein Passwort im
Handbuch steht, aber unnötig.

Zwei Zeilen in `server/.htaccess`, hinter dem HTTPS-Zwang:

```
RewriteRule ^(apk|demo)(/|$) - [F,L]
```

mod_rewrite läuft dort schon, und beide Ordner werden ausschließlich vom
PHP-Code gelesen (`readfile()` in `apk.php`, `file_get_contents()` in
`demo_lib.php`) — die Sperre kostet die Anwendung nichts. Gemessen unter einem
Apache mit dieser `.htaccess`: `apk/`, `apk/<datei>.apk`, `demo/` und
`demo/fixture.json.gz` je **403**, `login.php` und `assets/style.css`
unverändert **200**.

**Keine Laufzeitsperre wie bei `sicherungen/`.** Der Ordner entsteht durch
FTPS-Upload, nicht durch Code; es gibt keine Stelle, an der eine `.htaccess`
angelegt würde, ohne dafür eine zu erfinden. Wer die Anwendung auf einen
Webserver ohne `.htaccess`-Auswertung stellt (nginx), muss die Sperre dort
selbst setzen — das gilt für die Regeln darüber genauso.

#### Punktdateien — und die eine Ausnahme (seit Web 20.15.1, Nr. 213)

Eine Zeile weiter unten, aus demselben Grund hinter dem HTTPS-Zwang:

```
RewriteRule "(^|/)\.(?!well-known/)" - [F,L]
```

**Der Anlass** war die Zustandsdatei der Auslieferungskette.
`SamKirkland/FTP-Deploy-Action` legt `.ftp-deploy-sync-state.json` in das
Zielverzeichnis — also in den Webroot. Inhalt: je ausgelieferter Datei Pfad,
Größe und Hash, dazu der Zeitpunkt der letzten Auslieferung. Kein Geheimnis
(die Ausnahmeliste der Kette hält `config.php` und Konsorten draußen, sie
stehen daher auch nicht in der Datei), aber die vollständige Struktur und der
Versionsstand jeder einzelnen Datei.

Die Regeln darüber nennen **sieben Dateien und zwei Muster beim Namen**. Eine
Punktdatei war nicht darunter, weil niemand mit ihr gerechnet hatte — deshalb
sperrt diese Zeile die Gattung und nicht den Namen. Sie fängt damit auch
`.env`, `.git/` und `.DS_Store`.

**Die Ausnahme ist der gefährliche Teil.** `.well-known/` trägt die
ACME-Herausforderung der Zertifikatserneuerung. Eine pauschale Sperre nimmt
der Anlage spätestens nach 90 Tagen das Zertifikat, und zwar lautlos — niemand
ruft diesen Pfad von Hand auf. Der negative Vorgriff `(?!well-known/)` lässt
genau ihn durch. Ein bloßes `/.well-known` **ohne** Schrägstrich fällt unter
die Sperre; das ist gewollt, ACME fragt immer
`/.well-known/acme-challenge/<Token>`.

**Zwei Schranken, nicht eine.** `state-name` in `ausliefern-lauf.yml` (bis
Kette II/AP5: `auslieferung.yml`, zweimal) legt die Datei zusätzlich eine
Ebene über den Webroot — zwei Namen, weil sich Staging und Produktion einen
FTP-Zugang teilen könnten und zwei gleichnamige Zustandsdateien einander
überschrieben.

**Seit AP6 kommt der Pfad ausschließlich aus der Variablen
`FTP_STATE_PFAD`** (E-KH-07); der eingebaute Vorgabewert ist weg, und fehlt
die Variable, bricht der Lauf im ersten Schritt ab. Die heute eingetragenen
Werte sind `../.deploy-state-staging.json` bzw.
`../.deploy-state-produktion.json` — **kein Vorschlag, sondern der
Ist-Zustand:** Solange der Vorgabewert galt, hat die Kette genau diese
Zeichenketten benutzt, und dort liegen die Dateien. Ein anderer Wert lässt
die Aktion ihre Zustandsdatei nicht finden; sie hält den Server für leer und
überträgt alles neu.

Erlaubt der Käfig des FTP-Zugangs kein `../`, zeigt man mit derselben
Variablen wieder nach innen — die `.htaccess` fängt die Datei dort ab
(Prüfzeile `/.deploy-state-staging.json` → 403 in der Tabelle unter 6.3).
Der Rückbau ist deshalb eine Eintragung und kein Notfall.

**Warum das messbar ist und ein 404 nichts beweist.** `RewriteRule [F]`
antwortet **403, ob die Datei da ist oder nicht** — mod_rewrite läuft vor der
Dateisuche. Stufe 2 der Kette nutzt genau das: vier Punktpfade müssen **403**
geben, `.well-known/acme-challenge/` muss **404 und ausdrücklich nicht 403**
geben. Der Befund, der zu dieser Zeile geführt hat, wurde zuerst falsch
entlastet: Die Abfrage lief gegen ein noch leeres Staging, gab 404, und das
sah aus wie eine Sperre. Ein Prüfmittel, das „gesperrt" nicht von „nicht
vorhanden" unterscheidet, misst nichts.

**Was die Regel nicht tut:** eine bereits abgelegte Datei entfernen. Auf einer
Anlage, auf die schon ausgeliefert wurde, liegt sie weiter im Webroot —
gesperrt, aber vorhanden. Sie wird einmal von Hand per FTP gelöscht.

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
| `start.addr`, `start.lat`, `start.lon` | **Manueller Abfahrtort** (seit Web 6.2.0) — nur bei der Regel „Manueller Ort"; die Regel selbst (`missions.start_src`) ist Klartext. Bis zum 25.09.2026 fehlte die Zeile hier und in `CLAUDE.md` 4, obwohl 4.98a ihn als Blob-Schlüssel führt (Konzept BV, F-BV-13) |
| `notes` | **Notizen des Einsatzes** (seit Web 19.0.0, S9/AP7). Bis dahin die Klartextspalte `missions.notes`; sie bleibt `NULL`-fähig stehen, bis P8 sie entfernt (R60), und trägt nur noch Altbestand, den die Anhebung nicht erreicht hat |

Fehlende Schlüssel bedeuten „keine Angabe"; ein leerer Block wird als
`__CLEAR__` übertragen und löscht den vorhandenen.

> **`notes` ist das erste Feld, das über den Feldkatalog in den Block kommt.**
> Alle Schlüssel darüber entstehen aus handgeschriebenem Markup in
> `einsatz_form.php` mit festen Kennungen. `notes` trägt dagegen
> `'store' => 'pat'` in `mission_fields.php`; `mf_ist_spalte()` nimmt es damit
> von selbst aus jedem `SELECT`, `INSERT` und `UPDATE` auf `missions`, und
> `mf_pat_felder()` ist die eine Liste, aus der Formular, Anzeige und Suche
> schöpfen. Ein weiteres solches Feld (S11: Zielklinik) braucht nur den
> Katalogeintrag.
>
> **Der Altbestand zieht beim Entsperren um** — `api/pat_anheben.php`, siehe
> Abschnitt 4.98d. Ein Konto, das sich nie entsperrt, behält seinen Klartext
> in der Spalte; das ist derselbe Zustand wie vor Web 19, nicht schlechter.

> **`site_desc` ist ein aktives Feld, kein Altbestand.** Es sieht wie ein Rest
> der früheren Klartextspalte aus, ist aber Teil des verschlüsselten Blocks und
> wird an acht Stellen gelesen und geschrieben. Es zu entfernen zerstörte
> stillschweigend vorhandene Patientendaten.

**Im Klartext in der Datenbank** stehen dagegen: die **Tagesnotizen**
(`days.notes`, Betriebsnotizen des Diensttags — nicht die des Einsatzes),
Zeiten und Phasen — samt
**Koordinate jeder Phase**, und Phase 4 und 5 sind der Einsatzort —, Track,
Distanz und Steigung, `site_ele_m`, Transportziel mit `dest_lat`/`dest_lon`,
Schockraum, Reanimationsereignisse, Besatzung, Einsatzmittel, Diensttag- und
Standortdaten. Das ist eine bewusste Entscheidung — diese Angaben sind für
Auswertung, Sortierung und Statistik nötig, die der Server leisten muss. Sie
sind für sich genommen nicht personenbeziehbar; **in Verbindung mit Ort und
Zeitpunkt eines Einsatzes können sie es aber werden.** Wer eine Installation
betreibt, sollte das wissen und den Datenbankzugang entsprechend behandeln.

> **Dieser Abschnitt ist seit Web 15.6.0 die Zusage, nicht mehr nur eine
> Einräumung** (Backlog Nr. 138, Weg C aus `docs/konzepte/Konzept-V1-Ortsdaten.md`,
> K-1). Der Satz, um den es geht, ist der unbequeme: **Aus Spur und
> Phasenkoordinaten lässt sich der Einsatzort rekonstruieren** — die
> Verschlüsselung der Adresse verbirgt ihn nicht. Ein Datenbankabzug ergibt Ort,
> Zeit, Klinik und Behandlung; ohne Name und Diagnose, aber mit Zusatzwissen
> re-identifizierend.
>
> Deshalb sagen `CLAUDE.md` 4, `README.md`, `Handbuch.md` 5 und der Textbaustein
> für die Datenschutzerklärung (Handbuch 11.5) jetzt **dasselbe** und zählen
> beide Seiten auf. *(Stand 25.09.2026: für die ersten drei nachgemessen und
> berichtigt — Notizen des Einsatzes, Abfahrtort, Höhe des Einsatzorts;
> 11.5 zieht P5c/AP9 nach, Backlog Nr. 194.)* Vorher versprachen sie „Diagnose, Alter und Einsatzort sind
> Ende-zu-Ende-verschlüsselt" — richtig für das Feld, irreführend für die Sache.
>
> **Das macht nichts sicherer.** Es macht das Projekt ehrlich, und es ist die
> Voraussetzung dafür, dass die Frage nach **Weg B** (Schlüssel auf die Uhr,
> Rahmenplan S11) nicht als Widerspruch im Raum steht, sondern als offener
> Punkt: Backlog Nr. 43.

Die Zuordnung Datensatz ↔ Person entsteht ausschließlich über den
verschlüsselten Block.

**Unter demselben Inhaltsschlüssel liegt seit Web 20.44.0 der private Teil
des Rückweg-Paars** (`users.rw_privat`, `edk1:`, Konzept RW, 4.99q) — keine
Patientenangabe, sondern Schlüsselmaterial, aber mit derselben Zusage: Der
Server kennt den Schlüssel nicht und kann ihn nicht öffnen. Der öffentliche
Teil (`rw_oeffentlich`) steht im Klartext, weil er öffentlich ist; aus ihm
folgt nichts über den Block. Wie `pat_wrap_rc` hängt `rw_privat` **nicht** am
Server-Anteil (`RW_PRIVAT_RE` weist `edka1:` ab), weil er mit dem
Wiederherstellungsschlüssel allein aufgehen muss.

#### Klartext-Reste außerhalb der Datenbank (Backlog Nr. 133, K-13)

Drei Stellen, an denen geschützte oder halbgeschützte Angaben **vorübergehend
im Klartext** liegen. Sie sind hier benannt, weil zwei davon bleiben — eine
Aufzählung ist die einzige Form von Schutz, die man ihnen geben kann.

| Was | Wo | seit Web 15.6.0 |
|---|---|---|
| `dump.sql.gz` — eine **unverschlüsselte Abschrift jeder Tabelle** während des Komplettbackup-Baus | `sicherungen/komplett/.bau-<8 Hex>/` | **wird geräumt**: bei einem Fehlschlag sofort (`komp_schub()` fängt, räumt, setzt den Zustand auf `abgebrochen`), und bei einem Absturz ohne `catch` spätestens im nächsten Aufräumlauf — auch dem, bei dem nichts fällig ist |
| **Reset-Token** bis zur Einlösung | PHP-Sitzungsdatei und Zugriffslog des ersten GET | bleibt (in M1-06 anerkannt): Der Token steht eine Stunde und wird beim ersten Gebrauch entwertet; ihn aus dem Zugriffslog zu halten hieße, den Link nicht mehr per Adresszeile anzunehmen. **Seit Web 20.26.0 liegt die Sitzungsdatei wenigstens nicht mehr irgendwo:** Sie steht in `server/.sitzungen/` mit `0700` statt in dem Verzeichnis, auf das der Hoster zeigt (Schritt 16, 5b.2 Punkt 11) — das verkleinert genau diesen Rest, hebt ihn aber nicht auf |
| **Setz-Link**, wenn die Mail nicht wegging | auf der Kontoseite der Verwaltung | bleibt (`admin_user.php`): Ein gültiger Token in der Datenbank, von dem niemand weiß, ist die schlechtere Lage |

**Was das Räumen kostet:** „Fortsetzen" nimmt einen **gescheiterten** Lauf
nicht mehr auf — der Dump ist weg, der nächste fängt von vorn an. Das ist
Rechenzeit, keine Daten; die Vorlage ist die Datenbank selbst. Ein Häppchen,
das nur seine Zeit aufgebraucht hat, ist **kein** Fehlschlag: Es wirft nicht,
und der Lauf geht unverändert weiter. Gemessen: ein Bauordner mit Klartext, ein
Aufräumlauf ohne Fälligkeit → **1 auf 0**; ein Lauf, der wirft → **1 auf 0**,
Zustand `abgebrochen`, dazu eine Zeile im Protokoll (Reiter System).

Bis Web 15.5.2 wurde der Bauordner erst geräumt, wenn das **nächste Backup
fällig** war — bei einem wöchentlichen Plan also bis zu sieben Tage später.

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
| Markup | `ui_ortsfeld()` in `ui.php` | erzeugt `<p>addr`, `<p>lat`, `<p>lon`, `<p>suggest`, `<p>state`, `<p>chips` |
| Verhalten | `assets/ortsfeld.js` | `EdOrtsfeld.init({praefix, …})` |

Eine Verwendung ist damit ein PHP-Aufruf und ein `init()`. Die fünf:

| Verwendung | Präfix | Besonderheit |
|---|---|---|
| Einsatzort | `loc` | Textfeld = Suchfeld (die Adresse **ist** die Bezeichnung) |
| Manueller Abfahrtort | `start` | wie Einsatzort, eigener Blob-Schlüssel `start` |
| Zielklinik am Einsatz | `f_transport_dest_` | getrennte Suche, Stammdaten **mit Koordinaten** als eigene Gruppe der Vorschlagsliste |
| Standort im Konto | `sdbase` | getrennte Suche, nur Zubehör (`feld => false`) |
| Zielklinik im Konto | `sdtd<id>` | dito, Präfix trägt die Standortkennung — der Dialog steht einmal je Standort auf der Seite |

**Zwei Bedienformen, ein Code.** Bei `getrennteSuche: false` sucht das
Textfeld beim Tippen; ein Adresstreffer wird zur Bezeichnung. Bei `true`
läuft die Suche **nur auf den Lupen-Knopf** (seit Web 9.4.0 — er ersetzt das
frühere zweite Suchfeld „Lokalisation …"), und der Treffer setzt **nur** die
Koordinaten — „Standort Talwang" ist keine Adresse, und eine Suche, die
den Namen überschriebe, nähme ihn weg. Alles übrige ist in beiden Formen
dasselbe: Chip statt Zahlen im Textfeld, lokale Formaterkennung vor jeder
Netzanfrage, Bestätigung statt sofortiger Übernahme, ruhende Suche bei
gesetzten Koordinaten, und die Prüfung „Koordinaten ohne Bezeichnung" beim
Absenden.

**Die Trefferliste ist seit Web 15.7.0 ein eigener Baustein**
(`assets/vorschlagsliste.js`, `EdVorschlaege`, S9/AP1, E-S9-07). Sie ersetzt
drei Fassungen und eine vierte, die der Browser beisteuerte: die Photon-Liste
des Ortsfelds, die Liste der weiteren Rettungsmittel und jede native
`<datalist>` (Backlog Nr. 68, 102, 106). Das Ortsfeld sagt ihr, **was**
darin steht — erkannte Koordinate, Stammdaten, Adressen —, und **ob** eine
Gruppenzeile erscheint; **wie** es dasteht, entscheidet der Baustein. Er
übernimmt auf `mousedown` mit `preventDefault()`, kennt Pfeiltasten, Enter
und Escape und braucht `EdHtml.escape` (`assets/html.js`) sowie `edSymbol`
(`assets/symbol.js`). Gestaltung und Zustände: `docs/Design.md` 9.28.

Zwei Zahlen des Bausteins stehen als Konstanten in `ortsfeld.js`:
**höchstens zwei** Stammdatentreffer über den Adressen (F10) und die
Photon-Grenze **sechs** in der Abfrageadresse. Stammdaten erscheinen ab dem
ersten Zeichen bei Teilübereinstimmung und **ohne** die 400-ms-Entprellung —
sie liegen im Browser; die Adressabfrage bleibt bei ihren drei Grenzen.

**Der Adressdienst hat seit Web 15.8.0 genau einen Zugang**
(`assets/geocoder.js`, `EdGeocoder`, S9/AP2, E-S9-05). Vorher stand die
Anschrift zweimal fest im ausgelieferten Code — in `ortsfeld.js` für die
Vorwärtssuche, in `ortswahl.js` für die Umkehrsuche. Das Modul hat vier
Mitglieder: `an()` (darf gefragt werden?), `dienst()`/`host()` (wen?),
`suche(q, {sofort})` und `umkehr(lat, lon)`. Bei ihm liegen auch die drei
Grenzen der Abfrage — **400 ms** Entprellung, **drei** Zeichen Mindestlänge,
**sechs** Treffer — und der `AbortController`, der eine überholte Anfrage
abbricht; `suche()` liefert dann `null` statt eines veralteten Ergebnisses.
**Es gibt keine Rückfalladresse:** Fehlt der Bootstrap, ist `an()` falsch und
es geht nichts hinaus. `grep -rn "komoot" server/assets/` = 0 ist damit eine
Eigenschaft und keine Momentaufnahme.

Die Einstellungen dazu stehen in `server/geocoder_lib.php`:
`geocoder_installation_an()` (`app_state`-Schlüssel `adresssuche`),
`geocoder_konto_an($userId)` (Spalte `users.adresssuche`, Migration
`2026_09_07_adresssuche_konto`), `geocoder_dienst()`/`geocoder_host()`
(`app_state`-Schlüssel `geocoder_url`, Vorgabe `GEOCODER_VORGABE`) und
`geocoder_an()`, das **beide** Schalter verundet — die Frage, die ein
Aufrufer stellen sollte. Geschrieben wird über `geocoder_installation_setzen()`
und `geocoder_konto_setzen()`; beide ziehen den Zwischenspeicher der laufenden
Anfrage nach, weil die Seite sich nach dem Speichern selbst ausgibt. Beide
Leser vertragen eine **fehlende** Spalte bzw. Tabelle — das Fenster zwischen
Deploy und `update.php`.

In den Browser kommen die Werte über **`ui_geocoder_bootstrap()`**, und zwar
aus `ui_ortsfeld()` selbst: Wo ein Ortsfeld steht, stehen seine Einstellungen,
und keine Seite kann sie vergessen. Der Beleg war bis Web 17.1.1
`admin_stammdaten.php` — sie rief `ui_krypto_bootstrap()` nie auf und hätte
die Einstellungen sonst nicht gehabt. Die Seite ist gestrichen (Web 18.0.0),
die Bauform bleibt: Der nächste Ortsfeld-Einbau kann wieder auf einer Seite
ohne Verschlüsselung stehen. Ausgegeben wird
**`window.GEO_AN`/`window.GEO_DIENST`**, nicht `const`: Ein `const` auf
oberster Ebene liegt im globalen lexikalischen Bereich, wird aber keine
Eigenschaft von `window` — eine eigene Datei, die `global.GEO_DIENST` liest,
findet dann nichts (F-S9-P-08). `ui_geocoder_hinweis()` gibt die Kleinzeile
unter dem **ersten** Ortsfeld der Seite aus, und nur bei eingeschalteter
Suche.

**Die Ortswahl** (`assets/ortswahl.js`, Web 9.4.0, E-P3-34): Der Pin-Knopf
am Ortsfeld (`ui_ortsfeld` mit `'ortswahl' => true`) öffnet ein Blatt mit
„Meine Position übernehmen" (`navigator.geolocation`, nur über HTTPS) und
„Auf der Karte wählen" (Leaflet-Dialog mit **Fadenkreuz** in der Kartenmitte
statt Klick-Marker — auf dem Handy verdeckt der eigene Finger sonst genau die
Stelle). Zur Koordinate holt `EdGeocoder.umkehr()` eine Adresse; sie füllt
das Feld nur, wenn es leer ist (`EdOrtsfeld`-Steuerobjekt, `uebernehmen()`),
und die Anfrage trägt ausschließlich die Koordinate.

Seit Web 15.8.0 tragen **fünf** Felder den Knopf statt zweier: Einsatzort,
manueller Abfahrtort, Transportziel (über den Feldkatalog, `'ortswahl' =>
true` an `transport_dest`), das Lagefeld des Standorts (`einstellungen.php`)
und das der Zielklinik im Standortdialog (`stammdaten_ui.php`). Bis Web 17.1.1
stand statt des letzten das Lagefeld der systemweiten Stammdatenpflege in
dieser Aufzählung; die Zahl blieb dieselbe, gezählt wird jetzt das Richtige. Der Block dafür steht in
`ui_ortsfeld()` **einmal** und wird in beiden Zweigen ausgegeben — bis Web
15.7.1 rendete ihn nur der `feld = true`-Zweig, und die Nur-Lage-Fassung der
Stammdaten hatte deshalb keine Karte (Backlog Nr. 70).

Der Dialog selbst kann seit Web 15.8.0 zweierlei mehr. Erstens ein
**Suchfeld** im Kopf (nur bei `EdGeocoder.an()`): Ein Treffer ruft
`karte.setView()` und schreibt den Namen ins Suchfeld — **ins Formular
schreibt er nichts**; erst „Übernehmen" übernimmt (F1). Zweitens die
**aufgezeichnete Spur**: Der Aufrufer übergibt sie als Feld oder als
Funktion, `EdOrtswahl.registriere(praefix, steuerobjekt, {spur})`; der Dialog
wartet nicht auf sie, sondern zeichnet nach — Linie in `EdGeo.spurFarbe(0)`,
`EdGeo.markerRing()` an Anfang und Ende, Legende sichtbar. `fitBounds` läuft
**nur** bei leerem Ortsfeld und **nur**, solange niemand selbst geschoben oder
gezoomt hat (`dragstart`/`zoomstart` setzen ein Merkzeichen). Pfeile trägt der
Dialog nicht: Hier wird ein Punkt gewählt, keine Fahrt gelesen.

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

### 4.98d Der Anhebelauf für Altbestand (`api/pat_anheben.php`, ab Web 19.0.0)

Ein Feld, das aus einer Klartextspalte in den verschlüsselten Block wandert,
lässt vorhandene Daten zurück — und **der Server kann sie nicht selbst
verschlüsseln**: Der Inhaltsschlüssel liegt in der Schlüsselhülle des Kontos
und wird aus dem Passwort abgeleitet. Umziehen kann nur der Browser.

`api/pat_anheben.php` ist die beiden Hälften dieses Umzugs. **GET** liefert bis
zu 200 Einsätze dieses Kontos, die noch Klartext in `missions.notes` haben,
samt vorhandenem `pat_blob`. **POST** nimmt je Einsatz den neuen Blob entgegen
und setzt die Spalte auf `NULL`. `assets/unlock.js` ruft beides im Hintergrund
auf, sobald ein Inhaltsschlüssel vorliegt — an **allen drei** Entsperrwegen
(Vormerkfach nach der Anmeldung, `EdKeyGuard`, Dialog), in Runden, ohne dass
jemand darauf wartet.

Vier Regeln, jede aus einem konkreten Schaden hergeleitet:

- **Eine Wache je Zeile.** `missions` führt kein `updated_at`. Jedes `UPDATE`
  trägt deshalb `notes IS NOT NULL` **und** `pat_blob <=> ?` — der Blob muss
  noch genau der sein, den dieser Browser gelesen hat. `<=>` statt `=`, weil
  der Blob `NULL` sein darf. Sonst überschriebe ein zweites Fenster eine
  inzwischen geänderte Diagnose, lautlos.
- **Kein `uhr_gesperrt = 1`, kein `edited = 1`.** Das Einsatzformular setzt beides
  bei jedem Speichern, und `ingest.php` hört bei `uhr_gesperrt = 1` auf, Daten der
  Uhr zu
  übernehmen. Ein Anhebelauf, der den Formularweg nachbaute, fröre den
  gesamten Altbestand eines Kontos still gegen die Uhr ein. Geschrieben werden
  genau zwei Spalten.
- **Der Blob gewinnt.** Steht dort schon eine Notiz, bleibt sie; die Spalte ist
  dann ein Rest.
- **Ein unlesbarer Blob wird nicht angefasst.** Er gehört zu einem anderen
  Schlüssel; ihn zu ersetzen hieße, fremde Angaben zu löschen. Der Klartext
  bleibt dann stehen.

**Es gibt keinen gespeicherten Merker.** „Einmal je Konto" ist die Wirkung,
nicht der Mechanismus: Sobald kein Einsatz mehr Klartext trägt, liefert GET eine
leere Liste. Ein Merker kostete eine Spalte samt Migration — und wäre falsch,
sobald wieder Klartext hereinkommt: eine eingespielte Sicherung mit Nutzlast 10,
ein CSV-Import einer alten Datei, das Zurücksetzen des Demo-Kontos. Der
abgeleitete Zustand kennt diesen Fall von selbst.

### 4.98a Eine Stelle je Sache — und das Register, das es nachzählt

**R83, gebaut in Schritt 15 (Zentralisierung), Stand Web 20.34.0.** Die
Anwendung hatte für eine Reihe von Sachen mehrere Eingänge — nicht als
Entwurf, sondern weil sie gewachsen ist. Schritt 15 hat sie
zusammengeführt und, wichtiger, ein **Maß** dafür angelegt.

| Sache | Der eine Weg | Was es vorher gab |
|---|---|---|
| Konfiguration lesen | `konfig('schluessel')` | `$CFG` an 46 Stellen, `config.php` siebenmal eingebunden |
| Sitzung starten | `sitzung_starten()` | 9 `session_start()` |
| API-Eingang | `api_methode()`, `api_rumpf()` | 12 eigene `php://input`, 17 eigene Fehlerschlüssel |
| Flash-Meldung | `flash_setzen()` / `flash_holen()` | 22 direkte `$_SESSION['flash…']` |
| Schema fragen | `db_hat_tabelle/spalte/index()` | 9 eigene `information_schema` |
| Transaktion | `db_transaktion()` | 33 eigene `beginTransaction()` |
| Kindtabellen eines Einsatzes | `einsatz_lib.php` | 30 eigene `INSERT`/`DELETE` |
| Spalten von `missions` | `mf_spalten($zweck)` | 12 Handlisten |
| Zahl, Größe, Zeit (PHP) | `format_lib.php` | 4 Byte-Formatierer, 2 Zeitformen, 27 `number_format` |
| Zahl, Größe, Zeit (Browser) | `EdFormat` | 14 benannte Formatierer in 5 Dateien |
| Anfrage an den Server | `EdApi.postJson/.postForm` | 20 Sendestellen, 7 Achsen Unterschied |
| Meldung im Browser | `EdHtml.meldung()` | 7 Nachbauten, 3 Ton-Tabellen |
| Karte anlegen | `EdKarte.anlegen()` | 4 Präambeln in 3 Reihenfolgen |
| Patientenliste laden | `EdPat.listeLaden()` | 3 gleichlautende Auftakte |
| Archiv (ZIP) bauen oder öffnen — seit Web 20.39.0 | `zip_lib.php` (`zip_bauen()`, `zip_eintrag()`, `zip_oeffnen()`, `zip_lesen()`) | 4 eigene `new ZipArchive` samt je eigener Frage, ob die Erweiterung da ist (Z39) |
| Suche, Filterpillen, Seitenwahl, Reiter — seit Web 20.39.0 | `ui_listenkopf()`, `ui_listenfuss()`, `ui_reiter()` | 8 Stellen Markup von Hand in `admin_users.php` (Z40) |

**Das Register ist `tools/zaehlung/register.php`.** Es führt je Sache eine
Zeile mit einer **Decke** — wie viele Stellen es höchstens geben darf — und
zählt mit dem **Tokenizer**, nicht mit `grep`: Ein Aufruf in einem Kommentar
oder in einer Zeichenkette zählt nicht, ein Methodenaufruf `->date(` auch
nicht. Der Stufe-1-Schritt „Zentralisierung — hält jede Sache ihre eine
Stelle?" fährt es bei jedem Push, mit vorgeschalteter Selbstprobe (34 Fälle
mit Sollwert).

**Drei Dinge, die man dem Register nicht ansieht und wissen muss:**

**Nicht jede Decke ist null.** Vier Zeilen enden begründet darüber
(E-ZE-27 bis -30): Die Zentrale selbst zählt mit; ein Zählmuster schlägt
falsch an (eine Parameterweitergabe, die wie ein Formularfeld aussieht; ein
`<p class="meldung">`, das gar keine Meldung ist); oder eine dünne
Weiterleitung bindet einen **Leerwert** je Zusammenhang, und ihn aufzulösen
hieße, ihn an fünfzehn Aufrufstellen zu wiederholen statt an fünf. Jede
dieser Decken trägt den Grund ausgeschrieben im Register.

**Das Register ist eine Liste, kein Spürsinn.** Es sieht keine Sache, für
die niemand eine Zeile angelegt hat.

**Die Zeilen messen verschieden scharf.** Die Zeile für die Formatierer im
Browser zählt über eine **Namensliste** — einen neu erfundenen Formatierer
unter neuem Namen sieht sie nicht. Die sechste Kilometerfassung des Bestands
(`assets/luftlinie.js`) hat erst eine Gegenprobe über das Muster der
**Rechnung** gefunden, und eine dritte Dauer-Schreibweise in
`assets/schneiden.js` steht aus demselben Grund noch (Backlog Nr. 273). Wer
etwas zentralisiert, zählt am Ende einmal unabhängig nach.

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
| Ratenschutz | `ratelimit_lib.php` | Zählung je Konto **und** IP, in der Datenbank. Greift **vor** teuren Prüfungen (bcrypt, PBKDF2), Antwortzeit bei Misserfolg konstant. Seit Web 4.4.0 an `login`, `salt`, `reset`, `pair`; seit 13.0.0 dazu `pair_start` (jede Sitzungsanfrage je Adresse) und `pair_code` (Code-Eingabe im Web je Konto und Adresse), E-S5-16. |
| Fester Vergleichswert | `AUTH_VERGLEICHSWERT` in `db.php` | Ein bcrypt-Hash ohne zugehöriges Geheimnis, damit auch der Zweig „Kennung unbekannt" eine Passwortprüfung rechnet. Ohne ihn beantwortet die Antwortzeit die Frage, welche Konten und Geräte es gibt. **Seine Rundenzahl muss zu `PASSWORD_DEFAULT` passen, und die wächst mit den PHP-Fassungen** — seit Web 19.1.2 prüft `login.php` das mit `password_needs_rehash()` und rechnet im Bedarfsfall einen frischen Hash statt eines Vergleichs (Backlog Nr. 93; vorher 173,7 ms Abstand, nachher 0,1 ms). Seit Web 13.0.0 daneben `GERAET_VERGLEICHSWERT` (SHA-256) für die Gerätepfade — dieselbe Aufgabe, anderes Verfahren (E-S5-42). |
| Antwort abschließen | `antwort_abschliessen()` in `smtp.php` | Beendet die Antwort, bevor der Mailversand beginnt. Nimmt dem Versand die messbare Wirkung auf die Antwortzeit. |
| Schlüssel-Prüfsumme | `assets/crypto.js` | Erkennt, ob ein Inhaltsschlüssel zum Konto gehört. Der Server lernt dadurch nichts über den Schlüssel — er gewinnt nur die Fähigkeit, den einen Fehler zu erkennen, der alles kostet. |
| Schlüsselbindung | `assets/keyguard.js` | Bindet den zwischengespeicherten Inhaltsschlüssel an die Hülle, aus der er stammt, und lässt ihn nach derselben Frist ablaufen wie die Sitzung — **gleitend wie sie**: Jeder Treffer erneuert den Zeitstempel (R44, seit Web 12.9.0). Vorher war es eine feste Frist ab dem Entsperren, und genau daraus entstand der Entsperrdialog mitten in der Arbeit. **Muss vor `unlock.js` geladen werden.** |
| Fehlerantwort der Endpunkte | `db.php` | `json_fehler()` protokolliert den vollen Ausnahmetext und gibt nach außen nur eine achtstellige Kennung. `fehler_kennung()` für Stellen mit eigener Antwortform (`ingest.php`). |
| Zeitrechnung | `db.php` | **`TIMESTAMP` und `DATETIME` verhalten sich verschieden, und das ist bei jeder Zeitspalte mitzudenken.** `TIMESTAMP` rechnet MySQL beim Schreiben in UTC um und beim Lesen zurück — der gespeicherte Wert ist unabhängig von der Sitzungszone immer richtig (`pair_sessions.erstellt_am`, `devices.last_seen`/`created_at`, `users.created_at`, `missions.created_at`, `deleted_refs`). `DATETIME` speichert unverändert, was dasteht; dort entscheidet die Sitzungszone (`rate_limits`, `password_resets.expires_at`, sowie die Einsatz- und Papierkorbzeiten — Letztere werden aber über `local_to_utc()` bzw. `UTC_TIMESTAMP()` befüllt und waren nie zonenabhängig). |
| Zeitrechnung | `db.php` | Die Verbindung steht seit Web 4.5.2 ausdrücklich auf UTC (`SET time_zone = '+00:00'`). Ohne das käme die Zeitrechnung von `NOW()` aus einer Hoster-Einstellung, und `NOW()` und `UTC_TIMESTAMP()` liefen um den Zonenversatz auseinander. Der Unterschied im Code bleibt: `UTC_TIMESTAMP()` für den Papierkorb (90-Tage-Frist, `TRASH_DAYS`), `NOW()` für Kurzlebiges (Ratenschutz, Token, Kopplungssitzungen). Die **Anzeige** rechnet in PHP nach `konfig('app.timezone')` um (bis Web 20.26.2: `$CFG['app']['timezone']`). |
| Sitzungsende | `session_lib.php` | Eine Fassung für Abmelden, Ablauf, gelöschtes Konto **und** Passwortwechsel; räumt die Schlüssel im Browser und nennt den Grund. `session_verwerfen()` für Abrufe, die JSON erwarten. |
| Meldung über eine Umleitung | `session_lib.php` | `flash_setzen($ton, $text)` und `flash_holen()` (liest **und** löscht), Töne `notice` und `error`, Sitzungsschlüssel `flash`. Ab Web 20.28.0; vorher setzten drei Seiten `$_SESSION['flash_notice']` und `$_SESSION['flash_error']` an 22 Stellen von Hand. **Ein** Schlüssel statt zwei: Der Ton ist eine Eigenschaft der Meldung, und bei zwei Schlüsseln entscheidet die Reihenfolge des Auslesens, was jemand sieht. |
| Eingang der Endpunkte | `db.php` | `api_methode($erlaubt)` und `api_rumpf($o)` — Methodenprüfung (405 `method`) und Rumpf als JSON-Objekt (400 `leer`/`format`). Ab Web 20.28.0. **Zwei Funktionen, weil `csrf_check()` dazwischen steht**; Einzelheiten im Abschnitt „Der Eingang der Endpunkte". |
| E-Mail-Adressen | `server/email_lib.php` | Eine Fassung für Normalisierung (`email_normalisieren()`), Prüfung (`email_pruefen()`) und Dublettenerkennung (`ist_dublettenfehler()`). **Ohne Abhängigkeiten**, damit `install.php` sie vor der Ersteinrichtung laden kann. |
| Rollenprüfung | `auth_guard.php` | `ist_admin()` ist die einzige Stelle, an der die Frage gestellt wird; `require_admin()` und `ui.php` setzen darauf auf. |
| Schlüssel/Wert-Ablage | `db.php` | `app_state_lesen()`, `app_state_setzen()`, dazu ab Web 20.29.0 `app_state_mehrere()` (eine Abfrage statt n), `app_state_setzen_mehrere()`, `app_state_loeschen()` und `app_state_einmalig()` (`INSERT IGNORE`, dann zurücklesen — für die beiden Servergeheimnisse, bei denen von zwei gleichzeitigen Anfragen nur **eine** gewinnen darf). Die Längenprüfung gegen `APP_STATE_MAX` steht in `app_state_zu_lang()`. **Zwei Stellen fragen weiter selbst:** `jobs.php` (Gerätevertrag — es antwortet `500 datenbank`, wo der Helfer `null` liefert) und `job_aufraeumen_schritte()` (Verbund auf `users`). |
| Virtuelles Gerät | `db.php` | `geraet_virtuell_sicherstellen($pdo, $userId)` — holen oder anlegen, an einer Stelle statt an vier. Dazu `geraet_virtuell_kennung()`, `geraet_virtuell()` (für Listen im Speicher), `GERAETE_ECHT_SQL` und `geraete_echt_sql($alias)` für Abfragen mit Tabellenalias sowie `GERAET_VIRTUELL_MUSTER` für die eine Abfrage, die das `LIKE`-Muster bindet. Ab Web 20.29.0. |
| Einsatz laden | `einsatz_lib.php` | `einsatz_laden($id, $userId, ['spalten' => …, 'papierkorb' => 'nein'\|'ja'\|'egal'])`. Die Besitzprüfung steht **in** der Abfrage; die drei Fehlerfälle (gibt es nicht · gehört jemand anderem · falsche Seite des Papierkorbs) sind bewusst nicht unterscheidbar. Ab Web 20.29.0; zwei Stellen bleiben namentlich außen vor (siehe Dateikopf). |
| Transaktionsrahmen | `transaktion_lib.php` (bis Web 20.37.2 `db.php`, Nr. 288) | `db_transaktion($pdo, $fn)` — beginnen, den Rumpf laufen lassen, bestätigen; bei jedem `Throwable` zurückrollen und **weiterwerfen**. **Verschachtelungsfest und asymmetrisch:** Wer schon in einer fremden Transaktion steht, öffnet keine eigene und bestätigt und verwirft dann auch nichts. Vor dem `rollBack()` wird nachgefragt, ob sie noch steht — ein DDL bestätigt in MySQL still, und der Rumpf darf selbst zurückgerollt haben; sonst verdeckte eine zweite Ausnahme die erste. Ab Web 20.30.0; **neun Rahmen bleiben namentlich außen vor**, die Registerzeile Z16 führt sie mit Grund. |
| Kindtabellen eines Einsatzes | `einsatz_lib.php` | `einsatz_phasen_ersetzen()`, `einsatz_reas_ersetzen()`, `einsatz_rettungsmittel_ersetzen()`, `einsatz_besatzung_ersetzen()` — fünf Schreibwege (Formular, CSV-Import, Uhr-Eingang, Backup, Schneiden), dreißig Anweisungen, jetzt null außerhalb. Schalter `loeschen` (Vorgabe `true`; das Backup schreibt in einen gerade angelegten Einsatz) und `ignorieren` (dessen `INSERT IGNORE`). **Sie prüfen nichts** — was gültig ist, entscheidet der Aufrufer. Ab Web 20.30.0. |
| Vorbereitete Anweisung | `einsatz_lib.php` (`einsatz_anweisung()`) | Eine `PDOStatement` je Verbindung und SQL-Text. Nötig, weil `db.php` `ATTR_EMULATE_PREPARES => false` setzt: Jedes `prepare()` ist ein Roundtrip, und der CSV-Import führt seine Anweisungen bis zu 3 000-mal aus. Die Verbindung wird **mitgehalten**, nicht nur ihre Objektkennung — eine freigegebene PDO gäbe ihre `spl_object_id` an die nächste weiter. |
| Zahl, Größe, Anteil, Datum, Zeit | `format_lib.php` | `zahl_text()` · `groesse_text()` (drei Stufen: GB zwei, MB eine, KB null Nachkommastellen) · `groesse_kurz_text()` (mit Stufe „B", abgeschnittenen Nullen und `unbegrenzt` — eine **andere** Schreibweise, keine zweite Fassung) · `groesse_paar_text($ist, $grenze)` (gemeinsame Einheit: „3 von 250 MB") · `prozent_wert($teil, $ganz, 'ab'\|'kauf')` und `prozent_text()` · `datum_text()` · `datum_zeit_text($utc, $trenner)` · `datum_stunde_text()` · `zeit_relativ()` · `heute_lokal()` · `iso_utc()` / `iso_utc_lesen()`. Ab Web 20.32.0. **Sie lädt nur `konfig_lib.php`** — `install.php` erreicht sie über `plattform_lib.php`, bevor es eine `config.php` gibt. **Zwölf Stellen bleiben namentlich außen vor**, die Registerzeilen Z22–Z26 führen sie mit Grund. |
| Ortszeit | `format_lib.php` (`fmt_local()`) | **Stand bis Web 20.31.0 in `db.php`**, Name und Verhalten unverändert — alle 113 Aufrufer in 37 Dateien merken nichts, weil `db.php` die neue Datei lädt. Umgezogen, weil `datum_text()` und `datum_zeit_text()` darauf aufbauen. `local_to_utc()` **bleibt in `db.php`**: Sie liest einen Formularwert, um damit zu rechnen — die andere Richtung. |
| Zwei Trenner für Datum und Zeit | `format_lib.php` | `datum_zeit_text($utc, $trenner)` hängt den Trenner **nach** dem Formatieren an, statt ihn ins Format-Literal zu schreiben. Sonst würde der erste Trenner mit einem Buchstaben still zu Formatzeichen (` um ` ergäbe `u` = Mikrosekunden, `m` = Monat) — und so einer steht schon im Bestand. Drei Trenner sind im Umlauf (Leerzeichen 25×, Mittelpunkt 11×, Komma 1×); die Vereinheitlichung entscheidet 10c AP9 und findet dafür **eine** Stelle statt 41. |
| Formatierer im Browser | `assets/format.js` (`EdFormat`) | **Zahl und Größe** (`zahl()`, `groesse()`) — dieselben Regeln wie `format_lib.php`, über 2 014 Byte-Werte Zeichen für Zeichen geprüft. **Tag, Dauer und Strecke** seit Web 20.34.0 (`tag()`, `tagKurz()`, `spanne()`, `dauer()`, `dauerUhr()`, `minuten()`, `km()`, `kmSumme()`) — dafür gibt es **keine** PHP-Entsprechung: Dort geht es um UTC-Zeitstempel mit Zonenumrechnung, hier um einen nackten Tagesstring und um Dauern, die serverseitig nicht entstehen. **Der Leerwert ist überall ein Parameter**, kein fester Wert — die Lehre aus AP7, wo ein fester Frühausstieg aus „– um –“ ein „–“ machte. Sechs verschiedene Leer-Antworten waren im Bestand gemessen worden. **Die Datei steht im `<head>`** (`ui_seite_start()`), damit die Ladereihenfolge keine Frage mehr ist. **Fünf dünne Weiterleitungen bleiben** (Z34), jede bindet einen anderen Leerwert. |
| Spalten von `missions` | `mission_fields_lib.php` | `mf_missions_register()` führt jede der 41 Spalten **genau einmal** und sagt je Zweck, ob sie dabei ist und an welcher **Position**; `mf_spalten($zweck, $präfix, $alias)` erzeugt die Liste, `mf_spalten_sql()` den SQL-Text. Neun Zwecke: `export` · `backup` · `backup_restore` · `import_neu` · `import_aendern` · `ingest_neu` · `schnitt_neu` · `suchindex` · `range`. Die Position wird mitgeführt, weil die Listen in Menge **und** Reihenfolge eingefroren sind — eine andere Reihenfolge ändert die Spaltenfolge im CSV-Export. `mf_spalten()` verlangt je Zweck eine lückenlose Folge ab 0. Fehlt eine Spalte in einem Zweck, steht der Grund in `mf_missions_gruende()`. Ab Web 20.31.0. |
| Feste Werte in einer erzeugten Anweisung | (Aufrufseite) | Wo Werte fest im Satz stehen, hängt die Wertform an der **Spalte**, nicht an ihrer Stelle: `['uhr_gesperrt' => '1', 'origin' => "'import'"]`, `COALESCE(?, spalte)` für die vier Felder unter der Export-Schranke (A9/P10), `NULL AS spalte` im Export ohne personenbezogene Angaben. Wer ein Feld unter die Schranke nimmt, trägt es an **einer** Stelle ein. |
| Schema fragen | `db.php` | `db_hat_tabelle()`, `db_hat_spalte()`, `db_hat_index()`, seit Web 20.41.0 `db_spalte_typ()` (der volle Spaltentyp, etwa die Werte eines ENUM — P5c/AP4), seit Web 21.0.0 `db_spalte_nullbar()` (ob die Spalte NULL zulässt — P5c/AP8) — sie nehmen ein `PDO`, **zwingend**: `tools/schemaprobe/` lässt Migrationen gegen ein frisch angelegtes Schema laufen, also gegen eine andere Verbindung als `db()`. Die privaten `_hat_*` in `migration_lib.php` reichen seit Web 20.29.0 nur noch durch (E-ZE-04: gelaufene Migrationen werden nicht umgebaut). |
| Anfrage an den Server | `assets/api.js` (`EdApi`) | `EdApi.postJson(url, daten, o)` und `EdApi.postForm(url, felder, o)`, seit Web 20.34.0. Beide liefern `{ ok, status, daten, meldung }` und **werfen nie** — ein Netzfehler kommt als `status: 0`. Sie hängen das CSRF-Token selbst an (Kopfzeile bzw. Feld) und bauen den **einen** Satzbau `<Vorgang> ist fehlgeschlagen: <Grund>`. Vorrangkette des Grundes: `meldung` → `text` → `hinweis` → Ersatzsatz mit Kennung. **`error` steht nicht in der Kette** — es trägt Maschinenwörter, keine Sätze, und erscheint nur als Kennung in der Klammer. **`ok` ist ein Transport-Urteil**: Es prüft `daten.ok !== false`, nicht `=== true`, weil die lesenden Endpunkte gar kein `ok` schicken — wer ein fachliches `ok` braucht, prüft `a.daten.ok` mit. **Die Array-Regel von `postForm` ist sicherheitsrelevant**: Ein Feldwert, der ein Array ist, geht als `name[]` hinaus; ohne die Klammern liest PHP eine Zeichenkette, `is_array()` schlägt fehl, und die Schlüsselblatt-Rückfrage zählt einen Fehlversuch. **Die Datei steht im `<head>`** — nicht in der Immer-Liste, weil `ui_geruest_ende()` auf `einstellungen.php` und `import.php` **nach** den Seitenskripten steht und `unlock.js` dort zur Ladezeit sendet. |
| Meldung im Browser | `assets/html.js` (`EdHtml.meldung`) | `EdHtml.meldung(ton, text, o)`, seit Web 20.34.0 — zeichengleich mit `ui_meldung_markup()` in `ui.php`; 200 von 200 Prüffällen ergaben denselben DOM-Baum. **Fünf Töne, geschlossene Liste, Wurf beim sechsten**, genau wie PHP. `o.auftakt` (fetter Vorspann, maskiert), `o.knopf` (fertiges Markup, unmaskiert), `o.roh` (Text **nicht** maskieren — ein benanntes Loch mit genau einem Verbraucher, der Erfolgsmeldung des Imports mit Link und Umbruch). Vorher: sieben eigenständige Nachbauten, keine zwei gleich, drei verschiedene Ton-zu-Symbol-Tabellen. **Die Tonklasse wird zusammengesetzt** — `tools/quelltext/` (`vollstaendigkeit`) sieht sie deshalb nicht und meldet `.meldung-ok` und `.meldung-schutz` als Regel ohne Markup. Das ist kein Befund, sondern die Eigenschaft des Bausteins. |
| Auftakt der Patientenanzeige | `assets/patient.js` (`EdPat.listeLaden`) | `EdPat.listeLaden(liste, o)`, seit Web 20.34.0: Schlüssel holen, Sperrbanner nach der Regel setzen (sichtbar genau dann, wenn kein Schlüssel da ist **und** die Liste überhaupt einen `pat_blob` enthält), entschlüsseln, zählen. Gibt `{ ck, zahl }` zurück und **entscheidet nicht**: Tagesansicht und Zeitraum kehren bei fehlendem Schlüssel zurück, die **Suche nicht** — sie muss ihre Trefferliste auch gesperrt zeigen und dabei den Altersfilter sperren. `zeigeUnlesbar()` ruft der Aufrufer **nach** seiner Schleife. **`einstellungen.php` bleibt draußen** (Z36 endet bei 1): Es teilt nur den Aufruf, arbeitet mit Fenstern zu 250 aus einem Bestand von tausenden, und `hinweisUnlesbar()` wertet die **ganze** Liste aus — über ein Fenster gesagt wäre der Satz falsch. |
| Maskierung | `assets/html.js` (`EdHtml.escape`) | Eine Fassung, auch in Attributpositionen sicher (fünf Zeichen statt drei). Seit Web 4.6.0 in einer eigenen Datei statt in `missiontable.js` — die wird nur von zwei Seiten geladen, gebraucht wird die Maskierung auf fünf. `EdMissionTable.escape`/`.esc` bleiben als Weiterleitung. **Nicht dasselbe** wie `xmlEscape()` in `export.js`: GPX ist XML mit eigenen Regeln. |
| Patientenanzeige | `assets/patient.js` | Eine Entschlüsselungsschleife statt fünf; unterscheidet sichtbar „keine Angaben" von „nicht lesbar". `entschluessleListe()` wird seit Web 4.6.0 von allen Aufrufern benutzt (Tages-, Zeitraum- und Suchansicht, Export, Import-Abgleich, Backup-Lauf) und schreibt je Einsatz `_pat` und `_patState`. |
| Migrationsschutz | `migration_lib.php` (`migrationen_inhalt_zaehlen()`) | Destruktive Migrationen tragen `zerstoert` (Klartext, was verlorenginge) und optional `inhalt` (Spalten, deren Inhalt die Ausführung blockiert — einzeln freigebbar, wenn die Daten gesichert sind). **Seit Web 21.0.0 dazu `vorbedingung`** (P5c/AP8, E-P5c-125): eine Funktion, die zählt, was die Migration unmöglich machte, und `vorbedingung_weg`, der eine Satz, wie man es herstellt. Sie sperrt **ohne Freigabe-Kennung** — kein Häkchen auf Betrieb → Updates, ein eigener Hinweis in `php update.php` —, weil ein `MODIFY … NOT NULL` über einer NULL-Zeile nichts freizugeben hätte. Eine blockierte Migration hält die Kette **nicht** an — sie hat nichts getan, anders als ein Fehler. |
| Blockabfrage | `db.php` (`sql_in_bloecken()`) | Eine Abfrage je Tabelle statt einer je Datensatz, in Blöcken zu 1000 IDs. Benutzt von Export, Tagesansicht und Backup. Die Vorlage trägt `{IDS}` und ist **keine** Formatzeichenkette — ein Prozentzeichen im SQL bleibt ein Prozentzeichen. |
| Formatkennung des Chiffretexts | `assets/crypto.js` (`CHIFFRE_PRAEFIX`), `validate_lib.php` (`PAT_BLOB_RE`, `WRAP_RE`) | `edk1:` vor jedem Chiffretext. Schreiben immer, Lesen großzügig (keine Kennung = erste Fassung), unbekannte Kennung wird als solche gemeldet. Betrifft `pat_blob` **und** beide Schlüsselhüllen — sie kommen aus derselben Funktion. |
| Rundenzahl der Ableitung | `db.php` (`KDF_ITER_ZIEL`, `KDF_ITER_LISTE`), `users.kdf_iter` | Je Konto gespeichert und gelesen, nicht angenommen. `deriveKeys()` verlangt sie als **Pflichtparameter ohne Vorgabewert** — ein Vorgabewert ließe jede vergessene Aufrufstelle stillschweigend mit dem alten Wert rechnen, und das fiele erst bei der nächsten Anhebung auf. Der Salz-Endpunkt nennt jeder Adresse dieselbe **Liste**, damit er nicht verrät, welche Konten es gibt. **Beim Anheben von `KDF_ITER_ZIEL` muss der bisherige Wert in `KDF_ITER_LISTE` stehen bleiben**, sonst kann sich kein Bestandskonto mehr anmelden; die Zeile „Schlüsselableitung" auf Betrieb → Status meldet diesen Zustand (rot, „Anmeldung blockiert"). |
| Wiederherstellungsschlüssel | `assets/crypto.js` (`pruefeRecoveryCode()`) | Prüft Länge und Alphabet **vor** der Ableitung und unterscheidet Tippfehler von falschem Zettel. Ohne die Prüfung entsteht aus einer krummen Eingabe klaglos ein falscher Schlüssel, und die Meldung lautet in beiden Fällen „passt nicht". |
| Passwortgüte | `assets/pwquality.js` | Mindestlänge im Skript statt nur als HTML-Attribut, Stärkeanzeige, Abgleich gegen häufige Passwörter. Seit Web 4.7.0 an allen fünf Stellen eingebunden: Erstvergabe, Zurücksetzen, Passwortwechsel, Backup-Passwort, Export-Archivpasswort. Vorher lag der Baustein ungenutzt neben `minlength`-Attributen. |
| Seitenhülle | `ui.php` (`ui_seite_start()`, `ui_seite_ende()`) | Ab Web 7.1.0. Doctype, `<head>`, Eröffnung und Abschluss des `<body>` — vorher 28-mal von Hand, mit zwei Schreibweisen des Viewports und zwei Titeltrennern. **Der Tab-Titel lautet seit Web 15.3.1 „&lt;Seite&gt; — Gen-EM NAdoku"** (vorher „— Einsatzdoku"); die zweite Stelle, die einen Titel selbst setzt, ist die Wartungsseite in `wartung_lib.php`. Leaflet-CSS nur auf Kartenseiten und **vor** `style.css`, damit eigene Regeln die des Kartenwerks überschreiben. **Ohne Abhängigkeit auf oberster Ebene**, damit `install.php` sie vor der Ersteinrichtung laden kann; `asset()`, `e()` und `favicon_tags()` werden über `ui_asset()`/`ui_e()`/`ui_favicon()` nur benutzt, wo es sie gibt. **`install.php` lädt sie seit Web 9.10.1 am Dateianfang** — vorher stand das `require_once` in `render_page()` selbst, und weil die Aufrufer ihr Argument mit `ui_meldung_markup()` und `ui_knopf()` bauen (PHP wertet Argumente vor dem Aufruf aus), endete jeder Zweig in „Call to undefined function". Der Einrichter war damit seit Web 9.1.0 unbenutzbar (F-P3-AR). |
| Streifen über dem Inhalt | `ui.php` (`ui_hinweise()`), `umgebung_lib.php`, `ankuendigung_lib.php` | Ab Web 20.38.0 (P5c/AP1, E-P5c-55). **Eine Reihe, vier Streifen, feste Reihenfolge:** Umgebung → Ankündigung → Demo → Datenschutz. Sie steht an der Stelle des Demo-Hinweises (`ui_leiste_ende()`), nicht unter der Kopfleiste — dort verschob ein Streifen seit jeher die klebende Leiste (F-P3-G). Die Seiten ohne Gerüst rufen `ui_hinweise()` selbst als erstes Kind ihres `<main>`, die Anmeldeseiten über der Karte; `einwilligung.php` ohne den Datenschutz-Streifen, dessen Ziel sie ist. **Das Etikett** kommt aus `konfig('app.umgebung')` über `umgebung()` — nie abgeleitet, geschlossene Farbliste — und wirkt an drei Stellen: Titelvorsatz in `ui_seite_start()` (und in `stoerung_seite_html()` für Wartung und Überlast), Klasse `.kopf-umgebung` in `ui_kopf()`, Streifen. **Die Ankündigung** liegt in drei `app_state`-Zeilen (`ankuendigung_text`, `_ton`, `_bis`); das × ist ein Formular an `ankuendigung.php` (ohne `auth_guard`, weil der Streifen auch über der Anmeldung steht), das in der Sitzung die **Kennung** dieser Ankündigung merkt — eine neue erscheint wieder. `login.php` löscht die Marke beim Anmelden, weil `session_regenerate_id()` die Daten behält. Kein × ohne Sitzung (lesende Seiten ohne Cookie) und nicht in der eigenen Sitzung der Setzseite. `assets/ankuendigung.js` schließt ohne Neuladen, wo es `CSRF` gibt, und steht **hinter** der Reihe, damit die Reihe mit ihrem letzten Streifen verschwinden kann. |
| Reiter | `ui.php` (`ui_reiter()`), `assets/reiter.js` | Ab Web 20.39.0 (P5c/AP2, E-P5c-25). Wechsel zwischen gleichrangigen Sichten einer Seite, serverseitig — jeder Reiter ist ein Verweis, der Parameter steht in der Adresse. `attr` je Reiter wird durchgereicht (AP9 braucht `data-cancel-form`). **Seiten mit Reitern tragen keine Unterpunkte in der Leiste:** `menue.js` baut keine, wenn `#inhalt` eine `.reiter`-Reihe enthält. Gestaltung: `Design.md` 9.37. |
| Listenkopf und Listenfuß | `ui.php` (`ui_listenkopf()`, `ui_listenfuss()`), `assets/listenkopf.js` | Ab Web 20.39.0 (P5c/AP2, F-P5c-54). Suchfeld mit versteckten Feldern, Filterpillen (mit Zahl, eine mit Kreuz für einen Filter aus der Adresse), ein Auswahlfeld, das mit Skript sofort abschickt; darunter Zählung und Seitenwahl (erste, letzte, Nachbarn, Ellipse). Bis dahin nur als Markup in `admin_users.php`; Registerzeile Z40 hält es außerhalb von `ui.php` auf null. |
| Aufklappbare Zeile | `ui.php` (`ui_zeile()` mit `daten`) | Ab Web 20.39.0 (P5c/AP2, E-P5c-26). Eine Zeile mit Angaben wird ein `<details class="zeile-mehr">`, dessen `<summary>` die Zeile ist; die Angaben stehen darunter als `<dl>`. `aktionsspalte => true` hält die leere Spalte in Zeilen ohne Angaben. Die Gegenregel zu `.zeile:first-child` ist Pflicht (F-P5c-13, `Design.md` 9.2). |
| ZIP-Archive | `zip_lib.php` | Ab Web 20.39.0 (P5c/AP2, E-P5c-57, R83). `zip_verfuegbar()`, `zip_bauen($ziel, [Name => Pfad], $packen)` (ungepackt als Vorgabe, weil die Teile schon gzip und versiegelt sind), `zip_eintrag($pfad, $name)`, `zip_oeffnen($pfad)` (nur lesend), `zip_lesen($pfad, $fn)`, `zip_namen()`. Vorher öffneten vier Stellen selbst ein Archiv und fragten je selbst nach der Erweiterung; Registerzeile Z39. **Lädt selbst nichts.** |
| Etwas melden, das schiefging | `systemmeldung_lib.php` (`system_melden()`, `system_rueckfall()`) | Ab Web 20.40.0 (P5c/AP3, E-P5c-58, Backlog Nr. 248). Der Reiter System statt `error_log()`: Kennung, bereinigte Meldung, Datei und Zeile; ohne Datenbank derselbe Satz mit derselben Kennung im Fehlerprotokoll des Webspace. `error_log()` bleibt an zwei Stellen (Register Z38, Decke 2). Einzelheiten 4.99g. |
| Protokoll lesen | `protokoll_lib.php` (`protokoll_liste()`, `protokoll_zahl()`, `protokoll_quellen()`) | Ab Web 20.39.0 (P5c/AP2, E-P5c-38). Je Reiter ein bis drei Quellen mit derselben Zeilenform, **je Quelle eine Abfrage, nie ein UNION** (Kollationen, 4.99g). Die Seite, die Zählkarte der Statusseite und der Archivjob lesen alle darüber. |
| Krypto-Rüstzeug der Seiten | `ui.php` (`ui_krypto_bootstrap()`) | Ab Web 7.2.0. Die Verweise auf `crypto.js`, `keyguard.js` und `unlock.js` samt `PAT_WRAP`, `KDF_SALT`, `KDF_ITER` und `KDF_ITER_ZIEL`; wahlweise `PAT_KEY_CHECK`, `CSRF` und `pwquality.js`. Vorher acht Blöcke in sieben Dateien — mit zwei Namen für dieselbe Hülle. Ein **zweiter Aufruf im selben Seitenaufbau gibt nichts aus und meldet es in den Reiter System** (`system_melden('ui', …)`): Zwei Einbindungen von `crypto.js` wären ein `SyntaxError`, der das ganze zweite Skript verwirft. |
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

### 2a Die Arbeitsumgebung — und was ihre Motoren messen

**Was der Wegwerf-Container mitbringt, was nachgeholt wird, welche sieben
Umgebungswerte er braucht und was er nicht kann, steht seit PK-01 in
`Sandbox-Setup.md`** — dort und nur dort. Hier stand es bis dahin; zwei
Beschreibungen derselben Umgebung altern getrennt, und die eine hätte den
Umbau der Beschaffung in PK-02 nicht mitbekommen.

Was hier bleibt, ist die andere Frage: **wie oft welches Prüfmittel welche
Engine fährt, und warum.**

**Seit AP3b der Mockup-Runde fahren die drei Prüfmittel sie selbst**
(`--motor chromium|firefox|webkit`, Vorgabe Chromium). Motorwahl und die
nötige Firefox-Voreinstellung stehen **an einer Stelle**, `tools/motor.mjs`;
Bilderlauf, Klickprobe und Stilvergleich holen sie dort. Wie oft welches
Mittel dreifach fährt, ist nicht für alle gleich, und der Unterschied ist
gemessen, nicht geschätzt:

| Mittel | dreifach | Kosten je Motor | warum |
|---|---|---|---|
| **Stilvergleich** | **immer** | 14–18 s | Berechnete Stile sind genau die Frage, bei der Motoren auseinandergehen. Die Aussage ist die **Übereinstimmung** der drei Zahlen, nicht die Zahl: Der Vergleich misst alt gegen neu *innerhalb* eines Motors, also meldet ein Motor, der eine neue Regel nicht kann, **weniger** Abweichungen. |
| **Bilderlauf** | **gestaffelt** | rund 9 min voll | Chromium voll; Firefox und WebKit über die berührten Seiten (`--nur`) plus `--risiko` — zehn Seiten mit motorempfindlichem CSS, die Liste samt Gründen im Kopf von `aufnehmen.mjs`. Dreimal voll wären 26 Minuten nach jedem Arbeitspaket. |
| **Klickprobe** | **nach Bedarf** | rund 3,5 min | Sie misst Wege, nicht Darstellung — JS-Semantik ist über Motoren hinweg weitgehend dieselbe. Dreifach bei Dialogen, Blättern, Übergängen, Fokus, `<details>`, Zeigerereignissen. **Und nur mit frisch eingespieltem Referenzbestand zwischen den Läufen** (8 s); ohne ihn melden Lauf 2 und 3 falsche Fehlschläge, gemessen 40 / 38 / 36 von 40. |

**Zwei Dinge, die jeder Motorlauf wissen muss**, beide gemessen und beide in
`tools/motor.mjs` begründet: Headless Firefox meldet ohne Voreinstellung
„kein Zeiger, kein Hover" und misst damit den ganzen Media-Block der
36-px-Bedienhöhe nicht (`ui.primaryPointerCapabilities` und
`ui.allPointerCapabilities` auf `6`); und nur **Chromium** verliert die
Eingabeart des Fingerlaufs am Vollseiten-Screenshot — Firefox und WebKit
behalten sie, weshalb die CDP-Krücke dort weder nötig noch möglich ist.

**Der dreifache Lauf hat am ersten Tag zwei Befunde geliefert**, und beide
wären sonst nicht aufgefallen: `import.php` lief bei 360 px **nur in WebKit**
um 6 px über, weil WebKit den längsten Eintrag eines Auswahlfeldes in den
Überlauf des Kastens rechnet (Nr. 185, behoben mit
`select.feld-eingabe{contain:paint}`); und die Klickprobe maß die Drehung der
Richtungspfeile mit `getScreenCTM()`, das in WebKit die CSS-Transformation
eines HTML-Vorfahren nicht enthält — ein Fehler im Prüfmittel, der wie einer
der Anwendung aussah (Nr. 186).

**Die vierte Zahl: Karten ausserhalb des Gerüsts** (seit Web 20.21.1,
Backlog Nr. 225). Der Lauf zählt je Seite, wie viele `section.karte` bzw.
`details.karte` **nicht** in `main.inhalt` hängen, und nennt sie beim Titel.

Der Anlass war ein `ui_karte_ende()` zu viel auf der Profilseite: Es gab ein
`</div></section>` ohne Gegenstück aus, der Parser nahm für das `</div>` das
nächste offene — `div.rahmen` — und schloss damit `form`, `main.inhalt` und
`rahmen` mitten auf der Seite. Vier Karten lagen danach direkt am `body`, über
die volle Fensterbreite, unter der Seitenleiste hindurch. **Zehn Tage lang.**

**Warum die drei älteren Zahlen das nicht sehen konnten:** `scrollWidth` blieb
gleich `innerWidth` — es lief nichts über, es lag nur falsch. Die Konsole blieb
still. Die Knopfhöhen stimmten. Der Lauf meldete in allen drei Engines drei
Nullen neben einer kaputten Seite. Gemessene Gegenprobe mit wieder eingebautem
Fehler: „Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0" **und**
„6 Karten geprüft · 4 außerhalb von main.inhalt".

Die Zahl nennt, was sie gemessen hat („n geprüft · m außerhalb") und nicht nur
das Ergebnis — eine Seite ohne Karten meldete sonst dieselbe Null wie eine
geprüfte.

**Ein Satz von gestern ist zurückgenommen:** Firefox meldet die
`latin-ext`-Schriftabrufe **nicht** als Konsolenfehler. Die Abbrüche
(`NS_BINDING_ABORTED`) stammten von einem Messskript, das schneller
weiterblätterte als die Schriften luden; im echten Lauf melden alle drei
Motoren 0. Ein Rauschfilter dafür ist deshalb **nicht** gebaut worden.


*Die Staffelung dieser Tabelle ändert sich mit PK-04 (E-PK-14): Der
Bilderlauf wird nach Stufen abgestuft, die von Hand gepflegte Risikoliste
fällt. Bis dahin gilt sie wie beschrieben.*

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
| `geraete` | `device_id`, `api_key_hash`, `label`, seit Web 14.2.0 auch `geraet_art` und `geraet_modell` — **ohne** das virtuelle Gerät „Manuelle Einträge" (s. u.) |
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

Gepackt abgelegt: roh rund 2,8 MB, im Wesentlichen 63 752 Spurpunkte als
JSON-Zahlen. Gepackt sind es rund 860 KB, und die Datei geht bei jedem Deploy
über FTPS mit.

**Was ein Reset kostet — gemessen, nicht geschätzt** (15.09.2026, Prüfstand
mit MariaDB und PHP auf demselben Rechner, je drei Läufe): **6,6 s** mit dem
heutigen Bestand (106 Einsätze, 63 752 Punkte), **5,9 s** mit dem Stand davor
(88 Einsätze, 55 861 Punkte). Die Zeit trägt **die Besucherin**, deren Anfrage
den fälligen Reset auslöst (`demo_reset_wenn_faellig()` aus
`auth_guard.php`) — sie sieht ihre Seite so lange nicht. Zweimal die Stunde
ist das wenig Last und trotzdem jedes Mal ein spürbarer Aufenthalt für genau
eine Person; ob es dabei bleibt, steht als Backlog Nr. 76 offen. **Nach einem
Deploy mit neuer Fixture zeigt das bestehende Demo-Konto bis zum nächsten
Reset den alten Bestand** — wer ihn sofort sehen will, drückt unter
Verwaltung → Demo-Konto „Zurücksetzen".

#### Zwei Riegel je Geheimnis — einer beim Erzeugen, einer beim Einspielen

Die Fixture **reist**: Sie entsteht auf der Referenzmaschine und wird beim
Deploy auf den Produktivserver gelegt. Ein Riegel im Erzeuger greift deshalb
nur an einem der beiden Enden. Zwei Werte in ihr sind an die Installation
gebunden, und beide haben darum ein Riegelpaar:

| Wert | Riegel im Erzeuger (`fixture/erzeugen.php`) | Riegel im Einspieler (`demo_fixture_laden()`) |
|---|---|---|
| `kdf_iter` | bricht ab, wenn das Konto nicht auf `KDF_ITER_ZIEL` steht (Backlog Nr. 155, Web 19.2.0) | weist eine Rundenzahl ab, die `KDF_ITER_LISTE` nicht mehr anbietet |
| `pat_wrap_pw` / `pat_wrap_rc` | bricht ab, wenn eine der beiden nicht `edk1:` trägt (S10/AP5) | weist sie ab — `huelle_pw_pruefen($wrap, istDemo: true)` und `huelle_rc_pruefen()`, Web 20.2.1 |

**Warum die Hüllen `edk1:` bleiben müssen.** Seit S10 hängt der
Datenschlüssel am Server-Anteil aus `config.php`, und den würfelt
`install.php` je Installation neu. Eine Hülle mit Anteil
(`edka1:<kennung>:`) lässt sich nur dort öffnen, wo dieser Anteil steht. Das
Demo-Konto bekommt bauartbedingt **gar keinen** (E-P1-19/E-S10-06):
`KONTO_ANTEILE` ist für es `null`, `ANTEIL_STAND` ist `'demo'`, und
`api/kdf_upgrade.php` überspringt es. `EdCrypto.datenschluessel()` wirft bei
einer `edka1:`-Hülle ohne Anteil ausdrücklich, statt auf die PBKDF2-Hälfte
zurückzufallen — ein Rückfall ergäbe einen Schlüssel, der nicht passt, und der
Fehlschlag sähe aus wie ein falsch getipptes Passwort.

**Was ein Abbruch kostet.** `demo_reset_wenn_faellig()` fängt jede Ausnahme ab
und meldet sie in den Reiter System (`system_melden('demo', …)`); eine verbogene Fixture lässt das Demo-Konto also
aufhören, sich zurückzusetzen — es geht nichts verloren und niemand wird
ausgesperrt. `demo_anlegen()` lässt die Ausnahme durch: Wer das Konto von Hand
anlegt, soll den Grund lesen.

Gemessen von `tools/referenzdatensatz/fixture/riegelprobe.php`: **10 von 10**,
beide Riegel in beide Richtungen, der abgefangene Reset (Demo-Konto 88 → 88
Einsätze) und die SHA-256 der echten Fixture vorher/nachher.

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
keine gibt. **Vor diesem stillen Erfolg steht seit Web 19.3.1 die Prüfung des
Formular-Tokens** (Backlog Nr. 67, Unterpunkt): Bis dahin lag der Demo-Ausstieg
davor, und ein Aufruf ohne Token bekam für das Demo-Konto 200, während jedes
andere Konto 403 sah. Der stille Erfolg gilt also für den regulären Aufruf —
`unlock.js` schickt `X-CSRF` immer mit —, nicht für jeden Aufruf.

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

Zwei neue Töpfe in `ratelimit_lib.php`, die **anders zählen** als die
übrigen: nicht Fehlversuche, sondern **gelungene** Anmeldungen. (Eine Zahl
stand hier bis Web 20.11.0 — „als die vier bestehenden“ —, und sie war
schon lange falsch.)

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
| `Uploader.mc` | Job-Queue (fertige Einsätze → Segmente → aktive), Chunking ≤ 500, `next_seq`-Bestätigung, Purge inkl. Marken; `hasServer()`/`hasCredentials()`. **Seit Uhr 3.1.0** trennt `onResponse()` drei Fälle: eine Störung (später erneut), ein dauerhaft abgewiesenes Paket (Marke `bad_<ref>`, wird übersprungen) und ein abgemeldetes Gerät (`401`/`403` → `abgemeldet`, das Senden hält an). Siehe 5.1c |
| `Input.mc` | Eingabemodell: `ActionDelegate` übersetzt Tasten, Wischgesten und Langdrücke einmal zentral in Aktionen (s. Abschnitt 5.1) |
| `DeviceProfile.mc` | je Profil eine eigene Fassung in `source-tasten5/` bzw. `source-tasten3/`; liefert `HAS_UP_DOWN` und die Bedienhinweise |
| `Ui.mc` | Geometrie relativ zur Displayhöhe (`s()` liefert bei 260 exakt den Ausgangswert), Markenfarben, Rea-Marker |
| `Nav.mc` | Pager: Uhr → Tempo → Statistik → Sync → Rea |
| `StartView.mc` | Startbildschirm „Dienst beginnen"; Hinweise zu Server-Adresse und Kopplung |
| `ClockView/SpeedView/StatsView/SyncView/CprView.mc` | Oberflächen + Delegates; erben von `ActionDelegate` und beschreiben nur noch die Aktionen |
| `SyncView.mc` | Sync-Status (Backlog = nur abgeschlossene Pakete, **ohne geparkte**), App-Version, Kopplung per START-Halten, **Verwerfen abgewiesener Pakete per kurzem START** |
| `Pair.mc` | Kopplung (seit Uhr 3.0.0 umgekehrt): holt mit `start` eine Sitzung, fragt im Takt `status`, bestätigt mit `bestaetigen` — erst danach `Storage 'cred'`. Bis dahin liegen Code, Kennung und Schlüssel **nur im Arbeitsspeicher** |
| `PairView.mc` | Die Kopplungsansicht: zeigt den Code groß, dazu Restzeit und Verbindungshinweis; BACK bricht ab. Eigene Ansicht, weil der Code Buchstaben trägt (keine Ziffernschrift), eine Restzeit läuft und BACK hier anders wirkt als auf der Sync-Seite |
| `Const.mc` / `Util.mc` | `APP_VERSION`, Labels, Tuning-Werte; ISO-UTC, lokale Anzeige, Vibration |

Rückruf-Muster: `method()` existiert nur auf Objekten → kleine Träger-Klassen
(`TrackCb`, `CprCb`, `UploaderCb`) reichen Callbacks an die Module weiter.

> **Kartenseite entfernt (1.3.5):** `MapPage.mc` funktionierte am Gerät nicht
> zuverlässig und wurde gelöscht. Eine künftige Kartenansicht wird neu
> aufgebaut; die alte Fassung liegt in der Git-Historie.

> **Vor jeder Änderung an einer Oberfläche:** `Uhr-Layout_Regeln.md` lesen. Dort
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

### 4.99a2 Das Ersetzfenster der Geräte (ab Web 15.6.0, Backlog Nr. 134)

**Wogegen.** Der Geräteschlüssel liegt auf der Garmin-Uhr im Klartext
(`watch/source/Pair.mc`; die Plattform hat nichts Besseres — die Wear-OS-Uhr
kennt gar keine Zugangsdaten, dort sendet das Handy). Lesen kann ein Finder
nichts, `ingest.php` ist POST-only. Er kann aber **hochladen**, und damit bis
Web 15.5.2 die Phasen bestehender Einsätze ersetzen — so lange, bis das Gerät
im Web getrennt ist.

**Was schon geschützt war:** Ein Einsatz mit `uhr_gesperrt = 1` — jemand hat ihn im
Web bearbeitet — wird ganz übergangen, und Phasen werden nur ersetzt, wenn der
Upload mindestens so viele bringt wie gespeichert sind. Offen blieb der
**unbearbeitete** Einsatz von vor drei Wochen.

**Die Regel.** Ein **bestehender** Datensatz lässt sich nur
`INGEST_ERSETZFENSTER_H` = **72 Stunden** ab dem Augenblick, in dem der
Server ihn **zum ersten Mal gesehen** hat — `created_at` —, von seinem Gerät
verändern. Nicht ab dem gesendeten `started_at` — den bestimmt der Absender —,
und seit der Nachbesserung vom 07.09.2026 auch nicht mehr ab dem gespeicherten:
Das stammt beim Anlegen ebenfalls vom Gerät, und eine Uhr mit falsch
gestellter Zeit legte ihren Einsatz mit einem Datum von vor Jahren an — das
Fenster war im selben Augenblick zu, der **laufende** Einsatz verlor Punkte
und Phasen, und weil `next_seq` weiterwanderte, löschte die Uhr sie als
quittiert (Gegenprüfung des Web-Teils, Funde 2 und 5). Die Nachbesserung
rechnete zunächst das Spätere aus `started_at` und `created_at`, „Zukunft
zählt nicht" — und die zweite Gegenprüfung zeigte, dass „Zukunft" je Paket
gegen jetzt gerechnet wurde: Ein `started_at`, das beim Anlegen 99 Stunden
vorn lag, wurde zum Anker, sobald die Zeit es eingeholt hatte, und das längst
geschlossene Fenster ging zu einem gerätebestimmten Zeitpunkt noch einmal
72 Stunden auf. Auf den Augenblick des Anlegens angewendet ist ein
`started_at` später als `created_at` immer Zukunft, und das Spätere aus beiden
ist immer `created_at` — also steht es so im Code. `started_at` dient nur als
Rückfall, solange die Migration `2026_09_07_rest_segments_created_at` nicht
gelaufen ist, und ein Anker wird nie später als jetzt angesetzt. `rest_segments`
trägt `created_at` seit dieser Migration; die vorhandenen Zeilen bekommen ihr
`started_at`, nicht die Migrationszeit — gekappt auf den Bereich der Spalte
(TIMESTAMP, ab 1970-01-01 00:00:01) und auf höchstens die Migrationszeit, denn
`started_at` ist DATETIME und nimmt jedes Jahr an. Die Migration läuft in drei
für sich wiederholbaren Schritten (Spalte NULL anlegen, füllen wo NULL, dann
NOT NULL) und gilt erst als erledigt, wenn alle drei stehen: Die erste Fassung
scheiterte an einem einzigen Segment mit `started_at` 1970-01-01 00:00:00
nach dem ALTER, und der nächste Klick hätte sie als „nicht nötig" verbucht —
mit der Migrationszeit als Anker an jedem alten Segment.

Danach: `ok` **ohne** Metadaten-Upsert, ohne Phasen- und Reanimationsersatz,
**ohne Anhängen von Punkten** und **ohne Fortschreiben des Diensttags**
(bis zur Nachbesserung schrieb ein Paket mit `started_at` 2001 und `ended_at`
2097 Beginn und Ende des Diensttags um — der Einsatz selbst blieb, Fund 1), und
ohne dass ein Diensttag im Papierkorb einen leeren Nachfolger bekommt (Fund 4).
Benannt wird alles über `kept_phases`, `kept_resus`, `kept_points` und —
für Ende, `final`, Strecke und Anstieg — `kept_meta` (JSON-Vertrag 5; ohne
das Feld sah ein spätes Abschlusspaket wie ein Erfolg aus, und der Einsatz
blieb für immer „läuft noch", Fund 3). Kein Fehler — die Uhr wiederholte sonst
endlos —, und `next_seq` wandert weiter, damit sie aufhört zu senden.

**Warum die Punkte anders behandelt werden als bei `uhr_gesperrt`.** Dort wird
weiter angehängt: Der Inhalt ist bearbeitet, die Spur nicht, und Anhängen ist
unkritisch. Hier ist der **Absender** der Unsichere — ein Finder schriebe
sonst seine eigene Fahrt in die Spur eines drei Wochen alten Einsatzes.

**Warum 72 und nicht 48 oder 7 Tage** (F-SP-8, 06.09.2026): 48 h wären knapper,
aber ein Freitagsdienst, der erst am Montag synchronisiert, käme nicht mehr
nach. 7 Tage deckten Urlaub mit Uhr im Koffer — und gäben einem Finder eine
ganze Woche.

**Die Zeiten eines Pakets müssen zu seinem `day` passen.** Beides kommt aus
derselben Quelle, und bis zur Wiederaufnahme der zweiten Gegenprüfung hat
niemand nachgesehen, ob sie einander widersprechen: Ein Paket mit `day`
2026-08-09 und `started_at` 2001-01-01 lief durch, der Einsatz stand mit
96 Jahren Dauer in der Datenbank, und der Zeitraum des Diensttags war darauf
gezogen. `pruef_zeit_zum_tag()` und `pruef_ende_nach_beginn()` (beide in
`validate_lib.php`, also auf dem gemeinsamen Weg) prüfen das jetzt.

**Verworfen wird der Wert, nicht der Upload.** Das Paket kommt an, der Einsatz
entsteht, er ist sichtbar und löschbar — nur für das **Fortschreiben des
Diensttags** wird ein Zeitpunkt, der die Prüfung nicht besteht, nicht
verwendet; er steht als `rejected` in der Antwort. Eine erste Fassung hat das
Paket mit `400` abgewiesen, und das war falsch: Sie hätte die falsch gestellte
Uhr ausgesperrt — genau die, die Fund 2 der ersten Gegenprüfung wieder
hereingeholt hat. Ein Gerät, dessen Kalender nach einer Tiefentladung auf 1970
steht, muss seine Daten loswerden können. Dass ein **Ende vor dem Beginn**
liegt, hatte ebenfalls niemand gefragt: `dt_zeitraum_fortschreiben()` zog den
Diensttag daraufhin in beide Richtungen auf, mit Werten, die es nie gab.

Das Fenster ist **sehr** weit — von Mitternacht des Vortags bis zum Ende des
31. Tages danach —, und das mit Absicht. Vier Dinge zwingen dazu: der
Zeitzonenversatz zwischen Ortsdatum und UTC (ein voller Tag in beide
Richtungen zwischen UTC−12 und UTC+14); ein Dienst über Mitternacht; vor allem
aber, dass in der **Handy-App jedes Paket eines Dienstes den Tag des
Dienstbeginns trägt**, nicht seinen eigenen (`Dienstklammer.kt`: das
Ruhesegment bekommt `dienst.tag`); und dass ein Dienst **keine Höchstdauer**
hat, weder in der App noch auf dem Server — wer das Beenden vergisst, hat
Pakete, deren Zeiten Tage nach ihrem `day` liegen. Sie müssen ankommen: Der
Datenfehler ist der vergessene Dienst, nicht das Paket. Die Prüfung wehrt ab,
worum es geht — Jahre und Jahrzehnte —, und den feinen Schutz leistet nicht
sie, sondern das Ersetzfenster.

**Neue Datensätze werden immer angenommen.** Sie sind sichtbar und löschbar und
überschreiben nichts — **auch nicht den Zeitraum eines älteren Diensttags**:
Ein Paket mit neuem `client_ref` hat kein Fenster, wird aber über `day` oder
`day_ref` auf den alten Tag aufgelöst und schrieb dessen Beginn und Ende
genauso um wie Fund 1 (zweite Gegenprüfung, Wiederaufnahme). Der Zeitraum
eines Diensttags wird deshalb nur fortgeschrieben, solange an ihm noch
gearbeitet wird: Anker ist das **jüngste `created_at` der übrigen Datensätze
des Tages** — Serverzeit, wie beim Fenster selbst. Der gerade angelegte zählt
nicht mit, sonst wäre jeder Tag offen, an dem eben ein Paket ankam. Ein Tag
ohne andere Datensätze ist frisch und offen.

> **Die erste Fassung dieser Regel fragte den Tag nach *seinen* Zeiten** — und
> die kommen vom Absender. Ein Dienst, der später als 72 Stunden nach seinem
> Datum hochgeladen wurde (Uhr lange ohne Netz), bekam damit nie ein
> `ended_at`: Sein Diensttag entstand in diesem Augenblick, galt aber nach
> seinem Datum als längst geschlossen. Das war derselbe Fehler eine Ebene
> höher als Fund 2 — gemessen an der eigenen Probe, nicht vermutet, und der
> Grund, warum der Anker jetzt am Anlegen hängt. `days` trägt kein
> `created_at`; die Datensätze des Tages sind der nächste ehrliche Ersatz
> (Backlog Nr. 158).

Und ein
**bestehender** Datensatz, dessen Tag inzwischen im Papierkorb liegt, wandert
innerhalb des Fensters auf den neu bestimmten Tag (bis dahin entstand ein
leerer Tag, und der Datensatz blieb am gelöschten hängen — Backlog Nr. 33
im offenen Fenster; außerhalb wird gar kein Tag bestimmt). Der Weg gegen
eine verlorene Uhr bleibt das **Trennen** des Geräts (Handbuch 10); das
Fenster begrenzt nur, was bis dahin geschehen kann.

Nachweis: `tools/proben/ingest/` Teil 9 — **1 Paket angenommen, 1 abgewiesen**,
dazu die Gegenprobe, dass ein neuer Einsatz weiterhin entsteht, und seit den
Nachbesserungen **neun Erwartungen der Gegenprüfungen** (Diensttag bleibt,
Abschlusspaket genannt, falsch gestellte Uhr nimmt weiter an, Zukunft
schließt, kein leerer Tag, Ruhesegment nennt beides; vorgehende Uhr öffnet
nicht erneut; neuer `client_ref` lässt den Tageszeitraum stehen; Papierkorb
im offenen Fenster; Zeiten passen nicht zum Tag; nachgelieferter Dienst
bekommt Beginn und Ende; vergessener Dienst kommt an; 40 Tage danach nicht
mehr; Ende vor Beginn abgewiesen): **62 Erwartungen, 0 nicht erfüllt** — am
Stand vor der ersten Nachbesserung sind sieben davon rot, am Stand vor der
Wiederaufnahme zwei, am Stand vor der Neufassung der Tagesregel vier
(zweimal der abgewiesene Widerspruch, zweimal der nachgelieferte Dienst ohne
Ende); gegen den Stand `448ce9f`, also vor allen vier Nachbesserungen dieser
Runde, sind es fünf. Dieselbe Stufe
hat die Zeitstempel der ganzen Probe auf `time()` umgestellt: Sie standen auf
festen März-Daten, und damit prüfte die halbe Probe zweite Pakete an
Datensätzen, die das Fenster längst verlassen hatten — zehn Erwartungen
kippten, keine davon zu Recht.

### 4.99b Bedrohungsmodell der Kopplung (ab Web 13.0.0, S5)

Die Kopplung ist die eine Stelle, an der ein fremdes Gerät in ein fremdes
Konto geraten kann. Sie war es vor S5 und ist es danach — nur ist der Weg
seit Web 13.0.0 umgedreht, und mit ihm sind es die Angriffe: Bis 12.9.4
erzeugte das Web einen Code und die Uhr tippte ihn, also war der Code das
Geheimnis auf dem Weg vom Bildschirm zum Handgelenk. Jetzt zeigt das Gerät
den Code, ein Mensch gibt ihn im Browser ein, und das Gerät hat das letzte
Wort. Der Code weist damit **nichts mehr aus** (E-S5-03): Wer ihn abliest,
kann am Gerät nichts auslösen, weil sich `status` und `bestaetigen` mit
`X-Device-Id` und `X-Api-Key` ausweisen und nicht mit ihm.

**Zwei Angriffsflächen, zwei Tore** (E-S5-05). Ein fremdes Gerät soll nicht in
mein Konto („gib mal AB3 K7Q ein"), und mein Gerät soll nicht in ein fremdes
Konto (Code vom Handgelenk abgelesen und schneller eingegeben). Gegen das
erste steht die Bestätigungsseite im Web, die zeigt, **was** da koppeln will;
gegen das zweite die Rückbestätigung am Gerät, die zeigt, **wessen** Konto es
wäre. Kein Tor allein trägt: Die Seite sieht nur, wer eingibt, das Gerät nur,
wer bestätigt.

**Schwebende Zugangsdaten statt schwebender Geräte** (E-R49-2). `start`
liefert Kennung und Schlüssel sofort mit — aber in `pair_sessions`, nicht in
`devices`. Bis zum Ja gibt es das Gerät nicht, und `ingest.php` weist die
Daten ab. Das ist der Grund, warum der Schlüssel schon im ersten Schritt über
die Leitung darf: Er ist ohne Bestätigung wertlos, und die Bestätigung
braucht ein Konto.

| Nr. | Angriff | Tor / Gegenmittel | Was bleibt |
|---|---|---|---|
| 1 | **Fremdes Gerät im eigenen Konto** — jemand bewegt eine Kontoinhaberin dazu, einen Code einzugeben, den er ihr nennt | Die Eingabe koppelt nichts, sie **sucht** erst (`einstellungen.php`, Aktion `koppeln_pruefen`): Die Karte „Dieses Gerät koppeln?" zeigt Art, Modell und gekürzte Kennung, und erst ein zweiter Knopf bindet die Sitzung ans Konto. Danach die Kopplungsmail beim Ja (`pair.php` 318–343) und die Plakette „neu" sieben Tage lang (`GERAETE_NEU_TAGE`, `db.php` 686) | Wer ein Gerät zeigt, das dem Opfer plausibel erscheint, kommt durch — das ist Social Engineering, keine Protokolllücke. Das Gerät ist danach löschbar, und was es hochgeladen hat, trägt seine Kennung |
| 2 | **Eigenes Gerät im fremden Konto** — Code vom Handgelenk abgelesen und schneller eingegeben als die Trägerin | Die Rückbestätigung am Gerät. `status` liefert im Zustand `beansprucht` die **maskierte** Adresse des beanspruchenden Kontos (`pair.php` 226–228, `email_maskieren()` in `db.php` 504–511); das Gerät fragt damit, und eine fremde Adresse fällt auf | Wer eine Adresse mit denselben zwei Anfangszeichen **und** derselben Domain hat, gewinnt. Dagegen hülfe nur die volle Adresse, und die will R36/E-R49-4 nicht auf eine Uhr schreiben. **Bis Paket C steht dieses Tor nur auf der Serverseite** — der Server sagt, wer beansprucht hat; die Frage stellt erst die neue Uhr-Fassung |
| 3 | **Code-Raum füllen und auf einen Treffer hoffen** | Drei Bremsen greifen ineinander: die Obergrenze offener Sitzungen (`PAIR_SITZUNGEN_MAX` = 1000, `db.php` 488; gezählt per SQL über unverfallene Zeilen, `kopplung_lib.php` 39–43, geprüft in `pair.php` 140–142), der Topf `pair_start` je Adresse (20/600 s, `ratelimit_lib.php` 79) und der Topf `pair_code` an der Eingabe im Web, **je Konto und je Adresse** (10/600 s, `ratelimit_lib.php` 80; gezählt in `einstellungen.php` bei „Code nicht gefunden") | Sechs Zeichen aus 32 sind 1,07 Mrd. Möglichkeiten; höchstens 1000 davon sind gleichzeitig belegt, und findbar sind nur **unbeanspruchte, unverfallene** Sitzungen (`pair_sitzung_nach_code()`, `kopplung_lib.php` 135–145). Das sind ≤ 9,3 · 10⁻⁷ je Versuch — und ein Treffer läuft noch in Angriff 2, also in die Rückbestätigung |
| 4 | **Verstopfen** — die Obergrenze mit eigenen Sitzungen füllen, damit niemand mehr koppeln kann | `pair_start` macht daraus 50 Adressen je zehn Minuten; die Obergrenze zählt **nur unverfallene** Zeilen, ein Vorrat toter Sitzungen bringt also nichts (E-S5-14). Umgekehrt räumt `start` bewusst **nichts** vorab auf — sonst ließe sich der Server mit jeder Anfrage zum Aufräumen bringen; das tut der Job `aufraeumen` | Ein großer Adressvorrat verhindert für die Dauer des Angriffs **Neukopplungen**. Er berührt den laufenden Betrieb gekoppelter Geräte nicht: `ingest.php` kennt `pair_sessions` gar nicht |
| 5 | **Rechenlast** — den Server mit Anfragen beschäftigen, die teuer sind | Seit Web 13.0.0 ist an diesem Pfad **nichts mehr teuer**: Geräte- und Sitzungsschlüssel liegen als SHA-256 (`geraet_schluessel_hash()`, `db.php` 575), nicht mehr als bcrypt. Eine `status`-Abfrage kostet einen indizierten `SELECT` und einen Hashvergleich, ein `start` einen `INSERT`. Was übrigbleibt, ist die **Mindestdauer** von 0,35 s, die `rate_gleiche_dauer()` (`ratelimit_lib.php` 260–265) jedem abgewiesenen Aufruf auferlegt — sie bindet einen PHP-Arbeitsprozess. Begrenzt wird sie von denselben Töpfen wie Angriff 3 und 4 | Der Angriff hat mit E-S5-42 seinen Gegenstand verloren, und das ist der Grund, warum der Abfragetakt von fünf Sekunden eine **Bedienzahl** sein darf und keine Lastzahl: 120 Abfragen je Sitzung kosten Mikrosekunden, unter bcrypt wären es rund 27 s gewesen |
| 6 | **Schwebende Zugangsdaten mitnehmen** — ein Gerät holt sich mit `start` einen Schlüssel und bestätigt nie | Ohne `devices`-Zeile weist `ingest.php` mit `401` ab (es sucht ausschließlich in `devices`, `ingest.php` 74–81). Die Sitzung verfällt nach zehn Minuten (eine Frist für alles, `pair_frist_sql()` in `kopplung_lib.php` 33–36), und gespeichert ist ohnehin nur der Hash (`schema.sql` 439) | Keiner. Der Klartext geht genau einmal über die Leitung — in der Antwort auf `start` (`pair.php` 181) — und steht in keinem Protokoll: Die `catch`-Zweige loggen die Ausnahme, nie den Rumpf |
| 7 | **Die maskierte Adresse ablesen** — ein Gerätehalter erfährt etwas über die Person, die seinen Code eingegeben hat | Maskiert werden alle Zeichen des lokalen Teils bis auf die ersten zwei; die Domain bleibt voll, weil sie die Trägerin ihr Konto erkennen lässt (`db.php` 486–511). Der Wert wird nur im Dialog gezeigt und **nirgends gespeichert** | Zwei Zeichen und eine Domain. Wer sie sieht, hat die Person gerade zur Eingabe bewegt — er kennt sie also ohnehin (das ist Angriff 1); ein neuer Personenbezug entsteht nicht |
| 8 | **Zeitseitenkanal** — aus der Antwortdauer ablesen, welche Gerätekennungen es gibt | Jeder Zweig, der etwas über Fremdes sagen könnte, endet über `abweisen()` (`pair.php` 97–105) und damit über `rate_gleiche_dauer()`. Der unbekannte Zweig rechnet gegen `GERAET_VERGLEICHSWERT` dieselben Schritte wie der bekannte (`pair.php` 355–364), und die Rümpfe für „Kennung unbekannt" und „Schlüssel falsch" sind byteweise gleich — beide `{"error":"auth"}`. `410` und `409` kommen **ohne** Verzögerung (`antworten()`, `pair.php` 107–113): Sie setzen die richtige Kennung **und** den richtigen Schlüssel voraus und sagen einem Fremden nichts | Wie an `ingest.php` und `auth_salt.php`. Die Kopplungsprobe misst beide 401-Zweige nebeneinander; siehe „Die Antwortzeit als Auskunft" weiter oben |
| 9 | **CSRF auf den Web-Aktionen** — eine fremde Seite lässt den angemeldeten Browser einen Code beanspruchen oder eine Sitzung abbrechen | `csrf_field()` an allen drei Formularen der Karte „Gerät koppeln" (`einstellungen.php`, Aktionen `koppeln_pruefen`, `koppeln_bestaetigen`, `koppeln_abbrechen`), wie an jeder Formularaktion der Anwendung | Der Nachlade-Endpunkt hat bewusst keine Prüfung — siehe Nr. 11 |
| 10 | **Verlorene Antwort auf `bestaetigen`** — das Ja kommt an, die Antwort nicht, das Gerät wiederholt | Idempotenz über `devices` (E-S5-15): Ein wiederholtes Ja und ein `status` mit den Zugangsdaten eines bereits angelegten Geräts bekommen `200 {"ok":true}` bzw. `{"zustand":"gekoppelt"}` (`pair.php` 366–376). Auch das gleichzeitige zweite Ja endet dort: Es wartet an `SELECT … FOR UPDATE`, findet die Sitzung nicht mehr und fällt in den Geräte-Zweig (`pair.php` 262–271, 303, 346) | Ohne diese Antwort hinge die Kopplung an einem einzigen Funkpaket. Ein `bestaetigen nein` auf ein fertiges Gerät ist deshalb ein **Nichtstun** (E-S5-48): Ein Nein, das ein Gerät löschte, wäre ein Trennen ohne Trennen-Mail |
| 11 | **Der Nachlade-Endpunkt als neue Tür** — `api/kopplung_stand.php` sagt, ob eine Kopplung fertig ist (E-S5-53) | Er **nimmt keine Eingabe**. Welche Sitzung gemeint ist, steht in der Browsersitzung (`$_SESSION['pair_warten']`, gesetzt beim Beanspruchen), nicht in einem Parameter, den jemand mit einer fremden Kennung füllen könnte; die Kontokennung steht zusätzlich in der Bedingung, damit eine Erinnerung aus einer fremden Sitzung — möglich nach einem Kontowechsel im selben Browser — nichts beantwortet. Er verlangt Anmeldung, ist GET-only und ändert nichts | Kein CSRF-Token und kein eigener Ratenschutz, beides begründet: Er liest, und er hat nichts zu erraten. Was er verrät, hat der Aufrufer eine Minute zuvor selbst angestoßen |
| 12 | **Ein Reiter, der ewig fragt** — das Nachladeskript als selbstgemachte Last | `assets/kopplung.js` hört von selbst auf: bei Erfolg, bei Ablehnung am Gerät, mit dem Ende der Frist und nach drei Fehlversuchen in Folge; es ruht, solange der Reiter im Hintergrund liegt (`document.hidden`), und es lebt nie länger als die zehn Minuten der Sitzung | Ohne JavaScript bleibt der Weg vollständig — die Karte sagt auch dann, was am Gerät zu tun ist; das Skript nimmt nur den Handgriff „neu laden" ab |

**Was das Modell nicht abdeckt.** Ein Angreifer, der den Browser der
Kontoinhaberin schon in der Hand hat, braucht keine Kopplung — er ist
angemeldet. Ein Angreifer, der `config.php` und die Datenbank hat, ebenso
wenig. Und der Fall „jemand steht neben der Uhr und drückt selbst Ja" ist ein
körperlicher Zugriff auf das Gerät; dagegen steht in dieser Anwendung nichts,
und das ist eine bewusste Grenze.

**Ein Topf, drei Verbraucher — und keiner leert ihn.** `pair` bremst nicht nur
`pair.php`, sondern auch das Token von `jobs.php` und den GPX-Abruf in
`gpx.php` (sieben Zählstellen dort). Wer die Zahlen in `RATE_GRENZEN` ändert,
ändert alle drei.

Bis Web 13.1.0 rief ein gelungenes `trennen` `rate_erfolg('pair')` und leerte
damit den Zähler dieser Adresse **für alle drei** — wer Freigabelinks
durchprobierte, holte sich also mit einem getrennten eigenen Gerät zehn
frische Versuche, und neu koppeln kostet einen Handgriff. Der Aufruf ist mit
13.1.1 ersatzlos entfallen: An diesem Endpunkt gibt es seit 13.0.0 nichts mehr
zu vertippen — den Code tippt ein Mensch im Web, und dort hat er seinen
eigenen Topf `pair_code` mit eigenem `rate_erfolg`. Was hier ankommt, sind
Kopfzeilen einer Maschine.

Dieselbe Überlegung galt schon für `status` und `bestaetigen` (E-S5-50): Ein
Erfolg dort darf den Zähler nicht leeren, sonst setzte ein Angreifer mit einer
eigenen, gültigen Sitzung alle fünf Sekunden zurück, während er daneben fremde
Kennungen durchprobiert.

**Wo dieselbe Frage sonst noch beantwortet wird:** „Die Antwortzeit als
Auskunft" (Antwortgleichheit der vier anmeldungsfreien Endpunkte),
„Geräte je Konto" (`MAX_GERAETE`, Kopplungsmail, Plakette „neu"), 4.99
(Ratenschutz, fester Vergleichswert, Sitzungsende) und der JSON-Vertrag,
Abschnitt 1a (die Antworten im Wortlaut).

### 4.99c Wartungsmodus (ab Web 13.2.0, S5 Paket W)

**Ein Schalter, der die Installation vorübergehend für alle außer der
Verwaltung schließt.** Er beantwortet den Unterschied zwischen „kaputt" und
„gleich wieder da": Während eines Updates antwortet die Anwendung sonst mit
**500** — alte und neue Dateien nebeneinander, neuer Code über einem alten
Schema. Mit dem Wartungsmodus antwortet sie mit **503**, und darauf haben Uhr
und Handy eine Antwort: „später unverändert erneut versuchen" (JSON-Vertrag
Abschnitt 5). Sie puffern und liefern nach. **Kein Client wurde dafür
geändert** (E-S5W-08).

| | |
|---|---|
| Zustand | Datei `server/wartung.lock`, JSON mit `seit` (ISO-UTC) und `von` (Anzeigename). **Keine Datenbank** — der Schalter wird gerade dann gebraucht, wenn sie umgebaut wird oder eine Migration auf halber Strecke steht |
| Tor | `wartung_tor()` in `wartung_lib.php`, gerufen aus `db.php` **hinter `json_out()` und vor jeder Verbindung**. Nicht in `auth_guard.php`: Dort liefen nur die Seiten durch — `ingest.php` und `pair.php` laden `db.php` direkt, und das sind die beiden, die die Daten der Uhr bringen. Der **Torwächter** (unten) steht dagegen genau dort, und aus dem umgekehrten Grund: Er braucht eine Verbindung |
| Antwort, Seiten | 503 mit einer schlichten HTML-Seite ohne `ui.php` (dessen Hülle zieht über `ui_favicon()`/`logo_stamm()` die Datenbank herein). Das Stylesheet ist verlinkt — statisch. Kein Skript |
| Antwort, Maschinen | 503 `{"error":"maintenance","meldung":"…"}`. JSON, wenn der Pfad `/api/` enthält **oder** das Skript in `JSON_SKRIPTE_AUSSERHALB_API` steht — `ingest.php`, `pair.php`, `auth_salt.php`, `jobs.php`. Die vier liegen nicht unter `/api/` und brauchen trotzdem JSON. **Für den Wartungsmodus zählen nur die ersten beiden**, weil die anderen zwei in `WARTUNG_AUSNAHMEN` stehen und das Tor bei ihnen vorher umkehrt; die Liste ist in P5a/AP9 für die **Überlast** gewachsen, die keine Ausnahmen kennt (Abschnitt 5e) |
| Kopfzeilen | `Retry-After: 300` (E-S5W-12), `Cache-Control: no-store`. Kein `Set-Cookie`: Das Tor greift vor `session_start()` |
| Ausnahmen | **sechzehn** Skripte (`WARTUNG_AUSNAHMEN` in `wartung_lib.php` — dort steht zu jedem der Grund), verglichen am **Dateinamen** (`basename($_SERVER['SCRIPT_NAME'])`, nicht am Pfad — `login.php` lädt `db.php` als Erstes): `betrieb_status.php`, `betrieb_sicherheit.php`, `betrieb_statistik.php`, `betrieb_updates.php`, `betrieb_jobs.php`, `betrieb_server.php`, `betrieb_schluesselblatt.php`, `admin_komplettsicherung.php`, `admin_sicherungsziele.php`, `update.php`, `wiederherstellen.php`, `jobs.php`, `login.php`, `auth_salt.php`, `logout.php`, `install.php`. **Die Betriebsseiten stehen seit S8/AP2 bzw. AP4 mit dabei** — ohne sie sperrte sich der Wartungsmodus selbst aus: Die Seite mit dem Ausschalter antwortete 503 (F-S8-P-04). `betrieb_schluesselblatt.php` kam mit S10/AP3 dazu: Die Lage, in der man das Blatt braucht, ist genau eine Wartungslage. `betrieb_sicherheit.php` mit P5a/AP8, aus demselben Grund und schärfer: Dort steht der Knopf, mit dem sich eine Sperre aufheben lässt — wer im Wartungsmodus jemanden wieder hereinlassen muss, braucht genau diese Seite. **Komplett-Backup und Backup-Ziele seit Web 21.1.0** (P5c/AP9, E-P5c-134): Schloss der Torwächter, führte der Knopf „Komplett-Backup" der Seite Updates in die Sperre, und die Vorbedingung der FTP-Migration nannte die gesperrten Backup-Ziele als Weg. **Das Tor fragt nie nach der Rolle** — bis Web 21.1.0 sagten Karte und Handbuch „für alle außer Verwaltung und Betrieb" (F-P5c-158); jede Seite unter Verwaltung antwortet 503. **Die Zahl stand hier bis Web 20.1.0 auf „elf“ und die Aufzählung ließ `auth_salt.php` aus** — beide hinkten seit Web 19.1.2 (Nr. 171) hinterher; maßgeblich ist immer die Konstante, nicht dieser Satz. Alles unter `assets/` läuft ohnehin nicht durch PHP; die Kommandozeile ist nie getort |
| Schalten | `betrieb_updates.php`, Karte „Wartungsmodus", POST mit CSRF, nur BetreiberIn (S8/AP1). Idempotent: Ein zweites Einschalten überschreibt `seit` und `von` nicht. Scheitert das Schreiben oder Löschen, sagt die Seite es **mit Pfad** |
| Sichtbarkeit | Es gibt kein automatisches Ausschalten (E-S5W-05). Ein oranger Balken auf `betrieb_updates.php` und `login.php` nennt Zeitpunkt und Konto — das sind die beiden einzigen Seiten, auf denen ein stehengebliebener Wartungsmodus überhaupt auffallen kann |
| Jobs | laufen weiter (E-S5W-11). `jobs.php` mit Token ist Ausnahme, damit das Komplett-Backup **während** der Wartung läuft — genau dann ist es konsistent. Der Huckepack-Weg aus `auth_guard.php` läuft auf `betrieb_updates.php` mit, und zwar **vor** `require_betreiberin()` und damit vor jeder Migration desselben Aufrufs. Wer Ruhe braucht: `jobs.php --pause` |
| Anmeldung | Der Passwortvergleich ist unverändert. **Nach** einem Erfolg entscheidet die Rolle: Admin und BetreiberIn weiter, alles andere sofort wieder abgemeldet und auf die Wartungsseite (E-S5W-09). Die Ratenschutz-Zähler werden trotzdem geleert — das Passwort war richtig |
| Logo | Die Wartungsseite kann `logo_stamm()` nicht rufen (Datenbank) und **wirft eine Münze** zwischen den beiden Standardlogos, wie `logo_aufloesen('wechselnd')`. Eine Installation mit eigenem Logo sieht während der Wartung eines der beiden Standardlogos |

**Die Datei ist der Schalter, nicht ihr Inhalt.** Liegt sie da, ist aber
unlesbar oder kein gültiges JSON, gilt die Wartung trotzdem; der Balken sagt
„seit unbekannt". Andersherum wäre es falsch — ein Tippfehler im Inhalt darf
keine Installation öffnen, die jemand ausdrücklich geschlossen hat.

**Zwei Einträge, die zusammengehören:** `server/wartung.lock` steht in
`.gitignore` **und** in der Ausnahmeliste des FTPS-Schritts in
`.github/workflows/ausliefern-lauf.yml` (bis Kette II/AP5: zweimal in
`auslieferung.yml`; bis Web 20.3.0: `deploy.yml`). Ohne den ersten schlösse ein Checkout jede
Installation; ohne den zweiten löschte der Push die Datei — mitten im Update,
für das sie da ist. Dasselbe Muster wie `config.php`, `install.php`,
`install.lock`, `sicherungen/`, `apk/` — und seit Web 20.13.0 `ueberlast.json` (Abschnitt 5e).

#### Der Torwächter (ab Web 20.6.0, P5a/AP3, E-P5a-20; R40 (4), Nr. 54)

**Seit Web 20.6.0 schaltet nicht mehr nur ein Mensch.** `wartung.lock` trägt
im Feld `von` jetzt auch zwei Herkünfte statt eines Namens:

| `von` | wer | wann |
|---|---|---|
| ein Name oder eine Adresse | ein Mensch | Karte „Wartungsmodus" auf Betrieb → Updates |
| `kette` | die Auslieferungskette | vor dem FTPS-Sync (P5a/AP1, E-P5a-12) |
| `torwaechter` | die Anwendung selbst | erste angemeldete Anfrage nach einem Deploy mit ausstehender Migration |

**Die Frage stellt `auth_guard.php`, nicht `db.php`.** Das Tor in `db.php` ist
ausdrücklich *ohne* Datenbank gebaut — es muss antworten, während die
Datenbank umgebaut wird. Eine Abfrage dort nähme ihm genau die Eigenschaft,
um derentwillen es dort steht. `migrationen_ausstehend()` braucht eine
Verbindung und steht deshalb eine Ebene höher.

**Der Zwischenspeicher hängt am Katalog-Hash.** Ein voller
`migrationen_lauf($pdo, false)` geht 46 Katalogeinträge durch und stellt je
Eintrag mindestens eine `information_schema`-Abfrage — das ist der Preis
einer Statusseite, nicht der Preis *jeder* Seite. In `app_state` stehen
deshalb zwei Zeilen: `migration_tor_hash` (SHA-256 über die **Kennungen** des
Katalogs — nicht über den Katalog selbst, der enthält Closures und lässt sich
nicht serialisieren) und `migration_tor_offen`. Stimmt der Hash, gilt die
gespeicherte Antwort.

**Drei Stellen schreiben ihn fort, und alle drei müssen es:**

1. `migrationen_lauf(…, true)` nach einem ausgeführten Lauf — der Hash ändert
   sich dabei *nicht*, und ohne diese Zeile schlösse der Torwächter die
   Installation gleich wieder zu.
2. `wiederherstellen.php` nach dem Einspielen (**Nr. 54**) — ein eingespielter
   Dump bringt das Register der *Quellinstallation* mit, und der Hash dieser
   Installation passt trotzdem. Er wird **verworfen**, nicht neu gerechnet:
   Das Rechnen kostet, und die nächste Anfrage tut es ohnehin.
3. Der Deploy selbst, aber nur mittelbar — er ändert den Katalog, also den
   Hash, also fällt der Zwischenspeicher von selbst.

**Bei einem Fehler bleibt die Installation offen.** Fehlt `app_state`,
antwortet die Datenbank nicht, wirft eine `skip`-Prüfung — dann liefert
`migrationen_ausstehend()` `false`. Der Torwächter darf keine Installation
schließen, weil er selbst nicht messen konnte; dieselbe Richtung wie beim
Ratenschutz.

**Was er offen lässt, und das steht auch im Code:** `ingest.php` und
`pair.php` laden `auth_guard.php` nicht. Bis zur ersten angemeldeten Anfrage
bekommen die Geräte also weiter 500 statt 503. **Verloren geht dabei nichts**
— 5xx ist 5xx, sie puffern und liefern nach —, und für die
Auslieferungskette ist das Fenster null: Sie lässt den Wartungsmodus bei
ausstehender Migration von sich aus an. Für den Weg von Hand schließt es die
erste angemeldete Anfrage.

**Aus geht er nie von selbst** (R66). Betrieb → Updates zeigt den Grund und
bietet nach dem Lauf einen zweiten „Wartung beenden" genau dort an, wo man
gerade geklickt hat — drei Bedingungen: der Torwächter muss geschaltet haben,
es darf nichts mehr ausstehen, und die Wartung muss noch stehen.

**Nicht Umfang:** eine eigene Wartungsmeldung auf Uhr und Handy ist
Backlog-Kandidat.

**Nachweis:** `php tools/proben/wartung/probe.php` — **69 Erwartungen** (gezählt 25.09.2026, Web 21.1.0), beide
Richtungen (zu wenig gesperrt / zu viel gesperrt), einschließlich der drei
Regeln aus E-S5W-09 am Code und seit Web 20.6.0 **Teil 7**: der Torwächter
schließt, nennt den Grund, gibt `ingest.php` sein JSON-503, lässt Betrieb →
Updates offen, bietet „Wartung beenden" und öffnet wieder — dazu Nr. 54 in
der Richtung, die weh tut (Erwartung 32 zeigt, dass der Zwischenspeicher nach
einer Wiederherstellung *lügt*, Erwartung 33, dass das Zurücksetzen ihn
wieder sehend macht). Betriebsablauf: Abschnitt 7.


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
Kopf von `tools/erzeugen/uhr-bilder.sh`.

Bilder erzeugen: `tools/erzeugen/uhr-bilder.sh`. Die passenden Jungle-Zeilen:
`tools/uhr-pruefstand/geraeteklassen.py --bloecke`.

### 5.1c Was die Uhr mit einer Absage anfängt (ab Uhr 3.1.0, Backlog Nr. 159)

Bis Uhr 3.0.2 kannte `Uploader.onResponse()` zwei Fälle: Erfolg, und alles
andere. „Alles andere" hieß `lastError` setzen und beim nächsten Anlass erneut
versuchen — für einen Netzfehler richtig, für eine Absage falsch. Ein Paket,
das der Server nie annimmt, stand vorn in `_findJob()` und blieb dort; alles
dahinter kam nicht mehr an. Und weil `Pair.start()` das Trennen verweigert,
solange `Model.backlogCount() > 0`, ließ sich die Uhr danach auch nicht mehr
neu koppeln. Es blieb das Löschen der App, mit allem, was sie trug.

Seither unterscheidet die Antwortbehandlung drei Fälle:

| Antwort | Bedeutung | Was die Uhr tut |
|---|---|---|
| `401`, `403` | Das **Gerät** ist abgemeldet: gelöscht, Schlüssel ungültig, oder auf inaktiv gestellt | `Uploader.abgemeldet` wird gesetzt, `syncAll()` kehrt sofort zurück. **Kein Paket wird geparkt** — mit ihnen ist nichts verkehrt, sie werden nach einer neuen Kopplung gebraucht. Die Sync-Seite nennt den Grund und den Weg zurück |
| `400` **mit** `{"error":…}` | **Dieses** Paket ist unbrauchbar | Marke `bad_<ref>` im Storage, `_next()` arbeitet weiter. Ein blankes `400` ohne Kennzeichen zählt nicht: Es kann von jedem Zwischenstück kommen, und ein gesundes Paket dafür zu parken wäre teurer als ein Versuch zuviel |
| alles Übrige | Störung | unverändert: `lastError`, später erneut |

**Ein geparktes Paket wird übersprungen, nicht entfernt.** Das ist der Punkt,
an dem die naheliegende Lösung Daten verliert: `Model.backlogCount()` entsorgt
Einträge, für die `Uploader.hasWork()` falsch liefert — wer ein geparktes
Paket darüber aus der Schlange nähme, ließe seine Spur als Waise im Speicher
zurück, rund 100 kB, die nichts mehr freigibt. `hasWork()` bleibt deshalb
wahr; `_findJob()`, `backlogCount()` und `allSynced()` fragen zusätzlich
`istGeparkt()`. Entfernt wird nur über `Uploader.verwerfen()`, und das räumt
`Track.purge()` und alle drei Marken mit.

Dass geparkte Pakete **nicht** im Rückstand zählen, ist kein Schönheitsfehler,
sondern der Ausweg: Sonst bliebe die Zahl für immer über null, `Pair.start()`
verweigerte das Trennen weiter, und die Sackgasse wäre dieselbe wie vorher.
Beim Trennen werden sie verworfen — sie gehören dem bisherigen Konto —, und
die Rückfrage sagt es vorher.

### 5.2 Neue Zielgeräte prüfen — `tools/uhr-pruefstand/eingabe-probe`

Bevor ein Gerät in `watch/manifest.xml` aufgenommen wird, muss gemessen sein,
welche Tasten überhaupt bei der App ankommen, welche Behaviors das System
daraus ableitet und ob die Langdruck-Erkennung dort funktioniert. Datenblätter
reichen dafür nicht: Auf der Venu 3s ist eine der drei Tasten systemseitig
belegt und für Connect-IQ-Apps unsichtbar — das steht in keiner Übersicht.

Dafür liegt unter `tools/uhr-pruefstand/eingabe-probe/` ein eigenständiges Connect-IQ-Projekt.
Es wird nie ausgeliefert, hat eine eigene UUID und keine Berechtigungen. Es
protokolliert jedes Eingabeereignis mit Millisekunden-Stempel auf Konsole und
Display und startet bei jedem Tastendruck einen 1000-ms-Timer — denselben
Mechanismus, den die App über `Const.LONG_PRESS_MS` für Langdrücke benutzt.
Steht `HALTE-TIMER` im Protokoll vor dem `KeyReleased`, sind Langdrücke
möglich; steht es danach oder gar nicht, sind sie es nicht.

Messfolge und Auswertung: `tools/uhr-pruefstand/eingabe-probe/LIESMICH.md`.
Ergebnisse gehören nach `Geraete-Eingabe.md`.

**Build:** VS Code + Monkey-C-Erweiterung + Connect-IQ-SDK + JDK;
Entwickler-Schlüssel via „Generate a Developer Key". Ziele `fenix6pro`, `fr945`
und `venu3s` (s. Abschnitt 5.1 und `Geraete-Eingabe.md`),
Debug-Build; Sideload: `.prg` nach `GARMIN/Apps/`. Server-Adresse, Geräte-ID und
API-Schlüssel sind **App-Einstellungen** (Garmin Connect). Seit Uhr 3.0.0 hat
die **Server-Adresse einen Vorgabewert** — `nadoku.gen-em.org`, die öffentliche
Installation (E-R49-8); Selbsthoster tragen dort ihre eigene Domain ein. Geräte-ID
und API-Schlüssel bleiben leer und sind der **Alt-Weg** für die Handanlage; im
Regelfall füllt sie die **Kopplung** (Uhr: Sync-Seite → START halten; das Gerät
zeigt einen Code, den ein Mensch im Web unter Einstellungen → Geräte einträgt).
Connect IQ bewahrt diese Einstellungen an der App-Kennung auf — sie überstehen
jedes Neukompilieren, gehen aber bei einem Wechsel der Kennung verloren; der
Simulator behält sie sogar über ein neues Kompilat hinweg (`pruefstand.sh
einstellungen-leeren`). `Const.APP_VERSION` bei Releases mitziehen (Anzeige
Sync-Seite). Die **App-Kennung im `manifest.xml`** ist seit Uhr 2.0.0 keine
Platzhalterzahl mehr, sondern die endgültige — sie wird nicht wieder gewechselt,
weil ein Wechsel für die Uhr eine andere App bedeutet und die Kopplung mitnimmt.

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
Prüfstand neu aufsetzt, muss die Adresse also erfragen. Seit dem 23.09.2026
holt das Skript zuerst je ein Archiv (`devices.tar`, `fonts.tar`) von dort —
eine Anfrage statt tausender, und die Anfragen waren die Zeit: rund 31 min für
Gerätedateien und Schriften in Lauf #253, 1 min über die Archive. Fehlt ein
Archiv, fällt es mit einer Warnung auf `wget -r` über die Einzeldateien
zurück; dafür braucht die Quelle eine eingeschaltete Verzeichnisauflistung.
Gepackt werden die Archive im Ordner `~/.Garmin/ConnectIQ` mit
`tar cf devices.tar Devices` und `tar cf fonts.tar Fonts`, **immer über den
ganzen Bestand** — sonst sieht `geraeteklassen.py` kein neues Gerät. Lässt
sich ein Archiv nicht entpacken oder liegt es eine Ebene zu tief, ist der
Lauf rot. **Wer Geräte nachlädt, packt beide Archive neu**; sonst holt die
Kette ohne Warnung den alten Stand.
`aufbau` holt nur die drei Zielgeräte — für Stufe I und
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

**Der Simulator und TLS** (Netzprobe, F-S5-11). Ob der Simulator einen
Server auf `127.0.0.1` erreicht, beantwortet `tools/uhr-pruefstand/netzprobe/`
mit einer Anfrage an `pair.php` (`GET` → 405). Gemessen am 03.09.2026, SDK
9.2.0, fenix6pro:

| Weg | Konsole der App | Zugriffsprotokoll |
|---|---|---|
| `http://127.0.0.1:8080` | **−1001** (`SECURE_CONNECTION_REQUIRED`) | **`[405]: GET /pair.php`** |
| `https://127.0.0.1:8443`, selbstsigniert | 404 | nichts (`tlsv1 alert unknown ca`) |
| `https://127.0.0.1:8443`, eigene CA im Systemspeicher | **405** | **`[405]: GET /pair.php`** |

**Die erste Zeile ist die unangenehme:** Über blankes HTTP lässt der
Simulator die Anfrage hinaus, der Server **führt sie aus**, und die App
bekommt `−1001`. Wer nur auf den Rücklaufcode sieht, hält den Weg für tot
und übersieht, dass die Gegenseite gehandelt hat — bei einem schreibenden
`POST` kein Schönheitsfehler. Die dritte Zeile ist das Rezept in
`tools/referenzdatensatz/einspielen/lokal_starten.sh`: eine eigene CA, ein
Serverzertifikat mit `subjectAltName=IP:127.0.0.1`, die CA per
`update-ca-certificates` im Systemspeicher. Sie entsteht auf der Maschine
und verlässt sie nicht.

Die Grenzen bleiben die des Simulators, unverändert: keine echte Hardware,
keine Systemgesten, kein Server. Ein Lauf zeigt, dass es startet und wie es
aussieht — nicht, dass es richtig ist. Anleitung:
`tools/uhr-pruefstand/LIESMICH.md`.

### 4.99d Betrieb: drei Seiten statt einer Wartungsseite (ab Web 15.1.0, S8/AP2)

**Die Wartungsseite war neun Blöcke auf einer Fläche** — Wartungsmodus,
Schlüsselableitung, Logo, Umgebung, Hintergrundjobs, Job-Auslöser, Einsätze
ohne Diensttag, Migrationsliste und der Balken darüber. Sie beantwortete vier
verschiedene Fragen an einem Ort (B-S8-03), und wer eine davon suchte, las
zuerst die anderen drei. S8 teilt sie nicht auf, sondern **löst sie auf**
(E-S8-05):

| bisher auf der Wartungsseite | jetzt |
|---|---|
| Wartungsmodus, Migrationen, Fassung | `betrieb_updates.php` (Betrieb → Updates) |
| Hintergrundjobs, Auslöser, Token | `betrieb_jobs.php` (Betrieb → Hintergrundjobs) |
| Speichergrenze, Warnschwellen, Ablage | `betrieb_server.php` (Betrieb → Servereinstellungen) |
| Schlüsselableitung, Umgebung | `betrieb_status.php` (seit Web 15.3.0) |
| Logo der Installation | `admin_installation.php` (S8/AP3 — bis dahin auf `update.php`) |
| Einsätze ohne Diensttag | ersatzlos entfallen (E-S8-17) |

Alle drei neuen Seiten beginnen mit `require_betreiberin()` (S8/AP1, R75) und
stehen in `WARTUNG_AUSNAHMEN` — **ohne das sperrt sich der Wartungsmodus selbst
aus**: Die Seite mit dem Ausschalter antwortete 503 (F-S8-P-04, gemessen).

**`update.php` bleibt übergangsweise stehen**, aus zwei Gründen und beide enden
bald: Der Notausgang `php update.php` auf der Kommandozeile läuft ohne Sitzung —
für den Fall, dass die Anmeldung selbst von einer Migration abhängt —, und die
Logo-Karte hätte bis AP3 sonst keinen Ort. Im Browser zeigt die Datei seither
nur noch einen Wegweiser auf die drei neuen Seiten. Ab AP3 ist der Web-Teil eine
Weiterleitung; die Adresse steht in zu vielen Lesezeichen, als dass ein 404 die
richtige Antwort wäre (Backlog Nr. 77).

#### Der Migrationskatalog ist eine eigene Datei

`migration_lib.php` trägt seit S8/AP2 den Katalog **und** den Lauf; `update.php`
und `betrieb_updates.php` rufen nur noch auf. Vorher stand beides in
`update.php`, und die Seite war damit die einzige Stelle des Projekts, an der
sich Darstellung und Datenmodell nicht trennen ließen.

| Funktion | Antwort |
|---|---|
| `migrationen_katalog()` | die Migrationen selbst, in Reihenfolge — **die einzige Stelle** |
| `migrationen_register(PDO)` | legt `schema_migrations` an, falls sie fehlt |
| `migrationen_lauf(PDO, bool $ausfuehren, array $forcieren)` | Vorschau (`false`) oder Lauf (`true`); liefert `results`, `offen`, `blockiert`, `gelaufen` |
| `migrationen_stand(PDO)` | Zahl, letzte Kennung und Datum der ausgeführten |
| `migrationen_inhalt_zaehlen()` / `…_text()` | der Migrationsschutz: Was ginge verloren? |

**Sechs Anzeigestatus je Zeile, und `offen` wird nicht aus ihnen abgeleitet.**
`ok` (bereits angewendet oder gerade gelaufen) · `todo` (steht an, oder nach
einem Abbruch nicht mehr versucht) · **`skip`** (nicht nötig, aber noch nicht
verbucht — nur in der Vorschau; seit Web 15.5.2, Backlog Nr. 149) · `stopp`
(destruktiv, Daten stehen darin) · `warn` (Zustand nicht feststellbar) ·
`fail` (gescheitert, die Kette hält an). `offen` zählt getrennt mit, weshalb
sich ein Status hinzufügen lässt, ohne die Zählung zu berühren — genau das
tut `skip`.

> **`skip` und `skipped` sind zwei verschiedene Dinge, und sie stehen acht
> Zeilen auseinander.** `skip` ist der **Anzeigestatus** einer Zeile und sagt
> „noch nicht verbucht"; `skipped` ist ein Wert der **Registerspalte**
> `schema_migrations.status` und sagt das Gegenteil: „verbucht, ausgeführt
> wurde nichts". Wer sie verwechselt, baut den Phantomzähler aus Nr. 149
> wieder ein.

**Wer einen Status hinzufügt, sucht seine Leser.** Es sind drei, und zwei
davon liegen in derselben Datei: `betrieb_updates.php` sortiert nach ihm in
die Listen *Ausstehend* und *Ausgeführt* **und** wählt daraus die Plakette —
letzteres über ein `match` mit `default`-Zweig, das einen unbekannten Status
still zu einem roten „Fehler" macht. `update.php` druckt ihn generisch. Ein
neuer Status, der nur in eine der beiden Stellen eingetragen wird, ist
schlimmer als keiner: Steht er nur im `match`, verschwindet die Zeile aus
beiden Karten; steht er nur im Filter, erscheint sie als gescheitert.

**Was hinter einem Fehler steht, wird nicht mehr verschwiegen.** Bis Web 15.0.0
brach der Lauf bei einem Fehler ab, und die Migrationen dahinter tauchten in der
Ausgabe gar nicht auf — die Karte zählte dann weniger Ausstehende, als es gab
(F-S8-P-05). Seither trägt jede von ihnen den Zustand `steht aus` mit dem Text
„NICHT MEHR VERSUCHT — der Lauf hat davor abgebrochen." Gemessen: vier
Testmigrationen, eine davon scheiternd; Zählung 3 in allen drei Ansichten.

#### Speicher der Installation

`speicher_lib.php` beantwortet zwei Fragen mit zwei Balken: Was belegen die
**Backups** von der Speichergrenze, und was belegt die **Installation** vom
Webspace laut Hosting. Drei Eigenheiten, die den Aufbau erklären:

1. **Gemessen wird im Aufräumjob, nicht beim Seitenaufruf.** Der
   Verzeichnislauf über `server/` und die Summe über `information_schema`
   kosten zusammen mehr, als eine Seite kosten darf; die Zahlen ändern sich in
   Stunden, nicht in Sekunden. `speicher_messen()` hängt als letzter Schritt am
   täglichen `job_aufraeumen()` und schreibt nach `app_state`
   (`speicher_db_bytes`, `speicher_dateien_bytes`, `speicher_stand`). Die Seite
   liest nur. Ein Teilergebnis wird **auch** geschrieben: Scheitert der
   Verzeichnislauf, steht dort 0 — als „nicht messbar" erkennbar, während ein
   alter Wert mit frischem Zeitstempel eine Lüge wäre.
2. **Der freie Webspace wird nicht gemessen, sondern angegeben.**
   `disk_free_space()` liefert auf geteiltem Hosting den Datenträger des
   *Hosts*, nicht die Quota dieses Kontos — eine Zahl im Terabyte-Bereich, die
   nichts mit dem Tarif zu tun hat. Sie wäre schlimmer als keine. Der Wert
   `webspace_gb` ist deshalb eine Angabe der BetreiberIn; ohne sie zeigt der
   zweite Balken nur die Summe, ohne Anteil und ohne Warnung.
3. **`sicherungen/` zählt im Dateilauf nicht mit.** Die Backups stehen im
   zweiten Balken als eigene Segmente; zweimal in dieselbe Summe genommen
   ergäbe das einen Balken über 100 %.

`speicher_ton()` färbt Balken, Legende und Statusseite nach **denselben**
Schwellen wie die Warnmail (Vorgabe 70/90) — sonst färbte sich der Balken
orange, während die Mail schweigt. Genauigkeit gemessen (P-10): Dateien
7 632 622 B gegen `du -sb --exclude=sicherungen` 7 632 622 B, Datenbank
4 800 512 B gegen die SQL-Summe 4 800 512 B — **0 % Abweichung**; verlangt
waren < 2 %. Für eine Abrechnung taugt die InnoDB-Schätzung trotzdem nicht,
für die Frage „wie viel Platz brauche ich?" genau.

#### Der Knopf „kopieren"

`assets/kopieren.js` gehört zum Baustein `ui_codeblock_lang()` (Design.md 9.18).
Der Knopf steht **hidden** im Markup und wird erst vom Skript sichtbar gemacht:
Ohne JavaScript bliebe sonst ein Knopf stehen, der nichts tut. Der Weg ist
`navigator.clipboard.writeText`; scheitert er (fehlende Berechtigung, kein
sicherer Kontext), markiert das Skript den Text und meldet „markiert — Strg+C".
Die Rückmeldung steht **im Knopf**, nicht daneben — eine Zeile, die auftaucht
und wieder verschwindet, verschiebt sonst das Layout.

### 4.99e Status und Statistik: bewerten und zählen (ab Web 15.3.0, S8/AP4; drei Reiter ab Web 20.47.0, P5c/AP7)

**Zwei Seiten, zwei Fragen — und die Trennung ist die Sache.**
`betrieb_status.php` beantwortet „ist hier etwas zu tun?", `betrieb_statistik.php`
„was trägt diese Installation?". Eine Zahl, die auf der Statistik orange wäre,
gehört auf den Status; eine Auskunft, die nichts fordert, gehört nicht in die
Ampel. **Eine Ausnahme steht da, seit S8:** „Deaktiviert" unter Geräte trägt
orange, sobald ein Gerät gesperrt ist (`betrieb_statistik.php`) — eine
Zeile, kein Urteil über die Anlage.

#### Die Ampel ist eine Tabelle

Vier Töne, und sie bedeuten auf der Statusseite **überall** dasselbe. Es sind
keine neuen — `ui_plakette()` kennt sie seit P3 (Design.md 9.4); neu ist die
feste Bedeutung.

| Ton | heißt | Beispiele |
|---|---|---|
| **blau** | in Ordnung | Wartungsmodus aus · keine ausstehende Migration · Schlüssel vorhanden · Job ohne Fehler und ohne Rückstand · SMTP zugestellt · Komplett-Stand jünger als der Plan · Speicher unter der ersten Schwelle · Ablage beschreibbar |
| **orange** | braucht Aufmerksamkeit, arbeitet aber | Wartungsmodus an · Migration ausstehend · Job mit Rückstand · Auslöser huckepack · Komplett-Stand älter als der Plan · Konto-Backups überfällig oder nie · Speicher ab der ersten Schwelle · Backup-Ziel aktiv, aber nie versendet · Antwort nicht sicher entkoppelt |
| **rot** | arbeitet nicht, oder es geht etwas verloren | Serverschlüssel fehlt · verwaiste Rundenzahl · kein Job-Lauf seit über 24 h · Job mit Fehler · SMTP-Fehler beim letzten Versand · Komplett-Backup nie bei gesetztem Plan · Speicher ab der zweiten Schwelle · Ablage nicht beschreibbar · Tabelle `jobs` fehlt |
| neutral | nicht eingerichtet, oder eine reine Zahl | SMTP nicht eingerichtet · kein Backup-Ziel · Plan „aus" · PHP-Fassung |

Die Zuordnung steht an **einer** Stelle — `status_zeile()` in
`betrieb_status.php` nimmt den Ton entgegen und zählt ihn zugleich. Deshalb
kann die Meldung oben eine Zahl nennen, ohne dass jemand sie von Hand pflegt:
Der Rumpf der Seite entsteht in einem **Ausgabepuffer**, die Meldung danach,
ausgegeben wird sie davor. Ein Vorlauf, der die Zeilen zweimal rechnet, hätte
die Messungen zweimal gekostet.

**Zwei Ampelzustände sind schwer zu erreichen, und das ist eine Eigenschaft
der Anwendung.** „Kein Job-Lauf seit über 24 h" wird beim Aufruf der
Statusseite normalerweise sofort widerlegt: Der Huckepack-Weg
(`run_cleanup_if_due` in `db.php`) läuft auf **dieser** Anfrage mit. Zu sehen
ist er, wenn die Jobs pausiert sind (`php jobs.php --pause`) — dann steht
darüber ohnehin die orange Zeile „Pause". Und „Ablage nicht beschreibbar"
lässt sich als `root` nicht erzwingen: Die Rechteprüfung greift für `root`
nicht, und `edbak_ablage_bereit()` legt das Verzeichnis notfalls neu an.

#### Was die Statistik nicht zählt

**Das Demo-Konto — in keiner Zahl.** Sein Bestand ist erfunden, liegt als
Fixture im Repositorium und wird alle dreißig Minuten daraus neu hergestellt
(4.99a). Jede Abfrage der Seite trägt deshalb `WHERE … <> :demo`; fehlt die
Marke `demo_user_id`, ist der Wert 0 und die Bedingung wahr für alle —
dieselbe Abfrage, ein Sonderfall weniger.

**Den Papierkorb.** Ein gelöschter Einsatz ist keine Nutzung, und er käme beim
Wiederherstellen zurück. Geprüft wird `missions.deleted_at IS NULL` — und nur
das: Wer einen Diensttag löscht, setzt `deleted_at` auch an jedem seiner
Einsätze (`deleted_with_day = 1`, `trash_lib.php`). Bis Web 20.46.0 stand
hier zusätzlich ein Verbund mit `days`; er schloss nebenbei jeden Einsatz
ohne Diensttag aus (`day_id` darf leer sein) und fällt mit der Zählung ab
Beginn weg.

**Wear-OS-Uhren** — sie erscheinen unter Geräte in keiner Zahl, weil sie in
`devices` nie eine Zeile bekommen; unter Einsätze stehen sie seit Web 20.47.0
als Herkunft „Wear-OS-Uhr". Die Wear-OS-App hat weder Serveradresse noch
Schlüssel (E-S4-11); sie schickt ihre Ereignisse an das Handy, und das Handy
koppelt. `devices.geraet_art` ist deshalb `'uhr'` (Garmin, mit Teilenummer),
`'handy'` (Android), `'sonstiges'` oder leer (ältere Fassungen melden nichts);
die Seite zeigt die beiden letzten als „Sonstige" und „Ohne Angabe", wenn es
welche gibt. Das Mockup sah eine Zeile „Wear-OS-Uhren" und eine Art
„Uhr (Wear OS)" vor; beide wären dauerhaft null gewesen. **Eine Zeile, die
bauartbedingt nie etwas zählt, sagt nicht „null" — sie verschweigt, dass es
hier nichts zu zählen gibt.** An ihrer Stelle steht ein Satz.

#### Drei Reiter, eine Zählung (ab Web 20.47.0, P5c/AP7)

**Ein Menüeintrag, drei Reiter** (E-P5c-18, Bild M-P5c-01b):
`?r=nutzer|einsaetze|geraete`, Vorgabe `nutzer`, gebaut mit `ui_reiter()`.
Betrieb behält seine sieben Einträge, und die Seite heißt weiter „Statistik"
— kein Name „Betriebslage". Über den Reitern die vier Kennzahlen, jede ein
Verweis in ihren Reiter. **Jeder Reiter hat dieselbe Form:** links die
Tabelle „… je Zeitraum", rechts die Karte „was es gibt", im Raster
`.form-raster-links-breit` (3 : 2 ab 1200 px, `Design.md` 9.26). Jede Sicht
rechnet nur ihre eigenen Abfragen; die Kennzahlen laufen auf jedem Reiter.

| Reiter | links | rechts |
|---|---|---|
| NutzerInnen | Konten je Zeitraum (24 h · 7 T · 30 T · 6 M): **Aktiv**, Angemeldet, Neu angelegt | Konten nach Rolle (alle aus `ROLLEN`), **Ohne Gerät** |
| Einsätze | Einsätze je Zeitraum (24 h · 7 T · 30 T · 6 M · 1 J): Einsätze, NutzerInnen mit Einsatz, Ø je NutzerIn mit Einsatz | **Herkunft der Einsätze**, 30 Tage, alle sechs Werte |
| Geräte | Geräte je Zeitraum (7 T · 30 T · 6 M): zuletzt gemeldet, gekoppelt | Geräte nach Art, der Wear-Satz; darunter Gerätemodelle über die ganze Breite |

**„Aktiv" nach R38:** `last_login` **oder** `devices.last_seen` eines
**echten** Geräts (`geraete_echt_sql('d')`) im Fenster. **„Ohne Gerät"**
zählt ebenso nur echte Geräte (Nr. 190): Das virtuelle Gerät der
Handeinträge entsteht beim ersten Formular, beim Import, beim Schneiden und
beim GPX-Import — wer ausschließlich von Hand dokumentierte, fiel bis Web
20.46.0 aus genau der Gruppe heraus, die die Kleinzeile meint.

**Herkunft:** Summen von `missions.origin` über die letzten 30 Tage, alle
sechs Werte aus `HERKUNFT_WERTE`, auch mit 0, beschriftet aus
`HERKUNFT_TEXTE` (`geraete_lib.php` — die lange Form; die Plakette am Einsatz
liest die kurze). Ein Wert, den die Fassung nicht kennt, steht als „Andere".
Keine Datenschutz-Vorbedingung (E-P5c-45): Summen einer vorhandenen Spalte,
nur für die BetreiberIn — schwächer identifizierend als das Gerätemodell.
Browser-Zugriffe werden nicht gezählt (R36).

**Sortierung und CSV bleiben im Reiter:** Jeder Spaltenkopf und „Als CSV"
tragen `r=geraete` (F-P5c-39).

#### Gezählt wird ab Beginn des Einsatzes (seit Web 20.47.0)

`missions.started_at` (UTC, Pflichtfeld), ohne Demo-Konto, ohne Papierkorb,
**in Fenstern mit Unter- und Obergrenze**: „7 Tage" heißt zwischen jetzt
minus sieben Tagen und jetzt. Ein Beginn in der Zukunft liegt in keinem
Fenster und zählt nur unter „gesamt" (F-P5c-39 — vorher hatten die Fenster nur
eine Untergrenze). Die Abfrage steht **einmal**, in `statistik_lib.php`
(`statistik_einsaetze_sql()`, `statistik_herkunft_sql()`): eine Abfrage für
alle fünf Fenster über das längste, gelesen über den Index
`idx_missions_started` (Migration `2026_09_24_statistik_beginn`, Nr. 191 —
sie legt außerdem `idx_missions_deleted` an, wo er fehlt: Jede frisch
eingerichtete Anlage hatte ihn bis Web 20.47.0 nicht, F-P5c-124).
Der Messstand lässt **dieselbe** Abfrage erklären (Schritt `statistik`).

**Bis Web 20.46.0 zählte die Seite nach Diensttag** (`missions` JOIN `days`,
gefiltert auf `days.day`) — wie die Statistik der NutzerIn. Entschieden am
20.09.2026 (E-P5c-18): ab Beginn, mit eigenem Index, und damit entfällt der
Name „Bestand" für die eine Zählweise neben der anderen (Nr. 192). **Preis:**
Ein Einsatz nach Mitternacht gehört hier zum Tag seines Beginns, in der
Statistik der NutzerIn zum Dienst des Vortags — die Summen können um
einzelne Einsätze auseinanderliegen. Der Satz unter der Einsatzkarte sagt,
wie gezählt wird; das Handbuch (12.2) sagt, warum die Zahlen abweichen.

**„6 Monate" sind 180 Tage, „1 Jahr" 365.** Ein Monat ist keine feste Länge;
verschieden lange Monate in einer Spalte wären eine stille Ungenauigkeit.

#### Der Hersteller ist abgeleitet, nicht gespeichert

Eine Spalte `hersteller` gäbe es nicht zu füllen: `geraete_lib.php` zieht
Hersteller und Modell ausdrücklich zusammen, weil `Build.MANUFACTURER` ohne
`Build.MODEL` wertlos ist („google" allein beantwortet nichts). Abgeleitet
wird über die **Art**:

| `geraet_art` | Hersteller |
|---|---|
| `uhr` | Garmin — eine Uhr, die koppelt, ist eine Garmin-Uhr |
| `handy` | erstes Wort von `geraet_modell`, denn genau so ist der Name zusammengezogen |
| sonst | — |

**Nicht über die Teilenummer**, obwohl das Konzept es so vorsah. „Teilenummer
vorhanden → Garmin" stimmt, ist aber nicht vollständig: `geraet_teil` bleibt
leer, wenn eine ältere Uhr-Fassung nichts über sich meldet oder ein Gerät von
Hand angelegt wurde. Im Referenzbestand steht bei der `fēnix 7` genau das, und
die Regel machte daraus den Hersteller „fēnix".

#### Die CSV-Ausfuhr ist für Excel gemacht

**Semikolon und UTF-8-BOM**, beides aus demselben Grund: Excel in deutscher
Einstellung liest Komma-CSV als eine Spalte und UTF-8 ohne BOM als Latin-1 —
aus „fēnix" wird „fÄ“nix". Wer die Datei danach speichert, hat den Fehler in
seinen Daten. Ein Werkzeug, das UTF-8 erkennt, verträgt den BOM; Excel
verträgt sein Fehlen nicht. Der Dateiname trägt das Datum
(`geraetemodelle-2026-09-05.csv`), damit zwei Ausfuhren nicht denselben Namen
haben.

Gemessen: erste Bytes `ef bb bf`, danach `Gerät` als `47 65 72 c3 a4 74`.

### 4.99f Menü und Leiste des Einstellungsbereichs (ab Web 15.4.0, S8/AP5)

**Eine Quelle.** `ui_einstellungen_punkte()` in `server/ui.php` liefert die
Blöcke — Schlüssel, Titel, Punkte, je Punkt `[key, href, text, symbol,
zaehler]`. `ui_leiste_einstellungen()` und `ui_einstellungen_uebersicht()`
lesen beide daraus; die Rollenfrage wird an dieser einen Stelle beantwortet
(`ist_admin()`, `ist_betreiberin()`), die Wächter der Seiten prüfen sie ein
zweites Mal. Ein Menü, das mehr zeigt als erreichbar ist, führt ins 403; eines,
das weniger zeigt, verschweigt eine Funktion.

**Die Blöcke sind `<details>`, und PHP rendert die Vorgabe** — „Einstellungen"
plus der Block der aktiven Seite, in jeder Breite. Damit ist der Serverzustand
schon der Zielzustand; `assets/menue.js` legt nur darüber, was in dieser
Sitzung von Hand geändert wurde (`sessionStorage`, ein Schlüssel je Block).
Ein Skript, das die Vorgabe selbst herstellt, ließe bei jedem Seitenaufruf
kurz den anderen Zustand aufblitzen. Ausnahme beim Anwenden des gemerkten
Zustands: Der Block, der die aktive Seite trägt, bleibt offen — sonst stünde
der aktive Eintrag unsichtbar in einem zugeklappten Block.

**Die Statusseite schreibt an zwei Stellen — und nur an diesen zwei.** Ihre
Zusage lautet „ändert nichts am Bestand"; beide Ausnahmen ändern keinen
Bestand, sondern prüfen an Ort und Stelle: der fehlende **Serverschlüssel**
(seit Web 15.2.0) und seit Web 19.3.0 der Knopf **„Testmail an mich"** im Kopf
der Karte E-Mail (Backlog Nr. 120, freigegeben am 12.09.2026). Für SMTP gibt
es keine zuständige Seite, auf die zu verweisen wäre — der Zugang steht allein
in der `config.php`.

Drei Dinge hängen an dieser Testmail, und jedes davon ist ein eigener Fehler,
wenn es fehlt:

- **Der POST-Zweig steht vor `status_karten()`.** `smtp_send()` vermerkt den
  Versand selbst (`smtp_versand_vermerken()` schreibt `smtp_last` und
  `smtp_last_ok`), und die Erhebung liest diese Marken. Stünde der Zweig
  danach, zeigte die Zeile „Letzter Versand" den Stand von vor dem Klick.
- **Erst `smtp_eingerichtet()`, dann versuchen.** `smtp_send()` prüft das
  nicht selbst: Es baut die Verbindung auf, scheitert und vermerkt einen
  Fehlschlag. Ohne die Vorprüfung machte ein Klick auf einer Installation ohne
  Mailserver aus „nicht eingerichtet" (neutral, keine Aufforderung) ein
  „fehlgeschlagen" (rot, zählt in der Meldung oben mit) — eine Statusseite,
  die ein Problem behauptet, das es nicht gibt.
- **Ratenschutz und kurzes Zeitlimit.** Topf `testmail` (3 je Stunde, Merkmal
  Konto UND IP) und `$zeitlimit = 5` statt der Vorgabe 15. Der Versand läuft
  synchron, weil sein Ergebnis gezeigt werden soll; jeder Protokollschritt hat
  sein eigenes Limit, und bei 15 s hielte ein hängender Mailserver die Seite
  über zwei Minuten. `ratelimit_lib.php` muss `betrieb_status.php` dafür
  ausdrücklich nachladen — weder `auth_guard.php` noch `status_lib.php` tun
  es.

**Die Zähler kommen aus `status_lib.php`.** Diese Datei ist mit AP5 aus
`betrieb_status.php` herausgelöst worden und enthält die **eine** Erhebung:
`status_erhebung()` liefert unter `karten` die Zeilen samt Ampelton und unter
`zahlen` die Rohwerte (ausstehende Migrationen, Jobs mit Fehler, kranke
Konto-Backups). `betrieb_status.php` zeichnet die Karten,
`menue_zaehler_betrieb()` und `menue_zaehler_konto()` bauen daraus die Zahlen.
Der Grund für die Trennung ist nicht Ordnung, sondern Wahrheit: Ein Zähler mit
eigener Rechnung sagt früher oder später etwas anderes als die Seite, auf die
er führt.

Zwei Zwischenspeicher in `app_state` (`menue_zaehler_betrieb`,
`menue_zaehler_konto`), je 60 Sekunden, als JSON mit Zeitstempel. Zwei und
nicht einer, weil „Konto-Backups" im Block Verwaltung steht und schon für eine
Admin gilt — die soll nicht die volle Betriebserhebung bezahlen. Die
Statusseite selbst liest keinen Speicher; sie rechnet immer neu und frischt
ihn dabei auf. Gemessen: warm 0,46 ms, kalt mit voller Erhebung 8,15 ms;
Serverantwortzeit der Seiten des Bereichs 7 bis 9 ms (Median, neun Läufe).

**`status_lib.php` wird lazy geladen.** Das `require_once` steht in
`ui_einstellungen_punkte()` hinter der Rollenprüfung, nicht auf oberster
Ebene: `install.php` lädt `ui.php` **ohne Datenbank**, und ein `require` auf
oberster Ebene hätte den Einrichter mitgerissen (dieselbe Falle wie F-P3-AR).

**Die Unterpunkte entstehen im Browser.** `assets/menue.js` liest die Karten
der Seite (`#inhalt .karte[id]`) und hängt ihre Titel als Sprungmarken unter
den aktiven Eintrag. Die Alternative — die Seite meldet ihre Karten an
`ui_geruest_start()` — scheitert daran, dass die Leiste **vor** dem Inhalt
gezeichnet wird: Die Seite müsste ihre Kartentitel zweimal nennen, und die
eine Liste liefe der anderen davon. Voraussetzung ist eine `id` an der Karte,
mit dem Vorsatz `k-`; 27 Karten in sieben Dateien haben mit AP5 eine bekommen.

**Seiten mit Reitern bekommen keine Unterpunkte** (ab Web 20.39.0, P5c/AP2,
E-P5c-25). Enthält `#inhalt` eine `.reiter`-Reihe, baut `menue.js` nichts:
Die Reiter sind die Gliederung der Seite, und eine zweite daneben in der
Leiste nennte die Karten des gerade offenen Reiters und wechselte bei jedem
Reiterwechsel ihren Inhalt. Der Bedienweg `admin-protokoll-reiter` misst
**0** Unterpunkte.

**Unter 800 px Fensterhöhe blendet das Stylesheet sie in der festen Leiste
aus** (ab Web 21.1.0, P5c/AP9, E-P5c-131): `@media (min-width:1024px) and
(max-height:799px)` setzt `.leiste-gruppe .eintrag-unterliste` auf
`display:none`. `menue.js` baut sie weiter — die Entscheidung steht an einer
Stelle, im Stylesheet, und die Schublade unter 1024 px behält sie. Anlass:
bei 1280 × 720 waren auf 8 von 14 Seiten nicht alle 18 Einträge ohne Rollen
erreichbar. Die Lücke zwischen 800 und 946 px ist benannt (`Design.md` 9.25,
F-P5c-164).

Die Markierung („welche Karte steht gerade oben") läuft über einen
`IntersectionObserver`, dessen `rootMargin` die Kopfhöhe plus einen Saum
abzieht. Drei Regeln, die alle aus einer Messung stammen:

* Der `rootMargin` liest `--kopf` aus dem Stylesheet. Eine Zahl im Skript wäre
  die zweite Stelle, an der die Kopfhöhe steht.
* Eine Karte zählt erst, wenn unter der Kopfleiste noch ein Saum von ihr
  steht. Ohne das bleibt eine hohe Karte mit zwei Pixeln Unterkante die
  „oberste sichtbare", während man längst die nächste liest.
* Der Topf, in dem „oberste" gilt, wird an der **Lage** erkannt (linker Rand
  der `.form-spalte`), nicht an der Fensterbreite: Das Zweispalten-Raster
  greift erst ab 1200 px, darunter stehen dieselben Kästen untereinander.

**Keine `scroll-margin-top`.** Das Konzept sah eine vor; `html` trägt jedoch
seit Langem `scroll-padding-top: calc(var(--kopf) + var(--abstand-4))`, und
beides addiert sich — gemessen landete die angesprungene Karte 68 px zu tief.
Mit `scroll-padding-top` allein sitzt der Sprung bei 72 px.

### 4.99g Das Betriebsprotokoll: Schreibweg, Seite, Archiv (ab Web 20.16.5, P5b/AP1; Seite und Archiv ab Web 20.39.0, P5c/AP2; Reiter System ab Web 20.40.0, P5c/AP3)

*Entscheidungen: V1 (16.09.2026), V2, E-P5b-06, E-P5b-12; für Seite und
Archiv E-P5c-02, -03, -10, -11, -26, -38, -39, -57, -75, -76; für den Reiter
System E-P5c-12, -58. Code: `server/protokoll_lib.php` (Schreiben und
Lesen), `server/protokoll_archiv_lib.php` (Archiv),
`server/admin_protokoll.php` (Seite), `server/systemmeldung_lib.php`
(Reiter System); Tabelle `protokoll_ereignisse`.*

#### Was hineingeschrieben wird — und was ausdrücklich nicht

**Betriebsereignisse.** Konto angelegt, freigeschaltet, gesperrt, gelöscht;
Rolle oder Adresse geändert; Sicherung eingespielt; Wartung gefahren;
Schlüsselblatt bestätigt; Mail versandt; Job gelaufen.

**Kein Zugriffsprotokoll.** Wer wann welchen Einsatz geöffnet, gelesen oder
exportiert hat, steht hier nicht. Das ist V1, entschieden am 16.09.2026, und
es ist eine Zusage und keine Lücke: Wer hier einen Eintrag „Einsatz 417
angesehen" ergänzt, ändert eine Programmentscheidung und nicht eine Funktion.

**Keine IP-Adressen** (V2, E-P5b-06). Sie stehen ausschließlich im Reiter
*Sicherheit*, und der liegt in einer **anderen Tabelle**.

#### Sieben Reiter, sechs Tabellen — und warum das so bleibt

`protokoll()` **schreibt** in sechs Reiter (die Werte des ENUM
`protokoll_ereignisse.reiter`). Die Seite **zeigt** sieben, und vier davon
lesen ganz oder teilweise aus Tabellen, die ihre Sache ohnehin führen
(E-P5c-38): **keine doppelte Ablage.**

| Reiter | Quelle | Frist | einstellbar |
|---|---|---|---|
| **Verwaltung** (das Audit) | `protokoll_ereignisse` | **365 Tage** | ja, 90–1095 |
| **Sicherheit** (Sperren, Angriffe) | `sicherheit_ereignisse` (P5a/AP6) und `csp_berichte` | 30 Tage | **nein** |
| E-Mail | `mail_warteschlange` — **nur Vorlage, Zustand, Zeit** — und `protokoll_ereignisse` | 30 Tage (die Warteschlange ab `erstellt`) | nein |
| Jobs | `job_laeufe` und `protokoll_ereignisse` | 30 Tage | nein |
| Sicherung | `protokoll_ereignisse` (Komplett-Backup erzeugt, geladen, eingespielt, gelöscht; Konto-Backup eingespielt — es gibt keine Tabelle dafür) | 30 Tage | nein |
| Ziele | `sicherungsziel_dateien` (gesendet, dort gelöscht) und `protokoll_ereignisse` | **keine** für `sicherungsziel_dateien` — sie ist die Buchführung dessen, was drüben liegt, und bleibt; 30 Tage für die übrigen | nein |
| System | `protokoll_ereignisse` — `system_melden()` und die Behandler (ab Web 20.40.0) | 30 Tage | nein |

Die Trennung von `sicherheit_ereignisse` ist kein Übergangszustand, sondern
die Frist: Dort stehen IP- und E-Mail-Adressen im Klartext und verfallen nach
30 Tagen (E-P5a-09), hier steht das Audit und bleibt bis zu drei Jahre.
**Zwei Fristen in einer Tabelle sind eine Einladung, die kürzere zu
vergessen.** Entschieden mit E-P5c-11 (V6): Sie bleiben getrennt.

**Die E-Mail-Sicht zeigt nie Empfänger, Betreff, Rumpf oder Fehlertext.**
Eine offene Zeile trägt einen Setz-Link mit gültigem Token, und ein
SMTP-Fehler nennt die Adresse, an der er scheiterte — beides gehört nicht in
eine Liste, die der Admin sieht und die ins Archiv geht.

#### Je Quelle eine Abfrage, nie ein UNION

`protokoll_quellen($reiter)` liefert je Reiter ein bis drei SQL-Stücke mit
**denselben Spaltennamen** (`id`, `zeit`, `art`, `uid`, `uart`, `bid`, `text`,
`daten`). Jede Quelle fragt für sich; PHP fügt zusammen, sortiert nach Zeit
und schneidet die Seite zu (für Seite *s* holt jede Quelle ihre jüngsten
*s* × 50 Zeilen). Die Gesamtzahl ist die Summe der Zählungen.

**Warum nicht `UNION ALL`:** Die Tabellen sind zu verschiedenen Zeiten
entstanden, auf Produktiv unter verschiedenen Vorgabe-Kollationen, und ein
UNION über zwei Textspalten verschiedener Kollation ist auf MySQL und
MariaDB ein Fehler („Illegal mix of collations"). Örtlich sind alle gleich;
der Fehler zeigte sich erst dort, wo niemand mehr hinsieht (Muster Nr. 238).
Aus demselben Grund läuft die **Suche je Spalte** (`spalte LIKE ?`, mit `OR`
verbunden) und nicht über `CONCAT_WS` zweier Tabellen.

**Die Suche** trifft Text, E-Mail und Name von Urheber und Betroffenem, die
**Beschriftung** einer Art („Rolle" findet `rolle_geaendert`) und — nur im
Feld `daten.kennung` — eine achtstellige Fehlerkennung. **Eine Kennung im
Suchfeld wechselt auf den Reiter System** (Umleitung 303), und nur die
BetreiberIn sieht ihn. **Der Kontofilter** (`?konto=…`, von der Kontoseite)
fragt nur Quellen mit Konten; die übrigen fallen dann weg, statt leer zu
antworten.

**Fehlt eine Tabelle** (Migration steht aus), meldet die Seite das, statt
eine leere Liste zu zeigen, die wie ein ruhiger Monat aussähe.

#### Der Katalog: Art → Wort und Ton

`PROTOKOLL_ARTEN` in `protokoll_lib.php` gibt jeder Art eine Beschriftung und
einen Ton nach der Ampel aus `Design.md` 9.23 — neutral (ein Vorgang), blau
(erledigt), orange (ein Eingriff, eine Sperre), rot (ein Fehler). Gefiltert
wird nach dem Schlüssel, gezeigt das Wort. **Eine Art, die im Katalog fehlt,
erscheint als ihr Schlüssel und neutral** — sichtbar falsch, aber nicht
verloren. Welche Arten ein Reiter kennt (für das Auswahlfeld), steht in
`protokoll_arten_des_reiters()` — aus dem Katalog, nicht aus dem Bestand.

**Neu mit Web 20.39.0** (E-P5c-38): `rolle_geaendert`, `setzlink_gesendet`,
`geraet_umgeschaltet`, `geraet_geloescht` (je Verwaltung und Selbstbedienung;
beim Entkoppeln **vor** dem Löschen gerufen), `wartung_an`, `wartung_aus`,
`demo_zurueckgesetzt`, `migration_ausgefuehrt`, `archiv_heruntergeladen`,
`frist_geaendert` in der Verwaltung; `komplett_erzeugt`,
`komplett_heruntergeladen`, `komplett_eingespielt`, `komplett_geloescht`,
`kontobackup_eingespielt` in der Sicherung.

**Neu mit Web 20.41.0** (AP4): `verifikation_gesendet` — die Bestätigung
einer Registrierung erneut verschickt, mit dem Zustand der Zustellung, nie
mit dem Link. Und `konto_geloescht` schreibt jetzt auch die Löschung durch
die Verwaltung auf der Kontoseite, **vor** dem `DELETE` (F-P5c-99); bis
dahin schrieben ihn nur die Selbstlöschung und der Verfall.

#### Wer welchen Reiter sieht

`protokoll_reiter_sichtbar()`: **BetreiberIn alle sieben und das Archiv;
Admin Verwaltung, E-Mail, Jobs, Sicherung (`PROTOKOLL_REITER_VERWALTUNG`);
Support Verwaltung und E-Mail** (seit Web 20.41.0, AP4). Was die Rolle nicht
darf, zeigt die Seite nicht — kein ausgegrauter Reiter. Ein Reiter außerhalb
der Liste in der Adresse ist 403, ein unbekannter 404; die Meldung nennt,
wem er vorbehalten ist — Jobs und Sicherung der Verwaltung, die übrigen der
BetreiberIn (F-P5c-100). Die Zellen stehen in der Berechtigungsmatrix
(4.99p).

**Löschen kann niemand** — nur die Fristen (E-P5c-02). Fristen und Archiv
stellt die BetreiberIn in **Betrieb → Servereinstellungen → Protokoll** ein;
jede Änderung schreibt `frist_geaendert`.

#### Kein Fremdschlüssel auf `users`, und das ist der wichtigste Satz

Weder für `urheber_user_id` noch für `betroffen_user_id`. Der häufigste
Verwaltungseintrag überhaupt ist **„Konto gelöscht"**:

- mit `ON DELETE CASCADE` löschte die Kontolöschung ihren eigenen
  Protokolleintrag,
- mit `RESTRICT` verhinderte der Eintrag die Löschung.

Beides ist falsch. Die Id bleibt als Zahl stehen, auch wenn es das Konto
nicht mehr gibt — genau dafür ist ein Audit da.

`urheber_user_id` ist **`0` und nicht `NULL`**, wenn kein Mensch gehandelt
hat; `urheber_art` sagt dann, welche Art von Niemand es war (`cli` an der
Konsole, `job` im Huckepack). Ein `NULL` ließe offen, ob niemand handelte
oder ob jemand vergessen wurde.

#### Wenn das Schreiben scheitert (V7)

**Still scheitern ist schlechter als laut, laut abbrechen ist schlechter als
still.** Ein Protokoll, das eine Kontolöschung verhindert, weil seine Tabelle
fehlt, hält den Betrieb an, um über den Betrieb zu berichten. Eines, das
unbemerkt nichts schreibt, ist keines.

Der Mittelweg hat **drei Stufen, und alle drei müssen da sein**:

1. `error_log()` mit der Kennung `protokoll:` — für die BetreiberIn, die ins
   Serverprotokoll sieht. Eine der zwei Stellen, die `error_log()` seit
   Web 20.40.0 noch rufen (Register Z38): `system_melden()` hier schriebe den
   Fehlschlag des Protokolls ins Protokoll.
2. Der Zähler `protokoll_fehler` in `app_state` — er überlebt die Anfrage.
3. Der Hinweis auf **Betrieb → Status** — er fällt jemandem auf, der nicht
   sucht. Die Karte trägt dann eine rote Plakette „*n* nicht geschrieben".

`protokoll()` gibt `false` zurück. **Der Rückgabewert ist ein Hinweis und kein
Grund abzubrechen** — nur `system_melden()` prüft ihn, um in den Rückfall zu
gehen (seit P5c/AP3; hier stand bis Web 21.1.0 „kein Aufrufer prüft ihn").

#### Der Reiter System — das Fehlerprotokoll (ab Web 20.40.0, P5c/AP3)

Bis Web 20.39.0 stand hier „Die `error_log()`-Aufrufe bleiben, wo sie sind",
bis der Reiter System steht und jemand die Einträge lesen kann. Seit AP3
gehen sie hierher. **Zwei Wege hinein, ein Ort:**

- **`system_melden($bereich, $text, $grund)`** — die 75 Stellen in 30
  Dateien, die bis dahin `error_log()` riefen (Backlog Nr. 248). `$text` sind
  die eigenen Worte der Stelle, `$grund` die fremde Ursache: eine Ausnahme
  (Datei, Zeile und Klasse kommen mit) oder der Text einer Gegenstelle.
  Rückgabe ist die **Kennung**, acht Hexziffern groß, dieselbe Form wie seit
  M3-10. `fehler_kennung()` und damit `json_fehler()` gehen seither hier
  durch; ihre Antwortform bleibt (`JSON-Vertrag.md` 5).
- **Drei Behandler**, eingerichtet in `db.php` gleich hinter der Prüfung auf
  `config.php` und genau dort (`tools/quelltext/behandler.php`, ein Riegel):
  `system_ausnahme_behandeln()` für eine Ausnahme, die niemand fängt,
  `system_fehler_behandeln()` für Warnungen und Hinweise,
  `system_abbruch_pruefen()` am Ende der Anfrage für den schweren Fehler —
  Parse-Fehler, Speicherende, Zeitende —, den keiner der beiden sieht.

| Art | Wort, Ton | woher |
|---|---|---|
| `ausnahme` | Unerwarteter Fehler, rot | Behandler; `fehler_kennung()` |
| `abbruch` | Abbruch, rot | Abschlussbehandler |
| `stoerung` | Störung, orange | `system_melden()` einer Stelle |
| `php_warnung` | PHP-Warnung, orange | Fehlerbehandler (`E_WARNING` u. ä.) |
| `php_hinweis` | PHP-Hinweis, grau | Fehlerbehandler (Hinweis, Veraltetes) |

**Die Antwort.** Die Fehlerseite ist das Gerüst der Störungsseiten
(`stoerung_seite_html()`, kein neuer Baustein): 500, eine
`meldung-fehler` mit der Kennung und der Satz „Melde diese Kennung an
<Kontaktadresse>" — oder, ohne Adresse oder ohne Datenbank, „Nenne diese
Kennung, wenn du den Fehler meldest" (`system_meldesatz()`, R38). Vorher
verwirft sie jeden Ausgabepuffer: Ein Hoster mit `output_buffering` hätte
sonst die halbe Seite davor stehen (F-P5c-97). Wo ein
Skript fragt (`wartung_json_gefragt()`), dieselbe Aussage als JSON
`{"error":"server","kennung":…,"meldung":…}`. **Auf der Kommandozeile** bleibt
es beim gewohnten Bild: Text und Aufrufkette auf stderr, Rückgabewert 255,
dazu die Kennung — und Warnungen gehen dort **zusätzlich** wie gewohnt auf
stderr, damit eine Probe, die sie zählt, weiter zählt. Ein Gedrängel (1205,
1213) wird auch im Behandler 503 `ausgelastet`, wie in `json_fehler()`.

**Was hineingeht und was nicht** (E-P5c-12). Satz, Kennung, Art, Datei und
Zeile (bezogen auf `server/`, nie der Ordner beim Hoster), die Klasse einer
Ausnahme, die ausgeführte Datei (`seite`) — und der Urheber aus der
Sitzung, wie bei jedem Eintrag. **Nicht:** Anfrage, Kopfzeilen,
Sitzungsinhalt, IP. In fremden Meldungen werden Werte in Anführungszeichen
durch `'…'` ersetzt, E-Mail-Adressen durch `[Adresse]`, IPv4-Adressen durch
`[IP]` — `Duplicate entry '…' for key` trüge sonst Adressen und
Gerätekennungen in ein Archiv, das 365 Tage liegt und außer Haus geht. Die
eigenen Worte einer Stelle behalten ihre Anführungszeichen (sie nennen
Schlüssel, keine Werte), Adressen verlieren auch sie.

**Mit `@` unterdrückt heißt: nicht da.** 136 Stellen in 19 Dateien rechnen
mit einem Fehlschlag und fragen selbst nach; der Behandler gibt `false`
zurück, PHP geht seinen gewohnten Weg, und `error_get_last()` bleibt
gefüllt. **Dieselbe Stelle zählt je Anfrage einmal, höchstens 20 je
Anfrage** (`SYSTEM_PHP_JE_ANFRAGE`) — eine Warnung in einer Schleife schriebe
sonst tausend gleiche Zeilen.

**Drei Eigenschaften, die bleiben müssen.** (1) `systemmeldung_lib.php` lädt
nichts: `install.php` lädt `ui.php` ohne `db.php`, und `wartung_lib.php` darf
keine Datenbank voraussetzen. `protokoll_lib.php` kommt erst im Rumpf von
`system_melden()` dazu. (2) **Keine Schleife:** `protokoll()` scheitert →
`protokoll_fehler_vermerken()` → `app_state_setzen()` scheitert →
`system_melden()` … — die Sperre schickt jede Meldung, die während des
Meldens entsteht, in den Rückfall; fällt die Datenbank einmal aus, bleibt sie
für die Anfrage aus. (3) **Ein Rückfall, eine Kennung:** Geht die Datenbank
nicht, steht der Satz mit derselben Kennung im Fehlerprotokoll des Webspace
(`system_rueckfall()`). Das ist neben `protokoll_fehler_vermerken()` die
zweite von **zwei** `error_log()`-Stellen, die bleiben (Register Z38,
Decke 2).

**Die benannten Rückfallstellen** rufen `system_rueckfall()` unmittelbar,
weil dort die Datenbank das Problem ist: die Verbindungsgrenze und das
Gedrängel (`wartung_lib.php`), der Torwächter (`migration_lib.php`), die
beiden Anmeldewege, die eine ausstehende Migration überbrücken
(`auth_guard.php`, `login.php`), und seit P5c/AP6 der Health-Endpunkt, wenn
die Datenbank nicht antwortet (`api/health.php`) — sechs Orte (hier standen
bis Web 21.1.0 fünf).

**Mitten in einer Transaktion** wartet ein Eintrag bis zum Ende der Anfrage
(`system_nachtragen()`): Er wäre sonst Teil einer Transaktion, die gerade
etwas meldet und oft gleich zurückgerollt wird. Steht sie dann noch offen,
geht er in den Rückfall.

**Die Kennungssuche** im Suchfeld (nur BetreiberIn, E-P5c-26) vergleicht
**groß**. Die Kollation von `JSON_UNQUOTE()` hängt an der Engine — gemessen
`utf8mb4_bin` auf MariaDB 10.11, MySQL 8.0 und 8.4, `utf8mb3_general_ci` nur
auf MariaDB 10.6 —, und bis AP3 stand dort `strtolower`: Auf Produktiv und
Staging hätte die Suche nichts gefunden (F-P5c-89).

**Nachweis:** `tools/proben/protokoll/` Teil 7 (über einen eigenen `php -S`
mit `fehlerrouter.php`, nichts unter `server/`), `tools/proben/ingest/`
Teil 11 (Geräteweg, über einen vorübergehenden Auslöser auf `missions`),
`tools/quelltext/behandler.php`.

#### Die Zählkarte auf Betrieb → Status

Einträge je Reiter, letzte 24 Stunden und gesamt — **seit Web 20.39.0 über
dieselben Quellen wie die Seite** (`protokoll_zahl()`). Bis dahin zählte sie
nur `protokoll_ereignisse`, und E-Mail, Jobs und Ziele standen auf null,
obwohl ihre Tabellen voll waren. Jede Zahl ist ein Verweis auf ihren Reiter.

#### Das Archiv (Job `protokoll_archiv`, ab Web 20.39.0)

**Wofür:** Die Datenbank hält die Einträge nur so lange wie ihre Frist. Das
Archiv hält sie länger und außer Haus (E-P5c-03).

| | |
|---|---|
| **Zeitraum** | alle **7 Tage** (1–31), Grenzen **Mitternacht in der Zeitzone der Anlage** — über den Kalender gerechnet, damit eine Sommerzeitumstellung die Grenze nicht auf 23 oder 1 Uhr schiebt |
| **Erstes Archiv** | beginnt beim **ältesten Eintrag** aller Quellen, auf den Tag abgerundet (E-P5c-76); der Job holt dann Zeitraum für Zeitraum nach |
| **Ablage** | `sicherungen/protokoll/<Beginn ISO UTC>_<Kennung des Serverschlüssels>.zip` |
| **Inhalt** | je Reiter eine JSON-Zeilen-Datei, in Teile zu höchstens **1 MB** Klartext geschnitten, jeder Teil für sich mit `sk_versiegeln()` versiegelt, dazu `manifest.json.sk`; Format: `docs/Backup-Format.md` 7 |
| **Höchstgröße** | **32 MB** Klartext je Archiv; was darüber liegt, wird nicht archiviert, und das Manifest sagt es (`gekuerzt` je Reiter) |
| **Arbeitsweise** | in Häppchen zu **500 Zeilen** je Abfrage mit Fortsetzungsmarke (Produktiv hat keinen Cron, huckepack gibt es 3 s); der Klartext liegt nur im Bauordner `.bau/` unter `sicherungen/` und wird nach dem Packen gelöscht |
| **Aufbewahrung** | **365 Tage** (90–1095), gezählt ab dem Beginn des Zeitraums; dann löscht der Job |
| **Versand** | mit dem Versandjob auf die aktiven Sicherungsziele (Einstellung, Vorgabe an), **ohne Aufbewahrungsregel dort** (4.97c) |
| **Ohne Serverschlüssel** | kein Archiv — ein Archiv im Klartext wäre genau das, was das Siegel verhindern soll; die Statusseite sagt schon rot, dass der Schlüssel fehlt |

**Was hineindarf (E-P5c-39, -75).** Sicherheit: nur Art, Topf, Stufe, Zeit —
**`merkmal` (IP oder Adresse) und `wer` nicht**; aus den CSP-Berichten
Richtlinie, Quelle, Seite, Anzahl. E-Mail: nur Vorlage, Art, Zeit. **Alle
übrigen Reiter wie gespeichert — samt der Adressen im Text** (Q-P5c-31,
entschieden 24.09.2026): Das Audit soll nach einer Kontolöschung noch sagen,
wer es war, und die Datenbank hält dieselben Texte ohnehin 365 Tage. Die
30-Tage-Zusage aus E-P5a-09 gilt für `sicherheit_ereignisse`, und die hält
auch im Archiv.

**Die Kennung des Serverschlüssels steht im Namen und im Siegelzweck**
(`protokollarchiv|<Name>|<Teil>`). Ein umbenanntes Archiv lässt sich nicht
mehr öffnen, und eines von einem anderen Schlüssel erkennt die Seite am
Namen, ohne es zu öffnen: Der Reiter Archiv zeigt es mit der Plakette
„anderer Schlüssel" und sperrt den Download.

**Der Download** (nur BetreiberIn, POST mit Token) entsiegelt in einen
Arbeitsordner unter dem temporären Verzeichnis, packt `manifest.json` und je
Reiter eine `.jsonl` in ein gewöhnliches ZIP, liefert es aus und räumt auf.
Er schreibt `archiv_heruntergeladen` — **vor** der Auslieferung, weil die
Anfrage danach endet.

**Gewicht:** Die Archive zählen gegen Sicherungsgrenze und Warnschwellen
(`speicher_lib.php`, `protokoll_bytes`); der Speicherbalken trägt sie im
Segment der Konto-Backups mit (E-P5c-77) — ein eigenes Segment bräuchte
eine Farbe und eine Freigabe.

### 4.99p Die Berechtigungsmatrix (ab Web 20.39.0, P5c/AP2, E-P5c-22)

*Wer darf was — eine Zeile je Handlung, eine Spalte je Rolle. Die Tabelle
zwischen den beiden Markierungen ist **keine Beschreibung, sondern die
Vorgabe**: `tools/proben/rollen/probe.php` liest sie aus dieser Datei und
fährt jede Zelle gegen die örtliche Anlage. Wer eine Zeile ändert, ändert, was
die Probe verlangt; wer ein Tor im Code ändert, ohne die Zeile nachzuziehen,
bekommt die Probe rot. Anlass: Nr. 286 (Admin erreichte Komplett-Backup und
Backup-Ziele) und Nr. 149.*

**Was die Zellen heißen.** `200`, `303`, `404`: der Statuscode einer
GET-Anfrage. `403`: das **Rollentor** — die Seite antwortet mit „Kein
Zugriff" oder „… vorbehalten". `durch`: nur bei POST — die Probe schickt ein
**absichtlich falsches Formular-Token**, und die Antwort ist die
Token-Ablehnung („Ungültiges Formular-Token"), nicht das Rollentor. So belegt
die Zelle, dass die Rolle die Handlung erreicht, **ohne dass sie ausgeführt
wird** — kein Komplett-Backup, keine Löschung, kein Versand. Der Preis: Dass
die Handlung mit gültigem Token auch gelingt, zeigt die Matrix nicht; das tun
die Bedienwege und die Proben der jeweiligen Sache.

**Zwei Platzhalter** (seit Web 20.41.0, AP4): `{ziel}` ist ein Konto der
Rolle `user`, `{admin}` eines der Rolle `admin` — beide legt die Probe an und
räumt sie ab. Ein Konto der Rolle `support` als Ziel hat keinen Platzhalter;
dass der Support auch andere Support-Konten nicht betreut (E-P5c-99), steht
im Code (`rolle_darf_support()`), gemessen ist es hier nicht. Die Kontoseite
fragt die Rolle **dreimal**: erst, ob die Angemeldete das Zielkonto
überhaupt betreuen darf (der Support nur Konten von NutzerInnen, E-P5c-40),
dann je Handlung (`handlung_erlaubt()`), und seit Web 20.42.0 beim
Zurücksetzen des Zweitfaktors noch einmal nach der Rolle des Ziels
(`rolle_darf_zweitfaktor_zuruecksetzen()`). Alle drei Tore stehen in der
Matrix.

**Was die Matrix nicht führt** (Gegenlesung AP11). Sie hat eine Zeile je
Handlung auf den Seiten, die **mehr als eine Rolle** erreicht — Verwaltung,
Protokoll, Rechtstexte — und je eine für die Seiten, die allein der
BetreiberIn gehören. Die POST-Handlungen auf diesen BetreiberIn-Seiten
(`betrieb_server.php`, `betrieb_jobs.php`, `betrieb_updates.php`,
`betrieb_status.php`, `betrieb_sicherheit.php`, dazu
`api/schluesselblatt_pruefen.php`; gezählt 25 Handlungen und 7
Seitenaufrufe) stehen **nicht** einzeln darin: Jede liegt hinter
`require_betreiberin()` am Kopf ihrer Datei, und das Tor der Seite misst die
Probe. Eine Handlung, die dort vor dem Tor stünde, fände die Matrix nicht.

**Seit Web 21.1.0 (AP9) stehen die Seite Rechtstexte und ihr
Vorschau-Endpunkt darin.** Die Zeile „Installation: Rechtstexte speichern"
ist dabei gegangen: Die Installation nimmt keinen Rechtstext mehr an.

<!-- rollenprobe:anfang -->
| Handlung | Aufruf | user | support | admin | betreiberin |
|---|---|---|---|---|---|
| Protokoll: die Seite | `GET admin_protokoll.php` | 403 | 200 | 200 | 200 |
| Protokoll: Reiter Verwaltung | `GET admin_protokoll.php?r=verwaltung` | 403 | 200 | 200 | 200 |
| Protokoll: Reiter Sicherheit | `GET admin_protokoll.php?r=sicherheit` | 403 | 403 | 403 | 200 |
| Protokoll: Reiter E-Mail | `GET admin_protokoll.php?r=email` | 403 | 200 | 200 | 200 |
| Protokoll: Reiter Jobs | `GET admin_protokoll.php?r=jobs` | 403 | 403 | 200 | 200 |
| Protokoll: Reiter Sicherung | `GET admin_protokoll.php?r=sicherung` | 403 | 403 | 200 | 200 |
| Protokoll: Reiter Ziele | `GET admin_protokoll.php?r=ziele` | 403 | 403 | 403 | 200 |
| Protokoll: Reiter System | `GET admin_protokoll.php?r=system` | 403 | 403 | 403 | 200 |
| Protokoll: Archiv | `GET admin_protokoll.php?r=archiv` | 403 | 403 | 403 | 200 |
| Protokoll: Archiv herunterladen | `POST admin_protokoll.php action=archiv_laden` | 403 | 403 | 403 | durch |
| Protokoll: Fehlerkennung wechselt auf System | `GET admin_protokoll.php?q=0badc0de` | 403 | 200 | 200 | 303 |
| Protokoll: unbekannter Reiter | `GET admin_protokoll.php?r=gibtesnicht` | 403 | 404 | 404 | 404 |
| Protokoll: Fristen und Archiv einstellen | `POST betrieb_server.php action=protokoll` | 403 | 403 | 403 | durch |
| Komplett-Backup: die Seite | `GET admin_komplettsicherung.php` | 403 | 403 | 403 | 200 |
| Komplett-Backup: herunterladen | `POST admin_komplettsicherung.php action=herunterladen` | 403 | 403 | 403 | durch |
| Komplett-Backup: jetzt sichern | `POST admin_komplettsicherung.php action=jetzt_sichern` | 403 | 403 | 403 | durch |
| Komplett-Backup: fortsetzen | `POST admin_komplettsicherung.php action=fortsetzen` | 403 | 403 | 403 | durch |
| Komplett-Backup: abbrechen | `POST admin_komplettsicherung.php action=abbrechen` | 403 | 403 | 403 | durch |
| Komplett-Backup: Regeln | `POST admin_komplettsicherung.php action=regeln` | 403 | 403 | 403 | durch |
| Komplett-Backup: Stand löschen | `POST admin_komplettsicherung.php action=stand_loeschen` | 403 | 403 | 403 | durch |
| Backup-Ziele: die Seite | `GET admin_sicherungsziele.php` | 403 | 403 | 403 | 200 |
| Backup-Ziele: Ziel speichern | `POST admin_sicherungsziele.php action=ziel_speichern` | 403 | 403 | 403 | durch |
| Backup-Ziele: Ziel löschen | `POST admin_sicherungsziele.php action=ziel_loeschen` | 403 | 403 | 403 | durch |
| Backup-Ziele: Abdruck vergessen | `POST admin_sicherungsziele.php action=abdruck_vergessen` | 403 | 403 | 403 | durch |
| Backup-Ziele: Versand an oder aus | `POST admin_sicherungsziele.php action=versand_schalter` | 403 | 403 | 403 | durch |
| Backup-Ziele: jetzt versenden | `POST admin_sicherungsziele.php action=jetzt_versenden` | 403 | 403 | 403 | durch |
| Backup-Ziele: Ziel prüfen | `POST admin_sicherungsziele.php action=ziel_pruefen` | 403 | 403 | 403 | durch |
| Backup-Ziele: Bestand ansehen | `POST admin_sicherungsziele.php action=ziel_bestand` | 403 | 403 | 403 | durch |
| NutzerInnen: die Liste | `GET admin_users.php` | 403 | 200 | 200 | 200 |
| NutzerInnen: Konto anlegen | `POST admin_users.php action=user_add` | 403 | 403 | durch | durch |
| NutzerInnen: Auswahl sichern | `POST admin_users.php action=sichern_auswahl` | 403 | 403 | durch | durch |
| Kontoseite: Konto einer NutzerIn | `GET admin_user.php?id={ziel}` | 403 | 200 | 200 | 200 |
| Kontoseite: Konto eines Admins | `GET admin_user.php?id={admin}` | 403 | 403 | 200 | 200 |
| Kontoseite: Rolle, Name, Adresse | `POST admin_user.php?id={ziel} action=konto` | 403 | 403 | durch | durch |
| Kontoseite: Mengengrenzen | `POST admin_user.php?id={ziel} action=konto_grenzen` | 403 | 403 | durch | durch |
| Kontoseite: Status | `POST admin_user.php?id={ziel} action=konto_status` | 403 | 403 | durch | durch |
| Kontoseite: Setz-Link senden | `POST admin_user.php?id={ziel} action=pw_reset` | 403 | durch | durch | durch |
| Kontoseite: Bestätigung erneut senden | `POST admin_user.php?id={ziel} action=verifikation` | 403 | durch | durch | durch |
| Kontoseite: Konto-Backup erzeugen | `POST admin_user.php?id={ziel} action=sichern` | 403 | 403 | durch | durch |
| Kontoseite: Konto-Backup einspielen | `POST admin_user.php?id={ziel} action=einspielen` | 403 | 403 | durch | durch |
| Kontoseite: Backup freigeben | `POST admin_user.php?id={ziel} action=freigeben` | 403 | 403 | durch | durch |
| Kontoseite: Freigabe widerrufen | `POST admin_user.php?id={ziel} action=widerrufen` | 403 | 403 | durch | durch |
| Kontoseite: Backup löschen | `POST admin_user.php?id={ziel} action=paket_loeschen` | 403 | 403 | durch | durch |
| Kontoseite: Konto löschen | `POST admin_user.php?id={ziel} action=user_delete` | 403 | 403 | durch | durch |
| Kontoseite: Gerät an oder aus | `POST admin_user.php?id={ziel} action=device_toggle` | 403 | 403 | durch | durch |
| Kontoseite: Gerät deaktivieren | `POST admin_user.php?id={ziel} action=device_aus` | 403 | durch | durch | durch |
| Kontoseite: Gerät entkoppeln | `POST admin_user.php?id={ziel} action=device_delete` | 403 | 403 | durch | durch |
| Kontoseite: Setz-Link an einen Admin | `POST admin_user.php?id={admin} action=pw_reset` | 403 | 403 | durch | durch |
| Kontoseite: Zweitfaktor zurücksetzen | `POST admin_user.php?id={ziel} action=totp_zuruecksetzen` | 403 | 403 | durch | durch |
| Kontoseite: Zweitfaktor eines Admins zurücksetzen | `POST admin_user.php?id={admin} action=totp_zuruecksetzen` | 403 | 403 | 403 | durch |
| Konto-Backups: die Seite | `GET admin_sicherungen.php` | 403 | 403 | 200 | 200 |
| Konto-Backups: Regeln | `POST admin_sicherungen.php action=regeln` | 403 | 403 | durch | durch |
| Konto-Backups: alle sichern | `POST admin_sicherungen.php action=sichern_alle` | 403 | 403 | durch | durch |
| Konto-Backups: einspielen | `POST admin_sicherungen.php action=einspielen` | 403 | 403 | durch | durch |
| Konto-Backups: freigeben | `POST admin_sicherungen.php action=freigeben` | 403 | 403 | durch | durch |
| Konto-Backups: Freigabe widerrufen | `POST admin_sicherungen.php action=widerrufen` | 403 | 403 | durch | durch |
| Konto-Backups: Paket löschen | `POST admin_sicherungen.php action=paket_loeschen` | 403 | 403 | durch | durch |
| Konto-Backups: Ordner löschen | `POST admin_sicherungen.php action=ordner_loeschen` | 403 | 403 | durch | durch |
| Installation: die Seite | `GET admin_installation.php` | 403 | 403 | 200 | 200 |
| Installation: Name | `POST admin_installation.php action=instanz_name` | 403 | 403 | durch | durch |
| Installation: Adressen | `POST admin_installation.php action=instanz_adressen` | 403 | 403 | durch | durch |
| Installation: Logo | `POST admin_installation.php action=logo_standard` | 403 | 403 | durch | durch |
| Rechtstexte: die Seite | `GET admin_rechtstexte.php` | 403 | 403 | 200 | 200 |
| Rechtstexte: speichern | `POST admin_rechtstexte.php` | 403 | 403 | durch | durch |
| Rechtstexte: Vorschau beim Tippen | `POST api/rechtstext_vorschau.php` | 403 | 403 | durch | durch |
| Demo-Konto: die Seite | `GET admin_demo.php` | 403 | 403 | 200 | 200 |
| Demo-Konto: anlegen | `POST admin_demo.php action=demo_anlegen` | 403 | 403 | durch | durch |
| Demo-Konto: zurücksetzen | `POST admin_demo.php action=demo_reset` | 403 | 403 | durch | durch |
| Demo-Konto: entfernen | `POST admin_demo.php action=demo_entfernen` | 403 | 403 | durch | durch |
| Rückweg: Paar ablegen (Konzept RW) | `POST api/rueckweg_anlegen.php` | durch | durch | durch | durch |
<!-- rollenprobe:ende -->

**Daneben prüft die Probe Wirkungen**, denn eine Zelle `durch` sagt nur,
dass die Rolle die Handlung erreicht, nicht, was sie dort tut:

- Ein Rollenwechsel auf der Kontoseite erzeugt **genau einen** Eintrag
  `rolle_geaendert` im Reiter Verwaltung, und ein Speichern ohne Wechsel
  keinen (E-P5c-38, F-P5c-17).
- **Der Support** (AP4, E-P5c-14, -40), mit gültigem Token: Das Menü zeigt
  unter Verwaltung genau NutzerInnen und Protokoll, die Liste kein Konto mit
  eigenen Rechten (die Gegenprobe beim Admin findet es) und drei Kacheln,
  die Kontoseite die Sicht aus M-P5c-02c — einspaltig, Mengen ohne Formular,
  keine Konto-Backups, kein Löschen, kein Speichern; beim Admin stehen die
  Marken da —, die Protokollseite genau zwei Reiter. Der Setz-Link steht **nicht** in seiner Antwort — auch nicht,
  wenn die Mail nicht hinausging; die Gegenprobe mit einem Admin zeigt ihn im
  selben Fall. Ein Gerät schaltet er aus (1 → 0), aber nicht wieder an und
  nicht ab (403, das Gerät bleibt); ein Konto löscht er nicht (403, das Konto
  bleibt). Die Bestätigung einer Registrierung geht erneut hinaus und
  schreibt `verifikation_gesendet`, ohne den Link zu zeigen.

### 4.99q Der Zweitfaktor (ab Web 20.42.0, P5c/AP5)

*E-P5c-15, -41 bis -43, -53, -54, -56, -61; Mockups M-P5c-02b und -02c.*
TOTP nach RFC 6238 — HMAC-SHA1 über den Zeitschritt, 30 s, sechs Ziffern,
ein Schritt Toleranz nach beiden Seiten. **Pflicht** für Support, Admin und
BetreiberIn (`rolle_braucht_zweitfaktor()`), **Angebot** für NutzerInnen,
**gesperrt** im Demo-Konto. Alles Rechnen und Speichern steht in
`totp_lib.php`; Datenmodell in Abschnitt 3 (`users.totp_*`, `totp_codes`),
Migration `2026_09_24_zweitfaktor`.

**Die Code-Abfrage steht VOR der Sitzung** (E-P5c-53, F-P5c-31). Nach dem
richtigen Passwort setzt `login.php` für ein Konto mit Zweitfaktor **nicht**
`user_id`, sondern den halben Stand `$_SESSION['totp_halb']` (Konto, Adresse,
Frist **fünf Minuten**) und leitet mit 303 auf sich selbst um; die Seite zeigt
dann den Code-Schritt. `user_id` und `session_regenerate_id()` kommen erst
mit einem gültigen Code (`anmeldung_vollenden()`). Damit ist die halbe
Anmeldung für jede andere Seite und jeden Endpunkt schlicht „nicht
angemeldet" — **ohne dass einer von ihnen davon weiß**. Seit derselben
Fassung antwortet `auth_guard.php` einem API-Aufruf ohne `user_id` mit
**401 JSON** (`error: session_ende`, `grund: nicht_angemeldet`) statt einer
Weiterleitung, der `fetch()` gefolgt wäre.

**Nach dem Code wird das Konto noch einmal gelesen** (`login_zeile()`,
`login_zugang()`): In den fünf Minuten kann es gesperrt oder die Wartung
eingeschaltet worden sein. **Die Selbstlöschung nimmt erst die ganze
Anmeldung zurück**, nicht schon das Passwort (`$vollstaendig`): Wer nur das
Passwort hat, darf die Löschung nicht aufheben.

**Das Vormerkfach.** Der Passwortschritt legt die abgeleiteten Hälften im
`sessionStorage` ab (`merkeAbleitungen`, S10); abgeholt werden sie von der
ersten angemeldeten Seite. Der Code-Schritt lädt kein `unlock.js`. Endet der
halbe Stand ohne Anmeldung — „Zurück zur Anmeldung" (`?abbrechen=1`),
abgelaufen, gesperrt —, räumt die Passwortseite das Fach
(`EdCrypto.vergissAbleitungen()`).

**Kein Code gilt zweimal.** `totp_schritt` hält den zuletzt angenommenen
Zeitschritt; angenommen wird nur ein späterer, atomar in der Bedingung des
`UPDATE`. Ohne das wäre derselbe Code wegen der Toleranz rund 90 Sekunden
lang wiederholbar. Wiederherstellungscodes: zehn, je acht Zeichen aus
`TOTP_CODE_ZEICHEN` (ohne 0, 1, I, L, O, U), gespeichert mit
`password_hash()`, angenommen mit `benutzt_am` — ebenfalls atomar. Eine
Anmeldung mit Code schreibt `totp_code_benutzt` ins Protokoll, mit dem Konto
als Urheber.

**Der Ratentopf `totp`** (fünf Versuche je 15 Minuten, mit Leiter) zählt je
Konto, nicht je Adresse — wer hier steht, hat das Passwort schon. App-Code und
Wiederherstellungscode zählen in denselben Topf. Ist er gesperrt, entsteht der
halbe Stand gar nicht erst.

**Das Einrichtungstor** (`auth_guard.php`, E-P5c-61): Eine Pflichtrolle ohne
`totp_seit` landet auf `zweitfaktor.php` — einer eigenen Seite in der
Anmeldehülle, nicht im Gerüst —, die API antwortet 403 JSON. Offen bleiben
nur `zweitfaktor.php` und `logout.php`; das Einwilligungstor steht danach und
lässt `zweitfaktor.php` durch, sonst schickten die beiden Tore einander im
Kreis. **Stumm in drei Fällen**, und jeder ist ein Riegel: Die Spalten fehlen
(Deploy vor `update.php`), die Wartung ist an (die BetreiberIn muss
`betrieb_updates.php` immer erreichen), kein Serverschlüssel (die Einrichtung
verweigert ohne ihn — ein Tor, dessen einzige Tür verschlossen ist, sperrte
die BetreiberIn aus, die ihn nachtragen soll).

**Einrichten** (Tor und Profilkarte, gemeinsame Teile in
`zweitfaktor_teile.php`): `totp_einrichtung_beginnen()` legt ein neues
Geheimnis versiegelt ab, **ohne** `totp_seit`; eine angefangene Einrichtung
behält ihr Geheimnis beim Neuladen. `totp_einrichtung_abschliessen()` setzt
`totp_seit` erst mit einem passenden Code und liefert die zehn Codes — sie
stehen **genau einmal** in der Antwort auf diesen POST. Der QR-Code entsteht
im Browser (`assets/qr.js` aus `qrcode-generator`, 9.38 in `Design.md`),
daneben die otpauth-Adresse als Verweis und das Geheimnis in Base32. Das
Codeblatt (`codeblatt.php`, `.blatt-druck`) bekommt die Codes per POST und
speichert nichts.

**Zurücksetzen** (Kontoseite, E-P5c-42): die BetreiberIn für alle Rollen, ein
Admin nur für Konten der Rolle user (`rolle_darf_zweitfaktor_zuruecksetzen()`,
geprüft **vor** dem Formular-Token wie jedes Rollentor, E-P5c-85); das eigene
Konto nicht. `totp_abschalten()` leert Geheimnis, Zeitschritt und Codes und
schreibt `totp_zurueckgesetzt`; die Mail `totp_zurueckgesetzt` geht an die
Kontoadresse. **Selbst ausschalten** geht nur ohne Pflicht. Der Demo-Reset
leert die Spalten (`demo_zweitfaktor_leeren()`, gerufen aus
`demo_zuruecksetzen()`).

**Der Rückweg über den Wiederherstellungsschlüssel entsteht in vier Paketen**
(Konzept RW, E-P5c-104, F-P5c-106): E-P5c-42 sah ihn gegen `pat_key_check`
vor — einen Wert, den jeder Datenbankabzug enthält. Die fälschungssichere
Fassung prüft stattdessen eine **ECDSA-Signatur** (P-256, SHA-256, 64 Byte
`r‖s`) über eine Herausforderung des Servers gegen den öffentlichen Teil
eines Paars je Konto (`users.rw_*`, Abschnitt 3); der private Teil liegt
unter dem Inhaltsschlüssel und lässt sich nur im Browser mit dem
Wiederherstellungsschlüssel öffnen. **Seit Web 20.43.0 (RW-01)** stehen die
Spalten, die Prüfung (`rueckweg_lib.php`: `rw_nachricht()` baut
`nadoku-rw-v1|totp-rueckweg|<Konto>|<Herausforderung>` selbst, `rw_pruefen()`
über phpseclib) und der **Selbsttest** gegen einen festen Vektor aus
Chromium: `rw_verfuegbar()` merkt sich sein Ergebnis in `app_state`
(`rw_selbsttest`) je Fassung und Plattform — neu getestet wird nach einem
Deploy oder einem neuen PHP/OpenSSL beim Hoster, nicht bei jedem Aufruf.
Betrieb → Status zeigt es als Zeile „Rückweg-Prüfung" (blau „prüft" mit Weg
und Dauer — über openssl rund 20 ms je Prüfung, davon 19 für das Laden des
Schlüssels in reinem PHP; ohne openssl rund 170 ms —, orange
„abgeschaltet", wenn der Test scheitert).

**Seit Web 20.44.0 (RW-02) entsteht das Paar** — im Browser, still nach der
Anmeldung, für jedes Konto mit Inhaltsschlüssel außer dem Demo-Konto
(E-RW-02, -15). `ui_krypto_bootstrap()` liefert `RW_STAND` (`rw_zustand()`:
`'da'`, `'fehlt'`, `'spalten'` vor `update.php`, `'demo'`) und nur bei
`'fehlt'` die Datei `assets/rueckweg.js`. `unlock.js` merkt sich beim
Auflösen des Vormerkfachs das Anmelde-Token (nach einer stillen Anhebung das
neue) und gibt es, sobald der Inhaltsschlüssel da ist, mit ihm an
`EdRueckweg.anlegen()`: WebCrypto erzeugt ein Paar auf P-256, der private
Teil (PKCS8) wird mit `EdCrypto.encrypt(ck, …)` zu `edk1:`, beides geht an
`api/rueckweg_anlegen.php`. **Das Vormerkfach löst `unlock.js` dafür selbst
auf**, wenn ein Paar fehlt — auch auf einer Seite, die den Schlüssel nicht
braucht (F-RW-14: Die Startseite eines Kontos ohne Diensttag löste es nie);
still, ohne Entsperrdialog, und über denselben einen Lauf wie
`ensureContentKey()`, damit die stille KDF-Anhebung nicht zweimal läuft. Der
Endpunkt verlangt Formular-Token **und** Anmelde-Token (Topf `login`, wie
`schluessel_erneuern.php`), prüft den öffentlichen Teil auf Form und Kurve,
den privaten auf `RW_PRIVAT_RE`, weist das Demo-Konto ab (403) und ein
vorhandenes Paar ohne `ersetzen` (409; die Bedingung steht im `UPDATE`
selbst). Protokoll `rueckweg_angelegt`, ohne Mail. **„Rückweg erneuern"**
in der Karte „Zweitfaktor" (nur mit eingeschaltetem Zweitfaktor und
vorhandenem Paar) fragt das Passwort: `EdSchluessel.oeffnen()` — die Schritte
1 bis 3 der Schlüsselerneuerung samt Wache gegen `pat_key_check`, seit RW-02
als eigene Funktion — liefert Inhaltsschlüssel und Token, dann ersetzt
`EdRueckweg.erneuern()`: Protokoll `rueckweg_erneuert`, Mail
`rueckweg_erneuert`. Beim Ausschalten des Zweitfaktors bleibt das Paar liegen
(E-RW-06); der Demo-Reset leert es in `demo_zweitfaktor_leeren()`.
Konto-Backup und Freigabe tragen es nicht (E-RW-09).

**Seit Web 20.45.0 (RW-03) steht der Weg am Code-Schritt.** `login.php` bietet
ihn an, wenn `rw_angeboten()` es sagt (Selbsttest grün, Paar und
Wiederherstellungs-Hülle da) — als dritten Verweis im Code-Schritt und im
Schritt „Wiederherstellungscode"; sonst sagt der zweite, dass die Verwaltung
hilft. `?weg=schluessel` zeigt den **Schlüsselschritt**: Jede Anzeige stellt
mit `rw_herausforderung_stellen()` eine neue Herausforderung in die halbe
Sitzung (`totp_halb.rw_herausforderung`, `rw_bis`, fünf Minuten) und liefert
in unbenannten versteckten Feldern die Wiederherstellungs-Hülle, den privaten
Teil und die fertige Nachricht aus `rw_nachricht()` (E-RW-18 — das Format
steht einmal). Im Browser öffnet `EdRueckweg.signieren()` mit dem Zettel die
Hülle, damit den privaten Teil, und signiert; passt der Zettel nicht, geht
**nichts** hinaus. Das Schlüsselfeld hat **keinen Namen** — gesendet wird nur
`signatur`. Auf dem Server prüft `rw_rueckweg_pruefen()`, was schiefgehen
kann: Herausforderung **sofort** verbrauchen, „wird angeboten?" (sonst 400),
Frist, Topf `totp` mit den Merkmalen des Code-Schritts, `rw_pruefen()` gegen
die **selbst gebaute** Nachricht; ein Fehlversuch zählt dort. **Das Ja
vollzieht `login.php`, in der Reihenfolge des Code-Schritts:** Erfolg zählen,
das Tor `login_zugang()`, die Sitzung — und erst **dahinter**
`totp_abschalten($id, 'schluessel')` (Protokoll `totp_zurueckgesetzt`,
`daten.weg = schluessel`, das Konto als Urheber) und die Mail
`totp_zurueckgesetzt` mit dem Satz des Wegs. Stünde das Abschalten vor dem
Tor, verlöre ein gesperrtes Konto oder eines während der Wartung seinen
Zweitfaktor ohne Anmeldung und ohne Mail (F-RW-21, Rückwegprobe B8). Die
Rolle user bekommt die Erfolgskarte, eine Pflichtrolle geht unmittelbar nach
`zweitfaktor.php` (Marke `zf_nach_rueckweg`, die Meldung oben, einmal). **`crypto.js`,
`rueckweg.js` und das Seitenskript stehen auf `login.php` immer da**,
PHP-frei: Die Integritätswache vergleicht die Seite ohne Sitzung, und ein
Skript nur im halben Stand fehlte ihr; das Formular `#schluesselform` steht
in `BEDINGTE_FORMULARE`. **Was ein Datenbankabzug kann:** prüfen, nicht
signieren — der private Teil liegt unter dem Inhaltsschlüssel, den nur Passwort
(samt Server-Anteil) oder Zettel öffnen (Tabelle unten; Rückwegprobe B7).

**Was ein Abzug enthält — die Konstruktion hinter „prüfen, nicht signieren"**
(aus Konzept RW 5.2, übernommen mit dem Abschluss von P5c). Ein „geht nicht"
lässt sich durch Ausprobieren nicht vollständig beweisen. Der Beleg hat
deshalb zwei Hälften: diese Tabelle — jeder Wert eines Datenbankabzugs ist
öffentlich, ein Hash oder ein Chiffretext unter einem Schlüssel, den der Abzug
nicht enthält — und die Messung der Rückwegprobe (B7: mit allen diesen Werten
in der Hand kommt kein Signaturversuch durch).

| Wert im Abzug | Was er ist | Öffnet er `rw_privat`? |
|---|---|---|
| `rw_oeffentlich` | öffentlicher Teil — darf jeder kennen | nein (prüft nur) |
| `rw_privat` | AES-GCM unter dem Inhaltsschlüssel | nur mit dem Inhaltsschlüssel |
| `pat_wrap_pw` | Inhaltsschlüssel unter dem Datenschlüssel (Passwort **und** `kdf_anteil` aus `config.php`) | nur mit Passwort und Anteil |
| `pat_wrap_rc` | Inhaltsschlüssel unter dem Wiederherstellungsschlüssel | nur mit dem Zettel |
| `pat_key_check` | 128-Bit-Hash des Inhaltsschlüssels | nein |
| `password_hash`, `kdf_salt`, `kdf_iter`, `session_epoch`, alle übrigen Spalten | Hashes, Parameter, Klartextangaben ohne Schlüsselcharakter | nein |
| `app_state` (Kennungen, Marken), `config.php` (`kdf_anteil`, `server_key`) | Server-Anteil und Serverschlüssel — **nicht** in der Datenbank; und selbst mit ihnen fehlt die PBKDF2-Hälfte | nein |

Was der Beleg **nicht** behauptet: dass Passwort plus Zettel nicht genügen
(sie genügen — das ist der Rückweg, derselbe Zuschnitt wie Passwort plus
Codeblatt), und dass ein Angreifer mit Zugriff auf den laufenden Browser der
NutzerIn nichts kann.

**Grenzen und Dauerwirkungen des Rückwegs** (aus Konzept RW 2 und 6):

- **Wer die Datenbank ändern kann, kann den öffentlichen Teil tauschen.** Ein
  Abzug genügt nicht; ein Schreibzugriff auf `users` würde genügen — und wer
  den hat, kann heute schon `password_hash` tauschen. Der Rückweg ändert an
  dieser Grenze nichts.
- **Wird der Inhaltsschlüssel je umgeschlüsselt** (heute wechselt er nie,
  R1), muss das Paar mit: `rw_privat` wird dann an derselben Stelle neu
  verpackt wie `pat_wrap_pw` und `pat_wrap_rc`. Wer ein Umschlüsseln baut,
  baut diese dritte Hülle mit.
- **Kein Paar beim Einschalten des Zweitfaktors** (E-RW-02, F-RW-04): Dort
  liegt kein Passwortnachweis vor, und wer den öffentlichen Teil schreiben
  darf, besitzt den Rückweg. Das Paar entsteht deshalb nur mit
  Anmelde-Token (Vormerkfach-Weg) oder nach Passwortabfrage („Rückweg
  erneuern"), ersetzt nie still und wird nur mit dem Konto gelöscht (E-RW-06).
- **P-256 und nicht Ed25519** (E-RW-03): gleichwertig, aber in WebCrypto die
  etabliertere Wahl, und phpseclib prüft sie in reinem PHP, wo ein Hoster
  keine `openssl`-Kurve hat (gemessen 214 ms).
- **Die Herausforderung liegt in der Sitzungsdatei** (`server/.sitzungen/`).
  Ohne den privaten Teil ist sie nichts wert, und sie verfällt nach fünf
  Minuten.
- **`pw_handling.php` prüft beim Passwort-Reset weiter gegen
  `pat_key_check`** — dort schützt der Wert vor einem Versehen, nicht vor
  einem Angreifer, und der Zweitfaktor steht danach weiter davor.
- **`PAT_KEY_CHECK` steht im Seitenquelltext** jeder Seite, die ihn für den
  Freigabe-Vergleich braucht (`einstellungen.php`, F-RW-06): ein
  Verifizierer, kein Wissensnachweis — genau deshalb prüft der Rückweg eine
  Signatur und nicht ihn (Backlog Nr. 319).
- **Konten ohne Inhaltsschlüssel** (Passwort nie gesetzt) bekommen kein Paar
  und brauchen keins.

Die Migration heißt `2026_09_24_rueckweg_schluesselpaar` (drei Spalten an
`users`).

**Stumm nur ohne Spalten — ein Fehler ist kein Nein** (F-P5c-166, seit Web
21.1.1). Zwischen Deploy und `update.php` gibt es die Spalten nicht, und dann
schweigen Code-Schritt, Einrichtungstor und Rückweg (E-P5c-36, -53). Bis Web
21.1.0 hielten `totp_spalten_da()`, das Einrichtungstor in `auth_guard.php`
und `rw_zustand()` **jeden** Fehler der Datenbank dafür — und `login.php`
meldete dann ohne Code-Schritt an. Jetzt gilt nur die fehlende Spalte
(`db_hat_spalte()` = nein bzw. SQLSTATE 42S22) als dieses Fenster; jeder
andere Fehler bricht die Anfrage ab. Gemessen mit einer gestörten Verbindung:
`totp_spalten_da()` in der Zweitfaktorprobe (Teil 2b), `rw_zustand()` in der
Rückwegprobe (A7, dort auch die Lage „spalten" als 42S22 — bis Web 21.1.0
stellte eine SQLite-Datenbank sie nach, die HY000 meldet, und die Lage war
nur grün, weil der Fehler geschluckt wurde).

**Der Bus-Faktor** (E-P5c-16, -56): Die Zeile „Verwaltungskonten" auf Betrieb
→ Status zählt **handlungsfähige** Konten — `status = 'aktiv'` und ein
eingeschalteter Zweitfaktor (`status_verwaltungskonten()`). Orange, solange
weniger als zwei BetreiberInnen handlungsfähig sind (E-P5c-44, -63). Text,
Ton und Plakette wählt `status_verwaltungszeile()` aus den drei Zahlen, ohne
Datenbank — damit die Probe die Lagen mit gesetzten Zahlen prüfen kann
(E-P5c-113).

**Prüfkonten** (E-P5c-43): Das Sandbox-Prüfkonto bekommt einen echten
Zweitfaktor mit bekanntem Geheimnis (`tools/zweitfaktor/pruefkonto.php`,
Schritt 6b von `lokal_einrichten.sh`); die Werkzeuge rechnen den Code mit
**einem Rechner je Sprache** (`tools/zweitfaktor/totp.{php,mjs,py}`) und
teilen einen Zähler, weil der Server keinen Code zweimal annimmt. Auf Staging
reicht die Kette das Secret `STAGING_TOTP` an den Kreislauf durch. **Kein
Konfigurationsschalter**, der die Pflicht abschaltet.

**Nachweis:** `bash tools/proben/proben.sh zweitfaktor` — RFC-Vektoren 6/6,
Code-Schritt über HTTP, Tor, Rückzug der Selbstlöschung, Demo-Reset,
Bus-Faktor samt der Tabelle seiner Lagen. Dazu zwei Bedienwege
(`tools/bedienprobe/wege/zweitfaktor.mjs`: QR-Code mit jsQR gelesen gleich
der angezeigten Adresse; `wege/einstellungen_profil.mjs`: einschalten,
falscher, wiederholter und richtiger Code, Vormerkfach nach dem Abbruch
leer). Für den Rückweg: `bash tools/proben/proben.sh rueckweg` (Signaturen,
Selbsttest, Marke, `rw_zustand()` in vier Lagen, Kontopaket ohne `rw_`), die
Rollenprobe (jede Rolle erreicht `api/rueckweg_anlegen.php` und legt ab) und
der Bedienweg `wege/einstellungen_profil_rueckweg.mjs` (Paar entsteht beim
Anmelden, Endpunkt weist ab, Karte, Erneuern, Demo-Konto ohne Paar). Seit
RW-03 hat die Rückwegprobe zwei Teile unter einem Namen: `probe.php` Teil B
misst `rw_rueckweg_pruefen()` ohne HTTP (echt, wiederholt, abgelaufen,
fremd/verändert/zweckfremd bis zur Sperre im Topf `totp`, die alte Fassung mit
`pat_key_check`, nicht angeboten, die Abzug-Gegenprobe) und über HTTP den
Verweis, die 400, den ganzen Weg mit echter Signatur und dieselbe Signatur an
einem gesperrten Konto (Zweitfaktor bleibt an); `probe.mjs` fährt den Weg im
Browser als NutzerIn und als BetreiberIn — örtlich und in Stufe 2 gegen
Staging, von Hand mit `--motor` auch in Firefox und WebKit (RW-04). Beide
Teile startet `tools/proben/rueckweg/probe.sh`, seit dem Aufnehmen von
Konzept BR die Einstiegsdatei im Läufer.

### 4.99r Der Health-Endpunkt (ab Web 20.46.0, P5c/AP6)

*E-P5c-17, -52; R38. Code: `server/api/health.php`; Topf `health` in
`ratelimit_lib.php`; die gemerkten Anteile in `speicher_lib.php`.*

**Wozu.** Die BetreiberIn trägt eine Adresse in ihr eigenes Monitoring ein
und erfährt, ob die Anlage läuft — ohne sich anzumelden und ohne dass die
Anwendung einen Fremddienst anbindet. Der Endpunkt antwortet nur, wenn er
gefragt wird.

    GET /api/health.php?token=<betrieb.health_token>

**Der Token steht in `config.php`** (`'betrieb' => ['health_token' => …]`,
Vorlage in `config.example.php`). **Leer heißt: Endpunkt aus.** Er steht
dort und nicht in der Oberfläche, weil er in einem fremden Monitoring
steht: Wer ihn wechselt, wechselt ihn dort mit.

**Antworten.**

| Lage | HTTP | Rumpf |
|---|---|---|
| alles in Ordnung | **200** | die Felder unten, `ok: true` |
| Datenbank fort oder Migration ausstehend | **503** | die Felder unten, `ok: false` |
| Token fehlt, ist falsch oder keiner eingerichtet | **403** | `{"error":"token"}` — dreimal dieselbe Antwort, mit `hash_equals()` verglichen und mit angeglichener Dauer (`rate_gleiche_dauer()`): Sie verrät nicht, **ob** ein Token eingerichtet ist |
| mehr als 60 Anfragen je Minute und Adresse | **429** | `{"error":"zu_viele_versuche"}` |
| Wartung | **503** | `{"error":"maintenance", …}` — **aus dem Tor in `db.php`**, bevor der Endpunkt eine Zeile ausführt, ohne Token-Prüfung |
| Überlast | **503** | `{"error":"ausgelastet"}` aus `ueberlast_antwort()` — **nicht** aus dem Tor, sondern aus `db()` beim ersten Zugriff, und das ist der Ratenschutz, noch vor dem Token. Ein POST bekommt deshalb bei Überlast 405. Hier stand bis Web 21.1.0 „ebenfalls aus dem Tor" |
| andere Methode als GET | **405** | `{"error":"method"}` (`api_methode()`) |

**Die Felder** (E-P5c-52) — und nichts darüber hinaus: keine Konten, keine
Mengen, nichts über den Hoster.

| Feld | Bedeutung | Quelle |
|---|---|---|
| `ok` | Datenbank erreichbar **und** keine Migration ausstehend | — |
| `web_version` | die ausgelieferte Fassung | `WEB_VERSION` |
| `db` | die Datenbank antwortet | `SELECT 1` |
| `migration_ausstehend` | `update.php` muss laufen — oder eine Migration ist blockiert (Inhalt oder Vorbedingung) und steht auch danach noch aus | `migrationen_ausstehend()` (der gemerkte Stand des Torwächters; zählt blockierte mit) |
| `jobs_alter_s` | Sekunden seit dem letzten Lauf irgendeines Jobs, `null` = nie | `MAX(jobs.letzter_lauf)` |
| `system_24h` | Einträge im Reiter System der letzten 24 h | `protokoll_zahl('system', ['tage' => 1])` |
| `protokoll_fehler` | gescheiterte Protokolleinträge, bis jemand quittiert | `protokoll_fehler_zahl()` |
| `speicher_pct` | der höchste der drei Anteile, `null` = nie gemessen oder kein Bezug | `speicher_prozent_hoechster()` |

**Ein Feld `wartung` gibt es nicht** — in der Wartung antwortet das Tor, es
wäre nie `true` gewesen (F-P5c-25). Was `error: maintenance` heißt, sagt das
Handbuch (12.9).

**Kein Verzeichnislauf je Abruf.** Ein Monitoring fragt einmal je Minute.
`speicher_pct` liest deshalb, was der tägliche Aufräumjob beim Schritt
„Speicher messen" merkt: Datenbank gegen ihr Kontingent, Backups gegen die
Speichergrenze, alles gegen den Webspace (`app_state.speicher_prozent`,
abgerundet wie die Balken; ohne Bezug kein Wert). Bis zum ersten Lauf nach
dem Deploy steht `null`. Die übrigen Felder sind vier kleine Abfragen.

**Jeder Teil darf scheitern, ohne die Antwort zu kippen.** Fehlt die
Datenbank, kommt `db: false` mit 503 — nicht eine PHP-Fehlerseite; fehlt eine
Zahl, steht `null`, und der Grund im Reiter System (Bereich `health`).

**Topf `health`**: 60 je Minute und Adresse, **zählt die Menge** (jede
Anfrage, auch die richtige — Muster `csp`), **ohne Leiter**: Eine wachsende
Sperre träfe das Monitoring der BetreiberIn, nicht einen Angreifer, der den
Token nicht kennt.

**Nachweis:** Ratenprobe Abschnitt 11 (über HTTP: 403 dreimal mit gleicher
Dauer, 200 mit genau diesen Feldern, 503 bei ausstehender Migration,
`speicher_pct` gestellt, ohne Messung und nach `speicher_messen()` gegen die
Balken der Karte „Speicher", 60 durch und die 61. mit 429) und Wartungsprobe Fall 5a (503 `maintenance` aus dem
Tor, mit falschem und ohne Token, der Topf zählt nichts).

### 4.99l Mengengrenze je Konto (ab Web 20.21.0, P5b/AP6)

*E-P5b-04, -18; Backlog Nr. 37 und 48. Code: `konten_einstellungen_lib.php`
(`konto_mengen()`, `konto_grenzen()`, `konto_fuellstand()`), `spur_bytes()` in
`spur_lib.php`.*

#### Zwei Grenzen, der größere Anteil zählt

| | Vorgabe | je Konto |
|---|---|---|
| Einsätze | `konten_grenze_einsaetze` (5000) | `users.grenze_einsaetze` |
| Speicher | `konten_grenze_mb` (250) | `users.grenze_mb` |
| Konto-Backups | `adminbackup_aufbewahrung` (2) | `users.backup_pakete` |

**`NULL` heißt „die Vorgabe gilt"** — nicht 0 und nicht die Vorgabe als Zahl.
Trüge die Spalte den Wert, änderte eine spätere Anhebung der Vorgabe an
bestehenden Konten nichts, und niemand sähe, warum.

**Der größere der beiden Anteile zählt**, nicht der Durchschnitt: Wer 5000
Einsätze mit wenigen GPS-Daten hat, ist genauso am Ende wie jemand mit 250 MB
in dreihundert Aufzeichnungen. Eine gemittelte Zahl ließe beide weiterladen,
bis eine Grenze weit überschritten ist.

#### `507`, und warum nicht `403` oder `429`

`507 Insufficient Storage` sagt „der Server hat keinen Platz mehr". `403`
hieße „du darfst nicht", `429` hieße „nicht so schnell" — beides wäre falsch
und ließe die Uhr das Falsche tun. Bei `507` wie bei `429` behält sie ihre
Warteschlange und sendet später.

**Bearbeiten und Löschen bleiben frei.** Die Grenze steht in `ingest.php` und
im Import, nirgends sonst. Wer sie erreicht, muss aufräumen können. Aus
demselben Grund zählt, was im **Papierkorb** liegt, nicht mit — sonst ließe
sich die Grenze durch Löschen nicht unterschreiten, und genau das schlägt die
Meldung vor.

**Der Ratenschutz zählt die Absage nicht.** Ein volles Konto ist kein Angriff.

#### Gemessen wird gecacht, fortgeschrieben wird geschätzt

Die Byte-Messung liest die Blob-Längen **aller** GPS-Daten eines Kontos. Bei
jedem Upload wäre das genau an dem Weg teuer, der schnell sein muss.

| Wann | Wie |
|---|---|
| nach jedem Upload-Schub | **geschätzt** fortgeschrieben (`SPUR_ZEILE_BYTE` je Punkt) |
| Aufräumjob, einmal am Tag | **gemessen**, Cache ersetzt |

Format in `app_state` unter `mengen:<id>`: `<einsaetze>\|<bytes>\|<zeitstempel>`
— drei Zahlen mit Trennstrich, weil `app_state.v` 190 Zeichen fasst und JSON
dort keinen Platz hätte.

**Ohne die Fortschreibung griffe die Grenze erst einen Tag später**, und ein
Konto könnte an einem Tag beliebig weit darüber hinauswachsen.

#### `spur_bytes()` steht in `spur_lib.php`

Nicht beim Aufrufer, und das ist die Regel aus `CLAUDE.md` 4: Die Punkte
liegen je nach Alter als Zeilen in `track_points` **oder** als Blob in
`track_blobs` — und während einer Nachlieferung als beides. Wer nur eine der
beiden Tabellen zählt, misst je nach Bestand die Hälfte, ohne Fehlermeldung.

**Geschätzt und nicht gewogen**: Für `track_points` steht ein fester Wert je
Zeile (`SPUR_ZEILE_BYTE`, 48), weil `information_schema` nur Tabellensummen
kennt; für `track_blobs` wird `LENGTH()` gezählt — dort steckt die Masse. Die
Grenze ist damit eine **Schätzung mit bekanntem Fehler, keine Abrechnung**,
und das ist ehrlicher, als eine Zahl auf das Byte genau auszuweisen, die es
nicht ist.

#### Backlog Nr. 48 ist die Zahl der Backup-Pakete, nicht eine Frist

**Und das ist beim Bauen zuerst falsch verstanden worden**, deshalb steht es
hier: Nr. 48 („Aufbewahrung je Konto einstellbar, nicht nur je Installation")
meint `adminbackup_aufbewahrung` — **wie viele Konto-Backups aufgehoben
werden**, bevor der älteste verdrängt wird. Es meint **keine
Aufbewahrungsfrist für Einsätze**; die gibt es nicht, und sie soll es auch
nicht geben: Einsatzdaten von selbst verschwinden zu lassen wäre eine Zusage,
die dieses Projekt nicht macht.

Umgesetzt als `users.backup_pakete` und `edbak_aufbewahrung_konto($kennung)`,
gelesen in `edbak_verdraengen()`. **Keine zweite Installationsvorgabe** — die
gibt es schon unter Verwaltung → Konto-Backups; eine zweite Zahl daneben wäre
genau die Doppelung, die R83 verhindern soll.

`edbak_aufbewahrung_konto()` geht **über die Kontokennung**, nicht über die
Id: `edbak_verdraengen()` arbeitet auf dem Ordner, und der heißt nach der
Kennung. Ein Ordner ohne Konto („Backup ohne Konto") fällt auf die
Installationszahl zurück — es gibt niemanden mehr, der etwas anderes bestimmen
könnte.

#### Die Marken sterben mit dem Konto

`mengen:<id>` und `mengen_gemeldet:<id>` hängen an keinem Fremdschlüssel;
`konto_loeschen()` räumt sie deshalb ausdrücklich mit, und der Aufräumjob holt
verwaiste aus der Zeit davor. **`users.id` ist AUTO_INCREMENT, aber ein
Wiederanlauf aus einer Sicherung kann eine Id erneut vergeben** — das neue
Konto fände dann den Mengenstand des alten vor und stünde womöglich sofort an
seiner Grenze, ohne einen einzigen Einsatz. Genau das ist beim Prüfen
passiert.

#### Was hier NICHT gebaut wurde

**Die Umstellung der Geräteschlüssel auf SHA-256** (E-P5b-17). Sie ist seit
**Web 13.0.0** erledigt — `geraet_schluessel_gueltig()` rechnet
`hash_equals(hash('sha256', …))`, und der Kommentar daneben trägt die Messung,
die sie ausgelöst hat: bcrypt kostete 228 ms je Upload „für eine Bremse, die
nichts bremst". Das Konzept beschreibt in 1.3 einen Stand von vor Web 13.0.0.

### 4.99m Die Selbstregistrierung (ab Web 20.22.0, P5b/AP3)

*E-P5b-01, -02, -03, -13, -23. Code: `server/registrieren.php`,
`server/bestaetigen.php`, `server/konten_einstellungen_lib.php`
(`wegwerf_trifft()`), `server/ratelimit_lib.php` (drei Töpfe),
`server/konto_lib.php` (Verfall, Sammelmeldung), `server/jobs_lib.php`
(`konto_verfall`), `server/wegwerfdomains.txt`.*

**Der Weg in fünf Schritten.**

1. `registrieren.php` nimmt **Adresse, Name und drei Häkchen** — kein
   Passwort (Begründung unten). Antwort ist für jeden Ausgang dieselbe Karte.
2. Bei freier Adresse entsteht ein Konto im Zustand **`unbestaetigt`**
   (`konto_anlegen(…, 'registrierung', 'unbestaetigt', …, TOKEN_REGISTRIERUNG_S)`),
   dazu ein **48 Stunden** gültiger Token. Die Mail `registrierung` trägt den
   Link.
3. Der Link führt auf **`pw_handling.php`** — dieselbe Seite wie beim
   Einladungsweg. Dort entstehen im Browser Passwort, Datenschlüssel und
   Wiederherstellungsschlüssel.
4. Nach dem Speichern wechselt der Status: bei *offen* auf **`aktiv`**, bei
   *mit Freischaltung* auf **`wartet`**; im zweiten Fall geht eine Sammelmail
   an die Verwaltung.
5. Weiterleitung auf **`bestaetigen.php?s=…`**, das sagt, wie es weitergeht.

**Warum das Passwort nicht auf der Registrierungsseite steht.** Der
Datenschlüssel hängt seit S10 am Server-Anteil, und der wird per
`HMAC(kdf_anteil, 'konto:<id>')` aus der **Kontonummer** abgeleitet (4.98,
E-S10-17). Eine Registrierungsseite hat sie nicht — das Konto entsteht ja erst
mit ihr. Der Browser kann `pat_wrap_pw` also nicht bilden, und eine Hülle ohne
Anteil weist `huelle_pw_pruefen()` ab, sobald die Installation einen
ausliefert. **Konto zuerst, Schlüssel danach** ist damit keine Vorliebe,
sondern die einzige Reihenfolge, die geht. Das freigegebene Mockup M-P5b-02a
zeichnet die Felder dort; die Abweichung ist am 17.09.2026 mit dem
Auftraggeber geklärt.

**Keine Kontoauskunft.** Fünf Ausgänge, eine Antwort: frei, belegt,
Wegwerfadresse, Demo-Adresse, gesperrter Ratenschutz. Unterschieden wird in
der **Mail** — `registrierung` an die freie, `registrierung_bekannt` an die
belegte, keine an die übrigen. Die Dauer ist angeglichen
(`rate_gleiche_dauer($t0, REG_MINDESTDAUER)`, 0,5 s Boden) und der Versand
läuft **nach** `antwort_abschliessen()`; ohne beides wäre die Dauer die
Auskunft, die der gleiche Text verhindert (M1-07). Gemessen über 120 Aufrufe:
Spanne der Mediane **0,2 ms**.

**Die drei Bremsen.**

| | was | warum still |
|---|---|---|
| Honeypot | Feld `website` in `.nur-vorlesen`, mit `aria-hidden` und `tabindex="-1"` | `display:none` füllt kein Bot; ohne `aria-hidden` wäre es eine Falle für Bildschirmleser |
| Mindestdauer | signierter Zeitstempel, 4 s bis 2 h gültig (`reg_stempel()`) | ohne Signatur bestimmt der Absender die Zahl selbst |
| Töpfe | `reg` 10/h je IP · `regg` 100/h global · `regz` **3/24 h je Zieladresse** | eine Meldung „Honeypot gefüllt" wäre eine Bauanleitung |

`regz` ist der wichtigste: Ohne ihn verschickt die Seite an **jede**
eingetippte Adresse eine Mail, ohne dass der Absender sie besitzen muss. Sein
Merkmal ist ein **Hash** der Adresse (`rate_reg_ziel()`) — `rate_limits` wäre
sonst ein Verzeichnis fremder Postfächer in derselben Datenbank, die auch ein
Angreifer abzieht.

**Die Wegwerfliste.** `server/wegwerfdomains.txt`, 8 883 Domains, CC0 1.0,
eine je Zeile, durchgehend klein. **Nie zur Laufzeit geholt** (R36);
`wegwerf_trifft()` liest sie je Anfrage einmal in eine `static`. Die Prüfung
geht von der vollen Domain nach oben (`a.b.example.com` → `b.example.com` →
`example.com`), damit Unterdomains mitzählen — sie hört **vor** der Endung auf,
denn eine Liste, die `com` sperrte, sperrte das halbe Netz. Fehlt die Datei,
trifft nichts: Eine Installation, die alle Registrierungen abweist, *weil* eine
Datei fehlt, wäre das Gegenteil des Schalters. Pflege:
`tools/erzeugen/wegwerfdomains.py` (Runbook, Abschnitt 7; Backlog
Nr. 230), Herkunft in `docs/Lizenzen.md` 7b.

**Zwei Fristen, zwei Zustände, ein Job.** `konto_verfall` löscht
unbestätigte Konten nach **48 h** (`KONTEN_UNBESTAETIGT_H`, fest — eine
Sicherheitsfrist) und wartende nach der eingestellten Frist (Vorgabe 30 Tage,
1–365). Gemessen ab `created_at` bzw. `bestaetigt_am`; **deshalb** schreibt
`konto_status_setzen()` beim Übergang nach `wartet` jetzt `bestaetigt_am` mit.
Gelöscht wird über **`konto_loeschen()`** und nicht per `DELETE` — die Kaskade
erreicht die GPS-Spuren nicht und die `app_state`-Zeilen `mengen:<id>` erst
recht nicht (4.99l). Die letzte Mail geht **nur an die Wartenden**.

**Die Freischaltung** ist der vorhandene Knopf in der Statuskarte der
Kontoseite; neu ist die Mail `freigeschaltet` beim Übergang **`wartet` →
`aktiv`** — beim Entsperren (`gesperrt` → `aktiv`) geht keine. Gefunden werden
die Wartenden über den Filter **„Wartet auf Freischaltung"** in
`admin_users.php`; die Liste liest `status` seit Web 20.22.0 mit und zeigt ihn
als Plakette neben der Adresse (keine neunte Spalte — in 95 von 100 Zeilen wäre
sie leer).

### 4.99n Handbuch und „Was ist NAdoku" als Seiten (ab Web 20.23.0, P5b/AP8)

*E-P5b-08, -22; Mockup M-P5b-01. Code: `server/doku_lib.php`,
`server/doku_seite.php`, `server/hilfe.php`, `server/ueber.php`,
`server/assets/doku.js`, `server/vendor/Parsedown.php`.*

Die Quelle bleibt Markdown im Repositorium: `docs/Handbuch.md` und
`docs/Was-ist-NAdoku.md`. Auf GitHub editierbar, die Wortliste läuft darüber,
das Prüftor prüft die Rendertauglichkeit — und es gibt **keine zweite Fassung
in einer Datenbank**, die auseinanderlaufen könnte.

#### Zwei Orte, und eine Asymmetrie, die man kennen muss

`doku_pfad()` sucht erst `../docs/` (Selbsthosterinnen laden das Repositorium
hoch), dann `server/doku/` (dorthin kopiert die Kette). **Für den TEXT genügt
das, für BILDER nicht** — und das ist der Punkt, an dem sich zwei Welten
treffen:

- Den **Text** liest PHP aus dem Dateisystem. `../docs/` ist dort ein ganz
  normaler Ort.
- Ein **Bild** holt der **Browser**. Der sieht nur, was unterhalb des
  Dokumentenstamms liegt, also `server/`. `../docs/` ist für ihn unerreichbar,
  und das soll so bleiben.

Deshalb schreibt `DokuMarkdown::inlineImage()` jede relative Bildquelle auf
`doku/…` um, und die Kette kopiert `docs/bilder/` mit. Wer selbst hostet und
nur `docs/` neben `server/` legt, bekommt den Text und keine Bilder; dann
fehlt derselbe Kopierschritt.

**Der Fehler ist teuer, weil er leise ist:** Ohne die Umschreibung löste
`![](bilder/x.png)` zu `/bilder/x.png` auf — `server/bilder/`, wo nichts
liegt. Der Bilderlauf meldete dafür **24 Konsolenfehler**; auf dem Server
wären es drei kaputte Bilder in einem Handbuch gewesen, das ohne sie noch
lesbar ist.

#### Was der Renderer darf

`Parsedown` mit **beidem**: `setMarkupEscaped(true)` (rohes HTML wird Text)
und `setSafeMode(true)` (Zielprüfung an Links). Dazu drei eigene Regeln in
`DokuMarkdown`:

| Regel | wofür |
|---|---|
| Sprungmarken an jeder Überschrift, h2/h3 zusätzlich ins Verzeichnis | `hilfe.php#abschnitt` als Ziel für Hilfe-Verweise |
| `rel="noopener"` an fremden Zielen | E-P5b-22; ohne `target` streng genommen überflüssig, aber dann schon da |
| **Bilder nur relativ** | die Zusage „keine fremde Quelle zur Laufzeit" |

**Die dritte ist die einzige, die wirklich etwas trägt** — und sie ist
nachgemessen, nicht vermutet. Parsedown liefert **mit** SafeMode für
`![B](https://fremd.example/b.png)` ein `<img src="https://fremd.example/…">`.
SafeMode prüft das **Schema**, nicht die **Herkunft**. Auf einer Seite, die
jede Besucherin vor der Anmeldung sieht, steht zwischen dem Handbuch und einem
fremden Server allein dieser Überschreiber.

#### Kein Cache — entgegen dem Konzept

E-P5b-22 verlangt einen Cache in `app_state`. Er ist **unmöglich**
(`app_state.v` ist `VARCHAR(190)`, das gerenderte Handbuch 303 KB) und
**unnötig**: 266 KB Markdown rendern in **11 bis 12 ms**. Die Begründung steht
im Kopf von `doku_lib.php`. Wäre er je nötig, gehörte er in eine **Datei**
neben der Quelle.

#### Wo die Seiten auftauchen

- **Fußzeile der Anmeldeseite** (`.fuss-anmeldung`): Was ist NAdoku? ·
  Handbuch · Impressum · Datenschutz — **unter** der Karte (bis Web 20.37.2
  stand sie daneben, Nr. 289). Der erste Link ist der einzige Weg, auf
  dem jemand **ohne Konto** erfährt, was diese Anwendung ist.
- **Kopfleiste, angemeldet:** ein Fragezeichen links vom Zahnrad
  (`.kopf-hilfe`), das auch auf dem Handy stehen bleibt.
- `ueber.php` zeigt am Ende „Konto anlegen" **nur, wenn die Registrierung
  offen ist** — sonst führte der Knopf auf eine Seite, die absagt.

#### Grenzen der Prüfmittel an dieser Seite

`hilfe.php` zeigt das ganze Handbuch auf einer Seite. Der Bilderlauf kann sie
**nicht ganzseitig** fotografieren: 19 MB je Abzug, und ab 1024 px scheitert
Chromium an seiner Höchsthöhe. Der Eintrag trägt deshalb
`"ganzseitig": false` in `seiten.json` — das Bild ist ein Ausschnitt, die
**Messungen** (Überlauf, Konsolenfehler, Knopfhöhen) laufen weiter über das
ganze Dokument.

### 4.99o Einstiege nach der Anmeldung (ab Web 20.24.0, P5b/AP9)

*E-P5b-09, -10, -19, -20, -21; Mockups M-P5b-02b/c/d. Code:
`server/einstieg_lib.php`, `server/erststart_karte.php`,
`server/rueckfrage_dialog.php`, `server/schluessel_teile.php`,
`server/blatt_dialog.php`, `server/notfallblatt.php`,
`server/assets/rueckfrage.js`, `server/assets/schluessel.js`,
`server/assets/schluesselblatt.js`, `server/api/rueckfrage.php`,
`server/api/schluessel_erneuern.php`,
`server/api/schluesselblatt_pruefen.php`.*

Nach dem Anmelden kann viererlei anstehen. Es erscheint **genau eines**, in
dieser Reihenfolge:

| # | Was | Form | Fällig |
|--:|---|---|---|
| 1 | Einwilligungstor | eigene **Seite** (`einwilligung.php`) | solange eine Annahme fehlt (4.99j) |
| 2 | Schlüsselblatt-Rückfrage | Dialog, nur BetreiberIn | alle 3 Monate, installationsweit |
| 3 | Konto-Rückfrage | Dialog | nach 30 Tagen, 6 Monaten, dann jährlich |
| 4 | Erststart | **Karte** über der Tagesübersicht | solange ein Schritt offen ist |

`einstieg_faellig($userId, $istBetreiberin, $uebergehen)` gibt den Schlüssel
des ersten fälligen Einstiegs zurück oder `null`. Der dritte Parameter nennt,
was die Sitzung schon gezeigt hat — ohne ihn verdeckt eine weggeklickte Frage
alle folgenden, weil die Funktion nur die erste herausgibt.

Das Einwilligungstor steht in der Liste, aber nicht im Code dieser
Bibliothek: Es ist eine Umleitung in `auth_guard.php` und läuft, bevor eine
Seite etwas ausgibt.

#### Der vierte ist kein Dialog

E-P5b-19 zählt alle vier in einer Reihe auf und schreibt „die Seite zeigt
genau einen Dialog". Das freigegebene Mockup M-P5b-02b sagt es genauer:
**„Die Karte steht über der Tagesübersicht, nicht als Dialog: Wer sie
ignoriert, arbeitet trotzdem."**

Der Unterschied ist keine Geschmacksfrage. Die Rückfragen sind
Sicherheitsfragen mit einer Frist — sie dürfen stören. Der Erststart ist eine
Einladung; wer ihn wegklicken muss, um an seine Diensttage zu kommen, lernt in
der ersten Minute, dass diese Anwendung im Weg steht.

#### Zustand am Konto, nicht in den Stammdaten

| Spalte | Was |
|---|---|
| `users.erststart_stand` | Bitfeld der drei Schritte (1 Standort, 2 Rettungsmittel, 4 Gerät); **`-1` = nicht mehr zeigen**, deshalb `TINYINT` mit Vorzeichen |
| `users.rueckfrage_naechste` | Datum der nächsten Konto-Rückfrage, `NULL` = Uhr läuft noch nicht |
| `users.rueckfrage_runde` | 0 → 30 Tage, 1 → 6 Monate, 2+ → jährlich |
| `users.rueckfrage_verschoben` | Zähler für „Später", höchstens 3 je Runde |
| `app_state.schluesselblatt_bestaetigt_am` | installationsweit, für die Betreiber-Rückfrage |

Ein `SELECT COUNT(*) FROM bases` wäre für den Erststart einfacher und wäre
falsch: „Ich brauche keinen Standort" ist eine Antwort, und wer den Schritt
bewusst übergeht, soll ihn nicht bei jedem Anmelden wiedersehen.

Die Uhr der Konto-Rückfrage startet bei der **Anmeldung**
(`rueckfrage_anstossen()` in `login.php`), nicht beim Anlegen des Kontos, und
nur wenn `pat_wrap_rc` gesetzt ist: Ein eingeladenes Konto ohne gesetztes
Passwort hat kein Notfallblatt, und es 30 Tage später danach zu fragen wäre
eine Frage ohne Gegenstand.

#### Das Notfallblatt speichert nichts — und kann nichts speichern

`notfallblatt.php` bekommt den Wiederherstellungsschlüssel **per POST aus dem
Browser** und gibt ihn in derselben Antwort zurück. Er wird nicht gelesen,
nicht geschrieben, nicht protokolliert. Das ist keine Vorsicht, sondern die
einzig mögliche Bauform: Der Server kennt diesen Schlüssel nicht.

Daraus folgt die Eigenschaft, die das Blatt ausmacht: **Es lässt sich später
nicht erneut drucken.** Wer es verliert, erzeugt einen neuen Schlüssel — das
alte Blatt wird damit ungültig, und genau das steht darauf.

**Kein `auth_guard.php`, und das ist kein Versehen.** Der Schlüssel wird an
zwei Stellen gezeigt, und an einer davon gibt es noch keine Sitzung: beim
ersten Setzen des Passworts (`pw_handling.php`). Was stattdessen schützt: Die
Seite gibt nur wieder, was ihr gesendet wurde, und zwar **geprüfte Werte in
festem Text** — der Code muss dem Format entsprechen (20 Zeichen aus dem
Alphabet ohne 0/1/I/L/O/U, fünf Vierergruppen), die Adresse muss eine Adresse
sein; alles andere wird ausgelassen. Kein freier Text, kein Verweis nach
draußen, kein Markup aus der Eingabe.

#### Die Schlüsselerneuerung: eine Komponente, zwei Verbraucher

`assets/schluessel.js` rechnet, `api/schluessel_erneuern.php` schreibt,
`schluessel_teile.php` zeigt. Aufrufer sind der Rückfrage-Dialog und die Karte
unter **Einstellungen → Profil** (R83).

Der Weg im Browser:

1. Passwort → Datenschlüssel (`deriveKeys`, `datenschluessel`)
2. damit `pat_wrap_pw` öffnen → der **Inhaltsschlüssel** (ck)
3. **ck gegen `pat_key_check` halten** — die Wache
4. neuen Code erzeugen (`newRecoveryCode`), ck damit neu verpacken
5. `api/schluessel_erneuern.php` → schreibt **genau** `pat_wrap_rc`
6. den Code anzeigen

**Schritt 3 ist die Stelle, an der Daten verlorengehen könnten.** Öffnete
Schritt 2 aus irgendeinem Grund einen *anderen* Schlüssel, verpackte Schritt 4
diesen, der Server nähme ihn an, und der neue Zettel öffnete **nichts** — der
alte wäre schon überschrieben. Konten ohne `pat_key_check` (vor Web 10)
bekommen die Funktion nicht, statt sie ungeprüft zu benutzen.

Drei Feinheiten, die je einen Betriebsfall entscheiden:

- `datenschluessel()` und **nicht** `datenschluesselZu()`: Die erste liest die
  Anteilskennung aus dem **Präfix der zu öffnenden Hülle**, die zweite erzwingt
  eine genannte. Während einer Anteilsrotation trägt die alte Hülle noch den
  alten Anteil — mit der zweiten wäre die Erneuerung für jedes Konto
  gescheitert, das sich seither nicht angemeldet hat.
- `encrypt()` und **nicht** `huelleBauen()`: `pat_wrap_rc` hängt **nicht** am
  Server-Anteil (E-S10-04). Der Server weist eine `edka1:`-Hülle zusätzlich ab
  (`huelle_rc_pruefen()`).
- **Das Passwort wird verlangt, obwohl die Sitzung steht.** Nicht wegen der
  Verschlüsselung — eine entsperrte Sitzung hätte den ck bereits —, sondern
  weil sonst jemand mit einer übernommenen Sitzung den Zettel des Opfers
  ungültig machen könnte: kein Datenklau, aber der lautlose Verlust des
  Rückwegs. Geprüft wird serverseitig, und der Endpunkt zählt in den Topf
  `login`.

`session_epoch` bleibt unberührt: Am Schlüssel der offenen Sitzungen hat sich
nichts geändert.

#### Die Betreiber-Rückfrage wird eingegeben, nicht bestätigt

`api/schluesselblatt_pruefen.php`, zwei Aufrufe:

- `aktion=stellen` — der Server würfelt je Wert (Serverschlüssel,
  Server-Anteil) **zwei verschiedene Gruppenpositionen** aus 1–16, legt sie in
  **seine Sitzung** und nennt nur die Nummern. Ein verstecktes Feld wäre vom
  Browser wählbar; wer die Positionen selbst bestimmt, sucht sich die zwei
  aus, die er kennt.
- `aktion=pruefen` — vier Antworten, Vergleich mit `hash_equals()` gegen die
  Gruppen aus `config.php`, **alle vier ohne Abkürzung bei der ersten
  Abweichung**. Ein Abbruch wäre an der Antwortzeit messbar und machte aus
  einem Rätsel zu sechzehn Zeichen vier Rätsel zu vier.

Gezeigt wird nie ein Wert, nur die achtstellige **Kennung** — die Regel des
Hauses; `betrieb_schluesselblatt.php` ist ihre einzige Ausnahme. Drei
Fehlversuche → Topf **`blatt`** (erste Sprosse 15 Minuten, dann steigend — die
Leiter überholt den Wert in `sperre`), Eintrag in
`sicherheit_ereignisse` ohne Werte. Erfolg setzt
`app_state.schluesselblatt_bestaetigt_am` und schreibt ins Protokoll (Reiter
Verwaltung).

**Keine Schlüsselerneuerung an dieser Stelle.** Den Serverschlüssel zu wechseln
hieße, jede versiegelte Sicherung neu zu umhüllen — ein S10-Vorgang, kein
Knopf in einem Dialog. Wer sein Blatt verloren hat, druckt es neu; der
Schlüssel bleibt derselbe.

#### „Später" heißt zweierlei

Bei der **Konto**-Rückfrage sieben Tage, höchstens dreimal je Runde
(`rueckfrage_verschoben`). Bei der **Betreiber**-Rückfrage nur bis zur
nächsten Anmeldung, über ein Merkmal in der Sitzung — und das weicht von
E-P5b-10 ab. Der Grund: Der Schlüsselblatt-Stand ist **ein** Datum in
`app_state`, und es heißt `schluesselblatt_bestaetigt_am`. Um sieben Tage zu
schieben, müsste dort ein Datum stehen, an dem nichts bestätigt wurde — eine
Unwahrheit in genau dem Feld, das die Frage beantwortet. Und es gälte für
alle: Eine BetreiberIn, die schiebt, nähme die Frage auch der anderen weg.

#### Grenzen

- **Ohne JavaScript erscheinen die beiden Dialoge nicht.** Die Erneuerung
  rechnet im Browser (PBKDF2, HKDF, AES-GCM) und kann keinen serverseitigen
  Ersatzweg haben — der Server kennt den Inhaltsschlüssel nicht. Die
  Erststart-**Karte** dagegen kommt ohne aus; ihre beiden Auswege sind ein
  gewöhnliches Formular.
- **Die Fristen sind im Betrieb nicht abgewartet, sondern gestellt worden.**
  Geprüft wurde mit vorgestelltem `rueckfrage_naechste`; die Runden 0 → 1 → 2
  → 2 sind in vier Durchgängen gemessen, ein tatsächlicher Halbjahresabstand
  nicht.

### 4.99k Selbstlöschung und Adresswechsel (ab Web 20.20.0, P5b/AP5)

*E-P5b-16. Code: `server/konto_lib.php`, `server/adresse_bestaetigen.php`,
Job `konto_loeschung`.*

#### Der Adresswechsel geht über die neue Adresse

| | bis Web 20.19.0 | seit 20.20.0 |
|---|---|---|
| Schreiben | **sofort** | erst beim Klick |
| Prüfung der neuen Adresse | **keine** | Link, 24 h |
| Nachricht an die alte | Hinweis | Hinweis (unverändert) |
| Nachweis | Passwort-Token | Passwort-Token (unverändert) |

**Ein Tippfehler sperrte aus.** Die Anmeldung läuft über die Adresse, und
„Passwort vergessen" schickt an eine Adresse, die es nicht gibt — der Weg
zurück führte über die Verwaltung oder, auf einer Einzelinstallation, über die
Datenbank.

**Kein `UNIQUE` auf `email_neu`.** Zwei Konten dürfen dieselbe Adresse
vormerken; erst der Klick entscheidet, und dort fängt das `UNIQUE` auf `email`.
Eine Sperre schon beim Vormerken verriete, dass jemand anders dieselbe Adresse
vorgemerkt hat.

**Der Token steht nicht in `password_resets`.** Er gehört zu einer Adresse,
nicht zu einem Passwort; dort wäre er ein zweiter Tokentyp in einer Tabelle,
deren Regel „höchstens ein gültiger je Konto" lautet — ein Adresswechsel würde
dann einen offenen Einladungslink entwerten.

**`adresse_bestaetigen.php` läuft ohne Anmeldung**, und das ist der Zweck: Wer
den Link hat, hat Zugang zum Postfach der neuen Adresse — genau das ist der
Nachweis. Eine Anmeldung wäre gerade dann unmöglich, wenn sie am meisten hülfe.

#### Die Selbstlöschung

`gesperrt` mit dem Grund `selbstloeschung`, `loeschung_am` = jetzt + **30
Tage**. **Die Rücknahme ist die Anmeldung** — `login.php` setzt den Status
zurück, ohne dass es einen Knopf braucht.

**Warum kein eigener Rücknahmeweg:** Ein Link in der Mail, der etwas anderes
tut als anmelden, wäre ein zweiter Weg mit eigenem Token und eigener Frist —
und er müsste ohne Passwort wirken, sonst braucht man ohnehin die Anmeldung.
Ein Rückzug ohne Passwort ist aber genau das, was ein Angreifer wollte, der
die Löschung verhindern will, um weiter mitzulesen.

**Der Job löscht höchstens fünf je Lauf.** Eine Kontolöschung räumt die Spuren
von Hand (die Kaskade erreicht sie nicht), löscht den Backup-Ordner im
Dateisystem und kaskadiert über vierzehn Tabellen. Das Huckepack-Budget sind
drei Sekunden für **alle** Jobs zusammen; einen Tag später zu löschen ist kein
Zusagenbruch, eine hängende Anfrage schon.

**Er steht weit vorn im Katalog**, gleich hinter dem Aufräumen: Im Regelfall
hat er nichts zu tun (eine Abfrage über einen Index), und wenn doch, ist es
das, worauf jemand ein Recht hat.

#### Das Protokoll nennt beim Wechsel keine Adressen

Nur die Kontonummer und dass gewechselt wurde. Ein Audit, in dem jede je
benutzte Adresse eines Kontos steht, ist ein Verzeichnis von Adressen und
nicht eines von Handlungen. Nachgemessen: Der Eintrag enthält kein `@`.

Beim **Löschen** stehen Adresse und Termin dagegen im Eintrag — dort ist die
Adresse der einzige Anhalt, der die Löschung überhaupt noch nachvollziehbar
macht, und die Zeile überlebt das Konto.

### 4.99j Einwilligungen (ab Web 20.19.0, P5b/AP4)

*E-P5b-05, -15. Code: `server/einwilligung_lib.php`, `server/einwilligung.php`,
Tabelle `konto_einwilligungen`.*

#### Vier Rechtstexte, drei mit Einwilligung

| Schlüssel | Seite | Einwilligung | Wirkung bei neuer Fassung |
|---|---|---|---|
| `impressum` | `impressum.php` | **keine** | — |
| `datenschutz` | `datenschutz.php` | „zur Kenntnis genommen" | Hinweis auf jeder Seite |
| `nutzungsbedingungen` | `nutzungsbedingungen.php` | „angenommen" | **sperrt den Login** |
| `avv` | `avv.php` | „angenommen" | **sperrt den Login** |

**Der Unterschied ist die Rechtsnatur, nicht der Rang.** Ein Vertrag kommt
durch Annahme zustande und darf ohne sie nicht weiterlaufen. Eine
Datenschutzerklärung informiert; Widerspruch dagegen ist kein
Vertragsschluss, sondern ein Recht. Sie darf den Zugang nicht sperren — muss
aber auffallen, sonst ist die Kenntnisnahme eine Behauptung.

**Das Impressum steht bewusst nicht in der Reihe.** Es wird weder angenommen
noch zur Kenntnis genommen; es ist eine Pflichtangabe. `RT_EINWILLIGUNG` sagt,
welche der vier eine Einwilligung verlangen.

#### Was „aktuelle Fassung" heißt

`rechtstexte.stand_am`, und seit AP4 ist das ein **`DATETIME`** und kein
`DATE` mehr — der Fehlerfund F3 des Konzepts: Zwei Änderungen am selben Tag
wären sonst *eine* Fassung, und wer die erste angenommen hat, gälte als
Annehmer der zweiten. Der Editor bleibt ein Datumsfeld; die Uhrzeit setzt der
Speicherweg.

`konto_einwilligungen.stand_am` hält, **welche** Fassung angenommen wurde. Der
Vergleich der beiden ist die ganze Prüfung.

**Ein Text ohne Standdatum verlangt nichts.** Sonst sperrte ein leer
angelegter Platzhalter alle Konten aus — genau der Zustand zwischen dem
Einspielen der Mechanik (AP4) und dem Einspielen der geprüften Texte (R41).

**Der Satz gilt für die Registrierung genauso — seit Web 20.22.2, vorher
nicht.** `registrieren.php` verlangte alle Schlüssel aus `RT_EINWILLIGUNG`,
ohne `stand_am` anzusehen; der Begriff kam in der Datei kein einziges Mal vor.
Wer sich in dem Zustand registrierte, auf den dieser Abschnitt gerade
hingewiesen hat, musste den Haken „Ich nehme die Vereinbarung zur
Auftragsverarbeitung (AVV) an" setzen, während `avv.php` daneben den Leertext
zeigte. `einwilligung_in_kraft()` beantwortet die Frage jetzt für beide
Seiten; **Prüfung und Markup ziehen aus derselben Liste**, sonst könnte die
BetreiberIn zwischen Anzeige und Absenden einen Text in Kraft setzen und das
Formular verlangte einen Haken, den es nie gezeigt hat. Backlog Nr. 231.

#### Wo die Annahme entsteht

**Bei der Registrierung, nicht erst am Tor** (E-P5b-25, seit Web 20.22.2).
Dort wird der Vertrag geschlossen; das Tor ist dafür da, eine **neue** Fassung
nachzuholen, nicht die erste zu erheben. Bis Web 20.22.1 war es andersherum,
und zwar ungewollt: `registrieren.php` verlangte die Häkchen und schrieb sie
nie — `einwilligung_setzen()` wurde im ganzen Server nur von
`einwilligung.php` aufgerufen. Verloren ging dadurch nichts (das Tor fasst
jedes Konto beim ersten Login), wohl aber die Stelle: Die Registrierende
beantwortete dieselben Fragen zweimal.

**Dass das Konto dabei noch `unbestaetigt` ist, ist kein Einwand.**
Festgehalten wird, was an diesem Formular erklärt wurde — nicht, wem die
Adresse gehört. Wird die Registrierung nie bestätigt, räumt
`job_konto_verfall()` das Konto weg und die Zeilen mit ihm (`ON DELETE
CASCADE` an `fk_kew_user`).

**Das Tor bleibt das Auffangnetz**, und es wird gebraucht: Wer sich
registriert, solange kein Text in Kraft ist, gibt keine Erklärung ab — die
holt das Tor beim ersten Login nach dem Einspielen.

#### Das Tor

`auth_guard.php` leitet auf `einwilligung.php`, solange eine Annahme fehlt.
**Drei Wege bleiben offen — Abmelden, Export, Konto löschen.** Wer nicht
zustimmen will, muss an seine Daten kommen und gehen können; ein Tor, das auch
den Ausgang versperrt, wäre Nötigung. Die Ausnahmeliste steht in
`auth_guard.php` neben dem Tor, nicht in einer Konstante: Sie gehört dazu und
wird mit ihm gelesen.

**API und `ingest.php` bleiben unberührt.** Die Uhr fragt niemanden um
Zustimmung — sie hat keinen Bildschirm dafür, und ihre Besitzerin hat der
Nutzung zugestimmt, als sie das Gerät gekoppelt hat. Ein Tor vor `api/day.php`
ließe eine laufende Aufzeichnung ins Leere laufen.

#### Eine Zeile je Konto und Schlüssel, nicht je Annahme

Der Primärschlüssel ist `(user_id, schluessel)`; eine neue Annahme
überschreibt die alte. Der Verlauf — wer wann welche Fassung angenommen hat —
steht im **Protokoll** (Reiter Verwaltung) und überlebt dort auch die
Kontolöschung. Die Tabelle beantwortet nur die eine Frage, die bei jedem
Seitenaufbau gestellt wird: *Liegt die aktuelle Fassung vor?*

`ON DELETE CASCADE` ist hier — anders als beim Protokoll — richtig: Eine
Einwilligung ist eine Aussage **über** das Konto; ohne Konto hat sie keinen
Gegenstand.

#### Der Leerzustand kommt aus einem Katalog

In `rechtstext_seite.php` stand er als *zweiwertiger* ternärer Ausdruck. Das
war die eine Stelle, an der ein dritter Schlüssel **stillschweigend falsch**
geantwortet hätte: Die Nutzungsbedingungen hätten gemeldet, es sei „noch keine
Datenschutzerklärung hinterlegt". Kein Fehler, keine Meldung — nur ein
falscher Satz. `RT_LEERTEXT` kann das nicht: Ein fehlender Eintrag fällt beim
Nachsehen auf, ein falscher Zweig nicht.

### 4.99i Der Lebenszyklus eines Kontos (ab Web 20.17.0, P5b/AP2)

*E-P5b-11, -12; R37 (1); Backlog Nr. 202 Paket 1. Code: `server/konto_lib.php`.*

#### Vier Fassungen derselben Sache — und warum das aufgelöst wurde

Ein Konto entstand an zwei Stellen, ein Token an vier, und keine glich der
anderen:

| Stelle | legte an | Transaktion | entwertete Vorgänger | Laufzeit |
|---|---|---|---|---|
| `admin_users.php` | Konto + Token | **ja** (E17) | nein | `INTERVAL 24 HOUR` |
| `install.php` | Konto + Token | **nein** | nein | `INTERVAL 24 HOUR` |
| `admin_user.php` | nur Token | — | ja | `INTERVAL 1 HOUR` |
| `reset_request.php` | nur Token | — | ja | `INTERVAL 1 HOUR` |

**`install.php` war die gefährliche**: Ein Abbruch zwischen den beiden
`INSERT` hinterließ ein Konto ohne Weg hinein — anmelden ging nicht (kein
Passwort), und der Einrichter lief nicht mehr, weil `install.lock` stand. Eine
Installation, aus der man sich beim Einrichten selbst ausgesperrt hat.

Aufgelöst wurde es hier und nicht in Schritt 15, weil die Selbstregistrierung
aus AP3 sonst die **fünfte** Fassung geworden wäre — und sie ist die einzige,
die von außen erreichbar ist.

**Nachweis:** `grep -rn "INSERT INTO password_resets" server/` zeigt nur noch
`konto_lib.php`.

#### Die Bibliothek läuft ohne `config.php`

`install.php` ist der vierte Aufrufer und der einzige, der läuft, **bevor** es
eine `config.php` gibt: Er schreibt sie erst, nachdem er das erste Konto
angelegt hat, und bringt deshalb seine eigene PDO-Verbindung mit. `konto_lib.php`
lädt `db.php` und `protokoll_lib.php` deshalb nur mit `is_file()`, und jede
Funktion nimmt ein `?PDO` entgegen. Dieselbe Falle wie Backlog Nr. 223 — und
hier von vornherein vermieden statt hinterher behoben.

**Und dann doch hineingelaufen (Backlog Nr. 288).** Seit Schritt 15 legt
`konto_anlegen()` das Konto in `db_transaktion()` an, und der Rahmen stand in
`db.php` — also hinter derselben Weiche. Von **Web 20.30.0 bis 20.37.2**
scheiterte jede Neueinrichtung an „Call to undefined function
db_transaktion()"; bemerkt hat es erst `hochfahren.sh --neu`, weil Stufe 1
keine Anlage einrichtet. Seit **Web 20.37.3** steht der Rahmen in
`transaktion_lib.php`, die nichts lädt; `konto_lib.php` bindet sie
**unbedingt** ein, `db.php` ebenfalls. **Nie ein zweites Mal definieren**,
auch nicht hinter `function_exists()`: Die Erfolgsseite des Einrichters lädt
`db.php` nach, sobald `config.php` steht, und eine zweite Definition bräche dort
mit „Cannot redeclare" ab — nachdem `config.php` und `install.lock` geschrieben
sind. **Wer hier eine Funktion aus einer bedingt geladenen Datei ruft, prüft
den Einrichter mit `bash tools/sandbox/hochfahren.sh --neu`.**

Der Protokolleintrag „erstes Konto angelegt" fällt im Einrichter aus
(`function_exists('protokoll')`). Das ist richtig: Er trüge ohnehin keinen
Urheber, weil es zu diesem Zeitpunkt noch niemanden gibt, der handeln könnte.

#### Die vier Zustände und ihre Übergänge

| von → nach | `unbestaetigt` | `wartet` | `aktiv` | `gesperrt` |
|---|---|---|---|---|
| **`unbestaetigt`** | — | ja | ja | ja |
| **`wartet`** | nein | — | ja | ja |
| **`aktiv`** | **nein** | **nein** | — | ja |
| **`gesperrt`** | nein | nein | ja | — |

**Die Übergänge stehen als Tabelle und nicht als `if`-Zweige** (`KONTO_UEBERGAENGE`).
Die Rückwege sind die interessanten: Aus `gesperrt` geht es nach `aktiv`
zurück (Entsperren, Rücknahme der Selbstlöschung), aus `aktiv` aber **nicht**
nach `unbestaetigt` — ein Konto, dessen Besitzerin sich plötzlich nicht mehr
anmelden kann, ohne dass jemand es angeordnet hat, wäre ein Fehler mit Ansage.

**Entsperren räumt auf**: `gesperrt_seit`, `gesperrt_grund` und
**`loeschung_am`** werden geleert. Ohne das Letzte fände der Löschjob ein
`loeschung_am` in der Vergangenheit und löschte ein Konto, dessen Besitzerin
die Löschung gerade zurückgenommen hat — kein Schönheits-, sondern ein
Datenverlustfehler.

#### Wo der Status geprüft wird

| Ort | Verhalten |
|---|---|
| `login.php` | **vor** der Sitzung, im Erfolgszweig der Passwortprüfung. Eigene Seite über `stoerung_seite_html()` (dritter Aufrufer, kein neuer Baustein), Antwortdauer angeglichen |
| `auth_guard.php` | jede angemeldete Anfrage. Endegrund **`gesperrt`** — neu in `SESSION_ENDE_GRUENDE`, **mit beiden Texten** |
| `ingest.php` | **`403`** mit `{"error":"konto","grund":"<status>"}`, **nach** der Schlüsselprüfung |

**Die Selbstlöschung ist der Sonderfall, und zwar der wichtige** (E-P5b-16):
Während der Karenz steht das Konto auf `gesperrt`, aber **die Anmeldung IST
der Rückzug**. `login.php` nimmt die Löschung zurück, statt abzuweisen; hier
abzuweisen hieße, den einen Weg zu versperren, der aus der Löschung
herausführt — und danach löscht der Job. `auth_guard.php` lässt eine laufende
Sitzung mit diesem Grund deshalb ebenfalls durch.

**`ingest.php`: `403` und nicht `401`.** Die Uhr behandelt `403` heute als
„abgemeldet" und puffert — genau das soll sie tun. Der Unterschied steht im
JSON-Rumpf, nicht im Code; **eine Uhr-Stufe ist dafür nicht nötig**. Dass die
Uhr „abgemeldet" statt „gesperrt" anzeigt, ist ein Backlog-Eintrag für die
nächste Auslieferung.

**Der Ratenschutz zählt das nicht mit.** Ein gesperrtes Konto ist kein
Angriff; sein Gerät sendet weiter, weil niemand es abgeschaltet hat. Zählte es
als Fehlversuch, sperrte der Ratenschutz nach kurzer Zeit eine Kennung, die
nichts falsch macht — und nach dem Entsperren käme der Rückstand dann **nicht**
durch. Gemessen: fünf Uploads mit gesperrtem Konto, **null** Zeilen in
`rate_limits`.

### 4.99h Die Einstellungen rund um Konten (ab Web 20.16.5, P5b/AP1)

*E-P5b-14. Code: `server/konten_einstellungen_lib.php`, Oberfläche
Betrieb → Servereinstellungen, Karte „Konten".*

**Bibliothek und nicht Seite** (R83): Die zweiten Verbraucher stehen
namentlich im Konzept — `registrieren.php` liest die Betriebsart,
`login.php` die Demo-Anmeldung, `ingest.php` die Mengengrenzen, der Job
`konto_verfall` die Freischaltfrist. Sie in die Seite zu schreiben und später
herauszuziehen wäre derselbe Umbau, nur mit vier Aufrufern mehr.

| Schlüssel in `app_state` | Vorgabe | wirkt ab |
|---|---|---|
| `konten_reg_art` | **`einladung`** | AP3 |
| `konten_reg_frist` | 30 Tage | AP3 |
| `konten_wegwerf` / `…_eigene` | an / leer | AP3 |
| `konten_grenze_einsaetze` | 5000 | AP6 |
| `konten_grenze_mb` | 250 | AP6 |
| `konten_aufbewahrung` | leer = unbegrenzt | AP6 |
| `konten_demo_anmeldung` | an | **AP1** |
| `protokoll_frist_verwaltung` | 365 | **AP1** |

**Die Vorgabe der Betriebsart ist `einladung`**, und das ist nicht die, die
nadoku selbst fährt. Sie ist die sicherste Grundstellung für eine
Selbsthosterin, die die Seite nie aufschlägt — heutiges Verhalten, keine
offene Tür durch Untätigkeit (E-P5b-01).

**Zwei Fallen, beide im Code vermerkt:**

- **Leer ist ein Wert.** Die Aufbewahrung „unbegrenzt" ist der leere String;
  sie darf nicht auf die Vorgabe zurückfallen, sonst ließe sie sich nie
  einschalten. Eine `0` gibt es nicht — sie hieße „nichts aufbewahren".
- **Eine unbekannte Betriebsart fällt auf die Vorgabe zurück**, statt zu
  gelten. `offen` durch einen Tippfehler wäre die teuerste aller stillen
  Änderungen.

`konten_einstellungen()` holt **alle acht Schlüssel in einer Abfrage** und
hält sie je Anfrage. `app_state_lesen()` hat keinen Zwischenspeicher und
fragt bei jedem Aufruf neu; acht Einzelaufrufe wären acht Abfragen je
Seitenaufbau.

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

**Die Uhr verlangt seit Android 0.13.0 gar keine Berechtigung mehr.** Sie
hatte genau eine — `WAKE_LOCK` —, und die war im Quelltext unbenutzt: kein
Aufruf von `PowerManager` oder `newWakeLock` im ganzen Modul. Eine
Berechtigung auf Vorrat fragt zwar niemanden (Normalstufe, kein Dialog),
erscheint aber im Store-Eintrag; sie ist ausgetragen. Das Handy führt acht,
jede im Manifest begründet — und drei ausdrücklich **nicht**:
`ACCESS_BACKGROUND_LOCATION` (ein sichtbarer Vordergrunddienst braucht sie
nicht), `CAMERA` (mit R63 entfallen) und
`REQUEST_IGNORE_BATTERY_OPTIMIZATIONS` (verstößt gegen die Inhaltsrichtlinie
des Play Store).

**Der Geräteschlüssel folgt keiner Umleitung** (seit Android 0.13.0).
`HttpURLConnection` folgt in der Voreinstellung einer 3xx-Antwort von selbst
und nimmt die Kopfzeilen mit — darunter `X-Api-Key`. `HttpNetzweg` setzt
deshalb `instanceFollowRedirects = false`; eine Umleitung gilt seither als
**Serverfehler**, weil die App mit genau zwei Endpunkten einer fest
eingebauten Adresse spricht (R63). Der Sendeweg war nie betroffen: Er
behandelt alles außer 200/400/401/413 als „später erneut".

**Klartext ist im Release seit Android 0.14.0 doppelt verboten** (Backlog
Nr. 142, Krypto-Review AN-1). `Serveradresse` bildet im ausgelieferten Stand
für jede Adresse `https` — die Ausnahme für `localhost` und IPv4-Adressen,
die der Prüfstand braucht, hängt an `BuildConfig.DEBUG` —, und
`handy/src/release/res/xml/netzsicherheit.xml` verbietet Klartext auf
Systemebene. Das zweite braucht es wegen `minSdk` 26: Androids eigenes
Verbot gilt erst ab API 28. Betroffen war nur ein Selbsthoster, der sein APK
mit einer IP-Adresse baut; der Standardbau mit fester Domain nie. **Kein
Certificate Pinning**, mit Begründung (Nr. 143): `android/LIESMICH.md`,
Abschnitt „Warum kein Certificate Pinning".

**Die App führt seit Android 0.13.0 zu den Rechtstexten.** Unter den
Einstellungen stehen zwei Verweise auf `datenschutz.php` und `impressum.php`
derselben Serveradresse; beide Seiten sind ohne Anmeldung erreichbar. Sie
öffnen den Browser — die App hat **kein WebView**, und das soll sie nicht
bekommen: Es wäre die einzige Stelle, an der fremdes Markup in ihrem Prozess
liefe.

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

**Und zwei Böden gegen einen fremden Absender** (seit Android 0.14.0,
Backlog Nr. 144, Krypto-Review AN-4). Der erste ist die Bibliothek: Der Data
Layer stellt nur zwischen Apps gleichen Pakets und gleicher Signatur zu. Der
zweite ist die App selbst: `HandyHorcher` fragt die verbundenen Knoten ab
(`WearNachrichtenweg.verbundeneKnoten()`, dieselbe eine Datei), und
`Uhrannahme.absenderBekannt()` verlangt, dass `sourceNodeId` darunter steht
— sonst weder Wirkung noch Quittung; eine echte Uhr liefert nach, sobald sie
verbunden ist. Ist die Liste nicht lesbar, gilt der Absender als fremd (das
kostet Zeit, keine Daten). Dazu die **Zeit der Uhr**: höchstens fünf Minuten
in der Zukunft und höchstens fünf Minuten vor dem laufenden Dienst; außerhalb
wird quittiert, aber nicht gewirkt — dieselbe Regel wie für eine Phase ohne
Dienst. Die fünf Minuten sind gewählt, nicht gemessen.

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

**Seit 0.15.0 sagt die Anzeige, seit wann der Dienst läuft — mit Datum, wenn
er nicht von heute ist** (Backlog Nr. 160). `Zeit.seit()` liefert „07:00",
solange Beginn und Gegenwart auf denselben **Ortstag** fallen, sonst
„Fr. 05.09., 07:00"; denselben Wert benutzen die Dienstansicht und die
Dauermeldung. Der Anlass ist der fortgesetzte Dienst: Ein zweiter „Dienst
beginnen" gibt bei laufendem Dienst den vorhandenen zurück (E-R45-13), und
die Zeile „läuft seit 07:00" war von einem Dienst, der eben erst begann,
nicht zu unterscheiden. **Die Sprache des Datums ist fest deutsch**, nicht die
des Geräts — im Emulatorlauf stand dort sonst „Tue 08.09." mitten im
deutschen Satz.

Dazu eine **Erinnerung auf dem Warnkanal** (ID 5), einmal je Lauf des
Aufzeichnungsdienstes, sobald er `DIENSTDAUER_ERINNERUNG_H` = **26 Stunden**
überschreitet: Titel „Dienst läuft seit N Stunden", Text mit dem Beginn und
dem Knopf „Dienst beenden". Die Schwelle ist gewählt, nicht gemessen, und die
Begründung steht an der Konstante — regulär bis 24 Stunden, zwei Stunden Luft
für einen späten Schichtwechsel. Geprüft wird sie im **Wächtertakt** (10 s);
der Vergleich kostet nichts, und ein Merker sorgt dafür, dass die Meldung
einmal entsteht statt alle zehn Sekunden.

**Ein zweiter Benachrichtigungskanal, weil Android die Einstellungen eines
Kanals nach dem Anlegen der NutzerIn überlässt.** Der Kanal „Aufzeichnung"
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

### Dienstende und Nachsenden

*Seit Android 0.9.0 (S5, Paket E2).*

**Das Dienstende hält den Vordergrunddienst, bis der Sendelauf zurück ist.**
Bis 0.8.1 stand dort: Faden starten, `stopForeground`, `stopSelf` — ohne
dazwischen zu warten. Der Lauf lief danach in einem Prozess **ohne**
Vordergrunddienst weiter, und den räumt Android bei Bedarf ab. Kam er wegen
fehlenden Netzes nicht durch, endete er mit `spaeterErneut`, und ein Später
gab es nicht: Ausserhalb eines Dienstes existierte kein Zeitgeber. Das ist der
belegte Fehler des Vorfalls vom 02.09.2026.

Der Ablauf jetzt:

| Schritt | |
|---|---|
| 1 | `klammer.beenden()` schliesst Einsatz, Segment und Dienst im Puffer |
| 2 | Ortung ab, Wächter ab, Netzrückruf ab, Warnung (ID 3) weg |
| 3 | Dauermeldung „Dienst beendet · sende …" — **der Dienst steht noch** |
| 4 | DIENSTENDE-Lauf auf dem Sendeausführer |
| 5 | Rückstand 0 → keine Meldung bleibt. Rückstand > 0 → Nachsende-Job + stiller Hinweis (ID 2). 401 → Hinweis „Gerät neu koppeln", **kein** Job |
| 6 | `stopForeground`, `stopSelf` |

**Der Nachsende-Job** (`NachsendeDienst`, `JobScheduler`, feste Job-ID) ist
ein Bordmittel — `WorkManager` wäre eine Abhängigkeit und setzt intern auf
denselben Planer. Bedingung `NETWORK_TYPE_ANY`, `setPersisted(true)` (dafür
`RECEIVE_BOOT_COMPLETED`), Backoff exponentiell ab 30 s. **Wann geplant wird,
steht in einer reinen Funktion** `Nachsenden.planen(bericht, rueckstand,
dienstLaeuft)` und nirgends sonst:

- nach einem Lauf: nur bei `spaeterErneut`, Rückstand > 0, kein 401, kein
  laufender Dienst;
- beim App-Start (`bericht = null`): jeder Rückstand genügt — der Prozess kann
  gestorben sein, bevor der Job geplant wurde.

Er läuft erst nach dem **ersten Entsperren**: Die Zugangsdaten liegen in der
anmeldungsgeschützten Speicherung. Kein `directBootAware`.

**Ein Dienstende von der Uhr beendet den Vordergrunddienst.** `HandyHorcher`
liest `klammer.laeuft()` **vor** und nach `uebernimm()` und fragt
`Dienstfolge.aus(liefVorher, laeuftNachher, dienstSteht)`. Die dritte Zahl ist
die Vorsicht: Ohne stehenden Dienst wird nichts angefasst — ein Stopp-Befehl
an einen Dienst, den es nicht gibt, startet ihn erst, und `startService` aus
dem Hintergrund wirft ab Android 8.

**Wiederverbindung.** Der Vordergrunddienst meldet
`registerDefaultNetworkCallback` an; `onAvailable` löst einen Lauf aus,
höchstens einmal je 60 s. Die Bremse liegt in `Sendetakt` und nicht im
Rückruf — dort ist sie prüfbar. Damit ist der Auslöser `WIEDERVERBINDUNG` aus
E-S4-07 gebaut und `ACCESS_NETWORK_STATE` hat seinen Nutzer.

**Ein Sendeausführer, nie zwei Läufe zugleich.** `NAdokuApp` hält einen
`newSingleThreadExecutor`; Dienst (Takt, Dienstende, Wiederverbindung),
Oberfläche (Phase, Abschluss, „Jetzt senden") und Nachsende-Job reichen ihre
Läufe dort ein. Vorher startete jeder Anlass einen eigenen `Thread`, und die
Zusicherung „die Läufe überlappen nicht" galt nur für den Takt. **Nebenwirkung,
die zählt:** Damit ist auch die Reihenfolge beim Nachsenden gesichert — ein
älteres, nicht-finales Paket kann nicht mehr nach einem finalen ankommen.

**Was die Ansicht zeigt** (`abgewiesen()` im Puffer): „Alles gesendet" /
„Rückstand N Pakete" / **„N Pakete vom Server abgewiesen"** in Rot, dazu ein
Knopf „Jetzt senden" bei Rückstand und eine Ergebniszeile aus dem letzten
Lauf. Die 400-Zeile schliesst die Lücke, durch die ein Paket bisher aus
Warteschlange **und** Anzeige fiel.

**Abgewiesene Pakete bleiben nicht mehr für immer** (seit Android 0.14.0,
Backlog Nr. 114 Räumteil, Krypto-Review AN-2): Jeder Sendelauf räumt vorher,
was älter ist als 30 Tage (`Raeumung.FRIST_TAGE`), und das Trennen räumt ohne
Frist — die Pakete gehören dem zurückgegebenen Konto. Gelöscht wird nur
Abgeschlossenes, samt Punkten und Phasen in einer Transaktion; beendete
`dienst`-Zeilen ohne Pakete gehen mit, die laufende nie. Der Bedienweg zum
Ansehen und Ausleiten bleibt Nr. 114.

### Der Uhr-Spiegel

*Seit Android 0.10.0 (S5, Paket E3).*

Der Vordergrunddienst schickt den Ortungszustand bei **jedem Wechsel** ans
Handgelenk — nicht mehr nur als Antwort auf ein Ereignis der Uhr. Wer keinen
Knopf drückte, sah bis dahin „Dienst läuft", während nichts aufgezeichnet
wurde.

**Sechs Stufen, drei Anzeigen.** `Ortungscode.anzeige()` fasst zusammen:
`KEINE_ORTUNG` (die vier Stufen ohne Aufzeichnung), `SUCHEN`, `STILL` (nur
`OK` — und ein fehlendes Feld, also eine ältere Handy-Fassung). Die Regel
liegt in `gemeinsam/` und wird von **beiden** Seiten benutzt: von der Uhr zum
Zeichnen, vom Handy zum Entscheiden, ob überhaupt gesendet wird. Zwei Kopien
liefen auseinander, und dann meldete das Handy Wechsel, die nichts ändern —
oder einen nicht, den die Uhr angezeigt hätte. Ein Prüffall hält beide Enden
aneinander: Die Stufen, bei denen das Handy warnt, sind genau die, bei denen
die Uhr rot wird.

**Zwei Ausführer, und der Unterschied ist begründet.** `NAdokuApp` hält den
Sendeausführer (Server) **und** einen eigenen für die Uhr. Beide
Warteschlangen haben verschiedene Fristen: Ein Upload kann eine Minute
dauern, eine Uhrnachricht bis zu fünf Sekunden je Schritt blockieren. Lägen
sie hintereinander, käme die Warnung zu spät ans Handgelenk oder ein Funkloch
hielte einen Diensttag auf. Was E-S5Z-11 zusichert — nie zwei Läufe auf
demselben Puffer — bleibt unberührt: Eine Uhrnachricht liest und schreibt
nicht.

**Eine verlorene Standmeldung wird nicht nachgeliefert.** Anders als ein
Ereignis der Uhr (gepuffert bis zur Quittung) ist der Stand ein
Augenblickswert; der nächste Wechsel trägt den dann gültigen.

### Was die App an den Server schickt

Nichts Neues: denselben JSON-Vertrag wie die Uhr-App aus Abschnitt 5. **Der
Ingest ist geräteneutral und bleibt es** — er sieht ein Gerät mit Kennung und
API-Schlüssel und weiß nicht, ob dahinter Monkey C oder Kotlin steckt. Die
Kennungspräfixe unterscheiden die Quellen (`am-`/`ar-`/`ad-` für das Handy,
`wm-` für die Wear-OS-Uhr); sie stehen seit Vertragsfassung 1.4 im
JSON-Vertrag, Abschnitt 8 — der Nachtrag hing an R42 und ist mit S6 erledigt.

**Die Kopplung ist seit Web 12.9.0 nicht mehr geräteneutral**, und das ist
Absicht: `pair.php` liest den Block `geraet` aus — seit Web 13.0.0 in der
Anfrage `start` — und hält fest, was gekoppelt hat (R42). Die Neutralität gilt
weiterhin für den Upload — dort entscheidet nichts am Verhalten des Servers,
welcher Client sendet. Beim Koppeln ist die
Geräteart die Auskunft selbst.

### Prüfen ohne Gerät

Es gibt keine Uhr und kein Telefon. Was trotzdem geht, steht in
`android/LIESMICH.md`; die Kurzform:

- **Prüffälle** über JUnit und Robolectric — auch gegen ein *echtes* SQLite
  und, wo eine lokale Installation läuft, gegen `ingest.php` selbst. Das
  Android-Abbild, das Robolectric dafür braucht, kommt seit Android 0.15.1
  über **Gradle** statt über Robolectrics eigenen Downloader; die Läufe sind
  damit netzunabhängig (`android/LIESMICH.md` 2.3).
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

## 5b. Plattformprofil (ab Web 20.5.0, P5a/AP2, R81)

**Was eine Installation von ihrer Plattform braucht — hosterneutral, in genau
zwei Stufen.** Beschlossen am 15.09.2026 als R81; Volltext der Herleitung in
`docs/konzepte/Vorbereitung-P5-Plattformprofil.md` (PP-1 bis PP-9).

**Zwei Installationen lesen diese Liste, und sie stehen auf zwei
Plattformen.** Seit dem 20.09.2026 liegt Staging bei einem anderen Hoster als
Produktiv; was das für die Übertragbarkeit einer Messung bedeutet — und der
Vergleich beider Auskünfte nebeneinander — steht in **Abschnitt 6.3a**.

| Stufe | Bedeutung | Was die Anwendung daraus macht |
|---|---|---|
| **Muss** | Fehlt es, läuft die Anwendung nicht — und sagt es. | `install.php` prüft es **vor** der Einrichtung und lässt sie nicht zu; die Statusseite prüft es im Betrieb und zeigt **rot**. |
| **Empfohlen** | Wird genutzt, wenn es da ist. Fehlt es, läuft die Anwendung vollständig — langsamer, mit Verzögerung oder mit einem Handgriff mehr. | Auf der Statusseite ein **Hinweis**; er färbt die Ampel nicht. |

Es gibt keine dritte Stufe. Was weder Muss noch Empfohlen ist, wird **nicht
vorausgesetzt** — namentlich der DDoS-Grundschutz des Hosters und die
Verschlüsselung at rest. Beides ist eine Empfehlung an die BetreiberIn, keine
Anforderung an die Plattform.

### 5b.1 Eine Funktion, zwei Leser

Die Liste steht an **einer** Stelle: `plattform_pruefen()` in
`server/plattform_lib.php`. `install.php` ruft sie vor der Einrichtung, die
Statusseite im Betrieb. Zwei Listen liefen auseinander — und ein Hoster kann
eine PHP-Fassung oder ein Weblimit jederzeit umstellen, ohne jemanden zu
fragen. Was die Einrichtung verlangt, muss die Installation auch im dritten
Jahr noch erfüllen.

**`ok` ist dreiwertig:** `true` erfüllt, `false` nicht erfüllt, **`null` nicht
feststellbar**. Nur `false` auf der Muss-Stufe hält die Einrichtung auf. Wer
nichts gemessen hat, darf nichts behaupten.

### 5b.2 Die Prüfpunkte (Stand 20.09.2026)

**Muss**

| # | Prüfung | Sollwert |
|---|---|---|
| 1 | PHP-Version | ≥ 8.2 |
| 2 | Erweiterungen | `pdo_mysql`, `openssl`, `mbstring`, `zip`, `zlib` |
| 3 | Weblimits | `memory_limit` ≥ 64 MB, `max_execution_time` ≥ 30 s, `post_max_size` und `upload_max_filesize` ≥ 2 MB |
| 4 | Schreibrechte | Anwendungswurzel, `sicherungen/`, `sys_get_temp_dir()` — je **mit Probedatei**. `.sitzungen/` kommt als **vierter** hinzu, aber auf der Stufe Empfohlen (unten) |
| 5 | Freier Platz | ≥ 1× größtes Komplett-Backup — oder ehrlich „unbekannt" |
| 6 | HTTPS | die Anfrage kam über TLS (Ausnahme `localhost`) |
| 7 | Datenbank | MySQL ≥ 8.0 oder MariaDB ≥ 10.6, InnoDB, `utf8mb4` |
| 8 | Verbindungsgrenze | `max_user_connections` ≥ 10 (sonst `max_connections`) |
| 9 | Kontingent der Datenbank | unter der obersten Warnschwelle (Vorgabe 10 GB, Z2) |
| 10 | SMTP | eingerichtet; `install.php` wählt zusätzlich einmal an |
| 11 | **Sitzungsablage** | der **wirksame** `session.save_path` gehört uns und ist für andere gesperrt (Schritt 16, E-SA-07) |

**Empfohlen:** PHP ≥ 8.3 · OPcache aktiv · Datenbank in Herstellerpflege
(MySQL 8.4, MariaDB 10.11/11.4) · `max_user_connections` ≥ 50 · `config.php`
beschreibbar · **Ablage der Sitzungen beschreibbar** (der vierte Schreibort,
E-SA-04) · vertrauenswürdige Proxys eingetragen (reine Auskunft).

> **Punkt 11 misst den wirksamen Ort, nicht den gewünschten** — und darin
> liegt sein Wert. Richtet die Anwendung ihre eigene Ablage ein
> (`server/.sitzungen/`, 0700), prüft er sie; fällt sie auf den Pfad des
> Hosters zurück (E-SA-03), prüft er **jenen**. Der Punkt bleibt nach einem
> Rückfall also scharf, und das ist der Fall, für den es ihn gibt.
>
> **Rot wird er bei jedem Recht für „andere"** (`fileperms & 0007`) und
> dann, wenn das Verzeichnis uns nicht gehört und sich trotzdem auflisten
> lässt: **Dann sind wir selbst der Fremde, der es lesen konnte.** Wo sich
> Rechte oder Eigentümer nicht ermitteln lassen — `open_basedir`, kein
> `ext-posix` —, steht `null`, nicht `true`.
>
> **Muss und nicht Empfohlen**, obwohl es einen Rückfall gibt: Nur Muss wird
> auf der Statusseite rot. Eine Sitzungsdatei trägt kein Schlüsselmaterial,
> aber ihr **Dateiname ist die Sitzungskennung** — wer sie liest, ist
> angemeldet und sieht die Klartextliste. Dass Muss die Einrichtung sperren
> kann, ist bedacht und fällt praktisch aus: `install.php` ruft
> `sitzung_ablage()` lange vor der Prüfung, und wer die Wurzel beschreiben
> darf (selbst ein Muss), kann `.sitzungen/` anlegen. Ist die Wurzel nicht
> beschreibbar, scheitert die Einrichtung ohnehin eine Zeile früher.
>
> **Der vierte Schreibort dagegen ist Empfohlen** (E-SA-04) und im guten
> Fall **unsichtbar**: Die Statusseite zeigt erfüllte Empfehlungen nicht
> einzeln, sondern nur in der Schlusszeile. Das ist gewollt — wo die
> Sitzungen liegen und wie viele es sind, sagt Punkt 11, und der steht
> immer da.
>
> **Und Punkt 11 sagt seit Web 20.26.1 auch, ob das Verzeichnis existiert.**
> Der Grund ist ein Befund vom Ausrollen: Auf Staging meldete die Seite
> „konnte kein eigenes Verzeichnis einrichten", obwohl es angelegt und
> beschreibbar war — und ausgerechnet die Zeile, die das gezeigt hätte, war
> als erfüllte Empfehlung unsichtbar. Eine Muss-Zeile, die immer dasteht,
> trägt diese Auskunft jetzt mit.

### 5b.2a Der Rückfall nennt seine Lage, er rät sie nicht (ab Web 20.26.1)

**Bis Web 20.26.0 stand auf der Statusseite eine Ursache, die niemand
gemessen hatte.** Sobald der wirksame Pfad nicht der eigene war, druckte sie
„Die Anwendung konnte kein eigenes Verzeichnis einrichten" — ein Literal, kein
Messergebnis. Auf der Staging-Anlage war der Satz falsch: Das Verzeichnis war
angelegt und beschreibbar, nur hat die Anlage den gesetzten Pfad nicht
übernommen. Für diesen Fall gab es keinen Zustand, also auch keinen wahren
Satz.

`sitzung_ablage_stand()` trägt deshalb eine **benannte Lage**, und
`sitzung_ablage_satz()` hält je Lage genau einen Satz bereit:

| Lage | wann |
|---|---|
| `eigen` | Pfad gesetzt **und zurückgelesen** |
| `kommandozeile` | CLI — richtet absichtlich nichts ein |
| `sitzung_lief` | beim Laden von `db.php` lief schon eine Sitzung |
| `nicht_anlegbar` | `mkdir` gescheitert |
| `nicht_beschreibbar` | Schreibprobe gescheitert |
| `nicht_uebernommen` | angelegt und beschreibbar, aber der Pfad greift nicht |

**Die Farbe kommt weiter aus der Messung, der Satz aus der Lage.** Wer einen
Ausgang ergänzt, ergänzt den Satz mit; eine unbekannte Lage bekommt bewusst
keinen erfundenen.

**Der Rückgabewert von `session_save_path()` taugt zur Erfolgsprüfung
nicht** — das ist der teuerste Einzelbefund dieses Pakets. Gemessen unter
PHP 8.4.19:

| Ablehnungsgrund | Rückgabe | Pfad danach |
|---|---|---|
| Sitzung schon aktiv | `false` | alt |
| Kopfzeilen schon gesendet | `false` | alt |
| **`open_basedir` sperrt den Pfad aus** | **der alte Pfad als Zeichenkette** | alt |

Der dritte Fall sieht aus wie Erfolg. Ein `=== false` lässt ihn durch.
**Belastbar ist allein das Zurücklesen** — der wirksame Pfad gegen den
gewünschten, und genau so prüft `sitzung_ablage_setzen()` es.

#### Wenn der Hoster den Pfad vorgibt: `.user.ini` (gemessen am 20.09.2026)

**Genau das ist auf Staging eingetreten.** Die Zeile stand auf
`nicht_uebernommen`: Verzeichnis angelegt, `0700`, Schreibprobe bestanden —
und `session.save_path` blieb auf `/home/webpages/tmp`.

**Die Abhilfe kostete eine Datei und keinen Support.** Eine `.user.ini` neben
`index.php` mit einer Zeile:

    session.save_path = "/pfad/zur/anwendung/.sitzungen"

Danach stand die Zeile blau: *eigenes Verzeichnis, 0700*. Der Hoster hatte den
Wert also nur **gesetzt**, nicht per `php_admin_value` **gesperrt**.

**Sie gehört nicht ins Repositorium.** Der Pfad ist anlagenabhängig, und
E-PP-04 sagt: Ein Hosterwechsel ändert `config.php`, keine Codezeile. Sie ist
ein **Handgriff der BetreiberIn**, und er steht als solcher im Runbook
(Abschnitt 7). Zwei Bedingungen: `.user.ini` wirkt nur bei CGI/FastCGI, und
sie greift erst nach `user_ini.cache_ttl` (Vorgabe 300 s). Gegen Abruf über
die Adresszeile deckt sie dieselbe Punktregel wie `.sitzungen/` selbst.

**Hilft sie nicht**, steht der Wert als `php_admin_value` fest. Dann bleibt
der Weg über den Hoster — oder, wenn der nicht mitspielt, ein eigener
Sitzungs-Handler (`session_set_save_handler`), den keine `php.ini`
überstimmen kann. Das wäre ein eigener Umbau und der Punkt, an dem die in
E-SA-00 vertagte Stufe 3 (Sitzungen in der Datenbank) wieder zur Frage steht.

**Produktiv ist ungeprüft.** Dort ist `session.save_path` nie erhoben worden.
Beim ersten Ausrollen dorthin zeigt dieselbe Zeile, ob derselbe Handgriff
fällig ist — **das ist der Prüfpunkt, nicht eine Vermutung**.

**Die Regel ist dauerhaft, die Zahl ist ein Stand** (E-PP-03): Für Versionen
gilt „vom Hersteller noch mit Sicherheitskorrekturen versorgt". Die Zahlen
oben sind der Stand vom 15.09.2026 und stehen im Code an **einer** Stelle, als
Konstanten in `plattform_lib.php` — nicht in drei Meldungstexten.

### 5b.3 Warum die Untergrenze bei PHP 8.2 liegt

**Nicht, weil der Code mehr bräuchte.** Er kommt mit 8.1 aus (`array_is_list()`,
Rückgabetyp `never`) und soll die Untergrenze auch nicht von selbst
weiterschieben. Sie liegt bei 8.2, weil PHP 8.1 seit Ende 2025 keine
Sicherheitskorrekturen mehr bekommt. Eine Anwendung, die Patientendaten führt,
soll nicht auf einer ungepflegten Fassung laufen und es niemandem sagen.

### 5b.4 Die Weiche in `install.php` — und ihre Grenze

`install.php` beginnt mit einer Versionsprüfung, die eine Seite mit dem Grund
zeigt. **Sie nützt nur, solange die Datei auf der alten Fassung noch übersetzt
werden kann:** PHP übersetzt eine Datei vollständig, bevor es die erste Zeile
ausführt. Eine einzige `match`-Anweisung weiter unten, und die Besucherin auf
PHP 8.0 bekommt statt der Erklärung einen Parse Error.

`install.php` bleibt deshalb **PHP-7-lesbar**, und Stufe 1 des Prüftors zählt
das mit dem Tokenizer nach (`tools/quelltext/` `installweiche`) — für `install.php`
**und** für `server/php_mindest.php`. Die Prüfung ist nicht vollständig —
benannte Argumente und Eigenschaftenbeförderung sieht sie nicht —, und sie
sagt das bei jedem Lauf selbst.

**`server/php_mindest.php` ist drei Zeilen lang und trägt die Zahl.** E-PP-03
verlangt, dass die Untergrenze an *einer* Stelle steht.
`plattform_lib.php` — der natürliche Ort — ist PHP-8-Code (`match`) und darf
von der Weiche nicht geladen werden. Ohne diese kleine Datei stünde die 8.2
zweimal da: im Vergleich und im Satz „Diese Anwendung braucht PHP 8.2 oder
neuer". Zwei Zahlen für dieselbe Aussage laufen beim nächsten Anheben
auseinander, und zwar in der teuersten Richtung — der Vergleich stiege, der
Satz bliebe stehen und sperrte jemanden mit einer falschen Auskunft aus.
`php_mindest.php` ist aus demselben Grund altertümlich geschrieben wie die
Weiche selbst: kein `declare`, kein Rückgabetyp, nichts, was PHP 5.3 nicht
übersetzt.

**Was sie nicht leisten kann:** `index.php` schützen. Jene Datei **ist**
PHP-8-Code, und PHP übersetzt sie ganz, bevor die Weiterleitung auf
`install.php` zur Ausführung käme. Wer auf einer zu alten Fassung die
Startseite aufruft, sieht einen Parse Error; der Weg für eine Ersteinrichtung
ist `install.php` unmittelbar.

### 5b.5 Schreibrechte werden mit einer Probedatei geprüft

`is_writable()` beantwortet die Frage anhand der Rechtebits — und liegt falsch,
sobald ACLs, `open_basedir`, ein schreibgeschütztes Dateisystem oder SELinux im
Spiel sind. Auf geteiltem Webspace ist genau das der Regelfall.
`plattform_schreibprobe()` legt stattdessen eine Datei mit zufälligem Namen an,
liest sie zurück und räumt sie weg. Der zufällige Name ist kein Schmuck: Eine
feste Probedatei wäre über die Adresszeile abrufbar, wenn das Verzeichnis im
Web-Wurzelverzeichnis liegt.

**Seit Schritt 16 hat die Funktion einen zweiten Aufrufer**, und der ist der
häufigere: `sitzung_ablage()` prüft mit ihr, ob sich in `server/.sitzungen/`
schreiben lässt. Sie lädt `plattform_lib.php` dafür **nach**, und zwar nur im
Zweig mit altem Marker — also höchstens einmal je Stunde, nicht bei jeder
Anfrage. Eine zweite Schreibprobe im Repositorium wäre die Art Verdopplung,
die auseinanderläuft.

> **Die Stelle, an der das still brechen kann, steht in `email_lib.php`.**
> Deren beide `require_once` liegen im Rumpf einer Funktion — eines davon
> stand bis Web 20.25.0 auf Spalte 0 eingerückt und sah damit aus wie ein
> Aufruf auf oberster Ebene. Auf oberster Ebene zöge es über `mail_lib.php`
> **`db.php` nach**, und die Funktion liefe dann in einer halb geladenen
> `db.php`. `require_once` meldete den Zyklus nicht, sondern kehrte still
> zurück. Die Einrückung ist berichtigt und trägt jetzt einen Kommentar, der
> das sagt.
>
> **Seit Web 20.27.0 ist der Zyklus kürzer, aber nicht fort** (Schritt 15
> AP2, E-ZE-06): `db.php` ruft `sitzung_ablage()` nicht mehr beim eigenen
> Laden — das tut jetzt `sitzung_starten()`, unmittelbar bevor PHP die
> Sitzungsdatei anlegt. Die Bedingung bleibt trotzdem dieselbe: **Kein Weg
> aus `sitzung_lib.php` oder `plattform_lib.php` darf `db.php` erreichen**,
> denn `sitzung_starten()` wird aus Seiten gerufen, die `db.php` geladen
> haben. Nachgemessen über `get_included_files()`: `plattform_lib.php` zieht
> `email_lib.php` und `php_mindest.php` nach, `db.php` nicht.

### 5b.6 Die zwei Kontingente und ihre Warnung

Seit Web 20.5.0 gibt es **zwei** Kontingent-Angaben unter Betrieb →
Servereinstellungen:

| Angabe | Vorgabe | Warum eine Angabe und keine Messung |
|---|---|---|
| `webspace_gb` | keine | `disk_free_space()` liefert auf geteiltem Hosting den Datenträger des **Hosts**, nicht die Quota dieses Kontos |
| `db_gb` | **10 GB** (Z2) | Kein Hoster macht das DB-Kontingent abfragbar; `information_schema` sagt, wie groß die Datenbank **ist**, nicht wie groß sie sein **darf** |

Der Unterschied bei der Vorgabe ist Absicht: Der Webspace ist je Tarif
verschieden und ohne Angabe schlicht unbekannt — ein geratener Wert wäre
schlimmer als keiner. Die 10 GB dagegen sind die Untergrenze, die diese
Anwendung nach Z2 tragen muss; das ist eine Zusage des Projekts, keine
Vermutung über den Hoster.

**Gewarnt wird mit denselben Schwellen wie überall** (`edbak_schwellen()`,
Vorgabe 70/90 %) und per Mail an alle mit Verwaltungsrecht — höchstens einmal
je Schwelle, und eine unterschrittene Schwelle wird vergessen, damit die
Warnung beim nächsten Überschreiten wiederkommt.

> **Nebenbefund von AP2, und kein kleiner.** `edbak_schwellen_melden()` — die
> Warnung für die Speichergrenze der Backups, seit S8 vorhanden — **wurde im
> Betrieb von niemandem aufgerufen**. Nachgemessen am 15.09.2026:
> `grep -rn "schwellen_melden" --include=*.php` findet die Definition und
> einen Aufruf in `tools/proben/wiederherstellung/probe.php`, sonst nichts.
> Geschrieben, geprüft, tot — dieselbe Klasse Fehler wie Backlog Nr. 89
> („Dieser Job lief von Web 12.2.0 bis 12.9.2 nie"). Der Aufruf steht jetzt im
> täglichen Aufräumjob, direkt hinter der Messung.

## 5c. Sicherheitskopfzeilen, CSP und die Client-Adresse (ab Web 20.7.0, P5a/AP4)

**Bis Web 20.6.0** standen vier Kopfzeilen in `server/.htaccess`. Eine
Content-Security-Policy gab es nicht. Drei Folgen, und jede war ein Loch:
kein Schutz gegen eingeschleustes Skript — und diese Anwendung entschlüsselt
Patientendaten *im Browser*, ein Skript dort läuft neben dem Datenschlüssel;
`.htaccess` gilt nur auf Apache, wer hinter nginx oder Caddy läuft hatte gar
keine Kopfzeilen; und HSTS war ein Jahr, fest verdrahtet.

### 5c.1 Eine Stelle schreibt sie: `kopfzeilen_lib.php`

| Funktion | Ruft wer | Setzt |
|---|---|---|
| `kopfzeilen_seite()` | `ui_seite_start()` (jede HTML-Seite), `wartung_kopfzeilen()`, `betrieb_schluesselblatt.php` | CSP mit Nonce, HSTS, `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy` |
| `kopfzeilen_json()` | `json_kopf()`, und damit `json_roh_out()` und `json_out()` — **jede** API-Antwort (seit Web 20.9.1 auch die sieben Rohausgaben, Nr. 203) | nur `X-Content-Type-Options`, `Referrer-Policy` und HSTS — **keine CSP** (E-P5a-15). Eine JSON-Antwort ist kein Dokument, der Browser führt darin nichts aus; die wichtige Zeile ist `nosniff`, damit er sie nicht als HTML deutet |
| `kopf_nonce_attr()` | jedes Inline-`<script>` | ` nonce="…"` |
| `https_tor()` | `auth_guard.php`, `login.php` — **vor** `session_start()` | 301 auf HTTPS, wenn HSTS an ist |

**Und vor dem `session_start()` steht seit Web 20.9.1
`ini_set('session.use_strict_mode', '1')`** (E-P5a-38, Backlog Nr. 205).
Ohne die Zeile übernimmt PHP eine Sitzungskennung, die der Browser mitbringt,
auch wenn es sie nie vergeben hat — Session-Fixation. Sie stand bis dahin an
genau **zwei** Stellen (`install.php`, `wiederherstellen.php`), ausgerechnet
den beiden Wegen, die **keine** Anmeldesitzung tragen.

**Seit Web 20.27.0 heißt „vor dem" wörtlich einen** (Schritt 15 AP2,
E-ZE-12/-13): Es gibt in `server/` genau **einen** `session_start()`, und er
steht in `sitzung_starten()` in `sitzung_lib.php`. Vorher waren es **neun in
neun Dateien und vier Fassungen** — mit vier verschiedenen Sätzen
Cookie-Parameter, deren Unterschied nirgends aufgeschrieben war. Die vier
**Arten** (`app`, `lesend`, `einrichtung`, `passwort`) stehen jetzt als
Tabelle neben der Funktion.

`tools/quelltext/` `sitzungshaertung` zählt in Stufe 1 nach, mit dem
Tokenizer, und prüft seither vier Dinge statt einem: **genau ein Aufruf** ·
**er steht in `sitzung_lib.php`** · **die Härtung steht in den zwölf Zeilen
davor** · **jeder weitere Aufruf ist ein Befund, auch ein gehärteter**.
Gemessen: **1 Aufruf, 0 Befunde**, Selbstprobe **12/12**. Der vierte Punkt
ist der eigentliche Zugewinn — ein neuer Sitzungsstart *mit* Härtung war
unter der alten Prüfung grün und hätte die Cookie-Parameter trotzdem neu
erfinden müssen. Genau so sind die neun entstanden. Was sie *nicht* messen
kann, ist ob die Einstellung **wirkt** — das misst nur eine laufende
Installation (Befehl in der dortigen `LIESMICH.md`).

Weil beide Wege durch *eine* Stelle gehen, bekommt jede Seite und jede
API-Antwort die Kopfzeilen — gleich auf welchem Webserver.

### 5c.2 Die Richtlinie

    default-src 'none';
    script-src 'self' 'nonce-<16 Zufallsbytes je Anfrage>';
    style-src 'self'; style-src-attr 'unsafe-inline';
    img-src 'self' data: <vier Kacheldomains>;
    font-src 'self'; connect-src 'self' <Geocoder, wenn eingeschaltet>;
    worker-src 'self' blob:;
    frame-ancestors 'none'; base-uri 'none'; form-action 'self';
    object-src 'none';
    report-uri api/csp_bericht.php; report-to csp

Sie beginnt bei `default-src 'none'` und zählt auf, was erlaubt ist — nicht
umgekehrt. Ein Skript ohne den Nonce der laufenden Anfrage läuft nicht; damit
ist die klassische XSS-Kette unterbrochen, auch wenn die Maskierung einmal
versagt.

**Drei Abweichungen vom Konzept (SP-5), alle im Kopf von
`kopfzeilen_lib.php` begründet:**

1. **`style-src-attr 'unsafe-inline'`** (E-P5a-32). Drei statische
   `style="…"` sind umgebaut; **zehn** entstehen zur Laufzeit in JavaScript —
   Leaflet-divIcons (`geo.js`), Zeilenvorlagen per `innerHTML`
   (`missiontable.js`), die Balken der Schnittleiste (`schneiden.js`). Sie
   umzubauen hieße, für jeden einzelnen Pfeil einer Spur einen eigenen
   Listener zu setzen — und es änderte an der Angriffsfläche **nichts**, weil
   `el.style.x` CSSOM ist und von CSP ohnehin nicht erfasst wird. Was ein
   Stilattribut anrichten kann, begrenzen `default-src 'none'` und das enge
   `img-src`.
2. **`connect-src` nennt den eingestellten Geocoder** (`geocoder_dienst()`),
   nicht den fest verdrahteten Namen aus dem Konzept — und lässt ihn weg,
   wenn die Adresssuche aus ist.
3. **`img-src` mit `data:` — und das war zuerst anders.** SP-5 führt `data:`
   mit; eine Zählung im eigenen Quelltext ergab **0 Treffer**, also wurde es
   gestrichen, mit dem Satz „der Report-Only-Lauf sagt, wenn das ein Irrtum
   war". Er hat es gesagt: **140 Verstöße** auf den vier Kartenseiten
   (`index.php` 65, `zeitraum.php` 31, `einsatz.php` 28, `tag_spuren.php` 16).
   Ursache ist eine eingebaute Konstante in `leaflet.js` — ein 1×1 Pixel
   großes, durchsichtiges GIF als `data:` (`L.Util.emptyImageUrl`), das
   Leaflet als `src` setzt, wenn es eine Kachel wegräumt. Sie steht in einer
   **minifizierten Bibliothek** und nicht in unserem Quelltext, und genau
   deshalb hat die Zählung sie nicht gesehen.

   **Der Preis ist ausgesprochen:** `data:` in `img-src` erlaubt
   eingeschleustem Markup ein beliebiges Bild aus der Zeichenkette selbst —
   die schwächste Zeile dieser Richtlinie. Sie bleibt, weil die Alternative
   das Patchen einer vendorierten Bibliothek wäre und weil ein Bild kein
   Skript ausführt: Der Weg zum Datenschlüssel bleibt über `script-src`
   verschlossen.

### 5c.3 Zwei Stufen: Report-Only, dann scharf

Zuerst `Content-Security-Policy-Report-Only`: Der Browser meldet, was er
blockiert *hätte*, und führt es trotzdem aus. Erst wenn zwei Wochen lang
nichts Neues gemeldet wird, legt eine BetreiberIn den Schalter
**„CSP scharf schalten"** um (Betrieb → Servereinstellungen, Zeile
`csp_scharf` in `app_state`). Eine Richtlinie, die man am ersten Tag scharf
schaltet, schaltet man am zweiten wieder ab.

`api/csp_bericht.php` sammelt die Meldungen. Er **verlangt ausdrücklich keine
Anmeldung**: Ein Verstoß auf der *Anmeldeseite* ist der interessanteste von
allen — dort steht der Weg des Passworts —, und ein Endpunkt, der eine
Sitzung verlangt, sähe genau den nicht. Daraus folgt, dass jeder ihn füllen
kann; drei Schranken dagegen, keine davon eine Anmeldung:

- **Ratentopf `csp`**, 200 je Stunde und Adresse.
- **Zusammenfassung statt Protokoll:** UNIQUE über
  (`richtlinie`, `quelle`, `seite`) macht aus tausend gleichen Meldungen eine
  Zeile mit Zähler. Eine gebrochene Kartenseite meldete sonst je Kachel
  einmal — deshalb wird die *Quelle* auf Schema und Host gekürzt.
- **Längen werden gekappt**, der Rumpf bei 8 kB abgeschnitten.

**Was nicht gespeichert wird:** keine IP, kein Konto, kein Abfrageteil der
Adresse — `einsatz.php?id=4711` wird zu `einsatz.php`. Diese Anwendung führt
kein Protokoll darüber, wer wann welchen Einsatz geöffnet hat, und eine
CSP-Meldung soll daran nichts ändern. Der Job `aufraeumen` löscht Zeilen,
deren `zuletzt` älter als 30 Tage ist.

Beide Meldeformate werden angenommen: `report-uri` schickt
`{"csp-report": {…}}`, die Reporting-API (`report-to`) eine Liste von
`{"type":"csp-violation","body":{…}}`. Welches ein Browser schickt, ist seine
Sache. Die Antwort ist **immer 204**, auch wenn nichts gespeichert wurde —
eine Fehlermeldung wäre eine Auskunft an jemanden, der nichts zu fragen hatte.

> **Die Falle, und warum `report-to` an einer Bedingung hängt.**
> `report-uri` nimmt eine **relative** Adresse — sie löst sich gegen die Seite
> auf und stimmt damit auch in einem Unterverzeichnis. `report-to` nennt nur
> einen **Namen**; wohin der zeigt, steht in einer eigenen Kopfzeile
> `Reporting-Endpoints`. Stehen beide da, **bevorzugt Chromium `report-to` —
> und verwirft den Bericht ersatzlos, wenn die Gruppe nicht auflösbar ist.**
>
> Gemessen am 15.09.2026 (F6): Mit `report-to csp` und einer
> `Reporting-Endpoints`-Zeile, die eine *relative* Adresse trug, kamen
> **null** Berichte an — der Bilderlauf über 49 Seiten meldete „0
> CSP-Berichte“, während zwei absichtlich ausgelöste Verstöße in der Konsole
> standen. Ohne `report-to` landete derselbe Verstoß sofort in der Tabelle.
>
> Deshalb setzt `kopf_melde_url()` die Bedingung: `report-to` und
> `Reporting-Endpoints` erscheinen **nur**, wenn eine vollständige
> HTTPS-Adresse gebaut werden kann — aus der laufenden Anfrage, nicht aus
> `app.base_url` (ein abweichender Name wäre fremder Herkunft, und der
> Browser schickte erst recht nichts). Sonst trägt `report-uri` allein. Eine
> Kopfzeile, die auf eine Gruppe zeigt, die es nicht gibt, ist schlimmer als
> keine.
>
> Nachgehalten wird das von `tools/proben/csp-browser/browserprobe.mjs`, Abschnitt 7:
> Sie löst einen Verstoß **absichtlich** aus und sieht nach, ob er ankommt.

### 5c.4 HSTS ist eine Einstellung — und `.htaccess` hat die Zeile verloren

Wählbar sind **aus / 1 Tag / 7 Tage / 1 Jahr**, Vorgabe **1 Tag**
(`hsts_tage` in `app_state`). Wer eine Installation aufsetzt, will nicht mit
dem ersten Aufruf ein Jahr an einen Namen gebunden sein, den er vielleicht
nicht behält; wer seit Jahren produktiv läuft, will die 365 Tage.

> **E-P5a-31 — warum `.htaccess` die HSTS-Zeile verliert.**
> `Header always set` **überschreibt**, was PHP schickt. Die Einstellung
> hätte auf Apache nichts bewirkt, und die Oberfläche hätte etwas angezeigt,
> was nicht stimmt. Zwei Wahrheiten über dieselbe Kopfzeile sind schlimmer
> als eine. Die drei übrigen Kopfzeilen stehen weiterhin in `.htaccess`, aber
> als **`setifempty`**: PHP führt, wo PHP läuft, und `.htaccess` deckt die
> statischen Dateien, die nie durch PHP gehen.
>
> **Der Preis ist benannt:** Auf einer bestehenden Installation fällt die
> Bindung von einem Jahr auf einen Tag, bis jemand sie wieder hochstellt.
> Das ist der richtige Weg herum — eine zu kurze Bindung kostet einen Klick,
> eine zu lange kostet ein Jahr.

### 5c.5 Die Client-Adresse: `netz_lib.php`

`rate_ip()` rechnete mit `REMOTE_ADDR`. Hinter einem Reverse Proxy, einem
Loadbalancer oder einem DDoS-Schutz ist das die Adresse **des Proxys** — der
Ratenschutz zählte damit alle NutzerInnen als eine und hätte sie gemeinsam
ausgesperrt.

`netz_client_ip()` wertet `X-Forwarded-For` aus, **aber nur**, wenn
`REMOTE_ADDR` in `config.php` unter `netz.vertrauenswuerdige_proxys` steht
(Adressen oder CIDR, IPv4 und IPv6; der Vergleich läuft byteweise über
`inet_pton`). Die Liste ist **leer vorgegeben** — wer hier einträgt, sagt
„von diesen Adressen glaube ich der Kopfzeile", und das ist eine Aussage über
die eigene Netztopologie, die nur die BetreiberIn treffen kann. Ohne Eintrag
rechnet alles wie vor 20.7.0.

Genommen wird der **letzte** Eintrag der Kette, nicht der erste: Den ersten
kann der Client selbst geschrieben haben.

Dieselbe Liste entscheidet über `X-Forwarded-Proto` (`kopf_https()`, und
damit HSTS und `https_tor()`): Wer die Client-Adresse fälschen könnte, könnte
sonst auch behaupten, eine Anfrage sei über HTTPS gekommen.

### 5c.6 CSRF als Kopfzeile

`csrf_ok()` nimmt seit 20.7.0 das POST-Feld **oder** die Kopfzeile `X-CSRF`.
Zwölf API-Dateien trugen denselben handgeschriebenen Prüfblock; sie rufen nun
`csrf_check()`. Nötig wurde das, weil ein `fetch()` ohne Formular sonst ein
Pseudo-Feld mitschleppen müsste — nebenbei verschwinden zwölf Kopien einer
Prüfung, die hätten auseinanderlaufen können.

### 5c.7 Nachweis: `tools/proben/csp-browser/`

Ein vergessener Nonce legt eine Seite **still** lahm — kein PHP-Fehler, kein
Protokolleintrag, keine rote Seite. Die Probe zählt nach: fünf Regeln
(Inline-Skript ohne Nonce, `<style>`-Block, Ereignis-Attribut,
`javascript:`-Adresse, fremde Herkunft), gemessen mit dem Tokenizer über ein
**Markup-Bild** der Datei. Anleitung und Grenzen in
`tools/quelltext/LIESMICH.md`; sie läuft auch in Stufe 1 der
Auslieferungskette.

## 5d. Der Name dieser Installation (ab Web 20.8.0, P5a/AP5)

**Bis Web 20.7.0** stand der Name **38-mal von Hand** im Quelltext, in **drei**
Schreibweisen: „Gen-EM NAdoku" (Browsertab, Kopfleiste, Anmeldeseite,
Wartungsseite, Schlüsselblatt, Installer, GPX-Datei, `from_name`), „Gen-EM
Einsatzdokumentation Notarzt" (alle acht Mailtexte) und „Einsatzdokumentation
Notarzt" in der Testmail — **ohne „Gen-EM"**. Die dritte ist der Beweis, nicht
der Sonderfall: Eine abweichende Schreibweise fällt niemandem auf, solange man
acht Dateien nebeneinanderlegen müsste, um sie zu sehen.

Der zweite Grund wiegt schwerer: **„Gen-EM" ist eine Marke, keine Funktion.**
Logo, Impressum und Datenschutztext sind längst je Installation einstellbar
(E-P3-19/20, R32) — der Name war es nicht.

### 5d.1 Zwei Werte, zwei Gebrauchslagen

| Funktion | Vorgabe | Wo sie steht |
|---|---|---|
| `instanz_kurz()` | `Gen-EM NAdoku` | Browsertab, Kopfleiste, Anmeldeseite, Wartungsseite, Schlüsselblatt |
| `instanz_name()` | `Gen-EM Einsatzdokumentation Notarzt` | Mailbetreff, Grußformel |

Beide liegen in `app_state` (`instanz_name`, `instanz_kurz`) und werden unter
**Verwaltung → Installation**, Karte „Name" gepflegt — dort, wo auch das
Logo steht. Leer heißt „zurück auf die Vorgabe"; höchstens 80
Zeichen.

### 5d.2 Die Datei lädt nichts — und das ist der Kniff

`instanz_lib.php` hat **keine** Abhängigkeit auf oberster Ebene, dasselbe
Muster wie `smtp.php`. Drei Stellen dürfen hier nicht anklopfen:

- **`install.php`** läuft, bevor es eine Datenbank gibt;
- **`wartung_lib.php`** ist ausdrücklich ohne Datenbank gebaut — sie muss
  antworten, während die Datenbank umgebaut wird;
- **das HTTPS-Tor** in `kopfzeilen_lib.php` antwortet vor allem anderen.

Alle drei benutzen `INSTANZ_KURZ_VORGABE` unmittelbar. Die Funktionen prüfen
mit `function_exists('app_state_lesen')` selbst, ob es eine Datenbank gibt —
ein Verbindungsversuch während eines Schemaumbaus läuft im schlechten Fall in
die Zeitgrenze statt in eine Ausnahme.

`ui.php` benutzt `ui_instanz_kurz()` mit demselben Rückfall, weil `install.php`
die Seitenhülle vor der Ersteinrichtung lädt.

### 5d.3 Die Wartungsseite bekommt den Namen aus dem Schalter

Sonst zeigte ausgerechnet die Seite, die Fremde zu sehen bekommen, „Gen-EM
NAdoku", während die Installation anders heißt. `wartung_einschalten()`
schreibt den Namen deshalb als Schlüssel `wer` in `wartung.lock` — beim
Einschalten steht die Datenbank noch. Fehlt er oder ist die Datei unlesbar,
gilt die Vorgabe; **„Die Datei ist der Schalter, nicht ihr Inhalt"** bleibt
unverändert.

> `wartung_daten()` ist eine **weiße Liste**. Beim ersten Versuch stand `wer`
> in der Datei und wurde beim Lesen still verschluckt, weil die Liste ihn
> nicht kannte — die Seite zeigte die Vorgabe, und nichts deutete auf einen
> Fehler hin. Wer dort einen Schlüssel ergänzt, ergänzt beides.

### 5d.4 Der Name wird geprüft, weil er in einen Mailbetreff geht

`instanz_namen_setzen()` weist Steuerzeichen und Zeilenumbrüche ab. Ein
Zeilenumbruch im Betreff wäre eine **eingeschleuste Kopfzeile**, und der
Betreff ist die eine Stelle, an der ein selbst eingetippter Wert das Haus
verlässt, ohne dass ein Mensch ihn noch einmal ansieht.

**Gemessen wird gegen den Endpunkt, nicht gegen das Formular:** Ein `<input
type="text">` entfernt Zeilenumbrüche von sich aus — ein gebastelter POST
nicht. 6 von 6 Versuchen wie erwartet (Zeilenumbruch, Wagenrücklauf, Nullbyte,
Tabulator, 81 Zeichen, gültig); bei allen fünf abgewiesenen bleibt die Vorgabe
in der Datenbank stehen.

### 5d.5 Zwei Stellen bleiben ausdrücklich fest

- **Die Fußzeile „© Gen-EM · Open Source"** (`ui.php`) ist die *Urheberschaft
  der Software*, nicht der Name des Betriebs. Wer diese Anwendung aufsetzt,
  darf seinen Dienst benennen — nicht den, der sie geschrieben hat.
- **`creator` in jeder GPX-Datei** (`gpx_creator()` in `gpx_lib.php`,
  `assets/export.js` für den großen Export) benennt die **Software**, nicht
  die Installation — GPX 1.1 beschreibt es als *„the software that created
  your GPX document"*. Wer die Anwendung aufsetzt, hat sie nicht geschrieben.
  Seit Web 20.8.0 mit Fassung (`Gen-EM NAdoku 20.8.0`), weil diese Anwendung
  GPX auch wieder **einliest**. Volle Begründung: `docs/Export-Format.md` 3.5.

  **Gemessen am 16.09.2026:** Der Referenz-Export enthält **204** GPX-Dateien,
  alle mit `creator`, und `normalisieren.py` blendete das Attribut **nicht**
  aus. Seither ist dort die **Fassung** maskiert (wie `App-Version:` seit
  jeher) und der **Name** weiterhin verglichen — **204 von 204** normalisieren
  danach gleich, **0** Unterschiede, die Referenz musste nicht neu erzeugt
  werden. Gegenprobe: ein fremder `creator` fällt weiterhin auf.

### 5d.6 Zwei Adressen der Installation (Web 20.9.0, E-P5a-40)

Dieselbe Fehlerklasse wie beim Namen, eine Fassung später gefunden: In
**sieben** Mailtexten stand dieselbe persönliche Adresse der EntwicklerIn, fest
im Quelltext. Eine fremde BetreiberIn verwies ihre NutzerInnen an eine
Unbekannte.

| Funktion | `app_state` | Wofür | Wenn leer |
|---|---|---|---|
| `instanz_kontakt()` | `instanz_kontakt` | die Zeile „Bei Fragen wende dich an …" in **jeder** E-Mail (`mail_rahmen()`) | die Zeile **fällt weg** |
| `betrieb_mail()` | `betrieb_mail` | Betriebspost: volles Speicherkontingent, überfällige Sicherungen | weiterhin an **alle** mit Verwaltungsrecht |

Gepflegt unter **Verwaltung → Installation**, Karte „Adressen".

**Sie stehen in `app_state`, nicht in `config.php`.** Jene wird zur Laufzeit
nicht geschrieben; was dort steht, lässt sich nur über FTP ändern. Eine
Adresse, die in jeder Mail steht, muss eine BetreiberIn selbst umstellen
können.

**Die Kontaktadresse ist nicht `smtp.from`.** Das ist der *Absender* und auf
einer gut eingerichteten Anlage ein `noreply@`. Eine Mail, die im Fehlerfall
auf ein Postfach verweist, das niemand liest, ist schlimmer als eine ohne
Verweis — deshalb fällt die Zeile weg, statt etwas Falsches zu behaupten.

**Die Betreiberadresse geht in die vorsichtige Richtung.** Steht etwas drin,
geht die Betriebspost **nur** dorthin; bleibt sie leer, gilt die bisherige
Rollenliste. Eine leere Einstellung darf keine Warnung verschlucken.
`mail_betriebsziele(bool $nurAngemeldete = false)` in `mail_lib.php` hält die
Auswahl an **einer** Stelle — drei Stellen bauten dieselbe Liste, und die
dritte hatte bereits eine abweichende Sortierung (R83).

**Geprüft** gegen den Endpunkt, nicht gegen das Formular: 7 Fälle (ohne `@`,
Zeilenumbruch, CRLF, 191 Zeichen, Betreiberadresse ohne `@`, beide gültig,
beide leer), **5 abgewiesen, 2 angenommen**, alle mit der erwarteten Meldung.
Die Längenprüfung steht **vor** der Syntaxprüfung — `filter_var()` weist eine
überlange Adresse ebenfalls ab, aber mit der Meldung „keine gültige
E-Mail-Adresse", und wer eine syntaktisch einwandfreie Adresse eintippt und
das liest, sucht an der falschen Stelle.

### 5d.7 `app_url()` — eine Basisadresse statt sieben Verkettungen

`base_url` wurde an sieben Stellen von Hand verkettet, **fünf davon ohne
`rtrim()`**. Steht in der `config.php` ein Schrägstrich am Ende, entstand
`https://host//pw_handling.php`. `app_url(string $pfad = '')` liefert
`rtrim(base_url, '/')` plus Pfad; ohne `base_url` eine leere Zeichenkette (ein
halber Link ist besser als eine Ausnahme mitten im Anlegen eines Kontos).

Seit Web 20.9.0 liest **nur noch** `instanz_lib.php` selbst den Wert
(2 Stellen) und `install.php`, das ihn erfragt und in die `config.php`
schreibt (4 Stellen). Drei weitere Treffer stehen in **Kommentaren** —
`grep` ohne Kommentarfilter zählt 10 und sagt damit nichts.

Sie liest **zuerst das schon geladene `$GLOBALS['CFG']`** und fällt nur dann
auf `require config.php` zurück, wenn dort nichts steht — sonst wäre es ein
Dateizugriff je Aufruf.

> Nebenbei gefunden: `smtp.php` schickte bei leerer `base_url` ein nacktes
> `EHLO ` — `parse_url('')` liefert `false`. Das ist kein gültiger Befehl;
> strenge Relais antworten mit 501, und der Versand scheitert an einer Stelle,
> an der niemand ihn vermutet. Jetzt steht dort ein Rückfall auf `localhost`.

### 5d.8 Was von „Gen-EM" im Quelltext bleibt

`grep -rn "gen-em\.org" server/` ergibt seit Web 20.9.0 **0**. Von den festen
`'Gen-EM NAdoku'` benutzen Installer und HTTPS-Tor jetzt
`INSTANZ_KURZ_VORGABE`; **eine** bleibt als Zeichenkette stehen, und zwar mit
Grund: die **PHP-zu-alt-Meldung** ganz oben in `install.php`. Alles oberhalb
der Weiche muss auf einer alten PHP-Fassung noch übersetzbar sein, also darf
dort nichts geladen werden. Der `require` auf `instanz_lib.php` steht deshalb
**hinter** der Weiche — eine Besucherin auf PHP 8.0 hat die Seite dort längst
verlassen.

Die **alte Produktivdomain** ist aus der lebenden Dokumentation verschwunden
(sie stand zuletzt siebenmal darin; wie sie hieß, steht im Changelog zu
Web 20.9.0). Stehen bleibt sie in der **Historie** — Changelog,
Rahmenplan-Archiv, `docs/konzepte/erledigt/` — und im **Prüffall der
Wortliste**: Eine Historie, die man umschreibt, ist keine mehr, und ein
Prüffall, aus dem man den Suchbegriff entfernt, prüft nichts.

> Dass dieser Absatz den Namen selbst **nicht** nennt, ist kein Versehen.
> `docs/Technik.md` steht im Bereich `c` der Wortliste, und die zählte ihn
> beim ersten Versuch prompt als zwei Treffer. Ein Werkzeug, das die eigene
> Erfolgsmeldung als Befund liest, hat recht.

**In den mobilen Clients bleibt `gen-em.org` stehen, und zwar mit Ansage**
(Auftraggeber, 16.09.2026 — E-P5a-42). Betroffen sind vier Stellen:

| Stelle | Was |
|---|---|
| `watch/resources/settings/properties.xml` | Vorgabe-Serveradresse der Uhr-App — **sichtbar in Garmin Connect** |
| `watch/resources/settings/settings.xml` | die Beschriftung dazu |
| `android/handy/build.gradle.kts` | Vorgabe-Serverbasis des ausgelieferten APK |
| `android/…/kopplung/Serveradresse.kt` | der Fehlertext „Erwartet wird ein Rechnername wie …" |

**Es sind Vorgaben, keine Festverdrahtungen** — beide Clients nehmen eine
andere Adresse an, und die Uhr zeigt das Feld in Garmin Connect. Eine
BetreiberIn, die diese Anwendung aufsetzt, baut ihre Apps ohnehin selbst
(Signatur, Store-Eintrag); dabei setzt sie die Vorgabe. Sie auf
`nadoku.beispieldomain.de` zu ziehen hieße, dass die App **dieser**
Installation ab Werk ins Leere zeigt — und kostete je eine eigene
Auslieferung von Uhr und Android.

> **P2 hatte genau das schon einmal getan** (R29/R48, Uhr 1.11.1): Die
> Beispieldomain zog von der alten Produktivdomain auf
> `nadoku.beispieldomain.de`. Später wurde sie auf `nadoku.gen-em.org`
> zurückgestellt. Wer diesen Absatz ändern will, ändert damit eine
> Entscheidung, die **zweimal** gefallen ist — nicht ein Versehen.

Ebenso bleibt der **Paketname** `org.genem.nadoku`: Er lässt sich nicht
ändern, ohne installierte Apps zu brechen (der Bindestrich aus `gen-em.org`
entfällt, weil ein Paketname keinen trägt — steht in
`android/handy/build.gradle.kts`). Und `WACHE_BASIS` in
`.github/workflows/integritaet.yml` ist die Adresse **dieser**
Installation — Betriebskonfiguration der Auslieferungskette, keine Eigenschaft
der Software.

### 5e Der Ratenschutz — Leiter, Schwellen, Verlangsamung, Mengenbremse, Sicherheitsseite (P5a/AP6–AP8)

**Was bis Web 20.9.1 galt.** Eine Sperre dauerte fest 15 Minuten — die erste
wie die hundertste. Wer geduldig ist, bekommt damit 10 Versuche je
Viertelstunde, dauerhaft. Und alle Fehlversuche der Installation zusammen
wurden nirgends gezählt: Ein Angriff über tausend Namen sah aus wie tausend
einzelne Vertipperinnen.

#### 5e.1 Die Leiter

Je Merkmal eine **Stufe 1–4** (0 = nie gesperrt), Dauer aus `rate_leiter()`,
Vorgabe **15 / 20 / 30 / 60 min**. Verfall: **24 h ohne Fehlversuch**
(`stufe_bis`). Alles einstellbar unter Betrieb → Servereinstellungen.

> **Die Stufenzählung ist 1-basiert**, obwohl das Konzept an einer Stelle
> „Stufe 0–3" sagt. An der anderen sagt es „Sammelmail bei Stufe 4", und
> beides zusammen geht nicht auf. Gewählt ist die Lesart, die jemand
> ausspricht: Stufe 4 heißt wörtlich die 60-Minuten-Sperre.

**Nicht jeder Topf bekommt eine Leiter** — nur `login`, `login_ip`, `salt`,
seit Web 20.11.0 `ingest` und `ingest_ip`, seit Web 20.24.0 `blatt` (das Prüfen
des Schlüsselblatts) und seit Web 20.42.0 `totp` (der Code des
Zweitfaktors):

| Topf | Leiter | warum |
|---|---|---|
| `ingest`, `ingest_ip` | **ja** (seit 20.11.0) | bei `ingest.php` läuft kein Vorgang, den die längere Sperre unterbricht — die Daten liegen in der Warteschlange des Geräts und kommen später an. Die Sperre kostet den legitimen Fall nichts als Zeit, und Zeit ist genau das, was sie den illegitimen kosten soll (E-P5a-48) |
| `reset` | **nein** | sperrt heute 3600 s; jede Sprosse unterhalb der vierten wäre *schwächer*. Und sein Scheitern ist absichtlich still — `reset_request.php` antwortet im gesperrten Fall wortgleich wie im erlaubten |
| `pair`, `pair_start`, `pair_code` | nein | eine längere Sperre unterbräche dort einen Vorgang, der **gerade läuft**: Jemand steht am Gerät mit einem Code, der in zehn Minuten verfällt. Eine Stunde Sperre schreckt keinen Automaten ab, sie beendet die Kopplung für den Menschen |
| `demo`, `demog`, `testmail`, `csp`, `health`, `rt_vorschau` | nein | die zählen **Menge**, nicht Fehlversuche — es gibt dort niemanden, der eskaliert; bei `health` träfe eine wachsende Sperre das Monitoring der BetreiberIn, bei `rt_vorschau` (seit Web 21.1.0) eine angemeldete Verwaltung, die gerade einen Rechtstext schreibt |

> **Die Trennlinie stand bis Web 20.11.0 falsch da.** Bei den Kopplungstöpfen
> hieß es, „dahinter steht ein Gerät, das nicht lesen kann, was auf der Seite
> steht" — auf `ingest.php` trifft das wörtlich genauso zu, die Regel hätte
> also gegen die Leiter entschieden, die AP7 dort baut. Sie war nicht falsch
> gemeint, sondern falsch formuliert (E-P5a-48).

#### 5e.2 Zwei Schwellen, zwei Töpfe

10 Fehlversuche je **Konto**, 50 je **Anschluss** (`login_ip`). Bis Web 20.9.1
galten für beide dieselben 10 — hinter einem Klinik-NAT sperrte damit die
zehnte Vertipperin die übrigen neunzehn aus.

**Warum zwei Töpfe und nicht zwei Zahlen in einem:** Diese Tabelle hängt am
**Topf**, nicht am Merkmal, und `rate_misserfolg()` bindet für alle Merkmale
eines Aufrufs dieselben Parameter. Zwei Grenzen in einem Topf hieße, die
Schleife umzubauen — und damit die Bauart aufzugeben, die in
`ratelimit_lib.php` zweimal ausgeschrieben begründet ist. Der hauseigene Weg
ist ein zweiter Topf mit ausdrücklicher Merkmalsliste (Muster:
`RATE_DEMO_GLOBAL`).

> **`login.php` muss beide Töpfe leeren**, wenn eine Anmeldung gelingt. Sonst
> läuft eine Praxis über den Tag in ihre 50 hinein, ohne dass irgendjemand
> etwas falsch gemacht hätte.

#### 5e.3 Die Verlangsamung — und wo sie ansetzt

Topf `global`, Merkmal `alle`, `max = PHP_INT_MAX` (er sperrt **nie**).
Gelesen wird nur `versuche`; ab 200 / 400 / 800 / 1600 je 15 min wartet jede
**fehlgeschlagene** Anmeldung 1 / 2 / 4 / 8 s.

**Zwei Stellen, an denen man sie falsch einbaut:**

1. **Nicht als `usleep()` vor der Antwort**, sondern als erhöhte Mindestdauer
   *durch* `rate_gleiche_dauer_gebremst()`. Jene stellt die Antwortzeit des
   Fehlerzweigs auf einen festen Wert; ein zusätzliches Warten daneben
   zerstörte genau die Gleichheit, die sie herstellt.
2. **Wer schon gesperrt ist, wird nicht verlangsamt.** Jede wartende Anfrage
   hält einen PHP-Arbeitsprozess. Bei 1600 Fehlversuchen je 15 Minuten und
   8 s Wartezeit warteten dauerhaft **rund 14 Anfragen gleichzeitig** — auf
   einem Webspace mit zehn Arbeitern wäre die Bremse die Überlastung, die sie
   verhindern soll. Gesperrte Anfragen kosten nichts; damit hängt die Zahl der
   Wartenden an der **Sperrrate** und nicht an der Flutrate. Verraten wird
   nichts: Die Meldung sagt dem Aufrufer, dass er gesperrt ist.

Der Zähler hat eine **Fensterprüfung** beim Lesen. Ohne sie bliebe eine
Installation nach einem Angriff verlangsamt, bis zufällig wieder jemand ein
Passwort falsch eintippt — im schlechtesten Fall wochenlang, und niemand fände
den Grund.

#### 5e.4 Zwei Fehler, die ohne Meldung durchgegangen wären

**Der Gesperrte hätte sich durch Klopfen befreit.** `rate_misserfolg()` setzte
`gesperrt_bis = NULL`, sobald das **Zählfenster** ablief. Folgenlos, solange
bei allen Töpfen `sperre == fenster` galt — und das galt. Mit einer Leiter bis
60 min bei 15 min Fenster hätte ein einziger Fehlversuch nach Fensterablauf
die **laufende** Sperre gelöscht.

**Die Stufe wäre nie zurückgefallen.** Der Verfall wurde nur dort
aufgefrischt, wo *nicht* gesperrt wurde — also bei den ersten neun
Fehlversuchen. Jeder schob die Frist um 24 h vor, sodass sie beim zehnten nie
abgelaufen war. Gefunden von `tools/proben/raten/`; im Betrieb wäre es niemandem
aufgefallen.

#### 5e.5 Der Weg zurück

`pw_handling.php` rief bis Web 20.9.1 **keine einzige** `rate_*`-Funktion. Wer
sein Passwort über den Link zurücksetzte, blieb an der Anmeldung gesperrt.
`rate_konto_freigeben()` räumt jetzt `login` und `salt` dieses Kontos — **nur
das Konto, nicht die Adresse**.

Für die Betriebsseite gibt es `rate_sperre_aufheben($topf, $merkmal, $wer)`.
**Nicht `rate_erfolg()`**: Jene bildet die Merkmale aus dem *Aufrufer* — auf
der Betriebsseite wäre das die IP der AdministratorIn; der Knopf löschte ihre
eigene Zeile, meldete Erfolg, und die Sperre bliebe stehen.

#### 5e.6 Das Fenster zwischen Deploy und `update.php`

Die Grundzählung in `rate_misserfolg()` nennt `stufe` und `stufe_bis` **mit
keinem Wort** und läuft in diesem Fenster unverändert weiter. Die Leiter steht
in einem **zweiten, eigen gefangenen** Statement und tut dort nichts; gesperrt
wird dann mit der festen Dauer wie vor Web 20.10.0.

Nähme man die neuen Spalten in das vorhandene `INSERT` auf, würde es dort
werfen — und weil `rate_misserfolg()` alles in *einem* `try/catch` fängt und
still zurückkehrt, zählte der Ratenschutz gar nicht mehr. Für **alle** Töpfe,
nicht nur für die Leiter, und ohne dass irgendetwas rot würde.

**Nachweis:** `php tools/proben/raten/probe.php` — 50 Prüfungen, 0 Befunde.

#### 5e.7 Die Mengenbremse von `ingest.php` (Web 20.11.0, P5a/AP7)

**Was bis Web 20.10.0 galt.** `ingest.php` war der **einzige Endpunkt der
Anwendung ohne Ratenschutz**. Das war kein Versehen, sondern eine offene
Grundsatzfrage (R19, Backlog Nr. 17): Ein Zähler am Upload-Endpunkt kann eine
Uhr aussperren, die ihre Daten loswerden will. Mit der Verteilung der Clients
über die Stores (E-PV-1) hat sich die Abwägung gedreht — E-R45-6 nennt genau
diese Kombination, öffentlicher Client mit Geräteschlüssel ohne Bremse, als
die Flutungsgefahr aus P5. Entschieden mit E-P5a-01.

**Zwei Töpfe, je 30 Fehlversuche pro 15 Minuten, mit Leiter:**

| Topf | Merkmal | wann er zählt |
|---|---|---|
| `ingest` | `id:<Gerätekennung>` | die Kennung **gibt es**, der Schlüssel passt nicht |
| `ingest_ip` | `ip:<Adresse>` | die Kennung **gibt es nicht** |

**Gezählt werden ausschließlich Fehlversuche** (E-P5a-01 (1)). Nicht gezählt
werden `405`, `413`, `403 device_disabled`, `400` und `500` — und der
Wartungsmodus schon gar nicht, der antwortet in `db.php`, bevor `ingest.php`
läuft. Ein gelungener Upload leert den Kennungstopf.

**Die Bremse sitzt geteilt**, und das ist wesentlich:

- Die **Prüfung** steht **vor** der Geräteabfrage — genau die will sie
  einsparen — und damit auch vor `demo_reset_wenn_faellig()` und vor
  `beginTransaction()`. Eine Bremse, die erst hinter der Arbeit greift, bremst
  nichts. Sie liest beide Töpfe in **einem** Statement
  (`rate_sperre_paare()`), weil das der heißeste Weg der Anwendung ist.
- Die **Zählung** steht nur an den beiden Zweigen, die mit `401` enden.

**Die Antwort ist `429` mit `{"error":"zu_viele_versuche"}` und
`Retry-After`** — und sie nennt nicht, welcher Topf gegriffen hat.

##### Warum 30 und nicht 10

Gemessen am Sendeplan des Referenzdatensatzes
(`tools/referenzdatensatz/einspielen/messprotokoll.json`): 612 Anfragen, Spitze
**3** an einem einzelnen Auslöser, 199 Abstände von 0 s — ein Dienst kommt in
**Stößen**. Der Stoß, der die Grenze bestimmt, ist aber ein anderer: ein
**Schlüsselwechsel**. Danach liegen die Pakete eines ganzen Dienstes in der
Warteschlange des Geräts und laufen der Reihe nach in die Abweisung;
**14 Teilstücke in einem Paket** sind gemessen (`teilstuecke_je_paket.max`).
30 lässt zwei solche Stöße durch.

##### Das Existenzorakel — benannt, verkleinert, nicht geschlossen

Das Konzept nannte **50** für den Adresstopf. Verschiedene Schwellen sind eine
Auskunft: Wer dieselbe geratene Kennung von einer Adresse aus hämmert, bekäme
sie bei existierender Kennung ab dem 31. Versuch abgewiesen, bei nicht
existierender erst ab dem 51. Genau diese Auskunft hat **M4-07** in
`ingest.php` mit dem Blindvergleich gegen `GERAET_VERGLEICHSWERT` beseitigt.

**Deshalb 30 gegen 30** (E-P5a-47). Die Absenkung kostet keinen legitimen
Verkehr: In den Adresstopf zählen ausschließlich *unbekannte* Kennungen, und
ein gekoppeltes Gerät sendet nie eine unbekannte. Der NAT-Einwand trifft nur
Geräte, deren Eintrag im Web gelöscht wurde (R47) und die weitersenden — die
sollen aufhören.

> **Was bleibt, wird nicht beschönigt.** Eine zweistufige Probe unterscheidet
> weiterhin: 31 Versuche mit der fraglichen Kennung, danach einer mit einer
> offensichtlich erfundenen. Kommt darauf `401`, war die erste bekannt. Das zu
> schließen hieße, auch Fehlversuche **bekannter** Kennungen in den Adresstopf
> zu zählen — dann sperrt ein einziges Gerät mit veraltetem Schlüssel seine
> ganze Adresse, einschließlich des soeben neu gekoppelten, das die Abhilfe
> ist. Die Kennung ist `dev-` + 128 Bit Zufall und laut `pair.php`
> ausdrücklich **kein Geheimnis**; das Orakel beantwortet nur die Frage „ist
> diese Kennung, die ich ohnehin schon habe, noch eingetragen?"

##### Der Vermerk am Gerät

`devices.abgewiesen_seit` (der **erste** Fehlversuch einer Serie, nicht der
letzte) und `devices.abgewiesen_anzahl`. Sichtbar unter **Einstellungen →
Geräte** (Kleinzeile und orange Plakette) und unter **Betrieb → Status**
(Zeile „Abgewiesene Geräte"). Beide werden beim nächsten gelungenen Upload
geleert.

##### Das Fenster zwischen Deploy und `update.php`

Die beiden Spalten kommen mit einer Migration. Die Geräteabfrage in
`ingest.php` und die auf der Kontoseite haben deshalb einen **Rückfall** ohne
sie; die Statusseite fängt. Der Schutz selbst hängt nicht daran — er zählt in
`rate_limits`, und die Tabelle steht seit Web 20.10.0.

##### Was die Clients tun müssen: nichts

Ein Client, der `401` richtig behandelt, behandelt `429` ebenfalls richtig.
Beide tun es bereits: Die Uhr fällt in ihren Zweig „später erneut"
(`Uploader.mc`), die Android-App in `Sendeantwort.SpaeterErneut`
(`code != 200`), und beide brechen den Sendelauf ab, ohne etwas zu verwerfen.
`Retry-After` liest keiner von beiden — die Uhr **kann** es nicht, der Rückruf
von Connect IQ bekommt `(code, data)` und keine Kopfzeilen. Die Zeile steht
für einen fremden Client an derselben Schnittstelle.

**Nachweis:** `php tools/proben/ingest/probe.php` — 83 Erwartungen, 0 nicht
erfüllt, davon 21 in Teil 10. Laufzeit über den erzeugten Sendeplan (612
Anfragen, 64 478 Punkte, je zwei Läufe): Median **14,43 ms ohne**,
**15,14 ms mit** Bremse (+4,9 %), Mittel 19,67 gegen 20,33 ms (+3,4 %), 0
Fehlversuche in allen vier Läufen — bei einer Streuung von 3 bis 4 % zwischen
zwei *gleichen* Läufen.

#### 5e.8 Die Sicherheitsseite (Web 20.12.0, P5a/AP8)

**Betrieb → Status → Sicherheit** (`betrieb_sicherheit.php`). Fünf Karten:
aktive Sperren mit dem Knopf „Aufheben", Verlangsamung, Mengenbremse der
Geräte, Ereignisse der letzten 30 Tage, Mailregel.

**Sie ist eine Unterseite und kein Menüpunkt** (E-P5a-08). Für eine
BetreiberIn stehen siebzehn Einträge in der Leiste; einer mehr für eine Seite,
die man an guten Tagen nie braucht, wäre an der falschen Stelle teuer.
`ui_geruest_start(['menue' => 'betrieb_status'])` hält den Eintrag der
Elternseite aktiv — dasselbe Muster wie `admin_user.php` unter
`admin_users.php`. In `ui_einstellungen_punkte()` ist **nichts** einzutragen;
die Sprungmarken der Karten entstehen von selbst aus `.karte[id]`.

##### Drei Lesefunktionen und keine vierte

Sie stehen in `ratelimit_lib.php` neben `rate_ereignis()`, nicht als
Einzelabfragen in der Seite — dieselbe Trennung wie zwischen `status_lib.php`
und `betrieb_status.php`:

| Funktion | liefert |
|---|---|
| `sicherheit_ereignisse($arten, $toepfe, $grenze)` | die Ereignisse der letzten 30 Tage **plus die Gesamtzahl** |
| `sicherheit_bremse_geraete($grenze)` | Geräte mit `abgewiesen_anzahl > 0` |
| `sicherheit_mailregel()` | an/aus, letzte Meldung, Empfänger, höchste Sprosse |

`sicherheit_ereignisse()` liefert die **Gesamtzahl** mit, nicht nur die Zeilen:
`LIMIT 200` ist unter Beschuss schnell erreicht, und eine Karte, die dann
zweihundert Zeilen zeigt und schweigt, sagt „das war alles".

##### Drei Ableitungen wurden eine

„Ist dieses Merkmal ein Konto oder eine Adresse?" stand dreimal nachgebaut —
in `rate_sperre()`, in `sicherheit_melden_pruefen()` und auf der Statusseite.
`rate_sperren_aktiv()` liefert **`art`** jetzt mit. Dasselbe bei den
Gerätevermerken: Die Statusseite stellte eine eigene Abfrage auf dieselben zwei
Spalten und ruft jetzt `sicherheit_bremse_geraete()`.

##### Was die Seite ändert — genau eines

`rate_sperre_aufheben($topf, $merkmal, $wer)`, über POST mit CSRF-Token.
**Nicht `rate_erfolg()`**: Jene bildet die Merkmale aus dem *Aufrufer* — das
wäre die Adresse der AdministratorIn; der Knopf löschte ihre eigene Zeile,
meldete Erfolg, und die Sperre bliebe stehen. `$wer` ist die Kontokennung der
Handelnden; ohne sie bliebe die Spalte `wer` leer, und das Ereignis
„aufgehoben" sagte nicht, wer aufgehoben hat.

##### Was **nicht** protokolliert wird, und warum das auf der Karte steht

Ein Sperrereignis entsteht nur an den **sieben Töpfen mit Leiter** — `login`,
`login_ip`, `salt`, `ingest`, `ingest_ip`, `blatt`, `totp` (bis Web 21.1.0
stand hier „fünf"; `blatt` und `totp` waren beim Nachtragen der Leiter nicht
mitgezählt worden). Die übrigen neun (`reset`, die drei
Kopplungstöpfe, `demo`, `demog`, `testmail`, `csp`) sperren über den
Rückfallweg **ohne** Protokollzeile. Ohne diesen Satz auf der Karte liest sich
eine kurze Liste als „es war fast nichts", obwohl neun Töpfe gar nicht
berichten.

Und die Karte „Verlangsamung" zeigt **Anstiege, keine Phasen**: Vermerkt wird,
wenn die Stufe steigt; ein Ende hat kein eigenes Ereignis.

##### Zwei Fehler, die AP8 nebenbei behoben hat

**Das Protokoll hing am Mailschalter.** `sicherheit_melden_pruefen()` kehrte
als *erste* Zeile zurück, wenn `rate_mail_an()` false ist — und darin stand
das Vermerken der Verlangsamungsstufe. Wer die Sammelmail abschaltete,
schaltete stillschweigend auch das Protokoll ab. Protokollieren und Melden
stehen jetzt in zwei Funktionen
(`sicherheit_verlangsamung_vermerken()` / `sicherheit_melden_pruefen()`).

**Der Gerätevermerk verfiel nie.** `devices.abgewiesen_*` wird beim nächsten
gelungenen Upload geleert — den gibt es nicht mehr, wenn das Gerät ausgemustert
ist. Der Aufräumjob hat dafür jetzt den Schritt **`Geraetevermerke`**
(30 Tage ab `abgewiesen_seit`).

##### Der Datenschutztext

Die Seite zeigt IP- und E-Mail-Adressen im Klartext. Die Anwendung liefert
**keinen Rechtstext mit** (R32); sie kann nur **vorschlagen**. Unter
*Verwaltung → Rechtstexte* (Reiter Datenschutzerklärung, Karte
„Textbausteine"; bis Web 21.0.0 unter *Installation*) steht deshalb ein
zweiter Textbaustein neben dem zur Adresssuche aus S9/AP2 — und anders als jener **ohne Bedingung**: Den
Ratenschutz gibt es in jeder Installation, und er lässt sich nicht abschalten.

**Nachweis:** `node tools/bedienprobe/probe.mjs --nur P5a-AP8` — 1 von 1 Weg
erfüllt, gemessen am DOM *und* an der Datenbank (Zeile 1 → 0, `rate_limits`
1 → 0, Ereignis „aufgehoben" mit Kontokennung).


## 5e. Die Verbindungsgrenze (ab Web 20.13.0, P5a/AP9, E-P5a-18)

**„Ausgelastet" ist nicht „kaputt".** MySQL/MariaDB weist eine Verbindung ab,
wenn eine von drei Grenzen erreicht ist. Die Nummern sind verschieden, und das
ist der Punkt:

| Nummer | Grenze | wer sie setzt |
|---|---|---|
| **1040** | `max_connections` — der ganze Datenbankserver | der Hoster, serverweit |
| **1203** | Systemvariable `max_user_connections` | der Hoster, für alle Konten gleich |
| **1226** | `ALTER USER … WITH MAX_USER_CONNECTIONS n` | der Hoster, **für dieses eine Datenbankkonto** |

E-P5a-18 nannte die ersten beiden. **Gemessen am 16.09.2026 gegen MariaDB
10.11 ist der Fall, den ein geteilter Webspace herstellt, die dritte Zeile** —
eine GRANT-Grenze am Konto, also 1226. Alle drei stehen deshalb in
`UEBERLAST_CODES` (`wartung_lib.php`).

Bis Web 20.12.0 kam in diesen Fällen eine **500** heraus, mit dem
ungefilterten Text der PDO-Ausnahme — darin stehen Hostname und Benutzername
der Datenbank.

| | |
|---|---|
| Abfangstelle | `db()` in `db.php`, um den `new PDO(...)` herum. Nur beim **Verbinden**; alles andere bleibt ein Fehler |
| Antwort, Seiten | 503 mit einer Seite im Aufbau der Wartungsseite (gemeinsames Gerüst `stoerung_seite_html()`), Satz „Der Server ist gerade ausgelastet — bitte in einer Minute noch einmal", **kein** Skript, **kein** Knopf „Zur Verwaltung" (die Verwaltung antwortet ebenfalls nicht) |
| Antwort, Maschinen | 503 `{"error":"ausgelastet","meldung":"…"}` |
| Kopfzeilen | `Retry-After: 5`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`. **Ohne `kopfzeilen_seite()`** — jenes liest zwei Einstellungen aus `app_state`, also aus der Datenbank, die gerade nicht antwortet |
| Wer bekommt JSON | `JSON_SKRIPTE_AUSSERHALB_API` — `ingest.php`, `pair.php`, `auth_salt.php`, `jobs.php` — plus alles unter `/api/`. **Vier statt zwei:** Für den Wartungsmodus genügten zwei, weil die beiden anderen in `WARTUNG_AUSNAHMEN` stehen und das Tor bei ihnen vorher umkehrt. Die Überlast kennt keine Ausnahmen. `gpx.php` bleibt draußen: Es wird vom Browser angesteuert, nicht per `fetch()` geholt |
| Zähler | Datei `server/ueberlast.json` — laufende Stunde, Zahl darin, größte je gemessene Stunde, Gesamtzahl, Zeitpunkt des letzten Vorfalls. `flock`, weil eine Überlast viele Prozesse gleichzeitig trifft |
| Anzeige | Statusseite, Karte **Server**, Zeile **Verbindungen**. Orange ab zehn Vorfällen in der laufenden Stunde — **oder** wenn die Spitze diese Schwelle erreicht hat und der letzte Vorfall keine 24 Stunden her ist. Ist die Datei nicht schreibbar, sagt die Zeile das: „0 Vorfälle" und „nicht gezählt" sähen sonst gleich aus |
| Persistente Verbindungen | bleiben aus. `PDO::ATTR_PERSISTENT` steht nirgends (gezählt 16.09.2026: 0 Treffer unter `server/` und `tools/`) — eine persistente Verbindung belegt über das Ende der Anfrage hinaus genau den Platz, um den es hier geht |
| Prüfmittel | `tools/proben/verbindung/` — stellt 1226 her, misst über echtes HTTP. 24 Erwartungen |

**Warum der Zähler in einer Datei steht und nicht in `app_state`** (E-P5a-50).
Das Konzept sagt `app_state`. Das geht nicht, und zwar aus dem Grund, der den
Zähler überhaupt erst interessant macht: In dem Augenblick, in dem gezählt
werden müsste, gibt es keine Verbindung zur Datenbank. Es ist derselbe Satz,
der über `wartung.lock` steht. Erwogen und verworfen wurde, den Vorfall in
eine Datei zu schreiben und beim nächsten gelungenen Verbindungsaufbau nach
`app_state` nachzutragen — das hätte den Buchstaben erfüllt und **zwei
Speicher für eine Zahl** gebraucht. Die Datei steht in `.gitignore` **und** in
der Ausnahmeliste des FTPS-Schritts, wie `config.php`, `install.php`
(Nr. 214), `install.lock`, `wartung.lock`, `sicherungen/` und `apk/`.

**Der Riegel gegen die Schleife.** Alles, was unterhalb der 503-Antwort noch
eine Einstellung nachsehen will (`kopfzeilen_lib.php` liest zwei aus
`app_state`), landet über `app_state_lesen()` wieder in `db()` — mit `$pdo`
weiterhin `null`, also mit einem zweiten Verbindungsversuch, der genauso
scheitert. Ohne den statischen Riegel `$inUeberlast` wäre das eine
Endlosschleife bis zum Speicherende, ausgerechnet unter Last. Mit ihm fliegt
die Ausnahme beim zweiten Mal weiter; `app_state_lesen()` fängt sie und nimmt
ihre Vorgabe — genau das, wofür sie gebaut ist. Die Verbindungsprobe misst das
nach: Der Zähler muss **genau so viele** Vorfälle tragen, wie es Abweisungen
gab.

**Auf der Kommandozeile wird gezählt, aber nicht geantwortet.** Ein Job, der
eine HTML-Seite nach stdout schreibt und sich beendet, verschluckt seinen
eigenen Fehler; der Aufrufer soll die Ausnahme sehen. Dieselbe Unterscheidung
trifft `wartung_tor()` eine Ebene höher.

### 5e.1 Gedrängel: 1213 und 1205 (E-P5a-52)

Gefunden von der Verbindungsprobe, nebenbei, und teurer als das, wonach sie
suchte: Zwanzig Uploads desselben Geräts auf denselben Diensttag, gleichzeitig
abgeschickt, ergaben **zwölfmal HTTP 500** — `SQLSTATE[40001] 1213 Deadlock
found when trying to get lock; try restarting transaction`. Alle Uploads eines
Diensttags fassen dieselbe `days`-Zeile an (`dt_zeitraum_fortschreiben()` in
`diensttag_lib.php`), und InnoDB bricht dann eine der beteiligten
Transaktionen ab, um den Kreis zu lösen.

Das ist derselbe Fehler wie 1040/1203/1226, eine Ebene höher: Die Anfrage ist
nicht kaputt, sie ist zu früh — die Meldung von InnoDB sagt es wörtlich. Seit
Web 20.13.0 antworten **1213** (Deadlock) und **1205** (Lock wait timeout)
ebenfalls mit 503 `ausgelastet`, an zwei Stellen: `json_fehler()` in `db.php`
und der eigene Fangblock von `ingest.php`, das seine 500 selbst ausgibt.

Der Vorfall steht mit **Datei und Zeile** im Fehlerprotokoll des Webspace
und **nicht** im Reiter System (`gedraengel_vermerken()`, ein Rückfall nach
4.99g): Die Anfrage hängt gerade an einer Sperre, und ein weiterer
Schreibzugriff ist das Letzte, was sie braucht. Ein Gedrängel ist auch nichts,
wonach jemand am Telefon fragt — was zählt, ist die Stelle, und die findet man
durch Zählen gleicher Zeilen. Die Kennung, die jede Rückfallzeile seit
Web 20.40.0 trägt, schadet nicht; nach ihr fragt nur niemand. Und er zählt
**nicht** in `ueberlast.json`: Der Zähler beantwortet die Frage „steht
`max_user_connections` zu eng?", und ein Gedrängel um eine Tabellenzeile
beantwortet sie nicht.

**Die eigentliche Abhilfe steht aus** — die Transaktion zu wiederholen, statt
sie dem Aufrufer zurückzugeben. Backlog Nr. 210.

## 6. Deployment — die Auslieferungskette (ab Web 20.4.0, P5a/AP1)

**Bis Web 20.3.0** gab es genau einen Weg: Push auf `main` mit Änderungen unter
`server/` → GitHub Action → FTPS auf **Produktiv**. Keine Zwischenstufe, kein
Prüftor, kein Freigabeschritt, kein Rollback-Weg — und kein Backup, von dem
jemand wüsste, dass es zu diesem Stand gehört. Das beschreibt R67 und beendet
E-P5a-10.

### 6.1 Zwei Wege, drei Tore

| Auslöser | Ziel | Umgebung | Davor |
|---|---|---|---|
| Push auf `main` | **Staging** | `staging` | Stufe 1 |
| **Handlauf auf `hotfix/*`** (ab AP7) | **Staging** | `staging` | Stufe 1 |
| Tag `web-vX.Y.Z` | **Produktiv** | `produktion` | Stufe 1, Stufe 2, Pflichtfreigabe, Backup-Tor, **Abstammung** |

Vier Arbeitsläufe unter `.github/workflows/`:

| Datei | Was |
|---|---|
| `pruefung.yml` | **Stufe 1** — jeder Pull Request, dazu jeder Push auf `main` (seit 21.09.2026; vorher jeder Push auf jedem Zweig); seit PK-05 die Gegenlesung des Prüfberichts (6.2) |
| `auslieferung.yml` | **wann**: Jobs `staging`, `stufe2`, `produktion`, `Rückfallstand (Staging)` und `zeiger` |
| `ausliefern-lauf.yml` | **was**: die Schrittfolge, einmal, für beide Umgebungen |
| `integritaet.yml` | die Wache; läuft nach einem **Produktiv**-Deploy und täglich |

**Die Trennung ist seit Kette II/AP5 (21.09.2026) und hat einen Grund.**
Bis dahin standen die Schritte zweimal in `auslieferung.yml`: vier im Job
`staging`, dreizehn im Job `produktion`. Staging hatte weder Zielprobe noch
Backup-Tor, weder Wartungsmodus noch Migrationsabfrage — **also lief auf
Produktiv jedes Mal etwas, was vorher nirgends gelaufen war.** Genau das
verbietet E-KH-17, und genau deshalb steht die Folge jetzt einmal:
`auslieferung.yml` ruft sie zweimal auf und reicht `umgebung` durch.

**Was die Umgebung noch trennt, ist klein und begründet sich selbst:** zwei
Schritte (der Tag-Vergleich — Staging fährt von `main` und hat keinen Tag;
und das Tor der grünen Läufe — es fragt, ob dieser Stand auf *Staging* grün
war, und müsste auf Staging nach sich selbst fragen) und drei Werte
(Basisadresse, Zielpfad, Pfad der Zustandsdatei), die der **erste** Schritt
des gemeinsamen Laufs bestimmt. Alles Übrige ist gleich.

**Der Anzeigename eines Jobs wird zusammengesetzt** — `staging / ausliefern`
und `produktion / ausliefern`. Die **Schritt**namen bleiben, wie sie waren.

**Die Reihenfolge ist seit AP6 eine Regel und keine Gewohnheit** (E-KH-06):
*Alles, was scheitern kann, ohne den Server zu verändern, steht vor dem
Wartungsschalter; unmittelbar danach folgt der Abgleich.* Vorher lagen die
`doku`-Kopie, der Gesprächslauf-Riegel und das Bereitstellen der
Zustandsdatei **hinter** „Wartung an" — scheiterte einer davon, stand die
Anlage zu, ohne dass auch nur eine Datei ausgeliefert worden wäre.

Dazu drei Schritte, die es vorher nicht gab:

| Schritt | Wofür |
|---|---|
| **Adressvergleich** | `PRODUKTION_URL` gegen `WACHE_BASIS`. Gehen sie auseinander, liefert die Kette nach A aus und die Wache bewacht B — **beide Seiten sind für sich grün**, und der Produktivserver bliebe unbeobachtet |
| **Versionsprüfung nach dem Abgleich** | Der FTPS-Schritt meldet Erfolg, wenn die Übertragung gelungen ist — nicht, wenn die Anwendung danach die neue Fassung ausliefert. Stimmt sie nicht: rot, **Wartung bleibt an** |
| **Schlussschritt** (`if: failure()`) | Fragt die Anlage nach Wartungsmodus und Fassung und schreibt beides samt „Dateistand unbekannt" und den zwei Bedienwegen in die Zusammenfassung. Antwortet sie nicht, steht dort **`unbekannt`** — nie „aus" |

**Zwei `concurrency`-Gruppen, je eine Umgebung, ohne Abbruch** (E-KH-11). Ein
abgebrochener Produktivlauf ließe die Wartung an und einen halben Dateistand
oben; ein abgebrochener Staging-Abgleich ist nicht harmloser, weil die
Zustandsdatei der Aktion dann einen Server beschreibt, den es so nicht gibt.
**Bekannte Folge, benannt statt verschwiegen:** Je Gruppe wartet höchstens
ein Lauf; ein dritter verdrängt den zweiten wartenden. Für Staging ist das
hinnehmbar — der jüngste Stand gewinnt, und genau den will man.

**Alle zwölf fremden `uses:`-Zeilen hängen an einer 40-stelligen Commit-SHA**
(E-KH-10), die Version steht als Kommentar daneben. **Die Zahl ist eine
Orientierung, kein Prüfwert** — nachgezählt wird „keine fremde Zeile ohne
SHA", und dafür stehen zwei Zählungen nebeneinander (`CLAUDE.md` 3). Die beiden **lokalen**
(`./.github/workflows/ausliefern-lauf.yml`) tragen keine und können es
nicht: Ein lokaler Pfad nimmt keinen Ref und läuft immer auf dem Commit des
Aufrufers — das ist strenger als ein Pin, nicht schwächer.

**Das Tor der grünen Läufe fragt nach dem Job, nicht nach dem Lauf.** Ein
übersprungener Job macht den Lauf nicht rot; bis AP6 zählte das Tor also,
dass ein Lauf stattgefunden hat, und nicht, dass ausgeliefert wurde.

**Seit AP7 entscheidet es nicht mehr selbst** (E-KH-15). Der Schritt holt nur
noch die Tatsachen — welche Läufe es gab, wie sie ausgingen, ob der Commit
vom Zeiger `produktion` abstammt —, und das Urteil fällt
`tools/kette/freigabe.py`. Der Grund ist derselbe wie beim Backup-Tor
(E-P5a-12): Eine Bedingung, die eine Auslieferung verhindern soll, gehört
dorthin, wo eine `--selbstprobe` sie nachweisen kann. Als Bash im
Arbeitslauf wäre die Gegenprobe — *ein Hotfix ohne Abstammung muss abgelehnt
werden* — nur durch einen echten Produktivlauf zu belegen, also praktisch
nie. Jetzt ist sie eine von **52 Lagen** in Stufe 1.

**Stufe 1 zählt es seit dem 23.09.2026 nach Baum, nicht nach Commit**
(Konzept TB). Ein PR-Lauf trägt die SHA des Zweigs, nie die des
Merge-Commits; nach der SHA gefragt, wartete das Tor auf den Push-Lauf auf
`main`, der denselben Inhalt noch einmal maß (Lauf 35834341392, Tag
`web-v20.37.1`). Jetzt holt der Schritt die jüngsten 50 grünen
`pruefung.yml`-Läufe, je Lauf den Baum seines Commits und bei passendem Baum
die Jobs — anerkannt wird ein Lauf, dessen Baum der des Tag-Commits ist
**und** in dem der Job `Stufe 1` selbst grün war. Den Verweis-Lauf auf
`main`, der `Stufe 1` auslässt, zählt es damit nicht. Beweisbar gleich ist
der Baum, weil das Ruleset „up to date" verlangt; liegt der grüne Lauf
außerhalb der 50, hilft ein Handlauf von `pruefung.yml` auf dem Stand.

**Was anerkannt wird, und was nicht:**

| Lage | zählt |
|---|---|
| Push auf `main`, Job `staging` grün | **ja** |
| Handlauf auf `hotfix/*`, Job `staging` grün, Commit stammt vom Zeiger ab | **ja** (E-KH-15) |
| derselbe Hotfix **ohne** Abstammung | nein |
| Abstammung nicht zu ermitteln (Zweig `produktion` fehlt, Abruf scheitert) | nein — **im Zweifel zu** |
| Handlauf auf `main` | nein (E-KH-29) |
| Push auf einen Arbeitszweig | nein |
| irgendetwas davon mit übersprungenem oder rotem `staging`-Job | nein |

**Warum die Abstammung an die Stelle des Zweigschutzes tritt:** Ein
`hotfix/*`-Zweig kommt per **Handlauf** auf Staging, nicht per Push — und
ein Handlauf umginge den Zweigschutz von `main`, auf dem die alte Regel
ruht. E-KH-15 setzt dafür die Abstammung vom Zeiger `produktion` ein, also
von genau dem Stand, der heute ausgeliefert ist. Wer einen Hotfix baut,
zweigt vom Ausgelieferten ab; wer etwas anderes unterschieben will, kann das
nicht, ohne die Abstammung zu verlieren. Verglichen wird über
`compare/produktion...<sha>`; `ahead` und `identical` belegen die
Abstammung, `behind` und `diverged` nicht.

**Ein Handlauf auf `main` zählt ausdrücklich nicht** (E-KH-29). Wörtlich
gelesen ließe E-KH-15 ihn zu — die Entscheidung setzt aber den Zweigschutz
von `main` voraus, und der ist laut Zuarbeit Z4 **noch nicht gesetzt**. Bis
dahin wäre ein Handlauf auf `main` genau der Weg, den E-KH-15 versperren
will. Für `main` ist der Push ohnehin der normale Weg.

**Der Jobname wird auf Gleichheit geprüft, nicht auf den Anfang** (AP7). Bis
dahin stand dort `startswith("staging")`, und das war eine Falle mit
Zeitzünder: AP7 bringt mit `Rückfallstand (Staging)` einen zweiten Job in
dieselbe Datei, der etwas mit Staging tut. Hieße er `staging-sicherung`,
zählte ein **Tag**-Lauf — in dem `staging` übersprungen ist — sich selbst
als Nachweis „stand auf Staging". Beide Riegel stehen: der Name **und** der
Vergleich.

> **Die Pflichtfreigabe wandert mit — nachgemessen.** Sie hängt am
> `environment:`, und das liegt seit AP5 im aufgerufenen Lauf. Beides ist
> belegt: dass die Umgebung dort **bindet** (F-KH-U-31) und dass die
> **Freigabepflicht** mitwandert — **Lauf 35566000648 vom 21.09.2026 hat
> die Freigabe angefordert und gestanden, bis sie erteilt war**
> (F-KH-U-34). Ein Ausdruck, der ins Leere zeigte, ließe den Job ohne
> Umgebung und damit ohne Freigabe laufen; **wer diese Zeile ändert, misst
> es neu** — ein Probelauf gegen `produktion` kostet einen Klick und
> liefert nichts aus.

**Der Tag ist die Fassung** (F-P5a-1, entschieden 15.09.2026): `web-vX.Y.Z`,
gleich `WEB_VERSION` in `server/version.php`. Der Produktionslauf verweigert,
wenn beides nicht übereinstimmt. Uhr und Android bekommen keine
Auslieferungs-Tags — deren Signatur liegt außerhalb der CI (E-S4-16).
**Rollback ist ein Lauf mit dem vorigen Tag.**

### 6.2 Stufe 1 — was ohne Installation messbar ist

`pruefung.yml`, **bei jedem Pull Request und bei jedem Push auf `main`**.
Ein Push auf einen Arbeitszweig löst seit dem 21.09.2026 keinen Lauf mehr
aus (Vorgriff auf PK-05): Vorher erzeugte jeder Push **zwei** Läufe namens
`Stufe 1`, und der Zweigschutz wartete auf den langsameren — 56 statt einer
Minute (PR #69, #70).

**Seit PK-05 ist Stufe 1 die Gegenlesung des Prüfstands** (Station C,
`docs/Pruefablauf.md` 2.3 und 5). Sie wiederholt nicht, was der Prüfstand
gemessen hat, sondern fährt die billigen Riegel und hält deren Zahlen dem
Prüfbericht in der Nachricht des PR-Kopfs entgegen. Android und Uhr baut sie
**nicht mehr**: Das tut der Prüfstand, wenn `android/` oder `watch/` berührt
ist, und der Bericht sagt es — sagt er „nicht berührt", obwohl der PR dort
etwas ändert, ist das Tor rot. Bis PK-05 baute Stufe 1 beides selbst, mit
eigener Bereichserkennung, eigenem JDK und bis zu 42 Minuten Laufzeit; die
Datei hatte 988 Zeilen, heute 337.

Die Schritte des Jobs `Stufe 1`, in dieser Reihenfolge. **Die Zahlen stehen
nicht hier**, sondern in der `LIESMICH.md` des Werkzeugs
(`docs/Pruefablauf.md` 6.11) — dieser Abschnitt trug bis Web 20.21.1 eine
Bilderzahl, die längst nicht mehr stimmte.

| Schritt | Grün heißt |
|---|---|
| Fassungen nennen (Web, Uhr, Android) | eine Auskunft — aber eine unlesbare Fassung ist rot |
| `php -l` über `server/` und `tools/` | 0 Fehler, und mindestens eine Datei gelesen; die Zahl der versionierten `server/`-Dateien geht in die Gegenlesung (`syntax-php`) |
| `cmark-gfm` bereitstellen | für die Quelltextprüfungen `handbuch` und `bestand` (seit BR-05: `bestand` liest die Tabelle der Quelltextprüfungen damit) |
| `tools/quelltext/pruefen.sh --selbstprobe`, dann `alle` | alle Selbstproben und alle dreizehn Prüfungen grün (`tools/quelltext/LIESMICH.md`) — darunter seit BR-03 Backlog-Nummern (`bestand`), Python übersetzen (`pysyntax`) und Handbuch rendern (`handbuch`), bis dahin drei eigene Schritte |
| `tools/screenshots/kontrast.py` | 0 Befunde |
| Umgebungswert eine Ebene höher | alle sechs Namen leer (6.5, E-KH-28) |
| `tools/kettenaufrufe/pruefen.py --probe`, dann ohne Schalter | Selbstprobe vollständig, 0 Befunde; jeder ungeprüfte Aufruf benannt |
| `tools/kette/tor.py`, `download_lib.mjs`, `tools/kette/freigabe.py`, `tools/kette/baumsuche.py`, je `--selbstprobe` | jede Lage erfüllt |
| `tools/spaltenregister/pruefen.php`, `tools/zaehlung/zaehlen.php`, je mit Selbstprobe | 0 Befunde, 0 Zeilen über der Decke |
| `tools/proben/proben.sh rechtstexte` | 0 Fehlschläge |
| **Prüfbericht gegenlesen** (überall außer auf `main`) | `bericht.py lesen` rc 0: Baum, Stufe, Flächen, alle Riegel, keine rote und keine nicht gemessene Probe |

Daneben, als eigener Job mit Matrix: **`Schema gegen …`** —
`tools/schemaprobe/probe.php` gegen MySQL 8.4.0 und MariaDB 10.6, je mit
Selbstprobe. **Er wird Pflichtprüfung** (E-PK-47): Die BetreiberIn trägt
„Schema gegen MySQL 8.4.0" und „Schema gegen MariaDB 10.6" ins Ruleset ein
— bis dahin hält ein roter Schemalauf einen Merge nicht auf.

> **Die Gegenlesung ist streng** (E-PK-42): Der Baum im Bericht muss der des
> PR-Kopfs sein. Nach einem fremden Merge ist „Update branch" deshalb der
> falsche Knopf — sein Merge-Commit trägt keinen Bericht. Der Weg steht in
> `docs/Pruefablauf.md` 5.3. Auf `main` liest niemand gegen: Dort misst der
> Lauf entweder selbst oder verweist über `Schon gemessen?` auf den grünen
> PR-Lauf mit demselben Baum. **Ein Handlauf auf einem Zweig liest gegen**
> (E-PK-43): Sonst setzte ein Handlauf auf dem PR-Kopf ein grünes
> `Stufe 1` ohne Gegenlesung — der Hotfix-Weg (6.6b) braucht deshalb einen
> Bericht im Hotfix-Commit.

> **`tools/quelltext/` `jobregister` hängt seit Kette II in Stufe 1** — seit
> PK-05 als Teil von `pruefen.sh alle`. Schritt 16 hatte es gebaut, weil die
> erzeugte Beschreibung im Katalog sonst altert (Backlog Nr. 208, „sonst
> wandert das Problem nur eine Ebene weiter"). Es wird bei jedem PR
> nachgezählt statt nur, wenn es jemand fährt.

> **`tools/spaltenregister/` war zwei Tage lang rot, und das gehört
> hierher**, weil es zeigt, wofür die Selbstprobe da ist. Zwei Befunde, beide
> aus Schritt 15 selbst: `start_sort` stand in der Abbildung von
> `api/suchindex.php`, aber nicht im Register, und der Selbstprobefall
> „mf_spalten: Alias an" erwartete `uhr_gesperrt AS manual` **ohne
> Backticks**, während der Merge den Fix aus Web 20.26.3 nach `mf_spalten()`
> gezogen hatte. Behoben von Schritt 15 selbst (`f1bc9e6`); Backlog Nr. 282,
> erledigt.

> **`tools/kettenaufrufe/` ist das einzige Prüfmittel, das die KETTE prüft**
> und nicht die Anwendung. Es liest jeden `run:`-Block der Arbeitsläufe und
> die Aufrufe in `tools/pruefstand/pruefablauf.json`, findet die darin
> aufgerufenen Werkzeuge und hält jeden Schalter gegen die Schnittstelle, die
> im Quelltext des Werkzeugs steht — ohne eines auszuführen. Grund: Drei
> Kettenschritte sind am 16./17.09.2026 beim jeweils **ersten** echten Lauf
> gescheitert, alle drei mit gültigem YAML (Nr. 217). Seine Grenze steht in
> seiner `LIESMICH.md` und gehört dazu: Es prüft Schnittstellen, nicht
> Verhalten.

**Rot heißt kein Merge** — das entscheidet aber nicht die Datei, sondern der
Zweigschutz auf `main`. **Seine Pflichtprüfung heißt `Stufe 1`**, nach dem
Namen des **Jobs**, nicht nach dem des Arbeitslaufs („Prüfung") und nicht
nach dem Dateinamen; wer sie anders einträgt, hängt sie an nichts
(Rahmenplan 6b). Er ist seit dem **21.09.2026** gesetzt — bis dahin war der
Lauf eine Auskunft und keine Schranke. Denselben Namen sucht
`tools/kette/baumsuche.py` (`JOB`) — für das Produktionstor in
`ausliefern-lauf.yml`, das die Liste an `tools/kette/freigabe.py` gibt, und
für den Job `Schon gemessen?`; seit BR-03 eine Suche statt zweier (E-BR-09).
Ein umbenannter Job hängt alle drei still ab.

### 6.3 Stufe 2 — was eine Installation braucht

Job `stufe2` in `auslieferung.yml`, nach dem Staging-Sync, **drei Schritte**
(seit dem 21.09.2026, Konzept PK, E-PK-01/E-PK-17): der Griff auf
`login.php`, die Punktdatei-Sperre und der Kreislauf edbak (`--frisch`, mit
Job-Pause, 0 unerklärt). Das ist, was nur die echte Anlage zeigt —
Hoster-PHP, Hoster-Datenbank, Hoster-Apache. Nr. 267 fiel genau dort: Der
Alias `AS manual` war auf MySQL 8.4.10 reserviert, und die Sandbox hatte bis
dahin nur MariaDB gesehen. **Der Kreislauf** braucht ein **Prüfkonto auf
Staging** (Umgebungsgeheimnisse `STAGING_KONTO`, `STAGING_PASS`, Variable
`STAGING_URL`); fehlt es, ist der Lauf rot und sagt warum. Zeitgrenze des
Jobs 20 Minuten; gemessen sind 1:56 für alles zusammen (Lauf 35639445224,
Versuch 2, damals noch mit dem csv-Kreislauf).

**Seit Web 20.45.0 ein vierter Schritt: die Rückwegprobe gegen Staging**
(Konzept RW, E-RW-11, -12; `tools/proben/rueckweg/probe.mjs` mit der Adresse
von Staging und dem Zugang des Prüfkontos samt Geheimnis). Ob phpseclib beim Hoster über `openssl` oder
in reinem PHP prüft und ob der Weg durchgeht, zeigt nur der Hoster. Die Probe
legt zwei Wegwerfkonten `umlauf-rueckweg…` an (NutzerIn und BetreiberIn,
über `pruefkonto.py`), schaltet deren Zweitfaktor ein, setzt ihn mit dem
Wiederherstellungsschlüssel zurück und löscht die Konten wieder. Sie läuft
**nach** dem Kreislauf und benutzt dessen Installationen (Playwright,
Chromium, `cryptography`); fehlt `STAGING_TOTP` oder das Prüfkonto, ist der
Schritt rot. **Gemessen ist er erst nach dem Merge** (P-RW-02).

**Bis zum 21.09.2026 liefen hier auch der csv-Kreislauf und der Bilderlauf**
(62 Seiten in acht Breiten). Beide messen die Anwendung, nicht die Anlage,
und laufen seither in der Sandbox (Konzept PK). Der Bilderlauf war auf der
neuen Staging-Anlage nie grün: Er meldet sich für 32 Seiten als
`demo@gen-em.org` mit dem Vorgabekennwort an, und dieses Konto gab es dort
nicht. Weil das Tor der grünen Läufe einen **als Ganzes** grünen
Staging-Lauf verlangt, kam damit kein Tag durch — der erste Versuch mit
`web-v20.26.3` (Lauf 35646453443) scheiterte genau daran, ohne Produktiv zu
berühren (Nr. 268). Was der Bilderlauf auf Staging noch brauchte
(`JOBS_TOKEN` für die zwei Wartungsseiten, Nr. 220; der Bericht mit der
Spalte `Verursacher` in der Zusammenfassung, Nr. 221), gilt für seinen Lauf
in der Sandbox weiter.

**Die Läufe fahren je Umgebung in Reihe** (`concurrency`, seit dem
21.09.2026). Zwei Merges innerhalb einer Minute erzeugten zwei Staging-Läufe
zugleich: Der erste hielt für seinen Kreislauf die Hintergrundjobs 1800 s an,
der zweite wartete am Backup-Tor vierzig Aufrufe auf ein Komplett-Backup, das
„angehalten bis 19:06:50" meldete, und schloss nach dreizehn Minuten. Ein
jüngerer Lauf **wartet** jetzt, statt den älteren abzubrechen: Ein Abbruch
mitten im Abgleich hinterließe einen halben Stand bei eingeschalteter
Wartung, ein Abbruch mitten im Kreislauf die Jobpause für bis zu 30 Minuten.
GitHub hält je Gruppe einen wartenden Lauf; kommt ein dritter, fällt der
mittlere weg, bevor er die Anlage berührt hat — sein Commit steht dann nie
auf Staging, und der Tag gehört auf den nächsten. Tag-Läufe haben ihre
eigene Gruppe und warten nie hinter einem Merge; ein Tag **während** eines
laufenden Staging-Laufs kann mit `Rückfallstand (Staging)` auf dessen
Jobpause treffen und wird dann nach dessen Ende neu gestartet.

**Der Messstand läuft hier nicht, und bei einem Tag-Lauf läuft Stufe 2
überhaupt nicht.** Bis Web 20.16.4 stand an dieser Stelle „und **nur bei
Tag-Läufen** der Messstand" — ein Satz, der zwei Dinge zugleich behauptete,
die beide nicht zutreffen. Der Messstand-Schritt ist in P5a/AP9 **ersatzlos
aus der Kette gestrichen** (Nr. 206); seine Zahlen stehen in
`tools/messstand/ausgangsmessung.md`. Und `staging` ist für Tag-Läufe
abgeschaltet (`if: !startsWith(github.ref, 'refs/tags/')`), `stufe2` hängt
mit `needs: staging` daran — ein Tag lässt **allein** `produktion` laufen.
Der Stand, den ein Tag ausliefert, hat Stufe 2 deshalb schon vorher gesehen,
und genau das prüft das Tor „Grüner Staging- und Stufe-1-Lauf auf diesem
Stand?" nach — **am ganzen Lauf**, nicht nur am Job `staging`.

**Der Kreislauf braucht seit Web 20.16.0 zusätzlich `JOBS_TOKEN`** in der
Umgebung `staging` (Backlog Nr. 219). Er hält die Hintergrundjobs an, bevor
er ein Backup in ein frisches Konto spielt — sonst dünnt der
Verdichtungsjob die wiederhergestellten Spuren aus, und der Vergleich misst
„hat der Job dazwischen zugeschlagen" statt „kommt zurück, was hineinging"
(gemessen: 125 verdichtete Spuren in einem Lauf ohne Pause). Das ging bis
dahin nur über die Kommandozeile und damit nur auf dem Rechner der
Installation; hier läuft ein Läufer gegen ein fernes Staging. **Fehlt das
Token, ist der Lauf rot und sagt es** — nicht still auf den lokalen Weg
zurückgefallen, der hier ohnehin an der fehlenden `config.php` scheitert.

**Der erste Schritt unterscheidet seit Nr. 214 zwei Fälle.** Landet der
Aufruf auf `install.php`, fragt er diese Datei zusätzlich ab: Kommt **404**,
liegt es nicht am `FTP_ZIELPFAD`, sondern daran, dass `install.php` seither
in der Ausnahmeliste steht und bewusst nicht ausgeliefert wird — die Meldung
sagt dann, die Datei einmal von Hand hochzuladen. Ohne diese Unterscheidung
suchte man den Fehler im falschen Ort.

**Dazu seit Web 20.15.1 der Schritt „Punktdateien gesperrt,
.well-known offen?"** (Nr. 213). Er braucht **kein** Prüfkonto — nur
`STAGING_URL` — und misst die `.htaccess`-Sperre aus Abschnitt 4.97g in
beide Richtungen:

| Pfad | erwartet | wofür |
|---|---|---|
| `/.ftp-deploy-sync-state.json` | **403** | die Zustandsdatei der Kette (der Anlass) |
| `/.deploy-state-staging.json` | **403** | auch der neue Name, falls `../` scheitert |
| `/.env`, `/.git/config` | **403** | die Gattung, nicht nur der Name |
| `/.well-known/acme-challenge/kettenpruefung` | **404, NICHT 403** | die Zertifikatserneuerung |

Die letzte Zeile ist die wichtigere. Antwortet `.well-known/` mit 403, ist die
ACME-Herausforderung tot und das Zertifikat läuft in bis zu 90 Tagen ab —
lautlos. Dieser Schritt ist das Einzige, was zwischen einer zu breiten Sperre
und einer abgelaufenen Anlage steht.

Und er beweist überhaupt etwas, weil `RewriteRule [F]` **403 antwortet, ob die
Datei da ist oder nicht**: mod_rewrite läuft vor der Dateisuche. Ein 404
käme auch von einer leeren Adresse — genau so wurde der auslösende Befund
zuerst falsch entlastet.

### 6.3a Staging und Produktiv sind zwei Plattformen (ab 20.09.2026, E-KH-04)

**Bis zum 19.09.2026 lagen sie im selben Webspace.** `staging.nadoku.gen-em.org`
war eine Subdomain desselben Plesk-Abonnements wie Produktiv, mit eigener
Datenbank und eigenem FTPS-Konto — aber **unter demselben Systemnutzer**. Das
war der Fehler: Staging-PHP konnte Produktivs `config.php` lesen, und damit
reichten die Staging-Zugangsdaten faktisch bis Produktiv — an der
Pflichtfreigabe und am Backup-Tor vorbei. Wer Staging kompromittiert, hätte
den Serverschlüssel, den Server-Anteil am Datenschlüssel und den DB-Zugang
von Produktiv gehabt. Eine Prüfumgebung, deren Zugangsdaten die
Produktionsumgebung öffnen, ist keine.

**Seit dem 20.09.2026 liegt Staging bei lima-city**
(`staging-nadoku.gen-em.org`), also bei einem **anderen Hoster**; die alte
Anlage ist am selben Tag stillgelegt worden. Die Einrichtung steht in
`docs/Rahmenplan.md`, Abschnitt 6a.

**Der Preis ist eine Zusage, die es nicht mehr gibt.** E-PP-09 hatte Staging
ausdrücklich „beim selben Hoster im selben Tarif" festgelegt, und zwar mit
dieser Begründung: *Nur dann misst der Messstand die Grenzen, die Produktiv
wirklich hat.* Diese Eigenschaft ist fort. **Was Stufe 2 auf Staging misst,
gilt für Staging.** Zeitgrenzen, Speichergrenzen, `max_user_connections`,
Plattenplatz, die Antwortzeiten des Messstands — keine dieser Zahlen ist auf
Produktiv übertragbar, und keine darf so zitiert werden. Wer aus einem grünen
Stufe-2-Lauf liest, dass eine Abfrage auf Produktiv innerhalb der Zeitgrenze
bleibt, liest etwas, das dort nicht steht.

**Was an die Stelle tritt** (Konzept Kette II): der **Probelauf gegen
Produktiv** (E-KH-08) — eine Handauslösung mit Pflichtfreigabe, die
Geheimnisse, Adressvergleich, Zielprobe und einen Trockenlauf des Transports
fährt und **nichts ausliefert**; und dieser Abschnitt. Der Leitsatz dahinter
ist E-KH-17: **Die Logik probt Staging bei jedem Push, die Plattform probt
der Probelauf.**

**Und ein Gewinn, den die alte Anordnung nicht hatte.** R81 sagt, die
Anwendung sei nicht auf einen Hoster zugeschnitten. Solange Staging und
Produktiv derselbe Hoster waren, war das eine Behauptung, die nichts prüfte.
Seither läuft die Anwendung mit **jedem Push auf `main`** auf einer zweiten,
anders konfigurierten Plattform los — und ein Zuschnitt, der sich
eingeschlichen hat, fällt dort auf, bevor ein Selbsthoster ihn findet.

#### Der Plattformvergleich

Beide Anlagen melden ihre Plattform selbst: **Betrieb → Status**, gespeist aus
`plattform_pruefen()` (Abschnitt 5b). Die Auskunft gehört hierher
nebeneinander, damit ein Unterschied sichtbar ist, bevor er eine Messung
erklärt.

| Prüfpunkt (5b.2) | Produktiv (Plesk) | Staging (lima-city) |
|---|---|---|
| PHP-Fassung | **8.3.33** | **8.3.33** — dieselbe |
| Server-API | ⬚ Z3 | **FPM/FastCGI**, Apache 2.4 davor |
| PHP-Erweiterungen | alle fünf vorhanden | alle fünf vorhanden |
| `memory_limit` | **512 MB** | **512 MB** — gleich |
| `max_execution_time` | **240 s** | **300 s** |
| `post_max_size` / `upload_max_filesize` | **256 MB / 256 MB** | **500 MB / 500 MB** |
| OPcache | **aus** | **an** — aber nur **Dateicache** (`file_cache_only`), SHM und JIT aus. **Die Statusseite wird ihn trotzdem als „aus" melden** — siehe Kasten |
| Datenbank | **MariaDB 10.11.14** | **MySQL 8.4.10** (Statusseite, 21.09.2026) — **eine andere Datenbank als Produktiv**; genau daran ist der Export bis Web 20.26.3 gescheitert (Nr. 267) |
| `max_user_connections` | **nicht gesetzt**; es gilt `max_connections` = **151** | ⬚ |
| Kontingent der Datenbank | Angabe 10 GB, belegt 9,8 MB (0 %) | ⬚ |
| Freier Platz | **861,7 GB gemeldet** — Datenträger des Hosts, nicht das Kontingent | ⬚ |
| Cron | **nein** — alle elf Jobs laufen **huckepack** („anfrage") | ⬚ |
| FTPS | **ja**, belegt mit WinSCP | **ja**; Konto auf `/` eingesperrt |
| HTTPS | **ja** | **ja** — Apache 2.4, Port 443 |
| Herkunft des Zertifikats | **Let's Encrypt**, automatisch erneuert | ⬚ |
| Anwendungswurzel | eigenes Verzeichnis unter einem Plesk-Vhost (`…/nadoku-produktion`) | eigenes Verzeichnis im lima-city-Webspace (`…/nadoku-staging`) |
| Zeitzone | ⬚ Z3 | `Europe/Berlin` |

**Die beiden Spalten sind auf verschiedenen Wegen erhoben, und das ändert,
was sie belegen.** Produktiv ist am 20.09.2026 aus **Betrieb → Status** und
**Betrieb → Hintergrundjobs** abgelesen — also aus `plattform_pruefen()`.
Staging ist am selben Tag aus einer **`phpinfo()`-Ausgabe** erhoben, weil die
Anwendung dort noch nicht installiert ist (Rahmenplan 6a, Schritt 6 steht
aus). **`phpinfo()` zeigt die PHP-Einstellung, die Statusseite zeigt, was die
Anwendung daraus macht** — und der OPcache unten beweist, dass das nicht
dasselbe ist. Die Staging-Spalte ist deshalb **vorläufig** und wird ersetzt,
sobald die Statusseite dort antwortet. Alles, was nur die Anwendung weiß
(Datenbank, Platz, Kontingent, Jobwege), steht bis dahin auf `⬚`.

> **Der OPcache meldet auf lima-city das Gegenteil dessen, was läuft.**
> `plattform_pruefen()` fragt zuerst `function_exists('opcache_get_status')`
> und dann die Funktion selbst. Auf Staging steht
> **`disable_functions = dl, syslog, opcache_get_status`** — und
> `function_exists()` antwortet für eine so abgeschaltete Funktion **`false`**.
> Die Statusseite wird dort also **„OPcache: aus"** zeigen, während die
> phpinfo **„Opcode Caching: Up and Running"** meldet.
>
> **Der Schaden ist klein, der Fehler ist grundsätzlich.** Klein, weil OPcache
> nur *Empfohlen* ist und keine Ampel färbt, und weil
> `opcache_invalidate()` — das die Anwendung nach jedem Schreiben in
> `config.php` ruft (`serverkrypto_lib.php`) — **nicht** abgeschaltet ist und
> weiter wirkt. Grundsätzlich, weil Abschnitt 5b.1 genau das verbietet:
> *„`ok` ist dreiwertig … **`null` nicht feststellbar**. Wer nichts gemessen
> hat, darf nichts behaupten."* Hier hat die Anwendung nichts gemessen und
> behauptet „aus".
>
> **Und es ist der erste Ertrag des Hosterwechsels.** E-KH-04 versprach, die
> Portabilitätszusage aus R81 werde von nun an wirklich geprobt statt nur
> behauptet. Das hier ist der Beleg: ein Zuschnitt auf den einen Hoster, der
> sechs Tage lang niemandem auffiel, weil beide Anlagen derselbe Hoster
> waren. **Behoben wird er nicht hier** — das wäre Servercode und gehört
> nicht in ein Dokumentationspaket; der Vorschlag steht im Prüfdokument.

> **Zwei Zahlen der Produktiv-Spalte sind keine Messung, und das muss
> dabeistehen.** **Freier Platz** meldet auf geteiltem Webspace den
> Datenträger des **Hosts**, nicht das Kontingent dieses Kontos — 861,7 GB ist
> eine Untergrenze für schlechte Nachrichten und keine Entwarnung. **Das
> Kontingent der Datenbank** ist überhaupt keine Messung, sondern eine
> **Angabe** (Vorgabe 10 GB, Z2, einstellbar unter *Betrieb →
> Servereinstellungen*); kein Hoster macht sie abfragbar. Wer eine dieser
> beiden Zahlen zitiert, zitiert diesen Kasten mit.

> **Der auffälligste Wert ist eine Nicht-Zahl: Produktiv hat keinen Cron.**
> Alle elf Hintergrundjobs tragen als Weg **„anfrage"** — sie laufen huckepack
> auf einer Seitenanfrage (Abschnitt 4.97a). Das ist ein zulässiger der drei
> Wege und kein Mangel, aber es heißt: **Ohne Besucher läuft nichts.** Der
> Job „GPS-Daten verdichten" stand am 20.09.2026 auf **Rückstand 69**. Für
> die Kette ist das die Zeile, die zählt, sobald AP5 das Backup-Tor auch auf
> Staging fährt: Ein Komplett-Backup, das huckepack abgearbeitet wird,
> braucht Anfragen — und ein Läufer, der auf `tor.py` wartet, erzeugt sie
> nicht von selbst.

> **Drei Staging-Einstellungen, die keine Tabellenzeile sind, aber in AP5
> zählen werden.** **(1) `default_socket_timeout = 5`** statt der üblichen 60:
> Jede Netzverbindung, die PHP über einen Stream aufbaut — SMTP-Probe,
> Backup-Ziel per SFTP, ein HTTP-Abruf — bricht dort nach fünf Sekunden ab.
> **(2) `session.save_path = /home/webpages/tmp`** liegt **über** dem eigenen
> Verzeichnis — es ist also **geteilt**. **Gemessen am 20.09.2026:** root als
> Eigentümer, Rechte **0773**, für Fremde also `-wx` **ohne Leserecht**; ein
> `scandir()` aus der Anlage heraus scheitert. Andere Kunden können die
> Sitzungsdateien damit **nicht auflisten**, und Sitzungs-IDs sind 128 Bit.
> Der Zuschnitt ist Absicht des Hosters, und er trägt. **Dass die Anwendung
> sich darauf verlässt, ohne es zu prüfen, trägt nicht:** Auf Produktiv ist
> derselbe Wert nicht erhoben, und für Selbsthoster ist er offen. Eine
> Sitzungsdatei führt zwar kein Schlüsselmaterial — ihr **Dateiname ist die
> Sitzungs-ID**, und wer sie auflisten kann, ist angemeldet. Vorschlag im
> Prüfdokument, Abschnitt 4.
> **(3) `open_basedir` ist leer** und `allow_url_fopen` an. Beides ist die
> Voreinstellung vieler Hoster und kein Mangel der Anwendung; es steht hier,
> damit der Vergleich später nicht bei null anfängt.

### 6.4 Das Backup-Tor

Vor dem Schreiben auf Produktiv läuft das Komplett-Backup **nachweislich zu
Ende**. Die Logik steht in `tools/kette/tor.py` und nicht im Arbeitslauf: Eine
Bedingung, die einen Deploy verhindern soll, gehört dorthin, wo eine
Selbstprobe sie nachweisen kann — **fünf Lagen** für das Backup-Tor selbst,
**elf** für die ganze Datei (die übrigen gehören zum Unterbefehl `pause` und
zur Fehlerantwort), alle ohne Netz. Seit Web 20.16.1 läuft sie in **Stufe 1**
und nicht mehr nur hier: Für das Backup-Tor war der Platz unmittelbar vor dem
Tor richtig, aber die `pause`-Lagen bewachen einen Schritt aus Stufe 2 — wer
ihn beschädigte, bekam von Stufe 1 ein Grün.

Der Ablauf des Produktionslaufs:

1. Tag gegen `WEB_VERSION` halten.
2. Prüfen, dass es auf **diesem Commit** einen grünen Stufe-1- **und** einen
   grünen Staging-Lauf gibt.
3. **Backup-Tor:** `jobs.php?aktion=komplett` in einer Schleife (höchstens 40
   Aufrufe, 20 s Pause), bis `fertig` kommt **und** der jüngste
   Komplett-Stand jünger ist als der Laufbeginn. Sonst: **Abbruch ohne
   Deploy.**
4. `jobs.php?aktion=wartung_an` (Urheber `kette`).
5. FTPS-Sync.
6. `jobs.php?aktion=zustand`: Steht eine Migration aus, **bleibt die Wartung
   an** und der Lauf endet grün mit dem Hinweis „Migration ausstehend —
   `update.php` von Hand, dann Wartung aus"; sonst `wartung_aus`.

**Zwei Bedingungen, nicht eine.** `fertig` allein genügt nicht: Ein Backup von
gestern meldet ebenfalls `fertig` und schützt diesen Deploy nicht. Und
`aktion=komplett` **legt einen Auftrag an**, wenn keiner steht — ohne das täte
der Aufruf bei Plan „Nur von Hand" nichts und meldete sofort `fertig`.

### 6.5 Was auf dem Server liegt

**Auf dem Server liegt 1:1 der Repositoriumsstand von `server/`**; ausgenommen
sind `config.php` und `install.lock` (bei der Einrichtung erzeugt),
`wartung.lock` (der Schalter des Wartungsmodus), `ueberlast.json` (der Zähler
der Verbindungsgrenze, P5a/AP9), `install.php` (liegt im Repositorium, wird
aber nicht ausgeliefert — Nr. 214), `sicherungen/` und `apk/`.
Diese Ausnahmeliste ist tragend — ohne sie löscht der nächste Deploy, was nur
dort entsteht. Sie steht **zusätzlich** in `.gitignore` (außer `install.php`);
beides muss so bleiben.

**Seit Kette II/AP5 steht sie EINMAL** (E-KH-20 (1)), im FTPS-Schritt von
`ausliefern-lauf.yml`; **acht Pfade, und die Zahl ist der Prüfwert.** Bis
dahin stand sie zweimal, wortgleich, je einmal für Staging und Produktiv.
Zwei wortgleiche Listen sind keine zwei Riegel, sondern einer und ein
Versprechen: Wer die eine ergänzt und die andere vergisst, schützt eine
Umgebung und die andere nicht — und merkt es erst, wenn eine Datei fehlt,
die es nur auf dem Server gab.

> **Dieser Absatz nannte bis Web 20.25.0 fünf Einträge und die Listen führten
> sieben** — `ueberlast.json` und `install.php` fehlten hier, seit Web 20.15.2
> beziehungsweise P5a/AP9. Nachgetragen in Schritt 16 beim Gegenlesen, nicht
> weil es jemandem aufgefallen wäre. Dieselbe Klasse wie Backlog Nr. 208, nur
> an einer Liste, für die es noch kein Prüfmittel gibt.

> **`.sitzungen/` wird der achte Pfad — und steht noch nicht darin**
> (Schritt 16, E-SA-05). Der Eintrag gehört zu Kette II (E-KH-20, AP5 dort)
> und ist dort angemeldet; Schritt 16 fasst `.github/` nicht an.
>
> **Wogegen er schützt — nachgemessen, weil die naheliegende Begründung
> nicht stimmt.** Der heutige Transport löscht `.sitzungen/` **nicht**:
> `getServerFiles()` listet das Fernverzeichnis nie, sondern liest
> ausschließlich die eigene Zustandsdatei; `HashDiff.getDiffs()` kann
> deshalb nur löschen, was dort steht, und ein zur Laufzeit auf dem Server
> entstandener Ordner stand dort nie. Gelesen in `@samkirkland/ftp-deploy`
> **1.2.3, 1.2.4 und 1.2.5**, in allen drei Fassungen zeichengleich
> (`HashDiff.js` und `deploy.js`, SHA-256 identisch nach Normierung der
> Zeilenenden).
>
> Der Eintrag schützt gegen einen anderen Weg: Sobald `.sitzungen/` **einmal
> im Auscheckstand des Läufers** läge — eine gelöschte `.gitignore`-Zeile
> genügt —, würde er hochgeladen, stünde ab da in der Zustandsdatei, und ab
> da löschte ihn jeder Deploy, bei dem er lokal fehlt. Gegen
> `dangerous-clean-slate` hilft er **nicht**: Das löscht laut Anleitung
> ausdrücklich auch Ausgenommenes.
>
> Der Abnahmepunkt „zwei Deploys hintereinander, die Sitzung überlebt beide"
> bleibt bis zum Merge von Kette II **offen** — er belegt dann die Zusage
> des Eintrags, nicht mehr die Abwehr einer akuten Gefahr.
>
> Die Aktion prüft Datei- und Verzeichnismuster getrennt; jeder Ordner steht
> deshalb **zweimal** (`sicherungen/**` und `sicherungen/`). Für `.sitzungen/`
> gilt dasselbe — zwei Zeilen, in **beiden** Schritten.

Geheimnisse liegen seit Web 20.4.0 nicht mehr als Repositoriums-Secrets herum,
sondern an den **Umgebungen**:

| Ort | Geheimnisse | Variablen |
|---|---|---|
| Umgebung `staging` | `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `STAGING_KONTO`, `STAGING_PASS`, `JOBS_TOKEN` | `FTP_ZIELPFAD`, `FTP_STATE_PFAD`, `STAGING_URL` |
| Umgebung `produktion` | dieselben drei FTP-Angaben plus `JOBS_TOKEN` | `FTP_ZIELPFAD`, `FTP_STATE_PFAD`, `PRODUKTION_URL` |
| Repositorium | `CIQ_GERAETE_URL` — seit PK-05 von keinem Arbeitslauf gelesen; bleibt bis PK-08 (E-PK-48) | `WACHE_BASIS` |

`FTP_SERVER` ist der **nackte Hostname**, ohne Protokoll und ohne Pfad.

> **Der Satz über der Tabelle stimmt heute nur zur Hälfte, und zwar
> gemessen** (Kette II, F-KH-U-32, 21.09.2026). `FTP_SERVER`,
> `FTP_USERNAME` und `FTP_PASSWORD` lösen **auch ohne `environment:`** auf —
> es gibt sie zusätzlich eine Ebene höher, als Repositoriums- oder
> Organisationsgeheimnis. `JOBS_TOKEN` und alle Variablen nicht; bei denen
> ist die Umstellung vollständig.
>
> **Was das kostet:** Der erste Schritt des Auslieferungslaufs prüft, ob die
> drei Werte da sind, und ist genau dafür gebaut, **vor** Backup und Wartung
> anzuschlagen. Fehlt der Umgebungswert, greift still der von oben, und der
> Schritt meldet „Drei Geheimnisse vorhanden" — **ein Tor, das immer
> aufgeht.** Ausgeliefert wird weiterhin richtig (die Umgebung gewinnt gegen
> die Ebene darüber); was fehlt, ist die Warnung.
>
> **BEHOBEN am 21.09.2026** (Prüfpunkt 22, F-KH-U-34). Es waren drei
> *Repository secrets*, zwei Monate alt und damit älter als die Umstellung
> auf Umgebungen — Reste, die beim Umzug liegen geblieben sind;
> Organisationsgeheimnisse gab es keine. Die BetreiberIn hat sie gelöscht,
> die Gegenprobe ist gefahren (Lauf 35566000648: die Zielprobe schreibt,
> holt zurück, vergleicht und löscht — mit den reinen Umgebungswerten).
> **Der erste Schritt kann damit wieder fehlschlagen.**
>
> **Die Regel bleibt und gilt weiter (E-KH-28): Ein Wert, den die Kette aus
> einer Umgebung liest, darf auf keiner Ebene darüber denselben Namen
> haben** — sonst ist jede Prüfung auf sein Vorhandensein eine Prüfung auf
> den falschen Wert. Den maschinellen Nachweis baut AP6 ein.

**`FTP_ZIELPFAD` und `FTP_STATE_PFAD` haben ihre Vorgabewerte mit AP6
verloren** (Kette II, E-KH-07, 21.09.2026). Bis dahin sprang bei fehlender
Variable `./staging/` bzw. `./httpdocs/` ein und
`../.deploy-state-staging.json` bzw. `../.deploy-state-produktion.json` —
eine fehlende Variable führte damit **still in ein fremdes Verzeichnis**,
statt den Lauf anzuhalten. **Jetzt heißt fehlend rot**, und zwar im ersten
Schritt des Auslieferungslaufs: vor dem Auschecken, vor den Geheimnissen,
vor jedem Zugriff auf den Server. Die Meldung nennt den Namen der Variablen,
die Umgebung und den Weg in die Einstellungen.

**Sie gehören damit in beiden Umgebungen ausdrücklich gesetzt** (Zuarbeit
Z4). Am 21.09.2026 war `FTP_STATE_PFAD` auf `staging` **gemessen leer**
(F-KH-U-31, Runde 2); der erste Lauf nach AP6 wird dort rot, bis sie steht.
Dasselbe gilt für `WACHE_BASIS`, das seine Vorgabe an zwei Stellen verloren
hat — in `integritaet.yml` und in `wache.py` selbst.

**Seit AP5 stehen diese Vorgaben an EINER Stelle** — im ersten Schritt des
gemeinsamen Laufs, zusammen mit der Basisadresse, in einem `case` über die
Umgebung. Vorher standen sie an fünf Stellen verstreut in zwei Jobs. Der
Schritt **bricht ab**, wenn die Basisadresse leer ist oder die Umgebung
weder `staging` noch `produktion` heißt; ein leerer Zielpfad bricht
unmittelbar vor dem Abgleich ab, statt die Anwendung in das
Wurzelverzeichnis des FTP-Zugangs zu synchronisieren.

**Warum dort ein `case` steht und kein Ausdruck:** Ein `A && B || C` in
einem GitHub-Ausdruck fällt auf `C` durch, sobald `B` **leer** ist — nicht
nur, wenn `A` falsch ist. Mit
`umgebung == 'produktion' && vars.PRODUKTION_URL || vars.STAGING_URL` hätte
ein Produktivlauf bei leerer `PRODUKTION_URL` still gegen **Staging**
gemessen: Backup-Tor an der falschen Anlage, Zielprobe an der falschen
Adresse, Lauf grün. Das ist der Fehler, den die Zusammenführung hätte
einbauen können. Welche Werte die
beiden Anlagen tragen, steht in `docs/Rahmenplan.md` 6a.

**`JOBS_TOKEN` steht in beiden Umgebungen unter demselben Namen und trägt
verschiedene Werte.** Das Token gehört der **Installation**
(`app_state.jobs_token`, sichtbar unter Betrieb → Hintergrundjobs), nicht dem
Repositorium; Staging und Produktiv sind zwei Installationen. Jeder Lauf liest
es aus seiner eigenen Umgebung, deshalb kollidiert der gleiche Name nicht.
**Seit Kette II/AP5 gibt es dafür keine zwei Zeilen mehr, sondern einen
Schritt:** Der erste Schritt des gemeinsamen Laufs bestimmt Basisadresse,
Zielpfad und Pfad der Zustandsdatei je Umgebung — mit einem `case` und einem
Abbruch, wenn einer fehlt.

### 6.6 Die Integritätswache hängt am Namen

`integritaet.yml` wird per `workflow_run` vom Lauf **„Auslieferung"**
angestoßen. **Wer diesen Lauf umbenennt, hängt die Wache ab — still, ohne
Fehlermeldung.** Genau das stand bis Web 20.3.0 im Raum: Dort hieß die
Kupplung „Server per FTP hochladen" (Fund F4 des P5a-Konzepts).

Seit der Kette hat der Auslieferungslauf **zwei Ziele**, die Wache misst aber
Produktiv. **Bis zum 20.09.2026** fragte sie deshalb zuerst über die API, ob
der Job `produktion` in diesem Lauf mit Erfolg geendet hat, und hielt sonst
still. Dieser Riegel ist mit AP2 entfallen — warum, steht im nächsten
Abschnitt.

### 6.5a Zielprobe und Probelauf — die Kette misst, statt zu glauben

**Die Zielprobe** (`tools/kette/zielprobe.py`, seit AP3 der Kettenhärtung,
E-KH-07) steht **vor dem Backup-Tor**. Sie schreibt per FTPS eine Datei mit
Zufallsnamen und Zufallsinhalt ins Zielverzeichnis, holt sie über HTTPS
unter der Basisadresse der Umgebung zurück, vergleicht sie, löscht sie und
prüft das Löschen (danach 404).

**Seit AP5 läuft sie in beiden Umgebungen** — bis dahin nur auf Produktiv.
Das ist keine Zugabe, sondern E-KH-17: Ein Tor, das nur dort steht, wo es
teuer wird, ist auf dem Weg dorthin nie gefahren worden.

**Warum vor dem Backup-Tor:** Bis dahin hat noch nichts den Server verändert.
Zeigt das FTP-Konto auf ein anderes Verzeichnis als die öffentliche Adresse,
fällt das dort auf — und nicht erst, nachdem ein Komplett-Backup gelaufen und
die Wartung an ist.

**Sie fährt zwei Rundläufe** (seit 20.09.2026): einen **flachen** in das
bestehende Zielverzeichnis, und einen **durch ein neu angelegtes
Verzeichnis** — Anlegen, Hineinschreiben, **Auflisten**, HTTPS, aufräumen.
Der zweite stellt `ensureDir` nach, die Stelle, an der die
Auslieferungsaktion mit `ECONNRESET` abbricht. Der flache berührt sie nie,
und genau deshalb war der Trennversuch zur TLS-Sitzung viermal grün, während
der echte Upload viermal rot war.

**Warum `curl` und nicht die Auslieferungsaktion:** Er ist bewusst ein
**zweiter** FTPS-Client. Scheitert der Upload in der Aktion und die Probe
gelingt, liegt es an der Bibliothek; scheitern beide an derselben Stelle, an
der Plattform. Das ist der Trennschnitt, den F3 braucht. Zwei Betriebsarten
— mit und ohne Wiederverwendung der TLS-Sitzung auf dem Datenkanal —, und ob
`curl` sie tatsächlich wiederverwendet, meldet die Probe **dreiwertig**
(`JA` / `NEIN` / **`NICHT FESTSTELLBAR`**). Einzelheiten:
Kopf von `tools/kette/zielprobe.py`.

**Die Mengenprobe** (`--mengenprobe N`, 1–500) gehört zur selben Datei und
läuft **in keinem Kettenschritt**. Sie fährt einen einzigen `curl`-Aufruf,
der `N` Verzeichnisse anlegt und beschreibt — über **eine** Steuerverbindung.
Sie ist die Antwort auf die Lücke, die nach fünf Trennversuchen übrigblieb:
Die Zielprobe ruft `curl` je Operation einmal auf und bekommt jedes Mal eine
frische Sitzung, die Auslieferungsaktion hält **eine** Verbindung für 688
Dateien und 62 Verzeichnisse offen. Ein Server, der die zweite oder dritte
Datenverbindung **einer** Sitzung abweist, ist für die Zielprobe unsichtbar.
Gemeldet wird die Zahl der abgeschlossenen Übertragungen gegen die verlangte
(„2 von 5") und bei Abbruch der Servertext wörtlich. **Die Zeitgrenze wächst
mit der Zahl der Ziele** (30 s + 8 s je Ziel); gemessen gegen Produktiv sind
80 Ziele ein Schritt von 2:49, Aufräumen eingeschlossen. Läuft sie ab, meldet die Probe ausdrücklich, dass **sie selbst**
abgebrochen hat und nicht der Server — die Falschdiagnose wäre ein falscher
Befund an den Hoster. Sie legt Dateien auf
einem echten Server an — deshalb hinter zwei Riegeln: Sie läuft nur über die
Eingabe **`probelauf_mengenprobe`** (1–500) des Arbeitslaufs „Auslieferung",
und nur zusammen mit dem Häkchen `probelauf`; ohne dieses bricht der Schritt
ab. Ein Tag-Lauf und ein Push haben das Feld nicht. Dann tritt sie **an die
Stelle** der beiden Rundläufe. Aufgeräumt wird im `finally`, auch nach
Abbruch.

**Der Probelauf** (E-KH-08) ist eine Handauslösung mit der Eingabe
`probelauf`. Er fährt denselben Job `produktion` mit derselben
Pflichtfreigabe — und läuft deshalb auch von `main`, wo kein Tag steht:

| gefahren | nicht gefahren |
|---|---|
| die drei Geheimnisse | Tag gegen `WEB_VERSION` |
| **Zustandsdatei anlegen, falls sie fehlt** (E-KH-26) | — |
| Zielprobe (samt Selbstprobe) — **oder, mit `probelauf_mengenprobe` bzw. `probelauf_sitzungsprobe`, die Mengen- oder die Sitzungsprobe an ihrer Stelle** | Tor der grünen Läufe |
| Abgleich als **Trockenlauf** (`dry-run`) — **außer im Gesprächslauf, siehe unten** | Backup-Tor, Wartungsmodus, `doku`-Kopie, Migrationsabfrage |

Geschrieben wird nichts außer **der Probedatei** — die im selben Schritt
gelöscht wird — **und der Zustandsdatei der Auslieferungsaktion**, falls sie
fehlt. Die Zusammenfassung beginnt mit **„PROBELAUF — nichts ausgeliefert"**,
und das gilt: **ausgeliefert** wird nichts. Geschrieben schon, und zwar diese
eine Datei.

**Warum sie dazugekommen ist (E-KH-26, 20.09.2026).** Fehlt die
Zustandsdatei, läuft auch der **Trockenlauf** in den toten Client — er merkt
es nur nicht, weil danach kein Steuerbefehl mehr kommt (6.5b). Die Zeile
„0 geplante Löschungen", auf der die Abnahme beruht, käme dann aus dem
**Fehlerpfad** und nicht aus einem Vergleich mit dem Serverbestand. Eine
Abnahme, die ihre Zahl aus dem Fehlerpfad liest, prüft nichts.

**Was die Datei ist:** wenige hundert Byte, `data: []`, **außerhalb des
Webroots** (`../.deploy-state-produktion.json`) und damit nicht öffentlich
abrufbar. Die Aktion schreibt sie beim ersten echten Lauf ohnehin selbst
fort. **Eine vorhandene wird nie angefasst** — sie trägt den Bestand des
Servers.

**Eine Ausnahme, und sie steht hier und nicht im Kleingedruckten: der
Gesprächslauf** (`probelauf_gespraech`, Prüfpunkt 18a der Kettenhärtung). Er
schaltet den Trockenlauf **ab** — die Aktion schreibt echt. Sie muss es,
denn im Trockenlauf erreicht sie `ensureDir` nie (`syncProvider.js` beginnt
`createFolder` mit `if (this.dryRun === true) return;`), und genau deshalb
hat kein Probelauf F3 je ausgelöst. Dazu `log-level: verbose`, womit
`basic-ftp` seinen FTP-Dialog mitschreibt.

**Drei Riegel halten den Preis klein:**

1. Ohne `probelauf` bricht ein eigener Schritt ab. Er läuft **ohne `if`** —
   ein Riegel, der nur greift, wenn die Lage schon stimmt, ist keiner.
2. `server-dir` zeigt auf **`.zielprobe-gespraech/`** statt auf den Webroot.
   Die Anwendung bleibt unberührt: kein Wartungsmodus, kein halber Stand. Der
   **führende Punkt** ist Absicht — `.htaccess` weist jeden Pfad mit
   führendem Punkt mit 403 ab (Z. 64), sonst läge dort eine zweite,
   öffentlich abrufbare Kopie der Anwendung.
3. `state-name` zeigt auf eine **eigene** Zustandsdatei *innerhalb* des
   Probeverzeichnisses. Ohne das zeigte `../` von dort in den Webroot.

**Was er hinterlässt:** das Probeverzeichnis. Es wird **von Hand** entfernt.
Das Werkzeug räumt einzelne Probedateien weg, keine Bäume — ein Werkzeug,
das Verzeichnisbäume auf dem Produktivserver löscht, soll es nicht geben.

**Das Tor der grünen Läufe entfällt im Probelauf mit Absicht:** Es schützt
Produktiv davor, ungeprobten Code zu bekommen — der Probelauf liefert keinen
Code aus. Und es wäre der falsche Riegel am falschen Tag: Ausgerechnet wenn
die Kette klemmt, braucht man den Probelauf, um zu messen **warum**.

### 6.5b Warum die Zustandsdatei der Auslieferungsaktion da sein muss

**Ohne sie liefert die Kette gar nicht aus.** Das ist nicht Vorsicht, sondern
gemessen: Bis zum 20.09.2026 ist **kein einziger** Abgleich gegen den
Produktivserver durchgelaufen, und das war der Grund.

**Der Ablauf, wörtlich aus dem FTP-Dialog:**

```
> MKD .zielprobe-gespraech
< 257 "/.zielprobe-gespraech" - Directory successfully created
> CWD .zielprobe-gespraech
< 250 CWD command successful
> EPSV
< 229 Entering Extended Passive Mode (|||63029|)
> RETR .deploy-state-gespraech.json
> QUIT
```

**`RETR` — und keine Serverantwort.** Jede andere Zeile hat ihr `<`, diese
nicht. Die Datenverbindung steht per `EPSV` schon, als der Server merkt, dass
die Datei fehlt; er schließt sie, und `basic-ftp` liest `ECONNRESET` **auf
dem Datensocket** statt der `550` auf dem Steuerkanal.

**Und dann kommt das Tückische:** `getServerFiles` fängt jeden Fehler ab und
deutet ihn als *„this must be your first publish! 🎉"*. Die Aktion rechnet
weiter — **mit einem toten Client** — und stirbt erst beim nächsten
Steuerbefehl:

```
creating folder "api/"
Error: Client is closed because read ECONNRESET (data socket)
    at Client.sendIgnoringError → Client._openDir → Client.ensureDir
```

**Die Meldung nennt eine Stelle drei Schritte hinter der Ursache.** Wer sie
für die Ursache hält, sucht bei `ensureDir` — und findet nichts, weil
`_openDir` nur `MKD` und `CWD` sendet und gar keine Datenverbindung öffnet
(`basic-ftp` 6.2.1, Z. 686–689). Acht Trennversuche sind daran vorbeigelaufen.

**Der Zustand erhält sich selbst:** Solange keine Zustandsdatei da ist, stirbt
jeder Lauf daran — und weil er stirbt, wird nie eine geschrieben. Jeder Lauf
ist der erste.

**Die Abhilfe** ist ein eigener Schritt vor dem Abgleich
(`tools/kette/zustand.py`): Er prüft mit `--head` (`SIZE`/`MDTM` auf dem
Steuerkanal, **kein** `RETR` — das ist ja die Operation, die tötet) und legt
die Datei an, wenn sie fehlt. **Eine vorhandene fasst er nie an**, denn sie
trägt den Bestand des Servers; sie zu überschreiben hieße, der Aktion zu
sagen, der Server sei leer. Nach dem Schreiben wird **nachgemessen**, nicht
geglaubt.

**Belegt am 20.09.2026:** 688 Dateien, 62 Verzeichnisse, 9,7 MB, 7 Minuten
47 Sekunden, kein `ECONNRESET` — der erste vollständige Abgleich gegen diesen
Server, gefahren gegen ein Probeverzeichnis. Danach hat die Aktion ihre
Zustandsdatei selbst fortgeschrieben.

**Was zu tun ist, wenn F3 wiederkommt:** Die Zustandsdatei ist weg — aus dem
Backup zurückgespielt, aufgeräumt, oder der Webspace ist neu. Der Schritt
legt sie dann von selbst wieder an; er läuft vor **jedem** Abgleich, gerade
deshalb.

**Und die Kehrseite: Eine auf dem Server von Hand gelöschte Datei kommt
nicht zurück** (Backlog Nr. 214). Die Aktion vergleicht den Auscheckstand
gegen ihre Zustandsdatei, **nie gegen den Server**; dort steht die Datei
weiter mit ihrem Hash, also gilt sie als ausgeliefert, und kein Lauf
schickt sie wieder — bis sich ihr Inhalt im Repositorium ändert. Drei Wege
heraus, der erste ist der schonende:

1. **Die eine Datei von Hand per FTPS hinauflegen**, im Stand des
   ausgelieferten Commits. Sie passt dann wieder zu ihrem Hash, und die
   Zustandsdatei stimmt.
2. **Die Datei im Repositorium ändern** — der nächste Lauf sieht einen neuen
   Hash und liefert sie aus. Nur, wenn ohnehin eine Änderung ansteht.
3. **Die Zustandsdatei löschen.** Der Schritt oben legt eine leere an, und
   die Aktion hält den Server für leer: **Der nächste Lauf überträgt alles**
   — gemessen am 20.09.2026 688 Dateien in rund acht Minuten, und das mit
   eingeschalteter Wartung, denn die geht in `ausliefern-lauf.yml`
   unmittelbar vor dem Abgleich an. Auf Produktiv der teuerste Weg.

### 6.6a Der Zeiger `produktion` — wogegen die Wache vergleicht

**Der Fehler war nicht der Auslöser, sondern der Vergleichsstand** (Befund B6
der Kettenhärtung, E-KH-13). Die Wache verglich den Produktivserver gegen
`server/` **neben sich** — also gegen den Zweig, auf dem sie lief, und das
ist `main`. Auf Produktiv liegt aber nicht `main`, sondern der zuletzt
**ausgelieferte** Stand. Sobald `main` einen Schritt weiter ist, und das ist
der Normalfall, meldete sie eine Abweichung, die keine ist. Sie war deshalb
seit dem 17.09.2026 **täglich rot**.

**Nachgerechnet** (20.09.2026, aus den Ständen selbst, ohne Netz): Am
18.09.2026 lag auf Produktiv `14f99ac` (P5a) und auf `main` stand `eec41e1`
(P5b). Die alte Anordnung meldete damit **128 Dateien, 121 gleich, 1
abweichend** (`assets/style.css`) **und 6 × 404** (`doku.js`,
`rueckfrage.js`, `schluessel.js`, `schluesselblatt.js`, zwei Symbole) — dazu
in Teil 2 `login.php`, das zwischen beiden Ständen um 128 Zeilen gewachsen
war. Die neue Anordnung, gegen den Zeiger gerechnet: **122 Dateien, 122
gleich, 0 abweichend, 0 × 404.**

**Der Zeiger ist der Zweig `produktion`.** Er zeigt auf den Commit, der
ausgeliefert wurde. Bewegt wird er vom Job **`zeiger`** in
`auslieferung.yml`, und zwar nur nach einem erfolgreichen `produktion`-Job
(`if: needs.produktion.result == 'success'` — `needs` allein genügt nicht,
ein übersprungener Job gilt GitHub als erfüllte Abhängigkeit).

| | |
|---|---|
| Werkzeug | von `main` — eine Verbesserung an der Wache soll sofort greifen, nicht erst nach dem nächsten Deploy |
| Vergleichsstand | vom Zeiger — `actions/checkout` mit `ref: produktion`, `path: zeiger`, `fetch-tags: true` |
| Aufruf | `wache.py "$BASIS" --stand zeiger` |
| Zusammenfassung | nennt verglichenen Commit, Tag und Dateizahl |
| Fehlender Zeiger | **rot mit Ansage**, nie still grün — samt dem Befehl, ihn anzulegen |
| Auslöser | **jeder** — der `workflow_run`-Riegel ist entfallen |
| Berechtigungen | `contents: write` **nur** im Job `zeiger`; der Job mit den FTPS-Geheimnissen bleibt bei `read`. `actions: read` ist mit dem Riegel entfallen |

**Warum nicht das jüngste `web-v*`-Tag:** Es ist nicht der ausgelieferte
Stand. Ein Tag kann gesetzt und die Freigabe nie erteilt worden sein, das
Backup-Tor kann zugegangen sein — und nach einem Zurücksetzen liegt ein
**älterer** Stand oben als der jüngste Tag. Deshalb schiebt der Job erzwungen:
Ein Zurücksetzen ist eine Auslieferung, und der Zeiger folgt dorthin.

**Dass der Riegel entfallen kann, ist eine Folge davon und kein Nebenbei.**
Er war nötig, solange gegen `main` verglichen wurde: Nach einem
Staging-Deploy hätte die Wache Produktiv gegen einen Stand gehalten, der dort
gar nicht liegt. Jetzt ändert sich der Vergleichsstand **nur** bei einer
Produktiv-Auslieferung — der Riegel wäre ab jetzt schädlich, weil er die
Wache nach einem Staging-Deploy schweigen ließe, obwohl der Vergleich gültig
wäre.

**Die Regel für Menschen** steht in `CLAUDE.md` 3: Auf `produktion` wird
nicht entwickelt, es gibt keinen PR dorthin, und von Hand bewegt wird er
nicht.

### 6.6b Der Hotfix-Weg — Runbook (ab AP7, E-KH-15/-16)

**Wann man ihn braucht.** Auf Produktiv liegt Tag `web-v20.26.2`, etwas ist
kaputt, und `main` ist inzwischen bei `20.28.0` mit Dingen, die noch nicht
ausgeliefert werden sollen. Über `main` zu reparieren hieße, alles
mitzuliefern. Der Hotfix-Weg zweigt stattdessen **vom Ausgelieferten** ab.

**Die Zusage dahinter, und sie ist der Grund für die Abstammungsprüfung:**
Ein Handlauf auf einem Zweig umgeht den Zweigschutz von `main`. Was ihn
ersetzt, ist die Abstammung vom Zeiger `produktion` — der Hotfix muss von dem
Stand kommen, der draußen läuft, sonst lässt das Tor ihn nicht durch.

**Der Rückfallstand entsteht von selbst.** Bei jeder Produktiv-Auslieferung
legt der Job `Rückfallstand (Staging)` ein Komplett-Backup **auf Staging** an
und schreibt dessen Dateinamen neben den Tag in die Laufzusammenfassung
(E-KH-16). Diesen Namen braucht Schritt 2.

**Über Web 21.0.0 zurück geht es nur mit dem Rückfallstand** (P5c/AP8). Die
Migration `2026_09_25_zentrale_stammdaten` wirft die Tabelle der Auswahl
zentraler Standorte weg, und der Code davor liest sie in jeder
Diensttag-Auswahl. Ein älterer Stand, ohne die Datenbank dazu ausgeliefert,
läuft auf eine fehlende Tabelle. Erst den Komplett-Stand einspielen
(Schritt 2), dann ausliefern — dasselbe gilt für jeden Hotfix, dessen Tag vor
21.0.0 liegt.

---

1. **Schemaunterschied zwischen Tag und `main` prüfen.** Unterscheiden sich
   die Migrationsstände nicht, ist Staging schon brauchbar — **weiter bei
   Schritt 3.** Unterscheiden sie sich, zeigt Staging ein Datenmodell, das es
   auf Produktiv nicht gibt, und ein dort geprobter Hotfix beweist nichts.

2. **Staging auf den Stand des Tags zurücksetzen.** Die Laufzusammenfassung
   der Auslieferung dieses Tags nennt den Dateinamen des Komplett-Standes.
   Einspielen über **Betrieb → Wiederherstellen** (`wiederherstellen.php`),
   als angemeldete AdministratorIn.
   **Die Kette setzt nichts aus der Ferne zurück** (E-KH-16): Eine
   Wiederherstellung ist der einschneidendste Vorgang, den die Anwendung
   kennt, und sie bleibt dort, wo die Anwendung sie hingelegt hat.
   > **Der Stand wird verdrängt.** Staging bewahrt nur eine begrenzte Zahl
   > von Komplett-Ständen auf — **der Vorgabewert ist 2**
   > (`KOMP_AUFBEWAHRUNG_VORGABE`), einstellbar unter **Betrieb →
   > Komplettsicherung → „Stände aufbewahren"** (1 bis 20). Bei 2 ist der
   > Stand des vorletzten Tags bereits weg. Wer den Hotfix-Weg ernst meint,
   > setzt die Aufbewahrung höher oder lädt den Stand herunter, wenn die
   > Zusammenfassung ihn nennt.

3. **`hotfix/*` vom Zeiger abzweigen.**
   `git fetch origin produktion && git switch -c hotfix/20.26.3-anmeldung origin/produktion`
   Der Zweigname muss mit `hotfix/` anfangen — der Schrägstrich gehört dazu,
   `hotfix-schnell` ist keiner. Dann der Fix, und **eine eigene
   Korrekturversion** in `server/version.php`; der Changelog nennt den
   Hotfix. **Vor dem Commit den Prüfstand fahren** (`bash
   tools/pruefstand/pruefen.sh`) und den Bericht in die Commit-Nachricht
   schreiben — seit PK-05 liest Stufe 1 auch beim Handlauf auf einem Zweig
   gegen (E-PK-43). Gemessen wird gegen `main`; weil die Fassungen dort
   auseinanderliegen, heißt die Stufe meist „neben" — das ist mehr, nicht
   weniger.

4. **Handlauf auf Staging, Stufe 2 grün.** Actions → „Auslieferung" → *Run
   workflow* → Zweig `hotfix/…` → **alle Kästchen leer**. Der Job
   `staging / ausliefern` fährt dieselbe Schrittfolge wie sonst; danach läuft
   Stufe 2 gegen Staging. Beides muss grün sein — das Tor fragt nachher genau
   danach.

5. **Tag und Freigabe.** `git tag web-v20.26.3 && git push origin web-v20.26.3`.
   Das Tor der grünen Läufe prüft jetzt dreierlei: grüner Stufe-1-Lauf auf
   demselben **Baum** (ein Handlauf von `pruefung.yml` auf dem Hotfix-Zweig
   liefert ihn, wenn der Commit den Bericht aus Schritt 3 trägt), grüner
   `staging`-Job auf **diesem** Commit, und
   **Abstammung vom Zeiger**.
   Dann die Pflichtfreigabe. Nach dem Lauf rückt der Zeiger `produktion` auf
   den Hotfix nach.

6. **PR nach `main`.** Der Hotfix wird geholt, **`main` behält seine höhere
   Fassung** — die Korrekturversion des Hotfixes ist eine Seitenlinie, kein
   Rückschritt für `main`. Der Changelog nennt den Hotfix als solchen.

7. **Staging zurück auf `main`.** Handlauf auf `main` (oder der nächste Push
   dorthin), und **Migrationen von Hand** unter Betrieb → Updates: Der
   Rücksprung von einem älteren auf einen neueren Schemastand geschieht nicht
   von selbst.

---

**Was schiefgehen kann, und woran man es merkt:**

| Symptom | Ursache |
|---|---|
| Der Tag-Lauf bricht am Tor ab: „stammt NICHT vom Zeiger `produktion` ab" | Der Hotfix-Zweig wurde von `main` abgezweigt statt vom Zeiger — Schritt 3 noch einmal |
| Dasselbe, obwohl richtig abgezweigt | Der Zeiger ist inzwischen weitergerückt (eine andere Auslieferung dazwischen). Neu abzweigen und Schritt 4 wiederholen |
| „Abstammung: nein (Vergleich: — nicht ermittelt —)" | Der Zweig `produktion` fehlt. Er ist kein Arbeitszweig und wird nur vom Job `zeiger` bewegt (6.6a) |
| Der Handlauf auf `hotfix/*` zählt nicht | Der Job `staging` war übersprungen oder rot. Übersprungen ist nicht geprüft (B5) |
| Der Handlauf von `pruefung.yml` auf `hotfix/*` ist rot: „Kein Prüfbericht in der Nachricht" | Der Hotfix-Commit trägt keinen Bericht — Schritt 3, Prüfstand fahren, Commit mit Bericht darüber (E-PK-43) |
| Der Rückfallstand heißt `unbekannt` | Die Anlage hat keinen Komplett-Stand gemeldet. Der Job ist dann **rot** — ein Stand, dessen Namen niemand kennt, ist keiner |

**Der Rückfallstand blockiert die Auslieferung nicht** (E-KH-30). Scheitert
er, ist der Lauf rot und sagt, dass er fehlt; ausgeliefert wird trotzdem.
Die Begründung ist unbequem und trägt: Ein Hotfix wird gebraucht, **weil**
etwas kaputt ist. Hinge die Produktiv-Auslieferung daran, dass Staging
erreichbar ist, dann sperrte eine kranke Testumgebung die Reparatur der
echten — genau verkehrt herum.

### 6.7 Selbst hosten — der Weg ohne GitHub bleibt vollständig

Dateien hochladen, `update.php` aufrufen. Das ist der eine Weg, den jeder
gehen kann, und er bleibt es (PP-9, Muss). Die Anwendung weiß nicht, wie sie
auf den Server gekommen ist — FTPS, SFTP, rsync über SSH und ein Upload von
Hand sind gleichwertig. Was sie weiß, ist ihre eigene Fassung
(`version.php`) und ob eine Migration aussteht.

## 7. Betrieb (Runbook)

**Vor jeder Auslieferung: die Wegwerfliste nachziehen** (seit Web 20.22.0,
Backlog Nr. 230).

    python3 tools/erzeugen/wegwerfdomains.py              # holen, messen, Diff zeigen
    python3 tools/erzeugen/wegwerfdomains.py --schreiben  # und schreiben

**Ein Handgriff, kein Automatismus** — die Zusage „keine fremde Quelle zur
Laufzeit" (R36) kennt keine Ausnahme, auch nicht für eine Textdatei. Der Preis
ist, dass `server/wegwerfdomains.txt` genau so lange altert, wie niemand den
Lauf macht, **und dass es niemandem auffällt**: Eine durchgelassene
Registrierung sieht aus wie eine richtige.

**Das Werkzeug schreibt nicht, wenn etwas nicht stimmt** — weder bei
Großbuchstaben, Kommentarzeilen oder Doppelungen noch, wenn eine der zehn
geprüften echten Provider- und Klinikdomains auf der Liste steht. Die zweite
Prüfung ist die wichtigere: Eine getroffene Klinikdomain sperrt die Ärztin
dahinter aus, und sie erfährt **nie**, woran es lag (die Seite antwortet auf
jede Adresse gleich). Ein Treffer ist deshalb kein Grund, die Liste zu kürzen,
sondern einer, die Quelle zu wechseln. Nach dem Schreiben: Zahl und Datum in
`docs/Lizenzen.md` 7b nachziehen.

**Update mit Wartungsmodus (seit Web 13.2.0 der Regelweg, S5 Paket W):**
Zwischen dem ersten und dem letzten per FTPS hochgeladenen File stehen alte
und neue Dateien nebeneinander, und zwischen dem Hochladen und der Migration
erwartet neuer Code Tabellen, die es noch nicht gibt. Wer in dieses Fenster
gerät, bekommt **500**. Für eine Uhr ist das etwas anderes als ein 503: Der
JSON-Vertrag sagt zu 5xx „später unverändert erneut versuchen" — sie puffert
und liefert nach. Die acht Schritte:

> **Und seit Web 20.6.0 nimmt der Torwächter Schritt 2 auch ohne Kette ab**
> (P5a/AP3): Steht nach dem Hochladen eine Migration aus, schaltet die
> Anwendung den Wartungsmodus bei der ersten angemeldeten Anfrage selbst ein
> und sagt auf der Wartungsseite, warum. **Aus geht er nie von selbst** — das
> bleibt Schritt 6, von Hand (R66).

> **Seit Web 20.4.0 nimmt die Kette die Schritte 1, 2 und 3 ab** (P5a/AP1,
> Abschnitt 6.4): Der Produktionslauf fährt das Komplett-Backup zu Ende,
> schaltet die Wartung ein, lädt hoch und schaltet sie hinterher wieder aus —
> **außer** es steht eine Migration aus, dann bleibt sie an und der Lauf sagt
> es. Von Hand bleiben damit Schritt 4 und die Gegenproben 5 bis 8. Die
> Schritte unten stehen weiterhin vollständig da: Sie sind der Weg **ohne**
> Kette, und den geht jede Selbsthosterin und jede, bei der die Kette gerade
> nicht läuft.

1. **Komplett-Backup prüfen** (Zeitpunkt, Ziel erreichbar), bei Bedarf
   „Jetzt sichern". Der Weg dorthin steht auf **Betrieb → Updates** in der
   Karte „Ausstehende Updates": Sie nennt den jüngsten Komplett-Stand mit
   Alter und verlinkt die Komplett-Sicherung.
2. **Wartungsmodus einschalten** — Karte „Wartungsmodus" oben auf
   **Betrieb → Updates** (`betrieb_updates.php`). Ab jetzt bekommt jede
   Anfrage außer den Ausnahmen 503 mit `Retry-After: 300`.
3. **Dateien hochladen.** Mit Kette: Tag `web-vX.Y.Z` setzen und die Freigabe
   erteilen. Ohne Kette: `server/` per FTPS, SFTP oder rsync hochladen.
4. **Betrieb → Updates neu laden** → ausstehende Migrationen ausführen.
5. **Startseite in einem zweiten Reiter prüfen.** Es *muss* 503 kommen —
   kommt eine Seite, steht der Wartungsmodus nicht.
6. **Wartungsmodus ausschalten.** Startseite erneut: antwortet, und die
   Fassung in der Fußzeile ist die neue.
7. Uhr und Handy synchronisieren beim nächsten Kontakt von selbst. Nichts
   ist verloren gegangen; die Geräte haben gepuffert.
8. **Betrieb → Status aufrufen** (`betrieb_status.php`) — die Prüfstelle nach
   jedem Deploy (S8/AP4). Vier Karten, je Sache eine Zeile mit Plakette;
   erwartet wird die Meldung **„Alles läuft"**. Was hier auffällt und
   anderswo nicht: ein Job, der seit dem Update scheitert, eine
   Schlüsselableitung, mit der sich Konten nicht mehr anmelden können, ein
   Serverschlüssel, den der Deploy nicht mitgebracht hat, eine Ablage, die
   nicht beschreibbar ist. Die Seite **ändert nichts** — jede Zeile führt
   dorthin, wo sich etwas ändern lässt. Blau heißt in Ordnung, orange
   „arbeitet, braucht Aufmerksamkeit", rot „arbeitet nicht". Steht dort eine
   Zahl, ist der Deploy noch nicht fertig.

**Nach dem Ausrollen sind alle abgemeldet — einmal, und mit Ansage**
(seit Web 20.26.0, Schritt 16, E-SA-08). Die Anwendung legt ihre
Sitzungsdateien seither selbst ab (`server/.sitzungen/` statt des Pfads, auf
den der Hoster zeigt). Beim ersten Aufruf nach dem Ausrollen sucht sie im
neuen Verzeichnis, und dort liegt noch nichts: **Jede offene Sitzung endet,
jede Angemeldete meldet sich einmal neu an.** Es geht dabei nichts verloren —
keine Eingabe, kein Schlüssel, kein Einsatz; die Betroffene sieht die
Anmeldeseite statt der Seite, die sie erwartet hat.

Das ist der Gegensatz zu Schritt 7: **Uhr und Handy puffern, die
Browsersitzungen nicht.** Dasselbe passiert ein zweites Mal, falls sich der
Ort noch einmal ändert — etwa wenn die Probe nach einer Stunde ein anderes
Ergebnis liefert (E-SA-02). Ein Mischbetrieb zweier Ablagen wäre das
Schlimmere; ein sauberer Schnitt ist deshalb gewollt.

**Danach: Betrieb → Status, Zeile „Sitzungsablage" ansehen** (seit Web
20.26.1). Steht sie **blau**, liegt alles richtig. Steht sie **rot**, sagt der
Satz daneben, was zu tun ist — er rät nicht mehr:

| Satz in der Kleinzeile | Handgriff |
|---|---|
| „Das eigene Verzeichnis ließ sich nicht anlegen" | Schreibrecht der Anwendungswurzel prüfen |
| „Das eigene Verzeichnis ist nicht beschreibbar" | Rechte von `server/.sitzungen/` prüfen |
| **„die Anlage übernimmt den gesetzten Pfad aber nicht"** | **`.user.ini` anlegen, siehe unten** |
| „lief bereits eine Sitzung" | `session.auto_start` oder `auto_prepend_file` der Anlage; dann tragen die Sitzungscookies auch **kein** `secure`/`SameSite` — beim Hoster abstellen lassen |

**Der Handgriff `.user.ini`** (gemessen auf Staging am 20.09.2026, 5b.2a).
Eine Datei neben `index.php`, eine Zeile, der absolute Pfad der Anlage:

    session.save_path = "/home/webpages/…/nadoku-staging/.sitzungen"

Dann rund fünf Minuten warten (`user_ini.cache_ttl`, Vorgabe 300 s) und die
Statusseite neu laden. **Auf Staging hat das gereicht** — der Hoster hatte
`session.save_path` nur gesetzt, nicht gesperrt.

Sie liegt **nur auf dem Server** und gehört nicht ins Repositorium: Der Pfad
ist anlagenabhängig (E-PP-04). Sie beginnt mit einem Punkt und fällt damit
unter dieselbe `.htaccess`-Sperre wie `.sitzungen/`. **Sie steht allerdings
NICHT in der Ausnahmeliste des Transports** — sie muss dort nicht stehen,
solange der Transport nur löscht, was er selbst hochgeladen hat (6.5), aber
wer den Transport wechselt, denkt an sie.

**Produktiv ist ungeprüft.** Dort ist `session.save_path` nie erhoben worden;
ob der Handgriff auch dort fällig ist, sagt die Zeile beim ersten Ausrollen.

**Sie wiederholt sich nicht bei jedem Deploy** — das ist nachgemessen und
nicht angenommen (6.5): Der heutige Transport löscht `.sitzungen/` nicht, weil
seine Löschliste nur aus der eigenen Zustandsdatei entsteht und der Ordner dort
nie stand. Dass `.sitzungen/` trotzdem in die Ausnahmeliste gehört (Kette II,
E-KH-20), hat einen anderen Grund; er steht in 6.5.

**Was währenddessen erreichbar bleibt** (E-S5W-04): die **sechs**
Betriebsseiten `betrieb_status.php`, `betrieb_statistik.php`,
`betrieb_updates.php`, `betrieb_jobs.php`, `betrieb_server.php` und — seit
S10 — `betrieb_schluesselblatt.php` (die Lage, in der man das Blatt braucht,
ist genau eine Wartungslage), dazu `update.php` und
`wiederherstellen.php` (die Arbeit selbst und der Rückweg), `jobs.php` mit
Token — das Komplett-Backup der Kette läuft **während** der Wartung, genau
dann ist es konsistent —, `login.php` mit `auth_salt.php` (ohne den
Nebenaufruf holt der Browser weder Salz noch Rundenzahl und leitet kein Token
ab, Backlog Nr. 171), `logout.php` und `install.php`. Alles
unter `assets/` läuft ohnehin nicht durch PHP. Der CLI-Notausgang
`php update.php` ist nie getort.

**Wer sich während der Wartung anmeldet und nicht verwaltet**, wird nach der
gelungenen Anmeldung **sofort wieder abgemeldet** und sieht die Wartungsseite
(E-S5W-09). Das ist Absicht: Während des Umbaus soll keine Sitzung mit
entsperrtem Inhaltsschlüssel herumliegen, und keine Anmeldung soll
`last_login` schreiben, während das Schema geändert wird.

**Der Wartungsmodus lässt sich nicht vergessen — theoretisch.** Es gibt kein
automatisches Ausschalten und keine Zeitsteuerung (E-S5W-05). Auffallen kann
ein stehengebliebener Wartungsmodus nur dort, wo `wartung_balken()` steht: auf
den **fünf Betriebsseiten** und auf `login.php`. Dort steht dann oben ein
oranger Balken mit Zeitpunkt und Konto. Alles andere antwortet mit 503, und
ein 503 sagt nicht, dass es seit drei Tagen kommt.

> **Bis Web 15.5.1 fehlte der Balken auf `betrieb_statistik.php`** — der
> einzigen der fünf Ausnahmeseiten ohne ihn, und ausgerechnet der, auf der
> man am längsten liest. Gefunden beim Nachrechnen für das Handbuch (S8/AP8),
> nicht von einem Prüfmittel. Die Regel lautet seither ohne Ausnahme: **Wer
> in `WARTUNG_AUSNAHMEN` steht, zeigt den Balken.** Gemessen bei
> eingeschaltetem Wartungsmodus: fünf Seiten mit 200 und Balken, neun weitere
> Seiten mit 503.

**Der Schalter ist eine Datei.** `server/wartung.lock`, JSON mit `seit` und
`von`. Wer keinen Browserzugang mehr hat, legt sie per SSH an oder löscht
sie — das ist der ganze Mechanismus:

```bash
echo '{"seit":"2026-09-03T14:12:00Z","von":"SSH"}' > server/wartung.lock  # ein
rm server/wartung.lock                                                     # aus
```

**Die Datei ist der Schalter, nicht ihr Inhalt.** Ist sie da, aber unlesbar
oder kein gültiges JSON, gilt die Wartung trotzdem; der Balken sagt dann
„seit unbekannt". Andersherum wäre es falsch — ein Tippfehler im Inhalt darf
keine Installation öffnen, die jemand ausdrücklich geschlossen hat.

**Sie steht in `.gitignore` und in der Ausnahmeliste des Deploys.** Beides
muss so bleiben: Ohne den ersten Eintrag schlösse ein Checkout jede
Installation, ohne den zweiten löschte der Push die Datei — mitten im Update,
für das sie da ist.

**Der Wartungsmodus greift nicht:** Prüfen in dieser Reihenfolge —
(1) Liegt `server/wartung.lock` wirklich dort, wo `WARTUNG_DATEI` hinzeigt
(neben `db.php`)? (2) Ist die aufgerufene Seite eine der Ausnahmen (`WARTUNG_AUSNAHMEN`, seit Web 21.1.0 sechzehn; hier stand bis dahin „dreizehn", es waren vierzehn)?
(3) Steht die Zeile `wartung_tor();` in `db.php` noch **vor** jedem
`db()`-Aufruf? Nachweis für alle drei:
`php tools/proben/wartung/probe.php` (**69 Erwartungen**, gezählt 25.09.2026; seit Web 15.5.2 misst
ihr Teil 6 zusaetzlich die Zaehlweise der Migrationen, Backlog Nr. 149, seit
15.6.0 mit 12a, dass die Integritaetswache im Wartungsmodus nicht rot wird,
Nr. 140, seit S10 mit 6a, dass das **Schluesselblatt** erreichbar bleibt —
die Lage, in der man es braucht, ist eine Wartungslage —, und seit Web 21.1.0
mit 6b Komplett-Backup und Backup-Ziele samt Balken).

**Die Integritaetswache ist rot:** `tools/integritaetswache/LIESMICH.md`,
Abschnitt „Wenn sie rot wird" — in dieser Reihenfolge: Wurde gerade deployt?
Steht `main` weiter als die Auslieferung? Erst wenn beides nicht passt, ist es
eine Manipulation, und dann gilt: **nichts ueberschreiben**, bevor die
abweichende Datei per FTPS heruntergeladen und beiseitegelegt ist — sie ist
der Beleg. Danach FTPS-Zugangsdaten wechseln, Deploy neu ausloesen, und jedes
Passwort, das seit der Abweichung eingegeben wurde, als moeglicherweise
mitgelesen behandeln.

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
Referenzbestand neu einspielen (der nächste Absatz), dann die Fixture
neu erzeugen, dann ausrollen, dann im Adminbereich zurücksetzen. Die
Reihenfolge ist wesentlich: Eine Fixture aus einem halb eingespielten Bestand
sieht vollständig aus und ist es nicht.

**Den Referenzbestand neu aufbauen** — vollständig aus dem Repositorium; kein
Notweg, sondern der reguläre (Anleitung: `tools/referenzdatensatz/einspielen/`):

```
sh      tools/referenzdatensatz/einspielen/lokal_starten.sh
python3 tools/referenzdatensatz/einspielen/einspielen.py --stufen konto
php     tools/referenzdatensatz/einspielen/demo_kennzeichnen.php
node    tools/referenzdatensatz/einspielen/passwort_setzen.mjs '<Einrichtungslink>' nadokudemo0815 rc.json
python3 tools/referenzdatensatz/einspielen/einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste,schneiden
node    tools/referenzdatensatz/browser/csv_import.mjs
node    tools/referenzdatensatz/browser/referenz_export.mjs
```

Rund vier Minuten für den Bestand, dazu je Export einige Minuten für die
GPX-Dateien. **Nicht identisch** kommen zurück: interne Kennungen (die
Normalisierung nimmt sie weg), `created_at` der Einsätze und die
Gerätekennungen (`dev-…`). Ein Vergleich über einen Wiederaufbau hinweg
zeigt die beiden letzten als Abweichung, und das ist richtig — die Zeilen
und das Gerät sind neu. Innerhalb eines Umlaufs muss `created_at` dagegen
wörtlich stimmen.

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
Spurpunkten sollte trotzdem ein echter Zeitgeber her.
**Betrieb → Hintergrundjobs** → Karte **„Auslöser"**; dort stehen Befehl
und Adresse je in einem Wertekasten mit Knopf „kopieren":

1. **Kommandozeile** (bevorzugt): `* * * * * php /pfad/zur/installation/jobs.php`
   — den Pfad dieser Installation nennt der Wertekasten, **nicht** `server/`,
   das es auf dem Server nicht gibt (Nr. 150). Jede
   Minute ist unbedenklich — ein Lauf ohne Arbeit kostet zwei Abfragen. Die
   tägliche Aufräumarbeit läuft trotzdem nur einmal am Tag; das entscheidet der
   Job, nicht der Zeitplan. Einzelne Jobs: `php jobs.php waisen`, Hilfe:
   `php jobs.php --hilfe`.
2. **Abruf über die Adresse**, wo es keinen CLI-Cron gibt:
   `https://…/jobs.php?token=…`. **Die Adresse enthält ein Geheimnis** — nicht
   in eine Mail, nicht in ein Ticket. Ein neues Token macht das alte ungültig;
   ein bestehender Zeitplan-Eintrag läuft danach ins Leere.

**Spuren werden nicht verdichtet (Rückstand wächst):**
**Betrieb → Hintergrundjobs** → Karte „Zustand" → Zeile
**„Spuren verdichten"**. Darunter steht, was
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
ab, und Betrieb → Hintergrundjobs zeigt sie an. **Sie ist kein Ersatz für ein
Backup:** Was der Ausdünnungsjob einmal ersetzt hat, ist weg.

**Nach dem Ausrollen von Web 10.2.0 auf einen gewachsenen Bestand:** Der erste
Verdichtungslauf trägt den ganzen Altbestand ab. Gemessen an 3,3 Mio. Punkten:
9395 Spuren in 44 s über die Kommandozeile. Am Huckepack-Weg (3 s je Anfrage,
frühestens alle fünf Minuten) dauert dasselbe Tage — wer den Altbestand zügig
abgetragen haben will, richtet vorher einen der beiden anderen Auslöser ein
oder ruft einmal `php jobs.php verdichtung` von Hand auf.

**Zeitplan-Eintrag antwortet `429` (`zu_viele_versuche`):** Das Token stimmt
nicht, und zehn Fehlversuche haben die IP für zehn Minuten gesperrt. Adresse
aus **Betrieb → Hintergrundjobs** neu kopieren, dann **zehn Minuten warten** — vorher wird auch
der richtige Aufruf abgewiesen.

**Läuft die Wartung noch?** **Betrieb → Hintergrundjobs** → Karte
**„Zustand"**: je Job
letzter Lauf, Auslöser, Rückstand und letzter Fehler. Plakette „scheitert" =
mindestens ein Job wirft dauerhaft; der Text steht darunter. Plakette
„Migration ausstehend" = die Tabelle `jobs` fehlt, also lief der Migrationslauf
nach dem Ausrollen von Web 10.1.0 nie. Ein wachsender **Rückstand** beim
Job `waisen` heißt nicht „kaputt", sondern „kommt am Huckepack-Weg nicht
hinterher" — dann Punkt 1 oder 2 oben einrichten.

**Gerät verloren / Schlüssel kompromittiert:** Web → „Geräte" (oder Verwaltung)
→ **Deaktivieren**. Wirkt sofort (Ingest antwortet `403`); Daten bleiben. Neue
Uhr = neues Gerät anlegen.

**Die Kopplung klappt nicht (seit Web 13.0.0 anders zu suchen als davor):**
Der Ablauf hat drei Schritte — das Gerät holt sich einen Code, ein Mensch
gibt ihn im Web ein, das Gerät bestätigt —, und jeder kann für sich scheitern.
Die Meldung, die eine Person sieht, sagt selten, an welchem. Deshalb der Reihe
nach, von der billigsten Prüfung zur teuersten. Alle SQL-Beispiele laufen auf
der Anwendungsdatenbank; `10` ist `PAIR_TTL_MIN` aus `db.php`.

1. **Ist die Migration gelaufen?** Ohne sie gibt es die Tabelle nicht, und
   `start` antwortet mit `500`. **Betrieb → Updates** → Karte „Ausgeführt":
   die Zeile
   `2026_09_03_kopplungssitzungen` muss „erledigt" tragen — auf einer frisch
   installierten Anlage „übersprungen", weil `schema.sql` die Tabelle schon
   mitbringt. Von der Kommandozeile:

   ```sql
   SHOW TABLES LIKE 'pair_sessions';
   SELECT id, status, applied_at FROM schema_migrations
    WHERE id = '2026_09_03_kopplungssitzungen';
   ```

   Fehlt beides, lief der Migrationslauf nach dem Ausrollen nie. Das ist
   der häufigste Fall und der einzige, bei dem gar nichts geht.

2. **Kommt der Code überhaupt beim Server an?** Wenn das Gerät einen Code
   zeigt, steht er in der Tabelle — sonst zeigt es einen Fehler, nicht einen
   Code.

   ```sql
   SELECT id, code, device_id, user_id, erstellt_am,
          TIMESTAMPDIFF(SECOND, NOW(),
                        DATE_ADD(erstellt_am, INTERVAL 10 MINUTE)) AS rest_s
     FROM pair_sessions ORDER BY id DESC LIMIT 5;
   ```

   Keine Zeile aus der letzten Viertelstunde heißt: Die Anfrage `start` hat
   den Server nie erreicht (Adresse, TLS, Telefon außer Reichweite) oder sie
   wurde abgewiesen — dann weiter bei Punkt 4 und 5.

3. **Ist die Frist abgelaufen?** `rest_s` aus der Abfrage oben. Es gibt
   **eine** Frist von zehn Minuten ab `erstellt_am`, für alles: Eingeben im
   Web und Bestätigen am Gerät müssen beide hineinfallen, und das Beanspruchen
   verlängert nichts (E-S5-12). Ein Code aus Minute elf ist kein Fehler,
   sondern Ablauf; das Gerät holt einen neuen. Zeilen mit `rest_s <= 0` bleiben
   liegen, bis der Job `aufraeumen` sie entfernt — sie zählen aber nirgends
   mehr mit.

4. **Greift eine Sperre?** Drei Töpfe zählen an drei verschiedenen Stellen;
   `merkmal` ist `ip:<adresse>` oder `id:<kontokennung>`.

   ```sql
   SELECT topf, merkmal, versuche, fenster_start, gesperrt_bis
     FROM rate_limits
    WHERE topf IN ('pair','pair_start','pair_code')
      AND (gesperrt_bis > NOW() OR versuche > 0)
    ORDER BY gesperrt_bis DESC;
   ```

   `pair_start` (20 je 10 min, je Adresse) sperrt das Holen eines Codes — das
   Gerät meldet „Zu viele Versuche". `pair_code` (10 je 10 min, je Konto
   **und** Adresse) sperrt die Eingabe im Web; die Seite nennt dann die
   Uhrzeit, bis zu der sie nichts annimmt. `pair` (10 je 10 min, je Adresse)
   sperrt die ausgewiesenen Anliegen — und, das ist beim Suchen zu wissen,
   **auch das Token von `jobs.php` und den GPX-Abruf**: Ein gesperrter Topf
   `pair` kann von einem falsch eingetragenen Zeitplan stammen und nicht von
   der Kopplung. Eine Sperre läuft von selbst ab; wer nicht warten will,
   löscht die betreffende Zeile.

5. **Ist der Server an der Obergrenze?** Dann antwortet `start` mit `429` und
   `zu_viele_sitzungen`, und das Gerät sagt „Server ausgelastet".

   ```sql
   SELECT COUNT(*) FROM pair_sessions
    WHERE erstellt_am > DATE_SUB(NOW(), INTERVAL 10 MINUTE);
   ```

   Gegen `PAIR_SITZUNGEN_MAX` = 1000 (`db.php`) halten. Gezählt werden nur
   unverfallene Zeilen; ein großer Bestand alter Zeilen ist also nicht die
   Ursache, sondern nur unaufgeräumt. Erreicht diese Zahl im Betrieb die
   Grenze, ist es kein Betriebsfall, sondern ein Angriff — Punkt 4 zeigt, von
   welchen Adressen.

6. **Ist das Konto voll?** Höchstens `MAX_GERAETE` (5) echte Geräte, aktive
   wie deaktivierte.

   ```sql
   SELECT COUNT(*) FROM devices
    WHERE user_id = <konto> AND device_id NOT LIKE 'manual-%';
   ```

   Ist die Grenze erreicht, nimmt die Geräteseite den Code gar nicht erst an
   und sagt es. Kommt die Meldung dagegen **am Gerät** („Zu viele Geräte /
   Erst eines im Web löschen"), ist zwischen Eingabe und Ja ein Gerät
   dazugekommen; die Sitzung ist dann gelöscht, und der Weg zurück ist ein
   Gerät löschen und von vorn beginnen (E-S5-18).

7. **Trägt das Gerät noch einen bcrypt-Schlüssel?** Seit Web 13.0.0 liegen
   Geräteschlüssel als SHA-256; ein Hash aus der Zeit davor passt **nie mehr**
   (E-S5-42, kein Umhash-Pfad — Absicht).

   ```sql
   SELECT id, user_id, device_id, label, last_seen
     FROM devices WHERE api_key_hash LIKE '$2y$%';
   ```

   Jede Zeile hier ist ein Gerät, das mit `401` abgewiesen wird — beim Upload
   wie bei jedem Kopplungsanliegen. Abhilfe: **einmal neu koppeln**, nachdem
   der Sync des Geräts vollständig ist. Die Demo-Geräte der Fixture stehen
   absichtlich in dieser Liste; zu ihnen hat niemand einen Klartextschlüssel.

8. **Sagt das Gerät „Uhr-App aktualisieren"?** Dann sendet es den alten
   Rumpf `{"code": …}` ohne Feld `aktion`, und der Server antwortet `400` mit
   `error: aktion`. Eine Übergangszeit, in der beide Wege gehen, gibt es nicht
   (E-R49-7): Der alte Weg setzte einen im Web erzeugten Code voraus, und den
   gibt es nicht mehr. Hilft nur die neue Client-Fassung.

**Was in der Datenbank steht, wenn alles richtig läuft:** eine Zeile mit
`user_id IS NULL`, solange niemand den Code eingegeben hat; dieselbe Zeile mit
gesetzter `user_id` nach der Eingabe im Web; und **keine** Zeile mehr, sobald
das Gerät Ja gesagt hat — dann steht stattdessen eine in `devices`. Anlegen
und Löschen geschehen in einer Transaktion; einen Zwischenzustand, in dem
beides oder keines von beidem existiert, gibt es nicht.

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

1. `python3 tools/erzeugen/geraetemodelle.py <Gerätedateien>` neu laufen lassen
   und ausrollen. Die Gerätedateien liefert nur der SDK-Manager; ihre
   Bereitstellungsadresse (`CIQ_GERAETE_URL`) steht nicht im Repositorium und
   **muss erfragt werden**.
2. **Seit Web 20.15.0 geschieht der zweite Schritt von selbst** (P5a/AP11,
   E-P5a-21): Der Job `nachaufloesen` merkt am Fingerabdruck der Tabelle,
   dass sie sich geändert hat, und zieht die bestehenden Zeilen in Blöcken von
   200 nach. Betrieb → Status, Zeile **Gerätemodelle** sagt, ob es ansteht
   („steht aus", orange) oder erledigt ist („zuletzt nachgelöst …, N
   nachgezogen, M unbekannt").

   Wer zusehen will, kann weiterhin
   `php tools/erzeugen/geraetemodelle-nachaufloesen.php` fahren — es zeigt Zeile für
   Zeile, was es vorhat, und trägt mit `--schreiben` ein. **Das war bis
   Web 20.14.0 der einzige Weg, und er braucht Shell-Zugriff**; auf einem
   Webspace ohne SSH holten die Geräte ihre Angabe erst bei der nächsten
   Kopplung nach — also womöglich nie. Genau das war der Grund für den Job.

**Nichts geht verloren, solange das offen ist** — die Rohangabe steht in
`devices.geraet_teil`. Zu beachten: Betroffen ist nicht nur der Modellname.
Solange die Tabelle die Teilenummer nicht kennt, steht in `geraet_art` die
**ungeprüfte Selbstauskunft** des Geräts, und die Garmin-App sendet dort fest
`"uhr"` — ein Radcomputer wäre bis zum Nachauflösen als Uhr gezählt.

**CSP scharf schalten (nach Web 20.7.0, einmalig je Installation):** Nach dem
Ausrollen läuft die Content-Security-Policy als **Report-Only** — der Browser
meldet, was er blockiert *hätte*, und führt es trotzdem aus. So gehört es
sich: Eine Richtlinie, die man am ersten Tag scharf schaltet, schaltet man am
zweiten wieder ab.

1. **Zwei Wochen laufen lassen**, im normalen Betrieb.
2. **Betrieb → Servereinstellungen** → Karte **„Sicherheitskopfzeilen"**.
   Darunter stehen die letzten 20 Meldungen mit Richtlinie, Quelle, Seite und
   Zähler. **Leer ist das Ziel.** Steht dort etwas, ist es *vor* dem
   Umschalten zu klären: Jede Zeile ist etwas, das nach dem Scharfschalten
   nicht mehr funktioniert — und zwar ohne Fehlermeldung.
3. Schalter **„CSP scharf schalten"** umlegen.
4. **Danach durchklicken**, nicht nur die Startseite: Karte in allen vier
   Anbietern, Einsatzformular mit Adresssuche, Import (zip.js), Export,
   Druckansicht. Was hier bricht, bricht still.

Zurückschalten geht jederzeit über denselben Schalter. Die Meldungen bleiben
stehen; der Job `aufraeumen` löscht sie nach 30 Tagen.

**HSTS-Bindung einstellen (nach Web 20.7.0):** Dieselbe Karte, Segment
**„HSTS"** — aus / 1 Tag / 7 Tage / 1 Jahr. **Nach dem Update auf 20.7.0
steht sie auf 1 Tag**, auch auf einer Installation, die vorher über
`.htaccess` ein Jahr band: Die HSTS-Zeile ist dort entfallen, weil
`Header always set` überschrieben hätte, was PHP schickt (E-P5a-31, siehe
5c.4). **Wer produktiv läuft und bei seiner Domain bleibt, stellt hier wieder
auf 1 Jahr.** Wer gerade erst aufsetzt, lässt es auf 1 Tag, bis die Adresse
endgültig ist.

**Hinter einem Reverse Proxy (nach Web 20.7.0):** Steht die Anwendung hinter
einem Reverse Proxy, Loadbalancer oder DDoS-Schutz, ist `REMOTE_ADDR` die
Adresse *des Proxys* — der Ratenschutz zählt dann alle NutzerInnen als eine
und sperrt sie **gemeinsam** aus. Abhilfe: in `config.php` den Block `netz`
füllen.

```php
'netz' => [
    'vertrauenswuerdige_proxys' => ['10.0.0.8', '192.168.1.0/24', '2001:db8::/32'],
],
```

Nur wer hier steht, darf `X-Forwarded-For` **und** `X-Forwarded-Proto` setzen.
Leer lassen, wenn die Anwendung direkt am Netz hängt — ein zu weiter Eintrag
lässt jeden seine eigene Adresse behaupten und hebelt den Ratenschutz aus.

**Eine Testanlage kennzeichnen (seit Web 20.38.0, einmalig je Anlage,
E-P5c-05):** In der `config.php` **der Testanlage** — nie auf Produktiv —
unter `app` das Etikett, und daneben den Betreff-Vorsatz der Mails:

```php
'app'  => [ /* … */ 'umgebung' => ['name' => 'Staging', 'farbe' => 'rot'] ],
'mail' => ['betreff_praefix' => '[Staging]'],
```

Danach trägt jede Seite „[Staging]" im Titel, eine rote Kopfleiste und den
Streifen „Staging — Testdaten, kein Echtbetrieb"; die Wartungs- und die
Überlastseite nur den Titel. Notfall-, Schlüssel- und Codeblatt tragen den
Titel und — seit Web 21.1.0 bzw. beim Codeblatt seit 20.42.0 — eine rot
umrandete Umgebungszeile unter dem Blattkopf (E-P5c-50). `farbe` ist eine geschlossene Liste
(`UMGEBUNG_FARBEN` in `server/umgebung_lib.php`, heute nur `rot`). **Nie
abgeleitet** — kein Blick auf Domain oder Zweig; ohne Eintrag verhält sich
die Anlage wie die Produktivanlage. **Prüfen:** Betrieb → Status, Karte
„Server", Zeile „Umgebung" — blau mit „Staging" bzw. „Produktiv"; orange,
wenn der Vorsatz ohne Etikett steht oder die Farbe unbekannt ist. Beide
Schlüssel stehen auskommentiert in `config.example.php`. `config.php` wird
zur Laufzeit nicht geschrieben; die Zeile kommt per FTP hinauf, und bis der
OPcache des Hosters die Datei neu liest, können einige Sekunden vergehen.

**Health-Endpunkt einrichten (seit Web 20.46.0, einmalig je Anlage, P5c/AP6):**

1. Einen Zufallswert erzeugen: `php -r 'echo bin2hex(random_bytes(24));'`.
2. In `config.php` eintragen:
   `'betrieb' => ['health_token' => '<Wert>'],`
3. Im Monitoring die Adresse
   `https://<anlage>/api/health.php?token=<Wert>` eintragen, Abruf **einmal je
   Minute** (mehr als 60 je Minute sperrt die Adresse eine Minute lang).
   Auswerten: HTTP 200 = in Ordnung, 503 = nicht in Ordnung — der Rumpf sagt,
   was (`db`, `migration_ausstehend`, oder `error: maintenance`).
4. Probe: `curl -s https://<anlage>/api/health.php?token=<Wert>` → `"ok":true`.
   Ohne Token oder mit falschem → 403 `token`.

Ausschalten: den Eintrag leeren. Der Endpunkt antwortet dann jedem mit 403,
wie einem falschen Token.

**Code-Update mit DB-Änderung ausrollen:** pushen (Deploy läuft automatisch)
→ als BetreiberIn **Betrieb → Updates** aufrufen → nach dem Lauf muss die
Karte „Ausstehende Updates" leer sein („Alles aktuell"), und in der Karte
„Ausgeführt" muss jede Zeile die Plakette **„erledigt"** tragen. (Bis Web 9.11.1 stand dort ein ✔; seit P3/O11 sagt der
Status ein Wort — `erledigt` blau, `steht aus` orange, **`nicht nötig`
neutral** (seit Web 15.5.2), `blockiert` rot, `Fehler` rot —, weil
Schriftzeichen als Symbol ausgeschlossen sind, E-P3-18.)
Fehlgeschlagene Migrationen werden nicht verbucht und beim nächsten Aufruf
erneut versucht; Folge-Migrationen stoppen bis dahin. **Version in `version.php`
erhöhen** nicht vergessen, sonst sieht der Browser alte Dateien.

**Eine Zeile „nicht nötig" ist kein Fehler und keine Ausnahme** (seit Web
15.5.2, Backlog Nr. 149). Sie heißt: Das Schema stimmt schon, es fehlt nur der
Vermerk im Register — der Fall, den ein Eingriff von Hand hinterlässt (siehe
den Notweg unten). Sie steht unter **Ausstehend**, zählt in dieselbe Zahl wie
Status und Menüzähler, und der Knopf „Ausstehende ausführen" trägt den Vermerk
nach, ohne etwas auszuführen. Der Anzeigestatus heißt `skip` und ist etwas
anderes als der Registerwert `skipped`: Der eine sagt „noch nicht verbucht",
der andere „verbucht, ausgeführt wurde nichts".

**Notweg: Betrieb → Updates verweigert den Zugang, weil eine Migration die
Rolle erst vergibt.** Eingetreten am 06.09.2026 mit
`2026_09_05_rolle_betreiberin` (Backlog Nr. 149 a). Die Lage: Die Seite, die
Migrationen ausführt, beginnt mit `require_betreiberin()`; die Rolle
`betreiberin` legt aber erst diese Migration an. `update.php` lässt eine Admin
durch und leitet mit 302 auf dieselbe Seite — also auf ein 403. Der
Kommandozeilen-Notausgang `php update.php` braucht eine Kommandozeile, und die
gibt es auf einfachem Webspace nicht. **So kommt man heraus:**

1. Im Datenbankwerkzeug des Hosters (phpMyAdmin oder gleichwertig) die
   SQL-Anweisungen der Migration ausführen. Sie stehen im Katalog in
   `migration_lib.php` unter ihrer Kennung — **in ihrer Reihenfolge**, denn
   sie bauen aufeinander auf. Für die genannte waren es zwei:

   ```sql
   ALTER TABLE users MODIFY role ENUM('user','admin','betreiberin')
                     NOT NULL DEFAULT 'user';
   UPDATE users SET role = 'betreiberin' WHERE role = 'admin';
   ```

   Erst `ALTER`, dann `UPDATE`: Liefe das `UPDATE` gegen die alte,
   zweiwertige Spalte, machte MariaDB je nach `sql_mode` einen leeren String
   oder einen Fehler daraus — das erste still.
2. **Den Registervermerk NICHT von Hand setzen.** Neu anmelden (die Rolle
   greift mit der nächsten Sitzung), **Betrieb → Updates** aufrufen — die
   Migration steht jetzt als **„nicht nötig"** da — und **„Ausstehende
   ausführen"** drücken. Die Anwendung schreibt den Vermerk selbst, mit dem
   richtigen Status `skipped`, und der Zähler an Updates und Status
   verschwindet. Ein von Hand gesetzter Eintrag hätte denselben Effekt und
   keine Prüfung dahinter.

> **Regel daraus: Eine Migration, die Rechte einführt, muss ohne diese Rechte
> ausführbar sein.** S8 hatte das Aussperren der **letzten** BetreiberIn
> bedacht (R75) und das Fehlen der **ersten** nicht. Wer einen Wächter vor
> eine Seite zieht, prüft den **Erstlauf** mit — den Zustand *vor* der
> Migration, nicht nur den danach. Die nächste Rollenmigration ist die
> Support-Rolle in P5 (R38); dort gilt die Regel, und sie gehört ins
> Bedrohungsmodell (P6, R69).
>
> **Für die Support-Rolle (Web 20.41.0) ist die Regel erfüllt, und
> nachgemessen:** Die Migration hängt einen Wert an, den zum Ausführen
> niemand braucht — sie läuft über Betrieb → Updates mit der vorhandenen
> BetreiberIn. Nachgestellt in P5c/AP4 mit dem neuen Code auf dem alten
> Schema: Anmeldung 200, `betrieb_updates.php` 200 mit der Migration in der
> Liste, danach `update.php` durch.
>
> **Für den Zweitfaktor (Web 20.42.0) ebenso:** Das Einrichtungstor schweigt,
> solange die Spalten fehlen und solange die Wartung an ist — und nach dem
> Deploy ist die Wartung an, bis jemand `update.php` gefahren hat. Die
> BetreiberIn kommt also ohne Zweitfaktor an Betrieb → Updates.

**Notweg: Die einzige BetreiberIn hat Handy und Codes verloren** (seit Web
20.42.0, E-P5c-42). **Seit Web 20.45.0 zuerst den Rückweg nehmen** (Konzept
RW, E-RW-08): Mit Passwort und Notfallblatt setzt sie ihn am Code-Schritt
selbst zurück („Gerät und Codes verloren?") und landet im Einrichtungstor —
mit Protokoll und Mail. Das geht, wenn Betrieb → Status „Rückweg-Prüfung"
blau zeigt und das Konto ein Paar hat (die Karte „Zweitfaktor" im Profil sagt
„eingerichtet"). **Fehlt eines davon oder das Notfallblatt**, kann nur eine
**andere** BetreiberIn zurücksetzen — gibt es keine, führt kein Weg über die
Oberfläche hinein. Im Datenbankwerkzeug des Hosters, mit der Kennung des
Kontos:

```sql
UPDATE users SET totp_geheimnis = NULL, totp_seit = NULL, totp_schritt = NULL
 WHERE id = <Kennung>;
DELETE FROM totp_codes WHERE user_id = <Kennung>;
```

Danach mit dem Passwort anmelden; das Einrichtungstor verlangt sofort einen
neuen Zweitfaktor. **Im Protokoll steht dieser Weg nicht** — er geht an der
Anwendung vorbei. Wer ihn benutzt, trägt es in die Betriebsakte ein. Der
Fall, dass die einzige BetreiberIn **auch** den Datenbankzugang verloren hat,
ist Backlog Nr. 249 (Schritt 18) — seit RW-03 nur noch, wenn sie zugleich ihr
Notfallblatt verloren hat. Die Bus-Faktor-Zeile auf Betrieb → Status
steht genau deshalb orange, bis es eine zweite BetreiberIn gibt.

**Notweg: Nach einem Wiederanlauf mit anderem Serverschlüssel** lässt sich
kein Zweitfaktor-Geheimnis mehr öffnen (es ist mit dem alten versiegelt). Die
Anmeldung nimmt dann nur noch Wiederherstellungscodes — sie hängen nicht am
Schlüssel (E-P5c-42). Wer keine mehr hat, nimmt den Rückweg mit dem
Notfallblatt — er hängt ebenfalls nicht am Serverschlüssel —, sonst setzt
eine BetreiberIn zurück, die einzige BetreiberIn über den SQL-Weg oben.

**Die Zeile „Rückweg-Prüfung" auf Betrieb → Status steht orange** (seit Web
20.43.0, Konzept RW): Der Selbsttest hat den festen Vektor nicht bestanden,
und der Rückweg mit dem Wiederherstellungsschlüssel ist abgeschaltet — die
Anmeldung bietet ihn nicht an; Codes und Verwaltung gehen weiter. Die
Meldung im Reiter System nennt den Weg (`openssl` oder `php`). **Erst neu
testen lassen:** Das Ergebnis steht in `app_state` unter `rw_selbsttest` und
gilt je Fassung und Plattform; wer es löscht, lässt beim nächsten Aufruf
der Statusseite neu testen:

```sql
DELETE FROM app_state WHERE k = 'rw_selbsttest';
```

Bleibt es orange, ist phpseclib unter `server/vendor/phpseclib3/` beschädigt
oder unvollständig ausgeliefert — `sha256sum -c phpseclib3.sha256` im Ordner
`server/vendor` (`docs/Lizenzen.md` 3a) sagt, welche Datei.

**Neue Zusatzfelder für Einsätze:** 1) Migration in `migration_lib.php`
(`migrationen_katalog()`) ergänzen
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
6. **Anmelden** — mit einem verwaltenden Konto aus dem Backup; die
   Passwörter sind dieselben wie vorher.
7. **Betrieb → Updates aufrufen** und den Migrationslauf ausführen.
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
| „Der Server-Anteil der Verschlüsselung fehlt oder ist nicht der, mit dem die Hüllen gebaut wurden" (seit Web 19.7.0) | **der Regelfall nach Schritt 5**, siehe unten | den `kdf_anteil` aus dem Wiederanlaufpaket eintragen |

**Nach Schritt 5 steht der Server-Anteil fast immer auf „abweichend" — und
das ist richtig so (seit Web 19.7.0, S10).** Das Komplettbackup enthält
**jede** Tabelle, also auch `app_state` mit der Kennung des Anteils, mit dem
die Hüllen gebaut wurden. Es enthält **nicht** `config.php`; die hat Schritt 3
frisch angelegt, und `install.php` hat darin einen **neuen**, zufälligen
`kdf_anteil` gewürfelt. Wert und Marke gehen damit auseinander.

Genau dafür gibt es die Marke. Ohne sie würde die Installation den neuen
Anteil für den richtigen halten, und jede NutzerIn bekäme beim Anmelden
„Passwort falsch" — für Daten, die vollständig da sind. Mit ihr sagt die
Anwendung, was Sache ist, und nennt die **erwartete Kennung**. Der Griff
danach: den `kdf_anteil` aus dem Wiederanlaufpaket über die Karte *Nachtragen
vom Blatt* eintragen (die Kennung wird vor dem Schreiben verglichen). Ist er
unwiederbringlich weg, bleibt der Neuanfang — dann setzt jede NutzerIn ihr
Passwort über den Wiederherstellungsschlüssel neu; **die Daten selbst sind
davon nicht betroffen**.

**Das Wiederanlaufpaket (seit Web 12.1.0, E-S2-21; seit Web 19.7.0 mit einem
vierten Stück).** Getrennt von der Anwendung aufbewahren — auf einem anderen
Rechner, nicht im selben Backup:

1. **`server/config.php`.** Sie steht in `.gitignore` **und** in der
   Ausnahmeliste des Deploys; es gibt sie also nur auf dem Server.
2. **Der Serverschlüssel** darin (`'server_key' => '…'`, 64 Hexzeichen).
   Er versiegelt die Zugangsdaten der Backup-Ziele und — ab AP8 — das
   Komplettbackup. **Ohne ihn** sind die Zugangsdaten der Ziele neu
   einzutragen (verschmerzbar) und ein versiegeltes Komplettbackup **nicht
   mehr zu öffnen** (nicht verschmerzbar).
3. **Der Server-Anteil** darin (`'kdf_anteil' => '…'`, 64 Hexzeichen; seit
   Web 19.7.0, S10). Er geht in den Datenschlüssel **jedes Kontos** ein.
   **Ohne ihn** lässt sich keine `edka1:`-Hülle mehr öffnen — und zwar für
   alle gleichzeitig. **Es ist trotzdem kein Datenverlust:** `pat_wrap_rc`
   hängt nicht am Anteil, jede NutzerIn kommt über den
   Wiederherstellungsschlüssel wieder herein und setzt dabei ihr Passwort
   neu. Aus dem Verlust wird damit ein Vorgang für alle statt einer
   Katastrophe — aber ein Vorgang, den niemand will.
4. **Der Zugang zum Backup-Ziel** — Rechnername, Nutzer, Passwort bzw.
   privater Schlüssel. Er steht in der Datenbank, aber versiegelt; wer nur
   den Dump hat und den Serverschlüssel nicht, kommt an die Backups
   dort nicht heran. Das ist der Sinn der Sache und zugleich der Grund,
   ihn zusätzlich von Hand zu notieren.

> **`config.php` ist seit S10 Schlüsselträger der ganzen Installation.** Bis
> Web 19.6.0 kostete ihr Verlust die Betriebsgeheimnisse; seither kostet er
> zusätzlich jeder NutzerIn einen Passwort-Reset. Das Schlüsselblatt
> (Betrieb → Servereinstellungen; kommt mit S10/AP3) druckt beide Geheimnisse
> mit Kennung — **zwei Ausdrucke, zwei Orte**. Es ist Pflicht, nicht
> Empfehlung.

**Probe-Wiederherstellung** ist ein Prüfpunkt und keine Formalie: einmal je
Halbjahr ein Paket vom Ziel holen und in ein Wegwerfkonto einspielen. Ein
Backup, das nie zurückgespielt wurde, ist eine Vermutung.

**Serverschlüssel nachtragen (bestehende Installation):** Adminbereich →
**Backup-Ziele**. Ist `config.php` beschreibbar, genügt der Knopf; sonst
zeigt die Seite die fertige Zeile zum Einfügen — **genau eine** eintragen, bei
jedem Neuladen steht dort eine andere. Danach die Zeile ins Wiederanlaufpaket.

**Backup-Ziel einrichten:** Adminbereich → **Backup-Ziele** → *Ziel
anlegen*. **SFTP wählen, wenn das Ziel es anbietet** — es ist von den
beiden das einzige, das den Server am Hostschlüssel wiedererkennt (`ftp` ist
seit Web 20.2.0 abgeschafft, 4.97c). Danach
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

**„Der Server-Anteil der Verschlüsselung fehlt oder ist nicht der, mit dem die
Hüllen gebaut wurden" (seit Web 19.7.0, S10).** Das ist die Lage *abweichend*:
`config.php` trägt einen anderen `kdf_anteil` als den, dessen Kennung in
`app_state.kdf_anteil_kennung` steht — oder gar keinen. Die Anwendung läuft
weiter, **es ist kein Wartungsmodus**; nur der Anteil wird nicht ausgeliefert,
und Konten mit `edka1:`-Hülle kommen nicht an ihre geschützten Angaben.

*Reihenfolge:*
1. Betrieb → **Status**, Zeile „Server-Anteil". Sie nennt die **erwartete**
   Kennung. Betrieb → Servereinstellungen zeigt daneben die vorhandene.
2. Den richtigen Wert aus dem **Schlüsselblatt** oder dem Wiederanlaufpaket
   nachtragen (Karte „Schlüssel des Servers", *Nachtragen vom Blatt*). Der
   Server rechnet die Kennung des eingegebenen Werts und schreibt **nur bei
   Übereinstimmung**; bei Abweichung nennt die Meldung beide Kennungen und
   ändert nichts.
3. Ist der Wert **unwiederbringlich** weg, bleibt der Neuanfang: einen
   frischen Anteil erzeugen. Danach setzt jede NutzerIn ihr Passwort über den
   **Wiederherstellungsschlüssel** neu — kein Datenverlust, aber ein Vorgang
   für alle.

*Was ausdrücklich **nicht** passiert:* „Passwort falsch". Die Meldung
unterscheidet gegenüber der NutzerIn nicht zwischen „fehlt" und „anderer
Wert" (dieselbe Linie wie `sk_oeffnen()`); den Unterschied sieht die
BetreiberIn auf Status und Karte. Konten mit `edk1:`-Hülle — darunter das
Demo-Konto — sind von alledem nicht betroffen und melden sich weiter an.

**Zeile „Schlüsselableitung" auf Betrieb → Status (seit Web 5.0.1; bis
Web 15.0.0 auf der Wartungsseite, in 15.1.0 und 15.2.0 vorübergehend nicht
sichtbar):** Erscheint
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
Installer. **Seit Nr. 214 gehört `install.php` nicht mehr zur Auslieferung** —
sie steht in der Ausnahmeliste des FTPS-Schritts. Eine leere Anlage lässt
sich damit **nicht allein über die Kette** einrichten: Die Datei
`server/install.php` muss **einmal von Hand** hinauf, danach wird eingerichtet
und danach wird sie wieder gelöscht — so, wie es der letzte Satz dieses
Absatzes seit jeher verlangt. Stufe 2 der Kette erkennt genau diesen Fall und
sagt es (Abschnitt 6.3). Der Installer fragt **kein** Passwort mehr ab; er legt den
Zugang ohne Passwort an und zeigt auf der Erfolgsseite einen 24 h gültigen
Einmal-Link auf `pw_handling.php`, über den Passwort und
Wiederherstellungsschlüssel im Browser entstehen. Nach Erfolg sperrt
`install.lock`; `install.php` danach löschen.

**Das erste Konto ist die BetreiberIn** (seit Web 15.0.0, R75). Wer eine
Installation einrichtet, ist die, die sie betreibt — und in diesem Augenblick
die Einzige. Legte der Installer ein Admin-Konto an, stünde die frische
Installation ohne Zugang zu ihrem eigenen Betriebsbereich da: Serverschlüssel,
Wartungsmodus, Migrationen, Speichergrenze. Der Weg zurück führte über die
Datenbank.

**Vier Rollen, eine Hierarchie** (R75; Support seit Web 20.41.0, P5c/AP4,
R38): `user` ⊂ `support` ⊂ `admin` ⊂ `betreiberin` — der Support als
schmaler Ausschnitt der Verwaltung, nicht als eigene Stufe mit eigenen
Rechten. Die Prüfung steht an zwei Stellen und nirgends sonst —
`rolle_darf_verwalten()`, `rolle_darf_support()`, `rolle_ist_support()` und
`rolle_ist_betreiberin()` in `db.php` als reine Prädikate (auch ohne
Sitzung benutzbar: `login.php` prüft vor dem Anmelden, ob der Wartungsmodus
jemanden durchlässt, `rechtstext_seite.php` liest eine fremde Zeile),
`ist_admin()`, `darf_support()`, `ist_support()`, `ist_betreiberin()`,
`require_admin()`, `require_support()`, `require_betreiberin()` und
`handlung_erlaubt()` in `auth_guard.php` für die angemeldete Sitzung.
`ist_admin()` heißt „darf verwalten" und ist für eine BetreiberIn ebenfalls
wahr; `darf_support()` ist für Support, Admin und BetreiberIn wahr.

**Der Support fragt je Handlung, nicht je Seite** (E-P5c-14, -40).
`admin_users.php`, `admin_user.php` und `admin_protokoll.php` stehen hinter
`require_support()`; jede POST-Handlung auf den beiden Kontoseiten ruft
`handlung_erlaubt($action, […])` **vor** `csrf_check()` (E-P5c-85) — die
Protokollseite fragt stattdessen einmal, ob die BetreiberIn fragt
(`admin_protokoll.php`), mit derselben Wirkung: Rolle vor Token — die Liste nennt, was der Support
darf: auf der Kontoseite `pw_reset`, `verifikation`, `device_aus`, auf der
Liste nichts. Die Kontoseite eines Kontos mit eigenen Rechten ist für ihn
403 **vor** jeder Handlung, und die Liste zeigt ihm nur
`rollen_ohne_rechte_sql()`. Den Setz-Link sieht er nie, auch nicht, wenn die
Mail nicht hinausging. `device_aus` schaltet nur 1 → 0; `device_toggle`
bleibt der Verwaltung. Die Zellen stehen in der Berechtigungsmatrix (4.99p). Zwei Zusagen sitzen serverseitig und sind nicht umgehbar: Nur eine
BetreiberIn vergibt oder entzieht die Rolle, und das **letzte**
BetreiberIn-Konto lässt sich weder zurückstufen noch löschen
(`ist_letzte_betreiberin()`).

**Ein Komplett-Backup aus der Zeit vor 15.0.0** bringt beim Wiederherstellen
das alte zweiwertige ENUM und lauter `admin` zurück. Das sperrt niemanden aus
— ein Admin darf verwalten, und der CLI-Notausgang `php update.php` fragt
ohnehin nicht nach einer Rolle —, aber der
Migrationslauf danach ist Pflicht und stellt die Rollen wieder her. Der
Wiederherstellungsweg nennt ihn ohnehin als zweiten Schritt.

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

## Konto-Backups (A8, seit Web 5.9.0; Name seit Web 15.2.0, E-S8-06)

**Zweck.** Die Verwaltung soll Konten sichern und wiederherstellen können, ohne
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

**`sicherungen/` steht in der `exclude`-Liste des FTPS-Schritts von `.github/workflows/ausliefern-lauf.yml`** (bis Kette II/AP5: zweimal in `auslieferung.yml`; bis Web 20.3.0: `deploy.yml`).**
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
Formular, ein Speichern), Geräte, die Konto-Backups **dieses** Kontos und die
Löschung als Gefahrenzone. `admin_sicherungen.php` behält die Regeln — und
seit Web 9.10.0 nur noch sie (Abschnitt „Backups: was auf welcher Seite
steht").

**Der reservierte Platz für das Abonnement ist mit Web 15.2.0 fort**
(B-S8-11). Er stand seit Web 9.9.0 als Karte „Abonnement · ab P5" auf jeder
Kontoseite und wiederholte dort eine Zusage, die niemand terminiert hat. R33
steht im Rahmenplan; die Karte entsteht mit ihrem Inhalt und nicht davor.

**Die Freigabe hat seit Web 15.2.0 eine Zustandszeile** (B-S8-09). Sie war
vorher ein Zustand ohne Anzeige: Ein Paket dieses Kontos stand für jemand
anderen offen, sichtbar nur als Plakette an einer Zeile und als Eintrag im
Aktionsmenü. Jetzt sagt eine Meldung in der Karte, für wen, seit wann, welches
Paket, was die andere Seite noch tun muss — mit „Widerrufen". Ist das
Zielkonto inzwischen gelöscht, sagt sie auch das: Die Freigabe läuft dann ins
Leere.

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

### Die wöchentliche Erinnerung an die Verwaltung (seit Web 9.10.0)

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
   der Wartung (`update.php`; zieht in S8/AP3 auf Verwaltung → Installation).
   `logo_standard()` liest ihn je Anfrage einmal
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
`konfig('app.logo_path')` gewinnt nur noch, wenn dort eine **fremde** Datei
steht (F-P3-AN). `pw_handling.php` lädt dafür `session_lib.php`.

**Der Platzhalterhinweis** an der Logo-Karte fragt die Datei, nicht eine
Zahl im Code: `logo_platzhalter_liegt()` liest die ersten 400 Byte von
`gen-em_logo_fahrzeug.svg` und `…_weiss.svg` und sucht das Wort „PLATZHALTER"
im Kopfkommentar. Er verschwindet damit von selbst, sobald die echten Dateien
liegen — sie ersetzen den Platzhalter 1:1 (gleicher Name, gleicher `viewBox`).


### Installation und Rechtstexte (R32, seit Web 9.11.0; Installation seit Web
15.2.0, Rechtstexte wieder eigene Seite seit Web 21.1.0)

**Die Anwendung liefert keinen Rechtstext mit.** Was darin steht, ist Sache der
BetreiberIn; die Anwendung stellt die öffentlichen Seiten (seit Web 20.19.0
vier), einen Editor und die Verweise in jeder Fußzeile. Der Leerzustand ist
die Auslieferung.

**Aus „Rechtstexte" ist mit Web 15.2.0 „Installation" geworden** (E-S8-05).
Impressum, Datenschutz und das **Logo der Installation** beantworten dieselbe
Frage — was zeigt diese Anlage Menschen, die noch nicht angemeldet sind? Das
Logo lag bis dahin auf der Wartungsseite, und das war ein Befund (B-S8-10):
Der Logo-Standard ist Gestaltung, keine Wartung. Zwei Formulare, zwei
Speicherwege: Die beiden Texte teilen sich die Speichern-Leiste, das Logo hat
einen eigenen Knopf — es wirkt sofort und soll nicht auf einen halbfertigen
Rechtstext warten.

**Seit Web 21.1.0 sind die Rechtstexte wieder eine eigene Seite**
(`admin_rechtstexte.php`, P5c/AP9, E-P5c-28, Backlog Nr. 121). Auf
„Installation" standen die vier Texte untereinander in der rechten Spalte,
jeder mit einer Vorschau des **gespeicherten** Stands darunter — bei 720 px
Fensterhöhe tippte man oben und sah unten nichts. Jetzt: ein Reiter je Text
(`ui_reiter()`, beschriftet aus `RT_TEXTE`), ein Formular je Reiter, ab 1200 px
Feld links und Vorschau rechts. Ein Reiterwechsel mit ungespeichertem Text
fragt über `data-cancel-form` nach (`assets/forms.js`). Installation behält
Name, Adressen und Logo, einspaltig.

**Die Vorschau beim Tippen rendert auf dem Server.**
`assets/rechtstext_vorschau.js` schickt Text und Standdatum 0,4 s nach dem
letzten Tastendruck an `api/rechtstext_vorschau.php`, und der gibt zurück, was
`rt_html()` und `rt_stand_markup()` daraus machen. Ein zweiter Renderer im
Browser läse eines Tages anders als der, der die öffentliche Seite baut. Der
Endpunkt hält die Reihenfolge des Hauses — Methode, **Rolle**
(`require_admin()`: Admin und BetreiberIn), Token, Ratenschutz, Rumpf — und
speichert nichts. Der Rumpf darf viermal `RT_MAX_ZEICHEN` Bytes groß sein
(UTF-8 plus JSON-Maskierung); ein zu langer **Text** ist ein Befund von
`rt_pruefen()` (422 mit Satz), ein zu großer **Rumpf** kein Text mehr (413).
**Topf `rt_vorschau`**: 120 Abrufe in 5 Minuten je Konto (nur das Konto,
nicht die Adresse — F-P5c-155), dann 60 s Pause —
er fängt ein Skript ab, das den Renderer als Rechenknecht benutzt, nicht
jemanden, der einen langen Text schreibt. Laufende Abrufe verwirft das Skript
über eine laufende Nummer: Nur die Antwort auf die jüngste Anfrage wird
gezeigt.

**Das Token für `EdApi` ohne das Krypto-Rüstzeug** (E-P5c-130). `EdApi` liest
die Konstante `CSRF`, und die schrieb bis dahin nur `ui_krypto_bootstrap()` —
zusammen mit Salz, Rundenzahl und dem Server-Anteil des Kontos. Die Seite
braucht nichts davon; `ui_csrf_bootstrap()` schreibt nur das Token. Beide
holen die Zeile aus `ui_csrf_zeile()`, die sie **einmal** je Seitenaufbau
liefert: Ein zweites `const CSRF` wäre ein SyntaxError im zweiten Skript. Der
erste Browserlauf hatte genau diese Lücke — der Endpunkt antwortete 403, die
Plakette stand auf „nicht aktuell" (F-P5c-154); der Bedienweg
`admin-rechtstexte-vorschau` fällt ohne den Aufruf rot aus.

| Datei | Aufgabe |
|---|---|
| `rechtstexte_lib.php` | Ablage (`rt_lesen`, `rt_speichern`, `rt_pruefen`) und der Renderer `rt_html()` |
| `rechtstext_seite.php` | Die öffentliche Seite — beide Dokumente teilen sie sich |
| `impressum.php`, `datenschutz.php`, `nutzungsbedingungen.php`, `avv.php` | Zwei Zeilen: Schlüssel setzen, Seite laden |
| `admin_rechtstexte.php` | Editor, ein Reiter je Text, Vorschau daneben (seit Web 21.1.0; von 15.2.0 bis 21.0.0 eine Weiterleitung auf `admin_installation.php`) |
| `api/rechtstext_vorschau.php` | Vorschau beim Tippen: `rt_html()` auf dem Server, speichert nichts, Topf `rt_vorschau` |
| `assets/rechtstext_vorschau.js` | Entprellung, jüngste Antwort, Plakette, anteiliges Mitrollen |
| `admin_installation.php` | Name, Adressen, Logo der Installation (S8/AP3, E-S8-05); bis Web 21.0.0 auch der Editor der Rechtstexte |
| `tools/proben/rechtstexte/` | Angriffsprobe für `rt_html()` |

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
Sie ruft stattdessen `sitzung_starten('lesend')` (das nimmt ein vorhandenes
Cookie an und meldet niemanden an) und liest die Rolle **aus der Datenbank**,
nicht aus der Sitzung — dieselbe Regel wie im Guard (M1-05): Eine zurückgenommene
Adminrolle würde sonst bis zur nächsten Anmeldung weitergelten.

> **Sie legt dabei seit Web 20.27.0 keine Sitzung mehr an** (Schritt 15 AP2,
> F-ZE-2). Die Art `lesend` startet **nur, wenn ein Sitzungscookie da ist** —
> und das gilt für alle drei Seiten dieser Bauart: `rechtstext_seite.php`,
> `doku_seite.php` (Handbuch, „Was ist NAdoku") und `notfallblatt.php`.
>
> **Vorher bekam jeder Besucher eine Sitzung**, auch jeder Bot, und seit
> Web 20.26.0 landete jede davon als Datei in `server/.sitzungen/`. Das
> Konzept der Sitzungsablage hatte behauptet, die drei Seiten prüfen auf das
> Cookie; nachgemessen am 20.09.2026 taten sie es nicht. **Für Angemeldete
> ändert sich nichts** — ihr Browser schickt das Cookie, die Sitzung startet,
> der angemeldete Kopf sieht aus wie vorher.
>
> **Wer Dateien in `.sitzungen/` zählt, rechnet das ein:** Die Zahl auf der
> Statusseite fällt seither, ohne dass jemand etwas gelöscht hätte.

Ohne `config.php` leitet sie auf `install.php` um, wie `login.php` es tut. Ein
Impressum ist das erste, was jemand auf einer frischen Installation aufruft — es
darf keinen weißen Fehler zeigen.

#### Grenzen

- **Die Content-Security-Policy ist die zweite Verteidigungslinie** hinter dem
  Renderer (seit Web 20.7.0, 5c). Hier stand bis Web 21.1.0 „keine
  Content-Security-Policy".
- **Kein Versionsstand der Texte.** Wer den Text überschreibt, überschreibt ihn;
  eine Historie gibt es nicht. Für ein Dokument, dessen alte Fassung
  rechtlich zählen kann, ist das eine bewusst offene Stelle.
- **Die Vorschau zeigt seit Web 21.1.0 das Getippte** (Endpunkt oben); ohne
  JavaScript den gespeicherten Stand.
