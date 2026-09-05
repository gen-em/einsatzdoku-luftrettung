# Review — Kryptographie und Sicherheit: Kommt ein Angreifer an Patientendaten?

**Anlass:** Frage des Auftraggebers vom 05.09.2026: *„Ist das sicher? Gibt es
im Hacker-Fall tatsächlich keine Möglichkeit, an PatientInnendaten zu
kommen?"* — **Auftrag:** Lesen und bewerten, nichts ändern. Dieses Dokument
ändert keinen Code. Es ist ein Eingang für das Bedrohungsmodell des
R17-Reviews (Rahmenplan R69, Stück 1) und kann dort aufgehen; die Befunde
sind **nicht** in den Backlog eingetragen — das ist nach R69 die
Freigaberunde des Auftraggebers (Abschnitt 6 macht Vorschläge).

**Stand:** Zweig `claude/crypto-security-review-eee2et` auf `main` vom
05.09.2026 (Commit `0e9429e`): Web 14.2.2, Uhr 3.0.2, Android 0.13.0.
Geprüft durch Lesen des Codes: `server/` vollständig für Krypto-, Anmelde-
und Schlüsselwege; dazu drei Durchgänge — klassische Web-Schwachstellen
(alle `server/*.php`, `server/api/*.php`, `server/assets/*.js`), Android
(`android/handy`, `android/uhr`, `android/gemeinsam`), Backup- und Adminwege.
**Nicht im Browser geprüft** (Abschnitt 1).

---

## 0. Die Antwort in fünf Sätzen

1. Das Verfahren ist richtig gebaut: PBKDF2 mit kontoeigenem Salz, ein
   zufälliger Inhaltsschlüssel, AES-256-GCM, zwei Hüllen, Wiederherstellungs-
   schlüssel mit rund 98 Bit — nach dem Vorbild Bitwarden, ohne Eigenbau,
   ohne einen Fehler, der die Verschlüsselung bricht. Kein serverseitiger
   Codepfad kennt den Inhaltsschlüssel oder den Klartext von `pat_blob`.
2. Gegen den klassischen Einbruch — Datenbank kopiert, Hoster neugierig,
   Sicherung verloren — hält sie **so lange wie das Passwort**: Wer den
   Abzug hat, kann Passwörter offline durchprobieren, und der Server kann die
   Passwortqualität nach Bauart nicht prüfen, weil er das Passwort nie sieht.
3. **„Keine Möglichkeit" stimmt nicht.** Drei Wege bleiben: ein schwaches
   Passwort (Angreifer mit Datenbankabzug); ein Angreifer, der **Code
   ausliefern** kann — Serverzugang, Push-Recht auf `main`, eine XSS-Lücke —
   bekommt den Schlüssel beim nächsten Anmelden; und die **Klartextdaten
   neben dem Chiffretext** (GPS-Spur, Phasenkoordinaten, Zielklinik, Zeiten,
   Reanimationsverlauf) verraten Einsatzort und Behandlung ohne jede
   Entschlüsselung.
4. Der dritte Weg ist der größte reale Riss in der Zusage — und er ist dem
   Projekt bekannt (Backlog 43, Weg B in P6). Der zweite ist prinzipbedingt
   für jede Web-Anwendung; das Handbuch (Abschnitt 5) sagt es bereits. Als
   zweite Verteidigungslinie gegen XSS fehlt eine Content-Security-Policy
   (Backlog 8); der Sweep hat **keine** XSS-Lücke gefunden.
5. Neu aus diesem Review: Admin-Backups verlassen das Haus **unversiegelt**,
   wahlweise über reines FTP; die Rundenzahl liegt unter der heutigen
   Empfehlung (320 000 statt 600 000); Photon bekommt den Einsatzort im
   Klartext; es gibt keinen zweiten Faktor; dazu eine Reihe niedriger Punkte
   (Abschnitt 4).

---

## 1. Was NICHT geprüft werden konnte — und warum

An erster Stelle, nicht in einer Fußnote.

- **Kein Browserlauf.** Alle Aussagen zu XSS, Sitzungen und
  `sessionStorage` sind aus dem Code gelesen, nicht im Browser vorgeführt.
  Die Prüfliste (Abschnitt 7) holt das nach.
- **Kein Angriffsversuch gegen eine Installation.** Kein Passwort geknackt,
  keine Datenbank abgezogen, kein Endpunkt beschossen. Die Zahlen zur
  Angriffsrate sind eine CPU-Messung auf der Prüfmaschine und eine Schätzung
  aus veröffentlichten GPU-Werten (3.3).
- **Betriebsumgebung unbekannt.** Hoster, PHP-Konfiguration (`session.*`,
  `display_errors`), ob `.htaccess` überhaupt greift (Apache/LiteSpeed gegen
  nginx), Datenbankrechte, TLS-Konfiguration, Branch-Schutz auf GitHub, wer
  die FTP-Zugangsdaten des Deploys kennt — nichts davon steht im
  Repositorium.
- **Rechtstexte** liegen in der Datenbank der Installation, nicht im Code;
  ob die Datenschutzerklärung Photon und die Kachelserver nennt, ist hier
  nicht prüfbar.
- **Vendorierte Bibliotheken** (Leaflet, SheetJS, zip.js, phpseclib): nur
  die Einbindung geprüft, nicht ihr Code. Eine XSS-Lücke in SheetJS oder
  Leaflet wäre wegen der fehlenden CSP voll wirksam.
- **Android:** Vertrauensmodell der Data-Layer-Bibliothek, Release-Manifest
  der Uhr, Hardware-Anker des Keystore — nur am Gerät prüfbar (4.4).

---

## 2. Bedrohungsmodell: Wer greift an, und was bekommt er

Die Frage „gibt es im Hacker-Fall keine Möglichkeit?" hat keine eine
Antwort, weil „Hacker" neun verschiedene Lagen meint. Die Tabelle nennt je
Lage, was der Angreifer in der Hand hat und was er damit an Patientendaten
erreicht. **Fett** ist, was die Zusage bricht.

| Nr. | Lage | Was er hat | Geschützte Angaben (`pat_blob`) | Was er trotzdem erfährt |
|---|---|---|---|---|
| A | **Datenbankabzug** — SQL-Lücke, Hoster-Panne, gestohlene Sicherung, neugieriger Datenbank-Admin | Chiffretexte, beide Hüllen, Salt, Rundenzahl, bcrypt(Token) | Verschlossen, **solange das Passwort gut ist** (3.3). Wiederherstellungshülle: 98 Bit, unerreichbar | **GPS-Spur, Koordinate jeder Phase (auch „Ankunft PatientIn"), Zielklinik mit Koordinaten und Schockraum, Zeiten, Reanimationsverlauf, Ortshöhe, Besatzungsnamen** — der Einsatzort ist ohne jede Entschlüsselung rekonstruierbar (Backlog 43, `Konzept-V1-Ortsdaten.md` 2) |
| B | **Datenbank + Dateisystem** — FTP-Zugang, Hosting-Panel, Backup des Webspace | zusätzlich `config.php` mit Serverschlüssel und SMTP-Zugang | Weiterhin verschlossen: Der Serverschlüssel öffnet Backup-Ziel-Zugänge und Komplettbackups, nicht die Hüllen (`serverkrypto_lib.php:9-22`; einzige Nutzer `sicherungsziel_lib.php`, `komplett_lib.php`) | Wie A; dazu Zugangsdaten der Backup-Ziele und alle Komplettbackups (deren `pat_blob` bleibt Chiffretext); Mails im Namen der Anwendung |
| C | **Aktiver Serverzugriff** — Code ändern: FTP-Zugang, Hoster, **jedes GitHub-Konto mit Push-Recht auf `main`**, kompromittierte Action | die ausgelieferten Seiten und `crypto.js` | **Offen.** Eine geänderte Zeile in `login.php` oder `crypto.js` schickt Passwort oder Schlüssel bei der nächsten Anmeldung mit; `ui_krypto_bootstrap()` (`ui.php:1905`) liefert Hülle, Salz und Runden ohnehin an jede Seite. Kein Web-Verfahren schützt hiergegen — auch keine Subresource Integrity gegen den eigenen Server | alles |
| D | **Skript im Origin (XSS)** — eine Lücke in einer Ausgabestelle genügt | JavaScript in der Sitzung der NutzerIn | **Offen für dieses Konto:** `sessionStorage.pck` ist der Inhaltsschlüssel als Hex (`crypto.js:329-340`); dazu die API mit allen Chiffretexten. **Keine CSP** als zweite Linie (Backlog 8). Der Sweep fand keine Lücke (4.1) | alles des angemeldeten Kontos |
| E | **Passwort erbeutet** — Phishing, Schulter, Keylogger, Wiederverwendung | Passwort | **Offen:** Passwort = Anmeldung **und** Datenschlüssel. Kein zweiter Faktor für NutzerInnen-Konten (P5 plant TOTP nur für Admin) | alles |
| F | **Endgerät** — Browser-Erweiterung mit Seitenzugriff, Sitzungswiederherstellung, unbeaufsichtigter Rechner | den Tab | Offen, solange der Tab lebt: Schlüssel im `sessionStorage`, Klartext im DOM. Nach 30 min Inaktivität endet die Sitzung; die Räumung im Browser hängt an JavaScript (`session_lib.php:63-69`). Firefox schreibt `sessionStorage` für die Sitzungswiederherstellung auf die Platte | alles, was der Tab zeigt |
| G | **Dritte, die der Browser selbst anspricht** | Photon (`photon.komoot.io`): getippte Adresse und Koordinate des Einsatzorts (`ortsfeld.js:82,360`, `ortswahl.js:34`); Kachelserver OSM, openmaps.fr, OpenTopoMap, Esri: Kartenausschnitt um den Einsatzort (`map_layers.js:32-57`) | Nicht der Chiffretext — aber **Adresse und Koordinate des Einsatzorts im Klartext, während des Tippens, an einen Dritten** | Einsatzort, Zeitpunkt, IP-Adresse der NutzerIn |
| H | **Verlorenes Gerät** — Garmin-Uhr, Handy | Garmin: Geräteschlüssel im Klartext im Gerätespeicher (`watch/source/Pair.mc:853`); Handy: im Keystore-Tresor (4.4) | Verschlossen: `ingest.php` ist POST-only, antwortet nur `ok`/`next_seq` (`ingest.php:49,707`) | Er kann **Einsätze hochladen und Phasen bestehender Einsätze überschreiben** (Upload ersetzt vollständig), bis das Gerät im Web getrennt ist. Lokal: Spuren und Zeiten, keine geschützten Angaben |
| I | **Böswillige Administration** (in der Anwendung, ohne Serverzugriff) | Konten, Einladungen, Backups, Freigaben, Komplettsicherung | Verschlossen: Kein Admin-Passwort-Setzen; der Reset-Weg verlangt den Wiederherstellungsschlüssel **im Browser** (`pw_handling.php:167-177`); die Freigabe reicht nur Chiffretext heraus (`api/adminbackup_freigabe.php:21-24`) | Alles aus A (über die Komplettsicherung). Kann Daten **zerstören** (Konto löschen, Reset ohne Schlüssel sperrt aus), nicht lesen — 4.3 |

**Die kurze Fassung.** Gegen A und B — den klassischen Einbruch — hält die
Verschlüsselung, mit zwei Einschränkungen, die beide nicht am Verfahren
liegen: Sie hält genau so lange, wie das Passwort stark ist, und sie
verdeckt den Einsatzort nur nominell, weil Spur und Phasenkoordinaten
daneben im Klartext stehen. Gegen C, D und E hält sie nicht, und das kann
kein Verfahren im Browser ändern; hier zählen Zugangsschutz zu Server und
Repositorium, eine CSP und ein zweiter Faktor. G ist kein Angriff, sondern
ein Abfluss, den das Projekt in Kauf nimmt — er gehört in die
Datenschutzerklärung. H und I sind begrenzt und im Code richtig behandelt.

---

## 3. Das Verfahren, wie es heute im Code steht

Alles Folgende ist gelesen, nicht angenommen.

### 3.1 Schlüsselhierarchie

| Stufe | Was | Wo |
|---|---|---|
| Passwort → 512 Bit | PBKDF2-HMAC-SHA256, **320 000 Runden** je Konto (`users.kdf_iter`), 16 Byte Salt je Konto | `crypto.js:63-77`, `db.php:673` |
| Datenschlüssel | Bytes 0–31 der Ableitung; bleibt im Browser (`sessionStorage.edk`) | `crypto.js:73,329-331` |
| Anmelde-Token | Bytes 32–63; ersetzt das Passwort gegenüber dem Server, dort **bcrypt**-gehasht | `crypto.js:74`, `login.php:136`, `pw_handling.php:167` |
| Inhaltsschlüssel (CK) | 256 Bit Zufall, nie vom Passwort abgeleitet | `pw_handling.php:424-427` |
| Hülle 1 `pat_wrap_pw` | AES-256-GCM(Datenschlüssel, CK) | `crypto.js:143-151` |
| Hülle 2 `pat_wrap_rc` | AES-256-GCM(SHA-256(`edk-rc:`+Code), CK); Code 20 Zeichen aus 30 ≈ **98 Bit** | `crypto.js:170-181,253-256` |
| `pat_blob` | AES-256-GCM(CK, JSON), 96-Bit-Zufalls-IV, Kennung `edk1:` | `crypto.js:139-151` |
| `pat_key_check` | SHA-256(`edk-ckchk:`+CK), 128 Bit; nur Verwechslungsschutz | `crypto.js:313-317` |
| Backup `.edbak` | Klartext, dann PBKDF2 (320 000) mit Backup-Passwort, AES-GCM mit AAD-Bindung je Teil | `crypto.js:380-560` |
| Serverschlüssel | 32 Byte in `config.php`; versiegelt **nur** Backup-Ziel-Zugänge und Komplettbackup | `serverkrypto_lib.php:1-80` |

### 3.2 Anmeldung, Sitzung, Schlüsselwechsel

- **Anmeldung:** Ratenschutz je Konto **und** IP in der Datenbank, vor
  bcrypt; Fehlerzweig mit konstanter Dauer (`login.php:80-84,223-225`).
  Sitzungscookie `Secure`, `HttpOnly`, `SameSite=Strict`;
  `session_regenerate_id(true)` nach Erfolg (`login.php:12,185`).
- **Salz-Endpunkt:** Pseudo-Salz per HMAC in identischer Länge und
  identische Rundenliste für jede Adresse, Mindestdauer 50 ms
  (`auth_salt.php:87-123`).
- **Sitzung:** 30 min Inaktivität; Rolle bei jeder Anfrage aus der
  Nutzerzeile; `session_epoch` beendet fremde Sitzungen nach Passwortwechsel
  (`auth_guard.php:56-73,138-143`).
- **Passwortwechsel:** altes Token als Nachweis, nur `KDF_ITER_ZIEL`,
  Hülle geprüft, `pat_key_check` abgeglichen, alles in einer Transaktion,
  `session_epoch + 1`, offene Reset-Links entwertet (`einstellungen.php:137-250`).
- **Stille Anhebung der Rundenzahl:** altes Token als Nachweis, nur
  Zielwert, keine Senkung, `pat_key_check` (`api/kdf_upgrade.php:80-137`).
- **Reset und Einladung:** Token 32 Byte `random_bytes`, gespeichert als
  SHA-256, 1 h bzw. 24 h gültig, aus der Adresszeile in eine eigene
  Cookie-Sitzung überführt (`reset_request.php:89-91`,
  `pw_handling.php:64-93`). Der Reset ersetzt `pat_wrap_rc` **nicht**
  (`pw_handling.php:171-178`); die Betriebsart kommt aus dem Datenbank-
  zustand, nie aus der Eingabe (Z. 113).
- **Header:** HSTS (nur `max-age`), `X-Frame-Options DENY`, `nosniff`,
  `Referrer-Policy` (`.htaccess:30-35`). **Keine CSP.** 16 Seiten tragen
  Inline-Skripte.

### 3.3 Bewertung der Verfahrenswahl

**Solide und ohne Eigenbau.** Ausschließlich WebCrypto, ausschließlich
authentifizierte Verschlüsselung (GCM), Zufall aus `crypto.getRandomValues`,
Formatkennung vor jedem Chiffretext, AAD-Bindung der Backup-Teile gegen
Vertauschen und Unterschieben. Die Trennung von Datenschlüssel und Inhalts-
schlüssel ist richtig: Ein Passwortwechsel packt nur die Hülle um. Die
Zweiteilung einer PBKDF2-Ausgabe in Anmelde-Token und Datenschlüssel ist
sauber — beide Hälften sind unabhängige PRF-Ausgaben; wer das Token sieht
(der Server bei jeder Anmeldung), gewinnt gegenüber dem Chiffretext der Hülle
nichts dazu.

**Drei Stellen, an denen die Wahl unter dem heutigen Stand der Empfehlungen
liegt — keine davon ist ein Fehler, alle drei sind Zahlen:**

1. **320 000 Runden.** OWASP empfiehlt für PBKDF2-HMAC-SHA256 seit 2023
   600 000; Bitwarden, dessen Bauform hier ausdrücklich Vorbild ist, hat
   seinen Standard 2023 ebenfalls auf 600 000 angehoben. Der Mechanismus zum
   Anheben ist vorhanden und getestet (`KDF_ITER_LISTE`, stille Anhebung,
   M2-01) — die Anhebung ist eine Änderung in zwei Zeilen von `db.php`, und
   der Kommentar dort beschreibt sie schon. **Gemessen auf der Prüfmaschine
   (ein CPU-Kern, Python `hashlib`):** 165 ms je Ableitung bei 320 000
   Runden, 285 ms bei 600 000. Für die Anmeldung heißt das rund 0,3 s mehr
   auf einem langsamen Telefon; für einen Angreifer halbiert es die Rate.
2. **PBKDF2 statt Argon2id.** PBKDF2 ist GPU-freundlich, Argon2id nicht.
   WebCrypto kennt Argon2 nicht; es bräuchte eine vendorierte WASM-Bibliothek
   — lokal möglich (Zusage „keine fremde Quelle"), aber ein neuer
   Fremdbestandteil mit Pflege. Für ein Projekt dieser Größe ist die
   Anhebung der Runden der bessere erste Schritt; Argon2id gehört als
   **Frage** in das Bedrohungsmodell von P6, nicht als Fehler.
3. **Wiederherstellungsschlüssel ohne Streckung** (ein SHA-256). Bei 98 Bit
   Zufall ist das richtig — Durchprobieren scheidet aus, der Kommentar in
   `crypto.js:206-210` rechnet es vor. Die Restklassenbildung `raw[i] % 30`
   verzerrt die Verteilung minimal (Werte 0–15 sind um ein Achtel
   wahrscheinlicher); der Entropieverlust liegt weit unter einem Bit.

**Was der Server über den Inhaltsschlüssel lernt: nichts Verwertbares.**
`pat_key_check` ist ein 128-Bit-Ausschnitt eines Hashes über 256 Bit Zufall;
weder umkehrbar noch durchprobierbar.

**Die Rate eines Angreifers mit Datenbankabzug.** Er hat Salt, Rundenzahl
und `pat_wrap_pw`. Je Passwortkandidat kostet ihn eine PBKDF2-Ableitung und
einen AES-GCM-Versuch; der GCM-Tag sagt ihm sofort, ob er richtig lag.
Größenordnung nach veröffentlichten hashcat-Werten (PBKDF2-HMAC-SHA256 auf
einer RTX 4090: rund 7–8 Mio. Ableitungen/s bei 1 000 Runden): **etwa
20 000 bis 25 000 Kandidaten je Sekunde und Grafikkarte** bei 320 000
Runden. Das ist eine Schätzung, keine Messung — die Größenordnung genügt für
die Folgerung:

- ein Passwort aus Wörterbuch und Mustern („Sommer2026!", Name+Jahr) fällt
  in Stunden bis Tagen;
- zehn zufällige Zeichen aus 70 (≈ 61 Bit) kosten rund drei Millionen
  GPU-Jahre; eine Passphrase aus fünf zufälligen Wörtern ebenso.

**Der Schutz gegen den Datenbankabzug ist damit genau so gut wie das
Passwort — und der Server kann die Passwortqualität nach Bauart nicht
prüfen.** `pwquality.js` (Mindestlänge 10, Sperrliste, Musterprüfung) wirkt
nur im Browser und nur gegen Unachtsamkeit, nicht gegen Absicht. Das ist
kein Mangel des Codes, sondern die Konsequenz der Bauform; sie gehört ins
Handbuch und in die Einweisung.

---

## 4. Befunde

Schweregrade: **hoch** = bricht die Zusage in der Praxis; **mittel** =
schwächt sie oder nimmt eine Verteidigungslinie; **niedrig** = begrenzt,
aber zu kennen. **Kritisch: keiner.** Was bereits im Backlog steht, ist als
solches gekennzeichnet.

### 4.1 Web (Kryptographie, Anmeldung, klassische Schwachstellen)

| Nr. | Grad | Befund | Beleg | Empfehlung |
|---|---|---|---|---|
| K-1 | **hoch** (bekannt: Backlog 43) | **Klartext-Ortsdaten.** GPS-Spur, Koordinate jeder Phase (Phase 4/5 sind der Einsatzort, so eingestuft in `api/export_data.php:50-53`), `site_ele_m`, `transport_dest` mit `dest_lat/lon`, `schockraum`, Reanimationsverlauf mit Uhrzeiten, Besatzungsnamen. Ein Datenbankabzug ergibt Ort, Zeit, Klinik und Behandlung — ohne Name und Diagnose, aber mit Zusatzwissen re-identifizierend | `schema.sql` (`missions`, `mission_phases`, `track_*`, `resus_*`, `*_crew`); `Technik.md` 4.98 räumt es ein | Weg B (Schlüssel auf die Uhr) bleibt die Lösung; bis dahin **Weg C** aus `Konzept-V1-Ortsdaten.md` 5: die Zusage in `CLAUDE.md` 4, `Technik.md` und der Datenschutzerklärung auf das eingrenzen, was sie hält |
| K-2 | **mittel** (bekannt: Backlog 8) | **Keine Content-Security-Policy.** Daten- und Inhaltsschlüssel liegen als Hex im `sessionStorage`; im Vormerkfach `edkvor` während einer Rundenanhebung auch die Anmelde-Token (`crypto.js:374-377`; heute inaktiv, `KDF_ITER_LISTE` hat einen Eintrag). Jede XSS-Lücke — auch eine künftige oder eine in Leaflet/SheetJS/zip.js — liest sie aus. Der Sweep fand keine; die CSP ist die Schicht für die, die nicht gefunden wird | `.htaccess:30-35`; `grep Content-Security-Policy server/` leer | Nonce-basierte CSP (`script-src 'nonce-…' 'self'`, `object-src 'none'`, `base-uri 'none'`, `frame-ancestors 'none'`); wegen 16 Inline-Skripten ein Paket, kein Handgriff — P5 plant es (Rahmenplan Schritt 10). Dazu erwägen: Schlüssel als nicht-extrahierbarer `CryptoKey` in IndexedDB statt Hex — XSS könnte dann entschlüsseln lassen, aber den Schlüssel nicht mitnehmen |
| K-3 | **mittel** (inhärent) | **Passwortstärke ist die einzige Schranke gegen den Offline-Angriff**; Rundenzahl 320 000 unter der Empfehlung 600 000 (3.3) | `db.php:673`, `pwquality.js:33` | `KDF_ITER_ZIEL` auf 600 000, `KDF_ITER_LISTE = [600000, 320000]`; Mindestlänge in `pwquality.js` auf 12 und Passphrasen-Empfehlung im Handbuch |
| K-4 | **mittel** (neu) | **Admin-Backups sind unversiegelt** — blankes JSON im ZIP, mit allen Klartextfeldern (K-1), E-Mail, Name und `pat_wrap_rc` — und gehen beim Versand wahlweise über **reines FTP** oder FTPS **ohne Zertifikatsprüfung** (`ext/ftp` prüft nicht). Die Begründung in `Backup-Format.md` 5 („kein Schlüssel, ohne ihn zu speichern") ist seit dem Serverschlüssel (Web 12.1.0) überholt | `adminbackup_lib.php:404,624`; `sicherungsziel_lib.php:31-33,244-246`; `schema.sql:512` (`ENUM('ftp','ftps','sftp')`) | Adminpakete mit dem Serverschlüssel versiegeln wie das Komplettbackup; Protokoll `ftp` abschaffen oder mit rotem Hinweis versehen; SFTP mit Hostschlüssel als Empfehlung |
| K-5 | **mittel** (neu) | **Kein zweiter Faktor** für NutzerInnen-Konten. Passwort = Anmeldung und Datenschlüssel; Phishing genügt | `login.php` | TOTP für alle Konten (P5 plant nur Admin); optional Passkeys für die Anmeldung — der Datenschlüssel bliebe passwortgebunden, aber die Anmeldung wäre phishing-fest |
| K-6 | **mittel/niedrig** | **Einsatzort an Dritte:** Photon bekommt Adresse (beim Tippen ab drei Zeichen) und Koordinate (Umkehrsuche); Kachelserver den Ausschnitt. Widerspricht dem Wortlaut „keine fremde Quelle zur Laufzeit" (`CLAUDE.md` 4), ist im Code begründet | `ortsfeld.js:61-82,360`; `ortswahl.js:16-34`; `map_layers.js:32-57` | In der Datenschutzerklärung benennen; S9 PS-1 (Geocoding-Quelle) klärt die Zukunft; Zusage in `CLAUDE.md` präzisieren („kein fremdes Skript") |
| K-7 | niedrig | **E-Mail-Wechsel ohne Passwort** im Profil (nur CSRF-Token, kein `old_token`, kein `epoch`, keine Bestätigung an alte oder neue Adresse); Admin kann die Adresse eines Kontos ebenfalls ändern und einen Reset schicken. Die Kette endet im Reset-Modus, der den Wiederherstellungsschlüssel braucht: **kein Zugriff auf `pat_blob`**, aber Kontoübernahme für Klartextfelder und Aussperren. Altkonten ohne `pat_key_check` haben die zusätzliche Hürde (`pw_handling.php:143-150`) nicht | `einstellungen.php:92-106`; `admin_user.php:127-133,172-196` | Profil-E-Mail-Wechsel mit `old_token` und Benachrichtigung an die alte Adresse; P5 (E-Mail-Wechsel mit Double-Opt-In) deckt es |
| K-8 | niedrig | **Login-CSRF:** Anmeldeformular ohne Token; eine fremde Seite kann einen abgemeldeten Browser per Top-Level-POST in ein Angreiferkonto anmelden. Patientenfelder nicht betroffen (kein `edk`, fremde Hülle öffnet nicht); Gefahr: Eingaben ins fremde Konto | `login.php:246`, `login.php:15` | CSRF-Token auch am Anmeldeformular (Sitzung existiert bereits) |
| K-9 | niedrig | **Unversperrte Ordner im Webroot:** `server/apk/` (Anmeldung nur über `apk.php`, Dateinamen vorhersagbar; `Technik.md` 4.97g sagt „nur angemeldet" — das gilt für `apk.php`, nicht für den Ordner) und `server/demo/fixture.json.gz` (Demo-Schlüsselmaterial mit öffentlichem Passwort — harmlos, aber unnötig) haben keine `.htaccess`-Sperre, anders als `sicherungen/` | `apk_lib.php:22`; `server/demo/` ohne `.htaccess`; `.htaccess` ohne Regel | Beide Ordner in `.htaccess` sperren; für `apk/` die Sperre zur Laufzeit anlegen wie bei `sicherungen/` |
| K-10 | niedrig | **DOCTYPE-Sperre im GPX-Import umgehbar:** Regex auf dem Rohtext vor `simplexml_load_string(..., LIBXML_NONET)`; ein UTF-16-kodiertes GPX passiert sie. Folge: interne Entitäten (Billion Laughs) möglich, XXE nicht (kein `NOENT`, `NONET`). Nur angemeldet, 12 MB Grenze, libxml-Aufblähungsgrenzen | `gpx_lib.php:332` | Prüfung nach der Kodierungserkennung oder `LIBXML_DTDLOAD`/`DTDATTR` ausdrücklich aus und `libxml_disable_entity_loader`-äquivalent; Größe des expandierten Dokuments begrenzen |
| K-11 | niedrig | **Unangemeldete Auskunft in `wiederherstellen.php`:** Datenbank-Fehlertext (Host, Nutzer möglich) und Kontenzahl für jeden Besucher | `wiederherstellen.php:158,530,538` | Fehlertext nur als Kennung; Kontenzahl weglassen („in Betrieb" genügt) |
| K-12 | niedrig | **Freitextfelder im Klartext ohne Hinweis:** `bw_info` („Namen / Infos"), `days.notes`, Besatzungs-Freitexte; `notes` trägt den Placeholder „Freitext (keine Patientendaten!)", die anderen nicht. Bedienfehler tragen Patientendaten in den Klartext | `mission_fields.php:395,426,459` | Denselben Hinweis an alle Klartext-Freitextfelder; Backlog 108 (Schloss-Icon) und 109 (Notizfeld verschlüsseln) sind die Richtung |
| K-13 | niedrig | **Reste im Klartext:** Reset-Token bis zur Einlösung in der PHP-Sitzungsdatei und im Zugriffslog des ersten GET (in M1-06 anerkannt); bei Mailfehler zeigt die Verwaltung den Setz-Link an; während des Komplettbackup-Baus liegt `dump.sql.gz` unversiegelt in `sicherungen/komplett/.bau-*/`, Reste bis zum nächsten Lauf | `pw_handling.php:87`; `admin_user.php:201`; `komplett_lib.php:53-55,454,471` | Bauordner nach Fehlschlag sofort räumen; sonst hinnehmen und dokumentieren |
| K-14 | niedrig | **Verlorene Garmin-Uhr:** Geräteschlüssel im Klartext im Gerätespeicher (die Plattform hat nichts Besseres); der Finder kann Einsätze hochladen und Phasen bestehender Einsätze überschreiben, bis das Gerät getrennt ist. Lesen kann er nichts | `watch/source/Pair.mc:853`; `ingest.php:49,707` | Im Handbuch unter „Gerät verloren": sofort trennen; erwägen, ob `ingest.php` Phasen eines **abgeschlossenen** Einsatzes noch ersetzen soll |
| K-15 | niedrig | **Kleinigkeiten an Headern und Escaping:** HSTS ohne `includeSubDomains`/`preload`; keine `Permissions-Policy`; `json_encode` in Inline-Skripten ohne `JSON_HEX_TAG` (Seitenbruch möglich, keine Ausführung, weil `\/` maskiert); `csrf_check()` ohne `(string)`-Cast (Selbst-DoS); `querySelector` mit Wert aus dem URL-Fragment in `suche.php:535` (Bruch, kein XSS) | `.htaccess:35`; `ui.php:1928-1940`; `auth_guard.php:175` | `JSON_HEX_TAG\|JSON_HEX_AMP` als Vorgabe in `ui_krypto_bootstrap()` und Co.; HSTS erweitern, wenn Subdomains geklärt sind |
| K-16 | niedrig (organisatorisch) | **Push auf `main` ist Deploy.** Der Deploy läuft aus GitHub Actions per FTPS mit Klartext-Zugangsdaten in Secrets; jedes Konto mit Push-Recht auf `main` ist Angreifer C. Das Repositorium soll öffentlich werden (R68) | `.github/workflows/deploy.yml` | Branch-Schutz auf `main` mit Review-Pflicht und 2FA-Zwang in der Organisation; R67 (Staging, Freigabetor) beendet den Autodeploy ohnehin |

### 4.2 Was der Web-Sweep ausdrücklich **nicht** gefunden hat

Kein ausnutzbares XSS: Alle Bausteine maskieren (`e()`/`ui_e()` in
`ui.php:951-1360`), `rt_html()` maskiert zuerst und lässt Linkziele nur per
Positivliste durch (`rechtstexte_lib.php:222-301`), JS-Sinken laufen über
`EdHtml.escape`/`textContent` (`zeitraum.php:558-568`,
`einsatz_form.php:1876-1930`, Photon-Treffer in `ortsfeld.js`); die
Baustein-Schlüssel mit Roh-Markup (`aktionen`, `unter`, `plakette`, `attr`)
wurden an allen 60+ Aufrufstellen geprüft — Inhalte kommen aus Literalen
oder vor-maskiert. **Keine SQL-Injection:** PDO mit Platzhaltern überall,
Tabellennamen nur aus Literal-Maps, Spaltennamen des Feldkatalogs validiert
(`mission_fields_lib.php:64,169`), `IN`-Listen über `sql_in_bloecken()`.
**Kein IDOR:** `user_id` steht in der Abfrage, nicht dahinter
(`api/mission.php:15`, `einsatz_form.php:23,95,479`, `spur_lib.php:1476`,
`export_data.php:521`, `trash_lib.php`, alle `diensttag_*` über
`dt_laden($userId, …)`). **CSRF** an jedem POST-Block und jedem
API-Endpunkt (`hash_equals`). **Kein Open Redirect**, kein `unserialize`,
kein dynamisches `include`, kein `$_FILES`. **Zufall** überall
`random_bytes`, **Vergleiche** überall `hash_equals`.

### 4.3 Serverseitige Wege, auf denen Schlüssel oder Klartext liegen könnten

Geprüft wurden alle Stellen, an denen der Server Schlüsselmaterial oder
Klartext geschützter Felder in die Hand bekommen könnte. **Ergebnis: kein
serverseitiger Codepfad kennt den Inhaltsschlüssel oder den Klartext von
`pat_blob`** — mit der einen dokumentierten Ausnahme Demo-Konto.

- **Admin-Backups** (`adminbackup_lib.php:357-613`): Aus `users` gehen nur
  `email, name, account_key, pat_wrap_rc, pat_key_check` ins Paket; **nicht**
  `pat_wrap_pw`, `password_hash`, `kdf_salt`. `pat_blob` bleibt Chiffretext.
- **Freigabeweg** (`api/adminbackup_freigabe.php`,
  `einstellungen.php:2896-3075`): Der Server reicht `pat_wrap_rc` und
  Chiffretexte an die angemeldete Zielnutzerin; Wiederherstellungsschlüssel,
  abgeleiteter Schlüssel und Inhaltsschlüssel bleiben im Browser
  (`einstellungen.php:2919-2943`); zum Server gehen nur umgeschlüsselte
  Einträge. Die Sperre „Freigabe statt Einspielen" wird serverseitig
  erzwungen (`admin_user.php:243-249`). `tools/freigabeprobe/` belegt
  „Chiffretext ist ein anderer, Klartext derselbe".
- **Demo-Konto**: Kennung ausschließlich `app_state.demo_user_id`
  (`demo_lib.php:118-134`), gesetzt nur in `demo_anlegen()` (Z. 262-264), das
  ein zweites Demo-Konto ablehnt (Z. 234). Kein Endpunkt nimmt eine
  Kontokennung von außen. Wer mit Datenbank-Schreibzugriff ein fremdes Konto
  zum Demo-Konto erklärt, **vernichtet** dessen Schlüssel beim Reset
  (`demo_lib.php:307-317` überschreibt sie mit Fixture-Werten) — er legt sie
  nicht offen. Die Fixture trägt `password_hash`, `kdf_salt`, beide Hüllen
  und `pat_key_check` des Demo-Kontos, keinen Klartext-Inhaltsschlüssel.
- **Reset und Einladung**: Kein Weg in der Verwaltung setzt `pat_wrap_rc`
  auf NULL (grep über `admin_user.php`, `admin_users.php`: keine
  `pat_wrap`-Stelle); nur `user_delete` entfernt das Konto ganz, und die
  alten Blobs sind danach allein per Freigabe plus Wiederherstellungs-
  schlüssel lesbar. Die Kette aus K-7 endet ohne Offenlegung.
- **E-Mail** (`smtp.php:148-150`): implizites TLS mit `verify_peer`
  (anders als FTPS), CRLF-Prüfung der Adresse; Inhalte sind Links,
  Gerätekennungen, Adressen — keine Patientendaten, Passwörter oder Schlüssel.
- **`ingest.php`**: nimmt keine Patientenfelder an (Z. 113-156, 342-404,
  513-585); alle `pat_blob`-Schreibwege laufen durch `pruef_pat_blob()`
  (`validate_lib.php:554-574`) — die prüft Form und Länge, nicht Inhalt, was
  sie nach Bauart auch nicht kann.
- **Suchindex, Export, Logs**: Suchindex liefert den eigenen Bestand als
  Chiffretext, kein Suchbegriff und kein Blind-Index geht zum Server
  (`api/suchindex.php:38,55,205`); Export filtert serverseitig und liefert
  `pat_blob` nur als Chiffretext (`api/export_data.php:274-276,435`),
  entschlüsselt wird in `assets/export.js`. Alle `error_log`-Stellen
  gesichtet: Ausnahmetexte, Empfängeradressen, Job-Fehler — keine Blobs,
  Tokens, Passwörter, Schlüssel.

### 4.4 Android (Handy und Wear OS, 0.13.0)

Keine kritischen oder hohen Befunde. Die drei Zusagen halten im Quelltext:

- **Keine Patientendaten auf Handy und Uhr.** An `ingest.php` gehen
  `kind, client_ref, day, started_at, ended_at, final, distance_m, ascent_m,
  phases, track` (`handy/…/senden/Sendekoerper.kt:29-67`); Reanimation
  sendet Android gar nicht. Lokal: Klartext-SQLite `puffer.db` mit GPS-Spur,
  Zeiten, Phasen — keine Zugangsdaten, keine geschützten Angaben
  (`puffer/Puffer.kt:38-144`).
- **Zugangsdaten im Tresor.** `tresor.bin` im app-privaten Speicher,
  AES-256-GCM mit frischem IV, Schlüssel aus dem `AndroidKeyStore`, nicht
  exportierbar (`tresor/Schluesseltresor.kt:42-165`,
  `tresor/Tresorschluessel.kt:49-118`); `allowBackup="false"` und
  `dataExtractionRules` schließen Cloud- und Gerätetransfer aus
  (`handy/src/main/AndroidManifest.xml:144-148`). Der Schlüssel geht nur in
  Kopfzeilen (`senden/Sender.kt:93-96`), Umleitungen sind abgeschaltet
  (`kopplung/Netzweg.kt:92`), keine der 20 Log-Stellen enthält ihn.
- **Die Uhr kennt keine Zugangsdaten.** `uhr/src/main/AndroidManifest.xml`
  hat **keine einzige `uses-permission`**, auch kein `INTERNET`; kein
  HTTP-Client, kein Tresor unter `uhr/` oder `gemeinsam/`; der Prüffall
  `NachrichtenformatTest.kt:82-90,135-141` zählt die Schlüssel beider
  Nachrichtenrichtungen nach.

Niedrige Befunde (Empfehlungen, keine Lücken):

| Nr. | Befund | Ort |
|---|---|---|
| AN-1 | Die HTTP-Ausnahme für `localhost` und IPv4-Adressen gilt auch im **Release-Build** und stuft ein ausdrückliches `https://127.0.0.1/` auf `http` herab (Test `oertlicheAdressenBehaltenHttp` belegt es). Keine Release-`network_security_config` mit Klartextverbot; auf Android 8.0/8.1 (`minSdk 26`) ginge `X-Api-Key` bei einer Selbsthoster-Adresse per IP im Klartext. Der Standardbau mit fester Domain ist nicht betroffen | `kopplung/Serveradresse.kt:108,119`; `ServeradresseTest.kt:92` |
| AN-2 | Vom Server mit 400 abgewiesene Pakete bleiben samt GPS-Spur dauerhaft liegen (`fehlerhaft = 1`), überleben Trennen und Neukopplung; `dienst`-Zeilen werden nie gelöscht | `puffer/Puffer.kt:449-514` |
| AN-3 | Kein Certificate Pinning und keine dokumentierte Entscheidung dagegen (vertretbar bei fester Domain, aber nirgends festgehalten) | `kopplung/Netzweg.kt` |
| AN-4 | Der Data-Layer-Empfang prüft keinen Absender (`sourceNodeId`) und keine Plausibilität der Zeitstempel; das Vertrauen ruht ganz auf der proprietären Bibliothek (gleicher Paketname, gleiche Signatur). Kein Abflussweg — nur Störung möglich | `uhr/HandyHorcher.kt:30-32`, `uhr/Uhrannahme.kt:63-92` |
| AN-5 | Release ohne R8 (`isMinifyEnabled = false`), Logs ungefiltert (Inhalte unkritisch); Gradle-Wrapper ohne `distributionSha256Sum` | `*/build.gradle.kts`, `gradle-wrapper.properties:3-5` |

Nicht prüfbar im Container: das Vertrauensmodell der Data-Layer-Bibliothek
(nur Gerätetest), das zusammengeführte Uhr-Manifest der Release-APK
(Prüfweg: `aapt2 dump permissions`, erwartet: keine), der Hardware-Anker
des Keystore (nur echtes Gerät).

---

## 5. Was sauber gelöst ist

Damit die Befunde nicht das Bild verzerren — das hier ist der größere Teil:

- **Kryptographie ohne Eigenbau** (3.3); Formatkennung; AAD-Bindung der
  Backup-Teile; `pruefeRunden()` ohne Vorgabewert; Wiederherstellungs-
  schlüssel mit Alphabet- und Längenprüfung vor der Ableitung.
- **Der Server kann nach Bauart nichts öffnen** und lernt nichts (4.3);
  Passwortwechsel, Rundenanhebung und Reset verlangen den Nachweis und
  prüfen die Hülle gegen `pat_key_check`, bevor irgendetwas geschrieben
  wird; Reset lässt `pat_wrap_rc` unberührt; keine Admin-Passwortvergabe.
- **Anmeldung:** Ratenschutz vor bcrypt, konstante Antwortdauer, kein
  Aufschluss über vorhandene Konten (Salz-Endpunkt), `SameSite=Strict`,
  Sitzungsneuvergabe, Epoch, Rolle je Anfrage aus der Zeile.
- **Kopplung:** Bedrohungsmodell mit zwölf Angriffen dokumentiert
  (`Technik.md` 4.99b), Schlüssel nur als SHA-256 gespeichert, Klartext
  genau einmal über die Leitung, `hash_equals`, Zeitgleichheit der
  Fehlzweige.
- **Web-Klassiker:** kein XSS, keine SQL-Injection, kein IDOR, kein Open
  Redirect gefunden; CSRF durchgehend; Dateinamen per Regex (4.2).
- **Android:** Keystore-Tresor, kein Backup, keine Patientendaten, Uhr ohne
  Berechtigungen, Nachrichtenformat per Prüffall festgeschrieben (4.4).
- **Die Grenzen sind benannt**, nicht versteckt: Handbuch 5 („prinzipbedingt
  nicht gegen einen vollständig übernommenen Server"), `Technik.md` 4.98,
  Backlog 8 und 43, `Konzept-V1-Ortsdaten.md`. Dieses Review hat die
  Selbstbeschreibung des Projekts an keiner Stelle als beschönigend erlebt.

---

## 6. Empfehlungen in Reihenfolge

Vorschlag, nicht Beschluss — die Entscheidung je Fund ist nach R69 Sache
der Freigaberunde.

**Sofort (klein, ohne Konzept, je ein Commit):**

1. `KDF_ITER_ZIEL` auf 600 000, alte Zahl in der Liste (K-3). Vorher
   den Anhebungsweg mit einem Konto in der Prüfumgebung fahren.
2. `.htaccess`-Sperren für `apk/` und `demo/` (K-9).
3. CSRF-Token am Anmeldeformular (K-8).
4. Hinweistext an allen Klartext-Freitextfeldern (K-12).
5. Fehlertext und Kontenzahl aus `wiederherstellen.php` nehmen (K-11).
6. Branch-Schutz auf `main` prüfen und 2FA in der GitHub-Organisation
   erzwingen (K-16) — kein Code, ein Häkchen.

**Nächstes Paket (Konzept nötig):**

7. Adminpakete mit dem Serverschlüssel versiegeln, Protokoll `ftp`
   abschaffen (K-4). Berührt `Backup-Format.md` 5 und den Rückweg.
8. Content-Security-Policy mit Nonce (K-2) — P5 plant es; die 16
   Inline-Skripte sind die Arbeit.
9. E-Mail-Wechsel mit Passwortnachweis und Benachrichtigung (K-7) —
   P5 plant Double-Opt-In.
10. Zweiter Faktor für alle Konten (K-5) — P5 plant TOTP für Admin;
    die Frage ist, ob nur dort.

**Bedrohungsmodell P6 (Fragen, keine Fixes):**

11. Weg B — Schlüssel auf die Uhr (K-1); bis dahin Weg C in `CLAUDE.md`,
    `Technik.md`, Datenschutzerklärung.
12. Argon2id statt PBKDF2 (3.3, Nr. 2) — Fremdbestandteil gegen
    GPU-Resistenz abwägen.
13. Photon und Kachelserver in der Datenschutzerklärung; Geocoding-Quelle
    nach S9 PS-1 (K-6).
14. Schlüssel als nicht-extrahierbarer `CryptoKey` statt Hex im
    `sessionStorage` (K-2, zweiter Teil).

**Vorschläge für den Backlog** (Nummern vergibt die Freigaberunde): K-4,
K-5, K-7, K-8, K-9, K-10, K-11, K-12, K-16, AN-1, AN-2. K-1, K-2 und K-6
stehen als 43, 8 und über S9 PS-1 bereits drin.

---

## 7. Prüfliste — was **du** noch prüfen musst

Je Punkt der Bedienweg, das erwartete Ergebnis und woran ein Scheitern zu
erkennen ist. Abhaken, wenn geprüft.

- [ ] **Branch-Schutz `main`** — GitHub → Settings → Branches: Regel für
      `main` mit „Require pull request" und „Require review". *Scheitern:*
      keine Regel, oder ein einzelnes Konto kann direkt pushen.
- [ ] **2FA in der Organisation** — GitHub → Organization → Settings →
      Authentication security: „Require two-factor authentication" aktiv.
      *Scheitern:* Haken fehlt.
- [ ] **FTP-Zugangsdaten des Deploys** — Wer kennt `FTP_PASSWORD`? Nur die
      GitHub-Secrets und eine Person. *Scheitern:* das Passwort steht in
      einer Mail, einem Chat oder einem Passwortmanager, den mehrere sehen.
- [ ] **`.htaccess` greift** — im Browser `https://…/config.php`,
      `https://…/schema.sql`, `https://…/vendor/laden.php` aufrufen:
      erwartet 403. *Scheitern:* Inhalt oder 200. Danach dasselbe für
      `https://…/apk/` und `https://…/demo/fixture.json.gz` — heute
      **erwartet: erreichbar** (K-9); nach dem Fix 403.
- [ ] **Security-Header live** — Entwicklerwerkzeuge → Netzwerk → Antwort
      der Startseite: `Strict-Transport-Security`, `X-Frame-Options: DENY`,
      `X-Content-Type-Options: nosniff` vorhanden; `Content-Security-Policy`
      **fehlt** (K-2, bekannt). *Scheitern:* einer der drei fehlt — dann
      greift `.htaccess` nicht.
- [ ] **Schlüssel im Tab** — angemeldet, Einsatz geöffnet, Entwickler-
      werkzeuge → Anwendung → Session Storage: `edk` und `pck` sind
      sichtbar (64 Hexzeichen). Das ist der heutige Zustand und der Grund
      für K-2. Abmelden → beide weg. *Scheitern:* nach dem Abmelden liegt
      einer noch da.
- [ ] **Neuer Tab = Entsperrdialog** — angemeldet, Link in neuem Tab
      öffnen: Dialog „Geschützte Angaben entsperren" erscheint. *Scheitern:*
      Angaben ohne Dialog lesbar (Schlüssel wandert zwischen Tabs — darf
      nicht sein).
- [ ] **Reset ohne Wiederherstellungsschlüssel** — Testkonto,
      „Passwort vergessen", Link öffnen, neues Passwort mit falschem
      Schlüssel: Meldung „Der Wiederherstellungsschlüssel passt nicht. Es
      wurde nichts geändert." und altes Passwort gilt weiter. *Scheitern:*
      Passwort geändert oder Angaben danach unlesbar.
- [ ] **Passwortstärke der echten Konten** — jede NutzerIn fragen (nicht
      prüfen — der Server kann es nicht): mindestens zwölf Zeichen oder eine
      Passphrase, nirgends wiederverwendet. *Scheitern:* jemand nennt ein
      Muster aus Name und Jahr.
- [ ] **Backup-Ziele** — Admin → Sicherungsziele: Protokoll jedes Ziels
      ansehen. *Scheitern:* eines steht auf `ftp` (K-4 — Klartext über die
      Leitung).
- [ ] **Datenschutzerklärung der Installation** — Admin → Rechtstexte:
      Photon/komoot und die vier Kachelanbieter genannt? *Scheitern:*
      nicht genannt (K-6).
- [ ] **Android Release-Manifest der Uhr** — `aapt2 dump permissions
      uhr-release.apk` (SDK-Werkzeug): erwartet keine Zeile. *Scheitern:*
      irgendeine `uses-permission`, vor allem `INTERNET`.
- [ ] **Login-CSRF vorführen** (optional, nur Prüfumgebung) — eine lokale
      HTML-Datei mit einem Formular, das per POST an `login.php` ein eigenes
      Testkonto anmeldet, in einem abgemeldeten Browser absenden: erwartet
      **angemeldet** (K-8, heutiger Zustand); nach dem Fix „Ungültiges
      Formular-Token".

**Grenzen dieses Reviews:** gelesen, nicht gefahren (Abschnitt 1). Die
Größenordnungen in 3.3 sind Schätzungen. Ein Angreifer, der mehr weiß als
dieses Dokument, findet, was drei Durchgänge nicht gefunden haben — die
CSP (K-2) ist genau für diesen Fall.
