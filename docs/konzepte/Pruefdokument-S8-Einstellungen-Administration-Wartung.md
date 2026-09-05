# Prüfdokument S8 — Einstellungen, Administration und Wartung

**Zum Konzept** `Konzept-S8-Einstellungen-Administration-Wartung.md`,
Abschnitt 8 (Prüfprotokoll-Soll). Geführt nach K9: Was ist geprüft, wie, mit
welchem Ergebnis; was ist noch offen, und wie wird es geprüft. Wird nach jedem
Arbeitspaket fortgeschrieben und mit dem Konzept gepusht (K7).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 05.09.2026 — angelegt mit dem Konzept; **noch nichts geprüft** (Umsetzung nicht begonnen) |
> | Geprüft | — |
> | Offen | P-01 bis P-42 |
> | Fehlerfunde | keine |
> | Prüfumgebung | Claude-Code-Umgebung mit laufender Anwendung (PHP, MariaDB, Playwright); Kopie der Produktivdaten für P-02, P-10, P-21, P-24 |
> | Ist-Bilder | `00-ist-*` in `docs/konzepte/konzept-s8/mockups/` — **vor AP1 aufnehmen** (Wartungsseite, Backups, Backup-Ziele, Komplett-Backup, Rechtstexte, NutzerInnen, Kontoseite, Geräte, Leiste und Übersicht; 360, 720, 1024, 1280) |

---

## 1. Prüfpunkte

Stand: `offen` · `geprüft` · `Fehler` (mit Fund-Nummer) · `entfällt` (mit Grund).

### AP1 — Rolle „BetreiberIn"

| Nr. | Was | Wie | Stand | Ergebnis |
|---|---|---|---|---|
| P-01 | Rechtematrix 5.3 | drei Konten, jede Seite je Rolle, 403 dokumentiert | offen | |
| P-02 | Migration auf Kopie der Produktivdaten | Dump, Migration, Rollen vorher/nachher, zweiter Lauf ohne Änderung | offen | |
| P-03 | Schutz des letzten BetreiberIn-Kontos | Rückstufung und Löschung, Oberfläche und direkter POST | offen | |
| P-04 | `install.php` legt BetreiberIn an | Ersteinrichtung auf leerer Datenbank | offen | |
| P-05 | keine wörtliche Rollenprüfung außerhalb des Wächters | `grep -rn "'admin'" server/` | offen | |
| P-06 | Demo-Reset lässt Rollen unberührt | Reset, `users.role` vergleichen | offen | |
| P-30 | Bilderlauf berührte Seiten | acht Breiten | offen | |

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

Keine — Umsetzung nicht begonnen.

| Nr. | Paket | Fund | Behoben in | Stand |
|---|---|---|---|---|

---

## 3. Reste für Rahmenplan Abschnitt 6

Werden beim Abschluss (Konzept 9.2) übertragen. Kandidaten, die sich schon
abzeichnen: Z-01 (Mailzustellung nicht aufgezeichnet → Statuszeile nur
„eingerichtet"), Z-03 (Links fehlen → Knopf entfällt, Text bleibt).

---

## Änderungsverlauf

| Datum | Was |
|---|---|
| 05.09.2026 | Angelegt mit Schritt 4 des Konzepts: P-01 bis P-42 je Paket, Klärpunkte Z-01 bis Z-03 |
