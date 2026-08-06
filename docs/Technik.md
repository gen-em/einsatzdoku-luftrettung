# Einsatzdoku — Technische Dokumentation

*Stand: 26.07.2026 · Bedienung: `Handbuch.md` · Schnittstelle: `JSON-Vertrag.md` ·
Historie: `CHANGELOG.md`.*

## 1. Architekturüberblick

```
┌─────────────────┐  HTTPS POST /ingest.php   ┌──────────────────────────┐
│ Garmin Fenix 6  │  JSON (JSON-Vertrag)      │  Webspace                │
│ Connect-IQ-App  │ ────────────────────────► │  PHP ≥ 8.1  + MySQL      │
│ (Monkey C)      │  X-Device-Id / X-Api-Key  │                          │
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
hems/
├── docs/                  Handbuch, Technik, Changelog, Backlog, JSON-Vertrag,
│                          Backup-Format, Export-Format,
│                          Geraete-Eingabe (gemessenes Eingabeverhalten je Uhr),
│                          Uhr-Layout (Layoutregeln der Uhr-Oberflächen)
├── server/                komplette Web-App (wird per FTPS deployt)
│   ├── version.php        WEB_VERSION (einzige Stelle für die Versionsnummer)
│   ├── db.php             PDO, Helfer (e/asset/favicon_tags/logo_src/fmt_local/local_to_utc), Aufräumjob
│   ├── ui.php             Kopf-/Seitenleisten, Fußzeile
│   ├── auth_guard.php     Session/CSRF/Rollen
│   ├── auth_salt.php      KDF-Salt (mit Pseudo-Salt gegen User-Enumeration)
│   ├── login/logout/reset_request.php   Auth-Flows
│   ├── pw_handling.php    Passwortvergabe über Einmal-Link: Erstvergabe (erzeugt
│   │                      Inhalts- + Wiederherstellungsschlüssel) und Reset
│   ├── index.php          Tagesübersicht (Karte + Tabelle)
│   ├── einsatz.php        Einsatzansicht · einsatz_form.php Nachtragen/Bearbeiten
│   ├── zeitraum.php       Jahres-/Monatsübersicht (Karte, Statistik, Tabelle)
│   ├── suche.php          Suche über den gesamten Bestand (filtert im Browser, s. u.)
│   ├── mission_fields.php Zentraler Feldkatalog der Zusatzfelder
│   ├── einstellungen.php  Profil/Standortdaten/Backup/Geräte
│   ├── import.php         Import/Export (eigene Seite, erscheint als Eintrag
│   │                      der Einstellungs-Leiste)
│   ├── admin_users.php + admin_user.php  Nutzerverwaltung (Liste · Detail) · geraete.php (Weiterleitung)
│   ├── admin_stammdaten.php  Zentrale (globale) Stammdaten aller sechs Typen
│   ├── flugtag_neu.php    Flugtag von Hand anlegen
│   ├── einsatz_loeschen.php · flugtag_loeschen.php · papierkorb.php  Löschen mit Vorschau
│   ├── ingest.php         Uhr-/Fremdquellen-Endpunkt (Auth, Idempotenz)
│   ├── pair.php           Uhr-Kopplung per Code
│   ├── backup_lib.php     Backup-Serialisierung · trash_lib.php Papierkorb-Logik
│   ├── install.php        Serverinstallation · update.php Migrations-Runner
│   ├── smtp.php           SMTPS-Versand
│   ├── api/               day.php · mission.php · range.php · suchindex.php · backup_data.php · backup_restore.php ·
│   │                      import_commit.php (Abgleich + Übernahme des Imports) ·
│   │                      export_data.php (nur lesend, Rohdaten für den Export)
│   ├── assets/            style.css, crypto.js (WebCrypto), unlock.js (Entsperrdialog, s. u.),
│   │                      patient.js, daylist.js, confirm.js,
│   │                      missiontable.js (gemeinsame Einsatztabelle, s. u.),
│   │                      map_fullscreen.js + map_layers.js (gemeinsame Leaflet-Controls, s. u.),
│   │                      import.js (Pipeline) + import_profiles.js (Formate) + import_ui.js (Bedienung),
│   │                      export.js (alle drei Exportprofile, Aufbau im Browser)
│   │   └── vendor/        xlsx.full.min.js — SheetJS Community Edition 0.18.5, Apache-2.0 ·
│   │                      zipjs.min.js — zip.js 2.8.34, BSD-3-Clause (ZIP + AES-256);
│   │                      beide lokal vendoriert (kein CDN), Herkunft und SHA-256 im Dateikopf
│   │   └── images/        Logo als SVG (farbig + weiss), favicon.png
│   ├── favicon.ico        Browser-Symbol im Wurzelverzeichnis
│   ├── schema.sql         Voll-Schema für Neuinstallationen
│   └── .htaccess          HTTPS-Zwang, Dateisperren, Sicherheits-Kopfzeilen
├── watch/                 Connect-IQ-Projekt (Monkey C)
│   ├── manifest.xml, monkey.jungle
│   ├── resources/         Vorgabe für alle Geräte
│   ├── resources-<gerät>/ geräteabhängige Überschreibungen (Launcher-Icon)
│   └── source/            s. Abschnitt 5
├── tools/                 Werkzeuge, werden nicht ausgeliefert
│   └── eingabe-probe/     Connect-IQ-Probe zum Ausmessen des Eingabe-
│                          verhaltens neuer Zielgeräte (s. Abschnitt 5.2)
└── .github/workflows/deploy.yml   FTPS-Deploy (nur server/, exkl. config)
```

## 3. Datenmodell (MySQL)

| Tabelle | Zweck / Besonderheiten |
|---|---|
| `users` | Login (E-Mail = Username), Rolle `user`/`admin`; Löschen kaskadiert alles; **Browser-Schlüsselableitung** (`kdf_salt`) und **E2E-Schlüssel-Hüllen** `pat_wrap_pw`/`pat_wrap_rc` (Inhaltsschlüssel passwort- bzw. wiederherstellungsverpackt). `password_hash` ist NULL, solange das Passwort noch nicht gesetzt wurde — ein solches Konto kann sich nicht anmelden |
| `password_resets` | Token-Hashes (sha256); 1 h bei „Passwort vergessen“, 24 h bei Neuanlage und Installation; Aufräumjob entsorgt Altbestand |
| `devices` | Upload-Zugang je Gerät: `device_id` (öffentlich) + `api_key_hash`; **`active`-Flag** (deaktivieren statt löschen); virtuelle Geräte `manual-<userId>` für Handeinträge (dauerhaft inaktiv, aus Listen gefiltert) |
| `missions` | Einsatz; `UNIQUE(device_id, client_ref)` = Idempotenz-Anker; `day` = Flugtag; **`manual`-Marker** — ausschließlich Schutz vor Uhr-Überschreiben, NICHT „von Hand angelegt"; **`origin`** (`watch`/`manual`/`import`) = Herkunft, wird beim Anlegen gesetzt und nie wieder geändert; **`edited`** = wurde nach dem Anlegen verändert; `deleted_at`/`deleted_with_day` (Papierkorb); Zusatzfelder lt. `mission_fields.php`; **`site_ele_m`** = berechnete Einsatzort-Höhe (kein Formularfeld, siehe `site_elevation_lib.php`); **`crew_override` + `crew_p1`…`crew_other`** = abweichende Besatzung je Einsatz (NULL, solange keine Abweichung — die Tagescrew in `days` bleibt die einzige Wahrheit, siehe Abschnitt 4); **`pat_blob`** = E2E-Chiffretext (Name, Geburtsdatum, Alter, Diagnose, Einsatzort, seit Web 2.9.0 auch die Einsatznummer, seit Web 3.3.0 auch die Beschreibung des Einsatzortes — Klartext-Ortsspalten existieren seit der Pflicht-Migration nicht mehr) |
| `mission_phases` | Phasen-Zeitstempel (2–10, Mehrfach-Einträge erlaubt) inkl. Position |
| `resus_sessions` / `resus_events` | Reanimationen: **mehrere Sitzungen je Einsatz**, Ereignisse typisiert |
| `rest_segments` | Ruhe-Track-Segmente (gleiches Idempotenz-Schema wie Einsätze) |
| `track_points` | GPS-Punkte für Einsätze **und** Segmente; PK `(owner_type, owner_id, seq)`; bewusst ohne FK (polymorph) → Aufräumjob entfernt Waisen |
| `bases` / `aircraft` / `crew_presets` | Stammdaten: Standorte, Maschinen (mit Rollen-Häkchen), Besatzungsnamen je Rolle; `user_id` NULL = **zentral** (vom Admin gepflegt, für alle NutzerInnen), sonst persönlich |
| `resources` | Vorbelegung „Andere Rettungsmittel" ; `user_id` NULL = zentral, sonst persönlich |
| `mission_resources` | Rettungsmittel-Zuordnung je Einsatz (eigene Zeilen, einzeln entfernbar) |
| `bw_units` | Bergwacht-Bereitschaften; `user_id` NULL = zentral, sonst persönlich |
| `transport_dests` | Vorbelegung „Transportziel" (Datalist-Vorschläge, `missions.transport_dest` bleibt Freitext ohne FK); `user_id` NULL = zentral, sonst persönlich |
| `user_defaults` | Nutzerbezogene Standard-Vorbelegung für Flugtage (`kind` in `base`/`aircraft`, `item_id` verweist auf `bases.id` bzw. `aircraft.id`, persönlich oder zentral); ersetzt die Alt-Spalten `bases.is_default`/`aircraft.is_default` (bleiben nur wegen Alt-Backup-Import im Schema) |
| `days` | Flugtag-Metadaten; **Verknüpfung über natürlichen Schlüssel `(user_id, day)`**, entsteht lazy beim ersten Speichern |
| `pair_codes` | Kopplungscodes für die Uhr (5 Zeichen, 60 min, einmalig; Aufräumjob) |
| `deleted_refs` | Sperrliste gelöschter `client_ref`s (90 Tage) gegen Wieder-Upload durch die Uhr |
| `app_state` | Schlüssel/Wert (z. B. `last_cleanup`, `salt_secret`) |
| `schema_migrations` | Buchführung des Migrations-Runners |

Skalierung: ~2.000–2.500 Punkte je Einsatz; Indizes `(user_id, day)` und der
Punkte-PK tragen das auf Jahre problemlos (~1 Mio. Punkte/Jahr).

## 4. Zentrale Abläufe

**Upload & Idempotenz** (Details: `JSON-Vertrag.md`): Die Uhr sendet je
Einsatz/Segment eine `client_ref` und Punkte ab `seq_from`; der Server
antwortet mit `next_seq` (erste noch fehlende Sequenz). Wiederholungen sind
unschädlich (`INSERT IGNORE` auf den Punkte-PK, Upsert auf `client_ref`).
Phasen/Rea werden je Upload **vollständig ersetzt** (kein Delta). Die Uhr darf
lokal erst löschen, wenn `final` bestätigt und `next_seq` = Punktzahl.

**Ende-zu-Ende-Verschlüsselung (Pflicht):** Beim Login leitet der Browser per
PBKDF2-SHA256 (310 000 Runden) aus Passwort + `kdf_salt` zwei Werte ab: ein
Auth-Token (ersetzt das Passwort gegenüber dem Server, wird dort gehasht
gespeichert) und einen Datenschlüssel (bleibt im Browser, `sessionStorage`).
Ein zufälliger **Inhaltsschlüssel** (256 Bit, nicht vom Passwort abgeleitet)
verschlüsselt `pat_blob` (`{last, first, dob, dx, age, mission_no,
loc:{addr,lat,lon}, site_desc}`, AES-256-GCM) und liegt doppelt verpackt in `users`: mit dem Datenschlüssel
(`pat_wrap_pw`) und mit dem aus dem Wiederherstellungsschlüssel abgeleiteten
Schlüssel (`pat_wrap_rc`). Weil der Inhaltsschlüssel vom Passwort getrennt ist,
kostet ein Passwortwechsel kein Neuverschlüsseln — nur die Hülle wird erneuert.

Beide Hüllen entstehen **gemeinsam mit dem Passwort** in `pw_handling.php`
(siehe unten). Ein anmeldbares Konto ohne Hüllen kann es dadurch nicht geben;
die früher in `auth_guard.php` erzwungene Ersteinrichtung entfällt seit
Web 2.7.0 ersatzlos. Passwort-Ändern re-wrappt clientseitig **und atomar**:
Lässt sich der Inhaltsschlüssel nicht umpacken, wird auch das Passwort nicht
geändert. Eine Admin-Passwortvergabe existiert bewusst nicht.

**Entsperren des Inhaltsschlüssels in der Sitzung (`assets/unlock.js`, ab Web
3.0.0):** Anmeldung und Inhaltsschlüssel haben unterschiedliche Lebensdauern —
die Anmeldung hängt am PHP-Sitzungscookie (`SESSION_TIMEOUT_S`, 30 min
Inaktivität), der Schlüssel dagegen am `sessionStorage` des jeweiligen Tabs
(`edk` = Datenschlüssel, `pck` = Inhaltsschlüssel). Der Zustand „angemeldet,
aber gesperrt" tritt daher regelmäßig auf: Link im neuen Tab, Browser-Neustart,
Passwort-Reset ohne Wiederherstellungsschlüssel (der Wrap passt dann nicht mehr,
`getContentKey()` liefert `null`).

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
  während die Rechnung weiterläuft. Die 310 000 PBKDF2-Runden dauern je nach
  Gerät 0,3–1 s; solange sind Knöpfe und Feld gesperrt und es steht ein
  Wartehinweis.
- **Kein `window.prompt` als Rückfallebene.** Fehlt `<dialog>` oder das Salt,
  kommt `null` zurück — ein Prompt zeigte das Passwort im Klartext.

Aufrufstellen: `index.php`, `zeitraum.php`, `einsatz.php` (bezieht den Wrap aus
der API-Antwort `m.pat_wrap`, das Salt zusätzlich aus PHP), `einsatz_form.php`,
`import.php` über `assets/import_ui.js` und `assets/export.js`, sowie
`einstellungen.php` im Backup-Block. **Nicht** umgestellt ist der
Passwortwechsel im Profil-Block von `einstellungen.php`: Er leitet Schlüssel in
einem anderen Zusammenhang ab (Re-Wrap mit dem alten Passwort) und hat mit dem
Entsperren nichts zu tun.

Jeder Sperrhinweis trägt einen Knopf `.unlockbtn`, der denselben Ablauf erneut
anstößt. Wichtig dabei: Die Funktionen hinter diesen Knöpfen müssen ein
zweites Mal aufrufbar sein, ohne doppelt zu zeichnen — in `zeitraum.php` und
`index.php` ist das gegeben, weil ohne Schlüssel vorher kein Pin entsteht; in
`einsatz.php` wird der Sperr-Eintrag (`#patlockdt`/`#patlockdd`) zu Beginn
entfernt.

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
`tile.openmaps.fr`, OpenTopoMap über `tile.opentopomap.org`) und hängt
Leaflets eingebautes `L.control.layers()` an — kein zusätzliches Plugin.
Wie beim bisherigen OSM-Layer werden dabei ausschließlich Kartenkacheln
anhand des sichtbaren Ausschnitts angefragt, keine Standort- oder
Patientendaten (gleiches Datenschutzprinzip wie beim Verzicht auf
What3Words in der Ortssuche). Beide zusätzlichen Anbieter sind
spendenfinanzierte Community-Server ohne Verfügbarkeitsgarantie; die
Attribution enthält deshalb die jeweils geforderten Hinweise (inkl.
Spenden-Link bei OpenHikingMap, CC-BY-SA-Hinweis bei OpenTopoMap).

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

**Backup (portabel):** `api/backup_data.php` liefert alle Daten der NutzerIn als
Roh-JSON (geschützte Angaben weiterhin als Chiffretext). Der Browser
entschlüsselt sie mit dem Inhaltsschlüssel, ersetzt sie durch Klartext und
versiegelt das Ganze per `EdCrypto.sealBackup()` (AES-256-GCM, PBKDF2 310 000,
gzip via CompressionStream) zur `.edbak`-Datei. Beim Import öffnet der Browser
die Datei, verschlüsselt die Angaben mit dem Schlüssel des **Zielkontos** neu
und schickt sie an `api/backup_restore.php`. Dadurch sind Backups zwischen
Konten übertragbar; der Server sieht nie Klartext. Aufbau: `docs/Backup-Format.md`.

**Versionierung & Zwischenspeicher:** `WEB_VERSION` steht ausschließlich in
`server/version.php`. `asset($pfad)` (in `db.php`) hängt `?v=<Version>` an jede
Stylesheet- und Skript-Adresse; nach dem Erhöhen der Nummer lädt der Browser
geänderte Dateien von selbst neu — das manuelle Leeren des Zwischenspeichers
entfällt. **Beim Ausliefern immer die Version erhöhen.** `favicon_tags()`
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
`flugtag_neu.php`.

**Einsatztage-Leiste:** `ui_days_sidebar()` gruppiert die Tage serverseitig
nach Jahr und Monat (`<details>`-Verschachtelung); welches Jahr/welcher Monat
offen ist, bestimmt PHP anhand von `$currentDay` bzw. des jüngsten Tages —
kein JavaScript nötig, da jede Navigation ohnehin einen Seitenaufruf auslöst.
`assets/daylist.js` erzwingt das Akkordeon-Verhalten (ein offenes Element je
Ebene) für Klicks ohne Seitenwechsel und trennt die Klickbereiche:
Beschriftung → `zeitraum.php`, Dreieck → nur auf/zu.

> **CSS-Falle:** `.daylist a{display:block}`
> hat höhere Spezifität als `.trashlink` und steht weiter unten — Regeln für
> Menüpunkte müssen daher `.daylist a.klasse` lauten und **nach** `.daylist a`
> stehen.

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
einmal je Flugtag in `days.crew_*` gepflegt. Ein einzelner Einsatz kann davon
abweichen (fachlicher Anlass: Pilotenwechsel im laufenden Dienst) — dafür trägt
`missions` die Spalten `crew_override` (0/1) und `crew_p1`…`crew_other`.
**Bewusst redundanzfrei:** Ohne Abweichung bleiben die `missions`-Spalten NULL;
es gibt keine Kopie der Tagescrew am Einsatz. Die Regel lautet je Rolle
`crew_override = 1 AND missions.crew_X IS NOT NULL ? missions.crew_X :
days.crew_X`. Sie ist **einmal** implementiert, in `api/mission.php`, das das
Ergebnis als `crew_effektiv` (`{rolle: {label, name, abw}}`, nur belegte
Rollen) liefert; `einsatz.php` rendert es unverändert im Block „Besatzung".
Die `days`-Zeile wird dort **separat** geladen statt per JOIN — `SELECT *` auf
`missions` und `days` tragen dieselben Spaltennamen, ein JOIN würde sie
überschreiben.

Das Leeren beim Entfernen des Hakens erledigt die generische
Checkbox-Kindlogik in `einsatz_form.php` ohne Sonderfall (Kinder werden bei
Haken = 0 auf NULL gesetzt). Die Auswahllisten kommen über
`options_src => 'crew:<rolle>'` aus `crew_presets` — wie überall seit den
zentralen Stammdaten mit `(user_id = ? OR user_id IS NULL)`. Ein gespeicherter
Wert, der nicht mehr in den Stammdaten steht, wird beim Rendern vorangestellt
statt verworfen (gilt für alle `options_src`-Selects, also auch `bw_unit`) —
sonst ginge er beim nächsten Speichern still verloren.

Die Uhr kennt keine Besatzung; `ingest.php` ist davon unberührt.

**Rollenfilter der Besatzungsfelder (ab Web 2.7.1):** Welche der fünf Rollen im
Einsatzformular erscheinen, bestimmen die Häkchen `aircraft.p1`…`aircraft.other`
des am Flugtag eingetragenen Hubschraubers. Deklariert wird das je Feld über
`role_gate` in `mission_fields.php`; `einsatz_form.php` lädt die Rollen einmal
per `days JOIN aircraft` und setzt beim Rendern nur das `hidden`-Attribut.
**Nicht gerenderte Felder wären ein Datenverlust-Pfad** — der Browser sendet
sie dann nicht mit, und `readField()` liest fehlend als leer und überschreibt
den Bestand mit NULL. Deshalb wird immer gerendert und nur versteckt (`hidden`
verhindert das Absenden nicht). Zwei Rückfallregeln: Ein Feld mit Wert bleibt
sichtbar (sonst unerreichbar nach Maschinenwechsel am Flugtag), und ohne
bekannte Rollen (kein Flugtag oder kein Hubschrauber) werden alle gezeigt,
sonst wäre der Haken funktionslos.

Das Flugtag-Formular filtert nach denselben Häkchen, dort aber clientseitig
(`index.php`, `updateCrewFields()`), weil der Hubschrauber im Formular selbst
gewechselt werden kann. Im Einsatzformular steht er fest, daher serverseitig.

**Einsatzort-Höhe:** `site_elevation_lib.php` (`compute_site_elevation()`) ist
die **einzige Implementierung** — Referenzzeitpunkt Phase 5 „Ankunft
PatientIn", Fallback Phase 6, Toleranz 300 s (Konstante
`SITE_ELE_TOLERANCE_S`) zum zeitlich nächstgelegenen `track_points.ele`.
Aufgerufen von `ingest.php` (nach jedem Uhr-Upload), `einsatz_form.php` (nach
manuellem Speichern — Phasen ändern sich, der Track bleibt gleich),
`backup_lib.php` (nach Restore — aus den gerade eingespielten Phasen/Track neu
berechnet statt aus der Datei übernommen) und `update.php` (Backfill bei der
Migration). Kein Formularfeld, daher nicht in `mission_fields.php`.

**Zeitraum-API:** `api/range.php` liefert alle Einsätze eines Jahres oder Monats
**bewusst ohne Trackpunkte** — bei einem ganzen Jahr wären das
Hunderttausende Koordinaten. Die Karte der Zeitraumansicht (Einsatzort-Pins)
nutzt stattdessen die Koordinaten im `pat_blob`, die der Browser für die
Tabellenspalten ohnehin entschlüsselt — keine zweite Entschlüsselung, keine
Serveränderung nötig. Zusätzlich liefert die API `winch_cycles` und
`site_ele_m` je Einsatz (Grundlage der Statistiktabelle) sowie `tage` neu aus
der `days`-Tabelle statt `COUNT(DISTINCT day)` aus `missions` — zählt also
auch einsatzfreie Flugtage mit (Divisor der Durchschnittswerte). Die
geschützten Angaben entschlüsselt der Browser wie überall selbst.

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
nicht gebraucht. Der Endpunkt kommt mit sechs Abfragen aus, unabhängig von der
Zahl der Einsätze (kein N+1): Einsätze, Flugtage, Standorte, Maschinen,
Rettungsmittel, Reanimationen.

Zwei Fallen, die dort dokumentiert sind und bei Änderungen zu beachten bleiben:

- **days wird nicht per JOIN angebunden.** `missions` und `days` tragen beide
  `crew_p1`…`crew_other`; ein JOIN würde sie überschreiben. Dieselbe Falle ist
  in `api/mission.php` beschrieben. Die effektive Besatzung folgt derselben
  COALESCE-Regel: Einsatzwert nur, wenn `crew_override = 1` **und** das
  Rollenfeld belegt ist, sonst die Tagescrew.
- **`origin`, nicht `manual`.** Der Herkunftsfilter wertet `origin`
  ('watch' | 'manual' | 'import') aus. `manual` bedeutet seit Web 2.11.0
  ausschließlich „die Uhr überschreibt diesen Einsatz nicht mehr" — der
  Kommentar am Spaltenkopf in `schema.sql` sagt das ausdrücklich.

`start_min` (Minuten seit Mitternacht, Grundlage des Alarmzeitfilters) wird aus
derselben `fmt_local()`-Umrechnung abgeleitet wie `start_hhmm`, damit Anzeige
und Filter nicht auseinanderlaufen können. Standort und Maschine fallen auf die
Alt-Freitextspalten `days.base` / `days.aircraft` zurück, wenn die
Stammdaten-Verknüpfung fehlt — sonst wären Flugtage von vor der Umstellung
nach diesen Kriterien nicht auffindbar.

**Filterzustand im URL-Fragment.** Der gesamte Zustand steht hinter dem `#`,
nie im Query-String: Fragmente werden nicht an den Server gesendet und landen
damit nicht im Zugriffsprotokoll. Geschrieben wird mit `history.replaceState`,
nicht über `location.hash` — sonst wüchse die Chronik mit jedem Tastendruck im
Suchfeld. Die Parameternamen sind Teil bereits verschickter Links und dürfen
**nicht** umbenannt werden:

| Kurz | Filter | Kurz | Filter |
|------|--------|------|--------|
| `q`  | Freitext | `hk` | Herkunft (`watch`/`manual`/`import`) |
| `dv` / `db` | Datum von / bis | `st` | Standort |
| `zv` / `zb` | Alarmzeit von / bis | `ac` | Maschine |
| `wd` | Wochentage (`1`=Mo … `7`=So, kommagetrennt) | `c1`…`c5` | Besatzung P1, P2, HEMS, FR, Sonstige |
| `wi` | Windeneinsatz (`j`/`n`) | `rm` | Weiteres Rettungsmittel |
| `cv` / `cb` | Cycles von / bis | `tz` | Transportziel |
| `pv` / `pb` | Cycles mit Patient von / bis | `av` / `ab` | Alter von / bis |
| `lv` | Luftverladung (`j`/`n`) | `kv` / `kb` | Flugstrecke von / bis (km) |
| `bw` | Bergwacht (`j`/`n`) | `ev` / `eb` | Einsatzdauer von / bis (min) |
| `bu` | Bergwacht-Bereitschaft | `hv` / `hb` | Höhe Einsatzort von / bis (m) |
| `se` | Sekundärtransport (`j`/`n`) | `s` | Sortierspalte |
| `sr` | Schockraum (`j`/`n`) | `sd` | Sortierrichtung (`a`/`d`) |
| `re` | Reanimation (`j`/`n`) | | |
| `rt` | Reanimations-Ereignisse (kommagetrennt) | | |

Ein neuer Filter braucht drei Dinge: einen Eintrag in der Liste `FILTER` in
`suche.php` (mit `gruppe`), sein Feld im passenden `<details class="filtergruppe">`
der Filterspalte und seine Zeile in `trifft()`. Auslesen, Schreiben ins
Fragment, Wiederherstellen, das Zählen aktiver Filter und das Aufklappen der
Blöcke bei einem geteilten Link leiten sich alle aus `FILTER` ab.

**Layout (ab Web 3.1.1).** Die Filter stehen in der linken Spalte; `suche.php`
ruft `ui_days_sidebar()` **nicht** auf — einzelne Flugtage sind bei einer Suche
über den Gesamtbestand ohne Nutzen. Die Spalte nutzt bewusst eine eigene Klasse
`.filterspalte` statt `.daylist`: Letztere ist auf feste Fensterhöhe mit
`overflow:hidden` gesetzt und würde eine lange Filterliste abschneiden.
`.layout-suche` verbreitert die Spalte von 200 auf 280 px. Zwei Stolpersteine
sind dort im CSS vermerkt: Die 720-px-Regel für `.layout-suche` muss **nach**
der Grundregel stehen, weil der allgemeine 720-px-Block nur `.layout` greift
und sonst von der gleich spezifischen, später notierten Regel ausgehebelt
würde; und `.filterfuss .btn-plain` setzt `width:auto` gegen die globale
Formularregel. `daylist.js` steigt ohne `.dayyears` von selbst aus, die
Filter-`<details>` liegen ausserhalb und werden deshalb nicht wie das
Tages-Akkordeon gegenseitig verkoppelt — die Blöcke lassen sich einzeln
öffnen.

Bei gesperrtem Inhaltsschlüssel bleiben die geschützten Felder aus dem
Heuhaufen der Freitextsuche und der Altersfilter ist abgeschaltet — sonst wäre
mit gesetztem Altersfilter jeder Einsatz ein Nicht-Treffer. Nach dem Entsperren
wird der Heuhaufen neu gebaut und sofort neu gefiltert.

**Gemeinsame Einsatztabelle (`assets/missiontable.js`, ab Web 3.1.0).**
`zeitraum.php` und `suche.php` zeigen dieselbe Liste; Spalten, Sortierung und
Zeilenaufbau stehen deshalb genau einmal dort. `EdMissionTable.erzeuge()` baut
Kopf und Rumpf in ein übergebenes `<table>`; die Formatierer (`fmtTag`,
`fmtDur`, `fmtKm`, `extractOrt`, `esc`) sind zusätzlich einzeln exportiert,
weil `zeitraum.php` sie auch für Karten-Popups und Kacheln braucht. Eine neue
Spalte ist ein Eintrag in `SPALTEN` und erscheint auf beiden Seiten.

Zwei Rücksichten auf `zeitraum.php`, die sonst als Regression auffielen:
`pfeilInitial: false` erhält das bisherige Verhalten (Sortierpfeil erst nach
dem ersten Klick auf einen Spaltenkopf), und `onAfterDraw` wendet die
Hervorhebung der Extremwert-Kacheln erneut an — die Zeilen sind nach jedem
Zeichnen neu und hätten ihre Markierung sonst verloren.

**Papierkorb (Soft-Delete):** Einsätze, Ruhesegmente und Flugtage tragen
`deleted_at`; alle Lesepfade (Übersicht, Tages-/Einsatz-/Zeitraum-API,
Tagesliste, Backup) filtern darauf. `trash_lib.php` bündelt Umfangsermittlung,
weiches Löschen, Wiederherstellen und endgültiges Entfernen; der Aufräumjob in
`db.php` räumt nach `TRASH_DAYS` (**90**) endgültig ab. Beim Löschen eines
Flugtags werden dessen Einsätze/Segmente mit `deleted_with_day = 1` markiert —
sie hängen am Tag und kehren mit ihm zurück. `ingest.php` quittiert Uploads für
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
   zeilenweise Prüfung (`ok`/`warn`/`error`), Gruppierung nach Flugtag.
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
   Dadurch werden Nummerndubletten nur noch innerhalb der Flugtage erkannt,
   die in der Importdatei vorkommen — der Preis der Verschlüsselung. Tag und
   Alarmzeit bleiben als zweites, uneingeschränktes Merkmal wirksam.
   `commit` schreibt in **einer** Transaktion.

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
hätte diese Seite mitverändert. `action=meta` liefert Flugtage, Einsätze
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
entschlüsselt werden kann. Ohne den Haken „Patientendaten einschließen" wird das
Feld schon serverseitig **nicht selektiert**, nicht erst im Browser weggelassen.
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
- **Der Spaltensatz des CSV hängt nicht am Patientendaten-Haken.** Ohne Haken
  bleiben die `pat_`-Spalten vorhanden und leer. Ein wechselnder Spaltensatz
  würde jeden einlesenden Importer zwingen, zwei Fälle zu unterscheiden.
- **Die Formatauswahl `#exp_fmt` ist ein `<select>`, kein Optionsfeld.** In
  `export.js` wird sie ausschließlich über `gewaehltesFormat()` gelesen. Wird
  daraus wieder ein `input[name="exp_fmt"]:checked`, liefert `querySelector`
  `null`, `syncFormat()` wirft beim `DOMContentLoaded` — und weil die
  Registrierung des Klick-Zuhörers die **letzte** Anweisung im Init-Block ist,
  bleibt „Export erstellen" danach vollständig tot, ohne sichtbare Meldung.
  Genau das ist in Web 3.1.1 passiert (behoben in 3.2.0). Beim Umbau von
  Bedienelementen auf dieser Seite gehören Markup und Skript zusammen.
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
- `emptyDayRows` am Profil: Im Excel (Standard) steht ein Flugtag ohne Einsatz
  als eine
  Zeile mit Datum und lauter `-`. Ohne diese Unterscheidung entstünde daraus
  beim Rückimport ein Einsatz ohne Alarmzeit. Solche Zeilen legen den Flugtag an
  und keinen Einsatz.

Pflichtangaben werden beim Rückimport **nach** der Parserkette geprüft: Das
Füllzeichen `-` ist beim Einlesen nicht leer, wird aber zu `null` — die Prüfung
vor der Kette sieht das nicht.

**Aufräumjob:** `run_cleanup_if_due()` (db.php) läuft max. 1×/Tag, huckepack
auf `auth_guard.php` (Web) und `ingest.php` (Uhr) — kein Cron nötig. Marke
`last_cleanup` wird *vor* dem Lauf gesetzt (verhindert Parallel-Läufe);
entsorgt Trackpunkt-Waisen, alte Reset-Tokens, abgelaufene Kopplungscodes und
endgültig fällige Papierkorb-Einträge; scheitert grundsätzlich still (Wartung
darf keine Anfrage brechen).

**Sicherheit:** HTTPS erzwungen (.htaccess), Session-Cookies
HttpOnly/Secure/SameSite=Strict, CSRF für Formulare (`csrf_field`) und
JSON-POSTs (Header `X-CSRF`), PDO Prepared Statements durchgängig,
Passwörter/Schlüssel nur als Hash, Bruteforce-Bremse am Login, Ingest mit
Größen- (512 KB) und Wertevalidierung, sensible Dateien per .htaccess gesperrt,
Referrer-Policy `strict-origin-when-cross-origin` (OSM-Kacheln).

## 5. Uhr-App (Monkey C) — Modulstruktur

| Datei | Verantwortung |
|---|---|
| `HemsApp.mc` | Einstieg; Restore-Kette bei Neustart (Model → Track → Cpr → Sync) |
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
fenix6pro.sourcePath = source;source-tasten5
venu3s.sourcePath    = source;source-tasten3
venu3s.resourcePath  = resources;resources-venu3s
```

**Nicht** `$(<gerät>.sourcePath);source-tasten5` schreiben. Der Selbstbezug
fällt auf eine Vorgabe zurück, die alle `source*`-Ordner einsammelt; dann
landen beide `DeviceProfile.mc` im Build und der Compiler meldet
`Redefinition of 'HAS_UP_DOWN'`. Dasselbe gilt für `resourcePath` — sonst
bekäme die Fenix das 70×70-Icon der Venu 3s.

`base.sourcePath` steht auf dem Fünf-Tasten-Profil: Ein Gerät, das jemand ins
Manifest einträgt ohne hier eine Zeile zu ergänzen, baut damit gegen das
konservativere Profil.

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

## 6. Deployment

Push auf `main` mit Änderungen unter `server/` → GitHub Action
(`.github/workflows/deploy.yml`) lädt per **FTPS** hoch. **Auf dem Server liegt
dadurch 1:1 der Repositoriumsstand**; einzige Ausnahme ist die bei der
Installation erzeugte `config.php` (und `install.lock`), die nur auf dem Server
existieren. Secrets: `FTP_SERVER` (nackter Hostname!), `FTP_USERNAME`,
`FTP_PASSWORD`. `.gitignore` hält `watch/bin/`, `*.prg` und `config.php` aus dem
Repo.

## 7. Betrieb (Runbook)

**Gerät verloren / Schlüssel kompromittiert:** Web → „Geräte" (oder Verwaltung)
→ **Deaktivieren**. Wirkt sofort (Ingest antwortet `403`); Daten bleiben. Neue
Uhr = neues Gerät anlegen.

**Code-Update mit DB-Änderung ausrollen:** pushen (Deploy läuft automatisch)
→ als Admin **`/update.php`** aufrufen → alle Zeilen müssen ✔ zeigen.
Fehlgeschlagene Migrationen werden nicht verbucht und beim nächsten Aufruf
erneut versucht; Folge-Migrationen stoppen bis dahin. **Version in `version.php`
erhöhen** nicht vergessen, sonst sieht der Browser alte Dateien.

**Neue Zusatzfelder für Einsätze:** 1) Migration in `update.php` ergänzen
(`ALTER TABLE missions ADD COLUMN …`) und die ID zusätzlich in die
`skipped`-Liste in `schema.sql` eintragen, 2) Spalte auch ans `CREATE TABLE
missions` in `schema.sql` anfügen (sonst weichen Neuinstallation und
migrierter Bestand voneinander ab), 3) Eintrag in `mission_fields.php`.
Formular, Speichern, API und Detailanzeige übernehmen es dann automatisch.
**Ausnahme:** `day_col` wirkt derzeit **nicht** automatisch — die Spalten der
Tagestabelle sind hartkodiert (Backlog Nr. 10).

**Backup:** regelmäßiger MySQL-Dump (alle Tabellen; `mysqldump` oder
Hoster-Backup). Wiederherstellung: Dump einspielen; `config.php` bleibt
unberührt. Die Uhr sendet nach einer Wiederherstellung fehlende jüngste Daten
idempotent nach, sofern lokal noch vorhanden.

**Neuinstallation:** leere DB + `server/` hochladen → `index.php` leitet zum
Installer. Der Installer fragt **kein** Admin-Passwort mehr ab; er legt den
Zugang ohne Passwort an und zeigt auf der Erfolgsseite einen 24 h gültigen
Einmal-Link auf `pw_handling.php`, über den Passwort und
Wiederherstellungsschlüssel im Browser entstehen. Nach Erfolg sperrt
`install.lock`; `install.php` danach löschen.

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
aktiv? Connect-IQ-Einstellungen (Server-Domain, ID, Schlüssel)? Uhr online
(Handy-Kopplung/WLAN)? Anzeige „Sync ausstehend" verschwindet nach
erfolgreichem Upload.

## 8. Backlog

Die offenen Punkte stehen in einer eigenen Datei: **`Backlog.md`**. Dort sind
sie durchnummeriert; Verweise aus Code und Dokumentation nennen die Nummer
(z. B. „Backlog Nr. 10").
