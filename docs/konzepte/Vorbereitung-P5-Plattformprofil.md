# Vorbereitung P5 — Plattformprofil (Hosting-Entscheidung, R36)

**Zielpfad:** `docs/konzepte/Vorbereitung-P5-Plattformprofil.md` — dasselbe
Muster wie `Vorbereitung-S9-Problemsammlung.md`: eine Sammlung mit den
bereits gefallenen Entscheidungen, aus der das P5-Konzept nach K1 entsteht.
Dieses Dokument beantwortet **eine** der Zuarbeiten, die der Rahmenplan
(Abschnitt 6) „vor Schritt 10" verlangt: die **Hosting-Entscheidung** nach
R36. Die zweite, das **Staging-Ziel** (R67), ist am selben Tag gefallen und
steht in PP-9 (E-PP-09).
**Rahmenplan:** Schritt 10 (P5 Dienstbetrieb), R36, R67, R72; Backlog Nr. 8,
80, 141.
**Status:** Vorbereitung. Geht als Abschnitt „Plattformprofil" in das
P5-Konzept ein und wandert mit R72 in das Betreiberhandbuch („Installation
und Selbsthosting"). Bleibt nach R62 als Herkunft liegen.
**Stand:** 15.09.2026, zweite Runde (F-PP-2 entschieden, Staging-Ziel
eingetragen).
**Modell:** Fable (Konzept, R14). Kein Code.

**Bezeichner:** Die neun Eckdaten heißen **PP-1 bis PP-9**. Die
Festlegungen darin heißen **E-PP-nn** und gehen unverändert in das
P5-Konzept ein (dort als E-P5-… fortgeführt). Fragen heißen **F-PP-n** und
stehen gesammelt in Abschnitt 4 — mit Entscheidung, wo sie gefallen ist.

---

## 0. Die Entscheidung und ihr Rahmen

**Hosting-Entscheidung (Auftraggeber, 15.09.2026):** Der Dienstbetrieb
bleibt beim jetzigen Hoster (Plesk, geteilter Webspace). **Die Anwendung
wird trotzdem nicht auf diesen Hoster zugeschnitten** — sie muss auf einer
anderen Plattform genauso laufen. Die Anforderungen an die Plattform werden
deshalb **hosterneutral** als Profil festgelegt, nicht als Beschreibung des
heutigen Tarifs.

Das ist keine neue Linie, sondern die aus R36 („geteilter Webspace nach Z2
bleibt die Untergrenze, die die Anwendung tragen muss") und aus S2, dessen
Zielmaße weitergelten:

- **Z2** — 500 Konten × 600 Einsätze je Installation; geteilter Webspace,
  **10 GB MySQL-Kontingent**, PHP-Weblimits (`post_max_size`,
  `max_execution_time`) gelten.
- **Z3** — je Serveranfrage: POST ≤ 2 MB, Laufzeit ≤ 30 s,
  PHP-Speicherspitze ≤ 64 MB.

### E-PP-01 — Zwei Stufen

Das Plattformprofil kennt genau zwei Stufen:

| Stufe | Bedeutung | Was die Anwendung daraus macht |
|---|---|---|
| **Muss** | Was jede Installation bieten muss. Fehlt ein Muss, läuft die Anwendung nicht — und sagt es. | `install.php` prüft es **vor** der Einrichtung; die Statusseite (Betrieb → Status) prüft es im Betrieb und zeigt **rot**, wenn es nachträglich wegfällt. |
| **Empfohlen** | Was die Anwendung nutzt, wenn es da ist. Fehlt ein Empfohlen, läuft die Anwendung vollständig — langsamer, mit Verzögerung oder mit einem Handgriff mehr. | Die Statusseite zeigt eine Abweichung als **Hinweis** (nicht rot, nicht orange) mit dem Satz, was fehlt und was dadurch anders läuft. |

Es gibt keine dritte Stufe. Was weder Muss noch Empfohlen ist, wird
**nicht vorausgesetzt** (PP-8) und kommt im Betreiberhandbuch als
Empfehlung an den Betreiber vor, nicht als Anforderung an die Plattform.

### E-PP-02 — Jede P5-Funktion nennt ihre Stufe und ihren Rückfall

Jedes Arbeitspaket des P5-Konzepts, das etwas von der Plattform braucht,
sagt im Konzept: **welche Stufe** es voraussetzt und **wie es auf Muss
läuft**, wenn es Empfohlen braucht. Eine Funktion, die nur auf Empfohlen
läuft und auf Muss stumm bleibt, ist ein Fehler — sie muss auf Muss
entweder laufen oder sichtbar sagen, dass sie nicht läuft.

### E-PP-03 — Die Regel ist dauerhaft, die Zahl ist ein Stand

Für Versionen (PHP, Datenbank) gilt die **Regel**: eine Fassung, die vom
Hersteller noch mit Sicherheitskorrekturen versorgt wird. Die **Zahlen** in
diesem Dokument sind der Stand vom 15.09.2026 und werden bei der Umsetzung
und danach mit jeder Doku-Neufassung (R72) nachgeprüft. `install.php` prüft
die Zahl, das Betreiberhandbuch nennt die Regel — und die Zahl steht an
**einer** Stelle im Code (Konstante), nicht in drei Meldungstexten.

### E-PP-04 — Was das Profil nicht ist

Kein Leistungsversprechen für einen bestimmten Tarif und keine
Anleitung für Plesk. Wo der jetzige Hoster mehr kann als das Profil
verlangt, nutzt die Anwendung das über die Empfohlen-Stufe — und nur so.
Ein Hosterwechsel darf im Code keine Zeile ändern, nur in `config.php`.

---

## 1. Die neun Eckdaten

### PP-1 — PHP

**Befund:** `docs/Technik.md` nennt „PHP ≥ 8.1". Der Code braucht
nachweislich 8.1 (`array_is_list()` in `validate_lib.php` und
`mission_fields_lib.php`, Rückgabetyp `never` an 15 Stellen). `install.php`
prüft seit S2 vier Erweiterungen (`zip`, `zlib`, `openssl`, `mbstring`),
aber **keine PHP-Version** — eine Installation auf PHP 8.0 fällt erst beim
ersten Formular mit einem Fatal Error auf. PHP 8.1 erhält seit Ende 2025
keine Sicherheitskorrekturen mehr (Stand 15.09.2026; nachprüfen).

**Muss**
- PHP **≥ 8.2**. Die Untergrenze steigt von 8.1 auf 8.2, nicht weil der
  Code es braucht, sondern weil 8.1 aus der Herstellerpflege ist
  (E-PP-03). Der Code bleibt bei 8.1-Merkmalen — er soll die Untergrenze
  nicht von selbst weiterschieben.
- Erweiterungen: `pdo_mysql`, `openssl`, `mbstring`, `zip`, `zlib` (die
  vier bisherigen plus `pdo_mysql`, das bisher stillschweigend vorausgesetzt
  war). `json`, `ctype`, `session`, `hash` sind seit PHP 8 fest eingebaut und
  werden nicht geprüft.
- Weblimits nach Z3: `memory_limit` ≥ 64 MB, `max_execution_time` ≥ 30 s,
  `post_max_size` und `upload_max_filesize` ≥ 2 MB. `install.php` liest die
  vier Werte und nennt jeden, der darunter liegt, **mit dem Namen der
  Einstellung** — nicht nur „zu klein".
- `install.php` prüft die Version **als Erstes**, vor den Erweiterungen:
  Auf einem zu alten PHP kann die Prüfdatei selbst schon nicht laufen, wenn
  sie 8.1-Syntax enthält. Die Versionsprüfung steht deshalb in einer
  Zeile, die jedes PHP 7 noch parst.

**Empfohlen**
- PHP **≥ 8.3** (aktive Pflege, nicht nur Sicherheitskorrekturen).
- **OPcache aktiv.** Die Anwendung ist darauf vorbereitet
  (`opcache_invalidate()` nach jedem Schreiben in `config.php`,
  `serverkrypto_lib.php`); ohne OPcache läuft sie, jede Anfrage übersetzt
  dann alles neu.

### PP-2 — Datenbank

**Befund:** Das Schema ist InnoDB mit `utf8mb4`; der Code benutzt weder
Fensterfunktionen noch CTEs noch `RETURNING` noch `CHECK`. Technisch läuft
das ab MySQL 5.7.7 / MariaDB 10.2.2 (große Index-Präfixe). Die Untergrenze
bestimmt deshalb die Herstellerpflege, nicht ein Merkmal (E-PP-03). Zu
`max_user_connections` gibt es im Code **keine** Behandlung: Ein „Too many
connections" endet heute in der allgemeinen Fehlerseite.

**Muss**
- **MySQL ≥ 8.0 oder MariaDB ≥ 10.6**, InnoDB, `utf8mb4`. `install.php`
  liest `SELECT VERSION()` und erkennt MariaDB an der Zeichenkette.
- **Kontingent 10 GB** (Z2). Die Anwendung misst ihre Belegung
  (`speicher_lib.php`) und warnt mit **denselben Schwellen wie das
  Webspace-Kontingent (70/90, `webspace_gb`)** — das DB-Kontingent wird eine
  zweite **Einstellung** `db_gb` daneben, weil kein Hoster es abfragbar
  macht; Vorgabe 10 GB. *(Berichtigt 15.09.2026 beim Nachmessen für das
  P5a-Konzept, E-P5a-11: zuerst stand hier „ab 80 %" ohne Bezug auf die
  vorhandene Webspace-Warnung.)*
- **`max_user_connections` ≥ 10.** Die Anwendung hält sich daran:
  **genau eine** Verbindung je Anfrage, keine persistenten Verbindungen
  (`PDO::ATTR_PERSISTENT` bleibt aus), der Huckepack-Job läuft auf der
  Verbindung der Anfrage, die ihn trägt, nicht auf einer zweiten.
- **Verhalten an der Grenze** (neu, P5): Ein Verbindungsfehler der Klassen
  „zu viele Verbindungen" (MySQL 1040/1203) antwortet mit **HTTP 503**,
  `Retry-After: 5` und der Wartungsseite ohne Stacktrace — bei API-Aufrufen
  (`ingest.php`, Uhr und Handy) als JSON, damit der Client seine
  Warteschlange behält und später sendet. Die Statusseite zählt diese
  Antworten (Zähler in `app_state`) und zeigt sie als Hinweis; ab
  **10 je Stunde** orange.
- Der **Messstand** (S2) fährt einmal mit `max_user_connections = 10` gegen
  Z2-Last und weist nach, dass der 503-Weg trägt und nichts verloren geht.

**Empfohlen**
- Eine **LTS-Fassung** in Herstellerpflege (Stand 15.09.2026: MySQL 8.4,
  MariaDB 10.11 oder 11.4).
- `max_user_connections` **≥ 50**.
- Datenbank auf **demselben Rechner oder im selben Rechenzentrum** wie
  PHP: Die Ansichten machen viele kleine Abfragen; 1 ms Latenz je Abfrage
  ist unsichtbar, 20 ms sind es nicht.

### PP-3 — Jobs

**Befund:** Es gibt drei Wege, die Wartung anzustoßen: **Huckepack**
(`run_cleanup_if_due` in `db.php`, auf einer normalen Anfrage, mit
Zeitbudget), **Cron** (`php jobs.php`, Kommandozeile) und **URL mit Token**
(`jobs.php?token=…`, Technik.md 4.97a, für externe Zeitplandienste). Die
Statusseite meldet „kein Job-Lauf seit über 24 h" rot.

**Muss**
- **Huckepack genügt.** Jeder Job, den P5 anlegt, läuft auf dem
  Huckepack-Weg und hält das dortige Zeitbudget. Das gilt für die
  **Mail-Warteschlange**, die **Karenzlöschung** (Selbstlöschung mit Frist,
  R37), die **Audit-Rotation** und den **Torwächter** für ausstehende
  Migrationen (R40.4).
- **Latenzzusage auf Muss:** Ein täglicher Job läuft innerhalb von 24 h,
  sofern die Installation in dieser Zeit **eine** Anfrage sieht. Eine
  Installation ohne jede Anfrage braucht keine Wartung.
- **Mail geht nicht über den Job allein** (E-PP-05): Der **erste
  Zustellversuch** einer Nachricht läuft **synchron in der Anfrage**, die
  sie erzeugt — Double-Opt-In, Kopplungsmail, Passwort-Reset warten nicht
  auf den nächsten Huckepack-Lauf. Scheitert er, geht die Nachricht in die
  Warteschlange, und die Wiederholungen laufen im Job. Der synchrone
  Versuch hat ein eigenes Zeitbudget (**5 s** Verbindungs- und
  Sendezeit), damit ein hängender SMTP-Server nicht die Registrierung
  hängen lässt.
- Die Statusseite sagt, **welcher Weg** zuletzt gelaufen ist (Huckepack,
  Cron, URL) — heute sagt sie nur *dass*.

**Empfohlen**
- **Cron alle 5 Minuten** auf `php jobs.php`, oder — ohne Shell — ein
  externer Zeitplandienst auf die Token-URL. Dann trägt die Wartung keine
  Besucherin mehr mit, und Wiederholungen der Mail-Warteschlange kommen
  in Minuten statt beim nächsten Besuch.
- Läuft ein Cron, **schweigt der Huckepack-Weg** (er sieht am Zeitstempel
  des letzten Laufs, dass er nicht dran ist). Das ist heute schon so und
  bleibt.

### PP-4 — Shell und SSH

**Befund:** Der Betrieb aus dem Browser deckt heute: Einrichtung,
`update.php`, Backups und Wiederherstellung, Serverschlüssel und
Server-Anteil, Schlüsselblatt, Wartungsmodus, Demo-Reset. Eine Shell braucht
nur, was unter `tools/` liegt — und `tools/` wird **nicht deployt**
(`deploy.yml` lädt nur `server/`). Backlog Nr. 80 hängt daran:
`tools/geraetemodelle/nachaufloesen.php` ist ohne Shell nicht ausführbar,
und ohne ihn zeigt die Geräteverteilung ungeprüfte Selbstauskünfte.

**Muss**
- **Keine Shell.** Alles, was eine Betreiberin im Betrieb tun muss, hat
  einen Weg im Browser unter *Betrieb* und *Verwaltung*. Das ist heute
  erfüllt und bleibt eine Zusage: **Ein P5-Arbeitspaket, das einen
  Betriebsgriff nur als Skript liefert, ist unvollständig.**
- `tools/` ist **Entwicklungs- und Prüfwerkzeug**, keine
  Betriebsvoraussetzung. Das Betreiberhandbuch führt je Werkzeug, ob es
  eine Shell braucht und welcher Browser-Weg dasselbe leistet.
- **Nr. 80 löst sich in P5 von selbst** (E-PP-06, entschieden 15.09.2026,
  F-PP-2): Ein Job merkt, dass die Modelltabelle `geraetemodelle.php`
  neuer ist als die letzte Auflösung — ein Hash der Tabelle liegt in
  `app_state`, weicht er ab, ist der Job dran — und geht die Gerätezeilen
  in Stücken von 200 je Lauf durch (Zeitbudget wie Huckepack), bis der
  Hash stimmt. Er ändert nur, was die Tabelle kennt, und rührt die
  Rohangabe nie an (die Regeln des Skripts gelten unverändert). Die
  Statusseite zeigt danach als Hinweis: „Modelltabelle vom …: N Zeilen
  nachgelöst, M unbekannt". **Läuft auf beiden Stufen:** mit Cron binnen
  Minuten nach dem Deploy, ohne Cron beim nächsten Aufruf (PP-3). Das
  Skript unter `tools/` bleibt für die Shell — mit Vorschau — und ruft
  dieselbe Bibliotheksfunktion. Die Vorschau vor dem Schreiben, die das
  Skript verlangt, entfällt für den Job bewusst: Die Tabelle ist
  deployter Code und hat das Prüftor (R67, Stufe 1) schon passiert.

**Empfohlen**
- SSH für die Betreiberin — für `php jobs.php --pause`, Wiederanlauf aus
  dem Wiederanlaufpaket unter Zeitdruck, Logs. Nichts davon ist ohne SSH
  unmöglich, alles ist damit schneller.

### PP-5 — Dateisystem

**Befund:** Die Anwendung schreibt an drei Orte: einmalig bei der
Einrichtung `config.php` und `install.lock` (Anwendungswurzel),
im Betrieb `sicherungen/` (Ablage mit `komplett/` und `eingang/`) und
`wartung.lock` (Anwendungswurzel, Paket W). `config.php` **muss nicht**
beschreibbar sein: Backup-Ziele, Serverschlüssel und Server-Anteil bieten
den Knopf, wenn sie es ist, und sonst die eine Zeile zum Eintragen von Hand
(Technik.md 7). Freien Platz misst `speicher_lib.php` bereits.

**Muss**
- Beschreibbar: die **Anwendungswurzel** (`wartung.lock` — der
  Torwächter aus P5 schaltet ihn; bei der Einrichtung `config.php` und
  `install.lock`) und **`sicherungen/`** samt Unterverzeichnissen.
  `install.php` prüft beides mit einer Probedatei, nicht mit
  `is_writable()` allein (das lügt bei ACLs und `open_basedir`).
- **Freier Platz:** Die Statusseite warnt **orange**, wenn der freie
  Platz unter dem **Zweifachen des größten vorhandenen Komplett-Backups**
  liegt, und **rot** unter dem Einfachen — dann schlägt das nächste
  Komplett-Backup fehl. Meldet der Hoster keinen freien Platz
  (`disk_free_space()` liefert `false` oder den Wert der ganzen Platte),
  steht dort „unbekannt" und ein Hinweis, das Kontingent von Hand zu
  prüfen — kein geratener Wert.
- `open_basedir` darf gesetzt sein; die Anwendung greift auf nichts
  außerhalb ihrer Wurzel und `sys_get_temp_dir()` zu. `install.php`
  prüft, dass `sys_get_temp_dir()` beschreibbar ist (die Backups bauen
  ihre ZIPs dort).

**Empfohlen**
- `config.php` beschreibbar — dann genügen die Knöpfe (S10 P-06).
- Ein Plattenplatz, der die Aufbewahrung der Komplett-Backups trägt: Die
  Zahl der aufbewahrten Stände ist eine Einstellung (Vorgabe **2**,
  `KOMP_AUFBEWAHRUNG_VORGABE`, einstellbar 1–20 — berichtigt 15.09.2026,
  E-P5a-11: hier stand „3"); wer
  mehr behält, braucht entsprechend mehr Platz, und die Warnung oben
  rechnet mit dem Größten, nicht mit der Summe.

### PP-6 — HTTP-Schicht

**Befund:** Cookies tragen `secure`, `httponly`, `samesite=Strict`
(`auth_guard.php`). Sicherheitskopfzeilen (CSP, HSTS, `nosniff`,
`frame-ancestors`) setzt heute **niemand** — Backlog Nr. 8, Bauplan SP-5
(CSP mit Nonce). `rate_ip()` liest bewusst nur `REMOTE_ADDR` und wertet
`X-Forwarded-For` **nicht** aus, weil die Kopfzeile vom Aufrufer kommt und
sich zum Zurücksetzen des Zählers erfinden ließe.

**Muss**
- **HTTPS.** Die Anwendung prüft es und verweigert die Anmeldung über
  HTTP mit einer Seite, die sagt, warum (heute stünde ein `secure`-Cookie
  einfach stumm nicht da). Ausnahme: `localhost` — Browser behandeln es als
  sicheren Kontext, und so läuft der Prüfstand.
- **Kopfzeilen kommen aus PHP** (E-PP-07): `Content-Security-Policy`
  (SP-5, Nonce je Antwort), `Strict-Transport-Security` (nur wenn die
  Anfrage über HTTPS kam; `max-age` beginnt mit einem Tag und steigt über
  eine Einstellung — ein falsch gesetzter langer HSTS sperrt eine Domain
  aus), `X-Content-Type-Options: nosniff`, `Referrer-Policy`,
  `frame-ancestors 'none'`. Eine `.htaccess` liegt als **Zusatz** bei, für
  Apache-Hoster, und wiederholt nur, was PHP schon setzt — sie ist keine
  Voraussetzung, weil nginx und Caddy sie nicht lesen.
- **Webserver-neutral:** kein `mod_rewrite` nötig (die Anwendung läuft
  mit sichtbaren `.php`-Adressen, wie heute), kein `mod_php`
  vorausgesetzt (FPM und CGI sind gleichwertig). Wer eine hübsche Adresse
  will, konfiguriert den Server; die Anwendung merkt es nicht.
- **Client-IP:** `REMOTE_ADDR`, sonst nichts — wie heute. Der Grund aus
  `ratelimit_lib.php` gilt weiter.

**Empfohlen**
- **Vertrauenswürdige Proxys** (E-PP-08): `config.php` bekommt eine Liste
  `vertrauenswuerdige_proxys` (IP-Adressen oder CIDR, Vorgabe **leer**).
  Nur wenn `REMOTE_ADDR` in dieser Liste steht, nimmt `rate_ip()` die
  **letzte** Adresse aus `X-Forwarded-For`. Leer heißt: Verhalten wie
  heute. Das ist die Antwort für Installationen hinter einem Reverse
  Proxy oder einem DDoS-Schutz, ohne die Zählung für alle anderen
  angreifbar zu machen. Die IP-Grenzwerte für NAT (R37) rechnen mit dieser
  Adresse.
- HTTP/2 oder HTTP/3 und Brotli/gzip auf dem Server: unsichtbar für die
  Anwendung, spürbar für fünf Jahre alte Handys (Z3).

### PP-7 — Mail

**Befund:** Versand über SMTP aus `config.php` (`smtp`, TLS, eigener
Server möglich — `smtp.php`); `mail()` wird nirgends benutzt.
`tools/versandprobe/` misst gegen echte Gegenstellen. Eine Warteschlange
gibt es noch nicht (P5, R37); Warnmails aus S2 gehen direkt.

**Muss**
- **SMTP mit TLS** (implizit auf 465 oder STARTTLS auf 587) aus
  `config.php` — kein `mail()`, kein Sendmail-Pfad, keine Annahme über
  einen Hoster-Relay. Der Absender liegt auf einer Domain, die die
  Betreiberin kontrolliert.
- **Mail-Warteschlange** (P5): jede ausgehende Nachricht wird zuerst
  gespeichert, dann versucht (synchron, E-PP-05), dann bei Bedarf im Job
  wiederholt — **fünf Versuche über 24 h**, danach steht sie auf der
  Statusseite als unzustellbar mit Empfänger und Grund. Nichts geht
  verloren, weil ein SMTP-Server gerade nicht antwortet.
- **SPF, DKIM, DMARC** sind **Betreiberpflicht**, keine Anforderung an die
  Plattform: Das Betreiberhandbuch sagt, was einzutragen ist, die
  Betriebsakte hält fest, dass es geschehen ist, und die Versandprobe
  prüft den Empfang. Die Anwendung kann DNS nicht prüfen und tut nicht so.

**Empfohlen**
- **Bounce-Postfach**, das die Anwendung per IMAP liest (Job): Eine
  Adresse, die dreimal zurückkommt, wird am Konto als „unzustellbar"
  vermerkt und die Nutzerin beim nächsten Anmelden gefragt. Ohne
  Postfach bleibt der Vermerk aus; die Zustellversuche werden gezählt,
  mehr nicht.
- Eine **eigene Absender-Subdomain** (etwa `mail.`), damit ein Fehler im
  Versand nicht den Ruf der Hauptdomain trifft.

### PP-8 — Nicht vorausgesetzt

Zwei Punkte aus R36, die das Profil ausdrücklich **nicht** verlangt — und
warum.

**DDoS-Grundschutz des Hosters.** Der Ratenschutz der Anwendung
(`ratelimit_lib.php`: Töpfe, Enumerationsschutz, gleiche Antwortzeiten)
ist die Muss-Stufe; er hält fehlerhafte Clients und einfache Angriffe
ab. Volumetrische Angriffe hält kein PHP ab — dafür ist die Plattform
zuständig, und ob sie es tut, ist Tarif und nicht Anforderung. Im
Betreiberhandbuch als Empfehlung; mit PP-6 (vertrauenswürdige Proxys)
lässt sich ein vorgeschalteter Schutz einbinden, ohne dass der Ratenschutz
blind wird.

**Verschlüsselung at rest.** Nicht vorausgesetzt, aber empfohlen — und
die Begründung ist die Antwort auf die Frage vom 15.09.2026, ob nach S11
noch etwas im Klartext liege. **Ja.** S11 verschlüsselt **Spur,
Phasenkoordinaten, Reanimationsereignisse und Zielklinik** (Schritt 12a);
`seq` und Zeitstempel bleiben absichtlich Klartext. Was **auch nach S11
im Klartext liegt** und weshalb es Personenbezug hat:

| Bleibt Klartext | Personenbezug |
|---|---|
| Notizen des **Diensttags** (`days.notes`, Betriebsnotizen) | je nach Inhalt — die Kleinzeile „Klartext — keine Patientendaten" bleibt |
| Phasen**zeiten** und `seq`, Diensttag-Datum, Rettungsmittel je Tag | wer wann Dienst hatte |
| **Besatzungsnamen** | Kolleginnen, die kein Konto haben |
| Stammdaten: Rettungsmittel, **Standorte samt Koordinaten**, Kliniken | Arbeitsort |
| Kontodaten: E-Mail, Rollen, Einwilligungen, Sitzungen, Gerätekennungen (Art und Modell) | die Nutzerin selbst |
| Audit-Protokoll, Fehlerprotokoll (P5) | wer was getan hat |

Das ist kein Mangel von S11, sondern seine Grenze: S11 schützt den
**Einsatz** (wo, wen, was), nicht den **Betrieb** (wer, wann, womit). Ein
Datenbankabzug nach S11 sagt nichts mehr über Patientinnen und Einsatzorte,
aber weiterhin, welche Notärztin an welchem Tag auf welchem Rettungsmittel
saß. Genau dafür ist Verschlüsselung at rest die passende zweite Schicht —
und sie ist Tarif, nicht Code. Was die Anwendung selbst leistet: Was die
Datenbank **verlässt**, ist versiegelt — Adminpakete seit S10
(`sk_versiegeln()`), Konto-Backups als `.edbak` Fassung 4, Backup-Ziele
nur noch SFTP und FTPS. Der Datenschutztext der Installation nennt beides:
was verschlüsselt ist, was nicht, und dass die Betreiberin für die
Plattform darunter einsteht (Nr. 43, Weg C).

### PP-9 — Staging und Auslieferung

**Befund:** Heute lädt eine GitHub Action bei jedem Push auf `main` mit
Änderungen unter `server/` per FTPS auf Produktiv — ohne Zwischenstufe
(`CLAUDE.md` 3). R67 beendet das mit P5-Beginn: Staging automatisch,
Prüftor, Umgebung „produktion" mit Freigabe- und Backup-Tor. R66:
kein Selbst-Update, die Installation ändert ihren Code nie selbst.

**Muss**
- **Staging ist eine zweite Installation nach demselben Profil**: eigene
  Datenbank, eigene (Sub-)Domain, eigene `config.php`, eigenes
  Wiederanlaufpaket. Nichts an der Anwendung unterscheidet Staging von
  Produktiv außer dem Inhalt von `config.php` — es gibt **keinen
  Staging-Schalter im Code**.
- **Der Deploy-Transport ist Sache der Kette, nicht der Anwendung.** FTPS,
  SFTP, rsync über SSH oder ein Upload von Hand sind gleichwertig; die
  Anwendung weiß nicht, wie sie auf den Server kam. Was sie weiß, ist ihre
  eigene Fassung (`version.php`) und ob eine Migration aussteht — der
  **Torwächter** (R40.4) schaltet dann `wartung.lock`, bis `update.php`
  gelaufen ist.
- Für **Selbsthoster ohne GitHub**: Der Weg „Dateien hochladen,
  `update.php` aufrufen" bleibt vollständig und steht im Betreiberhandbuch
  als der eine Weg, den jeder gehen kann. Die Kette nach R67 ist der
  bequeme Weg des Projekts, nicht die Voraussetzung.

**Staging-Ziel (E-PP-09, Auftraggeber 15.09.2026)**
- Adresse **`staging.nadoku.gen-em.org`**, beim selben Hoster im selben
  Tarif wie Produktiv („Webserver same same") — damit gilt das Empfohlen
  unten. HTTPS wie Produktiv über den Hoster (Let's Encrypt).
- Absender **`staging@gen-em.org`**; jede Mail aus Staging trägt den
  Betreff-Präfix **„[Staging]"**, damit keine je mit einer Produktiv-Mail
  verwechselt wird. Der Präfix ist eine Einstellung in `config.php`
  (`mail.betreff_praefix`, Vorgabe leer), nichts Staging-Spezifisches im
  Code (PP-9, Muss).
- **Backup-Ziel:** ein eigenes **SFTP-Ziel** für Staging wird eingerichtet
  (Auftraggeber, 15.09.2026) — damit lässt sich das Backup-Tor der Kette
  (R67, K2) dort durchproben, ohne dass Staging-Stände neben
  Produktiv-Sicherungen liegen.
- **Einrichtung** (Zuarbeit, Abschnitt 6 des Rahmenplans, keine
  Zugangsdaten an das Konzept): Subdomain mit eigenem Verzeichnis, eigene
  Datenbank samt DB-Nutzer, ein FTPS-Konto, das nur das
  Staging-Verzeichnis sieht; in GitHub die Umgebung `staging` mit den
  drei FTP-Geheimnissen. Nach dem ersten Deploy `install.php`, **eigener**
  Serverschlüssel und Server-Anteil (nie die von Produktiv), dann
  Demo-Konto, Referenzdatensatz und Messstand-Konto per Prüfwerkzeugen.
  Die Umstellung von `deploy.yml` (Push → Staging, Tag → Produktion mit
  Freigabe) ist das **erste Code-Paket von P5**; bis dahin deployt `main`
  weiter auf Produktiv.

**Empfohlen**
- **Staging beim selben Hoster im selben Tarif** wie Produktiv: Nur dann
  misst der Messstand die Grenzen, die Produktiv wirklich hat
  (`max_user_connections`, Zeitlimits, Platz). Ein Staging auf einem
  größeren Rechner findet die Fehler nicht, die auf Produktiv auftreten.
- Die **Kette nach R67**: GitHub-Umgebungen `staging` und `produktion`,
  Prüftor Stufen 1 und 2, Pflichtfreigabe durch die Betreiberin,
  Backup-Tor vor dem Produktiv-Deploy. Ihre Zugangsdaten sind
  Umgebungsgeheimnisse, nie im Repositorium.

---

## 2. Was `install.php` und die Statusseite daraus prüfen

Die Muss-Stufe ist die Prüfliste; sie steht an **einer** Stelle im Code
(eine Funktion, die eine Liste von Befunden zurückgibt) und wird von
beiden Seiten aufgerufen — `install.php` vor der Einrichtung, die
Statusseite im Betrieb.

| # | Prüfung | Muss | Herkunft |
|---|---|---|---|
| 1 | PHP-Version | ≥ 8.2 | PP-1 |
| 2 | Erweiterungen | `pdo_mysql`, `openssl`, `mbstring`, `zip`, `zlib` | PP-1 |
| 3 | Weblimits | `memory_limit` ≥ 64M, `max_execution_time` ≥ 30, `post_max_size` und `upload_max_filesize` ≥ 2M | PP-1, Z3 |
| 4 | Datenbank | MySQL ≥ 8.0 oder MariaDB ≥ 10.6; InnoDB; `utf8mb4` | PP-2 |
| 5 | Verbindungsgrenze | `SHOW VARIABLES LIKE 'max_user_connections'` — 0 (unbegrenzt) oder ≥ 10; darunter rot | PP-2 |
| 6 | Schreibrechte | Anwendungswurzel, `sicherungen/`, `sys_get_temp_dir()` — je mit Probedatei | PP-5 |
| 7 | Freier Platz | ≥ 1× größtes Komplett-Backup (rot), ≥ 2× (orange) — oder „unbekannt" | PP-5 |
| 8 | HTTPS | Anfrage kam über TLS | PP-6 |
| 9 | SMTP | Verbindung und TLS-Handshake zum eingetragenen Server (kein Versand) | PP-7 |
| 10 | Jobs | letzter Lauf < 24 h, mit Weg (Huckepack, Cron, URL) | PP-3 |

Die Empfohlen-Stufe erscheint auf der Statusseite als eigene Karte
„Plattform" mit Hinweisen: PHP-Fassung unter 8.3, OPcache aus, keine
LTS-Datenbank, Verbindungsgrenze unter 50, `config.php` nicht beschreibbar,
kein Cron, keine vertrauenswürdigen Proxys eingetragen (nur als Auskunft),
kein Bounce-Postfach. **Kein Hinweis färbt die Ampel** — die Ampel bleibt
den Zuständen vorbehalten, die Technik.md 4.99e heute nennt.

---

## 3. Was daraus ins P5-Konzept geht

Die Eckdaten erzeugen Arbeit an vier Stellen; das P5-Konzept schneidet sie
in seine Pakete ein und nennt je Paket die Stufe (E-PP-02).

| Was | Wo | Herkunft |
|---|---|---|
| Prüffunktion der Muss-Stufe; `install.php` prüft Version, `pdo_mysql`, Weblimits, Schreibrechte mit Probedatei, DB-Version und Verbindungsgrenze | `install.php`, neue Bibliothek, Betrieb → Status | Abschnitt 2 |
| Karte „Plattform" auf der Statusseite (Empfohlen-Hinweise, Weg des letzten Job-Laufs) | Betrieb → Status | PP-1 bis PP-7 |
| 503-Weg bei Verbindungsgrenze, Zähler, Messstand-Nachweis | `db.php`, API-Antworten, Messstand | PP-2 |
| Kontingent-Einstellung (10 GB) mit Warnung ab 80 % | Betrieb → Servereinstellungen, Statistik | PP-2 |
| Synchroner erster Zustellversuch mit 5-s-Budget, Warteschlange mit fünf Versuchen über 24 h, Unzustellbar-Liste | Mail-Warteschlange (R37) | PP-3, PP-7 |
| Job „Gerätemodelle nachauflösen" mit Hash-Auslöser und Stückelung; Hinweis auf der Statusseite; Bibliotheksfunktion, die auch das Skript ruft | Jobs, Betrieb → Status, `tools/geraetemodelle/nachaufloesen.php` | PP-4, Nr. 80 |
| Betreff-Präfix als Einstellung (`mail.betreff_praefix`) | `config.example.php`, Mailversand | PP-9 |
| `deploy.yml`: Push → Staging, Tag → Produktion mit Freigabe (erstes Code-Paket) | `.github/workflows/`, R67 | PP-9 |
| Platzwarnung gegen das größte Komplett-Backup; Aufbewahrung als Einstellung | Betrieb → Status, Servereinstellungen | PP-5 |
| Kopfzeilen aus PHP (CSP mit Nonce nach SP-5, HSTS mit Einstellung, `nosniff`, `Referrer-Policy`, `frame-ancestors`); HTTPS-Zwang mit Erklärseite; `.htaccess` als Zusatz | zentraler Einstieg (`auth_guard.php` oder `ui.php`), Backlog Nr. 8 | PP-6 |
| `vertrauenswuerdige_proxys` in `config.php` und `rate_ip()` | `config.example.php`, `ratelimit_lib.php` | PP-6 |
| Bounce-Postfach als Job (Empfohlen) | Jobs, Kontoseite | PP-7 |
| Torwächter schaltet `wartung.lock`; Selbsthoster-Weg im Betreiberhandbuch | `update.php`, Betrieb → Updates, Doku | PP-9, R40.4 |

Nicht in P5, aber aus diesem Dokument: das Kapitel „Installation und
Selbsthosting" des Betreiberhandbuchs (R72, P7) übernimmt die Tabellen aus
Abschnitt 1 und 2 als Anforderungsliste — die Regel aus E-PP-03 im Text,
die Zahlen als Stand mit Datum.

---

## 4. Offene Fragen

Der Auftraggeber hat am 15.09.2026 gebeten, die Aufteilung in Muss und
Empfohlen nach eigenem Ermessen zu schreiben. Die Festlegungen oben sind
deshalb **getroffen, nicht vorgeschlagen**; die vier Stellen, an denen eine
andere Wahl vertretbar gewesen wäre, stehen hier zum Gegenlesen — ohne
Widerspruch gelten sie mit der Freigabe dieses Dokuments. **F-PP-2 ist auf
Rückfrage entschieden worden** (15.09.2026), anders als zuerst gesetzt.

| # | Festlegung | Die andere Wahl wäre gewesen | Warum so |
|---|---|---|---|
| F-PP-1 | PHP-Untergrenze **8.2** (PP-1) | bei 8.1 bleiben — der Code braucht nicht mehr | 8.1 ist aus der Herstellerpflege; eine Untergrenze, die eine ungepflegte Fassung zulässt, lässt den Betreiber auf einem Stand ohne Sicherheitskorrekturen laufen und sagt es ihm nicht. Wer 8.1 noch hat, sieht es in `install.php` mit dem Grund |
| F-PP-2 | **Entschieden 15.09.2026: O2 — Nr. 80 löst ein Job von selbst** (PP-4, E-PP-06). Zuerst gesetzt war O1, ein Knopf mit Vorschau in Betrieb → Statistik | O1 Knopf mit Vorschau · O2 automatischer Job mit Hash-Auslöser · O3 bleibt Shell-Skript („Geräte holen es bei der nächsten Kopplung nach") | O3 verletzt die Muss-Zusage aus PP-4 — ein gekoppeltes Gerät koppelt nicht neu, die Zeile bleibt falsch. O1 und O2 leisten dasselbe; O2 braucht keinen Handgriff und keinen Cron (läuft Huckepack). Die Vorschau, die O1 böte, ersetzt das Prüftor: Die Tabelle ist deployter Code. Rückfrage des Auftraggebers „O2, aber mit Cronjob?" — Cron macht es schneller, nicht möglich; Antwort in PP-3 |
| F-PP-3 | **Vertrauenswürdige Proxys** kommen mit P5 (PP-6, E-PP-08), Vorgabe leer | erst wenn eine Installation hinter einem Proxy steht | Die IP-Grenzwerte für NAT (R37) rechnen mit der Client-Adresse; hinter einem Proxy zählen sie sonst **alle** Nutzerinnen als eine — und sperren sie gemeinsam aus. Die Option ist klein, die Vorgabe „leer" ändert nichts am heutigen Verhalten |
| F-PP-4 | **Platzwarnung** gegen das größte Komplett-Backup, nicht gegen einen festen Wert (PP-5) | fester Schwellwert, etwa 500 MB | Ein fester Wert ist bei 10 GB Datenbank zu klein und bei 200 MB zu groß. Das größte Backup ist die einzige Zahl, die sagt, ob das nächste noch passt |

---

## 5. Nachweis

- Fundstellen im Code, auf `origin/main` (`7f334cb`, 15.09.2026)
  nachgemessen: `array_is_list()` in zwei Dateien und `: never` an 15
  Stellen (PHP ≥ 8.1); Erweiterungsprüfung in `install.php` ohne
  Versionsprüfung; `opcache_invalidate()` in `serverkrypto_lib.php`;
  `rate_ip()` ohne `X-Forwarded-For` in `ratelimit_lib.php` mit
  Begründung; `secure`-Cookies in `auth_guard.php`; `wartung.lock` in der
  Anwendungswurzel (`wartung_lib.php`); `smtp` in `config.example.php`;
  `disk_free_space()` in `speicher_lib.php`; Schema InnoDB `utf8mb4` ohne
  Fensterfunktionen, CTEs, `RETURNING` oder `CHECK`.
- Z2 und Z3 aus dem S2-Konzept (Historie
  `e8a512b:docs/Konzept-S2-Mengen-Spuren-Sicherung.md`, Abschnitt 0).
- Pflegefristen von PHP und Datenbanken: Stand 15.09.2026 aus dem Wissen
  der Konzeptsitzung, **nicht** gegen die Herstellerseiten nachgemessen —
  vor der Umsetzung prüfen (E-PP-03).
- Staging-Ziel, Absender und Tarif: Angaben des Auftraggebers vom
  15.09.2026 (E-PP-09).
- Zwei Zahlen dieses Dokuments waren beim ersten Schreiben falsch und sind
  mit dem P5a-Konzept berichtigt (E-P5a-11): die Warnschwelle des
  DB-Kontingents (PP-2) und die Vorgabe der Komplett-Aufbewahrung (PP-5). `nachaufloesen.php`: Kopfkommentar und
  `geraete_lib.php` (E-S6-6), Backlog Nr. 80.
