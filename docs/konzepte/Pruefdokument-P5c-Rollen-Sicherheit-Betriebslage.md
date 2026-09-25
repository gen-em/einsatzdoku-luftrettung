# Prüfdokument P5c — Rollen, Sicherheit, Betriebslage

Gehört zu `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`. Nach `CLAUDE.md`
7: was maschinell geprüft wurde (Mittel **und** Zahl), was im Browser, was
nicht und warum, und eine abhakbare Prüfliste — je Punkt der Bedienweg, das
erwartete Ergebnis und woran ein Scheitern zu erkennen ist. Angelegt mit
AP1; jedes Paket schreibt seinen Abschnitt fort.

## 0. Was nicht geprüft werden konnte

*Steht vorn, weil es das ist, was noch jemand tun muss.*

| Was | Warum nicht | Wann dann |
|---|---|---|
| **`EXPLAIN` und die Migration auf Staging** (AP7) | Örtlich gemessen: Migration über Betrieb → Updates, beide Indizes angelegt, Wartung an (1f); `EXPLAIN` der Herkunftsabfrage über `idx_missions_started`. Ob MySQL auf Staging denselben Plan wählt, hängt an Fassung und Bestand. | nach Merge und `update.php`: P-P5c-32 |
| **Die Zahlen an einem echten Bestand** (AP7) | Nachgerechnet ist die Seite gegen unabhängiges SQL an einem gestellten Bestand (Messstand, Referenzbestand, Demo, Papierkorb, künftige Einsätze). Ob die Zahlen auf Produktiv zur Erfahrung der BetreiberIn passen, sieht nur sie. | P-P5c-33 |
| **Die drei Reiter in Firefox und WebKit als Bild** (AP7, Nr. 300) | Der Bilderlauf fährt Chromium. Von Hand gemessen ist in allen drei Motoren der Überlauf bei 1200 bis 1440 px (36 Messungen, 0), nicht das Bild in acht Breiten. | P-P5c-34 (Handy) |
| **Ein echtes Monitoring gegen den Health-Endpunkt** (AP6) | Die Arbeitsumgebung hat keines und erreicht Staging nicht. Belegt ist der Endpunkt mit `curl` gegen die örtliche Anlage: 403 dreimal mit gleicher Dauer, 200 mit genau den acht Feldern, 503 bei ausstehender Migration und aus dem Tor in der Wartung, 429 ab dem 61. Abruf (1e). Ob ein Uptime-Dienst die Antwort so liest, wie das Runbook es sagt, zeigt erst ein echter. | nach dem Merge: P-P5c-29 |
| **`speicher_pct` mit den Grenzen eines echten Hosters** (AP6) | Örtlich stehen die Kontingente, die die Sandbox einträgt; der Wert ist gegen `speicher_messen()` gerechnet (1e). Ob er zu dem passt, was der Hoster anzeigt, sieht nur, wer beide nebeneinander legt. | P-P5c-30 |
| **Der Zweitfaktor mit einer echten Authenticator-App** (AP5) | Die Arbeitsumgebung hat kein Handy. Belegt ist, dass der QR-Code die angezeigte `otpauth://`-Adresse trägt (jsQR liest den Abzug, gleich) und dass drei eigene Rechner nach RFC 6238 dieselben Codes bilden wie der Server (6 / 6 Vektoren) — nicht, dass eine bestimmte App die Adresse annimmt, Aussteller und Konto richtig zeigt und mit der Uhr des Handys im Fenster bleibt. | P-P5c-20, -21 |
| **`STAGING_TOTP` und Station D** (AP5) | Das Geheimnis des Prüfkontos entsteht erst auf Staging (E-P5c-105). Örtlich ist die Kette gelesen und der Riegel „fehlt → rot" gegen den YAML-Text geprüft; gelaufen ist Stufe 2 mit Zweitfaktor nicht. | P-P5c-25 |
| **Das Codeblatt aus Firefox gedruckt** (AP5) | Playwright druckt nur aus Chromium ein PDF (eine Seite, gemessen). Firefox und WebKit haben das Blatt am Bildschirm in acht Breiten gezeigt, nicht auf Papier. | P-P5c-22 |
| **Die Mail „Zweitfaktor zurückgesetzt" in einem echten Postfach** (AP5) | wie bei der Rundmail: gemessen bis Katalog und Warteschlange (Mailprobe Abschnitt 1). | P-P5c-24 |
| **Die fünf Minuten des halben Stands in echter Zeit** (AP5) | Keine Probe wartet fünf Minuten. Die Wartungsprobe stellt eine halbe Sitzung mit abgelaufener Frist her (12a) und sieht das Passwortformular samt Räumen des Vormerkfachs; dass die Frist bei fünf Minuten liegt, ist gelesen (`TOTP_HALB_FRIST_S`). | P-P5c-23 (nebenbei) |
| **Die Bedienwege des Zweitfaktors in Firefox und WebKit** (AP5) | Die Bedienprobe fährt nur Chromium (Nr. 300); von Hand gefahren ist in Firefox und WebKit der Bilderlauf der vier neuen Seiten (1d), nicht die Bedienung. | P-P5c-28 (Handy) |
| P-P5c-29 | **Das eigene Monitoring fragt die Anlage** (AP6) | nach Merge und Deploy auf Staging (nach dem Tag auf Produktiv): Runbook `Technik.md` 7 „Health-Endpunkt einrichten" — Token erzeugen, in `config.php` eintragen, die Adresse im Monitoring eintragen, Abruf einmal je Minute | Das Monitoring meldet „oben"; `curl -s …/api/health.php?token=<Wert>` zeigt `"ok":true` und die gerade ausgelieferte `web_version`; ohne und mit falschem Token **403** `token` | 403 mit richtigem Token (Eintrag nicht unter `betrieb` oder ein Leerzeichen im Wert); 200 ohne Token; ein Feld mehr oder weniger als die acht; das Monitoring meldet nach einer Minute Dauerabfrage 429 (Abruf zu dicht eingestellt) |
| P-P5c-30 | **Die Zahlen im Rumpf stimmen zur Anlage** (AP6) | am Tag nach P-P5c-29 (der Aufräumjob muss einmal gelaufen sein): die Antwort neben Betrieb → Status und Betrieb → Servereinstellungen, Karte „Speicher", legen | `speicher_pct` = der höchste von drei Anteilen, abgerundet: die beiden Balken der Karte (Backups, Installation gesamt) und die Datenbankgröße gegen das Kontingent der Datenbank; `jobs_alter_s` klein (Minuten, nicht Tage); `system_24h` = Zahl im Reiter System, Zeitraum 1 Tag; `protokoll_fehler` 0 | `speicher_pct` bleibt `null` nach einem Tag (Job läuft nicht, P-P5c-07 ansehen); eine Zahl weicht von der Seite ab |
| P-P5c-31 | **Wartung und Update aus Sicht des Monitorings** (AP6) | beim nächsten Deploy mit Migration: das Monitoring beobachten | während der Kette 503 mit `"error":"maintenance"`; nach Wartung aus, aber vor `update.php`, 503 mit `"migration_ausstehend":true`; danach 200 | 200, während `update.php` aussteht; 403 in der Wartung (dann prüft der Endpunkt den Token vor dem Tor) |
| P-P5c-32 | **Die Migration und der Index auf Staging** (AP7) | nach Merge: Betrieb → Updates → `update.php`, Wartung aus; dann in phpMyAdmin (Staging): `SHOW INDEX FROM missions` und `EXPLAIN SELECT m.origin, COUNT(*) FROM missions m WHERE m.user_id <> 0 AND m.deleted_at IS NULL AND m.started_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY AND m.started_at <= UTC_TIMESTAMP() GROUP BY m.origin` | `2026_09_24_statistik_beginn` unter *Angewendet*; `idx_missions_started` **und** `idx_missions_deleted` in der Indexliste; `EXPLAIN` mit `key = idx_missions_started` | ein Index fehlt (dann ist die Migration nicht gelaufen oder abgebrochen — Betrieb → Updates, Protokoll); `key` leer bei sehr kleinem Bestand ist kein Fehler, dann `possible_keys` ansehen |
| P-P5c-33 | **Die drei Reiter an echten Zahlen** (AP7) | auf Staging nach dem Merge, auf Produktiv nach dem Tag: Betrieb → Statistik, alle drei Reiter, eine Spalte mit Sortieren und „Als CSV" im Reiter Geräte | Kennzahlen oben führen in ihren Reiter; „Einsätze in 30 Tagen" ist dieselbe Zahl wie in der Tabelle; die Herkunft summiert sich zu dieser Zahl; nach einem Klick auf einen Spaltenkopf bleibt der Reiter Geräte offen | Sortieren springt auf NutzerInnen (fehlendes `r`); „Aktiv" kleiner als „Angemeldet"; eine Herkunft fehlt; die Zahl weicht grob von der Summe der eigenen Statistiken ab (einzelne Einsätze sind erklärt, Handbuch 12.2) |
| P-P5c-34 | **Die Statistik am Handy** (AP7) | am Handy: Betrieb → Statistik, die Reiter wechseln | die Reiterreihe passt oder rollt in sich; je Reiter steht die Tabelle **vor** der Karte; die Tabelle mit fünf Fenstern rollt in ihrer Karte, die Seite nicht | die Seite läuft waagerecht aus dem Bild; die Karte steht vor der Tabelle |
| **Staging in Rot, Produktiv in Blau** | Beides sind echte Anlagen (Station D und E). Örtlich ist das Etikett gestellt und gemessen (1), aber ob die `config.php` von Staging den Eintrag trägt, sieht nur, wer Staging aufruft. | nach dem Merge (Staging) und nach dem Tag (Produktiv): P-P5c-01, -02 |
| **Eine Rundmail in einem echten Postfach** | Die Arbeitsumgebung erreicht keinen Mailserver; die Mailprobe spricht mit einem SMTPS-Nachbau, der annimmt und wegwirft. Gemessen ist der Weg bis „250 angenommen", nicht die Zustellung. | P-P5c-03 |
| **Firefox und WebKit** | Bilderlauf und Bedienwege laufen im Prüfstand in Chromium — **auch in der Hauptstufe**, anders als `Pruefablauf.md` 3 versprach (F-P5c-103, Nr. 300). Die **Bedienwege** sind in Firefox und WebKit nicht gefahren. | Bilderlauf für AP4 **von Hand** in Firefox 142 und WebKit 26 (1c); die Bedienwege bleiben bei Nr. 300 |
| **Die Anmeldung als Support im Browser** (AP4) | Die Rollenprobe fälscht ihre Sitzungen (E-P5c-78) — die Anmeldung leitet das Token im Browser per PBKDF2 ab. Ob ein Support-Konto sich anmeldet, den Datenschlüssel entsperrt und im Menü genau NutzerInnen und Protokoll sieht, ist nicht mit einem echten Konto gefahren. Die Anmeldung fragt die Rolle nur beim Wartungsmodus. | P-P5c-15 |
| **Die Sicht des Supports im Bild** (AP4) | Der Bilderlauf meldet sich nur als BetreiberIn an (Nr. 297). Belegt ist die Sicht **als Markup** in der Rollenprobe (drei Kacheln, einspaltig, Mengen ohne Formular, keine Backups, kein Löschen, kein Speichern — mit Gegenprobe beim Admin), nicht als Bild in acht Breiten. | P-P5c-16 |
| **`update.php` nach einem echten Deploy** (AP4) | Örtlich nachgestellt: ENUM zurück, Registereintrag weg, gemerkter Katalog-Hash verworfen (1c). Dass die Kette nach dem Deploy die Wartung anlässt und nach `update.php` sagt, was zu tun ist, zeigt nur Staging. | P-P5c-17 |
| **Die Bedienwege in Firefox und WebKit** (AP4, F-P5c-103) | Der Prüfstand fährt die Bedienprobe nur in Chromium; von Hand ist nur der Bilderlauf in beiden gefahren. | Nr. 300 |
| **Die Plattformmatrix der Hauptstufe** (AP4, F-P5c-103) | Der Prüfstand fährt in `haupt` die Schemaprobe über vier Datenbanken unter PHP 8.4 — **nicht** PHP 8.3 und **keinen** Kreislauf je Datenbank, auch nicht gegen MySQL 8.4.0, obwohl `Pruefablauf.md` 3 beides versprach. Von Hand unter PHP 8.3.33 gefahren: Rollenprobe und Migration über die Seite (1c). **Nicht gefahren:** ein Kreislauf `edbak` gegen MariaDB 10.6, MySQL 8.0 und 8.4.0 — AP4 ändert nichts an Sicherung und Import, nur ein ENUM, dessen Werte die Sicherung als Text trägt. | Backlog Nr. 300 (Prüfkette); P-P5c-17 misst Staging (MySQL 8.4) |
| **Die Bestätigung in einem echten Postfach** (AP4) | wie bei der Rundmail: gemessen bis zur Warteschlange und zum Protokolleintrag, nicht die Zustellung; örtlich geht sie nicht hinaus („NICHT zugestellt"). | P-P5c-18 |
| **Das Archiv in echter Zeit** (AP2) | Örtlich ist der Zeitraum gestellt: Die Protokollprobe setzt Marke und Einträge in die Vergangenheit und ruft den Job mit knappem Budget auf. Ob der Job auf einer Anlage **ohne Cron** in einer Woche drankommt und nach dem Merge die Wochen seit dem ältesten Eintrag nachholt, zeigt nur Staging. | P-P5c-07 |
| **Die Sicht des Admins im Bild** (AP2) | Der Bilderlauf kennt keine reine Admin-Rolle (F-P5c-41) — seine Seiten mit `rolle: admin` meldet er als BetreiberIn an. Die vier Reiter des Admins belegt die Rollenprobe **als Statuscode**, nicht als Bild. | P-P5c-06 |
| **Ein Archiv auf einem echten Ziel** (AP2) | Die Versandprobe (Teil 13) schickt Archive an einen SFTP-Nachbau und misst, dass die Aufbewahrungsregel sie nicht anfasst. Ein echter Hoster mit vsftpd oder OpenSSH ist `--echt` und in dieser Arbeitsumgebung nicht gefahren. | P-P5c-07 |
| **Die Fehlerseite beim Hoster** (AP3) | Örtlich ist sie über einen eigenen `php -S` provoziert (Teil 7 der Protokollprobe). Auf Staging und Produktiv lässt sich eine ungefangene Ausnahme nicht auslösen, ohne eine Datei unter `server/` abzulegen — und das tut dieses Paket mit Absicht nicht (E-P5c-97). Was der Hoster davor schiebt (seine eigene 500-Seite, `display_errors`), sieht nur, wer dort einen echten Fehler erlebt. | P-P5c-11 (Bild), P-P5c-13 (Protokoll des Webspace) |
| **Eine Datenbank, die wirklich weg ist** (AP3) | Nachgestellt ist „die eigene Verbindung ist getrennt" (`KILL CONNECTION`). Ein gestoppter Server kostet zusätzlich einen Verbindungsversuch mit Zeitgrenze — einen je Anfrage, weil `system_melden()` sich den Ausfall merkt. Gefahren ist das nicht: Es hieße, die Datenbank der Arbeitsumgebung anzuhalten, während sie niemand sonst braucht, und das Ergebnis wäre dasselbe Rückfall-Verhalten. | — (Grenze, 3) |
| **Die Kennungssuche als Seite auf MySQL 8.4** (AP3) | Die Kollation von `JSON_UNQUOTE()` ist auf allen vier Fassungen **als Abfrage** gemessen (F-P5c-89); die Seite selbst läuft örtlich nur auf MariaDB 10.11. | P-P5c-10 |
| **Die Ankündigung auf Staging unter Last des Huckepack-Jobs** | Örtlich gibt es kein Cron und keine fremden Aufrufe; die Rundmail wird hier mit `mail_job()` von Hand hinausgetragen. Wie lange vierzig Mails auf Staging brauchen, hängt davon ab, wer dort Seiten aufruft. | P-P5c-03 (Zeit notieren) |

## 1. Messprotokoll AP1 (23.09.2026, Web 20.38.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| Mailprobe, erweitert um Abschnitt 14 | `bash tools/proben/proben.sh mail` | **50 Prüfungen, 0 Befunde** (vorher 41). Neu: 6 erreichbare Konten → 6 Zeilen, **0,01 s gegen einen schweigenden Server** (sofort versucht: bis 30 s), 6 von 6 zugestellt in 1 Joblauf, zweite Rundmail abgewiesen, genau 1 Protokolleintrag; ohne Passwort, unbestätigt, gesperrt und Demo-Konto **nicht** darunter |
| Bedienwege der Seite | `node tools/bedienprobe/probe.mjs --nur betrieb-server` | **2 von 2 erfüllt**: gesetzt → sichtbar · weggeklickt → fort, auch auf Status · neu angemeldet → wieder da · entfernt → fort; die Rückfrage nennt „3 erreichbare Konten", Abbrechen schreibt nichts (Protokoll 0 → 0) |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **25 Paare, 0 verfehlt** — neu: Weiß auf Rot 4,78, Schnee auf Rot 4,68, Orange hell auf Rot **4,12** (Soll 3,0) |
| Register | `php tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke** (Z29 csrf 2/2, Z37 Meldung 4/4, Z10 app_state 2/2, Z38 `error_log(` 75/77 — AP1 hat keinen Aufruf dazugebracht) |
| Quelltext | `bash tools/quelltext/pruefen.sh alle` | **8 von 8 grün**, Textprobe 0 außerhalb der Ausnahmen |
| Abdeckung | `python3 tools/pruefstand/auswahl.py --abdeckung` | 0 ohne Muster; `ankuendigung_lib.php` löst die Mailprobe aus |
| Statuszeile „Umgebung", vier Fälle | eigener Lauf, `config.php` gestellt über `tools/konfig_stellen.php` | Präfix ohne Etikett → orange „Präfix ohne Etikett" · Farbe `lila` → orange „Farbe unbekannt", Kopfleiste rot · Staging ohne Präfix → blau „Staging … Mails ohne Präfix" · ohne Eintrag → blau „Produktiv … Kopfleiste blau, Mails ohne Präfix". `config.php` danach unverändert (0 Zeilen mit `umgebung`) |
| **Bilderlauf mit Etikett** (Station B, Stufe „neben", 8 Breiten) | `php mit_etikett.php node tools/screenshots/aufnehmen.mjs --stufe neben --etikett Staging` — der Umschalter stellt `app.umgebung` und `mail.betreff_praefix` über `tools/konfig_stellen.php`, wartet 3 s (F-P5c-69) und stellt danach zurück; dazu eine gesetzte Ankündigung (Ton Warnung, 2 h) | **496 Einzelbilder aus 62 Seiten** (Anmelde- und Wartungsseite eingeschlossen), 13 min 8 s: **Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0 · Karten außerhalb von `main.inhalt` 0 von 163 · gleiche Bilder über Breiten 0**. Etikett: **496 Titel und 424 Kopfleisten geprüft, 24 Abweichungen** — alle auf Notfallblatt (zwei Seiten) und Schlüsselblatt, deren Titel den Vorsatz nicht trug (F-P5c-70). Behoben und die drei Seiten nachgefahren: **24 Titel, 0 Abweichungen**. `config.php` danach: 0 Zeilen mit `umgebung` |
| **Bilderlauf ohne Etikett** (Gegenrichtung) | im Prüfstand (Stufe „neben") | kein Titel mit „[", keine Kopfleiste mit `kopf-umgebung` — der Bilderlauf misst die Gegenrichtung ohne Schalter mit; grün im ersten Prüfstand (745 s), Zahl im Prüfbericht des Commits |
| **Prüfstand, erster Lauf** (frische Anlage, `hochfahren.sh --neu`) | `bash tools/pruefstand/pruefen.sh` → Stufe „neben" (20.37.3 → 20.38.0), 37 Proben | **36 grün, 1 rot, 0 nicht gemessen**, 1 221 s. Rot: der Stilvergleich — 41 483 Elementmessungen, 546 Abweichungen, alle aus AP1, aber keine Liste, gegen die sie zu halten gewesen wären (F-P5c-72). Bilderlauf 745 s grün, Bedienprobe **50 von 50**, beide Kreisläufe grün. Nicht committet — der Baum änderte sich danach |
| **Stilvergleich mit Liste** | `bash tools/stilvergleich/gegen.sh` mit `geplant.txt` (30 Signaturen, geschrieben mit `--schreiben` und Zeile für Zeile gelesen: 21 aus den neuen Regeln, 9 Folgen der gewachsenen Katalogprobe — `height` an `html`/`body`, `top`/`bottom`/`inset` an absolut gesetzten Elementen) | **30 gemessen, 30 geplant, 0 ungeplant, 0 nicht gemessen** → grün. Gegenproben: eine Zeile gestrichen → rot („UNGEPLANT … kopf-umgebung : background"); eine erfundene Zeile dazu → rot („GEPLANT, ABER NICHT GEMESSEN") |
| Schnelltest im Browser (Chromium, 1440 px) | eigener Lauf, ohne und mit Etikett | Anmeldung: Streifen über der Karte, Kreuz schickt ab und kommt zurück (kein `CSRF` auf der Seite); Startseite: Kreuz schließt **ohne Neuladen** (URL gleich), nach dem Neuladen fort, **0 leere Behälter**; nach Abmelden und Anmelden wieder da; mit Etikett: Titel „[Staging] …", `kopf kopf-umgebung`, Reihenfolge Umgebung → Ankündigung; 0 Konsolenfehler |

**Vier Fehler auf dem Weg**; die ersten zwei beim ersten Lauf gemessen und behoben:
Das Skript der Ankündigung stand **in** der Reihe `.hinweise` und hielt sie
nach dem Schließen am Leben (F-P5c-65). Und die Prüfwerkzeuge klickten beim
Anmelden den ersten Absendeknopf der Seite — mit einer Ankündigung deren Kreuz
(F-P5c-64, 24 Stellen in 20 Dateien umgestellt).

**Ein dritter Fehler, gefunden vom Bilderlauf mit Etikett** (F-P5c-70):
Notfall- und Schlüsselblatt bauen ihre Hülle selbst, und ihr Titel trug den
Vorsatz nicht. Das Konzept hatte ihn auf AP9 verschoben (E-P5c-70, erste
Fassung); eine gemessene Lücke, die eine Zeile je Datei kostet, bleibt nicht
drei Pakete stehen.

**Ein vierter, gefunden vom Einrichten der frischen Anlage** (F-P5c-71):
`hochfahren.sh --neu` scheiterte in Schritt 2 mit 500, weil `install.php`
seit AP1 die Streifen zeigt und der Demo-Streifen `db.php` lud, bevor es eine
`config.php` gibt — dieselbe Art Fehler wie Nr. 288. Keiner der Läufe auf der
eingerichteten Anlage konnte ihn sehen. Behoben; `hochfahren.sh --neu` danach
rc 0.

**Und eine Lücke der Prüfkette** (F-P5c-72): Der erste Prüfstand war 36
grün, 1 rot — der Stilvergleich, weil es die Liste der geplanten
Abweichungen, gegen die `Pruefablauf.md` 6.10 eine Gestaltungsänderung
halten will, nur als Satz gab. Gebaut als `tools/stilvergleich/geplant.txt`
(E-P5c-74). **Nach dem Merge ist die Datei zu leeren** (Rahmenplan 6).

**Eine Falle des Prüfmittels, kein Fehler der Anwendung** (F-P5c-69): Wer
`config.php` schreibt und die Anlage in derselben Sekunde fragt, bekommt den
alten Stand — der OPcache prüft Zeitstempel höchstens alle zwei Sekunden. Der
Umschalter wartet deshalb drei Sekunden, bevor er misst.

## 1a. Messprotokoll AP2 (24.09.2026, Web 20.39.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Rollenprobe** (neu) | `bash tools/proben/proben.sh rollen` | **87 Erwartungen, 0 offen**: 28 Zeilen der Matrix aus `Technik.md` 4.99p × 3 Rollen (13 Protokoll, 7 Komplett-Backup, 8 Backup-Ziele), dazu die Wirkung: ein Rollenwechsel → **genau 1** Eintrag `rolle_geaendert`, ein Speichern ohne Wechsel → 0. **Gegenprobe:** das alte Tor `require_admin()` an `admin_sicherungsziele.php` kurz zurück → **8** Zellen der Admin-Spalte rot (Seite und sieben Handlungen), danach wieder 87 / 0. **Erster Lauf 37 Abweichungen**, alle Fehlanzeigen der Probe (F-P5c-73); danach eine echte: Token vor Rolle beim Archiv-Download (F-P5c-74, behoben) |
| **Protokollprobe** (neu) | `bash tools/proben/proben.sh protokoll` | **21 Erwartungen, 0 offen**: Häppchen (mehrere Aufrufe bei knappem Budget), entsiegelt **0** Treffer für `"ip:`, IPv4, IPv6 und Adressen in Sicherheit und E-Mail — gegen eine eigens angelegte Sperre **mit** IP und Adresse und eine Mail **mit** Empfänger und Fehlertext; fremde Kennung erkannt und nicht geöffnet; umbenanntes Archiv lässt sich nicht öffnen; Archiv 366 Tage alt → gelöscht, junges bleibt; Download: gewöhnliches ZIP, **genau 1** Eintrag `archiv_heruntergeladen`; der Versand erkennt den Namen. Verwaltung trägt Adressen — gezählt, nicht bewertet (E-P5c-75). **Gegenprobe:** `merkmal` in die Archivzeile zurück → rot |
| **Versandprobe, Teil 13** (neu) | `bash tools/proben/proben.sh versand` | **141 Erwartungen, 0 offen.** Drei Archive gehen an den SFTP-Nachbau und liegen drüben im Ordner `protokoll`; **die Regel „eins je Konto" entfernt keines**, auch nicht im zweiten Lauf (im selben Lauf räumt sie 3 Kontopakete anderer Konten auf — zu Recht); mit Versand aus bleibt ein viertes hier. **Gegenprobe:** die Riegelzeile in `sz_aufraeumen()` herausgenommen → **2 von 3** Archiven drüben gelöscht, **4** Prüfsätze rot; Zeile zurück, Datei byte-gleich. Der erste Bau war rot, ohne dass ein Archiv fehlte (F-P5c-87) |
| **Komplettprobe**, erweitert | `bash tools/proben/proben.sh komplett` | **67 Erwartungen, 0 offen** — neu: Kopfzeile „OHNE ZEILEN" steht, kein `INSERT` für `sicherheit_ereignisse` und `rate_limits`, **IP und Adresse eines eigens angelegten Sperrereignisses stehen nicht im Dump**; eingespielt kommen beide Tabellen leer und mit gleichem Schema an |
| Wiederherstellung | `bash tools/proben/proben.sh wiederherstellung` | **111 Erwartungen, 0 offen** (Schreiber `komplett_eingespielt` auf dem Weg) |
| **Bedienwege der Seite** (neu) | `node tools/bedienprobe/probe.mjs --nur admin-protokoll` | **2 von 2**: Reiter „Jobs" aktiv und in der Adresse, **0** Unterpunkte in der Leiste, der Menüpunkt „Protokoll" aktiv; eine Fehlerkennung im Suchfeld landet auf „System". Aufgeklappt stehen die Angaben da; **Höhe mit Angaben 68,4 px = fest 68,4 px**; die Art-Auswahl schickt ab, „Filtern" ist verborgen. **Gegenprobe:** Gegenregel `.zeile-mehr > summary.zeile` entfernt → **56,4 gegen 68,4 px**, rot (12 px — das Konzept schätzte 13) |
| **Bilderlauf** (Stufe „neben", 8 Breiten, einzeln nach dem ungültigen Vorlauf) | `node tools/screenshots/aufnehmen.mjs --stufe neben` | **520 Einzelbilder aus 65 Seiten** (neu `44a-protokoll`, `44b-protokoll-sicherheit`, `44c-protokoll-archiv`): **Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0 · 167 Karten, 0 außerhalb von `main.inhalt` · 0 gleiche Bilder über Breiten**; Etikett (keins erwartet): 520 Titel und 448 Kopfleisten, 0 Abweichungen. **Die drei Seiten zeigt er als BetreiberIn** — die Admin-Sicht nicht (0) |
| **Bedienprobe** (einzeln) | `node tools/bedienprobe/probe.mjs` | **52 von 52 Wegen erfüllt**, 0 verfehlt (vorher 50) |
| Zeilenhöhe über den Motor | eigener Lauf über `tools/motor.mjs`, Chromium | **1440 px: 68,39 = 68,39 px · 390 px: 110,98 = 110,98 px** (mit Angaben gegen fest, mittlere Zeilen) |
| Register | `php tools/zaehlung/zaehlen.php` | **40 Zeilen, 0 über der Decke**; neu Z39 (`new ZipArchive` außerhalb `zip_lib.php`: 4 → **0**) und Z40 (Listen- und Reiter-Markup außerhalb `ui.php`: 8 → **0**). Beim Bauen zweimal rot gesehen (F-P5c-77, -78). Z39 gegengeprobt: mit und ohne Rückstrich je **1** Treffer (F-P5c-84) |
| Stilvergleich mit Liste | `bash tools/stilvergleich/gegen.sh` | **42 575 Elementmessungen, 1 087 Abweichungen, 54 Signaturen geplant, 54 gemessen, 0 ungeplant, 0 nicht gemessen** — 30 aus AP1, 24 aus AP2 (Reiter, Protokollliste, aufklappbare Zeile, Auswahlfeld in der Filterreihe). Eine Signatur stand zuerst über zwei Zeilen (F-P5c-85, behoben) |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **25 Paare, 0 verfehlt**; „Orange tief auf Rauch" 4,04 trägt jetzt den aktiven Reiter (F-P5c-81) |
| Schemaprobe | `bash tools/sandbox/plattform.sh alles` | **4 × 19 Prüfungen, 0 Fehlschläge** (MariaDB 10.11.14 und 10.6.28, MySQL 8.0.46 und 8.4.0) — nötig, weil AP2 `migration_lib.php` berührt (Muster „migration") |
| Design-Tabellen | `python3 tools/erzeugen/design.py alle` | Bausteine **48** Funktionen (44 + 4), Symbole **58**, Medienblöcke **25**; eingesetzt bis zur nächsten Überschrift, wobei eine veraltete zweite Summenzeile gefallen ist (F-P5c-82) |
| **Prüfstand** | `bash tools/pruefstand/pruefen.sh` auf frischer Anlage, Stufe „neben" (20.37.3 → 20.39.0) | im Prüfbericht des Commits `P5c-AP2` |
| **Erster finaler Prüfstand** | derselbe Aufruf auf frischer Anlage | **39 grün, 1 rot, 0 nicht gemessen, 1 322 s.** Rot: die Schemaprobe — „Access denied for user 'root'@'localhost'". Ihr Eintrag rief sie seit PK-03 ohne Zugangsdaten und war nie gelaufen (F-P5c-88). Behoben: `plattform.sh schema`, einzeln **4 × 19 / 0**; Gegenprobe mit angehaltenem MySQL-8.4-Behälter → rot, „FEHLT mysql:8.4.0 (Port 3308)". Nicht committet — der Baum änderte sich |
| **Vorlauf des Prüfstands — ungültig** | derselbe Aufruf, 24.09.2026, 05:16–05:30 UTC | **31 grün, 8 rot, 1 nicht gemessen, 831 s.** Ungültig, weil ich daneben `plattform.sh alles` gestartet hatte (F-P5c-86): Die Schemaprobe legt für ihre Laufzeit eine Wegwerf-`config.php` auf die Probedatenbank, der Torwächter sah eine ausstehende Migration und schaltete um 05:22:12 die Wartung ein. Ab da 503 — Bilderlauf ab `10b` ohne Bilder (414 Konsolenfehler), danach Bedienprobe, Ingest, GPX, Kopplung, CSP, beide Anmeldewege der Kreisläufe. **Alle Proben vor 05:22 grün**, darunter Rollen-, Protokoll-, Komplett- und Versandprobe. Nicht als Beleg verwendet |

## 1b. Messprotokoll AP3 (24.09.2026, Web 20.40.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Protokollprobe, Teil 7** (neu) | `bash tools/proben/proben.sh protokoll` | **36 Erwartungen, 0 offen** (vorher 21). Neu 15 — ihr `php -S` läuft mit `output_buffering=4096`, wie beim Hoster: eine halbe Seite, dann eine Ausnahme → nur die Fehlerseite steht da; **Gegenprobe:** das Verwerfen des Puffers herausgenommen → **„halbe Seite davor", rot**, zurück byte-gleich (F-P5c-97). Außerdem: ungefangene Ausnahme → **500**, Kennung auf der Seite = Kennung im Reiter System, Art `ausnahme`, Urheber aus der Sitzung; **9 Marken, 0 gefunden** (Wert, Adresse, zwei IPs, Anfrage, Kopfzeile, `X-Forwarded-For`, Sitzungsinhalt, Sitzungskennung), der Wert als `'…'`; ohne Kontaktadresse „Nenne diese Kennung", mit ihr „Melde diese Kennung an" mit Verweis; unter `/api/` JSON mit `error`, `kennung`, `meldung`; `@` → **0** Einträge; 5 + 25 Warnungen → **20** Einträge, die Seite läuft weiter; Speicherende bei 8 MB → **500** und Eintrag `abbruch`; Kennungssuche **klein geschrieben** findet den Eintrag, aus Verwaltung → **303** nach System; Kommandozeile → **Rückgabewert 255**, Kennung auf stderr, Eintrag `cli`; eigene Verbindung getrennt → Rückfall mit der Kennung, **3 Zeilen in 0,2 s**, keine Adresse, kein Eintrag. Der erste Lauf zählte 1 statt 20 Warnungen (F-P5c-92, Probe berichtigt) |
| **Mailprobe, Abschnitt 13** (umgebaut) | `bash tools/proben/proben.sh mail` | **51 Prüfungen, 0 Befunde** (vorher 50). Liest den Reiter System statt einer `error_log`-Datei: **1** Eintrag mit Kennung, kein Empfänger darin, **dieselbe Kennung in der Fehlerspalte der Warteschlange**. Im Vorlauf rot, weil er noch die Datei las (F-P5c-96) |
| **Kopplungsprobe, Fall 27** (umgebaut) | `bash tools/proben/proben.sh kopplung` | **76 Erwartungen, 0 offen**; der Versandweg nach der Antwort belegt über einen Eintrag „smtp: Verbindung nicht möglich" im Reiter System statt über das Protokoll des PHP-Servers (F-P5c-96) |
| **Verbindungsprobe, Teil 2** (umgebaut) | `bash tools/proben/proben.sh verbindung` | **24 Erwartungen, 0 offen, 3 von 3 Läufen**, je in einer Runde: 11, 13, 16 Abweisungen an der Grenze, 1 bis 2 Gedrängel. Vorher gemessen: 0 (Vorlauf), 1, 12, 13 — der Nachweis hing am Zeitverhalten (F-P5c-98). **Gegenprobe:** `--frei 8` → drei Runden, **0** Abweisungen, **OFFEN**, 60 von 60 Paketen da |
| **Was die Proben im Reiter System hinterlassen** | Abfrage nach allen Einzelläufen | **74** Einträge (Mail-, Job- und Kreislaufproben erzeugen SMTP-Fehler mit Absicht), **0** mit `@`, **0** mit einer IPv4-Adresse |
| **Vorlauf des Prüfstands — nicht als Beleg verwendet** | `bash tools/pruefstand/pruefen.sh` auf frischer Anlage | **38 grün, 4 rot, 1 377 s.** Rot: Kopplung und Mail (lasen `error_log`, F-P5c-96), Verbindung (F-P5c-98), Textprobe (dreimal „Nutzerin" im Handbuch statt „NutzerIn"). Bilderlauf **845 s grün**, Bedienprobe grün, Stilvergleich grün, Schemaprobe grün |
| **Ingestprobe, Teil 11** (neu) | `bash tools/proben/proben.sh ingest` | **86 Erwartungen, 0 offen** (vorher 83). Ein Auslöser auf `missions` lässt das Einfügen scheitern: **HTTP 500 mit genau `{"error":"server","kennung":…}`**, dieselbe Kennung im Reiter System, der Wert ersetzt, der Einsatz nicht angelegt |
| **Riegel `behandler`** (neu) | `bash tools/quelltext/pruefen.sh behandler` | **145** PHP-Dateien, die drei Zeilen in `db.php` (58–60), **0 Befunde**. Selbstprobe **9 von 9** (entfernt, nur im Kommentar, doppelt, falscher Name, zweiter Ausnahme-Behandler anderswo, lokaler mit und ohne `restore`, Methode gleichen Namens). **Gegenprobe:** `set_exception_handler(…)` in `db.php` auskommentiert → **Rückgabewert 1**, zurück → 0 |
| Quelltext | `bash tools/quelltext/pruefen.sh --selbstprobe`, dann `alle` | **6 von 6** Selbstproben, **9 von 9** Prüfungen (Textprobe 0 neue Treffer bei 17 umgeschriebenen Sätzen) |
| Register | `php tools/zaehlung/zaehlen.php` | **40 Zeilen, 0 über der Decke**; **Z38 `error_log(` 75 → 2** (`protokoll_lib.php:216`, `systemmeldung_lib.php:155`), `decke_jetzt` und `decke_ziel` 2 |
| Schemaprobe | `bash tools/sandbox/plattform.sh alles` | **4 × 19 Prüfungen, 0 Fehlschläge** (MariaDB 10.11.14 und 10.6.28, MySQL 8.0.46 und 8.4.0) — nötig, weil AP3 `migration_lib.php` berührt |
| **Kollation der Kennungssuche** | eigene Abfrage auf den vier Fassungen, `JSON_UNQUOTE(JSON_EXTRACT(daten,'$.kennung')) = ?` | klein / groß: MariaDB 10.11 **0 / 1**, MariaDB 10.6 **1 / 1**, MySQL 8.0 **0 / 1**, MySQL 8.4.0 **0 / 1** — `utf8mb4_bin` außer auf 10.6 (`utf8mb3_general_ci`). Groß trifft überall (F-P5c-89) |
| Riegelprobe der Demo-Fixture | `php tools/referenzdatensatz/fixture/riegelprobe.php` | **10 von 10**; der gescheiterte Reset meldet **1** Störung in den Reiter System, die Probe nimmt sie heraus (F-P5c-93); Fixture SHA-256 gleich |
| Fehlerseite im Bild | eigener Lauf, Chromium, 1280 und 390 px | `konzept-p5c/ap3/fehlerseite-1280.png`, `…-390.png` — Gerüst der Störungsseiten, kein Überlauf |
| Rauchtest vor den Proben | CLI und `php -S` von Hand | Transaktion: Eintrag nach dem Zurückrollen geschrieben; `@file_get_contents()` → 0 Einträge; Speicherende bei 4 MB → Seite mit Kennung. Die acht Zeilen des Rauchtests sind danach gelöscht |

**Was die Umgebung gestört hat, und wie es zu erkennen war.** Nach einem
Neustart des Behälters liefen die Dienste nicht, und die Statusseite zeigte
die Zeile „E-Mail — Letzter Versand" rot: Der SMTPS-Nachbau war nicht
gestartet. Kein Fehler der Anwendung — nach `hochfahren.sh` grün. Und das
Modul `plattform` stand nach dem Neustart nicht; der Vorlauf des Prüfstands
meldete die Schemaprobe deshalb als **nicht gemessen** statt grün, wie es
sein soll.

## 1c. Messprotokoll AP4 (24.09.2026, Web 20.41.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Rollenprobe, erweitert** | `bash tools/proben/proben.sh rollen` | **279 Erwartungen, 0 offen** (vorher 87). Matrix **65 Zeilen × 4 Rollen = 260 Zellen** (vorher 28 × 3); dazu 3 für den Rollenwechsel und **16 Wirkungen des Supports** mit gültigem Token: Menü genau `admin_users.php` und `admin_protokoll.php`; in der Liste fehlt das Konto eines Admins (beim Admin nicht); drei Kacheln (beim Admin vier); Kontoseite wie im Bild, **Gegenprobe beim Admin: die Marken stehen da**; zwei Protokollreiter; Setz-Link **ohne** `pw_handling.php?token=` in der Antwort, Mail „NICHT zugestellt", Verweis an die Verwaltung, **Gegenprobe: dem Admin zeigt die Seite den Link**; Gerät 1 → 0 mit einem Eintrag, zweimal → kein zweiter; einschalten 403, bleibt 0; entkoppeln 403, bleibt; Konto löschen 403, bleibt; Bestätigung an ein unbestätigtes Konto → 1 Eintrag, kein Link; an ein aktives → abgewiesen, 0 Einträge. Aufgeräumt: 0 Konten `rollenprobe-*`, 0 Geräte danach |
| **Gegenprobe der Rollenprobe** | `device_toggle` in `SUPPORT_HANDLUNGEN` und `elseif (ist_support())` → `elseif (false)` im Setz-Link-Zweig, danach zurück | **4 rot** — die Zelle „Gerät an oder aus · support" (`durch` statt 403), „kein Link in der Antwort", „an die Verwaltung verwiesen", „wieder einschalten: 403" (HTTP 200, active 1). Zurück: 0 rot |
| **Vor `update.php`** (Abnahme) | ENUM auf drei Werte, Registereintrag `2026_09_24_rolle_support` gelöscht, `migration_tor_hash` und `migration_tor_offen` aus `app_state` gelöscht (das tut sonst der Deploy); dann eine Anmeldung ohne Sitzung und eine BetreiberIn-Sitzung | Erste angemeldete Anfrage schaltet die Wartung ein (`von: torwaechter`); `login.php` **200** mit „Wartungsmodus seit … — automatisch geschaltet" und Formular; `betrieb_updates.php` **200** und nennt die Migration; `admin_users.php` **503**; `betrieb_statistik.php` **200** mit der Zeile Support. `php server/update.php` → „Erfolgreich angewendet", ENUM `('user','admin','betreiberin','support')`, Register `applied`, Wartung **bleibt an**. Ohne das Verwerfen des Hashs schaltete der Torwächter nicht — seine Antwort gilt je Katalog-Hash (Absicht) |
| Protokollprobe | `bash tools/proben/proben.sh protokoll` | **36 / 0** (Reiter des Supports über die Rollenprobe) |
| Wartungsprobe | `bash tools/proben/proben.sh wartung` | **67 / 0** — Migrationsregister der Probe wieder hergestellt |
| Mailprobe | `bash tools/proben/proben.sh mail` | **51 / 0** — Abschnitt 1 rendert jeden Katalogeintrag, also auch `registrierung_erneut` |
| Schemaprobe | `bash tools/sandbox/plattform.sh schema` | **4 × 19 / 0** (MariaDB 10.11.14 und 10.6.28, MySQL 8.0.46 und 8.4.0) — die neue Migration gegen ein frisch angelegtes Schema |
| Migrationsregister | `bash tools/quelltext/pruefen.sh migrationsregister` | **0 Befunde**, 0 ungenutzte Ausnahmen (`schema.sql` trägt `2026_09_24_rolle_support` vorgemerkt) |
| **Stufenregel** (E-P5c-88) | `python3 tools/pruefstand/auswahl.py --selbstprobe`, dann `--stufe-ermitteln` | **35 Lagen, 0 Fehlschläge** (acht neu); gegen den Stand: `haupt` — „Nebenstufe 20.37.3 → 20.41.0; neu in `server/migration_lib.php`: `2026_09_24_rolle_support` (Regel migration)" |
| Register | `php tools/zaehlung/zaehlen.php` | **40 Zeilen, 0 über der Decke**; Z13 (Rollenvergleich von Hand) **0 / 0** mit `support` im Muster |
| Quelltext | `bash tools/quelltext/pruefen.sh alle` | **9 von 9**, Textprobe 0 Treffer außerhalb der Ausnahmen |
| **Unter PHP 8.3.33** (F-P5c-103, von Hand) | `hochfahren.sh --php 8.3`, dann die Rollenprobe; danach ENUM zurück, Registereintrag und Katalog-Hash weg, als BetreiberIn `betrieb_updates.php` mit `action=migrate` | Kopfzeile `X-Powered-By: PHP/8.3.33`; Rollenprobe **279 / 0**; vor dem Ausführen HTTP 200, Migration genannt, Wartung an (Torwächter); danach ENUM mit vier Werten, Register `applied`, **Wartung an**. Zurück: Behälter `nadoku-php83-lauf` entfernt, `PHP/8.4.19` |
| **Erster Prüfstand — nicht als Beleg verwendet** | `hochfahren.sh --neu`, `pruefen.sh` → Stufe `haupt`, 44 Proben | **42 grün, 2 rot, 2 461 s.** Rot: der Stilvergleich — **42 718** Elementmessungen, eine **ungeplante** Signatur `div.form-raster-einspaltig : grid-template-columns, max-width, width` (die neue Regel; `geplant.txt` um diese eine Zeile ergänzt, 55 statt 54) — und der Messstand (F-P5c-104). Bilderlauf **840 s, 520 Bilder** — nur Chromium (F-P5c-103). Rollen-, Schema-, Anteil- und Verbindungsprobe grün |
| **Messstand, ganze Kette** (F-P5c-104) | `cd tools/messstand && python3 messen.py --frisch` | **9 min 38 s**, Rückgabewert 0; Konto angelegt, 17 Dateien mit **5050** Einsätzen und 3 018 750 Spurpunkten eingespielt (354,5 s); Browser unter Drossel 6×: Anmelden 3,38 s, Tagesliste 2,08 s, Tagesansicht 1,71 s, Suche 4,9 s, Jahresübersicht 75,6 s, Nachbearbeitung 1,09 s, Backup 60,63 s — **0 Fehler**; Server: `edbak_build()` in Fenstern 3,98 s, Spitze 12 MB von 64 |
| **Bilderlauf in Firefox und WebKit** (F-P5c-103, von Hand) | frische Anlage, `node tools/screenshots/aufnehmen.mjs --stufe neben --motor firefox`, dann `--motor webkit` | **Firefox 142** (erster Lauf, frische Anlage): **520 Bilder aus 65 Seiten, 769 s** — Überlauf **0**, Knöpfe falscher Höhe **0**, Karten **167 von 167** im Gerüst, gleiche Bilder **0**, Etikett **0** Abweichungen, **1 Konsolenfehler**; sein Wortlaut ging verloren, weil der folgende Lauf den Ausgabeordner leerte. Nachgefahren: **1 Konsolenfehler** „downloadable font: download failed … `status=2152398850`" (`NS_BINDING_ABORTED` — Gecko bricht das Laden einer Schrift beim Seitenwechsel ab) auf `16-papierkorb` bei 360 px; diesen Lauf traf der Demo-Reset um 10:28:29 bei seinem ersten Aufruf, 64 Aufnahmen fielen mit Grund aus („Platzhalter nicht auflösbar"), 456 Bilder, sonst 0/0/0. **WebKit 26: 520 Bilder, 908 s** — Überlauf **0**, Knöpfe **0**, Karten **167 von 167**, gleiche Bilder **0**, Etikett **0**, **16 Konsolenfehler**, alle `api/csp_bericht.php` → 503 auf den zwei Wartungsseiten, ausgelöst vom Bildschirmfoto selbst (F-P5c-105). **Kein Befund an einer Seite von AP4** |
| Engines | eigener Start je Engine gegen `login.php` | Chromium 141, Firefox 142, WebKit 26 — alle drei laden die Seite; Voraussetzung für die Hauptstufe |

## 1d. Messprotokoll AP5 (24.09.2026, Web 20.42.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Zweitfaktorprobe** (neu) | `bash tools/proben/proben.sh zweitfaktor` | **44 / 0** (43 bis F-P5c-117). RFC 6238 Anhang B **6 / 6** (Server) und dieselben Werte aus dem PHP-Rechner der Werkzeuge; Wiederholung und Schreibweisen; über HTTP: Passwort → 303, API 401, falscher Code → „Der Code passt nicht.", richtiger → 302 `index.php`, derselbe noch einmal → abgewiesen, Wiederherstellungscode → angemeldet, derselbe noch einmal → abgewiesen, „Zurück zur Anmeldung" → `data-vergessen="1"`, fünf falsche Codes → Sperre; Einrichtungstor (Seiten 302, API 403, Einrichtung, zehn Codes, danach durch); Selbstlöschung erst mit dem Code zurückgenommen; der Schritt des Demo-Resets leert die Spalten, und der Reset ruft ihn genau einmal (Gegenprobe ohne Aufruf: rot); Bus-Faktor: Zählung am Bestand und **sechs Lagen** mit gesetzten Zahlen |
| **Bedienweg `zweitfaktor-einrichten`** | `node tools/bedienprobe/probe.mjs --nur zweitfaktor-einrichten` | **1 / 1** — QR gelesen (jsQR 1.4.0) **= angezeigte Adresse**, 10 Codes, „Weiter" gesperrt → mit Haken frei, danach `index.php` |
| **Bedienweg `einstellungen-profil-zweitfaktor`** (neu, F-P5c-114) | `--nur einstellungen-profil-zweitfaktor` | **1 / 1** — eingeschaltet, 10 Codes · falscher Code abgewiesen · derselbe Code wie beim Einschalten abgewiesen **im Fenster** (Abstand 0 Schritt) · der nächste → `index.php` · Vormerkfach **belegt → leer** |
| **Gegenproben der Bedienwege** | Wiederholungsschutz ab (alle drei Riegel, F-P5c-113), dann das Räumen des Vormerkfachs ab; danach zurück | **rot:** „derselbe Code **angenommen** (im Fenster, Abstand 1 Schritt)"; **rot:** „Vormerkfach belegt → **belegt**". Die erste Fassung der ersten Gegenprobe schaltete nur zwei Riegel ab und blieb grün (F-P5c-113). Zurück: grün |
| Rollenprobe | `bash tools/proben/proben.sh rollen` | **287 / 0** — Matrix **67 Zeilen × 4 Rollen**, neu die zwei Zeilen `totp_zuruecksetzen` (`{ziel}`: Admin und BetreiberIn durch; `{admin}`: nur BetreiberIn) |
| Wartungsprobe | `… wartung` | **67 / 0** — Pflichtkonten der Probe mit `totp_seit`; 12a in beiden Richtungen (halbe Sitzung abgelaufen → `data-vergessen="1"`, sonst `"0"`) |
| Protokollprobe | `… protokoll` | **36 / 0** |
| Ratenprobe | `… raten` | **50 / 0** — „Genau 7 Töpfe haben eine Leiter": blatt, ingest, ingest_ip, login, login_ip, salt, **totp** |
| Mailprobe | `… mail` | **51 / 0** — Abschnitt 1 rendert jeden Katalogeintrag, also auch `totp_zurueckgesetzt` |
| **Integritätswache** | `python3 tools/integritaetswache/wache.py --selbstprobe`, dann gegen die Sandbox | **43 / 0**; gegen die Sandbox „Kein Unterschied" — mit `huelle_srcs()` (F-P5c-110) und `BEDINGTE_FORMULARE` (F-P5c-111) |
| **Kettenaufrufe** | `tools/kettenaufrufe/` | **79 Aufrufe, 0 Befunde** — darunter `kreislauf.py --admin-totp` aus Stufe 2 |
| **Billige Riegel** (F-P5c-116) | `bash tools/quelltext/pruefen.sh alle`; `php tools/zaehlung/zaehlen.php` | zuerst **7 von 9** und **Z13 über der Decke**; nach der Berichtigung **9 von 9** (Textprobe 0 Treffer außerhalb der Ausnahmen, Vollständigkeit 0 Befunde) und **40 Zeilen, 0 über der Decke** |
| **Vor `update.php`** (Abnahme) | Tabelle `totp_codes` und die drei Spalten entfernt, Registereintrag `2026_09_24_zweitfaktor` und `migration_tor_hash` gelöscht; dann Anmeldung der BetreiberIn über `sitzung.py` | Anmeldung **ohne** Code-Schritt (die Spalten fehlen, `totp_an()` schweigt); die erste angemeldete Anfrage schaltet die Wartung ein; `login.php` danach mit „Wartungsmodus seit … — automatisch geschaltet"; `betrieb_updates.php` **200**, Migration genannt; `betrieb_status.php` **200**, „Verwaltungskonten — Ein handlungsfähiges Konto …", Plakette „nur 1" (ohne Spalten zählt jedes aktive Konto); `betrieb_statistik.php` **200**; `index.php`, `einstellungen.php`, `zweitfaktor.php` **503** (Wartung). `php server/update.php` → „Erfolgreich angewendet", drei Spalten und `totp_codes` da, **Wartung bleibt an**. Nach dem Ausschalten landet die BetreiberIn auf `zweitfaktor.php` — das Tor greift |
| **Unter PHP 8.3.33** (F-P5c-103, von Hand) | `hochfahren.sh --php 8.3`; dieselbe Rücknahme, dann `betrieb_updates.php` mit `action=migrate` | GET **200**, Migration genannt; POST → „2026_09_24_zweitfaktor … Erfolgreich", Spalten und Tabelle da, **Wartung an**. Danach Zweitfaktorprobe **43 / 0**, Rollenprobe **287 / 0**, Wartungsprobe **67 / 0**, Bedienwege **2 / 2**; im Protokoll des Behälters keine Zeile mit `Fatal`, `Warning`, `Deprecated` oder `Notice`. Zurück: Behälter entfernt, PHP des Containers |
| **Backlog Nr. 211** | `curl -sk …/api/day.php?day=2026-01-01` | **401**, `{"error":"session_ende","grund":"nicht_angemeldet",…}` |
| **Bilderlauf der vier Seiten in drei Motoren** | `aufnehmen.mjs --nur 01-anmeldung,01a-,30a-,30b- --stufe neben --motor …` | Vor der Berichtigung: Chromium `klein` (3 Breiten) Überlauf 1 (Codeblatt 360 px), Firefox und WebKit (8 Breiten) je Überlauf 1 (Codeblatt 768 px, +26) — F-P5c-112. **Danach Chromium, Firefox, WebKit je 32 Bilder: Überlauf 0, Konsolenfehler 0, Knöpfe 0, Karten 8 von 8, Etikett 0** |
| **Codeblatt als PDF** | Chromium, `page.pdf({format:'A4', preferCSSPageSize:true})` | **1 Seite** — nach beiden CSS-Berichtigungen |
| **Stilvergleich** | `bash tools/stilvergleich/gegen.sh` gegen `origin/main`, dazu eine eigene Auswertung gegen den Stand von AP4 | **44 954 Elementmessungen, 128 Signaturen, 74 davon neu.** Ausgewertet je Element, ob es in einem neuen Baustein liegt (`.blatt-druck`, `.zweitfaktor-einrichtung`, `.codeblock`, `#codeform`, `#k-zweitfaktor`, `.anmeldung-schritt`, `.qr`): Auf dem Markup der Seiten ändert sich **außerhalb** davon nur die Höhe von `html`, `body` und dem Hüllen-`div` und die Lage eines absolut gesetzten `span.nur-vorlesen` darunter; im Katalog nur die neuen Selektoren und dieselbe Lageverschiebung bei neun absolut gesetzten Elementen. **Kein bestehendes Element ändert seinen Stil.** `geplant.txt` neu geschrieben (128) |
| **Erster Prüfstand — nicht als Beleg verwendet** | `hochfahren.sh --neu`, `pruefen.sh` → Stufe `haupt` (Stufenregel `migration`), 45 Proben | **44 grün, 1 rot, 2 099 s.** Rot: die GPX-Probe, „204 von 204 ohne Gegenstück" — die Zweitfaktorprobe (Teil 6) hatte vorher den Demo-Reset ausgelöst (F-P5c-117). Grün darin u. a.: Bilderlauf **868 s** (Chromium, acht Breiten), Bedienprobe **54 von 54**, **298 s**, Stilvergleich gegen `geplant.txt`, Messstand **633 s**, Schemaprobe, beide Kreisläufe, Zweitfaktorprobe |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh` | *steht in der Commit-Nachricht von `P5c-AP5`* |

## 1e. Messprotokoll AP6 (24.09.2026, Web 20.46.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Ratenprobe, Abschnitt 11** (neu, über HTTP) | `bash tools/proben/proben.sh raten` | **63 / 0**, davon **13** in Abschnitt 11: 403 `token` ohne, mit falschem und ohne eingerichteten Token, Dauer **0,352 / 0,352 / 0,352 s**; 200 mit genau den acht Feldern (`ok,web_version,db,migration_ausstehend,jobs_alter_s,system_24h,protokoll_fehler,speicher_pct`), `web_version` 20.46.0, nur Zahlen und Wahrheitswerte; Migration ausstehend (Torstand gestellt) → **503**, `ok:false`; `speicher_pct` gestellt 12 / – / 47 → **47**, ohne Messung **null** bei 200, nach `speicher_messen()` wie die Balken der Karte „Speicher" (Backups 0, Gesamt ohne Bezug null) → Endpunkt **1**; 60 × 200, die 61. **429**; `health` ohne Leiter |
| **Erster Lauf der Ratenprobe — nicht als Beleg verwendet** | dasselbe | **61 / 2:** „Endpunkt aus" → **200**, und damit die Dauer 0,352 / 0,352 / **0,006 s**. Meine Probe wartete 1,2 s nach dem Schreiben von `config.php`; der OPcache prüft alle 2 s (F-P5c-123). Mit 3 s: grün |
| **Gegenprobe** | in `api/health.php` die Fassung 1 des Konzepts: kein Token eingerichtet → **404** `aus`; danach zurück | **rot, 2 Befunde:** „Endpunkt aus" HTTP **404**, Dauer 0,352 / 0,352 / **0,004 s** — genau der Unterschied, den E-P5c-52 ausschließt. Zurück: grün |
| Wartungsprobe | `… wartung` | **68 / 0** (vorher 67) — neu **Fall 5a**: `api/health.php` in der Wartung → **503 / 503** `maintenance` mit und ohne Token, Topf `health` **0** Zeilen |
| Rollenprobe | `… rollen` | **296 / 0** — der neue Endpunkt steht in keiner Rolle (ohne Sitzung, nur Token) |
| **Billige Riegel** | `bash tools/quelltext/pruefen.sh alle`; `php tools/zaehlung/zaehlen.php`; `python3 tools/quelltext/bestand.py`; `auswahl.py --abdeckung` | **12 von 12**; **40 Zeilen, 0 über der Decke**; Bestand **0**; Muster `health` löst Raten- und Wartungsprobe aus |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh` | *steht in der Commit-Nachricht von `P5c-AP6`* |

## 1f. Messprotokoll AP7 (24.09.2026, Web 20.47.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Vor und nach `update.php`** | Sandbox auf dem Stand von AP6 (frische Anlage, Web 20.46.0), dann der Code von AP7; `betrieb_statistik.php` je Reiter, Betrieb → Updates, `action=migrate` | vorher: drei Reiter **200** in je rund 0,08 s (ohne den Index), Wartungsbalken da, die Migration unter *Ausstehend*; `migrate` → `applied`, **Wartung an**. Beim ersten Lauf fehlte danach `idx_missions_deleted` (F-P5c-124); nach der Berichtigung zurückgesetzt und neu: **2 von 2** Indizes |
| **Zahlen gegen eine unabhängige Rechnung** | Seite geparst gegen SQL mit Fenstergrenzen aus PHP (`gmdate`, nicht `UTC_TIMESTAMP`), alle Konten außer Demo — Messstand (5 050), `umlauf-edbak` (Referenzbestand, 107, davon 5 im Papierkorb, 23 künftig), `umlauf-csv` (101) | **10 Vergleiche, 0 Abweichungen:** Einsätze je Fenster **10 · 56 · 230 · 2 265 · 4 744**, NutzerInnen mit Einsatz 1 · 1 · 1 · 4 · 4, Ø je NutzerIn (kaufmännisch, „1.186,0"), gesamt **5 255**, Herkunft 30 Tage 30 / 150 / 0 / 10 / 40 / 0, Aktiv, Angemeldet, Neu angelegt, „Ohne Gerät" **5**, Konten 5 |
| **Die Ausschlüsse als Differenz** | dieselbe Rechnung ohne je einen Ausschluss, Fenster 1 · 7 · 30 · 180 · 365 | mit Demo **… · 2 314 · 4 822** (Demo: 49 im 180-Tage-Fenster, 0 in 30 Tagen); mit Papierkorb **… · 2 266 · 4 749** (die 5); ohne Obergrenze **425 · 471 · 645 · 2 680 · 5 159** (415 künftige — in keinem Fenster, nur in „gesamt"); die **alte Zählung nach Diensttag** **425 · 471 · 655 · 2 734 · 5 159**: Der Unterschied kommt zum größten Teil aus der fehlenden Obergrenze (415), der Rest aus Diensttag gegen Beginn (im 30-Tage-Fenster 10) |
| **„Aktiv" nach R38, gestellt** | `umlauf-csv`: Anmeldung auf −400 Tage, dann ein echtes Gerät mit `last_seen` jetzt, dann −10 Tage; danach zurück | Aktiv / Angemeldet (24 h · 7 T · 30 T · 6 M): **3/3** → mit Gerät **4 / 3** → Gerät vor 10 Tagen **3·3·4·4 / 3**; „Ohne Gerät" 5 → **4**, Kachel Geräte 0 → **1**; zurück: wie vorher |
| **Messstand-Schritt `statistik`** (neu) | `python3 messen.py --schritte statistik` | Reiter **0,077 / 0,087 / 0,076 s** (Median aus 3, Ziel unter 1 s) bei **5 366** Einsätzen; `EXPLAIN` Herkunft `key=idx_missions_started`, 230 Zeilen; Fenster Tabellenscan, `possible_keys` mit dem Index, **4 832 von 5 366 (90 %)** im längsten Fenster. Die erste Fassung verlangte den Index auch dort und war rot (F-P5c-125) |
| **Gegenprobe Messstand** | Index `idx_missions_started` entfernt, Schritt gefahren, Index zurück | **rot:** Fenster „nicht in possible_keys", Herkunft „nicht in key" |
| **Überlauf in `.tabelle-scroll`** | eigenes Messskript über `tools/motor.mjs` (nicht im Repositorium): drei Reiter × 1200 / 1280 / 1366 / 1440 px × Chromium, Firefox, WebKit | **36 Messungen, 0 px** in der Tabelle und 0 px an der Seite; Spalten 535 : 357 (1200) bis 679 : 453 (1440) |
| **Gegenprobe Raster** | dieselbe Messung, `.form-raster-links-breit` im Browser entfernt (1 : 1), Reiter Einsätze | Überlauf **40 px** bei 1200, **20 px** bei 1240, 0 ab 1280 — die Regel ist nötig, das Bild sagte „bis 1300" |
| Geräteprobe | `php tools/proben/geraete/probe.php` | **66 / 0** — neu Teil 7: Schlüssel von `HERKUNFT_TEXTE` = `HERKUNFT_WERTE`, je `kurz` und `lang` |
| **Stilvergleich** | `gegen.sh` gegen `origin/main` | zuerst **38 ungeplant, 31 nicht gemessen** — 23 an den Druckblättern (F-P5c-126); Gegenproben: neue Regel mit alter Seite **1** ungeplant, sauberer AP6-Stand **0 / 0**; nach `--schreiben` **139 / 139**, 44 590 Elementmessungen |
| **Billige Riegel** | `pruefen.sh alle`, `zaehlen.php`, `bestand.py`, Migrationsregister | Textprobe zuerst **1 Treffer** („Garmin-Uhr" im Katalog) → Ausnahme `herkunft-texte-garmin` → 0; **12 von 12**; **40 / 0**; Bestand **0**; Migrationsregister **0** |
| **Bilderlauf** | `aufnehmen.mjs --stufe haupt` | **560 Einzelbilder, 70 Kontaktbögen**, Überlauf 0, Konsolenfehler 0, Knöpfe falscher Höhe 0, Karten 180 / 0 außerhalb, Etikett 560 Titel / 472 Kopfleisten 0 Abweichungen. Nach dem geschützten Leerzeichen die drei Seiten noch einmal: 24 Bilder, 0 / 0. Bei 360 px stand zuerst „100 |
| **Erster Prüfstand — nicht als Beleg verwendet** | `hochfahren.sh --neu`, `pruefen.sh` → Stufe `haupt` | **rot, 47 / 2, 2 353 s:** Bedienprobe **51 / 55** („Der Demo-Reset lief um 23:30:38 UTC mitten in diesem Lauf"), GPX-Probe „204 von 204 ohne Gegenstück". Grün darin u. a.: Messstand mit Schritt `statistik`, Bilderlauf **876 s**, Stilvergleich, Schemaprobe, Migrationsregister, beide Kreisläufe. Ursache: die Reihenfolge der Muster (F-P5c-127) |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh`, zweiter Lauf | *steht in der Commit-Nachricht von `P5c-AP7`* |

## 2. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-P5c-01 | **Staging ist rot** | nach dem Merge und dem Eintrag `app.umgebung` in die `config.php` von Staging (Rahmenplan 6): Staging aufrufen, anmelden, Betrieb → Status | Reiter im Browser „[Staging] …"; Kopfleiste rot, der aktive Punkt mit hellem Strich; Streifen „Staging — Testdaten, kein Echtbetrieb" über dem Inhalt und über der Anmeldung; Statuszeile „Umgebung" blau „Staging" | Kopfleiste blau (Eintrag fehlt oder OPcache — einige Sekunden warten); Statuszeile orange „Präfix ohne Etikett" (Eintrag fehlt, Präfix steht) oder „Farbe unbekannt" (Tippfehler bei `farbe`) | offen |
| P-P5c-02 | **Produktiv bleibt blau** | nach dem Tag: Produktiv aufrufen, Betrieb → Status | Kopfleiste dunkelblau, kein Streifen, kein „[…]" im Titel; Statuszeile blau „Produktiv" | irgendein Etikett auf Produktiv — dann steht `app.umgebung` in der falschen `config.php` | offen |
| P-P5c-03 | **Eine Rundmail kommt an** | auf Staging: Servereinstellungen → Ankündigung setzen (Ende morgen) → „Als Rundmail senden …" → Rückfrage lesen → senden; danach einige Seiten aufrufen (der Job läuft huckepack) und das Postfach eines eigenen Kontos ansehen | Die Rückfrage nennt die Zahl der erreichbaren Konten; Meldung „Rundmail an N Konten eingereiht"; im Postfach eine Mail „[Staging] Ankündigung — …" mit dem Text; Betrieb → Status, Karte E-Mail, zeigt die Zeilen als zugestellt; ein zweiter Versuch am selben Tag ist gesperrt | keine Mail nach einer Stunde mit Seitenaufrufen (Warteschlange ansehen: offen? unzustellbar?); zwei Mails; ein Betreff ohne „[Staging]" | offen — **Zeit bis zur Zustellung notieren** |
| P-P5c-04 | **Die Ankündigung auf dem Handy** | auf Staging am Handy: eine zweizeilige Ankündigung setzen, Startseite und Anmeldung ansehen, das × tippen | Das × steht oben rechts und bricht nicht in eine eigene Zeile; nach dem Tippen ist der Streifen fort; nach Ab- und Anmelden wieder da | das × unten links allein in einer Zeile; oder der Streifen bleibt nach dem Tippen | offen |
| P-P5c-05 | **Bis 10c ausgeliefert ist, entsteht kein Konto mit der Rolle admin** (E-P5c-31) | Verwaltung → NutzerInnen, Filter Rolle | 0 Konten mit der Rolle admin, bis der 10c-PR gemergt und ausgeliefert ist | ein Admin-Konto — es erreicht heute Komplett-Backup und Backup-Ziele (Nr. 286, behoben mit AP2) | offen, **laufend** — **auf Staging** darf nach dem Merge ein Admin-Konto zum Prüfen entstehen (P-P5c-06); dort ist die Behebung dann schon ausgeliefert |
| P-P5c-06 | **Der Admin sieht vier Reiter und kommt nicht an die Backups** (AP2) | auf Staging nach dem Merge: ein Konto mit der Rolle admin anlegen, damit anmelden; Verwaltung → Protokoll; dann `admin_komplettsicherung.php` und `admin_sicherungsziele.php` von Hand in die Adresszeile | Reiter Verwaltung, E-Mail, Jobs, Sicherung — **kein** Sicherheit, Ziele, System, Archiv; das Suchfeld sagt „Text oder Konto"; beide Backup-Seiten „Kein Zugriff"; in der Leiste unter Protokoll keine Unterpunkte | ein fünfter Reiter; eine Backup-Seite öffnet sich; `?r=sicherheit` in der Adresse zeigt Einträge statt „Kein Zugriff" | offen |
| P-P5c-07 | **Das Archiv entsteht, geht hinaus und lässt sich laden** (AP2) | auf Staging nach dem Merge: einige Seiten aufrufen (der Job läuft huckepack), dann Verwaltung → Protokoll → Archiv; nach dem nächsten Versand wieder; ein Archiv herunterladen und `sicherheit.jsonl` öffnen | Archive für jede Woche **seit dem ältesten Eintrag** (auf Staging seit P5b, also mehrere); Kennung wie auf dem Schlüsselblatt; nach dem Versand „auf dem Ziel"; im ZIP je Reiter eine `.jsonl` und `manifest.json`; in `sicherheit.jsonl` **keine** IP-Adresse und keine E-Mail-Adresse; im Reiter Verwaltung ein Eintrag „Archiv heruntergeladen". **Die Zeit notieren**, bis das erste Archiv da war | nach einem Tag mit Seitenaufrufen kein Archiv (Jobs → „Protokoll archivieren": Rückstand? Fehler?); „nur lokal" nach einem Versand; eine IP in `sicherheit.jsonl`; „anderer Schlüssel" an einem neuen Archiv | offen |
| P-P5c-08 | **Die Seite am Handy** (AP2) | auf Staging am Handy, als BetreiberIn: Verwaltung → Protokoll, Reiter „Archiv" (ganz rechts) antippen, dann „Verwaltung"; eine Zeile mit Winkel aufklappen | Die Reiterreihe rollt waagerecht, der aktive Reiter ist nach dem Laden im Bild, die Seite selbst rollt **nicht** waagerecht; die Plakette steht unter dem Text; aufgeklappt stehen die Angaben in fester Schrift | der aktive Reiter außerhalb des Bildes; die Seite lässt sich seitlich schieben; die Plakette neben einem fünfzeiligen Text | offen |
| P-P5c-09 | **Ein Komplett-Backup hinterlässt einen Eintrag und keine IP** (AP2) | auf Staging: Betrieb → Komplett-Backup → Jetzt sichern, dann „Herunterladen" (unverschlüsselt), die `.sql.gz` entpacken | im Kopf eine Zeile `-- OHNE ZEILEN: sicherheit_ereignisse, rate_limits …`; zu beiden Tabellen `CREATE TABLE`, aber kein `INSERT`; im Protokoll, Reiter Sicherung, zwei Einträge (erzeugt, heruntergeladen) | ein `INSERT` für `sicherheit_ereignisse` oder `rate_limits`; kein Eintrag im Reiter Sicherung | offen |
| P-P5c-10 | **Die Kennungssuche findet einen Eintrag** (AP3) | auf Staging nach dem Merge: Verwaltung → Protokoll → System; hat der Reiter einen Eintrag, dessen Winkel aufklappen und die Kennung abschreiben; dann in einem anderen Reiter die Kennung **klein geschrieben** ins Suchfeld | Die Seite springt nach System und zeigt genau diesen Eintrag | „Keine Einträge" — dann vergleicht Staging (MySQL 8.4) anders, als die Abfrage gemessen hat (F-P5c-89) | offen — **nur, wenn ein Eintrag da ist**; sonst bei P-P5c-12 nachholen |
| P-P5c-11 | **Die Fehlerseite, wie sie gebaut ist** (AP3, E-P5c-96) | die beiden Bilder in `docs/konzepte/konzept-p5c/ap3/` ansehen | Gerüst wie die Wartungsseite, rote Meldung mit der Kennung, darunter der Meldeweg und „Zur Startseite"; am Handy kein Überlauf | Wortlaut oder Aufbau nicht, was du willst — dann sag es; die Seite hat kein freigegebenes Bild (E-P5c-96) | offen — **Durchsicht** |
| P-P5c-12 | **Was im Reiter System steht** (AP3) | eine Woche nach dem Merge auf Staging (nach dem Tag auf Produktiv): Verwaltung → Protokoll → System, Zeitraum 7 Tage | Wenige Einträge; jeder mit Kennung, Datei und Zeile; **keine** IP-Adresse, keine E-Mail-Adresse außer als `[Adresse]` | viele gleiche Einträge (eine Stelle, die dauernd meldet — Befund); eine Adresse im Klartext in einer Meldung (die Bereinigung hat ein Muster nicht getroffen) | offen |
| P-P5c-13 | **Das Fehlerprotokoll des Webspace wird still** (AP3) | wo zugänglich: das PHP-Fehlerprotokoll beim Hoster vor und eine Woche nach dem Deploy vergleichen | Danach nur noch Zeilen mit `[KENNUNG]` vorn (der Rückfall) und die Meldungen, die PHP selbst schreibt | Zeilen ohne Kennung im alten Format („app_state: …", „Ratenschutz …") — dann ruft eine Stelle noch `error_log()` (Register Z38 hätte es melden müssen) | offen — **nur, wo zugänglich** |
| P-P5c-14 | **Die Kontaktadresse erscheint auf der Fehlerseite** (AP3) | Verwaltung → Installation: steht eine Kontaktadresse? | Wenn ja, nennt die Fehlerseite sie als Verweis; wenn nein, sagt sie „Nenne diese Kennung …" (beides örtlich gemessen) | — (Hinweis: Ohne Kontaktadresse weiß eine NutzerIn nicht, wohin mit der Kennung) | offen — **Entscheidung, ob eine eingetragen wird** |
| P-P5c-15 | **Ein Support-Konto meldet sich an** (AP4) | auf Staging nach dem Merge **und nach `update.php`**: als BetreiberIn ein Konto mit der Rolle Support anlegen (Verwaltung → NutzerInnen → Anlegen, Rolle „Support"), den Setz-Link annehmen, anmelden | Unter dem Zahnrad Einstellungen und **Verwaltung mit genau NutzerInnen und Protokoll**, kein Betrieb; das Profil sagt „Rolle: Support — du siehst unter Verwaltung die Konten der NutzerInnen und das Protokoll" | Anlegen scheitert mit einer Kennung im Reiter System (dann fehlt die Migration — solange sie aussteht, steht die Anlage aber ohnehin in Wartung); Anmeldung scheitert; ein dritter Menüpunkt unter Verwaltung | offen |
| P-P5c-16 | **Die Sicht des Supports wie im Bild** (AP4, M-P5c-02c) | mit dem Konto aus P-P5c-15, am Schreibtisch und am Handy: NutzerInnen, dann die Kontoseite einer NutzerIn mit Gerät | Liste: drei Kacheln (Konten, Konto-Backup überfällig, nie Konto-Backup), kein „Anlegen", keine Kästchen, kein Filter „Admins", **kein Konto mit Rechten**. Kontoseite **einspaltig**: Konto gesperrt ohne „Speichern", Status mit Hinweis, Mengen ohne Formular, Geräte mit „Deaktivieren"; Aktionen: „Setz-Link senden" (und „Bestätigung erneut senden" bei einer unbestätigten Registrierung) | zwei Spalten mit leerer rechter; ein grauer Knopf; ein Admin in der Liste; die Kontoseite eines Admins öffnet sich (Adresse von Hand: `admin_user.php?id=` eines Admins → muss „Kein Zugriff" sagen) | offen |
| P-P5c-17 | **`update.php` nach dem Deploy** (AP4) | nach dem Merge auf Staging: die Kette ansehen, dann Betrieb → Updates | Die Kette meldet die ausstehende Migration und lässt die Wartung an; Betrieb → Updates nennt „Vierte Rolle: Support"; nach „Ausstehende ausführen" steht sie unter Ausgeführt; **die Wartung bleibt an**, bis sie jemand ausschaltet | die Anwendung antwortet mit 500 statt Wartung; die Migration scheitert (Meldung auf der Seite, im Reiter System); der Wartungsmodus geht von selbst aus | offen — **nach dem Tag dasselbe auf Produktiv** |
| P-P5c-18 | **„Bestätigung erneut senden" kommt an** (AP4) | auf Staging: mit einer eigenen, noch nicht bestätigten Registrierung (Registrierung offen) → als Support oder Admin deren Kontoseite → Aktionen → „Bestätigung erneut senden" → Postfach | Eine Mail „[Staging] …" mit Link und dem Zeitpunkt, bis zu dem er gilt; der frühere Link geht nicht mehr; im Protokoll „Bestätigung erneut" ohne Link | keine Mail (Warteschlange ansehen); der alte Link geht noch; ein Link im Protokoll oder auf der Seite beim Support | offen |
| P-P5c-19 | **Der Support deaktiviert ein Gerät und kommt nicht weiter** (AP4) | mit dem Konto aus P-P5c-15 an einem Testkonto mit gekoppeltem Gerät: „Deaktivieren"; danach dieselbe Seite als Admin | Beim Support danach „deaktiviert" **ohne** Knopf in der Zeile; beim Admin „Aktivieren" und „Entkoppeln"; im Protokoll ein Eintrag „Gerät umgeschaltet" mit dem Support als Urheber; das Gerät bekommt bei der nächsten Übertragung eine Absage | beim Support ein „Aktivieren"; kein Protokolleintrag; das Gerät sendet weiter | offen |
| P-P5c-20 | **Das Prüfkonto auf Staging richtet den Zweitfaktor mit einer echten App ein** (AP5, zugleich die Zuarbeit `STAGING_TOTP`) | nach Merge und `update.php` auf Staging (Rahmenplan 6): mit `STAGING_KONTO` anmelden → Einrichtungstor → QR-Code mit der App **scannen** → sechsstelligen Code eingeben → „Einschalten" | Die App zeigt den Kurznamen der Anlage (Vorgabe „Gen-EM NAdoku") und die Adresse des Kontos; der Code wird angenommen; zehn Codes erscheinen, „Weiter" erst nach dem Haken; danach die Startseite | die App liest den Code nicht; der Code wird abgewiesen (Uhrzeit des Handys prüfen — der Server lässt ±30 s zu); das Tor erscheint nach „Weiter" wieder | offen |
| P-P5c-21 | **Abtippen statt Scannen** (AP5) | an einem eigenen Testkonto auf Staging: Einstellungen → Profil → Zweitfaktor → Einrichten; das Geheimnis **in Vierergruppen** von Hand in eine zweite App tippen (Art „zeitbasiert"); am Handy „In der Authenticator-App öffnen" antippen | Beide Wege ergeben denselben Code wie der Scan; der Knopf öffnet die App mit dem Eintrag | die App verlangt ein anderes Format; der Knopf tut nichts (dann kennt das Handy keine App für `otpauth://` — kein Fehler der Anlage) | offen |
| P-P5c-22 | **Das Codeblatt auf Papier, aus zwei Browsern** (AP5) | nach dem Einschalten: „Codeblatt drucken" in Chromium und in Firefox → Druckvorschau, A4 | **eine Seite**; Kästchen **vor** jedem Code; oben die Zeile „Staging …" (auf Produktiv nicht); Fuß mit Fassung und „Seite 1 von 1" | zwei Seiten; das Kästchen hinter dem Code; die Umgebungszeile fehlt auf Staging | offen |
| P-P5c-23 | **Anmelden mit einem Wiederherstellungscode** (AP5) | abmelden; Passwort; im Code-Schritt „Wiederherstellungscode verwenden" → einen Code vom Blatt | angemeldet; auf der Profilkarte „9 von 10"; unter Verwaltung → Protokoll, Reiter Verwaltung, ein Eintrag „Wiederherstellungscode" (orange); derselbe Code ein zweites Mal wird abgewiesen | der Code wird nicht angenommen (die Schreibweise ist egal — Leerzeichen, klein); kein Protokolleintrag; der Zähler bleibt bei 10 | offen |
| P-P5c-24 | **Zurücksetzen durch die Verwaltung** (AP5) | auf Staging als BetreiberIn: Verwaltung → NutzerInnen → Testkonto mit Zweitfaktor → Karte „Zweitfaktor" → „Zurücksetzen …" → Rückfrage bestätigen; Postfach des Testkontos | Meldung „zurückgesetzt"; eine Mail „[Staging] Zweitfaktor zurückgesetzt …"; im Protokoll „Zweitfaktor zurückgesetzt"; die nächste Anmeldung des Testkontos fragt keinen Code (NutzerIn) bzw. landet im Tor (Pflichtrolle). Die Karte fehlt am **eigenen** Konto und beim Demo-Konto | keine Mail; die Karte am eigenen Konto; ein Admin kann eine BetreiberIn zurücksetzen | offen |
| P-P5c-25 | **Station D grün mit `STAGING_TOTP`** (AP5) | nach P-P5c-20 und dem Eintrag des Secrets: den Stufe-2-Lauf neu starten | Kreisläufe `csv` und `edbak` grün; kein Fehler „STAGING_TOTP fehlt" | „Code-Schritt: zweimal abgewiesen" (Geheimnis falsch abgeschrieben — Leerzeichen sind erlaubt); „Einrichtungstor" (der Zweitfaktor des Prüfkontos wurde zurückgesetzt) | offen |
| P-P5c-26 | **Die Zeile „Verwaltungskonten"** (AP5) | Betrieb → Status auf Staging und nach dem Tag auf Produktiv | orange, solange weniger als zwei BetreiberInnen einen Zweitfaktor haben; der Satz passt zur Lage; blau „vertreten", sobald die zweite ihn eingerichtet hat | blau mit nur einer handlungsfähigen BetreiberIn; eine BetreiberIn ohne Zweitfaktor zählt mit | offen |
| P-P5c-27 | **Nach `update.php`: jede Pflichtrolle ins Tor** (AP5) | nach dem Tag auf Produktiv: `update.php`, Wartung aus, als BetreiberIn anmelden | das Einrichtungstor; nach der Einrichtung die Startseite. **Jede weitere BetreiberIn, jeder Admin und Support** landet bei der nächsten Anmeldung dort | eine Seite der Anwendung ohne Tor (dann fehlen die Spalten — ist `update.php` gelaufen?) | offen — **Produktiv** |
| P-P5c-28 | **Code-Schritt und Tor am Handy** (AP5) | auf Staging am Handy anmelden | Im Code-Schritt öffnet sich die Zifferntastatur, und das Handy bietet den Code aus der App an (`one-time-code`, wo unterstützt); im Tor steht der QR-Code über dem Text, kein Überlauf | Buchstabentastatur; eine waagerecht schiebbare Seite | offen |

## 3. Grenzen der benutzten Prüfmittel

**Aus AP7:**

- **Die Nachrechnung ist SQL gegen Seite, nicht zwei unabhängige Wahrheiten.**
  Die Fenstergrenzen rechnet sie in PHP statt mit `UTC_TIMESTAMP`, die
  Bedingungen (Demo, Papierkorb, Obergrenze) sind dieselben, weil sie die
  Entscheidung sind. Belegt ist, dass die Seite rechnet, was E-P5c-18 sagt —
  nicht, dass E-P5c-18 die richtige Zählung ist.
- **„Aktiv" ist an einem gestellten Gerät belegt.** Echte Geräte hat in der
  Sandbox nur das Demo-Konto, und das zählt nie; die Lage ist deshalb
  gestellt und danach zurückgelegt.
- **Die Überlaufmessung ist ein Skript im Arbeitsordner**, kein Werkzeug des
  Repositoriums; es misst `scrollWidth − clientWidth` an `.tabelle-scroll`
  und am Dokument. Der Bilderlauf misst nur die Seite, und 1200 / 1366 px
  kennt er nicht (F-P5c-41).
- **Die Gerätemodelle-Tabelle war leer** — außer dem Demo-Konto koppelt in
  der Sandbox kein echtes Gerät. Ihr Überlauf ist deshalb nicht gemessen;
  sie ist unverändert seit S8.

**Aus AP6:**

- **Der Health-Endpunkt ist mit `curl` gegen `php -S` gemessen**, nicht mit
  einem Monitoring und nicht hinter dem Webserver des Hosters. Ob dort ein
  vorgeschalteter Cache eine 503 zwischenspeichert oder den Parameter
  `token` in ein Zugriffsprotokoll schreibt, sieht die Probe nicht
  (P-P5c-29).
- **„Gleiche Dauer" ist die Spanne dreier Einzelmessungen** (fehlt,
  falsch, aus) gegen eine Schwelle — keine Statistik über viele Abrufe. Sie
  belegt, dass alle drei Wege durch `rate_gleiche_dauer()` gehen, nicht,
  dass ein Angreifer mit tausend Messungen nichts unterscheiden könnte.
- **`speicher_pct` ist gegen die Balken der Karte „Speicher“
  (`speicher_uebersicht()`) gehalten** — dieselbe Rechnung an anderer Stelle, nicht unabhängig. In
  der Sandbox ist kein Webspace eingetragen; „Gesamt" war deshalb `null`,
  und belegt sind nur Datenbank und Backups. Die Gegenprobe gegen das, was
  der Hoster anzeigt, ist P-P5c-30.
- **Überlast ist nicht eigens gefahren.** Das Tor in `db.php` antwortet
  für jeden Endpunkt gleich; die Verbindungsprobe misst es allgemein, nicht
  an `api/health.php`.

**Aus AP5:**

- **Die Code-Rechner rechnen mit der Uhr der Arbeitsumgebung** — derselben,
  die der Server hat. Ein Handy mit falsch gehender Uhr stellt keine Probe
  nach; das Fenster von ±30 s ist gelesen und an RFC-Vektoren gerechnet,
  nicht an einem driftenden Gerät gemessen (P-P5c-20).
- **jsQR liest ein Bildschirmfoto aus Chromium**, scharf und ohne Winkel.
  Eine Handykamera bei schlechtem Licht misst niemand; Fehlerstufe M und
  vier Module Ruhezone sind gelesen (`Design.md` 9.38).
- **Die Zweitfaktorprobe spricht HTTP mit einem eigenen Konto**, dessen
  Passwort-Token sie in PHP ableitet wie der Browser. Die Ableitung im
  Browser selbst (Web Crypto) fahren die beiden Bedienwege.
- **Die Auswertung „neu oder Bestand" des Stilvergleichs** hängt an einer
  Selektorliste, die ich dafür geschrieben habe; sie ist kein Werkzeug und
  steht nicht im Repositorium. Was sie gezeigt hat, steht in 1d.

**Aus AP4:**

- **Die Sicht ist Markup, nicht Bild.** „Kontoseite wie im Bild" sucht fünf
  Marken (`form-raster-einspaltig`, `karte-mengen`, das Feld der Grenzen,
  die Karten Konto-Backups und Löschen, „Speichern") und hält sie gegen die
  Seite des Admins. Ob die einspaltige Seite in acht Breiten gut aussieht,
  misst sie nicht (P-P5c-16).
- **Der Fehlfall des Setz-Links ist örtlich der einzige Fall.** In dieser
  Arbeitsumgebung geht die Mail an `@probe.invalid` nicht hinaus; die Probe
  prüft deshalb den Fehlfall mit Gegenprobe und würde, ginge die Mail
  hinaus, „Fehlfall nicht belegt" sagen statt grün.
- **„Vor `update.php`" ist nachgestellt**, mit verworfenem Katalog-Hash
  statt einem echten Deploy (0, P-P5c-17).
- **Der Bilderlauf in WebKit stört, was er misst** (F-P5c-105): Jedes Bild
  löst einen CSP-Bericht aus, weil Playwright ein Stylesheet einsetzt. Die
  16 Konsolenfehler auf den Wartungsseiten sind das; die Tabelle der
  CSP-Berichte der örtlichen Anlage ist nach einem solchen Lauf voll davon
  und sagt über die Seiten nichts.

**Aus AP3:**

- **Teil 7 läuft unter `php -S`, nicht unter dem PHP des Hosters.** Die
  Ausgabepufferung ist dort eine andere: `php -S` puffert nicht, ein Hoster
  oft 4 KB (`output_buffering`). Die Fehlerseite verwirft deshalb vorher
  jeden Puffer — gemessen mit `-d output_buffering=4096` (1b). Was ein
  Hoster sonst noch davorschaltet (eine eigene 500-Seite), misst keine
  Probe.
- **„Datenbank weg" ist eine getrennte Verbindung**, kein angehaltener
  Server (0).
- **Die Bereinigung ist eine Musterliste.** Werte in Anführungszeichen,
  E-Mail- und IPv4-Adressen trifft sie; eine Adresse ohne `@`, einen Namen
  ohne Anführungszeichen oder eine IPv6-Adresse nicht. Gemessen ist sie
  gegen neun Marken, die die Probe selbst setzt — nicht gegen jede Meldung,
  die PHP oder MySQL erzeugen können.
- **Die Proben hinterlassen Störungen im Reiter System** — die Mail-, Job-
  und Kreislaufproben erzeugen SMTP-Fehler mit Absicht (74 Einträge nach
  allen Einzelläufen). Bis AP3 standen dieselben Zeilen im Protokoll des
  PHP-Servers; es ist dieselbe Spur an anderem Ort, und sie verfällt nach
  30 Tagen. Nur Protokoll-, Kopplungs-, Mail- (Abschnitt 13) und
  Riegelprobe nehmen ihre eigenen Nachweise wieder heraus.
- **Der Riegel `behandler` sieht Zeilen, nicht Wirkung.** Dass die
  Behandler auch das Richtige tun, misst die Protokollprobe.

**Aus AP2:**

- **Die Rollenprobe belegt, dass eine Rolle eine Handlung erreicht, nicht,
  dass sie gelingt.** Eine POST-Zelle „durch" heißt: Die Seite antwortet mit
  der Token-Ablehnung statt mit dem Rollentor. Ob die Handlung mit gültigem
  Token das Richtige tut, messen die Proben der Sache (Komplett, Versand)
  und die Bedienwege.
- **Die Matrix sieht nur, was in ihr steht.** Eine neue Seite ohne Zeile in
  `Technik.md` 4.99p prüft niemand — dieselbe Grenze wie beim Register
  (`CLAUDE.md` 4).
- **Die Protokollprobe stellt die Zeit.** Sie verschiebt Marke und
  Einträge; die Grenzen um eine Sommerzeitumstellung sind gelesen
  (`DateTimeImmutable` in Ortszeit) und nicht gefahren.
- **Die Zeilenhöhe ist in Chromium gemessen**, in zwei Breiten.
  Firefox und WebKit fährt die Hauptstufe (AP4).

**Aus AP1:**

- **Die Mailprobe misst bis „250 angenommen".** Ob eine Mail ankommt, sagt
  nur ein Postfach (P-P5c-03).
- **Die Bedienwege laufen in Chromium, in einer Breite (1280 px).** Das ×
  am Handy (P-P5c-04) misst der Bilderlauf nur als Bild und Überlauf, nicht
  als Bedienung.
- **Der erste Bilderlauf mit Etikett lief zehnmal langsamer** (rund eine
  Minute je Seite statt 7 bis 20 s) und wurde abgebrochen; Einzelmessungen
  derselben Seiten mit und ohne Etikett und Ankündigung lagen danach bei
  12 bis 15 s, der zweite Lauf brauchte 13 min für 62 Seiten. Die Ursache
  des ersten ist **nicht geklärt** — sie lag nicht an der Ankündigung und
  nicht am Etikett (beides einzeln nachgemessen).
- **Das Wegklicken je Sitzung ist an einem Browser gemessen.** Dass zwei
  Geräte derselben Person je eine eigene Sitzung haben und die Ankündigung je
  Gerät geschlossen wird, folgt aus der Bauart (Sitzungsmarke), ist aber
  nicht nachgestellt.
