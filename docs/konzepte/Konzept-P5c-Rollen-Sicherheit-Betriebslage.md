# Konzept P5c — Rollen, Sicherheit, Betriebslage

**Kürzel:** `P5c`. Arbeitspakete `AP1 … AP11` (Commit-Nachrichten beginnen
mit `P5c-AP<n>: …`), Mockup-Runden `M-P5c-NN`, Entscheidungen `E-P5c-NN`,
Befunde `F-P5c-NN`, Fragen an die BetreiberIn `Q-P5c-NN`, Prüfpunkte
`P-P5c-NN` (im Prüfdokument). Benennung nach `docs/Pruefablauf.md` 7; wie die
Nummern aus Fassung 1 umgezogen sind, steht in 0a (E-P5c-35).
**Rahmenplan:** Schritt 10c (R82), R10, R25, R31, R36, R38, R39, R42, R64,
R74, R75, R78, R81, R83; Einschub vom 20.09.2026 (Abschnitt D).
**Backlog:** 80 (Rest), 121, 141, 168, 169, 190, 191, 192, 243, 244, 245, 246,
248, 253, 269, **286** (neu, 23.09.2026); nach Schritt 18: 249.
**Nicht hier:** 122 (→ Schritt 17, E-P5c-23), 228 (nur auf Anlass), 250
(→ Schritt 17), 258, 276, 277 (E-P5c-46), 287 (→ Schritt 17, E-P5c-49);
**198 wird nicht umgesetzt** (E-P5c-27); **200 wird nicht umgesetzt**
(E-P5c-51, AP10 entfällt).
**Vorbereitung:** `Vorbereitung-P5c-Protokollierung.md` (V1–V9 entschieden,
16./20.09.2026); Gestaltungsvorgaben des Auftraggebers vom 17. und 20.09.2026
(Konzept P5b Abschnitt 6; Erklärtext-Regel); **Abgleich vom 23.09.2026**
gegen `8fd4553` (`konzept-p5c/Abgleich-2026-09-23.md`, 0a).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2). **Kein Fable-Schritt**
in der Umsetzung. Die Mockup-Runde **M-P5c-02** fährt Opus, **aus dem
HTML-Stand der Anwendung** (E-P5c-34).
**Fächerung (`CLAUDE.md` 7):** je Paket in 3.2 (E-P5c-33).
**Ablage:** dieses Dokument; Prüfdokument daneben
(`Pruefdokument-P5c-Rollen-Sicherheit-Betriebslage.md`, entsteht mit AP1);
Mockups und Abgleich in `konzept-p5c/`.
**Keine Versionsnummer im Konzept** (K3). Wo „Nebenstufe" oder „Hauptstufe"
steht, ist das ein Vorschlag nach `CLAUDE.md` 2; die Nummer setzt die
Umsetzung.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **23.09.2026 — Fassung 2.** Fassung 1 (20.09.2026, gegen `862ca7f`, Web 20.25.0) freigegeben, Mockup-Runde M-P5c-01 freigegeben. Am 23.09.2026 gegen `8fd4553` (Web 20.37.1, nach Schritt 15, 16 und PK-01 bis -04) abgeglichen: 139 Rohbefunde, 16 davon in der Gegenlesung widerlegt; 21 Fragen dem Auftraggeber vorgelegt und am selben Tag beantwortet (Q-P5c-05 bis -25, 2.7). Daraus **E-P5c-31 bis -58** und **F-P5c-15 bis -58**. Nachgezogen auf `main` `8ae873c` (PK-04 gemergt, Konzept TB). |
> | Entschieden | E-P5c-01 bis **E-P5c-58**; Q-P5c-01 bis **-25** beantwortet |
> | Offen | **M-P5c-02 gebaut am 23.09.2026, Freigabe steht aus** — mit ihr die Fragen **Q-P5c-26 bis -30** (6.2). Neue Befunde der Runde: **F-P5c-59 bis -63** (Abschnitt 5); zwei davon liegen außerhalb von P5c und stehen als **Backlog 288** (Neueinrichtung scheitert seit Web 20.30.0) und **289** (Anmeldeseite) — **Nr. 288 hält Station B auf** und gehört vor P5c (3.0) |
> | Umsetzung | nicht begonnen; **nach PK-05** (E-P5c-32), nach Freigabe von M-P5c-02, nach Behebung von Nr. 288. Reihenfolge AP1 → AP2 → AP3 → AP4 → AP5 → AP6 → AP7 → AP8 → AP9 → AP11 (3.0); **AP10 entfällt** |
> | Fable-Schritte | **keine.** M-P5c-01 ist am 20.09.2026 mit Fable gefahren; M-P5c-02 fährt Opus (Q-P5c-08) |
> | Nummern | Backlog **286** (Admin-Tor, AP2) und **287** (Karten „Was hier gilt" außerhalb von Verwaltung und Betrieb, Schritt 17) mit dieser Fassung eingetragen; **288** und **289** mit der Mockup-Runde M-P5c-02; der Kopf des Backlogs nennt **290** als nächste freie Nummer. Alle übrigen Einträge in Rahmenplan und Backlog stehen als Einschub in 7 und 8 und werden **mit AP1** eingespielt (F-P5c-50) |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Versionsstufe (Vorschlag) | Prüfstand | Migration | Commit | Abnahmezahlen |
> |---|---|---|---|---|---|---|
> | M-P5c-02 Mockup-Runde | **gebaut 23.09.2026, Freigabe offen** | — (nur `docs/`) | — | — | | Überlauf 0 in 6 Bildschirmdateien; QR 2 von 2 gelesen; Codeblatt 1 Seite (74 %); Blätter auf Staging je 1 Seite (93 %, 71 %; Härtefall 1013 von 1017 px); Kontraste 8 von 8 gleich mit `kontrast.py` |
> | AP1 Banner und Ankündigung | offen | Neben | neben | nein | | |
> | AP2 Protokollseite und Archiv | offen | Neben | neben | nein | | |
> | AP3 Fehlerprotokoll | offen | Neben | neben | nein | | |
> | AP4 Support-Rolle | offen | Neben | **haupt** | **ja** (ENUM `users.role`) | | |
> | AP5 Zweitfaktor und Bus-Faktor | offen | Neben | **haupt** | **ja** (TOTP-Spalten, Codetabelle) | | |
> | AP6 Health | offen | Neben | neben | nein | | |
> | AP7 Statistik mit drei Reitern | offen | Neben | **haupt** | **ja** (Index) | | |
> | AP8 R39-Rest | offen | **Haupt** | haupt | **ja** (zerstörend, Nutzlast) | | |
> | AP9 Aufräumen der Einstellungen | offen | Neben | neben | nein | | |
> | ~~AP10 Bounce-Postfach~~ | **entfällt** (E-P5c-51) | — | — | — | — | — |
> | AP11 Abschluss | offen | — | haupt | — | | |

---

## 0. Auftrag und Umfang

**Anlass.** Dritter Teil von P5 (R82): was der Betrieb **über** den Konten
braucht — Rollen (Support, Zweitfaktor), das lesbare Protokoll (10b baute den
Schreibweg), Banner, Fehlerprotokoll, Health, das Betriebslage-Dashboard in
seinem festen Minimalumfang (in der Oberfläche heißt es **„Statistik"** —
E-P5c-18; „Betriebslage" bleibt der Arbeitsname aus R38), der Rest von R39,
und — nach Entscheidung des Auftraggebers vom 20.09.2026 — das **Aufräumen
der Einstellungen**: Menü, Texte, Druckseiten.

**Was dieses Konzept liefert:** Befund am Stand `862ca7f` (Fassung 1) und
seine Nachmessung am Stand `8fd4553`/`8ae873c` (Fassung 2, 1.5),
Festlegungen als E-Einträge, zehn Arbeitspakete mit Abnahmezahlen (AP10 ist
entfallen), zwei Mockup-Aufträge, Prüfprotokoll-Soll nach der Prüfkette
(`docs/Pruefablauf.md`), Einschübe.

**Was es nicht liefert:** Diagramme und freie Zeiträume in der Statistik
(Nr. 122 → Schritt 17); Sitzungsbindung, „Gerät merken" beim Zweitfaktor und
Serverschlüsselwechsel (Schritt 18, Nr. 242, 247); Änderungen an Uhr und
Android (keine nötig — Health und Banner sind Server, und die Apps melden
sich mit Gerätekennung und Schlüssel an, nicht mit Passwort; F-P5c-31); die
Zusammenführung von `sicherheit_ereignisse` in `protokoll_ereignisse` (V6:
**bleibt getrennt**, E-P5c-11); Proof-of-Work (228); **das Lesen von
Rückläufern aus einem Postfach** (E-P5c-51).

**Kein Versionssprung im Konzept** (K3).

## 0a. Fassung 2 — was sich am 23.09.2026 geändert hat

**Warum eine zweite Fassung.** Fassung 1 ist am 20.09.2026 gegen Web 20.25.0
geschrieben und freigegeben worden. Bis zum Beginn der Umsetzung sind
Schritt 16 (Sitzungsablage), Schritt 15 (Zentralisierung, Web 20.26 bis
20.37.0) und PK-01 bis -04 (Prüfkette) gelaufen. Schritt 15 hat die Helfer
gebaut, an die 10c andockt; PK hat die Prüfmittel umgebaut, auf die die
Abnahmen zeigen, und die Benennung und die Fächerung neu geregelt. Ein
Konzept, das auf `tools/rollenprobe/`, die „Wortliste" und einen
„Browser-Prüfstand" zeigt, schickt die Umsetzung an Stellen, die es nicht
mehr gibt.

**Wie abgeglichen wurde.** Sechs lesende Agenten, je einer für einen Bereich
des Konzepts, haben jede konkrete Behauptung (Datei, Funktion, Zeile, Zahl,
Werkzeug, Backlog-Nummer) gegen `8fd4553` gehalten; eine Gegenlesung hat
nachgemessen, 16 Befunde widerlegt und die übrigen zu 21 Fragen verdichtet.
Die Rohbefunde mit Belegen liegen in `konzept-p5c/Abgleich-2026-09-23.md` —
**gegen dessen Abschnitt 2 lesen**, dort stehen die widerlegten. Der Befund
„Admin erreicht das Komplett-Backup" ist zusätzlich von Hand nachgeprüft.

**Was sich inhaltlich geändert hat** (Einzelheiten in 2.7):

- **AP10 entfällt**, Nr. 200 wird nicht umgesetzt (E-P5c-51).
- **Zweitfaktor mit QR-Code**, zehn Codes, Codes unabhängig vom
  Serverschlüssel, Code-Schritt vor der Sitzung (E-P5c-41, -42, -53, -54).
- **Support** sieht den Setz-Link nie und handelt nur an Konten der Rolle
  user (E-P5c-40).
- **Neue Mockup-Runde M-P5c-02** vor AP1 (E-P5c-34).
- **Reiter-Quellen, Archivinhalt, Health, Rundmail, Fehlerbehandler** sind
  festgelegt (E-P5c-38, -39, -52, -55, -56).
- **Der Datum-Zeit-Trenner ist das Komma** (E-P5c-37).
- **Die Karten „Was hier gilt" entfallen** (E-P5c-49).
- **AP8 ist größer** als in Fassung 1: ein Umbau über mindestens zehn Dateien
  und das Sicherungsformat, deshalb Hauptstufe (F-P5c-38, E-P5c-36).
- **Das Admin-Tor** für Komplett-Backup und Backup-Ziele wird in AP2
  berichtigt (E-P5c-31, Nr. 286).
- **Abnahmen** stehen nach Station und Stufe der Prüfkette (Abschnitt 4).

**Umbenennung nach `Pruefablauf.md` 7 (E-P5c-35).** Nummern werden nie
wiederverwendet; deshalb sind F-P5c-01 bis -04 nicht neu belegt.

| Fassung 1 | Fassung 2 | Was |
|---|---|---|
| F-P5c-1 bis F-P5c-4 | **Q-P5c-01 bis Q-P5c-04** | Fragen an den Auftraggeber, mit der Freigabe vom 20.09.2026 beantwortet (2.4) |
| F-P5c-5 bis F-P5c-7 | **F-P5c-05 bis F-P5c-07** | Befunde am eigenen Konzept (2.5) |
| F1 bis F7 (Abschnitt 5) | **F-P5c-08 bis F-P5c-14** | Befunde am Code |
| — | **F-P5c-15 bis F-P5c-58** | Befunde des Abgleichs (Abschnitt 5) |
| — | **Q-P5c-05 bis Q-P5c-25** | Fragen des Abgleichs, beantwortet am 23.09.2026 (2.7) |
| AP1 … AP11 | **unverändert** | 52 Verweise „10c AP<n>" stehen in Rahmenplan und Backlog |

Wer eine ältere Quelle liest, findet dort `F-P5c-7` (Rahmenplan) oder `F6`
(Mockup-LIESMICH) — gemeint sind F-P5c-07 und F-P5c-13.

---

## 1. Befund

*Abschnitte 1.1 bis 1.4 sind der Befund von Fassung 1 am Stand `862ca7f`,
berichtigt, wo die Nachmessung am 23.09.2026 etwas anderes ergab (Vermerk
„berichtigt 23.09."). Was sich seither am Code geändert hat, steht in 1.5.*

### 1.1 Protokoll und Sicherheit

- `protokoll_lib.php` (P5b AP1): `protokoll(reiter, art, text, daten,
  betroffen)`, Tabelle `protokoll_ereignisse(id, zeit, reiter, art,
  urheber_user_id, urheber_art, betroffen_user_id, text, daten)`, sechs
  Reiter (`PROTOKOLL_REITER`: verwaltung, email, jobs, sicherung, ziele,
  system; **in `schema.sql` ein ENUM**), Fristen als Konstanten (Verwaltung
  365, einstellbar 90–1 095; übrige 30), Zähler `protokoll_fehler`. **Lesbar
  ist es nicht** — nur die Zählkarte auf Betrieb → Status.
  **Berichtigt 23.09.:** `protokoll()` hat **12 Aufrufe in 7 Dateien**, und
  **alle schreiben in den Reiter `verwaltung`** — die übrigen fünf Reiter
  haben keinen einzigen Schreiber (F-P5c-16). Rollenwechsel und
  Adressänderung durch die Verwaltung schreiben nichts (F-P5c-17).
- `betrieb_sicherheit.php` (P5a AP8): Unterseite Status → Sicherheit mit
  `sicherheit_ereignisse` (Sperren, Verlangsamung, Ziel-Löschungen), 30
  Tage, Knopf „aufheben". **Berichtigt 23.09.:** Die Tabelle hat **keine
  IP-Spalte** — die IP steht im Feld `merkmal` mit dem Präfix `ip:`, die
  E-Mail-Adresse in `wer` (F-P5c-18). Die Bremse liest die Seite aus
  `devices.abgewiesen_anzahl`, die Ziel-Löschungen aus
  `sicherungsziel_dateien`.
- **`error_log()`: 75 Aufrufe in 30 Dateien** (berichtigt 23.09.; gezählt
  mit dem Tokenizer, ohne Kommentare und Zeichenketten; an `862ca7f` waren es
  77 in 32 — Schritt 15 hat vier Stellen nebenbei aufgelöst). Die
  Registerzeile Z38 steht noch auf 77 (F-P5c-21). **Kein**
  `set_exception_handler()`, ein lokaler `set_error_handler()` in
  `sicherungsziel_lib.php`. Die Fehlerseite zeigt eine achtstellige Kennung
  (`json_fehler()`), aber nicht, wohin man sie meldet (R38); **13 sichtbare
  Texte** verweisen auf „das Fehlerprotokoll des Webspace" (F-P5c-24).
- Rollen: `user`, `admin`, `betreiberin` (`ROLLEN` in `db.php`,
  `users.role` ein ENUM; `rolle_darf_verwalten()`,
  `rolle_ist_betreiberin()`). Kein Support, kein Zweitfaktor, keine
  Bus-Faktor-Prüfung. **Alle sieben `betrieb_*.php` verlangen
  `require_betreiberin()`** — **berichtigt 23.09.:** Der Menüblock Betrieb hat
  aber **zwei** weitere Einträge, `admin_komplettsicherung.php` und
  `admin_sicherungsziele.php`, und **beide verlangen nur `require_admin()`**.
  Per Direktaufruf erreicht ein Admin den Klartext-Dump der ganzen Datenbank
  (F-P5c-15, Nr. 286, E-P5c-31). Den Block Betrieb **zeigt** das Menü allein
  der BetreiberIn (R75), Verwaltung sehen Admin und BetreiberIn (R74 (1)) —
  das entscheidet den Ort des Protokolls (F-P5c-07).
- Kein Ankündigungsbanner, kein Umgebungsetikett, kein Health-Endpunkt.

### 1.2 Betriebslage

- `betrieb_statistik.php` (S8 AP4): zählt Konten, Geräte, Modelle; die
  R38-Zählung („aktiv" = `last_login` **oder** `devices.last_seen` im
  Fenster; Einsätze nach `started_at` in fünf Fenstern, ohne Demo, ohne
  Papierkorb) gibt es nicht — Nr. 192 hält fest, dass S8 anderes zählt.
  Der Index `missions(started_at)` ist nie gelegt worden (Nr. 191); der
  vorhandene führt mit `user_id`. **Berichtigt 23.09.:** Die Fenster haben nur
  eine Untergrenze (F-P5c-39); „Ohne Gerät" zählt zu niedrig (Nr. 190).
- Geräteverteilung (R42): Art, Modell, Teilenummer liegen; der Nachlöse-Job
  aus P5a hält sie aktuell. Die Auswertung gibt es seit S8 — Karten „Geräte"
  und „Gerätemodelle", je echtem Gerät, mit „Ohne Angabe". **Berichtigt
  23.09.:** Seit Schritt 15 bindet die Seite das Muster als
  `GERAET_VIRTUELL_MUSTER`; den Helfer `geraete_echt_sql()` gibt es in
  `db.php`, die Seite benutzt ihn nicht. **Herkunft je Einsatz** (`origin`,
  R64) wird nirgends ausgewertet — Nr. 80 Rest; `HERKUNFT_WERTE`
  (`geraete_lib.php`) hat **sechs** Werte: `watch`, `android`, `wear`,
  `manual`, `import`, `schnitt`. Ihre Beschriftungen stehen an zwei Stellen
  mit verschiedenen Wörtern (F-P5c-39).

### 1.3 Einstellungen, Texte, Druck

- `ui_einstellungen_punkte()` (Datenquelle), `ui_leiste_einstellungen()`
  (linkes Menü) und `ui_einstellungen_uebersicht()` (Übersicht) in `ui.php`
  (berichtigt 23.09.: Fassung 1 nannte „Z. 855 ff."; Zeilennummern wandern,
  Funktionsnamen nicht). Drei Bereiche mit heute **16 Einträgen** (5/4/7).
  Gemeint war mit Nr. 244 vor allem das **linke Menü**: Überschrift und
  Eintrag stehen beide in Bricolage 15 px; die Überschrift soll 600 gegen 400
  wiegen, Bricolage ist aber nur in **500 und 600** eingebunden (F-P5c-14).
  Dazu steht der Winkel der Überschrift dort, wo die Einträge ihr Symbol
  tragen. Die Übersicht trägt heute eine gesperrte Versalzeile in 12 px.
- Erklärtext: die Verwaltungs- und Betriebsseiten tragen je Karte zwei bis
  sechs Sätze; das Handbuch ist seit P5b AP8 aus der Anwendung erreichbar
  (`hilfe.php#abschnitt`), wird von den Karten aber nirgends verlinkt —
  Nr. 245 (**0** Verweise `hilfe.php#` in `server/`, berichtigt 23.09.).
  **Acht** dieser Seiten tragen zusätzlich eine zugeklappte Karte „Was hier
  gilt" nach R74 (5) — `betrieb_status.php` allein rund 13 Sätze (F-P5c-48).
- `betrieb_schluesselblatt.php` (S10) und `notfallblatt.php` (P5b AP9):
  Fließtext, kein Logo, Umbruch auf zwei Seiten bei langen Werten —
  Nr. 246.
- Rechtstexte: `admin_rechtstexte.php` ist seit S8 nur eine Weiterleitung;
  bearbeitet wird unter Verwaltung → **Installation**
  (`admin_installation.php`), vier Karten in der rechten Spalte eines
  Zweispalters. Eine Vorschau gibt es dort seit Web 9.11.0 (unter dem Feld,
  **gespeicherter** Stand, `rt_html()`); es fehlt das Mitlaufen beim Tippen —
  Nr. 121. `rt_html()` kennt Überschriften, Absätze, Listen und Verweise,
  **kein Fett**.
- Zeitraumübersicht zählt Winde bodengebunden nicht — Nr. 198; **wird nicht
  umgesetzt** (E-P5c-27), seit Schritt 15 ist das die Regel (E-ZE-31).
- R39-Rest: Überbleibsel zentraler Stammdaten (Nr. 168, **sieben Punkte**,
  F-P5c-38); Diensttag mit „Anderem Rettungsmittel" ohne Besatzung (Nr. 169;
  **die Ausnahme steht in `index.php`**, eine Datei `diensttag_form.php` gibt
  es nicht und gab es nie — F-P5c-53).

### 1.4 Mail

- Warteschlange, Unzustellbar-Liste, Sammelmails (P5a AP5); kein Postfach,
  das Rückläufer liest — Nr. 200. **Wird nicht umgesetzt** (E-P5c-51).
- **Nachgetragen 23.09.:** `mail_einreihen()` versucht jede Zeile sofort, bis
  zu 5 s je Nachricht; einen Weg „nur einreihen" gibt es nicht (F-P5c-29).
  18 Aufrufe in 13 Dateien.

### 1.5 Nachgemessen am 23.09.2026 — was sich seit Fassung 1 geändert hat

| Seit | Was | Wirkung auf 10c |
|---|---|---|
| Schritt 16 (Web 20.26.0) | `sitzung_lib.php`, `.sitzungen/` als achter Schutzlistenpfad | keine; der Absatz „Andockstellen zu Schritt 16" ist erledigt (F-P5c-52) |
| Schritt 15 (Web 20.27.0 bis 20.37.0) | `konfig()`, `sitzung_starten()`, `api_methode()`/`api_rumpf()`, `format_lib.php`, `db_transaktion()`, `flash_*()`, `geraete_echt_sql()`, `app_state_*()`, das Register `tools/zaehlung/register.php` in Stufe 1 | die Andockstellen am Ende dieses Dokuments; **neuer 10c-Code, der eine zweite Stelle baut, macht Stufe 1 rot** |
| Schritt 15 AP9a (Web 20.35.0, E-ZE-31) | Winde und Bergwacht: die Auswertung folgt der Betriebsart | bestätigt E-P5c-27, die Folge ist größer (2.6) |
| PK-01 bis -04 (bis Web 20.37.1) | `docs/Pruefablauf.md` (fünf Stationen, drei Stufen, Berührung → Probe, Prüfbericht), `docs/Sandbox-Setup.md`, Proben unter `tools/proben/<name>/`, die Wortliste ist die **Textprobe** mit fünf Klassen, Hausform Binnen-I, Benennung F/Q, Fächerungszeile | Abschnitt 3.1, 3.2, 4; Umbenennung 0a |
| PK-04, gemergt (PR #75) | Vollständigkeit misst gegen null | jede neue Klasse braucht eine Regel |
| Konzept TB (23.09.2026) | das Produktionstor vergleicht Baum statt Commit; läuft vor PK-05 | keine; 10c wartet ohnehin auf PK-05 |

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
IPs nur im Reiter Sicherheit. *Woraus die Reiter gefüllt werden: E-P5c-38.*

**E-P5c-03 — Archiv als Mischweg** (V4, V5, V6). Alle **7 Tage**
(einstellbar) schreibt der Job `protokoll_archiv` die Einträge des
Zeitraums als ZIP mit einer JSON-Zeilen-Datei je Reiter, versiegelt mit
`sk_versiegeln()`, nach `sicherungen/protokoll/`; der **Dateiname** trägt die
**Kennung des Serverschlüssels** (berichtigt 23.09.: hier stand „Dateiname
und ZIP-Kopf" — ein Kopf läge im Chiffrat, F-P5c-19); Aufbewahrung **365
Tage** (einstellbar), dann löscht der Job. Download entsiegelt beim
Herunterladen und wird selbst protokolliert; passt die Kennung nicht zum
aktuellen Schlüssel, sagt die Seite das und nennt das Wiederanlaufpaket.
Komplett-Backup: `protokoll_ereignisse` ja; `sicherheit_ereignisse` und
`rate_limits` **ohne Zeilen** (berichtigt 23.09.: Schema ja, Zeilen nein —
F-P5c-20); das Archiv geht mit dem Versandjob auf das Sicherungsziel
(Einstellung, Vorgabe an) und zählt bei E-P5a-03 als eigene Dateiart.
**Was ins Archiv darf, legt E-P5c-39 fest** — „ohne IP-Spalte" traf nichts,
weil es keine gibt; die technische Form E-P5c-57.

**E-P5c-04 — Schreibweg bleibt, Fehlfall bleibt** (V7): `protokoll()`
unverändert; scheitert das Schreiben, scheitert die Handlung nicht.

**E-P5c-05 — Umgebungsbanner** (20.09.2026, Nr. 243). `config.php`
`app.umgebung = ['name' => 'Staging', 'farbe' => 'rot']`, Vorgabe leer; ist
es gesetzt: Kopfleiste in **`--rot`** (Fassung 1: „Newroz-Rot"; den Namen
führt nur noch das Rahmenplan-Archiv) statt Dunkelblau, Zeile „Staging —
Testdaten, kein Echtbetrieb", Seitentitel-Präfix „[Staging]". Nie abgeleitet
(nicht aus Domain, Zweig oder Kette); die Statusseite warnt, wenn
`mail.betreff_praefix` gesetzt ist und `app.umgebung` nicht. **Derselbe
Baustein** trägt das Ankündigungsbanner (E-P5c-13). *Wo die Zeile steht, wie
der aktive Kopfpunkt auf Rot lesbar bleibt und welche Werte `farbe` annimmt:
E-P5c-55 und M-P5c-02.*

**E-P5c-06 — Erklärtext-Regel** (20.09.2026, Nr. 245, neue Grundregel in
`Design.md` 6): In der Oberfläche steht je Karte **höchstens ein Satz**,
der sagt, was hier passiert; alles Erklärende steht im Handbuch, die Karte
trägt den Verweis auf die Sprungmarke; Warnungen bleiben als Meldung,
Feldhinweise bleiben eine Zeile. **Alle** Seiten unter Verwaltung und
Betrieb werden danach überarbeitet — **nicht** der Bereich Einstellungen
(klargestellt 23.09.); ausgelagerter Text wandert ins Handbuch, nichts wird
gelöscht. *Die Karten „Was hier gilt" entfallen: E-P5c-49. Die Zählregel
legt AP9 vor der Ausgangszählung fest (F-P5c-48).*

**E-P5c-07 — Menü-Gliederung** (Nr. 244): **Entschieden in M-P5c-01
(20.09.2026):** das **linke Menü** bekommt Bereichsüberschriften nach
**Option 1 „Linie"**; die **Übersicht** bekommt je Bereich eine eigene Karte
mit **Bereichszeichen und mittigem Kopf**. Einzelheiten: E-P5c-29.

**E-P5c-08 — Druckseiten** (Nr. 246): Schlüsselblatt und Notfallblatt mit
einem Baustein `.blatt-druck` — Marke + **Kurzname der Installation**
(`instanz_kurz()`, Vorgabe „Gen-EM NAdoku"; entschieden 20.09.2026) und
Überschrift oben, Schlüssel in einer Kachel (Rahmen, Rauch, Feste Schrift,
Vierergruppen), **genau eine A4-Seite** (`@page A4`, feste Ränder,
`page-break-inside: avoid`). Ändert E-P5b-09. **Freigegeben 20.09.2026** wie
M-P5c-01f; Einzelheiten: E-P5c-30. **Ergänzt 23.09.:** Der Baustein entsteht
**in AP5** mit seinem ersten Verwender, dem Codeblatt des Zweitfaktors; AP9
stellt die beiden Blätter darauf um. Auf Staging tragen die Blätter eine
Umgebungszeile (E-P5c-50). Abnahme per PDF aus Chromium; Firefox ist
Prüfpunkt der BetreiberIn (F-P5c-42).

**E-P5c-09 — Gestaltungsvorgaben vom 17.09.2026 gelten** (Konzept P5b,
Abschnitt 6): Zeilenaktionen rechtsbündig in einer Spalte, Kartenfuß Häkchen
links / Zweitaktion rechts, alles vertikal zentriert — Abnahmekriterium
jedes neuen Bausteins. **Ergänzt 23.09.:** Texte folgen der Hausform
(Binnen-I, E-PK-26/-37); Beschriftungen kommen aus ihrem Katalog
(`RT_TEXTE`, `HERKUNFT_TEXTE`), nicht aus dem Mockup.

### 2.2 Aus dem Nachmessen (E-P5c-10 bis -24)

**E-P5c-10 — Protokollseite: Verwaltung → Protokoll** (neue Seite
`admin_protokoll.php`; Ort nach R74 (1), F-P5c-07). **Eine** Seite, **ein**
Ort; was die Rolle nicht darf, zeigt die Seite nicht: Reiter oben (nur die,
die die Rolle sehen darf), 50 je Seite mit Blättern, jede Zeile: Zeit, Art,
Urheber (Konto oder `job`/`cli`), Betroffener, Text; `daten` aufklappbar.
Kopf: der Zusagesatz (E-P5c-01). **Nur für die BetreiberIn** zusätzlich:
die Reiter Sicherheit, Ziele und System, das Archiv mit Kennung und Download,
die Kennungssuche (E-P5c-12) und der Verweis auf die Fristen (die Karte
„Protokoll" liegt in Betrieb → Servereinstellungen, E-P5c-24). Status →
Sicherheit bleibt als Kurzsicht mit dem Knopf „aufheben" und verweist auf
den Reiter Sicherheit. **Freigegeben 20.09.2026 wie M-P5c-01a**, mit drei
Abweichungen von diesem Text (ein Suchfeld statt der vier Filter, Archiv als
abgesetzter Reiter, Plakette am Handy unter dem Text) — E-P5c-26.
**Ergänzt 23.09.:** Suchfeld, Filterpillen und Seitenwahl gibt es bisher nur
als handgeschriebenes Markup in `admin_users.php`; die Protokollseite ist der
zweite Verbraucher, AP2 löst die Helfer heraus (R83, F-P5c-54). Der
Menüeintrag „Protokoll" und das Symbol `protokoll` kommen **mit AP2**, nicht
mit AP9 (F-P5c-47).

**E-P5c-11 — Reiter Sicherheit liest `sicherheit_ereignisse`.** Die
P5a-Tabelle bleibt (Zusammenführung verworfen: sie trägt IPs mit eigener
Frist und den Sperrzustand, den die Leiter braucht); der Reiter liest sie
über eine Sicht `protokoll_sicht_sicherheit()` in derselben Zeilenform.
Ein Ereignis landet in genau einer der beiden Tabellen. *Die übrigen Reiter:
E-P5c-38.*

**E-P5c-12 — Fehlerprotokoll: ein Behandler, ein Reiter.**
`set_exception_handler()` und `set_error_handler()` in `db.php` (früh, vor
jeder Seite): jede unbehandelte Ausnahme und jeder Warnungsfehler wird mit
Kennung (acht Hex, wie `json_fehler()`), Datei, Zeile, Art und gekürzter
Meldung in den Reiter **System** geschrieben — **ohne** Anfragedaten, ohne
Sitzungsinhalt, ohne IP; die Fehlerseite zeigt die Kennung und den Satz
„Melde diese Kennung an <Kontaktadresse>" (R38). Die **75
`error_log()`-Aufrufe in 30 Dateien** (berichtigt 23.09.; Fassung 1: 77 in
32) werden umgestellt (Paket 3 aus Nr. 202, hier erledigt); `error_log()`
bleibt nur als Rückfall, wenn die Datenbank nicht antwortet. Suche nach
Kennung: im Suchfeld der Protokollseite, **nur BetreiberIn** (F-P5c-06).
**Neu gefasst 23.09.:** Nicht jede Stelle kann `protokoll()` rufen — einige
melden gerade den Ausfall der Datenbank, `install.php` lädt Teile ohne
`db.php`, und `app_state_setzen()` meldet sich selbst über `error_log`
(F-P5c-22). Deshalb **ein Helfer** mit Rekursionssperre und Rückfall; der
Behandler übergeht mit `@` unterdrückte Fehler und ersetzt Werte in
Anführungszeichen; Kontaktadresse ist `instanz_kontakt()`, ist sie leer oder
die Datenbank weg, nennt die Seite nur die Kennung. Einzelheiten: E-P5c-58.

**E-P5c-13 — Ankündigungsbanner und Rundmail** (R38). `app_state`
`ankuendigung` = Text, Ton (info/warn), gültig bis; BetreiberIn setzt es in
Betrieb → Servereinstellungen; Baustein unter der Kopfleiste (derselbe wie
E-P5c-05, das Umgebungsbanner steht darüber, wenn beide da sind);
Nutzerinnen können es wegklicken (Sitzung), es kommt beim nächsten Anmelden
wieder, bis es abläuft. **Rundmail:** Knopf „als Rundmail senden" →
Warteschlange, an alle erreichbaren Konten, protokolliert (Verwaltung), mit
Sicherheitsabfrage und Vorschau der Empfängerzahl; höchstens eine je Tag.
*Platz, Speicherform und Empfängerkreis: E-P5c-55 und -56.*

**E-P5c-14 — Support-Rolle** (R38). `ROLLEN` um `support`; darf: Konten
**der Rolle user** und ihre Geräte **sehen** (Metadaten, keine Einsätze),
Setz-Link neu senden (**ohne ihn je zu sehen**), Verifikationsmail neu
senden, Gerät deaktivieren (**nur** aus, nicht wieder an), Reiter
Verwaltung und E-Mail lesen; darf nicht: einspielen, löschen, Stammdaten,
Rollen, Servereinstellungen, Rechtstexte, Konten anderer Rollen. Prüfung an
**einer** Andockstelle: `rolle_darf_support()` neben
`rolle_darf_verwalten()`; die Verwaltungsseiten fragen je Handlung, nicht je
Seite. **Berichtigt 23.09.:** Sperren/Entsperren mit Grund **besteht** seit
Web 20.17.0; neu ist nur die Verifikationsmail, und für sie gibt es noch
keine Funktion — sie entsteht in `konto_lib.php`, nur für Konten im Status
`unbestaetigt`, und **verlängert die 48-h-Frist nicht** (F-P5c-58).
Einschränkungen des Supports: E-P5c-40. Support bekommt **keine**
Betriebsmails (`ROLLEN_VERWALTUNG_SQL` bleibt) und kommt bei Wartung nicht
herein (wie heute alle außer Verwaltung).

**E-P5c-15 — Zweitfaktor: TOTP, Pflicht für Admin, BetreiberIn und
Support, Angebot für alle übrigen** (R38, Nr. 141; Q-P5c-01). RFC 6238,
SHA-1, 30 s, sechs Ziffern, ±1 Fenster. **Neu gefasst 23.09.:** Form,
Rückwege, Anmeldeweg und Datenmodell stehen in **E-P5c-41, -42, -53, -54**;
was hier in Fassung 1 stand (Geheimnis versiegelt, zehn Codes, QR aus einer
vendorierten Bibliothek, Tor wie E-P5b-15, Topf `totp`), gilt dort weiter,
**mit drei Änderungen**: Die Codes hängen nicht am Serverschlüssel
(E-P5c-42); die Code-Abfrage steht **vor** der Sitzung, nicht als Tor danach
(E-P5c-53); Einrichtung unter **Einstellungen → Profil** (einen Reiter
„Konto" gibt es nicht, F-P5c-53). Nutzerinnen können ihn dort einschalten;
im Demo-Konto ist das gesperrt.

**E-P5c-16 — Bus-Faktor** (R38): Betrieb → Status zeigt es, wenn weniger
als zwei handlungsfähige Konten mit Rolle admin oder betreiberin bestehen
oder nur eine BetreiberIn. Kein Zwang, nur Sichtbarkeit. **Neu gefasst
23.09.:** in **Orange**, nicht Rot (E-P5c-44); „handlungsfähig" ist
definiert (E-P5c-56); Ort ist `status_erhebung()` in `status_lib.php`.

**E-P5c-17 — Health-Endpunkt** (R38; Q-P5c-02). `api/health.php`, nur mit
Token aus `config.php` (`betrieb.health_token`, leer = Endpunkt aus), JSON,
HTTP 200 bei ok, 503 sonst; keine Konten-, Mengen- oder Hosterdaten;
Ratenschutz Topf `health` (60/min je IP). Kein Fremddienst wird angebunden
— der Betreiber trägt die URL in sein Monitoring ein. **Neu gefasst 23.09.:**
Felder, Antwortcodes und das Verhalten in Wartung und Überlast stehen in
**E-P5c-52** (das Feld `wartung` entfällt — der Torwächter antwortet vorher).

**E-P5c-18 — Betriebslage-Dashboard, Minimalumfang, nicht mehr** (R38,
R42; Nr. 191, 192, 80 Rest). Neu gefasst nach M-P5c-01 (20.09.2026): kein
achter Eintrag unter Betrieb und kein Name „Betriebslage" in der Oberfläche
— die Seite heißt weiter **„Statistik"** (`betrieb_statistik.php`) und
bekommt **drei Reiter** (Baustein E-P5c-25): **NutzerInnen · Einsätze ·
Geräte** (`?r=nutzer|einsaetze|geraete`, Vorgabe `nutzer`). Über den Reitern
bleiben die vier Kennzahlen; jede führt in ihren Reiter. Jeder Reiter hat
dieselbe Form — links die Tabelle „… je Zeitraum", rechts die Karte „was es
gibt" (Zeilen mit Zählplakette); gestapelt steht die Tabelle zuerst.
Layout: `.form-raster-links-breit` (3 : 2 ab 1200 px, **neue Layoutregel**).
- **NutzerInnen:** „Konten je Zeitraum" (24 h · 7 Tage · 30 Tage · 6
  Monate): **Aktiv im Zeitraum** (`last_login` **oder** `devices.last_seen`
  eines echten Geräts im Fenster — R38), Angemeldet, Neu angelegt; rechts
  „Konten" nach Rolle (**alle Rollen aus `ROLLEN`, samt Support** — AP4
  stellt die Liste um) und „Ohne Gerät" (S8, **berichtigt nach Nr. 190**).
- **Einsätze:** „Einsätze je Zeitraum" (24 h · 7 Tage · 30 Tage · 6 Monate ·
  1 Jahr): Einsätze, NutzerInnen mit Einsatz, Ø je NutzerIn mit Einsatz;
  rechts **„Herkunft der Einsätze"** nach `origin`, letzte 30 Tage, **alle
  sechs** Werte aus `HERKUNFT_WERTE`, auch mit 0, beschriftet aus einem
  Katalog `HERKUNFT_TEXTE` neben `HERKUNFT_WERTE` (F-P5c-39).
- **Geräte:** die S8-Karten unverändert („Geräte", „Geräte je Zeitraum",
  „Gerätemodelle") — **samt dem Satz, dass Wear-OS-Uhren dort bauartbedingt
  nicht erscheinen** (Z-02 vom 05.09.2026; das Mockup zeigt ihn nicht, er
  bleibt).

**Eine Zählung (entschieden 20.09.2026):** Einsätze zählen auf dieser Seite
**ab Beginn des Einsatzes** (`missions.started_at`, Pflichtfeld), ohne Demo,
ohne Papierkorb, mit **Index `missions(started_at)`** (Migration, Nr. 191);
**jedes Fenster hat eine Obergrenze** (`started_at` zwischen jetzt − Fenster
und jetzt; künftige Einsätze erscheinen nur unter „gesamt" — ergänzt
23.09.). Die Zählung nach Diensttag entfällt hier, damit auch der Name
„Bestand" — **Nr. 192 erledigt sich.** **Preis:** Die Zahlen der BetreiberIn
können um einzelne Einsätze von der Summe der NutzerInnen-Statistiken
abweichen; der Satz unter der Karte sagt, wie gezählt wird. Alle Zahlen aus
vorhandenen Spalten (R36); Browser-Zugriffe werden nicht gezählt (R36; die
User-Agent-Hälfte von Nr. 80 ist gestrichen, E-P5c-45).

**E-P5c-19 — R39-Rest.** **Neu gefasst 23.09.** Nr. 168 hat **sieben
Punkte**, sechs davon offen (F-P5c-38): `user_id NULL` in sechs Tabellen,
`user_bases` samt vier Leseverbünden, das Nutzlastfeld `user_bases` in
Export und Import, die Funktionen `dt_base_erlaubt()`, `dt_bases()`,
`dt_vehicles()` mit „eigen **oder** zentral" (35 Zeilen in 10 Dateien), die
Doku-Sätze in `Technik.md` und `Backup-Format.md`, der Rest in
`einspielen.py`. Das ist **ein Umbau über mindestens zehn Server-Dateien und
das Sicherungsformat**, keine Migration mit Vorzählung allein — deshalb
Hauptstufe (E-P5c-36). Die Migration zählt vorher, ob zentrale Einträge
existieren, und blockiert dann mit Torwächter-Meldung; nach Auskunft der
BetreiberIn tritt das nicht auf (E-P5c-48). Nr. 169: ein Diensttag mit
„Anderem Rettungsmittel" bekommt das Besatzungsfeld mit **allen Rollen der
gewählten Betriebsart** (E-P5c-47); die Ausnahme in **`index.php`** entfällt,
Kreisläufe prüfen.

**E-P5c-20 — Aufräumen der Einstellungen als ein Paket** (Nr. 244, 245,
246, 121, **253, 269**): Menü und Übersicht nach E-P5c-29; Textüberarbeitung
aller Verwaltungs- und Betriebsseiten nach E-P5c-06 mit **Zählung**;
Druckseiten nach E-P5c-08 und -30; Rechtstext-Vorschau beim Tippen nach
**E-P5c-28**; der Datum-Zeit-Trenner nach **E-P5c-37**; die Karten „Was hier
gilt" nach **E-P5c-49**.

**E-P5c-21 — Bounce-Postfach** (Nr. 200). **Entfällt** (E-P5c-51, 23.09.2026).
*Fassung 1:* Job `bounces` liest ein IMAP-Postfach, ordnet Rückläufer über
einen Schlüssel im Betreff der Warteschlange zu, drei Rückläufer →
`adresse_unzustellbar`. *Warum nicht:* F-P5c-26.

**E-P5c-22 — Berechtigungsmatrix als Datei.** `docs/Technik.md` bekommt
eine Tabelle Rolle × Handlung (user, support, admin, betreiberin) — und die
**Rollenprobe** prüft sie: jede Handlung einmal je Rolle, erwartet 200/403
wie in der Tabelle. **Berichtigt 23.09.:** Ort ist **`tools/proben/rollen/`**
(E-PK-24), Eintrag in `proben.sh` und `pruefablauf.json`, Anlass **Nr. 286**
und Nr. 149 (Grundsatz 5); die Sandbox legt **je Rolle ein Prüfkonto** an.
Matrix und Probe entstehen in **AP2** mit den drei bestehenden Rollen (Reiter
und Protokoll-Handlungen, dazu die 13 Handlungen von Komplett-Backup und
Backup-Zielen); **AP4 erweitert** um Support und alle Verwaltungshandlungen;
AP5 um den Zweitfaktor-Reset; AP9 um den Vorschau-Endpunkt (F-P5c-05).

**E-P5c-23 — Nr. 122 nach Schritt 17** (Q-P5c-04): freie Zeiträume und
Diagramme sind Statistik-Komfort, nicht Betriebslage; 10c bleibt beim
Minimalumfang aus R38 („mehr ausdrücklich nicht").

**E-P5c-24 — Zwei Einstellungskarten, keine dritte.** Betrieb →
Servereinstellungen: Karte „Protokoll" (Archivtakt, Archivfrist,
Verwaltungsfrist, Versand des Archivs) und Karte „Ankündigung"; das
Umgebungsetikett und der Health-Token liegen in `config.php`, weil sie je
Installation gelten und nie aus der Oberfläche gesetzt werden sollen (und
`config.php` eine Wiederherstellung der Datenbank überlebt). **Ergänzt
23.09.:** Die Verwaltungsfrist ist **heute schon** einstellbar, als Feld
`protokoll_frist` in der Karte „Konten" — es **zieht** in die Karte
„Protokoll" um (ein Ort, R74).

### 2.3 Ort je Funktion (K1, R74)

| Funktion | Ort |
|---|---|
| Protokoll mit Reitern und Suchfeld (je Rolle); für die BetreiberIn dazu Archiv, Download, Kennungssuche | Verwaltung → **Protokoll** (neu, AP2) |
| Fristen, Archivtakt, Archivversand, Ankündigung | Betrieb → **Servereinstellungen** (Karten „Protokoll", „Ankündigung") |
| Bus-Faktor, Protokoll-Fehlerzähler, Umgebungswarnung | Betrieb → **Status** (`status_erhebung()`) |
| Betriebslage: Konten, Einsätze, Geräte, Herkunft | Betrieb → **Statistik**, drei Reiter (kein neuer Menüeintrag) |
| Support-Handlungen, Verifikationsmail, Zweitfaktor zurücksetzen | Verwaltung → **NutzerInnen** / Kontoseite (`admin_user.php`) |
| Zweitfaktor einrichten, Wiederherstellungscodes, Codeblatt | Einstellungen → **Profil**; für Pflichtrollen ein Einrichtungstor nach der Anmeldung |
| Code-Abfrage bei der Anmeldung | `login.php`, **vor** der Sitzung (E-P5c-53) |
| Banner (Umgebung, Ankündigung) | jede Seite, im Inhalt an der Stelle des Demo-Hinweises (E-P5c-55) |
| Health | `api/health.php` |
| Rechtstexte bearbeiten, mit Vorschau beim Tippen | Verwaltung → **Rechtstexte** (eigene Seite, E-P5c-28); Verwaltung → Installation behält Name, Adressen, Logo |
| Umgebung, Health-Token | `config.php` (Beispiel in `config.example.php`) |

### 2.4 Mit der Freigabe vom 20.09.2026 beantwortet (Q-P5c-01 bis -04)

*Bis zum 23.09.2026 hießen sie F-P5c-1 bis -4.*

| # | Festlegung | Die andere Wahl | Warum so |
|---|---|---|---|
| Q-P5c-01 | TOTP **Pflicht für admin, betreiberin, support; Angebot für user** | Pflicht nur für Admins (R38/Nr. 141: „für alle Konten angeboten, für Admins Pflicht") | Nutzerinnen ohne Zweitgerät verlören sonst den Zugang; die Rollen mit Reichweite tragen die Pflicht — neu gegenüber Nr. 141 ist nur, dass BetreiberIn und Support ausdrücklich dazugehören (berichtigt 23.09.: hier stand „Pflicht für alle (Nr. 141 wörtlich)") |
| Q-P5c-02 | Health nur mit Token, minimales JSON | offen ohne Token | ein offener Endpunkt verrät Zustand jedem; Monitoring kann einen Token führen |
| Q-P5c-03 | Bounce-Postfach als **letztes** Paket, Empfohlen | weglassen | **überholt am 23.09.2026:** weggelassen (Q-P5c-25, E-P5c-51) |
| Q-P5c-04 | Nr. 122 nach Schritt 17 | in 10c | R38: Minimalumfang, „mehr ausdrücklich nicht" |

### 2.5 Korrekturen nach der Freigabe (F-P5c-05 bis -07, 20.09.2026)

*Bis zum 23.09.2026 hießen sie F-P5c-5 bis -7.*

| # | Was nicht stimmte | Festlegung | Die andere Wahl |
|---|---|---|---|
| F-P5c-05 | Die Abnahme von AP2 verlangte die Rollenprobe „4 Rollen × 7 Reiter" — Support-Rolle und Probe entstanden aber erst in AP4 | **Matrix und Rollenprobe entstehen in AP2** mit den drei bestehenden Rollen (3 × 7 = 21 Zellen); **AP4 erweitert** um Support (4 × 7 = 28) und die Verwaltungshandlungen | AP4 vor AP2 — scheidet aus, weil die Leserechte des Supports die Protokollseite aus AP2 brauchen |
| F-P5c-06 | E-P5c-12 gab die Kennungssuche „Admin und BetreiberIn"; nach E-P5c-02 (V8) sieht der Admin den Reiter System nicht | **Kennungssuche nur BetreiberIn**; die Fehlerseite nennt die Kontaktadresse des Betriebs | Admin bekommt den Reiter System |
| F-P5c-07 | E-P5c-10 legte das Protokoll unter **Betrieb** ab. Den Block Betrieb zeigt das Menü allein der BetreiberIn — Admin und Support hätten ihre Reiter nie erreicht | **Verwaltung → Protokoll**, eine Seite, Reiter nach Rolle; die Einstellkarte bleibt in Betrieb → Servereinstellungen | zwei Menüeinträge auf dieselbe Seite — widerspricht R74 |

**Ohne Inhaltsänderung berichtigt (20.09.):** Backlog-Nummern nach Weg A;
Verweis in E-P5c-02; Überschrift von E-P5c-15; Zahl der
`error_log()`-Aufrufe.

### 2.6 Aus der Mockup-Runde M-P5c-01 (E-P5c-25 bis -30, 20.09.2026)

Die Bilder und ihre Anmerkungen liegen in `konzept-p5c/mockups/`; das
`LIESMICH.md` dort führt Stand, Messwerte und die vorgeschlagenen Klassen.
**Maßgeblich für die Umsetzung ist das Bild — für die Form, nicht für die
Schreibweise** (ergänzt 23.09.: Texte aus den Mockups folgen der Hausform,
drei Mockup-Texte tun es nicht; Beschriftungen kommen aus ihren Katalogen).

**E-P5c-25 — Baustein „Reiter"** (`.reiter`, `.reiter-punkt`,
`.reiter-abgesetzt`, `.reiter-rahmen`; neuer Abschnitt in `Design.md`
Kapitel 9). Wechsel zwischen gleichrangigen Sichten **einer** Seite;
serverseitig, jeder Reiter ist ein Verweis. Orange Unterstreichung wie in
der Kopfleiste („hier stehst du"). Unter 720 px rollt die Reihe in ihrem
eigenen Behälter, mit Verlauf am Rand; die Anwendung holt den aktiven Reiter
beim Laden ins Bild. **Drei Verwender:** Protokoll (AP2 — dort entsteht der
Baustein), Statistik (AP7), Rechtstexte (AP9). **Seiten mit Reitern tragen
keine Unterpunkte in der Leiste:** `menue.js` baut keine, wenn `#inhalt`
eine `.reiter`-Reihe enthält. **Ergänzt 23.09.:** Der Baustein reicht
Attribute je Reiter durch (`data-cancel-form` für die Rückfrage bei
ungespeichertem Text in AP9, F-P5c-57).

**E-P5c-26 — Protokollseite im Einzelnen** (freigegeben wie M-P5c-01a).
**Ein Suchfeld** für Text, Konto (E-Mail von Urheber oder Betroffenem) und —
nur BetreiberIn — die achtstellige Fehlerkennung; eine Kennung wechselt von
jedem Reiter auf System. Der Konto-Filter per Kennnummer bleibt für Verweise
von der Kontoseite (`?konto=…`) und erscheint als entfernbare Plakette.
**Zeitraum** als Filterpillen (24 h · 7 Tage · 30 Tage, im Reiter Verwaltung
dazu 365 Tage), **Art** als Auswahlfeld. **Archiv** als abgesetzter Reiter
am rechten Rand, nur BetreiberIn; passt die Kennung des Serverschlüssels
nicht, sagt es eine Meldung und der Download ist gesperrt; der Knopf steht
**ohne Symbol**. **Zeile:** `.zeile` mit Plakette = Art (Wort, nicht
Schlüssel — `protokoll_lib.php` bekommt einen Katalog Art → Beschriftung und
Ton: neutral, blau, orange, rot); Zeilen mit `daten` sind ein `<details>`
(`.zeile-mehr`), der Winkel steht in der Aktionsspalte. **Unter 720 px**
rückt die Plakette unter den Text. Support sieht die Seite wie der Admin, mit
zwei Reitern. **Ergänzt 23.09.:** Zeit als „TT.MM.JJJJ, HH:MM" (E-P5c-37).

**E-P5c-27 — Nr. 198 wird nicht umgesetzt** (entschieden 20.09.2026):
bodengebunden keine Windenanzeige, auch nicht bei Bergwacht-Diensttagen.
**Bestätigt und erweitert durch E-ZE-31** (Schritt 15 AP9a, Web 20.35.0):
Die Auswertung folgt Betriebsart und Fähigkeit, das ist jetzt die Regel und
keine Lücke mehr. **Folge, damit sie dasteht:** Winden-Cycles
bodengebundener Bergwacht-Diensttage erscheinen in keiner Ansicht der
Zeitraumübersicht **und seit Web 20.35.0 auch nicht in der Tagesübersicht**;
erfasst werden sie weiter. (Fassung 1 sagte „`api/range.php` bleibt
unverändert" — Schritt 15 hat die Datei fünfmal geändert, den Artfilter
aber gelassen.)

**E-P5c-28 — Rechtstexte: eigene Seite, Vorschau rechts** (Nr. 121;
entschieden 20.09.2026). `admin_rechtstexte.php` wird wieder zur Seite;
Menüeintrag **Rechtstexte** unter Verwaltung zwischen Installation und
Demo-Konto, Zeichen `rechtstexte` (liegt im Vorrat). Reiter je Text, ab
1200 px links Feld und Stand, rechts die Karte „Vorschau", darunter
gestapelt. Die Vorschau rollt in sich, ist so hoch wie das Feld und rollt
beim Rollen im Feld anteilig mit. **Renderer bleibt `rt_html()` auf dem
Server:** Endpunkt `api/rechtstext_vorschau.php` — POST, nur Admin und
BetreiberIn, CSRF, `no-store`, Ratenschutz; Abruf 0,4 s nach dem letzten
Tastendruck, laufende Abrufe werden verworfen. Zustand als Plakette, im
Fehlfall eine Meldung. Ohne Skript bleibt die Vorschau des gespeicherten
Stands. Reiterwechsel mit ungespeichertem Text: Rückfrage. **Ergänzt
23.09.:** Die Reiter sind aus `RT_TEXTE` beschriftet — der vierte heißt
„Vereinbarung zur Auftragsverarbeitung (AVV)", nicht „AVV" wie im Bild
(Gestaltungsvorgabe 4, 17.09.2026); die Reihe rollt unter 720 px. Die
Andockstellen des Endpunkts stehen in AP9. Verwaltung hat danach **sechs**
Einträge (NutzerInnen, Konto-Backups, Installation, Rechtstexte,
Demo-Konto, Protokoll) — nicht fünf wie in M-P5c-01c. Nach dem Umzug hat
Installation keine rechte Spalte mehr; sie wird mit vorhandenen Bausteinen
neu gesetzt (F-P5c-46).

**E-P5c-29 — Menü und Übersicht** (Nr. 244, entschieden 20.09.2026).
**Leiste, Option 1 „Linie":** der Winkel der Bereichsüberschrift wandert nach
rechts, Überschrift eine Stufe größer (`--groesse-4`) in Dunkelblau,
Trennlinie und `--abstand-3` Luft vor jedem weiteren Bereich. Geändert werden
**nur** Regeln an `.leiste-gruppe`; die Diensttage-Leiste bleibt unberührt.
**Übersicht:** je Bereich eine eigene Karte, der Bereichsname ist der
Kartentitel mit Zahl der Einträge; `.uebersicht-block` und
`.uebersicht-block-erst` entfallen. Der Kartenkopf trägt ein rundes
**Bereichszeichen** (`.bereich-zeichen`), Bereichsnamen und Zahl — **als
Gruppe mittig** — auf Rauch. Zeichen: `profil`, `gruppe`, `server`.
`ui_karte_start()` bekommt die Option **`symbol`**; `Design.md` 9.1 bekommt
die Variante **„Bereichskarte"** (`.karte-bereich`). Die **Leiste bleibt
linksbündig.** Kontraste: Zeichen 13,6 : 1, Titel auf Rauch 12,5 : 1, Zahl
auf Rauch 5,3 : 1. Neuer Eintrag **„Protokoll"** (Zeichen `protokoll`, Tabler
„list") — **kommt mit AP2**. **Ergänzt 23.09.:** Option 1 hebt die
Begründung aus `Design.md` 9.25 auf („zwei Leisten mit demselben Mechanismus
sollen denselben Griff haben", E-S8-07) — für das Einstellungsmenü, nicht
für die Diensttage-Leiste; 9.25 und 9.27 werden umgeschrieben, die erzeugten
Tabellen neu erzeugt (F-P5c-45). Die Leiste wird höher: 18 Einträge nach
P5c statt 16, Abnahme der Erreichbarkeit in AP9 (F-P5c-47).

**E-P5c-30 — Druckblatt im Einzelnen** (freigegeben wie M-P5c-01f). Kopf:
farbige Bildmarke + `instanz_kurz()` in Bricolage, rechts Adresse und
Druckzeit (beim Notfallblatt dazu das Konto). Das **Schlüsselblatt** nimmt
den Logo-Standard der Installation, das **Notfallblatt** die Wahl des Kontos
— ohne Sitzung den Standard. Kachel: Rahmen in Dunkelblau trägt die
Aussage, Rauch ist Zugabe. **Nummerierte Gruppen** (zweimal acht beim
Schlüsselblatt, fünf beim Notfallblatt). „Wozu" steht an der jeweiligen
Kachel, „Wohin" in der Warnung. **Erklärtext-Regel mit Grenze:** drei
Zeilen bleiben auf dem Schlüsselblatt, der Verweis nennt zusätzlich
`docs/Handbuch.md` im Repositorium. Fußzeile mit **Webversion** auf beiden
Blättern. `@page` A4, Ränder 14 mm / 18 mm. Gemessen: Schlüsselblatt mit drei
Werten 90 % des Satzspiegels, im Härtefall 96 %; Notfallblatt 68 %; je eine
Seite. **Ergänzt 23.09.:** `.blatt-gruppen` und `.blatt-gruppe` **gibt es
schon** (Betreiber-Rückfrage, P5b AP9) — die Klassen des Druckblatts heißen
bei der Übernahme `.blatt-druck-gruppen`, `.blatt-druck-gruppen-5`,
`.blatt-druck-gruppe` (F-P5c-44). Die Schlüsselblatt-Zeile „Öffnet alles
Versiegelte" nennt die Zweitfaktor-Geheimnisse mit (E-P5c-42). Auf Staging
eine Umgebungszeile im Kopf (E-P5c-50).

### 2.7 Aus dem Abgleich vom 23.09.2026 (Q-P5c-05 bis -25, E-P5c-31 bis -58)

**Die Fragen und ihre Antworten** (alle am 23.09.2026 vom Auftraggeber):

| # | Frage | Antwort | → |
|---|---|---|---|
| Q-P5c-05 | Admin erreicht Komplett-Backup und Backup-Ziele — eigene Korrekturstufe vor 10c? | **Nein**: es gibt kein Konto mit Rolle admin; mit AP2 | E-P5c-31 |
| Q-P5c-06 | Reihenfolge PK und 10c | **PK-05 zuerst**, dann 10c; PK-06 bis -08 daneben | E-P5c-32 |
| Q-P5c-07 | Fächerungszeile | **sparsam**: nur lesende Messung und getrennte neue Dateien | E-P5c-33 |
| Q-P5c-08 | Mockups für Banner, Zweitfaktor, Support, Bus-Faktor | **M-P5c-02 vor AP1, Opus, aus dem HTML-Stand heraus, nicht frei gezeichnet** | E-P5c-34 |
| Q-P5c-09 | Benennung nach `Pruefablauf.md` 7 | **teilweise**: Q und F umbenannt, AP-Nummern bleiben | E-P5c-35 |
| Q-P5c-10 | Versions- und Prüfstufe bei Migrationen | **Nebenstufe, Prüfstand `haupt` bei Migration; AP8 Hauptstufe** | E-P5c-36 |
| Q-P5c-11 | Datum-Zeit-Trenner | **Komma** | E-P5c-37 |
| Q-P5c-12 | Woraus die Protokoll-Reiter gefüllt werden | **Sichten auf vorhandene Tabellen**, neue Schreiber nur, wo es keine Quelle gibt | E-P5c-38 |
| Q-P5c-13 | Was ins versiegelte Archiv darf | **geschwärzt**: Sicherheit ohne Merkmal und Person, E-Mail ohne Inhalt; keine eigene Löschregel auf dem Ziel | E-P5c-39 |
| Q-P5c-14 | Support und Setz-Link | **(a)**: Support sieht den Link nie und handelt nur an Konten der Rolle user | E-P5c-40 |
| Q-P5c-15 | Zweitfaktor: QR, Zahl der Codes, „Gerät merken" | **QR-Code** (Aufwand erfragt: rund 10–15 % von AP5), zehn Codes, Merken mit Nr. 242 | E-P5c-41 |
| Q-P5c-16 | Rückwege beim Zweitfaktor | **wie empfohlen**: Codes mit `password_hash()`, Rücksetzen durch BetreiberIn/Admin | E-P5c-42 |
| Q-P5c-17 | Prüfkonten und Pflicht-TOTP | **bekanntes Geheimnis**, Werkzeuge rechnen den Code; kein Abschalter | E-P5c-43 |
| Q-P5c-18 | Farbe des Bus-Faktors | **Orange** | E-P5c-44 |
| Q-P5c-19 | Herkunft je Einsatz vor der Datenschutzerklärung | **Vorbedingung gilt für die Summenzählung nicht** | E-P5c-45 |
| Q-P5c-20 | Beifang | **253, 190, 269 und die Index-Drift**; nicht 258, 276, 277 | E-P5c-46 |
| Q-P5c-21 | Nr. 169: Rollensatz | **alle Rollen der gewählten Betriebsart** | E-P5c-47 |
| Q-P5c-22 | Nr. 168: blockierte Migration | **tritt nicht auf** — eine Instanz, dort ist alles gelöscht | E-P5c-48 |
| Q-P5c-23 | Karten „Was hier gilt" | **entfallen**, Inhalt ins Handbuch | E-P5c-49 |
| Q-P5c-24 | Druckblätter auf Staging | **Umgebungszeile im Blattkopf** | E-P5c-50 |
| Q-P5c-25 | AP10: wozu IMAP? | **streichen** — es wird nur versendet | E-P5c-51 |

**E-P5c-31 — Das Admin-Tor von Komplett-Backup und Backup-Zielen wird in AP2
berichtigt** (Q-P5c-05, F-P5c-15, Nr. 286). `admin_komplettsicherung.php` und
`admin_sicherungsziele.php` verlangen `require_betreiberin()` statt
`require_admin()`, wie R75 und der Kopf der Rollen in `db.php` es sagen; der
Verweis in `admin_sicherungen.php` auf diese Seiten erscheint nur noch der
BetreiberIn. **Warum nicht sofort:** Es gibt heute kein Konto mit der Rolle
admin (Auskunft des Auftraggebers; die Migration von S8 hat alle Admins zu
BetreiberInnen gemacht) — die Lücke ist nicht ausnutzbar, solange niemand
eines anlegt. **Warum in AP2:** Die Rollenprobe entsteht dort und hat damit
ihren Anlass. Bis dahin legt niemand ein Admin-Konto an (Prüfpunkt im
Prüfdokument).

**E-P5c-32 — 10c beginnt nach PK-05** (Q-P5c-06). PK-05 baut das Tor um, das
den 10c-Pull-Request prüfen wird (Gegenlesung des Prüfberichts), und die
Prüfstand-Stufe **„neben"** ist heute nicht gebaut: `pruefablauf.json` kennt
nur `klein` und `haupt`, der Bilderlauf läuft fest mit `--stufe klein`
(F-P5c-49). Ohne sie trüge jeder 10c-Bericht eine Nebenstufe, die nichts
zusätzlich gemessen hat. **Anforderung an PK-05:** die Stufe „neben" so
bauen, wie `Pruefablauf.md` 3 sie beschreibt. PK-06 bis -08 laufen neben
10c; beide Zweige vergeben Backlog-Nummern nur aus ihrer im Backlog-Kopf
eingetragenen Spanne. Konzept TB läuft vor PK-05 und berührt 10c nicht.

**E-P5c-33 — Fächerung sparsam** (Q-P5c-07, `CLAUDE.md` 7). Gefächert wird
nur **lesende Messung** und **Umbau getrennter neuer Dateien**; die Tabelle je
Paket steht in 3.2 und wird bei Beginn jedes Pakets bestätigt. AP3 und AP9
werden bewusst **nicht** je Datei gefächert: Beide hängen an einer
gemeinsamen Stelle (Helfer und Meldesatz bzw. `Handbuch.md`), und PK-04/5 hat
gemessen, dass eine Fächerung nach Datei keine dateiübergreifende
Unstimmigkeit findet. Wer fächert, prüft je Datei, **welchem der drei
Auslieferungsstränge** sie angehört (Nr. 284).

**E-P5c-34 — Mockup-Runde M-P5c-02 vor AP1, Opus, aus dem HTML-Stand**
(Q-P5c-08). Neue Darstellungen entstehen nur nach Freigabe mit Mockup
(`CLAUDE.md` 5), und M-P5c-01 deckt Banner, Zweitfaktor, Support-Sicht und
Bus-Faktor nicht ab. **„Aus dem HTML-Stand"** heißt: Die Seiten werden aus
der örtlichen Installation mit den echten `ui_*`-Bausteinen und dem echten
`style.css` gerendert und abgespeichert; ergänzt wird nur, was neu ist. Nicht
frei gezeichnet. Auftrag: 6.2.

**E-P5c-35 — Benennung** (Q-P5c-09). Die Tabelle in 0a; neue Nummern
zweistellig; Commit-Präfix `P5c-AP<n>:`; Prüfpunkte `P-P5c-NN`.

**E-P5c-36 — Migrationen, Versions- und Prüfstufe** (Q-P5c-10). Migrationen
haben **AP4** (ENUM `users.role` um `support`), **AP5** (Spalten des
Zweitfaktors, Tabelle der Codes), **AP7** (Index `missions(started_at)`) und
**AP8** (zerstörend: `user_id NOT NULL`, `user_bases` entfällt, dazu die
Nutzlast). Versionsstufe: **Nebenstufe**, außer **AP8: Hauptstufe**
(Datenmodell plus Sicherungsformat; Vorbilder 14.0.0 und 18.0.0). **Jedes
Paket mit Migration fährt den Prüfstand mit `--stufe haupt`** — die
Plattformmatrix läuft nur dort, und genau dort sind Nr. 238 und Nr. 267
gefunden worden. Jedes solche Paket sagt ausdrücklich an: **nach dem Deploy
`update.php`, die Kette lässt die Wartung an** (`CLAUDE.md` 3), und liest neue
`users`-Spalten im Anmeldeweg mit dem Rückfall-SELECT (Muster
`auth_guard.php`, „WARUM DAS SELECT ZWEIMAL DASTEHT"), damit die Anlage vor
`update.php` nicht mit 500 steht (F-P5c-32).

**E-P5c-37 — Der Datum-Zeit-Trenner ist das Komma** (Q-P5c-11, Nr. 253):
„TT.MM.JJJJ, HH:MM", wie in den freigegebenen Mockups a und f und im
heutigen Schlüsselblatt. **AP2** übergibt `', '` ausdrücklich (die
Protokollzeile entsteht vor AP9); **AP9** setzt die Vorgabe von
`datum_zeit_text()` auf `', '` und streicht den Parameter bei den zwölf
Aufrufern, die ihn übergeben; die vier von Hand zusammengesetzten Stellen und
die Umgehung in `wartung_lib.php` ziehen mit (rund 18 Stellen in 11 Dateien,
F-P5c-11). **Benannte Ausnahme:** „ um " im Mailtext bleibt. Nr. 253 wird
berichtigt: gemeint ist ` · `, kein Gedankenstrich.

**E-P5c-38 — Woraus die Reiter gefüllt werden** (Q-P5c-12, F-P5c-16). Die
Vorbereitung sagt es selbst: „P5c erfindet das Protokoll nicht, sondern fasst
zusammen, was P5a verstreut angelegt hat."

| Reiter | Quelle |
|---|---|
| Verwaltung | `protokoll('verwaltung', …)` — heute 12 Aufrufe; **neu:** `rolle_geaendert`, `adresse_geaendert` (ohne Adressen, E-P5b-16), `setzlink_gesendet`, `geraet_umgeschaltet`, `geraet_geloescht`, `wartung_an`, `wartung_aus`, `demo_zurueckgesetzt`, `migration_ausgefuehrt`, `rundmail`, `archiv_heruntergeladen`, `frist_geaendert` (AP2), `verifikation_gesendet` (AP4), `totp_eingerichtet`, `totp_zurueckgesetzt` (AP5) |
| Sicherheit | Sicht auf `sicherheit_ereignisse` und `csp_berichte` (E-P5c-11) |
| E-Mail | Sicht auf `mail_warteschlange` — **nur Art, Zustand, Zeit**, nie Empfänger, Betreff oder Rumpf (offene Zeilen tragen Setz-Links mit gültigem Token) |
| Jobs | Sicht auf `job_laeufe` |
| Sicherung | **neu:** `protokoll('sicherung', …)` für Komplett-Backup erzeugt, heruntergeladen, eingespielt, gelöscht und Konto-Backup eingespielt — es gibt keine Tabelle dafür |
| Ziele | Sicht auf `sicherungsziel_dateien` (Versand, Löschung) |
| System | `protokoll('system', …)` aus dem Behandler und dem Helfer (AP3) |

Keine doppelte Ablage; die Sichten liefern dieselbe Zeilenform wie
`protokoll_ereignisse`. **Kein Schemawechsel:** Der ENUM von
`protokoll_ereignisse.reiter` bleibt bei sechs Werten, der siebte Reiter
liest eine andere Tabelle. Die Rollenprobe prüft, dass ein Rollenwechsel
einen Eintrag erzeugt.

**E-P5c-39 — Was ins Archiv darf** (Q-P5c-13, F-P5c-18). Das Archiv liegt 365
Tage und geht außer Haus; `sicherheit_ereignisse` führt IP- und E-Mail-Adressen
und wird bewusst nach 30 Tagen gelöscht (E-P5a-09). Deshalb:

- **Sicherheit:** nur Art, Topf, Stufe, Zeit — `merkmal` und `wer`
  **nicht**. Die Zählung bleibt nachvollziehbar, die 30-Tage-Zusage hält.
- **E-Mail:** nur Art, Zustand, Zeit (wie die Sicht, E-P5c-38).
- **System:** Meldungen mit ersetzten Werten (E-P5c-58).
- **Übrige Reiter:** wie gespeichert.
- **Auf dem Sicherungsziel keine eigene Löschregel** (rund 52 kleine Dateien
  im Jahr) — keine Migration; `sz_versand_schub()` und
  `sz_ist_sicherungsname()` lernen die dritte Dateiart.

Abnahme: `grep` im entsiegelten Archiv nach `"ip:`, nach einem Muster für
IPv4 und IPv6 und nach `@` in Adressform → je 0.

**E-P5c-40 — Support sieht den Setz-Link nie und handelt nur an Konten der
Rolle user** (Q-P5c-14, F-P5c-34). **Warum:** Geht die Mail nicht sofort
hinaus, zeigt `admin_user.php` den Link an; `pw_handling.php` behandelt ein
Konto ohne Wiederherstellungshülle als Erstvergabe und setzt Passwort und
Schlüssel ohne weiteren Nachweis. Mit dem angezeigten Link könnte der Support
ein frisch angelegtes, noch nie angemeldetes Konto übernehmen — auch eines
mit Rolle admin oder betreiberin. **Festlegung:** Für den Support löst der
Knopf nur den Versand aus; schlägt er fehl, heißt es „nicht zugestellt —
bitte an Admin oder BetreiberIn wenden". Konten anderer Rollen sieht der
Support nicht in der Liste und erreicht ihre Kontoseite nicht (403). Die
Matrix prüft beides. *Dieselbe Lage besteht heute zwischen Admin und
BetreiberIn; mangels Admin-Konten nicht ausnutzbar — Befund in F-P5c-34, keine
Änderung in 10c.*

**E-P5c-41 — Zweitfaktor mit QR-Code** (Q-P5c-15, F-P5c-37; **hebt SP-11 und
Nr. 141 in diesem Punkt auf**). SP-11 sagte „ein QR-Erzeuger wäre ein neuer
Fremdbestandteil — der Text genügt jeder Authenticator-App". Der Auftraggeber
hält das Scannen für den Normalfall, gerade bei den Pflichtrollen; der
Aufwand ist klein. Festlegung:

- **Bibliothek:** `qrcode-generator` (Kazuhiko Arase, MIT, Fassung 2.0.4), eine
  JS-Datei unter `server/assets/vendor/`, mit Herkunft und SHA-256 im Kopf,
  Eintrag in `docs/Lizenzen.md` — keine fremde Quelle zur Laufzeit.
- **Darstellung:** Nur die Modulmatrix kommt aus der Bibliothek; das SVG baut
  die Anwendung selbst, mit Klassen und Token statt `style="…"`.
- **Daneben immer** die otpauth-Adresse als Verweis und das Geheimnis in
  Base32, in Vierergruppen zum Abtippen (`ParagonIE\ConstantTime\Base32` ist
  vendoriert).
- **Zehn** Wiederherstellungscodes (Nr. 141 sagte acht), je acht Zeichen, als
  Hash (E-P5c-42), Anzeige einmalig, druckbar als Codeblatt.
- **„Gerät 30 Tage merken"** kommt mit dem Cookie-Token aus Nr. 242 in
  Schritt 18 — zwei Cookie-Mechanismen werden nur einmal gebaut.
- **Prüfbarkeit:** Ein Werkzeug liest das QR-Bild aus einem Abzug und hält den
  Inhalt gegen die angezeigte otpauth-Adresse; der Decoder `jsqr` (Apache-2.0)
  ist **nur Prüfwerkzeug** unter `tools/`, kein Laufzeitbestandteil
  (`docs/Lizenzen.md`, Abschnitt Prüfmittel). Der Scan mit einer echten App ist
  Prüfpunkt der BetreiberIn.

**E-P5c-42 — Rückwege beim Zweitfaktor** (Q-P5c-16, F-P5c-37). Das Geheimnis
hängt am Serverschlüssel (`sk_versiegeln()`). Fehlt der Schlüssel oder wird er
ersetzt, lässt sich keines mehr öffnen — und „Serverschlüssel anlegen"
verlangt eine angemeldete BetreiberIn. **Die Codes dürfen nicht an dem
Schlüssel hängen, dessen Verlust sie überbrücken sollen.** Festlegung:

- **Wiederherstellungscodes mit `password_hash()`**, unabhängig vom
  Serverschlüssel; jeder Code gilt einmal.
- **Zurücksetzen durch die Verwaltung:** die BetreiberIn für alle Rollen, ein
  Admin für Konten der Rolle user; Support-Konten nur die BetreiberIn.
  Handlung auf der Kontoseite, `protokoll('verwaltung',
  'totp_zurueckgesetzt')`, Mail an die Kontoadresse.
- **Zurücksetzen mit dem Wiederherstellungsschlüssel** (`pw_handling.php`) nur
  für Rolle user **und** nur, wenn `pat_key_check` gesetzt ist und passt; sonst
  bleibt der Zweitfaktor stehen und die Seite sagt es. Vor AP5 wird auf beiden
  Anlagen gezählt, wie viele Konten `pat_key_check IS NULL` tragen (Zuarbeit).
- **Notweg im Runbook** (`Technik.md` 7): die Spalten per SQL leeren.
- **Schlüsselblatt und Nr. 247** nennen die Zweitfaktor-Geheimnisse unter
  „alles Versiegelte".
- Der Fall „einzige BetreiberIn verliert Gerät und Codes" bleibt Nr. 249,
  Schritt 18.

**E-P5c-43 — Prüfkonten mit bekanntem Geheimnis** (Q-P5c-17, F-P5c-33). 30
Werkzeugdateien melden sich an, 15 davon mit dem Prüfkonto
`admin@gen-em.org` — eine BetreiberIn; Station D fährt den Kreislauf mit
`STAGING_KONTO`, einem Konto mit Verwaltungsrechten. Ohne Lösung wird Stufe 2
rot und kein Tag kommt mehr durch. Festlegung: Die Sandbox legt die
Prüfkonten mit einem **bekannten Geheimnis** an; die Werkzeuge rechnen den
Code selbst (HMAC-SHA1) — **ein Rechner je Sprache** (Node, Python, PHP),
nicht je Werkzeug. Für Staging trägt die BetreiberIn das Geheimnis als
Secret `STAGING_TOTP` ein (Zuarbeit, **vor dem Merge des 10c-PR**); die
Kette reicht es durch, `tools/kettenaufrufe/` wird gefahren. **Kein
Konfigurationsschalter**, der die Pflicht abschaltet — ein Schalter, der auf
Produktiv nie an sein darf und den nichts daran hindert, schwächt die
Zusage. Der Bilderlauf erkennt eine gescheiterte Anmeldung heute nur an der
Adresse `login.php`; er erkennt künftig auch den Code-Schritt (sonst droht
F-P3-AQ: grüne Zahl, Bilder der Anmeldeseite).

**E-P5c-44 — Bus-Faktor in Orange** (Q-P5c-18). `Design.md` 9.23 definiert
Rot als „es arbeitet nicht"; `install.php` legt genau eine BetreiberIn an —
jede neue Anlage wäre dauerhaft rot und der Menüzähler „Status" stünde nie
auf null. Orange in beiden Fällen (weniger als zwei handlungsfähige
Verwaltungskonten; nur eine BetreiberIn). `betreiberinnen_zahl()` zählt heute
auch gesperrte Konten — der Bus-Faktor zählt handlungsfähige (E-P5c-56).
**Nachgeprüft mit M-P5c-02 (23.09.2026):** Der Menüzähler zählt Orange
ebenso wie Rot (F-P5c-59) — die Begründung trägt damit nur gegen die rote
Farbe, nicht gegen den dauerhaften Zähler. Neu vorgelegt als **Q-P5c-30**
(6.2).

**E-P5c-45 — Herkunft je Einsatz ohne Datenschutz-Vorbedingung** (Q-P5c-19,
Nr. 80). Die Zählung sind Summen einer vorhandenen Spalte, nur für die
BetreiberIn sichtbar (R36); die Gerätemodell-Tabelle läuft seit Web 15.3.0
ohne diese Bedingung, und die Herkunft identifiziert schwächer als das
Modell. Die Vorbedingung aus Nr. 80 gilt deshalb für diese Karte nicht; das
wird in Nr. 80 vermerkt. **Die User-Agent-Hälfte von Nr. 80 wird
gestrichen:** Browser-Zugriffe werden nicht gezählt (R36).

**E-P5c-46 — Beifang** (Q-P5c-20). Mit in 10c, weil jeder klein ist und in
einer Datei liegt, die das Paket ohnehin umbaut: **Nr. 253** (Trenner, AP2/AP9),
**Nr. 190** („Ohne Gerät" zählt zu niedrig, AP7), **Nr. 269**
(`schluesselblatt.js` bleibt bei Netzausfall stumm, AP9), die **Index-Drift
`idx_missions_deleted`** (AP7, F-P5c-39). **Nicht** in 10c: Nr. 258 (drei
`api/`-Dateien am `json_out()` vorbei → Schritt 17), Nr. 276 (tote Spalte —
eine zweite zerstörende Migration neben AP8 → P8), Nr. 277 (`await` ohne
`catch` in `einstellungen.php` → Schritt 17).

**E-P5c-47 — Nr. 169: alle Rollen der gewählten Betriebsart** (Q-P5c-21).
Das Tagesrettungsmittel hat keine `vehicle_roles`; „wie jeder andere" legte
deshalb keinen Rollensatz fest. Die Betriebsart wird im Dialog ausdrücklich
gewählt (`index.php`, `adhoc_kind`), `CREW_ROLES` trägt `kind`
air/ground/both — geraten wird nichts. Abnahme etwa: „Tag Luft zeigt p1, p2,
hems, fr, other".

**E-P5c-48 — Nr. 168: zentrale Stammdaten treten nicht auf** (Q-P5c-22).
Auskunft des Auftraggebers: Es läuft eine Instanz, dort sind alle gelöscht.
Die Migration **zählt trotzdem vorher** und blockiert mit Torwächter-Meldung,
wenn sie etwas findet — das kostet nichts und schützt eine Anlage, in die
jemand eine alte Sicherung einspielt. Ein eigener Runbook-Abschnitt entfällt;
der Satz in Nr. 168, man könne zentrale Einträge „über die Verwaltung noch
löschen", wird berichtigt (seit S9/AP5b falsch). Die Vorzählung ist eine
**eigene Vorbedingung** auf `user_id IS NULL` — `migrationen_inhalt_zaehlen()`
zählt das Gegenteil (F-P5c-38).

**E-P5c-49 — Die Karten „Was hier gilt" entfallen** (Q-P5c-23, F-P5c-48).
R74 (5) schrieb „Erklärtext einheitlich als EINE zugeklappte Karte ‚Was hier
gilt' am Seitenende"; E-P5c-06 ist jünger und sagt: höchstens ein Satz je
Karte, alles Erklärende ins Handbuch. Auf den **acht** Seiten unter
Verwaltung und Betrieb entfällt die Karte, ihr Inhalt geht ins Handbuch,
jede Karte der Seite verweist auf ihre Stelle. **R74 (5) wird
umgeschrieben** (Einschub 7). Drei Seiten außerhalb von Verwaltung und
Betrieb tragen die Karte ebenfalls (`import.php`, `einsatz_form.php`,
`wiederherstellen.php`) — sie bleiben bis Schritt 17 (**Nr. 287**).

**E-P5c-50 — Druckblätter mit Umgebungszeile** (Q-P5c-24). Ein auf Staging
gedrucktes Schlüsselblatt unterscheidet sich vom Produktiv-Blatt sonst nur
durch Kurzname und Kennung; Staging hat eigene Schlüssel (Rahmenplan 6a).
Ist `app.umgebung` gesetzt, trägt der Blattkopf eine **Textzeile** mit dem
Namen der Umgebung — sie druckt auch schwarzweiß. Ändert M-P5c-01f um eine
Zeile; das Bild dazu liefert M-P5c-02.

**E-P5c-51 — AP10 entfällt, Nr. 200 wird nicht umgesetzt** (Q-P5c-25,
F-P5c-26). Die Anwendung versendet nur; „zugestellt" heißt „vom
SMTP-Server des Hosters angenommen". Einen späteren Rückläufer könnte sie nur
über ein Postfach lesen, und dafür bräuchte es IMAP — die Erweiterung gehört
seit PHP 8.4 nicht mehr zum Kern, ein eigener Client wäre ein Paket für
sich, und die Arbeitsumgebung erreicht kein IMAP. **Der Nutzen ist klein:**
Jede Adresse ist bei Registrierung oder Einladung über einen Link bestätigt
worden; tote Adressen entstehen erst später, und bei wenigen Konten sieht die
BetreiberIn die Rückläufer im Postfach der Absenderadresse. **Festlegung:**
AP10 gestrichen; Nr. 200 geschlossen als „nicht umsetzen" (wie Nr. 198: bleibt
unter *Offen* mit Vermerk, weil nichts erledigt wurde); das Handbuch sagt,
dass `mail.from` ein echtes Postfach sein sollte, das die BetreiberIn liest.
Die Zuarbeit `mail.postfach` entfällt.

**E-P5c-52 — Health im Einzelnen** (F-P5c-25). *Aus dem Abgleich, ohne
Frage an den Auftraggeber — folgt aus Hausmuster und Bestand.*

- **Wartung und Überlast antworten aus dem Tor:** `wartung_tor()` läuft beim
  Laden von `db.php`, vor jeder Zeile von `api/health.php`; in der Wartung
  bekommt jeder Aufrufer 503 `{"error":"maintenance"}`, bei Überlast die
  Antwort von `ueberlast_antwort()`. Das Feld `wartung` entfällt; das Handbuch
  sagt, was `error: maintenance` bedeutet. Fassung und Wartungszustand sind
  ohnehin öffentlich (Fußzeile, Wartungsseite).
- **Token wie `jobs.php`:** fehlend, falsch oder nicht eingerichtet →
  einheitlich **403** mit `hash_equals()` und `rate_gleiche_dauer()` — „nicht
  sagen, ob überhaupt ein Token eingerichtet ist". (Fassung 1: „ohne Token
  404" — der Unterschied 404/403 verriete genau das.)
- **Felder:** `ok` (Datenbank erreichbar **und** keine Migration ausstehend),
  `web_version`, `db`, `migration_ausstehend`, `jobs_alter_s` (Sekunden seit
  dem letzten Lauf irgendeines Jobs), `system_24h` (Einträge im Reiter System
  der letzten 24 h — **deshalb AP6 nach AP3**), `protokoll_fehler` (der
  Zähler aus `app_state`, bis zum Quittieren), `speicher_pct` (der höchste der
  drei gemerkten Prozentwerte, **ohne** Verzeichnislauf je Abruf).
- **Topf `health`** in `RATE_GRENZEN` (60/min je IP, zählt Menge, ohne
  Leiter); die Leiter-Tabelle in `Technik.md` führt ihn mit „nein".
- Das Format steht in `Technik.md` 4 (Eingang der Endpunkte), nicht in
  `JSON-Vertrag.md` (der gilt für Geräte).

**E-P5c-53 — Die Code-Abfrage steht vor der Sitzung** (F-P5c-31). `login.php`
setzt heute `$_SESSION['user_id']` direkt nach dem Passwort; ein Tor danach
„wie E-P5b-15" gilt nur für Seiten, und 20 Endpunkte unter `api/` sowie sechs
Stellen außerhalb von `auth_guard.php` werten `user_id` als „angemeldet".
Festlegung:

- Nach dem Passwort setzt `login.php` nur einen **eigenen Sitzungsschlüssel**
  (Konto, Frist fünf Minuten); `user_id` und `session_regenerate_id()` kommen
  erst nach einem gültigen Code. Ein API-Aufruf mit halber Anmeldung → 401.
- Der Code-Schritt lädt kein `unlock.js`; bei Abbruch, Sperre oder „Zurück zur
  Anmeldung" ruft er `EdCrypto.vergissAbleitungen()` — die abgeleiteten
  Hälften liegen sonst länger im Vormerkfach.
- **Das Tor in `auth_guard.php`** betrifft nur die **Einrichtung** für
  Pflichtrollen: Wer eine Pflichtrolle ohne eingerichteten Zweitfaktor hat,
  landet auf der Einrichtung; die API antwortet 403 als JSON; eigene
  Ausnahmeliste (Einrichtung, Abmelden). Das Tor ist **stumm, solange die
  Spalten fehlen** (Rückfall-SELECT, E-P5c-36) und **solange die Wartung an
  ist** — eine BetreiberIn muss `betrieb_updates.php` immer erreichen.
- Topf `totp` mit Leiter in `RATE_GRENZEN`; die Ratenprobe führt die Töpfe mit
  Leiter als geschlossene Liste (6 → 7).

**E-P5c-54 — Datenmodell des Zweitfaktors** (F-P5c-37). Spalten an `users`:
`totp_geheimnis` (20 Byte, versiegelt mit `sk_versiegeln()`, Zweck
`totp|<user_id>` — der Zweck verhindert das Umhängen), `totp_seit`
(eingeschaltet **erst nach einem bestätigten Code**), `totp_schritt` (der
letzte angenommene Zeitschritt — kein Code gilt zweimal, ±1 Fenster hieße
sonst rund 90 s Wiederholbarkeit). Tabelle
`totp_codes(user_id, hash, benutzt_am)`. **Ohne `server_key`** verweigert die Einrichtung mit Meldung.
**Demo-Konto:** Einschalten gesperrt (dieselbe Stelle, die dort Profil und
Passwort sperrt); der Demo-Reset leert die Spalten. `app_state_einmalig()`
hat in AP5 keinen Verbraucher (installationsweite Geheimnisse, nicht je
Konto).

**E-P5c-55 — Banner: Platz, Farbe, Speicherform** (F-P5c-27, -28, -30). *Die
Form entscheidet M-P5c-02; hier die Vorgaben an die Runde.*

- **Titelpräfix** in `ui_seite_start()` (45 Aufrufe in 43 Dateien),
  **Farbe der Kopfleiste** in `ui_kopf()` (eine Stelle), **die Bannerzeile an
  der Stelle des Demo-Hinweises** in `ui_leiste_ende()` — der Platz „unter der
  Kopfleiste" ist seit P3 geräumt (F-P3-G: der Hinweis verschob die klebende
  Leiste). Reihenfolge der Streifen: **Umgebung → Ankündigung → Demo →
  Datenschutz**.
- Die Seiten ohne Gerüst (Anmeldeseiten, Dokument- und Rechtstextseiten,
  `wiederherstellen.php`, `install.php`, `ui_abbruch()`) rufen dieselbe
  Funktion; die Wartungs- und die Überlastseite setzen nur den Titelpräfix.
- `farbe` ist eine **geschlossene Liste** (heute nur `rot`); ein unbekannter
  Wert wird rot und löst eine Warnung auf der Statusseite aus.
- Der **aktive Kopfpunkt** auf `--rot` braucht ≥ 3 : 1 (WCAG 1.4.11): Orange
  auf Rot erreicht nur 2,10 : 1. Die Lösung zeigt M-P5c-02; das Paar kommt in
  `kontrast.py`.
- **Ankündigung** als drei `app_state`-Schlüssel (`ankuendigung_text`,
  `_ton`, `_bis`); der Text ist höchstens 190 Byte (`APP_STATE_MAX`), das
  Formular sagt es — keine Migration. Die Rundmail verschickt den
  Ankündigungstext.

**E-P5c-56 — Drei Begriffe statt einmal „aktiv"** (R74 (6): ein Begriff je
Ding). „Aktiv" stand in Fassung 1 für drei verschiedene Mengen:

| Begriff | Wo | Menge |
|---|---|---|
| **aktiv im Zeitraum** | Statistik (E-P5c-18) | `last_login` oder `last_seen` eines echten Geräts im Fenster |
| **erreichbar** | Rundmail (E-P5c-13) | `status = 'aktiv'`, Passwort gesetzt, nicht das Demo-Konto |
| **handlungsfähig** | Bus-Faktor (E-P5c-44) | `status = 'aktiv'`, bei Pflichtrollen Zweitfaktor eingerichtet |

**E-P5c-57 — Das Archiv technisch** (F-P5c-19, -20, -55). Die Kennung des
Serverschlüssels steht im **Dateinamen** und im **Zweck** von
`sk_versiegeln()` — ein umbenanntes Archiv passt dann nicht mehr. Der Job
arbeitet **in Häppchen mit Fortsetzungsmarke** (Produktiv hat keinen Cron,
huckepack gibt es 3 s) und setzt eine Höchstgröße je Archiv; er steht im
Jobkatalog und im `jobregister`. Ablage bleibt `sicherungen/protokoll/` —
der Pfad ist durch Schutzliste, `.gitignore` und die Wache schon gedeckt, und
`edbak_kennung_gueltig()` hält den Ordner aus der Liste der verwaisten Ordner
heraus; ein anderer Ort hieße drei Stellen und die Prüfzahl 8 in `CLAUDE.md` 3.
Die Archive zählen gegen Sicherungsgrenze und Warnschwellen
(`speicher_lib.php`). **Komplett-Backup:** `sicherheit_ereignisse` und
`rate_limits` mit Schema, **ohne Zeilen**, und eine Zeile „OHNE ZEILEN: …
(Grund)" im Dumpkopf neben „NICHT ENTHALTEN: config.php" — ein Dump ohne die
Tabellen ließe nach einem Wiederanlauf `ratelimit_lib.php` scheitern.
`ZipArchive` zieht nach **`zip_lib.php`** (R83; die Registerzeile als Art
`muster`, weil die Art `aufruf` `new X(` überspringt).

**E-P5c-58 — Das Fehlerprotokoll technisch** (F-P5c-21 bis -24).

- **Ein Helfer** (Vorschlag: `system_melden()` in einer eigenen kleinen Datei,
  die **ohne `db.php`** geladen werden kann): versucht `protokoll('system')`
  nur, wenn die Datenbank geladen ist, hat eine **Rekursionssperre**
  (`protokoll()` → `protokoll_fehler_vermerken()` → `app_state_setzen()` →
  `error_log` darf nicht zurück in `protokoll()` führen) und schreibt sonst
  `error_log`. Die Rückfallstellen (Überlast, Gedränge, Torwächter, fehlende
  Migration, `install.php`-Pfad) sind benannt und bleiben Rückfall.
- **Der Behandler** in `db.php` übergeht unterdrückte Fehler
  (`!(error_reporting() & $no)` — 136 `@` in 19 Dateien) und ersetzt Werte in
  Anführungszeichen (`Duplicate entry '…'` trägt sonst Gerätekennungen und
  Adressen ins 365-Tage-Archiv).
- **Die Fehlerseite** stützt sich auf `stoerung_seite_html()` (HTML) und
  `json_out()` mit Kennung (JSON); **die Antwortform von `fehler_kennung()`
  bleibt**, auch auf dem Geräteweg (`ingest.php`
  `{"error":"server","kennung":…}`, `JSON-Vertrag.md` 5 unverändert).
- **Die 13 Texte** „… im Fehlerprotokoll des Webspace" sagen danach, wo die
  Kennung steht.
- **Z38** (`error_log(`): `decke_ziel` 2 (Helfer-Rückfall und
  `protokoll_fehler_vermerken`); `decke_jetzt` wird zu Beginn von AP3 von 77
  auf 75 gesenkt und am Ende auf 2. **Dass der Behandler da ist,** prüft eine
  Regel in `tools/quelltext/` (genau ein `set_exception_handler(` und ein
  `set_error_handler(` in `db.php`) — das Register kennt nur Decken, ein
  Entfernen bliebe dort grün. Nr. 248 wird entsprechend berichtigt.

---

## 3. Arbeitspakete

### 3.0 Reihenfolge und Voraussetzungen

**Voraussetzungen:** Schritte 16 und 15 gemergt (erfüllt, 20. und
23.09.2026); **PK-05 gemergt** (E-P5c-32); **M-P5c-02 freigegeben**;
**Backlog Nr. 288 behoben** (F-P5c-62): Ohne sie scheitert
`hochfahren.sh --neu`, und jedes Paket braucht Station B — auch für die
Migrationen von AP4, AP5, AP7 und AP8, die auf einer frisch eingerichteten
Anlage nachgemessen werden. Empfohlen als eigene Korrekturstufe vor P5c,
zusammen mit Nr. 289.

**Reihenfolge:** AP1 → AP2 → AP3 → AP4 → AP5 → AP6 → AP7 → AP8 → AP9 →
AP11. Die Gründe:

- AP1 zuerst: klein, wirkt sofort, und **die Buchführung aus Einschub 7/8
  läuft mit AP1** (F-P5c-50).
- AP2 vor AP3 (der Reiter System braucht die Seite), vor AP4 (die Rollenprobe
  entsteht in AP2), vor AP7 und AP9 (der Baustein „Reiter" entsteht in AP2).
  **Menüeintrag und Symbol „Protokoll" kommen mit AP2** (F-P5c-47).
- AP3 vor AP6 (`system_24h`, E-P5c-52).
- AP4 vor AP5 (Support vor der Pflichtprüfung) und vor AP7 (die Rollenliste
  der Statistik folgt `ROLLEN`, F-P5c-36).
- **AP5 baut `.blatt-druck`** mit seinem ersten Verwender, dem Codeblatt; AP9
  stellt die beiden Blätter darauf um (E-P5c-08).
- AP9 nach AP2 und AP7 (Texte der neuen Seiten sind mit zu überarbeiten).
- AP11 zuletzt; **AP10 entfällt**.

### 3.1 Was für jedes Paket gilt

- **Commit** je Paket, Nachricht beginnt mit `P5c-AP<n>:`; darin der
  **Prüfbericht** aus `bash tools/pruefstand/pruefen.sh` (`Pruefablauf.md` 5)
  — erzeugt, nicht geschrieben. Danach Konzept (Statusblock, Stand der
  Umsetzung) und Prüfdokument fortschreiben, **pushen** (`CLAUDE.md` 7, 8).
- **Versionsstufe, Changelog, Doku, Backlog** nach `CLAUDE.md` 2; die Spalte
  „Versionsstufe" im Statusblock ist ein Vorschlag.
- **Prüfstand-Stufe** nach E-P5c-36; ein Paket mit Migration fährt
  `--stufe haupt`. Was die Stufe nicht von selbst fährt, steht in der Abnahme
  des Pakets **mit Aufruf** (Abschnitt 4).
- **Migration** immer mit Ansage „nach dem Deploy `update.php`, die Wartung
  bleibt an" und mit dem Rückfall-SELECT für neue Spalten im Anmeldeweg.
- **Neue Probe** nur mit Zeile „Anlass: Nr. …" (Grundsatz 5), unter
  `tools/proben/<name>/`, eingetragen in `proben.sh` und `pruefablauf.json`;
  `Pruefablauf.md` 4 danach mit `bericht.py erzeugen-doku` neu erzeugen.
- **Neuer Bedienweg** nach Seite benannt
  (`tools/bedienprobe/wege/admin_protokoll.mjs`), damit E-PK-15 ihn nicht
  noch einmal umbenennen muss.
- **Neue Seite** in `tools/screenshots/seiten.json`.
- **Neue Klasse** mit Regel in `style.css` und Eintrag in `Design.md`
  (Vollständigkeit misst gegen null); Tabellen in `Design.md` mit
  `python3 tools/erzeugen/design.py alle` erzeugen.
- **Neuer Konfigurationsschlüssel** mit Kommentar in
  `server/config.example.php` und Runbook-Absatz in `Technik.md` 7.
- **Neuer Handbuch-Abschnitt** ans Ende seines Kapitels, **nicht
  eingeschoben** — Sprungmarken tragen die Abschnittsnummer, und ein
  eingeschobener Abschnitt bricht jeden Verweis still (F-P5c-43).
- **Neuer Code, der eine zweite Stelle baut, macht Stufe 1 rot** (Register);
  eine Decke wird nur angehoben, wenn es hier steht.
- **Was nicht gemessen werden konnte**, steht im Prüfdokument vorn.

### 3.2 Fächerung je Paket (E-P5c-33)

*Bei Beginn jedes Pakets bestätigen. Nie gefächert: Prüfarbeit und
erzählender Text (`CHANGELOG.md`, Kopf von `version.php`, Konzept,
Prüfdokument).*

| Paket | Gefächert | Seriell |
|---|---|---|
| M-P5c-02 | nichts | alles — eine Bildsprache |
| AP1 | nichts | alles |
| AP2 | nichts | alles — `protokoll_lib.php` ist der gemeinsame Knoten von Seite, Job und Archiv |
| AP3 | nichts — 75 Aufrufe in 30 Dateien schafft einer seriell; Helfer, Präfixe und Meldesatz sind dateiübergreifend | alles |
| AP4 | **lesend:** Bestandsaufnahme der 41 POST-Handlungen und der Seitenaufrufe der sieben `admin_*.php` als Rohfassung der Matrix (Handlung, Zeile, heutiges Tor, schreibt Protokoll ja/nein) — **2 Agenten** (Verwaltungsblock: `admin_user`, `admin_users`, `admin_sicherungen`, `admin_installation`, `admin_demo`; Betriebsblock: `admin_komplettsicherung`, `admin_sicherungsziele`) | Kern (`db.php`, Migration, `auth_guard.php`), Umbau, Rollenprobe, Matrix, Doku |
| AP5 | **Umbau neuer Dateien:** (1) `totp_lib.php` mit den RFC-Vektoren als Prüffall — 1 Agent; (2) der Code-Rechner in den Werkzeugen, **je Sprache ein Agent** (Node, Python, PHP), erst wenn die Schnittstelle feststeht; (3) das Vendoring der QR-Bibliothek samt Herkunft, Prüfsumme, `Lizenzen.md` — 1 Agent | der Anmeldeweg (`login.php`, Code-Schritt, `auth_guard.php`, `einstellungen.php`, `pw_handling.php`, `admin_user.php`), Migration, Topf, `.blatt-druck`, Prüfarbeit, Doku |
| AP6 | nichts | alles |
| AP7 | nichts | alles |
| AP8 | **lesend:** Nachmessung der Bestandsaufnahme R39 (208 Befunde vom 09.09.2026) gegen den Stand, **je Fläche A bis F ein Agent (6)** | Migration, Nutzlast, Umbau, Kreisläufe. Zeigt die Nachmessung mehr als rund 20 Codestellen, wird eine Fächerung des Umbaus je Datei bei Paketbeginn neu vorgelegt |
| AP9 | **lesend:** Ausgangs- und Endzählung „Sätze je Karte / Verweise je Karte" nach vorher festgelegter Zählregel — **4 Agenten** nach Seitengruppen (Konten; Backups; Status und Sicherheit; übrige) **plus 1 unabhängige Gegenprobe** | der Trenner (vor allem anderen), alle Texte (`Handbuch.md` ist eine Datei), `ui.php`, `style.css`, `Design.md`, Rechtstexte-Kette, Druckseiten, Prüfarbeit |
| AP11 | **lesend:** Gegenlesung der Phase, je Paket ein Agent (Muster PK-04/5e) | Prüfläufe, Prüfdokument, Einschübe, Erzähltext |

### AP1 — Banner und Ankündigung (E-P5c-05, -13, -55, -56; Bild: M-P5c-02)

**Inhalt.** Umgebungsetikett aus `config.php` (`konfig('app.umgebung')`):
Titelpräfix in `ui_seite_start()`, Farbe in `ui_kopf()`, Bannerzeile an der
Stelle des Demo-Hinweises in `ui_leiste_ende()`; die Seiten ohne Gerüst rufen
dieselbe Funktion; Wartungs- und Überlastseite nur Präfix. `farbe` als
geschlossene Liste. Statusseite warnt bei Präfix ohne Etikett und bei
unbekannter Farbe. Karte „Ankündigung" in Betrieb → Servereinstellungen (drei
`app_state`-Schlüssel, 190 Byte), Wegklicken je Sitzung. **Rundmail:**
`mail_einreihen()` lernt „nur einreihen, nicht sofort versuchen" (bleibt eine
Stelle — sonst bräuchten N Empfänger bis zu N × 5 s in einem Seitenaufruf,
F-P5c-29), Katalogeintrag `rundmail`, Empfänger „erreichbar" (E-P5c-56),
Sicherheitsabfrage mit Zahl, höchstens eine je Tag, `protokoll('verwaltung',
'rundmail')`. `config.example.php`: `app.umgebung`, `mail.betreff_praefix`
nachtragen, den Kommentar zur Kontaktadresse berichtigen (sie steht unter
Verwaltung → Installation). **Buchführung:** Einschub 7 und 8 einspielen.

**Migration:** keine. **Stufe:** Neben, Prüfstand `neben`.

**Abnahme.**
- Bilderlauf **örtlich zweimal** (Station B): mit und ohne `app.umgebung`,
  gestellt über `tools/konfig_stellen.php`; mitgemessen Anmelde- und
  Wartungsseite; 0/0/0/0; mit Etikett Präfix auf allen Seiten, Kopfleiste
  rot auf allen Seiten mit Kopfleiste.
- `kontrast.py` mit den neuen Paaren: Weiß auf `--rot` ≥ 4,5 : 1 (gerechnet
  4,78), aktiver Kopfpunkt auf Rot ≥ 3 : 1.
- **Mailprobe** (`bash tools/proben/proben.sh mail`) erweitert: N erreichbare
  Konten → N eingereihte Zeilen, die Gegenstelle nimmt N an, eine zweite
  Rundmail am selben Tag wird abgewiesen, genau 1 Protokolleintrag.
- Bedienweg `wege/betrieb_server.mjs`: Ankündigung setzen, erscheinen,
  wegklicken, nach Neuanmeldung wieder da.
- **Anmeldeseite:** Die Streifen stehen über der Karte, die Verweise darunter
  (Nr. 289 — ist sie bis AP1 nicht behoben, behebt AP1 sie mit, weil es die
  Seite ohnehin anfasst; F-P5c-63).
- **Prüfpunkte der BetreiberIn** (Prüfdokument): Staging rot nach dem Merge,
  Produktiv blau nach dem Tag, Rundmail in einem echten Postfach angekommen.

### AP2 — Protokollseite und Archiv (E-P5c-02, -03, -10, -11, -22, -25, -26, -31, -37, -38, -39, -57; Bild: M-P5c-01a)

**Inhalt.**
- `admin_protokoll.php` unter Verwaltung; **Baustein „Reiter"** mit Abschnitt
  in `Design.md` 9 und der Bedingung in `menue.js`; **Menüeintrag
  „Protokoll"** und Symbol `protokoll.svg` (Tabler „list", Pfad aus der
  Tabler-Quelle, Symbolliste, `Lizenzen.md`, `Design.md` 8 neu erzeugt).
- Suchfeld, Zeitraum-Pillen, Art-Auswahl, Seitenwahl als **Helfer in
  `ui.php`**; `admin_users.php` zieht im selben Paket um; Registerzeile mit
  Decke 0 außerhalb von `ui.php` (R83, F-P5c-54); Stilvergleich für beide
  Seiten.
- `.zeile-mehr` samt Gegenregel zu `.zeile:first-child` (F-P5c-13); Katalog
  Art → Beschriftung und Ton; Zeit mit `', '` (E-P5c-37).
- Reiter-Quellen und neue Schreiber nach E-P5c-38; Rechte nach E-P5c-02.
- **Admin-Tor** von Komplett-Backup und Backup-Zielen (E-P5c-31, Nr. 286),
  samt Nachtrag in `Technik.md`.
- Job `protokoll_archiv` (Häppchen, Höchstgröße, Jobkatalog, `jobregister`),
  Archivinhalt nach E-P5c-39, Versand als dritte Dateiart, Komplett-Backup
  „ohne Zeilen" samt Komplettprobe (Muster der Ausnahme `jobs`),
  `Backup-Format.md`; `zip_lib.php` mit `adminbackup_lib.php` als erstem
  Umzug (E-P5c-57).
- Karte „Protokoll" in Servereinstellungen; `protokoll_frist` zieht aus
  „Konten" dorthin.
- **Rollenprobe** `tools/proben/rollen/` (Anlass Nr. 286, Nr. 149), die
  Sandbox legt je Rolle ein Prüfkonto an; **Berechtigungsmatrix** in
  `Technik.md`.
- Handbuch: Abschnitt „Protokoll" ans Ende von Kapitel 11.

**Migration:** keine. **Stufe:** Neben, Prüfstand `neben`.

**Abnahme.**
- Rollenprobe: **3 Rollen × 7 Reiter = 21 Zellen**, 0 Abweichungen (user sieht
  die Seite nicht, Admin vier Reiter, BetreiberIn sieben; Archiv, Download
  und Kennungssuche nur BetreiberIn); **dazu die 13 Handlungen** von
  Komplett-Backup und Backup-Zielen: Admin 403, BetreiberIn 200; ein
  Rollenwechsel erzeugt genau 1 Eintrag.
- Archivlauf mit gestelltem Datum: ZIP mit einer Datei je Reiter, Kennung im
  Namen, im entsiegelten Inhalt **0** Treffer für `"ip:`, IPv4, IPv6 und
  Adressen; Download entsiegelt und protokolliert; Archiv nach 366 Tagen
  (gestellt) gelöscht; falsche Kennung → Meldung, Download gesperrt.
- Komplettprobe: `sicherheit_ereignisse` und `rate_limits` mit CREATE, ohne
  Zeilen, Kopfzeile vorhanden; Kreislauf `edbak` 0 unerklärt;
  `wiederherstellung` und `versandprobe` grün.
- Bilderlauf der Seite (Sicht BetreiberIn) 0/0/0/0; die Admin-Sicht belegt die
  Rollenprobe (Reiterzahl 4) und `/chrome` — **der Bilderlauf kennt keine reine
  Admin-Rolle** (F-P5c-41, im Prüfdokument vorn).
- Zeilenhöhe aufklappbarer und fester Zeilen gleich, gemessen über
  `tools/motor.mjs` bei 1440 und 390 px.
- `menue.js`: **aktiver Eintrag vorhanden UND 0 `.eintrag-unter`** — ohne
  Menüeintrag wäre „keine Unterpunkte" grün, ohne gemessen zu haben.
- Stilvergleich: die Liste deckt sich mit der Liste der geplanten Änderungen
  (`.zeile`, Reiter, Listenhelfer).

### AP3 — Fehlerprotokoll (E-P5c-12, -58)

**Inhalt.** Helfer und Behandler nach E-P5c-58; 75 Aufrufe umgestellt; die
Rückfallstellen benannt; Fehlerseite mit Meldeweg (`instanz_kontakt()`);
Kennungssuche im Suchfeld (nur BetreiberIn); die 13 Texte „Fehlerprotokoll
des Webspace" umgetextet (Handbuch, `Technik.md`); der veraltete Kommentar in
`db.php` („EINE VON ZWEI AUFRUFSTELLEN … install.php") berichtigt; Z38 wie in
E-P5c-58; die Behandler-Regel in `tools/quelltext/`; Nr. 248 berichtigt.

**Migration:** keine. **Stufe:** Neben, Prüfstand `neben`.

**Abnahme.**
- `zaehlung` (Z38): `error_log(`-Aufrufe **≤ 2**, gezählt vom Register
  (Tokenizer, ohne Kommentare und Zeichenketten) — nicht mit `grep -c`, das
  Kommentare mitzählt (roh heute 82 in 32).
- Behandler-Regel: genau 1 `set_exception_handler(`, 1 `set_error_handler(` in
  `db.php`; Gegenprobe mit entferntem Behandler → rot.
- Eine provozierte Ausnahme erscheint im Reiter System mit Kennung; die
  Fehlerseite zeigt dieselbe Kennung; ein Aufruf mit `@` erzeugt **0**
  Einträge; eine Meldung mit Wert in Anführungszeichen ist ersetzt;
  Sitzungs-, Anfrage- und IP-Daten fehlen im Eintrag (`grep` im Dump).
- Datenbank weg → Rückfall schreibt `error_log`, keine Endlosschleife (die
  Rekursionssperre greift).
- Geräteweg: `ingest.php` antwortet im Fehlfall unverändert
  `{"error":"server","kennung":…}` (Ingestprobe).
- Textprobe 0.

### AP4 — Support-Rolle (E-P5c-14, -22, -40)

**Inhalt.** Migration ENUM `users.role` um `support`; `ROLLEN`,
`rolle_darf_support()`; die Aufzählstellen, die das Register nicht sieht
(F-P5c-36): `schema.sql`, `auth_guard.php` (Wache „Verwaltung oder Support",
`rollen_auswahl`), `admin_users.php` (Sortwert), `betrieb_statistik.php`
(Liste aus `ROLLEN`), `ui.php` (Support sieht unter Verwaltung NutzerInnen und
Protokoll); Z13-Muster um `support`. Verwaltungsseiten fragen **je Handlung**
(der POST-Verteiler ist nicht zentralisiert, E-ZE-07). Verifikationsmail
(neue Funktion in `konto_lib.php`), Setz-Link ohne Anzeige, Gerät nur
deaktivieren (eigene Handlung, nur 1 → 0 — `device_toggle` schaltet heute in
beide Richtungen). `protokoll()` für Setz-Link, Geräte und Verifikationsmail.
Meldungen bleiben in derselben Antwort (Umleiten nach POST ist Nr. 250,
Schritt 17). Matrix und Rollenprobe erweitert; Sandbox-Konto für Support.

**Migration:** ja — **nach dem Deploy `update.php`, die Wartung bleibt an.**
Älterer Code normiert eine Rolle `support` über `rolle_normieren()` auf
`user` — ein Rücksetzen über AP4 hinweg ist unkritisch. **Stufe:** Neben,
Prüfstand `haupt`.

**Abnahme.**
- Rollenprobe: Reiter **4 × 7 = 28 Zellen**, dazu **Handlungen × Rollen**
  (rund 43 × 4 = 172 Zellen, die Zahl aus der Bestandsaufnahme), 0
  Abweichungen; Support erreicht Verwaltung → Protokoll mit genau zwei
  Reitern; Support sendet Setz-Link (Link **nicht** in der Antwort, auch nicht
  im Fehlfall), deaktiviert ein Gerät, reaktiviert **nicht** (403), löscht
  nicht (403), erreicht die Kontoseite eines Admins nicht (403).
- Migrationsregister und Schemaprobe grün; Plattformmatrix (Stufe `haupt`).
- Anmeldung und `betrieb_updates.php` **vor** `update.php` mit dem neuen Code
  (Rückfall-SELECT).

### AP5 — Zweitfaktor und Bus-Faktor (E-P5c-15, -16, -41 bis -44, -53, -54, -56; Bild: M-P5c-02)

**Inhalt.** `totp_lib.php` (RFC 6238); Migration (Spalten, `totp_codes`);
QR-Bibliothek vendoriert, SVG aus der Modulmatrix; Karte „Zweitfaktor" unter
Einstellungen → Profil (einrichten mit QR, Base32 und otpauth-Adresse, erst
nach bestätigtem Code eingeschaltet; Codes einmalig anzeigen; Codeblatt über
**`.blatt-druck`**, der Baustein entsteht hier nach M-P5c-01f); Code-Schritt
in `login.php` vor der Sitzung; Einrichtungstor für Pflichtrollen; Topf
`totp`; Rücksetzen durch Verwaltung und über den Wiederherstellungsschlüssel
nach E-P5c-42; Demo-Sperre und Demo-Reset; Bus-Faktor in
`status_erhebung()`; Prüfkonten mit bekanntem Geheimnis, Code-Rechner je
Sprache, Bilderlauf erkennt den Code-Schritt; Kette reicht `STAGING_TOTP`
durch (`tools/kettenaufrufe/`); `Lizenzen.md` (QR-Bibliothek; `jsqr` als
Prüfwerkzeug); Handbuch-Abschnitt; Runbook-Notweg; Nr. 141 und 247
berichtigt.

**Migration:** ja — **nach dem Deploy `update.php`, die Wartung bleibt an**;
das Einrichtungstor ist stumm, solange die Spalten fehlen. **Stufe:** Neben,
Prüfstand `haupt`.

**Abnahme.**
- RFC 6238 Anhang B: **6/6** SHA-1-Vektoren (sechsstellig = Rest modulo 10⁶
  der achtstelligen Werte), als Prüffall in der Rollen- oder einer eigenen
  Probe.
- Bedienweg `wege/einstellungen_profil.mjs`: Base32 von der Seite lesen, Code
  in Node rechnen, einschalten, abmelden, anmelden mit Code; **falscher Code**
  → abgewiesen; **derselbe Code zweimal** → abgewiesen; nach abgebrochenem
  Code-Schritt ist das Vormerkfach leer.
- QR-Inhalt = angezeigte otpauth-Adresse (Decoder `jsqr` gegen den Abzug).
- API-Aufruf mit halber Anmeldung → 401; Pflichtrolle ohne Zweitfaktor landet
  auf der Einrichtung, die API antwortet 403.
- Wiederherstellungscode einmal gültig, zweimal nicht; Rücksetzen nach
  E-P5c-42 je Rolle in der Rollenprobe (Matrix erweitert).
- Ratenprobe grün mit **7** Töpfen mit Leiter.
- Bus-Faktor als Tabelle Rollenmix → Ton (1 BetreiberIn allein → Fall 1;
  1 BetreiberIn und 1 Admin → Fall 2; 2 BetreiberInnen, eine ohne
  Zweitfaktor → Fall 3, Orange; **2 handlungsfähige BetreiberInnen** →
  keiner). Die Ampel hängt allein an „zwei BetreiberInnen handlungsfähig",
  die Zahl der Verwaltungskonten wählt nur den Text (F-P5c-60). Der Ton von
  Fall 1 und 2 folgt Q-P5c-30. Texte und Ort: M-P5c-02 (d).
- Bilderlauf aller Seiten: **0 Seiten mit Code-Schritt** unter den Bildern.
- Anmeldung und `betrieb_updates.php` vor `update.php` (Rückfall).
- Plattformmatrix, Kreislauf `edbak` (Stufe `haupt`).
- **Prüfpunkte der BetreiberIn:** Einrichtung mit einer echten
  Authenticator-App (Scan und Abtippen), Codeblatt aus Chromium und Firefox
  gedruckt, Station D grün mit `STAGING_TOTP`.

### AP6 — Health (E-P5c-17, -52)

**Inhalt.** `api/health.php` über `api_methode('GET')`, Token mit
`hash_equals()` und `rate_gleiche_dauer()`, Felder nach E-P5c-52, Topf
`health`; `config.example.php`; `Technik.md` 4 (Format) und 7 (Runbook);
Handbuch-Abschnitt für die BetreiberIn; Muster für `server/api/health.php` in
`pruefablauf.json`.

**Migration:** keine. **Stufe:** Neben, Prüfstand `neben`. **Nach AP3.**

**Abnahme.** In den vorhandenen Proben statt einer neuen: `wartungsprobe` —
Wartung an → 503 aus dem Tor, ohne Token-Prüfung; `ratenprobe` — 61 Aufrufe in
einer Minute → 429; dazu ohne Token, falscher Token, Endpunkt aus → je **403**
mit gleicher Dauer; richtiger Token → 200 mit genau den Feldern aus E-P5c-52;
Migration ausstehend → 503 mit `ok:false`. **Nicht prüfbar:** ein echtes
externes Monitoring (nur `curl`).

### AP7 — Statistik mit drei Reitern (E-P5c-18, -45, -46; Bild: M-P5c-01b)

**Inhalt.** Migration Index `missions(started_at)`; `idx_missions_deleted`
in `schema.sql` nachtragen, damit frische und migrierte Anlagen gleich sind
(zeigt `EXPLAIN`, dass er nicht gebraucht wird, entfernt ihn stattdessen eine
Migration — F-P5c-39); drei Reiter; **eine** Zählung mit Obergrenze;
„aktiv im Zeitraum" nach R38 über `geraete_echt_sql('d')`; Herkunft mit
sechs Werten aus `HERKUNFT_TEXTE`; „Ohne Gerät" nach Nr. 190; `r` in Sortier-
und CSV-Verweisen; Layoutregel `.form-raster-links-breit` und dritte Zeile in
`Design.md` 9.26; Wear-Satz bleibt; der Satz unter der Einsatzkarte und der
Handbuch-Abschnitt sagen, wie gezählt wird; R38-Statuszeile und Nr. 192 nach
*Erledigt*; Nr. 80 mit Vermerk (E-P5c-45); **Messstand-Schritt `statistik`**
in `tools/messstand/` (drei Reiter und `EXPLAIN`, Sitzung der BetreiberIn;
fehlt das Konto `messstand@…`, legt AP7 es nach E-PK-27 an); `seiten.json`
mit drei Einträgen `?r=…`.

**Migration:** ja — **nach dem Deploy `update.php`, die Wartung bleibt an.**
**Stufe:** Neben, Prüfstand `haupt`.

**Abnahme.**
- Zahlen gegen den Referenzbestand **in einem Nicht-Demo-Konto**
  (`einspielen.py --konto …`) nachgerechnet, SQL im Prüfdokument; im
  Demo-Konto 0 (belegt den Ausschluss); Papierkorb ausgeschlossen, Differenz
  benannt; die vier künftigen Diensttage des Bestands erscheinen in keinem
  Fenster.
- Differenz zur alten Diensttag-Zählung einmal ausgewiesen und erklärt.
- `EXPLAIN` zeigt den neuen Index, lokal und (Prüfpunkt) auf Staging.
- Messstand: **jeder Reiter < 1 s bei 5 000 Einsätzen**.
- Überlauf **in** `.tabelle-scroll` (scrollWidth − clientWidth) bei 1200,
  1280, 1366 und 1440 px über `tools/motor.mjs` — der Bilderlauf kennt 1200 und
  1366 nicht und misst nur den Überlauf der Seite (F-P5c-41).
- Sortieren bleibt im Reiter; Betrieb hat weiter sieben Einträge.

### AP8 — R39-Rest (E-P5c-19, -47, -48)

**Inhalt.** Nr. 168 alle offenen Punkte (F-P5c-38): Migration mit eigener
Vorbedingung (zählt `user_id IS NULL` je Tabelle, blockiert mit
Torwächter-Meldung), danach `user_id NOT NULL` in sechs Tabellen und
`user_bases` samt Leseverbünden entfernt; die „eigen oder zentral"-Abfragen
entschlackt; **Nutzlastversion** in `backup_lib.php` angehoben, alte Pakete
werden weiter gelesen (`user_bases` still überlesen); `Technik.md`,
`Backup-Format.md`, `einspielen.py`. Die **Bestandsaufnahme R39** ist die
Fundliste und wird mit AP8 gelöscht, wie ihr Kopf es sagt. Nr. 169 nach
E-P5c-47 (`index.php`, `diensttag_lib.php`, `api/day.php`,
`validate_lib.php`, Handbuch). **Runbook:** Ein Rücksetzen über AP8 hinweg
braucht den Rückfallstand aus dem Komplett-Backup — älterer Code läuft auf
fehlende Tabellen und Spalten.

**Migration:** ja, **zerstörend** — **nach dem Deploy `update.php`, die
Wartung bleibt an.** **Stufe:** **Haupt**, Prüfstand `haupt`.

**Abnahme.**
- Migration auf dem Referenzbestand: Vorzählung im Protokoll, **0** gelöschte
  Zeilen mit Inhalt; eine gestellte zentrale Zeile → Migration blockiert mit
  Meldung.
- Kreisläufe `csv`, `edbak` **und `edbak-alt`** (`kreislauf.py --art …`) 0
  unerklärt — die Nutzlast ändert sich; eine Sicherung im alten Format spielt
  ein.
- `user_bases`: in `server/` ohne `version.php` und ohne die zwei gelaufenen
  Migrationen **15 → 0** (außer der neuen Löschmigration); in `docs/` ohne
  Changelog, Backlog und `konzepte/` **3 → 0**. Null über alles ist ohne
  Geschichtsfälschung nicht erreichbar (107 Treffer, F-P5c-38).
- Schemaprobe, Migrationsregister, Plattformmatrix grün.
- Nr. 169: Tag Luft zeigt die Rollen der Luft, Tag Boden die des Bodens
  (Bedienweg `wege/index.mjs`).

### AP9 — Aufräumen der Einstellungen (E-P5c-06 bis -08, -20, -28 bis -30, -37, -46, -49, -50; Bilder: M-P5c-01c, -01d, -01f, -02)

**Inhalt.**
1. **Zuerst der Trenner** (E-P5c-37) — er steckt in 8 der 15 Seitendateien;
   Register Z26 nachziehen.
2. **Zählregel** vor der Ausgangszählung festlegen (F-P5c-48): was ein Satz
   ist; welche Elemente mitzählen (Kartentext ja; `klein` als „eine Zeile"
   getrennt gezählt; Meldungen nein; `.seiten-erklaerung` als Seitenkopf mit
   Soll 1); `Design.md` 9.0 und Kapitel 10 Regel 5 an E-P5c-06 angleichen.
3. Erklärtext-Regel als Grundregel 6.x in `Design.md`; Textüberarbeitung
   aller Verwaltungs- und Betriebsseiten; Karten „Was hier gilt" entfallen
   (E-P5c-49); Handbuch-Nachträge **ans Kapitelende**, jede Karte mit
   Verweis auf ihre Sprungmarke.
4. **Ankerprüfung** für `hilfe.php#…` gegen `doku_marke()` der
   Handbuch-Überschriften, als Zusatz zu `linkprobe` im Tor (Anlass
   F-P5c-43).
5. Leiste nach Option 1, Übersicht mit Bereichskarten; `Design.md` 9.25 und
   9.27 umgeschrieben, `ABWEICHEND` in `tools/erzeugen/design.py` angepasst,
   Tabellen neu erzeugt.
6. Druckseiten auf `.blatt-druck` (aus AP5) mit den umbenannten
   Gruppenklassen und der Umgebungszeile (E-P5c-50); Nr. 269.
7. **Seite Rechtstexte** mit Vorschau-Endpunkt: `api_methode('POST')`,
   `csrf_check()`, `api_rumpf()` mit `max_bytes` ≥ 4 × 60 000, `rt_pruefen()`,
   **neuer Topf** in `RATE_GRENZEN`, im Browser `EdApi.postJson`; die zwei
   sichtbaren Verweise auf Installation umhängen (`rechtstext_seite.php`,
   `betrieb_sicherheit.php`); Z26 nachziehen; Reiter aus `RT_TEXTE`;
   `data-cancel-form` an den Reitern; Umzug der vier Karten aus Installation,
   Installation danach mit vorhandenen Bausteinen (einspaltig oder
   `.karten-raster`, keine neue Darstellung).
8. `seiten.json`: Einstellungen-Übersicht (BetreiberIn) und
   `admin_rechtstexte.php`.

**Migration:** keine. **Stufe:** Neben, Prüfstand `neben`.

**Abnahme.**
- Zählung vorher/nachher je Seite nach der Zählregel, mit unabhängiger
  Gegenprobe: Sätze je Karte ≤ 1, Verweise ≥ 1 je Karte mit ausgelagertem
  Text, 0 Karten „Was hier gilt" unter Verwaltung und Betrieb.
- Ankerprüfung: jeder `hilfe.php#…`-Verweis trifft eine Überschrift; mit
  eingebautem Fehler rot.
- PDF beider Blätter aus **Chromium** = 1 Seite, das Schlüsselblatt mit drei
  Werten; **Firefox ist Prüfpunkt der BetreiberIn** (Playwright erzeugt PDF nur
  mit Chromium, F-P5c-42). **Dazu der Härtefall mit Umgebungszeile**
  (drei Werte, Kurzname 83, Adresse 62 Zeichen): im Mockup 1013 von 1017 px —
  4 px Luft. Reicht es im Bau nicht, rückt die Zeile als erste Zeile in
  `.blatt-kopf-rechts` (M-P5c-02, LIESMICH 8.4).
- Notfallblatt: „Einstellungen → Profil" statt „→ Konto", zweimal
  (F-P5c-61); `grep -c "Einstellungen → Konto" server/notfallblatt.php` = 0.
- Kontraste (`kontrast.py`) von Kartenkopf und Reiterschrift ≥ 4,5 : 1.
- **Stilvergleich** (`bash tools/stilvergleich/gegen.sh`): die Liste deckt sich
  mit den geplanten Änderungen (Regeln an `.leiste-gruppe`,
  `.uebersicht-block*` entfallen, neue Klassen); **die Diensttage-Leiste hat
  keine Abweichung** — belegt über Stilvergleich und `vergleichen.py
  --nur-text` für die `index.php`-Seiten, nicht über den Bilderlauf (der
  vergleicht nicht mit einem früheren Stand).
- Erreichbarkeit der Leiste: BetreiberIn bei 1280 × 900 und 1280 × 720, Soll
  **18 von 18** Einträgen bei der Vorgabe offener Blöcke.
- Rollenprobe um den Vorschau-Endpunkt erweitert (Admin und BetreiberIn 200,
  sonst 403); Vorschau ohne Skript = gespeicherter Stand; Lesezeichen
  `admin_rechtstexte.php` führt auf die Seite; Rückfrage bei ungespeichertem
  Text beim Reiterwechsel (Bedienweg `wege/admin_rechtstexte.mjs`).
- Bilderlauf acht Breiten 0/0/0/0; Textprobe 0; Vollständigkeit 0.

### ~~AP10 — Bounce-Postfach~~ — entfällt (E-P5c-51)

### AP11 — Abschluss

Prüfstand `haupt` über den ganzen Zweig; Kreisläufe `csv`, `edbak`,
`edbak-alt`; Textprobe; Bilderlauf; Rollenprobe; Bedienprobe; Gegenlesung der
Phase (lesend, gefächert); **Prüfdokument** (K9) mit den Prüfpunkten
`P-P5c-NN` und „nicht geprüft" vorn; Handbuch-Kapitel Protokoll, Zweitfaktor,
Statistik vollständig; Einschübe in Rahmenplan (Erledigt-Zeile, Reste,
Abschnitt 10) und Backlog; **Pull Request** — die BetreiberIn mergt
(`CLAUDE.md` 8: eine Instanz mergt nie). Nach dem Merge: `update.php` für die
Migrationen aus AP4, AP5, AP7 und AP8 (Rahmenplan 6).

---

## 4. Prüfprotokoll-Soll

*Nach `docs/Pruefablauf.md`: Station A = Arbeitsumgebung, B = Prüfstand, C =
Tor, D = Staging, BetreiberIn = Prüfpunkt im Prüfdokument. „Automatisch" heißt:
die Berührung löst es über `pruefablauf.json` aus, oder es ist ein billiger
Riegel.*

| Mittel | Aufruf | Station / Stufe | Pakete | Soll |
|---|---|---|---|---|
| Prüfbericht | `bash tools/pruefstand/pruefen.sh [--stufe haupt]` | B | alle | in der Commit-Nachricht; bei Migration Stufe `haupt` |
| Billige Riegel (Syntax, Textprobe, Vollständigkeit, Kontraste, Linkprobe, Register, Spaltenregister, Kettenaufrufe …) | automatisch | B und C | alle | 0 / 0 |
| Rollenprobe (neu) | `bash tools/proben/proben.sh rollen` | B | AP2, AP4, AP5, AP9, AP11 | Matrix vollständig, 0 Abweichungen |
| Mailprobe | `bash tools/proben/proben.sh mail` | B | AP1 | Rundmail N/N, zweite abgewiesen |
| Wartungs- und Ratenprobe | automatisch (`wartung_lib.php`, `auth_guard.php`, `ratelimit_lib.php`) | B | AP3, AP5, AP6 | 503 aus dem Tor; 429; 7 Töpfe mit Leiter |
| Komplett-, Versand-, Wiederherstellungsprobe | automatisch (Backup-, Komplett-, Zieldateien) | B | AP2, AP8 | grün; Tabellen ohne Zeilen benannt |
| Kreisläufe `csv`, `edbak`, `edbak-alt` | `python3 tools/referenzdatensatz/vergleich/kreislauf.py --art …` | B | AP2, AP8, AP11 | 0 unerklärt |
| Migrationsregister, Schemaprobe | automatisch (`schema.sql`, `migration_lib.php`) | B und C | AP4, AP5, AP7, AP8 | grün |
| Plattformmatrix | Stufe `haupt` | B | AP4, AP5, AP7, AP8, AP11 | je Paar grün |
| Messstand | Schritt `statistik` in `tools/messstand/` | B | AP7 | jeder Reiter < 1 s bei 5 000 |
| Bilderlauf | automatisch; `node tools/screenshots/aufnehmen.mjs` | B | AP1 (zweimal), AP2, AP5, AP7, AP9, AP11 | 0/0/0/0; 0 Seiten mit Code-Schritt |
| Eigene Messungen über `tools/motor.mjs` | im Prüfdokument mit Verfahren | A | AP2 (Zeilenhöhe), AP7 (Überlauf in `.tabelle-scroll`), AP9 (Erreichbarkeit der Leiste) | Zahl je Breite |
| Bedienprobe (nach Seite benannte Wege) | `tools/bedienprobe/` | B | AP1, AP2, AP5, AP8, AP9 | alle Wege grün |
| Stilvergleich | `bash tools/stilvergleich/gegen.sh` | B | AP2, AP9 | Liste = geplante Änderungen; Diensttage-Leiste 0 |
| Kontrastmessung neuer Flächen | `python3 tools/screenshots/kontrast.py` | B | AP1, AP2, AP9 | Schrift ≥ 4,5 : 1, Kopfpunkt ≥ 3 : 1 |
| PDF der Blätter | Chromium über Playwright | A | AP5, AP9 | je 1 Seite |
| RFC-6238-Vektoren | Prüffall in der Probe | B | AP5 | 6/6 |
| QR-Inhalt | Abzug + `jsqr` | A | AP5 | = otpauth-Adresse |
| Behandler-Regel | `tools/quelltext/` | B und C | AP3 | 1 und 1 in `db.php` |
| Zählung `error_log(` | Register Z38 | B und C | AP3 | ≤ 2 |
| Ankerprüfung `hilfe.php#` | Zusatz zu `linkprobe` | B und C | AP9 | 0 tote Anker |
| Zählung Erklärtext | Zählregel aus AP9 | A (lesend, gefächert) | AP9 | Sätze je Karte ≤ 1, Verweise ≥ 1 |
| Kettenaufrufe | automatisch (`.github/`, `tools/`) | B und C | AP5 | 0/0 |

**Nicht prüfbar, so gesagt — gehört im Prüfdokument an den Anfang:**
Mailzustellung in ein echtes Postfach (nur Port 443); echte
Authenticator-App (echtes Gerät); PDF aus Firefox (Playwright druckt nur mit
Chromium); die reine Admin-Sicht im Bilderlauf (keine Rolle dafür);
Staging- und Produktivansicht (Station D und E, nach Merge und Tag);
externes Monitoring (nur `curl`); der Schlüsselwechsel-Fall beim Archiv
(Schritt 18; hier nur die Kennungs-Meldung mit gefälschtem Namen); der Weg
„Deploy → Anmeldung → `update.php`" in einem Zug (Nr. 234,
`Pruefablauf.md` 8).

---

## 5. Befunde

*F-P5c-05 bis -07 stehen in 2.5. F-P5c-08 bis -14 sind die Befunde F1 bis F7
aus Fassung 1. F-P5c-15 bis -58 stammen aus dem Abgleich vom 23.09.2026; die
Belege stehen in `konzept-p5c/Abgleich-2026-09-23.md` unter der
angegebenen Kennung (A-, B-, … = Bereich und laufende Nummer).*

| # | Befund | Wie weiter |
|---|---|---|
| F-P5c-08 | `error_log()`-Aufrufe seit 16.09. von 42 auf 77 gestiegen (Fassung 1, F1) | AP3; heute 75 (F-P5c-21) |
| F-P5c-09 | Nr. 192: S8-Statistik und R38 zählen Verschiedenes (F2) | erledigt sich mit AP7 |
| F-P5c-10 | Notfallblatt ohne Logo und ohne Seitengrenze (F3) | AP9, E-P5c-08 |
| F-P5c-11 | Dieses Konzept selbst: Protokoll unter Betrieb, Rollenprobe zu früh verlangt, Kennungssuche gegen V8 (F4) | berichtigt 20.09. — 2.5 |
| F-P5c-12 | Dieses Konzept selbst, gefunden in M-P5c-01: `doku_html()`, Rechtstexte-Weiterleitung, vier statt sechs Herkünfte, Geräteverteilung, Nr. 244 (F5) | berichtigt 20.09. |
| F-P5c-13 | `.zeile:first-child` trifft jedes `<summary class="zeile">` — aufklappbare Zeilen 13 px niedriger (F6) | Gegenregel mit AP2 |
| F-P5c-14 | Bricolage nur in 500 und 600 eingebunden; `font-weight:400` wirkt als 500 — Ursache von Nr. 244 (F7) | AP9, Vermerk in `Design.md` |
| F-P5c-15 | **Admin erreicht Komplett-Backup und Backup-Ziele** per Direktaufruf (`require_admin()`), samt Klartext-Dump; Prüfdokument S8 P-01 hatte die Nachmessung verlangt (C-09, von Hand nachgeprüft) | E-P5c-31, Nr. 286, AP2 |
| F-P5c-16 | `protokoll()` schreibt nur in den Reiter Verwaltung (12 in 7); fünf Reiter ohne Schreiber (B-01) | E-P5c-38 |
| F-P5c-17 | Rollenwechsel, Adressänderung durch die Verwaltung, Setz-Link, Geräte, Wartung, Demo-Reset, Migration schreiben nichts ins Protokoll (B-02, C-10) | E-P5c-38, AP2, AP4 |
| F-P5c-18 | `sicherheit_ereignisse` hat keine IP-Spalte; IP in `merkmal`, Adresse in `wer`, 30-Tage-Zusage (B-08) | E-P5c-39 |
| F-P5c-19 | `sk_versiegeln()` liefert eine Zeichenkette — ein „ZIP-Kopf" läge im Chiffrat; ein Guss sprengt 64 MB (B-07) | E-P5c-57 |
| F-P5c-20 | Das Komplett-Backup hat keine Ausnahmeliste; ohne die Tabellen scheitert nach einem Wiederanlauf `ratelimit_lib.php` (B-06) | E-P5c-57 |
| F-P5c-21 | `error_log`: 75 in 30 (Tokenizer); Z38 steht auf 77 (B-14, F-16) | E-P5c-58, AP3 |
| F-P5c-22 | Stellen, die nicht nach `protokoll()` umgestellt werden können: Datenbankausfall, `install.php` ohne `db.php`, Rekursion über `app_state_setzen()` (B-15) | E-P5c-58 |
| F-P5c-23 | 136 `@`-Unterdrückungen; Meldungen tragen Werte (B-17) | E-P5c-58 |
| F-P5c-24 | 13 sichtbare Texte „Fehlerprotokoll des Webspace" (B-16) | AP3 |
| F-P5c-25 | Health: `wartung_tor()` antwortet vor dem Endpunkt, das Feld `wartung` wäre nie wahr; 404/403 verriete, ob ein Token eingerichtet ist (A-02, A-15) | E-P5c-52 |
| F-P5c-26 | Eine zugestellte Warteschlangenzeile löscht Empfänger, Betreff, Rumpf — ein Rückläufer wüsste nicht, an wen; `ext-imap` fehlt seit PHP 8.4; die Arbeitsumgebung erreicht kein IMAP (A-03, A-12, F-15) | E-P5c-51 — AP10 entfällt |
| F-P5c-27 | `ui_seite_start()` trägt keine Kopfleiste; die Kopfleiste kommt aus `ui_kopf()`; 8 Anmeldeseiten ohne Kopfleiste; der Platz unter der Kopfleiste ist seit P3 geräumt; bis zu vier Streifen auf Staging mit Demo (A-09, A-10) | E-P5c-55, M-P5c-02 |
| F-P5c-28 | Orange auf `--rot` 2,10 : 1, Hover-Strich 1,68 : 1 — der aktive Kopfpunkt wäre auf Rot unlesbar (A-16) | E-P5c-55, M-P5c-02 |
| F-P5c-29 | `mail_einreihen()` versucht sofort, bis 5 s je Nachricht (A-11) | AP1 |
| F-P5c-30 | `app_state` trägt 190 Byte (A-19) | E-P5c-55 |
| F-P5c-31 | Ein Tor nach der Sitzung ließe API und sechs weitere Stellen mit dem bloßen Passwort offen; das Vormerkfach im sessionStorage hielte länger (C-03, C-04). Die Apps melden sich mit Gerätekennung an — keine App-Änderung | E-P5c-53 |
| F-P5c-32 | Neue `users`-Spalten im Anmeldeweg ohne Rückfall ließen die Anlage vor `update.php` mit 500 stehen (Hotfix nach P5b) (C-05) | E-P5c-36 |
| F-P5c-33 | 30 Werkzeugdateien melden sich an, 15 mit dem BetreiberIn-Konto; Station D mit Verwaltungskonto; der Bilderlauf erkennt eine gescheiterte Anmeldung nur an `login.php` (C-06) | E-P5c-43 |
| F-P5c-34 | Setz-Link wird bei Mailfehler angezeigt; Erstvergabe ohne Nachweis — Support könnte ein frisches Konto jeder Rolle übernehmen; dieselbe Lage zwischen Admin und BetreiberIn (C-11) | E-P5c-40 |
| F-P5c-35 | Das Demo-Konto könnte den Zweitfaktor einschalten und alle Nachfolgenden aussperren (C-18) | E-P5c-54 |
| F-P5c-36 | Rollen an Stellen von Hand aufgezählt, die das Register nicht sieht (ENUM, `rollen_auswahl`, Sortwert, Statistik mit festen Schlüsseln, Menü); Z13 kennt `support` nicht (C-13, D-14) | AP4 |
| F-P5c-37 | Keine QR-Bibliothek vendoriert; E-P5c-15 wich ohne Begründung von SP-11/Nr. 141 ab (QR, Codezahl, Merken); Datenmodell offen; das Geheimnis hängt am Serverschlüssel (C-01, C-02, C-17, F-20) | E-P5c-41, -42, -54 |
| F-P5c-38 | Nr. 168 hat sieben Punkte, sechs offen — Umbau über ≥ 10 Dateien und die Nutzlast; `migrationen_inhalt_zaehlen()` zählt das Gegenteil; 107 Treffer `user_bases`, Null nur ohne Geschichte (D-05, D-06, D-07) | E-P5c-19, -48, AP8 |
| F-P5c-39 | Statistik: Fenster ohne Obergrenze; Referenzbestand im ausgeschlossenen Demo-Konto; Sortier-Verweise ohne `r`; Herkünfte in zwei Listen; Wear-Satz fehlt im Mockup; `idx_missions_deleted` nur auf migrierten Anlagen; „Ohne Gerät" zu niedrig (Nr. 190) (D-04, D-11, D-12, D-15 bis D-18) | AP7 |
| F-P5c-40 | Der Messstand hat keinen Statistikfall; das Konto `messstand@…` fehlt örtlich (D-10, F-14) | AP7 |
| F-P5c-41 | Bilderlauf: Breiten 400, 1200, 1366 fehlen; keine reine Admin-Rolle; Übersicht nicht in `seiten.json`; misst keinen Überlauf in Scroll-Behältern und keinen früheren Stand (B-10, D-13, E-10, F-11) | Abnahmen AP2, AP7, AP9; eigene Messungen |
| F-P5c-42 | Playwright erzeugt PDF nur mit Chromium (E-09, F-12) | Firefox als Prüfpunkt der BetreiberIn |
| F-P5c-43 | Sprungmarken tragen die Abschnittsnummer und brechen still; kein Prüfmittel prüft sie; heute 0 Verweise (E-05, F-10) | AP9: Kapitelende, Ankerprüfung |
| F-P5c-44 | `.blatt-gruppen` und `.blatt-gruppe` gibt es schon (Betreiber-Rückfrage) (E-06) | E-P5c-30 |
| F-P5c-45 | `Design.md` 9.25 begründet den Winkel links; 9.27 beschreibt `.uebersicht-block-erst`; die erzeugten Tabellen sind schon heute veraltet (E-11) | AP9 |
| F-P5c-46 | Installation ohne rechte Spalte nach dem Umzug der Rechtstexte (E-14) | AP9, vorhandene Bausteine |
| F-P5c-47 | Ohne Menüeintrag wäre die Abnahme „keine Unterpunkte" grün ohne Messung; Menüeintrag und Symbol standen in AP9, gebraucht in AP2; die Leiste wird höher (B-11, E-15) | AP2, AP9 |
| F-P5c-48 | Keine Zählregel für den Erklärtext; acht Karten „Was hier gilt" nach R74 (5), drei weitere außerhalb (E-01, E-04) | E-P5c-49, AP9 |
| F-P5c-49 | Die Prüfstand-Stufe „neben" ist nicht gebaut (`pruefablauf.json` kennt `klein` und `haupt`; Bilderlauf fest `klein`); `Pruefablauf.md` 4 nennt 12 Riegel, die Datei hat 14 (F-08) | E-P5c-32 — an PK-05 |
| F-P5c-50 | Buchführung: Rahmenplan 5 führt 80, 141, 168, 169, 191 ohne AP, 253 und 258 fehlen; Backlog 192, 198, 244 tragen widersprüchliche Reste; Zeile 233 „S10c" statt Schritt 18; PK fehlt im Fahrplan, M1 steht als blockiert, Schritt 15 als „Merge offen" (F-01, F-17) | Einschub 7/8, mit AP1 |
| F-P5c-51 | Das Konzept nannte Prüfmittel, die es nicht mehr gibt oder die etwas anderes messen: `tools/rollenprobe/`, „Wortliste", „Versandprobe" für Mail (misst Sicherungsziele), „Browser-Prüfstand" (A-07, B-09, C-07, C-08, E-17, F-07, F-09, F-13) | Abschnitt 4 |
| F-P5c-52 | Andockstellen-Zahlen: `ui_seite_start()` 45/43, `mail_einreihen()` 18/13, `protokoll()` 12/7, `json_fehler()` 17/13 (je ohne Definition); der Absatz zu Schritt 16 ist erledigt; `gpx_lib.php` ist keine Hülle (A-09, A-22, A-23, B-20, B-21) | Andockstellen berichtigt |
| F-P5c-53 | Konzeptfehler: `diensttag_form.php` gibt es nicht (Ausnahme in `index.php`); einen Reiter „Konto" gibt es nicht (Profil); Sperren mit Grund besteht seit 20.17.0; Zeilennummern in `ui.php` (C-12, C-19, D-23, E-19) | berichtigt 23.09. |
| F-P5c-54 | Suchfeld, Filterpillen, Seitenwahl nur als handgeschriebenes Markup in `admin_users.php`; die Protokollseite wäre die zweite Kopie (B-12) | AP2, R83 |
| F-P5c-55 | `new ZipArchive` viermal in `adminbackup_lib.php`; die Registerart `aufruf` übersieht `new X(` (B-13) | E-P5c-57 |
| F-P5c-56 | Die Ratenprobe führt die Töpfe mit Leiter als geschlossene Liste (F-13) | AP5 |
| F-P5c-57 | Für den Vorschau-Endpunkt fehlten die Andockstellen; `forms.js` fragt im Stil der Anwendung nur über `data-cancel-form` (E-12) | AP9, E-P5c-25 |
| F-P5c-58 | Die Verifikationsmail hat keine Funktion; der Verfall unbestätigter Konten zählt ab `created_at` (C-12) | E-P5c-14 |
| F-P5c-59 | *(M-P5c-02)* Der Menüzähler „Status" zählt Orange und Rot (`status_lib.php`); die Begründung von E-P5c-44 — Rot hielte den Zähler dauerhaft über null — gilt für Orange genauso | Q-P5c-30 |
| F-P5c-60 | *(M-P5c-02)* Die zwei Bedingungen aus E-P5c-16 sind eine: weniger als zwei handlungsfähige Verwaltungskonten heißt immer auch weniger als zwei handlungsfähige BetreiberInnen | AP5, Abnahme berichtigt |
| F-P5c-61 | *(M-P5c-02)* `notfallblatt.php` schickt zweimal nach „Einstellungen → Konto" (Z. 169, 213); das freigegebene M-P5c-01f trägt den Fehler mit | AP9, Abnahme |
| F-P5c-62 | *(M-P5c-02)* **Neueinrichtung scheitert seit Web 20.30.0:** `konto_anlegen()` ruft `db_transaktion()`, `konto_lib.php` lädt `db.php` nur mit `config.php`; `hochfahren.sh --neu` scheitert in Schritt 4 | **Backlog 288**, Voraussetzung (3.0) |
| F-P5c-63 | *(M-P5c-02)* Die Anmeldeseite stellt die vier Verweise neben die Karte (`.anmeldung` in Zeilenrichtung) — bei 390 px ist die Karte rund 200 px breit; seit Web 20.23.0 | **Backlog 289**, spätestens AP1 |

---

## 6. Mockup-Runden

### 6.1 M-P5c-01 — erledigt am 20.09.2026

Auf Anforderung des Auftraggebers **vor** der Umsetzung gezogen (wie
M-P5b-01/-02); gebaut gegen `862ca7f` mit den echten `ui_*`-Funktionen und dem
echten `rt_html()`, Fable. Ablage `konzept-p5c/mockups/`, ein Dokument: das
`LIESMICH.md` dort (mit Nachtrag vom 23.09.2026).

| Mockup | Ergebnis |
|---|---|
| (a) Protokoll, Sichten Admin und BetreiberIn, 1440 und 400 | **freigegeben** unverändert — E-P5c-25, -26 |
| (b) Betriebslage | V3 **freigegeben**: Statistik mit drei Reitern, eine Zählung — E-P5c-18 |
| (c) Menü-Gliederung | **freigegeben (V4):** Leiste Option 1 „Linie"; Übersicht mit Bereichskarten — E-P5c-29 |
| (d) Rechtstext-Vorschau | **entschieden:** eigene Seite, Vorschau rechts — E-P5c-28 |
| (e) Zeitraumübersicht mit Winde | **entfällt** — E-P5c-27 |
| (f) Schlüsselblatt und Notfallblatt | **freigegeben**, mit Webversion in der Fußzeile — E-P5c-08, -30 |

### 6.2 M-P5c-02 — Auftrag (E-P5c-34)

**Wer, wie:** Opus, **aus dem HTML-Stand** — Seiten der örtlichen Installation
(`tools/sandbox/`) mit den echten `ui_*`-Bausteinen und dem echten `style.css`
rendern und abspeichern; neue Klassen im `<style>`-Block der Datei unter
„Vorschlag für style.css", wie in M-P5c-01. Nicht frei gezeichnet. Bilder aus
Chromium bei 1440 und 390 px (Druckblätter 1240 px, dazu PDF). Ablage
`konzept-p5c/mockups/`, Abschnitt im `LIESMICH.md`. Kein Anwendungscode.

| Mockup | Inhalt | Entscheidungen |
|---|---|---|
| (a) Banner | Seite mit Kopfleiste in `--rot` samt aktivem Kopfpunkt (Lösung für ≥ 3 : 1), Umgebungszeile, Ankündigung in beiden Tönen mit Wegklicken; **Anmeldeseite** (ohne Kopfleiste); Staging mit Demo-Konto: alle vier Streifen in der Reihenfolge aus E-P5c-55; Karte „Ankündigung" in Servereinstellungen mit Rundmail-Knopf und Sicherheitsabfrage | E-P5c-05, -13, -55 |
| (b) Zweitfaktor | Karte „Zweitfaktor" unter Einstellungen → Profil: Einrichtung mit QR, Base32 in Vierergruppen und otpauth-Verweis, Bestätigungscode, Anzeige der zehn Codes; **Code-Schritt** bei der Anmeldung (samt Wiederherstellungscode-Weg); **Einrichtungstor** für Pflichtrollen; **Codeblatt** als A4 auf `.blatt-druck` | E-P5c-41, -42, -53, -54 |
| (c) Support-Sicht | Kontoseite (`admin_user.php`) als Support: sichtbare Handlungen, Setz-Link-Fehlfall ohne Link, Verifikationsmail; die NutzerInnen-Liste ohne Konten anderer Rollen; dazu die Handlung „Zweitfaktor zurücksetzen" in der Sicht der BetreiberIn | E-P5c-14, -40, -42 |
| (d) Bus-Faktor | die Zeile in Betrieb → Status in Orange, beide Fälle | E-P5c-16, -44, -56 |
| (e) Druckblatt auf Staging | Kopf von Schlüsselblatt und Notfallblatt mit Umgebungszeile | E-P5c-50 |

**Messung:** Überlauf 0 in allen Bildern; Kontraste der neuen Paare nach WCAG
gerechnet und gegen `kontrast.py` gehalten; PDF des Codeblatts = 1 Seite;
Texte in der Hausform. **Freigabe** je Mockup durch den Auftraggeber;
danach Rückschreibung ins Konzept (E-Einträge, Abnahmen).

**Stand 23.09.2026: gebaut, zur Freigabe.** Neun Dateien mit Bild, drei
davon mit PDF; Dateien, Messung und Bauart im `LIESMICH.md` der Mockups,
Abschnitt 8. Gemessen: Überlauf 0 in sechs Bildschirmdateien, 0 fehlende
Ressourcen, 0 Konsolenfehler; QR 2 von 2 aus dem Bild gelesen = otpauth-Adresse;
Codeblatt 1 Seite (748 von 1017 px); Schlüssel- und Notfallblatt auf Staging
je 1 Seite (945 und 727 px), Härtefall 1013 von 1017 px; Kontraste 8 von 8
gleich mit den Funktionen von `kontrast.py`. **Nicht gemessen:** Firefox-Druck,
Scan mit einer echten Authenticator-App (beides Prüfpunkte der Umsetzung).

**Offen mit der Freigabe** (Q-P5c-26 bis -30, Vorschlag fett):

| Nr. | Frage | Vorschlag |
|---|---|---|
| Q-P5c-26 | (a) Aktiver Kopfpunkt auf `--rot`: Strich in `--orange-hell` (A, 4,12 : 1) oder Weiß (B, 4,78 : 1) | **A** |
| Q-P5c-27 | (a) Ankündigung auch auf der Anmeldeseite | **ja** |
| Q-P5c-28 | (b) Einrichtungstor als eigene Seite in der Anmeldehülle, Name `zweitfaktor.php` | **ja, ja** |
| Q-P5c-29 | (c) Aktionsblatt „Setz-Link senden" statt „Passwort zurücksetzen", für alle Rollen | **ja** |
| Q-P5c-30 | (d) Bus-Faktor und Menüzähler (F-P5c-59): alle Fälle orange wie E-P5c-44, oder Fall 1 und 2 neutral („keine Vertretung", zählt nicht) und nur Fall 3 orange | **Fall 1 und 2 neutral, Fall 3 orange** — Vorbild „Komplett-Backup: kein Plan"; neutral heißt „nicht eingerichtet", orange „eingerichtet, trägt aber nicht" |

---

## 7. Einschub Rahmenplan (mit AP1; Fassung vergibt die einspielende Instanz)

- **Fahrplan (Abschnitt 3):** PK und TB in die Reihenfolge — „… → 15 → **TB →
  PK-05** → 10c → 17 → 18 …" (PK-06 bis -08 daneben); Voraussetzung 10c
  „Schritte 16 und 15; **PK-05**; Freigabe M-P5c-02". Die veralteten Sätze
  „M1 ist blockiert" und Schritt 15 „Merge nach `main` offen" berichtigen
  (F-P5c-50).
- **Fahrplanzeile 10c:** Konzept „liegt vor: Fassung 2 vom 23.09.2026
  (E-P5c-01 bis -58, Q-P5c-01 bis -25, AP1–AP9 und AP11, **AP10 entfallen**,
  Mockup-Runde M-P5c-02 offen, Opus)".
- **R38:** Zweitfaktor nach E-P5c-41/-42 (QR, zehn Codes, Merken → Schritt 18);
  Bus-Faktor in Orange (E-P5c-44); Fenster und Zählung nach E-P5c-18 (mit AP7).
- **R74 (5)** umschreiben (E-P5c-49): „Erklärtext: je Karte höchstens ein
  Satz, alles Erklärende im Handbuch mit Verweis auf die Sprungmarke
  (E-P5c-06); die zugeklappte Karte ‚Was hier gilt' entfällt."
- **R78 / SP-11:** der QR-Code ist entschieden (E-P5c-41), SP-11 in diesem
  Punkt überholt.
- **Abschnitt 5:** 80, 191, 190 → 10c AP7; 168, 169 → 10c AP8; 141 → 10c AP5;
  121, 244, 245, 246, 253, 269 → 10c AP9; 243 → 10c AP1; 248 → 10c AP3; 286 →
  10c AP2; 200 → „nicht umsetzen (E-P5c-51)"; 258 → Schritt 17; 287 →
  Schritt 17; 233 „S10c" → Schritt 18.
- **Abschnitt 6 (Zuarbeit):** `app.umgebung` in `config.php` von **Staging**;
  `betrieb.health_token` in beiden Anlagen; **`mail.postfach` entfällt**;
  Secret **`STAGING_TOTP`** in der Umgebung `staging` **vor dem Merge des
  10c-PR**; Zahl der Konten mit `pat_key_check IS NULL` auf Produktiv **vor
  AP5**; nach dem Merge **`update.php`** (Migrationen aus AP4, AP5, AP7, AP8),
  die Wartung bleibt bis dahin an; Z7 (`mail.betreff_praefix` auf Staging) als
  Voraussetzung der Warnung aus E-P5c-05. Bis 10c ausgeliefert ist, legt
  niemand ein Konto mit der Rolle admin an (E-P5c-31).
- **Abschnitt 10:** eine Zeile.

## 8. Einschub Backlog (mit AP1, außer 286 bis 289 und dem Kopf)

- **Mit dieser Fassung eingetragen:** Nr. **286** (Admin-Tor, → 10c AP2),
  Nr. **287** (Karten „Was hier gilt" auf `import.php`, `einsatz_form.php`,
  `wiederherstellen.php`, → Schritt 17); **mit M-P5c-02 (23.09.2026):**
  Nr. **288** (Neueinrichtung scheitert, eigene Korrekturstufe vor P5c) und
  Nr. **289** (Anmeldeseite, mit 288, spätestens AP1). Kopf: nächste freie
  Nummer **290**.
- **141** berichtigen: QR-Code statt Text (E-P5c-41), zehn Codes, „Gerät
  merken" mit Nr. 242 in Schritt 18; → 10c AP5.
- **200** schließen: „nicht umsetzen — entschieden 23.09.2026 (E-P5c-51)";
  bleibt unter *Offen* mit Vermerk.
- **80:** Vorbedingung gilt nicht für die Summenzählung (E-P5c-45);
  User-Agent-Hälfte gestrichen (R36); → 10c AP7.
- **168:** den Satz „über die Verwaltung noch löschen" berichtigen; „tritt
  nicht auf" (E-P5c-48); → 10c AP8.
- **169:** Weg (b) entschieden (E-P5c-47); → 10c AP8.
- **190:** → 10c AP7.
- **192, 198, 244:** die überholten Absätze (Zuordnung „Bestand",
  „10c, AP9 — Kacheln nach Typ", „Fable-Schritt") als überholt kennzeichnen,
  nicht löschen.
- **247:** Zweitfaktor-Geheimnisse unter „alles Versiegelte".
- **248:** berichtigen: Übergabezahl 75, Decke 2; der Behandler wird von einer
  Regel in `tools/quelltext/` geprüft, nicht von einer Registerzeile.
- **253:** „Gedankenstrich" → ` · `; entschieden: Komma (E-P5c-37); → 10c AP2/AP9.
- **269:** → 10c AP9.

---

# Andockstellen aus Schritt 15 — was 10c vorfindet

> **Herkunft.** Dieser Abschnitt stand bis zum 22.09.2026 als Abschnitt 6 in
> `docs/konzepte/Konzept-Zentralisierung.md`. Schritt 15 ist abgeschlossen
> und gemergt (PR #74, 23.09.2026); die Andockstellen stehen hier, beim
> Verbraucher, **gegen den gebauten Stand nachgezogen** (Web 20.27.0 bis
> 20.37.0) und **am 23.09.2026 nachgemessen** (F-P5c-52). Wo „**gebaut**"
> steht, ist der Helfer da und trägt den genannten Namen.
>
> **Was Schritt 15 NICHT gebaut hat und 10c selbst bauen muss**, steht in
> den Zeilen mit „10c" — vor allem `zip_lib.php` (R83, 10c AP2 ist der zweite
> Verbraucher) und die Decke der Registerzeile **Z38** für `error_log(`
> (Übergabezahl **75**; das Register steht noch auf 77).
>
> **Zählweise:** Aufrufe **ohne** die Definition, wie das Register zählt.

| 10c-Paket | braucht | findet vor | aus |
|---|---|---|---|
| **AP1 Banner** | Umgebungsetikett aus `config.php` | **gebaut (Web 20.27.0):** `konfig('app.umgebung')`; der Mail-Präfix kommt aus demselben Leser (`konfig('mail.betreff_praefix')`) | AP2 |
| | Banner auf jeder Seite | `ui_seite_start()` ist **eine** Stelle für den Titelpräfix (**45 Aufrufe in 43 Dateien**); die Kopfleiste kommt aus **`ui_kopf()`** (8 Aufrufe in 5 Dateien), die Hinweise aus **`ui_leiste_ende()`**. **Eigene Hüllen** (einzeln zu entscheiden): `wartung_lib.php` (Störung, Wartung), `kopfzeilen_lib.php`, `install.php` (nur die Seite „PHP zu alt"), `betrieb_schluesselblatt.php`, `notfallblatt.php`. **Über `ui_seite_start()` ohne Kopfleiste:** 8 Anmeldeseiten. *`gpx_lib.php` stand hier und ist keine Hülle.* | Bestand |
| | Rundmail | `mail_einreihen()` (18 Aufrufe in 13 Dateien) versucht sofort — „nur einreihen" baut 10c | Bestand, **10c** |
| **AP2 Protokollseite, Archiv** | Schreibweg | `protokoll()` (**12 Aufrufe in 7 Dateien**, alle Reiter `verwaltung`) — von 15 unberührt | Bestand |
| | Rollengatter je Reiter | `require_admin()`/`require_betreiberin()` (`auth_guard.php`), `rolle_*()` (`db.php`); **0 Rollenvergleiche von Hand** außerhalb `db.php` (Z13) | AP4, **gebaut** |
| | Zeile, Filter, Archivliste | `datum_zeit_text()`, `zeit_relativ()`, `zahl_text()`, `groesse_text()` aus `format_lib.php`; Listenhelfer baut **10c** (R83) | AP7, **gebaut** |
| | Archivtakt, Fristen | `app_state_lesen()`, `app_state_setzen()`, `app_state_mehrere()`, `app_state_setzen_mehrere()`, `app_state_loeschen()` | AP4, **gebaut** |
| | Archivlauf | `db_transaktion()` | AP5, **gebaut** |
| | ZIP schreiben | 4× `new ZipArchive` in `adminbackup_lib.php` (798 schreibt, drei lesen); **`zip_lib.php` baut 10c AP2**, Registerzeile als Art `muster` | Bestand, **10c** |
| **AP3 Fehlerprotokoll** | Zahl der umzustellenden Aufrufe | **75 in 30** (Tokenizer); Z38 steht auf 77/77/77 | E-ZE-05 |
| | Zählmittel und Stufe-1-Schritt | `tools/zaehlung/`, Zeile **Z38**; 10c AP3 setzt die Decke auf **2** | AP1, AP10 |
| | Behandler „früh in `db.php`" | der frühe Teil von `db.php`: `konfig_lib.php` laden, `wartung_tor()`. `sitzung_ablage()` steht dort nicht mehr (E-ZE-06); **der Kommentar dazu ist veraltet** und wird in AP3 berichtigt | AP2 |
| | JSON-Fehler mit Kennung | `json_fehler()` (**17 Aufrufe in 13 Dateien**), `fehler_kennung()` (8 in 7, ruft selbst `error_log`) — unberührt; `api_rumpf()` fängt keine Ausnahmen | AP3 |
| **AP4 Support-Rolle** | eine Andockstelle | `ROLLEN`, `rolle_darf_verwalten()` in `db.php` — `rolle_darf_support()` kommt daneben; die Aufzählstellen außerhalb des Registers in AP4 | AP4, **gebaut** |
| | „je Handlung, nicht je Seite" | der POST-Verteiler (`$_POST['action']`) ist **nicht** zentralisiert (E-ZE-07); 41 Handlungen in sieben `admin_*.php` | — |
| | Meldungen nach Handlungen | `flash_setzen()`/`flash_holen()` in `session_lib.php` — **in `admin_user.php` und `admin_users.php` nicht benutzt**; 10c bleibt bei der Meldung in derselben Antwort (Nr. 250 → Schritt 17) | AP3, **gebaut** |
| **AP5 Zweitfaktor** | Sitzung und Code-Schritt | `sitzung_starten('app')` ist der einzige Weg in die Anmeldesitzung; der Code-Schritt steht in `login.php` **vor** `user_id` (E-P5c-53); das Einrichtungstor in `auth_guard.php` nach dem Sitzungsstart, neben dem Einwilligungstor | AP2 |
| | Geheimnis, Einstellungen | `sk_versiegeln()`, `konfig()`; `ParagonIE\ConstantTime\Base32` (vendoriert). *`app_state_einmalig()` stand hier und hat in AP5 keinen Verbraucher.* | Bestand, AP2 |
| **AP6 Health** | Eingang ohne Sitzung | `api_methode('GET')` aus `db.php` — lädt kein `auth_guard.php` | AP3, **gebaut** |
| | Token, Dauer, Zustand | `konfig('betrieb.health_token')`, `rate_gleiche_dauer()`, `migrationen_ausstehend()`; `wartung_tor()` und `ueberlast_antwort()` antworten **vorher** (E-P5c-52) | AP2, **gebaut** |
| **AP7 Betriebslage** | Zahlen und Anteile | `zahl_text()`, `prozent_text()`, `zeit_relativ()` | AP7, **gebaut** |
| | „je echtem Gerät" | `geraete_echt_sql(string $alias = '')`, `GERAET_VIRTUELL_MUSTER` — **die Seite nutzt heute das Muster, nicht den Helfer** | AP4, **gebaut** |
| | Migration für den Index | nur über `db_hat_index()`; Registerzeile Z15 | AP4 **gebaut**, AP10 |
| **AP8 R39-Rest** | Migration mit Vorzählung | `db_hat_tabelle()`, `db_hat_spalte()`; **`migrationen_inhalt_zaehlen()` zählt Zeilen MIT Inhalt** — Nr. 168 braucht das Gegenteil, also eine eigene Vorbedingung | AP4, **gebaut** |
| | Besatzung am Diensttag | `day_crew` in `diensttag_lib.php`, der Dialog in `index.php`; `einsatz_besatzung_ersetzen()` gilt für Einsätze | AP5 |
| **AP9 Aufräumen** | Datum-Zeit-Trenner | `datum_zeit_text()` mit Vorgabe `' '`; 36 Aufrufe: 24 Vorgabe, 11 ` · `, 1 `', '`; dazu 4 Zusammensetzungen, 1 Umgehung in `wartung_lib.php`, ` um ` im Mailtext — **rund 18 Stellen in 11 Dateien, nicht „eine Zeile"** | AP7 |
| | Vorschau-Endpunkt | `api_methode('POST')`, `csrf_check()`, `api_rumpf()` (ohne Vorgabe für `max_bytes`), `rt_pruefen()`, `EdApi.postJson`; **ein neuer Topf** in `RATE_GRENZEN` | AP3, AP8, **gebaut** |
| | *JS von `einstellungen.php`* | *stand hier; nicht im Umfang — E-P5c-06 gilt Verwaltung und Betrieb* | — |
| ~~**AP10 Bounce**~~ | — | entfällt (E-P5c-51) | — |
| **AP11 Abschluss** | — | Das Register läuft in Stufe 1: neuer 10c-Code, der eine zweite Stelle baut, macht die Kette rot. **Decken werden nicht angehoben, ohne dass es im Konzept steht** | AP10 |

**Andockstellen zu Schritt 16:** erledigt mit Schritt 15 AP2 (Web 20.27.0,
E-ZE-06, F-ZE-2) — `sitzung_ablage()` hat einen Aufrufer,
`sitzung_starten()`; die Sitzungsart `lesend` startet ohne Cookie nicht,
anonyme Abrufe lesender Seiten legen keine Datei mehr an.
