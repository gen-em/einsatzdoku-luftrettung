# Technik.md — die Gliederung bis R17-27 und danach

*Eingefroren am 28.10.2026 (Schritt 12b, Paket R17-27, Backlog Nr. 408). Dieses
Dokument wird nicht fortgeschrieben; es übersetzt alte Abschnittsnummern von
`docs/Technik.md` in neue.*

**Warum es das gibt.** Bis Web 23.5.0 war `Technik.md` nicht mehr in
Reihenfolge: Die Abschnitte 4.99a2 bis 4.99c standen hinter „## 5. Uhr-App",
4.99d bis 4.99g hinter 5.2b, 4.99p bis 4.99s vor 4.99l, und die Nummern
„4.98a", „4.99", „5e" und „5e.1" gab es je zweimal; der Ratenschutz war ein
Unterabschnitt „5e" mitten in „5d", und die Konto-Backups standen ohne Nummer
hinter dem Backlog (F-R17-59). Die Betreiberin hat am 28.10.2026 entschieden:
komplett neu nummerieren, und hier festhalten, was wie verändert wurde.

**Was sich geändert hat — und was nicht.** Geändert sind die Überschriften
(dieselben Titel, neue Nummer, der Ratenschutz eine Ebene höher) und die
Verweise auf Abschnitte; sonst ist der Text nur verschoben. Gemessen: Nach
dem Umstellen der Verweise standen vor und nach dem Umsortieren dieselben
14 748 Zeilen da, als Menge verglichen, und dieselben 270 Überschriftstexte.
Die Verweise in den lebenden Dokumenten (`docs/`, `CLAUDE.md`, `README.md`,
die `LIESMICH.md`) sind umgestellt. **Nicht umgestellt** sind die Kommentare
im Code (`server/`, `tools/`, `android/`, `watch/`) und die Arbeitsdokumente
unter `docs/konzepte/`, der Changelog, die Archive und der Verlauf: Dort
bedeutet „Technik.md 4.99q" weiter den Abschnitt der alten Zählung — diese
Tabelle übersetzt ihn. Die Kommentare zieht Backlog Nr. 408 nach.

**Mehrdeutige alte Nummern.** „4.98a", „4.99", „5e" und „5e.1" gab es zweimal.
Ein Verweis darauf ist nur über seinen Gegenstand aufzulösen: Ortsfeld oder
Register, Mailweg oder Bausteine, Ratenschutz oder Verbindungsgrenze.

| alt | neu | Titel |
|---|---|---|
| `1` | **1** | Architekturüberblick |
| `2` | **2** | Verzeichnisstruktur |
| `2a` | **2.1** | Die Arbeitsumgebung — und was ihre Motoren messen |
| `3` | **3** | Datenmodell (MySQL) |
| `4` | **4** | Zentrale Abläufe |
| `—` | **4.1** | Die Antwortzeit als Auskunft |
| `4.99` (erstes „4.99") | **4.2** | Der Mailweg — Katalog, Warteschlange, Job (P5a/AP5) |
| `—` | **4.3** | Was ein Gerät beim Koppeln über sich meldet — seit Web 12.9.0 gespeichert |
| `—` | **4.4** | Geräte je Konto |
| `—` | **4.5** | Der Eingang der Endpunkte (ab Web 20.28.0) |
| `4.97` | **4.6** | Spurspeicherung: drei Stufen und ein Format (ab Web 10.0.0, S2) |
| `4.97a` | **4.7** | Hintergrundjobs: drei Auslöser, ein Katalog (ab Web 10.1.0, S2) |
| `4.97b` | **4.8** | GPX-Abruf je Spur und je Auswahl (ab Web 10.3.0, S2/AP4, E-S2-09; im Browser seit Web 22.3.0, S11-05d) |
| `4.97c` | **4.9** | Backup-Ziele: das Backup verlässt das Haus (ab Web 12.1.0, S2/AP7, E-S2-22) |
| `4.97d` | **4.10** | Komplett-Backup der Installation (ab Web 12.2.0, S2/AP8, E-S2-19 bis E-S2-21) |
| `4.97e` | **4.11** | Schneiden: ein Zeitbereich wandert (ab Web 12.5.0, S4/A2, E-S4-53) |
| `4.97f` | **4.12** | GPX herein: der Weg zurück (ab Web 12.7.0, S4/A3, E-S4-18; im Browser seit Web 22.3.0, S11-05d) |
| `4.97g` | **4.13** | Die Android-App verteilen (ab Web 12.8.0, S4/A1, E-S4-16) |
| `4.98` | **4.14** | Was im verschlüsselten Block liegt — und was nicht |
| `4.98a` (erstes „4.98a") | **4.15** | Ortsfeld und Luftlinie (ab Web 6.1.0) |
| `4.98b` | **4.16** | Sichtbarkeit von Einsatzfeldern |
| `4.98c` | **4.17** | Gruppen des Einsatzformulars (ab Web 7.0.0) |
| `4.98d` | **4.18** | Der Anhebelauf für Altbestand (`api/pat_anheben.php`, ab Web 19.0.0) |
| `4.98a` (zweites „4.98a") | **4.19** | Eine Stelle je Sache — und das Register, das es nachzählt |
| `4.99` (zweites „4.99") | **4.20** | Gemeinsame Bausteine |
| `4.99a` | **4.21** | Demo-Konto (ab Web 7.3.0) |
| `4.99a2` | **4.22** | Das Ersetzfenster der Geräte (ab Web 15.6.0, Backlog Nr. 134) |
| `4.99b` | **4.23** | Bedrohungsmodell der Kopplung (ab Web 13.0.0, S5) |
| `4.99c` | **4.24** | Wartungsmodus (ab Web 13.2.0, S5 Paket W) |
| `4.99d` | **4.25** | Betrieb: drei Seiten statt einer Wartungsseite (ab Web 15.1.0, S8/AP2) |
| `4.99e` | **4.26** | Status und Statistik: bewerten und zählen (ab Web 15.3.0, S8/AP4; drei Reiter ab Web 20.47.0, P5c/AP7) |
| `4.99f` | **4.27** | Menü und Leiste des Einstellungsbereichs (ab Web 15.4.0, S8/AP5) |
| `4.99g` | **4.28** | Das Betriebsprotokoll: Schreibweg, Seite, Archiv (ab Web 20.16.5, P5b/AP1; Seite und Archiv ab Web 20.39.0, P5c/AP2; Reiter System ab Web 20.40.0, P5c/AP3) |
| `4.99h` | **4.29** | Die Einstellungen rund um Konten (ab Web 20.16.5, P5b/AP1) |
| `4.99i` | **4.30** | Der Lebenszyklus eines Kontos (ab Web 20.17.0, P5b/AP2) |
| `4.99j` | **4.31** | Einwilligungen (ab Web 20.19.0, P5b/AP4) |
| `4.99k` | **4.32** | Selbstlöschung und Adresswechsel (ab Web 20.20.0, P5b/AP5) |
| `4.99l` | **4.33** | Mengengrenze je Konto (ab Web 20.21.0, P5b/AP6) |
| `4.99m` | **4.34** | Die Selbstregistrierung (ab Web 20.22.0, P5b/AP3) |
| `4.99n` | **4.35** | Handbuch und „Was ist NAdoku" als Seiten (ab Web 20.23.0, P5b/AP8) |
| `4.99o` | **4.36** | Einstiege nach der Anmeldung (ab Web 20.24.0, P5b/AP9) |
| `4.99p` | **4.37** | Die Berechtigungsmatrix (ab Web 20.39.0, P5c/AP2, E-P5c-22) |
| `4.99q` | **4.38** | Der Zweitfaktor (ab Web 20.42.0, P5c/AP5) |
| `4.99r` | **4.39** | Der Health-Endpunkt (ab Web 20.46.0, P5c/AP6) |
| `4.99s` | **4.40** | Das Schlüsselpaar des Kontos und die Gerätehülle (ab Web 22.0.0, S11-03) |
| `5` | **5** | Uhr-App (Monkey C) — Modulstruktur |
| `5.1` | **5.1** | Tastenbelegung je Geräteprofil |
| `5.1a` | **5.2** | Pausenzustand der Reanimation |
| `5.1b` | **5.3** | Geräteprofile im Jungle |
| `5.1c` | **5.4** | Was die Uhr mit einer Absage anfängt (ab Uhr 3.1.0, Backlog Nr. 159) |
| `5.2` | **5.5** | Neue Zielgeräte prüfen — `tools/uhr-pruefstand/eingabe-probe` |
| `5.2b` | **5.6** | Uhr-App ohne Arbeitsplatz prüfen — `tools/uhr-pruefstand` |
| `5a` | **6** | Android-Apps (Kotlin/Compose) — Handy und Wear OS |
| `—` | **6.1** | Zwei Module, ein Quelltext |
| `—` | **6.2** | Der Weg zwischen Uhr und Handy |
| `—` | **6.3** | Ortungswächter und Nachsenden |
| `—` | **6.4** | Dienstende und Nachsenden |
| `—` | **6.5** | Der Uhr-Spiegel |
| `—` | **6.6** | Was die App an den Server schickt |
| `—` | **6.7** | Prüfen ohne Gerät |
| `5b` | **7** | Plattformprofil (ab Web 20.5.0, P5a/AP2, R81) |
| `5b.1` | **7.1** | Eine Funktion, zwei Leser |
| `5b.2` | **7.2** | Die Prüfpunkte (Stand 20.09.2026) |
| `5b.2a` | **7.3** | Der Rückfall nennt seine Lage, er rät sie nicht (ab Web 20.26.1) |
| `5b.3` | **7.4** | Warum die Untergrenze bei PHP 8.2 liegt |
| `5b.4` | **7.5** | Die Weiche in `install.php` — und ihre Grenze |
| `5b.5` | **7.6** | Schreibrechte werden mit einer Probedatei geprüft |
| `5b.6` | **7.7** | Die zwei Kontingente und ihre Warnung |
| `5c` | **8** | Sicherheitskopfzeilen, CSP und die Client-Adresse (ab Web 20.7.0, P5a/AP4) |
| `5c.1` | **8.1** | Eine Stelle schreibt sie: `kopfzeilen_lib.php` |
| `5c.2` | **8.2** | Die Richtlinie |
| `5c.3` | **8.3** | Zwei Stufen: Report-Only, dann scharf |
| `5c.4` | **8.4** | HSTS ist eine Einstellung — und `.htaccess` hat die Zeile verloren |
| `5c.5` | **8.5** | Die Client-Adresse: `netz_lib.php` |
| `5c.6` | **8.6** | CSRF als Kopfzeile |
| `5c.7` | **8.7** | Nachweis: `tools/proben/csp-browser/` |
| `5d` | **9** | Der Name dieser Installation (ab Web 20.8.0, P5a/AP5) |
| `5d.1` | **9.1** | Zwei Werte, zwei Gebrauchslagen |
| `5d.2` | **9.2** | Die Datei lädt nichts — und das ist der Kniff |
| `5d.3` | **9.3** | Die Wartungsseite bekommt den Namen aus dem Schalter |
| `5d.4` | **9.4** | Der Name wird geprüft, weil er in einen Mailbetreff geht |
| `5d.5` | **9.5** | Zwei Stellen bleiben ausdrücklich fest |
| `5d.6` | **9.6** | Zwei Adressen der Installation (Web 20.9.0, E-P5a-40) |
| `5d.7` | **9.7** | `app_url()` — eine Basisadresse statt sieben Verkettungen |
| `5d.8` | **9.8** | Was von „Gen-EM" im Quelltext bleibt |
| `5e` (bisher `### 5e` unter `## 5d`) | **10** | Der Ratenschutz — Leiter, Schwellen, Verlangsamung, Mengenbremse, Sicherheitsseite (P5a/AP6–AP8) |
| `5e.1` (bisher `####` unter `### 5e`) | **10.1** | Die Leiter |
| `5e.2` (bisher `####` unter `### 5e`) | **10.2** | Zwei Schwellen, zwei Töpfe |
| `5e.3` (bisher `####` unter `### 5e`) | **10.3** | Die Verlangsamung — und wo sie ansetzt |
| `5e.4` (bisher `####` unter `### 5e`) | **10.4** | Zwei Fehler, die ohne Meldung durchgegangen wären |
| `5e.5` (bisher `####` unter `### 5e`) | **10.5** | Der Weg zurück |
| `5e.6` (bisher `####` unter `### 5e`) | **10.6** | Das Fenster zwischen Deploy und `update.php` |
| `5e.7` (bisher `####` unter `### 5e`) | **10.7** | Die Mengenbremse von `ingest.php` (Web 20.11.0, P5a/AP7) |
| `5e.8` (bisher `####` unter `### 5e`) | **10.8** | Die Sicherheitsseite (Web 20.12.0, P5a/AP8) |
| `5e` (bisher `## 5e`) | **11** | Die Verbindungsgrenze (ab Web 20.13.0, P5a/AP9, E-P5a-18) |
| `5e.1` (unter `## 5e`) | **11.1** | Gedrängel: 1213 und 1205 (E-P5a-52) |
| `6` | **12** | Deployment — die Auslieferungskette (ab Web 20.4.0, P5a/AP1) |
| `6.1` | **12.1** | Zwei Wege, drei Tore |
| `6.1a` | **12.2** | Der Läufer — ein festes Abbild statt `ubuntu-latest` (Nr. 236) |
| `6.2` | **12.3** | Stufe 1 — was ohne Installation messbar ist |
| `6.3` | **12.4** | Stufe 2 — was eine Installation braucht |
| `6.3a` | **12.5** | Staging und Produktiv sind zwei Plattformen (ab 20.09.2026, E-KH-04) |
| `6.4` | **12.6** | Das Backup-Tor |
| `6.5` | **12.7** | Was auf dem Server liegt |
| `6.5a` | **12.8** | Zielprobe und Probelauf — die Kette misst, statt zu glauben |
| `6.5b` | **12.9** | Warum die Zustandsdatei der Auslieferungsaktion da sein muss |
| `6.6` | **12.10** | Die Integritätswache hängt am Namen |
| `6.6a` | **12.11** | Der Zeiger `produktion` — wogegen die Wache vergleicht |
| `6.6b` | **12.12** | Der Hotfix-Weg — Runbook (ab AP7, E-KH-15/-16) |
| `6.7` | **12.13** | Selbst hosten — der Weg ohne GitHub bleibt vollständig |
| `7` | **13** | Betrieb (Runbook) |
| `—` | **14** | Konto-Backups (A8, seit Web 5.9.0; Name seit Web 15.2.0, E-S8-06) |
| `—` | **14.1** | Aufbewahrung und Verdrängung (seit Web 9.8.0) |
| `—` | **14.2** | Der Auftrag „Alle sichern" (seit Web 12.0.0) |
| `—` | **14.3** | Die NutzerInnen-Liste (E-P3-41, seit Web 9.9.0) |
| `—` | **14.4** | Die Kontoseite (E-P3-41, seit Web 9.8.0) |
| `—` | **14.5** | Die Regelseite (seit Web 9.10.0) |
| `—` | **14.6** | Die wöchentliche Erinnerung an die Verwaltung (seit Web 9.10.0) |
| `—` | **14.7** | Das Logo der Installation (E-P3-19/20, einstellbar seit Web 9.10.0) |
| `—` | **14.8** | Installation und Rechtstexte (R32, seit Web 9.11.0; Installation seit Web |
| `8` | **15** | Backlog |
