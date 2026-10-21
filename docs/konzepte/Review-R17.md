# Review R17 — Sammelstelle des Bug- und Sicherheitsreviews (P6)

**Kürzel:** `R17`, nach Planung v1.0 (E-PV-5 (e): „Entscheidungen als
E-R17-n im Review-Dokument"). Arbeitspakete `R17-NN`, Entscheidungen
`E-R17-NN`, Befunde `F-R17-NN`, Fragen an die Betreiberin `Q-R17-NN`,
Prüfpunkte `P-R17-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`R17-01: …`). Benennung nach `docs/Pruefablauf.md` 7. **Die Nummern
laufen über alle zwölf Stücke des Reviews fort:** Stück 1 (dieses Dokument
ab Abschnitt 3) ist Schritt 12, die Stücke 2 bis 12 sind Schritt 12b und
schreiben hier weiter — mit denselben Zählern, ohne Neubeginn.
**Rahmenplan:** Schritt **12** — „P6, Stück 1: das Bedrohungsmodell" (R17,
R69, R86); Voraussetzung „18 gemergt" erfüllt am 05.10.2026 (PR #101).
**Herkunft:** Konzeptsitzung ab dem 28.09.2026 (Fable, R14); begonnen am
11.10.2026 nach dem Abschluss von 18. Die Punkte liefert
`python3 tools/steuerung/uebersicht.py --ziel 12`: **zwei Einträge** am
11.10.2026 — Nr. 146 (Fragen an das Bedrohungsmodell) und Nr. 263 (Grenze
der Integritätswache).
**Modell:** **Fable** für jedes Paket dieses Schritts (R17, R69: „mit
Fable"); es gibt keine Umsetzung mit Opus, weil kein Paket Code schreibt.
**Fächerung (`CLAUDE.md` 7):** nur in R17-01, lesend — drei Leser für
drei Flächen (Server: Krypto, Backup, Sitzung; Clients: Uhr und Android;
Kette und Betrieb), ohne Nebenwirkung, jeder liefert Fundstellen mit
Funktionsnamen; **die Leser laufen mit Opus**, Fable führt zusammen und
prüft die Hoch- und Mittel-Befunde am Quelltext nach (E-R17-05). R17-00,
-02, -03, -04 seriell: erzählender Text, ein Ton.
**Versionsstufe:** keine. Berührt sind `docs/` und eine Zeile in
`tools/quelltext/textprobe.py` (der neue Bereich); nach `CLAUDE.md` 2 stuft
das keine der drei Zählungen hoch, und der Changelog bekommt keinen Eintrag.
Der Prüfstand fährt `klein`.
**Ablage:** dieses Dokument (Statusblock, Pakete, Entscheidungen, Befunde,
Fragen — und ab Schritt 12b die Fundliste der Stücke 2 bis 12);
`Pruefdokument-R17-Review.md` daneben; **das Modell selbst als
`docs/Bedrohungsmodell.md`** (E-R17-01), dauerhaft, neben `Technik.md`.
**Zweig:** `claude/exciting-brown-0n9et2`, aufgesetzt auf `origin/main`
bei `05a863e` (Web 21.14.0, Uhr 3.1.0, Android 0.17.0; 18 gemergt und
abgeschlossen). Push nach jedem Paket (K7); der Pull Request kommt erst nach
der Freigabe des Modells durch die Betreiberin (E-R17-03).
**Backlog-Spanne:** **370 bis 379** (E-SD-21, eingetragen mit R17-00 vor
jeder Vergabe). Vergeben aus der Spanne: siehe Statusblock.

**Schritt 12b (seit 20.10.2026):** Rahmenplan Schritt **12b** — „P6, Stücke
2 bis 12: Review und Bereinigung" (R17, R69, R86); Voraussetzung „12a
gemergt" erfüllt am 20.10.2026 (PR #116, #117; `main` bei `f17380c`, Web
23.0.2, Uhr 4.0.0, Android 0.18.0). Auftrag, Lesweise und Stücke stehen in
**Abschnitt 7**, die Pakete R17-05 bis R17-17 in Abschnitt 4, die Befunde
der Stücke in 2.3a. **Modell:** Fable für jedes Paket des Reviews; **Fable
liest jedes Stück selbst** (E-R17-14) — Unter-Agenten nur lesend für
mechanische Vorarbeit. **Versionsstufe:** keine für den Review (nur
`docs/`); die Pakete nach der Freigaberunde stufen nach `CLAUDE.md` 2.
**Zweig:** `claude/lucid-cannon-nvv86i`, aufgesetzt auf `origin/main` bei
`f17380c`. **Backlog-Spanne:** **400 bis 409** (E-SD-21, zuerst gepusht in
`e99a855`). **Prüfdokument:** `Pruefdokument-R17-Review.md` wird
fortgeschrieben (E-R17-15). **Zweig von Schritt 12** war
`claude/exciting-brown-0n9et2` (Absatz darüber), gemergt mit PR #103.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **21.10.2026 — R17-17 begonnen: Freigaberunde eröffnet, HALT.** Elf Fragen an die Betreiberin stehen in 2.2 (Q-R17-09 bis -19): die Fundliste und der Paketschnitt als Ganzes (Q-09), die Reihenfolge (Q-10), F-R17-58 als vorgezogenes Paket (Q-11), Pfeffer vor bcrypt (Q-12), der Alt-Weg der Uhr (Q-13), die Frist für „abgemeldet" (Q-14), die Zeichenkette in `wh_auspacken()` (Q-15), Nr. 380 bis 382 in P6 oder nach P7 (Q-16), Nr. 77 (Q-17), Nr. 263 (Q-18), der Umfang des Kommentardurchgangs (Q-19) — je mit Empfehlung und Preis; daneben die Liste dessen, was bei der Betreiberin liegt (P-R17-04, -12, -13, Rahmenplan 6.1, CSP scharf, Hoster-Logdauer). **Bis die Antworten da sind, ändert R17-17 nichts weiter.** Danach: E-R17-17 ff., Paketschnitt verbindlich, Zeilen R17-18 ff. in Abschnitt 4, Backlog-Kopfzeilen, Rahmenplan. Davor: **21.10.2026 — R17-16 erledigt: Stück 12, Zusammenführung.** Kein Code, kein neuer Befund: Die 59 Befunde aus 2.3 und 2.3a stehen in 7.3.12 nach Grad sortiert (5 hoch, 22 mittel, 25 niedrig, 7 Hinweise), jeder mit Stück, Kurztext, Vorschlag und dem Paket, in das er fällt; die fünf Posten aus 3.4 sind alle beantwortet (K-3, K-7, K-15, AN-5 in den Stücken 2, 3 und 9, K-10 gegenstandslos) und dort nachgetragen; die zehn Backlog-Punkte mit Ziel 12b sind eingeordnet. **Sofortpaket: keins** (E-R17-06 — kein Befund „kritisch"; was sofort ansteht, kostet keinen Code: P-R17-04, -12, -13 und die vier Zeilen zu F-R17-05 in Rahmenplan 6.1; der eine Kandidat für ein vorgezogenes Paket ist F-R17-58, der öffentliche Vertragstext). **Paketschnitt als Entwurf:** elf Pakete A bis K — fünf mit Fable (A Konten, Nachweise und Freigabe; B Sicherung und Wiederherstellung; D Uhr; F Kette und Werkzeuge; H Inhaltsschlüssel als `CryptoKey`, Nr. 370), sechs mit Opus (C Server-Verhalten; E Android; G Browser-Herkunft; I Seitenaufbau und Mengen, Nr. 380 bis 382; J Dokumentation und Zusagen, F-R17-58; K Aufräumen und Kommentardurchgang je Codebasis) — und daneben die Zuarbeiten der Betreiberin (F-R17-04, -05, -14, -23). **Kommentardurchgang gezählt:** 11 855 Verweise in 613 Dateien (Server 6 532, davon 814 in `version.php`; Werkzeuge 3 263; Browser 971; Android 956; Uhr 133). **Aufräumposten gebündelt:** je Codebasis eine Liste aus 7.3.2 bis 7.3.11, darunter 24 Karteileichen mit Gegenprobe als die neuen A4-Kandidaten (Nr. 21). **R5-Ausnahmeliste: leer, jetzt gemessen** — kein gespeicherter Name führt an einem Bodeneinsatz in die Irre; fünf sind seit Web 6.0.0 umbenannt, die übrigen bezeichnen Luftgebundenes, das am Bodentag leer bleibt. **Als Nächstes: R17-17 — Freigaberunde, Halt bei der Betreiberin** (Q-R17-09 ff., je Fund Empfehlung und Preis). Davor: **21.10.2026 — R17-15 erledigt: Stück 11, Doku gegen Code, gelesen.** 19 Dokumente, 33 903 Zeilen — `Technik.md`, `Handbuch.md`, `Design.md`, die Formate, `CLAUDE.md`, `README.md`, die Anleitungen von Android, Store, Vendor und Symbolen —, jede Zeile von Fable, und jede prüfbare Aussage darin gegen den Code gehalten: rund 120 Konstanten, Töpfe, Fristen, Grenzen, Listen und Zahlen nachgemessen (7.3.11), `docs/rechtstexte/` auf technische Aussagen durchsucht. **Zwei Befunde** (2.3a): F-R17-58 (mittel — vier öffentliche Texte beschreiben GPS-Spur, Phasenorte, Transportziel und Reanimation noch als **Klartext**: der Kasten in `README.md`, `Was-ist-NAdoku.md` (die Seite `ueber.php` vor der Anmeldung), `Handbuch.md` 4.3 und 5 (`hilfe.php`) und die **AVV-Vorlage** in Anlage II — seit S11 ist das Gegenteil wahr, `CLAUDE.md` 4, das Bedrohungsmodell und die Datenschutzerklärung sagen es; die beiden Rechtstext-Vorlagen widersprechen einander damit heute), F-R17-59 (niedrig — Sammelbefund II veralteter Stellen: rund 50 Zahlen und Sätze in zwölf Dokumenten, die der Code überholt hat, von „siebenundzwanzig Proben" (31) über „14 Aktionen an SHAs" (15), „46 Katalogeinträge" (74) und die Abbildfassungen in `Sandbox-Setup.md` bis zur Gliederung von `Technik.md`, in der `## 5.` vor `4.99a2` steht und `4.98a` zweimal vorkommt). **Altbefunde:** F-R17-09 bestätigt (die Doku sagt weiter nicht, dass eine getippte Adresse an Photon geht); F-R17-25 bestätigt; F-R17-26 bestätigt; F-R17-29 zum Teil noch offen (6.4 „auf diesem Commit", 6.5 „`.sitzungen/` steht noch nicht darin" stehen noch — gemessen: steht seit AP5 zweimal in `ausliefern-lauf.yml`); F-R17-30 bestätigt (die Krypto-Review bleibt Historie). **Gemessen, nicht nur gelesen:** `jobregister` 11 Jobs, 18 Schritte; Register 44 Zeilen; Rollenmatrix 118 Zeilen; `uses:` 15 von 15; Ausnahmeliste 11 Zeilen, acht Pfade; `sitzungshaertung` 0 Befunde; `kennzeichnung` 26 von 26; Vendor 338 Prüfsummen OK; Manifest 99 Geräte; 23 Ratentöpfe, acht mit Leiter (7.3.11). Aufräumposten: Stand-Zeilen (`Technik.md` 20.09., `Handbuch.md` 06.09.2026), Zeilennummern als Fundstellen in 4.99b, die Signatur `ensureContentKey(wrap, kdfSalt)`, `<datalist>` in 4, der HERKUNFT-Satz „phpseclib nur für SFTP" (vier Verwender). **Als Nächstes: R17-16 — Stück 12, Zusammenführung** (7.3). Davor: **21.10.2026 — R17-14 erledigt: Stück 10, Werkzeuge und Workflows, gelesen.** 277 Werkzeugdateien, 82 305 Zeilen — die vier Workflows, `tools/motor.mjs` und die 21 Ordner unter `tools/`, jede Datei von Fable selbst; dazu 151 Datendateien, zwei Fremddateien, zwei eingecheckte Ausgaben und fünf Binärdateien katalogisiert (7.3.10). **Drei Befunde** (2.3a): F-R17-55 (mittel — `tools/sandbox/konfig_stellen.php` legt bei jedem Lauf von acht Proben eine vollständige Kopie der `config.php` als `config.php.vor-probe` **ins Webverzeichnis**, `tools/proben/mail/probe.php` ebenso als `config.php.mailprobe`; beide bleiben bei `kill -9` liegen, keine steht in der `FilesMatch`-Liste der `.htaccess` (die ist auf `config\.php$` verankert), `.vor-probe` steht in keiner Schutzliste, `.mailprobe` nur in `.gitignore` — eine Datei mit der Endung `.vor-probe` liefert jeder Webserver als Text aus), F-R17-56 (niedrig — `tools/zaehlung/register.php` Z17 hält die Decke 30 bei Ist 0 seit AP5; dreißig Rückfälle blieben grün), F-R17-57 (niedrig — `tools/kette/tor.py` setzt `JOBS_TOKEN` in die Anfrageadresse, `jobs.php` nimmt es auch per POST; das Token steht im Zugriffslog des Hosters). **Altbefunde:** F-R17-03, -04, -15, -17, -18 bestätigt; F-R17-16 bestätigt und um F-R17-55 ergänzt; F-R17-20 bestätigt und verschärft (vier `.deb` ohne Prüfsumme in den Simulator, `npx --yes http-server` in zwei Proben, das SDK ohne Prüfsumme auch im Uhr-Job der Auslieferung); F-R17-14 am Code nicht messbar; Nr. 188 mit vier lebenden Beispielen belegt; Nr. 263 bestätigt. **Gefahren, nicht nur gelesen:** 13 Selbstproben und Erzeuger im Container — `bestand.py` 0 Befunde, Quelldaten 7 262 Einzelprüfungen, Generator 339 472, Vergleich 10 von 10 und 15 von 15 Proben aufs Exempel, Register 44 Zeilen 0 über der Decke, `vulture` 37 Meldungen (7.4). Aufräumposten: sechs Verweise ins Leere (`tools/mailprobe/`, `tools/anteilprobe/`, `tools/motoren-ausnahmen.md` …), zwei Handwerkzeuge, die den heutigen Stand nicht mehr messen (`sichtpruefung.mjs`, `riegelprobe.php`), der feste Containerpfad an sieben Stellen, drei Zahlen für die Probenzahl, veraltete „Nur Chromium"-Sätze. **Als Nächstes: R17-15 — Stück 11, Doku gegen Code** (7.3). Davor: **20.10.2026 — R17-13 erledigt: Stück 9, Android, gelesen.** 135 Dateien, 23 824 Zeilen — 98 Kotlin-Dateien (18 809 Zeilen) in beiden Modulen und `gemeinsam/`, die Bauskripte, Manifeste, Ressourcen, 46 Prüffall-Dateien und die fünf Werkzeuge (7.3.9), jede von Fable selbst. **Ein Befund** (2.3a): F-R17-54 (mittel — der Knopf „Dienst beenden" in der Akkuwarnung ab 15 % und in der 26-Stunden-Erinnerung schickt nur die Aktion `BEENDEN` an den Vordergrunddienst; der baut Ortung und Takt ab, sendet und stoppt sich, schließt aber den Dienst im Puffer nicht — `app.klammer.beenden()` rufen nur die zwei anderen Wege vorher selbst. Der Diensttag bleibt offen, die App zeigt beim nächsten Öffnen „Dienst läuft" ohne Aufzeichnung, die Uhr auch, und der nächste „Dienst beginnen" setzt den alten fort; das Spiegelbild von B-S5Z-04). **Altbefunde:** F-R17-27 bestätigt (drei `main`-Dateien mit Play-Diensten, dazu eine im instrumentierten Prüfsatz), F-R17-31 bestätigt (`UhrHorcher` liest `sourceNodeId` nicht; Hinweis bleibt), F-R17-03: `android/` liest keine Umgebungsvariable (0 Treffer), das Erbe liegt in der Kette (Stück 10/11); AN-5 beantwortet (`isMinifyEnabled = false` in beiden Modulen, Grund in beiden `proguard-rules.pro`). **Gemessen, nicht nur gelesen:** `tools/sandbox/aufbauen.sh android` und `./gradlew --no-daemon lint test` im Container — BUILD SUCCESSFUL in 6 min 5 s, Handy 280 Prüffälle je Bauart (15 ohne Anlage übersprungen), Uhr 72, 0 Fehlschläge, Lint 0 Fehler (Handy 5 Fassungshinweise, Uhr 0), Bilderlauf 78 und 7 Bilder — dieselben Zahlen wie `android/LIESMICH.md` vom 17.10.2026. Aufräumposten: der Kommentar „wie der in der Dauermeldung" (sie hat keinen Knopf), der Sekundentakt der Hauptansicht mit drei SQLite-Abfragen auf dem Hauptfaden, der Altbestand `server_basis`, „drei" gegen „zwei" Fremdbestandteile, `Uhrsteuerung.zustand` ohne `@Volatile`, die unlesbare Zeile im Uhrpuffer. **Als Nächstes: R17-14 — Stück 10, Werkzeuge und Workflows** (7.3). Davor: **20.10.2026 — R17-12 erledigt: Stück 8, Uhr, gelesen.** 20 Monkey-C-Dateien, zwei Geräteprofile, Manifest, Jungle und 15 Ressourcendateien, 5 650 Zeilen (7.3.8), jede von Fable selbst. **Zwei Befunde** (2.3a): F-R17-52 (mittel — `Uploader.onResponse()` räumt das bestätigte Paket über den Listenindex, den `_findJob()` beim Absenden gemessen hat; wer währenddessen „geparkte verwerfen" bestätigt oder die Kopplung trennt, oder wem der Takt der Sync-Seite einen fertigen Eintrag davor wegräumt, verschiebt die Liste — der Rückruf entfernt dann ein fremdes Paket, dessen Spur als Waise im Speicher bleibt und das nie ankommt, oder greift hinter das Ende und bricht die App ab; `verwerfen()` daneben löscht richtig über die Referenz), F-R17-53 (niedrig — der Selbsttest der Gerätehülle merkt sich „kaputt" je App-Fassung und Firmware dauerhaft in `kpruef`; eine einmalige Ausnahme beim ersten Start wird so zum Dauerzustand bis zum nächsten Update, nichts setzt ihn zurück, und die Uhr sendet still nichts mehr). **Altbefunde:** F-R17-11 bestätigt (`_serverUrl()` lässt `http://` stehen, unverändert in 4.0.0), F-R17-12 bestätigt (der Alt-Weg über `deviceId` und `apiKey` steht noch in `properties.xml` und `credentials()`), F-R17-13 bestätigt (Klartextpuffer, E-R17-09), **F-R17-28 zur Hälfte erledigt** (geparkte Pakete räumt `parkFristPruefen()` nach 30 Tagen, E-S11-11; nach `abgemeldet` bleiben alle Pakete ohne Frist). **Gemessen, nicht nur gelesen:** Der Uhr-Prüfstand stand diesmal im Container (`CIQ_GERAETE_URL` gesetzt, SDK 9.2.0 geholt) — Stufe I über alle 99 Manifest-Geräte: 99 übersetzt, 0 fehlgeschlagen, 0 Warnungen. Aufräumposten: zwei Funktionen ohne Verwender (`Input.hasUpDown()`, `.touchActive()`), ein Kopfkommentar aus der Zeit vor dem SDK (`Util.mc`), die Markenfarben als Hexliterale in `Ui.mc`, zwei sichtbare Einstellungen des Alt-Wegs. **Als Nächstes: R17-13 — Stück 9, Android** (7.3). Davor: **20.10.2026 — R17-11 erledigt: Stück 7, Browser-Skripte und Stylesheet, gelesen.** 57 Skripte und `style.css`, 22 597 Zeilen (7.3.7), dazu die Köpfe der fünf vendor-Textdateien — jede von Fable selbst. **Ein Befund und ein Hinweis** (2.3a): F-R17-51 (niedrig — `assets/openlocationcode.js` ist eine vendorierte Google-Bibliothek unter Apache-2.0 ohne Herkunft, Fassung und SHA-256 im Kopf, abgelegt außerhalb von `vendor/`, und `docs/Lizenzen.md` kennt sie nicht, obwohl sie „alle Fremdbestandteile" verspricht), F-R17-50 (Hinweis — die Kopf-Prüfsumme von `vendor/leaflet/leaflet.css` ist die des CRLF-Originals aus npm; das Repositorium hält die Datei per `.gitattributes` als LF, die Summe ist auf keinem Klon nachrechenbar, der Inhalt ist zeichengleich). **Kein Markup-Loch:** 50 `innerHTML`-Zuweisungen in den Skripten und 27 in den Seiten, jede Einsetzung aus Daten läuft über `EdHtml.escape()` oder ist nach Bauart auf `[a-z0-9]` begrenzt (`pwquality.js`); `eval`, `new Function`, `document.write` 0; `fetch` an sieben Stellen, zwei davon an den abschaltbaren Adressdienst. **F-R17-24 bestätigt** (`edk` lebt bis zum Tabende, `edkvor` bis zum ersten Verbrauch), **Nr. 341 gemessen** (13 Regeln ohne Markup, die sechs ohne Grund stehen nur in Kommentaren), Nr. 380 gezählt (20 `asset()`-Skripte auf `einstellungen.php`, 19 auf `index.php`, dazu 7 aus dem Gerüst), Nr. 382 nur gelesen. Aufräumposten: 14 exportierte Einträge ohne Verwender außerhalb ihrer Datei, `EdLuftlinie.FARBE` als fester Hexwert neben dem Token, drei Pixelwerte unter einer Eigenschaftsausnahme, zwei Abschnitte „26" im Stylesheet. **Als Nächstes: R17-12 — Stück 8, Uhr** (7.3). Davor: **20.10.2026 — R17-10 erledigt: Stück 6, Betrieb, gelesen.** 48 Dateien, 38 118 Zeilen (7.3.6), jede von Fable selbst. **Drei Befunde und ein Hinweis** (2.3a): F-R17-47 (niedrig — `.htaccess` sperrt sieben Dateien, nicht aber `wartung.lock` und `ueberlast.json` im Webroot: Während einer Wartung liest jeder ohne Anmeldung, wer sie geschaltet hat — Name oder Anmeldeadresse des BetreiberIn-Kontos —, und den Überlastzähler jederzeit), F-R17-46 (niedrig — `smtp.php` erklärt ein Relais ohne Anmeldung an zwei Stellen für gültig, `smtp_send()` schickt aber immer `AUTH LOGIN` mit `$cfg['user']` ohne `??`: zwei PHP-Warnungen und eine 503 je Mail), F-R17-49 (niedrig — zwei Aufrufer geben `ui_meldung_markup()` HTML im Text mit, das die Funktion maskiert: Die Karte „Verlangsamung" der Sicherheitsseite ab Bremsstufe 1 und die Hilfe ohne `docs/` zeigen `<strong>` und `<code>` als Text), F-R17-48 (Hinweis — das Meldungsmuster in `ueberlast_erkannt()`, laut Kommentar der eine verlässliche Weg, kennt 1226 nicht; die zwei Wege davor fangen es, die Probe ist über sie grün). **F-R17-19 und -21 bestätigt** (`edbak_ablage_bereit()` hat fünf Aufrufer, `wiederherstellen.php` ist keiner; `mail_konto_entfernen()` hat keinen), F-R17-05 beim Stand der Betreiberin, Nr. 77 gelesen (`update.php` ist seit Web 15.2.0 Weiche und CLI-Notausgang, `WARTUNG_AUSNAHMEN` führt es). Dazu **P-R17-10** (Produktiv: `/ueberlast.json` jetzt und `/wartung.lock` in der nächsten Wartung abrufen). Aufräumposten: drei Karteileichen (`protokoll_frist_verwaltung_setzen()`, `protokoll_fehler_quittieren()`, `doku_bestand()`), eine Inhaltsprüfung, die 0 für leer hält, ein Tagesvergleich in zwei Zeitzonen, eine Pflichtliste ohne `link`, 2 494 Kommentarverweise. PHPStan: 66 Meldungen im Stück, 45 Rauschen, 21 gelesen, 0 Befunde. **Als Nächstes: R17-11 — Stück 7, Browser-Skripte und Stylesheet** (7.3). Davor: **20.10.2026 — R17-09 erledigt: Stück 5, Verwaltung und Admin, gelesen.** 31 Dateien, 18 322 Zeilen (7.3.5), jede von Fable selbst. **Vier Befunde** (2.3a): F-R17-43 (niedrig — „Neue Codes erzeugen" in `einstellungen.php` verlangt keinen frischen Code: Wer eine Sitzung erbeutet, erzeugt zehn neue Wiederherstellungscodes und liest sie ab — ein dauerhafter zweiter Faktor, genau das, was E-SR-31 beim Passkey verhindert), F-R17-44 (niedrig — `zeitraum.php` prüft den Monat nur auf zwei Ziffern: `m=13` schreibt eine PHP-Warnung in den Reiter System und einen Titel ohne Monat), F-R17-45 (niedrig — die CSV der Gerätemodelle schreibt die Selbstauskunft der Geräte ohne Formelschutz; ein Modellname mit `=` am Anfang wird in Excel zur Formel), F-R17-42 (Hinweis — `DEMO_GESPERRT` in `admin_user.php` lässt `konto_status`, `konto_grenzen` und `totp_zuruecksetzen` durch, und der Reset holt eine Sperre nicht zurück). **F-R17-07 und -08 bestätigt** (Nr. 371: drei Schreibwege gegen ein BetreiberIn-Konto ohne Riegel; der Klartext-Dump steht nicht in `ZF_FRISCH_HANDLUNGEN`), **Nr. 381 bestätigt** (Suche und Zeitraum holen und entschlüsseln den ganzen Bestand, bevor sie 200 zeigen), F-R17-02 und -06 unverändert. Dazu **P-R17-09** (Produktiv: Protokoll nach fremden `totp_codes_erneuert`). Aufräumposten: keine Karteileiche (140 Funktionen, alle genannt), ein toter Zweig, ein Erststart-Vermerk vor dem `INSERT`, drei Handgriffe ohne Treffermeldung, 1 012 Kommentarverweise. PHPStan: 201 Meldungen im Stück, 181 Rauschen, 20 gelesen, 0 Befunde. **Als Nächstes: R17-10 — Stück 6, Betrieb** (7.3). Davor: **20.10.2026 — R17-08 erledigt: Stück 4, Einsatz, Diensttag, Spur, Ingest, gelesen.** 36 Dateien, 20 019 Zeilen (7.3.4), jede von Fable selbst. **Vier Befunde** (2.3a): F-R17-39 (mittel — `einsatz.php` liest `n_gespeichert` aus `spur_stand()`, das es seit Web 23.0.0 nicht mehr liefert: Nach dem Wartungslauf, der eine Spur packt oder ausdünnt, rechnet die Seite null Punkte, der Menüeintrag „GPS-Daten als GPX" und die Plakette fehlen, und jeder Aufruf schreibt eine PHP-Warnung in den Reiter System; die GPX-Probe misst nur eine frische Spur), F-R17-38 (niedrig — die Umdatierung in `tageszuordnung_lib.php` fängt zwei Variablen ein, die es noch nicht gibt: zwei PHP-Warnungen je Umdatierung, sonst richtig), F-R17-40 (niedrig — der Import überspringt ein Datum ganz, sobald IRGENDEIN Diensttag dieses Datums im Papierkorb liegt, auch wenn daneben ein aktiver steht), F-R17-41 (Hinweis — `dup: overwrite` ohne `overwrite_id` umgeht die Kennungsprüfung des Imports). Die drei Altbefunde des Stücks (F-R17-09, -13, -28) bestehen, soweit dieses Stück sie sieht; **K-10 beantwortet** (gegenstandslos: seit S11-05d liest der Browser die GPX-Datei, der Server sieht kein XML mehr); Nr. 23 gelesen (kein Schreibweg dieses Stücks nimmt eine Reanimationsart an — sie liegt in der Hülle, und der Browser prüft sie; der eine Aufrufer von `pruef_reanimationsart()` ist der Rückweg der Sicherung, Stück 2). Dazu **P-R17-08** (Produktiv: Einsatz mit gepackter Spur, Reiter System). Aufräumposten: keine Karteileiche (193 Funktionen, alle genannt), eine tote Zelle, ein ungenutztes `use`, eine Phase 10, ein doppelter Abruf, 1 242 Kommentarverweise. PHPStan: 101 Meldungen im Stück, 87 Rauschen, 14 gelesen, 5 davon die zwei Befunde F-R17-38 und -39. **Als Nächstes: R17-09 — Stück 5, Verwaltung und Admin** (7.3). Davor: **20.10.2026 — R17-07 erledigt: Stück 3, Zugang und Sitzung, gelesen.** 31 Dateien und der API-Teil von `db.php`, 11 238 Zeilen (7.3.3), jede von Fable selbst. **Zwei Befunde** (2.3a): F-R17-36 (mittel — `rate_merkmale()` hängt an jeden Konto-Topf die Adresse mit derselben Schwelle: Der Anschluss ist bei 10 Fehlversuchen gesperrt, nicht bei 50, `login_ip` greift nie, `Technik.md` 5e.2 sagt das Gegenteil; beim Schlüsselblatt sperren 3 Fehlversuche den Anschluss trotz „je Konto"), F-R17-37 (niedrig — der Notzugang zählt nur die eingetippte Adresse, kein Anschluss, kein globaler Zähler: bcrypt-Last ohne Grenze). **F-R17-32 verschärft:** dieselbe Lücke an drei weiteren Stellen in `einstellungen.php`. Die drei Altbefunde des Stücks (F-R17-22, -23, -31) bestehen; **K-7 und K-15 beantwortet** (Adresswechsel im Profil mit Passwort, Link an die neue und Warnung an die alte Adresse; `csrf_ok()` hat den `is_string`-Riegel). P-R17-04 bleibt bei der Betreiberin, dazu **P-R17-06 und -07** (Dump von vor S7; Anschlusssperren auf Produktiv). Aufräumposten: zwei Karteileichen, eine zweite Schreibweise des Merkmals, 608 Kommentarverweise. PHPStan: 31 Meldungen im Stück, 22 Rauschen, 9 gelesen, 0 Befunde. **Als Nächstes: R17-08 — Stück 4, Einsatz, Diensttag, Spur, Ingest** (7.3). Davor: **20.10.2026 — R17-06 erledigt: Stück 2, Kryptographie und Schlüssel, gelesen.** 38 Dateien, 20 053 Zeilen (7.3.2), jede von Fable selbst (E-R17-14). **Drei Befunde und ein Hinweis** (2.3a): F-R17-32 (mittel — `api/kdf_upgrade.php` prüft das alte Anmelde-Token ohne Ratenschutz, die Geschwister haben ihn), F-R17-33 (niedrig — die Endmarken-Prüfung in `wiederherstellen.php` vergleicht zweimal dieselbe Zeichenkette; die S7-Umstellung hat die alte Schreibweise mitersetzt, vor der ihr eigener Kommentar warnt), F-R17-34 (niedrig — der Rückweg schreibt Standortnamen ohne die Prüfschicht), F-R17-35 (Hinweis — ein Paketteil ohne `edsk1:` kommt roh zurück, auch in einem Fassung-3-Paket). Die sieben Altbefunde des Stücks (F-R17-01, -02, -06, -10, -19, -24, -26) am Stand Web 23.0.2 nachgelesen: **alle sieben bestehen unverändert** (7.3.2). K-3 beantwortet (Mindestlänge 12 = `PW_MIN_LAENGE`). Aufräumposten: eine Karteileiche (`serverschluessel_zeile()`), drei tote Zweige seit Web 23, 987 Kommentarverweise. Vorsortierung örtlich mit PHPStan 2.3.1 (Stufe 4): 88 Meldungen im Stück, 73 Rauschen, 15 gelesen, eine davon ein Befund (F-R17-33). **Als Nächstes: R17-07 — Stück 3, Zugang und Sitzung** (7.3). Davor: **20.10.2026 — Schritt 12b läuft; R17-05 Buchführung erledigt.** Rahmenplan Fassung 167 (Fahrplanzeile 12b auf „Umsetzung", Kopf: Nr. 387 gemergt, `main` bei Web 23.0.2), Spanne 400 bis 409 zuerst gepusht (`e99a855`), gemergte Spannen 12a und 19 aus dem Backlog-Kopf gestrichen (die Decke `backlog-kopf` riss sonst mit 61 Zeilen), dieses Dokument mit Abschnitt 7 (Auftrag, Lesweise, elf Stücke mit Umfang und zugehörigen Posten) und den Paketen R17-05 bis R17-17, Prüfdokument fortgeschrieben (E-R17-15). Drei Entscheidungen der Betreiberin E-R17-14 bis -16 (Fable liest selbst; Prüfdokument fortschreiben; Vorsortierung örtlich). **Als Nächstes: R17-06 — Stück 2, Kryptographie und Schlüssel** (7.3). Davor: **11.10.2026 — Schritt 12 abgeschlossen.** Die Betreiberin hat das Modell freigegeben (P-R17-01); Erledigt-Zeile in Rahmenplan 8 (Fassung 156), Fahrplanzeile heraus, **PR #103** gegen `main`. Dieses Dokument bleibt für 12b (E-R17-02): Die Stücke 2 bis 12 schreiben mit denselben Zählern fort (nächste Nummern: E-R17-14, F-R17-32, Q-R17-09). Davor: **R17-04: die Antworten sind eingearbeitet, das Modell wartete auf die Freigabe (P-R17-01).** Acht Entscheidungen E-R17-06 bis -13: kein Sofortpaket (alles in 12b), Argon2id nein, `CryptoKey` ja in 12b (Nr. 370), S11 mit Zweck Server und der Klartextliste aus E-R17-10, Admin-Riegel (Nr. 371), Nr. 263 offen für 12b, der Krypto-Review gelöscht (Stand je K-Nummer in 3.4). Nr. 146 erledigt. Rahmenplan Fassung 155: Zuarbeit „Umgebung `produktion` beschränken" (F-R17-04). **Als Nächstes: Freigabe des Modells durch die Betreiberin, dann Erledigt-Zeile, Fahrplanzeile heraus, Pull Request.** Davor: **R17-03: acht Fragen an die Betreiberin gestellt (2.2).** Q-R17-01 (Sofortpaket für F-R17-02 und -03? Zusage F-R17-01 sofort berichtigen?), -02 und -03 (Nr. 146: Argon2id nein, `CryptoKey` ja in 12b), -04 und -05 (S11: Zweck Server; was Klartext bleibt), -06 (Admin gegen BetreiberIn-Konten: nein), -07 (Nr. 263: nicht umsetzen), -08 (Krypto-Review bleibt bis 12b). **Als Nächstes: die Antworten, dann R17-04** (Einarbeiten, Prüfdokument, Freigabe, Erledigt-Zeile, PR). Davor: **R17-02 erledigt: das Modell steht.** `docs/Bedrohungsmodell.md` nach der Gliederung in 3.1: fünf Werte (1), sechzehn Angreiferlagen A bis P (2), dreizehn Zusagen mit Grenze (3), die Bausteine in vier Tabellen gegen die Lagen (4), zwanzig Restrisiken mit Zuordnung (5), die Entwurfsfragen für S11 mit Empfehlungen zu Nr. 146 und Nr. 263 (6). Eingetragen im Bereich `c` der Textprobe mit zwei begründeten Ausnahmen (Fachbegriff Spur, Plattformnennung Garmin — das Modell unterscheidet die Uhren an ihren Sicherheitseigenschaften); verlinkt aus `README.md`, `Technik.md` (2, 4.98, 4.99b) und `CLAUDE.md` 4. **Als Nächstes: R17-03** — die Fragen an die Betreiberin; dort hält der Schritt an. Davor: **R17-01 erledigt: die Bestandsaufnahme.** Drei lesende Leser (Opus) haben den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegen die Doku gelesen; Fable hat die Hoch- und Mittel-Befunde am Quelltext nachgeprüft und das Inventar verdichtet: **55 Bausteine** (3.2), **31 Befunde** (2.3) — fünf **hoch** (die S10-Zusage überzeichnet das Offline-Raten, F-R17-01; die Freigabe liefert einem Admin den Klartext jedes Kontos ohne Spur, F-R17-02; Gradle erbt den App-Signaturschlüssel, F-R17-03; ein Probelauf fordert `produktion` von jedem Zweig an, F-R17-04; die Pflichtstücke des Wiederanlaufs sind seit dem 14.09.2026 offen, F-R17-05), fünfzehn mittel, acht niedrig, zwei Hinweise. Was keiner sicher lesen konnte, steht im Prüfdokument 1. **Als Nächstes: R17-02** (das Modell schreiben). Davor: **R17-00 erledigt** (Spanne, Fahrplanzeile, dieses Dokument, Prüfdokument). |
> | Entschieden | E-R17-01 bis E-R17-16 (Abschnitt 2.1) — **E-R17-14 bis -16 von der Betreiberin am 20.10.2026** (Fächerung, Prüfdokument, Vorsortierung); E-R17-01 bis -03 und **E-R17-06 bis -13 (die Antworten auf Q-R17-01 bis -08)** von der Betreiberin am 11.10.2026, E-R17-04 am 28.09.2026, E-R17-05 aus der Umsetzung. |
> | Offen | **12b:** die Freigaberunde (R17-17) über die 59 Befunde (2.3 und 2.3a) und die zehn Backlog-Punkte mit Ziel 12b (Nr. 21, 77, 188, 263, 341, 370, 371, 380, 381, 382) — Grundlage ist die Fundliste in 7.3.12 mit dem Paketschnitt-Entwurf A bis K; die Posten aus 3.4 sind erledigt. Danach wird der Paketschnitt verbindlich (E-PV-5 (8)), die Pakete bekommen Nummern ab R17-18. Schritt 12 ist abgeschlossen (PR #103 gemergt). |
> | Umsetzung | **R17-00 erledigt** 11.10.2026 (`fca8549`). **R17-01 erledigt** 11.10.2026 (`3b6f330`; Inventar I-01 bis I-55, Befunde F-R17-01 bis -31; Prüfdokument 1 und 2 fortgeschrieben). **R17-02 erledigt** 11.10.2026 (`docs/Bedrohungsmodell.md`, Textprobe-Bereich, Verweise; keine neuen Befunde — das Modell nennt F-R17-01 bis -31 an Ort und Stelle). **R17-03 beantwortet** 11.10.2026 (Q-R17-01 bis -08 → E-R17-06 bis -13). **R17-04 in Arbeit:** Antworten eingearbeitet (Modell 5 und 6, Backlog 146 → erledigt, 263 → 12b, Nr. 370 und 371 neu, Rahmenplan Fassung 155, Krypto-Review nach 3.4 und gelöscht); **Freigabe 11.10.2026, Abschluss geschrieben** (Rahmenplan Fassung 156, PR #103). **R17-05 erledigt** 20.10.2026 (Spanne `e99a855`; Buchführung im Commit mit dem Prüfbericht). **R17-06 erledigt** 20.10.2026 (Stück 2: 38 Dateien gelesen, F-R17-32 bis -35, sieben Altbefunde bestätigt, K-3 beantwortet; 7.3.2; Prüfbericht in der Commit-Nachricht). **R17-07 erledigt** 20.10.2026 (Stück 3: 31 Dateien gelesen, F-R17-36 und -37, F-R17-32 verschärft, drei Altbefunde bestätigt, K-7 und K-15 beantwortet, P-R17-07; 7.3.3; Prüfbericht in der Commit-Nachricht). **R17-08 erledigt** 20.10.2026 (Stück 4: 36 Dateien gelesen, F-R17-38 bis -41, drei Altbefunde nachgelesen, K-10 beantwortet, Nr. 23 gelesen, P-R17-08; 7.3.4; Prüfbericht in der Commit-Nachricht). **R17-09 erledigt 20.10.2026** (Stück 5 gelesen, F-R17-42 bis -45 in 2.3a, F-R17-07 und -08 bestätigt, Nr. 371 und 381 bestätigt, F-R17-02 und -06 unverändert, P-R17-09; 7.3.5; Prüfbericht in der Commit-Nachricht). **R17-10 erledigt 20.10.2026** (Stück 6 gelesen, F-R17-46 bis -49 in 2.3a, F-R17-19 und -21 bestätigt, F-R17-05 unverändert, Nr. 77 gelesen, P-R17-10; 7.3.6; Prüfbericht in der Commit-Nachricht). **R17-11 erledigt 20.10.2026** (Stück 7 gelesen, F-R17-50 und -51 in 2.3a, F-R17-24 bestätigt, Nr. 341 gemessen, 380 gezählt, 382 gelesen; 7.3.7; Prüfbericht in der Commit-Nachricht). **R17-12 erledigt 20.10.2026** (Stück 8 gelesen, F-R17-52 und -53 in 2.3a, F-R17-11, -12, -13 bestätigt, F-R17-28 zur Hälfte erledigt, Uhr-Prüfstand Stufe I 99 von 99 ohne Warnung; 7.3.8; Prüfbericht in der Commit-Nachricht). **R17-13 erledigt 20.10.2026** (Stück 9 gelesen, F-R17-54 in 2.3a, F-R17-27 und -31 bestätigt, AN-5 beantwortet, Gradle `lint test` im Container grün — 280 und 72 Prüffälle je Bauart, 0 rot, Lint 0 Fehler; 7.3.9; Prüfbericht in der Commit-Nachricht). **R17-14 erledigt 21.10.2026** (Stück 10 gelesen, F-R17-55 bis -57 in 2.3a, F-R17-03, -04, -15, -16, -17, -18, -20 bestätigt, Nr. 188 belegt, Nr. 263 bestätigt, 13 Selbstproben grün, P-R17-12; 7.3.10; Prüfbericht in der Commit-Nachricht). **R17-15 erledigt 21.10.2026** (Stück 11 gelesen, F-R17-58 und -59 in 2.3a, F-R17-09, -25, -26, -30 bestätigt, F-R17-29 zum Teil offen, rund 120 Aussagen nachgemessen, P-R17-13; 7.3.11; Prüfbericht in der Commit-Nachricht). **R17-16 erledigt 21.10.2026** (Stück 12: Fundliste aller 59 Befunde, Sofortpaket leer, Paketschnitt-Entwurf A bis K mit Modellmarkierung, Aufräumposten je Codebasis mit 24 A4-Kandidaten, Kommentardurchgang 11 855 Verweise, R5-Liste leer und gemessen, 3.4 nachgetragen; 7.3.12; Prüfbericht in der Commit-Nachricht). |
> | Fable-Schritte | Schritt 12: alle. **12b:** alle Pakete des Reviews (R17-05 bis -17); die Pakete nach der Freigaberunde: Kryptographie mit Fable, alles andere mit Opus (K8, E-PV-5 (7)), je Paket im Paketschnitt markiert. |
> | Fächerung | **R17-01 gefahren:** drei lesende Leser mit **Opus** (Server und Browser 129 Werkzeugaufrufe, 15 min; Uhr und Android 71, 10 min; Kette und Betrieb 105, 11 min), ohne Nebenwirkung — Leser 3 hat über `gh api` nur gelesen (Rulesets, Zweigregeln; Umgebungen und Organisation mit 403 verweigert). Zusammenführung und Nachprüfung Fable, seriell. Die übrigen Pakete seriell. **12b (E-R17-14):** keine Leser — Fable liest jedes Stück selbst, seriell; Unter-Agenten nur lesend für mechanische Vorarbeit (Aufruferlisten, Verweiszählung, ungenutzte Regeln), ohne Nebenwirkung, je Stück im Prüfprotokoll genannt. |
> | Nummern | 370 bis 379 reserviert; vergeben: **370** (Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`, 12b, E-R17-08), **371** (Admin darf BetreiberIn-Konten nicht anfassen, 12b, E-R17-11). **12b: 400 bis 409** reserviert (R17-05, `e99a855`); vergeben: noch keine. |

---

## 1. Auftrag

**Ziel von Schritt 12:** ein Dokument, kein Code — das **Bedrohungsmodell**
der Anwendung, als Stück 1 des R17-Reviews (R69). Es sammelt ein, was heute
verteilt liegt (`Technik.md` 4.97 bis 4.99q, der Krypto-Review vom
05.09.2026 — seit R17-04 nur noch in der Git-Historie, Stand in 3.4 —,
SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18), und sagt
an einem Ort: **was geschützt wird, gegen wen, womit, und wo die Grenzen
liegen.** Es ist der **Eingang des Konzepts S11** (Schritt 12a): Es sagt,
was Klartext bleiben darf, wie der Schlüssel aufs Gerät kommt und welche der
Fragen aus Nr. 146 vor S11 zu entscheiden sind. Und es ist danach die
Referenz, gegen die die Stücke 2 bis 12 (Schritt 12b) lesen — ein Review
prüft gegen Schutzziele und Angreifer, und die müssen vorher an einem Ort
stehen (Planung v1.0, 4e.1).

**Abnahme (Rahmenplan 3, Schritt 12):** Modell vollständig, Fragen aus
Nr. 146 entschieden, Freigabe der Betreiberin.

**Nicht Ziel:** Code, auch nicht „kleine" Behebungen — was das Modell an
Lücken zeigt, wird Befund (F-R17-NN) und geht in die Freigaberunde von 12b
oder, wenn es eine Zusage bricht, ins Sofortpaket (R69, K4). Nicht Ziel
ist auch das Konzept S11 selbst: Das Modell beantwortet dessen
Entwurfsfragen, es entwirft nicht. Und nicht Ziel ist die Doku-Pflege
außerhalb des Modells: Wo das Modell eine Stelle in `Technik.md` ersetzt,
bekommt die Stelle einen Verweis, keinen Umbau.

**Was das Modell leisten muss, in einem Satz je Leserin:** Die
**Betreiberin** liest, welches Risiko sie trägt und welches nicht; der
**Review** liest, wogegen er prüft; das **S11-Konzept** liest, was es
verschlüsseln muss und was bleiben darf; **VVT und TOMs** (R41, Schritt 14)
lesen daraus ihre technischen Maßnahmen ab.

## 2. Entscheidungen und Fragen

### 2.1 Entscheidungen (E)

| Nr. | Entscheidung | Wer, wann | Begründung |
|---|---|---|---|
| E-R17-14 | **Fable liest in 12b jedes Stück selbst**, seriell. Unter-Agenten nur für mechanische, lesende Vorarbeit (Aufruferlisten, Verweiszählung, ungenutzte Regeln), nie für die Lesung selbst. | Betreiberin, 20.10.2026 (Vorschlag Fable, Option a) | R86 will den Code **einmal, in Endfassung, von Fable** gelesen; Opus-Leser mit Nachprüfung (E-R17-05) wären ein Opus-Review mit Stichproben. E-R17-05 war für eine Bestandsaufnahme gegen die Doku richtig, für die Lesung des Codes nicht. Preis: Fable-Credits über elf Pakete. |
| E-R17-15 | **`Pruefdokument-R17-Review.md` wird fortgeschrieben**, kein eigenes Prüfdokument für 12b. | Betreiberin, 20.10.2026 (Vorschlag Fable) | Dieselben Zähler (P-R17-NN) und dieselbe Logik wie E-R17-02 für das Konzept; die offenen Punkte P-R17-03 bis -05 bleiben neben den neuen sichtbar statt in zwei Dateien. |
| E-R17-16 | **Maschinelle Vorsortierung nur örtlich** — PHPStan oder Psalm für toten PHP-Code, `vulture` für die Python-Werkzeuge, Android Lint, Compiler-Warnungen der Uhr, wo der Container es kann; nichts davon ins Repositorium, nichts in die Kette. Gelesen wird trotzdem alles (E-PV-5 (2)). | Betreiberin, 20.10.2026 (Vorschlag Fable) | Ein Werkzeug im Repositorium braucht einen Anlass und eine Anleitung (`Pruefablauf.md` 6.1, 6.2) — für eine einmalige Lesung ist das Aufwand ohne Riegel. Was die Vorsortierung liefert, steht je Stück mit Mittel und Zahl im Prüfprotokoll (5) und im Prüfdokument (2); ihre Grenzen in Prüfdokument 5. |
| E-R17-01 | **Das Modell ist eine eigene Datei, `docs/Bedrohungsmodell.md`**, normative Dokumentation neben `Technik.md` — kein Abschnitt darin. Es wird im selben Paket, in dem es entsteht, in den Bereich `c` der Textprobe eingetragen (`Pruefablauf.md` 6.6) und aus `README.md`, `Technik.md` und `CLAUDE.md` 4 verlinkt. | Betreiberin, 11.10.2026 (Vorschlag Fable) | Planung v1.0 ließ die Wahl offen („eigener Abschnitt oder eigene Datei, entscheidet der Review"). `Technik.md` hat 13 600 Zeilen; das Modell hat andere Leser (Betreiberin, Datenschutz, Review) und muss ohne die Technik lesbar sein. |
| E-R17-02 | **Kürzel `R17`; dieses Dokument bleibt nach Schritt 12 stehen.** Es ist das Konzept von 12 (Statusblock, Pakete, Prüfprotokoll) und zugleich die Sammelstelle des ganzen Reviews; 12b schreibt mit denselben Zählern fort. Die Erledigt-Zeile von 12 nennt es; gelöscht wird es erst mit dem Abschluss von 12b. | Betreiberin, 11.10.2026 | Abweichung von R62 („das Konzept wird gelöscht"), weil der Rahmenplan es so vorsieht („Review-R17.md beginnt damit", „Fortsetzung aus 12") und eine Fundliste, die über 12a hinweg zwei Dateien hätte, zwei Zählungen hätte. |
| E-R17-03 | **Push nach jedem Paket auf `claude/exciting-brown-0n9et2`** (K7); **kein Pull Request vor der Freigabe des Modells** durch die Betreiberin. | Betreiberin, 11.10.2026 | K7 und `CLAUDE.md` 8; der PR ist die Phase, nicht ein Paket. |
| E-R17-06 | **Kein Sofortpaket vor 12a.** F-R17-01, -02 und -03 bleiben „hoch" und gehen als Pflichtpakete in die Freigaberunde von 12b; die überzeichnete S10-Zusage (F-R17-01) bleibt bis dahin in `CLAUDE.md` 4, `Technik.md` 3 und SP-3 stehen — `CLAUDE.md` 4 sagt seit R17-02, dass bei Abweichung das Modell gilt. | Betreiberin, 11.10.2026 (Q-R17-01: „Alles erst in 12b") | Fable hatte F-R17-02 und -03 als kleines Sofortpaket und die Berichtigung der Zusage sofort empfohlen (R69: Bruch einer Zusage → Sofortpaket). Preis der Entscheidung: F-R17-02 (ein Admin liest den Klartext jedes Kontos ohne Spur) bleibt bis nach S11 offen; die Anlage hat heute ein Konto. |
| E-R17-07 | **Argon2id wird nicht weiterverfolgt.** Nr. 146 damit zur Hälfte erledigt; die Begründung steht im Modell 6.5. | Betreiberin, 11.10.2026 (Q-R17-02) | Nach S10 bringt das Raten ohne Anmeldung keinen Datenschlüssel; mit dem Pfeffer aus F-R17-01 bräuchte auch das Raten `config.php`. Ein WASM-Fremdbestandteil mit 64 MB je Ableitung verteidigte dann nur noch gegen Lage B. |
| E-R17-08 | **Der Inhaltsschlüssel wird ein nicht-extrahierbarer `CryptoKey`** — als Paket in 12b (Fable), nach der scharfen CSP (F-R17-23). Backlog **Nr. 370**. Nr. 146 damit erledigt. | Betreiberin, 11.10.2026 (Q-R17-03) | Ein XSS könnte weiter entschlüsseln, den Schlüssel aber nicht mitnehmen — und ein mitgenommener Schlüssel gilt dauerhaft (F-R17-10). Erste Linie CSP, zweite Linie `CryptoKey`. |
| E-R17-09 | **S11 hat den Zweck „Server":** Verschlüsselung vor dem Upload; das verlorene Gerät bleibt benanntes Restrisiko (Modell R13, R14), geparkte Pakete auf der Garmin bekommen eine Frist (F-R17-28). „Beim Schreiben" ist ein eigener späterer Schritt, falls gewünscht. | Betreiberin, 11.10.2026 (Q-R17-04) | SP-9 Punkt 6 („ein Verlust kostet das Trennen, nicht mehr") gilt nur bei Verschlüsselung beim Schreiben, und die baut die Anzeige der Uhr um (`Track.readPoints()`). S11 wird kleiner und ehrlicher. |
| E-R17-10 | **In S11 bleiben Klartext:** Zeiten, `seq`, Kennungen, Strecke, Anstieg, Stammdaten, Besatzung, Einsatzmittel. **Verschlüsselt werden** Spur, Phasenkoordinaten, Reanimationsverlauf und das Transportziel; die Zählung „Fahrten je Klinik" wandert in den Browser und rechnet nach dem Entsperren (F-SP-7). | Betreiberin, 11.10.2026 (Q-R17-05) | Der Server rechnet mit Koordinaten nirgends (`Konzept-V1-Ortsdaten.md` 3.1); R78 (6) hat das Transportziel ausdrücklich in den Umfang genommen. |
| E-R17-11 | **Ein Admin darf BetreiberIn-Konten nicht sperren, löschen oder umadressieren.** Riegel in `admin_user.php`, Zeile in der Rollenprobe; kleines Paket in 12b. Backlog **Nr. 371**. | Betreiberin, 11.10.2026 (Q-R17-06) | F-R17-07: Heute ist nur die letzte BetreiberIn geschützt; die Matrix 4.99p misst `{ziel}` = `user`. |
| E-R17-12 | **Nr. 263 bleibt offen, Ziel 12b.** Der Code-Review liest die Wache in Stück 10 (Werkzeuge) und entscheidet dann über die Abhilfe; das Modell benennt die Grenze (G5, 6.6) ohne Stand. | Betreiberin, 11.10.2026 (Q-R17-07: „Offen lassen für 12b") | Fable hatte `nicht umsetzen` empfohlen (eine Hash-Schnittstelle wäre ein neuer Angriffsweg). Die Betreiberin will die Lesung von 12b abwarten. |
| E-R17-13 | **`Review-Krypto-Sicherheit.md` wird mit R17-04 gelöscht.** Die Git-Historie behält sie (zuletzt in `bc08eda`); der Stand je K-Nummer und AN-Nummer steht in 3.4, damit 12b weiß, was noch zu lesen ist. | Betreiberin, 11.10.2026 (Q-R17-08: „Jetzt löschen") | Fable hatte „bleibt bis 12b" empfohlen. Ihr Abschnitt 2 ist durch das Modell ersetzt, ihre Befunde sind teils erledigt, teils in 3.4 übergeben. |
| E-R17-05 | **Die Leser von R17-01 laufen mit Opus, die Zusammenführung mit Fable.** Ihre Berichte bleiben außerhalb des Repositoriums; was zählt, steht verdichtet in 3.2 und 2.3, und jeder Befund trägt, wer ihn gelesen hat. | Fable, 11.10.2026 | Eine Messung ist lesend und gut fächerbar (`CLAUDE.md` 7); drei Leser mit Fable kosteten das Dreifache für eine Vorarbeit, die ohnehin nachgeprüft wird. Die Rohberichte (rund 100 kB) sind Lesungen mit Unsicherheiten; ins Repositorium gehört nur, was gegengelesen oder als Lesung gekennzeichnet ist. |
| E-R17-04 | **Schritt 12 läuft nicht parallel zu 18**, sondern danach, wie der Rahmenplan es vorsah. | Betreiberin, 28.09.2026 | Fable hatte das Parallelfahren vorgeschlagen (das Modell ist zu neun Zehnteln unabhängig von 18) und den Preis genannt (Nachlese-Paket, R-Nummer, Konflikte in den Steuerungsdokumenten). Die Betreiberin hat die Reihenfolge behalten; 18 war am 05.10.2026 gemergt. |

### 2.2 Fragen an die Betreiberin (Q)

Erste Runde gestellt mit R17-03 am 11.10.2026, zweite Runde (die
Freigaberunde von 12b) mit R17-17 am 21.10.2026 — unten. Je Frage die
Lage, die Empfehlung und ihr Preis; die Antwort wird E-R17-NN und geht mit R17-04 ins Modell
(Abschnitt 6) und in die Backlog-Kopfzeilen. **Alle acht sind am 11.10.2026
beantwortet** (E-R17-06 bis -13); vier wie empfohlen, vier anders (Q-01,
-07, -08) oder mit anderem Weg.

| Nr. | Frage | Lage | Empfehlung | Preis | Antwort |
|---|---|---|---|---|---|
| Q-R17-01 | **Sind F-R17-01, -02 und -03 „kritisch"** — Bruch einer Zusage, also Sofortpaket **vor** 12a (R69) — oder „hoch", also Pflichtpaket in 12b? | F-R17-01: Die Zusage „kein Offline-Angriff mehr" ist falsch, der Code tut, was er soll — die Zusage bricht, nicht die Anlage. F-R17-02: Ein Admin liest den Klartext jedes Kontos ohne Spur — bricht die Kontentrennung, die Nr. 286 gerade herstellen wollte. F-R17-03: Gradle sieht den Signaturschlüssel — bricht E-PK-63 und ist nicht rücksetzbar | **F-R17-02 und F-R17-03 ins Sofortpaket**, beide klein (Freigabe nur an das Herkunftskonto oder dieselbe Adresse, mit Protokoll, Mail und frischem Code; Gradle in einem eigenen Schritt ohne `APK_*`). **F-R17-01 zweiteilig:** die Zusage in `CLAUDE.md` 4, `Technik.md` 3 und SP-3 **sofort** berichtigen (Doku, kein Code), den Pfeffer vor bcrypt als Pflichtpaket in 12b (Fable). F-R17-04 und -05 sind Zuarbeiten von dir, jetzt | Ein Sofortpaket vor 12a kostet eine Opus-Sitzung und eine Auslieferung; ohne sie liegt F-R17-02 bis nach S11 offen | **Alles erst in 12b** — E-R17-06 |
| Q-R17-02 | **Nr. 146, Argon2id statt PBKDF2** — weiterverfolgen? | Nach S10 bringt das Raten ohne Anmeldung keinen Datenschlüssel; mit dem Pfeffer aus F-R17-01 braucht das Raten `config.php` — Argon2id verteidigte dann nur noch gegen Lage B, die alles hat. Preis wäre ein WASM-Fremdbestandteil, 64 MB je Ableitung, dritter Ableitungsweg | **Nein.** Nr. 146 damit zur Hälfte erledigt; die Begründung steht im Modell 6.5 | keiner | **Nein** — E-R17-07 |
| Q-R17-03 | **Nr. 146, Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`** — bauen? | Ein XSS könnte weiter entschlüsseln, den Schlüssel aber nicht mitnehmen — und ein mitgenommener Schlüssel gilt dauerhaft (F-R17-10). Umbau mittel: IndexedDB statt `sessionStorage`, Lebensdauermodell, alle `getContentKey`-Aufrufer | **Ja, als Paket in 12b** (Fable), **nach** der scharfen CSP (F-R17-23): erste Linie CSP, zweite Linie `CryptoKey` | eine Fable-Sitzung in 12b; das Lebensdauermodell „ein Tab, ein Schlüssel" ändert sich (Handbuch) | **Ja, in 12b nach der CSP** — E-R17-08, Nr. 370 |
| Q-R17-04 | **S11: Zweck „Server" oder „Server und Gerät"?** (F-R17-13) | SP-9 verschlüsselt vor dem Upload: Das schließt Datenbank, Backup, Paket, Transport — nicht das verlorene Gerät, dessen Puffer Klartext bleibt (Garmin samt Reanimation, ohne Frist). „Beim Schreiben" baut die Anzeige der Uhr um (`Track.readPoints()`) und die Phasenkoordinate des Handys | **Zweck Server.** S11 benennt das Gerät als Restrisiko und gibt geparkten Paketen eine Frist (F-R17-28); „beim Schreiben" ein eigener späterer Schritt | S11 wird kleiner und ehrlicher; das Gerät bleibt, was es heute ist | **Zweck Server** — E-R17-09 |
| Q-R17-05 | **S11: Was darf Klartext bleiben?** | Der Server braucht Zeiten, `seq`, Kennungen, Strecke und Anstieg (Zahlen), Stammdaten; Besatzung und Einsatzmittel sind nicht Teil von R78. Die Zählung „Fahrten je Klinik" braucht das Transportziel im Klartext — R78 hat es ausdrücklich in den Umfang genommen | **Zeiten, `seq`, Kennungen, Strecke, Anstieg, Stammdaten, Besatzung, Einsatzmittel bleiben Klartext; das Transportziel wird verschlüsselt und die Zählung je Klinik wandert in den Browser** (F-SP-7) | die Statistik je Klinik rechnet nach dem Entsperren, nicht mehr serverseitig | **Wie empfohlen** — E-R17-10 |
| Q-R17-06 | **Darf ein Admin ein BetreiberIn-Konto sperren, löschen oder umadressieren?** (F-R17-07) | Heute ja, nur die letzte BetreiberIn ist geschützt; die Rollenprobe misst den Fall nicht | **Nein** — Riegel in `admin_user.php`, Zeile in der Rollenprobe; Paket in 12b | klein | **Nein, Riegel bauen** — E-R17-11, Nr. 371 |
| Q-R17-07 | **Nr. 263 (Grenze der Integritätswache): Stand `nicht umsetzen`?** | Die Abhilfe wäre eine Schnittstelle zum Hashen der eigenen Quelldateien — ein neuer Angriffsweg für ein Problem, das der Vergleich nur teilweise löst (Modell G5, 6.6) | **Ja**, `nicht umsetzen` mit der Begründung im Modell; stattdessen F-R17-15 (Anleitung, Alarmweg) und F-R17-18 (lesende Probe gegen Produktiv) | die Grenze bleibt und ist benannt | **Offen lassen für 12b** — E-R17-12 |
| Q-R17-08 | **`Review-Krypto-Sicherheit.md` — bleibt sie?** Der Rahmenplan sagt, sie „kann im Bedrohungsmodell aufgehen" | Ihr Abschnitt 2 ist durch das Modell ersetzt, ihre Befunde K-1 bis K-16 sind teils erledigt, teils in 12b zu lesen (F-R17-30) | **Bleibt bis zum Abschluss von 12b** als Protokoll mit Datum im Kopf; 12b trägt je K-Nummer den Stand nach und löscht sie mit P6 | keiner | **Jetzt löschen** — E-R17-13; der Stand je K-Nummer steht in 3.4 |

**Zweite Runde — die Freigaberunde von 12b, gestellt mit R17-17 am
21.10.2026.** Grundlage ist 7.3.12: die Fundliste (a) mit einem Paket je
Nummer und der Paketschnitt-Entwurf (e). E-PV-5 lässt die Betreiberin
**je Fund** entscheiden; damit das nicht 59 Fragen werden, ist die erste
Frage die Liste als Ganzes — **wer eine Nummer anders haben will, nennt
sie** —, und die übrigen zehn sind die Stellen, an denen der Vorschlag
eine Wahl enthält, die sichtbar etwas ändert (einen Umfang, ein Verhalten
für NutzerInnen, eine Reihenfolge). Die Antwort je Frage wird
E-R17-NN; danach wird der Schnitt verbindlich, die Pakete bekommen ihre
Zeilen in Abschnitt 4 (R17-18 ff.), die Backlog-Kopfzeilen ihr Ziel, der
Rahmenplan seine Fahrplanzeilen. **Halt:** Bis die Antworten da sind,
ändert R17-17 nichts außer diesem Abschnitt.

| Nr. | Frage | Lage | Empfehlung | Preis | Antwort |
|---|---|---|---|---|---|
| Q-R17-09 | **Der Paketschnitt A bis K und die Zuordnung je Befund (7.3.12 (a), (e)) — so annehmen?** Jede Nummer, die in ein anderes Paket soll, nicht gebaut werden soll oder deren Vorschlag anders ausfallen soll, bitte nennen. | 59 Befunde, 0 kritisch; 31 mit Fable (A, B, D, F, H), 23 mit Opus (C, E, G, I, J, K), 2 allein bei der Betreiberin, 3 ohne Paket. Die fünf hohen: F-R17-01, -02 in A, -03 in F, -04 und -05 bei der Betreiberin | **Ja**, mit den Antworten auf Q-R17-10 bis -19 als Änderungen | elf Pakete; jedes eine Sitzung, K je Codebasis eine; fünf davon Fable | |
| Q-R17-10 | **Reihenfolge:** zuerst F (Kette) und J Teil 1 (F-R17-58), dann A, D, C, B, E, H, G, J Teil 2, K, zuletzt I? | F trägt den einen hohen Codebefund (F-R17-03) und die Lieferkette des Uhr-Pakets (F-R17-20); A die beiden anderen hohen. K fasst dieselben Dateien noch einmal an und soll nach den Korrekturen laufen; H braucht die scharfe CSP auf Produktiv (E-R17-08, F-R17-23) | **Ja** — mit einer Freiheit: Ein Paket, das wartet (H auf die CSP, I auf Q-R17-16), lässt das nächste vor | keiner; die Reihenfolge ist ein Vorschlag, kein Riegel | |
| Q-R17-11 | **F-R17-58 als vorgezogenes Paket jetzt**, vor allen anderen — die vier öffentlichen Texte, eine neue Fassung von AVV und Datenschutzerklärung mit Ankündigung, die Textprobe-Falle? | Der Vertragstext und die Seite vor der Anmeldung sagen seit vier Monaten das Gegenteil der Datenschutzerklärung daneben; kein Datenrisiko, aber eine falsche Zusage nach außen. Umsetzbar in einer Sitzung, ohne Kryptographie | **Ja**, als erstes Paket (J Teil 1, Opus) | eine neue AVV-Fassung sperrt den Login, bis jede NutzerIn sie erneut annimmt (`Technik.md` 4.99j) — vorher die Ankündigung setzen; die Betreiberin spielt die Texte auf Produktiv ein (P-R17-13) | |
| Q-R17-12 | **F-R17-01: nur die Zusage berichtigen (a), oder auch der Pfeffer vor bcrypt (b)?** | (a) macht `CLAUDE.md` 4 und `Technik.md` 3 wahr: Ein Datenbankabzug erlaubt das Offline-Raten der Token weiter. (b) macht die alte Zusage wahr: Der Server hasht `HMAC(konto_anteil, token)`, und das Raten braucht `config.php` — drei Dateien, stille Umstellung beim nächsten Anmelden | **(a) und (b)** in Paket A | ein Konto, das sich nach dem Paket nie mehr anmeldet, bleibt beim alten Hash bis zum nächsten Reset; die Ratenprobe und die Rückwegprobe B7 messen den Weg | |
| Q-R17-13 | **F-R17-12 (2): den Alt-Weg der Uhr abschaffen** — `deviceId` und `apiKey` als Einstellungen, `credentials()` liest sie, wenn `cred` fehlt? | Seit Uhr 3.0.0 füllt die Kopplung den Weg nicht mehr; er trägt Zugangsdaten durch Garmin Connect und ist in den Einstellungen sichtbar (7.3.8). Abschaffen heißt: die zwei Einträge aus `settings.xml` und `properties.xml`, der Zweig in `credentials()`, das Leeren in `lokalTrennen()` | **Ja**, in Paket D (Fable) | eine Uhr, die vor Uhr 3.0.0 gekoppelt und seither nie neu gekoppelt wurde, muss einmal neu koppeln — ein Satz im Handbuch und im Changelog; ob es eine solche Uhr gibt, weiß nur die Betreiberin | |
| Q-R17-14 | **F-R17-28: Frist auch für den Zustand „abgemeldet" auf der Uhr** (Pakete nach 30 Tagen verwerfen, wie geparkte seit E-S11-11), oder stattdessen der Satz im Handbuch, dass Trennen im Web auf der Uhr nichts löscht? | Nach 401 oder 403 bleibt heute jedes Paket ohne Frist im Klartext, bis jemand koppelt oder trennt (K-14) — eine verlorene, im Web gelöschte Uhr hält ihren Puffer unbegrenzt | **Frist**, dieselbe wie für geparkte (`PARK_FRIST_S`), in Paket D; den Handbuch-Satz dazu | ein Diensttag, der 30 Tage nach einer Abmeldung noch nicht gesendet ist, geht auf der Uhr verloren — heute bliebe er ewig, unverschlüsselt | |
| Q-R17-15 | **F-R17-33: die zweite Zeichenkette in `wh_auspacken()` setzen** (Dumps von vor S7 bleiben als eigene erkennbar), oder am v1.0-Schnitt (R60) auf eine Zeile kürzen? | Ob ein Komplett-Dump von vor Web 12.9.2 noch irgendwo liegt, weiß nur die Betreiberin; der Kommentar nennt den Fall „den gefährlichsten Fehlschluss einer Umbenennung" | **Setzen** (eine Zeile, Paket B); die Kürzung mit R60 in P8 | keiner | |
| Q-R17-16 | **Nr. 380, 381, 382 (Seitenaufbau, Suche und Zeitraum, erste Antwort — Paket I): in P6, oder nach P7?** | Es sind Messposten aus S11-13 mit dem Vermerk „unmittelbar nach S11", keine Befunde des Reviews. 382 ist zuerst nachzumessen; 380 kann einen Bauschritt verlangen (Skripte zusammenfassen), den es heute nicht gibt; 381 ist ein Umbau von Suche und Zeitraum mit klarem Weg (E-S11-27) | **In P6 als letztes Paket**, in dieser Reihenfolge: 382 nachmessen, 381 bauen, 380 nur ohne neuen Bauschritt (je Seite laden, was sie braucht) — ein Bauschritt wäre eine Frage für P7 | eine Opus-Sitzung mit Messstand vorher und nachher (F-S11-25); P6 wird um ein Paket länger | |
| Q-R17-17 | **Nr. 77: `update.php` als Weiche behalten** (302 auf `betrieb_updates.php`, Notausgang `php update.php`), und den Punkt damit schließen? | Die Adresse steht in Runbooks, Lesezeichen und in `WARTUNG_AUSNAHMEN`; die Seite selbst ist seit Web 15.2.0 aufgelöst (E-S8-05). Übrig ist allein die Frage, ob die Adresse bleibt | **Behalten** und Nr. 77 als erledigt austragen („aufgelöst, die Weiche bleibt") | keiner | |
| Q-R17-18 | **Nr. 263 (die Wache sieht nur Öffentliches): jetzt schließen** mit Stand `nicht umsetzen`? | Q-R17-07 ließ den Punkt für 12b offen; Stück 10 hat ihn bestätigt und nichts Neues gefunden (7.3.10); die Grenze steht im Modell (E-R17-12), die zwei Abhilfen daneben — F-R17-15 und -18 — liegen in Paket F | **Ja**, `nicht umsetzen` mit Verweis auf das Modell und Paket F | keiner | |
| Q-R17-19 | **Kommentardurchgang (Paket K): alle fünf Codebasen, auch `tools/` und `.github/`, und `version.php` sowie `version.properties` ausgenommen?** | 11 855 Verweise; 3 263 in den Werkzeugen, wo 84 Dateien eine Anlass-Zeile tragen, die bleiben muss (Grundsatz 5); 895 in den zwei Fassungsdateien, die der Changelog in Kommentarform sind und deren Form P7 neu bestimmt (R15) | **Alle fünf**, Werkzeuge mit der Anlass-Regel; die zwei Fassungsdateien nicht anfassen — P7 entscheidet ihre Form | K ist das größte Paket; gefächert je Codebasis (getrennte Dateien), Prüfarbeit und Changelog seriell | |

**Was daneben bei der Betreiberin liegt — keine Frage, eine Liste:**
P-R17-04 (Rulesets für den Zweig `produktion` und die Umgebung
`produktion`, Zweitfaktor der Organisation, Produktiv-Einstellungen —
F-R17-04, -14), P-R17-12 (keine `config.php`-Kopie auf Staging und
Produktiv), P-R17-13 (die vier öffentlichen Texte auf Produktiv), die
vier Zeilen in Rahmenplan 6.1 (F-R17-05), **CSP scharf und HSTS auf
Produktiv** (F-R17-23 — Voraussetzung für Paket H, E-R17-08) und eine
Zahl: wie lange der Hoster Zugriffslogs hält (F-R17-22, -57, für die
Betriebsakte).

### 2.3 Befunde (F)

Je Befund: Nummer, **Schweregrad** (kritisch / hoch / mittel / niedrig /
Hinweis), Fundstelle (Funktionsname oder Abschnitt, nicht Zeilennummer),
was gesehen wurde, Wirkung, Empfehlung. **Der Review behebt nichts** (K4,
R69). Wege: kritisch → Sofortpaket vor allem anderen; alles andere →
Freigaberunde und Pflichtpaket in P6 (12b). Die Spalte **geprüft** sagt,
wer die Stelle gelesen hat: *Fable* heißt am Quelltext nachgeprüft, *Leser*
heißt Lesung des Lesers (Opus), nicht gegengelesen. Der Schweregrad ist ein
Vorschlag; **ob F-R17-01, -02 und -03 als Bruch einer Zusage „kritisch"
sind und ins Sofortpaket gehören, fragt R17-03 die Betreiberin** (Q).

| Nr. | Grad | Fundstelle | Gesehen → Wirkung | Empfehlung | geprüft |
|---|---|---|---|---|---|
| F-R17-01 | **hoch** | `CLAUDE.md` 4, `Technik.md` 3 („ein Abzug allein genügt seither nicht mehr"), Kopf `serverkrypto_lib.php`, SP-3 — gegen `login.php`, `pw_handling.php`, `api/kdf_upgrade.php` (`password_hash($token)`) | Das Anmelde-Token sind die PBKDF2-Bytes 32–63 **ohne** Anteil, bcrypt-gehasht in `users.password_hash`. Wer nur die Datenbank hat, rät weiter offline (je Kandidat PBKDF2 600 000 + bcrypt); der Anteil verhindert nur das Öffnen der Hülle **aus dem Abzug**. Ein gefundenes Passwort führt über eine Anmeldung (Konten ohne Zweitfaktor) zum Anteil und damit zu allen `pat_blob`. Die Zusage „kein Offline-Angriff auf das Passwort mehr" ist **falsch**; richtig ist „kein Öffnen der Hülle aus dem Abzug allein" | (a) Die Zusage in `CLAUDE.md` 4, `Technik.md` 3, SP-3 berichtigen — sofort, Doku. (b) Pfeffer vor bcrypt: Server hasht `HMAC(konto_anteil, token)` statt `token` — dann braucht auch das Offline-Raten `config.php`. Kleiner Umbau in drei Dateien plus stille Umstellung beim nächsten Anmelden (Muster `kdf_upgrade`). Fable-Schritt | Fable |
| F-R17-02 | **hoch** | `admin_user.php` Aktion `freigeben`; `adminbackup_lib.php` `edbak_freigeben()`; `api/adminbackup_freigabe.php`; `Review-Krypto-Sicherheit.md` 2 (I), 4.3 („reicht nur Chiffretext heraus") | Ein Admin gibt das Paket **jedes** Kontos an **jedes** Konto frei, auch an sein eigenes (Auswahl: alle außer dem Besitzer; nur die Zieladresse ist abzutippen); **kein** `protokoll()`, **keine** Mail an das Herkunftskonto, **kein** frischer Code (nicht in `ZF_FRISCH_HANDLUNGEN`). Der Endpunkt entsiegelt und liefert dem Zielkonto Klartext-JSON aller Einträge, Diensttage und Spuren samt `pat_wrap_rc`, `pat_key_check`, Adresse und Name. `pat_blob` bleibt zu. **Wirkung:** Lage I liest still Ort, Zeit, Klinik, Reanimation, Besatzung, `bw_info`, `days.notes` jedes Kontos — genau den Abzug, den Nr. 286 der Rolle admin genommen hat | Freigabe nur an das Herkunftskonto selbst oder an ein Konto mit **derselben** Adresse (der Zweck ist „Konto neu aufgesetzt"), Protokolleintrag mit Urheber und Ziel, Mail an Herkunfts- und Zieladresse, frischer Code. Rollenprobe um den Fall erweitern | Fable |
| F-R17-03 | **hoch** | `tools/kette/appbau.sh` `android_bauen()`; `auslieferung.yml` Job `android`, Schritt „Bauen, signieren, Zertifikat prüfen" — gegen `Technik.md` 4.97g, `android/LIESMICH.md` 5, Kopf `appbau.sh` (E-PK-63 „Gradle sieht den Schlüssel nie"), `Pruefablauf.md` 2.5 | Die vier `APK_*`-Geheimnisse stehen im `env:` des Schritts; `./gradlew … assembleRelease` läuft darin als Kindprozess und erbt Keystore (Base64) und beide Passwörter — `System.getenv()` genügt jedem Plugin und jeder Abhängigkeit zur Bauzeit; Gradle-Abhängigkeiten ohne `verification-metadata.xml`. Die Zusage hält nur für die Datei auf der Platte. Der Schlüssel liegt auch bei Google und ist nicht rücksetzbar: Verlust = jede spätere Fassung „eine andere App" | Gradle in einem **eigenen Schritt ohne** die `APK_*`-Werte fahren (oder `env -i` mit Allowliste), Signatur im Folgeschritt; `gradle/verification-metadata.xml` erzeugen und prüfen lassen; Doku berichtigen. Selbstprobe von `appbau.sh` um „Umgebung des Gradle-Aufrufs enthält kein `APK_`" erweitern | Fable |
| F-R17-04 | **hoch** | `auslieferung.yml` `workflow_dispatch` (Probelauf, App-Probelauf); `ausliefern-lauf.yml` `environment: ${{ inputs.umgebung }}`; Rahmenplan 6.1 („Umgebung `staging` beschränken" offen; für `produktion` nennt kein Dokument eine Zweigbeschränkung) | Ein Probelauf fordert die Umgebung `produktion` **von jedem Zweig** an, mit der Workflow-Datei dieses Zweigs. Alle übrigen Riegel (Tag = Fassung, grüne Läufe, Abstammung, Backup-Tor, Trockenlauf) stehen in dieser Datei, die der Zweig mitbringt. Zwischen einem Konto mit Push-Recht (C2) und den Produktiv-Geheimnissen (FTP, `JOBS_TOKEN`, App-Schlüssel) steht **ein Klick** der Betreiberin auf einen Lauf, dessen Inhalt sie nicht liest | Deployment-Branch-Regel für `produktion`: nur Tags `web-v*`, `android-v*`, `uhr-v*` (und für Probeläufe `main`); dasselbe für `staging` (6.1). Zuarbeit der Betreiberin, kein Code. Die Doku nennt die Regel als Voraussetzung der Freigabe | Fable |
| F-R17-05 | **hoch** | Rahmenplan 6.1 (seit 14.09.2026: Server-Anteil anlegen, Schlüsselblatt drucken, Wiederanlaufpaket ergänzen; seit 25.09.2026: Komplett-Stände öffnen); 6.3 (Probe-Wiederherstellung) | Die vier Pflichtstücke des Wiederanlaufs (`CLAUDE.md` 4: „Pflicht, nicht Empfehlung") sind laut Steuerung auf Produktiv nicht erledigt. Solange der Anteil nicht angelegt ist, gilt die S10-Zusage auf Produktiv nicht; solange kein Blatt gedruckt ist, ist der Verlust von `config.php` ein Passwort-Reset für alle — und ohne geöffneten Stand ist kein Rückfallstand belegt. Das Modell kann nur sagen, was die Anlage **könnte**, nicht, was sie tut | Die vier Zeilen in 6.1 abarbeiten, mit Datum; danach einen Komplett-Stand auf einem Wegwerf-Webspace einspielen (Prüfliste S2). Kein Code | Fable (6.1 gelesen; den Zustand von Produktiv kann niemand hier messen) |
| F-R17-06 | mittel | `pw_handling.php` (Reset-Zweig: nur `key_check == pat_key_check`); `admin_user.php` (`konto` Adresswechsel ohne Bestätigung, `pw_reset`, `totp_zuruecksetzen`); `Technik.md` 3 („Eine Admin-Passwortvergabe existiert bewusst nicht"); Review 2 (I) | Kette für ein `user`-Konto: Paket an sich freigeben (F-R17-02) → `pat_key_check`; Adresse auf eine eigene ändern; Setz-Link; auf `pw_handling.php` neues Token, neues Salz und eine beliebige formgerechte Hülle mit bekanntem `key_check` senden — der Server nimmt sie an, der Wiederherstellungsschlüssel war nie im Spiel; 2FA zurücksetzen. Ergebnis: Anmeldung, Klartext und Geräte des Kontos übernommen; `pat_blob` bleibt zu, `pat_wrap_pw` zerstört (Opfer braucht den Zettel). Spuren: Protokoll, Mail an die alte Adresse. 4.99q benennt die Schwäche von `pat_key_check` richtig, Abschnitt 3 und die Review nicht | Mit F-R17-02 entfällt der erste Schritt, nicht die Kette: Adresswechsel durch Admin mit Bestätigung der **alten** Adresse (oder Karenz), Setz-Link nur an bestätigte Adressen, frischer Code für Adresswechsel und Setz-Link. Den Satz in `Technik.md` 3 auf „nicht im Browser" eingrenzen | Fable |
| F-R17-07 | mittel | `admin_user.php` Aktionen `konto`, `pw_reset`, `konto_status`, `user_delete`; Matrix 4.99p (`{ziel}` = `user`) | Ein Admin kann ein **zweites BetreiberIn-Konto** sperren, löschen oder umadressieren; Riegel gibt es nur für Rollenwechsel und die letzte BetreiberIn. Ob das gewollt ist, steht nirgends | Entscheiden (Q in R17-03): Admin darf BetreiberIn-Konten nicht anfassen — dann Riegel in `auth_guard`/`admin_user.php` und Rollenprobe. Oder gewollt — dann in 4.99p sagen | Leser |
| F-R17-08 | mittel | `admin_komplettsicherung.php` (`herunterladen`, `art=klar`); `db.php` `ZF_FRISCH_HANDLUNGEN`; `Technik.md` 4.97d („geht an die AdministratorIn, die … jede Zeile sehen kann") | Die BetreiberIn lädt den **entsiegelten** SQL-Dump (Hashes, Hüllen, `app_state`-Geheimnisse, alle Klartextspalten aller Konten, offene Mailrümpfe) **ohne frischen Code** — eine übernommene Sitzung oder ein gemerkter Rechner genügt für einen Lage-A-Abzug, während das Schlüsselblatt den frischen Code verlangt. 4.97d nennt die falsche Rolle (seit Nr. 286 BetreiberIn) und eine Begründung, die in der Anwendung nicht gilt | Download „klar" in `ZF_FRISCH_HANDLUNGEN`; 4.97d berichtigen | Leser |
| F-R17-09 | mittel | `geocoder_lib.php` (Vorgabe an), `assets/geocoder.js` (`?q=`, `/reverse?lat=&lon=`), `map_layers.js`; `CLAUDE.md` 4 („Keine fremde Quelle zur Laufzeit"; „Klartext dieser Felder geht nie …"); `Technik.md` 4.98 | Getippte Einsatzadresse und gewählte Koordinate der **geschützten** Felder `loc.*`/`start.*` gehen im Klartext an `photon.komoot.io`; der Kartenausschnitt um den Einsatzort an vier Kacheldienste. Das Handbuch (12.5) sagt es, die beiden Dokumente, die die Zusage formulieren, nicht; „keine fremde Quelle" meint Skripte, nicht Verbindungen | In `CLAUDE.md` 4 und `Technik.md` 4.98 einen Satz: welche geschützten Angaben an welche Dritten gehen und wo der Schalter steht. Das Modell führt es als Lage G | Leser (Handbuch 12.5 bestätigt) |
| F-R17-10 | mittel | `crypto.js` (CK nie neu); `einstellungen.php` Passwortwechsel; `api/schluessel_erneuern.php`; `Technik.md` 4.99q („heute wechselt er nie") | Der Inhaltsschlüssel wird nie rotiert: Wer ihn einmal hatte (XSS, Tab, Sitzung), liest mit jedem späteren Abzug alle alten **und neuen** Blöcke; ein Passwortwechsel heilt nichts. Dazu ersetzt, wer Passwort und Sitzung hat, `pat_wrap_rc` über `api/schluessel_erneuern.php` durch eine eigene Hülle — nur Protokoll (für `user` unsichtbar), **keine Mail**, anders als beim Rückweg | Als Restrisiko ins Modell (5); Mail bei `schluessel_erneuern`; eine Umschlüsselung des Bestands ist S11-Material (dort wird ohnehin neu verschlüsselt) | Leser |
| F-R17-11 | mittel | `watch/source/Uploader.mc` `_serverUrl()` | `if (u.find("http") != 0) { u = "https://" + u; }` — ein eingetragenes `http://…` bleibt `http://`. `JSON-Vertrag.md` nennt `https://`; Android erzwingt es doppelt (Nr. 142). Mit `http://` gingen `X-Api-Key`, Spur, Phasen und Reanimation ab dem Telefon unverschlüsselt | `http://` auf `https://` heben oder abweisen; eine Zeile | Fable |
| F-R17-12 | mittel | `watch/resources/settings/properties.xml` (`serverUrl`, `deviceId`, `apiKey`); `Uploader.mc` `credentials()`; `Communications.makeWebRequest`; `Technik.md` 4.99b, 4.99a2; `android/LIESMICH.md` („Warum kein Pinning") | Drei Dinge, die kein Modell nennt: (1) Die Serveradresse der Garmin ist in der Garmin-Connect-App änderbar — wer dorthin kommt, lenkt Schlüssel und alle ungesendeten Daten an einen fremden Server; (2) der Alt-Weg legt den API-Schlüssel in die App-Einstellungen von Garmin Connect; (3) **jede** Garmin-Anfrage mit Schlüssel und Klartext-Rumpf wird von der Begleit-App des Telefons ausgeführt, die TLS-Prüfung liegt außerhalb der App | Als Lage M (Transport über die Begleit-App) ins Modell; Alt-Weg abschaffen (die Kopplung füllt ihn seit Uhr 3.0.0 nicht mehr, F-R17-29); Serveradresse nur noch in der Kopplung setzen oder gegen die gekoppelte Anlage prüfen | Leser |
| F-R17-13 | mittel | `Vorbereitung-Sicherheitspaket.md` SP-9 Punkt 6 („ein Verlust kostet das Trennen, nicht mehr"); `Review-Krypto-Sicherheit.md` 2 Lage H („Lokal: Spuren und Zeiten"); `Model.mc`, `Track.mc`, `puffer.db` | SP-9 verschlüsselt **vor dem Upload**; der Puffer auf dem Gerät (Spur-Chunks, Phasen, auf der Garmin auch der **Reanimationsverlauf**) bleibt Klartext, bis das Paket bestätigt ist. SP-9 schützt Server und Datenbank, nicht das verlorene Gerät; für Lage H müsste beim **Schreiben** verschlüsselt werden, was `Track.readPoints()` (Polylinie, Teil-Chunk) und `Puffer.punktNaheZeit()` (Phasenkoordinate per SQL) umbaut. Ferner: Der öffentliche Schlüssel nur in der `bestaetigen`-Antwort ginge verloren — beide Geräte schließen die Kopplung auch über `status` = `gekoppelt` ab | Entwurfsfrage für S11 (Modell 6): Zweck „Server" oder „Server **und** Gerät"? Lage H im Modell um den Reanimationsverlauf ergänzen. Schlüsselweg auch in `status`/`gekoppelt` und `ingest`-Antwort | Leser |
| F-R17-14 | mittel | Zweig `produktion` (`GET …/rules/branches/produktion` = `[]`, 11.10.2026); `.claude/settings.json`; `CLAUDE.md` 3 („wird nicht von Hand bewegt"); Rahmenplan 6.1 (Schutz offen) | Der Zeiger, gegen den die Wache vergleicht, hat **keine** Regel; die Deny-Liste sperrt nur Merge und Auto-Merge — `push_files`, `create_branch`, `run_workflow`, `cancel_workflow_run`, `delete_workflow_run_logs` bleiben, alle unter der Identität der Betreiberin. Wer den Zeiger auf einen manipulierten Stand schiebt, macht die Wache zur Falschmeldung | Ruleset für `produktion`: nur die App der Kette darf schieben (oder: Bypass nur `github-actions`); `push_files` und `actions_run_trigger` in die Deny-Liste. Zuarbeit | Fable (API) |
| F-R17-15 | mittel | `tools/integritaetswache/LIESMICH.md`; `Technik.md` 7 (Runbook „Wenn sie rot wird"); `wache.py` (Alarmtext) | Die Anleitung beschreibt eine andere Wache: „jede Datei unter `server/`", „FTPS-Zugang", Ausnahmeliste mit `doku/`. Tatsächlich HTTPS ohne Zugangsdaten, nur `assets/` und `login.php`. Runbook und Alarmtext verweisen auf einen Abschnitt „Wenn sie rot wird", den es nicht gibt — im Ernstfall liest die Betreiberin eine falsche Anleitung | LIESMICH neu schreiben, den Abschnitt anlegen; Alarmweg klären (an wen geht ein roter geplanter Lauf?) | Fable |
| F-R17-16 | mittel | `tools/kettenaufrufe/LIESMICH.md` („jede `uses:`-Zeile") gegen `pruefen.py` (kein „uses"); `CLAUDE.md` 3 („acht Pfade, die Zahl ist der Prüfwert", „keine fremde Zeile ohne SHA") | Weder das SHA-Pinning noch die Ausnahmeliste misst ein Werkzeug — nur der Handbefehl aus `CLAUDE.md`. Zwei Prüfwerte ohne Prüfmittel, und die Anleitung des einen Werkzeugs behauptet das Gegenteil | Riegel in Stufe 1: `uses:` ohne 40-Hex-SHA = rot; Ausnahmeliste gegen `.gitignore` und eine Sollliste = rot. LIESMICH berichtigen | Leser |
| F-R17-17 | mittel | `auslieferung.yml` Jobs `android`, `uhr`; `CLAUDE.md` 3 („Der Produktionslauf verlangt einen grünen Stufe-1-Lauf") | Für App-Tags gibt es weder Stufe-1-Nachweis noch Abstammungsprüfung — ein `android-v*` von einem nie geprüften Commit braucht nur Tag = Fassung und die Freigabe. `CLAUDE.md` 3 liest sich, als gälte das Tor für alle drei Tags | Tore `freigabe.py`/`baumsuche.py` auch vor die App-Jobs; bis dahin den Satz in `CLAUDE.md` 3 eingrenzen | Leser |
| F-R17-18 | mittel | Stufe 2 „Punktdateien gesperrt?"; `CLAUDE.md` 3 („Staging belegt kein Plattformverhalten von Produktiv") | Die Sperre der Punktpfade (`.deploy-state-*.json`, `.sitzungen/`, `.well-known`) ist **nur auf Staging** gemessen — bei einem anderen Hoster. Auf Produktiv läuft nichts davon; die 403 dort ist nie belegt | Lesende Probe gegen Produktiv (drei `GET`, Erwartung 403) in die Wache oder in den Produktionslauf nach dem Abgleich | Leser |
| F-R17-19 | mittel | `wiederherstellen.php` (ruft `edbak_ablage_bereit()` nicht); `admin_komplettsicherung.php` (Dateiname `…-YYYY-MM-DDTHH-MM-SS.sql.gz`); Runbook 7 Schritt 4 und 8 | Auf frischem Webspace fehlt `sicherungen/.htaccess` (entsteht erst zur Laufzeit); eine nach `eingang/` gelegte **Klartext**-`.sql.gz` (vom Runbook erlaubt) ist per HTTP abrufbar, ihr Name sekundengenau ratbar; Schritt 8 räumt laut Text nur Dump und Nachweis, nicht die Quelldatei | `wiederherstellen.php` ruft `edbak_ablage_bereit()` zuerst; Runbook: nur `.edk` in `eingang/`, Quelldatei im Aufräumschritt | Fable |
| F-R17-20 | mittel | Job `stufe2` (`pip install cffi cryptography`, `npm install playwright@1.56`); `tools/uhr-pruefstand/pruefstand.sh` `sdk_holen` (SDK ohne Prüfsumme); `android/gradle/` (keine `verification-metadata.xml`); E-PK-54 | Die 14 Aktionen sind gepinnt, die Pakete in `run:` nicht — im selben Job wie Staging-Passwort, `STAGING_TOTP`, `JOBS_TOKEN`; das SDK im Job mit dem Garmin-Schlüssel (Schlüssel erst im Folgeschritt). E-PK-54 begründet „kein Cache", nicht „keine Fassungsbindung" | `pip`/`npm` mit festen Fassungen und Hashes (`--require-hashes`, `package-lock`), SDK-Prüfsumme, Gradle-Verifikation (mit F-R17-03) | Leser |
| F-R17-21 | niedrig | `mail_lib.php` `mail_konto_entfernen()` gegen `konto_lib.php` `konto_loeschen()` | Der Docblock verlangt den Aufruf bei jeder Kontolöschung; es gibt **keinen** Aufrufer. Die Adresse eines gelöschten Kontos bleibt bis 30 Tage in der Warteschlange und in den Komplett-Ständen dieser Zeit | Aufruf in `konto_loeschen()`; Probe | Fable |
| F-R17-22 | niedrig | `jobs.php`, `tor.py adresse_bauen()`, `api/health.php` (`?token=`); `pw_handling.php`, `adresse_bestaetigen.php` (erster Abruf); `mail_lib.php` `mail_einreihen()` (Rumpf) | `JOBS_TOKEN` und Health-Token reisen als GET-Parameter in jedes Hoster-Zugriffsprotokoll; der erste Abruf jedes Setz- und Bestätigungslinks ebenso (Einladung 24 h gültig); offene Setz-Links stehen im Klartext in `mail_warteschlange.text` und damit in jedem Komplett-Stand. `Technik.md` 4.98 „Klartext-Reste" nennt den Rumpf nicht; `pw_handling.php` nennt das Log richtig | Jobs- und Health-Token als Kopfzeile statt Parameter; Rumpf der Warteschlange nach Zustellung leeren (geschieht) und in 4.98 nennen; Hoster-Logdauer in die Betriebsakte | Leser |
| F-R17-23 | niedrig | `kopfzeilen_lib.php` `kopf_csp_scharf()` (Vorgabe aus), `KOPF_HSTS_VORGABE = 1`; `Technik.md` 5c | CSP und `frame-ancestors` wirken erst nach dem Umschalten durch die BetreiberIn; HSTS 1 Tag. Dokumentiert — aber ob Produktiv umgeschaltet ist, steht nicht im Repositorium; Lage D ist bis dahin ungebremst | Stand von `csp_scharf` und `hsts_tage` auf Produktiv in 6.1 abfragen; Vorgabe nach der Probezeit auf scharf | Leser |
| F-R17-24 | niedrig | `Technik.md` 4.99 Zeile „Schlüsselbindung"; `keyguard.js` `verwerfeInhalt()`; `crypto.js` `merkeAbleitungen()` | Nach der Frist läuft nur `pck` ab; `edk` bleibt im `sessionStorage` bis Navigation oder Tabende; `edkvor` trägt nach jeder Anmeldung das Anmelde-Token (passwortgleich gegenüber dem Server) bis zur ersten Seite mit CK-Bedarf | Zeile in 4.99 berichtigen; `edkvor` nach dem Abholen sofort räumen (prüfen, ob es geschieht); Nr. 146 (`CryptoKey`) | Leser |
| F-R17-25 | niedrig | `CLAUDE.md` 4 Klartextliste („vollständig zu zitieren"); `mission_fields.php` (`bw_info` „Namen / Infos", `other_ema`) | Die Liste nennt `bw_info`, `other_ema`, `other_resources`, Schockraum, Winde, Stammdaten-Koordinaten und die Altbestand-Spalte `missions.notes` nicht; `Technik.md` 4.98 deckt die Klasse ab. `bw_info` ist der naheliegende Ort für einen Bedienfehler mit Namen (Kleinzeile „Klartext" steht) | Liste in `CLAUDE.md` 4 auf 4.98 verweisen lassen statt sie zu wiederholen; das Modell führt die vollständige Liste (I-07) | Leser |
| F-R17-26 | niedrig | Kopf `serverkrypto_lib.php`; `config.example.php`; Review 2 (B) | Der Serverschlüssel versiegelt auch **TOTP-Geheimnisse** und **Protokollarchive** — für Lage B heißt das: `config.php` + Datenbank öffnen den zweiten Faktor aller Konten. Steht in 4.99q, nicht an den Kopfstellen | Kopf und Beispielkonfiguration ergänzen; im Modell (4) als Wirkung von B | Leser |
| F-R17-27 | niedrig | `docs/Lizenzen.md` 6a („an genau einer Stelle: `WearNachrichtenweg.kt`"); `HandyHorcher.kt`, `UhrHorcher.kt` (`WearableListenerService`, `MessageEvent`) | Die Play-Dienste-Bibliothek steckt in **drei** Dateien; die Schnittstelle deckt das Senden, der Empfang kann prinzipbedingt nicht dahinter. `CLAUDE.md` 4 „auf eine Datei begrenzt" ist zu eng | Satz in `Lizenzen.md` 6a und `CLAUDE.md` 4 auf „Senden hinter der Schnittstelle, Empfang in zwei Horchern" berichtigen | Leser |
| F-R17-28 | niedrig | `watch/source/Uploader.mc` `istGeparkt()`, `verwerfen()`; `Model.mc` | Geparkte Pakete (400) und nach `abgemeldet` **alle** Pakete bleiben auf der Garmin ohne Frist; Android räumt nach 30 Tagen (AN-2). Ortsdaten liegen länger auf der Uhr, als irgendwo steht | Frist wie Android, oder im Handbuch sagen, dass Trennen im Web nichts auf der Uhr löscht | Leser |
| F-R17-29 | niedrig | Sammelbefund veralteter Stellen: `Technik.md` 4.99b Nr. 2 („bis Paket C"), 5a (Signaturschlüssel „außerhalb"), 6.1 („zwölf" `uses:`; „keine App-Tags"), 6.4 Schritt 2 („auf diesem Commit"), 6.5 (sieben Pfade, `.sitzungen/` „noch nicht", „beide Schritte", `STAGING_TOTP` fehlt); `.gitignore`-Kommentar; `Pruefablauf.md` 2.3 („15 Schritte"); `JSON-Vertrag.md` 1/1a (Klartext „nur auf der Uhr", „vom Admin vergeben", Platzhalter „‹Fassung›"); `watch/resources/settings/settings.xml` („wird beim Koppeln gesetzt"); `Vorbereitung-Play-Console.md` 4.2 (`WAKE_LOCK`); `docs/rechtstexte/AVV.md` Anlage III („Schlüssel in zwei Teilen"); SP-4 gegen Ruleset (0 Reviews, Bypass) | Je Stelle sagt die Doku einen Stand, den der Code nicht mehr hat. Keine davon ist für sich ein Sicherheitsproblem; zusammen lesen sie sich als Anlage, die anders ist als die beschriebene | Ein Aufräumpaket in 12b (Kommentardurchgang, R13); AVV-Satz als Rechtstext vor der nächsten Fassung berichtigen | Leser |
| F-R17-30 | Hinweis | `Review-Krypto-Sicherheit.md` 0, 2, 3, 4 (Stand Web 14.2.2) | Rundenzahl, Datenschlüssel mit Anteil, CSP, Zweitfaktor, Rückweg, Notzugang, K-4/K-7/K-8/K-9/K-11/K-15, Lage H (72 h), Lage I (Komplett nur BetreiberIn, aber Freigabe) — die Review ist an vielen Stellen überholt, ihre Zeilennummern stimmen nicht mehr. Das Modell ersetzt ihren Abschnitt 2 | Die Review bleibt als Protokoll (Datum im Kopf); das Modell verweist auf sie als Vorläufer, nicht als Quelle | Leser |
| F-R17-31 | Hinweis | Sammelhinweis: `pw_handling.php` liefert den Anteil an jeden gültigen Link (ohne Passwort; in SP-3 benannt); `ui_krypto_bootstrap()` trägt Hülle, Salz, Runden, Anteil in jede angemeldete Seite (gespeicherte Seite = Offline-Raten); `auth_salt.php`: Pseudosalz je Adresse stabil, echtes Salz ändert sich beim Reset; Zustandsdatei der Auslieferung im Webroot (Repositorium öffentlich); Klartext-Dump im Bauordner des Komplettbackups über viele Anfragen (dokumentiert); Wear-Uhr ohne Absenderprüfung (Signaturgleichheit vorausgesetzt); Handy wertet jedes 400 als fehlerhaft, 403 als „später"; Kommentar `netzwerk_pruefstand.xml` „zwei Adressen" bei dreien; Metadaten-Abfluss (Takt und Größe des Uploads verraten Dienst und Phasen auch bei TLS) | Kein Befund gegen Code oder Doku, aber Wissen, das ins Modell gehört (3, 5) | ins Modell; nichts weiter | Leser |

### 2.3a Befunde der Stücke 2 bis 12 (Schritt 12b)

Zählung fortlaufend ab **F-R17-32**. Jeder Befund ist von Fable am
Quelltext gelesen (E-R17-14); die Spalte „geprüft" entfällt deshalb, an
ihre Stelle tritt das **Stück**. Schweregrade und Wege wie in 2.3:
**kritisch** (ausnutzbar, Datenverlust, Bruch einer Zusage) → Sofortpaket;
**hoch** → Pflichtpaket vor v1.0; **mittel**, **niedrig**, **Hinweis** →
die Freigaberunde entscheidet; eine Kategorie „nach v1.0" gibt es nicht
(E-PV-5 (7)). Karteileichen, ungenutzter Code und die Fundstellen des
Kommentardurchgangs stehen **nicht** hier, sondern je Stück in 7.3 — sie
sind Aufräumarbeit, kein Befund, solange sie nichts kaputtmachen. Ein
Befund, der einen offenen Backlog-Punkt bestätigt oder einen der 31 aus 2.3
verschärft, nennt die Nummer und bekommt keine neue.

| Nr. | Grad | Stück | Fundstelle | Gesehen → Wirkung | Empfehlung |
|---|---|---|---|---|---|
| F-R17-32 | **mittel** | 2 | `api/kdf_upgrade.php`, Block „Nachweis" (`password_verify($altToken, …)` → 403 `nachweis`); zum Vergleich `api/schluessel_erneuern.php` und `api/kontopaar.php` (`rate_erlaubt('login', $email)` davor, `rate_misserfolg` beim Fehlschlag, Kommentar „ein Orakel") | Der Endpunkt tauscht Salz, Rundenzahl und Hüllen und verlangt dafür das alte Anmelde-Token. Er prüft es mit `password_verify()`, aber **ohne** `rate_erlaubt()`/`rate_misserfolg()` — die zwei Geschwister, die dasselbe Token prüfen, zählen in den Topf `login`, dieser nicht. Wer eine gültige Sitzung hat (Lage D, F; Modell 2), rät das Passwort hier online, ohne Sperre, gebremst nur von bcrypt (Größenordnung Sekundenbruchteile je Versuch); das Anmeldeformular und die Geschwister sperren nach wenigen Fehlversuchen. Ein gefundenes Passwort öffnet mit dem Anteil der Sitzung alle `pat_blob` (F-R17-01). Zusage „Ratenschutz, wo geraten werden kann" (7.2) gebrochen. **Verschärft in Stück 3 (R17-07):** dieselbe Lücke dreimal in `einstellungen.php` — Adresswechsel (`old_token`), Kontolöschung (`pw_loesch`), Passwortwechsel (`old_token`), je `password_verify()` gegen den Hash ohne einen `rate_*`-Aufruf (`grep` über die Datei: vier `password_verify`, die einzigen `rate_*` gehören zum Kopplungscode). Mit einer erbeuteten Sitzung rät man das Passwort also an vier Stellen ohne Sperre; die Seite liest Stück 5 ganz | `rate_erlaubt('login', $email)` vor der Prüfung (429 wie die Geschwister), `rate_misserfolg('login', $email)` vor dem 403 — an allen vier Stellen, besser als EINE Funktion `passwort_nachweis($userId, $token)` in `auth_guard.php`, die Topf und Vergleich zusammen trägt (dann gibt es keine fünfte Abschrift); ein Paket mit F-R17-01 (b). Die Zweitfaktorprobe um den Fall erweitern (ein dritter Fehlversuch → 429) |
| F-R17-33 | niedrig | 2 | `wiederherstellen.php` `wh_auspacken()`, Block „IST DIE DATEI VOLLSTÄNDIG?" (`$eigen = str_contains(…, 'Komplett-Backup der Installation') \|\| str_contains(…, 'Komplett-Backup der Installation')`); Erzeuger `komp_dump_schreiben()` in `komplett_lib.php`; Rahmenplan-Archiv-2 Schritt 4 (S7) | Der Kommentar sagt: zwei Schreibweisen, die alte („Komplettsicherung der Installation", bis Web 12.9.2) muss stehen bleiben, sonst gilt ein Dump von vor S7 als **fremd** und die Endmarke wird nicht verlangt — „der gefährlichste Fehlschluss, den eine Umbenennung anrichten kann". Genau das ist geschehen: Die Umstellung des Backup-Begriffs (S7, 03.09.2026) hat **beide** Zeichenketten ersetzt, die Zeilen sind wortgleich (PHPStan: `function.impossibleType`, „will always evaluate to false"). Wirkung: Ein Dump, der vor dem 03.09.2026 erzeugt und beim Erzeugen abgebrochen wurde, wird **ohne** Endmarkenprüfung eingespielt — halb, und die Seite zeigt dazu „fremder Dump". Betroffen sind nur Dumps aus der Zeit vor S7; ob noch einer liegt, weiß die Betreiberin (P-R17-06). Die Prüfliste S7 (Punkt 4, „Komplett-Backup aus der Zeit vor diesem Deploy einspielen") hat das nicht gesehen, weil ein **vollständiger** alter Dump auch als fremder durchläuft | Die zweite Zeichenkette auf `Komplettsicherung der Installation` setzen (eine Zeile) — oder, wenn kein Dump von vor S7 mehr existiert, am v1.0-Schnitt (R60) auf eine Zeile kürzen und den Kommentar mitnehmen. In beiden Fällen: eine Probe, die einen Dump mit altem Kopf und ohne Endmarke abweisen sieht (`tools/proben/komplett/`), und das Register `bestand` lernt die Regel „zwei gleichlautende Zweige in einem `\|\|`" nicht — das sieht PHPStan, nicht die Kette (7.4) |
| F-R17-34 | niedrig | 2 | `backup_lib.php` `edbak_restore()`, Block „Stammdaten" (`$name = (string)$b['name']` → `INSERT IGNORE INTO bases`); dagegen `pruef_rettungsmittel()` für `vehicles`, `pruef_text()` für Gerätekennungen, `pruef_kalendertag()` für Diensttage im selben Lauf | Standortname und Koordinaten eines Backups gehen ohne `pruef_text()`/`pruef_breite()`/`pruef_laenge()` in die Datenbank — kein Leerraumschnitt, keine Längengrenze, keine Zahlprüfung; der Rest desselben Laufs geht über die Prüfschicht. Zusage „alle Schreibwege, ohne Ausnahme" (`CLAUDE.md` 4) hat hier eine. Wirkung: ein Name über der Spaltenlänge bricht im **strikten** SQL-Modus den ganzen Lauf mit 500 ab (nichts geschrieben, Meldung ohne Feld), im laxen wird er still gekappt und passt danach nicht mehr zum `base_ref` der Rettungsmittel — die bleiben ohne Standort, gezählt als „übersprungen". Kein Fremdzugriff (eigenes Konto, gebundene Parameter), kein Verlust | `pruef_text($b['name'], <Spaltenlänge>, 'bases.name', $pruef)` und `pruef_breite`/`pruef_laenge` für die Koordinaten, Fehler zählen wie bei den Rettungsmitteln; vier Zeilen. Dieselbe Lesung für `bw_units` und `transport_dests`, falls sie dort ebenso stehen (nicht nachgelesen — der Block ist lang) |
| F-R17-35 | Hinweis | 2 | `adminbackup_lib.php` `edbak_teil_oeffnen()` („Ein Eintrag ohne `edsk1:` ist Fassung 2 und kommt unverändert zurück"); Aufrufer `edbak_paket_teil_lesen()`, `edbak_paket_kopf_lesen()`, `edbak_paket_einspielen()` | Das Präfix entscheidet je Teil, nicht die Fassung des Pakets: In einem Fassung-3-Paket wird ein Teil, dem jemand das Siegel abgenommen und den Klartext hingelegt hat, ohne Prüfung angenommen — das Siegel bindet Server, Konto, Paket und Teilnamen (AAD), der Rückfall auf Fassung 2 hebt die Bindung für diesen Teil auf. Wer das kann, hat Dateizugriff (Lage B) und damit ohnehin `config.php`; deshalb Hinweis, kein Befund. Bleibt stehen, bis es keine Fassung-2-Pakete mehr gibt | Beim Paketschnitt: das Manifest trägt die Fassung; ab Fassung 3 den Rückfall abschalten (`edbak_teil_oeffnen()` bekommt die Fassung mit, ein unversiegelter Teil ist dann `null`). Zwei Zeilen und eine Probe |
| F-R17-36 | **mittel** | 3 | `ratelimit_lib.php` `rate_merkmale()` (hängt immer `ip:` an), `rate_misserfolg()` und `rate_erlaubt()` (laufen über alle Merkmale eines Topfes mit EINEM `max`), `rate_erfolg()`; Aufrufer `login.php` (`rate_misserfolg('login', $email)`), `api/schluesselblatt_pruefen.php` (`rate_erlaubt('blatt', $email)`), `reset_request.php`; Kommentar in `RATE_GRENZEN` zu `login_ip` („`login` zählt dann nur noch das Konto") gegen den Kommentar im Fehlzweig von `login.php` („zählt seit jeher BEIDE Merkmale … das bleibt so"); `docs/Technik.md` 5e.2 („10 Fehlversuche je Konto, 50 je Anschluss"); `tools/proben/raten/probe.php` (misst `login_ip` nur isoliert über `klopfen('login_ip', …)`) | Ein Topf hat EINE Schwelle, aber `rate_merkmale($konto)` liefert zwei Merkmale — Adresse und Konto —, und `rate_misserfolg()` sperrt jedes davon bei `versuche >= max`. Im Topf `login` (max 10) ist damit nach zehn Fehlversuchen **von einem Anschluss** — gleich an welchen Konten — die Adresszeile gesperrt, `rate_erlaubt('login', $email)` findet sie und meldet „von diesem Anschluss"; mit Leiter 15, 20, 30, 60 Minuten. Der Topf `login_ip` (50, E-P5a-04, R37: Klinik-NAT) greift nie, weil 10 vor 50 kommt, und die Einstellung `ratenschutz_login_ip_max` ist unterhalb von `ratenschutz_login_max` wirkungslos. Dasselbe beim Schlüsselblatt: `blatt` (max 3) sperrt nach drei Fehlversuchen die Adresse — „je Konto und nicht je Adresse, damit eine gesperrte BetreiberIn die andere nicht mitsperrt" steht daneben und gilt nicht; und bei `reset` (5/h) sind es fünf Reset-Anfragen je Anschluss und Stunde. Wirkung: Verfügbarkeit (Lage A, Modell 2 — ein Fremder sperrt mit zehn falschen Passwörtern an erfundenen Adressen jeden Anschluss, hinter dem eine Klinik sitzt, für bis zu einer Stunde; die Sammelmail meldet es als Angriff), und die Doku verspricht eine Zahl, die der Code nicht hält. Die Probe `raten` hat es nicht gesehen, weil sie den Topf `login_ip` allein klopft, nie den Pfad `rate_misserfolg('login', $email)` | In `login.php` den Konto-Topf mit ausdrücklicher Merkmalsliste rufen — `rate_erlaubt('login', null, [rate_merkmal_kennung($email)])`, ebenso `rate_misserfolg` und `rate_erfolg` (das Muster von `ingest`/`ingest_ip` und `totp`); `login_ip` trägt dann allein die Adresse. Für `blatt` dasselbe; für `reset` entscheiden (die Adresszählung ist dort vielleicht gewollt — dann in den Kommentar). Oder grundsätzlicher: `rate_merkmale()` abschaffen und jeden Topf mit seiner Merkmalsliste rufen — dann kann der Fehler nicht wiederkommen. Probe `raten`: einen Fall „zehn Fehlversuche von einer Adresse an zehn Konten → Adresse NICHT gesperrt". `Technik.md` 5e.2 bleibt dann wahr; bis dahin ist sie falsch (Stück 11). Zwölf Zeilen und ein Probenfall |
| F-R17-37 | niedrig | 3 | `zweitfaktor_notweg.php` (`$merkmale = [rate_merkmal_kennung($email)]`, `rate_erlaubt('notweg', null, $merkmale)`, `rate_misserfolg('notweg', …)`; `notweg_passwort_passt()` mit `password_verify()` oder Blindvergleich); zum Vergleich `login.php` (`login_ip`, `rate_global_misserfolg()`), `reset_request.php` (Adresse im Merkmal), `pair.php` (Topf je IP) | Der Notzugang zählt **nur die eingetippte Adresse** — fünf je Stunde, mit Leiter. Wer die Adresse je Anfrage wechselt (erfundene reichen, der Blindvergleich rechnet trotzdem), bekommt je Anfrage eine bcrypt-Rechnung von rund einer Viertelsekunde CPU, ohne Anschlussgrenze und ohne den globalen Zähler, den die Anmeldung führt; Sitzung und Formular-Token holt sich ein Skript mit einem GET. Kein Zugang, kein Geheimnis — aber Rechenlast von einer Adresse auf einem Webspace mit wenigen Arbeitern (Lage A, Verfügbarkeit). Jeder andere unangemeldete Weg mit Passwort- oder Schlüsselvergleich hat eine Adressbremse (Anmeldung, Reset, Kopplung) oder eine Rechenaufgabe (Registrierung) | Zweites Merkmal `rate_merkmal_ip()` in den Topf (eine Liste wie bei `login_ip`, Schwelle etwa 20/h) und `rate_global_misserfolg()` im Fehlzweig, damit die Verlangsamung auch hier greift — drei Zeilen; ein Fall in der Probe `raten` |
| F-R17-38 | niedrig | 4 | `tageszuordnung_lib.php` `tz_tag_datum_aendern()`, der Rahmen `db_transaktion($pdo, function (PDO $pdo) use ($dayId, $delta, $mIds, $neuTag, $platzhalter, $rIds, $userId, $werte)`; die beiden Namen entstehen erst IM Rahmen, in der Zerlegung `foreach (sql_in_bloecke_sql($mIds) as [$platzhalter, $werte])`; `systemmeldung_lib.php` `system_fehler_behandeln()` (wirft nicht, trägt ein) | Das `use` fängt zwei Variablen ein, die an der Stelle nicht existieren. PHP 8 meldet das als Warnung „Undefined variable $platzhalter" und „… $werte" — bei JEDER Umdatierung eines Diensttags zwei Zeilen im Reiter System (der Behandler wirft nicht; nachgemessen mit `php -r`: Warnung, Lauf geht weiter). Die Funktion arbeitet richtig, weil die Zerlegung die Namen im Rahmen neu belegt. Seit Schritt 15 AP10 (22.09.2026); PHPStan sieht es als vier Meldungen (`closure.unusedUse` ×2, `variable.undefined` ×2). Wirkung: Lärm im Reiter System, der eine echte Warnung verdeckt | Die beiden Namen aus dem `use` streichen — zwei Wörter; die Meldungen von PHPStan verschwinden mit |
| F-R17-39 | **mittel** | 4 | `einsatz.php`, Block „DER SPURZUSTAND, EINMAL" (`$spurStand = spur_stand(…)`, `$spurPunkte = $spurStand['stufe'] === 1 ? spur_zahlen(…) : $spurStand['n_gespeichert'] + max(0, $spurStand['naechste_seq'] - $spurStand['n_original'])`, `$hatSpur = $spurPunkte > 0`; daran der Menüeintrag „GPS-Daten als GPX" und die Konstante `SPUR` für die Plakette); `spur_lib.php` `spur_stand()` (liefert `stufe`, `n_original`, `naechste_seq` — kein `n_gespeichert`, seit S11-12), `spur2_stand()`; `api/wartungslauf.php` und `assets/wartungslauf.js` (packen und dünnen aus, angestoßen von `index.php`); zum Vergleich `tag_spuren.php` (`array_sum(array_column($stuecke, 'punkte'))`) | `n_gespeichert` war das Feld des SPUR1-Blobs und ist mit Web 23.0.0 (S11-12, `0a6b926`) aus `spur_stand()` verschwunden; die Zeile in `einsatz.php` liest es weiter. Solange eine Spur nur Gerätestücke hat (Stufe 1), greift der erste Zweig, und alles stimmt — die GPX-Probe misst genau diesen Fall. Sobald der Wartungslauf die Spur gepackt (Stufe 2) oder ausgedünnt (Stufe 3) hat — und den stößt `index.php` bei jeder Anmeldung an —, greift der zweite: `null + max(0, naechste_seq − n_original)`, und das ist bei einer gepackten Spur ohne Nachzügler **0**. Folge auf jedem Konto, dessen Spuren der Wartungslauf erreicht hat: `$hatSpur` ist falsch, der Menüeintrag „GPS-Daten als GPX" fehlt, die Plakette „GPS-Daten (ausgedünnt)" fehlt, `SPUR.n` ist 0 — und jeder Aufruf der Einsatzseite schreibt „Undefined array key n_gespeichert" in den Reiter System. Die Karte zeichnet die Spur trotzdem (sie kommt aus der API). Kein Datenverlust, der Abruf geht weiter über die Spurenseite des Tages; aber ein Weg, den das Handbuch nennt, ist auf Produktiv seit Web 23.0.0 für jede gepackte Spur fort, und der Reiter System füllt sich. PHPStan: `offsetAccess.notFound` | Den Dreisatz durch `spur_zahlen(db(), 'mission', [$mid])[$mid] ?? 0` für ALLE Stufen ersetzen (die Summe der `punkte` ist seit SPUR2 die Zahl, die die Seite will — dieselbe Rechnung wie `tag_spuren.php`); eine Zeile. Die GPX-Probe um einen Fall erweitern, der die Spur vorher packt (`spur2_wartung_tauschen()` im Prüfstand), damit der zweite Zweig je gemessen wird. P-R17-08 sagt, ob Produktiv schon betroffen ist |
| F-R17-40 | niedrig | 4 | `api/import_commit.php` `import_commit()`, Block „Diensttage": `$imPapierkorb` (alle Daten mit `deleted_at IS NOT NULL`), dann je Tag `if (isset($imPapierkorb[$tag])) { $tageUebersprungen++; continue; }` VOR `$vorhandenQ` (der erste aktive Tag des Datums); Block „Einsätze": `$dayIdByDate[$tag] ?? 0` → `ohne_diensttag`; `check`: `SELECT … FROM days WHERE … deleted_at IS NULL AND day IN (…)` | Ein Kalendertag darf seit E9 mehrere Diensttage tragen, und die Sperre fragt nur: Liegt zu diesem DATUM ein Tag im Papierkorb? Liegt dort einer — etwa der zweite Tag einer Doppelanlage von Garmin und Handy, den jemand gelöscht hat —, überspringt der Import das Datum ganz, obwohl daneben ein aktiver Diensttag steht, den `check` dem Browser eben noch gemeldet hat. Alle Einsätze des Datums zählen dann unter `ohne_diensttag`, der Tag unter `days_skipped_trash`; die Meldung sagt „Papierkorb", die NutzerIn sieht in der Leiste einen aktiven Tag. Bis die Frist den Papierkorb leert (30 Tage), ist das Datum nicht importierbar. Verhaltensfehler, kein Datenverlust (es wird nichts übernommen) | Die Papierkorb-Sperre nur dann, wenn `$vorhandenQ` KEINEN aktiven Tag findet — die Abfrage zuerst, die Sperre in den `$dayId === 0`-Zweig; drei Zeilen. Ein Fall in der Importprobe (ein gelöschter und ein aktiver Tag am selben Datum) |
| F-R17-41 | Hinweis | 4 | `api/import_commit.php` `import_commit()`: die Vorprüfung `if (($m['dup'] ?? '') !== 'overwrite') { … preg_match('/^imp-[0-9a-f]{24}$/', $ref) … }` gegen den Zweig `if (($m['dup'] ?? '') === 'overwrite' && !empty($m['overwrite_id'])) { … } else { … 'client_ref' => (string)$m['client_ref'] … $insE->execute($p); }` | Die Kennungsprüfung läuft für alles außer `overwrite`; der INSERT-Zweig greift aber auch für `overwrite` OHNE `overwrite_id`. Eine Nutzlast mit `dup: 'overwrite'` und ohne Kennung legt so einen Einsatz mit leerer oder beliebiger `client_ref` an — ungeprüft gegen Form und Dublette. Nur das eigene Konto, nur mit einer Nutzlast von Hand (der Browser schickt `overwrite` stets mit Kennung); die Hülle bleibt an die mitgeschickte Kennung gebunden, der Browser öffnet sie damit. Kein Schaden für Dritte, aber zwei Prüfungen, die an der Zweigentscheidung vorbeigehen | Die Prüfung an dieselbe Bedingung hängen wie den Zweig (`dup === 'overwrite' && !empty(overwrite_id)`), oder `overwrite` ohne `overwrite_id` mit 400 abweisen; zwei Zeilen |
| F-R17-42 | Hinweis | 5 | `admin_user.php`: `DEMO_GESPERRT = ['konto', 'sichern', 'einspielen', 'freigeben', 'widerrufen', 'paket_loeschen', 'user_delete']` gegen die Schreibwege `konto_status`, `konto_grenzen`, `totp_zuruecksetzen`; das Markup sagt „Das Demo-Konto lässt sich nicht sperren" und blendet den Knopf aus; `demo_lib.php` kennt `status` nicht | Die Liste deckt drei Handlungen nicht: Ein POST von Hand sperrt das Demo-Konto (`konto_status`), setzt ihm Grenzen oder setzt den — ohnehin gesperrten — Zweitfaktor zurück; nur die Oberfläche hält den Knopf fern. Eine Sperre bleibt, weil der Reset den Status nicht zurücksetzt: Das öffentliche Konto ist zu, bis die Verwaltung es von Hand öffnet. Nur Admin und BetreiberIn, nur von Hand; kein Datenzugang | `konto_status` und `konto_grenzen` in `DEMO_GESPERRT` aufnehmen (eine Zeile), `totp_zuruecksetzen` ebenso — oder der Reset setzt `status` mit zurück. Die Rollenprobe (`tools/proben/rollen/`) prüft das Demo-Konto bisher nicht; ein Fall dort |
| F-R17-43 | niedrig | 5 | `einstellungen.php`, Zweig `$action === 'zf_codes_neu'` → `totp_codes_erneuern($userId)`; davor nur `csrf_check()`; `ZF_FRISCH_HANDLUNGEN` (`db.php`) kennt `totp_ausschalten`, `passkey_anlegen`, `passkey_entfernen`, nicht `zf_codes_neu`; `totp_codes_erneuern()` schreibt `protokoll('totp_codes_erneuert')`, keine Mail | Mit einer erbeuteten Sitzung lassen sich zehn neue Wiederherstellungscodes erzeugen und ablesen, die alten werden ungültig. Die Codes sind ein dauerhafter zweiter Faktor — E-SR-31 verlangt genau deshalb für das Anlegen eines Passkeys einen frischen Code („Wer eine fremde Sitzung erbeutet hat, soll sich damit keinen dauerhaften zweiten Faktor anlegen"); die Codes blieben dabei außen vor. Grenzen: Ohne Passwort öffnen die Codes nichts (Lage G im Modell, Passwort plus Sitzung); die Handlung steht im Protokoll; die rechtmäßige Person merkt es, wenn ihre Codes nicht mehr gelten — aber erst dann, und ohne Mail | `zf_codes_neu` in `ZF_FRISCH_HANDLUNGEN` aufnehmen und vor dem Zweig `zweitfaktor_frisch_verlangen('totp_codes_neu')` rufen — dasselbe Muster wie `zf_ausschalten`, zwei Zeilen; dazu die Mail, die `passkey_entfernt` schon hat (`mail_einreihen()`), damit der Wechsel die Person erreicht. Modell 3 (Zusage Zweitfaktor) um den Satz ergänzen |
| F-R17-44 | niedrig | 5 | `zeitraum.php`: `if ($monat !== '' && !preg_match('/^\d{2}$/', $monat)) { $monat = ''; }`, danach `$MONATSNAMEN[(int)$monat]` und `mktime(0, 0, 0, (int)$monat, 1, (int)$jahr)`; `api/range.php` prüft `^(0[1-9]\|1[0-2])$` | `zeitraum.php?y=2026&m=13` (ebenso `00`, `99`) besteht die Formprüfung: PHP-Warnung „Undefined array key 13" (im Reiter System als Art PHP), Titel „ 2026" ohne Monat, `mktime()` rechnet den Monat ins nächste Jahr; erst `api/range.php` weist ab, die Seite zeigt „Nicht geladen." Nachgerechnet mit `php -r`. Kein Datenzugang; ein Aufruf von Hand oder ein alter Verweis | Dieselbe Prüfung wie `api/range.php` (`^(0[1-9]\|1[0-2])$`), eine Zeile — oder die Weiche auf `index.php` wie beim Jahr |
| F-R17-45 | niedrig | 5 | `betrieb_statistik.php`, Zweig `export=csv`: `fputcsv($out, [$m['name'], $m['hersteller'], $m['art'], …], ';', '"', '')`; `$m['name']` aus `geraet_modell` oder `geraet_teil`, der Selbstauskunft des Geräts beim Koppeln (`pair.php`, `_geraet_kuerzen()` in `geraete_lib.php` entfernt nur Steuerzeichen und kürzt) — der einzige `fputcsv`-Schreiber im Bestand | Ein Modellname, der mit `=`, `+`, `-` oder `@` beginnt, wird in Excel und LibreOffice zur Formel (CSV-Injektion); den Namen setzt das Gerät, also eine veränderte App an einem beliebigen Konto, und die Datei öffnet die BetreiberIn. Excel fragt vor DDE und `HYPERLINK` nach; die Wirkung ist eine Täuschung oder ein Abfluss der Tabelle, kein Zugriff auf die Anlage. Die CSV der NutzerIn schreibt `assets/export.js` im Browser aus eigenen Daten (Stück 7) | Zellen, die mit `=`, `+`, `-`, `@`, Tabulator oder Wagenrücklauf beginnen, mit einem führenden Apostroph oder Tabulator entschärfen — als kleine Hilfsfunktion in `format_lib.php`, damit der nächste Export sie bekommt; dazu ein Fall in der Rollen- oder Statistikprobe mit einem Modellnamen `=1+1` |
| F-R17-46 | niedrig | 6 | `smtp.php`: `smtp_eingerichtet()` und `smtp_probe()` sagen „Ein Relais ohne Authentifizierung ist eine gültige Einrichtung"; `smtp_send()` schickt nach `EHLO` und `STARTTLS` immer `AUTH LOGIN`, dann `base64_encode($cfg['user'])` und `base64_encode($cfg['pass'])` ohne `??`; `config.example.php` trägt `user` und `pass` | Fehlen `user` und `pass` in `config.php`, schreibt jede Mail zwei PHP-Warnungen „Undefined array key" in den Reiter System, das Relais antwortet auf `AUTH LOGIN` mit 503 oder 500, `$expect` meldet „Antwort 503 statt 334", `$ok` wird falsch — die Einrichtung, die zwei Funktionen für gültig erklären, versendet nichts, und die Probe auf der Serverseite sagt es nicht. Kein Sicherheitsbruch; ein Widerspruch zwischen Zusage und Code, der erst beim ersten Versand auffällt, als Störung statt als Hinweis. Betreff und Empfänger sind unberührt: Alle Betreffe in `mail_katalog()` sind feste Texte oder Zahlen, kein Nutzertext geht in eine Kopfzeile | `AUTH LOGIN` nur senden, wenn `$cfg['user']` nicht leer ist (drei Zeilen in `smtp_send()`), und `smtp_probe()` den Fall mitmessen lassen — oder den Satz an beiden Stellen streichen und `smtp_eingerichtet()` `user` und `pass` verlangen. Welches von beiden, ist eine Betriebsfrage an die Betreiberin |
| F-R17-47 | niedrig | 6 | `.htaccess`: `<FilesMatch "^(config\.php\|config\.example\.php\|schema\.sql\|db\.php\|smtp\.php\|auth_guard\.php\|install\.lock)$">` sperrt sieben Dateien; `wartung_lib.php` schreibt `wartung.lock` und `ueberlast.json` nach `__DIR__`, also in den Webroot; `betrieb_updates.php` gibt `wartung_einschalten($userName ?: $userEmail)` den Namen oder die Anmeldeadresse mit, `wartung_lib.php` schreibt sie als `wer` in die Datei; `migrations/*.sql` ebenfalls nicht gesperrt | Während einer Wartung liefert `https://<host>/wartung.lock` jedem Abrufer ohne Anmeldung das JSON mit `seit` und `wer` — Name oder Anmeldeadresse des BetreiberIn-Kontos, das geschaltet hat; `https://<host>/ueberlast.json` liefert jederzeit den Zähler und die Zeitpunkte der Verbindungsgrenze. Beides sind Betriebsdaten, keine Patientendaten; die Anmeldeadresse eines BetreiberIn-Kontos ist eine Kontokennung (Lage A im Modell: ein Ziel für Raten und Ratenschutz), und der Zähler sagt, wann die Anlage am Limit ist. `migrations/*.sql` ist offener Quelltext, kein Geheimnis. Am Code gelesen, nicht gegen einen Apache gefahren (P-R17-10) | Beide Dateinamen in die `FilesMatch`-Liste (eine Zeile) — oder beide Dateien in ein Verzeichnis außerhalb des Webroots, wie `sicherungen/` es hält; `wer` in `wartung.lock` auf die Kontonummer kürzen, die Seite kann den Namen nachschlagen. Daneben, kein Befund: Der HTTPS-Zwang in `.htaccess` prüft `%{HTTPS}`, nicht `X-Forwarded-Proto`; hinter einem TLS-Proxy wäre das eine Schleife (`kopfzeilen_lib.php` kennt den Proxy, `.htaccess` nicht) — ein Satz in `Technik.md` 6, solange Produktiv und Staging ohne Proxy laufen |
| F-R17-48 | Hinweis | 6 | `wartung_lib.php`, `ueberlast_erkannt()`: Weg 1 `errorInfo[1]` gegen `UEBERLAST_CODES = [1040, 1203, 1226]`, Weg 2 `(int)$ex->getCode()` gegen dieselbe Liste, Weg 3 `preg_match('/\[(1040\|1203)\]/', $ex->getMessage())`; der Kommentar darüber: „Verlässlich ist nur die MELDUNG"; der Kommentar an `UEBERLAST_CODES` nennt 1226 als den Fall, den ein Hoster herstellt; `tools/proben/verbindung/probe.php` prüft den Bericht mit `\[(1040\|1203\|1226)\]` | Der Weg, den der Kommentar den verlässlichen nennt, führt die Nummer nicht, die der Kommentar daneben den wichtigen nennt. Wirkung heute keine: Beim Verbinden trägt `getCode()` mit PDO-MySQL die Treibernummer, Weg 2 greift, und die Probe ist grün — über Weg 2, nicht über das Muster, das sie selbst kennt. Fiele Weg 2 aus (ein Treiber, der den SQLSTATE in `getCode()` legt), wäre 1226 eine 500 statt der 503 mit Wartezeit | 1226 ins Muster (eine Zahl), und das Muster aus `UEBERLAST_CODES` bauen statt es daneben zu schreiben — dann sind drei Listen eine, und die Probe misst, was der Kommentar verspricht |
| F-R17-49 | niedrig | 6 | `ui.php`, `ui_meldung_markup()`: `$m .= ui_e($text)` — der Text wird maskiert, nur `$knopf` nimmt Markup; dagegen `betrieb_sicherheit.php`, Karte „Verlangsamung": `ui_meldung_markup('warn', 'Jede <strong>fehlgeschlagene</strong> Anmeldung … <strong>Wer das richtige Passwort hat, kommt …</strong> …')`, und `doku_seite.php`, Fall `$seite === null`: `ui_meldung_markup('warn', 'Die Datei <code>…</code> ist auf diesem Server nicht zu finden … <code>docs/</code> … <code>doku/</code>.', 'Dieses Dokument fehlt.')`; `grep -A4` über alle Aufrufer: diese zwei tragen Tags im Text, kein dritter | Sobald die Bremse auf Stufe 1 oder höher steht, zeigt die Sicherheitsseite der BetreiberIn `<strong>fehlgeschlagene</strong>` als Text; wer `server/` ohne `docs/` hochlädt — der häufigste Fehler beim Selbsthosten, den die Meldung gerade erklären soll —, liest `<code>docs/</code>` als Text. Dazu `e()` auf die Sekundenzahl in derselben Meldung: doppelt maskiert, harmlos. Dieselbe Klasse wie der Fehler, den `betrieb_jobs.php` im Kommentar beschreibt (sichtbares `<code>` bis Web 19.3.0); kein Sicherheitsbruch — die Maskierung tut, was sie soll | Beide Texte ohne Tags, die Hervorhebung über `$auftakt` (der dafür gebaute Weg) — und, weil es die dritte Stelle dieser Art ist, ein Riegel in Stufe 1: kein `<` in einem Textargument von `ui_meldung()` oder `ui_meldung_markup()` (`tools/zaehlung/`, eine Zeile mit Decke 0) |
| F-R17-50 | Hinweis | 7 | `server/assets/vendor/leaflet/leaflet.css`, Kopf: „SHA-256 a7837102…"; `.gitattributes`: `* text=auto eol=lf`, die einzige `-text`-Ausnahme ist `tools/proben/gpx/gpx11.xsd` — mit dem Kommentar, der genau diese Falle beschreibt; `git ls-files --eol`: `i/lf w/lf attr/text=auto eol=lf`; das npm-Original (`leaflet-1.9.4.tgz`, `dist/leaflet.css`, CRLF, 661 Zeilen) hat als SHA-256 den Kopfwert; der Rumpf im Repositorium (LF) hat 337bfca5…, mit CRLF zurückgewandelt wieder den Kopfwert, `diff -w` gegen das Original ist leer. `docs/Lizenzen.md` 3 führt vier Prüfsummen (`leaflet.js`, `xlsx.full.min.js`, `zipjs.min.js`, `qrcode.js`), `leaflet.css` nicht; kein Werkzeug rechnet die Köpfe nach (`bestand` prüft Herkunftszeilen, keine Summen) | Die Zusage aus CLAUDE.md 4 und Lizenzen.md 3 — „mit Herkunft und SHA-256 im Dateikopf" — ist für diese eine Datei auf keinem Klon nachrechenbar: Wer sie prüft, bekommt eine Abweichung und muss erst herausfinden, dass die Zeilenenden sie erzeugen, nicht ein fremder Eingriff. Kein Integritätsbruch — der Inhalt ist bis auf `\r` zeichengleich. Die vier Skripte stimmen (nachgerechnet) | `server/assets/vendor/leaflet/leaflet.css -text` in `.gitattributes` und die Datei mit CRLF neu einchecken (dann stimmt der Kopf), oder den Kopf auf die LF-Summe `337bfca5…` umschreiben und den Grund dazuschreiben; `leaflet.css` in die Prüfsummentabelle von Lizenzen.md; und ein Riegel in Stufe 1, der die Kopfsummen aller vendor-Textdateien nachrechnet — eine Prüfsumme, die niemand nachrechnet, ist eine Zahl im Kommentar |
| F-R17-51 | niedrig | 7 | `server/assets/openlocationcode.js` (720 Zeilen): Kopf „Copyright 2014 Google Inc. … Apache License, Version 2.0", keine Herkunft (Paket, Fassung, Abruf), keine SHA-256, Ablage unter `assets/` statt `assets/vendor/`; `locparse.js` ruft `OpenLocationCode.isValid()`, `.isFull()`, `.isShort()`, `.decode()`; `docs/Lizenzen.md` 3: „Alle unter `server/assets/vendor/`", „Jede vendorierte Datei trägt im Kopf Herkunft, Version und SHA-256", Tabelle ohne diese Bibliothek, Prüfsummentabelle ohne sie; nur `docs/Technik.md` und das CHANGELOG nennen `google/open-location-code` (Apache-2.0); das Register `fremde Quelle` (`vollstaendigkeit.py`) sieht sie nicht, weil es Adressen sucht, keine Dateien | Der Lizenznachweis des Projekts ist unvollständig: Die Stelle, die alle Fremdbestandteile verspricht (Lizenzen.md, Pflegepflicht CLAUDE.md 9), kennt eine Bibliothek nicht, die auf zwei Seiten läuft (`einsatz_form.php`, `einstellungen.php`); Apache-2.0 verlangt die Weitergabe des Lizenzhinweises — den trägt die Datei selbst, der Nachweis daneben fehlt. Und die Herkunftszusage aus CLAUDE.md 4 gilt für sie nicht nachprüfbar: Welche Fassung, von wo, unverändert? Kein Sicherheitsbruch — `node --check` grün, der Code ist gelesen, reine Rechnung ohne DOM und ohne Netz | Nach `assets/vendor/` verschieben und die `asset()`-Einbindung nachziehen, Kopf mit Paket (`open-location-code`, Fassung oder Commit), Abrufdatum und SHA-256, Zeile in Lizenzen.md 3 samt Prüfsumme — und der Riegel aus F-R17-50 zählt sie mit |
| F-R17-52 | mittel | 8 | `watch/source/Uploader.mc` `_findJob()` (`"pendingIdx" => i`), `onResponse()` (`Model.pendingMissions.remove(Model.pendingMissions[idx])`, ebenso `pendingRest`); daneben `alleGeparktenVerwerfen()` (aufgerufen aus `SyncView.mc` `VerwerfenDelegate.onResponse()` und `Pair.mc` `lokalTrennen()`), `Model.mc` `backlogCount()` (aufgerufen im Takt der Sync-Seite, `SyncView.mc`, und aus `Pair.mc`); `verwerfen()` als Gegenbeispiel | Das bestätigte Paket wird nach der Antwort über den **Listenindex** entfernt, den `_findJob()` beim Absenden gemessen hat. Dazwischen liegt eine Netzanfrage, und in der Zeit darf die Liste schrumpfen: `alleGeparktenVerwerfen()` entfernt jedes geparkte Paket — und geparkte stehen gerade **vor** dem laufenden, weil `_findJob()` sie überspringt —, `backlogCount()` entfernt fertige Einträge ohne Arbeit, und beides läuft von der Sync-Seite aus, also genau dort, wo auch die Anfrage gestartet wird; kein `_busy`-Riegel hält es auf. Rückt die Liste um eins, trifft `remove(pendingMissions[idx])` ein **anderes** Paket: Es verschwindet aus der Liste, seine Spur bleibt als Waise im Speicher (die Bauart, vor der der Kommentar an `verwerfen()` selbst warnt), und der Einsatz kommt nie auf dem Server an — das ist der Verlust eines dokumentierten Einsatzes auf der Uhr. Ist `idx` nach dem Schrumpfen hinter dem Ende, wirft der Zugriff im Rückruf, und die App bricht ab. Gelesen, nicht nachgespielt: Der Weg ist ein paar Sekunden breit (Dauer einer Anfrage), aber die zwei Handgriffe liegen auf derselben Seite | Nicht über den Index, sondern über die Referenz entfernen — so, wie `verwerfen()` es schon tut (Schleife rückwärts, `ref.equals(...)`), oder vor dem `remove` prüfen, dass `pendingMissions[idx]["ref"]` noch `ref` ist; `pendingIdx` kann dann entfallen. Dazu ein Fall im Rundlauf (`tools/uhr-pruefstand/rundlauf/`): zwei geparkte und ein offenes finales Paket, Sync anstoßen, während der Anfrage „geparkte verwerfen" bestätigen, danach `pendingMissions` und die Chunks zählen |
| F-R17-53 | niedrig | 8 | `watch/source/Huelle.mc` `_messen()` (`catch (e) { ergebnis = "kaputt"; }` und `Storage.setValue(ABLAGE_PRUEFUNG, kennung + "#" + ergebnis)`), `_kennung()` (App-Fassung und Firmware), `bereit()`; Verbraucher `Uploader.mc` `syncAll()` (`if (!Huelle.hatSchluessel() \|\| !Huelle.bereit()) { return; }`), `SyncView.mc` (Zeile „Verschlüsseln geht nicht"); `Pair.mc` `lokalTrennen()` löscht `cred`, `kschl` und die Properties, nicht `kpruef` | Der Selbsttest der Gerätehülle (E-S11-30) läuft einmal je App-Fassung und Firmware und legt sein Ergebnis in `kpruef` ab — auch das Ergebnis **„kaputt"**, und das für jede Ausnahme, die beim Messen fällt. Eine einmalige Ursache (knapper Speicher beim ersten Start mit vollem Puffer, ein Watchdog während der zwei Schlüsselvereinbarungen, ein vorübergehend nicht verfügbares `Toybox.Cryptography`) wird so zum **Dauerzustand**: `bereit()` liest den Speicher und misst nicht neu, `syncAll()` kehrt still um, die Sync-Seite zeigt eine Zeile, die nach Hardware klingt, und nichts im Gerät setzt den Vermerk zurück — weder das Trennen und Neukoppeln noch ein Menüpunkt; erst ein App- oder Firmware-Update ändert die Kennung. Kein Sicherheitsbruch (die Uhr sendet dann **nichts**, keinen Klartext), aber ein stiller Ausfall des einzigen Sendewegs, den die Trägerin nicht selbst beheben kann | Nur die beiden gültigen Ergebnisse (`be`, `le`) dauerhaft ablegen, „kaputt" flüchtig halten und beim nächsten `bereit()` neu messen (höchstens einmal je Start); zusätzlich `kpruef` in `lokalTrennen()` mitlöschen, damit Neukoppeln ein frischer Anfang ist. Der Selbsttest ist in 0,2 s gemessen (Fenix 6 Pro, E-S11-30) — eine Wiederholung je Start kostet nichts Spürbares |
| F-R17-54 | mittel | 9 | `android/handy/src/main/java/org/genem/nadoku/handy/aufzeichnung/AufzeichnungsDienst.kt` `akkuwarnungStellen()` (`bau.addAction(0, getString(R.string.dienst_beenden), PendingIntent.getService(… setAction(BEENDEN) …))`, ab `Akkustufe.NIEDRIG`), `dauererinnerungStellen()` (dieselbe Aktion), `onStartCommand()` (`if (intent?.action == BEENDEN) { beenden() … }`), `beenden()` (privat: `removeUpdates`, `waechterAbraeumen()`, `app.sendelauf { … nachDemDienstende(…) }`), `nachDemDienstende()` (`stopForeground`, `stopSelf()`); zum Vergleich `HauptActivity.kt` (Rückfrage: `app.klammer.beenden()` **dann** `AufzeichnungsDienst.beenden(kontext)`), `uhr/Uhrannahme.kt` `wirke()` (`DIENST_BEENDEN -> klammer.beenden(zeitpunkt)`) mit `Dienstfolge.BEENDEN` im `HandyHorcher`; `dienst/Dienstklammer.kt` `beenden()` | Der Knopf „Dienst beenden" in der Akkuwarnung (ab 15 %) und in der Erinnerung nach 26 Stunden ist eine `PendingIntent` mit der Aktion `BEENDEN` an den **Vordergrunddienst** — und der ruft darauf nur sein eigenes `beenden()`: Ortung ab, Wächter ab, Takt ab, Meldung „Dienst beendet · sende …", ein Sendelauf, danach `stopSelf()`. **`app.klammer.beenden()` steht nirgends auf diesem Weg.** Die zwei anderen Wege schließen die Klammer vorher selbst (die Rückfrage der Hauptansicht, das Dienstende von der Uhr über `Uhrannahme`), und `AufzeichnungsDienst.beenden(kontext)` ist dort nur der zweite Schritt. Aus der Benachrichtigung gedrückt bleibt der Dienst im Puffer deshalb **offen**, mitsamt dem offenen Einsatz- oder Ruhepaket: Der Vordergrunddienst ist weg und zeichnet nichts mehr auf, die Dauermeldung verschwindet, aber `klammer.laeuft()` sagt weiter ja — die Hauptansicht zeigt beim nächsten Öffnen „Dienst läuft" mit „Dienst beenden" (ohne Ortung; nichts startet den Dienst neu), die Uhr bekommt mit der nächsten Standmeldung `dienstLaeuft = true`, und der nächste „Dienst beginnen" setzt den alten **fort** (E-R45-13) — der Montagsdienst hängt am Freitag, genau der Fall, den die Erinnerung aus Nr. 160 verhindern soll und für den sie diesen Knopf anbietet. Im Web steht der Diensttag so lange ohne Ende, wie es der Warntext der kritischen Stufe selbst als Schaden beschreibt („Ein Abschalten mitten im Dienst lässt den Diensttag ohne Ende stehen"). Es ist das Spiegelbild von B-S5Z-04 (`Dienstfolge.kt`): Dort endete der Dienst im Puffer und der Vordergrunddienst lief weiter; hier endet der Vordergrunddienst und der Dienst im Puffer läuft weiter. Kein Prüffall deckt es — `LebenszyklusTest` und `DienstklammerTest` prüfen die Klammer, keiner schickt `BEENDEN` an den Dienst. Und am Code steht ein Kommentar, der die Lücke verdeckt („DER KNOPF LÖST DIESELBE AKTION AUS wie der in der Dauermeldung"): Die Dauermeldung hat keinen Knopf (`addAction` kommt zweimal vor, Akkuwarnung und Erinnerung), und `version.properties` zu 0.12.0 sagt dasselbe. Gelesen, nicht nachgespielt: kein Emulator gestartet, kein Gerät (Pruefablauf 6.9; der Review ändert nichts) | Im `BEENDEN`-Zweig von `onStartCommand()` zuerst `app.klammer.beenden()` rufen — sie ist idempotent (`false`, wenn kein Dienst läuft), die beiden anderen Wege bleiben also richtig —, dann `beenden()`. Ein Robolectric-Fall dazu (`Robolectric.buildService(AufzeichnungsDienst::class.java)`, Dienst über die Klammer beginnen, Intent mit `BEENDEN` an `onStartCommand()`, danach `klammer.laeuft()` falsch und `puffer.laufenderDienst()` null). Den Kommentar an `akkuwarnungStellen()` und den Satz in `version.properties` berichtigen („derselbe Weg wie die Rückfrage der Hauptansicht"). Und ein Blick in den Bestand: Wer den Knopf schon gedrückt hat, findet im Web einen Diensttag ohne Ende — dort schließen, nicht nachspielen (P-R17-11 im Prüfdokument) |
| F-R17-55 | mittel | 10 | `tools/sandbox/konfig_stellen.php` `konfig_stellen()` (`@file_put_contents($pfad . '.vor-probe', $text)`; Kopfkommentar: „`register_shutdown_function` greift nicht bei `kill -9` … sie steht als `config.php.vor-probe` daneben"), die acht Aufrufer `tools/proben/{anteil,komplett,passkey,raten,schluesselwechsel,versand,wiederherstellung,zweitfaktor}/probe.php`; `tools/proben/mail/probe.php` (`config.php.mailprobe`); `.gitignore` (Zeile 12 `server/config.php.mailprobe`, Kommentar nennt `tools/mailprobe/`); `server/.htaccess` (`FilesMatch "^(config\.php\|config\.example\.php\|…)$"`); `.github/workflows/ausliefern-lauf.yml` Ausnahmeliste (`config.php`, sonst nichts dieser Art); `tools/proben/komplett/probe.php` (Kopie von `server/` samt `config.php` mit echtem DB-Zugang und `kdf_anteil` nach `/tmp/komplettprobe-*`) | Acht Proben stellen für ihren Lauf einen Wert der `config.php` (Serverschlüssel, `base_url`, `kdf_anteil`, `health_token`) über `konfig_stellen()`, und die Funktion schreibt vorher die **ganze** Datei — DB-Zugang, `server_key`, `kdf_anteil`, SMTP-Passwort — als `config.php.vor-probe` **daneben, ins Webverzeichnis**; die Mailprobe tut dasselbe als `config.php.mailprobe`. Das ist als Rückweg nach `kill -9` gedacht und so auch gesagt. Nur: Die Kopie ist eine Datei mit der Endung `.vor-probe`, kein PHP — Apache wie `php -S` liefern sie als Text aus, und die `FilesMatch`-Regel der `.htaccess`, die `config.php` deckt, endet mit `$` und deckt keine Kopie. Während jeder dieser Läufe steht die Konfiguration der örtlichen Anlage unter `https://127.0.0.1:8443/config.php.vor-probe` lesbar, und nach einem harten Abbruch bleibt sie stehen; `.vor-probe` steht weder in `.gitignore` noch in der Ausnahmeliste der Kette, `.mailprobe` nur in `.gitignore` (unter einem Kommentar, der auf ein Verzeichnis `tools/mailprobe/` zeigt, das es nicht gibt). Der Weg nach Produktiv führt über einen Commit (`git add -A` nach einem abgebrochenen Lauf, dann FTPS — die Ausnahmeliste hielte die Datei nicht auf), nicht über die Anlage selbst; bis dahin ist es die Sandbox, und deren Zugangsdaten sind Prüfwerte. Dass eine Probe `config.php` **austauscht**, ist für diese acht nötig (F-SR-83 begründet es für den Schlüsselwechsel); dass die Sicherung dieser Datei im Webverzeichnis liegt und unter keiner Schutzregel, ist es nicht. Dieselbe Bauart hat `komplett/probe.php` mit einer vollständigen Kopie der Anlage unter `/tmp` (0770, Abräumen nur im Shutdown). Gemessen: `grep -rln "konfig_stellen("` 8 Proben + 2 Werkzeuge; `.htaccess` Zeile 67; `.gitignore` Zeile 8 bis 12; `ausliefern-lauf.yml` Zeile 488; `git ls-files server/config.php*` leer | Die Sicherung **außerhalb** von `server/` ablegen (`sys_get_temp_dir()` mit `umask 0077` und `chmod 0600` — so hält es `schluesselwechsel/probe.php` mit seiner Stand-Datei schon) oder, wenn sie daneben bleiben soll, die `FilesMatch`-Regel auf `^config\.php` ohne Ende setzen und beide Namen in `.gitignore` **und** in die Ausnahmeliste der Kette nehmen; den Kommentar in `.gitignore` auf `tools/proben/mail/` berichtigen. Ein Stufe-1-Riegel, der `server/config.php*` im Baum rot färbt (das wäre der Riegel aus F-R17-16, Ausnahmeliste gegen `.gitignore`). P-R17-12 im Prüfdokument: Auf Staging und Produktiv antworten `/config.php.vor-probe` und `/config.php.mailprobe` mit 404 |
| F-R17-56 | niedrig | 10 | `tools/zaehlung/register.php` Zeile Z17 (`'start' => 30, 'decke_jetzt' => 30, 'decke_ziel' => 0`; Paket AP5, Grund E-ZE-21); Kopf der Datei („Erst das Paket schreibt `decke_jetzt` herunter"); `php tools/zaehlung/zaehlen.php` (21.10.2026: `Z17 AP5 30 0 30 drunter`); zum Vergleich Z22 (`decke_ziel` 18, `decke_jetzt` 4, Ist 4) | Das Register ist der Riegel der Zentralisierung (`CLAUDE.md` 4: „schlägt an, sobald eine Zeile darüber steigt"). Für Z17 — `INSERT`/`DELETE` auf Kindtabellen außerhalb von `einsatz_lib.php` und `migration_lib.php` — ist das Paket AP5 erledigt und der Bestand steht auf **0**, die Decke aber noch auf dem Startwert **30**: Dreißig neue Schreibstellen an `mission_phases`, `resus_*` oder den Spurtabellen am Datenzugriff vorbei blieben grün. Der Kopf des Registers nennt genau diesen Zustand als den, den es nicht geben soll. Z22 zeigt die andere Richtung (`decke_ziel` 18 über `decke_jetzt` 4 — ein Zielwert, der nie nachgezogen wurde), schadet aber nicht, weil die Decke beim Ist steht. Niedrig, weil kein Rückfall da ist — nur der Riegel, der ihn nicht melden würde | `decke_jetzt` von Z17 auf 0 setzen (ein Lauf von `zaehlen.php` belegt, dass es hält), `decke_ziel` von Z22 auf 4; und eine Zeile Selbstprobe in `zaehlen.php`, die eine Decke über dem Ist nach einem erledigten Paket meldet — sonst steht die nächste stehengebliebene Decke wieder da, bis jemand liest |
| F-R17-57 | niedrig | 10 | `tools/kette/tor.py` `adresse_bauen()` (`d = {"token": token, "aktion": aktion}` in die Anfrageadresse), Kopfkommentar (`--token …`); `.github/workflows/auslieferung.yml` (`tor.py backup --basis "$STAGING_URL" --token "$JOBS_TOKEN"`, `zustand`, `pause`); `tools/screenshots/aufnehmen.mjs` `tor()`, `tools/referenzdatensatz/vergleich/kreislauf.py` `jobs_pause()` (beide über `--jobs-token`); `server/jobs.php` (`$_GET['token'] ?? $_POST['token']`) | Das Job-Token — der eine Schlüssel, mit dem die Kette Backup, Wartung und Zustand des **Produktivservers** fernsteuert (I-44, Lage K) — geht als `GET`-Parameter `?token=…` über die Leitung. TLS schützt es unterwegs; am Ziel steht es im **Zugriffslog des Hosters** (Staging wie Produktiv, mit Zeitstempel), in jedem Proxy-Log dazwischen und auf dem Läufer in der Prozessliste (`--token` als Argument). `jobs.php` nimmt es seit jeher auch per `POST` an — der Weg ist da, nur geht ihn niemand. Niedrig: Wer das Zugriffslog des Hosters liest, hat meist mehr; aber ein Token, das die Wartung einschaltet und Backups auslöst, gehört nicht in eine Zeile, die der Hoster nach Vertrag 14 Tage aufhebt | `tor.py` schickt das Token im Rumpf (`POST`, `application/x-www-form-urlencoded`) oder als Kopfzeile, `jobs.php` nimmt `$_GET['token']` nicht mehr an (eine Fassung Übergang, dann weg); `aufnehmen.mjs` und `kreislauf.py` reichen es weiter wie heute — der Wechsel liegt in `tor.py` allein. Danach das Token einmal drehen (Wartungsbereich), weil die alten Logs es tragen |
| F-R17-58 | mittel | 11 | `README.md` Zeilen 14 bis 20 (Kasten „Was die Verschlüsselung nicht abdeckt": „die GPS-Daten, die Koordinate jeder Einsatzphase, die Höhe des Einsatzorts, das Transportziel samt Koordinate, Zeiten, Reanimationsverlauf … liegen im Klartext in der Datenbank … Aus den GPS-Daten und den Phasenkoordinaten lässt sich der Einsatzort rekonstruieren"); `docs/Was-ist-NAdoku.md` Zeilen 44 bis 48 („Im Klartext — für uns lesbar … die Koordinate jeder Einsatzphase, die GPS-Aufzeichnung der Fahrt … das Transportziel samt Koordinate, die Höhe des Einsatzorts, der Verlauf einer Reanimation") — ausgeliefert nach `server/doku/`, gezeigt von `ueber.php` **vor der Anmeldung**; `docs/Handbuch.md` 1945 bis 1975 (4.3 „Schloss und Klartext": „Alles Übrige — Zeiten, Phasen samt Koordinaten, GPS-Daten, Transportziel — liegt lesbar in der Datenbank") und Kapitel 5 ab 2396 („Im Klartext liegen dagegen: … die GPS-Daten, die Koordinate jeder Einsatzphase, die Höhe des Einsatzorts, das Transportziel samt Koordinate, … der Verlauf einer Reanimation", 2490 „Der Einsatzort lässt sich damit aus den GPS-Daten ableiten") — `hilfe.php`, ohne Anmeldung erreichbar; `docs/rechtstexte/AVV.md` Zeilen 47 bis 56 (A.2) und Anlage II Nr. 2 (146 bis 167, 271 f.: „Im Klartext … die Koordinate jeder Einsatzphase sowie die GPS-Aufzeichnung der Fahrt … Transportziel samt Koordinate … Verlauf einer Reanimation … **der Reanimationsverlauf liegt im Klartext**"); aus Stück 10 vorgemerkt: `tools/referenzdatensatz/quelldaten/FORMAT.md` 196 bis 198, `generator/wegpunkte.py` 8 bis 10. **Dagegen:** `CLAUDE.md` 4 („Seit S11 … ebenso verschlossen: die GPS-Spur, die Koordinate jeder Phase … und der Reanimationsverlauf … Transportziel samt Koordinate und Ortshöhe liegen im `pat_blob`"), `docs/Bedrohungsmodell.md` 3 (G1 bis G3), `docs/rechtstexte/Datenschutzerklaerung.md` 45 bis 58 (Kopf „Stand nach S11": GPS-Daten, Ort jeder Einsatzphase, Transportziel, Reanimation unter „Was verschlüsselt ist"), `docs/Handbuch.md` 2567 („Der Schlüssel für Uhr und Handy (seit Web 22.0.0)") und 11.5a, `docs/Technik.md` 4.98 („Seit Web 22 (S11) nicht mehr: …") | Seit Web 22 (S11-04 bis -07) verschließen Uhr, Handy und Browser Spur, Phasenorte und Reanimation in der Gerätehülle, Transportziel und Ortshöhe liegen im `pat_blob`; seit Web 23 (S11-12) gibt es die Klartextspalten und -tabellen nicht mehr, und `ingest.php` weist Klartext mit `400 klartext` ab. **Vier öffentliche Texte sagen das Gegenteil** — und zwar als die ausdrücklich „ehrliche" Zusage aus Nr. 138 (Web 15.6.0, Weg C): der Kasten im `README.md`, die Seite „Was ist NAdoku" (der einzige Weg, auf dem jemand ohne Konto erfährt, was die Anwendung tut — `Technik.md` 4.99n), das Handbuch in 4.3 und in Kapitel 5 (wo derselbe Abschnitt wenige Absätze weiter den Schlüssel für Uhr und Handy erklärt), und die **AVV-Vorlage** in A.2 und Anlage II Nr. 2 — mit dem Satz „der Reanimationsverlauf liegt im Klartext" in Fettdruck und einer Folgerung daraus. Die Richtung ist zu schwach, nicht zu stark: Niemand verlässt sich auf einen Schutz, den es nicht gibt. Aber die AVV ist das Dokument, das die Betreiberin bei der Registrierung mit jeder NutzerIn schließt (E-P5b-05); eine Vereinbarung, die Art und Umfang der Verarbeitung falsch beschreibt, ist als Auftragsverarbeitungsvertrag falsch — und sie widerspricht der Datenschutzerklärung daneben, die auf S11 steht. `Technik.md` 4.98 sagt selbst, `CLAUDE.md` 4, `README.md`, `Handbuch.md` 5 und der Textbaustein sagten „jetzt dasselbe" — seit S11-11 stimmt der Satz nur noch für `CLAUDE.md` 4. Mittel: kein Datenrisiko, aber eine falsche öffentliche Zusage und ein falscher Vertragstext, vier Monate nach der Umstellung; S11-11 hat `CLAUDE.md` 4 nachgezogen (der Absatz sagt es) und die vier anderen nicht | Die vier Texte in **einem** Paket auf den Wortlaut von `CLAUDE.md` 4 und `Datenschutzerklaerung.md` 2 bringen — `README.md` 14 bis 20, `Was-ist-NAdoku.md` 44 bis 48, `Handbuch.md` 4.3 und 5 (Vorlage: 11.5a und 2567), `AVV.md` Kopfkasten, A.2 und Anlage II Nr. 2 —, dazu `FORMAT.md` und `wegpunkte.py`, und in `Technik.md` 4.98 den Satz „sagen dasselbe" nachziehen. Die beiden Rechtstexte brauchen danach eine neue Fassung mit Standdatum (die AVV sperrt den Login, bis sie erneut angenommen ist — `Technik.md` 4.99j; vorher die Ankündigung setzen). Und ein Riegel, damit es nicht ein drittes Mal auseinanderläuft (Nr. 138 war das erste Mal): die Textprobe (Bereich `c`) bekommt die Wendung „liegen im Klartext in der Datenbank" und „liegt im Klartext" neben GPS/Koordinate/Reanimation/Transportziel als Falle — ein Treffer außerhalb der Archive und der Changelog-Historie ist rot. P-R17-13 im Prüfdokument: die auf Produktiv eingespielten Texte (`avv.php`, `datenschutz.php`) und `ueber.php` gegenlesen |
| F-R17-59 | niedrig | 11 | Sammelbefund II veralteter Stellen (Ergänzung zu F-R17-29, Stück 11), je mit gemessenem Wert: **`README.md`** 5 („Fenix 6 Pro, Forerunner 945, Venu 3s" — `watch/manifest.xml` trägt 99 Geräte, darunter `fenix7`, `fenix7pro`, `fr965`; der Store-Eintrag zeigt fēnix 7, FR 965, Venu 3), 69 („elf Quelltextprüfungen" — `NAMEN` in `tools/quelltext/pruefen.sh`: 14). **`docs/Handbuch.md`** 3 (Stand 06.09.2026), 66 („Fenix 7 und neuer … noch nicht ausgeliefert"), 2638, 2715, 2733 („Ältere Backups kannst du weiterhin einspielen", „Ältere Dateien lassen sich unverändert öffnen" — gegen 2675 „Seit Web 23 … nur ab 22.5.0" und `NUTZLAST_HOECHSTENS` 13), 3095 („Betriebsangaben: Einsatzzeiten, Transportziele … Verlauf einer Reanimation" ohne den Haken — seit Web 22.4.0 unter der Schranke, `Technik.md` 4), 3688 (verweist für den CSV-Export auf „Abschnitt 8" — das ist Löschen und Papierkorb; der Export steht in 7.1). **`docs/Bedrohungsmodell.md`** 181 („14 Aktionen an SHAs" — 15). **`docs/Export-Format.md`** 213 („16 geschützte" von 31 Spalten — 17 nach dem Umzug von `transport_dest`). **`docs/Backup-Format.md`** 256 („Ableitungen zu je 320 000 Runden" — `KDF_ITER_ZIEL` 600 000), 611 (Beispiel `"version": 12` — Nutzlast 13). **`docs/Pruefablauf.md`** 206 („`Stufe 1` mit 15" Schritten — 16; F-R17-29 nannte es, steht noch), 897 („vierzehn Regeln" — `REGELN` in `bestand.py`: 15, `motoren` seit R87), 6.11 kennzeichnung („heute 20 von 20: zehn Felder" — gemessen 26 von 26, 13 Felder). **`docs/Sandbox-Setup.md`** 44 bis 50 (Abbild „PHP 8.4.19 … Python 3.11 … Docker 29.3.1 … 15 GB" — heute PHP 8.3.6, Python 3.13.16, Docker 29.9.1; 6.1a sagt 16 GB), 445 („310 000 Runden"), 467 („`python3` ist 3.11, apt legt nach 3.12" — 3.13). **`docs/Design.md`** 2730 („115 Aufrufstellen" — 153). **`android/LIESMICH.md`** 12 bis 16 (Status-Kasten „Arbeitspaket C2 … Was fehlt, ist der Browserteil (Block A)" — S4 ist seit dem 04.09.2026 gemergt), 39 („die GitHub-Action lädt nur `server/` hoch" — seit PK-08 baut und legt der Job `android` die APKs ab), 1371 („Die Kamera. `QrKamera` ist eine Hülle um CameraX" — die Klassen `QrKamera`, `QrLeser`, `QrInhalt` sind seit Android 0.11.0 fort; `grep` findet sie nur noch hier). **`server/vendor/HERKUNFT.md`** 52 bis 59 („phpseclib selbst wird nur für **einen** Zweck geladen: den SFTP-Adapter" — Passkeys, Rückweg und Kontopaar laden es ebenso, `passkey_lib.php`, `rueckweg_lib.php`, `kontopaar_lib.php`). **`CLAUDE.md`** 435 („die sieben Umgebungswerte" — `Sandbox-Setup.md` 4 zählt acht, `CIQ_GERAETE_URL`), 4 (die Liste „Spuren nur über `spur_lib.php` … genau einen Weg" nennt acht Funktionen; `Technik.md` 4.97 führt daneben `spur_min_ts()`, `spur2_stand()`, `spur2_faellig()`, `spur2_wartung_tauschen()` als Verbraucherwege). **`docs/Technik.md`** Kopf (Stand 20.09.2026), 585 („symbole/ 55 Zeichen" — 58), 676 („schemaprobe/ … 19 Erwartungen" — 50 je Fassung seit S11-12, `tools/schemaprobe/LIESMICH.md`), 798 („proben/ siebenundzwanzig Prüfungen" — 31), 2a („sieben Umgebungswerte"), 983 (`users`: „Rolle `user`/`admin`" — ENUM mit vier Werten seit Web 20.41.0), 1018 (`sicherheit_ereignisse`: „seit Web 20.42.0 sieben" Töpfe mit Leiter — acht, `notweg`; 5e.8 in Zeile 11201 hat es richtig), 1416 (`EdUnlock.ensureContentKey(wrap, kdfSalt)` — die Funktion nimmt `kdfIter` als dritten Parameter, `unlock.js` 501), 1727 („Die einteiligen Fassungen 2 und 3 werden weiterhin gelesen" — der Absatz davor und 4.99s sagen: seit Web 23 nicht mehr), 1988 (`<datalist>` für Besatzungsvorschläge — seit Web 15.7.0 `EdVorschlaege`, 4.98a sagt es), 3295 („In allen elf Dateien mit Rumpf" — 16 Dateien rufen `api_rumpf()`, 26 `api_methode()`), 6875 („geht 46 Katalogeinträge durch" — `migrationen_katalog()` hat 74), 11205 („Die übrigen dreizehn" Töpfe ohne Leiter — vierzehn, `kontopaar` fehlt; `RATE_GRENZEN` hat 23), 11434 („Alle zwölf fremden `uses:`-Zeilen" — 15; F-R17-29), 11597 („Datei hatte 988 Zeilen, heute 337" — `pruefung.yml` hat 294), 11947 (6.4 Schritt 2 „auf **diesem Commit**" — 6.1 erklärt selbst „nach Baum, nicht nach Commit"; F-R17-29), 11989 (6.5 „`.sitzungen/` wird der achte Pfad — und steht noch nicht darin … zwei Zeilen, in **beiden** Schritten" — steht seit Kette II/AP5 in `ausliefern-lauf.yml` 497 f., und es gibt einen Schritt; F-R17-29), Gliederung (`### 4.98a` zweimal, 5753 und 6036; `## 5. Uhr-App` 6517 steht **vor** `### 4.99a2`, `4.99b`, `4.99c`; `4.99d` bis `4.99g` folgen auf `5.2b`; `4.99p` bis `4.99s` stehen vor `4.99l`, `m`, `n`, `o`, `k`, `j`, `i`, `h`), 4.99b (Zeilennummern als Fundstellen: `pair.php` 318 bis 343, `db.php` 686, `ratelimit_lib.php` 79 und 80 — heute 324 f., `schema.sql` 439) | Keiner dieser Sätze ist für sich ein Sicherheitsproblem, und keiner stand in F-R17-29. Zusammen sind es rund fünfzig Stellen in zwölf Dokumenten, an denen eine Zahl oder ein Satz einen Stand beschreibt, den der Code überholt hat — die meisten seit S11, einige seit PK-08 oder R87, die Abbildfassungen seit dem letzten Containerwechsel. Drei davon wiegen mehr als die anderen: das **Handbuch** sagt in Kapitel 6 dreimal, ältere Sicherungen ließen sich weiter einspielen, und einmal das Gegenteil — eine NutzerIn mit einer Datei von vor 22.5.0 liest je nach Absatz etwas anderes; `android/LIESMICH.md` trägt im **Statuskasten** einen Stand von Anfang September und nennt eine Klasse, die es seit 0.11.0 nicht gibt; und `Technik.md` ist mit 14 125 Zeilen nicht mehr in Reihenfolge — wer „4.99a2" sucht, findet es hinter „## 5.", und `4.98a` zweimal. `Technik.md` 3 benennt bei `rate_limits` selbst die Regel („Eine Zahl in einem Fließtext altert genauso still wie eine Aufzählung") und zieht sie dort — und nur dort. Was die Lesung **nicht** gefunden hat, gehört dazu: Alle Konstanten, Fristen, Töpfe, Grenzen und Listen der Abschnitte 4.97 bis 5e, das Jobregister, die Rollenmatrix, die Ausnahmeliste der Kette, die Prüfsummen in `Lizenzen.md` und `HERKUNFT.md`, die erzeugten Tabellen in `Design.md` stimmen — dort, wo ein Werkzeug nachzählt (`jobregister`, `bestand design`, `register.php`, `rollen/probe.php`, Stufe 1), altert nichts | Ein Dokumentationspaket, keine Einzelkorrekturen: je Dokument die Stellen oben, mit der Regel aus `Technik.md` 3 — **eine Zahl, die ein Werkzeug zählt, steht nicht im Fließtext** (Probenzahl, Symbolzahl, Umgebungswerte, Katalogeinträge, Schrittzahlen); wo sie bleiben soll, mit Stand und Befehl daneben. `Technik.md` neu gliedern (4.99a2 bis c, d bis g, h bis o an ihren Platz; `4.98a` Register → `4.98e`). Die Dokumentenprobe aus Nr. 188 um zwei Fälle erweitern, die diese Lesung gefunden hat: „Zahl vor einem Verzeichnisnamen gegen `ls`" (symbole, proben, schemaprobe) und „`Stand:`-Zeile älter als der letzte Commit an der Datei" — beides ohne Installation messbar. F-R17-29 bleibt offen; seine Technik-Stellen 6.4 und 6.5 stehen noch, und Stück 12 bündelt beide Sammelbefunde zu einem Posten |

## 3. Stück 1 — das Bedrohungsmodell

Das Ergebnis dieses Stücks steht in `docs/Bedrohungsmodell.md`. Hier steht,
**wie** es entstanden ist und **was** dabei herausgekommen ist, das nicht ins
Modell gehört: die Inventartabelle (R17-01), die Befunde und die Fragen.

### 3.1 Gliederung des Modells (Entwurf, wird mit R17-02 endgültig)

0. **Zweck, Geltung, Pflege** — für wen, welcher Stand (Web, Uhr, Android,
   Datum), wann es fortgeschrieben wird.
1. **Schutzziele und Werte** — was geschützt wird: Patientendaten,
   Ortsdaten, Konten und Schlüssel, Integrität der Anlage und der
   Auslieferung, Verfügbarkeit; je Wert, welche Eigenschaft (Vertraulichkeit,
   Integrität, Verfügbarkeit) und welche Zusage (`CLAUDE.md` 4).
2. **Angreiferlagen** — die neun aus dem Krypto-Review vom 05.09.2026 (A bis
   I), erweitert um: Kette und GitHub (Push-Recht, Actions, Secrets),
   Google und Play (Signaturschlüssel, Data Layer), fremde NutzerIn
   derselben Anlage (Mehrbenutzer seit P5), Support-Rolle, die Betreiberin
   selbst; je Lage, was sie hat und was sie erreicht.
3. **Zusagen und ihre benannten Grenzen** — E2E-Felder und Klartextliste
   (Zustand, nicht Ziel; S11), Demo-Konto, Signaturschlüssel bei Google,
   Speicherdauern, Geheimnisse der Kette, die Grenze der Integritätswache
   (Nr. 263), Photon und Kachelserver, Regel „Migration ohne Rechte".
4. **Bausteine gegen Angreifer** — die Matrix: Schlüsselhierarchie mit
   Server-Anteil, Container 4, SPUR1, Komplettbackup, Serverschlüssel und
   sein Wechsel, Demo-Konstruktion, Keystore des Handys, Geräteschlüssel der
   Uhr, Kopplungsweg, Sitzung und Sitzungsbindung, Zweitfaktor mit Passkeys,
   Rückweg und Notzugang, CSP, Ratenschutz und Rechenaufgabe, Rollen und
   Berechtigungsmatrix, Kette mit Toren, Zeiger und Wache.
5. **Restrisiken** — benannt, je eines mit Zuordnung: hingenommen (und
   warum), Backlog-Nummer, oder S11.
6. **Entwurfsfragen für S11** — was Klartext bleiben darf, wie der Schlüssel
   aufs Gerät kommt (SP-9 gegen das Modell), die Antworten auf Nr. 146.

### 3.2 Inventar der Quellen (R17-01, 11.10.2026)

**Wie es entstanden ist.** Drei lesende Leser (Opus, je einer für Server
und Browser, für Uhr und Android, für Kette und Betrieb) haben den Code bei
Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegen die Doku gelesen und je
Baustein Fundstelle, Verhalten, Doku-Abgleich, Probe und Angreiferlage
geliefert; Fable hat die Hoch- und Mittel-Befunde am Quelltext nachgeprüft
(Spalte „geprüft" in 2.3) und das Inventar verdichtet. **Was keiner der
drei sicher lesen konnte, steht im Prüfdokument, Abschnitt 1** — nicht hier.
Die Spalte **Modell** nennt den Abschnitt von `docs/Bedrohungsmodell.md`
nach der Gliederung in 3.1 (1 Schutzziele · 2 Angreifer · 3 Zusagen und
Grenzen · 4 Bausteine · 5 Restrisiken · 6 Entwurfsfragen S11). Fundstellen
nennen Funktionen, keine Zeilen.

**Server und Browser**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-01 | Passwortableitung | `assets/crypto.js` `deriveKeys()`; `db.php` `KDF_ITER_ZIEL` | PBKDF2-HMAC-SHA256, 600 000 Runden, 16-Byte-Salz je Konto; Bytes 0–31 „Hälfte" (bleibt im Browser), 32–63 Anmelde-Token. `auth_salt.php` liefert für jede Adresse Salz oder HMAC-Pseudosalz in gleicher Form | `proben/anteil`, `proben/raten` | 4 |
| I-02 | Server-Anteil (S10) | `serverkrypto_lib.php` `kdf_anteil()`, `konto_anteil()`; `crypto.js` `datenschluessel()`; `ui.php` `ui_krypto_bootstrap()` | Anteil je Konto = HMAC(`kdf_anteil`, `konto:<id>`), geht als HKDF-Salz in den Datenschlüssel; `kdf_anteil` nur in `config.php`. Ausgeliefert an jede angemeldete Sitzung **und** an jeden gültigen Reset- oder Einladungslink (`pw_handling.php`); Demo ohne | `proben/anteil` | 4, F-R17-01 |
| I-03 | Anmelde-Token beim Server | `login.php`, `pw_handling.php`, `api/kdf_upgrade.php` | `users.password_hash` = bcrypt(Anmelde-Token); das Token enthält **keinen** Anteil. Blindvergleich gegen `AUTH_VERGLEICHSWERT`, Mindestdauer 0,35 s | keine | 4, F-R17-01 |
| I-04 | Inhaltsschlüssel und Hüllen | `crypto.js` `huelleBauen()`, `huelleOeffnen()`, `contentKeyCheck()`; `validate_lib.php` `WRAP_PW_RE`, `WRAP_RC_RE`, `RW_PRIVAT_RE` | CK 256 Bit Zufall im Browser; `pat_wrap_pw` als `edka1:<Anteilkennung>:…`, `pat_wrap_rc` immer `edk1:` (Code 20 Zeichen ≈ 98 Bit, SHA-256 ohne Streckung), `pat_blob` AES-256-GCM `edk1:`, `pat_key_check` 128 Bit. **Der CK wird nie rotiert**; ein Passwortwechsel packt nur die Hülle um | `proben/anteil`, `proben/rueckweg` | 4, F-R17-10 |
| I-05 | Schlüssel zur Laufzeit | `crypto.js` `setDataKey()`, `getContentKey()`, `merkeAbleitungen()`; `keyguard.js` | `sessionStorage`: `edk` und `pck` als Hex, `edkvor` (PBKDF2-Hälften **und** Anmelde-Token) nach jeder Anmeldung bis zur ersten Seite, die den CK braucht. Keyguard verwirft nach 30 min nur `pck`; `edk` bleibt bis Navigation oder Tabende | `proben/frist`, `proben/abmelden` | 4, 6 (Nr. 146), F-R17-24 |
| I-06 | Inhalt des Blocks | `einsatz_form.php`, `mission_fields.php` (`'store' => 'pat'`), `mf_ist_spalte()` | Elf Schlüssel nach `Technik.md` 4.98; `notes` über den Katalog; Server prüft nur die Form (`PAT_BLOB_RE`) | `quelltext/kennzeichnung` | 3 |
| I-07 | Klartextspalten mit Orts- oder Personenbezug | `schema.sql` | `missions`: Zeiten, Strecke, `site_ele_m`, Transportziel mit Koordinate, Schockraum, Winde, `bw_unit`, **`bw_info`** („Namen / Infos"), **`other_ema`**, `other_resources`, `notes` (Altbestand), Gerät; `mission_phases.lat/lon`; `mission_crew.name`; `resus_*`; `days` (Zeiten, Standort, `notes`); `day_crew.name`; `track_points`, `track_blobs` (SPUR1); Stammdaten; `users.email/name`; `protokoll_ereignisse.text` | keine | 3, F-R17-25 |
| I-08 | Anmeldung und Ratenschutz | `login.php`; `ratelimit_lib.php` `RATE_GRENZEN`, `rate_gleiche_dauer()` | Sperre vor bcrypt (`login` 10/15 min, `login_ip` 50/15 min, Leiter, globale Verlangsamung), Login-CSRF, Antwortgleichheit | `proben/raten`, `proben/sitzung` | 4 |
| I-09 | Sitzung und Bindung | `sitzung_lib.php` `sitzung_starten()`, `sitzung_binden()`; `auth_guard.php` | Cookie `Secure`/`HttpOnly`/`SameSite=Strict`, `use_strict_mode`, `session_regenerate_id` nach Erfolg; Bindung `EDBIND` (32 Byte, in der Datei nur SHA-256); Ablage `server/.sitzungen/` 0700; Rolle und Epoch je Anfrage aus `users`; 30 min Inaktivität | `proben/sitzung`, `quelltext/sitzungshaertung` | 4 |
| I-10 | Registrierung | `registrieren.php` `pow_aufgabe_neu()`, `pow_ok()`, `reg_stempel()` | 16-Bit-Rechenaufgabe in der Sitzung, signierter Zeitstempel, Wegwerfliste, Mindestdauer, gleiche Antwort | `proben/raten`, `proben/csp-browser` | 4 |
| I-11 | Zweitfaktor | `totp_lib.php`; `passkey_lib.php`; `db.php` `ZF_FRISCH_HANDLUNGEN` | TOTP (Geheimnis unter dem Serverschlüssel), Passkeys ohne PRF, „Gerät merken" (`EDGERAET`, SHA-256 in `vertraute_geraete`), frischer Code für acht Griffe — **nicht** für Komplett-Download, Freigabe, Adresswechsel, Setz-Link. Der Zweitfaktor sitzt vor der Sitzung, nicht vor dem Datenschlüssel | `proben/zweitfaktor`, `proben/passkey` | 4, F-R17-02, -08 |
| I-12 | Rückweg und Notzugang | `rueckweg_lib.php`; `zweitfaktor_notweg.php`; `nachweis_lib.php` | `rw_privat` als `edk1:` unter dem CK, Signatur über serverseitige Herausforderung; Notzugang verlangt Datei, Datenbankwert und Passwort, antwortet gleich und gleich lang | `proben/rueckweg`, `proben/zweitfaktor` Teil 8 | 4 |
| I-13 | Reset per Mail | `reset_request.php`; `konto_lib.php` `reset_token_ausstellen()`; `pw_handling.php` | Token 32 Byte, als SHA-256 gespeichert, 1 h (Einladung 24 h); die Seite liefert `pat_wrap_rc` und den Anteil des Kontos; der Server prüft beim Reset Form, Rundenzahl, Anteilkennung und **nur `pat_key_check`** — den Wiederherstellungsschlüssel prüft allein der Browser | `proben/anteil`, `proben/mail` | 4, F-R17-06 |
| I-14 | Verwaltung am fremden Konto | `admin_user.php` (Aktionen `konto`, `pw_reset`, `totp_zuruecksetzen`, `konto_status`, `user_delete`, `freigeben`) | Admin: Adresse sofort ändern (Hinweismail an die alte), Setz-Link, 2FA-Reset, sperren, löschen, Paket an **jedes** Konto freigeben; Riegel gegenüber BetreiberIn-Konten nur Rollenwechsel und „letzte BetreiberIn". Support: nur `pw_reset`, `verifikation`, `device_aus` an `user` | `proben/rollen` | 2 (I, K), F-R17-02, -06, -07 |
| I-15 | Serverschlüssel | `serverkrypto_lib.php` `sk_versiegeln()`, `sk_oeffnen_mit_wem()`; Job `schluesselwechsel` | `edsk1:` AES-256-GCM mit Zweck in der AAD; versiegelt Ziel-Zugänge, Kontopakete, **TOTP-Geheimnisse**, **Protokollarchive**; Komplettbackup mit eigener Blockversiegelung; Wechsel als Vorgang mit `server_key_alt`; öffnet keine Hülle | `proben/schluesselwechsel`, `proben/komplett` | 4, F-R17-26 |
| I-16 | `config.php` und `app_state` | `config.example.php`; `install.php` | 16 `konfig()`-Schlüssel: DB, SMTP, `health_token`, `server_key(_alt)`, `kdf_anteil(_alt)`; die Anwendung schreibt die Datei selbst (Schlüsselgriffe). **In der Datenbank** und damit in jedem Abzug: `salt_secret`, `reg_secret`, `notzugang_geheim`, `jobs_token` | `proben/anteil` | 3, 4 |
| I-17 | Konto-Backup `.edbak` | `crypto.js` `sealBackup()`; `backup_lib.php` `edbak_build()` | Browser entschlüsselt und versiegelt **Klartext** mit dem Backup-Passwort (PBKDF2, AES-GCM, AAD je Teil); enthält `pat_key_check` | `proben/container`, `proben/wiederherstellung` | 4 |
| I-18 | Kontopaket und Freigabe | `adminbackup_lib.php` `edbak_freigeben()`; `api/adminbackup_freigabe.php` | Paket Fassung 3 (Klartextfelder, `pat_blob` als Chiffre, `pat_wrap_rc`, `pat_key_check`, Adresse, Name) unter dem Serverschlüssel; Freigabe an jedes Konto außer dem Besitzer, bestätigt nur durch Abtippen der Zieladresse, **ohne Protokoll, Mail, frischen Code**; der Endpunkt entsiegelt und liefert Klartext-JSON der Einträge, Diensttage und Spuren | `proben/freigabe`, `proben/rollen` | 4, F-R17-02 |
| I-19 | Komplettbackup | `komplett_lib.php`; `admin_komplettsicherung.php`; `wiederherstellen.php` | Dump aller Tabellen (ohne `rate_limits`, `sicherheit_ereignisse`), `EDKOMP1` je 256 KiB unter Serverschlüssel oder Passphrase (PBKDF2 600 000); Klartext-Dump im Bauordner während des Baus; Download „klar" entsiegelt (nur BetreiberIn, Protokoll, **kein frischer Code**); Vorgabe 2 Stände | `proben/komplett`, `proben/wiederherstellung` | 4, 5, F-R17-08 |
| I-20 | Backup-Ziele | `sicherungsziel_lib.php` `ZielFtp`, `ZielSftp` | SFTP mit gemerktem Hostschlüssel; FTPS über `ext/ftp` **ohne Zertifikatsprüfung**; Zugänge versiegelt; drüben wird nie gelöscht außer per Löschregel; Protokollarchive gehen ohne Aufbewahrungsregel mit | `proben/versand` | 3, 4 |
| I-21 | Spuren | `spur_lib.php`; `gpx.php` | `track_points`/`track_blobs` (SPUR1) im Klartext; Abruf hinter `auth_guard` mit `user_id` | `proben/spur`, `proben/gpx` | 3, 6 |
| I-22 | Demo-Konto | `demo_lib.php`; `server/demo/fixture.json.gz` | Schlüsselmaterial in der Fixture, Passwort öffentlich, kein Anteil, Rolle `user` fest, Kennung nur aus `app_state`, Reset 30 min nach Änderung; Anmeldung abschaltbar | `referenzdatensatz/fixture/riegelprobe.php` | 3 |
| I-23 | Ingest und Kopplung | `ingest.php`; `pair.php`; `kopplung_lib.php`; `db.php` `geraet_schluessel_hash()` | Geräteschlüssel 192 Bit Zufall, gespeichert als SHA-256; Blindvergleich; Ersetzfenster 72 h nur für eigene Einsätze (`device_id` + `client_ref`); Kopplung nach 4.99b (Code weist nichts aus, Rückbestätigung am Gerät) | `proben/ingest`, `proben/kopplung`, `proben/geraete` | 2 (H), 4 |
| I-24 | Rollen und Kontentrennung | `auth_guard.php`; `einsatz_lib.php` `einsatz_laden()`; Matrix 4.99p | `user_id` in den gelesenen Abfragen (Stichproben: `api/mission.php`, `api/suchindex.php`, `gpx.php`, `api/pat_anheben.php`); die Rollenprobe misst `{ziel}` = `user`, nicht Admin → BetreiberIn | `proben/rollen` | 2 (I, J, K), F-R17-07 |
| I-25 | Protokoll und Systemmeldungen | `protokoll_lib.php` `protokoll()`; `systemmeldung_lib.php` `system_melden()` | Adressen der Konten im Text, Verwaltung 365 Tage (90–1095), übrige 30; keine geschützten Felder, keine Tokens, keine Passwörter gefunden; Archiv versiegelt | `proben/protokoll` | 3 |
| I-26 | CSP und Kopfzeilen | `kopfzeilen_lib.php` `kopf_csp()`, `kopf_hsts()`; `.htaccess` | Nonce-CSP (`default-src 'none'`, Geocoder und vier Kacheldomains), **Vorgabe Report-Only**; HSTS Vorgabe 1 Tag; `nosniff`, `DENY`, Referrer-Policy | `proben/csp-browser`, `quelltext/csp` | 4, 5, F-R17-23 |
| I-27 | Adresssuche und Kacheln | `geocoder_lib.php`; `assets/geocoder.js`, `ortsfeld.js`, `map_layers.js` | Photon (`photon.komoot.io`, Vorgabe **an**) bekommt getippte Adresse und Koordinate der geschützten Felder `loc.*`/`start.*`; Kacheln von OSM, openmaps.fr, OpenTopoMap, Esri | `proben/csp-browser` | 3 (Lage G), F-R17-09 |
| I-28 | Suchindex und Export | `api/suchindex.php`; `api/export_data.php`; `assets/export.js` | Index je Konto, Block als Chiffre; Export im Browser entschlüsselt, ZIP-AES optional, sonst Klartextdatei auf dem Gerät | keine | 3 |
| I-29 | Speicherdauern | `trash_lib.php`; `jobs_lib.php` (Aufräumen); `konto_lib.php` | Papierkorb 90 d; Protokoll 365/30 d; `sicherheit_ereignisse` 30 d; Mail 30 d (Rumpf bei Zustellung geleert); Sitzungen 30 min + Karenz; Selbstlöschung 30 d; unbestätigte Konten 48 h; Kontopakete 2, Komplett-Stände 2; **Ziele unbegrenzt**; Spuren ab 6 Monaten ausgedünnt; auf Produktiv kein Cron (huckepack) | `proben/jobs`, `quelltext/jobregister` | 3 |
| I-30 | Unangemeldet sichtbar | `ui.php`; `api/health.php`; `jobs.php`; `.htaccess` | Fassung in Fußzeile und `data-webversion`; `health.php` und `jobs.php` mit Token als GET-Parameter; `.htaccess` sperrt `config.php`, `schema.sql`, `db.php`, `smtp.php`, `auth_guard.php`, `install.lock`, Punktpfade, `apk/`, `demo/`, Nachweismuster — nicht `wartung.lock`, `ueberlast.json`, `migrations/` | `proben/raten`, `proben/wartung` | 2, 3, F-R17-22 |

**Uhr und Android**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-31 | Garmin: Zugangsdaten | `watch/source/Pair.mc` `_fertig()`; `Uploader.mc` `credentials()`; `resources/settings/properties.xml` | `Storage "cred"` = Kennung und Schlüssel **im Klartext**; Rückfall auf die App-Einstellungen `deviceId`/`apiKey` (Alt-Weg, in Garmin Connect eingetippt); schwebende Daten bis zum Ja nur im RAM | keine; Uhr-Prüfstand übersetzt und fotografiert nur | 2 (H), 4, F-R17-12 |
| I-32 | Garmin: Serveradresse und Transport | `Uploader.mc` `_serverUrl()`, `serverBase()`; `Communications.makeWebRequest` | Adresse aus den App-Einstellungen (in Garmin Connect änderbar); ohne `http`-Anfang wird `https://` vorangestellt, **`http://` bleibt**; jede Anfrage läuft über die Garmin-Begleit-App des Telefons; kein Pinning | keine | 2 (G, M), F-R17-11, -12 |
| I-33 | Garmin: Puffer | `Model.mc` `save()`; `Track.mc` `_flush()`, `readPoints()`; `Cpr.mc` | Laufender und alle unbestätigten Einsätze mit Phasen (Koordinate) und **Reanimationssitzungen**, Spurpunkte in Chunks à 200 — alles Klartext im Gerätespeicher; Gesendetes wird nach Bestätigung gelöscht, **Geparktes bleibt ohne Frist**, nach Trennen im Web bleibt alles bis zur Neukopplung | keine | 2 (H), 6, F-R17-13, -28 |
| I-34 | Garmin: Krypto-Fähigkeit | `watch/manifest.xml` `minApiLevel="3.1.0"`; `grep Toybox.Cryptography` = 0 | Kein Hash, keine Verschlüsselung, kein HMAC im Code; Zufall nur `Math.rand()` für `client_ref`. Was `Toybox.Cryptography` auf den 99 Zielgeräten kann, ist **im Container nicht prüfbar** (kein SDK) — SP-9 behauptet es | keine; Stufe I des Uhr-Prüfstands könnte es mit einer Probe-App messen | 6 |
| I-35 | Handy: Tresor | `tresor/Schluesseltresor.kt`; `tresor/Tresorschluessel.kt` | `tresor.bin` als JSON unter AES-256-GCM, Schlüssel `nadoku-tresor` im AndroidKeyStore, `setUserAuthenticationRequired(false)`, kein StrongBox, kein `setUnlockedDeviceRequired`; schwebende Kopplungsdaten in der Datei | `SchluesseltresorTest` (8), `GeraetTresorTest` (4, nur auf Gerät) | 4 |
| I-36 | Handy: Puffer | `puffer/Puffer.kt`; `Raeumung` | `puffer.db` (SQLite) im Klartext: Punkte, Phasen mit Koordinate, Dienste; **keine Reanimation**; Abgewiesene nach 30 Tagen und beim Trennen geräumt; kein SQLCipher | `PufferMigrationTest`, `AbgewieseneTest` | 2 (H), 6 |
| I-37 | Handy: Backup, Netz, Komponenten | `AndroidManifest.xml`; `res/xml/datensicherung.xml`; `kopplung/Serveradresse.kt`; `src/release/res/xml/netzsicherheit.xml` | `allowBackup=false`, Übertragungsregeln schließen alles aus; Adresse fest aus `BuildConfig`, Release immer `https`, Klartext verboten, nur System-CAs, kein Pinning (Nr. 143); acht Rechte, exportiert nur Launcher und Data-Layer-Horcher; Logs ohne Koordinaten und Schlüssel | `ServeradresseTest`; Manifest-Lesung | 4 |
| I-38 | Wear-OS-Uhr | `uhr/src/main/AndroidManifest.xml`; `gemeinsam/…/Uhrnachricht.kt`; `uhr/…/funk/UhrHorcher.kt`, `Uhrpuffer.kt` | **Keine** Berechtigung, kein Netz, kein Tresor; Format ohne Koordinaten und ohne Schlüssel (`NachrichtenformatTest`); Puffer nur Ereigniszeiten und Phasennummern; Empfang von Quittung und Stand **ohne Absenderprüfung** (das Handy prüft) | `NachrichtenformatTest` (11), `UhrfunkTest` | 4 |
| I-39 | Data Layer | `gemeinsam/…/WearNachrichtenweg.kt`; `handy/…/HandyHorcher.kt`; `uhr/…/UhrHorcher.kt` | `MessageClient.sendMessage` an alle `connectedNodes`; Play-Dienste-Typen in **drei** Dateien (Senden hinter der Schnittstelle, Empfang prinzipbedingt nicht); Handy prüft Absender und Zeitfenster | `UhrannahmeTest`, Attrappe; `DataLayerErreichbarTest` (Gerät) | 3 (Lage L), F-R17-27 |
| I-40 | Signatur und Bau | `tools/kette/appbau.sh` `android_bauen()`; `auslieferung.yml` Job `android` | Ein RSA-4096-Schlüssel signiert Handy- und Wear-APK, derselbe liegt seit 26.09.2026 bei Google; `./gradlew` läuft im Schritt, dessen `env:` Keystore (Base64) und beide Passwörter trägt — **Gradle und jedes Plugin erben sie**; keine `verification-metadata.xml`; Garmin-Entwicklerschlüssel nur im Bauschritt, SDK ohne Prüfsumme | `appbau.sh --selbstprobe` (prüft das Zertifikat, nicht die Umgebung) | 2 (C3, L), 4, F-R17-03 |
| I-41 | Transport Gerät → Server | `docs/JSON-Vertrag.md`; `Sendekoerper.kt`; `Uploader.mc` `_send()` | `X-Device-Id`/`X-Api-Key` statisch je Anfrage, keine Signatur über den Rumpf, kein Zeitstempel; Rumpf im Klartext: Spur, Phasen mit Koordinate, Zeiten, Strecke, Garmin dazu Reanimation; Replay idempotent über `client_ref`+`seq`; **Takt und Größe** verraten Dienstbeginn, Phasen, Ende auch bei intaktem TLS | `proben/ingest`, `SendekoerperTest` | 3, 5 |

**Kette und Betrieb**

| Nr. | Baustein | Fundstelle | Was gilt (gelesen) | Probe | Modell |
|---|---|---|---|---|---|
| I-42 | Auslöser und Freigabe | `auslieferung.yml` `on:`; `ausliefern-lauf.yml` `environment:` | Push auf `main` → Staging; Tags `web-v*`, `android-v*`, `uhr-v*`; `workflow_dispatch` **auf jedem Ref** mit Probelauf-Schaltern — ein Probelauf fordert die Umgebung `produktion` an, mit der **Workflow-Datei des Zweigs**; alle übrigen Riegel stehen in dieser Datei; wer freigeben darf, ist nicht im Repositorium | Probelauf von Hand | 2 (C2), 4, F-R17-04 |
| I-43 | Tore des Produktionslaufs | `ausliefern-lauf.yml`; `tools/kette/freigabe.py`, `baumsuche.py`, `tor.py` | Grüner Stufe-1-Lauf auf demselben **Baum**, grüner Staging-Job auf dem Commit, Abstammung vom Zeiger, Backup-Tor (bis 40 × 20 s, prüft Frische, nicht Lesbarkeit), Tag = `WEB_VERSION`, Nachprüfung der gemeldeten Fassung, Wartung bleibt bei offener Migration an. **Nicht für `android` und `uhr`**: dort nur Tag = Fassung und Freigabe | Selbstproben in Stufe 1 | 4, F-R17-17 |
| I-44 | FTPS-Transport | `SamKirkland/FTP-Deploy-Action@110f9186…` (v4.4.0); Ausnahmeliste | Acht Pfade, die drei Verzeichnisse doppelt — stimmt mit `CLAUDE.md` 3; Löschliste nur aus der eigenen Zustandsdatei, die mit `FTP_ZIELPFAD=/` **im Webroot** liegt (Punktregel der `.htaccess` als einzige Schranke); das FTP-Konto sieht `config.php`, `sicherungen/`, `.sitzungen/` | **kein Prüfmittel zählt die Liste** | 4, F-R17-16, -31 |
| I-45 | Pinning und Lieferkette | vier Workflows; `tools/uhr-pruefstand/pruefstand.sh`; Job `stufe2` | 14 von 14 fremden `uses:`-Zeilen an einer SHA (nachgezählt 11.10.2026); **ungepinnt in `run:`**: `pip install cffi cryptography`, `playwright@1.56`, Gradle-Abhängigkeiten, Connect-IQ-SDK — teils im selben Job wie Staging-Passwort, TOTP-Geheimnis, `JOBS_TOKEN` | kein Werkzeug (`kettenaufrufe` liest keine `uses:`) | 4, F-R17-16, -20 |
| I-46 | Geheimnisse der Kette | Workflows; `Technik.md` 6.5; `Sandbox-Setup.md` 4 | `staging`: FTP (3), `JOBS_TOKEN`, Prüfkonto, Passwort, `STAGING_TOTP`; `produktion`: FTP (3), `JOBS_TOKEN`, vier `APK_*`, `UHR_ENTWICKLERSCHLUESSEL_B64`; Repositorium: `CIQ_GERAETE_URL`, `WACHE_BASIS`. Fork-PRs bekommen keine; ein PR aus dem Repositorium fährt seine eigene Workflow-Datei. Staging-Zugänge liegen auch in der Arbeitsumgebung | Stufe-1-Schritt „eine Ebene höher?" (6 Namen) | 3, 4 |
| I-47 | `JOBS_TOKEN` | `jobs.php`; `jobs_lib.php`; `tor.py` | 64 Hex im Klartext in `app_state` (Betrieb → Hintergrundjobs), als GET-Parameter; kann Komplettbackup anstoßen, Wartung schalten, Jobs anhalten; in jedem Komplett-Dump und in jedem Hoster-Zugriffslog eines Kettenlaufs | `tor.py --selbstprobe` | 3, 4, F-R17-22 |
| I-48 | Zweigschutz und Deny-Liste | Rulesets (API, 11.10.2026); `.claude/settings.json` | „Main Protect": PR-Pflicht, **0 Pflicht-Reviews**, Stufe 1 strikt; Merge-Bypass eine Person; **kein Tag-Ruleset**; Zweig `produktion` **ohne Regel** (`rules/branches/produktion` = `[]`); Deny-Liste sperrt nur Merge und Auto-Merge — `push_files`, `create_branch`, `run_workflow`, `cancel_workflow_run` bleiben | gemessen 21.09.2026 (Abwesenheit) | 2 (C2), 4, F-R17-14 |
| I-49 | Integritätswache | `tools/integritaetswache/wache.py`; `integritaet.yml` | Täglich und nach jeder Auslieferung; holt über HTTPS **ohne Zugangsdaten** `server/assets/` und vergleicht SHA-256 gegen den Zeiger; für `login.php` die Menge der Inline-Skripte, Formulare, Umlenkungen; sieht keinen PHP-Code, keine `config.php`, kein `auto_prepend_file`, keine andere Passwortseite (Nr. 358); Alarm = roter Lauf an die Person der letzten `cron`-Änderung | `wache.py --selbstprobe` | 3 (Nr. 263), 4, F-R17-15 |
| I-50 | Wiederanlaufpaket und Schlüsselblatt | `Technik.md` 7; `betrieb_schluesselblatt.php`; Rahmenplan 6.1 | Vier Stücke (`config.php`, Serverschlüssel, Server-Anteil, Zugang zum Ziel), Blatt in zwei Ausdrucken; laut 6.1 **offen seit 14.09.2026**: Anteil anlegen, Blatt drucken, Paket ergänzen; Komplett-Stände auf Produktiv nie geöffnet (seit 25.09.2026 offen); Probe-Wiederherstellung nie gefahren | keine | 5, F-R17-05 |
| I-51 | Mailweg | `mail_lib.php` `mail_einreihen()`, `mail_konto_entfernen()`; `smtp.php` | Warteschlange hält Adresse, Betreff und **Rumpf mit Setz-Links im Klartext** bis zur Zustellung, dann geleert, 30 Tage; SMTP-Zugang in `config.php`; keine geschützten Felder in Mails; `mail_konto_entfernen()` hat **keinen Aufrufer** | `proben/mail` | 3, F-R17-21, -22 |
| I-52 | Wiederherstellen ohne Anmeldung | `wiederherstellen.php` | Schranken: leere Datenbank, Nachweisdatei, Quelle nur `sicherungen/eingang/`; ruft `edbak_ablage_bereit()` **nicht** — auf frischem Webspace fehlt `sicherungen/.htaccess`; eine Klartext-`.sql.gz` dort trägt einen sekundengenau ratbaren Namen | `proben/wiederherstellung` (örtlich) | 4, F-R17-19 |
| I-53 | Staging gegen Produktiv | `Technik.md` 6.3a; Rahmenplan 6a | Zwei Hoster seit 20.09.2026; Staging ohne Echtdaten, eigene Schlüssel; Stufe 2 schreibt auf Staging; die Sperre der Punktdateien ist **nur auf Staging** gemessen; Produktiv-Hoster nur im AVV-Entwurf benannt | Stufe 2 | 3, F-R17-18 |
| I-54 | Die eine Person | `Technik.md` 7 „Notweg"; Rahmenplan 6.2, 6.3 | An der Betreiberin hängen Freigabe, Merge-Bypass, Schlüsselblatt, Betriebsakte, Hoster- und FTP-Zugänge, GitHub-Umgebungen, Play Console, Connect-IQ-Store; Notzugang braucht FTP **und** Datenbank; bei Ausfall keine Auslieferung, kein Merge, kein Schlüsselnachtrag | `proben/zweitfaktor`, `rueckweg` (Stufe 2) | 2 (P), 5 |
| I-55 | Der Hoster | `Technik.md` 6.3a; `docs/rechtstexte/AVV.md` | Liest alles auf der Platte: `config.php`, Sitzungsdateien, Sicherungen, Datenbank, Zugriffsprotokolle mit Tokens; verschlossen bleibt `pat_blob` ohne Passwort oder Zettel — **bis er Code ändert** und das nächste Passwort mitliest; die Wache sieht davon `assets/` und `login.php` | Wache (teilweise) | 2 (O), 3 |

### 3.3 Lage in einem Absatz (R17-01)

Gegen den klassischen Einbruch — Datenbank, Dateisystem, Hoster — hält die
Verschlüsselung des Blocks, und sie hält genau so lange, wie das Passwort
stark ist (I-03: das Offline-Raten bleibt, I-02 verhindert nur das Öffnen
der Hülle aus dem Abzug). Alles neben dem Block liegt offen und
rekonstruiert den Einsatz (I-07). Gegen aktiven Zugriff auf Server oder
Kette (C, C2, C3) steht zwischen Push-Recht und Produktiv **ein Klick**
(I-42, I-48), und Gradle sieht den Signaturschlüssel (I-40). Innerhalb der
Anwendung liest ein Admin über die Freigabe den Klartext jedes Kontos
(I-18), ohne Spur. Die Geräte tragen Schlüssel und Klartextpuffer ohne
Verschlüsselung at rest (I-31, I-33, I-36); SP-9 würde den Server schützen,
nicht das verlorene Gerät. Und die Pflichtstücke des Wiederanlaufs sind laut
Rahmenplan seit vier Wochen offen (I-50).

### 3.4 Der Krypto-Review vom 05.09.2026 — Stand je Befund (R17-04)

Die Datei `docs/konzepte/Review-Krypto-Sicherheit.md` (Stand Web 14.2.2,
Uhr 3.0.2, Android 0.13.0) ist mit R17-04 gelöscht (E-R17-13; zuletzt in
`bc08eda`). Ihr Abschnitt 2 ist durch das Modell ersetzt; ihre Befunde
stehen hier mit dem Stand bei Web 21.14.0, damit Schritt 12b weiß, was
noch zu lesen ist. **„erledigt" heißt: im Code nachgelesen (Inventar
I-NN); „offen für 12b" heißt: nicht nachgelesen, Stück genannt.**

| Nr. | Befund (Kurzform) | Stand bei Web 21.14.0 |
|---|---|---|
| K-1 | Klartext-Ortsdaten | Weg C erledigt (Web 15.6.0, Nr. 138); Weg B ist S11 (12a, E-R17-09, -10); Modell G3, I-07 |
| K-2 | keine CSP; Schlüssel als Hex im `sessionStorage` | CSP mit Nonce gebaut (P5a/AP4), **Vorgabe Report-Only** (F-R17-23, I-26); `CryptoKey` entschieden für 12b (E-R17-08, Nr. 370) |
| K-3 | 320 000 Runden; Passwortstärke einzige Schranke | 600 000 Runden erledigt (SP-1, I-01); Mindestlänge in `pwquality.js` **beantwortet (R17-06, 7.3.2):** `MIN_LAENGE = 12` wie `PW_MIN_LAENGE` in `db.php`, dazu Liste häufiger Passwörter und Anteilsregel — kein Befund; die Rechnung des Angreifers steht im Modell 4.1 und F-R17-01 |
| K-4 | Adminpakete unversiegelt, `ftp` | erledigt (S10: Fassung 3 versiegelt, `ftp` abgeschafft; I-18); FTPS weiter ohne Zertifikatsprüfung (I-20, Modell 4.4) |
| K-5 | kein Zweitfaktor für NutzerInnen | erledigt (TOTP für alle, 10c/AP5; Passkeys SR-09; I-11) |
| K-6 | Einsatzort an Photon und Kachelserver | Handbuch 12.5 und Textbaustein nennen es; Schalter vorhanden; die Zusage-Texte nicht → **F-R17-09** |
| K-7 | E-Mail-Wechsel ohne Passwort; Admin-Adresswechsel | Profilwechsel: P5b (Double-Opt-In) — **beantwortet (R17-07, 7.3.3):** Passwortnachweis, Vormerkung, Link an die neue und Warnung an die alte Adresse, kein Befund (der Nachweis ohne Topf → F-R17-32); der Admin-Adresswechsel ohne Bestätigung steht → **F-R17-06** |
| K-8 | Login-CSRF | erledigt (Nr. 127, I-08) |
| K-9 | `apk/` und `demo/` unversperrt | erledigt (`.htaccess` 403, I-30) |
| K-10 | DOCTYPE-Sperre im GPX-Import umgehbar (UTF-16) | **gegenstandslos seit S11-05d** (R17-08, 20.10.2026): Der Server liest keine GPX-Datei mehr — `api/gpx_import.php` nimmt verschlossene Stücke und prüft Form, Menge und Kennung; die Datei liest `assets/gpx.js` im Browser, weist ein Nullbyte (UTF-16) vor dem Dekodieren ab und sucht `<!DOCTYPE` im dekodierten Text. Die Regeln sind dort „Bequemlichkeit, keine Schranke" (Kopfkommentar von `api/gpx_import.php`): Wer sie umgeht, schadet dem eigenen Konto |
| K-11 | Auskunft in `wiederherstellen.php` | erledigt (nur „in Betrieb", I-30); neu daneben F-R17-19 |
| K-12 | Klartext-Freitextfelder ohne Hinweis | erledigt (Kleinzeile an jedem Klartext-Freitextfeld, Riegel `kennzeichnung`; Nr. 109 Notizen ins Blob); die Liste in `CLAUDE.md` 4 ist kürzer als der Bestand → F-R17-25 |
| K-13 | Reste im Klartext (Reset-Token, Setz-Link, Bauordner) | Bauordner wird geräumt (Web 15.6.0); Reset-Token im Zugriffslog bleibt, dazu neu der Mailrumpf → **F-R17-22**; Klartext-Dump im Bauordner während des Baus bleibt (F-R17-31) |
| K-14 | verlorene Garmin-Uhr | Ersetzfenster 72 h (Web 15.6.0, I-23), Handbuch-Kasten; Rest: Klartextpuffer samt Reanimation ohne Frist → **F-R17-13, -28**; Serveradresse und Begleit-App → **F-R17-12** |
| K-15 | Header und Escaping (HSTS, Permissions-Policy, `JSON_HEX`, `csrf_check`, `querySelector`) | `JSON_HEX_*`, Permissions-Policy, Referrer-Policy erledigt (I-26); HSTS Vorgabe 1 Tag → F-R17-23; `csrf_check`-Cast und `suche.php` (Nr. 153 erledigt laut Backlog-Erledigt) — **`csrf_check` beantwortet (R17-07, 7.3.3):** `csrf_ok()` prüft `is_string()` vor `hash_equals()`, kein TypeError; die Kopfzeilen sind vollständig — kein Befund |
| K-16 | Push auf `main` ist Deploy; Branch-Schutz, Org-2FA | Kette mit Freigabe (R67, PK); Ruleset ohne Pflicht-Review (F-R17-29); Org-2FA **unbelegt** (Prüfdokument 1, P-R17-04); neu: **F-R17-04** (Probelauf von jedem Zweig), **F-R17-14** (Zeiger ohne Regel) |
| AN-1 | HTTP-Ausnahme im Release | erledigt (Nr. 142, `netzsicherheit.xml`, I-37) |
| AN-2 | abgewiesene Pakete bleiben | erledigt für Android (30 Tage, I-36); **für die Garmin offen → F-R17-28** |
| AN-3 | kein Pinning, nicht festgehalten | erledigt (Nr. 143 dokumentiert, I-37) |
| AN-4 | Data-Layer-Empfang ohne Absenderprüfung | Handy prüft seit 0.14.0 (Nr. 144, I-39); die Wear-Uhr nicht (F-R17-31) |
| AN-5 | kein R8, Wrapper ohne Prüfsumme | Wrapper mit `distributionSha256Sum` (Nr. 145); **R8/`isMinifyEnabled` beantwortet (R17-13, 7.3.9):** in beiden Modulen `false`, begründet in `proguard-rules.pro` (E-R45-7) — kein Befund |

**Aus ihrer Prüfliste (Abschnitt 7) ist noch nicht abgehakt:** Org-2FA und
die Regeln der Umgebungen (P-R17-04), die Passwortstärke der echten Konten
(Einweisung, Modell R1), `aapt2 dump permissions` der Wear-Release-APK
(P-R17-05), die Datenschutzerklärung der Installation mit Photon und den
Kacheldiensten (Betreiberin, Rechtstexte). Die übrigen Punkte sind durch
Proben oder Riegel ersetzt (`proben/sitzung`, `proben/frist`,
`proben/anteil`, `proben/raten`, Stufe 2 „Punktdateien") oder erledigt.

## 4. Arbeitspakete

| Paket | Inhalt | Fächerung | Abnahme |
|---|---|---|---|
| **R17-00 Buchführung** | Spanne 370–379, Fahrplanzeile (Rahmenplan Fassung 154), dieses Dokument mit Statusblock, Prüfdokument; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-01 Bestandsaufnahme** | Die verstreuten Stellen einsammeln und **gegen den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegenlesen**, nicht gegen die Doku: `Technik.md` 4.97, 4.97c/d, 4.98, 4.99a/a2/b/q, `Review-Krypto-Sicherheit.md` 2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18 (Sitzungsbindung, Gerät merken, Passkeys, Schlüsselwechsel, Notzugang, Rechenaufgabe), Kette und ihre Geheimnisse, Keystore des Handys, Geräteschlüssel der Uhr, Play App Signing. Ergebnis: Inventartabelle in 3.2, Befunde, wo Doku und Code auseinandergehen. | **lesend, drei Leser** (Server; Clients; Kette und Betrieb), ohne Nebenwirkung; Fable führt zusammen. | Inventar vollständig gegen die Liste im Fahrplan (Schritt 12: „Verschlüsselung, Container 4, SPUR1, Komplettbackup und Serverschlüssel, Demo, Handy, Kopplung, SP-9 und Nr. 146, Signatur, Kette, Nr. 109, Nr. 263"); jede Zeile mit Fundstelle. |
| **R17-02 Das Modell schreiben** | `docs/Bedrohungsmodell.md` nach 3.1; Bereich `c` der Textprobe; Verweise aus `README.md`, `Technik.md` (4.99b und der Sicherheitsabschnitt verweisen hin, statt zu wiederholen), `CLAUDE.md` 4. | seriell | Modell vollständig gegen 3.1; Textprobe 0 neue Treffer; Verweise stehen. |
| **R17-03 Fragen an die Betreiberin** | Q-R17-NN mit Empfehlung und Preis: Nr. 146 (Argon2id; `CryptoKey`), S11-Entwurfsfragen, Nr. 263. **Halt:** wartet auf die Antworten. | seriell | Jede Frage beantwortet und als E-R17-NN eingetragen; Nr. 146 erledigt, Nr. 263 mit Stand. |
| **R17-04 Abschluss** | Antworten ins Modell einarbeiten, Prüfdokument mit Mittel und Zahl, Prüfstand `klein`, Freigabe der Betreiberin; danach Erledigt-Zeile (Rahmenplan 8), Backlog-Kopfzeilen, Verlaufszeile, Pull Request gegen `main`. | seriell | Freigabe erteilt; PR gestellt. |
| **R17-05 Buchführung 12b** | Spanne 400–409 (zuerst gepusht), Rahmenplan Fassung 167 (Fahrplanzeile, Kopf), Abschnitt 7 und 2.3a hier, Prüfdokument fortgeschrieben, E-R17-14 bis -16; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-06 Stück 2 — Kryptographie und Schlüssel** | Server- und Browser-Krypto samt S11 (7.3, Stück 2); K-3 (`pwquality.js`); Nr. 370 als Entwurfsfrage. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 38 Dateien gelesen (7.3.2), F-R17-32 bis -35 in 2.3a, sieben Altbefunde bestätigt, K-3 beantwortet, Nr. 370 mit Verwenderzahl notiert; Prüfprotokoll mit Zahl (5). |
| **R17-07 Stück 3 — Zugang und Sitzung** | Anmeldung, Sitzung, Zweitfaktor, Registrierung, Kopplung, Ratenschutz, CSRF; jeder HTTP-Einstieg des Stücks gegen die Checkliste (7.2); K-7, K-15. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 31 Dateien und der API-Teil von `db.php` gelesen (7.3.3), F-R17-36 und -37 in 2.3a, F-R17-32 verschärft, F-R17-22, -23, -31 bestätigt, K-7 und K-15 beantwortet, P-R17-07; Prüfprotokoll mit Zahl (5). |
| **R17-08 Stück 4 — Einsatz, Diensttag, Spur, Ingest** | Ingest, SPUR2, Diensttag, Einsatz, Nachbearbeitung, GPX-Import, Prüfschicht, Feldkatalog; K-10. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 36 Dateien gelesen (7.3.4), F-R17-38 bis -41 in 2.3a, F-R17-09, -13, -28 nachgelesen (soweit das Stück sie sieht), K-10 beantwortet, Nr. 23 gelesen, P-R17-08; Prüfprotokoll mit Zahl (5). |
| **R17-09 Stück 5 — Verwaltung und Admin** | Einstellungen, Admin-Seiten, Stammdaten, Rechtstexte, Papierkorb, Suche, Zeitraum, APK-Ausgabe, Statistik; Nr. 371, 381; F-R17-02, -06, -07, -08 in Endfassung. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 31 Dateien gelesen (7.3.5), F-R17-42 bis -45 in 2.3a, F-R17-07 und -08 bestätigt (Nr. 371, 381), F-R17-02 und -06 unverändert, P-R17-09; Prüfprotokoll mit Zahl (5). |
| **R17-10 Stück 6 — Betrieb** | `update.php` und Migrationen, Wartung, Jobs, Sicherungsziele, Mail, Installation, `db.php`, `ui.php`, Kopfzeilen, `.htaccess`; Nr. 77; F-R17-19, -21. | seriell, Fable liest; Vorsortierung örtlich (PHPStan, eigene Zähler) | **Erledigt 20.10.2026:** 48 Dateien gelesen (7.3.6), F-R17-46 bis -49 in 2.3a, F-R17-19 und -21 bestätigt, F-R17-05 unverändert, Nr. 77 gelesen, P-R17-10; Prüfprotokoll mit Zahl (5). |
| **R17-11 Stück 7 — Browser-Skripte und Stylesheet** | alle `assets/*.js`, `style.css`, die Köpfe unter `vendor/`; der `innerHTML`-Durchgang (Prüfdokument 1 (11)); Nr. 341, 380, 382; F-R17-24. | seriell, Fable liest; Vorsortierung örtlich (`node --check`, Prüfsummen, `vollstaendigkeit.py`, eigene Zähler) | **Erledigt 20.10.2026:** 58 Dateien gelesen (7.3.7), F-R17-50 und -51 in 2.3a, F-R17-24 bestätigt, Nr. 341 gemessen, 380 gezählt, 382 gelesen; Prüfprotokoll mit Zahl (5). |
| **R17-12 Stück 8 — Uhr** | `watch/` vollständig (Krypto der Gerätehülle, Puffer, Uploader, Kopplung, Anzeige); F-R17-11, -12, -13, -28. | wie R17-06; Compiler-Warnungen nur, wenn das SDK im Container steht | **Erledigt 20.10.2026:** 39 Dateien gelesen (7.3.8), F-R17-52 und -53 in 2.3a, F-R17-11, -12, -13 bestätigt, F-R17-28 zur Hälfte erledigt; Uhr-Prüfstand Stufe I 99 von 99 übersetzt, 0 Warnungen; Prüfprotokoll mit Zahl (5). |
| **R17-13 Stück 9 — Android** | beide Module, Gradle, Manifeste, Ressourcen; AN-5 (R8); F-R17-03, -27, -31. | wie R17-06; Android Lint örtlich | **Erledigt 20.10.2026:** 135 Dateien gelesen (7.3.9), F-R17-54 in 2.3a, F-R17-27 und -31 bestätigt, F-R17-03 ohne zweiten Leser in `android/`, AN-5 beantwortet; Gradle `lint test` im Container: 280 und 72 Prüffälle je Bauart, 0 rot, Lint 0 Fehler; Prüfprotokoll mit Zahl (5). |
| **R17-14 Stück 10 — Werkzeuge und Workflows** | `tools/` und `.github/`; je Werkzeug: prüft es noch, was der Code tut?; Nr. 188, 263; F-R17-04, -14 bis -18, -20. | wie R17-06; `vulture` örtlich | **Erledigt 21.10.2026:** 277 Werkzeugdateien gelesen, 160 weitere katalogisiert (7.3.10), F-R17-55 bis -57 in 2.3a, sieben Altbefunde bestätigt (-16 ergänzt, -20 verschärft), F-R17-14 am Code nicht messbar, Nr. 188 belegt, Nr. 263 bestätigt; 13 Selbstproben und Erzeuger im Container grün; Prüfprotokoll mit Zahl (5). |
| **R17-15 Stück 11 — Doku gegen Code** | die normative Doku gegen den Stand Web 23.0.2, Uhr 4.0.0, Android 0.18.0; F-R17-09, -25, -26, -29, -30. | wie R17-06; dazu `grep`, `php -r`, die Riegel selbst (`jobregister`, `sitzungshaertung`, `kennzeichnung`, `zaehlen.php`) | **Erledigt 21.10.2026:** 19 Dokumente, 33 903 Zeilen gelesen, rund 120 Aussagen nachgemessen (7.3.11), F-R17-58 und -59 in 2.3a, F-R17-09, -25, -26, -30 bestätigt, F-R17-29 zum Teil offen; Prüfprotokoll mit Zahl (5). |
| **R17-16 Stück 12 — Zusammenführung** | Fundliste sortiert (alle Grade, 2.3 und 2.3a), Sofortpaket ausgewiesen, Kommentardurchgang und Karteileichen je Codebasis gebündelt, Vorschlag für den Paketschnitt, die R5-Ausnahmeliste als Zuarbeit für P7. | seriell | **Erledigt 21.10.2026:** 59 Befunde in einer Liste mit Paket je Nummer (7.3.12), Sofortpaket leer (E-R17-06), Paketschnitt-Entwurf A bis K mit Modell je Paket, Aufräumposten je Codebasis (24 A4-Kandidaten für Nr. 21), Kommentardurchgang 11 855 Verweise in 613 Dateien, R5-Liste leer mit Messung, 3.4 nachgetragen; Prüfprotokoll mit Zahl (5). |
| **R17-17 Freigaberunde** | Q-R17-NN je Fund mit Empfehlung und Preis. **Halt:** wartet auf die Antworten der Betreiberin; danach E-R17-NN, Backlog-Kopfzeilen, der Paketschnitt wird verbindlich, Rahmenplan (Reihenfolge, R-Nummer, Fahrplanzeile). | seriell | **In Arbeit seit 21.10.2026:** Q-R17-09 bis -19 gestellt (2.2), die Liste als Ganzes plus zehn Wahlstellen; **Halt** bei der Betreiberin. Abnahme: jede Frage beantwortet und als E-R17-NN eingetragen; Paketschnitt steht. |

Die Pakete **nach** der Freigaberunde (Sofortpaket, Pflichtpakete,
Aufräumpakete je Codebasis, Kommentardurchgang) bekommen ihre Zeilen mit
R17-17 — der Paketschnitt folgt der Freigaberunde, nicht umgekehrt
(E-PV-5 (8)).

## 5. Prüfprotokoll

Je Paket: was gemessen wurde, mit Mittel und Zahl. Die Prüfliste für die
Betreiberin steht im Prüfdokument.

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| R17-16 | Lesung Fable der 59 Befundzeilen (2.3, 2.3a), der 3.4-Posten, der Aufräumposten aus 7.3.2 bis 7.3.11 (Auszug `aufraeumposten.txt`, 1 404 Zeilen), der zehn Backlog-Texte (`uebersicht.py --ziel 12b`) und der Rahmenplan-Zeilen 12b, 13, R5, R71 · `zaehlung-r17-16.py` (das weiteste Muster der Stücke, aus 7.3.9, über `git ls-files` nach Endung je Codebasis) · `grep -oiE` über Luft- und Bodenbegriffe in `schema.sql`, `JSON-Vertrag.md`, `Export-Format.md`, `Backup-Format.md`, `Const.mc`, `mission_fields.php` (R5) · `grep -rlE "Anlass: Nr\."` · `decken.py` · `nummern.py` · `uebersicht.py --ziel 12b` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 59 Befunde: 5 hoch, 22 mittel, 25 niedrig, 7 Hinweise — jeder mit Paket; 0 kritisch, Sofortpaket leer; 11 Pakete im Entwurf (5 Fable, 6 Opus) und 4 Zuarbeiten der Betreiberin; 5 Posten aus 3.4 erledigt, 10 Backlog-Punkte eingeordnet · Kommentardurchgang: 613 Dateien, 227 731 Zeilen, 11 855 Verweise (Server 164/101 298/6 532, Browser 58/22 597/971, Uhr 39/5 650/133, Android 134/22 435/956, Werkzeuge 218/75 751/3 263); davon 895 in `version.php` und `version.properties`; 84 Werkzeugdateien mit Anlass-Zeile · R5: 0 irreführende gespeicherte Namen, 5 seit Web 6.0.0 umbenannt, 11 luftgebundene Namen mit Grund · Karteileichen mit Gegenprobe: 24 (6 PHP, 14 JS-Exporte, 2 Monkey C, 2 Python) · Steuerung und Tor: Commit-Nachricht von R17-16 | 21.10.2026 |
| R17-15 | Lesung Fable (E-R17-14) der 19 Dokumente aus einem Dump mit Zeilennummern (`stueck11-doku.txt`, 33 922 Zeilen mit Köpfen) · je prüfbare Aussage ein `grep`, `php -r`, `ls`, `wc` oder der Riegel selbst: Konstanten in `db.php`, `validate_lib.php`, `ratelimit_lib.php`, `totp_lib.php`, `passkey_lib.php`, `mail_lib.php`, `jobs_lib.php`, `spur_lib.php`, `kopfzeilen_lib.php`, `instanz_lib.php`, `wartung_lib.php`, `protokoll_lib.php`, `registrier*.php`, `Const.mc`, `Ortungswaechter.kt`, `build.gradle.kts` · `php tools/quelltext/jobregister.php` · `bash tools/quelltext/pruefen.sh sitzungshaertung`, `… kennzeichnung` · `php tools/zaehlung/zaehlen.php` · `python3 tools/screenshots/kontrast.py` · `sha256sum -c` unter `server/vendor/` · `watch/manifest.xml`, `monkey.jungle`, Ressourcenordner · `.github/workflows/*` (`uses:`, `runs-on`, Ausnahmeliste, Schritte) · `server/.htaccess`, `.gitignore` · `php -v`, `python3 -V`, `docker -v`, `nproc`, `free` · `docs/rechtstexte/` per `grep` auf Klartext, Fristen, Fassungen · Nachlesen von F-R17-09, -25, -26, -29, -30 | 19 Dokumente, 33 903 Zeilen; 2 Befunde (F-R17-58 mittel, -59 niedrig); rund 120 Aussagen gemessen, davon rund 50 überholt (F-R17-59) und 4 Texte gegen die Zusage (F-R17-58); Jobregister 11/11 Jobs, 18/18 Schritte; Register 44 Zeilen, 0 über der Decke; Rollenmatrix 118 Zeilen; `ZF_FRISCH_HANDLUNGEN` 8; `WARTUNG_AUSNAHMEN` 18, `JSON_SKRIPTE_AUSSERHALB_API` 4; `RATE_GRENZEN` 23 Töpfe, 8 mit Leiter; `uses:` 15 von 15 an einer SHA, `runs-on` 9 × 26.04 + 1 × 24.04; Ausnahmeliste 11 Zeilen = 8 Pfade; `sitzungshaertung` 0 Befunde; `kennzeichnung` 26 von 26; Vendor 338 Prüfsummen OK; Symbole 58, Schriften 10; Manifest 99 Geräte (8 × `fenix7*`), Ressourcenordner 8 + 3; Android 8 und 0 Berechtigungen; Mailkatalog 28; Gerätemodelle 325; `migrationen_katalog()` 74; `pruefung.yml` 294 Zeilen; Proben 31; `REGELN` 15, `NAMEN` 14; Container PHP 8.3.6, Python 3.13.16, Node 22.22.0, Docker 29.9.1 | 21.10.2026 |
| R17-14 | Lesung Fable (E-R17-14) · Inventar über `git ls-files tools .github` mit Einteilung in Werkzeug, Daten, Fremd, Ausgabe, Binär · Selbstproben und Erzeuger im Container: `tools/quelltext/bestand.py`, `referenzdatensatz/quelldaten/pruefen.py`, `generator/erzeugen.py` + `pruefen.py`, `vergleich/vergleichen.py --testabweichung` (CSV und edbak gegen die eingecheckte Referenz), `browser/download_lib.mjs --selbstprobe`, `proben/kopplung/rundlauf.mjs --selbstprobe`, `proben/huelle/referenz.py --selbstprobe`, `schemaprobe/probe.php --selbstprobe`, `spaltenregister/pruefen.php`, `screenshots/kontrast.py`, `zaehlung/zaehlen.php` · `vulture tools/` · Zähler (`zaehlung-r17-14.py`) über `subprocess`, `shell=True`, `eval`, `proc_open`, `curl -k`, `verify=`, `ignoreHTTPSErrors`, `CERT_NONE`, `secrets.`, `os.environ`/`process.env`/`getenv`, feste Pfade, `npx`, `pip`/`npm install`, `require config.php`, `konfig(`, `register_shutdown_function`, `finally`, `DELETE FROM`, `DROP`, Passwort- und Token-Argumente, Vorgabe-Passwörter, `uses:`-Zeilen · `grep` über `konfig_stellen(`, `vor-probe`, `mailprobe`, `FilesMatch`, `Anlass:`, `sichtpruefung`, `riegelprobe`, `Punktdateien`, `APK_`, `inputs.umgebung`, `needs:` · Nachlesen von F-R17-03, -04, -14 bis -18, -20, Nr. 188, 263, 388, 391 | 277 Dateien, 82 305 Zeilen (67 `.mjs`, 66 `.py`, 52 `.php`, 42 `.md`, 19 `.sh`, 13 `.json`, 6 `.mc`, 4 `.yml`, 3 `.jungle`, 2 `.txt`, 2 `.html`, 1 `.js`), 437 Dateien im Inventar; 3 Befunde (F-R17-55 mittel, -56 und -57 niedrig); `bestand.py` 0 Befunde; Quelldaten 7 262 Einzelprüfungen, 98 Matrixzeilen, 0 offen; Generator 22 Dienste, 106 Einsätze, 68 018 Spurpunkte, 638 Anfragen, Prüfung 339 472 Einzelprüfungen, 0 Befunde; Vergleich CSV 10 von 10 (12 486 Einzelvergleiche, 0 unerklärt), edbak 15 von 15 (347 566, 0); `download_lib` 10/0, `rundlauf` 13/13, `referenz.py` 5/0, Schemaprobe 4/0, Spaltenregister vollständig, Kontrast 36 Paare 0 verfehlt; Register 44 Zeilen, 0 über der Decke (Z17 Ist 0, Decke 30); `vulture` 37 Meldungen (6 mit 100 %); `konfig_stellen(` 8 Proben; `ignoreHTTPSErrors: true` 13 Dateien, `: lokal` 14; `uses:` 15 von 15 an einer SHA; fester Containerpfad 7 Dateien; `require …config.php` 15 Dateien; `DELETE FROM` ohne `WHERE` 7 Dateien; 28 `LIESMICH.md`, 21 mit Anlass-Nummer, 1 Erzeugerform, 6 in Unterordnern ohne | 21.10.2026 |
| R17-13 | Lesung Fable (E-R17-14) · `tools/sandbox/aufbauen.sh android` und `./gradlew --no-daemon lint test` im Container (beide Module, beide Bauarten) · Auswertung von `lint-results-debug.xml` und `build/test-results/*/TEST-*.xml` je Modul · Zähler (`zaehlung-r17-13.py`) über `Log.`, `try`, `rawQuery`/`execSQL`, `SecureRandom`, `Tasks.await`, `HttpURLConnection`, `Cipher.getInstance`, `KeyGenParameterSpec`, `PendingIntent`, `@Volatile`, `System.getenv`, Play-Dienste-Importe, `sourceNodeId` · `grep` über `klammer.beenden`, `BEENDEN`, `addAction`, `uses-permission`, `exported` · Nachlesen von F-R17-03, -27, -31 und AN-5 | 135 Dateien, 23 824 Zeilen (98 Kotlin-Dateien, 18 809 Zeilen), 1 075 Kommentarverweise, 1 Befund (F-R17-54 mittel); Gradle BUILD SUCCESSFUL in 6 min 5 s, 144 Aufgaben; Handy 280 Prüffälle je Bauart (15 übersprungen, 4 Klassen), Uhr 72 (0), 0 Fehlschläge; Lint Handy 0 Fehler, 5 Warnungen (Fassungshinweise), Uhr 0 und 0; Bilderlauf 78 + 7 Bilder; `klammer.beenden` 2 Aufrufer, `BEENDEN`-Aktion an 2 Knöpfen, `System.getenv` 0, Play-Dienste 3 `main`-Dateien, 25 SQL-Aufrufe ohne Interpolation; F-R17-27, -31 bestätigt, AN-5 beantwortet | 20.10.2026 |
| R17-12 | Lesung Fable (E-R17-14) · Uhr-Prüfstand `aufbau` und `reihe` (Stufe I, SDK 9.2.0, alle Manifest-Geräte) · `grep -c WARNING` über die 99 Übersetzungslogs · Zähler über `makeWebRequest`, `Storage.*`, `Properties.*`, `println`, `Cryptography.`, `Math.rand` · Verwenderprobe über alle `function`-Namen · Nachlesen von F-R17-11, -12, -13, -28 am Stand Uhr 4.0.0 | 39 Dateien, 5 650 Zeilen (5 253 Monkey C), 131 Kommentarverweise, 2 Befunde (F-R17-52 mittel, F-R17-53 niedrig); Aufbau: SDK 9.2.0, 1 332 Schriftdateien, 99 von 99 Geräten, 0 fehlende Bibliotheken; Stufe I: 99 übersetzt, 0 fehlgeschlagen, 0 Warnungen in 99 Logs; `makeWebRequest` 6, `println` 0, 306 Funktionen, 2 ohne Verwender; F-R17-11, -12, -13 bestätigt, F-R17-28 zur Hälfte erledigt | 20.10.2026 |
| R17-11 | Lesung Fable (E-R17-14) · `node --check` über alle Skripte · SHA-256 der vendor-Rümpfe gegen den Kopf, `leaflet.css` zusätzlich gegen das npm-Original (`leaflet-1.9.4.tgz`) und `git ls-files --eol` (F-R17-50) · `grep` über Herkunft und SHA in den Köpfen, über `openlocationcode` in `docs/` und über die Verwender (F-R17-51) · `tools/quelltext/vollstaendigkeit.py` (Nr. 341) und `grep` über die sechs Klassen in `server/` · `grep` als Verweiszähler und über `innerHTML`, `insertAdjacentHTML`, `textContent`, `eval`, `new Function`, `document.write`, `fetch(`, `style="`, `localStorage`, `sessionStorage` · Exportprobe (`window.EdX = {…}` gegen `EdX.name` in `server/` und `tools/`) · `<script` und `asset('assets/*.js')` je Seite (Nr. 380) · CSP-Gegenprobe (`style-src-attr` gegen die `style="`-Stellen, `KOPF_KACHELN` gegen die Hosts in `map_layers.js`) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 57 Skripte, 17 783 Zeilen, und `style.css`, 4 814 — 22 597 Zeilen, 929 Verweise; `vendor/`: 5 Textdateien (3 029 Zeilen), 5 PNG; `fonts/`: 10 woff2 · `node --check`: 61 Dateien, 0 Fehler · Prüfsummen: 4 von 5 stimmen, `leaflet.css` LF `337bfca5…` gegen Kopf `a7837102…` = CRLF-Original · 2 Einträge in 2.3a (1 niedrig, 1 Hinweis) · `innerHTML`-Zuweisungen 50 in Skripten, 27 in Seiten; `insertAdjacentHTML` 1 und 3; `textContent` 41; `eval`, `new Function`, `document.write` 0; `fetch(` 7 Aufrufe (1 Zentrale `api.js`, 2 Adressdienst, 4 `same-origin`); `style="` 10 (geo 2, missiontable 2, schneiden 6) = die zehn, die `style-src-attr` deckt; `localStorage` 2, `sessionStorage` 24 · Exporte: 36 Namen (einer ein Wächterwert), 0 ohne Verwender; 15 Einträge ohne Verwender außerhalb ihrer Datei, 1 mit Probe (`EdKontopaar.paarErzeugen`), 14 ohne · Stylesheet: 10 `url()`, alle `fonts/`; 0 `@import`, 0 fremde Hosts; `vollstaendigkeit.py`: 13 Regeln ohne Markup (7 mit Grund, 6 ohne — Nr. 341 bestätigt, alle sechs nur in Kommentaren genannt), 0 Hexwerte und 0 Schriftgrößen außerhalb, 3 Pixelwerte unter Eigenschaftsausnahme (34, 56, 14 px) · Skripte je Seite (`asset()`): `einstellungen.php` 20, `index.php` 19, `einsatz_form.php` 13, `einsatz.php` 9, `import.php` 9, `zeitraum.php` 6, `tag_spuren.php` 6, `suche.php` 5, dazu 7 aus `ui_geruest_ende()` · CSP: 4 Kachelhosts in `map_layers.js` = 4 in `KOPF_KACHELN` · Steuerung: siehe Commit-Nachricht von R17-11 | 20.10.2026 |
| R17-10 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `grep -A4` über alle Aufrufer von `ui_meldung_markup()` gegen `<strong`, `<code`, `<em`, `<a `, `<br` (F-R17-49) · `grep` über `'unter' =>` an `ui_titelzeile()` (Ausgabe ohne Maskierung) · `grep` über `FilesMatch` in `.htaccess` gegen die Schreibziele `__DIR__ . '/…'` in `wartung_lib.php` und über die Aufrufer von `wartung_einschalten()` (F-R17-47) · `grep` über `AUTH LOGIN`, `$cfg['user']` und „ohne Authentifizierung" in `smtp.php`, über die Betreffe in `mail_katalog()` (F-R17-46) · `grep` über `ueberlast_erkannt()`, `UEBERLAST_CODES` und das Muster der Verbindungsprobe (F-R17-48) · `ls api/` gegen `WARTUNG_AUSNAHMEN` · `grep` über die Aufrufer von `mail_konto_entfernen()` und `edbak_ablage_bereit()` (F-R17-21, -19) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 48 Dateien, 38 118 Zeilen (43 PHP, 4 SQL, `.htaccess`); 16 HTTP-Einstiege (11 Seiten, 4 Rechtstext-Hüllen, 1 `api/`), dazu `doku_seite.php` hinter `hilfe.php` und `ueber.php`; 4 Einträge in 2.3a (3 niedrig, 1 Hinweis); F-R17-19, -21 bestätigt, F-R17-05 unverändert, Nr. 77 gelesen · PHPStan 66 Meldungen im Stück: 20 `variable.undefined` (Rauschen aus `auth_guard.php`), 25 `class.notFound`/`class.noParent`/`method.notFound` (17 phpseclib, 8 Parsedown — beide unter `vendor/`, Rauschen), 21 gelesen: 0 Befunde, 3 veraltete Annotationen, 9 Absicht (der KDF-Übergangsblock in `status_lib.php`, `$pdo !== null` als Lesehilfe in `install.php`, immer wahre Weichen), 1 Fehlalarm (`inTransaction()` nach DDL, `transaktion_lib.php`), 8 kosmetisch · 412 Funktionen der 27 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 3 ohne Nennung (`protokoll_frist_verwaltung_setzen()`, `protokoll_fehler_quittieren()`, `doku_bestand()`), 61 nur in der eigenen Datei (5 davon JavaScript in `index.php`) · `ui_meldung_markup()`: 2 Aufrufer mit Tags im Text, 1 `ui_e($text)` · `ui_titelzeile()`: 18 `'unter' =>`, 10 mit Laufzeitwert, 10 maskiert oder aus Datumswerten · `FilesMatch`: 7 Namen, 2 Laufzeitdateien fehlen; `wartung_einschalten()`: 1 Aufrufer, Name oder Adresse · `smtp.php`: 2 Sätze „ohne Authentifizierung", 0 Bedingung am `AUTH LOGIN`; `mail_katalog()`: 0 Betreffe mit Nutzertext · `ueberlast_erkannt()`: Liste 3 Codes, Muster 2, Probenmuster 3 · `api/`: 26 Dateinamen, 0 in `WARTUNG_AUSNAHMEN` · `mail_konto_entfernen()`: 0 Aufrufer; `edbak_ablage_bereit()`: 5 Dateien, `wiederherstellen.php` 0 · 2 494 Kommentarverweise in 48 Dateien · Steuerung: siehe Commit-Nachricht von R17-10 | 20.10.2026 |
| R17-09 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `php -r` (Undefined array key beim Monat 13, F-R17-44) · `grep` über `ZF_FRISCH_HANDLUNGEN` gegen die `zf_`-Zweige von `einstellungen.php` (F-R17-43), über `DEMO_GESPERRT` gegen die Handlungen von `admin_user.php` (F-R17-42), über `fputcsv` und `_geraet_kuerzen()` (F-R17-45), über `api/range.php` (Monatsprüfung) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 31 Dateien, 18 322 Zeilen (27 PHP-Dateien im Kern, 2 `api/`-Einstiege, 2 Skripte); 17 HTTP-Einstiege (15 Seiten, 2 `api/`), dazu `rechtstext_seite.php` hinter vier Hüllen; 4 Einträge in 2.3a (3 niedrig, 1 Hinweis); F-R17-07, -08 bestätigt, Nr. 371, 381 bestätigt, F-R17-02, -06 unverändert · PHPStan 201 Meldungen im Stück: 181 `variable.undefined` (Rauschen aus `auth_guard.php`), 0 `class.notFound`, 20 gelesen: 0 Befunde, 7 veraltete Rückgabeformen (`totp_zustand()` liefert `geraete`, `statistik_zeitraum()` `bis_utc`, `edbak_auftrag_schub()` `meldungen`), 10 Absicht (leere Store-Adressen, immer wahre Weichen), 2 Fehlalarme (Schließung mit `&$absatz`), 1 toter Zweig (7.3.5) · 140 Funktionen der 15 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 0 ohne Nennung (18 nur in der eigenen Datei, Seitenhelfer; 27 davon JavaScript in `suche.php`, `zeitraum.php`, `einstellungen.php`) · 1 012 Kommentarverweise in 31 Dateien · `ZF_FRISCH_HANDLUNGEN`: 8 Handlungen, `zf_codes_neu` fehlt · `DEMO_GESPERRT`: 7 von 10 Schreibwegen · `fputcsv`: 1 Schreiber, 0 Formelschutz · Steuerung: siehe Commit-Nachricht von R17-09 | 20.10.2026 |
| R17-08 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `php -r` (Warnung beim `use` einer undefinierten Variablen, F-R17-38) · `git log -S` (seit wann `n_gespeichert` fehlt, F-R17-39) · `grep` gegen `tools/proben/gpx/probe.mjs`, `demo_lib.php` und `assets/wartungslauf.js` (misst die Probe eine gepackte Spur? wer stößt den Wartungslauf an?) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 36 Dateien, 20 019 Zeilen (22 PHP-Dateien im Kern, 8 `api/`-Einstiege, 6 Skripte); 20 HTTP-Einstiege (12 Seiten, 8 `api/`); 4 Einträge in 2.3a (1 mittel, 2 niedrig, 1 Hinweis); 3 Altbefunde nachgelesen, 3 bestehen (soweit das Stück sie sieht) · PHPStan 101 Meldungen im Stück: 87 `variable.undefined` (`$userId`, `$patReady`, `$patWrapPw` aus `auth_guard.php`, Rauschen), 0 `class.notFound`, 14 gelesen: 5 sind zwei Befunde (F-R17-38 vier Meldungen, F-R17-39 eine), 9 Absicht oder harmlos (7.3.4) · 193 Funktionen der 14 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 0 ohne Nennung · 1 242 Kommentarverweise in 36 Dateien · Probe `gpx`: 1 Abruf über `[data-gpx-laden]`, an einer frischen Spur der Stufe 1 (`demo_lib.php` nennt keine Stufe); `wartungslauf.js` ruft `api/wartungslauf.php` aus `index.php` · Steuerung: siehe Commit-Nachricht von R17-08 | 20.10.2026 |
| R17-07 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `grep` gegen `tools/proben/raten/probe.php` und `docs/Technik.md` 5e.2 (F-R17-36) · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 31 Dateien, 11 238 Zeilen, dazu `db.php` Zeilen 480 bis 830 (JSON-Hülle, Eingang der Endpunkte) und `wegwerfdomains.txt` nur auf Form (8 883 Zeilen); 2 Einträge in 2.3a (1 mittel, 1 niedrig), 1 Altbefund verschärft; 3 Altbefunde nachgelesen, 3 bestehen · PHPStan 31 Meldungen im Stück: 22 `variable.undefined` (`$row`, `$userId`, `$userRole`, `$einwilligungOffen` aus `auth_guard.php` in `zweitfaktor.php` 16 und `einwilligung.php` 6, Rauschen), 0 `class.notFound`, 9 gelesen: 0 Befunde, 1 Fehlalarm (`jobs.php`, Closure), 8 Absichtsvergleiche (7.3.3) · 169 Funktionen der 15 PHP-Dateien mit Definitionen gegen `server/`, `tools/`, `docs/`: 2 ohne Nennung (`sitzung_bindung_loeschen()`, `adresse_vormerkung_loeschen()`) · 608 Kommentarverweise in 31 Dateien · Probe `raten`: `login_ip` an 4 Stellen, alle isoliert, 0 über den Pfad der Anmeldung · Steuerung: siehe Commit-Nachricht von R17-07 | 20.10.2026 |
| R17-06 | Lesung Fable (E-R17-14) · PHPStan 2.3.1, Stufe 4, örtlich (E-R17-16) · `grep` als Verweiszähler und Aufruferprobe · `git log -G`/`-S` · `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` · Prüfstand `klein` | 38 Dateien, 20 053 Zeilen gelesen; 4 Einträge in 2.3a (1 mittel, 2 niedrig, 1 Hinweis); 7 Altbefunde nachgelesen, 7 bestätigt · PHPStan über `server/` (ohne `vendor/`, `assets/`, `demo/`, `doku/`): 506 Meldungen, davon 88 in Stück 2 — 29 `variable.undefined` (`$userId` aus `auth_guard.php`, Rauschen), 44 `class.notFound` (phpseclib und ConstantTime liegen unter `vendor/`, ausgeschlossen), 15 gelesen: 1 Befund (F-R17-33), 6 tote Zweige (7.3.2), 8 Absichtsvergleiche · 334 PHP-Funktionen des Stücks gegen `server/`, `tools/`, `docs/` gezählt: 1 ohne Nennung (`serverschluessel_zeile()`); `crypto.js`, `keyguard.js`, `pwquality.js`, `kontopaar.js`, `schluessel.js`: 0 Methoden ohne Verwender · 987 Kommentarverweise in 38 Dateien (Zahl je Datei in 7.3.2) · Steuerung: 21 Decken, 0 gerissen · 0 neue Nummern · 0 neue Treffer · 150/0 · Prüfstand `klein`: Bericht in der Commit-Nachricht von R17-06 | 20.10.2026 |
| R17-05 | `decken.py` · `uebersicht.py --ziel 12b` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 21 Decken, 0 gerissen (die Decke `backlog-kopf` riss zwischendurch mit 61 Zeilen — behoben durch Streichen der gemergten Spannen 12a und 19; `fahrplan-vokabular` riss mit „läuft" — Statuswort auf „Umsetzung") · 69 offene Einträge, 0 ohne Grammatik, 0 ohne Ziel, 10 zu Ziel 12b · 0 neue Nummern, 15 Zweige verglichen, 0 Überschneidungen · 0 neue Treffer, 0 durchgerutschte Fallen · 0 Befunde · 150/0 · Prüfstand `klein`: Bericht in der Commit-Nachricht von R17-05 | 20.10.2026 |
| R17-04 | `decken.py` · `uebersicht.py --ziel 12` und `--ziel 12b` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · `grep` auf den gelöschten Dateinamen · Prüfstand `klein` | 21/0 · Ziel 12: 0, Ziel 12b: 7 · 2 neue Nummern, 0 Überschneidungen · 0 neue Treffer · 0 Befunde · 150/0 · 7 Nennungen der gelöschten Datei, alle hier als Fundstelle oder Geschichte · Prüfstand 23 grün (Baum `ea29dca…` = `30d7226`); der Abschluss trägt seinen Bericht in der eigenen Commit-Nachricht | 11.10.2026 |
| R17-03 | `decken.py` · `uebersicht.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 21/0 · 62/0/0 · 0 · 0 neue Treffer · 0 Befunde · 150/0 · Prüfstand: Commit-Nachricht von R17-03 | 11.10.2026 |
| R17-02 | `textprobe.py --bereich c` (mit dem neuen Dokument) · `textprobe.py --selbstprobe` · `decken.py` · `bestand.py` · `linkprobe.py` · `pysyntax.py` · Prüfstand `klein` | Textprobe: erst 52 neue Treffer (43 `luft`: Garmin, Connect IQ, Garmin Connect, Spur; 9 `hausform`: Betreiberin), nach Hausform-Korrektur und zwei begründeten Ausnahmen **0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen**; Selbstprobe 4/4 · 21 Decken, 0 gerissen · 0 Befunde · 150 Verweise, 0 Abweichungen · 63 Werkzeuge, 0 Fehler · Prüfstand: Commit-Nachricht von R17-02 | 11.10.2026 |
| R17-01 | drei Leser (Opus, lesend) · Nachprüfung Fable am Quelltext · `gh api` lesend (Rulesets, `rules/branches/produktion`) · `decken.py` · `uebersicht.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 55 Bausteine, 31 Befunde (5 hoch, 15 mittel, 8 niedrig, 2 Hinweise); **11 Befunde von Fable am Quelltext nachgeprüft** (F-R17-01 bis -06, -11, -14, -15, -19, -21), 20 als Lesung gekennzeichnet; `rules/branches/produktion` = `[]`; Werkzeuge: siehe Prüfdokument 2; Prüfstand: Commit-Nachricht von R17-01 | 11.10.2026 |
| R17-00 | `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | 21 Decken, 0 gerissen (die Verlaufszeile riss einmal mit 333 Zeichen, gekürzt auf 283) · 62 Einträge, 0 ohne Grammatik, 0 ohne Ziel, 2 zu Ziel 12 · 0 neue Nummern, 0 Überschneidungen gegen 8 Zweige · 0 neue Treffer · 0 Befunde · 150 Verweise, 0 Abweichungen. Prüfstand `klein`: siehe Commit-Nachricht von R17-00 | 11.10.2026 |

## 6. Quellen

- `docs/Rahmenplan.md` 3 (Schritt 12, 12a, 12b; „Warum so"), 7 (R17, R69,
  R78, R86).
- `docs/konzepte/Konzept-Planung-v1.0.md` 4e (Befund, Optionen, E-PV-5).
- Der Krypto-Review vom 05.09.2026 (`docs/konzepte/Review-Krypto-Sicherheit.md`
  bis `bc08eda`; **gelöscht mit R17-04**, E-R17-13; Stand je Befund in 3.4).
- `docs/konzepte/Vorbereitung-Sicherheitspaket.md` (SP-3b, SP-7, SP-9).
- `docs/konzepte/Konzept-V1-Ortsdaten.md` (Vorstudie Weg B, Fragen in 6
  beantwortet).
- `docs/Backlog.md` Nr. 146, Nr. 263, Nr. 43 (Weg B, gehört zu 12a);
  `Backlog-Erledigt.md` Nr. 109 (Notizen ins Blob) und Nr. 138 (Weg C).
- `docs/Technik.md` 4.97 bis 4.99q; `CLAUDE.md` 3 und 4.

## 7. Schritt 12b — Stücke 2 bis 12: Review und Bereinigung

### 7.1 Auftrag

**Ziel (Rahmenplan 3, Schritt 12b):** sauberer Code, Verhalten unverändert
außer bei Funden. Der Review liest den Stand **nach S11** — Web 23.0.2,
Uhr 4.0.0, Android 0.18.0 — in den Stücken 2 bis 12 aus E-PV-5 (2), damit
der Code **genau einmal und in seiner Endfassung** gelesen wird (R86); die
Krypto auf Uhr und Handy, SPUR2, die Spurfunktionen im Browser und die
Nutzlastfassung der Sicherung sind deshalb mit im Umfang. Gesucht werden
**Bugs, Sicherheitslücken, ungebrauchter Code, Karteileichen und Probleme
jeder Art** (R69); gelesen wird gegen das Bedrohungsmodell
(`docs/Bedrohungsmodell.md`: Schutzziele, Lagen A bis P, Zusagen mit
Grenze). Dazu der **Kommentardurchgang** (R13, R31): Kommentare erklären das
Warum in Worten; Beschlussnummern (R…, E-…), Backlog-Nummern, Fassungen
(„seit Web 9.7.0"), Konzept- und Paketnamen verschwinden aus dem Code — wo
ein Grund ohne den Verweis unverständlich würde, wird er ausgeschrieben, wo
er nur Geschichte war, entfällt er. Die Rückverfolgung leisten Changelog
und Rahmenplan-Archiv.

**Der Review behebt nichts** (K4, E-PV-5 (6)) — auch nicht „eine Zeile".
Jeder Fund wird Befund (2.3a) oder Aufräumposten (7.3) und geht in die
**Freigaberunde** (R17-17): Die Betreiberin entscheidet je Fund; dann
Sofortpaket für Kritisches, Pflicht- und Aufräumpakete für den Rest.
**v1.0 wird nicht erklärt, solange ein Fund offen ist.** Kryptographie-
Änderungen sind Fable-Schritte, alles andere Opus (K8).

**Abnahme (Rahmenplan 3):** Review vollständig (elf Stücke, jede Datei der
Listen in 7.3 gelesen), Pakete abgenommen, Wortliste 0/0/0.

**Nicht Ziel:** Umbenennung, Doku-Neufassung, Manifest, Changelog neu —
das ist P7 (Schritt 13). Nicht Ziel ist auch eine zweite Bestandsaufnahme
gegen die Doku: Die 31 Befunde aus 2.3 bleiben stehen und werden in ihrem
Stück **in Endfassung nachgelesen** (steht ein Befund nach S11 anders, sagt
es die Zeile in 2.3a mit Verweis), nicht neu erhoben.

### 7.2 Lesweise je Stück

Jedes Stück ist ein Paket (R17-06 bis -16) und liefert vier Dinge:

1. **Die Dateiliste, abgehakt.** 7.3 nennt je Stück die Dateien; eine
   Datei, die nicht gelesen wurde, steht dort als nicht gelesen, mit Grund
   (Prüfdokument 1). Fable liest selbst (E-R17-14); ein Unter-Agent darf
   Aufrufer suchen, Verweise zählen, ungenutzte Regeln listen — lesend,
   ohne Nebenwirkung —, und das Ergebnis steht mit Mittel und Zahl im
   Prüfprotokoll (5). Vorsortierung örtlich (E-R17-16), was der Container
   kann; was er nicht kann, ist ein Befund mit Zahl im Prüfdokument 1.
2. **Befunde** in 2.3a — mit Grad, Stück, Fundstelle als Funktionsname,
   Wirkung gegen das Modell (welche Lage, welche Zusage), Empfehlung und
   geschätztem Aufwand in der Empfehlung.
3. **Aufräumposten** in 7.3 je Stück: Karteileichen (Funktionen, Felder,
   Regeln, Dateien ohne Verwender — mit der Gegenprobe, **wer** sie nicht
   mehr ruft; bei Antwortverträgen, Export und Backup gilt Nr. 21: „niemand
   liest es" reicht dort nicht), ungebrauchter Code, und die Fundstellen
   des Kommentardurchgangs **als Zahl je Datei**, nicht als Liste jeder
   Zeile — die Liste erzeugt das Aufräumpaket selbst.
4. **Prüfprotokoll** (5) und Prüfdokument (2) mit Mittel und Zahl;
   Statusblock, Commit, Push (K7).

**Die Checkliste für jeden HTTP-Einstieg** (E-PV-5, U3) — Seiten im
Wurzelverzeichnis von `server/` und alles unter `api/`:

| Punkt | Frage |
|---|---|
| Rolle | Wer darf hierher? `auth_guard`-Aufruf, Rollenprüfung, Besitzprüfung je Datensatz (IDOR) |
| CSRF | Jeder schreibende Weg hinter `csrf_check()` oder `EdApi`; kein GET, das schreibt |
| Eingabe | Jede Einsatzangabe über `validate_lib.php`; Form und Größe verschlossener Werte; Typen, Längen, Listen |
| Ausgabe | Jede Ausgabe kodiert (`htmlspecialchars`, `JSON_HEX_*`, im Browser kein `innerHTML` mit Fremdtext) |
| Fehlerweg | Was verrät eine Ausnahme, ein 500, ein Timing? Antwortgleichheit, wo das Modell sie verlangt (Anmeldung, Reset, Registrierung) |
| Rate | Ratenschutz, wo geraten werden kann |
| Datenbank | nur gebundene Parameter; `mf_spalten()` statt Spaltenliste; Transaktion über `db_transaktion()` |
| Eine Stelle | die elf Wege aus `CLAUDE.md` 4 (Register `tools/zaehlung/`) — kein zweiter Eingang |

Für Uhr und Android tritt an ihre Stelle die Modulliste aus E-PV-5 (U3):
Geheimnis- und Schlüsselablage, Puffer, Sendeweg, Kopplung,
Vordergrunddienst, Nachrichtenweg — vollständig; der Rest nach der
Checkliste sinngemäß (Eingabe, Fehlerweg, Verwender).

### 7.3 Die Stücke — Umfang, zugehörige Posten, Stand

Die Dateilisten sind der Stand bei `f17380c` (20.10.2026); eine Datei, die
während 12b dazukommt, wird dem Stück nachgetragen. Die Zuordnung folgt
E-PV-5 (2), ergänzt um das, was seit dem 03.09.2026 entstanden ist (P5,
S10, 18, S11). **Eine Datei gehört zu genau einem Stück**; wer sie in
einem anderen braucht, liest sie dort nach, trägt sie aber nicht doppelt.
Die Spalte „Posten" nennt, was das Stück außer der Lesung mitbringt: offene
Befunde aus 2.3 zum Nachlesen in Endfassung, offene K- und AN-Posten aus
3.4, Backlog-Punkte mit Ziel 12b.

| Stück | Paket | Umfang | Posten | Stand |
|---|---|---|---|---|
| 2 Kryptographie und Schlüssel | R17-06 | `serverkrypto_lib.php`, `auth_salt.php`, `pw_handling.php`, `komplett_lib.php`, `backup_lib.php`, `adminbackup_lib.php`, `wiederherstellen.php`, `demo_lib.php`, `geraetehuelle_lib.php`, `kontopaar_lib.php`, `schluesselwechsel_lib.php`, `schluessel_teile.php`, `totp_lib.php`, `passkey_lib.php`, `rueckweg_lib.php`, `betrieb_schluesselblatt.php`, `codeblatt.php`, `notfallblatt.php`, `zweitfaktor_teile.php`; `api/kdf_upgrade.php`, `api/pat_anheben.php`, `api/schluessel_erneuern.php`, `api/adminbackup_freigabe.php`, `api/backup_*.php`; Browser: `crypto.js`, `keyguard.js`, `pwquality.js` und die Skripte der Gerätehülle und des Schlüsselpaars | F-R17-01, -02, -06, -10, -19, -24, -26; K-3 (Mindestlänge in `pwquality.js`); Nr. 370 (`CryptoKey`) als Entwurfsfrage für den Paketschnitt | **gelesen** 20.10.2026 (R17-06; Ergebnis in 7.3.2) |
| 3 Zugang und Sitzung | R17-07 | `login.php`, `logout.php`, `session_lib.php`, `sitzung_lib.php`, `auth_guard.php`, `ratelimit_lib.php`, `reset_request.php`, `pair.php`, `jobs.php`, `zweitfaktor.php`, `zweitfaktor_notweg.php`, `registrieren.php`, `bestaetigen.php`, `adresse_bestaetigen.php`, `einwilligung.php`, `einwilligung_lib.php`, `konto_lib.php`, `einstieg_lib.php`, `kopplung_lib.php`, `kopfzeilen_lib.php`, `netz_lib.php`, `wegwerfdomains.txt`, die CSRF-Prüfung in `db.php`; die zugehörigen `api/`-Einstiege und Browser-Skripte | F-R17-22, -23, -31 (Anteil an jeden Link); K-7 (Adresswechsel), K-15 (`csrf_check`); P-R17-04 bleibt bei der Betreiberin | **gelesen** 20.10.2026 (R17-07; Ergebnis in 7.3.3) |
| 4 Einsatz, Diensttag, Spur, Ingest | R17-08 | `ingest.php`, `spur_lib.php`, `tag_spuren.php`, `diensttag_*.php`, `tageszuordnung_lib.php`, `einsatz.php`, `einsatz_form.php`, `einsatz_lib.php`, `einsatz_loeschen.php`, `einsatz_verschieben.php`, `nachbearbeitung.php`, `nachbearbeitung_lib.php`, `import.php`, `validate_lib.php`, `mission_fields.php`, `mission_fields_lib.php`, `nachweis_lib.php`, `geraete_lib.php`; die zugehörigen `api/`-Einstiege (Spur, Phasen, Reanimation, Export) und Browser-Skripte (Spurfunktionen, Karte, Formular) | F-R17-09 (Photon, Kacheln), -13, -28 (Fristen, soweit Server); K-10 (DOCTYPE/UTF-16 im GPX-Import); Nr. 23 nur lesen (gehört zu 13) | **gelesen** 20.10.2026 (R17-08; Ergebnis in 7.3.4) |
| 5 Verwaltung und Admin | R17-09 | `einstellungen.php`, `konten_einstellungen_lib.php`, `admin_users.php`, `admin_user.php`, `admin_demo.php`, `admin_installation.php`, `admin_komplettsicherung.php`, `admin_protokoll.php`, `admin_rechtstexte.php`, `admin_sicherungen.php`, `admin_sicherungsziele.php`, `stammdaten_ui.php`, `rechtstexte_lib.php`, `rechtstext_seite.php`, `papierkorb.php`, `trash_lib.php`, `suche.php`, `zeitraum.php`, `apk.php`, `apk_lib.php`, `geraetemodelle.php`, `geraetemodelle_lib.php`, `statistik_lib.php`, `betrieb_statistik.php`, `erststart_karte.php`, `blatt_dialog.php`, `rueckfrage_dialog.php`; zugehörige `api/` und Skripte | F-R17-07, -08; Nr. 371 (Admin-Riegel), Nr. 381 (Suche und Zeitraum) | **gelesen** 20.10.2026 (R17-09; Ergebnis in 7.3.5) |
| 6 Betrieb | R17-10 | `update.php`, `migration_lib.php`, `migrations/`, `schema.sql`, `wartung_lib.php`, `jobs_lib.php`, `sicherungsziel_lib.php`, `speicher_lib.php`, `smtp.php`, `email_lib.php`, `mail_lib.php`, `install.php`, `db.php` (außer CSRF), `transaktion_lib.php`, `konfig_lib.php`, `plattform_lib.php`, `php_mindest.php`, `status_lib.php`, `systemmeldung_lib.php`, `umgebung_lib.php`, `instanz_lib.php`, `protokoll_lib.php`, `protokoll_archiv_lib.php`, `ankuendigung.php`, `ankuendigung_lib.php`, `betrieb_*.php` (außer Statistik und Schlüsselblatt), `ui.php`, `format_lib.php`, `zip_lib.php`, `doku_lib.php`, `doku_seite.php`, `hilfe.php`, `ueber.php`, `index.php`, `version.php`, `config.example.php`, `.htaccess`, `api/health.php`; die Rechtstext-Seiten (`impressum.php`, `datenschutz.php`, `avv.php`, `nutzungsbedingungen.php`) | F-R17-05 (Stand bei der Betreiberin), -19, -21; Nr. 77 (`update.php` in Unterseiten) | **gelesen** 20.10.2026 (R17-10; Ergebnis in 7.3.6) |
| 7 Browser-Skripte und Stylesheet | R17-11 | alle 57 Dateien unter `server/assets/` (`*.js`), `style.css`, die Köpfe unter `assets/vendor/` und `assets/fonts/` (Herkunft, SHA-256); der `innerHTML`-Durchgang; was in den Stücken 2 bis 5 schon gelesen ist, wird hier nur auf Verwender und tote Zweige gelesen | F-R17-24 (`edk`, `edkvor`); Nr. 341 (sechs Regeln, Fokus-Zweig), 380 (Seitenaufbau), 382 (erste Antwort) | **gelesen** 20.10.2026 (R17-11; Ergebnis in 7.3.7) |
| 8 Uhr | R17-12 | `watch/source/*.mc` (20 Dateien), `watch/resources/` (Einstellungen, Strings, Manifest), `watch/monkey.jungle`; Modulliste: Geheimnis und Gerätehülle, Puffer samt Reanimation, Uploader, Kopplung, Anzeige | F-R17-11 (`http://`), -12 (Begleit-App, Einstellungen), -13, -28 (Frist); AN-2 für die Garmin | **gelesen** 20.10.2026 (R17-12; Ergebnis in 7.3.8) |
| 9 Android | R17-13 | `android/` beide Module (98 Kotlin-Dateien), `build.gradle.kts`, `libs.versions.toml`, Manifeste, `netzsicherheit.xml`, `netzwerk_pruefstand.xml`, Ressourcen, die Prüffälle; Modulliste: Keystore, Nachrichtenweg und Horcher, SQLite-Puffer, Vordergrunddienst, Kopplung, Sendeweg | F-R17-03 (Gradle und `APK_*`), -27 (drei Dateien mit Play-Diensten), -31 (Wear ohne Absenderprüfung, 400/403); AN-5 (R8, `isMinifyEnabled`) | **gelesen** 20.10.2026 (R17-13; Ergebnis in 7.3.9) |
| 10 Werkzeuge und Workflows | R17-14 | `tools/` (22 Ordner, 433 Dateien) und `.github/workflows/` (vier Dateien); je Werkzeug: prüft es noch, was der Code tut? Anlass-Zeile, Anleitung nach `Pruefablauf.md` 6.2; die Kette gegen das Modell (Lagen C, K) | F-R17-04, -14, -15, -16, -17, -18, -20; Nr. 188 (Dokumentenprobe), Nr. 263 (Wache, E-R17-12) | **gelesen** 21.10.2026 (R17-14; Ergebnis in 7.3.10) |
| 11 Doku gegen Code | R17-15 | `README.md`, `CLAUDE.md`, `docs/Technik.md`, `Handbuch.md`, `JSON-Vertrag.md`, `Export-Format.md`, `Backup-Format.md`, `Geraete-Eingabe.md`, `Design.md`, `Lizenzen.md`, `Bedrohungsmodell.md`, `Pruefablauf.md`, `Sandbox-Setup.md`, `android/LIESMICH.md`, die `LIESMICH.md` der Werkzeuge (Zahlen gegen `Pruefablauf.md` 6.11), `docs/rechtstexte/` nur auf technische Aussagen | F-R17-09, -25, -26, -29 (Sammelbefund veralteter Stellen), -30 | **gelesen** 21.10.2026 (R17-15; Ergebnis in 7.3.11) |
| 12 Zusammenführung | R17-16 | kein Code: Fundliste sortiert (2.3 und 2.3a), Sofortpaket ausgewiesen, Aufräumposten je Codebasis gebündelt, Kommentardurchgang als Zahl je Codebasis, Vorschlag für den Paketschnitt mit Fable-Markierung, R5-Ausnahmeliste als Zuarbeit für P7 | Nr. 21 (die A4-Kandidaten entstehen aus den Karteileichen-Listen neu) | **zusammengeführt** 21.10.2026 (R17-16; Ergebnis in 7.3.12) |

Die Unterabschnitte **7.3.2 bis 7.3.12** entstehen mit dem jeweiligen Paket
und halten je Stück: die abgehakte Dateiliste, die Aufräumposten
(Karteileichen mit Gegenprobe, ungebrauchter Code), die Kommentarfundstellen
als Zahl je Datei und die Ergebnisse der Vorsortierung.

#### 7.3.2 Stück 2 — Kryptographie und Schlüssel (R17-06, 20.10.2026)

**Dateiliste, abgehakt — 38 Dateien, 20 053 Zeilen, jede von Fable
vollständig gelesen** (E-R17-14; kein Unter-Agent, die Zähler unten sind
`grep`). Drei Dateien, die 7.3 dem Stück zuschlug, lesen sich im
Zusammenhang des Zugangs und gehen nach **Stück 3**:
`api/rueckweg_anlegen.php`, `api/passkey_anlegen.php`,
`api/schluesselblatt_pruefen.php` (dort als Einstiege des Zweitfaktors).
Die Bibliotheken dahinter (`rueckweg_lib.php`, `passkey_lib.php`,
`totp_lib.php`) sind hier gelesen. Die Spalte rechts ist die Zahl der
Fundstellen des Kommentardurchgangs (Muster: Paketkürzel, E-/F-/R-Nummern,
„bis/seit Web N", „Nr. NNN"; Orientierung, kein Prüfwert — das Muster
trifft auch Fachbegriffe, die so aussehen).

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `serverkrypto_lib.php` | 1 569 | 100 | `api/kdf_upgrade.php` | 240 | 10 |
| `auth_salt.php` | 127 | 5 | `api/pat_anheben.php` | 152 | 3 |
| `pw_handling.php` | 709 | 42 | `api/schluessel_erneuern.php` | 138 | 4 |
| `komplett_lib.php` | 1 744 | 45 | `api/kontopaar.php` | 145 | 5 |
| `backup_lib.php` | 2 563 | 140 | `api/adminbackup_freigabe.php` | 130 | 10 |
| `adminbackup_lib.php` | 2 587 | 109 | `api/backup_data.php` | 80 | 5 |
| `wiederherstellen.php` | 780 | 21 | `api/backup_eintraege_restore.php` | 71 | 4 |
| `demo_lib.php` | 822 | 45 | `api/backup_restore.php` | 131 | 13 |
| `geraetehuelle_lib.php` | 205 | 12 | `api/backup_spuren.php` | 90 | 5 |
| `kontopaar_lib.php` | 156 | 4 | `api/backup_spuren_restore.php` | 89 | 5 |
| `schluesselwechsel_lib.php` | 1 320 | 90 | `assets/crypto.js` | 1 035 | 37 |
| `schluessel_teile.php` | 101 | 4 | `assets/keyguard.js` | 161 | 5 |
| `totp_lib.php` | 647 | 45 | `assets/unlock.js` | 579 | 26 |
| `passkey_lib.php` | 992 | 83 | `assets/pwquality.js` | 682 | 7 |
| `rueckweg_lib.php` | 391 | 23 | `assets/schluessel.js` | 185 | 5 |
| `betrieb_schluesselblatt.php` | 220 | 23 | `assets/kontopaar.js` | 197 | 6 |
| `codeblatt.php` | 128 | 6 | `assets/schluesselblatt.js` | 249 | 4 |
| `notfallblatt.php` | 213 | 14 | `assets/rueckweg.js` | 161 | 10 |
| `zweitfaktor_teile.php` | 91 | 1 | `assets/passkey.js` | 173 | 11 |

**Summe 987 Verweise.** Die fünf Dateien mit den meisten tragen 522 davon
(`backup_lib`, `adminbackup_lib`, `serverkrypto_lib`,
`schluesselwechsel_lib`, `passkey_lib`) — dort ist der Kopfkommentar
Geschichte des Pakets, nicht Beschreibung des Codes, und dort beginnt das
Aufräumpaket.

**Was die Lesung ergeben hat, in einem Absatz.** Die Kryptographie selbst
hält, was das Modell sagt: Die Hüllen (`edk1:`, `edka1:`, `edsk1:`,
Gerätehülle Fassung 1, EDKOMP1, Konto-Backup Fassung 4) tragen Zweck oder
Bindung in den Zusatzdaten, der Wiederherstellungsschlüssel bleibt vom
Anteil fern (`WRAP_RC_RE`, `RW_PRIVAT_RE`, geprüft an jeder Schreibstelle
einschließlich der Demo-Fixture), `pat_blob` und Stücke gehen auf keinem
Weg im Klartext über den Server, und jeder Schreibweg der Backups prüft
Eigentum je Kennung gegen `user_id`. Die vier Einträge in 2.3a sind
Ränder: ein fehlender Ratenschutz an einem von drei Geschwistern
(F-R17-32), eine Prüfung, die eine Umbenennung abgeschaltet hat
(F-R17-33), eine Lücke in der Prüfschicht des Rückwegs (F-R17-34), ein
Rückfall je Teil statt je Paket (F-R17-35). Nichts davon ist kritisch,
nichts ein Sofortpaket; F-R17-32 gehört in das Paket zu F-R17-01 (b).

**Die sieben Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen —
alle bestehen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-01 (hoch) | unverändert: `password_hash($neuToken, …)` in `api/kdf_upgrade.php` und `pw_handling.php`, Token ohne Anteil |
| F-R17-02 (hoch) | unverändert: `edbak_freigeben()` ruft weder `protokoll()` noch eine Mail; `freigeben` steht nicht in `ZF_FRISCH_HANDLUNGEN` (acht Handlungen gezählt) |
| F-R17-06 (mittel) | unverändert: Reset-Zweig in `pw_handling.php` prüft allein `key_check` gegen `pat_key_check` |
| F-R17-10 (mittel) | unverändert: `api/schluessel_erneuern.php` schreibt `protokoll('verwaltung', 'schluessel_erneuert', …)`, keine Mail |
| F-R17-19 (mittel) | unverändert: `edbak_ablage_bereit()` wird in `wiederherstellen.php` nicht gerufen (0 Treffer) |
| F-R17-24 (niedrig) | bestätigt, mit einer Präzisierung: `edkvor` wird von `unlock.js` beim ersten Schlüsselbedarf, beim Abmelden und bei jedem Anmeldeversuch geräumt (`vergissAbleitungen()`), liegt also einen Seitenwechsel lang; `edk` bleibt, wie beschrieben |
| F-R17-26 (niedrig) | unverändert: der Kopf von `serverkrypto_lib.php` nennt die Protokollarchive, nicht die TOTP-Geheimnisse; `config.example.php` keines von beiden |

**K-3 beantwortet** (3.4): `pwquality.js` verlangt `MIN_LAENGE = 12`,
dieselbe Zahl wie `PW_MIN_LAENGE` in `db.php`; dazu die Liste häufiger
Passwörter, eine Anteilsregel gegen Wiederholungen und Reihen. Ein
zufällig erzeugtes Passwort wird nicht gegen den Rest gemessen — richtig,
es ist die stärkere Wahl. Kein Befund.

**Nr. 370 (`CryptoKey`) — Notiz für den Paketschnitt:** `getContentKey()`
hat **vier Verwenderdateien** mit zusammen elf Nennungen; der Schlüssel
liegt als Hex unter `pck` im `sessionStorage`, daneben das
`schluesselfach` (ein `CryptoKey`-Zwischenspeicher, den `clearSession()`
leert). Der Umbau ersetzt das Hex durch einen nicht-extrahierbaren
`CryptoKey` in IndexedDB; das Lebensdauermodell „ein Tab, ein Schlüssel"
bleibt (E-R17-08).

**Aufräumposten (kein Befund):**

- **Karteileiche, mit Gegenprobe:** `serverschluessel_zeile()` in
  `serverkrypto_lib.php` — 0 Nennungen in `server/`, `tools/` und `docs/`
  außer der Definition (seit S10 baut `config_eintrag_zeile()` die Zeile
  direkt; der Umschlag ist übrig geblieben).
- **Tote Zweige seit Web 23 (S11-12), von PHPStan bestätigt:**
  `edbak_build()` in `backup_lib.php` — die Vollausgabe mit `missions` und
  `rest_segments` am Ende der Funktion und die Zeile `if (!$nurKopf)
  $data['_spur_index'] = …` sind unerreichbar, weil der Riegel am Kopf nur
  noch Kopf oder Fenster durchlässt (`deadCode.unreachable`,
  `booleanNot.alwaysFalse`, `if.alwaysTrue`); `komp_schub_lauf()` in
  `komplett_lib.php` — das letzte `return` nach dem Siegel-Zweig
  (`deadCode.unreachable`). Dazu `$cursor === []` und `$stand === 'siegel'`
  in derselben Datei, `$u['pat_wrap_pw'] !== null` in `kdf_upgrade.php`,
  `$m['blob_alt'] !== null` in `pat_anheben.php`, `$kennungAlt !== null`
  in `serverkrypto_lib.php`: Vergleiche, die der Typ schon entscheidet —
  Gürtel und Hosenträger, lesbar, kein Fehler; das Aufräumpaket entscheidet
  je Stelle.
- **Register (Stück 10 prüft die Decke):** `api/kdf_upgrade.php` ruft
  `$pdo->beginTransaction()` direkt statt `db_transaktion()` — eine der
  Stellen, die die Registerzeile „Transaktion" oberhalb von null hält.
- **Bewusst wirkungslos:** der Parameter `csrf` in `schluessel.js` (die
  Kopfzeile setzt `EdApi`); ein Aufrufer reicht ihn noch durch.
- **Beobachtung ohne Posten:** `pw_handling.php` nimmt im Reset-Zweig
  `key_check = ''` an, wenn das Konto noch kein `pat_key_check` trägt
  (Altkonten) — dieselbe Klasse wie F-R17-06, dort mit abgedeckt.
- **Kommentardurchgang:** 987 Fundstellen (Tabelle oben); das Aufräumpaket
  erzeugt die Zeilenliste selbst.

**Vorsortierung, was sie gebracht hat:** PHPStan hat den einen Befund
geliefert, den die Lesung übersehen hätte (F-R17-33 — zwei wortgleiche
Zeichenketten in einem `||` liest ein Mensch als zwei verschiedene, weil
der Kommentar es sagt), und die toten Zweige bestätigt. Die 44
`class.notFound` sind kein Befund, sondern die Grenze der Konfiguration
(`vendor/` ausgeschlossen, phpseclib und ConstantTime ohne Autoloader);
die 29 `variable.undefined` sind `$userId` aus `auth_guard.php` in
jedem `api/`-Einstieg — ein Muster, das PHPStan ohne Bootstrap-Datei nicht
versteht. Beides steht im Prüfdokument 5.

#### 7.3.3 Stück 3 — Zugang und Sitzung (R17-07, 20.10.2026)

**Dateiliste, abgehakt — 31 Dateien, 11 238 Zeilen, jede von Fable
vollständig gelesen; dazu aus `db.php` der Teil, den 7.3 dem Stück
zuschlägt** (die JSON-Hülle `json_kopf()`, `json_roh_out()`, `json_out()`,
der Eingang `api_methode()`, `api_rumpf()`, `json_fehler()`, Zeilen 480 bis
830; der Rest von `db.php` ist Stück 6) **und `wegwerfdomains.txt` nur auf
Form** (8 883 Zeilen, eine Domain je Zeile — Inhalt liest niemand). Die
drei Einstiege, die Stück 2 hierher verwiesen hat (`api/passkey_anlegen.php`,
`api/rueckweg_anlegen.php`, `api/schluesselblatt_pruefen.php`), sind hier
gelesen; dazu `api/kopplung_stand.php` und `api/csp_bericht.php` und die
fünf Skripte, die nur dieses Stück verwendet. Die Verwender aus Stück 2
(`unlock.js`, `passkey.js`, `rueckweg.js`) sind nicht noch einmal gezählt.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `login.php` | 1 324 | 114 | `einwilligung_lib.php` | 222 | 8 |
| `logout.php` | 18 | 0 | `konto_lib.php` | 807 | 33 |
| `session_lib.php` | 483 | 23 | `einstieg_lib.php` | 382 | 11 |
| `sitzung_lib.php` | 755 | 58 | `kopplung_lib.php` | 223 | 6 |
| `auth_guard.php` | 712 | 59 | `kopfzeilen_lib.php` | 559 | 16 |
| `ratelimit_lib.php` | 1 642 | 77 | `netz_lib.php` | 143 | 5 |
| `reset_request.php` | 149 | 7 | `api/passkey_anlegen.php` | 121 | 14 |
| `pair.php` | 459 | 36 | `api/rueckweg_anlegen.php` | 141 | 6 |
| `jobs.php` | 386 | 12 | `api/schluesselblatt_pruefen.php` | 260 | 6 |
| `zweitfaktor.php` | 330 | 20 | `api/kopplung_stand.php` | 58 | 1 |
| `zweitfaktor_notweg.php` | 368 | 26 | `api/csp_bericht.php` | 143 | 2 |
| `registrieren.php` | 528 | 44 | `assets/api.js` | 220 | 4 |
| `bestaetigen.php` | 101 | 3 | `assets/kopplung.js` | 199 | 3 |
| `adresse_bestaetigen.php` | 61 | 2 | `assets/pow.js` | 80 | 4 |
| `einwilligung.php` | 179 | 3 | `assets/pow-worker.js` | 159 | 4 |
| | | | `assets/zweitfaktor.js` | 26 | 1 |

**Summe 608 Verweise.** Drei Dateien tragen 250 davon (`login.php`,
`ratelimit_lib.php`, `auth_guard.php`); die Checkliste aus 7.2 ist an
jedem der 19 HTTP-Einstiege des Stücks durchgegangen.

**Was die Lesung ergeben hat, in einem Absatz.** Der Zugang ist sorgfältig
gebaut, und die Sorgfalt ist an den richtigen Stellen: eine Sitzungsart je
Zweck mit festen Cookie-Parametern und `use_strict_mode` an genau einer
Stelle, die Bindung ohne Übernahme alter Sitzungen, die Rolle aus der
Zeile statt aus der Sitzung, ein halber Stand zwischen Passwort und Code,
der für jeden Endpunkt „nicht angemeldet" ist, konstante Antwortdauer in
jedem Fehlerzweig, der etwas verraten könnte (Anmeldung, Reset,
Registrierung, Kopplung, Notzugang, Schlüsselblatt), `hash_equals()` an
jedem Geheimnis, kein offener Umleiter (`zweitfaktor_zurueck()` lässt nur
Seiten der Liste zu), der Rückweg schaltet den Zweitfaktor erst nach Tor
und Sitzung ab, `csrf_ok()` nimmt Feld oder Kopfzeile und keinen Typ, den
es nicht erwartet. Die beiden Befunde sind Ränder derselben Bibliothek: Der
Ratenschutz zählt an einer Stelle zu viel (die Adresse in jedem Konto-Topf,
F-R17-36) und an einer anderen zu wenig (der Notzugang ohne Adresse,
F-R17-37) — und F-R17-32 aus Stück 2 hat in `einstellungen.php` drei
Geschwister. Nichts davon öffnet ein Konto; F-R17-36 kann eine Klinik
aussperren und widerspricht der Doku, und das ist der Grund für „mittel".

**Die Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-22 (niedrig) | unverändert: `jobs.php` nimmt `token` aus `$_GET` (oder `$_POST`), `adresse_bestaetigen.php` den Token aus `$_GET`; der erste Abruf jedes Links steht im Zugriffsprotokoll des Hosters |
| F-R17-23 (niedrig) | unverändert: `KOPF_HSTS_VORGABE = 1`, `kopf_csp_scharf()` liefert ohne Einstellung `false` (Report-Only); ob Produktiv umgeschaltet ist, sagt nur P-R17-04 |
| F-R17-31 (Hinweis) | für die Server-Anteile bestätigt (`ui_krypto_bootstrap()` ist Stück 7, die Wear-Prüfung Stück 9); nichts Neues |

**K-7 beantwortet** (3.4): Der Adresswechsel im Profil (`einstellungen.php`)
verlangt das Passwort (`old_token` gegen den Hash), schreibt die Adresse
**nicht** sofort, sondern merkt sie vor (`adresse_vormerken()`), schickt
den Bestätigungslink an die **neue** und eine Warnung an die **alte**
Adresse (`profil_adresswechsel_melden()`); erst der Klick schreibt
(`adresse_bestaetigen()`, das UNIQUE auf `email` fängt die Dublette dort).
Das ist das Double-Opt-In aus P5b. Der Adresswechsel durch die Verwaltung
ohne Bestätigung bleibt F-R17-06. Was der Passwortnachweis dort nicht hat,
ist ein Topf — das ist die Verschärfung von F-R17-32.

**K-15 beantwortet** (3.4): `csrf_ok()` in `session_lib.php` liest das Feld,
sonst die Kopfzeile `X-CSRF`, und prüft `is_string()` vor `hash_equals()`
— `csrf[]=x` ist „kein Token" und kein TypeError mehr. `csrf_check()` in
`auth_guard.php` antwortet 403 als JSON unter `api/`, sonst als Seite.
Header und Escaping: `kopfzeilen_seite()` setzt `nosniff`,
`Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, CSP mit Nonce
(`default-src 'none'`, `script-src 'self' 'nonce-…'`, `style-src-attr
'unsafe-inline'` mit Begründung, `img-src data:` mit Begründung) und HSTS
nur über HTTPS; `kopfzeilen_json()` den schmalen Satz. Kein Befund.

**P-R17-04** bleibt bei der Betreiberin (GitHub-Umgebungen, Zweitfaktor der
Organisation, Produktiv-Einstellungen); dieses Stück kann keinen der drei
Punkte aus dem Repositorium heraus beantworten.

**Aufräumposten (kein Befund):**

- **Karteileichen, mit Gegenprobe (0 Nennungen in `server/`, `tools/`,
  `docs/` außer der Definition):** `sitzung_bindung_loeschen()` in
  `sitzung_lib.php` (das Beenden ruft `sitzung_cookie_loeschen('bindung')`
  und `session_destroy()` direkt); `adresse_vormerkung_loeschen()` in
  `konto_lib.php` (der im Kommentar genannte „Abbruch durch die NutzerIn"
  hat keinen Knopf — entweder er kommt, oder die Funktion geht).
- **Zweite Schreibweise eines Merkmals:** `rate_konto_freigeben()` baut
  `'id:' . mb_substr(mb_strtolower($konto), 0, 180)` selbst statt
  `rate_merkmal_kennung()` zu rufen — läuft auseinander, sobald jemand die
  Normalisierung dort ändert (dieselbe Klasse wie F-R17-36, nur ohne
  Wirkung heute).
- **Kommentarwiderspruch** in `ratelimit_lib.php` gegen `login.php` (siehe
  F-R17-36) — mit dem Befund zu bereinigen, nicht vorher.
- **Register (Stück 10 prüft die Decke):** `pair.php` ruft
  `$pdo->beginTransaction()` direkt statt `db_transaktion()`;
  `login.php`, `zweitfaktor_notweg.php` und `kopplung.js` rufen `fetch()`
  direkt — zwei davon unangemeldet (kein Token, `EdApi` nicht geladen), die
  dritte ein GET; Stück 10 sieht nach, wie die Registerzeile sie zählt.
- **PHPStan, 9 gelesene Meldungen, keine ein Befund:** `db.php` 212/238
  (`!== []` gegen eine Konstante — Gürtel und Hosenträger), `jobs.php` 358
  (Fehlalarm: die Closure wird neu ausgewertet), `konto_lib.php` 362
  (`elseif`-Kette, die letzte Bedingung ist durch die vorigen bestimmt),
  `pair.php` 173/216 (`is_array($b)` zweimal, das zweite Mal schon
  entschieden), `registrieren.php` 232 (der Rest-Bit-Zweig von `pow_ok()`
  ist mit `POW_BITS = 16` unerreichbar — parametrisch richtig, heute tot).
- **Kommentardurchgang:** 608 Fundstellen (Tabelle oben).

**Vorsortierung, was sie gebracht hat:** nichts, was die Lesung nicht
hatte — die beiden Befunde sind Verhaltensfragen über drei Dateien hinweg
(welche Merkmale ein Aufruf bildet), die kein statisches Werkzeug sieht.
PHPStan hat die zwei Karteileichen nicht gefunden (Stufe 4 kennt keine
„ungenutzte Funktion"); die hat der `grep` über die Definitionen.

#### 7.3.4 Stück 4 — Einsatz, Diensttag, Spur, Ingest (R17-08, 20.10.2026)

**Dateiliste, abgehakt — 36 Dateien, 20 019 Zeilen, jede von Fable
vollständig gelesen:** die 22 PHP-Dateien des Kerns, die acht
`api/`-Einstiege des Stücks und die sechs Skripte, die Spur, Karte und
Formular tragen. `ortsfeld.js`, `ortswahl.js`, `locparse.js`,
`zeitfeld.js`, `vorschlagsliste.js` und `geocoder.js` sind Verwender des
Formulars und gehören zu Stück 7; `wartungslauf.php` und
`wartungslauf.js` sind Stück 6 (dort mit F-R17-39 im Blick).

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `ingest.php` | 1 314 | 117 | `mission_fields_lib.php` | 617 | 39 |
| `spur_lib.php` | 1 449 | 65 | `nachweis_lib.php` | 172 | 7 |
| `tag_spuren.php` | 468 | 10 | `geraete_lib.php` | 436 | 19 |
| `diensttag_datum.php` | 152 | 6 | `api/day.php` | 585 | 52 |
| `diensttag_lib.php` | 1 502 | 95 | `api/mission.php` | 304 | 28 |
| `diensttag_loeschen.php` | 111 | 3 | `api/range.php` | 278 | 30 |
| `diensttag_neu.php` | 199 | 10 | `api/schneiden.php` | 496 | 37 |
| `diensttag_zusammenfuehren.php` | 390 | 19 | `api/suchindex.php` | 272 | 29 |
| `tageszuordnung_lib.php` | 334 | 19 | `api/export_data.php` | 579 | 63 |
| `einsatz.php` | 1 148 | 69 | `api/gpx_import.php` | 174 | 12 |
| `einsatz_form.php` | 2 484 | 158 | `api/import_commit.php` | 913 | 62 |
| `einsatz_lib.php` | 192 | 19 | `assets/geo.js` | 229 | 14 |
| `einsatz_loeschen.php` | 92 | 3 | `assets/gpx.js` | 277 | 5 |
| `einsatz_verschieben.php` | 187 | 7 | `assets/schneiden.js` | 711 | 20 |
| `nachbearbeitung.php` | 387 | 17 | `assets/ortsdaten.js` | 519 | 19 |
| `nachbearbeitung_lib.php` | 371 | 34 | `assets/patient.js` | 382 | 14 |
| `import.php` | 321 | 24 | `assets/forms.js` | 170 | 5 |
| `validate_lib.php` | 1 273 | 55 | | | |
| `mission_fields.php` | 531 | 57 | | | |

**Summe 1 242 Verweise.** Drei Dateien tragen 370 davon (`einsatz_form.php`,
`ingest.php`, `diensttag_lib.php`); die Checkliste aus 7.2 ist an jedem der
20 HTTP-Einstiege des Stücks durchgegangen (12 Seiten, 8 `api/`).

**Was die Lesung ergeben hat, in einem Absatz.** Der Datenweg vom Gerät
bis zur Seite hält, was `CLAUDE.md` 4 verspricht, und zwar an den Stellen,
die es tragen: `ingest.php` weist Klartext-Ortsdaten mit `400 klartext` ab,
prüft Stück und Hülle über die Prüfschicht, zählt den Eingang mit
ausdrücklicher Merkmalsliste (das Muster, das F-R17-36 für die Anmeldung
empfiehlt), hält das Ersetzfenster an `created_at` und die Sperrliste für
beide Arten; jeder Schreibweg des Formulars, des Imports und des Schnitts
verlangt die Hülle und hält die Phasenzeiten des Browsers gegen die eigene
Rechnung, statt eine Hülle mit verrutschten Orten abzulegen; die Besitzprüfung
steckt in `einsatz_laden()` und `dt_laden()` und sonst in der Abfrage
(`user_id = ?` in jedem `SELECT`, `UPDATE` und `DELETE`, der ein Konto
betrifft); Spalten kommen aus dem Register, Tabellennamen aus Konstanten,
Werte an Platzhalter; CSRF steht vor jedem `POST`; der Export zieht seine
Schranke serverseitig (`NULL AS`, kein Blob, keine Spur ohne das Flag); die
Hüllen bindet der Browser an Art, Kennung und Platz und öffnet die gepackten
vor den Gerätestücken. Die vier Befunde liegen nicht im Schutz, sondern im
Verhalten: Eine Zeile, die ein Feld aus dem alten Spurformat liest, das es
seit Web 23 nicht gibt (F-R17-39, und das ist der Grund für „mittel" — der
GPX-Abruf fehlt auf Produktiv an jeder gepackten Spur, und der Reiter System
füllt sich), ein `use` mit zwei Namen zu viel (F-R17-38), eine
Papierkorb-Sperre im Import, die das Datum statt den Tag fragt (F-R17-40),
und eine Kennungsprüfung, die an der Zweigentscheidung vorbeigeht
(F-R17-41). Nichts davon öffnet fremde Daten oder verliert eigene.

**Die Altbefunde des Stücks, am Stand Web 23.0.2 nachgelesen — soweit das
Stück sie sieht:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-09 (mittel) | besteht: `einsatz_form.php` sagt unter dem Titel „Felder mit Schloss ver- und entschlüsselt dein Browser, alle anderen liegen lesbar auf dem Server" und verweist auf das Handbuch — dass eine getippte Adresse an Photon geht, steht weder dort noch in `CLAUDE.md` 4 oder `Technik.md` 4.98; `geocoder.js` und `ortsfeld.js` liest Stück 7, die Dokumente Stück 11 |
| F-R17-13 (mittel) | für den Server bestätigt: `ingest.php` nimmt nur Hüllen (`400 klartext`), die Spur liegt vom Eingang an verschlossen; der Klartext-Puffer auf dem Gerät ist Stück 8 und 9 (Lage H) |
| F-R17-28 (niedrig) | hier nicht sichtbar: Fristen auf der Uhr sind Stück 8; die Serverseite (Ersetzfenster 72 h, Sperrliste `deleted_refs`) hat keine Frist für geparkte Pakete und braucht keine |

**K-10 beantwortet** (3.4): gegenstandslos seit S11-05d — siehe die Zeile
dort. **Nr. 23 nur gelesen** (gehört zu Schritt 13): Seit S11 nimmt kein
Schreibweg des Servers eine Reanimationsart entgegen — Ingest, Formular,
Import und Schnitt bekommen die Reanimation nur in der Hülle, und der Server
öffnet sie nicht; `pruef_reanimationsart()` in `validate_lib.php` hat in diesem Stück
keinen Aufrufer mehr; der eine, der bleibt, ist `edbak_restore()` in
`backup_lib.php` (Stück 2, die Reanimation aus einer Sicherung). Geprüft
wird die Art sonst im Browser,
`EdOrtsdaten.einsatzZeiten()` gegen `RESUS_LABELS` ohne `beginn`. Ob der
Vertrag 3.3 damit stimmt, ist eine Frage an Stück 11, nicht an den Code.

**Aufräumposten (kein Befund):**

- **Karteileichen:** keine — 193 Funktionen der 14 PHP-Dateien mit
  Definitionen, jede außerhalb ihrer Datei genannt (`grep` gegen `server/`,
  `tools/`, `docs/`). Die Nennung in `docs/` zählt mit; wer schärfer zählen
  will, nimmt `docs/` heraus.
- **Eine tote Zelle:** `diensttag_loeschen.php` baut sein Posten-Array mit
  `null` an dritter Stelle und liest es mit `$klein ?? ''` — der Wert ist
  immer `null` (PHPStan `nullCoalesce.variable`). Entweder eine Kleinzeile
  hinein oder die Spalte heraus.
- **Ein ungenutztes `use`:** `einsatz_form.php`, der Rahmen
  `db_transaktion(…, function … use (…, $readField, …))` fängt `$readField`
  ein und ruft es nicht (PHPStan `closure.unusedUse`).
- **Eine Phase, die es nicht gibt:** `einsatz_form.php`, „Phase hinzufügen"
  rechnet `Math.min(last + 1, 10)`; das `<select>` kennt 2 bis 9, und nach
  einer Zeile mit Phase 9 fällt die neue auf Phase 2 statt auf 9. `9` statt
  `10`.
- **Ein doppelter Abruf:** `einsatz_form.php` holt `api/mission.php` beim
  Speichern ein zweites Mal (`ortSpurPunkte()`), obwohl `spurHolen()` die
  Antwort nach dem ersten Öffnen des Kartendialogs schon hält — zweimal die
  ganze Einsatzzeile samt Stücken je Speichern eines Einsatzes mit Spur.
  Eine Antwort für beide.
- **Register (Stück 10 prüft die Decke):** `ingest.php`,
  `diensttag_zusammenfuehren.php`, `api/day.php` und
  `api/import_commit.php` rufen `$pdo->beginTransaction()` direkt statt
  `db_transaktion()` — bei `ingest.php` mit Grund (die Wiederholschleife bei
  Gedrängel braucht den Rahmen selbst); `nachbearbeitung_lib.php` fragt
  `information_schema` von Hand (`nb_spalte_nullbar()`, `nb_moeglich()`)
  statt über `db_spalte_nullbar()`; `einsatz_form.php` ruft `fetch()` für
  zwei GETs ohne Token. Stück 10 sieht nach, wie die Registerzeilen sie
  zählen.
- **PHPStan, 14 gelesene Meldungen:** 5 sind die zwei Befunde
  (`tageszuordnung_lib.php` 231 bis 233 → F-R17-38; `einsatz.php` 44 →
  F-R17-39). Die übrigen 9: `diensttag_loeschen.php` 76 (die tote Zelle
  oben), `einsatz_form.php` 230 und 251 (M5-11: Gürtel und Hosenträger,
  Absicht), 512 (das `use` oben), `ingest.php` 509 (`isset` und `!== null`
  doppelt, harmlos) und 1268 (`?? false` auf gesetzter Variable, harmlos),
  `api/import_commit.php` 414 (`?? 0` auf vorhandenem Schlüssel, harmlos),
  `validate_lib.php` 448 und 449 (Rückfall für PHP vor 8.2, Absicht).
- **Kommentardurchgang:** 1 242 Fundstellen (Tabelle oben).

**Vorsortierung, was sie gebracht hat:** diesmal einen Befund. F-R17-39
stand als `offsetAccess.notFound` in der PHPStan-Liste, und erst die Lesung
hat daraus die Wirkung gemacht (welcher Zweig wann greift, was der
Wartungslauf tut, was die Probe misst); F-R17-38 stand als vier Meldungen
da. Die beiden anderen Befunde sind Verhaltensfragen über mehrere Blöcke
hinweg, die kein statisches Werkzeug sieht. Der Aufruferzähler hat nichts
gefunden, und das ist auch ein Ergebnis: Das Stück trägt keinen ungenutzten
Code.

#### 7.3.5 Stück 5 — Verwaltung und Admin (R17-09, 20.10.2026)

**Dateiliste, abgehakt — 31 Dateien, 18 322 Zeilen, jede von Fable
vollständig gelesen:** die 27 PHP-Dateien des Kerns (die Liste aus 7.3),
dazu die zwei `api/`-Einstiege und die zwei Skripte, die zu keinem anderen
Stück gehören: `api/rechtstext_vorschau.php` und `assets/rechtstext_vorschau.js`
(Vorschau der Rechtstexte), `api/rueckfrage.php` und `assets/rueckfrage.js`
(die Konto-Rückfrage, die auch die Kontoseite bedient). `schluessel.js`,
`schluessel_teile.php`, `zweitfaktor_teile.php`, `totp_lib.php`,
`passkey_lib.php`, `rueckweg_lib.php` sind Stück 2 und 3 und dort gelesen;
`kopplung.js`, `dialog.js`, `kartenfilter.js`, `ortsfeld.js` und die
Zip-Bibliothek sind Stück 7; `adminbackup_lib.php`, `komplett_lib.php`,
`sicherungsziel_lib.php` sind Stück 2 und 6. `geraetemodelle.php` ist eine
erzeugte Tabelle (325 Teilenummern) und auf Form gelesen.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `einstellungen.php` | 5 460 | 380 | `suche.php` | 1 282 | 61 |
| `konten_einstellungen_lib.php` | 448 | 15 | `zeitraum.php` | 953 | 72 |
| `admin_users.php` | 907 | 38 | `apk.php` | 85 | 6 |
| `admin_user.php` | 1 544 | 88 | `apk_lib.php` | 85 | 3 |
| `admin_demo.php` | 350 | 19 | `geraetemodelle.php` | 346 | 1 |
| `admin_installation.php` | 273 | 18 | `geraetemodelle_lib.php` | 198 | 4 |
| `admin_komplettsicherung.php` | 484 | 24 | `statistik_lib.php` | 295 | 19 |
| `admin_protokoll.php` | 301 | 5 | `betrieb_statistik.php` | 830 | 52 |
| `admin_rechtstexte.php` | 275 | 14 | `erststart_karte.php` | 165 | 6 |
| `admin_sicherungen.php` | 762 | 42 | `blatt_dialog.php` | 100 | 5 |
| `admin_sicherungsziele.php` | 742 | 41 | `rueckfrage_dialog.php` | 96 | 1 |
| `stammdaten_ui.php` | 506 | 42 | `api/rechtstext_vorschau.php` | 70 | 2 |
| `rechtstexte_lib.php` | 483 | 10 | `api/rueckfrage.php` | 94 | 1 |
| `rechtstext_seite.php` | 123 | 5 | `assets/rechtstext_vorschau.js` | 112 | 2 |
| `papierkorb.php` | 309 | 16 | `assets/rueckfrage.js` | 255 | 6 |
| `trash_lib.php` | 389 | 14 | | | |

**Summe 1 012 Verweise.** `einstellungen.php` trägt 380 davon allein — die
Datei ist mit 5 460 Zeilen die längste des Bestands und führt vier Reiter
mit 24 Handlungszweigen; die Checkliste aus 7.2 ist an jedem der 17
HTTP-Einstiege des Stücks durchgegangen (15 Seiten, 2 `api/`; dazu
`rechtstext_seite.php` hinter den vier Hüllen `impressum.php`,
`datenschutz.php`, `nutzungsbedingungen.php`, `avv.php`, ohne Anmeldung
und mit der Rolle aus der Datenbank).

**Was die Lesung ergeben hat, in einem Absatz.** Das Stück hält die
Rollen- und Besitzgrenzen, die es verspricht: Jede Admin-Seite steht hinter
`require_admin()` oder `require_betreiberin()`, jede Handlung hinter
`csrf_check()`, jeder Schreibweg auf Stammdaten, Geräte und Papierkorb
trägt `user_id = ?` in der Anweisung, und die Dateinamen der Sicherungen
laufen durch `komp_name_gueltig()`, `edbak_paketname_gueltig()` und
`protokoll_archiv_name_gueltig()`, bevor ein Pfad entsteht; das APK wird
nicht geprüft, sondern aus der Liste gewählt. Die Rechtstexte maskieren
den ganzen Text, bevor sie Struktur erkennen, und lassen Links nur über
eine Positivliste zu; die Vorschau rendert auf dem Server und zählt im
Topf `rt_vorschau`. Suche und Zeitraum schicken keinen Suchbegriff an den
Server, halten den Filterzustand im Fragment und maskieren jede Zelle über
`EdHtml.escape`. Passwort- und Adresswechsel verlangen das aus dem Passwort
abgeleitete Token, die Kontolöschung das Passwort selbst, und der
Passwortwechsel hebt den Sitzungszähler in derselben Transaktion. Die vier
Befunde liegen daneben: eine Handlung des Zweitfaktors, die das Muster von
E-SR-31 nicht mitbekommen hat (F-R17-43), eine Monatsprüfung, die zwei
Ziffern genügen lässt (F-R17-44), ein CSV-Schreiber ohne Formelschutz an
einem Wert, den das Gerät setzt (F-R17-45), und eine Sperrliste des
Demo-Kontos, die drei Handlungen vergisst (F-R17-42). Nichts davon öffnet
fremde Daten; F-R17-43 ist das einzige, das die Zusage „Zweitfaktor" berührt
— und nur mit einer Sitzung, die schon erbeutet ist.

**Die Altbefunde und Posten des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-07 (mittel), Nr. 371 | **bestätigt:** `admin_user.php` kennt gegenüber einem BetreiberIn-Konto zwei Riegel, den Rollenwechsel und „die letzte BetreiberIn"; `konto` (Adresse ändern), `konto_status` (sperren) und `user_delete` (mit frischem Code) laufen für ein zweites BetreiberIn-Konto bei Rolle `admin` durch. `admin_users.php` prüft beim Anlegen die Rolle, nicht beim Bearbeiten. Nr. 371 bleibt offen für 12b |
| F-R17-08 (mittel) | **bestätigt:** `ZF_FRISCH_HANDLUNGEN` hält acht Handlungen (`schluessel`, `schluesselblatt`, `rollenwechsel`, `totp_zuruecksetzen`, `user_delete`, `totp_ausschalten`, `passkey_anlegen`, `passkey_entfernen`); `admin_komplettsicherung.php` gibt die ganze Datenbank als entsiegeltes SQL (`art=klar`) nach `require_betreiberin()` und `csrf_check()` heraus, mit Protokolleintrag, ohne frischen Code — das Löschen eines Kontos verlangt ihn, der Dump aller Konten nicht |
| F-R17-02 (hoch) | unverändert, auch von der Verwaltungsseite her: `admin_sicherungen.php` `freigeben` ruft kein `protokoll()` und keine Mail, `freigeben` steht nicht in `ZF_FRISCH_HANDLUNGEN` |
| F-R17-06 (mittel) | unverändert: `admin_user.php` `konto` schreibt die Adresse ohne Nachweis und ohne Bestätigung an die alte; der Weg des Profils (`einstellungen.php`, Token plus Link an die neue und Warnung an die alte Adresse) ist der bessere und gilt nur für die eigene |
| Nr. 381 | **bestätigt:** `suche.php` holt den ganzen Bestand (`api/suchindex.php`), entschlüsselt jeden Einsatz (`EdPat.listeLaden(missions)`), baut je Einsatz den Heuhaufen und zeigt dann 200; `zeitraum.php` ebenso über `api/range.php` mit `entschluesselePat()` über alle und 200 Zeilen in der Tabelle; die Karte zeichnet jeden Pin. Der Weg steht im Backlog (E-S11-27) |

**Nur gelesen, kein Befund:** `admin_protokoll.php` zeigt dem Support über
`?konto=<id>` die Anmeldeadresse jedes Kontos als Pille, auch die der
Admins und BetreiberInnen, deren Kontoseiten ihm `admin_user.php`
vorenthält — dieselbe Auskunft tragen die Zeilen des Protokolls über
`urheber_user_id` ohnehin; eine Zeile für die Berechtigungsmatrix
(`Technik.md` 4.99p), Stück 11. `apk.php` liefert ohne CSRF per `GET`, was
nichts ändert (M3-11), und mit `Cache-Control: private`. Die Rückfrage
(`api/rueckfrage.php`) verschiebt nur Fristen und begründet, warum die
Sitzung reicht.

**Aufräumposten (kein Befund):**

- **Karteileichen:** keine — 140 Funktionen der 15 PHP-Dateien mit
  Definitionen, jede genannt (`grep` gegen `server/`, `tools/`, `docs/`);
  18 davon nur in der eigenen Datei (die Helfer der Seiten `admin_users.php`,
  `betrieb_statistik.php`, `rechtstexte_lib.php`), 27 sind JavaScript in
  `suche.php`, `zeitraum.php` und `einstellungen.php`.
- **Ein toter Zweig:** `einstellungen.php`, Standortseite, `if (!$sdBases)`
  mit der Meldung „Noch kein Standort verfügbar" — die Seite setzt
  `$sdBases = [$seiteB]` zwei Zeilen darüber, der Zweig ist nie wahr
  (PHPStan `booleanNot.alwaysFalse`). Heraus.
- **Ein Vermerk vor dem Schreiben:** `einstellungen.php`, `base_save` ruft
  `erststart_erledigt($userId, ERSTSTART_STANDORT)` VOR dem `INSERT IGNORE`
  — eine Dublette, die nichts anlegt, hakt den Erststart-Schritt trotzdem
  ab. Den Aufruf hinter `$sdNeuId()`.
- **Drei Handgriffe ohne Treffer:** `toggle`, `rename` und `delete` in
  `einstellungen.php` melden „Status geändert.", „Bezeichnung gespeichert."
  und „Gerät gelöscht." auch dann, wenn `WHERE id = ? AND user_id = ?`
  keine Zeile getroffen hat (`rowCount()` wird nicht gelesen). Dasselbe
  Muster wie bei den Stammdaten, nur ohne deren Dublettenmeldung.
- **Veraltete Rückgabeformen:** drei `@return`-Annotationen nennen nicht
  alles, was zurückkommt — `totp_zustand()` (`geraete`),
  `statistik_zeitraum()` (`bis_utc`, nur bei `eigen`),
  `edbak_auftrag_schub()` (`meldungen`); sieben PHPStan-Meldungen, kein
  Fehler im Lauf. Nachziehen, sonst meldet die nächste Vorsortierung dieselben
  sieben wieder.
- **Register (Stück 10 prüft die Decke):** `einstellungen.php` liest
  `$_GET['t']` nach `isset()` noch einmal mit `??` (eine Zeile ohne Wirkung);
  `admin_komplettsicherung.php` fragt `$error === null` in einem Block, der
  nur mit `$error === null` erreichbar ist. Beides PHPStan, beides harmlos.
- **PHPStan, der Rest:** 201 Meldungen in den Dateien des Stücks, 181
  `variable.undefined` (`$userId`, `$userRole`, `$row`, `$patReady` aus
  `auth_guard.php`, Rauschen); 20 gelesen — 0 Befunde, 7 die Rückgabeformen
  von oben, 10 Absicht (die leeren Konstanten `CONNECT_IQ_URL` und
  `PLAY_TEST_URL`, Weichen, die heute immer gleich entscheiden), 2
  Fehlalarme (`rt_html()` ändert `$absatz` und `$liste` über `&` in
  Schließungen, PHPStan sieht die Zuweisung nicht), 1 der tote Zweig.

#### 7.3.6 Stück 6 — Betrieb (R17-10, 20.10.2026)

**Dateiliste, abgehakt — 48 Dateien, 38 118 Zeilen, jede von Fable
vollständig gelesen:** die Liste aus 7.3, darunter `version.php` mit 8 267
Zeilen Kommentar und einer Zeile Code, `schema.sql` und die drei
Nachlesefassungen unter `migrations/`, `.htaccess` und `config.example.php`.
`betrieb_statistik.php` und `betrieb_schluesselblatt.php` stehen in der
Zeile aus 7.3 ausdrücklich nicht (Stück 5 und 2, dort gelesen); `db.php`
ist hier ganz gelesen, der CSRF- und API-Teil zum zweiten Mal (Stück 3).
`kopfzeilen_lib.php`, `csp_lib.php` und `auth_guard.php` sind Stück 3,
`komplett_lib.php`, `adminbackup_lib.php`, `serverkrypto_lib.php` Stück 2.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `update.php` | 81 | 14 | `protokoll_archiv_lib.php` | 606 | 2 |
| `migration_lib.php` | 4 314 | 219 | `ankuendigung.php` | 51 | 1 |
| `migrations/2026-07-16_mehrere_reanimationen.sql` | 6 | 0 | `ankuendigung_lib.php` | 262 | 2 |
| `migrations/2026-08-08_review-bausteine.sql` | 60 | 0 | `betrieb_jobs.php` | 305 | 22 |
| `migrations/2026-08-30_rechtstexte.sql` | 31 | 1 | `betrieb_server.php` | 1 827 | 117 |
| `schema.sql` | 1 154 | 202 | `betrieb_sicherheit.php` | 447 | 23 |
| `wartung_lib.php` | 1 023 | 58 | `betrieb_status.php` | 357 | 24 |
| `jobs_lib.php` | 1 501 | 88 | `betrieb_updates.php` | 445 | 38 |
| `sicherungsziel_lib.php` | 1 725 | 35 | `ui.php` | 3 497 | 198 |
| `speicher_lib.php` | 409 | 12 | `format_lib.php` | 452 | 25 |
| `smtp.php` | 405 | 15 | `zip_lib.php` | 147 | 4 |
| `email_lib.php` | 158 | 6 | `doku_lib.php` | 361 | 1 |
| `mail_lib.php` | 1 044 | 40 | `doku_seite.php` | 194 | 3 |
| `install.php` | 686 | 41 | `hilfe.php` | 18 | 1 |
| `db.php` | 2 096 | 163 | `ueber.php` | 15 | 1 |
| `transaktion_lib.php` | 98 | 17 | `index.php` | 1 712 | 148 |
| `konfig_lib.php` | 117 | 4 | `version.php` | 8 268 | 801 |
| `plattform_lib.php` | 709 | 29 | `config.example.php` | 105 | 7 |
| `php_mindest.php` | 34 | 3 | `.htaccess` | 129 | 10 |
| `status_lib.php` | 1 282 | 53 | `api/health.php` | 130 | 4 |
| `systemmeldung_lib.php` | 427 | 6 | `impressum.php` | 12 | 1 |
| `umgebung_lib.php` | 87 | 4 | `datenschutz.php` | 9 | 1 |
| `instanz_lib.php` | 344 | 13 | `avv.php` | 15 | 3 |
| `protokoll_lib.php` | 948 | 31 | `nutzungsbedingungen.php` | 15 | 3 |

**Summe 2 494 Verweise.** `version.php` trägt 801 davon — die Datei ist das
Gedächtnis jeder Hauptnummer —, `migration_lib.php` 219, `schema.sql` 202,
`ui.php` 198. Die Checkliste aus 7.2 ist an jedem der 16 HTTP-Einstiege
des Stücks durchgegangen (11 Seiten: `update.php`, `install.php`,
`ankuendigung.php`, fünf `betrieb_*.php`, `hilfe.php`, `ueber.php`,
`index.php`; die 4 Rechtstext-Hüllen; `api/health.php`), dazu
`doku_seite.php` hinter den zwei Hilfeseiten.

**Was die Lesung ergeben hat, in einem Absatz.** Der Betrieb hält, was er
verspricht, an den Stellen, die zählen: Jede Betriebsseite steht hinter
`require_betreiberin()`, jede Handlung hinter `csrf_check()`; die
Migrationen laufen nur auf einen POST mit Token oder von der Kommandozeile,
ihre Sperren (Vorbedingung ohne Freigabe, Inhalt mit Freigabe je Kennung)
und die Schluckliste 1050/1060/1061/1091 sind gelesen und stimmen mit dem
Kommentar überein; der Torwächter hasht die Kennungen und lässt bei einem
eigenen Fehler durch, wie er es ankündigt. Der Hilfe-Renderer läuft mit
`setSafeMode(true)` und `setMarkupEscaped(true)` und nimmt Bilder nur
relativ; `api/health.php` prüft den Ratenschutz vor dem Token und
antwortet karg. Die Bausteine in `ui.php` maskieren jeden Text, den ein
Aufrufer als Text übergibt, und nehmen rohes Markup nur dort, wo der
Vertrag es sagt (`attr`, `aktionen`, `plaketten`, `knopf`, `unter`); die
achtzehn `unter`-Aufrufer sind nachgelesen, die zehn mit Laufzeitwert
maskieren. Die Jobs halten sich an ihre Zeitstempel-Sperre, die
Sicherungsziele an ihre drei Sicherungen vor dem Löschen, das Protokoll
kürzt und escaped seine `LIKE`-Muster. Die vier Einträge liegen daneben:
zwei Laufzeitdateien, die `.htaccess` nicht kennt (F-R17-47), eine Zusage
über Relais ohne Anmeldung, die der Code nicht einlöst (F-R17-46), zwei
Meldungen mit Tags in einem Text, der maskiert wird (F-R17-49), und ein
Meldungsmuster, das die Nummer nicht führt, die sein Kommentar meint
(F-R17-48). Nichts davon öffnet Daten; F-R17-47 ist das einzige, das etwas
nach außen gibt, und das ist der Name der Person, die die Wartung
geschaltet hat.

**Die Altbefunde und Posten des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-05 (hoch) | **beim Stand der Betreiberin:** die vier Pflichtstücke des Wiederanlaufs stehen in Rahmenplan 6.1; kein Code dieses Stücks ändert daran etwas — `betrieb_schluesselblatt.php` ist Stück 2, der Anteil liegt in `config.php`. Ob Produktiv sie erledigt hat, kann hier niemand messen |
| F-R17-19 (mittel) | **bestätigt, von der anderen Seite:** `edbak_ablage_bereit()` hat fünf Aufrufer — `adminbackup_lib.php`, `admin_sicherungen.php`, `speicher_lib.php`, `status_lib.php`, `komplett_lib.php` —, `wiederherstellen.php` ist keiner; die `.htaccess` unter `sicherungen/` entsteht also erst, wenn eine dieser fünf Stellen gelaufen ist. `.htaccess` im Webroot sperrt `sicherungen/` nicht selbst (die Liste kennt nur Dateinamen, F-R17-47) |
| F-R17-21 (niedrig) | **bestätigt:** `mail_konto_entfernen()` hat keinen Aufrufer (0 Treffer in `server/`, `tools/`, `docs/` außer der Definition); der Docblock „wer ein Konto löscht, ruft das hier mit auf" ist unerfüllt. Empfänger und Betreff unzustellbarer Zeilen überleben das Konto bis zum 30-Tage-Aufräumen in `job_aufraeumen_schritte()` |
| Nr. 77 | **gelesen:** `update.php` ist seit Web 15.2.0 eine Weiche (302 auf `betrieb_updates.php`) mit dem Notausgang `php update.php` ohne Sitzung; `WARTUNG_AUSNAHMEN` führt die Datei, damit der Notausgang in der Wartung erreichbar bleibt. Der Posten steht auf „teilweise" (E-S8-05: aufgelöst statt aufgeteilt) und bleibt bei 12b |

**Nur gelesen, kein Befund:** `wartung_ausnahme()` vergleicht
`basename(SCRIPT_NAME)` — die 26 Dateinamen unter `api/` tragen keinen der
Namen aus `WARTUNG_AUSNAHMEN`, ein Skript kann sich also nicht in die
Ausnahme hineinbenennen. `jobs_token` liegt in `app_state` (E-S2-17,
bewusst: ein Datenbankabzug trägt das Token). `mig_klartext_wo()` nennt die
Anmeldeadresse des Kontos im Detailtext der gesperrten Migration — nur der
BetreiberIn, über `ui_zeile()` maskiert. `install.php` prüft den Nachweis
vor der Datenbankprobe und gibt `getMessage()` nur der Einrichterin.
`speicher_dateien_bytes()` zählt `.sitzungen/` und `apk/` mit — die
Speichergrenze misst damit auch, was keine Sicherung ist; das ist der
dokumentierte Sinn der Zahl.

**Aufräumposten (kein Befund):**

- **Drei Karteileichen:** `protokoll_frist_verwaltung_setzen()` und
  `protokoll_fehler_quittieren()` in `protokoll_lib.php`, `doku_bestand()`
  in `doku_lib.php` („für die Statusseite", die sie nicht ruft) — 412
  Funktionen der 27 PHP-Dateien mit Definitionen gegen `server/`, `tools/`,
  `docs/`, 61 nur in der eigenen Datei genannt (Seitenhelfer, Schritte der
  Jobs, 5 JavaScript in `index.php`). Für die A4-Kandidaten (Nr. 21).
- **Eine Inhaltsprüfung, die 0 für leer hält:**
  `migrationen_inhalt_zaehlen()` zählt `IS NOT NULL AND <> ''`; auf einer
  numerischen Spalte vergleicht MySQL `''` als 0, eine Ortshöhe von 0 m
  oder eine Koordinate 0 gilt als leer und sperrt nicht. Für Produktiv
  gegenstandslos (die Migration ist gelaufen), für eine Fremdanlage eine
  Lücke der Sperre, die ohnehin freigebbar ist. Je Spaltentyp vergleichen.
- **Ein Tagesvergleich in zwei Zeitzonen:** `jobs_einen_lauf()` rechnet
  `$heute` in der PHP-Zeitzone und vergleicht mit `DATE(letzter_lauf)` in
  UTC — die Tagesgrenze der täglichen Jobs liegt bis zu zwei Stunden
  neben Mitternacht. Beide Seiten in UTC.
- **Eine Pflichtliste ohne `link`:** `mail_katalog()` führt bei
  `freigeschaltet` `pflicht => []`, der Text liest `$d['link']`; der eine
  Aufrufer (`admin_user.php`) übergibt ihn. Nachziehen, damit der nächste
  Aufrufer es nicht vergisst.
- **Ein Rückfall von vor Web 20.10.0:** `job_aufraeumen_schritte()`,
  Schritt „Ratenschutz-Zähler", fragt nach der Spalte `stufe_bis`, die seit
  `2026_09_16_ratenschutz_stufen` jede Anlage hat. Heraus.
- **„Mengen je Konto" läuft über alle Konten**, auch gesperrte,
  unbestätigte und das Demo-Konto, und schreibt täglich je Konto eine
  Marke `mengen_gemeldet:<id>` in `app_state`. Auf `status = 'aktiv'`
  einschränken.
- **Zwei Kommentare, die mehr versprechen als der Code:**
  `instanz_namen_setzen()` schließt laut Kommentar „Zeichen, die in einer
  Mail-Kopfzeile etwas bedeuten" aus, prüft aber nur `\x00`–`\x1F` und
  `\x7F` (unschädlich: `smtp_send()` kodiert den Betreff mit
  `mb_encode_mimeheader`); `install.php` nennt sich „PHP-7-lesbar" und
  benutzt `fn` (7.4). Dazu `migrations/2026-08-30_rechtstexte.sql` mit
  `stand_am DATE`, das Schema hat `DATETIME` — die Nachlesefassung ist
  veraltet. `smtp_send()` ersetzt `\n` durch `\r\n` vor dem Dot-Stuffing;
  ein Rumpf, der schon CRLF trägt, würde `\r\r\n` — heute trägt keiner.
- **PHPStan, der Rest:** 66 Meldungen in den Dateien des Stücks, 20
  `variable.undefined` (Rauschen aus `auth_guard.php`), 25
  `class.notFound`/`class.noParent`/`method.notFound` (phpseclib und
  Parsedown liegen unter `vendor/`, das die Vorsortierung nicht liest —
  Rauschen); 21 gelesen — 0 Befunde, 3 veraltete Annotationen
  (`job_waisen_rueckstand()` und `protokoll_archiv_ausliefern()` geben nie
  `null`, `protokoll_filter_sql()` liefert kein `idx`), 9 Absicht (der
  KDF-Übergangsblock in `status_lib.php`, tot, solange `KDF_ITER_LISTE` ein
  Element hat; `$pdo !== null` dreimal in `install.php` als Lesehilfe; zwei
  Weichen in `betrieb_server.php`, die heute immer gleich entscheiden), 1
  Fehlalarm (`transaktion_lib.php`: `inTransaction()` nach einem DDL-Befehl
  kann falsch sein, PHPStan hält es für immer wahr — der Kommentar dort
  erklärt den Fall), 8 kosmetisch (`$typen === []` zweimal in `db.php`, die
  Redundanzen in `ui.php`, `api/health.php`, `sicherungsziel_lib.php`, ein
  ungenutztes `use $kennung` in `smtp.php`, ein `??` auf `$_POST` in
  `install.php`).

#### 7.3.7 Stück 7 — Browser-Skripte und Stylesheet (R17-11, 20.10.2026)

**Dateiliste, abgehakt — 58 Dateien, 22 597 Zeilen, jede von Fable
vollständig gelesen:** alle 57 Skripte unter `assets/` und `style.css`
(4 814 Zeilen, Abschnitt für Abschnitt). Die 30 Skripte, die in den Stücken
2 bis 5 schon gelesen waren, sind hier ein zweites Mal gelesen — mit dem
Blick auf Verwender, tote Zweige und Einsetzungen, nicht noch einmal auf die
Kryptographie. Dazu die Köpfe der fünf Textdateien unter `vendor/`
(`leaflet.css` 667 Zeilen, `leaflet.js` 12, `qrcode.js` 2 310,
`xlsx.full.min.js` 33, `zipjs.min.js` 7 — minifizierte Rümpfe sind nicht
Zeile für Zeile gelesen, sondern gegen ihre Prüfsumme gehalten), die fünf
Leaflet-PNG und die zehn Schriftdateien nur als Bestand.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `ankuendigung.js` | 87 | 1 | `menue.js` | 216 | 10 |
| `api.js` | 220 | 6 | `missiontable.js` | 932 | 72 |
| `blatt-drucken.js` | 27 | 2 | `openlocationcode.js` | 720 | 0 |
| `blatt.js` | 152 | 1 | `ortsdaten.js` | 519 | 19 |
| `confirm.js` | 199 | 3 | `ortsfeld.js` | 599 | 39 |
| `crypto.js` | 1 035 | 44 | `ortswahl.js` | 273 | 13 |
| `daylist.js` | 61 | 2 | `passkey.js` | 173 | 13 |
| `dialog.js` | 186 | 4 | `patient.js` | 382 | 14 |
| `doku.js` | 108 | 1 | `pow-worker.js` | 159 | 6 |
| `export.js` | 1 700 | 84 | `pow.js` | 80 | 5 |
| `format.js` | 293 | 12 | `pwquality.js` | 682 | 11 |
| `forms.js` | 170 | 5 | `qr.js` | 71 | 1 |
| `geo.js` | 229 | 14 | `rechtstext_vorschau.js` | 112 | 2 |
| `geocoder.js` | 201 | 9 | `reiter.js` | 45 | 1 |
| `gpx.js` | 277 | 5 | `rueckfrage.js` | 255 | 6 |
| `html.js` | 130 | 3 | `rueckweg.js` | 161 | 10 |
| `import.js` | 852 | 9 | `schluessel.js` | 185 | 12 |
| `import_profiles.js` | 513 | 31 | `schluesselblatt.js` | 249 | 7 |
| `import_ui.js` | 1 127 | 39 | `schneiden.js` | 711 | 20 |
| `kartenfilter.js` | 155 | 4 | `schublade.js` | 103 | 0 |
| `keyguard.js` | 161 | 6 | `suchtext.js` | 293 | 6 |
| `kontopaar.js` | 197 | 6 | `symbol.js` | 69 | 0 |
| `kopieren.js` | 76 | 2 | `unlock.js` | 579 | 34 |
| `kopplung.js` | 199 | 3 | `vorladen.js` | 47 | 3 |
| `listenkopf.js` | 55 | 4 | `vorschlagsliste.js` | 322 | 8 |
| `locparse.js` | 294 | 0 | `wartungslauf.js` | 381 | 10 |
| `luftlinie.js` | 169 | 12 | `zeitfeld.js` | 168 | 2 |
| `map_fullscreen.js` | 195 | 8 | `zweitfaktor.js` | 26 | 2 |
| `map_layers.js` | 203 | 7 | `style.css` | 4 814 | 276 |

**Summe 929 Verweise.** `style.css` trägt 183 davon, `missiontable.js` 46,
`import_ui.js` 43 — die Skripte erzählen ihre Geschichte in den Köpfen, nicht
im Code. Die Checkliste aus 7.2 hat für ein Skript drei Fragen: Woher kommt
jeder Wert, der in die Seite geht; was geht wohin über das Netz; was bleibt
im Browser liegen. Alle drei sind an jedem Skript gestellt.

**Was die Lesung ergeben hat, in einem Absatz.** Die Browserseite hält ihre
Zusagen: Jeder Wert aus Daten — `pat_blob`, Stammdaten, Suchwörter,
Adresstreffer, Dateinamen einer Sicherung — geht über `EdHtml.escape()` in
`innerHTML`, oder er geht über `textContent`, oder er ist nach Bauart auf
`[a-z0-9]` begrenzt, bevor er eingesetzt wird (`pwquality.js` zitiert nur
normalisierte Reste, `symbol.js` filtert den Namen auf `[a-z0-9-]`,
`suchtext.js` und `vorschlagsliste.js` heben auf dem maskierten Text hervor
und setzen nur `$1` ein). Die zwei Stellen, die Markup aus einer Antwort
nehmen, sind benannt und richtig: `rechtstext_vorschau.js` setzt das Ergebnis
von `rt_html()` ein, derselben Funktion, deren Ausgabe die öffentliche Seite
trägt, und `html.js` kennt mit `roh` ein Loch, das genau ein Verbraucher mit
einzeln maskierten Werten benutzt (`import_ui.js`). Der Server bekommt, was
er bekommen soll: `api.js` ist die eine Sendestelle mit CSRF und
`same-origin`, die vier `fetch`-Aufrufe daneben sind `GET` auf die eigene
Adresse oder gehen an den Adressdienst, der abschaltbar ist (K-6); der
Kartenausschnitt geht an vier Kachelhosts, die in `KOPF_KACHELN` stehen und
im Bedrohungsmodell (Lage G, R4). Im Browser bleibt, was bleiben soll:
`localStorage` nur die Kartengröße, `sessionStorage` Menüzustand, die
Ausgelassenen des Wartungslaufs und die Schlüsselablage aus Stück 2
(`edk` bis zum Tabende, `edkvor` bis zum ersten Verbrauch — F-R17-24
bestätigt, kein Befund neu). `eval`, `new Function` und `document.write`
kommen nicht vor; `node --check` findet in 61 Dateien keinen Fehler. Die
zehn `style="`-Attribute sind gerechnete Prozentwerte und Spurfarben aus
Token, und `style-src-attr 'unsafe-inline'` steht in der CSP mit genau
dieser Begründung. Die zwei Einträge liegen daneben: eine Prüfsumme, die
niemand nachrechnen kann (F-R17-50), und eine Fremdbibliothek, die der
Lizenznachweis nicht kennt (F-R17-51). Beides sind Lücken der
Herkunftszusage, keine der Verschlüsselung.

**Die Altbefunde und Posten des Stücks, am Stand Web 23.0.2 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-24 (niedrig) | **bestätigt:** `keyguard.js` hält `edk` in `sessionStorage` bis zum Tabende, geräumt nur von `clearSession()` (Abmelden, Kontowechsel); `edkvor` schreibt die Anmeldung, und `unlock.js` räumt es in `loeseVormerkung()` an allen drei Ausgängen (`vergissAbleitungen()`), sonst beim nächsten `ensureContentKey()`. Ein Tab, der offen bleibt, hält den Inhaltsschlüssel, solange die PHP-Sitzung längst abgelaufen ist — wie in 2.3 beschrieben; bleibt bei der Freigaberunde |
| Nr. 341 | **gemessen:** `vollstaendigkeit.py` meldet 13 Regeln „im Markup nicht gefunden"; sieben mit Grund (vier `geo-ring-*`, zwei `zaehler-*`, `leaflet-div-icon`), sechs ohne — `feld-reihe` (nur im Docblock von `ui_feld()`), `karte-neben`, `kennzahl-raster-5`, `sd-titel` (nur in Kommentaren von `einstellungen.php` und `version.php`), `sd-zahl`, `symbol-gefuellt` (nur im Docblock von `ui_symbol()`). Alle sechs haben keinen Verwender im Markup, keiner ist vergessen: `.sd-titel`/`.sd-zahl` sind mit S9/AP5 durch den Kartenkopf abgelöst, `.karte-neben` durch `.tag-raster`, `.kennzahl-raster-5` durch die Vierer- und Dreierraster, `.feld-reihe` nie gebaut, `.symbol-gefuellt` ohne Stern. Der Fokus-Zweig in `fokus()` (`einstellungen.php`) ist nicht Teil dieses Stücks (Stück 5, dort gelesen). Weg wie im Posten: streichen, `Design.md` mitziehen — bleibt bei 12b |
| Nr. 380 | **gezählt:** `asset('assets/*.js')` je Seite — `einstellungen.php` 20, `index.php` 19, `einsatz_form.php` 13, `einsatz.php` 9, `import.php` 9, `zeitraum.php` 6, `tag_spuren.php` 6, `suche.php` 5; dazu 7 aus `ui_geruest_ende()` auf jeder Seite mit Gerüst (`symbol.js`, `html.js`, `format.js`, `api.js`, `schublade.js`, `menue.js`, `blatt.js`). Die Zahl im Posten („15 bis 22") stimmt für die drei großen Seiten; `vorladen.js` (S11-04c) nimmt der ersten Anfrage die Wartezeit, nicht der Skriptzahl. Bleibt offen |
| Nr. 382 | **nur gelesen:** die erste Antwort von Startseite und Anmeldung hängt an PHP, nicht an den Skripten; kein Mittel dieses Stücks misst sie. Bleibt offen |

**Nur gelesen, kein Befund:** `map_layers.js` schickt den Kartenausschnitt
an vier fremde Kachelserver — benannt im Kopf, in `KOPF_KACHELN`
(`kopfzeilen_lib.php`) und im Bedrohungsmodell (Lage G, R4; F-R17-09 für
die Zusage-Texte). `export.js` neutralisiert Formelzellen (`csvEscape()`),
also das, was der Server-Export nicht tut (F-R17-45). `gpx.js` liest mit
`DOMParser` als `application/xml`, weist `DOCTYPE` ab und deckelt auf 12 MB
und 50 000 Punkte. `pow-worker.js` prüft den Auftrag (`^[0-9a-f]+$`, Bits 1
bis 32) und rechnet SHA-256 selbst, mit Gegenprobe gegen WebCrypto.
`passkey.js` verlangt `attestation: 'none'`. `wartungslauf.js` öffnet das
gepackte Stück vor dem Senden noch einmal und vergleicht Punkt für Punkt.
`schneiden.js` fragt vor dem Löschen über `edConfirm()` und schickt den
`stand` mit, gegen den der Server tauscht. `missiontable.js` maskiert in
`zelleGeschuetzt()` und `kachelGeschuetzt()` selbst, `hervor()` bekommt den
Wert maskiert — die Entscheidung liegt nicht beim Aufrufer (Nr. 22).

**Aufräumposten (kein Befund):**

- **14 exportierte Einträge ohne Verwender außerhalb ihrer Datei**
  (Exportprobe gegen `server/` und `tools/`): `EdGeocoder.dienst`, `.host`,
  `.MINDESTZEICHEN`; `EdGpx.STUFE_DUENN`; `EdLuftlinie.text`, `.FARBE`;
  `EdPat.hinweisUnlesbar`, `.markeUnlesbar`; `EdRueckweg.paarErzeugen`,
  `.paarSenden`; `EdVorschlaege.hervor`; `EdWartungslauf.lauf`,
  `.ausduennen`, `.ausduennungPruefen`. Ein fünfzehnter
  (`EdKontopaar.paarErzeugen`) hat eine Probe. Die drei des Wartungslaufs
  sind Kandidaten für eine Probe, nicht fürs Streichen (die Ausdünnung ist
  wörtlich `spur_lib.php` und hat im Browser keinen Prüffall). Für die
  A4-Kandidaten (Nr. 21).
- **Ein fester Hexwert in einem Skript:** `luftlinie.js` hält
  `FARBE = '#4280E5'` — das ist `--blau`, und `geo.js` liest seine Farben
  über `getComputedStyle` aus den Token. Die Vollständigkeitsprüfung zählt
  Hexwerte nur im Stylesheet. Über das Token lesen, wie `spurFarbe()`.
- **Drei Pixelwerte außerhalb der Skala, die unter einer
  Eigenschaftsausnahme durchgehen:** `.kopf-marke img{height:34px}`,
  `.anmeldung-logo{height:56px}` (= `--kopf`), `.plakette .symbol{width:14px;
  height:14px}`; dazu `.blatt-kopf img{height:11mm}`. `vollstaendigkeit.py`
  nimmt ganze Eigenschaften aus (`vollstaendigkeit-ausnahmen.md`), nicht
  einzelne Stellen — eine Ausnahme nach Eigenschaft deckt auch, was sie nicht
  meint. Entweder Token (`--kopf`, `--symbol-klein` − 2) oder je Stelle mit
  Grund.
- **Zwei Abschnitte „26" im Stylesheet:** „26 EINSTELLUNGEN UND
  VERWALTUNGSLISTEN" und, nach 29 und 30, „26. DRUCK" (S10/AP3). Die
  Gliederung steht auch in `Design.md`; die zweite Nummer gehört
  umnummeriert.
- **`rueckfrage.js` und `schluesselblatt.js` lassen eine abgelehnte Antwort
  still durch** (400/403 mit gültigem JSON) — beide Kommentare sagen es
  selbst („ist ein Befund und in AP8 bewusst nicht behoben"); die Frist
  bleibt dann fällig, die Frage kommt wieder. Folgenlos, aber zwei stille
  Stellen, die ein `console.error` kosten würden.
- **`suchtext.js`, `hervor()`:** Ein Suchwort wie „amp" oder „lt" trifft im
  maskierten Text die Entität (`&amp;`) und zerteilt sie — Anzeige, kein
  Markup, im Kommentar als „hier reicht der einfache Weg" benannt. Über
  einen Textknoten suchen oder Entitäten überspringen.
- **Kommentare, die älter sind als der Code:** `patient.js` sagt
  „symbol.js, das diese Seite nicht in jedem Fall lädt" (zwei Absätze
  weiter: nachgemessen, es lädt immer); `map_fullscreen.js` nennt vier
  Karten, `map_layers.js` fünf; `missiontable.js` nennt `index.php` als
  Seite, die „ihre Zeile selbst baut" (seit AP9b nicht mehr).

#### 7.3.8 Stück 8 — Uhr (R17-12, 20.10.2026)

**Dateiliste, abgehakt — 39 Dateien, 5 650 Zeilen, jede von Fable
vollständig gelesen:** die 20 Monkey-C-Dateien unter `watch/source/`
(5 234 Zeilen), die zwei Geräteprofile (`source-tasten3/`,
`source-tasten5/`), `manifest.xml` (99 Geräte, zwei Berechtigungen:
`Positioning`, `Communications`, `minApiLevel` 3.1.0), `monkey.jungle`, die
drei Dateien unter `resources/settings/` und `resources/strings/` und die
zwölf `drawables.xml` der Symbolordner. Die Checkliste aus 7.2 hat für die
Uhr vier Fragen: Woher kommt der Schlüssel, mit dem sie verschließt; was
liegt im Speicher und wie lange; was geht über das Netz wohin; und was
geschieht bei jeder Antwort des Servers. Alle vier sind an jeder Datei
gestellt.

| Datei | Zeilen | Verweise | Datei | Zeilen | Verweise |
|---|---|---|---|---|---|
| `source/ClockView.mc` | 390 | 0 | `source/StatsView.mc` | 53 | 0 |
| `source/Const.mc` | 131 | 15 | `source/SyncView.mc` | 414 | 14 |
| `source/Cpr.mc` | 176 | 0 | `source/Track.mc` | 293 | 0 |
| `source/CprView.mc` | 430 | 0 | `source/Ui.mc` | 161 | 1 |
| `source/Huelle.mc` | 422 | 16 | `source/Uploader.mc` | 478 | 19 |
| `source/HuelleVektor.mc` | 15 | 3 | `source/Util.mc` | 151 | 1 |
| `source/Input.mc` | 243 | 0 | `source-tasten3/DeviceProfile.mc` | 10 | 0 |
| `source/Model.mc` | 332 | 8 | `source-tasten5/DeviceProfile.mc` | 9 | 0 |
| `source/NAdokuApp.mc` | 32 | 0 | `manifest.xml` | 146 | 1 |
| `source/Nav.mc` | 34 | 0 | `monkey.jungle` | 139 | 0 |
| `source/Pair.mc` | 1 055 | 46 | `resources/settings/properties.xml` | 30 | 2 |
| `source/PairView.mc` | 219 | 5 | `resources/settings/settings.xml` | 26 | 0 |
| `source/SpeedView.mc` | 61 | 0 | `resources/strings/strings.xml` | 6 | 0 |
| `source/StartView.mc` | 144 | 0 | 12× `drawables.xml` | 50 | 0 |

**Summe 131 Verweise.** `Pair.mc` trägt 46 davon, `Uploader.mc` 19,
`Huelle.mc` 16 — die Uhr erzählt ihre Entscheidungen in langen
Blockkommentaren vor dem Code, und die Verweise sind E-S5-, E-S11- und
Backlog-Nummern.

**Was die Lesung ergeben hat, in einem Absatz.** Die Hülle hält, was S11
versprochen hat: `Huelle.mc` baut je Hülle ein flüchtiges P-256-Paar,
leitet mit HKDF-SHA256 (Info `nadoku-huelle-1` plus Empfängerschlüssel,
Salz der flüchtige öffentliche Schlüssel) den Inhaltsschlüssel ab,
verschließt mit AES-256-CBC und bindet Kopf und Bindung mit HMAC-SHA256;
`gleich()` vergleicht in konstanter Zeit, `schluesselSetzen()` nimmt nur
65 Byte mit `0x04` am Anfang und lässt `createPublicKey` den Kurvenpunkt
prüfen, und der Selbsttest misst die Byteordnung des Geräts gegen den
Vektor aus `HuelleVektor.mc` (E-S11-30). Die Kopplung tut, was E-S5-22
verlangt: `Pair.mc` hält Kennung und Schlüssel bis zur `200` auf
`bestaetigen ja` nur im Arbeitsspeicher, jeder Rückruf trägt die
Sitzungskennung, mit der gefragt wurde, und `lokalTrennen()` löscht `cred`,
die Properties, den Kontoschlüssel und die geparkten Pakete. Der Uploader
unterscheidet, was zu unterscheiden ist: `401` und `403` sind „abgemeldet"
(im Arbeitsspeicher, kein Paket wird geparkt), `400` mit `error=payload`
parkt das eine Paket mit Zeitstempel, alles andere wird wiederholt;
`credentials()` liest den Speicher vor den Properties. Über das Netz gehen
sechs `makeWebRequest`-Aufrufe, fünf in der Kopplung (`pair.php`) und einer
im Uploader; `println` kommt nicht vor, `Math.rand()` nur in `Util.newRef()`
(Zähler plus Zufall, eine Referenz, kein Geheimnis). Im Speicher liegen elf
Arten von Schlüsseln (`cred`, `cpr`, `refseq`, `kschl`, `kpruef`,
`ack_<ref>`, `meta_<ref>`, `bad_<ref>`, `<ref>_<n>` zu 200 Punkten,
`K_STATE`, `K_TRACK_META`) — Spur, Reanimation und Zugangsdaten im
Klartext, wie F-R17-13 und I-31 es sagen und E-R17-09 es entschieden hat.
Die zwei Einträge liegen im **Lebenslauf eines Pakets**, nicht in der
Kryptographie: ein Index, der eine Netzanfrage lang gilt und danach auf
ein anderes Paket zeigen kann (F-R17-52), und ein Selbsttest, der sich
einen schlechten Tag dauerhaft merkt (F-R17-53). Und erstmals ist das
Stück nicht nur gelesen, sondern übersetzt: Der Uhr-Prüfstand hat SDK
9.2.0 geholt und die App für alle 99 Manifest-Geräte gebaut — 99
übersetzt, 0 fehlgeschlagen, 0 Warnungen (7.4).

**Die Altbefunde und Posten des Stücks, am Stand Uhr 4.0.0 nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-11 (mittel) | **bestätigt, unverändert:** `_serverUrl()` in `Uploader.mc` prüft `u.find("http") != 0` und setzt sonst `https://` davor — ein eingetragenes `http://…` bleibt `http://`. Bleibt bei der Freigaberunde; die Abhilfe ist eine Zeile (`http://` abweisen oder auf `https://` heben) |
| F-R17-12 (mittel) | **bestätigt:** (1) `serverUrl` steht in `properties.xml` mit Vorgabe `nadoku.gen-em.org` und in `settings.xml` als sichtbare Einstellung (in Garmin Connect änderbar); (2) der Alt-Weg lebt: `deviceId` und `apiKey` stehen als Einstellungen in `settings.xml`, `credentials()` liest sie, sobald `cred` fehlt, und `lokalTrennen()` leert sie — ein Weg, den seit S5 niemand mehr braucht und der Zugangsdaten durch die Begleit-App trägt; (3) der Transport über die Begleit-App ist Plattform, nicht Code. Bleibt bei der Freigaberunde; (2) ist ein Aufräumposten mit Sicherheitsgewinn |
| F-R17-13 (mittel) | **bestätigt:** `Model.mc`, `Track.mc`, `Cpr.mc` schreiben Spur, Phasen und Reanimation im Klartext in den Storage, bis der Uploader sie verschlossen gesendet hat; `cred` ebenso (I-31). Entschieden mit E-R17-09 (Zweck „Server") und als Grenze (1) der Zusage in CLAUDE.md 4 benannt; kein neuer Stand |
| F-R17-28 (niedrig) | **zur Hälfte erledigt:** `parkFristPruefen()` in `Uploader.mc` verwirft geparkte Pakete nach `PARK_FRIST_S` (30 Tage, E-S11-11), aufgerufen aus `syncAll()` — AN-2 ist damit auch für die Garmin erledigt, soweit es Geparkte betrifft. Offen bleibt der Zustand **„abgemeldet"** (401, 403): Dort wird nichts geparkt, alle Pakete bleiben ohne Frist, bis jemand neu koppelt oder trennt — eine verlorene, im Web gelöschte Uhr hält ihren Klartextpuffer unbegrenzt (K-14). Bleibt bei der Freigaberunde mit dieser Hälfte |

**Nur gelesen, kein Befund:** `Pair.mc` zeigt den Kopplungscode erst nach
der Antwort auf `start` und vergisst die Sitzung bei jedem Ausgang
(`_sitzungVergessen()`); ein Rückruf einer älteren Sitzung wird an der
Kennung erkannt und verworfen. `Input.mc` sperrt die Tasten gegen
Fehlbedienung mit Handschuh, `CprView.mc` quittiert jeden Druck. `Track.mc`
schreibt in Chunks zu 200 Punkten (`CHUNK_POINTS`) und schließt den
letzten beim Beenden. `Uploader.mc` prüft vor jedem Senden
`Huelle.hatSchluessel()` und `Huelle.bereit()` und schickt sonst nichts —
der Server würde Klartext ohnehin mit `400 klartext` abweisen. Die
Einsatzhülle entsteht als handgebautes JSON aus Konstanten in `Const.mc`
(Arten der Reanimation, ASCII). `Ui.mc` hält die Markenfarben als
Hexliterale — auf dem Gerät gibt es kein Token, und die Werte sind die aus
`Design.md`. Die Manifest-Berechtigungen sind zwei (`Positioning`,
`Communications`); `Background`, `Fit`, `SensorHistory` und `UserProfile`
fehlen, und das ist richtig so.

**Aufräumposten (kein Befund):**

- **Zwei Funktionen ohne Verwender** (Verwenderprobe über alle 306
  `function`-Namen): `Input.hasUpDown()` und `Input.touchActive()` — je ein
  Treffer, die Definition. Streichen oder begründen.
- **Ein Kopfkommentar aus der Zeit vor dem SDK:** `Util.mc` beginnt mit
  „Erster Wurf, noch nicht gegen das SDK kompiliert". Der Prüfstand
  übersetzt die Datei seit Nr. 13 für 99 Geräte; der Satz ist seit
  Uhr 1 falsch.
- **Die Markenfarben als Hexliterale** in `Ui.mc` (`0xFF8F1F`, `0x4280E5`,
  `0xD63338`) und in `CprMenuView.ITEMS` — unvermeidlich auf dem Gerät,
  aber ohne Herkunft im Kommentar; eine Zeile „= `--orange`, `--blau`,
  `--rot` aus `Design.md` 2" macht die Zahl prüfbar.
- **Zwei sichtbare Einstellungen des Alt-Wegs:** `deviceId` und `apiKey` in
  `settings.xml` und `properties.xml` (F-R17-12 (2)). Nach der
  Entscheidung der Freigaberunde austragen — mit `credentials()` und dem
  Leeren in `lokalTrennen()`.
- **Ein Kommentar, der den Zähler trifft:** In `Uploader.mc` steht
  `makeWebRequest` einmal in Prosa neben dem Aufruf; wer die Netzstellen
  zählt, zählt sieben und findet sechs. Umformulieren.
- **`Math.rand()` ohne Vermerk:** `Util.newRef()` mischt Zähler und
  Zufall zu einer Referenz; ein Kommentar „kein Schlüssel, kein Geheimnis"
  erspart die Frage beim nächsten Review.

#### 7.3.9 Stück 9 — Android (R17-13, 20.10.2026)

**Dateiliste, abgehakt — 135 Dateien, 23 824 Zeilen, jede von Fable
vollständig gelesen:** die 98 Kotlin-Dateien (18 809 Zeilen) in
`handy/src/main/`, `uhr/src/main/`, `gemeinsam/quelle/` und den vier
Prüfsätzen (`src/test/` beider Module, `src/androidTest/` des Handys), die
drei Manifeste (Handy `main`, `debug`, `release`) und das der Uhr,
`netzsicherheit.xml`, `netzwerk_pruefstand.xml`, `datensicherung.xml`
(beide Module), die Ressourcen (`strings.xml`, `themen.xml`, `farben.xml`,
die Vektoren), `robolectric.properties` (beide), `stroeme.txt`, die drei
`build.gradle.kts`, `settings.gradle.kts`, `gradle.properties`,
`libs.versions.toml`, der Wrapper, beide `proguard-rules.pro`,
`version.properties`, `LIESMICH.md` und die fünf Werkzeuge. Die Checkliste
aus 7.2 hat für Android fünf Fragen: Woher kommt der Schlüssel, mit dem das
Handy verschließt; was liegt im Speicher, wie geschützt und wie lange; was
geht über das Netz wohin, und was geschieht bei jeder Antwort; wer hört von
außen zu (`exported`, die Horcher); und was tut der Dienst, wenn das System
oder die Nutzerin ihn beendet. Alle fünf sind an jeder Datei gestellt.
**Anders als in 7.3.2 bis 7.3.8 steht hier keine Tabelle je Datei:** 135
Zeilen trügen zur Lesbarkeit nichts bei. Die Zählung je Datei liegt im
Prüfbericht (`stueck9-tabelle.txt` im Prüfstand), hier die Gruppen:

| Gruppe | Dateien | Zeilen | Verweise | Größte Datei |
|---|---|---|---|---|
| Handy, `src/main/` (mit `debug/`, `release/`) | 48 | 9 114 | 411 | `AufzeichnungsDienst.kt` (1 074) |
| Handy, `src/test/` und `src/androidTest/` | 38 | 6 641 | 144 | `HandyBildTest.kt` (690) |
| Uhr, `src/main/` | 17 | 1 926 | 103 | `UhrActivity.kt` (523) |
| Uhr, `src/test/` | 8 | 1 423 | 48 | `UhrBildTest.kt` (347) |
| `gemeinsam/` (Quelle und Ressourcen) | 8 | 869 | 45 | `Uhrnachricht.kt` (279) |
| Bau: `build.gradle.kts` (3), `settings.gradle.kts`, `gradle.properties`, `libs.versions.toml`, Wrapper (2), `proguard-rules.pro` (2), `version.properties`, `LIESMICH.md` | 11 | 2 660 | 284 | `LIESMICH.md` (1 389) |
| `werkzeuge/` (5 Skripte) | 5 | 1 191 | 40 | `kontraste.py` (456) |
| **Summe** | **135** | **23 824** | **1 075** | |

**Summe 1 075 Verweise.** `LIESMICH.md` trägt 119, `version.properties` 81,
`AufzeichnungsDienst.kt` 49, `UhrActivity.kt` 35 — die App erzählt wie die
Uhr in Blockkommentaren, und die Verweise sind E-S4-, E-S5Z-, E-AR-,
B-S4-/B-S5Z- und Backlog-Nummern.

**Was die Lesung ergeben hat, in einem Absatz.** Die Hülle des Handys tut,
was die der Uhr tut: `senden/Geraetehuelle.kt` prüft den öffentlichen
Schlüssel des Kontos auf der Kurve (P-256, die Kurvengleichung selbst,
nicht nur `KeyFactory`), leitet mit HKDF-SHA256 ab, verschließt mit
AES-256-CBC und bindet mit HMAC-SHA256; `GeraetehuelleTest` hält die sechs
Vektoren der Referenz byte-gleich. Der Tresor (`kopplung/Schluesseltresor.kt`,
`Tresorschluessel.kt`) legt Kennung, Geräteschlüssel und Kontoschlüssel
unter AES-256-GCM aus dem Android-Keystore ab (`setUserAuthenticationRequired(false)`
mit Begründung — ein Dienst, der um 3 Uhr sendet, kann niemanden
entsperren lassen), schreibt atomar über `.neu` und hält die Zugangsdaten
in `toString()` verborgen. Der Netzweg (`Netzweg.kt`) folgt keiner
Umleitung (`instanceFollowRedirects = false`), trennt `SSLException` von
den übrigen Fehlern, und `Serveradresse.kt` lässt im Release nur `https`
zu; `netzsicherheit.xml` verbietet Klartext, das Prüf-APK erlaubt ihn nur
für `127.0.0.1`, `10.0.2.2` und `localhost`. Die Kopplung
(`Kopplungsdienst.kt`) wertet das `error`-Feld der Antwort aus und hält
eine `3xx` für einen Serverfehler, nicht für ein Ziel. Die Annahme von der
Uhr (`uhr/Uhrannahme.kt`) prüft den Absender gegen die verbundenen Knoten
(unlesbare Liste heißt fremd), die Zeit gegen Gegenwart und Dienstfenster
(fünf Minuten Spiel) und quittiert, was sie nicht wirkt, damit die Uhr es
nicht ewig nachliefert; `HandyHorcher` hört nur auf `wear://*/nadoku`. Der
Puffer (`puffer/Puffer.kt`) spricht SQL in 25 Aufrufen, keiner mit
Interpolation, migriert von 1 nach 2 und räumt Abgewiesene nach 30 Tagen
(Nr. 114, 0.14.0). Das Manifest des Handys fragt acht
Berechtigungen und begründet jede, das der Uhr keine; `allowBackup` ist
in beiden aus und die Regeln sind leer; `exported` tragen je Modul die
Startansicht und der Horcher mit Pfadfilter. Der Bau zieht Fremdes nur
zur Bauzeit (`FAIL_ON_PROJECT_REPOS`), der Wrapper trägt seine Prüfsumme,
das Robolectric-Abbild kommt über Gradle und liegt offline unter
`build/robolectric-abbild/` beider Module, und keine Bau-Datei liest eine
Umgebungsvariable (0 Treffer auf `System.getenv` und
`environmentVariable`). Das Nachrichtenformat zwischen Uhr und Handy
trägt sechs Schlüssel, und der Prüffall zählt sie nach — kein `api_key`,
keine `device_id` (E-S4-11). Der eine Eintrag liegt im **Lebenslauf des
Dienstes**, nicht in der Kryptographie: ein Knopf in zwei
Benachrichtigungen, der den Vordergrunddienst beendet und den Dienst im
Puffer offen lässt (F-R17-54). Und das Stück ist nicht nur gelesen, sondern
gebaut und geprüft: `./gradlew --no-daemon lint test` im Container — Handy
280 Prüffälle je Bauart (15 ohne Anlage übersprungen), Uhr 72, 0 rot, Lint
0 Fehler, Bilderlauf 78 und 7 Bilder (7.4).

**Die Altbefunde und Posten des Stücks, am Stand Android 0.18.0
nachgelesen:**

| Befund | Stand 20.10.2026 |
|---|---|
| F-R17-03 (hoch) | **In `android/` kein zweiter Leser:** `System.getenv` und `providers.environmentVariable` kommen in keiner Gradle- oder Kotlin-Datei vor (0 Treffer); `handy/build.gradle.kts` liest `SERVER_BASIS` aus einer `-P`-Eigenschaft mit Vorgabe, beide Module lesen `signatur.properties` als Datei. Was ein Gradle-Lauf an `APK_*` erbt, erbt er aus der Umgebung des Jobs — das ist Stück 10/11 (`appbau.yml`, `ausliefern-lauf.yml`). Bleibt offen bis dahin |
| F-R17-27 (niedrig) | **bestätigt:** `import com.google.android.gms` steht in drei `main`-Dateien — `gemeinsam/…/WearNachrichtenweg.kt` (die Umsetzung der Schnittstelle), `handy/…/uhr/HandyHorcher.kt` und `uhr/…/funk/UhrHorcher.kt` (beide `WearableListenerService`, die Hülle, die das System startet) — und in `handy/src/androidTest/…/DataLayerErreichbarTest.kt`. Alles oberhalb kennt nur `Nachrichtenweg`; die Zusage in `CLAUDE.md` 4 („auf eine Datei begrenzt") ist auf drei zu berichtigen, der Grund steht in F-R17-27 |
| F-R17-31 (Hinweis) | **bestätigt:** `UhrHorcher.onMessageReceived()` schaltet nur nach `ereignis.path` und liest `sourceNodeId` nicht — eine Quittung oder Standmeldung von jedem Knoten gleicher Signatur wird übernommen; `sourceNodeId` kommt im ganzen Baum nur im `HandyHorcher` vor (zwei Stellen) und in der Dokumentation von `Uhrannahme`. Das Handy wertet `400` und `403` in `Netzweg`/`Sendeantwort` getrennt. Bleibt Hinweis: Der Data Layer stellt nur zwischen Apps gleicher Signatur zu, und eine Uhr hat ein Handy |
| AN-5 (R8) | **beantwortet, kein Befund:** `isMinifyEnabled = false` in beiden `build.gradle.kts`, und beide `proguard-rules.pro` sagen warum — rund zwei Dutzend Klassen, kein Gewinn neben Compose, dafür lesbare Stapelauszüge für den Gerätetest (E-R45-7). Die Datei steht als Ort für die Ausnahmen bereit, falls das kippt |

**Nur gelesen, kein Befund:** `AufzeichnungsDienst.kt` hält den Dienst
`START_STICKY`, misst Lücken mit `elapsedRealtime()` statt Systemzeit,
stuft die Ortung über den `Ortungswaechter` in sechs Codes und schickt den
Wechsel ans Handgelenk; `Akkuwaechter` meldet je Stufe einmal und setzt
sich am Kabel zurück. `NachsendeDienst`/`Nachsenden` planen den Job
`setPersisted` und nur bei Netz, `Sender` begrenzt auf 500 Anfragen je Lauf
und halbiert bei `413`. `Kennungen` bilden `wm-`/`u-`-Kennungen aus Zähler
und Zufall ohne Zeitstempel. `Uhrpuffer.kt` schreibt über `.neu` und
umbenennen, `Uhrfunk.kt` merkt vor dem Senden und löscht erst nach der
Quittung, `Uhrsteuerung.kt` lässt den Stand des Handys führen und keine
Standmeldung eine Rückfrage überschreiben; `Uhrbedienung.kt` ist eine reine
Zustandsmaschine mit 30 Fällen in `UhrbedienungTest`. Die Werkzeuge messen,
was sie sagen: `farbabgleich.py` die Token gegen `:root`, `kontraste.py`
die Paare **und** die Vollständigkeit je Rolle (mit Selbstprobe aus den zwei
historischen Fehlern), `stroeme.py` die Soll-Zahlen der Ausdünnung zweimal
unabhängig, `bildmarken.sh` die Herkunft der vier PNG, `emulator.sh` Stufe
II mit jeder Falle als Kommentar. `PendingIntent` kommt siebenmal vor,
siebenmal `FLAG_IMMUTABLE`. `TODO` kommt nicht vor.

**Aufräumposten (kein Befund):**

- **Ein Kommentar, der eine Lücke verdeckt:** „DER KNOPF LÖST DIESELBE
  AKTION AUS wie der in der Dauermeldung" an `akkuwarnungStellen()` und
  „denselben, den die Dauermeldung schon hat" in `version.properties`
  (0.12.0). Die Dauermeldung hat keinen Knopf; `addAction` kommt zweimal
  vor. Geht mit F-R17-54.
- **Der Sekundentakt der Hauptansicht:** `HauptActivity.kt` zählt `takt`
  jede Sekunde hoch, und fünf `remember(takt)` lesen nach — drei davon
  SQLite (`rueckstand()`, `abgewiesen()`, `verwerfbar()`) auf dem
  Hauptfaden. Auf dem S24 unmerklich, auf einem alten Gerät mit vollem
  Puffer ein Ruckeln je Sekunde. Die drei Zahlen ändern sich nur mit einem
  Sendelauf oder einem Verwerfen — einen Beobachter oder einen eigenen,
  langsameren Takt dafür.
- **Ein Altbestand, der liegen bleibt:** `server_basis` in
  `Einstellungen.kt`; der Kommentar sagt es selbst. Eine Zeile `remove()`
  beim Start kostet weniger als der Absatz, der erklärt, warum sie fehlt.
- **„Drei" gegen „zwei":** `libs.versions.toml` sagt im Kopf „die drei
  zugelassenen Fremdbestandteile" und sechs Zeilen tiefer „ZWEI STATT
  VIER"; `PruefServer.kt` spricht von dreien. Es sind zwei (AndroidX/Compose,
  `play-services-wearable`).
- **`Uhrsteuerung.zustand` ohne `@Volatile`:** geschrieben auf der Werkbank
  (`UhrApp.werkbank`), gelesen vom Hauptfaden beim Start der Ansicht
  (`app?.zustand`). Harmlos — der nächste Beobachter-Aufruf bringt den
  frischen Stand —, aber das Handy markiert dieselbe Bauform fünfmal mit
  `@Volatile`. Angleichen.
- **Ein Zeitschlag, der immer zeichnet:** `UhrActivity.kt` schickt jede
  Sekunde `Zeitschlag`, `Uhrsteuerung.ereignis()` setzt danach `zustand`
  über `anzeigeNachziehen()` neu und ruft `beiAenderung` — ein Post auf den
  Hauptfaden und eine Neuzeichnung je Sekunde, auch wenn nichts anders ist.
  `Uhrzustand` ist eine `data class`; ein Vergleich vor dem Setzen spart es.
- **Eine Zeile, die nie geht:** `Uhrpuffer.quittieren()` entfernt nur
  Zeilen, deren `nr` lesbar ist; eine unlesbare Warteschlangenzeile (ein
  halbes Schreiben über den Ersatzweg ohne `renameTo`) bleibt für immer,
  `nachliefern()` sendet sie bei jedem Start, `gepuffert` zählt sie. Der
  Kopf wird bei Unlesbarkeit verworfen (`laden()`), die Zeilen nicht —
  dasselbe dort.
- **Fünf Fassungshinweise von Lint** (wear-compose 1.7.1, Kotlin 2.4.21,
  Robolectric 4.17): kein Posten dieses Reviews, `LIESMICH.md` nennt sie
  seit dem 17.10.2026 — hier nur, damit die Zahl 5 nicht als Befund gelesen
  wird.

#### 7.3.10 Stück 10 — Werkzeuge und Workflows (R17-14, 21.10.2026)

**Was „gelesen" hier heißt — und was nicht.** `git ls-files tools .github`
nennt 437 Dateien. Davon sind **277 Werkzeug** (Code und seine Anleitung:
`.mjs`, `.py`, `.php`, `.sh`, `.yml`, `.mc`, `.jungle`, `.md`, die
Soll-, Ausnahme- und Registerdateien als `.json`/`.md`/`.txt`), **151
Daten** (die 22 Dienstdateien und Schemata des Referenzdatensatzes, die
144 Straßengeometrien unter `routen/`, Fahrzeiten, Stammdaten — 53 381
Zeilen JSON), **2 Fremd** (`tools/bedienprobe/vendor/jsQR.js`, 10 114
Zeilen, und seine Lizenz — die Prüfsumme im Kopfblock stimmt mit der
Datei ohne Kopfblock überein, nachgerechnet; gelesen ist sie nicht, sie
ist Fremdcode mit Herkunft, Lizenz und Hash), **2 Ausgabe**
(`tools/bedienprobe/ausgabe/bericht.{md,json}`, eingecheckt und je Lauf
fortgeschrieben) und **5 Binär** (vier Bilder, ein Zertifikat der
Mailprobe). Die 277 Werkzeugdateien — 82 305 Zeilen — hat Fable
vollständig gelesen, in zwei Sitzungen; die 160 anderen sind
katalogisiert und stichprobenweise aufgeschlagen. Die Frage der
Checkliste (7.2) war für dieses Stück eine andere als für Code der
Anwendung: **Prüft das Werkzeug noch, was der Code heute tut** — oder
einen Stand, den es nicht mehr gibt? Und: **Was fasst es an**
(Konfiguration, Datenbank, Dateien der Anlage), **wie räumt es auf**,
**woher kommen seine Geheimnisse**, und **was lädt es zur Laufzeit von
außen**. Alle vier sind an jeder Datei gestellt. Wie in 7.3.9 steht hier
keine Tabelle je Datei; die Zeilen je Datei liegen im Prüfbericht
(`stueck10-werkzeuge.txt`, 82 582 Zeilen mit Kopfzeilen), hier die
Ordner:

| Ordner | Dateien | Zeilen | Was darin liegt |
|---|---|---|---|
| `tools/proben/` | 65 | 25 459 | 31 Proben des Läufers `proben.sh` (PHP, Python, `.mjs`, `.sh`), `huelle/` mit der Referenz der Gerätehülle |
| `tools/referenzdatensatz/` | 52 | 12 397 | Quelldaten-Prüfung, Generator, Einspiellauf, Browserschritte, Kreislauf und Vergleich |
| `tools/quelltext/` | 29 | 9 928 | die 14 Quelltextprüfungen von Stufe 1 (`bestand.py` allein 2 657 Zeilen) mit Soll- und Ausnahmelisten |
| `tools/bedienprobe/` | 35 | 7 491 | Läufer, Probekonto, 31 Klickwege unter `wege/` |
| `tools/kette/` | 8 | 4 185 | Torwächter, Zielprobe, Freigabe, Baumsuche, APK-Ablage, Appbau |
| `tools/screenshots/` | 6 | 3 393 | Bilderlauf, Stilvergleich der Bilder, Kontrastrechner |
| `tools/messstand/` | 8 | 2 901 | Mengenlauf, Serverprobe, Browserprobe, Vervielfältigung |
| `tools/uhr-pruefstand/` | 18 | 2 479 | SDK-Aufbau, Rundlauf, Krypto-, Eingabe- und Netzprobe (Monkey C) |
| `tools/pruefstand/` | 6 | 2 250 | `pruefen.sh`, Auswahl, Bericht, `pruefablauf.json`, Motorenregister |
| `tools/erzeugen/` | 9 | 1 590 | Erzeuger für Design-Tabellen, Gerätemodelle, Logos, Prüfkonten, Wegwerfdomains |
| `tools/zaehlung/` | 3 | 1 584 | Register der Zentralisierung (44 Zeilen) und sein Zähler |
| `.github/workflows/` | 4 | 1 526 | `pruefung.yml`, `auslieferung.yml`, `ausliefern-lauf.yml`, `integritaet.yml` |
| `tools/integritaetswache/` | 2 | 1 112 | `wache.py` und Anleitung |
| `tools/steuerung/` | 5 | 1 106 | Decken, Nummern, Übersicht, Verschieben der Steuerungsdokumente |
| `tools/stilvergleich/` | 9 | 972 | Stilvergleich der Oberfläche |
| `tools/sandbox/` | 5 | 888 | Aufbau, Hochfahren, Plattform, `konfig_stellen.php` |
| `tools/schemaprobe/` | 2 | 812 | Migration gegen `schema.sql` |
| `tools/spaltenregister/` | 3 | 785 | Register der `missions`-Spalten, Wegprobe |
| `tools/kettenaufrufe/` | 2 | 552 | Schnittstellen der Kette gegen die Werkzeuge |
| `tools/motor.mjs` | 1 | 509 | die eine Stelle für die drei Browser-Engines (R87) |
| `tools/zweitfaktor/` | 5 | 386 | TOTP-Rechner in drei Sprachen, Prüfkonto |
| **Summe** | **277** | **82 305** | |

**Was die Lesung ergeben hat, in einem Absatz.** Die Werkzeuge sind, was
`CLAUDE.md` 6 verspricht: Jedes trägt seinen Anlass (21 Anleitungen mit
Backlog-Nummer, `tools/erzeugen/` in der Erzeugerform, sechs Anleitungen
in Unterordnern ohne — dort verlangt die Regel `anlass` keine), jedes
nennt am Ende seine Zahl, und fast jedes hat eine Gegenprobe gegen sich
selbst — `vergleichen.py` baut 25 Abweichungen ein und prüft, dass die
zehn Gegenproben **nicht** gemeldet werden; `generator/pruefen.py`
rechnet den HKDF-Prüfvektor aus RFC 5869 nach, bevor es damit rechnet;
`lesen.py` prüft die SHA-256 jedes Backup-Teils, bevor es ihn öffnet;
`quelldaten/pruefen.py` liest die Sperrliste der Textprobe statt sie zu
kopieren und ist rot statt still, wenn sie fehlt (PK-04). Die Proben
räumen in `finally` **und** im Shutdown auf (28 `register_shutdown_function`,
158 `finally`), löschen mit Präfix-Riegeln (`umlauf-`, `probe.invalid`,
`messstand@`), und wo eine Probe die Anlage schlimmer verändert als eine
Nutzerin es könnte, sagt sie es im Kopf (`zweitfaktor/probe.php` Teil 8
macht alle anderen BetreiberInnen für zehn Sekunden zu Admins;
`verbindung/probe.php` ändert `max_user_connections` als `root` über den
Unix-Socket; `schluesselwechsel/probe.php` wechselt den Serverschlüssel
und hält seine Stand-Datei mit `umask 0077` „vom ersten Byte an"). Die
Rückwegprobe B7 misst mit jedem Abzugswert der Kontozeile, ob er
`rw_privat` öffnet (0 Erfolge) — das ist die Messung zur Zusage aus
`CLAUDE.md` 4, nicht ihre Behauptung. Die Werkzeuge gegen Staging
(`kreislauf.py`, `rueckweg/probe.mjs`, `sitzung.py`) prüfen das
Zertifikat (`verify = not basis.startswith("https://127.0.0.1")`,
`ignoreHTTPSErrors: lokal`); die 13 mit `ignoreHTTPSErrors: true` laufen
nur gegen die Sandbox. Keine fremde Shell: 43 `subprocess.run`, 0
`shell=True`; PHP-Schnipsel gehen über `execFileSync` mit
`JSON.stringify` oder `php -r` mit `$argv`. Die 15 fremden `uses:`-Zeilen
der Workflows hängen an einer 40-stelligen SHA (15 von 15 — der Handbefehl
aus `CLAUDE.md` 3 bleibt der einzige Prüfer, F-R17-16). **Die drei
Befunde liegen nicht in der Messung, sondern am Rand davon:** eine
Sicherung der Konfiguration, die dort liegt, wo ein Webserver sie
ausliefert (F-R17-55); ein Riegel, dessen Decke nach dem Paket nie
heruntergeschrieben wurde (F-R17-56); ein Token in der Anfrageadresse
(F-R17-57). Und ein Muster, das Nr. 188 seit September benennt, hat in
diesem Stück **vier lebende Beispiele** — Verweise auf Pfade, die es nicht
gibt, und niemand misst sie (unten).

**Die Altbefunde und Posten des Stücks, am Stand vom 21.10.2026
nachgelesen:**

| Befund | Stand 21.10.2026 |
|---|---|
| F-R17-03 (hoch) | **bestätigt:** `auslieferung.yml`, Job `android`, Schritt „Bauen, signieren, Zertifikat prüfen" hält `APK_SPEICHER_B64`, `APK_SPEICHER_PASSWORT`, `APK_SCHLUESSEL_NAME`, `APK_SCHLUESSEL_PASSWORT` im `env:` des Schritts (Zeilen 446 bis 451); `tools/kette/appbau.sh` ruft darin `./gradlew`. Stück 9 hat belegt, dass `android/` selbst keine Umgebungsvariable liest — das Erbe ist das Erbe des Prozesses, und das gilt weiter |
| F-R17-04 (hoch) | **bestätigt:** `auslieferung.yml` `workflow_dispatch` ohne Zweigbindung, `ausliefern-lauf.yml` `environment: ${{ inputs.umgebung }}` (Zeile 61), `concurrency: ausliefern-${{ inputs.umgebung }}`; keine Datei des Baums bindet die Umgebung `produktion` an `main` oder einen Tag — das kann nur das Ruleset der Umgebung (P-R17-04) |
| F-R17-14 (mittel) | **am Code nicht messbar:** Der Schutz des Zweigs `produktion` ist eine GitHub-Einstellung; im Baum steht nur die Deny-Liste in `.claude/settings.json` (unverändert zwei Einträge: `merge_pull_request`, `enable_pr_auto_merge`) und der Job `zeiger` mit `git push --force`. Bleibt bei der Betreiberin (P-R17-04) |
| F-R17-15 (mittel) | **bestätigt:** `tools/integritaetswache/LIESMICH.md` sagt weiter „jede Datei unter `server/`" (Zeile 14), „FTPS-Zugang" (24), Ausnahmen mit `doku/` (32); `wache.py` holt über `urllib` per HTTPS (`hole()`), `assets/` und die Anmeldeseite, ohne Zugangsdaten. Dazu neu gelesen: `wache.py` kennt `--unsicher` (`CERT_NONE`, `check_hostname = False`) — `integritaet.yml` setzt es nicht, und so soll es bleiben; die Wache, die den Produktivserver auf Fremdes prüft, darf nicht selbst jedem Zertifikat glauben |
| F-R17-16 (mittel) | **bestätigt und ergänzt:** `tools/kettenaufrufe/pruefen.py` enthält kein `uses` (0 Treffer), die 15 SHA-Pins zählt nur der Handbefehl; die Ausnahmeliste der Kette (`ausliefern-lauf.yml` Zeile 476 bis 491) misst niemand gegen `.gitignore`. Und die Lücke hat einen Inhalt: `config.php.vor-probe` und `config.php.mailprobe` stehen in keiner oder nur einer der drei Listen — F-R17-55 |
| F-R17-17 (mittel) | **bestätigt:** Jobs `android` (Zeile 419) und `uhr` (498) haben kein `needs:`, rufen weder `freigabe.py` noch `baumsuche.py`; vor dem Bau stehen Tag = Fassung und die Freigabe der Umgebung `produktion`, sonst nichts |
| F-R17-18 (mittel) | **bestätigt:** Der Schritt „Punktdateien gesperrt, `.well-known` offen?" steht in `auslieferung.yml` (Zeile 180) im Job `stufe2` gegen `STAGING_URL`; im Job `produktion` und in `integritaet.yml` gibt es ihn nicht |
| F-R17-20 (mittel) | **bestätigt und verschärft:** `pruefstand.sh` `sdk_holen()` lädt das Connect-IQ-SDK über `sdks.json` ohne Prüfsumme, `bibliotheken()` vier `.deb` von `archive.ubuntu.com` ohne Prüfsumme, entpackt sie mit `dpkg-deb` und lädt sie per `LD_LIBRARY_PATH` in den Simulator — und **derselbe Aufbau läuft im Uhr-Job der Auslieferung** (`auslieferung.yml` Zeile 529, `pruefstand.sh aufbau-uebersetzen`), mit dem das Garmin-Paket entsteht: Die Lieferkette des Pakets, das die Betreiberin auf 99 Uhren bringt, hängt an einem ungepinnten Download. Dazu `npx --yes http-server` in `proben/abmelden/pruefe.mjs` und `proben/frist/pruefe.mjs` (ein npm-Paket zur Laufzeit ohne Fassung, nur örtlich), `pip install` ohne Hash in vier Zeilen, `npm install playwright@1.56` ohne Lock |
| Nr. 188 (Dokumentenprobe, Stand teilweise) | **belegt, mit vier Beispielen aus diesem Stück:** `.gitignore` Zeile 9 nennt `tools/mailprobe/` (heißt `tools/proben/mail/`), Zeile 157 `tools/anteilprobe/ausgabe-*.png` (heißt `tools/proben/anteil/`, und deshalb ist `ausgabe-blatt-druck.png` **nicht** ignoriert — eine Ignorierregel, die ins Leere zeigt, ist keine), `bedienprobe/wege/einstellungen_profil_passkey.mjs` nennt `tools/motoren-ausnahmen.md` (heißt `tools/pruefstand/motoren.md`), `quelltext/zerlegen.py` nennt `wortliste.py` (heißt `textprobe.py`). Keines davon misst ein Werkzeug: `linkprobe.py` liest `<seite>.php?…` in `server/`, die Dokumentenprobe aus Nr. 188 gibt es noch nicht. Der Punkt bleibt bei 12b; der Weg darin (Pfadverweise gegen `git ls-files`) hätte alle vier gefunden |
| Nr. 263 (Wache sieht nur Öffentliches) | **bestätigt, nichts Neues:** `wache.py` `lauf()` vergleicht, was `hole()` per HTTPS liefert — `assets/`, `login.php`; keinen PHP-Quelltext. E-R17-12 hat die Grenze ins Modell geschrieben; der Punkt bleibt, wie er ist |

**Nur gelesen, kein Befund — und absichtlich hier, damit es nicht noch
einmal gelesen werden muss:** `bestand.py` und `kennzeichnung.php` werten
PHP-Literale mit `eval()` über eine Positivliste (nur Literale, `__DIR__`,
`dirname`/`realpath`) — vertretbar, der Kommentar sagt es.
`protokoll/fehlerrouter.php` ruft `eval()` absichtlich 25-mal, als Router
eines eigenen `php -S` auf einem freien Port. `migrationsregister.php`
baut seine Selbstprobe unter einem vorhersagbaren Pfad in
`sys_get_temp_dir()` — zwei Kopien von Repositoriumsdateien, kein
Geheimnis. Die Vorgabe-Passwörter der Prüfkonten (`pruefstandzugang2026`,
`umlaufpruefung2026`, `nadokudemo0815`, `geheim-probe-2026`,
`messstandpruefung2026`, `pruefstandadminsicht2026`, `nadokulokal`)
stehen 70-mal in 41 Dateien — alle für die örtliche Anlage oder das
Demo-Konto, keines für Staging oder Produktiv; `kreislauf.py` sagt es
(„Zugänge einer fernen Anlage kommen nie hierher"). `--admin-totp`,
`--passwort`, `--jobs-token` als Kommandozeilenargumente (35 und 38
Treffer) sind in der Prozessliste des Läufers sichtbar; das ist das
durchgängige Muster, und die Kette gibt die Werte aus `secrets` — auf
dem Läufer liest die Prozessliste niemand sonst. `tools/zaehlung/register.php`
zählt nur `server/` (Bestand 159 PHP, 57 JS): Die 15 `require …config.php`
in Werkzeugen sind regelkonform, auch wenn `ingest/probe.php` ausdrücklich
sagt, warum es seit Web 20.27.0 nicht mehr nötig ist (unten).
`lokal_starten.sh` legt eine eigene CA in den Systemspeicher
(`update-ca-certificates`) — für den Connect-IQ-Simulator, begründet mit
F-S5-11, Sandbox. `rundlauf.sh` des Uhr-Prüfstands schreibt
`pair_sessions.user_id` per SQL und sagt, dass es den Klick ersetzt. Die
`DELETE FROM` ohne `WHERE` (20 in 7 Dateien) treffen Prüf-, Raten- und
Mailtabellen der örtlichen Anlage; `TRUNCATE` kommt nicht vor, `DROP
DATABASE` nur in `lokal_einrichten.sh` mit Warnung im Kopf. `TODO`,
`FIXME`, `XXX` kommen nicht vor. `vulture` meldet 37 Stellen, sechs mit
100 %: zweimal eine Variable `timeout`, die `tor.py` setzt und nicht
liest (Zeilen 581, 622), viermal Parameter einer SFTP-Attrappe in
`versand/gegenstellen.py`, die paramiko verlangt; der Rest sind
Schnittstellen, die paramiko oder `HTMLParser` von außen rufen, und
zwei Funktionen ohne Aufrufer (`ortsdaten.gepackt_bauen`,
`quelldaten/pruefen.ph_zeiten`).

**Aufräumposten (kein Befund):**

- **Zwei Handwerkzeuge messen einen Stand, den es nicht mehr gibt:**
  `referenzdatensatz/einspielen/sichtpruefung.mjs` sucht die Diagnose über
  `diagnose\s*🔒` — das Schloss ist seit S10/AP5 ein SVG
  (`symbol-schutz`, `einsatz.php` Zeilen 241 und 565), und `innerText`
  liefert dafür nichts; `demo_pruefen.mjs` hat dieselbe Falle damals
  berichtigt, `sichtpruefung.mjs` nicht (es meldete „Keine
  Diagnose-Beschriftung", also falsch rot — kein stilles Grün). Und
  `fixture/riegelprobe.php` erwartet in Teil 1 Rückgabe 0 von
  `erzeugen.php`, das seit Web 23 mit Rückgabe 2 abbricht (Nr. 388 sagt
  es); Teil 1 und 2 sind damit dauerhaft rot. Beide ruft kein Prüfstand
  (`motoren.md` „von Hand", Nr. 391) — der Weg aus Nr. 391 („je Werkzeug
  entscheiden: einhängen oder löschen") gilt für beide, und
  `sichtpruefung.mjs` gehört zu Nr. 391 dazugeschrieben.
- **Der feste Containerpfad `/home/user/einsatzdoku-luftrettung` an sieben
  Stellen** als Rückfall oder Konstante: `bedienprobe/wege/admin_protokoll.mjs`,
  `admin_rechtstexte.mjs`, `betrieb_server.mjs`, `p5a-ap8.mjs`
  (`process.env.ED_WURZEL || …`), `proben/anteil/betriebslauf.mjs` und
  `umstellungslauf.mjs` (`WURZEL = …` und `file:///home/user/…/motor.mjs`
  fest), `referenzdatensatz/einspielen/lokal_starten.sh` (`WURZEL=…/server`,
  während `lokal_einrichten.sh` daneben aus dem Skriptpfad rechnet).
  `probekonto.mjs` zeigt den Weg (`HIER`). Auf einem anderen Rechner
  laufen die sieben nur mit `ED_WURZEL`; das sagt keine Anleitung.
- **`tools/proben/anteil/ausgabe-blatt-druck.png`** legt `betriebslauf.mjs`
  bei jedem Lauf ins Repositoriumsverzeichnis, ungetrackt und nicht
  ignoriert (die Regel in `.gitignore` zeigt auf `tools/anteilprobe/`);
  F-RP-13 hat dasselbe für `csp-browser/` nach `/tmp` verlegt — hier
  nicht. Geht mit Nr. 188.
- **Drei Zahlen für die Zahl der Proben:** `proben.sh` sagt im Kopf
  „dreißig", `proben/LIESMICH.md` oben „Neunundzwanzig" und unten
  „`alle` fährt 31"; `RUF` hat 31 Einträge. Eine Zahl, aus der Liste
  gerechnet, oder keine.
- **„Nur Chromium" an drei Stellen, seit R87 falsch:** `bedienprobe/probe.mjs`
  schreibt in jeden Bericht „Nur Chromium; WebKit und Gecko stehen im
  Prüfstand nicht zur Verfügung" — und so steht es im eingecheckten
  `ausgabe/bericht.md`, obwohl die Probe seit AP3b in drei Engines läuft;
  `screenshots/aufnehmen.mjs` Kopf („GRENZEN. Gemessen wird Chromium",
  „acht Breiten" — es sind zehn); `stilvergleich/gegen.sh` („geht an
  `motor.mjs` vorbei" — `stilvergleich.js` ruft `starten()`).
- **Eingecheckte Ausgaben gegen ihren Kommentar:** `probe.mjs` sagt,
  `ausgabe/` stehe in `.gitignore`; dort steht nur `ausgabe-*/`, und
  `bericht.{md,json}` sind seit PK-04 im Baum (die LIESMICH beruft sich
  darauf). Entscheiden — tracken mit Satz oder ignorieren —, dann den
  Kommentar angleichen. `einspielen/messprotokoll.{json,md}` sind
  dieselbe Bauart, dort gewollt (Vorarbeit R19).
- **Zehn Proben binden `config.php` noch von Hand ein**
  (`jobs`, `kopplung`, `spur`, `komplett`, `wartung`, `wartungslauf`,
  `freigabe/vorbereiten.php`, `messstand/messen.py`, `serverprobe.py`,
  `zaehlen.php` und drei weitere Nennungen in Kommentaren — 18 Treffer in
  15 Dateien), während `ingest/probe.php` sagt, warum es seit Web 20.27.0
  nicht mehr nötig ist (`db.php` bringt `konfig_lib.php` mit). Das
  Register zählt `tools/` nicht; eine Zeile `require` weniger je Probe.
- **`ignoreHTTPSErrors: true` fest in 13 Browserwerkzeugen, an die
  Adresse gebunden in 14** (`lokal = /^https:\/\/(127\.0\.0\.1|localhost)/`);
  `passwort_setzen.mjs` begründet die Bindung („ein Skript, das sie
  pauschal abschaltet, prüft irgendwann die falsche Gegenstelle"). Die 13
  laufen heute nur gegen die Sandbox; angleichen, bevor eines davon
  gegen Staging gefahren wird.
- **Doppelte und verunglückte Kommentare:** `papierkorb_misch.mjs` trägt
  den Block „DIE VORAUSSETZUNG DES ZIELKONTOS PRUEFEN (S2/AP10)" zweimal
  fast wortgleich; `komplett/probe.php` prüft
  `str_contains(…, 'Komplett-Backup der Installation') || str_contains(…,
  dieselbe Zeichenkette)` — der Kommentar (F-S7-04) beschreibt zwei
  Schreibweisen, es steht eine; `kopplung/rundlauf.mjs` sagt „PBKDF2 mit
  320 000 Runden" (600 000 seit Nr. 155; `klickweg.mjs` hat es berichtigt);
  `uhr-bilder.sh` „tools/erzeugen/ (logos)LIESMICH.md" und `schemaprobe`
  „tools/quelltext/ (migrationsregister)"; `zerlegen.py` nennt
  `wortliste.py` und `--probe`; `textprobe-ausnahmen.json` Regel
  `update-migrationen` „Entfallen mit R11 … P6".
- **Zwei Stellen, die die Sandbox-Texte doppelt halten:** `FORMAT.md`
  Zeile 196 bis 198 und `wegpunkte.py` Zeile 8 bis 10 sagen beide,
  Trackpunkte und Phasenkoordinaten lägen „im Klartext (`track_points`,
  `mission_phases`)" — seit Web 22 verschlossen, seit Web 23 ohne diese
  Tabellen. Das ist Stück 11 (Doku gegen Code); hier vorgemerkt.
- **Aufräumen, das den Vorwert nicht kennt:** `mail/probe.php` setzt
  `instanz_name`, `instanz_kontakt`, `betrieb_mail` am Ende auf `''`,
  `raten/probe.php` die Marken `RATE_K_MAIL_MARK`, `RATE_K_BREMSE_ST`
  ebenso und löscht in `rate_limits`/`sicherheit_ereignisse` alle Zeilen
  der Töpfe `global`/`health`, nicht nur eigene; `versand/probe.php`
  schaltet fremde Ziele aus und merkt sich die Liste nur im `finally`,
  nicht im Shutdown. Örtliche Anlage; wer dort arbeitet, merkt es.
- **`pkill -f "monkeydo"` in `pruefstand.sh` `starten()`** — der Kopf von
  `kryptoprobe` erklärt selbst, warum das jede Shell mit dem Wort in der
  Befehlszeile trifft, und beendet über die PID; `starten()` und
  `bildreihe` nicht.
- **Kleines:** `stilvergleich.js` mit `/opt/pw-browsers/chromium-1194/…`
  fest (die Fassungsnummer altert); `screenshots/LIESMICH.md` „15 von 15"
  gegen 17 Fälle der Selbstprobe; `vergleichen.py` (screenshots) `--werte`
  zweimal (argparse und `sys.argv`); `zaehlen.php` `$leer = str_repeat(…)`
  sofort überschrieben; `verschieben.py` hält das Status-Vokabular als
  zweite Stelle neben `uebersicht.py`; `versand/probe.php` Teil 4
  `pruef(…, true, 'BEFUND…')` als immer grüne Dokumentationszeile;
  `tor.py` Zeilen 581 und 622 `timeout` gesetzt und nicht gelesen;
  `__import__("os")` statt `import os` in `einspielen.py` und
  `serverprobe.py`; `abmelden/pruefe.mjs` ruft `motorWahl` vor dem
  `import` (ESM-Hoisting macht es gültig).

#### 7.3.11 Stück 11 — Doku gegen Code (R17-15, 21.10.2026)

**Was „gelesen" hier heißt.** Die 19 Dokumente der Zeile in 7.3 — 33 903
Zeilen — hat Fable aus einem Dump mit Zeilennummern vollständig gelesen,
Abschnitt für Abschnitt, und **je prüfbare Aussage** sofort gegen den Code
gehalten: eine Zahl gegen `grep -c` oder `ls | wc -l`, eine Konstante
gegen ihre Definition, eine Liste gegen das Register oder den Katalog, ein
„seit Web …" gegen die Datei. Die Frage der Checkliste (7.2) war hier:
**Beschreibt der Satz den Code vom 21.10.2026** (Web 23.0.2, Uhr 4.0.0,
Android 0.18.0) — oder einen Stand, den es nicht mehr gibt? Und bei den
Zusagen: **Sagt das Dokument dasselbe wie `CLAUDE.md` 4 und das Modell?**
Nicht noch einmal gelesen sind die 28 `LIESMICH.md` der Werkzeuge (Stück
10, 7.3.10) und die drei Vorlagen unter `docs/rechtstexte/` (803 Zeilen) —
die sind juristische Texte der Betreiberin, hier nur per `grep` auf
technische Aussagen durchsucht (Klartext, Fristen, Fassungen); was sie
sagen, steht in F-R17-58. Die Notizen je Dokument liegen im Prüfbericht
(`stueck11-notizen.md`); hier die Dokumente:

| Dokument | Zeilen | Was darin steht — und was die Lesung dagegen gehalten hat |
|---|---|---|
| `docs/Technik.md` | 14 125 | Architektur, Datenmodell, Abläufe 4.97–4.99s, Uhr, Android, Plattform, Kopfzeilen, Kette, Runbook; gegen Konstanten, Register, Workflows, `.htaccess`, Ratenschutz, Jobkatalog, Rollenmatrix, Migrationskatalog gehalten |
| `docs/Handbuch.md` | 6 540 | Bedienung aller Rollen; Fristen, Grenzen, Töpfe, Codes gegen `db.php`, `ratelimit_lib.php`, `totp_lib.php`, `passkey_lib.php`, `demo_lib.php`, `trash_lib.php`, `protokoll_lib.php`, `mail_lib.php`, `kopfzeilen_lib.php` |
| `docs/Design.md` | 3 408 | Richtlinie; Token-, Schwellen-, Symbol- und Bausteintabellen sind erzeugt (`bestand` Regel `design`), Breiten und Aufrufzahlen nachgezählt |
| `docs/Backup-Format.md` | 2 207 | Nutzlast 13, Fenster, Konto-Backup-Fassung 3, Archivformat, Schlüsselwechsel; gegen `backup_lib.php`, `backup_restore.php`, `einstellungen.php`, `komplett_lib.php` |
| `android/LIESMICH.md` | 1 389 | Bauanleitung, Entscheidungen, Prüfstand; gegen `build.gradle.kts`, `libs.versions.toml`, Manifest, Quelltext |
| `docs/Pruefablauf.md` | 1 109 | die eine Stelle für Prüfmittel; Riegelliste gegen `pruefablauf.json`, Schrittzahlen gegen `pruefung.yml`, Regeln gegen `bestand.py` |
| `docs/JSON-Vertrag.md` | 1 033 | Geräteschnittstelle; Fehlerschlüssel, Grenzen, Hüllenform gegen `ingest.php`, `pair.php`, `validate_lib.php`, `Const.mc` |
| `docs/Export-Format.md` | 964 | drei Profile, Schranke A9, GPX-`creator`; gegen `export.js`, `export_data.php`, `import_profiles.js` |
| `CLAUDE.md` | 642 | die Zusagen (Abschnitt 4), die Kette (3), das Prüfen (6); gegen Code, Workflows, `register.php`, `spur_lib.php` |
| `docs/Lizenzen.md` | 610 | Fremdbestandteile mit Prüfsummen; jede SHA-256 gegen die Datei, Kartenebenen gegen `map_layers.js`, Wegwerfliste gegen `wegwerfdomains.txt` |
| `docs/Sandbox-Setup.md` | 540 | Container, Ausbaustufen, acht Umgebungswerte; Abbildfassungen gegen `php -v`, `python3 -V`, `docker -v`, `nproc`, `free` |
| `docs/Bedrohungsmodell.md` | 312 | das Modell aus Schritt 12; gegen Kette (SHAs, Ausnahmeliste), Töpfe, Backlog Nr. 358 |
| `docs/Geraete-Eingabe.md` | 271 | Messungen je Gerät, Weg „Gerät ergänzen"; gegen `manifest.xml` (99 Geräte), `monkey.jungle`, Ressourcenordner |
| `docs/Uhr-Layout_Regeln.md` | 260 | Schriften, runde Displays, Blöcke; gegen `Ui.mc` (`NUM_VIS_PCT`, `s()`), Geräteliste |
| `docs/store/garmin/LIESMICH.md` | 173 | Store-Eintrag; Gerätezahl, Fassung, Texte gegen Manifest und `Const.mc` |
| `README.md` | 94 | Einstieg und Zusage-Kasten; gegen `CLAUDE.md` 4, Manifest, `tools/quelltext/pruefen.sh` |
| `server/assets/images/symbole/LIESMICH.md` | 79 | 58 Symbole, Herkunft; gegen `ls` und `Lizenzen.md` |
| `docs/Was-ist-NAdoku.md` | 76 | die öffentliche Seite (`ueber.php`); Klartextliste gegen `CLAUDE.md` 4 |
| `server/vendor/HERKUNFT.md` | 71 | Vendor-Bestand; 338 Prüfsummen gegen `sha256sum -c`, Verwender von phpseclib gegen `grep` |
| **Summe** | **33 903** | 19 Dokumente, jede Zeile von Fable gelesen; dazu `docs/rechtstexte/` (803 Zeilen, drei Vorlagen) nur auf technische Aussagen durchsucht und die 28 `LIESMICH.md` der Werkzeuge (Stück 10) nicht noch einmal |

**Was die Lesung ergeben hat, in einem Absatz.** Die Dokumentation ist an
den Stellen richtig, an denen ein Werkzeug sie hält: Das Jobregister
(`tools/quelltext/jobregister.php`) misst 11 Jobs und 18 Aufräumschritte
gegen `Technik.md` 4.97a, die Regel `design` hält die vier erzeugten
Tabellen in `Design.md`, die Rollenprobe liest ihre 118 Zeilen aus
`Technik.md` 4.99p, `register.php` seine 44 Zeilen aus `CLAUDE.md` 4, die
Riegelliste in `Pruefablauf.md` 4 ist wortgleich mit `pruefablauf.json`
(21 Proben), und jede Prüfsumme in `Lizenzen.md` und `HERKUNFT.md` stimmt
(338 Dateien unter `server/vendor/`, Parsedown, `gpx11.xsd`, die
Wegwerfliste mit 8 883 Domains). **Die Konstanten stimmen.** Rund 120
Zahlen aus Handbuch und Technik sind einzeln nachgemessen — Passwortlänge
12, Sitzung 1 800 s, Kopplungscode 6 aus 32 mit 10 Minuten, `MAX_GERAETE`
5, Papierkorb 90 Tage, Demo-Reset 1 800 s, die Töpfe `login` 10/900,
`login_ip` 50, `totp` 5/900, `pair` 10/600, `pair_start` 20, `pair_code`
10, `reg` 10/h, `regg` 100/h, `regz` 3/24 h, `blatt` 3, `health` 60/min,
`csp` 200/h, `demo` 20/h, `demog` 300/h, `kontopaar` 5/h, `testmail`
3/h; die Mail-Leiter 300/1 800/7 200/28 800/86 400, `MAIL_JE_LAUF` 10,
`MAIL_BUDGET_S` 5, `MAIL_MINDEST_S` 1,5; die Jobbudgets 300/20/3 s,
`JOB_ANFRAGE_PAUSE_S` 300, `JOB_SPERRE_VERFALL_S` 3 600, `JOB_PAUSE_MAX_S`
7 200, `KOMP_LAUF_MAX_S` 120, `JOB_WAISEN_BLOCK` 2 000; die Protokollfristen
365 (90 bis 1 095), das Archiv 7 Tage (1 bis 31) und 365 (90 bis 1 095);
`INGEST_ERSETZFENSTER_H` 72, `DT_UEBERLAPPUNG_MIN` 15, `PAIR_SITZUNGEN_MAX`
1 000, `LIMIT_TRACKPUNKTE_ANFRAGE` 2 000 und `_SPUR` 50 000,
`UPLOAD_CHUNK_POINTS` 500, `PAT_BLOB` 40 bis 60 000, `LIMIT_PHASEN` 500,
`LIMIT_SCHNITTE` 200, `GH_MIN_BYTE` 130, `GH_EINSATZ_MAX_BYTE` 262 144,
`SPUR_TOL` 2/3 m, `SPUR_DP_ABSCHNITT_MAX` 1 000, `KDF_ITER_ZIEL` 600 000,
`KDF_ITER_LISTE` [600 000, 320 000], `TOTP_HALB_FRIST_S` 300, acht Zeichen
je Code, `ZF_FRISCH_S` 900 mit acht Handlungen, `PK_HOECHSTENS` 10,
`PK_FELD_MAX` 32 KiB, `PK_ANTWORT_MAX` 192 KiB, `POW_BITS` 16,
`REG_MINDESTDAUER` 0,5 s, `REG_FORMULAR_GILT_S` 7 200,
`KONTEN_UNBESTAETIGT_H` 48, `TOKEN_EINLADUNG_S` 86 400, `TOKEN_RESET_S`
3 600, `SW_NACHZUEGLER_S` 600, `KOPF_HSTS_STUFEN` [1, 7, 365],
`INSTANZ_MAX` 80, Ankündigung 190 Byte, `EDBAK_FENSTER` 500 und
`FENSTER` 250 im Browser, `TEIL_PUNKTE` 100 000, `GEOCODER` 400 ms / 3
Zeichen / 6 Treffer, `SD_HILFE_AB` 6, `NUM_VIS_PCT` 78, `PARK_FRIST_S` 30
Tage, `DISPLAY_MAX_POINTS` 1 000, `ERSTFIX_MS` 120 000,
`DIENSTDAUER_ERINNERUNG_H` 26, `WARTUNG_AUSNAHMEN` 18,
`JSON_SKRIPTE_AUSSERHALB_API` 4, `KOMP_OHNE_ZEILEN` 2, `RESUS_LABELS` 10
(Uhr 9, ohne `beginn`), compileSdk/targetSdk 37, minSdk 26/30 — keine
davon falsch. Die `.htaccess` trägt beide Regeln aus 4.97g, die
Ausnahmeliste der Kette acht Pfade in elf Zeilen (`.sitzungen/` darunter),
`gen-em.org` kommt unter `server/` nicht vor, `komoot` nicht unter
`assets/`, die CSP hat `worker-src 'self' blob:` und `report-to` nur unter
Bedingung, wie 5c sagt. **Falsch ist, was niemand zählt** — und das ist
die eine Lehre dieses Stücks: Überall dort, wo ein Satz eine Zahl trägt,
die ein Verzeichnis, eine Liste oder ein Katalog **wäre** (Proben,
Symbole, Umgebungswerte, Katalogeinträge, Schritte, Aktionen, Töpfe),
und wo ein Absatz einen Umbau beschreibt, den der nächste Umbau überholt
hat (die Klartextliste, die Rückwärtskompatibilität der Sicherung, die
Signatur einer Funktion), steht ein Stand von vor S11, PK-08 oder R87.
Das sind die beiden Befunde: **F-R17-58** — vier öffentliche Texte und
die AVV-Vorlage beschreiben Spur, Phasenorte, Transportziel und
Reanimation als Klartext, die Datenschutzerklärung daneben richtig als
verschlüsselt —, und **F-R17-59**, der Sammelbefund II mit rund fünfzig
Stellen in zwölf Dokumenten, von denen `Technik.md` allein die Hälfte
trägt, einschließlich seiner Gliederung.

**Die Altbefunde des Stücks, am Stand vom 21.10.2026 nachgelesen:**

| Befund | Stand 21.10.2026 |
|---|---|
| F-R17-09 (mittel) | **bestätigt:** Keines der 19 Dokumente sagt, dass die getippte Einsatzadresse und die gewählte Koordinate der geschützten Felder im Klartext an `photon.komoot.io` gehen. `Technik.md` 4.98a beschreibt den Adressdienst („genau einen Zugang", drei Grenzen, `grep komoot = 0 ist eine Eigenschaft") ohne den Satz; `Handbuch.md` nennt die Adresssuche als Einstellung; der Textbaustein zur Adresssuche (Verwaltung → Rechtstexte) und die AVV-Vorlage Anlage IV (Zeile 185: „Übermittelt werden die getippte Adresse bzw. die Koordinate, nicht …") sind die zwei Stellen, die es sagen — in der Datenschutzerklärung der Betreiberin, nicht in der Zusage von `CLAUDE.md` 4 oder `Technik.md` 4.98. Bleibt, wie in 2.3a beschrieben |
| F-R17-25 (niedrig) | **bestätigt:** Die Klartextliste in `CLAUDE.md` 4 nennt `bw_info`, `other_ema`, Schockraum, Winde, Stammdaten-Koordinaten und die Altbestand-Spalte `missions.notes` weiter nicht; `Technik.md` 4.98 nennt „Schockraum, Besatzung, Einsatzmittel, Diensttag- und Standortdaten" und deckt die Klasse. Dazu aus dieser Lesung: `Handbuch.md` 3095 zählt `Transportziele` und den `Verlauf einer Reanimation` zu den „Betriebsangaben" ohne Haken (F-R17-59) — dieselbe Liste, in der dritten Fassung |
| F-R17-26 (niedrig) | **bestätigt, unverändert:** Der Kopf von `serverkrypto_lib.php` nennt die Protokollarchive, nicht die TOTP-Geheimnisse; `config.example.php` keines von beiden. `Technik.md` 4.97c hat die Tabelle der sechs Zwecke (mit `totp` seit SR-03, F-SR-01) — am Kopf der Datei steht sie nicht |
| F-R17-29 (niedrig) | **zum Teil noch offen:** Von den genannten Stellen stehen `Technik.md` 6.4 Schritt 2 („auf **diesem Commit**", Zeile 11947), 6.5 („`.sitzungen/` wird der achte Pfad — und steht noch nicht darin", „zwei Zeilen, in **beiden** Schritten", 11989 ff. — gemessen: `ausliefern-lauf.yml` 497 f., ein Schritt), 6.1 („Alle zwölf fremden `uses:`-Zeilen", 11434 — 15) und `Pruefablauf.md` 206 („`Stufe 1` mit 15" — 16) **noch so da**; 6.1 „keine App-Tags" ist berichtigt (die Tabelle nennt `android-v*` und `uhr-v*`, der Satz „Bis PK-08 stand hier …" steht daneben), 4.99b Nr. 2 „bis Paket C" steht noch (die Uhr fragt seit Uhr 3.0.0). F-R17-59 nennt nur Neues und verweist für diese auf F-R17-29; Stück 12 bündelt beide |
| F-R17-30 (Hinweis) | **bestätigt:** `docs/konzepte/Review-Krypto-Sicherheit.md` (Stand Web 14.2.2) ist nicht im Umfang dieses Stücks — kein normatives Dokument — und wurde nicht gelesen; die Lesung hat an keiner Stelle gefunden, dass ein lebendes Dokument sie als Quelle für einen heutigen Stand zitiert (`grep "Review-Krypto"` in `docs/*.md`: nur Verweise auf Funde K-1 bis K-15 als Herkunft, mit Backlog-Nummer). Bleibt Historie, wie 2.3 sagt |

**Nur gelesen, kein Befund — und absichtlich hier, damit es nicht noch
einmal gelesen werden muss:** `Handbuch.md` trägt an jeder Stelle, die
diese Lesung geprüft hat, die richtigen Fristen und Grenzen (die 30
Minuten des Demo-Resets, die 90 Tage des Papierkorbs, die zehn Codes zu
acht Zeichen, die zehn Passkeys je Adresse, die fünf Geräte, die 72
Stunden, die 365/90/1 095 Tage des Protokolls, die 30 Tage der Selbstlöschung,
die 48 Stunden der Registrierung); was dort falsch ist, ist die
Rückschau (F-R17-58, -59), nicht der Betrieb. `Design.md` stimmt überall,
wo die Tabellen erzeugt sind, und auch in den Breiten (zehn, `ALLE_BREITEN`)
und Sollhöhen (44 px / 36 px, `(hover:hover) and (pointer:fine) and
(min-width:1024px)`); die eine Zahl daneben (115 Aufrufstellen) ist von
Hand. `JSON-Vertrag.md` und `Export-Format.md` stimmen in jedem
Fehlerschlüssel, jeder Grenze und jedem Feldnamen, die geprüft wurden
(`klartext`, `ortsdaten`, `zu_viele_versuche`, `device_limit`,
`session_ende`, `max_body_bytes` 512 KB, 25 IDs je `track`, 5 000 Einsätze
je Export, `creator` mit Fassung); `Backup-Format.md` in Nutzlast 13,
`NUTZLAST_HOECHSTENS`, `EDBAK_FENSTER`, `bak-` + 12 Hex, dem Archivformat
7 und dem Schlüsselwechsel. `Bedrohungsmodell.md` nennt Nr. 358 richtig
(die zweite Passwortseite), die Lage K mit dem Job-Token (F-R17-57 ist
der Sender zum Log-Befund F-R17-22, dort steht beides), und seine Zahl
„14 Aktionen" ist die einzige, die altert. `Lizenzen.md` 6.1 führt die
vier Kartenebenen, die `map_layers.js` hat, und `kopfzeilen_lib.php` 183
die vier Kacheldomains — `Technik.md` 5c sagt „vier", und so ist es.
`Geraete-Eingabe.md` beschreibt in 5 den Weg, ein Gerät zu **messen**,
bevor es ins Manifest kommt; das Manifest trägt 99 Geräte aus
`geraeteklassen.py --bloecke`, gemessen sind drei — das ist kein
Widerspruch, sondern die Regel aus Uhr-Layout 1 („Klassen statt Geräte",
E-S11-30 misst den Selbsttest je Fassung und Firmware); die Lesung lässt
es stehen. `Sandbox-Setup.md` 6.1 nennt eine Falle mit Python 3.11 gegen
3.12, die mit 3.13 im Abbild gegenstandslos ist, und 5.3 die 310 000
Runden einer Messung von vor Nr. 155 — beides steht in F-R17-59. Der
Store-Eintrag (`docs/store/garmin/LIESMICH.md`) nennt 99 Geräte, Uhr
4.0.0 und die drei Vorzeigegeräte richtig; er sagt nicht, ob er
eingereicht ist — das misst kein Werkzeug (Prüfdokument 1).
`symbole/LIESMICH.md` führt 58 Zeichen, die `Lizenzen.md` 5 ebenso, nur
`Technik.md` 2 sagt 55.

**Aufräumposten (kein Befund):**

- **Zwei Stand-Zeilen, die nichts mehr sagen:** `Technik.md` „Stand:
  20.09.2026" (seither 31 Fassungen), `Handbuch.md` „Stand: 06.09.2026".
  Entweder pflegen — mit dem Paket, das die Datei anfasst — oder streichen;
  eine Stand-Zeile, die niemand nachzieht, ist eine Behauptung über das
  Alter des Textes, und sie ist falsch. Die Dokumentenprobe (Nr. 188)
  könnte sie gegen `git log -1 --format=%cs -- <datei>` halten.
- **Zeilennummern als Fundstellen** in `Technik.md` 4.99b (`pair.php`
  318–343, `db.php` 686, `ratelimit_lib.php` 79/80/260–265, `schema.sql`
  439, `ingest.php` 74–81, `kopplung_lib.php` 33–36/39–43/135–145) — sie
  altern mit jedem Commit (die Töpfe stehen heute in Zeile 323 ff.). Die
  Regel des Reviews (Funktionsnamen, E-R17-14) gilt auch für die Doku.
- **Drei Sätze in `Technik.md` 4, die eine Fassung beschreiben, die es nicht
  mehr gibt:** `EdUnlock.ensureContentKey(wrap, kdfSalt)` (drei Parameter
  seit der Rundenzahl je Konto), „ein `<datalist>` mit `suggest_src`"
  (seit 15.7.0 `EdVorschlaege`, 4.98a sagt es selbst), `ingest.php:150`
  als Zeilenverweis.
- **`server/vendor/HERKUNFT.md` „Wozu"** sagt, phpseclib werde nur für den
  SFTP-Adapter geladen; `passkey_lib.php`, `rueckweg_lib.php` und
  `kontopaar_lib.php` laden `Crypt/EC`, `Crypt/RSA` und `PublicKeyLoader`
  ebenso (F-R17-59 nennt es; hier als Posten für die Datei).
- **`android/LIESMICH.md` Statuskasten** trägt den Stand von Arbeitspaket
  C2 (Anfang September); der Kasten gehört entweder auf den heutigen
  Stand (0.18.0, S11-09) oder aus der Datei — ein Statuskasten in einer
  Bauanleitung altert schneller als alles andere darin.
- **`Handbuch.md` 6 Rückwärtskompatibilität** sagt dreimal „ältere
  Backups weiter einspielbar" (2638, 2715, 2733) und einmal „seit Web 23
  nur ab 22.5.0" (2675); nach dem Paket zu F-R17-59 bleibt der eine Satz,
  und er steht einmal.
- **Die Regel von `Technik.md` 3 zu `rate_limits`** („hier steht ihre
  Zahl absichtlich nicht mehr") verdient dieselbe Anwendung an den
  Stellen, die F-R17-59 nennt — Proben, Symbole, Umgebungswerte,
  Katalogeinträge, Schritte, Aktionen, Töpfe. Eine Zahl, die ein `ls`
  oder ein `count()` liefert, steht nicht im Fließtext; wo sie bleiben
  soll, mit dem Befehl daneben, der sie nachzählt (so hält es `CLAUDE.md`
  3 bei den `uses:`-Zeilen).
- **`CLAUDE.md` 6 „die sieben Umgebungswerte"** gegen `Sandbox-Setup.md`
  4 „acht" — `CLAUDE.md` ist Stück 11 und steht in F-R17-59; hier als
  Posten, weil die Datei bei jeder Sitzung geladen wird.

#### 7.3.12 Stück 12 — Zusammenführung (R17-16, 21.10.2026)

**Was dieses Stück ist — und was nicht.** Kein Code, keine Lesung, kein
neuer Befund: Das Stück nimmt, was die Stücke 1 bis 11 geschrieben haben
— 59 Befunde in 2.3 und 2.3a, fünf Posten in 3.4, zehn Backlog-Punkte mit
Ziel 12b, die Aufräumposten in 7.3.2 bis 7.3.11 — und bringt es in die
Form, die die Freigaberunde braucht: **eine Liste, in der jede Nummer ein
Paket hat** (a), die **Posten je Codebasis** gebündelt (c), den
**Kommentardurchgang als Zahl** (d), einen **Paketschnitt als Entwurf**
mit Modell je Paket (e) und die **R5-Ausnahmeliste** für P7 (f). Der
Schnitt ist ein Vorschlag, keine Entscheidung: Verbindlich wird er mit
R17-17, nach den Antworten der Betreiberin (E-PV-5 (8)); bis dahin
heißen die Pakete A bis K, ihre Nummern (R17-18 ff.) bekommen sie dann.
Fundstellen stehen hier als Funktions- und Dateinamen, nicht als Zeilen
(E-R17-14); die ausführliche Begründung je Befund steht in 2.3 und 2.3a
und wird hier nicht wiederholt.

**(a) Die Fundliste — 59 Befunde nach Grad, jede mit Paket.** Der Grad
ist der aus 2.3 und 2.3a; „Stück" nennt, wo der Befund entstand (1 =
Bestandsaufnahme R17-01) und wo er nachgelesen ist. Die Spalte „Paket"
verweist auf (e); „Betreiberin" heißt: kein Code, eine Zuarbeit aus
Rahmenplan 6.1 oder ein Prüfpunkt im Prüfdokument; „—" heißt: erledigt
oder ins Modell übernommen, kein Paket.

| Nr. | Grad | Stück | Was | Vorschlag, kurz | Paket |
|---|---|---|---|---|---|
| F-R17-01 | **hoch** | 1, 2 | Die Zusage „ein Datenbankabzug allein genügt nicht mehr" gilt nicht für die bcrypt-Token: Offline-Raten ohne `config.php` bleibt möglich | Zusage in `CLAUDE.md` 4 und `Technik.md` 3 berichtigen; Pfeffer vor bcrypt (`HMAC(konto_anteil, token)`) in `login.php`, `pw_handling.php`, `kdf_upgrade.php`, stille Umstellung beim nächsten Anmelden | A |
| F-R17-02 | **hoch** | 1, 5 | Freigabe einer Sicherung durch die Verwaltung an ein beliebiges Konto — ohne Protokoll, Mail, frischen Code | nur an das Herkunftskonto oder dieselbe Adresse; `protokoll()`, Mail an beide, `freigeben` in `ZF_FRISCH_HANDLUNGEN`; Rollenprobe | A |
| F-R17-03 | **hoch** | 1, 9, 10 | Gradle läuft im Auslieferungsjob mit den `APK_*`-Geheimnissen in der Umgebung | Bauen in einem Schritt ohne `APK_*` (oder `env -i` mit Positivliste), Signieren danach; `gradle/verification-metadata.xml`; Doku und Selbstprobe von `appbau.sh` | F |
| F-R17-04 | **hoch** | 1, 10 | Umgebung `produktion` ohne Zweig- oder Tag-Bindung; `workflow_dispatch` ohne Bindung | Deployment-Branch-Regel: nur `web-v*`, `android-v*`, `uhr-v*` und `main`; dasselbe für `staging` | Betreiberin (P-R17-04) |
| F-R17-05 | **hoch** | 1, 6 | Wiederanlaufpaket, Schlüsselblatt, Probe-Wiederherstellung auf Produktiv offen | die vier Zeilen in Rahmenplan 6.1 abarbeiten, mit Datum; Komplett-Stand auf Wegwerf-Webspace einspielen | Betreiberin (Rahmenplan 6.1) |
| F-R17-06 | mittel | 1, 5 | Adresswechsel durch die Verwaltung ohne Bestätigung; die Reset-Kette übernimmt damit ein Konto | Bestätigung an die alte Adresse oder Karenz; Setz-Link nur an bestätigte Adressen; frischer Code für beides; Satz in `Technik.md` 3 | A |
| F-R17-07 | mittel | 1, 5 | Ein Admin kann ein zweites BetreiberIn-Konto sperren, löschen, umadressieren (Nr. 371, E-R17-11) | Riegel in `admin_user.php` und `auth_guard.php`: Ziel mit Rolle `betreiberin` nur für BetreiberIn; Rollenprobe; 4.99p | A |
| F-R17-08 | mittel | 1, 5 | Klartext-Dump der ganzen Datenbank (`art=klar`) ohne frischen Code | `klar` in `ZF_FRISCH_HANDLUNGEN`; `Technik.md` 4.97d | A |
| F-R17-09 | mittel | 1, 4, 7, 11 | Kein Dokument sagt, dass getippte Adresse und gewählte Koordinate an Photon gehen | ein Satz in `CLAUDE.md` 4 und `Technik.md` 4.98 (Lage G), mit dem Schalter | J |
| F-R17-10 | mittel | 1, 2 | Der Inhaltsschlüssel wechselt nie; ein Passwortwechsel schließt keinen Abfluss | Restrisiko im Modell 5; Mail bei `schluessel_erneuern`; Umschlüsselung des Bestands ist S11-Material | A (Mail), J (Modell) |
| F-R17-11 | mittel | 1, 8 | `_serverUrl()` lässt `http://` als Serveradresse stehen | eine Zeile: abweisen oder auf `https://` heben | D |
| F-R17-12 | mittel | 1, 8 | Alt-Weg `deviceId`, `apiKey` als sichtbare Einstellungen; Serveradresse in Garmin Connect änderbar | Alt-Weg austragen (`settings.xml`, `properties.xml`, `credentials()`, `lokalTrennen()`); Adresse nur noch in der Kopplung setzen; Lage M | D |
| F-R17-13 | mittel | 1, 8, 9 | Klartextpuffer auf Uhr und Handy bis zum Senden (Lage H) | entschieden (E-R17-09, Zweck „Server"); Grenze (1) der Zusage in `CLAUDE.md` 4 | — |
| F-R17-14 | mittel | 1, 10 | Zweig `produktion` ohne Ruleset; `push_files` und `actions_run_trigger` nicht in der Deny-Liste | Ruleset: nur die Kette schiebt; die zwei Werkzeuge in `.claude/settings.json` | Betreiberin (P-R17-04); F (Deny-Liste) |
| F-R17-15 | mittel | 1, 10 | Die Anleitung der Integritätswache beschreibt eine andere Wache; kein Runbook-Abschnitt „rot"; Alarmweg unklar | `LIESMICH.md` neu, Abschnitt in `Technik.md` 7, Alarmweg festlegen | F |
| F-R17-16 | mittel | 1, 10 | Niemand misst die `uses:`-SHAs und die Ausnahmeliste der Kette gegen `.gitignore` | Riegel in Stufe 1: `uses:` ohne 40-Hex-SHA rot; Ausnahmeliste gegen `.gitignore` und eine Sollliste rot; `kettenaufrufe/LIESMICH.md` | F |
| F-R17-17 | mittel | 1, 10 | App-Tags laufen ohne Stufe-1-Tor | `freigabe.py` und `baumsuche.py` vor die Jobs `android` und `uhr`; bis dahin `CLAUDE.md` 3 eingrenzen | F |
| F-R17-18 | mittel | 1, 10 | Die Punktdateien-Probe läuft nur gegen Staging | drei lesende `GET` gegen Produktiv (Erwartung 403) in der Wache oder nach dem Abgleich | F |
| F-R17-19 | mittel | 1, 6 | `wiederherstellen.php` ruft `edbak_ablage_bereit()` nicht; der Dump-Name im Runbook | Aufruf zuerst; Runbook 7 Schritt 4 und 8 | B |
| F-R17-20 | mittel | 1, 10 | Ungepinnte Downloads (pip, npm, Connect-IQ-SDK, vier `.deb`) — auch im Uhr-Job der Auslieferung | Hashes und Lock, SDK-Prüfsumme, Gradle-Verifikation (mit F-R17-03) | F |
| F-R17-32 | mittel | 2, 3 | `kdf_upgrade.php` prüft das alte Token ohne Topf; drei Geschwister in `einstellungen.php` | EINE Funktion `passwort_nachweis()` mit `rate_erlaubt('login')` und `rate_misserfolg()`, vier Aufrufer | A |
| F-R17-36 | mittel | 3 | Zehn Fehlversuche von einem Anschluss sperren Adresse und Konto — eine Klinik sperrt sich aus; widerspricht der Doku | ausdrückliche Merkmalsliste in `login.php` (Muster `ingest.php`); Ratenprobe und `Technik.md` 5e.2 | C |
| F-R17-39 | mittel | 4 | `einsatz.php` liest `n_gespeichert` (seit Web 23 fort): GPX-Abruf fehlt an jeder gepackten Spur, der Reiter System füllt sich | `spur_zahlen()` für alle Stufen; die GPX-Probe an einer gepackten Spur | C |
| F-R17-52 | mittel | 8 | Die Uhr entfernt das bestätigte Paket über einen Listenindex, der während der Netzanfrage veralten kann | über die Referenz entfernen, wie `verwerfen()`; Rundlauf-Fall | D |
| F-R17-54 | mittel | 9 | „Dienst beenden" in zwei Benachrichtigungen beendet nur den Vordergrunddienst; der Dienst bleibt im Puffer offen | `klammer.beenden()` zuerst im `BEENDEN`-Zweig; Prüffall; die zwei Kommentare dazu | E |
| F-R17-55 | mittel | 10 | `konfig_stellen()` legt eine Kopie der ganzen `config.php` als `.vor-probe` ins Webverzeichnis | Sicherung außerhalb `server/` (`sys_get_temp_dir()`, `umask 0077`) oder in alle drei Listen; P-R17-12 | F |
| F-R17-58 | mittel | 11 | Vier öffentliche Texte und die AVV-Vorlage nennen Spur, Phasenorte, Transportziel und Reanimation als Klartext | ein Paket auf den Wortlaut von `CLAUDE.md` 4; neue Fassung beider Rechtstexte mit Ankündigung; Textprobe-Falle; P-R17-13 | J |
| F-R17-21 | niedrig | 1, 6 | `mail_konto_entfernen()` wird nie gerufen; Empfänger überleben das Konto 30 Tage | Aufruf in `konto_loeschen()`; Probe | A |
| F-R17-22 | niedrig | 1, 3, 10 | Tokens in Anfrageadressen (Jobs, Health, erste Abrufe der Links) stehen im Zugriffslog des Hosters | Jobs- und Health-Token als Kopfzeile oder Rumpf (mit F-R17-57); Logdauer in die Betriebsakte | F |
| F-R17-23 | niedrig | 1, 3 | CSP Report-Only und HSTS ein Tag als Vorgabe | Stand auf Produktiv abfragen; Vorgabe nach der Probezeit scharf | Betreiberin (P-R17-04); C (Vorgabe) |
| F-R17-24 | niedrig | 1, 7 | `edk` bis zum Tabende im `sessionStorage`; Zeile in `Technik.md` 4.99 falsch | Zeile berichtigen; `edkvor` räumt sich (bestätigt); Rest mit Nr. 370 | H |
| F-R17-25 | niedrig | 1, 11 | Die Klartextliste in `CLAUDE.md` 4 ist unvollständig | auf `Technik.md` 4.98 verweisen statt wiederholen | J |
| F-R17-26 | niedrig | 1, 2, 11 | Kopf `serverkrypto_lib.php` und `config.example.php` nennen nicht alle Zwecke des Serverschlüssels | die sechs Zwecke aus 4.97c an beide Stellen | J |
| F-R17-27 | niedrig | 1, 9 | Play-Dienste in drei Dateien, nicht in einer | Satz in `Lizenzen.md` 6a und `CLAUDE.md` 4: Senden hinter der Schnittstelle, Empfang in zwei Horchern | J |
| F-R17-28 | niedrig | 1, 8 | Uhr „abgemeldet": alle Pakete ohne Frist (K-14); geparkte haben seit Uhr 4.0.0 eine | Frist auch für „abgemeldet", oder der Satz im Handbuch, dass Trennen im Web auf der Uhr nichts löscht | D |
| F-R17-29 | niedrig | 1, 11 | Sammelbefund I veralteter Stellen (6.1, 6.4, 6.5 in `Technik.md`, `Pruefablauf.md` 206, AVV Anlage III …) | mit F-R17-59 EIN Dokumentationspaket | J |
| F-R17-33 | niedrig | 2 | Zwei wortgleiche Zeichenketten in `wh_auspacken()`; ein Dump von vor S7 gilt als fremd | zweite Zeichenkette auf „Komplettsicherung der Installation", oder am v1.0-Schnitt (R60) kürzen | B |
| F-R17-34 | niedrig | 2 | Stammdaten aus dem Backup gehen ohne Prüfschicht in die Datenbank | `pruef_text()`, `pruef_breite()`, `pruef_laenge()` in `edbak_restore()`, vier Zeilen | B |
| F-R17-37 | niedrig | 3 | Der Notzugang zählt nur die Adresse; eine bcrypt-Rechnung je Anfrage ohne Anschlussgrenze | zweites Merkmal `rate_merkmal_ip()`, `rate_global_misserfolg()` im Fehlzweig | C |
| F-R17-38 | niedrig | 4 | `use` mit zwei nicht existierenden Variablen: zwei Warnungen je Umdatierung | zwei Wörter streichen | C |
| F-R17-40 | niedrig | 4 | Die Papierkorb-Sperre des Imports fragt das Datum statt den Tag | Sperre nur, wenn kein aktiver Tag; Fall in der Importprobe | C |
| F-R17-43 | niedrig | 5 | Neue Wiederherstellungscodes ohne frischen Code | `zf_codes_neu` in `ZF_FRISCH_HANDLUNGEN`, `zweitfaktor_frisch_verlangen()`, Mail | A |
| F-R17-44 | niedrig | 5 | `zeitraum.php?m=13` besteht die Formprüfung: Warnung, Titel ohne Monat | dieselbe Prüfung wie `api/range.php`, eine Zeile | C |
| F-R17-45 | niedrig | 5 | CSV-Export der Statistik ohne Formelschutz am Gerätenamen | Entschärfer in `format_lib.php` (der Browser-Export hat ihn) | C |
| F-R17-46 | niedrig | 6 | „Relais ohne Anmeldung" als Zusage, die `smtp_send()` nicht einlöst | `AUTH LOGIN` nur mit `user`; `smtp_probe()` misst den Fall | C |
| F-R17-47 | niedrig | 6 | `wartung.lock` und `ueberlast.json` ohne Anmeldung abrufbar, mit dem Namen der Schaltenden | beide in die `FilesMatch`-Liste; `wer` auf die Kontonummer; P-R17-10 | C |
| F-R17-49 | niedrig | 6 | Zwei Meldungen mit Tags in maskiertem Text — die dritte Stelle dieser Art | Texte ohne Tags, Hervorhebung über `$auftakt`; Riegel in Stufe 1 | C |
| F-R17-51 | niedrig | 7 | `openlocationcode.js` ohne Herkunft, nicht in `Lizenzen.md` | nach `vendor/`, Kopf mit Paket und SHA-256, Zeile in `Lizenzen.md` 3 | G |
| F-R17-53 | niedrig | 8 | Der Selbsttest der Hülle merkt sich „kaputt" dauerhaft | nur `be` und `le` ablegen; `kpruef` in `lokalTrennen()` | D |
| F-R17-56 | niedrig | 10 | Decke Z17 nach AP5 nicht heruntergeschrieben; Z22 | Decken setzen; Selbstprobe in `zaehlen.php` gegen eine Decke über dem Ist | F |
| F-R17-57 | niedrig | 10 | Das Job-Token geht als `GET`-Parameter über die Leitung | `POST` oder Kopfzeile in `tor.py`, `jobs.php` (mit F-R17-22) | F |
| F-R17-59 | niedrig | 11 | Sammelbefund II: rund 50 Stellen in zwölf Dokumenten; die Gliederung von `Technik.md` | Dokumentationspaket mit der Regel aus `Technik.md` 3; Dokumentenprobe (Nr. 188) um zwei Fälle | J |
| F-R17-30 | Hinweis | 1 | Der Krypto-Review von Web 14.2.2 als Historie | gelöscht mit R17-04 (E-R17-13); 3.4 hält den Stand | — |
| F-R17-31 | Hinweis | 1 | Sammelhinweis: Anteil an jeden Link, Bootstrap in jeder Seite, Wear ohne Absenderprüfung, Metadaten | im Modell (R17-02); Wear-Absender bleibt Hinweis (7.3.9) | — |
| F-R17-35 | Hinweis | 2 | Der Fassung-2-Rückfall gilt je Teil, nicht je Paket | ab Fassung 3 abschalten, `edbak_teil_oeffnen()` bekommt die Fassung; zwei Zeilen | B |
| F-R17-41 | Hinweis | 4 | Die Kennungsprüfung im Import geht an der Zweigentscheidung vorbei | Prüfung an dieselbe Bedingung wie der Zweig | C |
| F-R17-42 | Hinweis | 5 | `DEMO_GESPERRT` vergisst drei Handlungen | drei Handlungen in die Liste; Rollenprobe | A |
| F-R17-48 | Hinweis | 6 | `ueberlast_erkannt()` Weg 1 ohne 1226; drei Listen statt einer | Muster aus `UEBERLAST_CODES` bauen | C |
| F-R17-50 | Hinweis | 7 | Die Prüfsumme von `leaflet.css` ist wegen der Zeilenenden auf keinem Klon nachrechenbar | `-text` in `.gitattributes` oder Kopf auf die LF-Summe mit Grund | G |

**Sofortpaket: keins.** E-R17-06 hat es für Schritt 12 so entschieden,
und die Stücke 2 bis 11 haben keinen Befund „kritisch" gebracht: Keiner
der 59 öffnet fremde Daten oder verliert eigene ohne eine erbeutete
Sitzung, eine veränderte App oder einen Angreifer, der schon den Server
beschreibt. Was **sofort** ansteht, kostet keinen Code — drei Prüfpunkte
der Betreiberin (P-R17-04: GitHub-Umgebungen und Produktiv-Einstellungen;
P-R17-12: keine `config.php`-Kopie auf Staging und Produktiv; P-R17-13:
die vier öffentlichen Texte) und die vier Zeilen zu F-R17-05 in
Rahmenplan 6.1. Will die Freigaberunde dennoch ein vorgezogenes Paket,
ist **F-R17-58** der Kandidat: ein öffentlicher Vertragstext, der seit
vier Monaten das Gegenteil der Datenschutzerklärung daneben sagt, ohne
Kryptographie, in einem Nachmittag umsetzbar (Paket J, Teil 1).

**(b) Die Posten aus 3.4 und die zehn Backlog-Punkte.** Die fünf Posten
des Krypto-Reviews, die 12b noch zu lesen hatte, sind gelesen und in 3.4
nachgetragen: K-3 (7.3.2), K-7 und K-15 (7.3.3), K-10 gegenstandslos
(7.3.4), AN-5 (7.3.9) — kein Befund aus ihnen, einer (K-7) hat F-R17-32
verschärft. Die zehn Backlog-Punkte mit Ziel 12b:

| Nr. | Was | Stand nach den Stücken | Paket |
|---|---|---|---|
| 21 | die 43 A4-Kandidaten sichten (Quellliste existiert nicht mehr) | **neu erhoben:** 24 Karteileichen mit Gegenprobe, Liste in (c) | K |
| 77 | `update.php` — die Adresse existiert noch (Weiche, Notausgang) | gelesen (7.3.6): 302 auf `betrieb_updates.php`, `php update.php` bleibt; die Frage ist, ob die Weiche bleibt oder nur die Kommandozeile | C (nach Antwort der Freigaberunde) |
| 188 | Dokumentenprobe — kein Mittel misst Verweise zwischen Dokumenten | belegt (7.3.10, vier tote Pfade) und erweitert (7.3.11: Zahl vor Verzeichnisnamen, `Stand:`-Zeilen) | J (das Werkzeug zum Dokumentationspaket) |
| 263 | die Wache sieht nur Öffentliches | bestätigt (7.3.10); Grenze im Modell (E-R17-12) | — (Freigaberunde: schließen oder als Restrisiko stehen lassen) |
| 341 | sechs Stylesheet-Regeln ohne Verwender, Fokus-Zweig | gemessen (7.3.7): alle sechs ohne Verwender, keine vergessen | K (Browser), `Design.md` mitziehen |
| 370 | Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey` (E-R17-08) | Notiz in 7.3.2: vier Verwenderdateien, elf Nennungen, `schluesselfach` als Vorbild | H |
| 371 | Admin darf BetreiberIn-Konten nicht anfassen (E-R17-11) | bestätigt (7.3.5), drei Handlungen laufen durch | A (mit F-R17-07) |
| 380 | Seitenaufbau: 15 bis 22 Skripte je Seite | gezählt (7.3.7): 20, 19, 13 auf den großen Seiten, 7 im Gerüst | I |
| 381 | Suche und Zeitraum entschlüsseln alles vor den 200 | bestätigt (7.3.5), Weg steht (E-S11-27) | I |
| 382 | erste Antwort von Startseite und Anmelden | nur gelesen (7.3.7): hängt an PHP, nicht an den Skripten; zuerst nachmessen | I |

Nr. 380 bis 382 sind keine Befunde des Reviews, sondern Messungen aus
S11-13 mit dem Vermerk „unmittelbar nach S11 (Betreiberin)"; ob sie in
P6 laufen oder nach P7, ist eine Frage der Freigaberunde — hier stehen
sie als Paket I, damit sie einen Platz haben.

**(c) Aufräumposten je Codebasis.** Die Posten stehen ausführlich in
7.3.2 bis 7.3.11; hier gebündelt nach dem, was ein Paket anfasst. Die
**Karteileichen** sind die neuen A4-Kandidaten (Nr. 21) — jede mit
Gegenprobe (0 Nennungen in `server/`, `tools/`, `docs/` außer der
Definition, bei JavaScript 0 Verwender außerhalb der Datei); ob eine
weg kann oder einen Verwender bekommt, entscheidet das Paket je Stelle,
und bei `api/`, Export und Backup reicht „niemand liest es" nicht.

- **Server-PHP** (Stücke 2 bis 6): **sechs Karteileichen** —
  `serverschluessel_zeile()`, `sitzung_bindung_loeschen()`,
  `adresse_vormerkung_loeschen()`, `protokoll_frist_verwaltung_setzen()`,
  `protokoll_fehler_quittieren()`, `doku_bestand()` (dazu
  `mail_konto_entfernen()`, die F-R17-21 nicht streicht, sondern ruft).
  **Tote Zweige seit Web 23:** Vollausgabe in `edbak_build()`, letztes
  `return` in `komp_schub_lauf()`, `if (!$sdBases)` in der Standortseite,
  der `stufe_bis`-Rückfall in `job_aufraeumen_schritte()`. **Am Register
  vorbei** (Stück 10 hat die Decke geprüft, die Zeilen halten sie): sieben
  direkte `beginTransaction()` (`kdf_upgrade.php`, `pair.php`,
  `ingest.php` mit Grund, `diensttag_zusammenfuehren.php`, `api/day.php`,
  `api/import_commit.php`), `information_schema` von Hand in
  `nachbearbeitung_lib.php`. **Kleinposten:** die tote Zelle in
  `diensttag_loeschen.php`, das ungenutzte `use` in `einsatz_form.php`,
  `Math.min(last + 1, 10)` statt 9, der doppelte Abruf von
  `api/mission.php` beim Speichern, `erststart_erledigt()` vor dem
  `INSERT`, drei Handgriffe ohne `rowCount()`, drei veraltete `@return`,
  `migrationen_inhalt_zaehlen()` mit `<> ''` auf Zahlen, der Tagesvergleich
  in zwei Zeitzonen in `jobs_einen_lauf()`, „Mengen je Konto" über alle
  Konten, `mail_katalog()` ohne `link` in der Pflichtliste, die zweite
  Schreibweise in `rate_konto_freigeben()`. **Kommentare, die der Code
  überholt hat:** `instanz_namen_setzen()`, „PHP-7-lesbar" in
  `install.php`, `stand_am DATE` in der Nachlesefassung der
  Rechtstext-Migration, der Kommentarwiderspruch in `ratelimit_lib.php`
  (mit F-R17-36). **Fassungsgeschichte:** `version.php` trägt 814 der
  6 532 Verweise — das ist der Changelog in Kommentarform und gehört zu
  P7 (R15), nicht zum Kommentardurchgang.
- **Browser** (Stück 7): **14 Exporte ohne Verwender** (`EdGeocoder.dienst`,
  `.host`, `.MINDESTZEICHEN`; `EdGpx.STUFE_DUENN`; `EdLuftlinie.text`,
  `.FARBE`; `EdPat.hinweisUnlesbar`, `.markeUnlesbar`;
  `EdRueckweg.paarErzeugen`, `.paarSenden`; `EdVorschlaege.hervor`;
  `EdWartungslauf.lauf`, `.ausduennen`, `.ausduennungPruefen` — die drei
  des Wartungslaufs sind Kandidaten für eine Probe, nicht fürs Streichen);
  **sechs Stylesheet-Regeln ohne Verwender** (Nr. 341); der Hexwert in
  `luftlinie.js`, drei Pixelwerte unter einer Eigenschaftsausnahme, zwei
  Abschnitte „26" im Stylesheet, die stillen 400/403 in `rueckfrage.js`
  und `schluesselblatt.js`, die Entität in `hervor()`, der
  `csrf`-Parameter in `schluessel.js`; drei Kommentare älter als der Code
  (`patient.js`, `map_fullscreen.js`, `missiontable.js`).
- **Uhr** (Stück 8): **zwei Funktionen ohne Verwender** (`Input.hasUpDown()`,
  `Input.touchActive()`); der Alt-Weg `deviceId`, `apiKey` (mit F-R17-12);
  der Kopfkommentar „noch nicht gegen das SDK kompiliert" in `Util.mc`;
  die Markenfarben als Hexliterale ohne Herkunft in `Ui.mc` und
  `CprMenuView`; der Prosa-Treffer `makeWebRequest` in `Uploader.mc`;
  `Math.rand()` ohne Vermerk in `Util.newRef()`.
- **Android** (Stück 9): der Kommentar „derselbe Knopf" (mit F-R17-54);
  der Sekundentakt mit drei SQLite-Abfragen in `HauptActivity.kt`;
  `server_basis` in `Einstellungen.kt`; „drei" gegen „zwei"
  Fremdbestandteile in `libs.versions.toml` und `PruefServer.kt`;
  `Uhrsteuerung.zustand` ohne `@Volatile`; der Zeitschlag, der immer
  zeichnet; die unlesbare Warteschlangenzeile in `Uhrpuffer.quittieren()`.
  `version.properties` trägt 81 der 956 Verweise — Fassungsgeschichte wie
  `version.php`.
- **Werkzeuge und Kette** (Stück 10): **zwei Funktionen ohne Aufrufer**
  (`ortsdaten.gepackt_bauen`, `quelldaten/pruefen.ph_zeiten`); zwei
  Handwerkzeuge, die einen Stand messen, den es nicht gibt
  (`sichtpruefung.mjs`, `fixture/riegelprobe.php` — Nr. 391); der feste
  Containerpfad an sieben Stellen; `ausgabe-blatt-druck.png` ungetrackt
  und nicht ignoriert (Nr. 188); drei Zahlen für die Probenzahl; „Nur
  Chromium" an drei Stellen seit R87 falsch; eingecheckte Ausgaben gegen
  ihren Kommentar; zehn Proben mit `config.php` von Hand;
  `ignoreHTTPSErrors: true` fest in 13 Werkzeugen; doppelte und
  verunglückte Kommentare (sechs Stellen); Aufräumen ohne Vorwert in drei
  Proben; `pkill -f "monkeydo"`; das Kleine aus 7.3.10. **Und ein
  Vorbehalt für den Kommentardurchgang:** 84 Werkzeugdateien tragen die
  Zeile „Anlass: Nr. …", und die **muss** bleiben (Grundsatz 5,
  `Pruefablauf.md` 1) — der Durchgang in `tools/` nimmt Nummern aus den
  Köpfen, nicht die Anlass-Zeile.
- **Dokumentation** (Stück 11, dazu aus Stück 10): die Posten aus 7.3.11
  (Stand-Zeilen, Zeilennummern in 4.99b, drei Sätze in `Technik.md` 4,
  HERKUNFT „Wozu", der Statuskasten in `android/LIESMICH.md`, Handbuch 6,
  `CLAUDE.md` 6 „sieben"), `FORMAT.md` und `wegpunkte.py` aus 7.3.10 —
  alle im Dokumentationspaket J, zusammen mit F-R17-29, -58, -59.

**(d) Kommentardurchgang — die Zahl je Codebasis.** R69 verlangt: keine
Verweise auf Beschlüsse, Backlog-Nummern, Fassungen und Konzepte mehr im
Code (R13 und R31 gehen darin auf). Die Stücke 2 bis 9 haben je Datei
gezählt, mit leicht verschiedenen Mustern und Dateimengen (7.3.9 zählte
`LIESMICH.md` mit, 7.3.8 ein engeres Muster); hier ist **einmal, mit dem
weitesten Muster der Stücke** (7.3.9: Paketkürzel, E-/F-/Q-/P-/R-/B-/AN-
Nummern, „bis/seit Web N", Fassungen von Web, Uhr und Android, „Nr. NNN",
„Schritt N", AP-Nummern) über die Codedateien jeder Codebasis gezählt
(`git ls-files` nach Endung; keine Markdown-Dateien, keine Daten, kein
`vendor/`):

| Codebasis | Dateien | Zeilen | Verweise | Die drei größten |
|---|---|---|---|---|
| Server-PHP (`server/`, ohne `assets/`, `vendor/`, `demo/`, `doku/`) | 164 | 101 298 | 6 532 | `version.php` 814, `einstellungen.php` 395, `backup_lib.php` 240 |
| Browser (`assets/*.js`, `style.css`) | 58 | 22 597 | 971 | `style.css` 278, `export.js` 89, `missiontable.js` 72 |
| Uhr (`watch/`) | 39 | 5 650 | 133 | `Pair.mc` 46, `Uploader.mc` 20, `Huelle.mc` 16 |
| Android (`android/`, ohne `LIESMICH.md`) | 134 | 22 435 | 956 | `version.properties` 81, `AufzeichnungsDienst.kt` 49, `UhrActivity.kt` 35 |
| Werkzeuge und Kette (`tools/`, `.github/`, ohne `jsQR.js`) | 218 | 75 751 | 3 263 | `wiederherstellung/probe.php` 168, `register.php` 157, `schluesselwechsel/probe.php` 144 |
| **Summe** | **613** | **227 731** | **11 855** | |

Drei Dinge zur Zahl. **Erstens:** 895 davon stehen in `version.php` und
`version.properties` — das ist die Fassungsgeschichte, deren Form P7
neu bestimmt (Changelog neu, R15); der Kommentardurchgang fasst sie nicht
an. Ohne sie bleiben **10 960**. **Zweitens:** In `tools/` ist ein Teil
der Verweise der **Anlass** (84 Dateien), und der bleibt; wie viele der
3 263 das sind, zählt das Paket beim Durchgang selbst. **Drittens:** Die
Zahl ist ein Maß für den Umfang, kein Prüfwert — das Muster trifft
Fachbegriffe (`E9` kann eine Entscheidung sein oder ein Gerätename), und
es verfehlt Prosa ohne Nummer („seit der Umstellung auf Diensttage").
Das Paket erzeugt die Zeilenliste je Datei selbst (7.2) und nennt am
Ende, wie viele Verweise es entfernt hat, neben dieser Zahl.

**(e) Der Paketschnitt — Entwurf.** Elf Pakete, geschnitten nach dem,
was ein Paket anfasst (eine Codebasis, ein Bereich), und nach dem
Modell (K8, E-PV-5 (7): Kryptographie, Schlüsselwege und die Geheimnisse
der Kette mit **Fable**, alles andere mit **Opus**). Die Reihenfolge
ist ein Vorschlag: zuerst, was öffentlich falsch ist oder hoch wiegt,
dann die Pakete mit Fable, zuletzt das Aufräumen — es fasst dieselben
Dateien noch einmal an und soll nicht vor den Korrekturen laufen. Jedes
Paket stuft die Zählung seiner Codebasis hoch, schreibt Changelog und
Doku und endet mit dem Prüfstand der passenden Stufe; die Zeilen in
Abschnitt 4 entstehen mit R17-17. Fächerung (E-ZE-25): seriell, außer wo
die Zeile es sagt.

| Paket | Inhalt | Modell | Umfang | Fächerung | Reihe |
|---|---|---|---|---|---|
| **A — Konten, Nachweise und Freigabe** | F-R17-01, -02, -06, -07 (Nr. 371), -08, -10 (Mail), -21, -32, -42, -43; `login.php`, `pw_handling.php`, `kdf_upgrade.php`, `admin_user.php`, `einstellungen.php` (Nachweise), `adminbackup_lib.php`, `konto_lib.php`, `db.php` (`ZF_FRISCH_HANDLUNGEN`); Rollenprobe und Ratenprobe erweitern | **Fable** | 10 Befunde, 2 hoch; etwa acht Dateien; eine stille Umstellung der Token beim Anmelden | seriell | 2 |
| **B — Sicherung und Wiederherstellung** | F-R17-19, -33, -34, -35; `wiederherstellen.php`, `backup_lib.php`, `adminbackup_lib.php`; Runbook 7 | **Fable** | 4 Befunde, 1 mittel; kleine Zeilen | seriell | 5 |
| **C — Server-Verhalten** | F-R17-36, -37, -38, -39, -40, -41, -44, -45, -46, -47, -48, -49; Nr. 77 (nach Antwort), F-R17-23 (Vorgabe); `ratelimit_lib.php`, `login.php` (Merkmale), `zweitfaktor_notweg.php`, `einsatz.php`, `tageszuordnung_lib.php`, `import_commit.php`, `zeitraum.php`, `betrieb_statistik.php`, `format_lib.php`, `smtp.php`, `.htaccess`, `wartung_lib.php`, `ui.php`; Riegel „kein Tag in `ui_meldung()`"; Proben: Raten, GPX an gepackter Spur, Import | Opus | 12 Befunde, 2 mittel; jede Stelle eine bis drei Zeilen, dazu drei Probenfälle | seriell | 4 |
| **D — Uhr** | F-R17-11, -12, -28, -52, -53; Aufräumposten Uhr (c); `Uploader.mc`, `Huelle.mc`, `Pair.mc`, `settings.xml`, `properties.xml`, `Util.mc`, `Ui.mc`; Rundlauf-Fall für F-R17-52 | **Fable** | 5 Befunde, 3 mittel; Uhr-Prüfstand Stufe I und Rundlauf | seriell | 3 |
| **E — Android** | F-R17-54; Aufräumposten Android (c); `AufzeichnungsDienst.kt`, `HauptActivity.kt`, `Einstellungen.kt`, `Uhrsteuerung.kt`, `Uhrpuffer.kt`, `libs.versions.toml`; Prüffall für den Knopf | Opus | 1 Befund mittel, 7 Posten; Gradle `lint test`, Emulator nach `Pruefablauf.md` 6.9 | seriell | 6 |
| **F — Kette und Werkzeuge** | F-R17-03, -14 (Deny-Liste), -15, -16, -17, -18, -20, -22, -55, -56, -57; `auslieferung.yml`, `ausliefern-lauf.yml`, `pruefung.yml`, `integritaet.yml`, `appbau.sh`, `tor.py`, `jobs.php`, `api/health.php`, `konfig_stellen.php`, `register.php`, `zaehlen.php`, `pruefstand.sh`, `wache.py` und ihre Anleitung; zwei neue Riegel in Stufe 1 | **Fable** | 11 Befunde, 1 hoch, 7 mittel; vier Workflows und acht Werkzeuge; Staging-Probelauf der Kette | seriell | 1 |
| **G — Browser-Herkunft** | F-R17-50, -51; `.gitattributes`, `leaflet.css`, `openlocationcode.js` nach `vendor/`, `Lizenzen.md` 3 | Opus | 2 Einträge, klein | seriell | 8 |
| **H — Inhaltsschlüssel als `CryptoKey`** | Nr. 370 (E-R17-08), F-R17-24; `crypto.js`, `keyguard.js`, `unlock.js` und die vier Verwenderdateien von `getContentKey()`; `Technik.md` 4.99 | **Fable** | 1 Umbau mit elf Nennungen; Bedienprobe und Krypto-Proben | seriell | 7 |
| **I — Seitenaufbau und Mengen** | Nr. 380, 381, 382 (E-S11-27, -32, -33); `suche.php`, `zeitraum.php`, Skriptladung je Seite, Messstand vorher und nachher | Opus | drei Messposten, einer davon „zuerst nachmessen"; Eichlauf (F-S11-25) | seriell | 11 (oder nach P7 — Frage der Freigaberunde) |
| **J — Dokumentation und Zusagen** | F-R17-58 (Teil 1: die vier Texte und die AVV, neue Fassung mit Ankündigung, Textprobe-Falle), F-R17-59 und -29 (Teil 2: rund 60 Stellen, `Technik.md` neu gliedern), F-R17-09, -10 (Modell), -25, -26, -27; Nr. 188 (Dokumentenprobe mit Pfadverweisen, Zahl vor Verzeichnisnamen, `Stand:`-Zeilen); Aufräumposten Dokumentation (c) | Opus | zwölf Dokumente, zwei Rechtstexte, ein Werkzeug; keine Codeänderung außer der Textprobe-Falle und der Dokumentenprobe | seriell (erzählender Text) | 1 (Teil 1), 9 (Teil 2) |
| **K — Aufräumen und Kommentardurchgang je Codebasis** | die Karteileichen und Posten aus (c) für Server, Browser (mit Nr. 341), Werkzeuge; der Kommentardurchgang (d) in allen fünf Codebasen, ohne `version.php`, `version.properties`, Anlass-Zeilen; Nr. 21 schließen | Opus | 24 Karteileichen, rund 40 Posten, rund 11 000 Verweise; Prüfstand `neben` je Codebasis | **je Codebasis ein Agent möglich** (getrennte Dateien); Prüfarbeit und Changelog seriell | 10 |
| *Betreiberin* | F-R17-04, -14 (Rulesets), -05 (Rahmenplan 6.1), -23 (Produktiv-Einstellungen); P-R17-04, -12, -13 | — | kein Code | — | vor allem anderen |

Was der Schnitt **nicht** enthält, mit Grund: F-R17-13 (entschieden,
E-R17-09), F-R17-30 (erledigt mit R17-04), F-R17-31 (im Modell); Nr. 263
(Grenze im Modell, E-R17-12 — die Freigaberunde entscheidet, ob der
Punkt zugeht). Drei Fragen gehen damit in R17-17, neben der Empfehlung je
Befund: ob Nr. 380 bis 382 in P6 laufen oder nach P7; ob F-R17-58 als
vorgezogenes Paket läuft; und ob `update.php` als Weiche bleibt (Nr. 77).

**(f) Die R5-Ausnahmeliste — Zuarbeit für P7.** R5 lässt gespeicherte
Namen (Spalten, Felder der Backup- und Exportformate, der Uhr-Vertrag)
bei der Umbenennung stehen, mit einer Ausnahme: „eine kurze, in P6 zu
entscheidende Liste von Feldern, die bei Bodeneinsätzen aktiv
irreführen" (R71: in P7 beschließen). Die Zulieferung aus P2 war leer;
dieses Stück hat nachgemessen — `grep -oiE` über Luft- und Bodenbegriffe
(luft, heli, hubschrauber, flug, landung, winde, hoist, pilot, crew,
hems, rth, nef, rtw, boden) in `schema.sql`, `JSON-Vertrag.md`,
`Export-Format.md`, `Backup-Format.md`, `Const.mc` und
`mission_fields.php`. Ergebnis in drei Gruppen:

- **Schon umbenannt** (Web 6.0.0, Vertrag 1.3, Uhr 1.8.0 — E20, E21):
  `flugtag` → `diensttag`, `hubschrauber` → `rettungsmittel`,
  `phase_03_abflug` → `phase_03_ausruecken`,
  `phase_07_landung_krankenhaus` → `phase_07_ankunft_klinik`,
  `Flugkilometer` → `Kilometer`. Die Phasen gehen als Nummern 2 bis 9
  über den Vertrag; die Uhr erfährt die Art nicht und trägt deshalb
  neutrale Beschriftungen (`Const.mc`).
- **Luftgebunden und richtig so:** die Rollenkennungen `p1`, `p2`,
  `hems`, `fr` (`CREW_ROLES`; an einem bodengebundenen Tag leer, die
  Bodenrollen `driver`, `trainee` gefüllt — `Export-Format.md` 3.8 sagt
  es), die Fähigkeit `winch` mit `winch_cycles`, `winch_cycles_pat`,
  `winch_airload` (nur sichtbar, wenn das Rettungsmittel eine Winde
  führt — eingefroren je Diensttag), `logo_wahl = 'hubschrauber'` und
  `LOGO_LUFT` (eine Wahl der Nutzerin, keine Aussage über den Einsatz),
  die Exportspalte `art` mit `luft`/`boden` (sie **benennt** die Art,
  statt sie vorauszusetzen).
- **Geschichte, kein Datenfeld:** die Migrationskennung
  `2026_07_17_flugtage` und die Zeilen „bis Web 5.10.0" in den Formaten.

**Die Liste bleibt leer.** Kein gespeicherter Name behauptet an einem
Bodeneinsatz etwas Falsches: Die luftgebundenen bezeichnen etwas, das es
dort nicht gibt, und bleiben leer. Was P7 zu entscheiden hat, ist nicht
R5, sondern der Name des Repositoriums und der sichtbaren Texte — und
das steht in Schritt 13, nicht hier. Zuarbeit erledigt; die Messung ist
der Beleg, nicht die Behauptung aus P2.

### 7.4 Vorsortierung — Mittel und Grenzen

Nach E-R17-16 örtlich und nicht im Repositorium. Geplant: PHPStan (Stufe
nach Probe) oder Psalm für ungenutzte Funktionen und tote Zweige unter
`server/`; `vulture` für `tools/`; Android Lint über `appbau.sh` oder
`gradlew lint`; die Compiler-Warnungen der Uhr, falls das Connect-IQ-SDK im
Container steht (Prüfdokument 1 sagt: bisher nicht). Dazu eigene Zähler für
den Kommentardurchgang (Muster für R-, E-, F-, Nr.- und Fassungsverweise je
Datei). Grobzählung am 20.10.2026, Orientierung und kein Prüfwert: rund
3 000 Treffer unter `server/`, 90 in `watch/`, 530 in `android/`, 1 100 in
`tools/` und `.github/` — das Muster trifft auch Fassungsköpfe
(`version.php` ist seiner Natur nach Geschichte) und Wörter, die zufällig
so aussehen. Was ein Mittel lieferte und was es nicht sieht, steht je
Stück in 7.3 und im Prüfdokument 2 und 5.

**Gefahren für Stück 2 (R17-06, 20.10.2026):** PHPStan 2.3.1 als Phar
im Arbeitsverzeichnis, Stufe 4, über `server/` ohne `vendor/`, `assets/`,
`demo/`, `doku/` — 506 Meldungen insgesamt, 88 in den Dateien des Stücks
(Aufschlüsselung in 5 und 7.3.2). Die Stufe ist die höchste, die ohne
Bootstrap-Datei und ohne Typangaben am Altbestand lesbar bleibt; höher
meldet sie Typen, die der Code bewusst offen lässt. **Nicht gefahren:**
`vulture` — es liest Python, nicht PHP, und ist für `tools/` (Stück 10)
aufgehoben; Android Lint und Uhr-Compiler kommen mit den Stücken 8 und 9.
Die eigenen Zähler (`grep` über die Funktionsnamen des Stücks gegen
`server/`, `tools/`, `docs/`; das Verweismuster je Datei) stehen in 7.3.2
mit Zahl. Die Zählung ist eine Liste, kein Spürsinn: Sie findet keine
Funktion, die über eine Zeichenkette gerufen wird, und zählt einen
Fachbegriff mit, der wie ein Verweis aussieht.

**Gefahren für Stück 3 (R17-07, 20.10.2026):** dieselbe PHPStan-Konfiguration;
31 Meldungen in den Dateien des Stücks (22 Rauschen, 9 gelesen, 0 Befunde;
Aufschlüsselung in 7.3.3). Dazu der Aufruferzähler über 169 Funktionen
(2 Karteileichen) und ein `grep` gegen die Probe `tools/proben/raten/` und
`docs/Technik.md` 5e.2, der F-R17-36 gegen Prüfmittel und Doku gehalten hat.

**Gefahren für Stück 4 (R17-08, 20.10.2026):** dieselbe PHPStan-Konfiguration;
101 Meldungen in den Dateien des Stücks (87 Rauschen, 14 gelesen, 5 davon
die zwei Befunde F-R17-38 und -39; Aufschlüsselung in 7.3.4). Dazu der
Aufruferzähler über 193 Funktionen (0 Karteileichen), `php -r` für das
Verhalten eines `use` mit undefinierter Variable, `git log -S` für das
Alter von F-R17-39 und ein `grep` gegen die Probe `tools/proben/gpx/`,
`demo_lib.php` und `assets/wartungslauf.js`, der gezeigt hat, warum die
Probe grün ist, während Produktiv den Fehler hat.

**Gefahren für Stück 5 (R17-09, 20.10.2026):** dieselbe PHPStan-Konfiguration;
201 Meldungen in den Dateien des Stücks (181 Rauschen, 20 gelesen, 0 Befunde;
Aufschlüsselung in 7.3.5). Dazu der Aufruferzähler über 140 Funktionen
(0 Karteileichen; 18 nur in der eigenen Datei genannt, das ist bei
Seitenhelfern der Regelfall), `php -r` für den Monat 13 (F-R17-44), und
vier `grep`-Gegenproben, die je eine Liste gegen die Handlungen halten, die
sie decken soll: `ZF_FRISCH_HANDLUNGEN` gegen die `zf_`-Zweige (F-R17-43),
`DEMO_GESPERRT` gegen die Handlungen von `admin_user.php` (F-R17-42),
`fputcsv` gegen die Herkunft seiner Zellen (F-R17-45), die Monatsprüfung
von `api/range.php` gegen die von `zeitraum.php` (F-R17-44). Alle vier
Befunde sind Listen, die eine Zeile vergessen — der Verweiszähler und
PHPStan finden so etwas nicht, nur die Lesung gegen die Gegenliste.

**Gefahren für Stück 6 (R17-10, 20.10.2026):** dieselbe PHPStan-Konfiguration;
66 Meldungen in den Dateien des Stücks (45 Rauschen — zum ersten Mal mit
`class.notFound` aus zwei Fremdbibliotheken unter `vendor/` —, 21 gelesen,
0 Befunde; Aufschlüsselung in 7.3.6). Dazu der Aufruferzähler über 412
Funktionen (3 Karteileichen, 61 nur in der eigenen Datei), und fünf
`grep`-Gegenproben, die je eine Liste oder einen Vertrag gegen seine
Verwender halten: die `FilesMatch`-Liste gegen die Schreibziele von
`wartung_lib.php` (F-R17-47), die Zusage „ohne Authentifizierung" gegen
den `AUTH`-Block (F-R17-46), das Meldungsmuster gegen die Codeliste und
das Probenmuster (F-R17-48), die Maskierung von `ui_meldung_markup()`
gegen die Texte ihrer Aufrufer (F-R17-49, erst die mehrzeilige Suche
fand die zweite Stelle — der Aufruf steht in `doku_seite.php` über fünf
Zeilen, eine einzeilige Suche nach `ui_meldung_markup(.*<` sieht ihn
nicht), die rohe `unter`-Ausgabe gegen ihre achtzehn Aufrufer (kein
Befund). Die Lehre dieses Stücks: Wer nach einem Markup-Argument sucht,
sucht über Zeilen hinweg; die Treffer stehen dort, wo der Text lang genug
ist, um umzubrechen.

**Gefahren für Stück 7 (R17-11, 20.10.2026):** kein PHPStan — das Stück
hat kein PHP. Stattdessen `node --check` über 61 Skripte (0 Fehler), die
Prüfsummen der fünf vendor-Textdateien gegen ihren Kopf (4 stimmen, eine
nicht — und der Grund war erst mit dem npm-Original und `git ls-files
--eol` zu finden: F-R17-50), `vollstaendigkeit.py` für Nr. 341 (13 Regeln
ohne Markup, bestätigt gegen `grep` über `server/`), ein Zähler über
`innerHTML`, `insertAdjacentHTML`, `textContent`, `eval`, `new Function`,
`document.write`, `fetch(`, `style="`, `localStorage`, `sessionStorage`,
eine Exportprobe (`window.EdX = {…}` gegen `EdX.name` in `server/` und
`tools/`: 36 Namen, 15 Einträge ohne fremden Verwender), die Skriptzahl je
Seite (Nr. 380) und zwei CSP-Gegenproben (`style-src-attr` gegen die zehn
`style="`-Stellen, `KOPF_KACHELN` gegen die vier Hosts in `map_layers.js`).
Zwei Grenzen, beide benannt: Der `innerHTML`-Durchgang ist Lesung — er
sagt, dass jede Einsetzung maskiert ist, aber keine Injektionsprobe mit
einer präparierten Sicherung hat es nachgespielt (Prüfdokument 1); und die
Exportprobe liest `window.EdX = {…}`-Blöcke mit einem Muster, das
Parameternamen einer Funktion im Block als Einträge sehen kann — die
Trefferliste ist von Hand bereinigt (ein Fehltreffer, `EdKarte.el`). Die
Lehre dieses Stücks: Eine Prüfsumme im Dateikopf ist nur so viel wert wie
das Werkzeug, das sie nachrechnet — und `.gitattributes` schreibt Dateien
um, bevor jemand sie prüft.

**Gefahren für Stück 8 (R17-12, 20.10.2026):** zum ersten Mal in diesem
Review der **Uhr-Prüfstand im Container** — `CIQ_GERAETE_URL` war gesetzt,
`developer.garmin.com` und die Gerätequelle erreichbar, und `aufbau` holte
SDK 9.2.0, 1 332 Schriftdateien und 99 von 99 Manifest-Geräten (0
fehlende Simulatorbibliotheken); `reihe` über die Auswahlliste von
`geraeteklassen.py` übersetzte für alle 99 Geräte (0 fehlgeschlagen) und
zählte in jedem Log die `WARNING`-Zeilen: 0 — nachgerechnet mit
`grep -c WARNING` über alle 99 Logs. Daneben Zähler über
`Communications.makeWebRequest` (6), `Storage.setValue` (13), `.getValue`
(18), `.deleteValue` (10), `Properties.getValue` (5), `.setValue` (2),
`println` (0), `try` (8), `WatchUi.Confirmation` (8), `Cryptography.`
(22), `randomBytes` (1), `Math.rand` (3), eine Verwenderprobe über alle
306 `function`-Namen (2 ohne zweite Nennung), `grep` über `"http"`,
`PARK_FRIST`, die Storage-Schlüssel und `uses-permission`. Drei Grenzen:
Stufe I belegt, dass die App **übersetzt** — nicht, dass sie läuft; Stufe
II (Simulator, Bilder) und der Rundlauf (koppeln, verschlossen senden,
öffnen) sind nicht gefahren, weil der Review nichts behebt und S11-08 beide
schon gemessen hat. F-R17-52 und -53 sind gelesen, nicht nachgespielt; die
Reproduktion steht in der Empfehlung. Und ein `WARNING`-Zähler sieht nur,
was der Compiler meldet — `monkeyc -w` kennt keine Datenflussanalyse, und
ein Index, der eine Anfrage lang gilt, ist für ihn kein Fehler. Die Lehre
dieses Stücks: Der Prüfstand, der am 03.09.2026 noch 29 Warnungen zählte,
zählt heute null — eine Zahl, die man erst glaubt, wenn man die 99 Logs
selbst gezählt hat.

**Gefahren für Stück 9 (R17-13, 20.10.2026):** zum ersten Mal in diesem
Review der **Android-Bau im Container** — `tools/sandbox/aufbauen.sh android`
holte SDK und cmdline-tools nach `/opt/android-sdk` (Rückgabe 0), dann
`./gradlew --no-daemon lint test` über beide Module und beide Bauarten:
BUILD SUCCESSFUL in 6 min 5 s, 144 Aufgaben (131 ausgeführt, 13 aus dem
Cache). Ausgelesen aus `build/test-results/*/TEST-*.xml`: Handy 30 Klassen,
280 Fälle je Bauart, 15 übersprungen (die drei Rundlauf-Klassen ohne
`-Pnadoku.rundlauf` und der jeweils andere Fall von `ServeradresseTest`),
Uhr 6 Klassen, 72 Fälle, 0 übersprungen, 0 Fehlschläge, 0 Fehler; aus
`lint-results-debug.xml`: Handy 5 Warnungen (2 × `GradleDependency`
wear-compose 1.7.1, 3 × `NewerVersionAvailable` Kotlin 2.4.21 und
Robolectric 4.17), 0 Fehler, Uhr 0 und 0; Bilderlauf Handy 78 Bilder, Uhr 7
(paarweise verschieden). Das sind, Zahl für Zahl, die Werte aus
`android/LIESMICH.md` vom 17.10.2026. Daneben Zähler über die 54
`main`-Kotlin-Dateien: `Log.` 23, `try` 27, `catch (Exception)` 13,
`rawQuery`/`execSQL` 25 (0 mit `$`), `SecureRandom` 3, `Tasks.await` 3,
`HttpURLConnection` 6, `Cipher.getInstance` 3, `KeyGenParameterSpec` 2,
`setUserAuthenticationRequired` 2, `PendingIntent.get` 7 (7 ×
`FLAG_IMMUTABLE`), `@Volatile` 5, `System.getenv` 0, Play-Dienste-Importe
6 Zeilen in 3 Dateien, `sourceNodeId` 3, `TODO` 0; Manifeste: 8
`uses-permission` am Handy, 0 an der Uhr, `exported="true"` je zwei. Vier
Grenzen: `lint test` **baut kein APK** — Größe und Signatur sind nicht
gemessen; die drei instrumentierten Klassen (`DataLayerErreichbar`,
`GeraetehuelleGeraet`, `GeraetTresor`, 11 Fälle) brauchen ein Gerät und
sind nicht gelaufen; die 15 übersprungenen Fälle brauchen die örtliche
Anlage, die der Review nicht startet; und Stufe II (`emulator.sh`) ist
nicht gefahren, weil der Review nichts ändert (`Pruefablauf.md` 6.9).
F-R17-54 ist gelesen, nicht nachgespielt — die Reproduktion steht in der
Empfehlung und als P-R17-11 im Prüfdokument. Die Lehre dieses Stücks: Ein
grüner Baulauf mit 352 Fällen sagt nichts über einen Weg, den kein Fall
geht — der Knopf in der Benachrichtigung hat keinen.

**Gefahren für Stück 10 (R17-14, 21.10.2026):** die Werkzeuge gegen sich
selbst, soweit der Container sie trägt — 13 Selbstproben und Erzeuger,
alle grün: `tools/quelltext/bestand.py` 0 Befunde (alle Flächen);
`referenzdatensatz/quelldaten/pruefen.py` 22 Dienste, 106 Einsätze, 123
Ruhesegmente, 1 372 Zeitstempel, 7 262 Einzelprüfungen, 98 Matrixzeilen,
0 offen, 110 Marken; `generator/erzeugen.py` 22 Dienste, 106 Einsätze,
68 018 Spurpunkte, 638 Ingest-Anfragen, 144 Strecken aus OSRM, dann
`generator/pruefen.py` 638 Anfragen, 219 Spuren auf Tempo und Höhe, 100
Krypto-Rundläufe, 4 Fußwege (2,1 bis 3,5 km/h), 339 472 Einzelprüfungen,
0 Befunde; `vergleich/vergleichen.py --testabweichung` gegen die
eingecheckte Referenz: CSV 10 von 10 Proben aufs Exempel, 12 486
Einzelvergleiche, 0 unerklärt, edbak 15 von 15, 347 566 Einzelvergleiche,
0 unerklärt (die Zahl aus `edbak_umlauf.json` vom 19.10.2026, auf die
Stelle); `browser/download_lib.mjs --selbstprobe` 10 erfüllt, 0 offen;
`proben/kopplung/rundlauf.mjs --selbstprobe` 13 von 13;
`proben/huelle/referenz.py --selbstprobe` 5 Fälle, 0 Fehlschläge;
`schemaprobe/probe.php --selbstprobe` 4 von 4; `spaltenregister/pruefen.php`
jede Spalte genau einmal, drei Abbildungen vollständig;
`screenshots/kontrast.py` 36 Paare, 0 verfehlt, 32 aus dem Stylesheet
abgeleitet, 0 ohne Eintrag; `zaehlung/zaehlen.php` 44 Zeilen, 0 über der
Decke (Z17 Ist 0 bei Decke 30 — F-R17-56). `vulture tools/` 37 Meldungen,
6 mit 100 %. Dazu die Zähler über die 277 Dateien (Abschnitt 5) und die
Inventur: 437 Dateien, davon 277 Werkzeug. **Vier Grenzen:** Der
Uhr-Prüfstand (`pruefstand.sh aufbau`) ist nicht gefahren — kein
Connect-IQ-SDK im Container (`/opt` ohne `connectiq`), und der Download
von `developer.garmin.com` ist genau der ungepinnte aus F-R17-20; `sh
pruefstand.sh` scheitert an `set -o pipefail` (dash), die Anleitung sagt
`bash`, kein Befund. Die Kette ist **gelesen, nicht gelaufen** — kein
Probelauf, kein Tag; was ein Ruleset tut (F-R17-04, -14), steht in keiner
Datei. `proben.sh alle` und die Bedienprobe sind nicht gefahren; der
Prüfstand `klein` am Ende des Pakets ist die eine Messung gegen die
Anlage, seine Zahl steht im Commit. Und die elf Browserwerkzeuge „von
Hand" (Nr. 391) sind nicht gestartet — zwei davon messen, wie 7.3.10
zeigt, einen Stand, den es nicht mehr gibt. F-R17-55 ist gelesen, nicht
nachgespielt (kein Probelauf mit `kill -9`); die Abrufbarkeit folgt aus
der Endung und der `FilesMatch`-Regel, nicht aus einem `GET` — P-R17-12
misst auf Staging und Produktiv, dass dort nichts liegt. Die Lehre dieses
Stücks: Werkzeuge, die ihre Zahl nennen und sich selbst prüfen, sind in
Ordnung, wo sie messen — die Befunde liegen daneben, in dem, was sie
**beim Messen anfassen** und wo sie es ablegen.

**Gefahren für Stück 11 (R17-15, 21.10.2026):** kein eigenes Werkzeug —
die Doku hat keins —, sondern je Aussage die kleinste Messung, die sie
belegt oder widerlegt: `grep -c`, `ls | wc -l`, `php -r 'require …; echo
count(…)'`, `wc -l`, `sha256sum -c`, und die Riegel selbst dort, wo sie
die Doku lesen (`jobregister.php` 11/18, `kennzeichnung` 26 von 26,
`sitzungshaertung` 0 Befunde, `zaehlen.php` 44 Zeilen, `kontrast.py` 36
Paare). Rund 120 Aussagen sind so gemessen, rund 50 davon überholt
(F-R17-59), vier Texte gegen die Zusage (F-R17-58); die Zahlen stehen je
Dokument in Abschnitt 5 und im Prüfdokument 2. **Drei Grenzen:** Gemessen
ist, was sich ohne Anlage messen lässt — eine Aussage über das Verhalten
der Oberfläche (Handbuch 4, 7, 10: welcher Knopf was tut) ist gegen den
Quelltext gelesen, nicht im Browser gefahren; der Prüfstand `klein` am
Ende des Pakets ist die eine Messung gegen die Anlage. Die Rechtstexte
sind Vorlagen — was auf Produktiv in `rechtstexte` eingespielt ist, sieht
nur die Betreiberin (P-R17-13). Und die Vollständigkeit der Lesung ist
die Vollständigkeit einer Lesung: Jede Zeile ist gelesen, jede **Zahl**
gemessen; ein Satz ohne Zahl, der einen Ablauf falsch beschreibt, fällt
nur auf, wenn der Code dazu im selben Stück lag — für 4.97 bis 5e war das
so (Stück 4 bis 6 liegen zurück), für die Handbuch-Kapitel zur Bedienung
nur über den Quelltext der Seiten. Die Lehre dieses Stücks steht in 7.3.11:
Was ein Werkzeug hält, stimmt; was eine Hand pflegt, altert — und die
Zusage altert dort am schnellsten, wo sie am häufigsten wiederholt ist.

**Gefahren für Stück 12 (R17-16, 21.10.2026):** keine Vorsortierung —
das Stück liest, was die Stücke 2 bis 11 geschrieben haben, und zählt
zweimal: den Kommentardurchgang mit dem weitesten Muster der Stücke über
alle fünf Codebasen (`zaehlung-r17-16.py`, Dateimenge nach Endung aus
`git ls-files`) und die gespeicherten Namen mit Luft- oder Bodenbegriff
(R5, `grep -oiE` über sechs Dateien). **Drei Grenzen:** Das Muster trifft
Fachbegriffe, die wie eine Nummer aussehen, und verfehlt jeden Verweis in
Prosa ohne Nummer („seit der Umstellung auf Diensttage") — die Zahl ist
eine Orientierung für den Umfang des Pakets, die Zeilenliste erzeugt das
Paket selbst (7.2). Die R5-Messung findet Namen, nicht Irreführung — ob
`hems` an einem Bodentag irreführt, ist ein Urteil, und es steht in 7.3.12
mit Begründung. Und der Paketschnitt ist ein Entwurf aus der Lesung: Ob
zwei Befunde in dasselbe Paket gehören, zeigt sich erst beim Umbau — ein
Paket, das sich als zu groß erweist, wird geteilt, nicht gedehnt. Die
Lehre dieses Stücks: Von 59 Befunden liegen 31 in Kryptographie,
Schlüsselwegen und Kette (Fable), 23 in Code, den Opus ändern kann, 2
sind allein Handgriffe der Betreiberin (F-R17-04, -05; bei -14 und -23
steht daneben ein Codeanteil), und 3 brauchen kein Paket mehr.
