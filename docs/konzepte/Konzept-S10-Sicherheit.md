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
> | Stand | 14.09.2026 — **AP5 erledigt (Web 20.2.1).** Konzept freigegeben; fünf Fragen (F-S10-1 bis -5) am 13.09.2026 mit dem Auftraggeber entschieden und als E-S10-03, -05, -12, -13, -14 übernommen. Die **eine Abweichung zur Ansage** — das erste `@media print` des Projekts (E-S10-U-06) — ist am 14.09.2026 nach Vorlage der Bilder **abgenommen**, ohne weiteres Mockup. **F-S10-6 aus AP5** (Abschnitt 7) ist am selben Tag mit Weg (a) entschieden. Keine offene Frage. |
> | Entschieden | E-S10-01 bis E-S10-18 (Abschnitt 2), dazu E-S10-U-01 bis **-16** aus der Umsetzung (Abschnitt 2a) |
> | Offen | nichts. **F-S10-6 ist am 14.09.2026 mit Weg (a) entschieden** und gebaut (Web 20.2.1). **Beide gemessenen Zahlen stehen:** HKDF im Browser (AP2, 0,023–0,154 ms) und die Paketgröße der Fassung 3 (AP4, drei Zahlen statt zwei — F-10) |
> | Umsetzung | **AP0 bis AP5 erledigt, AP6 als Nächstes.** Sechs Arbeitspakete (Abschnitt 3), eines nach dem anderen; nach jedem Paket Statusblock hier, Prüfprotokoll (Abschnitt 5), Push (K7). Voraussetzung erfüllt: Backlog-Runde 3 (PR #43) **und** Mockup-Runde 9c (PR #44) sind gemergt; `origin/main` stand am 14.09.2026 auf `3886e26`, **Web 19.6.0** |
> | Fable-Schritte der Umsetzung | keine |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP0 Ablage und Buchführung | **erledigt** 14.09.2026 | keine (nur `docs/`, `tools/`) | Rahmenplan Fassung **60**; Backlog-Vermerke an **3** Nummern (46, 139, 155); Containeraufbau **3 von 3** Engines |
> | AP1 Grundlage Server | **erledigt** 14.09.2026 | **19.7.0** | Anteilprobe **69 von 69** + Endpunktprobe **33 von 33**; `php -l` **0** Fehler in 9 Dateien; Klickprobe **43 von 43**; Kreisläufe csv **9120/0** und edbak **287 687/0**; Bilderlauf 48 Bilder **0/0/0**; Wortliste **0/0/0**; Linkprobe **117/0** |
> | AP2 Browser: Datenschlüssel mit Anteil, stille Umstellung | **erledigt** 14.09.2026 | **20.0.0** (Haupt) | Umstellungslauf **16 von 16** in **drei Engines, je zweimal**; darin **80 von 80** Blöcken nach der Umstellung lesbar und **0** Aufrufe von `kdf_upgrade.php` beim zweiten Anmelden; HKDF **0,023–0,154 ms** je Ableitung; Anteilprobe **69 von 69** + Endpunktprobe **33 von 33**; Klickprobe **43 von 43**; Kreisläufe **9120/0** und **287 687/0**; Bilderlauf 48 Bilder **0/0/0**; Wortliste **0/0/0**; Linkprobe **117/0**; `dataKeyHex` **0 im Code** (2 in der Versionserzählung) |
> | AP3 Betrieb: Schlüsselblatt, Nachtragen, Rotation, Status | **erledigt** 14.09.2026 | **20.1.0** | Betriebslauf **50 von 50** in **drei Engines**; darin: drei Kennungen gleich (Karte = Status = Blatt), Druckansicht bei 210 mm **16 Gruppen / 0 zerschnitten / 0 Überlauf**, Nachtragen falsch → `config.php` **byte-gleich**, Rotation **0/3 → 1/2** nach einer echten Anmeldung, Neuanfang zweiter Versuch **abgewiesen**, nach Reset **Inhaltsschlüssel identisch** und **1 von 1** Chiffretext geöffnet; Anteilprobe **69 von 69** + Endpunktprobe **33 von 33**; Klickprobe **43 von 43**; Kreisläufe **9120/0** und **287 687/0**; Bilderlauf 4 Seiten × 8 Breiten × **beide Bedienhöhen** je **0/0/0**, dazu Risikoliste in Firefox und WebKit; Kontraste **22 Paare, 0 verfehlt**; Wortliste **0/0/0**; Linkprobe **117/0**; Vollständigkeit **329 → 335**, Unterschied erklärt |
> | AP4 Adminpakete versiegeln, `ftp` abschaffen | **erledigt** 14.09.2026 | **20.2.0** | Jeder ZIP-Eintrag **3 von 3** mit `edsk1:`, `konto.json` ebenso, **0** Treffer für die E-Mail im ganzen Ablageordner; Paketgröße **drei** Zahlen (33 281 · 201 390 · 45 290); umbenanntes Paket und untergeschobener Teil je **1 von 1** abgewiesen; Freigabeprobe **16/16**, Wiederherstellungsprobe **98/98**, Versandprobe **116/116**, Komplettprobe **63/63** (vorher: Absturz 255); Altziel `ftp` — Versandschub **übersprungen 1 / Fehler 0**, Cron `1 übergangen`, Formular sperrt, Speichern abgewiesen **am Protokoll**; Klickprobe **43/43**; Bilderlauf 4 Seiten × 8 Breiten × beide Bedienhöhen **0/0/0**; Kontraste **22/0**; Wortliste **0/0/0**; Linkprobe **117/0**; Vollständigkeit **335 → 341**, erklärt |
> | AP5 Prüfmittel und Referenzbestand | **erledigt** 14.09.2026 | **20.2.1** (Korrektur — eine Stelle unter `server/`: der zweite Riegel, F-S10-6/a) | Wartungsprobe **57/57** (vorher **55 mit 1 rot** — F-S10-AP5-01); Riegelprobe **10 von 10** (beide Riegel, beide Richtungen, dazu der abgefangene Reset); Sitzungsprobe **2 von 2** gegen `pat_key_check` gerechnet; Wiederherstellungsprobe **106/106** (vorher 98, Teil 12 bringt 8); Komplettprobe **72/72** mit Schaltern (vorher 63) und **64** ohne (vorher 55), darin Teil 11: Anteil, Vor-Anteil und Serverschlüssel der **Installation** **0 Treffer** in 1 904 401 Byte Dump, Kennung **3 Treffer**; Freigabeprobe **16/16**; Endpunktprobe **34/34** (E0 neu); Generator-Prüfung **283 997/0** (RFC-5869-Prüfvektor neu); Prüfkonten **0 von 8** mit Hülle und **16 von 16** Fassung-1-Pakete lesbar; Anteilprobe **69/69**; Kreisläufe **9120/0** und **287 687/0**; Klickprobe **43/43**; Bilderlauf 4 Betriebsseiten × 8 Breiten **32 Bilder 0/0/0**; Vollständigkeit **341 → 340** (`config.php` ausgenommen), Hexfarben **0**; Wortliste **0/0/0** bei 96 Ausnahmen; Linkprobe **117/0** über **100** Zielseiten; Nachlese **0** Wegwerfkonten und **0** verwaiste Ordner (vorher 2 · 3 — F-S10-AP5-02). Versandprobe **116/116**, Jobprobe **27/27**, Design-Tabellen **237/0**. **Dreizehn Fehlerfunde, alle behoben**, zwei davon in AP5s eigener Arbeit (F-S10-AP5-01 bis -13) |
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
Zertifikat — belegt in `tools/versandprobe/LIESMICH.md`.

> **Berichtigt am 14.09.2026 (AP4-Vorbereitung).** Hier stand: „Die
> Versandprobe legt ihr Probeziel heute mit Protokoll `ftp` an." Das ist
> falsch, und es stand zweimal so da (auch in 1.7). Die Versandprobe legt ihr
> Datenbankziel als **`sftp`** an (`tools/versandprobe/probe.php:379`); ihr
> einziges `ftp` steht in einem **Negativfall** (`:400`), der heute schon
> nichts schreibt. Das einzige Werkzeug, das ein `ftp`-Ziel wirklich anlegt,
> ist **`tools/komplettprobe/probe.php:537`** — und genau das nennt der
> AP4-Inhalt nicht.
>
> **Zwei weitere Angaben desselben Abschnitts sind zu schärfen.**
> `sz_weg()` hat genau **einen** benannten Zweig (`sftp`, `:827`); alles
> andere landet in `ZielFtp`, und dort entscheidet `$prot === 'ftps'` über
> TLS (`:836`). FTPS ist damit geschützt — aber ein **unbekanntes oder
> leeres** Protokoll fällt still auf **Klartext-FTP** zurück. Und
> `sz_pruefen_eingabe()` prüft gegen `SZ_PORTS` (`:673`), nicht gegen
> `SZ_PROTOKOLLE`; ein Streichen nur aus dem Anzeigekatalog bliebe wirkungslos
> (das ist F-6, hier zum zweiten Mal belegt).

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
`tools/versandprobe/` (Datenbankziel **`sftp`**, ein `ftp`-Negativfall),
`tools/komplettprobe/` (legt als **einziges** Werkzeug ein `ftp`-Ziel an,
`probe.php:537`, und liest aus einem fest geschriebenen Ordner `/ftp/`,
`:553`), `tools/wiederherstellungs-probe/`.

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

**E-S10-U-04 Die Hüllenprüfung steht an einer Stelle, nicht an vier**
(14.09.2026, aus Fund F-3). Das Konzept nennt in E-S10-08 „eine Funktion für
alle fünf Stellen" — gemeint ist der Browser. Serverseitig fehlte die
Entsprechung: Die Prüfung, ob eine Hülle zum aktuellen Anteil gehört, stand
nur in `api/kdf_upgrade.php`, während `pat_wrap_pw` an **vier** Stellen
geschrieben wird. Sie liegt jetzt als `huelle_pw_pruefen()` in
`serverkrypto_lib.php` und wird von allen vieren gerufen; `huelle_rc_pruefen()`
daneben hält die Wiederherstellungs-Hülle vom Anteil fern.

*Warum das keine Verschärfung ist, sondern das Gemeinte:* `CLAUDE.md` 4
verlangt eine **gemeinsame Prüfschicht** — „alle Schreibwege, ohne Ausnahme".
Eine Prüfung an einem von vier Wegen erfüllt das nicht; sie erweckt nur den
Anschein. Dazu kommen zwei Ausdrücke statt eines (`WRAP_PW_RE`, `WRAP_RC_RE`),
weil eine Regel für zwei Hüllen mit verschiedenen Zusagen früher oder später
die falsche durchlässt.

Vorgezogen sind: `hkdf_sha256()`, `datenschluessel()`, `huelle_kennung()`,
`huelle_bauen()` und die Erweiterung von `entschluesseln()` auf das
`edka1:`-Präfix in `krypto.py`; in `sitzung.py` das Lesen von
`KONTO_ANTEILE` / `ANTEIL_KENNUNG` / `ANTEIL_STAND` und der Weg über
`datenschluessel()`. **HKDF ist gegen den Prüfvektor 1 aus RFC 5869
nachgerechnet.** AP5 hat damit weniger zu tun; was dort offenbleibt, steht
unverändert in E-S10-15 (Fixture, Freigabe-, Wiederherstellungs- und
Komplettprobe, Referenzbestand).

**E-S10-U-05 „Nicht eingerichtet" trägt eine NEUTRALE Plakette, keine rote**
(14.09.2026, Abweichung von E-S10-09 / AP3-Inhalt). Der AP3-Inhalt schreibt
„rot bei `fehlt` und `abweichend`". Rot bleibt jetzt `abweichend` allein;
`fehlt` ist neutral.

*Warum:* `docs/Design.md` 9.23 legt fest, was die vier Plakettentöne **auf
einer Statusseite** heißen — rot ist „etwas ist kaputt und muss reparariert
werden". Eine Installation ohne Server-Anteil ist nicht kaputt: Sie verhält
sich Zeile für Zeile wie vor Web 20.0.0, jede Hülle bleibt `edk1:`, niemand
ist ausgesperrt. Rot dort hieße, dass jede Installation, die S10 noch nicht
eingerichtet hat, mit einer roten Zeile im Status dasteht — und eine rote
Zeile, die immer steht und nichts bedeutet, macht die roten daneben
wertlos. `abweichend` bleibt rot, denn dort ist tatsächlich jemand
ausgesperrt.

**E-S10-U-06 Das Schlüsselblatt bringt das erste `@media print` des Projekts**
(14.09.2026). Konzept 1.6 sagt, S10 brauche „keinen neuen Baustein, kein
Mockup". Für die Karte und die Statuszeilen stimmt das; für das Blatt nicht
ganz: Es ist eine Seite, deren **Zweck der Ausdruck** ist, und dafür gab es
bis jetzt nichts — `server/assets/style.css` hatte keinen einzigen
Druckblock.

*Was gebaut wurde und was nicht:* **kein** neuer Baustein — das Blatt
benutzt Lesespalte, Meldung, Wertekasten, Feldhinweis und Knopf, alles
vorhanden. Dazu **drei** Druckregeln (Bildschirmknöpfe fort, keine
Flächenfarbe, kein Umbruch im Wert) und **eine** neue Klasse `.blatt-wert`,
die den vorhandenen Wertekasten um zwei Eigenschaften ergänzt. Kein neues
Token, kein neuer Farbwert, kein neues Symbol. In `docs/Design.md` steht es
als **sechster Seitentyp** (10.1) mit den drei Regeln und ihrer Begründung.

*Dies ist die eine Stelle, an der AP3 über das Konzept hinausgeht*, und sie
ist dem Auftraggeber ausdrücklich angesagt: `CLAUDE.md` 5 verlangt für eine
**neue Darstellung** eine Freigabe mit Mockup. Der Bilderlauf zeigt die
Seite in acht Breiten, der Betriebslauf die Druckansicht bei 210 mm.

> **Freigegeben am 14.09.2026** nach Vorlage der Bilder (Karte in den drei
> Lagen, Blatt im Druck bei 210 mm, Blatt am Bildschirm, Statusseite, Karte
> und Blatt bei 390 px): *„Passt, keine weiteren Mockups notwendig — ist
> abgenommen."* Die gebauten Bilder treten damit an die Stelle des Mockups;
> der Seitentyp steht in `docs/Design.md` 10.1.

**E-S10-U-07 `betrieb_schluesselblatt.php` steht in `WARTUNG_AUSNAHMEN`**
(14.09.2026). Der AP3-Inhalt sagt, `wartung_lib.php` bleibe unberührt — das
galt für die Sperre aus E-S10-09 und gilt weiter. Die Ausnahmeliste ist etwas
anderes: `betrieb_server.php` steht dort seit S8, und ihr Druckknopf führte
sonst auf eine 503-Seite — derselbe Griff ins Leere wie F-S8-P-04, nur eine
Ebene tiefer. Vor allem aber ist die Lage, in der man das Blatt braucht, genau
eine Wartungslage: Eine Sicherung ist auf einen neuen Server eingespielt,
`config.php` fehlt, der Anteil steht auf `abweichend` — und der Wert, der
nachzutragen ist, steht auf dem Ausdruck, den diese Seite gemacht hat.

**E-S10-U-08 Der Neuanfang ist serverseitig gegen Wiederholung gesichert**
(14.09.2026). E-S10-09 nennt den Neuanfang „mit Rückfrage"; eine Rückfrage im
Browser hält ein F5 nach dem Absenden nicht auf. `anteil_neuanfang()` prüft
deshalb die Lage selbst und weist alles ab, was nicht `abweichend` ist. Ohne
diese Schranke erzeugte ein versehentliches Neuladen einen **zweiten** neuen
Anteil — und die Konten, die gerade zurückgesetzt wurden, wären ein zweites
Mal ausgesperrt. Gemessen im Betriebslauf, Abschnitt 8.

**E-S10-U-09 Der Vermerk „übersprungen" bekommt einen eigenen Rückgabeschlüssel
— und auf der Jobebene einen zweiten Namen** (14.09.2026, mit dem
Auftraggeber; aus Fund F-A). `sz_versand_schub()` gibt zusätzlich
`'uebersprungen' => int` und `'uebersprungen_namen' => [string]` zurück; die
Zielseite hängt den Satz an die **Erfolgsmeldung**, nicht an den Fehlerkasten.

*Warum nicht in `fehler`:* `jobs_lib.php:1167` wirft, sobald dort etwas steht.
Die Folge wäre kein Vermerk, sondern ein **dauerhaft roter Versandjob** —
Betriebsstatus „scheitert", Wartungsseite rot, Cron-Rückgabewert 1, und
`jobs.letzter_erfolg` friert ein. Das Signal, für das dieser Wurf gebaut wurde
(„damit die Wartungsseite nicht ‚grün' meldet, während seit drei Wochen nichts
hinausgeht"), wäre verbrannt.

*Und auf der Jobebene ein ANDERER Name.* Die Zahl geht bis ins Cron-Protokoll
durch — dort ist `uebersprungen` aber schon belegt, und zwar schärfer als
erwartet: `jobs.php:73` prüft `isset($b['uebersprungen'])` und **überspringt
dann die ganze Jobzeile** (`continue`), weil der Schlüssel dort „dieser Job
lief wegen einer Pause gar nicht" heißt (`jobs_lib.php:319`). Ein
gleichnamiger Schlüssel ersetzte also das Ergebnis, statt es zu ergänzen.
`job_versand()` gibt deshalb **`uebergangen`** zurück, und `jobs.php` hängt es
an die normale Zeile: `versand fertig · erledigt 3 · 1 übergangen`.

**E-S10-U-10 `sz_weg()` prüft POSITIV gegen den Katalog** (14.09.2026, mit dem
Auftraggeber; aus Fund F-C). Die Funktion hat genau einen benannten Zweig
(`sftp`); alles andere landet in `ZielFtp`, wo `$prot === 'ftps'` über TLS
entscheidet. FTPS ist damit geschützt — ein **unbekanntes oder leeres**
Protokoll aber fällt still auf **Klartext-FTP** zurück, und dann gehen
Nutzername und Passwort offen über Port 21.

Geprüft wird deshalb `isset(SZ_PROTOKOLLE[$prot])` und **nicht** `!== 'ftp'`:
Ein `ENUM`, das je nach `sql_mode` still zum Leerstring wird, ist im Projekt
belegt (`backup_lib.php:2101`), und `db.php` setzt kein `sql_mode`. Damit sind
**beide** Wege dicht — der Versand und „Verbindung prüfen"
(`admin_sicherungsziele.php:351`), das sonst an einem Altziel weiterhin
Zugangsdaten im Klartext verschickt hätte.

**E-S10-U-11 Das Siegel bindet auch den PAKETNAMEN** (14.09.2026, mit dem
Auftraggeber; löst Fund F-7 auf). Der Zweck heißt
`adminpaket|<konto>|<paket>|<teil>` statt `adminpaket|<konto>|<teil>`.

*Der Einwand des Konzepts trägt nicht.* E-S10-13 hielt dagegen, der
Paketstempel müsse beim Lesen bekannt sein und käme damit aus dem versiegelten
Manifest. Nachgesehen: **Alle vier Leser bekommen den Dateinamen als
Parameter**, bevor sie irgendetwas öffnen (`adminbackup_lib.php:856`, `:2040`,
`:2184`, `:2207`), und der Name ist eindeutig (Zeitstempel plus vier
Zufallsbytes, `:246`).

*Der Preis wird ausdrücklich festgeschrieben:* **Wer ein Paket umbenennt,
macht es unlesbar** — auch dann, wenn es von einem Backup-Ziel unter anderem
Namen zurückkommt. Der Dateiname ist schon heute die Identität in der Ablage
(`edbak_verzeichnis_abgleichen()`), aber es stand nirgends geschrieben. Es
steht ab AP4 in `docs/Backup-Format.md`.

**E-S10-U-12 gzip vor dem Siegel** (14.09.2026, mit dem Auftraggeber).
Versiegelte Teile sind Zufallsrauschen; das ZIP kann sie nicht mehr packen,
und die Begründung im Bestand („hier ist es blankes JSON, der Packlauf lohnt
sich also", `adminbackup_lib.php:623`) fällt mit der Versiegelung weg. Ohne
Vorstufe wüchse das Paket am 5000er-Bestand von **11,42 MB Richtung 94,28 MB**
— das trifft Speichergrenze, Verdrängung und die Übertragung an jedes
Backup-Ziel.

Das Verfahren wird **einmal festgeschrieben** und in `docs/Backup-Format.md`
benannt: `spur_lib.php:137` hält fest, dass `gzencode`, `gzdeflate` und
`gzread` im Projekt nicht beliebig austauschbar sind. **Gemessen werden drei
Zahlen** (so verlangt es AP4 selbst und so berichtigt F-10): heute · Siegel im
ZIP ohne Vorstufe · gzip vor dem Siegel.

**E-S10-U-13 Ein Altziel lässt sich nicht durch bloßes Speichern umstellen**
(14.09.2026, mit dem Auftraggeber; aus Fund F-D). `ui_feld()` setzt `selected`
nur bei Übereinstimmung (`ui.php:1811`); fällt `ftp` aus dem Katalog, wählt der
Browser die **erste** Option — `sftp` —, während Port 21 und die versiegelten
Zugangsdaten stehenbleiben. Ein Druck auf „Speichern" ergäbe ein Ziel, das
plausibel aussieht und beim nächsten Versand scheitert; die rote Plakette wäre
dabei verschwunden, weil das Protokoll ja nicht mehr `ftp` ist.

Das Formular **sperrt** deshalb bei einem Altziel und sagt, was zu tun ist:
Protokoll, Port **und** Zugangsdaten neu setzen. Die Zugangsdaten sind mit dem
Serverschlüssel versiegelt und gelten nicht notwendig auch für FTPS — sie
stillschweigend zu übernehmen wäre geraten, nicht gewusst.

**E-S10-U-14 `konto.json` wird mitversiegelt** (14.09.2026, mit dem
Auftraggeber; löst Fund F-9 auf). Die Begleitdatei neben dem Paket trägt
E-Mail und Namen im Klartext (`adminbackup_lib.php:665`). Der Serverschlüssel
ist genau dafür da: Der Server muss sie ohne Browser lesen können.

*Erst damit stimmt die Abnahmezahl.* „Kein lesbarer Name, keine E-Mail" galt
sonst nur für die ZIP-Einträge, während die Adresse im Ordner daneben stünde —
eine grüne Zahl, die das Falsche misst. *Preis:* ein weiterer Leser und
Schreiber, und **ohne Serverschlüssel ist die Backup-Übersicht nicht mehr
lesbar** — was der Riegel aus Schritt 1 ohnehin verlangt.

**E-S10-U-15 Ein übersprungenes Ziel zählt nicht als säumig** (14.09.2026).
`sz_versand_rueckstand()` wertet allein `letzter_erfolg` und liefert ohne je
erfolgten Versand dauerhaft `null` — die Jobzeile stünde dann **blau „in
Ordnung"**, obwohl Pakete liegenbleiben; mit altem `letzter_erfolg` stünde sie
dauerhaft orange, obwohl alle SFTP-Ziele beliefert sind. Beides ist eine
Dauermeldung, die nichts mehr aussagt. Das übergangene Ziel wird deshalb aus
der Rückstandsrechnung genommen; sichtbar ist es an **seiner Zeile** (rote
Plakette). Mitzuziehen ist `status_lib.php:509`, dessen Zweig an `letzter_lauf`
hängt und das Ziel sonst dauerhaft als „nie versendet" meldete.

**E-S10-U-16 Der FTP-Absatz der Karte „Was hier gilt" wird umgeschrieben, nicht
gestrichen** (14.09.2026). Er rechtfertigt heute FTP („es steht hier, weil es
auf einfachem Webspace oft das Einzige ist"). Nach AP4 ist er der **einzige
Ort, an dem die rote Plakette erklärt wird**: Klartext, deshalb weder wählbar
noch beschickt, bestehende Ziele umstellen. Die Streichung selbst wird für den
`ENUM`-Rückbau vorgemerkt (Backlog Nr. 168 / Nr. 46).

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

> **Fortgeschrieben am 14.09.2026** nach der Vorbereitung (Kartierung des
> Codes, acht gegengeprüfte Funde, acht Entscheidungen E-S10-U-09 bis -16).
> Was hier stand, war an vier Stellen zu knapp; die Ergänzungen sind **kursiv**
> gekennzeichnet, die Berichtigungen benannt.

**Inhalt:** `adminbackup_lib.php` nach E-S10-13 (Schreiben, vier Leser,
Fassungserkennung am Inhalt, Fehlersatz), `admin_sicherungen.php` und
`admin_user.php` (Meldung ohne Serverschlüssel), `api/adminbackup_freigabe.php`
(öffnet vor der Weitergabe). `sicherungsziel_lib.php` und
`admin_sicherungsziele.php` nach E-S10-14. `tools/freigabeprobe/` gegen
Fassung 3.

*Dazu, und im ursprünglichen Zuschnitt nicht genannt:*

- ***`tools/komplettprobe/` zieht mit.*** Sie legt als **einziges** Werkzeug
  ein `ftp`-Ziel an (`probe.php:537`) und liest aus einem fest geschriebenen
  Ordner `/ftp/` (`:553`). Sobald `sz_pruefen_eingabe()` das Protokoll
  abweist, scheitert schon das Anlegen, und die **sieben** Erwartungen im
  Rumpf darunter laufen nicht mehr: aus 76/0 würde 69/1. Umgestellt wird auf
  `ftps` mit Port 2122, und der Ordnername wird aus dem Protokoll abgeleitet
  statt geschrieben.
- ***`tools/versandprobe/` bleibt bei `sftp`.*** Der ursprüngliche Satz
  („auf FTPS plus Negativfall") beruhte auf dem falschen Befund 1.5. Ihr
  Datenbankteil misst unter anderem, dass sich ein **Hostschlüssel-
  Fingerabdruck übernehmen lässt** (`probe.php:426`) — ein FTPS-Ziel hat
  keinen, der Fall bliebe grün und maß nichts. Sie bekommt **nur** den
  Negativfall dazu.
- ***Der vorhandene Negativfall wird stumpf und muss geschärft.***
  `probe.php:400` legt heute ein `ftp`-Ziel an und erwartet die Abweisung —
  aber wegen des **Doppelnamens** (`sicherungsziel_lib.php:723`), nicht wegen
  des Protokolls. Nach AP4 wäre er weiterhin grün und prüfte etwas anderes als
  gemeint. Er bekommt einen eigenen Namen, damit die Abweisung tatsächlich am
  Protokoll hängt.
- ***Neun sichtbare Texte*** statt der zwei genannten Stellen — die
  Aufzählung steht bei E-S10-U-16 und in Abschnitt 6.
- ***`konto.json` wird mitversiegelt*** (E-S10-U-14), sonst misst die
  Abnahmezahl unten nur die ZIP-Einträge und nicht den Ordner daneben.

**Drei Zahlen werden gemessen**, nicht zwei (Berichtigung aus F-10): Die Teile
sind heute schon **gepackt** — das ZIP tut es. Gemessen wird deshalb
**heute · Siegel ohne Vorstufe · gzip vor dem Siegel**, am 5000er-Bestand.
Gebaut wird nach E-S10-U-12 mit gzip; die mittlere Zahl belegt, warum.

**Ort:** Backup-Ziele (Protokollauswahl, Plakette, *Sperre am Altziel*,
*Karte „Was hier gilt"*), Kontoseite und NutzerInnen-Liste (Pakete),
Sicherungen, *Betrieb → Status (Zeile „Backups versenden")*.

**Abnahme:** `tools/freigabeprobe/` „Chiffretext ist ein anderer, Klartext
derselbe“ gegen ein Fassung-3-Paket, Zahl wie bisher; jeder ZIP-Eintrag beginnt
mit `edsk1:` (**n von n Teilen**, `manifest.json` eingeschlossen); *im ganzen
Ablageordner* — ZIP **und** `konto.json` — **0** Treffer für eine
E-Mail-Adresse; Einspielen und Zurückspielen eines Fassung-3-Pakets über den
Kreislauf edbak (0 unerklärte Abweichungen); Paket mit vertauschtem Teilnamen
im ZIP → Fehlersatz, nichts eingespielt (**1 von 1**); *Teil aus einem ANDEREN
Paket desselben Kontos untergeschoben → Fehlersatz* (**1 von 1**, das ist der
Zweck von E-S10-U-11); *umbenanntes Paket → Fehlersatz statt stillem
Fehlschlag* (**1 von 1**); ohne Serverschlüssel: „Alle sichern“ bricht mit
Grund ab, 0 Dateien angelegt; Versandprobe gegen FTPS und SFTP grün mit Zahl,
`ftp`-Ziel im Bestand: Plakette rot, Versandlauf meldet „übersprungen: 1“
*und das Cron-Protokoll „1 übergangen“*, Formular *sperrt mit Satz*, Speichern
mit `ftp` abgewiesen *am Protokoll, nicht am Namen* (**5 von 5**);
*`sz_weg()` mit leerem Protokoll wirft, statt Klartext zu senden* (**1 von 1**);
*Komplettprobe wieder 76/0*; Wortliste 0/0/0; Bilderlauf Backup-Ziele 0/0/0.

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
| AP2 | `tools/anteilprobe/umstellungslauf.mjs`, drei Engines × zwei Läufe | stille Umstellung im echten Browser | **16 von 16** je Lauf, **6 von 6** Läufen grün |
| AP2 | darin Schritt 1 | `kdf_upgrade.php` beim **ersten** Anmelden | **1 Aufruf**, Status 200, **kein** Entsperrdialog |
| AP2 | darin Schritt 2 | `kdf_upgrade.php` beim **zweiten** Anmelden | **0 Aufrufe** |
| AP2 | darin Schritt 3 | verschlüsselte Blöcke nach der Umstellung geöffnet | **80 von 80** (83 Einträge, 80 mit Block) |
| AP2 | darin Schritt 4 | **die gemessene Zahl:** HKDF je Ableitung (500 am Stück) | Chromium 141 **0,023–0,025 ms** · WebKit 26 **0,106–0,114 ms** · Firefox 142 **0,136–0,154 ms** (erwartet: unter 5 ms) |
| AP2 | darin Schritt 5 | Entsperrdialog öffnet die `edka1:`-Hülle | **1 von 1** |
| AP2 | darin Schritt 6 | Demo-Konto: `KONTO_ANTEILE` null, Hülle bleibt `edk1:`, Endpunkt übersprungen | **5 von 5** |
| AP2 | `grep -ro dataKeyHex server/` | Vorkommen des alten Namens | **0 im Code**, 2 in der Versionserzählung |
| AP2 | `tools/anteilprobe/probe.php --schreiben` und `endpunkt.py` | Regression der Serverseite nach F-2/F-3 | **69 von 69** und **33 von 33** |
| AP2 | `tools/klickprobe/probe.mjs` | Regression über alle Bedienwege | **43 von 43** |
| AP2 | Kreisläufe (R24) | csv / edbak | **9120/0** und **287 687/0** |
| AP2 | `tools/screenshots/` (5 Seiten × 8 Breiten) | Überlauf / Konsole / Knopfhöhe | 48 Bilder **0/0/0** |
| AP2 | `tools/wortliste/`, `tools/linkprobe/` | | **0/0/0** · **117/0** |
| AP3 | `php -l` | berührte PHP-Dateien | **0 Fehler in 8 Dateien** |
| AP3 | `tools/anteilprobe/betriebslauf.mjs`, drei Engines | Karte, Statuszeile, Blatt, die fünf Lagen an der **Oberfläche** | **50 von 50** je Engine |
| AP3 | darin Abschnitt 1 | Karte, Status und Blatt nennen dieselbe Kennung; die Karte zeigt den Wert **nicht** | **3 Kennungen gleich**, Wert auf der Karte **nicht** enthalten |
| AP3 | darin Abschnitt 2 | Blatt in `media: print` bei 718 px (210 mm) | **16 Vierergruppen · 0 zerschnitten · 0 waagerechter Überlauf · 0 Bildschirmknöpfe**, 1 Zeile; Bild `tools/anteilprobe/ausgabe-blatt-druck.png` |
| AP3 | darin Abschnitt 4 | Nachtragen mit **falschem** Wert | Meldung nennt **beide** Kennungen; `config.php` vorher/nachher **byte-gleich** |
| AP3 | darin Abschnitt 5 | Nachtragen mit richtigem Wert, in Vierergruppen abgetippt | geschrieben, Zustand `bereit` |
| AP3 | darin Abschnitt 6 | Rotation: beide Einträge in `config.php`, Marke gewandert, „alten Anteil entfernen" **nicht** angeboten | **3 von 3** · nicht angeboten |
| AP3 | darin Abschnitt 6b | **eine echte Anmeldung** während der Rotation schiebt ein Konto | **0/3 → 1/2**, Hülle des Kontos trägt die neue Kennung |
| AP3 | darin Abschnitt 7 | „Alten Anteil entfernen" bei alt = 0 | angeboten; `kdf_anteil_alt` danach **nicht mehr** in `config.php` |
| AP3 | darin Abschnitt 8 | Neuanfang: gelingt einmal, **zweiter Versuch** (das F5) wird abgewiesen | **1 gelungen / 1 abgewiesen**, Wert unverändert |
| AP3 | darin Abschnitt 8b | betroffenes Konto: Anmeldung gelingt, Entsperrdialog nennt den **erneuerten Anteil** | Wortlaut „Der Server-Anteil wurde erneuert — bitte das Passwort über den Wiederherstellungsschlüssel neu setzen" |
| AP3 | darin Abschnitt 8b | Reset über den Wiederherstellungsschlüssel, danach die Daten | **Inhaltsschlüssel Zeichen für Zeichen derselbe**, `pat_key_check` unverändert, **1 von 1** vor dem Neuanfang gebauter Chiffretext geöffnet |
| AP3 | darin Abschnitt 9 und `finally` | Fehler aus der Anwendung; Rückgabe | **0 Fehler**; `config.php` **byte-gleich**, **5 von 5** Hüllen byte-gleich, **6 von 6** Kontofeldern gleich |
| AP3 | Anteilprobe + Endpunktprobe (Regression) | Serverseite | **69 von 69** · **33 von 33** |
| AP3 | `tools/klickprobe/probe.mjs` | Regression über alle Bedienwege, gefahren **nach** der Reparatur der Prüfinstallation | **43 von 43** (ein früherer Lauf meldete 37 von 43 — Demo-Reset mitten im Lauf, F-S10-AP3-09) |
| AP3 | Kreisläufe (R24) | csv / edbak, unerklärte Abweichungen | **9120/0** und **287 687/0** |
| AP3 | Bilderlauf, 4 Seiten × 8 Breiten, **beide Bedienhöhen** | Überlauf / Konsole / Knopfhöhe | je 32 Bilder, **0/0/0** (Zeiger 44/36 px) und **0/0/0** (Finger 44 px) |
| AP3 | Bilderlauf mit Risikoliste, Firefox und WebKit | dasselbe über 14 Seiten | je 112 Bilder, **0 Überlauf / 0 Knopfhöhe**; Firefox **2–4 Konsolenfehler**, sämtlich abgebrochene Schriftabrufe — vor der Änderung 3, nach der Änderung 2 auf derselben Seite (F-S10-AP3-07) |
| AP3 | `tools/screenshots/kontrast.py` | gerechnete Paare / verfehlt | **22 / 0** |
| AP3 | `tools/vollstaendigkeit/pruefen.py` | Befunde vorher → nachher | **329 → 335**; die sechs sind `→` in der Pfadschreibweise (3 in Kommentaren, 3 in sichtbarem Text). **Hexfarben außerhalb `:root`: 0** |
| AP3 | `tools/wortliste/` · `tools/linkprobe/` | | **0/0/0** · **117/0** |
| AP4 | `php -l` | berührte PHP-Dateien | **0 Fehler in 14 Dateien** |
| AP4 | frisches Paket, roh im ZIP gezählt | Einträge mit `edsk1:` / Einträge gesamt | **3 von 3** (`kopf.json`, `eintraege/0001.json`, `manifest.json`) |
| AP4 | `grep` über den **ganzen Ablageordner** (ZIP **und** `konto.json`) | Treffer für die E-Mail-Adresse des Kontos | **0** |
| AP4 | `head -c 40 konto.json` | Begleitdatei versiegelt | `edsk1:` |
| AP4 | `edbak_paket_kopf_lesen()`, `edbak_paket_teil_lesen()`, `edbak_begleit_lesen()` | die drei Leser öffnen das versiegelte Paket | **3 von 3**, `version=3`, E-Mail wieder lesbar |
| AP4 | Paketgröße am Referenzkonto (83 Einsätze, 150 690 Byte Klartext) | **drei** Zahlen, wie F-10 verlangt | Fassung 2 **33 281** · Siegel ohne gzip **201 390** (+505 %) · gzip+Siegel **45 290** (+36 %) |
| AP4 | umbenanntes Paket einspielen | wird abgewiesen, nichts geschrieben | **1 von 1**, Meldung nennt „umbenannt" |
| AP4 | Teil aus einem **anderen** Paket desselben Kontos untergeschoben | wird abgewiesen, Manifest bleibt lesbar | **1 von 1**, „gehört nicht zu diesem Paket" |
| AP4 | `tools/freigabeprobe/` gegen ein Fassung-3-Paket | Umschlüsselung über den Wiederherstellungsschlüssel | **16 von 16** (vorher 14; zwei neue zählen das Siegel) |
| AP4 | `tools/wiederherstellungs-probe/` | Rundlauf, Siegel, Negativfälle | **98 von 98** (vorher 94, davon 3 rot — F-S10-AP4-03) |
| AP4 | `tools/versandprobe/` gegen die Nachbauten | FTPS, SFTP, Negativfälle | **116 von 116** (vorher 115) |
| AP4 | darin neu | `ftp` und **leeres** Protokoll werden von `sz_weg()` abgewiesen | **2 von 2** |
| AP4 | `tools/komplettprobe/ --ziel=…` | Bibliothek **und** Versand über FTPS | **63 von 63** (vorher: Absturz mit Rückgabewert 255, F-S10-AP4-02) |
| AP4 | Altziel mit `ftp` in der Datenbank, fünf Wege gemessen | `sz_weg` · „Verbindung prüfen" · Versandschub · Rückstand · Speichern | abgewiesen · abgewiesen · **übersprungen 1, Fehler 0** · `null` · abgewiesen am Protokoll |
| AP4 | `php server/jobs.php versand` | Cron-Zeile | `versand fertig · erledigt 0 · **1 übergangen**` |
| AP4 | Browser, Backup-Ziele und Status | Plakette, Formularsperre, Statuszeile | „wird übergangen" **ja** · Protokollauswahl ohne FTP **ja** · Protokollfeld leer statt geraten (`["","sftp","ftps"]`) · Status „1 umzustellen" **ja** · **0 Konsolenfehler** |
| AP4 | Bilderlauf, 4 Seiten × 8 Breiten, **beide Bedienhöhen** | Überlauf / Konsole / Knopfhöhe | je 32 Bilder **0/0/0** |
| AP4 | `tools/vollstaendigkeit/` | Befunde vorher → nachher; Hexfarben außerhalb `:root` | **335 → 341**, die sechs sind `→` der Pfadschreibweise · **0** |
| AP4 | `kontrast.py` · `tools/wortliste/` · `tools/linkprobe/` | | **22/0** · **0/0/0** · **117/0** |
| AP5 | `php -l` · `ast.parse` · `node --check` | alle 17 berührten Quelldateien des Pakets | **0 Fehler** in 9 PHP-, 5 Python- und 3 JS-Dateien |
| AP5 | `tools/wartungsprobe/probe.php` | Wartungsmodus, Ausnahmeliste, neue Erwartung 6a | **57 von 57** — **vorher 55 mit 1 nicht erfüllt** (F-S10-AP5-01) |
| AP5 | darin 6a | `betrieb_schluesselblatt.php` im Wartungsmodus | **200**, und WEDER Balken NOCH Gerüst (es wird gedruckt) |
| AP5 | `tools/referenzdatensatz/fixture/riegelprobe.php` | **beide** Riegel, je **beide** Richtungen | **10 von 10**: Erzeuger nimmt `edk1:` an (741 KB) und weist `edka1:` ab (Rückgabe 2, keine Datei); `demo_fixture_laden()` nimmt die echte Fixture an und weist eine verbogene ab, für `pat_wrap_pw` **und** `pat_wrap_rc`; SHA-256 der echten Fixture vorher/nachher **gleich** |
| AP5 | darin Erwartung 5 | der Abbruch kostet die Installation nichts: `demo_reset_wenn_faellig()` mit verbogener Fixture | **abgefangen**, Rückgabe `false`, Demo-Konto **88 → 88** Einsätze; der Grund steht im `error_log` |
| AP5 | `php -r 'demo_fixture_laden()'` gegen die ausgelieferte Fixture | Positivfall des zweiten Riegels | **angenommen**, `edk1:`/`edk1:`, 600 000 Runden |
| AP5 | `tools/referenzdatensatz/einspielen/sitzungsprobe.py` | `sitzung.py` öffnet beide Hüllenfassungen | **2 von 2**, je gegen `users.pat_key_check` gerechnet (nicht nur „keine Ausnahme") |
| AP5 | `tools/wiederherstellungs-probe/probe.php` | Rundlauf, Siegel, Negativfälle, **Teil 12** | **106 von 106** (vorher 98; Teil 12 bringt 8) |
| AP5 | darin Teil 12 | Anteil weg: Zustand, Auslieferung, beide Hüllenprüfungen, Einspielen, Schlüsselfelder, Adminpaket, Zurücklegen | **8 von 8**; `config.php` **nicht angefasst** (nur `$CFG` im Speicher) |
| AP5 | `tools/komplettprobe/probe.php --pruefdb --ziel` | Bibliothek, Versand, **Teil 11** | **72 von 72** (vorher **63**, dieselben Schalter). Ohne Schalter: **55 → 64** — die Zahl muss sagen, womit gemessen wurde |
| AP5 | darin Teil 11 | Anteil, Vor-Anteil und Serverschlüssel der **Installation** im Klartext-Dump | **0 Treffer** in 1 904 401 Byte; die **Kennung** dagegen **3 Treffer** (`app_state.kdf_anteil_kennung`) |
| AP5 | darin Teil 11 | Komplettsicherung öffnen, während der Anteil fehlt | **3 Blöcke**, kein Fehler — zwei Geheimnisse, nur eines weg |
| AP5 | `tools/anteilprobe/endpunkt.py` | `api/kdf_upgrade.php`, **neu mit E0** und mit Demo-Reset-Warnung | **34 von 34** (vorher 33; E0 macht die Voraussetzung zur Erwartung). Ein Zwischenlauf meldete **30 von 34** — Demo-Reset um 18:27:55 UTC mitten im Lauf, F-S10-AP5-12; der Lauf sagt das jetzt selbst |
| AP5 | `tools/referenzdatensatz/generator/pruefen.py` | Einzelprüfungen / Befunde, **neu mit HKDF-Block** | **283 997 / 0** (vorher 283 989; RFC 5869 Prüffall 1 plus Hüllenrundlauf in beide Fassungen) |
| AP5 | `tools/pruefkonten/pruefkonten.php anlegen 8` | Prüfkonten mit irgendeiner Hülle | **0 von 8** — E-S10-15 „falls es Hüllen anlegt" ist mit Nein beantwortet |
| AP5 | dieselben Konten, Pakete gelesen | Fassung-1-Pakete nach AP4 noch lesbar | **16 von 16** (die Lesetoleranz aus Backlog Nr. 46, erstmals gemessen) |
| AP5 | `tools/anteilprobe/probe.php --schreiben` | Regression der Serverseite | **69 von 69**, `config.php` byte-gleich |
| AP5 | `tools/freigabeprobe/probe.mjs` | Regression: Umschlüsselung über den Wiederherstellungsschlüssel gegen ein Fassung-3-Paket | **16 von 16** — der Posten aus E-S10-15, der schon in AP4 erledigt wurde; hier nur nachgemessen, damit er in AP5 nicht als ungeprüft dasteht |
| AP5 | `tools/referenzdatensatz/browser/demo_pruefen.mjs`, **dreimal hintereinander** | Demo-Konto im Browser: anlegen, lesen, verändern, zurücksetzen, Identität gesperrt | **24 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler** (193 gescheiterte Kartenkacheln gezählt und ausgewiesen). **Vorher: lief nicht durch** — Absturz in Abschnitt 5, davor drei falsche Befunde (F-S10-AP5-13) |
| AP5 | darin neu | Schloss an den geschützten Feldern · `api/kdf_upgrade.php` am Demo-Konto · E-Mail nach der Abweisung | **5** `.symbol-schutz` · **`{"ok":true,"uebersprungen":"demo"}`** (vorher `403 csrf`) · Adresse **unverändert**, auch in der Datenbank |
| AP5 | derselbe Lauf mit greifender Mengenbremse | trennt „nicht gemessen" von „verfehlt" (E-P1-20, wie F-11) | **12 gemessen / 11 nicht gemessen / 0 Befunde**, Rückgabe **0** — vorher wären das elf rote Zeilen gewesen |
| AP5 | `tools/versandprobe/probe.php /tmp/versandprobe` | Regression FTPS/SFTP und die Negativfälle aus AP4 | **116 von 116** (die Anleitung daneben nannte **115** — F-S10-AP5-03) |
| AP5 | `tools/jobprobe/probe.php` | **neu:** die Zahl `uebergangen` übersteht den festen Schlüsselsatz von `jobs_lauf()` | **27 von 27** (vorher 24; drei neue Erwartungen, `uebergangen = 0` als `integer`) |
| AP5 | `python3 tools/design/tabellen.py alle` gegen `docs/Design.md` | erzeugte Tabellenzeilen / davon abweichend | **237 / 0** — **vorher 7 abweichend** (sechs Symbolzählungen und eine Zeilennummer, alle von S10/AP3 verursacht, F-S10-AP5-09) |
| AP5 | darin Teil 11, neue Gegenprobe | Serverschlüssel der **Installation** gegen den der Arbeitskopie | **verschieden** — ohne diese Zeile hätte die Suche darüber den frisch erzeugten Schlüssel der Kopie gesucht und wäre zwangsläufig grün gewesen (F-S10-AP5-07) |
| AP5 | Kreisläufe (R24) | csv / edbak, unerklärte Abweichungen | **9120 / 0** und **287 687 / 0** |
| AP5 | `tools/klickprobe/probe.mjs` | Regression über alle Bedienwege | **43 von 43** |
| AP5 | `tools/vollstaendigkeit/` | Befunde vorher → nachher; Hexfarben außerhalb `:root` | **341 → 340** — der eine ist `server/config.php`, das beide Prüfmittel seit AP5 nicht mehr lesen (F-S10-AP5-06). AP5 fasst kein Markup an; Sollmenge weiter **220** Klassen, **50** ohne Gegenstück, **269** Unicode-Hinweise · **0** |
| AP5 | `tools/linkprobe/` | Verweise / Abweichungen / tote Zeilen, über wie viele Zielseiten | **117 / 0 / 0** über **100** Zielseiten (vorher 101 — dieselbe Ausnahme). S10 hat **0** neue parametrisierte Verweise gebracht; die Zahl ist gleich geblieben, weil es nichts zu zählen gab |
| AP5 | `tools/wortliste/` | Treffer / ungenutzte Ausnahmen / durchgerutschte Fallen | **0/0/0** bei **96** Ausnahmeregeln, alle 96 gegriffen |
| AP5 | Nachlese auf der Prüfinstallation | Wegwerfkonten `@example.invalid` / verwaiste Paketordner nach dem Lauf | **0** · **0** (vorher 2 · 3 — F-S10-AP5-02) |
| AP5 | Bilderlauf, 4 Betriebsseiten × 8 Breiten (45-, 46-, 48-, 48a-) | Überlauf / Konsole / Knopfhöhe | **32 Bilder, 0/0/0** — als Gegenprobe: AP5 ändert unter `server/` genau eine Stelle, und die hat keine Oberfläche |

---

## 6. Gesammelte Fehlerfunde (K4)

Funde während der Umsetzung, die nicht blockieren, werden hier gesammelt und
am Ende in den Backlog überführt.

**Woher die Funde von AP1 stammen.** Vor Beginn der Umsetzung ist das Konzept
**adversarisch gegen den Code gelesen** worden — sechs Angriffsrichtungen,
jede mit dem Auftrag zu widerlegen statt zu bestätigen, jeder Fund mit Beleg
aus dem Quelltext. Drei Richtungen sind durchgelaufen (Hüllen-Leser,
Sicherung/Wiederherstellung, Adminpakete), zwei blieben stehen (Versand,
Oberfläche/Doku) und werden vor AP4 nachgeholt. Die Funde unten sind
**von Hand am Code nachgeprüft**, nicht übernommen.

| Nr. | Fund | Wo | Entscheidung |
|---|---|---|---|
| F-1 | `EdCrypto.getContentKey()` ruft `decrypt()` unmittelbar und wirft damit an jeder `edka1:`-Hülle — auch über `EdKeyGuard.contentKey()`, das **jede** Anzeigeseite benutzt. Der Katalog der „fünf Stellen" in 1.2 zählt sie nicht mit | `crypto.js`, `keyguard.js` | **Erledigt in AP2.** `getContentKey()` geht über `huelleOeffnen()`. Ohne das wäre nach AP2 jede Seite gesperrt, und zwar erst beim zweiten Aufruf — der erste kommt aus dem Vormerkfach |
| F-2 | `WRAP_RE` prüft `pat_wrap_pw` **und** `pat_wrap_rc`. Seit AP1 nimmt sie `edka1:` an — für `pat_wrap_rc` soll das nie gelten (E-S10-04), und nichts hält es auf | `validate_lib.php` | **Erledigt in AP2.** `WRAP_PW_RE` und `WRAP_RC_RE`, dazu `huelle_pw_pruefen()` / `huelle_rc_pruefen()` in `serverkrypto_lib.php`. Ein `edka1:`-`pat_wrap_rc` wäre der Verlust des Rückwegs — genau das, was die Zusage ausschließt |
| F-3 | Die Kennungsprüfung sitzt an **einem von vier** Schreibwegen für `pat_wrap_pw`: `pw_handling.php` (Erstvergabe **und** Reset), `einstellungen.php` (Passwortwechsel) und `api/kdf_upgrade.php` — nur der letzte prüft | vier Dateien | **Erledigt in AP2.** Dieselbe Prüffunktion an allen vier. „Feldkatalog statt Sonderfall" gilt auch hier: eine Prüfung, die an drei Stellen fehlt, ist keine |
| F-11 | Die Mengenbremse des Demo-Kontos (E-P1-20: 20 Anmeldungen je Stunde) schlägt zu, wenn der Umstellungslauf mehrfach hintereinander fährt. Der Lauf blieb dann **drei Minuten** in der Zeitgrenze stehen und meldete „Timeout exceeded" — während auf der Anmeldeseite der wahre Grund stand | `tools/anteilprobe/umstellungslauf.mjs` | **Erledigt in AP2** (Prüfmittel, kein Anwendungsfehler). Der Lauf liest die Meldung und zählt den Demo-Teil als *nicht gemessen, mit Grund*. **Dieselbe Bremse erklärt den einen nicht reproduzierbaren Fehlschlag der Endpunktprobe vom selben Tag** |
| F-12 | `waitUntil: 'networkidle'` läuft in Firefox und WebKit nie ein: Die Kartenkacheln liegen bei `tile.openstreetmap.org` und werden vom Egress-Filter abgewiesen, beide Engines versuchen es weiter. Chromium kam durch — ein Unterschied, den man **ohne die drei Motoren nie gesehen hätte** | dasselbe | **Erledigt in AP2.** Gewartet wird auf `EdCrypto`/`EdUnlock` statt auf Netzruhe |
| F-13 | Jede Engine nennt einen abgebrochenen Ladevorgang anders: `ERR_ABORTED` (Chromium), `Load request cancelled` (WebKit), `NS_BINDING_ABORTED` (Firefox). Ein Filter auf die Chromium-Schreibweise meldet in den anderen beiden eine rote Zahl für harmloses Verhalten | dasselbe | **Erledigt in AP2.** Zwei Töpfe: Fehler der **Anwendung** werden gezählt, Fehler des **Prüfstands** genannt |
| F-14 | `php -S` bedient **eine** Anfrage zur Zeit. Ein in Schritt 5 geöffneter zweiter Tab hielt mit seiner Karte die Leitung besetzt; Schritt 6 wartete daraufhin **minutenlang** auf eine Seite, die einzeln gemessen in **1,7 s** da ist | dasselbe | **Erledigt in AP2.** Der Tab wird sofort geschlossen. Drei der vier Prüfstandsfunde sahen aus wie ein Fehler der Anwendung und waren keiner — die teuerste Sorte, weil man am falschen Ende sucht |
| F-4 | Nach dem Zurückspielen eines Komplettbackups steht der Anteil **immer** auf `abweichend` — das Backup bringt `app_state` mit, `config.php` aber nicht, und `install.php` würfelt einen neuen Anteil | `komplett_lib.php`, Runbook | **Kein Fehler, sondern der Zweck der Marke** — ohne sie sähe derselbe Zustand aus wie „Passwort falsch" für alle. **In AP1 dokumentiert:** Runbook 7 nennt den Fall als Regelfall nach Schritt 5, mit Griff und Rückweg |
| F-5 | `E-S10-07` verlangt, dass `login.php` **immer** ins Vormerkfach legt — damit liegt das Anmelde-Token nach **jeder** Anmeldung im `sessionStorage`, heute nur bei mehreren Rundenzahlen (also nie) | `login.php`, `crypto.js` | **AP2, mit Ansage.** Das Fach wird von der ersten Seite geräumt, die den Inhaltsschlüssel braucht — aber „die erste Seite" ist nicht jede. Zu prüfen ist, ob eine Räumung auch ohne Hülle stattfindet; sonst bleibt das Token länger liegen als nötig |
| F-6 | `SZ_PORTS` wird geprüft, nicht `SZ_PROTOKOLLE` — `ftp` aus der einen Liste zu streichen genügt nicht | `sicherungsziel_lib.php` | **AP4.** Vor dem Bauen nachzählen, an wie vielen Stellen `ftp` steht (die Gegenlesung nennt fünf allein in der Versandprobe) |
| F-7 | Der Zweck `adminpaket\|<konto>\|<teil>` bindet **nicht das Paket**: Ein Teil aus einem älteren Paket desselben Kontos ließe sich unterschieben | `adminbackup_lib.php` | **AP4, zu entscheiden.** Der Zweck könnte den Paketstempel aufnehmen. Dagegen spricht, dass er dann beim Lesen bekannt sein muss — also aus dem Manifest kommt, das selbst versiegelt ist |
| F-8 | „`sk_versiegelt()` am ersten Teil" — es gibt keinen „ersten Teil": Die vier Leser greifen je einen benannten Eintrag im ZIP | `adminbackup_lib.php` | **AP4.** Die Fassung erkennt der Leser an **`manifest.json`**, dem einzigen Eintrag, den jedes Paket hat und jeder Leser ohnehin liest |
| F-9 | Die Abnahmezahl `grep -c '"email"' = 0` misst nicht, was sie behauptet: Die E-Mail-Adresse liegt weiter im Klartext **neben** dem Paket (Ordnername, Dateiname, Kontozeile) | AP4-Abnahme | **AP4.** Die Zahl wird umformuliert oder fällt weg. Eine grüne Zahl, die das Falsche misst, ist schlimmer als keine (`CLAUDE.md` 6) |
| F-10 | Die Größenmessung in AP4 setzt voraus, die Teile seien heute ungepackt — das ZIP packt sie bereits | AP4-Abnahme | **AP4.** Gemessen werden drei Zahlen, nicht zwei: heute · Siegel im ZIP · gzip vor dem Siegel |
| F-15 | `ui_plakette(['ton' => 'ok'])` — `.plakette-ok` gibt es im Stylesheet **nicht**. Zwei Stellen im Bestand tragen den Ton seit ihrer Einführung und stehen dort ohne Hintergrund als bloßer Text: `betrieb_server.php` (Karte „Adresssuche") und `einstellungen.php` (Zeile „Adresssuche an"). Die neue Karte wollte ihn übernehmen | `betrieb_server.php`, `einstellungen.php`, `style.css` | **Gefunden im Gegenlesen von AP3, bevor er ausgeliefert wurde.** Die neue Karte nimmt `blau` — den Ton, den `Design.md` 9.23 für „in Ordnung" führt. Die zwei Altstellen bleiben unangefasst (K4: sie gehören nicht zu S10) und sind in **Backlog Nr. 36** vermerkt — dort steht derselbe Fall schon zweimal (`warn`, Web 10.3.0), und beim dritten Mal ist es kein Zufall mehr |
| F-16 | Die Zerlegung eines Hexwerts in Vierergruppen stand **zweimal** im Code — `apk_sha_lesbar()` und das Schlüsselblatt | `apk_lib.php`, `db.php` | **Erledigt in AP3.** Einmal als `hex_vierergruppen()` in `db.php`; `apk_sha_lesbar()` ruft sie |
| F-17 | `docs/Technik.md` führte die Ausnahmeliste des Wartungsmodus als „**elf** Skripte" und ließ `auth_salt.php` in der Aufzählung aus. Tatsächlich waren es zwölf, seit Web 19.1.2 (Nr. 171) | `Technik.md`, `wartung_lib.php` | **Erledigt in AP3.** Die Zeile nennt jetzt **dreizehn** (mit dem Schlüsselblatt), zählt `auth_salt.php` mit und verweist für den Grund je Eintrag auf die Konstante. Eine Zahl in der Dokumentation, die niemand nachzählt, wird beim nächsten Eintrag wieder falsch |
| F-S10-AP3-01 | **OPcache.** `php -S` läuft mit eingeschaltetem OPcache (`opcache.enable_cli` gilt für die SAPI `cli`, der eingebaute Server heißt `cli-server`), und der prüft den Zeitstempel nur alle 2 Sekunden. Der Betriebslauf schreibt `config.php` **an der Anwendung vorbei** und wartete danach 30 Sekunden auf ein Eingabefeld, das es in der gemessenen Lage nicht gab | `tools/anteilprobe/betriebslauf.mjs` | **Fehler des Prüfmittels, nicht der Anwendung.** `config_eintrag_schreiben()` verwirft den Zwischenspeicher seit S2/AP7 selbst; der Prüfstand sitzt in einem anderen Prozess und wartet deshalb 2,2 s. Er sah zwei Stunden lang wie ein Anwendungsfehler aus |
| F-S10-AP3-02 | **`form.requestSubmit()` läuft in `confirm.js`.** Der Lauf drückte damit „wechseln", „entfernen" und „Neuanfang" — und nichts geschah: `requestSubmit()` löst `submit` aus, die Rückfrage fängt es ab. Der Aufruf kehrte klaglos zurück, und sechs Erwartungen maßen eine Seite, die sich nie geändert hatte | dasselbe | **Erledigt.** Der Lauf **drückt den Knopf und beantwortet den Dialog**, wie es eine Betreiberin täte — damit ist die Rückfrage mitgemessen. `form.submit()` ginge am Zuhörer vorbei und misst einen Weg, den niemand geht |
| F-S10-AP3-03 | **Eine Voraussetzung, die man herstellen muss.** `endpunkt.py` misst ab E4 die Umstellung einer `edk1:`-Hülle; steht das Konto schon auf `edka1:` — nach jedem eigenen Lauf, nach dem Umstellungslauf, nach jedem Anmelden im Browser —, antwortet der Endpunkt `nicht_noetig`, und der Lauf meldet **22 von 33**, ohne dass an der Anwendung etwas fehlte | `tools/anteilprobe/endpunkt.py` | **Erledigt.** Die Datei ruft die Ausgangslage jetzt selbst über `huelle_stellen.py` her und nennt sie in der Kopfzeile. Zweimal hintereinander gefahren: **33 von 33**, beide Male |
| F-S10-AP3-04 | **Ein zweiter Tab ist keine zweite Sitzung.** Der Betriebslauf meldete sich in einem Tab des Hauptkontexts an, der bereits als Betreiberin angemeldet war; `login.php` leitet dort sofort weiter, und der Lauf wartete 30 s auf ein Anmeldefeld, das es auf der Zielseite nicht gibt | dasselbe | **Erledigt.** Jede Probeanmeldung bekommt einen **eigenen Browserkontext** — eigene Cookies, und zugleich das, was gemeint ist: ein anderer Mensch, ein anderer Browser |
| F-S10-AP3-05 | **Der teure.** Der Lauf legte sechs Felder des Admin-Kontos über PHP-Quelltext zurück, den er aus JavaScript zusammensetzte — mit `JSON.stringify()`, also in **doppelten** Anführungszeichen. PHP ersetzt darin alles, was wie eine Variable aussieht. Aus dem bcrypt-Hash `$2y$12$xdD.Dxofamu…` wurde `$2y$12.`, sieben Zeichen: **Das Konto war mit keinem Passwort mehr erreichbar**, und der nächste Lauf blieb an der Anmeldung stehen, ohne zu sagen, warum | dasselbe | **Erledigt.** `phpStr()` setzt jeden Wert in **einfache** Anführungszeichen; die Rückgabe zählt am Ende **alle sechs Felder** gegen den Stand vom Anfang und meldet die abweichenden beim Namen. Wiederhergestellt wurde der Hash aus dem abgeleiteten Anmeldetoken (`krypto.ableiten()`), nicht aus dem Passwort — dieselbe Rechnung, die der Browser macht |
| F-S10-AP3-06 | **Ein Dialog, den niemand ruft, erscheint nicht.** Abschnitt 8b wartete auf den Entsperrdialog des Admin-Kontos. Das Konto hat **keine geschützten Angaben** — also fragt keine Seite von selbst danach, und der Lauf meldete zwei rote Haken für etwas, das die Anwendung richtig macht | dasselbe | **Erledigt.** Der Lauf ruft `EdUnlock.ensureContentKey()` selbst — derselbe Aufruf, den jede Seite mit geschützten Angaben macht — und geht den Weg dann zu Ende: Passwort eintragen, „Entsperren" drücken, lesen, was dasteht. **Die Meldung erscheint erst nach der Eingabe**, und das ist richtig so: Der Dialog fragt zuerst nach dem Passwort und sagt erst dann, woran die Ableitung gescheitert ist |
| F-S10-AP3-07 | **`tools/screenshots/LIESMICH.md` versprach „0 Konsolenfehler in allen drei Motoren".** In S10/AP3 meldete Firefox auf `43b-sicherungsziele` bei 360 px zwei bis drei abgebrochene Schriftabrufe (`status=2152398850` = `NS_BINDING_ABORTED`) | `tools/screenshots/` | **Kein Anwendungsfehler; die Zusage war zu stark.** Ursache ist wieder `php -S`: Bei Last stehen die Schriftabrufe in der Schlange, und Firefox bricht sie ab, sobald die Seite fertig gezeichnet ist. Gegenprobe auf **demselben Stand vor und nach** der Änderung: **3 vorher, 2 nachher**. Die LIESMICH sagt es jetzt so und nennt den Wortlaut, an dem man es erkennt — **kein Filter**, denn ein Filter machte aus einer lesbaren Auskunft eine schmeichelhafte Null |
| F-S10-AP3-08 | **Der teuerste, und er hat echte Daten gekostet.** Abschnitt 6b meldet ein Konto an und lässt die stille Umstellung laufen — die packt den Inhaltsschlüssel mit dem Datenschlüssel des **neuen** Anteils neu ein. Der Schnappschuss der Hüllen stand aber in Abschnitt 7, also **nach** 6b; zurückgelegt wurde auf einen bereits umgestellten Stand, und das `finally` nahm den zugehörigen Anteil danach wieder aus `config.php`. `umlauf-csv@gen-em.org` trug anschließend eine Hülle mit der Kennung eines Anteils, den es nicht mehr gibt — **ausgesperrt, ohne Rückweg außer dem Wiederherstellungsschlüssel**, und den kennt niemand | `tools/anteilprobe/betriebslauf.mjs` | **Erledigt.** Der Schnappschuss steht jetzt **ganz am Anfang** und umfasst die Hüllen **aller** Konten; das `finally` legt sie zurück und zählt nach. Die alte Hülle ist der einzige mögliche Rückweg — eine Umhüllung ist nicht rückrechenbar, und sie geht nur mit dem Anteil auf, den dasselbe `finally` wiederherstellt. **Das Konto ist neu eingerichtet worden** (Prüfdokument Abschnitt 0). *Die Lehre gilt über dieses Werkzeug hinaus: Ein Prüfstand, der eine Verschlüsselung anfasst, muss seinen Schnappschuss vor der **ersten** Handlung nehmen, nicht vor der, die er für die erste hält.* |
| F-S10-AP3-09 | **Die Klickprobe meldete 37 von 43 — und die Anwendung war in Ordnung.** Der Demo-Reset (alle 30 min, `demo_lib.php`) lief um 14:01:15 mitten in den Lauf, der in derselben Minute begann. Sechs Wege meldeten „Adresse nicht aufgelöst (Bestand leer?)" und „Kein Diensttag im Bestand" — beides zeigt auf die Daten, und die Daten waren vollständig. Die **Sitzungswache** aus S9/AP6 fängt nur die verlorene *Sitzung*; ein Weg, der seine Adresse erst aus einer Liste holen muss, findet nach dem Reset nichts, und dann gibt es keine Seite, auf der die Wache greifen könnte. Derselbe Lauf sechs Minuten später: **43 von 43** | `tools/klickprobe/probe.mjs` | **Erledigt in AP3** (Prüfmittel, kein Anwendungsfehler). Der Fall lässt sich nicht abfangen, ohne jeden einzelnen Weg umzubauen — er wird deshalb **benannt**: Die Probe liest `demo_letzter_reset` vor und nach dem Lauf und schreibt bei einer Änderung eine Warnung an den Anfang des Berichts und auf die Konsole (nachgewiesen: Marke während eines Laufs verstellt → Warnung erscheint, mit Uhrzeit). Der Fehlertext von `gehZu()` nennt seither **beide** möglichen Ursachen statt nur der falschen. **Die Zahl 43 von 43 in diesem Paket ist die aus dem Lauf NACH der Reparatur** — die vorherige war ein Stand, den es nicht mehr gab |
| F-S10-AP4-01 | **Das Siegel kostet ein Drittel, und zwar durch base64.** Gemessen am Referenzkonto: Fassung 2 **33 281** Byte, Siegel ohne gzip-Vorstufe **201 390** (+505 %), gzip davor **45 290** (+36 %). Die verbleibenden 36 Prozent sind **nicht** der Packlauf, sondern der base64-Rahmen von `edsk1:` — `sk_versiegeln()` gibt Text zurück, damit ein Siegel durch jede Stelle passt, die Text erwartet | `serverkrypto_lib.php` | **Bleibt so, mit Ansage.** Ein binäres Siegelformat daneben wäre ein zweites Format für dieselbe Sache und beträfe auch Backup-Ziele und Komplett-Backup; das ist keine AP4-Entscheidung. Am 5000er-Bestand hiesse es rund 11,4 → 15,5 MB. Wer es angehen will, fängt beim Komplett-Backup an — dort ist die Datei am grössten |
| F-S10-AP4-02 | **Die Komplettprobe stürzte ab, und zwar seit Längerem.** Ihr Teil 8 stellt einen Abbruch mitten im Dump nach. Am Referenzbestand (18 376 Zeilen, 438 KB) läuft der Dump in **einem** Häppchen durch — dann gibt es keinen Bauordner mehr, `gzopen()` greift auf einen Pfad, den es nicht gibt, und `gzread(false)` wirft einen TypeError. Rückgabewert **255**, die Teile 9 und 10 liefen **nie**. Die Zahl „76 von 76" in der Anleitung stammt aus einer Zeit mit grösserem Bestand | `tools/komplettprobe/probe.php` | **Erledigt in AP4** (Prüfmittel, kein Anwendungsfehler) — und es war **vorbestehend**, nachgewiesen durch einen Lauf gegen den Stand vor AP4: derselbe Absturz, exit 255. Teil 8 und die Häppchenteilung in Teil 3 sagen jetzt mit einer Zahl, dass der Bestand zu klein ist, statt abzustürzen; gemessen wird die Teilung im Messstand. Danach: **63 Erwartungen, 0 offen**, und Teil 10 (der Versand) läuft zum ersten Mal mit |
| F-S10-AP4-03 | **Die Wiederherstellungsprobe scheiterte an ihrer eigenen Arithmetik.** Teil 10 gab je Schub zwei Konten frei; der Referenzbestand hat vier, also räumten zwei Schübe die Warteschlange leer, `edbak_auftrag_lesen()` gab `null`, und drei Erwartungen meldeten `cur 2 -> —`. Der Kommentar im Code spricht noch von „allen 31 Konten" — die Probe stammt aus der Zeit der Prüfkonten | `tools/wiederherstellungs-probe/probe.php` | **Erledigt in AP4**, ebenfalls **vorbestehend** (Gegenlauf gegen den Stand vor AP4: dieselben drei). Der zweite Schub nimmt jetzt **ein** Konto, und bei weniger als zwei offenen sagt die Probe das mit einer Zahl, statt eine Erwartung zu verfehlen. Danach **98 Erwartungen, 0 offen** — vorher 94, davon 3 rot |
| F-S10-AP4-04 | **Ein Rest aus AP3:** `komp_auftrag_starten()` schickte für den fehlenden Serverschlüssel auf „die Seite Backup-Ziele" — dort steht die Karte seit Web 20.1.0 nicht mehr | `komplett_lib.php` | **Erledigt in AP4.** Beide Stellen (Kommentar und Meldung) nennen jetzt Betrieb → Servereinstellungen. *Gefunden, weil AP4 denselben Riegel für die Adminpakete gebaut hat und die Vorlage daneben lag* — nicht, weil jemand danach gesucht hätte |
| F-S10-AP4-05 | **Zwei Proben behaupteten „Fassung 2" und meinten „mehrteilig".** Freigabeprobe und Wiederherstellungsprobe prüften `version === 2` — eine Zahl, die AP4 planmäßig ändert. Dazu prüfte keine von beiden, ob die Teile **wirklich** versiegelt sind | beide Proben | **Erledigt in AP4.** Sie erwarten 3 **und** zählen das Präfix `edsk1:` an jedem ZIP-Eintrag sowie an `konto.json` — „die Nummer allein ist kein Beleg": Ein Paket, das 3 ins Manifest schreibt und die Teile offen ablegt, wäre sonst durchgekommen. Die Wiederherstellungsprobe hat dazu zwei neue Fälle: umbenanntes Paket und untergeschobener Teil aus einem anderen Paket desselben Kontos |
| F-S10-AP5-01 | **Die Wartungsprobe war seit AP3 rot, und niemand hat es gesehen.** AP3 hat `betrieb_schluesselblatt.php` in `WARTUNG_AUSNAHMEN` aufgenommen — dreizehn Einträge. Erwartung 17 der Wartungsprobe hält die Liste gegen eine **fest hingeschriebene** Sollliste mit zwölf Einträgen, damit niemand unbemerkt etwas herausnimmt. Sie tat genau das, wofür sie gebaut ist: **55 Erwartungen, 1 nicht erfüllt**, ab dem 14.09.2026 mittags. Gemeldet wurde AP3 trotzdem als erledigt | `tools/wartungsprobe/probe.php` | **Erledigt in AP5.** Sollliste auf dreizehn, dazu **Erwartung 6a**: Das Blatt ist im Wartungsmodus erreichbar (200) und trägt dabei **weder** Balken **noch** Gerüst — als ausdrückliche Gegenaussage, damit es niemand „der Vollständigkeit halber" in die Balkenprüfung nimmt. Danach **57 von 57**. *Die Lehre ist nicht „die Probe war kaputt", sondern: Wer eine Liste ändert, die ein Prüfmittel bewacht, fährt dieses Prüfmittel — es steht nicht im Standardsatz für eine Oberflächenänderung, und genau deshalb fiel es durch* |
| F-S10-AP5-02 | **Zwei Prüfmittel ließen etwas liegen.** Die Wiederherstellungsprobe legt fünf Wegwerfkonten an und löschte drei; `probe-adminname@` und `probe-adminmisch@` (beide aus den AP4-Negativfällen) blieben nach jedem Lauf in `users` stehen. Und kein Lauf räumte die Paketordner unter `server/sicherungen/` — nach den AP4- und AP5-Läufen lagen dort **drei** verwaiste Ordner mit versiegelten Paketen zu Konten, die es nicht mehr gibt | `tools/wiederherstellungs-probe/probe.php` | **Erledigt in AP5.** `$weg()` für alle fünf, `edbak_ordner_loeschen()` im `finally` von Teil 12 — wie es Teil 8 längst tat. Der Kopf der Datei sagte „löscht sie am Ende wieder"; seit jetzt stimmt es. Nachgezählt: **0** Wegwerfkonten und **0** verwaiste Ordner nach dem Lauf. *Ein Prüfmittel, dessen Rückstände sich anhäufen, verändert mit jedem Lauf den Bestand, gegen den das nächste misst* |
| F-S10-AP5-03 | **Vier Zahlen in der Dokumentation waren Abschriften, keine Messungen.** `tools/komplettprobe/LIESMICH.md` nannte „76 Erwartungen" (gemessen 71 mit Schaltern, 55 ohne), `tools/wiederherstellungs-probe/LIESMICH.md` „30 von 30" (gemessen 106), `docs/Technik.md` zweimal verschiedene Zahlen für **dieselbe** Wartungsprobe (40 und 53, gemessen 57) und „eine der **elf** Ausnahmen" (dreizehn) | `tools/*/LIESMICH.md`, `docs/Technik.md` | **Erledigt in AP5** — alle vier gemessen und mit dem Messweg dazu (welcher Bestand, welche Schalter). *Zwei verschiedene Zahlen für dasselbe Prüfmittel in einem Dokument sind der Beleg, dass beide abgeschrieben waren: Hätte jemand gemessen, wäre ihm die zweite aufgefallen* |
| F-S10-AP5-04 | **Die Voraussetzung der Endpunktprobe stand nur als `print` da.** `endpunkt.py` stellt die Ausgangslage über `huelle_stellen.py` her (die Behebung von F-S10-AP3-03). Schlug das fehl, meldete der Lauf zwei Warnzeilen — und lief weiter, maß ab E4 eine bereits umgestellte Hülle und nannte trotzdem seine grüne Zahl | `tools/anteilprobe/endpunkt.py` | **Erledigt in AP5.** Die Voraussetzung ist jetzt **Erwartung E0** und wird mitgezählt: **34 von 34**. *Eine Voraussetzung, die nur als Meldung dasteht, ist keine — die Zahl muss sie tragen* |
| F-S10-AP5-05 | **`pruefkonten.php` wäre seit AP4 mitten im Bestand abgebrochen.** Es ruft `edbak_begleit_schreiben()` unmittelbar, und die versiegelt `konto.json` seit der Fassung 3. Ohne eingetragenen Serverschlüssel wirft sie — nach dem ersten `commit()`, also mit halbem Bestand in der Datenbank. `edbak_sicherung_erzeugen()` hat den Riegel in AP4 bekommen, dieser Aufrufer nicht | `tools/pruefkonten/pruefkonten.php` | **Erledigt in AP5.** `serverschluessel_da()` vor der Schleife, mit dem Satz, wo der Schlüssel herkommt. *Ein Riegel an der Bibliotheksfunktion deckt nicht die Werkzeuge ab, die eine Ebene tiefer greifen* |
| F-S10-AP5-13 | **`tools/referenzdatensatz/browser/demo_pruefen.mjs` lief überhaupt nicht durch — und tat es schon vor S10 nicht.** Vier Fehler, alle vier in der Probe und keiner in der Anwendung: **(1)** `zustand()` teilte den Kacheltext an **einem** Umbruch, `innerText` liefert heute `"83\n\nEinsätze"` — die vier Kennzahlen fielen weg, und jede Erwartung darauf verglich `undefined` gegen '83'. **(2)** Der Reset-Knopf heisst **„Zurücksetzen"**, gesucht wurde „Auf Standard zurücksetzen"; der ganze Zweig wurde übersprungen, der Lauf setzte **nie** zurück und meldete danach die unveränderten Zahlen als Befunde. **(3)** Die Diagnose wurde über `/diagnose\s*🔒\s*\n(.+)/` gelesen — `dtGeschuetzt()` setzt das Schloss längst als `<svg>`, und `innerText` liefert dafür nichts; gemeldet wurde „Schlüsselmaterial passt nicht zum Chiffretext", während der Klartext gut lesbar danebenstand. **(4)** `window.CSRF` ist `undefined` (die Konstante steht als `const`, nicht am `window`), also antwortete `api/kdf_upgrade.php` mit `403 csrf` — gemessen wurde die CSRF-Sperre statt der Zusage, dass der Endpunkt das Demo-Konto überspringt | `tools/referenzdatensatz/browser/demo_pruefen.mjs` | **Erledigt in AP5** (nach ausdrücklicher Anweisung vor AP6). Gegriffen wird jetzt am `form`-Attribut (`f-demo-anlegen`, `f-demo-reset`) und am `id` des Profilformulars (`pfform`) statt am Beschriftungstext; die Kacheln werden an `/\n+/` geteilt; die Diagnose am Zeilenpaar, **das Schloss getrennt am Markup** (`.symbol-schutz`, 5 gezählt — `CLAUDE.md` 4 verlangt es, und hier hat es nie jemand nachgesehen); `kdf_upgrade` mit dem echten Token, und aus der Protokollzeile ist eine **Erwartung** geworden (`{"ok":true,"uebersprungen":"demo"}`). Dazu die Mengenbremse des Demo-Kontos (E-P1-20): Der Lauf liest den Grund von der Anmeldeseite und zählt den Abschnitt als **nicht gemessen**, statt einen Stapel roter Zeilen zu melden — dieselbe Lösung wie F-11 im Umstellungslauf. Und der Kachelfilter greift jetzt **mit Beleg**: Eine Konsolenmeldung ohne Adresse (`ERR_TOO_MANY_RETRIES`) gilt nur dann als Kachelrauschen, wenn im selben Lauf eine fremde Kachel gescheitert ist; etwas unter `127.0.0.1` bleibt stehen. **Gemessen: 24 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler** (193 Kacheln gezählt und ausgewiesen), dreimal hintereinander. *Der Reset selbst war die ganze Zeit in Ordnung — dass der Papierkorb des Demo-Kontos mit jedem Lauf wuchs, lag daran, dass die Probe den Knopf nie fand. Ich hatte das zunächst als möglichen Anwendungsfehler gemeldet; es war keiner* |
| F-S10-AP5-12 | **Der Demo-Reset fällt auch der Endpunktprobe in den Lauf.** Gemessen am 14.09.2026, 18:27:55 UTC: Der Schlusslauf meldete **30 von 34**, ein zweiter Lauf Minuten später **34 von 34**. Abschnitt E10 misst das Demo-Konto; während des 30-Minuten-Resets ist es kurz weg, der Endpunkt antwortet **404**, und zwei Erwartungen fallen durch. Die Klickprobe hat für genau das seit AP3 eine Warnung (F-S10-AP3-09) — die Endpunktprobe nicht, obwohl sie dasselbe Konto anfasst | `tools/anteilprobe/endpunkt.py` | **Erledigt in AP5.** Sie liest `demo_letzter_reset()` vor und nach dem Lauf und **sagt es**, wenn ein Reset hineinfiel: „Ein Fehlschlag dort ist dann KEIN Befund an der Anwendung — den Lauf wiederholen." *Die Falle war seit AP3 bekannt und an einer Stelle behoben; die zweite Stelle hat niemand gesucht. Eine Falle, die man an einem Ort schliesst, ist an den anderen noch offen* |
| F-S10-AP5-09 | **Die erzeugten Tabellen in `docs/Design.md` standen sieben Zeilen hinter ihren Quellen.** `CLAUDE.md` 5 sagt, sie seien erzeugt (`python3 tools/design/tabellen.py alle`) und wer eine von Hand ändere, ändere sie an der falschen Stelle — was daraus folgt, sagt der Satz nicht: **Wer eine Seite baut, muss sie neu erzeugen.** S10/AP3 hat `betrieb_schluesselblatt.php` und die Karte „Schlüssel des Servers" gebracht; damit wuchsen sechs Symbolzählungen (`schloss.svg` 14 → 17, `status.svg` 27 → 29, `haken.svg` 25 → 26, `korb.svg` 21 → 22, `tausch.svg` 11 → 12, `warnung.svg` 29 → 30) und die Zeilennummer von `ui_krypto_bootstrap()` (2502 → 2509) | `docs/Design.md` | **Erledigt in AP5.** Neu erzeugt und eingesetzt; Gegenprobe: **237 erzeugte Tabellenzeilen, 0 abweichend**. *Der Lauf kostet Sekunden und ist rein lesend — er gehört in denselben Satz wie Wortliste und Vollständigkeit, wenn ein Paket eine Seite anfasst* |
| F-S10-AP5-10 | **Drei weitere abgeschriebene Zahlen in Anleitungen, alle aus AP4.** `tools/versandprobe/LIESMICH.md` nannte **115** (gemessen 116 — AP4 hat den leeren Protokollfall dazugebaut), `tools/freigabeprobe/LIESMICH.md` nannte die drei neuen Siegel-Erwartungen gar nicht (14 statt 16), und `tools/komplettprobe/klickweg.mjs` trug **320 000** Runden — einen Wert, den es seit Backlog Nr. 155 nicht mehr gibt — sowie zwei **Lücken** dort, wo `waitForLoadState` und `waitForNavigation` stehen sollten | `tools/versandprobe/LIESMICH.md`, `tools/freigabeprobe/LIESMICH.md`, `tools/komplettprobe/klickweg.mjs` und `LIESMICH.md` | **Erledigt in AP5**, alle drei gemessen statt abgeschrieben. *Ein Kommentar, der eine Falle beschreiben soll, nennt die Funktion, die hineinführt — sonst beschreibt er nichts* |
| F-S10-AP5-11 | **Die Abmelde-Probe beschrieb seit AP2 ein Fach falsch, das sie selbst misst.** S10/AP2 hat den Inhalt des Vormerkfachs gewechselt: `crypto.js` schreibt `{ hf: haelften, tk: tokens }` statt `{ dk, tk }`, weil der Datenschlüssel erst aus Hälfte **und** Anteil entsteht und deshalb nicht mehr im Fach liegen darf. `tools/abmelde-probe/probe.html` führte weiter `dk` | `tools/abmelde-probe/probe.html` | **Erledigt in AP5.** Am Messergebnis ändert sich nichts — die Probe füllt das Fach selbst und misst nur, ob es nach dem Abmelden leer ist. An der Auskunft ändert sich alles: Wer die Liste liest, liest sie als Beschreibung dessen, was im Browser liegt |
| F-S10-AP5-07 | **Eine Erwartung, die nicht fehlschlagen konnte.** Teil 11 der Komplettprobe suchte den Serverschlüssel im Dump und nahm ihn aus `$CFG['server_key']` — aber die Probe arbeitet gegen eine **Kopie** von `server/`, deren `config.php` oben einen **frisch gewürfelten** Schlüssel bekommt (`bin2hex(random_bytes(32))`), damit sie nie mit dem echten siegelt. Gesucht wurde also ein Wert, der Sekunden alt war und nie in der Datenbank stand: **0 Treffer waren zwangsläufig**, der grüne Haken sagte nichts. Der Anteil daneben war richtig gemessen — `$CFG` trägt ihn unverändert —, und beide Zeilen sahen gleich aus | `tools/komplettprobe/probe.php` | **Erledigt in AP5**, gefunden von der Gegenprobe des eigenen Pakets. Gesucht wird jetzt `$CFG_ECHT['server_key']`, und **eine zweite Erwartung belegt, dass die beiden Schlüssel verschieden sind** — sonst hätte niemand gesehen, wenn die erste wieder den falschen nähme. Danach **72 von 72** statt 71. *Eine Probe gegen eine Arbeitskopie misst nicht überall dasselbe wie eine gegen die Installation; welche Werte aus welcher Quelle kommen, muss an der Zeile stehen und nicht im Kopf des Lesers* |
| F-S10-AP5-08 | **Zwei Prüfmittel hätten in `app_state` schreiben können.** `anteil_zustand()` legt die Marke `kdf_anteil_kennung` an, wenn sie fehlt und ein Wert in `config.php` steht (E-S10-U-02) — richtig für die Anwendung, falsch für ein Prüfmittel: Teil 12 und Teil 11 hätten auf einer Installation ohne Marke genau den Zustand hergestellt, den sie messen. Die Arbeitskopie schützt davor nicht, sie betrifft nur `server/`, nicht MariaDB | `tools/wiederherstellungs-probe/probe.php`, `tools/komplettprobe/probe.php` | **Erledigt in AP5.** Beide lesen die Marke mit `schluessel_marke_lesen()`, **bevor** sie `anteil_zustand()` rufen, und lassen den Block ohne Marke mit einer Begründung aus, statt ihn zu fahren. *Nicht gemessen mit Grund ist besser als gemessen an einem Zustand, den man selbst erzeugt hat* |
| F-S10-AP5-06 | **Zwei Prüfmittel lasen `server/config.php` — die Datei mit beiden Geheimnissen.** `tools/vollstaendigkeit/pruefen.py` und `tools/linkprobe/probe.py` laufen über alles unter `server/` und nahmen bisher nur drei **Verzeichnisse** aus (`vendor`, `fonts`, `demo`), keine Datei. `config.php` trägt seit S10 den Serverschlüssel **und** den Server-Anteil. Heute war nichts ausgetreten — kein Muster der beiden Werkzeuge trifft auf einen 64-Hex-Wert zu, gemeldet wurde aus dieser Datei genau **ein** `→` aus dem Kopfkommentar. Der zweite Schaden war schon da: Die Datei gibt es auf einem blanken Auscheck nicht, dieselbe Probe meldete also je nach Umgebung eine andere Zahl | `tools/vollstaendigkeit/pruefen.py`, `tools/linkprobe/probe.py` | **Erledigt in AP5.** Beide haben eine `AUSGENOMMEN`-Liste und überspringen `config.php`; die Begründung steht im Kopf und in beiden `LIESMICH.md`. Gemessen: Vollständigkeit **341 → 340**, Linkprobe **101 → 100** Zielseiten bei unveränderten **117** Verweisen, `config.php` **0-mal** in der ausführlichen Ausgabe. *Gefunden hat das nicht ein Prüfmittel, sondern das Nachlesen, welche Dateien die Prüfmittel überhaupt anfassen — eine Frage, die man erst stellt, wenn eine Datei etwas zu verlieren hat* |
| F-S10-AP4-06 | **Der Test „fehlendes Teil" maß nach AP4 etwas anderes als draufsteht.** Er kopierte das Paket unter einem anderen Namen und löschte daraus ein Teil — seit E-S10-U-11 ist die Kopie schon wegen des Namens unlesbar, und die Meldung war der Siegelfehler statt „es fehlen N von M Teilen" | `tools/wiederherstellungs-probe/probe.php` | **Erledigt in AP4.** Gearbeitet wird am Original mit einer Sicherung ausserhalb der Ablage; der Namensfall steht als **eigene** Erwartung daneben. Dieselbe Falle wie bei den Prüfständen aus AP3: Ein Testaufbau, der zwei Dinge gleichzeitig ändert, misst keines von beiden |

---

## 7. Fragen — entschieden am 13.09.2026

| Nr. | Frage | Entscheidung | Übernommen in |
|---|---|---|---|
| F-S10-1 | Konto-Anteil aus Salz, Kontonummer oder serverseitig gewähltem Salz? | **Kontonummer** | E-S10-03 |
| F-S10-2 | Hüllenkennung `edk2:` oder eigene Kennung mit Anteil-Kennung? | **eigene Kennung `edka1:<kennung>:`** | E-S10-05 |
| F-S10-3 | Ort der Schlüsselfunktionen? | **Betrieb → Servereinstellungen**, Karte „Schlüssel des Servers“ | E-S10-12 |
| F-S10-4 | Altpakete umsiegeln oder liegen lassen? | **Gegenstandslos — es gibt keine Altpakete**; kein Job, kein Zähler, Lesetoleranz bis Nr. 46 | E-S10-13 |
| F-S10-5 | FTPS behalten oder abschaffen? | **behalten, mit Hinweis** | E-S10-14 |

**Aus AP5 (14.09.2026) — gestellt und entschieden:**

| Nr. | Frage | Sachlage | Entscheidung |
|---|---|---|---|
| F-S10-6 | **Entschieden am 14.09.2026: Weg (a) — jetzt, als Web 20.2.1.** Braucht die Demo-Hülle auch den **zweiten** Riegel in `server/demo_lib.php`? | AP5 hat den ersten gebaut: `fixture/erzeugen.php` bricht ab, wenn die Hülle des Demo-Kontos nicht `edk1:` trägt (E-S10-15, gemessen 4 von 4). Der zweite fehlt: `demo_fixture_laden()` prüft die Rundenzahl gegen `KDF_ITER_LISTE`, aber nicht das Hüllenpräfix — eine Fixture mit `edka1:` würde beim Reset **still** eingespielt, und das Demo-Konto käme auf der Produktivinstallation herein, ohne etwas öffnen zu können. Alle 30 Minuten aufs Neue. **Backlog Nr. 155 hat für die Rundenzahl genau dieses Paar gebaut**, mit der Begründung „Ohne den zweiten Riegel wäre ein Reset still erfolgreich und niemand käme mehr herein". Heute ist die Lücke nicht akut: Die ausgelieferte Fixture trägt `edk1:` (nachgesehen), und es gibt keinen zweiten Weg, eine Fixture zu bauen | **(a) — gebaut als Web 20.2.1.** `demo_fixture_laden()` prüft beide Hüllen mit der gemeinsamen Prüfschicht (`huelle_pw_pruefen($wrap, istDemo: true)`, `huelle_rc_pruefen()`), nicht mit einem eigenen Ausdruck. Damit trägt **jeder** der beiden installationsgebundenen Werte der Fixture ein Riegelpaar — Rundenzahl seit Nr. 155, Hüllenfassung seit jetzt; die Tafel steht in `docs/Technik.md` 4.99a. **AP5 hat damit doch eine Stufe** (Korrektur, kein Schema, keine Migration). Gemessen: `riegelprobe.php` **10 von 10** (vorher 4), darin der abgefangene Reset und die SHA-256 der echten Fixture vorher/nachher |

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
