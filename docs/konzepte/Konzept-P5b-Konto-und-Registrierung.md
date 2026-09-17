# Konzept P5b — Konto und Registrierung

**Rahmenplan:** Schritt 10b (R82), R9, R25, R31, R36, R37 (1)–(7), (10), (11),
R41, R83; V1 und V2 der P5c-Vorbereitung. **Backlog:** 37 (Speichergrenzen
je Konto), 48, 200 (bleibt offen, Abschnitt 2.2), 202 Paket 1 (Token, hier
mit erledigt — Abschnitt 2.2).
**Vorbereitung:** das Gespräch vom 16.09.2026 (Fragen 1–7 und zwei
Nachträge, E-P5b-01 bis -10); `Vorbereitung-P5c-Protokollierung.md` (V1,
V2 entschieden — der Schreibweg entsteht hier); `Vorbereitung-P5-
Plattformprofil.md` (PP-3: jeder Job läuft Huckepack).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2); **zwei
Fable-Schritte** in der Umsetzung (Abschnitt 6). **Ablage:** dieses
Dokument, das Prüfdokument daneben
(`Pruefdokument-P5b-Konto-und-Registrierung.md`, entsteht mit AP1),
Mockups in `konzept-p5b/mockups/`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 16.09.2026 — **Konzept freigegeben** (Auftraggeber, 16.09.2026, ohne Änderungen). E-P5b-01 bis -10 aus dem Gespräch, E-P5b-11 bis -22 aus dem Nachmessen, E-P5b-23 und -24 aus den erledigten Zuarbeiten. **Nachtrag 17.09.2026:** E-P5b-25 (Abschnitt 2.5) — private Zweckbestimmung, die AVV bleibt, Versuchsanwendung verworfen. |
> | Entschieden | E-P5b-01 bis E-P5b-25 (Abschnitt 2) |
> | Offen | nichts im Konzept; **außerhalb und nicht mehr in der Liste der Umsetzung:** die anwaltliche Prüfung der drei Rechtstext-Entwürfe samt Rechtsform der Betreiberin (E-P5b-24, E-P5b-25) — der Auftraggeber führt diese Zuarbeit ab 17.09.2026 selbst |
> | Umsetzung | **läuft** auf `claude/magical-dirac-we2y1z` seit 16.09.2026 — begonnen auf dem Stand von `main` nach dem Merge von P5a (PR #50, `14f99ac`). **Sieben von zehn Paketen sind gebaut** (Tabelle unten), AP8, AP9 und AP10 stehen aus. **Abweichend vom Fahrplan läuft 10b vor Schritt 15**: Der Auftraggeber hat die Umsetzung am 16.09.2026 unmittelbar nach dem P5a-Merge beauftragt; **Schritt 15 ist unverändert offen**. Welche Folgen das für die Zentralisierung hat, ist hier nicht gemessen — sie fasst dieselben Dateien an und misst ohnehin nach dem Merge neu (Rahmenplan, Fahrplanzeile 15). Reihenfolge und Abhängigkeiten innerhalb von 10b sonst wie in 3.0 |
> | Fable-Schritte der Umsetzung | **erledigt** — beide Mockups am 17.09.2026 geliefert und **freigegeben** (`konzept-p5b/mockups/`, M-P5b-01 V1, M-P5b-02 a–d V1/V1.1). Die Umsetzung von AP3, AP8 und AP9 braucht **keine Pause** mehr; die vier Gestaltungsvorgaben in Abschnitt 6 gelten |
> | Nummern | **Vergeben am 17.09.2026** beim Einspielen dieses Pakets: Rahmenplan **Fassung 78**, Backlog **Nr. 220 bis 222** (Proof-of-Work, Uhr-Grund bei `403`, Pflege der Wegwerfliste). Die Nummern 215 bis 219 hatte derselbe Zweig zuvor für Funde während der Umsetzung vergeben; 220 war damit die nächste freie. **Die Einschübe aus Abschnitt 7 und 8 sind eingearbeitet** — Fahrplanzeile 10b, Schritt-10-Block, Abschnitt 5, 6 und das Register R9/R25/R37 im Rahmenplan; im Backlog die drei neuen Punkte **220 bis 222**, der Speichergrenzen-Absatz an **Nr. 37** und die Verschiebung von **Nr. 48** nach *Erledigt*; **Nr. 202** trug seine Statuszeile schon seit AP2, **Nr. 200** bleibt unverändert. **Nr. 37 bleibt offen** — von ihr ist nur der Speichergrenzen-Teil gefallen, der Rest (Zeitraumübersicht, Suchindex, die sechs stillen Kappungen, `post_max_size`) steht. **Nachtrag 17.09.2026:** Backlog **Nr. 223** (Einwilligung bei der Registrierung nicht festgehalten) zu E-P5b-25 |

> **Stand der Umsetzung** (17.09.2026, Zweig `claude/magical-dirac-we2y1z`)
>
> | Paket | Stand | Version | Commit | Abnahmezahlen |
> |---|---|---|---|---|
> | AP1 — Protokoll-Schreibweg, Einstellungen | **erledigt** | Web 20.16.0 | `b9046ed` | Bereinigung 3 von 3 Fristfällen richtig; Fehlfall V7 in allen drei Stufen belegt; Bilderlauf 16 Bilder 0/0/0. **Der `app_state`-Teil entfiel** — `app_state_lesen()`/`_setzen()` stehen seit Web 20.7.0 in `db.php` |
> | AP2 — Lebenszyklus-Bibliothek | **erledigt** | Web 20.17.0 | `442dbcb` | Übergangstabelle statt Verzweigungen, 6 von 6 Übergängen wie festgelegt; `INSERT INTO password_resets` **4 Dateien → 2 Treffer in einer**; `ingest.php` 200/403 je Status; Ratenschutz zählt die Absage **0**mal. Backlog Nr. 202 Paket 1 mit erledigt |
> | AP3 — Registrierung | **erledigt** | Web 20.22.0 | `93f0d6f` | Honeypot, Mindestausfülldauer 4 s, drei Rätsel-Töpfe; Wegwerfliste **8 von 8** Anbietern, **0 von 10** echten Domains; immer dieselbe Antwort. **20.22.1** berichtigte den Wortlaut der Häkchen, **20.22.2** hält sie fest (Nr. 223) |
> | AP4 — Einwilligungen | **erledigt** | Web 20.19.0 | `edc3040` | 6 von 6 Fällen, darunter **zweite Fassung am selben Tag sperrt erneut** (Fehlerfund F3 des Konzepts); drei Wege bleiben am Tor offen, im Browser durchgeklickt; Ingestprobe bei leerer `konto_einwilligungen` **83 Erwartungen, 0 nicht erfüllt** |
> | AP5 — Selbstlöschung, Adresswechsel | **erledigt** | Web 20.20.0 | `edc3040` | Karenz **30,0 Tage**, Rückzug lässt den Bestand unverändert, Kaskade räumt alle Reste; Adresswechsel 6 von 6 Fällen — alte Adresse gilt bis zum Klick, zweiter Klick abgewiesen, belegte Adresse abgewiesen; Protokolleintrag enthält **kein `@`** |
> | AP6 — Mengengrenze, Aufbewahrung je Konto | **erledigt** | Web 20.21.0 | `533850d` | Uploads `200, 200, 200, 507`; Rumpf `{"error":"kontingent"}`; Ratenzähler **0 Zeilen**; nach Anheben kam die abgewiesene Aufzeichnung nach (**4 Einsätze**). Backlog Nr. 48: 4 Fälle, 4 bestanden. **E-P5b-17 ist gegenstandslos** — SHA-256 für Geräteschlüssel seit Web 13.0.0 erledigt, am Code nachgemessen |
> | AP7 — Demo-Anmeldung als Einstellung | **erledigt** | Web 20.18.0 | `ad93c9e` | Im Browser gemessen, nicht mit curl: drei Anmeldeversuche 1241 / 1270 / 1263 ms, **dieselbe Meldung**, Spanne **29 ms** (Soll < 50) |
> | AP8 — Handbuch, „Was ist NAdoku“ | **erledigt** | Web 20.23.0 | `d3832e4` | Handbuch 266 KB → 305 KB HTML in **11–12 ms**; **67** Verzeichniseinträge = 13 h2 + 54 h3 (Zählung der Datei); **79** Überschriften mit eindeutiger Sprungmarke; **11 von 11** Sicherheitsproben abgewehrt; Bilderlauf **32 Bilder, 0/0/0**. Drei Funde beim Prüfen, alle eigene — Bildpfade, 43 Tabellen, und ein Prüfmittel, das auf den Falschen zeigte |
> | AP9 — Onboarding und Rückfragen | **erledigt** | Web 20.24.0 | _(dieser Commit)_ | Die Abnahme ist der Schlüsselwechsel, im Browser gemessen: zweimal erneuert, dann **Code B gegen die neue Hülle: öffnet**, **Code A gegen die neue Hülle: öffnet nicht (OperationError)**, **Code A gegen seine alte Hülle: öffnet** — der Fehlschlag belegt damit die Ersetzung und nicht einen kaputten Erzeuger. Rundenzählung: `rueckfrage_naechste` 2027-03-17 = **6 Monate**, Runde **1**, verschoben **0**. Betreiber-Rückfrage: Positionen je Anzeige neu, Fehlversuch zählt (Meldung „Noch 2 Versuche"), Frage bleibt unverändert, Großschreibung angenommen, `schluesselblatt_bestaetigt_am` gesetzt, Sicherheitseintrag **ohne Werte**. Bilderlauf **24 Bilder, 0 Überlauf / 0 Konsolenfehler / 0 Knopfhöhen**; Wortliste **0 Treffer in 5 Bereichen**; Vollständigkeit **398** (+10, alle in drei neuen Dateien nachgezählt) |
> | AP10 — Abschluss | offen | — | — | — |
>
> **Wo es hakt: nirgends mehr im Haus.** Die Mockup-Freigabe vom 17.09.2026 hat
> die einzige offene Abhängigkeit aufgelöst; AP3 ist seither gebaut, AP8 und
> AP9 sind ohne weitere Pause umsetzbar. **Außerhalb** bleibt die anwaltliche Prüfung der drei
> Rechtstext-Entwürfe (E-P5b-24) — AP4 steht deshalb mit Platzhaltern da, und
> das ist der geplante Zustand, kein Rest. Die Entwürfe sind am 17.09.2026 nach
> E-P5b-25 überarbeitet worden (private Zweckbestimmung, § 203 StGB,
> Klartextliste ehrlich gestellt); **geprüft sind sie weiterhin nicht**.
>
> **Nr. 223 ist erledigt** (Web 20.22.2, 17.09.2026): Die Registrierung hält
> die Einwilligungen jetzt fest und verlangt nur, was in Kraft ist. Fünf
> Prüffälle gegen die lokale Installation gemessen, dabei ein Folgefehler in
> der Absage gefunden und behoben — Einzelheiten im Prüfdokument, F11.
>
> **AP8 ist gebaut** (Web 20.23.0). Eine Abweichung vom Konzept ist dabei
> gefallen und steht in E-P5b-22 nachgetragen: **der Cache entfällt.**
>
> **Und ein Fehler in meiner eigenen Arbeitsweise**, der hier stehen bleibt:
> Commit `51a4930` ist gepusht worden, **ohne die Vollständigkeitsprüfung zu
> fahren** — nur die Wortliste lief. Dieser Stand liegt bei **394** und
> wäre im Prüftor Stufe 1 durchgefallen (Schwelle 388). Aufgefallen ist es
> erst beim nächsten Paket. Prüfdokument F12.
>
> **Fünf Migrationen bisher** (AP3 bringt keine dazu) — `2026_09_16_protokoll_ereignisse`,
> `_konto_lebenszyklus`, `_einwilligungen`, `_adresswechsel_bestaetigt` und
> `_konto_grenzen` (nachgezählt in `migration_lib.php`; die Zahl stand hier
> zuerst auf vier, der Adresswechsel aus AP5 fehlte). **`update.php` ist
> fällig**, sonst stehen die neuen Karten leer da.
>
> **Sechs Fehlerfunde während der Umsetzung**, alle im Prüfdokument als F4 bis
> F9 belegt; vier davon haben eine Backlog-Nummer bekommen:
>
> | Fund | Was | Nummer | Behoben in |
> |---|---|---|---|
> | F4 | P5a/AP4 hatte die Anwendung **uninstallierbar** gemacht (`install.php` → HTTP 500) | Nr. 215 | Web 20.15.3 |
> | — | `frame-ancestors` stand in einer Report-Only-Richtlinie und war dort wirkungslos — **16 Konsolenfehler bei 16 Bildern** in WebKit | Nr. 216 | Web 20.16.0 |
> | F5 | **Backlog Nr. 48 falsch gelesen** — als Aufbewahrungsfrist für Einsätze statt als Zahl der Sicherungspakete; berichtigt, bevor es ausgeliefert wurde | — | vor Web 20.21.0 |
> | F6 | Die Profilseite brach aus ihrem Seitengerüst aus — vier Karten über die volle Fensterbreite, **zehn Tage lang**, und der Bilderlauf meldete in allen drei Engines drei Nullen | Nr. 217 | Web 20.21.1 |
> | F7 | Ein Meldungskasten trug den Ton `meldung-blau`, den es nicht gibt — weißer Kasten ohne Symbol | Nr. 218 | Web 20.21.1 |
> | F8 | Zwei Seiten fehlten in der Gerüst-Ausnahmeliste — „Seite ohne Gerüst" **2 → 0**, Ausnahmen 7 → 9 | — | Web 20.21.1 |
> | F9 | Die Symbolregel zählt Typografie — **299 von 319 Befunden sind `…` und `→`**; die Schwelle stand in zwei Dokumenten verschieden (366 gegen 377) | Nr. 219 | Web 20.21.1 |
>
> **Dreimal geprüft, nicht einmal.** Seit der Rückfrage des Auftraggebers vom
> 16.09.2026 läuft der Bilderlauf über **drei** Maschinen (Chromium, Firefox,
> WebKit). Nr. 216 ist genau daran aufgefallen und wäre unter Chromium allein
> nie sichtbar geworden.

---

## 0. Auftrag und Umfang

**Anlass.** Zweiter Teil von P5 (R82): alles, was ein Konto ist — wie es
entsteht, was es darf, wie es endet — und die Tür dazu, die
Registrierung. Dazu, weil 10b vor 10c läuft und dessen Ereignisse erzeugt,
der **Schreibweg des Protokolls** (V1: Betriebsereignisse, keine
Datenzugriffe). Und, aus dem Gespräch vom 16.09.2026 dazugekommen: das
**Handbuch und eine Seite „Was ist NAdoku"** als gerenderte, öffentliche
Seiten (E-P5b-08), das **Onboarding** mit Notfallblatt und den
**Besitz-Rückfragen** (E-P5b-09, -10).

**Was dieses Konzept liefert:** Befund je Thema (nachgemessen am
P5a-Zweig `claude/butte-umsetzen-5opi9u`, Stand 16.09.2026, Web 20.8.0 —
nicht an `main`, weil 10b auf dem Ergebnis von 10a aufsetzt), die
Festlegungen als E-Einträge, zehn Arbeitspakete mit Abnahmekriterien,
Prüfprotokoll-Soll, zwei Mockup-Aufträge, den Entwurf „Was ist NAdoku"
(Abschnitt 9), die Einschübe für Rahmenplan und Backlog.

**Was es nicht liefert:**

- **10c**: Support-Rolle, Zweitfaktor für alle (Nr. 141), Audit-Sicht,
  Protokoll-**Oberfläche** mit Reitern, Archiv und Download (V4–V9),
  Ankündigungsbanner, Fehlerprotokoll, Health, Dashboard, R39-Rest.
  10b baut den **Schreibweg** des Protokolls und schreibt hinein; lesbar
  wird es mit 10c. Bis dahin zeigt Betrieb → Status eine Zählkarte
  (Einträge je Reiter, letzte 24 h) — mehr nicht.
- Die **anwaltliche Prüfung** der Rechtstexte (R41): Entwürfe liegen vor
  (E-P5b-24), geprüft sind sie nicht — AP4 baut die Mechanik mit
  Platzhaltern, bis die geprüften Fassungen eingespielt sind.
- **Uhr- und Android-Änderungen**, außer der `507`-Antwort der
  Mengengrenze (E-P5b-04), die beide Clients wie `429` als „später
  erneut" behandeln (P5a-Konzept 1.7) — gemessen, keine Client-Stufe.
- **Proof-of-Work im Browser** (R37 (4) „notfalls"): nicht gebaut;
  Backlog-Eintrag mit Auslöser (Abschnitt 8).
- **Bounce-Postfach** (Nr. 200): bleibt offen; die Unzustellbar-Liste aus
  P5a AP5 ist der Zwischenstand.

**Kein Versionssprung im Konzept** (K3).

---

## 1. Befund (P5a-Zweig, 16.09.2026)

### 1.1 Konto heute

- `users`: `id`, `email`, `name`, `password_hash`, `kdf_salt`, `kdf_iter`,
  `pat_wrap_pw`, `pat_wrap_rc`, `pat_key_check`, `role`, `session_epoch`,
  `account_key`, `logo_wahl`, `adresssuche`, `last_login`, `created_at`.
  **Kein Status**, kein `bestaetigt_am`, kein `gesperrt_seit`, keine
  Einwilligungen, keine Karenz. Rollen über `rolle_normieren()` (`db.php`);
  BetreiberIn-Prüfung `rolle_ist_betreiberin()`.
- **Anlegen nur per Einladung** (`admin_users.php`): Konto + Kontokennung
  + Setz-Token in **einer** Transaktion (seit E17, mit dem Kommentar zum
  „halben Zustand" davor). Token 32 Byte, `password_resets(user_id,
  token_hash, expires_at, used_at)`. Dieselbe Transaktion steht in
  `admin_user.php`, `reset_request.php`, `install.php` noch einmal — das
  ist Backlog 202 Paket 1 (Token); **10b löst es mit der
  Lebenszyklus-Bibliothek** (E-P5b-11), weil die Selbstregistrierung sonst
  die fünfte Fassung würde.
- **E-Mail-Wechsel** in `einstellungen.php`: `email_pruefen()`, dann
  `UPDATE` — ungeprüft, keine Bestätigung, keine Hinweismail (R37 (6)).
- **Löschen** in `admin_user.php`: hartes `DELETE` mit Kaskade, mit
  Entscheidung über die Admin-Backups (E25); keine Selbstlöschung, keine
  Karenz.
- **Wiederherstellungsschlüssel**: entsteht im Browser beim Setzen des
  Passworts (`pw_handling.php`), einmalige Anzeige, Haken „abgelegt";
  Reset ohne ihn ändert nichts (`crypto.js` `recoveryKeyHex`,
  `newRecoveryCode`). Kein Notfallblatt, keine Rückfrage, kein
  Erneuerungsweg außer dem Reset. Das Muster für ein druckbares Blatt gibt
  es seit S10: `betrieb_schluesselblatt.php`.
- **Demo-Konto**: `demo_lib.php`, eigene Mengenbremse (`demo`/`demog`),
  **kein Knopf** auf der Anmeldeseite — die Zugangsdaten stehen in README
  und Handbuch. E-P5b-07 ändert daran nur, dass die Anmeldung abschaltbar
  wird.

### 1.2 Registrierung, Ratenschutz, Mail heute

- Es gibt **keine** Registrierungsseite. `login.php` kennt die Töpfe
  `login`, `salt`, `reset`, `demo` (P5a AP6 baut die Sperrleiter,
  E-P5a-04) und die gleiche Antwortdauer (`rate_gleiche_dauer()`).
- `reset_request.php` ist das Muster für „keine Kontoauskunft": immer
  dieselbe Antwort, Mail nur an bekannte Adressen — R37 (4) verlangt
  dasselbe für die Registrierung.
- Mail: P5a AP5 baut `mail_warteschlange`, `mail_einreihen()` mit
  synchronem erstem Versuch, `mail_rahmen()` und `app_url()` (Nachtrag
  16.09.2026). **10b schreibt keine Mail mehr direkt** — jede Nachricht
  dieses Konzepts geht durch `mail_einreihen()`.
- Rechtstexte: `rechtstexte(schluessel, inhalt, stand_am)` mit
  **`impressum`** und **`datenschutz`**; `rechtstexte_lib.php`. Kein
  Schlüssel für Nutzungsbedingungen oder AVV, keine Fassungskennung
  außer `stand_am`, keine Zuordnung je Konto.

### 1.3 Geräte, Mengen

- `devices(api_key_hash, active, geraet_art, geraet_modell, geraet_teil,
  …)`; `ingest.php` prüft über `geraet_schluessel_gueltig()` gegen
  bcrypt (R37 (10): 60–100 ms je Upload). P5a AP7 legt den Topf `ingest`
  daneben; die Sperre am Konto (`gesperrt`) kennt `ingest.php` noch nicht.
- Mengen je Konto: nichts. `speicher_lib.php` misst die Installation;
  `betrieb_statistik.php` zählt Konten und Geräte, keine Bytes je Konto.
  Nr. 48 (Aufbewahrung je Konto) und Nr. 37 (Speichergrenzen je Konto)
  warten hier.

### 1.4 Protokoll heute

- Nichts, was Verläufe hält (P5c-Vorbereitung 2.1–2.3). P5a legt an:
  `mail_warteschlange` (AP5), `rate_limits.stufe` und
  `sicherheit_ereignisse` (AP6), `csp_berichte` (AP4, bereits auf dem
  Zweig, Migration `2026_09_15_csp_berichte`), das Lösch-Protokoll der
  Ziele (AP10). **Das sind vier Tabellen für vier Reiter** — 10b legt die
  fünfte an (Verwaltung, E-P5b-12) und den Schreibweg, der die anderen
  nicht anfasst: `sicherheit_ereignisse` bleibt, wie P5a es baut; 10c
  entscheidet, ob es in die gemeinsame Tabelle wandert (V6).
- `error_log()`: 42 Aufrufe in 21 Dateien; der Log-Helfer aus Backlog 202
  Paket 3 ist dieser Schreibweg (Rahmenplan Fassung 74) — `protokoll()`
  ersetzt `error_log()` **nicht** flächendeckend in 10b (das wäre
  Paket 3 von Schritt 15 in anderem Gewand); 10b baut den Weg und benutzt
  ihn für seine eigenen Ereignisse; die Umstellung der 42 Aufrufe ist ein
  Paket in 10c, wenn der Reiter „System" steht.

### 1.5 Handbuch und Anmeldeseite

- `docs/Handbuch.md` liegt im Repositorium, wird **nicht** deployt
  (Kette syncht `server/`); die Anwendung rendert kein Markdown, es gibt
  keinen Markdown-Parser unter `server/vendor/` (dort: phpseclib).
  Hilfe-Verweise aus der Oberfläche zeigen auf GitHub oder nirgends.
- Die Anmeldeseite hat keine Fußzeile mit Verweisen; Impressum und
  Datenschutz sind als Seiten aus `rechtstexte` erreichbar (P5a AP4
  hat die Kopfzeilen dafür gesetzt).
- Keine Seite „Was ist NAdoku"; das README erklärt es für Entwickler,
  nicht für Notärztinnen.

---

## 2. Entscheidungen

### 2.1 Aus dem Gespräch vom 16.09.2026 (E-P5b-01 bis -10)

**E-P5b-01 — Drei Betriebsarten, Vorgabe „nur auf Einladung".** (R9.)
Betrieb → Servereinstellungen bekommt „Registrierung": *offen* / *offen
mit Freischaltung* / *nur auf Einladung*. Vorgabe nach Einrichtung: **nur
auf Einladung** (heutiges Verhalten, sicherste Grundstellung für
Selbsthoster); nadoku schaltet zum Betriebsstart auf **offen mit
Freischaltung**. Umschalten wirkt sofort auf die Registrierungsseite;
laufende Registrierungen (unbestätigt, wartend) laufen zu Ende.

**E-P5b-02 — Freischaltung.** Selbstregistrierung mit Double-Opt-In wie
bei *offen*; danach Kontostatus **`wartet`** (vierter Status neben
`unbestaetigt` / `aktiv` / `gesperrt`, R37 (2)). Admins erhalten eine
**Sammelmail** über die Warteschlange (höchstens eine je Stunde, Muster
E-P5a-07); Freischaltung ist ein Knopf in der Kontoverwaltung, die
Nutzerin bekommt „freigeschaltet". **Verfall der Wartenden: Einstellung,
Vorgabe 30 Tage** (Betrieb → Servereinstellungen); die Frist steht auf der
Registrierungsseite und in der Bestätigungsmail; beim Verfall geht eine
letzte Mail („Registrierung ist verfallen — du kannst sie neu stellen").
Getrennt davon: **unbestätigte** Konten verfallen nach **48 h** (R37 (3),
fest). Zwei Fristen, zwei Zustände, ein Job (`konto_verfall`, Huckepack).

**E-P5b-03 — Wegwerfadressen.** Mitgelieferte Sperrliste bekannter
Wegwerfdomains als Datei im Repositorium (`server/wegwerfdomains.txt`,
eine Domain je Zeile, mit Auslieferungen gepflegt), **keine
Laufzeitquelle** (R36). Einstellung „Wegwerfadressen abweisen", Vorgabe
**an**, abschaltbar, plus Feld für eigene Domains. Abgewiesen wird
**neutral** — dieselbe Antwort wie jede Registrierung (E-P5b-13); die Seite
sagt allgemein „Wegwerfadressen werden nicht angenommen". Ohne Wirkung bei
*nur auf Einladung*. Welche Liste: **F-P5b-1** (Zuarbeit; Lizenz prüfen,
Herkunft in `docs/Lizenzen.md`).

**E-P5b-04 — Mengengrenze je Konto.** Zwei Grenzen, Vorgabe in Betrieb →
Servereinstellungen, **je Konto überschreibbar** in der Kontoverwaltung
(dort auch Nr. 48, Aufbewahrung je Konto): **5 000 Einsätze** und **250 MB**
(Einsätze samt Spuren und Ruhesegmente, gemessen wie die Statistik zählt).
Ab **80 %** einmalig Mail und dauerhafter Hinweis auf der Kontoseite; bei
**100 %** nimmt `ingest.php` nichts mehr an (**`507`** mit JSON-Grund
`kontingent`; die Uhr behält ihre Warteschlange), der Import bricht mit
Erklärung ab, Bearbeiten und Löschen bleiben frei. Die Kontoverwaltung
zeigt Konten über 80 % gekennzeichnet.

**E-P5b-05 — Einwilligungen.** Drei Häkchen bei der Registrierung:
Nutzungsbedingungen „**angenommen**", AVV „**angenommen**",
Datenschutzerklärung „**zur Kenntnis genommen**" — der Wortlaut trägt den
rechtlichen Unterschied, die Form ist gleich. Gespeichert je Konto mit
**Fassungskennung** (`stand_am` des Textes) und Zeit. Neue Fassung von
Nutzungsbedingungen oder AVV **sperrt den nächsten Login** bis zur Annahme;
neue Datenschutzerklärung zeigt einen Hinweis, der mit demselben Häkchen
quittiert wird. **Bestehende Konten** holen alle drei beim ersten Login
nach P5b nach. Ablehnung: Konto bleibt am Tor, mit den Wegen „Export" und
„Konto löschen" (E-P5b-16); die Uhr puffert wie bei jeder Sperre.

**E-P5b-06 — Fristen des Protokolls.** **V2 beantwortet:** IP-Adressen nur
im Reiter *Sicherheit* (Sperren, Angriffe), Frist **30 Tage fest**
(= E-P5a-09). Reiter **Verwaltung** (das Audit): **365 Tage, einstellbar
90–1 095** (Betrieb → Servereinstellungen). Übrige Reiter (E-Mail, Jobs,
Sicherung, Ziele, System): 30 Tage fest.

**E-P5b-07 — Demo-Anmeldung als Einstellung, kein Knopf.** Das Demo-Konto
bleibt Bestandteil (R25: Bestand, Referenzdaten, Reset). Neue Einstellung
„Demo-Anmeldung zulassen", Vorgabe **an**; *aus* heißt: die Demo-Adresse
wird bei der Anmeldung wie ein falsches Passwort behandelt (gleiche
Antwort, gleiche Dauer), der Bestand bleibt. Beim Umschalten der
Betriebsart auf *nur auf Einladung* fragt die Seite einmal „Demo-Anmeldung
mit abschalten?". **Kein Demo-Knopf auf der Anmeldeseite — auch bei nadoku
nicht**; die Zugangsdaten stehen in README und Handbuch (heute schon so;
das Konzept schreibt es fest).

**E-P5b-08 — Handbuch und „Was ist NAdoku" als Seiten der Anwendung.**
Quelle bleibt Markdown im Repositorium: `docs/Handbuch.md` und neu
`docs/Was-ist-NAdoku.md` (Entwurf Abschnitt 9) — auf GitHub editierbar,
Wortliste läuft darüber, Prüftor Stufe 1 prüft die Rendertauglichkeit
(cmark-gfm, wie Nr. 196). Die Anwendung **rendert zur Laufzeit** mit einem
kleinen vendorierten Markdown-Parser unter `server/vendor/` (eine Datei,
MIT; sicherer Modus: kein rohes HTML, keine Skripte), gecacht je
Dateifassung (Hash) in `app_state`. Zwei **öffentliche** Seiten ohne
Anmeldung: `ueber.php` und `hilfe.php` — Inhaltsverzeichnis aus den
Überschriften, Sprungmarken je Abschnitt, Tabellen und Codeblöcke im
Stil der Anwendung, CSP-konform (kein Inline-Skript). **Fußzeile der
Anmeldeseite**: Was ist NAdoku · Handbuch · Impressum · Datenschutz.
Eingeloggt: Menüeintrag **Hilfe** → Handbuch; Hilfe-Verweise aus der
Oberfläche zeigen auf `hilfe.php#abschnitt`. **Auslieferung:** die Kette
(P5a AP1, `auslieferung.yml`) kopiert beide Dateien vor dem Sync nach
`server/doku/`; die Anwendung sucht erst `../docs/` (Selbsthoster laden
das Repositorium hoch), dann `doku/`, und meldet auf der Statusseite,
wenn keines da ist. **Gestaltung:** Mockup **M-P5b-01** (Fable).

**E-P5b-09 — Onboarding und Konto-Rückfrage.** (R37 (11).) **Erststart**
für jedes neue Konto ohne Stammdaten, eingeladen oder selbst registriert:
drei Schritte — Standort (optional), Rettungsmittel, Uhr oder Handy koppeln
(mit Sprungmarke ins Handbuch); jeder Schritt hat „später", der Einstieg
erscheint bei jedem Anmelden, bis alles erledigt oder einmal „nicht mehr
zeigen" gewählt ist. **Notfallblatt** im Moment der Schlüsselanzeige:
Knopf „Notfallblatt drucken", A4 nach dem Muster des Schlüsselblatts
(Schlüssel in Vierergruppen, Kontoadresse, Adresse der Installation,
Datum, drei Sätze: wofür, was ohne es verloren ist, wo aufbewahren);
später nicht mehr erzeugbar — der Server kennt den Schlüssel nicht.
**Besitz-Rückfrage** beim Anmelden **nach 30 Tagen, nach 6 Monaten, dann
jährlich**: „Hast du dein Notfallblatt noch?" — *Ja* · *Nein* → die
**Schlüsselerneuerung direkt im Dialog** (Passwort eingeben, neuer
Wiederherstellungsschlüssel im Browser, Notfallblatt drucken, alter
Schlüssel ungültig) · *später* (7 Tage, höchstens dreimal je Runde). Die
Erneuerung ist **eine Komponente** (`assets/schluessel.js` +
`api/schluessel_erneuern.php`) mit zwei Verbrauchern: dem Dialog und der
Kontoseite unter Einstellungen — R83, einmal gebaut.

**E-P5b-10 — Betreiber-Rückfrage zum Schlüsselblatt.** Für Konten mit
Rolle BetreiberIn, **alle 3 Monate**, beim Anmelden, dieselbe Komponente
in zweiter Variante. Es wird **eingegeben, nicht nur bestätigt**, und zwar
**teilweise**: Der Dialog nennt für Serverschlüssel und Server-Anteil je
**zwei zufällig gewählte Vierergruppen** (Position auf dem Blatt, etwa
„Serverschlüssel, Gruppen 3 und 11") — die Werte stehen nur auf dem
Blatt, der Server hält beide in `config.php` und vergleicht die Gruppen in
konstanter Zeit, zeigt nie einen Wert, nur die achtstellige Kennung als
Hilfe, welches Blatt gemeint ist. Eingabe über
`schluessel_eingabe_normalisieren()` (Leerzeichen, Groß/Klein). Erfolg →
Protokoll (Reiter Verwaltung: „Schlüsselblatt bestätigt, von …"), nächste
Frage in 3 Monaten; Fehlschlag → Hinweis mit dem Weg „Schlüsselblatt neu
drucken" (Betrieb → Schlüsselblatt), die Frage bleibt. „später" wie oben.
**Eine** Bestätigung je Quartal genügt für die Installation; bis dahin
sieht jede BetreiberIn die Frage. **Keine Schlüsselerneuerung an dieser
Stelle** — den Serverschlüssel zu wechseln heißt alles Versiegelte
umzuhüllen; das ist ein S10-Vorgang, kein Dialog.

### 2.2 Aus dem Nachmessen (E-P5b-11 bis -22)

**E-P5b-11 — Lebenszyklus als Bibliothek `konto_lib.php`.** (R37 (1).)
`konto_anlegen(email, name, rolle, quelle)` — Konto, Kontokennung,
Setz-Token und Einwilligungsplatzhalter in **einer** Transaktion; Quelle
`einladung` / `registrierung` / `einrichtung`. `reset_token_ausstellen
(userId, laufzeit)` entwertet alte Token, stellt einen aus (die Regel „ein
gültiger Token je Konto" gilt damit an allen vier Stellen; Laufzeiten als
Konstanten `TOKEN_EINLADUNG_S = 86400`, `TOKEN_RESET_S = 3600`, keine
SQL-Literale mehr). `admin_users.php`, `admin_user.php`,
`reset_request.php`, `install.php` ziehen um — **Backlog 202 Paket 1
(Token) ist damit hier erledigt** und wird dort so vermerkt. Mailtexte in
`email_lib.php` über `mail_rahmen()`.

**E-P5b-12 — Kontostatus und Protokoll-Schreibweg.** Migration:
`users.status ENUM('unbestaetigt','wartet','aktiv','gesperrt')` (Vorgabe
für Bestand `aktiv`), `users.bestaetigt_am`, `users.gesperrt_seit`,
`users.gesperrt_grund`, `users.loeschung_am` (Karenz, E-P5b-16),
`users.rueckfrage_naechste`, `users.rueckfrage_runde`. `auth_guard.php`
prüft je Anfrage: `gesperrt` → Endegrund `gesperrt` (neben `konto`),
`wartet`/`unbestaetigt` → Seite mit Stand; `ingest.php` weist
`gesperrt`, `wartet`, `unbestaetigt` mit **`403`** und JSON-Grund ab (die
Uhr puffert, `403` ist heute „abgemeldet" — der Grund im Rumpf
unterscheidet; keine Client-Stufe nötig, aber Handbuch). **Protokoll:**
Tabelle `protokoll_ereignisse(id, zeit, reiter, art, urheber_user_id,
betroffen_user_id, text, daten JSON NULL)` mit `reiter ENUM('verwaltung',
'email','jobs','sicherung','ziele','system')` — *Sicherheit* bleibt in
`sicherheit_ereignisse` (P5a), 10c entscheidet die Zusammenführung (V6).
Funktion `protokoll(string $reiter, string $art, string $text, array
$daten = [], ?int $betroffen = null)` in `protokoll_lib.php`: Urheber aus
der Sitzung, `cli`/`job` als Urheber 0 mit Kennzeichen; **scheitert das
Schreiben, scheitert die Handlung nicht** — `error_log()` mit Kennung,
Zähler `protokoll_fehler` in `app_state`, Status-Hinweis (V7, so
entschieden: still scheitern ist schlechter als laut, laut abbrechen ist
schlechter als still). Bereinigung im Job `aufraeumen` nach E-P5b-06.
Text ohne Personenbezug außer Konto-Ids und — im Reiter Verwaltung — der
Kontoadresse, weil das Audit sonst nicht lesbar ist (V2-konform: keine IP).

**E-P5b-13 — Registrierungsseite ohne Kontoauskunft.** `registrieren.php`
(öffentlich, nur bei *offen*/*mit Freischaltung*): Adresse, Name, Passwort
im Browser wie beim Einladungsweg (Schlüsselableitung,
Wiederherstellungsschlüssel, Notfallblatt), drei Häkchen (E-P5b-05),
Honeypot-Feld (unsichtbar, muss leer bleiben) und **Mindestausfülldauer 4 s**
(Zeitstempel im Formular, signiert). **Immer dieselbe Antwort**
(„Wenn die Adresse frei ist, kommt eine Mail"); bekannte Adresse → Mail
„du hast hier schon ein Konto" (Muster `reset_request.php`); Demo-Adresse
ausgeschlossen; Wegwerfdomain → dieselbe Antwort, keine Mail. Töpfe:
`reg` je IP (10/h), `regg` global (100/h → Verlangsamung wie E-P5a-05),
`regz` **je Zieladresse** (3/24 h — sonst Mailbomben-Schleuder, R37 (4));
alle drei nach der Sperrleiter aus P5a. Bestätigungslink 48 h; Klick →
`aktiv` (offen) oder `wartet` (mit Freischaltung) + Sammelmail.

**E-P5b-14 — Betriebsart und Einstellungen.** In Betrieb →
Servereinstellungen die Karte **„Konten"**: Betriebsart, Freischaltfrist
(Tage), Wegwerfadressen an/aus + eigene Domains, Mengengrenzen (Einsätze,
MB), Aufbewahrung je Konto Vorgabe (Nr. 48), Demo-Anmeldung an/aus,
Protokollfrist Verwaltung. Alles `app_state`, gelesen über die zwei
Funktionen aus Schritt 15 Paket 2 — **liegen sie beim Beginn von 10b noch
nicht vor, legt AP1 sie an** (`app_state_lesen()`, `app_state_setzen()`
in `db.php`, mit der 190-Zeichen-Grenze und dem Log der edbak-Fassung);
Schritt 15 zieht dann die übrigen Stellen nach.

**E-P5b-15 — Einwilligungen: Daten und Tor.** `rechtstexte` bekommt die
Schlüssel `nutzungsbedingungen` und `avv` (Platzhaltertexte bis R41,
F-P5b-2); Fassungskennung = `stand_am`. Tabelle `konto_einwilligungen
(user_id, schluessel, stand_am, zeit)`. Tor in `auth_guard.php` nach der
Anmeldung: fehlt eine Annahme zur aktuellen Fassung von
Nutzungsbedingungen oder AVV → `einwilligung.php` (nur diese Seite,
Abmelden, Export, Konto löschen erreichbar); Datenschutz fehlend → Hinweis
oben auf jeder Seite bis zum Häkchen. Admin-Änderung eines Textes setzt
`stand_am` und schreibt ins Protokoll (Verwaltung). API-Endpunkte und
`ingest.php` bleiben vom Tor unberührt (die Uhr fragt niemanden).

**E-P5b-16 — Selbstlöschung mit Karenz, E-Mail-Wechsel.** (R37 (5), (6).)
Kontoseite: „Konto löschen" → Passwort → Status `gesperrt` mit Grund
`selbstloeschung`, `loeschung_am = jetzt + 30 Tage`, Mail mit Rückzugslink
(Anmelden = Rückzug); Job `konto_loeschung` löscht endgültig über
denselben Weg wie die Admin-Löschung (`konto_loeschen()` in
`konto_lib.php`, mit der E25-Entscheidung zu Admin-Backups als
Parameter) und protokolliert. Admin-Löschung bleibt sofort. **E-Mail-
Wechsel**: neue Adresse → Bestätigungslink an die neue (24 h), Hinweismail
an die alte; erst der Klick schreibt um; beide Adressen bis dahin am
Konto (`users.email_neu`, `users.email_neu_token_hash`, `_bis`); Protokoll
(Verwaltung, ohne Adressen im Text — Ids und „Adresse geändert").

**E-P5b-17 — Geräteschlüssel auf SHA-256, sanft.** (R37 (10).)
`devices.api_key_sha256 CHAR(64) NULL`; `geraet_schluessel_gueltig()` prüft
zuerst SHA-256 (konstante Zeit), sonst bcrypt und schreibt bei Erfolg den
SHA-256 nach (Migration beim nächsten erfolgreichen Upload); neue Geräte
nur SHA-256. Der Blindvergleich für unbekannte Kennungen (M4-07) bleibt
in seiner Dauer gleich — er hängt mit `bcrypt` an der Antwortzeit; AP6
misst nach und setzt die Mindestdauer neu, damit bekannt/unbekannt
weiter gleich lang antworten. Messstand: `ingest.php` je Upload − 60 bis
−100 ms erwartet.

**E-P5b-18 — Mengen messen.** `konto_mengen(userId)`: Einsätze gezählt,
Bytes als Summe der Zeilenlängen der Konto-Tabellen (Muster
`speicher_datenbank_bytes()`, je Konto über `user_id`), gecacht je Konto
in `app_state` (Schlüssel `mengen:<id>`), erneuert im Job `aufraeumen`
und nach jedem Upload-Schub (Schätzung: +Bytes des Schubs, exakt beim
nächsten Job). Grenzen aus E-P5a-11-Muster: Vorgabe in `app_state`,
Überschreibung in `users.grenze_einsaetze`, `users.grenze_mb` (NULL =
Vorgabe).

**E-P5b-19 — Erststart und Rückfragen: Zustand am Konto, Dialog im
Browser.** `users.erststart_stand` (Bitfeld der drei Schritte, `-1` =
nicht mehr zeigen); `users.rueckfrage_naechste` (Datum) und
`rueckfrage_runde` (0: 30 Tage, 1: 6 Monate, 2+: jährlich); für
BetreiberInnen zusätzlich installationsweit
`app_state schluesselblatt_bestaetigt_am`. `auth_guard.php` liefert nach
der Anmeldung eine Liste fälliger Einstiege an die Seite (Reihenfolge:
Einwilligungstor, Schlüsselblatt-Rückfrage, Konto-Rückfrage, Erststart);
die Seite zeigt genau einen Dialog (`assets/dialog.js`). „später" schiebt
`rueckfrage_naechste` um 7 Tage und zählt `rueckfrage_verschoben` bis 3.

**E-P5b-20 — Schlüsselerneuerung als Komponente.** `assets/schluessel.js`:
Passwort abfragen → Datenschlüssel entpacken (`pat_wrap_pw`) → neuen
Wiederherstellungscode erzeugen (`newRecoveryCode`) → `pat_wrap_rc` neu
packen → `api/schluessel_erneuern.php` (Sitzung, Passwortprüfung
serverseitig, schreibt `pat_wrap_rc`, `session_epoch` unverändert) →
Anzeige + Notfallblatt. Der Dialog (E-P5b-09) und die Kontoseite rufen
dieselbe Funktion; das Notfallblatt (`notfallblatt.php`, Druckansicht)
bekommt den Schlüssel nur per POST aus dem Browser und speichert nichts.

**E-P5b-21 — Betreiber-Rückfrage: Gruppenprüfung.**
`api/schluesselblatt_pruefen.php` (BetreiberIn): Der Server wählt je Wert
zwei Gruppenpositionen (1–16), speichert sie in der Sitzung, die Seite
zeigt sie; Antwort = vier Gruppen; Vergleich mit `hash_equals()` gegen die
Gruppen aus `config.php` (`server_key`, `kdf_anteil`), gleiche Dauer bei
Erfolg und Fehlschlag; **drei Fehlversuche → Topf `blatt` (Sperrleiter),
Protokoll (Sicherheit, ohne Werte)**. Erfolg setzt
`schluesselblatt_bestaetigt_am`.

**E-P5b-22 — Handbuch-Renderer.** Parser: eine Datei unter
`server/vendor/`, MIT, `SafeMode`; keine Fremdquelle zur Laufzeit.
`doku_lib.php`: `doku_pfad(name)` (erst `../docs/`, dann `doku/`),
`doku_html(name)` (Cache in `app_state` unter `doku:<name>:<hash>`,
höchstens eine Fassung je Name), Inhaltsverzeichnis aus `h2`/`h3`,
Sprungmarken-IDs deterministisch aus dem Überschriftentext (umlautfest),
externe Links mit `rel="noopener"`, Bilder nur aus `docs/` relativ (die
Kette kopiert `docs/bilder/` mit, falls vorhanden). Seiten `hilfe.php`,
`ueber.php` ohne Anmeldung, Ratenschutz nicht nötig (Cache). Kette:
`auslieferung.yml` bekommt den Schritt „docs nach server/doku kopieren"
vor beiden Sync-Jobs. Prüftor Stufe 1: `cmark-gfm --validate-utf8` über
beide Dateien, 0 Warnungen.

### 2.3 Ort je Funktion (K1, R74)

| Funktion | Ort |
|---|---|
| Betriebsart, Freischaltfrist, Wegwerfadressen, Mengengrenzen-Vorgabe, Demo-Anmeldung, Protokollfrist Verwaltung | Betrieb → **Servereinstellungen**, Karte „Konten" |
| Freischalten, Sperren mit Grund, Grenzen und Aufbewahrung je Konto, Mengen je Konto, Status, Einwilligungsstand | Verwaltung → **Konten** (Liste, Kennzeichen > 80 %) und **Kontoseite des Admins** |
| Registrierung, Bestätigung, Verfall | `registrieren.php`, `bestaetigen.php` (öffentlich) |
| Einwilligungstor | `einwilligung.php` |
| Konto löschen, E-Mail wechseln, Schlüssel erneuern, Notfallblatt, Mengenanzeige | Einstellungen → **Konto** (Kontoseite der Nutzerin) |
| Erststart, Konto-Rückfrage, Schlüsselblatt-Rückfrage | Dialog nach der Anmeldung (`dialog.js`), Reihenfolge E-P5b-19 |
| Handbuch, Was ist NAdoku | `hilfe.php`, `ueber.php`; Fußzeile der Anmeldeseite; Menü **Hilfe** |
| Protokoll-Zählkarte | Betrieb → **Status** (bis 10c die Reiter baut) |
| Notfallblatt, Schlüsselblatt | `notfallblatt.php` (Druck), Betrieb → **Schlüsselblatt** (vorhanden) |

### 2.4 Erledigte Fragen (16.09.2026)

**E-P5b-23 — Wegwerfdomain-Liste (F-P5b-1).** Mitgeliefert wird
`disposable-email-domains/disposable-email-domains`
(`disposable_email_blocklist.conf`): **CC0 1.0**, 8 870 Domains, eine
je Zeile, alles klein, keine Sonderzeilen; gepflegt (letzter Commit am
Tag der Prüfung). Gemessen am 16.09.2026: 8 von 8 bekannten
Wegwerfanbietern enthalten (darunter trash-mail.com, wegwerfemail.de,
spambog.de, byom.de), 0 von 10 geprüften Provider- und Klinikdomains
fälschlich. Verworfen: `7c/fakefilter` (BSD-3, 10 686, Kommentarzeilen
und Doppelungen) und `FGRibreau/mailchecker` (MIT, 56 355 — sechsmal so
groß, mehr Risiko für echte Domains). Herkunft in `docs/Lizenzen.md`
trotz CC0. Pflege: Entwicklerwerkzeug
`tools/wegwerfdomains/aktualisieren.py` — holt die Datei, zeigt den Diff,
schreibt `server/wegwerfdomains.txt`; läuft von Hand vor Auslieferungen,
nie zur Laufzeit (R36). Die Datei ist 126 KB und wird nur bei einer
Registrierung gelesen.

**E-P5b-24 — Rechtstexte (F-P5b-2).** Drei Entwürfe liegen unter
`docs/rechtstexte/`: `Nutzungsbedingungen.md`, `AVV.md` (übernimmt die
EU-Standardvertragsklauseln nach Durchführungsbeschluss (EU) 2021/915
unverändert durch Verweis und füllt die Anlagen I–IV aus) und
`Datenschutz-Ergaenzung-P5.md` (elf Bausteine B1–B11 zum Einarbeiten in
die bestehende Erklärung). Festlegungen des Auftraggebers vom 16.09.2026:
Vertragspartner **Gen-EM GbR**; unentgeltlich ohne Verfügbarkeitszusage;
Haftung nur für Vorsatz und grobe Fahrlässigkeit mit den gesetzlichen
Ausnahmen (ein vollständiger Ausschluss ist nicht möglich); Nutzerkreis
**ärztlich im Rettungsdienst tätig mit Zusicherung der Berechtigung**;
AVV auf SCC-Basis; deutsches Recht, Gerichtsstand nur gegenüber
Unternehmern; Subauftragsverarbeiter **dataforest** (Hosting) und
**lima-city** (Mailserver). **Die Entwürfe sind nicht anwaltlich geprüft**
— das ist die verbleibende Zuarbeit vor dem Einspielen; AP4 baut bis
dahin mit Platzhaltern, damit der Weg prüfbar ist. Mit S11 ändert sich die
Abgrenzung der Klartextdaten in allen drei Texten — dann neue Fassungen,
die über E-P5b-05 vorgelegt werden.

---

### 2.5 Nachträgliche Entscheidung (17.09.2026)

**E-P5b-25 — Private Nutzung als Zweckbestimmung; die AVV bleibt.**
Anlass war eine Rückfrage des Auftraggebers während AP3: *„Warum
Auftragsdatenvereinbarung? Braucht es das?"* Die Frage war berechtigt —
E-P5b-05 setzt die AVV voraus, ohne sie herzuleiten, und E-P5b-24 nennt
sie als Festlegung vom 16.09.2026, ebenfalls ohne Begründung.

**Die Herleitung steht im Rahmenplan, R41** (Konzeptgespräch vom
30.08.2026) und war beim Beantworten der Rückfrage zunächst übersehen
worden: *„**AVV** als Bestandteil der Registrierung: Die NotärztInnen
sind Verantwortliche ihrer Dokumentation, der Betreiber wird
Auftragsverarbeiter für jede einzelne — bei 1 000 Konten ist das der
eigentliche Brocken der Öffnung."* Die AVV ist damit keine Annahme,
sondern eine Programmentscheidung mit Datum.

**Festlegung des Auftraggebers vom 17.09.2026:** Mit NAdoku wird
**ausschließlich privat dokumentiert, nie dienstlich**. Die Nutzung liegt
allein in der Verantwortung der nutzenden Ärztin; welche Daten sie
eingibt, ist ihre Entscheidung. Damit ist die Ärztin Verantwortliche im
Sinne der DSGVO, Gen-EM ist ihr Auftragsverarbeiter, und die AVV bleibt
Bestandteil der Registrierung.

**Was das auflöst:** Prüfhinweis (b) der AVV (*„Für Nutzerinnen, die als
Beschäftigte eines Trägers dokumentieren, ist der Träger
Verantwortlicher"*) stellte die tragende Konstruktion des eigenen
Vertragstextes in Frage — die Nutzerin nimmt bei der Registrierung eine
Parteistellung an, die der Anhang desselben Dokuments bezweifelt. Die
Festlegung räumt das ab. **Die Festlegung schafft die AVV nicht ab, sie
macht sie erst richtig:** Art. 28 Abs. 3 DSGVO verlangt den Vertrag
immer, wenn im Auftrag verarbeitet wird; dass die Verantwortliche eine
Privatperson ist, ist kein Ausnahmetatbestand.

**Formulierung: hart** (Entscheidung des Auftraggebers gegen die
Empfehlung der Umsetzung, die zur weichen riet, weil die Anwendung den
Unterschied nicht erkennen kann). Die Nutzungsbedingungen untersagen das
**Dokumentieren im Auftrag** — nicht das **Weitergeben danach**. Beides
wird getrennt formuliert, weil eine zu weit gefasste Klausel sonst
nebenbei den Export an die eigene Station verbietet (siehe unten).

**Verworfen: die Anwendung als Probe-/Versuchsanwendung mit ausschließlich
fiktiven Daten zu deklarieren.** Vom Auftraggeber erwogen, mit dem Gedanken,
die Verantwortung so vollständig auf die Nutzerin zu verlagern. Gegen die
Konstruktion sprechen fünf Gründe, hier festgehalten, damit sie nicht
wiederkehrt:

1. Sie widerspricht dem Gebauten. Die Anwendung nimmt GPS-Spuren einer am
   Handgelenk getragenen Uhr entgegen, protokolliert Reanimationsverläufe
   und liefert ein Exportformat aus, dessen Spaltenfolge mit einer realen
   Station vereinbart ist. **Und sie verschlüsselt Patientendaten
   Ende-zu-Ende** — der aufwendigste Teil des Projekts. Eine Anwendung für
   erfundene Daten braucht keine E2E-Verschlüsselung; die Verschlüsselung
   ist das stärkste Eingeständnis, dass echte Daten erwartet werden.
2. Die Rollen der DSGVO bestimmen sich nach Art. 4 danach, wer tatsächlich
   über Zwecke und Mittel entscheidet — nicht nach der Erklärung im
   Kleingedruckten. Der Betreiber ist nach dem eigenen Entwurf „eine Gruppe
   von Notärzten", die den Dienst selbst benutzt; er weiß, was hineingeht.
3. „Selbst schuld" löst nichts: Die Daten lägen danach auf dem Server des
   Betreibers, und Art. 32 (Sicherheit) und Art. 82 Abs. 2 (Haftung)
   treffen den Auftragsverarbeiter unmittelbar und sind nicht abbedingbar.
   Es entstünde die Lage **echte Gesundheitsdaten ohne AVV, ohne
   Verzeichnis, ohne dokumentierte TOMs** — genau die, die R41 vermeiden
   will.
4. Gegenüber einer Aufsichtsbehörde wirkt die Erklärung neben einer
   Datenbank mit echten Spuren als Verschleierung und wird nach
   Art. 83 Abs. 2 nicht neutral gewertet.
5. Sie verhindert die Arbeit, die ohnehin ansteht: Wer „Versuchsanwendung"
   schreibt, schreibt keine TOMs, kein Verarbeitungsverzeichnis und keinen
   Breach-Prozess — bis echte Daten drin sind, und dann fehlt alles.

**Was stattdessen dasselbe Ziel erreicht** und bereits gebaut oder geplant
ist: die **Öffnung in Wellen** nach R41 (erst „nur auf Einladung", dann
„offen mit Freischaltung", dann „offen" — die Vorgabe der Installation
steht auf `einladung`); das **Demo-Konto** als Ort für erfundene Daten
(AP7); die **Haftungsbegrenzung** auf Vorsatz und grobe Fahrlässigkeit
(Nutzungsbedingungen 8.1, getragen von der Unentgeltlichkeit).

**Das Exportprofil „Excel (GuteSeele)" bleibt** (Entscheidung des
Auftraggebers). Es ist ausgeliefert, seine Zielgruppe ist laut
`docs/Export-Format.md` „Dritte", und die Spaltenfolge ist „Teil des
Vertrags mit dem Empfänger". Das widerspricht der privaten Zweckbestimmung
**nicht** — die Ärztin exportiert selbst und entscheidet selbst, und genau
das ist ihre Verantwortung. Es widerspricht aber dem Satz *„keine Werbung,
keine Weitergabe, keine Auswertung deiner Daten durch Dritte"* im Entwurf
der Seite „Was ist NAdoku": Gemeint ist der Betreiber, gelesen wird es
weiter. **Der Satz wird in AP8 eindeutig auf den Betreiber bezogen.**

**Folgen für die drei Rechtstext-Entwürfe** (mit dieser Entscheidung
umgesetzt, Stand 17.09.2026):

- Nutzungsbedingungen 2.1/2.2 — private Zweckbestimmung als Pflicht, hart
  formuliert; die bisherige Zusicherung („Ob und in welchem Umfang du
  Einsatzdaten außerhalb der Systeme deines Trägers festhalten darfst …")
  entfällt, weil sie die dienstliche Einbindung zum Regelfall machte.
- AVV A.1 — die private Zweckbestimmung wird als Grundlage der
  Rollenverteilung genannt; Prüfhinweis (b) entfällt.
- **AVV A.2 — der Satz „Patientenbezogene Angaben erreichen den Betreiber
  ausschließlich verschlüsselt" fällt.** Er ist unrichtig und widerspricht
  R41 (*„Die Datenschutzerklärung nennt ehrlich die Grenze der E2E … eine
  Flugspur ist der Einsatzort"*) sowie Anlage II Nr. 2 und Anlage III
  derselben Datei.
- **§ 203 StGB wird aufgenommen** (Verpflichtung des Betreibers als
  mitwirkende Person). R41 verlangt ihn ausdrücklich in den
  Vertragstexten; er kam in keinem der drei Entwürfe vor. Das wiegt hier
  besonders, weil Betreiber und Nutzerkreis derselbe Berufsstand sind.
- Datenschutz-Ergänzung — eigener Baustein zu GPS-Spur und
  Phasenkoordinaten; bisher stand dazu ein Halbsatz bei den gekoppelten
  Geräten, während die Nutzungsbedingungen 4.1 für genau diese Abgrenzung
  dorthin verweisen.
- AVV Anlage IV — der Adressdienst (Vorgabe `photon.komoot.io`,
  abschaltbar auf zwei Ebenen, `docs/Lizenzen.md` 6.2) wird genannt; er
  fehlte in der Liste.

**Nicht Gegenstand dieser Entscheidung, aber beim Prüfen aufgefallen und
dem Auftraggeber gemeldet:** Die AVV nennt als Vertragspartner die
**Gen-EM GbR** — bei einer GbR haften die Gesellschafter persönlich und
unbeschränkt. R41 nennt „Recht **und Betreiberorganisation** vor der
Öffnung" in einem Atemzug; die Rechtsform gehört damit in die Zuarbeit
nach E-P5b-24. Der Auftraggeber führt diese Zuarbeit selbst; sie ist ab
17.09.2026 **aus der Liste der Umsetzung genommen**.

**Ein Mangel in AP3/AP4, dabei gefunden:** Die drei Häkchen bei der
Registrierung werden nirgends festgehalten, und sie werden auch für Texte
verlangt, die nicht in Kraft sind. Backlog **Nr. 223**.

**Erledigt am 17.09.2026 mit Web 20.22.2.** Die dort offene Frage ist
entschieden: **Festgehalten wird bei der Registrierung**, nicht erst am Tor —
dort wird der Vertrag geschlossen, das Tor holt nur eine *neue* Fassung nach.
Dass das Konto in diesem Augenblick noch `unbestaetigt` ist, steht dem nicht
entgegen; festgehalten wird, was an diesem Formular erklärt wurde, und der
Verfallsjob räumt eine nie bestätigte Registrierung samt Zeilen weg
(`ON DELETE CASCADE`). Neu ist `einwilligung_in_kraft()`; Prüfung und Markup
der Registrierung ziehen aus derselben Liste, damit das Formular keinen Haken
verlangen kann, den es nie gezeigt hat. Fünf Prüffälle gemessen (Prüfdokument
F11), dabei ein Folgefehler gefunden: Die Absage sagte fest „Ohne alle drei"
und zählt jetzt mit.

---

## 3. Arbeitspakete

### 3.0 Reihenfolge und Abhängigkeiten

AP1 zuerst (alle anderen schreiben ins Protokoll und lesen Einstellungen);
AP2 vor AP3, AP5 (Bibliothek); AP3 nach **M-P5b-02**; AP4 unabhängig nach
AP1; AP6 unabhängig nach AP1; AP7 klein, jederzeit; AP8 nach **M-P5b-01**;
AP9 nach AP2 und M-P5b-02. Voraussetzung des ganzen Konzepts: Merge von
10a und Umsetzung von Schritt 15 (Rahmenplan Fassung 74) — sind die zwei
`app_state`-Funktionen dann da, entfällt der Teil in AP1.

### AP1 — Protokoll-Schreibweg und Einstellungen (E-P5b-06, -12, -14)

- Migration `protokoll_ereignisse`; `protokoll_lib.php`; Bereinigung im
  Job; Karte „Konten" in Servereinstellungen (alle Felder, noch ohne
  Verbraucher außer Demo-Anmeldung); Zählkarte auf Status; falls nötig
  `app_state_lesen()`/`_setzen()`.
- **Abnahme:** Wartungsprobe um den Fall „Schreiben ins Protokoll
  scheitert" erweitert (Handlung gelingt, Zähler +1, Status-Hinweis);
  Bereinigung: Eintrag 31 Tage alt (Reiter E-Mail) weg, 31 Tage alt
  (Verwaltung) bleibt, 366 Tage alt weg; Wortliste 0/0/0.

### AP2 — Lebenszyklus-Bibliothek (E-P5b-11, -12 Kontostatus)

- `konto_lib.php`; Umzug der vier Token-Stellen; `users.status` und
  Spalten; `auth_guard.php` und `ingest.php` prüfen den Status; Sperren
  mit Grund in der Kontoverwaltung; Backlog 202 Paket 1 (Token) vermerken.
- **Abnahme:** `grep -rn "INSERT INTO password_resets" server/` zeigt
  nur `konto_lib.php`; Prüfkonten-Lauf: Einladung, Reset, Einrichtung
  laufen; gesperrtes Konto → `403` in `ingest.php` und Endegrund
  `gesperrt`; Uhr-Simulator sendet nach Entsperrung den Rückstand
  vollständig (Punkte vorher = nachher).

### AP3 — Registrierung (E-P5b-01, -02, -03, -13; **nach M-P5b-02**)

- `registrieren.php`, `bestaetigen.php`, Töpfe `reg`/`regg`/`regz`,
  Honeypot, Mindestausfülldauer, Wegwerfliste (E-P5b-23, samt
  `tools/wegwerfdomains/`), Freischaltung
  mit Sammelmail, Job `konto_verfall` (48 h / Frist), Mails über
  `mail_einreihen()`, Betriebsart-Wirkung.
- **Abnahme:** Prüfkonten-Lauf mit drei Betriebsarten; gleiche
  Antwortzeit frei/bekannt/Wegwerf (Δ < 50 ms über 100 Messungen);
  `regz` sperrt die vierte Mail an dieselbe Adresse in 24 h; Verfall
  48 h und 30 Tage im Prüfstand mit gestellter Uhr; Bilderlauf der neuen
  Seiten 8 Breiten 0/0/0.

### AP4 — Einwilligungen (E-P5b-05, -15; F-P5b-2)

- Schlüssel in `rechtstexte`, `konto_einwilligungen`, `einwilligung.php`,
  Tor in `auth_guard.php`, Hinweis für Datenschutz, Nachholen für
  Bestand, Protokoll bei Textänderung.
- **Abnahme:** Bestandskonto landet nach Update am Tor und kommt nach
  drei Häkchen durch; Textänderung sperrt erneut (Nutzungsbedingungen)
  bzw. zeigt Hinweis (Datenschutz); API und `ingest.php` unberührt
  (Referenzlauf 0 Fehlversuche mit Konto am Tor).

### AP5 — Selbstlöschung, E-Mail-Wechsel (E-P5b-16)

- Kontoseite: beide Wege; Job `konto_loeschung`; Rückzug per Anmeldung;
  `konto_loeschen()` gemeinsam mit der Admin-Löschung; Protokoll.
- **Abnahme:** Wiederherstellungsprobe: Konto in Karenz zurückgeholt →
  Bestand unverändert (Zahlen); nach 30 Tagen (gestellte Uhr) endgültig
  weg, Admin-Backups nach E25 behandelt; E-Mail-Wechsel: alte Adresse bis
  zum Klick aktiv, Hinweismail zugestellt (Versandprobe).

### AP6 — Mengengrenze, Aufbewahrung je Konto, SHA-256 (E-P5b-04, -17, -18; Nr. 37, 48)

- `konto_mengen()`, Grenzen, `507`-Weg, Kontoseite, Kontoverwaltung,
  Mail bei 80 %; Nr. 48 Aufbewahrung je Konto als Überschreibung; SHA-256
  sanft; Messstand.
- **Abnahme:** Messstand-Konto (5 000) bei 100 % → Upload `507`, Uhr
  behält Warteschlange, nach Grenze +1 000 kommt alles an (Punkte
  vorher = nachher); `ingest.php` je Upload gemessen vor/nach SHA-256;
  Antwortzeit bekannt/unbekannt Δ < 50 ms; Nr. 37 trägt die Zahlen.

### AP7 — Demo-Anmeldung als Einstellung (E-P5b-07)

- Einstellung, Verhalten bei *aus*, Rückfrage beim Umschalten, README
  und Handbuch: „kein Knopf, so ist es gewollt".
- **Abnahme:** Demo-Adresse bei *aus* → dieselbe Antwort und Dauer wie
  falsches Passwort (Δ < 50 ms); Demo-Reset funktioniert weiter.

### AP8 — Handbuch und „Was ist NAdoku" (E-P5b-08, -22; **nach M-P5b-01**)

- Parser vendoriert (`docs/Lizenzen.md`), `doku_lib.php`, `hilfe.php`,
  `ueber.php`, Fußzeile, Menü, Kette (Kopierschritt), Prüftor
  (Markdown-Prüfung), `docs/Was-ist-NAdoku.md` aus Abschnitt 9 (vom
  Auftraggeber gegengelesen), Hilfe-Verweise in der Oberfläche auf
  Sprungmarken.
- **Abnahme:** Handbuch vollständig gerendert (Zahl der Überschriften =
  Zahl der Inhaltsverzeichnis-Einträge), 0 rohes HTML durchgelassen
  (Probe mit `<script>` im Markdown), CSP-Probe 0 Berichte, Bilderlauf
  beider Seiten 8 Breiten 0/0/0, Seite ohne `docs/` und `doku/` zeigt
  den Hinweis statt einer leeren Seite.

### AP9 — Onboarding und Rückfragen (E-P5b-09, -10, -19, -20, -21; **nach M-P5b-02**)

- Erststart, Notfallblatt, Konto-Rückfrage, Schlüsselerneuerung
  (Komponente, zwei Verbraucher), Betreiber-Rückfrage mit Gruppenprüfung,
  Topf `blatt`.
- **Abnahme:** Prüfkonten-Lauf mit gestellter Uhr: Rückfrage bei 30 d,
  6 M, 1 J, „später" ×3; Erneuerung aus Dialog und aus der Kontoseite
  liefert dieselbe Funktion (ein `grep` auf die Komponente: zwei
  Aufrufer, eine Definition); nach Erneuerung öffnet der alte Schlüssel
  nichts mehr, der neue alles (Reset-Probe); Gruppenprüfung: richtig →
  bestätigt, drei falsche → Sperre nach Leiter, Protokoll ohne Werte;
  Notfallblatt-Druckansicht in 2 Breiten.

### AP10 — Abschluss

- Beide Kreisläufe 0 unerklärt (R24); Wortliste 0/0/0; Bilderlauf;
  Prüfdokument nach K9; Statusblock; Einschübe aus Abschnitt 7 und 8;
  Handbuch-Kapitel Konto, Registrierung, Notfallblatt; Merge nach
  Freigabe (K7).

---

## 4. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| Prüftor Stufe 1 (P5a) | jeder Push | grün; Markdown-Prüfung 0 Warnungen (AP8) |
| Kreisläufe csv und edbak | AP10, Stufe 2 nach Staging-Deploy | 0 unerklärt |
| Prüfkonten-Lauf (`tools/pruefkonten/`) | AP2, AP3, AP4, AP7, AP9 | Zahlen wie in den Abnahmen; Antwortzeit-Δ < 50 ms wo genannt |
| Referenzlauf / Uhr-Simulator | AP2, AP4, AP6 | 0 Fehlversuche; Punkte vorher = nachher |
| Messstand | AP6 | `ingest.php` −60 bis −100 ms je Upload; 507-Weg ohne Verlust |
| Versandprobe | AP3, AP5 | jede Mail dieses Konzepts einmal zugestellt, Betreff mit Präfix |
| Wiederherstellungsprobe | AP5 | Karenz-Rückzug unverändert; Endlöschung nach E25 |
| Wartungsprobe | AP1 | Protokoll-Fehlfall grün |
| Bilderlauf | AP3, AP8, AP9, AP10 | 0/0/0 in 8 Breiten, neue Seiten eingetragen in `seiten.json` |
| Wortliste | jedes Paket mit Text | 0/0/0 |

**Nicht prüfbar und wie damit umgegangen wird:** die Rückfragen in echter
Zeit (30 Tage, 6 Monate, 3 Monate) — im Prüfstand mit gestellter Uhr;
Zustellung an fremde Postfächer — Versandprobe gegen die eigenen; die
Wegwerfliste — mit einer Testdomain zusätzlich zur gelieferten Liste.

---

## 5. Gesammelte Fehlerfunde (K4)

| # | Fund | Wo | Wie weiter |
|---|---|---|---|
| F1 | Die Einladungsmail nennt den Langnamen der Anwendung noch von Hand („Willkommen bei der …") | `admin_users.php` | AP2 über `mail_rahmen()` (P5a AP5) |
| F2 | `403` aus `ingest.php` bedeutet der Uhr heute „abgemeldet"; mit Kontostatus bekommt derselbe Code einen zweiten Grund | `Uploader.mc`, `ingest.php` | E-P5b-12: Grund im Rumpf; Handbuch; Backlog-Eintrag für die Uhr-Anzeige (Abschnitt 8) |
| F3 | `rechtstexte` hat keine Fassungskennung außer `stand_am`; zwei Änderungen am selben Tag wären eine Fassung | `rechtstexte_lib.php` | AP4: `stand_am` wird DATETIME |
| F4 | **Die Wortliste sah die Rechtstexte nicht an.** Bereich (c) ist eine feste Liste von acht Dateien; `docs/rechtstexte/` stand nicht darin, weil die Entwürfe erst am 16.09.2026 entstanden. Der Lauf nach der Überarbeitung vom 17.09.2026 meldete **0 Treffer**, ohne eine Zeile davon gelesen zu haben — genau der Fall B-S4-06 | `tools/wortliste/wortliste.py` | **Erledigt 17.09.2026** mit E-P5b-25: die drei Dateien eingetragen, Lauf ergab **14 Treffer** (11× „Spur" statt „GPS-Daten", 2× „Piloten", 1× „Station"), alle umformuliert statt ausgenommen; danach 0 von 0. `LIESMICH.md` hält den Fall fest |
| F5 | **Die drei Häkchen der Registrierung werden nicht festgehalten**, und sie werden auch für Texte verlangt, die nicht in Kraft sind — `registrieren.php` prüft `RT_EINWILLIGUNG`, schreibt aber nie nach `konto_einwilligungen`; der einzige Schreibweg liegt am Tor beim Login | `registrieren.php`, `einwilligung_lib.php` | Backlog **Nr. 223**; eigenes Paket vor AP10. Dabei zu entscheiden: Eintrag schon bei der Registrierung oder bewusst erst am Tor |

---

## 6. Fable-Schritte der Umsetzung

**M-P5b-01 — Dokumentseite** (vor AP8): `hilfe.php` mit Inhaltsverzeichnis
links (Desktop 1440) und aufklappbar oben (Mobil 360), Suchfeld,
Sprungmarken, Fußzeile; `ueber.php` als kürzere Fassung derselben Seite
mit Einstieg „Registrieren" / „Anmelden" je nach Betriebsart. Dazu die
**Fußzeile der Anmeldeseite**.

**M-P5b-02 — Registrierung, Erststart, Rückfrage-Dialog** (vor AP3):
`registrieren.php` (drei Häkchen, Honeypot unsichtbar, Hinweistexte zu
Frist und Wegwerfadressen), `bestaetigen.php` und die Wartezustandsseite;
der **Erststart** als dreistufiger Einstieg über der Tagesübersicht; der
**Rückfrage-Dialog** in beiden Varianten (Konto: Ja/Nein/später mit
eingebetteter Erneuerung; Betreiber: vier Eingabefelder mit
Gruppenpositionen); das **Notfallblatt** als Druckansicht.

Vor jedem Mockup hinweisen und pausieren (K8). Alle übrigen Oberflächen
(Karte „Konten", Kontoseite, Kontoverwaltung) folgen bestehenden Mustern.

**Stand 17.09.2026: beide Mockups liegen als V1 vor** (Fable, auf Anforderung
des Auftraggebers vor der Umsetzung), unter `konzept-p5b/mockups/` mit
`LIESMICH.md`: `M-P5b-01-dokumentseite` (+ `-handy`), `M-P5b-02a-registrierung`,
`M-P5b-02b-erststart`, `M-P5b-02c-rueckfrage` (je + `-handy`),
`M-P5b-02d-notfallblatt` — je HTML mit dem echten `style.css` und PNG. Was sie
zusätzlich festlegen: Hilfe-Symbol im Kopf links vom Zahnrad; drei Knöpfe der
Konto-Rückfrage als Primär (breit) / Neutral / Leise; Betreiber-Rückfrage mit
vier Gruppenfeldern zweispaltig; Erststart als Karte, nicht als Dialog; das
Notfallblatt ohne Logo, mit Kennung von Konto und Installation. Nach Freigabe
sind AP3, AP8 und AP9 ohne weitere Pause umsetzbar.

**Gestaltungsvorgaben des Auftraggebers vom 17.09.2026 (gelten für die
Umsetzung, nicht nur für die Mockups):**

1. **Zeilenaktionen rechtsbündig in einer Spalte.** In Listen wie dem
   Erststart stehen Knöpfe und Plaketten aller Zeilen am **rechten Rand**
   untereinander, nicht hinter dem Text; die Plakette „erledigt" steht an
   derselben Stelle wie vorher der Knopf. Das ist die Regel aus
   `.zeile` / `.zeile-aktionen` (Baustein 9.2/9.3) — sie gilt hier
   ausdrücklich auch für Plaketten.
2. **Kartenfuß:** links das Häkchen „nicht mehr zeigen", rechts „Später".
3. **Alles vertikal zentriert und an seinem Element ausgerichtet:**
   Nummernplakette, Text und Aktion einer Zeile auf einer Mittellinie;
   Häkchen mittig zum Text; Knöpfe im Dialogfuß auf einer Höhe. Wo ein
   Baustein das schon leistet (`.zeile` hat `align-items:center`), wird es
   benutzt, nicht nachgebaut; wo ein neuer Baustein entsteht, ist die
   Zentrierung Abnahmekriterium (Bilderlauf, Knopfhöhen 0).
4. Die Bezeichnung **„Vereinbarung zur Auftragsverarbeitung (AVV)"** bleibt —
   das ist der Rechtsbegriff aus Art. 28 DSGVO; „Datenschutzvereinbarung"
   wäre mit der Datenschutzerklärung verwechselbar. In der Oberfläche steht
   der Begriff mit der Kurzform in Klammern, der Link führt zum Text.

Die Mockups M-P5b-02b sind mit Punkt 1 und 2 als V1.1 neu gerendert; die
übrigen Mockups waren davon nicht betroffen. **Alle Mockups sind am
17.09.2026 freigegeben.**

---

## 7. Einschub Rahmenplan (mit der Freigabe; Fassungsnummer vergibt die einspielende Instanz)

- **Fahrplanzeile 10b**, Konzept: „liegt vor:
  `docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md` (Fable,
  16.09.2026, E-P5b-01 bis -22, AP1–AP10, zwei Fable-Schritte)"; Status
  nach Freigabe: „Konzept freigegeben — Umsetzung nach 10a-Merge und
  Schritt 15".
- **R37** Status: „(1)–(7), (10), (11) in 10b (E-P5b-…); (8), (9) in P5a".
  **R9**, **R25** Status: „konkretisiert in 10b (E-P5b-01, -07)".
- **Abschnitt 5:** Nr. 37 (Speichergrenzen-Teil) und 48 → „10b AP6"; 200
  bleibt; 202 Paket 1 Token → „erledigt in 10b AP2" (nach Umsetzung).
- **Abschnitt 6, Zuarbeiten:** anwaltliche Prüfung der drei Entwürfe
  unter `docs/rechtstexte/` (E-P5b-24) — vor dem Einspielen in
  `rechtstexte`; AP4 baut vorher mit Platzhaltern.
- **Schritt-10-Block:** Satz zu 10b analog zum P5a-Absatz; V2 als
  beantwortet (E-P5b-06).
- **Abschnitt 10:** eine Zeile.

## 8. Einschub Backlog (Nummern vergibt die einspielende Instanz, ab der Reservierung im Kopf)

- **Neu:** Proof-of-Work im Browser für die Registrierung (R37 (4)
  „notfalls") — Auslöser: Spam trotz Honeypot, Mindestausfülldauer und
  `regz`; niedrig.
- **Neu:** Uhr zeigt bei `403` den Grund aus dem Rumpf (gesperrt /
  wartet / abgemeldet) statt einheitlich „abgemeldet" — niedrig, mit der
  nächsten Uhr-Stufe (zusammen mit Nr. 201).
- **Neu:** Wegwerfdomain-Liste mit jeder Auslieferung nachziehen
  (`tools/wegwerfdomains/aktualisieren.py`, E-P5b-23) — Pflegeaufgabe.
- **Nr. 37, 48:** „in 10b AP6". **Nr. 202** Paket 1 (Token): „erledigt in
  10b AP2" nach Umsetzung. **Nr. 200:** unverändert.

---

## 9. Entwurf: „Was ist NAdoku" (`docs/Was-ist-NAdoku.md`)

*Zielgruppe: Notärztinnen und Notärzte, die zum ersten Mal auf die Seite
kommen. Ton wie das Handbuch: direkt, keine Werbung. Der Auftraggeber
liest gegen; die Wortliste läuft darüber.*

> # Was ist NAdoku?
>
> NAdoku ist ein Werkzeug, mit dem du deine Notarzteinsätze
> dokumentierst — Zeiten, Ort, Rettungsmittel, Besatzung, Verlauf — und
> das dir am Ende eines Jahres sagt, was du gemacht hast. Es läuft im
> Browser und, wenn du willst, auf einer passenden Uhr oder einem
> Android-Handy, die deine Einsätze unterwegs erfassen und später
> abgleichen — welche Geräte das sind, steht im Handbuch.
>
> ## Für wen
>
> Für Menschen, die im Rettungsdienst ärztlich tätig sind und ihre
> Einsätze für sich selbst festhalten wollen: für die Weiterbildung, die
> Fortbildungsnachweise, den eigenen Überblick. Es ist kein
> Einsatzprotokoll der Leitstelle und ersetzt keine Dokumentation, die
> dein Träger von dir verlangt.
>
> ## Was mit deinen Daten passiert
>
> Alles, was Patientinnen und Patienten betrifft — Alter, Geschlecht,
> Diagnose, Maßnahmen, Freitext —, verschlüsselt dein Browser, bevor es
> den Server erreicht. Der Server speichert nur, was er nicht lesen kann.
> Das gilt auch für den Betreiber: Er sieht, wie viele Einsätze du hast,
> nicht, worum es ging. Deshalb gibt es einen
> Wiederherstellungsschlüssel, den nur du hast — verlierst du ihn und
> vergisst dein Passwort, kann niemand die Daten öffnen. NAdoku sagt dir
> das bei der Einrichtung und fragt dich später, ob du ihn noch hast.
>
> Zeiten, Orte und Namen deiner Kolleginnen liegen lesbar auf dem
> Server, damit die Anwendung damit rechnen kann. Was genau verschlüsselt
> ist und was nicht, steht in der Datenschutzerklärung dieser
> Installation.
>
> ## Was es nicht ist
>
> Kein Produkt einer Firma. NAdoku ist freie Software (AGPL) und wird von
> Gen-EM betrieben, einer Gruppe von Notärzten. Es gibt keine Werbung,
> keine Weitergabe, keine Auswertung deiner Daten durch Dritte. Es gibt
> aber auch keine Hotline: Hilfe steht im Handbuch, und was dort fehlt,
> erreicht uns über die Kontaktadresse im Impressum.
>
> ## Wie du anfängst
>
> Je nach Installation kannst du dich selbst registrieren oder brauchst
> eine Einladung — die Anmeldeseite sagt dir, was gilt. Nach der
> Anmeldung führt dich ein kurzer Einstieg durch die ersten Schritte:
> ein Standort, ein Rettungsmittel, eine Uhr oder ein Handy. Wer erst
> schauen will, findet im Handbuch ein Demo-Konto mit erfundenen
> Einsätzen.
>
> [Zum Handbuch](hilfe.php) · [Registrieren oder anmelden](login.php)
