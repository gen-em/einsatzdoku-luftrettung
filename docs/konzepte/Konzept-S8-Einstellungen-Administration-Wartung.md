# Konzept S8 — Einstellungen, Administration und Wartung

**Rahmenplan Schritt 7 · Beschluss R61 (02.09.2026) · Konzept nach K1, Sichtung
mit Fable nach R14 · Ablage `docs/konzepte/` (R62), Mockups in
`docs/konzepte/konzept-s8/mockups/` · Nummernkreise E-S8, F-S8, B-S8.**

> **Statusblock (K5, R62)**
>
> | | |
> |---|---|
> | Stand | 05.09.2026 — **AP1 und AP2 erledigt** (Web 15.0.0 und 15.1.0). Umsetzung auf `claude/umsetzung-buuvfq` |
> | Paket in Arbeit | **AP3 — Verwaltung: Installation, Konto-Backups, Kontoseite** |
> | Erledigt | Schritte 1–5 des Konzeptablaufs; **AP1** (Rolle „BetreiberIn", Abschnitt 11.2); **AP2** (Betrieb Teil 1, Abschnitt 11.3) |
> | Wo es hakt | nichts Blockierendes. **Zuarbeit für AP6 fehlt** (bestätigt 05.09.2026): weder Play-Store-Beitrittslink noch Connect-IQ-Adresse liegen vor — die Karte „App installieren" entsteht im Rückfall ohne Knöpfe, die Adressen sind später an je einer Stelle nachzutragen (Z-03). Zu prüfen in AP4: ob eine letzte Mailzustellung aufgezeichnet wird (Z-01); was die Wear-OS-App als `art` sendet (Z-02) |
> | Umsetzungsumgebung | lokale Installation aus `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` (MariaDB 10.11.14, PHP 8.4.19, Chromium); `00-ist-*`-Bilder vor AP1 aufgenommen |
> | Fable-Schritt | **das Konzept selbst** (R14, Rahmenplan Schritt 7); die Umsetzung läuft nach K2 mit Opus. Mockup-Freigabe je Darstellung durch den Auftraggeber (`CLAUDE.md` 5) |
> | Erhoben an | `main` vom 04.09.2026, 21:14 UTC: **Web 14.2.2, Uhr 3.0.0, Android 0.13.0** (PR #33, Schritt 6 gemergt), Rahmenplan Fassung 27 |
> | Erhoben aus | dem Repositorium allein: `server/ui.php`, `einstellungen.php`, `import.php`, `admin_users.php`, `admin_user.php`, `admin_stammdaten.php`, `admin_sicherungen.php`, `admin_sicherungsziele.php`, `admin_komplettsicherung.php`, `admin_rechtstexte.php`, `admin_demo.php`, `update.php`, `schema.sql`, `config.example.php`, `install.php`, `wiederherstellen.php`, `adminbackup_lib.php`, `komplett_lib.php`, `jobs_lib.php`; `docs/Handbuch.md` 3–11, `Technik.md` 7, `Backlog.md` 73–79, 89, `Rahmenplan.md` Abschnitte 3–7 |
> | Nicht erhebbar | alles, was eine laufende Anwendung braucht (Abschnitt 2.9): Bilder, Breiten, Umbrüche, das Stylesheet-Verhalten der Leiste |

**Konzeptablauf** (vereinbart 04.09.2026):

| Schritt | Inhalt | Stand |
|---|---|---|
| 1 | Befund: jede Einstellung und Verwaltungshandlung mit Fundort, Begriff, Zielgruppe, Speicherort | **erledigt** (Abschnitt 2) |
| 2 | Neuordnung gemeinsam entscheiden: Ordnungsprinzip, Seiten, Menü, Wartungsseite, Backup-Begriffe, Nr. 74, Vorgabe für P5 — als E-Fragen mit Optionen und Preis | **erledigt** 05.09.2026 (Abschnitte 4, 5) |
| 3 | Mockups je neuer Darstellung, einzeln zur Freigabe; hier als HTML mit den Token aus `style.css`, PNG-Fassung durch die umsetzende Instanz | **erledigt** 05.09.2026 — Abschnitt 6 |
| 4 | Konzept vollständig: Zielbild, Arbeitspakete mit Abnahmekriterien, Prüfprotokoll-Soll, Einfügeblöcke für Rahmenplan und Backlog | **erledigt** 05.09.2026 — Abschnitte 7, 8, 9 |
| 5 | Push durch die Instanz mit Repositoriumszugriff; Rahmenplan und Backlog nach 9.1 gegen den dann aktuellen Stand | **erledigt** 05.09.2026 — Abschnitt 10, Ergebnis in 11.1 |

Dieses Dokument wird während der Umsetzung fortgeschrieben (Statusblock,
Umsetzungsstand, Probleme, Fehlerfunde) und der Zweig nach jedem Paket
gepusht (K7). Das Prüfdokument entsteht daneben als
`docs/konzepte/Pruefdokument-S8-Einstellungen-Administration-Wartung.md` (K9).
Nach der Freigabe des Abschlusses: Erledigt-Zeile in Rahmenplan Abschnitt 8,
Reste nach Abschnitt 6, Backlog nach Abschnitt 5, Konzept löschen (R62).

---

## 0. Was dieses Dokument nicht festlegt (K2, K3)

Keine Versionsnummern (die Umsetzung entscheidet; Anhaltspunkt: eine
Neuordnung der Seiten mit Weiterleitungen ist nach der Zählweise in
`version.php` eine **Nebenversion** des Web, keine Migration ist absehbar),
keine Modellempfehlung je Paket, keine neuen Backlog-Nummern — Kandidaten
heißen „Backlog-Kandidat". Keine neuen Verwaltungsfunktionen und keine neuen
Rollen (Abschnitt 1.4).

---

## 1. Aufgabe

### 1.1 Auftrag (Rahmenplan Schritt 7)

**Ziel:** Die Einstellungs-, Verwaltungs- und Wartungsseiten werden einmal
**ergebnisoffen** gesichtet und neu geordnet, bevor P5 dort weitere Optionen
anlegt (R61).

**Inhalt:** (1) Bestandsaufnahme jeder Einstellung und jeder
Verwaltungshandlung mit Fundort, Begriff und Zielgruppe (NutzerIn, Admin,
Betreiberin) · (2) Neuordnung: welche Seite trägt was, Menüstruktur der
Einstellungen und der Administration, Aufteilung der Wartungsseite, Ort der
Migrationsliste · (3) Sicherungsoptionen vereinheitlicht (Begriffe nach S7;
Aufbewahrung, Speichergrenze, Ziele, Zeitplan, je Konto gegen je
Installation) · (4) die Einzelpunkte 73, 74, 75, 77, 78, 79 · (5) eine Vorgabe
für P5, wo Support-Adresse (R31), Rechtstexte (R32), Betriebsart der
Registrierung (R37) und die S2-Optionen liegen.

**Entscheidung im Konzept:** die Bedienhöhe am Schreibtisch (Nr. 74) — eine
Änderung an `CLAUDE.md` 5 und `Design.md` nur mit Begründung und
Kontrastprüfung. S9 wartet auf diese Entscheidung (R73).

**Abnahme:** Bilderlauf in acht Breiten, Vollständigkeit, Wortliste,
Stilvergleich mit Soll-Ist-Liste, Bedienprüfung jeder umgezogenen Funktion;
Handbuch nachgezogen, verschobene und entfernte Funktionen ausgetragen.

**Vorgaben, die schon feststehen:** Nr. 77 ist durch **R66** beantwortet —
die Unterseite „Migrationen" zeigt nur Ausstehende mit „Ausstehende
ausführen", der Torwächter (P5) liest dasselbe Register, ausgeführte
Kennungen bis P5 eingeklappt, danach im Audit-Protokoll; der
Wartungsmodus-Schalter (Paket W) zieht auf die Unterseite „Serverbetrieb".
Nr. 78 darf als Kleinstkorrektur vorab in der Backlog-Runde laufen.

### 1.2 Anlass: die Rückmeldungen vom 02.09.2026

Sechs Punkte, so im Rahmenplan festgehalten:

| Nr. | Rückmeldung | Backlog | Fundort (Abschnitt 2) |
|---|---|---|---|
| 1 | Begriffe und Optionen der Sicherung sind über P3 und S2 gewachsen und wirken wie Wildwuchs (Kontoseite, Sicherungsseite, Sicherungsziele, Komplettsicherung, Wartungsseite) | 79 | 2.4 |
| 2 | `update.php` trägt Migrationsliste, Job-Einstieg mit Cron und Token, Speichergrenze und mehr auf einer Seite | 77 | 2.3.14 |
| 3 | Die Filterknöpfe der NutzerInnen-Liste brechen in zwei Zeilen | 73 | 2.3.6 |
| 4 | Die Unterpunkte des Admin-Menüs sind fett und nicht einklappbar | 75 | 2.2 |
| 5 | Die Bedienhöhe 44 px wirkt am Schreibtisch hoch | 74 | programmweit |
| 6 | Der Wertekasten zeigt Cron-Adresse und Token in der Schriftgröße des Kopplungscodes | 78 | 2.3.14, 2.3.7, 2.3.10, 2.3.3 |

Weitere Rückmeldungen gibt es nicht (F-S8-05, 05.09.2026).

### 1.3 Was der Auftraggeber unter „Wildwuchs" versteht (05.09.2026)

Nicht die Begriffsvielfalt allein, sondern **die Art, wie Funktionen
dazukamen**: Jede neue Funktion wurde irgendwo eingeordnet oder bekam einen
eigenen Menüeintrag, ohne dass jemand die Bedienbarkeit des Ganzen im Blick
hatte. Als Beispiel nennt er die **Einsatz-Übersicht**, auf der inzwischen
die Ruhetracks samt der Option „Schneiden" erscheinen — das gehöre in ein
Untermenü.

Zwei Folgen für dieses Konzept:

1. Die Neuordnung braucht zuerst ein **Ordnungsprinzip** — eine Regel, die
   jeder Funktion ihren Ort nach Aufgabe, Häufigkeit und Zielgruppe zuweist,
   unabhängig davon, wann sie gebaut wurde. Es wird die erste Entscheidung in
   Abschnitt 4 und soll als programmweite Regel in den Rahmenplan (damit P5
   und S9 sich daran halten).
2. Das genannte Beispiel liegt **außerhalb des S8-Umfangs** nach Rahmenplan:
   Die Tagesübersicht (`index.php`, Spuren des Tages aus S2/AP4, Schneiden
   aus S4/A2) gehört zur Einsatzbearbeitung, also in den Bereich von S9
   (dort Nr. 110 „Kachel Spur heißt GPS-Daten"). Wer das in S8 hineinnimmt,
   fasst `index.php` an, das S9 ebenfalls umbaut. Entscheidung: F-S8-06 → (B).

**Nutzungskontext (Auftraggeber, 05.09.2026), an dem sich die Ordnung
ausrichtet:** Der **Regelweg** ist die GPS-Uhr (oder die Handy-App), die den
Dienst aufzeichnet. **Am häufigsten** wird danach im Browser gearbeitet: die
aufgezeichneten Einsätze werden bearbeitet und mit Patienten- und
Einsatzdaten gefüllt. **Selten** sind alle Wege, die Spuren von außen
hereinholen oder zerteilen — GPX einfügen, aus der Ruhezeit schneiden; sie
gehören in Untermenüs, nicht auf die Primärfläche.

### 1.4 Nicht Umfang

Neue Verwaltungsfunktionen (P5, R37, R38) — **Ausnahme, zu bestätigen
(F-S8-07): die Rolle „BetreiberIn"**, die der Auftraggeber am 05.09.2026 für
S8 gewünscht hat; Support-Rolle, TOTP und Audit bleiben P5. Rückbau der
zentralen Stammdaten (P5, R39) — S8 entfernt nur den Menüpunkt; Support-Adresse, Registrierungs-Betriebsart
und Audit selbst (P5) — S8 legt nur fest, **wo** sie liegen werden; der
Store-Weg der Android-App (R65, Schritt 6 Teil C) außer der Frage F-S8-04;
die Betriebsarten des Demo-Resets (Nr. 76, Backlog-Runde).

---

## 2. Befund (am Code gelesen, `main` 04.09.2026)

### 2.1 Zielgruppen — wie sie in diesem Dokument gemeint sind

| Kürzel | Zielgruppe | Wer das ist | Wo sie heute hinkommt |
|---|---|---|---|
| **N** | NutzerIn | jedes Konto mit Rolle `user` oder `admin`; dokumentiert eigene Einsätze | Zahnrad → Einstellungen (sechs Punkte) |
| **A** | Admin | Konto mit Rolle `admin`; verwaltet andere Konten, Backups, Rechtstexte, Demo | zweiter Block „Administration" (acht Punkte) |
| **B** | Betreiberin | wer die Installation betreibt: Hosting, `config.php`, Serverschlüssel, Migrationen, Cron | **keine eigene Oberfläche** — Dateien, Konstanten, `install.php`, `wiederherstellen.php`, Kommandozeile; ihre Handlungen in der Oberfläche liegen auf der Wartungsseite und zwei Backup-Seiten, erreichbar über die Rolle `admin` |

Es gibt **zwei Rollen** (`user`, `admin`), aber **drei Zielgruppen**. A und B
fallen heute in dieselbe Rolle; die Trennung ist eine Frage der Ordnung, nicht
der Rechte (F-S8-02). P5 bringt weitere Rollen (Support, R38) — S8 nicht.

Ebenen der Wirkung, in den Tabellen als „Ebene":

- **Konto** — wirkt nur für das eigene Konto (Tabellen mit `user_id`).
- **Installation** — wirkt für alle (`app_state`, `config.php`, Dateisystem
  `sicherungen/`, Konstanten).
- **Datei** — erzeugt oder liest eine Datei, die die Installation verlässt.

### 2.2 Menüstruktur heute

Das Zahnrad in der Kopfleiste führt auf `einstellungen.php` **ohne
Parameter** — die Übersichtsseite des Bereichs (E-P3-11): dieselben Punkte wie
die Leiste, als Liste mit Symbol, Text, Winkel; am Handy der einzige Ort, der
zeigt, was es gibt. Die Leiste ist unter 1024 px eine Schublade, darüber steht
sie fest daneben; auf Unterseiten trägt sie mobil einen Rückweg „‹
Einstellungen".

| Block | Eintrag | Ziel | Symbol |
|---|---|---|---|
| Einstellungen | Profil | `einstellungen.php?t=profil` | profil |
| | Standorte | `?t=standorte` | standort |
| | Rettungsmittel | `?t=rettungsmittel` | fahrzeug |
| | Geräte | `?t=geraete` | uhr |
| | Backup | `?t=backup` | sicherung |
| | Import / Export | `import.php` | tausch |
| Administration (nur A) | NutzerInnen | `admin_users.php` | gruppe |
| | Stammdaten systemweit | `admin_stammdaten.php` | datenbank |
| | Backups | `admin_sicherungen.php` | sicherung |
| | Backup-Ziele | `admin_sicherungsziele.php` | tausch |
| | Komplett-Backup | `admin_komplettsicherung.php` | datenbank |
| | Rechtstexte | `admin_rechtstexte.php` | rechtstexte |
| | Demo-Konto | `admin_demo.php` | kolben |
| | Wartung | `update.php` | werkzeug |
| Fuß | Abmelden | `logout.php` (mit Rückfrage) | abmelden |

Die Reihenfolge der Admin-Punkte ist im Code begründet: Backups → Backup-Ziele
→ Komplett-Backup „in der Reihenfolge, in der man sie braucht"; Rechtstexte
zwischen Backups und Demo nach Mockup 35 (P3). Symbole werden doppelt
vergeben (sicherung 2×, tausch 2×, datenbank 2×), weil ein neues Symbol
Freigabe mit Mockup bräuchte (Design.md 9).

**Weiche:** `?t=stammdaten` (Reiter bis Web 6.3.0) leitet auf `standorte`.

**Erreichbar, aber nicht im Menü:** die Kontoseite `admin_user.php` (aus der
NutzerInnen-Liste), `install.php` (Ersteinrichtung, verweigert sich bei
vorhandener `config.php`), `wiederherstellen.php` (nur auf leerer Datenbank),
`impressum.php` und `datenschutz.php` (Fußzeile, öffentlich), `jobs.php`
(Kommandozeile). In der Diensttage-Leiste, nicht im Einstellungsbereich:
„Zuordnung offen" (nur solange etwas offen ist), „Diensttag anlegen",
„Papierkorb".

Befunde dazu: B-S8-01 (Liste zweimal definiert), B-S8-15 (A und B
ungetrennt), Nr. 75 (Fettdruck, Einklappen — Stylesheet nicht gelesen, 2.9).

### 2.3 Bestand je Seite

Spalten: **Kennung** (nur für dieses Konzept) · **Einstellung / Handlung** ·
**Fundort** (Karte oder Stelle auf der Seite) · **Begriff heute** (wie die
Oberfläche es nennt) · **Zielgruppe** · **Ebene · Speicherort** · **Anmerkung**.

#### 2.3.1 Profil — `einstellungen.php?t=profil` (Handbuch 3.1a)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| P-01 | Anzeigename | Karte „Angaben" | Name | N | Konto · `users.name` | Kopfleiste |
| P-02 | Anmeldeadresse | Karte „Angaben" | E-Mail-Adresse (Anmeldung) | N | Konto · `users.email` | |
| P-03 | Logo für Kopfleiste und Browser-Symbol | Karte „Logo" | Logo: Standard der Installation / Hubschrauber (RTH) / Fahrzeug (NEF) / Wechselnd | N | Konto · `users` | übersteuert W-04; die Anmeldeseite zeigt immer den Standard |
| P-04 | Passwort ändern | Karte „Passwort ändern" | Passwort ändern | N | Konto · `users` (Ableitung im Browser, Umschlüsselung) | eigenes Formular, eigener Knopf |

Im Demo-Konto sind P-01, P-02 und P-04 gesperrt (E-P1-19, R25).

#### 2.3.2 Standorte und Rettungsmittel — `?t=standorte`, `?t=rettungsmittel` (Handbuch 9)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| SD-01 | Standort anlegen, bearbeiten (Name, Koordinaten) | Karte „Eigene Standorte" + Listenformular | Standort hinzufügen / bearbeiten | N | Konto · `bases` | |
| SD-02 | Standard-Standort setzen | Zeilenaktion | Standard | N | Konto · `user_defaults` (`base`) | Vorbelegung des Diensttags |
| SD-03 | Standort löschen | Zeilenaktion | Löschen | N | Konto · `bases` | |
| SD-04 | Vordefinierte Standorte übernehmen / abwählen | Karte „Vordefinierte Standorte" (zugeklappt) | Vordefinierte Standorte | N | Konto · `user_bases` ↔ `bases` mit `user_id IS NULL` | hängt an SW-01; **entfällt in P5 (R39)** |
| SD-05 | Rettungsmittel je Standort anlegen, bearbeiten, löschen (Bezeichnung, Art, Rollen, Fähigkeiten) | je Standort eine Karte (zugeklappt) → „Rettungsmittel" | Rettungsmittel | N | Konto · `vehicles`, `vehicle_roles`, `vehicle_capabilities` | S9 Nr. 111–113 berühren diese Formulare |
| SD-06 | Standard-Rettungsmittel | Zeilenaktion | Standard | N | Konto · `user_defaults` (`vehicle`) | |
| SD-07 | Besatzung (Vorbelegungen) | Unterabschnitt „Besatzung" | Besatzung | N | Konto · `crew_presets` | |
| SD-08 | Zielkliniken | Unterabschnitt „Zielkliniken" | Zielklinik hinzufügen / bearbeiten | N | Konto · `transport_dests` | S9 Nr. 107 |
| SD-09 | Weitere Rettungsmittel | Unterabschnitt „Weitere Rettungsmittel" | Weitere Rettungsmittel | N | Konto · `resources` | S9 Nr. 102 |
| SD-10 | Bergwacht (Bereitschaften) | Unterabschnitt „Bergwacht" | Bergwacht | N | Konto · `bw_units` | |

Sechzehn POST-Aktionen auf zwei Reitern; die Formulare kommen aus
`stammdaten_ui.php` und werden von `admin_stammdaten.php` **mitbenutzt**.

#### 2.3.3 Geräte — `?t=geraete` (Handbuch 10, 12)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| G-01 | Gerät koppeln: Code eingeben → Rückfrage (Art, Modell, Kennung) → Warten auf das Gerät | Karte „Gerät koppeln" in drei Zuständen | Gerät koppeln · Dieses Gerät koppeln? · Am Gerät bestätigen | N | Konto · `pair_sessions` → `devices` | S5, kein neuer Baustein |
| G-02 | Bezeichnung ändern | Zeilenaktion „Bearbeiten" → Listenformular | Bezeichnung ändern | N | Konto · `devices.label` | |
| G-03 | Deaktivieren / Aktivieren | Zeilenaktion | Deaktivieren · Aktivieren | N | Konto · `devices.active` | sperrt den Schlüssel, Daten bleiben; deaktivierte zählen auf die Grenze |
| G-04 | Gerät löschen | Zeilenaktion | Löschen (mit Rückfrage) | N | Konto · `devices` | gibt einen Platz frei |
| G-05 | Gerät von Hand anlegen → Zugangsdaten anzeigen | Listenformular „Gerät von Hand anlegen" → Karte „Zugangsdaten des neuen Geräts" | Gerät anlegen · Geräte-ID · API-Schlüssel | N | Konto · `devices` | der Garmin-Connect-Weg; Werte im `codeblock` (vgl. Nr. 78) |
| G-06 | Hinweis auf neue Geräte der letzten 7 Tage | Meldung über der Liste | „neu" | N | Anzeige · `GERAETE_NEU_TAGE` | zweite Spur neben der E-Mail |
| G-07 | Android-App herunterladen (APK, Größe, Fassung, Stand, SHA-256) | Karte „NAdoku für Android" | NAdoku für Android · Herunterladen | N | Datei · `apk.php` | **Rückfall bis zur Produktionsfreigabe (R65)**; Handbuch 10.1 nennt den Track als Regelweg — F-S8-04 |

Grenze: `MAX_GERAETE` = 5 je Konto (Konstante, B). Seitenerklärung nennt Grenze
und Belegung.

#### 2.3.4 Backup — `?t=backup` (Handbuch 6)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| B-01 | Backup erstellen — Passwort der Wahl (≥ 10 Zeichen) oder Kontopasswort | Karte „Backup erstellen" | Backup erstellen · Passwort für das Backup · Mein Kontopasswort verwenden | N | Datei · `.edbak`, im Browser verschlüsselt | Warnung: alle geschützten Angaben im Klartext in der Datei |
| B-02 | Backup einspielen (Datei + Passwort) | Karte „Backup einspielen" | Backup einspielen · Datei (.edbak) · Passwort des Backups | N | Konto | ergänzt nur, wiederholbar; zeigt Herkunft der Datei |
| B-03 | Freigegebenes Admin-Backup einspielen (Wiederherstellungsschlüssel) | Karte „Für dich freigegebenes Backup" — **nur wenn eine Freigabe vorliegt** | Wiederherstellungsschlüssel (XXXX-XXXX-XXXX-XXXX) | N | Konto | Gegenstück zu KS-07 / AB-09; der Schlüssel aus der Ersteinrichtung, nicht das Kontopasswort |
| B-04 | Entsperren der Verschlüsselungssitzung | Meldung „lockwarn" | Entsperren | N | Sitzung | Zustand, keine Einstellung; auch auf Import/Export |

#### 2.3.5 Import / Export — `import.php` (Handbuch 7)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| IE-01 | Einsatzliste importieren (Excel/CSV) in drei Schritten | Karten „1. Datei wählen", „2. Prüfen und korrigieren", „3. Übernehmen" | Import ausführen | N | Konto | |
| IE-02 | Export: Zeitraum (Wahl, Von/Bis), Format, GPX-Tracks einschließen, personenbezogene Angaben einschließen, mit Passwort schützen (AES-256) | Karte „Export" | Export erstellen | N | Datei | Schalter blenden sich gegenseitig aus |

Der **GPX-Import je Diensttag** liegt nicht hier, sondern auf der
Tagesübersicht neben „Spuren als GPX" (S4/A3, E-S4-18) — B-S8-18.

#### 2.3.6 NutzerInnen — `admin_users.php` (Handbuch 11.2, 11.3)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| NL-01 | Kennzahlen mit Sprung auf den Filter | Kennzahlraster (4) | Konten · Admins · Backup überfällig · nie gesichert | A | Anzeige | „überfällig"/„nie" messen **nur Admin-Pakete** (2.4) |
| NL-02 | Suche | Karte „Konten" | Suchfeld | A | Anzeige | |
| NL-03 | Filter | Plakettenreihe `.filterreihe` | Alle · Admins · Backup überfällig · Nie gesichert · Ohne Gerät | A | Anzeige | **Nr. 73**: bricht in zwei Zeilen |
| NL-04 | Sortierung, 50 je Seite | Spaltenköpfe | Konto · Rolle · Seit · Zuletzt angemeldet · Geräte · Backup | A | Anzeige · `KONTEN_JE_SEITE` | |
| NL-05 | Konto anlegen (E-Mail, Name, Rolle) → Setz-Link per Mail, bei Versandfehler Link auf der Seite | Titelaktion „Anlegen" → Formular | Anlegen | A | Installation · `users` | Rückfall-Link 24 h gültig |
| NL-06 | Mehrere Konten auf einmal sichern | Mehrfachauswahl → „Auswahl sichern" | Auswahl sichern | A | Installation · `sicherungen/` | Admin-Backup (2.4 b) |

#### 2.3.7 Kontoseite — `admin_user.php` (Handbuch 11.1)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| KS-01 | Name, Rolle, E-Mail ändern | Karte „Konto" (ein Formular) | Speichern | A | Konto (fremd) · `users` | Rollenwechsel wirkt sofort |
| KS-02 | Passwort zurücksetzen (Setz-Link) | Aktionsmenü | Passwort zurücksetzen | A | Konto (fremd) · `password_resets` | Link im `codeblock` — **Nr. 78** |
| KS-03 | Geräte des Kontos: Deaktivieren / Aktivieren, Entkoppeln | Karte „Geräte" | Deaktivieren · Entkoppeln | A | Konto (fremd) · `devices` | anderer Begriff als N (G-04 „Löschen") |
| KS-04 | Backups des Kontos ansehen (Zeitpunkt, Umfang, Größe, Zustand) | Karte „Backups" | Backups | A | Anzeige · `sicherungen/<Kontokennung>/` | Admin-Pakete |
| KS-05 | Jetzt sichern | Titelaktion und Kartenfuß | Jetzt sichern | A | Installation · `sicherungen/` | im Demo-Konto stattdessen „Zum Demo-Konto" |
| KS-06 | Paket einspielen (E-Mail-Bestätigung) | Zeilenaktion → Dialog | Einspielen | A | Konto (fremd) | |
| KS-07 | Für Zielkonto freigeben / Freigabe widerrufen (Paket, Zielkonto, E-Mail des Zielkontos) | Aktionsmenü und Kartenfuß → Dialog | Für Zielkonto freigeben · Freigabe widerrufen | A | Installation · Begleitdatei | Gegenstück zu B-03 |
| KS-08 | Paket löschen | Zeilenaktion → Dialog | Löschen | A | Installation · `sicherungen/` | |
| KS-09 | Abonnement | Karte „Abonnement · ab P5" | Abonnement | A | — | **Platzhalter** im Betrieb sichtbar — B-S8-11 |
| KS-10 | Konto löschen (Option „Backups dieses Kontos" mit, E-Mail-Bestätigung) | Karte „Konto löschen", rot abgesetzt | Konto endgültig löschen | A | Installation · `users` + `sicherungen/` | Gefahrenzone ganz unten |

#### 2.3.8 Stammdaten systemweit — `admin_stammdaten.php` (Handbuch 9.4)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| SW-01 | Systemweite Standorte anlegen, bearbeiten, löschen | Karte „Standorte" | Standorte | A | Installation · `bases` mit `user_id IS NULL` | |
| SW-02 | Je Standort: Rettungsmittel, Besatzung, Zielkliniken, Weitere Rettungsmittel, Bergwacht | je Standort eine Karte, Segmentwahl in der Titelzeile | wie 2.3.2 | A | Installation · dieselben Tabellen mit `user_id IS NULL` | Formulare aus `stammdaten_ui.php`, geteilt mit 2.3.2 |

**Entfällt in P5 (R39, Beschluss 30.08.2026)** — Rückbau einschließlich SD-04
und Doku. S8 gestaltet diese Seite nicht neu und ordnet sie nur ein (F-S8-03,
bestätigt 05.09.2026).

#### 2.3.9 Backups — `admin_sicherungen.php` (Handbuch 6.1, 11.3)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| AB-01 | Kennzahlen mit Sprung | Kennzahlraster (4) | Konten · Pakete · Größe · überfällig · nie gesichert | A | Anzeige | Doppel zu NL-01 |
| AB-02 | Alle Konten sichern (in Schüben, ~20 s, Merkzettel; Rest über den Wartungsjob) | Titelaktion „Alle sichern" | Alle sichern | A | Installation · `sicherungen/`, `app_state` (Auftrag) | Stand des Auftrags oben auf der Seite |
| AB-03 | Erinnerung nach … Tagen (Vorgabe 30) | Karte „Regeln" | Erinnerung nach | A | Installation · `app_state` | definiert „überfällig" |
| AB-04 | Aufbewahrung je Konto (Pakete, Vorgabe 2) | Karte „Regeln" | Aufbewahrung je Konto | A | Installation · `app_state` | jüngstes und freigegebenes nie gelöscht |
| AB-05 | Speichergrenze (GB, Vorgabe 2) für **alle Backups zusammen** | Karte „Regeln" | Speichergrenze | A/B | Installation · `app_state` | **Komplett-Backups zählen mit** (KB-06) — B-S8-06; bei Erreichen wird nicht mehr gesichert |
| AB-06 | Warnschwellen (%, Vorgabe 70, 90) | Karte „Regeln" | Warnschwellen | A/B | Installation · `app_state` | einmal je Schwelle; ohne SMTP als Dauermeldung |
| AB-07 | Erinnerung an Admins per E-Mail (Vorgabe aus) | Karte „Regeln" | Erinnerung an Admins per E-Mail | A | Installation · `app_state` | fährt auf dem täglichen Aufräumjob (W-06) |
| AB-08 | Ablage: Pfad, Zustand, letztes Backup, Ordner, Belegt, Reste abgebrochener Läufe | Karte „Ablage" | Ablage | A/B | Anzeige · Dateisystem | rein lesend |
| AB-09 | Backups ohne Konto: Einspielen in ein Zielkonto, Freigeben, Paket löschen, ganzen Ordner löschen (E-Mail der Herkunft) | Karte „Backups ohne Konto" (zugeklappt) | Backups ohne Konto | A | Installation · `sicherungen/` | Fall „Konto gelöscht und neu aufgesetzt" |

„Automatisch entsteht kein Backup" (Handbuch): Nächtliche Backups je Konto
sind nicht vorgesehen, weil der Server den Inhaltsschlüssel nicht hat.

#### 2.3.10 Backup-Ziele — `admin_sicherungsziele.php` (Handbuch 6.2)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| BZ-01 | Serverschlüssel erzeugen und in `config.php` eintragen, sonst Zeile von Hand | Karte „Serverschlüssel fehlt" (nur solange er fehlt) | Serverschlüssel erzeugen und eintragen | **B** | Installation · `config.php` `server_key` | Zeile im `codeblock` — Nr. 78; **dieselbe Karte auf 2.3.11** — B-S8-05 |
| BZ-02 | Backups automatisch versenden (Schalter) | Karte „Versand" | Backups automatisch versenden | A/B | Installation · `app_state` | der Wartungsjob schiebt neue Pakete auf aktive Ziele |
| BZ-03 | Jetzt versenden | Titelaktion | Jetzt versenden | A/B | Installation | |
| BZ-04 | Kennzeilen: Aktive Ziele, Wartet auf den nächsten Lauf | Karte „Versand" | — | A/B | Anzeige | Rückstand ist Schätzung |
| BZ-05 | Ziel anlegen / bearbeiten: Name, Protokoll (FTP, FTPS, SFTP), Rechnername, Port, Nutzername, Pfad, Passwort, privater Schlüssel (SFTP), passiver Modus, Ziel benutzen | Karte „Neues Ziel" / „Ziel bearbeiten" | Ziel anlegen | **B** | Installation · `backup_targets` (Zugangsdaten verschlüsselt mit BZ-01) | |
| BZ-06 | Verbindung prüfen (Schrittprotokoll „Was die Prüfung getan hat") | Zeilenaktion | Verbindung prüfen | B | — | |
| BZ-07 | Hostschlüssel vergessen | Zeilenaktion (SFTP) | Hostschlüssel vergessen | B | Installation · `backup_targets` | |
| BZ-08 | Ziel löschen | Zeilenaktion | Löschen | B | Installation | |
| BZ-09 | Erklärkarte | Karte „Was hier gilt" (zugeklappt, „drei Protokolle") | Was hier gilt | A/B | — | Doku in der Seite |

#### 2.3.11 Komplett-Backup — `admin_komplettsicherung.php` (Handbuch 6.3)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| KB-01 | Serverschlüssel-Karte | wie BZ-01 | identisch | B | | B-S8-05 |
| KB-02 | Jetzt sichern / Fortsetzen / Abbrechen | Titelaktionen | Jetzt sichern · Fortsetzen · Abbrechen | A/B | Installation · `sicherungen/` (Stände) | Wiederanlauf in Schüben |
| KB-03 | Läuft gerade (Stand, Tabellen, Zeilen, Größe, Begonnen) · Letzter Lauf (Fertig, Umfang, Verdrängt, Hinweis) | zwei Karten | Läuft gerade · Letzter Lauf | A/B | Anzeige | |
| KB-04 | Von selbst sichern: Nur von Hand / Täglich / Wöchentlich / Monatlich | Karte „Regeln" | Von selbst sichern | A/B | Installation · `app_state` | „sagt nicht WANN, sondern OB"; Auslöser auf der Wartungsseite (W-07); **lief bis 12.9.2 nie (Nr. 89)** |
| KB-05 | Stände aufbewahren (1–20) | Karte „Regeln" | Stände aufbewahren | A/B | Installation · `app_state` | „hier, nicht auf dem Backup-Ziel" |
| KB-06 | Kennzeilen: Jüngster Stand, Belegt von Komplett-Backups („zählt auf die Speichergrenze mit — sie steht unter Backups"), Wartet auf den nächsten Lauf | Karte „Regeln" | — | A/B | Anzeige | Querverweis auf AB-05 |
| KB-07 | Stände: Herunterladen, Versiegelt herunterladen (mit Passphrase), Löschen | Karte „Stände" | Herunterladen · Versiegelt herunterladen · Löschen | B | Datei | |
| KB-08 | Erklärkarte | Karte „Was hier gilt" („Wiederanlauf") | Was hier gilt | A/B | — | |

#### 2.3.12 Rechtstexte — `admin_rechtstexte.php` (Handbuch 11.3)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| RT-01 | Impressum: Text (eingeschränktes Markdown), Standdatum | Karte „Impressum" | Text · Stand | A/B | Installation · `rechtstexte` | öffentlich unter `impressum.php` |
| RT-02 | Datenschutzerklärung: Text, Stand | Karte „Datenschutz" | Text · Stand | A/B | Installation · `rechtstexte` | öffentlich unter `datenschutz.php`; auch in der Android-App (Handbuch 10.2b) |

Ein Speichern für beide (Speichern-Leiste). R32 sieht die Felder in den
**P5-Admin-Optionen** — S8 legt den Ort fest (Aufgabe 1.1 (5)).

#### 2.3.13 Demo-Konto — `admin_demo.php` (Handbuch 3.2)

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| DK-01 | Demo-Konto anlegen | Titelaktion (wenn keins da) | Demo-Konto anlegen | A | Installation · `users`, `app_state` | |
| DK-02 | Zurücksetzen (Fixture neu einspielen) | Titelaktion | Zurücksetzen | A | Installation | läuft ohnehin alle 30 min (Nr. 76, Backlog-Runde) |
| DK-03 | Demo-Konto entfernen | Aktionsmenü (mit Bestätigung) | Demo-Konto entfernen | A | Installation | |
| DK-04 | Zustand: Konto, letzter/nächster Reset, Kennzahlen (Diensttage, Einsätze, Ruhesegmente, Geräte) | Karte „Zustand" | Zustand | A | Anzeige | |
| DK-05 | Papierkorb-Zahlen des Demo-Kontos | Karte „Papierkorb" | Papierkorb | A | Anzeige | |
| DK-06 | Bericht des letzten Laufs · Was der Reset umfasst | zwei Karten (zugeklappt) | — | A | Anzeige | Doku in der Seite |

#### 2.3.14 Wartung — `update.php` (Handbuch 11.3 „Anlegen, Rollen und Wartung", Technik 7)

Seitentitel **„Wartung & Datenbank-Update"**, Menüpunkt **„Wartung"**
(B-S8-02). Neun Blöcke auf einer Fläche, in dieser Reihenfolge:

| Kennung | Einstellung / Handlung | Fundort | Begriff heute | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|---|
| W-01 | Wartungsbalken (Zustand) | über den Karten | — | A/B | Anzeige | Paket W |
| W-02 | Wartungsmodus einschalten / ausschalten (503 + Retry-After für alle außer Verwaltung) | Karte „Serverbetrieb" mit Plakette „Wartung" / „im Betrieb" | Wartungsmodus einschalten · ausschalten | **B** | Installation · `server/wartung.lock` | R66: zieht auf Unterseite „Serverbetrieb"; Torwächter in P5 setzt ihn automatisch |
| W-03 | Schlüsselableitung: Konten mit verwaister Rundenzahl (Anmeldung blockiert) | Karte „Schlüsselableitung" — **nur im Problemfall** | Schlüsselableitung | B | Anzeige · Behebung in `db.php` `KDF_ITER_LISTE` | Diagnose, Handlung außerhalb der Oberfläche |
| W-04 | Logo der Installation: Hubschrauber / Fahrzeug / wechselnd | Karte „Logo" (Segment) | Standard speichern | A | Installation · `app_state` `logo_standard` | **Gestaltung, keine Wartung** — B-S8-10; Platzhalter-Hinweis NEF |
| W-05 | Umgebung: Mailversand entkoppelbar ja/nein | Karte „Umgebung" | Mailversand · entkoppelt / nicht sicher entkoppelbar | B | Anzeige | rein diagnostisch |
| W-06 | Hintergrundjobs: je Job letzter Lauf, Auslöser, Rückstand, Fehler, Liegenbleiber mit Kennungen; Pause-Hinweis | Karte „Hintergrundjobs" mit Zustandsplakette | Hintergrundjobs · läuft / scheitert / angehalten / noch nie gelaufen | A/B | Anzeige · `jobs`, `app_state` | **Pause nur per Kommandozeile** (`jobs.php --pause`) — B-S8-16 |
| W-07 | Wann die Jobs laufen: 1. Cron-Zeile · 2. Token-Adresse mit „Neues Token erzeugen" · 3. Huckepack (3 s je Anfrage, alle 5 min) | Karte „Wann die Jobs laufen" | Kommandozeile · Abruf über die Adresse · Huckepack | **B** | Installation · `app_state` (Token), Konstanten | Cron-Zeile und Adresse im `codeblock` — **Nr. 78** |
| W-08 | Einsätze ohne Diensttag: Ansehen, Verschieben | Karte „Einsätze ohne Diensttag" mit Plakette „unvollständig sichtbar" | Einsätze ohne Diensttag | A | Anzeige → `einsatz.php`, Tageszuordnung | **Datenreparatur, keine Wartung** — B-S8-03 |
| W-09 | Datenbank-Update: Liste **aller 42 Migrationen** mit Status (erledigt / steht aus / blockiert / Fehler), Häkchen zum Freigeben blockierter, „Updates jetzt anwenden", „Zum Backup" | Karte „Datenbank-Update" mit Plakette „aktuell" / „n offen" | Updates jetzt anwenden | **B** | Installation · `schema_migrations` | **R66: nur Ausstehende, ausgeführte eingeklappt bis P5, dann Audit** — B-S8-04 |

#### 2.3.15 Außerhalb der Oberfläche (Betreiberin)

| Kennung | Einstellung / Handlung | Fundort | Ziel | Ebene · Speicherort | Anmerkung |
|---|---|---|---|---|---|
| X-01 | Datenbankzugang; `base_url`; `timezone` (Anzeige, Speicherung UTC); `logo_path`; `max_body_bytes` (512 KB Ingest); SMTP (host, port, user, pass, from, from_name); `server_key` | `config.php` (Muster `config.example.php`) | B | Installation · Datei | entsteht in `install.php`; `server_key` auch über BZ-01 |
| X-02 | Feste Grenzwerte: `MAX_GERAETE` 5 · `GERAETE_NEU_TAGE` 7 · `TRASH_DAYS` 90 · `KDF_ITER_LISTE` [320000] · `WARTUNG_RETRY_S` 300 · `JOB_BUDGET_ANFRAGE` 3 s · `JOB_ANFRAGE_PAUSE_S` 300 · `KONTEN_JE_SEITE` 50 · Vorgaben `EDBAK_*` (30 Tage, 2 Pakete, 2 GB, 70/90 %) · `KOMP_PLAENE` | Konstanten in `db.php`, `trash_lib.php`, `wartung_lib.php`, `jobs_lib.php`, `adminbackup_lib.php`, `komplett_lib.php`, `admin_users.php` | B (Code) | Installation · Quelltext | ohne Oberfläche, mit Absicht |
| X-03 | Ersteinrichtung: Nachweis (Datei im Verzeichnis), Datenbank, Adresse, Zeitzone, Logo, SMTP, erstes Admin-Konto mit Setz-Link | `install.php` | B | Installation | verweigert sich bei vorhandener `config.php` |
| X-04 | Installation aus Komplett-Backup wiederherstellen (leere Datenbank, Nachweis, Datei aus `sicherungen/eingang/`) | `wiederherstellen.php` | B | Installation | drei Schranken; der Rückweg zu KB-07 |
| X-05 | Jobs anhalten / fortsetzen | `php jobs.php --pause <Minuten>` | B | Installation · `app_state` | einzige Job-Handlung, die es nur auf der Kommandozeile gibt |
| X-06 | Schlüssel-Wert-Speicher der Installation | Tabelle `app_state` (`k`, `v`) | — | Installation | trägt: `salt_secret`, `logo_standard`, Backup-Regeln, Job-Token, Job-Pause, Komplett-Plan, Demo-Zustand, Auftrag „Alle sichern" — **die tatsächliche Ablage der Admin-Optionen**, ohne Schema |

**Zählung:** 27 Einträge für N (2.3.1–2.3.5), 61 für A (2.3.6–2.3.14, davon
etwa 15 der Sache nach B), 6 außerhalb der Oberfläche. Rein lesende
Kennzeilen und Erklärkarten sind mitgezählt; sie besetzen Fläche und gehören
in die Ordnung.

### 2.4 Querschnitt Backup (Kern von Nr. 79)

**Ein Wort, drei Dinge.** „Backup" bezeichnet heute drei verschiedene Dinge,
und die Oberfläche unterscheidet sie durch ein Plural-s, den Kontext oder gar
nicht:

| | **(a) Konto-Backup durch die NutzerIn** | **(b) Admin-Backup je Konto** | **(c) Komplett-Backup der Installation** |
|---|---|---|---|
| Was es ist | eine `.edbak`-Datei mit allen Daten des Kontos; die geschützten Angaben stehen darin im Klartext | ein `.edbak`-Paket je Konto auf dem Server, adressiert über die **Kontokennung**; geschützte Angaben bleiben mit dem Inhaltsschlüssel des Kontos verschlüsselt | ein Datenbankdump der ganzen Installation, versiegelt mit dem **Serverschlüssel** |
| Wer | N | A (Kontoseite, Backups-Seite, NutzerInnen-Liste) | A/B |
| Wo es liegt | bei der NutzerIn (Download) | `sicherungen/<Kontokennung>/` | `sicherungen/` (Stände) |
| Schutz | Passwort der Wahl **oder** Kontopasswort, Ver- und Entschlüsselung **im Browser** | serverseitig; Inhaltsschlüssel bleibt beim Konto | Serverschlüssel aus `config.php`; beim Download zusätzlich Passphrase |
| Rückweg | „Backup einspielen" (B-02) — auch in ein **anderes** Konto | „Einspielen" durch A (KS-06, AB-09); in ein Zielkonto nur nach **Freigabe** und mit dem **Wiederherstellungsschlüssel** der NutzerIn (KS-07 → B-03) | `wiederherstellen.php` auf leerer Datenbank (X-04) |
| Regeln | keine | Erinnerung nach Tagen, Aufbewahrung je Konto, Speichergrenze, Warnschwellen, Admin-Mail (AB-03…07) | Plan (aus/täglich/wöchentlich/monatlich), Stände aufbewahren (KB-04, KB-05); **Speichergrenze von (b) zählt (c) mit** |
| Zeitgeber | — | **kein automatisches Backup** (kein Inhaltsschlüssel beim Server); nur die Erinnerung fährt auf dem Aufräumjob | Aufräumjob nach Plan; Auslöser auf der Wartungsseite (W-07) |
| Versand nach außen | — | Backup-Ziele (BZ-02, BZ-03): der Wartungsjob schiebt neue Pakete auf aktive Ziele | zu prüfen, ob Stände mitversendet werden (2.9) |
| Handbuch | 6 | 6.1, 11.1, 11.3 | 6.3, Technik 7 |
| Menü | „Backup" (N) | „Backups" (A) | „Komplett-Backup" (A) |

**Begriffe, die dabei nebeneinander stehen** (Wortliste der Neuordnung, Schritt
2): Backup · Backups · Backup-Ziele · Komplett-Backup · Paket · Stand · Ablage ·
Ordner · Freigabe · sichern (Verb, bleibt nach R56) · einspielen ·
wiederherstellen · Wiederherstellungsschlüssel · Serverschlüssel ·
Kontokennung · Backup-Passwort · Kontopasswort · Passphrase · versiegelt.
Dazu die Rollen der Schlüssel: der **Wiederherstellungsschlüssel** gehört
der NutzerIn (Ersteinrichtung, wird nie wieder angezeigt), der
**Serverschlüssel** der Betreiberin (`config.php`), die **Kontokennung**
ist die Adresse eines Kontos im Dateisystem.

**Was „überfällig" misst.** Kennzahlen, Filter und Erinnerungsmail beziehen
sich ausschließlich auf (b): Der Stand kommt aus dem jüngsten vorhandenen
Paket im Konto-Ordner (`edbak_konto_stand()`). Ob eine NutzerIn selbst je
ein Backup (a) heruntergeladen hat, weiß niemand — B-S8-07.

**Wo die Regeln liegen:** vier Seiten. Regeln für (b) auf „Backups", Versand
auf „Backup-Ziele", Plan für (c) auf „Komplett-Backup", der Auslöser aller
Jobs auf „Wartung". Die Speichergrenze steht auf „Backups", wirkt aber auf
(b) und (c); die Komplett-Seite verweist auf sie mit einem Satz.

### 2.5 Backlog 73–79 mit Fundort

| Nr. | Punkt | Fundort im Befund | Stand |
|---|---|---|---|
| 73 | Filterknöpfe brechen in zwei Zeilen | NL-03, Baustein `.filterreihe` / `.listenfilter` | S8: Anordnung im Konzept, Umsetzung am Baustein |
| 74 | Bedienhöhe am Schreibtisch | programmweit (`CLAUDE.md` 5, `Design.md`, `tools/screenshots/`) | S8: Entscheidung; S9 wartet darauf |
| 75 | Admin-Unterpunkte fett, nicht einklappbar | 2.2, `ui_leiste_einstellungen()`, `.leiste-liste` | S8: mit der Menüstruktur; Stylesheet nicht gelesen (2.9) |
| 76 | Demo-Reset alle 30 min | DK-02 | Backlog-Runde (Messung) — nicht S8 |
| 77 | Wartungsseite aufteilen | 2.3.14 | S8; Schnitt durch R66 vorgezeichnet |
| 78 | Wertekasten in Kopplungscode-Größe | W-07, KS-02, BZ-01, **dazu G-05** (Geräte-ID, API-Schlüssel — in Nr. 78 nicht genannt) | S8; darf vorab in der Backlog-Runde laufen |
| 79 | Backup-Wildwuchs | 2.4 | S8, Kern |
| 89 | Komplett-Plan lief nie (behoben) | KB-04 | erledigt; Lehre: die Wartungsseite zeigte „Fehler", niemand las es — spricht für W-06 an sichtbarerer Stelle |

### 2.6 Dokumentation heute

| Dokument | Was dort steht | Betroffen von S8 |
|---|---|---|
| Handbuch 3.1a | Profil | Verweise |
| Handbuch 6, 6.1–6.3 | Backup (a), Admin-Backups (b), Ziele, Komplett (c) | Begriffe, Orte |
| Handbuch 7 | Import und Export | Verweise |
| Handbuch 9, 9.4 | Stammdaten; systemweit | 9.4 nur Einordnung (R39) |
| Handbuch 10, 10.1 | Geräte; App herunterladen | F-S8-04 |
| Handbuch 11, 11.1–11.3 | Administration: Kontoseite, Liste, „Anlegen, Rollen und Wartung" — die Wartung (Jobs, Wartungsmodus, Regeln, Rechtstexte) steckt im Unterabschnitt „Anlegen" | Neugliederung nötig (B-S8-17) |
| Technik 7 | Betrieb (Runbook): Deploy, Wartungsmodus-Ablauf, Migrationen | Verweise auf Unterseiten |
| `Backup-Format.md`, `Export-Format.md` | Formate | Begriffe |
| `Design.md` | Token, Bausteine, Seitentypen, Bedienhöhe | Nr. 74, Nr. 78, ggf. neue Bausteine |
| `CLAUDE.md` 5 | Bedienhöhe 44 px | Nr. 74 |

Nach R72 wird das Handbuch in P7 nach Zielgruppen neu gefasst (vier
Dokumente, Betreiberhandbuch generisch). S8 zieht das heutige Handbuch nach,
legt aber mit der Zielgruppentrennung die Gliederung vor, die P7 übernimmt.

### 2.7 Berührungen

| Mit | Was sich berührt | Regel |
|---|---|---|
| **S9** (Schritt 8) | Stammdaten-Seiten (SD-05, SD-08, SD-09: Nr. 102, 107, 111–113); Stylesheet; Nr. 74 | S9-Konzept nach diesem Konzept; Umsetzung parallel zulässig, wenn beide Konzepte die Berührungen benennen (R73); wer zuerst mergt, der andere zieht nach (K7). **Tagesübersicht: F-S8-06** |
| **P5** (Schritt 10) | Support-Adresse (R31), Rechtstexte in Admin-Optionen (R32), Registrierung (R37), Support-Rolle/TOTP/Audit/Dashboard (R38), Rückbau SW-01/02 und SD-04 (R39), Torwächter am Wartungsmodus (R40.4, Paket W) | S8 legt die Orte fest (Aufgabe 1.1 (5)), baut nichts davon |
| **R65** (Schritt 6 Teil C, Betriebsübergang) | G-07 APK-Karte, Handbuch 10.1 | F-S8-04 |
| **R66** | W-09 Migrationen, W-02 Serverbetrieb | Vorgabe steht (1.1) |
| **Paket W** (Web 13.2.0) | W-01, W-02, `wartung_lib.php` | Schalter zieht um, Mechanik bleibt |
| **Nr. 78** vorab | `.codeblock-wert`, `Design.md` | darf vor S8 in der Backlog-Runde laufen; dann übernimmt S8 den Stand |
| **P7** (R72) | Handbuch-Neufassung nach Zielgruppen | S8 liefert die Gliederung vor |

### 2.8 Befunde am Bestand (B-S8, K4 — gesammelt, nicht sofort behoben)

| Nr. | Befund | Fundort |
|---|---|---|
| B-S8-01 | Die Punktliste des Einstellungsbereichs steht **zweimal** im Code — in `ui_leiste_einstellungen()` und in `ui_einstellungen_uebersicht()` — und wird von Hand synchron gehalten. Jede Neuordnung ändert beide, oder Leiste und Übersicht laufen auseinander | `ui.php` |
| B-S8-02 | Seitentitel „Wartung & Datenbank-Update" gegen Menüpunkt „Wartung"; im Handbuch heißt der Abschnitt „Anlegen, Rollen und Wartung" | 2.3.14, Handbuch 11.3 |
| B-S8-03 | Die Wartungsseite mischt **vier Anliegen**: Betrieb (W-02, W-05, W-06, W-07), Gestaltung (W-04), Datenreparatur und Diagnose (W-03, W-08), Update (W-09) | 2.3.14 |
| B-S8-04 | Die Migrationsliste zeigt alle 42 Einträge; R66 will nur Ausstehende | W-09 |
| B-S8-05 | Die Karte „Serverschlüssel fehlt" steht wortgleich auf zwei Seiten | BZ-01, KB-01 |
| B-S8-06 | Die Speichergrenze steht unter „Backups", wirkt aber auf (b) **und** (c); die Komplett-Seite verweist nur | AB-05, KB-06 |
| B-S8-07 | „Backup überfällig" und „nie gesichert" (Kennzahlen, Filter, Mail) messen ausschließlich Admin-Pakete (b); ob NutzerInnen ihre eigenen Backups (a) je ziehen, ist unbeobachtet — die Kennzahl liest sich vollständiger, als sie ist | NL-01, AB-01 |
| B-S8-08 | „Backup" (N) und „Backups" (A) im Menü unterscheiden sich um ein s und meinen zwei verschiedene Dinge; „Komplett-Backup" ist ein drittes | 2.2, 2.4 |
| B-S8-09 | Der Freigabe-Fluss ist über drei Seiten verteilt (KS-07 → Begleitdatei → B-03), ohne dass eine Seite den ganzen Weg zeigt; die NutzerIn sieht ihre Karte erst, wenn die Freigabe vorliegt | KS-07, AB-09, B-03 |
| B-S8-10 | Der Logo-Standard der Installation liegt auf der Wartungsseite, die Logo-Wahl je Konto im Profil — dieselbe Sache an zwei Enden der Anwendung | W-04, P-03 |
| B-S8-11 | Die Kontoseite zeigt im Betrieb einen Platzhalter „Abonnement · ab P5" | KS-09 |
| B-S8-12 | Der Geräte-Reiter trägt fünf Anliegen: koppeln, Liste, von Hand anlegen (Garmin-Weg), App herunterladen, Zugangsdaten anzeigen | 2.3.3 |
| B-S8-13 | `.codeblock-wert` in Kopplungscode-Größe an **fünf** Stellen, Nr. 78 nennt vier: Cron-Zeile, Token-Adresse, Setz-Link, Serverschlüssel-Zeile — dazu Geräte-ID und API-Schlüssel (G-05) | Nr. 78 |
| B-S8-14 | „Stammdaten systemweit" und die Karte „Vordefinierte Standorte" entfallen in P5 (R39); beide Seiten teilen sich die Formulare aus `stammdaten_ui.php` | SW-01/02, SD-04 |
| B-S8-15 | Zielgruppen A und B sind in einem Block: Serverschlüssel, Wartungsmodus, Migrationen, Job-Token, Backup-Ziele (Betrieb) stehen neben NutzerInnen, Rechtstexte, Demo (Verwaltung); die Rolle `admin` ist beides | 2.2, 2.1 |
| B-S8-16 | Die Jobs lassen sich nur auf der Kommandozeile anhalten; die Karte „Umgebung" ist rein diagnostisch; „Schlüsselableitung" nennt eine Behebung im Quelltext | W-05, W-06, W-03 |
| B-S8-17 | Handbuch: Backup-Themen in 6, 6.1–6.3, 11.1, 11.3; Wartung als Unterabschnitt von „Anlegen, Rollen und Wartung" | 2.6 |
| B-S8-18 | „Import / Export" als Sammelpunkt ist unvollständig: der GPX-Import liegt auf der Tagesübersicht, der Backup-Rückweg auf „Backup" | IE-01, 2.3.4 |
| B-S8-19 | Kennzahlen „Konten, Admins, überfällig, nie gesichert" stehen zweimal (NutzerInnen-Liste, Backups-Seite) | NL-01, AB-01 |
| B-S8-20 | Erklärkarten „Was hier gilt", „Was der Reset umfasst", „Bericht des letzten Laufs" tragen Handbuchtext in der Oberfläche; dreimal auf drei Seiten, jeweils anders betitelt | BZ-09, KB-08, DK-06 |
| B-S8-21 | Begriffe für dieselbe Handlung wechseln zwischen N und A: Gerät „Löschen" (N) gegen „Entkoppeln" (A); „Backup einspielen" (N) gegen „Einspielen" (A); „Löschen" für Standorte, Geräte, Pakete, Ziele, Stände, Konten gleichlautend bei sehr verschiedener Tragweite | 2.3.3, 2.3.7 |

### 2.9 Was sich aus dem Repositorium nicht ermitteln ließ

1. **Bilder und Breiten.** Bei welcher Breite die Filterreihe (Nr. 73) bricht
   und wie die Wartungsseite bei 360 px aussieht, zeigt nur der Bilderlauf
   (`tools/screenshots/`, acht Breiten). Die umsetzende Instanz nimmt die
   `00-ist-*`-Bilder vor dem ersten Paket auf; die Mockups (Schritt 3)
   entstehen hier ohne sie.
2. **Das Stylesheet der Leiste** (Nr. 75) ist nicht gelesen — ob der Admin-Teil
   von S3 Block F ausgenommen blieb oder eine eigene Regel trägt, prüft
   Schritt 2 vor der Entscheidung zur Menüstruktur.
3. **Der Stand des Produktivservers**: welche der 42 Migrationen dort
   ausstehen (Rahmenplan-Kopf: vier, dazu R64) — für die Gestaltung von W-09
   unerheblich, für die Abnahme nicht.
4. **Ob Backup-Ziele auch Komplett-Stände versenden** — Handbuch 6.2 spricht
   von „Paketen"; der Code (`sicherungsziel_lib.php`) ist dazu nicht gelesen.
5. **Die Bildschirmfotos** zu Nr. 73 und Nr. 78 liegen nicht im
   Repositorium; die Beschreibung im Backlog reicht für den Befund.

---

## 3. Fragen und Antworten (F-S8)

| Nr. | Frage | Antwort / Stand |
|---|---|---|
| F-S8-01 | Ist „Wildwuchs" vor allem die Begriffsvielfalt oder die Verteilung über fünf Seiten? | **Beantwortet 05.09.2026:** weder allein — es ist die Art, wie Funktionen dazukamen: irgendwo eingeordnet oder mit eigenem Menüeintrag, ohne Blick auf die Bedienbarkeit. Folge: Ordnungsprinzip als erste Entscheidung (1.3). Das genannte Beispiel (Ruhezeiten mit „Schneiden" auf der Tagesübersicht) führt zu F-S8-06 |
| F-S8-02 | Soll die Betreiberin eine eigene Oberfläche bekommen, oder bleibt die dritte Zielgruppe auf Dateiebene? Genauer (05.09.2026): S8 kann (a) alles so lassen — Betrieb bleibt `config.php` plus Wartungsseite — oder (b) im Menü einen Bereich „Betrieb" von „Verwaltung" trennen, **ohne neue Rollen** (Rollen kommen in P5, R38). Eine Betreiberin-Oberfläche mit editierbarem SMTP wäre eine neue Funktion und damit außerhalb von S8 | **Beantwortet 05.09.2026:** Betreiberin-Oberfläche **mit eigener Rolle „BetreiberIn"**. Das geht über (b) hinaus und ist eine **Rahmenplan-Änderung** (Schritt 7 „Nicht Umfang: Rollen"; R38): Migration für `users.role`, Wächterfunktion neben `ist_admin()`, Zugriffsregeln je Seite, Rollenauswahl (NL-05, KS-01), `install.php`, Handbuch, Wortliste; Rechteänderung für das Bedrohungsmodell in P6. Folgeentscheidungen in F-S8-07 |
| F-S8-03 | „Stammdaten systemweit" bis P5 unverändert stehen lassen? Und danach? | **Beantwortet 05.09.2026, ergänzt:** der **Menüpunkt entfällt schon in S8**; die Seite bleibt per Adresse für Admins erreichbar, der Code fliegt mit R39 in P5. Handbuch 9.4 bekommt den Vermerk. Die Karte „Vordefinierte Standorte" (SD-04) bleibt bis P5 stehen — sie legt nichts Neues an (F-S8-08) |
| F-S8-04 | Die APK-Karte: in S8 unverändert lassen, oder die Geräte-Seite schon auf den Store-Weg ausrichten? Genauer (05.09.2026): R65 macht ab dem internen Test-Track den Store zum Regelweg, Handbuch 10.1 nennt ihn so, die APK-Karte bleibt Rückfall bis zur Produktionsfreigabe. Optionen: (a) Karte bleibt, Umbau mit der Produktionsfreigabe; (b) S8 gestaltet eine Karte „App installieren" mit dem Track als erstem Weg und dem APK darunter | **Beantwortet 05.09.2026: (b), jetzt.** Die Geräte-Seite bekommt die Karte „App installieren" — Store-Track als erster Weg, APK als Rückfall darunter, bis zur Produktionsfreigabe (R65). Mockup in Schritt 3 |
| F-S8-05 | Weitere Rückmeldungen über die sechs hinaus? | **Beantwortet 05.09.2026:** nein. Geschlossen |
| F-S8-06 | **Umfang:** Nimmt S8 die Tagesübersicht (Ruhezeiten, Schneiden, Spuren als GPX, GPX importieren) mit hinein — (A) S8 ordnet sie um und fasst `index.php` an, das S9 ebenfalls umbaut — oder (B) legt S8 das Ordnungsprinzip programmweit fest, benennt die Ruhezeiten als konkreten Punkt, und S9 setzt ihn um? | **Beantwortet 05.09.2026: (B).** S8 legt das Ordnungsprinzip programmweit fest (E-S8-01) und gibt S9 die Vorgabe für die Tagesübersicht mit (5.7) |
| F-S8-07 | **Rolle „BetreiberIn" in S8** (aus F-S8-02) — zu bestätigen: (1) die **Rahmenplan-Änderung** (Fassung 28: Schritt 7 nimmt die Rolle auf, R38 behält Support-Rolle, TOTP, Audit); (2) **Rechte** als Vorschlag: BetreiberIn ⊇ Admin ⊇ NutzerIn — die BetreiberIn kann alles, was ein Admin kann, plus den Bereich „Betrieb"; Admins sehen „Betrieb" nicht mehr; was dort liegt, entscheidet Schritt 2; (3) **Bestand** als Vorschlag: die Migration macht **alle heutigen Admins** zu BetreiberInnen, niemand verliert Zugriff, Rückstufung von Hand — Alternative: nur das älteste Admin-Konto; (4) **Vergabe:** nur eine BetreiberIn vergibt die Rolle; das letzte BetreiberIn-Konto lässt sich weder zurückstufen noch löschen; `install.php` legt das erste Konto als BetreiberIn an | **Beantwortet 05.09.2026: alle vier Punkte bestätigt.** (1) ja — die Rahmenplan-Änderung wird **gegen den dann aktuellen Stand von `main`** geschrieben, nicht gegen Fassung 27; (2) ja; (3) alle heutigen Admins werden BetreiberInnen; (4) ja. → E-S8-02 |
| F-S8-08 | Karte „Vordefinierte Standorte" (SD-04) auf der NutzerInnen-Seite: bleibt bis P5 stehen, oder auch in S8 ausblenden? | **Vorschlag:** bleibt — sie legt nichts Neues an; Widerspruch des Auftraggebers genügt |

Entschiedene Fragen werden in Schritt 2 als E-Einträge nach Abschnitt 4
überführt (K6).

---

## 4. Entscheidungen (E-S8)

**Alle Einträge E-S8-01 bis E-S8-14 sind am 05.09.2026 vom Auftraggeber
bestätigt** („finde ich gut so"). Wo Optionen standen, gilt der jeweils als
**Vorschlag** markierte Weg (E-S8-04: a „Verwaltung"; E-S8-09: 36 px;
E-S8-10: mit „Kopieren"). Optionen und Preise bleiben als Begründung stehen.

### E-S8-01 Ordnungsprinzip — programmweit (Register-Kandidat)

Jede Funktion hat **genau einen Ort**, und der folgt aus drei Fragen — nie
aus dem Zeitpunkt, zu dem sie gebaut wurde:

1. **Wer** (Zielgruppe) → der Menübereich: **Einstellungen** (NutzerIn),
   **Verwaltung** (Admin), **Betrieb** (BetreiberIn). Ein Bereich ist nur
   sichtbar, wer ihn benutzen darf.
2. **Woran** (Objekt) → die Seite: Handlungen an einem Diensttag oder
   Einsatz liegen bei diesem Diensttag oder Einsatz; Einstellungen des
   Kontos unter Einstellungen; Einstellungen der Installation unter
   Verwaltung (Inhalt, Konten, Texte) oder Betrieb (Server, Speicher,
   Updates).
3. **Wie oft** (Häufigkeit) → die Ebene auf der Seite: Die **Primärfläche**
   trägt nur den Regelweg — aufzeichnen mit Uhr oder Handy, im Browser die
   Einsätze ausfüllen. **Ausnahmen** (GPX einfügen, aus der Ruhezeit
   schneiden, Diensttage zusammenführen, Gerät ohne Code anlegen, APK von
   Hand, Backups ohne Konto) liegen **eine Ebene tiefer**: im Aktionsmenü
   des Objekts, in einer zugeklappten Karte oder auf einer Unterseite. Eine
   Ausnahme bekommt **nie** einen eigenen Hauptmenüpunkt.

Dazu drei Regeln der Darstellung:

4. **Zustand vor Handlung, aber nur bei Problem:** Diagnosekarten
   (Schlüsselableitung, Einsätze ohne Diensttag, Serverschlüssel fehlt,
   Jobs scheitern) erscheinen oben und nur, wenn es etwas zu tun gibt.
5. **Erklärtext einheitlich:** eine zugeklappte Karte „Was hier gilt" am
   Ende der Seite, sonst der Verweis ins Handbuch — keine Erklärkarten
   zwischen den Handlungen.
6. **Ein Begriff je Ding, ein Ding je Begriff** — über alle Zielgruppen
   hinweg (Wortliste; für Backup: E-S8-06).

Und die Regel, die den Wildwuchs künftig verhindert:

7. **Wer eine Funktion baut, benennt ihren Ort** nach 1–6 im Konzept oder
   im Backlog-Punkt; ein Paket ohne benannten Ort wird nicht gemergt.
   Als Ergänzung zu K1 in den Rahmenplan.

*Preis:* eine Registerzeile im Rahmenplan, eine Ergänzung an K1; die Regeln
4 und 5 kosten je Seite eine Umordnung, die S8 ohnehin macht.
*Alternative:* nur 1–3 als Regel, 4–7 als Empfehlung — dann bleibt der
Wildwuchs erlaubt, nur besser sortiert.

### E-S8-02 Rolle „BetreiberIn" — **entschieden 05.09.2026** (F-S8-07)

Dritte Rolle `betreiberin` in `users.role`. **Rechte:** BetreiberIn ⊇ Admin ⊇
NutzerIn — die BetreiberIn kann alles, was ein Admin kann, und sieht als
Einzige den Bereich „Betrieb". **Bestand:** die Migration macht alle
vorhandenen Admins zu BetreiberInnen; Rückstufung von Hand. **Vergabe:** nur
eine BetreiberIn vergibt oder entzieht die Rolle; das letzte
BetreiberIn-Konto lässt sich weder zurückstufen noch löschen; `install.php`
legt das erste Konto als BetreiberIn an. **Rahmenplan:** Schritt 7 nimmt die
Rolle in den Umfang, R38 behält Support-Rolle, TOTP und Audit; die Änderung
wird gegen den bei Push aktuellen Stand von `main` geschrieben.
*Preis:* eine Migration, `ist_betreiberin()` neben `ist_admin()`, Wächter je
Betrieb-Seite, Rollenauswahl an zwei Stellen, `install.php`, Handbuch,
Wortliste, Demo-Fixture (Rollenwert), Bedrohungsmodell in P6.

### E-S8-03 Umfang Tagesübersicht — **entschieden 05.09.2026** (F-S8-06, B)

S8 fasst `index.php` nicht an. Die Vorgabe an S9 steht in 5.7.

### E-S8-04 Menüstruktur — drei Bereiche, ein Zahnrad

Das Zahnrad bleibt der eine Einstieg; die Übersichtsseite und die Leiste
tragen **dieselbe Liste aus einer Quelle** (B-S8-01) in drei Blöcken:
**Einstellungen** (alle), **Verwaltung** (Admin, BetreiberIn), **Betrieb**
(BetreiberIn). Einträge und Reihenfolge in 5.1.
*Option a — Blockname „Verwaltung":* passt zu „Betrieb" und „Einstellungen"
(drei Substantive), kostet die Umbenennung von „Administration" in Handbuch
11 und Wortliste. *Option b — „Administration" bleibt:* kein Aufwand, aber
„Administration/Betrieb" mischt Fremdwort und Wort. **Vorschlag: a.**

### E-S8-05 Die Wartungsseite wird aufgelöst — der Block „Betrieb" in sechs Seiten

**Fassung vom 05.09.2026 nach Durchsicht von Mockup 02** (die erste Fassung
sah eine Sammelseite „Serverbetrieb" vor; der Auftraggeber hat sie in
Einzelseiten zerlegt — jede Seite ein Anliegen):

| Seite (Betrieb) | Trägt | Aus heute |
|---|---|---|
| **Status** | die Lage der Installation auf einen Blick, rein lesend, mit Ampel (E-S8-16); Problemfälle als rote Zeilen mit Link zur zuständigen Seite | W-01, W-03, W-05, Umgebung, Kennzeilen aus AB/BZ/KB; Serverschlüssel-Zustand statt der zweifachen Karte (B-S8-05) |
| **Statistik** | Zahlen über die Installation, rein lesend, ohne Ampel: Kennzahlen (Konten, Geräte, Einsätze gesamt, Einsätze in 30 Tagen), Karte Konten nach Rolle und Aktivität, Karte Geräte nach Art und Aktivität. **Der Ort für P5:** Geräteauswertung (Backlog 80, R64-Herkunft) und Betriebslage-Dashboard (R38) | neu (Rückmeldung 05.09.2026 zu Mockup 03); die Kachel „Daten" aus der ersten Fassung von Status |
| **Updates** | Wartungsmodus ein/aus (gehört zum Deploy) · ausstehende Migrationen mit „Ausstehende ausführen" · Ausgeführte zugeklappt bis P5 (R66) | W-02, W-09; `update.php` leitet hierher weiter bis P6 (Nr. 77) |
| **Hintergrundjobs** | Zustand je Job (wie W-06) · Auslöser: die drei Wege, Cron-Zeile und Token-Adresse im `codeblock-lang` mit „Kopieren", Token neu | W-06, W-07 |
| **Servereinstellungen** | Speicher: Speichergrenze, Warnschwellen, Speicherbalken mit Belegung nach Art, Ablage und Reste (E-S8-18) — der Ort für weitere Installationsregeln des Betriebs (P5: Mengenbremse) | AB-05, AB-06, AB-08, KB-06 (löst B-S8-06) |
| **Komplett-Backup** | wie heute; Serverschlüssel-Karte wird Meldung mit Link auf Status; „Belegt" verweist auf Servereinstellungen | KB-* |
| **Backup-Ziele** | wie heute; Serverschlüssel-Karte wird Meldung mit Link auf Status | BZ-* |

Was **fortfällt**: die Karte „Einsätze ohne Diensttag" (E-S8-17), die Karte
„Logo" (→ Installation, E-S8-12), die Seite „Wartung" als Ganzes. Was
**Status** nur anzeigt, hat seine Handlung auf einer der fünf anderen Seiten.
*Preis:* fünf neue Seiten (Status, Statistik, Updates, Hintergrundjobs,
Servereinstellungen), eine Weiterleitung, Umzug von acht Karten, Handbuch 11.3
und Technik 7 nachziehen.
*Verworfen:* eine Sammelseite „Serverbetrieb" (Mockup 02) — zu viel auf einer
Fläche, derselbe Fehler wie heute in kleiner.

### E-S8-06 Backup: drei Namen, drei Orte, ein Verb

| Ding | Name (Menü, Karten, Handbuch) | Ort | Regeln dort |
|---|---|---|---|
| (a) Datei der NutzerIn | **Backup** | Einstellungen → Backup | keine |
| (b) Paket je Konto durch die Verwaltung | **Konto-Backup** (Plural: Konto-Backups) | Verwaltung → Konto-Backups; Karte „Konto-Backups" auf der Kontoseite | Erinnerung nach, Aufbewahrung je Konto, Admin-Mail; „Alle sichern"; Backups ohne Konto |
| (c) Dump der Installation | **Komplett-Backup** | Betrieb → Komplett-Backup | Plan, Stände aufbewahren, Stände |
| Versand von (b) nach außen | **Backup-Ziele** | Betrieb → Backup-Ziele | Ziele, Versand-Schalter |
| Grenze und Belegung aller drei | **Speicher** | Betrieb → Servereinstellungen | Speichergrenze, Warnschwellen, Speicherbalken |

Verben: **sichern** (R56) für das Erzeugen; **einspielen** für jeden Rückweg
in ein Konto (N wie A); **wiederherstellen** nur für die Installation
(`wiederherstellen.php`). **Löschen** bleibt, aber Rückfragetext und
Gefahren-Ton nennen die Tragweite (Paket, Stand, Ziel, Gerät, Konto).
Kennzahlen und Filter heißen **„Konto-Backup überfällig"** und **„nie
Konto-Backup"** — sie messen genau das (B-S8-07). Gerät: **„Entkoppeln"** für
N und A gleich (heute „Löschen" gegen „Entkoppeln", B-S8-21). Die
Freigabe-Karte der Kontoseite bekommt eine Zustandszeile „freigegeben für …,
die NutzerIn spielt mit ihrem Wiederherstellungsschlüssel ein" (B-S8-09).
*Preis:* Wortliste, Handbuch 6 und 11, `Backup-Format.md` (Begriffe), keine
Datenänderung.
*Alternative:* „Admin-Backup" statt „Konto-Backup" — sagt, wer es macht,
nicht, was es ist; auf der Kontoseite der NutzerIn wäre „Admin-Backup"
irritierend.

### E-S8-07 Menü-Verhalten (Nr. 75)

Fettdruck nur für den aktiven Eintrag (wie S3 Block F für die Tagesleiste);
die drei Blöcke als **auf- und zuklappbare Gruppen** mit Blocküberschrift,
Zustand je Sitzung gemerkt. Vorgabe: der Block der aktiven Seite ist offen,
„Einstellungen" immer; am Schreibtisch (≥ 1024 px) alle offen, solange sie in
die Höhe passen. Kein neuer Baustein — `<details>`-Semantik in der Leiste.
*Preis:* Stylesheet, ein kleines Skript für den Sitzungszustand, Bilderlauf.
*Alternative:* nicht einklappbar, nur Fettdruck beheben — reicht für zwei
Blöcke, nicht für drei mit bis zu fünfzehn Einträgen in der Schublade.

### E-S8-08 Filterreihe (Nr. 73)

Am Baustein: das **Suchfeld in eigener Zeile in voller Breite**, die
**Filterplaketten darunter** in einer Zeile mit erlaubtem Umbruch und festem
Abstand — der Umbruch ist dann Absicht, nicht Unfall. Gilt für jede Liste mit
Suche und Filtern (heute nur NutzerInnen, künftig P5-Listen).
*Preis:* Stylesheet am Baustein, Bilderlauf acht Breiten.
*Alternative:* Filter als Auswahlfeld unter 1024 px — spart Platz, versteckt
aber die Zahlen je Filter.

### E-S8-09 Bedienhöhe am Schreibtisch (Nr. 74) — S9 wartet darauf

**Zwei Stufen:** 44 px bleibt die Vorgabe; für Zeigergeräte
(`@media (hover: hover) and (pointer: fine)`, ab 1024 px) eine **dichte Stufe
36 px** für Knöpfe, Felder, Listenzeilen und Menüeinträge. Begründung: Die
häufigste Arbeit — Einsätze nach der Aufzeichnung ausfüllen — ist
Formulararbeit am Schreibtisch; 36 px liegt über der Mindestzielgröße von
WCAG 2.5.8 (24 px) und entspricht der dichten Stufe verbreiteter
Designsysteme; Touch-Laptops mit Maus als Hauptzeiger bekommen 36, reine
Touch-Geräte 44. Kontrast ändert sich nicht (Höhe, keine Farbe); Nachtrag in
`Design.md` und `CLAUDE.md` 5; `tools/screenshots/` misst zwei Sollwerte.
*Preis:* Token `--bedienhoehe` in zwei Stufen, Messwerkzeug, Bilderlauf,
zwei Dokumente.
*Alternative:* 40 px als Kompromiss — spürbar weniger dicht als 36, aber
weniger Abstand zur Touch-Stufe; oder es bleibt bei 44 (dann entfällt S9
PS-3 „kompaktere Buttons").

### E-S8-10 Wertekasten (Nr. 78, B-S8-13)

Zweite Stufe `codeblock-lang`: `--schrift-fest` in `--groesse-2`, ohne
Sperrung, mit Umbruch an beliebiger Stelle; dazu ein Knopf **„Kopieren"** in
der Kartenecke (Zwischenablage), weil lange Werte abgeschrieben Fehler
machen. Der Kopplungscode behält die große Stufe. Fünf Stellen: Cron-Zeile,
Token-Adresse, Setz-Link, Serverschlüssel-Zeile, Geräte-ID/API-Schlüssel.
*Preis:* ein Baustein-Zusatz, `Design.md`, fünf Stellen; „Kopieren" ist ein
kleiner neuer Baustein und braucht das Mockup (Schritt 3).
*Alternative:* ohne „Kopieren" — darf vorab in der Backlog-Runde laufen.

### E-S8-11 Geräte-Seite (F-S8-04, B-S8-12)

Reihenfolge nach Häufigkeit: **1. Gerät koppeln** (Karte in drei Zuständen,
unverändert) · **2. Geräte** (Liste) · **3. App installieren** — neue Karte
mit zwei Zeilen: *Garmin-Uhr* (Weg über den Connect-IQ-Store, sofern der
Link aus der Uhr-Auslieferung vorliegt — zu prüfen, 2.9) und *Android-Handy
und Wear-OS-Uhr* (Play Store, interner Test-Track als Regelweg nach R65); das
**APK als Rückfall** darunter zugeklappt („Ohne Play Store: APK von Hand") mit
SHA-256 wie heute · **4. Gerät ohne Code anlegen** — zugeklappte Karte statt
Listenformular (Ausnahme, Regel 3) mit der Zugangsdaten-Karte wie heute.
*Preis:* eine neue Karte, zwei Karten zugeklappt, Handbuch 10.1 und 12.
*Zu klären in Schritt 3:* die Zeile für den Test-Track (Einladungslink oder
Anleitung) hängt an der Play-Console-Zuarbeit (Rahmenplan 6).

### E-S8-12 Vorgabe für P5 — wo künftige Optionen liegen

| Kommt in P5 (Beschluss) | Ort nach E-S8-01 | Seite |
|---|---|---|
| Support-Adresse (R31) | Installation · Verwaltung | **Installation** (neu, aus Rechtstexte + Logo) |
| Rechtstexte (R32) | Installation · Verwaltung | Installation |
| Betriebsart der Registrierung, Einwilligungen mit Fassung (R37) | Installation · Verwaltung | Installation |
| Ankündigungsbanner (R38) | Installation · Verwaltung | Installation |
| Konto-Lebenszyklus, Kontostatus, Selbstlöschung (R37) | Konto · Verwaltung | Kontoseite, NutzerInnen |
| Support-Rolle (R38) | Rolle | NutzerInnen (Rollenauswahl) |
| Admin-TOTP (R38) | Konto · Einstellungen | Profil, Karte „Sicherheit" (nur Admin/BetreiberIn) |
| Audit-Protokoll, Fehlerprotokoll-Sicht, Health-Endpunkt (R38) | Installation · Betrieb | Status (Health, Fehlerprotokoll); Audit als eigene Seite oder Unterseite von Status |
| Betriebslage-Dashboard, Geräteverteilung (R38, R42, Nr. 80) | Installation · Betrieb | **Statistik** — P5 ergänzt dort Karten (Geräteauswertung nach R64-Herkunft, Nutzung je Konto); Status bleibt reine Ampel |
| Torwächter für Migrationen (R40.4, R66) | Installation · Betrieb | Updates |
| Mengenbremse `ingest.php`, IP-Grenzwerte (R19, R37) | Installation · Betrieb | Servereinstellungen |
| S2-Sicherungseinstellungen | bereits verortet (E-S8-06) | Konto-Backups, Servereinstellungen |

### E-S8-13 Umgang mit den Befunden B-S8

**S8 behebt:** 01, 02, 03, 04, 05, 06, 08, 09 (Zustandszeile), 10, 11 (Karte
„Abonnement" bis P5 ausblenden), 12, 13, 15, 17, 19 (Kennzahlen bleiben auf
NutzerInnen; Konto-Backups zeigt Pakete, Größe und zwei Links), 20 (eine
Karte „Was hier gilt" je Seite, zugeklappt, am Ende), 21 (Wortliste).
**Backlog-Kandidaten:** 07 (Nutzer-Backups beobachten — neue Funktion), 16
(Jobs in der Oberfläche anhalten — neue Funktion), 18 (Sammelpunkt
Import/Export — mit S9 klären). **P5:** 14.

### E-S8-14 Stammdaten systemweit — **entschieden 05.09.2026** (F-S8-03)

Menüpunkt entfällt in S8; Seite bleibt per Adresse erreichbar (Admin,
BetreiberIn) bis P5 den Code entfernt (R39); Handbuch 9.4 mit Vermerk; Karte
„Vordefinierte Standorte" (SD-04) bleibt (F-S8-08, ohne Widerspruch).

### E-S8-15 Die Leiste zeigt die Unterpunkte der aktiven Seite — **entschieden 05.09.2026**

Der aktive Menüeintrag klappt in der linken Leiste auf und zeigt die
Kartentitel der Seite als klickbare Sprungmarken: eine Stufe kleiner,
eingerückt, **ohne Strich und ohne Symbol, 28 px Zeilenhöhe** (Fassung 2 nach
Mockup 03). Beim Scrollen wird **je Spalte die oberste sichtbare Karte fett**
— am Schreibtisch in zwei Spalten also bis zu zwei Punkte, am Handy immer
einer (**bestätigt 05.09.2026**). Zweck: Übersicht
behalten, wenn der Inhalt rechts länger ist als das Fenster. In der mobilen
Schublade dieselbe Liste ohne Markierung. Das ist ein **neuer Baustein**
(Unterliste am Eintrag, Sprungmarken an den Karten, ein kleines Skript);
Mockup 03 zeigt ihn.

### E-S8-16 Statusseite — **entschieden 05.09.2026**

Erste Seite des Blocks Betrieb, rein lesend: Was läuft, was nicht. Zeilen
nach Bereich (Server, Hintergrundjobs, E-Mail, Backups, Daten), jede mit
einer Plakette in der **Ampel** der vorhandenen Töne — **blau** in Ordnung,
**orange** Achtung (läuft, braucht aber Aufmerksamkeit: Rückstand, überfällig,
ausstehend, Schwelle 70 %), **rot** Warnung (etwas ist kaputt oder fehlt:
Job scheitert, seit über 24 h kein Lauf, Schwelle 90 %, Ablage nicht
beschreibbar, Serverschlüssel fehlt, Konten blockiert), **neutral** nicht
eingerichtet oder keine Aussage. Oben eine Meldung mit der Zahl der Punkte,
die Aufmerksamkeit brauchen; jede Zeile mit Link auf die Seite, auf der man
handelt. **Abgrenzung (05.09.2026):** Status ist reine Ampel — Zahlen stehen
auf **Statistik** (E-S8-05); dort baut P5 Geräteauswertung und Dashboard
auf (R38, Nr. 80). Health-Endpunkt und Fehlerprotokoll (R38) kommen auf
Status. Menüeintrag „Status" mit Plakette (Zahl der
Achtung-/Warnpunkte), wie „Updates" mit „n offen".
*Zu prüfen in der Umsetzung:* ob eine letzte erfolgreiche Mailzustellung
aufgezeichnet wird; falls nicht, zeigt die E-Mail-Zeile nur „eingerichtet /
nicht eingerichtet". Eine „Testmail senden"-Funktion wäre neu — Backlog-
Kandidat.

### E-S8-17 „Einsätze ohne Diensttag" entfällt aus dem Betrieb — **entschieden 05.09.2026**

Nutzersache: Jede NutzerIn sieht ihre eigenen als „Zuordnung offen" mit
Zähler in der Diensttage-Leiste und räumt sie über die Tageszuordnung selbst
auf. Die Admin-Karte war ein Reparaturwerkzeug aus der Einführung des
Diensttag-Modells und blieb stehen. **Entschieden 05.09.2026:** kein
Menüpunkt, keine Karte. **Auch keine Statistikzeile** (Freigabe 05.09.2026: „weg"). Die Karte W-08
und ihre Logik werden ersatzlos entfernt; die NutzerInnen-Seite bleibt.

### E-S8-18 Speicherbalken und Zweispaltigkeit — **entschieden 05.09.2026**

Der **Speicherbalken** mit Legende und Schwellenstrich (Mockup 02) ist
freigegeben und wird Baustein auf „Servereinstellungen". **Zwei Spalten** am
Schreibtisch werden Seitenregel für Seiten mit vielen Karten („wenn zu viel
da ist"), nicht Pflicht: Die Umsetzung setzt sie ein, wo mehr als vier Karten
stehen, und nennt es im Prüfprotokoll; Seiten mit wenigen Karten bleiben
einspaltig.

---

## 5. Zielbild

### 5.1 Menü

| Block | Sichtbar für | Einträge (Reihenfolge) |
|---|---|---|
| **Einstellungen** | alle | Profil · Geräte · Standorte · Rettungsmittel · Backup · Import / Export |
| **Verwaltung** | Admin, BetreiberIn | NutzerInnen · Konto-Backups · Installation · Demo-Konto |
| **Betrieb** | BetreiberIn | Status · Statistik · Updates · Hintergrundjobs · Servereinstellungen · Komplett-Backup · Backup-Ziele |
| Fuß | alle | Abmelden |

Von 14 auf 17 Einträge (Fassung 05.09.2026 nach Mockup 03) — jeder an einem begründeten Ort und jede Seite ein Anliegen. Geräte rückt
vor die Stammdaten, weil das Koppeln der erste Schritt jeder neuen Nutzerin
ist und die Stammdaten einmal gepflegt werden.

### 5.2 Seiten — was bleibt, was umzieht, was neu ist

| Seite | Heute | Ziel | Adresse |
|---|---|---|---|
| Profil, Standorte, Rettungsmittel, Backup, Import/Export | bleiben | unverändert bis auf Wortliste | wie heute |
| Geräte | fünf Anliegen gemischt | E-S8-11 | wie heute |
| NutzerInnen | Filterreihe bricht | E-S8-08; Kennzahlen bleiben; Rollenauswahl mit BetreiberIn | wie heute |
| Kontoseite | Backups, Abonnement-Platzhalter | Karte „Konto-Backups" mit Freigabe-Zustandszeile; „Abonnement" ausgeblendet; „Entkoppeln" | wie heute |
| Stammdaten systemweit | im Menü | ohne Menüpunkt (E-S8-14) | wie heute |
| Backups | Regeln, Ablage, Grenze | **Konto-Backups**: Kennzahlen (Pakete, Größe, zwei Links), Alle sichern, Regeln (Erinnerung, Aufbewahrung, Admin-Mail), Backups ohne Konto, Was hier gilt | Datei bleibt (R56), Titel neu |
| Rechtstexte | eigene Seite | **Installation**: Logo der Installation, Impressum, Datenschutz; P5 ergänzt (E-S8-12) | Umsetzung: neue Adresse, alte leitet weiter |
| Demo-Konto | bleibt | Erklärkarten als „Was hier gilt" | wie heute |
| Wartung | neun Blöcke | entfällt — aufgeteilt auf die vier folgenden Seiten; `update.php` → Updates | Weiterleitung bis P6 |
| — | — | **Status**: Meldung mit Zahl der Achtung-/Warnpunkte · Server · Hintergrundjobs · E-Mail · Backups — jede Zeile mit Ampelplakette und Link | neu |
| — | — | **Statistik**: Kennzahlen · Konten · Geräte — rein lesend, ohne Ampel; P5 ergänzt Karten | neu |
| — | — | **Updates**: Wartungsmodus (Karte mit Ablauf-Kurzform, Balken wie heute) · **Ausstehende Updates** mit Backup-Meldung, Kästchen für Blockierte, „Ausstehende ausführen" · Ausgeführt zugeklappt (bis P5) · Fassung | neu |
| — | — | **Hintergrundjobs**: Zustand je Job · Auslöser (drei Wege, `codeblock-lang` mit „Kopieren", Token neu) · Was hier gilt | neu |
| — | — | **Servereinstellungen**: Speicher (Grenze, Schwellen, Balken, Ablage, Reste) · Was hier gilt | neu |
| Backup-Ziele | Serverschlüssel-Karte, Versand, Ziele | Meldung mit Link statt Karte; sonst gleich | wie heute, Menüblock Betrieb |
| Komplett-Backup | Serverschlüssel-Karte, Regeln mit „Belegt" | Meldung mit Link; „Belegt" verweist auf Speicher; sonst gleich | wie heute, Menüblock Betrieb |

### 5.3 Rechte je Seite

| Bereich / Seite | NutzerIn | Admin | BetreiberIn |
|---|---|---|---|
| Einstellungen (alle sechs) | ja | ja | ja |
| Verwaltung: NutzerInnen, Kontoseite, Konto-Backups, Installation, Demo-Konto | — | ja | ja |
| Rolle „BetreiberIn" vergeben oder entziehen | — | — | ja |
| Betrieb: Status, Statistik, Updates, Hintergrundjobs, Servereinstellungen, Komplett-Backup, Backup-Ziele | — | — | ja |
| Stammdaten systemweit (ohne Menü) | — | ja | ja |
| `install.php`, `wiederherstellen.php` | außerhalb der Anmeldung (Nachweis) | | |

### 5.4 Wortliste Backup (Auszug für die Umsetzung)

Backup (a) · Konto-Backup (b) · Komplett-Backup (c) · Backup-Ziele · Speicher
· Paket (Datei von b) · Stand (Datei von c) · Ablage (Ort im Dateisystem) ·
Freigabe · sichern · einspielen · wiederherstellen (nur Installation) ·
Wiederherstellungsschlüssel (NutzerIn) · Serverschlüssel (BetreiberIn) ·
Kontokennung · Backup-Passwort · Passphrase (nur versiegelter Download).
Gestrichen: „Sicherung" als Substantiv (S7), „Admin-Backup", „Datenbank-Update"
(wird „Updates"), „Wartung" als Seitenname.

### 5.5 Betrieb im Einzelnen

**Status** (E-S8-16): Meldung oben („n Punkte brauchen Aufmerksamkeit") ·
Server: Serverbetrieb, Updates, Serverschlüssel, Schlüsselableitung,
Datenbank, PHP und Zeitzone · Hintergrundjobs: Auslöser mit letztem Lauf,
je Job · E-Mail: SMTP, letzte Zustellung (zu prüfen), Entkopplung ·
Backups: Komplett-Backup (jüngster Stand gegen Plan), Konto-Backups
(überfällig, nie), Backup-Ziele, Speicher, Ablage. Keine Kachel „Daten"
mehr — Zahlen stehen auf Statistik.

**Statistik (Fassung 2, 05.09.2026):** alles **ohne Demo-Konto**, Anteile
als Prozent neben der Zahl, Zeiträume **7 Tage / 30 Tage / 6 Monate** im
Zeitraum-Raster (neuer kleiner Baustein). Kennzahlraster (Konten, Geräte,
Einsätze gesamt, Einsätze in 30 Tagen) · **Konten:** nach Rolle, ohne Gerät;
Zeiträume: zuletzt angemeldet, neu angelegt · **Geräte:** Garmin-Uhren,
Wear-OS-Uhren, Android-Handys, deaktiviert; Zeiträume: zuletzt gemeldet,
gekoppelt · **Einsätze:** Zeiträume: Einsätze, NutzerInnen mit Einsatz,
**zwei Durchschnitte** (05.09.2026): Ø je aktiver NutzerIn = Einsätze ÷
NutzerInnen mit mindestens einem Einsatz im Zeitraum, Ø je NutzerIn gesamt =
Einsätze ÷ alle Konten ohne Demo; gezählt nach Diensttag ·
**Gerätemodelle:** Tabelle, Spalten Gerät, Hersteller, Art, Geräte, Anteil %,
NutzerInnen, Anteil %; sortierbar je Spalte über die Adresse (wie die
NutzerInnen-Liste), Vorgabe Anteil absteigend; **„Als CSV"** (Semikolon,
UTF-8-BOM); am Handy waagerecht scrollbar. **Hersteller wird abgeleitet,
nicht gespeichert** (E-S4-28): Teilenummer vorhanden → Garmin, sonst erstes
Wort der Rohangabe `geraet_teil`; Art aus `geraet_art` plus Teilenummer
(Uhr / Uhr (Wear OS) / Handy) — zu prüfen, was die Wear-OS-App als `art`
sendet. Alles aus `users`, `devices`, `missions`, `pair_sessions` zählbar.
**Rahmenplan:** Backlog 80 zieht teilweise nach S8 vor (Modelle, Nutzung);
die Herkunft je Einsatz (R64-Werte) bleibt P5 mit Datenschutz-Vorbedingung.

**Updates (freigegeben 05.09.2026):** Wartungsmodus (Karte mit dem
fünfstufigen Ablauf als Kurzform, Balken wie heute) · Karte **„Ausstehende
Updates"** (Nummer, Name, Herkunft, Zustand; Blockierte mit Kästchen und
Grund; Meldung mit jüngstem Komplett-Backup und Alter; „Ausstehende
ausführen"; leer: „Alles aktuell") · Ausgeführt (zugeklappt, Vorschau mit
Zahl, letzter Nummer, Datum; bis P5) · Fassung (Web, Datenbankstand,
Mindeststand Uhr-App, Android-Stand — Ort des Torwächters in P5).

**Hintergrundjobs:** Zustand je Job (Plaketten: letzter Lauf, Auslöser,
Rückstand, Fehler; Liegenbleiber) · Auslöser: 1. Kommandozeile, 2. Adresse mit
Token, 3. Huckepack; Werte im `codeblock-lang` mit „Kopieren"; „Neues Token
erzeugen"; Hinweis auf `--pause` · Was hier gilt.

**Servereinstellungen (Fassung 2, 05.09.2026):** Speicher mit **zwei
Balken** — „Backups" gegen die Speichergrenze (Konto-Backups,
Komplett-Backups, frei) und „Installation gesamt" gegen den **Webspace laut
Hosting** (Datenbank, Dateien, Konto-Backups, Komplett-Backups, frei);
Formular: Speichergrenze Backups, Warnschwellen (gelten für beide Balken),
Webspace laut Hosting (optional, neues Feld in `app_state`); lesende Zeilen
Ablage, Reste · Was hier gilt. Versendete Pakete auf Backup-Zielen zählen
nirgends mit — sie liegen außerhalb des Webspace. **Messung:**
Datenbank aus `information_schema` (Daten und Indizes), Dateien per
Verzeichnislauf ohne `sicherungen/`, einmal täglich vom Aufräumjob in
`app_state`; der freie Webspace wird **nicht gemessen** (`disk_free_space()`
zeigt auf gemeinsam genutztem Hosting den Host, nicht die Quota) — ohne
Angabe nur die Summe. **Die Grenze bleibt bei den Backups**: Die Datenbank
darf nicht angehalten werden. Am Schreibtisch einspaltig auf Lesebreite
(760 px), solange nur eine Karte steht (E-S8-18). Ab 70 % färbt sich das
betroffene Balkensegment orange, ab 90 % rot; Status zählt mit.

**Komplett-Backup, Backup-Ziele:** wie heute; statt der Serverschlüssel-Karte
eine Meldung mit Link auf Status, solange er fehlt.

### 5.6 Verwaltung: Installation im Einzelnen

Logo der Installation (Segment mit Vorschau-Kachel, wie heute W-04) ·
Impressum (Text, Stand, „Ansehen ›" auf die öffentliche Seite) · Datenschutz
(Text, Stand, „Ansehen ›"; Hinweis auf die Android-App) · eine
Speichern-Leiste, die nennt, was ungespeichert ist. P5 ergänzt
Support-Adresse, Registrierung, Banner als weitere Karten derselben Seite.
Markdown-Vorschau wäre neu — Backlog-Kandidat.

### 5.7 Vorgabe an S9 (aus F-S8-06, B)

Auf der **Tagesübersicht** gilt E-S8-01 Regel 3: Die Primärfläche zeigt die
Einsätze des Tages und den Weg, sie auszufüllen. **Spuren, Ruhezeiten,
Schneiden, GPX einfügen, Spuren als GPX** liegen eine Ebene tiefer — im
Aktionsmenü des Diensttags und auf der vorhandenen Unterseite „Spuren des
Tages" (`tag_spuren.php`); die Tagesübersicht nennt sie mit einer Zeile
(„n Ruhezeiten, Spuren des Tages ›"). „Diensttag zusammenführen" und
„Zuordnung" ebenso. S9 entscheidet die Form mit seinen Mockups (PS-3, PS-5)
und benennt die Berührung mit dem Stylesheet (R73).

---

## 6. Mockups (Schritt 3)

Ablage `docs/konzepte/konzept-s8/mockups/`, je Darstellung eine Freigabe
(`CLAUDE.md` 5). Die HTML-Fassungen entstehen in der Konzeptsitzung mit den
Token aus `server/assets/style.css` (Auszug, unverändert) und den Regeln der
betroffenen Bausteine; Symbole sind Platzhalter aus dem Vorrat, Schriften
werden im Mockup über Google Fonts geladen (in der Anwendung vendoriert).
Die PNG-Fassungen in acht Breiten rendert die umsetzende Instanz nach der
Umsetzung als Soll-Bilder; die `00-ist-*`-Aufnahmen vor dem ersten Paket.

### 6.1 Baustein-Bilanz — verbindlich für die Umsetzung (05.09.2026)

Der Auftraggeber will, dass **möglichst nichts Neues entsteht**. Die Mockups
sind Illustrationen; gebaut wird nach dieser Tabelle. Wo das Mockup-CSS
einen eigenen Namen trägt, gilt die Spalte „Baustein im Vorrat".

| Im Mockup | Baustein im Vorrat (`style.css`, `ui.php`) | Neu? |
|---|---|---|
| Kopfleiste, Leiste, Schublade, Eintrag, aktiver Eintrag | `.kopf`, `.leiste`, `.eintrag`, `.eintrag.aktiv` | nein |
| Gruppen „Einstellungen / Verwaltung / Betrieb" (`leiste-gruppe`) | **Akkordeon** der Diensttage-Leiste: `details` + `.akkordeon-zeile`, `.akkordeon-winkel`, `.akkordeon-inhalt`; Gruppenzahl als Text im `.akkordeon-text` | nein |
| Zahl am Menüeintrag („Updates 2", „Status 3") | `.zaehler` (wie „Zuordnung offen"), Ton nach Ampel | nein |
| Übersichtsseite, Blocküberschriften | `.uebersicht-zeile`, `.uebersicht-block` | nein |
| Karten mit Titel, Zahl, Aktion, Vorschau, zugeklappt | `ui_karte_start()` mit `zahl`, `aktion`; `.karte-klappbar`, `.karte-vorschau` | nein |
| Zeilen mit Kleinzeile, Plaketten, Knöpfen, ⋯-Menü | `ui_zeile()` mit `klein`, `plaketten`, `aktionen`; `ui_zeilenaktionen()` (`.blatt`) | nein |
| Ampelplaketten, Meldungen | `ui_plakette()` (neutral, blau, orange, rot); `ui_meldung()` (info, warn, fehler) | nein |
| Formulare: Feld, Feldreihe, Schalter, Segment, Kästchen, Speichern-Leiste | `ui_feld()`, `.feld-reihe`, `ui_schalter()`, `ui_segment()`, `input[type=checkbox]`, `ui_speichern_leiste()` | nein |
| Kennzahlen mit Sprung | `ui_kennzahl()` mit `href` | nein |
| Tabelle Gerätemodelle, sortierbar, scrollbar; Zeiträume 7/30/6 Monate | `.tabelle`, `th.sortable`, `.tabelle-scroll` (Sortierung über die Adresse wie NutzerInnen-Liste) | nein — das „Zeitraum-Raster" aus Mockup 04 Fassung 2 ist **zurückgeführt** |
| „Weg"-Zeilen bei App installieren | `ui_zeile()` mit Symbol, Kleinzeile, Knopf — **zurückgeführt** | nein |
| Migrationszeilen | `ui_zeile()`, Nummer als neutrale Plakette | nein |
| Logo-Vorschau | `ui_logo()` | nein |
| Wertekasten groß (Kopplungscode) | `.codeblock`, `.codeblock-wert` | nein |
| Filterreihe mit Suchfeld darüber (Nr. 73) | `.listenkopf`, `.listensuche`, `.filterreihe`, `.listenfilter` — **Regeländerung** an `.listenkopf` ab 1024 px (Suchfeld eigene Zeile) | Änderung, kein Baustein |
| Links in Statuszeilen („Updates ›") | ganze Zeile als Link (`ui_zeile()` mit `href`, wie `.uebersicht-zeile`) oder `karte-aktion` im Kartenkopf | nein |
| **Unterpunkte der aktiven Seite** (`.eintrag-unter`) | — | **ja** (E-S8-15): Unterliste am Eintrag, Anker an den Karten, Skript für die Markierung beim Scrollen |
| **Speicherbalken** mit Legende und Schwellenstrich | — | **ja** (E-S8-18) |
| **Wertekasten, kleine Stufe** (`.codeblock-lang`) mit „Kopieren" | Zusatzklasse am `.codeblock` | **ja, als Variante** (E-S8-10, Nr. 78): eine Klasse, ein leiser Knopf, `navigator.clipboard` |
| **Zwei Spalten** ab 1200 px bei vielen Karten (`.karten-raster`) | — | **ja, als Layoutregel** (E-S8-18): drei Zeilen CSS, keine neue Darstellung |
| **Bedienhöhe 36 px** für Zeigergeräte | Token `--knopf` in zweiter Stufe | Token-Änderung (E-S8-09) |

Kein neues Symbol (Symbole der neuen Menüpunkte kommen aus dem Vorrat, mit
denselben Doppelungen wie heute), keine neue Farbe, keine neue Schriftgröße,
kein neuer Abstand. `Design.md` bekommt drei Einträge (Unterpunkte,
Speicherbalken, Wertekasten-Stufe) und die Layoutregel.

| Nr. | Datei | Zeigt | Entscheidungen | Stand |
|---|---|---|---|---|
| 01 | `01-menue-drei-bloecke.html` | Übersicht 360, Schublade 360, Schreibtisch 1280 mit Leiste; BetreiberIn, aktiv „Konto-Backups" | E-S8-04, -07, -14, -02 | **freigegeben 05.09.2026** — mit Plakette „n offen" an „Updates" und Gruppenzahl am zugeklappten Block. **Nachtrag:** der Block Betrieb hat seit E-S8-05 sechs Einträge; die Leiste zeigt seit E-S8-15 Unterpunkte — beides in Mockup 03 |
| 02 | `02-serverbetrieb.html` | Sammelseite „Serverbetrieb" | — | **verworfen 05.09.2026** — Seite entfällt (E-S8-05); freigegeben daraus: Speicherbalken, `codeblock-lang` mit „Kopieren", Problemzeilen, zwei Spalten als Regel bei vielen Karten (E-S8-18) |
| 03 | `03-status.html` | Betrieb → Status mit Ampel (vier Karten); Leiste mit Unterpunkten der aktiven Seite, Fassung 2; 360 und 1280 | E-S8-15, -16 | **freigegeben 05.09.2026** (Fassung 2) |
| 04 | `04-statistik.html` | Betrieb → Statistik: Kennzahlen, Konten, Geräte, Einsätze, Gerätemodelle-Tabelle mit CSV; Zeitraum-Raster | E-S8-05 (Statistik), -17 | **Fassung 2 freigegeben 05.09.2026** (mit beiden Durchschnitten) |
| 05 | `05-updates.html` | Betrieb → Updates: Wartungsmodus mit Ablauf, Ausstehend (blockiert mit Kästchen, Backup-Meldung), Ausgeführt zugeklappt, Fassung; 360 und 1280 | E-S8-05, R66 | **freigegeben 05.09.2026** — Kartentitel „Ausstehende Updates", Ablauf-Kurzform bleibt |
| 06 | `06-hintergrundjobs.html` | Betrieb → Hintergrundjobs: Zustand (mit Fehlerzeile), Auslöser mit `codeblock-lang` und „Kopieren", Was hier gilt; 360 und 1280 | E-S8-05, -10 | **freigegeben 05.09.2026** |
| 07 | `07-servereinstellungen.html` | Betrieb → Servereinstellungen: Speicher mit zwei Balken (Backups gegen Grenze, Installation gesamt gegen Webspace), Grenze, Schwellen, Webspace-Angabe, Ablage, Reste, größte Tabellen; einspaltig auf Lesebreite | E-S8-06, -18 | **Fassung 2 freigegeben 05.09.2026** — ohne „Größte Tabellen"; versendete Pakete auf Backup-Zielen zählen nicht |
| 08 | `08-konto-backups.html` | Verwaltung → Konto-Backups (Kennzahlen mit Links, Alle-sichern-Stand, Regeln ohne Grenze, Backups ohne Konto zugeklappt, Was hier gilt) und die Kontoseite mit Karte „Konto-Backups" samt Freigabe-Zustandszeile; 360, 1280, Kontoseite 360 | E-S8-06, B-S8-07/-08/-09/-11/-19 | **freigegeben 05.09.2026** |
| 09 | `09-installation.html` | Verwaltung → Installation: Logo als Segment mit Vorschau, Impressum und Datenschutz mit „Ansehen ›", Speichern-Leiste; 360 und 1280 | E-S8-05, -12, B-S8-10 | **freigegeben 05.09.2026** |
| 10 | `10-geraete.html` | Einstellungen → Geräte: Koppeln, Liste mit ⋯-Menü, App installieren (Garmin, Play Store, APK zugeklappt), Gerät ohne Code zugeklappt; 360 und 1280 | E-S8-11, -10, B-S8-12/-21 | **freigegeben 05.09.2026** — „Weg"-Zeilen auf `ui_zeile()` zurückgeführt |
| 11 | `11-filterreihe.html` | NutzerInnen-Liste: heute (Filter brechen bei 780 px Inhalt) gegen neu (Suchfeld eigene Zeile, Filter darunter), dazu 360 px; mit den neuen Filternamen | E-S8-08 | **freigegeben 05.09.2026** |
| 12 | `12-bedienhoehe.html` | dieselben Bausteine bei 44 und 36 px nebeneinander: Leiste mit Unterpunkten, Suche und Filter, Formular mit Feldern, Schalter, Segment, Zeile, Knöpfen | E-S8-09 | **freigegeben 05.09.2026** |

## 7. Arbeitspakete (Schritt 4, bestätigt 05.09.2026)

Acht Pakete, jedes ein Zweig mit Push nach K7, jedes mit Abnahmekriterien,
Doku und Bilderlauf der berührten Seiten. Reihenfolge nach Abhängigkeit —
jede Seite entsteht, bevor das Menü auf sie zeigt. Die Umsetzung arbeitet
nach der Baustein-Bilanz 6.1, nicht nach dem Mockup-CSS. Versionsnummern
setzt die Umsetzung (K3); Hinweis: AP1 trägt eine Migration und ist nach der
Zählweise in `version.php` eine **Hauptversion** des Web, die übrigen Pakete
Nebenversionen. Dateinamen neuer Seiten sind Vorschläge — die Umsetzung
darf abweichen, muss es aber im Statusblock nennen.

**Berührung mit S9 (R73):** S9 darf parallel laufen, sobald dieses Konzept
gepusht ist. Berührungen: Stammdaten-Formulare (`stammdaten_ui.php`, S8
fasst sie nicht an), das Stylesheet (S8: Leiste, Listenkopf, Token
`--knopf`; S9: Tagesübersicht, Einsatzseite), die Bedienhöhe (AP7 vor S9
PS-3). Wer zuerst mergt, der andere zieht nach.

### AP1 — Rolle „BetreiberIn"

| | |
|---|---|
| Entscheidungen | E-S8-02 |
| Inhalt | (1) Migration: `users.role` von `ENUM('user','admin')` auf `ENUM('user','admin','betreiberin')`; danach `UPDATE users SET role='betreiberin' WHERE role='admin'`; idempotent (zweiter Lauf ändert nichts). (2) `auth_guard.php`: `ist_betreiberin()`, `require_betreiberin()`; `ist_admin()` liefert **auch für BetreiberInnen wahr** (Hierarchie 5.3). (3) Alle wörtlichen Vergleiche `=== 'admin'` außerhalb von `auth_guard.php` prüfen und auf die Wächterfunktionen umstellen — Fundstellen: `admin_user.php`, `admin_users.php`, `adminbackup_lib.php`, `login.php`, `rechtstext_seite.php`. (4) Rollenvergabe: Auswahl beim Anlegen (NL-05) und auf der Kontoseite (KS-01) zeigt „BetreiberIn" nur, wenn die Angemeldete BetreiberIn ist; serverseitig gleich geprüft. (5) Schutz: das letzte BetreiberIn-Konto kann weder zurückgestuft noch gelöscht werden — serverseitig verweigert, in der Oberfläche als abgeschaltete Option mit Kleintext. (6) `install.php`: erstes Konto wird BetreiberIn. (7) Demo-Fixture und Demo-Reset: Rolle `user`, prüfen, dass kein Skript Rollen zurücksetzt. (8) Anzeige der Rolle in Übersicht und Kontoseite („BetreiberIn"). (9) Backup-Formate: `role` steht im Komplett-Backup; ein alter Stand mit `admin` bleibt gültig und wird beim Einspielen auf neuem Schema nicht angehoben — Vermerk in `Backup-Format.md`; Konto-Backups (`.edbak`) tragen keine Rolle (prüfen). |
| Doku | Handbuch 11.3 „Rollen" (drei Rollen, Hierarchie, Vergabe, Schutz), 3.1 (Rollenanzeige), Wortliste „BetreiberIn"; `Technik.md` 7 (Ersteinrichtung); `CHANGELOG.md`. |
| Abnahme | Rechtematrix 5.3 durchgespielt mit drei Konten (Prüfprotokoll P-01); Migration auf einer Kopie der Produktivdaten: alle Admins → BetreiberInnen, sonst nichts geändert (P-02); letztes BetreiberIn-Konto geschützt (P-03); `install.php` legt BetreiberIn an (P-04); keine Fundstelle `=== 'admin'` außerhalb des Wächters (P-05); Demo-Reset lässt Rollen unberührt (P-06). |
| Berührung | P5/R38 (Support-Rolle kommt als vierter Wert hinzu), P6 Bedrohungsmodell (Rechteänderung). |

### AP2 — Betrieb, Teil 1: Updates, Hintergrundjobs, Servereinstellungen

| | |
|---|---|
| Entscheidungen | E-S8-05, -06 (Speicher), -10, -18; R66; Mockups 05, 06, 07 |
| Inhalt | (1) **Updates** (`betrieb_updates.php`, Wächter `require_betreiberin()`): Karte Wartungsmodus mit Ablauf-Kurzform, Aktionen `wartung_an`/`wartung_aus` und Balken aus `wartung_lib.php` (Paket W unverändert); Karte „Ausstehende Updates" aus dem Register: nur `steht aus`/`blockiert`/`Fehler`, Blockierte mit Kästchen und Grund, Meldung mit jüngstem Komplett-Backup und Alter (Link Komplett-Backup), Knopf „Ausstehende ausführen" (Aktion `migrate`), Leerzustand „Alles aktuell · NNN am Datum"; Karte „Ausgeführt" zugeklappt mit Vorschau (Zahl, letzte Nummer, Datum) — Liste bis P5; Karte „Fassung": `WEB_VERSION`, höchste ausgeführte Migration, Mindeststand Uhr-App (Quelle: Konstante in `ingest.php`/`api`, prüfen), Android-Stand (Quelle: APK-Metadaten, prüfen). (2) **Hintergrundjobs** (`betrieb_jobs.php`): Karte Zustand = heutige Jobs-Karte (Plaketten, Liegenbleiber, Pause-Hinweis) mit Fehlertext in der Kleinzeile; Karte Auslöser = heutige „Wann die Jobs laufen" mit Aktion `jobs_token_neu`; **Baustein `codeblock-lang`**: Zusatzklasse am `.codeblock` (`--schrift-fest`, `--groesse-2`, ohne Sperrung, `word-break`), leiser Knopf „Kopieren" mit `navigator.clipboard.writeText` und kurzer Rückmeldung („Kopiert"), Rückfall bei fehlender Berechtigung: Text markieren; „Was hier gilt" zugeklappt. (3) **Servereinstellungen** (`betrieb_server.php`): Karte Speicher mit **zwei Speicherbalken** (Baustein: `.speicher-balken` mit Segmenten, Legende, Schwellenstrich; Segment orange ab erster, rot ab zweiter Schwelle); Formular Speichergrenze Backups, Warnschwellen (bisherige Aktion `regeln` aus `admin_sicherungen.php`, die Schlüssel `grenze`/`schwellen` ziehen hierher, `tage`/`pakete`/`mail` bleiben dort), neues optionales Feld **Webspace laut Hosting** (`app_state` `webspace_gb`); Zeilen Ablage (Pfad, beschreibbar) und Reste; **Messung** im täglichen Aufräumjob: Datenbankgröße aus `information_schema.TABLES` (`data_length + index_length` je Tabelle des Schemas), Dateien per `RecursiveDirectoryIterator` über das Anwendungsverzeichnis **ohne** `sicherungen/`, Ergebnis mit Zeitstempel in `app_state` (`speicher_db_bytes`, `speicher_dateien_bytes`, `speicher_stand`); Warnschwellen gelten für beide Balken, Meldung wie heute einmal je Schwelle; „Was hier gilt". (4) `update.php` behält **übergangsweise nur die Logo-Karte** und einen Hinweis auf die drei neuen Seiten (die Seiten sind bis AP5 nur per Adresse erreichbar). (5) `admin_sicherungen.php`: Karten Ablage und die Felder Grenze/Schwellen entfallen, Kleintext verweist auf Servereinstellungen (der Rest folgt in AP3). |
| Doku | Handbuch 11.3: Abschnitte Updates, Hintergrundjobs, Servereinstellungen (ersetzen „Wartung"); 6.1 (Grenze umgezogen); `Technik.md` 7 (Runbook: Updates-Seite, Ablauf); `Design.md` 9: `codeblock-lang`, Speicherbalken; `CHANGELOG.md`. |
| Abnahme | Wartungsmodus von Updates aus ein- und ausschaltbar, Balken auf allen Seiten (P-07); Migrationslauf mit ausstehender, blockierter und fehlgeschlagener Migration (P-08); Token neu erzeugen, Kopieren-Knopf in zwei Browsern (P-09); Speichermessung gegen `du -sb` und eine SQL-Summe, Abweichung < 2 % (P-10); Schwellenfärbung bei künstlich gesetzten Werten (P-11); bestehende Regelwerte nach dem Umzug unverändert (P-12); `update.php` ohne alte Aktionen außer `logo_standard` (P-13); Bilderlauf drei Seiten (P-30). |
| Berührung | Paket W (`wartung_lib.php` unverändert), R66, Nr. 77, Nr. 78 (falls vorab in der Backlog-Runde umgesetzt: den dortigen Stand übernehmen). |

### AP3 — Verwaltung: Installation, Konto-Backups, Kontoseite

| | |
|---|---|
| Entscheidungen | E-S8-05 (Installation), -06, -12, -13; Mockups 08, 09 |
| Inhalt | (1) **Installation** (`admin_installation.php`, aus `admin_rechtstexte.php`; alte Adresse leitet weiter): Karte Logo (Aktion `logo_standard` aus `update.php`, Segment, Vorschau mit `ui_logo()`), Karten Impressum und Datenschutz wie heute mit „Ansehen ›" im Kartenkopf (öffentliche Seite), Speichern-Leiste nennt, was ungespeichert ist. (2) `update.php` wird **Weiterleitung** (302) auf Updates; bleibt bis P6 (Nr. 77). (3) **Konto-Backups** (`admin_sicherungen.php` bleibt, R56): Seitentitel und Untertitel; Kennzahlen Pakete · Größe, Konten, „Konto-Backup überfällig" und „nie Konto-Backup" als Links auf die Filter der NutzerInnen-Liste; Titelaktion „Alle sichern", Auftragsstand als Meldung; Karte Regeln (Erinnerung nach, Aufbewahrung je Konto, Admin-Mail) mit Kleintext zu Servereinstellungen; Karte „Backups ohne Konto" zugeklappt mit Vorschau, Einspielen und Freigeben als leise Knöpfe, Löschen und „Ganzen Ordner löschen" im ⋯-Menü; „Was hier gilt" (drei Backups, Freigabe, Schlüssel). (4) **Kontoseite** (`admin_user.php`): Karte „Konto-Backups" mit Überfälligkeits-Plakette und „Jetzt sichern" als Kartenaktion; **Freigabe-Zustandszeile** als blaue Meldung (für wen, seit wann, welches Paket, was die NutzerIn tut, „Widerrufen"); Karte „Abonnement · ab P5" **entfällt**; Geräte: „Entkoppeln" bleibt. (5) **NutzerInnen-Liste**: Kennzahlen, Filter und Spalte in der Wortliste (Konto-Backup überfällig, Nie Konto-Backup, Konto-Backup). (6) **Wortliste** überall: „Konto-Backup" für (b), „einspielen" für jeden Rückweg ins Konto, „wiederherstellen" nur Installation; die NutzerInnen-Seite Backup bleibt bis auf den Hinweis in „Für dich freigegebenes Backup" („ein Konto-Backup der Verwaltung"). |
| Doku | Handbuch 6, 6.1, 11.1, 11.2 (Wortliste, Orte), 11.3 (Installation statt Rechtstexte), `Backup-Format.md` (Begriffe); `CHANGELOG.md`. |
| Abnahme | Alle Backup-Wege verhaltensgleich: Jetzt sichern, Alle sichern, Einspielen, Freigeben, Widerrufen, Löschen, ohne Konto (P-14, nach dem Prüfdokument S7 Paket E); Logo von Installation aus änderbar, Anmeldeseite folgt (P-15); Weiterleitungen `update.php`, `admin_rechtstexte.php` (P-16); Freigabe-Zustandszeile erscheint und verschwindet mit Widerruf (P-17); kein Platzhalter „Abonnement" sichtbar (P-18); Wortliste per Suche: kein „Sicherung" als Substantiv, kein „Admin-Backup", kein „Datenbank-Update" (P-19); Bilderlauf drei Seiten (P-30). |
| Berührung | S7 (Begriffe, R56), P5 (Installation als Ort für R31, R32, R37, Banner). |

### AP4 — Betrieb, Teil 2: Status, Statistik

| | |
|---|---|
| Entscheidungen | E-S8-16, -17, -05 (Statistik); Mockups 03, 04 |
| Inhalt | (1) **Status** (`betrieb_status.php`), rein lesend, „Aktualisieren" in der Titelzeile; Meldung oben (Zahl der Achtung- und Warnpunkte; blau „Alles läuft", wenn keine); vier Karten mit Zeilen, jede Zeile Plakette nach **Ampeltabelle** (unten) und ganze Zeile als Link auf die zuständige Seite: **Server** — Serverbetrieb (Wartungsmodus), Updates (ausstehend), Serverschlüssel, Schlüsselableitung (verwaiste Rundenzahl), Datenbank (erreichbar, Größe aus `app_state`), PHP · Zeitzone · **Hintergrundjobs** — Auslöser (Weg, letzter Lauf), je Job · **E-Mail** — SMTP, letzte Zustellung (**zu prüfen**, ob aufgezeichnet; sonst nur „eingerichtet"), Versand entkoppelt · **Backups** — Komplett-Backup (jüngster Stand gegen Plan), Konto-Backups (überfällig, nie), Backup-Ziele, Speicher (%), Ablage. Problemfälle, die heute Karten waren (Serverschlüssel fehlt mit Aktion, Schlüsselableitung), werden rote Zeilen mit Link; die Aktion „Serverschlüssel erzeugen und eintragen" bleibt als Knopf in der Zeile (einzige Handlung der Seite). (2) **Statistik** (`betrieb_statistik.php`), rein lesend, alles **ohne Demo-Konto**: Kennzahlen (Konten, Geräte, Einsätze gesamt, Einsätze in 30 Tagen); Karte Konten (nach Rolle mit Anteil, ohne Gerät; Tabelle 7/30/180 Tage: zuletzt angemeldet aus `users.last_login`, neu angelegt aus `created_at`); Karte Geräte (Garmin-Uhren, Wear-OS-Uhren, Android-Handys, deaktiviert; Tabelle: zuletzt gemeldet aus `last_seen`, gekoppelt aus `created_at`); Karte Einsätze (Tabelle: Einsätze, NutzerInnen mit Einsatz, Ø je aktiver NutzerIn, Ø je NutzerIn gesamt — nach Diensttag gezählt, 6 Monate = 180 Tage); Karte **Gerätemodelle**: Tabelle mit Spalten Gerät, Hersteller, Art, Geräte, Anteil %, NutzerInnen, Anteil %; Gruppierung nach `geraet_modell`, Rückfall `geraet_teil`; **Hersteller abgeleitet**: Teilenummer vorhanden → „Garmin", sonst erstes Wort von `geraet_teil`; **Art**: `geraet_art` plus Teilenummer → Uhr / Uhr (Wear OS) / Handy (prüfen, was die Wear-OS-App als `art` sendet); Sortierung über `?sort=`/`?richtung=` wie die NutzerInnen-Liste, Vorgabe Anteil absteigend; **„Als CSV"** (`?export=csv`): dieselben sieben Spalten, Semikolon, UTF-8 mit BOM, `Content-Disposition` mit Datum im Namen; `.tabelle-scroll` am Handy. (3) Beide Seiten bis AP5 nur per Adresse erreichbar. |
| Ampeltabelle | **blau**: Wartungsmodus aus · keine ausstehende Migration · Schlüssel vorhanden · keine verwaiste Rundenzahl · Datenbank erreichbar · Auslöser Cron/Adresse mit Lauf < 24 h · Job ohne Fehler und ohne Rückstand · SMTP eingerichtet · Komplett-Stand jünger als der Plan · keine Konto-Backups überfällig · Speicher < erste Schwelle · Ablage beschreibbar. **orange**: Wartungsmodus an · Migration ausstehend · Job mit Rückstand · Auslöser Huckepack (kein Cron) · Komplett-Stand älter als der Plan · Konto-Backups überfällig oder nie · Speicher ≥ erste Schwelle · Backup-Ziel aktiv, aber nie versendet. **rot**: Serverschlüssel fehlt · verwaiste Rundenzahl · Datenbank nicht erreichbar · kein Job-Lauf seit > 24 h · Job mit Fehler · SMTP-Fehler beim letzten Versand · Komplett-Backup nie bei Plan ≠ aus · Speicher ≥ zweite Schwelle · Ablage nicht beschreibbar. **neutral**: nicht eingerichtet, reine Zahl. |
| Doku | Handbuch 11.3: Status, Statistik; `Design.md` 9: Ampel-Bedeutung der Plakettentöne (keine neuen Töne); `CHANGELOG.md`. |
| Abnahme | Jede Ampelzeile mit erzwungenem Zustand geprüft (Wartung an, Job-Fehler, Ablage schreibgeschützt, Schlüssel entfernt, Plan verletzt) (P-20); Statistikzahlen gegen Hand-SQL (P-21); CSV öffnet in Excel mit Umlauten, Sortierung stabil, Hersteller-Ableitung an allen vorhandenen Geräten plausibel (P-22); Demo-Konto in keiner Zahl (P-23); Seitenaufbau Status < 300 ms, Statistik < 500 ms auf Produktivdaten (P-24); Bilderlauf zwei Seiten (P-30). |
| Berührung | P5 (Dashboard und Geräteauswertung nach R64 auf Statistik; Health und Fehlerprotokoll auf Status), Backlog 80 (Teilung, Abschnitt 9). |

### AP5 — Menü und Leiste

| | |
|---|---|
| Entscheidungen | E-S8-01 Regel 5, -04, -07, -14, -15; Mockup 01, 03 (Leiste) |
| Inhalt | (1) `ui.php`: **eine Quelle** `ui_einstellungen_punkte(string $rolle): array` (Block, Text, Ziel, Symbol, Zähler) für `ui_leiste_einstellungen()` und `ui_einstellungen_uebersicht()` (löst B-S8-01); drei Blöcke nach 5.1, Block nach Rolle (Verwaltung ab Admin, Betrieb nur BetreiberIn); Blockname **„Verwaltung"**; Stammdaten systemweit ohne Eintrag. (2) Leiste: Blöcke als **Akkordeon** (`details` + `.akkordeon-zeile`/`-winkel`/`-inhalt`), Zustand je Sitzung in `sessionStorage`; Vorgabe: Block der aktiven Seite offen, Einstellungen immer, ab 1024 px alle; zugeklappter Block zeigt die Zahl der Einträge im `.akkordeon-text`. (3) **Zähler** (`.zaehler`) an Status (Achtung + Warnung, orange/rot nach höchster Stufe), Updates (ausstehend, neutral), Hintergrundjobs (Fehler, rot), Konto-Backups (überfällig + nie, orange) — Werte aus einer leichten Abfrage je Seitenaufruf mit 60-s-Zwischenspeicher in `app_state` oder Sitzung; Messung, dass kein Seitenaufbau messbar langsamer wird. (4) **Unterpunkte** (Baustein `.eintrag-unter`): Seiten melden ihre Karten über `ui_karte_start(['anker' => 'id'])`; die Leiste rendert unter dem aktiven Eintrag die Titel als Sprungmarken (28 px, `--groesse-2`, ohne Strich, ohne Symbol); Skript mit `IntersectionObserver`: **je Spalte die oberste sichtbare Karte fett**; in der Schublade ohne Markierung; Anker mit `scroll-margin-top` unter der Kopfleiste. (5) Nr. 75: Fettdruck nur für den aktiven Eintrag; Stylesheet `.leiste-liste` auf Reste aus S3 Block F prüfen. (6) Übersichtsseite: erster Block ohne Überschrift, „Verwaltung" und „Betrieb" mit `.uebersicht-block`; am Schreibtisch drei Spalten. (7) **Regel 5 auf allen Seiten** der drei Blöcke geprüft: je Seite höchstens eine Karte „Was hier gilt", zugeklappt, am Ende — die heutigen „Was der Reset umfasst", „Bericht des letzten Laufs" (Demo) werden zu einer Karte. (8) Zweispaltenregel `.karten-raster` (ab 1200 px, Seiten mit mehr als vier Karten) im Stylesheet, angewandt auf Status, Statistik, Updates, Hintergrundjobs, Konto-Backups, Installation, Geräte. (9) **Symbole des Menüs** — bestätigt am 05.09.2026 auf Rückfrage aus AP3: Mockup 01 zeichnet für „Updates" einen Kreispfeil und für „Installation" ein Haus; beide gibt es im Vorrat nicht. AP3 hat deshalb aus dem Vorrat geliehen (`werkzeug` für Updates, `kalender` für Hintergrundjobs, `datenbank` für Servereinstellungen, `rechtstexte` für Installation) und die Frage hierher verschoben. **Neue Symbole sind ausdrücklich erwünscht** — sie entstehen in AP5 nach dem Weg aus `docs/Design.md` Kapitel 9: Entwurf als Mockup, Freigabe, dann eine SVG-Datei je Zeichen unter `server/assets/images/symbole/` mit Eintrag in deren `LIESMICH.md` und in `docs/Lizenzen.md` (Herkunft Tabler Icons, MIT — oder eigener Entwurf mit Vermerk). |
| Doku | Handbuch 3 (Menü), 11 (Verwaltung, Betrieb), 9.4 (Vermerk ohne Menüpunkt); `Design.md` 9: Unterpunkte, Zweispaltenregel, Akkordeon in der Einstellungsleiste; `CHANGELOG.md`. |
| Abnahme | Sichtbarkeit je Rolle mit drei Konten: NutzerIn ein Block ohne Überschrift, Admin zwei, BetreiberIn drei (P-25); Zähler stimmen mit den Seiten überein (P-26); Unterpunkte springen und markieren, auch zweispaltig (P-27); Akkordeonzustand überlebt den Seitenwechsel, nicht die Sitzung (P-28); Seitenaufbau nicht messbar langsamer (P-24); kein Fettdruck außer aktiv (P-29); Bilderlauf Leiste und Übersicht (P-30). |
| Berührung | S3 Block F (Leiste), P5 (Listen bekommen Zähler nach derselben Regel). |

### AP6 — Einstellungen: Geräte, Wertekasten, Filterreihe

| | |
|---|---|
| Entscheidungen | E-S8-08, -10, -11; Mockups 10, 11 |
| Inhalt | (1) **Geräte** (`einstellungen.php?t=geraete`): Reihenfolge Koppeln → Geräte → App installieren → Gerät ohne Code; Seitenerklärung mit Belegung und Grenze; Liste als `ui_zeile()` mit Bezeichnung, Plaketten „neu"/„deaktiviert", Kleinzeile (Modell, Art, gekoppelt seit, zuletzt gemeldet), alle Handlungen im ⋯-Menü (`ui_zeilenaktionen()`): Bezeichnung ändern, Deaktivieren/Aktivieren, **Entkoppeln** (statt Löschen; dieselbe Rückfrage); Karte **App installieren** als zwei Zeilen: Garmin-Uhr (Knopf „Connect IQ", Ziel Konstante `CONNECT_IQ_URL` — nur, wenn die Uhr-App dort steht, sonst Text ohne Knopf), Android-Handy oder Wear-OS-Uhr (Knopf „Play Store", Ziel Konstante `PLAY_TEST_URL` aus der Play-Console-Zuarbeit; mit der Produktionsfreigabe später die Store-Adresse); APK zugeklappt („Ohne Play Store: APK von Hand") mit Fassung, Größe, Stand, SHA-256 aus `apk.php`; Karte **Gerät ohne Code anlegen** zugeklappt, danach Zugangsdaten-Karte mit Geräte-ID und API-Schlüssel im `codeblock-lang` mit „Kopieren". (2) **Wertekasten-Stufe** an den restlichen Stellen: Setz-Link (Rückfall in `admin_users.php`, Kontoseite), Serverschlüssel-Zeile (jetzt Status). (3) **Filterreihe** (Nr. 73): `.listenkopf` bleibt ab 1024 px Spalte, `.listensuche` Höchstbreite 36 rem, `.filterreihe` mit Umbruch — löst Mockup 41 aus P3 ab; gilt für alle künftigen Listen mit Suche und Filtern. |
| Doku | Handbuch 10, 10.1 (App installieren, Store zuerst, APK als Rückfall), 12 (Gerät ohne Code), 11.2 (Filter); `Design.md` 9 (Wertekasten-Stufe vollständig, Listenkopf); `CHANGELOG.md`. |
| Abnahme | Kopplung in allen drei Zuständen verhaltensgleich (P-31, nach Prüfdokument S5); APK weiterhin ladbar mit passender Prüfsumme (P-32); ⋯-Handlungen und Entkoppeln (P-33); Zugangsdaten kopierbar (P-09); Filterreihe bricht bei 780 px Inhaltsbreite nicht (P-34); Bilderlauf Geräte und NutzerInnen (P-30). |
| Berührung | R65 (Store-Weg; Link aus der Zuarbeit in Rahmenplan 6), S5 (Kopplung unverändert), Nr. 78. |

### AP7 — Bedienhöhe

| | |
|---|---|
| Entscheidungen | E-S8-09; Mockup 12 |
| Inhalt | (1) `style.css`: `@media (hover: hover) and (pointer: fine) and (min-width: 1024px) { :root { --knopf: 36px } }`; Durchsicht aller Stellen, die `--knopf` benutzen, auf ungewollte Folgen (Kopplungscode-Feld, Segment, Filter, Übersichtszeile, Listenformular-Fuß, Kartenaktion, Menüeintrag, Unterpunkte bleiben 28 px); unverändert: Kopfleiste (`--kopf`), Schalter (`--schalter-*`), Aktionsblatt (`--blatt-zeile`, gilt nur mobil). (2) `Design.md` 6 und 7: zwei Stufen mit Begründung (Formulararbeit am Schreibtisch, WCAG 2.5.8, Touch-Laptops); `CLAUDE.md` 5: „44 px, am Zeigergerät ab 1024 px 36 px". (3) `tools/screenshots/`: Messung mit zwei Sollwerten; Zeigergerät-Emulation (`hasTouch: false`, Medien-Emulation `pointer: fine`, `hover: hover`) für die Breiten ab 1024. (4) Bilderlauf in acht Breiten, ab 1024 zusätzlich mit Zeigergerät. |
| Doku | `Design.md`, `CLAUDE.md`, `tools/screenshots/LIESMICH.md`; `CHANGELOG.md`. |
| Abnahme | Gemessen 44 px unter 1024 und bei Touch, 36 px ab 1024 am Zeigergerät (P-35); kein Ziel unter 24 × 24 px (P-36); Fokusringe vollständig, keine Überlappungen bei 1024, 1200, 1440, 1920 (P-37); Kopplungscode lesbar (P-38). |
| Berührung | S9 PS-3 (baut darauf auf), alle Seiten (nur Höhe). |

### AP8 — Abschluss: Doku, Rahmenplan, Backlog

| | |
|---|---|
| Entscheidungen | alle; Abnahme aus 1.1 |
| Inhalt | (1) **Handbuch** neu gegliedert: Kapitel 6 mit den drei Backup-Begriffen (Backup, Konto-Backup, Komplett-Backup) und den Orten; Kapitel 11 „Verwaltung (Admin)" und neues Kapitel „Betrieb (BetreiberIn)" mit Status, Statistik, Updates, Hintergrundjobs, Servereinstellungen, Komplett-Backup, Backup-Ziele; „Wartung" als Seitenname getilgt; 9.4 mit Vermerk; Gliederung als Vorlage für P7 (R72). (2) `Technik.md` 7 Runbook: Ablauf über Updates, Status als Prüfstelle nach dem Deploy. (3) `Design.md`: Bausteine (Unterpunkte, Speicherbalken, Wertekasten-Stufe), Layoutregel, Bedienhöhe, Ampel-Bedeutung, „Wenn du X willst, nimm Y" ergänzt. (4) **Wortliste**: Backup-Begriffe, BetreiberIn, Verwaltung, Betrieb, Entkoppeln, Ausstehende Updates. (5) **Stilvergleich** mit Soll-Ist-Liste: elf Mockups gegen die Bilder in acht Breiten; Abweichungen begründet oder behoben. (6) **Vollständigkeit**: jeder Eintrag aus 2.3 hat einen Ort im Zielbild oder ist ausdrücklich entfallen (W-08, KS-09, AB-08 → Servereinstellungen, Erklärkarten). (7) Konzept- und Prüfdokument fortgeschrieben; nach Freigabe des Abschlusses: Rahmenplan- und Backlog-Einfügeblöcke aus Abschnitt 9.2, Konzept nach `docs/konzepte/erledigt/` (R62). |
| Abnahme | Handbuch ohne Verweis auf „Wartung" als Seite, ohne „Administration" als Blockname (P-39); Vollständigkeitstabelle 2.3 → Zielbild ohne Lücke (P-40); Stilvergleich ohne unbegründete Abweichung (P-41); Bilderlauf vollständig (P-30); `CHANGELOG.md` und Versionen konsistent (P-42). |

---

## 8. Prüfprotokoll-Soll (K9)

Das Prüfdokument `Pruefdokument-S8-Einstellungen-Administration-Wartung.md`
führt diese Punkte mit Stand, Prüfweg und Ergebnis. Nicht aus dem Container
prüfbar (2.9): alles, was Bilder, gemessene Breiten oder Produktivdaten
braucht — die Umsetzung prüft es in der Claude-Code-Umgebung mit laufender
Anwendung; die `00-ist-*`-Bilder entstehen vor AP1.

| Nr. | Was | Wie | AP |
|---|---|---|---|
| P-01 | Rechtematrix 5.3 | drei Konten (NutzerIn, Admin, BetreiberIn), jede Seite je Rolle aufrufen, 403 dokumentiert | 1 |
| P-02 | Migration auf Kopie der Produktivdaten | Dump einspielen, Migration laufen lassen, Rollen vorher/nachher vergleichen, zweiter Lauf ohne Änderung | 1 |
| P-03 | Schutz des letzten BetreiberIn-Kontos | Rückstufung und Löschung versuchen (Oberfläche und direkter POST) | 1 |
| P-04 | `install.php` legt BetreiberIn an | Ersteinrichtung auf leerer Datenbank | 1 |
| P-05 | keine wörtliche Rollenprüfung außerhalb des Wächters | `grep -rn "'admin'" server/` ohne Treffer in Vergleichen | 1 |
| P-06 | Demo-Reset lässt Rollen unberührt | Reset auslösen, `users.role` vergleichen | 1 |
| P-07 | Wartungsmodus von Updates aus | ein, Seiten fremd aufrufen (503 + Retry-After), aus | 2 |
| P-08 | Migrationslauf mit Ausstehend, Blockiert, Fehler | Test-Migrationen im Register anlegen, Lauf mit und ohne Häkchen | 2 |
| P-09 | Kopieren-Knopf | zwei Browser, mit und ohne Clipboard-Berechtigung, an allen fünf Stellen | 2, 6 |
| P-10 | Speichermessung | `du -sb` ohne `sicherungen/` und SQL-Summe gegen die Anzeige, Abweichung < 2 % | 2 |
| P-11 | Schwellenfärbung | `app_state` mit Werten über 70 und 90 % setzen, Balken und Status prüfen | 2, 4 |
| P-12 | Regelwerte nach Umzug unverändert | `app_state` vorher/nachher | 2 |
| P-13 | `update.php` übergangsweise nur Logo, danach Weiterleitung | Aufruf mit alten Aktionen → keine Wirkung; nach AP3 302 | 2, 3 |
| P-14 | Backup-Wege verhaltensgleich | Prüfdokument S7 Paket E wiederholen: Jetzt sichern, Alle sichern, Einspielen, Freigeben, Widerrufen, Löschen, ohne Konto | 3 |
| P-15 | Logo von Installation aus | ändern, Kopfleiste, Browser-Symbol, Anmeldeseite prüfen | 3 |
| P-16 | Weiterleitungen | `update.php`, `admin_rechtstexte.php` mit und ohne Parameter | 3 |
| P-17 | Freigabe-Zustandszeile | freigeben, widerrufen, NutzerInnen-Seite parallel | 3 |
| P-18 | kein Platzhalter sichtbar | Kontoseite ohne „Abonnement" | 3 |
| P-19 | Wortliste | Suche in `server/` und `docs/` nach „Sicherung" (Substantiv), „Admin-Backup", „Datenbank-Update", „Wartung" (Seite), „Administration" (Block) | 3, 8 |
| P-20 | Ampel je Zeile | jeden Zustand erzwingen, Plakette und Meldungszahl prüfen | 4 |
| P-21 | Statistikzahlen | Hand-SQL je Kennzahl, Demo ausgeschlossen | 4 |
| P-22 | Gerätemodelle-Tabelle | Sortierung je Spalte beide Richtungen, CSV in Excel, Hersteller-Ableitung je Gerät | 4 |
| P-23 | Demo-Konto in keiner Zahl | Demo anlegen, Zahlen unverändert | 4 |
| P-24 | Seitenaufbau | Status < 300 ms, Statistik < 500 ms, Zähler-Abfrage < 20 ms auf Produktivdaten | 4, 5 |
| P-25 | Menü je Rolle | drei Konten, Leiste und Übersicht | 5 |
| P-26 | Zähler | Werte gegen die Seiten | 5 |
| P-27 | Unterpunkte | Sprung, Markierung ein- und zweispaltig, Schublade ohne Markierung | 5 |
| P-28 | Akkordeonzustand | Seitenwechsel, neue Sitzung | 5 |
| P-29 | Fettdruck nur aktiv | Leiste in beiden Breiten | 5 |
| P-30 | Bilderlauf | acht Breiten je berührter Seite, ab AP7 zusätzlich Zeigergerät | alle |
| P-31 | Kopplung verhaltensgleich | Prüfdokument S5 wiederholen | 6 |
| P-32 | APK | Download, SHA-256 gegen Anzeige | 6 |
| P-33 | Geräte-Handlungen im ⋯-Menü | umbenennen, deaktivieren, aktivieren, entkoppeln | 6 |
| P-34 | Filterreihe | 780 px Inhaltsbreite eine Zeile; 360 px Umbruch gleichmäßig | 6 |
| P-35 | Bedienhöhe gemessen | Messwerkzeug: 44 unter 1024 und Touch, 36 ab 1024 Zeigergerät | 7 |
| P-36 | Mindestzielgröße | kein Ziel unter 24 × 24 px | 7 |
| P-37 | Fokus und Überlappung | Tastaturlauf bei 1024, 1200, 1440, 1920 | 7 |
| P-38 | Kopplungscode lesbar | Feld bei 36 px | 7 |
| P-39 | Handbuch-Begriffe | Suche nach „Wartung", „Administration" | 8 |
| P-40 | Vollständigkeit | Tabelle 2.3 gegen Zielbild, jeder Eintrag verortet oder entfallen | 8 |
| P-41 | Stilvergleich | Soll-Ist-Liste elf Mockups | 8 |
| P-42 | Versionen und Changelog | `version.php`, `CHANGELOG.md`, Handbuch-Kopf konsistent | 8 |

---

## 9. Wirkung auf Rahmenplan und Backlog

Alle Blöcke werden **gegen den bei Push aktuellen Stand von `main`**
geschrieben (Zusage 05.09.2026); Fassungsnummer, Registernummern und
Backlog-Nummern setzt die Instanz, die pusht.

### 9.1 Beim Push dieses Konzepts (Schritt 5)

**Rahmenplan, Schritt 7 (S8):** Zeile „Konzept: liegt vor (Fable,
05.09.2026), `docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md`,
Mockups 01, 03–12 freigegeben". Umfang ergänzt: „(6) Rolle BetreiberIn
(E-S8-02) · (7) Seiten Status und Statistik im Block Betrieb (E-S8-16,
E-S8-05) · (8) Ordnungsprinzip als Programmregel". „Nicht Umfang" angepasst:
„neue Verwaltungsfunktionen und Rollen (P5, R38) — **außer** der Rolle
BetreiberIn". Acht Pakete nach Abschnitt 7.

**Register (Abschnitt 7), neue Einträge:** (a) **Ordnungsprinzip**
(E-S8-01, sieben Regeln; K1 wird ergänzt: „Ein Konzept benennt für jede neue
Funktion ihren Ort nach dem Ordnungsprinzip; ohne Ort kein Merge"); (b)
**Rolle BetreiberIn** (E-S8-02, Rechte, Bestand, Vergabe; R38 behält
Support-Rolle, TOTP, Audit; Dashboard und Geräteauswertung auf Statistik,
Health und Fehlerprotokoll auf Status); (c) **Bedienhöhe in zwei Stufen**
(E-S8-09, 44/36); (d) **Drei Backup-Begriffe** (E-S8-06: Backup,
Konto-Backup, Komplett-Backup; „einspielen" gegen „wiederherstellen").

**Abschnitt 5 (Backlog-Zuordnung):** Nr. 80 geteilt — „Gerätemodelle und
Nutzung: S8 (Statistik); Herkunft je Einsatz (R64-Werte): P5 mit
Datenschutz-Vorbedingung". Nr. 73, 74, 75, 77, 78, 79: „S8, Konzept liegt
vor".

**Abschnitt 6 (Zuarbeit):** neue Zeilen „Play-Store-Beitrittslink des
internen Tests (für Geräte → App installieren, AP6)" und „Adresse der
Uhr-App im Connect-IQ-Store, falls veröffentlicht (AP6)"; Zeile
„Mockup-Freigaben S8" auf erledigt.

**Schritt 8 (S9):** Vermerk „Vorgabe aus S8 5.7 für die Tagesübersicht;
Bedienhöhe entschieden (44/36), Umsetzung in S8 AP7 vor S9 PS-3".

**Schritt 10 (P5):** Orte aus E-S8-12 als Vorgabe eintragen (Installation,
Kontoseite, Profil, Status, Statistik, Updates, Servereinstellungen).

**Backlog:** 73, 74, 75, 77, 78, 79 mit Vermerk „Konzept S8, E-S8-08/-09/-07/
-05/-10/-06"; 80 geteilt; neue Kandidaten als Einträge: „Nutzer-Backups
beobachten" (B-S8-07), „Hintergrundjobs in der Oberfläche anhalten"
(B-S8-16), „Sammelpunkt Import/Export" (B-S8-18, mit S9 klären), „Testmail
senden" (E-S8-16), „Markdown-Vorschau für Rechtstexte" (Mockup 09), „Zeitraum
frei wählen und Diagramme in der Statistik" (Mockup 04).

### 9.2 Beim Abschluss (nach AP8, Freigabe des Auftraggebers)

**Rahmenplan Abschnitt 8:** Erledigt-Zeile „Schritt 7 · S8 · Web x.y.z ·
Datum · acht Pakete · Rolle BetreiberIn, Block Betrieb mit sieben Seiten,
Konto-Backup-Begriffe, Bedienhöhe zwei Stufen, Ordnungsprinzip". **Abschnitt
4 (Sperren):** Zeilen mit S8 entfallen. **Abschnitt 6:** Reste aus dem
Prüfdokument (etwa: Uhr-App im Connect-IQ-Store, letzte Mailzustellung nicht
aufgezeichnet). **Kopf:** Versionen. **Backlog:** 73, 74, 75, 77, 78, 79
erledigt mit Web-Version; Kandidaten aus 9.1 bleiben offen. **Konzept** nach
`docs/konzepte/erledigt/` mit Prüfdokument (R62).

---

## 10. Schritt 5 — Push (Instanz mit Repositoriumszugriff)

1. Diese Datei nach `docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md`,
   das Prüfdokument daneben, die zwölf HTML-Mockups nach
   `docs/konzepte/konzept-s8/mockups/` (PNG-Fassungen entstehen später mit den
   Soll-Bildern).
2. Rahmenplan und Backlog nach 9.1 gegen den aktuellen Stand von `main`
   ändern; Fassung und Nummern vergeben; Änderungsverlauf.
3. Zweig, Pull Request, Merge nach Freigabe.
4. Danach AP1 in einer Umsetzungs-Instanz (Opus, K2) mit
   `00-ist-*`-Bildern vor der ersten Änderung.

**Erledigt am 05.09.2026** — Ergebnis in Abschnitt 11.1. Abweichung von
Punkt 3: Es wird **ein** Arbeitszweig (`claude/umsetzung-buuvfq`) für
Konzept-Push *und* Umsetzung geführt, nicht zwei; der Push je Arbeitspaket
bleibt (K7), der Merge auf `main` kommt einmal am Ende nach ausdrücklicher
Bestätigung (`CLAUDE.md` 3 und 8).

---

## 11. Umsetzungsprotokoll

Wird nach jedem Arbeitspaket fortgeschrieben (K5, R62): was ist gebaut,
welche Entscheidungen sind dabei gefallen, welche Probleme sind aufgetreten
und wie wurden sie gelöst. Die Prüfzahlen stehen im Prüfdokument.

### 11.1 Schritt 5 — Push (05.09.2026)

**Gebaut:** keine Codeänderung — nur Dokumentation, deshalb **keine
Versionserhöhung** (`CLAUDE.md` 2: eine Änderung, die nur `docs/` anfasst,
stuft keine der drei Zählungen hoch) und kein Changelog-Eintrag.

| Was | Wo |
|---|---|
| Konzept und Prüfdokument | `docs/konzepte/` |
| Zwölf Mockups | `docs/konzepte/konzept-s8/mockups/` |
| Rahmenplan **Fassung 28** | Kopf, Fahrplan (Schritt 7), K1 (Ordnungsprinzip), Abschnitt 4 (Sperre erfüllt), Abschnitt 5 (73–80), Abschnitt 6 (zwei Zuarbeiten neu, Konzeptfreigabe erledigt), Abschnitt 7 (**R74–R77**), Schritt 7 (Umfang 6–8, acht Pakete), Schritt 8 (Vorgabe Tagesübersicht), Schritt 10 (Ortstabelle aus E-S8-12), Abschnitt 10 |
| Backlog | 73, 74, 75, 77, 78, 79 mit dem Entscheidungsvermerk; 80 geteilt; **117–122** neu angelegt |

**Entscheidungen dabei:**

- **Nummernvergabe.** Die vier Programmentscheidungen bekommen **R74**
  (Ordnungsprinzip), **R75** (Rolle BetreiberIn), **R76** (Bedienhöhe zwei
  Stufen) und **R77** (drei Backup-Begriffe) — der Rahmenplan stand bei R73.
  Die sechs Backlog-Kandidaten bekommen **117–122**; 114–116 waren aus S5
  Paket E bereits vergeben. Gegenprobe auf doppelte Nummern: leer.
- **Ein Zweig statt zwei** (siehe oben, Abschnitt 10).

**Probleme:** keine.

### 11.2 AP1 — Rolle „BetreiberIn" (05.09.2026, Web 15.0.0)

**Version:** Web **15.0.0** — Hauptnummer nach der Zählweise in `version.php`.
Nicht wegen der Spalte (ein ENUM um einen Wert zu erweitern ist wenig),
sondern weil sich die Wege durch die Anwendung ändern, und zwar je nachdem,
wer angemeldet ist. Dieselbe Begründung wie bei 7.0.0.

**Gebaut:**

| Was | Wo |
|---|---|
| Rollenkatalog und reine Prädikate (`ROLLEN`, `rolle_normieren()`, `rolle_darf_verwalten()`, `rolle_ist_betreiberin()`, `rolle_text()`, `ROLLEN_VERWALTUNG_SQL`, `betreiberinnen_zahl()`, `ist_letzte_betreiberin()`) | `server/db.php` |
| Wächter der Sitzung (`ist_betreiberin()`, `require_betreiberin()`, `eigene_rolle()`, `rollen_auswahl()`); `ist_admin()` gilt jetzt auch für BetreiberInnen | `server/auth_guard.php` |
| Migration `2026_09_05_rolle_betreiberin` (ENUM erweitern, dann alle Admins zu BetreiberInnen), Register in `schema.sql` | `server/update.php`, `server/schema.sql` |
| Rolle anlegen, zählen, filtern, sortieren, anzeigen | `server/admin_users.php` |
| Rollenwechsel mit drei Schranken, Löschschranke, gesperrtes Rollenfeld, Hinweis in der Gefahrenzone | `server/admin_user.php` |
| Mails an alle mit Verwaltungsrechten, Standzählung | `server/adminbackup_lib.php` |
| Wartungsmodus lässt Verwaltende durch | `server/login.php` |
| Adminhinweis auf der öffentlichen Rechtstextseite | `server/rechtstext_seite.php` |
| Erstes Konto wird BetreiberIn; Texte auf „BetreiberIn" | `server/install.php` |
| Eigene Rolle im Profil (nur lesend) | `server/einstellungen.php` |
| Doku | `docs/Handbuch.md` 3.1a, 11, 11.3 („Drei Rollen"), `docs/Technik.md` 7, `docs/Backup-Format.md` 6.8, `docs/CHANGELOG.md` |

**Entscheidungen der Umsetzung (U-Nummern, ergänzen die E-Nummern):**

- **U-AP1-01 — Der Rollenkatalog liegt in `db.php`, die Wächter in
  `auth_guard.php`.** Zwei Stellen brauchen die Rollenfrage **ohne Sitzung**:
  `login.php` entscheidet vor dem Anmelden, ob der Wartungsmodus jemanden
  durchlässt, und `rechtstext_seite.php` liest die Rolle einer möglicherweise
  fremden Zeile. Beide können `auth_guard.php` nicht laden — es leitet auf die
  Anmeldung um. Die reinen Prädikate stehen deshalb in `db.php`; alles, was an
  der angemeldeten Sitzung hängt, in `auth_guard.php`.
- **U-AP1-02 — „Admins" zählt jedes Konto mit Verwaltungsrechten.** Kennzahl
  und Filter der NutzerInnen-Liste (und dieselbe Zählung in
  `adminbackup_lib.php`) beantworten die Frage „wie viele können hier
  verwalten?"; darauf ist eine BetreiberIn ein Ja. Wer wissen will, wer
  betreibt, liest die Rollenspalte — sie nennt drei Werte und sortiert nach
  Rechten (BetreiberIn, Admin, NutzerIn). Ein eigener Filter „BetreiberInnen"
  wäre bei zwei bis drei solchen Konten eine Plakette ohne Nutzen; er kann in
  P5 mit der Support-Rolle entstehen.
- **U-AP1-03 — Die Betrieb-Seiten behalten in AP1 ihren `require_admin()`.**
  `admin_komplettsicherung.php`, `admin_sicherungsziele.php` und `update.php`
  ziehen erst mit dem Menü in den Block Betrieb (AP5); ihre Wächter wechseln
  mit dem Umzug. Sie vorher zu verschärfen hieße, Menüeinträge stehen zu
  lassen, die für einen Admin auf 403 zeigen. **Folge für die Abnahme:** P-01
  ist an dieser Stelle noch nicht die Endfassung der Rechtematrix 5.3 — das
  Prüfdokument sagt es und wiederholt den Punkt nach AP5.
- **U-AP1-04 — Ein `disabled` allein ist auf einem Feld unsichtbar.** Siehe
  Fund F-S8-P-03; gelöst mit dem vorhandenen Baustein `feldsatz-gesperrt`
  statt mit einer neuen Regel im Stylesheet.
- **U-AP1-05 — Das gesperrte Rollenfeld trägt seinen Wert in einem versteckten
  Feld mit.** Ein `disabled` fieldset schickt nichts mit; ohne das versteckte
  Feld läse der Schreibweg „NutzerIn" und antwortete auf jedes Speichern von
  Name oder Adresse mit einer Rollen-Fehlermeldung. Das versteckte Feld steht
  **außerhalb** des Feldsatzes.
- **U-AP1-06 — Das Profil zeigt die eigene Rolle**, nur lesend. Es ist der
  einzige Ort, an dem eine NutzerIn sie nachsehen kann, und er erklärt, warum
  zwei Konten unter dem Zahnrad verschieden viel sehen. Kein neuer Baustein —
  ein `feld-hinweis` in der vorhandenen Karte „Angaben".

**Probleme und Funde** (Nummern wie im Prüfdokument):

- **F-S8-P-01 (blockierend, behoben): `tools/wortliste/ausnahmen.json` war
  kein gültiges JSON.** Zwischen den Einträgen `herkunft-wertevorrat-garmin`
  und `technik-abgrenzung-beide-uhren` fehlte `},\n{` — die
  Konfliktauflösung im Merge `589982b` hat sie verloren. Die Wortliste bricht
  seither mit `JSONDecodeError` ab, **statt zu messen**; sie kann also seit
  diesem Merge nicht gelaufen sein. Behoben, weil sie für jede Textänderung
  Pflicht ist (`CLAUDE.md` 6) und der Fund die laufende Arbeit blockierte
  (K4).
- **F-S8-P-02 (behoben, Folge desselben Merges):** Nach der Reparatur meldete
  die Wortliste eine **ungenutzte Ausnahme** — `technik-abgrenzung-beide-uhren`
  erwartet in `docs/Technik.md` den Wortlaut „Die **Garmin-Uhr** hält es
  genauso"; dort stand „Die Uhr-App hält es genauso". Die Begründung der
  Ausnahme sagt selbst, warum das falsch ist: Der Satz steht im
  Android-Kapitel und vergleicht mit der Connect-IQ-Uhr; „Uhr-App" ist genau
  dort zweideutig, wo es auf den Unterschied ankommt. Wortlaut
  wiederhergestellt.
- **F-S8-P-03 (behoben): Ein `disabled` am Auswahlfeld war unsichtbar.**
  `.feld-eingabe` setzt `background` und `color` selbst und überschreibt
  damit, was der Browser sonst graut; eine Regel `:disabled` gibt es nicht.
  Das gesperrte Rollenfeld sah aus wie ein bedienbares. Gelöst **ohne**
  Stylesheet-Änderung mit dem vorhandenen `feldsatz-gesperrt` (S3/AP10, für
  genau diesen Zweck gebaut): gemessen `:disabled` = wahr, Deckkraft 0,55.

**Was AP1 ausdrücklich noch nicht tut:** den Bereich „Betrieb" selbst. Bis
AP5 sieht eine BetreiberIn dasselbe wie ein Admin. Die Rolle kommt zuerst,
weil jede Seite von AP2 und AP4 mit `require_betreiberin()` beginnt.

### 11.3 AP2 — Betrieb, Teil 1: Updates, Jobs, Servereinstellungen (05.09.2026, Web 15.1.0)

**Version:** Web **15.1.0** — Nebennummer. Drei neue Seiten und zwei neue
Bausteine sind neue Funktionen, kein Umbau: Das Datenmodell bleibt, es gibt
**keine Migration** (alle neuen Werte liegen in `app_state`, das es längst
gibt), und die Wege durch die Anwendung ändern sich erst mit dem Menü in AP5 —
bis dahin sind die drei Seiten nur über ihre Adresse erreichbar. Die
Hauptnummer 15 hat AP1 vergeben, und zwar für die Rolle.

**Gebaut** (in fünf Commits, je einer Stufe entsprechend):

| Was | Wo |
|---|---|
| Migrationskatalog, Register, Lauf, Stand, Inhaltszählung — aus `update.php` herausgelöst | `server/migration_lib.php` (neu, 2 486 Zeilen) |
| Baustein `ui_codeblock_lang()` (Wertekasten zweite Stufe mit „kopieren") und `lesespalte` im Gerüst | `server/ui.php`, `server/assets/style.css`, `server/assets/kopieren.js` (neu) |
| Speicher der Installation: zwei Bezüge, Messung im Aufräumjob, Ton nach Schwellen | `server/speicher_lib.php` (neu), `server/jobs_lib.php` |
| Betrieb → Updates: Wartungsmodus mit Ablauf-Kurzform, Ausstehende, Ausgeführt, Fassung | `server/betrieb_updates.php` (neu) |
| Betrieb → Hintergrundjobs: Zustand, die drei Auslöser zum Kopieren, „Was hier gilt" | `server/betrieb_jobs.php` (neu) |
| Betrieb → Servereinstellungen: zwei Speicherbalken, Grenze, Schwellen, Webspace, Ablage | `server/betrieb_server.php` (neu) |
| Die drei Seiten in die Ausnahmeliste des Wartungsmodus | `server/wartung_lib.php` |
| Grenze und Schwellen ziehen aus der Backup-Seite ab; Ablage-Karte schlank mit Verweis | `server/admin_sicherungen.php`, `server/admin_komplettsicherung.php` |
| Übergangsseite mit Wegweiser und Logo-Karte; CLI-Notausgang unverändert | `server/update.php` |
| Bilderlauf: drei neue Seiten, Wartungsaufnahme zieht auf `betrieb_updates.php` | `tools/screenshots/seiten.json` |
| Wartungsprobe an die neue Lage angepasst (siehe F-S8-P-06) | `tools/wartungsprobe/probe.php` + `LIESMICH.md` |
| Doku | `docs/Handbuch.md` 8, `docs/Technik.md` 2, 4.99c, **4.99d (neu)**, 7, `docs/Design.md` 9.18–9.20, `docs/CHANGELOG.md` |

**Entscheidungen der Umsetzung (U-Nummern):**

- **U-AP2-01 — Der Migrationskatalog wird herausgelöst, bevor die Seite
  entsteht.** Zwei Aufrufer brauchen ihn: die neue Seite und der Notausgang
  `php update.php`, der ohne Sitzung läuft. Ein `require_once 'update.php'`
  aus der neuen Seite heraus hätte deren Ausgabe mitgezogen; ein zweiter
  Katalog wäre die schlimmste Lösung, denn **die Reihenfolge der Migrationen
  ist der Mechanismus**. Deshalb zuerst `migration_lib.php`, dann alles
  andere. Gegenprobe nach dem Herauslösen: 43 Einträge im Katalog gegen 43 in
  der `skipped`-Liste von `schema.sql`.
- **U-AP2-02 — Der Knopf „kopieren" steht `hidden` im Markup.** Ohne
  JavaScript bliebe sonst ein Knopf stehen, der nichts tut; das Skript macht
  ihn sichtbar. Rückfall bei fehlender Berechtigung (kein sicherer Kontext,
  abgelehnte Freigabe): Text markieren und „markiert — Strg+C" melden. Die
  Rückmeldung steht **im Knopf** — eine Zeile daneben, die auftaucht und
  wieder verschwindet, verschöbe das Layout.
- **U-AP2-03 — Der freie Webspace wird angegeben, nicht gemessen.**
  `disk_free_space()` liefert auf geteiltem Hosting den Datenträger des
  *Hosts*, nicht die Quota dieses Kontos. Eine Zahl im Terabyte-Bereich wäre
  schlimmer als keine: Man glaubte, es sei Platz. Ohne die Angabe zeigt der
  zweite Balken nur die Summe — ohne Anteil und ohne Warnung.
- **U-AP2-04 — Gemessen wird im Aufräumjob, nicht beim Seitenaufruf.**
  Verzeichnislauf plus `information_schema` kosten mehr, als eine Seite kosten
  darf, und die Zahlen ändern sich in Stunden. `speicher_messen()` hängt als
  letzter Schritt am täglichen `job_aufraeumen()`. Ein **Teilergebnis wird
  auch geschrieben**: Scheitert der Verzeichnislauf, steht dort 0 — als „nicht
  messbar" erkennbar, während ein alter Wert mit frischem Zeitstempel eine
  Lüge wäre.
- **U-AP2-05 — `sicherungen/` zählt im Dateilauf nicht mit.** Die Backups
  stehen im zweiten Balken als eigene Segmente; zweimal in dieselbe Summe
  genommen ergäbe das einen Balken über 100 %.
- **U-AP2-06 — Die Karte „Wartungsmodus" trägt den Ablauf als nummerierte
  Liste.** Fünf Schritte, drei davon auf dieser Seite. Der Ablauf stand bisher
  nur im Runbook; wer den Schalter sieht, sieht jetzt auch, wo im Vorgang er
  steht. Kein neuer Baustein — ein `<ol>` in einem `<div class="text">`.
- **U-AP2-07 — Die Wartungsaufnahme des Bilderlaufs zieht mit dem Schalter
  um.** `45a-wartung-aktiv` (auf `update.php`) ist entfallen,
  `46a-betrieb-updates-wartung` ist an seine Stelle getreten. Die
  Übergangsseite trägt den Balken zwar weiter, aber die Aufnahme soll die
  Seite zeigen, auf der man den Modus **schaltet**.

**Probleme und Funde:**

- **F-S8-P-04 (behoben): Der Wartungsmodus sperrte sich selbst aus.**
  `betrieb_updates.php` stand nicht in `WARTUNG_AUSNAHMEN`. Einschalten
  gelang, und die Antwort auf das Neuladen war **503** — von genau der Seite,
  auf der der Ausschalter steht. Der Weg zurück wäre `rm server/wartung.lock`
  per SSH gewesen. Alle drei Betriebsseiten stehen jetzt in der Liste, und die
  Wartungsprobe misst es (Erwartung 6, für alle drei einzeln).
- **F-S8-P-05 (behoben): Nach einem Fehler verschwanden die Migrationen
  dahinter aus der Anzeige.** Der Lauf brach ab, und die Karte zählte
  daraufhin weniger Ausstehende, als es gab — die Zahl war also **kleiner**
  als die Wahrheit, was die gefährlichere Richtung ist. Jetzt trägt jede
  Migration hinter dem Abbruch den Zustand `steht aus` mit dem Text „NICHT
  MEHR VERSUCHT — der Lauf hat davor abgebrochen." Gemessen mit vier
  Testmigrationen, eine davon scheiternd: Zählung 3 in allen drei Ansichten.
- **F-S8-P-06 (behoben): Die Wartungsprobe maß gegen die alte Entscheidung.**
  Nach AP2 meldete `tools/wartungsprobe/probe.php` **6 von 40 Erwartungen
  nicht erfüllt** — sie holte den Schalter von `update.php`, verlangte die
  sechs alten Ausnahmen und suchte in `login.php` nach dem Vergleich
  `!== 'admin'`, den AP1 durch `rolle_darf_verwalten()` ersetzt hat. Das ist
  kein Fehler der Anwendung, sondern ein Prüfmittel, das seiner Sache
  hinterherhinkt — und ohne die Anpassung hätte es ab hier bei **jedem** Lauf
  rot gemeldet und wäre dadurch wertlos geworden. Angepasst und um zwei
  Erwartungen erweitert (die beiden anderen Betriebsseiten, die
  Übergangsseite): **42 Erwartungen, 0 nicht erfüllt.** Das verwaltende Konto
  der Probe trägt jetzt die Rolle `betreiberin` — ein bloßer `admin` käme an
  `betrieb_updates.php` nicht hinein, und die Probe mäße den Wächter statt des
  Tors.
- **Kein Fehler der Anwendung, aber festgehalten:** Eine Testmigration mit
  `SELECT 1` brach mit `SQLSTATE[HY000] 2014 Cannot execute queries while
  other unbuffered queries are active` ab. Das ist eine Eigenschaft des
  ungepufferten PDO-Modus und trifft jede Migration, die eine Ergebnismenge
  offen lässt — echte Migrationen sind DDL und haben keine. Die Testmigration
  wurde auf `CREATE TABLE IF NOT EXISTS` umgestellt.
- **Aufgeräumt (Vollständigkeit):** Drei Pixelwerte außerhalb der Token
  (`border-radius:2px`, `margin-right:4px`, `vertical-align:-1px`) sind durch
  `var(--radius-rund)`, `var(--abstand-1)` und `middle` ersetzt; zwei
  abgeleitete Token (`--balken`, `--balken-punkt`) sind neu und in `Design.md`
  eingetragen.

**Was AP2 ausdrücklich noch nicht tut:** die Karten **Schlüsselableitung** und
**Umgebung**. Sie ziehen in AP4 auf `betrieb_status.php` und sind bis dahin
**nirgends sichtbar** — bewusst in Kauf genommen (die Übergangsseite sagt es),
weil beide nur im Fehlerfall etwas zeigen und ein vierter Ort in AP2 ein Ort
zu viel gewesen wäre. Die Logo-Karte steht bis AP3 weiter auf `update.php`.
Der Bericht „Einsätze ohne Diensttag" ist mit E-S8-17 ersatzlos entfallen.

---

---

## Änderungsverlauf dieses Dokuments

| Datum | Was |
|---|---|
| 05.09.2026 | **AP2 erledigt** (Web 15.1.0): die Wartungsseite aufgelöst — `betrieb_updates.php`, `betrieb_jobs.php`, `betrieb_server.php`; Migrationskatalog nach `migration_lib.php`; Speichermessung im Aufräumjob (`speicher_lib.php`); zwei Bausteine (`codeblock-lang`, `speicher-balken`). Drei Funde (F-S8-P-04 bis -06), alle behoben — darunter ein Wartungsmodus, der sich selbst aussperrte. Umsetzungsentscheidungen U-AP2-01 bis -07 |
| 05.09.2026 | **AP1 erledigt** (Web 15.0.0): dritte Rolle mit Migration, Hierarchie über `ist_admin()`, zwei Schranken, `install.php` legt BetreiberIn an, Profil zeigt die Rolle. Drei Funde (F-S8-P-01 bis -03), alle behoben — darunter eine seit dem Merge `589982b` unbrauchbare `ausnahmen.json` der Wortliste. Umsetzungsentscheidungen U-AP1-01 bis -06 |
| 05.09.2026 | **Schritt 5 erledigt** (Umsetzungsinstanz, Opus): Konzept, Prüfdokument und zwölf Mockups im Repositorium; Rahmenplan auf Fassung 28 (R74–R77, Schritte 7, 8, 10, Abschnitte 4, 5, 6, K1), Backlog 73–80 vermerkt, 80 geteilt, 117–122 angelegt. Statusblock, Abschnitt 10 und **Abschnitt 11 (Umsetzungsprotokoll)** angelegt |
| 05.09.2026 | Angelegt: Statusblock, Konzeptablauf, Aufgabe, Befund (Abschnitt 2 vollständig), F-S8-01 bis -06; F-S8-01, -03, -05 beantwortet |
| 05.09.2026 | F-S8-02 (Betreiberin-Oberfläche mit Rolle), F-S8-03 (Menüpunkt raus in S8) und F-S8-04 (APK-Karte jetzt) beantwortet; F-S8-07 (Rolle: Rahmenplan-Änderung, Rechte, Bestand, Vergabe) und F-S8-08 (SD-04) angelegt; Abschnitt 1.4 angepasst |
| 05.09.2026 | F-S8-06 (B) und F-S8-07 (alle vier Punkte) beantwortet; Nutzungskontext in 1.3; **Schritt 2: Vorschlag** — E-S8-01 bis -14 (Abschnitt 4) und Zielbild 5.1–5.7 |
| 05.09.2026 | **E-S8-01 bis -14 bestätigt** (Schritt 2 erledigt); Schritt 3 begonnen: Mockup-Plan in Abschnitt 6, Mockup 01 (Menü in drei Blöcken) zur Freigabe |
| 05.09.2026 | Mockup 01 freigegeben (beide Zusätze bleiben); Mockup 02 (Serverbetrieb) erstellt und zur Freigabe |
| 05.09.2026 | **Schritt 4 geschrieben:** acht Arbeitspakete (Abschnitt 7) mit Inhalt, Doku, Abnahme, Berührung; Prüfprotokoll-Soll P-01 bis P-42 (Abschnitt 8); Einfügeblöcke für Rahmenplan und Backlog (Abschnitt 9); Push-Anleitung (Abschnitt 10); Prüfdokument angelegt |
| 05.09.2026 | Mockups 11 und 12 freigegeben — **Schritt 3 abgeschlossen**; Paketschnitt für Schritt 4 zur Bestätigung vorgelegt |
| 05.09.2026 | Mockups 11 (Filterreihe) und 12 (Bedienhöhe) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 10 freigegeben; **Baustein-Bilanz 6.1** angelegt — fünf Erfindungen auf den Vorrat zurückgeführt (Akkordeon, Zähler, Tabelle, Zeile, Zeile), drei gewollte Neuerungen benannt; Mockups 04 und 10 entsprechend korrigiert |
| 05.09.2026 | Mockup 09 freigegeben; Mockup 10 (Geräte) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 08 freigegeben; Mockup 09 (Installation) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 07 Fassung 2 freigegeben (ohne „Größte Tabellen"); Mockup 08 (Konto-Backups und Kontoseite) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 07 Fassung 2: Gesamtspeicher (Datenbank, Dateien) gegen Webspace laut Hosting als zweiter Balken; Messregeln und Nicht-Messbarkeit des freien Webspace festgehalten |
| 05.09.2026 | Mockup 06 freigegeben; Mockup 07 (Servereinstellungen) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 05 freigegeben („Ausstehende Updates"); Mockup 06 (Hintergrundjobs) erstellt und zur Freigabe |
| 05.09.2026 | Mockup 04 Fassung 2 freigegeben (zwei Durchschnitte); Mockup 05 (Updates) erstellt und zur Freigabe |
| 05.09.2026 | Mockups 03 und 04 freigegeben; E-S8-15 bestätigt (je Spalte ein Punkt), E-S8-17 geschlossen (keine Statistikzeile); Statistik erweitert (Fassung 2: ohne Demo, Prozent, Zeiträume, Gerätemodelle-Tabelle mit CSV, Einsätze mit Ø); Hersteller-Ableitung festgehalten; Backlog 80 teilweise nach S8 (Rahmenplan-Änderung) |
| 05.09.2026 | Durchsicht Mockup 03: Seite **Statistik** im Block Betrieb (Kachel „Daten" zieht dorthin, P5-Dashboard und Nr. 80 verortet); E-S8-15 Fassung 2 (ohne Strich, enger, je Spalte ein fetter Punkt); Mockup 03 überarbeitet, Mockup 04 Statistik erstellt; Plan neu nummeriert |
| 05.09.2026 | Durchsicht Mockup 02: Betrieb neu geschnitten — E-S8-05 neu gefasst (Status, Updates mit Wartungsmodus, Hintergrundjobs, Servereinstellungen, Komplett-Backup, Backup-Ziele); E-S8-15 Leiste mit Unterpunkten, E-S8-16 Statusseite mit Ampel, E-S8-17 Einsätze ohne Diensttag entfällt (Statuszeile offen), E-S8-18 Speicherbalken und Zweispaltigkeit; Zielbild 5.1, 5.2, 5.3, 5.5 nachgezogen; Mockup-Plan neu nummeriert; Mockup 03 (Status) zur Freigabe |
