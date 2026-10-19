# Bedrohungsmodell — Gen-EM NAdoku

**Stand:** Web 21.14.0 · Uhr 3.1.0 · Android 0.17.0, gelesen am 11.10.2026
(Review R17, Stück 1; Schritt 12 des Rahmenplans); **Abschnitte 1, 3, 5 und 6
und die Zeilen zu W2 nachgezogen auf Web 22.7.0 · Uhr 4.0.0 · Android
0.18.0 am 18.10.2026** (S11-11, Schritt 12a), **W2, G3 und R3 auf
Web 23.0.0 am 19.10.2026** (S11-12, Aufräumauslieferung). **Dieses Dokument ist
die Referenz** dafür, was die Anwendung schützt, gegen wen, womit und wo
die Grenzen liegen. Wo ein anderes Dokument eine Zusage weiter fasst als
dieses, gilt dieses — und die Abweichung ist als Befund in
`docs/konzepte/Review-R17.md` 2.3 vermerkt, bis die Freigaberunde den
anderen Text nachzieht.

**Für wen.** Die **BetreiberIn** liest, welches Risiko sie trägt und
welches nicht (Abschnitte 2, 3, 5). Der **Review** (Stücke 2 bis 12) liest,
wogegen er prüft (Abschnitte 1, 2, 4). Das **Konzept S11** liest, was es
verschlüsseln muss und was bleiben darf (Abschnitt 6). **VVT und TOMs**
(Rechtstexte, Anlage III des AVV) lesen ihre technischen Maßnahmen aus
Abschnitt 4 ab und ihre Grenzen aus Abschnitt 3.

**Was es ist und was nicht.** Es beschreibt den Code, wie er ist — nicht,
wie er sein soll. Jede Aussage über die Anwendung ist am Quelltext gelesen
(Fundstellen als Funktionsnamen in `Review-R17.md` 3.2, Inventar I-01 bis
I-55); was dort niemand sicher lesen konnte, steht im Prüfdokument
(`Pruefdokument-R17-Review.md` 1), nicht hier als Behauptung. Es ist keine
Risikoanalyse mit Zahlen und kein Prüfbericht: Es sagt, *dass* eine Lage
offen ist, nicht, wie wahrscheinlich sie eintritt.

**Pflege.** Fortgeschrieben wird es (1) mit jeder Änderung der
Verschlüsselung, der Anmeldung, der Schlüsselablage auf einem Gerät oder
der Kette — also mit jeder Hauptstufe und mit jedem Paket, das Abschnitt 4
berührt; (2) wenn eine neue Angreiferlage dazukommt oder eine Grenze
fällt; (3) nach S11 vollständig neu in Abschnitt 3 und 6. Es liegt im
Bereich `c` der Textprobe (`docs/Pruefablauf.md` 6.6). Die Kopfzeile
„Stand" nennt die drei Fassungen, gegen die gelesen wurde; wer sie
anhebt, hat gelesen.

---

## 1. Schutzziele — was geschützt wird

Die Anwendung erfasst Rettungseinsätze: wer behandelt wurde, wo, wann, von
wem, mit welchem Verlauf. Daraus folgen fünf Werte. Je Wert steht, welche
Eigenschaft geschützt werden soll und welche Zusage das Projekt dazu gibt
(`CLAUDE.md` 4, Rahmenplan 1).

| Nr. | Wert | Eigenschaft | Zusage | Wo er liegt |
|---|---|---|---|---|
| W1 | **Geschützte Angaben** — Name, Geburtsdatum, Alter, Diagnose, Einsatznummer, Adresse und Koordinate des Einsatzorts, dessen Beschreibung, manueller Abfahrtort, Notizen des Einsatzes | Vertraulichkeit gegenüber jedem außer der NutzerIn | Ende-zu-Ende: nur der Browser der NutzerIn kann sie lesen; der Server, die BetreiberIn, der Hoster nie | `missions.pat_blob` (AES-256-GCM unter dem Inhaltsschlüssel), im Browser während der Sitzung, im `.edbak` der NutzerIn (unter ihrem Backup-Passwort), **im Klartext bei Photon** während des Tippens (Lage G) |
| W2 | **Ortsdaten und Verlauf** — seit Web 22 (S11) zweigeteilt. **Verschlossen:** GPS-Spur, Koordinate jeder Phase, Reanimationsverlauf (Gerätehülle unter dem Schlüsselpaar des Kontos), Ortshöhe und Transportziel mit Koordinate (`pat_blob`). **Klartext:** Zeiten, Phasennummern, Umriss der Spurstücke (Nummern, Punktzahl, Zeitraum, Stufe), Strecke und Anstieg, Besatzungsnamen, Einsatzmittel, Betriebsnotizen des Diensttags | Verschlossener Teil: Vertraulichkeit wie W1, gegenüber Server und BetreiberIn. Klartextteil: gegenüber Außenstehenden, **nicht** gegenüber Server und BetreiberIn | **Verschlossener Teil:** nur der Browser des Kontos öffnet ihn (privater Teil des Paars unter dem Inhaltsschlüssel); Geräte verschließen, öffnen nichts. Der Server nimmt seit Web 22.0.1 keinen Klartext mehr an. **Grenzen** in G3 (Gerätepuffer, Metadaten) | `track_stuecke`, `missions.ort_huelle`, `missions.pat_blob`; Klartext in `mission_phases` (Zeit, Nummer), `missions`, `mission_crew`, `days`; Klartextpuffer auf Garmin-Uhr und Handy. Die alten Spalten `track_points`/`track_blobs`, `mission_phases.lat/lon`, `resus_*` und die Zielspalten sind mit Web 23 gefallen (S11-12) |
| W3 | **Konten und Schlüssel** — Passwort, Anmelde-Token, Inhaltsschlüssel, Wiederherstellungsschlüssel, Server-Anteil, Serverschlüssel, Geräteschlüssel, Zweitfaktor-Geheimnisse, Sitzungen | Vertraulichkeit und Integrität | Der Server kennt vom Passwort nur den bcrypt-Hash des Anmelde-Tokens; keinen Inhaltsschlüssel; `config.php` ist Schlüsselträger aller Konten (Server-Anteil, Serverschlüssel) | `users` (Hüllen, Hashes, `rw_privat`), `config.php`, `app_state` (`jobs_token`, `salt_secret`, `reg_secret`, `notzugang_geheim`), `devices` (SHA-256 des Geräteschlüssels), Tresor des Handys, `Storage "cred"` der Garmin, `sessionStorage` des Browsers, GitHub-Umgebungen (Kette) |
| W4 | **Integrität der Anlage** — der ausgelieferte Code, die Datenbank, die Sicherungen, der Signaturschlüssel der Apps | Integrität | Nur die Kette liefert aus, nach Pflichtfreigabe; die Wache vergleicht das Ausgelieferte gegen den Zeiger; die Apps sind mit einem Schlüssel signiert, der auch bei Google liegt | Produktiv-Webspace, `main` und `produktion`, Umgebung `produktion`, Play App Signing |
| W5 | **Verfügbarkeit** — die Anlage, ihre Daten, der Rückweg nach einem Ausfall | Verfügbarkeit, Wiederherstellbarkeit | Zwei Komplett-Stände, auswärtige Ziele, ein Wiederanlaufpaket aus vier Stücken; Wartungsmodus; Staging vor Produktiv | `sicherungen/`, Backup-Ziele, Betriebsakte (außerhalb des Repositoriums), die eine Person, die freigibt |

**Eine Zuordnung, ohne die der Rest nicht lesbar ist:** Die Verbindung
„dieser Datensatz gehört zu dieser Person" entsteht **ausschließlich** über
W1. W2 ist für sich nicht personenbeziehbar, wird es aber **mit
Zusatzwissen** — wer weiß, dass am 3. März um 14:12 vor einem bestimmten
Haus ein Rettungsmittel eingetroffen ist, findet in W2 den Einsatz, seine Klinik und
seinen Reanimationsverlauf, ohne W1 je zu öffnen. Das ist der Satz, der
Schritt 12a (S11) begründet. **Seit S11** findet er den Einsatz über die
Zeiten weiterhin, Ort, Klinik und Reanimation aber nicht mehr. (Bis
Web 22.7.0 galt das nicht für den Altbestand vor dem Umzug und für
Sicherungen von vor S11; seit Web 23 gibt es beides nicht mehr, S11-12.)

## 2. Angreifer — wer greift an, was hat er, was erreicht er

Die neun Lagen A bis I stammen aus dem Krypto-Review vom 05.09.2026
(Abschnitt 2 jener Datei; sie ist mit R17-04 gelöscht, die Git-Historie
behält sie, der Stand je Befund steht in `Review-R17.md` 3.4) und sind
hier auf den Stand von Web 21.14.0 gebracht; J bis P sind neu. **Fett** ist, was eine Zusage aus Abschnitt 1 bricht. „Erreicht"
meint: ohne weiteren Fehler der Anwendung, nur mit dem, was die Lage ihm
gibt.

| Lage | Wer | Was er hat | Was er an W1 erreicht | Was er sonst erreicht |
|---|---|---|---|---|
| **A** | **Datenbankabzug** — SQL-Lücke, Hoster-Panne, gestohlener Dump, neugieriger Datenbank-Admin, ein BetreiberIn-Download (Lage I) | Chiffretexte, beide Hüllen, Salz, Rundenzahl, `password_hash`, `pat_key_check`, `app_state`-Geheimnisse, Mailwarteschlange | Verschlossen, **solange das Passwort stark ist**: Er kann das Passwort **offline raten** (je Kandidat PBKDF2 mit 600 000 Runden plus ein bcrypt-Vergleich gegen `password_hash` — das Anmelde-Token trägt keinen Server-Anteil); die Hülle öffnen kann er aus dem Abzug allein **nicht** (dafür fehlt `kdf_anteil`), ein gefundenes Passwort führt aber über eine Anmeldung zum Anteil — und bei Konten ohne Zweitfaktor damit zu allem. Wiederherstellungshülle: 98 Bit, unerreichbar | **Den Klartextteil von W2**: Zeit, Phasen, Besatzung, Umriss der Spur jedes Einsatzes — **Ort, Spur, Klinik und Reanimation seit Web 22 nur verschlossen** (vorher alles; bis Web 22.7.0 auch noch der nicht umgezogene Altbestand in den alten Spalten, die mit S11-12 gefallen sind); Adressen und Namen aller Konten; `jobs_token`, `notzugang_geheim`; offene Setz-Links im Mailrumpf (bis 30 Tage, Lage A + Postfach ⇒ Lage E für dieses Konto) |
| **B** | **Datenbank und Dateisystem** — FTP-Zugang, Hosting-Panel, Webspace-Backup des Hosters, das FTP-Geheimnis der Kette | zusätzlich `config.php` (Server-Anteil, Serverschlüssel, SMTP, DB), Sitzungsdateien, `sicherungen/`, Nachweisdateien | Weiterhin verschlossen ohne Passwort oder Zettel; das Offline-Raten wird **nicht** schneller als in A | Wie A; dazu **alle** `edsk1:`-Siegel: Zugänge der Backup-Ziele, Kontopakete, **TOTP-Geheimnisse aller Konten** (der zweite Faktor fällt), Protokollarchive; Komplett-Stände; Mails im Namen der Anlage; der Notzugang der einzigen BetreiberIn (Datei + Datenbankwert + Passwort) |
| **C1** | **Aktiver Serverzugriff** — Code auf dem Webspace ändern: FTP-Zugang, Hoster, kompromittierte Auslieferung | die ausgelieferten Seiten und `crypto.js` | **Offen.** Eine geänderte Zeile in `login.php`, `crypto.js` oder ein `auto_prepend_file` schickt Passwort oder Schlüssel bei der nächsten Anmeldung mit; `ui_krypto_bootstrap()` liefert Hülle, Salz, Runden und Anteil ohnehin in jede Seite. Kein Browserverfahren schützt hiergegen | alles. Die Integritätswache sieht davon **nur** `assets/` und die Anmeldeseite (Abschnitt 3, G5) |
| **C2** | **GitHub-Konto mit Push-Recht** — ein Mitglied der Organisation, ein gestohlenes Token, eine Instanz mit den GitHub-Werkzeugen (sie handelt als BetreiberIn) | Zweige, Tags, Läufe, den Zeiger `produktion` | wie C1, **sobald ausgeliefert** | Staging beliebig beschicken und dessen Geheimnisse lesen; Tags setzen; den Zeiger verschieben (keine Regel auf dem Zweig); einen Produktiv-Lauf mit **eigener Workflow-Datei** zur Freigabe vorlegen — alle Riegel außer dem Freigabeklick stehen in dieser Datei (F-R17-04, -14) |
| **C3** | **Lieferkette** — eine Aktion, ein Gradle-Plugin, ein `pip`/`npm`-Paket, das Connect-IQ-SDK | Code zur Bauzeit, in der Umgebung des Laufs | — | Die 14 Aktionen sind an SHAs gepinnt. **Gradle erbt Keystore und Passwörter des App-Signaturschlüssels** (F-R17-03); `pip`, `npm`, Gradle-Abhängigkeiten und das SDK sind ungepinnt, teils im Job mit Staging-Passwort, TOTP-Geheimnis und `JOBS_TOKEN`; die FTP-Aktion hält das Produktiv-FTP-Passwort und damit B |
| **D** | **Skript im Origin (XSS)** — eine Lücke in einer Ausgabestelle | JavaScript in der Sitzung der NutzerIn | **Offen für dieses Konto:** `sessionStorage.pck` ist der Inhaltsschlüssel als Hex, `edk` der Datenschlüssel; dazu die API mit allen Chiffretexten. Die CSP mit Nonce ist gebaut, steht aber **ab Werk auf Report-Only** (F-R17-23) | alles des angemeldeten Kontos; ein einmal gelesener Inhaltsschlüssel öffnet **alle späteren** Blöcke, weil er nie rotiert (F-R17-10) |
| **E** | **Passwort erbeutet** — Phishing, Schulter, Keylogger, Wiederverwendung, Lage A mit schwachem Passwort | Passwort | **Offen** für Konten ohne Zweitfaktor: Passwort = Anmeldung **und** Datenschlüssel. Mit Zweitfaktor: nur die Anmeldung ist gesperrt, der Datenschlüssel bleibt passwortgebunden (kein PRF, E-SR-28) — wer den Faktor umgeht (B: TOTP-Geheimnis; gemerkter Browser), hat alles | alles |
| **F** | **Endgerät** — Browser-Erweiterung mit Seitenzugriff, Sitzungswiederherstellung, unbeaufsichtigter Rechner, gemerkter Browser | den Tab, das Cookie `EDGERAET` | Offen, solange der Tab lebt: Nach 30 min verwirft der Keyguard nur `pck`; `edk` bleibt bis Navigation oder Tabende; `edkvor` trägt nach jeder Anmeldung kurz das Anmelde-Token (F-R17-24). Firefox schreibt `sessionStorage` für die Wiederherstellung auf die Platte. Eine Exportdatei ohne Passwort ist Klartext | alles, was der Tab zeigt; mit gemerktem Gerät entfällt der Code |
| **G** | **Dritte, die der Browser oder die Uhr anspricht** | Photon (`photon.komoot.io`, Vorgabe **an**): getippte Adresse und Koordinate des Einsatzorts und des Abfahrtorts; Kachelserver OSM, openmaps.fr, OpenTopoMap, Esri: Kartenausschnitt um den Einsatzort; Garmin-Begleit-App: jede Anfrage der Uhr | **Adresse und Koordinate geschützter Felder im Klartext**, während des Tippens, an einen Dritten (F-R17-09) | Einsatzort, Zeitpunkt, IP-Adresse der NutzerIn; die Garmin-Begleit-App sieht Geräteschlüssel und Klartext-Rumpf (Lage M) |
| **H** | **Verlorenes Gerät** — Garmin-Uhr, Handy, Wear-OS-Uhr | Garmin: Geräteschlüssel **und** Klartextpuffer (Spur, Phasen mit Koordinate, **Reanimationsverlauf**) im Gerätespeicher; Handy: Schlüssel im Keystore-Tresor (ohne Entsperrpflicht, ohne StrongBox), Puffer `puffer.db` im Klartext (Punkte, Phasen; keine Reanimation); Wear-OS: nichts | Verschlossen: `ingest.php` ist POST-only, antwortet nur `ok`/`next_seq` | **Hochladen** und eigene Einsätze bis 72 h nach dem ersten Eingang überschreiben, bis das Gerät im Web getrennt ist; lesen, was ungesendet oder geparkt auf dem Gerät liegt — auf der Garmin bis Uhr 3.1 **ohne Frist** (F-R17-28), **seit Uhr 4.0.0 höchstens 30 Tage ab dem Parken** (E-S11-11, -75). Trennen im Web löscht auf der Uhr nichts |
| **I** | **Verwaltung** — ein Konto der Rolle admin oder BetreiberIn, böswillig oder übernommen (E, F) | Konten, Einladungen, Pakete, Freigaben, Komplettsicherung, Protokoll | Verschlossen: kein Passwort-Setzen, der Reset verlangt den Zettel — **im Browser**; serverseitig prüft er nur `pat_key_check` (F-R17-06) | **Admin:** das Paket jedes Kontos an jedes Konto freigeben und den **Klartext aller Einträge, Diensttage und Spuren** lesen — ohne Protokoll, Mail oder frischen Code (F-R17-02); mit `pat_key_check` daraus, Adresswechsel und Setz-Link die Anmeldung eines `user`-Kontos übernehmen (F-R17-06); ein zweites BetreiberIn-Konto sperren, löschen, umadressieren (F-R17-07). **BetreiberIn:** der entsiegelte Komplett-Dump = Lage A, ohne frischen Code (F-R17-08). Beide: Daten zerstören (Konto löschen, Reset ohne Zettel sperrt aus) |
| **J** | **Fremde NutzerIn derselben Anlage** — seit der Selbstregistrierung (P5b) jedes Konto | eine eigene Sitzung | nichts | nichts Fremdes in den gelesenen Wegen (`user_id` in jeder Abfrage, Stichproben); ein vollständiger Durchgang ist Stück 3 und 4 des Reviews |
| **K** | **Support-Rolle** | `pw_reset`, Verifikation, Gerät abschalten an `user`-Konten | nichts | Setz-Link an ein `user`-Konto (mit Protokoll); keine Freigabe, kein Paket |
| **L** | **Plattformanbieter** — Google (Play App Signing, Play-Dienste, Data Layer), Garmin (Connect-IQ-Store, Garmin Connect, Begleit-App) | den App-Signaturschlüssel (Google), die Vermittlung jeder Data-Layer-Nachricht, die Ausführung jeder Uhr-Anfrage (Garmin) | — | **Google kann ein Update signieren, das jede Installation annimmt** — auch die seitengeladene — und damit Tresor und Puffer des Handys erreichen; Garmin Connect hält die Serveradresse (änderbar) und auf dem Alt-Weg den API-Schlüssel (F-R17-12); der Data Layer trägt nur Zeitpunkte und Phasennummern, keine Koordinaten |
| **M** | **Mitlesen auf dem Transportweg** — TLS gebrochen, Proxy mit Aufbruch, die Garmin-Begleit-App | den Rumpf jeder Anfrage | Hülle, Salz, Runden, Anteil aus jeder angemeldeten Seite ⇒ Offline-Raten wie A; Chiffretexte | Geräteschlüssel (statisch je Anfrage, keine Signatur über den Rumpf) ⇒ Lage H ohne Gerät; Spur, Phasen, Reanimation im Klartext — **von der Garmin seit Uhr 4.0.0 und vom Handy seit Android 0.18.0 nur noch als Gerätehülle** (S11-08, -09); und **auch bei intaktem TLS**: Takt und Größe der Uploads verraten Dienstbeginn, Phasen und Einsatzende |
| **N** | **Schadsoftware auf dem Handy** | ohne Root: eine fremde App; mit Root: alles | — | ohne Root nichts (App-Sandbox, `allowBackup=false`, nur Launcher und Data-Layer-Horcher exportiert, Signaturgleichheit); mit Root den Puffer kopieren und den Keystore-Schlüssel **benutzen** (nicht exportieren) |
| **O** | **Der Hoster** — Produktiv (Plesk; laut AVV-Entwurf dataforest) und Staging (lima-city) | alles auf der Platte: `config.php`, Sitzungsdateien, Sicherungen, Datenbank, Zugriffsprotokolle mit Tokens in URLs | Verschlossen ohne Passwort oder Zettel — **bis er Code ändert** (dann C1) | wie B; dazu die Zugriffsprotokolle (Dauer unbekannt) mit `JOBS_TOKEN`, Health-Token und dem ersten Abruf jedes Setz-Links (F-R17-22) |
| **P** | **Die BetreiberIn selbst — und ihr Ausfall** | Freigabe jeder Auslieferung, Merge-Bypass, Schlüsselblatt, Betriebsakte, Hoster- und FTP-Zugänge, GitHub-Umgebungen, Play Console, Connect-IQ-Store | als I | Bei Ausfall: keine Auslieferung, kein Merge, kein Schlüsselnachtrag, kein Notzugang für andere; der Notzugang selbst braucht FTP **und** Datenbank. Die Pflichtstücke des Wiederanlaufs sind laut Rahmenplan 6.1 **offen** (F-R17-05) |

**Die kurze Fassung.** Gegen A, B und O — den klassischen Einbruch — hält
die Verschlüsselung von W1, und sie hält genau so lange, wie das Passwort
stark ist; der Server-Anteil verhindert das Öffnen der Hülle **aus dem
Abzug**, nicht das Raten des Passworts. Vom W2 liegt seit S11 nur noch der
Klartextteil offen — Zeiten, Phasennummern, Besatzung, Umriss der Spur; Ort,
Spur, Klinik und Reanimation hält dieselbe Passwortgrenze wie W1.
Gegen C, D und E hält sie nicht, und kein Verfahren im Browser kann das
ändern; hier zählen der Zugangsschutz zu Server und Repositorium, eine
scharfe CSP und der Zweitfaktor. Zwischen C2 und Produktiv steht **ein
Klick**. Innerhalb der Anwendung liest I den Klartext jedes Kontos, ohne
Spur. H, L und M sind durch die Bauform der Geräte offen, nicht durch
Fehler.

## 3. Zusagen und ihre benannten Grenzen

Jede Zusage steht mit dem Satz daneben, der sie begrenzt. Wer eine Zusage
zitiert, zitiert die Grenze mit.

| Nr. | Zusage | Grenze — und wo sie steht |
|---|---|---|
| G1 | **Ende-zu-Ende-Verschlüsselung genau der Felder in W1.** Klartext dieser Felder geht nie an den Server, in ein Log oder in eine API-Antwort | **Außer an Photon und an den Kachelserver** während des Tippens und Zeigens (Lage G; Schalter unter Betrieb → Servereinstellungen, Vorgabe an). **Außer im Demo-Konto** (G4). **Außer, der Server liefert geänderten Code aus** (C1). Was im Blob liegt, steht in `Technik.md` 4.98; was daneben im Klartext liegt, in `Review-R17.md` 3.2, I-07 — die Liste in `CLAUDE.md` 4 ist kürzer (F-R17-25) |
| G2 | **Der Server kann nichts öffnen.** Er kennt den Anteil, nicht die PBKDF2-Hälfte; `pat_wrap_rc` und `rw_privat` hängen nicht am Anteil, der Verlust des Anteils ist ein Passwort-Reset für alle, kein Datenverlust | Gilt. **Aber die Zusage „ein Datenbankabzug allein reicht nicht mehr für einen Offline-Angriff auf das Passwort" gilt nicht** (F-R17-01): `password_hash` ist bcrypt über ein Token ohne Anteil. Richtig ist: Ein Abzug allein öffnet keine Hülle. Und: **Der Inhaltsschlüssel wird nie rotiert** — wer ihn einmal hatte, liest alles Spätere (F-R17-10). Auf Produktiv ist der Anteil laut Rahmenplan 6.1 noch nicht angelegt (F-R17-05) |
| G3 | **Spur, Phasenkoordinaten, Reanimationsverlauf, Transportziel und Ortshöhe liegen verschlossen** (S11, R78; Web 22.0 bis 23.0, Uhr 4.0.0, Android 0.18.0). Uhr und Handy verschließen vor dem Senden mit dem öffentlichen Teil des Kontopaars (Gerätehülle), der Server nimmt keinen Klartext an, nur der Browser des Kontos öffnet. Seit Web 23 (S11-12) gibt es keine Spalte und keine Tabelle mehr für Klartext-Ortsdaten; die Migration hält an, solange noch einer darin liegt, und nennt die Konten. Eine Sicherung unter Nutzlast 13 nimmt die Anwendung nicht mehr an | *(Bis Web 22.7.0 standen hier zwei Grenzen mehr: der Altbestand im Klartext bis zum Umzug durch das Einmalwerkzeug und Sicherungen von vor S11, E-S11-10. Beide sind mit S11-12 gefallen.)* **(3) Gerätepuffer** auf Garmin und Handy bleiben Klartext bis zum Senden (Lage H, E-R17-09). **(4) Metadaten:** Phasenzeiten und -nummern, Punktzahl und Zeitraum je Stück, Strecke, Anstieg und Takt der Uploads bleiben Klartext (M, R20). **(5) C1:** Der Schlüssel kommt ohne zweiten Kanal vom Server; wer ihn tauscht, bekommt, was Geräte danach verschließen (6.3). **(6) Ansehen:** Karte und Adresssuche fragen weiter Dritte (G) |
| G4 | **Das Demo-Konto ist die einzige benannte Ausnahme** (R25): Schlüsselmaterial in der Fixture, Passwort öffentlich, Rolle `user` fest, Kennung nur aus `app_state` | Es kann nur sich selbst treffen; es trägt nur erfundene Daten. **Seit Web 22.5.0 rechnet der Server beim Zurücksetzen dessen Inhaltsschlüssel aus dem öffentlichen Passwort** (`DEMO_PASSWORT`, E-S11-61), legt Transportziel und Ortshöhe in den Blob und ein Schlüsselpaar an, falls keines da ist (E-S11-58); jede Funktion dafür prüft `demo_ist_demo()`. Für kein anderes Konto hat er einen solchen Weg. Die Demo-Bremse und die Abschaltbarkeit der Anmeldung sind Verfügbarkeitsmaßnahmen, keine Vertraulichkeit |
| G5 | **Die Integritätswache vergleicht das Ausgelieferte gegen den Zeiger `produktion`** | Sie sieht, was öffentlich abrufbar ist: `server/assets/` und die Anmeldeseite — **keinen PHP-Quelltext**, keine `config.php`, keine `.htaccess`, kein `auto_prepend_file`, keine andere Passwortseite (Nr. 263, Nr. 358). Sie hält den häufigsten Angriff (ein untergeschobenes Skript) auf, nicht den stillsten. Ihr Alarm ist ein roter Lauf, dessen Empfänger nicht belegt ist. Ihre Anleitung beschreibt heute eine andere Wache (F-R17-15). Der Zeiger selbst hat keine Regel (F-R17-14) |
| G6 | **Nur die Kette liefert aus**, nach Pflichtfreigabe, nach grünem Stufe-1-Lauf auf demselben Baum, nach dem Backup-Tor | Das Tor der grünen Läufe gilt für `web-v*`, **nicht für `android-v*` und `uhr-v*`** (F-R17-17). Alle Riegel stehen in der Workflow-Datei des auslösenden Refs; ein Probelauf fordert `produktion` von jedem Zweig an (F-R17-04). Das Backup-Tor prüft Frische, nicht Lesbarkeit. Die Freigabe ist ein Klick einer Person auf einen Lauf, dessen Inhalt sie nicht liest |
| G7 | **Die Uhr kennt keine Zugangsdaten** (Wear OS, E-S4-11) | Gilt für die Wear-OS-Uhr: keine Berechtigung, kein Netz, kein Schlüssel, Format ohne Koordinaten. **Die Garmin-Uhr ist das Gegenteil:** Geräteschlüssel im Klartext, Klartextpuffer samt Reanimation, Serveradresse in Garmin Connect änderbar, jede Anfrage durch die Begleit-App (H, L, M) |
| G8 | **Der Data Layer steckt hinter einer Schnittstelle** | Gilt für das Senden; die zwei Horcher (Handy und Uhr) sind Play-Dienste-Klassen und können nicht dahinter (F-R17-27). Ob eine Nachricht bei fehlendem Bluetooth über Google-Server geht, ist nicht belegt — sie trägt keine Koordinaten |
| G9 | **Der Signaturschlüssel der Apps liegt außerhalb des Repositoriums** | Er liegt in der Umgebung `produktion` **und bei Google** (Play App Signing, seit 26.09.2026). Google kann ein Update signieren, das jede Installation annimmt. In der Kette erbt Gradle ihn (F-R17-03). Er ist nicht rücksetzbar: Verlust heißt „eine andere App" |
| G10 | **Speicherdauern:** Papierkorb 90 Tage, Protokoll 365 (Verwaltung) und 30 Tage, Mailwarteschlange 30 Tage, Sitzungen 30 min, Sicherheitsereignisse 30 Tage, Selbstlöschung mit 30 Tagen Karenz, zwei Konto-Pakete, zwei Komplett-Stände | **Auswärtige Ziele bewahren unbegrenzt**, solange keine Löschregel gesetzt ist; ein gelöschtes Konto lebt in Komplett-Ständen, Kontopaketen (wenn nicht „mit Sicherungen" gelöscht) und auf Zielen weiter, seine Adresse in einer Protokollzeile (≥ 365 Tage) und bis 30 Tage in der Mailwarteschlange (`mail_konto_entfernen()` hat keinen Aufrufer, F-R17-21). Auf Produktiv läuft kein Cron — ohne Besucher räumt nichts. Die Zugriffsprotokolle der Hoster kennt niemand |
| G11 | **Keine Telemetrie** (R36): Betriebszahlen nur aus vorhandenen Spalten; einzige Ausnahme die Gerätekennung beim Koppeln (Art, Modell, Firmware — kein `uniqueIdentifier`, keine IMEI) | Gilt. Was trotzdem nach außen geht: Photon und Kacheln (G), die CSP-Berichte an die eigene Anlage, die Metadaten des Uploads (M) |
| G12 | **Geheimnisse der Kette liegen in GitHub-Umgebungen** und werden nur nach Freigabe herausgegeben | `pull_request` aus einem Fork bekommt keine; ein PR **aus dem Repositorium** fährt seine eigene Workflow-Datei mit den Repositoriumsgeheimnissen und kann `staging` anfordern. Die Staging-Zugänge liegen auch in der Arbeitsumgebung der Instanzen. Wer freigeben darf und ob die Organisation den Zweitfaktor erzwingt, ist **nicht belegt** (Prüfdokument 1) |
| G13 | **Eine Migration, die Rechte einführt, ist ohne diese Rechte ausführbar** (`Technik.md`, Regel aus S8; die Support-Rolle hat sie erfüllt) | Gilt für die bisherigen; jede neue Rollenmigration prüft den Erstlauf mit |

## 4. Bausteine — was wogegen hält

Je Baustein: was er tut (kurz; die Fundstellen stehen in `Review-R17.md`
3.2 unter der I-Nummer), gegen welche Lagen er hält, gegen welche nicht,
und ob eine Probe ihn misst. **„Hält" heißt: Die Lage erreicht damit den
Wert nicht, den der Baustein schützt** — nicht, dass sie nichts erreicht.

### 4.1 Verschlüsselung und Schlüssel

| Baustein | Was er tut | Hält gegen | Hält nicht gegen | Probe |
|---|---|---|---|---|
| **Passwortableitung** (I-01) | PBKDF2-HMAC-SHA256, 600 000 Runden, Salz je Konto; Hälfte bleibt im Browser, Anmelde-Token geht zum Server | A, B, O: das Raten kostet je Kandidat eine Ableitung — rund 20 000 Kandidaten je Sekunde und Grafikkarte bei 320 000 Runden (Krypto-Review 3.3), die Hälfte davon heute; ein Wörterbuchpasswort fällt in Stunden, zehn zufällige Zeichen nie | E (Passwort bekannt); M und F (die Hälfte liegt im Browser) | `proben/anteil`, `proben/raten` |
| **Server-Anteil** (I-02, S10) | HMAC je Konto aus `kdf_anteil` (`config.php`), per HKDF in den Datenschlüssel; an jede angemeldete Sitzung und jeden gültigen Reset-Link ausgeliefert | A: die Hülle geht aus dem Abzug allein nicht auf | A beim **Raten** (Token ohne Anteil, F-R17-01); B, O (Anteil in `config.php`); E (die Anmeldung liefert ihn); A + Postfach (der Reset-Link liefert ihn) | `proben/anteil` |
| **Anmelde-Token und bcrypt** (I-03) | bcrypt über das Token; Blindvergleich gegen einen festen Wert, Mindestdauer 0,35 s | Online-Raten (mit dem Ratenschutz); Zeitseitenkanal | A: Offline-Raten gegen `password_hash` (ein bcrypt je Kandidat zusätzlich) | keine |
| **Inhaltsschlüssel und zwei Hüllen** (I-04) | CK 256 Bit Zufall; `pat_wrap_pw` unter dem Datenschlüssel (`edka1:`), `pat_wrap_rc` unter SHA-256 des 98-Bit-Codes (`edk1:`, nie am Anteil); `pat_blob` AES-256-GCM | A, B, O ohne Passwort oder Zettel; ein Passwortwechsel kostet nur die Hülle | D, F: ein einmal gelesener CK gilt **dauerhaft** (F-R17-10); wer Passwort und Sitzung hat, ersetzt `pat_wrap_rc` ohne Mail | `proben/anteil`, `proben/rueckweg` |
| **Schlüssel zur Laufzeit** (I-05) | `sessionStorage` (Hex), Keyguard verwirft `pck` nach 30 min | J (Bindung an das Konto im Tab) | D, F: Hex im `sessionStorage`, `edk` ohne Frist, `edkvor` mit Token (F-R17-24); Firefox-Sitzungswiederherstellung | `proben/frist`, `proben/abmelden` |
| **Feldkatalog mit `store => 'pat'`** (I-06) | Ein Blobfeld fällt von selbst aus jedem `SELECT`/`INSERT`/`UPDATE`; Schloss und Kleinzeile gegen eine Sollliste | den Bedienfehler „neues Feld im Klartext" (Riegel `kennzeichnung`) | den Bedienfehler **im Freitext**: `bw_info` („Namen / Infos"), `other_ema`, `days.notes` nehmen jeden Text im Klartext an — die Kleinzeile sagt es, mehr nicht | `quelltext/kennzeichnung` |
| **Serverschlüssel** (I-15) | `edsk1:` AES-GCM mit Zweck; versiegelt Ziel-Zugänge, Kontopakete, TOTP-Geheimnisse, Kontoschlüssel (seit S11-03), Protokollarchive, Komplettbackup; Wechsel als Vorgang | A: nichts davon geht aus dem Abzug auf; ein gestohlenes Komplettbackup ohne `config.php` ist nichts | B, O (Schlüssel in `config.php`) — **einschließlich des zweiten Faktors aller Konten** (F-R17-26) | `proben/schluesselwechsel`, `proben/komplett` |
| **Schlüsselpaar des Kontos** (S11-03, E-S11-14) | ECDH auf P-256 je Konto, im Browser erzeugt, nur mit Passwortnachweis abgelegt; der private Teil unter dem Inhaltsschlüssel (`edk1:`, nie am Anteil), der öffentliche **versiegelt** (`kontopaar|<id>`) und nur mit gültigem Siegel an Seite und Gerät (`kontoschluessel`); beim Entsperren gleicht der Browser beide Teile ab | A **mit Schreibrecht** auf die Datenbank: einen getauschten öffentlichen Teil gibt der Server nicht aus (Siegel), einen getauschten privaten meldet der Abgleich; eine übernommene Sitzung ohne Passwort legt kein Paar an | B, O, C1: Wer den Serverschlüssel hat, versiegelt einen eigenen Schlüssel, und wer den Server hat, tauscht `crypto.js` gleich mit (F-S11-04); der Abgleich **meldet**, er verhindert nicht — ein Gerät, das vorher koppelte, hat den getauschten Schlüssel schon | `proben/kontopaar`, `proben/kopplung`, `proben/huelle` |
| **Gerätehülle** (S11-02 bis -10, E-S11-12, -31) | Fassung 1: ECDH P-256 mit einem flüchtigen Paar je Hülle, HKDF-SHA256, AES-256-CBC und HMAC-SHA256 über Kopf, Chiffretext und Bindung (Art, `client_ref`, Platz); eine Umsetzung je Ort — `crypto.js`, `Huelle.mc`, `Geraetehuelle.kt`, `geraetehuelle_lib.php` (nur Proben und der Demo-Reset; bis Web 22.7.0 dazu der direkte Rückweg der Verwaltung) — gegen dieselben Vektoren (`tools/proben/huelle/`). Spurstücke, Einsatzhülle (Phasenorte, Reanimation); Ziel und Höhe im `pat_blob` | A, B, O, M, I lesend für Ort, Spur, Klinik und Reanimation: Der Server hat keinen privaten Teil. Verschieben oder Umetikettieren eines Stücks: Die Bindung schlägt fehl, der Browser meldet es an den Reiter System | C1 (getauschter Schlüssel, 6.3); H (Puffer vor dem Verschließen); die Klartextzahlen daneben (G3 (4)); ein Gerät prüft nicht, ob der Schlüssel echt ist — die Garmin nicht einmal, ob er auf der Kurve liegt (F-S11-85) | `proben/huelle`, `proben/ortsdaten`, `proben/wartungslauf`, `uhr-rundlauf`, `GeraetehuelleTest` (bis Web 22.7.0 dazu `proben/ortsumzug`) |
| **Konto-Backup `.edbak`** (I-17) | Browser entschlüsselt, versiegelt Klartext unter dem Backup-Passwort (PBKDF2, GCM, AAD je Teil) | jeden, der nur die Datei hat — so stark wie das Backup-Passwort | F: die Datei liegt auf dem Gerät der NutzerIn | `proben/container` |

### 4.2 Zugang

| Baustein | Was er tut | Hält gegen | Hält nicht gegen | Probe |
|---|---|---|---|---|
| **Ratenschutz** (I-08, I-10) | Sperre vor bcrypt je Konto und Adresse, Leiter, globale Verlangsamung; Rechenaufgabe und Stempel vor der Registrierung; Antwortgleichheit an den vier unangemeldeten Endpunkten | Online-Raten, Massenregistrierung, Zeitseitenkanal, Kontoerkennung über die Antwort | ein Pseudosalz ist je Adresse stabil, ein echtes ändert sich beim Reset — wer über Wochen fragt, erkennt ein Konto; Kontosperre durch Fremde ist in Kauf genommen | `proben/raten`, `proben/sitzung` |
| **Sitzung und Bindung** (I-09) | Cookie `Secure`/`HttpOnly`/`Strict`, Regenerierung, Bindungscookie `EDBIND` (in der Datei nur der Hash), Ablage 0700, Rolle je Anfrage aus der Zeile | B lesend (eine gelesene Sitzungsdatei ist ohne Cookie nichts); Sitzungsfixierung | D, F (das Cookie ist im Browser) | `proben/sitzung`, `quelltext/sitzungshaertung` |
| **Zweitfaktor** (I-11) | TOTP, Passkeys (ohne PRF), zehn Codes, Gerät merken, frischer Code für acht Griffe, Rückweg mit `rw_privat`, Notzugang | E für die **Anmeldung** | E für die **Daten** (passwortgebunden); B und O (TOTP-Geheimnis unter dem Serverschlüssel); F mit gemerktem Browser; die Griffe **ohne** frischen Code: Komplett-Download, Freigabe, Adresswechsel, Setz-Link (F-R17-02, -06, -08) | `proben/zweitfaktor`, `proben/passkey`, `proben/rueckweg` |
| **Reset per Mail** (I-13) | Token als Hash, kurze Frist, eigene Cookie-Sitzung; die Seite liefert Zettel-Hülle und Anteil; der Browser prüft den Zettel | Postfach-Angreifer ohne `pat_key_check` | I oder A + Postfach **mit** `pat_key_check`: Anmeldung übernehmbar, Block bleibt zu (F-R17-06); der Link liefert den Anteil ohne Passwort | `proben/anteil`, `proben/mail` |
| **Rollen und Kontentrennung** (I-14, I-24) | user ⊂ support ⊂ admin ⊂ betreiberin; `user_id` in jeder Abfrage; Berechtigungsmatrix als Probe | J (in den gelesenen Wegen); K | I: Freigabe an jedes Konto (F-R17-02), Admin gegen BetreiberIn-Konten (F-R17-07); die Matrix misst nur `{ziel}` = `user` | `proben/rollen` |
| **Protokoll** (I-25) | Urheber, Betroffener, Text; Verwaltung 365 Tage; keine geschützten Felder, Tokens oder Passwörter | das stille Handeln einer Verwaltung — **außer bei der Freigabe** (kein Eintrag, F-R17-02) | A, B, O lesen es; niemand kann es löschen, auch I nicht (gewollt) | `proben/protokoll` |

### 4.3 Geräte und Transport

| Baustein | Was er tut | Hält gegen | Hält nicht gegen | Probe |
|---|---|---|---|---|
| **Kopplung** (I-23, `Technik.md` 4.99b) | Das Gerät zeigt den Code, ein Mensch tippt ihn, das Gerät bestätigt; schwebende Zugangsdaten statt schwebender Geräte; Geräteschlüssel 192 Bit als SHA-256; Kopplungsmail, Plakette „neu" | fremdes Gerät im eigenen Konto (Bestätigungsseite), eigenes Gerät im fremden Konto (Rückfrage am Gerät), Code-Raum, Verstopfen, Zeitseitenkanal | Social Engineering; körperlichen Zugriff („jemand drückt selbst Ja"); H2 (Uhr in fremder Hand ohne Rückstand: trennen und neu koppeln) | `proben/kopplung`, `proben/geraete` |
| **Ingest und Ersetzfenster** (I-23, 4.99a2) | POST-only, Antwort nur `ok`/`next_seq`; Blindvergleich; eigene Einsätze nur 72 h ab erstem Eingang veränderbar | H beim **Lesen**; H beim Überschreiben alter Einsätze | H beim Hochladen und Überschreiben junger eigener Einsätze bis zum Trennen | `proben/ingest` |
| **Garmin-Uhr** (I-31 bis I-34) | Geräteschlüssel in `Storage "cred"`, Puffer in `Storage`, Adresse aus den App-Einstellungen, Anfragen über die Begleit-App; **seit Uhr 4.0.0** Gerätehülle (`Huelle.mc`) mit dem Kontoschlüssel aus der Kopplung (`Storage "kschl"`) und einem Selbsttest vor dem ersten Senden | — | H (Schlüssel und Klartextpuffer samt Reanimation; geparkte Pakete seit 4.0.0 mit Frist 30 Tage); L und M (Begleit-App, Garmin Connect mit änderbarer Adresse und Alt-Weg-Schlüssel, F-R17-12); `http://` bleibt `http://` (F-R17-11) | keine (der Uhr-Prüfstand übersetzt und fotografiert) |
| **Handy** (I-35 bis I-37) | Tresor AES-GCM mit Keystore-Schlüssel (seit 0.18.0 dazu der öffentliche Kontoschlüssel), Gerätehülle vor dem Upload (`Geraetehuelle.kt`, prüft die Kurvengleichung selbst), `puffer.db` Klartext, `allowBackup=false`, Release nur `https`, kein Pinning (Nr. 143), acht Rechte, Logs ohne Koordinaten und Schlüssel, Abgewiesene 30 Tage | L (Google-Sicherung), N ohne Root, G (System-CAs) | H (Puffer Klartext; Keystore-Schlüssel ohne Entsperrpflicht benutzbar), N mit Root, M mit System-CA (kein Pinning, bewusst) | 8 + 4 Tresor-Tests, `ServeradresseTest`, `PufferMigrationTest` |
| **Wear-OS-Uhr und Data Layer** (I-38, I-39) | keine Berechtigung, kein Netz, kein Schlüssel; Format ohne Koordinaten; Handy prüft Absender und Zeitfenster | H (nichts preiszugeben); N (Signaturgleichheit) | die Uhr prüft beim Empfang keinen Absender (nur unter gleicher Signatur erreichbar); L vermittelt jede Nachricht | `NachrichtenformatTest`, `UhrannahmeTest` |
| **Transport** (I-41) | TLS; statischer Geräteschlüssel in zwei Kopfzeilen; Idempotenz über `client_ref` + `seq` | G mit intaktem TLS (Inhalt) | M (Schlüssel und Rumpf; keine Signatur, kein Zeitstempel); Metadaten auch bei TLS | `proben/ingest` |
| **Signatur und Verteilung** (I-40) | ein Schlüssel für Handy und Wear, `apksigner` nach dem Bau, Zertifikat gegen Sollwert, Ablage in `apk/` (403 ohne Anmeldung), Android prüft Updates auf Signaturgleichheit | ein fremdes Update auf einem Gerät (Android) | C3 (Gradle erbt den Schlüssel, F-R17-03); L (Google hält ihn); Erstinstallation mit getauschter APK **und** Prüfsumme bei Server-Schreibzugriff (C1) | `appbau.sh --selbstprobe` (Zertifikat, nicht Umgebung) |

### 4.4 Kette, Betrieb, Sicherung

| Baustein | Was er tut | Hält gegen | Hält nicht gegen | Probe |
|---|---|---|---|---|
| **Pflichtfreigabe** (I-42) | Produktiv-Geheimnisse nur nach Freigabe der Umgebung `produktion` | Fremde ohne Schreibrecht; versehentliche Auslieferung | C2 (eigene Workflow-Datei, ein Klick, F-R17-04); P (ohne sie keine Auslieferung, auch kein Hotfix) | — |
| **Tore des Produktionslaufs** (I-43) | grüner Stufe-1-Lauf auf dem Baum, Staging grün, Abstammung vom Zeiger, Backup-Tor, Tag = Fassung, Nachprüfung, Wartung bei Migration | ungeprüfte oder falsch nummerierte Stände; Deploy ohne frischen Stand | C2 (Riegel in der Datei des Zweigs); **App-Tags** (kein Tor, F-R17-17); ein Stand, der sich nicht öffnen lässt | Selbstproben in Stufe 1 |
| **Pinning und Ausnahmeliste** (I-44, I-45) | 14 Aktionen an SHAs; acht Pfade bleiben auf dem Server | C3 auf Ebene der Aktionen; Löschen von Serverzustand | C3 in `run:` (pip, npm, Gradle, SDK, F-R17-20); die Liste selbst misst kein Werkzeug (F-R17-16) | **keine** |
| **Zweigschutz und Deny-Liste** (I-48) | PR-Pflicht mit striktem Stufe-1-Tor; Instanzen dürfen nicht mergen | Push auf `main` an der BetreiberIn vorbei; Merge durch eine Instanz | Tags (kein Ruleset); den Zeiger `produktion` (keine Regel, F-R17-14); `push_files`, `run_workflow`, `cancel_workflow_run` über die Werkzeuge | gemessen 21.09.2026 |
| **Integritätswache** (I-49) | HTTPS-Vergleich von `assets/` und der Anmeldeseite gegen den Zeiger, täglich und nach jeder Auslieferung | B, O, C1 beim Tausch eines Skripts unter `assets/` oder auf der Anmeldeseite | C1 im PHP-Code, `.user.ini`, andere Passwortseiten; C2 am Zeiger; Auslieferung nur an Nicht-GitHub-Adressen | `wache.py --selbstprobe` |
| **Komplettbackup und Ziele** (I-19, I-20) | alle Tabellen versiegelt (Serverschlüssel oder Passphrase), zwei Stände, auswärtige Ziele per SFTP (Hostschlüssel) oder FTPS (ohne Zertifikatsprüfung) | Verlust des Webspace; A, B lesend am Ziel ohne `config.php` | I (BetreiberIn-Download ohne frischen Code = Lage A, F-R17-08); FTPS-MitM (Zugangsdaten); unbegrenzte Aufbewahrung drüben; Klartext-Dump im Bauordner während des Baus; eine Klartext-`.sql.gz` in `eingang/` auf frischem Webspace (F-R17-19) | `proben/komplett`, `proben/versand` |
| **Kontopakete und Freigabe** (I-18) | Pakete unter dem Serverschlüssel; Freigabe für ein neu aufgesetztes Konto. Seit Web 22.5.0 tragen sie Einsatzhülle und Spurstücke, wie sie liegen, und `kp_privat` (unter dem Inhaltsschlüssel, wie in der Datenbank): Die Freigabe öffnet damit im Browser und verschließt für das neue Paar; der direkte Weg verschließt den Klartext älterer Pakete auf dem Server (E-S11-57, -60) | A, B lesend | I (jedes Konto an jedes, Klartext, ohne Spur, F-R17-02) | `proben/freigabe` |
| **Wiederanlaufpaket und Schlüsselblatt** (I-50) | vier Stücke, Blatt in zwei Ausdrucken, Rückfrage nach jeder Rotation | Verlust der Plattform | P: laut Rahmenplan 6.1 nicht erledigt (F-R17-05); Ablageort nur in der Betriebsakte | keine |
| **Notzugang** (I-12) | Datei + Datenbankwert + Passwort, eine Antwort, gleiche Dauer | A allein, B lesend | C1 (Schreibzugriff); für B oder O mit Passwort ein Zweitfaktor-Umweg (E-SR-24) | `proben/zweitfaktor` Teil 8 |
| **Staging getrennt** (I-53) | anderer Hoster, eigene Schlüssel, keine Echtdaten | Staging-Kompromittierung reicht nicht nach Produktiv | Passwort-Wiederverwendung (nicht prüfbar); Produktiv-Verhalten ist dort nicht belegt (Punktdateien, F-R17-18) | Stufe 2 |
| **Mailweg** (I-51) | Warteschlange, Rumpf nach Zustellung geleert, keine geschützten Felder | — | A, O (offene Setz-Links im Rumpf, erster Abruf im Zugriffslog, F-R17-22) | `proben/mail` |
| **CSP und Kopfzeilen** (I-26) | Nonce-CSP, `frame-ancestors`, HSTS, `nosniff`, Referrer-Policy | D — **wenn scharf** | D, solange Report-Only (Vorgabe); HSTS 1 Tag (F-R17-23) | `proben/csp-browser` |

## 5. Restrisiken — benannt, mit Zuordnung

Was nach Abschnitt 4 offen bleibt, steht hier mit einer von drei
Zuordnungen: **hingenommen** (und warum), **Befund** (in der Freigaberunde
von 12b zu entscheiden, F-R17-Nummer), **S11** (wird dort geschlossen oder
dort entschieden).

| Nr. | Restrisiko | Zuordnung |
|---|---|---|
| R1 | Die Vertraulichkeit von W1 ist so stark wie das Passwort; der Server kann die Passwortqualität nur im Browser prüfen (`pwquality.js`) | hingenommen — Bauform; Handbuch und Einweisung sagen es. Argon2id ist **entschieden: nein** (E-R17-07, Abschnitt 6.5) |
| R2 | Offline-Raten gegen `password_hash` aus dem Abzug; die Zusage sagt das Gegenteil | **Befund** F-R17-01 — Freigaberunde 12b (E-R17-06: kein Sofortpaket); bis dahin gilt bei Abweichung dieses Modell (`CLAUDE.md` 4) |
| R3 | Der Einsatzort ist aus W2 rekonstruierbar — Datenbank, Backup, Paket, Upload, Gerät | **Mit S11 geschlossen** (G3; seit Web 23 auch für den Altbestand, S11-12); offen bleibt er im Gerätepuffer bis zum Senden (R13, R14) und über die Zeiten für jemanden mit Zusatzwissen (R20) |
| R4 | Geschützte Ortsangaben gehen an Photon und Kacheldienste | hingenommen — Schalter vorhanden, Handbuch 12.5 sagt es; die Zusage-Texte nennen es nicht: **Befund** F-R17-09 |
| R5 | Ein Admin liest über die Freigabe den Klartext jedes Kontos ohne Spur; mit `pat_key_check` übernimmt er die Anmeldung eines `user`-Kontos; ein zweites BetreiberIn-Konto kann er sperren oder umadressieren | **Befund** F-R17-02, F-R17-06 — Freigaberunde 12b (E-R17-06); der Riegel gegen BetreiberIn-Konten ist **entschieden** (E-R17-11, Nr. 371) |
| R6 | Die BetreiberIn hält den Klartext-Dump ohne frischen Code | **Befund** F-R17-08 |
| R7 | Ein einmal abgeflossener Inhaltsschlüssel gilt für alle späteren Blöcke; ein Passwortwechsel heilt nichts | **Befund** F-R17-10 (Mail bei `schluessel_erneuern`); eine Umschlüsselung des Bestands ist **S11**-Material |
| R8 | XSS ist ohne scharfe CSP ungebremst; der Schlüssel liegt als Hex im `sessionStorage` | **Befund** F-R17-23 (Stand auf Produktiv); der nicht-extrahierbare `CryptoKey` ist **entschieden: Paket in 12b nach der scharfen CSP** (E-R17-08, Nr. 370) |
| R9 | Der Zweitfaktor schützt die Anmeldung, nicht den Datenschlüssel; sein Geheimnis liegt unter dem Serverschlüssel | hingenommen — E-SR-28 (kein PRF); Lage B hat ohnehin `config.php` |
| R10 | Zwischen Push-Recht und Produktiv steht ein Klick; der Zeiger ist ungeschützt; App-Tags haben kein Tor; die Lieferkette in `run:` ist ungepinnt | **Befund** F-R17-04, -14, -17, -20 — Zuarbeiten der BetreiberIn (Zweigbeschränkung, Ruleset) und ein Kettenpaket |
| R11 | Gradle sieht den App-Signaturschlüssel; Google hält ihn ebenfalls | **Befund** F-R17-03 (Kette); Google: hingenommen — R65, Play App Signing ist die Bedingung des Stores |
| R12 | Die Wache sieht keinen PHP-Code; ihre Anleitung ist falsch; ihr Alarmweg ist unbelegt | Sichtbereich: **offen für 12b** (Nr. 263, E-R17-12 — Stück 10 liest die Wache und entscheidet); **Befund** F-R17-15 für Anleitung und Alarmweg |
| R13 | Garmin-Uhr: Schlüssel und Klartextpuffer samt Reanimation ohne Frist; Adresse in Garmin Connect; Transport durch die Begleit-App; `http://` möglich | hingenommen für Speicher und Begleit-App (Plattform); **S11 hat den Zweck Server** (E-R17-09) und gibt geparkten Paketen eine Frist; **Befund** F-R17-11, -12, -28 für Adresse, Alt-Weg, Frist |
| R14 | Handy: Puffer im Klartext; Keystore-Schlüssel ohne Entsperrpflicht | hingenommen — Dateiverschlüsselung des Geräts und Sandbox; **entschieden:** S11 verschlüsselt vor dem Upload, nicht beim Schreiben (E-R17-09); „beim Schreiben" wäre ein eigener späterer Schritt |
| R15 | Die Pflichtstücke des Wiederanlaufs sind offen; kein Stand je geöffnet; keine Probe-Wiederherstellung | **Befund** F-R17-05 — Zuarbeit, kein Code |
| R16 | Alles hängt an einer Person; bei ihrem Ausfall steht die Anlage still | hingenommen bis zur Betriebsübergabe (Rahmenplan, zweiter Admin in der Betriebsakte offen) |
| R17 | Tokens in URLs und Mailrümpfen landen in Hoster-Logs und Komplett-Ständen; die Logdauer der Hoster ist unbekannt | **Befund** F-R17-22; Logdauer in die Betriebsakte |
| R18 | Gelöschte Konten und Einsätze leben auf Zielen und in Ständen weiter; auswärts unbegrenzt | hingenommen — Löschregel je Ziel vorhanden; ins Löschkonzept der Rechtstexte |
| R19 | Der Hoster kann Code ändern und das nächste Passwort mitlesen | hingenommen — Bauform jeder Webanwendung; AVV und Hosterwahl sind die Maßnahme |
| R20 | Metadaten des Uploads (Takt, Größe) verraten Dienst und Phasen auch bei TLS | hingenommen — benannt, nicht zu schließen ohne Füllverkehr |

## 6. Entwurfsfragen für S11 — und die Antworten aus Nr. 146

S11 (Schritt 12a, R78, Skizze SP-9) verschlüsselt Spur, Phasenkoordinaten,
Reanimationsverlauf und Transportziel mit einem Konto-Schlüsselpaar: Der
öffentliche Teil geht aufs Gerät, das Gerät verschlüsselt, der Browser
entschlüsselt. Dieses Modell sagt dem Konzept, was es vorfindet und was es
entscheiden muss. **Die BetreiberIn hat am 11.10.2026 entschieden**
(`Review-R17.md` 2.1, E-R17-07 bis -12); die Entscheidung steht je Frage
fett, die Lage davor bleibt als Begründung.

**Umgesetzt in S11** (Web 22.0.0 bis 22.7.0, Uhr 4.0.0, Android 0.18.0;
`docs/konzepte/Konzept-S11-Ortsdaten-Weg-B.md`). Die Abschnitte 6.1 bis 6.4
bleiben als Begründung stehen; was daraus geworden ist, steht in G3 und im
Baustein Gerätehülle (4.1). Kurz: 6.1 wie entschieden (die Zählung je Klinik
rechnet der Browser); 6.2 Zweck Server, Frist für geparkte Pakete auf der
Garmin 30 Tage; 6.3 der Schlüssel über `bestaetigen` und `status`, ohne
zweiten Kanal; 6.4 die Garmin kann es, mit Selbsttest der Byteordnung.

**6.1 Was Klartext bleiben darf.** Serverseitig gerechnet wird mit den
Koordinaten nichts (`Konzept-V1-Ortsdaten.md` 3.1); der Server braucht
**Zeiten** (Reihenfolge, Phasenzuordnung, Nachlieferung, Statistik je
Jahr), **`seq`**, **Kennungen** (`client_ref`, `device_id`), **Strecke und
Anstieg** (von der Uhr gerechnet, nur Zahlen) und die **Zählung** je Klinik
und je Reanimation, wenn die Statistik bleiben soll (F-SP-7). Klartext
bleiben müssen damit: Zeiten, `seq`, Kennungen, Diensttag- und
Standortdaten (Stammdaten), Besatzung und Einsatzmittel (nicht Teil von
R78). **Entschieden (E-R17-10):** Zeiten, `seq`, Kennungen, Strecke, Anstieg,
Stammdaten, Besatzung und Einsatzmittel bleiben Klartext; Spur,
Phasenkoordinaten, Reanimationsverlauf und das Transportziel werden
verschlüsselt, und die Zählung je Klinik wandert in den Browser (F-SP-7).

**6.2 Was der Schlüssel aufs Gerät erreicht — und was nicht (F-R17-13).**
SP-9 verschlüsselt **vor dem Upload**. Das schließt A, B, O, M und I für
W2 — nicht H: Der Puffer auf der Garmin (Spur, Phasen, Reanimation) und auf
dem Handy (`puffer.db`) bleibt Klartext, bis das Paket bestätigt ist, auf
der Garmin ohne Frist. SP-9 Punkt 6 („ein Verlust kostet das Trennen,
nicht mehr") gilt nur, wenn **beim Schreiben** verschlüsselt wird — das
baut `Track.readPoints()` (Polylinie, Teil-Chunk) und
`Puffer.punktNaheZeit()` (Phasenkoordinate aus Klartextpunkten) um und
verlangt, dass das Gerät nichts von dem zurücklesen kann, was es
geschrieben hat. **Entschieden (E-R17-09): Zweck Server.** S11 schließt Server und
Transport und benennt H als Restrisiko mit Frist für geparkte Pakete
(F-R17-28); „beim Schreiben" ist ein eigener, späterer Schritt, weil er die
Anzeige der Uhr umbaut.

**6.3 Wie der Schlüssel aufs Gerät kommt.** Heute trägt keine
Kopplungsantwort einen Schlüssel, und beide Geräte schließen die Kopplung
auch über `status` = `gekoppelt` ab, ohne die `bestaetigen`-Antwort zu
lesen. Der öffentliche Teil muss deshalb in **drei** Antworten stehen:
`bestaetigen`, `status`/`gekoppelt` und — für Bestandsgeräte — `ingest`.
*(Nachtrag S11: `ingest` entfiel, E-S11-09, F-S11-12; seit S11-03 tragen
`bestaetigen` und `status` ihn. Eine Garmin ohne Schlüssel holt ihn seit Uhr
4.0.0 mit ihren Zugangsdaten über `status` nach — dieselbe Herkunft, dieselbe
Prüfung, kein neuer Weg, E-S11-79.)*
Er kommt über TLS vom Server **ohne zweiten Kanal**: Wer den Server
beschreibt (C1), tauscht ihn, und die Geräte verschlüsseln für ihn. Das ist
dieselbe Grenze wie bei `crypto.js` und kein Grund gegen Weg B — aber ein
Satz für die Zusage. Auf der Garmin liegt er in `Storage` neben `cred`, ohne
Integritätsschutz; ein Prüfwert (Hash des öffentlichen Teils, in der
Kopplungsmail genannt) wäre der eine zweite Kanal, den es gibt.

**6.4 Kann die Garmin es?** *(Beantwortet: ja — Krypto-Probe S11-02 auf drei
Uhren im Simulator und einer echten Uhr, seit Uhr 4.0.0 in der App, mit
Selbsttest der Byteordnung, E-S11-30, -78; offen bleibt der Speicher der
kleinsten Zielgeräte beim Verschließen von 500 Punkten, Prüfdokument S11.)*
Bis S11-08 stand im Code keine Krypto; SP-9 nennt
`Toybox.Cryptography` ab API 3.0.0 (ECDH P-256, AES-256-CBC, HMAC-SHA256,
kein GCM). **Im Container ist das nicht prüfbar** (kein SDK); das Konzept
S11 beginnt mit einer Probe-App, die genau diese Aufrufe enthält, durch
Stufe I des Uhr-Prüfstands für alle 99 Geräte übersetzt (Prüfdokument 1).
Dazu die 8-KB-Grenze je Storage-Wert und der kleine App-Speicher der
ältesten Zielgeräte (128 kB): Chiffretext mit `eph_pub`, `iv`, `mac` und Base64 ist
größer als 200 Rohpunkte. Das Handy kann es mit Bordmitteln (JCA, ECDH,
GCM).

**6.5 Die zwei Fragen aus Nr. 146.**

| Frage | Lage nach S10 | Entschieden (11.10.2026) |
|---|---|---|
| **Argon2id statt PBKDF2** | Der Gewinn war gedacht gegen Lage A beim Raten. Nach S10 kostet das Raten je Kandidat PBKDF2 plus bcrypt und bringt ohne Anmeldung keinen Datenschlüssel; **mit F-R17-01 (Pfeffer vor bcrypt) braucht das Raten `config.php`** — dann verteidigte Argon2id nur noch gegen Lage B, die ohnehin alles hat. Preis: WASM-Fremdbestandteil, 64 MB je Ableitung auf alten Telefonen, dritter Ableitungsweg | **Nein, nicht weiterverfolgen** (E-R17-07); F-R17-01 (b) geht in die Freigaberunde 12b. Nr. 146 erledigt |
| **Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`** | Ein XSS könnte weiter entschlüsseln, den Schlüssel aber nicht mitnehmen; nach dem Schließen der Lücke ist der Angreifer draußen — und **das ist mit F-R17-10 wichtiger als gedacht**, weil ein mitgenommener CK dauerhaft gilt. Preis: IndexedDB statt `sessionStorage`, anderes Lebensdauermodell, Umbau aller `getContentKey`-Aufrufer | **Ja, als Paket in 12b** (E-R17-08, Backlog Nr. 370; Fable-Schritt), nach der scharfen CSP (F-R17-23) — die CSP ist die erste Linie, der `CryptoKey` die zweite |

**6.6 Nr. 263 — die Grenze der Integritätswache.** Sie bleibt benannt (G5).
Eine Abhilfe hieße, dem Server eine Schnittstelle zum Hashen der eigenen
Quelldateien zu geben — ein neuer Angriffsweg für ein Problem, das der
Vergleich nur teilweise löst. **Entschieden (E-R17-12): offen für 12b** — Stück 10 liest die Wache
und entscheidet über die Abhilfe; Fable hatte `nicht umsetzen` empfohlen.
Unabhängig davon stehen F-R17-15 (Anleitung, Alarmweg) und F-R17-18
(lesende Probe gegen Produktiv) als die zwei Dinge, die die Wache heute
wirklich besser machen.
