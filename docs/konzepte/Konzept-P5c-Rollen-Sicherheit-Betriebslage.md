# Konzept P5c — Rollen, Sicherheit, Betriebslage

**Rahmenplan:** Schritt 10c (R82), R10, R25, R31, R36, R38, R39, R42, R64,
R81, R83; Einschub vom 20.09.2026 (Abschnitt D). **Backlog:** 80 (Rest),
121, 141, 168, 169, 191, 192, 200, 243, 244, 245, 246; aus Abschnitt 8
neu 248, 249 (Nummern nach dem Einschub vom 20.09.2026, Weg A). **Nicht hier:**
122 (Diagramme in der Statistik → Schritt 17), 228 (nur auf Anlass);
**198 wird nicht umgesetzt** (E-P5c-27, entschieden in M-P5c-01).
**Vorbereitung:** `Vorbereitung-P5c-Protokollierung.md` (V1–V9 entschieden,
16./20.09.2026); Gestaltungsvorgaben des Auftraggebers vom 17. und
20.09.2026 (Konzept P5b Abschnitt 6; Erklärtext-Regel).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2); **ein Fable-Schritt**
(Mockup-Runde M-P5c-01, Abschnitt 6) — **erledigt am 20.09.2026**, vor der
Umsetzung gezogen. **Ablage:** dieses
Dokument, Prüfdokument daneben (`Pruefdokument-P5c-Rollen-Sicherheit-Betriebslage.md`,
entsteht mit AP1), Mockups in `konzept-p5c/mockups/`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **Freigegeben (Auftraggeber, 20.09.2026).** E-P5c-01 bis -09 aus den Gesprächen (V1–V9, Banner, Erklärtext, Druckseiten); E-P5c-10 bis -24 aus dem Nachmessen. **Am selben Tag nach der Freigabe korrigiert** (Abschnitt 2.5): Nummern nach Weg A, drei inhaltliche Stellen (F-P5c-5 bis -7), Verweise und Zahlen. **Mockup-Runde M-P5c-01 durchgeführt und freigegeben 20.09.2026** (V1 bis V3 am selben Tag, Abschnitt 6): E-P5c-07, -08, -10, -18, -20 danach neu gefasst, **E-P5c-25 bis -30 neu** (Abschnitt 2.6). |
> | Entschieden | E-P5c-01 bis E-P5c-30 |
> | Offen | nichts — die Mockup-Runde ist vollständig entschieden (zuletzt die Form der Übersicht, 20.09.2026). **F-P5c-1 bis -4** gelten mit der Freigabe. **F-P5c-5 bis -7** (2.5) sind Korrekturen der Konzeptinstanz **nach** der Freigabe — zur Kenntnis und zum Widerspruch; F-P5c-7 (Ort des Protokolls) wird im Mockup M-P5c-01 ohnehin sichtbar |
> | Umsetzung | nicht begonnen; **nach Schritt 16 und Schritt 15** (Einschub 20.09.2026). Reihenfolge AP1 → AP2 … AP11 (3.0); die Mockup-Runde liegt bereits vor |
> | Fable-Schritte der Umsetzung | **einer, erledigt 20.09.2026**: Mockup-Runde M-P5c-01 (Protokoll, Statistik, Menü und Übersicht, Rechtstext-Vorschau, Druckseiten; die Zeitraum-Zählung ist entfallen). In der Umsetzung selbst bleibt **kein** Fable-Schritt |
> | Nummern | keine Rahmenplan-Fassung aus diesem Konzept. Backlog **243–246, 248, 249** stehen im Einschub vom 20.09.2026 (Weg A: feste Nummern, die Kette-II-Instanz reserviert 241–249 im Backlog-Kopf, bevor sie einspielt); Abschnitt 7 und 8 übernimmt die einspielende Instanz — **beide sind am 20.09.2026 nach der Mockup-Runde fortgeschrieben** (198, 192, 244, Fahrplanzeile); ist der Einschub schon eingespielt, sind diese Zeilen nachzutragen. **Abgeglichen mit `Konzept-Zentralisierung.md` (Schritt 15, freigegeben 20.09.2026):** der eine Satz in AP3 steht hier bereits in der dort verlangten Fassung; 3.0 verweist auf die Andockstellen |

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
seinem festen Minimalumfang (in der Oberfläche heißt es **„Statistik"** —
E-P5c-18; „Betriebslage" bleibt der Arbeitsname aus R38), der Rest von R39, und — nach Entscheidung des
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
  aus P5a hält sie aktuell. **Nachgetragen in M-P5c-01:** Die Auswertung
  gibt es seit S8 bereits — Karten „Geräte" und „Gerätemodelle" in
  `betrieb_statistik.php`, je echtem Gerät (`device_id NOT LIKE 'manual-%'`),
  mit „Ohne Angabe". **Herkunft je Einsatz** (`origin`, R64) wird nirgends
  ausgewertet — Nr. 80 Rest; `HERKUNFT_WERTE` (`geraete_lib.php`) hat **sechs**
  Werte: `watch`, `android`, `wear`, `manual`, `import`, `schnitt`.

### 1.3 Einstellungen, Texte, Druck

- `ui_einstellungen_uebersicht()` (`ui.php` Z. 855 ff.): drei Bereiche
  (Einstellungen, Verwaltung, Betrieb) als Listen; die Bereichsnamen sind
  Zwischenüberschriften ohne Gewicht — Nr. 244. **Berichtigt in M-P5c-01:**
  Gemeint war mit Nr. 244 vor allem das **linke Menü**
  (`ui_leiste_einstellungen()`). Ursache dort, nachgemessen: Überschrift und
  Eintrag stehen beide in Bricolage 15 px; die Überschrift soll 600 gegen 400
  wiegen, Bricolage ist aber nur in **500 und 600** eingebunden —
  `font-weight:400` fällt auf 500. Dazu steht der Winkel der Überschrift
  dort, wo die Einträge ihr Symbol tragen. Die Übersicht trägt heute eine
  gesperrte Versalzeile in 12 px, gestapelt am ersten Bereich ausgeblendet.
- Erklärtext: die Verwaltungs- und Betriebsseiten tragen je Karte zwei bis
  sechs Sätze (Zählung folgt in AP9 als Ausgangszahl); das Handbuch ist seit
  P5b AP8 aus der Anwendung erreichbar (`hilfe.php#abschnitt`), wird von den
  Karten aber nirgends verlinkt — Nr. 245.
- `betrieb_schluesselblatt.php` (S10) und `notfallblatt.php` (P5b AP9):
  Fließtext, kein Logo, Umbruch auf zwei Seiten bei langen Werten — Nr. 246.
- Rechtstexte: **berichtigt in M-P5c-01** — `admin_rechtstexte.php` ist seit
  S8 nur eine Weiterleitung; bearbeitet wird unter Verwaltung →
  **Installation** (`admin_installation.php`), vier Karten in der rechten
  Spalte eines Zweispalters. Eine Vorschau gibt es dort seit Web 9.11.0
  (unter dem Feld, **gespeicherter** Stand, `rt_html()`); es fehlt das
  Mitlaufen beim Tippen — Nr. 121. `rt_html()` kennt Überschriften, Absätze,
  Listen und Verweise, **kein Fett**.
  Zeitraumübersicht zählt Winde bodengebunden nicht — Nr. 198; **wird nicht
  umgesetzt** (E-P5c-27).
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
**Entschieden in M-P5c-01 (20.09.2026):** das **linke Menü** — das mit
Nr. 244 vor allem gemeint war — bekommt Bereichsüberschriften nach
**Option 1 „Linie"**; die **Übersicht** bekommt je Bereich eine eigene Karte
mit **Bereichszeichen und mittigem Kopf**. Einzelheiten: E-P5c-29.

**E-P5c-08 — Druckseiten** (Nr. 246): Schlüsselblatt und Notfallblatt mit
einem Baustein `.blatt-druck` — Marke + **Kurzname der Installation**
(`instanz_kurz()`, Vorgabe „Gen-EM NAdoku"; **entschieden 20.09.2026** — hier
stand ein festes „NAdoku", das auf einer umbenannten Installation falsch
wäre) und Überschrift oben,
Schlüssel in einer Kachel (Rahmen, Rauch, Feste Schrift, Vierergruppen),
**genau eine A4-Seite** (`@page A4`, feste Ränder, `page-break-inside:
avoid`), Abnahme per PDF-Druck aus zwei Browsern. Ändert E-P5b-09.
**Freigegeben 20.09.2026** wie M-P5c-01f; Einzelheiten: E-P5c-30.

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
den Reiter Sicherheit. **Freigegeben 20.09.2026 wie M-P5c-01a, mit drei
Abweichungen von diesem Text** (ein Suchfeld statt der vier Filter, Archiv
als abgesetzter Reiter, Plakette am Handy unter dem Text) — E-P5c-26.

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
R42; Nr. 191, 192, 80 Rest). **Neu gefasst nach M-P5c-01 (20.09.2026).**
Hier stand: „neue Seite Betriebslage oder die umbenannte Statistik"; die
S8-Statistik werde „ergänzt", ihre Zählung heiße „Bestand". **Entschieden:**
kein achter Eintrag unter Betrieb und kein Name „Betriebslage" in der
Oberfläche — die Seite heißt weiter **„Statistik"**
(`betrieb_statistik.php`) und bekommt **drei Reiter** (Baustein E-P5c-25):
**NutzerInnen · Einsätze · Geräte** (`?r=nutzer|einsaetze|geraete`). Über den
Reitern bleiben die vier Kennzahlen; jede führt in ihren Reiter. Jeder
Reiter hat dieselbe Form — links die Tabelle „… je Zeitraum", rechts die
Karte „was es gibt" (Zeilen mit Zählplakette); gestapelt steht die Tabelle
zuerst. Layout: `.form-raster-links-breit` (3 : 2 ab 1200 px, **neue
Layoutregel**) — gleich geteilt lief die Fünf-Fenster-Tabelle zwischen 1200
und 1300 px über.
- **NutzerInnen:** „Konten je Zeitraum" (24 h · 7 Tage · 30 Tage · 6 Monate):
  **Aktiv** (`last_login` **oder** `devices.last_seen` im Fenster — R38),
  Angemeldet, Neu angelegt; rechts „Konten" nach Rolle und „Ohne Gerät" (S8).
- **Einsätze:** „Einsätze je Zeitraum" (24 h · 7 Tage · 30 Tage · 6 Monate ·
  1 Jahr): Einsätze, NutzerInnen mit Einsatz, Ø je NutzerIn mit Einsatz;
  rechts **„Herkunft der Einsätze"** nach `origin`, letzte 30 Tage, **alle
  sechs** Werte aus `HERKUNFT_WERTE`, auch mit 0 (hier standen vier).
- **Geräte:** die S8-Karten unverändert („Geräte", „Geräte je Zeitraum",
  „Gerätemodelle") — die Geräteverteilung aus R42 ist damit erfüllt, ohne
  dass etwas Neues entsteht.

**Eine Zählung (entschieden 20.09.2026):** Einsätze zählen auf dieser Seite
**ab Beginn des Einsatzes** (`missions.started_at`, Pflichtfeld), ohne Demo,
ohne Papierkorb, mit **Index `missions(started_at)`** (Migration, Nr. 191);
die S8-Zeilen rechnen auf derselben Grundlage. Die Zählung nach Diensttag
entfällt hier, damit auch der Name „Bestand" — **Nr. 192 erledigt sich.**
Gründe: Das 24-Stunden-Fenster geht nur so (ein Diensttag hat ein Datum,
keine Uhrzeit); `day_id` darf leer sein, `started_at` nicht; zwei fast
gleiche Zahlen brauchen zwei Namen, und der zweite klingt immer technisch.
**Preis:** Die Zahlen der BetreiberIn können um einzelne Einsätze von der
Summe der NutzerInnen-Statistiken abweichen (die zählen nach Diensttag); der
Satz unter der Karte sagt, wie gezählt wird. Alle Zahlen aus vorhandenen
Spalten (R36).

**E-P5c-19 — R39-Rest.** Nr. 168: die im Backlog gelisteten Überbleibsel
(Spalten, Konstanten, Doku-Sätze) entfallen mit einer Migration, die nur
löscht, was leer ist — und die vorher zählt, ob es leer ist (Torwächter-
Meldung, wenn nicht). Nr. 169: ein Diensttag mit „Anderem Rettungsmittel"
bekommt das Besatzungsfeld wie jeder andere; die Ausnahme in
`diensttag_form.php` entfällt, Kreisläufe prüfen.

**E-P5c-20 — Aufräumen der Einstellungen als ein Paket** (Nr. 244, 245,
246, 121): Menü und Übersicht nach E-P5c-29; Textüberarbeitung aller
Verwaltungs- und Betriebsseiten nach E-P5c-06 mit **Zählung** (Sätze je
Karte vorher/nachher, Handbuch-Verweise je Karte); Druckseiten nach
E-P5c-08 und -30; Rechtstext-Vorschau beim Tippen nach **E-P5c-28** (eigene
Seite, Vorschau rechts). **Berichtigt nach M-P5c-01:** Hier standen
`doku_html()` (die Funktion heißt `rt_html()`) und die Zeitraum-Zählung
Winde/Bergwacht (Nr. 198) — sie **entfällt**, E-P5c-27.

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
| Betriebslage: Konten, Einsätze, Geräte, Herkunft | Betrieb → **Statistik**, drei Reiter NutzerInnen · Einsätze · Geräte (E-P5c-18; kein neuer Menüeintrag) |
| Support-Handlungen, Sperren, Verifikationsmail | Verwaltung → **Konten** / Kontoseite des Admins |
| TOTP einrichten, Wiederherstellungscodes, Notfallblatt | Einstellungen → **Konto**; Tor nach der Anmeldung für Pflichtrollen |
| Banner (Umgebung, Ankündigung) | jede Seite, unter der Kopfleiste |
| Health | `api/health.php` |
| Rechtstexte bearbeiten, mit Vorschau beim Tippen | Verwaltung → **Rechtstexte** (eigene Seite, neu — E-P5c-28); Verwaltung → Installation behält Name, Adressen, Logo |
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

### 2.6 Aus der Mockup-Runde M-P5c-01 (E-P5c-25 bis -30, 20.09.2026)

Die Bilder und ihre Anmerkungen liegen in `konzept-p5c/mockups/`; das
`LIESMICH.md` dort führt Stand, Messwerte und die vorgeschlagenen Klassen.
**Maßgeblich für die Umsetzung ist das Bild**, dieser Abschnitt hält die
Entscheidungen fest.

**E-P5c-25 — Baustein „Reiter"** (`.reiter`, `.reiter-punkt`,
`.reiter-abgesetzt`, `.reiter-rahmen`; neuer Abschnitt in `Design.md`
Kapitel 9). Wechsel zwischen gleichrangigen Sichten **einer** Seite;
serverseitig, jeder Reiter ist ein Verweis. Orange Unterstreichung wie in
der Kopfleiste („hier stehst du"). Unter 720 px rollt die Reihe in ihrem
eigenen Behälter, mit Verlauf am Rand; die Anwendung holt den aktiven Reiter
beim Laden ins Bild. **Drei Verwender:** Protokoll (AP2 — dort entsteht der
Baustein), Statistik (AP7), Rechtstexte (AP9). Geprüft und verworfen:
Segment (für wenige kurze Möglichkeiten) und Filterpillen (stehen eine Zeile
tiefer als Filter). **Seiten mit Reitern tragen keine Unterpunkte in der
Leiste** (entschieden 20.09.2026): `menue.js` baut keine, wenn `#inhalt`
eine `.reiter`-Reihe enthält — die Reiter sind die Gliederung der Seite.

**E-P5c-26 — Protokollseite im Einzelnen** (freigegeben wie M-P5c-01a).
**Ein Suchfeld** für Text, Konto (E-Mail von Urheber oder Betroffenem) und —
nur BetreiberIn — die achtstellige Fehlerkennung; eine Kennung wechselt von
jedem Reiter auf System. Der Konto-Filter per Kennnummer bleibt für Verweise
von der Kontoseite (`?konto=…`) und erscheint als entfernbare Plakette.
**Zeitraum** als Filterpillen (24 h · 7 Tage · 30 Tage, im Reiter Verwaltung
dazu 365 Tage — die letzte Pille ist die Frist), **Art** als Auswahlfeld.
**Archiv** als abgesetzter Reiter am rechten Rand (kein Ereignis-Reiter —
E-P5c-02 bleibt bei sieben), nur BetreiberIn; passt die Kennung des
Serverschlüssels nicht, sagt es eine Meldung und der Download ist gesperrt;
der Knopf steht **ohne Symbol** (`einsatz.php` hält fest, dass der Vorrat
bewusst keines für „herunterladen" hat). **Zeile:** `.zeile` mit Plakette =
Art (Wort, nicht Schlüssel — `protokoll_lib.php` bekommt einen Katalog
Art → Beschriftung und Ton: neutral, blau, orange, rot); Zeilen mit `daten`
sind ein `<details>` (`.zeile-mehr`), der Winkel steht in der Aktionsspalte,
Zeilen ohne Daten behalten die leere Spalte (E-P5c-09). **Unter 720 px**
rückt die Plakette unter den Text — die eine Abweichung von „rechtsbündig
in einer Spalte", gemessen begründet (daneben blieben dem Haupttext 170 px).
Support sieht die Seite wie der Admin, mit zwei Reitern.

**E-P5c-27 — Nr. 198 wird nicht umgesetzt** (entschieden 20.09.2026, nach
dem Bild M-P5c-01e): bodengebunden keine Windenanzeige, auch nicht bei
Bergwacht-Diensttagen. Die Bodenansicht der Zeitraumübersicht bleibt, wie sie
ist; `api/range.php` bleibt unverändert. **Folge, damit sie dasteht:**
Winden-Cycles bodengebundener Bergwacht-Diensttage erscheinen in keiner
Ansicht der Zeitraumübersicht. Das Mockup (e) ist aus der Ablage entfernt.

**E-P5c-28 — Rechtstexte: eigene Seite, Vorschau rechts** (Nr. 121;
entschieden 20.09.2026 gegen „unter dem Feld"). `admin_rechtstexte.php` wird
wieder zur Seite (die Adresse gibt es als Weiterleitung, „weil sie in
Lesezeichen steht"); Menüeintrag **Rechtstexte** unter Verwaltung zwischen
Installation und Demo-Konto, Zeichen `rechtstexte` (liegt im Vorrat).
Reiter je Text (Impressum · Datenschutzerklärung · Nutzungsbedingungen ·
AVV); ab 1200 px links Feld und Stand, rechts die Karte „Vorschau", darunter
gestapelt. Die Vorschau rollt in sich, ist so hoch wie das Feld und rollt
beim Rollen im Feld anteilig mit. **Renderer bleibt `rt_html()` auf dem
Server** (kein zweiter im Browser, E-P3-38): Endpunkt
`api/rechtstext_vorschau.php` — POST, nur Admin und BetreiberIn, CSRF,
`no-store`, Ratenschutz; Abruf 0,4 s nach dem letzten Tastendruck, laufende
Abrufe werden verworfen. Zustand als Plakette („gespeicherter Stand", „wird
aktualisiert …", „ungespeichert"), im Fehlfall eine Meldung. Ohne Skript
bleibt die Vorschau des gespeicherten Stands. Reiterwechsel mit
ungespeichertem Text: `forms.js` fragt nach. Verwaltung → **Installation**
behält Name, Adressen, Logo. **Preis:** eine neue Seite und ein sechster
Eintrag unter Verwaltung — AP9 wird größer; der Endpunkt gehört in die
Berechtigungsmatrix (E-P5c-22).

**E-P5c-29 — Menü und Übersicht** (Nr. 244, entschieden 20.09.2026).
**Leiste, Option 1 „Linie":** der Winkel der Bereichsüberschrift wandert nach
rechts (die linke Spalte gehört den Symbolen der Einträge), Überschrift eine
Stufe größer (`--groesse-4`) in Dunkelblau, Trennlinie und `--abstand-3`
Luft vor jedem weiteren Bereich. Geändert werden **nur** Regeln an
`.leiste-gruppe`; Markup, Aufklappen und das Merken des Zustands bleiben,
die Diensttage-Leiste (derselbe Akkordeon-Baustein) bleibt unberührt.
Verworfen: Option 2 „Band" (Überschrift auf Sand). **Übersicht:** je Bereich
eine eigene Karte, der Bereichsname ist der Kartentitel mit Zahl der
Einträge (`ui_karte_start(['titel' => …, 'zahl' => …, 'klasse' =>
'karte-bereich'])`), in jeder Breite auch am ersten Bereich;
`.uebersicht-block` und `.uebersicht-block-erst` entfallen. **Entschieden
20.09.2026 (Vorschlag K2 aus acht, Überschriften mittig):** Der Kartenkopf
trägt ein rundes **Bereichszeichen** (`.bereich-zeichen`: `--knopf` im
Durchmesser, Dunkelblau, Symbol in `--auf-dunkel`), den Bereichsnamen und
die Zahl der Einträge — **als Gruppe mittig** (`justify-content:center`) —
und liegt auf Rauch. Zeichen: `profil` (Einstellungen — ich), `gruppe`
(Verwaltung — die Konten), `server` (Betrieb — die Anlage); dass sie je einen
Eintrag darunter doppeln, ist in Kauf genommen. `ui_karte_start()` bekommt
die Option **`symbol`**; in `Design.md` 9.1 entsteht die Variante
**„Bereichskarte"** (`.karte-bereich`) — gedacht für diese Seite, ohne
Kopfaktion (mittig hätte sie keinen Platz). Die **Leiste bleibt
linksbündig.** Kontraste gerechnet: Zeichen 13,6 : 1, Titel auf Rauch
12,5 : 1, Zahl auf Rauch 5,3 : 1. **Der Weg dahin:** Überschriftenzeile
(V1) verworfen zugunsten eigener Karten; getönte Köpfe in Sand, Dunkelblau
und Hellblau (V2, V3) — Hellblau und Dunkelblau verworfen. Befund dahinter:
`Design.md` kannte genau eine Form des Kartenkopfs und keinen Flächenton
hinter ihm; Farbe trägt in der Oberfläche überall Bedeutung, ein getönter
Kopf liest sich als Meldung oder als Kopfleiste. Aus acht Vorschlägen ohne
bedeutungstragende Farbe (K1 Akzentkante, K2 Bereichszeichen, K3
Karteireiter, K4 typografisch; N1 freie Liste, N2 Kacheln, N3 eine Karte mit
drei Spalten, N4 Reiter) fiel die Wahl auf K2. Neuer Eintrag
**„Protokoll"** unter Verwaltung (Zeichen `protokoll`, Tabler „list", Pfad
aus der Tabler-Quelle).

**E-P5c-30 — Druckblatt im Einzelnen** (freigegeben wie M-P5c-01f). Kopf:
farbige Bildmarke + `instanz_kurz()` in Bricolage, rechts Adresse und
Druckzeit (beim Notfallblatt dazu das Konto). Das **Schlüsselblatt** nimmt
den Logo-Standard der Installation, das **Notfallblatt** die Wahl des Kontos
— ohne Sitzung (erstes Setzen des Passworts) den Standard. Kachel: Rahmen in
Dunkelblau trägt die Aussage, Rauch ist Zugabe (Browser drucken Flächen in
der Vorgabe nicht; `@media print` nimmt sie weg). **Nummerierte Gruppen**
(zweimal acht beim Schlüsselblatt, fünf beim Notfallblatt) — die
Quartalsrückfrage E-P5b-10 fragt nach Gruppennummern. „Wozu" steht an der
jeweiligen Kachel, „Wohin" in der Warnung. **Erklärtext-Regel mit Grenze:**
Das Schlüsselblatt wird gebraucht, wenn `config.php` weg ist — drei Zeilen
bleiben auf dem Blatt, der Verweis nennt zusätzlich `docs/Handbuch.md` im
Repositorium. Fußzeile mit **Webversion** (`WEB_VERSION`) auf beiden
Blättern. `@page` A4, Ränder 14 mm / 18 mm. **Gemessen (Chromium, ohne
Hintergrundgrafiken):** Schlüsselblatt im ungünstigsten Fall mit drei Werten
90 % des Satzspiegels, mit 83 Zeichen Kurzname und langer Adresse 96 %;
Notfallblatt 68 %; je eine Seite. Papiermaße in mm liegen außerhalb der
Tokenskala — mit freigegeben.

---

## 3. Arbeitspakete

### 3.0 Reihenfolge

**M-P5c-01 liegt vor** (20.09.2026, vor der Umsetzung gezogen — wie bei
P5b). AP1 zuerst (Staging-Banner, klein, wirkt sofort); AP2 vor AP3 (der
Reiter System braucht die Seite); **AP2 vor AP7 und AP9** (der Baustein
„Reiter" entsteht in AP2, E-P5c-25); AP2 vor AP4
(die Rollenprobe entsteht in AP2, AP4 erweitert sie — F-P5c-5); AP4 vor AP5
(Support-Rolle vor der Pflichtprüfung); AP6 und AP8 unabhängig; AP9
nach AP2/AP7 (Texte der neuen Seiten sind mit zu
überarbeiten); AP10 zuletzt; AP11 Abschluss.

**Was 10c nach Schritt 15 vorfindet** (nachgetragen 20.09.2026; Quelle:
`Konzept-Zentralisierung.md`, freigegeben 20.09.2026, **Abschnitt 6
„Andockstellen für 10c"** — maßgeblich ist der Stand dort nach dessen AP10).
Die Tabelle dort nennt je 10c-Paket, welche Helfer bestehen (`konfig()`,
`sitzung_starten()`, `api_eingang()`, `format_lib.php`, `db_transaktion()`,
`flash_setzen()` …). **Drei Punkte ändern den Zuschnitt hier:**
(1) **AP3 baut kein Zählmittel** — siehe dort (E-ZE-03).
(2) **AP2 löst `zip_lib.php` heraus** (R83: das Protokoll-Archiv ist der
zweite Verbraucher von `ZipArchive`; `adminbackup_lib.php` zieht im selben
Paket um, die Registerzeile legt 10c an).
(3) **AP9 entscheidet den Datum-Zeit-Trenner** (F-ZE-3, FF-5; eigener
Backlog-Eintrag aus dem Schritt-15-Einschub): Nach Schritt 15 steht er an
einer Stelle (`datum_zeit_text()`); heute gibt es Leerzeichen (25), ` · ` (11)
und Komma (1). **Zu beachten:** Die freigegebenen Mockups M-P5c-01a
(Protokollzeile) und -01f (Druckblätter) setzen „TT.MM.JJJJ, HH:MM" mit
**Komma**, weil ` · ` in der Kleinzeile bereits die Angaben trennt („Zeit ·
Urheber · Betroffener") — das ist ein Beitrag zur Entscheidung, keine
Vorwegnahme.

### AP1 — Banner (E-P5c-05, -13)
Baustein `.banner`; Umgebungsetikett aus `config.php`; Ankündigung mit
Rundmail. **Abnahme:** Staging zeigt rote Kopfleiste und Präfix, Produktiv
nicht (zwei Bilderläufe); Statusseite warnt bei Präfix ohne Etikett;
Rundmail an ein Prüfkonto zugestellt, Protokolleintrag, zweite am selben
Tag verweigert; Kontrast Weiß auf Newroz-Rot ≥ 4,5:1 gemessen.

### AP2 — Protokollseite und Archiv (E-P5c-02, -03, -10, -11, -22, -25, -26; Bild: M-P5c-01a, freigegeben)
`admin_protokoll.php` unter Verwaltung (F-P5c-7); **Baustein „Reiter"** mit
Abschnitt in `Design.md` und der Bedingung in `menue.js` (E-P5c-25);
Suchfeld, Zeitraum-Pillen, Art-Auswahl, `.zeile-mehr`, Katalog Art →
Beschriftung und Ton (E-P5c-26); Rechte;
Berechtigungsmatrix in `Technik.md` und `tools/rollenprobe/` mit den drei
bestehenden Rollen (F-P5c-5); Job `protokoll_archiv`;
Komplett-Ausnahmen; Versandjob-Dateiart; Karte „Protokoll" in
Servereinstellungen; `zip_lib.php` herausgelöst (R83, siehe 3.0). **Abnahme:** Rollenprobe (E-P5c-22) **3 Rollen × 7
Reiter = 21 Zellen**, 0 Abweichungen (user sieht die Seite nicht, Admin vier
Reiter, BetreiberIn sieben; Archivliste, Download und Kennungssuche nur
BetreiberIn); Archivlauf mit gestelltem Datum: ZIP enthält je Reiter eine
Datei, **0 IP-Felder** (grep), Kennung im Namen; Download entsiegelt und
protokolliert; Komplett-Backup enthält `protokoll_ereignisse`, **nicht**
`sicherheit_ereignisse`/`rate_limits` (Tabellenliste im Prüfdokument);
Archiv nach 366 Tagen (gestellt) gelöscht; Kreislauf edbak unverändert;
Bilderlauf der Seite bei 1440 und 400 px gegen M-P5c-01a (Zeilenhöhe
aufklappbarer und fester Zeilen gleich — der Fund `.zeile:first-child`,
Abschnitt 5 F6); Seite mit Reitern ohne Unterpunkte in der Leiste.

### AP3 — Fehlerprotokoll (E-P5c-12)
Behandler in `db.php`; 77 Aufrufe umgestellt; Fehlerseite mit Meldeweg;
Kennungssuche (nur BetreiberIn, F-P5c-6). **Abnahme:** `error_log(`-
**Aufrufe** in `server/` → **≤ 2** (Behandler-Rückfall) — gezählt **ohne
Kommentare und Zeichenketten**; das Zählmittel besteht seit Schritt 15
(`tools/zaehlung/`, Zeile Z38); AP3 setzt dessen Decke auf 2 (Nr. 248) —
*berichtigt 20.09.2026 nach E-ZE-03, `Konzept-Zentralisierung.md` Abschnitt 9;
hier stand „das Zählmittel entsteht hier und geht in Stufe 1".* Ein blankes `grep -c` zählt Kommentarzeilen mit und
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

### AP7 — Statistik mit drei Reitern (E-P5c-18; Bild: M-P5c-01b V3, freigegeben)
Migration Index `missions(started_at)`; `betrieb_statistik.php` mit den
Reitern NutzerInnen · Einsätze · Geräte; **eine** Zählung ab Beginn des
Einsatzes; „Aktiv" nach R38; Herkunft mit sechs Werten; Layoutregel
`.form-raster-links-breit`; der Satz unter der Einsatzkarte und der
Handbuch-Abschnitt sagen, wie gezählt wird. **Abnahme:** Zahlen gegen den
Referenzdatensatz nachgerechnet (SQL im Prüfdokument), Demo und Papierkorb
ausgeschlossen (Differenz benannt); Differenz zur alten Diensttag-Zählung
einmal ausgewiesen und erklärt; `EXPLAIN` zeigt den neuen Index; Messstand:
**jeder Reiter** < 1 s bei 5 000 Einsätzen; Tabellen ohne Überlauf in
`.tabelle-scroll` bei 1200, 1280, 1366 und 1440 px; Betrieb hat weiter
sieben Einträge.

### AP8 — R39-Rest (E-P5c-19)
Migration mit Vorzählung; Diensttag-Besatzung. **Abnahme:** Migration auf
dem Referenzbestand: 0 gelöschte Zeilen mit Inhalt (Vorzählung im
Protokoll); Kreisläufe 0 unerklärt; Nr. 168-Liste abgehakt.

### AP9 — Aufräumen der Einstellungen (E-P5c-06, -07, -08, -20, -28, -29, -30; Bilder: M-P5c-01c, -01d, -01f)
Design.md-Regel (Erklärtext) und die neuen Bausteine (Bereichskarte
`.karte-bereich` mit `.bereich-zeichen` und der Option `symbol` in
`ui_karte_start()`, `.blatt-druck`, `.vorschau-kopf`/`.vorschau-rollt`); Leiste nach Option 1;
Übersicht mit Bereichskarten (E-P5c-29); Textüberarbeitung mit Zählung;
Handbuch-Nachträge (samt den Sprungmarken, auf die Blätter und Karten
verweisen); Druckseiten; **Seite Rechtstexte** mit Vorschau-Endpunkt, Umzug
der vier Karten aus „Installation"; Datum-Zeit-Trenner entschieden und an
der einen Stelle gesetzt (F-ZE-3, siehe 3.0). *Die Zeitraum-Zählung ist entfallen
(E-P5c-27); dafür ist die Rechtstext-Seite dazugekommen — das Paket ist
damit größer als bei der Freigabe des Konzepts.* **Abnahme:** Zählung
vorher/nachher je Seite (Sätze je Karte ≤ 1; Verweise ≥ 1 je Karte mit
ausgelagertem Text); PDF-Druck beider Blätter in Chromium und Firefox = 1
Seite, das Schlüsselblatt **mit drei Werten**; Kontrast des Kartenkopfs und
der Reiterschrift mit `tools/screenshots/kontrast.py` gemessen (≥ 4,5 : 1
für Schrift); Diensttage-Leiste im Bilderlauf **unverändert** (die
Leistenregeln hängen nur an `.leiste-gruppe`); Rollenprobe um den
Vorschau-Endpunkt erweitert (Admin und BetreiberIn 200, sonst 403);
Vorschau ohne Skript = gespeicherter Stand; Lesezeichen
`admin_rechtstexte.php` führt auf die neue Seite; Bilderlauf 8 Breiten
0/0/0; Wortliste 0/0/0.

### AP10 — Bounce-Postfach (E-P5c-21)
Job, Zuordnung, Vermerk. **Abnahme:** Versandprobe mit einem Rückläufer
aus dem eigenen Postfach: Zuordnung zur Warteschlangen-Id, Zähler +1;
dritter → Vermerk und Hinweis beim Anmelden; ohne `mail.postfach` → Job
übersprungen mit Meldung.

### AP11 — Abschluss
Kreisläufe, Wortliste, Bilderlauf, Rollenprobe, Prüfdokument (K9),
Handbuch-Kapitel Protokoll/Zweitfaktor/Statistik, Einschübe, Merge nach
Freigabe.

---

## 4. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| Rollenprobe (neu) | AP2, AP4, AP5, AP11 | Matrix vollständig, 0 Abweichungen |
| Kreisläufe csv/edbak | AP2, AP8, AP11 | 0 unerklärt |
| Bilderlauf (alle Seiten + Protokoll, Statistik mit drei Reitern, Rechtstexte) | AP1, AP2, AP7, AP9, AP11 | 0/0/0; Staging rot, Produktiv blau; Diensttage-Leiste unverändert |
| Versandprobe | AP1, AP10 | Rundmail 1/1; Rückläufer zugeordnet |
| Messstand | AP7 | Statistik: jeder Reiter < 1 s bei 5 000 |
| Kontrastmessung neuer Flächen (`kontrast.py`) | AP2, AP9 | Schrift ≥ 4,5 : 1 (Reiter, Kartenkopf) |
| PDF-Druck der Blätter | AP9 | je 1 Seite in Chromium und Firefox; Schlüsselblatt mit drei Werten |
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
| F2 | Nr. 192: S8-Statistik und R38 zählen Verschiedenes | **erledigt sich** mit AP7: eine Zählung ab Beginn des Einsatzes (E-P5c-18, entschieden 20.09.2026) — hier stand „zwei Namen, eine Fußnote" |
| F3 | Notfallblatt (P5b AP9) ohne Logo und ohne Seitengrenze | AP9, E-P5c-08 |
| F4 | Dieses Konzept selbst: Protokoll unter Betrieb abgelegt, obwohl Admin und Support es lesen sollen; Rollenprobe in AP2 verlangt, in AP4 gebaut; Kennungssuche gegen V8 | berichtigt am 20.09.2026 nach der Freigabe — Abschnitt 2.5 |
| F5 | Dieses Konzept selbst, gefunden in M-P5c-01 am Stand `862ca7f`: `doku_html()` gibt es nicht (`rt_html()`); „Verwaltung → Rechtstexte" war eine Weiterleitung, eine Vorschau gab es schon; vier Herkünfte genannt, sechs vorhanden; die Geräteverteilung als neu geführt, sie steht seit S8; Nr. 244 auf die Übersicht gelesen, gemeint war die Leiste | berichtigt am 20.09.2026 — 1.2, 1.3, E-P5c-18, -20, -29 |
| F6 | `.zeile:first-child{padding-top:0}` trifft jedes `<summary class="zeile">`, weil es erstes Kind seines `<details>` ist — aufklappbare Zeilen waren 13 px niedriger als feste (54 gegen 67 px, im Mockup gemessen) | Gegenregel im Vorschlag von M-P5c-01a; mit AP2 ins Stylesheet |
| F7 | Bricolage ist nur in 500 und 600 eingebunden; `font-weight:400` im Stylesheet (u. a. `.eintrag-text`) wirkt als 500. Kein Darstellungsfehler, aber eine Angabe, die nicht tut, was sie sagt — und die Ursache von Nr. 244 | in AP9 bei der Leiste berücksichtigt; Vermerk in `Design.md` (Typografie), sonst kein Handlungsbedarf |

## 6. Fable-Schritt der Umsetzung — erledigt am 20.09.2026

**M-P5c-01 — Mockup-Runde Betrieb.** Auf Anforderung des Auftraggebers
**vor** der Umsetzung gezogen (wie M-P5b-01/-02); gebaut gegen `862ca7f` mit
den echten `ui_*`-Funktionen und dem echten `rt_html()`. Drei Fassungen am
selben Tag (V1, V2 nach der ersten Durchsicht, V3 nach der zweiten). Ablage
`konzept-p5c/mockups/`, ein Dokument: das `LIESMICH.md` dort.

| Mockup | Ergebnis |
|---|---|
| (a) Protokoll, Sichten Admin und BetreiberIn, 1440 und 400 | **freigegeben** unverändert („genau so") — E-P5c-25, -26 |
| (b) Betriebslage | V1 (eigene Seite oder eine Seite „Betriebslage") **verworfen**; V3 **freigegeben**: Statistik mit drei Reitern, eine Zählung, keine Unterpunkte in der Leiste — E-P5c-18 |
| (c) Menü-Gliederung | **entschieden und freigegeben (V4):** Leiste Option 1 „Linie"; Übersicht mit Bereichskarten — rundes Bereichszeichen, Kopf mittig, auf Rauch (K2 aus acht Vorschlägen) — E-P5c-29. Verworfen: Überschriftenzeile, getönte Köpfe (Hellblau, Dunkelblau), Option 2 „Band" |
| (d) Rechtstext-Vorschau | **entschieden:** eigene Seite, Vorschau rechts — E-P5c-28 |
| (e) Zeitraumübersicht mit Winde | **entfällt** — Nr. 198 wird nicht umgesetzt, E-P5c-27; Dateien entfernt |
| (f) Schlüsselblatt und Notfallblatt | **freigegeben**, mit Webversion in der Fußzeile — E-P5c-08, -30 |

In der Umsetzung bleibt **kein** Fable-Schritt; die frühere Regel „Hinweisen
und pausieren (K8)" ist damit gegenstandslos.

## 7. Einschub Rahmenplan (mit der Freigabe; Fassung und Nummern vergibt die einspielende Instanz)

- Fahrplanzeile 10c: Konzept „liegt vor: `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`
  (Fable, 20.09.2026, E-P5c-01 bis -30, F-P5c-1 bis -7, AP1–AP11, ein
  Fable-Schritt — Mockup-Runde M-P5c-01, erledigt und freigegeben
  20.09.2026)"; Status „**Konzept freigegeben 20.09.2026**, Umsetzung
  offen"; Voraussetzung „Schritte 16 und 15".
- R38 Status: „(alle Punkte) in 10c, E-P5c-13 bis -18; Zweitfaktor
  konkretisiert E-P5c-15"; R39 „Rest in 10c AP8"; R42 „Auswertung in 10c
  AP7".
- Abschnitt 5: 80 Rest, 121, 141, 168, 169, 191, 192, 200, 243–246,
  248 → „10c AP…" mit Paketnummer; **122 → Schritt 17** (E-P5c-23);
  **249 → Schritt 18**; **198 → „wird nicht umgesetzt, entschieden
  20.09.2026 (E-P5c-27)"**; 192 → „erledigt sich mit 10c AP7 (eine
  Zählung)".
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
- Nr. 80, 121, 141, 168, 169, 191, 192, 200, 243–246: „in 10c AP…";
  Nr. 122: „Schritt 17".
- **Nr. 198: schließen als „nicht umsetzen — entschieden 20.09.2026
  (E-P5c-27): bodengebunden keine Windenanzeige, auch nicht bei
  Bergwacht-Diensttagen"**, mit der Folge im Eintrag (Winden-Cycles
  bodengebundener Bergwacht-Diensttage erscheinen in keiner Ansicht der
  Zeitraumübersicht).
- **Nr. 244: Text berichtigen** — gemeint ist vor allem das linke Menü, die
  Übersicht zieht mit (E-P5c-29); der Backlog-Nachtrag vom 18.09.2026 ist
  entsprechend fortgeschrieben.
- Nr. 192: mit AP7 als „erledigt sich" führen (eine Zählung statt zweier
  Namen).
