# Prüfdokument SR — Sicherheitsrunde II (Schritt 18)

*Gehört zu `Konzept-SR-Sicherheitsrunde-II.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Angelegt am 27.09.2026 mit dem Konzept (Fable); die Umsetzung
füllt es je Paket mit Mittel **und** Zahl. Stand: Konzeptphase — nichts
gebaut; geprüft ist die Buchführung (Spanne, Fahrplanzeile, Konzept) am
Stand R4-10 des 17er-Zweigs. Dieses Dokument bleibt, bis seine Prüfliste
abgehakt ist (K9); das Konzept wird nach der Freigabe des Abschlusses
gelöscht.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Befund ist eine Lesung, keine Messung an der Anlage.** | Keine Probe gefahren, kein Prüfstand für den Befund (Konzept 2.1); die örtliche Anlage lief nur für den Prüfbericht dieses Commits. Jede Trefferzahl ist ein `grep` am 27.09.2026; Aussagen wie „`anteil_wechseln()` fasst die Blatt-Marke nicht an" oder „`user_id` wird an einer Stelle gesetzt" stammen aus dem Code, nicht aus einem Lauf. | Die Umsetzung misst je Paket zuerst das Ausgangsmaß und trägt die Zahl hier ein; weicht sie vom Konzept ab, ist das ein Befund, kein Fehler des Konzepts. |
| **Der Baum ist der 17er-Zweig, nicht `main`.** | Gelesen wurde `26b4761` (R4-10). 17 ist nicht gemergt; R4-11 bis R4-26 stehen aus, darunter R4-14 (Demo-Änderungsmarke in `ingest.php`, `auth_guard.php`) und R4-15 (`days.created_at`, Migration) — beide an Dateien, die SR anfasst. | Wer die Umsetzung beginnt, nimmt `main` nach dem Merge von 17 und misst neu: `git log --stat 26b4761..origin/main -- server/` zeigt, was dazwischen kam. |
| **Der Nummernriegel ist auf diesem Zweig rot — mit fremden Nummern.** | `nummern.py` misst den Arbeitsbaum gegen die Basis zu `origin/main`; weil der Zweig auf dem 17er-Zweig sitzt, „legt" er dessen Nummern 340 bis 343 mit an (F-SR-12). Ob SR selbst kollidiert, zeigt das Werkzeug erst nach dem Merge von 17. | Vor dem Konzept-PR (nach dem Merge von 17): `python3 tools/steuerung/nummern.py` → 0; der frische Prüfbericht auf dem Merge-Commit trägt die Zeile grün. |
| **Zwei Browser-Cookies in echten Browsern.** | Bindungs- und Gerätecookie lassen sich örtlich nur in Chromium (Bilderlauf, Bedienprobe) messen; Firefox und WebKit nur von Hand mit `--motor` (Nr. 300). Wie ein Browser zwei `SameSite=Strict`-Cookies bei einer Weiterleitung nach 303 behandelt, entscheidet der Browser. | P-SR-07 in Firefox; Bedienweg `zweitfaktor.mjs` mit `--motor firefox` vor dem PR (SR-02). |
| **30 Tage.** | Der Ablauf des Gerätecookies lässt sich nicht abwarten; die Probe stellt `gueltig_bis` und misst den Ablauf am Server, nicht am Browser. | Zweitfaktorprobe (SR-02) mit gestelltem Datum; die Betreiberin sieht den echten Ablauf frühestens am 31. Tag. |
| **Ein echtes Sicherungsziel während des Schlüsselwechsels.** | Die Arbeitsumgebung fährt kein Ziel an, das außer Haus schreibt (`Sandbox-Setup.md` 6); die Versandprobe hat Gegenstellen im Container. Ob die alte Kopie auf dem Ziel bleibt und die neue dazukommt, zeigt nur eine Anlage mit Ziel. | P-SR-08 auf Staging (eigenes SFTP-Ziel, 6a Schritt 9). |
| **Das Webspace-Backup des Hosters.** | Der Fall, gegen den Nr. 242 schützt — eine Sitzungsdatei aus einem fremden Backup —, lässt sich nur nachstellen (eine Datei ohne Bindung), nicht herstellen. | Sitzungsprobe, Fall „Datei ohne Bindung" (SR-01). |
| **Der Deadlock unter Produktivlast.** | Die Verbindungsprobe stellt zwanzig gleichzeitige Pakete gegen den eingebauten PHP-Server mit Arbeitern her (`tools/proben/verbindung/`); auf Staging fehlt der Wurzelzugang zur Datenbank, Stufe 2 misst es nicht. | P-SR-11: die Zahl aus der örtlichen Probe; auf Produktiv zeigt es der Reiter System (`gedraengel_vermerken()` schreibt ins Fehlerprotokoll des Webspace, Nr. 248). |
| **Der Notzugang von der Hoster-Seite aus.** | Die Nachweisdatei per FTP anlegen, den Wert aus dem Datenbankwerkzeug abschreiben, das Passwort eingeben — das ist Bedienung an einer echten Anlage mit zwei Hoster-Werkzeugen. | P-SR-10 auf Staging. |
| **Die Dauer des Proof-of-Work auf echten Handys.** | Der Prüfstand misst Chromium, ungedrosselt und mit vierfacher CPU-Drosselung; ein altes Diensthandy ist ein anderer Prozessor mit anderem Browser. Die Konstante `POW_BITS` wird nach der Drosselung gewählt und ist eine Näherung. | P-SR-14 auf dem alten Diensthandy; ist es dort spürbar länger als drei Sekunden, ist die Bitzahl um eins zu senken (Halbierung), und der Wert steht dann hier. |

## 2. Maschinell geprüft — Konzeptphase (SR-00)

| Schritt | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| SR-00 | `python3 tools/steuerung/decken.py` | 20 Decken der Steuerungsdokumente, nach dem Schreiben der Fahrplanzeile und der Spanne | **20 Decken, 0 gerissen** |
| SR-00 | `python3 tools/steuerung/uebersicht.py --pruefen`, `--ziel 18` | Kopfzeilen des Backlogs; die Punkte mit Ziel 18 | **77 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 8 mit Ziel 18** (210, 228, 232, 233, 242, 247, 249, 251) |
| SR-00 | `python3 tools/steuerung/nummern.py --ohne-holen` | neue Nummern gegen `origin/main` und die Remote-Zweige | **4 Überschneidungen — 340 bis 343, alle aus 17** (F-SR-12); SR vergibt im Konzept keine Nummer, die Spanne 350–359 steht im Kopf. Grün erst nach dem Merge von 17 |
| SR-00 | `grep -rn "E-SR-\|Konzept SR" docs/` vor dem Anlegen | Ist das Kürzel frei? | **0 Treffer** (27.09.2026) |
| Befund | `grep -rn "sk_versiegeln(" server/ --include=*.php` | Zwecke des Serverschlüssels gegen `Technik.md` 4.97c | **6 Zwecke in 5 Dateien** (`adminbackup_lib`, `sicherungsziel_lib`, `totp_lib`, `protokoll_archiv_lib` je `sk_versiegeln()`; `komplett_lib` mit dem rohen Schlüssel) gegen **4** in der Tabelle — F-SR-01 |
| Befund | `grep -rn "\$_SESSION\['user_id'\] *=" server/` | Wo eine Sitzung angemeldet wird | **1** (`anmeldung_vollenden()`, `login.php`) |
| Befund | `grep -rn "session_regenerate_id\|setcookie(" server/ --include=*.php` | Stellen, die SR-01 kennen muss | **2 + 2** (`login.php`, `pw_handling.php`; `session_lib.php` beide Enden) |
| Befund | `grep -rn "install-nachweis-\|nachweis" server/install.php server/wiederherstellen.php` | Verbraucher der Nachweis-Hilfe (R83) | **2 Dateien** — SR-04 wäre der dritte |
| SR-00 | `bash tools/pruefstand/pruefen.sh --basis origin/claude/schritt-17-konzept-mockups-q0yjcm` (Stufe klein: Riegel, `steuerung`, `nummern`) | der Baum des Konzept-Commits gegen die Basis, auf der er sitzt | **21 grün, 1 rot (`nummern`, F-SR-12), 0 nicht gemessen, 39 s**; Bericht in der Commit-Nachricht; nichts in den Baum geschrieben |
| SR-00 | `bash tools/pruefstand/pruefen.sh` (Basis `origin/main`) | derselbe Baum — misst den 17er-Unterschied mit (R4-01 bis R4-10) | **35 grün, 2 rot, 2 nicht gemessen, 883 s**: rot `nummern` (F-SR-12) und `stilvergleich` (eine ungeplante Signatur an `login.php`, `input <label>`: `outline` — aus 17, nicht aus SR); nicht gemessen `android-bau` (Ausbaustufe `android` fehlt) und `schemaprobe` (Modul `plattform` fehlt). Die Bedienprobe schrieb `tools/bedienprobe/ausgabe/` neu; zurückgesetzt vor dem Commit |

## 3. Im Browser geprüft

Nichts — die Konzeptphase ändert keine Seite. Die Anlage lief (HTTP 200 auf
`login.php`, Fassung 21.1.8, 0 Migrationen offen, zwei Prüfkonten) nur für
den Prüfstand.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Die Umsetzung hängt je Paket ihre Punkte an (P-SR-13 ff.).

| Nr. | Bedienweg | Erwartung | Scheitern |
|---|---|---|---|
| P-SR-01 | ~~Konzept Abschnitt 3.2 lesen; Q-SR-01 bis -09 beantworten.~~ **Erledigt 27.09.2026:** Q-SR-01 bis -11 in der Konzeptsitzung beantwortet (Klickrunde), festgehalten als E-SR-17 bis -26. | — | — |
| P-SR-02 | ~~Freigabe des Konzepts.~~ **Erteilt 27.09.2026** (E-SR-27). Offen bleibt der Weg auf `main`: nach dem Merge von 17 eine Instanz beauftragen, die `main` nach `Pruefablauf.md` 5.3 aufnimmt (örtlich mergen, Prüfstand, Merge-Commit mit Bericht) und den Konzept-PR öffnet; Stufe 1 muss dort grün sein, auch `nummern`. | Rahmenplan auf `main`: Fahrplanzeile 18 „freigegeben"; `uebersicht.py --ziel 18` 8; Prüfbericht des PR-Kopfs ohne rote Zeile. | Stufe 1 rot am Kopf-Commit (Bericht fehlt, falscher Baum, `nummern` rot) — kein Merge, Instanz beauftragen. |
| P-SR-03 | Nach beiden Merges: Umsetzungsinstanz mit Opus auf eigenem Zweig von `main` starten (Reihenfolge E-SR-14: SR-01, -02, -07, -05, -03, -04, -08, -06). Nach SR-03 hält sie an (H-SR-06): dann eine Fable-Instanz zur Gegenlesung von SR-03 starten, danach Opus weiter. | Erster Commit `SR-01: …`; Zweig nach jedem Paket gepusht; Statusblock fortgeschrieben; nach SR-03 eine Meldung „warte auf Gegenlesung". | Ein Paket ohne Push, ein Statusblock ohne Fortschreibung; SR-04 beginnt ohne Gegenlesung. |
| P-SR-04 | **Vor dem Deploy von SR-01** eine Ankündigung setzen (Betrieb → Servereinstellungen, Karte „Ankündigung"): „Nach dem Update einmal neu anmelden." | Nach dem Deploy landet jede Angemeldete auf der Anmeldeseite mit dem Satz zum Grund; Uhr und Handy puffern. | Die Anmeldeseite ohne Erklärung; oder eine Sitzung von vorher läuft weiter (dann ist E-SR-04 nicht gebaut). |
| P-SR-05 | Die Bilder des Bilderlaufs ansehen, die die Umsetzung hier verlinkt (Code-Schritt mit Haken, Karte „Zweitfaktor" mit Zeile, Karte „Schlüssel des Servers" in drei Lagen, Seite des Notzugangs) — statt Mockups (Q-SR-08). | Vorhandene Bausteine, nichts Neues; sonst vor dem Paket sagen. | Ein Element, das in `Design.md` 9 nicht steht. |
| P-SR-06 | Nach dem Deploy von SR-02 (Migration `vertraute_geraete`): als Administratorin `update.php` aufrufen, Betrieb → Updates. | Migration gelaufen; Wartungsmodus geht aus. | Wartungsmodus bleibt an; Statuszeile nennt eine ausstehende Migration. |
| P-SR-07 | Auf Staging, in Chromium **und** Firefox: anmelden mit Code, Haken „Dieses Gerät n Tage merken" (n ist die Dauer deiner Rollengruppe aus Betrieb → Servereinstellungen, Karte „Anmeldung"); abmelden; anmelden. Dann Einstellungen → Profil, Karte „Zweitfaktor": Zahl, „Alle vergessen"; anmelden. Dann Passwort wechseln; anmelden. Zuletzt die Dauer der Verwaltungsrollen auf „aus" stellen; abmelden; anmelden. | Zweite Anmeldung ohne Code; nach „Alle vergessen" und nach dem Passwortwechsel wieder mit Code; mit „aus" kein Haken und Code bei jeder Anmeldung; Protokoll (Verwaltung) zeigt `totp_geraet_gemerkt` und `totp_geraete_vergessen`. | Ein Code, wo keiner sein sollte — oder keiner, wo einer sein muss (nach „Alle vergessen", nach dem Passwortwechsel, bei „aus"). |
| P-SR-08 | Auf Staging (eigenes SFTP-Ziel, mindestens ein Adminpaket, ein Protokoll-Archiv und ein Komplett-Stand vorhanden): Betrieb → Servereinstellungen, „Serverschlüssel wechseln". Schlüsselblatt drucken. Abmelden, anmelden. „Jetzt weiterarbeiten" bis „0 von n offen". „Jetzt sichern" (Komplett). Dann „Alten Schlüssel entfernen". | Blatt mit **drei** Kacheln (Serverschlüssel, bisheriger, Anteil); nach dem Anmelden die Rückfrage mit dem Satz zur Rotation; Karte „noch n von n" → „0"; „Alten Schlüssel entfernen" verweigert **vor** dem frischen Stand mit Grund, danach erlaubt; Protokoll drei Arten; Mail bei Beginn und Abschluss; auf dem Ziel: alte Dateien unverändert, neue dazu (Archiv unter neuem Namen, frischer Stand). | Ein Stück, das sich danach nicht öffnen lässt (Zweitfaktor-Anmeldung, Ziel-Zugang „Verbindung prüfen", Konto-Backup einspielen, Archiv herunterladen); „Alten Schlüssel entfernen" ohne frischen Stand möglich; ein altes Blatt vernichtet, obwohl das Ziel noch Stände unter dem alten Schlüssel trägt (E-SR-10). |
| P-SR-09 | Nach P-SR-08 (freiwillig, mit der halbjährlichen Probe-Wiederherstellung, 6.3): einen Komplett-Stand **von vor** dem Wechsel vom Ziel holen und mit dem alten `server_key` vom alten Blatt in eine Wegwerf-Anlage einspielen. | Öffnet mit dem alten, nicht mit dem neuen Schlüssel — so sagt es das Runbook. | Öffnet mit keinem (dann ist der alte Wert falsch abgeschrieben — Kennung vergleichen). |
| P-SR-10 | Auf Staging (genau eine BetreiberIn mit Zweitfaktor, Codeblatt beiseite): `zweitfaktor_notweg.php` aufrufen, Dateinamen ablesen, Datei per FTP anlegen, im Datenbankwerkzeug des Hosters den Wert lesen (`SELECT v FROM app_state WHERE k = 'notzugang_geheim'`, so steht es im Runbook), Adresse, Wert und Passwort eingeben. Danach dasselbe noch einmal mit dem **alten** Wert, und dasselbe mit zwei BetreiberInnen. | Zweitfaktor aus, Mail im Postfach, Protokoll `totp_zurueckgesetzt` mit `weg = notweg`, Datei weg, in `app_state` ein neuer Wert, Anmeldung führt ins Einrichtungstor. Mit dem alten Wert und mit zwei BetreiberInnen: dieselbe Meldung wie ohne Datei, nichts geändert. | Eine Auskunft, ob es genau eine BetreiberIn gibt oder ob der Wert stimmte; eine Antwort, die ohne Datei oder mit falschem Wert anders aussieht oder länger dauert als mit. |
| P-SR-11 | Die Zahl aus SR-05 hier lesen: `php tools/proben/verbindung/probe.php --frei 20`. | **0 × 503, 20 von 20 Paketen, 0 Gedrängel** im Fehlerprotokoll (Sollwert Nr. 210). | Eine 503 oder ein `1213` im Protokoll — dann ist die Schleife zu kurz oder die `days`-Fortschreibung nicht hinter dem Commit. |
| P-SR-12 | Freiwillig, nach SR-01 auf Staging: angemeldet bleiben, in den Entwicklerwerkzeugen des Browsers das Bindungscookie löschen, Seite neu laden. | Anmeldeseite mit dem Satz zum Grund `bindung`; nach dem Anmelden läuft alles. | Die Seite lädt weiter, als wäre nichts. |
| P-SR-13 | Nach SR-07 auf Staging: über ein gemerktes Gerät anmelden (kein Code), dann Betrieb → Servereinstellungen → „Schlüsselblatt drucken". Code eingeben. Blatt ansehen, zurück, „Server-Anteil wechseln" **nicht** ausführen, nur die Karte ansehen. Dann in einem zweiten Reiter Verwaltung → ein Konto → Rolle ändern. | Vor dem Blatt erscheint der Code-Schritt („Code bestätigen"), danach das Blatt; die Rollenänderung im zweiten Reiter geht ohne neuen Code durch (Frist 15 Minuten läuft). Nach 15 Minuten fragt die nächste Handlung der Liste wieder. | Das Blatt erscheint ohne Code nach einer gemerkten Anmeldung; oder der Code wird bei jeder Handlung verlangt, obwohl der letzte keine 15 Minuten alt ist. |
| P-SR-14 | Nach SR-08 auf Staging, auf dem **alten Diensthandy** und am Rechner: Registrierungsseite öffnen (Betriebsart auf „Selbstregistrierung" oder „nach Prüfung" stellen, danach zurück), Adresse und Passwort tippen, absenden. Stoppuhr nur, falls der Knopf „Sicherheitsprüfung läuft …" zeigt. | Am Rechner keine spürbare Wartezeit; am alten Handy höchstens ein paar Sekunden, dann geht es weiter. Die Zahlen aus dem Prüfstand (Median, Höchstwert, `POW_BITS`) stehen in Abschnitt 2. | Der Knopf wartet am Handy länger als drei Sekunden (dann `POW_BITS` um eins senken, SR-08 sagt, wo), oder die Registrierung geht ohne die Kleinzeile durch, obwohl JavaScript aus ist (dann fehlt die Serverprüfung). |

## 5. Grenzen der benutzten Prüfmittel

- `decken.py`, `uebersicht.py` und `nummern.py` messen Form (Decken,
  Grammatik, Ziel, Kollision), nicht Inhalt: Eine Fahrplanzeile mit
  richtigem Vokabular und falschem Satz ist für sie grün.
- Der Prüfstand der Stufe klein fährt bei einer reinen `docs/`-Berührung
  nur die Riegel; er belegt, dass die Buchführung hält, nicht, dass der
  Befund stimmt.
- `grep` zählt Schreibweisen: Die Zahl der `sk_versiegeln()`-Aufrufer
  findet den rohen Schlüssel in `komplett_lib.php` nicht — er steht in der
  Tabelle, weil die Doku (`Backup-Format.md` 6.3, `kdf: null`) ihn nennt.
  Die Umsetzung baut das Inventar aus beidem und zählt am Ende unabhängig
  nach (F-SR-01).
- Die Sitzungsprobe (SR-01) misst den eingebauten PHP-Server; ob ein Hoster
  `session.save_path` oder Cookie-Direktiven übersteuert, zeigt nur Betrieb
  → Status (Zeile „Sitzungsablage") auf der Anlage selbst.

## 6. Was aus der Runde offen bleibt

*(wird von der Umsetzung gefüllt: Punkte, die nach 18 ein neues Ziel
tragen — nach E-SR-25 nur Nr. 232; Nr. 228 wird gebaut (E-SR-26) —, und
die Reste je Paket)*
