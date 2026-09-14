# Konzept S10 — Sicherheit: Server-Anteil am Datenschlüssel, Adminpakete versiegelt

**Rahmenplan:** Schritt 9b (R78). **Backlog:** 139; berührt 140 (nur Abgrenzung),
141 und 146 (nur Abgrenzung), 46 (Rückbau der alten Paketfassung), 155 (Demo-Fixture).
**Vorbereitung:** `Vorbereitung-Sicherheitspaket.md` — SP-3 (mit dem Abschnitt
„Archivierung des Server-Anteils“) und SP-10; Entscheidungen F-SP-2 vom 06.09.2026.
**Review:** `Review-Krypto-Sicherheit.md` — K-3 (Weg 1), K-4. **Modell:** Konzept
Fable (R14), Umsetzung Opus (K2), **kein Fable-Schritt in der Umsetzung**.
**Ablage:** dieses Dokument, das Prüfdokument daneben
(`Pruefdokument-S10-Sicherheit.md`). Keine Mockups: S10 baut keinen neuen
Baustein und keine neue Darstellung (Abschnitt 1.6); wo eine Seite eine Karte
bekommt, ist es eine vorhandene Karte mit vorhandenen Bausteinen.
**Stand, gegen den gelesen wurde:** `main` vom 13.09.2026, Web 19.3.0, Uhr 3.1.0,
Android 0.15.0, Rahmenplan Fassung 43. Backlog-Runde 3 lief zu diesem Zeitpunkt
auf einem eigenen Zweig — **Abschnitt 8** nennt deshalb die Einträge für
Rahmenplan und Backlog als Text zum Einpflegen beim Merge, nicht als geänderte
Dateien.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 14.09.2026 — **AP1 erledigt (Web 19.7.0).** Konzept freigegeben; fünf Fragen (F-S10-1 bis -5) am 13.09.2026 mit dem Auftraggeber entschieden und als E-S10-03, -05, -12, -13, -14 übernommen. Keine offene Frage. |
> | Entschieden | E-S10-01 bis E-S10-18 (Abschnitt 2), dazu E-S10-U-01 bis -03 aus der Umsetzung (Abschnitt 2a) |
> | Offen | nichts. Zwei Zahlen werden **beim Bauen gemessen**, nicht hier gesetzt (Abschnitt 3, AP2 und AP4) |
> | Umsetzung | **AP0 und AP1 erledigt, AP2 als Nächstes.** Sechs Arbeitspakete (Abschnitt 3), eines nach dem anderen; nach jedem Paket Statusblock hier, Prüfprotokoll (Abschnitt 5), Push (K7). Voraussetzung erfüllt: Backlog-Runde 3 (PR #43) **und** Mockup-Runde 9c (PR #44) sind gemergt; `origin/main` stand am 14.09.2026 auf `3886e26`, **Web 19.6.0** |
> | Fable-Schritte der Umsetzung | keine |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP0 Ablage und Buchführung | **erledigt** 14.09.2026 | keine (nur `docs/`, `tools/`) | Rahmenplan Fassung **60**; Backlog-Vermerke an **3** Nummern (46, 139, 155); Containeraufbau **3 von 3** Engines |
> | AP1 Grundlage Server | **erledigt** 14.09.2026 | **19.7.0** | Anteilprobe **69 von 69** + Endpunktprobe **33 von 33**; `php -l` **0** Fehler in 9 Dateien; Klickprobe **43 von 43**; Kreisläufe csv **9120/0** und edbak **287 687/0**; Bilderlauf 48 Bilder **0/0/0**; Wortliste **0/0/0**; Linkprobe **117/0** |
> | AP2 Browser: Datenschlüssel mit Anteil, stille Umstellung | offen | **20.0.0** vorgesehen (Haupt) | |
> | AP3 Betrieb: Schlüsselblatt, Nachtragen, Rotation, Status | offen | 20.1.0 vorgesehen | |
> | AP4 Adminpakete versiegeln, `ftp` abschaffen | offen | 20.2.0 vorgesehen | |
> | AP5 Prüfmittel und Referenzbestand | offen | keine (nur `tools/`) | |
> | AP6 Abschluss: Dokumente, Backlog, Rahmenplan | offen | keine (nur `docs/`) | |

---

## 1. Befund — was heute im Code steht

Alles Folgende ist gelesen, nicht angenommen (Dateien und Funktionen, keine
Zeilennummern — Backlog-Konvention).

### 1.1 Die Schlüsselkette

- Aus dem Passwort leitet der Browser per PBKDF2-HMAC-SHA256 512 Bit ab
  (`EdCrypto.deriveKeys()` in `crypto.js`; Salz je Konto `users.kdf_salt`,
  Rundenzahl je Konto `users.kdf_iter`, Zielwert `KDF_ITER_ZIEL = 600000`,
  `KDF_ITER_LISTE = [600000]` — die Anhebung aus 9a ist auf `main` abgeschlossen).
  Bytes 0–31 sind der **Datenschlüssel** (`sessionStorage.edk`), Bytes 32–63
  das **Anmelde-Token**, das statt des Passworts an `login.php` geht und dort
  bcrypt-gehasht verglichen wird.
- Der **Inhaltsschlüssel** (256 Bit Zufall) liegt doppelt gehüllt:
  `users.pat_wrap_pw` = AES-256-GCM(Datenschlüssel, CK) und
  `users.pat_wrap_rc` = AES-256-GCM(SHA-256(`edk-rc:` + Wiederherstellungsschlüssel), CK).
  Beide Hüllen tragen die Chiffretext-Kennung `edk1:` — dieselbe wie jeder
  `pat_blob` (`CHIFFRE_PRAEFIX`, `WRAP_RE` in `validate_lib.php`).
- `pat_key_check` = SHA-256(`edk-ckchk:` + CK), 32 Hex; der Server vergleicht
  ihn bei jedem Umhüllen, damit nie eine Hülle mit einem anderen CK gespeichert
  wird.

**Wer die Datenbank hat, hat Salz, Rundenzahl und `pat_wrap_pw`** — und kann
das Passwort offline durchprobieren (K-3). Genau das schließt S10.

### 1.2 Die fünf Stellen, an denen der Browser aus dem Passwort die Hülle öffnet

| Stelle | Datei | Was heute passiert |
|---|---|---|
| Anmeldung | `login.php` (Inline-Skript) | leitet je Rundenzahl aus `auth_salt.php` ab; bei **einer** Zahl setzt es den Datenschlüssel sofort (`setDataKey`), bei mehreren legt es alles ins Vormerkfach `edkvor` (`merkeAbleitungen`) |
| Erste angemeldete Seite | `unlock.js` → `loeseVormerkung()` | nimmt den Datenschlüssel zur Rundenzahl des Kontos aus dem Fach; ist der Zielwert höher, öffnet es die Hülle, hüllt neu und ruft `api/kdf_upgrade.php` mit altem und neuem Token |
| Entsperrdialog | `unlock.js` → `frage()` | Passwort → `deriveKeys` → `decrypt(dataKey, PAT_WRAP)` |
| Passwortwechsel | `einstellungen.php`, Reiter Passwort | altes Passwort → Datenschlüssel → Hülle öffnen; **neues Salz im Browser** (`randomHex(16)`) → neue Hülle; Vormerkfach `edk_neu` bis zur Bestätigung |
| Export-Passwortprobe | `einstellungen.php`, Sicherungsblock | Passwort → `deriveKeys` → `decrypt(dataKey, PAT_WRAP)` als Nachweis |
| Reset / Einladung / Erstvergabe | `pw_handling.php` | **neues Salz im Browser**, neue Hülle(n); beim Reset wird der CK aus `pat_wrap_rc` geholt |

Die Seite liefert die Konstanten über `ui_krypto_bootstrap()` (`ui.php`):
`PAT_WRAP`, optional `PAT_KEY_CHECK`, `KDF_SALT`, `KDF_ITER`, `KDF_ITER_ZIEL`,
immer `CSRF`. `auth_guard.php` liest die Nutzerzeile und stellt
`$patWrapPw`, `$kdfSalt`, `$kdfIter` bereit.

### 1.3 Der Serverschlüssel

`serverkrypto_lib.php`: `server_key` in `config.php` (64 Hex), `sk_versiegeln()`
/ `sk_oeffnen()` mit Zweck in den Zusatzdaten (Kennung `edsk1:`),
`serverschluessel_eintragen()` — **erzeugt** einen neuen Schlüssel und schreibt
ihn in `config.php` (Nebendatei, Gegenprobe, `rename`, OPcache). Der Installer
(`install.php`) erzeugt ihn bei der Einrichtung. Verwendet von
`sicherungsziel_lib.php` (Zugänge der Ziele) und `komplett_lib.php`
(Komplettbackup). Oberfläche: Einstellungen → **Backup-Ziele**
(`admin_sicherungsziele.php`, Karte mit Knopf und Zeile zum Einfügen);
Statuszeile „Serverschlüssel“ in `status_lib.php`.

**Was fehlt:** Es gibt keine Kennung des Serverschlüssels und keinen Weg, einen
**vorhandenen** Wert (vom Papier) einzutragen und vor dem Schreiben zu prüfen.
SP-3 nennt `serverschluessel_eintragen()` als „Nachtragen-Weg“ — das ist er
nicht; er würfelt. Der Weg vom Blatt ist neu zu bauen (E-S10-10).

### 1.4 Die Adminpakete

`adminbackup_lib.php`, `edbak_sicherung_erzeugen()`: ZIP mit `manifest.json`
(Konto mit E-Mail und Name, `pat_wrap_rc`, `pat_key_check`, Umfang, Teileliste),
`kopf.json`, `eintraege/NNNN.json`, `spuren/NNNN.json` — **blankes JSON**,
gepackt durch das ZIP; `pat_blob` bleibt darin Chiffretext, alles Übrige
(Zeiten, Phasenkoordinaten, Zielklinik, Besatzung, E-Mail) liegt offen.
Leser: `edbak_paket_kopf_lesen()` (Manifest für Listen und Kontoseite),
`edbak_paket_teil_lesen()` (Freigabeweg, `api/adminbackup_freigabe.php`),
`edbak_paket_einspielen()` / `edbak_paket_zurueckspielen()` (Zurückspielen),
`edbak_paket_lesen()` (nur Fassung 1). Die Fassung erkennt heute die
**Dateiendung** (`.json` = 1, `.zip` = 2). `Backup-Format.md` 5 begründet die
fehlende Versiegelung mit einem Zustand vor Web 12.1.0.

**Auf der Installation gibt es keine Altpakete** (Auftraggeber, 13.09.2026) —
weder Fassung 1 noch unversiegelte Fassung 2. Das vereinfacht AP4 (E-S10-13).

### 1.5 Der Versand

`sicherungsziel_lib.php`: `SZ_PROTOKOLLE = sftp, ftps, ftp`, Adapter `ZielFtp`
(ext/ftp, TLS als eine Zeile Unterschied), `ZielSftp` (phpseclib);
`sz_pruefen_eingabe()` lässt jedes Protokoll aus `SZ_PORTS` zu;
`sz_versand_schub()` beschickt alle aktiven Ziele mit Kontopaketen und
Komplettbackups. Schema: `backup_targets.protokoll ENUM('ftp','ftps','sftp')`
(`schema.sql`, Migration in `migration_lib.php`). `ext/ftp` prüft bei FTPS kein
Zertifikat — belegt in `tools/versandprobe/LIESMICH.md`. Die Versandprobe legt
ihr Probeziel heute mit Protokoll `ftp` an.

### 1.6 Was S10 an der Oberfläche berührt

Betrieb → **Servereinstellungen** (`betrieb_server.php`: Karten Speicher,
Adresssuche, „Was hier gilt“), Betrieb → **Status** (`status_lib.php`,
Zeilen Serverschlüssel und Schlüsselableitung), Einstellungen →
**Backup-Ziele** (Karte Serverschlüssel, Zielformular mit Protokollauswahl),
Anmelde- und Entsperrwege (Meldungen). Alles mit vorhandenen Bausteinen
(`ui_karte_start`, `ui_knopf`, `ui_codeblock_lang`, `ui_meldung`, Plakette,
Schalter) — **kein neuer Baustein, kein Mockup nötig** (`CLAUDE.md` 5). Die
Druckansicht des Schlüsselblatts ist eine Seite ohne Gerüst, wie die
Wartungsseite (`wartung_seite_html()`) — auch das ist ein vorhandenes Muster.

### 1.7 Prüfmittel, die den Datenschlüssel nachrechnen

`tools/referenzdatensatz/einspielen/sitzung.py` meldet sich wie der Browser an,
liest `KDF_ITER` und `PAT_WRAP` aus der Seite und packt den CK mit dem
Datenschlüssel aus; `generator/krypto.py` liefert `ableiten()`/`entpacken()`;
`fixture/erzeugen.php` schreibt die Hüllen des Demo-Kontos in die Fixture. Dazu
`tools/freigabeprobe/` (liest Adminpakete über `edbak_paket_kopf_lesen()`),
`tools/versandprobe/` (Protokoll `ftp`), `tools/komplettprobe/`,
`tools/wiederherstellungs-probe/`.

---

## 2. Entscheidungen

**E-S10-01 Umfang und Abgrenzung.** S10 = SP-3 (Server-Anteil mit Schlüsselblatt,
Kennung, Nachtragen, Rotation) + SP-10 (Adminpakete versiegeln, `ftp`
abschaffen, Nr. 139). **Nicht** in S10: Deploy-Tor (F-SP-10 → mit Staging,
P5-Beginn; Nr. 140 bleibt so offen), Zweitfaktor (Nr. 141, P5), Argon2id /
`CryptoKey` / Passkeys (Nr. 146, P6), Weg B (S11, Nr. 43/53), CSP (Nr. 8, P5).
Wer eines davon beim Bauen „mitnehmen“ will, hält an (K4).

**E-S10-02 Das zweite Geheimnis heißt `kdf_anteil`.** 32 Byte als 64 Hexzeichen
in `config.php`, neben `server_key`; bei Rotation dazu `kdf_anteil_alt`. Der
Installer erzeugt ihn wie den Serverschlüssel (`random_bytes(32)`); eine
bestehende Installation legt ihn über die Karte aus E-S10-12 an. (Die
Vorbereitung schrieb `kdf_pepper`; der deutsche Bezeichner folgt `CLAUDE.md` 1,
und „Anteil“ ist das Wort, das Oberfläche und Handbuch benutzen.)

**E-S10-03 Der Konto-Anteil wird aus der Kontonummer abgeleitet** (F-S10-1,
entschieden 13.09.2026): `kontoAnteil = HMAC-SHA256(key = kdf_anteil als 32
Rohbyte, msg = "konto:" + users.id)`, 32 Byte, als 64 Hex an den Browser.
*Warum nicht das Salz wie in SP-3:* Passwortwechsel und Reset würfeln das
**neue** Salz im Browser; der Anteil zum neuen Salz wäre dem Browser in dem
Augenblick unbekannt, in dem er die neue Hülle baut — es bräuchte einen zweiten
Umlauf oder der Server müsste das Salz wählen. Die Kontonummer ist unveränderlich,
je Installation eindeutig, und der Anteil je Konto bleibt getrennt: Wer den Wert
eines Kontos kennt, kann daraus keinen anderen bilden (HMAC). Dass die Nummer
erratbar ist, kostet nichts — sie ist keine Zutat, die geheim sein müsste; das
Geheimnis ist `kdf_anteil`.

**E-S10-04 Der Datenschlüssel mit Anteil.** `dk = HKDF-SHA256(ikm = Bytes 0–31
der PBKDF2-Ableitung, salt = kontoAnteil, info = "edka1|dk", 256 Bit)` —
WebCrypto `deriveBits` mit `HKDF`, kein Fremdbestandteil. **Unverändert:** die
PBKDF2-Ableitung selbst, das Anmelde-Token (Bytes 32–63), `auth_salt.php`,
`login.php` serverseitig, `pat_wrap_rc` (öffnet weiter **ohne** Anteil — das
ist der Rückweg, wenn der Anteil verloren ist), der Inhaltsschlüssel, jeder
`pat_blob`, `pat_key_check`, das `.edbak`-Format, der Freigabeweg (arbeitet mit
`pat_wrap_rc`). Der Server kennt weiterhin weder Datenschlüssel noch CK: Er
kennt den Anteil, nicht die PBKDF2-Hälfte.

**E-S10-05 Hüllenkennung `edka1:<kennung>:`** (F-S10-2, entschieden 13.09.2026).
Eine Hülle mit Anteil lautet `edka1:` + Kennung des Anteils (8 Hex) + `:` +
base64(iv ‖ ct), der Chiffretext darin ist derselbe AES-256-GCM-Aufbau wie
bisher. `edk1:`-Hüllen (und Hüllen ohne Präfix) bleiben die Altfassung ohne
Anteil. *Warum nicht `edk2:`:* `EdCrypto.decrypt()` weist jede Kennung außer
`edk1:` als „neuere Programmfassung“ ab — die Kennung der Datensätze ist
etwas anderes als die Fassung der Hülle. *Warum die Anteil-Kennung im Präfix:*
Bei einer Rotation muss der Browser wissen, mit welchem von zwei Anteilen er
öffnet, und die Statusseite muss zählen können, wer noch auf dem alten steht —
beides am Präfix ablesbar, ohne dass jemand die Hülle öffnet. `pat_wrap_rc`
bekommt **kein** neues Präfix.
**Kennung** = die ersten 8 Hexzeichen von SHA-256 über die 64 Hexzeichen des
Werts in Kleinschreibung; dieselbe Rechnung in PHP (`hash('sha256', …)`) und auf
dem Schlüsselblatt. Dieselbe Kennung bekommt ab S10 auch der **Serverschlüssel**
(nur zur Anzeige und für den Nachtragen-Weg; seine Versiegelung `edsk1:` bleibt).

**E-S10-06 Auslieferung nur an die angemeldete Sitzung.** `ui_krypto_bootstrap()`
gibt `KONTO_ANTEILE` aus: ein Objekt `{ "<kennung>": "<64 hex>" }` mit dem
Anteil zum aktuellen `kdf_anteil` und — während einer Rotation — dem zum
`kdf_anteil_alt`; dazu `ANTEIL_STAND` (`'bereit'`, `'fehlt'`, `'abweichend'`,
`'demo'`). `pw_handling.php` gibt für das Konto des eingelösten Tokens den
aktuellen Anteil aus (die Seite kennt `user_id` aus `password_resets`). Das
**Demo-Konto** bekommt `null` und `'demo'`: seine Hülle bleibt `edk1:`, die
Fixture muss auf jeder Installation aufgehen (E-P1-19, Nr. 155). Kein Endpunkt
gibt den Anteil eines fremden Kontos heraus; `auth_salt.php` bleibt unberührt.

**E-S10-07 Stille Umstellung beim Anmelden, über den verallgemeinerten
Anhebungsweg.** `login.php` setzt den Datenschlüssel **nie mehr selbst**: Es
legt je Rundenzahl PBKDF2-Hälfte und Token ins Vormerkfach — auch bei einer
einzigen Rundenzahl —, denn ob die Hülle des Kontos den Anteil braucht, weiß
erst die angemeldete Seite. `loeseVormerkung()` in `unlock.js` liest das Präfix
von `PAT_WRAP`: `edka1:<k>:` → `dk = HKDF(hälfte, KONTO_ANTEILE[k])`; `edk1:` →
`dk = hälfte`. Ist die Hülle alt **und** `ANTEIL_STAND === 'bereit'`, oder trägt
sie eine Kennung, die nicht die aktuelle ist (Rotation), öffnet der Browser sie
mit dem passenden Schlüssel, hüllt mit dem aktuellen neu und schickt sie an
`api/kdf_upgrade.php` — der Endpunkt wird zur **Hüllenfassung**: Body
`{ alt_token, neu_token, neu_iter, wrap_pw, key_check }` wie heute, neu darf
`neu_iter === kdf_iter` sein, wenn `wrap_pw` ein `edka1:`-Präfix mit der
aktuellen Kennung trägt (Rundenanhebung und Hüllenumstellung laufen in
demselben Aufruf, wenn beides ansteht). Der Server prüft wie heute: altes Token
als Nachweis, Zielwert, keine Senkung, `WRAP_RE`, `pat_key_check` unverändert,
eine Transaktion, kein `session_epoch`; **neu:** das Präfix der neuen Hülle
muss die aktuelle Kennung tragen (keine Umstellung auf den Altwert), und das
Demo-Konto wird wie heute übersprungen. Ein Fehlschlag bleibt still; die alte
Hülle bleibt gültig und der Datenschlüssel der alte (M2-07-Regel).
Der entpackte CK wird wie bei der Rundenanhebung mit `setContentKey()` und
`EdKeyGuard.binden()` an die Hülle dieser Seite gebunden — die Seite trägt noch
die alte Hülle, die Datenbank schon die neue.

**E-S10-08 Eine Funktion für alle fünf Stellen.** `EdCrypto.datenschluessel(
haelfteHex, huelle, anteile)` entscheidet am Präfix der Hülle und liefert den
Datenschlüssel; `EdCrypto.huelleOeffnen(dk, huelle)` und
`EdCrypto.huelleBauen(dk, ckHex, kennung)` kapseln Präfix und Chiffre.
Entsperrdialog, Passwortwechsel, Export-Passwortprobe und Reset rufen diese drei
statt `deriveKeys().dataKeyHex` + `decrypt()`. **Neue Hüllen entstehen immer
mit dem aktuellen Anteil**, wenn einer ausgeliefert ist (`edka1:`); ohne
Anteil (`'fehlt'`, `'demo'`) als `edk1:` — der Weg bricht nicht, er stellt nur
nicht um. `WRAP_RE` in `validate_lib.php` nimmt beide Präfixe an;
`PAT_BLOB_RE` bleibt.

**E-S10-09 Zustände des Anteils und ihr Verhalten.** Der Server hält in
`app_state` die Kennung `kdf_anteil_kennung` des Anteils, mit dem die Hüllen
gebaut werden (gesetzt bei der ersten Auslieferung), und `server_key_kennung`
(gesetzt bei der ersten Versiegelung nach S10).

| Zustand | Bedingung | Verhalten |
|---|---|---|
| **nicht eingerichtet** | `config.php` ohne `kdf_anteil`, `app_state` leer | keine Auslieferung (`'fehlt'`); Hüllen bleiben `edk1:`, alles funktioniert wie vor S10; Status **rot mit Weg** („Server-Anteil anlegen“), Karte in E-S10-12 bietet Anlegen an |
| **bereit** | Kennung aus `config.php` = `app_state` | Auslieferung, stille Umstellung |
| **Rotation** | `kdf_anteil_alt` gesetzt; `app_state` = Kennung des alten | Server schreibt `app_state` auf die neue Kennung, liefert beide Anteile; Umstellung hüllt um; Status zählt je Kennung |
| **abweichend** | Kennung aus `config.php` ≠ `app_state`, auch nicht `alt`; oder `config.php` ohne Anteil bei gesetztem `app_state` | **keine Auslieferung** (`'abweichend'`); `edka1:`-Hüllen lassen sich nicht öffnen — die Seite sagt **„Der Server-Anteil der Verschlüsselung fehlt oder ist nicht der, mit dem die Hüllen gebaut wurden (Kennung `ab12cd34` erwartet). Bitte die Administration verständigen.“** statt „Passwort falsch“; Status **rot** mit derselben Kennung; die Karte bietet **Nachtragen vom Blatt** an (E-S10-10). `edk1:`-Hüllen öffnen weiter |
| **Neuanfang** | `config.php` und beide Blätter verloren | Karte: „Anteil neu erzeugen — alle Passworthüllen werden ungültig“ mit Rückfrage; setzt `app_state` auf die neue Kennung. Danach trifft jede NutzerIn mit `edka1:`-Hülle auf die Meldung „Der Server-Anteil wurde erneuert — bitte das Passwort über den Wiederherstellungsschlüssel neu setzen“ (Kennung der Hülle ist keinem ausgelieferten Anteil zuzuordnen); der Reset baut die neue Hülle. **Kein Datenverlust**, weil `pat_wrap_rc` nicht am Anteil hängt |

Die Meldungen unterscheiden **ausdrücklich nicht** zwischen „fehlt“ und
„anderer Wert“ gegenüber der NutzerIn (dieselbe Regel wie `sk_oeffnen()`); die
Betreiberin sieht den Unterschied auf Status und Karte.

**E-S10-10 Schlüsselblatt und Nachtragen vom Blatt.**
*Blatt:* eine Druckansicht (`betrieb_schluesselblatt.php`, nur BetreiberIn,
kein Gerüst, `@media print`), mit: Installationsadresse, Datum, **Serverschlüssel**
und **Server-Anteil** je als 64 Hexzeichen in Vierergruppen mit Kennung, und
drei Sätzen: wozu, wohin (zwei Ausdrucke — Betriebsakte und Passwortmanager der
Betreiberin), wann neu (nach jeder Rotation). Kein QR, kein Download als
Datei — die Seite ist zum Drucken; wer eine Datei will, druckt in PDF.
*Nachtragen:* ein Formular je Geheimnis auf der Karte (E-S10-12): Wert vom Blatt
einfügen (Gruppierung und Leerraum werden entfernt, Groß-/Kleinschreibung
gleichgestellt), der Server rechnet die Kennung und vergleicht sie mit
`app_state`; **nur bei Übereinstimmung** wird geschrieben — mit derselben
Mechanik wie `serverschluessel_eintragen()` (Nebendatei `config.neu.php`,
Gegenprobe, `rename`, OPcache, `@chmod 0640`), verallgemeinert zu
`config_eintrag_schreiben(schluessel, hex)`, der Ersetzen nur zulässt, wenn der
vorhandene Eintrag **keine** gültigen 64 Hex trägt oder ausdrücklich
„ersetzen“ angekreuzt ist (Fall: falscher Wert nach Wiederanlauf). Bei
Abweichung: „Die Kennung dieses Werts ist `xx`, erwartet ist `yy` — das ist
nicht der Wert, mit dem die Hüllen gebaut wurden. Es wurde nichts geändert.“
Ist `config.php` nicht beschreibbar, zeigt die Karte die fertige Zeile
(`ui_codeblock_lang`) — dann aber **erst nach** bestandener Kennungsprüfung.
Ohne Eintrag in `app_state` (frische Installation, nie versiegelt) gibt es
nichts zu prüfen; die Karte sagt das.

**E-S10-11 Rotation von Anfang an.** Karte: „Server-Anteil wechseln“ (Rückfrage
mit dem Satz, dass danach ein neues Blatt zu drucken ist) → neuer Wert als
`kdf_anteil`, der bisherige als `kdf_anteil_alt`, `app_state` auf die neue
Kennung, Hinweis „Blatt jetzt drucken“. Die Umstellung läuft je Konto beim
nächsten Anmelden (E-S10-07). Status zählt `pat_wrap_pw` nach Präfix:
`edka1:<neu>:` / `edka1:<alt>:` / `edk1:` (SQL `SUBSTRING`, keine Hülle wird
geöffnet). Steht niemand mehr auf dem alten, bietet die Karte „alten Anteil
entfernen“ an (löscht `kdf_anteil_alt` aus `config.php` über denselben
Schreibweg). Der Wiederherstellungsschlüssel ist von alledem unberührt. Eine
Rotation des **Serverschlüssels** ist nicht Teil von S10 (sie hieße alle
versiegelten Zugänge und Pakete umzusiegeln); die Karte sagt das, und der Weg
bleibt „Zugänge neu erfassen“ wie heute.

**E-S10-12 Ort: Betrieb → Servereinstellungen, Karte „Schlüssel des Servers“**
(F-S10-3, entschieden 13.09.2026; Ordnungsprinzip R74, E-S8-12). Die Karte
zeigt beide Geheimnisse **nur als Kennung** (nie den Wert), Zustand nach
E-S10-09, und die Knöpfe: Anlegen (je fehlendem Geheimnis), Schlüsselblatt
drucken, Nachtragen vom Blatt, Anteil wechseln, alten Anteil entfernen,
Neuanfang. Einstellungen → **Backup-Ziele** verliert die Karte
„Serverschlüssel“; ohne Serverschlüssel zeigt sie eine Meldung mit Verweis
(„Betrieb → Servereinstellungen“). `install.php` erzeugt beide Werte und sagt
im Kopf der `config.php` beide. Rolle: BetreiberIn (R75) — die Seite
Servereinstellungen ist es schon.

**E-S10-13 Adminpakete, Fassung 3** (F-S10-4: keine Altpakete, entschieden
13.09.2026). Jeder Teil des ZIP — **auch `manifest.json`** — wird mit
`sk_versiegeln($json, 'adminpaket|' . $account_key . '|' . $teilname)`
versiegelt; der Zweck bindet den Teil an Konto und Teilnamen, ein Umhängen
scheitert an der Prüfsumme. Die Fassung erkennt der Leser **am Inhalt**
(`sk_versiegelt()` am ersten Teil), nicht an der Endung: `.zip` bleibt. Ohne
Serverschlüssel entsteht kein Paket — dieselbe Regel und dieselbe Meldung wie
beim Komplettbackup; „Alle sichern“ bricht dann mit Grund ab statt still. Die
Leser (`_kopf_lesen`, `_teil_lesen`, `_einspielen`, `_zurueckspielen`) öffnen
serverseitig mit `sk_oeffnen()`; `pat_blob` bleibt darin Chiffretext, der
Freigabeweg reicht wie heute nur Chiffretext und `pat_wrap_rc` an die
angemeldete Zielnutzerin. Ein Teil, der sich nicht öffnen lässt, ist ein Fehler
mit Satz („mit einem anderen Serverschlüssel gespeichert“), nie ein leeres
Ergebnis. **Keine Umsiegelung, kein Zähler** unversiegelter Pakete: Es gibt
keine. Der Zweig für unversiegelte Fassung-2-Teile bleibt als Toleranz (eine
Abfrage am Präfix) und wird mit **Nr. 46** in P7 zusammen mit Fassung 1
ausgetragen — Nr. 46 bekommt den Vermerk (Abschnitt 8). Die Manifestfelder
`format`, `version: 3`, `web_version`, `konto`, `schluessel`, `umfang`,
`nutzlast`, `teile`, `abgelehnt` bleiben inhaltlich gleich. Das
**Komplettbackup** ist unberührt (es enthält die Datenbank, nicht
`sicherungen/`).

**E-S10-14 `ftp` weg, FTPS bleibt mit Hinweis** (F-S10-5, entschieden
13.09.2026). `SZ_PROTOKOLLE` verliert `ftp`; `sz_pruefen_eingabe()` weist es ab
(„FTP ohne Verschlüsselung wird nicht mehr angeboten — bitte SFTP oder FTPS“),
beim Anlegen und beim Ändern. Bestehende `ftp`-Ziele: Plakette **rot**
„unverschlüsselt — wird nicht beschickt“, `sz_versand_schub()` überspringt sie
mit Vermerk im Lauf, „Verbindung prüfen“ ist für sie gesperrt; das Formular
öffnet mit Protokollauswahl ohne `ftp`, sodass ein Speichern zwangsläufig
umstellt. Das `ENUM` bleibt (keine Migration — S10 braucht `update.php` nicht,
E-S10-16); der Rückbau des Werts geht mit dem P5-Schemarückbau (Nr. 168) oder
Nr. 46. FTPS behält seinen Eintrag mit dem Zusatz „prüft das Zertifikat der
Gegenstelle nicht — SFTP empfohlen“; der Satz steht auch im Handbuch 6 und im
Runbook. `tools/versandprobe/` legt das Probeziel als **FTPS** an (pyftpdlib
`TLS_FTPHandler` mit Wegwerfzertifikat; vsftpd mit `ssl_enable=YES`) und
bekommt einen Negativfall „`ftp`-Ziel wird abgewiesen / nicht beschickt“.

**E-S10-15 Prüfmittel ziehen nach** (AP5). `generator/krypto.py` bekommt
`datenschluessel(haelfte, huelle, anteile)` mit HKDF (Python `hashlib`/`hmac`
— kein neuer Fremdbestandteil); `sitzung.py` liest `KONTO_ANTEILE` und
`ANTEIL_STAND` aus der Seite und öffnet nach Präfix; **sie stellt nicht um**
(das ist Sache des Browsers), also müssen beide Hüllenfassungen für sie
lesbar sein. `fixture/erzeugen.php` schreibt die Demo-Hülle weiter als
`edk1:` und prüft das. `tools/freigabeprobe/` läuft gegen Fassung-3-Pakete;
`tools/wiederherstellungs-probe/` und `tools/komplettprobe/` bekommen je einen
Fall „`config.php` ohne Anteil → Zustand abweichend, Meldung, kein Datenverlust
nach Reset“. Referenzbestand: Konto auf `edk1:` setzen → anmelden → Präfix
`edka1:` — der **Umstellungslauf** der Abnahme.

**E-S10-16 Rang, Migration, Auslieferung.** Web **Haupt** (Verschlüsselung;
`version.php`-Kopf fortschreiben). **Keine Schemaänderung, keine Migration:**
`app_state` und alle Spalten existieren; `update.php` muss nach dem Merge
**nicht** laufen. Was nach dem Merge **fällig** ist (Abschnitt 6 des
Rahmenplans): Server-Anteil auf der Karte anlegen, Schlüsselblatt drucken
(zwei Ausdrucke), einmal anmelden und auf Status die Umstellung sehen, ein
`ftp`-Ziel — falls vorhanden — umstellen. Eine Auslieferung, eine Hauptstufe;
Korrekturstufen nach Bedarf.

**E-S10-17 Die Zusage in `CLAUDE.md` 4 wird ergänzt, nicht geändert.** Ein
Absatz: Der Datenschlüssel hängt seit S10 zusätzlich am Server-Anteil aus
`config.php`; der Server kann damit weiterhin nichts öffnen, aber ein
Datenbankabzug allein reicht nicht mehr für einen Offline-Angriff. `config.php`
ist damit Schlüsselträger aller Konten — das Wiederanlaufpaket und das
Schlüsselblatt sind Pflicht, nicht Empfehlung. Wer den Absatz zitiert, zitiert
auch den Satz zum Wiederherstellungsschlüssel (öffnet ohne Anteil).

**E-S10-18 Prüfung ohne Zahl ist keine Prüfung** (K9). Jedes Paket nennt in
Abschnitt 5 die Mittel mit Zahl; die Abnahme aus Rahmenplan 9b — Umstellungslauf
mit dem Referenzbestand, Reset-Weg, Freigabeweg, Demo-Reset (bleibt ohne Anteil),
Wartungsseite mit falscher Kennung — steht als P-01 bis P-05 im Prüfdokument.

---

## 2a. Entscheidungen aus der Umsetzung

Was das Konzept offenließ oder zweideutig sagte und beim Bauen entschieden
werden musste. Nummerierung `E-S10-U-nn`, damit sie von den Entscheidungen
des Konzepts (Abschnitt 2) unterscheidbar bleibt.

**E-S10-U-01 Die Versionsstufen je Paket** (14.09.2026, mit dem Auftraggeber).
Das Konzept legt nach der Vorgabe des Auftraggebers keine Nummern fest, sondern
nur den **Rang** (E-S10-16: Web Haupt). Verteilt wird er so:

| Paket | Stufe | warum |
|---|---|---|
| AP0 | keine | nur `docs/` und `tools/` (`CLAUDE.md` 2) |
| AP1 | **19.7.0** | Nebenstufe: neue Serverfunktionen, die **noch niemand ruft**. Kein Weg durch die Anwendung ändert sich |
| AP2 | **20.0.0** | **die Hauptnummer.** Hier hängt der Datenschlüssel tatsächlich am Server-Anteil, und jede Hülle wechselt ihr Format |
| AP3 | 20.1.0 | Nebenstufe: Karte, Schlüsselblatt, Rotation, zwei Statuszeilen |
| AP4 | 20.2.0 | Nebenstufe: Adminpakete Fassung 3, `ftp` aus der Auswahl |
| AP5 | keine | nur `tools/` |
| AP6 | keine | nur `docs/` |

*Warum die Hauptnummer nicht auf AP1 liegt:* Die Zählweise in `version.php`
misst, was sich für die Benutzung ändert („spürbar veränderte Wege durch die
Anwendung"). Nach AP1 ist das nichts — `kdf_anteil()` liefert `null`,
`ANTEIL_STAND` sagt `'fehlt'`, jede Hülle bleibt `edk1:`. Eine 20.0.0 dort
verspräche einen Umbau, den erst das nächste Paket vollzieht.

**E-S10-U-02 `app_state` wird bei der ersten Abfrage nachgetragen, nicht bei
der ersten Auslieferung** (14.09.2026). E-S10-09 sagt, die Kennung werde „bei
der ersten Auslieferung" gesetzt; die Abnahme von AP3 verlangt aber, dass
unmittelbar nach dem *Anlegen* drei Kennungen gleich sind (Karte = Blatt =
`app_state`) — und beim Anlegen ist noch nichts ausgeliefert.
Aufgelöst wird das in `anteil_zustand()`: Ist `app_state` leer **und** trägt
`config.php` einen gültigen Anteil, schreibt die Funktion die Kennung nach.
Da die Karte `anteil_zustand()` zum Aufbau ruft, steht sie unmittelbar nach
dem Anlegen drin; da `ui_krypto_bootstrap()` sie ebenfalls ruft, greift
derselbe Weg bei der ersten Auslieferung. Beide Sätze bleiben damit wahr.
Dasselbe gilt für `server_key_kennung` bei der ersten Versiegelung nach S10.

*Was dabei bewusst in Kauf genommen wird:* Auf einer Installation, die noch
nie einen Anteil hatte, übernimmt die Funktion, **was in `config.php` steht**
— auch einen falsch abgeschriebenen Wert. Das ist der Fall, den E-S10-10
ausdrücklich benennt („Ohne Eintrag in `app_state` … gibt es nichts zu
prüfen"); es gibt in diesem Zustand auch keine `edka1:`-Hülle, gegen die
geprüft werden könnte.

**E-S10-U-03 Zwei Stücke aus AP5 sind nach AP1 vorgezogen** (14.09.2026). Das
Konzept gibt `generator/krypto.py` und `sitzung.py` an AP5 (E-S10-15). Beide
werden schon in AP1 gebraucht, und das Verschieben ist kein Mehraufwand,
sondern verhindert zweierlei:

- **Die Endpunktprobe hätte eine Attrappe bauen müssen.** Der Server kann eine
  Hülle nicht öffnen und prüft nur ihr Präfix — eine Hülle mit Zufallsinhalt
  hätte für AP1 gereicht. Sie hätte aber im Fehlerfall etwas Unlesbares in der
  Datenbank stehen lassen, und sie hätte den Rundlauf nicht messen können:
  anmelden, öffnen, **derselbe Inhaltsschlüssel**. Genau das ist die Zahl, auf
  die es ankommt — ein anderer hieße, alle Daten des Kontos sind weg.
- **`sitzung.py` wäre eine Mine geworden.** Es entpackt den Inhaltsschlüssel
  mit der PBKDF2-Hälfte; an der ersten `edka1:`-Hülle wäre es gescheitert.
  Solange AP1 keine umstellt, fällt das nicht auf — AP2 stellt um, und dann
  hätte es zwischen AP2 und AP5 stillgestanden.

Vorgezogen sind: `hkdf_sha256()`, `datenschluessel()`, `huelle_kennung()`,
`huelle_bauen()` und die Erweiterung von `entschluesseln()` auf das
`edka1:`-Präfix in `krypto.py`; in `sitzung.py` das Lesen von
`KONTO_ANTEILE` / `ANTEIL_KENNUNG` / `ANTEIL_STAND` und der Weg über
`datenschluessel()`. **HKDF ist gegen den Prüfvektor 1 aus RFC 5869
nachgerechnet.** AP5 hat damit weniger zu tun; was dort offenbleibt, steht
unverändert in E-S10-15 (Fixture, Freigabe-, Wiederherstellungs- und
Komplettprobe, Referenzbestand).

---

## 3. Arbeitspakete

Eines nach dem anderen (K7); je Paket ein Commit, Statusblock, Push.
Reihenfolge, weil AP2 die Konstanten aus AP1 braucht, AP3 den Zustand aus
AP1, AP4 unabhängig ist (kann vor AP2/AP3 gebaut werden, wenn das
Prüfmittel-Umfeld es nahelegt), AP5 alles davor, AP6 zuletzt.

### AP1 — Grundlage Server

**Inhalt:** `serverkrypto_lib.php`: `kdf_anteil()` (32 Rohbyte oder null, wie
`serverschluessel()`), `kdf_anteil_alt()`, `konto_anteil(int $userId, string
$roh)`, `schluessel_kennung(string $hex)`, `anteil_zustand()` nach E-S10-09
(liest und setzt `app_state.kdf_anteil_kennung` / `server_key_kennung`),
`config_eintrag_schreiben()` als Verallgemeinerung von
`serverschluessel_eintragen()` (die alte Funktion ruft die neue). `install.php`
erzeugt `kdf_anteil`; `config.example.php` nennt beide Einträge mit einem Satz.
`auth_guard.php` stellt `$kontoAnteile` und `$anteilStand` bereit;
`ui_krypto_bootstrap()` gibt `KONTO_ANTEILE` und `ANTEIL_STAND` aus (Demo:
`null`/`'demo'`; `json_js` maskiert wie bisher). `validate_lib.php`:
`WRAP_RE` nimmt `edka1:[0-9a-f]{8}:` an. `pw_handling.php` gibt den Anteil des
Token-Kontos aus. `api/kdf_upgrade.php` → Hüllenfassung nach E-S10-07 (Kopf
der Datei umschreiben: was sie jetzt ist).
**Ort:** keine Oberfläche in diesem Paket außer den Konstanten.
**Abnahme:** `php -l` über alle berührten Dateien 0 Fehler; `anteil_zustand()`
in einem Kommandozeilen-Prüffall (`tools/pruefkonten/` oder ein eigener
`tools/anteilprobe/probe.php`) über die fünf Zustände aus E-S10-09 mit je
erwartetem Ergebnis — **5 von 5**; Kennung des Blatts und der Funktion
gleich (Hash von Hand gegengerechnet, 1 Wert); `kdf_upgrade.php` weist eine
Hülle mit Altkennung ab (**1 von 1**) und nimmt eine mit aktueller Kennung bei
`neu_iter === kdf_iter` an (**1 von 1**); Demo-Konto weiter `uebersprungen`.
Frische Installation über `install.php` trägt beide Einträge (Browserprobe,
1 Datei geprüft).

### AP2 — Browser: Datenschlüssel mit Anteil, stille Umstellung

**Inhalt:** `crypto.js`: `datenschluessel()`, `huelleOeffnen()`, `huelleBauen()`,
`HUELLE_PRAEFIX = 'edka1:'`, HKDF über WebCrypto; `deriveKeys()` liefert
weiter beide Hälften (der Name `dataKeyHex` wird zu `haelfteHex` — jede
Aufrufstelle zieht nach, `grep dataKeyHex server/` muss danach **0** sein).
`login.php`: immer Vormerkfach (E-S10-07). `unlock.js`: `loeseVormerkung()`
nach E-S10-07, `frage()` nach E-S10-08, Meldungen nach E-S10-09
(`'abweichend'` und Hülle mit unbekannter Kennung → die zwei Sätze aus der
Tabelle; kein „Passwort falsch“). `einstellungen.php`: Passwortwechsel und
Export-Probe über die drei Funktionen; neue Hülle mit aktuellem Anteil.
`pw_handling.php`: Erstvergabe und Reset bauen `edka1:`, wenn ein Anteil
ausgeliefert ist. `keyguard.js` unverändert (Bindung an die Hülle deckt den
Wechsel ab wie bei der Rundenanhebung).
**Eine Zahl wird gemessen, nicht gesetzt:** die zusätzliche Dauer der HKDF im
Browser (Erwartung: unter 5 ms; steht danach als Messwert im Changelog).
**Abnahme:** Umstellungslauf am Referenzbestand — Konto mit `edk1:`-Hülle,
Anmeldung, erste Seite: `pat_wrap_pw` trägt danach `edka1:<kennung>:`,
`pat_key_check` unverändert, **kein** Entsperrdialog, Suche findet denselben
Treffer wie vor der Umstellung (Zahl je Konto: 1 von 1, dazu Treffer vorher =
nachher); zweites Anmelden: kein zweiter Aufruf von `kdf_upgrade.php` (Netzwerk-
Mitschnitt, **0 Aufrufe**); Entsperrdialog in neuem Tab öffnet die `edka1:`-Hülle
(**1 von 1**); Passwortwechsel: neue Hülle `edka1:`, alte Sitzungen beendet,
Daten lesbar (Kreislauf csv vorher/nachher gleich); Reset über
Wiederherstellungsschlüssel: neue `edka1:`-Hülle, Daten lesbar; Export-Probe
mit richtigem und falschem Passwort (**2 von 2**); Demo-Konto: nach Anmeldung
weiter `edk1:` (**1 von 1**), Demo-Reset unverändert; `ANTEIL_STAND =
'abweichend'` künstlich hergestellt (Kennung in `app_state` verstellt):
Meldung mit erwarteter Kennung statt „Passwort falsch“ (**1 von 1**),
`edk1:`-Konto meldet sich weiter an. Bilderlauf der berührten Seiten
(Anmeldung, Entsperrdialog, Passwort, Sicherung) in beiden Bedienhöhen 0/0/0;
Wortliste 0/0/0; Vollständigkeit unverändert oder erklärt.

### AP3 — Betrieb: Schlüsselblatt, Nachtragen, Rotation, Status

**Inhalt:** `betrieb_server.php`: Karte „Schlüssel des Servers“ (E-S10-12) mit
den Zuständen und Knöpfen aus E-S10-09 bis E-S10-11; `betrieb_schluesselblatt.php`
(E-S10-10); `admin_sicherungsziele.php`: Karte Serverschlüssel raus, Verweis
rein; `status_lib.php`: Zeile „Serverschlüssel“ bekommt die Kennung, neue Zeile
**„Server-Anteil“** (Zustand, Kennung, Zählung je Präfix; rot bei `fehlt` und
`abweichend`, blau „Übergang läuft“ bei Rotation oder solange `edk1:`-Hüllen
außer Demo zählen, „in Ordnung“ sonst); Menüzähler wie bei den übrigen
Statuszeilen. `wartung_lib.php`: der Wartungsmodus bleibt unberührt — die
Sperre aus E-S10-09 ist **kein** Wartungsmodus, sie sperrt nur die Auslieferung
des Anteils.
**Ort:** Betrieb → Servereinstellungen (Karte), Betrieb → Status (zwei Zeilen),
Betrieb → Servereinstellungen → Schlüsselblatt (Druckansicht ohne Gerüst).
**Abnahme:** Anlegen auf einer Installation ohne Anteil → `config.php` trägt den
Eintrag, Kennung auf Karte = Kennung im Blatt = `app_state` (**3 gleich**);
Nachtragen mit **falschem** Wert → nichts geschrieben, Meldung nennt beide
Kennungen (Datei byte-gleich vorher/nachher); Nachtragen mit richtigem Wert →
geschrieben, Zustand `bereit`; Rotation → beide Einträge in `config.php`, Status
zählt `neu/alt/edk1` als drei Zahlen, nach Anmeldung eines Kontos wandert eine
Zahl (**vorher/nachher genannt**), „alten Anteil entfernen“ erst anbietbar bei
alt = 0; Neuanfang mit Rückfrage → `app_state` neu, betroffenes Konto sieht die
Reset-Meldung, Reset gelingt, Daten lesbar; Blatt-Druckansicht bei 210 mm
Breite ohne Umbruch der Gruppen (Bild); Bilderlauf Servereinstellungen, Status,
Backup-Ziele in beiden Bedienhöhen 0/0/0; Kontraste 0 verfehlt; Wortliste 0/0/0.

### AP4 — Adminpakete versiegeln, `ftp` abschaffen

**Inhalt:** `adminbackup_lib.php` nach E-S10-13 (Schreiben, vier Leser,
Fassungserkennung am Inhalt, Fehlersatz), `admin_sicherungen.php` und
`admin_user.php` (Meldung ohne Serverschlüssel), `api/adminbackup_freigabe.php`
(öffnet vor der Weitergabe). `sicherungsziel_lib.php` und
`admin_sicherungsziele.php` nach E-S10-14. `tools/versandprobe/` auf FTPS plus
Negativfall; `tools/freigabeprobe/` gegen Fassung 3.
**Eine Zahl wird gemessen:** Paketgröße Fassung 3 gegen Fassung 2 am
5000er-Bestand (Versiegelung vor dem Packen kostet die ZIP-Kompression; die
Teile sollten deshalb **vor** dem Versiegeln gzip-gepackt werden, wie die
`.edbak`-Teile — die Entscheidung trifft die Messung: gzip+Siegel vs. Siegel
allein, beide Zahlen ins Prüfprotokoll).
**Ort:** unverändert — Backup-Ziele (Protokollauswahl, Plakette), Kontoseite und
NutzerInnen-Liste (Pakete), Sicherungen.
**Abnahme:** `tools/freigabeprobe/` „Chiffretext ist ein anderer, Klartext
derselbe“ gegen ein Fassung-3-Paket, Zahl wie bisher; `unzip -p paket.zip
manifest.json | head -c 6` = `edsk1:` für jeden Teil (**n von n Teilen**);
`grep -c '"email"' <(unzip -p …)` = **0**; Einspielen und Zurückspielen eines
Fassung-3-Pakets über den Kreislauf edbak (0 unerklärte Abweichungen); Paket
mit vertauschtem Teilnamen im ZIP → Fehlersatz, nichts eingespielt (**1 von 1**);
ohne Serverschlüssel: „Alle sichern“ bricht mit Grund ab, 0 Dateien angelegt;
Versandprobe gegen FTPS und SFTP grün mit Zahl, `ftp`-Ziel im Bestand:
Plakette rot, Versandlauf meldet „übersprungen: 1“, Formular ohne `ftp`,
Speichern mit `ftp` abgewiesen (**4 von 4**); Wortliste 0/0/0; Bilderlauf
Backup-Ziele 0/0/0.

### AP5 — Prüfmittel und Referenzbestand

**Inhalt:** nach E-S10-15; dazu die Kreisläufe (R24) beide auf 0 unerklärt,
Wartungsprobe, Wiederherstellungsprobe, Komplettprobe mit dem neuen Fall,
`tools/pruefkonten/` (falls es Hüllen anlegt). Referenzbestand: Demo-Hülle
weiter `edk1:`; die übrigen Konten dürfen `edk1:` bleiben — sie stellen beim
ersten Anmelden um, und genau das ist der Umstellungslauf.
**Abnahme:** `sitzung.py` öffnet den CK eines `edk1:`- **und** eines
`edka1:`-Kontos (**2 von 2**); Kreisläufe csv und edbak **0** unerklärt mit
Zahl; `erzeugen.php` bricht ab, wenn die Demo-Hülle kein `edk1:` trägt
(Negativprobe 1 von 1); Wiederherstellungsprobe und Komplettprobe mit
Erwartungszahl vorher/nachher; Linkprobe 0 unbekannte Abweichungen.

### AP6 — Abschluss

**Inhalt:** `docs/Technik.md` 4 (Schlüsselkette neu beschrieben: Hälfte, Anteil,
HKDF, Hüllenkennung; die fünf Stellen; Zustände), 4.97c (Versand: FTPS-Satz,
`ftp` weg), Verzeichnisbaum (neue Datei, Karte), Runbook 7 (Wiederanlaufpaket
mit **vier** Stücken: `config.php`, Serverschlüssel, Server-Anteil,
Zugang zum Ziel; Schlüsselblatt; Nachtragen; Rotation; Neuanfang; die neue
Statuszeile); `docs/Backup-Format.md` 5 neu geschrieben (Fassung 3, Zweck je
Teil, Fassung 2 nur noch als Lesetoleranz bis Nr. 46); `docs/Handbuch.md` 5
(ein Absatz für NutzerInnen: was der Server-Anteil ist, was die Meldung heißt,
dass der Wiederherstellungsschlüssel ohne ihn geht), 6 (FTPS-Satz), 12
(Betrieb: Karte, Blatt, Rotation); `CLAUDE.md` 4 nach E-S10-17;
`docs/CHANGELOG.md` mit Begründung und den zwei gemessenen Zahlen; `version.php`
Kopf; Backlog 139 → Erledigt, Vermerke an 46, 140, 155; Rahmenplan: Zeile 9b,
Erledigt-Zeile Abschnitt 8, Abschnitt 6 (die fälligen Betriebsposten aus
E-S10-16), Abschnitt 10; **Konzept löschen** nach Freigabe des Abschlusses
(R62), Prüfdokument bleibt.
**Abnahme:** Linkprobe 0 unbekannte Abweichungen; Wortliste 0/0/0 über alle fünf
Bereiche (die Doku ist Bereich a); `grep -rn "kdf_pepper" docs/ server/` = 0
außer in der Vorbereitung und im Review (Historie); Konsistenzlesen der drei
Dokumente, die aufeinander verweisen (Technik 7 ↔ Handbuch 12 ↔ Backup-Format 5).

---

## 4. Was bewusst nicht gebaut wird

- **Gerätegeheimnis** nach 1Password-Art (verworfen in SP-3).
- **Umschlüsselung von `pat_wrap_rc`** — der Rückweg bleibt serverunabhängig.
- **Rotation des Serverschlüssels** (E-S10-11).
- **Anteil in der Datenbank oder im Komplettbackup** — nie (SP-3, „Was nicht ins
  Archiv gehört“).
- **Zähler oder Job für unversiegelte Altpakete** (E-S10-13).
- **Schemaänderung** — auch nicht für das `ENUM` (E-S10-14).
- **Ein Wartungsmodus** für den Zustand „abweichend“ — die Anwendung läuft
  weiter, nur der Anteil wird nicht ausgeliefert (AP3).

---

## 5. Prüfprotokoll

Wird je Paket fortgeschrieben: Mittel, Zahl, Stand. Leer bis AP1.

| Paket | Mittel | Zahl | Stand |
|---|---|---|---|
| AP0 | `sh -n tools/containeraufbau/aufbau.sh` | Syntaxfehler | **0** |
| AP0 | `sh tools/containeraufbau/aufbau.sh browser` | Engines, die starten | **3 von 3** (Chromium 141.0.7390.37, Firefox 142.0.1, WebKit 26.0) |
| AP0 | dasselbe, Negativprobe mit `PLAYWRIGHT_BROWSERS_PATH=/tmp/gibtsnicht` | Engines gemeldet / Rückgabewert | **0 von 3**, Rückgabewert **1** (vorher: WebKit brach ab, der Lauf lief weiter) |
| AP0 | `git fetch origin main` und Stand messen (Rahmenplan-Regel aus Fassung 42) | `origin/main` | `3886e26`, **Web 19.6.0**, Uhr 3.1.0, Android 0.15.0 |
| AP1 | `php -l` | berührte PHP-Dateien | **0 Fehler in 9 Dateien** |
| AP1 | `tools/anteilprobe/probe.php --schreiben` | Rechnungen, die fünf Lagen aus E-S10-09, Schreibweg in `config.php` | **69 von 69** |
| AP1 | dieselbe Probe, Teil D | `config.php` vorher/nachher | **byte-gleich**, 0 Nebendateien liegengeblieben |
| AP1 | `tools/anteilprobe/endpunkt.py` | `api/kdf_upgrade.php` über echtes HTTP | **33 von 33** |
| AP1 | darin E2/E3 | Hülle mit fremder bzw. ohne Anteil-Kennung | **2 von 2** abgewiesen (400 `anteil_kennung`), Hülle in der Datenbank unverändert |
| AP1 | darin E6 | Umstellung bei `neu_iter === kdf_iter` | **1 von 1** angenommen; `pat_key_check` und `kdf_iter` unverändert |
| AP1 | darin E8 | Rundlauf: Inhaltsschlüssel vor/nach der Umstellung | **gleich** |
| AP1 | darin E10 | Demo-Konto | `KONTO_ANTEILE` = `null`, `ANTEIL_STAND` = `'demo'`, Endpunkt meldet `uebersprungen` |
| AP1 | Kennung von Hand gegengerechnet | `schluessel_kennung()` gegen `substr(hash('sha256', …), 0, 8)` | **2 Werte**, beide gleich |
| AP1 | HKDF gegen RFC 5869, Prüfvektor 1 | `hkdf_sha256()` in `krypto.py` | **1 von 1** |
| AP1 | frische Installation über `install.php` | trägt `server_key` **und** `kdf_anteil`, beide 64 Hex, verschieden | **1 Datei geprüft**, Zustand `bereit` |
| AP1 | `tools/klickprobe/probe.mjs` | Regression über alle Bedienwege (Chromium) | **43 von 43** |
| AP1 | Kreisläufe (R24) | csv / edbak, unerklärte Abweichungen | **9120 / 0** und **287 687 / 0** |
| AP1 | `tools/screenshots/` (5 Seiten, 8 Breiten) | Überlauf / Konsolenfehler / Knopfhöhen | 48 Bilder, **0/0/0** |
| AP1 | `tools/wortliste/` (alle fünf Bereiche) | Treffer / ungenutzte Ausnahmen / Fallen | **0/0/0** |
| AP1 | `tools/linkprobe/` | Verweise / unbekannte Abweichungen | **117 / 0** |

---

## 6. Gesammelte Fehlerfunde (K4)

Funde während der Umsetzung, die nicht blockieren, werden hier gesammelt und
am Ende in den Backlog überführt. Leer bis AP1.

| Nr. | Fund | Wo | Entscheidung |
|---|---|---|---|
| | | | |

---

## 7. Fragen — entschieden am 13.09.2026

| Nr. | Frage | Entscheidung | Übernommen in |
|---|---|---|---|
| F-S10-1 | Konto-Anteil aus Salz, Kontonummer oder serverseitig gewähltem Salz? | **Kontonummer** | E-S10-03 |
| F-S10-2 | Hüllenkennung `edk2:` oder eigene Kennung mit Anteil-Kennung? | **eigene Kennung `edka1:<kennung>:`** | E-S10-05 |
| F-S10-3 | Ort der Schlüsselfunktionen? | **Betrieb → Servereinstellungen**, Karte „Schlüssel des Servers“ | E-S10-12 |
| F-S10-4 | Altpakete umsiegeln oder liegen lassen? | **Gegenstandslos — es gibt keine Altpakete**; kein Job, kein Zähler, Lesetoleranz bis Nr. 46 | E-S10-13 |
| F-S10-5 | FTPS behalten oder abschaffen? | **behalten, mit Hinweis** | E-S10-14 |

---

## 8. Einträge für Rahmenplan und Backlog — beim Merge einpflegen

Dieses Konzept entstand, während Backlog-Runde 3 auf einem Zweig lief. Wer S10
beginnt, pflegt die folgenden Einträge in die dann gültige Fassung ein (K7:
„wer zweiter mergt, zieht nach“).

**Rahmenplan, Fahrplan Zeile 9b** — Spalte Konzept: *„liegt vor (Fable,
13.09.2026), `docs/konzepte/Konzept-S10-Sicherheit.md`, E-S10-01 bis -18, keine
Mockups“*; Spalte Status: *„Konzept freigegeben; Umsetzung Opus, sechs
Arbeitspakete, kein Fable-Schritt; **keine Migration**; Voraussetzung: Runde 3
und 9c gemergt“*. Abschnitt „Schritt 9b“: einen Absatz *„Konzept liegt vor“*
mit den fünf Entscheidungen in je einem Halbsatz und der Abgrenzung aus
E-S10-01.

**Rahmenplan, Abschnitt 6** (nach dem Merge fällig, Auftraggeber): *Server-Anteil
anlegen und Schlüsselblatt drucken (zwei Ausdrucke, zwei Orte)* · *einmal
anmelden, Status: Zeile „Server-Anteil“ zählt die Umstellung* · *Backup-Ziele:
ein `ftp`-Ziel, falls vorhanden, auf SFTP/FTPS umstellen* · *Wiederanlaufpaket um
den Server-Anteil ergänzen*.

**Rahmenplan, Abschnitt 7 (Register):** keine neue Programmentscheidung — S10
setzt R78 um.

**Backlog Nr. 139:** Vermerk *„Konzept S10 liegt vor (13.09.2026), E-S10-13 und
-14; Umsetzung in AP4“*. **Nr. 46:** Vermerk *„Dazu der Leser für unversiegelte
Adminpakete (Fassung 2) und der `ENUM`-Wert `ftp` — beide bleiben nach S10 nur
als Toleranz und gehen hier mit“*. **Nr. 140:** unverändert (Deploy-Tor bleibt
außerhalb von S10, F-SP-10). **Nr. 155:** Vermerk *„S10 verlangt zusätzlich, dass
die Demo-Hülle `edk1:` bleibt — `erzeugen.php` prüft es (E-S10-15)“*.
**Kein neuer Backlog-Punkt** aus dem Konzept; Funde aus der Umsetzung kommen
über Abschnitt 6.
