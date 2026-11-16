# Konzept KA — Sicherheitspaket nach dem Audit

**Schritt 28 des Rahmenplans, Sofortpunkt vor Schritt 13 (R92).**
Befundteil: `docs/konzepte/Audit-KA-Sicherheit-Krypto.md` — dort stehen die
43 Befunde F-KA-01 bis F-KA-43 mit Fundstelle, Angriffsweg und Einstufung.
Dieses Konzept sagt, **was daraus gebaut wird und in welcher Reihenfolge**.
Es ändert keinen Code und legt keine Versionsnummer fest; die Stufe je Paket
steht da, die Nummer setzt die Umsetzung.

## Statusblock

| | |
|---|---|
| Stand | **Umsetzung** seit 10.11.2026, Zweig `claude/affectionate-darwin-vqocxe`, Backlog-Spanne 560–569 |
| In Arbeit | **Web 25.0.0, das Umlegen der Weiche** (Abschnitt 5, „KA-08 bis KA-15“, E-KA-82 bis -86), Zweig `claude/amazing-keller-i2f04f`, ein gesammelter PR am Ende: **KA-08 erledigt** (M-KA-03 freigegeben, E-KA-87), **KA-12 erledigt** (Handbuch und Runbook: Geräte nach dem Tag neu koppeln), **KA-09 erledigt** (Referenz Rückweg, Fable-Agent), **KA-15 erledigt** (Android 0.26.0), **KA-10 erledigt** (Browser-Kern), **KA-11 gebaut** (Anzeige in `einsatz.php`; gefahren wird sie mit umgelegter Weiche in KA-14), **KA-13 erledigt** (Demo-Fixture Format 4; lädt erst mit KA-14), **KA-14 erledigt** (Prüfstand `haupt` 74 grün, 0 rot); **alle Pakete KA-08 bis KA-15 fertig**; vor dem PR auf Wunsch der BetreiberIn Backlog **Nr. 566** (Android, erledigt), **Nr. 568** (E-KA-92, erledigt) und **Nr. 569** (Sichern und GPX schneller, Fable-Agent, erledigt); Prüfstand `haupt` 74 grün, 0 rot; **PR #190 gemergt 16.11.2026** (ging mit Konflikt auf, `main` örtlich gemergt, Prüfstand über den Merge neu; Regel seither `Pruefablauf.md` 5.3a); Lauf 219 auf Staging grün samt Stufe 2, R91 mit eigenem Konto ohne Befund (Abschnitt 9); **06e in Arbeit** als KA-16 bis KA-23 (Web 26.0.0, E-KA-93 bis -97; PR wartet auf den Tag `web-v25.0.0`); Garmin-Teil (Zähler am Inhalt, kein Senden ohne Herkunft) erledigt als KG-09/KG-10, Uhr 5.1.0, PR #189 gemergt; offen bei der BetreiberIn P-KA-16 (Handy am Gerät ab Android 12, nach `android-v0.25.0`) |
| Erledigt | **KA-06f** (PR #187, gemergt 15.11.2026, Android 0.25.0, Web 24.19.0; Zweig `claude/amazing-keller-i2f04f`; R91 auf Staging grün, Abschnitt 9) · **KA-06c** (PR #184, gemergt 14.11.2026, Android 0.24.0, Web 24.18.1; R91 auf Staging grün, Abschnitt 9) · **KA-06d** (PR #179, gemergt 14.11.2026, Web 24.18.0; `update.php` auf Staging gelaufen, R91 grün, Abschnitt 9) · **KA-06b-4** (PR #177, gemergt 13.11.2026, Web 24.17.0; R91 auf Staging grün, Abschnitt 9) · **KA-06b-3** (PR #176, gemergt 13.11.2026, Web 24.16.0) · **KA-06b-2** (PR #175, gemergt 12.11.2026, Web 24.15.0; `update.php` auf Staging gelaufen) · **KA-06b-1** (PR #174, gemergt 11.11.2026, Web 24.14.0) · **KA-06a** (PR #172, gemergt 11.11.2026, Referenz und Vektoren) · **KA-06b vorab** (PR #171, gemergt 11.11.2026, Web 24.13.1) · **KA-05** (PR #170, gemergt 11.11.2026, Web 24.13.0; Lauf 196 grün, auf Staging nachgeprüft — P-KA-11 bestanden, P-KA-10: Staging listet `assets/` noch) · **KA-01** (PR #165, gemergt 10.11.2026; Lauf 191 auf `main` grün, FTPS gegen Staging mit `security: strict`) · **KA-04** (PR #169, gemergt 11.11.2026, Web 24.12.1, Android 0.23.0) · **KA-03** (PR #167, gemergt 10.11.2026, Web 24.12.0) · **KA-02** (PR #166, gemergt 10.11.2026, Web 24.11.0; Lauf 192 grün, auf Staging nachgeprüft — Abschnitt 9; Tag `web-v24.11.0`, Lauf 193 auf Produktiv grün) |
| Hakt | nichts im Code. **Offen bei der BetreiberIn:** die SHA-Pflicht für Aktionen (Rest von KA-M2; das geprüfte Zertifikat ist mit Lauf 193 auch auf Produktiv belegt); **vor dem Tag von KA-03** P-KA-05 (SFTP-Ziele mit Abdruck) |
| Gelesen gegen | Commit `e8b0fc5` · Web 24.10.1 · Uhr 4.3.3 · Android 0.22.0; die Befunde von KA-01 am 10.11.2026 gegen `3e06f50` nachgelesen — alle acht halten (Abschnitt 9) |

**Die Umsetzung findet in einer eigenen Sitzung statt** — ein Paket nach
dem anderen, nach jedem Paket Statusblock, Prüfstand, Push
(`CLAUDE.md` 7 und 8).

---

## 1. Aufgabe

Alle 43 Befunde des Audits werden behoben, von hoch bis Hinweis (E-KA-04).
Kein Befund wird als hingenommen ins Bedrohungsmodell verschoben; wo eine
Grenze bauartbedingt bleibt, steht sie am Paket und geht mit KA-07 ins
Modell.

**Was danach gilt, in vier Sätzen:**

1. Kein Geheimnis, das Patientendaten öffnet, verlässt den Browser — auch
   nicht zum Drucken.
2. Jede Verbindung der Kette prüft ihr Gegenüber, und kein freier Text wird
   in ihr zu Code.
3. Ein verschlüsselter Block gehört nachweisbar zu seinem Konto, seinem
   Datensatz und seinem Zweck; Gerätedaten gehören nachweisbar zu einem
   gekoppelten Gerät.
4. Was ein Gerät aufzeichnet, landet im Konto, an das es beim Aufzeichnen
   gekoppelt war, oder nirgends.

**Nicht Teil dieses Schritts:** eine Rotation des Inhaltsschlüssels
(F-R17-10 bleibt Restrisiko R7; Schritt 14 setzt jedes Konto neu auf und
gibt ihm damit einen neuen), Signaturen für Sicherungspakete, ein echter
Safari.

---

## 2. Entscheidungen

| Nr. | Entscheidung | Wann, wer |
|---|---|---|
| E-KA-01 | Das Audit liest alles, unabhängig vom Bedrohungsmodell | 09.11.2026, BetreiberIn |
| E-KA-02 | Gemischte Arbeitsweise; jeder Befund eines Unter-Agenten wird an der Fundstelle nachgeprüft | 09.11.2026, BetreiberIn |
| E-KA-03 | Ergebnis als Dokument im Repositorium, keine Korrekturen im Audit | 09.11.2026, BetreiberIn |
| E-KA-04 | **Alle Befunde werden umgesetzt, von hoch bis niedrig, die Hinweise eingeschlossen** | 10.11.2026, BetreiberIn |
| E-KA-05 | **Zuordnung in beiden Stufen in einem Zug:** Bindung der Chiffretexte **und** Herkunftsnachweis der Gerätedaten — weil der Gerätecode für die Bindung ohnehin angefasst wird (Antwort auf Q-KA-03) | 10.11.2026, BetreiberIn |
| E-KA-06 | Der Bericht geht als **Sofortpunkt mit Konzept** in den Rahmenplan: Schritt 28 vor Schritt 13 (R92). Bedrohungsmodell und Prüfliste werden nachgezogen, wo nötig (Antwort auf Q-KA-04) | 10.11.2026, BetreiberIn |
| E-KA-07 | **Die Wiederherstellungsschlüssel werden erneuert — nach KA-02, nicht davor.** Davor schickte der Druckknopf auch den neuen Schlüssel an den Server. Vollständig heilt erst Schritt 14, weil das Erneuern denselben Inhaltsschlüssel nur neu verpackt (Antwort auf Q-KA-01: ob ein Proxy mitschreibt, ist nicht bekannt) | 10.11.2026, BetreiberIn |
| E-KA-08 | Der Formatwechsel ist ein Paket mit **eigener Entwurfsrunde** (KA-06a) vor der ersten Zeile Code; Format und Vektoren stehen wie bei S11 in einer Referenz, an der alle Umsetzungen gemessen werden | 10.11.2026, Konzept |
| E-KA-09 | **Nach KA-01 und KA-02 wird sofort getaggt** — beide hohen Befunde auf Produktiv, ohne auf KA-03 zu warten (Antwort auf Q-KA-05) | 10.11.2026, BetreiberIn |
| E-KA-10 | **Die Tokens für Jobs, Kette und Health werden nur einmal gezeigt,** direkt nach dem Erzeugen, und gehasht gespeichert; ein verlorenes Token wird erneuert, nicht nachgelesen (Antwort auf Q-KA-06) | 10.11.2026, BetreiberIn |
| E-KA-11 | **Der Rückfallstand auf Staging wird mit seinem Zeitstempel genannt, ohne Zufallsteil** (KA-01 Punkt 4). Der Job schrieb den Dateinamen bewusst neben den Tag, damit klar ist, auf welchen Stand Staging zurückgesetzt wird; die acht Hexzeichen sind aber die zweite Schranke des Ordners. Der Zeitstempel nennt den Stand eindeutig | 10.11.2026, BetreiberIn (Rückfrage vor KA-01) |
| E-KA-12 | **Der Schlussschritt „Wenn etwas schiefging" läuft auch bei Abbruch** (`failure() \|\| cancelled()`). Ein Job, der in sein Zeitlimit läuft, gilt als abgebrochen, nicht als gescheitert — und gerade dann gehört die Ansage dazu, ob die Wartung an ist | 10.11.2026, BetreiberIn |
| E-KA-13 | **`integritaet.yml` bekommt in F-KA-44 ebenfalls ein Zeitlimit** — dieselbe Lücke wie die übrigen Arbeitsläufe | 10.11.2026, BetreiberIn |
| E-KA-14 | **Riegel 3 sperrt jeden Eingang im Skript, nicht nur die vom Typ `string`.** Ein `boolean` kann nichts einschleusen, aber eine Regel ohne Typ-Nachschlagen hat keine Lücke, wenn jemand den Typ eines Eingangs ändert; die vier `boolean`-Stellen kosteten je eine `env:`-Zeile | 10.11.2026, Umsetzung |
| E-KA-15 | **Geheimnisse der Stufe-2-Werkzeuge über `--…-aus NAME`**, der Schalter nennt die Umgebungsvariable. `kreislauf.py` liest das Kettentoken bewusst **nicht** still aus `JOBS_TOKEN` (Kommentar vom 17.09.2026: ein vergessenes `export` schickte den Kreislauf sonst gegen die falsche Anlage); der Schalter bleibt deshalb ausdrücklich, nur der Wert verlässt die Befehlszeile. Die reinen Kettenwerkzeuge (`tor.py`, `zielprobe.py`, `zustand.py`, `apkablage.py`) lesen fest `JOBS_TOKEN` bzw. `FTP_PASSWORD`; `--token` und `--ftp-pass` gibt es nicht mehr | 10.11.2026, Umsetzung |
| E-KA-16 | **Es gibt eine Nutzerin, die BetreiberIn — keine Benachrichtigung, keine Rückwärtsverträglichkeit.** Daraus: KA-02 Punkt 6 (Satz nach der Erneuerung) entfällt; KA-M4 schrumpft auf „die BetreiberIn erneuert ihren Schlüssel nach dem Tag"; ein alter Aufrufer der Blätter bekommt 400 statt eines Übergangs. Für KA-05 Punkt 7 und KA-06e heißt das: kein Rückfall und keine Übergangsfrist für alte Formate — **aber** die eigenen Daten der BetreiberIn im Altformat müssen lesbar umziehen; das wird vor KA-06 noch einmal gefragt | 10.11.2026, BetreiberIn |
| E-KA-17 | **Übergabe an das Druckfenster per `postMessage`**, Ursprung und Fenster geprüft auf beiden Seiten. Die Fragmentkennung (`#code`) erreicht den Server nicht, steht aber im Verlauf, und viele Browser gleichen ihn zwischen Geräten ab; ein Druck im selben Fenster hätte den Bedienweg geändert (kein Blatt am Bildschirm) | 10.11.2026, BetreiberIn (Vorschlag der Umsetzung) |
| E-KA-18 | **Die Kontoadresse geht bei der Erstvergabe mit dem Code ans Blatt** — sie ist kein Geheimnis, und ohne Sitzung kennt das Blatt sie sonst nicht; mit Sitzung geht die aus der Sitzung vor | 10.11.2026, BetreiberIn |
| E-KA-19 | **Nr. 540 ohne Mockup:** Blätter und Druck stellen die Rollen auf ihre hellen Werte zurück — vorhandene Token, kein neuer Farbwert, kein neuer Baustein; das Druckbild bleibt das gedachte | 10.11.2026, BetreiberIn |
| E-KA-20 | **Der Riegel aus KA-02 Punkt 5 misst Formulare und Abrufe mit einem Druckblatt als Ziel, nicht den Feldnamen.** `code` heißt auch das Feld der Zweitfaktor-Formulare; ein Riegel über den Namen schlüge falsch an oder würde ausgenommen | 10.11.2026, BetreiberIn |
| E-KA-22 | **Die Antworten auf die drei Fragen vor KA-03:** Die bestehenden Tokens gelten weiter — ein Klartextwert wird beim ersten Lesen gehasht zurückgeschrieben, keine Migration, kein `update.php`; Punkt 10 über das „Gerät merken"-Cookie; Tag `web-v24.11.0` vor KA-03 | 10.11.2026, BetreiberIn |
| E-KA-23 | **Die Übernahme des Kettentokens vom Job-Token fällt** (mit E-KA-16): Mit Hashes hätte sie beide Tokens dauerhaft gleich gemacht. Fehlt das Kettentoken, ist es nicht eingerichtet | 10.11.2026, Umsetzung |
| E-KA-24 | **Die Rollentore von KA-03 stehen vor dem Formular-Token**, wie überall: Adressen der Installation (`require_betreiberin()`, 403) und der frische Code für ein Konto mit Rechten (vor `csrf_check()`, wie in `admin_user.php`). So misst die Matrix der Rollenprobe das Tor und nicht die Token-Ablehnung | 10.11.2026, Umsetzung |
| E-KA-25 | **Punkt 10 sucht über das Cookie, nicht über die Adresse** (`zweitfaktor_geraet_fuer_adresse()`): ohne Cookie keine Abfrage, mit Cookie genau eine, gleich ob es die Adresse gibt — die Antwortzeit verrät kein Konto. Der Topf `login_ip` gilt weiter für alle | 10.11.2026, Umsetzung |
| E-KA-26 | **Vertrag von KA-04, festgelegt vor dem Bau:** (1) `ingest.php` quittiert nach einem kollidierten Stück nur, was liegt (`spur_naechste_seq()`); (2) `pair.php` antwortet auf `status`, `bestaetigen` und `trennen` mit 403 `device_disabled` bzw. `konto` wie `ingest.php`; (3) das Handy wertet 400/401/403 nur mit dem `error`-Feld des Vertrags als endgültig, alles andere ist „später erneut"; vor dem Räumen nach Fristablauf ein Sendeversuch | 10.11.2026, BetreiberIn |
| E-KA-27 | **Die Wear-Uhr erfährt „Handy nicht gekoppelt" über ein neues Feld `gekoppelt` der Standmeldung** und zeigt „Handy nicht gekoppelt — Dienst nicht begonnen" | 10.11.2026, BetreiberIn |
| E-KA-28 | **Ein „Dienst beginnen" der Wear-Uhr wirkt ohne laufenden Dienst nur, wenn es höchstens 12 Stunden alt ist** — ein spät zugestelltes Ereignis derselben Schicht wirkt, eines von gestern nicht | 10.11.2026, BetreiberIn |
| E-KA-29 | **Das Handy deckelt `next_seq` auf `seq_from` plus gesendete Punkte**, wie KG es für die Uhr tut | 10.11.2026, BetreiberIn |
| E-KA-30 | **Ein im Web deaktiviertes Gerät trennt sich nicht mehr selbst** — sonst bliebe der Weg aus F-KA-05 offen; gelöscht wird es im Web | 10.11.2026, BetreiberIn |
| E-KA-31 | **Fassung 2 wird abgeschaltet, ohne Produktiv vorher zu zählen** (E-KA-16). Örtlich 0 von 6 Paketen in Fassung 2; Fassung 3 gibt es seit Web 20.2.0, je Konto bleiben zwei Pakete — ein Fassung-2-Paket kann es nur noch als „Backup ohne Konto" von davor geben. Der CHANGELOG sagt es | 11.11.2026, BetreiberIn |
| E-KA-32 | **Grenze des Demo-Kontos: 500 Einsätze und 50 MB GPS-Daten** — Puffer für spätere Erweiterungen der Vorlage (Vorschlag war 200 / 20) | 11.11.2026, BetreiberIn |
| E-KA-33 | **Die Passphrase des Komplett-Downloads braucht `PW_MIN_LAENGE` Zeichen** (12), wie ein Kontopasswort | 11.11.2026, BetreiberIn |
| E-KA-34 | **Ein CSP-Bericht trägt höchstens 20 Einträge**, der Rest wird verworfen | 11.11.2026, BetreiberIn |
| E-KA-35 | **SheetJS 0.20.3 vom Hersteller (`cdn.sheetjs.com`)**; der Host ist dafür in die Netzfreigabe der Arbeitsumgebung aufgenommen (11.11.2026, Freigabe der BetreiberIn). **Uhr-Rundläufe und `pruefablauf.json` gehören KG** — KA fährt keine Uhr-Proben und meldet nur vorher, wenn es Geräte-Endpunkte ändert | 11.11.2026, BetreiberIn |
| E-KA-36 | **Der Wächter in Bibliotheken und Teil-Vorlagen vergleicht `SCRIPT_FILENAME` mit `__FILE__`**, statt einer Konstante (Konzept: „Wächterkonstante"). Eine Konstante hätte jede Einstiegsseite gebraucht, und ein Wächter über `get_included_files()` hätte jedes Werkzeug getroffen, das per `php -r` eine Bibliothek zuerst einbindet; im CLI greift er nicht | 11.11.2026, Umsetzung |
| E-KA-37 | **Auch die unversiegelte Begleitdatei `konto.json` ist unlesbar** — über Punkt 7 hinaus: Seit KA-03 entscheidet ihre Adresse über eine Freigabe, und der Rückfall war derselbe wie beim Manifest | 11.11.2026, Umsetzung |
| E-KA-38 | **KA-06a schreibt ein Unter-Agent auf Fable**, die Hauptinstanz bleibt auf Opus und übernimmt Ergebnis, Konzept und Dokumente. Der Agent schreibt nur unter `tools/proben/huelle/`, keinen Anwendungscode und keine `docs/` | 11.11.2026, BetreiberIn |
| E-KA-39 | **Die drei Punkte zur Ableitung ohne Formatwechsel gehen vorab** als Teil von 06b mit Opus (F-KA-13, -16; Konzept 06b: „vorab als eigenes kleines Paket"). **F-KA-29 bleibt draußen** — ein neues Anmelde-Token gehört zum Entwurf von 06a | 11.11.2026, BetreiberIn |
| E-KA-40 | **Der Altbestand der BetreiberIn zieht still nach der Anmeldung um** (Anhebelauf wie `pat_anheben`), und **alte Sicherungen bleiben einspielbar** — der Browser hebt sie beim Einspielen (06d). *(Den zweiten Teil hat E-KA-60 am 13.11.2026 aufgehoben: Alte Sicherungen müssen nicht mehr einspielbar sein.)* Beantwortet die offene Frage aus E-KA-16; übernommen mit „alle wie vorgeschlagen" aus den Vorschlägen des Konzepts (06b, 06d) — wo die BetreiberIn es anders meinte, wird es vor 06b berichtigt | 11.11.2026, BetreiberIn |
| E-KA-41 | **Untergrenze der Rundenzahl 310 000** — der kleinste Wert, den je ein Konto (Vorgabe der Spalte `kdf_iter`) oder eine Sicherung trug; 600 000 hätte Sicherungen von davor unlesbar gemacht. **Frist des Vormerkfachs sechs Minuten** — die fünf der halben Anmeldung (`TOTP_HALB_FRIST_S`) und eine für den Seitenwechsel. Eine Antwort des Salz-Endpunkts in falscher Form meldet die Seite als „derzeit nicht möglich" (`status`), nicht als fehlendes Web-Krypto | 11.11.2026, Umsetzung |
| E-KA-42 | **Das Muster für `vendor/` in `pruefablauf.json` (F-KA-74) kommt mit dem nächsten Web-Paket**, sobald KG die Datei nicht mehr in Arbeit hat | 11.11.2026, BetreiberIn |
| E-KA-43 | **Zählerstand (Q-KA-07):** im Browser (IndexedDB, je Konto), dazu eine verschlossene Abschrift in 16 Blättern auf dem Server (`edk2`, Zweck `stand`); das Blatt folgt aus dem Hash der `client_ref`. Preis: Migration (`update.php`), ein 409-Weg, Umdatieren, Schneiden und Löschen nur entsperrt | 11.11.2026, BetreiberIn (KA-M5) |
| E-KA-44 | **Herkunft (Q-KA-08):** authentifizierte Hülle mit zwei ECDH, Kopplung mit Zusage und sechsstelligem Vergleichswert; **die Webseite verlangt einen eigenen Klick „Wert stimmt"**; der Demo-Bestand bekommt ein **Demo-Gerät** mit festem Schlüssel in der Fixture. Die neuen Anzeigen an Gerät und Webseite brauchen Mockup und Freigabe (`CLAUDE.md` 5) | 11.11.2026, BetreiberIn (KA-M5) |
| E-KA-45 | **Geräte ohne Schlüssel (Q-KA-09):** kein Nachreichen über `status`, sondern neu koppeln; bis dahin Fassung 1, im Browser „ohne Herkunftsnachweis", höchstens 30 Tage nach Auslieferung der Gerätefassungen | 11.11.2026, BetreiberIn (KA-M5) |
| E-KA-46 | **Stichtag Fassung 1 (Q-KA-10):** Der Server weist Gerätehüllen der Fassung 1 am Ende der Frist aus E-KA-45 mit 400 ab; der Browser liest Fassung 1 und `edk1`, bis der Anhebelauf jedes Blatt geschlossen hat — danach ist Altformat ein Befund | 11.11.2026, BetreiberIn (KA-M5) |
| E-KA-47 | **Rundenzahl 1 200 000 mit Ableitung 2 (Q-KA-11, F-KA-29):** ein PBKDF2-Block, Hälfte und Token per HKDF; Umstellung still beim Anmelden, während der Umstellung rechnet die Anmeldung beide Listenwerte | 11.11.2026, BetreiberIn (KA-M5) |
| E-KA-48 | **Die Entwurfsentscheidungen V-KA-01 bis -13 gelten** (Prüfprotokoll, KA-06a), darunter zwei Abweichungen von der Skizze: Verkettung der Stückfolge über die Deckung statt über einen Hash, keine Kontokennung in den Zusatzdaten | 11.11.2026, BetreiberIn (KA-M5, „alles wie vorgeschlagen") |
| E-KA-49 | **Den Krypto-Kern von 06b schreibt wieder ein Fable-Agent**, in eigener Arbeitskopie; Server-Endpunkte, Migration, Anhebelauf und Oberfläche baut die Hauptinstanz mit Opus. Eine Weiche hält das Schreiben im neuen Format aus, bis alle Leser es können | 11.11.2026, BetreiberIn |
| E-KA-50 | **Nach einem zurückgesetzten Server schreibt der Browser seinen Stand zurück** (`EdStandbuch.zurueckschreiben()`) — nur über einen eigenen Knopf mit Rückfrage der BetreiberIn, dazu ein Satz im Runbook. Ohne ihn wäre nach jeder eingespielten Sicherung jedes Blatt dauerhaft „zurückgelegt". Der Knopf ist ein neuer Bedienweg und braucht Mockup und Freigabe | 11.11.2026, BetreiberIn |
| E-KA-51 | **06b in vier Teilen, je ein PR:** 06b-1 Kern einbinden (Weiche aus), 06b-2 Server für das Standbuch (Tabelle, `api/standbuch.php`, Datensatz und Blatt in einer Transaktion, 409; Migration, `update.php`), 06b-3 Verbraucher umstellen und Ableitung 2 am Server, 06b-4 Anhebelauf. **Die Weiche wird erst umgelegt, wenn auch 06c und 06d stehen** — als Hauptstufe Web 25.0.0 | 11.11.2026, BetreiberIn |
| E-KA-52 | **Entscheidungen des Krypto-Kerns** (Fable-Agent, E-KA-49): (1) die Fassung der Ableitung folgt aus der Rundenzahl — ab 1 200 000 Fassung 2 (`ABLEITUNG2_AB`), keine eigene Spalte; (2) die Weiche ist eine Konstante im Skript, kein Wert vom Server; (3) das Buch vergisst nichts — beim Übernehmen eines neueren Blatts gilt je Feld der höhere Wert (strenger als die Referenz); (4) 409 heißt neu aufsetzen, vier Versuche, dann `konflikt`; (5) die Ablage hält den Stand verschlossen, eigene Datenbank `nadoku-stand`; (6) neuer Grund `blatt-version`; (7) `abgleichen()` liefert Befunde statt zu werfen; (8) `EdCrypto.decrypt()` weist `edk2:` mit Grund `gebunden` ab; (9) Formprüfungen strenger als die Referenz — wo die Referenz lockerer war, ist sie mit 06b-1 nachgezogen (F-KA-90) | 11.11.2026, Umsetzung |
| E-KA-53 | **Die Abschrift des Standbuchs liegt in einer eigenen Tabelle** (`stand_blaetter`, PK Konto und Blatt, Kaskade mit dem Konto), nicht in `users` oder `app_state`: 17 Zeilen je Konto mit eigener Version. Die Versionsregel ist **eine Anweisung je Fall** (`INSERT IGNORE` für ein neues Blatt, `UPDATE … WHERE version = ?` sonst) statt einer Transaktion mit Lesen und Schreiben. Decke 256 KB Innenlänge je Blatt. **Nicht im Konto-Backup** — ein eingespieltes Backup bekommt im Browser neue Zähler (06d); der Demo-Reset räumt die Blätter mit | 11.11.2026, Umsetzung |
| E-KA-54 | **06b-3 als ein Paket mit einem PR und einem Prüfstand**, innen drei Commits (Patientenblock, Schlüssel und Ableitung, Ortsdaten) — statt drei PRs mit je einem vollen Lauf | 12.11.2026, BetreiberIn |
| E-KA-55 | **Nach dem Abbruch des Agenten setzt die Hauptinstanz fort** (Variante 2): Der Agent (Opus) hatte 06b-3a angefangen; ein Neustart des Containers beendete ihn, und er ließ sich nicht fortsetzen. Sein Stand ist als Zwischencommit übernommen und durchgesehen | 13.11.2026, BetreiberIn |
| E-KA-56 | **Vorgriff auf 06d, nur die Form:** Der Server nimmt `edk2:` (Patientenblock ab Klasse 512), `edka2:` (nur Passworthülle; `rc`, `kp`, `rw` nie) und die Gerätehülle der Fassung 2 an. Ohne das ließe sich kein Schreibweg des Browsers mit umgelegter Weiche Ende zu Ende prüfen. Geräte senden Fassung 2 erst nach 06d (JSON-Vertrag 3.4) | 13.11.2026, Umsetzung |
| E-KA-57 | **Das Ziel der Rundenzahl bleibt 600 000, bis die Weiche umliegt** — `crypto.js` wählt Ableitung 2 nach der Rundenzahl, und ein höheres Ziel änderte das Anmelde-Token sofort. PHP rechnet Ableitung 2 schon (`kdf_haelfte()`); das Heben ist dann eine Zeile | 13.11.2026, Umsetzung |
| E-KA-58 | **Die Kennung des Kontos für die Ablage des Standbuchs kommt aus dem Inhaltsschlüssel** (AES-GCM-Prüfwert über nichts, davon SHA-256), nicht vom Server — eine Kennung, die der Server nennt, könnte er wechseln, und der Browser fände keinen Anker. **Datensatz und Blätter in einer Anfrage** (Feld `blaetter` mit `refs`); der Server prüft, dass jeder Block steht, dessen Zähler ein Blatt hebt (`uebersprungen`), sonst 409 `blatt` und nichts gespeichert. **Wiederherstellung und Übernahme lesen ohne Standbuch** — ein eingespielter Stand ist mit Absicht älter — und schreiben mit neuem Zähler | 13.11.2026, Umsetzung |
| E-KA-59 | **Der Anhebelauf läuft still nach der Anmeldung und nur mit umgelegter Weiche** (eigener Endpunkt `api/format_anheben.php`, Runden zu 200). Er öffnet jeden neuen Block vor dem Senden und vergleicht; der Server schreibt nur bei unverändertem `blob_alt`. Ein Blatt, für das der Server keinen Altbestand mehr nennt, wird geschlossen. Gerätedaten bleiben bei 06e | 13.11.2026, BetreiberIn („alles wie vorgeschlagen") |
| E-KA-60 | **Alte Sicherungen müssen nicht mehr einspielbar sein** — berichtigt E-KA-40 in diesem Punkt. Daraus: Sobald der Anhebelauf einmal durchgelaufen ist und alle 16 Blätter geschlossen sind, werden vor v1.0 **der Anhebelauf und das Lesen des alten Formats (`edk1:`, Gerätehülle Fassung 1) zurückgebaut** — mit 06e oder KA-07. Eine Sicherung im alten Format wird dann mit einer Meldung abgewiesen statt gehoben | 13.11.2026, BetreiberIn |
| E-KA-61 | **06d kommt vor 06c, und die Webseite der Kopplung geht mit 06d** (Vergleichswert, „Wert stimmt“, Eintrag ins Gerätebuch, Demo-Gerät). Grund: Die Garmin-Uhr (KG-03b) braucht für eine vollständige Kopplung den Server **und** die Webseite; die Rechnung dafür liegt seit 06b-1 in `huelle2.js`, es ist Verdrahtung, kein neuer Krypto-Kern (Opus). 06c behält nur das Handy. Vorab gehen der Vertragsentwurf an KG und das Mockup an die BetreiberIn; gebaut wird nach der Freigabe, in einem PR mit einem Prüfstand | 13.11.2026, BetreiberIn |
| E-KA-62 | **Ein Skript räumt die Testkonten auf Staging auf** (`tools/sandbox/staging_konten.py`, Nr. 564), und `docs/Pruefablauf.md` 2.4 sagt, dass jede Sitzung es am Ende fährt. Es löscht alles außer einer Liste, die es behält; ohne `--wirklich` zeigt es nur an. Geht mit dem PR von 06d | 13.11.2026, BetreiberIn |
| E-KA-63 | **Entwurf 06d und Mockup M-KA-01 freigegeben**, mit den drei Ergänzungen aus KG (`offenlegen` wiederholbar, Zustände von `status`, `huelle: 2` am gekoppelten Gerät); Zustand F bleibt: Ein Gerät mit älterer App koppelt bis zum Stichtag aus 06e mit Warnung („Trotzdem verbinden") | 13.11.2026, BetreiberIn |
| E-KA-64 | **Der Zähler `z` ist die Unix-Sekunde des Verschließens, lokal streng steigend** (`max(jetzt, letztes z + 1)`) — beantwortet Q-KA-13 unter Vorbehalt: Ein Fable-Agent prüft die Regel mit 06c gegen die Referenz | 13.11.2026, BetreiberIn |
| E-KA-65 | **Die Liste der Konten, die das Aufräumskript behält, steht in `NADOKU_STAGING_BEHALTEN`** (Umgebung der Arbeitsumgebung, nicht im Repositorium) — beantwortet Q-KA-14; das eigene Konto und `demo@…` bleiben immer | 13.11.2026, BetreiberIn |
| E-KA-66 | **Mockup M-KA-02 (Kopplung mit Vergleichswert am Handy) freigegeben** — sechs Zustände A bis F, nur vorhandene Bausteine der App; „Herkunft gesichert" als zweite Zeile der Sync-Karte | 14.11.2026, BetreiberIn |
| E-KA-67 | **Der Krypto-Kern des Handys und die Gegenprüfung des Zählers gehen an einen Fable-Agenten**; die Hauptinstanz bleibt auf Opus und baut Kopplungsablauf, Anzeige, Senden, Dokumente (wie E-KA-38, E-KG-26) | 14.11.2026, BetreiberIn |
| E-KA-68 | **F-KA-108 kommt in denselben PR wie 06c** — spart einen Prüfstandlauf | 14.11.2026, BetreiberIn |
| E-KA-69 | **Der Zähler `z` wird an den Inhalt gebunden** (beantwortet Q-KA-16, ändert E-KA-64): vergeben, wenn sich der Inhalt eines Einsatzes ändert, und mit dem Paket gespeichert — nicht mehr bei jedem Verschließen. Weiter Unix-Sekunde, lokal streng steigend. Ein Gerät, das nach einer Übergabe einen alten Stand nachsendet, trägt dessen alten Zähler und verliert. Gilt für Uhr (über KG) und Handy; die Referenz zuerst. Dazu meldet der Browser eine Hülle mit `schluss: false` nach einer mit `schluss: true` desselben Einsatzes als Befund | 15.11.2026, BetreiberIn |
| E-KA-70 | **Der Browser bekommt einen Rückweg bei einem zu hohen Zählerstand** (beantwortet Q-KA-15): Das Standbuch merkt sich zum Stand die SID des Absenders. Kleineres `z` vom **gleichen** Absender bleibt hart „zurückgelegt"; vom **anderen** Absender ein Hinweis „Uhrzeit von ‹Gerät› prüfen" mit beiden Zeiten und der bewussten Bestätigung „diesen Stand übernehmen", vermerkt im Standbuch. Kein stilles Kappen auf „jetzt + Toleranz". Kommt mit dem Umlegen der Weiche (Web 25.0.0), dort auch F-KA-112 | 15.11.2026, BetreiberIn |
| E-KA-71 | **Der Geräteschlüssel des Handys liegt im Android Keystore** (beantwortet Q-KA-17, F-KA-85): nicht exportierbar, ECDH im Keystore (`PURPOSE_AGREE_KEY`), **ohne Entsperrpflicht** (das Senden im Hintergrund darf nicht an der Sperre hängen). Der Versuch einer Kopplung bekommt einen eigenen Keystore-Eintrag, der beim Übernehmen zum gültigen wird | 15.11.2026, BetreiberIn |
| E-KA-72 | **`minSdk` des Handy-Moduls steigt von 26 auf 31** (Android 12) — es gibt dann nur einen Weg für den Geräteschlüssel, keinen Tresor-Rückfall. Handys vor Android 12 bekommen die App ab dieser Fassung nicht mehr. Das Uhr-Modul bleibt bei 30 (es hält keinen Schlüssel, E-S4-11) | 15.11.2026, BetreiberIn |
| E-KA-74 | **Was „Inhalt geändert“ heißt, und das Standbuchfeld `f`** (Fable-Agent, 06f-1): Inhalt ist alles innen außer `z` (Phasen, Reanimation, `schluss`, `start`); daraus zwei Messsätze je Absender — gleiches `z` ⇒ innen byte-gleich, anderer Inhalt ⇒ größeres `z`. Für den Befund merkt sich das Standbuch je Einsatz `f` = 1, sobald eine Hülle mit `schluss: true` abgenommen ist; es haftet und fügt sich zusammen wie `zu` (offen: Q-KA-18, -19) | 15.11.2026, Umsetzung |
| E-KA-75 | **Keystore-Ablage des Handys** (Fable-Agent, 06f-2): je Paar ein nie wiederverwendeter Alias `nadoku-geraet-<Zufall>`; welcher gilt und was der Versuch trägt, steht in `absender.bin` unter dem Tresorschlüssel, ohne privaten Schlüssel; gültig nur, was die Datei nennt und der Keystore mit demselben `spk` führt; Übernehmen schreibt erst die Datei, dann löscht es den alten Alias; Waisen räumt `beginnen`/`vergessen`, nicht der Start. `Z_s` wird je Hülle im Keystore gerechnet, nie abgelegt. Fällt der Keystore beim Senden aus, sendet der Lauf nichts (`Schluesselausfall`, Sender) | 15.11.2026, Umsetzung |
| E-KA-76 | **`f` haftet, und das Konto trägt `schluss` weiter** (beantwortet Q-KA-18, -19): Das Standbuchfeld `f` wird nie gelöscht; verschließt der Browser einen Einsatz, zu dem `f` steht, setzt er `schluss: true` — der Befund `offen-nach-schluss` gilt damit für jeden Absender und meldet nur echte Rückfälle | 15.11.2026, BetreiberIn |
| E-KA-77 | **Das Release-APK bleibt komprimiert** (`packaging.dex.useLegacyPackaging = true`, F-KA-118): rund 9,3 MB statt 24,8 MB über `server/apk/`; Preis ist die etwas längere Installation | 15.11.2026, BetreiberIn |
| E-KA-78 | **Zähler am Handy** (Fable-Agent, 06f-3): Spalte `paket.zaehler` (Schema 3), vergeben in derselben Transaktion wie Anlegen, Phase und Abschluss eines offenen Einsatzes; kein Bytevergleich (die drei Stellen ändern den Inhalt immer); Schema-2-Pakete bekommen ihn beim ersten Verschließen; der Sender liest Paket, Zähler und Phasen aus einem Augenblick (`einsatzstand()`); Zählerdatei nicht schreibbar ⇒ Änderung steht ohne Zähler, Senden wartet (`Zaehlerausfall`) | 15.11.2026, Umsetzung |
| E-KA-79 | **06e kommt ganz nach Web 25.0.0, in einem Zug** — Übernahme der Gerätedaten im Anhebelauf, Stichtag am Server und Rückbau des Altformats (E-KA-60). Stichtag und Rückbau setzen voraus, dass die Weiche umliegt und der Anhebelauf auf Produktiv einmal durch war; ändert die Reihenfolge in Abschnitt 4 (bis dahin 06e vor Web 25.0.0) | 15.11.2026, BetreiberIn |
| E-KA-80 | **Ein Handy mit `huelle: 2`, aber ohne Geräteschlüssel, sendet nichts und zeigt „neu koppeln“** (beantwortet Q-KA-20): kein Rückfall ohne Herkunftsnachweis; die Daten bleiben im Puffer. Gebaut mit Web 25.0.0 bzw. der Android-Fassung dazu | 15.11.2026, BetreiberIn |
| E-KA-81 | **Das Kennzeichen „vom Browser übernommen am …“ wird mit den übrigen Befunden gezeigt** (E-KA-70), ein Mockup für alles beim Umlegen der Weiche; 06e schreibt es nur in die Hülle | 15.11.2026, BetreiberIn |
| E-KA-82 | **Web 25.0.0 in acht Paketen KA-08 bis KA-15, am Stück, ein gesammelter PR**; der einzige Halt ist die Freigabe des Mockups M-KA-03 (`CLAUDE.md` 5) — KA-09 und KA-10 hängen nicht daran und laufen weiter | 15.11.2026, BetreiberIn |
| E-KA-83 | **F-KA-103: Geräte, die vor dem Umlegen gekoppelt wurden, koppeln neu** — kein Weg „Wert nachträglich bestätigen“; ein Satz in Handbuch und Runbook (es gibt eine Nutzerin, E-KA-16) | 15.11.2026, BetreiberIn |
| E-KA-84 | **Das Rundenziel steigt mit der Weiche auf 1 200 000** (Ableitung 2, E-KA-57); die Dauer der Anmeldung wird gemessen | 15.11.2026, BetreiberIn |
| E-KA-85 | **Den Krypto-Teil von KA-09, KA-10 und KA-15 schreibt ein Fable-Agent**, die Hauptinstanz bleibt auf Opus und prüft nach (wie E-KA-67, -73) | 15.11.2026, BetreiberIn |
| E-KA-86 | **E-KA-80 gilt auch für die Garmin-Uhr** und geht zusammen mit KG-05 an eine **neu gestartete** KG-Sitzung | 15.11.2026, BetreiberIn |
| E-KA-87 | **Mockup M-KA-03 (Befunde in der Einsatzansicht, „neu koppeln“ am Handy) freigegeben** — sieben Zustände A bis G, nur vorhandene Bausteine; in B zeigt die Karte erst nach der Entscheidung, „Nicht jetzt“ lässt alles stehen | 15.11.2026, BetreiberIn |
| E-KA-88 | **Der Rückweg in der Referenz** (Fable-Agent, KA-09): Standbuchfeld `a` (SID des Geräts, das `o` gesetzt hat; `k` bleibt, beide schließen einander aus) und `u` (Vermerke `[[z_v, sid, t]]` über bewusst verworfene Stände); Feldfolge p, o, k, a, f, u, s, d, x. Kleineres oder gleiches `z` von einem **anderen Gerät** ist der Hinweis `uhrzeit-pruefen` (Arten `kleiner`, `gleich`, `verworfen`); gleicher Absender, Konto und Altblatt ohne `a` bleiben hart `zurueckgelegt`. Die Bestätigung ist der Hinweis selbst und gilt nur, solange Hülle und Stand so stehen; neuer Befund `stand-uebernommen`, neuer Fehlergrund `bestaetigung`. Zwei Blätter führt `stand_zusammenfuehren()` zusammen, nicht mehr „höherer Zähler“ | 15.11.2026, Umsetzung |
| E-KA-89 | **Kein Serverumbau gegen das „wiederkehrende“ alte Paket** (Zweifel 1 aus KA-09): Ein Gerät sendet ein Paket nur, bis der Server es angenommen hat, und entsorgt es dann; seit E-KA-69 trägt jede Wiederholung dasselbe `z`, und nach der Bestätigung geht genau diese Hülle durch („nach-bestaetigung-dieselbe-huelle“). Neu erscheint der Hinweis erst bei einer echten Änderung am Gerät mit nachgehender Uhr — das ist der Zweck des Hinweises | 15.11.2026, Umsetzung |
| E-KA-90 | **Demo-Bestand: neu verschließen statt Einspiellauf umbauen** (Fable-Agent, KA-13): `einspielen.py` schreibt weiter Fassung 1 über die regulären Wege; `fixture/verschliessen.py` öffnet das ausgelesene Konto und verschließt alles neu über `referenz.py`/`referenz_inhalt.py` — Fixture-Format 4 mit Gerätehüllen Fassung 2 von zwei Demo-Geräten mit festem Schlüssel, `edk2:`-Blöcken, 16 geschlossenen Blättern, Gerätebuch, Kontopaar und 1 200 000 Runden; byte-gleich bei zweimaligem Bau. Kopplung über `pair.php` und Ingest in Fassung 2 im Einspiellauf sind **nicht** gebaut (Backlog Nr. 567) | 15.11.2026, Umsetzung |
| E-KA-91 | **Der Demo-Reset wechselt den Inhaltsschlüssel und setzt die Änderungsmarke nicht beim Lesen** (KA-13): Ohne Wechsel sähe ein Browser, der vor dem Reset gespeichert hatte, danach `blatt-zurueckgelegt` (gemessen); `api/standbuch.php` `stand` und `holen` setzen die Marke (Nr. 459) nicht mehr, `ablegen` weiter | 15.11.2026, Umsetzung |
| E-KA-92 | **Ein Gerätebuch, das nicht aufgeht, repariert der Browser selbst** (Backlog Nr. 568, Mockup M-KA-04 Fassung 2): Hat er eine eigene gute Fassung, schreibt er sie still zurück; hat er keine, legt er still ein neues, leeres Buch an — nichts vom Server übernommen — und zeigt „Geräte neu verbinden“ auf Geräteseite und Startseite, bis jedes gekoppelte Gerät neu gekoppelt ist. Die BetreiberInnen sollen das Wort Gerätebuch nicht kennen müssen. Preis: früher gesendete Gerätedaten bleiben unlesbar (sie sind es bei einem unlesbaren Buch ohnehin); wer den Server beschreibt, kann so ein Neukoppeln erzwingen (Verfügbarkeit, keine Zusage) | 16.11.2026, BetreiberIn |
| E-KA-93 | **06e wird jetzt gebaut, der PR wartet** — gemergt erst, wenn `web-v25.0.0` auf Produktiv liegt, der Anhebelauf dort durch ist und die Geräte neu gekoppelt sind (P-KA-18, -19); bis dahin bleibt `main` für Korrekturen an 25.x frei. Präzisiert E-KA-79 | 16.11.2026, BetreiberIn |
| E-KA-94 | **`k` haftet beim Zusammenführen wie `f`** (Q-KA-21, F-KA-119): Ein Gerätestand nach dem Konto bleibt abgewiesen, auch wenn ein anderer Browser einen höheren Gerätestand kannte. Regel in Referenz und Vektoren, dann im Browser | 16.11.2026, BetreiberIn |
| E-KA-95 | **Eine Lücke ohne Löschvermerk in übernommenen Altdaten bekommt einen engen Vermerk des Kontos** (Q-KA-22): Der Browser setzt beim Übernehmen einen Löschvermerk genau über die Lücke; die Leseregel bleibt | 16.11.2026, BetreiberIn |
| E-KA-96 | **Der Garmin-Teil von 06e wird in dieser Sitzung gebaut**, nicht über KG | 16.11.2026, BetreiberIn |
| E-KA-97 | **Das Altformat bleibt in 06e nur im Anhebelauf lesbar** — `edk1:` und Gerätehülle Fassung 1 öffnet nur noch der Lauf, der sie hebt; Anzeige, Export und alle Schreibwege verlangen Fassung 2. Sonst wäre ein Konto, das sich zwischen 25.0.0 und 26.0.0 nicht angemeldet hat, danach unlesbar. Ganz entfernt wird das Lesen mit KA-07. `edk1:` in `pat_wrap_rc` bleibt (nur ein Schlüsselwechsel hebt es) | 16.11.2026, Hauptinstanz (folgt aus E-KA-60, -79) |
| E-KA-98 | **Nur das eigene Konto verlangt Fassung 2; der Freigabeweg liest weiter beide** — „eigen“ heißt: Das Paar trägt einen Inhaltsschlüssel und damit ein Standbuch (`k.ck`). Ein fremdes Paar im Freigabeweg hat kein Buch, und ein Konto, das sich nie wieder anmeldet, hebt niemand. Die Anzeige „Vom Browser übernommen am …“ (M-KA-03 D) war in Web 25.0.0 nicht gebaut und kommt mit KA-18 | 16.11.2026, Hauptinstanz (folgt aus E-KA-97) |
| E-KA-99 | **Alte Fixture, Zweig `'server'` in `edbak_restore()` und `geraetehuelle_lib.php` bleiben bis KA-07** — sie sind die einzige Testquelle der Übernahme, die bis dahin bleibt. Der Stichtag gilt für jeden Schreibweg von außen; nur dieser innere Zweig darf für die Dauer des Aufrufs Fassung 1 und `edk1:` schreiben (`huelle_fassung1_zulassen()`). Berichtigt den Zuschnitt von KA-20 | 16.11.2026, Hauptinstanz |
| E-KA-100 | **Eine Sicherung, die vor Web 26.0.0 angelegt wurde, wird ganz abgewiesen** (gefragt: `manifest.web_version`), mit der Bitte, nach dem Update eine neue anzulegen — kein Übernehmen beim Einspielen, kein Weglassen der Gerätedaten. Präzisiert E-KA-60 | 16.11.2026, BetreiberIn |
| E-KA-73 | **E-KA-69, -71 und -72 kommen als eigenes Paket KA-06f vor 06e**, in einer neuen Instanz; der Krypto-Teil (Keystore-Umsetzung von `Geraeteschluessel`, Zählerregel in Referenz und Handy) geht wieder an einen Fable-Agenten (wie E-KA-67), der Garmin-Teil von E-KA-69 an KG (E-KA-21) | 15.11.2026, BetreiberIn |
| E-KA-66 | **Gerätebuch und „Wert stimmt“ gehen in zwei Schritten, nicht in einer Transaktion:** erst das Buch (über den vorhandenen Weg `geraetEintragen()`), dann der Vermerk am Server mit `im_buch`. Ein Gerät am Server ohne Buch-Eintrag sendete Daten, die nicht aufgehen; ein Buch-Eintrag ohne Gerät räumt der Abgleich der Geräteseite auf (`bis`). Weicht vom Entwurf 06d, Punkt 5 ab | 13.11.2026, Umsetzung |
| E-KA-67 | **Den Anschluss des leeren Schlussstücks prüft der Browser beim Lesen (Regel `luecke`), nicht der Eingang** — der Eingang prüft Form (Fassung 2, `ts_von` = `ts_bis`). Eine Abfrage des letzten Stücks im Eingang kostete jede Anfrage und belegte nichts, was der Browser nicht ohnehin prüft. Weicht vom Entwurf 06d, Punkt 8 ab | 13.11.2026, Umsetzung |
| E-KA-21 | **Die Garmin-Anteile gehen an eine eigene Instanz, Kürzel KG** (Abschnitt 11): KA-04 Punkt 1 und 4, der Garmin-Teil von KA-06c samt der Messung für 06a, die Garmin-Kommentare aus F-KA-43 und der Garmin-Teil von KA-M6. KG zählt eigene Nummern (`KG-NN`, `F-KG-NN`, `E-KG-NN`, `P-KG-NN`), führt ein eigenes Konzept und schreibt den Rahmenplan nicht — das tut diese Instanz | 10.11.2026, BetreiberIn |

---

## 3. Was am 10.11.2026 nachgemessen wurde (Antwort auf Q-KA-02)

Gelesen über die Schnittstelle von GitHub, nur lesend.

| Was | Stand |
|---|---|
| Repositorium | **öffentlich**, Forks erlaubt, heute keiner — die Lauf-Protokolle liest jeder |
| `main` | Rulesets „Main Protect" (PR-Pflicht, Pflichtprüfung „Stufe 1" strikt, kein Löschen, kein Force-Push, kein Bypass) und „Main Merge-Recht" (Schreiben nur per PR, Bypass ein Konto) |
| Zeiger `produktion` | Ruleset „Zeiger produktion" seit 06.11.2026 — **nur gegen Löschen**; schreiben und umsetzen darf, wer Schreibrecht hat |
| Tags | **drei Rulesets seit 10.11.2026**, gemessen über die Schnittstelle: aktiv, Ziel `refs/tags/web-v*`, `refs/tags/android-v*`, `refs/tags/uhr-v*`; Anlegen, Ändern und Löschen gesperrt, kein Force-Push; Bypass ein Konto (das der BetreiberIn) |
| Umgebung `produktion` | aus der Arbeitsumgebung nicht lesbar (der Proxy sperrt den Pfad) — **von der BetreiberIn abgelesen:** eine Pflichtprüferin (ihr Konto), „Prevent self-review“ aus, Umgehen durch Administratoren aus. „Deployment branches and tags“ stand auf „No restriction“ und ist **seit 10.11.2026 beschränkt** auf den Zweig `main` und die Tags `web-v*`, `android-v*`, `uhr-v*` („1 branch and 21 tags allowed“ — 13, 4 und 4 Tags, mit dem Repositorium abgeglichen) |
| Freigabe von Fork-Läufen | **von der BetreiberIn abgelesen:** „Require approval for all external contributors“ — die strengste der drei Stufen |
| Rechte des Lauf-Tokens | abgelesen: nur lesend; Läufe dürfen keine Pull Requests anlegen oder freigeben |
| Erlaubte Aktionen | abgelesen: alle; „Require actions to be pinned to a full-length commit SHA“ **aus** |
| Aufbewahrung der Lauf-Protokolle | abgelesen: 90 Tage |

Die abgelesenen Zeilen stammen aus vier Bildschirmfotos der BetreiberIn vom
10.11.2026, nicht aus einer eigenen Messung.

**Folgen:**

- **F-KA-25 wiegt schwerer**, weil das Protokoll öffentlich ist — und was
  die Kette bis zu KA-01 gedruckt hat, bleibt 90 Tage lesbar (KA-M2).
- **Der Nebenbefund zu F-KA-24 ist erledigt:** Ein Lauf aus einem Fork
  startet nie ohne Freigabe.
- **Selbstfreigabe ist erlaubt und muss es bleiben,** solange es eine
  einzige BetreiberIn gibt — sonst gäbe niemand ihren eigenen Tag frei. Der
  Preis: Wer unter ihrem Konto handelt, kann auslösen **und** freigeben.
  Die Arbeitsumgebung kann es nicht; der Proxy sperrt die Pfade unter
  `actions/`, soweit versucht (403). Mit einem zweiten Konto mit
  Freigaberecht wird der Haken gesetzt.
- **Die SHA-Pflicht für Aktionen ist heute nur der eigene Riegel**
  (`tools/kettenaufrufe/`). GitHub kann dasselbe auf seiner Seite
  erzwingen; alle 19 fremden Aufrufe erfüllen es schon. Einschalten mit
  KA-M2, nach einem Probelauf.
- Das Bedrohungsmodell nannte den Zeiger „ungeschützt“, was seit dem
  06.11.2026 nur noch für das Schreiben stimmt (nachgezogen).
- **F-R17-04 ist damit für fremde Konten geschlossen:** Ein Lauf von einem
  beliebigen Zweig erreicht die Umgebung nicht mehr, und einen Tag der drei
  Muster legt nur die BetreiberIn an. `main` bleibt zugelassen, weil die
  Probeläufe von dort starten.
- **Was bleibt:** Der Handlauf auf `main` mit freiem Text im Eingang und
  einem Freigabeklick (F-KA-14, KA-01) — und alles, was unter dem Konto
  der BetreiberIn handelt; der Bypass der Rulesets gilt auch dafür.
- **Zwei Anläufe brauchte es:** Die Tag-Muster der Umgebung standen zuerst
  als Zweig-Regeln da (kein Tag hätte ausgeliefert), und die Rulesets
  hatten zuerst kein Ziel. Der Name einer Regel ist nicht ihr Ziel — der
  Bedienweg gehört ins Runbook (KA-01, Punkt 9).

---

## 4. Reihenfolge und Abhängigkeiten

```
KA-01 Kette ──┐
KA-02 Blatt ──┴─► KA-M3 (Tag: beide hohen Befunde auf Produktiv) ─► KA-M4 (Schlüssel erneuern)
KA-03 Verwaltung
KA-04 Geräte: Kontobindung, Sendeweg
KA-05 Mengen, Schreibwege, Fremdbestandteile
KA-06 Bindung und Herkunft (a Entwurf → b Browser → c Geräte → d Server → f Zähler/Keystore
      → Weiche um, Web 25.0.0 → e Altformat; E-KA-73, -79)
KA-07 Modell, Texte, Abschluss
```

- **KA-01 und KA-02 zuerst**, in dieser Reihenfolge: Sie schließen beide
  hohen Befunde. KA-01 fasst nur Kette und Werkzeuge an und stuft keine
  Fassung hoch.
- **KA-03 bis KA-05** hängen nicht voneinander ab; die Reihenfolge folgt dem
  Gewicht. KA-04 fasst `watch/`, `android/` und zwei Serverdateien an und
  darf nach der Faustregel des Rahmenplans (Abschnitt 4) nicht neben einem
  anderen Paket laufen, das `server/` schreibt.
- **KA-06 nach KA-04**, weil beide denselben Gerätecode anfassen und die
  Kontobindung vor dem Formatwechsel stehen muss.
- **KA-07 zuletzt**; das Bedrohungsmodell wird aber **mit jedem Paket**
  fortgeschrieben, das Abschnitt 4 des Modells berührt (`CLAUDE.md` 4) —
  KA-07 liest am Ende das Ganze gegen.

**Buchführung vor dem ersten Paket:** Die Umsetzung reserviert eine
Zehnerspanne im Kopf von `docs/Backlog.md` und pusht sie (E-SD-21). Jede neue
Probe braucht die Zeile „Anlass: Nr. …" (Grundsatz 5 des Prüfablaufs); die
Nummern entstehen aus den Befunden, die eine Probe bekommen.

---

## 5. Arbeitspakete

Je Paket: Ziel, Befunde, Unterpunkte, berührte Auslieferung mit Stufe,
Pflegepflichten, Prüfung, Modell und Fächerung. „Stufe" meint die
Zählweise aus `CLAUDE.md` 2 — ohne Nummer.

### KA-01 Kette — Zertifikat, Eingänge, Riegel

**Ziel:** Die Auslieferung prüft ihr Gegenüber, kein Eingang wird zu Code,
und das Protokoll nennt nichts, was eine Schranke ist.
**Befunde:** F-KA-02, -14, -15, -24, -25, -26, -37, -39, dazu F-KA-44 (neu, Abschnitt 10).

1. `security: strict` am FTPS-Schritt in `ausliefern-lauf.yml`. **Erst
   messen, dann scharf:** ein Probelauf gegen Staging und einer gegen
   Produktiv (Handlauf der BetreiberIn, KA-M2) — schlägt die Prüfung fehl,
   liefert der Hoster ein Zertifikat, das nicht zum FTP-Namen passt, und der
   Name in `FTP_SERVER` ist zu berichtigen, nicht der Schalter.
2. `probelauf_mengenprobe` über `env:` in den Schritt reichen; dazu die
   Regel als Riegel: `tools/kettenaufrufe/` misst, dass kein
   `${{ inputs.* }}` vom Typ `string` und kein `${{ github.event.* }}` in
   einem `run:` steht.
3. Die Kettenwerkzeuge (`zielprobe.py`, `tor.py`, die Aufrufe in Stufe 2)
   lesen FTP-Passwort, Job-Token und Prüfkonto aus der Umgebung statt aus
   den Argumenten.
4. `tor.py` druckt `komplett.datei` und `wartung.von` nicht mehr; die
   Zusammenfassung des Staging-Laufs ebenso.
5. `.claude/settings.json`: `mcp__github__create_or_update_file` und
   `mcp__github__delete_file` in die Deny-Liste. **Die Datei steht damit in
   der Berührung des Pull Requests — gewollt** (`CLAUDE.md` 8).
6. `bericht.py`: Liefert `git rev-parse` nichts, ist das rot; Selbstprobe um
   die Lage erweitern.
7. `fremdsummen.py` nimmt `server/vendor/` (beide Listen) und
   `gradle-wrapper.jar` (Sollwert im Werkzeug) auf.
8. Dienst-Abbilder in `pruefung.yml` am Digest; der Riegel in
   `tools/kettenaufrufe/` sieht künftig auch `image:`.
9. Zwei Einstellungen der BetreiberIn begleiten das Paket (KA-M2): die
   SHA-Pflicht für Aktionen in GitHub einschalten, und die Protokolle der
   Läufe vor Punkt 4 ansehen — sie tragen `komplett.datei` und
   `wartung.von` noch bis zu 90 Tage. Dazu ins Runbook (`docs/Technik.md`
   13), wie die Umgebung und die Tag-Rulesets eingerichtet sind und woran
   man eine wirkungslose Regel erkennt („0 tags allowed“, „does not target
   any resources“) — wer die Anlage in Schritt 14 neu aufsetzt, braucht es.
10. **Zeitlimits (F-KA-44, Auftrag der BetreiberIn vom 10.11.2026):**
    `timeout-minutes` an jedem Job in `pruefung.yml`, Werte aus den
    Laufzeiten der letzten grünen Läufe hergeleitet; prüfen, ob
    `auslieferung.yml` und `ausliefern-lauf.yml` dieselbe Lücke haben, dazu
    `integritaet.yml` (E-KA-13).

**Berührt:** `.github/`, `tools/`, `.claude/` — **keine Fassung** (`CLAUDE.md`
2, Punkt 1). **Pflege:** `CLAUDE.md` 3 (Zahl und Wortlaut zu `uses:` bleiben,
der neue Riegel kommt dazu), `docs/Technik.md` 12, `docs/Pruefablauf.md`,
`tools/…/LIESMICH.md`, Bedrohungsmodell (Zeile Kette, R22).
**Prüfung:** Selbstproben der geänderten Werkzeuge mit je einer neuen roten
Lage; Stufe 1 auf dem PR; **nicht örtlich prüfbar:** Punkt 1 — nur ein
echter Lauf belegt ihn (KA-M2).
**Modell:** Opus. **Fächerung:** keine — acht kleine Änderungen, zwei davon
in derselben Workflow-Datei.

**Umgesetzt (10.11.2026)** — Einzelheiten in Abschnitt 9 und 10:

| Punkt | Was gebaut ist |
|---|---|
| 1 | `security: strict` am FTPS-Schritt; Riegel 6 in `tools/kettenaufrufe/` |
| 2 | Alle Eingänge in `run:` über `env:` (Zielprobe, Gesprächslauf, Schlussschritt, `zeiger`); Riegel 3 (E-KA-14) |
| 3 | `tor.py` liest `JOBS_TOKEN`, `zielprobe.py`/`zustand.py`/`apkablage.py` lesen `FTP_PASSWORD`; `kreislauf.py`, `pruefkonto.py`, Rückwegprobe mit `--…-aus NAME` (E-KA-15); `kreislauf.py` und `aufnehmen.mjs` reichen das Token an `tor.py` über die Umgebung; Riegel 5 |
| 4 | `fuers_protokoll()` in `tor.py`: ohne Zufallsteil, ohne `wartung.von`; `--frage komplett` gibt den Zeitstempel (E-KA-11) |
| 5 | Deny-Liste um `create_or_update_file` und `delete_file` |
| 6 | `bericht.py`: leerer Baum ist rot, Lage 1b |
| 7 | `fremdsummen.py`: beide Listen unter `server/vendor/` (jede Zeile, keine Datei daneben), `Parsedown.php`, Wrapper-JAR |
| 8 | Abbilder am Digest; Riegel 4 |
| 9 | Runbook `docs/Technik.md` 13 („Umgebung `produktion` und Tag-Rulesets"); die zwei Einstellungen der BetreiberIn bleiben KA-M2 |
| 10 | Zeitlimits an allen Jobs aller vier Arbeitsläufe (Abschnitt 10, F-KA-44); Schlussschritt auch bei Abbruch (E-KA-12) |

### KA-02 Notfallblatt im Browser

**Ziel:** Der Wiederherstellungsschlüssel verlässt den Tab nie.
**Befunde:** F-KA-01, -36.

1. Das Blatt entsteht im Browser: eine Seite ohne Formularziel, der Code per
   `textContent` aus dem Speicher der aufrufenden Seite, Druck über
   `window.print()`. Übergabe an ein neues Fenster über einen Weg, der
   **nicht** über das Netz geht (kein POST, keine Adresszeile; die
   Fragmentkennung wäre der einfachste zulässige Weg — sie erreicht den
   Server nicht, steht aber im Verlauf; die Entscheidung fällt im Paket mit
   Begründung).
2. `notfallblatt.php` nimmt keinen `code` mehr an; ein POST mit dem Feld
   antwortet 400 und schreibt nichts mit. Kontoadresse und Anlagenname
   kommen wie bisher vom Server — sie sind kein Geheimnis.
3. Beide Aufrufer (`pw_handling.php`, `schluessel_teile.php`) umstellen;
   Kopfkommentar und `docs/Technik.md` berichtigen.
4. Derselbe Bau für `codeblatt.php`, soweit es Werte spiegelt (F-KA-36).
5. Ein Riegel: Der Name des Feldes, das den Schlüssel trug, kommt in keinem
   Formular mit `action` mehr vor (`tools/quelltext/`).
6. Seitentext nach der Erneuerung: ein Satz, dass ältere Blätter über den
   Server gedruckt wurden (Vorbereitung von KA-M4).
7. **Backlog Nr. 540 mitnehmen:** Notfall- und Codeblatt nehmen im Dunkeln
   Farben des Bildschirms mit (Nachprüfung MA, 08.11.2026). Das Blatt wird
   hier ohnehin neu gebaut; die Abnahme aus Nr. 540 gilt für das neue.

**Umgesetzt (10.11.2026, Web 24.11.0)** — Einzelheiten in Abschnitt 9 und 10:

| Punkt | Was gebaut ist |
|---|---|
| 1 | `assets/blatt-uebergabe.js`: Knöpfe mit `data-blatt-ziel` öffnen das leere Blatt; das Blatt meldet sich beim Ursprungsfenster und bekommt Code und Adresse per `postMessage` (E-KA-17, -18), gesetzt per `textContent` nach Formatprüfung |
| 2 | `notfallblatt.php` liest keinen Anfragewert; alles außer GET bekommt 400 ohne Protokoll |
| 3 | `pw_handling.php`, `schluessel_teile.php`, `rueckfrage.js` ohne Formular; Kopfkommentare, `docs/Technik.md` |
| 4 | `codeblatt.php` und `zweitfaktor_teile.php` ebenso; das Blatt nimmt nur mit Sitzung etwas an |
| 5 | Riegel `druckblatt` in `tools/quelltext/` (E-KA-20), Anlass Nr. 560 |
| 6 | **entfällt** (E-KA-16) |
| 7 | Nr. 540: `.blatt-seite` und `@media print` stellen jede Rolle des dunklen Modus hell (E-KA-19) |

Dazu die Notfallblattprobe (`tools/proben/notfallblatt/`, drei Engines), ein
Hinweis unter dem Knopf, wenn der Browser das Fenster blockiert
(`.rf-druck-hinweis`), und `pruefkonto.py einladen` (Konto ohne gesetztes
Passwort, für die Probe).

**Berührt:** Web, Stufe **neben** (ein Weg durch die Anwendung ändert sich).
**Pflege:** `docs/Handbuch.md` (Verschlüsselung, Notfallblatt),
`docs/Technik.md`, `docs/Design.md` nur falls das Druckbild einen Baustein
ändert (dann Mockup und Freigabe), Bedrohungsmodell (Lagen M und O, R21).
**Prüfung:** Browserprobe in drei Engines — der Code erscheint auf dem
Blatt, und **kein Anfragekörper der ganzen Folge enthält ihn** (die Probe
schneidet die Anfragen mit; das ist die Zahl). Druckbild im Bilderlauf.
Handprüfung: Papier, ein echter Safari.
**Modell:** Opus. **Fächerung:** keine.

### KA-03 Verwaltung — Schranken, die halten, was sie sagen

**Ziel:** Der frische Zweitfaktor hat keinen Nachbarweg; Zusagen der
Verwaltung stimmen mit dem Code.
**Befunde:** F-KA-03, -08, -09, -19, -20, -21, -28, -35, -41, -42.

1. `user_add` mit Rolle ≠ `user` verlangt den frischen Code (Eintrag in
   `ZF_FRISCH_HANDLUNGEN`); Mail an alle BetreiberInnen bei jedem neuen
   Konto mit Rechten.
2. Freigabe eines Konto-Backups: Zielkonten mit offener Adressbestätigung
   abweisen; die Herkunft an die Kennung binden, die Adresse nur als
   zweiten Weg mit Frist. `pat_key_check` nicht mehr ausliefern, wenn der
   Browser ihn nicht braucht — sonst den Satz im Modell berichtigen.
3. SFTP: ohne gespeicherten Abdruck sendet der Job nicht; die Meldung sagt
   „erst prüfen".
4. Job-, Ketten- und Health-Token: gehasht speichern, einmalig nach dem
   Erzeugen zeigen (E-KA-10). **Folge für den Betrieb:** Wer ein Token verloren hat,
   erneuert es — ins Runbook und nach KA-M-Liste (Uptime Kuma, Geheimnisse
   der Kette).
5. Betriebs- und Kontaktadresse: nur BetreiberIn, mit Protokoll und Mail an
   die alte Adresse.
6. `admin_sicherungen.php`: derselbe Riegel nach oben wie
   `admin_user.php`; Sollwerte der harten Bestätigung bildet der Server.
7. Dienstadresse der Adresssuche: Host gegen ein strenges Muster; die CSP
   setzt nur, was das Muster erfüllt.
8. `adresse_bestaetigen.php`: der Link zeigt eine Seite mit Knopf, die
   Änderung geschieht per POST.
9. `api/schluessel_erneuern.php`: Demo-Ausstieg.
10. Anmeldesperre (F-KA-42): Ein gemerktes Gerät darf trotz Kontosperre
    weiter versuchen — die Sperre trifft dann den Rater, nicht die
    Inhaberin. Ohne gemerktes Gerät bleibt es wie heute.

**Berührt:** Web, Stufe **neben**. Migration, falls die Token-Spalten ihre
Form wechseln — dann **`update.php` ansagen**.
**Pflege:** Handbuch (Verwaltung, Betrieb), Technik (4.x Zweitfaktor,
Sicherungsziele, 13 Runbook), `docs/Backup-Format.md` falls die Freigabe ihr
Paket ändert, Bedrohungsmodell (Lage I, R25, R27).
**Prüfung:** je Punkt eine Probe mit roter Gegenlage (Anfrage ohne frischen
Code → 303 zur Bestätigung; Freigabe an ein Konto mit offener Adresse →
abgewiesen; Job ohne Abdruck → sendet nicht). Staging-Nachprüfung nach R91.
**Modell:** Opus. **Fächerung:** Punkte 1, 3, 5, 7, 8 und 9 fassen je eigene
Dateien an und können gefächert umgebaut werden; 2, 4, 6 und 10 seriell.
Prüfarbeit nie gefächert.

### KA-04 Geräte — Kontobindung und Sendeweg

**Ziel:** Kein Gerät schickt Aufgezeichnetes in ein anderes Konto als das,
an das es beim Aufzeichnen gekoppelt war; kein Punkt geht still verloren.
**Befunde:** F-KA-05, -06, -07, -18, -27, -31, -32, -33.

1. **→ KG-01 (E-KA-21).** **Garmin-Uhr:** `Pair.start()` weist bei laufendem Dienst immer ab. Die
   Bindung an den alten Schlüssel deckt künftig auch laufende Pakete, falls
   eine Trennung auf anderem Weg entsteht (`cred` gelöscht).
2. **Handy:** Dienstbeginn von der Wear-Uhr nur bei bestehender Kopplung —
   sonst quittieren, nicht wirken, der Uhr den Zustand melden. Jede neue
   Kopplung bindet vorgefundenen Bestand ohne Bindung als fremd.
   `trennen()` verweigert bei laufendem Dienst. Unlesbarer Tresor: Bestand
   wird gebunden, nicht mitgenommen.
3. **Server, `ingest.php`:** Wird ein Stück nicht abgelegt, weil der
   Schlüssel kollidiert, quittiert der Server nur `spur_naechste_seq()`.
   Gegenprüfen, ob das Handy ebenso stückelt wie die Uhr (im Audit nicht
   geprüft).
4. **→ KG-01 (E-KA-21).** **Uhr:** `next_seq` höchstens `seq_from + gesendete Punkte`; die Größe
   eines unbestätigten Stücks merken und identisch wiederholen.
5. **Handy:** 400, 401 und 403 nur mit dem `error`-Feld des Vertrags als
   endgültig werten, sonst „später erneut"; vor dem Räumen nach Fristablauf
   ein Sendeversuch.
6. **Server, `pair.php`:** `status` und `trennen` prüfen `active` und den
   Kontostatus wie `ingest.php`.
7. **Android-Kleinigkeiten:** Benachrichtigungsaktion „Dienst beenden" mit
   `setAuthenticationRequired` (ab API 31); untere Zeitgrenze für
   `DIENST_BEGINNEN`; `taskAffinity=""`.

**Berührt:** Uhr **neben**, Android **neben**, Web **Korrektur** (zwei
Endpunkte); `docs/JSON-Vertrag.md` (Quittung, Fehlerfelder).
**Pflege:** Handbuch (Geräte, Kopplung — Zeile 129 stimmt dann),
`android/LIESMICH.md`, Technik 6, `docs/Geraete-Eingabe.md`,
Bedrohungsmodell (Lage H, R24).
**Prüfung:** Android-Prüffälle für die drei Wege (die Module haben Tests);
`tools/proben/ingest/` um die Dublette mit größerer Punktzahl; Uhr im
Simulator (`tools/uhr-pruefstand/`); Emulator nach `Pruefablauf.md` 6.9.
**Nicht maschinell prüfbar:** Übergabe einer echten Uhr im laufenden Dienst,
Sperrbildschirm am echten Handy — Prüfliste.
**Modell:** Opus. **Fächerung:** Uhr, Android und Server sind getrennte
Dateibäume und können gefächert umgebaut werden; der Vertrag wird vorher
seriell festgelegt.

### KA-05 Mengen, Schreibwege, Fremdbestandteile

**Ziel:** Kein Konto trifft die Anlage; jeder Schreibweg prüft; keine
Bibliothek mit bekannter Lücke liest fremde Dateien.
**Befunde:** F-KA-10, -11, -22, -23, -34, -38, -40.

1. Mengengrenze: `konto_fuellstand()` an den Anfang jedes **anlegenden**
   Web-Wegs (sechs Stellen, Audit F-KA-10), `konto_mengen_fortschreiben()`
   danach; Löschen und Bearbeiten bleiben frei. Eigene, enge Grenze für das
   Demo-Konto. **Eine Stelle je Sache:** Der Riegel kommt als eine Funktion,
   und `tools/zaehlung/register.php` bekommt die Zeile.
2. SheetJS auf eine behobene Fassung vom Hersteller-Archiv, lokal
   vendoriert, Herkunft und Summe im Kopf; `docs/Lizenzen.md` nennt die neue
   Quelle (**Klasse `netz` der Textprobe:** die Adresse muss dort stehen).
3. `EdHtml.meldung()` liest Optionen nur als eigene Eigenschaften; dieselbe
   Durchsicht für jeden Baustein in `html.js`, der ein Optionsobjekt nimmt.
4. Wiederherstellung: Katalogspalten durch dieselben Prüfaufrufe wie der
   Import; `notes` nicht mehr als Klartextspalte annehmen.
5. Teil-Vorlagen und Bibliotheken per `.htaccess` sperren; dazu eine
   Wächterkonstante, die auch ohne Apache hält. `migrations/` sperren,
   `Options -Indexes`, `.zustand/` in die Pfadliste der Wache.
6. Regex-Anker: `\z` an allen Stellen, die eine Kennung oder ein
   Weiterleitungsziel prüfen; ein Riegel in `tools/quelltext/` zählt `$/'`
   ohne `D` in `preg_match` über Anfragewerte.
7. Sicherung: Fassung-2-Rückfall abschalten (unversiegelt ist rot),
   Passphrase des Komplett-Downloads auf die Mindestlänge der Konten,
   Protokollarchiv nicht ins gemeinsame Temp-Verzeichnis, Einträge je
   CSP-Bericht deckeln.

**Berührt:** Web, Stufe **neben**. Punkt 7 kann eine Sicherung älterer
Fassung unlesbar machen — **vorher zählen**, wie viele Pakete Fassung 2
tragen, und es im Changelog sagen.
**Pflege:** Lizenzen, Backup-Format, Technik (4.x, 12, 13), Handbuch
(Import, Grenzen), Bedrohungsmodell (G4, R26).
**Prüfung:** Mengenprobe je Weg mit roter Gegenlage; Importprobe mit den
vorhandenen Tabellenfällen in drei Engines (die Bibliothek wechselt);
Kreisläufe `edbak` und CSV; `fremdsummen`. **Nicht nachgestellt wird** die
Lücke selbst — die Probe belegt die neue Fassung und das gehärtete Gadget,
nicht einen gescheiterten Angriff.
**Modell:** Opus. **Fächerung:** Punkte 1, 2+3, 4, 5+6 und 7 fassen getrennte
Dateien an; seriell bleibt Punkt 1 in sich (sechs Endpunkte, eine Funktion).

### KA-06 Bindung und Herkunft — der Formatwechsel

**Ziel:** Zuordnung in beiden Stufen (E-KA-05). Ein Block, der nicht an
seine Stelle gehört, geht nicht auf; Gerätedaten, die kein gekoppeltes Gerät
verschlossen hat, werden als solche erkannt.
**Befunde:** F-KA-04, -12, -13, -16, -17, -29, -30.

Das ist ein Umbau des Datenformats in vier Umsetzungen (Browser, Handy,
Garmin-Uhr, PHP-Formprüfung) mit Altbestand: Stufe **haupt** für Web und
Uhr, **neben** oder **haupt** für Android nach dessen Zählweise.

**KA-06a Entwurf und Referenz** — vor jeder Zeile Anwendungscode. Ergebnis:
ein Formatdokument im Kopf von `tools/proben/huelle/referenz.py` (wie S11),
Prüfvektoren, und eine Freigaberunde (KA-M5). Die Skizze, von der der
Entwurf ausgeht:

- **Chiffretexte unter dem Inhaltsschlüssel (Stufe 1).** Neue Kennung
  (Arbeitsname `edk2:`), AES-256-GCM mit Zusatzdaten aus Zweck
  (`pat`, `kp`, `rw`, `wrap`), Kontokennung und — für `pat` — `client_ref`
  des Einsatzes. Gegen das Zurücklegen ein Zähler je Einsatz im Block, den
  der Browser gegen einen Stand vergleicht, den der Server nicht fälschen
  kann; **wo dieser Stand liegt, ist die erste Entwurfsfrage** (ein
  Verzeichnis unter dem Inhaltsschlüssel kostet einen Abruf je Liste).
  Auffüllen auf Größenklassen (F-KA-30).
- **Gerätehülle Fassung 2 (Stufe 1).** Absolute Zeiten in der Hülle statt
  relativer zu einem Klartextanker; ein Zähler in der Einsatzhülle; die
  Stückfolge einer Spur verkettet, damit ein fehlendes Stück auffällt.
- **Herkunft (Stufe 2).** Ein Schlüsselpaar je Gerät, erzeugt bei der
  Kopplung. **Vorschlag: authentifizierte Hülle statt Signatur** — der
  Schlüssel der Hülle entsteht aus dem flüchtigen **und** dem statischen
  Geräteschlüssel (zwei ECDH gegen den Kontoschlüssel). Das braucht nur
  Verfahren, die alle drei Geräte heute schon benutzen. **Zu messen in
  06a:** ob `Toybox.Cryptography` auf den Zielgeräten ein statisches Paar
  dauerhaft halten kann, und was eine Signatur dort kostete — erst danach
  fällt die Wahl.
- **Der öffentliche Geräteschlüssel braucht einen zweiten Kanal** — sonst
  tauscht ihn, wer den Server beschreibt. Das ist derselbe fehlende Kanal
  wie beim Kontoschlüssel (Modell 6.3) und wie in F-KA-17: **ein kurzer
  Vergleichswert**, abgeleitet aus Kontoschlüssel und Geräteschlüssel,
  den das Gerät **und** die Webseite bei der Kopplung zeigen. Er ersetzt
  die maskierte Adresse als zweites Tor. Der Browser legt den bestätigten
  Geräteschlüssel unter dem Inhaltsschlüssel ab.
- **Ableitung (ohne Formatwechsel der Daten).** Untergrenze der Rundenzahl
  im Browser, Formprüfung des Salzes (F-KA-13); Vormerkfach mit Zeitstempel
  und Räumen auf jedem Weg (F-KA-16); ein PBKDF2-Block plus HKDF für Hälfte
  und Token (F-KA-29) — **das ändert das Anmelde-Token jedes Kontos** und
  läuft deshalb über die stille Umstellung beim Anmelden, wie die Anhebung
  der Rundenzahl.

**KA-06b Browser.** `EdCrypto` schreibt die neue Fassung, liest beide; ein
Anhebelauf nach dem Muster `api/pat_anheben.php` hebt den Altbestand
(Patientenblöcke, beide privaten Teile, die Wiederherstellungshülle beim
nächsten Erneuern). Die drei Punkte zur Ableitung gehen hier mit und können
**vorab als eigenes kleines Paket** ausgeliefert werden (F-KA-13 und -16
brauchen keinen Formatwechsel).

**KA-06c Geräte.** Geräteschlüssel, Hülle Fassung 2, Kopplung mit
Vergleichswert — Garmin-Uhr (**→ KG-03, E-KA-21**) und Handy; die Wear-Uhr bleibt ohne Schlüssel
(sie sendet nicht). **Gekoppelte Geräte haben keinen Schlüssel:** Sie
koppeln neu oder senden bis dahin Fassung 1, die der Browser als „ohne
Herkunftsnachweis" kennzeichnet. Die Frist dafür entscheidet KA-M5.

**Mit 06c zu klären (14.11.2026, BetreiberIn: „alles in 6c").** Aus der
Rückmeldung von KG zu KG-03b (PR #181, Uhr 5.0.0; F-KG-22, Konzept KG 5e)
gehen an die **Gegenprüfung des Zählers durch Fable** (E-KA-64):
(1) Eine Uhr, deren Zeit einmal weit voraus lag, behält den hohen Stand;
ein zweites Gerät fällt für diesen Einsatz als `zurueckgelegt` durch, bis
seine Zeit den Stand überholt. (2) Eine Uhr ohne Zeit oder mit Nachgang
fällt selbst durch, sobald ein anderes Gerät den Einsatz belegt hat — ob
der Browser das als Angriff oder als „Uhrzeit prüfen" zeigt, ist zu
entscheiden. (3) `z` belegt die Zeit des Verschließens, nicht die Frische
des Inhalts; wer zuletzt sendet, gewinnt (wie mit `+1`). (4) Der Kopf von
`referenz.py` spricht von „je Absender", geprüft wird je Einsatz. (5) Der
Referenz fehlt ein Vektor mit zwei Absendern an einem Einsatz. **Dazu
F-KA-108** (Server, aus 06d): Die Kopplung mit Schlüssel an einem Konto
ohne Schlüsselpaar.

**KA-06d Server und Formate.** `validate_lib.php` und `geraetehuelle_lib.php`
prüfen Form und Größe beider Fassungen; `docs/JSON-Vertrag.md`,
`docs/Backup-Format.md` (Nutzlast hebt sich, Altpakete werden beim
Einspielen im Browser gehoben), `docs/Export-Format.md` falls berührt.
**Der Server kann weiterhin nichts öffnen und nichts prüfen außer der Form.**

**KA-06f Zähler am Inhalt, Geräteschlüssel im Keystore** (E-KA-69, -71,
-72, -73; vor 06e). (1) **Referenz:** die Zählerregel nach E-KA-69 im Kopf
von `referenz.py` und in den Vektoren — neue Fälle, bestehende bleiben
unverändert; die Übergabe-Vektoren aus KA-06c zeigen den Unterschied (ein
nachgesendeter alter Stand fällt jetzt durch). (2) **Handy:** `minSdk` 31;
`Geraeteschluessel` als Keystore-Umsetzung (EC P-256, `PURPOSE_AGREE_KEY`,
nicht exportierbar, ohne Entsperrpflicht; Versuch als zweiter Eintrag, beim
Übernehmen umbenannt bzw. getauscht); `verschliessen2` rechnet `Z_s` über
eine Schnittstelle statt über ein `Geraetepaar` mit privatem `d`; die
Tresor-Ablage `geraet.bin` entfällt, eine vorhandene wird beim ersten Start
gelöscht (es gibt sie nur auf Testgeräten seit 0.24.0 — die Kopplung muss
dann neu). Der Zähler wird im Puffer je Paket geführt und nur bei einer
Änderung des Inhalts erhöht. (3) **Uhr:** dieselbe Zählerregel, Auftrag an
KG. (4) **Browser:** Befund bei `schluss: false` nach `schluss: true`
(kleiner Teil von E-KA-69; der Rückweg E-KA-70 erst mit 25.0.0).
**Prüfen:** Robolectric hat keinen Keystore — die Keystore-Umsetzung misst
erst ein Gerät oder der Emulator mit API ≥ 31 (`android/werkzeuge/emulator.sh`,
`Pruefablauf.md` 6.9); die Prüffälle laufen gegen die Schnittstelle.
**Fassungen** legt die Umsetzung fest; das Anheben von `minSdk` ist eine
spürbare Änderung der Auslieferung und gehört in Changelog, `android/LIESMICH.md`
und Handbuch (welche Handys die App noch bekommen). Uhr über KG.

**KA-06e Altformat.** Was mit Gerätedaten geschieht, die nie einen
Herkunftsnachweis bekommen können (die Geräte von damals verschließen sie
nicht neu): Der Browser verschließt sie im Anhebelauf unter einem eigenen
Kennzeichen „vom Browser übernommen am …". Annahme von Fassung 1 am Server
endet zu einem Stichtag (`400`, wie `klartext` in S11).

**KA-06d Entwurf der Kopplung (13.11.2026, E-KA-61) — freigegeben
(E-KA-63).** Die Rechnung steht in der Referenz (`referenz.py`, Kopf
„DER VERGLEICHSWERT DER KOPPLUNG"); hier steht, wie sie über `pair.php`
und die Webseite läuft. Beantwortet F-KG-16.

1. **`start`** nimmt neben `geraet` den Block
   `"schluessel": {"spk": "<Base64, 65 Byte SEC1>", "zusage": "<Base64, 32 Byte>"}`.
   Fehlt er, koppelt das Gerät wie bisher, ohne Herkunftsnachweis (bis zum
   Stichtag aus 06e). Ein Block in falscher Form ist `400 schluessel`.
2. **Webseite, „Dieses Gerät koppeln?"**: Der Browser bekommt SPK und
   Zusage mit der Rückfrage, legt sie zur Gerätekennung in die
   Sitzungsablage des Tabs und schickt beim Verbinden **N_b** (16 Byte,
   Base64, im Browser gezogen) mit `koppeln_bestaetigen`. Der Server setzt
   N_b **einmal**, beim Beanspruchen.
3. **`status`, Zustand `beansprucht`**, trägt dazu `kontoschluessel` und
   `n_b`. Das Gerät legt sich auf das erste Paar fest; ein anderes in einer
   späteren Antwort bricht ab (F-KG-16 (1): **nein**, kein zweites Paar).
4. **Neu: `offenlegen`** mit Kopfzeilen, `{"aktion":"offenlegen","n_g":"<Base64, 16 Byte>"}`
   → `200 {"ok":true}`. Nur im Zustand `beansprucht`. **Wiederholbar:**
   dasselbe N_g noch einmal → wieder `200` (die Antwort kann über Bluetooth
   verloren gehen); ein anderes → `409 {"error":"n_g"}`. N_g muss
   zur Zusage passen — **das prüft der Server nicht**, das prüft der Browser.
   Danach rechnet das Gerät den Wert und zeigt ihn mit der maskierten Adresse.
5. **Webseite, „Vergleichswert prüfen"**: Die Karte fragt nach, bis N_g da
   ist (`api/kopplung_stand.php` liefert ihn), prüft die Zusage gegen die
   SPK aus Schritt 2 (nicht gegen eine neue Abschrift des Servers),
   rechnet den Wert und zeigt ihn. **„Wert stimmt"** schreibt den Eintrag
   ins Gerätebuch (`sid`, `spk`, `geraet`, `seit`) und meldet dem Server
   `wert_ok` — in einer Anfrage, Gerätebuch und Vermerk in einer
   Transaktion (Feld `blaetter` wie in 06b-3). **„Wert ist anders"** oder
   eine Zusage, die nicht passt, markiert die Sitzung als abgelehnt (sie
   bleibt bis zum Ende der Frist stehen, damit das Gerät den Grund
   erfährt) und meldet es im Reiter System (`kopplung`, ohne Werte).
6. **`bestaetigen` (Ja)** schließt erst ab, wenn `wert_ok` vorliegt; sonst
   `202 {"wartet":"browser"}`, und das Gerät fragt mit `status` nach.
   **Die Zustände von `status` nach dem Offenlegen** (Rückmeldung KG):
   (a) Ja da, Wert noch nicht bestätigt →
   `{"zustand":"beansprucht","wartet":"browser", …}`; (b) im Web abgelehnt →
   `{"zustand":"abgelehnt","grund":"wert"|"zusage"}` — das Gerät zeigt „Im
   Web abgelehnt" und legt kein Paar ab; (c) abgeschlossen →
   `{"zustand":"gekoppelt","kontoschluessel":…,"huelle":2}`. Umgekehrt schließt `wert_ok` ab, wenn das Ja schon
   da ist. Wer zuletzt kommt, legt das Gerät an. `devices` bekommt `spk`
   (Migration); `status` und `bestaetigen` sagen nach dem Abschluss
   `"huelle": 2` — fehlt das Feld (alter Server), sendet das Gerät
   Fassung 1. **Auch `status` eines schon gekoppelten Geräts trägt es**,
   sobald am Gerät ein `spk` steht — so lernt eine Uhr, deren Merker
   verloren ging, ihn über denselben Weg wieder wie den Kontoschlüssel.
7. **Der Zähler `z` (F-KG-16 (3), Q-KA-13).** Die Referenz verlangt
   `z ≥ o` je Einsatz, nicht `o + 1`; ein Zähler für alle Einsätze, der das
   Trennen überdauert (`gz` der Uhr), erfüllt das. **Vorschlag für den
   Gerätewechsel:** `z` = Unix-Sekunde des Verschließens, lokal streng
   steigend gehalten (`max(jetzt, letztes z + 1)`). Dann steigt der Zähler
   auch über Geräte hinweg, solange ihre Uhren nicht mehr als den Abstand
   zweier Übergaben auseinandergehen; das Format ändert sich nicht.
   **Zur Gegenprüfung durch Fable mit 06c** — es ist eine Regel der
   Referenz, keine der Verdrahtung.
8. **Das leere Schlussstück (F-KG-16 (2)).** `ts_von` = Unix-Sekunde des
   Abschlusses, innen und in der Klartextspalte gleich; Platz `<seq>:0`.
   Der Server prüft die Form; dass `seq` an das letzte Stück anschließt,
   prüft der Browser beim Lesen (Vertrag 3.4; berichtigt mit KA-06c, F-KA-111).
9. **Austragen und Aufräumen.** Entkoppeln im Web setzt `bis` im
   Gerätebuch (der Browser, in derselben Anfrage). Ein Eintrag ohne Gerät
   (das Gerät sagte Nein, nachdem der Wert bestätigt war) bekommt `bis`
   beim nächsten Laden der Geräteseite. **Das Aufräumskript** behält das
   eigene Konto, `demo@…` und die Adressen aus `NADOKU_STAGING_BEHALTEN`
   (Umgebung der Arbeitsumgebung, kommagetrennt); fehlt die Variable, ist
   es rot statt großzügig (Q-KA-14).
10. **Mockup:** `docs/konzepte/konzept-ka/mockups/M-KA-01-kopplung.html`
    und `.png` — nur vorhandene Bausteine (Karte, Zeile, Codeblock in der
    großen Stufe, Meldung, Knöpfe im Fuß).

**Berührt:** Web, Uhr, Android; Migration (Spalte für Geräteschlüssel,
Zähler) — **`update.php` ansagen**. **Pflege:** `CLAUDE.md` 4 (der Absatz
zur Verschlüsselung nennt dann Bindung und Herkunft), Technik 4.14 und 4.40,
Bedrohungsmodell 3, 4.1, 4.3, 6 vollständig neu für diese Zeilen,
`tools/quelltext/kennzeichnung-soll.md` falls ein neues Zeichen entsteht
(dann Mockup und Freigabe), Handbuch (Kopplung, Verschlüsselung).
**Prüfung:** Hüllenprobe gegen die Referenz in allen vier Umsetzungen,
byte-gleich; je Angriff aus F-KA-04 und F-KA-12 eine Probe, die ihn ausführt
und den Fehler des Browsers misst (vertauschen, zurücklegen, Zeit
verschieben, Stück entfernen, Hülle ohne Geräteschlüssel); Anhebelauf am
Messstand mit dem großen Bestand; voller Prüfstand (Hauptstufe).
**Nicht maschinell prüfbar:** Kopplung mit Vergleichswert an echten Geräten.
**Modell: Fable** für 06a und für den Krypto-Kern von 06b und 06c (K8 des
Rahmenplans) — **vor Beginn darauf hinweisen und pausieren**; Opus für 06d,
06e und die Oberfläche. **Fächerung:** 06a seriell. 06c: Uhr und Handy
gefächert, nachdem die Referenz steht. 06b seriell (eine Bibliothek, viele
Verbraucher).

### KA-08 bis KA-15 — Web 25.0.0, die Weiche wird umgelegt (E-KA-82)

Ein Zweig, ein gesammelter PR, am Stück; Modellwahl Opus, der Krypto-Teil
an einen Fable-Agenten (E-KA-85). **Fächerung:** KA-09 und KA-10 laufen als
Agent, während KA-08 auf die Freigabe wartet; sonst seriell (Prüfarbeit
und erzählender Text lassen sich nicht fächern, `CLAUDE.md` 7).

| Paket | Inhalt | Befunde, Entscheidungen | Hängt an |
|---|---|---|---|
| KA-08 Mockup | M-KA-03: Anzeige der Befunde im Browser (`offen-nach-schluss`, `zurueckgelegt` mit Rückweg „diesen Stand übernehmen“ und „Uhrzeit von ‹Gerät› prüfen“, Kennzeichen „übernommen“, fehlende Herkunft) und am Handy „neu koppeln“ — nur vorhandene Bausteine. **Halt bis zur Freigabe** | E-KA-70, -80, -81 | — |
| KA-09 Referenz Rückweg | SID des Absenders zum Stand im Standbuch; kleineres `z` vom gleichen Absender hart `zurueckgelegt`, vom anderen ein Hinweis mit bewusster Bestätigung, vermerkt; gleiches `z` vom anderen Absender ebenso ein Hinweis; neue Vektoren, bestehende unverändert | E-KA-70, F-KA-112 | — |
| KA-10 Browser-Kern | `standbuch.js`, `huelle2.js` nach KA-09; Befunde und Rückweg bis an die Verbraucher (`EdOrtsdaten`) | E-KA-70, -76 | KA-09 |
| KA-11 Oberfläche | Befundanzeige und Rückweg nach M-KA-03, Sollliste der Kennzeichen | E-KA-70, -81 | KA-08, KA-10 |
| KA-12 Geräte von vorher | Satz in Handbuch und Runbook: vor dem Umlegen gekoppelte Geräte koppeln neu | F-KA-103, E-KA-83 | — |
| KA-13 Demo-Bestand | Demo-Fixture in Fassung 2 neu verschlossen, Demo-Gerät mit festem Schlüssel im Gerätebuch | F-KA-104, E-KA-44 | KA-10 |
| KA-14 Weiche um | `SCHREIBT_FASSUNG_2 = true`, Rundenziel 1 200 000; alle Schreibwege Ende zu Ende mit umgelegter Weiche (Formular, Import, Wiederherstellung, Übernahme, Wartungslauf, Notizen, Kontopaar, Rückweg, Schneiden, Löschen); Messstand mit dem Anhebelauf am großen Bestand; Dauer der Anmeldung; Prüfstand `haupt`; Pflege `CLAUDE.md` 4, Technik 4.14/4.40/4.42, Bedrohungsmodell, Handbuch, JSON-Vertrag | E-KA-51, -57, -59, -84 | KA-10 bis KA-13 |
| KA-15 Android 0.26.0 | Mit `huelle: 2` und ohne Geräteschlüssel kein Senden, Hinweis „neu koppeln“ nach M-KA-03 | E-KA-80 | KA-08 |

**Garmin:** E-KA-80 an der Uhr und KG-05 (Zählerregel) gehen an eine neu
gestartete KG-Sitzung (E-KA-86).

**Auslieferung:** Tag `web-v25.0.0` und `android-v0.26.0` setzt die
BetreiberIn; vorher die Messung am großen Bestand (KA-14).

### KA-16 bis KA-23 — 06e: Altformat beenden (Web 26.0.0, E-KA-79, -93)

Ein Zweig, ein gesammelter PR, am Stück; **gemergt erst nach dem Tag
`web-v25.0.0`, dem Anhebelauf auf Produktiv und dem Neukoppeln der Geräte**
(E-KA-93, P-KA-18, -19). Modell Opus (Abschnitt 5). **Fächerung:** keine —
Referenz, Browser-Kern und Proben hängen aneinander, der Rest ist Prüfarbeit
und erzählender Text. Garmin in dieser Sitzung (E-KA-96).
**Bestandsaufnahme** (16.11.2026, Agent): rund 60 Dateien mit Treffern,
davon rund 35 inhaltlich (Gruppen A bis H: `edk1:`, Gerätehülle Fassung 1,
Anhebelauf und Weiche, Kopplung ohne Schlüssel, alte Sicherungen,
Zusammenführen, Lücken, Proben).

| Paket | Inhalt | Befunde, Entscheidungen | Hängt an |
|---|---|---|---|
| KA-16 Referenz | `k` haftet beim Zusammenführen (Vektor `konto-unter-geraet` umgedreht); Übernahme einer Hülle der Fassung 1 als Konto mit `uebernommen` und engem Vermerk über jede Lücke; ein Leser ohne Freibrief für Fassung 1 | E-KA-94, -95, -97; F-KA-119 | — |
| KA-17 Browser-Kern | `standbuch.js`, `huelle2.js`, `ortsdaten.js` nach der Referenz; Lesen verlangt Fassung 2 und `edk2:`; das Altformat öffnet nur noch der Anhebelauf | E-KA-97 | KA-16 |
| KA-18 Übernahme | der Anhebelauf hebt auch Gerätedaten der Fassung 1 (Einsatzhüllen, Spurstücke) und meldet, was er übernommen hat | E-KA-81, -95 | KA-17 |
| KA-19 Stichtag | Server: Gerätehülle Fassung 1 → `400 fassung`, `edk1:` im `pat_blob` → `400`, `start` ohne `schluessel` → `400 schluessel`; Zustand F und „Trotzdem verbinden“ entfallen; eine Sicherung mit Inhalt im Altformat wird abgewiesen | E-KA-60 | KA-18 |
| KA-20 Rückbau | Weiche, Schreibwege der Fassung 1, Zweig `'server'` in `edbak_restore()`, Fixture-Format 1–3 samt alter Fixture, `geraetehuelle_lib.php`; Proben auf Fassung 2; doppelter Hash im Standbuch (Rest Nr. 569) | E-KA-60 | KA-19 |
| KA-21 Android | sendet nur Fassung 2; ohne `huelle: 2` oder Geräteschlüssel „neu koppeln“ | E-KA-80 | KA-19 |
| KA-22 Uhr (Garmin) | dasselbe auf der Garmin-Uhr | E-KA-96 | KA-19 |
| KA-23 Abschluss | Dokumente, Bedrohungsmodell, Changelog, voller Prüfstand, PR | — | alle |

**Bleibt bewusst:** `edk1:` in `pat_wrap_rc` (und damit `WRAP_RC_RE`) —
der Browser kann die Wiederherstellungshülle ohne den
Wiederherstellungsschlüssel nicht neu verschließen; sie hebt erst ein
Schlüsselwechsel. Das Lesen des Altformats im Anhebelauf fällt mit KA-07.

### KA-07 Modell, Texte, Abschluss

**Ziel:** Was die Dokumente sagen, tut der Code.
**Befunde:** F-KA-43 und die Restpflege aller Pakete.

1. `docs/Bedrohungsmodell.md` einmal ganz gegen den dann erreichten Stand
   lesen; die Zeilen R21 bis R27 gehen als behoben oder als benannte Grenze
   in den Bestand; die Kopfzeile „Nachgelesen gegen" hebt sich.
2. Die fünf Kommentare aus F-KA-43 berichtigen — die zwei in `watch/`
   (`Huelle.mc`, `Pair.mc`) mit KG-01 (E-KA-21).
3. `CLAUDE.md` 3 und 4, soweit die Pakete es nicht schon getan haben.
4. Prüfdokument nach `CLAUDE.md` 7, danach Aufgehen in der Prüfliste.
5. Abschluss nach Rahmenplan 9: Erledigt-Zeile, Reste nach 6, Backlog-
   Kopfzeilen, Verlaufszeile; dieses Konzept und der Audit-Bericht werden
   gelöscht, die Git-Historie behält beide.

**Berührt:** `docs/`, Kommentare — Stufe **Korrektur** für die Auslieferung,
deren Kommentare sich ändern. **Modell:** Opus. **Fächerung:** keine
(erzählender Text).

---

## 6. Meilensteine der BetreiberIn

| Nr. | Was | Wann |
|---|---|---|
| KA-M1 | In GitHub ansehen und setzen: Umgebung `produktion` und Freigabe von Fork-Läufen — **erledigt 10.11.2026** (Abschnitt 3): Umgebung auf `main` und die drei Tag-Muster beschränkt | erledigt |
| KA-M2 | Tag-Rulesets — **erledigt 10.11.2026**, gemessen. **Mit KA-01:** Probelauf der Auslieferung mit `security: strict` gegen Staging und Produktiv starten und freigeben; danach „Require actions to be pinned to a full-length commit SHA“ einschalten und einen Lauf abwarten; die Protokolle älterer Auslieferungsläufe löschen, soweit sie `wartung.von` nennen | in KA-01 |
| KA-M3 | Tag nach KA-01 und KA-02 — beide hohen Befunde auf Produktiv (E-KA-09) | nach KA-02 |
| KA-M4 | Wiederherstellungsschlüssel erneuern — seit E-KA-16 nur der eigene der BetreiberIn (es gibt keine weiteren Konten); unter Einstellungen → Profil, danach das alte Blatt vernichten (E-KA-07) | nach KA-M3 |
| KA-M5 | Freigaberunde zum Formatentwurf: Lage des Zählerstands, Bauart der Herkunft, Frist für Geräte ohne Schlüssel, Stichtag für Fassung 1 | **erledigt 11.11.2026** (E-KA-43 bis -48) |
| KA-M6 | Gerätetests: Kopplung mit Vergleichswert, Übergabe im laufenden Dienst, Sperrbildschirm | nach KA-04 und nach KA-06c |

---

## 7. Fragen

| Nr. | Frage | Antwort |
|---|---|---|
| Q-KA-01 | Schreibt ein Proxy oder Hoster Anfragekörper mit? | nicht bekannt — die Schlüssel werden erneuert (E-KA-07) |
| Q-KA-02 | Regeln für `produktion` und Tags, Fork-Läufe? | nachgemessen, abgelesen und am 10.11.2026 gesetzt, Abschnitt 3 |
| Q-KA-03 | Zuordnung als Zusage oder als Grenze? | Zusage, beide Stufen in einem Zug (E-KA-05) |
| Q-KA-04 | Wie geht der Bericht in die Steuerung ein? | Sofortpunkt mit Konzept (E-KA-06) |
| Q-KA-05 | Soll nach KA-01 und KA-02 sofort getaggt werden, oder erst mit KA-03? | sofort (E-KA-09) |
| Q-KA-06 | Dürfen Token für Jobs, Kette und Health nach KA-03 nicht mehr nachlesbar sein (einmalige Anzeige)? Das ändert den Betrieb | ja (E-KA-10) |
| Q-KA-07 | KA-M5: Wo liegt der Zählerstand gegen das Zurücklegen? | Browser plus Abschrift in 16 Blättern (E-KA-43) |
| Q-KA-08 | KA-M5: Bauart der Herkunft; eigener Klick „Wert stimmt"; Demo-Bestand? | wie vorgeschlagen, mit Klick und Demo-Gerät (E-KA-44) |
| Q-KA-09 | KA-M5: Frist für Geräte ohne Schlüssel? | neu koppeln, höchstens 30 Tage (E-KA-45) |
| Q-KA-10 | KA-M5: Stichtag für Fassung 1? | Ende der Frist (E-KA-46) |
| Q-KA-11 | KA-M5: Rundenzahl mit Ableitung 2? | 1 200 000 (E-KA-47) |
| Q-KA-12 | Wird der Anhebeweg vor v1.0 zurückgebaut, wenn er einmal durchgelaufen ist? | ja, samt dem Lesen von `edk1:` — alte Sicherungen müssen nicht mehr einspielbar sein (E-KA-60) |
| Q-KA-13 | Wie hält der Zähler `z` der Einsatzhülle, wenn ein Einsatz das Gerät wechselt (Übergabe im laufenden Dienst)? Das Standbuch kennt je Einsatz einen Stand `o`; ein zweites Gerät mit kleinerem Zähler fiele als `zurueckgelegt` durch | Unix-Sekunde, Gegenprüfung mit 06c (E-KA-64) |
| Q-KA-14 | Welche Konten behält das Aufräumskript auf Staging? Die Liste steht nicht im Repositorium, weil dort keine echten Adressen stehen sollen | Umgebungsvariable `NADOKU_STAGING_BEHALTEN` (E-KA-65) |
| Q-KA-15 | **Zähler, Zweifel (1) und (2):** Soll der Browser einen Rückweg „diesen Stand übernehmen" bekommen und das Standbuch zum Stand die SID des Absenders merken — dann „zurückgelegt" hart nur bei gleichem Absender, bei anderem ein Hinweis „Uhrzeit prüfen" mit Bestätigung? Mit 06e oder mit dem Umlegen der Weiche (Web 25.0.0)? (Empfehlung des Fable-Agenten, Prüfprotokoll KA-06c) | beantwortet: E-KA-70 |
| Q-KA-16 | **Zähler, Zweifel (3):** `z` beim Verschließen lassen (E-KA-64, gebaut) — dann belegt es nicht die Frische des Inhalts, und ein Gerät, das nach der Übergabe einen alten, nicht abgeschlossenen Stand nachsendet, gewinnt —, oder `z` beim Ändern des Inhalts vergeben und mit dem Paket puffern (ändert E-KA-64, trifft auch die Uhr)? Kleinere Möglichkeit: Der Browser meldet eine Hülle mit `schluss: false` nach einer mit `schluss: true` als Befund | beantwortet: E-KA-69 |
| Q-KA-18 | **Befund `offen-nach-schluss`, haftet `f`?** (06f-1, Zweifel 1 des Agenten) So gebaut: `f` bleibt gesetzt, der Befund steht bei jedem Lesen, solange die offene Hülle die gespeicherte ist. Darf ein Gerät einen abgeschlossenen Einsatz legitim wieder öffnen, wäre das ein Dauerhinweis; Alternative: `f` an `o` binden, Befund nur einmal | beantwortet: E-KA-76 |
| Q-KA-19 | **Befund auch an Hüllen des Kontos?** (06f-1, Zweifel 2) Die Referenz meldet ihn gleich von welchem Absender. Verschließt der Browser selbst (Formular, Wartungslauf) nach einem Abschluss mit `schluss: false`, meldet er sich selbst — dann das Konto ausnehmen oder `schluss` dort weitertragen | beantwortet: E-KA-76 |
| Q-KA-20 | **Handy mit `huelle: 2`, aber ohne Geräteschlüssel** (06f-2, Zweifel 2 des Agenten): nach dem Update von 0.24.0 (alte Ablage gelöscht) oder wenn der Keystore keinen Eintrag liefert, sendet das Handy wie bisher Fassung 1, bis es neu koppelt. Soll es stattdessen gar nicht senden? Bis zum Stichtag aus 06e nimmt der Server Fassung 1 an | beantwortet: E-KA-80 |
| Q-KA-21 | **F-KA-119: Soll `k` beim Zusammenführen zweier Blätter haften?** Heute gewinnt der höhere Zähler; ein Blatt mit einem höheren Gerätestand verdrängt einen Kontostand, und danach käme ein Gerät nach dem Konto wieder durch. Haften hieße: ein Gerät nach dem Konto bleibt hart abgewiesen, auch wenn ein anderer Browser einen höheren Gerätestand kannte | beantwortet: E-KA-94 |
| Q-KA-22 | **Eine Spur mit echter Lücke ohne Löschvermerk** (alte Gerätedaten der Fassung 1 mit verlorenen Punkten, aus einer Sicherung neu verschlossen) lehnt das Einspielen seit KA-14 ab und meldet sie. Soll es sie lesbar übernehmen — durch einen engen Vermerk des Kontos in der Lücke, oder indem der Leser den Merker `LUECKE` des Kontos gelten lässt (Referenz-Änderung)? | beantwortet: E-KA-95 |
| Q-KA-17 | **F-KA-85:** Bleibt der Geräteschlüssel des Handys im Tresor (gebaut, alle API-Stufen gleich, kein Hardware-Schutz), oder ab API 31 als nicht exportierbarer Keystore-Schlüssel mit `PURPOSE_AGREE_KEY`, darunter der Tresor? Aufwand nach dem Agenten 1 bis 1½ Pakete und Gerätetests unter und ab API 31 | beantwortet: E-KA-71, -72 |

---

## 8. Risiken der Umsetzung

- **KA-01 Punkt 1 kann die Auslieferung anhalten**, wenn das Zertifikat des
  Hosters nicht zum FTP-Namen passt. Deshalb zuerst der Probelauf, und der
  Schalter erst, wenn er grün ist — nicht umgekehrt.
- **KA-03 Punkt 4 und KA-06 ändern den Betrieb** (Token, Kopplung). Beides
  gehört ins Runbook, bevor es ausgeliefert wird.
- **KA-06 ist das einzige Paket, das Daten unlesbar machen kann.** Der
  Anhebelauf schreibt nur, was er vorher gelesen hat, und vergleicht gegen
  den alten Block (`blob_alt`, wie heute); der Messstand fährt ihn am großen
  Bestand, bevor er ausgeliefert wird. Fassung 1 bleibt lesbar, bis KA-06e
  sie ausdrücklich beendet.
- **Der Umfang ist groß:** sieben Pakete, davon eines eine Hauptstufe in
  drei Auslieferungen. Schritt 13 wartet so lange. Wenn das zu lang wird,
  ist die Trennstelle zwischen KA-05 und KA-06 — KA-06 kann nach R90 nicht
  hinter v1.0, weil v1.0 das Format festschreibt.

---

## 9. Prüfprotokoll

### KA-01 (10.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **`security: strict` gegen den Hoster** (Punkt 1). Örtlich gibt es weder
   den FTP-Zugang noch einen Läufer; der Riegel misst die Zeile, nicht die
   Verbindung. Belegt wird es durch den ersten Lauf auf `main` (Staging) und
   den Probelauf der BetreiberIn gegen Produktiv (KA-M2, P-KA-02). Ein
   Zertifikatsfehler dort ist der erwartete Befund, wenn der Name in
   `FTP_SERVER` nicht zum Zertifikat passt.
2. **Die geänderten Arbeitsläufe sind nicht gelaufen.** Geprüft sind YAML
   (alle vier Dateien laden), Shell-Syntax (55 `run:`-Blöcke mit `bash -n`,
   0 Fehler, Ausdrücke durch einen Platzhalter ersetzt) und die Aufrufe gegen
   die Werkzeuge (Riegel `kettenaufrufe`). Dass `timeout-minutes` und die
   `env:`-Übergaben auf GitHub tun, was sie sollen, zeigt erst Stufe 1 auf
   dem PR und der nächste Auslieferungslauf.
3. **Stufe 2 mit den `--…-aus`-Schaltern** (Kreislauf, Rückwegprobe gegen
   Staging) läuft erst auf `main`. Örtlich gefahren sind die Werkzeuge mit
   ihren Selbstproben und dem Prüfstand; der Weg „Variable der Umgebung
   `staging` → `--admin-passwort-aus STAGING_PASS`" ist nur gelesen.
4. **`actionlint` ist im Container nicht vorhanden** (`which actionlint`
   leer); die Ausdruckssyntax prüft erst GitHub.

**Befunde nachgelesen** gegen `3e06f50`: F-KA-02 (`ausliefern-lauf.yml`
FTPS-Schritt ohne `security:`), -14 (Zeile 279 `ZAHL="${{ inputs… }}"`),
-15 (Deny-Liste mit vier Einträgen), -24 (`bericht.py` `if ist and …`),
-25 (`tor.py` druckt `zustand` ungefiltert, `komplett_lib.php` nennt den
Namen die zweite Schranke), -26 (`fremdsummen.py` nimmt `server/vendor/`
aus), -37 (`--ftp-pass`, `--token` in den Aufrufen), -39 (`image: mysql:8.4.0`,
`mariadb:10.6`). Alle halten; keiner war hinfällig.

**Gemessen** (jede Zahl mit ihrem Gegenstand):

| Mittel | Ergebnis | Was es belegt |
|---|---|---|
| `kettenaufrufe --probe` | **67 von 67** (davon 27 neu: Riegel 3 sieben, 4 sieben, 5 sieben, 6 vier, je mit Gegenprobe; dazu bestehende) | jede neue Lage wird gemeldet, jede Gegenprobe nicht |
| `kettenaufrufe` gegen diesen Stand | **0 Befunde**; 147 Aufrufe, 59 `run:`-Blöcke ohne Eingang, 2 Abbilder am Digest, 0 Geheimnisschalter, 1 FTPS-Schritt strikt | die Behebung hält in allen vier Arbeitsläufen |
| `kettenaufrufe` (neue Fassung) gegen die Arbeitsläufe von `main` (`3e06f50`) | **32 Befunde**: 11 Eingänge im Skript, 2 Abbilder ohne Digest, 18 Geheimnisse auf der Befehlszeile, 1 FTPS ohne `strict` | die Riegel wären ohne die Behebung rot |
| `tor.py --selbstprobe` | **43/0** (vorher 36; neu: Token nur aus der Umgebung, `--token` scheitert laut, Protokoll ohne Zufallsteil und ohne `wartung.von`, Name ohne Zufallsteil nicht unverändert) | F-KA-25, -37 |
| `zielprobe.py --selbstprobe` | **95/0** (neu: ohne `FTP_PASSWORD` → 2 mit Namen der Variablen; `--ftp-pass` scheitert laut) | F-KA-37 |
| `zustand.py`, `apkablage.py --selbstprobe` | **28/0**, **25/0** | unverändert grün nach dem Umbau |
| `bericht.py lesen --selbstprobe` | **52 Lagen, 0 Fehlschläge** (neu Lage 1b: leerer Baum ist rot) | F-KA-24 |
| `fremdsummen.py --selbstprobe` | **14/0** (neu sieben: Datei geändert, Datei neben der Liste, gelistete fehlt, Parsedown geändert, JAR getauscht, JAR fehlt, Gegenprobe) | F-KA-26 |
| `fremdsummen.py` gegen diesen Stand | 6 + **351** Dateien (338 + 11 aus den Listen, `Parsedown.php`, JAR), 0 Befunde | die Summen stimmen heute |
| `fremdsummen.py` mit verändertem `Crypt/AES.php` und JAR (Wegwerfbaum) | **2 Befunde**, rc 1 | der Riegel schlägt an echten Dateien an |
| Laufzeiten für F-KA-44 | 30 Läufe `pruefung.yml` bis Lauf 500: höchstens **3,0 min** je Lauf; 50 Läufe `integritaet.yml`: höchstens **1,8 min**; Auslieferung: `ausliefern` höchstens rund 3 min (Produktion 23.6.0), `Rückfallstand` und `zeiger` je unter 15 s | Herleitung der Limits (Abschnitt 10) |

Der Prüfbericht des Commits trägt den Lauf von `tools/pruefstand/pruefen.sh`.

**Nach dem Merge (Lauf 191 auf `main`, 10.11.2026):** alle Jobs grün. Der
Schritt „server/ per FTPS synchronisieren" gegen Staging lief mit
`security: strict` durch — das Zertifikat von Staging passt zum Namen in
`FTP_SERVER`. Stufe 2 lief mit den `--…-aus`-Schaltern: Kreislauf `edbak`
(18 min) und Rückwegprobe grün. **Produktiv belegt erst der Probelauf der
BetreiberIn** (KA-M2).

### KA-02 (10.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Papier und ein echter Safari.** Der Druck ist am Bildschirm und mit
   `emulateMedia('print')` gemessen, nicht auf Papier; WebKit ist nicht
   Safari. Prüfliste.
2. **Der dunkle Modus ist nur in Chromium gegen den hellen gehalten**, mit
   einem Werkzeug aus der Sitzung, nicht mit einer Probe des Bestands: Der
   Bilderlauf nimmt den dunklen Modus nicht auf (Nr. 521). Die Druckvorschau
   der **Tagesübersicht** aus Nr. 540 ist nicht nachgestellt; gemessen ist
   stattdessen eine Seite mit Fehlermeldung im Druck — dieselben Rollen.
3. **Ein Browser, der `window.open` blockiert:** Der Hinweis unter dem Knopf
   ist gebaut, aber nicht ausgelöst gemessen.

**Gemessen** (jede Zahl mit ihrem Gegenstand):

| Mittel | Ergebnis | Was es belegt |
|---|---|---|
| Notfallblattprobe, Chromium, örtlich | **16 von 16**: Erstvergabe 0 Treffer in 22 Anfragen bis zum Blatt und 31 bis zum Abschluss; Erneuerung 0 in 99; Codeblatt 0 in 9; Blatt zeigt Schlüssel, Adresse (ohne und mit Sitzung) und zehn Codes; POST an beide Blätter 400 ohne Wiedergabe; ohne Ursprungsfenster kein Blatt | F-KA-01, -36 behoben |
| dieselbe Probe gegen `server/` von `main` | **4 von 16**: der Schlüssel in 1 von 20 Anfragen (Erstvergabe) und 1 von 97 (Erneuerung), die Codes 10-mal in 8 Anfragen; POST 200 | sie wäre ohne die Behebung rot |
| Riegel `druckblatt` | Selbstprobe **9/0**; gegen diesen Stand 226 Dateien, **0 Befunde**; gegen `server/` von `main` **8 Befunde** (drei Formulare, drei Lesestellen, zwei fehlende Abweisungen) | der Riegel hält den alten Weg auf |
| hell gegen dunkel, Chromium, fünf Ansichten (Notfallblatt leer, gefüllt, gefüllt im Druck, Codeblatt, Anmeldung mit Fehlermeldung im Druck) | **0 von 158 Elementen** weichen ab (Farbe, Fläche, Rand); mit `style.css` von `main` **49** | Nr. 540 |

**Nach dem Merge (Lauf 192, 10.11.2026):** alle Jobs grün; Staging meldet
24.11.0, ein POST an `notfallblatt.php` bekommt dort 400. **R91 auf
Staging:** Notfallblattprobe gegen Staging in drei Engines je 16 von 16
(Wegwerfkonto, angelegt und gelöscht mit dem BetreiberIn-Konto der
Arbeitsumgebung); hell gegen dunkel 0 von 173 Elementen abweichend.
Offen bleiben Papier und ein echter Safari (P-KA-03).

Prüfstand und Bericht: im Commit von KA-02. Der volle Lauf (`39a9420`,
Stufe neben, drei Engines, zehn Breiten, 8 852 s) war **64 grün, 1 rot** —
die Wartungslaufprobe, unabhängig von KA-02 (F-KA-54); der Kopf des PR erbt
die 64 und misst die berichtigte Probe neu.

### KA-03 (10.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Der Weg der Tokens auf Produktiv.** Dass ein Klartext-Kettentoken beim
   ersten Lesen gehasht wird und weiter gilt, belegt die Wartungsprobe
   örtlich (Lage „Übergang aus Klartext"); ob die Kette nach dem Tag grün
   bleibt, zeigt erst der Lauf (P-KA-06).
2. **Ob auf Produktiv ein SFTP-Ziel ohne Abdruck liegt** — örtlich gibt es
   keine Ziele (gemessen: 0 Zeilen in `backup_targets`). P-KA-05.
3. **Die Gegenprobe für die Tokens ist ein Abbruch, keine rote Zeile:**
   Jobs- und Wartungsprobe rufen die neue Schnittstelle (`jobs_token_neu()`)
   und brechen gegen `main` mit „undefined function" ab. Rot sind sie, aber
   nicht an der Stelle, die den Befund misst.
4. **Eine echte Mail im Postfach** (Konto mit Rechten, Adressen der
   Installation, Adressbestätigung im Mailprogramm): gemessen ist die Zeile
   in der Warteschlange, nicht die Zustellung. P-KA-07.

**Gemessen** (jede Zahl mit ihrem Gegenstand; Gegenprobe = dieselbe Probe
gegen die 19 Dateien unter `server/` von `main`):

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Rollenprobe | **634 Erwartungen, 0 nicht erfüllt** — neu: zwei Matrixzeilen (Konto mit Rechten mit frischem Code; Adressen: Admin 403) und der Teil „Schranken der Verwaltung" (9 Zeilen: Mail an die BetreiberInnen, Protokoll und Mail an die alte Adresse, Einspielen nach oben abgewiesen, Löschbestätigung gegen den Serverwert, Demo 403, CSP ohne Fremdtext, Eingabeprüfung, Adresslink per GET ohne Wirkung, per POST mit) | **10 nicht erfüllt** — jede neue Zeile außer „Knopf per POST" (die gilt auch vorher) | F-KA-03, -20, -21, -28, -35, -41 |
| Zweitfaktorprobe | **142 ok, 0 fehlen** — neu in 5b: Kontosperre gesetzt, neuer Browser bekommt die Sperrmeldung, der Browser mit gemerktem Gerät kommt herein | **1 fehlt** — genau „gemerktes Gerät: angemeldet" (200 statt 302) | F-KA-42 |
| Freigabeprobe, Chromium | **27 Erwartungen, 0 nicht erfüllt** — neu: Adresse ohne offene Bestätigung erlaubt, mit offener abgewiesen; die Antwort trägt `pat_wrap_rc`, kein `pat_key_check` | **2 nicht erfüllt** — beide neuen | F-KA-08 |
| Versandprobe (pyftpdlib, paramiko) | **143 Erwartungen, 0 nicht erfüllt** — neu: ohne Abdruck 0 Dateien gesendet und die Meldung „noch kein Hostschlüssel gespeichert" | **2 nicht erfüllt** — der Job sendete 8 Dateien an das ungeprüfte Ziel | F-KA-09 |
| Jobsprobe, Wartungsprobe | **42/0 und 83/0** — Teil 8 und 9 bis 9g: gehasht gespeichert, Prüfen über `…_pruefen()`, Übergang aus Klartext, Ausgangswerte zurückgelegt | Abbruch (siehe oben, 3.) | F-KA-19 |

**Die Befunde an der heutigen Stelle:** Alle zehn halten — die Gegenprobe
ist für jeden außer F-KA-19 eine rote Zeile gegen `main`; F-KA-19 stand
sichtbar in `app_state` (Klartext vor dem ersten Lesen durch den neuen
Code).

Prüfstand und Bericht: im Commit von KA-03.

**Nach dem Merge (Lauf 194, 10.11.2026):** alle Jobs grün, Stufe 2
eingeschlossen; das Backup-Tor sprach mit dem Kettentoken aus GitHub gegen
den neuen Code — der Übergang aus Klartext (E-KA-22) hat damit auf Staging
gehalten. **R91 auf Staging:** Betrieb → Hintergrundjobs mit dem
BetreiberIn-Konto der Arbeitsumgebung, Chromium: drei Karten
„eingerichtet“, kein Tokenwert auf der Seite (gezählt: vier Werte mit
64 Hexzeichen, alle `csrf`). Kein neues Health-Token erzeugt, die
Adressbestätigung (P-KA-07) nicht durchgespielt — sie änderte die Adresse
des eigenen Kontos.

### KA-05 (11.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Die `.htaccess` auf den echten Hostern.** Gemessen ist sie mit einem
   Apache 2.4.58 im Container (`AllowOverride All`, HTTPS); ob Produktiv
   (Plesk) und Staging (lima-city) sie genauso lesen, zeigt erst die
   Anlage (Stufe 2, R91, P-KA-11). `Options -Indexes` ist deshalb nicht
   drin (F-KA-70).
2. **Die Lücken in SheetJS 0.18.5 selbst** sind nicht nachgestellt (Konzept:
   „nicht nachgestellt wird die Lücke selbst"); belegt ist die neue Fassung
   und das gehärtete Gadget.
3. **Gegenproben durch Abbruch statt Zeile:** Wiederherstellung Teil 17
   (alter Code bricht mit Datenbankfehler ab) und der Arbeitsordner des
   Protokollarchivs (Funktion fehlt auf `main`).

**Gemessen** (Gegenprobe = mit dem Stand von `main` für die betroffene Datei):

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Rollenprobe | **645 Erwartungen, 0 nicht erfüllt** — neu: sechs anlegende Wege bei vollem Konto 507/Satz, Bearbeiten frei, Gegenlage ohne Grenze 5 von 5 ohne 507, Demo 500/50; `api/range.php` mit Zeilenumbruch 400; CSP-Bericht 30 → 20 Zeilen | CSP: 30 Zeilen (rot); Mengenteil: Abbruch (neue Funktion fehlt) | F-KA-10, -34, -40 |
| Wiederherstellungsprobe | **149 Erwartungen, 0 nicht erfüllt** — neu Teil 17 (Katalogspalten, `notes`) und 18 (Fassung 2, Begleitdatei, Passphrase, Arbeitsordner) | Teil 17 Abbruch (Datenbankfehler), Teil 18 (1)(2)(3) rot | F-KA-22, -40 |
| Bedienprobe, Weg `ka05-fremdbestandteile` | SheetJS 0.20.3 geladen, geerbtes `knopf`/`roh` wirkt nicht, eigenes `knopf` wirkt | dasselbe mit `html.js` von `main` (Node): geerbtes `knopf` und `roh` WIRKEN | F-KA-11 |
| Kreislauf CSV, drei Engines | je **0 unerklärte, 1 324 erwartete Abweichungen** | — | SheetJS 0.20.3 liest und schreibt wie 0.18.5 |
| Riegel `schranken` (Nr. 562) | Selbstprobe **8/0**; 164 Dateien, **0 Befunde** | gegen `server/` von `main` **166 Befunde** (102 Anker, 62 Wächter, 2 `.htaccess`) | F-KA-23, -34, -38 |
| Apache 2.4.58 im Container, HTTPS | `migrations/*.sql`, `jobs_lib.php`, `blatt_dialog.php`, `schluessel_teile.php` 403; `.well-known/` 404, `login.php` und `assets/` erreichbar | mit `.htaccess` von `main`: SQL 200, `jobs_lib.php` 200, `blatt_dialog.php` 200 | F-KA-23, -38 |
| eingebauter PHP-Server, ohne `.htaccess` | Wächter: `jobs_lib.php`, `blatt_dialog.php`, `schluessel_teile.php`, `konto_lib.php` 404 | alter `blatt_dialog.php` 500 (Systemmeldung), alter `jobs_lib.php` 200 | F-KA-23 |
| Zählung | **51 Zeilen, 0 über der Decke** — neu Z51 (Satz „Das Konto ist voll") | Start 1 (`ingest.php`) | eine Stelle |
| Ingest-, Kopplungs-, Jobs-, Wartungs-, Wache-Selbstprobe | 99/0, 87/0, 42/0, 83/0, 55/0 | — | nach dem Anker-Umbau nichts gebrochen |

**Fassung 2 vorher gezählt** (Konzept, Punkt 7): örtlich 6 Pakete, alle
Fassung 3; 3 Begleitdateien, alle versiegelt. Staging und Produktiv nicht
gezählt (E-KA-31).

**R91 nach PR #187 (Web 24.19.0, Staging, 15.11.2026).** Lauf 215 grün
(Staging ausgeliefert in 47 s, Stufe 2 mit Kreislauf `edbak` und
Rückwegprobe); Staging meldet v24.19.0, `standbuch.js`, `huelle2.js` und
`ortsdaten.js` liegen dort byte-gleich zum Repositorium. Mit eigenem Konto:
Betrieb → Status ohne neuen Befund (ein Hinweis: ein nie gesichertes
Konto), Hintergrundjobs ohne gescheiterten Job — P-BR5-08 hat damit wieder
keinen Gegenstand. Für 06f selbst gibt es auf Staging keinen Punkt der
Arbeitsumgebung: Der Befund liegt hinter der Weiche, das Handy braucht ein
Gerät (P-KA-16). Danach `staging_konten.py --wirklich`: ein Konto der Kette
gelöscht, vier behalten.

### R91 nach PR #190 — Web 25.0.0 auf Staging (16.11.2026)

**Was nicht geprüft werden konnte — zuerst.** Das eigene Konto auf Staging
hat einen manuell angelegten Einsatz ohne geschützte Angaben und kein
gekoppeltes Gerät: Anhebelauf, Befunde und „Geräte neu verbinden“ hatten
dort nichts zu zeigen. Das bleibt P-KA-17 bis -20 (BetreiberIn, Produktiv).

| Mittel | Ergebnis | Was es belegt |
|---|---|---|
| Lauf 219 (`cae436c`) | grün: Auslieferung, Stufe 2 mit Kreislauf `edbak` und Rückwegprobe in drei Engines, 13 min | die Anlage nimmt Web 25.0.0, kein Schema offen |
| Anmeldung mit eigenem Konto, Chromium, dreimal | 4,9 / 5,4 / 7,0 s samt Zweitfaktor und Netz | 1 200 000 Runden auf dem Weg der BetreiberIn |
| Startseite, Geräteseite, Einsatz 20332, Betrieb → Status | **0** sichtbare Meldungen, **0** Konsolenfehler, Fußzeile v25.0.0 | kein Befund, kein Hinweis „Geräte neu verbinden“ ohne Anlass |
| Prüfliste | P-PU-05 (Kopf `voll:haupt`, drei Engines, zehn Breiten), P-PR-01 (`freigabeprobe` in drei Engines grün), P-PR-02 (`nummern=0`, Bericht im Merge-Commit) bestanden, gestrichen | — |
| `staging_konten.py --wirklich` | 1 Konto gelöscht (`umlauf-edbak@…`), 4 behalten | aufgeräumt |

Erster Lauf meines Skripts meldete „gesperrt“ und „nicht lesen“: Es las
versteckte Meldungen mit (`allInnerTexts` ohne `:visible`); nach der
Berichtigung keine.

### Backlog Nr. 566, 568, 569 vor dem PR (16.11.2026)

Auf Wunsch der BetreiberIn vor dem gesammelten PR abgearbeitet, damit ein
Prüfstand reicht. Keine eigene Nummer: 566 fährt in Android 0.26.0, 568 und
569 in Web 25.0.0.

**Was nicht geprüft werden konnte — zuerst.** (1) Nr. 566: das Rennen an
einem echten Handy (Phase während einer laufenden Anfrage) — gemessen nur
unter Robolectric. (2) Nr. 568: ein echtes, von außen beschädigtes Buch auf
Staging; die Probe legt einen Zufallstext örtlich ab. (3) Nr. 568 in Firefox
und WebKit — gefahren im Prüfstand, nicht einzeln. (4) Nr. 569: kein Vorher vor der
Weiche auf dieser Maschine — Vergleich gegen den KA-14-Lauf desselben Tags;
je Messung ein Lauf. **Offen gelassen:** Im Backup gehen noch rund 49 000 von
173 000 Krypto-Aufrufen ans Standbuch (`eintrag()` rechnet den Hash zweimal,
`patOeffnen()` je Block `kontoKennung()`); mit 06e. Die Reihenfolge im
CSV-Archiv hat sich geändert (Spuren vorn) — keine Zusage, in
`Export-Format.md` 3 vermerkt.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Nr. 566 `./gradlew --offline build` | Handy **402** Prüffälle je Bauart (15 übersprungen), Uhr 75, **0** rot | vier eingebaute Gegenfehler, alle rot (Commit `6fe9bfa`) | Schema 4, Bestätigung nur für den gesendeten Stand |
| Nr. 568 Bedienprobe `geraetebuch-unlesbar-neu`, `-zurueck` (Chromium) | **2 / 0**: ohne eigene Fassung v1 → v2, Buch geht auf, Merker gesetzt, Hinweis auf Geräte- und Startseite, Zeile „neu verbinden · …“; mit eigener Fassung v2 → v3, still, kein Merker | `buchUnlesbar()` liefert immer falsch: **0 / 2** (Buch bleibt v1/v2 und geht nicht auf) | E-KA-92, Mockup M-KA-04 |
| Nr. 569 Messstand `messen.py --nur Backup,GPX` (5 088 Einsätze, Drossel 6×, Fable-Agent) | Backup **117,4 → 98,1 s** (Halde 100 → 67 MB, Krypto 143 639 → 113 784); GPX mit Passwort **223,6 → 116,6 s** (364 → 84 MB), ohne **221,3 → 114,5 s** (411 → 88 MB) | CPU-Profil je Schritt (neu `--profil`): Zeit lag in `zip.TextReader` (~48 s), `new Intl.DateTimeFormat` je Feld (~35 s), `Uint8Array.from(atob())` (14–19 s), `seitenbuch()` je Hülle (15 635 ×) — nicht in der Rechnung der Fassung 2 | der Titel von Nr. 569 ist so nicht belegt; behoben ist die Zeit trotzdem |
| Nr. 569 Hüllenprobe `tools/proben/huelle/probe.sh` | **606 / 0**, Ortsdatenprobe **19 / 0** | `zsFach` mit festem Schlüssel: 126 Abweichungen (`pruefwert`); `sidFach` mit festem Schlüssel: SID nicht byte-gleich — beide rot, zurückgenommen | die Fächer hängen am ganzen Schlüssel |
| Nr. 569 Kreislauf `edbak --frisch`, `csv --frisch` | 345 356 bzw. 11 424 Vergleiche, **0** unerklärt | — | Sicherung und CSV inhaltsgleich |
| Nr. 569 GPX gegen Referenzarchiv 05.11. | 212 / 212 GPX inhaltsgleich (Kennung, `creator`, Zeit ausgeblendet); Firefox und WebKit je ein Export, Archiv intakt | — | der Strom ins Archiv ändert keinen Inhalt; **nicht geöffnet:** das AES-verschlüsselte Archiv (kein Werkzeug im Container), nur der Download belegt |
| Nr. 568 erster Lauf | „neu“ ohne Hinweis | — | kein Fehler: ohne gekoppeltes Gerät mit Schlüssel ist nichts neu zu verbinden, die Geräteseite quittiert den Merker; die Probe legt jetzt ein Gerät an |

### KA-14 Die Weiche wird umgelegt — Web 25.0.0 (15./16.11.2026)

**Was nicht geprüft werden konnte — zuerst.** (1) **Produktiv:** Der
Anhebelauf am echten Bestand, die Anmeldung auf echten Handys mit 1 200 000
Runden und das Neukoppeln aller Geräte prüft die BetreiberIn nach dem Tag
(P-KA-18, -19). (2) **Ein echter Hinweis „Uhrzeit prüfen“** entsteht nur mit
zwei Geräten und einer nachgehenden Uhr; die Seite ist mit einem
eingespielten Hinweis gemessen (Bedienprobe `ka11-*`), der Kern an den
Vektoren. (3) Ein echtes Safari. (4) Die Garmin-Uhr folgt E-KA-80 erst mit KG.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Prüfstand `haupt`, erster Lauf | **11 rot**, 1 nicht gemessen, 61 grün, 11 990 s | — | Befunde F-KA-122 bis -126 |
| Prüfstand `haupt`, zweiter Lauf (nach den Behebungen) | **74 grün, 0 rot, 0 nicht gemessen**, 9 906 s; drei Engines, zehn Breiten, alle Seiten, `handy=gebaut`; Bericht im Commit, `bericht.py` „in Ordnung“ (80 Zahlen) | Gegenfehler je Behebung (Agent): Kreislauf 222 Spuren / 1 Abweichung, 242 × 409, Freigabe 400, Kopplung rot | Web 25.0.0 mit umgelegter Weiche über alle Wege |
| Messstand (5 088 Einsätze, Drossel 6×) | Backup 117 s, GPX mit/ohne Passwort 224 / 221 s, 0 × 409 | erster Lauf: GPX Abbruch nach 1 800 s | F-KA-124 behoben; langsamer als vor der Weiche (Nr. 569; Halde beim GPX 364–411 MB) |
| Bedienprobe `ka06b4` mit alter Fixture | Altbestand 104 → 0, `kp`/`rw` `edk2:`, 16 von 16 Blättern zu | — | Anhebelauf mit ausgelieferter Weiche |
| Bedienprobe `ka11-*` (neu) | 3 / 3 (M-KA-03 B, C, E) | — | Anzeige und Rückweg der Seite |
| `demo_fassung2.mjs --zweimal` | Ohne Befund (106 Einsätze, 118 Segmente, Gerätebuch 2 Geräte) | — | Demo mit Weiche an |
| Anmeldung Demo-Konto | Chromium 1,0–1,4 s, WebKit 1,5 s (Container) | — | Rundenziel 1 200 000 |

### KA-13 Demo-Bestand in Fassung 2 (15.11.2026, Fable-Agent)

**Was nicht geprüft werden konnte — zuerst.** (1) Der volle Einspiellauf
und der Prüfstand; der Mischzustand „Einspiellauf schreibt Fassung 1,
Browserschritte nach KA-14 Fassung 2“ ist nie durchgespielt (Nr. 567).
(2) `demo_pruefen.mjs`, Abschnitt `spurfaelle`, bricht an einer verborgenen
Zeile ab — auch mit der alten Fixture, älter als KA-13. (3) Gemessen wurde
gegen eine Abschrift von `server/` mit Weiche an auf Port 8444; der Baum
selbst lädt die Fixture erst mit KA-14.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `demo_fassung2.mjs --zweimal` (neu), drei Engines | je **30 / 0**: 106 Einsätze und 118 Segmente gehen auf, 63 366 Punkte, kein Stück in Fassung 1, kein Befund, Marke nicht gesetzt | Demo-Gerät nicht im Gerätebuch: rot (26 Einsätze, 11 Segmente, `absender`) | Demo mit Weiche an |
| `verschliessen.py --selbstprobe`, `abgleich.py` (auch `--tief`), `riegelprobe.php`, `sitzungsprobe.py` | 9 / 0, „Gleich.“ und 10 / 0, 11 von 11, 2 von 2 | zweimal gebaut byte-gleich | Fixture, Riegel |
| Hüllenprobe | 606 / 0 | — | nichts am Kern gekippt |

Fixture 903 114 → 1 100 377 Byte; Einsatzhüllen 2 vom Gerät, 104 vom Konto
(jedes Speichern im Formular verschließt als Konto), Spuren 197 Gerät, 26
Konto. **Zwischenfall:** Eine Probe gegen die Abschrift hob das Konto
`admin@example.invalid` der örtlichen Anlage still auf 1 200 000 / `edka2:`;
der Agent hat es zurückgestellt. **Zweifel:** `rc_hex` steht in der Fixture
(öffentlich wie das Demo-Passwort); jeder Reset hinterlässt im Browser ein
verwaistes Buch; Format 1–3 und der Zweig `'server'` in `edbak_restore()`
sind Rückbau für 06e.

### KA-10 Browser-Kern: Rückweg (15.11.2026, Fable-Agent)

**Was nicht geprüft werden konnte — zuerst.** (1) Nur Chromium.
(2) Kein Lauf gegen eine Anlage (`apiTransport`, IndexedDB, echte Seite) —
das fährt der Prüfstand mit KA-14. (3) Mit Weiche aus ist „kein sichtbarer
Weg ändert sich“ gelesen; die Hüllenprobe ruft `EdOrtsdaten` nicht.
(4) Eine Bestätigung, die sich nur im Vermerk vom geltenden Hinweis
unterscheidet, deckt kein Vektor (eingebauter Fehler blieb grün).

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Hüllenprobe `probe.sh` (Chromium) | **606 / 0, 0 ausgenommen** (39 + 271 + 296; vorher 450 / 156 ausgenommen) | Hauptinstanz nachgefahren; 13 von 14 eingebauten Fehlern rot (Hüllenprobe oder Ortsdatenprobe) | Kern nach E-KA-88 |
| `ortsdaten_probe.mjs` (neu, aus dem Scratchpad des Agenten übernommen) | **17 / 0** | siehe oben | Fortschreiben, Hinweis, Übernehmen, Befunde über `EdOrtsdaten` |

**Schnittstelle für KA-11:** `EdOrtsdaten.einsatz()` unverändert (nur
Inhalt); neu `einsatzOeffnen()` → `{inhalt, absender, befund}`,
`einsatzUebernehmen(m, k, hinweis)`, `fuerAnsicht(m, ck, {bestaetigt})`
setzt `m.ort_absender`, `m.ort_befund`; der Hinweis ist ein Fehler mit
`grund: 'uhrzeit-pruefen'`, `hinweis`, `inhalt`. **Zweifel:** Die vier
übrigen Aufrufer von `einsatz()` (Export, Import, Formular, Einstellungen)
behandeln den Hinweis wie „geht nicht auf“; `eintragOrdnen` im Browser
prüft die Form von `a` und `u` nicht (die Referenz wirft `innen`).

### KA-15 Android 0.26.0: ohne Geräteschlüssel kein Senden (15.11.2026, Fable-Agent für den Sender)

**Was nicht geprüft werden konnte — zuerst.** (1) Der echte Keystore:
„Eintrag fehlt“ ist mit der Ablage im Speicher gespielt; wie ein Gerät
einen vorübergehenden Ausfall meldet, zeigt nur P-KA-16. (2) Kein
Emulatorlauf für 0.26.0 — die Anzeige belegt der Bilderlauf, der Weg
„Neu koppeln“ → Trennfrage ist nur gelesen. (3) `herkunftsstand()` in
`NAdokuApp` hat keinen eigenen Prüffall, nur die Regel dahinter.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `./gradlew --offline build`, Hauptinstanz nach der Anzeige | BUILD SUCCESSFUL in 4 min 35 s; Lint 0 Fehler; Handy **398** je Bauart (15 übersprungen), Uhr 75, 0 rot | Agent: acht eingebaute Fehler (Halt entfernt, Tresor nicht neu gelesen, `schluesselHolen` mit Paarbedingung, Trennen ohne Ausnahme, Trennen zu weit, Ausfall setzt `herkunftFehlt`, Halt ohne `huelle2`, Halt ohne `spaeterErneut`) — je 1 bis 26 Fälle rot | E-KA-80 im Sender, Trennen trotz Rückstand |
| Bilderlauf `HandyBildTest` | neu `bereit-herkunft-fehlt` (hell, dunkel, zwei Breiten) | gegen M-KA-03 F angesehen | Anzeige nach E-KA-87 |

**Über den Auftrag hinaus (Agent, übernommen):** `schluesselHolen()`
merkt sich `huelle` unabhängig vom Paar (sonst schrieb sich ein Handy ohne
Paar selbst auf Fassung 1 zurück); `trennen()` erlaubt bei `FEHLT` trotz
Rückstand, mit Bindung an den Kontoschlüssel — sonst wäre „Neu koppeln“
eine Sackgasse. **Zweifel:** Der gebundene Rückstand hat die 30-Tage-Frist
wie nach einer Abmeldung; der Nachsende-Job läuft bei dauerhaft fehlendem
Paar mit Rückzug bis 5 h weiter, ohne Netzanfrage.

### KA-09 Referenz: Rückweg bei zu hohem Zählerstand (15.11.2026, Fable-Agent)

**Was nicht geprüft werden konnte — zuerst.** (1) 156 Browserprüfungen
sind „ausgenommen“, bis KA-10 sie baut; dass sie erfüllbar sind, zeigt ein
Probeumbau des Agenten im Scratchpad (606 / 0 / 0), nicht der Code.
(2) Nur Chromium. (3) Android- und Uhr-Prüffälle nicht gefahren (neuer
Schlüssel `rueckweg`, nach Lesen der Quellen ohne Wirkung auf sie).

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `referenz.py pruefen` / `--selbstprobe` | Fassung 2 **47 geöffnet, 50 Gegenlagen, 5 Hinweise** (vorher 32/40), 0 Abweichungen; Selbstprobe **48 / 0** (19 eingebaute Fehler) | Hauptinstanz: dieselben Zahlen; zweites `erzeugen` ändert nichts | Regel E-KA-88 |
| `referenz_inhalt.py` | **26 / 28**, Selbstprobe **28 / 0**; Vektor `standblaetter` (drei Blätter byte-genau) | nachgefahren | Blattformat mit `a`, `u` |
| `git diff --numstat` | `vektoren-2.json` 1090 / **0**, `vektoren-inhalt.json` 74 / **0**; `vektoren.json` und vier `.mc` unverändert | nachgefahren | kein bestehender Vektor gekippt |
| Browserprobe (Chromium) | **450 / 0, 156 ausgenommen** | Probeumbau: 606 / 0 / 0; neues `huelle2.js` mit altem `standbuch.js`: 26 rot | was KA-10 bauen muss |

**Folge für den Betrieb:** Das nach einer Übergabe nachgesendete alte
Paket ist mit `a` im Stand ein Hinweis, nicht mehr hart (E-KA-70 so
gewollt). Offen aus dem Bericht: Altblatt ohne `a` bleibt hart; Konto mit
gleichem `z` gegen Gerätestand wird still angenommen (F-KA-112 nur für
Geräte geschlossen); zwei Browser bestätigen gegensätzlich — der höhere
`o` gewinnt, eine Entscheidung ist neu zu treffen.

### KA-06f-4 Browser: Befund `offen-nach-schluss` (15.11.2026, Hauptinstanz)

**Was nicht geprüft werden konnte — zuerst.** (1) Dass das Konto
`schluss` weiterträgt (`EdOrtsdaten.einsatzVerschliessen()`) und das
Lesen `f` ins Standbuch schreibt (`EdOrtsdaten.einsatz()`), misst keine
Probe einzeln — beides liegt hinter der Weiche, und die Browserprobe ruft
den Kern, nicht `EdOrtsdaten`. Gelesen, nicht gefahren; der Prüfstand
fährt die Bedienproben der Weiche. (2) Das Zusammenführen von `f` bei
einem 409-Konflikt (`hoeher()`) ist nicht gemessen; die neue Prüfung deckt
Schreiben, Reisen und Weiterschreiben durch einen dritten Browser.
(3) Angezeigt wird der Befund nirgends — das kommt mit E-KA-70.
(4) Nur Chromium; Firefox und WebKit fährt der Prüfstand.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Browserprobe `tools/proben/huelle/probe.sh` (Chromium) | **438 Prüfungen, 0 Abweichungen, 0 ausgenommen** (39 + 188 Weiche aus + 211 an; vorher 427 mit 10 ausgenommen); Server 22 / 0 | Befund nie geliefert: **4** rot (zwei Fälle × zwei Weichenstellungen); `f` aus `FELDER` genommen: **1** rot (die neue Prüfung) — beide zurückgenommen | `einsatzLesen()` liefert den Befund wie `einsatz_befunde()` der Referenz; `f` steht nach `k`, reist mit und bleibt |

### KA-06f-3 Handy: Zähler am Inhalt (15.11.2026, Fable-Agent für den Kern)

**Was nicht geprüft werden konnte — zuerst.** (1) Kein echtes Gerät und
kein Emulatorlauf für 06f-3: Der Puffer ist SQLite unter Robolectric, das
genügt für Schema und Regeln; eine Migration auf einem Handy mit echtem
Bestand aus 0.24.0 zeigt erst P-KA-16. (2) Ein Prozesstod mitten im
`fsync` der Zählerdatei ist nicht gespielt, nur als zurückgerollte
Transaktion und neu geöffneter Puffer. (3) Das Rennen aus Backlog Nr. 566
ist gelesen, nicht gemessen. (4) Der Container startete zweimal neu; der
Agent war vor dem ersten Neustart fertig, sein Stand lag unversehrt im
Arbeitsbaum.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `./gradlew --offline build`, Hauptinstanz nach F-KA-117 | BUILD SUCCESSFUL in 5 min 12 s; Lint 0 Fehler, Handy 6 Warnungen (Versionshinweise); Handy **389** Prüffälle je Bauart (15 übersprungen), Uhr 75, **0** rot | der Agent: 388 / 0 vor F-KA-117; zehn eingebaute Fehler (z je Anfrage neu, Zähler nicht gespeichert, Phase oder Abschluss ohne neues z, Einsatz ohne z, Migration verliert Pakete oder Spalte, zweites Schließen kostet z, Zählerausfall behält alten Zähler, Gerätezähler nicht abgelegt) — je 1 bis 17 Fälle rot | Schema 3, Regel E-KA-69 am Handy, Migration |
| `EinsatzzaehlerTest` (neu, 10) gegen den Vektor `zaehler` | alle zwölf Schritte gespielt; 34 geöffnete Hüllen, 61 Paare mit gleichem `z` alle innen byte-gleich, 500 Paare mit anderem Inhalt alle mit anderem `z` | siehe oben | die beiden Messsätze aus E-KA-74 |
| `SenderTest.einAbschlussOhneNeuenPunktGehtHinaus` (F-KA-117) | grün | ohne `metadaten_bestaetigt = 0` in `paketSchliessen()`: rot, einziger von 35 | der Abschluss geht hinaus |
| `:handy:assembleRelease` nach E-KA-77 | 9 251 914 B, `classes*.dex` `Defl:N` | vorher 24 816 714 B, `Stored` | F-KA-118 |

**Zweifel des Agenten:** Anlegen und erste Phase kosten zwei `z` (T, T+1)
— regelkonform. Jede Inhaltsänderung schreibt die Zählerdatei mit `fsync`
in der Transaktion, nur bei Phasen, nie bei Punkten. An `Z_MAX` bleibt der
Zähler stehen; dort gilt „anderer Inhalt ⇒ größeres z“ nicht mehr (war
schon so). Während seines Baus beendete der Kernel einmal einen Prozess
wegen Speichermangels; der Bau lief durch.

### KA-06f-2 Handy: Geräteschlüssel im Keystore, `minSdk` 31 (15.11.2026, Fable-Agent für den Kern)

**Was nicht geprüft werden konnte — zuerst.** (1) **Kein echtes Gerät:**
Der Gerätetest lief im Emulator (API 37, `google_apis` x86_64, ohne KVM);
der Keystore meldet dort `securityLevel=0` (Software). Ob ein Handy den
Schlüssel im TEE oder in einer StrongBox hält, zeigt nur ein Gerät
(P-KA-16). (2) Ein Sendelauf hinter der Bildschirmsperre und ein
Prozesstod mitten im Schreiben der Datei sind nicht gemessen; der
Prozesstod zwischen zwei Schritten nur mit der Ablage im Speicher.
(3) Ein Handy unter Android 12, das die Installation ablehnt, ist nicht
gefahren (P-KA-16). (4) Gradle-Prüfläufe für 06f-2 allein hat nur der
Agent gefahren; die Hauptinstanz fährt den vollen Bau mit 06f-3.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `./gradlew build :handy:assembleDebugAndroidTest` (Agent) | BUILD SUCCESSFUL in 11 min 16 s; Lint 0 Fehler, Handy 6 Warnungen (alle Versionshinweise in `libs.versions.toml`); Handy 374 Prüffälle je Bauart, 15 übersprungen, 0 rot | zehn eingebaute Fehler (Versuch ohne Festlegung gültig, alter Schlüssel vor dem Schreiben gelöscht, `Z_s` mit falschem Schlüssel, Griff ohne `spk`-Vergleich, `geraet.bin.neu` bleibt, Sender fängt den Ausfall nicht, `vergessen` lässt Schlüssel liegen, keine Waisenräumung, zweite Antwort gilt, Verwaltung vor dem Schlüssel) — jeder rot (1 bis 14 Fälle), zurückgenommen | Regeln, Prozesstod an zehn Stellen, Byte-Gleichheit gegen `vektoren-2.json` über eine Software-Ablage |
| Gerätetest im Emulator, Hauptinstanz (`am instrument`, API 37) | `GeraeteschluesselGeraetTest` **6 / 6**, `GeraetTresorTest` **6 / 6**, `GeraetehuelleGeraetTest` **2 / 2** | — | Eintrag im AndroidKeyStore, nicht exportierbar, nur `PURPOSE_AGREE_KEY`, keine Nutzerauthentisierung, `Z_s` byte-gleich zur Software-Rechnung, Hülle geht mit dem Kontoschlüssel der Vektoren auf; Ablauf mit echten Aliassen |

**Zweifel des Agenten:** (1) Auf Geräten mit älterem Keymaster hält
keystore2 ECDH-Schlüssel in Software — nicht exportierbar, aber nicht im
TEE; steht im Bedrohungsmodell. (2) Q-KA-20. (3) Scheitert das Löschen
eines Alias, bleibt ein Waise bis zum nächsten `beginnen`/`vergessen`.
(4) Die Datei hängt an `renameTo` wie der Tresor. Der Agent hat über den
Auftrag hinaus `Sender.kt` angefasst (E-KA-75) — übernommen.

### KA-06f-1 Zählerregel in der Referenz (15.11.2026, Fable-Agent, E-KA-73)

**Was nicht geprüft werden konnte — zuerst.** (1) Die Browserprobe lief nur
in Chromium (156.0.8078.4); Firefox und WebKit fährt der Prüfstand am Ende
von KA-06f. (2) Der Befund `offen-nach-schluss` ist in der Referenz
gemessen, im Browser nicht — `EdHuelle2.einsatzLesen()` liefert ihn noch
nicht; die Probe zählt die 10 Prüfungen dafür als „ausgenommen“ (06f-4).
(3) Die Android-Prüffälle, die `vektoren-2.json` lesen, laufen erst mit
06f-2/-3.

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| `referenz.py pruefen` nach `erzeugen` | Fassung 1 6 / 8; Fassung 2 **32 geöffnet, 40 Gegenlagen** (vorher 26/39), 0 Abweichungen | von der Hauptinstanz nachgefahren, gleiche Zahlen; zweites `erzeugen` ändert keine Datei | Regel und Vektoren stimmen überein |
| `referenz.py --selbstprobe` | **28 / 0** (vorher 19); neun neue Fehlerarten, je mit benannter Meldung | nachgefahren | die neuen Prüfungen schlagen an |
| `git diff --numstat` | `vektoren-2.json` 222 / **0**, `vektoren.json` unverändert; die vier `.mc`-Dateien byte-gleich | nachgefahren | kein bestehender Vektor gekippt, Uhr unberührt |
| Browserprobe `probe.sh` (Chromium) | **427 Prüfungen, 0 Abweichungen, 10 ausgenommen** (39 + 183 Weiche aus + 205 an; vorher 415); Server 22 / 0 | einzeln von der Hauptinstanz gefahren: dieselben Zahlen, rc 0 | `server/assets/` liest die neuen Fälle wie die Referenz |

**Neue Fälle unter `einsaetze`** (Übergabe wie in 06c: Handy ändert bei
T0+1320, Uhr schließt bei T0+1500 ab): `inhalt-alter-stand-nachgesendet` →
`zurueckgelegt` (der Unterschied zu E-KA-64); `inhalt-gleich-neu-verschlossen`
→ angenommen, Stand bleibt; `inhalt-geaendert-nach-uebergabe` → angenommen;
`inhalt-offen-nach-schluss` und `inhalt-gleiches-z-offen-nach-schluss` →
angenommen mit Befund (der zweite fängt F-KA-112 halb);
`inhalt-offen-ohne-schluss-im-stand` → ohne Befund. Dazu der Schlüssel
`zaehler`: zwölf Schritte eines Geräts mit drei Einsätzen — der Vektor für
Handy (06f-3) und KG.

**Was der Browser für 06f-4 braucht** (Bericht des Agenten): Feld `f` in
`standbuch.js` (`FELDER` p, o, k, f, s, d, x; Zusammenführen „einer hat
es“), beim Abnehmen nach `stand_nach_abnahme()`, `einsatzLesen()` gibt
`befund: []` zurück; dann misst die Probe die 10 ausgenommenen Prüfungen
von selbst. `vektoren-inhalt.json` trägt `f` noch nicht — das Blattformat
braucht dafür in 06f-4 einen Fall in `referenz_inhalt.py`.

**Zweifel des Agenten, die offen bleiben:** Q-KA-18 und -19 (vor 06f-4);
„Inhalt geändert“ besser per Bytevergleich vor dem Vergeben (Vorgabe für
06f-3 und KG); F-KA-112 bleibt bei zwei offenen oder zwei abgeschlossenen
Ständen mit gleichem `z` bestehen (erst E-KA-70 schließt das); F-KA-116.
Die LIESMICH nannte für `referenz_inhalt.py` 18 Fälle, gemessen sind 21 —
auch vor dieser Änderung; berichtigt.

### KA-06c Handy: Kopplung mit Vergleichswert und Fassung 2 (14.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Kein Gerät, kein Emulator.** Alles lief unter Robolectric/JVM. Dass
   JCA-EC (`KeyFactory`, `KeyAgreement`) auf echtem Android (Conscrypt,
   API 26 bis 36) rechnet wie hier und der Dateischlüssel aus dem
   `AndroidKeyStore` kommt, zeigt erst der Gerätetest (P-KA-16).
2. **Fassung 2 vom Handy am echten Eingang.** `huelle: 2` sagt der Server
   nur, wenn der Browser das Gerät ins Gerätebuch eingetragen hat — und das
   tut er erst mit umgelegter Weiche (Web 25.0.0). Bis dahin sendet auch
   ein Handy 0.24.0 Fassung 1. Gemessen ist der Sendekörper (geöffnet mit
   dem Schlüssel der Vektoren) und die Hülle gegen die Referenz, nicht der
   Weg durch `ingest.php`.
3. **Die Kopplung gegen den echten `pair.php`.** Die Prüffälle sprechen
   echtes HTTP, aber gegen einen Prüfserver mit festen Antworten; die
   Rundläufe gegen die örtliche Anlage (15 übersprungene Fälle) brauchen
   eine eingerichtete Installation und laufen hier nicht.
4. **Die Zweifel (1) bis (3) am Zähler** sind nur an der Referenz geprüft;
   die Entscheidung liegt bei der BetreiberIn (Q-KA-15, -16).

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Referenz `tools/proben/huelle/referenz.py` nach `erzeugen` | `pruefen`: Fassung 1 6 geöffnet, 8 Störungen; Fassung 2 **26 geöffnet, 39 Gegenlagen** (vorher 23/38), 0 Abweichungen; `--selbstprobe` **19 / 0**; `vektoren-2.json` 55 Zeilen dazu, 0 weg; die erzeugten `.mc`-Dateien byte-gleich | Browserprobe `probe.mjs` 415 Prüfungen, 0 Abweichungen (Fable-Agent) | vier Wechselvektoren (zwei Absender an einem Einsatz, Zweifel 5), Kopf „je Einsatz" (Zweifel 4) |
| Prüffälle des Kerns (`Geraetehuelle2Test` 11, `KopplungswertTest` 5, `GeraeteschluesselTest` 11, `HuellenzaehlerTest` 9) | 36 / 0 rot, von der Hauptinstanz nachgefahren | vier eingebaute Fehler: 15 von 25 rot (Fable-Agent); einmalig gegen die Python-Referenz geöffnet | Kern byte-gleich zur Referenz |
| `KopplungVergleichTest` (neu, 15 Fälle, echtes HTTP) | 15 / 0: Block `schluessel` beim Start, Wert = Rechnung aus Zusage, SPK, beiden Zufallszahlen, erstes Paar gilt, ohne Kontoschlüssel kein Offenlegen, Ablehnung räumt auf, 202, `huelle: 2` übernimmt das Paar, Trennen vergisst es | zwei eingebaute Fehler (SCHLUSS ohne „letztes Stück", Paar nie übernommen): 4 von 29 rot | Ablauf 1a.7 am Handy |
| `SendekoerperTest`, fünf neue Fälle | SCHLUSS nur am letzten Stück eines abgeschlossenen Pakets, `z` steigt je Anfrage, leeres Schlussstück `<seq>:0` mit `ts_von` = `ts_bis` = Ende, ohne Fassung 2 kein Schlussstück | siehe oben | Vertrag 3.4 am Handy |
| `./gradlew build` | BUILD SUCCESSFUL in 7m 23s; Lint 0 Fehler; Handy 368 Prüffälle je Bauart (15 übersprungen), Uhr 75, 0 rot; Release Handy 9 285 038 B | — | Bau |
| Bilderlauf `HandyBildTest` | 96 Bilder je Modus (32 Bildschirme), fünf neu; gegen M-KA-02 angesehen, hell und dunkel | — | Anzeige nach E-KA-66 (Abweichung D: F-KA-109) |
| Kopplungsprobe `tools/proben/kopplung/probe.php` | **105 / 0**, neu Fall 48 (F-KA-108) | gegen die Bibliothek von Web 24.18.0: 48 rot | F-KA-108 in der Bibliothek |

**Die Zählerregel (E-KA-64), Gegenprüfung durch den Fable-Agenten.** Die
Regel bleibt — sie ist die einfachste, die über einen Gerätewechsel steigt,
und das Format ändert sich nicht. (1) Eine vorauslaufende Uhr hält einen
hohen Stand, den nichts senkt; ein anderes Gerät fällt dann als
`zurueckgelegt` durch (Verfügbarkeit, kein Bruch der Zusage). (2) Eine
nachgehende Uhr ist vom Zurücklegen nicht sicher zu unterscheiden; Vektor
`wechsel-uhr-geht-nach`. (3) `z` belegt das Verschließen, nicht die
Frische; am Handy schärfer, weil jede Anfrage neu verschließt. (4) Kopf der
Referenz berichtigt, (5) Vektoren ergänzt. Empfehlungen und Alternativen:
Q-KA-15 bis -17.

**R91 nach PR #184 (Web 24.18.1, Staging, 14.11.2026).** Lauf 212 grün,
Stufe 2 mit Kreislauf `edbak` und Rückwegprobe; Staging meldet v24.18.1.
Mit eigenem Konto der Weg von P-KA-14 noch einmal (Chromium, nachgebautes
Gerät): „Wert stimmt" — Seite und Gerät 329010, Ja 200, `gekoppelt`, über
die Oberfläche entkoppelt; „Wert ist anders" — `abgelehnt`/`wert`, Ja 409,
kein Gerät; 0 Seitenfehler. Danach `staging_konten.py --wirklich`: ein
Konto der Kette gelöscht, vier behalten. Für das Handy selbst gibt es auf
Staging keinen Punkt der Arbeitsumgebung — P-KA-16 und -17 brauchen ein
Gerät. **Nachgetragen:** Der Prüfstand wählte die Kopplungsprobe für
KA-06c nicht aus (F-KA-114); sie war einzeln gefahren (105/0).

### KA-06d Kopplung mit Vergleichswert (13.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Kein echtes Gerät.** Weder die Garmin (KG-03b) noch das Handy (06c)
   koppeln schon mit Geräteschlüssel; gemessen ist ein nachgebautes Gerät
   nach der Referenz (Node, WebCrypto) gegen die echte Seite und den echten
   Endpunkt. Ob ein Gerät den Wert richtig zeigt, misst erst KG-03b bzw.
   06c gegen dieselbe Referenz.
2. **Fassung 2 von einem Gerät Ende zu Ende** (verschlossen am Gerät, am
   Eingang angenommen, im Browser mit Herkunft geöffnet) ist nicht
   gemessen: Am Eingang ist die FORM gemessen (Hüllen in genau dieser Form),
   das Öffnen mit Herkunft seit 06a/06b-1 an den Vektoren. Das Stück
   dazwischen gibt es erst mit einem Gerät, das Fassung 2 schreibt.
3. **Das leere Schlussstück in Wartungslauf, Export und Sicherung** ist nicht
   gefahren — es kommt erst von einem Gerät mit `huelle: 2`. Gemessen ist
   der Eingang (`spur2_stueck_schreiben()` nimmt 0 Punkte nur in Stufe 1).
4. **Die Karte am Handy und in einem echten Safari** — nur Chromium in der
   Bedienprobe; der volle Lauf fährt Firefox und WebKit (Prüfstand).

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Kopplungsprobe `tools/proben/kopplung/probe.php`, Teil 7 und 8 (neu, Fälle 35 bis 47) | 104 von 104 Erwartungen, davon 21 neu: Schlüssel in falscher Form → 400; `status` mit `n_b` und Kontoschlüssel; Ja vor dem Offenlegen 409; `offenlegen` 200/200/409 `n_g`/400; Ja ohne Wert 202 `wartet`; Wert aus fremdem Konto `zustand`; Abschluss mit `spk`, `herkunft` 1, `huelle` 2; abgelehnt → `status` `abgelehnt`, Ja 409; ohne Gerätebuch kein `huelle`; ältere App wie bisher; Eingang: eigene SID 200, fremde 400, ohne Herkunft 400, leeres Schlussstück Fassung 2 200 / Fassung 1 400 | gegen Web 24.17.0 wären alle 21 rot (`schluessel` überlesen, `offenlegen` unbekannt → 400 `aktion`, Fassung 2 ohne Absenderprüfung angenommen) | Vertrag 1a.7 am Endpunkt, Eingang 3.4 |
| Bedienprobe `ka06d.mjs`, vier Wege (Chromium, örtlich) | Wert: Seite und Gerät zeigen dieselbe Zahl, gekoppelt ohne `huelle`; Buch (Weiche nur im Probebrowser an): Gerät im Gerätebuch, `huelle: 2`, nach dem Entkoppeln `bis`; anders: `abgelehnt`/`wert`, Ja 409; Zusage: andere N_g → keine Zahl, Fehler, `abgelehnt`/`zusage` | der Weg „Buch" fand F-KA-101 und F-KA-102 | Webseite nach M-KA-01, Prüfung der Zusage im Browser, Gerätebuch, Abgleich |
| Bedienprobe in Firefox und WebKit, Abfolge `ka06b4-anhebelauf` → `mr-oeffner-markiert` | im ersten vollen Lauf rot (Öffner fehlt, die Seite stand auf der Anmeldung); mit Neuanmeldung in `ka06b4` in allen drei Engines grün | allein gefahren war `mr` grün — der Fehler lag in der Reihenfolge | F-KA-106 |
| Rundlauf der Kopplung `tools/proben/kopplung/rundlauf.mjs` | 25 von 25, 0 Konsolenfehler | — | der alte Weg (ältere App) unverändert, samt Nachladen und Bedienhöhe |
| Zentralisierungsregister | 51 Zeilen, 0 über der Decke; Z16 von 7 auf 6 (der Rahmen von `pair.php` steht jetzt in `db_transaktion()`) | ein erster Stand mit `echo json_encode` in `api/kopplung_wert.php` stand über Z41 | die eine Stelle je Sache |
| Aufräumskript `tools/sandbox/staging_konten.py` gegen Staging | ohne Variable: rot, nichts gelöscht; Trockenlauf: 4 bleiben; mit `--wirklich`: `umlauf-edbak@example.invalid` gelöscht | der erste Stand las den Platzhalter `neue@example.invalid` als Konto (F-KA-105) | E-KA-62, -65 |
| Prüfstand, Hauptstufe, voller Lauf, über den örtlichen Merge mit `main` (KG-08, Uhr 4.6.0; Konflikte nur in `CHANGELOG.md` und `Backlog-Erledigt.md`, beide Seiten behalten) | erster Lauf am 14.11.2026: 63 grün, **1 rot** (`rueckwegprobe` in Chromium, F-KA-107), 1 Hinweis (`tools/sandbox/LIESMICH.md` 41 statt 40 Zeilen, behoben), 9732 s; in WebKit eine Wiederholung nach E-DM-10 (`betrieb-server-frischer-code`, `waitForURL` 90 s); der zweite Lauf steht als Bericht im Commit | die rote Probe einzeln dreimal nachgefahren: 3 von 3 grün (je 50 + 23 Prüfungen); im selben Lauf Firefox und WebKit grün | der Bericht des Commits, über den gemischten Baum |

**R91 nach PR #177 (Web 24.17.0)** steht beim Paket 06b-4.

**R91 nach PR #179 (Web 24.18.0, Staging, 14.11.2026).** Lauf 207: Auslieferung
grün; **Stufe 2 rot** — der Kreislauf `edbak` bekam in allen drei Engines 503
auf `admin_users.php`, weil der Torwächter bis `update.php` geschlossen hält;
dasselbe Bild wie Lauf 202 (PR #175, ebenfalls mit Migration). Vor
`update.php`, mit eigenem Konto: P-P5c-42 bestanden (Updates, Komplett-Backup
und Backup-Ziele 200 mit Wartungsbalken, eine Seite unter Verwaltung 503;
„Jetzt sichern" ergab einen Stand mit 11 512 Zeilen aus 42 Tabellen);
P-P5c-31 zur Hälfte (503 `maintenance` während und nach der Kette). Nach
`update.php` der BetreiberIn: **P-KA-14 bestanden** — Seite und Gerät
531908, Ja 200, `status` `gekoppelt` ohne `huelle`, ein Gerät in der Liste,
über die Oberfläche entkoppelt (danach `status` 401); „Wert ist anders":
461505 auf beiden Seiten, `abgelehnt`/`wert`, Ja 409, kein Gerät; 0
Seitenfehler (Chromium, nachgebautes Gerät wie `ka06d.mjs`). Ein erster
Durchgang ließ ein Testgerät stehen (Fehler im Skript der Arbeitsumgebung,
nicht der Anwendung); es ist entfernt. Danach `staging_konten.py --wirklich`:
ein Konto gelöscht (`umlauf-edbak@example.invalid`), vier behalten.
Lauf 208 (Merge von PR #180, nach `update.php`): **Stufe 2 grün** —
Kreislauf `edbak` und Rückwegprobe in drei Engines gegen das neue Schema;
das Konto des Kreislaufs danach wieder gelöscht.

### KA-06b-4 Anhebelauf (13.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Der Messstand mit dem großen Bestand** ist nicht gefahren (Konzept 8:
   „vor der Auslieferung"). Gemessen ist das Demo-Konto mit 104 Blöcken. Der
   Lauf wird erst mit dem Umlegen der Weiche wirksam; die Messung am großen
   Bestand gehört deshalb vor Web 25.0.0, nicht vor diese Fassung.
2. **Ein Abbruch mitten im Lauf** (Seite verlassen) ist nicht nachgestellt;
   er lässt nach Bauart den Rest im alten Format, und der nächste Lauf macht
   weiter.

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Bedienprobe `ka06b4-anhebelauf` (Demo-Konto, Weiche nur im Browser der Probe umgelegt) | Weiche aus: Altbestand 104 → unverändert; Weiche an: Altbestand 0, Kontopaar `edk2:`, **16 von 16 Blättern zu**; zweiter Lauf ändert nichts; falsches `blob_alt`: 0 angehoben, Block unverändert | ohne Endpunkt und ohne den Lauf: Abbruch, rot | Anhebelauf, Rundlauf, Wache am Server, Schließen der Blätter |
| Demo-Konto nach dem Weg | 104 Blöcke `edk1:`, 0 Blätter (zurückgesetzt) | — | die Probe hinterlässt nichts |

**R91 nach PR #176 (Web 24.16.0, Staging, 13.11.2026):** Lauf 204 grün; Anmeldung im Browser mit eigenem Konto und Zweitfaktor; Kern geladen; Standbuch 16 Blätter, Buch 0 (die Weiche steht aus, nichts geschrieben); Einzelansicht des Einsatzes 20332 mit geschützten Angaben geöffnet, Sperrbanner versteckt, 0 Seitenfehler; Reiter System ohne Eintrag nach dem 09.11.2026.

**R91 nach PR #177 (Web 24.17.0, Staging, 13.11.2026):** Lauf 205 grün, Staging meldet 24.17.0; Anmeldung mit eigenem Konto und Zweitfaktor; Kern geladen; Standbuch 16 Blätter, Buch 0; Anmeldeschlüssel nicht liegen geblieben, leeres Salz abgewiesen; Einsatz 20332 geöffnet, Sperrbanner versteckt, 0 Seitenfehler; Reiter System ohne Eintrag nach dem 09.11.2026. **Nicht gemessen:** der Anhebelauf selbst — die Weiche steht aus, niemand ruft ihn.

### KA-06b-3 Verbraucher (13.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Ende zu Ende gemessen ist nur das Einsatzformular** (Patientenblock und
   Einsatzhülle, beide Stellungen der Weiche). Import, Wiederherstellung,
   Übernahme, Wartungslauf, Notizen-Anhebung, Kontopaar, Rückweg, Schneiden
   und Löschen sind mit umgelegter Weiche **nicht** Ende zu Ende gefahren —
   umgebaut, mit ausgeschalteter Weiche im Prüfstand (alle bestehenden Wege
   grün), mit umgelegter nur über die Hüllenprobe des Kerns.
2. **Spurstücke der Fassung 2 aus einem Gerät** gibt es noch nicht (06c,
   06d); der Leser ist nur gegen die Vektoren gemessen.
3. **Zwei Tabs gleichzeitig** sind wie in 06b-2 nur nacheinander gemessen.

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Bedienprobe `ka06b3-patient-weiche-an` (Weiche nur im Browser der Probe umgelegt) | gespeichert `edk2:`, Blätter 1 → 2, Feld `blaetter` mitgeschickt, Block geht auf; dieselbe Anfrage noch einmal → **409**, Block unverändert | Stand vor dem Umbau (`5261e4e`): `edk1:`, kein Blatt, kein Feld, Wiederholung 302 — rot | Formular, Server-Transaktion, Versionsregel, Einsatzhülle Fassung 2 |
| Bedienprobe `ka06b3-patient-weiche-aus` | gespeichert `edk1:`, kein Feld `blaetter` | derselbe Stand: ebenso grün | mit ausgeschalteter Weiche unverändert |
| Hüllenprobe des Servers | **22 Prüfungen, 0 Abweichungen**, neu Ableitung 1 und 2 in PHP (4) | ohne `kdf_haelfte()` wären die zwei Fälle mit 1 200 000 Runden rot | Ableitung 2 byte-gleich zur Referenz |

**R91 nach PR #175 (Web 24.15.0, Staging, 12.11.2026, nach update.php der BetreiberIn):** Anmeldung im Browser mit eigenem Konto und Zweitfaktor; Kern geladen; api/standbuch.php stand: 16 Blätter, Buch 0; edkvor leer; 0 Seitenfehler; Reiter System ohne Eintrag nach dem 09.11.2026.

### KA-06b-2 Server für das Standbuch (11.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Die Migration auf Staging und Produktiv** — `update.php` ruft die
   BetreiberIn (P-KA-12); örtlich ist sie angewendet und steht.
2. **Datensatz und Blatt in einer Anfrage** gibt es noch nicht; es kommt mit
   den Verbrauchern (06b-3). Bis dahin schreibt kein Browser in die Tabelle.
3. **Zwei Tabs gleichzeitig** sind nicht nebenläufig gemessen, sondern
   nacheinander: der zweite mit der veralteten Version bekommt den
   Konflikt. Dass die Anweisung atomar ist, folgt aus ihr, nicht aus einer
   Messung.

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Bedienprobe, Weg `ka06b2-standbuch` (Prüfkonto, über `EdStandbuch.apiTransport()`) | 16 Blätter und Buch in Form; Blatt abgelegt und Zeichen für Zeichen zurück; veraltete Version → Konflikt mit dem Blatt des Servers, ebenso beim Buch; 400 `text`, `version`, `wer` | ohne Endpunkt (Stand von `main`): Abbruch, rot; Versionsregel mit `version >= ?` statt `= ?`: kein Konflikt, rot | Schnittstelle zwischen Browser und Server, Versionsregel, Form |
| Bedienprobe, Weg `ka06b2-fremdkonto` | das Demo-Konto sieht das Blatt des Prüfkontos nicht | ohne Endpunkt: rot | Bindung an das Konto der Sitzung |
| Migration örtlich | `2026_11_11_standbuch` „Erfolgreich angewendet", Tabelle wie in `schema.sql` | — | Migration und Schema gleich |
| Schemaprobe, Modul `plattform` | MariaDB 10.6, MySQL 8.0, MySQL 8.4.0 je **52 Prüfungen, 0 Fehlschläge** | — | die Migration läuft auf allen Zielfassungen |
| Migrationsregister | 76 Kennungen im Katalog, 76 in der Vorabliste | Zwischenstand: 75 (rot, F-KA-96) | eine neue Installation trägt die Migration als erledigt ein |

**Prüfstand (Stufe neben, drei Engines):** Zwischenstand 61 grün, 3 rot —
`migrationsregister` (F-KA-96), `schemaprobe` (Plattform-Container nach
einem Neustart nicht gestartet, F-KA-95), `rueckwegprobe` in WebKit
(Zeitgrenze beim Seitenwechsel, einzeln danach 23/0, F-KA-94). Zweiter
Zwischenstand nach Vorabliste und Plattform: 63 grün, 1 rot — wieder die
Rückwegprobe in WebKit, an anderer Stelle. Der Kopf misst sie neu und erbt
den Rest.

**R91 nach PR #174 (Web 24.14.0, Staging, 12.11.2026 02:10 UTC):** Lauf grün; Fassung 24.14.0; inhalt2.js, huelle2.js, standbuch.js je 200; Anmeldung im Browser mit eigenem Konto und Zweitfaktor; EdInhalt, EdHuelle2, EdStandbuch definiert; edkvor leer; 0 Seitenfehler; Reiter System ohne Eintrag nach dem 09.11.2026.

### KA-06b-1 Kern einbinden (11.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Kein Verbraucher benutzt den Kern**, und die Weiche steht aus: Was
   gemessen ist, ist die Bibliothek gegen die Referenz, nicht ein Weg der
   Anwendung. Die stille Umstellung auf Ableitung 2 (`unlock.js`,
   `formatHeben`) ist nur auf Syntax geprüft; sie braucht 06b-3.
2. **Der Transport der Blätter** ist nur gegen einen Server im Speicher
   gemessen (`speicherTransport()`); `api/standbuch.php` gibt es erst mit 06b-2.
3. **Zeiten:** Dauer der Ableitung 2 bei 1 200 000 Runden und Größe eines
   Blatts bei 5 000 Einsätzen sind nicht gemessen.
4. **Die Stärke des Entwurfs** ist weiter nicht gegengelesen.

**Gemessen:**

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Hüllenprobe, drei Engines (Agent; hier Chromium nachgefahren) | je **407 Prüfungen, 0 Abweichungen** (39 Fassung 1, 173 Weiche aus, 195 Weiche an) | mit `crypto.js` von `main`: 368 von 407 rot; mit altem `crypto.js` und den drei neuen Dateien: 355 rot | Kern byte-gleich zur Referenz, jede Gegenlage mit ihrem Grund |
| Zwölf Mutanten (Agent; je eine Prüfung im Kern entfernt) | alle rot, 2 bis 96 Abweichungen | — | die Probe sieht, was sie prüfen soll |
| Referenz-Selbstprobe | `referenz_inhalt.py` **21 Fälle, 0 Fehlschläge** (drei neu), `referenz.py` 17/0 | die drei neuen Fälle mit der Referenz von `main`: **3 rot** | F-KA-90 |
| Vektoren nach `erzeugen` | unverändert (`git status`) | — | die Korrekturen ändern kein Format |

**Prüfstand (Stufe neben, drei Engines):** 61 grün, 1 rot (`dokumente`:
`Technik.md` nannte den Pfad des Endpunkts aus 06b-2, berichtigt), 1
Hinweis (`erzeugen`: Zeilennummer von `ui_krypto_bootstrap()` in
`Design.md` neu erzeugt, im Commit). Der Kopf erbt die übrigen Proben.

**R91 nach PR #171 (Web 24.13.1, Staging, 11.11.2026):** Lauf 198 grün;
Anmeldung im Browser mit eigenem BetreiberIn-Konto und Zweitfaktor gelingt,
`edkvor` auf der ersten Seite leer, ein leeres Salz abgewiesen, 0
Seitenfehler, im Reiter System kein Eintrag nach dem 09.11.2026.

### KA-06a Entwurf — Ergebnis (11.11.2026, Fable-Agent, E-KA-38)

**Stand:** Referenz und Vektoren stehen unter `tools/proben/huelle/`
(`referenz.py` beide Fassungen der Gerätehülle, `referenz_inhalt.py` neu für
`edk2:`, Standbuch, Gerätebuch, Ableitung 2; `vektoren-2.json`,
`vektoren-inhalt.json`). Fassung 1 ist byte-gleich geblieben. **Die
Entwurfsentscheidungen unten sind mit KA-M5 freigegeben** (11.11.2026,
E-KA-48); sie behalten ihre Nummern V-KA-01 bis -13, damit Referenz und
Konzept sich aufeinander beziehen können.

**Format in kurz.**
- Gerätehülle Fassung 2: `0x02 ‖ SID(8) ‖ EPK(65) ‖ IV(16) ‖ CT ‖ TAG(32)`,
  AES-256-CBC + HMAC-SHA256; Schlüssel aus `HKDF(Z_e ‖ Z_s, salt = EPK,
  info = "nadoku-huelle-2" ‖ Konto-pub ‖ SPK)` — Z_e flüchtig, Z_s statisch
  (Geräteschlüssel) gegen den Kontoschlüssel. SID = erste 8 Byte von
  `SHA-256("nadoku-absender-1" ‖ SPK)`. Kleinste Hülle 138 Byte.
- Spurstück innen mit 12 Byte Kopf (absolutes `ts_von`, Merker
  SCHLUSS/LUECKE/UEBERNOMMEN); Einsatzhülle innen JSON mit Zähler `z` und
  absolutem `start`.
- `edk2:`: AES-256-GCM, Zusatzdaten `"edk2" ‖ Zweck ‖ Bezug`, innen Zähler,
  Länge, Nutzlast, aufgefüllt auf Größenklassen (128 … 4096, dann Vielfache;
  `pat` mindestens 512).
- Kopplung: Zusage des Geräts vor dem Offenlegen, Vergleichswert sechs
  Ziffern.
- Ableitung 2: PBKDF2 ein Block (32 Byte), Hälfte und Token per HKDF.

**Entwurfsentscheidungen (V-KA-01 bis V-KA-13, freigegeben mit E-KA-48):**

| Nr. | Vorschlag | Grund |
|---|---|---|
| V-KA-01 | Herkunft über zwei ECDH (flüchtig und statisch) statt Signatur | Die Garmin kann nicht signieren (F-KG-11); dieselben Bausteine wie heute; Vorwärtsgeheimnis bleibt |
| V-KA-02 | Z_s darf auf dem Gerät liegen | fest je Gerät und Konto; eine Hülle kostet dann so viel wie heute |
| V-KA-03 | Der Browser verschließt als Absender „Konto" | Formular, Wartung, Schnitt und Import brauchen keinen eigenen Schlüssel |
| V-KA-04 | **Abweichung von der Skizze:** Die Stückfolge wird über die Deckung verkettet (lückenlos ab 0, SCHLUSS am Ende, leeres Schlussstück), nicht über einen Hash | Die Uhr verschließt je Sendeversuch neu und schneidet anders zu (F-KA-80) |
| V-KA-05 | Absolute Zeit in der Hülle; Umdatierung als belegter Versatz im Standbuch | sonst ginge das Umdatieren eines Diensttags nicht mehr (F-KA-78) |
| V-KA-06 | Löschvermerke belegt im Standbuch | sonst ist „Stück entfernt" nicht von einem Schnitt zu unterscheiden (F-KA-79) |
| V-KA-07 | **Abweichung von der Skizze:** keine Kontokennung in den Zusatzdaten | Jedes Konto hat seinen Inhaltsschlüssel; die Nummer vergibt der Server und bände an die Installation |
| V-KA-08 | Der Rückweg bleibt frei von Server und Anteil (`kp`, `rw`, `wrap`/`rc`) | Zusage aus `CLAUDE.md` 4 |
| V-KA-09 | Der Zähler steht im Chiffretext; ein Zähler über dem Stand ist kein Fehler | ein verlorener Schreibvorgang sperrt nichts aus |
| V-KA-10 | Ein Abstieg auf Fassung 1 bzw. `edk1` ist ein Befund (`zurueckgelegt`, nach dem Anhebelauf `altformat`) | sonst wäre das Zurücklegen über das Altformat offen |
| V-KA-11 | Vergleichswert sechs Ziffern **mit Zusage** | ohne Zusage rechnet ein Angreifer ein passendes Paar in Millisekunden aus |
| V-KA-12 | Ableitung 2 wechselt zusammen mit der Rundenzahl (1 200 000) | bei gleicher Rundenzahl wäre die neue Wurzel die alte Hälfte |
| V-KA-13 | Ein Gerät darf sich nicht als Konto ausgeben (Herkunftsliste, LUECKE, UEBERNOMMEN nur vom Konto) | sonst überschriebe ein Gerät die Abnahme |

**Fragen an KA-M5** stehen in Abschnitt 7 als Q-KA-07 bis Q-KA-11, beantwortet mit E-KA-43 bis -47.

**Gemessen** (Agent; danach der Prüfstand dieses Pakets im Commit):

| Mittel | Ergebnis |
|---|---|
| `referenz.py --selbstprobe` | 17 Fälle, 0 Fehlschläge (vorher 5) |
| `referenz.py pruefen` | Fassung 1: 6 geöffnet, 8 Störungen, 0 Abweichungen · Fassung 2: 23 geöffnet, 38 Gegenlagen, 0 Abweichungen |
| `referenz_inhalt.py --selbstprobe` / `pruefen` | 18/0 · 23 geöffnet, 28 Gegenlagen, 0 Abweichungen |
| Hüllenprobe Chromium | Server 18, Browser 39 Prüfungen, 0 Abweichungen (Fassung 1) |
| Gegenrechnung WebCrypto in Node (Wegwerfskript) | 72 Prüfungen, 0 Abweichungen |
| `sha256sum` vor/nach `erzeugen` | `vektoren.json`, `HuelleVektor.mc`, `Vektoren.mc` unverändert |

Die fünf verlangten Angriffe scheitern je mit Grund: vertauschen →
`pruefwert`; zurücklegen → `zurueckgelegt`; Zeit verschieben → `zeit`;
Stück entfernen → `luecke` / `ohne-schluss`; Hülle ohne Geräteschlüssel →
`absender`, `pruefwert`, `ohne-herkunft`.

**Nicht gemessen:** keine Umsetzung außer der Referenz (Browser, PHP,
Android, Garmin prüfen weiter Fassung 1); Kosten auf Uhr und Handy
gerechnet, nicht gemessen; Größenklassen nicht gegen echte Längen
gehalten; **die Sicherheit des Entwurfs ist nicht gegengelesen** — die
Vektoren belegen das Format, nicht seine Stärke.

**Grenzen:** Die Herkunft beweist nur dem Browser des Kontos etwas, nicht
Dritten und nicht dem Server; wer den privaten Kontoschlüssel hat (auch ein
Skript in der Seite, Lage D), baut jede Gerätehülle selbst; eine gefundene
Uhr fälscht, bis sie ausgetragen ist. Der Server kann weiter löschen,
vorenthalten und einem neuen Browser einen alten, stimmigen Stand zeigen;
Klartextfelder (`final`, Strecke, Besatzung, Tagesnotizen, Phasenzeiten bis
06b) bleiben fälschbar. Hüllen der Fassung 1 bekommen nie eine Herkunft.

### KA-06b vorab — Ableitung (11.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Einstellungen → Profil** lädt `unlock.js` nicht; landet die erste
   Seite nach der Anmeldung dort, räumt erst die Frist (sechs Minuten). Das
   ist eine benannte Grenze, kein Messfehler (F-KA-77).
2. **Die Anmeldung gegen Staging** mit der neuen Untergrenze läuft erst nach
   dem Merge (R91); örtlich melden sich Bedien-, Rollen- und Rückwegprobe an.

**Gemessen** (Gegenprobe = `crypto.js`, `unlock.js`, `login.php` von `main`):

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Bedienprobe, Weg `ka06-ableitung` | 1 000 Runden und leeres Salz abgewiesen, 310 000 angenommen; Antwort des Salz-Endpunkts mit 1 000 Runden abgewiesen mit `status` „form" | alles angenommen (rot) | F-KA-13 |
| Bedienprobe, Weg `ka06-vormerkfach` (Prüfkonto) | Fach von vor sieben Minuten und Fach ohne Zeitstempel verworfen und geräumt, frisches geliefert; nach der Startseite (RW und KP „da") leer | altes und zeitloses geliefert, nach der Startseite **belegt** (rot) | F-KA-16 |


### KA-04 (10.11.2026, ohne die Garmin-Punkte 1 und 4)

**Was nicht geprüft werden konnte — zuerst.**

1. **Kein echtes Gerät, kein Emulator.** Die Wear-Uhr mit „Handy nicht
   gekoppelt", die Zustellung von `gekoppelt` über den Data Layer und
   „Dienst beenden" auf dem Sperrbildschirm (`setAuthenticationRequired`
   wirkt erst ab API 31, und Robolectric zeigt nur, dass die Aktion gebaut
   wird) sind nur im Quelltext und in Robolectric belegt. Prüfliste
   P-KA-08, -09.
2. **Die Übergabe einer echten Uhr im laufenden Dienst** gehört zu KG (Garmin).
3. **Der Server-Rundlauf der Android-Fälle** (`-Pnadoku.rundlauf`) lief
   nicht; die 15 übersprungenen Fälle sind genau diese.

**Gemessen** (Gegenprobe = dieselben Fälle mit zurückgenommener Behebung):

| Mittel | Ergebnis | Gegenprobe | Was es belegt |
|---|---|---|---|
| Ingestprobe | **99 Erwartungen, 0 nicht erfüllt** — neu Teil 1a: 0:120, dann 0:300 → nichts abgelegt, `next_seq` 120; 120:180 → alle 300 Punkte liegen | **1 nicht erfüllt** (`next_seq` 300) | F-KA-07 |
| Kopplungsprobe | **87 Erwartungen, 0 nicht erfüllt** — neu: deaktiviertes Gerät und gesperrtes Konto bekommen auf `status` und `trennen` 403, das Gerät bleibt | **4 nicht erfüllt** — die zwei neuen und zwei Folgezeilen (das deaktivierte Gerät trennte sich selbst und fehlte danach) | F-KA-27, E-KA-30 |
| Android-Prüffälle (Robolectric), Debug | **387 Fälle, 0 Fehler, 15 übersprungen** (Rundlauf ohne Server); je Debug und Release gelaufen: 11 neue, 1 umgeschriebener | **9 von 95 rot** in Sender-, Sendeantwort-, Kopplungs- und Uhrannahmetest — genau die neuen und der umgeschriebene Fristfall | F-KA-06, -18, -32; E-KA-27 bis -29 |

**Die Befunde an der heutigen Stelle:** F-KA-06 (alle drei Wege), -07, -18,
-27, -31, -32, -33 halten. Zu Weg 2 von F-KA-06 siehe F-KA-66.

---

## 10. Befunde der Umsetzung

| Nr. | Befund | Wie gelöst |
|---|---|---|
| F-KA-44 | **Kein Job der vier Arbeitsläufe hatte ein Zeitlimit außer fünf in `auslieferung.yml`.** Am 10.11.2026 hing Lauf 499 im ersten Versuch 45 Minuten im Schritt „PHP-Syntax über server/ und tools/", endete ohne abrufbares Protokoll als fehlgeschlagen und war im zweiten Versuch nach zwei Minuten grün — am Commit hatte sich nichts geändert (gemeldet von der BetreiberIn). Ohne Limit gilt die Vorgabe von GitHub, sechs Stunden. Dieselbe Lücke hatten `ausliefern` (`ausliefern-lauf.yml`), `Rückfallstand`, `zeiger` und `wache` | `timeout-minutes` an jedem Job: `Schon gemessen?` 5, `Stufe 1` 15, `Schema` 10 (gemessen höchstens 3,0 min je ganzer Lauf, rund 40 s je Datenbank); `wache` 15 (höchstens 1,8 min); `zeiger` 10 (rund 10 s); `ausliefern` 150 und `Rückfallstand` 120 — **über** der Grenze des Backup-Tors (2 Runden × 40 Aufrufe × bis 60 s Antwort + 20 s Pause ≈ 107 min), damit das Tor seinen Abbruch selbst meldet; darüber Luft für einen vollen Abgleich. `staging` und `produktion` rufen einen wiederverwendbaren Lauf auf und nehmen kein `timeout-minutes`; das Limit steht an dessen Job. Der Schlussschritt läuft auch bei Abbruch (E-KA-12) |
| F-KA-45 | **`tools/kettenaufrufe/LIESMICH.md` und `tools/kette/LIESMICH.md` gingen beim Nachtragen über 40 Zeilen** (`bestand` misst die Decke) | beide neu gefasst, je 40 Zeilen; die Zahlengeschichte steht hier, nicht dort |
| F-KA-46 | **`docs/Pruefablauf.md` 6.8 nannte die Ausnahmeliste „von acht Pfaden"** — seit Web 23.3.1 sind es sieben (`CLAUDE.md` 3) | an Ort und Stelle berichtigt |
| F-KA-47 | **Ein Satz in `android/LIESMICH.md`, dass `fremdsummen` die Wrapper-JAR jetzt misst, hätte den Android-Bau ausgelöst** (Muster `android/**`), und das Android-SDK fehlt in dieser Arbeitsumgebung (`/opt/android-sdk` nicht vorhanden) — der Prüfstand wäre für eine Zeile Text rot | Satz zurückgenommen; **geht mit KA-04 hinein**, das `android/` ohnehin anfasst. Bis dahin sagen es `Lizenzen.md` 3a, `Technik.md` 12.14 und der Kopf von `fremdsummen.py` (`WRAPPER_SOLL`) |
| F-KA-48 | **Der Container wurde neu gestartet, während die Sitzung auf die Antwort der BetreiberIn wartete** (`uptime` 44 min um 12:21; die örtliche Anlage antwortete nicht mehr) | `hochfahren.sh` wieder gefahren, die Anlage stand ohne `--neu`; nichts verloren, weil nichts lief. Bestätigt `docs/Sandbox-Setup.md` 6.1a |
| F-KA-49 | **Der erste Schlusssatz zu Nr. 540 sagte „abgenommen im Bilderlauf"** — der Bilderlauf nimmt den dunklen Modus aber nicht auf (Nr. 521) | nachgemessen mit einem Vergleich hell gegen dunkel (Abschnitt 9, KA-02), Satz berichtigt, bevor er committet war |
| F-KA-50 | **Der Schritt in `pruefung.yml` hieß „Quelltext — vierzehn Prüfungen"**, es waren schon sechzehn, mit `druckblatt` siebzehn; ebenso der Kopf von `tools/quelltext/pruefen.sh` („fünfzehn") | beide berichtigt; niemand liest den Schrittnamen (gesucht) |
| F-KA-51 | **Der Bilderlauf baute das gefüllte Notfall- und Codeblatt über den alten POST** (`tools/screenshots/aufnehmen.mjs`, Schritte `notfallblatt` und `codeblatt`) — im ersten Prüfstand von KA-02 rot mit 20 Konsolenfehlern („Stylesheet nicht angewendet": die 400-Antwort ist Text) | `blattUeberFenster()`: ein Helferfenster derselben Sitzung öffnet das Blatt und beantwortet dessen Meldung wie der Druckknopf; das gefüllte `<main>` geht in die aufgenommene Seite. Einzeln nachgefahren: 9 Bilder, 0 Konsolenfehler |
| F-KA-52 | **Der Stilvergleich war rot mit 19 ungeplanten Signaturen je Engine** — alle aus `.rf-druck` (Umbruch, neue Zeile `.rf-druck-hinweis`) und der um 4 px längeren Katalogseite; keine weitere | geplante Listen neu geschrieben (`gegen.sh --schreiben`), je 19 Signaturen; die drei geerbten Zeilen des vorigen Schritts fallen weg |
| F-KA-53 | **Bei 360 px stoßen die fünf Schlüsselgruppen des Notfallblatts aneinander** (gesehen im Bilderlauf, Baustein unverändert seit Web 21.1.0) | **in KA-02 behoben** (Wunsch der BetreiberIn, statt eines Backlog-Punkts; die zuerst angelegte Nr. 561 ist nie gepusht worden und entfällt): unter 480 px am Bildschirm drei Gruppen je Zeile mit Spaltenabstand, auf Papier weiter fünf; Bilderlauf 02f bei 360 px ohne Überlauf, Gruppen getrennt |
| F-KA-54 | **Die Wartungslaufprobe war seit OF-07 (Web 24.8.0) rot, ohne dass es auffiel** — Teil C rief die Tagesansicht ohne `#ruheliste` auf, und seit OF-07 steht die Karte „Ruhesegmente" nur auf Abruf da; der Knopf blieb verborgen, der Klick lief in die Zeitgrenze. Grün zuletzt am 07.11.2026 (MA-18); seither lief sie in keinem Bericht, weil kein Paket ihr Muster berührte. Im Prüfstand von KA-02 rot in drei Engines, mit `server/` von `main` ebenso | Probe ruft `index.php?d=…#ruheliste` auf und klickt nur, wenn der Bereich nicht schon offen ist; danach 24 ok, 0 fehlen. Zwischenstand `39a9420` trägt den roten Bericht, der nächste Commit den grünen (Erbe nach `Pruefablauf.md` 5.4) |
| F-KA-55 | **Die Notfallblattprobe baute ihren Browserkontext von Hand** (`ignoreHTTPSErrors` nur örtlich) statt über `kontextMachen()` aus `tools/motor.mjs`; gegen Staging scheiterte Firefox an der Zertifizierungsstelle des Proxys der Arbeitsumgebung (`SEC_ERROR_UNKNOWN_ISSUER`, `Sandbox-Setup.md` 5.2). Örtlich und in Chromium/WebKit fiel es nicht auf | `kontextMachen(browser, { basis })`; danach Firefox gegen Staging 16 von 16, örtlich unverändert 16 von 16. Geht mit dem nächsten PR |
| F-KA-56 | **Der Container wurde ein zweites Mal neu gestartet**, diesmal in der Wartezeit zwischen PR und Merge (`uptime` 20 min um 18:35); die örtliche Anlage stand nicht mehr | `hochfahren.sh`, ohne `--neu`; wie F-KA-48 |
| F-KA-57 | **Der erste Bau von Punkt 5 erkannte nie eine Änderung:** `instanz_kontakt()` und `betrieb_mail()` merken sich ihren Wert je Anfrage, der Vergleich „alt gegen neu" nach dem Speichern verglich den alten mit sich selbst — kein Protokoll, keine Mail (gefunden von der neuen Zeile der Rollenprobe) | neu gelesen aus `app_state`; dazu die Art `installation_adressen` im Katalog `PROTOKOLL_ARTEN` und in der Reiterliste |
| F-KA-58 | **Die Zweitfaktorprobe nahm `admin_users.php` als Beispiel einer fremden Rücksprungseite** — seit Punkt 1 ist sie eine Seite der Liste (`konto_mit_rechten`), der Fall wurde rot, ohne dass etwas falsch war | Beispiel `admin_installation.php` (keine Seite der Liste) |
| F-KA-59 | **Die Versandprobe sendete in Teil 12 und 13 an SFTP-Ziele ohne Abdruck** — genau das, was Punkt 3 verbietet; im ersten Lauf rot mit Abbruch | Teil 12 misst zuerst die Abweisung (Lauf 0), dann übernehmen beide Teile den Abdruck mit `sz_fingerabdruck_merken()` |
| F-KA-60 | **Örtlich steht die CSP im Berichtsmodus** (`Content-Security-Policy-Report-Only`), die erste Fassung der CSP-Zeile fand keine Kopfzeile | die Zeile liest beide Namen |
| F-KA-64 | **Der Container wurde ein drittes Mal neu gestartet** (`uptime` 1 min um 22:33), während der Aufbau der Android-Stufe und der erste Gradle-Lauf liefen; die Anlage stand nicht, der Gradle-Lauf war fort. Das SDK unter `/opt/android-sdk` überlebte | `hochfahren.sh` ohne `--neu`, Gradle neu gestartet. Dazu: `ANDROID_HOME` war in der Sitzung nicht gesetzt — der erste Lauf scheiterte an „SDK location not found"; mit `ANDROID_HOME=/opt/android-sdk` grün |
| F-KA-65 | **Das Handy schneidet seine Spurstücke wie die Uhr wachsend** (`Sender.sendeTeilstueck()`: ab der bestätigten Marke so viele Punkte, wie gerade da sind) — KA-04 Punkt 3 fragte danach. Der Punktverlust aus F-KA-07 traf also beide | durch die Behebung im Server abgedeckt; das Handy deckelt `next_seq` zusätzlich (E-KA-29) |
| F-KA-66 | **Das Audit sagt zu Weg 2 von F-KA-06, die App zeige bei unlesbarem Tresor „die Kopplung"** — sie zeigt den Kopplungsbildschirm (`lesen()` gibt `null`, `gekoppelt()` ist falsch). Der Befund hält trotzdem: Die nächste Kopplung nahm den Bestand mit | `vorgefundenesBinden()` beim Ja deckt diesen Weg und jeden anderen; der Satz im Audit bleibt, wie er ist (E-KA-03) |
| F-KA-67 | **`Sendeantwort` wertete 400 `klartext` als „fehlerhaft"** — der Vertrag sagt seit Web 22.0.1 „parken, nicht verwerfen". Gefunden beim Umbau für F-KA-18 | `klartext` ist „später erneut" mit einer Stunde Wartezeit |
| F-KA-68 | **Eine Bindung über ein offenes Paket zählte der Sender als leer** (`rueckstandBis()` zählt nur abgeschlossene) und hätte sie gelöst — das gebundene Paket ginge doch hinaus. Gefunden beim Bau von `vorgefundenesBinden()`, bevor es auftreten konnte | Sender zählt mit `Puffer.bestandBis()`; Prüffall `eineBindungDecktAuchEinOffenesPaket`. **Bleibt:** Die Frist räumt ein gebundenes offenes Paket erst, wenn sein Dienst endet |
| F-KA-69 | **Die Uhrannahme-Prüffälle liefen gegen die echte Uhr** (`jetzt` nicht gesetzt) — mit der 12-Stunden-Grenze wären alle Ereignisse vom Juli „zu alt" gewesen; ebenso der Rundlauf in `MissionRundlaufTest` | feste Empfangszeit im Test (17:00 desselben Tages), im Rundlauf die Zeit des Falls |
| F-KA-70 | **`Options -Indexes` in der `.htaccess` legt eine Anlage lahm**, wenn der Hoster `AllowOverride Options` nicht erlaubt: gemessen mit Apache 2.4.58, `AllowOverride FileInfo AuthConfig Limit` — 500 auf jeder Seite, `login.php` eingeschlossen. Ob Produktiv und Staging es erlauben, ist nicht gemessen | Zeile nicht aufgenommen, Begründung in der `.htaccess`; das Listing schaltet die BetreiberIn beim Hoster ab (P-KA-10) |
| F-KA-71 | **Der Arbeitsprozess der Sitzung startete neu, die Datenbank stand nicht mehr** (11.11.2026, `uptime` 11 min); die Anlage lief ohne `--neu` wieder an | wie F-KA-48 |
| F-KA-72 | **Die erste Gegenprobe des Wächters am eingebauten PHP-Server zeigte auch mit dem alten `blatt_dialog.php` 404** — der Server hatte den Stand noch zwischengespeichert. Ein zweiter Abruf mit Kopfzeilen zeigte 500 und den Fehlerbildschirm | Gegenprobe mit `-D -` wiederholt; erst dann gezählt |
| F-KA-73 | **Zwei Zeilen der neuen Proben waren falsch, nicht der Code:** `?? 'x'` machte aus dem erwarteten `NULL` ein `'x'`, und das Label „nichts angelegt" stand ohne Zählung da | `array_key_exists()`; die Zahl der Einsätze wird gezählt |
| F-KA-74 | **Die neue SheetJS-Datei liegt unter `vendor/` und löst heute kein Muster des Prüfstands aus** — weder `oberflaeche` (`server/assets/*.js`) noch `kreislauf-csv`. Eine künftige Fassung liefe ungeprüft durch | Kreislauf CSV von Hand in drei Engines; das Muster nachzuziehen hieße `pruefablauf.json` zu ändern — offen, Bericht an die BetreiberIn |
| F-KA-75 | **Der Messstand lief in die neue Mengengrenze:** Er spielt 16 wachsende Bestände in ein Konto, und ab dem 16. (318 Einsätze, gesamt 5 088) antwortete `api/backup_eintraege_restore.php` mit `507 kontingent` — im ersten Prüfstand von KA-05 rot. Die Grenze tat, was sie soll; das Messkonto misst ausdrücklich den ungünstigsten Fall | `messen.py` hebt die Grenze des Messkontos nach dem Anlegen auf (1 000 000 Einsätze / MB, `grenze_heben()`); danach alle 16 Dateien eingespielt, gesamt 5 088 |
| F-KA-76 | **Uhr-Stufe und Modul `plattform` fehlten im Container** — `uhr-krypto` (der Wächter steht auch in `geraetehuelle_lib.php`) war rot, `schemaprobe` (der Anker in `migration_lib.php`) nicht gemessen. Für `plattform` lief der Docker-Dienst nicht | `aufbauen.sh uhr` (Connect IQ 9.2.0, 173 Geräte) und, nach `dockerd`, `aufbauen.sh plattform` (MySQL 8.0, 8.4.0, MariaDB 10.6, PHP 8.3); der Zwischenstand `539de50` trägt den roten Bericht, der nächste Commit misst die drei neu |
| F-KA-77 | **F-KA-16 hält, aber enger, als das Audit es beschreibt:** Gemessen gegen `main` räumten die Startseite des Demo-Kontos, Suche, Zeitraum und Import das Fach schon immer — sie fragen beim Laden nach dem Inhaltsschlüssel. Stehen blieb es auf der Startseite eines Kontos ohne Bedarf (Prüfkonto) und auf Einstellungen (ohne `unlock.js`) | Auflösen in `nachDerAnmeldung()` immer; Frist für die Seiten ohne `unlock.js`; die Probe misst am Prüfkonto, weil die Demo-Startseite auf `main` grün gewesen wäre |
| F-KA-78 | **Absolute Zeiten stoßen auf die Umdatierung** (Entwurf 06a): `tageszuordnung_lib.php:270`, `:290` verschieben `ts_von` über `spur_zeit_verschieben()` (`spur_lib.php:590`); das Konzept nannte die Umdatierung nicht | V-KA-05: Versatz belegt im Standbuch |
| F-KA-79 | **Löschvermerke liegen im Klartext und fehlten in F-KA-12:** Wer `track_cuts` beschreibt, blendet Punkte aus, ohne eine Hülle zu berühren (`assets/ortsdaten.js:74`); der Server verwirft Stücke in einem Schnitt (`ingest.php:1293`) — rechtmäßige Lücken gibt es | V-KA-06: Vermerke belegt im Standbuch |
| F-KA-80 | **Die Garmin-Uhr verschließt je Sendeversuch neu** (`watch/source/Uploader.mc:433-453`); eine Kette über den Prüfwert des Vorgängers risse im Regelbetrieb | V-KA-04: Verkettung über die Deckung |
| F-KA-81 | **Phasenzeiten und -nummern sind Klartext, und der Browser fällt still auf die Reihenfolge zurück** (`assets/ortsdaten.js:179`) | 06b meldet eine Abweichung gegen die Hülle als `zeit` |
| F-KA-82 | **Der Server verschließt selbst:** Demo-Bestand (`demo_lib.php:699-706`, `edk1`) und `gh_einsatz_verschliessen()`, `gh_stuecke_bauen()` (`geraetehuelle_lib.php:147`, `:177`) — `edk2` bekommt eine zweite Umsetzung in PHP | E-KA-44 (Demo-Gerät); 06d |
| F-KA-83 | **Die Formprüfung ist auf Fassung 1 verdrahtet:** `validate_lib.php:805-809`, `GH_MIN_BYTE = 130` (`:265`), `punkte ≥ 1` (`:830`, `:857`) | 06d: Kopf 90, Minimum 138, Schlussstück mit 0 Punkten |
| F-KA-84 | **„Die Uhr ersetzt keine Browserhülle" ist nur eine Serverregel** (`ingest.php:749`, `uhr_gesperrt`) | Fassung 2 hält es im Standbuch |
| F-KA-85 | **Android `minSdk = 26`**, ECDH im Keystore erst ab API 31 (`android/handy/build.gradle.kts:40`) — darunter liegt der Geräteschlüssel im vorhandenen Tresor | 06c; Bedrohungsmodell nennt es |
| F-KA-86 | **Die Demo-Anmeldung lief örtlich in ihre Stundensperre** (20 je Stunde), weil Einzelläufe der Bedienprobe mit dem Prüfstand zusammenkamen — die Barrierefreiheitsprobe war rot, mit dem Code von `main` ebenso | Zähler `demo`/`demog` örtlich geleert, voller Lauf neu; Einzelläufe sparsam |
| F-KA-87 | **Schneiden und Löschen treffen die Lückenregel:** `ortsdaten.js:392-395` behält beim Schneiden die Sequenznummern, ein Stück endet an jeder Lücke — unter Fassung 2 ist das `luecke`, solange kein Löschvermerk im Standbuch sie erklärt. Die Sperren liegen heute im Klartext (`m.spur_sperren`, `{von, bis}`) | 06b-3: Vermerke ins Standbuch (V-KA-06); Schneiden und Löschen nur entsperrt (E-KA-43) |
| F-KA-88 | **Schreiben als Konto braucht den Inhaltsschlüssel:** die drei Schreibwege in `ortsdaten.js` (`:407`, `:445`, `:454`) haben heute nur den öffentlichen Kontoschlüssel | 06b-3 |
| F-KA-89 | **JSON ist zwischen den Umsetzungen nicht byte-gleich für ganzzahlige Koordinaten:** Python schreibt `47.0`, JavaScript `47`; die Vektoren meiden solche Werte. Öffnen geht, byte-gleiches Nachbauen nicht — betrifft auch 06c und KG-03. Umgekehrt nimmt der Browser `"z": 3.0` an (nach `JSON.parse` nicht von `3` zu unterscheiden), die Referenz nicht | benannt; die Vektoren meiden ganzzahlige Koordinaten weiter, KG weiß es |
| F-KA-90 | **Die Referenz war an drei Stellen lockerer als der Browser:** eine leere Anteil-Kennung öffnete als Hülle ohne Anteil (`referenz_inhalt.py:271`), das Gerätebuch prüfte SID und SPK nicht (`:350`), ein kaputtes gepacktes Stück warf ohne benannten Grund (`referenz.py:351`) | mit 06b-1 nachgezogen, drei neue Fälle in der Selbstprobe; die Vektoren ändern sich nicht |
| F-KA-91 | **Das Demo-Konto stellt nie auf das neue Format um** — ohne Anteil-Kennung überspringt `unlock.js:313` die Umstellung | die Demo-Hülle kommt als `edk2:` aus der Fixture (E-KA-44, 06d) |
| F-KA-92 | **Die Arbeitskopie des Agenten begann auf `main` statt auf dem Zweig**, und `.claude/worktrees/` stand unverfolgt im Baum — der Prüfstand hätte sie mit `git add -A` eingesammelt | Zweig im Agenten vorgespult; `.claude/worktrees/` örtlich in `.git/info/exclude` |
| F-KA-93 | **Der Container startete mitten in der Arbeit neu** (11.11.2026, `uptime` 2 min); die Anlage lief mit `hochfahren.sh` wieder an | wie F-KA-71 |
| F-KA-94 | **Die Rückwegprobe lief in WebKit zweimal in die Zeitgrenze eines Seitenwechsels** (90 s), in beiden vollen Läufen von 06b-2 und an verschiedenen Stellen (nach dem Tor der BetreiberIn, beim ersten Anmelden); im vollen Lauf von 06b-1 war sie grün. Einzeln dreimal **23 ok, 0 fehlen**. Der Paketcode berührt keinen Browserweg, die Ratenzähler waren leer | im Kopf allein neu gemessen; als Backlog-Punkt beobachten, wenn es wiederkommt |
| F-KA-95 | **Nach dem Neustart des Containers liefen die Plattform-Container nicht** — die Schemaprobe fand MariaDB 10.6 und beide MySQL nicht | `plattform.sh alles` nach jedem Neustart; `hochfahren.sh` startet sie nicht mit |
| F-KA-96 | **Die neue Migration fehlte in der Vorabliste von `schema.sql`** — eine Neuinstallation hätte sie als offen gemeldet | Zeile ergänzt; das Migrationsregister hat es gefunden |
| F-KA-97 | **Der Agent für 06b-3 brach mit dem Neustart des Containers ab** und ließ sich nicht fortsetzen; sein Stand (11 Dateien, nur 06b-3a) lag uncommittet in der Arbeitskopie | als Zwischencommit gesichert, von der Hauptinstanz fortgeführt (E-KA-55); Agenten committen künftig je Teil |
| F-KA-98 | **Die erste Fassung der Probe legte die Weiche nicht um, sondern brach die Seite:** `true` ist ein Zeichen kürzer als `false`, und eine abgefangene Antwort mit der alten `Content-Length` verwirft der Browser (`net::ERR_FAILED`) — ohne `EdInhalt` fiel alles Weitere | Länge und Kodierung aus dem Kopf der Antwort genommen |
| F-KA-99 | **Formular und Import reichten dem Leser den Klartextbeginn nicht mit** — jede Einsatzhülle der Fassung 2 wäre dort mit `zeit` gescheitert und hätte das Speichern gesperrt | `started_ts` mitgegeben; `einsatz()` nimmt auch `started_at` |
| F-KA-100 | **Die Formprüfung kannte nur die Gerätehülle der Fassung 1** (F-KA-83) — mit umgelegter Weiche wies der Server die Einsatzhülle des Formulars zurück | `pruef_huelle()` kennt beide Fassungen (E-KA-56) |
| F-KA-101 | **Die Bedienprobe `ka06b2-standbuch` legt Zufallstexte als Blatt und Gerätebuch des Prüfkontos ab und lässt sie liegen** — ein solches Gerätebuch geht zu Recht nicht auf, und jeder spätere Weg, der es öffnet, scheitert mit `pruefwert` | `ka06d-kopplung-buch` leert das Standbuch des Prüfkontos davor und danach (nur örtlich) |
| F-KA-102 | **Die Geräteseite lud das Rüstzeug nur, solange ein Gerät mit Schlüssel in der Liste stand** — nach dem Entkoppeln des letzten lief der Abgleich des Gerätebuchs nie | geladen, sobald Migration und Kontopaar da sind; gefunden von `ka06d-kopplung-buch` |
| F-KA-103 | **Mit ausgelieferter Weiche trägt der Browser kein Gerät ins Gerätebuch** — ein Gerät, das vor dem Umlegen koppelt, bekommt kein `huelle: 2` und sendet Fassung 1, bis es neu koppelt | benannt (Technik 4.42); für Web 25.0.0: die Geräte bis dahin neu koppeln, oder ein Weg „Wert nachträglich bestätigen" (Mockup) |
| F-KA-104 | **Das Demo-Gerät mit festem Schlüssel (E-KA-44) ergibt erst Sinn, wenn die Demo-Daten in Fassung 2 verschlossen sind** — mit ausgelieferter Weiche gibt es weder Gerätebuch noch Fassung-2-Gerätedaten im Demo | verschoben auf das Umlegen der Weiche (Web 25.0.0), mit dem Neuverschließen der Demo-Fixture |
| F-KA-105 | **Das Aufräumskript las zuerst jede Adresse der Kontenliste** — auch den Platzhalter des Einladungsformulars (`neue@example.invalid`), und meldete ihn als nicht löschbares Konto | es liest nur die Adressen der Kontozeilen |
| F-KA-106 | **Der Weg `ka06b4-anhebelauf` (06b-4) ließ das Demo-Konto abgemeldet zurück** — sein Reset beendet die Sitzung, die Seite merkt es aber erst an einer Anfrage im Hintergrund und leitet nach der Wache von `gehZu()` zur Anmeldung um; in Firefox und WebKit stand `mr-oeffner-markiert` danach auf `login.php`. 06b-4 war nie dreifach gefahren worden (Hauptstufe ohne Zehnergrenze) | der Weg meldet die Rolle nach dem Reset neu an; nachgefahren in drei Engines |
| F-KA-107 | **Die Rückwegprobe lief im ersten vollen Lauf über den Merge in Chromium in eine Zeitgrenze** — `page.goto(suche.php)` wartete 30 s auf `networkidle`, nach acht grünen Schritten; `suche.php` berührt KA-06d nicht, Firefox und WebKit waren im selben Lauf grün | nicht nachzustellen: einzeln dreimal grün; zweiter voller Lauf. **Nicht behoben:** die Probe wartet weiter auf `networkidle` — wiederholt es sich, gehört sie auf `domcontentloaded` und ein Warten auf den Inhaltsschlüssel umgestellt (eigene Nummer) |
| F-KA-108 | **`pair.php` beginnt die Kopplung mit Schlüssel auch an einem Konto ohne Schlüsselpaar** — `status` trägt dann `n_b`, aber `kontoschluessel: null`, und die Uhr bricht mit „Konto ohne Schlüssel" ab (gemeldet von KG, F-KG-20; hier nicht nachgestellt) | geht mit 06c (BetreiberIn, 14.11.2026): der Server lehnt den Block `schluessel` ohne Kontopaar mit eigenem Fehler ab, oder die Webseite hält vor dem Verbinden an |
| F-KA-109 | **Das Mockup M-KA-02 zeigte in D „Gekoppelt · Adresse"** — die Sync-Karte zeigt dort seit jeher den Server (`Gekoppelt · nadoku…`), nicht das Konto | gebaut ist nur, was neu war: die Zeile „Herkunft gesichert" darunter; die erste Zeile bleibt, wie sie ist |
| F-KA-110 | **Die Kopplungsprobe koppelte in Teil 7 an einem Konto ohne brauchbares Schlüsselpaar** (K8 hatte es absichtlich zerstört), und Fall 36 nahm `kontoschluessel: null` hin — genau der Fall F-KA-108 | Teil 7 legt das Paar vorher wieder an; 36 verlangt den Schlüssel; neuer Fall 48, gegen Web 24.18.0 rot |
| F-KA-111 | **Punkt 8 des Entwurfs (KA-06d) sagte, der Server prüfe den Anschluss von `seq` beim leeren Schlussstück** — `spur2_stueck_schreiben()` prüft nur die Form; der Vertrag 3.4 hatte recht (der Browser prüft beim Lesen). Gefunden vom Fable-Agenten als offene Frage, nachgelesen am Quelltext | Punkt 8 berichtigt |
| F-KA-112 | **Gleiches `z` wird angenommen** (`z ≥ o`): Zwei Geräte, die in derselben Sekunde verschließen, sind gegeneinander austauschbar. Selten und klein, stand aber nirgends (Fable-Agent) | benannt; gehört zu Q-KA-15 |
| F-KA-113 | **Das Handy schreibt kleine Koordinaten als `1.0E-5`** (org.json), die Referenz als `1e-05`. Beides ist gültiges JSON, die Referenz liest es; mit dem Browser nicht gemessen (Fable-Agent) | benannt; Gegenprobe im Browser mit dem nächsten Paket, das Fassung 2 vom Handy öffnet |
| F-KA-114 | **Der Prüfstand wählte die Kopplungsprobe bei geänderter `server/kopplung_lib.php` nicht aus** — das Muster `geraete` in `tools/pruefstand/pruefablauf.json` nannte `pair.php` und `geraete*.php`, nicht die Bibliothek und nicht die Probe selbst; in `klein` lief sie deshalb für KA-06c nicht (in `neben` und `haupt` über das Auffangmuster schon) | beide Pfade ins Muster `geraete`; KA-06c hatte die Probe einzeln gefahren (105/0, Fall 48 gegen 24.18.0 rot) |
| F-KA-115 | **E-KA-66 und E-KA-67 sind doppelt vergeben** — je einmal von der BetreiberIn (14.11.2026: Mockup M-KA-02, Fable-Agent für 06c) und von der Umsetzung (13.11.2026: Gerätebuch in zwei Schritten, Anschluss des Schlussstücks). Gefunden beim Vergeben von E-KA-74 | nicht umnummeriert (Verweise in Commits und PRs); wer zitiert, nennt Datum oder Inhalt dazu |
| F-KA-116 | **Die Selbstprobe der Fassung 2 ist bei den alten Fällen schwächer, als sie aussieht:** `pruefen2()` vergleicht zuerst die ganze Datei mit dem Neubau, jede veränderte Kopie ist schon dadurch rot — die Fälle „Fehler 2“ sind damit trivial erfüllt (Fable-Agent, 06f-1) | die neun neuen Fälle verlangen die benannte Meldung; die alten bleiben, wie sie sind (Backlog-Kandidat) |
| F-KA-117 | **Ein Abschluss ohne neuen Punkt ging am Handy nie an den Server** (älter als KA-06f, gefunden vom Fable-Agenten in 06f-3): `paketSchliessen()` ließ `metadaten_bestaetigt` stehen; war der Teil-Upload bestätigt, hatte das Paket laut `hatArbeit()` keine Arbeit — `final`, Endzeit, Strecke und die Hülle mit SCHLUSS blieben im Handy, ohne als Rückstand zu zählen | behoben: der Abschluss setzt die Bestätigung zurück; Prüffall `einAbschlussOhneNeuenPunktGehtHinaus` (ohne die Zeile rot); verwandtes Rennen bei der Bestätigung: Backlog Nr. 566 |
| F-KA-118 | **Mit `minSdk` 31 wuchs das Release-APK von 9,3 auf 24,8 MB** — das Gradle-Plugin legt ab `minSdk` 28 die Dex-Dateien unkomprimiert ab | E-KA-77: komprimiert, 9 251 914 B |
| F-KA-119 | **Beim Zusammenführen zweier Blätter gewann ein höherer Gerätestand über einen niedrigeren Kontostand, und `k` ging verloren** (bestehend in `hoeher()`, gefunden in KA-09) — danach käme ein Gerät nach dem Konto wieder durch | **offen:** auch `stand_zusammenfuehren()` (KA-09/-10) lässt den höheren `o` gewinnen, `k` zählt nur bei Gleichstand (Vektor „konto-unter-geraet“). Schließen hieße: Regel in der Referenz ändern („`k` haftet wie `f`“) samt Vektoren — Frage an die BetreiberIn (Q-KA-21) |
| F-KA-120 | **Das Prüfmuster `huelle` nannte `inhalt2.js`, `huelle2.js`, `standbuch.js` und `ortsdaten.js` nicht** — eine Änderung nur dort wählte die Hüllenprobe in `klein` nicht (wie F-KA-114); und `ortsdaten.js` maß überhaupt keine Probe | vier Pfade ins Muster; neue `ortsdaten_probe.mjs` (17 Prüfungen) läuft in `probe.sh` |
| F-KA-121 | **Zwei Abweichungen von M-KA-03 im Bau von KA-11:** (1) Die Zeiten stehen ohne Datum („09:31 Uhr“) — `EdFormat` kennt keinen Tag aus einer Unix-Sekunde, und eine eigene Rechnung verbietet das Register; (2) die Geräte heißen wie im Gerätebuch (Kennung, z. B. `dev-…`), nicht „Garmin fēnix 6 Pro“ — das Buch kennt kein Modell. Zustand D („übernommen“) zeigt die Seite erst mit 06e, das die Kennzeichnung schreibt; der Befund `stand-uebernommen` nach einer Bestätigung wird nicht eigens angezeigt (im Mockup nicht vorgesehen) | benannt; Modellname wäre ein Feld im Gerätebuch (06e oder später) |
| F-KA-122 | **Kreislauf `edbak` mit umgelegter Weiche verliert eine Aufzeichnung** (Ruhesegment, 131 → 0 Punkte; 222 statt 223 Spuren nach Einspielen und erneutem Sichern) und erzeugt dabei 239 Antworten 409 | **behoben (KA-14):** Ursache war nicht der Import der 409, sondern der fehlende Löschvermerk im Standbuch für eine Lücke (Fassung 2 verlangt ihn, `luecke`); das Einspielen trägt jetzt die deckenden Vermerke ein und lehnt eine Spur ohne ab. Die 409 kamen vom gleichzeitigen Fortschreiben beim Sichern (wie F-KA-124). Kreislauf 223 Spuren, 0 Abweichungen, 0 × 409 |
| F-KA-123 | **Die Übernahme einer Kontosicherung (Freigabe) scheitert mit umgelegter Weiche** — 409, 400, Gerätehülle `zeit` | **behoben (KA-14):** `blaetter: []` ging beim zweiten Versuch mit (400); jetzt `null`. Die Probe öffnet über das Standbuch und reicht den Beginn durch. Freigabeprobe 27 / 0 |
| F-KA-124 | **Der GPX-Export über einen Zeitraum hängt am Messstand mit umgelegter Weiche** (5 088 Einsätze, nach 1 800 s kein Download, 101 867 Krypto-Aufrufe) | **behoben (KA-14):** Standbuch schreibt je Blatt nacheinander, Lesen schreibt gesammelt fort. Messstand: Backup 120,75 s (Ziel ≤ 5 min), GPX mit Passwort 224,56 s, ohne 207,04 s, 0 × 409 — langsamer als vor der Weiche (Backlog Nr. 569) |
| F-KA-125 | **Die erste Kopplung nach der Anmeldung meldet mit ausgelieferter Weiche „Das Gerät ließ sich nicht eintragen“** (Bedienprobe `ka06d-kopplung-wert`; derselbe Ablauf mit nur im Probebrowser umgelegter Weiche war grün) — genau der Weg, den KA-12 nach dem Tag verlangt | **kein Fehler der Anwendung:** Reste von `ka06b2-standbuch` (ein Zufallstext als Gerätebuch im Prüfkonto) ließen `geraetEintragen()` scheitern; `ka06d` räumt Standbuch und Ablage jetzt vor und nach. Ein Gerätebuch, das nicht aufgeht, sperrt aber jede Kopplung (Backlog Nr. 568) |
| F-KA-126 | **Prüfmittel erwarteten das alte Format oder rechneten Ableitung 1:** `probekonto.mjs` (Token bei 1 200 000 Runden), Rückweg-, Kontopaar-, Ortsdaten-, Freigabeprobe (`edk1:` erwartet bzw. mit `EdCrypto.decrypt` geöffnet), Spurprobe (Punkte aus der Fixture), `ka06b3-…-weiche-aus` (Zustand vor Web 25) | Probekonto, Rückweg, Spurprobe (alte Fixture unter `tools/referenzdatensatz/fixture/alt/`), `ka06b3`, Ortsdaten-, Wartungslauf-, Kontopaar-, Freigabe-, CSP-Probe (Zeitgrenze 90 s) berichtigt |
| F-KA-127 | **Vierzehn Browserwerkzeuge warteten beim Anmelden nur 30 s** — seit dem Rundenziel 1 200 000 (im Übergang zweimal abgeleitet) riss das in WebKit unter der Last des Prüfstands von Lauf zu Lauf an anderer Stelle (CSP-Probe, Barrierefreiheit); einzeln gemessen dauert die Anmeldung in WebKit 1,5 s | alle auf 90 s, wie die übrigen Anmeldewege |
| F-KA-61 | **Lauf 193 (Tag `web-v24.11.0`) hat Produktiv von Web 23.8.2 auf 24.11.0 gehoben** — damit auch die Migration `2026_11_07_darstellung`, die der Rahmenplan für den nie gesetzten Tag `web-v23.11.3` ansagte | Rahmenplan-Kopf berichtigt; `update.php` auf Produktiv ist fällig, falls noch nicht aufgerufen (Bericht an die BetreiberIn) |
| F-KA-62 | **Der erste Bau von Punkt 4 hätte einen Gerätevertrag still gebrochen:** `jobs.php` las das Token über `app_state_lesen()`, das einen Datenbankfehler fängt — bei toter Datenbank wäre aus 500 `datenbank` ein 403 „Token falsch" geworden (AP4-c). Gefunden nicht von einer Probe, sondern vom Grundtext der Zählzeile Z10, deren Ratsche nach unten anschlug | `token_gespeichert()` liest mit eigenem SELECT, das wirft; Z10 bleibt bei 2, der Grund nennt die neue Stelle. **Nicht gemessen:** keine Probe stellt eine tote Datenbank vor `jobs.php` nach |
| F-KA-63 | **Erster Prüfstand von KA-03: 3 rot** — Ratenprobe (erwartete den Health-Token aus `config.php` im Klartext abgelegt), Zählung (zwei Rollenvergleiche von Hand in `admin_users.php`, Z13; dazu Z10 aus F-KA-62), Wortliste (ein Satz im Bedrohungsmodell nannte „im Klartext" neben Ort und Spur). Dazu der Hinweis `erzeugen`: `docs/Design.md` neu erzeugt | Ratenprobe erwartet den Hash (102/0); `rolle_darf_support()` statt `!== 'user'`; Satz umformuliert; `Design.md` geht in den Commit |

**Auch KA-03 baut keine neue Probe:** Es erweitert Rollen-, Zweitfaktor-,
Freigabe-, Versand-, Jobs- und Wartungsprobe; deshalb keine Nummer aus der
Spanne.

**Keine neue Probe, keine neue Backlog-Nummer:** KA-01 erweitert vorhandene
Riegel (`kettenaufrufe`, Anlass Nr. 217; `fremdsummen`, F-R17-50/-51;
`bericht.py`) um Lagen mit F-KA-Nummern. Die Spanne 560–569 bleibt für die
Pakete ab KA-02.

---

## 11. Garmin-Teil — eigene Instanz KG (seit 10.11.2026, E-KA-21)

Was die Instanz KG baut. Sie führt dazu ein eigenes Konzept
(`docs/konzepte/Konzept-KG-Garmin-Sicherheit.md`, legt sie selbst an) mit
Statusblock und Prüfprotokoll; **dieser Abschnitt ist nur der Auftrag**, sein
Stand steht dort.

| Paket | Inhalt | Befunde | Hängt an | Modell |
|---|---|---|---|---|
| KG-01 | `Pair.start()` weist bei laufendem Dienst immer ab; die Bindung an den alten Schlüssel deckt auch laufende Pakete (`cred` gelöscht); `next_seq` höchstens `seq_from + gesendete Punkte`; ein unbestätigtes Stück wird mit derselben Größe wiederholt; Kommentare `Huelle.mc` (Kurvenprüfung) und `Pair.mc` (Schulterblick) berichtigt | F-KA-05, -07 (Uhr-Seite), -18 (Uhr-Seite), -43 (zwei Stellen) | nichts — die Server-Seite von F-KA-07 (KA-04 Punkt 3) ist mit der Uhr-Deckelung verträglich | Opus |
| KG-02 | **Messung für KA-06a:** Hält `Toybox.Cryptography` auf den Zielgeräten ein statisches P-256-Paar dauerhaft (Speicher, Neustart, Update der App)? Was kostet dort eine ECDSA-Signatur (Zeit, Speicher), und gibt es sie überhaupt? Ergebnis als Befund mit Zahlen für die Entwurfsrunde | — | nichts | Opus |
| KG-03 | Garmin-Teil von KA-06c: Geräteschlüssel, Gerätehülle Fassung 2, Kopplung mit Vergleichswert — byte-gleich zur Referenz aus KA-06a | F-KA-04, -12, -17 (Garmin) | KA-06a fertig und KA-M5 freigegeben | **Fable** für den Krypto-Kern — vorher pausieren |
| KG-04 | Garmin-Teil von KA-M6: Übergabe einer echten Uhr im laufenden Dienst, Kopplung mit Vergleichswert — als Prüfliste für die BetreiberIn | — | KG-01, KG-03 | — |
| KG-05 | **Garmin-Teil von KA-06f (E-KA-69, -74):** `z` am Inhalt — vergeben bei einer Änderung des Inhalts (alles innen außer `z`, vorher Bytevergleich), mit dem Paket abgelegt, ein Gerätezähler über Neustart und Trennen; gemessen an `vektoren-2.json`, Schlüssel `zaehler` und Fälle `inhalt-…` (Commit `466b963`). Die Sitzung KG war am 15.11.2026 nicht mehr aktiv (`session_inactive`); der Auftrag steht deshalb hier | — | 06f-1 (gepusht) | **Fable** für den Krypto-Kern |

**Regeln der Zusammenarbeit:**

- KG fasst `watch/`, `tools/uhr-pruefstand/`, die Garmin-Abschnitte von
  `docs/Handbuch.md`, `docs/Technik.md`, `docs/Geraete-Eingabe.md` und die
  Garmin-Zeilen des Bedrohungsmodells an — **nicht** `server/`, `android/`,
  `.github/` und nicht den Rahmenplan. Braucht KG eine Serveränderung, sagt
  sie es der BetreiberIn; diese Instanz baut sie.
- Eigene Backlog-Spanne, vor der ersten Nummer reserviert und gepusht
  (E-SD-21); eigene Uhr-Fassung (`watch/source/Const.mc`), eigener Tag
  `uhr-v*`.
- Changelog-Einträge mit Präfix `Uhr`. Merkonflikte im Changelog, im
  Bedrohungsmodell oder in diesem Konzept löst, wer als Zweiter mergt, nach
  `Pruefablauf.md` 5.3.
- Diese Instanz trägt Beginn und Ende von KG in den Rahmenplan ein, sobald
  KG es meldet.

