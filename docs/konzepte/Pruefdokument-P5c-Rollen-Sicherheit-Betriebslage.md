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
| **Staging in Rot, Produktiv in Blau** | Beides sind echte Anlagen (Station D und E). Örtlich ist das Etikett gestellt und gemessen (1), aber ob die `config.php` von Staging den Eintrag trägt, sieht nur, wer Staging aufruft. | nach dem Merge (Staging) und nach dem Tag (Produktiv): P-P5c-01, -02 |
| **Eine Rundmail in einem echten Postfach** | Die Arbeitsumgebung erreicht keinen Mailserver; die Mailprobe spricht mit einem SMTPS-Nachbau, der annimmt und wegwirft. Gemessen ist der Weg bis „250 angenommen", nicht die Zustellung. | P-P5c-03 |
| **Firefox und WebKit** | Bilderlauf und Bedienwege sind in Chromium gefahren (Stufe „neben"); die drei Engines fährt erst die Hauptstufe. | mit dem ersten Paket in Stufe „haupt" (AP4) |
| **Das Archiv in echter Zeit** (AP2) | Örtlich ist der Zeitraum gestellt: Die Protokollprobe setzt Marke und Einträge in die Vergangenheit und ruft den Job mit knappem Budget auf. Ob der Job auf einer Anlage **ohne Cron** in einer Woche drankommt und nach dem Merge die Wochen seit dem ältesten Eintrag nachholt, zeigt nur Staging. | P-P5c-07 |
| **Die Sicht des Admins im Bild** (AP2) | Der Bilderlauf kennt keine reine Admin-Rolle (F-P5c-41) — seine Seiten mit `rolle: admin` meldet er als BetreiberIn an. Die vier Reiter des Admins belegt die Rollenprobe **als Statuscode**, nicht als Bild. | P-P5c-06 |
| **Ein Archiv auf einem echten Ziel** (AP2) | Die Versandprobe (Teil 13) schickt Archive an einen SFTP-Nachbau und misst, dass die Aufbewahrungsregel sie nicht anfasst. Ein echter Hoster mit vsftpd oder OpenSSH ist `--echt` und in dieser Arbeitsumgebung nicht gefahren. | P-P5c-07 |
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

**Was die Umgebung gestört hat, und wie es zu erkennen war.** Nach einem
Neustart des Behälters liefen die Dienste nicht, und die Statusseite zeigte
die Zeile „E-Mail — Letzter Versand" rot: Der SMTPS-Nachbau war nicht
gestartet. Kein Fehler der Anwendung — nach `hochfahren.sh` grün. Und das
Modul `plattform` stand nach dem Neustart nicht; der Vorlauf des Prüfstands
meldete die Schemaprobe deshalb als **nicht gemessen** statt grün, wie es
sein soll.

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

## 3. Grenzen der benutzten Prüfmittel

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
