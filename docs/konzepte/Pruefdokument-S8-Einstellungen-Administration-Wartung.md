# Prüfdokument S8 — Einstellungen, Administration und Wartung

**Zum Konzept** `Konzept-S8-Einstellungen-Administration-Wartung.md`,
Abschnitt 8 (Prüfprotokoll-Soll). Geführt nach K9: Was ist geprüft, wie, mit
welchem Ergebnis; was ist noch offen, und wie wird es geprüft. Wird nach jedem
Arbeitspaket fortgeschrieben und mit dem Konzept gepusht (K7).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 05.09.2026 — **AP1 und AP2 geprüft** (Web 15.0.0 und 15.1.0) |
> | Geprüft | P-02 bis P-13 vollständig · P-01 **teilweise** (Zwischenstand nach AP1, siehe dort) · P-30 für die berührten Seiten (AP1: 4, AP2: 5) |
> | Offen | P-01 (Endfassung nach AP5), P-09 **zur Hälfte** (zweiter Browser fehlt im Container), P-14 bis P-29, P-31 bis P-42 |
> | Fehlerfunde | **sechs, alle behoben:** F-S8-P-01 bis -06 (Abschnitt 2) |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI), MariaDB 10.11.14, Chromium über Playwright; lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto). **Keine Kopie der Produktivdaten** — für P-02, P-10, P-21 und P-24 steht deshalb die Prüfung an echten Mengen aus (siehe „Was nicht geprüft werden konnte") |
> | Ist-Bilder | **aufgenommen vor AP1** — vollständiger Lauf: 336 Einzelbilder, 42 Kontaktbögen, 8 Breiten (360, 390, 420, 768, 1024, 1280, 1440, 1920); Überlauf 0, Konsolenfehler 0, Knöpfe ≠ 44 px 0. Gegenprobe auf gleiche Bilder: 336 Dateien, **333 verschiedene Prüfsummen** — die drei Doppelten sind die Tagesübersicht mit und ohne Schublade bei 1024, 1280 und 1440, wo die Schublade bauartbedingt nichts tut. Zehn davon liegen als Kontaktbogen (360, 768, 1024, 1280) unter `docs/konzepte/konzept-s8/ist/`; die 336 Einzelbilder nicht — `tools/screenshots/ausgabe/` steht mit Grund in `.gitignore`, und sie sind aus dem Commit `cecbc76` jederzeit neu zu erzeugen |

---

## 0. Was nicht geprüft werden konnte — und warum

Steht bewusst vor der Prüfliste (K9).

| Was | Warum nicht | Wie es doch geprüft wird |
|---|---|---|
| **P-02 an echten Daten** — Migration auf einer Kopie der Produktivdaten | Der Wegwerf-Container hat keinen Zugang zum Produktivserver, und es liegt kein Dump vor | Geprüft ist die Migration am Referenzbestand (2 Konten) und in beide Richtungen (Erst- und Zweitlauf). Der Produktivlauf bleibt der Abnahme vorbehalten: **Betrieb → Updates** aufrufen (bis zum Ausrollen von 15.1.0: `update.php`) und danach in der NutzerInnen-Liste nachsehen, dass jedes vorher als „Admin" geführte Konto jetzt „BetreiberIn" heißt |
| **Mengen und Laufzeiten** (P-10, P-21, P-24) | dieselbe Ursache: kein Produktivbestand | P-10 ist am Referenzbestand geprüft (0 % Abweichung); P-21 und P-24 folgen in AP4. Am echten Bestand einmal nach dem Ausrollen |
| **Zweiter Browser** (P-09) | im Container steht nur Chromium; WebKit und Gecko fehlen | bleibt eine Sichtprüfung des Auftraggebers, wie schon die P3-Reste |
| **Bedienung mit Tastatur und Vorleser** | nicht automatisierbar in diesem Aufbau | Prüfliste Abschnitt 3 |

---

---

## 1. Prüfpunkte

Stand: `offen` · `geprüft` · `Fehler` (mit Fund-Nummer) · `entfällt` (mit Grund).

### AP1 — Rolle „BetreiberIn" · **erledigt 05.09.2026, Web 15.0.0**

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-01 | Rechtematrix 5.3 | drei Konten (NutzerIn, Admin, BetreiberIn), zwölf Seiten je Rolle über Playwright aufgerufen, Statuscode aufgezeichnet | **teilweise** | **36 Aufrufe.** NutzerIn: 4 × 200 (Einstellungen, Profil, Geräte, Import/Export), **8 × 403**. Admin und BetreiberIn: je 12 × 200. Menüblöcke der Leiste: NutzerIn 1 („Einstellungen"), Admin 2, BetreiberIn 2. **Noch nicht die Endfassung** — die drei Betrieb-Seiten (`admin_komplettsicherung.php`, `admin_sicherungsziele.php`, `update.php`) tragen bis zu ihrem Umzug in den Block Betrieb (AP5) weiter `require_admin()`; das ist so entschieden (U-AP1-03). **Nach AP5 zu wiederholen**, dann muss ein Admin dort 403 bekommen und drei Menüblöcke dürfen nur der BetreiberIn erscheinen |
| P-02 | Migration | am Referenzbestand: Rollen vorher/nachher, ENUM vorher/nachher, zweiter Lauf | **geprüft** | Vorher `enum('user','admin')`, 1 × `admin`, 1 × `user`. Nach dem Lauf `enum('user','admin','betreiberin')`, 1 × `betreiberin`, 1 × `user` (Demo unberührt). **Zweiter Lauf: „Bereits angewendet", Rollen unverändert.** Migrationsregister gegengezählt: **43 in `schema.sql` = 43 in `update.php`**. An echten Daten steht die Prüfung aus (Abschnitt 0) |
| P-03 | Schutz des letzten BetreiberIn-Kontos | **fünf** Versuche als direkter POST aus einer angemeldeten Sitzung, nicht über die Oberfläche | **geprüft** | (1) Letzte BetreiberIn stuft sich selbst zurück → abgewiesen, Rolle unverändert, Meldung „Das ist das letzte Konto mit der Rolle ‚BetreiberIn'. Es lässt sich nicht zurückstufen …". (2) Admin löscht das letzte BetreiberIn-Konto → abgewiesen, Konto steht. (3) Admin vergibt „BetreiberIn" → abgewiesen, Meldung „Die Rolle ‚BetreiberIn' vergibt und entzieht nur eine BetreiberIn …". (4) Admin legt ein Konto mit `role=betreiberin` an → Konto entsteht als **Admin**. (5) Gegenprobe: BetreiberIn vergibt die Rolle → **geht**. Dazu die Anzeige: `select[name=role]` effektiv `:disabled` = wahr, Deckkraft des Feldsatzes 0,55; Speichern von Name und Adresse funktioniert trotzdem („Name gespeichert.", Rolle unverändert) |
| P-04 | `install.php` legt BetreiberIn an | Ersteinrichtung auf leerer Datenbank über den echten HTTP-Weg (`lokal_einrichten.sh`) | **geprüft** | Erstes Konto `admin@gen-em.org` mit `role = betreiberin`; ENUM dreiwertig; Migration `2026_09_05_rolle_betreiberin` als **`skipped`** verbucht — der Eintrag in `schema.sql` greift |
| P-05 | keine wörtliche Rollenprüfung außerhalb des Wächters | `grep -rn "'admin'" server/` | **geprüft** | **10 Fundstellen, keine davon ein Vergleich in einem Schreibweg:** der Rollenkatalog selbst (`db.php` 3 ×), die Auswahlliste (`auth_guard.php` 2 ×), die Migration (`update.php` 2 ×), die Sortierstufe und der Rückfall beim Anlegen (`admin_users.php` 2 ×) und **ein Menüschlüssel** (`ui.php:704`, `'admin' => ['admin_users.php', …]` — eine Kennung, keine Rolle) |
| P-06 | Demo-Reset lässt Rollen unberührt | Demo-Konto auf `admin` gesetzt, `demo_zuruecksetzen()` ausgeführt, alle Rollen verglichen | **geprüft** | Vorher `[betreiberin, admin]`, nachher `[betreiberin, user]` — das Demo-Konto fällt auf `user` zurück (E-P1-09), die BetreiberIn bleibt unberührt |
| P-30 | Bilderlauf berührte Seiten | acht Breiten, Seiten `01-anmeldung`, `30-einstellungen-profil`, `40-nutzerinnen`, `41-kontoseite` | **geprüft** | **32 Einzelbilder, 4 Kontaktbögen.** Überlauf 0, Konsolenfehler 0, Knöpfe ≠ 44 px 0 |
| — | Wortliste | `python3 tools/wortliste/wortliste.py`, alle fünf Bereiche | **geprüft** | **0 Treffer außerhalb der Ausnahmen (in 0 Zeilen), 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen.** 80 Regeln, 80 gegriffen. Bereiche: a 89 Dateien/166 Treffer, b 30/46, c 8/253, d 2/3, e 35/2 — alle erklärt. **Erst nach F-S8-P-01 und -02 lauffähig** |
| — | Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py`, gegen denselben Lauf im Stand vor AP1 (Commit `cecbc76`, eigener Arbeitsbaum) | **geprüft** | **280 Befunde vorher, 280 nachher — unverändert.** AP1 fasst weder Stylesheet noch Klassennamen an |
| — | Syntax | `php -l` über alle Dateien in `server/` und `server/api/` | **geprüft** | 0 Fehler |

### AP2 — Betrieb, Teil 1 · **erledigt 05.09.2026, Web 15.1.0**

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-07 | Wartungsmodus von Updates aus | über echtes HTTP: einschalten, fremde Seite aufrufen, Kopfzeilen lesen, ausschalten, Schalterdatei prüfen | **geprüft** | Ein: fremde Seite **503** mit `Retry-After: 300`; auf `betrieb_updates.php` der orange Balken „Wartungsmodus seit 05.09.2026 … von admin@gen-em.org" und die Plakette **„Wartung"** an der Karte; Knopf heißt „Wartungsmodus ausschalten". Aus: `server/wartung.lock` **weg**, Startseite antwortet wieder. Bild als Beleg: `46a-betrieb-updates-wartung-768.png`. Dazu die Wartungsprobe: **42 Erwartungen, 0 nicht erfüllt** (siehe F-S8-P-06) |
| P-08 | Migrationslauf Ausstehend / Blockiert / Fehler | **vier** Testmigrationen an den Katalog gehängt: eine läuft (`CREATE TABLE pruef_p08`), eine ist blockiert (Inhaltsschutz), eine scheitert (kaputtes SQL), eine steht dahinter | **geprüft** | Vorschau vor dem Lauf: **4 ausstehend, 1 blockiert**, Kästchen **nur** an der blockierten, Register 0 Testeinträge, keine Testtabelle. Nach dem Lauf: **nur `pruef_p08` existiert**, im Register **nur** `9999_01_01_pruef_steht_aus: applied` — die gescheiterte ist **nicht** verbucht, die dahinter trägt „steht aus · NICHT MEHR VERSUCHT — der Lauf hat davor abgebrochen." Zählung **3** in allen drei Ansichten (Karte, Liste, Meldung). Vorher zeigte die Karte an dieser Stelle eine zu **kleine** Zahl (F-S8-P-05). Testmigrationen danach entfernt, Register und Tabelle aufgeräumt |
| P-09 | Kopieren-Knopf | Chromium mit erteilter Zwischenablage-Berechtigung: Knopf drücken, `navigator.clipboard.readText()` gegen den angezeigten Wert halten | **halb geprüft** | Inhalt der Zwischenablage **zeichengleich** mit dem angezeigten Wert (Token-Adresse, **64 Zeichen**), Knopftext wechselt auf „kopiert" und nach 2 s zurück. Ohne JavaScript ist der Knopf `hidden` und der Wert bleibt markierbar (im Markup geprüft). **Der zweite Browser fehlt** (Abschnitt 0) — der Rückfallweg „markiert — Strg+C" ist damit nur im Code belegt, nicht im Lauf |
| P-10 | Speichermessung | `speicher_datenbank_bytes()` und `speicher_dateien_bytes()` gegen `du -sb --exclude=sicherungen server/` und die Summe aus `information_schema` | **geprüft** | Dateien **7 677 397 B** = `du -sb` **7 677 397 B**; Datenbank **6 848 512 B** = SQL-Summe **6 848 512 B**. **Abweichung 0 %** — verlangt waren < 2 %. (Der Dateiwert wächst mit jedem Paket; die Gegenprobe lief zuletzt am 05.09.2026 nach der Dokumentation.) An **echten Mengen** steht die Messung aus (Abschnitt 0) |
| P-11 | Schwellenfärbung | fünf Belegungsgrade gegen die Schwellen 70/90 gerechnet und die Klasse des Balkens gelesen | **geprüft** | 36 % **blau** · 71 % **orange** · 88 % **orange** · 95 % **rot** · 100 % **rot**. Balken, Legende und Warnmail benutzen dieselbe Funktion `speicher_ton()` — sonst färbte sich der Balken, während die Mail schweigt |
| P-12 | Regelwerte nach Umzug unverändert | über das Formular der neuen Seite schreiben, dann `app_state` lesen | **geprüft** | `adminbackup_grenze_gb = 4`, `adminbackup_schwellen = 60,80`, `webspace_gb = 25` geschrieben und zurückgelesen; die Schlüssel `adminbackup_aufbewahrung`, `adminbackup_mail` und `versand_auto` **unverändert** — sie bleiben auf `admin_sicherungen.php` |
| P-13 | `update.php` übergangsweise nur Logo | die drei alten Aktionen als POST mit gültigem CSRF an `update.php` schicken | **geprüft** | `run`, `wartung_an`, `jobs_token_neu` → je **200**, keine Wirkung: kein Migrationslauf, keine Schalterdatei, kein neues Token. Die Seite zeigt die Karte „Wo es jetzt steht" mit drei Verweisen und die Logo-Karte, keine Migrationsliste und keinen Wartungsknopf. **Nach AP3 zu wiederholen**, dann muss ein Aufruf mit 302 antworten |
| P-30 | Bilderlauf Updates, Hintergrundjobs, Servereinstellungen | acht Breiten, Seiten `45-wartung`, `46-betrieb-updates`, `46a-betrieb-updates-wartung`, `47-betrieb-jobs`, `48-betrieb-server` | **geprüft** | **40 Einzelbilder, 5 Kontaktbögen.** Überlauf 0, Konsolenfehler 0, Knöpfe ≠ 44 px 0. Gegenprobe auf gleiche Bilder: 40 Dateien, **40 verschiedene Prüfsummen** — keine Seite zeigt das Bild einer anderen. Zusätzlich am Bild nachgesehen (K9, „grüne Zahl benennt das Gemessene"): `46a-…-768.png` trägt den Wartungsbalken, die Plakette „Wartung" und den Ausschalt-Knopf |
| — | Wartungsprobe | `php tools/wartungsprobe/probe.php` gegen die lokale Installation | **geprüft** | **42 Erwartungen, 0 nicht erfüllt.** Darunter: alle drei Betriebsseiten im Wartungsmodus 200 (F-S8-P-04), die Ausnahmeliste **genau** neun Einträge, das 503 in **0,3 ms** statt 1,6 ms. Vor der Anpassung: 6 von 40 nicht erfüllt (F-S8-P-06) |
| — | Migrationsregister | Katalog gegen die `skipped`-Liste gezählt, nach dem Herauslösen nach `migration_lib.php` | **geprüft** | **43 in `schema.sql` = 43 in `migration_lib.php`** |
| — | Wortliste | `python3 tools/wortliste/wortliste.py`, alle fünf Bereiche, **nach** der Dokumentation | **geprüft** | **0 Treffer außerhalb der Ausnahmen (in 0 Zeilen), 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen.** 82 Regeln, 82 gegriffen (zwei neue für die Logowahl der Installation, eine umgehängt auf `migration_lib.php`). Bereiche: a 94 Dateien/166 Treffer, b 31/46, c 8/254, d 2/3, e 35/2 |
| — | Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py` | **geprüft** | **280 Befunde vor AP2, 286 nachher.** Die sechs sind erklärt: **2 ×** `style="width:…%"` (die Balkenbreite ist ein gerechneter Wert und gehört nicht in eine Klasse), **4 ×** Unicode in Kommentaren und Menüpfaden. Dazu **4 informative** „Regel im Stylesheet, im Markup nicht gefunden" für `sb-konto`, `sb-komplett`, `sb-db`, `sb-dateien` — sie entstehen zur Laufzeit in `speicher_balken()` |
| — | Syntax | `php -l` über alle **95** Dateien in `server/` und `server/api/` | **geprüft** | 0 Fehler |

### AP3 — Verwaltung

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-13 | `update.php` Weiterleitung | 302 auf Updates, mit und ohne Parameter | offen | |
| P-14 | Backup-Wege verhaltensgleich | Prüfdokument S7 Paket E wiederholen | offen | |
| P-15 | Logo von Installation aus | Kopfleiste, Browser-Symbol, Anmeldeseite | offen | |
| P-16 | Weiterleitung `admin_rechtstexte.php` | mit und ohne Parameter | offen | |
| P-17 | Freigabe-Zustandszeile | freigeben, widerrufen, NutzerInnen-Seite parallel | offen | |
| P-18 | kein Platzhalter „Abonnement" | Kontoseite | offen | |
| P-19 | Wortliste (Teil 1) | Suche in `server/`: „Sicherung" als Substantiv, „Admin-Backup", „Datenbank-Update" | offen | |
| P-30 | Bilderlauf Installation, Konto-Backups, Kontoseite, NutzerInnen | acht Breiten | offen | |

### AP4 — Betrieb, Teil 2

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-11 | Schwellen auf Status | wie AP2, Zeile Speicher | offen | |
| P-20 | Ampel je Zeile | jeden Zustand erzwingen (Wartung an, Job-Fehler, Ablage schreibgeschützt, Schlüssel entfernt, Plan verletzt, Migration ausstehend, Huckepack) | offen | |
| P-21 | Statistikzahlen | Hand-SQL je Kennzahl, Demo ausgeschlossen | offen | |
| P-22 | Gerätemodelle-Tabelle | Sortierung je Spalte beide Richtungen; CSV in Excel; Hersteller je Gerät | offen | |
| P-23 | Demo-Konto in keiner Zahl | Demo anlegen, Zahlen unverändert | offen | |
| P-24 | Seitenaufbau | Status < 300 ms, Statistik < 500 ms auf Produktivdaten | offen | |
| P-30 | Bilderlauf Status, Statistik | acht Breiten | offen | |
| Z-01 | **Zu klären:** wird eine letzte Mailzustellung aufgezeichnet? | Code lesen (`email_lib.php`); sonst Zeile nur „eingerichtet" | offen | |
| Z-02 | **Zu klären:** was sendet die Wear-OS-App als `art`? | Android-Quelle `wear`/`uhr`, Kopplungsblock | offen | |

### AP5 — Menü und Leiste

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-24 | Zähler-Abfrage | < 20 ms, kein Seitenaufbau messbar langsamer | offen | |
| P-25 | Menü je Rolle | drei Konten, Leiste und Übersicht | offen | |
| P-26 | Zähler | Werte gegen Status, Updates, Hintergrundjobs, Konto-Backups | offen | |
| P-27 | Unterpunkte | Sprung, Markierung ein- und zweispaltig, Schublade ohne Markierung | offen | |
| P-28 | Akkordeonzustand | Seitenwechsel, neue Sitzung | offen | |
| P-29 | Fettdruck nur aktiv | Leiste 360 und 1280 | offen | |
| P-30 | Bilderlauf Leiste, Übersicht, alle Seiten mit Zweispaltenregel | acht Breiten | offen | |

### AP6 — Einstellungen

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-09 | Kopieren an Setz-Link, Serverschlüssel, Zugangsdaten | wie AP2 | offen | |
| P-31 | Kopplung verhaltensgleich | Prüfdokument S5 wiederholen, drei Zustände | offen | |
| P-32 | APK | Download, SHA-256 gegen Anzeige | offen | |
| P-33 | Geräte-Handlungen im ⋯-Menü | umbenennen, deaktivieren, aktivieren, entkoppeln | offen | |
| P-34 | Filterreihe | 780 px Inhaltsbreite eine Zeile, 360 px gleichmäßiger Umbruch | offen | |
| P-30 | Bilderlauf Geräte, NutzerInnen | acht Breiten | offen | |
| Z-03 | **Zuarbeit:** Play-Store-Beitrittslink, Connect-IQ-Adresse | Rahmenplan Abschnitt 6 | offen | |

### AP7 — Bedienhöhe

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-35 | Bedienhöhe gemessen | Messwerkzeug: 44 unter 1024 und Touch, 36 ab 1024 Zeigergerät | offen | |
| P-36 | Mindestzielgröße | kein Ziel unter 24 × 24 px (Messwerkzeug über alle Bedienelemente) | offen | |
| P-37 | Fokus und Überlappung | Tastaturlauf 1024, 1200, 1440, 1920 | offen | |
| P-38 | Kopplungscode lesbar | Feld bei 36 px | offen | |
| P-30 | Bilderlauf alle Seiten, ab 1024 zusätzlich Zeigergerät | acht Breiten × zwei Modi | offen | |

### AP8 — Abschluss

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-19 | Wortliste (Teil 2) | Suche in `docs/`: „Wartung" als Seite, „Administration" als Block | offen | |
| P-39 | Handbuch-Begriffe | wie P-19, dazu Gliederung Verwaltung / Betrieb | offen | |
| P-40 | Vollständigkeit | Tabelle 2.3 des Konzepts gegen Zielbild: jeder Eintrag verortet oder entfallen | offen | |
| P-41 | Stilvergleich | Soll-Ist-Liste elf Mockups gegen die Bilder | offen | |
| P-42 | Versionen und Changelog | `version.php`, `CHANGELOG.md`, Handbuch-Kopf, `android/version.properties` unverändert | offen | |

---

## 2. Fehlerfunde (F-S8-P-nn)

| Nr. | Paket | Fund | Behoben in | Stand |
|---|---|---|---|---|
| F-S8-P-01 | AP1 | **`tools/wortliste/ausnahmen.json` ist kein gültiges JSON.** Zwischen den Einträgen `herkunft-wertevorrat-garmin` und `technik-abgrenzung-beide-uhren` fehlt das trennende `},{`; die Datei bricht mit `JSONDecodeError: Expecting ',' delimiter: line 543` ab. Verursacht von der Konfliktauflösung im Merge `589982b` (Schritt 6). **Die Wortliste kann seither nicht gelaufen sein** — sie meldet keine Null, sie meldet gar nichts, und genau davor warnt `CLAUDE.md` 6. Der Fund blockierte die laufende Arbeit und wurde deshalb sofort behoben (K4) | AP1 | **behoben** — 80 Regeln lesbar, Lauf 0/0/0 |
| F-S8-P-02 | AP1 | **Ungenutzte Ausnahme nach der Reparatur.** `technik-abgrenzung-beide-uhren` erwartet in `docs/Technik.md` den Wortlaut „Die **Garmin-Uhr** hält es genauso"; dort stand „Die Uhr-App hält es genauso" — dieselbe Merge-Auflösung. Die Ausnahme begründet selbst, warum das falsch ist: Der Satz steht im Android-Kapitel (5a) und vergleicht mit der Connect-IQ-Uhr; „Uhr-App" ist genau dort zweideutig, wo es auf den Unterschied ankommt (S5 Paket E, 03.09.2026) | AP1 | **behoben** — Wortlaut wiederhergestellt, Ausnahme greift wieder |
| F-S8-P-03 | AP1 | **Ein `disabled` an einem Eingabefeld ist unsichtbar.** `.feld-eingabe` setzt `background` und `color` selbst und überschreibt damit die Graufärbung des Browsers; eine Regel `.feld-eingabe:disabled` gibt es nicht. Das gesperrte Rollenfeld sah aus wie ein bedienbares. Betrifft grundsätzlich jedes einzeln gesperrte Feld, nicht nur dieses | AP1 | **behoben ohne Stylesheet-Änderung** — das Feld steht in einem `.feldsatz-gesperrt`-Feldsatz (S3/AP10, für genau diesen Zweck gebaut). Gemessen: `:disabled` wahr, Deckkraft 0,55. Eine Regel `:disabled` am Feld selbst bleibt ein **Kandidat für AP7** (Bedienhöhe fasst das Stylesheet ohnehin an) |
| F-S8-P-04 | AP2 | **Der Wartungsmodus sperrte die Seite mit dem Ausschalter aus.** `betrieb_updates.php` stand nicht in `WARTUNG_AUSNAHMEN`. Einschalten gelang; das Neuladen derselben Seite antwortete mit **503**. Der Weg zurück wäre `rm server/wartung.lock` per SSH gewesen — auf einer Produktivinstallation heißt das: geschlossen, bis jemand mit Shell-Zugang wach wird. Der Fehler entstand, weil die Ausnahmeliste am Dateinamen vergleicht und die alte Seite noch mit dabei war; die neue fiel dadurch niemandem auf | AP2 | **behoben** — alle drei Betriebsseiten stehen in der Liste (neun Einträge). Die Wartungsprobe misst es je Seite einzeln (Erwartung 6), damit es beim nächsten Umzug auffällt |
| F-S8-P-05 | AP2 | **Nach einem Migrationsfehler verschwanden die Migrationen dahinter aus der Anzeige.** Der Lauf bricht beim ersten Fehler ab — richtig so, denn die Reihenfolge ist der Mechanismus. Die Ausgabe zeigte danach aber **nur die versuchten**, und die Karte zählte daraufhin weniger Ausstehende, als es gab. Die Zahl war also **kleiner** als die Wahrheit, und das ist die gefährliche Richtung: „2 stehen aus" nach einem Fehler, wo 5 offen sind | AP2 | **behoben** — jede Migration hinter dem Abbruch trägt `steht aus` mit dem Text „NICHT MEHR VERSUCHT — der Lauf hat davor abgebrochen." Gemessen mit vier Testmigrationen (eine scheiternd): Zählung **3** in Karte, Liste und Meldung |
| F-S8-P-06 | AP2 | **Die Wartungsprobe maß gegen die alte Entscheidung.** Nach AP2 meldete `tools/wartungsprobe/probe.php` **6 von 40 Erwartungen nicht erfüllt**: Sie holte den Schalter von `update.php` (Fälle 6, 13), verlangte die **sechs** alten Ausnahmen (17) und suchte in `login.php` den Vergleich `!== 'admin'` (18), den AP1 durch `rolle_darf_verwalten()` ersetzt hat. Kein Fehler der Anwendung — aber ein Prüfmittel, das ab hier bei jedem Lauf rot gemeldet hätte, ist wertlos: Man gewöhnt sich an das Rot | AP2 | **behoben und erweitert** — Schalter auf `betrieb_updates.php`, das verwaltende Konto der Probe trägt jetzt `betreiberin` (`require_betreiberin()`), zwei neue Erwartungen (die beiden anderen Betriebsseiten, die Übergangsseite). **42 Erwartungen, 0 nicht erfüllt.** `LIESMICH.md` nachgezogen, zwei neue Zeilen in der Fehlertabelle |

---

## 3. Reste für Rahmenplan Abschnitt 6

Werden beim Abschluss (Konzept 9.2) übertragen. Kandidaten, die sich schon
abzeichnen:

- **Z-01** — ob eine letzte Mailzustellung aufgezeichnet wird; falls nicht,
  zeigt die Statuszeile nur „eingerichtet" (zu klären in AP4).
- **Z-03** — **bestätigt am 05.09.2026:** weder der Play-Store-Beitrittslink
  noch die Connect-IQ-Adresse liegen vor. Die Karte „App installieren"
  entsteht in AP6 im Rückfall: Zeilen ohne Knopf, nur erklärender Text; die
  Adressen sind später an je einer Stelle nachzutragen (`PLAY_TEST_URL`,
  `CONNECT_IQ_URL`). Zwei Zeilen dazu stehen seit Fassung 28 in Rahmenplan
  Abschnitt 6.
- **Aus AP1: die Migration auf dem Produktivserver.** Betrieb → Updates aufrufen —
  danach in der NutzerInnen-Liste nachsehen, dass jedes vorher als „Admin"
  geführte Konto jetzt „BetreiberIn" heißt. Das ist zugleich der fehlende
  Teil von P-02.
- **Aus AP1: eine Regel `.feld-eingabe:disabled`** im Stylesheet (F-S8-P-03).
  In AP1 mit dem vorhandenen Baustein umgangen; AP7 fasst das Stylesheet
  ohnehin an und kann sie mitnehmen.
- **Aus AP2: der Rückfallweg des Kopieren-Knopfs** („markiert — Strg+C") ist
  nur im Code belegt, nicht im Lauf — im Container steht nur Chromium, und
  dort greift der reguläre Weg. Sichtprüfung des Auftraggebers in einem
  zweiten Browser (P-09, zweite Hälfte).
- **Aus AP2: die Speichermessung an echten Mengen.** Am Referenzbestand
  stimmt sie auf das Byte (0 % Abweichung); wie sie sich bei einer Datenbank
  im Gigabyte-Bereich verhält — Laufzeit des Verzeichnislaufs, Schätzfehler
  von InnoDB —, zeigt erst der Produktivbestand. Nach dem Ausrollen einmal
  Betrieb → Servereinstellungen aufrufen und die Zahlen gegen die Angaben des
  Hosters halten.
- **Aus AP2: die Karten „Schlüsselableitung" und „Umgebung" sind
  vorübergehend nirgends sichtbar.** Sie ziehen in AP4 auf
  `betrieb_status.php`. Bis dahin ist ein Konto mit unbrauchbarer `kdf_iter`
  in der Oberfläche nicht zu erkennen — der Weg dorthin steht in
  `docs/Technik.md` 7. **Kein Rest über S8 hinaus**, aber ein offener Punkt
  zwischen AP2 und AP4.

---

## Änderungsverlauf

| Datum | Was |
|---|---|
| 05.09.2026 | **AP2 geprüft** (Web 15.1.0): P-07 bis P-13 und P-30 mit Zahlen belegt (P-09 halb — der zweite Browser fehlt); Wartungsprobe 42/0, Migrationsregister 43 = 43, Wortliste 0/0/0 mit 82 Regeln, Vollständigkeit 280 → 286 mit Erklärung je Befund, `php -l` über 95 Dateien; drei Fehlerfunde aufgenommen und behoben (F-S8-P-04 bis -06); vier Reste ergänzt |
| 05.09.2026 | **AP1 geprüft** (Web 15.0.0): P-02 bis P-06 und P-30 mit Zahlen belegt, P-01 als Zwischenstand mit Wiederholung nach AP5; Wortliste 0/0/0 und Vollständigkeit 280 = 280; drei Fehlerfunde aufgenommen und behoben; Abschnitt 0 („was nicht geprüft werden konnte") angelegt; Ist-Bilder aufgenommen und gegengeprüft |
| 05.09.2026 | Angelegt mit Schritt 4 des Konzepts: P-01 bis P-42 je Paket, Klärpunkte Z-01 bis Z-03 |
