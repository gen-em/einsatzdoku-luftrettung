# Konzept P5c — Rollen, Sicherheit, Betriebslage

**Rahmenplan:** Schritt 10c (R82), R10, R25, R31, R36, R38, R39, R42, R64,
R81, R83; Einschub vom 20.09.2026 (Abschnitt D). **Backlog:** 80 (Rest),
121, 141, 168, 169, 191, 192, 198, 200, 243, 244, 245, 246; aus Abschnitt 8
neu 248, 249 (Nummern nach dem Einschub vom 20.09.2026, Weg A). **Nicht hier:**
122 (Diagramme in der Statistik → Schritt 17), 228 (nur auf Anlass).
**Vorbereitung:** `Vorbereitung-P5c-Protokollierung.md` (V1–V9 entschieden,
16./20.09.2026); Gestaltungsvorgaben des Auftraggebers vom 17. und
20.09.2026 (Konzept P5b Abschnitt 6; Erklärtext-Regel).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2); **ein Fable-Schritt**
in der Umsetzung (Mockup-Runde M-P5c-01, Abschnitt 6). **Ablage:** dieses
Dokument, Prüfdokument daneben (`Pruefdokument-P5c-Rollen-Sicherheit-Betriebslage.md`,
entsteht mit AP1), Mockups in `konzept-p5c/mockups/`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **Freigegeben (Auftraggeber, 20.09.2026).** E-P5c-01 bis -09 aus den Gesprächen (V1–V9, Banner, Erklärtext, Druckseiten); E-P5c-10 bis -24 aus dem Nachmessen. **Am selben Tag nach der Freigabe korrigiert** (Abschnitt 2.5): Nummern nach Weg A, drei inhaltliche Stellen (F-P5c-5 bis -7), Verweise und Zahlen. |
> | Entschieden | E-P5c-01 bis E-P5c-24 |
> | Offen | nichts. **F-P5c-1 bis -4** gelten mit der Freigabe. **F-P5c-5 bis -7** (2.5) sind Korrekturen der Konzeptinstanz **nach** der Freigabe — zur Kenntnis und zum Widerspruch; F-P5c-7 (Ort des Protokolls) wird im Mockup M-P5c-01 ohnehin sichtbar |
> | Umsetzung | nicht begonnen; **nach Schritt 16 und Schritt 15** (Einschub 20.09.2026). Reihenfolge AP1 → M-P5c-01 → AP2 … AP11 (3.0) |
> | Fable-Schritte der Umsetzung | **einer**: Mockup-Runde M-P5c-01 vor AP2 (Protokoll, Dashboard, Menü-Gliederung, Rechtstext-Vorschau, Zeitraum-Zählung, Druckseiten) |
> | Nummern | keine Rahmenplan-Fassung aus diesem Konzept. Backlog **243–246, 248, 249** stehen im Einschub vom 20.09.2026 (Weg A: feste Nummern, die Kette-II-Instanz reserviert 241–249 im Backlog-Kopf, bevor sie einspielt); Abschnitt 7 und 8 übernimmt die einspielende Instanz |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP1 bis AP11 | offen | — | — |

---

## 0. Auftrag und Umfang

**Anlass.** Dritter Teil von P5 (R82): was der Betrieb **über** den Konten
braucht — Rollen (Support, Zweitfaktor), das lesbare Protokoll (10b baute den
Schreibweg), Banner, Fehlerprotokoll, Health, das Betriebslage-Dashboard in
seinem festen Minimalumfang, der Rest von R39, und — nach Entscheidung des
Auftraggebers vom 20.09.2026 — das **Aufräumen der Einstellungen**: Menü,
Texte, Druckseiten.

**Was dieses Konzept liefert:** Befund am Stand `origin/main` `862ca7f`
(Web 20.25.0, 20.09.2026), Festlegungen als E-Einträge, elf Arbeitspakete
mit Abnahmezahlen, ein Mockup-Auftrag, Prüfprotokoll-Soll, Einschübe.

**Was es nicht liefert:** Diagramme und freie Zeiträume in der Statistik
(Nr. 122 → Schritt 17); Sitzungsbindung und Serverschlüsselwechsel
(Schritt 18); Änderungen an Uhr und Android (keine nötig — der Health-
Endpunkt und das Banner sind Server); die Zusammenführung von
`sicherheit_ereignisse` in `protokoll_ereignisse` (V6: **bleibt getrennt**,
E-P5c-11); Proof-of-Work (228).

**Kein Versionssprung im Konzept** (K3).

---

## 1. Befund (`origin/main` `862ca7f`, 20.09.2026)

### 1.1 Protokoll und Sicherheit

- `protokoll_lib.php` (P5b AP1): `protokoll(reiter, art, text, daten,
  betroffen)`, Tabelle `protokoll_ereignisse(id, zeit, reiter, art,
  urheber_user_id, urheber_art, betroffen_user_id, text, daten)`, sechs
  Reiter (`PROTOKOLL_REITER`: verwaltung, email, jobs, sicherung, ziele,
  system), Fristen als Konstanten (Verwaltung 365, einstellbar 90–1 095;
  übrige 30), Zähler `protokoll_fehler`. **Lesbar ist es nicht** — nur die
  Zählkarte auf Betrieb → Status.
- `betrieb_sicherheit.php` (P5a AP8): Unterseite Status → Sicherheit mit
  `sicherheit_ereignisse` (Sperren, Verlangsamung, Bremse, Ziel-Löschungen),
  30 Tage, IPs im Klartext, Knopf „aufheben".
- **77 `error_log()`-Aufrufe in 32 Dateien** in `server/` (nachgemessen
  20.09.2026 an `862ca7f`, ohne `vendor/`, Kommentare und Zeichenketten
  ausgefiltert; ein blankes `grep` findet 84 Stellen, sieben davon sind
  Kommentare — 16.09.: 42; P5a/P5b haben fast verdoppelt), Präfixe
  uneinheitlich; **kein** `set_exception_handler()`,
  ein lokaler `set_error_handler()` in `sicherungsziel_lib.php`. Die
  Fehlerseite zeigt eine achtstellige Kennung (`json_fehler()`), aber nicht,
  wohin man sie meldet (R38).
- Rollen: `user`, `admin`, `betreiberin` (`ROLLEN` in `db.php`;
  `rolle_darf_verwalten()`, `rolle_ist_betreiberin()`). Kein Support, kein
  Zweitfaktor, keine Bus-Faktor-Prüfung. **Alle sieben `betrieb_*.php`
  verlangen `require_betreiberin()`** (nachgemessen 20.09.2026): Den Block
  Betrieb sieht allein die BetreiberIn (R75), Verwaltung sehen Admin und
  BetreiberIn (R74 (1)) — das entscheidet den Ort des Protokolls (F-P5c-7).
- Kein Ankündigungsbanner, kein Umgebungsetikett, kein Health-Endpunkt.

### 1.2 Betriebslage

- `betrieb_statistik.php` (S8 AP4): zählt Konten, Geräte, Modelle; die
  R38-Zählung („aktiv" = `last_login` **oder** `devices.last_seen` im
  Fenster; Einsätze nach `started_at` in fünf Fenstern, ohne Demo, ohne
  Papierkorb) gibt es nicht — Nr. 192 hält fest, dass S8 anderes zählt.
  Der Index `missions(started_at)` ist nie gelegt worden (Nr. 191); der
  vorhandene führt mit `user_id`.
- Geräteverteilung (R42): Art, Modell, Teilenummer liegen; der Nachlöse-Job
  aus P5a hält sie aktuell. **Herkunft je Einsatz** (`origin`,
  `HERKUNFT_WERTE`, R64) wird nirgends ausgewertet — Nr. 80 Rest.

### 1.3 Einstellungen, Texte, Druck

- `ui_einstellungen_uebersicht()` (`ui.php` Z. 855 ff.): drei Bereiche
  (Einstellungen, Verwaltung, Betrieb) als Listen; die Bereichsnamen sind
  Zwischenüberschriften ohne Gewicht — Nr. 244.
- Erklärtext: die Verwaltungs- und Betriebsseiten tragen je Karte zwei bis
  sechs Sätze (Zählung folgt in AP9 als Ausgangszahl); das Handbuch ist seit
  P5b AP8 aus der Anwendung erreichbar (`hilfe.php#abschnitt`), wird von den
  Karten aber nirgends verlinkt — Nr. 245.
- `betrieb_schluesselblatt.php` (S10) und `notfallblatt.php` (P5b AP9):
  Fließtext, kein Logo, Umbruch auf zwei Seiten bei langen Werten — Nr. 246.
- Rechtstexte (`admin_rechtstexte.php`): Bearbeiten ohne Vorschau — Nr. 121;
  Zeitraumübersicht zählt Winde und Bergwacht nicht — Nr. 198 (Mockup).
- R39-Rest: Überbleibsel zentraler Stammdaten (Nr. 168, Liste im Backlog);
  Diensttag mit „Anderem Rettungsmittel" ohne Besatzung (Nr. 169).

### 1.4 Mail

- Warteschlange, Unzustellbar-Liste, Sammelmails (P5a AP5); kein Postfach,
  das Rückläufer liest — Nr. 200.

---

## 2. Entscheidungen

### 2.1 Aus den Gesprächen (E-P5c-01 bis -09)

**E-P5c-01 — Das Protokoll heißt Protokoll und führt Betriebsereignisse**
(V1, 16.09.2026). Kein Zugriffsprotokoll; die Zusage aus `schema.sql` und
`Technik.md` bleibt und steht auf der Protokollseite als erster Satz.

**E-P5c-02 — Reiter, Rechte, Fristen** (V2, V3, V8, V9). Reiter: Verwaltung,
Sicherheit, E-Mail, Jobs, Sicherung, Ziele, System — jedes Ereignis hat
einen, kein „Sonstiges". Sehen: BetreiberIn alle; Admin Verwaltung, E-Mail,
Jobs, Sicherung; Support (E-P5c-14) Verwaltung und E-Mail lesend. **Löschen:
niemand** — nur Fristen (Sicherheit und übrige 30 Tage fest; Verwaltung 365,
90–1 095). Herunterladen und Fristen ändern: BetreiberIn, protokolliert.
IPs nur im Reiter Sicherheit.

**E-P5c-03 — Archiv als Mischweg** (V4, V5, V6). Alle **7 Tage**
(einstellbar) schreibt der Job `protokoll_archiv` die Einträge des
Zeitraums als ZIP mit einer JSON-Zeilen-Datei je Reiter, **ohne IP-Spalte**,
versiegelt mit `sk_versiegeln()`, nach `sicherungen/protokoll/`; Dateiname
und ZIP-Kopf tragen die **Kennung des Serverschlüssels**; Aufbewahrung
**365 Tage** (einstellbar), dann löscht der Job. Download entsiegelt beim
Herunterladen und wird selbst protokolliert; passt die Kennung nicht zum
aktuellen Schlüssel, sagt die Seite das und nennt das Wiederanlaufpaket.
Komplett-Backup: `protokoll_ereignisse` ja; `sicherheit_ereignisse` und
`rate_limits` **ausgenommen** (Ausnahmeliste in `komplett_lib.php`, neu);
das Archiv geht mit dem Versandjob auf das Sicherungsziel (Einstellung,
Vorgabe an) und zählt bei E-P5a-03 als eigene Dateiart.

**E-P5c-04 — Schreibweg bleibt, Fehlfall bleibt** (V7): `protokoll()`
unverändert; scheitert das Schreiben, scheitert die Handlung nicht.

**E-P5c-05 — Umgebungsbanner** (20.09.2026, Nr. 243). `config.php`
`app.umgebung = ['name' => 'Staging', 'farbe' => 'rot']`, Vorgabe leer; ist
es gesetzt: Kopfleiste in **Newroz-Rot** statt Dunkelblau, Zeile „Staging —
Testdaten, kein Echtbetrieb" unter der Kopfleiste, Seitentitel-Präfix
„[Staging]". Nie abgeleitet (nicht aus Domain, Zweig oder Kette); die
Statusseite warnt, wenn `mail.betreff_praefix` gesetzt ist und
`app.umgebung` nicht. **Derselbe Baustein** trägt das Ankündigungsbanner
(E-P5c-13).

**E-P5c-06 — Erklärtext-Regel** (20.09.2026, Nr. 245, neue Grundregel in
`Design.md` 6): In der Oberfläche steht je Karte **höchstens ein Satz**,
der sagt, was hier passiert; alles Erklärende steht im Handbuch, die Karte
trägt den Verweis auf die Sprungmarke; Warnungen bleiben als Meldung,
Feldhinweise bleiben eine Zeile. **Alle** Seiten unter Verwaltung und
Betrieb werden danach überarbeitet; ausgelagerter Text wandert ins Handbuch,
nichts wird gelöscht.

**E-P5c-07 — Menü-Gliederung** (Nr. 244): zuerst Mockup mit klarer
Überschriftenzeile je Bereich; trägt das nicht, eigene Karte je Bereich.
Entscheidung im Mockup M-P5c-01.

**E-P5c-08 — Druckseiten** (Nr. 246): Schlüsselblatt und Notfallblatt mit
einem Baustein `.blatt-druck` — Marke + „NAdoku" und Überschrift oben,
Schlüssel in einer Kachel (Rahmen, Rauch, Feste Schrift, Vierergruppen),
**genau eine A4-Seite** (`@page A4`, feste Ränder, `page-break-inside:
avoid`), Abnahme per PDF-Druck aus zwei Browsern. Ändert E-P5b-09.

**E-P5c-09 — Gestaltungsvorgaben vom 17.09.2026 gelten** (Konzept P5b,
Abschnitt 6): Zeilenaktionen rechtsbündig in einer Spalte, Kartenfuß Häkchen
links / Zweitaktion rechts, alles vertikal zentriert — Abnahmekriterium
jedes neuen Bausteins.

### 2.2 Aus dem Nachmessen (E-P5c-10 bis -24)

**E-P5c-10 — Protokollseite: Verwaltung → Protokoll** (neue Seite
`admin_protokoll.php`; Ort nach R74 (1) — **korrigiert 20.09.2026, F-P5c-7:**
hier stand „Betrieb → Protokoll", den Block Betrieb sieht aber allein die
BetreiberIn, und E-P5c-02 gibt Admin und Support Reiter). **Eine** Seite,
**ein** Ort; was die Rolle nicht darf, zeigt die Seite nicht: Reiter oben
(nur die, die die Rolle sehen darf), Filter Zeitraum · Art · Konto · Text,
50 je Seite mit Blättern, jede Zeile: Zeit, Art, Urheber (Konto oder
`job`/`cli`), Betroffener, Text; `daten` aufklappbar. Kopf: der Zusagesatz
(E-P5c-01). **Nur für die BetreiberIn** zusätzlich: die Reiter Sicherheit,
Ziele und System, die Archivliste mit Kennung und Download, die
Kennungssuche (E-P5c-12) und der Verweis auf die Fristen (die Karte
„Protokoll" liegt in Betrieb → Servereinstellungen, E-P5c-24). Status →
Sicherheit bleibt als Kurzsicht mit dem Knopf „aufheben" und verweist auf
den Reiter Sicherheit.

**E-P5c-11 — Reiter Sicherheit liest `sicherheit_ereignisse`.** Die
P5a-Tabelle bleibt (Zusammenführung verworfen: sie trägt IPs mit eigener
Frist und den Sperrzustand, den die Leiter braucht); der Reiter liest sie
über eine Sicht `protokoll_sicht_sicherheit()` in derselben Zeilenform.
Ein Ereignis landet in genau einer der beiden Tabellen.

**E-P5c-12 — Fehlerprotokoll: ein Behandler, ein Reiter.**
`set_exception_handler()` und `set_error_handler()` in `db.php` (früh, vor
jeder Seite): jede unbehandelte Ausnahme und jeder Warnungsfehler wird mit
Kennung (acht Hex, wie `json_fehler()`), Datei, Zeile, Art und gekürzter
Meldung in den Reiter **System** geschrieben — **ohne** Anfragedaten, ohne
Sitzungsinhalt, ohne IP; die Fehlerseite zeigt die Kennung und den Satz
„Melde diese Kennung an <Kontaktadresse>" (R38). Die **77 `error_log()`-
Aufrufe** (1.1) werden auf `protokoll('system', …)` umgestellt (Paket 3 aus Nr.
202, hier erledigt); `error_log()` bleibt nur als Rückfall im Behandler
selbst, wenn die Datenbank nicht antwortet. Suche nach Kennung: Filterfeld
auf der Protokollseite, **nur BetreiberIn** (korrigiert 20.09.2026,
F-P5c-6: der Reiter System ist nach E-P5c-02 der BetreiberIn vorbehalten;
die Kontaktadresse auf der Fehlerseite ist die des Betriebs).

**E-P5c-13 — Ankündigungsbanner und Rundmail** (R38). `app_state`
`ankuendigung` = Text, Ton (info/warn), gültig bis; BetreiberIn setzt es
in Betrieb → Servereinstellungen; Baustein `.banner` unter der Kopfleiste
(derselbe wie E-P5c-05, das Umgebungsbanner steht darüber, wenn beide da
sind); Nutzerinnen können es wegklicken (Sitzung), es kommt beim nächsten
Anmelden wieder, bis es abläuft. **Rundmail:** Knopf „als Rundmail senden"
→ Warteschlange, an alle aktiven Konten, protokolliert (Verwaltung), mit
Sicherheitsabfrage und Vorschau der Empfängerzahl; höchstens eine je Tag.

**E-P5c-14 — Support-Rolle** (R38). `ROLLEN` um `support`; darf: Konten
und Geräte **sehen** (Metadaten, keine Einsätze), Setz-Link neu senden,
Verifikationsmail neu senden, Gerät deaktivieren, Reiter Verwaltung und
E-Mail lesen; darf nicht: einspielen, löschen, Stammdaten, Rollen,
Servereinstellungen, Rechtstexte. Prüfung an **einer** Andockstelle:
`rolle_darf_support()` neben `rolle_darf_verwalten()`; die
Verwaltungsseiten fragen je Handlung, nicht je Seite. Kontoseite des
Admins ergänzt Sperren/Entsperren mit Grund und „Verifikationsmail erneut
senden" — beides P5b-Bibliothek, hier nur Knöpfe.

**E-P5c-15 — Zweitfaktor: TOTP, Pflicht für Admin, BetreiberIn und
Support, Angebot für alle übrigen** (R38, Nr. 141; **F-P5c-1**). RFC 6238, SHA-1, 30 s,
sechs Ziffern, ±1 Fenster, Geheimnis 20 Byte versiegelt mit
`sk_versiegeln()` in `users.totp_geheimnis`; **zehn Wiederherstellungscodes**
(je acht Zeichen, als Hash), Anzeige einmalig, Druck über `.blatt-druck`.
Einrichtung: Betrieb-/Kontoseite, Geheimnis als **otpauth-URI und Base32
zum Abtippen** plus QR-Bild aus einer kleinen vendorierten Bibliothek
(MIT, `server/vendor/`, kein Netz). Pflicht: Konten mit Rolle admin,
betreiberin, support müssen ihn beim nächsten Anmelden einrichten (Tor wie
E-P5b-15); Nutzerinnen können ihn unter Einstellungen → Konto einschalten.
Abfrage nach Passwort, Topf `totp` mit der Sperrleiter; Verlust: Wieder-
herstellungscode oder — für Nutzerinnen — Reset mit Wiederherstellungs-
schlüssel setzt TOTP zurück; für Admins nur eine andere BetreiberIn.

**E-P5c-16 — Bus-Faktor** (R38): Betrieb → Status zeigt **orange**, wenn
weniger als zwei Konten mit Rolle admin oder betreiberin aktiv sind, und
**rot**, wenn nur eine BetreiberIn existiert (Schlüsselblatt-Rückfrage,
Freigaben, Rollenwechsel hängen an ihr). Kein Zwang, nur Sichtbarkeit.

**E-P5c-17 — Health-Endpunkt** (R38; **F-P5c-2**). `api/health.php`, nur
mit Token aus `config.php` (`betrieb.health_token`, leer = Endpunkt aus),
JSON: `ok`, `web_version`, `db` (erreichbar), `jobs_alter_s`, `wartung`,
`migration_ausstehend`, `protokoll_fehler_24h`, `speicher_pct`; HTTP 200
bei ok, 503 sonst; keine Konten-, Mengen- oder Hosterdaten; Ratenschutz
Topf `health` (60/min je IP). Kein Fremddienst wird angebunden — der
Betreiber trägt die URL in sein Monitoring ein.

**E-P5c-18 — Betriebslage-Dashboard, Minimalumfang, nicht mehr** (R38,
R42; Nr. 191, 192, 80 Rest). Betrieb → **Betriebslage** (neue Seite, oder
die umbenannte Statistik — entscheidet M-P5c-01): Konten gesamt / aktiv
24 h · 7 T · 30 T (`last_login` **oder** `devices.last_seen`); Einsätze
gesamt / 24 h · 7 T · 30 T · 6 M · 1 J nach `started_at`, ohne Demo, ohne
Papierkorb, mit **Index `missions(started_at)`** (Migration, Nr. 191);
Geräteverteilung je echtem Gerät nach Art und Modell, „unbekannt" für
Geräte ohne Kennung; **Herkunft je Einsatz** nach `origin` (R64:
Uhr/Handy/Formular/Import, Nr. 80 Rest) als Verteilung der letzten 30
Tage. Die S8-Statistik wird um diese Karten **ergänzt**, ihre eigene
Zählung heißt fortan sichtbar „Bestand" (Nr. 192: zwei Zählungen, zwei
Namen, eine Fußnote, was jede zählt). Alle Zahlen aus vorhandenen Spalten
(R36).

**E-P5c-19 — R39-Rest.** Nr. 168: die im Backlog gelisteten Überbleibsel
(Spalten, Konstanten, Doku-Sätze) entfallen mit einer Migration, die nur
löscht, was leer ist — und die vorher zählt, ob es leer ist (Torwächter-
Meldung, wenn nicht). Nr. 169: ein Diensttag mit „Anderem Rettungsmittel"
bekommt das Besatzungsfeld wie jeder andere; die Ausnahme in
`diensttag_form.php` entfällt, Kreisläufe prüfen.

**E-P5c-20 — Aufräumen der Einstellungen als ein Paket** (Nr. 244, 245,
246, 121, 198): Menü nach Mockup; Textüberarbeitung aller Verwaltungs- und
Betriebsseiten nach E-P5c-06 mit **Zählung** (Sätze je Karte vorher/nachher,
Handbuch-Verweise je Karte); Druckseiten nach E-P5c-08; Rechtstext-
Vorschau beim Tippen (Nr. 121: Vorschau rechts, gerendert wie die Seite,
über `doku_html()` aus P5b — Markdown erlaubt, sonst Text); Zeitraum-
Zählung Winde/Bergwacht (Nr. 198: Kacheln nach Typ, Mockup).

**E-P5c-21 — Bounce-Postfach** (Nr. 200; **F-P5c-3**). Einstellung
Empfohlen: `config.php` `mail.postfach` (IMAP, TLS, Zugang); Job
`bounces` liest ungelesene Nachrichten, erkennt Rückläufer an
`Auto-Submitted`/`Return-Path`/DSN-Struktur, ordnet über den
Nachrichtenschlüssel im Betreff (`[NAdoku #<id>]`, neu in
`mail_einreihen()`) der Warteschlange zu, zählt je Adresse; **drei**
Rückläufer → Konto `adresse_unzustellbar = 1`, Hinweis beim nächsten
Anmelden, Protokoll E-Mail. Ohne Postfach: nichts davon, keine Warnung.

**E-P5c-22 — Berechtigungsmatrix als Datei.** `docs/Technik.md` bekommt
eine Tabelle Rolle × Handlung (user, support, admin, betreiberin) — und
`tools/rollenprobe/` prüft sie: jede Handlung einmal je Rolle, erwartet
200/403 wie in der Tabelle. Ohne die Probe wäre die Support-Rolle eine
Behauptung. **Matrix und Probe entstehen in AP2** mit den drei bestehenden
Rollen (Reiter und Protokoll-Handlungen); **AP4 erweitert** beide um die
Rolle Support und die Verwaltungshandlungen (korrigiert 20.09.2026,
F-P5c-5).

**E-P5c-23 — Nr. 122 nach Schritt 17** (**F-P5c-4**): freie Zeiträume und
Diagramme sind Statistik-Komfort, nicht Betriebslage; 10c bleibt beim
Minimalumfang aus R38 („mehr ausdrücklich nicht").

**E-P5c-24 — Zwei Einstellungskarten, keine dritte.** Betrieb →
Servereinstellungen: Karte „Protokoll" (Archivtakt, Archivfrist,
Verwaltungsfrist, Versand des Archivs) und Karte „Ankündigung"; das
Umgebungsetikett und der Health-Token liegen in `config.php`, weil sie je
Installation gelten und nie aus der Oberfläche gesetzt werden sollen.

### 2.3 Ort je Funktion (K1, R74)

| Funktion | Ort |
|---|---|
| Protokoll mit Reitern und Filter (je Rolle); für die BetreiberIn dazu Archivliste, Download, Kennungssuche | Verwaltung → **Protokoll** (neu, F-P5c-7) |
| Fristen, Archivtakt, Archivversand, Ankündigung | Betrieb → **Servereinstellungen** (Karten „Protokoll", „Ankündigung") |
| Bus-Faktor, Protokoll-Fehlerzähler, Umgebungswarnung | Betrieb → **Status** |
| Betriebslage: Konten, Einsätze, Geräte, Herkunft | Betrieb → **Betriebslage** (M-P5c-01 entscheidet: neue Seite oder Statistik) |
| Support-Handlungen, Sperren, Verifikationsmail | Verwaltung → **Konten** / Kontoseite des Admins |
| TOTP einrichten, Wiederherstellungscodes, Notfallblatt | Einstellungen → **Konto**; Tor nach der Anmeldung für Pflichtrollen |
| Banner (Umgebung, Ankündigung) | jede Seite, unter der Kopfleiste |
| Health | `api/health.php` |
| Rechtstext-Vorschau | Verwaltung → **Rechtstexte** |
| Umgebung, Health-Token, Postfach | `config.php` |

### 2.4 Zum Gegenlesen — mit der Freigabe vom 20.09.2026 entschieden

| # | Festlegung | Die andere Wahl | Warum so |
|---|---|---|---|
| F-P5c-1 | TOTP **Pflicht für admin, betreiberin, support; Angebot für user** | Pflicht für alle (Nr. 141 wörtlich) | Nutzerinnen ohne Zweitgerät verlieren sonst den Zugang; ihre Daten sind E2E — der Angreifer mit Passwort sieht Klartextdaten, das Angebot deckt das; die Rollen mit Reichweite tragen die Pflicht |
| F-P5c-2 | Health nur mit Token, minimales JSON | offen ohne Token | ein offener Endpunkt verrät Version und Zustand jedem; Monitoring kann einen Token führen |
| F-P5c-3 | Bounce-Postfach als **letztes** Paket, Empfohlen | weglassen (Nr. 200 offen lassen) | die Unzustellbar-Liste zählt nur Versuche; Rückläufer sind der einzige Weg, tote Adressen zu erkennen; ohne Postfach ändert sich nichts |
| F-P5c-4 | Nr. 122 nach Schritt 17 | in 10c | R38: Minimalumfang, „mehr ausdrücklich nicht" |

### 2.5 Korrekturen nach der Freigabe (20.09.2026, Konzeptinstanz)

Beim Gegenlesen des Pakets am Abend des 20.09.2026 gefunden, am Stand
`862ca7f` nachgemessen. **Drei davon ändern Inhalt** — sie stehen als
F-Einträge, zur Kenntnis und zum Widerspruch:

| # | Was nicht stimmte | Festlegung | Die andere Wahl |
|---|---|---|---|
| F-P5c-5 | Die Abnahme von AP2 verlangte die Rollenprobe „4 Rollen × 7 Reiter" — Support-Rolle und `tools/rollenprobe/` entstanden aber erst in AP4. AP2 war so nicht abnehmbar | **Matrix und Rollenprobe entstehen in AP2** mit den drei bestehenden Rollen (3 × 7 = 21 Zellen); **AP4 erweitert** um Support (4 × 7 = 28) und die Verwaltungshandlungen | AP4 vor AP2 — scheidet aus, weil die Leserechte des Supports (Reiter Verwaltung, E-Mail) die Protokollseite aus AP2 brauchen |
| F-P5c-6 | E-P5c-12 gab die Kennungssuche „Admin und BetreiberIn"; nach E-P5c-02 (V8, Auftraggeber) sieht der Admin den Reiter System nicht | **Kennungssuche nur BetreiberIn**; die Fehlerseite nennt die Kontaktadresse des Betriebs | Admin bekommt den Reiter System — das änderte V8 und zeigte Dateipfade einer Rolle, die den Server nicht betreibt |
| F-P5c-7 | E-P5c-10 legte das Protokoll unter **Betrieb** ab. Den Block Betrieb sieht allein die BetreiberIn (R75; alle sieben `betrieb_*.php` verlangen `require_betreiberin()`) — Admin und Support hätten ihre Reiter nie erreicht | **Verwaltung → Protokoll**, eine Seite (`admin_protokoll.php`), Reiter und Werkzeuge nach Rolle; die Einstellkarte bleibt in Betrieb → Servereinstellungen | zwei Menüeinträge auf dieselbe Seite — widerspricht R74 („genau ein Ort") |

**Ohne Inhaltsänderung berichtigt:** Backlog-Nummern nach Weg A (242→243,
243→244, 244→245, 245→246; neu 248 und 249 aus Abschnitt 8 — die 240 hatte
die Kette-II-Instanz am selben Tag für einen eigenen Punkt vergeben); Verweis
in E-P5c-02 (Support ist E-P5c-14, nicht -05); Überschrift von E-P5c-15
(Pflicht auch für Support, wie Text und F-P5c-1); Zahl der
`error_log()`-Aufrufe (77 Aufrufe in 32 Dateien statt „83") und die Abnahme
von AP3, die mit einem blanken `grep -c` Kommentarzeilen mitgezählt hätte.

---

## 3. Arbeitspakete

### 3.0 Reihenfolge

AP1 zuerst (Staging-Banner, klein, wirkt sofort); dann **M-P5c-01**
(Fable); AP2 vor AP3 (der Reiter System braucht die Seite); AP2 vor AP4
(die Rollenprobe entsteht in AP2, AP4 erweitert sie — F-P5c-5); AP4 vor AP5
(Support-Rolle vor der Pflichtprüfung); AP6, AP7, AP8 unabhängig; AP9
nach M-P5c-01 und nach AP2/AP7 (Texte der neuen Seiten sind mit zu
überarbeiten); AP10 zuletzt; AP11 Abschluss.

### AP1 — Banner (E-P5c-05, -13)
Baustein `.banner`; Umgebungsetikett aus `config.php`; Ankündigung mit
Rundmail. **Abnahme:** Staging zeigt rote Kopfleiste und Präfix, Produktiv
nicht (zwei Bilderläufe); Statusseite warnt bei Präfix ohne Etikett;
Rundmail an ein Prüfkonto zugestellt, Protokolleintrag, zweite am selben
Tag verweigert; Kontrast Weiß auf Newroz-Rot ≥ 4,5:1 gemessen.

### AP2 — Protokollseite und Archiv (E-P5c-02, -03, -10, -11, -22; **nach M-P5c-01**)
`admin_protokoll.php` unter Verwaltung (F-P5c-7), Reiter, Filter, Rechte;
Berechtigungsmatrix in `Technik.md` und `tools/rollenprobe/` mit den drei
bestehenden Rollen (F-P5c-5); Job `protokoll_archiv`;
Komplett-Ausnahmen; Versandjob-Dateiart; Karte „Protokoll" in
Servereinstellungen. **Abnahme:** Rollenprobe (E-P5c-22) **3 Rollen × 7
Reiter = 21 Zellen**, 0 Abweichungen (user sieht die Seite nicht, Admin vier
Reiter, BetreiberIn sieben; Archivliste, Download und Kennungssuche nur
BetreiberIn); Archivlauf mit gestelltem Datum: ZIP enthält je Reiter eine
Datei, **0 IP-Felder** (grep), Kennung im Namen; Download entsiegelt und
protokolliert; Komplett-Backup enthält `protokoll_ereignisse`, **nicht**
`sicherheit_ereignisse`/`rate_limits` (Tabellenliste im Prüfdokument);
Archiv nach 366 Tagen (gestellt) gelöscht; Kreislauf edbak unverändert.

### AP3 — Fehlerprotokoll (E-P5c-12)
Behandler in `db.php`; 77 Aufrufe umgestellt; Fehlerseite mit Meldeweg;
Kennungssuche (nur BetreiberIn, F-P5c-6). **Abnahme:** `error_log(`-
**Aufrufe** in `server/` → **≤ 2** (Behandler-Rückfall) — gezählt **ohne
Kommentare und Zeichenketten**; das Zählmittel entsteht hier und geht in
Stufe 1 (Nr. 248). Ein blankes `grep -c` zählt Kommentarzeilen mit und
bliebe nach der Umstellung über 2, sobald ein Kommentar die Herkunft nennt; eine provozierte Ausnahme erscheint im Reiter System
mit Kennung, die Fehlerseite zeigt dieselbe Kennung; Sitzungs-, Anfrage-
und IP-Daten fehlen im Eintrag (grep im Dump).

### AP4 — Support-Rolle und Konto-Knöpfe (E-P5c-14, -22)
Rolle, Andockstelle, Verwaltungsseiten je Handlung, Kontoseite-Knöpfe;
Berechtigungsmatrix und `tools/rollenprobe/` aus AP2 **um die Rolle Support
und die Verwaltungshandlungen erweitert** (F-P5c-5). **Abnahme:**
Rollenprobe grün mit Zahl (Reiter **4 × 7 = 28 Zellen**, dazu Handlungen ×
Rollen); Support erreicht Verwaltung → Protokoll und sieht genau zwei Reiter; Support kann Setz-Link
senden und Gerät deaktivieren, aber nicht löschen (403).

### AP5 — Zweitfaktor (E-P5c-15, -16)
TOTP-Bibliothek (eigene, RFC 6238 ist klein), QR vendoriert,
Wiederherstellungscodes, Tor für Pflichtrollen, Kontoseite, Topf `totp`,
Bus-Faktor auf Status. **Abnahme:** Prüfvektoren aus RFC 6238 Anhang B
(6/6); Einrichtung mit einer Authenticator-App im Browser-Prüfstand;
Wiederherstellungscode einmal gültig, zweimal nicht; Pflichtrolle ohne
TOTP landet am Tor; Bus-Faktor-Ampel bei 1/2/3 Konten.

### AP6 — Health (E-P5c-17)
Endpunkt, Token, Ratenschutz. **Abnahme:** ohne Token 404, falscher 403,
richtiger 200 mit JSON-Feldern; Wartung an → 503; 61 Aufrufe in einer
Minute → 429.

### AP7 — Betriebslage (E-P5c-18; **nach M-P5c-01**)
Migration Index; Zählung; Karten; Herkunft; Statistik als „Bestand".
**Abnahme:** Zahlen gegen den Referenzdatensatz nachgerechnet (SQL im
Prüfdokument), Demo und Papierkorb ausgeschlossen (Differenz benannt);
`EXPLAIN` zeigt den neuen Index; Messstand: Seite < 1 s bei 5 000 Einsätzen.

### AP8 — R39-Rest (E-P5c-19)
Migration mit Vorzählung; Diensttag-Besatzung. **Abnahme:** Migration auf
dem Referenzbestand: 0 gelöschte Zeilen mit Inhalt (Vorzählung im
Protokoll); Kreisläufe 0 unerklärt; Nr. 168-Liste abgehakt.

### AP9 — Aufräumen der Einstellungen (E-P5c-06, -07, -08, -20; **nach M-P5c-01**)
Design.md-Regel; Menü; Textüberarbeitung mit Zählung; Handbuch-Nachträge;
Druckseiten; Rechtstext-Vorschau; Zeitraum-Zählung. **Abnahme:** Zählung
vorher/nachher je Seite (Sätze je Karte ≤ 1; Verweise ≥ 1 je Karte mit
ausgelagertem Text); PDF-Druck beider Blätter in Chromium und Firefox = 1
Seite; Bilderlauf 8 Breiten 0/0/0; Wortliste 0/0/0.

### AP10 — Bounce-Postfach (E-P5c-21)
Job, Zuordnung, Vermerk. **Abnahme:** Versandprobe mit einem Rückläufer
aus dem eigenen Postfach: Zuordnung zur Warteschlangen-Id, Zähler +1;
dritter → Vermerk und Hinweis beim Anmelden; ohne `mail.postfach` → Job
übersprungen mit Meldung.

### AP11 — Abschluss
Kreisläufe, Wortliste, Bilderlauf, Rollenprobe, Prüfdokument (K9),
Handbuch-Kapitel Protokoll/Zweitfaktor/Betriebslage, Einschübe, Merge nach
Freigabe.

---

## 4. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| Rollenprobe (neu) | AP2, AP4, AP5, AP11 | Matrix vollständig, 0 Abweichungen |
| Kreisläufe csv/edbak | AP2, AP8, AP11 | 0 unerklärt |
| Bilderlauf (alle Seiten + Protokoll, Betriebslage) | AP1, AP9, AP11 | 0/0/0; Staging rot, Produktiv blau |
| Versandprobe | AP1, AP10 | Rundmail 1/1; Rückläufer zugeordnet |
| Messstand | AP7 | Betriebslage < 1 s bei 5 000 |
| RFC-6238-Vektoren | AP5 | 6/6 |
| Zählung Erklärtext | AP9 | Sätze je Karte ≤ 1, Verweise ≥ 1 |
| Zählung `error_log(`-Aufrufe (ohne Kommentare und Zeichenketten) | AP3, danach Stufe 1 | ≤ 2 |
| Wortliste | jedes Paket | 0/0/0 |

**Nicht prüfbar, so gesagt:** echtes externes Monitoring (nur `curl`);
Rückläufer fremder Provider (nur eigenes Postfach); Schlüsselwechsel-Fall
beim Archiv (Schritt 18; hier nur die Kennungs-Meldung mit gefälschtem
Namen).

## 5. Gesammelte Fehlerfunde (K4)

| # | Fund | Wie weiter |
|---|---|---|
| F1 | `error_log()`-Aufrufe seit 16.09. von 42 auf 77 gestiegen (32 Dateien, nachgemessen 20.09.2026) — jedes P5-Paket brachte eigene | AP3 stellt alle um; Prüftor Stufe 1 bekommt die Zählung (Soll ≤ 2, Nr. 248) |
| F2 | Nr. 192: S8-Statistik und R38 zählen Verschiedenes | AP7: zwei Namen, eine Fußnote |
| F3 | Notfallblatt (P5b AP9) ohne Logo und ohne Seitengrenze | AP9, E-P5c-08 |
| F4 | Dieses Konzept selbst: Protokoll unter Betrieb abgelegt, obwohl Admin und Support es lesen sollen; Rollenprobe in AP2 verlangt, in AP4 gebaut; Kennungssuche gegen V8 | berichtigt am 20.09.2026 nach der Freigabe — Abschnitt 2.5 |

## 6. Fable-Schritt der Umsetzung

**M-P5c-01 — Mockup-Runde Betrieb** (vor AP2): (a) Verwaltung → Protokoll
(F-P5c-7) mit Reitern, Filter, Zeile — **in zwei Sichten**: Admin (vier
Reiter, ohne Archiv) und BetreiberIn (sieben Reiter, Archivliste,
Kennungssuche) (Desktop 1440, Handy 400);
(b) Betriebslage-Karten und die Entscheidung Seite/Statistik;
(c) Einstellungen-Übersicht mit Überschriftenzeile je Bereich — und die
Variante eigene Karten, damit die Entscheidung am Bild fällt; der neue
Eintrag „Protokoll" steht dabei unter Verwaltung;
(d) Rechtstext-Vorschau; (e) Zeitraumübersicht mit Winde/Bergwacht;
(f) Schlüsselblatt und Notfallblatt als eine A4-Seite mit Marke.
Eine Runde, ein Dokument, wie `konzept-p5b/mockups/`. Hinweisen und
pausieren (K8); nach Freigabe keine weitere Pause.

## 7. Einschub Rahmenplan (mit der Freigabe; Fassung und Nummern vergibt die einspielende Instanz)

- Fahrplanzeile 10c: Konzept „liegt vor: `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`
  (Fable, 20.09.2026, E-P5c-01 bis -24, F-P5c-1 bis -7, AP1–AP11, ein
  Fable-Schritt)"; Status „**Konzept freigegeben 20.09.2026**, Umsetzung
  offen"; Voraussetzung „Schritte 16 und 15".
- R38 Status: „(alle Punkte) in 10c, E-P5c-13 bis -18; Zweitfaktor
  konkretisiert E-P5c-15"; R39 „Rest in 10c AP8"; R42 „Auswertung in 10c
  AP7".
- Abschnitt 5: 80 Rest, 121, 141, 168, 169, 191, 192, 198, 200, 243–246,
  248 → „10c AP…" mit Paketnummer; **122 → Schritt 17** (E-P5c-23);
  **249 → Schritt 18**.
- Abschnitt 6: Zuarbeit „`betrieb.health_token` und ggf. `mail.postfach`
  in `config.php` beider Anlagen" — vor AP6/AP10. **Nicht Teil des
  Einschubs:** Die Erklärtext-Regel (E-P5c-06) kommt als Grundregel 6.x in
  `docs/Design.md` — mit AP9, nicht beim Einspielen.
- Abschnitt 10: eine Zeile.

## 8. Einschub Backlog (Nummern nach dem Einschub vom 20.09.2026, Weg A)

- **Neu, Nr. 248:** `set_exception_handler()`-Behandler im Prüftor Stufe 1
  mitprüfen (Zählung der `error_log(`-Aufrufe ≤ 2, ohne Kommentare) — mit
  AP3 erledigt, Eintrag als Prüfmittel-Vermerk. Zuordnung: 10c AP3.
- **Neu, Nr. 249:** TOTP-Reset durch eine zweite BetreiberIn — der Fall
  „einzige BetreiberIn verliert Zweitgerät und Codes" ist ein
  Wiederanlauf-Fall (S10-Runbook), nicht 10c; hier nur benannt. Zuordnung:
  Schritt 18 (Sicherheitsrunde II).
- Nr. 80, 121, 141, 168, 169, 191, 192, 198, 200, 243–246: „in 10c AP…";
  Nr. 122: „Schritt 17".
