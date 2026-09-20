# Prüfdokument — Sitzungsablage (Schritt 16, Web 20.26.0)

**Stand:** 20.09.2026 · **Zweig:** `claude/new-session-cbq57h`, von
`origin/main` `862ca7f` · **Konzept:** `Konzept-Sitzungsablage.md`

Dieses Dokument beantwortet „was muss **ich** noch tun?". Was belegt ist,
steht in Abschnitt 2 und 3; was **nicht** geprüft werden konnte, steht zuerst.

---

## 0. Was nicht geprüft werden konnte — und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man es später erkennt |
|---|---|---|---|
| N-1 | **Die Anwendung im Browser** — Anmeldung, Abmeldung, Passwort-Reset, Handbuch, Rechtstext, Notfallblatt | In dieser Umgebung liegt **keine Installation**: `server/config.php` fehlt, es gibt keine Datenbank. Gemessen: `php tools/jobprobe/probe.php` bricht an der fehlenden `config.php` ab | Prüfliste Abschnitt 4, Punkte B-1 bis B-6 |
| N-2 | **`https://<basis>/.sitzungen/` → 403** | Braucht Apache und eine erreichbare Anlage. Die Regel selbst ist gelesen (`server/.htaccess`: `RewriteRule "(^\|/)\.(?!well-known/)" - [F,L]`) und deckt jeden Pfad mit führendem Punkt ab — **gelesen ist nicht gemessen** | Punkt B-7 |
| N-3 | **Stufe 2 der Kette** (vier Pfade 403, `.well-known/` 404) | Läuft gegen Staging. Der Auftrag untersagt Merge und Push auf `main` ohne Ansage; Stufe 2 ist auf der neuen Staging-Anlage derzeit ohnehin nicht grün | Punkt B-8 |
| N-4 | **Prüfpunkt E-SA-07 auf einer echten Anlage** | Der hier gemessene Rückfall lief gegen `/var/lib/php/sessions` dieses Containers (`0733`), nicht gegen lima-city (`0773`, Eigentümer root). Die Logik ist damit belegt, **die Anlage nicht** | Punkt B-9 |
| N-5 | **„Zwei Deploys hintereinander, die Sitzung überlebt beide"** | `.sitzungen/` steht **noch nicht** in der Ausnahmeliste des Transports (Kette II, E-KH-20, AP5 dort). **Nachgetragen 20.09.2026:** Der heutige Transport löscht den Ordner nachweislich nicht (K-1) — der Punkt belegt damit die Zusage des Eintrags, nicht die Abwehr einer akuten Gefahr | Punkt K-1 — **erst nach Kette II prüfbar** |
| N-6 | **`tools/jobregister/` in Stufe 1** | Derselbe Grund: `.github/workflows/pruefung.yml` gehört zu Kette II. Das Werkzeug liegt vor und läuft; es hängt nur noch nicht | Punkt K-2 |
| N-7 | **nginx** | Es gibt keine Anlage dieses Projekts mit nginx. Dort greift die `.htaccess`-Regel nicht, und `0700` ist die **einzige** Sicherung | bleibt dauerhaft ungeprüft; steht so in `docs/Technik.md` 5b.2 |
| N-8 | **„Verzeichnis nicht anlegbar" auf einer echten Anlage** | Nachgestellt, indem `.sitzungen` als **Datei** statt als Verzeichnis existierte — `mkdir` scheitert dann wie bei fehlendem Schreibrecht. Ein entzogenes Schreibrecht ließ sich nicht nachstellen: Der Prüfstand läuft als `root`, und root ignoriert die Rechtebits | Der Rückfallzweig selbst ist belegt (Abschnitt 2.4) |
| N-9 | **`tools/wartungsprobe/` nach der Änderung** | Braucht eine laufende Installation mit Datenbank (N-1). Die Änderung ist gelesen und eng (`sitzung_ort()` an zwei Stellen), **aber nicht gefahren** | Punkt B-10 — **wichtig**, weil diese Probe sonst 57 Erwartungen mit „nicht angemeldet" verliert |

**Zwei Zahlen, die nicht das sind, wonach sie aussehen:**

- **„Neun Sitzungsstarts" gilt für `server/` OHNE `vendor/`.** Unter
  `server/vendor/phpseclib3/Crypt/Random.php` stehen zwei weitere echte
  `session_start()` (Zeilen 94 und 117). Sie liegen im reinen PHP-Rückfall von
  `Random::string()`, erreichbar nur wenn `random_bytes()` wirft — auf der
  Projektuntergrenze PHP 8.2 ein toter Pfad, und phpseclib wird ohnehin erst
  lange nach `db.php` geladen. Sie erben die neue Ablage und legten dort
  `sess_1` an, was das Räummuster `sess_*` mit abdeckt. Kein Handlungsbedarf —
  aber wer „neun" ohne diesen Zusatz zitiert, zitiert eine Zahl mit
  stillschweigender Bedingung.
- **„398 Befunde" der Vollständigkeit ist die Schwelle, nicht null.** Der
  erste Lauf nach dem Paket meldete **399**: ein Auslassungszeichen (U+2026)
  in einem neuen Kommentar in `db.php`. Dieselbe Falle wie bei Web 20.25.0.
  Entfernt, wieder 398 — „unveraendert".

---

## 1. Was gebaut wurde

| Datei | Art |
|---|---|
| `server/sitzung_lib.php` | **neu** |
| `tools/jobregister/pruefen.php`, `tools/jobregister/LIESMICH.md` | **neu** |
| `server/db.php`, `server/install.php`, `server/auth_guard.php`, `server/email_lib.php`, `server/plattform_lib.php`, `server/jobs_lib.php`, `server/jobs.php`, `server/version.php` | geändert |
| `tools/wartungsprobe/probe.php`, `tools/wartungsprobe/LIESMICH.md` | geändert |
| `.gitignore`, `docs/CHANGELOG.md`, `docs/Technik.md`, `docs/Backlog.md` | geändert |
| `docs/konzepte/Konzept-Sitzungsablage.md`, dieses Dokument | **neu** |

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

### 2.1 Die Bauform: zwei Aufrufstellen, neun Sitzungsstarts

**Mit dem Tokenizer, nicht mit `grep`.** Ein `grep` über `sitzung_ablage(`
findet **acht** Stellen — sechs davon sind Kommentare.

```
php -r '$n=0; $rit=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("server"));
foreach ($rit as $f) { $p=$f->getPathname();
  if (substr($p,-4)!==".php" || strpos($p,"/vendor/")!==false) continue;
  if (basename($p)==="sitzung_lib.php") continue;
  $t=token_get_all(file_get_contents($p));
  for ($i=0;$i<count($t);$i++) {
    if (!is_array($t[$i]) || $t[$i][0]!==T_STRING || $t[$i][1]!=="sitzung_ablage") continue;
    for ($k=$i+1;$k<count($t);$k++){ if (is_array($t[$k]) && $t[$k][0]===T_WHITESPACE) continue; break; }
    if ($t[$k]!=="(") continue;
    $n++; echo "  $p:".$t[$i][2]."\n"; } }
printf("Echte Aufrufe: %d\n",$n);'
```

| Gemessen | Soll | Ergebnis |
|---|---|---|
| Echte Aufrufe von `sitzung_ablage()` außerhalb `sitzung_lib.php` | **2** | **2** — `server/db.php:586`, `server/install.php:144` |
| Dateien mit echtem `session_start()` unter `server/` ohne `vendor/` | **9** | **9** |
| davon mit `db.php` davor | **8** | **8** — die neunte ist `install.php` |

**Zweimal unabhängig gemessen.** `php tools/sitzungshaertung/pruefen.php`
zählt dieselben neun mit einem eigenen Tokenizer-Leser; eine dritte,
getrennt geschriebene Zählung (Klammertiefe je `require`) kam auf dieselbe
Zahl und wies zusätzlich nach, dass `install.php` `db.php` **auch transitiv
nicht** lädt. Eine naive Erreichbarkeitshülle ohne Klammertiefe behauptet das
Gegenteil (39 erreichte Dateien, darunter `db.php`) — **sie irrt**, weil
`email_lib.php:128` im Rumpf einer Funktion steht.

**Kein zehnter Sitzungsstart, keiner ohne `db.php` davor** — die
Abbruchbedingung des Auftrags ist nicht eingetreten.

### 2.2 Der Web-Weg, gefahren statt angenommen

Prüfstand: eine Kopie von `server/` mit `config.example.php` als
`config.php`, dahinter `php -S`. Damit ist `PHP_SAPI` **nicht** `cli`.

| Gemessen über den echten `db.php`-Ladeweg | Ergebnis |
|---|---|
| `.sitzungen/` entsteht | ja |
| Rechte | **0700** |
| Eigentümer | eigene UID |
| `session.save_path` | zeigt auf `.sitzungen/` |
| `session.gc_probability` | **0** |
| Markerdatei `.geprueft` | angelegt, `0600` |
| Sitzungsdatei nach `session_start()` | `sess_6c60d073…` in `.sitzungen/` — **identisch mit der Kennung im `Set-Cookie`** |
| liegengebliebene Probedateien | **0** |

### 2.3 Die Markerdatei spart die Probe wirklich

Gemessen wurde nicht „0 Probedateien" (das beweist nur, dass aufgeräumt
wird), sondern **ob `plattform_lib.php` überhaupt nachgeladen wurde** —
`function_exists('plattform_schreibprobe')` vor und nach dem Aufruf.

| Lage | Probe gelaufen? | Soll |
|---|---|---|
| Marker frisch | **nein** | nein |
| Marker auf 2 Stunden gealtert | **ja** | ja |
| danach wieder | **nein** | nein |

Entspricht der Abnahme „zweite Anfrage innerhalb einer Stunde schreibt **0**
Probedateien, mit gealtertem Marker **1**".

### 2.4 Rückfall, CLI, Aufräumen

| Fall | Gemessen |
|---|---|
| **Rückfall** (`mkdir` scheitert) | `save_path` bleibt der Hosterpfad · Stand `eigen=false` mit Grund · `schreib_sitzungen` = `false` (Empfohlen) · `sitzung_ablage` = `false` (Muss), gemessen „Hosterpfad, 0733 - fuer andere zugaenglich, 0 Dateien" |
| **CLI** | `.sitzungen/` wird **nicht** angelegt, `save_path` unverändert, Stand `gelaufen=false` |
| **Aufräumjob** | drei Dateien angelegt (eine auf 2 h gealtert, eine frisch, dazu `nicht_sess.txt` auf 27 h): **„1 gelöscht"** · Markerdatei bleibt · Fremddatei bleibt · zweiter Lauf **0** |
| **Frist** | `SESSION_TIMEOUT_S` 1800 + `SITZUNG_KARENZ_S` 3600 = **5400 s** |

### 2.5 Die Prüfpunkte

`plattform_pruefen(null)` liefert **17** Befunde (vorher **15**). Auf der
Kommandozeile stehen beide neuen auf `null` — **keine Falschmeldung**:

```
  schreib_wurzel     muss       ok=true  | beschreibbar
  schreib_ablage     muss       ok=true  | beschreibbar
  schreib_tempdir    muss       ok=true  | beschreibbar
  schreib_sitzungen  empfohlen  ok=NULL  | nicht messbar (Kommandozeile)
  sitzung_ablage     muss       ok=NULL  | nicht messbar (Kommandozeile)
```

### 2.6 Das Jobregister (Nr. 208)

| Gemessen | vorher | nachher |
|---|---|---|
| Jobs im Katalog / im Register | 11 / **9** | 11 / **11** |
| Räumschritte im Code / laut Register | 16 / **13** | 17 / **17** |
| Befunde `tools/jobregister/pruefen.php` | **7** | **0** |
| Selbstprobe | — | **9 von 9** |
| Werkzeugbaum in `Technik.md` / Ordner unter `tools/` | 46 / 48 | **48 / 48** |

### 2.7 Die Prüfmittel der Kette

| Mittel | Was es gemessen hat | Ergebnis |
|---|---|---|
| `php -l` über `server/` und `tools/` | **161 Dateien** (ohne `vendor/`) | **0 Fehler** |
| `tools/wortliste/wortliste.py` | a **133** Dateien (vorher 132 — `sitzung_lib.php` ist mitgemessen), b 40, c 11, d 2, e 35 | **0** Treffer außerhalb, **0** ungenutzte Ausnahmen, **0** durchgerutschte Fallen |
| `tools/vollstaendigkeit/pruefen.py --hoechstens 398` | 398 | **„AUF DER SCHWELLE: 398 — unveraendert"** |
| `tools/screenshots/kontrast.py` | **22 Paare** | **0 verfehlt** |
| `tools/kettenaufrufe/pruefen.py` | 3 Arbeitsläufe, **30 Aufrufe** | **0 Befunde, 0 ungeprüft**, Selbstprobe 10/10 |
| `tools/kette/tor.py --selbstprobe` | 11 Fälle | **11 erfüllt, 0 offen** |
| `tools/sitzungshaertung/pruefen.php` | **133 Dateien, 9 Aufrufe** | **0 ohne Härtung**, Selbstprobe 8/8 |
| `tools/installweiche/pruefen.php` | `install.php` **667 Zeilen**, `php_mindest.php` 35 | **0 Befunde** |
| `tools/cspprobe/pruefen.php` | — | **0 Befunde**, Selbstprobe 8/8 |
| `tools/migrationsregister/pruefen.php` | — | **0 Befunde** |
| `tools/jobregister/pruefen.php` | 11 Jobs, 17 Schritte | **0 Befunde**, Selbstprobe 9/9 |
| Backlog-Nummern (`uniq -d`) | 76 offen, 159 erledigt | **0 Dubletten**, höchste Nummer **239** |

**Die Prüfmittel liefen zuletzt**, nach Code und Dokumentation — genau
deshalb ist das eine Auslassungszeichen aufgefallen (Abschnitt 0).

---

## 3. Was beim Prüfen gefunden und behoben wurde

| # | Befund | Wie er auffiel |
|---|---|---|
| F-1 | **Ohne `clearstatcache()` nach `mkdir` meldet `sitzung_ablage()` einen Rückfall, den es nicht gibt.** Der realpath-Cache gilt prozessweit und 120 s. Auf der Statusseite wäre das ein **rotes Muss** gewesen | Weil der Prüfstand den Übergang „keine Ablage → Ablage" wirklich gefahren ist. Nach der Berichtigung **5 von 5** Läufen sauber |
| F-2 | **`$cli` war in `plattform_lib.php` erst 170 Zeilen nach meinem Einschub definiert.** Die CLI-Zweige hätten nie gegriffen, und `plattform_schreibprobe()` hätte auf der Kommandozeile „Das Verzeichnis gibt es nicht" gemeldet | Beim Gegenlesen des eigenen Diffs, vor dem ersten Lauf |
| F-3 | **Ein U+2026 in einem neuen Kommentar in `db.php`** schob die Vollständigkeit auf 399 | `tools/vollstaendigkeit/` |
| F-4 | **`tools/wegwerfdomains/` fehlte seit Web 20.22.0 im Werkzeugbaum** (46 gegen 48) | Zählung beim Eintragen von `tools/jobregister/` |
| F-5 | **`docs/Technik.md` 6.5 nannte fünf Ausnahmen, die Kette führt sieben** (`ueberlast.json`, `install.php` fehlten) | Gegenlesen für den achten Pfad |

---

## 4. Prüfliste — was die Betreiberin noch tun muss

Je Punkt: **Bedienweg**, **erwartetes Ergebnis**, **woran ein Scheitern zu
erkennen ist**.

### Im Browser, nach dem Ausrollen auf Staging

- [ ] **B-1 Anmelden.** `login.php` aufrufen, anmelden, bis zur
      Tagesübersicht durchgehen.
      **Erwartet:** Die Übersicht erscheint.
      **Scheitern:** Die Anmeldeseite kommt wieder, ohne Fehlermeldung — eine
      **Anmeldeschleife**. Das wäre der Fall „Anmeldung und Tor suchen in
      verschiedenen Ablagen" und hieße, dass eine Aufrufstelle fehlt.
- [ ] **B-2 Abmelden und neu anmelden.** Abmelden, dann erneut anmelden.
      **Erwartet:** funktioniert; im Verzeichnis liegt **1** Datei, nach dem
      Abmelden **0**, nach dem erneuten Anmelden wieder **1** (Zahl auf
      Betrieb → Status, Karte „Plattform", Zeile „Sitzungsablage").
      **Scheitern:** Die Zahl bleibt nach dem Abmelden stehen, oder sie steigt
      bei jedem Aufruf.
- [ ] **B-3 Passwort-Reset.** Den Weg über „Passwort vergessen" gehen
      (`pw_handling.php` führt eine **eigene** Sitzung mit eigenem Cookienamen).
      **Erwartet:** Der Link führt zum Setzen des Passworts.
      **Scheitern:** 403 oder „Sitzung abgelaufen" unmittelbar nach dem Klick.
- [ ] **B-4 Handbuch, Rechtstext, Notfallblatt** angemeldet aufrufen.
      **Erwartet:** Die Seiten zeigen den **angemeldeten** Kopf.
      **Scheitern:** Sie zeigen den abgemeldeten Kopf — dann hat
      `@session_start()` die bestehende Sitzung nicht gefunden.
- [ ] **B-5 Betrieb → Status.** Karte „Plattform" öffnen.
      **Erwartet:** Zeile **„Sitzungsablage"**, Plakette blau, Text
      „eigenes Verzeichnis, 0700, N Dateien", darunter der Pfad. Die Zeile
      „Ablage der Sitzungen" erscheint **nicht** — das ist richtig (Stufe
      Empfohlen, erfüllt). Die Schlusszeile „Empfohlen insgesamt" zählt jetzt
      **x/7** statt x/6.
      **Scheitern:** Plakette rot oder „nicht messbar"; oder die Zeile fehlt ganz.
- [ ] **B-6 Betrieb → Hintergrundjobs.** Zeile „Aufräumen".
      **Erwartet:** Die Beschreibung nennt **siebzehn** Schritte, darunter
      „Sitzungsdateien", und die Namen tragen richtige Umlaute
      („Ratenschutz-Zähler", „Gerätevermerke").
      **Scheitern:** Ein Schritt fehlt, oder es steht „Ratenschutz-Zaehler" da.
- [ ] **B-7 `https://<basis>/.sitzungen/` aufrufen.**
      **Erwartet:** **403**, und zwar auch für
      `https://<basis>/.sitzungen/.geprueft` und für einen erfundenen
      Dateinamen — `[F]` antwortet vor der Dateisuche.
      **Scheitern:** 404 (dann greift die Regel nicht, sie fällt nur nicht
      auf, weil nichts da ist) oder gar ein Verzeichnislisting.
- [ ] **B-8 Stufe 2 der Kette** nach dem Merge laufen lassen.
      **Erwartet:** unverändert grün — vier Pfade 403, `.well-known/` **404**.
      **Scheitern:** Ein 404 auf einen der vier, oder ein 403 auf
      `.well-known/` (dann ist die Zertifikatserneuerung tot).
- [ ] **B-9 Prüfpunkt E-SA-07 auf Staging ablesen** (dieselbe Zeile wie B-5).
      **Erwartet:** `true` mit Rechten **0700**, oder `null` („nicht messbar")
      wenn `open_basedir` oder fehlendes `ext-posix` die Messung verhindern.
      **Scheitern:** rot — dann liegt ein Rückfall vor, und der Grund steht in
      derselben Zeile.
- [ ] **B-10 `php tools/wartungsprobe/probe.php <basis>` fahren.**
      **Erwartet:** dieselbe Zahl Erwartungen wie vor dem Paket, **0 offen**.
      **Scheitern:** Sitzungsfälle mit „nicht angemeldet" oder 403. Das hieße,
      `sitzung_ort()` trifft den Ort nicht — **und es sähe aus wie ein Fehler
      der Anwendung, ist aber einer der Probe.**

### Offen, weil es an Kette II hängt

- [ ] **K-1 Achter Schutzlistenpfad.** `.sitzungen/**` und `.sitzungen/` in
      **beide** `exclude`-Blöcke von `.github/workflows/auslieferung.yml`
      (die Aktion prüft Datei- und Verzeichnismuster getrennt, deshalb je
      zwei Zeilen). Danach: **zwei Deploys hintereinander, die Sitzung
      überlebt beide.**
      **Scheitern:** Nach dem zweiten Deploy ist man abgemeldet — dann löscht
      der Transport das Verzeichnis weiterhin.
      **Bis dahin — nachgemessen, nicht angenommen:** Der heutige Transport
      löscht `.sitzungen/` **nicht**. `getServerFiles()` listet das
      Fernverzeichnis nie, sondern liest ausschließlich die eigene
      Zustandsdatei; `HashDiff.getDiffs()` kann deshalb nur löschen, was dort
      steht, und ein zur Laufzeit entstandener Ordner stand dort nie. Gelesen
      in `@samkirkland/ftp-deploy` **1.2.3, 1.2.4, 1.2.5** — `HashDiff.js` und
      `deploy.js` zeichengleich (SHA-256 nach Normierung der Zeilenenden).
      Die einmalige Abmeldung wiederholt sich also **nicht** bei jedem Deploy.
      Der Eintrag schützt gegen den anderen Weg: `.sitzungen/` im
      Auscheckstand → hochgeladen → in der Zustandsdatei → ab da löschbar.
      Gegen `dangerous-clean-slate` schützt er nicht.
- [ ] **K-2 `tools/jobregister/` in Stufe 1 einhängen**
      (`.github/workflows/pruefung.yml`, neben `sitzungshaertung`):

          php tools/jobregister/pruefen.php --selbstprobe
          php tools/jobregister/pruefen.php

      **Erwartet:** 0 Befunde, Selbstprobe 9/9.
      **Scheitern:** Der Lauf ist rot — dann sind Register und Code
      auseinandergelaufen, und genau dafür ist er da.
      **Bis dahin ist Nr. 208 nur halb wirksam:** Die erzeugte Beschreibung
      kann nicht mehr altern, das Register in `docs/Technik.md` schon — es
      wird nur eben nachgezählt, sobald es jemand fährt.
- [ ] **K-3 Backlog 241 und 242 eintragen.** Schritt 16 vergibt keine
      Nummern und legt keinen Eintrag an; `docs/CHANGELOG.md`,
      `docs/Technik.md` und dieses Dokument **nennen** die 241 bereits. Die
      Erledigt-Zeile steht in der Rückmeldung.
      **Scheitern:** Die Nummer bleibt unbelegt, und die Verweise zeigen ins
      Leere.

### Nach der Freigabe des Abschlusses

- [ ] **K-4** Erledigt-Zeile in `docs/Rahmenplan.md` Abschnitt 8, Reste nach
      Abschnitt 6, Backlog nach Abschnitt 5, eine Zeile nach Abschnitt 10 —
      und **dann** das Konzept löschen. Dieses Prüfdokument bleibt, bis die
      Liste oben abgehakt ist.

---

## 5. Grenzen der benutzten Prüfmittel

- **`tools/sitzungshaertung/`** misst, dass `use_strict_mode` **höchstens
  zwölf Zeilen** vor jedem `session_start()` steht — als Substring über den
  Rohtext, nicht über Tokens. Sie sagt **nicht**, ob `ini_set()` wirkt. Der
  neue Aufruf in `install.php` steht deshalb **vor**
  `session_set_cookie_params()`: Ein Kommentarblock zwischen Härtung und
  Aufruf hätte die Härtung aus dem Fenster geschoben und Stufe 1 rot gemacht,
  mit einem sachlich falschen Befund.
- **`tools/jobregister/`** sieht nicht, ob ein Job **tut**, was danebensteht
  (dafür `tools/jobprobe/`), und nicht die Prosa der Registerzeile jenseits
  von Namen und Zahl. Einen Job, der zur Laufzeit in den Katalog gerechnet
  wird, findet sie ebenfalls nicht — es gibt heute keinen.
- **`tools/vollstaendigkeit/`** misst gegen eine **Schwelle** (398), nicht
  gegen null. Ein neues Symbol und ein entferntes heben sich in der Zahl auf.
- **`tools/wortliste/`** liest sichtbare Texte, keine Kommentare. Die
  Schlüssel der Aufräumschritte sind seit diesem Paket sichtbarer Text
  (erzeugte Beschreibung **und** Fehlermeldung eines gescheiterten Schritts)
  — sie werden damit erstmals mitgemessen.
- **Der Prüfstand mit `php -S`** ist kein Apache: Er kennt `.htaccess` nicht.
  Er belegt den PHP-Teil (Verzeichnis, Rechte, Pfad, Marker, Räumen) und
  **nichts** über die 403-Sperre.
- **Der Prüfstand läuft als `root`.** Ein entzogenes Schreibrecht lässt sich
  darin nicht nachstellen; der Rückfall wurde deshalb über ein `mkdir`
  erzwungen, das an einer gleichnamigen Datei scheitert.
