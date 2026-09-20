# Konzept — Sitzungsablage: die Anwendung legt ihre Sitzungen selbst ab

**Rahmenplan:** Schritt 16 (Einschub vom 20.09.2026), R81. **Backlog:** 241
(dieses Vorhaben), 242 (Sitzungsbindung, benannt und vertagt) — Nummern nach
Weg A des Einschubs; die 240 hatte die Kette-II-Instanz am 20.09.2026 für
einen eigenen Punkt vergeben.
**Auftrag:** `Prompt-Sitzungsablage-2026-09-20.md` (Claude Code, Kette II/AP1,
20.09.2026) — Befund, Fundstellen und die Entscheidung des Auftraggebers
für **Stufe 2**; Stufe 3 (Datenbank) ist dort mit vier Gründen verworfen
und wird hier nicht erneut abgewogen. **Modell:** Konzept Fable (R14),
Umsetzung Opus (K2), kein Fable-Schritt in der Umsetzung. **Ablage:** dieses
Dokument, Prüfdokument daneben (`Pruefdokument-Sitzungsablage.md`, entsteht
mit dem Paket).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **Freigegeben (Auftraggeber, 20.09.2026, abends) in der korrigierten Fassung.** F-1 bis F-6 aus dem Auftrag sind entschieden (Abschnitt 2); die Empfehlung aus Auftrag Abschnitt 9 ist aufgenommen (E-SA-07). **E-SA-02 ist am Abend des 20.09.2026 neu gefasst** (Abschnitt 2a): Der Befund kannte drei Sitzungsstarts, gemessen sind **neun**; der Cache lag in der Datenbank, die vor dem Sitzungsstart noch nicht verbunden ist. Die Freigabe gilt dieser Fassung. |
> | Entschieden | E-SA-01 bis E-SA-09 |
> | Offen | nichts im Konzept; **Anmeldung an Kette II:** der achte Schutzlistenpfad (E-SA-05) — E-KH-20, AP5 dort |
> | Umsetzung | ein Paket, **Nebennummer** (E-SA-08); parallel zu Kette II auf eigenem Zweig von `main` — berührt `sitzung_lib.php` (neu), `db.php`, `install.php`, `plattform_lib.php`, den Aufräumjob, `.gitignore`, Doku. **Merge nach `main` nur auf Ansage** (löst einen Staging-Deploy aus) |
> | Nummern | Dieses Konzept vergibt keine Rahmenplan-Fassung; Backlog 241 und 242 sind mit dem Einschub vom 20.09.2026 vergeben (Rahmenplan-Einschub-2026-09-20.md, Weg A) |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP1 | **erledigt** 20.09.2026, Web **20.26.0**, Zweig `claude/new-session-cbq57h` | Code, Doku und Prüfmittel gelaufen; Browserprüfung auf Staging offen | 9 Sitzungsstarts (Tokenizer, zweimal unabhängig) · 2 Aufrufstellen · 8 von 9 laden `db.php` davor · Dateizählung 1/0/1 · Marker 0/1 Probedateien · Aufräumjob „1 gelöscht" · CLI legt nichts an · 17 Befunde in `plattform_pruefen(null)` (vorher 15) · Jobregister 11/11 Jobs, 17/17 Schritte, 0 Befunde |
>
> **Wo es hakte, und wie es gelöst wurde** — die Langfassung steht in
> Abschnitt 5, die Prüfliste in `Pruefdokument-Sitzungsablage.md`:
>
> | # | Was das Konzept nicht wusste | Entscheidung |
> |---|---|---|
> | U-1 | E-SA-07 verlangt **rot**, rot gibt es nur auf Stufe `muss`, und `muss` + `false` sperrt `install.php` | `muss`, wie im Konzept — die Sperre ist unerreichbar, solange `schreib_wurzel` gilt (Auftraggeber, 20.09.2026) |
> | U-2 | `tools/wartungsprobe/` legt Sitzungsdateien **im CLI** an und wäre still gebrochen | im selben Paket mitgezogen (`sitzung_ort()`) |
> | U-3 | `SESSION_TIMEOUT_S` steht in `auth_guard.php`, das der Aufräumjob nie lädt | Konstante nach `sitzung_lib.php` verschoben |
> | U-4 | Nr. 208 wäre durch einen bloßen Nachzug **nicht** erledigt | Ursache behoben: erzeugte Beschreibung + `tools/jobregister/` (Auftraggeber, 20.09.2026) |
> | U-5 | Ohne `clearstatcache()` nach `mkdir` meldet die Probe einen Rückfall, den es nicht gibt | behoben, 5/5 Läufe sauber |
> | U-6 | Konzept nennt `PHP_SAPI === 'cli'`, das Haus benutzt `cli \|\| phpdbg` | `sitzung_cli()`, dieselbe Fassung wie `wartung_cli()` |
> | U-7 | Der vierte Schreibort ist auf Stufe `empfohlen` im guten Fall **unsichtbar** | gewollt; Ort und Dateizahl trägt der Muss-Punkt daneben |

---

## 0. Auftrag und Umfang

Die Anwendung soll ihre PHP-Sitzungsdateien **selbst** ablegen — in einem
eigenen, nicht auflistbaren Verzeichnis mit `0700` — statt sie dort liegen zu
lassen, wohin der Hoster `session.save_path` zeigen lässt. Anlass: Auf der
Staging-Anlage bei lima-city liegt `session.save_path` auf einem
**geteilten** Verzeichnis (`0773`, Eigentümer root, `gc_probability = 0`);
es ist gutgegangen, weil der Hoster das Auflisten sperrt — die Anwendung
hätte es nicht gemerkt. Für Produktiv ist derselbe Wert nicht erhoben, für
Selbsthoster ist er offen. Das verträgt sich nicht mit R81.

**Was auf dem Spiel steht, nüchtern:** Eine Sitzungsdatei trägt kein
Schlüsselmaterial; die E2E-Zusage ist nicht berührt. Sie trägt aber die
Sitzung selbst — der Dateiname ist die Sitzungs-ID. Wer sie liest, ist
angemeldet und sieht die Klartextliste aus `CLAUDE.md` 4; bei `role = admin`
die Verwaltung. Das ist der Schaden, den dieses Konzept verhindert.

**Nicht Gegenstand:** Stufe 3 (Sitzungen in der Datenbank — verworfen, mit
Verfallsdatum: sie wird alternativlos, sobald mehrere Anwendungsserver eine
Ablage teilen sollen, was auf keinem Fahrplan steht); Änderungen an Kette II
(die Schutzliste wird dort zusammengeführt, der neue Pfad wird **angemeldet**);
die Sitzungsbindung per Cookie-Token (E-SA-09, Backlog 242).

## 1. Befund (aus dem Auftrag, Stand `claude/fervent-dirac-xirsqw` 20.09.2026; Sitzungsstarts nachgemessen an `origin/main` `862ca7f`)

- Sitzungsstart in `auth_guard.php` Z. 31, `use_strict_mode` davor
  (E-P5a-38); **kein `session_save_path()` im Code**; die erste
  DB-Verbindung fällt nach dem Sitzungsstart (Z. 72–74) — `db.php` wird
  vorher geladen, verbindet aber erst beim ersten `db()`.
- **Es gibt neun `session_start()`-Aufrufe in neun Dateien, nicht drei**
  (nachgemessen 20.09.2026): `auth_guard.php`, `login.php`,
  `pw_handling.php`, `session_lib.php` (in `session_beenden()`),
  `doku_seite.php`, `notfallblatt.php`, `rechtstext_seite.php` (die drei
  letzten mit `@session_start()`, nur wenn ein Cookie da ist),
  `install.php`, `wiederherstellen.php`. **Acht davon laden `db.php` vor
  ihrem Sitzungsstart; `install.php` nicht** (dort gibt es noch keine
  `config.php`). Der Auftrag und die erste Fassung dieses Konzepts kannten
  nur drei — mit ihnen hätte `login.php` die Sitzung beim Hoster abgelegt
  und `auth_guard.php` sie in `.sitzungen/` gesucht: **niemand hätte sich
  anmelden können.**
- `session_lib.php` **besteht** (Abmelden, Logo, CSRF) und lädt `db.php` —
  `install.php` kann sie deshalb nicht laden. `plattform_lib.php` lädt ohne
  `db.php` und trägt `plattform_schreibprobe()`.
- `app_state` ist eine **Datenbanktabelle** (`k`, `v`) — vor dem
  Sitzungsstart nicht ohne vorgezogene Verbindung lesbar, in `install.php`
  gar nicht.
- Inaktivität: `SESSION_TIMEOUT_S` (30 min, `auth_guard.php` Z. 114–132)
  — die Anwendung hat also **schon eine eigene Sitzungsfrist**.
- Globale Sperrung über `users.session_epoch` — gelöst.
- `.htaccess` Z. 64 sperrt jeden Pfad mit führendem Punkt (403) außer
  `.well-known/`; Stufe 2 der Kette misst das bei jedem Push.
- `plattform_pruefen()` prüft drei Schreiborte mit Probedatei
  (`plattform_lib.php` Z. 327–346); Dreiwertigkeit `true`/`false`/`null`
  (Technik.md 5b.1).
- Der Aufräumjob (4.97a) räumt bereits acht Bestände; die Schutzliste des
  Transports steht in zwei wortgleichen `exclude`-Blöcken mit sieben Pfaden.
- `use_strict_mode` schützt **nicht** vor einer untergeschobenen Datei; was
  schützt, ist `use_only_cookies` — und ab jetzt der Ort.

## 2. Entscheidungen

**E-SA-01 — Ort: `.sitzungen/` unter der Anwendungswurzel** (F-3: mit
führendem Punkt). Damit greifen die bestehende `.htaccess`-Regel und die
bestehende Prüfung der Kette ohne Änderung. „Eine Ebene über dem Webroot"
ist verworfen: Bei beiden Anlagen ist das FTP-Konto auf die Wurzel
eingesperrt, und ob PHP darüber schreiben darf, ist genau die
Anlagenabhängigkeit, die der Auftrag beseitigt.

**E-SA-02 — Anlegen, Rechte und die eine Stelle** (neu gefasst 20.09.2026,
Abschnitt 2a). `sitzung_ablage()` liegt in **`sitzung_lib.php`** (neu, eine
Funktion, **ladbar ohne `db.php` und ohne `config.php`** — nicht in
`session_lib.php`, die `db.php` lädt). Sie legt `.sitzungen/` mit `0700` an,
wenn es fehlt (`mkdir` mit `0700`, danach `chmod`, weil `umask` mitreden
kann), prüft Beschreibbarkeit mit Probedatei (`plattform_schreibprobe()`,
nur in diesem Zweig nachgeladen) und ruft bei Erfolg `session_save_path()`.
**Gerufen wird sie an zwei Stellen, nicht an neun:** in **`db.php`** — früh,
neben `wartung_tor()`, **ohne Datenbank** — und in **`install.php`** vor
dessen `session_start()`. Acht der neun Sitzungsstarts laden `db.php` davor
und sind damit gedeckt, ein künftiger zehnter ebenso; genau das ist der
Grund für die zentrale Stelle. Die Funktion tut nichts im CLI-Lauf
(`PHP_SAPI === 'cli'` — sonst legte ein Cron-Nutzer das Verzeichnis mit
fremdem Eigentümer an) und nichts, wenn schon eine Sitzung läuft. Die neun
Sitzungsstarts selbst bleiben, wie sie sind, bis Schritt 15 Paket 3
`sitzung_starten()` baut; dann wandert der Aufruf dorthin.
**Cache ohne Datenbank:** eine Markerdatei **`.sitzungen/.geprueft`** — ist
ihr `mtime` jünger als eine Stunde, genügt `is_dir`; sonst Probedatei, bei
Erfolg `touch`. Wechselt das Ergebnis der Probe, wechselt der Ort, und alle
Sitzungen enden dabei einmal — gewollt (kein Mischbetrieb zweier Ablagen),
und die Statusseite sagt es (E-SA-03).

**E-SA-03 — Rückfall (F-1: Haltung a).** Scheitert Anlegen oder Probe, gilt
der **Hosterpfad wie heute** — die Anwendung läuft weiter, die Statusseite
sagt es (Karte „Plattform": „Sitzungsablage: Hosterpfad, eigenes Verzeichnis
nicht anlegbar — Grund"). Kein Anhalten, kein Ausweichen nach
`sys_get_temp_dir()` (dort dasselbe Problem, nur woanders). Ein Rückfall auf
den Zustand von heute ist keine Verschlechterung; dass man ihn **sieht**, ist
die Verbesserung.

**E-SA-04 — Prüfpunkt Empfohlen (F-2).** Die Ablage wird der **vierte
Schreibort** in `plattform_pruefen()`, in der Bauform der drei bestehenden,
mit Probedatei — Stufe **Empfohlen**: Abweichung ist ein Hinweis, keine
Ampelfarbe; `install.php` verweigert nicht. Muss wäre scharf für eine
Eigenschaft, die heute keine Installation erfüllt.

**E-SA-05 — Schutz an vier Stellen, alle vier genannt.** (1) `.htaccess`:
Punktregel, gedeckt. (2) Dateirechte `0700`: die einzige Sicherung auf
nginx. (3) **Schutzliste des Transports:** `.sitzungen/` wird der **achte**
nur-auf-dem-Server-Pfad — in beiden `exclude`-Blöcken und in `CLAUDE.md` 3;
das Konzept **meldet ihn bei Kette II an** (E-KH-20, AP5 dort), trägt ihn
nicht selbst ein. Fehlt er, löscht ein künftiger Transport mit Löschabgleich
bei jedem Deploy alle Sitzungen. (4) `.gitignore`: `server/.sitzungen/`.

**E-SA-06 — Aufräumen (F-4).** Der Aufräumjob (4.97a) bekommt den Teil
`sitzungen`: löscht **nur `sess_*`-Dateien** in `.sitzungen/` (die
Markerdatei `.geprueft` bleibt; auch die Dateizahl der Statusseite zählt nur
`sess_*`), deren `mtime` älter ist als
**`SESSION_TIMEOUT_S` plus eine Stunde Karenz** — die Frist ist die, die die
Anwendung schon hat (30 min Inaktivität), nicht `gc_maxlifetime` des
Hosters (1440 s auf lima-city; auf Produktiv unbekannt). Der Job meldet die
**Zahl gelöschter Dateien** wie die übrigen Räumteile; die Statusseite zeigt
die Zahl der Dateien im Verzeichnis (Karte „Plattform"). `session.gc_probability`
setzt die Anwendung auf **0** für ihre Ablage — der Job räumt, nicht der
Zufall; ohne Job (Huckepack, PP-3) räumt der nächste Aufruf.

**E-SA-07 — Prüfpunkt „Ablage für Fremde auflistbar?"** (Empfehlung aus dem
Auftrag, Abschnitt 9 — **aufgenommen**). Unabhängig davon, ob die Anwendung
den Pfad selbst setzt, prüft `plattform_pruefen()` den **wirksamen**
`session_save_path`: Rechte des Verzeichnisses (`fileperms`), Eigentümer
gegen die eigene UID, `scandir()`-Versuch. `false` (auflistbar oder fremd
beschreibbar mit Rechten für „andere") → **rot**; `null` wo nicht
feststellbar (`open_basedir`, kein `posix_geteuid`) → „nicht feststellbar",
mit dem Satz aus 5b.1. Der Punkt bleibt auch nach einem Rückfall nach
E-SA-03 scharf — genau dann braucht man ihn.

**E-SA-08 — Nebennummer (F-5).** Kein Datenmodell, keine Migration; die
Wege durch die Anwendung sind dieselben. Dass alle Angemeldeten beim
Ausrollen einmal abgemeldet werden, ist eine Betriebsfolge, keine Wegänderung
— sie steht im Changelog, im Runbook (Technik.md 7) und im Kopfkommentar von
`version.php` mit dieser Begründung.

**E-SA-09 — Sitzungsbindung: benannt, nicht mitgenommen (F-6).** Ein
Zufallstoken nur im Cookie, dessen Hash in der Sitzung liegt, macht eine
gelesene Sitzungsdatei wertlos — auch eine aus einem gefundenen Backup. Das
ist ein Sicherheitsumbau mit eigener Prüfung (Cookie-Handling in Uhr und
Handy? nein — nur Browser; Reset-Fluss; `session_epoch`-Wechselwirkung) und
gehört nicht in einen Verzeichniswechsel: **Backlog 242, Sicherheitsrunde II**.

## 2a. Korrektur vom 20.09.2026 (Konzeptinstanz) — was an E-SA-02 nicht stimmte

| # | Was nicht stimmte | Jetzt | Die andere Wahl |
|---|---|---|---|
| K-SA-1 | Das Konzept kannte **drei** Sitzungsstarts und gab nur ihnen den Aufruf. Gemessen sind **neun** — darunter `login.php`. Anmeldung und Tor hätten in verschiedenen Ablagen gesucht | **zwei Aufrufstellen**: `db.php` (deckt acht) und `install.php` | neun einzelne Aufrufe — jeder vergessene ist eine Anmeldeschleife, und der zehnte Sitzungsstart vergisst ihn sicher |
| K-SA-2 | Der Stunden-Cache lag in `app_state` — einer Datenbanktabelle; vor dem Sitzungsstart ist die Datenbank nicht verbunden, in `install.php` gibt es sie nicht, und fällt sie aus, stürbe jede Anfrage vor der Fehlerseite | Markerdatei `.sitzungen/.geprueft` (`mtime`) | Verbindung vorziehen — nähme dem frühen Teil von `db.php` die Eigenschaft, ohne Datenbank zu antworten (vgl. `wartung_tor()`) |
| K-SA-3 | „`session_lib.php` (neu)" — die Datei besteht und lädt `db.php`; `install.php` könnte sie nicht laden | neue `sitzung_lib.php` ohne Abhängigkeiten | in `plattform_lib.php` — lüde bei jeder Anfrage die Plattformprüfung mit |

Alles Übrige (E-SA-01, -03 bis -09) ist unverändert bis auf die
Backlog-Nummern (240→241, 241→242) und den Zusatz „nur `sess_*`" in E-SA-06.

## 3. Arbeitspaket

**AP1 — Sitzungsablage** (ein Commit, Nebennummer):

- `sitzung_lib.php` (neu): `sitzung_ablage()` mit Markerdatei; Aufruf in
  `db.php` (früh, ohne Datenbank) und in `install.php` vor
  `session_start()`; `session.gc_probability = 0` für die eigene Ablage.
  Die neun `session_start()`-Stellen bleiben unberührt.
- `plattform_lib.php`: vierter Schreibort (Empfohlen) und der Prüfpunkt
  E-SA-07 (dreiwertig); Karte „Plattform" zeigt Ort, Dateizahl, Rückfall.
- Aufräumjob: Teil `sitzungen` (nur `sess_*`) mit Zählung; Katalog und Jobregister in
  Technik.md nachgezogen (das behebt nebenbei **Nr. 208**, falls noch offen).
- `.gitignore`; Anmeldung des achten Pfads bei Kette II (Kommentar im
  Konzept dort, Eintrag in `CLAUDE.md` 3 durch Kette II).
- Doku: Technik.md 5b (Prüfpunkte), 7 (Runbook: „nach dem Ausrollen sind
  alle abgemeldet"), Changelog mit Begründung der Nebennummer.

**Abnahme (Prüfdokument, Zahlen):** Statusseite zeigt den vierten
Schreibort mit Pfad, beschreibbar; `stat` nach dem ersten Lauf: `0700`,
Eigentümer = eigene UID; `https://<basis>/.sitzungen/` → **403** (Apache);
Stufe 2 der Kette unverändert grün (vier Pfade 403, `.well-known/` 404);
anmelden/abmelden/anmelden → **1 Datei entsteht, 0 nach Abmelden, 1 nach
erneutem Anmelden**; **Aufrufstellen:** `sitzung_ablage(` wird an genau
**2** Stellen gerufen (`db.php`, `install.php`), und von den Dateien mit
`session_start(` laden **8 von 9** `db.php` davor, die neunte ist
`install.php` (Zählung im Prüfdokument, als Selbstprobe wiederholbar);
**alle Wege einmal gegangen:** Anmeldung über `login.php` bis zur
Tagesübersicht ohne Schleife, Passwort-Reset (`pw_handling.php`), Abmelden
(`session_beenden()`), Handbuch/Rechtstext/Notfallblatt angemeldet mit
angemeldetem Kopf, `install.php` und `wiederherstellen.php` im Prüfstand;
Markerdatei: zweite Anfrage innerhalb einer Stunde schreibt **0**
Probedateien, mit gealtertem Marker **1**; CLI-Lauf (`jobs.php`) legt das
Verzeichnis **nicht** an; Aufräumjob mit einer künstlich gealterten Datei →
„1 gelöscht"; zwei Deploys hintereinander → Sitzung überlebt beide (Beleg,
dass der Transport das Verzeichnis nicht anfasst — **erst nach Kette II
AP5** prüfbar, bis dahin als offen geführt); `grep` auf die Schutzliste: der
achte Pfad in beiden Blöcken und in `.gitignore`; Prüfpunkt E-SA-07 auf
Staging: `null` oder `true` mit Zahl (Rechte); Wortliste, Kettenaufrufe,
Vollständigkeit wie immer.

**Nicht prüfbar, und so gesagt:** nginx (keine Anlage dieses Projekts) und
„Verzeichnis nicht anlegbar" auf einer echten Anlage — nachgestellt im
Prüfstand mit entzogenem Schreibrecht; beides in Abschnitt 0 des
Prüfdokuments.

## 4. Einschub Rahmenplan und Backlog

Im Einschub vom 20.09.2026 (`Rahmenplan-Einschub-2026-09-20.md`): Schritt 16
mit diesem Konzept; Backlog **241** (Sitzungsablage, Befund mit Zahlen,
Verweis hierher) und **242** (Sitzungsbindung per Cookie-Token,
Sicherheitsrunde II). Keine neue R-Nummer: R81 trägt die Begründung.

---

## 5. Umsetzung (Claude Code, 20.09.2026, Web 20.26.0)

**Zweig** `claude/new-session-cbq57h`, von `origin/main` `862ca7f` (Web
20.25.0, gemessen und nicht abgeschrieben). Ein Arbeitspaket, ein Commit.

### 5.1 Was gebaut wurde

| Datei | Was |
|---|---|
| `server/sitzung_lib.php` (neu) | `sitzung_ablage()` mit Markerdatei, `sitzung_aufraeumen()`, `sitzung_dateien_zahlen()`, `sitzung_wirksamer_pfad()`, `sitzung_cli()`; trägt `SESSION_TIMEOUT_S`, `SITZUNG_KARENZ_S`, `SITZUNG_MARKER_S`. **Lädt nichts** |
| `server/db.php` | Aufruf hinter `wartung_tor()` |
| `server/install.php` | Aufruf **vor** `session_set_cookie_params()` |
| `server/auth_guard.php` | `SESSION_TIMEOUT_S` entfällt hier (U-3) |
| `server/email_lib.php` | Einrückung von Zeile 128 berichtigt, Warnkommentar |
| `server/plattform_lib.php` | vierter Schreibort (Empfohlen) und Punkt „Sitzungsablage" (Muss, dreiwertig); `$cli` einmal statt zweimal |
| `server/jobs_lib.php` | Räumteil „Sitzungsdateien"; `job_aufraeumen_schritte()` ausgelagert; Beschreibung erzeugt; drei Schlüssel mit ASCII-Umschrift berichtigt |
| `server/jobs.php` | `· N gelöscht` in der Laufzeile |
| `tools/wartungsprobe/` | `sitzung_ort()` (U-2), `LIESMICH.md` nachgezogen |
| `tools/jobregister/` (neu) | Register gegen Code, Tokenizer, ohne Installation (U-4) |
| `.gitignore` | `server/.sitzungen/` |
| Doku | `version.php`, `CHANGELOG.md`, `Technik.md` (2, 4.97a, 4.98-Bedrohungsmodell, 5b, 6.2, 6.5, 7), `Backlog.md` (208 nach Erledigt) |

### 5.2 Abweichungen vom Konzept, jede mit Grund

1. **`sitzung_cli()` statt `PHP_SAPI === 'cli'`** (U-6). Das Konzept nennt die
   engere Form. Eine Zeile unter dem neuen Aufruf steht `wartung_tor()`, und
   `wartung_cli()` nimmt `phpdbg` mit. Zwei Tore nebeneinander in derselben
   Datei mit verschiedener Vorstellung davon, was „Kommandozeile" heißt, sind
   ein Widerspruch, der beim ersten `phpdbg`-Lauf auffällt.
2. **`SESSION_TIMEOUT_S` ist mitgewandert** (U-3). Das Konzept sagt dazu
   nichts; ohne den Umzug wäre der Räumteil auf der Kommandozeile an
   `Undefined constant` gestorben und am Huckepack-Weg durchgelaufen.
3. **`tools/wartungsprobe/` wurde mitgezogen** (U-2). Ohne das liefert dieses
   Paket ein Prüfmittel aus, das mit „nicht angemeldet" scheitert und dabei
   aussieht wie ein Fehler der Anwendung.
4. **Nr. 208 ist an der Ursache behoben, nicht durch einen Nachzug** (U-4).
   Das Konzept nimmt an, ein Nachziehen von Katalog und Register erledige den
   Punkt. Es tut es nicht: Genau das ist zweimal geschehen, und zweimal wuchs
   der Abstand wieder. Entschieden vom Auftraggeber am 20.09.2026.
5. **Der Räumteil heißt „Sitzungsdateien"** und meldet über den neuen
   Berichtsschlüssel `geloescht_dateien`. Das Konzept sagt „wie die übrigen
   Räumteile" — die melden aber gar keine Zahl; `geloescht` gibt es bereits
   und heißt „auf einem fremden Ziel entfernt".
6. **Der vierte Schreibort ist im guten Fall unsichtbar** (U-7). Das Konzept
   fordert in der Abnahme „Statusseite zeigt den vierten Schreibort mit Pfad,
   beschreibbar"; `status_lib.php` zeigt erfüllte Empfehlungen nicht einzeln.
   Ort, Rechte und Dateizahl trägt deshalb der Muss-Punkt daneben — er steht
   immer da, auch im Rückfall, und das ist die Lage, auf die es ankommt.

### 5.3 Was dabei aufgefallen ist und nicht im Auftrag stand

- **Der realpath-Cache** (U-5): Ein Rückfall, den es nicht gab. Gefunden nur,
  weil der Prüfstand den Übergang „keine Ablage → Ablage" wirklich gefahren
  ist statt ihn anzunehmen.
- **`tools/wegwerfdomains/` fehlte im Werkzeugbaum** seit Web 20.22.0 (46 im
  Baum, 48 auf der Platte) — derselbe Fehler, den Nr. 208 für
  `tools/containerprobe/` beschreibt.
- **Die Ausnahmeliste in `Technik.md` 6.5** nannte fünf Einträge, die Kette
  führt sieben (`ueberlast.json`, `install.php`).
- **Der Transport löscht `.sitzungen/` heute gar nicht** — nachgemessen am
  20.09.2026, nachdem die Frage im Prüfdokument zunächst als „nicht messbar
  bis Kette II" geführt war. Sie war messbar, nur nicht an der Anlage:
  `getServerFiles()` in `@samkirkland/ftp-deploy` listet das Fernverzeichnis
  nie, sondern liest ausschließlich die eigene Zustandsdatei, und
  `HashDiff.getDiffs()` kann deshalb nur löschen, was dort steht. Ein zur
  Laufzeit auf dem Server entstandener Ordner stand dort nie. Gelesen in
  1.2.3, 1.2.4 und 1.2.5 — `HashDiff.js` und `deploy.js` in allen drei
  Fassungen zeichengleich. **Folge:** Fünf Sätze in `.gitignore`,
  `Technik.md` 6.5 und 7, `CHANGELOG.md` und dem Prüfdokument behaupteten
  mehr, als belegt war („löscht bei jedem Deploy alle Sitzungen"); sie sind
  berichtigt. Der achte Schutzlistenpfad bleibt richtig, aber aus einem
  anderen Grund: Läge `.sitzungen/` einmal im Auscheckstand, wäre er ab da
  in der Zustandsdatei und damit löschbar. Gegen `dangerous-clean-slate`
  schützt er ohnehin nicht.
- **Zwei echte `session_start()` in `server/vendor/`**
  (`phpseclib3/Crypt/Random.php`). Toter Rückfallpfad auf PHP ≥ 8.2. Die Zahl
  **neun** gilt für „`server/` ohne `vendor/`" — wer sie ohne diesen Zusatz
  zitiert, zitiert eine Zahl mit stillschweigender Bedingung.

### 5.4 Was offen bleibt

Vollständig mit Bedienweg im Prüfdokument. Kurz: alles, was **Staging oder
einen Browser** braucht (`/.sitzungen/` → 403, Stufe 2, Prüfpunkt E-SA-07 auf
einer echten Anlage, die Anmeldewege), und alles, was **an Kette II hängt**
(achter Schutzlistenpfad, der Eintrag von `tools/jobregister/` in Stufe 1,
und damit der Abnahmepunkt „zwei Deploys hintereinander").
