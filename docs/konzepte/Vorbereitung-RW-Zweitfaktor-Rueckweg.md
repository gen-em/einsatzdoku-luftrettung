# Vorbereitung RW — der Rückweg beim Zweitfaktor über den Wiederherstellungsschlüssel

*Angelegt 24.09.2026 in der Umsetzung von P5c (Zweig
`claude/p5c-mockups-konzept-4yeomf`, nach AP4 `255be7f`). Grundlage für ein
**Einschubkonzept RW** in einer eigenen Sitzung. Anlass: **Q-P5c-38** — die
Betreiberin hat entschieden, den Rückweg über den Wiederherstellungsschlüssel
**jetzt fälschungssicher** zu bauen (Alternative C). Dieses Dokument ist kein
Konzept: Es hält fest, was gefunden, gemessen und im Code festgelegt ist, und
welche Fragen das Konzept beantworten muss.*

---

## 1. Auftrag

Eine NutzerIn, die ihr Gerät für den Zweitfaktor **und** ihre
Wiederherstellungscodes verloren hat, soll den Zweitfaktor selbst
zurücksetzen können, indem sie **beweist, dass sie den
Wiederherstellungsschlüssel kennt** — und zwar so, dass **niemand diesen
Beweis aus einer Kopie der Datenbank fälschen kann**. Der Server darf dabei
weiterhin nichts entschlüsseln können (`CLAUDE.md` 4, Ende-zu-Ende-Zusage).

## 2. Befund — warum der beschlossene Weg nicht trägt (F-P5c-106)

E-P5c-42 (dritter Punkt) sah vor: Zurücksetzen mit dem
Wiederherstellungsschlüssel in `pw_handling.php`, nur für die Rolle `user` und
nur, wenn `pat_key_check` gesetzt ist **und passt**.

`pat_key_check` ist ein **Verifizierer, kein Geheimnis**:

- berechnet im Browser als `SHA-256('edk-ckchk:' + ckHex)`, auf 32
  Hexzeichen gekürzt (`server/assets/crypto.js`, `contentKeyCheck()`);
- gespeichert in `users.pat_key_check`;
- verglichen in `pw_handling.php` (Zurücksetzen, rund Z. 169) und in
  `einstellungen.php` (Passwortwechsel) — **gegen den Wert, den der Browser
  schickt**.

Wer eine Kopie der Datenbank hat (ein unverschlüsseltes Komplett-Backup, ein
Dump), kennt den Wert und kann ihn vorlegen, **ohne den
Wiederherstellungsschlüssel zu kennen**.

- **Beim Passwort-Zurücksetzen ist das unschädlich:** Ohne den echten
  Schlüssel entsteht nur eine unbrauchbare Passwort-Hülle; die verschlüsselten
  Felder bleiben unlesbar. Der Prüfwert schützt dort gegen ein Versehen (ein
  falscher Schlüssel würde die Daten unlesbar machen), nicht gegen einen
  Angreifer — und dafür ist er gebaut.
- **Beim Zweitfaktor schon:** Mit Postfach (Setz-Link) **und**
  Datenbankkopie ließen sich Passwort **und** Zweitfaktor zurücksetzen. Danach
  ist das Konto angemeldet: alle Klartextangaben (Spur, Phasenkoordinaten,
  Zeiten, Besatzung) live lesbar, alles änderbar und löschbar.

## 3. Gemessen (Zuarbeit aus Rahmenplan 6, erledigt 24.09.2026)

`SELECT role, SUM(pat_key_check IS NULL) AS ohne, COUNT(*) AS alle FROM users GROUP BY role;`

| Anlage | `user` ohne / alle | `betreiberin` ohne / alle |
|---|---|---|
| Staging | 0 / 2 | 0 / 2 |
| Produktiv | 0 / 1 | **1 / 1** |

Das eine Konto ohne Prüfwert war die **einzige BetreiberIn auf Produktiv**
(vor der Migration des Prüfwerts angelegt, Passwort seither nicht
gewechselt). Für den Rückweg war das ohne Belang — er gilt nach E-P5c-42 nur
für `user`. **Noch am 24.09.2026 hat die Betreiberin das Passwort gewechselt;
danach auf Produktiv 0 / 1 und 0 / 1 — kein Konto ohne Prüfwert auf beiden
Anlagen.**

## 4. Was im Code feststeht (Randbedingungen)

| Nr. | Tatsache | Wo |
|---|---|---|
| R1 | **Der Inhaltsschlüssel (ck) wird nie gewechselt.** Er entsteht einmal, bei der Erstvergabe des Passworts, im Browser (`EdCrypto.randomHex(32)`). Passwortwechsel, Zurücksetzen und „Neuen Wiederherstellungsschlüssel erzeugen" packen ihn nur neu ein. Ein Schlüsselpaar, das an ck hängt, bleibt also für die Lebensdauer des Kontos gültig. | `pw_handling.php` (Z. 571), `api/schluessel_erneuern.php` |
| R2 | **`pat_wrap_rc` geht heute schon an jeden mit gültigem Setz-Link** (`const WRAP_RC` im Seitenquelltext). Seine Stärke ist die Entropie des Wiederherstellungsschlüssels (20 Zeichen, 32er-Alphabet) und PBKDF2. | `pw_handling.php` (Z. 480) |
| R3 | **`pat_wrap_rc` hängt nicht am Server-Anteil** (`kdf_anteil`) — Absicht, er ist der Rückweg für dessen Verlust. `WRAP_RC_RE` und `huelle_rc_pruefen()` erzwingen das. | `CLAUDE.md` 4, `pw_handling.php` |
| R4 | Verschlüsseln mit ck im Browser: `EdCrypto.encrypt(keyHex, text)` / `decrypt()` — AES-GCM, Präfix `edk1:`. | `server/assets/crypto.js` |
| R5 | **WebCrypto kann ECDSA P-256** in Chromium, Firefox und WebKit. **PHP prüft es mit `openssl_verify()`**; örtlich gemessen: `openssl` und `sodium` geladen, Kurve `prime256v1` vorhanden. **Beim Hoster (Staging, Produktiv) nicht nachgesehen.** Achtung Format: WebCrypto signiert als `r‖s` (64 Byte), OpenSSL erwartet DER. | örtlich, 24.09.2026 |
| R6 | Konto-Backup (`.edbak`) und Freigabe tragen `pat_wrap_rc` und `pat_key_check` mit — für das Einspielen in ein **anderes** Konto. Das Komplett-Backup trägt die ganze Tabelle `users`. | `adminbackup_lib.php`, `einstellungen.php` (Freigabe) |
| R7 | Das Demo-Konto bekommt seine Hüllen aus der Fixture und wird alle 30 Minuten zurückgesetzt; der Zweitfaktor ist dort gesperrt (P5c AP5, „Demo-Sperre"). | `demo_lib.php` |
| R8 | Der Setz-Link (`pw_handling.php`) ist heute der einzige Ort, an dem der Browser mit dem Wiederherstellungsschlüssel ck in der Hand hält, **ohne** angemeldet zu sein. | `pw_handling.php` |
| R9 | Rollen seit AP4: `user`, `support`, `admin`, `betreiberin`; **nach E-P5c-42 gilt der Schlüssel-Rückweg nur für `user`**, Support-Konten setzt nur die BetreiberIn zurück. | `db.php`, Konzept P5c |

## 5. Stand von P5c, auf den der Einschub trifft

- **AP5 wird ohne den Schlüssel-Rückweg gebaut** (E-P5c-104): TOTP (RFC 6238),
  Code-Schritt in `login.php` vor der Sitzung, Einrichtung unter Einstellungen →
  Profil, zehn Wiederherstellungscodes mit `password_hash()`, Einrichtungstor
  für die Pflichtrollen, Zurücksetzen **durch die Verwaltung** nach E-P5c-42,
  Topf `totp`, Bus-Faktor. Bis RW gebaut ist, gilt damit Alternative B: Wer
  Gerät **und** Codes verliert, braucht die Verwaltung.
- **Die Namen der Spalten und Tabellen des Zweitfaktors legt AP5 fest**; sie
  stehen nach dem Push von AP5 in `server/migration_lib.php` und
  `docs/Technik.md` auf dem Zweig.
- RW kommt als **eigenes Paket vor AP11** (Arbeitsname AP5b), mit eigener
  Migration. Die Stufe des Prüfstands ist dann `haupt` (Stufenregel
  `migration`, E-P5c-88).

## 6. Skizze aus der Umsetzung — ein Vorschlag, keine Festlegung

1. **Schlüsselpaar je Konto**, ECDSA P-256, im Browser erzeugt. Der
   öffentliche Teil (SPKI) liegt beim Server; der private Teil (PKCS8) liegt
   dort **nur mit ck verschlüsselt** (`edk1:`). Der Server kann nicht
   signieren; ein Datenbankabzug enthält nur den öffentlichen Teil und eine
   Hülle, die ohne ck nichts nützt.
2. **Entstehung beim Einschalten des Zweitfaktors** — die Profilseite hat ck
   in der Hand. Jedes Konto mit Zweitfaktor hat dann ein Paar; ein
   allgemeiner Anhebelauf wäre nicht nötig. Ersetzen nur, wenn keins da ist.
3. **Der Beweis:** Der Server stellt eine Herausforderung (Zufallswert,
   kurzlebig, einmal gültig, an Konto und Zweck gebunden); der Browser öffnet
   mit dem Wiederherstellungsschlüssel `pat_wrap_rc` → ck → privaten Teil und
   signiert `Zweck ‖ Konto ‖ Herausforderung`; der Server prüft mit dem
   öffentlichen Teil.
4. **Ort:** in `pw_handling.php` (Postfach **und** Wiederherstellungsschlüssel,
   wie E-P5c-42) als ausdrückliche Wahl „Zweitfaktor ebenfalls zurücksetzen";
   oder am Code-Schritt der Anmeldung (Passwort **und**
   Wiederherstellungsschlüssel). Beides sind zwei Faktoren; der erste braucht
   keinen neuen Weg ohne Anmeldung.
5. **Folgen:** Protokolleintrag (`totp_zurueckgesetzt` mit Weg), Mail an die
   Kontoadresse, Topf im Ratenschutz, Runbook-Notweg bleibt.

## 7. Fragen, die das Konzept beantworten muss

| Nr. | Frage |
|---|---|
| 1 | **Ort** des Rückwegs: `pw_handling.php`, Code-Schritt, oder beides? |
| 2 | **Wann entsteht das Paar?** Beim Einschalten des Zweitfaktors, oder für alle Konten beim nächsten Entsperren (Anhebelauf)? Was gilt für Konten, die den Zweitfaktor schon vor RW eingeschaltet haben (AP5 ist dann ausgeliefert)? |
| 3 | **Verfahren:** ECDSA P-256 (überall in WebCrypto) oder Ed25519 (jünger; PHP über `sodium`)? Nachricht, Domänentrennung, Lebensdauer und Einmaligkeit der Herausforderung. |
| 4 | **Ersetzen und Entfernen des Paars:** nur beim ersten Mal? Beim Ausschalten des Zweitfaktors löschen? Was, wenn eine offene Sitzung ein Paar setzt, das zu einem anderen Schlüssel gehört (der Server kann die Zugehörigkeit zu ck nicht prüfen)? |
| 5 | **Rollen:** bleibt es bei `user` (E-P5c-42), oder gilt der Rückweg jetzt, da er fälschungssicher ist, auch für Support und Admin? Die BetreiberIn bleibt nach Nr. 249 ein eigener Fall. |
| 6 | **Backups:** gehört das Paar ins Konto-Backup, in die Freigabe, ins Komplett-Backup (R6)? Was geschieht beim Einspielen in ein anderes Konto? |
| 7 | **Demo-Konto** (R7): kein Paar, oder eins aus der Fixture? |
| 8 | **Oberfläche:** Braucht es eine neue Darstellung (Schalter in `pw_handling.php`, „Gerät verloren?" am Code-Schritt)? Dann Mockup und Freigabe nach `CLAUDE.md` 5 — das freigegebene Bild M-P5c-02b zeigt am Code-Schritt nur „Wiederherstellungscode verwenden" und „Zurück zur Anmeldung". |
| 9 | **Hoster** (R5): Wie wird vor dem Merge belegt, dass Staging und Produktiv ECDSA prüfen können (Statuszeile, Prüfpunkt, Stufe 2)? |
| 10 | **Prüfung:** Wie belegt die Abnahme, dass ein Datenbankabzug **nicht** genügt? Mindestens: echte Signatur → Rückweg; gefälschte, wiederholte, fremde, abgelaufene, zweckfremde Signatur → abgewiesen; mit allen Werten aus der Datenbank, aber ohne Wiederherstellungsschlüssel → abgewiesen. |

## 8. Was das Konzept liefern soll

- **Ablage:** `docs/konzepte/Konzept-RW-Zweitfaktor-Rueckweg.md`, Mockups
  gegebenenfalls in `docs/konzepte/konzept-rw/`. **Kürzel RW**: `E-RW-NN`,
  `F-RW-NN`, `Q-RW-NN`, `P-RW-NN`, Pakete `RW-NN` (`CLAUDE.md` 7).
- **Zweig:** von `claude/p5c-mockups-konzept-4yeomf` abzweigen und **nur neue
  Dateien** unter `docs/konzepte/` anlegen — das P5c-Konzept, Rahmenplan,
  Backlog und Code nicht anfassen. Was dort nachzutragen ist, steht als
  **Einschub** im RW-Konzept (Text für den Statusblock von P5c, die Änderung an
  E-P5c-42, Zeilen für Rahmenplan 6 und Backlog); eingespielt wird er von der
  Umsetzung auf dem P5c-Zweig. **Backlog-Nummern vergibt die Umsetzung.**
- **Inhalt:** Statusblock; Entscheidungen samt beantworteter Fragen;
  Arbeitspakete mit Inhalt, Migration, Stufe und **Abnahme mit Zahlen**, je
  Paket eine Zeile **Modellwahl** und eine Zeile **Fächerung** (`CLAUDE.md` 7);
  ein Prüfplan (Probe, Gegenproben, Prüfpunkte der Betreiberin); Grenzen.
  **Keine Versionsnummer** — die setzt die Umsetzung.

## 9. Wo nachlesen

- `docs/konzepte/Konzept-P5c-Rollen-Sicherheit-Betriebslage.md` —
  E-P5c-15, -41 bis -44, -53, -54, -61 (Zweitfaktor), AP5, Q-P5c-38,
  E-P5c-104, F-P5c-106.
- `docs/Technik.md` 4 (Einleitung: „Ende-zu-Ende-Verschlüsselung" und „Der
  Server-Anteil"), 4.97c (Serverschlüssel), 4.98 (Katalog der
  verschlüsselten Felder), 7 (Runbook).
- `server/pw_handling.php`, `server/assets/crypto.js`,
  `server/api/schluessel_erneuern.php`, `server/einstellungen.php`
  (Passwortwechsel, Freigabe), `server/adminbackup_lib.php`.
- `CLAUDE.md` 4 (Zusagen) und 5 (Oberfläche, Mockup-Pflicht).
