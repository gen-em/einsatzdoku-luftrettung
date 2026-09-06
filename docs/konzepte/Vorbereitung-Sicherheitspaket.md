# Vorbereitung — Sicherheitspaket: Vorschläge zu den Befunden des Krypto-Reviews

**Zielpfad:** `docs/konzepte/Vorbereitung-Sicherheitspaket.md` — dasselbe
Muster wie `Vorbereitung-S9-Problemsammlung.md`: die Sammlung mit Optionen,
Preisen und den offenen Entscheidungen, aus der nach dem Go des
Auftraggebers ein Konzept nach K1 entsteht. **Anlass:** Frage vom
06.09.2026 zu `Review-Krypto-Sicherheit.md`: *„Kann man diese Punkte
angehen? Oder sind sie schon adressiert? Was sind deine Vorschläge?"*
**Status:** Vorschläge, nichts umgesetzt. **Sieben Entscheidungen sind am
06.09.2026 gefallen (Abschnitt 6a), drei stehen offen (Abschnitt 6b).**
Rahmenplan Fassung 28 (R74) und Backlog 117–136 tragen den Stand.
**Stand:** 06.09.2026, Web 14.2.2, Uhr 3.0.2, Android 0.13.0.

**Bezeichner:** Die Vorschläge heißen **SP-1 bis SP-14**, die offenen
Entscheidungen **F-SP-1 bis F-SP-10**. Befundnummern (K-1 …, AN-1 …) sind
die aus dem Review.

---

## 0. Kurzantwort: Was ist schon adressiert, was nicht

| Weg | Was der Plan schon vorsieht | Was fehlt | Vorschlag |
|---|---|---|---|
| **1 — Schwaches Passwort plus Datenbankabzug** (K-3) | Nichts, das die Rechnung ändert. R37.11 (P5) bringt das Notfallblatt für den Wiederherstellungsschlüssel; `pwquality.js` prüft im Browser | Der Datenbankabzug allein genügt heute für den Offline-Angriff; die Rundenzahl liegt unter der Empfehlung | **SP-1** Rundenzahl 600 000 (sofort) · **SP-2** Passwortregeln (sofort) · **SP-3** Server-Anteil am Datenschlüssel — macht den Abzug **allein** wertlos (Konzept, Hauptstufe) · SP-3b Argon2id als P6-Frage |
| **2 — Wer Code ausliefern kann** (K-2, K-16) | **Beschlossen, nicht umgesetzt:** R67 beendet den Autodeploy auf Produktion (Staging, Freigabetor, Produktion nur nach Freigabe der Betreiberin); CSP in P5 (Backlog 8) | Branch-Schutz und 2FA der GitHub-Organisation (nur der Betreiber sieht es); eine CSP ist geplant, aber ohne Bauplan; niemand merkt eine Manipulation auf dem Server | **SP-4** GitHub-Zuarbeit (heute) · **SP-5** CSP mit Nonce, Bauplan (P5) · **SP-6** Integritätswache (klein, jederzeit) · **SP-7** Schlüssel als `CryptoKey` (P6-Frage) |
| **3 — Klartextdaten neben dem Chiffretext** (K-1) | Backlog 43 mit `Konzept-V1-Ortsdaten.md` („C jetzt, B als Ziel"); P6 entscheidet Weg B; R41: die Datenschutzerklärung nennt die Grenze | Weg C ist **nicht** umgesetzt (`CLAUDE.md` 4 verspricht weiter den verschlüsselten Einsatzort); Weg B hat kein Design — die Frage „hat die Uhr überhaupt Krypto?" war offen | **SP-8** Weg C jetzt (nur Dokumente) · **SP-9** Weg B mit **Konto-Schlüsselpaar** — Design-Skizze liegt hier, die Uhr **kann** es (ECDH P-256, AES-256, HMAC ab Connect IQ 3.0.0; geprüft am 06.09.2026); löst zugleich Backlog 53 und die Frage des verlorenen Geräts |

Die neuen Befunde: **SP-10** Adminpakete versiegeln (K-4) · **SP-11**
Zweitfaktor für alle Konten (K-5; R38 plant ihn nur für Admins) · **SP-12**
Photon (K-6; S9 PS-1 ist der Entscheidungspunkt) · **SP-13** die kleinen
Web-Punkte K-7 bis K-15 als **Sofortpaket** · **SP-14** die Android-Punkte
AN-1 bis AN-5 als Kleinauslieferung.

**Empfohlene Reihenfolge:** SP-4 heute (kein Code) → Sofortpaket SP-1, SP-2,
SP-8, SP-13, SP-14 (eine Kleinauslieferung Web, eine Android, kein Konzept
nach K1, Muster R42) → **Zwischenpaket „Sicherheit"** mit SP-3, SP-6 und
SP-10 (Konzept nach K1, Fable) → P5 nimmt SP-5 und SP-11 als Präzisierung
von Backlog 8 und R38 auf → P6 entscheidet SP-7, SP-3b, Passkeys und
**SP-9** als eigene Phase.

---

## 1. Weg 1 — das Passwort ist die einzige Schranke

### SP-1 — Rundenzahl auf 600 000

**Was:** `KDF_ITER_ZIEL = 600000`, `KDF_ITER_LISTE = [600000, 320000]`
(`db.php:673-674`). Der Rest ist gebaut: Die stille Anhebung (M2-01,
`api/kdf_upgrade.php`) zieht jedes Konto beim nächsten Anmelden nach; die
Anmeldung rechnet in der Übergangszeit zweimal (Handbuch 5 sagt es schon).
**Preis:** gemessen 165 → 285 ms je Ableitung auf einem CPU-Kern; auf einem
langsamen Telefon rund 0,3 s mehr je Anmeldung, in der Übergangszeit das
Doppelte. Für den Angreifer halbiert sich die Rate. **Prüfung:** ein Konto
im Referenzbestand durch die Anhebung fahren, `tools/fristprobe/`-Muster;
Wartungsseite „Schlüsselableitung" muss leer bleiben. **Rang:** Neben.
**Dokumente:** `Technik.md` 4 (Absatz „Stille Anhebung"), Handbuch 5,
Changelog. Sobald alle Konten nachgezogen sind, den Altwert aus der Liste
streichen (Wartungsseite meldet, wer noch darauf steht).

### SP-2 — Passwortregeln, die der Browser durchsetzen kann

Der Server sieht das Passwort nie; die einzige Stelle, an der eine Regel
greift, ist `pwquality.js` — und die umgeht nur, wer sich selbst schaden
will. Deshalb lohnt es sich, sie ernst zu nehmen:

- Mindestlänge **12** statt 10 (`MIN_LAENGE`), Passphrasen ausdrücklich
  empfohlen („vier zufällige Wörter") — der Stärkeanzeiger belohnt Länge
  bereits (`pwquality.js:145-153`).
- Die Sperrliste um Muster erweitern, die hier naheliegen: Ortsnamen des
  Standorts, „Notarzt", „Rettung", Jahreszahlen mit Sonderzeichen.
  Keine 800-kB-Wörterbuchbibliothek (zxcvbn) — sie wäre ein neuer
  Fremdbestandteil für einen Fall, den zwölf Zeichen Mindestlänge besser
  abdecken.
- Handbuch 3.1 und das Notfallblatt (R37.11) sagen den Satz, der die
  Bauform erklärt: *Wer die Datenbank stiehlt, kann dein Passwort
  ausprobieren, so oft er will. Der einzige Schutz ist ein Passwort, das
  sich nicht raten lässt.*

**Preis:** klein. **Rang:** Korrektur (Text) bzw. Neben (Regel).

### SP-3 — Server-Anteil am Datenschlüssel („Pepper")

**Die Idee.** Heute ist der Datenschlüssel die erste Hälfte von
PBKDF2(Passwort, Salz). Wer die Datenbank hat, hat Salz, Rundenzahl und
die Hülle `pat_wrap_pw` und kann offline raten. Wenn der Datenschlüssel
zusätzlich von einem Geheimnis abhängt, das **nicht in der Datenbank**
liegt, ist der Abzug allein wertlos — genau der Gedanke, aus dem der
Serverschlüssel in `config.php` entstanden ist (`serverkrypto_lib.php:23-29`:
„Der Zweck ist der Fall „jemand hat die Datenbank""). Das Vorbild ist der
„Secret Key" von 1Password, nur ohne dass die NutzerIn etwas abtippen
muss.

**Mechanik.**

1. `config.php` bekommt ein zweites Geheimnis `kdf_pepper` (32 Byte,
   erzeugt vom Installer bzw. nachgetragen wie der Serverschlüssel).
2. Der Server leitet je Konto ab: `kontoPepper = HMAC-SHA256(kdf_pepper,
   kdf_salt)` — jedes Konto bekommt einen eigenen Wert, und wer den eines
   Kontos kennt, kann damit kein anderes angreifen.
3. Der Browser rechnet wie heute PBKDF2 und bildet daraus das Anmelde-Token
   (unverändert — die Anmeldung braucht den Pepper nicht). Den
   Datenschlüssel bildet er **erst auf der angemeldeten Seite**:
   `dk = HKDF-SHA256(ikm = PBKDF2-Hälfte, salt = kontoPepper,
   info = "edk-dk-2")` — WebCrypto kann HKDF, es kostet nichts. Der
   Pepper kommt mit `ui_krypto_bootstrap()` wie heute Salz und Rundenzahl.
4. `pat_wrap_pw` wird mit `dk` gehüllt und trägt die Kennung `edk2:`;
   `pat_wrap_rc` bleibt **unverändert** und **ohne Pepper** — der
   Wiederherstellungsschlüssel muss unabhängig vom Server funktionieren.
5. Umstellung wie M2-01: Beim nächsten Anmelden öffnet der Browser die alte
   Hülle (`edk1:`, ohne Pepper), hüllt neu und schickt beides mit dem alten
   Token an den bestehenden Endpunkt `api/kdf_upgrade.php` (verallgemeinert
   zu „Hüllenfassung"). Nichts läuft auf einmal, nichts ohne Nachweis.

**Was es bringt.** Angreifer A (nur Datenbank): kein Offline-Angriff mehr
möglich — er kennt den Pepper nicht und kann ihn nur über eine Anmeldung
bekommen, die er ohne Passwort nicht schafft. Angreifer B (Datenbank plus
`config.php`): wie heute. Ein angemeldeter Insider: nur sein eigener Wert.
Ein Postfach-Angreifer mit Reset-Link bekommt auf `pw_handling.php` den
Wert seines Kontos (er braucht ihn, um die neue Hülle zu bauen) — zusammen
mit einem Datenbankabzug wäre das der heutige Zustand für **dieses eine**
Konto, nicht für alle.

**Was es kostet — und das gehört ausgesprochen.**

- `config.php` wird vom Backup-Schlüsselträger zum **Schlüsselträger aller
  Konten**: Geht `kdf_pepper` verloren, öffnet keine Passworthülle mehr,
  und **jede** NutzerIn muss über den Wiederherstellungsschlüssel ein neues
  Passwort setzen (Daten bleiben erhalten — `pat_wrap_rc` hängt nicht am
  Pepper). Das Wiederanlaufpaket (`Technik.md` Runbook) trägt ihn deshalb
  mit, und die Wartungsseite meldet einen fehlenden oder falschen Wert wie
  beim Serverschlüssel.
- Das Demo-Konto bleibt bei `edk1:` ohne Pepper — seine Fixture muss auf
  jeder Installation aufgehen; `api/kdf_upgrade.php` überspringt es
  bereits (E-P1-19).
- Berührt: `crypto.js`, `login.php`, `unlock.js`, `ui.php`
  (`ui_krypto_bootstrap`), `auth_guard.php`, `pw_handling.php`,
  `einstellungen.php` (Passwortwechsel), `api/kdf_upgrade.php`,
  `serverkrypto_lib.php`, `install.php`, `config.example.php`,
  Wartungsseite; Dokumente: `Technik.md` 4 und Runbook, Handbuch 5,
  `Backup-Format.md` (keine Änderung am Format — festhalten), Changelog.
- **Rang: Haupt** (Verschlüsselung). Konzept nach K1, Fable (R14);
  Prüfung mit Referenzbestand, Umstellungslauf, Reset-Weg, Freigabeweg,
  Demo-Reset.

**Alternative, verworfen:** ein Gerätegeheimnis nach 1Password-Art
(Zufallswert im `localStorage` jedes Geräts, auf dem Notfallblatt
gedruckt). Schützt auch gegen B, kostet aber jede NutzerIn auf jedem neuen
Gerät ein Abtippen und macht den Schlüsselverlust noch wahrscheinlicher —
R37.11 nennt ihn schon heute Support-Thema Nr. 1.

#### Archivierung des Server-Anteils — Antwort auf die Rückfrage zu F-SP-2

Die Rückfrage vom 06.09.2026: *Kann die BetreiberIn den Schlüssel
sicherheitshalber archivieren, damit ein Problem mit `config.php` nicht
gleichbedeutend ist mit „alle müssen das Passwort zurücksetzen"?* — **Ja,
und das ist nicht Kür, sondern Teil des Entwurfs.** Der Serverschlüssel
hat das Muster schon: `serverkrypto_lib.php:30-35` verlangt ein
**Wiederanlaufpaket** aus `config.php`, Serverschlüssel und Zugang zum
Backup-Ziel, getrennt aufbewahrt; das Runbook in `Technik.md` sagt es
noch einmal. Der Server-Anteil kommt in dasselbe Paket. Vier Stücke machen
daraus etwas, das im Ernstfall wirklich trägt:

1. **Das Schlüsselblatt.** Die Wartungsseite druckt auf Knopfdruck ein
   Blatt mit Serverschlüssel und Server-Anteil (64 Hexzeichen in
   Vierergruppen — abtippbar, kein QR-Fremdbestandteil nötig), je mit
   **Kennung** (die ersten acht Zeichen von SHA-256 des Werts) und Datum.
   Zwei Ausdrucke, zwei Orte — Betriebsakte (R72 sieht sie ausdrücklich
   außerhalb des Repositoriums vor) und ein Passwortmanager der
   Betreiberin. Das Blatt ist das Archiv; es entsteht einmal bei der
   Einrichtung und noch einmal nach jeder Rotation.
2. **Die Kennung im Betrieb.** Beim ersten Gebrauch legt der Server die
   Kennung des Server-Anteils in `app_state` ab. Jede angemeldete Seite
   vergleicht sie mit dem, was in `config.php` steht. Weicht sie ab, gibt
   `ui_krypto_bootstrap()` den Wert **nicht** heraus, und die Seite sagt
   „Der Server-Anteil der Verschlüsselung fehlt oder ist nicht der, mit dem
   die Hüllen gebaut wurden (Kennung `ab12cd34` erwartet) — Administration
   verständigen" statt „Passwort falsch". Die Wartungsseite meldet dasselbe
   mit der erwarteten Kennung, sodass die Betreiberin am Schlüsselblatt
   sofort sieht, welches der richtige Wert ist. Dasselbe Muster wie
   `sk_oeffnen()` → `null` → „mit einem anderen Serverschlüssel
   gespeichert".
3. **Der Nachtragen-Weg.** Adminbereich → „Serverschlüssel nachtragen"
   (`Technik.md` Runbook, `serverkrypto_lib.php:245`, schreibt
   `config.neu.php`) bekommt ein zweites Feld. Einfügen vom Blatt, Kennung
   wird geprüft, erst dann geschrieben. Ein falsch abgetippter Wert kommt
   nie in `config.php`.
4. **Rotation, von Anfang an vorgesehen.** Ist `config.php` **bekannt
   geworden** (nicht verloren), wird der Anteil gewechselt: neuer Wert als
   `kdf_pepper`, alter als `kdf_pepper_alt`; die stille Umstellung hüllt
   jedes Konto beim nächsten Anmelden um (der Browser bekommt beide
   Werte, öffnet mit dem alten, hüllt mit dem neuen — derselbe Weg wie bei
   der Rundenanhebung); die Wartungsseite zählt, wer noch auf dem alten
   steht; danach fällt `kdf_pepper_alt` weg. Der Wiederherstellungs-
   schlüssel ist von alledem unberührt.

**Was bleibt, wenn alles schiefgeht** — `config.php` weg **und** beide
Blätter weg: Kein Datenverlust. `pat_wrap_rc` hängt nicht am Server-Anteil;
jede NutzerIn setzt über den Wiederherstellungsschlüssel ein neues Passwort,
und die neue Hülle entsteht unter einem neuen Anteil. Das ist der Fall, den
die Rückfrage meint, und er ist mit dem Schlüsselblatt ein Fehler zweiter
Ordnung: Er braucht drei verlorene Dinge an drei Orten.

**Was nicht ins Archiv gehört:** Der Anteil steht **nicht** in der
Datenbank und **nicht** im Komplettbackup (das ist mit dem Serverschlüssel
aus derselben `config.php` versiegelt — ein Backup, das den Schlüssel zu
sich selbst enthält, wäre keines; dieselbe Regel gilt heute schon für den
Serverschlüssel, `serverkrypto_lib.php:23-29`).

### SP-3b — Argon2id (P6-Frage)

WebCrypto kennt Argon2 nicht. Es bräuchte eine vendorierte WASM-Bibliothek
(etwa `argon2-browser`, ~50 kB), einen dritten Ableitungsweg in
`crypto.js`, eine Fassungskennung je Konto und Speicher von 64 MB je
Ableitung — auf einem alten Telefon spürbar. Gewinn: Grafikkarten
verlieren ihren Vorteil, die Rate des Angreifers fällt um ein bis zwei
Größenordnungen. **Nach SP-3 ist der Gewinn klein**, weil der Abzug allein
dann nichts mehr nützt. Vorschlag: als Frage ins Bedrohungsmodell P6,
Entscheidung nach SP-3.

---

## 2. Weg 2 — wer Code ausliefern kann

### SP-4 — GitHub-Zuarbeit (kein Code, heute)

1. Branch-Schutz auf `main`: Pull Request Pflicht, mindestens ein Review,
   keine Umgehung für Administratoren, keine Force-Pushes.
2. Zweifaktor-Zwang in der Organisation `gen-em`.
3. Die Deploy-Secrets (`FTP_*`) auf die GitHub-Umgebung „produktion" mit
   Freigabe der Betreiberin verschieben — das ist Schritt 1 von R67 und
   lässt sich **vor** dem Staging-Aufbau ziehen: Der bestehende Workflow
   bekommt `environment: produktion`, und jeder Lauf wartet auf ein Ja.
4. FTP-Zugangsdaten beim Hoster rotieren, sobald das steht, damit kein
   älterer Ort sie noch kennt.

**Das Repositorium ist öffentlich** (`gen-em/einsatzdoku-luftrettung`,
geprüft am 06.09.2026): Branch-Schutz, Regelsätze und Umgebungs-Freigaben
stehen damit ohne bezahlten Plan zur Verfügung — Punkt 1 bis 3 sind
Häkchen, keine Beschaffung. Und weil der Code öffentlich ist, kennt ein
Angreifer jede Zeile; die kleinen Befunde K-8 bis K-11 sind damit nicht
theoretisch, sondern nachlesbar.

**Preis:** eine halbe Stunde, kein Deploy. **Wirkung:** Angreifer C
braucht ab dann zwei Konten oder den Hoster.

### SP-5 — Content-Security-Policy mit Nonce (Bauplan für P5, Backlog 8)

Die Bestandsaufnahme vom 06.09.2026 macht die CSP **enger möglich, als
Backlog 8 annimmt**: **0** Inline-Ereignisbehandler (`onclick=` u. ä.),
**1** `style=`-Attribut (`index.php`), **0** `javascript:`-Adressen,
16 Seiten mit `<script>`-Blöcken, alle über `ui_seite_start()`.

**Vorschlag:**

```
Content-Security-Policy:
  default-src 'none';
  script-src 'self' 'nonce-<zufall>';
  style-src 'self' 'unsafe-inline';           # Leaflet setzt Stile per CSSOM, das ist erlaubt; das eine Attribut in index.php wird umgebaut, dann fällt 'unsafe-inline'
  img-src 'self' data: blob: https://tile.openstreetmap.org https://tile.openmaps.fr https://*.tile.opentopomap.org https://server.arcgisonline.com;
  connect-src 'self' https://photon.komoot.io;
  font-src 'self';
  worker-src 'self' blob:;                     # zip.js
  frame-ancestors 'none'; base-uri 'none'; form-action 'self'; object-src 'none'
```

- Der Nonce entsteht je Anfrage in `ui_seite_start()` und wird an jeden
  `<script>`-Block gehängt — **eine** Stelle, weil alle Seiten die Hülle
  benutzen (P3 hat genau dafür gesorgt).
- **Zuerst `Content-Security-Policy-Report-Only`** mit einem
  `report-to`-Endpunkt in `server/api/csp_bericht.php` (schreibt Kennung
  und blockierte Quelle ins Fehlerprotokoll, ratenbegrenzt) — zwei Wochen
  im Betrieb, dann scharf. So fällt jede vergessene Quelle auf, bevor sie
  eine Seite bricht.
- HSTS um `includeSubDomains` ergänzen, sobald geklärt ist, dass keine
  Subdomain ohne TLS läuft; `Permissions-Policy: geolocation=(self),
  camera=(), microphone=()` (nur `ortswahl.js` nutzt Geolocation).
- **Was die CSP nicht kann:** Angreifer C. Wer den Server hat, setzt den
  Nonce selbst. Sie ist die Linie gegen D (XSS), nichts anderes.

**Preis:** ein Arbeitspaket (Bilderlauf über alle 30 Seiten, Konsole leer,
Import mit zip.js, Karten aller vier Anbieter, Export). **Rang:** Neben.

### SP-6 — Integritätswache (klein, jederzeit)

Eine GitHub-Action, täglich und nach jedem Deploy: Sie lädt von der
Produktivinstallation die statischen Skripte (`assets/crypto.js`,
`keyguard.js`, `unlock.js`, `login.php`-Inline-Block nach Normalisierung)
und vergleicht ihre SHA-256 mit dem ausgelieferten Tag. Weicht etwas ab,
wird der Lauf rot und eine Mail geht an die Betreiberin.

- **Was sie erkennt:** eine per FTP oder Hoster-Panel veränderte
  Auslieferung — der wahrscheinlichste Weg für Angreifer C ohne
  GitHub-Konto.
- **Was sie nicht erkennt:** einen Angreifer mit Push-Recht (er ändert
  beides — dagegen steht SP-4) und veränderten PHP-Code, der nicht
  ausgeliefert wird (der `login.php`-Vergleich fängt den Teil, der
  Passwörter berührt).
- **Preis:** ein Nachmittag; kein Serverzugriff nötig, nur HTTPS.
  Läuft in `.github/workflows/`, keine Versionsstufe.
- **Keine eigene Mailadresse nötig:** Ein fehlgeschlagener Lauf löst die
  gewöhnliche GitHub-Benachrichtigung an die Person aus, die den Workflow
  zuletzt geändert hat (Einstellung „Actions" in den
  GitHub-Benachrichtigungen). Eine eigene Adresse bräuchte es nur, wenn
  die Meldung woanders ankommen soll. Die frühere Formulierung „Adresse
  ist Zuarbeit" ist damit zurückgenommen.

### SP-7 — Schlüssel als nicht-extrahierbarer `CryptoKey` (P6-Frage)

Heute liegen Daten- und Inhaltsschlüssel als Hex im `sessionStorage`
(`crypto.js:329-340`). Ein `CryptoKey` mit `extractable: false` in
IndexedDB ließe ein XSS zwar weiterhin entschlüsseln, aber den Schlüssel
**nicht mitnehmen** — nach dem Schließen der Lücke ist der Angreifer
draußen. Der Preis ist ein anderes Lebensdauermodell: IndexedDB ist nicht
tab-gebunden („ein Tab, ein Schlüssel", Handbuch 5); die Frist aus
`keyguard.js` müsste den Eintrag selbst räumen. Gewinn mäßig, Umbau mittel
(alle `getContentKey`-Aufrufer, `aesKey()`-Fach, `tools/fristprobe/`).
Vorschlag: Frage für das Bedrohungsmodell P6, nicht vorher.

---

## 3. Weg 3 — die Klartextdaten

### SP-8 — Weg C jetzt: die Zusage auf das eingrenzen, was sie hält

Nur Dokumente, keine Versionsstufe: `CLAUDE.md` Abschnitt 4 („Diagnose,
Alter und Einsatzort werden im Browser ver- und entschlüsselt" → dazu der
Satz, dass Spur, Phasenkoordinaten, Zielklinik, Zeiten und
Reanimationsereignisse im Klartext liegen und der Einsatzort daraus
rekonstruierbar ist), `Technik.md` 4.98 (steht dort schon, wird zur
Zusage), README, Handbuch 5, und der Entwurf für die Datenschutzerklärung
(R41 verlangt genau das). **Ein Nachmittag.** Er macht nichts sicherer —
er macht das Projekt ehrlich, und er ist die Voraussetzung dafür, dass die
Frage nach Weg B nicht mehr als Widerspruch im Raum steht.

### SP-9 — Weg B: Schlüssel auf die Uhr, als Konto-Schlüsselpaar

`Konzept-V1-Ortsdaten.md` nennt drei offene Punkte für Weg B: Wie kommt
ein Schlüssel auf die Uhr, was bedeutet ein verlorenes Gerät, was passiert
beim Passwortwechsel. **Ein Schlüsselpaar je Konto beantwortet alle drei —
und Backlog 53 gleich mit.**

**Die Fähigkeiten der Uhr, nachgesehen am 06.09.2026** (Connect IQ
API-Dokumentation, `Toybox.Cryptography`, alles ab **API 3.0.0** — die
Zielgeräte stehen auf 3.1.0, `watch/manifest.xml:13`): `KeyPair` mit
`KEY_PAIR_ELLIPTIC_CURVE_SECP256R1`, `KeyAgreement` mit
`KEY_AGREEMENT_ECDH`, `Cipher` mit `CIPHER_AES256` in `MODE_CBC`,
`HashBasedMessageAuthenticationCode` mit `HASH_SHA256`, `randomBytes()`.
**Kein GCM.** Damit ist der Weg technisch offen: ECDH auf P-256, AES-256-
CBC und HMAC-SHA256 als „erst verschlüsseln, dann prüfen" — jedes dieser
Stücke kann auch WebCrypto, und Android ohnehin.

**Design-Skizze.**

1. **Schlüsselpaar** (ECDH P-256) entsteht im Browser bei der Erstvergabe
   (`pw_handling.php`) und für Bestandskonten still beim nächsten Anmelden.
   Privater Teil unter dem Inhaltsschlüssel gehüllt → `users.acc_priv_wrap`
   (wie `pat_wrap_*`), öffentlicher Teil im Klartext → `users.acc_pub`.
   **Passwortwechsel kostet nichts** — der Inhaltsschlüssel ändert sich
   nicht, also auch die Hülle des privaten Teils nicht.
2. **Das Gerät bekommt nur den öffentlichen Teil**: bei der Kopplung in der
   Antwort auf `bestaetigen`, für bereits gekoppelte Geräte in der Antwort
   von `ingest.php` (alte Fassungen ignorieren unbekannte Schlüssel, der
   Vertrag erlaubt das).
3. **Vor dem Upload verschlüsselt das Gerät** je Spurstück und je Phase:
   flüchtiges Schlüsselpaar → ECDH mit `acc_pub` → Schlüssel per HMAC
   ableiten → AES-256-CBC mit Zufalls-IV → HMAC-SHA256 über IV und
   Chiffretext. Nutzlast: `{eph_pub, iv, ct, mac}`, Base64 im JSON. Der
   flüchtige private Teil wird sofort verworfen.
4. **Der Server speichert Chiffretext** je Spurstück und Phase; `seq` und
   Zeitstempel bleiben im Klartext, damit Reihenfolge, Zählung,
   Phasenzuordnung über die Zeit und Nachlieferung weiter serverseitig
   funktionieren (`Konzept-V1-Ortsdaten.md` 3.1: der Server rechnet mit
   den Koordinaten nicht — nur mit den Zeiten).
5. **Der Browser entschlüsselt** mit dem privaten Teil (aus der Hülle,
   nach dem Entsperren) und zeichnet wie heute.
6. **Verlorenes Gerät:** Es trägt nur den öffentlichen Teil und den
   Geräteschlüssel — es kann hochladen, aber **nichts** entschlüsseln, auch
   nicht, was es selbst gesendet hat. Die Frage aus Konzept-V1 ist damit
   beantwortet: Ein Verlust kostet das Trennen, nicht mehr.
7. **Backlog 53** — versiegelte Serversicherungen ohne Browser — bekommt
   denselben Schlüssel: Der Server verschlüsselt mit `acc_pub`, öffnen kann
   nur der Browser.

**Was es kostet, und das ist der Grund für eine eigene Phase:** Jede
Serverfunktion, die heute Koordinaten liest, wandert in den Browser oder
entfällt — Ausdünnung Stufe 3 (`spur_lib.php`, Douglas-Peucker), der
serverseitige GPX-Abruf (4.97b), das Schneiden (4.97e), das Verschieben,
die Ortshöhe (`site_elevation_lib.php`; sie ginge in den `pat_blob`),
die Zusammenführung. Dazu: Uhr-Vertrag (`JSON-Vertrag.md`), Uhr- und
Android-Code, SPUR-Format (ein SPUR2 als Liste versiegelter Stücke, weiter
nur über `spur_lib.php`), Backup Fassung 4 (Spurteile sind dann schon
Chiffretext — das passt), ein Bestandsweg (Stichtag oder Umschlüsselung
alter Spuren im Browser, F-SP-6). Reanimationsereignisse und Zielklinik
können denselben Umschlag nehmen; dann verliert die Statistik
„Reanimationen je Jahr" und „Fahrten je Klinik" ihre serverseitige Zählung
(Entscheidung F-SP-7). **Rang: Haupt** auf allen drei Zählungen; Konzept
nach K1 mit Fable; **entschieden als S11, Schritt 12a des Rahmenplans:
nach P6 und vor der Öffnung** (F-SP-5, F-SP-6). Der Altbestand wird
**nicht** über einen Produktweg umgeschlüsselt, sondern über ein
**Einmalwerkzeug im Browser** — dort liegt der private Schlüssel —, das
für das eine bestehende Konto läuft und danach aus dem Repositorium
entfernt wird. Das setzt voraus, dass zu diesem Zeitpunkt nur dieses eine
Konto Bestand hat; mit der Öffnung gälte die Entscheidung nicht mehr.

---

## 4. Die neuen Befunde

### SP-10 — Adminpakete versiegeln, FTP abschaffen (K-4)

`sk_versiegeln($klartext, $zweck)` (`serverkrypto_lib.php:110`) tut heute
für Backup-Ziel-Zugänge und Komplettbackup, was den Adminpaketen fehlt.
Jedes Teil des Paket-ZIPs wird damit versiegelt (Zweck `adminpaket`), der
Freigabeweg öffnet serverseitig (`edbak_paket_teil_lesen()` — Chiffretext
der `pat_blob` bleibt Chiffretext), alte unversiegelte Pakete bleiben
lesbar (Kennung `edsk1:` vorn, wie überall). Ohne Serverschlüssel wird
nicht gesichert — dieselbe Regel wie beim Komplettbackup. Das Protokoll
`ftp` verschwindet aus der Auswahl neuer Ziele; bestehende `ftp`-Ziele
bekommen einen roten Hinweis und werden nicht mehr beschickt, bis sie
umgestellt sind. **Preis:** klein bis mittel; `Backup-Format.md` 5 wird
umgeschrieben (die heutige Begründung ist überholt), `Technik.md` 4.97c,
Handbuch. **Rang:** Neben. Gehört ins Zwischenpaket zu SP-3, weil beide
den Serverschlüssel und das Wiederanlaufpaket berühren.

### SP-11 — Zweitfaktor für alle Konten (K-5)

R38 beschließt TOTP (RFC 6238, ohne Fremdquelle) für Admin-Konten in P5.
Vorschlag: **für alle Konten anbieten, für Admins Pflicht.** Mechanik: das
Geheimnis entsteht serverseitig, wird mit `sk_versiegeln()` abgelegt
(ein Datenbankabzug liefert es nicht), erscheint einmalig als
`otpauth://`-Adresse und Base32-Text (ein QR-Erzeuger wäre ein neuer
Fremdbestandteil — der Text genügt jeder Authenticator-App), dazu acht
Ersatzcodes gehasht. Anmeldung: Passwort → Code → Sitzung; Ratenschutz
je Konto; „dieses Gerät 30 Tage merken" als Cookie mit eigenem Hash.
**Was er schützt:** die Anmeldung, also Angreifer E (Phishing) und den
Zugang zur Hülle. **Was er nicht schützt:** den Offline-Angriff (Weg 1) —
dafür ist SP-3 da. **Preis:** mittel; P5 baut den Kern ohnehin.
**Passkeys** als Zweitfaktor brauchen eine WebAuthn-Serverbibliothek
(Fremdbestandteil) — P6-Frage; Passkeys **mit PRF** als Ersatz der
Passwortableitung (Bitwarden seit 2024) sind die weitergehende Frage
derselben Runde.

### SP-12 — Photon und Kachelserver (K-6)

Drei Wege, nach Preis:

- **(a) Jetzt, klein:** In der Datenschutzerklärung nennen (R41); ein
  Hinweis am Ortsfeld („Vorschläge von photon.komoot.io — der getippte Text
  wird dorthin gesendet"); ein **Schalter je Installation**
  „Adresssuche im Internet" — die Komponente hat die Option `adresssuche`
  bereits (`ortsfeld.js:118`), die Umkehrsuche in `ortswahl.js` folgt
  demselben Schalter. Aus heißt: Koordinaten, Plus-Codes und Karte bleiben,
  Vorschläge und Rückwärtssuche entfallen.
- **(b) Eigener Vermittler** (Proxy auf dem eigenen Server): verbirgt die
  IP-Adresse der NutzerIn, nicht den Inhalt. Nicht wert.
- **(c) Selbstbetrieb** (Photon oder Nominatim mit Deutschland-Auszug):
  Java bzw. PostGIS, zweistellige Gigabyte, nichts für geteilten Webspace
  — nur mit der Hosting-Entscheidung aus R36. Das ist die Frage von
  S9 PS-1, und sie gehört dorthin.

**Vorschlag:** (a) im Sofortpaket, (c) in S9 mit der Hosting-Frage. Die
Kachelserver bleiben in jedem Fall (die Karte ist die Anwendung); die
Datenschutzerklärung nennt sie.

### SP-13 — Sofortpaket Web: die kleinen Punkte

Eine Kleinauslieferung nach dem Muster R42 (eigener Zweig, ein Commit je
Punkt, kein Konzept nach K1, Prüfdokument mit Zahlen), **Rang Neben**:

| Nr. | Befund | Maßnahme |
|---|---|---|
| K-3 | Rundenzahl | SP-1 |
| K-8 | Login-CSRF | `csrf_field()` auch im Anmeldeformular — `login.php` startet die Sitzung schon beim GET (Z. 13), das Token liegt also vor; Fehlversuch ohne Ratenstrafe, Meldung „Formular abgelaufen, bitte erneut" |
| K-7 | E-Mail-Wechsel ohne Passwort | `old_token` wie beim Passwortwechsel (dasselbe Skript in `einstellungen.php`), `session_epoch` unverändert; Bestätigungs- und Hinweismail kommen mit R37.6 in P5 — hier nur der Nachweis. Admin-Adresswechsel: Hinweismail an die alte Adresse (der Versandweg existiert) |
| K-9 | Offene Ordner | Zwei Zeilen in `.htaccess`: `RewriteRule ^(apk\|demo)/ - [F]` — mod_rewrite läuft dort schon (HTTPS-Zwang); eine Datei in `apk/` käme wegen der Deploy-Ausnahmeliste nie an |
| K-10 | DOCTYPE-Sperre GPX | Vor der Regex: Datei muss gültiges UTF-8 sein und darf kein Nullbyte enthalten (`mb_check_encoding`, `strpos("\0")`) — GPX aus Geräten ist UTF-8, UTF-16 wird mit klarer Meldung abgewiesen |
| K-11 | Auskunft in `wiederherstellen.php` | Fehlerkennung statt Fehlertext (`fehler_kennung()` existiert), „in Betrieb" ohne Kontenzahl |
| K-12 | Freitext ohne Hinweis | Schlüssel `hinweis` im Feldkatalog für `bw_info`, `notes` und die Besatzungs-Freitexte; `days.notes` im Diensttagformular; Text „Klartext — keine Patientendaten" (Backlog 108 bringt später das Symbol) |
| K-13 | Klartext-Reste | Bauordner `sicherungen/komplett/.bau-*` nach Fehlschlag sofort räumen; Rest bleibt und wird in `Technik.md` benannt |
| K-14 | Verlorene Uhr | Handbuch 12: „Uhr verloren → sofort trennen"; **Zeitfenster ab Einsatzbeginn** für das Ersetzen von Phasen und das Anhängen von Punkten an bestehende Einsätze, danach `ok` ohne Ersetzen; Neuanlage immer; die Zahl ist **F-SP-8** |
| K-15 | Header und Escaping | `JSON_HEX_TAG\|JSON_HEX_AMP\|JSON_HEX_APOS\|JSON_HEX_QUOT` als Vorgabe in `ui_krypto_bootstrap()` und den anderen Inline-`json_encode`-Stellen; `csrf_check()` mit `(string)`-Cast; HSTS-Erweiterung mit SP-5 |
| K-6a | Photon-Hinweis und Schalter | SP-12 (a) |
| K-1c | Weg C | SP-8 (nur Dokumente, kein Versionssprung) |

Prüfung: Bilderlauf über die berührten Seiten, Wortliste, `tools/gpxprobe/`
für K-10, ein Browserlauf für K-7 und K-8 mit Zahl (Anmeldung mit und ohne
Token, E-Mail-Wechsel mit falschem Passwort).

### SP-14 — Kleinauslieferung Android (AN-1 bis AN-5)

| Nr. | Maßnahme |
|---|---|
| AN-1 | Die HTTP-Ausnahme an `BuildConfig.DEBUG` binden; im Release eine `network_security_config` mit `cleartextTrafficPermitted="false"`; der Prüffall `oertlicheAdressenBehaltenHttp` läuft nur im Debug-Buildtyp |
| AN-2 | Abgewiesene Pakete (`fehlerhaft = 1`) nach 30 Tagen und beim Trennen löschen; `dienst`-Zeilen ohne Pakete mit ihnen; Zähler im Prüfstand |
| AN-3 | Entscheidung „kein Pinning, weil feste Domain mit rotierendem Zertifikat" in `android/LIESMICH.md` festhalten — eine Zeile, aber eine, die es dann gibt |
| AN-4 | `sourceNodeId` gegen die verbundenen Knoten prüfen und Zeitstempel auf Plausibilität (nicht in der Zukunft, nicht älter als der Dienst) — Robolectric-Prüffall mit Attrappe |
| AN-5 | `distributionSha256Sum` in `gradle-wrapper.properties` (wird nur beim Herunterladen geprüft, stört den Container nicht); R8 bleibt aus, Begründung steht |

Eine Android-Versionsstufe Neben; Emulator-Lauf mit Bildern nach
`android/LIESMICH.md`.

---

## 5. Pakete und Reihenfolge

| Schritt | Inhalt | Form | Rang |
|---|---|---|---|
| 0 | SP-4 GitHub-Zuarbeit; SP-12 (a) Datenschutztext | Zuarbeit, kein Code | — |
| 1 | **Sofortpaket Web:** SP-1, SP-2, SP-8, SP-12 (a) Hinweis und Schalter, SP-13 — Rahmenplan Schritt 9a, Backlog 117–128 | Kleinauslieferung, Muster R42, Prüfdokument | Web Neben |
| 2 | **Sofortpaket Android:** SP-14 — Rahmenplan Schritt 9a, Backlog 132–135 und 114 | Kleinauslieferung | Android Neben |
| 3 | **Zwischenpaket „Sicherheit":** SP-3 Server-Anteil samt Schlüsselblatt und Kennung, SP-10 Adminpakete; SP-6 Integritätswache je nach F-SP-9 schon in Schritt 1 — Rahmenplan Schritt 9b, **S10**, vor P5 | Konzept nach K1 (Fable), Umsetzung Opus, Prüfdokument | Web Haupt |
| 4 | **P5** nimmt SP-5 (CSP-Bauplan) und SP-11 (Zweitfaktor für alle) als Präzisierung von Backlog 8 und R38 auf | im P5-Konzept | — |
| 5 | **P6-Bedrohungsmodell** entscheidet SP-3b, SP-7, Passkeys/PRF, F-SP-7 | Review R17 Stück 1 | — |
| 6 | **Weg B** (SP-9) als eigene Phase — Rahmenplan Schritt 12a, S11, vor der Öffnung | Konzept nach K1 (Fable) | Web, Uhr, Android Haupt |

Schritt 1 und 2 sind unabhängig voneinander und von 3. Schritt 3 ist ein
Hauptversionssprung und gehört **nicht** in die Backlog-Runde.

---

## 6a. Entschieden (06.09.2026)

| Nr. | Frage | Entscheidung |
|---|---|---|
| F-SP-1 | Sofortpaket Web und Android (Schritt 1 und 2) | **Ja, als Ganzes.** Jeder Punkt ein Commit, einzeln rücknehmbar. Rahmenplan Schritt 9a, Backlog 117–128 und 132–135 |
| F-SP-2 | SP-3 Server-Anteil am Datenschlüssel | **Ja**, mit der Rückfrage nach dem Archiv — beantwortet in SP-3 (Schlüsselblatt, Kennung, Nachtragen-Weg, Rotation); Verlust von `config.php` ist damit kein Reset für alle mehr, sondern ein Griff in die Betriebsakte. Rahmenplan Schritt 9b, S10 |
| F-SP-3 | Zweitfaktor | **Wie vorgeschlagen:** für alle Konten angeboten, für Admins Pflicht (SP-11, Backlog 131, P5 als Erweiterung von R38) |
| F-SP-5 | Weg B: Zeitpunkt | **Nach P6, als eigene Phase S11** (Ermessen an den Bearbeiter übertragen); **vor der Öffnung**, weil F-SP-6 ein einziges Konto voraussetzt. Rahmenplan Schritt 12a |
| F-SP-6 | Weg B: Altbestand | **Einmalwerkzeug.** Es gibt nur ein Konto; die alten Spuren werden einmal umgeschlüsselt — im Browser, weil nur dort der Schlüssel liegt — über eine eigene Datei, die danach verworfen wird. Kein dauerhafter Produktweg, keine Umschlüsselung „auf Vorrat" |
| F-SP-7 | Weg B: Umfang | **(c)** — Spur, Phasenkoordinaten, Reanimationsereignisse **und** Zielklinik. Folge: beide Statistiken zählt der Browser, der Klinik-Pin erscheint erst nach dem Entsperren; die Klartext-Entscheidung in `mission_fields.php:354` wird dort umgekehrt und begründet |
| F-SP-10 | Deploy-Tor | **(b)** — erst mit dem Staging-Aufbau (R40 (2), P5-Beginn). Bis dahin: Branch-Schutz und 2FA (SP-4.1, 4.2) sofort; der Autodeploy bleibt |

## 6b. Offen — drei Fragen, ausführlicher erklärt

### F-SP-4 — Photon: was ist das Problem, was die Absicht

**Das Problem.** Wer im Einsatzformular den Einsatzort tippt, löst ab dem
dritten Zeichen und nach 400 ms Ruhe eine Anfrage an
`https://photon.komoot.io/api/?q=…` aus (`ortsfeld.js:82,360`) — mit dem
getippten Text und, wie bei jeder HTTP-Anfrage, der IP-Adresse der
NutzerIn. Wer den Ort auf der Karte wählt, schickt die Koordinate an die
Umkehrsuche (`ortswahl.js:34`). Photon ist ein kostenloser
Gemeinschaftsdienst der Firma komoot: kein Vertrag, kein
Auftragsverarbeitungsvertrag, keine Zusage über Protokollierung oder
Speicherdauer. Die Adresse des Einsatzorts — genau das Feld, das die
Anwendung Ende-zu-Ende verschlüsselt, damit **der eigene Server** es nie
sieht — geht damit im Klartext an einen **fremden** Server, jedes Mal,
während des Tippens. Die Kachelserver (OpenStreetMap, OpenTopoMap,
openmaps.fr, Esri) sehen Ähnliches gröber: den Kartenausschnitt, den die
NutzerIn ansieht, also die Gegend um den Einsatzort. Das ist kein
Angriff und kein Fehler im Code — die Anwendung tut, was sie soll —,
sondern ein **Abfluss an Dritte, den die Zusage in `CLAUDE.md` 4 nicht
nennt** („keine fremde Quelle zur Laufzeit" meint Skripte und Schriften,
nicht Datenabfragen) und den die Datenschutzerklärung nennen muss.

**Die Absicht des Vorschlags.** Nicht die Adresssuche abschaffen — sie ist
im Einsatz nützlich —, sondern drei Dinge: **Transparenz** (ein Satz am
Feld, ein Absatz im Datenschutztext), **Wahl** (ein Schalter je
Installation, damit ein Betreiber, der den Abfluss nicht will, ihn ohne
Codeänderung abstellen kann; die Komponente hat die Option `adresssuche`
bereits) und **die eigentliche Lösung dorthin, wo sie hingehört** —
Selbstbetrieb eines Geocoders ist eine Hosting-Frage (Java, PostGIS,
zweistellige Gigabyte) und wird in S9 PS-1 mit der Hosting-Entscheidung
beantwortet.

| Option | Was passiert | Folge |
|---|---|---|
| (a) Hinweis, Datenschutztext, Schalter — Vorgabe **an** | Für die heutige Installation ändert sich nichts Sichtbares außer dem Hinweis; wer will, schaltet ab | Abfluss bleibt bis S9, ist aber benannt und abschaltbar |
| (b) wie (a), Vorgabe **aus** | Adressvorschläge und Umkehrsuche sind aus, bis die Administration sie einschaltet; Koordinaten, Plus-Codes und Kartenwahl bleiben | Jede NutzerIn verliert die Vorschläge, bis jemand den Schalter findet |
| (c) Adresssuche abschaffen | Nur Koordinaten, Plus-Codes und Karte; die Karte selbst braucht weiter Kacheln | Der Abfluss der Adresse endet, der der Kacheln nicht; Bedienung im Einsatz wird spürbar schlechter |

**Vorschlag: (a).**

### F-SP-8 — Ersetzfenster der Uhr: was schon geschützt ist, und die Zahl

**Was heute schon gilt** (beim Nachsehen am 06.09.2026 gefunden):
`ingest.php:251` überspringt Einsätze mit `manual = 1` vollständig —
Metadaten, Phasen, Reanimation bleiben, nur Spurpunkte werden angehängt —,
und `manual = 1` setzt `einsatz_form.php:477`, sobald jemand im Web die
Zeiten ändert. Dazu ersetzt `ingest.php:359` Phasen nur, wenn der Upload
**mindestens so viele** bringt wie gespeichert sind. Der Fall „ich
bearbeite früher, die Uhr ist noch nicht gesynct" ist damit heute schon
abgedeckt: Der spätere Sync überschreibt die Handarbeit nicht.
Der Vorschlag (b) aus der ersten Fassung war deshalb überflüssig — und
er ist zurückgenommen.

**Was bleibt:** Ein **unbearbeiteter** Einsatz von vor drei Wochen kann
von einer verlorenen Uhr mit denselben Kennungen überschrieben werden, so
lange das Gerät nicht getrennt ist. Dagegen hilft genau das
**Zeitfenster ab Einsatzbeginn**, das der Auftraggeber vorgeschlagen hat:
Innerhalb des Fensters darf ein Gerät Phasen ersetzen und Punkte
anhängen (Nachlieferung nach Funkloch), danach antwortet `ingest.php`
mit `ok` **ohne** zu ersetzen — idempotent, kein Fehler auf der Uhr, aber
mit `kept_phases` benannt (JSON-Vertrag 5). Neue Einsätze werden immer
angenommen; sie sind sichtbar und löschbar.

| Zahl | Für | Gegen |
|---|---|---|
| **48 h** (Auftraggeber) | knapp, kleines Fenster für einen Finder | ein Freitagsdienst, der erst am Montag synchronisiert, käme nicht mehr nach |
| **72 h** (Review) | deckt das Wochenende | ein Tag mehr Fenster |
| 7 Tage | deckt Urlaub mit Uhr im Koffer | für einen Finder eine ganze Woche |

**Vorschlag: 72 h**, als Konstante in `db.php` neben `PAIR_TTL_MIN`; die
Zahl steht im Handbuch 12 bei „Uhr verloren: sofort trennen" und im
`JSON-Vertrag.md`.

### F-SP-9 — Integritätswache: was das ist

Der eine Angriff, gegen den keine Verschlüsselung im Browser hilft, ist
ein Server, der **veränderten Code** ausliefert: eine Zeile in
`crypto.js`, und das nächste Passwort geht mit. Verhindern lässt sich
das nur durch Zugangsschutz (SP-4, R67). **Erkennen** lässt es sich —
und darum geht es hier. Die Integritätswache ist eine GitHub-Action
(`.github/workflows/integritaet.yml`), die **täglich** und nach jedem
Deploy von der Produktivinstallation die öffentlich ausgelieferten
Skripte lädt — `assets/crypto.js`, `keyguard.js`, `unlock.js` und den
Inline-Skriptblock der Anmeldeseite — und ihre SHA-256 mit den Dateien
des ausgelieferten Standes im Repositorium vergleicht. Stimmt etwas
nicht, wird der Lauf rot, und GitHub schickt seine gewöhnliche
Fehlermeldung an die Person, die den Workflow zuletzt geändert hat —
**eine eigene Mailadresse braucht es nicht** (die frühere Angabe
„Adresse ist Zuarbeit" ist zurückgenommen). Sie braucht keinen
Serverzugriff, nur HTTPS; sie erkennt Manipulation per FTP oder
Hoster-Panel, nicht einen Angreifer mit Push-Recht (dagegen SP-4) und
nicht PHP-Code, der nicht ausgeliefert wird (der Vergleich der
Anmeldeseite fängt den Teil, der Passwörter berührt). Preis: ein
Nachmittag, keine Versionsstufe.

| Option | Folge |
|---|---|
| (a) jetzt, im Zweig des Sofortpakets | ab dann täglich ein stiller grüner Lauf; ein roter ist ein Alarm |
| (b) mit R67 | erst mit der Auslieferungskette in P5/P8; bis dahin merkt niemand eine Manipulation |
| (c) gar nicht | Erkennung bleibt dem Zufall überlassen |

**Vorschlag: (a).**

## 7. Was dieses Dokument nicht ist

Kein Konzept nach K1: keine Arbeitspakete mit Statusblock, kein
Prüfprotokoll. Es nennt Optionen, Preise und Entscheidungen. Sobald die
Fragen in Abschnitt 6 beantwortet sind, entsteht daraus je Schritt ein
Konzept oder — für Schritt 1 und 2 — unmittelbar die Umsetzung mit
Prüfdokument.
