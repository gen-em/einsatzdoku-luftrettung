# Konzept BR5 — Backlog-Runde 5, Web (Fahrplanschritt 22)

**Kürzel:** BR5. Pakete `BR5-NN`, Entscheidungen `E-BR5-NN`, Befunde
`F-BR5-NN`, Fragen `Q-BR5-NN`, Prüfpunkte `P-BR5-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/tender-bell-225ta2` · **Herkunft:**
`docs/konzepte/Vorbereitung-Backlog-Einordnung.md` 4, E-BE-02, -07, -08
(Betreiberin, 02.11.2026) · **Backlog-Spanne:** 480 bis 489 ·
**Prüfdokument:** `Pruefdokument-BR5-Backlog-Runde-5.md` (entsteht mit
BR5-10).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| BR5-01 | Transaktion und Protokoll: Nr. 354, 345, 457 | offen |
| BR5-02 | Jobs und Mail: Nr. 357, 454, 450 | offen |
| BR5-03 | Formularfelder: Nr. 355 (`post_text()`) | offen |
| BR5-04 | Zweite Passwortseite: Nr. 358 | offen |
| BR5-05 | Schlüsselpaar und Rückweg nach der Anmeldung: Nr. 392 | offen |
| BR5-06 | Meldungen und Karten: Nr. 346, 356, 456 | offen |
| BR5-07 | Backup-Formular: Nr. 430 | offen |
| BR5-08 | Geräte: Nr. 361 (mit 432), 440 | offen |
| BR5-09 | PHP 8.3.6: Nr. 383 | offen |
| BR5-10 | Abschluss | offen |

**In Arbeit:** nichts — Konzept fertig, alle Fragen entschieden (E-BR5-04 bis -18). **Es hakt:** nirgends. PR #137 ist gemergt (`b3a848c`), `main` ist aufgenommen; die Umsetzung kann mit BR5-01 beginnen. Fahrplanzeile 22 und die Kopfzeilen von 450, 454, 456, 457 sind geschrieben (F-BR5-13).

---

## 1. Auftrag

Siebzehn Web-Punkte aus dem Backlog: die dreizehn aus E-BE-02 (345, 346,
354, 355, 356, 357, 358, 361, 383, 392, 430, 432, 440) und vier aus dem
Prüfdurchgang der Arbeitsumgebung auf Staging (450, 454, 456, 457;
E-BR5-01). Nr. 432 ist eine Doppelung von 361 und wird mit ihr erledigt
(E-BE-07). Nr. 453 aus demselben Prüfdurchgang erledigt PR #137
(Web 23.6.2), Nr. 455 ist Werkzeug und ebenfalls dort.

Berührt werden `server/`, dazu `tools/zaehlung/register.php` (neue Zeilen
für 355 und 457), `tools/integritaetswache/wache.py` (358) und die
Dokumente nach CLAUDE.md 2 und 9. **Nicht berührt:** `watch/`, `android/`,
`.github/`. Ausgeliefert wird mit einem Tag `web-v…` nach der Freigabe; das
ist nicht Teil dieses Schritts (CLAUDE.md 3).

**Warum vor 13:** P7 dokumentiert das Verhalten der 1.0; die Fehler sollen
vorher weg sein, sonst beschreibt P7 den Fehler, oder P7 und BR5 fassen
dieselben Seiten an (E-BE-02).

**Nicht Teil:** Datenmodell und Migrationen. Kein Paket braucht nach
heutigem Befund eine Schemaänderung; fände die Umsetzung doch eine, ist das
ein Befund mit Rückfrage, weil dann `update.php` nach dem Deploy fällig wird
(CLAUDE.md 3).

## 2. Befund

Gelesen am 03.11.2026 auf `origin/main` `1771fbb2` (nach PR #136, Schritt
21), für 456 und 457 auf `origin/claude/new-session-p1duc7` `6091bfac`.
Alle siebzehn Punkte sind offen; was der Backlog anders sagt als der Code,
steht hier.

**F-BR5-01 — Nr. 383 ist vermutlich seit Web 23.0.3 behoben, nur nicht
ausgetragen.** Commit `ef895ed3` (Nr. 393, 21.10.2026) ersetzt in
`komp_oeffnen()` und `komp_erster_block_oeffnet()` (`komplett_lib.php`)
`filesize()` durch `fstat()`. Der Kommentar dort sagt: „rot unter PHP 8.3.6,
mit `fstat()` grün“. Das Fehlerbild von 383 passt genau: Der Siegelschub liest
die Zielgröße, der Stat-Cache behält sie, das Öffnen im selben Prozess hält
einen mittleren Block für den letzten, und die Endmarke in den Zusatzdaten
passt nicht („Block 1“). `komp_siegel_schub()` trägt keinen Hash- oder
Deflate-Zustand zwischen Anfragen, nur zwei Zahlen. OpenSSL und zlib sind es
also nicht. Nr. 393 hat 383 nie genannt. Was fehlt, ist die Messung unter
8.3.6 gegen den heutigen Stand.

**F-BR5-02 — `docs/Sandbox-Setup.md` sagt „Produktiv läuft 8.4“,
`docs/Technik.md` sagt Produktiv 8.3.33, Staging 8.4.27** (Statusseite,
02.11.2026). Eine der beiden Stellen ist falsch; BR5-09 zieht sie nach.

**F-BR5-03 — Nr. 357 nimmt an, die Klasse stehe im Reiter System; das tut
sie nicht.** `jobs_einen_lauf()` ruft
`system_melden('jobs', …, (string)$fehler)` mit einem Text, nicht mit der
Ausnahme; `system_eintrag()` setzt `klasse`, `datei` und `zeile` nur bei einer
Ausnahme. Wer den Klassennamen aus `letzter_fehler` streicht, ohne den Aufruf
umzustellen, verliert ihn ganz.

**F-BR5-04 — Nr. 355 zählt 109 Stellen; heute sind es 125, und eine davon
ist eine 500.** `(string)($_POST[` 119-mal, `(string)$_POST[` 6-mal (gemessen
03.11.2026, `grep` über `server/`, ohne `vendor/`). Dazu zwei lokale Hüllen
`$feld` (`registrieren.php`, `zweitfaktor_notweg.php`). `login.php` übergibt
`$_POST['email'] ?? ''` an `email_normalisieren(?string)` unter
`strict_types` — `email[]=x` ist ein `TypeError`, also eine 500, wie früher in
`registrieren.php`. `$_GET` hat dasselbe Muster 25-mal.

**F-BR5-05 — Betrieb → Updates kann die Fassung der Wear-OS-APK als
„Android-App“ zeigen.** `betrieb_updates.php` nimmt `apk_liste()[0]`, und die
Liste ist nach Ablagezeit sortiert. Liegt die Uhr-APK zuletzt, zeigt die
Zeile deren Fassung und Stand. Derselbe Ursprung wie Nr. 361, ein zweites
Symptom.

**F-BR5-06 — Der Weg von Nr. 440 ist durch Schritt 21 teilweise überholt.**
Der Eintrag sagt, was nach dem Löschen auf dem Gerät wartet, komme bei diesem
Konto „nie mehr an“. Seit GR-03 und GR-05 (Android 0.20.0, Uhr 4.3.0) darf
ein abgemeldetes Gerät mit Rückstand neu koppeln; bringt die neue Kopplung
denselben Kontoschlüssel, geht der Rückstand hinaus (E-BE-08, E-GR-01). Die
Aktion heißt in der Oberfläche außerdem „Entkoppeln“, nicht „Löschen“.

**F-BR5-07 — Das „keine“ aus Nr. 456 kommt aus dem PHP, nicht aus
`reaVorschau()`.** `einsatz_form.php` setzt die Karte mit
`'vorschau' => 'keine'`; `reaVorschau()` läuft nur nach `ortOeffnen()` und
nach „Reanimation hinzufügen“. Im Sperrzweig von `patLaden()` und im
`catch` von `ortOeffnen()` (Hülle nicht lesbar) bleibt der Wert aus dem PHP
stehen. Zwei falsche Zustände, nicht einer.

**F-BR5-08 — Die Begründung der Zählzeile Z37 für `patwarn` ist veraltet.**
`symbol.js` steht als erstes Skript der Immer-Liste; `zeigeUnlesbar()` wird
nur auf `index.php`, `suche.php` und `zeitraum.php` gerufen, dort nach
`ui_geruest_ende()` und erst nach dem Entschlüsseln. `patient.js` sagt das
selbst („nachgemessen 14.09.2026“). Nr. 346 hat damit kein
Ladereihenfolge-Problem.

**F-BR5-09 — Nr. 392 trifft auch den Rückweg, und für das Kontopaar gibt es
keinen Weg von Hand.** `loeseVormerkung()` räumt `edkvor` aus dem
Sitzungsspeicher, bevor das Paar angelegt ist; `rueckwegAnlegen()` vergisst
das Token nach dem ersten Versuch. Kontopaar und Rückweg laufen beide still
und ohne `await`. Der Rückweg hat den Knopf „Rückweg erneuern“, das Kontopaar
hat nichts. **Das Token braucht die Erzeugung nicht** — `paarErzeugen(ck)`
braucht nur den Inhaltsschlüssel, und der liegt in IndexedDB. Das Token ist
allein der Passwortnachweis gegenüber `api/kontopaar.php` und
`api/rueckweg_anlegen.php`, absichtlich: Eine übernommene Sitzung soll kein
eigenes Paar ablegen können, sonst bekäme sie alle künftigen Ortsdaten. Der
zweite Weg aus dem Backlog („auf der zweiten Seite nachholen“) ist ohne Token
also keiner; er ist der erste Weg in anderer Reihenfolge.

**F-BR5-10 — Ein 5xx ist nicht immer eine Absage des Empfängers (Nr. 454).**
`smtp_letzter_fehler()` liefert nur `['kennung', 'grund']`; der Code steht im
Text („Server antwortete 550 …“). Ein 535 bei `AUTH` oder ein 5xx bei
`MAIL FROM` ist ein Fehler der Einstellungen. Würde jede 5xx-Antwort sofort
`unzustellbar` setzen, verfiele bei einem falschen SMTP-Passwort die ganze
Warteschlange. Gezählt werden darf nur die Absage beim Empfänger
(`RCPT TO`) und nach `DATA`.

**F-BR5-11 — `spur_stueck_geloescht` fehlt an zwei Stellen, und kein Riegel
sieht es (Nr. 457).** In `PROTOKOLL_ARTEN` (Plakette, Textsuche) und in
`protokoll_arten_des_reiters()` (Filter). Ein Abgleich aller literalen und
der dynamischen Arten (03.11.2026) fand keine weitere Lücke.

**F-BR5-12 — Nach einem Deadlock meldet `inTransaction()` falsch (Nr. 354).**
pdo_mysql liest den Zustand vom Server; nach dem Rollback durch InnoDB ist er
`false`. Wer in `protokoll()` erst im `catch` fragt, ob eine Transaktion
lief, bekommt „nein“. Der Zustand muss **vor** dem INSERT gemerkt werden.
Betroffen sind vier Wege, alle über Hilfsfunktionen (`totp_abschalten()`,
Passwort-Reset in `pw_handling.php`, Passwortwechsel in `einstellungen.php`,
`notweg_zuruecksetzen()`); direkt im Rumpf steht `protokoll(` nirgends.
`app_state_setzen()` schluckt Fehler ebenso und läuft in Transaktionen in
`demo_lib.php` und `zweitfaktor_notweg.php`. **Vorbild für den Weg hinter den
Commit** gibt es schon: `system_melden()` stellt in einer Transaktion in eine
Warteschlange und trägt am Ende der Anfrage nach.

**F-BR5-13 — Vier Kopfzeilen liegen gerade auf einem anderen Zweig.** 450,
454, 456 und 457 tragen auf `origin/main` (456, 457: nur auf PR #137)
„gehört zu: nächste Backlog-Runde“. PR #137 ändert `Backlog.md` weiter. Wer
die Kopfzeilen jetzt auf `22` stellt, erzeugt einen Konflikt in derselben
Datei; das geschieht deshalb erst nach dem Merge von #137, als erster
Commit von BR5-01.

**F-BR5-14 — Nr. 450 hat die Ursache in der Kette, die Abhilfe am Server.**
Stufe 2 legt Konten unter `…@example.invalid` an (`umlauf_konto()` in
`tools/referenzdatensatz/vergleich/kreislauf.py`); die Adresse ist in rund
zehn Werkzeugen fest verdrahtet, und der Riegel `UMLAUF_PRAEFIX` hängt an
ihr. `mail_einreihen()` lässt `.invalid` durch, weil `filter_var()` sie für
gültig hält. Eine Domain unter `.invalid` ist nach RFC 2606/6761 nie
zustellbar, auf keiner Anlage — ein Riegel dort trifft also nichts, was
ankommen könnte. Nr. 454 löst 450 nicht: Die Absage dort ist eine 450, also
ein 4xx.

## 3. Fragen an die Betreiberin

Alle fünfzehn am 03.11.2026 entschieden, jede wie vorgeschlagen
(„alle Vorschläge ok“); die Entscheidungen stehen in Abschnitt 4.

**Q-BR5-01 — `protokoll()` in einer Transaktion (Nr. 354).** Vorschlag:
**werfen, wenn die Transaktion durch den Fehler verloren ging** — Zustand vor
dem INSERT merken, im `catch` `if ($inTx && !db()->inTransaction()) throw`.
Ein Fehler, der nur die Anweisung zurückrollt (Sperrzeit 1205), wird weiter
geschluckt wie heute. Begründung: eine Stelle, vier Zeilen; für die Aufrufer
ändert sich nur, dass der Rest des Rumpfs nicht mehr ohne Transaktion
weiterläuft — eine Fehlerseite bekommen sie heute schon (von `commit()`).
Alternativen: (b) bei **jedem** Fehler in einer Transaktion werfen —
strenger, macht aber ein fehlgeschlagenes Protokoll zum Abbruch des
Vorgangs; (c) Einträge hinter den Commit wie `system_melden()` — dann gibt es
keinen Eintrag für Zurückgerolltes, aber der Grundsatz „Protokolleinträge
stehen mit in der Transaktion“ kehrt sich um, und rohe Transaktionen spülen
nicht. → **E-BR5-04.**

**Q-BR5-02 — `app_state_setzen()` gleich mit (Nr. 354, 345)?** Vorschlag:
**ja, dieselbe Regel.** Es schluckt wie `protokoll()` und steht in zwei
Transaktionen; BR5-01 nutzt es für 345 selbst in einer. → **E-BR5-05.**

**Q-BR5-03 — Umfang von `post_text()` (Nr. 355).** Vorschlag: **alle 125
`$_POST`-Stellen in einem Zug**, dazu die zwei `$feld`-Hüllen; die
Registerzeile mit Decke 0. `post_text()` liefert für eine Liste `null`, der
Aufrufer macht daraus seinen Formularfehler; keine Zeile im Reiter System.
`$_GET` (25 Stellen) als `get_text()` im selben Paket, mit eigener Zeile.
Die 500 an `email[]` in `login.php` (F-BR5-04) gehört dazu. Alternative: nur
die Anmeldeschritte (`login.php`, `zweitfaktor.php`, `zweitfaktor_notweg.php`,
`registrieren.php`) und eine Ratsche für den Rest — weniger Berührung jetzt,
aber eine zweite Runde später, und P7 dokumentiert einen halben Stand. → **E-BR5-06.**

**Q-BR5-04 — Gemeinsame Kontozeile auch (Nr. 358)?** Vorschlag: **ja** —
`notweg_konto()` ist eine gekürzte Kopie von `login_zeile()`; beide gehen in
eine Funktion neben der Token-Prüfung auf. Voraussetzung: `login.php` ist
nicht mehr gesperrt (E-SR-14 galt für Schritt 18, der seit Langem
abgeschlossen ist). → **E-BR5-07.**

**Q-BR5-05 — Wie überlebt der Passwortnachweis den Seitenwechsel
(Nr. 392)?** Das ist die eine Sicherheitsfrage dieses Schritts.
- **(a) Token im Sitzungsspeicher halten**, bis Kontopaar und Rückweg
  angelegt sind; die PBKDF2-Hälfte wird weiter sofort geräumt. Am wenigsten
  Code. Preis: Das Token ist ein passwortgleicher Nachweis gegenüber dem
  Server, gültig bis zur nächsten Passwortänderung, lesbar für ein Skript in
  der Seite (Lage D) und am Endgerät (Lage F). Heute liegt es einen
  Seitenwechsel lang dort (F-R17-24, Bedrohungsmodell I-05); (a) verlängert
  das bis zum Erfolg und braucht eine Frist.
- **(b) Eine Anlegemarke der Sitzung** *(Vorschlag)*: `login.php` setzt nach
  dem geprüften Passwort serverseitig eine Marke in die Sitzung — einmalig,
  mit Frist (Vorschlag zehn Minuten), nur gültig für das **erste** Anlegen
  von Kontopaar und Rückweg. `api/kontopaar.php` und
  `api/rueckweg_anlegen.php` nehmen statt des Tokens auch die Marke. Kein
  passwortgleicher Wert im Browser; die Marke ist nach Gebrauch oder Ablauf
  wertlos und hängt an der Sitzung. Preis: Wer die Sitzung in diesen zehn
  Minuten stiehlt, könnte ein Paar setzen — das Fenster ist kleiner als bei
  (a), wo er das Token selbst hätte, aber die Begründung „nur mit
  Passwortnachweis“ wird für das erste Anlegen zu „nur kurz nach einem
  Passwortnachweis“. Bedrohungsmodell und die Kopfkommentare beider Endpunkte
  ziehen nach.
- **Dazu, in beiden Fällen** *(Vorschlag)*: ein Weg von Hand für das
  Kontopaar wie „Rückweg erneuern“ — Knopf „Schlüsselpaar einrichten“ mit
  Passwortdialog (`EdSchluessel.oeffnen()` liefert Inhaltsschlüssel und
  Token), dort, wo heute „kein Schlüsselpaar“ abweist. Sicherheitlich
  neutral. Er ist ein vorhandener Baustein (Knopf in einer Karte), keine neue
  Darstellung. → **E-BR5-08.**

**Q-BR5-06 — Benennung der APKs (Nr. 361, 432).** Die zwei Einträge
schlagen Verschiedenes vor. Vorschlag: **„NAdoku für Android-Handy“ und
„NAdoku für Wear-OS-Uhr“** (432), die Fassung wandert in die Kleinzeile
(„Fassung 0.18.2 · APK · 7,1 MB · Stand …“). Begründung: „Uhr“ allein meint im
Projekt meist die Garmin-Uhr (Tags `uhr-v…`); das Handbuch und die
Play-Store-Zeile darüber sagen schon „Android-Handy“ und „Wear-OS-Uhr“.
Betrieb → Updates bekommt **zwei Zeilen**, „Android-App (Handy)“ und
„Wear-OS-App“, und nimmt je die Datei ihres Geräts statt `[0]` (F-BR5-05).
Eine Datei ohne Muster (Altbestand) bleibt „NAdoku x.y.z“. → **E-BR5-09.**

**Q-BR5-07 — Wortlaut der Reanimationskarte (Nr. 456).** Vorschlag:
**„gesperrt“**, solange der Inhaltsschlüssel fehlt, und **„nicht lesbar“**,
wenn die Hülle nicht aufgeht; „keine“ nur, wenn der Einsatz keine Hülle hat
(das weiß der Server). Kurz genug für den Kartenkopf. Alternative: „wartet
auf dein Passwort“ wie die Ansicht — länger, und im Formular gibt es den
Hinweis der Leiste schon. → **E-BR5-10.**

**Q-BR5-08 — Welche 5xx machen eine Mail unzustellbar (Nr. 454)?**
Vorschlag: **5xx bei `RCPT TO` und nach `DATA`**; 552 (Postfach voll) bleibt
auf der Leiter, weil es sich meist von selbst löst. 5xx bei `AUTH`,
`MAIL FROM`, `HELO` und der Verbindung bleiben Störungen auf der Leiter
(F-BR5-10). Genau eine Zeile im Reiter System je Absage. → **E-BR5-11.**

**Q-BR5-09 — Welche Domains reiht der Mailweg nicht ein (Nr. 450)?**
Vorschlag: **die vier reservierten Namen aus RFC 2606/6761 — `.invalid`,
`.test`, `.example`, `.localhost`.** Statt einer Störung im Reiter System ein
Eintrag im Protokoll (Reiter E-Mail), und die Einladungsseite sagt „nicht
verschickt — Adresse unter einer reservierten Domain“. Alternative: nur
`.invalid` (das, was die Kette benutzt). → **E-BR5-12.**

**Q-BR5-10 — Wer prüft Nr. 430?** Die Abnahme geht nur in einem echten
Browser mit Bitwarden; die Sandbox hat keins. Vorschlag: **Handprüfliste der
Betreiberin** (Firefox mit Bitwarden, auf Staging nach dem Merge). Der Umbau
ist Markup: Das Feld „Kontopasswort“ samt verstecktem Benutzernamen in ein
eigenes `<form>`, die zwei Felder für ein eigenes Backup-Passwort außerhalb;
Enter schickt nicht ab. Keine neue Darstellung. → **E-BR5-13.**

**Q-BR5-11 — Warnung beim Entkoppeln (Nr. 440).** Vorschlag: Liegt der
letzte Kontakt länger als **einen Tag** zurück (oder gab es keinen), ergänzt
die Rückfrage: „Zuletzt gemeldet am TT.MM.JJJJ um HH:MM. Was danach
aufgezeichnet wurde, liegt noch auf dem Gerät und kommt erst nach einer neuen
Kopplung an dieses Konto an. Ist das Gerät verloren, besser
**Deaktivieren**.“ Bei Kontakt heute bleibt der bisherige Satz. **Dieselbe
Rückfrage in der Verwaltung**; derselbe Satz in Handbuch und Runbook. Das ist
Text im vorhandenen Dialog (`data-confirm`), kein neuer Baustein. → **E-BR5-14.**

**Q-BR5-12 — Bezeichnung für `spur_stueck_geloescht` und ein Riegel
(Nr. 457)?** Vorschlag: **„GPS-Daten gelöscht“**, neutraler Ton wie die
übrigen Löscharten der Verwaltung; **dazu eine Zählzeile**, die jede literale
Art eines `protokoll()`-Aufrufs gegen `PROTOKOLL_ARTEN` hält, und jede Art
aus der Reiterliste ebenso. Anlass: Nr. 457 (Grundsatz 5). → **E-BR5-15.**

**Q-BR5-13 — Was zeigt die Karte von einem gescheiterten Job (Nr. 357)?**
Vorschlag: **die Meldung, durch `system_bereinigen()`** (ohne Pfade,
Adressen, Werte in Anführungszeichen); die Ausnahme selbst geht an
`system_melden()`, damit Klasse, Datei und Zeile im Reiter System stehen
(F-BR5-03). Dieselbe Kürzung für „Ließen sich nicht umhüllen“. Alte Zeilen in
`job_laeufe` bleiben, wie sie sind. → **E-BR5-16.**

**Q-BR5-14 — Linien auf der Karte „Schlüssel des Servers“ (Nr. 356).**
Vorschlag: **eine Linie je Abschnitt**, nicht je Formular — „Nachtragen vom
Blatt“ trägt bei „beide abweichend“ zwei Formulare, die zusammengehören.
„Neuanfang“ bekommt denselben Aufbau wie „Serverschlüssel wechseln“
(Formular außen, Fuß innen). → **E-BR5-17.**

**Q-BR5-15 — Wie wird Nr. 383 geschlossen?** Vorschlag: **beide Proben
unter dem PHP 8.3.6 der Sandbox fahren**; sind sie grün, ist 383 mit Verweis
auf Nr. 393 erledigt, ohne Codeänderung und ohne Fassung. Als Gegenprobe
einmal `fstat()` versuchsweise zurück auf `filesize()`: dann rot. 8.3.33 und
8.4 nicht eigens — Produktiv läuft 8.3.33 und war dort nie rot. Eine
Mindestfassung nur, falls 8.3.6 trotz allem rot bleibt; dann Rückfrage. → **E-BR5-18.**

## 4. Entscheidungen

**E-BR5-01 — Umfang: siebzehn Punkte (02. und 03.11.2026, Betreiberin).**
Die dreizehn aus E-BE-02, dazu 450 und 454 (02.11.2026) sowie 456 und 457
(03.11.2026) aus dem Prüfdurchgang der Arbeitsumgebung. Nr. 453 erledigt
PR #137; Nr. 455 ist Werkzeug und ebenfalls dort.

**E-BR5-02 — Diese Sitzung schreibt nur das Konzept (02.11.2026,
Betreiberin).** Die Umsetzung übernimmt eine andere Instanz, Paket für
Paket nach Abschnitt 5.

**E-BR5-03 — Konzept jetzt, Umsetzung nach PR #137 (03.11.2026,
Betreiberin).** PR #137 (`claude/new-session-p1duc7`, Web 23.6.2) ändert
`server/jobs_lib.php`, `server/sicherungsziel_lib.php`, `server/version.php`
und `docs/CHANGELOG.md`; die Instanz dort ändert nach eigener Auskunft keinen
Code mehr, nur Prüfliste, Backlog und Rahmenplan. Die Umsetzung von BR5
beginnt erst nach dessen Merge und nimmt `main` dann nach
`docs/Pruefablauf.md` 5.3 auf. Bis dahin berührt dieser Zweig nur das
Konzept und die eigene Spannenzeile in `Backlog.md`.

**E-BR5-04 — `protokoll()` wirft, wenn die Transaktion verloren ging
(03.11.2026, Betreiberin, Q-BR5-01).** Zustand vor dem INSERT merken; ein Fehler, der nur die
Anweisung zurückrollt, wird weiter geschluckt. Kein Weg hinter den Commit.

**E-BR5-05 — `app_state_setzen()` nach derselben Regel (03.11.2026, Betreiberin,
Q-BR5-02).**

**E-BR5-06 — `post_text()` für alle Stellen, `get_text()` dazu, Decke 0
(03.11.2026, Betreiberin, Q-BR5-03).** Eine Liste ergibt `null` und beim Aufrufer einen
Formularfehler, keine Zeile im Reiter System; die 500 an `email[]` gehört
dazu; die zwei `$feld`-Hüllen gehen darin auf.

**E-BR5-07 — Token-Prüfung und Kontozeile von Anmeldung und Notzugang an je
einer Stelle (03.11.2026, Betreiberin, Q-BR5-04).**

**E-BR5-08 — Anlegemarke der Sitzung statt Token im Browser, dazu der Weg
von Hand (03.11.2026, Betreiberin, Q-BR5-05).** `login.php` setzt nach dem geprüften Passwort
eine einmalige Marke mit zehn Minuten Frist, gültig nur für das erste
Anlegen von Kontopaar und Rückweg. Das passwortgleiche Token bleibt, wie
heute, höchstens einen Seitenwechsel im Sitzungsspeicher. Der bewusste Preis:
Wer die Sitzung in diesen zehn Minuten stiehlt, kann ein Paar setzen — aus
„nur mit Passwortnachweis“ wird für das erste Anlegen „nur kurz nach einem
Passwortnachweis“; Bedrohungsmodell und die Kopfkommentare beider Endpunkte
sagen es. Dazu der Knopf „Schlüsselpaar einrichten“ mit Passwortdialog.

**E-BR5-09 — „NAdoku für Android-Handy“ und „NAdoku für Wear-OS-Uhr“ (03.11.2026, Betreiberin,
Q-BR5-06).** Die Fassung steht in der Kleinzeile; Betrieb → Updates bekommt
zwei Zeilen, je mit der Datei ihres Geräts.

**E-BR5-10 — Reanimationskarte: „gesperrt“ und „nicht lesbar“ (03.11.2026, Betreiberin,
Q-BR5-07).** „keine“ nur ohne Hülle.

**E-BR5-11 — Sofort unzustellbar nur bei einer Absage beim Empfänger (03.11.2026, Betreiberin,
Q-BR5-08).** 5xx bei `RCPT TO` und nach `DATA`, außer 552; alles andere
bleibt auf der Leiter. Genau eine Zeile im Reiter System je Absage.

**E-BR5-12 — Keine Mail an reservierte Domains (03.11.2026, Betreiberin, Q-BR5-09).**
`.invalid`, `.test`, `.example`, `.localhost` werden nicht eingereiht; ein
Protokolleintrag (Reiter E-Mail) statt einer Störung, die Einladungsseite
sagt „nicht verschickt — Adresse unter einer reservierten Domain“.

**E-BR5-13 — Nr. 430 prüft die Betreiberin von Hand (03.11.2026, Betreiberin, Q-BR5-10).**
Firefox mit Bitwarden, auf Staging nach dem Merge; Punkt der Handprüfliste.

**E-BR5-14 — Warnung beim Entkoppeln ab einem Tag ohne Kontakt (03.11.2026, Betreiberin,
Q-BR5-11).** Wortlaut wie in Q-BR5-11, mit Datum und Uhrzeit, in den
Einstellungen und in der Verwaltung, derselbe Satz in Handbuch und Runbook.

**E-BR5-15 — „GPS-Daten gelöscht“ und eine Zählzeile für Protokollarten
(03.11.2026, Betreiberin, Q-BR5-12).** Anlass: Nr. 457.

**E-BR5-16 — Jobfehler: bereinigte Meldung in der Karte, Ausnahme an den
Reiter System (03.11.2026, Betreiberin, Q-BR5-13).** Über `system_bereinigen()`; dieselbe
Kürzung für „Ließen sich nicht umhüllen“; alte Zeilen bleiben.

**E-BR5-17 — Eine Linie je Abschnitt auf der Karte „Schlüssel des Servers“
(03.11.2026, Betreiberin, Q-BR5-14).** „Neuanfang“ im Aufbau von „Serverschlüssel wechseln“.

**E-BR5-18 — Nr. 383 wird gemessen, nicht umgebaut (03.11.2026, Betreiberin, Q-BR5-15).**
Beide Proben unter 8.3.6 mit Gegenprobe; grün → erledigt mit Verweis auf
Nr. 393. Eine Mindestfassung nur nach erneuter Rückfrage.

**Modellwahl:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine. Alle Pakete schreiben `server/version.php`,
`docs/CHANGELOG.md` und das Konzept (erzählender Text, CLAUDE.md 7);
BR5-03 bis -05 fassen dieselben Anmeldeseiten an; die Prüfarbeit läuft an
einem Prüfstand.

## 5. Arbeitspakete

Jedes Paket benennt den Ort jeder neuen Funktion (K1, R74). Die Reihenfolge
ist die Abarbeitungsfolge: Das Fundament (Transaktion, Formularfelder) liegt
vor den Anmeldeseiten, die darauf aufbauen; die Oberfläche folgt; 383 ist
eine Messung und kann jederzeit laufen. Jedes Paket mit Codeänderung stuft
`server/version.php` hoch und schreibt einen Changelog-Eintrag (CLAUDE.md 2);
welche Stufe, entscheidet die Umsetzung (K3).

### BR5-01 Transaktion und Protokoll (Nr. 354, 345, 457; Q-BR5-01, -02, -12)

*Vorab:* Kopfzeilen von 450, 454, 456, 457 auf `gehört zu: 22`
(F-BR5-13).

*Weg:*
1. `protokoll()` merkt vor dem INSERT `db()->inTransaction()` und wirft nach der
   Entscheidung zu Q-BR5-01. Dasselbe in `app_state_setzen()` (Q-BR5-02).
   `protokoll_fehler_vermerken()` läuft nur, wenn nicht geworfen wird.
2. Kommentare „`protokoll()` fängt seine eigenen Fehler“ in
   `pw_handling.php` und `einstellungen.php` nachziehen.
3. Nr. 345: „Logo-Standard“ prüft den Rückgabewert von
   `app_state_setzen()`; `instanz_namen_setzen()` und
   `instanz_adressen_setzen()` schreiben beide Werte in einem
   `db_transaktion()` und werfen, wenn einer scheitert.
4. Nr. 457: `spur_stueck_geloescht` in `PROTOKOLL_ARTEN` und in die
   Reiterliste `verwaltung`; Zählzeile nach Q-BR5-12.

*Ort:* `server/protokoll_lib.php`, `server/db.php` (`app_state_setzen()`),
`server/instanz_lib.php`, `server/admin_installation.php`,
`tools/zaehlung/register.php` (neue Zeile).

*Abnahme:* ein **echter** Deadlock in `totp_abschalten()` (zwei
Verbindungen, MariaDB; die zweite sperrt die Lücke hinter der letzten Zeile
von `protokoll_ereignisse`, dann die Kontozeile) → nichts bleibt stehen,
Passkeys vorhanden. Ein Auslöser mit `SIGNAL` stellt den Fall nicht her
(Nr. 354). Ein gescheitertes `app_state_setzen()` (gesperrte Tabelle) auf der
Installationsseite → Fehlermeldung statt „gespeichert“, beim Namen keiner der
beiden Werte geschrieben. Verwaltung → Protokoll: Plakette „GPS-Daten
gelöscht“ und die Art im Filter; die neue Zählzeile steht auf 0.

### BR5-02 Jobs und Mail (Nr. 357, 454, 450; Q-BR5-08, -09, -13)

*Voraussetzung:* PR #137 ist gemergt (`jobs_lib.php`, E-BR5-03).

*Weg:*
1. Nr. 357: `jobs_einen_lauf()` schreibt nach `letzter_fehler`, in
   `job_laeufe` und in den Bericht nur die bereinigte Meldung;
   `system_melden()` bekommt die Ausnahme. Dieselbe Kürzung für den Grund
   in „Ließen sich nicht umhüllen“ (`schluesselwechsel_lib.php`).
2. Nr. 454: Die Antwortprüfung in `smtp.php` führt Code und Schritt mit,
   `smtp_letzter_fehler()` gibt beide heraus; `mail_zeile_versuchen()` setzt
   bei einer Absage nach Q-BR5-08 sofort `unzustellbar`, mit der Absage als
   Grund.
3. Nr. 450: `mail_einreihen()` reiht Adressen unter den Domains aus
   Q-BR5-09 nicht ein, schreibt einen Protokolleintrag und gibt eine
   eigene Rückmeldung; die Einladungsseiten (`admin_users.php`,
   `admin_user.php`) nennen sie.

*Ort:* `server/jobs_lib.php`, `server/schluesselwechsel_lib.php`,
`server/smtp.php`, `server/mail_lib.php`, `server/admin_users.php`,
`server/admin_user.php`; eine neue Rückgabekonstante neben `MAIL_ABGELEHNT`.

*Abnahme:* ein Job, der wirft (verfälschtes Häppchen) → Karte ohne
Klassennamen, Reiter System mit Klasse; eine Mail an eine Adresse ohne
Postfach → nach dem ersten Versuch „unzustellbar“, genau eine Störung; ein
falsches SMTP-Passwort → Mails bleiben „wartet“; ein Konto unter
`…@example.invalid` → keine Mail eingereiht, kein Eintrag im Reiter System.
Gegenprobe auf Staging nach dem Merge: ein Lauf von Stufe 2 hinterlässt keine
Mailfehler (R91).

### BR5-03 Formularfelder (Nr. 355; Q-BR5-03)

*Weg:*
1. `post_text(string $schluessel): ?string` — Text oder `''`, wenn das Feld
   fehlt, `null`, wenn es eine Liste ist; `get_text()` ebenso nach Q-BR5-03.
2. Alle Stellen nach dem Umfang aus Q-BR5-03 umstellen; die zwei
   `$feld`-Hüllen gehen darin auf; `email[]` in `login.php` wird ein
   Formularfehler.
3. Registerzeile in `tools/zaehlung/register.php` gegen
   `(string)($_POST[…]`, `(string)$_POST[…]` und `is_string($_POST[…])`;
   Decke nach Q-BR5-03.

*Ort:* `post_text()`/`get_text()` in einer Bibliothek, die auch
`registrieren.php` und `zweitfaktor_notweg.php` ohne Sitzung laden — die
Umsetzung wählt sie und nennt sie im Statusblock (Vorschlag:
`validate_lib.php`, die gemeinsame Prüfschicht).

*Abnahme:* jedes Feld der Anmeldeschritte (`email`, `tokens`, `code`, `rc`,
`signatur`, `passkey_antwort`) als Liste → Formularfehler, keine Zeile im
Reiter System, keine 500; die Registerzeile hält ihre Decke; Bilderlauf ohne
Konsolenfehler.

### BR5-04 Zweite Passwortseite (Nr. 358; Q-BR5-04)

*Weg:*
1. Browser: eine Funktion in `crypto.js` (Salz holen, Token je Rundenzahl,
   auf Wunsch die Hälften), von `login.php` und `zweitfaktor_notweg.php`
   gerufen; die Meldungstexte bleiben bei der Seite.
2. Server: Token-Liste lesen und prüfen (je `kdf_iter`, Blindvergleich
   gegen `AUTH_VERGLEICHSWERT`) als eine Funktion; die Kontozeile nach
   Q-BR5-04. Das alte Einzelfeld `token` in `login.php` bleibt, solange ein
   Schreibweg es sendet — die Umsetzung misst das und nennt es.
3. `zweitfaktor_notweg.php` in `SEITEN` der Integritätswache (Web 21.13.0 ist
   ausgeliefert).

*Ort:* `server/assets/crypto.js`; die PHP-Funktionen neben
`AUTH_VERGLEICHSWERT` in `server/db.php` (beide Seiten laden es ohne
Anmeldung; `auth_guard.php` setzt eine Anmeldung voraus);
`tools/integritaetswache/wache.py`.

*Abnahme:* genau ein `fetch('auth_salt.php'` im Quelltext; Anmeldung und
Notzugang mit altem und neuem `kdf_iter` in drei Engines; die Wache misst
zwei Seiten.

### BR5-05 Schlüsselpaar und Rückweg nach der Anmeldung (Nr. 392; Q-BR5-05)

*Weg:* nach Q-BR5-05. Für (b): Marke in `login.php` nach dem geprüften
Passwort setzen, `api/kontopaar.php` und `api/rueckweg_anlegen.php` nehmen
sie für das erste Anlegen an und verbrauchen sie; `unlock.js` legt an,
sobald der Inhaltsschlüssel da ist, auch auf einer späteren Seite. Für (a):
`edkvor` behält nur das Token der gültigen Rundenzahl, mit Frist, und räumt es
bei Erfolg oder endgültigem Fehler. In beiden Fällen der Weg von Hand für das
Kontopaar. `docs/Bedrohungsmodell.md` (Lagen D, F, I-05; F-R17-24) und
`docs/Technik.md` („Entsperren des Inhaltsschlüssels“) ziehen nach
(CLAUDE.md 4).

*Ort:* `server/login.php`, `server/assets/unlock.js`,
`server/assets/kontopaar.js`, `server/assets/rueckweg.js`,
`server/api/kontopaar.php`, `server/api/rueckweg_anlegen.php`; die Marke
in einer Funktion neben `passwort_nachweis_json()` (`auth_guard.php`); der
Knopf an der Stelle, die heute mit „kein Schlüsselpaar“ abweist.

*Abnahme:* die Freigabeprobe **ohne Warten auf der ersten Seite** grün, in
drei Engines — für Kontopaar **und** Rückweg; bei (b) zusätzlich: die Marke
ist nach einmaligem Gebrauch und nach Ablauf der Frist wertlos (zweites
Anlegen → abgewiesen); der Weg von Hand legt ein fehlendes Paar an.

### BR5-06 Meldungen und Karten (Nr. 346, 356, 456; Q-BR5-07, -14)

*Weg:*
1. Nr. 346: `patwarn` und der Papierkorb-Hinweis als leerer Behälter, gefüllt
   über `EdHtml.meldung()` (`patwarn` mit `roh`, weil der Satz die Marke
   trägt — der Kommentar „genau ein Verbraucher“ in `html.js` zieht nach);
   Z37 auf 2, Begründung kürzen (F-BR5-08).
2. Nr. 356: je Abschnitt ein `div.listen-form` nach Q-BR5-14.
3. Nr. 456: Vorschau nach Q-BR5-07 im PHP (Hülle vorhanden?), im Sperrzweig
   von `patLaden()` und im `catch` von `ortOeffnen()`.

*Ort:* `server/assets/patient.js`, `server/index.php`,
`server/assets/html.js` (nur Kommentar), `server/betrieb_server.php`,
`server/einsatz_form.php`, `tools/zaehlung/register.php` (Z37).

*Abnahme:* Z37 = 2; Bilder der beiden Meldungen; Bild der Karte
„Schlüssel des Servers“ in der Lage *abweichend*, 390 und 1280 px;
gesperrtes Formular eines Einsatzes mit Reanimation → nicht „keine“;
Bilderlauf ohne Konsolenfehler. Kein neuer Baustein, kein Mockup — die drei
Änderungen gleichen an vorhandene Bausteine an (`Design.md` 9).

### BR5-07 Backup-Formular (Nr. 430; Q-BR5-10)

*Weg:* in der Karte „Backup erstellen“ das Feld „Kontopasswort“ samt
verstecktem Benutzernamen in ein eigenes `<form>`; die zwei Felder für ein
eigenes Backup-Passwort außerhalb; ein `submit`-Handler verhindert das
Abschicken der Seite und führt Enter auf den Knopf.

*Ort:* `server/einstellungen.php` (Karte `#k-erstellen` und ihr Skript).

*Abnahme:* Bildvergleich vorher/nachher ohne Abweichung; Backup mit
Kontopasswort und mit eigenem Passwort in drei Engines; **Bitwarden nach
Q-BR5-10 als Handprüfpunkt** — kann hier nicht geprüft werden.

### BR5-08 Geräte (Nr. 361 mit 432, 440; Q-BR5-06, -11)

*Weg:*
1. `apk_liste()` gibt `geraet` mit (`nadoku-uhr-…` → Wear-OS-Uhr,
   `nadoku-<Fassung>` → Handy, sonst keins) und sortiert Handy vor Uhr vor
   Rest. Einstellungen → Geräte, „Ohne Play Store“ benennt nach Q-BR5-06;
   Betrieb → Updates nach Q-BR5-06 (F-BR5-05). `apk.php` mitprüfen.
2. Nr. 440: Die Rückfrage vor dem Entkoppeln (Einstellungen → Geräte und
   Verwaltung) bekommt den Satz aus Q-BR5-11, gebaut aus
   `devices.last_seen`.
3. Handbuch (Geräte, Ohne Play Store, Updates) und Runbook „Gerät verloren“
   in `docs/Technik.md` mit demselben Wortlaut.

*Ort:* `server/apk_lib.php`, `server/einstellungen.php`,
`server/betrieb_updates.php`, `server/admin_user.php`; der Satz aus 440 als
eine Funktion, die beide Seiten rufen, in der Bibliothek, die heute
`devices.last_seen` für beide Seiten liest (die Umsetzung nennt sie im
Statusblock).

*Abnahme:* zwei APK-Dateien → Handy zuerst, jede Zeile mit ihrem Gerät, die
Prüfsumme an ihrer Zeile; eine Datei ohne Muster unverändert; Betrieb →
Updates zeigt beide Fassungen richtig, auch wenn die Uhr-APK zuletzt
abgelegt wurde. Entkoppeln eines Geräts mit Kontakt vor mehr als einem Tag →
Rückfrage mit Datum und Rat; Kontakt heute → ohne; in beiden Seiten;
`tools/zaehlung/` ohne neue Stelle.

### BR5-09 PHP 8.3.6 (Nr. 383; Q-BR5-15)

*Weg:* Anlage aufbauen (`docs/Sandbox-Setup.md` 6.1a), Komplett- und
Schlüsselwechselprobe unter dem PHP 8.3.6 der Sandbox; Gegenprobe mit
`filesize()` statt `fstat()` (nicht committet). F-BR5-02 berichtigen.

*Ort:* keine Codeänderung erwartet; `docs/Sandbox-Setup.md` oder
`docs/Technik.md` (F-BR5-02).

*Abnahme:* beide Proben grün unter 8.3.6, mit Zahl; die Gegenprobe rot;
383 in `Backlog-Erledigt.md` mit Verweis auf Nr. 393. Bleibt eine Probe rot:
Befund und Rückfrage, keine Mindestfassung ohne Entscheidung.

### BR5-10 Abschluss

Prüfstand über den Endstand (Umfang nach `docs/Pruefablauf.md`; die
Anmeldeseiten aus BR5-03 bis -05 in drei Engines, auch wenn `style.css` und
`ui.php` unberührt sind — die Umsetzung nennt den gewählten Umfang),
Prüfdokument (CLAUDE.md 7), Backlog-Punkte nach `Backlog-Erledigt.md`,
Rahmenplan (Fahrplanzeile), Pull Request. Erledigt-Zeile und Löschung von
Konzept und Prüfdokument erst nach der Freigabe.

## 6. Prüfprotokoll

*(noch leer — die Umsetzung schreibt je Paket Mittel und Zahl hierher)*

## 7. Verlauf

| Datum | Was |
|---|---|
| 03.11.2026 | `main` mit PR #137 und #138 aufgenommen; Fahrplanzeile 22, Rahmenplan Fassung 206, Kopfzeilen 450, 454, 456, 457 auf `22`; gemergte Spanne 470 bis 479 (GR) gestrichen, weil die Kopfdecke von `Backlog.md` sonst riss (61 > 60). |
| 03.11.2026 | Q-BR5-01 bis -15 entschieden wie vorgeschlagen (E-BR5-04 bis -18). |
| 03.11.2026 | Konzept angelegt; Befund F-BR5-01 bis -14, Fragen Q-BR5-01 bis -15; E-BR5-01 bis -03 entschieden; Spanne 480 bis 489 reserviert (`0c39a8cb`). |
