# Konzept S5 — Zusatz: Wartungsmodus (Paket W)

**Ergänzung zu `Konzept-S5-Kopplung-umgekehrt.md` · Rahmenplan Schritt 5 ·
Auftrag des Auftraggebers vom 03.09.2026 aus der vorgezogenen Planung v1.0
(`Konzept-Planung-v1.0.md`, Abschnitt 7) · Ablage `docs/konzepte/` neben dem
S5-Konzept (K1), Lebenszyklus R62.**

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 03.09.2026 — **gebaut** (Web 13.2.0) auf `claude/s7-umsetzung-vorbereiten-s8kax0`; alle vier F-Fragen entschieden. Der erste echte Einsatz des Schalters ist der Merge dieser Phase (6.2) |
> | Paket in Arbeit | keines — W ist gebaut. Offen bleibt der Nachweis, den nur ein echter Deploy führen kann (Abschnitt 6.2 und 6.3): dass der FTPS-Sync die `wartung.lock` stehen lässt |
> | Erledigt | **W** (03.09.2026, Web **13.2.0**): `wartung_lib.php`, Tor in `db.php`, Schalter und Balken auf `update.php`, Balken und E-S5W-09 in `login.php`, `.gitignore` und `deploy.yml`, `tools/wartungsprobe/` (**40 / 40**), Bilderlauf (16 Bilder), Technik 4.99c und Runbook, Handbuch 11.3, Vertrag 2.1, Design 10.1 — Abschnitt 5 |
> | Wo es hakt | **Nichts mehr offen.** F-S5W-01 bis -04 sind am 03.09.2026 entschieden (E-S5W-09 bis -12); F-S5W-01 **abweichend** von der Empfehlung — ein Nicht-Admin-Konto wird nach der Anmeldung sofort wieder abgemeldet |
> | Fable-Schritt | **keiner** (Dateiprüfung und eine 503-Antwort; keine Kryptographie, keine Migration) |
> | Erhoben an | `claude/s7-umsetzung-vorbereiten-s8kax0` (03.09.2026): Web 13.0.0 nach Paket A; `main` Uhr 2.0.0, Android 0.7.7 |
> | Erhoben aus | dem Repositorium allein (`server/db.php`, `auth_guard.php`, `update.php`, `jobs.php`, `ingest.php`, `pair.php`, `.gitignore`, `deploy.yml`, `docs/JSON-Vertrag.md`, Konzept S4). Kein Server, kein Gerät in der Konzeptsitzung — was sich so nicht ermitteln ließ, steht in Abschnitt 7 |

Dieses Dokument wird während der Umsetzung fortgeschrieben (Statusblock,
Abschnitt 5 Umsetzungsstand, Abschnitt 8 Fehlerfunde); das Prüfdokument S5
bekommt einen eigenen Abschnitt „Paket W". Nach der Freigabe des
S5-Abschlusses geht dieses Dokument denselben Weg wie das Hauptkonzept
(R62): Erledigt-Zeile, Reste, Backlog, löschen.

**Was dieses Dokument nicht festlegt (K2, K3):** keine Versionsnummern
(Paket W ist eine **Nebenversion** des Web — neue Funktion), keine
Modellempfehlung, keine Backlog-Nummern — Kandidaten heißen
„Backlog-Kandidat". **Nummernkreise:** E-S5W, F-S5W, B-S5W sind von
E-S5/F-S5/B-S5 und E-S5Z/F-S5Z getrennt.

**Warum ein Zusatz und kein Nachtrag in B–D:** K4 (was nicht im Paket steht,
wird nicht gebaut) und drei parallel laufende Zweige. W berührt nur den
Server und die Doku; es ist von B, C, D und E unabhängig (Abschnitt 5,
Berührungen).

---

## 0. Auftrag an die Umsetzungsinstanz (Zusatzprompt)

> Konzept S5 wird um **Paket W — Wartungsmodus** ergänzt: ein Schalter auf
> der Wartungsseite, der die Installation vorübergehend für alle außer der
> Administration schließt. Endgeräte bekommen 503 und liefern nach; sie
> werden **nicht** geändert. Grundlage ist dieses Dokument; es liegt neben
> dem S5-Konzept unter `docs/konzepte/`.
>
> 1. F-S5W-01 bis F-S5W-04 dem Auftraggeber mit Empfehlung vorlegen,
>    Antworten als E-S5W-09 ff. hier eintragen (K6).
> 2. Im S5-Konzept: Statusblock um die Zeile „Paket W (Zusatz): siehe
>    `Konzept-S5-Zusatz-Wartungsmodus.md`" ergänzen; in Abschnitt 9
>    („Umsetzungsstand") eine Zeile W.
> 3. W läuft **nach Paket B**, vor D, auf demselben Zweig. Es berührt
>    `server/db.php`, `server/update.php`, `server/login.php`,
>    `server/wartung_lib.php` (neu), `.gitignore`,
>    `.github/workflows/deploy.yml` und Doku — nicht `einstellungen.php`
>    (B), nicht `watch/`, nicht `android/`.
> 4. Umsetzung nach Abschnitt 4 (Verhalten) und 5 (Paket, Abnahme):
>    `server/version.php` Nebenversion, Changelog, `docs/Handbuch.md`
>    (Admin-Kapitel), `docs/Technik.md` (Betriebsablauf „Update mit
>    Wartungsmodus", Runbook-Schritte), `docs/JSON-Vertrag.md` (503-Rumpf
>    als Hinweis unter 5xx), Prüfdokument S5 Abschnitt „Paket W",
>    `tools/wortliste/` Bereiche a und c, Statusblock, Push (K7).
> 5. Prüfmittel zuletzt (Abschnitt 6.1): Wartungsprobe über echtes HTTP
>    nach dem Muster der Kopplungsprobe, Bilderlauf der Wartungsseite,
>    Kreisläufe, Register gegengezählt (unverändert), `php -l`.
> 6. Kein Fable-Schritt. Kein Push auf `main` vor der Bestätigung (K7).
>    Der erste Einsatz des Schalters ist der Merge von W selbst
>    (Abschnitt 6.2).

---

## 1. Befund

### 1.1 Wie ein Update heute abläuft (am Code gelesen)

| Schritt | Was geschieht | Fundstelle |
|---|---|---|
| Deploy | Push auf `main` → FTPS-Sync von `server/` auf den Produktivserver; Ausnahmen `config.php`, `install.lock`, `sicherungen/`, `apk/` | `.github/workflows/deploy.yml` |
| Fenster | Zwischen erstem und letztem hochgeladenen File laufen alte und neue Dateien nebeneinander; danach erwartet der Code Tabellen, die eine ausstehende Migration erst anlegt | — |
| Migration | Administratorin ruft `update.php` (Web: `auth_guard.php` + `require_admin()`; CLI: `php update.php` als Notausgang) | `server/update.php` 12–20 |
| Geräte im Fenster | `ingest.php`, `pair.php` laufen über `db.php`; eine fehlende Tabelle wird zur PDO-Ausnahme → 500 | `server/ingest.php` 3, `server/pair.php` 86 |
| Jobs | drei Wege: CLI-Cron, `jobs.php?token=…`, huckepack aus `auth_guard.php` (`run_cleanup_if_due()`); `--pause` hält Jobs für Messungen an | `server/jobs.php` 1–40, 106–136; `server/auth_guard.php` 206 |
| Gemeinsamer Einstieg | **jede** PHP-Datei mit Datenzugriff lädt `db.php` (Seiten über `auth_guard.php`, Endpunkte direkt); `db()` verbindet **erst beim ersten Aufruf** (statisch); `json_out()` wohnt in `db.php` | `server/db.php` 1–20, 247 |
| Sperren heute | `install.lock` sperrt nur `install.php`; ein Wartungsschalter existiert nicht | `.gitignore`, `server/install.php` |

### 1.2 Was die Endgeräte bereits können

- **JSON-Vertrag Abschnitt 5, Fehlertabelle:** `5xx → Später unverändert
  erneut versuchen (Backoff)`. Die Uhr leert ihren Puffer erst nach
  bestätigtem `next_seq` (Vertrag 4, „Nachzügler: bei fehlender Verbindung
  puffert die Uhr und sendet später identisch nach").
- **Android:** E-S4-06 „Puffer und Warteschlange wie die Uhr" (SQLite,
  400 markieren, 401 pausieren, 413 halbieren, **5xx Backoff**); im
  S4-Prüfprotokoll geprüft: „5xx / 503 → später erneut, nichts markiert,
  nichts bestätigt".
- **Folge:** Antwortet der Server während der Wartung mit **503**, geht kein
  Gerätedatum verloren, und **kein Client wird geändert**. Paket W ist
  reine Serverarbeit.

### 1.3 Was fehlt

Ein Schalter, der (a) alle Anfragen außer denen der Administration mit 503
beantwortet, (b) ohne Datenbank auskommt (die ist im Wartungsfall gerade
das, was umgebaut wird), (c) den Deploy überlebt, (d) sichtbar ist, solange
er steht. Der **Torwächter** aus Rahmenplan R40 (4) (P5) ist der
automatische Sonderfall desselben Mechanismus — Wartung bei ausstehender
Migration; er hängt sich später an denselben Zustand.

---

## 2. Entscheidungen (E-S5W) — aus der Freigabe des Zuschnitts vom 03.09.2026

| Nr. | Entscheidung |
|---|---|
| E-S5W-01 | **Schalter auf `update.php`**, nur für Admin (`require_admin()`): „Wartungsmodus einschalten" / „Wartungsmodus ausschalten"; mit Zeitpunkt und Konto. S8 (Nr. 77) verschiebt ihn später auf die Unterseite „Serverbetrieb". |
| E-S5W-02 | **Zustand als Datei `server/wartung.lock`** neben `install.lock` — kein Datenbankzugriff, damit der Schalter auch bei laufender oder gescheiterter Migration greift. Inhalt: JSON mit `seit` (ISO-Zeit) und `von` (Anzeigename des Kontos). Vom Deploy ausgenommen (`deploy.yml` `exclude`) und aus dem Repositorium ausgeschlossen (`.gitignore`) — wie `install.lock`. |
| E-S5W-03 | **Wirkung:** jede Web-Anfrage außer den Ausnahmen → **HTTP 503** mit `Retry-After`. Geräte-Endpunkte und Browser-Skriptaufrufe (`/api/`): `{"error":"maintenance"}` — die Clients behandeln es nach der 5xx-Regel. Seiten: eine schlichte Wartungsseite ohne Datenbank (Abschnitt 4.3). CLI-Aufrufe sind nie betroffen. |
| E-S5W-04 | **Ausnahmen:** `update.php` und `wiederherstellen.php` (die Arbeit selbst und der Rollback), `jobs.php` (Token-Weg — das Komplett-Backup der Kette läuft **während** der Wartung, genau dann ist es konsistent), `login.php`/`logout.php` (damit die Administration hineinkommt, F-S5W-01), `install.php` (hat `install.lock`), statische Dateien unter `assets/` (laufen nicht durch PHP). |
| E-S5W-05 | **Kein automatisches Ausschalten**, keine Zeitsteuerung, kein Aufruf nach außen. Ein stehender Wartungsmodus ist auf `update.php` unübersehbar (Balken „Wartungsmodus seit … von …"). |
| E-S5W-06 | **Das Tor sitzt in `db.php`**, hinter `json_out()` und vor jeder Datenbankverbindung (`db()` verbindet erst beim ersten Aufruf); die Prüfung und die Antworten wohnen in `server/wartung_lib.php` (neu), damit `db.php` nur eine Zeile bekommt. |
| E-S5W-07 | **Jobs laufen weiter** wie heute; der Wartungsmodus hält sie nicht an. `jobs.php --pause` bleibt das Werkzeug für Messungen. (F-S5W-03 fragt nur nach dem Huckepack-Weg.) |
| E-S5W-08 | **Kein Client wird geändert.** Eine eigene Wartungsmeldung auf Uhr und Handy ist Backlog-Kandidat nach v1.0 (Abschnitt 9). |
| E-S5W-09 | **F-S5W-01 entschieden (Auftraggeber, 03.09.2026): Ein Nicht-Admin-Konto wird nach gelungener Anmeldung SOFORT WIEDER ABGEMELDET** — gegen die Empfehlung, die die Sitzung stehen lassen wollte. `login.php` bleibt erreichbar und trägt den Balken; der Passwortvergleich ist unverändert (Antwortgleichheit unberührt, der Zweig hängt am **Erfolg**, nicht am Vergleich). Nach dem Erfolg entscheidet die Rolle: **Admin** → `update.php`; **alles andere** → Sitzung sofort beenden und die Wartungsseite (503) ausliefern. **Drei Dinge, die daran hängen und leicht falsch gemacht werden:** (a) Wer abgemeldet wird, darf **nicht** wieder das Anmeldeformular sehen — das läse sich wie „Passwort falsch"; er sieht die Wartungsseite, und die sagt, warum. (b) `rate_erfolg('login')` und `rate_erfolg('salt')` laufen **trotzdem**: Das Passwort war richtig, und wer während der Wartung dreimal richtig tippt, darf sich danach nicht ausgesperrt finden. (c) Die Rolle steht seit M1-05 **nicht** in der Sitzung; `login.php` muss `role` in seine Abfrage aufnehmen. **Was der Auftraggeber damit kauft:** Während der Wartung liegt keine Sitzung mit entsperrtem Inhaltsschlüssel herum, und keine Nicht-Admin-Sitzung schreibt in `users` (`last_login`), während `update.php` das Schema umbaut. **Was er bezahlt:** Wer sich während der Wartung anmeldet, meldet sich danach ein zweites Mal an. |
| E-S5W-10 | **F-S5W-02 entschieden (Auftraggeber, 03.09.2026): Die 503-Antwort trägt `meldung`, die Skripte werden NICHT nachgezogen.** Am Code nachgesehen (die Frage aus Abschnitt 7): **`export.js` 180, `import_ui.js` 262/715 und `schneiden.js` 268/495 lesen `d.meldung` aus jeder Fehlerantwort**, ohne auf den Zahlencode zu sehen — sie zeigen den Wartungstext also ohne eine Zeile Änderung. **`kopplung.js` 99 wirft `'HTTP ' + status`**, `unlock.js`, `ortsfeld.js` und `ortswahl.js` zeigen ihre allgemeine Meldung. Das bleibt so: Drei davon sind Komfortwege (Adresssuche), und der vierte ist der Kopplungstakt, der sich nach drei Fehlern selbst beendet — während einer Wartung koppelt ohnehin niemand. Backlog-Kandidat „Browser-Skripte zeigen den Wartungstext einheitlich". |
| E-S5W-11 | **F-S5W-03 entschieden (Auftraggeber, 03.09.2026): Die Huckepack-Jobs bleiben wie heute** — der Wartungsmodus hält sie nicht an. Aber die Wartungsprobe **misst** es (Fall 8 und der CLI-Nachsatz), damit aus „wie heute" eine geprüfte Zahl wird und nicht eine Annahme bleibt. Zur Einordnung, am Code gelesen: `update.php` lädt `auth_guard.php` (Zeile 18), und dessen letzte Zeile ruft `run_cleanup_if_due()` (206) — **vor** `require_admin()` und damit vor jeder Migration desselben Aufrufs. Der Aufräumjob läuft also gegen das **alte** Schema und ist fertig, bevor die Migration beginnt; er läuft zudem höchstens einmal je Tag. Wer während einer laufenden Migration Ruhe braucht, nimmt `jobs.php --pause` (E-S5W-07). |
| E-S5W-12 | **F-S5W-04 entschieden (Auftraggeber, 03.09.2026): `Retry-After: 300`.** Fünf Minuten. Die Geräte halten ihren eigenen Backoff (Vertrag 5xx); der Wert ist ein Hinweis für Browser und Werkzeuge und passt zur Dauer eines Updates mit Migration. |

---

## 3. Offene Fragen (F-S5W) — **alle vier entschieden am 03.09.2026**

Vorgelegt mit Empfehlung (K6, Abschnitt 0 Punkt 1), beantwortet vom
Auftraggeber. **Drei folgen der Empfehlung, eine nicht** — F-S5W-01. Die
Entscheidungen stehen ausgeschrieben als E-S5W-09 bis -12 in Abschnitt 2;
hier nur, was gefragt war und was daraus wurde.

| Nr. | Frage | Empfehlung war | Entschieden |
|---|---|---|---|
| F-S5W-01 ✓ | **Anmeldung während der Wartung.** Sollen sich auch NutzerInnen anmelden können? | Anmeldung läuft normal, Nicht-Admin landet auf der Wartungsseite, **Sitzung bleibt** | **Abweichend: Sitzung wird sofort beendet** → E-S5W-09 |
| F-S5W-02 ✓ | **Was die Browser-Skripte zeigen.** Soll das Skript die Wartung eigens melden? | `meldung` mitsenden, **wenn** es ohne neuen Aufwand geht | **Wie empfohlen** — und es geht ohne Aufwand: drei Skripte zeigen sie schon heute → E-S5W-10 |
| F-S5W-03 ✓ | **Huckepack-Jobs** laufen auf `update.php` auch während der Wartung. Stört das? | Nein — wie heute, festhalten statt ändern | **Wie empfohlen**, zusätzlich gemessen → E-S5W-11 |
| F-S5W-04 ✓ | **`Retry-After`** in Sekunden. | 300 | **Wie empfohlen: 300** → E-S5W-12 |

---

## 4. Verhalten

### 4.1 Zustände

| Zustand | Erkennbar an | Wer sieht was |
|---|---|---|
| Normal | keine `wartung.lock` | alles wie heute |
| Wartung | `wartung.lock` vorhanden und lesbar | Seiten: Wartungsseite (503, HTML) · `/api/`, `ingest.php`, `pair.php`: 503 JSON · Ausnahmen (E-S5W-04): unverändert, dazu der Balken auf `update.php` und `login.php` |
| Datei unlesbar / kaputtes JSON | `wartung.lock` vorhanden, Inhalt nicht auswertbar | **Wartung gilt trotzdem** (die Datei ist der Schalter, nicht ihr Inhalt); Balken zeigt „seit unbekannt" |

### 4.2 Übergänge

- **Einschalten** (`update.php`, POST mit CSRF, Admin): Datei schreiben
  (`seit`, `von`); Meldung „Wartungsmodus eingeschaltet — alle anderen
  Anfragen bekommen 503; Geräte liefern nach." Idempotent.
- **Ausschalten** (`update.php`, POST mit CSRF, Admin): Datei löschen;
  Meldung „Wartungsmodus ausgeschaltet." Scheitert das Löschen (Rechte),
  sagt die Seite das mit dem Pfad — nichts Stilles.
- **Deploy** löscht die Datei nicht (Ausnahme in `deploy.yml`); ein Update
  im Wartungsmodus bleibt im Wartungsmodus, bis die Administratorin
  ausschaltet.

### 4.3 Die Wartungsseite (503, HTML)

Ohne Sitzung, ohne Datenbank, ohne `ui.php` (das braucht `logo_stamm()` aus
der Datenbank). Eine Datei aus `wartung_lib.php`: Standardlogo aus
`assets/` (bei Selbsthostern mit eigenem Logo erscheint während der Wartung
das Standardlogo — festgehalten), `assets/style.css` darf verlinkt werden
(statisch). Text:

> **Wartung.** NAdoku wird gerade aktualisiert und ist in wenigen Minuten
> wieder da. Deine Uhr und dein Handy liefern ihre Daten danach von selbst
> nach.
> Hast du gerade ein Formular abgeschickt: Geh im Browser **zurück** — die
> Eingaben stehen noch im Formular — und schick es später erneut ab.

Kopfzeilen: `HTTP/1.1 503 Service Unavailable`, `Retry-After: <F-S5W-04>`,
`Cache-Control: no-store`. Kein Skript.

### 4.4 Die Wartungsantwort (503, JSON)

`{"error":"maintenance","meldung":"NAdoku wird gerade aktualisiert. Bitte
später erneut."}` — der Schlüssel `error` ist die Zusage an die Clients
(Vertrag 5xx), `meldung` dient dem Browser-Skript (F-S5W-02). JSON, wenn
der Pfad `/api/` enthält oder das Skript `ingest.php` oder `pair.php` ist;
die Umsetzung gleicht die Liste mit den Endpunkten im Vertrag ab.

### 4.5 Der Balken (Ausnahmeseiten)

Auf `update.php` und `login.php` im Wartungsmodus oben ein Balken (Farbe
Warnen aus der P3-Palette): „Wartungsmodus seit 03.09.2026 14:12 von
Philipp — alle anderen Anfragen bekommen 503." Auf `update.php` daneben der
Knopf „Wartungsmodus ausschalten"; im Normalzustand der Knopf
„Wartungsmodus einschalten" im Abschnitt Serverbetrieb.

### 4.6 Betriebsablauf „Update mit Wartungsmodus" (für `docs/Technik.md` und das Betreiberhandbuch)

1. Wartungsseite → Komplett-Backup prüfen (Zeitpunkt, Ziel erreichbar), ggf.
   „Jetzt sichern".
2. **Wartungsmodus einschalten.**
3. Push auf `main` (bis P5) bzw. Freigabe des Produktiv-Laufs (ab P5, R67).
4. `update.php` neu laden → ausstehende Migrationen ausführen.
5. Startseite in einem zweiten Reiter prüfen (kommt 503 — richtig).
6. **Wartungsmodus ausschalten.** Startseite erneut: antwortet, Fassung in
   der Fußzeile stimmt.
7. Uhr und Handy synchronisieren beim nächsten Kontakt von selbst.

---

## 5. Arbeitspaket W — Dateien, Berührungen, Abnahme

| | |
|---|---|
| **Dateien** | `server/wartung_lib.php` (neu: `wartung_aktiv()`, `wartung_daten()`, `wartung_tor()`, `wartung_einschalten()`, `wartung_ausschalten()`, Wartungsseite, JSON-Antwort, Ausnahmeliste) · `server/db.php` (eine Zeile: `require_once` + `wartung_tor()` hinter `json_out()`) · `server/update.php` (Schalter, Balken, Meldungen) · `server/login.php` (Balken; Weiterleitung nach Anmeldung nach F-S5W-01) · `.gitignore` (`server/wartung.lock`) · `.github/workflows/deploy.yml` (`exclude: wartung.lock`) · `server/version.php` · `CHANGELOG` · `docs/Handbuch.md` (Admin-Kapitel: Abschnitt „Wartungsmodus") · `docs/Technik.md` (4.6 als Betriebsablauf; Runbook) · `docs/JSON-Vertrag.md` (unter 5xx: „503 kann `{"error":"maintenance"}` tragen — Verhalten unverändert") · `docs/konzepte/Pruefdokument-S5-Kopplung-umgekehrt.md` (Abschnitt „Paket W") · `tools/wartungsprobe/` (neu, 6.1) |
| **Berührungen** | B (`einstellungen.php`): keine gemeinsame Datei. D (Doku): beide schreiben `Handbuch.md` und `Technik.md` — W **vor** D, damit D den Wartungsabschnitt mitliest. C, E: keine. S4-Rest (Schritt 6): `update.php` wird dort nicht angefasst. S8 (Nr. 77): verschiebt den Schalter, baut ihn nicht neu. |
| **Abnahme** | Wartungsprobe: **alle Erwartungen erfüllt**, Zahl gemeldet (Soll ≥ 12, Liste in 6.1) · Bilderlauf der Wartungsseite in den acht Breiten: 0 Überlauf, 0 Konsolenfehler · Kreisläufe csv und edbak 0 unerklärt (unberührt) · Register gegengezählt, **unverändert** (keine Migration) · `php -l` über alle geänderten Dateien · Wortliste a und c 0/0/0 · Betriebsablauf 4.6 einmal von Hand auf der Installation des Auftraggebers mit dem Merge von W selbst (6.2) |

### Umsetzungsstand (wird fortgeschrieben)

| Paket | Stand | Fassung | Zahlen | Anmerkung |
|---|---|---|---|---|
| W | **erledigt** 03.09.2026 | Web **13.2.0** (Nebennummer: neue Funktion, keine Migration, kein geänderter Datenweg) | Wartungsprobe **40 / 40** · Bilderlauf **16 Bilder, 0 Überlauf, 0 Konsolenfehler** · Kopplungsprobe 76 / 76, Ingestprobe 30 / 30 und Browser-Rundlauf 25 / 25 unverändert · Wortliste a–d über **129 Dateien** 0 / 0 / 0 · Vollständigkeit **278** unverändert · S5-Anker 0 nicht gefunden · `php -l` 0 Fehler | Dateien: **`server/wartung_lib.php` (neu)**, `server/db.php` (das Tor, eine Zeile plus Begründung), `server/update.php` (POST-Handler, Balken, Karte „Serverbetrieb"), `server/login.php` (Balken, `role` in der Abfrage, E-S5W-09), `.gitignore`, `.github/workflows/deploy.yml`, **`tools/wartungsprobe/` (neu)**, `tools/screenshots/` (Zustand `wartung`, zwei Seiten), `version.php`, `CHANGELOG.md`, `docs/Technik.md` (**4.99c neu**, Runbook), `docs/Handbuch.md` 11.3, `docs/JSON-Vertrag.md` (Fassung 2.1, 503 unter 5xx), `docs/Design.md` 10.1 |

### Probleme und wie sie gelöst wurden (Paket W)

| Nr. | Was auffiel | Wie es gelöst wurde |
|---|---|---|
| W-1 | **Die Wartungsprobe scheiterte an sich selbst.** Drei Schaltfälle bekamen 403 statt 200. Ursache war nicht der Code, sondern die Probe: `session_id()` und `session_start()` scheitern, sobald PHP etwas ausgegeben hat — sie las das CSRF-Token nach der ersten gedruckten Zeile nach und bekam einen Leerstring | Alle Sitzungen entstehen jetzt **vor** der ersten Ausgabe, und das Token wird mitgenommen statt nachgelesen; zum Aufräumen wird die Sitzungsdatei gelöscht. Steht als Warnung in `tools/wartungsprobe/LIESMICH.md` — es ist genau die Art Fehlschlag, die man beim nächsten Mal wieder dem Code anlastet |
| W-2 | **`ui_plakette` kennt kein Grün.** Der erste Entwurf gab der Karte „Serverbetrieb" im Normalzustand `['ton' => 'gruen']`. Es gibt vier Töne: neutral, orange, blau, rot | `neutral`. Eine neue Farbe hätte eine Herkunft gebraucht (`Design.md` — die Skala ist geschlossen), und „im Betrieb" ist kein Alarm, sondern der Normalfall |
| W-3 | **Ersatzschreibung in sichtbaren Texten.** Der erste Entwurf der Karte schrieb „Schliesst … voruebergehend fuer alle ausser der Verwaltung" — die Konvention des Projekts gilt für **Kommentare**, nicht für Bildschirmtexte | Umlaute eingesetzt. Dabei fiel eine ältere Stelle derselben Art auf: „Das Token liess sich nicht erzeugen" (`update.php`, Fehlerzweig des Token-Wechsels) — mitgenommen, siehe B-S5W-01 |
| W-4 | **Der Namensraum des Bilderlaufs war schon belegt.** Der neue Eintrag hieß zuerst `41-wartung` — es gibt bereits `41-kontoseite` und `45-wartung` (die Adminseite). `--nur 41` traf beide | `07-wartungsseite`, in der Gruppe „Öffentlich" hinter `06-wiederherstellen`. Dazu `45a-wartung-aktiv`: dieselbe Adminseite **mit** stehendem Wartungsmodus, damit Balken und Ausschalt-Knopf ein Bild haben |
| W-5 | **Der Bilderlauf konnte den Zustand nicht herstellen.** Die Wartungsseite entsteht nicht dadurch, dass jemand klickt, sondern dadurch, dass eine Datei auf dem Server liegt — ein `vorher`-Schritt im Browser hilft nicht | Ein Feld `"wartung": true` je Seiteneintrag. Der Lauf legt die Datei vor den acht Breiten an und entfernt sie danach; zusätzlich hängt das Ausschalten an `process.on('exit')` und an SIGINT/SIGTERM. **Eine fremde Wartung wird nicht angefasst** — läge beim Einschalten schon eine Datei, öffnete ein Bilderlauf sonst eine Installation, die jemand geschlossen hat |

### Fehlerfunde am Bestand (B-S5W)

| Nr. | Fund | Fundstelle | Umgang |
|---|---|---|---|
| B-S5W-01 | Eine sichtbare Meldung in Ersatzschreibung: „Das Token liess sich nicht erzeugen" | `server/update.php`, Fehlerzweig `jobs_token_neu` | **Behoben** in 13.2.0. Dieselbe Ungleichheit wie die vier Meldungen in `pair.php` (Web 13.1.1): Kommentare stehen in Ersatzschreibung, Bildschirmtexte nicht |

---

## 6. Prüfprotokoll-Soll

### 6.1 Wartungsprobe (maschinell, echtes HTTP, Muster `tools/kopplungsprobe/`)

Mit `wartung.lock` gesetzt (die Probe legt sie an und räumt sie ab):

| Nr. | Anfrage | Erwartung |
|---|---|---|
| 1 | `GET index.php` ohne Sitzung | 503, `Retry-After`, HTML enthält „Wartung", **kein** `Set-Cookie` |
| 2 | `GET einsatz.php` mit Nutzer-Sitzung | 503, HTML |
| 3 | `POST ingest.php` mit gültigem Geräteschlüssel | 503, `{"error":"maintenance"}`, **keine** Zeile in `missions`/Spuren |
| 4 | `POST pair.php` (Anliegen `start`) | 503, JSON `maintenance` |
| 5 | `GET api/…` (ein vorhandener Skriptendpunkt) mit Sitzung | 503, JSON `maintenance`, `meldung` vorhanden |
| 6 | `GET update.php` mit Admin-Sitzung | 200, Balken „Wartungsmodus seit", Knopf „ausschalten" |
| 7 | `GET update.php` mit Nutzer-Sitzung | wie heute (kein Admin → Abweisung), **nicht** 503 |
| 8 | `GET jobs.php?token=<gültig>` | 200, Job-Bericht (Jobs laufen) |
| 9 | `GET jobs.php?token=falsch` | 403 `{"error":"token"}` wie heute |
| 10 | `GET login.php` | 200, Balken; Anmeldung möglich |
| 11 | `POST wiederherstellen.php` Vorprüfung mit Admin-Sitzung | nicht 503 |
| 12 | `GET assets/style.css` | 200 (statisch, ungetort) |
| 13 | Ausschalten über `update.php` (POST, CSRF), dann 1 erneut | 200, Startseite |
| 14 | `wartung.lock` mit kaputtem Inhalt | 503; Balken „seit unbekannt" |
| 15 | Antwortzeit von 3 und 4 | unter der Antwortzeit ohne Wartung — das Tor greift **vor** Datenbank und Ratenschutz |

Dazu: `php update.php` (CLI) im Wartungsmodus läuft (Notausgang unberührt).

### 6.2 Von Hand — der erste Einsatz ist der Merge von W

Auf der Installation des Auftraggebers: Betriebsablauf 4.6 mit dem Merge,
der W bringt. Dabei die Uhr während des Wartungsmodus einmal senden lassen
(Einsatz abschließen): Erwartung „später erneut" auf der Uhr, nach dem
Ausschalten kommt der Einsatz an. Zeitpunkte und Ergebnis ins Prüfdokument.

### 6.3 Nicht prüfbar aus dem Container — steht im Prüfdokument an erster Stelle

Das Verhalten des FTPS-Sync gegenüber einer nicht im Repositorium liegenden
Datei (`wartung.lock`) — die Ausnahme in `deploy.yml` ist die Zusage,
bewiesen wird sie beim ersten Deploy im Wartungsmodus (6.2).

---

## 7. Was sich aus dem Repositorium nicht ermitteln ließ

| Frage | Wie die Umsetzung es belegt |
|---|---|
| Zeigt das Browser-Skript eine `meldung` aus einer 503-Antwort an, oder nur bei 401? | Fundstelle im Skript nennen (F-S5W-02), Entscheidung eintragen |
| Löscht `SamKirkland/FTP-Deploy-Action` serverseitige Dateien, die nicht im Quellstand liegen, ohne `exclude`? | Mit `exclude` gesetzt; beim ersten Deploy (6.2) nachgesehen, ob die Datei steht |

*Geklärt am Code:* `login.php` lädt `db.php` als Erstes (Zeile 4, danach
`session_lib.php`, `ratelimit_lib.php`) — das Tor in `db.php` muss
`login.php` also **über den Skriptnamen** ausnehmen, bevor irgendetwas die
Datenbank berührt; die Ausnahmeliste in `wartung_lib.php` prüft
`basename($_SERVER['SCRIPT_NAME'])`. `jobs.php` mit falschem Token
antwortet heute 403 (`jobs.php` 129) — Erwartung 9 in 6.1 ist danach
formuliert.

---

## 8. Fehlerfunde am Bestand (B-S5W, K4)

| Nr. | Fund | Fundstelle | Umgang |
|---|---|---|---|
| — | noch keine | | |

---

## 9. Nicht Umfang

- Der **Torwächter** (automatische Wartung bei ausstehender Migration) —
  P5, R40 (4); er setzt denselben Zustand und nutzt `wartung_lib.php`.
- Steuerung des Schalters aus der **Auslieferungskette** (etwa
  `jobs.php?token=…&wartung=an`) — P5 mit R67, Stufe 3.
- **Ankündigung** eines Wartungsfensters an NutzerInnen, Zeitsteuerung,
  automatisches Ausschalten (E-S5W-05).
- **Eigene Wartungsmeldung auf Uhr und Handy** und Auswertung von
  `Retry-After` — Backlog-Kandidat nach v1.0 (E-S5W-08).
- **Jobs anhalten** während der Wartung (E-S5W-07).
- Verschieben des Schalters auf die Unterseite „Serverbetrieb" — S8, Nr. 77.

---

## 10. Übergabe an die Umsetzung und Wirkung auf den Rahmenplan

1. F-S5W-01 bis -04 entscheiden lassen, als E-S5W-09 ff. eintragen.
2. Dokument neben das S5-Konzept legen; S5-Konzept Statusblock und
   Abschnitt 9 ergänzen (Abschnitt 0, Punkt 2); Prüfdokument S5 Abschnitt
   „Paket W" mit der Liste aus 6.1 anlegen.
3. W nach B, vor D. Fassung (Nebenversion), Changelog, Handbuch, Technik,
   Vertrag, Wortliste, Statusblock, Push.
4. Prüfmittel zuletzt; 6.2 mit dem Merge; Zahlen ins Prüfdokument.
5. **Beim Merge von W** in `docs/Rahmenplan.md`: Schritt 5 um „Paket W
   (Wartungsmodus, Zusatz)" ergänzen; Schritt 9 (P5), Torwächter: „setzt
   den Wartungsmodus aus Paket W automatisch" (der Einfügeblock 6.2.3.3
   in `Konzept-Planung-v1.0.md` nennt es bedingt — die Bedingung entfällt);
   Abschnitt 4 Berührungen: „W vor D (`Handbuch.md`, `Technik.md`)";
   Backlog-Kandidat „Wartungsmeldung auf den Geräten" mit Nummer nach
   `uniq -d`.
6. Nach der Freigabe des S5-Abschlusses: R62 wie für das Hauptkonzept —
   die Erledigt-Zeile nennt die Web-Fassung von W und die Zahl aus 6.1.
