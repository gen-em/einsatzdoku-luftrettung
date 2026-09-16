# Prüfdokument P5a — Kette und Fundament

**Konzept:** `Konzept-P5a-Kette-und-Fundament.md` (15.09.2026, E-P5a-01 bis
-58, AP1 bis AP12) — **nach der Freigabe am 16.09.2026 gelöscht**; die Historie
behält es unter `bcbb04f`, die Zusammenfassung steht in `docs/Rahmenplan.md`
Abschnitt 8. **Dieses Dokument bleibt, bis seine Prüfliste abgehakt ist.** **Gemessen auf:** Zweig `claude/butte-umsetzen-5opi9u`.
**Stand dieses Dokuments:** 16.09.2026, **nach AP12 und dem Nachtrag Nr. 213**
(Web 20.4.0 bis **20.15.1**), Merge und Tag stehen aus. **33 Prüfpunkte** in
Abschnitt 3; zehn davon (P1–P8, P12, P33) betreffen die Auslieferungskette und
brauchen GitHub-Umgebungen, die es hier nicht gibt (N36, N38). **P33 ist der
einzige Punkt der Liste, bei dem ein Fehler das Zertifikat der Anlage kostet.**

Das Prüfprotokoll im Konzept beantwortet „ist es belegt?". Dieses Dokument
beantwortet „was muss **ich** noch tun?" (`CLAUDE.md` 7, K9).

---

## 0. Was nicht geprüft werden konnte und warum

**Diese Liste steht am Anfang, nicht in einer Fußnote.**

| # | Was | Warum nicht | Wie es doch geprüft wird |
|---|---|---|---|
| N1 | **Die Arbeitsläufe selbst** (`pruefung.yml`, `auslieferung.yml`, `integritaet.yml`) | Ein GitHub-Arbeitslauf läuft nur bei GitHub. Der Container hat keine Actions-Umgebung, und ein `act`-Nachbau misst etwas anderes als das Original — vor allem bei Umgebungen mit Pflichtfreigabe. | Geprüft ist bisher nur, dass die drei Dateien **gültiges YAML** sind und die erwarteten Auslöser und Jobs tragen (Zahlen unten). Der erste echte Lauf ist Prüfpunkt **P1** unten. |
| N2 | **Die Pflichtfreigabe der Umgebung `produktion`** | Sie ist eine Einstellung des GitHub-Kontos und braucht das echte Konto der Betreiberin. | Prüfpunkt **P3** — Probe-Tag, Freigabe von Hand. |
| N3 | **Das Backup-Tor gegen eine echte Installation** | Es verlangt einen Produktivserver mit Job-Token und ein 10-GB-Backup. | Die **Selbstprobe** misst die Logik an fünf Lagen ohne Netz (5/5 erfüllt); der echte Lauf ist Prüfpunkt **P2**. |
| N4 | **Stufe 2** (Kreisläufe, Bilderlauf, Messstand gegen Staging) | Staging existiert noch nicht (Zuarbeit, Rahmenplan Abschnitt 6). | Prüfpunkt **P4**. |
| N5 | **Uhr Stufe I im Arbeitslauf** | `CIQ_GERAETE_URL` ist in dieser Arbeitsumgebung gesetzt, als GitHub-Secret aber noch nicht hinterlegt (Zuarbeit). | Der Schritt ist so gebaut, dass er **ausdrücklich überspringt und es sagt** — nie ein stilles Grün. Prüfpunkt **P5**. |
| N6 | **`./gradlew build` im Arbeitslauf** | Im Container liegt das SDK unter `/opt/android-sdk`; der GitHub-Läufer bringt ein eigenes mit. Ob die Pfade dort zusammenpassen, zeigt erst der Lauf. | Prüfpunkt **P1**. |
| N7 | **Die Weiche auf einem echten PHP 8.0/8.1** | Der Container hat PHP 8.4, und eine zweite Fassung daneben zu bauen misst etwas anderes als ein Hoster-PHP. | Nachgestellt: Die Untergrenze wurde in einer **Kopie** von `server/` auf 99.0 gesetzt und die Seite abgerufen — **HTTP 500, Titel „PHP zu alt", Text lesbar, 923 Byte**. Dazu zählt `tools/installweiche/` nach, dass die Datei PHP-7-lesbar geblieben ist. Prüfpunkt **P9**. |
| N9 | **Ob eine Kartenkachel wirklich ankommt** | Der Wegwerf-Container hat keine Egress-Freigabe zu den vier Kachelservern; die Anfragen enden in `net::ERR_ABORTED`. Ein fehlendes Bild sagt dann nichts über die Richtlinie. | Gemessen wird stattdessen, was die Richtlinie **tut**: Unter der scharfen CSP erzeugt jeder der vier Hosts **keine** Konsolenmeldung „Refused to load the image", ein nicht gelisteter Host (`kachel.invalid`) **eine**. Ohne diese Gegenprobe belegten die vier nichts. Prüfpunkt **P13**. |
| N10 | **HSTS und der HTTPS-Zwang im Echtbetrieb** | Die lokale Installation liegt hinter einem TLS-Weiterleiter (`socat`), der unverschlüsselt an PHP weiterreicht. `$_SERVER['HTTPS']` ist damit leer — richtigerweise setzt die Anwendung dort **kein** HSTS und leitet nicht um. | Die Logik ist einzeln geprüft (`kopf_hsts_tage()`, Stufen 0/1/7/365, Ablehnung anderer Werte). Der Echtlauf ist Prüfpunkt **P14**. |
| N11 | **Der Weg über die Reporting-API (`report-to`)** | Aus demselben Grund: `kopf_melde_url()` liefert ohne HTTPS bewusst nichts, also steht `report-to` lokal gar nicht in der Richtlinie. Gemessen ist deshalb nur der Weg über `report-uri` — der, den heute ohnehin jeder Browser nimmt. | Prüfpunkt **P15** auf einer echten HTTPS-Installation. Dass der Endpunkt **beide** Formate versteht, ist einzeln geprüft: `report-uri`-Rumpf (`{"csp-report":{…}}`) und Reporting-API-Liste (`[{"type":"csp-violation",…}]`) — beide **204**, beide als Zeile gespeichert, und in beiden Fällen ist der **Abfrageteil der Adresse weg** (`suche.php?q=geheim` → `suche.php`). |
| N12 | **Die zwei Wochen Report-Only** | Sie sind Betrieb, keine Abnahme — und sie messen genau das, was ein Prüflauf nicht sieht: was **Nutzerinnen** auf Wegen tun, die der Bilderlauf nicht geht. | Prüfpunkt **P16**; dort steht auch, woran man erkennt, dass man zu früh scharf geschaltet hat. |
| N13 | **Die Anwendung hinter einem echten Reverse Proxy** | Der Container hat keinen. | `netz_in_bereich()` ist gegen **18 Fälle** geprüft, alle richtig: IPv4 und IPv6, Einzeladresse und CIDR, die Ränder eines /24, `0.0.0.0/0`, ein IPv4 gegen einen IPv6-Bereich, eine unlesbare Adresse und zwei unmögliche Präfixlängen (`/33`, `/-1`) — die letzten vier müssen **false** ergeben und tun es. Der Echtlauf ist Prüfpunkt **P17**. |
| N19 | **Ein echter Angriff** | 200 Fehlversuche in einer Schleife sind keine 200 Anfragen aus 50 Netzen. Der Container hat keine Freigabe nach draußen und keinen zweiten Rechner. | Gemessen ist, was die Bibliothek bei gegebenen Zählerständen TUT. Ob 8 s Verlangsamung einen echten Angreifer aufhalten, sagt nur ein echter Angriff — die Rechnung dazu (14 gleichzeitig Wartende bei 1600/15 min) steht im Kopf von `ratelimit_lib.php` und ist der Grund, aus dem Gesperrte nicht verlangsamt werden. Prüfpunkt **P22**. |
| N20 | **Gleichzeitigkeit in der Leiter** | Zwei Fehlversuche in derselben Millisekunde sind nicht nachgestellt. | Die Leiter ist deshalb als **ein** Statement gebaut (lesen-rechnen-schreiben würde bei zwei gleichzeitigen Klopfern dieselbe Stufe zweimal schreiben — die Leiter bliebe stehen, wo zwei Leute gleichzeitig klopfen, also genau im Angriffsfall). Belegt ist das hier nicht. |
| N21 | **Die 24 Stunden in Echtzeit** | Die Probe datiert `stufe_bis` zurück. | Gemessen ist damit die REGEL, nicht die Uhr. Dass `stufe_bis` bei jedem Fehlversuch richtig fortgeschrieben wird, ist einzeln geprüft (Abschnitt 4 der Ratenprobe, beide Richtungen). |
| N18 | **`api/adminbackup_freigabe.php` mit einem echten freigegebenen Backup** | Dafür müsste eine Administration ein Konto-Backup freigeben und eine zweite Person es abholen; der Prüfstand hat keine Freigabe liegen. | Der Fehlerzweig läuft über `json_out()` und ist damit mitgeprüft; der Rohausgabe-Zweig (`json_roh_out($klar)`) ist **gelesen, nicht gelaufen**. Prüfpunkt **P21**. |
| N14 | **Ob eine Mail ANKOMMT** | Die Gegenstelle von `tools/mailprobe/` nimmt an und wirft weg; der Container hat keinen Mailserver und keine Freigabe nach draußen. | Gemessen ist der Weg BIS zum „250 angenommen" — Katalog, Rahmen, Warteschlange, Leiter, Frist, Endzustände (41 Prüfungen, 0 Befunde). Ob ein Empfänger die Nachricht im Postfach findet, sagt nur ein Postfach: Prüfpunkt **P18**. |
| N15 | **Das Aussehen einer Mail in einem Mailprogramm** | Dasselbe. Der Rahmen ist auf seine **Bestandteile** geprüft (Anrede, Kern, Kontaktzeile, Grußformel mit `instanz_name()`), nicht auf seine Wirkung. | Prüfpunkt **P18** — eine echte Einladung und eine echte Reset-Mail ansehen. |
| N16 | **Eine hängende Namensauflösung** | `smtp_send()` rechnet mit einer Frist ab dem ersten Byte; die DNS-Auflösung liegt **davor** und lässt sich in PHP nicht begrenzen. Das steht im Kopf der Datei ausgeschrieben. | Nicht nachstellbar. Was gemessen ist: ein Server, der **antwortet und dann schweigt** (5,01 s bei 5 s Budget), und einer mit **12 Fortsetzungszeilen je 1 s** (5,00 s; ohne Frist über 13 s). |
| N17 | **Zwei Job-Läufe, die dieselbe Warteschlangenzeile gleichzeitig greifen** | Nicht nachgestellt. | Die Sperre `laeuft_seit` in `jobs` verhindert zwei gleichzeitige Läufe desselben Jobs (jobprobe Teil 5); zwei **verschiedene** Auslöser am selben Job sind damit abgedeckt, ein manueller Direktaufruf von `mail_zeile_versuchen()` nicht. |
| N22 | **Der vollständige Einspiellauf des Referenzdatensatzes** (`tools/referenzdatensatz/einspielen/`) | Der Generator lief (21 Dienste, 612 Ingest-Anfragen, 64 478 Punkte). Die Stufe `geraet` bricht ab: Sie koppelt über die **Weboberfläche** und braucht eine angemeldete Sitzung des Demo-Kontos; dessen Einladungslink war in diesem Container nicht mehr zu haben (`Konto demo@gen-em.org besteht bereits`), und ohne ihn wirkt das Einlösen des Kopplungscodes nicht (`409 nicht_beansprucht`). | Gemessen wurde **der erzeugte Sendeplan selbst** — dieselben 612 Anfragen, dieselben Körper, über echtes HTTP, mit per SQL angelegten Geräten: **0 Fehlversuche**, Median 14,43 ms ohne und 15,14 ms mit Bremse (je zwei Läufe). Das prüft `ingest.php`, nicht die Geräteverwaltung — und das ist die Frage von AP7. Der Kopplungsweg und die beiden Kreisläufe gehören zu **AP12**. |
| N23 | **Ob `Retry-After` einen Client erreicht** | Kein Client dieses Projekts liest die Kopfzeile, und die Garmin-Uhr **kann** es nicht: Der Rückruf von Connect IQ bekommt `(code, data)` und keine Kopfzeilen. | Gemessen ist, dass die Zeile **dasteht und den richtigen Wert trägt** (`Retry-After: 900`, Ingestprobe Teil 10). Dass beide Clients die `429` richtig behandeln, ist am **Quelltext** belegt (`Uploader.mc` fällt in „später erneut", `Sendeantwort.lese()` in `SpaeterErneut` bei `code != 200`, und `Sender.sendeAlles()` bricht den Lauf ab) — **nicht** an einem laufenden Gerät. Prüfpunkt **P24**. |
| N24 | **Die Mengenbremse unter echtem Mobilfunk-NAT** | Der Container hat eine Adresse. Ob hinter einem Anbieter-NAT Geräte zusammenfallen, sagt nur der Betrieb. | Die Bauart nimmt das Risiko heraus: In den Adresstopf zählen **ausschließlich unbekannte** Kennungen, und ein gekoppeltes Gerät sendet nie eine unbekannte — gemessen als eigene Erwartung („Der Adresstopf ist leer — bekannte Kennungen zählen dort nicht"). Prüfpunkt **P24**. |
| N25 | **Die Karte „Löschungen auf Sicherungszielen“** (E-P5a-08 nennt sechs Karten, gebaut sind fünf) | Es gibt dafür heute weder Tabelle noch Schreibweg noch `app_state`-Schlüssel — die Löschregel je Ziel entsteht erst in **AP10** (E-P5a-03). An fünf Stellen nachgesehen: `schema.sql` kennt keine Löschspalte in `backup_targets`, `Zielweg::loeschen()` hat genau einen Aufrufer (die Probedatei), `sz_loeschen()` löscht nur den DB-Eintrag, kein `app_state`-Schlüssel, kein Jobschritt. | **Erledigt in AP10 (16.09.2026).** Mit `sicherungsziel_dateien` gibt es Tabelle und Schreibweg; die Karte steht als sechste auf der Sicherheitsseite und zeigt die Löschungen der letzten 30 Tage mit Ziel, Datei, Größe und Grund. Gemessen im Browser mit drei Zeilen. |
| N26 | **Ob ein Ereignis nach 30 Tagen wirklich verschwindet** — in Echtzeit | Der Aufräumjob läuft höchstens einmal je Kalendertag; 30 Tage lassen sich nicht abwarten. | Gemessen ist die **Regel**, nicht die Uhr: Das `DELETE` steht mit `INTERVAL 30 DAY` in `job_aufraeumen()` (Schritte `Sperrereignisse` und, neu, `Geraetevermerke`), und die Lesefunktion `sicherheit_ereignisse()` blickt auf **dieselbe** Frist zurück — eine Seite, die weiter zurückblickt als der Job aufhebt, zeigte eine Lücke, die wie ein ruhiger Monat aussieht. Der Echtlauf ist Prüfpunkt **P25**. |
| N27 | **`post_max_size` der Zielanlage** (Backlog Nr. 37, eine der drei Messungen aus AP9) | Die Zahl gehört der Anlage, nicht dieser Maschine — und Staging stand am 16.09.2026 noch nicht (Zuarbeit, Rahmenplan 6a). Lokal kamen 32 MB durch, obwohl `post_max_size` auf 8M steht: Der eingebaute PHP-Server verhält sich bei `Content-Type: application/json` anders als ein Apache. Die lokale Zahl ist damit **keine** Auskunft. | **Es braucht keine Messung mehr, nur einen Seitenaufruf:** Die Plattformkarte auf Betrieb → Status nennt `post_max_size` und `upload_max_filesize` mit Soll- und Ist-Wert (seit AP2). Prüfpunkt **P29**. |
| N28 | **Die Fehlernummern 1040 und 1203** | 1040 (`max_connections` des ganzen Servers) lässt sich auf einer Maschine, auf der noch etwas anderes läuft, nicht gefahrlos herstellen. Die Systemvariable hinter 1203 lässt sich in MariaDB **nicht zur Laufzeit setzen**, wenn der Server mit `--max-user-connections=0` gestartet ist (Fehler 1290, gemessen). | Die Verbindungsprobe stellt **1226** her — die GRANT-Grenze am Datenbankkonto, also den Fall, den ein Hoster setzt. Für 1040 und 1226 ist zusätzlich die **Ausnahme selbst** untersucht worden (16.09.2026): `getCode()` trägt die Treibernummer als Zahl, `errorInfo[1]` ist gesetzt, die Meldung trägt `[1040]` bzw. `[1226]` — alle drei Wege in `ueberlast_erkannt()` greifen. Für 1203 ist es die Liste `UEBERLAST_CODES`, gelesen, nicht gelaufen. Prüfpunkt **P27**. |
| N29 | **Der Zweig „Zähler nicht schreibbar" über HTTP** | Der Prüfserver läuft als `root`, und für `root` ist jedes Verzeichnis schreibbar — `chmod a-w server/` ändert daran nichts. | Gemessen am **Funktionsaufruf** mit einem unprivilegierten Benutzer (`setpriv --reuid=65534 … php -r 'ueberlast_stand()'`): **`schreibbar=false`**, `gesamt=13`. Die Statuszeile wertet genau dieses Feld zuerst aus. Prüfpunkt **P27** (zweiter Spiegelstrich). |
| N30 | **Die Verbindungsgrenze unter Z2-Last und hinter PHP-FPM** | „Gegen Z2-Last" (500 Konten à 600 Einsätze) sind 300 000 Einsätze und ein Tag Rechenzeit. Und gemessen ist der **eingebaute** PHP-Server; ein Apache mit PHP-FPM hält eigene Prozessgrenzen, die möglicherweise **vor** der Datenbankgrenze liegen — dann kommt gar keine Anfrage bis zu `db()`. | Gemessen ist das **Verhalten an der Grenze**, nicht das Verhalten unter Bestandsgröße: 20 gleichzeitige Uploads bei 8 Arbeitern und 2 freien Plätzen, **0 Antworten außerhalb von 200 und 503**, nach Wiederholung 20/20 Einsätze und 400/400 Punkte in der Datenbank. Prüfpunkt **P27**. |
| N31 | **Die Löschregel gegen ein echtes auswärtiges Ziel** | Der Container kommt nur auf Port 443 hinaus; 21, 22 und 990 laufen ins Leere (nachgemessen mit `github.com:22`). Gemessen wird gegen die Nachbauten pyftpdlib/paramiko auf Loopback. | `tools/versandprobe/` Teil 12 misst die **Regel** vollständig — 5 fremde Dateien, 5 eigene, N = 2, 3 Löschungen, 5 von 5 fremden bleiben. Was der Nachbau nicht hat: eine langsame oder abreißende Leitung während des Löschens, und ein Ziel, auf dem jemand anderes gleichzeitig arbeitet. Prüfpunkt **P30**. |
| N32 | **Ein Altbestand, der hier schon weggeräumt ist** | Der Versand trägt eine Datei ins Protokoll ein, wenn er sie sendet **oder** sie drüben schon mit gleichem Namen und gleicher Größe vorfindet. Sicherungen, die drüben liegen und hier nicht mehr, kann er nicht belegen — und rührt sie deshalb nie an. Nachstellen ließe sich das, prüfen ließe sich daran aber nur, dass nichts geschieht. | Gemessen ist die **sichere Richtung**: In Teil 12 bleiben genau die Dateien liegen, die das Protokoll nicht kennt (fünf fremde, darunter eine mit gültigem Namensmuster). Die Folge steht im Handbuch und in `docs/Technik.md` 4.97c. |
| N33 | **Die zwei roten Erwartungen der Wiederherstellungsprobe** (Teil 10) | Sie sind **nicht** von AP10 verursacht: am unveränderten Stand ebenso rot (nachgemessen 16.09.2026, `git stash`). Der Prüffall gibt dem Sammelvorgang „Alle sichern" ein enges Zeitbudget und erwartet, dass danach etwas offen bleibt; auf einer Installation mit zwei fast leeren Konten passen beide hinein. | Aufgenommen als **Backlog Nr. 212**. Bis dahin sind es zwei rote Zeilen, die als solche benannt sind — und das ist der Punkt: Eine unerklärte rote Zeile gewöhnt jeden daran, rote Zeilen zu übersehen. |
| N34 | **Die 325 ausgelieferten Teilenummern gegen echte Geräte** | Die Geräteprobe setzt eine **eigene, kleine** Modelltabelle (fünf Geräte A bis E) und prüft damit das Verhalten des Nachlösens, nicht den Inhalt der ausgelieferten Tabelle. Ob die 325 Einträge in `GERAETE_MODELLE` **richtig** sind, sagt keine Probe — nur ein Gerät, das sich meldet. | Gemessen ist die **Mechanik**: dass eine erweiterte Tabelle genau die Zeilen der neuen Teilenummer nachzieht (1 von 5) und alle anderen unberührt lässt, und dass der Fingerabdruck sich mit dem Inhalt ändert. Ein falscher Eintrag in der Tabelle führt zu einem falschen Modellnamen an genau den Zeilen dieser Teilenummer — sichtbar in der Geräteliste, korrigierbar durch einen Tabelleneintrag und den nächsten Lauf. Prüfpunkt **P32**. |
| N35 | **Der Nachlöse-Job über einen großen Altbestand** | Hier stehen fünf Gerätezeilen mit Rohangabe. Ob das Zeitbudget (3 s huckepack, 20 s im eigenen Lauf) über Tausende reicht und wie viele Läufe es dann braucht, sagt nur eine Installation mit Bestand. | Gemessen ist die **Fortsetzung**: mit Blockgröße 1 meldet der Lauf `geprueft 1`, `fertig false`, die Marke wandert, und der **Hash wird erst am Ende geschrieben** — ein abgebrochener Lauf gilt also nicht als erledigt. Damit ist ein Bestand jeder Größe in endlich vielen Läufen durch; offen ist nur, in wie vielen. Prüfpunkt **P32**. |
| N36 | **Die Auslieferungskette selbst — sie ist gebaut, aber nie gelaufen** | Sie braucht GitHub-Umgebungen, Geheimnisse, eine Pflichtfreigabe, einen Zweigschutz und eine Staging-Installation. Nichts davon gibt es in einem Wegwerf-Container, und die Zuarbeiten dazu standen am 16.09.2026 noch aus (Rahmenplan 6a). **Die ganze Phase hat damit ihren zentralen Gegenstand nicht im Lauf gesehen.** | Gemessen ist, was sich ohne GitHub messen lässt: die drei Arbeitsläufe sind **gültiges YAML**, das Backup-Tor entscheidet in `tools/kette/tor.py --selbstprobe` **5 von 5** Lagen richtig, und das Migrationsregister läuft ohne Installation. Was nur der Ernstfall zeigt, steht als Prüfpunkte **P1 bis P8** und **P12** — neun der 32. Wer diese Phase für abgenommen hält, weil die Proben grün sind, verwechselt „gebaut" mit „läuft". |
| N37 | **Der Merge nach `main` und der Tag** | Beide brauchen die ausdrückliche Freigabe (`CLAUDE.md` 3 und 8, K7). Ein Push auf `main` löst seit Web 20.4.0 den **Staging**-Deploy aus, ein Tag `web-v20.15.0` die **Produktion** — und zwar erst nach Pflichtfreigabe und Backup-Tor. | Beides ist bewusst **nicht** getan. Die Phase liegt vollständig auf `claude/butte-umsetzen-5opi9u`. Was beim ersten Tag passieren **soll**, steht in P2, P3 und P8; was schiefgehen kann, ebenso. |
| N38 | **Die Punktdatei-Sperre und `state-name`** (Nr. 213, Web 20.15.1) | Drei Dinge gehen von hier aus nicht: Der Prüfserver ist der eingebaute PHP-Server, der **`.htaccess` gar nicht liest**; Port 21 und 990 verlassen den Container nicht, also ist `../` im FTP-Käfig nicht zu messen; und `nadoku.gen-em.org` ist vom Ausgangs-Gateway dieser Umgebung gesperrt (403 auf CONNECT, gemessen). Die Staging-Abfrage gab **404** — aber Staging ist leer, dort gibt auch `login.php` 404. | Gemessen ist der **Auslöser**, nicht die Abhilfe: Die Aktion legt die Datei laut ihrem eigenen README nach `server-dir` (Vorgabe `.ftp-deploy-sync-state.json`), ihr Inhalt steht in den Typdefinitionen der Bibliothek (`{type, name, size, hash}` je Datei), und `server/.htaccess` hatte **keine** Regel, die auf Punktdateien passt — alle 81 Zeilen gelesen. Die Abhilfe prüft **Stufe 2 der Kette** beim ersten Lauf gegen Staging, in beide Richtungen. Prüfpunkt **P33**. |
| N8 | **Die Kontingent-Warnmail auf einem echten Mailserver** | Der Container hat keinen. | Die Logik ist mit abgesenkten Schwellen (50/53 %) durchgespielt: Beide Schwellen schlagen an, der Versand scheitert erwartungsgemäß und wird **nicht** als gemeldet vermerkt — also am nächsten Tag erneut versucht. Prüfpunkt **P10**. |

---

## 1. Was maschinell geprüft wurde — mit Mittel und Zahl

Alles unten auf dem Stand nach AP1 (Web 20.4.0), im Wegwerf-Container
(PHP 8.4.19, Python 3.11.15, Node 22.22.2, MariaDB 10.11.14).

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| PHP-Syntax | `php -l` über `server/` und `tools/` | **0 Fehler** |
| YAML der drei Arbeitsläufe | `yaml.safe_load` | **3 von 3 gültig**; `pruefung.yml` → Auslöser `push`, `pull_request`, `workflow_dispatch`, Job `pruefung`; `auslieferung.yml` → `push`, `workflow_dispatch`, Jobs `staging`, `stufe2`, `produktion`; `integritaet.yml` → `schedule`, `workflow_run`, `workflow_dispatch`, Job `wache` |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **46 Kennungen im Katalog, 46 in der Vorabliste, 30 Tabellen, 180 Spalten, 29 Löschungen, 0 Befunde, 4 erklärte Ausnahmen, 0 ungenutzte** |
| Migrationsregister, Selbstprobe | `… --selbstprobe` | **4 von 4** eingebauten Schäden gefunden (Prüfungen 1, 2, 4, 5) |
| Backup-Tor | `python3 tools/kette/tor.py --selbstprobe` | **5 von 5** Lagen erfüllt |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen (in 0 Zeilen), 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 96 Regeln, 96 gegriffen |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py` | **340 Befunde** — und zwar **unverändert**: derselbe Lauf auf `origin/main` (`7f334cb`) in einem zweiten Arbeitsbaum meldet ebenfalls **340**. AP1 fasst `style.css` nicht an. |
| Vollständigkeit **nach AP4** | dasselbe | **365 Befunde** gegen **351** auf dem Stand vor AP4 (gemessen im selben Container per `git stash`). Die Bewegung ist vollständig erklärt: `style=`-Attribute **13 → 10** (die drei statischen sind aufgelöst), Unicode-Symbole **280 → 297** — **+17, alle in Kommentaren** neuer Dateien, je Datei nachgezählt. Sichtbares Markup hat kein Symbol dazubekommen |
| Backlog-Nummern | `grep -oE '^[0-9]+\.' docs/Backlog.md \| tr -d '.' \| sort -n \| uniq -d` | **leer** |
| Plattformprofil, Installation | `plattform_pruefen(db())` gegen die lokale Installation | **21 Befunde**; Muss offen **0**, Empfohlen offen **0**, nicht feststellbar **4** (freier Platz, HTTPS auf der Kommandozeile, und zwei davon abhängige) |
| Plattformprofil, Neuinstallation | dieselbe Funktion in einer Kopie von `server/` **ohne `config.php`** | **15 Befunde**, Muss offen **0** — die Einrichtung liefe durch |
| Die Weiche | `php tools/installweiche/pruefen.php` | `install.php` **623 Zeilen**, `php_mindest.php` **35 Zeilen**, 7 Token-Arten in der Sperrliste, **0 Befunde** |
| Die Weiche, Selbstprobe | `… --selbstprobe` | **8 von 8**; davon **4 Fälle, die NICHT anschlagen dürfen** (dieselben Wörter im Kommentar, `preg_match`, `match` als Zeichenkette, gewöhnlicher Rückgabetyp) |
| Kontingent-Warnung | `speicher_kontingente_melden()` mit Schwellen 50/53 % und `db_gb = 0,01` | beide Schwellen schlagen an; ohne Mailserver **2 Fehler**, Marke bleibt leer → wird wiederholt |
| Aufräumjob | `php jobs.php aufraeumen` | läuft durch, 8 Schritte, kein Fehler |
| Wartungsprobe **mit Teil 7** | `php tools/wartungsprobe/probe.php https://127.0.0.1:8443` | **67 Erwartungen, 0 nicht erfüllt** (vorher 57; Teil 7 bringt 10) |
| Torwächter im Browser | eigene Playwright-Probe, angemeldet als BetreiberIn | **12 von 12**: 503 auf der Startseite, Grund auf der Wartungsseite, JSON-503 für `ingest.php`, Betrieb → Updates offen mit Grund, „Wartung beenden" nach dem Lauf, danach wieder 200 · **0 Seitenfehler** |
| Bilderlauf `betrieb_updates.php` | acht Breiten | **8 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 falsche Knopfhöhen** |
| **CSP-Probe, Quelltext** (AP4) | `php tools/cspprobe/pruefen.php` | **106 PHP-Dateien** (ohne `vendor/`), **108 `<script>`-Stellen**, **0 Befunde** über fünf Regeln; `style="…"` **0 in PHP**, **10 in `assets/*.js`** (erlaubt, E-P5a-32) |
| **CSP-Probe, Selbstprobe** | `… --selbstprobe` | **8 von 8**; davon **4 Fälle, die NICHT anschlagen dürfen** (ein Kommentar, der `<script>` nennt; `<script src>` auf eigene Dateien; `<script>` mit Nonce; `data-onload="1" name="onlineform"`) |
| **CSP-Probe, Browser** (AP4) | `node tools/cspprobe/browserprobe.mjs` | **33 von 33 Erwartungen**, **0 Seitenfehler**. Darunter: Nonce je Anfrage verschieden, 9 Seiten mit **0** Inline-Skripten ohne Nonce, JSON-Antwort mit `nosniff` und bewusst **ohne** CSP, Umschalten scharf ↔ Report-Only, vier Kachelanbieter erlaubt + ein nicht gelisteter gesperrt, eingeschleustes `<script src>` blockiert, Inline-Skript ohne Nonce blockiert (scharf) bzw. ausgeführt (Report-Only), `style="…"` wirkt weiter |
| **Gegenprobe des Meldewegs** | Teil derselben Probe | **4 absichtlich ausgelöste Verstöße → 4 Zeilen** in `csp_berichte`, richtig zusammengefasst (`inline` mit Zähler **2**). **Diese Zeile ist der Grund, warum die Zahl darunter etwas wert ist** — siehe den Kasten unten |
| **Bilderlauf, voll** (AP4) | `node tools/screenshots/aufnehmen.mjs` | **392 Einzelbilder, 49 Kontaktbögen · 0 Überlauf · 0 Konsolenfehler · 0 falsche Knopfhöhen** |
| **`netz_in_bereich()`** | 18 Fälle von Hand | **18 von 18** — IPv4/IPv6, Einzeladresse und CIDR, `/24`-Ränder, `0.0.0.0/0`, IPv4 gegen IPv6-Bereich, unlesbare Adresse, `/33` und `/-1` (die letzten vier müssen **false** ergeben) |
| **`kopf_hsts_tage_setzen()`** | 8 Werte | **8 von 8** — 0/1/7/365 angenommen, 2/−5/99999/30 **abgewiesen**; Vorgabe ohne Eintrag **1** |
| **`api/csp_bericht.php`, beide Formate** | Aufruf von Hand | `report-uri`-Rumpf und Reporting-API-Liste: je **204**, je eine Zeile; **Abfrageteil entfernt** (`suche.php?q=geheim` → `suche.php`). Unfug im Rumpf und ein `GET`: **204 ohne Zeile** |
| **CSP-Berichte nach dem Bilderlauf** | `SELECT COUNT(*) FROM csp_berichte` | **0** — beim **dritten** Lauf. Die beiden davor: siehe Kasten |
| **Wartungsprobe** (AP4-Stand) | `php tools/wartungsprobe/probe.php https://127.0.0.1:8443` | **67 Erwartungen, 0 nicht erfüllt** — darunter Erwartung 12a, die den Nonce-Fund gemacht hat |
| **Integritätswache, Selbstprobe** | `python3 tools/integritaetswache/wache.py --selbstprobe` | **30 Erwartungen, 0 nicht erfüllt** |
| **Integritätswache, Lauf** | `… https://127.0.0.1:8443 --unsicher` | **122 Dateien unter `assets/` gleich**, 1 Inline-Block gleich, 1 externes Skript gleich, 1 Formular gleich, 0 Ereignisattribute, 0 `javascript:`-Adressen — **kein Unterschied**. *Vor der Behebung: eine Abweichung, und zwar bei jedem Lauf* |

> ### Warum die „0 CSP-Berichte" dreimal gemessen wurde
>
> **Erster Lauf: 0 Berichte — und wertlos.** Die Richtlinie trug
> `report-to csp`, die Gruppe war nie auflösbar definiert, und Chromium
> **bevorzugt `report-to` und verwirft den Bericht dann ersatzlos**. Die
> Tabelle blieb leer, weil nichts ankam, nicht weil nichts zu melden war.
> Gefunden hat es die Gegenprobe: zwei absichtlich ausgelöste Verstöße
> standen in der Konsole und fehlten in der Tabelle.
>
> **Zweiter Lauf, nach der Behebung: 140 Verstöße** auf genau vier Seiten —
> `index.php` 65, `zeitraum.php` 31, `einsatz.php` 28, `tag_spuren.php` 16,
> alle mit der Quelle `data`. Das sind die vier Kartenseiten. Ursache: eine
> eingebaute Konstante in `leaflet.js` (ein 1×1 durchsichtiges GIF als
> `data:`), die in einer **minifizierten Bibliothek** steht und die eine
> Zählung im eigenen Quelltext deshalb nicht sehen kann. `img-src data:`
> war zuvor gestrichen worden, weil jene Zählung 0 ergab.
>
> **Dritter Lauf, mit `data:` in `img-src`: 0 Berichte** — und diesmal heißt
> das etwas.
>
> **Die Lehre für jedes künftige Paket:** Eine Prüfung, die nur nachsieht, ob
> *nichts* gemeldet wurde, kann „alles in Ordnung" nicht von „der Meldeweg
> ist kaputt" unterscheiden. Sie muss bei jedem Lauf einen Verstoß auslösen
> und ihn wiederfinden. In `browserprobe.mjs` steht das als fester Punkt.

**Was die Zahlen benennen** (`CLAUDE.md` 6): Die Wortliste hat sechs Bereiche
gemessen (Server-PHP, Skripte, Dokumentation, Android, `watch/`), nicht einen;
die Vollständigkeitszahl ist ein **Vergleich gegen den Ausgangsstand**, keine
absolute Güte — 340 ist der Altbestand aus P3, den AP4 und spätere Pakete
teilweise abbauen (13 `style=`-Attribute, davon **3 in PHP**: die drei, die
AP4 auflöst).

---

### 1e. Nach AP5 (Web 20.9.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Mailprobe** (neu) | `php tools/mailprobe/probe.php` | **41 Prüfungen, 0 Befunde** über 13 Abschnitte, gegen eine eigene SMTPS-Gegenstelle in fünf Betriebsarten |
| **Jobprobe** | `php tools/jobprobe/probe.php` | **35 Erwartungen, 0 nicht erfüllt** (Teil 10 neu: 7 Erwartungen zum Job `mail`) |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 96 Regeln, 96 gegriffen; sechs Bereiche (Server-PHP, Skripte, Doku, Android, `watch/`) |
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py --hoechstens 367` | **367** (50 + 10 + 299 + 8). Die Kette stand während des Umzugs auf 371 (Doppelbestand: dieselben Texte im Katalog *und* in den alten Aufrufern) und ist zurückgezogen. **Im AP5-Commit standen 366, und das war um 1 zu niedrig** — siehe den Kasten unten |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **0 Befunde**; 206 Katalogspalten, 29 Löschungen, 4 erklärte Ausnahmen, 0 ungenutzt |
| CSP-Probe | `php tools/cspprobe/pruefen.php` | **0 Befunde** über 108 PHP-Dateien und 108 `<script>`-Stellen |
| Installweiche | `php tools/installweiche/pruefen.php` | **0 Befunde** — der neue `require` auf `instanz_lib.php` steht hinter der PHP-Weiche |
| PHP-Syntax | `php -l` je berührter Datei | 0 Fehler |

**Zählungen zur Abnahme des Umzugs:**

| Was | Befehl | Ergebnis |
|---|---|---|
| Aufrufer von `smtp_send()` außerhalb von `mail_lib.php` | `grep -rn "smtp_send" server/*.php` | **0** (nur noch Kommentare, die die Geschichte erzählen) |
| Fest eingebaute gen-em-Adressen und -Domains | `grep -rn "gen-em\.org\|@gen-em" server/` | **0** |
| `base_url` von Hand | `grep -rn "base_url" server/ --include=*.php \| grep -vE ":[0-9]+: *(\*\|//\|#)"` | **7 Stellen, alle rechtmäßig**: `instanz_lib.php` 2× (die Bibliothek selbst), `install.php` 4× (die Quelle — dort wird der Wert erfragt und geschrieben), `config.example.php` 1×. Ohne den Kommentarfilter kommen 3 weitere Treffer dazu, alle in **Kommentaren** (`smtp.php`, `kopfzeilen_lib.php`, `version.php`) — die Zahl ohne Filter wäre 10 und hieße nichts |
| Empfängeradresse im Fehlerprotokoll | `grep -n "toEmail" server/smtp.php` \| `grep error_log` | **0** |
| `luftrettung.net` in lebender Dokumentation | `grep -rn "luftrettung\.net" docs/ --include=*.md`, ohne Changelog, Archiv und `konzepte/erledigt/` | **7 → 1**, und die eine ist der **Satz in `Technik.md` 5d.8, der das Weglassen erklärt**. Weiterhin da, mit Absicht: Changelog (3), Rahmenplan-Archiv (1), `konzepte/erledigt/` (9) und der **Prüffall der Wortliste** (`tools/wortliste/zerlegen.py`) — eine Historie, die man umschreibt, ist keine mehr, und ein Prüffall ohne Suchbegriff prüft nichts |

**Was die Zahlen benennen:** Die 41 der Mailprobe sind **Zusagen über
Fehlerfälle**, nicht über den Normalfall — gegen einen funktionierenden
Mailserver ließe sich keine einzige davon messen. Die 367 der Vollständigkeit
sind ein **Vergleich gegen den Ausgangsstand** aus P3, keine absolute Güte.

> **Ein Messfehler, hier und nicht in einer Fußnote.** Im AP5-Commit stand
> **366**, und die Kette bekam diese Schwelle mit. Gemessen worden war sie
> **mitten im Paket**, vor den letzten Kommentar- und Dokumentationszeilen
> desselben Pakets — committet wurde ein Stand mit **367**. Der Lauf war
> damit rot, und zwar wegen **Unterschreitung**: genau dafür wirkt die
> Schwelle seit Web 20.8.0 in beide Richtungen. Behoben in AP4a.
>
> **Die Lehre ist nicht „die Zahl war falsch", sondern „sie wurde zu früh
> genommen".** `CLAUDE.md` 6 sagt es wörtlich. Nachgemessen wird seither
> gegen einen **ausgecheckten Stand** (`git worktree add --detach`), nicht
> gegen die Arbeitskopie — sonst misst man, was man gerade tippt.

---

### 1f. Nach AP4a (Web 20.9.1), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py --hoechstens 367` | **367 — auf der Schwelle, unverändert.** AP4a fügt netto **0** hinzu: Zwei Auslassungszeichen in neuen Kommentaren sind wieder entfernt worden, statt die Zahl wachsen zu lassen |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0/0/0**. Beim ersten Lauf **2 Treffer in 1 Zeile** — der Satz in `Technik.md` 5d.8, der das Verschwinden der alten Produktivdomain beschreibt und sie dabei nannte. Umformuliert; ein Werkzeug, das die eigene Erfolgsmeldung als Befund liest, hat recht |
| **Sitzungshärtung** (neu) | `php tools/sitzungshaertung/pruefen.php --selbstprobe` | **8 von 8** |
| dieselbe | `php tools/sitzungshaertung/pruefen.php` | **108 PHP-Dateien, 7 echte `session_start()`-Aufrufe, 0 ohne Härtung** (vor AP4a wären es 5 gewesen) |
| Fixations-Gegenprobe | `curl` mit vorgegebener, **frischer** Sitzungskennung gegen `login.php` | **ohne** die Zeile: kein `Set-Cookie` — die Kennung wurde übernommen. **Mit** ihr: `Set-Cookie` mit einer anderen Kennung. `php.ini` des Prüfstands: `session.use_strict_mode => Off` |
| Kopfzeilen der sieben umgestellten Endpunkte | Browser (angemeldet) und `curl` | alle mit `Content-Type: application/json; charset=utf-8`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` |
| `api/export_data.php` mit drei echten Einsatz-IDs | `fetch` aus der angemeldeten Seite | **HTTP 200, 68 820 Byte** Spurpunkte |
| `api/backup_data.php?teil=kopf` | dasselbe | **HTTP 200, 19 603 Byte** |
| `jobs.php` mit falschem und richtigem Token | `curl` | **403** bzw. **200** mit dem vollen Jobbericht |
| `auth_salt.php`, bekannte und unbekannte Adresse | `curl` | beide **200**, beide 32-Zeichen-Salt, ununterscheidbar |
| `pair.php`, Methode und unbekannte Aktion | `curl` | **405** bzw. **400** |
| Zählung | `grep -rn "Content-Type: application/json" server/` | **2 Codezeilen**: `db.php` und `wartung_lib.php` |

**Was die Zahlen benennen:** Die Fixations-Gegenprobe misst **den Unterschied**
und nicht den Zustand — „eine neue Kennung kommt zurück" belegt für sich
genommen nichts, solange nicht feststeht, dass die `php.ini` es nicht ohnehin
täte. Deshalb steht dort auch die `Off`-Zeile.

> **Eine Falle, die beim ersten Versuch zuschlug:** Eine Sitzungskennung, die
> schon einmal **benutzt** wurde, ist dem Server bekannt und wird auch mit
> `use_strict_mode` angenommen — richtig so. Der zweite Lauf maß deshalb das
> Gegenteil des ersten, bis beide eine frische Zufallskennung bekamen. Wer die
> Probe wiederholt, würfelt jedes Mal neu.

---

### 1g. Nach AP6 (Web 20.10.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Ratenprobe** (neu) | `php tools/ratenprobe/probe.php` | **49 Prüfungen, 0 Befunde** über 11 Abschnitte |
| Kopplungsprobe | `php tools/kopplungsprobe/probe.php` | **76 Erwartungen, 0 nicht erfüllt** |
| Wartungsprobe | `php tools/wartungsprobe/probe.php` | **67 Erwartungen, 0 nicht erfüllt** |
| Mailprobe | `php tools/mailprobe/probe.php` | **41 Prüfungen, 0 Befunde** |
| Jobprobe | `php tools/jobprobe/probe.php` | **35 Erwartungen, 0 nicht erfüllt** |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **0 Befunde**; 51/51 Kennungen, 34 Tabellen, 217 Spalten |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0/0/0** |
| Vollständigkeit | `… --hoechstens 372` | **372 — auf der Schwelle.** 367 + 5 Pfeile „Betrieb → Status" in neuen sichtbaren Texten |
| CSP-Probe, Sitzungshärtung, Installweiche | je eigener Lauf | **0 / 0 / 0** |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **22 Paare, 0 verfehlt** |
| PHP-Syntax | `php -l` je Datei | **482 Dateien, 0 Fehler** |

**Die Zahlen der Ratenprobe im Einzelnen** — sie sind die Abnahme von AP6:

| Was | Gemessen |
|---|---|
| Leiter | 10 Fehlversuche → Stufe 1, **900 s**; weitere → 2 (1200 s), 3 (1800 s), 4 (3600 s); darüber bleibt es bei 4 |
| Verfall | `stufe_bis` abgelaufen → nächste Sperre wieder **Stufe 1, 900 s**. Gegenprobe: laufende Frist wird von einem Fehlversuch auf volle 24 h verlängert, Stufe bleibt |
| Zwei Schwellen | 49 je Adresse sperren nicht, der 50. sperrt; 9 je Konto sperren nicht, der 10. sperrt |
| Verlangsamung | 200/400/800/1600 → Stufe 1/2/3/4; gemessen **1,00 s** und **8,0 s**; abgelaufenes Fenster → Stufe 0 |
| Gleiche Antwortzeit | **20,5 ms gegen 20,2 ms, Differenz 0,3 ms** über 100 Messungen |
| Sammelmail | erster Anlass → 1 Zeile; zwei weitere in derselben Stunde → **unverändert 1**; nach einer Stunde → 2; abgeschaltet → unverändert |
| Aufheben | `rate_sperre_aufheben()` löscht und protokolliert; `rate_konto_freigeben()` räumt `login` **und** `salt`, auch bei abweichender Schreibweise |
| Leiter je Topf | genau `login`, `login_ip`, `salt`; `reset` und die Kopplungstöpfe ausdrücklich nicht; `global` sperrt nie |

**Was die Zahlen benennen:** Die 49 sind Aussagen über die **Bibliothek**,
nicht über den Weg durch `login.php` — den misst der Browser (Abschnitt 2).
Und die Uhr ist gestellt: „Nach 24 Stunden fällt die Stufe" ist gemessen,
indem `stufe_bis` zurückdatiert wurde, nicht indem gewartet wurde.

### 1h. Nach AP7 (Web 20.11.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Ingestprobe** (um Teil 10 erweitert) | `php tools/ingestprobe/probe.php` | **83 Erwartungen, 0 nicht erfüllt** — davon **21 neu** |
| Ratenprobe | `php tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** (zwei neu) |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **0 Befunde**; 52/52 Kennungen, 34 Tabellen, **219 Spalten** |
| **Laufzeit über den erzeugten Sendeplan** | eigenes Messskript, 612 Anfragen je Lauf | siehe Tabelle unten; **0 Fehlversuche in allen vier Läufen** |
| Bilderlauf (zwei berührte Seiten, acht Breiten) | `node tools/screenshots/aufnehmen.mjs --nur 33-,45-` | **16 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0/0/0** (98 Regeln, 98 gegriffen) |
| Vollständigkeit | `… --hoechstens 372` | **372 — unverändert** |
| PHP-Syntax | `php -l` je geänderte Datei | 0 Fehler |

**Die Zahlen der Ingestprobe Teil 10 im Einzelnen** — sie sind die Abnahme
von AP7:

| Was | Gemessen |
|---|---|
| 14 Fehlversuche in einem Stoß | **alle `401`**, `gesperrt_bis` leer, Zähler 14 |
| Versuche 15 bis 30 | **weiterhin `401`** — der *sperrende* Versuch selbst wird nicht abgewiesen |
| Versuch 31 | **`429`**, Rumpf `{"error":"zu_viele_versuche"}`, **`Retry-After: 900`**, Tabelle: Stufe 1, Rest 900 s |
| Die Antwort | nennt den Topf **nicht** (ein Schlüssel im Rumpf, sonst nichts) |
| Sperrereignisse | **genau 1** — eines je Sperre, nicht eines je Fehlversuch |
| Vermerk am Gerät | **30** mit Beginnzeitpunkt; der 31. Versuch ist **nicht** mitgezählt (er kam nicht bis zur Prüfung) |
| Nachbar an derselben Adresse | **`200`** — und der Adresstopf ist **leer** |
| `403 device_disabled` | **zählt nicht** (keine Zeile im Topf) |
| `400 payload` | **zählt nicht** |
| `413 too_large` | **zählt nicht** |
| Gelungener Upload | leert Topf **und** Vermerk (`anzahl 30 → 0`, `seit → NULL`) |
| 30 erfundene Kennungen | alle `401`, der 31. **`429`** — **dieselbe Schwelle** wie bei bekannter Kennung (E-P5a-47) |
| Erfundene Kennung | hinterlässt **keinen** Gerätevermerk (0 Geräte) |
| Gesperrte Adresse | auch ein gültiges Gerät bekommt **`429`** — der in E-P5a-47 benannte Kollateralschaden, gemessen und nicht behauptet |

**Die Laufzeitmessung, je zwei Läufe:**

| | ohne Bremse | mit Bremse | Δ |
|---|---|---|---|
| Median je Anfrage | **14,43 ms** | **15,14 ms** | +4,9 % |
| Mittel je Anfrage | **19,67 ms** | **20,33 ms** | +3,4 % |
| Gesamtdauer 612 Anfragen | 12,10 s | 12,51 s | +3,4 % |
| Fehlversuche | **0** | **0** | — |

**Was diese Zahl benennt — und was nicht.** Die Streuung **zwischen zwei
gleichen Läufen** liegt bei 3 bis 4 %; der Aufschlag ist damit die **obere
Schranke**, nicht der Messwert. Gemessen ist der Weg durch `ingest.php` auf
einem Container mit lokaler MariaDB — nicht auf geteiltem Webspace, wo eine
zusätzliche Abfrage anders wiegt. Und gemessen ist der **gelungene** Weg: Die
Bremse kostet dort genau eine indizierte Abfrage; im Fehlerzweig kommen die
Zählschritte dazu, und der Fehlerzweig ist der, den niemand schnell braucht.

---

### 1i. Nach AP8 (Web 20.12.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Klickprobe** (neuer Weg) | `node tools/klickprobe/probe.mjs --nur P5a-AP8` | **1 von 1 erfüllt** |
| Bilderlauf (3 Seiten, 8 Breiten) | `… --nur 45b-,45-,43a-` | **24 Bilder · 0 Überlauf · 0 Konsole · 0 Knopfhöhen** |
| Wartungsprobe | `php tools/wartungsprobe/probe.php` | **67/0**; Ausnahmeliste **14** statt 13 |
| Ratenprobe · Ingestprobe · Kopplungsprobe | je eigener Lauf | **50/0 · 83/0 · 76/0** |
| Mailprobe · Jobprobe | je eigener Lauf | **41/0 · 35/0** |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **0 Befunde** — AP8 bringt **keine** Migration |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0/0/0** |
| Vollständigkeit | `… --hoechstens 377` | **377** — 372 + 5 Pfeile in neuen sichtbaren Texten |
| CSP · Sitzungshärtung · Installweiche · Kontraste | je eigener Lauf | **0 · 0 · 0 · 22/0** |
| PHP-Syntax | `php -l` je Datei | **458 Dateien, 0 Fehler** |

**Die Zahlen der Klickprobe im Einzelnen** — sie sind die Abnahme von AP8,
und sie sind der Grund, warum der Weg überhaupt entstanden ist: Der Bilderlauf
hat noch nie einen Knopf gedrückt.

| Was | Gemessen |
|---|---|
| Sperre in der Karte | genau **eine** Zeile mit dem Prüfmerkmal, und sie trägt einen Knopf |
| Klick auf „Aufheben“ | öffnet die **Rückfrage** aus `data-confirm` (Dialog im DOM) |
| nach dem Bestätigen | Zeile mit Prüfmerkmal **1 → 0** |
| in der Datenbank | `rate_limits` **1 → 0** |
| im Protokoll | Ereignis `aufgehoben` mit `wer` = **`admin@gen-em.org`** |
| Meldung | „Die Sperre ist aufgehoben. Der Vorgang steht unten in den Ereignissen …“ |

Der Weg **legt seine Sperre selbst an** (`id:klickprobe-ap8@example.invalid`,
Topf `login`) und räumt sie im `finally` ab — auch wenn er unterwegs
scheitert. Eine echte Sperre zu benutzen hieße, sich selbst auszusperren.

**Ein Befund, der keiner war, und warum er hier steht.** Die Kopplungsprobe
meldete zwischendurch **1 von 76 verfehlt** („Versandweg nach der Antwort
betreten, Protokollzeile SMTP“). Die Ursache lag nicht im Code, sondern
daran, dass der PHP-Server von Hand in ein **anderes Protokoll** gestartet
worden war als das, in das die Probe sieht (`/tmp/php-server.log`, Vorgabe in
`probe.php:54`). Nach einem Start über
`tools/referenzdatensatz/einspielen/lokal_starten.sh`: **76 von 76**. Wer die
Proben fährt, startet die Installation über das Skript — sonst misst er den
Prüfstand und nicht die Anwendung.

---

### 1j. Nach AP9 (Web 20.13.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Verbindungsprobe (neu)** | `php tools/verbindungsprobe/probe.php` | **24 von 24 Erwartungen erfüllt.** Teil 1: 10 Verbindungen belegt, dann `SQLSTATE[HY000] [1226]`; `login.php` **503** mit `Retry-After: 5`, `Cache-Control: no-store`, dem Satz aus E-P5a-18, **kein `<script>`** und **keinem von 7** Datenbank-Begriffen (`SQLSTATE`, `1040`, `1203`, `PDO`, Datenbankbenutzer, `max_user_connections`, `Stack trace`); `ingest.php` und `auth_salt.php` **503 JSON** `{"error":"ausgelastet"}`; `index.php` **302** (erreicht die Datenbank nie); Zähler **ges 3, n 3, sp 3** bei **3** Abweisungen. Teil 2 (8 Arbeiter, 2 freie Plätze, 20 gleichzeitige Pakete): **8 × 200, 12 × 503, 0 anderes**; davon **10** aus der Verbindungsgrenze (Zählerdifferenz) und **2** aus Gedrängel; nach Wiederholung **20 von 20** Einsätzen und **400 von 400** Spurpunkten in der Datenbank |
| Messstand | `python3 messen.py --frisch` | **5050 Einsätze · 1000 Diensttage · 2 813 201 Spurpunkte**, Bestand in 32,2 s erzeugt, in **231,9 s** über den regulären Weg eingespielt, **0 Konsolenfehler**. Browserprobe: Suche **3,18 s**, Tagesansicht **1,11 s**, **Zeitraumübersicht 42,61 s** (Jahr 2026, 3983 Einsätze, 3983 `<tr>`, 2,2 MB JSON), **Nachbearbeitung 2,83 s** (0 offene Zuordnungen), Backup **49,84 s / 11,8 MB**. Serverprobe nach Verdichtung und `OPTIMIZE`: Spuren **3,66 MB je 1000 Einsätzen** (Ziel 3), Fensterweg des Backups **0,92 s / 12 MB Spitze**, Waisen-Vollscan **0,033 s / 0 Waisen** |
| Wartungsprobe | `php tools/wartungsprobe/probe.php` | **67 Erwartungen, 0 nicht erfüllt** — AP9 fasst `wartung_lib.php` an (gemeinsames Gerüst, vierstellige JSON-Liste), und die Probe misst genau das nach |
| Ratenprobe · Ingestprobe · Kopplungsprobe | je `probe.php` | **50/0 · 83/0 · 76/0** |
| Mailprobe · Jobprobe · Spurprobe | je `probe.php` | **41/0 · 35/0 · 45/0** |
| Bilderlauf | `--nur 45-,45b,01-` | **24 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px) |
| Wortliste | `python3 wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 98 Regeln, 98 gegriffen. Eine Regel (`maschine-als-rechner`) um den Zeilenkopf „Antwort, Maschinen" erweitert — der gleiche Kopf in Technik.md 4.99c lag zufällig im Uhr-Kapitel und war darüber erklärt, der neue in 5e nicht |
| Vollständigkeit | `python3 pruefen.py` | **377 = unverändert.** Erster Lauf: 380; die drei zusätzlichen waren **Auslassungszeichen in neuen Kommentaren** und sind entfernt |
| CSP-Probe | `php tools/cspprobe/pruefen.php` | **0 Befunde** auf **109** `<script>`-Stellen |
| Sitzungshärtung · Installweiche · Migrationsregister | je `pruefen.php` | **0 · 0 · 0** (52 Kennungen, 34 Tabellen, 219 Spalten) |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| PHP-Syntax | `php -l` über `server/` und `tools/` | **484 Dateien, 0 Fehler** |

**Ein Nebenbefund zur Messmethode.** Die Serverprobe meldete für die Spuren
zuerst **19,30 MB je 1000 Einsätzen** und dann **3,66 MB** — derselbe Bestand,
derselbe Lauf. Der Unterschied ist `--optimieren`: Ohne `OPTIMIZE TABLE` zählt
der **belegte** Platz, und der enthält die von der Verdichtung freigegebenen
Seiten. Das Werkzeug sagt es in seiner Ausgabe selbst dazu; wer die erste Zahl
zitiert, zitiert eine Tabelle und keinen Bestand.

---

### 1k. Nach AP10 (Web 20.14.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Versandprobe, Teil 12 neu** | `php tools/versandprobe/probe.php /tmp/versandprobe` | **135 von 135** (116 vorher). Regel AUS: 5 Sicherungen gesendet, **0 Löschungen**, Protokoll trägt 5 · 5 fremde Dateien dazugelegt → **10 am Ziel** · Regel AN (N = 2): **3 Löschungen**, **5 von 5 fremden bleiben** (darunter `2026-09-30T12-00-00Z_b0000009.zip` — gültiges Namensmuster, nie von uns gesendet), **7 statt 2** Dateien am Ziel, von den eigenen die **zwei jüngsten** übrig · Protokollzeilen **3 = 3** Löschungen, Grund „Aufbewahrung dieses Ziels: höchstens 2 je Konto" · `sz_loeschungen()` sieht dieselben 3 · dritter Lauf **0 gelöscht, 3 nicht wieder gesendet, 0 gesendet** · Statuszeile: Ziel **mit** Regel taucht nicht unter „wächst" auf, dasselbe Ziel ohne Regel und ohne Löschung schon |
| **Wiederherstellungsprobe, 5 Erwartungen neu** | `php tools/wiederherstellungs-probe/probe.php` | **110**, davon **2 rot** — und die sind **nicht** von AP10 (N33). Neu: `geraet_art: "radcomputer"` → **`NULL`**, `geraet_modell` daneben bleibt stehen, `"HANDY"` → **`handy`** (Gegenprobe), dasselbe am Ruhesegment, und beides steht im Prüfprotokoll (`geraet_art: keine bekannte Geräteart — als „unbekannt" übernommen`) |
| Migration | `php server/update.php` | `2026_09_16_sicherungsziel_aufbewahrung` **erfolgreich angewendet**. `ON DELETE CASCADE` gemessen: 2 Ziele entfernt → **0** Zeilen in `sicherungsziel_dateien` übrig |
| Bilderlauf | `--nur 43b,45-,45b` | **erster Lauf: 2 von 24 mit Überlauf** — `div.zeile-aktionen` bei 768 px (+156) und 1024 px (+120), Ursache fünf Knöpfe in einer Reihe. Kürzerer Text half nicht genug (+49 / +13). Nach `blatt_immer`: **24 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** |
| Verbindungsprobe | `php tools/verbindungsprobe/probe.php` | **24/24** — in 13 Läufen einmal **23/24**, unmittelbar nach einer Reihe anderer Proben. Die Probe zählt seither vor dem Belegen die fremden Verbindungen desselben Datenbankkontos und sagt es |
| Wartungsprobe · Ratenprobe · Ingestprobe | je `probe.php` | **67/0 · 50/0 · 83/0** |
| Kopplungsprobe · Mailprobe · Jobprobe · Spurprobe | je `probe.php` | **76/0 · 41/0 · 35/0 · 45/0** |
| Wortliste | `python3 wortliste.py` | **0/0/0**, 98 Regeln, 98 gegriffen |
| Vollständigkeit | `python3 pruefen.py` | **377 = unverändert.** Erster Lauf **388**; die elf Zusätzlichen waren Auslassungszeichen, Pfeile und **Malzeichen** (`×`) in neuen Kommentaren und sind dort entfernt |
| CSP · Sitzungshärtung · Installweiche · Migrationsregister | je `pruefen.php` | **0 · 0 · 0 · 0** |
| Kontraste · PHP-Syntax | | **22 Paare, 0 verfehlt** · **484 Dateien, 0 Fehler** |

---

### 1l. Nach AP11 (Web 20.15.0), im selben Container

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| **Geräteprobe, Teil 2 neu** | `php tools/geraeteprobe/probe.php` | **59 von 59** (39 vorher), davon **20 neue gegen die Datenbank**. Fünf Geräte A bis E: Vorschau nennt **genau A und B**, zählt C/D/E als unbekannt und **schreibt dabei nichts** (Gegenzählung in der Tabelle) · der Lauf schreibt **dieselbe Menge** · A bekommt „Venu 3S" · **B: die Tabelle schlägt die Selbstauskunft** (`geraet_art` „uhr" → „sonstiges") · C (Handy) und D (unbekannte Teilenummer) bleiben **Zeichen für Zeichen** gleich · die **Rohangabe** ist bei allen fünf unverändert · zweiter Lauf mit derselben Tabelle: **0 geschrieben** · **erweiterte Tabelle** (E kommt hinzu): anderer Fingerabdruck, **genau 1** Zeile nachgezogen, C und D weiter unberührt · Job bei stehendem Hash **0**, bei geändertem **1**, danach steht der Hash der ausgelieferten Tabelle · Blockgröße 1: `geprueft 1`, `fertig false`, Fortsetzungsmarke wandert |
| Jobkatalog | `php server/jobs.php` (CLI) | `nachaufloesen` steht **zwischen `komplett` und `waisen`**, meldet „fertig · erledigt 0" auf einem Bestand, der nichts nachzuziehen hat |
| Wartungsprobe · Jobprobe · Kopplungsprobe · Ingestprobe | je `probe.php` | **67/0 · 35/0 · 76/0 · 83/0** |
| Bilderlauf | `--nur 45-` | **8 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px) — gemessen ist die **Statusseite**, die einzige Seite, die AP11 verändert |
| Wortliste | `python3 wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; **99** Regeln, 99 gegriffen. Eine neu (`technik-nachloesejob`): Der neue Abschnitt in `docs/Technik.md` nennt **Garmin**, weil es dort die Sache **ist** — Teilenummern wie `006-B4127-00` gibt es nur dort, und ein neutraler Ersatz würde die Stelle unverständlich machen |
| Vollständigkeit | `python3 pruefen.py` | **377 = unverändert** |
| CSP · Sitzungshärtung · Installweiche · Migrationsregister | je `pruefen.php` | **0 · 0 · 0 · 0** |
| Kontraste · PHP-Syntax | | **22 Paare, 0 verfehlt** · **485 Dateien, 0 Fehler** |

> **Was diese Zahlen NICHT sagen.** Die Geräteprobe misst mit einer **eigenen,
> fünfzeiligen** Modelltabelle, nicht mit den 325 ausgelieferten Teilenummern
> (N34), und der Bestand, über den nachgelöst wird, ist ebenso fünf Zeilen lang
> (N35). Gemessen ist damit das **Verhalten** des Nachlösens — wen es anfasst,
> wen nicht, wann es wieder läuft —, nicht der Inhalt der Tabelle und nicht das
> Zeitbudget über Tausende Zeilen.

---

### 1m. Abschluss AP12 (16.09.2026), im selben Container

**Die Kreisläufe — der Regressionsdurchgang nach R24.** Je Lauf ein frisches Konto, Referenz hinein, wieder heraus, Feld gegen Feld:

| Lauf | Einzelvergleiche | unerklärt | erwartet | ungenutzte Regeln |
|---|---:|---:|---:|---:|
| `kreislauf.py --art edbak --frisch` | **328 771** | **0** | 21 | 0 |
| `kreislauf.py --art csv --frisch` | **10 922** | **0** | 1271 | 0 |
| `kreislauf.py --art edbak-alt --frisch` (R11, Altformat) | **287 852** | **0** | 795 | **0** (nach der Korrektur unten) |

**Und die Probe aufs Exempel — weil ein Vergleich, der nichts meldet, zweideutig ist:** `vergleichen.py --testabweichung` legt dem Werkzeug Veränderungen hin, die es finden **muss**, und solche, die es **nicht** melden darf. **Backup 15/15, CSV 10/10** — darunter die Gegenproben, die vor der Normalisierung angreifen (verschobene Diensttag-Kennungen, vertauschte Stammdatenzeilen, vertauschte Reanimationsereignisse, geändertes Herkunftskonto): **0 Meldungen**, wie es sein soll.

> **Ein Nebenbefund am Prüfmittel, gefunden weil die Zahl nicht null war.** Der Altformatlauf meldete zuerst **1 ungenutzte Ausnahmeregel**. Die Regel beschrieb den Sprung der Nutzlastfassung `7 → 10`; gemessen sind aber **7 → 11** (seit Web 19.0.0, S9/AP7 — die Notiz des Einsatzes wanderte in den verschlüsselten Block). Eine zweite Regel ohne `von` fing den Fall auf, deshalb blieb „unerklärt" bei 0 **und niemand sah, dass die erste tot war**. Zu **einer** Regel zusammengefasst, die beide Enden nennt. Der Befund ist **nicht** von P5a — er liegt seit S9 —, aber er ist hier aufgefallen, und eine ungenutzte Regel ist genau das, wovor das Werkzeug in seiner eigenen Ausgabe warnt: „Entweder beschreiben sie etwas, das es nicht mehr gibt — dann gehören sie weg."

> **Ein zweiter Nebenbefund, diesmal an der Dokumentation.** Die Ordner unter `tools/` gegen den Werkzeugbaum in `docs/Technik.md` gezählt: **44 auf der Platte, 43 im Baum**. Es fehlte `tools/containerprobe/`, seit **Web 12.0.0** (S2/AP6, fünf Monate). Nachgetragen, jetzt **44 zu 44**. Dieselbe Ursache wie Backlog **Nr. 208** — eine von Hand geführte Aufzählung neben einer Wahrheit, die woanders steht; dort mit der Zählung nachgetragen.

**Der Stilvergleich ist nicht gefahren worden, und das ist kein Übergehen:** `git diff origin/main..HEAD -- server/assets/style.css` ist **leer**. Die ganze Phase hat das Stylesheet nicht angefasst — alle Oberflächenänderungen laufen über vorhandene Bausteine (`ui_karte_start`, `ui_zeilenaktionen`, `meldung-warn`). Der Stilvergleich misst Regelverschiebungen; ohne geänderte Regeln misst er den Vergleichsstand gegen sich selbst.

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| Wortliste | `python3 wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** · 99 Regeln, **99 gegriffen** · gemessen über **190 Dateien** in fünf Bereichen: 109 PHP, 36 JS, 8 Dokumente, 2 Android, 35 Uhr — also jeden Client |
| Bilderlauf, **voller Lauf** | `node aufnehmen.mjs` (ohne `--nur`) | **400 Einzelbilder · 50 Seiten · 8 Breiten (360–1920 px) · 0 waagerechter Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px). **Gegenprobe gefahren** — `md5sum *.png | sort -u`: **400 verschiedene Prüfsummen zu 400 Bildern**, also zeigt keine Seite das Bild einer anderen. Die Gruppen: 20 Inhalt, 8 Einstellungen, 8 Betrieb, 7 Öffentlich, 7 Administration |
| Vollständigkeit | `python3 pruefen.py` | **377 = unverändert** (der Stand seit AP8) |
| Kontraste | `python3 kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| PHP-Syntax | `php -l` über `server/` und `tools/` | **485 Dateien, 0 Fehler** |

---

---

## 2. Was im Browser geprüft wurde

**Nach AP1: nichts, und das ist richtig.** AP1 ändert an der Oberfläche keine
Zeile. Die einzige Codeänderung ist der Parameter `aktion` in `jobs.php` —
ein JSON-Endpunkt ohne Oberfläche, der sich mit dem Token aufrufen lässt
(Prüfpunkt P6).

**Nach AP2**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Bilderlauf `betrieb_status.php` und `betrieb_server.php`, acht Breiten von 360 bis 1920 px | **16 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px) |
| Karte „Plattform" **aufgeklappt** angesehen | 16 Zeilen, alle mit Messwert und Sollwert; 0 Konsolenfehler |
| Feld „Kontingent der Datenbank" neben „Webspace laut Hosting" | steht in derselben Reihe, Vorgabe **10** eingetragen |
| Ampel | Die Plattform-Karte trägt **0** zur Zählung oben bei — gemessen: 1 rot / 4 orange vor **und** nach ihrer Einführung, alle aus den anderen vier Karten |
| Weiche (nachgestellt, Kopie mit Untergrenze 99.0) | **HTTP 500**, Titel „PHP zu alt — Gen-EM NAdoku", Fließtext lesbar |

---

**Nach AP4**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Kopfzeilen der **Anmeldeseite** ohne Sitzung gelesen | CSP-Report-Only vollständig, dazu `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`; Abzug in `tools/cspprobe/kopfzeilen.png` |
| Nonce zweimal hintereinander | **verschieden** (`qE3YA2qE…` ≠ `iaDsZhxS…`) |
| 9 Seiten durchgegangen (Start, Zeitraum, Suche, Einstellungen, Betrieb ×2, Import, Nachbearbeitung, NutzerInnen) | jede mit CSP, **0** Inline-Skripte ohne wirksamen Nonce |
| Karte „Sicherheitskopfzeilen" auf Betrieb → Servereinstellungen | da; Schalter und Segment bedient, beide Richtungen wirken |
| Scharf geschaltet, dann Einschleuseversuche | fremdes `<script src>` **blockiert**, Inline-Skript ohne Nonce **blockiert**, `style="…"` wirkt weiter (E-P5a-32) |
| Import unter der scharfen Richtlinie | `import.php` lädt SheetJS und führt es aus (`window.XLSX` da) |
| Zurückgeschaltet auf Report-Only | wirkt; **Auslieferungszustand wiederhergestellt** |
| Voller Bilderlauf | 392 Bilder, **0 Konsolenfehler** |

**Was dabei auffiel und nicht auffallen sollte:** Die Zählung der Nonces
scheiterte zuerst an der Probe, nicht an der Anwendung — Browser **verstecken**
den Wert des `nonce`-Attributs nach dem Parsen, `getAttribute('nonce')` liefert
eine leere Zeichenkette. Der Wert steht nur in der Eigenschaft `.nonce`. Die
Probe fragt seither danach.

---

**Nach AP5**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Betrieb → Status, Karte **E-Mail**, Zeile „Warteschlange" im Zustand *leer* | blau, Plakette „leer", Text „Nichts liegt an" |
| dieselbe Zeile mit **zwei unzustellbaren** Zeilen im Bestand | **rot**, Plakette „2 unzustellbar", beide Adressen und der letzte Grund im Kleintext; Abzug `ap5-mailkarte-rot.png` |
| dieselbe Zeile mit **einer offenen** Zeile | orange, „1 wartet" |
| Verwaltung → Installation, Karte **„Adressen"** | da, unter „Name"; zwei Felder vom Typ `email`, eigener Knopf |
| Beide Adressen eingetragen und gespeichert | Meldung „Adressen dieser Installation gespeichert."; Werte stehen nach dem Neuladen im Feld |
| Adresse ohne `@` eingetippt | Der Browser weist sie ab (`type="email"`), **das Formular wird gar nicht abgeschickt** |
| Konsolenfehler auf beiden Seiten | **0** |

> **Die Browserprüfung der ungültigen Adresse belegt nichts über den Server.**
> `type="email"` hält den Klick auf, aber ein gebastelter POST nicht — dieselbe
> Lücke wie beim Namen in Teil 1. Gemessen ist deshalb der **Endpunkt**:
> `instanz_adressen_setzen()` gegen 7 Fälle (ohne `@`, Zeilenumbruch, CRLF,
> 191 Zeichen, Betreiberadresse ohne `@`, beide gültig, beide leer) —
> **5 abgewiesen, 2 angenommen**, jede mit der zutreffenden Meldung.

---

---

**Nach AP6**, gegen die lokale Installation (Chromium 141):

| Was | Ergebnis |
|---|---|
| Betrieb → Servereinstellungen, Karte **„Ratenschutz"** | da, mit Plakette „ruhig" bzw. „Verlangsamung Stufe N"; **0 Konsolenfehler** |
| Sechs Speicherversuche über das Formular | gültig **angenommen**; Leiter rückwärts, drei statt vier Werte, Konto = 1, Buchstaben — **vier abgewiesen**, jede mit der zutreffenden Meldung |
| Gegenprobe „nichts halb gespeichert" | gültige Leiter + ungültige Kontozahl → Leiter **unverändert** (25/30/40/80 vorher wie nachher) |
| Betrieb → Status, Karte Server | Zeile **„Verlangsamung"** orange („Stufe 2 … 640 Fehlversuche in den letzten 15 Minuten") und **„Gesperrt"** orange („1 Anschluss und 1 Name — höchste Stufe 4") |
| Anmeldeseite bei laufender Verlangsamung (GET) | „Die Anmeldung antwortet derzeit verzögert, etwa 2 Sekunden. Das ist eine Schutzmaßnahme; dein Passwort wird ganz normal geprüft." |
| Anmeldung mit gesperrtem Namen | „Zu viele Anmeldeversuche für diesen Namen. Wieder ab 12:29 Uhr. „Passwort vergessen?" geht weiterhin." · Formular **gesperrt** · Knopf „Anmelden (37 Minuten)" · Zustandszeile „Noch 37 Minuten." |
| **Gegenprobe: erfundene Adresse, ebenfalls gesperrt** | **wortgleiche** Meldung (nur die Uhrzeit unterscheidet sich) — keine Kontoauskunft |
| Countdown über 5 s | „Noch 43 Sekunden." → „Noch 38 Sekunden." — **er tickt** |
| Nach Ablauf der Sperre (52 s gewartet) | „Du kannst es wieder versuchen.", Formular **entsperrt** |
| Konsolenfehler auf allen drei Seiten | **0** |

> **Was der Browser hier NICHT belegt:** dass die Verlangsamung die Antwort
> tatsächlich aufhält. Die Seite sagt es nur an. Gemessen ist es in der
> Ratenprobe (1,00 s und 8,0 s) — und zwar in einem **eigenen Prozess**, weil
> `rate_verlangsamung()` sich ihre Antwort je Anfrage merkt.

**Nach AP9**, gegen die lokale Installation (Chromium 141):

| Was | Ergebnis |
|---|---|
| Betrieb → Status, Karte Server, Zeile **„Verbindungen"** bei 13 Vorfällen | „13 in dieser Stunde · Spitze 13 je Stunde (16.09.2026 18 Uhr) · insgesamt 13 · zuletzt vor 25 Minuten — max_user_connections beim Hoster anheben lassen", Plakette **„zu eng"**, orange; **0 Konsolenfehler** |
| Dieselbe Zeile ohne Zählerdatei | „Keine abgewiesene Verbindung seit Beginn der Zählung · persistente Verbindungen sind aus", Plakette „in Ordnung", blau |
| Dieselbe Zeile bei nicht schreibbarer Datei | **nicht über HTTP messbar** — der Prüfserver läuft als `root`. Am Funktionsaufruf mit unprivilegiertem Benutzer: `schreibbar=false` (N29) |
| **Ausgelastet-Seite** bei 360 px | HTTP **503**, `Retry-After: 5`, **0 px** waagerechter Überlauf; Logo **NEF** (Münzwurf), Stylesheet trägt, Text vollständig |
| Dieselbe bei 768 px | HTTP **503**, `Retry-After: 5`, **0 px**; Logo **Hubschrauber** — der Münzwurf des gemeinsamen Gerüsts arbeitet |
| Dieselbe bei 1280 px | HTTP **503**, `Retry-After: 5`, **0 px** |
| Konsolenmeldungen der Ausgelastet-Seite | **3** — je Breite eine: „Failed to load resource: 503". Das ist der **Statuscode des Dokuments selbst**, den Chromium meldet, kein fehlendes Teil der Seite: Das Stylesheet trägt sichtbar (Schriften, Farben, Meldungsrahmen), und die Seite lädt sonst nichts |
| Zeitraumübersicht mit 3983 Einsätzen | die Seite kommt und ist vollständig — sie braucht **42,61 s** dafür (Messstand, 6× Drossel). Das ist der Befund, kein Fehler |

> **Was der Browser hier NICHT belegt:** dass die 503 aus der
> Verbindungsgrenze kommt und nicht aus etwas anderem. Der Browser sieht nur
> den Statuscode. Belegt ist es über den **Zähler**: Er zählt genau die
> Abweisungen mit, die die Probe ausgelöst hat (3 von 3), und er zählt das
> Gedrängel ausdrücklich **nicht** mit — daraus ergibt sich in Teil 2 die
> Aufteilung 10 zu 2.

**Nach AP10**, gegen die lokale Installation (Chromium 141) und eine echte
SFTP-Gegenstelle (paramiko):

| Was | Ergebnis |
|---|---|
| Zielseite mit zwei Zielen | „Bildprobe Ohne Regel" trägt „**räumt dort nicht auf**", „Bildprobe Ziel" die orange Plakette „**räumt dort auf**" und die Zeile „behält dort 6 je Konto und 12 Komplett-Stände"; **0 Konsolenfehler** |
| Menü einer Zielzeile | fünf Handlungen im Blatt statt in der Reihe, „Löschen" abgesetzt und rot |
| **„Nachsehen, was dort liegt"** gegen die leere Gegenstelle | „Dort liegt nichts von hier — entweder ist noch nichts gesendet worden, oder es liegt unter einem anderen Pfad", 0 Dateien, 0 fremde |
| Dasselbe mit Inhalt | **4 Dateien · 2,0 MB in 2 Ordnern**, ältester Stand **30.07.2026**, jüngster **03.08.2026 · 11:00 Uhr**, **2 fremde (15 KB)** mit dem Satz „diese Anwendung fasst sie nie an, auch nicht mit eingeschalteter Aufbewahrungsregel" |
| Betrieb → Status → Sicherheit, sechste Karte | „Löschungen auf Sicherungszielen · 3" mit Ziel, Ordner, Datei, Größe, Zeitpunkt und **Grund** je Zeile |
| Betrieb → Status, Karte Backups | neue Zeile **„Aufbewahrung am Ziel"**, orange: „Auf ‚Bildprobe Ohne Regel' liegen 24 Sicherungen (206,0 MB), und es ist dort nie etwas entfernt worden — seit 19.06.2026" |
| Gegenprobe | Ein Ziel **mit** Regel taucht dort **nicht** auf (in der Versandprobe gemessen, nicht nur im Browser) |

> **Was der Browser hier NICHT belegt:** dass die Löschregel drüben das
> Richtige löscht. Der Browser sieht nur, was die Seite sagt. Belegt ist es in
> `tools/versandprobe/` Teil 12 — dort wird **am Ziel nachgezählt**, nicht in
> der Anwendung: 7 Dateien übrig, 5 davon fremd, die zwei jüngsten eigenen.

**Nach AP11**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Betrieb → Status, Karte „Server", neue Zeile **„Gerätemodelle"** im Zustand *aktuell* | wörtlich: „325 Teilenummern · zuletzt nachgelöst 16.09.2026 19:39 Uhr · 0 nachgezogen, 0 unbekannt (Handys und fremde Modelle, sie bleiben unberührt)" — grün, **0 Konsolenfehler** |
| Dieselbe Zeile nach einem **Eingriff in den Hash** (`app_state`-Schlüssel auf einen fremden Wert gesetzt) | orange: „325 Teilenummern · die Tabelle hat sich geändert, der Nachlöse-Job zieht beim nächsten Lauf nach" · Plakette **„steht aus"** |
| Dieselbe Zeile **ohne** gespeicherten Stand (Schlüssel gelöscht) | „325 Teilenummern · noch nie nachgelöst" · Plakette **„ungeprüft"** — der Zustand einer frisch aktualisierten Installation |
| Nach einem Joblauf zurück auf die Statusseite | Die Zeile steht wieder auf **aktuell**, mit neuem Zeitpunkt — belegt, dass Job und Anzeige denselben Schlüssel lesen und nicht zwei Wahrheiten führen |
| Bilderlauf derselben Seite, acht Breiten von 360 bis 1920 px | **8 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** |

> **Was der Browser hier NICHT belegt:** dass der Job das Richtige nachzieht.
> Die Statuszeile sagt nur, **ob** und **wann**. Dass er genau die Zeilen der
> neuen Teilenummer anfasst und Handys und Fremdmodelle in Ruhe lässt, ist in
> `tools/geraeteprobe/` Teil 2 gemessen — dort wird **in der Datenbank**
> nachgezählt, vorher und nachher.

---

---

## 3. Die Prüfliste

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

### P1 — Stufe 1 läuft grün (nach dem ersten Push)

**Weg:** Den Arbeitszweig pushen, in GitHub → Actions den Lauf **Prüfung**
öffnen.
**Erwartet:** Alle Schritte grün. In der Zusammenfassung stehen Zahlen: „PHP-
Syntax: N Dateien, 0 Fehler", der Wortlisten-Auszug, das Migrationsregister
mit seinen sechs Zahlen.
**Scheitern erkennbar an:** einem roten Schritt — **oder** an einer
Zusammenfassung, in der „ÜBERSPRUNGEN" steht, wo es nicht stehen soll. Ein
grüner Lauf mit zwei übersprungenen Schritten ist kein grüner Lauf.

### P2 — Das Backup-Tor bricht gegen die echte Installation ab

**Weg:** Den Produktionslauf mit einem Tag starten, dessen `JOBS_TOKEN`
absichtlich falsch ist (Umgebungsgeheimnis kurz ändern).
**Erwartet:** Der Schritt „Backup-Tor" endet **rot**, mit der Zeile „Die
Installation antwortet mit 'token' … Es wird nicht ausgeliefert." **Der
FTPS-Schritt läuft gar nicht erst.**
**Scheitern erkennbar an:** einem Lauf, der trotzdem hochlädt — dann steht das
Tor offen. Danach das Geheimnis zurücksetzen und den Lauf wiederholen.

### P3 — Die Pflichtfreigabe hält an

**Weg:** Tag `web-v20.4.0` setzen (oder die dann gültige Fassung). In Actions
den Lauf **Auslieferung** öffnen.
**Erwartet:** Der Job `produktion` steht auf **„Waiting"** mit dem Hinweis auf
die nötige Freigabe. Erst nach dem Klick auf *Review deployments → Approve*
läuft er los.
**Scheitern erkennbar an:** einem Job, der ohne Rückfrage durchläuft — dann ist
in der Umgebung `produktion` kein Reviewer eingetragen.

### P4 — Stufe 2 misst gegen Staging

**Weg:** Nach der Einrichtung von Staging (Zuarbeit) einen Push auf `main`.
**Erwartet:** Job `stufe2` grün; Kreisläufe **0 unerklärt**, Bilderlauf
**0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen**.
**Scheitern erkennbar an:** „ÜBERSPRUNGEN (kein Prüfkonto)" in der
Zusammenfassung — dann fehlen `STAGING_KONTO`/`STAGING_PASS`, und **gemessen
wurde nichts**.

### P5 — Uhr Stufe I im Lauf

**Weg:** `CIQ_GERAETE_URL` als Repositoriums-Secret hinterlegen, Push.
**Erwartet:** Der Schritt übersetzt die App für alle Zielgeräte und endet
grün.
**Scheitern erkennbar an:** der Warnung „CIQ_GERAETE_URL nicht gesetzt — Uhr
Stufe I ÜBERSPRUNGEN, nicht bestanden".

### P6 — Die vier Aktionen von `jobs.php` gegen eine echte Installation

**Weg:** Mit dem Job-Token aus Betrieb → Jobs:

```
python3 tools/kette/tor.py zustand     --basis https://… --token …
python3 tools/kette/tor.py wartung-an  --basis https://… --token …
python3 tools/kette/tor.py wartung-aus --basis https://… --token …
python3 tools/kette/tor.py backup      --basis https://… --token … --versuche 3 --pause 5
```

**Erwartet:** `zustand` nennt `version`, `wartung`, `komplett` und
`migration_ausstehend`. `wartung-an` legt `server/wartung.lock` an, und die
Startseite antwortet danach mit **503** — der Wartungsbalken auf
Betrieb → Updates sagt „von kette". `wartung-aus` räumt sie weg.
**Scheitern erkennbar an:** `{"ok": false}` mit Meldung — bei `wartung-an`
heißt das fast immer: die Anwendungswurzel ist nicht beschreibbar.

### P7 — Die Integritätswache hält nach einem Staging-Deploy still

**Weg:** Push auf `main` (Staging-Deploy). In Actions den Lauf
**Integritaetswache** öffnen.
**Erwartet:** Der erste Schritt meldet „Job 'produktion' in Lauf …: nicht
gelaufen", die beiden Arbeitsschritte sind übersprungen, in der
Zusammenfassung steht „Dieser Lauf ging nicht auf Produktiv — die Wache hält
still."
**Scheitern erkennbar an:** einer roten Wache nach einem Staging-Deploy. Dann
vergleicht sie Produktiv gegen einen Stand, der dort nicht liegt.

### P8 — Die Wache löst nach einem Produktiv-Deploy aus

**Weg:** Nach P3 denselben Lauf ansehen.
**Erwartet:** Die Wache läuft, Selbstprobe grün, Vergleich grün.
**Scheitern erkennbar an:** gar keinem Lauf — dann stimmt der Name in
`integritaet.yml` nicht mehr mit dem Anzeigenamen in `auslieferung.yml`
überein (Fund F4).

### P12 — Der Torwächter auf der echten Installation

**Weg:** Nach dem nächsten Deploy **mit** Migration nichts tun außer sich
anmelden.
**Erwartet:** Die Wartungsseite mit „Die Anwendung hat selbst geschlossen".
Auf Betrieb → Updates die Meldung „Vom Torwächter geschlossen", danach
„Ausstehende ausführen", dann der Knopf „Wartung beenden".
**Scheitern erkennbar an:** einer Startseite, die trotz ausstehender Migration
antwortet. Dann prüfen, ob `app_state` die Zeilen `migration_tor_hash` und
`migration_tor_offen` trägt — fehlen sie, ließ sich die Tabelle nicht
beschreiben, und der Torwächter fällt (absichtlich) auf „offen lassen"
zurück.

### P9 — Die Weiche auf einem echten alten PHP

**Weg:** Beim Hoster im Kundenmenü PHP auf **8.0 oder 8.1** stellen,
`install.php` aufrufen. (Auf einer Installation mit Betrieb **nicht**
machen — die Anwendung antwortet dann auf keiner Seite mehr.)
**Erwartet:** Die Seite „PHP ist zu alt" mit der gemessenen Fassung und dem
Grund.
**Scheitern erkennbar an:** einem Parse Error („syntax error, unexpected …")
statt der Seite. Dann ist in `install.php` oder `php_mindest.php` PHP-8-Syntax
gelandet, die `tools/installweiche/` nicht sieht — die vier Lücken stehen in
seiner LIESMICH.

### P10 — Die Kontingent-Warnmail geht wirklich hinaus

**Weg:** Unter Betrieb → Servereinstellungen das **Kontingent der Datenbank**
kurz auf einen Wert knapp über der aktuellen Größe setzen (die Zeile
„Datenbank" auf Status nennt sie), dann den Aufräumjob laufen lassen —
`php jobs.php aufraeumen` oder einen Tag warten.
**Erwartet:** Eine Mail „Datenbank: 70 % des Kontingents erreicht" an alle mit
Verwaltungsrecht, **einmal**. Ein zweiter Lauf schickt nichts mehr.
Danach das Kontingent zurücksetzen (leer = Vorgabe).
**Scheitern erkennbar an:** gar keiner Mail (dann prüfen, ob „Letzter Versand"
auf Status rot steht) oder an einer Mail bei **jedem** Lauf — dann wird die
Marke `speicher_schwellen_gemeldet` nicht geschrieben.

### P11 — Die Warnung der Speichergrenze, die es nie gab

**Weg:** Speichergrenze kurz so weit senken, dass die vorhandenen Backups über
70 % liegen, dann den Aufräumjob laufen lassen.
**Erwartet:** Eine Mail „Backups: 70 % der Speichergrenze erreicht".
**Warum dieser Punkt hier steht:** Diese Mail ist seit S8 geschrieben und ist
**nie** hinausgegangen — der Aufruf fehlte. Dass sie jetzt im Aufräumjob steht,
ist nachgelesen; dass sie ankommt, ist es noch nicht.

---

### P13 — Die vier Kartenanbieter auf einer Installation mit Netz

**Weg:** Betrieb → Servereinstellungen → **CSP scharf schalten**. Dann auf der
Tagesübersicht die Karte öffnen und alle **vier** Grundkarten durchschalten
(Standard, Wanderkarte, Topografie, Luftbild).
**Erwartet:** Alle vier zeigen Kacheln. Die Konsole bleibt leer.
**Woran ein Scheitern zu erkennen ist:** Die Karte bleibt **grau** und in der
Konsole steht „Refused to load the image … because it violates … `img-src`".
Dann fehlt eine Domain in `KOPF_KACHELN` — genau der Fall, vor dem Backlog
Nr. 181 warnt. Sofort zurück auf Report-Only schalten; die Adresse steht in
der Meldung.
**Warum dieser Punkt hier steht:** Der Wegwerf-Container erreicht die
Kachelserver nicht (N9). Geprüft ist, dass die **Richtlinie** sie durchlässt —
nicht, dass sie antworten.

---

### P14 — HSTS auf einer echten HTTPS-Installation

**Weg:** Nach dem Deploy Betrieb → Servereinstellungen → Karte
„Sicherheitskopfzeilen". Die HSTS-Stufe steht auf **1 Tag**. Mit den
Entwicklerwerkzeugen (Netzwerk → eine beliebige Seite → Antwort-Header) nachsehen.
**Erwartet:** `Strict-Transport-Security: max-age=86400`. **Genau einmal.**
Dann auf **1 Jahr** stellen, neu laden: `max-age=31536000`, wieder genau einmal.
**Woran ein Scheitern zu erkennen ist:** Die Kopfzeile steht **zweimal** da,
oder sie ändert sich beim Umschalten nicht. Dann ist die HSTS-Zeile in
`server/.htaccess` wieder aufgetaucht — sie muss **weg** sein (E-P5a-31), weil
`Header always set` überschreibt, was PHP schickt.
**Warum dieser Punkt hier steht:** Lokal liegt die Installation hinter einem
TLS-Weiterleiter, der unverschlüsselt an PHP weitergibt; HSTS wird dort
richtigerweise gar nicht gesetzt (N10).

**Und ein Satz für die Betreiberin:** Nach dem Update steht die Bindung auf
**1 Tag**, auch wenn die `.htaccess` vorher ein Jahr band. Wer produktiv läuft
und bei seiner Domain bleibt, stellt hier wieder auf **1 Jahr**.

---

### P15 — Der Weg über die Reporting-API

**Weg:** Auf der echten HTTPS-Installation eine beliebige Seite öffnen und in
den Antwort-Headern nachsehen.
**Erwartet:** Neben der CSP steht `Reporting-Endpoints: csp="https://…"` mit
der **vollständigen** Adresse dieser Installation, und die CSP endet auf
`report-uri api/csp_bericht.php; report-to csp`.
**Gegenprobe (wichtiger als die Zeile selbst):** In der Konsole
`document.head.appendChild(Object.assign(document.createElement('img'), {src:'https://kachel.invalid/x.png'}))`
ausführen, dann Betrieb → Servereinstellungen neu laden. **Unter den Berichten
muss eine Zeile `img-src | https://kachel.invalid` stehen.**
**Woran ein Scheitern zu erkennen ist:** Die Konsole meldet den Verstoß, die
Liste bleibt leer. Genau das ist am 15.09.2026 passiert (F6), und es sah aus
wie ein grünes Ergebnis. Dann zeigt `report-to` auf eine Gruppe, die der
Browser nicht auflösen kann.
**Warum dieser Punkt hier steht:** Lokal liefert `kopf_melde_url()` bewusst
nichts (kein HTTPS), also steht `report-to` dort gar nicht erst in der
Richtlinie — geprüft ist nur der Weg über `report-uri` (N11).

---

### P16 — Scharf schalten, nach zwei Wochen und nicht früher

**Weg:** Zwei Wochen normalen Betrieb abwarten. Dann Betrieb →
Servereinstellungen → Karte „Sicherheitskopfzeilen" → die Liste der Berichte
ansehen.
**Erwartet:** **leer.** Erst dann den Schalter umlegen.
**Danach durchklicken, und zwar diese sechs Wege:** Karte in allen vier
Anbietern · Einsatzformular mit Adresssuche (tippen, Vorschlag wählen) ·
Import einer XLSX-Datei · Export · Druckansicht · Schneidewerkzeug einer Spur.
**Woran ein Scheitern zu erkennen ist:** Ein Knopf tut **nichts**. Keine
Fehlermeldung, keine rote Seite — die Meldung steht nur in der Konsole
(F12). Das ist die Betriebsart eines vergessenen Nonces. Sofort
zurückschalten; der Schalter wirkt ohne Neustart.
**Steht etwas in der Liste, ist es vor dem Umschalten zu klären, nicht danach.**
Jede Zeile nennt Richtlinie, Quelle und Seite — genug, um sie zu finden.
**Warum dieser Punkt hier steht:** Die zwei Wochen messen, was ein Prüflauf
nicht sieht: was **Nutzerinnen** auf Wegen tun, die der Bilderlauf nicht geht.

---

### P17 — Hinter einem Reverse Proxy

**Weg:** Nur nötig, wenn die Installation hinter einem Reverse Proxy,
Loadbalancer oder DDoS-Schutz steht. In `config.php` den Block `netz` füllen
(Adressen oder CIDR), dann von **zwei verschiedenen** Anschlüssen aus je
einmal falsch anmelden.
**Erwartet:** Beide Fehlversuche zählen **getrennt**. In `rate_limits` stehen
zwei Zeilen mit verschiedenen Adressen.
**Woran ein Scheitern zu erkennen ist:** Es steht **eine** Zeile da, mit der
Adresse des Proxys. Dann greift der Eintrag nicht — Tippfehler im CIDR, oder
der Proxy meldet sich mit einer anderen Adresse, als dort steht. Die Folge im
Betrieb: Der Ratenschutz sperrt **alle** Nutzerinnen gemeinsam aus.
**Die Gegenrichtung ist genauso wichtig:** Steht dort ein zu weiter Bereich
(etwa `0.0.0.0/0`), darf jeder seine eigene Adresse behaupten — und der
Ratenschutz ist wirkungslos. Im Zweifel **leer lassen**; dann rechnet alles
wie vor Web 20.7.0.

---

### P18 — Eine echte Mail auf einem echten Mailserver

**Wofür:** N14 und N15 — der ganze Weg endet hier lokal beim „250
angenommen". Was danach kommt, sagt nur ein Postfach.

**Weg:**

1. Auf der Installation unter **Verwaltung → Installation → Adressen** eine
   Kontaktadresse eintragen (eine, die gelesen wird).
2. **Betrieb → Status → „Testmail an mich"** klicken.
3. Die Mail im Postfach öffnen.
4. Eine **Einladung** verschicken (Verwaltung → NutzerInnen → anlegen) und
   die Mail dort ebenfalls öffnen.
5. **Passwort vergessen** auslösen und die dritte Mail ansehen.

**Erwartet:**

- Alle drei tragen denselben Rahmen: Anrede, Sache, **„Bei Fragen wende dich
  an <deine Kontaktadresse>."**, „Viele Grüße" + **Name der Installation**.
- Der Betreff trägt den Namen der Installation — **auch die Testmail**; sie
  war bis Web 20.7.0 die einzige ohne.
- Der Link in Einladung und Reset-Mail ist **einfach** geschrägstrichelt
  (`https://host/pw_handling.php?token=…`), nicht `host//pw_handling.php`.
  Zur Gegenprobe vorher in der `config.php` einen Schrägstrich an `base_url`
  anhängen — der Link muss trotzdem stimmen.
- **Woran ein Scheitern zu erkennen ist:** eine fremde oder fehlende
  Kontaktzeile (dann greift `instanz_kontakt()` nicht), ein „Gen-EM" im
  Betreff einer Installation, die anders heißt (dann greift `instanz_name()`
  nicht), oder ein doppelter Schrägstrich im Link (dann ist irgendwo wieder
  von Hand verkettet worden).

---

### P19 — Die Warteschlange trägt über eine echte Störung

**Wofür:** Die Leiter ist lokal in Sekunden gemessen, nicht über 24 Stunden,
und gegen eine Gegenstelle, die auf Kommando ablehnt — nicht gegen einen
Mailserver, der wirklich aus ist.

**Weg:**

1. In der `config.php` den SMTP-Host auf einen unerreichbaren Namen setzen.
2. Eine **Einladung** verschicken.
3. Betrieb → Status ansehen.
4. Den SMTP-Host zurückstellen.
5. `php server/jobs.php` von Hand aufrufen (oder warten, bis der Cron läuft).

**Erwartet:**

- Nach 2: Die Einladung wird **trotzdem angelegt**, die Schwellenmarke steht,
  der Link ist **nicht** im Klartext auf der Seite gelandet.
- Nach 3: Die Zeile „Warteschlange" steht **orange** auf „1 wartet".
- Nach 5: Sie steht auf **blau/leer**, und die Mail ist da.
- **Woran ein Scheitern zu erkennen ist:** Bleibt die Zeile nach Schritt 5
  orange, hat der Job nicht gegriffen — dann steht in `job_laeufe` nichts
  Neues zu `mail`, und die Ursache ist entweder die Sperre (`laeuft_seit` in
  `jobs`) oder das Budget. Steht sie auf **rot** mit „unzustellbar", war die
  Störung länger als 24 Stunden; das ist dann richtig und kein Fehler.

---

### P20 — Der Name und die Adressen auf einer fremden Installation

**Wofür:** Der ganze Sinn von E-P5a-35 und E-P5a-40 — dass eine fremde
Betreiberin nicht mit „Gen-EM" unterschreibt.

**Weg:** Auf einer Installation, die **nicht** Gen-EM ist, Name, Kurzname und
beide Adressen eintragen. Dann: Browsertab, Kopfleiste, Anmeldeseite,
Wartungsseite (einschalten!), Schlüsselblatt und **eine Mail** ansehen.

**Erwartet:** Nirgends „Gen-EM" — **außer** in der Fußzeile „© Gen-EM · Open
Source" und im `creator` einer exportierten GPX-Datei. Beides ist die
**Urheberschaft der Software** und bleibt mit Absicht stehen (Konzept 2.2,
`docs/Export-Format.md` 3.5).

**Woran ein Scheitern zu erkennen ist:** Zeigt die **Wartungsseite** den alten
Namen, steht er nicht in `wartung.lock` — `wartung_daten()` ist eine weiße
Liste, und ein Schlüssel, den sie nicht kennt, wird beim Lesen still
verschluckt.

---

### P21 — Ein freigegebenes Backup abholen

**Wofür:** N18 — der Rohausgabe-Zweig von `api/adminbackup_freigabe.php` ist
gelesen, nicht gelaufen.

**Weg:** Als Administration ein Konto-Backup freigeben (Verwaltung →
NutzerInnen → Konto → Backup freigeben). Als die berechtigte Person die
Freigabe abholen und dabei die Netzwerkansicht des Browsers offen haben.

**Erwartet:** Die Antworten auf `api/adminbackup_freigabe.php` tragen
`Cache-Control: no-store`, `X-Content-Type-Options: nosniff` und
`Content-Type: application/json; charset=utf-8` — **auch die mit `?teil=…`**,
die den Klartext eines Pakets durchreicht. Das Einspielen gelingt wie bisher.

**Woran ein Scheitern zu erkennen ist:** Fehlt `no-store`, ist die Umstellung
an dieser einen Stelle nicht angekommen — die Antwort enthält dann den
entschlüsselten Inhalt eines fremden Kontos in einem Zwischenspeicher.

---

### P22 — Der Ratenschutz auf einer Installation mit echtem Verkehr

**Wofür:** N19 — die Rechnung zur Prozessbelegung ist gerechnet, nicht
gemessen.

**Weg:** Nach dem Ausrollen eine Woche laufen lassen. Dann Betrieb → Status
ansehen und Betrieb → Servereinstellungen, Karte „Ratenschutz".

**Erwartet:**

- Die Zeile **„Gesperrt"** steht meistens gar nicht da. Eine Sperre ist ein
  Ereignis, kein Zustand.
- Steht sie da, ist die höchste Stufe **1**. Stufe 4 heißt: Jemand hat
  viermal an einem Tag zehn Fehlversuche gemacht — das ist kein Vertippen.
- Die Zeile **„Verlangsamung"** steht **nie** da. 200 Fehlversuche je 15
  Minuten erreicht ein normaler Dienstbetrieb nicht.

**Woran ein Scheitern zu erkennen ist:**

- Steht „Verlangsamung" im Alltag da, ist die Schwelle für diese Installation
  zu niedrig — **nicht** die Bremse abschalten, sondern die Schwelle
  hochsetzen und *nachsehen, woher die Fehlversuche kommen*.
- Melden sich Kolleginnen, die „nichts falsch gemacht" haben und trotzdem
  gesperrt sind, greift die **Adress**-Schwelle (50 je 15 min). Das ist der
  Klinik-NAT-Fall: Zahl erhöhen.
- Hält eine Sperre **länger als 60 Minuten**, stimmt etwas nicht — die Leiter
  endet bei der letzten Sprosse. Dann steht in `rate_limits` eine Zeile mit
  einem `gesperrt_bis`, das niemand gesetzt hat.

---

### P23 — Die Sammelmail kommt einmal, nicht hundertmal

**Wofür:** Die Stundenregel ist lokal gemessen; auf einer Installation unter
echtem Beschuss läuft sie nebenläufig.

**Weg:** Betriebsadresse eintragen (Verwaltung → Installation), dann von einem
zweiten Gerät aus zehn Fehlversuche mit demselben Namen machen, viermal
hintereinander (dazwischen die Sperre ablaufen lassen).

**Erwartet:** Genau **eine** Mail, Betreff „Auffällige Anmeldeversuche".
Weitere Anlässe in derselben Stunde erzeugen keine zweite.

**Woran ein Scheitern zu erkennen ist:** Zwei oder mehr Mails in einer Stunde
— dann greift die Marke nicht (sie steht in `app_state` unter
`ratenschutz_mail_last`). Gar keine Mail: erst prüfen, ob unter Verwaltung →
Installation eine Betreiberadresse steht und ob der Schalter „Bei der höchsten
Stufe melden" an ist; danach Betrieb → Status, Zeile „Warteschlange".

### P24 — Eine echte Uhr mit veraltetem Schlüssel

**Wofür:** N23 und N24 — dass beide Clients die `429` richtig behandeln, ist
am Quelltext belegt, nicht an einem laufenden Gerät. Und ob die Adressgrenze
hinter einem Mobilfunk-NAT trägt, sagt nur der Betrieb.

**Weg:**

1. Ein gekoppeltes Gerät einen Dienst aufzeichnen lassen, **ohne** dass es
   hochlädt (Flugmodus genügt) — so entsteht ein Rückstand in seiner
   Warteschlange.
2. Im Web **Verwaltung → Konto → Geräte** das Gerät entkoppeln und **neu**
   koppeln. Das alte Gerät trägt jetzt einen veralteten Schlüssel.
3. Das alte Gerät online nehmen und seinen Rückstand senden lassen.

**Erwartet:**

- Das Gerät **verliert nichts**. Sein Rückstand bleibt vollständig —
  Punktzahl vorher gleich Punktzahl nachher.
- Die Uhr zeigt eine Störung an und hört auf zu senden; die Android-App
  ebenso. **Kein** Paket wird als „fehlerhaft" markiert.
- In **Einstellungen → Geräte** trägt das alte Gerät eine orange Plakette
  **„abgewiesen"** und in der Kleinzeile eine Zahl mit Zeitpunkt.
- In **Betrieb → Status** steht die Zeile **„Abgewiesene Geräte"** (orange)
  mit derselben Zahl.
- Das **neu gekoppelte** Gerät lädt die ganze Zeit ungestört weiter hoch —
  auch wenn beide im selben WLAN hängen.

**Woran ein Scheitern zu erkennen ist:**

- **Das alte Gerät verwirft Pakete** oder markiert sie als fehlerhaft: Dann
  behandelt es die `429` wie eine `400`. Das wäre ein Fehler im Client, nicht
  im Server — nachzusehen in `Uploader.mc` (`onResponse`) beziehungsweise
  `Sendeantwort.lese()`.
- **Das neue Gerät kommt nicht mehr durch**: Dann zählen Fehlversuche mit
  *bekannter* Kennung in den Adresstopf, und das darf nicht sein (E-P5a-47).
  In `rate_limits` nachsehen: Steht dort eine Zeile mit `topf = 'ingest_ip'`,
  obwohl nur bekannte Kennungen gesendet haben, sitzt der Fehler in
  `ingest.php` an den beiden `401`-Zweigen.
- **Der Vermerk bleibt stehen**, nachdem das Gerät wieder hochlädt: Dann
  greift die Rücksetzung hinter der Schlüsselprüfung nicht — sichtbar daran,
  dass `abgewiesen_anzahl` in `devices` größer als 0 bleibt, obwohl
  `last_seen` frisch ist.
- **Mehrere Geräte im selben Netz werden gemeinsam gesperrt**, ohne dass
  jemand erfundene Kennungen sendet: Dann ist die Aufteilung der Töpfe
  verletzt. Das ist der einzige Fall, in dem die Adressgrenze von 30 zu
  niedrig wäre — und dann ist nicht die Zahl das Problem, sondern die
  Zuordnung.

---

### P25 — Ein Ereignis, das nach 30 Tagen verschwindet

**Wofür:** N26 — gemessen ist die Regel, nicht die Uhr.

**Weg:** Nach dem Ausrollen einen Monat vergehen lassen. Dann **Betrieb →
Status → Sicherheit** öffnen, Karte „Ereignisse der letzten 30 Tage“.

**Erwartet:** Das älteste Ereignis ist höchstens 30 Tage alt. Steht in der
Karte eine Gesamtzahl („die jüngsten 200 von …“), ist auch die um die
verfallenen Zeilen kleiner geworden.

**Woran ein Scheitern zu erkennen ist:**

- Ein Ereignis mit einem Datum **älter als 30 Tage**: Dann läuft der Schritt
  `Sperrereignisse` im Aufräumjob nicht. Nachsehen unter **Betrieb →
  Hintergrundjobs**, ob `aufraeumen` überhaupt läuft — und ob er einen Fehler
  meldet.
- Ein Gerät trägt auf **Einstellungen → Geräte** seit über 30 Tagen die
  orange Plakette „abgewiesen“, obwohl es längst ausgemustert ist: Dann
  greift der Schritt `Geraetevermerke` nicht. Das ist der Fall, den AP8
  behoben hat — er fällt sonst niemandem auf, weil die Statuszeile einfach
  dauerhaft orange steht.
- **Umgekehrt** auffällig: eine Karte, die plötzlich **leer** ist, obwohl es
  Sperren gab. Dann löscht der Job zu viel — die Frist steht als
  `INTERVAL 30 DAY` in `job_aufraeumen()` und ist **keine** Einstellung; wer
  dort etwas anderes findet, hat den Fehler.

---

### P26 — Der Knopf „Aufheben“ an einer echten Sperre

**Wofür:** Die Klickprobe fährt gegen eine **selbst angelegte** Sperre auf ein
erfundenes Merkmal. Dass der Weg auch dann stimmt, wenn eine echte Kollegin
sich ausgesperrt hat, sagt nur der Ernstfall.

**Weg:** Eine zweite Person tippt ihr Passwort zehnmal falsch. Dann **Betrieb
→ Status → Sicherheit** öffnen, ihre Zeile suchen, **Aufheben** drücken,
bestätigen — und sie bitten, sich sofort anzumelden.

**Erwartet:** Die Anmeldung gelingt **ohne Wartezeit**. Die Zeile ist fort, und
in „Ereignisse der letzten 30 Tage“ steht ein Eintrag „aufgehoben“ **mit
deinem Namen**.

**Woran ein Scheitern zu erkennen ist:**

- Sie ist **weiterhin gesperrt**: Dann greift eine zweite Sperre, die die
  Karte auch zeigt — die auf ihren **Anschluss** (Topf `login_ip`). Beide
  müssen fallen. Zeigt die Karte nur eine, sitzt der Fehler in
  `rate_sperren_aktiv()`.
- Das Ereignis steht ohne Namen da (`durch —`): Dann wird `$wer` nicht
  übergeben, und das Protokoll sagt nicht, wer gehandelt hat — also genau
  das nicht, wofür die Spalte da ist.
- **Deine eigene** Anmeldung hängt danach: Dann ist statt
  `rate_sperre_aufheben()` irgendwo `rate_erfolg()` gelandet, und der Knopf
  hat die Zeile der handelnden Person gelöscht statt der gemeinten.

---

### P27 — Die Verbindungsgrenze auf der echten Anlage (AP9)

**Wofür:** `tools/verbindungsprobe/` stellt **1226** her — die GRANT-Grenze am
Datenbankkonto. Auf der Zielanlage kann dieselbe Sache aus **1040** oder
**1203** kommen, und davor steht bei einem Apache mit PHP-FPM zusätzlich eine
**Prozessgrenze**, die die Anwendung gar nicht sieht. Gemessen ist der
eingebaute PHP-Server, nicht der Webspace.

**Weg:** Auf Staging (oder Produktiv, außerhalb der Dienstzeit) **Betrieb →
Status → Plattform** öffnen und den Wert notieren, den der Hoster für
gleichzeitige Datenbankverbindungen setzt; dann mehrere Uploads gleichzeitig
auslösen — zwei Uhren und ein Handy gleichzeitig synchronisieren lassen, oder
ein Backup ziehen, während jemand die Suche benutzt.

**Erwartet:** Entweder passiert nichts (die Grenze ist weit genug), oder es
kommt eine Seite **„Ausgelastet"** bzw. `{"error":"ausgelastet"}` — **nie**
eine 500 und **nie** ein Ausnahmetext mit Hostnamen. Die Zeile
**Verbindungen** auf der Statusseite zählt mit.

**Woran ein Scheitern zu erkennen ist:**

- Es kommt eine **500** mit `SQLSTATE[…]`: Dann trägt der Hoster eine vierte
  Fehlernummer, die `UEBERLAST_CODES` nicht kennt. Die Nummer steht in der
  Meldung — sie gehört in die Liste, und der Fund gehört ins Backlog.
- Die Zeile **Verbindungen** sagt „**Nicht gezählt**": Dann ist
  `server/ueberlast.json` nicht schreibbar. Das ist kein Schönheitsfehler —
  ohne die Datei gibt es **keine** Zahl, und die Zeile sagt das deshalb
  ausdrücklich, statt eine Null zu zeigen. Abhilfe: Schreibrechte auf
  `server/` prüfen.
- Die Uhr meldet einen Upload als **endgültig gescheitert**: Dann behandelt
  sie das 503 nicht als 5xx. Das wäre neu — der JSON-Vertrag verlangt seit
  jeher Backoff und unveränderte Wiederholung, und S4 hat es gemessen.

### P28 — Der Deadlock unter echter Gleichzeitigkeit (AP9, Nr. 210)

**Wofür:** Der Fund von AP9 stammt aus **zwanzig Uploads desselben Geräts auf
denselben Diensttag**. Das ist im Betrieb selten; häufiger ist ein Handy und
eine Uhr am selben Tag, oder eine Nachlieferung, die neben einem laufenden
Upload landet. Wie oft es dann wirklich passiert, sagt nur der Betrieb.

**Weg:** Über einige Dienste hinweg gelegentlich ins **Fehlerprotokoll des
Webspace** sehen und nach der Zeichenfolge `Gedraengel` suchen.

**Erwartet:** Einzelne Zeilen sind harmlos — das Gerät wiederholt und der
Upload kommt an. Häufen sich **dieselbe Datei und Zeile**, ist dort ein
Engpass, und Backlog Nr. 210 (Transaktion wiederholen) wird dringend.

**Woran ein Scheitern zu erkennen ist:** Ein Einsatz **fehlt** im Web, obwohl
die Uhr ihn als gesendet führt. Dann hat eine Wiederholung nicht
stattgefunden — das wäre etwas anderes als das Gedrängel selbst und gehört
sofort gemeldet.

### P29 — `post_max_size` der Zielanlage (Backlog Nr. 37, offen)

**Wofür:** Die einzige der drei Messungen aus Nr. 37, die AP9 **nicht**
liefern konnte. Sie entscheidet, ab wie vielen Einsätzen eine
Wiederherstellung scheitert — der Browser entsiegelt die `.edbak` und schickt
rohes, unkomprimiertes JSON per POST.

**Weg:** Sobald Staging steht: **Betrieb → Status → Plattform** öffnen. Die
Karte nennt `post_max_size` und `upload_max_filesize` mit Sollwert (Z3: 2 MB)
und Ist-Wert.

**Erwartet:** Eine Zahl. Sie gehört in **Backlog Nr. 37** eingetragen, und
zwar mit der Angabe, von welcher Anlage sie stammt.

**Woran ein Scheitern zu erkennen ist:** Steht dort weniger als **8 MB**,
passen rund 280 Einsätze in eine Datei (28 KB Nutzlast je Einsatz, gemessen) —
dann braucht ein Bestand von 5000 Einsätzen mindestens 18 Dateien, und das
gehört ins Handbuch, bevor es jemand im Ernstfall herausfindet.

---

### P30 — Die Löschregel gegen das echte Ziel (AP10)

**Wofür:** Gemessen ist sie gegen einen Nachbau auf Loopback (N31). Ein echtes
Ziel hat eine langsame Leitung, eigene Rechteregeln und womöglich einen
zweiten Benutzer. Und es ist der einzige Ort, an dem ein Fehler wehtut:
**Hier wird auf einer fremden Maschine gelöscht.**

**Weg:** Zuerst **nur nachsehen** — Menü einer Zielzeile, „Nachsehen, was dort
liegt". Die Zahlen mit dem vergleichen, was dort wirklich liegt (per FTP-
Programm oder SSH). **Erst wenn sie stimmen**, die Regel einschalten und
großzügig setzen (etwa 12 je Konto, 24 Komplett-Stände), dann „Jetzt
versenden".

**Erwartet:** Der Erfolgssatz nennt, was entfernt wurde. Unter Betrieb →
Status → Sicherheit steht jede Löschung mit Datei und Grund. Am Ziel liegen
danach **genau** so viele eigene Sicherungen wie eingestellt — und **alles
Fremde unverändert**.

**Woran ein Scheitern zu erkennen ist:**

- **Am Ziel fehlt etwas, das nicht von hier stammt.** Dann hat die
  Herkunftsprobe versagt, und das ist der schwerste Fall des ganzen Pakets.
  Sofort die Regel abschalten (Haken weg), und den Fund melden — die
  Protokollkarte sagt, welche Dateien es traf.
- **Es wird gar nichts entfernt, obwohl mehr dort liegt als eingestellt.**
  Zwei harmlose Gründe: Die Migration ist nicht gelaufen (dann sagt es der
  Lauf), oder die Dateien stammen aus der Zeit vor dem Versandprotokoll und
  liegen hier nicht mehr — die werden nie angefasst (N32). Ein dritter wäre
  ein Fehler: Der eigene Versand ist gescheitert; dann steht das am Ziel.
- **Bei jedem Lauf werden dieselben Dateien gesendet und wieder entfernt.**
  Dann greift E-P5a-57 nicht. Erkennbar an der Zeile „N Sicherungen gingen
  nicht erneut hinaus", die dann fehlt, und an einer Löschliste, die jeden Tag
  dieselben Namen zeigt.

### P31 — Ein Ziel, das zwei Installationen benutzen (AP10)

**Wofür:** Die zweite Sicherung (Versandprotokoll) ist genau dafür da: Eine
**zweite** Installation schreibt Dateien mit demselben Namensmuster — und die
sind für diese hier so fremd wie ein Urlaubsfoto. Der Nachbau misst das mit
einer erfundenen Datei; zwei echte Installationen sind etwas anderes.

**Weg:** Falls zutreffend: auf beiden Installationen „Nachsehen" fahren und
die Zahlen vergleichen. Die Summe der „eigenen" beider Seiten plus die
„fremden" muss aufgehen.

**Erwartet:** Was die eine als eigen zählt, zählt die andere als fremd.

**Woran ein Scheitern zu erkennen ist:** Beide zählen dieselbe Datei als
eigen. Dann steht sie in beiden Versandprotokollen — möglich nur, wenn eine
Installation aus einer Sicherung der anderen entstanden ist. **Dann darf die
Regel auf keiner von beiden an sein**, bis die Protokolle getrennt sind.

### P32 — Der Nachlöse-Job auf der echten Installation (AP11)

**Wofür:** N34 und N35 — gemessen ist die Mechanik an fünf erfundenen
Gerätezeilen mit einer fünfzeiligen Modelltabelle. Auf der echten Installation
stehen die 325 ausgelieferten Teilenummern und ein gewachsener Gerätebestand.

**Weg:** Nach dem Update Betrieb → Status öffnen und die Zeile
**„Gerätemodelle"** in der Karte „Server" ansehen. Sie steht zuerst auf
**„ungeprüft"**. Den täglichen Lauf abwarten (oder `jobs.php?aktion=lauf` mit
dem Token anstoßen), dann dieselbe Zeile noch einmal lesen und anschließend
unter Geräte die Liste durchsehen.

**Erwartet:** Die Zeile wechselt auf **grün** mit Zeitpunkt und zwei Zahlen
(„N nachgezogen, M unbekannt"). In der Geräteliste tragen vorher namenlose
Garmin-Geräte jetzt ihren Modellnamen; **Handys und fremde Geräte sind
unverändert**. Steht dort ein zweistelliger oder größerer Bestand, läuft der
Job möglicherweise über **mehrere Tage** — dann steht die Zeile so lange auf
„steht aus", und das ist richtig so.

**Woran ein Scheitern zu erkennen ist:**

- **Ein falscher Modellname** an einer Teilenummer. Dann ist der Eintrag in
  `GERAETE_MODELLE` falsch, nicht der Job (N34). Korrektur: Tabelleneintrag
  richtigstellen — der geänderte Fingerabdruck sorgt von selbst dafür, dass
  der nächste Lauf die Zeilen noch einmal anfasst.
- **Ein Handy hat einen Garmin-Modellnamen bekommen** oder eine Rohangabe ist
  verschwunden. Das wäre ein Fehler des Jobs und nicht der Tabelle; er ist in
  der Probe ausdrücklich ausgeschlossen (C und D bleiben Zeichen für Zeichen
  gleich), also melden.
- **Die Zeile bleibt auf „steht aus", und die Zahl bewegt sich nicht.** Dann
  kommt der Job nicht durch: entweder läuft der tägliche Lauf gar nicht (das
  sähe man auch an den anderen Jobs) oder das Zeitbudget reicht je Lauf nur
  für einen Block — dann `jobs.php?aktion=lauf` mehrmals anstoßen und
  zusehen, ob die Zahl wächst (N35).

### P33 — Die Punktdatei-Sperre auf der echten Anlage (Nr. 213, Web 20.15.1)

**Wofür:** N38 — von hier aus ist weder `.htaccess` noch der FTP-Käfig
messbar. Und dies ist der einzige Prüfpunkt der Liste, bei dem ein Fehler
**das Zertifikat der Anlage kostet**.

**Weg, in dieser Reihenfolge:**

1. **Zuerst nachsehen, was heute dasteht.** Im Browser
   `https://nadoku.gen-em.org/.ftp-deploy-sync-state.json` aufrufen, **bevor**
   ausgeliefert wird. Kommt JSON, ist der Befund bestätigt; kommt 403 oder
   404, notieren welches — das ist der Ausgangswert.
2. Ausliefern (Tag). Der Lauf bricht ab, **wenn `../` im Käfig nicht erlaubt
   ist** — dann Variable `FTP_STATE_PFAD` auf einen Pfad **innerhalb** des
   Zielverzeichnisses setzen (z. B. `.deploy-state-produktion.json`) und
   erneut laufen lassen. Die `.htaccess` sperrt ihn dort.
3. Dieselbe Adresse noch einmal aufrufen. **Erwartet: 403.**
4. **`https://nadoku.gen-em.org/.well-known/acme-challenge/pruefung` aufrufen.
   Erwartet: 404 — und ausdrücklich NICHT 403.**
5. **Die alte Datei von Hand per FTP löschen.** Sie liegt weiter im Webroot;
   die Sperre verbirgt sie nur.

**Woran ein Scheitern zu erkennen ist:**

- **Schritt 3 gibt 200 und JSON.** Dann liest dieser Webserver die
  `.htaccess` nicht (nginx? LiteSpeed?) oder `mod_rewrite` fehlt. Dann hilft
  nur Schritt 2 — die Datei muss physisch aus dem Webroot.
- **Schritt 4 gibt 403.** **Das ist der teure Fall.** Die Sperre ist zu breit,
  die Zertifikatserneuerung ist tot, und zwar lautlos: Es fällt erst auf,
  wenn das Zertifikat in bis zu 90 Tagen abläuft und der Browser warnt. Sofort
  die Ausnahme `(?!well-known/)` in `server/.htaccess` prüfen. Der
  Stufe-2-Schritt der Kette fängt genau das ab — aber nur, wenn Staging
  steht.
- **Schritt 3 gibt 404 statt 403.** Kein Beweis, sondern eine Nichtmessung:
  404 heißt „dort liegt nichts" und käme auch von einer leeren Adresse.
  Genau daran ist die erste Abfrage zu diesem Befund gescheitert. Dann
  zusätzlich einen Pfad abfragen, den es sicher nicht gibt
  (`/.pruefung-213`): **403** heißt, die Regel greift; 404 heißt, sie greift
  nicht.

---

---

## 4. Zuarbeiten, ohne die Punkte offen bleiben

Aus dem Rahmenplan, Abschnitt 6 — hier nur, was P1 bis P8 blockiert:

1. GitHub-Umgebungen `staging` und `produktion` anlegen; die drei
   FTP-Geheimnisse je Umgebung, `JOBS_TOKEN` nur bei `produktion`.
2. **Pflichtfreigabe** („required reviewers") an der Umgebung `produktion`.
3. Zweigschutz auf `main` mit `pruefung` als **Pflichtprüfung** — ohne ihn ist
   Stufe 1 eine Auskunft und keine Schranke.
4. Variablen: `FTP_ZIELPFAD` je Umgebung, `STAGING_URL`, `PRODUKTION_URL`.
5. Staging-Installation samt Prüfkonto (`STAGING_KONTO`, `STAGING_PASS`).
6. `CIQ_GERAETE_URL` als Repositoriums-Secret.

---

## 5. Grenzen der benutzten Prüfmittel

### 5d. `tools/verbindungsprobe/` (seit AP9)

- **Sie misst 1226, nicht 1040 und nicht 1203.** Die serverweite Grenze lässt
  sich auf einer Maschine, auf der noch etwas anderes läuft, nicht gefahrlos
  herstellen; die Systemvariable hinter 1203 lässt sich in MariaDB nicht zur
  Laufzeit setzen, wenn der Server mit `--max-user-connections=0` gestartet
  ist. Belegt ist für alle drei, dass `ueberlast_erkannt()` sie an `getCode()`,
  `errorInfo[1]` **und** an der Meldung findet — 1040 und 1226 wurden am
  16.09.2026 einzeln herbeigeführt und ihre Ausnahme untersucht.
- **Sie misst den eingebauten PHP-Server.** Ein Apache mit PHP-FPM hält eigene
  Prozessgrenzen, und die liegen möglicherweise **vor** der Datenbankgrenze.
  Siehe P27.
- **Sie misst nicht unter Z2-Last.** Das Konzept nennt „gegen Z2-Last" (500
  Konten à 600 Einsätze). Das sind 300 000 Einsätze und ein Tag Rechenzeit;
  diese Probe misst das **Verhalten an der Grenze**, nicht das Verhalten unter
  Bestandsgröße.
- **Der Zweig „nicht schreibbar" ist nicht über HTTP gemessen.** Der
  Prüfserver läuft als `root`, und für `root` ist jedes Verzeichnis
  schreibbar. Belegt ist der Zweig am **Funktionsaufruf** mit einem
  unprivilegierten Benutzer (`schreibbar=false`).
- **Sie ändert eine Berechtigung in der Datenbank.** Der Ausgangswert wird im
  `finally` zurückgeschrieben; scheitert das, sagt sie es auf STDERR mit Konto
  und Host. Auf einer Installation mit Betrieb hat sie nichts zu suchen.

### 5e. `tools/geraeteprobe/` Teil 2 (seit AP11)

- **Sie misst mit einer eigenen Modelltabelle**, nicht mit den 325
  ausgelieferten Teilenummern. Das ist Absicht: Die Frage von AP11 lautet
  „kommt eine **neue** Tabelle nur bei den Zeilen an, die sie neu kennt?",
  und dafür braucht es zwei Tabellen in **einem** Lauf. Was sie damit nicht
  sagt: ob die ausgelieferte Tabelle richtig ist (N34).
- **Sie schreibt in die Datenbank** — fünf Geräte, ein Konto, ein Aufräumen
  im `finally`. Auf einer Installation mit Betrieb hat sie nichts zu suchen;
  sie läuft gegen die lokale Installation.
- **Sie misst fünf Zeilen, kein Zeitbudget.** Die Fortsetzung ist mit
  Blockgröße 1 belegt (`geprueft 1`, `fertig false`, Marke wandert), die
  Dauer eines Blocks über Tausende Zeilen nicht (N35).
- **Sie misst die Bibliothek, nicht den täglichen Lauf.** Dass der Job im
  Katalog an der richtigen Stelle steht und vom Huckepack-Weg aufgerufen
  wird, ist an `php server/jobs.php` abgelesen, nicht an einem Cron-Aufruf
  der echten Anlage.

### 5c. `tools/ratenprobe/` (seit AP6)

- **Sie misst die Bibliothek, nicht den Weg durch `login.php`.** Ob die drei
  Zählungen an der richtigen Stelle stehen, sagt nur der Browser.
- **Die Uhr ist gestellt.** `stufe_bis` wird zurückdatiert statt gewartet.
  Gemessen ist damit die Regel, nicht der Zeitablauf.
- **Sie leert den Topf `global` vollständig**, nicht nur eigene Zeilen — er
  hat nur eine. Auf einer Installation unter Beobachtung geht damit der
  laufende Zählerstand der Verlangsamung verloren.
- **Keine Gleichzeitigkeit.** Siehe N20.
- **Eine Falle, die sie sich selbst gestellt hat:** `rate_verlangsamung()`
  merkt sich ihre Antwort je Anfrage — im Betrieb richtig. Der erste Lauf
  meldete deshalb „8,00 s" für Stufe 1, weil der Merker noch die Stufe 4 aus
  der Schleife darüber trug. Die Zeitmessung läuft seither in einem eigenen
  PHP-Prozess.

### 5b. `tools/sitzungshaertung/` (seit AP4a)

- **Sie misst, dass die Zeile dasteht — nicht, dass sie wirkt.** `ini_set()`
  kann scheitern (`session.*` lässt sich nach `session_start()` nicht mehr
  setzen, und manche Hoster sperren einzelne Direktiven). Das misst nur eine
  laufende Installation; der Befehl steht in der dortigen `LIESMICH.md`.
- **Sie sieht nicht, ob die Zeile erreicht wird.** Steht sie in einem `if`,
  das nie zutrifft, zählt sie trotzdem.
- **`vendor/` ist ausgenommen.** `phpseclib3/Crypt/Random.php` startet eine
  eigene Sitzung und wird nicht von uns gepflegt.
- **Der Abstand ist eine Annahme**: bis zu zwölf Zeilen vor dem Aufruf.
  Dazwischen liegt in der Anwendung regelmäßig ein Kommentarblock und
  `session_set_cookie_params()`. Wer mehr dazwischenschreibt, bekommt einen
  Befund, der keiner ist — dann ist die Konstante `ABSTAND` zu erhöhen und
  **nicht** die Prüfung abzuschalten.

### 5a. `tools/mailprobe/` (seit AP5)

- **Sie misst den Code, nicht das Netz.** Alles läuft über Loopback; die
  gemessenen Fristen (5,00 s / 5,01 s / 3,01 s) sind die **obere Schranke des
  Codes**, nicht die eines Netzes.
- **Sie tauscht `server/config.php` aus** und stellt sie wieder her — beim
  regulären Ende, bei einer Ausnahme und bei einem `exit`. **Nicht** bei
  `kill -9` oder einem wegbrechenden Behälter; dann liegt die Sicherung als
  `server/config.php.mailprobe` daneben. Liegt sie da, ist die Probe nicht
  sauber zu Ende gekommen.
- **Ihre Gegenstelle ist ein Nachbau.** Sie spricht SMTP genau genug für
  diese Anwendung, nicht für jeden Mailserver der Welt. Was sie **nicht**
  nachstellt: STARTTLS (die Anwendung benutzt implizites TLS auf 465),
  Größenbeschränkungen, Greylisting, DKIM/SPF.
  > Dass „genau genug" nicht selbstverständlich ist, hat sie selbst gezeigt:
  > Die erste Fassung beantwortete den Dreischritt `AUTH LOGIN`
  > (334 → 334 → **235**) dreimal mit 334. `smtp_send()` brach ab, **bevor**
  > es je ein `RCPT TO` schickte — die Betriebsart `ablehnen` war von `ok`
  > nicht zu unterscheiden, und die Probe meldete acht Befunde, von denen
  > keiner die Anwendung betraf.



- **`tools/migrationsregister/`** sieht keine DDL, die aus eingesetzten Namen
  gebaut wird (`ALTER TABLE \`$tab\` …`). Vier Spalten fallen heute genau so;
  sie stehen mit Begründung in `ausnahmen.json`. Eine Migration, die ihre
  ganze DDL so baut, ist für das Werkzeug unsichtbar.
- **`tools/kette/tor.py --selbstprobe`** misst die Entscheidungslogik, nicht
  das Netz: Ob die Gegenstelle wirklich der Produktivserver ist, ob das
  Backup lesbar ist und ob der Serverschlüssel der richtige ist, prüft sie
  nicht (das erste ist Sache der Umgebung, das zweite und dritte Sache von
  `tools/wiederherstellungs-probe/`).
- **Die YAML-Prüfung** sagt, dass die Dateien gültig sind, nicht dass sie das
  Richtige tun. Ein Ausdruck in `if:` kann syntaktisch einwandfrei und
  inhaltlich falsch sein; das zeigt erst P1.
- **`tools/vollstaendigkeit/`** misst das Stylesheet gegen den Stand vor P3.
  Seine Zahl ist ein Vergleich, keine Güte.
- **`tools/installweiche/`** sieht nur Konstrukte mit eigenem Token. Benannte
  Argumente, Eigenschaftenbeförderung, ein nachgestelltes Komma in einer
  Parameterliste und `new` in Initialisierern sieht sie **nicht** — sie druckt
  diese vier bei jedem Lauf mit aus, damit die Grenze nicht nur in der
  Dokumentation steht.
- **Der Torwächter misst nur bei angemeldeten Anfragen.** `ingest.php` und
  `pair.php` laden `auth_guard.php` nicht; bis zur ersten Anmeldung nach einem
  Update bekommen die Geräte 500 statt 503. Verloren geht nichts, und mit der
  Auslieferungskette ist das Fenster null — aber es ist da, und die
  Wartungsprobe misst es nicht (sie meldet sich an).
- **Ein Muster, das HTML liest, liest kein PHP.** `<script…[^>]*>` endet am
  `>` eines PHP-Schlusses `?>` — und `<script<?= kopf_nonce_attr() ?>>` ist
  die Schreibweise dieses Projekts. Zwei Werkzeuge sind darüber gestolpert
  (Integritätswache, Wartungsprobe 12a); beide lesen den Tag-Rumpf jetzt als
  `(?:<\?(?:php\b|=).*?\?>|[^>])*`. Wer ein drittes Werkzeug schreibt, das
  Markup aus **Quelldateien** liest, braucht dasselbe.

- **`tools/cspprobe/pruefen.php`** liest nur PHP. Markup, das zur Laufzeit in
  `assets/*.js` entsteht, sieht sie nicht — ein per `innerHTML` eingesetztes
  `<script>` führt der Browser allerdings ohnehin nicht aus. Und sie sieht
  **nicht, ob der Nonce wirkt**: Dass `kopf_nonce_attr()` dasteht, heißt
  nicht, dass `kopfzeilen_seite()` vorher lief. Dafür ist `browserprobe.mjs`
  da. Ihre Liste der Ereignis-Attribute (28 Stück) ist nicht abschließend.
- **`tools/cspprobe/browserprobe.mjs`** misst eine Installation **ohne
  HTTPS** (TLS-Weiterleiter davor). HSTS, der HTTPS-Zwang und der Weg über
  `report-to` stehen dort deshalb gar nicht erst in der Richtlinie und sind
  ungemessen (N10, N11). Und sie misst **Chromium**; dass Firefox und WebKit
  dieselbe Richtlinie gleich deuten, ist angenommen, nicht gemessen — bei
  `report-uri` gegen `report-to` gehen die Engines nachweislich auseinander.
- **Eine Zählung im eigenen Quelltext misst nicht, was der Browser tut.**
  `img-src data:` wurde gestrichen, weil `grep` über `server/` und
  `assets/style.css` **0 Treffer** ergab; der Browser meldete danach **140
  Verstöße**, weil die Quelle in einer **minifizierten Bibliothek** steht
  (`leaflet.js`). Wer künftig eine Richtlinienzeile mit einer Zählung
  begründet, zählt in `assets/vendor/` mit — oder verlässt sich auf die
  Report-Only-Phase und nicht auf die Zählung.
- **`plattform_pruefen()`** misst, was PHP messen kann. Der freie Plattenplatz
  ist auf geteiltem Webspace die Zahl des Hosts und nicht das Kontingent; die
  Zeile sagt es dazu und die Prüfung meldet `null` statt einer Entwarnung. Die
  Kontingente selbst sind **Angaben**, keine Messungen — wer sie falsch
  einträgt, bekommt eine falsche Warnung.
