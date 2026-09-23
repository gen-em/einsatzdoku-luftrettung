# Prüfdokument — Korrekturstufe Backlog Nr. 288 und 289 (Web 20.37.3)

*Nach `CLAUDE.md` 7. Erstellt am 23.09.2026 auf dem Zweig
`claude/p5c-mockups-konzept-4yeomf` — beide Fehler sind beim Bau der
Mockup-Runde M-P5c-02 (Konzept P5c) gefunden und auf Wunsch der Betreiberin
in derselben Sitzung behoben worden. Abschnitt 1 beantwortet „ist es
belegt?"; die **Prüfliste** in Abschnitt 5 beantwortet „was muss ich noch
tun?".*

> | | |
> |---|---|
> | Stufe | **Web 20.37.3** — Korrekturstufe, **keine Migration**, `update.php` nicht fällig |
> | Punkte | **Nr. 288** — eine neue Anlage ließ sich seit Web 20.30.0 nicht einrichten · **Nr. 289** — die Verweise der Anmeldeseite standen neben statt unter der Karte |
> | Neu entstanden | `server/transaktion_lib.php` · Backlog **Nr. 290** (keine Stufe richtet eine Anlage ein) und **Nr. 291** (halbes Schema nach einem Fehlschlag) |
> | Prüfumgebung | Container: PHP 8.4.19 (CLI und `php -S`), MariaDB 10.11.14, Chromium über Playwright 1.56; örtliche Anlage aus `tools/sandbox/hochfahren.sh --neu` |
> | Ergebnis | Alles Maschinelle grün bis auf den Stilvergleich, und der zeigt **genau die geplante Änderung**; **vier Punkte** bleiben für die Betreiberin (Abschnitt 5) |

---

## 0. Was nicht geprüft werden konnte — und warum

- **Die Neueinrichtung auf einer echten Anlage.** Belegt ist sie örtlich, mit
  PHP 8.4 und MariaDB 10.11 — nicht bei einem Hoster, nicht unter PHP 8.3
  (`hochfahren.sh --php 8.3` ist nicht gefahren) und nicht gegen MySQL 8.
  Eine echte Anlage wird erst beim nächsten Hosterwechsel neu eingerichtet;
  bestehende Anlagen richten sich nie neu ein und sind von Nr. 288 nicht
  betroffen. **Deshalb kein Prüfpunkt jetzt** — aber der Hinweis an die
  Stelle, an der er gebraucht wird: `docs/CHANGELOG.md` 20.37.3, „Für die
  BetreiberIn".
- **Andere Browser als Chromium.** Der Stilvergleich, der Bilderlauf und die
  eigene Messung der Anmeldehülle laufen in Chromium. `flex-direction:column`
  ist keine Eigenheit eines Browsers, aber gemessen ist es nur dort.
  **Prüfpunkte P-01 und P-02** sehen es am Handy an.
- **Die Abmeldeseite im echten Ablauf.** Sie leitet per Skript sofort weiter;
  gemessen ist ihr Markup aus `session_beenden()` (PHP-CLI), ohne Skripte,
  unter der Adresse der Anlage — also Hülle, Stylesheet und Karte, nicht der
  Augenblick zwischen Laden und Weiterleiten.
- **Der Riegel in der Kette** (Nr. 290). Nicht gebaut; die Korrektur schließt
  die Lücke, nicht die Blindheit der Kette für sie.

## 1. Maschinell geprüft — Mittel und Zahl

### Nr. 288 — die Einrichtung

| Mittel | Zahl | Datum |
|---|---|---|
| **Vorher**, auf `origin/main` (`1139ce7`, Web 20.37.2; `server/` unverändert): `bash tools/sandbox/hochfahren.sh --neu` | **RC 1**, Abbruch in Schritt 4 „Einrichtungslink nicht gefunden" | 23.09.2026 |
| Vorher: dasselbe Formular von Hand abgeschickt, Antwort der Seite gelesen | „Beim Anlegen der Tabellen/des ersten Kontos ist ein Fehler aufgetreten: **Call to undefined function db_transaktion()** — Tipp: eine leere Datenbank verwenden …"; `config.php` danach **nicht** vorhanden | 23.09.2026 |
| **Nachher**: `bash tools/sandbox/hochfahren.sh --neu` | **RC 0** — Schritt 4 findet den Einrichtungslink, Schritt 6 setzt das Passwort im Browser (Konsolenfehler: keine), Schritt 7 spielt das Demo-Konto ein (**106 Einsätze, 21 Diensttage, 2 Geräte**); Nachweis `login.php` HTTP 200 | 23.09.2026 |
| `konto_lib.php` ohne `config.php` laden (PHP-CLI), `function_exists('db_transaktion')` | vorher **false**, nachher **true** | 23.09.2026 |
| Tokenizer über `server/`: Definitionen von `db_transaktion` | **1** (`transaktion_lib.php`), **0** in `db.php` — keine zweite Definition, auch nicht hinter `function_exists()` | 23.09.2026 |
| Registerzeile **Z16** (`tools/zaehlung/`) | **Start 33 · Decke 9 · Ist 9, genau** — „beginTransaction( ausserhalb transaktion_lib.php"; Register gesamt **38 Zeilen, 0 über der Decke** | 23.09.2026 |

### Nr. 289 — die Anmeldehülle

| Mittel | Zahl | Datum |
|---|---|---|
| **Stilvergleich** (`tools/stilvergleich/`, Vergleichsstand `origin/main`) | **40 768 Elementmessungen, 26 Abweichungen** — alle **eine** Eigenschaft an **einem** Element: `.anmeldung`, `flex-direction: row → column`, 13 Breiten × 2 Katalogseiten. **Deckt sich mit der geplanten Liste** (`Pruefablauf.md` 6.10); der Prüfstand meldet ihn deshalb als „rot" mit Zahl 1 | 23.09.2026 |
| **Eigene Messung**: sechs Seiten der Hülle bei 390 und 1440 px, je mit dem Stylesheet von `origin/main` und dem neuen (Skript im Arbeitsbereich, nicht abgelegt) | **10 von 10 Karten auf den Pixel gleich** auf den fünf Seiten mit nur der Karte (`reset_request.php`, `registrieren.php`, `bestaetigen.php`, `pw_handling.php` mit ungültigem Link, Abmeldeseite); **0 nicht gemessen**; Überlauf **0** in allen 24 Messungen | 23.09.2026 |
| Dieselbe Messung, `login.php` bei **390 px** | Karte vorher **202 px** breit, jetzt **366 px**; Verweise vorher rechts daneben (x 214), jetzt **darunter** (Oberkante 651 px, Kartenunterkante 635 px) | 23.09.2026 |
| Dieselbe Messung, `login.php` bei **1440 px** | Karte vorher aus der Mitte geschoben (x 337), jetzt **mittig** (x 520, 400 px); Verweise darunter (644 px, Kartenunterkante 628 px) | 23.09.2026 |

### Der Prüfstand (Station B, `bash tools/pruefstand/pruefen.sh --basis origin/main`)

Stufe **klein** (Korrektursprung), 37 berührte Dateien, 6 Muster, 18 Proben.
**Dreimal gefahren — und einmal davon mit drei verfehlten Wegen:**

| Lauf | Anlage | Ergebnis |
|---|---|---|
| 1 | frisch aus `hochfahren.sh --neu` | 17 grün, Stilvergleich 1 (geplant); `syntax-php` 486/0 — `transaktion_lib.php` war beim Start noch nicht eingecheckt, und die Probe liest `git ls-files` |
| 2 | **dieselbe Anlage** nach Lauf 1 | 16 grün, Stilvergleich 1, **Bedienprobe 45 von 48** — verfehlt `ap4-ohne-standort-sichtbar` (12 statt 4 in „Ohne Standort", 13 statt 10 insgesamt), `ap4-zuordnen-friert-ein` und `ap4a-kurzname-im-band` (beide Zeitüberschreitung). Die Probe meldet selbst: **„Der Demo-Reset lief um 10:45:36 UTC mitten in diesem Lauf. Verfehlte Wege sind verdächtig — bitte wiederholen."** |
| 3 | **frisch** aus `hochfahren.sh --neu` | **17 grün**, Stilvergleich 1 (geplant); **Bedienprobe 48 von 48**, `syntax-php` 487/0 |

**Zu Lauf 2:** Keiner der drei Wege berührt Einrichtung oder Anmeldehülle.
**Eine Vermutung ist gemessen und widerlegt:** dass der Demo-Reset
standortlose Rettungsmittel verdoppelt — zwei Resets von Hand
(`demo_zuruecksetzen()`), vorher und nachher je **4 von 10**. Bleibt die
Erklärung der Probe selbst: ein Reset mitten im Lauf, auf einer Anlage, auf
der schon ein Lauf Spuren hinterlassen hat. Der Bericht in der
Commit-Nachricht stammt aus einem Lauf auf frischer Anlage.

Die Zahlen von Lauf 3:

| Probe | Zahl |
|---|---|
| `syntax-php` | **487 / 0** |
| `zaehlung` | 38 Zeilen, 0 über der Decke |
| `wortliste` (Textprobe) | **0 Treffer**, 108 Regeln, 108 gegriffen, 0 ungenutzt |
| `linkprobe` | **122 Verweise, 0 Abweichungen** |
| `kontraste` | **22 Paare, 0 verfehlt** |
| `bilderlauf` | **62 Seiten, 186 Einzelbilder** · Überlauf **0** · Konsolenfehler **0** · Knöpfe falscher Höhe **0** · Karten außerhalb `main.inhalt` **0 von 162** |
| `bedienprobe` | **48 von 48 Wegen erfüllt** |
| `verbindungsprobe` | **24 von 24 Erwartungen** |
| `installweiche` | **0 Befunde** — `transaktion_lib.php` liegt hinter der Weiche (geladen erst in `install.php` Z. 384, über `konto_lib.php`) |
| `spaltenregister`, `cspprobe`, `sitzungshaertung`, `jobregister`, `migrationsregister`, `rechtstexte`, `kettenaufrufe`, `vollstaendigkeit` | je **0** |

*Die Zahlen des letzten Laufs, auf dem Stand dieses Commits, stehen im
Prüfbericht der Commit-Nachricht.*

## 2. Nebenbefunde

- **`Design.md` 10.1 nannte den Einrichter als Beispiel der Anmeldehülle.**
  Er trägt seit O10 die öffentliche Hülle (Kommentar in `install.php` über
  `ui_seite_start()`); berichtigt. Die Hülle tragen sechs Seiten: Anmeldung,
  Passwort vergessen, Passwort setzen, Registrierung, Bestätigung,
  Abmeldeseite.
- **„Einrichten gescheitert" ist nicht der Text der Seite**, sondern die
  Zusammenfassung von `hochfahren.sh`. Im Backlog-Eintrag Nr. 288 berichtigt.
- **Der Rat „eine leere Datenbank verwenden" steht bei jedem Fehler** im
  Einrichter, auch bei einem, der mit der Datenbank nichts zu tun hat — und
  das Schema steht nach einem Fehlschlag schon (`schema.sql`: 41 von 42
  Tabellen ohne `IF NOT EXISTS`). → **Nr. 291.**
- **`Pruefdokument-Zentralisierung.md` A-7** meldete die Einrichtung als
  gelaufen; AP5 hat den Weg danach gebrochen. Dort nachgetragen.
- **Kein Prüfbericht passte seit PK-04/2 zu seinem Commit — behoben.**
  Gefunden, als `bericht.py lesen` den Bericht dieser Stufe abwies: Baum im
  Bericht `9a973fc`, Baum des Commits `2ea1371`; der Unterschied war genau
  `tools/proben/gpx/gpx11.xsd`, 788 Zeilen, nur Zeilenenden. `.gitattributes`
  nahm die Datei unter ihrem alten Pfad `tools/gpxprobe/` aus. Pfad
  berichtigt; danach Baum des Prüfstands = Baum des Index (`e09d779` beim
  Nachmessen). Kein Workflow liest den Bericht gegen — deshalb blieb es seit
  dem 22.09.2026 stumm.
- **Zwei Hinweise aus der lesenden Abklärung, NICHT nachgeprüft** (dort als
  „vermutet" geführt): Die Verbindung des Einrichters setzt keine Zeitzone
  (`SET time_zone`), `pw_handling.php` prüft den Ablauf des Links dagegen in
  UTC — die „24 Stunden" könnten um den Versatz der Datenbank abweichen. Und
  die Plattformprüfung des Einrichters führt HTTPS als Muss, während
  `kopfzeilen_lib.php` den Einrichter ausdrücklich nicht auf HTTPS zwingt.
  **Keine Backlog-Nummer**, solange sie nicht gemessen sind.

## 3. Was bewusst stehen bleibt

- **Kein Rückfall hinter `function_exists()`.** Die Erfolgsseite des
  Einrichters lädt `db.php`, sobald `config.php` geschrieben ist; eine zweite
  Definition bräche dort ab — **nachdem** `config.php` und `install.lock`
  stehen. Die Anlage wäre gesperrt, ohne dass der Einrichtungslink erschiene.
- **Kein Eintrag in `server/.htaccess`.** `transaktion_lib.php` führt keinen
  Code auf oberster Ebene aus — dieselbe Linie wie `email_lib.php`,
  `konfig_lib.php` und `format_lib.php`, die ebenfalls direkt abrufbar sind.
- **Kein neuer Baustein.** Nr. 289 ändert eine Eigenschaft einer vorhandenen
  Regel; `Design.md` 10.1 sagt es.

## 4. Grenzen der benutzten Prüfmittel

- **`hochfahren.sh --neu` geht einen Weg:** Status „aktiv", die Eingaben aus
  `lokal_einrichten.sh`. Die Plattformprüfung des Einrichters ist im
  Container erfüllt; wie sie sich auf einem Hoster verhält, zeigt er nicht.
- **Der Stilvergleich misst statisches Markup** zweier Katalogseiten, keine
  echte Seite und keinen Bedienzustand. Die echten Seiten misst die eigene
  Messung — sie vergleicht Kästen, keine Bilder.
- **Der Bilderlauf vergleicht nicht mit einem früheren Stand**; er sagt
  „kein Überlauf, keine Fehler", nicht „sieht aus wie vorher".
- **Die Bedienprobe braucht eine frische Anlage.** Ein zweiter Lauf auf
  derselben Datenbank, dazu ein Demo-Reset mitten hinein, verfehlte drei
  Wege (Abschnitt 1, Lauf 2). Wer sie wiederholt, richtet vorher neu ein.
- **Die Lese-Agenten** der Abklärung haben den Weg des Einrichters in einer
  Kopie mit SQLite nachgestellt; beweiskräftig ist allein `hochfahren.sh
  --neu` gegen MariaDB.

## 5. Prüfliste für die Betreiberin — vier Punkte, nach dem Merge

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-01 | Anmeldeseite am Handy (Nr. 289) | Staging, `login.php`, abgemeldet, am Handy im Hochformat | Die Karte nutzt fast die ganze Breite; darunter, mittig, „Was ist NAdoku? · Handbuch · Impressum · Datenschutz" | Die Karte ist schmal (rund die Hälfte der Breite), die Verweise stehen rechts daneben | offen |
| P-02 | Anmeldeseite am Rechner (Nr. 289) | Staging, `login.php`, Fenster breit | Karte mittig, Verweise darunter | Karte nach links geschoben, Verweise rechts daneben | offen |
| P-03 | Die übrigen Seiten der Hülle unverändert | Staging: „Passwort vergessen?" und — wenn die Registrierung offen ist — „Konto anlegen", am Handy | Karte wie gewohnt, mittig, volle Breite | Karte schmal, verschoben oder nicht mittig | offen |
| P-04 | Produktiv nach dem Tag `web-v20.37.3` | Fußzeile der Anmeldeseite ansehen; Betrieb → Updates | Fußzeile nennt **20.37.3**; Updates meldet **nichts ausstehend** (keine Migration) | Andere Fassung in der Fußzeile, oder eine ausstehende Migration | offen |

**Wenn alle vier abgehakt sind, wird dieses Dokument gelöscht** (`CLAUDE.md`
7); die Zeile in `Rahmenplan.md` 6 dazu.
