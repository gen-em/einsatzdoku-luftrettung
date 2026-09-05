# Prüfdokument S8 — Einstellungen, Administration und Wartung

**Zum Konzept** `Konzept-S8-Einstellungen-Administration-Wartung.md`,
Abschnitt 8 (Prüfprotokoll-Soll). Geführt nach K9: Was ist geprüft, wie, mit
welchem Ergebnis; was ist noch offen, und wie wird es geprüft. Wird nach jedem
Arbeitspaket fortgeschrieben und mit dem Konzept gepusht (K7).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 05.09.2026 — **AP1 geprüft** (Web 15.0.0) |
> | Geprüft | P-02, P-03, P-04, P-05, P-06 vollständig · P-01 **teilweise** (Zwischenstand nach AP1, siehe dort) · P-30 für die drei berührten Seiten |
> | Offen | P-01 (Endfassung nach AP5), P-07 bis P-29, P-31 bis P-42 |
> | Fehlerfunde | **drei, alle behoben:** F-S8-P-01 bis -03 (Abschnitt 2) |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI), MariaDB 10.11.14, Chromium über Playwright; lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto). **Keine Kopie der Produktivdaten** — für P-02, P-10, P-21 und P-24 steht deshalb die Prüfung an echten Mengen aus (siehe „Was nicht geprüft werden konnte") |
> | Ist-Bilder | **aufgenommen vor AP1** — vollständiger Lauf: 336 Einzelbilder, 42 Kontaktbögen, 8 Breiten (360, 390, 420, 768, 1024, 1280, 1440, 1920); Überlauf 0, Konsolenfehler 0, Knöpfe ≠ 44 px 0. Gegenprobe auf gleiche Bilder: 336 Dateien, **333 verschiedene Prüfsummen** — die drei Doppelten sind die Tagesübersicht mit und ohne Schublade bei 1024, 1280 und 1440, wo die Schublade bauartbedingt nichts tut. Zehn davon liegen als Kontaktbogen (360, 768, 1024, 1280) unter `docs/konzepte/konzept-s8/ist/`; die 336 Einzelbilder nicht — `tools/screenshots/ausgabe/` steht mit Grund in `.gitignore`, und sie sind aus dem Commit `cecbc76` jederzeit neu zu erzeugen |

---

## 0. Was nicht geprüft werden konnte — und warum

Steht bewusst vor der Prüfliste (K9).

| Was | Warum nicht | Wie es doch geprüft wird |
|---|---|---|
| **P-02 an echten Daten** — Migration auf einer Kopie der Produktivdaten | Der Wegwerf-Container hat keinen Zugang zum Produktivserver, und es liegt kein Dump vor | Geprüft ist die Migration am Referenzbestand (2 Konten) und in beide Richtungen (Erst- und Zweitlauf). Der Produktivlauf bleibt der Abnahme vorbehalten: `update.php` aufrufen und danach in der NutzerInnen-Liste nachsehen, dass jedes vorher als „Admin" geführte Konto jetzt „BetreiberIn" heißt |
| **Mengen und Laufzeiten** (P-10, P-21, P-24) | dieselbe Ursache: kein Produktivbestand | in AP2 und AP4 mit dem Messstand am Referenzbestand, danach einmal am echten Bestand |
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

### AP2 — Betrieb, Teil 1

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-07 | Wartungsmodus von Updates aus | ein, fremde Seiten 503 + Retry-After, aus | offen | |
| P-08 | Migrationslauf Ausstehend / Blockiert / Fehler | Test-Migrationen im Register | offen | |
| P-09 | Kopieren-Knopf | zwei Browser, mit/ohne Berechtigung, Cron-Zeile und Token | offen | |
| P-10 | Speichermessung | `du -sb` ohne `sicherungen/`, SQL-Summe, < 2 % | offen | |
| P-11 | Schwellenfärbung | Werte über 70 und 90 % setzen | offen | |
| P-12 | Regelwerte nach Umzug unverändert | `app_state` vorher/nachher | offen | |
| P-13 | `update.php` übergangsweise nur Logo | alte Aktionen ohne Wirkung | offen | |
| P-30 | Bilderlauf Updates, Hintergrundjobs, Servereinstellungen | acht Breiten | offen | |

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
- **Aus AP1: die Migration auf dem Produktivserver.** `update.php` aufrufen —
  danach in der NutzerInnen-Liste nachsehen, dass jedes vorher als „Admin"
  geführte Konto jetzt „BetreiberIn" heißt. Das ist zugleich der fehlende
  Teil von P-02.
- **Aus AP1: eine Regel `.feld-eingabe:disabled`** im Stylesheet (F-S8-P-03).
  In AP1 mit dem vorhandenen Baustein umgangen; AP7 fasst das Stylesheet
  ohnehin an und kann sie mitnehmen.

---

## Änderungsverlauf

| Datum | Was |
|---|---|
| 05.09.2026 | **AP1 geprüft** (Web 15.0.0): P-02 bis P-06 und P-30 mit Zahlen belegt, P-01 als Zwischenstand mit Wiederholung nach AP5; Wortliste 0/0/0 und Vollständigkeit 280 = 280; drei Fehlerfunde aufgenommen und behoben; Abschnitt 0 („was nicht geprüft werden konnte") angelegt; Ist-Bilder aufgenommen und gegengeprüft |
| 05.09.2026 | Angelegt mit Schritt 4 des Konzepts: P-01 bis P-42 je Paket, Klärpunkte Z-01 bis Z-03 |
