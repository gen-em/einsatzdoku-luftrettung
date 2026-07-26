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
├── docs/                  Handbuch, Technik, Changelog, JSON-Vertrag
├── server/                komplette Web-App (wird per FTPS deployt)
│   ├── version.php        WEB_VERSION (einzige Stelle für die Versionsnummer)
│   ├── db.php             PDO, Helfer (e/asset/favicon_tags/logo_src/fmt_local), Aufräumjob
│   ├── ui.php             Kopf-/Seitenleisten, Fußzeile
│   ├── auth_guard.php     Session/CSRF/Rollen, erzwungene E2E-Einrichtung
│   ├── auth_salt.php      KDF-Salt (mit Pseudo-Salt gegen User-Enumeration)
│   ├── login/logout/reset_request/reset_confirm.php   Auth-Flows
│   ├── einrichtung.php    E2E-Ersteinrichtung (Wiederherstellungsschlüssel) & Entsperren
│   ├── index.php          Tagesübersicht (Karte + Tabelle)
│   ├── einsatz.php        Einsatzansicht · einsatz_form.php Nachtragen/Bearbeiten
│   ├── zeitraum.php       Jahres-/Monatsübersicht (Karte, Statistik, Tabelle)
│   ├── mission_fields.php Zentraler Feldkatalog der Zusatzfelder
│   ├── einstellungen.php  Profil/Standortdaten/Backup/Geräte
│   ├── admin.php + admin_user.php  Nutzerverwaltung · geraete.php (Weiterleitung)
│   ├── admin_stammdaten.php  Zentrale (globale) Stammdaten aller sechs Typen
│   ├── flugtag_neu.php    Flugtag von Hand anlegen
│   ├── einsatz_loeschen.php · flugtag_loeschen.php · papierkorb.php  Löschen mit Vorschau
│   ├── ingest.php         Uhr-/Fremdquellen-Endpunkt (Auth, Idempotenz)
│   ├── pair.php           Uhr-Kopplung per Code
│   ├── backup_lib.php     Backup-Serialisierung · trash_lib.php Papierkorb-Logik
│   ├── install.php        Serverinstallation · update.php Migrations-Runner
│   ├── smtp.php           SMTPS-Versand
│   ├── api/               day.php · mission.php · range.php · backup_data.php · backup_restore.php
│   ├── assets/            style.css, crypto.js (WebCrypto), patient.js, daylist.js, confirm.js
│   │   └── images/        Logo als SVG (farbig + weiss), favicon.png
│   ├── favicon.ico        Browser-Symbol im Wurzelverzeichnis
│   ├── schema.sql         Voll-Schema für Neuinstallationen
│   └── .htaccess          HTTPS-Zwang, Dateisperren, Sicherheits-Kopfzeilen
├── watch/                 Connect-IQ-Projekt (Monkey C)
│   ├── manifest.xml, monkey.jungle, resources/
│   └── source/            s. Abschnitt 5
└── .github/workflows/deploy.yml   FTPS-Deploy (nur server/, exkl. config)
```

## 3. Datenmodell (MySQL)

| Tabelle | Zweck / Besonderheiten |
|---|---|
| `users` | Login (E-Mail = Username), Rolle `user`/`admin`; Löschen kaskadiert alles; **Browser-Schlüsselableitung** (`kdf_salt`, `kdf_ver` immer 1) und **E2E-Schlüssel-Hüllen** `pat_wrap_pw`/`pat_wrap_rc` (Inhaltsschlüssel passwort- bzw. wiederherstellungsverpackt) |
| `password_resets` | Token-Hashes (sha256), 1 h gültig; Aufräumjob entsorgt Altbestand |
| `devices` | Upload-Zugang je Gerät: `device_id` (öffentlich) + `api_key_hash`; **`active`-Flag** (deaktivieren statt löschen); virtuelle Geräte `manual-<userId>` für Handeinträge (dauerhaft inaktiv, aus Listen gefiltert) |
| `missions` | Einsatz; `UNIQUE(device_id, client_ref)` = Idempotenz-Anker; `day` = Flugtag; **`manual`-Marker** (Schutz vor Uhr-Überschreiben); `deleted_at`/`deleted_with_day` (Papierkorb); Zusatzfelder lt. `mission_fields.php`; **`site_ele_m`** = berechnete Einsatzort-Höhe (kein Formularfeld, siehe `site_elevation_lib.php`); **`pat_blob`** = E2E-Chiffretext (Name, Geburtsdatum, Alter, Diagnose, Einsatzort — Klartext-Ortsspalten existieren seit der Pflicht-Migration nicht mehr) |
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
verschlüsselt `pat_blob` (`{last, first, dob, dx, age, loc:{addr,lat,lon}}`,
AES-256-GCM) und liegt doppelt verpackt in `users`: mit dem Datenschlüssel
(`pat_wrap_pw`) und mit dem aus dem Wiederherstellungsschlüssel abgeleiteten
Schlüssel (`pat_wrap_rc`). Weil der Inhaltsschlüssel vom Passwort getrennt ist,
kostet ein Passwortwechsel kein Neuverschlüsseln — nur die Hülle wird erneuert.

`auth_guard.php` erzwingt die Ersteinrichtung (`einrichtung.php`), solange die
Hüllen fehlen; dieselbe Seite entsperrt nach einem Passwort-Reset per
Wiederherstellungsschlüssel. Passwort-Ändern re-wrappt clientseitig; eine
Admin-Passwortvergabe existiert bewusst nicht.

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

> **Historie:** Ältere Konten mit `kdf_ver = 0` (Passwort ging im Klartext zum
> Server) wurden in Web 2.1.0 vollständig entfernt. Es gibt keinen
> unverschlüsselten Anmeldeweg mehr; Browser ohne Web-Krypto erhalten eine
> klare Fehlermeldung. `auth_salt.php` liefert Salts, für unbekannte Adressen
> ein deterministisches Pseudo-Salt gegen User-Enumeration.

**Passwort-Reset:** `reset_confirm.php` verlangt das neue Passwort **und** den
Wiederherstellungsschlüssel. Der Browser entpackt damit den Inhaltsschlüssel,
leitet aus dem neuen Passwort Salz + Token ab und verpackt den Schlüssel neu;
der Server schreibt Token-Hash, Salz und Hülle in **einer Transaktion**. Passt
der Schlüssel nicht, bricht der Vorgang im Browser ab, bevor etwas gesendet
wird — das Konto bleibt unverändert.

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
Tages- und Zeitraumübersicht. Name und Geburtsdatum erscheinen nur in der
Einsatzansicht, nie in den Tabellenübersichten. Das Alter wird nur dann als
Wert gespeichert, wenn es **nicht** aus einem Geburtsdatum ableitbar ist.

**Rettungsmittel:** `other_resources` hat in `mission_fields.php` den Sondertyp
`resources` und **keine** `missions`-Spalte. Vorbelegungen stehen in
`resources`, die Zuordnung je Einsatz in `mission_resources` (eigene Zeilen,
einzeln entfernbar). Das Löschen einer Vorbelegung lässt dokumentierte Einsätze
unverändert. Backup exportiert/importiert beide.

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
`api/mission.php` und `api/backup_data.php` kapseln ihre Datenbankzugriffe in
try/catch (Muster ursprünglich aus `api/backup_restore.php`) und antworten bei
einer Ausnahme mit `{"error": "<endpunkt>", "meldung": "<Exception-Message>"}`
statt eines leeren HTTP 500 — wichtig z. B. direkt nach einem Deploy mit
DB-Änderung, aber vor dem Aufruf von `/update.php`. Neue Endpunkte sollten
demselben Muster folgen. Die jeweiligen Frontends (`zeitraum.php`,
`einsatz.php`, `index.php`) zeigen `error`+`meldung` in einer Fehlerbox an.

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

**Schutz manueller Einsätze:** Beim Ingest wird vor dem Upsert der
`manual`-Marker geprüft. Ist er gesetzt, werden Metadaten/Phasen/Rea **nicht**
angefasst; Trackpunkte laufen weiter ein (append-only). Gesetzt wird der Marker
beim Speichern im Bearbeitungsformular bzw. bei Handanlage.

**Zeitbehandlung:** Speicherung UTC (`DATETIME`), Anzeige über `fmt_local()`
(Europe/Berlin). Das Formular rechnet lokale Eingaben nach UTC um; Zeiten
„nach Mitternacht" (kleiner als die vorherige) erhalten +1 Tag.

> **PHP-Falle:** Numerische
> Array-Schlüssel werden zu Ganzzahlen; unter `strict_types` bricht `e()` dann
> ab. Bei Jahr/Monat-Gruppierungen überall `(string)`-Umwandlung und `str_pad`.

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
| `Cpr.mc` | Rea-Timer app-weit (1-s-Tick), 2:00-Zyklus, Ereignisse, **persistenter Zustand** (übersteht Neustart) |
| `Uploader.mc` | Job-Queue (fertige Einsätze → Segmente → aktive), Chunking ≤ 500, `next_seq`-Bestätigung, Purge inkl. Marken; `hasServer()`/`hasCredentials()` |
| `Nav.mc` | Pager: Uhr → Tempo → Statistik → Sync → Rea |
| `StartView.mc` | Startbildschirm „Dienst beginnen"; Hinweise zu Server-Adresse und Kopplung |
| `ClockView/SpeedView/StatsView/SyncView/CprView.mc` | Oberflächen + Delegates; lange Tastendrücke manuell via `onKeyPressed/Released` (`Const.LONG_PRESS_MS`); Tastensperre-Erkennung (zweite Taste während START) |
| `SyncView.mc` | Sync-Status (Backlog = nur abgeschlossene Pakete), App-Version, Kopplung per START-Halten |
| `Pair.mc` | Kopplungscode-Eingabe → tauscht Code gegen Geräte-Zugang (`Storage 'cred'`) |
| `Const.mc` / `Util.mc` | `APP_VERSION`, Labels, Tuning-Werte; ISO-UTC, lokale Anzeige, Vibration |

Rückruf-Muster: `method()` existiert nur auf Objekten → kleine Träger-Klassen
(`TrackCb`, `CprCb`, `UploaderCb`) reichen Callbacks an die Module weiter.

> **Kartenseite entfernt (1.3.5):** `MapPage.mc` funktionierte am Gerät nicht
> zuverlässig und wurde gelöscht. Eine künftige Kartenansicht wird neu
> aufgebaut; die alte Fassung liegt in der Git-Historie.

**Build:** VS Code + Monkey-C-Erweiterung + Connect-IQ-SDK + JDK;
Entwickler-Schlüssel via „Generate a Developer Key". Ziel `fenix6pro`,
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
`skipped`-Liste in `schema.sql` eintragen, 2) Eintrag in `mission_fields.php`.
Formular, Speichern, API und Anzeige übernehmen automatisch.

**Backup:** regelmäßiger MySQL-Dump (alle Tabellen; `mysqldump` oder
Hoster-Backup). Wiederherstellung: Dump einspielen; `config.php` bleibt
unberührt. Die Uhr sendet nach einer Wiederherstellung fehlende jüngste Daten
idempotent nach, sofern lokal noch vorhanden.

**Neuinstallation:** leere DB + `server/` hochladen → `index.php` leitet zum
Installer; nach Erfolg sperrt `install.lock`; `install.php` danach löschen.

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

## 8. Backlog (bewusst offen)

1. Reanimations-Zeiten im Nachtrage-/Bearbeitungsformular
2. Serverseitige Track-Vereinfachung (Douglas-Peucker) für die Web-Darstellung
3. GPX-Export (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`)
4. Geteilte Flugtage (Crew-weit statt je NutzerIn)
5. Geräte-Limit pro NutzerIn
6. Weitere Zielgeräte (Fenix 7/8, Touch-Bedienung)
7. Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen
8. Content-Security-Policy als zusätzliche Verteidigungslinie
9. `asset()` auf Datei-Zeitstempel statt globale Version umstellen
