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

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **11.10.2026 — R17-01 erledigt: die Bestandsaufnahme.** Drei lesende Leser (Opus) haben den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegen die Doku gelesen; Fable hat die Hoch- und Mittel-Befunde am Quelltext nachgeprüft und das Inventar verdichtet: **55 Bausteine** (3.2), **31 Befunde** (2.3) — fünf **hoch** (die S10-Zusage überzeichnet das Offline-Raten, F-R17-01; die Freigabe liefert einem Admin den Klartext jedes Kontos ohne Spur, F-R17-02; Gradle erbt den App-Signaturschlüssel, F-R17-03; ein Probelauf fordert `produktion` von jedem Zweig an, F-R17-04; die Pflichtstücke des Wiederanlaufs sind seit dem 14.09.2026 offen, F-R17-05), fünfzehn mittel, acht niedrig, zwei Hinweise. Was keiner sicher lesen konnte, steht im Prüfdokument 1. **Als Nächstes: R17-02** (das Modell schreiben). Davor: **R17-00 erledigt** (Spanne, Fahrplanzeile, dieses Dokument, Prüfdokument). |
> | Entschieden | E-R17-01 bis E-R17-05 (Abschnitt 2.1) — E-R17-01 bis -03 von der Betreiberin am 11.10.2026, E-R17-04 am 28.09.2026, E-R17-05 aus der Umsetzung. |
> | Offen | keine Frage offen; für R17-03 vorgemerkt: ob F-R17-01, -02, -03 als Bruch einer Zusage **kritisch** sind (Sofortpaket, R69), ob ein Admin BetreiberIn-Konten anfassen darf (F-R17-07), der Zweck von Weg B — Server oder Server **und** Gerät (F-R17-13) —, dazu Nr. 146, die S11-Entwurfsfragen und Nr. 263. |
> | Umsetzung | **R17-00 erledigt** 11.10.2026 (`fca8549`). **R17-01 erledigt** 11.10.2026 (Inventar I-01 bis I-55, Befunde F-R17-01 bis -31; Prüfdokument 1 und 2 fortgeschrieben). R17-02 bis R17-04 offen. |
> | Fable-Schritte | alle; keine Opus-Umsetzung in diesem Schritt. |
> | Fächerung | **R17-01 gefahren:** drei lesende Leser mit **Opus** (Server und Browser 129 Werkzeugaufrufe, 15 min; Uhr und Android 71, 10 min; Kette und Betrieb 105, 11 min), ohne Nebenwirkung — Leser 3 hat über `gh api` nur gelesen (Rulesets, Zweigregeln; Umgebungen und Organisation mit 403 verweigert). Zusammenführung und Nachprüfung Fable, seriell. Die übrigen Pakete seriell. |
> | Nummern | 370 bis 379 reserviert; vergeben: keine. |

---

## 1. Auftrag

**Ziel von Schritt 12:** ein Dokument, kein Code — das **Bedrohungsmodell**
der Anwendung, als Stück 1 des R17-Reviews (R69). Es sammelt ein, was heute
verteilt liegt (`Technik.md` 4.97 bis 4.99q, `Review-Krypto-Sicherheit.md`
2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18), und sagt
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
| E-R17-01 | **Das Modell ist eine eigene Datei, `docs/Bedrohungsmodell.md`**, normative Dokumentation neben `Technik.md` — kein Abschnitt darin. Es wird im selben Paket, in dem es entsteht, in den Bereich `c` der Textprobe eingetragen (`Pruefablauf.md` 6.6) und aus `README.md`, `Technik.md` und `CLAUDE.md` 4 verlinkt. | Betreiberin, 11.10.2026 (Vorschlag Fable) | Planung v1.0 ließ die Wahl offen („eigener Abschnitt oder eigene Datei, entscheidet der Review"). `Technik.md` hat 13 600 Zeilen; das Modell hat andere Leser (Betreiberin, Datenschutz, Review) und muss ohne die Technik lesbar sein. |
| E-R17-02 | **Kürzel `R17`; dieses Dokument bleibt nach Schritt 12 stehen.** Es ist das Konzept von 12 (Statusblock, Pakete, Prüfprotokoll) und zugleich die Sammelstelle des ganzen Reviews; 12b schreibt mit denselben Zählern fort. Die Erledigt-Zeile von 12 nennt es; gelöscht wird es erst mit dem Abschluss von 12b. | Betreiberin, 11.10.2026 | Abweichung von R62 („das Konzept wird gelöscht"), weil der Rahmenplan es so vorsieht („Review-R17.md beginnt damit", „Fortsetzung aus 12") und eine Fundliste, die über 12a hinweg zwei Dateien hätte, zwei Zählungen hätte. |
| E-R17-03 | **Push nach jedem Paket auf `claude/exciting-brown-0n9et2`** (K7); **kein Pull Request vor der Freigabe des Modells** durch die Betreiberin. | Betreiberin, 11.10.2026 | K7 und `CLAUDE.md` 8; der PR ist die Phase, nicht ein Paket. |
| E-R17-05 | **Die Leser von R17-01 laufen mit Opus, die Zusammenführung mit Fable.** Ihre Berichte bleiben außerhalb des Repositoriums; was zählt, steht verdichtet in 3.2 und 2.3, und jeder Befund trägt, wer ihn gelesen hat. | Fable, 11.10.2026 | Eine Messung ist lesend und gut fächerbar (`CLAUDE.md` 7); drei Leser mit Fable kosteten das Dreifache für eine Vorarbeit, die ohnehin nachgeprüft wird. Die Rohberichte (rund 100 kB) sind Lesungen mit Unsicherheiten; ins Repositorium gehört nur, was gegengelesen oder als Lesung gekennzeichnet ist. |
| E-R17-04 | **Schritt 12 läuft nicht parallel zu 18**, sondern danach, wie der Rahmenplan es vorsah. | Betreiberin, 28.09.2026 | Fable hatte das Parallelfahren vorgeschlagen (das Modell ist zu neun Zehnteln unabhängig von 18) und den Preis genannt (Nachlese-Paket, R-Nummer, Konflikte in den Steuerungsdokumenten). Die Betreiberin hat die Reihenfolge behalten; 18 war am 05.10.2026 gemergt. |

### 2.2 Fragen an die Betreiberin (Q)

Kommen mit R17-03, je Frage mit Empfehlung und Preis; die Antwort wird
E-R17-NN. Vorgesehen: die zwei Fragen aus Nr. 146 (Argon2id;
nicht-extrahierbarer `CryptoKey`), die Entwurfsfragen für S11 (was Klartext
bleiben darf; wie der Schlüssel aufs Gerät kommt; SP-9 gegen das Modell),
Nr. 263 (Stand setzen). *Noch keine gestellt.*

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
2. **Angreiferlagen** — die neun aus `Review-Krypto-Sicherheit.md` 2 (A bis
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

## 4. Arbeitspakete

| Paket | Inhalt | Fächerung | Abnahme |
|---|---|---|---|
| **R17-00 Buchführung** | Spanne 370–379, Fahrplanzeile (Rahmenplan Fassung 154), dieses Dokument mit Statusblock, Prüfdokument; Werkzeuge der Steuerung grün. | seriell | `decken.py` 0 gerissen, `uebersicht.py` 0 ohne Ziel, `nummern.py` 0 Überschneidungen; gepusht. |
| **R17-01 Bestandsaufnahme** | Die verstreuten Stellen einsammeln und **gegen den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 gegenlesen**, nicht gegen die Doku: `Technik.md` 4.97, 4.97c/d, 4.98, 4.99a/a2/b/q, `Review-Krypto-Sicherheit.md` 2 und 3, SP-9, `Konzept-V1-Ortsdaten.md`, die Ergebnisse von 18 (Sitzungsbindung, Gerät merken, Passkeys, Schlüsselwechsel, Notzugang, Rechenaufgabe), Kette und ihre Geheimnisse, Keystore des Handys, Geräteschlüssel der Uhr, Play App Signing. Ergebnis: Inventartabelle in 3.2, Befunde, wo Doku und Code auseinandergehen. | **lesend, drei Leser** (Server; Clients; Kette und Betrieb), ohne Nebenwirkung; Fable führt zusammen. | Inventar vollständig gegen die Liste im Fahrplan (Schritt 12: „Verschlüsselung, Container 4, SPUR1, Komplettbackup und Serverschlüssel, Demo, Handy, Kopplung, SP-9 und Nr. 146, Signatur, Kette, Nr. 109, Nr. 263"); jede Zeile mit Fundstelle. |
| **R17-02 Das Modell schreiben** | `docs/Bedrohungsmodell.md` nach 3.1; Bereich `c` der Textprobe; Verweise aus `README.md`, `Technik.md` (4.99b und der Sicherheitsabschnitt verweisen hin, statt zu wiederholen), `CLAUDE.md` 4. | seriell | Modell vollständig gegen 3.1; Textprobe 0 neue Treffer; Verweise stehen. |
| **R17-03 Fragen an die Betreiberin** | Q-R17-NN mit Empfehlung und Preis: Nr. 146 (Argon2id; `CryptoKey`), S11-Entwurfsfragen, Nr. 263. **Halt:** wartet auf die Antworten. | seriell | Jede Frage beantwortet und als E-R17-NN eingetragen; Nr. 146 erledigt, Nr. 263 mit Stand. |
| **R17-04 Abschluss** | Antworten ins Modell einarbeiten, Prüfdokument mit Mittel und Zahl, Prüfstand `klein`, Freigabe der Betreiberin; danach Erledigt-Zeile (Rahmenplan 8), Backlog-Kopfzeilen, Verlaufszeile, Pull Request gegen `main`. | seriell | Freigabe erteilt; PR gestellt. |

## 5. Prüfprotokoll

Je Paket: was gemessen wurde, mit Mittel und Zahl. Die Prüfliste für die
Betreiberin steht im Prüfdokument.

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| R17-01 | drei Leser (Opus, lesend) · Nachprüfung Fable am Quelltext · `gh api` lesend (Rulesets, `rules/branches/produktion`) · `decken.py` · `uebersicht.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 55 Bausteine, 31 Befunde (5 hoch, 15 mittel, 8 niedrig, 2 Hinweise); **11 Befunde von Fable am Quelltext nachgeprüft** (F-R17-01 bis -06, -11, -14, -15, -19, -21), 20 als Lesung gekennzeichnet; `rules/branches/produktion` = `[]`; Werkzeuge: siehe Prüfdokument 2; Prüfstand: Commit-Nachricht von R17-01 | 11.10.2026 |
| R17-00 | `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | 21 Decken, 0 gerissen (die Verlaufszeile riss einmal mit 333 Zeichen, gekürzt auf 283) · 62 Einträge, 0 ohne Grammatik, 0 ohne Ziel, 2 zu Ziel 12 · 0 neue Nummern, 0 Überschneidungen gegen 8 Zweige · 0 neue Treffer · 0 Befunde · 150 Verweise, 0 Abweichungen. Prüfstand `klein`: siehe Commit-Nachricht von R17-00 | 11.10.2026 |

## 6. Quellen

- `docs/Rahmenplan.md` 3 (Schritt 12, 12a, 12b; „Warum so"), 7 (R17, R69,
  R78, R86).
- `docs/konzepte/Konzept-Planung-v1.0.md` 4e (Befund, Optionen, E-PV-5).
- `docs/konzepte/Review-Krypto-Sicherheit.md` (Eingang; Bedrohungsmodell in
  2, Verfahren in 3, Befunde in 4).
- `docs/konzepte/Vorbereitung-Sicherheitspaket.md` (SP-3b, SP-7, SP-9).
- `docs/konzepte/Konzept-V1-Ortsdaten.md` (Vorstudie Weg B, Fragen in 6
  beantwortet).
- `docs/Backlog.md` Nr. 146, Nr. 263, Nr. 43 (Weg B, gehört zu 12a);
  `Backlog-Erledigt.md` Nr. 109 (Notizen ins Blob) und Nr. 138 (Weg C).
- `docs/Technik.md` 4.97 bis 4.99q; `CLAUDE.md` 3 und 4.
