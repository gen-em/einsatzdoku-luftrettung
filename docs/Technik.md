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
│                          Review-Umsetzung (Stand der Review-Behebung),
│                          Backup-Format, Export-Format,
│                          Geraete-Eingabe (gemessenes Eingabeverhalten je Uhr),
│                          Uhr-Layout (Layoutregeln der Uhr-Oberflächen)
├── server/                komplette Web-App (wird per FTPS deployt)
│   ├── version.php        WEB_VERSION (einzige Stelle für die Versionsnummer)
│   ├── db.php             PDO, Helfer (e/asset/favicon_tags/logo_src/fmt_local/local_to_utc), Aufräumjob
│   ├── ui.php             Kopf-/Seitenleisten, Fußzeile
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
│   ├── admin_users.php + admin_user.php  Nutzerverwaltung (Liste · Detail)
│   ├── admin_stammdaten.php  Systemweite Stammdaten aller sechs Typen
│   │                       (Reiter `?t=standorte` / `?t=rettungsmittel`)
│   ├── diensttag_neu.php  Diensttag von Hand anlegen · diensttag_datum.php Datum ändern
│   │                       · diensttag_zusammenfuehren.php  mehrfach gestartete Dienste
│   │                         wieder zu einem Diensttag vereinen
│   ├── diensttag_lib.php  Diensttage anlegen, zuordnen, einfrieren, auflisten
│   ├── nachbearbeitung.php + nachbearbeitung_lib.php  einmalige Nachträge nach der Migration
│   ├── einsatz_loeschen.php · diensttag_loeschen.php · papierkorb.php  Löschen mit Vorschau
│   ├── ingest.php         Uhr-/Fremdquellen-Endpunkt (Auth, Idempotenz)
│   ├── pair.php           Uhr-Kopplung per Code
│   ├── backup_lib.php     Backup-Serialisierung · trash_lib.php Papierkorb-Logik
│   ├── adminbackup_lib.php  Admin-Sicherungen: Ablage, Übersicht, Freigabe (A8)
│   ├── admin_sicherungen.php  Adminseite dazu · sicherungen/ die Ablage selbst
│   │                       (entsteht nur auf dem Server, im Deploy ausgenommen)
│   ├── validate_lib.php   Gemeinsame Prüfschicht für Einsatzdaten (alle vier Schreibwege)
│   ├── ratelimit_lib.php  Ratenschutz (Konto + IP, in der Datenbank)
│   ├── session_lib.php    Sitzungsende mit Räumung im Browser (Abmelden, Ablauf,
│   │                       gelöschtes Konto, Passwortwechsel)
│   ├── email_lib.php      E-Mail: Normalisierung, Prüfung, Dublettenerkennung
│   │                       (ohne Abhängigkeiten — auch für install.php)
│   ├── install.php        Serverinstallation · update.php Migrations-Runner
│   ├── smtp.php           SMTPS-Versand + Abschluss der Antwort vor langsamer Arbeit
│   ├── api/               day.php · mission.php · range.php · suchindex.php · backup_data.php · backup_restore.php ·
│   │                      import_commit.php (Abgleich + Übernahme des Imports) ·
│   │                      export_data.php (nur lesend, Rohdaten für den Export) ·
│   │                      adminbackup_freigabe.php (freigegebene Sicherung für die NutzerIn)
│   ├── assets/            style.css (Schriften werden lokal ausgeliefert, s. u.),
│   │                      crypto.js (WebCrypto), unlock.js (Entsperrdialog, s. u.),
│   │                      zeitfeld.js (Zeiteingabe im 24-Stunden-Format, s. u.),
│   │                      keyguard.js (Bindung/Lebensdauer des Inhaltsschlüssels),
│   │                      pwquality.js (Passwortgüte), patient.js, daylist.js, confirm.js,
│   │                      html.js (HTML-Maskierung, die eine Fassung für alle Seiten),
│   │                      missiontable.js (gemeinsame Einsatztabelle, s. u.),
│   │                      aktionsmenu.js (Verhalten des Aktionsmenüs oben rechts),
│   │                      map_fullscreen.js + map_layers.js (gemeinsame Leaflet-Controls, s. u.),
│   │                      import.js (Pipeline) + import_profiles.js (Formate) + import_ui.js (Bedienung),
│   │                      export.js (alle drei Exportprofile, Aufbau im Browser),
│   │                      ortsfeld.js (Ortsfeld-Komponente: Bezeichnung + optionale
│   │                       Koordinaten, sechs Verwendungen, s. u.),
│   │                      luftlinie.js (gestrichelte Verbindung ohne GPS-Track, s. u.)
│   │   └── vendor/        xlsx.full.min.js — SheetJS Community Edition 0.18.5, Apache-2.0 ·
│   │                      zipjs.min.js — zip.js 2.8.34, BSD-3-Clause (ZIP + AES-256) ·
│   │                      leaflet/ — Leaflet 1.9.4, BSD-2-Clause (Karten; CSS, JS, images/);
│   │                      alle lokal vendoriert (kein CDN), Herkunft und SHA-256 im Dateikopf
│   │   └── fonts/         Bricolage Grotesque 500/600 und Open Sans 400/600/700 als woff2,
│   │                      je Subset latin und latin-ext (@fontsource, OFL-1.1)
│   │   └── images/        Logo als SVG (farbig + weiss), favicon.png
│   ├── favicon.ico        Browser-Symbol im Wurzelverzeichnis
│   ├── config.example.php Vorlage der config.php (die selbst nur auf dem Server
│   │                      liegt und vom Deploy ausgenommen ist)
│   ├── schema.sql         Voll-Schema für Neuinstallationen
│   ├── migrations/        Migrationen als nachlesbare SQL-Dateien (ausgeführt wird über update.php)
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
| `users` | Login (E-Mail = Username), Rolle `user`/`admin`; Löschen kaskadiert alles; **Browser-Schlüsselableitung** (`kdf_salt` + `kdf_iter` = Rundenzahl je Konto) und **E2E-Schlüssel-Hüllen** `pat_wrap_pw`/`pat_wrap_rc` (Inhaltsschlüssel passwort- bzw. wiederherstellungsverpackt), dazu `pat_key_check` = im Browser gerechnete Prüfsumme des Inhaltsschlüssels (NULL bei Altbestand — ein gültiger Zustand); `session_epoch` = Zähler, mit dem ein Passwortwechsel offene Sitzungen beendet (**seit Web 4.5.0 in Gebrauch**). `password_hash` ist NULL, solange das Passwort noch nicht gesetzt wurde — ein solches Konto kann sich nicht anmelden. Die **Sortierregel der E-Mail-Spalte ist ausdrücklich festgelegt** (`utf8mb4_unicode_ci`); ohne das hinge die Anmeldung an der Standardregel der jeweiligen Installation. Seit Web 4.5.0 schreibt und sucht der Code zusätzlich kleingeschrieben (`email_lib.php`), hängt also nicht mehr von der Sortierregel ab; **Bestandszeilen bleiben unverändert**, die ci-Regel trifft sie ohnehin |
| Sicherung | `backup_lib.php` | Das Format ist seit Web 4.5.2 **aufgezählt** statt „alles, was in der Tabelle steht". Neue Spalten sind damit nicht mehr automatisch enthalten — sie einzutragen ist eine Entscheidung. Draußen: `id`/`user_id`/`device_id` (interne Verweise) und `other_resources` (tote Altspalte seit der Migration `2026_07`). **Bekannt:** `site_ele_m` ist in der Sicherung, kommt beim Einspielen aber nicht zurück — der Einspielweg schreibt nur die Felder aus `mission_fields.php` plus `pat_blob`. |
| `password_resets` | Token-Hashes (sha256); 1 h bei „Passwort vergessen“, 24 h bei Neuanlage und Installation; Aufräumjob entsorgt Altbestand. Seit Web 4.4.0 gilt **höchstens ein offener Token je Konto**: Eine neue Anforderung entwertet alle vorherigen. Seit Web 4.5.0 entwertet auch **jeder Passwortwechsel** alle offenen Token des Kontos — der 24-Stunden-Einladungslink entsteht auf einem anderen Weg und hätte den soeben gewählten Zustand sonst überschreiben können |
| `devices` | Upload-Zugang je Gerät: `device_id` (öffentlich, seit Web 4.5.1 aus **16** statt 4 Zufallsbytes — Bestandsgeräte behalten die kurze Kennung) + `api_key_hash`; **`active`-Flag** (deaktivieren statt löschen); virtuelle Geräte `manual-<userId>` für Handeinträge (dauerhaft inaktiv, aus Listen gefiltert). Seit Web 4.4.0 **höchstens `MAX_GERAETE` (5) echte Geräte je Konto**, aktive wie deaktivierte — die virtuellen zählen nicht mit |
| `missions` | Einsatz; `UNIQUE(device_id, client_ref)` = Idempotenz-Anker; **`day_id`** = Fremdschlüssel auf `days` (bis Web 5.10.0: die Spalte `day` mit dem Kalenderdatum); **`manual`-Marker** — ausschließlich Schutz vor Uhr-Überschreiben, NICHT „von Hand angelegt"; **`origin`** (`watch`/`manual`/`import`) = Herkunft, wird beim Anlegen gesetzt und nie wieder geändert; **`edited`** = wurde nach dem Anlegen verändert; `deleted_at`/`deleted_with_day` (Papierkorb); Zusatzfelder lt. `mission_fields.php`; **`site_ele_m`** = berechnete Einsatzort-Höhe (kein Formularfeld, siehe `site_elevation_lib.php`); **`crew_override`** = abweichende Besatzung je Einsatz; die Namen liegen seit Web 6.0.0 in **`mission_crew`** (`mission_id, role_code, name`) statt in fünf festen Spalten — die Tagescrew in `day_crew` bleibt die einzige Wahrheit, solange der Haken nicht gesetzt ist (siehe Abschnitt 4); **`pat_blob`** = E2E-Chiffretext (Name, Geburtsdatum, Alter, Diagnose, Einsatzort, seit Web 2.9.0 auch die Einsatznummer, seit Web 3.3.0 auch die Beschreibung des Einsatzortes — Klartext-Ortsspalten existieren seit der Pflicht-Migration nicht mehr) |
| `mission_phases` | Phasen-Zeitstempel **2–9** (Mehrfach-Einträge erlaubt und erwünscht — eine erneut gesetzte Phase ist eine Korrektur, keine Dublette) inkl. Position. Eine Phase 10 gibt es nicht; der Abschluss läuft über `final` und `ended_at` |
| `resus_sessions` / `resus_events` | Reanimationen: **mehrere Sitzungen je Einsatz**, Ereignisse typisiert |
| `rest_segments` | Ruhe-Track-Segmente (gleiches Idempotenz-Schema wie Einsätze) |
| `track_points` | GPS-Punkte für Einsätze **und** Segmente; PK `(owner_type, owner_id, seq)`; bewusst ohne FK (polymorph) → Aufräumjob entfernt Waisen |
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
| `pair_codes` | Kopplungscodes für die Uhr: **6 Zeichen** aus 32 (`PAIR_CHARS` in `db.php`, ohne 0/O und 1/I), **10 Minuten** gültig, **genau einmal** einlösbar, höchstens **ein offener Code je Konto**; die Einmaligkeit wird durch die Reihenfolge „entwerten, dann prüfen“ in `pair.php` durchgesetzt statt bloß zugesichert; Ratenschutz über `rate_limits`; Aufräumjob entsorgt Altbestand |
| `deleted_refs` | Sperrliste gelöschter `client_ref`s (90 Tage) gegen Wieder-Upload durch die Uhr; `owner_type` unterscheidet Einsatz und Ruhe-Segment — die Liste gilt für **beide** |
| `rate_limits` | Ratenschutz: Versuche je `topf` (login/salt/reset/pair) und `merkmal` (`ip:…` oder `id:…`), mit Zeitfenster und Sperrfrist; liegt bewusst in der Datenbank und nicht in der Sitzung — eine Zählung, die der Aufrufer durch Wegwerfen seines Cookies zurücksetzen kann, ist keine. Seit Web 4.4.0 sind **alle vier Töpfe in Gebrauch**. Bei `salt` und `reset` zählt **jede** Anfrage, nicht nur eine fehlgeschlagene: Beide Endpunkte kennen kein Scheitern, begrenzt wird die Menge (`rate_zaehlen()`). Aufräumjob entsorgt Altbestand |
| `app_state` | Schlüssel/Wert (z. B. `last_cleanup`, `last_cleanup_ok`, `salt_secret`). `last_cleanup` = letzter **Versuch** der Wartung, `last_cleanup_ok` = letzter **vollständiger** Lauf (seit Web 4.5.1). Weichen sie voneinander ab, scheitert dauerhaft mindestens ein Aufräumschritt; die Wartungsseite zeigt das an |
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

**Backup (portabel):** `api/backup_data.php` liefert alle Daten der NutzerIn als
Roh-JSON (geschützte Angaben weiterhin als Chiffretext). Der Browser
entschlüsselt sie mit dem Inhaltsschlüssel, ersetzt sie durch Klartext und
versiegelt das Ganze per `EdCrypto.sealBackup()` (AES-256-GCM, PBKDF2 mit der
Rundenzahl des Kontos — sie steht seit Web 5.0.0 im Dateikopf, gzip via
CompressionStream) zur `.edbak`-Datei. Beim Import öffnet der Browser
die Datei, verschlüsselt die Angaben mit dem Schlüssel des **Zielkontos** neu
und schickt sie an `api/backup_restore.php`. Dadurch sind Backups zwischen
Konten übertragbar; der Server sieht nie Klartext. Aufbau: `docs/Backup-Format.md`.

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
* `diensttag_datum.php` nennt, was am gewählten Datum bereits liegt. Es ist
  reine Auskunft — belegt oder frei ändert nichts an der Zulässigkeit.

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
einmal je Diensttag in `day_crew` gepflegt (bis Web 5.10.0: fünf Spalten
`days.crew_*`). Ein einzelner Einsatz kann davon abweichen (fachlicher Anlass:
Pilotenwechsel im laufenden Dienst) — dafür trägt `missions` die Spalte
`crew_override` (0/1), die Namen liegen in `mission_crew`.
**Bewusst redundanzfrei:** Ohne Abweichung gibt es in `mission_crew` keine
Zeile; es gibt keine Kopie der Tagesbesatzung am Einsatz. Die Regel lautet je
Rolle `crew_override = 1 AND mission_crew.name IS NOT NULL ?
mission_crew.name : day_crew.name`. Sie ist **einmal** implementiert, in
`api/mission.php`, das das Ergebnis als `crew_effektiv`
(`{rolle: {label, name, abw}}`, nur belegte Rollen) liefert; `einsatz.php`
rendert es unverändert im Block „Besatzung".

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

**Layout (ab Web 3.1.1).** Die Filter stehen in der linken Spalte; `suche.php`
ruft `ui_days_sidebar()` **nicht** auf — einzelne Diensttage sind bei einer Suche
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

**Gemeinsame Einsatztabelle (`assets/missiontable.js`, ab Web 3.1.0).**
`zeitraum.php` und `suche.php` zeigen dieselbe Liste; Spalten, Sortierung und
Zeilenaufbau stehen deshalb genau einmal dort. `EdMissionTable.erzeuge()` baut
Kopf und Rumpf in ein übergebenes `<table>`; die Formatierer (`fmtTag`,
`fmtDur`, `fmtKm`, `extractOrt`, `esc`) sind zusätzlich einzeln exportiert,
weil `zeitraum.php` sie auch für Karten-Popups und Kacheln braucht. `esc` und
`escape` zeigen seit Web 4.6.0 beide auf `EdHtml.escape` (`assets/html.js`);
die Datei muss deshalb **vor** `missiontable.js` geladen werden. Eine neue
Spalte ist ein Eintrag in `SPALTEN` und erscheint auf beiden Seiten.

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

Weil `suche.php` die Ergebniszeile aus denselben zwei Zahlen baut, steht ihr
Text in `onAfterDraw` und nicht in `anwenden()` — das Nachladen zeichnet neu,
ohne dass sich ein Filter geändert hätte.

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

**Tabs der Zeitraum-Übersicht (ab Web 6.2.0, Konzept 3.7.1).** Die Tableiste
erscheint nur, wenn im Zeitraum **beide** Arten vorliegen; maßgeblich sind die
Diensttage (`tage_art` aus `api/range.php`), nicht die Einsätze — ein
bodengebundener Dienst ohne einen einzigen Einsatz ist trotzdem einer. Liegt nur
eine Art vor, bestimmt sie allein die **Beschriftung** der Kacheln; gezeigt wird
in diesem Fall alles, auch die Einsätze neutraler Diensttage, denn sonst fehlten
sie in der einzigen Ansicht, die es dann gibt. Der Hinweis auf mitgezählte
neutrale Diensttage steht überall dort, wo sie tatsächlich mitzählen — in
„Gemischt" und in einer Ansicht ohne Tableiste.

Die Kacheln entstehen im Browser (`KACHELSATZ` in `zeitraum.php`) statt fest im
HTML zu stehen: Welche es gibt und wie sie heißen, hängt am Tab und bei den
Windenkacheln zusätzlich am Bestand. Die Ereignisse der Extremwert-Kacheln
werden deshalb beim Erzeugen vergeben, nicht am Raster delegiert — `mouseenter`
steigt nicht auf, und `mouseover` feuerte zusätzlich bei jedem Wechsel zwischen
Wert und Beschriftung innerhalb derselben Kachel. Die Karten-Pins werden beim
Tabwechsel **verworfen und neu gesetzt** (`pinLayer`), nicht versteckt: Ein Pin
ohne Bildschirmposition lässt kein `setStyle()` zu — derselbe Stolperstein wie
beim Ausgangsausschnitt der Karte.

**Papierkorb (Soft-Delete):** Einsätze, Ruhesegmente und Diensttage tragen
`deleted_at`; alle Lesepfade (Übersicht, Tages-/Einsatz-/Zeitraum-API,
Tagesliste, Backup) filtern darauf. `trash_lib.php` bündelt Umfangsermittlung,
weiches Löschen, Wiederherstellen und endgültiges Entfernen; der Aufräumjob in
`db.php` räumt nach `TRASH_DAYS` (**90**) endgültig ab. Beim Löschen eines
Diensttags werden dessen Einsätze/Segmente mit `deleted_with_day = 1` markiert —
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

**Aufräumjob:** `run_cleanup_if_due()` (db.php) läuft max. 1×/Tag, huckepack
auf `auth_guard.php` (Web) und `ingest.php` (Uhr) — kein Cron nötig. Marke
`last_cleanup` wird *vor* dem Lauf gesetzt (verhindert Parallel-Läufe);
entsorgt Trackpunkt-Waisen, alte Reset-Tokens, abgelaufene Kopplungscodes,
Ratenschutz-Zähler, Einträge der Sperrliste und endgültig fällige
Papierkorb-Einträge; scheitert **gegenüber der Anfrage** still (Wartung darf
keine Anfrage brechen).

Seit Web 4.5.1 hat jeder der sieben Schritte einen **eigenen** Fehlerblock, und
Fehler gehen ins Fehlerprotokoll des Webspace (Suchwort `cleanup:`). Vorher
teilten sich alle Schritte einen Block: Scheiterte einer, entfielen alle
folgenden — und weil die Marke bereits stand, lief an diesem Tag nichts mehr.
Am nächsten Tag scheiterte es an derselben Stelle wieder. Am spürbarsten beim
Papierkorb, der als vorletzter Schritt stand.

Die Marke bleibt bewusst *vor* der Arbeit. Sie danach zu setzen hieße, dass
zwei gleichzeitige Anfragen beide aufräumen; das ist der teurere Fehler.

Eine zweite Marke `last_cleanup_ok` hält fest, wann zuletzt ein Lauf
**vollständig** durchging. `update.php` zeigt beide an — weichen sie
voneinander ab, scheitert dauerhaft ein Schritt. Ohne diese Auskunft ist ein
kaputter Aufräumjob von einem laufenden nicht zu unterscheiden, bis irgendwann
auffällt, dass der Papierkorb seit Monaten nicht geleert wurde.

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
Wiederherstellungsschlüssel und beim Einspielen einer Sicherung; gesetzt wird
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
Anmeldung erreichbar. Beide müssen für "gibt es" und "gibt es nicht"
Antworten liefern, die sich in **Länge, Zeichenvorrat, Aufbau und Dauer**
nicht unterscheiden. Beim Salt war es zuletzt die Länge, die alles verriet:
Ein echtes Salt hat 32 Hexzeichen, das Pseudo-Salt hatte 64. Wer hier etwas
ändert, prüft bitte beide Zweige nebeneinander.

**Der Aufruf einer Seite darf nichts verändern.** `update.php` führt
Migrationen erst auf eine bestätigte Absendung mit Formular-Token aus; der
Aufruf zeigt nur an, was anstünde. Migrationen können Spalten löschen, und
eine unwiderrufliche Handlung auf einen GET hin ist immer falsch — auch dann,
wenn nur Verwaltende die Seite erreichen.

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

**Zwei Bedienformen, ein Code.** Bei `getrennteSuche: false` ist das Textfeld
zugleich das Suchfeld; ein Adresstreffer wird zur Bezeichnung. Bei `true` gibt
es ein eigenes Suchfeld daneben, und der Treffer setzt **nur** die Koordinaten —
„Standort Kempten" ist keine Adresse, und eine Suche im Namensfeld schriebe den
Namen weg. Alles übrige ist in beiden Formen dasselbe: Chip statt Zahlen im
Textfeld, lokale Formaterkennung vor jeder Netzanfrage, Bestätigung statt
sofortiger Übernahme, ruhende Suche bei gesetzten Koordinaten, und die Prüfung
„Koordinaten ohne Bezeichnung" beim Absenden.

**Die Luftlinie** (`assets/luftlinie.js`) zeichnet, was ohne GPS-Aufzeichnung
über den Weg bekannt ist: **Abfahrtort → Einsatzort → Zielklinik**, gestrichelt
und in Max Blau, damit sie nicht mit dem Track-Orange verwechselt wird. Drei
Regeln, die sie nie verletzt:

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
Formular → Uhr → Sicherung, die nach Vertrauenswürdigkeit der Quelle genau
umgekehrt:

| Prüfung | Formular | Import | Uhr | Sicherung |
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
| Schlüsselbindung | `assets/keyguard.js` | Bindet den zwischengespeicherten Inhaltsschlüssel an die Hülle, aus der er stammt, und lässt ihn mit der Sitzungsfrist ablaufen. **Muss vor `unlock.js` geladen werden.** |
| Fehlerantwort der Endpunkte | `db.php` | `json_fehler()` protokolliert den vollen Ausnahmetext und gibt nach außen nur eine achtstellige Kennung. `fehler_kennung()` für Stellen mit eigener Antwortform (`ingest.php`). |
| Zeitrechnung | `db.php` | **`TIMESTAMP` und `DATETIME` verhalten sich verschieden, und das ist bei jeder Zeitspalte mitzudenken.** `TIMESTAMP` rechnet MySQL beim Schreiben in UTC um und beim Lesen zurück — der gespeicherte Wert ist unabhängig von der Sitzungszone immer richtig (`pair_codes`, `devices.last_seen`/`created_at`, `users.created_at`, `missions.created_at`, `deleted_refs`). `DATETIME` speichert unverändert, was dasteht; dort entscheidet die Sitzungszone (`rate_limits`, `password_resets.expires_at`, sowie die Einsatz- und Papierkorbzeiten — Letztere werden aber über `local_to_utc()` bzw. `UTC_TIMESTAMP()` befüllt und waren nie zonenabhängig). |
| Zeitrechnung | `db.php` | Die Verbindung steht seit Web 4.5.2 ausdrücklich auf UTC (`SET time_zone = '+00:00'`). Ohne das käme die Zeitrechnung von `NOW()` aus einer Hoster-Einstellung, und `NOW()` und `UTC_TIMESTAMP()` liefen um den Zonenversatz auseinander. Der Unterschied im Code bleibt: `UTC_TIMESTAMP()` für den Papierkorb (30-Tage-Frist), `NOW()` für Kurzlebiges (Ratenschutz, Token, Kopplungscodes). Die **Anzeige** rechnet in PHP nach `$CFG['app']['timezone']` um. |
| Sitzungsende | `session_lib.php` | Eine Fassung für Abmelden, Ablauf, gelöschtes Konto **und** Passwortwechsel; räumt die Schlüssel im Browser und nennt den Grund. `session_verwerfen()` für Abrufe, die JSON erwarten. |
| E-Mail-Adressen | `server/email_lib.php` | Eine Fassung für Normalisierung (`email_normalisieren()`), Prüfung (`email_pruefen()`) und Dublettenerkennung (`ist_dublettenfehler()`). **Ohne Abhängigkeiten**, damit `install.php` sie vor der Ersteinrichtung laden kann. |
| Rollenprüfung | `auth_guard.php` | `ist_admin()` ist die einzige Stelle, an der die Frage gestellt wird; `require_admin()` und `ui.php` setzen darauf auf. |
| Maskierung | `assets/html.js` (`EdHtml.escape`) | Eine Fassung, auch in Attributpositionen sicher (fünf Zeichen statt drei). Seit Web 4.6.0 in einer eigenen Datei statt in `missiontable.js` — die wird nur von zwei Seiten geladen, gebraucht wird die Maskierung auf fünf. `EdMissionTable.escape`/`.esc` bleiben als Weiterleitung. **Nicht dasselbe** wie `xmlEscape()` in `export.js`: GPX ist XML mit eigenen Regeln. |
| Patientenanzeige | `assets/patient.js` | Eine Entschlüsselungsschleife statt fünf; unterscheidet sichtbar „keine Angaben" von „nicht lesbar". `entschluessleListe()` wird seit Web 4.6.0 von allen Aufrufern benutzt (Tages-, Zeitraum- und Suchansicht, Export, Import-Abgleich, Sicherungslauf) und schreibt je Einsatz `_pat` und `_patState`. |
| Migrationsschutz | `update.php` (`inhalt_zaehlen()`) | Destruktive Migrationen tragen `zerstoert` (Klartext, was verlorenginge) und optional `inhalt` (Spalten, deren Inhalt die Ausführung blockiert). Eine blockierte Migration hält die Kette **nicht** an — sie hat nichts getan, anders als ein Fehler. |
| Blockabfrage | `db.php` (`sql_in_bloecken()`) | Eine Abfrage je Tabelle statt einer je Datensatz, in Blöcken zu 1000 IDs. Benutzt von Export, Tagesansicht und Sicherung. Die Vorlage trägt `{IDS}` und ist **keine** Formatzeichenkette — ein Prozentzeichen im SQL bleibt ein Prozentzeichen. |
| Formatkennung des Chiffretexts | `assets/crypto.js` (`CHIFFRE_PRAEFIX`), `validate_lib.php` (`PAT_BLOB_RE`, `WRAP_RE`) | `edk1:` vor jedem Chiffretext. Schreiben immer, Lesen großzügig (keine Kennung = erste Fassung), unbekannte Kennung wird als solche gemeldet. Betrifft `pat_blob` **und** beide Schlüsselhüllen — sie kommen aus derselben Funktion. |
| Rundenzahl der Ableitung | `db.php` (`KDF_ITER_ZIEL`, `KDF_ITER_LISTE`), `users.kdf_iter` | Je Konto gespeichert und gelesen, nicht angenommen. `deriveKeys()` verlangt sie als **Pflichtparameter ohne Vorgabewert** — ein Vorgabewert ließe jede vergessene Aufrufstelle stillschweigend mit dem alten Wert rechnen, und das fiele erst bei der nächsten Anhebung auf. Der Salz-Endpunkt nennt jeder Adresse dieselbe **Liste**, damit er nicht verrät, welche Konten es gibt. **Beim Anheben von `KDF_ITER_ZIEL` muss der bisherige Wert in `KDF_ITER_LISTE` stehen bleiben**, sonst kann sich kein Bestandskonto mehr anmelden; die Wartungsseite meldet diesen Zustand unter „Schlüsselableitung". |
| Wiederherstellungsschlüssel | `assets/crypto.js` (`pruefeRecoveryCode()`) | Prüft Länge und Alphabet **vor** der Ableitung und unterscheidet Tippfehler von falschem Zettel. Ohne die Prüfung entsteht aus einer krummen Eingabe klaglos ein falscher Schlüssel, und die Meldung lautet in beiden Fällen „passt nicht". |
| Passwortgüte | `assets/pwquality.js` | Mindestlänge im Skript statt nur als HTML-Attribut, Stärkeanzeige, Abgleich gegen häufige Passwörter. Seit Web 4.7.0 an allen fünf Stellen eingebunden: Erstvergabe, Zurücksetzen, Passwortwechsel, Backup-Passwort, Export-Archivpasswort. Vorher lag der Baustein ungenutzt neben `minlength`-Attributen. |
| Boolesche Freitextsuche | `assets/suchtext.js` (`EdSuchtext.pruefer()`) | Ab Web 7.0.0. Zerlegt eine Sucheingabe in ein Prädikat über den Heuhaufen: UND / ODER / NICHT, Klammern, Phrasen. Ohne Operator verhält sie sich wie die alte Wortliste. Scheitert **nie** an einer Eingabe — die Trefferliste rechnet bei jedem Tastendruck, also ist eine halbfertige Eingabe der Normalfall. Ohne Kenntnis der Seite und darum ohne die Seite prüfbar. |
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

**Backup:** regelmäßiger MySQL-Dump (alle Tabellen; `mysqldump` oder
Hoster-Backup). Wiederherstellung: Dump einspielen; `config.php` bleibt
unberührt. Die Uhr sendet nach einer Wiederherstellung fehlende jüngste Daten
idempotent nach, sofern lokal noch vorhanden.

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
Browser, keine Sicherung. Betroffen sind die Phasenzeiten im Einsatzformular
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
aktiv? Connect-IQ-Einstellungen (Server-Domain, ID, Schlüssel)? Uhr online
(Handy-Kopplung/WLAN)? Anzeige „Sync ausstehend" verschwindet nach
erfolgreichem Upload.

## 8. Backlog

Die offenen Punkte stehen in einer eigenen Datei: **`Backlog.md`**. Dort sind
sie durchnummeriert; Verweise aus Code und Dokumentation nennen die Nummer
(z. B. „Backlog Nr. 10").

---

## Admin-Sicherungen (A8, seit Web 5.9.0)

**Zweck.** Administration soll Konten sichern und wiederherstellen können, ohne
Einblick in die Daten zu bekommen. Der Serverteil war im Kern vorhanden:
`edbak_build()` liefert das vollständige Datenpaket und behält `pat_blob` als
Chiffretext, `edbak_restore()` übernimmt ihn unverändert.

**Ablage.** `server/sicherungen/<kontokennung>/`, je Ordner eine
`konto.json` (Begleitdatei **und** Verzeichnis) und höchstens drei Pakete
`<zeitstempel>_<zufall>.json`. Nicht in der Datenbank: Ein Paket liegt bei
größeren Beständen im zweistelligen MB-Bereich, `max_allowed_packet` liegt auf
geteiltem Webspace oft unveränderlich bei 16 MB — und eine Sicherung im selben
Behälter wie das Gesicherte ist keine Rückfallebene.

**Zwei Schranken gegen den Abruf über den Browser**, dasselbe Muster wie bei der
Nachweisdatei der Ersteinrichtung (M1-11): eine `.htaccess` mit
`Require all denied`, die `edbak_ablage_bereit()` bei **jedem** Schreibzugriff
nachlegt, und der nicht erratbare Ordnername.

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
Der letzte Schritt ist Absicht: Das Feld `daten` **ist** ein Backup der
Formatversion 5, und ein zweiter Rückspielpfad wäre eine zweite Stelle, an der
dieselben Fehler zu machen sind.

**Grenze, die im Handbuch steht und hier wiederholt gehört:** Ohne
Wiederherstellungsschlüssel ist ein neu aufgesetztes Konto nicht
wiederherstellbar. Das ist kein Mangel der Umsetzung, sondern die Folge der
Ende-zu-Ende-Verschlüsselung — der Schlüssel existiert nirgends sonst.
