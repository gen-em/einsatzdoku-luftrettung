# Vorbereitung P5c — Protokollierung („Log")

**Angelegt:** 16.09.2026, während P5a/AP5, auf Anweisung des Auftraggebers.
**Stand:** 16.09.2026 — **V1 entschieden** (gehalten, Abschnitt 5); V2–V9
offen. Der Schreibweg (Tabelle, `protokoll()`, Ereignisformat) wird nach
Rahmenplan Fassung 74 im **10b-Konzept** als erstes Paket festgelegt, weil
10b vor 10c läuft und dessen Verwaltungsereignisse erzeugt; 10c baut
Reiter, Archiv und Download darauf. Der Log-Helfer aus der
Zentralisierungsanalyse (Schritt 15) ist derselbe Schreibweg und wird
nicht doppelt gebaut.
**Zweck:** Dieses Dokument ist **vor** dem P5c-Konzept zu lesen und **zu
diskutieren**. Es ist kein Konzept: Es sammelt den gemessenen Befund, den
Auftrag, die offenen Fragen und die Grenzen, die nicht verhandelbar sind.
**Zuordnung:** Schritt 10c (P5c — Rollen, Sicherheit, Betriebslage), R38, R82.

> **Warum das vor das Konzept gehört.** Die Bestandsaufnahme vom 16.09.2026
> hat ergeben, dass diese Anwendung **praktisch kein Protokoll führt** — und
> dass das zur Hälfte Absicht ist. Ein Konzept, das die Absicht nicht kennt,
> baut gegen eine schriftlich gegebene Zusage. Die Grenzen in Abschnitt 4
> sind deshalb zuerst zu lesen.

---

## 1. Der Auftrag (Auftraggeber, 16.09.2026)

Wörtlich, damit nichts verlorengeht:

> Ich hätte gerne einen Unterpunkt irgendwo im Adminbereich der „Log" heißt,
> wo einerseits einfach alles geschrieben steht, wo allerdings auch mit einem
> Reiter oben gefiltert werden kann nach verschiedenen Log-Events (Email,
> Server, FTP, Backup bzw. was halt Sinn macht zu unterscheiden). Es muss dann
> ein mit Serverschlüssel verschlüsseltes Log-File erstellt werden, was alle X
> Tage dann ZIP-komprimiert wird und für X-Lange aufbewahrt wird bzw. lesbar
> ist (einstellbar in den Einstellungen). Unverschlüsselter Download dann aus
> dem Admin-Panel.

Dazu die Vorgabe zu IP-Adressen:

> Wäre mein Favorit nichts zu loggen mit IPs was nicht auf Bans/Angriffe
> zurückzuführen ist. Sperren etc. sollten schon mit IP geloggt werden.

---

## 2. Was heute da ist — gemessen am 16.09.2026

**Zustände, keine Verläufe.** Vier Sichten, je mit Fundstelle.

### 2.1 Zugang

| Was | Wo | Form | Dauer | Einsehbar |
|---|---|---|---|---|
| Letzte Anmeldung | `users.last_login`, geschrieben in `login.php:266` | **ein** überschriebener Zeitstempel, **keine IP, kein Gerät** | bis zur nächsten Anmeldung | ja — als **Datum ohne Uhrzeit** (`admin_users.php:628`) |
| Fehlversuche | `rate_limits` | **Zähler**, kein Ereignis; `ip:…` und `id:…` als **getrennte, unverknüpfte** Zeilen | bei Erfolg sofort gelöscht, sonst ≤ 1 Tag (`jobs_lib.php:522`) | **gar nicht** |
| Passwortwechsel | `users.session_epoch` | eine Zahl, die hochzählt — ohne Zeit, ohne Urheber | dauerhaft | nein |
| Reset angefordert/benutzt | `password_resets` | `used_at`; **kein** `created_at`, kein Auslöser | eingelöst → beim nächsten Aufräumlauf **weg** | nein |

**Ein Rollenwechsel, ein Adresswechsel, eine Abmeldung und eine Gerätelöschung
hinterlassen gar nichts.** Eine Kontosperre gibt es nicht — ein Konto wird
gelöscht (`admin_user.php:433`, hartes `DELETE`) oder es bleibt.

### 2.2 Handlungen

**In 36 Tabellen gibt es keine einzige Spalte, die festhält, WER etwas getan
hat.** Kein `geloescht_von`, kein `bearbeitet_von`, kein `created_by`;
`user_id` bedeutet überall Eigentum, nie Urheberschaft.

Die drei, die am meisten wehtun:

1. **Das Einspielen eines Backups wird nicht vermerkt.**
   `edbak_paket_zurueckspielen()` (`adminbackup_lib.php:2406`) ersetzt den
   Datenbestand eines Kontos und schreibt **keine Marke** — weder in
   `app_state` noch in `konto.json`. Danach ist nicht feststellbar, dass der
   heutige Bestand aus einem Paket vom Vormonat stammt. Das ist der
   eingreifendste Vorgang, den diese Anwendung kennt.
2. **Ein Rollenwechsel überschreibt.** `UPDATE users SET role = ?`
   (`admin_user.php:150`) — die alte Rolle ist fort. Dass sich jemand zur
   BetreiberIn hochstufen ließ, ist nachträglich nicht feststellbar.
3. **Endgültiges Löschen löscht auch seinen eigenen Beleg.**
   `trash_purge_mission()` entfernt die Zeile samt `deleted_at`.

### 2.3 Fehler

- **42 `error_log()`-Aufrufe in 21 Dateien.** Sie gehen dorthin, wohin die
  `error_log`-Direktive des Hosters zeigt. Die Anwendung setzt **weder**
  `error_log` **noch** `log_errors` **noch** `display_errors` — nicht per
  `ini_set()`, nicht in `.htaccess`, nicht in `config.example.php`.
- **Es gibt keinen globalen `set_exception_handler()`/`set_error_handler()`.**
  Eine Ausnahme außerhalb eines `try` (etwa das einzige `new PDO` ohne catch,
  `db.php:15`) erzeugt keine Anwendungsreaktion: Die Nutzerin sieht, was die
  PHP-Konfiguration des Hosters hergibt — leere Seite oder vollständiger
  Stacktrace mit Dateipfaden.
- **Keine Web-Sicht.** `fehler_kennung()` (`db.php:534`) zeigt eine
  achtstellige Kennung und den Satz „Er steht im Fehlerprotokoll des
  Webspace." Die Anwendung kann dieses Protokoll **weder lesen noch
  durchsuchen**.
- **Keine Historie, nur je ein letzter Fehler.** `jobs.letzter_fehler` und
  `backup_targets.letzter_fehler` werden beim nächsten Erfolg auf `NULL`
  gesetzt. Ein Fehler, der jede zweite Nacht auftritt, ist am Morgen danach
  nicht mehr da.
- **Steht beim Hoster `log_errors=Off`, gehen alle 42 Meldungen lautlos
  verloren**, und nichts in der Anwendung zeigt das an.

### 2.4 Was schon geplant ist

- **R38** (30.08.2026) verspricht bereits: Audit-Protokoll administrativer
  Handlungen, **Admin-Sicht aufs Fehlerprotokoll (Suche nach Kennung)**,
  Health-Endpunkt.
- **R82** ordnet das Schritt **10c** zu, nach 10b.
- **E-S8-12** legt den Ort fest: Betrieb → Status (Health, Fehlerprotokoll);
  Audit als eigene Seite oder Unterseite von Status.
- **PP-3** verlangt: Jeder Job läuft auf dem Huckepack-Weg und hält dessen
  Zeitbudget — auch die **Audit-Rotation**. Kein Cron, keine Shell.
- **E-P5a-09** hat die Frist für Betriebsdaten ohne Kontobezug schon
  entschieden: **30 Tage, keine Einstellung.** Für Fehlerprotokoll und Audit
  steht dort ausdrücklich: „entscheidet 10c (Audit länger)."
- **P5a baut den Platz daneben:** Die Sicherheitssicht (AP8) bringt aktive
  Sperren, Sperrereignisse der letzten 30 Tage, Verlangsamungen und Treffer
  der Mengenbremse. **10c hängt das Audit daneben.**

---

## 3. Was daraus folgt — Vorschläge zur Diskussion

### 3.1 Der Name „Log" ist zu weit, und das ist gefährlich — **angenommen 16.09.2026 (V1)**

„Wo einfach alles geschrieben steht" kollidiert frontal mit einer **schriftlich
gegebenen Zusage** (Abschnitt 4.1). Vorschlag: Der Bereich heißt **„Protokoll"**
und führt **Betriebsereignisse**, nicht Datenzugriffe. Die Reiter machen die
Grenze sichtbar, statt sie zu verstecken.

### 3.2 Vorschlag für die Reiter

| Reiter | Was hinein gehört | IP? |
|---|---|---|
| **Sicherheit** | Sperren, Entsperren, Fehlversuchsserien, Mengenbremse, CSP-Verstöße | **ja** — der einzige Reiter mit IP |
| **E-Mail** | eingereiht, zugestellt, gescheitert, unzustellbar (Empfänger, Grund) | nein |
| **Jobs** | Lauf, Dauer, Ergebnis, Fehler — **mit Historie**, nicht nur der letzte | nein |
| **Sicherung** | Paket erstellt, verdrängt, versendet, **eingespielt**, Freigabe erteilt/widerrufen | nein |
| **Ziele (FTP/SFTP)** | Verbindung, Übertragung, Löschung drüben | Zieladresse, nicht Client-IP |
| **Verwaltung** | Konto angelegt/gelöscht, Rolle geändert, Adresse geändert, Demo zurückgesetzt, Wartung an/aus, Migration ausgeführt | nein |
| **System** | Fehlerkennungen mit Text, Plattformbefunde | nein |

### 3.3 Drei Dinge, die es fast geschenkt gibt

1. **`jobs.letzter_fehler` bekommt eine Historie** statt überschrieben zu
   werden. *(Auftraggeber 16.09.2026: „Ja, angehen." — wird in P5a/AP5
   angefasst, weil dort ein neuer Job entsteht.)*
2. **`log_errors`/`error_log` als Prüfpunkt im Plattformprofil.**
   **Wichtig, und ein Widerspruch zur Annahme „erledigt sich mit dem
   zentralen Log":** Ein zentraler Log kann einen **PHP-Fatal nicht fangen** —
   bei `memory_limit`, `max_execution_time` oder einem Parse Error stirbt der
   Prozess, bevor Anwendungscode läuft. Das sieht nur das Protokoll des
   Hosters. **Beides wird gebraucht.**
3. **Ein globaler `set_exception_handler()`**, damit eine unbehandelte
   Ausnahme eine Anwendungsreaktion erzeugt statt der Hoster-Vorgabe.

---

## 4. Grenzen, die nicht verhandelbar sind

### 4.1 Die Zusage „kein Zugriffsprotokoll"

Zweimal schriftlich, wortgleich in `server/schema.sql:675` und
`docs/Technik.md:6673`:

> „Diese Anwendung führt kein Protokoll darüber, wer wann welchen Einsatz
> geöffnet hat."

**Ein Protokoll, das „alles" schreibt, bricht diesen Satz.** Lesen,
Exportieren und Herunterladen bleiben ungeloggt — oder die Zusage wird
ausdrücklich zurückgenommen, mit Datum, Begründung und Nachtrag in der
Datenschutzerklärung. Beides ist vertretbar; **stillschweigend darüber
hinweggehen ist es nicht.**

### 4.2 R36 „Keine Telemetrie"

> „Betriebszahlen ausschließlich aus vorhandenen Spalten (R38), **es wird
> nichts Neues erfasst**."

Ein Protokoll ist die **zweite** benannte Ausnahme (die erste war die
Gerätekennung, R42). Sie braucht eine eigene Begründung im Konzept.

### 4.3 Die Verschlüsselung hat einen schmalen Wirkungsbereich

Der Serverschlüssel liegt in `config.php` **neben** dem Log. Wer den Server
hat, hat beides. Verschlüsselung schützt gegen das **Komplettbackup, das das
Haus verlässt**, gegen Mitleser auf geteiltem Webspace und gegen eine einzeln
abgegriffene Datei — **nicht** gegen den, der schon drin ist.

**Zwei Fallstricke, die ins Konzept gehören:**

- **Ein Wechsel des Serverschlüssels macht alte Logs unlesbar.** Das Projekt
  kennt das Problem bereits von `kdf_anteil_alt`; für Logs braucht es eine
  eigene Antwort (mitrotieren, oder Frist ≤ Rotationsabstand, oder Schlüssel
  je Datei und der alte bleibt im Wiederanlaufpaket).
- **Ein Log, das man nach einem Einbruch lesen will, war für den Angreifer
  genauso lesbar.** Wer Manipulationssicherheit will, braucht etwas anderes
  als Verschlüsselung — Anhängen ohne Ändern, Prüfsummenkette, oder ein Ziel
  außer Haus.

### 4.4 Der unverschlüsselte Download ist selbst ein Datenabfluss

Eine heruntergeladene Klartextdatei mit E-Mail-Adressen und IPs ist ein
**Datenexport**. Vorschlag: nur **BetreiberIn**, und **der Download steht im
Protokoll**. Sonst ist ausgerechnet der Weg, der die meisten Daten auf einmal
bewegt, der einzige unprotokollierte.

---

## 5. Zu klären — vor dem Konzept

| # | Frage | Warum sie jetzt beantwortet werden muss |
|---|---|---|
| **V1** | **Wird die Zusage „kein Zugriffsprotokoll" gehalten oder zurückgenommen?** — **Entschieden 16.09.2026 (Auftraggeber): gehalten.** Der Bereich heißt **Protokoll** und führt **Betriebsereignisse**, keine Datenzugriffe (3.1). Lesen, Exportieren, Herunterladen von Einsätzen bleiben ungeloggt; Handlungen — Backup eingespielt, Rolle oder Adresse geändert, Löschungen, der Download des Protokolls selbst — werden protokolliert. |
| **V2** | **IP nur bei Sperren/Angriffen — bestätigt?** Und mit welcher Frist? | Vorgabe des Auftraggebers ist Datenminimierung und damit der verteidigungsfähige Entwurf. **Die Fristlänge braucht juristische Bestätigung, keine technische.** R41 verlangt die Speicherdauer ohnehin in der Datenschutzerklärung. |
| **V3** | **Wie lange werden Protokolle aufbewahrt, je Reiter?** | E-P5a-09 hat 30 Tage für Betriebsdaten **fest** entschieden. „Einstellbar" widerspräche dem — außer für das Audit, für das dort „länger" steht. Die Einstellbarkeit ist also auf einen Teil zu begrenzen. |
| **V4** | **Was passiert mit alten Logs beim Wechsel des Serverschlüssels?** | Sonst sind sie nach der ersten Rotation stumm. |
| **V5** | **Geht das Protokoll ins Komplettbackup?** | `komplett_lib.php` nimmt neue Tabellen **automatisch** mit. Wenn ja, verlässt es verschlüsselt das Haus — wenn nein, muss es ausdrücklich ausgenommen werden. |
| **V6** | **Datei oder Tabelle?** | Der Auftrag sagt „Log-File", ZIP-komprimiert. Eine Tabelle ist leichter zu filtern und zu löschen, eine Datei leichter zu rotieren und zu versiegeln. Ein Mischweg (Tabelle für die letzten X Tage, versiegelte ZIP-Datei fürs Archiv) ist wahrscheinlich richtig — das ist zu entscheiden, nicht anzunehmen. |
| **V7** | **Was schreibt in das Protokoll — und was passiert, wenn das Schreiben scheitert?** | Ein Protokoll, dessen Fehlschlag die Handlung abbricht, ist ein Ausfallrisiko. Eines, das still scheitert, ist wertlos. |
| **V8** | **Wer darf es sehen?** Admin oder nur BetreiberIn? | Das Audit „schützt auch die Admins selbst" (R38) — dann darf ein Admin es nicht löschen können. |
| **V9** | **Wird der Reiter je Ereignisart gefüllt, oder gibt es Ereignisse ohne Reiter?** | Ein „Sonstiges" sammelt erfahrungsgemäß alles und wird nie gelesen. |

---

## 6. Was in P5a schon passiert und hier nicht doppelt gebaut werden darf

- **AP5** baut `mail_warteschlange` mit Empfänger, Betreff, Versuchen, Fehler
  und Zustand, 30 Tage, sichtbar als Liste „Letzte Mails" auf der Statusseite.
  **Das ist faktisch der erste Protokoll-Reiter** — P5c sollte ihn übernehmen,
  nicht neu erfinden.
- **AP6** erweitert `rate_limits` um Stufen und legt `sicherheit_ereignisse`
  an (30 Tage). **Das ist der Reiter „Sicherheit", inklusive IP.**
- **AP8** baut Betrieb → Status → **Sicherheit** als Unterseite mit aktiven
  Sperren, Sperrereignissen, Verlangsamungen und Bremse-Treffern.
- **AP10** bringt das Lösch-Protokoll der Sicherungsziele (E-P5a-03: „und ab
  10c im Audit").
- **AP4** hat `csp_berichte` gebaut — ausdrücklich als Platzhalter, „bis 10c
  es baut".

**Daraus folgt der wichtigste Satz dieses Dokuments:** P5c erfindet das
Protokoll nicht, sondern **fasst zusammen, was P5a verstreut angelegt hat** —
und ergänzt, was fehlt (Verwaltungshandlungen, Fehlerhistorie, Archiv,
Download). Ein Konzept, das bei null anfängt, baut vier Dinge zweimal.

---

## 7. Vorschlag für die Reihenfolge

1. ~~**V1 und V2 entscheiden** (Zusage, IP) — alles andere hängt daran.~~
   **V1 ist entschieden** (gehalten); **V2** (IP nur bei Sperren; Frist)
   steht noch aus.
2. Bestand aus P5a sichten (Abschnitt 6) und festlegen, was übernommen wird.
3. Datenmodell: ein Ereignisformat für alle Reiter, oder je Reiter eines.
4. Archivweg: Rotation, Versiegelung, Frist, Schlüsselwechsel (V4).
5. Oberfläche: Unterseite mit Reitern; **Mockup nötig**, weil die Reiterleiste
   ein neuer Baustein wäre (`CLAUDE.md` 5, `Design.md` Kapitel 9 prüfen —
   gibt es sie schon?).
6. Download und Rechte (V8), Protokollierung des Downloads selbst (4.4).
7. Datenschutztext-Nachtrag — **R41 verlangt ihn ohnehin**, und die
   Datenschutzerklärung ist laut Rahmenplan bereits **überfällig**.
