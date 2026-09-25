# Konzept RW — der Rückweg beim Zweitfaktor über den Wiederherstellungsschlüssel

**Kürzel:** `RW`. Einschubkonzept zu P5c (Q-P5c-38, E-P5c-104); Arbeitspakete
`RW-01 … RW-04` (Commit-Nachrichten beginnen mit `RW-0n: …`; im P5c-Konzept
heißt das Ganze **AP5b**), Mockup-Runde `M-RW-01`, Entscheidungen `E-RW-NN`,
Befunde `F-RW-NN`, Fragen an die BetreiberIn `Q-RW-NN`, Prüfpunkte `P-RW-NN`
(im Prüfdokument, entsteht mit RW-04). Benennung nach `docs/Pruefablauf.md` 7.
**Rahmenplan:** Schritt 10c (R82), R38, R78; Zuarbeit „Einschubkonzept RW"
in Abschnitt 6.
**Backlog:** 141 (Zweitfaktor), 249 (teilweise, E-RW-08); nicht 242.
**Vorbereitung:** `Vorbereitung-RW-Zweitfaktor-Rueckweg.md` (24.09.2026),
gegen `42c644c` (Web 20.41.0, nach P5c/AP4) geprüft — Befunde in 1.
**Modell:** Konzept Fable (dieser Stand), Umsetzung **Opus** (K2). **Kein
Fable-Schritt** in der Umsetzung.
**Fächerung (`CLAUDE.md` 7):** je Paket in 4.1 — **nichts** wird gefächert.
**Ablage:** dieses Dokument; Prüfdokument daneben
(`Pruefdokument-RW-Zweitfaktor-Rueckweg.md`, entsteht mit RW-04); Mockups in
`konzept-rw/`.
**Keine Versionsnummer im Konzept** (K3). „Nebenstufe" ist ein Vorschlag nach
`CLAUDE.md` 2; die Nummer setzt die Umsetzung. Die Migration macht die
Prüfstand-Stufe zu `haupt` (Stufenregel `migration`, E-P5c-88).
**Wo es hin soll:** auf den P5c-Zweig, als eigenes Paket **nach AP5 und vor
AP11** (E-P5c-104). Die Namen der Zweitfaktor-Spalten und der Funktion, die
den Zweitfaktor zurücksetzt, **legt AP5 fest**; RW liest sie nach dem Push von
AP5 nach (3.3) und baut nichts davon ein zweites Mal.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **24.09.2026 — Fassung 1, freigegeben.** Vorbereitung gegen den Code geprüft (F-RW-01 bis -08, mit einer Messung des Signaturwegs in drei Browser-Motoren und vier PHP-Prüfwegen: 12 Prüfungen, 0 Abweichungen). Zehn Fragen vorgelegt und am 24.09.2026 einzeln beantwortet (Q-RW-01 bis -10, 2.1); eine Antwort weicht von der Empfehlung ab (Q-RW-07). **Mockup-Runde M-RW-01 gebaut und freigegeben am 24.09.2026** (7). |
> | Entschieden | **E-RW-01 bis -14** (2.2). Aus der Umsetzung: **Q-RW-11** (Demo-Konto, 24.09.2026) → **E-RW-15**, ersetzt E-RW-10; **E-RW-16** (Marke des Selbsttests), **E-RW-17** (`RW_STAND` aus `ui_krypto_bootstrap()`) und **E-RW-18** (die fertige Nachricht), alle ohne Frage. Keine offene Frage. |
> | Offen | Nur, was die Anlage zeigen muss: **P-RW-01 bis -05** (Prüfdokument RW, 2) — die Statuszeile auf Produktiv, der erste Stufe-2-Lauf mit der Rückwegprobe (braucht `STAGING_TOTP`), ein echter Rückweg auf Staging, einmal mit falschem Zettel, das Demo-Konto nach dem Reset. Nach dem Merge des 10c-PR `update.php` (die Migration aus RW-01), die Wartung bleibt bis dahin an. |
> | Umsetzung | **gebaut am 24.09.2026** auf `claude/p5c-mockups-konzept-4yeomf` (Konzept per Cherry-Pick `5fb1d2a`). **RW-01 erledigt** (Web 20.43.0, `aecfaee`, mit Migration — nach dem Deploy `update.php`, die Wartung bleibt an; Befunde F-RW-09 bis -13). **RW-02 erledigt** (Web 20.44.0, `f82e277`, ohne Migration; Befunde F-RW-14 bis -16). **RW-03 erledigt** (Web 20.45.0, `123da2f`, ohne Migration; Befunde F-RW-17 bis -21). Danach hat der Zweig `main` aufgenommen (Konzept BR, `3576a97`; Konzept P5c E-P5c-115): Die Rückwegprobe hat seither eine Einstiegsdatei `probe.sh`, ihr Anlass ist Nr. 319. **RW-04 erledigt** (ohne Versionsstufe; Befunde F-RW-22, -23; Einschübe in P5c-Konzept, Rahmenplan 117, Backlog Nr. 301, 249, 141). **AP5b ist damit gebaut**; es geht mit dem 10c-PR (E-P5c-104). Offen sind nur die Prüfpunkte der Betreiberin (Prüfdokument RW, 2). |
> | Fable-Schritte | **keine.** |
> | Nummern | Backlog **301** (die Zeile aus 9.3, nachgesehen auf `origin/main` `ba2ec57`, höchste 318; aus der Spanne des P5c-Zweigs) und **319** (Anlass der Rückwegprobe, E-P5c-116); Nr. 249 und 141 berichtigt. Rahmenplan **Fassung 117** (Abschnitt 6 drei Zeilen, Verlaufszeile). |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Versionsstufe (Vorschlag) | Prüfstand | Migration | Commit | Abnahmezahlen |
> |---|---|---|---|---|---|---|
> | M-RW-01 Mockup-Runde | **erledigt — freigegeben 24.09.2026** | — (nur `docs/`) | — | — | | Überlauf 0 px Seite, 0 von 11 und 0 von 4 Rahmen; Knöpfe 16 von 16 zu 36 px (Zeigergerät) und 6 von 6 ≥ 44 px (Handy); 0 fehlende Ressourcen, 0 Konsolenfehler; keine neue Klasse, kein neues Symbol, keine neue Farbe |
> | RW-01 Grundlage: Prüfung, Migration, Statuszeile | **erledigt 24.09.2026** | Neben — **Web 20.43.0** | **haupt** (Bericht im Commit `RW-01`; Stufenregel `migration`) | **ja** (`2026_09_24_rueckweg_schluesselpaar`, drei Spalten an `users`) | `aecfaee` | Rückwegprobe Teil A **27 / 0**: Selbsttest **2 / 2** (openssl rund 20 ms, reines PHP **185 ms**, im Sollbereich 150–400), sechs Signaturfälle **1 angenommen / 5 abgewiesen**, fremde Kurve und fremdes Verfahren **2 / 2** abgewiesen (dazu die Kurvenprüfung selbst 2 / 2, F-RW-12), Statuszeile **3 / 3** Lagen, Marke: zweimal gefragt **1** Lauf · Gegenprobe Kurvenprüfung heraus: **3 rot** · vor `update.php`: `betrieb_updates.php` 200 mit Migration, Status 200, Wartung an · **unter PHP 8.3.33 von Hand**: Migration über Betrieb → Updates, Probe **27 / 0** (reines PHP 168 ms) · Quelltext **9 von 9**, Register **40 / 0 über der Decke** · Schemaprobe, Migrationsregister: im Prüfstand |
> | RW-02 Das Paar entsteht: Anmeldung, Karte, Demo | **erledigt 24.09.2026** | Neben — **Web 20.44.0** | **haupt** (Bericht im Commit `RW-02`) | nein | `f82e277` | Bedienweg **1 / 1**: Paar **0 → 1** beim Anmelden (Protokoll +1, Mails +0), zweite Anmeldung unverändert; ohne Token **403**, falsches Token **403**, vorhanden **409**, P-384 **400**, jeweils nichts geschrieben; Fehlversuch im Topf `login` gezählt, gestellte Sperre **429** (F-RW-16); Karte „eingerichtet" mit Knopf; Erneuern mit Passwort: neuer Wert, Protokoll +1, Mails +1; Demo mit gültigem Token **403 / 403**, 0 Paare · `RW_STAND` **4 / 4** (drei im Bedienweg, `'spalten'` in der Rückwegprobe gegen eine Datenbank ohne die Spalten) · Gegenproben: vor F-RW-14 **0 → 0**, ohne Demo-Sperre **200 / 200** — beide rot · Rückwegprobe **33 / 0** (Kontopaket: **0** Zeichenketten `rw_`; Gegenprobe 1 rot) · Zweitfaktorprobe **45 / 0** (Demo-Reset **3 / 3** NULL; Gegenprobe 0 / 3 rot) · Rollenprobe **296 / 0** (Endpunkt 4 × durch, 4 / 4 legen mit Token ab) · Karte und Dialog 1440 / 376 px: Überlauf 0, Knöpfe 36 / 44 px · Quelltext **9 von 9**, Register **40 / 0** · **nicht gemessen:** der Kreislauf `edbak` in ein Zielkonto mit Paar (Prüfdokument 0) |
> | RW-03 Der Rückweg am Code-Schritt | **erledigt 24.09.2026** | Neben — **Web 20.45.0** | **haupt** (Bericht im Commit `RW-03`) | nein | `123da2f` | **`probe.mjs` 23 / 0** (örtlich; der echte Weg im Browser): NutzerIn — Paar entsteht, Zweitfaktor über die Profilkarte, Code-Schritt mit Verweis, Tippfehler benannt, fremder Zettel **0 Anfragen**, Erfolgskarte, Startseite 200, Suche mit Datenschlüssel, Profil „Einrichten", Codes 0, Protokoll +1, Mail +1; BetreiberIn — Tor, Paar, Rückweg → **302** auf `zweitfaktor.php` mit Meldung · **`probe.php` 49 / 0** (Teil B 16): echt → angenommen, die Bibliothek schaltet nichts ab; wiederholt abgewiesen; abgelaufen abgewiesen, nicht gezählt, neue Herausforderung; fremd/verändert/zweckfremd gezählt, **Sperre nach 5**, danach auch echt abgewiesen; `pat_key_check` statt Signatur **4 / 4** abgewiesen; nicht angeboten **2 / 2**; **Abzug 73 Versuche, 0 Erfolge** (Öffner-Gegenprobe 1); HTTP: Verweis 1 / 0, POST ohne Paar **400**, echte Signatur → Zweitfaktor aus, Protokoll +1, Mail +1, **gesperrtes Konto → Tor, Zweitfaktor bleibt an** · Gegenproben rot: alte Fassung (B5), nicht verbrauchte Herausforderung (B1, B2), Abschalten vor dem Tor (B8) · Wartungsprobe **67 / 0** · Wache 43 / 0, gegen die Sandbox kein Unterschied · Kettenaufrufe **0 ungeprüft** · Stufe 2: **erst nach dem Merge** (P-RW-02) |
> | RW-04 Abschluss: Prüfdokument, Einschübe, Kette | **erledigt 24.09.2026** | — (nur `docs/` und `tools/`) | **haupt** (Bericht im Commit `RW-04`) | — | | **Nr. 300 von Hand:** `probe.mjs --motor` in **Firefox 23 / 0** und **WebKit 23 / 0** (nach F-RW-22; WebKit gegen `php -S` mit vier Arbeitern **3 von 3**, mit einem 2 von 4 — F-RW-23), Chromium 23 / 0 · **unter PHP 8.3.33:** Rückweg `probe.php` **49 / 0** (reines PHP 168 ms, openssl 12 ms) und `probe.mjs` **23 / 0**, Wartung **67 / 0**, Zweitfaktor **45 / 0**, Rollen **296 / 0**, Behälter **0** Zeilen Fatal/Warning/Deprecated · Prüfstand nach dem Aufnehmen von BR: Schemaprobe **4 × 19 / 0**, Bilderlauf **544 Bilder, 0 mit Code- oder Schlüsselschritt**, Bedienprobe **55 / 55**, Kontraste **25 / 0**, Register **40 / 0**, Bestand **0 Befunde** · Einschübe: P5c-Statusblock (AP5b erledigt mit Commits), E-P5c-42 dritter Punkt, 3.0, 3.2, AP11, Q-P5c-38; Rahmenplan 6 (drei Zeilen) und 10 (117); Backlog 301 neu, 249 und 141 berichtigt · Soll gegen Ist: Prüfdokument RW, 4 |

---

## 0. Auftrag und Umfang

Eine NutzerIn, die ihr Gerät für den Zweitfaktor **und** ihre zehn
Wiederherstellungscodes verloren hat, soll den Zweitfaktor selbst
zurücksetzen können, indem sie beweist, dass sie ihren
**Wiederherstellungsschlüssel** (die 20 Zeichen vom Notfallblatt) kennt — so,
dass **niemand diesen Beweis aus einer Kopie der Datenbank fälschen kann**.
Der Server darf dabei weiterhin nichts entschlüsseln können (`CLAUDE.md` 4).

Der Anlass ist F-P5c-106: Der in E-P5c-42 beschlossene Weg prüfte gegen
`pat_key_check`, einen Wert, der in der Datenbank steht. Die Betreiberin hat
Alternative C gewählt (Q-P5c-38): ein Schlüsselpaar je Konto, dessen privater
Teil mit dem Inhaltsschlüssel verpackt ist, und eine Signatur über eine
Herausforderung des Servers.

**Im Umfang:** das Schlüsselpaar (Entstehung, Ablage, Erneuern), der Rückweg
am Code-Schritt der Anmeldung, die Prüfung auf dem Server, die Statuszeile,
die Kartenzeile im Profil, das Demo-Konto (ohne Paar, E-RW-15), die Rückwegprobe,
Handbuch, Technik, Runbook, und die Einschübe in P5c, Rahmenplan und Backlog.

**Nicht im Umfang:** der Zweitfaktor selbst (AP5), das Zurücksetzen durch die
Verwaltung (AP5, E-P5c-42), „Gerät merken" (Nr. 242, Schritt 18), ein Wechsel
des Inhaltsschlüssels (gibt es nicht, R1), die Passwort-Reset-Seite
`pw_handling.php` (bleibt unverändert, E-RW-01).

---

## 1. Befunde — die Vorbereitung gegen den Code (F-RW-01 bis -08)

Geprüft am 24.09.2026 gegen `42c644c`. Die Randbedingungen R1 bis R9 der
Vorbereitung stimmen; zwei Aussagen sind überholt, zwei Punkte fehlten.

| Nr. | Befund | Folge |
|---|---|---|
| **F-RW-01** | **Die Hosterfrage ist keine Voraussetzung mehr.** Unter `server/vendor/phpseclib3/` liegt phpseclib 3.0.57 (vendoriert für den SFTP-Adapter, `Lizenzen.md` 3a). Sie prüft ECDSA P-256 im **IEEE-Format** — genau das `r‖s`, das WebCrypto liefert — und Ed25519 **in reinem PHP**; `openssl` und `sodium` sind nur Beschleuniger (`EC::forceEngine()`). Die DER-Umwandlung aus R5 entfällt. | E-RW-03, -04; Q-RW-09 wird eine Belegfrage |
| **F-RW-02** | **Gemessen, nicht angenommen:** Chromium 141, Firefox 142 und WebKit 26 erzeugen hier über WebCrypto beide Paare, exportieren SPKI und PKCS8 und signieren (je 64 Byte). PHP 8.4.19 prüft die Browser-Signaturen mit `openssl_verify` (nach DER), mit `sodium`, mit phpseclib **intern** (ECDSA 214 ms, Ed25519 334 ms) und mit phpseclib bester Engine (1,5 ms) — **12 Prüfungen, 0 Abweichungen** (drei Gegenproben je Weg: veränderte Nachricht, fremder Schlüssel). Nebenbefund: **Firefox und WebKit starten in diesem Container**, anders als `Sandbox-Setup.md` 1 sagt. | E-RW-03; Backlog-Zeile 9.3 |
| **F-RW-03** | **`pw_handling.php` allein trägt nicht** (Skizze 4 der Vorbereitung). Wer Gerät und Codes verliert, aber sein Passwort kennt, müsste dort das Passwort zurücksetzen; wer alles verliert, steht nach dem Setz-Link erneut vor dem Code-Schritt. Der Code-Schritt ist notwendig; ein Schalter in `pw_handling.php` wäre eine zweite Stelle für dieselbe Sache. | E-RW-01 |
| **F-RW-04** | **Wer den öffentlichen Teil schreiben darf, besitzt den Rückweg.** Ein Schreibweg, der nur die Sitzung verlangt, ließe jemanden an einer offenen Sitzung ein eigenes Paar hinterlegen. Das Schreiben verlangt deshalb den **Passwortnachweis** (Token, Muster `api/schluessel_erneuern.php` und `api/kdf_upgrade.php`), ersetzt nie still, schreibt Protokoll und beim Erneuern eine Mail. Das Einschalten des Zweitfaktors (AP5, E-P5c-54) fragt kein Passwort ab — dort entsteht deshalb **kein** Paar. | E-RW-02, -06 |
| **F-RW-05** | **Die Herausforderung braucht keine Tabelle.** Die halbe Anmeldesitzung nach E-P5c-53 hält Konto und Frist (fünf Minuten); sie trägt die Herausforderung, einmal gültig, an Konto und Zweck gebunden. Keine Migration dafür, kein Aufräumjob. | E-RW-04 |
| **F-RW-06** | **`PAT_KEY_CHECK` steht im Seitenquelltext** von `einstellungen.php` (Freigabe-Vergleich). Der Prüfwert ist ein Verifizierer, den jede angemeldete Sitzung sieht — F-P5c-106 ein zweites Mal. Beim Passwort-Reset bleibt er, was er ist: Schutz vor einem Versehen. | 5.3 (Gegenprobe zur alten Fassung) |
| **F-RW-07** | Zwei Aussagen der Vorbereitung sind überholt: „Ed25519 jünger" (R5) — in allen drei Motoren vorhanden (F-RW-02); „nur Rolle user" (Abschnitt 1, R9) — stammt aus der Zeit des schwachen Nachweises. | E-RW-03, -08 |
| **F-RW-08** | **Die eine Stelle für das stille Entstehen ist der Vormerkfach-Weg** in `unlock.js` (`loeseVormerkung()`): Nur dort hat der Browser den Inhaltsschlüssel **und** das Anmelde-Token zugleich in der Hand — dieselbe Lage, in der `api/kdf_upgrade.php` die Hülle still umstellt. Der Anhebelauf nach dem Muster `pat_anheben.php` (Vorbereitung 6, Punkt 2) braucht damit keinen eigenen Endpunkt für den Altbestand: Jedes Konto ist beim nächsten Anmelden dran. | E-RW-02 |

Die Zahlen der Vorbereitung (Abschnitt 3: 0 Konten ohne Prüfwert auf beiden
Anlagen) sind für RW ohne Belang — der Prüfwert spielt im Rückweg keine Rolle
mehr.

### 1.1 Aus der Umsetzung (F-RW-09 ff.)

| Nr. | Befund | Folge |
|---|---|---|
| **F-RW-09** | *(vor RW-02)* **E-RW-10 und die Abnahme von RW-02 widersprachen sich.** E-RW-10 lässt das Paar des Demo-Kontos auf dem gewöhnlichen Weg beim Anmelden entstehen, damit der Fixture-Erzeuger es auf der Referenzanlage findet; die Abnahme nennt einen vierten Wert `RW_STAND = 'demo'`. Nach dem Muster von `ANTEIL_STAND = 'demo'` hieße er „nie anlegen" — dann käme nie ein Paar in die Fixture | vorgelegt als **Q-RW-11**; die Betreiberin hat das Demo-Konto ausgenommen — **E-RW-15** ersetzt E-RW-10 |
| **F-RW-10** | *(RW-01)* **Eine Prüfung über openssl kostet rund 20 ms, nicht 1,5 bis 2.** F-RW-02 hat die Prüfung mit einem schon geladenen Schlüssel gemessen, E-RW-11 nennt als Beispiel „openssl, 2 ms". Gemessen am 24.09.2026: Laden des SPKI durch phpseclib **18,8 ms** (ASN.1 und Punktprüfung in reinem PHP), `verify()` über openssl **0,97 ms**. Jede echte Prüfung lädt den Schlüssel einmal | kein Fehler; die Statuszeile nennt die ehrliche Zahl je Prüfung (rund 20 ms). Der Selbsttest läuft einmal ungemessen vorab, damit das erstmalige Laden der Klassen (rund 15 ms) nicht mitzählt |
| **F-RW-11** | *(RW-01, aus AP5)* **`Lizenzen.md` 3a sagte, phpseclib werde „nur vom SFTP-Adapter" geladen**, und führte constant_time_encoding nur als Voraussetzung von phpseclib. Seit Web 20.42.0 lädt `totp_lib.php` den Lader und nutzt Base32 daraus; AP5 hatte es nicht nachgetragen. Ebenso nannte `Technik.md` 3 „zweiundzwanzig" Proben ohne die Zweitfaktorprobe | berichtigt mit RW-01: drei Verwender des Laders, 24 Proben (gezählt mit `proben.sh --liste`) |
| **F-RW-12** | *(RW-01, Prüfmittel)* **Ein Ed25519-SPKI fällt schon an der Formregel**, nicht an der Kurvenprüfung: 60 statt 124 Zeichen, `RW_OEFFENTLICH_RE` verlangt 100 bis 200. Die Probe hätte damit die Kurvenprüfung für Ed25519 nie erreicht | die Probe ruft `rw_oeffentlich_laden()` zusätzlich ohne die Regel; Gegenprobe (Kurvenprüfung heraus) → 3 rot |
| **F-RW-13** | *(RW-01, aus P5c/AP5)* Die Bemerkung zur Zweitfaktorprobe in `pruefablauf.json` sagte noch „setzt den Demo-Bestand einmal zurück" — seit F-P5c-117 falsch | berichtigt |
| **F-RW-14** | *(RW-02)* **Auf der Startseite eines Kontos ohne Diensttag entstand kein Paar.** E-RW-02 setzt auf den Vormerkfach-Weg in `unlock.js`; das Fach löst aber erst eine Seite auf, die den Inhaltsschlüssel BRAUCHT, und `index.php` braucht ihn nur, wenn es einen Tag zu zeigen gibt. Gemessen mit dem ersten Lauf des Bedienwegs: Anmeldung → `index.php` → Fach belegt, Paar **0 → 0**; erst `suche.php` legte es an. Gerade die Konten der BetreiberInnen haben oft keine Einsätze — für sie ist der Rückweg gedacht (E-RW-08) | `unlock.js` löst das Fach bei `RW_STAND === 'fehlt'` nach dem Laden selbst auf, still und ohne Dialog, über denselben einen Lauf wie `ensureContentKey()` (sonst liefe die stille KDF-Anhebung zweimal mit demselben alten Token). Danach **0 → 1** auf `index.php`. Nebenwirkung, gewollt: Die KDF-Anhebung läuft für solche Konten ebenfalls gleich nach der Anmeldung |
| **F-RW-15** | *(RW-02, aus AP5)* **`tools/proben/LIESMICH.md` nannte „Zweiundzwanzig" Proben**, es sind 24. F-RW-11 hat `Technik.md` 3 berichtigt und die LIESMICH übersehen; bemerkt während des RW-01-Prüfstands, als der Baum nicht mehr angefasst werden durfte | berichtigt mit RW-02 |
| **F-RW-16** | *(RW-02, Prüfmittel)* **„10 falsche Token → Sperre" lässt sich am Endpunkt nicht erklopfen**, ohne die Sandbox auszusperren: `rate_misserfolg('login', $email)` zählt immer auch die ADRESSE (`rate_merkmale()`), und zehn Fehlversuche sperrten jede Anmeldung von 127.0.0.1 für 15 Minuten — den Rest des Prüfstands eingeschlossen | der Bedienweg misst, dass ein Fehlversuch unter dem Kontomerkmal zählt, setzt die Sperre dann von Hand und erwartet 429; die Zeilen des Topfs `login` stellt er danach wieder her. Die Leiter selbst misst die Ratenprobe an der Bibliothek |
| **F-RW-17** | *(RW-03, Konstruktion)* **Skripte, die nur im Schlüsselschritt stünden, machten die Integritätswache rot.** Sie vergleicht `login.php` ohne Sitzung gegen die Quelle: Jeder PHP-freie Inline-Block der Quelle muss in der Auslieferung stehen, jedes `src`-Skript auch. 3.2 sah `crypto.js`, `rueckweg.js` und Konstanten nur für den Schlüsselschritt vor | `crypto.js`, `rueckweg.js` und ein PHP-freies Seitenskript stehen auf `login.php` immer da (Muster des Vormerkfach-Blocks aus AP5); die Werte stehen in unbenannten versteckten Feldern des bedingten Formulars `#schluesselform`, das in `BEDINGTE_FORMULARE` steht. Wache gegen die Sandbox: 4 Blöcke, 4 Skripte gleich, kein Unterschied |
| **F-RW-18** | *(RW-03)* **Ein POST mit gültiger Signatur an ein Konto ohne Paar hieß „abgelaufen" (200)** statt 400: `rw_rueckweg_pruefen()` (damals `rw_rueckweg_einloesen()`, F-RW-21) fragte zuerst nach der Herausforderung — und die hat ein Konto, dem der Schritt nie angeboten wurde, nie. Gefunden mit der Rückwegprobe B8 | Reihenfolge getauscht: erst „wird er angeboten?", dann die Frist |
| **F-RW-19** | *(RW-03)* **Die Erfolgskarte endete im ersten Handlauf in „Unerwarteter Fehler"**: Die Mail las `$u['email']`, die `login_zeile()` nicht liefert, und die Meldung eine Konstante `MAIL_FEHLER`, die es nicht gibt (die Zustände heißen `zugestellt`, `wartet`, `abgelehnt`). Gefunden über die Kennung im Reiter System | Adresse aus dem halben Stand; „unterwegs" bei `zugestellt` oder `wartet`, sonst „ließ sich nicht verschicken" |
| **F-RW-20** | *(RW-03, Prüfmittel)* **„Vormerkfach leer" nach der Startseite gilt nicht für ein Konto ohne Diensttag.** Die Startseite braucht dort den Schlüssel nicht und lässt das Fach liegen, wie nach jeder Anmeldung; F-RW-14 löst es nur auf, wenn ein Paar FEHLT | `probe.mjs` misst an der Seite, die den Schlüssel braucht: `suche.php` löst das Fach auf, und der Datenschlüssel ist da — eine vollständige Anmeldung ohne Entsperrdialog |
| **F-RW-21** | *(RW-03)* **Der Zweitfaktor wurde VOR dem Tor abgeschaltet.** `rw_rueckweg_einloesen()` zählte den Erfolg und rief `totp_abschalten()` selbst; `login.php` fragte erst danach das Tor `login_zugang()` (Kontostatus, Wartung). Ein gesperrtes Konto — oder jedes ohne Verwaltungsrecht während der Wartung — verlor so seinen Zweitfaktor ohne Anmeldung und **ohne Mail**, die erst hinter dem Tor kommt; während der Wartung schrieb es zudem in `users`. Gefunden hat es der erste Prüfstand des Pakets: Die Wartungsprobe (Fall 18, E-S5W-09 b) sucht vor jedem Aufruf des Tors das `rate_erfolg` seines Schritts und fand „Aufruf Z. 478 hinter nichts" | Die Funktion heißt jetzt `rw_rueckweg_pruefen()` und prüft nur — Fehlversuche zählt sie weiter. Das Ja vollzieht `login.php` in der Reihenfolge des Code-Schritts: `rate_erfolg`, Tor, Sitzung, **dann** `totp_abschalten()` (mit dem Konto als Urheber) und die Mail. Rückwegprobe B8 misst es über HTTP an einem gesperrten Konto: Zweitfaktor an, Protokoll 0, Mail 0; mit der alten Reihenfolge rot (Protokoll 1, Mail 0). Konzept 3.2 nannte die Reihenfolge von Tor und Abschalten nicht |
| **F-RW-22** | *(RW-04, Prüfmittel)* **In Firefox und WebKit entstand in `probe.mjs` nie ein Paar** — die Anwendung legte es an, die Probe verhinderte es. `paarAbwarten()` lud die Startseite neu, sobald `networkidle` kam, und das kommt mitten in der Schlüsselableitung, die ohne Netzverkehr läuft. In Chromium war die Ableitung schneller als das Fenster; in den beiden anderen Motoren brach jeder Neuladevorgang sie ab. Gegenprobe von Hand: dasselbe Konto in Firefox, fünf Sekunden gewartet — `api/rueckweg_anlegen.php` 200, `RW_STAND` „da" | Die Probe wartet erst auf die Antwort des Endpunkts (erwartet vor der Navigation, die das Entsperren auslöst), dann lädt sie neu. Danach Firefox **23 / 0**, Chromium **23 / 0** |
| **F-RW-23** | *(RW-04, Arbeitsumgebung)* **WebKit blieb beim zweiten Anmelden in 2 von 4 Läufen 90 s ohne Navigation** — immer an derselben Stelle, nach „Zweitfaktor eingeschaltet". Der örtliche `php -S` bedient mit einem Arbeiter eine Anfrage nach der anderen; eine Verbindung, die WebKit offen hält, hält ihn auf | Gegenprobe: `PHP_CLI_SERVER_WORKERS=4` → **3 von 3** grün (23 / 0). Kein Fehler der Anwendung; als Weg an Backlog Nr. 301 angehängt (Server mit mehreren Arbeitern, bevor WebKit in eine Stufe kommt) |

---

## 2. Entscheidungen

### 2.1 Die zehn Fragen der Vorbereitung (Q-RW-01 bis -10, 24.09.2026)

Einzeln vorgelegt, mit Empfehlung; die Antworten in Fettdruck.

| Nr. | Frage | Empfehlung | Entscheidung |
|---|---|---|---|
| Q-RW-01 | Ort des Rückwegs | nur der Code-Schritt (F-RW-03) | **nur beim Anmelden, am Code-Schritt** — E-RW-01 |
| Q-RW-02 | Wann entsteht das Paar | still nach der Anmeldung, für jedes Konto mit Inhaltsschlüssel (F-RW-04, -08) | **still nach der Anmeldung, für alle Konten** — E-RW-02 |
| Q-RW-03 | Verfahren | ECDSA P-256 über phpseclib (IEEE) | **ECDSA P-256** — E-RW-03 |
| Q-RW-04 | Ersetzen und Entfernen | Ersetzen nur ausdrücklich mit Passwort, Protokoll, Mail; beim Ausschalten nicht löschen | **so** — E-RW-06 |
| Q-RW-05 | Rollen | alle vier, auch BetreiberIn (Nr. 249 teilweise) | **alle vier Rollen** — E-RW-08 |
| Q-RW-06 | Backups | nicht ins Konto-Backup, nicht in die Freigabe; Komplett-Backup trägt es von selbst | **so** — E-RW-09 |
| Q-RW-07 | Demo-Konto | kein Paar; Reset leert die Spalten | **abweichend: ein festes Paar aus der Fixture** — E-RW-10; **in der Umsetzung neu gefasst durch Q-RW-11** |
| Q-RW-08 | Oberfläche | Mockup M-RW-01, fünf Bilder plus Handybreite | **ja** — 7, E-RW-13 |
| Q-RW-09 | Beleg auf den Anlagen | Statuszeile mit Selbsttest, Stufe 2 gegen Staging, Prüfpunkt Produktiv | **so** — E-RW-11 |
| Q-RW-10 | Beleg „Abzug genügt nicht" | Rückwegprobe in zwei Teilen, Abzug-Gegenprobe, Gegenprobe zur alten Fassung | **so** — E-RW-12 |
| Q-RW-11 | *(Umsetzung, vor RW-02, 24.09.2026)* E-RW-10 lässt das Paar des Demo-Kontos auf dem gewöhnlichen Weg entstehen; die Abnahme von RW-02 nennt einen Wert `RW_STAND = 'demo'`. Hieße er „nie anlegen", käme nie ein Paar in die Fixture. Festes Paar ohne Anlegen, Demo ganz ausnehmen, oder wie E-RW-10? | Demo ausnehmen — der Zweitfaktor ist dort gesperrt, das Paar hätte keinen Verbraucher | **Demo ausnehmen** (Gegenfrage der Betreiberin: „wäre es unkomplizierter, das Demo-Konto vom Paar auszunehmen?") — E-RW-15 |

### 2.2 Die Entscheidungen (E-RW-01 bis -14)

**E-RW-01 — Der Rückweg steht am Code-Schritt der Anmeldung, und nur dort**
(Q-RW-01, F-RW-03). Nach dem Passwort, vor der Sitzung (E-P5c-53), bekommt
der Code-Schritt neben „Wiederherstellungscode verwenden" einen dritten Weg:
„Gerät und Codes verloren? Wiederherstellungsschlüssel verwenden" — auch aus
dem Schritt „Wiederherstellungscode" heraus, weil dort steht, wer die Codes
nicht mehr hat. Passwort und Wiederherstellungsschlüssel sind zwei Faktoren.
`pw_handling.php` bleibt unverändert; der dritte Punkt von E-P5c-42 wird
durch diesen ersetzt (8.2). Der Weg wird **nur angeboten, wenn das Konto ein
Paar hat und der Server prüfen kann** (E-RW-11); sonst sagt der Schritt
„Wiederherstellungscode", dass ohne Codes die Verwaltung hilft (Alternative
B bleibt der Boden).

**E-RW-02 — Das Paar entsteht still nach der Anmeldung, für jedes Konto mit
Inhaltsschlüssel** (Q-RW-02, F-RW-04, -08). Die eine Stelle ist der
Vormerkfach-Weg in `unlock.js`: Hat das Konto kein Paar (`RW_STAND ===
'fehlt'`, ausgeliefert von `ui_krypto_bootstrap()`), erzeugt der Browser eines
über WebCrypto, verpackt den privaten Teil (PKCS8) mit dem Inhaltsschlüssel
(`EdCrypto.encrypt(ck, …)`, Kennung `edk1:`) und schickt beides mit dem
**Anmelde-Token** der aktuellen Rundenzahl an `api/rueckweg_anlegen.php` — der
Nachweis, den auch `kdf_upgrade.php` verlangt. Der Server prüft das Token
gegen `password_hash`, prüft den öffentlichen Teil auf eine gültige
P-256-Kurve, schreibt nur, wenn noch keins da ist, und schreibt einen
Protokolleintrag ohne Mail. Ein Fehlschlag bleibt still; der nächste
Entsperrvorgang versucht es erneut (wie `pat_anheben.php`). **Nicht** beim
Einschalten des Zweitfaktors: Dieser Schritt verlangt kein Passwort, und ein
Paar, das ohne Passwortnachweis entsteht, wäre der Riss in der Zusage
(F-RW-04). Wer den Zweitfaktor vor RW eingeschaltet hat, bekommt sein Paar
beim nächsten Anmelden; die Karte sagt bis dahin „ab der nächsten
Anmeldung" (E-RW-07).

**E-RW-03 — ECDSA P-256, geprüft mit phpseclib im IEEE-Format** (Q-RW-03,
F-RW-01, -02). WebCrypto `ECDSA`/`P-256`/`SHA-256`; öffentlicher Teil als
SPKI, privater als PKCS8, beide Base64. Der Server prüft über
`phpseclib3\Crypt\PublicKeyLoader::load()` → `withSignatureFormat('IEEE')`
→ `withHash('sha256')` → `verify()`; phpseclib nimmt `openssl`, wenn die
Kurve dort vorhanden ist, sonst reines PHP (gemessen 214 ms — zumutbar für
einen Weg, den ein Konto einmal im Jahr geht). Ed25519 wäre gleichwertig;
P-256 ist die etabliertere Wahl in WebCrypto und in reinem PHP die
schnellere. `openssl_verify` und `sodium` werden **nicht** unmittelbar
gerufen — zwei Prüfwege wären zwei Stellen.

**E-RW-04 — Die Herausforderung liegt in der halben Anmeldesitzung**
(F-RW-05). 32 Zufallsbytes (`random_bytes`), erzeugt beim Aufruf des
Schlüsselschritts, abgelegt in der Sitzung neben Konto und Frist aus
E-P5c-53, **gültig fünf Minuten, einmal** — nach jedem Prüfversuch verworfen,
ob erfolgreich oder nicht. Die signierte Nachricht ist
`nadoku-rw-v1|totp-rueckweg|<Kontonummer>|<Herausforderung als 64 Hexzeichen>`
(UTF-8): Präfix und Zweck trennen die Domäne, die Kontonummer bindet an das
Konto, die Herausforderung macht sie einmalig. Der Server baut die Nachricht
selbst aus Sitzung und Konto — **nie** aus dem, was der Browser schickt.

**E-RW-05 — Das Datenmodell: drei Spalten an `users`, keine Tabelle.**
`rw_oeffentlich VARCHAR(255) NULL` (SPKI, Base64, 124 Zeichen), `rw_privat
TEXT NULL` (`edk1:` + Base64 des AES-GCM-Chiffretexts über PKCS8, rund 220
Zeichen), `rw_seit DATETIME NULL`. Migration
`JJJJ_MM_TT_rueckweg_schluesselpaar` (Datum setzt die Umsetzung), kein
`zerstoert`, mit `skip` über `db_hat_spalte()`. **Rückfall-SELECT** in
`auth_guard.php` und `login.php`: Fehlen die Spalten, ist der Weg stumm
(`RW_STAND === 'fehlt'` wird dann **nicht** gemeldet, damit der Browser nicht
gegen fehlende Spalten anschreibt — ein eigener Wert `'spalten'`). Prüfregeln
in `validate_lib.php`: `RW_OEFFENTLICH_RE` (Base64, 100–200 Zeichen),
`RW_PRIVAT_RE` = `WRAP_RC_RE` (nur `edk1:`, nie `edka1:` — der private Teil
hängt am Inhaltsschlüssel, nicht am Anteil, und muss mit dem
Wiederherstellungsschlüssel allein aufgehen, wie `pat_wrap_rc`).

**E-RW-06 — Ersetzen nur ausdrücklich, Löschen nur mit dem Konto**
(Q-RW-04, F-RW-04). Der Knopf „Rückweg erneuern" in der Karte „Zweitfaktor"
fragt das Passwort ab (dieselbe Komponente wie „Neuen
Wiederherstellungsschlüssel erzeugen", `assets/schluessel.js` als Muster:
Passwort → Datenschlüssel → `pat_wrap_pw` öffnen → Inhaltsschlüssel gegen
`pat_key_check` halten → neues Paar → `api/rueckweg_anlegen.php` mit
`ersetzen: 1`). Der Server verlangt auch hier das Token, schreibt
`rueckweg_erneuert` ins Protokoll und schickt die Mail `rueckweg_erneuert`
an die Kontoadresse — wer den Rückweg an sich nähme, hinterließe eine Spur
im Postfach. Beim Ausschalten des Zweitfaktors bleibt das Paar liegen: Es
hängt am Inhaltsschlüssel, der nie wechselt (R1), nicht am Zweitfaktor.
Gelöscht wird es nur mit dem Konto (die Spalten hängen an der Zeile). Der
Anlegeweg ohne `ersetzen` weist ein vorhandenes Paar mit 409 ab.

**E-RW-07 — Die Kartenzeile** (Q-RW-08, M-RW-01). In der Karte „Zweitfaktor"
(Einstellungen → Profil, eingeschaltet) eine dritte `.zeile` „Rückweg mit
dem Wiederherstellungsschlüssel": Plakette blau „eingerichtet" mit Datum
(`rw_seit`), neutral „ab der nächsten Anmeldung", wenn das Paar fehlt; der
Knopf „Rückweg erneuern" nur bei vorhandenem Paar. Für die Pflichtrolle
ändert sich der Satz unter den Zeilen: „zurücksetzen kann eine andere
BetreiberIn oder du selbst mit dem Wiederherstellungsschlüssel". Bei
ausgeschaltetem Zweitfaktor zeigt die Karte die Zeile nicht — das Paar ist
dann ohne Verbraucher.

**E-RW-08 — Alle vier Rollen** (Q-RW-05, F-RW-07). Der Nachweis ist
fälschungssicher und für alle gleich stark: Passwort und Notfallblatt sind
derselbe Zuschnitt wie Passwort und Codeblatt, den E-P5c-42 ohnehin zulässt.
Der Weg über die Verwaltung bleibt unverändert (BetreiberIn für alle, Admin
für user, Support-Konten nur die BetreiberIn). **Nr. 249** ist damit für den
Fall gelöst, in dem die einzige BetreiberIn Passwort und Notfallblatt hat;
für den Rest bleibt der Runbook-Notweg. Das weicht vom „nur user" in
E-P5c-42 ab und steht so im Einschub (8.2).

**E-RW-09 — Backups** (Q-RW-06). Konto-Backup (`.edbak`) und Freigabe tragen
das Paar **nicht**: Beide dienen dem Einspielen in ein anderes Konto, dessen
Paar an seinem eigenen Inhaltsschlüssel hängt. Das Komplett-Backup trägt die
drei Spalten als Teil von `users` von selbst; `Backup-Format.md` bekommt
dafür einen Satz. `edbak_restore()` in dasselbe Konto lässt die Spalten
unberührt.

~~**E-RW-10 — Das Demo-Konto trägt ein festes Paar aus der Fixture** (Q-RW-07,
Antwort der Betreiberin gegen die Empfehlung; R7). Der Fixture-Erzeuger
(`tools/referenzdatensatz/fixture/erzeugen.php`) kopiert `rw_oeffentlich` und
`rw_privat` wie `pat_wrap_rc` aus der Datenbank der erzeugenden Anlage — er
kann kein Paar rechnen, er hat den Inhaltsschlüssel nicht; das Paar entsteht
dort einmal auf dem gewöhnlichen Weg (E-RW-02) beim Anmelden im Demo-Konto.
`demo_zuruecksetzen()` schreibt beide Spalten mit zurück, `demo_anlegen()`
ebenso; der Riegel in `demo_fixture_laden()` prüft `rw_privat` gegen
`RW_PRIVAT_RE` (nie `edka1:`), wie die Wiederherstellungs-Hülle. „Rückweg
erneuern" ist im Demo-Konto **gesperrt** (dieselbe Stelle, die Profil und
Passwort sperrt). Der Zweitfaktor ist im Demo ohnehin gesperrt (E-P5c-54);
das Paar hat dort keinen Verbraucher. **Preis:** Die Fixture muss nach RW
einmal neu erzeugt werden (Zuarbeit, 9.2); bis dahin entsteht auf jeder
Anlage beim ersten Demo-Anmelden ein Paar, das der nächste Reset auf leer
setzt — ohne Folgen, weil nichts es liest.~~
**Aufgehoben am 24.09.2026 durch E-RW-15** (Q-RW-11): Das Demo-Konto bekommt kein Paar.

**E-RW-11 — Drei Belege, dass die Anlage prüfen kann** (Q-RW-09, F-RW-01).
(1) `rw_selbsttest()` in `rueckweg_lib.php` prüft eine feste Beispielsignatur
(Vektor aus der Messung F-RW-02, als Konstanten in der Bibliothek) und nennt
den Weg (`openssl` oder reines PHP) und die Dauer; `status_erhebung()` zeigt
es in der Karte „Server" als Zeile **„Rückweg-Prüfung"**: blau „prüft
(openssl, 2 ms)" bzw. „prüft (reines PHP, 214 ms)", orange „Selbsttest
fehlgeschlagen — der Rückweg ist abgeschaltet". `rw_verfuegbar()` ist das
zwischengespeicherte Ergebnis (`app_state`-Marke mit Katalog-Hash, wie das
Migrationstor; kein 214-ms-Selbsttest je Seitenaufruf) und schaltet den
dritten Weg am Code-Schritt. (2) Stufe 2 fährt die Rückwegprobe gegen Staging
(E-RW-12; Aufruf in `tools/kettenaufrufe/`, rot bei Fehlschlag, kein
Überspringen — E-KH-12). (3) Prüfpunkt der Betreiberin auf Produktiv nach dem
Tag: die Statuszeile ansehen (P-RW-01).

**E-RW-12 — Die Rückwegprobe** (Q-RW-10; Anlass: F-P5c-106, Nr. 141).
`tools/proben/rueckweg/`, zwei Dateien unter einem Namen in `proben.sh`:
`probe.mjs` fährt den echten Browserweg gegen die Anlage (Muster
Freigabeprobe: alle Krypto aus `crypto.js` und `rueckweg.js`, kein Nachbau;
eigenes Wegwerfkonto über `pruefkonto.py`, E-RP-02, dessen
Wiederherstellungsschlüssel die Probe bei der Erstvergabe von der Seite
liest), `probe.php` misst den Server ohne HTTP (Muster Anteilprobe). Die
Fälle stehen in 5.3. Der Bericht zählt: angenommene und abgewiesene Fälle,
geprüfte Werte des Abzugs, 0 Erfolge.

**E-RW-13 — Die Form ist M-RW-01** (Q-RW-08, freigegeben 24.09.2026; 7).
Eingefügt ist nur das Neue; keine neue Klasse, kein neues Symbol, keine neue
Farbe. Die Klassen `.feld-code` und `.anmeldung-schritt` kommen mit AP5
(M-P5c-02b).

**E-RW-14 — Ratenschutz, Protokoll, Mail.** Ein Signaturversuch zählt im
Topf **`totp`** (AP5, mit Leiter): Der Browser sendet nur, wenn die Hülle
aufging — was auf dem Server scheitert, ist eine Fälschung oder ein
Programmfehler, und beides gehört gezählt. Der erfolgreiche Rückweg schreibt
`protokoll('verwaltung', 'totp_zurueckgesetzt', …, ['weg' => 'schluessel'])`
über **dieselbe Funktion, mit der die Verwaltung zurücksetzt** (AP5; der
`weg` unterscheidet die Fälle) und schickt die Mail `totp_zurueckgesetzt` aus
AP5 mit dem Satz, dass es der Wiederherstellungsschlüssel war. Neue
Protokollarten: `rueckweg_angelegt`, `rueckweg_erneuert` (Reiter Verwaltung,
neutral); neue Mail: `rueckweg_erneuert` (Art `konto`). Der Anlegeweg nach
der Anmeldung teilt den Topf `login` (wie `schluessel_erneuern.php`: ein
Passwortorakel bekommt keinen eigenen Topf).


**E-RW-15 — Das Demo-Konto bekommt kein Paar** (Q-RW-11, 24.09.2026;
ersetzt E-RW-10). Der Zweitfaktor ist im Demo gesperrt (E-P5c-54); ein Paar
hätte dort keinen Verbraucher, und das feste Paar aus E-RW-10 kostete ein
Anlegen im Demo auf der Referenzanlage, einen Riegel in `demo_fixture_laden()`
und eine neu erzeugte Fixture — Aufwand für etwas, das nie benutzt wird.
Deshalb: `RW_STAND === 'demo'` heißt **nicht anlegen** (wie `ANTEIL_STAND
'demo'`); `api/rueckweg_anlegen.php` weist das Demo-Konto ab (**403**, mit und
ohne `ersetzen`); der Demo-Reset leert die Spalten an derselben Stelle wie den
Zweitfaktor (`demo_zweitfaktor_leeren()`, F-P5c-117); die Karte zeigt die
Zeile im Demo nicht (dort steht ohnehin nur der Satz der Demo-Sperre). Die
Fixture und ihr Erzeuger bleiben unverändert, die Zuarbeit „Fixture neu
erzeugen" entfällt, P-RW-05 prüft nur noch, dass das Demo-Konto kein Paar
hat. Am Code-Schritt kommt das Demo-Konto nie an, weil es keinen Zweitfaktor
haben kann.

**E-RW-16 — Die Marke des Selbsttests gilt je Fassung und Plattform, nicht je
Katalog-Hash** (RW-01, ohne Frage; führt E-RW-11 aus). E-RW-11 nannte „eine
`app_state`-Marke mit Katalog-Hash, wie das Migrationstor". Der Katalog
ändert sich nur mit einer Migration — und genau das, was den Selbsttest
scheitern ließe, ändert ihn nicht: ein neues PHP oder OpenSSL beim Hoster,
ein Deploy mit neuem phpseclib. Die Marke ist deshalb
`sha256(WEB_VERSION | PHP_VERSION | OPENSSL_VERSION_TEXT)`; jeder Deploy mit
Code ändert `WEB_VERSION` ohnehin, auch jeder mit Migration. Die Absicht von
E-RW-11 bleibt: einmal je Stand, nicht je Seitenaufruf (Probe A6: zweimal
gefragt, einmal getestet).

**E-RW-17 — `RW_STAND` fragt `ui_krypto_bootstrap()`, nicht `auth_guard.php`**
(RW-02, ohne Frage; führt E-RW-05 aus). E-RW-05 und 3.4 nennen den
Rückfall-SELECT in `auth_guard.php`. Die Wache läuft aber bei jeder Anfrage,
auch bei jedem API-Aufruf, und gebraucht wird der Wert nur auf den Seiten,
die das Rüstzeug der Verschlüsselung anfordern. `rw_zustand()` in
`rueckweg_lib.php` fragt deshalb dort, mit demselben stillen Rückfall
(`'spalten'`), und liefert der Karte zugleich das Datum. Die Absicht von
E-RW-05 bleibt: Fehlen die Spalten, schreibt der Browser nicht an.


**E-RW-18 — Die Seite liefert die fertige Nachricht** (RW-03, ohne Frage;
führt E-RW-04 aus). 3.2 skizziert `RW_KONTO` und `RW_HERAUSFORDERUNG`, aus
denen der Browser die Nachricht zusammensetzt. Das Format stünde dann
zweimal — in `rw_nachricht()` und in `rueckweg.js`. Der Schlüsselschritt
liefert deshalb die Nachricht aus `rw_nachricht()` fertig; geprüft wird
unverändert gegen die Nachricht, die der Server beim Prüfen selbst baut. Die
Absicht von E-RW-04 bleibt: Was der Browser schickt, bestimmt nicht, was
geprüft wird.

---

## 3. Bauplan

### 3.1 Das Paar

```
Browser (unlock.js, Vormerkfach-Weg; oder Karte „Rückweg erneuern")
  kp   = crypto.subtle.generateKey({name:'ECDSA', namedCurve:'P-256'}, true, ['sign','verify'])
  spki = base64(exportKey('spki',  kp.publicKey))          → users.rw_oeffentlich
  priv = EdCrypto.encrypt(ck, base64(exportKey('pkcs8', kp.privateKey)))   → users.rw_privat  ('edk1:…')
  POST api/rueckweg_anlegen.php { token, oeffentlich: spki, privat: priv, ersetzen: 0|1 }

Server (api/rueckweg_anlegen.php)
  Sitzung, csrf_check(), rate_erlaubt('login', email)
  password_verify(token, password_hash)         sonst 403, rate_misserfolg('login')
  RW_OEFFENTLICH_RE, RW_PRIVAT_RE, rw_oeffentlich_pruefen(spki)  (lädt, Kurve muss secp256r1 sein)  sonst 400
  demo_ist_demo()                                → 403 (kein Paar im Demo, E-RW-15)
  rw_oeffentlich vorhanden && !ersetzen          → 409
  UPDATE users SET rw_oeffentlich=?, rw_privat=?, rw_seit=NOW() WHERE id=?
  protokoll('verwaltung', ersetzen ? 'rueckweg_erneuert' : 'rueckweg_angelegt')
  ersetzen: mail_einreihen('rueckweg_erneuert', email)
```

Der Server lernt dabei nichts, was er nicht schon hat: den öffentlichen Teil
(darf jeder kennen) und einen Chiffretext unter dem Inhaltsschlüssel (den er
nicht kennt). Was er **kann**: den öffentlichen Teil auf Form und Kurve
prüfen. Was er **nicht kann**: prüfen, ob der private Teil zum öffentlichen
gehört oder mit dem richtigen Inhaltsschlüssel verpackt ist — deshalb hängt
das Schreiben am Passwortnachweis (E-RW-02, -06), und deshalb geht der
Rückweg im Browser nur, wenn der Zettel die Hülle öffnet.

### 3.2 Der Rückweg

```
login.php, Code-Schritt (halbe Sitzung: konto, frist 5 min — E-P5c-53)
  GET  ?weg=schluessel   nur wenn rw_verfuegbar() && rw_oeffentlich IS NOT NULL
       $_SESSION[…]['rw_herausforderung'] = bin2hex(random_bytes(32)), ['rw_bis'] = jetzt + 300
       Seite liefert: WRAP_RC (pat_wrap_rc), RW_PRIVAT, RW_HERAUSFORDERUNG, RW_KONTO (id)
       lädt crypto.js, rueckweg.js — kein unlock.js; Abbruch → EdCrypto.vergissAbleitungen()

Browser (rueckweg.js)
  p = EdCrypto.pruefeRecoveryCode(eingabe)      Sofortmeldung wie pw_handling.php (M2-06)
  rk = EdCrypto.recoveryKeyHex(eingabe)
  ck = EdCrypto.decrypt(rk, WRAP_RC)             scheitert → „passt nicht", nichts wird gesendet
  pkcs8 = EdCrypto.decrypt(ck, RW_PRIVAT)
  key = importKey('pkcs8', …, {name:'ECDSA', namedCurve:'P-256'}, false, ['sign'])
  sig = sign({name:'ECDSA', hash:'SHA-256'}, key, utf8('nadoku-rw-v1|totp-rueckweg|' + RW_KONTO + '|' + RW_HERAUSFORDERUNG))
  POST login.php { weg: 'schluessel', signatur: base64(sig) }    (64 Byte r‖s)

Server
  halbe Sitzung gültig, Herausforderung vorhanden, nicht abgelaufen   sonst „abgelaufen", neue Herausforderung
  Herausforderung SOFORT aus der Sitzung nehmen (einmal gültig, gleich welcher Ausgang)
  rate_erlaubt('totp', email)
  nachricht = rw_nachricht($userId, $herausforderung)          aus Sitzung und Konto, nie aus dem Rumpf
  rw_pruefen($u['rw_oeffentlich'], $nachricht, base64_decode($sig))   phpseclib, IEEE, sha256
    falsch → rate_misserfolg('totp', email), Meldung, Herausforderung neu beim nächsten Aufruf
    richtig → totp_zuruecksetzen($userId, 'schluessel')   (die Funktion aus AP5: Spalten leeren, Codes löschen,
              Protokoll, Mail) → Sitzung wie nach einem gültigen Code (user_id, session_regenerate_id)
              Rolle user: Erfolgskarte „Zweitfaktor zurückgesetzt" mit „Weiter zur Startseite"
              Pflichtrolle: Location: zweitfaktor.php (das Tor aus E-P5c-61) mit Meldung oben
```

Der Wiederherstellungsschlüssel verlässt den Browser nie; der Server sieht
eine Signatur und prüft sie gegen den öffentlichen Teil. Eine Sitzung, in der
die Herausforderung fehlt, ist eine ohne Rückweg — kein Sonderfall, sondern
derselbe Zustand wie ein abgelaufener Code-Schritt.

### 3.3 Was AP5 festlegt und RW nachliest

- Die Namen der Zweitfaktor-Spalten und der Tabelle der Codes (E-P5c-54);
  RW schreibt sie nicht selbst, sondern ruft die Rücksetzfunktion von AP5.
- Den Namen dieser Funktion (im Konzept „`totp_zuruecksetzen()`") und ihren
  Parameter für den Weg (`verwaltung` | `schluessel`); hat AP5 keinen, bekommt
  sie ihn mit RW-03 — **eine** Funktion, zwei Aufrufer.
- Die Ablage der halben Sitzung (Schlüsselnamen) und den Topf `totp`.
- Die Mail `totp_zurueckgesetzt` (E-P5c-42, Verwaltungsweg); RW ergänzt den
  Satz zum Weg.
- Die Bedienwege `wege/einstellungen_profil.mjs` und den Code-Rechner der
  Werkzeuge (E-P5c-43), die die Rückwegprobe zum Einschalten braucht.

**Nachgelesen am 24.09.2026 gegen AP5 (`262787c`, Web 20.42.0).** Wo das
Konzept einen Arbeitsnamen trägt, gilt der Name aus AP5 — RW baut nichts
davon ein zweites Mal:

| Im Konzept | In AP5 | Folge für RW |
|---|---|---|
| Spalten des Zweitfaktors | `users.totp_geheimnis`, `totp_seit`, `totp_schritt`; Tabelle `totp_codes(id, user_id, hash, benutzt_am)`; Vorabfrage `totp_spalten_da()`, Zustand `totp_an()` / `totp_zustand()` | RW liest nur `totp_an()`; geschrieben wird über die Rücksetzfunktion |
| `totp_zuruecksetzen($userId, 'schluessel')` | **`totp_abschalten(int $userId, string $weg): bool`** in `totp_lib.php`, `$weg` heute `'selbst'` \| `'verwaltung'`. Leert Geheimnis, Schritt und Codes in einer Transaktion und schreibt, wenn er an war, `totp_zurueckgesetzt` (Verwaltung) bzw. `totp_ausgeschaltet` (selbst), jeweils mit `['weg' => …]`. **Die Mail schickt der Aufrufer** (`admin_user.php`) | RW-03 ergänzt `$weg = 'schluessel'` → `totp_zurueckgesetzt` mit `['weg' => 'schluessel']` und eigenem Text; `login.php` schickt die Mail wie `admin_user.php`. **Eine** Funktion, drei Aufrufer |
| halbe Sitzung | `$_SESSION['totp_halb'] = ['konto', 'email', 'bis', 'demo']`, Frist `TOTP_HALB_FRIST_S` (300 s); endet über `?abbrechen=1`, Ablauf oder Sperre mit `$vergessen = true` (Vormerkfach räumen) | Die Herausforderung liegt als `rw_herausforderung` und `rw_bis` in **diesem** Feld — endet der halbe Stand, endet sie mit |
| Topf `totp` | `rate_erlaubt('totp', null, [rate_merkmal_kennung($email)])`, fünf in 15 min, Leiter; `rate_misserfolg()` / `rate_erfolg()` mit denselben Merkmalen; Sperrmeldung `login_code_sperre()` | Der Signaturversuch zählt mit genau diesen Merkmalen (nicht `rate_erlaubt('totp', $email)`, wie 3.2 skizziert) |
| Mail `totp_zurueckgesetzt` | Katalog `mail_lib.php`, Art `konto`, Pflicht `link`; Text „die Verwaltung … hat den Zweitfaktor deines Kontos zurückgesetzt" | RW-03 macht den ersten Satz vom Weg abhängig (Wert `weg` in den Daten); der Verwaltungsweg bleibt wörtlich |
| Code-Schritt | `login.php`, `form#codeform` mit `schritt=code`, `?art=rc` für Wiederherstellungscodes, `?abbrechen=1`; die Integritätswache kennt `#codeform` als bedingtes Formular (`BEDINGTE_FORMULARE`, F-P5c-111) | Ein Formular des Schlüsselschritts ist ebenso bedingt und kommt in dieselbe Liste |
| Einrichtungstor | `zweitfaktor.php` (E-P5c-61), Teile in `zweitfaktor_teile.php` | Die Meldung „Zweitfaktor zurückgesetzt …" oben im Tor (M-RW-01, Bild 3) |
| Bedienwege, Rechner | `tools/bedienprobe/wege/einstellungen_profil.mjs` (Weg `einstellungen-profil-zweitfaktor`), `wege/zweitfaktor.mjs`, Hilfsmodul `tools/bedienprobe/probekonto.mjs`; `tools/zweitfaktor/totp.{php,mjs,py}` mit gemeinsamem Zähler (E-P5c-111) | Der neue Weg `wege/einstellungen_profil_rueckweg.mjs` und die Rückwegprobe benutzen `probekonto.mjs` bzw. `naechsterCode()` |
| Demo | `demo_zweitfaktor_leeren()` in `demo_zuruecksetzen()` (F-P5c-117) | RW-02 schreibt die Paar-Spalten an derselben Stelle zurück, nicht in einer zweiten Funktion |

### 3.4 Dateien

| Datei | Neu / geändert | Paket |
|---|---|---|
| `server/rueckweg_lib.php` | neu: `rw_nachricht()`, `rw_pruefen()`, `rw_oeffentlich_pruefen()`, `rw_selbsttest()`, `rw_verfuegbar()`, der Prüfvektor | RW-01 |
| `server/migration_lib.php` | Migration, drei Spalten | RW-01 |
| `server/schema.sql`, `server/validate_lib.php` | Spalten; `RW_OEFFENTLICH_RE`, `RW_PRIVAT_RE` | RW-01 |
| `server/status_lib.php` | Zeile „Rückweg-Prüfung" | RW-01 |
| `server/api/rueckweg_anlegen.php` | neu | RW-02 |
| `server/assets/rueckweg.js` | neu: `paarErzeugen(ck)`, `paarSenden()`, `signieren()` | RW-02, RW-03 |
| `server/assets/unlock.js` | Vormerkfach-Weg ruft `paarErzeugen()`, wenn `RW_STAND === 'fehlt'` | RW-02 |
| `server/auth_guard.php`, `server/ui.php` | `$rwStand` → `RW_STAND` in `ui_krypto_bootstrap()`, Rückfall-SELECT | RW-02 |
| `server/einstellungen.php` | Kartenzeile, Knopf „Rückweg erneuern", Demo-Sperre | RW-02 |
| `server/protokoll_lib.php`, `server/mail_lib.php` | zwei Arten, eine Mail | RW-02 |
| `server/demo_lib.php` | Reset leert die Paar-Spalten in `demo_zweitfaktor_leeren()`; Fixture und Erzeuger **unverändert** (E-RW-15) | RW-02 |
| `server/login.php` | dritter Weg, Herausforderung, Prüfung, Erfolgskarte, Weiterleitung | RW-03 |
| `server/adminbackup_lib.php` | **nichts** — Gegenprobe: die Spalten stehen in keiner Spaltenliste des Kontopakets | RW-02 |
| `tools/proben/rueckweg/` (`probe.mjs`, `probe.php`, `LIESMICH.md`), `proben.sh`, `pruefablauf.json`, `tools/kettenaufrufe/` | Rückwegprobe; Stufe 2 | RW-03 |
| `docs/Handbuch.md` (3.1, Zweitfaktor-Abschnitt aus AP5), `docs/Technik.md` (3, 4 Ende-zu-Ende, 4.98 Absatz zum Paar, 7 Runbook), `docs/Backup-Format.md`, `docs/Lizenzen.md` (phpseclib: zweiter Verwender), `docs/Design.md` (keine neue Klasse — nur die Zeile in 9.2 nennen), `docs/CHANGELOG.md`, `docs/Backlog.md` | Doku | RW-03, RW-04 |
| `tools/zaehlung/register.php` | keine neue Zeile nötig: `EdApi.postJson()` und `sitzung_starten()` werden benutzt, nicht umgangen; Gegenprobe im Prüfstand (Register 0 über der Decke) | RW-02 |

---

## 4. Arbeitspakete

### 4.1 Was für jedes Paket gilt

Wie P5c 3.1: Commit je Paket mit dem Prüfbericht aus
`bash tools/pruefstand/pruefen.sh` in der Nachricht; danach Statusblock,
Stand der Umsetzung und Prüfdokument fortschreiben und **pushen**;
Versionsstufe, Changelog, Doku, Backlog nach `CLAUDE.md` 2; die Migration
mit der Ansage „nach dem Deploy `update.php`, die Wartung bleibt an" und mit
Rückfall-SELECT; neue Probe mit Zeile „Anlass: Nr. …"; neuer Bedienweg nach
Seite benannt; was nicht gemessen werden konnte, steht im Prüfdokument vorn.

| Paket | Modellwahl | Fächerung |
|---|---|---|
| RW-01 | Opus | nichts — vier Dateien, ein Gedanke |
| RW-02 | Opus | nichts — `rueckweg.js`, `unlock.js` und der Endpunkt sind ein Weg |
| RW-03 | Opus | nichts — `login.php` ist eine Datei; Prüfarbeit ist nie gefächert |
| RW-04 | Opus | nichts — erzählender Text und Prüfarbeit |

### RW-01 — Grundlage: Prüfung, Migration, Statuszeile (E-RW-03, -04, -05, -11)

**Inhalt.** `rueckweg_lib.php` mit `rw_nachricht()`, `rw_pruefen()` (phpseclib,
IEEE, sha256; nur `secp256r1`), `rw_oeffentlich_pruefen()`, `rw_selbsttest()`
mit dem festen Vektor, `rw_verfuegbar()` mit Marke; Migration und
`schema.sql`; die beiden Prüfregeln; Statuszeile „Rückweg-Prüfung" in
`status_erhebung()`; `probe.php` Teil A (5.3); `Lizenzen.md` 3a (phpseclib:
zweiter Verwender); Runbook-Absatz.

**Migration:** ja — **nach dem Deploy `update.php`, die Wartung bleibt an**;
der Rückweg ist stumm, solange die Spalten fehlen. **Stufe:** Neben,
Prüfstand `haupt`.

**Abnahme.**
- Selbsttest grün mit `EC::forceEngine('PHP')` und mit `'OpenSSL'`: **2 von
  2**, Dauer je Weg im Bericht (Sollbereich reines PHP 150–400 ms).
- `probe.php` Teil A: **6 Fälle** (5.3) — 1 angenommen, 5 abgewiesen; dazu
  ein öffentlicher Teil auf `secp384r1` und einer auf Ed25519 → beide von
  `rw_oeffentlich_pruefen()` abgewiesen (**2 von 2**).
- Statuszeile in drei Lagen (Selbsttest gestellt): blau openssl, blau reines
  PHP, orange fehlgeschlagen — **3 von 3**; `rw_verfuegbar()` liest die Marke,
  Selbsttest läuft **einmal** je Katalog-Hash (Zähler im Prüflauf).
- Schemaprobe **4 × 0 Befunde**, Migrationsregister **0 Befunde**; Anmeldung
  und `betrieb_updates.php` vor `update.php` **200** (Rückfall).
- Register **0 über der Decke**; Quelltext grün; Textprobe **0 neue**.

### RW-02 — Das Paar entsteht: Anmeldung, Karte, Demo (E-RW-02, -06, -07, -09, -10, -14)

**Inhalt.** `api/rueckweg_anlegen.php`; `assets/rueckweg.js`
(`paarErzeugen()`, `paarSenden()`); Vormerkfach-Weg in `unlock.js`;
`RW_STAND` über `auth_guard.php`/`ui_krypto_bootstrap()` (Rückfall);
Kartenzeile und „Rückweg erneuern" in `einstellungen.php` (Passwortdialog
nach dem Muster von `schluessel.js`, Prüfsumme gegen `pat_key_check` vor dem
Verpacken); Demo-Sperre (kein Paar im Demo, E-RW-15); Protokollarten, Mail;
Demo-Reset; Gegenprobe Kontopaket ohne die Spalten; Bedienweg
`wege/einstellungen_profil_rueckweg.mjs`; Handbuch-Absatz zur Karte.

**Migration:** nein. **Stufe:** Neben, Prüfstand `haupt` (die Migration aus
RW-01 liegt im Unterschied zu `main`).

**Abnahme.**
- Bedienweg: Konto ohne Paar anmelden → Spalten gefüllt (**0 → 1**), Protokoll
  `rueckweg_angelegt` **+1**, Mails **+0**; zweite Anmeldung → Wert
  **unverändert**; Sitzung ohne Token an den Endpunkt → **403**, nichts
  geschrieben; `ersetzen` ohne Passwort → **403**; mit Passwort → neuer Wert,
  Protokoll `rueckweg_erneuert` **+1**, Mail **+1**; ohne `ersetzen` bei
  vorhandenem Paar → **409**; öffentlicher Teil auf falscher Kurve → **400**;
  Demo-Konto an den Endpunkt, mit und ohne `ersetzen` → **403** (E-RW-15).
- `RW_STAND`: `'da'`, `'fehlt'`, `'spalten'` (Spalten zurückgebaut), `'demo'`
  (nicht anlegen, E-RW-15) — **4 von 4** im Bilderlauf/Rollenprobe als Markup
  belegt; im Demo nach dem Anmelden **0** Paare.
- Ratenprobe: der Endpunkt zählt in `login` (Gegenprobe: 10 falsche Token →
  Sperre) — **1 von 1**.
- Kontopaket: `edbak_build()`-Spaltenlisten enthalten `rw_` **0-mal**; ein
  Kreislauf `edbak` in ein zweites Konto lässt dessen Paar **unverändert**.
- ~~Fixture: Erzeuger kopiert die Spalten (**2 von 2** vorhanden), Riegel
  weist eine `edka1:`-Fassung ab (**1 von 1** rot), Demo-Reset schreibt
  beide zurück (Wert vor = Wert nach, **2 von 2**).~~ Entfällt (E-RW-15).
  Stattdessen: Demo-Reset leert die drei Spalten (**3 von 3** NULL, gestellt
  über `demo_zweitfaktor_leeren()`), und der Reset ruft diese Funktion
  (Zweitfaktorprobe, Teil 6).
- Register **0 über der Decke** (der Endpunkt ruft `EdApi.postJson()`);
  Textprobe **0 neue**; Rollenprobe grün mit erweiterter Matrix (Endpunkt je
  Rolle: alle vier 200 mit Token; das Demo-Konto misst der Bedienweg, 403 mit
  und ohne `ersetzen` nach E-RW-15).

### RW-03 — Der Rückweg am Code-Schritt (E-RW-01, -04, -08, -12, -13, -14)

**Inhalt.** Dritter Weg in `login.php` (Verweis, Schlüsselschritt mit
Sofortprüfung, Erfolgskarte, Weiterleitung der Pflichtrollen ins Tor);
Herausforderung in der halben Sitzung; `signieren()` in `rueckweg.js`;
Prüfung und `totp_zuruecksetzen($userId, 'schluessel')`; Topf `totp`; Mail;
Rückwegprobe `probe.mjs` und `probe.php` Teil B (5.3); Eintrag in `proben.sh`,
`pruefablauf.json` (Berührung `login.php`, `rueckweg_lib.php`,
`rueckweg.js`), `tools/kettenaufrufe/` für Stufe 2; Seiten in
`seiten.json` (Schlüsselschritt braucht die halbe Sitzung — der Bilderlauf
erkennt den Code-Schritt seit AP5 und fotografiert ihn nicht; die neue
Ansicht wird über den Bedienweg belegt, nicht über den Bilderlauf);
Handbuch 3.1 und der Zweitfaktor-Abschnitt aus AP5; `Technik.md` 4 (Ende-
zu-Ende: das Paar), 4.98 (Absatz „Was das Paar ist und nicht ist"), 7;
`CHANGELOG.md`.

**Migration:** nein. **Stufe:** Neben, Prüfstand `haupt`.

**Abnahme.**
- Rückwegprobe `probe.mjs` gegen die Anlage: Wegwerfkonto anlegen, Passwort
  setzen (Schlüssel gelesen), anmelden (Paar entsteht), Zweitfaktor mit
  bekanntem Geheimnis einschalten, abmelden, anmelden → Code-Schritt →
  Schlüsselschritt → **Zweitfaktor aus** (Spalten leer, Codes 0), Protokoll
  `totp_zurueckgesetzt` mit `weg=schluessel` **+1**, Mail **+1**, Sitzung
  steht (Startseite **200**), Vormerkfach leer; dasselbe als BetreiberIn →
  landet auf `zweitfaktor.php` (**302**). **Falscher Zettel** → Meldung im
  Browser, **0 Anfragen** an den Server; **Tippfehler** → Zeichen benannt.
- `probe.php` Teil B: die sechs Fälle gegen den Login-Weg über die
  Bibliothek plus die Abzug-Gegenprobe (5.3): **N Werte, 0 Erfolge**;
  Gegenprobe zur alten Fassung **rot**.
- Ratenprobe: zehn falsche Signaturen → Sperre im Topf `totp` (**1 von 1**);
  eine abgelaufene Herausforderung (Frist gestellt) → **abgewiesen, neue
  Herausforderung**; dieselbe Signatur zweimal → **zweite abgewiesen**.
- Weg nicht angeboten, wenn `rw_oeffentlich IS NULL` oder `rw_verfuegbar()`
  falsch: Verweis **0-mal** im Markup, POST mit Signatur trotzdem → **400**
  (**2 von 2**).
- Bilderlauf: **0 Seiten mit Code-Schritt** unter den Bildern (wie AP5);
  Bedienprobe alle Wege grün; Textprobe **0 neue**; Register **0 über der
  Decke**; Kontraste unverändert (keine neue Farbe).
- Stufe 2: Rückwegprobe gegen Staging im Kettenaufruf **grün** (erst nach dem
  Merge messbar — P-RW-02).

### RW-04 — Abschluss: Prüfdokument, Einschübe, Kette

**Inhalt.** Prüfstand `haupt` über den ganzen RW-Stand; Prüfdokument RW mit
den Prüfpunkten `P-RW-01` bis `-05` und „nicht geprüft" vorn (5.4); die
Einschübe aus 8 und 9 auf dem P5c-Zweig einspielen (Statusblock P5c,
E-P5c-42, Rahmenplan 6, Backlog mit vergebenen Nummern); Nr. 249
berichtigen; Handbuch, Technik, Changelog gegenlesen (sie verweisen
aufeinander). **Kein eigener Pull Request:** RW geht mit dem 10c-PR
(E-P5c-104). Nach dem Merge: `update.php` (die RW-Migration zählt zu den
Migrationen aus 10c). ~~und die Fixture neu erzeugen (9.2)~~ — entfällt
(E-RW-15).

**Abnahme.** Prüfstand `haupt` grün mit dem Bericht im Commit; Prüfdokument
mit Zahlen zu jedem Punkt aus RW-01 bis RW-03; Einschübe eingespielt (der
P5c-Statusblock nennt AP5b als erledigt mit Commit).

---

## 5. Prüfplan

### 5.1 Prüfprotokoll-Soll

| Prüfung | Mittel | Paket | Soll |
|---|---|---|---|
| Selbsttest beide Engines | `probe.php` Teil A | RW-01 | 2 / 2, Dauer je Weg |
| Sechs Signaturfälle gegen die Bibliothek | `probe.php` Teil A | RW-01 | 1 angenommen, 5 abgewiesen |
| Fremde Kurve, fremdes Verfahren | `probe.php` Teil A | RW-01 | 2 / 2 abgewiesen |
| Statuszeile drei Lagen | Prüflauf mit gestelltem Selbsttest | RW-01 | 3 / 3 |
| Schemaprobe, Migrationsregister | `plattform.sh schema`, automatisch | RW-01 | 4 × 0, 0 |
| Anlegeweg (Token, 403, 409, 400, Demo, Erneuern, Mail) | Bedienweg `einstellungen_profil_rueckweg.mjs` | RW-02 | 8 / 8 |
| `RW_STAND` vier Lagen | Rollenprobe (Markup) | RW-02 | 4 / 4 |
| Kontopaket ohne die Spalten; Kreislauf lässt Paar stehen | Gegenprobe, Kreislauf `edbak` | RW-02 | 0-mal, unverändert |
| ~~Fixture: kopiert, Riegel, Reset~~ Demo: kein Paar, Reset leert (E-RW-15) | Zweitfaktorprobe Teil 6, Bedienweg | RW-02 | 3 / 3 NULL, Endpunkt 403 |
| Der echte Rückweg im Browser (user, BetreiberIn, falscher Zettel, Tippfehler) | `probe.mjs` | RW-03 | 4 / 4 |
| Sechs Fälle gegen den Login-Weg, Abzug-Gegenprobe, alte Fassung | `probe.php` Teil B | RW-03 | 1 / 5, N / 0, rot |
| Ratenschutz `totp`, Ablauf, Wiederholung | Ratenprobe, `probe.php` | RW-03 | 1 / 1, 1 / 1, 1 / 1 |
| Weg nicht angeboten → nicht annehmbar | `probe.php` Teil B | RW-03 | 2 / 2 |
| Bilderlauf, Bedienprobe, Textprobe, Register, Kontraste | Prüfstand `haupt` | alle | 0 Code-Schritt-Bilder, grün, 0 neue, 0 über der Decke, 0 verfehlt |
| Rückweg gegen Staging | Stufe 2, `tools/kettenaufrufe/` | RW-04 (nach dem Merge) | grün |

### 5.2 Was der Beleg beweisen muss — und was nicht

Ein „geht nicht" lässt sich durch Ausprobieren nicht vollständig beweisen.
Der Beleg hat deshalb zwei Hälften: **die Konstruktion** (jeder Wert, den
ein Datenbankabzug enthält, ist entweder öffentlich, ein Hash oder ein
Chiffretext unter einem Schlüssel, den der Abzug nicht enthält) und **die
Messung** (mit allen diesen Werten in der Hand kommt kein Signaturversuch
durch). Beides steht im Prüfdokument nebeneinander; die Zahl allein wäre
eine grüne Zahl ohne Gegenstand (Grundsatz 7).

| Wert im Abzug | Was er ist | Öffnet er `rw_privat`? |
|---|---|---|
| `rw_oeffentlich` | öffentlicher Teil — darf jeder kennen | nein (prüft nur) |
| `rw_privat` | AES-GCM unter dem Inhaltsschlüssel | nur mit dem Inhaltsschlüssel |
| `pat_wrap_pw` | Inhaltsschlüssel unter dem Datenschlüssel (Passwort **und** `kdf_anteil` aus `config.php`) | nur mit Passwort und Anteil |
| `pat_wrap_rc` | Inhaltsschlüssel unter dem Wiederherstellungsschlüssel | nur mit dem Zettel |
| `pat_key_check` | 128-Bit-Hash des Inhaltsschlüssels | nein |
| `password_hash`, `kdf_salt`, `kdf_iter`, `session_epoch`, alle übrigen Spalten | Hashes, Parameter, Klartextangaben ohne Schlüsselcharakter | nein |
| `app_state` (Kennungen, Marken), `config.php` (`kdf_anteil`, `server_key`) | Server-Anteil und Serverschlüssel — **nicht** in der Datenbank; und selbst mit ihnen fehlt die PBKDF2-Hälfte | nein |

Was der Beleg **nicht** behauptet: dass Passwort plus Zettel nicht genügen
(sie genügen — das ist der Rückweg), und dass ein Angreifer mit Zugriff auf
den laufenden Browser der NutzerIn nichts kann (das ist nie Gegenstand der
Zusage gewesen).

### 5.3 Die Rückwegprobe im Einzelnen

**Teil A (`probe.php`, RW-01) — die Bibliothek.** Erzeugt in PHP (phpseclib)
ein Paar, signiert die Nachricht nach E-RW-04 und ruft `rw_pruefen()`:

| Fall | Erwartung |
|---|---|
| echte Signatur, richtige Nachricht | angenommen |
| **fremd:** Signatur eines anderen Paars | abgewiesen |
| **verändert:** ein Byte der Nachricht | abgewiesen |
| **zweckfremd:** Zweck `passwort-reset` statt `totp-rueckweg` | abgewiesen |
| **fremdes Konto:** andere Kontonummer in der Nachricht | abgewiesen |
| **verstümmelt:** 63 Byte Signatur | abgewiesen |

Dazu der Selbsttest mit erzwungener Engine (`PHP`, `OpenSSL`) und die zwei
fremden Schlüsselformen (P-384, Ed25519) gegen `rw_oeffentlich_pruefen()`.

**Teil B (`probe.php`, RW-03) — der Weg im Server, ohne HTTP.** Stellt die
halbe Sitzung her (wie die Rollenprobe Sitzungen fälscht, E-P5c-78) und
ruft den Prüfzweig von `login.php` über die Bibliothek:

| Fall | Erwartung |
|---|---|
| echte Signatur über die Herausforderung der Sitzung | Zweitfaktor aus, Protokoll +1 |
| **wiederholt:** dieselbe Signatur ein zweites Mal | abgewiesen (Herausforderung verbraucht) |
| **abgelaufen:** `rw_bis` in die Vergangenheit gestellt | abgewiesen, neue Herausforderung |
| **fremd, verändert, zweckfremd** wie in Teil A | abgewiesen, `rate_misserfolg('totp')` +1 |
| **Abzug-Gegenprobe:** jede Spalte von `users` dieser Zeile, jeder Wert aus `app_state`, `kdf_anteil` und `server_key` aus `config.php` — mit jedem Wert als Schlüssel `rw_privat` zu öffnen versucht (AES-GCM, `edk1:`), mit jedem als Hex und als Rohbytes | **N Werte, 0 Erfolge** (N im Bericht) |
| **alte Fassung:** Rumpf mit richtigem `pat_key_check`, ohne Signatur | abgewiesen (**rot** wäre: angenommen) |
| Weg nicht angeboten (`rw_oeffentlich` NULL; `rw_verfuegbar()` falsch) | POST mit gültiger Signatur → 400 |

**`probe.mjs` (RW-03) — der echte Weg im Browser.** Wegwerfkonto über
`pruefkonto.py` (E-RP-02), Passwort über `pw_handling.php` setzen und den
Wiederherstellungsschlüssel von der Seite lesen, anmelden (Paar entsteht
still — Spalten 0 → 1), Zweitfaktor mit bekanntem Geheimnis einschalten
(Code-Rechner aus E-P5c-43), abmelden, anmelden, dritter Weg, Zettel
eingeben → Zweitfaktor aus, Startseite 200; als BetreiberIn → 302 auf
`zweitfaktor.php`; falscher Zettel → 0 Anfragen (Netzmitschnitt der Seite);
Tippfehler → Zeichen in der Meldung. Alle Krypto aus `crypto.js` und
`rueckweg.js` — die Probe prüft die Anwendung, nicht sich selbst. Das Konto
wird danach entfernt.

### 5.4 Prüfpunkte der Betreiberin (ins Prüfdokument, RW-04)

| Nr. | Was | Woran ein Scheitern zu erkennen ist |
|---|---|---|
| P-RW-01 | Nach dem Tag auf Produktiv: Betrieb → Status, Karte „Server", Zeile „Rückweg-Prüfung" | orange statt blau; oder die Zeile fehlt (Migration nicht gelaufen) |
| P-RW-02 | Nach dem Merge: der Stufe-2-Lauf zeigt die Rückwegprobe grün | Lauf rot mit dem Namen der Probe |
| P-RW-03 | Auf Staging mit einem eigenen Konto: Zweitfaktor einschalten, Codes wegwerfen, abmelden, anmelden, „Gerät und Codes verloren?", Zettel eingeben | keine Erfolgskarte; oder die Mail „Zweitfaktor zurückgesetzt" kommt nicht an |
| P-RW-04 | Dasselbe mit einem **falschen** Zettel (aus einem anderen Konto) | die Seite sagt nicht „passt nicht zu diesem Konto", sondern etwas anderes — oder setzt zurück |
| P-RW-05 | Nach dem Merge: Demo → „Auf Standard zurücksetzen"; danach im Demo anmelden und Betrieb → Status bzw. die Kontoseite des Demo-Kontos ansehen (E-RW-15) | der Reset läuft mit Fehler; oder im Protokoll steht ein `rueckweg_angelegt` für das Demo-Konto |

---

## 6. Grenzen

- **Passwort plus Zettel öffnen alles.** Das ist der Rückweg — und es ist
  derselbe Zuschnitt wie Passwort plus Codeblatt. Wer den Zettel verliert,
  hat weiterhin den Verwaltungsweg; wer beides verliert, Zettel und
  Verwaltung, hat den Runbook-Notweg (Nr. 249, Rest).
- **Der Beleg ist Konstruktion plus Messung** (5.2). Er beweist nicht, dass
  es keinen unbekannten Weg gibt; er beweist, dass jeder gespeicherte Wert
  einer von drei Sorten ist und dass die Probe mit allen Werten scheitert.
- **Wer die Datenbank ändern kann, kann den öffentlichen Teil tauschen.**
  Ein Abzug genügt nicht; ein Schreibzugriff auf `users` würde genügen — und
  wer den hat, kann heute schon `password_hash` tauschen. Der Rückweg ändert
  an dieser Grenze nichts, und das Konzept behauptet nichts anderes.
- **`pw_handling.php` bleibt, wie es ist:** Der Passwort-Reset prüft weiter
  gegen `pat_key_check` — dort schützt der Wert vor einem Versehen, nicht vor
  einem Angreifer (F-P5c-106, Vorbereitung 2), und der Zweitfaktor steht
  danach weiter davor.
- **Reines PHP kostet 214 ms je Prüfung.** Das gilt für Hoster ohne
  `openssl`-Kurve; die Statuszeile sagt es. Ein Rückweg je Konto und Jahr
  trägt das; ein Rateangriff scheitert vorher am Topf `totp`.
- **Die Herausforderung lebt in der Sitzungsdatei** (`server/.sitzungen/`,
  Schritt 16). Sie ist ein Zufallswert ohne Wert für jemanden, der die Datei
  liest — ohne den privaten Teil ist sie nichts wert, und sie verfällt nach
  fünf Minuten.
- **Der Inhaltsschlüssel wechselt nie** (R1). Sollte das je anders werden
  (ein Umschlüsseln des Kontos), muss das Paar mit — dann wird `rw_privat`
  an derselben Stelle neu verpackt wie `pat_wrap_pw` und `pat_wrap_rc`.
- **Konten ohne Inhaltsschlüssel** (`pat_wrap_rc IS NULL`, Passwort nie
  gesetzt) bekommen kein Paar und brauchen keins: Ohne Passwort gibt es
  keinen Zweitfaktor.
- **Stufe 2 misst erst nach dem Merge.** Bis dahin ist der Weg auf Staging
  unbelegt; P-RW-02 und -03 sagen, wann und woran.

---

## 7. Mockup-Runde M-RW-01 — freigegeben am 24.09.2026

Auftrag Q-RW-08, gebaut aus dem freigegebenen M-P5c-02b gegen das echte
`style.css`; Dateien, Messungen und Bauart in `konzept-rw/LIESMICH.md`.
Gezeigt: (1) der Code-Schritt und der Schritt „Wiederherstellungscode" mit
dem dritten Verweis; (2) der Schlüsselschritt in vier Lagen (leer,
vollständig, Tippfehler, passt nicht); (3) nach dem Erfolg die Erfolgskarte
für user und das Einrichtungstor für die Pflichtrolle; (4) die Kartenzeile in
drei Lagen; dazu die Handybreite. **Gemessen:** Überlauf 0 px, 0 von 11 und 0
von 4 Rahmen; Knöpfe 16 von 16 zu 36 px (Zeigergerät) und 6 von 6 ≥ 44 px;
0 fehlende Ressourcen, 0 Konsolenfehler; keine neue Klasse, kein neues
Symbol, keine neue Farbe. **Entschieden mit der Freigabe, ohne eigene
Frage:** die Form wie im Bild (E-RW-13); die Statuszeile „Rückweg-Prüfung"
ohne Bild (gewöhnliche Statuszeile, wie M-P5c-02 (d)).

---

## 8. Einschub für das Konzept P5c (spielt die Umsetzung ein)

### 8.1 Statusblock

- Zeile **Offen**: „**Einschubkonzept RW liegt vor** (Fassung 1, freigegeben
  24.09.2026; Q-RW-01 bis -10 beantwortet, E-RW-01 bis -14, M-RW-01
  freigegeben): `Konzept-RW-Zweitfaktor-Rueckweg.md`. Gebaut wird es als
  **AP5b = RW-01 bis RW-04** nach AP5 und vor AP11. Sonst nichts: …" (der
  Rest der Zeile bleibt).
- Tabelle **Stand der Umsetzung**, Zeile AP5b: „AP5b Rückweg über den
  Wiederherstellungsschlüssel (Konzept RW: RW-01 bis RW-04) | offen — Konzept
  liegt vor | Neben | haupt | **ja** (drei Spalten an `users`, RW-01) | |".
- Zeile **Entschieden**: „… **Vor AP5b: E-RW-01 bis -14** (Konzept RW,
  24.09.2026; E-RW-08 ersetzt den dritten Punkt von E-P5c-42)."
- Zeile **Nummern**: die Nummern aus 9.3, wenn vergeben.

### 8.2 E-P5c-42, dritter Punkt (ersetzt den durchgestrichenen Absatz)

> - **Zurücksetzen mit dem Wiederherstellungsschlüssel — fälschungssicher,
>   nach Konzept RW** (E-RW-01 bis -14, 24.09.2026): am Code-Schritt der
>   Anmeldung, für **alle vier Rollen**, mit einer Signatur über eine
>   Herausforderung des Servers; der private Teil des Kontopaars hängt am
>   Inhaltsschlüssel, ein Datenbankabzug enthält nur den öffentlichen Teil.
>   `pat_key_check` spielt keine Rolle mehr. Der Verwaltungsweg (Punkt 2)
>   bleibt unverändert. Nr. 249 ist damit für den Fall „Passwort und
>   Notfallblatt vorhanden" gelöst; der Runbook-Notweg bleibt für den Rest.
>   *(Der aufgehobene Absatz vom 24.09.2026 bleibt darunter als Werdegang
>   stehen.)*

Dazu in **3.0 Reihenfolge**: „AP5 → **AP5b** → AP6 …"; in **3.2 Fächerung**
eine Zeile „AP5b | nichts | alles (Konzept RW 4.1)"; in **AP11**: „Migrationen
aus AP4, AP5, **AP5b**, AP7 und AP8".

### 8.3 Q-P5c-38 in der Fragentabelle

Spalte Entscheidung ergänzen: „… — **Konzept RW liegt vor (24.09.2026)**,
E-RW-01 bis -14".

---

## 9. Einschub für Rahmenplan und Backlog (spielt die Umsetzung ein)

### 9.1 Rahmenplan, Abschnitt 8 und 10

Keine eigene Erledigt-Zeile — RW ist ein Paket von P5c und geht in dessen
Zeile mit. Eine Verlaufszeile in Abschnitt 10 mit dem Einspielen: „Konzept
RW liegt vor (24.09.2026, Zweig `claude/nice-lovelace-snlo8m`), Zuarbeit
erledigt; AP5b eingeplant."

### 9.2 Rahmenplan, Abschnitt 6 (Zuarbeiten)

| Was | Wofür | Wann |
|---|---|---|
| ~~**Einschubkonzept RW erstellen lassen** …~~ | 10c (AP5b) | **erledigt 24.09.2026** — `Konzept-RW-Zweitfaktor-Rueckweg.md`, Zweig `claude/nice-lovelace-snlo8m`; AP5b gebaut aus RW-01 bis RW-04 |
| ~~**Nach dem Merge des 10c-PR: die Demo-Fixture neu erzeugen** …~~ | 10c AP5b | **entfällt** — das Demo-Konto bekommt kein Paar (E-RW-15, Q-RW-11) |
| **Prüfpunkte P-RW-01 bis -05** (Prüfdokument RW): Statuszeile „Rückweg-Prüfung" auf Produktiv nach dem Tag; ein echter Rückweg mit eigenem Konto auf Staging, einmal mit falschem Zettel; die Mail im Postfach | 10c AP5b | nach Merge und Tag |

### 9.3 Backlog (Nummern vergibt die Umsetzung)

- **Zeile ohne Nummer, neu:** „**`Sandbox-Setup.md` 1 sagt, Firefox und
  WebKit starten im Container nicht — sie starten.** *Aufgenommen 24.09.2026
  aus Konzept RW (F-RW-02).* Gemessen mit Playwright 1.56: Firefox 142 und
  WebKit 26 starten, laden eine Seite über `localhost` und rechnen WebCrypto.
  Die Tabelle „Nicht im Abbild" nennt die Systembibliotheken beider Motoren
  als fehlend (Nr. 183); das gilt für dieses Abbild nicht mehr. *Weg:* die
  Zeile berichtigen und mit einem Datum versehen, Nr. 183 nachsehen; ob der
  Bilderlauf in `haupt` damit alle drei Motoren fahren kann, gehört zu
  Nr. 300. **Zuordnung: Backlog-Runde.**"
- **Nr. 249 berichtigen** (nicht verschieben): „**Teilweise gelöst mit
  Konzept RW (E-RW-08):** Hat die einzige BetreiberIn Passwort und
  Notfallblatt, setzt sie den Zweitfaktor am Code-Schritt selbst zurück. Der
  Wiederanlauf-Fall bleibt für den Rest: ohne Zettel, ohne Passwort, oder
  wenn der Rückweg ausgeschaltet ist (Statuszeile orange)."
- **Nr. 141**: Verweis ergänzen: „Rückweg über den Wiederherstellungsschlüssel
  → Konzept RW, AP5b."

---

## 10. Wo nachlesen

- `Vorbereitung-RW-Zweitfaktor-Rueckweg.md` — Auftrag, Befund, Randbedingungen
  R1–R9, die zehn Fragen.
- `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md` — E-P5c-15, -41 bis -44,
  -53, -54, -61, AP5, Q-P5c-38, E-P5c-104, F-P5c-106.
- `konzept-rw/LIESMICH.md` — die Mockup-Runde mit Messwerten.
- `docs/Technik.md` 4 (Ende-zu-Ende, Server-Anteil), 4.98, 4.98d, 7;
  `docs/Pruefablauf.md` 3, 5, 7; `docs/Lizenzen.md` 3a (phpseclib).
- Code: `server/pw_handling.php`, `server/assets/crypto.js`,
  `server/assets/unlock.js`, `server/assets/schluessel.js`,
  `server/api/schluessel_erneuern.php`, `server/api/kdf_upgrade.php`,
  `server/serverkrypto_lib.php`, `server/demo_lib.php`,
  `tools/referenzdatensatz/fixture/erzeugen.php`, `tools/proben/freigabe/`,
  `tools/proben/anteil/`, `server/vendor/phpseclib3/Crypt/EC/`.
