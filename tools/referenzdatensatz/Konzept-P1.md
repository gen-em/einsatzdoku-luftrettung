# Konzept P1 — Referenzdatensatz und Demo-Account

Programm: Gen-EM NAdoku (Rahmenplan, Phase P1)
Dieses Dokument: Phasenkonzept nach K1 — Befund, Entscheidungen (E),
offene Fragen (F), Arbeitspakete mit Abnahmekriterien, Prüfprotokoll,
Fehlerfunde. Es ist die Übergabeeinheit an die umsetzende
Claude-Code-Instanz und wird von ihr fortgeschrieben.

Dieses Dokument liegt seit B1 im Repositorium unter
`tools/referenzdatensatz/Konzept-P1.md` und wird dort fortgeschrieben —
Fortschreibungen sind damit versioniert und neben dem Erzeugnis lesbar.

Keine Versionsnummern in diesem Dokument (K3). Standardmodell der
Umsetzung ist Opus; **P1 enthält keinen Fable-Schritt** (K2/K8).
Fehlerfunde werden gesammelt, nicht sofort behoben (K4).
Je Arbeitspaket ein Commit, gepusht wird einmal am Phasenende nach
ausdrücklicher Bestätigung — ein Push auf `main` deployt sofort (K7).

---

## 1. Ziel

Ein **generierter**, vollständiger Beispieldatensatz (30–40 fiktive
Einsätze über 2026, R4) mit zwei Rollen:

1. **Demo-Account** auf der Produktivinstallation — vorzeigbar und
   **ausprobierbar** (beschreibbar), vom Admin anlegbar und
   automatisch wie manuell auf den Standardzustand zurücksetzbar.
2. **Regressionsreferenz** — das Projekt hat bisher keinerlei Tests.
   Kanonische Referenz-Exporte (Nutzer-Export CSV und edbak) dienen als
   Vergleichsdateien für den Kreislauftest importieren → exportieren →
   vergleichen und als Sicherheitsnetz für alle Folgephasen (P2–P6).

Dazu zwei programmweite Nebenaufträge:

- **Vorarbeit R19:** Beim Erzeugen und Einspielen der Ingest-Payloads
  wird das reale Aufrufverhalten festgehalten (Teilstücke je Dienst,
  zeitlicher Abstand, Spitzen) — Bemessungsgrundlage für den
  P5-Entwurf der Mengenbremse. Erhebung, keine Schutzmaßnahme.
- **Dauer-Regressionsfall R20:** Mindestens ein Einsatz trägt im
  Altersfeld einen Angriffswert (HTML-/Skriptmarker), damit die
  Maskierung der Einsatztabellen dauerhaft mitgeprüft wird.

## 2. Befund (statische Analyse des Bestands)

Grundlage: `docs/JSON-Vertrag.md`, `docs/Export-Format.md`,
`docs/Backup-Format.md`, `server/schema.sql`, `server/ingest.php`,
`server/api/day.php`, `server/api/import_commit.php`,
`server/einsatz_form.php`, `server/assets/crypto.js`,
`server/adminbackup_lib.php`, `server/admin_users.php`,
`server/trash_lib.php`, `server/api/suchindex.php`,
`.github/workflows/deploy.yml`.

- **B-01 — Vier legitime Schreibwege existieren:**
  1. `ingest.php` (Geräteschnittstelle, Header `X-Device-Id`/`X-Api-Key`,
     Vertrag `docs/JSON-Vertrag.md` 1.3): legt Einsätze, Ruhe-Segmente
     und **neutrale** Diensttage an, nimmt Phasen, Reanimationen und
     Trackpunkte inkrementell entgegen. Grenzen: 512 KB Body, ≤ 2000
     Punkte je Anfrage (Richtwert 500), Phasen 2–9, Koordinaten- und
     Mengenprüfungen je Feld. Deaktivierte Geräte (`active=0`) sind vom
     Upload gesperrt.
  2. `einsatz_form.php` (Session-POST mit CSRF): schreibt alle
     Einsatzfelder aus `mission_fields.php`, Phasen, Reanimationen und
     nimmt `pat_blob` ausschließlich als Chiffretext entgegen
     (`pruef_pat_blob`).
  3. CSV-Rückimport `export_csv_v1` (`import.php` → Browser →
     `api/import_commit.php`): liest `einsaetze.csv` (auch aus dem
     ZIP), verschlüsselt geschützte Felder im Browser, legt Einsätze
     mit `origin=import` und Präfix `imp-` an; GPX-Tracks werden
     bewusst **nicht** eingelesen.
  4. Backup-Wiederherstellung (`api/backup_restore.php`, browserseitig):
     Dubletten (`client_ref`) werden vollständig **übersprungen**, nie
     zusammengeführt.
- **B-02 — Kryptographie ist skriptseitig nachbildbar:** Anmeldetoken
  und Schlüsselableitung sind PBKDF2-SHA256 (Salt via `auth_salt.php`,
  Rundenzahl je Konto), Inhaltsschlüssel liegt passwortverpackt in
  `users.pat_wrap_pw`, Chiffretexte sind `edk1:` + Base64(IV‖AES-256-GCM).
  Alles ist in `crypto.js` und `docs/Backup-Format.md` (inkl.
  Python-Beispiel) dokumentiert. Ein Werkzeug kann sich damit regulär
  anmelden und über die echten Endpunkte einspielen — **ohne** an
  Validierung oder Verschlüsselung vorbeizugehen (R4).
- **B-03 — Zuordnung neutraler Diensttage:** `POST api/day.php`
  (JSON `{day_id, vehicle_id, base_id, crew, notes}`, Header `X-CSRF`)
  ruft `dt_zuordnen()` — derselbe Weg wie Formular und Nachbearbeitung.
- **B-04 — Einschränkungen des CSV-Imports:**
  - Diensttage werden **nur über das Datum** aufgelöst und dabei auf den
    **ersten** Diensttag des Datums abgebildet
    (`ORDER BY started_at, id LIMIT 1`). Beim Überschreiben setzt der
    Einsatz-Update `day_id` auf diesen Tag — der Import taugt daher
    **nicht** zum Nachtragen an Einsätzen eines zweiten Dienstes am
    selben Kalendertag (er würde den Einsatz umhängen).
  - Bestehende Diensttage erhalten über den Import **keine**
    Standort-/Rettungsmittel-Zuordnung (nur Besatzung im Modus
    `update`).
  - Ein Datum, dessen Diensttag im Papierkorb liegt, wird abgelehnt
    („Ablehnen statt zurückholen").
- **B-05 — Papierkorb:** `TRASH_DAYS = 90`, danach endgültig; beim
  endgültigen Entfernen wandert die `client_ref` auf die Sperrliste
  (`deleted_refs`), `man-` ausgenommen. Papierkorb-Inhalte erscheinen
  **weder** im Export **noch** im Backup — sie sind nur am laufenden
  System prüfbar.
- **B-06 — Kontoanlage heute nur per Einladung:** `admin_users.php`
  legt Konto ohne Passwort an und erzeugt einen Reset-Link; Passwort
  und Schlüsselmaterial entstehen erst im Browser der NutzerIn
  (`pw_handling.php`). Einen Demo-Mechanismus gibt es nicht.
- **B-07 — Admin-Sicherung:** serverseitiges JSON je Konto unter
  `server/sicherungen/<kontokennung>/` mit dem inneren Backup-JSON,
  `pat_blob` bleibt Chiffretext, `schluessel` enthält `pat_wrap_rc` und
  `pat_key_check`. Eine **serverseitige** Wiederherstellung existiert
  nicht; der vorhandene Weg läuft über Freigabe + Browser
  (`api/adminbackup_freigabe.php` → `api/backup_restore.php`).
  Geräte (`devices`) sind im Backup-Format **nicht** enthalten.
- **B-08 — Deploy:** synchronisiert ausschließlich `server/`
  (FTP, Ausnahmeliste u. a. `config.php`, `sicherungen/`). Inhalte, die
  der Produktivserver braucht, müssen unter `server/` liegen; `tools/`
  wird nie ausgeliefert.
- **B-09 — Neutrale Diensttage und A12:** Die Nachbearbeitungsseite
  (`nachbearbeitung.php`) verschwindet erst, wenn **keine** Diensttage
  ohne Zuordnung mehr existieren; erst dann wird `base_id` in den
  Stammdatentabellen auf NOT NULL gezogen. Ein dauerhaft neutraler
  Diensttag im Demo-Konto würde das für die gesamte Installation
  blockieren.
- **B-10 — Dienstzeitraum:** `days.started_at/ended_at` werden von den
  Schreibwegen fortgeschrieben (`dt_zeitraum_fortschreiben`); die Uhr
  liefert sie über ihre Uploads, von Hand angelegte Tage über
  `diensttag_neu.php`.
- **B-11 — Ratenschutz zählt nur Fehlversuche:** `ratelimit_lib.php`
  führt Töpfe `login`, `salt`, `reset`, `pair` über Fehlversuche je IP
  und Kennung. Ein **veröffentlichtes** Passwort läuft daran vorbei —
  erfolgreiche Anmeldungen werden nirgends begrenzt.
- **B-12 — Suche ist rein lesend:** `api/suchindex.php` ist GET-only
  und liefert den Bestand je Sitzung an den Browser; es gibt keinen
  serverseitig gespeicherten Suchindex. Ebenso sind Export
  (`api/export_data.php`) und Backup-Erstellung (`api/backup_data.php`)
  lesend.
- **B-13 — Anfragegetriebene Aufräumjobs:** Wiederkehrende Arbeiten
  (Papierkorb-Verfall, `rate_limits`-Bereinigung, Sperrlisten-Verfall)
  laufen ohne Cron, angestoßen im Zuge normaler Anfragen — dieses
  Muster steht für den automatischen Demo-Reset zur Verfügung.

## 3. Entscheidungen

Phaseninterne Entscheidungen; programmweite stehen im Rahmenplan
(hier maßgeblich: R4, R11, R12, R19, R20).

| Nr. | Entscheidung |
|---|---|
| E-P1-01 | **Kanalarchitektur nach Erfassungsart.** (a) Luft- und Bodeneinsätze **mit aufgezeichnetem Track** entstehen als Payloads über `ingest.php` mit einem echten, regulär angelegten Gerät des Demo-Kontos — das testet zugleich den Einspeiseweg (R4). Die neutralen Diensttage werden anschließend per `POST api/day.php` zugeordnet (B-03). (b) Das Nachtragen der Nachbearbeitungs- und Patientenfelder an diesen Einsätzen erfolgt per Skript über `einsatz_form.php` (Session, CSRF, `pat_blob` als `edk1:`-Chiffretext) — adressiert je Einsatz-ID und damit frei von der Import-Einschränkung B-04. (c) **Nachträglich erfasste** Einsätze (nur Start-/Zielkoordinaten, kein Track) kommen mehrheitlich über den **CSV-Import** im Browser herein (`origin=import`); mindestens einer wird von Hand über das Einsatzformular angelegt (`origin=manual`), damit alle drei Herkünfte vertreten sind. Kein roher SQL-Weg (R4). |
| E-P1-02 | **Region:** reale Geographie am Alpenrand (Allgäu-artig) — nötig für plausible Tracks, Höhen und Kartendarstellung —, aber **fiktive Namen** für Standorte, Rettungsmittel, Kliniken, Besatzung und Bergwacht-Einheiten. Keine realen Rufnamen (z. B. kein „Christoph …"). Personen- und Adressangaben sind frei erfunden. |
| E-P1-03 | **Bodentracks:** einmalige Erzeugung über einen Routing-Dienst (z. B. OSRM) **zur Generierungszeit**; die Ergebnis-Geometrie wird ins Repo eingecheckt. Keine Laufzeitabhängigkeit — Einspielen und Regression funktionieren offline. Lufttracks werden geometrisch erzeugt (Großkreis/Kurven mit plausiblen Geschwindigkeits- und Höhenprofilen). |
| E-P1-04 | **Quellformat:** JSON ist die führende Quelle des Generators (ein Dokument je Einsatz/Dienst, bildet Vertrag und Felder direkt ab). GPX ist **abgeleitetes Sichtprüfformat**, keine Quelle — es kann Phasen, Reanimation und Felder nicht tragen. |
| E-P1-05 | **Stammdaten** (Standorte, Rettungsmittel samt Rollen/Fähigkeiten, Zielkliniken, Besatzungs-Vorbelegungen, weitere Rettungsmittel, Bergwacht-Einheiten) legt das Einspielskript über die regulären Einstellungs-Endpunkte an. Der gesamte Datensatz ist damit aus einem leeren Konto reproduzierbar. Zentrale (Admin-)Stammdaten bleiben außen vor — das Demo-Konto ist ein normales Nutzerkonto. |
| E-P1-06 | **Zielumgebung:** Der Referenzzustand entsteht auf der **Produktivinstallation** (eigenes Demo-Konto). Ein vollständiger Probelauf gegen eine lokale Installation vorab ist zulässig und empfohlen; maßgeblich ist der Produktivlauf. |
| E-P1-07 | **Ablage:** Generator, JSON-Quelldaten, erzeugte Payloads, Importdateien, Messprotokoll, Referenz-Exporte und Vergleichswerkzeug unter `tools/referenzdatensatz/` (vom Deploy nicht berührt, B-08). Nur was der Produktivserver für die Demo-Funktion braucht, liegt unter `server/demo/` (E-P1-08). |
| E-P1-08 | **Demo-Account-Funktion im Adminbereich** (aus F5-Klärung): Der Adminbereich erhält „Demo-Konto anlegen" und „Demo-Konto auf Standard zurücksetzen" (manuell; zusätzlich automatisch nach E-P1-18). Mechanik: Das Werkzeug erzeugt nach dem Einspiellauf eine **Fixture** (`server/demo/`) aus (1) dem Schlüssel- und Anmeldematerial des Demo-Kontos (E-Mail, `password_hash` des abgeleiteten Anmeldetokens, `kdf_salt`, `kdf_iter`, `pat_wrap_pw`, `pat_wrap_rc`, `pat_key_check`, `account_key`), (2) dem Datenbestand im inneren Backup-JSON (Chiffretexte unverändert, B-07), (3) den Geräten (`device_id`, `api_key_hash`, `label`, `active=1`) samt `day_refs` und (4) dem Nachlauf-Drehbuch nach E-P1-21. „Anlegen" installiert Kontozeile + Fixture. „Zurücksetzen" löscht **alle** Bestände des Demo-Kontos — Diensttage, Einsätze, Ruhezeiten, Stammdaten, Geräte einschließlich besucherangelegter, Kopplungscodes, offene Passwort-Reset-Einträge, Papierkorb und Sperrlisten-Einträge (`deleted_refs`) der Demo-Geräte — und spielt die Fixture erneut ein, **einschließlich Konto- und Schlüsselmaterial**, sodass selbst eine unerwartet gelungene Kontoänderung folgenlos bliebe. Die Chiffretexte bleiben dabei gültig, weil der Inhaltsschlüssel unverändert aus der Fixture kommt — der Server spielt `pat_blob` **ohne jede Entschlüsselung** unverändert ein; ein Browser ist nicht beteiligt. Die Wiederherstellung läuft serverseitig über eine mit `api/backup_restore.php` **geteilte** Einspielroutine (Refactoring in `backup_lib.php`) — dieselbe Validierung, kein zweiter Rückspielpfad mit eigenen Fehlern, kein roher SQL-Weg. Kennzeichnung des Demo-Kontos über `app_state` (`demo_user_id`); Anlegen und Zurücksetzen sind transaktional und wirken ausschließlich auf dieses Konto. |
| E-P1-09 | **Sicherheitsrahmen der Demo-Funktion:** Zugangsdaten **und** Geräteschlüssel des Demo-Kontos sind planmäßig öffentlich, und sein Schlüsselmaterial liegt planmäßig auf dem Server — eine bewusste, eng begrenzte Ausnahme vom E2E-Prinzip, zulässig **nur** für dieses eine synthetische Konto mit rein fiktiven Daten (Rolle `user`). Schutzschichten: gesperrte Konto-Identität (E-P1-19), Anmelde-Mengenbremse (E-P1-20), automatischer Reset (E-P1-18) einschließlich Wiederherstellung des Schlüsselmaterials (E-P1-08) sowie die vorhandenen Größen- und Mengengrenzen von `ingest.php`. **Benannte, hingenommene Restrisiken:** Besucherinhalte (auch unerwünschte) und Massendaten sind innerhalb eines Reset-Fensters sichtbar bzw. vorhanden — begrenzt durch das 30-Minuten-Fenster, die Bodygrenzen und das Banner; die Grundsatzentscheidung zur Ingest-Mengenbremse fällt nach R19 in P5 und deckt dann auch das Demo-Konto mit ab. Handbuch, Demo-Banner und Adminseite weisen darauf hin, dass im Demo-Konto niemals echte Daten erfasst werden dürfen und Änderungen regelmäßig verworfen werden. Diese Konstruktion ist ausdrücklich Prüfgegenstand des R17-Reviews in P6. |
| E-P1-10 | **Reihenfolge Konto und Schlüssel:** Das Demo-Konto wird zuerst einmalig über den regulären Weg angelegt (Einladung, Passwortvergabe im Browser mit dem festgelegten Demo-Passwort). Das Werkzeug leitet alle Schlüssel aus Passwort + `auth_salt.php` ab bzw. entpackt `pat_wrap_pw` — die Fixture (E-P1-08) übernimmt anschließend genau dieses Material. So passen Chiffretexte und Konto auf jeder Installation zusammen. |
| E-P1-11 | **Kein dauerhaft neutraler Diensttag** im Referenzzustand (B-09): Jeder Diensttag ist am Ende Standort und Rettungsmittel zugeordnet. Der neutrale Zwischenzustand wird trotzdem geprüft — als Prüfschritt während des Einspielens, nicht als Dauerzustand. |
| E-P1-12 | **Referenz-Exporte:** Nutzer-Export als CSV-Archiv (mit personenbezogenen Angaben, unverschlüsselt, inkl. `tracks/`) und eine edbak-Datei mit festem, dokumentiertem Backup-Passwort. Beide entstehen im Browser aus dem fertigen Referenzzustand und werden unter `tools/referenzdatensatz/referenz/` eingecheckt. Die edbak-Datei ist zugleich die vorgesehene Abnahmedatei für R11 in P6 („v1.0 liest 7.x-edbak"). |
| E-P1-13 | **Vergleichswerkzeug:** Ein Skript (Python, `tools/referenzdatensatz/vergleich/`) normalisiert flüchtige Anteile (interne IDs, `created_at`, Erzeugungszeitpunkte, Dateinamensdatum, ID-abhängige Trackdateinamen, App-Version in `LIESMICH.txt`) und vergleicht einen aktuellen Export feldgenau gegen die Referenz. Chiffretexte werden nicht verglichen (IV-Zufall); verglichen wird der Klartext der Exporte. Ergebnis: maschinenlesbarer Abweichungsbericht. |
| E-P1-14 | **Messprotokoll (R19-Vorarbeit):** Das Einspielskript bildet das Sendeverhalten der Uhr nach (`watch/source/Uploader.mc` ist Referenz: Chunking ≤ 500 Punkte, Teil-Uploads bei Phasenwechseln, Ruhe-Segmente periodisch). Da das Einspielen schneller läuft als Echtzeit, werden die **Soll-Zeitpunkte** aus dem simulierten Dienstverlauf analytisch protokolliert (nicht die Wanduhr des Replays): Anfragen je Dienst, Teilstücke je Einsatz, Abstände, Spitzenwerte, dazu die Zahl der Fehlversuche 0. Ablage als `messprotokoll.json` + kurze Auswertung in Markdown. |
| E-P1-15 | **R20-Angriffswert**, fortgeschrieben in B1. Ursprünglich sollte der HTML-/Skriptmarker über den **CSV-Import** ins Altersfeld (`pat_alter`). Das ist über diesen Weg nicht möglich (Fehlerfund F-P1-A). Geltende Fassung: (a) Marker in den geschützten **Freitextfeldern** — Diagnose, Ortsbeschreibung, Einsatznummer, Einsatzort-Adresse — über den CSV-Import, wo der `trim`-Parser sie unverändert annimmt (D15/IMP-01 und IMP-02); (b) Marker **im Altersfeld** über den Nachtrag per `einsatz_form.php` (D15/`m-11-6127408395`), also über den Kanal, den E-P1-01(b) für alle Nachbearbeitungs- und Patientenfelder ohnehin vorsieht. Beide bleiben dauerhaft im Referenzzustand und in den Referenz-Exporten; die Fixture trägt sie über jeden Reset weiter. Zusätzlich enthalten Freitextfelder CSV-kritische Werte (Semikolon, Anführungszeichen, Zeilenumbruch, Formel-Anfangszeichen `=`,`+`,`-`,`@`) als Dauerfälle für Quoting und Formelschutz. |
| E-P1-16 | **Sperrlisten-Prüfung als Ablaufschritt:** Ein Einsatz wird eingespielt, in den Papierkorb gelegt, endgültig gelöscht und sein Payload erneut gesendet — erwartet: keine Wiederanlage (`deleted_refs`). Dieser Fall ist Prüfschritt des Einspiellaufs, kein Dauerzustand. |
| E-P1-17 | **Kein Fable-Schritt in P1.** Alle Pakete laufen mit dem Standardmodell (K2). |
| E-P1-18 | **Das Demo-Konto ist beschreibbar** (Klärung aus dem Konzeptgespräch): Besucher können Einsätze und Diensttage anlegen, ändern und löschen, Stammdaten pflegen und Geräte koppeln — die Funktionen sollen ausprobierbar sein. Dafür wird das Konto **automatisch alle 30 Minuten auf den Standardzustand zurückgesetzt** (Mechanik E-P1-08). Auslösung anfragegetrieben nach dem Muster der vorhandenen Aufräumjobs (B-13): Bei Web-Anfragen des Demo-Kontos und bei `ingest.php`-Anfragen von Demo-Geräten wird `app_state` (`demo_letzter_reset`) geprüft und bei Überschreitung **zuerst** zurückgesetzt — wer nach längerer Ruhe kommt, sieht immer den Standardzustand. Höchstdrift 30 Minuten relativ zu jeder Aktivität; ein Zeitdienst (Cron) wird nicht vorausgesetzt. Nebenläufige Anfragen während eines Resets dürfen scheitern, aber nichts beschädigen (Transaktion). Die Oberfläche zeigt im Demo-Konto dauerhaft ein Banner: fiktive Daten, Ausprobieren erwünscht, automatische Rücksetzung alle 30 Minuten, keine echten Daten erfassen. |
| E-P1-19 | **Gesperrt ist ausschließlich die Konto-Identität:** E-Mail-Änderung, Passwortänderung, KDF-Upgrade (`api/kdf_upgrade.php`) und — falls vorhanden — eine Kontolöschung durch das Demo-Konto selbst werden an den betroffenen Endpunkten mit freundlichem Hinweis abgewiesen; zusätzlich weist `reset_request.php` die Demo-Adresse ab (kein Passwort-Reset-Weg, kein E-Mail-Versand). Alles Übrige bleibt offen — ausdrücklich auch Geräteverwaltung und Kopplung (`pair.php`) samt Uploads über `ingest.php`. |
| E-P1-20 | **Anmelde-Mengenbremse:** Neuer Topf `demo` in `ratelimit_lib.php`, der — anders als die bestehenden Töpfe (B-11) — **erfolgreiche** Anmeldungen am Demo-Konto zählt: je IP **und** zusätzlich global je Zeitfenster. Die bewährte Tabelle `rate_limits` wird mitgenutzt; Grenzwerte werden in der Umsetzung festgelegt und hier als Fortschreibung nachgetragen. |
| E-P1-22 | **Umfang und Ausgewogenheit** (Festlegung in B1, ersetzt „30–40 Einsätze" in Abschnitt 5): **16 Diensttage, 8 luftgebunden und 8 bodengebunden, mit 87 Einsätzen** — im Schnitt knapp sechs je Dienst. Zwei Gründe. Erstens die Abdeckung: Ein Verhältnis von 11 Luft- zu 3 Bodendiensten prüft die bodengebundene Hälfte der Anwendung (Rollensatz `driver`/`trainee`, Standort ohne Koordinaten, fehlende Fähigkeiten, Straßentracks) an einem Bruchteil der Fälle. Zweitens die Glaubwürdigkeit als Demo: Ein Diensttag mit zwei Einsätzen sieht aus wie ein Datensatz, einer mit sechs wie ein Dienst. Preis, bewusst bezahlt: größere Fixture, längerer Einspiellauf, längere Kreislauftests. |
| E-P1-23 | **Prüffälle von Hand, Betriebsalltag erzeugt** (Festlegung in B1): Jeder Einsatz, der eine Zeile der Abdeckungsmatrix belegt, ist von Hand geschrieben und trägt eine Begründung im Dokument. Der Rest entsteht aus `quelldaten/aufbauen.py` und `katalog.py`, deterministisch bei festem Samen, und ist mit `"erzeugt": true` gekennzeichnet. Die erzeugten Einsätze werden bei einem erneuten Lauf ersetzt, die handgeschriebenen nie. **Die Quelle bleiben die eingecheckten JSON-Dokumente** (E-P1-04); `aufbauen.py` ist ein Schreibgehilfe, kein zweiter Datenweg. |
| E-P1-21 | **Papierkorb-Dauerzustand über Reset-Nachlauf:** Das Backup-Format kennt keine gelöschten Einträge (B-05). Statt eines Fixture-Sonderformats führt der Reset nach dem Einspielen ein kleines Drehbuch aus: benannte Einsätze und Diensttage werden über die regulären Löschwege (`trash_lib.php`) in den Papierkorb gelegt. So bleibt die geteilte Einspielroutine formattreu, und die Papierkorb-Abdeckung (Abschnitt 5) übersteht jeden Reset. |

## 4. Offene Fragen

| Nr. | Frage | Vorschlag | Zu entscheiden vor |
|---|---|---|---|
| F-P1-01 | ~~Zugangsdaten des Demo-Kontos~~ | **GEKLÄRT vor B1:** `demo@gen-em.org`, Passwort `nadokudemo0815`, Backup-Passwort der Referenz-edbak ebenfalls `nadokudemo0815`. Nennung im Handbuch und auf der Adminseite. | erledigt |
| F-P1-02 | ~~Übergabe der Luftrettungs-Beispieldatei~~ | **GEKLÄRT vor B1:** Datei übergeben und entschlüsselt (Container v2, Nutzlastversion 5; 10 Diensttage, 50 Einsätze). Sie dient wie vorgesehen als **inhaltliche Vorlage** für Tonfall und Feldbelegung, nicht als Importquelle — Nutzlastversion 5 wird von der Anwendung nicht mehr eingelesen (`error: version_alt`), was für diesen Zweck folgenlos ist. | erledigt |

**Zwei weitere Klärungen vor B1**, beide vom Auftraggeber entschieden:
Der Umfang steigt auf 16 ausgewogene Diensttage (E-P1-22), und der
R20-Wert im Altersfeld kommt über den Nachtrag statt über den CSV-Import
(E-P1-15, Fehlerfund F-P1-A).

Geklärt im Konzeptgespräch (bereits als E überführt): Kanalwahl
(E-P1-01), Region (E-P1-02), Routing (E-P1-03), Quellformat (E-P1-04),
Stammdaten-Anlage (E-P1-05), Zielumgebung (E-P1-06), Ablage (E-P1-07),
Demo-Funktion mit Anlegen/Zurücksetzen (E-P1-08), beschreibbares
Demo-Konto mit 30-Minuten-Reset statt Schreibschutz (E-P1-18),
Sperrumfang nur Konto-Identität inkl. Reset-Request-Abweisung
(E-P1-19), Anmelde-Mengenbremse (E-P1-20).

## 5. Abdeckungsmatrix (Soll)

Verbindliche Mindestabdeckung; die konkrete Einsatzliste entsteht in B1
und weist jede Zeile dieser Matrix mindestens einem Einsatz/Dienst zu.

**Gesamtumfang (fortgeschrieben in B1, E-P1-22): 16 Diensttage — 8
luftgebunden, 8 bodengebunden — mit 87 Einsätzen**, verteilt über das Jahr
2026 mit plausiblen Häufungen (nicht gleichverteilt). Der ursprüngliche
Ansatz von 30–40 Einsätzen stammte aus einem Entwurf mit deutlich weniger
Bodendiensten; er prüfte die bodengebundene Hälfte der Anwendung an einem
Bruchteil der Fälle und ließ einen Diensttag wie einen Datensatz aussehen
statt wie einen Dienst.

| Dimension | Mindestens abzudecken |
|---|---|
| Erfassungsart (R4) | luftgebunden mit Track (Ingest) · bodengebunden mit Track (Ingest) · nachträglich ohne Track, nur Start-/Zielkoordinaten (Import bzw. Formular) |
| Herkunft | `watch` · `manual` · `import` — je ≥ 1 |
| Diensttage | Luft- und Bodendienste; ≥ 1 Kalendertag mit **zwei** Diensten (`day_ref`-Zuordnung); ≥ 1 Dienst über Mitternacht (Einsatzdatum ≠ Diensttag); Diensttag **ohne** Einsatz; Tagesnotizen |
| Besatzung | alle Rollen des Katalogs belegt (Luft: p1, p2, hems, fr, other · Boden: driver, trainee, other); ≥ 1 Einsatz mit abweichender Besatzung (`crew_override`) |
| Phasen | alle Phasen 2–9 im Datensatz; ≥ 1 Einsatz mit Mehrfacheintrag derselben Phase (Korrektur); ≥ 1 Einsatz mit unvollständigen Phasen (Dauer leer); ≥ 1 nicht abgeschlossener Einsatz (`final=0`, `ended_at` leer) |
| Reanimation | ≥ 1 Einsatz mit einer Sitzung; ≥ 1 mit **mehreren** Sitzungen; alle **speicherbaren** Ereignisarten kommen im Datensatz vor (inkl. `rosc` und `tod`). Das sind **neun**, nicht zehn: `beginn` nimmt kein Schreibweg als Ereignis an — siehe Fehlerfund F-P1-F |
| Transport | `air` · `ground` · `ambulant` · leer; NA-Begleitung; Fehleinsatz/Storno; Sekundärtransport; Schockraum; Zielklinik mit und ohne Koordinate |
| Abfahrtort | alle vier Regeln `base` · `prev_site` · `prev_dest` · `manual` (letztere mit verschlüsseltem `pat.start`) |
| Luftspezifik | Winde (mit Cycles, Cycles mit Patient, Luftverladung) nur an windenfähigem Rettungsmittel; Bergwacht mit Einheit und `bw_info` |
| Geschützte Angaben | Einsatz mit Geburtsdatum (Alter gerechnet) · Einsatz mit Handalter (`pat_alter`, darunter der R20-Angriffswert) · Diagnose · Einsatzort mit Adresse+Koordinate · Ortsbeschreibung · Einsatznummer · Einsatz **ohne** jede geschützte Angabe |
| Sonderzeichen | Freitexte mit Semikolon, Anführungszeichen, Zeilenumbruch, Formel-Anfangszeichen, Umlauten/ß (E-P1-15) |
| Ruhezeiten | Segmente mit Track; mehrere Segmente je Dienst; nicht abgeschlossenes Segment |
| Papierkorb | ≥ 1 gelöschter Einsatz und ≥ 1 gelöschter Diensttag (mit `deleted_with_day`-Einsätzen) als Dauerzustand — nach jedem Reset über den Nachlauf wiederhergestellt (E-P1-21); Sperrlisten-Fall als Ablaufschritt (E-P1-16) |
| Stammdaten | ≥ 2 Standorte (einer mit, einer ohne Koordinaten); ≥ 2 Luft-Rettungsmittel (mit/ohne Fähigkeiten) und ≥ 1 Boden-Rettungsmittel; Zielkliniken mit/ohne Koordinate; Vorbelegungen aller Arten; Standard-Markierungen |
| Zeit | Einsätze in MEZ **und** MESZ; ≥ 1 Dienst im Umfeld einer Zeitumstellung 2026 |
| Weitere Felder | weitere Rettungsmittel (mehrere je Einsatz); weiterer Notarzt; Notizen an Einsatz und Diensttag; bearbeiteter Uhr-Einsatz (`edited=1`, `manual=1` als Nebenwirkung) |

## 6. Arbeitspakete

Reihenfolge B1 → B7; B4 folgt zwingend auf B3 (die zu aktualisierenden
Einsätze müssen existieren), B6 auf B5 (die Fixture entsteht aus dem
abgenommenen Referenzzustand).

### B1 — Einsatzliste und Quelldaten — **ERLEDIGT**
Abdeckungsmatrix (Abschnitt 5) in eine konkrete Liste von Diensttagen
und Einsätzen übersetzen; je Einheit ein JSON-Quelldokument
(E-P1-04) mit allen Feldern, Phasenzeiten, Reanimationen,
Track-Eckdaten und Kanalzuordnung (E-P1-01). Inhaltliche Vorlage ist
die Luftrettungs-Beispieldatei (F-P1-02); Namen und Orte nach E-P1-02.
Ein Matrix-Abgleichsdokument weist jede Matrixzeile den Einsätzen zu.
**Abnahme:** Jede Zeile der Matrix ist mindestens einmal zugewiesen;
Umfang nach E-P1-22; JSON-Schema der Quelldokumente liegt bei und
alle Dokumente validieren dagegen; keine realen Namen.

**Stand.** Abgenommen. Erzeugt unter `tools/referenzdatensatz/quelldaten/`:
16 Dienstdokumente (`dienste/D01…D16.json`), `stammdaten.json`, der
Sperrlisten-Prüfschritt unter `pruefschritte/`, zwei JSON-Schemata,
`FORMAT.md` als Formatbeschreibung, `katalog.py` und `aufbauen.py`
(Betriebsalltag, E-P1-23), `wegpunkte.py` (Wegpunktauflösung, geteilt mit
dem Generator) und `pruefen.py`. Der Matrix-Abgleich (`matrix_abgleich.md`)
wird aus `pruefen.py --matrix` **erzeugt** statt gepflegt — ein von Hand
geführtes Abgleichsdokument ist nach der zweiten Änderung falsch und
behauptet trotzdem weiter eine Abdeckung, die es nicht mehr gibt.

**Gemessen** (`pruefen.py`): 87 Einsätze, 99 Ruhesegmente, 1 122
Zeitstempel auf Existenz und Eindeutigkeit geprüft, **5 528
Einzelprüfungen, keine Befunde**; 78 Matrixzeilen, davon **0 offen**.
Determinismus von `aufbauen.py`: zwei Läufe, 16 Dateien byteweise gleich.

**Vorgezogen aus B2:** Da der Netzzugang zu `router.project-osrm.org`
freigegeben wurde, ist die Straßengeometrie der Bodeneinsätze bereits
abgerufen und eingecheckt (E-P1-03): 117 Teilstücke, 84 verschiedene
Strecken, unter `generator/routen/`. Der Generator läuft damit offline.

**Drei Befunde, die B1 selbst hervorgebracht hat** und die als Prüfungen
stehen geblieben sind, statt nur behoben zu werden:
1. Ein Zeitstempel lag in der **übersprungenen Stunde** der
   Frühjahrsumstellung (29.03.2026, 02:00 MEZ). `pruefen.py` prüft
   seither jeden Zeitstempel auf Existenz **und** Eindeutigkeit.
2. Sechs Einsätze führten **standortfremde Vorbelegungen**. Fünf wurden
   auf den eigenen Standort gezogen; einer bleibt bewusst stehen und ist
   als Freitextfall gekennzeichnet — `other_resources` ist Freitext mit
   Vorschlagsliste, und ein Wert außerhalb der Liste muss vorkommen,
   sonst prüft der Datensatz nur den bequemen Teil des Feldes.
3. Mehrere **Routen-Wegpunkte lösten auf keine Koordinate auf**. Daraus
   entstand die Trennung von `spur` und `geschuetzt` (FORMAT.md): Spur
   und Phasenkoordinaten liegen in der Anwendung im Klartext,
   verschlüsselt ist die *Adresse*. Ein Einsatz ohne geschützte Angaben
   hat deshalb sehr wohl eine Spur.

### B2 — Generator
Werkzeug (Python, `tools/referenzdatensatz/generator/`), das aus den
Quelldokumenten erzeugt: (a) Lufttracks geometrisch, Bodentracks über
Routing mit eingecheckter Geometrie (E-P1-03), inkl. Höhenprofil;
(b) Ingest-Payloads in vertragskonformen Teilstücken samt
Soll-Sendeplan (Grundlage für E-P1-14); (c) Formulardaten für das
Nachtragen inkl. `edk1:`-Chiffretexte (B-02); (d) Importdateien im
`export_csv_v1`-Format für die nachträglichen Einsätze inkl.
R20-Angriffswert; (e) GPX-Ableitungen zur Sichtprüfung.
**Abnahme:** Generator läuft deterministisch (fester Zufallssamen)
offline durch; Payloads halten alle Vertragsgrenzen ein (Stichprobe
gegen `docs/JSON-Vertrag.md` 3.2); Bodentracks folgen Straßen
(Sichtprüfung GPX); Chiffretexte entschlüsseln mit dem Kontoschlüssel
zum Quell-Klartext.

**Stand: ERLEDIGT.** Unter `tools/referenzdatensatz/generator/`:
`erzeugen.py` (Hauptlauf), `spur.py` (Spuren), `gelaende.py`
(Höhenmodell aus rund fünfzig Stützpunkten), `krypto.py` (PBKDF2 und
AES-256-GCM nach `assets/crypto.js`), `pruefen.py`, `LIESMICH.md` und
`routen/` mit der eingecheckten Straßengeometrie.

**Gemessen** (`pruefen.py`): 526 Ingest-Anfragen, 56 587 Trackpunkte,
**283 738 Einzelprüfungen, keine Befunde**. Determinismus: zwei Läufe,
692 Dateien byteweise gleich. Größter Body 20,2 KB gegen die Grenze von
512 KB. Die Teilstückbildung ist wirklich beansprucht — 166 Pakete gehen
in mehreren Anfragen hinaus, 18 davon mit genau 500 Punkten.

**Statt Stichprobe: alles.** Die Abnahme sah eine Stichprobe gegen die
Vertragsgrenzen vor. Geprüft wird stattdessen **jede** Anfrage gegen
**jede** Grenze. Eine Stichprobe beantwortet nicht die Frage, ob der
Datensatz vertragskonform ist, sondern nur, ob die gezogenen Stücke es
sind — und der Datensatz soll gerade die Grundlage sein, auf die sich
spätere Phasen verlassen.

**Straßentreue: erfüllt statt ausgewiesen.** Der Netzzugang zu
`router.project-osrm.org` wurde während B1 freigegeben. Die
Bodentracks folgen damit echten Straßen (117 Teilstücke, 84
verschiedene Strecken); dazu liegt eine **Fahrzeiten-Tafel** von 86
Paaren vor, mit der die Quelldaten den Einsatzort nach der echten
Fahrzeit wählen statt nach der Luftlinie.

### Was B2 an Fehlern hervorgebracht hat

Diese vier standen nicht im Konzept; sie sind beim Bauen aufgefallen
und jeder ist als **dauerhafte Prüfung** stehen geblieben.

1. **Der Rückweg gehörte nicht zum Einsatz.** Der Generator zählte den
   Weg von der Klinik zurück zum Einsatz und musste ihn in die Spanne
   zwischen Übergabe und Endzeit pressen — dabei entstanden Rückflüge
   mit 666 km/h. Richtig ist, was die Uhr tut: `_endMission` beendet den
   Einsatz und startet sofort ein Ruhe-Segment (`Model.mc`), der Rückweg
   wird **dort** aufgezeichnet. Die Ableitung steht jetzt einmal in
   `quelldaten/wegpunkte.py` (`tagesablauf`) und wird von Generator und
   Routenabruf gemeinsam benutzt.
2. **Der Einsatzort richtete sich nicht nach der verfügbaren Zeit.**
   Erzeugte Einsätze wählten den Ort frei aus dem Katalog, während die
   Phasen die Anfahrtszeit vorgaben — 45 km in sieben Minuten. Für die
   Straße genügte die Luftlinie dafür nicht: Im Voralpenland liegt ein
   Ort 15 km Luftlinie und 40 km Fahrstrecke entfernt. Deshalb die
   Fahrzeiten-Tafel.
3. **Das Geschwindigkeitsprofil überhöhte die Mitte um 57 Prozent.**
   Eine Kosinus-Glättung ist an den Enden richtig und in der Mitte
   falsch. Jetzt ein Trapez — beschleunigen, halten, bremsen —, das die
   Reisegeschwindigkeit nur rund 18 Prozent über den Mittelwert hebt.
4. **Die Spur sprang zwischen Halt und Fahrt.** OSRM rastet Anfang und
   Ende einer Route auf die nächste Straße; der Halt davor stand exakt
   auf dem Wegpunkt. Aus dem Versatz wurden 175 km/h. Die Halte sitzen
   jetzt am tatsächlichen Ende des Fahrabschnitts.

Dazu zwei Funde in den Quelldaten, die B2 sichtbar gemacht hat: Ein
NEF-Einsatzort lag auf 2 100 m (der Ortskatalog führte denselben Namen
mit zwei Koordinaten), und der Standort Talwang hatte nur eine
Zielklinik mit Koordinaten — 20 km entfernt, sodass jeder Bodentransport
zu schnell war. Beides ist in den Quelldaten behoben und wird von
`quelldaten/pruefen.py` mitgeprüft (Erreichbarkeit je Abschnitt und je
Ruhe-Segment).

### B3 — Einspiellauf (skriptgestützt)
Einspielskripte für den kompletten Lauf gegen eine Installation:
Demo-Konto regulär anlegen (E-P1-10, Zugangsdaten nach F-P1-01),
Stammdaten über die Einstellungs-Endpunkte (E-P1-05), Gerät anlegen,
Ingest-Replay nach Soll-Sendeplan mit Messprotokoll (E-P1-14),
Diensttag-Zuordnung per `api/day.php` (B-03), Nachtragen per
`einsatz_form.php` (E-P1-01b), manuelle Einsätze, Papierkorb-Fälle
und Sperrlisten-Prüfschritt (E-P1-16). Erst vollständiger Probelauf
lokal, dann Produktivlauf (E-P1-06).
**Abnahme:** Lauf ist aus leerem Konto wiederholbar; Messprotokoll
liegt vor (Anfragen je Dienst, Teilstücke, Abstände, Spitzen);
Stichprobe in der Oberfläche: Diensttage zugeordnet, Tracks sichtbar,
geschützte Angaben nach Freischalten lesbar; neutraler Zustand vor
Zuordnung wurde beobachtet (E-P1-11); keine Zeile entstand per SQL.

**Stand: ERLEDIGT (lokal).** Unter `tools/referenzdatensatz/einspielen/`:
`lokal_starten.sh` (MariaDB, PHP-Server, TLS davor), `sitzung.py`
(Anmeldung über den regulären Weg), `passwort_setzen.mjs` (Browser),
`einspielen.py` (neun Stufen), `messprotokoll.py`, `sichtpruefung.mjs`
und `LIESMICH.md`.

**Gemessen.** 526 Ingest-Anfragen, **0 Fehlversuche**, 0 verworfene
Einzelwerte, 0 übergangene Listen. Bestand danach: 16 Diensttage
(1 im Papierkorb), 78 sichtbare Einsätze (76 `watch`, 2 `manual`),
5 im Papierkorb (4 davon mit ihrem Diensttag), 95 Ruhesegmente,
55 861 Spurpunkte, 1 Sperrlisteneintrag. Die vier CSV-Importe folgen
in B4.

**Neutraler Zustand belegt (E-P1-11):** Vor der Zuordnung waren
**16 von 16** Diensttagen ohne Art — gezählt, nicht behauptet, und im
Lauf-Zustand festgehalten.

**Sperrliste (E-P1-16) bestanden:** senden → 1 Einsatz, Papierkorb → 0,
endgültig löschen → 0, **erneut senden → 0**. Der Einsatz kam nicht
wieder; `deleted_refs` trägt die Kennung.

**Sichtprüfung im Browser:** Diensttag mit Zuordnung im Titel, 6
Einsatzzeilen, 28 Spurpfade auf der Tageskarte und 9 auf der
Einsatzkarte, 8 Phasenzeilen, geschützte Angaben ohne weiteres Zutun
lesbar (gelesen: „Schädel-Hirn-Trauma bei Motorradunfall"),
**Konsole ohne Fehler**.

**Was dabei NICHT geprüft werden konnte** — und das gehört an diese
Stelle und nicht in eine Fußnote:

- **Kartenkacheln.** Sie kommen von `tile.openstreetmap.org` und
  Nachbarn (`assets/map_layers.js`); der Egress-Proxy dieser Umgebung
  lässt sie nicht durch. Geprüft ist, dass die **Spur** gezeichnet
  wird — nicht, dass der Kartenhintergrund erscheint.
- **Mailversand.** Kein SMTP lokal; der Einrichtungslink wurde von der
  Adminseite abgelesen statt aus einer Mail.
- **Der Produktivlauf** (P-12) steht weiterhin aus — dafür fehlt der
  Zugang.

### B4 — Browser-Schritte
Dokumentierte Klickstrecke für die Anteile, die bewusst im Browser
laufen: CSV-Import der nachträglichen Einsätze (inkl. R20-Fall,
Dublettenverhalten beachten, B-04), Sichtkontrolle der maskierten
Tabellen und der Suche (der Suchindex wird je Sitzung gelesen, nicht
gespeichert, B-12).
**Abnahme:** Importierte Einsätze tragen `origin=import`; der
Angriffswert steht inert (maskiert) in den Tabellen; Klickstrecke ist
als nummerierte Anleitung in `tools/referenzdatensatz/LIESMICH.md`
festgehalten.

### B5 — Referenz-Exporte, Vergleichswerkzeug, Kreislauftest
Referenz-Exporte erzeugen und einchecken (E-P1-12);
Vergleichswerkzeug bauen (E-P1-13); Kreislauftest ausführen:
Referenz-CSV in ein **frisches** Konto importieren → erneut
exportieren → vergleichen; erwartete, dokumentierte Abweichungen
(z. B. Tracks nicht im Importweg, explizit gespeicherte effektive
Besatzung, Herkunft `import`) landen als Ausnahmeliste im Werkzeug.
Ebenso: edbak in ein frisches Konto einspielen → exportieren →
vergleichen (hier ohne Track-Ausnahme). Für spätere Regressionsläufe
gilt: unmittelbar vor dem Vergleichs-Export das Demo-Konto
zurücksetzen (manuell oder automatischen Reset abwarten), damit
Besucheränderungen den Vergleich nicht verfälschen — so steht es auch
im LIESMICH.
**Abnahme:** Beide Kreisläufe laufen mit leerem Abweichungsbericht
(nach Ausnahmeliste) durch; das Werkzeug meldet eine absichtlich
eingebaute Testabweichung zuverlässig; Bedienung im LIESMICH
beschrieben.

### B6 — Demo-Account-Funktion im Adminbereich
Fixture aus dem Referenzzustand erzeugen (E-P1-08, unter
`server/demo/`), serverseitige Einspielroutine als geteilte Logik mit
`api/backup_restore.php` herausziehen, Admin-Oberfläche „Demo-Konto
anlegen / auf Standard zurücksetzen", automatischer 30-Minuten-Reset
samt Auslösepunkten (E-P1-18), Reset-Nachlauf für den Papierkorb
(E-P1-21), Sperren der Konto-Identität und Reset-Request-Abweisung
(E-P1-19), Anmelde-Mengenbremse (E-P1-20), Demo-Banner, Absicherung
nach E-P1-09, Handbuchhinweis. Deploy-Auswirkung beachten (K7, B-08).
**Abnahme:** Auf einer frischen Testinstallation: Anlegen erzeugt das
Konto, Anmeldung mit den Demo-Zugangsdaten gelingt, geschützte Angaben
sind lesbar, Bestandszahlen und Export entsprechen der Referenz
(Vergleichswerkzeug), der Papierkorb enthält die vorgesehenen Fälle.
Nach absichtlichen Veränderungen (Einsatz gelöscht, neuer angelegt,
Gerät gekoppelt, Stammdatum geändert) stellt der nächste automatische
Reset — geprüft durch Verstellen des `app_state`-Zeitstempels bzw.
Wartezeit — den Standardzustand einschließlich Konto- und
Schlüsselmaterial wieder her; besucherangelegte Geräte und
Kopplungscodes sind entfernt, geschützte Angaben danach weiterhin
lesbar. E-Mail-/Passwortänderung, KDF-Upgrade und Passwort-Reset
werden mit Hinweis abgewiesen; Kopplung und Ingest-Upload
funktionieren. Die Mengenbremse greift bei Überschreiten der
Grenzwerte. Beide Admin-Funktionen verweigern die Arbeit auf jedem
anderen Konto.

### B7 — Dokumentation und Abschluss
`tools/referenzdatensatz/LIESMICH.md` (Aufbau, Läufe, Regression),
Handbuch-Abschnitt Demo-Konto (inkl. Zugangsdaten nach F-P1-01 und
Hinweis auf Reset und fiktive Daten), `docs/Technik.md`-Ergänzung
(Demo-Mechanik, Fixture, Reset), Changelog- und Doku-Konsistenz,
Prüfdokument P1 nach K9 erstellen, Statuszeile im Rahmenplan
fortschreiben, Backlog-Durchsicht auf neue Funde (K4).
**Abnahme:** Prüfdokument-P1 liegt vor (Kurzfassung, maschinelle
Prüfungen mit Zahlen, Nichtprüfbares, abhakbare Prüfliste mit
Bedienweg/Erwartung/Bedeutung); Doku in sich konsistent.

## 7. Prüfprotokoll

Wird von der umsetzenden Instanz geführt (K5); Bedienprüfungen
zusätzlich im Prüfdokument-P1 (K9).

| Nr. | Prüfung | Paket | Stand |
|---|---|---|---|
| P-01 | Matrix-Abgleich vollständig | B1 | **erfüllt** — 78 Zeilen, 0 offen; 5 528 Einzelprüfungen ohne Befund (`pruefen.py`) |
| P-02 | Payloads gegen Vertragsgrenzen | B2 | **erfüllt** — statt Stichprobe ALLE 526 Anfragen; 283 738 Einzelprüfungen ohne Befund |
| P-03 | Determinismus des Generators (zwei Läufe, gleiches Ergebnis) | B2 | **erfüllt** — Quelldaten 16 Dateien, Generator 692 Dateien, je zwei Läufe byteweise gleich |
| P-04 | Lokaler Gesamtlauf aus leerem Konto | B3 | **erfüllt** — neun Stufen durchgelaufen, 526 Ingest-Anfragen ohne Fehlversuch |
| P-05 | Messprotokoll vorhanden und plausibel | B3 | **erfüllt** — `messprotokoll.md`: Spitze 14 Anfragen an einem Auslöser, 174 Abstände von 0 s, Median 1020 s |
| P-06 | Sperrlisten-Fall verhält sich wie erwartet | B3 | **erfüllt** — nach erneutem Senden 0 Einsätze, Eintrag in `deleted_refs` |
| P-07 | R20-Wert maskiert in allen Einsatztabellen | B4 | offen |
| P-08 | Kreislauf CSV mit leerem Abweichungsbericht | B5 | offen |
| P-09 | Kreislauf edbak mit leerem Abweichungsbericht | B5 | offen |
| P-10 | Demo anlegen/zurücksetzen auf frischer Installation | B6 | offen |
| P-11 | Demo-Funktionen wirken nur auf das Demo-Konto | B6 | offen |
| P-12 | Produktivlauf abgeschlossen, Stichprobe Oberfläche | B3/B4 | offen |
| P-13 | Automatischer 30-Minuten-Reset: Auslösung, Vollständigkeit inkl. Schlüsselmaterial und Papierkorb-Nachlauf, Aufräumen besucherangelegter Geräte/Codes | B6 | offen |
| P-14 | Sperren der Konto-Identität und Abweisung des Passwort-Resets | B6 | offen |
| P-15 | Anmelde-Mengenbremse Topf `demo` (je IP und global) | B6 | offen |

## 8. Fehlerfunde (gesammelt, K4)

### F-P1-A — Das Altersfeld ist über den CSV-Import nicht erreichbar

**Fundort:** `server/assets/import.js:165` (`PARSERS.alterJahre`),
`server/einsatz_form.php:1129` und `:1658`.

**Sache:** E-P1-15 benennt den CSV-Import als Vektor für einen
HTML-/Skriptmarker im Altersfeld (`pat_alter`). Über diesen Weg geht das
nicht: `alterJahre` gibt für jeden Wert, den `ganzzahl` nicht auflöst,
`{error: 'Alter: ganze Zahl erwartet'}` zurück, und das Feld wird
verworfen. Das Formularfeld `pat_age` ist `type=number min=0 max=120` und
wird mit `parseInt` übernommen.

**Wirkung:** Kein Fehler im Code — beide Prüfungen sind richtig. Falsch
war die Annahme im Konzept. Der Dauer-Regressionsfall wäre stillschweigend
entfallen: Der Import hätte den Wert verworfen und den Einsatz trotzdem
angelegt, und niemand hätte gemerkt, dass die Prüfung nicht mehr prüft.

**Blockierend:** nein.

**Verbleib:** E-P1-15 ist fortgeschrieben. Die Marker stehen jetzt in den
geschützten Freitextfeldern (CSV-Import, unverändert übernommen) **und**
im Altersfeld über den Nachtrag per `einsatz_form.php`. Letzteres ist kein
Umweg an der Prüfschicht vorbei: Der Server nimmt `pat_blob` bauartbedingt
nur als Chiffretext entgegen (`pruef_pat_blob`) und kann seinen Inhalt
nicht prüfen — die Zahlenprüfung ist eine Eingabehilfe des Browsers, keine
Zusicherung des Servers. **Genau deshalb ist die Maskierung beim Anzeigen
die Verteidigungslinie**, und genau die soll R20 dauerhaft absichern.

**Folge für B5:** Die Exportspalte `pat_alter` trägt diesen Wert; beim
CSV-Rückimport verwirft ihn `alterJahre` mit Hinweis. Das gehört als
benannte Ausnahme in die Ausnahmeliste des Vergleichswerkzeugs — und ist
zugleich der Beleg, dass der Parser tut, was er soll.

### F-P1-B bis F-P1-E — Funde des Generators

Vier Modellfehler im **Generator selbst** (nicht in der Anwendung),
gefunden beim Bauen von B2 und dort behoben; jeder ist als dauerhafte
Prüfung stehen geblieben. Beschreibung im Arbeitspaket B2 oben:
Rückweg am falschen Datensatz, Einsatzort ohne Rücksicht auf die
Anfahrtszeit, überhöhtes Geschwindigkeitsprofil, Sprung zwischen Halt
und Route. **Blockierend: nein**, alle behoben. Kein Verbleib im
Backlog — sie betreffen ausschließlich das Werkzeug dieser Phase.

### F-P1-F — Der JSON-Vertrag führt eine Reanimationsart, die kein Schreibweg annimmt

**Fundort:** `docs/JSON-Vertrag.md` Abschnitt 3.3 gegen
`server/ingest.php:299` und `server/einsatz_form.php:317`.

**Sache:** Der Vertrag nennt `beginn` unter den gültigen Werten von
`events[].type` und merkt dazu nur an, die **Uhr** führe bewusst eine
Teilmenge und kenne `beginn` nicht. Das liest sich als Zusage, der Server
nehme die Art an. Er nimmt sie auf **keinem** Weg an:

- `ingest.php:299` speichert das Ereignis **still nicht**
  (`$ty !== 'beginn'`) — ohne Eintrag in `rejected`.
- `einsatz_form.php:317` weist es mit der Meldung „Unbekannte Art eines
  Reanimationsereignisses" ab.

Beide begründen es gleich und **richtig**: Der Reanimationsbeginn steckt in
`started_at` der Sitzung; ein zweites Mal als Ereignis wäre er doppelt.
`RESUS_LABELS` (db.php) führt `beginn` weiterhin — dort als Beschriftung
für die Startzeile, nicht als Ereignisart. `pruef_reanimationsart` in
`validate_lib.php` lässt ihn durch; die Ausnahme steht an beiden
Schreibwegen **zusätzlich**.

**Wirkung:** Ein Client, der gegen den Vertrag implementiert, sendet ein
`beginn`-Ereignis und bekommt einen Eintrag weniger — auf dem Ingest-Weg
**ohne jede Meldung**. Genau der Fall, vor dem Abschnitt 0 desselben
Dokuments warnt: „Ein Vertrag, der etwas zusichert, was der Code nicht
einhält, ist schlimmer als gar keiner." Die Tabelle dort führt
„Reanimationsarten gegen die Liste (3.3)" als *durchgesetzt*.

**Gefunden** beim Einspielen von D11/MAN-01: Der Datensatz sollte alle
Ereignisarten belegen und trug `beginn` als Ereignis; das Formular wies
den Einsatz ab, und er entstand nicht.

**Blockierend:** nein. Der Datensatz führt den Beginn jetzt dort, wo er
hingehört — im Feld `beginn` der Sitzung. Speicherbare Ereignisarten sind
**neun**, nicht zehn; die Abdeckungsmatrix ist entsprechend
fortgeschrieben, und `quelldaten/pruefen.py` weist ein `beginn`-Ereignis
seither ab.

**Verbleib — zu entscheiden:** Entweder der Vertrag wird berichtigt (3.3
nennt neun Ereignisarten, `beginn` steht als Sitzungsbeginn daneben), oder
der Code nimmt die Art an. Der Vertrag nennt sich selbst die führende
Quelle und sagt, eine Abweichung sei „ein Fehler in der Umsetzung, nicht im
Vertrag" — hier spricht die Sache aber für den Code: Der Beginn ist eine
Eigenschaft der Sitzung, kein Ereignis in ihr. **Vorschlag: Vertrag
berichtigen**, dazu ein Backlog-Eintrag. Nicht in dieser Phase entschieden.

*Weitere Funde während der Umsetzung hier eintragen (Fundort, Wirkung,
blockierend ja/nein, Verbleib → Backlog/Phase).*

## 9. Statuspflege

Nach jedem Paket: Abnahmekriterien abhaken, Prüfprotokoll
fortschreiben, Abweichungen als P-Einträge begründen. Am Phasenende:
Rahmenplan Abschnitt 6 aktualisieren, Push nach Bestätigung (K7).
