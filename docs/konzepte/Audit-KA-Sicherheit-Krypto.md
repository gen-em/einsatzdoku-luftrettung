# Audit KA — Sicherheitsreview und kryptographisches Audit

**Stand des Berichts:** 09.11.2026 · gelesen gegen Commit `e8b0fc5` ·
Web 24.10.1 · Uhr 4.3.3 · Android 0.22.0.

**Was das ist.** Ein unabhängiger Durchgang durch den ganzen Bestand,
ausdrücklich **über das Bedrohungsmodell hinaus** (E-KA-01):
`docs/Bedrohungsmodell.md` wurde als Selbstauskunft gelesen, die zu prüfen
ist, nicht als Grenze. Der Bericht ändert keinen Code. Er ist kein Konzept
mit Arbeitspaketen, sondern dessen Eingang; Abschnitt 7 schlägt einen
Zuschnitt vor.

**Statusblock.**

| | |
|---|---|
| Durchgang | abgeschlossen, 43 Befunde: **2 hoch**, 9 mittel, 4 niedrig bis mittel, 11 niedrig, 17 Hinweise; kein kritischer |
| Umgesetzt | nichts — Befunde und Empfehlungen, keine Korrektur. **Seit 10.11.2026 Befundteil von Schritt 28:** `Konzept-KA-Sicherheitspaket.md` sagt, was gebaut wird (E-KA-04 bis -08) |
| Steuerung | seit 10.11.2026: Fahrplanzeile 28 und R92 im Rahmenplan, R21 bis R27 im Bedrohungsmodell, P-KA-01 und -02 in der Prüfliste; `Backlog.md` unberührt — die Nummern entstehen in der Umsetzung |
| Hakt | nichts — die vier Fragen sind beantwortet (Abschnitt 8) |

**Entscheidungen vor Beginn** (Rückfrage am 09.11.2026):

| Nr. | Entscheidung |
|---|---|
| E-KA-01 | Umfang: alles, nach Risiko gereiht, unabhängig vom Bedrohungsmodell; dessen Annahmen werden mitgeprüft |
| E-KA-02 | Arbeitsweise gemischt: Krypto-Kern, Anmeldung, Sitzung und Zweitfaktor liest die Hauptinstanz zusammenhängend; Android, Garmin-Uhr, Kette, Daten-Endpunkte und Verwaltung/Sicherung lesen fünf Unter-Agenten, rein lesend; **jeder ihrer Befunde ist an der zitierten Stelle nachgeprüft**, bevor er hier steht |
| E-KA-03 | Ergebnis als Dokument unter `docs/konzepte/`, Kürzel KA, auf dem Arbeitszweig; keine Code-Korrekturen |

---

## 1. Was nicht geprüft werden konnte — zuerst

1. **Nichts wurde ausgeführt.** Kein Prüfstand, kein Browser, kein Emulator,
   kein Simulator, keine Anlage. Jeder Befund ist ein **Lesebefund**: der
   Datenfluss ist am Quelltext verfolgt, der Angriff nicht nachgestellt.
   Ausgeführt wurden nur zwei Funktionsproben mit `php -r` (`parse_url`,
   Regex-Anker) und `sha256sum` über die Vendor-Listen.
2. **Nicht vollständig Zeile für Zeile gelesen** sind: `migration_lib.php`,
   `status_lib.php`, `speicher_lib.php`, `plattform_lib.php`,
   `schluesselwechsel_lib.php`, `ratelimit_lib.php` (nur Aufbau und
   Aufrufstellen), die Anzeigeteile der Verwaltungs- und Betriebsseiten,
   `einsatz_form.php` ab Zeile 720, die Inline-Skripte der Listenseiten,
   `export.js`, `import*.js`, `ui.php`, die Ansichten der Garmin-Uhr
   (`ClockView`, `CprView`, `Input` und weitere) und die Fremdbibliotheken
   inhaltlich. Diese Teile sind über **Muster** geprüft (jede DOM-Senke, jede
   SQL-Stelle, jeder Anfrageparameter, jeder Krypto-, Netz- und
   Ablagezugriff). Ein Fehler außerhalb dieser Muster kann dort stehen.
3. **Einstellungen außerhalb des Repositoriums:** Rulesets und
   Umgebungsschutz in GitHub, Freigabe von Fork-Läufen, was der Agent-Proxy
   für `gh` und `git` zulässt, `AllowOverride` und Verzeichnislisting beim
   Hoster, ob vor Produktiv ein Proxy Anfragekörper protokolliert.
4. **Plattformverhalten:** `Toybox.Cryptography` (Güte des Zufalls, ob
   `createPublicKey` die Kurve prüft), Android Keystore auf Herstellergeräten,
   das Verhalten einer Benachrichtigungsaktion auf dem Sperrbildschirm, ob
   die Garmin-Begleit-App Zertifikate prüft.
5. **Die Git-Historie** ist ein flacher Klon mit 318 Commits; die Suche nach
   früher eingecheckten Geheimnissen deckt nur diese ab (dort: keine).
6. **Die Ausnutzbarkeit von F-KA-11** (SheetJS) ist ein begründeter
   Verdacht: Fassung, Leseweg und ein passendes Gadget sind gelesen, die
   Kette ist nicht nachgestellt.

**Grenze des Mittels.** Fünf der sieben Lesenden waren Unter-Agenten; sie
berichten in derselben sicheren Tonlage, ob sie recht haben oder nicht. Jeder
übernommene Befund ist deshalb an der Fundstelle gegengelesen — das belegt
die **zitierten Stellen**, nicht die Vollständigkeit der Suche. Wo ein Agent
„geprüft und in Ordnung" meldet, steht das in Abschnitt 6 als seine Aussage,
stichprobenhaft bestätigt.

---

## 2. Gesamtbild

**Die Serverseite ist sorgfältig gebaut.** In den klassischen Klassen —
Zugriff auf fremde Datensätze, CSRF, SQL-Injektion, XSS, Dateieinschluss,
offene Weiterleitung — steht nach diesem Durchgang **kein ausnutzbarer
Befund**. Anmeldung, Sitzung, TOTP, WebAuthn, Notzugang, Passwort-Reset und
die Server-Versiegelung (`edsk1:`) sind fehlerfrei gelesen.

**Die Befunde liegen woanders, in vier Gruppen:**

1. **Zwei Stellen, an denen ein Geheimnis den geschützten Bereich im
   Regelbetrieb verlässt** — der Wiederherstellungsschlüssel beim Druck des
   Notfallblatts (F-KA-01) und das Produktiv-FTP-Passwort bei jeder
   Auslieferung über eine ungeprüfte TLS-Verbindung (F-KA-02). Beide brauchen
   keinen Fehler der Anwendung und keinen geänderten Code, nur einen
   Mitleser. Beide führen in Lagen, gegen die laut Modell nichts mehr hält.
2. **Die Verschlüsselung schützt Vertraulichkeit, nicht Zuordnung.**
   Patientenblöcke sind an keinen Datensatz gebunden (F-KA-04), Gerätedaten
   tragen keinen Herkunftsnachweis (F-KA-12). Das Modell verspricht für W1
   und W2 nur Vertraulichkeit; für eine medizinische Dokumentation ist die
   Frage „gehört dieser Befund zu diesem Einsatz?" aber keine Nebensache.
3. **Die Kontobindung der Geräte hat Lücken ohne Angreifer.** Auf geteilt
   genutzten Geräten landen Spur, Phasenorte und Reanimationsverlauf durch
   eine normale Bedienfolge lesbar im falschen Konto (F-KA-05, F-KA-06), und
   Spurpunkte gehen still verloren (F-KA-07).
4. **Die Schranke „frischer Zweitfaktor" hat Nachbarwege** (F-KA-03,
   F-KA-19, F-KA-20), und zwei Zusagen der Verwaltung halten am Code nicht
   (F-KA-08, F-KA-10).

**Wo das Bedrohungsmodell und der Code auseinandergehen,** steht in
Abschnitt 5 — sieben Stellen.

---

## 3. Befunde

Schweregrade: **hoch** — bricht eine Kernzusage unter einer realistischen
Voraussetzung; **mittel** — Schaden an Daten, Rechten oder Verfügbarkeit
unter einer benannten Voraussetzung; **niedrig** — begrenzte Wirkung oder
enge Voraussetzung; **Hinweis** — keine Wirkung gefunden, aber eine Stelle,
die es nicht geben sollte. Spalte „Modell": **neu** — steht dort nicht;
**weicht ab** — das Modell sagt etwas anderes; **schwerer** — benannt, aber
schwächer beschrieben.

### 3.1 Hoch

#### F-KA-01 — Das Notfallblatt schickt den Wiederherstellungsschlüssel im Klartext an den Server

- **Fundstellen:** `server/pw_handling.php:411-413` und
  `server/schluessel_teile.php:85-87` (Formular mit `name="code"`, Ziel
  `notfallblatt.php`); `server/notfallblatt.php:90` liest ihn, Zeile 180 gibt
  ihn aus. `pw_handling.php:609` füllt das Feld mit dem eben erzeugten Code.
- **Was geschieht:** Der Knopf „Notfallblatt drucken" sendet die 20 Zeichen
  per POST an PHP — bei der Erstvergabe und nach jeder Erneuerung, mit
  Sitzungscookie oder Kontoadresse.
- **Warum das zählt:** Der Code öffnet `pat_wrap_rc` und damit den
  Inhaltsschlüssel, **ohne Passwort und ohne Server-Anteil** — die
  Wiederherstellungshülle hängt bewusst nicht am Anteil. Wer Anfragekörper
  sieht (Audit-Log einer WAF, Reverse-Proxy, TLS-Inspektion im Kliniknetz,
  Hoster) und die Datenbank hat, liest alle Patientenblöcke und den privaten
  Teil des Kontopaars. Der Weg ist **passiv**, er ändert keinen Code, und die
  Integritätswache sieht ihn nicht.
- **Modell: neu.** Das Notfallblatt kommt dort nicht vor. Der Dateikopf
  („Der Server kennt diesen Schlüssel nicht") stimmt für die Ablage, nicht
  für den Transport. Lage O („verschlossen … bis er Code ändert") und der
  Satz „Klartext dieser Felder geht nie an den Server" sind hier nicht
  erfüllt.
- **Unabhängig zweimal gefunden** (Hauptinstanz und Unter-Agent
  Daten-Endpunkte).
- **Empfehlung:** Das Blatt im Browser bauen — eine statische Seite, der Code
  per `textContent`, dann `window.print()` — sodass er den Tab nie verlässt.
  Bis dahin die Grenze im Modell und im Seitentext benennen.

#### F-KA-02 — Die Auslieferung prüft das FTPS-Zertifikat des Hosters nicht

- **Fundstelle:** `.github/workflows/ausliefern-lauf.yml:460-467`; der Schritt
  setzt `protocol: ftps`, aber kein `security:`. In keiner der vier
  Workflow-Dateien steht das Wort.
- **An der Quelle nachgemessen:** `action.yml` der Aktion an genau der
  gepinnten SHA `110f9186…` führt den Eingang `security` ohne Vorgabe; die
  Bibliothek `@samkirkland/ftp-deploy` 1.2.5 setzt die Vorgabe `loose`
  (`dist/utilities.js:164`) und rechnet
  `rejectUnauthorized = args.security === "strict"` (`dist/deploy.js:75`).
- **Angriffsweg:** Wer sich zwischen GitHub-Läufer und Hoster setzt
  (DNS-Umlenkung, Transit), zeigt ein beliebiges Zertifikat, bekommt das
  Produktiv-FTP-Passwort und tauscht ausgelieferte Dateien. Die Zielprobe
  davor prüft das Zertifikat (`curl --ssl-reqd`); der Angreifer muss also
  die zweite Verbindung gezielt aufbrechen.
- **Folge:** Lage C1 — „eine geänderte Zeile in `login.php` … schickt
  Passwort oder Schlüssel bei der nächsten Anmeldung mit".
- **Modell: neu.** Zeile 193 nennt fehlende Zertifikatsprüfung nur für die
  Backup-Ziele.
- **Empfehlung:** `security: strict` setzen und mit einem Probelauf
  nachmessen. Eine Zeile.

### 3.2 Mittel

#### F-KA-03 — Neue Konten mit Rechten entstehen ohne frischen Zweitfaktor

- **Fundstellen:** `server/admin_users.php:119-122` und `:160-161`; in der
  Datei steht kein `zweitfaktor_frisch_verlangen()`, und `user_add` fehlt in
  `ZF_FRISCH_HANDLUNGEN` (`server/db.php:1234-1273`). `konto_anlegen()` legt
  sofort mit Status `aktiv` an.
- **Angriffsweg:** Wer eine angemeldete BetreiberIn-Sitzung führt
  (unbeaufsichtigter Rechner, Skript in der Seite), sendet `action=user_add`
  mit `role=betreiberin` und einer eigenen Adresse, setzt das Passwort,
  richtet den eigenen Zweitfaktor ein — und hat ein dauerhaftes Konto mit
  eigenem frischem Code: Schlüsselblatt, Komplett-Stand, Rollenwechsel. Bei
  einer reservierten Adresse zeigt die Seite den Setz-Link sogar selbst
  (`admin_users.php:238-240`).
- **Der Widerspruch:** Der Rollenwechsel eines **bestehenden** Kontos
  verlangt den frischen Code (`admin_user.php:122-125`), das gleichwertige
  Anlegen nicht.
- **Modell: neu.** Einstufung: Der Unter-Agent nannte „hoch, bedingt"; hier
  mittel, weil die übernommene Sitzung Voraussetzung ist. Es ist der
  wichtigste Befund dieser Gruppe — er macht die Schranke wirkungslos.
- **Empfehlung:** `user_add` mit Rolle ≠ `user` an den frischen Code binden;
  bei neuen Konten mit Rechten eine Mail an alle BetreiberInnen.

#### F-KA-04 — Patientenblöcke und Schlüsselhüllen sind an nichts gebunden

- **Fundstelle:** `server/assets/crypto.js:300-308` — `encrypt()` ruft
  AES-GCM ohne `additionalData`. Dieselbe Funktion verschließt `pat_blob`
  (`einsatz_form.php:2376`), `kp_privat`, `rw_privat` und `pat_wrap_rc`.
- **Angriffsweg:** Wer die Datenbank beschreiben kann (SQL-Lücke mit
  Schreibrecht, Hoster, Lage B), vertauscht `pat_blob` zwischen zwei
  Einsätzen desselben Kontos oder legt einen älteren Stand zurück. Der
  Browser entschlüsselt beides ohne Fehler: Name und Diagnose von PatientIn A
  stehen am Einsatz von B.
- **Der Maßstab im eigenen Haus:** Die Server-Versiegelung bindet den Zweck
  samt Kontonummer (`serverkrypto_lib.php:272-273`), der Backup-Container
  den Platz jedes Teils (`crypto.js:859-860`). Die empfindlichsten
  Chiffretexte der Anwendung sind die einzigen ohne Bindung.
- **Nebenwirkung:** Alle vier Arten liegen unter demselben Schlüssel im
  selben Format; eine Trennung der Zwecke gibt es nicht.
- **Modell: neu.** W1 verspricht nur Vertraulichkeit.
- **Empfehlung:** Eine Formatfassung mit AAD aus Zweck, Kontonummer und
  `client_ref`; für `pat_blob` zusätzlich ein Zähler gegen das Zurückrollen.
  Das ist ein Formatwechsel mit Altbestand und gehört in ein eigenes Konzept.

#### F-KA-05 — Garmin-Uhr: Neukoppeln im laufenden Dienst schickt laufende Daten ans neue Konto

- **Fundstellen:** `watch/source/Pair.mc:284-302` — die Sperre „Erst Dienst
  beenden" steht nur im Zweig „abgeschlossener Rückstand vorhanden und
  abgemeldet"; sonst fällt der Weg durch. `watch/source/Model.mc:42-78` —
  `backlogCount()` zählt nur abgeschlossene Pakete.
  `watch/source/Uploader.mc:405-412` — laufender Einsatz und laufendes
  Ruhesegment gehen ohne `istFremd`-Prüfung hinaus.
- **Was geschieht:** Wird eine geteilte Uhr im laufenden Dienst übergeben und
  neu gekoppelt, verschließt sie Phasenkoordinaten, den ganzen
  Reanimationsverlauf und alle Spurpunkte seit der letzten Quittung mit dem
  Schlüssel des **neuen** Kontos. Dort sind sie lesbar. Dasselbe gilt für
  eine gefundene Uhr, auch wenn die EigentümerIn sie im Web deaktiviert hat.
- **Modell: weicht ab.** `docs/Handbuch.md:129-130` sagt: „Während eines
  Dienstes geht das nicht".
- **Empfehlung:** `Pair.start()` bei `Model.serviceActive` grundsätzlich
  abweisen.

#### F-KA-06 — Handy: Die Kontobindung des Klartextpuffers hat drei Wege daran vorbei

- **Fundstellen:**
  `android/handy/…/kopplung/Kopplungsdienst.kt:387-397` — die Bindung
  entsteht nur in `trennen()`; `…/senden/Sender.kt:208` — der Sender hält nur
  zurück, wenn eine Bindung besteht.
- **Die drei Wege:**
  1. **Die Uhr startet einen Dienst auf einem ungekoppelten Handy.**
     `…/uhr/Uhrannahme.kt:107` prüft den Kopplungszustand nicht;
     `tresor.gekoppelt()` liest nur `HauptActivity.kt:136`. Das Handy
     zeichnet auf, sendet nichts, und wer als Nächstes koppelt, bekommt die
     Daten. Im ungekoppelten Zustand lässt sich der Dienst am Handy nicht
     beenden.
  2. **Der Tresor ist unlesbar** (`Schluesseltresor.kt:57-65`): Die App
     zeigt die Kopplung, `Kopplungsdienst.starten()` prüft keinen Rückstand.
  3. **„Nicht im laufenden Dienst trennen" steht nur in der Oberfläche**
     (`EinstellungenAnsicht.kt:127-133`); `trennen()` prüft es nicht.
- **Modell: weicht ab** (Lage H, E-GR-01: fremde Einsätze gehen nicht in ein
  fremdes Konto).
- **Empfehlung:** Dienstbeginn von der Uhr an die Kopplung binden; beim
  Abschluss jeder Kopplung vorgefundenen Bestand ohne Bindung als fremd
  binden; `trennen()` bei laufendem Dienst verweigern.

#### F-KA-07 — Stiller Verlust von Spurpunkten bei wiederholtem Teilstück

- **Fundstellen:** `watch/source/Uploader.mc:433-435` — die Stückgröße wächst
  zwischen zwei Versuchen; `server/schema.sql:867` — Primärschlüssel
  `(owner_type, owner_id, seq_from)`; `server/spur_lib.php:1071-1074` — eine
  Dublette wird still übergangen; `server/ingest.php:1136-1137` — quittiert
  wird trotzdem `seq_from + gesendete Punkte`.
- **Ablauf:** Die Uhr sendet `0:120`, der Server speichert, die Antwort geht
  verloren. Beim nächsten Versuch sendet sie `0:300`. Der Insert kollidiert,
  nichts wird abgelegt, quittiert wird 300. Die Punkte 120 bis 299 liegen
  nirgends; bei einem abgeschlossenen Einsatz löscht die Uhr sie.
- **Kein Angreifer nötig** — ein Funkabriss genügt, ebenso der eigene
  503-nach-Commit-Pfad (`ingest.php:1204-1218`). Ob das Handy ebenso
  stückelt, ist nicht geprüft.
- **Modell: neu.**
- **Empfehlung:** Wenn nichts abgelegt wurde, nur `spur_naechste_seq()`
  quittieren.

#### F-KA-08 — Die Freigabe eines Konto-Backups umgeht die Adressfrist

- **Fundstellen:** `server/adminbackup_lib.php:1254-1257` vergleicht die
  Adresse der Begleitdatei mit `users.email`; `server/admin_user.php:237`
  schreibt `users.email` beim Adresswechsel durch die Verwaltung **sofort**.
  `adresse_unbestaetigt()` fragt die Freigabe nicht.
- **Angriffsweg (Admin mit frischem Code):** Die Adresse des Opfers ändern,
  einem Zweitkonto die frei gewordene alte Adresse geben, das Paket dorthin
  freigeben, die entsiegelten Teile abrufen. Offen liegen Zeiten, Phasen,
  Besatzung, Diensttagsnotizen, Stammdaten; `pat_blob`, Hüllen und Spurstücke
  bleiben verschlossen. Nicht still — zwei Mails und Protokolleinträge —,
  aber sofort.
- **Modell: weicht ab.** Der Kommentar über der Funktion sagt, ein
  Adresswechsel gelte erst nach der Frist. Zeile 96 des Modells sagt, den
  `pat_key_check` liefere keine Freigabe mehr;
  `server/api/adminbackup_freigabe.php:97` liefert ihn.
- **Empfehlung:** Zielkonten mit offener Adressbestätigung abweisen; stärker
  an die Kennung als an die Adresse binden.

#### F-KA-09 — Der SFTP-Hostschlüssel wird nur nach manueller Prüfung gepinnt

- **Fundstellen:** `server/sicherungsziel_lib.php:467-468` prüft nur, wenn
  ein Abdruck gespeichert ist; gespeichert wird er allein in
  `server/admin_sicherungsziele.php:201-205` nach „Verbindung prüfen". Der
  Versandjob (`sicherungsziel_lib.php:1119-1122`) verbindet ohne zu
  speichern.
- **Folge:** Ein Ziel, das nie geprüft wurde, dessen Host geändert oder
  dessen Schlüssel „vergessen" wurde, bekommt bei jedem Lauf Passwort und
  Sicherungen — von wem auch immer die Verbindung beantwortet wird.
- **Modell: weicht ab** (Zeile 193 nennt SFTP als die Variante mit
  Hostschlüsselprüfung).
- **Empfehlung:** Ohne gespeicherten Abdruck nicht senden.

#### F-KA-10 — Die Mengengrenze je Konto gilt nur für Geräte

- **Fundstellen:** `konto_fuellstand()` hat vier Aufrufer, davon nur
  `server/ingest.php:350` an einem Schreibweg. Der Kommentar dort
  (Zeile 346-347) nennt „hier und im Import"; im Import steht sie nicht.
  Ohne Prüfung: `api/import_commit.php`, `api/gpx_import.php`,
  `api/backup_restore.php`, `api/backup_eintraege_restore.php`,
  `api/backup_spuren_restore.php`, `einsatz_form.php`.
- **Angriffsweg:** Jedes Konto — auch das öffentliche Demo-Konto, dessen
  Anmeldung ab Werk an ist (`konten_einstellungen_lib.php:122`) — füllt in
  einer Schleife die gemeinsame Datenbank bis zum Kontingent des Hosters.
- **Modell: weicht ab** (G4: „kann nur sich selbst treffen").
- **Empfehlung:** Die Prüfung an den Anfang jedes anlegenden Web-Wegs;
  Löschen und Bearbeiten frei lassen; für das Demo-Konto eine eigene, enge
  Grenze.

#### F-KA-11 — SheetJS 0.18.5 liest zugespielte Tabellen

- **Fundstellen:** `server/assets/vendor/xlsx.full.min.js` (Kopf: Fassung
  0.18.5); `server/assets/import.js:385`. Ein passendes Gadget:
  `server/assets/html.js:101-120` liest `opt.roh` und `opt.knopf` ohne
  Eigenschaftsprüfung und setzt `knopf` ungefiltert ins Markup.
- **Bekannt für diese Fassung:** CVE-2023-30533 (Prototype Pollution beim
  Lesen einer präparierten Datei, behoben in 0.19.3) und CVE-2024-22363
  (ReDoS, behoben in 0.20.2). Das npm-Archiv, aus dem laut `Lizenzen.md`
  bezogen wird, führt keine behobene Fassung.
- **Einordnung:** Die Importseite kann den Inhaltsschlüssel benutzen. Ob die
  Lücke genau die geerbten Eigenschaften setzen kann, ist **nicht
  nachgestellt** (Abschnitt 1, Punkt 6).
- **Modell: neu.**
- **Empfehlung:** Auf eine behobene Fassung vom Hersteller-Archiv heben, mit
  Summe im Kopf; in `EdHtml.meldung()` Optionen nur als eigene Eigenschaften
  lesen.

### 3.3 Niedrig bis mittel

#### F-KA-12 — Die Gerätehülle belegt weder Herkunft noch Zeit noch Vollständigkeit

- **Fundstellen:** `server/assets/crypto.js:1077-1113` — der Prüfschlüssel
  entsteht aus einem flüchtigen Schlüssel und dem öffentlichen Kontoschlüssel;
  `server/assets/ortsdaten.js:131` und `:186` — Punkt- und Ereigniszeiten
  sind relativ zu `ts_von` beziehungsweise `started_at`, beide Klartext
  außerhalb des Prüfwerts; `ortsdaten.js:105-141` prüft keine Lücken.
- **Was daraus folgt:** (1) Wer den öffentlichen Schlüssel kennt — der
  Server, jedes gekoppelte Gerät, eine gefundene Uhr —, baut gültige Hüllen
  mit beliebigem Inhalt. (2) Wer die Datenbank beschreibt, verschiebt Spur-
  und Reanimationszeiten, löscht Stücke oder legt eine ältere Einsatzhülle
  zurück, ohne dass der Prüfwert bricht. Über Kontogrenzen geht nichts.
- **Modell: weicht ab.** Zeile 158 sagt: „Verschieben oder Umetikettieren
  eines Stücks: Die Bindung schlägt fehl" — das gilt für ein einzelnes
  Stück, nicht für Zeitanker, Gesamtumfang und Fälschung.
- **Empfehlung:** In einer Fassung 2 absolute Zeiten in die Hülle und einen
  Zähler in die Einsatzhülle; ein Herkunftsnachweis bräuchte einen
  Signaturschlüssel je Gerät. Bis dahin Zeile 158 auf das Tatsächliche kürzen.

#### F-KA-13 — Die Rundenzahl der Schlüsselableitung kommt vom Server, der Browser nimmt alles ab 1 000

- **Fundstellen:** `server/assets/crypto.js:33-37` (`pruefeRunden`: 1 000
  bis 10 000 000); `crypto.js:124` übernimmt die Liste aus `auth_salt.php`;
  der Sollwert ist 600 000 (`server/db.php:1624`).
- **Angriffsweg:** Wer die Antwort des Salz-Endpunkts verändert, ohne
  Skripte zu tauschen (ein Zwischenstück hinter TLS, ein Fehler im
  Endpunkt), bekommt ein Anmelde-Token aus 1 000 Runden und rät das Passwort
  sechshundertmal billiger.
- **Modell: neu.**
- **Empfehlung:** Untergrenze im Browser auf den kleinsten je gültigen Wert
  heben und das Salz auf seine Form prüfen.

#### F-KA-14 — Skript-Injektion über den Eingang `probelauf_mengenprobe`

- **Fundstelle:** `.github/workflows/ausliefern-lauf.yml:279` —
  `ZAHL="${{ inputs.probelauf_mengenprobe }}"`; der Eingang ist freier Text
  (`auslieferung.yml:30-40`), die Zahlenprüfung folgt erst danach. Der
  Schritt trägt die FTP-Zugangsdaten, der Job das Job-Token.
- **Angriffsweg:** Ein Handlauf auf unverändertem `main` mit Shell-Code im
  Feld. Nötig sind Schreibrecht und, für Produktiv, ein Freigabeklick auf
  etwas, das wie ein Probelauf aussieht. Es ist die einzige Einsetzung
  freien Textes in ein `run:`.
- **Modell: schwerer** — anders als der benannte Weg C2 braucht dieser keine
  eigene Workflow-Datei.
- **Empfehlung:** Über `env:` hereinreichen.

#### F-KA-15 — Die Deny-Liste lässt das Schreiben über die Contents-API offen

- **Fundstelle:** `.claude/settings.json:3-8` sperrt vier Werkzeuge.
  `mcp__github__create_or_update_file` und `mcp__github__delete_file` sind
  nicht darunter; in der Werkzeugliste dieser Sitzung standen beide bereit
  (nicht benutzt).
- **Folge:** Der mit `push_files` gemeinte Riegel („kein Schreiben an Git
  vorbei") hat einen Nachbarweg, etwa auf den ungeschützten Zeiger
  `produktion`. Eine Bash-Regel für `gh` gibt es ebenfalls nicht.
- **Modell: schwerer** (Zeile 191 nennt nur `create_branch`).
- **Empfehlung:** Beide aufnehmen; die eigentliche Grenze gehört auf die
  GitHub-Seite (Regel auf `produktion` und Tags).

### 3.4 Niedrig

| Nr. | Befund | Fundstelle | Empfehlung |
|---|---|---|---|
| F-KA-16 | **Das Vormerkfach hält PBKDF2-Hälfte und Anmelde-Token im `sessionStorage`** und wird nicht auf jedem Weg geräumt: `nachDerAnmeldung()` kehrt ohne Räumen zurück, wenn Rückweg und Kontopaar schon da sind; ein Tab, der an der Code-Abfrage stehen bleibt, behält beides. Das Modell (Lage D) sagt, der Datenschlüssel liege „gar nicht mehr" — seine Passworthälfte liegt in diesem Fenster doch | `assets/crypto.js:675-686`, `assets/unlock.js:562-567` | am Ende von `nachDerAnmeldung()` immer räumen; Zeitstempel ins Fach, nach wenigen Minuten verwerfen |
| F-KA-17 | **Das zweite Tor der Kopplung zeigt zwei Zeichen und die Domain.** Wer den Code sieht und zuerst eingibt, erscheint am Gerät als `ab***@` plus Domain — in einer Einrichtung mit gemeinsamer Maildomain von der eigenen Adresse nicht zu unterscheiden. Gilt für Handy und Garmin-Uhr | `db.php:1079-1086`, `KopplungAnsicht.kt:149`, `Pair.mc:748` | einen Vergleichswert zeigen, den auch die Webseite zeigt — am besten aus dem öffentlichen Kontoschlüssel; das wäre zugleich dessen fehlender zweiter Kanal |
| F-KA-18 | **Geräte werten Serverantworten ohne Vertragsrumpf als endgültig.** Handy: Statuscode 400 parkt ein Paket für immer, 401/403 startet die 30-Tage-Löschfrist, der Rumpf wird nicht angesehen. Uhr: `next_seq` wird ungeprüft übernommen — wer die Serveradresse in Garmin Connect umstellt, lässt damit den Puffer löschen | `Sendeantwort.kt:115-117`, `Sender.kt:280-293`, `Uploader.mc:586-591` | nur mit dem `error`-Feld aus `ingest.php` als endgültig werten; `next_seq` auf `seq_from + gesendete Punkte` deckeln |
| F-KA-19 | **Job-, Ketten- und Health-Token stehen bei jedem Aufruf im Klartext auf der Seite;** nur das Erneuern verlangt den frischen Code. Eine übernommene Sitzung kopiert das Kettentoken und schaltet danach ohne Sitzung Wartung und Pause | `betrieb_jobs.php:400`, `:443`, `:495` gegen `:48` | nur nach frischem Code oder einmalig nach dem Erzeugen zeigen; gehasht speichern |
| F-KA-20 | **Jeder Admin ändert Betriebs- und Kontaktadresse, ohne Protokoll und ohne frischen Code.** Sammelmails über auffällige Anmeldeversuche gehen dann nur noch dorthin | `admin_installation.php:4`, `:70-74`, `mail_lib.php:794-795` | der BetreiberIn vorbehalten, protokollieren, die alte Adresse benachrichtigen |
| F-KA-21 | **Ein Admin spielt ein Paket in jedes Konto ein, auch in das einer BetreiberIn;** die „harte" Bestätigung prüft gegen einen Sollwert, den der Client mitschickt | `admin_sicherungen.php:185-186`, `:264-270` | denselben Riegel wie `admin_user.php:105`; Sollwert serverseitig bilden |
| F-KA-22 | **Die Wiederherstellung schreibt Katalogspalten ungeprüft,** darunter die Altspalte `notes` im Klartext. Der Kommentar darüber verspricht ein Stutzen, der Code stutzt nicht. Nur eigene Daten, gebundene Parameter — aber ein Schreibweg an `validate_lib.php` vorbei | `backup_lib.php:1805-1833`, `:2052-2055` | dieselben Prüfaufrufe wie `api/import_commit.php:517-556` |
| F-KA-23 | **Teil-Vorlagen sind ohne Anmeldung direkt abrufbar.** `blatt_dialog.php` nennt nach einer Rotation die Kennung des alten Schlüssels und endet dann im Fehler, der über den Ausnahmebehandler eine Systemmeldung schreibt — je Abruf eine Zeile, ohne Ratengrenze | `.htaccess:73`, `blatt_dialog.php:52-60`, `:94`, `db.php:58` | `*_lib.php`, `*_teile.php`, `*_dialog.php` per `.htaccess` sperren |
| F-KA-24 | **Der Prüfbericht ist frei schreibbarer Text der Commit-Nachricht.** Dazu überspringt die Prüfung den Baumvergleich still, wenn `git rev-parse` nichts liefert | `tools/pruefstand/bericht.py:196-197`, `:344` | „leer = rot"; im Modell als Grenze benennen |
| F-KA-25 | **Der Dateiname des Produktiv-Komplettstands und `wartung.von` stehen im Lauf-Protokoll.** Der Name ist laut `komplett_lib.php:147-149` die zweite Schranke des Ordners; `wartung.von` ist die Auskunft, deretwegen `wartung.lock` nach `.zustand/` zog | `tools/kette/tor.py:330-331`, `ausliefern-lauf.yml:527` | `datei` und `von` nicht drucken |
| F-KA-26 | **Zwei Prüfsummen misst kein Riegel:** die 349 Dateien unter `server/vendor/` (ausdrücklich ausgenommen) und `gradle-wrapper.jar`. Beide stimmen heute (nachgerechnet). Ein Tausch der JAR bestimmt die APK, die der Signaturjob unterschreibt | `tools/quelltext/fremdsummen.py:26-27`, `android/LIESMICH.md:884-886` | beide in den Riegel aufnehmen |

### 3.5 Hinweise

| Nr. | Hinweis | Fundstelle |
|---|---|---|
| F-KA-27 | `pair.php` prüft im Geräte-Zweig weder `devices.active` noch den Kontostatus: Eine im Web deaktivierte Uhr bekommt weiter den Kontoschlüssel und kann `trennen` aufrufen — das ebnet den Weg aus F-KA-05 | `pair.php:371` gegen `ingest.php:291` |
| F-KA-28 | Die Dienstadresse der Adresssuche erlaubt eine CSP-Direktiven-Injektion (nur BetreiberIn): `parse_url` nimmt `; script-src-elem * data` in den Host auf, und der geht ungeprüft in die Kopfzeile. Mit `php -r` nachgestellt | `geocoder_lib.php:194-204`, `kopfzeilen_lib.php:303-306` |
| F-KA-29 | PBKDF2 liefert 512 Bit, also zwei Blöcke: Die NutzerIn rechnet beide, ein Angreifer mit dem Token nur einen. Kein Bruch, aber die halbe Rechenzeit ist verschenkt — ein Block plus HKDF erlaubte die doppelte Rundenzahl bei gleicher Wartezeit | `assets/crypto.js:79-91` |
| F-KA-30 | Chiffretexte sind nicht gepolstert: Die Länge eines `pat_blob` zeigt, ob und wie viel Freitext ein Einsatz trägt | `assets/crypto.js:300-308` |
| F-KA-31 | „Dienst beenden" in zwei Warnmeldungen ist ein Dienst-`PendingIntent` ohne `setAuthenticationRequired` — auf dem Sperrbildschirm ohne Entsperren auslösbar (Plattformverhalten nicht gemessen) | `AufzeichnungsDienst.kt:544-552`, `:614-622` |
| F-KA-32 | Ein nie quittiertes „Dienst beginnen" der Wear-Uhr kann Tage später eine Aufzeichnung starten: Ohne laufenden Dienst hat die Zeitprüfung keine untere Grenze | `Uhrannahme.kt:164-168` |
| F-KA-33 | `HauptActivity` trägt `singleTask` ohne `taskAffinity=""`, anders als das Uhr-Manifest | `android/handy/src/main/AndroidManifest.xml:140` |
| F-KA-34 | Regex-Anker `$` ohne `D` passt auch vor einem Zeilenumbruch: Dieselbe GPX-Datei lässt sich mit angehängtem Zeilenumbruch an der Dublettenprüfung vorbei zweimal einspielen (nur eigene Daten) | `api/gpx_import.php:78`, `api/import_commit.php:184`, `api/range.php:25` |
| F-KA-35 | `adresse_bestaetigen.php` ändert die Anmeldeadresse per GET; ein Mailscanner, der Links vorab abruft, bestätigt den Wechsel | `adresse_bestaetigen.php:27-28` |
| F-KA-36 | `notfallblatt.php` und `codeblatt.php` spiegeln POST-Werte ohne Token und ohne Anmeldung: Eine fremde Seite kann im Ursprung der Anlage ein echt aussehendes Blatt mit einem von ihr gewählten Code anzeigen lassen | `notfallblatt.php:90-113` |
| F-KA-37 | Die Kettenwerkzeuge bekommen FTP-Passwort und Job-Token als Argument, entgegen der eigenen Zusage in `zielprobe.py:255-256`; sichtbar für jeden Prozess desselben Laufs | `ausliefern-lauf.yml:345`, `:354`, `:435` |
| F-KA-38 | `.htaccess`: `migrations/*.sql` sind abrufbar, `Options -Indexes` fehlt, und `.zustand/` steht nicht in der Pfadliste der Wache | `server/.htaccess:73`, `wache.py:385-387` |
| F-KA-39 | Die Dienst-Abbilder der Stufe 1 hängen am Tag, nicht am Digest; der Riegel sieht nur `uses:` | `pruefung.yml:248`, `:253` |
| F-KA-40 | Sicherung, vier Kleinigkeiten: ein unversiegeltes Manifest macht ein ganzes Paket unversiegelt lesbar (Fassung-2-Rückfall); die Passphrase des Komplett-Downloads braucht nur 8 Zeichen; das Protokollarchiv liegt beim Download unversiegelt im gemeinsamen Temp-Verzeichnis; `api/csp_bericht.php` nimmt je Anfrage viele Einträge an | `adminbackup_lib.php:155`, `komplett_lib.php:1714`, `protokoll_archiv_lib.php:568`, `api/csp_bericht.php:83-90` |
| F-KA-41 | `api/schluessel_erneuern.php` hat keinen Demo-Ausstieg: Das Demo-Konto kann `pat_wrap_rc` tauschen und je Aufruf eine Mail einreihen | `api/schluessel_erneuern.php:67-132` |
| F-KA-42 | Die Anmeldesperre hängt am Kontonamen: Wer eine Adresse kennt, sperrt deren Anmeldung wiederholt für 15 bis 60 Minuten. „Passwort vergessen" bleibt offen — vermutlich bewusst, hier nur benannt | `login.php:611`, `ratelimit_lib.php:539` |
| F-KA-43 | Kommentare, die dem Code widersprechen: `Huelle.mc:80` (Kurvenprüfung) gegen Modell Zeile 158; `Pair.mc:17-19` (Schulterblick löse nichts aus); `freigabe.py:13-15` (Abstammung belege den Inhalt); `backup_lib.php:2051` (Stutzen); Kopf von `notfallblatt.php` | dort |

---

## 4. Kryptographische Bewertung je Baustein

| Baustein | Verfahren, wie gelesen | Urteil |
|---|---|---|
| **Ableitung aus dem Passwort** | PBKDF2-HMAC-SHA256, Salz 128 Bit je Konto, 600 000 Runden, 512 Bit geteilt in Hälfte und Anmelde-Token | trägt; zwei Schwächen am Rand: F-KA-13 (Untergrenze), F-KA-29 (zwei Blöcke). Das Modell sagt richtig, dass die Stärke am Passwort hängt |
| **Server-Anteil** | HKDF-SHA256(Hälfte, Salz = HMAC(`kdf_anteil`, `konto:<id>`), Info `edka1\|dk`) | in Ordnung; Zwecke sauber getrennt (`konto:`, `pfeffer:`) |
| **Anmelde-Hash** | bcrypt über HMAC-SHA256(Token, Pfeffer je Konto); Blindvergleich bei unbekanntem Konto | in Ordnung |
| **Inhaltsschlüssel und Hüllen** | 256 Bit Zufall; AES-256-GCM, 96-Bit-Zufallsnonce, **ohne Zusatzdaten** | vertraulich: ja. Bindung: nein — F-KA-04. Menge der Verschlüsselungen je Schlüssel weit unter der Nonce-Grenze |
| **Wiederherstellungsschlüssel** | 20 Zeichen aus 30, rund 98 Bit; SHA-256 ohne Streckung; die Verzerrung der Restklassenbildung kostet 0,05 Bit | das Verfahren trägt; **der Transport bricht es** — F-KA-01 |
| **Ablage im Browser** | nicht extrahierbarer `CryptoKey` in IndexedDB, Frist 30 Minuten, Bindung an die Hülle | in Ordnung mit den benannten Grenzen; Rest: F-KA-16 |
| **Gerätehülle** | flüchtiges P-256-Paar je Hülle, ECDH, HKDF-SHA256 (Salz = flüchtiger öffentlicher Schlüssel, Info mit Empfänger), AES-256-CBC, dann HMAC-SHA256 über Kopf, Chiffretext und längenpräfigierte Bindung; Prüfwert in fester Zeit vor AES | sauberes ECIES, in Uhr, Handy, Browser und PHP deckungsgleich; kein Polsterungsorakel, kein Klartext-Rückfall. Grenze: F-KA-12 |
| **Kontopaar** | P-256, privat unter dem Inhaltsschlüssel, öffentlich mit `edsk1:` versiegelt und an die Kontonummer gebunden; einmal gesetzt, nie ersetzt | in Ordnung; ein reiner Datenbank-Schreiber tauscht den öffentlichen Teil nicht. Der fehlende zweite Kanal ist benannt |
| **Server-Versiegelung `edsk1:`** | AES-256-GCM, 96-Bit-Zufallsnonce, 128-Bit-Prüfwert, Zusatzdaten mit Zweck und Kontonummer | vorbildlich — der Maßstab für F-KA-04 |
| **Konto-Backup, Fassung 4** | PBKDF2 je Vorgang, AES-256-GCM je Teil, Zusatzdaten aus Kopf, Backup-Kennung, Name und Platz | in Ordnung |
| **Komplett-Stand** | Blöcke zu 256 KiB, AES-256-GCM, Zusatzdaten mit Kopf-Hash, Blockindex und Schlussmarke | in Ordnung; Rest: F-KA-40 |
| **TOTP** | RFC 6238, Fenster ±1, Wiederholungsschutz über den letzten Schritt, Geheimnis versiegelt, Codes als bcrypt, einmalig | in Ordnung |
| **Passkeys** | eigene WebAuthn-Prüfung: Herausforderung einmalig, Ursprung, `rpIdHash`, UP, Zähler, strenger CBOR- und COSE-Leser, ES256 und RS256 | in Ordnung; sorgfältiger als die meisten Bibliotheken |
| **Rückweg** | ECDSA P-256 über phpseclib, Nachricht bindet Zweck, Konto und einmalige Herausforderung, Selbsttest vor Gebrauch | in Ordnung |
| **Transport** | Sitzungscookies `Secure`, `HttpOnly`, `SameSite=Strict`, Bindungscookie, Regeneration; CSP mit Nonce | in Ordnung; **der Transport der Kette nicht** — F-KA-02 |

---

## 5. Wo Bedrohungsmodell und Code auseinandergehen

| Stelle im Modell oder Text | Was dort steht | Was der Code tut | Befund |
|---|---|---|---|
| Lage O, Zusage G2 | verschlossen, „bis er Code ändert" | der Wiederherstellungsschlüssel geht im Regelbetrieb an den Server | F-KA-01 |
| Zeile 193 | fehlende Zertifikatsprüfung nur bei den Backup-Zielen | auch die Auslieferung prüft nicht | F-KA-02 |
| Zeile 158 | Verschieben oder Umetikettieren eines Stücks schlägt fehl | gilt nur für das einzelne Stück | F-KA-12 |
| Zeile 96 | den `pat_key_check` liefert keine Freigabe mehr | der Endpunkt liefert ihn | F-KA-08 |
| Zusage G4 | ein Konto kann nur sich selbst treffen | die Mengengrenze fehlt an sechs Web-Schreibwegen | F-KA-10 |
| Lage H, E-GR-01 | fremde Einsätze gehen nicht in ein fremdes Konto | drei Wege am Handy, einer an der Uhr | F-KA-05, -06 |
| Lage D | der Datenschlüssel liegt „gar nicht mehr" | seine Passworthälfte liegt im Vormerkfach | F-KA-16 |

Dazu `docs/Handbuch.md:129-130` (F-KA-05). Nach `CLAUDE.md` Abschnitt 4 gilt
bei Abweichung das Modell, und die Abweichung steht als Befund, bis eine
Freigaberunde nachzieht — hier ist es umgekehrt: Der Code weicht ab, und das
Modell beschreibt den gewollten Stand.

---

## 6. Geprüft und in Ordnung

Mit der Einschränkung aus Abschnitt 1. **Selbst gelesen** heißt: von der
Hauptinstanz; **Agent** heißt: Aussage eines Unter-Agenten, stichprobenhaft
bestätigt.

- **Anmeldung** (selbst gelesen): Blindvergleich bei unbekanntem Konto,
  angeglichene Antwortdauer, Pseudo-Salz für unbekannte Adressen, Sperre je
  Konto und Anschluss, Regeneration der Sitzung, `session_epoch` beendet
  fremde Sitzungen beim Passwortwechsel.
- **Zweitfaktor** (selbst gelesen): halber Stand mit Frist und Bindung,
  gemerktes Gerät als 256-Bit-Zufall mit Hash in der Datenbank, Notzugang mit
  drei unabhängigen Nachweisen, frischer Code vor Ausschalten und
  Passkey-Verwaltung.
- **Passwort-Reset** (selbst gelesen): Token als Hash, einmalig, mit Frist,
  wandert aus der Adresszeile in eine eigene Sitzung; Links aus
  `app.base_url`, nie aus der Host-Kopfzeile.
- **Schlüssel-Endpunkte** (selbst gelesen): jede Änderung verlangt das
  Anmelde-Token; das Kontopaar lässt sich nicht ersetzen; die
  Wiederherstellungshülle darf nicht am Anteil hängen.
- **Client-Adresse** (selbst gelesen): `X-Forwarded-For` nur von
  eingetragenen Proxys, und dann der letzte Eintrag.
- **Zugriff auf fremde Datensätze** (Agent): jede ID gegen `user_id`, auch in
  `UPDATE` und `DELETE`, bei Quelle und Ziel; Tabelle je Endpunkt im
  Agentenbericht.
- **SQL** (Agent, an Stichproben bestätigt): nur gebundene Parameter;
  Bezeichner aus Katalog und festen Listen.
- **XSS** (Agent): alle DOM-Senken der genannten Skripte bis zur Quelle
  verfolgt; entschlüsselte Felder, Importzellen und Antworten der
  Adresssuche laufen durch die Maskierung. Kein `eval`, `unserialize`,
  `exec` im Anwendungscode (selbst per Suche gegengeprüft).
- **Android** (Agent): Tresor im AndroidKeyStore ohne Klartext-Rückfall,
  Release nur HTTPS ohne eigenen TrustManager, keine Umleitungen,
  `allowBackup="false"`, nur zwei exportierte Komponenten. **Die Zusage „die
  Uhr kennt keine Zugangsdaten" hält.**
- **Garmin-Uhr** (Agent): kein Klartext-Rückfall, IV aus
  `Cryptography.randomBytes`, Geräteschlüssel mit 192 Bit als Hash, Gerät
  entsteht erst nach dem Ja an der Uhr.
- **Kette** (Agent): kein `pull_request_target`; `contents: write` nur im Job
  `zeiger`; kein Job sieht Produktiv-Geheimnisse ohne Umgebung; alle 19
  fremden `uses:` an einer 40-stelligen SHA; Bau ohne Geheimnisse, Signatur
  getrennt; **keine eingecheckten Geheimnisse**.

---

## 7. Vorschlag für den Zuschnitt

Kein Fahrplan — ein Vorschlag, gereiht nach Wirkung je Aufwand. Die
Paketnummern sind Arbeitsnamen; Versionen legt die Umsetzung fest.

| Paket | Inhalt | Befunde | Umfang |
|---|---|---|---|
| **KA-01 Sofortriegel** | `security: strict`; Mengenprobe über `env:`; `user_add` an den frischen Code; `Pair.start()` im Dienst abweisen; `pair.php` prüft `active`; Hostprüfung der Adresssuche; Deny-Liste; Protokollausgabe der Kette | F-KA-02, -03, -05, -14, -15, -25, -27, -28 | je wenige Zeilen; Web, Uhr und Kette |
| **KA-02 Notfallblatt im Browser** | das Blatt ohne Server bauen; Seitentext und Modell nachziehen | F-KA-01, -36 | klein, aber mit Druckbild in drei Engines |
| **KA-03 Kontobindung der Geräte** | Handy: drei Wege schließen; Server: nur Gespeichertes quittieren; Geräte: Antworten nur mit Vertragsrumpf endgültig | F-KA-06, -07, -18, -32 | Android, Web, Uhr; braucht Emulator und Simulator |
| **KA-04 Verwaltung und Sicherung** | Freigabe gegen die Adressfrist; SFTP ohne Abdruck nicht senden; Token-Anzeige; Betriebsadresse; Einspielen nach oben; Prüfaufrufe in der Wiederherstellung | F-KA-08, -09, -19, -20, -21, -22 | Web |
| **KA-05 Mengen und Fremdbestandteile** | Mengengrenze an alle anlegenden Wege; SheetJS heben; `EdHtml.meldung()` härten; Summenriegel; Teil-Vorlagen sperren | F-KA-10, -11, -23, -26, -38 | Web, Prüfmittel |
| **KA-06 Bindung der Chiffretexte** | Formatfassung mit Zusatzdaten für `pat_blob` und Hüllen; Gerätehülle Fassung 2 mit Zeiten und Zähler; Anhebelauf für den Altbestand; Vergleichswert bei der Kopplung | F-KA-04, -12, -13, -16, -17, -29 | **eigenes Konzept** — Formatwechsel in vier Umsetzungen, Hauptstufe |
| **KA-07 Modell und Texte** | Abschnitt 5 nachziehen; die Kommentare aus F-KA-43; übrige Hinweise einordnen | F-KA-24, -30, -31, -33 bis -35, -37, -39 bis -43 | Dokumentation, einzelne Zeilen |

KA-01 und KA-02 schließen beide hohen Befunde und den wichtigsten mittleren.

---

## 8. Fragen an die BetreiberIn — beantwortet am 10.11.2026

| Nr. | Frage | Antwort |
|---|---|---|
| Q-KA-01 | Steht vor Produktiv oder Staging ein Proxy, eine WAF oder ein Hoster-Protokoll, das Anfragekörper mitschreibt? | nicht bekannt; die Wiederherstellungsschlüssel werden erneuert — **nach** dem Umbau des Notfallblatts (E-KA-07) |
| Q-KA-02 | Gelten in GitHub Regeln für den Zweig `produktion` und für Tags, und starten Fork-Läufe ohne Freigabe? | nachgemessen, soweit lesbar — Tabelle unten |
| Q-KA-03 | Soll die Zuordnung der verschlüsselten Daten (F-KA-04, F-KA-12) eine Zusage werden? | ja, Bindung und Herkunft in einem Zug (E-KA-05) |
| Q-KA-04 | Wie soll dieser Bericht in die Steuerung eingehen? | als Sofortpunkt mit Konzept, Schritt 28 (E-KA-06, R92) |

**Nachgemessen am 10.11.2026** (Schnittstelle von GitHub, nur lesend):

| Was | Stand | Folge für die Befunde |
|---|---|---|
| Repositorium | öffentlich, Forks erlaubt, heute keiner | die Lauf-Protokolle liest jeder — **F-KA-25 wiegt schwerer** |
| Zeiger `produktion` | Ruleset seit 06.11.2026, nur gegen Löschen | F-KA-15 nannte ihn „ungeschützt“: Das stimmt für das Schreiben, nicht mehr für das Löschen |
| Tags | kein Ruleset | wer Schreibrecht hat, setzt einen `web-v*`-Tag; davor steht die Pflichtfreigabe |
| `main` | zwei Rulesets, PR-Pflicht, „Stufe 1“ strikt, Bypass ein Konto per PR | wie dokumentiert |
| Umgebung `produktion`, Freigabe von Fork-Läufen | **nicht lesbar** — der Proxy der Arbeitsumgebung sperrt beide Pfade | Handprüfung P-KA-01 |
