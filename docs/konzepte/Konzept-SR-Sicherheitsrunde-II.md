# Konzept SR — Sicherheitsrunde II (Schritt 18)

**Kürzel:** `SR`. Arbeitspakete `SR-01 …`, Entscheidungen `E-SR-NN`, Befunde
`F-SR-NN`, Fragen an die Betreiberin `Q-SR-NN`, Haltepunkte `H-SR-NN`,
Prüfpunkte `P-SR-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`SR-02: …`). Benennung nach `docs/Pruefablauf.md` 7; `S2` ist Mengen
und Spuren, `SA` die Sitzungsablage, `RW` der Rückweg — daher `SR`.
**Rahmenplan:** Schritt **18** (Fahrplan: „Sitzungsbindung per Cookie-Token
(Nr. 242, dazu „Gerät merken" beim Zweitfaktor), Serverschlüssel wechseln
als Vorgang (Nr. 247), TOTP-Reset der einzigen BetreiberIn ohne Blatt
(Nr. 249), Rest der Betreiber-Rückfrage (Nr. 233), `ingest.php`-Deadlock
(Nr. 210); Nr. 228 bleibt „nur auf Anlass""; Voraussetzung der Umsetzung
„Merge von 17"). Nach 18 folgt 12 (P6-Review), und das ist der Grund für die
Reihenfolge: Der Review soll gehärtete Seiten lesen (Rahmenplan 3).
**Herkunft:** Konzeptsitzung am 27.09.2026 (Fable, R14). Die Liste der
Punkte liefert `python3 tools/steuerung/uebersicht.py --ziel 18`: **acht
Einträge** am 27.09.2026 — die sechs der Fahrplanzeile und **Nr. 232, 251**,
die Schritt 15 (E-ZE-12) und R4-01 (Q-R4-12) hierher gehängt haben; seit
der **Nachfassung** am selben Tag **neun**, mit **Nr. 350** (Passkeys, SR-09,
aus der eigenen Spanne — E-SR-29); seit dem Merge von 17 (28.09.2026) **elf**:
**Nr. 250** (der Rest von R4-11, `betrieb_server.php`) und **Nr. 344**
(F-R4-33) sind mit E-R4-37 hierher gekommen — Zuordnung E-SR-37.
**Modell:** Konzept Fable (R14); Umsetzung **Opus** (K2), **kein
Fable-Schritt**; SR-03 bekommt vor dem PR eine **Gegenlesung durch Fable**
(Q-SR-09, E-SR-23, H-SR-06) — ein Halt, kein Schritt; **SR-09 ebenso**
(Q-SR-13, E-SR-36, H-SR-08).
**Fächerung (`CLAUDE.md` 7):** keine, in keinem Paket. Alle Pakete
schreiben `server/`, und jedes trägt erzählenden Text; die Konzeptsitzung
hat nicht gefächert (2.1). **Die Gegenlesung H-SR-08** lief auf Wunsch der
Betreiberin als lesender Workflow (E-SR-44) — eine Messung, kein Paket;
ebenso ihre Nachprüfung und die dritte Lesung (E-SR-57).
**Versionsstufe:** legt die Umsetzung fest (K3). Je Paket in Abschnitt 4
vermerkt: SR-05 Korrektur; SR-01, SR-03, SR-04, SR-07, SR-08 Neben; SR-02
und SR-09 Neben **mit Migration** — der Prüfstand fährt dafür `haupt` (Stufenregel `migration`),
und nach dem Deploy ruft eine Administratorin `update.php`. Uhr und Android
bleiben unberührt (kein Paket fasst `watch/` oder `android/` an).
**Ablage:** dieses Konzept; Prüfdokument
`Pruefdokument-SR-Sicherheitsrunde-II.md` daneben (angelegt mit dem
Konzept, gefüllt von der Umsetzung). **Keine Mockups** — jede Oberfläche
dieser Runde besteht aus vorhandenen Bausteinen (Q-SR-08).
**Zweig:** `claude/gallant-mccarthy-yacnzk`, aufgesetzt auf
`claude/schritt-17-konzept-mockups-q0yjcm` bei `26b4761` (R4-10, Web
21.1.8) — dem Stand, den die Betreiberin am 27.09.2026 als jüngsten Code
benannt hat (E-SR-02). Die Umsetzung läuft nach dem Merge von 17 **und**
dieses Konzepts auf einem eigenen Zweig von `main`. **17 ist am 28.09.2026
gemergt** (PR #94, `f4ac705`, Web 21.6.1); dieser Zweig hat `main` nach
`Pruefablauf.md` 5.3 aufgenommen (Merge-Commit mit Bericht), der Konzept-PR
ist gestellt. **Die Umsetzung läuft seit dem 28.09.2026 auf
`claude/pr95-stufe-18-ztactt`**, per Fast-Forward auf den Kopf des noch
offenen Konzept-PR #95 (`d88522e`) gesetzt — auf Wunsch der Betreiberin vor
dessen Merge (E-SR-38).
**Backlog-Spanne:** **350 bis 359** (E-SD-21, eingetragen mit `SR-00`
vor jeder Vergabe). Vergeben aus der Spanne: siehe Statusblock.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **04.10.2026 — Nachmessung zu H-SR-06 (Web 21.12.2):** was 21.12.1 nur gelesen hatte, ist gemessen — der gefährliche Gegenfall der Probe (mit Sicherung, auch mit echter Sperre), mehr als 20 werfende Stücke, die Ausfälle beim Beginn, Knopf und Stand unter drittem Schlüssel fest in der Probe; zwei Auskünfte stimmten nicht (F-SR-88), behoben. Davor am selben Tag: **H-SR-06 durchlaufen: die Gegenlesung von SR-03 (42 Meldungen, zwei hoch; F-SR-78 bis -83) behoben in Web 21.12.1, die Behebung nachgeprüft (26 weitere, eine hoch, zwei mittel; F-SR-84 bis -87) und eingearbeitet** — jeder Griff an einen Schlüssel unter einer Sperre, der Nachweis erst nach zehn Minuten, ein Wechsel von Hand mit dem Beginn eines Wechsels, ein Komplett-Stand über den Wechsel neu versiegelt, ein Stück, das wirft, genannt statt den Wechsel anzuhalten, und ein Rückweg der Probe, der nichts wegwirft — auch nicht nach einem Wettlauf ohne Sieger. **Als Nächstes: SR-04** (Notzugang). Davor: **29.09.2026 — SR-03 gebaut (Web 21.12.0): Der Serverschlüssel wechselt als Vorgang** — der bisherige bleibt als `server_key_alt`, der Job `schluesselwechsel` hüllt vier Zwecke um und weist sie nach, der bisherige geht erst nach Nachweis, frischem Stand und Rückfrage; beide Rotationen machen das Blatt sofort fällig (Nr. 233), Nr. 344 behoben. Davor am selben Tag: **die offenen Punkte von H-SR-08 gemessen (Web 21.11.2):** dritte Lesung (zwölf Meldungen, F-SR-58 bis -67), der Zähler unter echtem Wettlauf, Nr. 354 mit echtem Deadlock nachgestellt und berichtigt, der Knopf in `passkey.js` im Bedienweg. Davor **SR-05 gebaut (Web 21.11.1): `ingest.php` ohne Deadlock, 180 von 180 ohne 503.** Davor am selben Tag: **H-SR-08 durchlaufen: Gegenlesung von SR-09 (41 Befunde, F-SR-33 bis -44) behoben in Web 21.11.0, die Behebung nachgeprüft (23 weitere, F-SR-45 bis -52, eingearbeitet bis auf Nr. 354)**; E-SR-42 bestätigt, Q-SR-14 bis -18 beantwortet. Gebaut sind SR-01, SR-02, SR-07 und SR-09 (Web 21.7.0 bis 21.10.0), gestapelt auf dem offenen Konzept-PR #95 (E-SR-38). Davor: **27.09.2026 — Konzept freigegeben** (Betreiberin, E-SR-27); **Nachfassung am selben Tag: SR-09 Passkeys** (E-SR-29, -35, -36) — **neun Pakete** (4). **28.09.2026: 17 gemergt (PR #94), `main` aufgenommen, Konzept-PR gestellt**; aus 17 kamen Nr. 250 und 344 mit Ziel 18 dazu (E-SR-37). |
> | Entschieden | **E-SR-01 bis E-SR-80** (Abschnitt 3.1); **E-SR-71 bis -75 von der Betreiberin am 04.10.2026** (H-SR-06: Lesung mit Rechnungen, Komplett-Stand neu versiegeln, Frist zehn Minuten, Handeintrag wie ein Wechsel, Web 21.12.1), E-SR-76 bis -78 aus der Behebung, E-SR-79 aus der Nachprüfung, E-SR-80 von der Betreiberin zur Nachmessung. Von der Betreiberin am 27.09.2026: E-SR-02 (Ausgangsstand), E-SR-17 bis -26 (die Antworten der Klickrunde, Q-SR-01 bis -11), E-SR-27 (Freigabe), E-SR-28 und -29 (Nachfassung: kein PRF, Passkeys als SR-09), E-SR-35 und -36 (Q-SR-12, -13); die übrigen aus dem Konzept, E-SR-37 am 28.09.2026 nach dem Merge von 17. **Aus der Umsetzung:** E-SR-38 (Stapeln auf PR #95) und E-SR-39 (lesende Seiten in SR-01), beide von der Betreiberin am 28.09.2026; E-SR-40 (SR-02), E-SR-41 (SR-07), E-SR-42 (Ursprung aus `app.base_url`, SR-09 — **von der Betreiberin bestätigt** bei H-SR-08), E-SR-43 (SR-09). **Bei H-SR-08:** E-SR-44 (Gegenlesung als Workflow) und E-SR-45 bis -49 (Q-SR-14 bis -18) von der Betreiberin am 28.09.2026; E-SR-50 (Fehlversuche), E-SR-51 (Migration an Ort und Stelle), E-SR-52 (der Preis von E-SR-50) und E-SR-53 (Nr. 354) aus der Umsetzung. **Aus SR-05:** E-SR-54 (`last_seen` hinter den Commit), E-SR-55 (Verbindungsprobe: zweite Lage, ab klein). **Nachtrag zu H-SR-08:** E-SR-56 (Nr. 354 nur messen, Antwort (a)) und E-SR-57 (dritte Lesung sofort, ohne Last) von der Betreiberin am 29.09.2026; E-SR-58 (Meldung „neu laden") und E-SR-59 (Nr. 355) aus der Umsetzung. **Aus SR-03:** E-SR-60 bis -63 von der Betreiberin am 29.09.2026 vor dem Paket (Rotationen nacheinander, Unlesbares melden statt blockieren, Mail an jedes BetreiberIn-Konto, Pflicht-Haken mit Rückfrage); E-SR-64 bis -70 aus der Umsetzung (Zeiger, Rahmen für den Knopf, Nr. 344 in der Schlüsselwechselprobe, Kennung im Kopf, Anstoß nach dem Nachweis, Rückweg der Probe, bedingtes `UPDATE`). |
> | Offen | **Der Konzept-PR #95 wartet auf den Merge**; der PR der Umsetzung wird erst danach gestellt (sonst zeigte er das Konzept als eigenen Unterschied). **H-SR-06 ist durchlaufen** (Web 21.12.1); als Nächstes SR-04. |
> | Umsetzung | **SR-00 erledigt** (Konzept, Spanne, Fahrplanzeile — Rahmenplan Fassungen 139, 140, 143 bis 145 — bis zum Merge von 17 als 137 bis 142 gezählt, weil 17 dieselben Nummern vergeben hatte). **SR-01 erledigt** 28.09.2026 (Web 21.7.0; Befunde F-SR-15 bis -18; Rahmenplan Fassung 146: Schritt beginnt). **SR-02 erledigt** 28.09.2026 (Web 21.8.0, Migration `2026_09_28_vertraute_geraete`; Befunde F-SR-19 bis -23; Nr. 250 erledigt). **SR-07 erledigt** 28.09.2026 (Web 21.9.0; Befunde F-SR-24 bis -27). **SR-09 erledigt** 28.09.2026 (Web 21.10.0, Migration `2026_09_28_passkeys`; Befunde F-SR-28 bis -32; Nr. 350 erledigt, Nr. 353 neu). **H-SR-08 durchlaufen** 29.09.2026 (Web 21.11.0; Befunde F-SR-33 bis -52, E-SR-44 bis -53, Nr. 354 neu; Migration `2026_09_28_passkeys` an Ort und Stelle geändert). **SR-05 erledigt** 29.09.2026 (Web 21.11.1; Befunde F-SR-53 bis -57; Nr. 210 erledigt). **Nachtrag zu H-SR-08 erledigt** 29.09.2026 (Web 21.11.2; die vier offenen Punkte gemessen, dritte Lesung; Befunde F-SR-58 bis -67; Nr. 354 berichtigt, Nr. 355 neu). **SR-03 erledigt** 29.09.2026 (Web 21.12.0; Befunde F-SR-68 bis -77; Nr. 233, 247 und 344 erledigt, Nr. 356 neu). **H-SR-06 durchlaufen** 04.10.2026 (Web 21.12.1, `bac4320`; Befunde F-SR-78 bis -83, Nachprüfung F-SR-84 bis -87, E-SR-71 bis -79; Nr. 357 neu). **Nachmessung zu H-SR-06 erledigt** 04.10.2026 (Web 21.12.2; F-SR-88, E-SR-80). |
> | Fable-Schritte | keine; **zwei Fable-Gegenlesungen** vor dem PR: SR-03 (H-SR-06, E-SR-23) — **durchlaufen**, als Workflow mit Rechnungen (E-SR-71) — und SR-09 (H-SR-08, E-SR-36) — **durchlaufen**, als Workflow (E-SR-44). |
> | Fächerung | keine in den Paketen; die **Gegenlesung H-SR-08**, ihre Nachprüfung und die dritte Lesung liefen als lesender Workflow (E-SR-44, E-SR-57), die **Gegenlesung H-SR-06**, ihre Nachprüfung und die Gegenprüfung der Nachmessung ebenso (E-SR-71). |
> | Nummern | 350 bis 359 reserviert; vergeben: **350** (Passkeys als zweiter Faktor, SR-09), **351** (Passkey allein, `nach v1.0`, E-SR-35), **352** (`nummern.py` im offenen Merge, F-SR-14, `nächste Backlog-Runde`), **353** (Schemaprobe vergleicht migriert und frisch nicht, F-SR-28, `nächste Backlog-Runde`), **354** (`protokoll()` schluckt in einer Transaktion einen Deadlock, F-SR-50, berichtigt mit F-SR-63, `nächste Backlog-Runde`), **355** (Formularfeld als Liste gibt eine PHP-Warnung, F-SR-65, `nächste Backlog-Runde`), **356** (zwei Abschnitte der Karte „Schlüssel des Servers" ohne Abstand, F-SR-73, `nächste Backlog-Runde`), **357** (Klassenname im Fehlertext der Jobs, F-SR-82, `nächste Backlog-Runde`). |

---

## 1. Auftrag

**Ziel:** Die Lücken schließen, die frühere Konzepte benannt und
ausdrücklich hierher gelegt haben — sieben Punkte, zwei davon (6 und 7) am
27.09.2026 von der Betreiberin dazu entschieden — und danach trägt **kein
offener Backlog-Punkt mehr `gehört zu: 18`**: erledigt, oder mit Begründung
an seinen richtigen Ort gehängt.

1. **Eine gelesene Sitzungsdatei ist wertlos** (Nr. 242, E-SA-09). Heute ist
   der Dateiname die Sitzung: Wer `sess_<id>` liest — aus dem Verzeichnis,
   aus einem Webspace-Backup des Hosters —, ist angemeldet. Dazu die eine
   Zeile aus Nr. 251, die Schritt 15 an eine Stelle geholt und nicht
   entschieden hat.
2. **„Gerät merken" beim Zweitfaktor** — der Rest aus Nr. 141 (E-P5c-41):
   zwei Cookie-Mechanismen werden einmal gebaut, deshalb hier und nicht in
   10c. Dazu, auf Wunsch der Betreiberin (Q-SR-11): ein **frischer Code**
   vor den kritischen Handlungen — ein gemerktes Gerät reicht dafür nicht.
3. **Der Serverschlüssel lässt sich wechseln, ohne dass etwas stumm wird**
   (Nr. 247) — als Vorgang mit Nachweis, nach dem Muster, das S10 für den
   Server-Anteil schon gebaut hat. Und die Betreiber-Rückfrage erfährt von
   einer Rotation (Nr. 233).
4. **Die einzige BetreiberIn kommt ohne Zweitgerät, Codes und Blatt wieder
   hinein** (Nr. 249) — heute nur mit SQL im Datenbankwerkzeug des Hosters,
   und das Protokoll erfährt nichts davon.
5. **Zwanzig gleichzeitige Uploads auf denselben Diensttag** enden nicht
   mehr zu zwölf Teilen in 503 (Nr. 210), sondern in zwanzig Einsätzen.
6. **Ein Proof-of-Work vor der Registrierung** (Nr. 228) — fest eingebaut,
   nicht „auf Anlass": Die Betreiberin hat es am 27.09.2026 so entschieden
   (Q-SR-07, E-SR-26).
7. **Passkeys als zweiter Faktor neben TOTP** (Nr. 350, Nachfassung vom
   27.09.2026, E-SR-29): Der Code-Schritt wird phishingfest — eine
   WebAuthn-Signatur ist an den Ursprung gebunden, ein abgefischter Code
   nicht. *Berichtigt mit H-SR-08 (F-SR-44): Phishingfest ist die
   Passkey-Antwort, nicht der Schritt — der App-Code daneben bleibt
   abfischbar.* Codes und Rückweg bleiben der Notweg.

Dazu zwei Reste aus 17, die mit dessen Merge am 28.09.2026 hierher kamen
(E-R4-37, E-SR-37): **Nr. 250** — `betrieb_server.php` leitet nach POST um
(SR-02) — und **Nr. 344** — der Widerruf einer Freigabe prüft die Kennung
(SR-03).

**Nicht Ziel:** alles, was Schritt 12 gehört (der Review liest, was hier
gebaut ist); die Verschlüsselung der Ortsdaten (12a, R78); **Passkeys als
Ersatz des Passworts** (PRF-Erweiterung, E-SR-28: nicht weiterverfolgt); ein zweiter
Faktor für die Geräte (Uhr und Handy melden sich mit Gerätekennung und
Schlüssel an, nicht mit Passwort — F-P5c-31; kein Cookie, keine Sitzung);
Änderungen an der Auslieferungskette (PK-06 bis PK-08); der Rahmenplan
außer an den vier Anlässen.

**Warum jetzt:** 17 und 18 vor 12, damit der Review aufgeräumte und
gehärtete Seiten liest (Rahmenplan 3). Drei der fünf Punkte sind
**Rückstände beschlossener Sicherheitspakete** — Nr. 242 aus Schritt 16,
„Gerät merken" aus 10c/AP5, Nr. 233 aus P5b/AP9 —, die dort mit Absicht
nicht gebaut wurden, weil sie eine eigene Prüfung brauchen. Diese Prüfung
ist der Kern dieser Runde.

## 2. Befund

### 2.1 Wie gemessen

Am 27.09.2026 auf dem Arbeitszweig bei `26b4761` (R4-10, Web 21.1.8), **nur
lesend**: die acht Backlog-Einträge, die Fahrplanzeile, die betroffenen
Dateien unter `server/` (Sitzung, Anmeldung, Zweitfaktor, Rückweg,
Serverkrypto, Schlüsselblatt, Rückfrage, `ingest.php`), `docs/Technik.md`
4.97c, 4.99q, 5e, 7, `docs/Handbuch.md` 3.1f, 11.4b, 12.5, `docs/Backup-
Format.md` 6 und 7, die Prüfmittel (`pruefablauf.json`, `tools/proben/`,
`tools/quelltext/sitzungshaertung.php`) und die drei gelöschten Konzepte,
aus denen die Punkte stammen (Sitzungsablage `f6cb5fd^`, P5c `00cacef^`,
Zentralisierung über `sitzung_lib.php`). Dazu die Sperrliste, die Konzept
R4 für Schritt 18 geführt hat (dort 2.3), gegen den heutigen Stand.

**Keine Probe gefahren, kein Prüfstand.** Nachgelesen am 28.09.2026 nach
dem Merge von 17: Nr. 250 und 344 im Backlog, `git log 26b4761..f4ac705`
über die SR-Dateien (2.4). Trefferzahlen sind `grep` am
27.09.2026; jede Aussage „so verhält sich die Anlage" stammt aus dem Code
und der Doku. Die örtliche Anlage lief nur für den Prüfstand dieses
Commits. Nicht gefächert: Fünf Themen, jedes hängt an denselben drei
Dateien (`sitzung_lib.php`, `login.php`, `serverkrypto_lib.php`), und eine
Gegenprüfung durch Agenten hätte hier vor allem eines gemessen — dass
niemand die Anlage anfassen durfte.

### 2.2 Die elf Punkte — Einordnung

Spalte „Befund": was am 27.09.2026 gilt. Spalte „Paket": Abschnitt 4.

| Nr. | Punkt | Befund am 27.09.2026 | Einordnung | Paket |
|---|---|---|---|---|
| 242 | Sitzungsbindung per Cookie-Token | gilt: ein `session_start()` (`sitzung_starten()`), `use_strict_mode`, `session_regenerate_id(true)` an zwei Stellen — aber die Sitzungsdatei allein **ist** die Sitzung; `user_id` entsteht an genau einer Stelle (`anmeldung_vollenden()`) | umsetzen | SR-01 |
| 251 | `secure` in zwei Sitzungsarten HTTPS-abhängig | gilt: `SITZUNG_ARTEN` — `app`, `passwort` fest; `lesend`, `einrichtung` von `$_SERVER['HTTPS']` abhängig; `install.php` sagt im Kommentar, warum `einrichtung` so bleiben muss | umsetzen — `lesend` fest, `einrichtung` bleibt (E-SR-06) | SR-01 |
| — | „Gerät merken" (Nr. 141, E-P5c-41) | gilt: nichts davon existiert; Handbuch 3.1f sagt es ausdrücklich („Ein „Gerät 30 Tage merken" gibt es nicht") | umsetzen, mit Migration | SR-02 |
| 247 | Serverschlüssel wechseln als Vorgang | gilt: `serverschluessel_eintragen()` ersetzt nie, `serverschluessel_nachtragen()` prüft gegen die Marke; **kein** Wechsel, **kein** `server_key_alt`; das Muster ist beim Anteil vollständig da (`anteil_wechseln()`, `anteil_alt_entfernen()`, `anteil_zaehlung()`); **sechs** Zwecke hängen am Schlüssel, die Doku nennt vier (F-SR-01) | umsetzen | SR-03 |
| 233 | Rückfrage fragt nie nach dem bisherigen Anteil | gilt: `blatt_werte()` in `api/schluesselblatt_pruefen.php` fragt nur `server_key` und `kdf_anteil`; `anteil_wechseln()` fasst die Marke `schluesselblatt_bestaetigt_am` nicht an | umsetzen — die Rotation macht das Blatt fällig (E-SR-11) | SR-03 |
| 249 | TOTP-Reset der einzigen BetreiberIn ohne Blatt | gilt teilweise: mit Passwort und Notfallblatt der Rückweg (RW-03); ohne beides nur SQL im Runbook (`Technik.md` 7, „Notweg"), ohne Protokoll | umsetzen — Notzugang mit Nachweisdatei (Q-SR-05) | SR-04 |
| 210 | `ingest.php`-Deadlock | gilt teilweise: 1213/1205 antworten 503 `ausgelastet` (E-P5a-52); die Transaktion wird **nicht** wiederholt (`Technik.md` 5e.1: „Die eigentliche Abhilfe steht aus"); `dt_zeitraum_fortschreiben()` läuft in derselben Transaktion wie der Einsatz-Upsert | umsetzen | SR-05 |
| 232 | Fristen der Rückfragen nie im Betrieb abgelaufen | gilt, Anlass nicht eingetreten; SR fasst `einstieg_lib.php` an, baut aber keine dritte Frist | **umhängen → `nächste Backlog-Runde`**, `nur auf Anlass` — Q-SR-06 | SR-06 |
| 350 | Passkeys als zweiter Faktor (Nachfassung 27.09.2026) | gilt: nichts davon existiert — aber `rw_pruefen()` prüft ECDSA-P-256-Signaturen über eine Server-Herausforderung mit phpseclib, `Crypt/EC` und `Crypt/RSA` liegen vendoriert (`Lizenzen.md` 3), es fehlt ein CBOR-Leser; SP-11 und Nr. 146 nennen eine Fremdbibliothek als Voraussetzung (F-SR-13) | **umsetzen — zusätzlich zu TOTP** (Betreiberin, E-SR-29; Q-SR-12), mit Migration | SR-09 |
| 250 | Umleiten nach POST — Rest: `betrieb_server.php` (aus 17, R4-11) | laut Nr. 250: elf Seiten leiten seit Web 21.1.9 um (`flash_setzen()` mit Ort, Ton, Ergebnis); `betrieb_server.php` gibt nach POST noch seine Seite aus — für 18 freigelassen (Konzept R4 2.3) | umsetzen — mit der Karte „Anmeldung", derselbe Weg wie R4-11 (E-SR-37) | SR-02 |
| 344 | `edbak_freigabe_widerrufen()` prüft die Kennung nicht (aus 17, F-R4-33) | laut Nr. 344: ein POST `widerrufen` mit unauflösbarem Handgriff schreibt eine versiegelte `konto.json` in die Wurzel der Ablage und meldet Erfolg; `edbak_ordner_loeschen()` prüft mit `edbak_kennung_gueltig()`, die zwei Nachbarn nicht — 17 ließ `adminbackup_lib.php` für 18 frei (E-R4-37) | umsetzen (E-SR-37) | SR-03 |
| 228 | Proof-of-Work gegen Registrierungs-Spam | gilt, `nur auf Anlass`; der Anlass (verfallene, nie bestätigte Konten je Woche) wäre im Protokoll ablesbar (`konto_geloescht`, `weg = verfall`) und entstünde erst mit offener Registrierung; `registrieren.php` hat Honeypot, Mindestausfülldauer und drei Ratentöpfe, keine Rechenaufgabe | **umsetzen — fest, ohne Anlass** (Betreiberin, Q-SR-07, E-SR-26) | SR-08 |

### 2.3 Der Bestand je Thema

**Sitzung.** Seit Schritt 15 AP2 gibt es in `server/` genau einen
`session_start()`, in `sitzung_starten()` (`sitzung_lib.php`), mit der
Tabelle `SITZUNG_ARTEN` (vier Arten) davor; der Riegel `sitzungshaertung`
zählt das in Stufe 1 nach. `user_id` wird an **einer** Stelle gesetzt
(`anmeldung_vollenden()` in `login.php`), `session_regenerate_id(true)` an
zwei (dort und in `pw_handling.php`), `setcookie()` an zwei (beide Enden in
`session_lib.php`). Die Sitzungsdatei trägt kein Schlüsselmaterial — aber
`user_id`, `epoch`, `csrf`, `last_seen`, den halben Stand `totp_halb`
(Konto, Adresse, Frist, die Herausforderung des Rückwegs), Marken der
Ankündigung und Rückfragen. Der Kopf von `sitzung_lib.php` und `Technik.md`
sagen es wörtlich: „Wer sie liest, ist angemeldet." Schritt 16 hat den
**Ort** gesichert (`.sitzungen/`, `0700`, Rückfall sichtbar); die
**Datei** ist weiter der Beweis. `session_epoch` beendet fremde Sitzungen
nach einem Passwortwechsel — nicht davor.

**Zweitfaktor.** `totp_lib.php`: Geheimnis versiegelt (`totp|<konto>`), zehn
Codes gehasht, `totp_anmeldung_pruefen()`, `totp_abschalten($id, $weg)`.
Der Code-Schritt steht in `login.php` **vor** der Sitzung (E-P5c-53):
Passwort → `totp_halb` → 303 → Code oder Wiederherstellungscode oder der
Rückweg (`?weg=schluessel`) → `anmeldung_vollenden()`. Die Karte
„Zweitfaktor" liegt in `einstellungen.php` (Teile in
`zweitfaktor_teile.php`), das Tor in `auth_guard.php`. Der Vorschlag aus
SP-11 („dieses Gerät 30 Tage merken" als Cookie mit eigenem Hash) ist in
Nr. 141 zweimal auf Nr. 242 verschoben worden.

**Passkeys.** Nichts davon existiert: kein `navigator.credentials` unter
`assets/`, kein CBOR unter `server/`. Die Bausteine liegen dennoch da.
`rw_pruefen()` in `rueckweg_lib.php` prüft eine ECDSA-P-256-Signatur über
SHA-256 mit phpseclib (`Crypt/EC`, `PublicKeyLoader`) — das ist der Kern
einer WebAuthn-Assertion; `rw_herausforderung_stellen()` legt eine
Herausforderung in den halben Stand, und `login.php` nimmt die Signatur als
Formularfeld; `api/rueckweg_anlegen.php` ist ein Registrierungsendpunkt mit
Token; `rueckweg.js` zeigt den WebCrypto-Weg im Browser unter der CSP
`script-src 'self'`; `Crypt/RSA` liegt in derselben vendorierten
Bibliothek (`Lizenzen.md` 3, 338 Dateien mit Prüfsumme). Die Bedienprobe
hält für Chromium schon eine CDP-Sitzung (`newCDPSession`, Fingergerät) —
der virtuelle Authenticator (`WebAuthn.addVirtualAuthenticator`) liegt auf
demselben Kanal; Firefox und WebKit haben keinen (`--motor`, Nr. 300). Ein
Passkey ist an den Ursprung gebunden: Staging und Produktiv sind zwei
Anlagen, die Sandbox unter `localhost` ist ein sicherer Kontext. SP-11
nannte eine Fremdbibliothek als Voraussetzung (F-SR-13).

**Serverschlüssel.** `server_key` in `config.php`, 64 Hexzeichen; Kennung
acht Zeichen (`schluessel_kennung()`), Marke `server_key_kennung` in
`app_state`, drei Lagen (`serverschluessel_zustand()`: fehlt, bereit,
abweichend). `sk_versiegeln($klartext, $zweck)` bindet den Zweck in die
Zusatzdaten; `sk_oeffnen()` liefert `null` ohne Grund. Was daran hängt —
gezählt an den Aufrufern von `sk_versiegeln()` und am rohen Schlüssel:

| Zweck | Ort | Menge | Vom Server umhüllbar |
|---|---|---|---|
| `sicherungsziel:<id>:<feld>` | `backup_targets`, zwei Spalten | je Ziel ein bis zwei Werte | ja |
| `totp\|<konto>` | `users.totp_geheimnis` | je Konto mit Zweitfaktor eine Zeile | ja |
| `adminpaket\|<konto>\|<paket>\|<teil>`, `adminkonto\|<konto>` | Dateien unter `sicherungen/<kennung>/` | je Konto × Pakete × Teile | **lokal ja**; Kopien auf einem Ziel nein |
| `protokollarchiv\|<name>\|<teil>` | `sicherungen/protokoll/<zeit>_<kennung>.zip` — die Kennung steht im **Dateinamen** | eines je Woche | lokal ja (mit neuem Namen); Ziel nein |
| Komplett-Backup (EDKOMP1, Kopf `kdf: null`, roher Schlüssel je 256-KB-Block, Kopf in den Zusatzdaten) | `sicherungen/komplett/*.edk` | je Plan ein Stand, bis zur Aufbewahrungsgrenze | technisch ja (vollständiges Neuschreiben jedes Blocks); Ziel nein |

Zwei Eigenschaften bestimmen den Entwurf: **`edsk1:` trägt keine Kennung**
(anders als `edka1:`), der Server kann also an einer Chiffre nicht ablesen,
welcher Schlüssel sie versiegelt hat — nur am Protokoll-Archiv steht sie
im Namen. Und **was auf einem Sicherungsziel liegt, erreicht kein Vorgang
auf dem Server.** Das Muster für eine Rotation ist beim Anteil vollständig
gebaut (`kdf_anteil_alt`, Lage `rotation`, Marke wandert, Zählung, „Alten
Anteil entfernen" mit Riegel, Blatt mit „bisheriger", Statuszeile); es
fehlt beim Serverschlüssel allein der Schritt, der beim Anteil der Browser
tut: das Umhüllen.

**Rückfrage.** `api/schluesselblatt_pruefen.php` würfelt je Wert zwei
Gruppen, legt sie in die Sitzung, vergleicht alle vier mit `hash_equals()`
ohne Abkürzung; `blatt_werte()` nennt **nur** `server_key` und
`kdf_anteil`, mit dem Satz aus Nr. 233 als Begründung. Fällig ist das Blatt
nach `P3M` ab `app_state.schluesselblatt_bestaetigt_am` (`blatt_faellig()`
in `einstieg_lib.php`; kein Datum heißt fällig). Die Rotation des Anteils
(`anteil_wechseln()`) schreibt `config.php` und die Marke — das Datum der
Blatt-Bestätigung fasst sie nicht an; die Rückfrage kommt also erst mit
dem nächsten Quartal, und dann ohne den Wert, der inzwischen dazugekommen
ist.

**`ingest.php`.** Eine Transaktion von `beginTransaction()` bis zum
`commit()` über den Rumpf der Annahme (Sperrliste, Papierkorb,
Ersetzfenster, Upsert des Einsatzes oder Ruhesegments, Punkte über
`spur_lib.php`, `ingest_tag_offen()` und `dt_zeitraum_fortschreiben()`,
Fortsetzungsmarke); zwei frühe `commit()` in den Dublettenzweigen; der
Fangblock rollt zurück und antwortet bei `gedraengel_erkannt()` mit 503
(`ueberlast_antwort()`), sonst 500 mit Kennung. `transaktion_lib.php`
nennt diesen Rahmen ausdrücklich als die Ausnahme, die Schritt 18 bekommt.
Die Verbindungsprobe stellt den Fall her (`--frei`), misst ihn aber im
Prüfstand nur in Stufe `haupt` (F-SR-08).

### 2.4 Umfeld — was 17 hinterlässt, was 18 anfasst

Konzept R4 hat eine **Sperrliste für Schritt 18** geführt (dort 2.3) und
seine Pakete an diesen Dateien klein gehalten. Gegen den Stand R4-10
gelesen:

| Datei | R4 sagte | 18 tatsächlich |
|---|---|---|
| `sitzung_lib.php`, `session_lib.php`, `login.php`, `auth_guard.php` | 242 | ja — SR-01, SR-02 (F-SR-09: die Sätze „wer sie liest, ist angemeldet" gehen mit) |
| `totp_lib.php`, `zweitfaktor*.php`, `einstellungen.php`, `admin_user.php`, `pw_handling.php` | 242/249 | ja — SR-02 (Gerät merken, vergessen beim Passwortwechsel und Zurücksetzen), SR-04 nur `totp_lib.php`; SR-09 (Passkeys: Abschnitt in der Karte, Knopf im Code-Schritt, `totp_abschalten()` nimmt sie mit) |
| `rueckweg_lib.php`, `api/rueckweg_anlegen.php`, `assets/rueckweg.js`, `vendor/phpseclib3/` | — (nicht auf der Liste) | **nein** — SR-09 liest sie als Muster und lässt sie stehen; daneben entstehen `passkey_lib.php`, `api/passkey_anlegen.php`, `assets/passkey.js` |
| `serverkrypto_lib.php`, `betrieb_server.php`, `betrieb_schluesselblatt.php`, `api/schluesselblatt_pruefen.php`, `einstieg_lib.php`, `sicherungsziel_lib.php`, `*_archiv_lib.php`, `adminbackup_lib.php`, `komplett_lib.php` | 233/247 | ja — SR-03; dazu `jobs_lib.php` (ein Job) und ein neues `schluesselwechsel_lib.php` |
| `install.php` ab Zeile 397 | 247 | **nein** — die Schlüssel entstehen dort, die Rotation lebt in `serverkrypto_lib.php`; R4-09 durfte die Datei unbesorgt anfassen (F-SR-02) |
| `ingest.php`, `diensttag_lib.php`, `transaktion_lib.php` | 210 | `ingest.php` ja, `transaktion_lib.php` nur ein Kommentar; `diensttag_lib.php` nein (R4-15 hat `days.created_at` dort gebaut, SR-05 liest es nur) |
| `konto_lib.php`, `demo_lib.php`, `wartung_lib.php`, `db.php`, `schema.sql`, `migration_lib.php` | 249/228/242/210 | `schema.sql`, `migration_lib.php`, `demo_lib.php`, `jobs_lib.php` — SR-02, dazu `schema.sql`, `migration_lib.php`, `demo_lib.php` in SR-09 (`passkeys`); `db.php` zwei Einträge in `ZF_FRISCH_HANDLUNGEN` (SR-07, SR-09); `konto_lib.php`, `wartung_lib.php` nein |
| `registrieren.php`, `ratelimit_lib.php`, `zip_lib.php` | 228 | `registrieren.php` ja (SR-08, Proof-of-Work); `ratelimit_lib.php` bekommt einen Topf (SR-04); `zip_lib.php` nein |

**Was 17 in diesen Dateien geändert hat und SR übernimmt:** `json_out()` in
`api/rueckfrage.php`, `api/schluessel_erneuern.php`,
`api/schluesselblatt_pruefen.php` (R4-09); der Zweig `user_delete` über
`konto_loeschen()` (R4-10); `days.created_at` (R4-15); die
Demo-Änderungsmarke (R4-14) — je eine Zeile in `ingest.php` und
`auth_guard.php`, die SR nicht umbaut. **17 ist am 28.09.2026 gemergt**
(PR #94, `f4ac705`). Gemessen mit `git log 26b4761..f4ac705` über die
Dateien dieser Tabelle: **acht haben sich bewegt** — `session_lib.php`
(R4-11), `auth_guard.php`, `admin_user.php`, `demo_lib.php` (R4-14),
`einstellungen.php` (R4-13, R4-14), `ingest.php` (R4-12, R4-14, R4-15),
`migration_lib.php` und `schema.sql` (R4-15); `sitzung_lib.php`,
`login.php`, `totp_lib.php`, `serverkrypto_lib.php`, `betrieb_server.php`,
`registrieren.php` und die Rückweg-Dateien unverändert. Der Befund (2.3)
gilt damit weiter; die Umsetzung liest die acht vor SR-01 noch einmal.

**PK-06 bis PK-08** schreiben die Workflows und `CLAUDE.md` 3 und 6; SR
fasst `.github/` nicht an. **Schritt 12** (Bedrohungsmodell) und **12b**
(Code-Review, seit R86 nach S11) lesen danach: `Review-Krypto-Sicherheit.md`
nennt in 3.2 die Sitzung („Sitzungscookie Secure, HttpOnly,
SameSite=Strict; `session_regenerate_id(true)`") — nach SR-01 steht dort
ein Satz mehr, und der Review misst ihn.

### 2.5 Befunde am Bestand

- **F-SR-01 `Technik.md` 4.97c zählt vier Zwecke des Serverschlüssels; es
  sind sechs.** Die Tabelle nennt `sicherungsziel`, `komplett`, `adminpaket`,
  `adminkonto`; dazugekommen sind `protokollarchiv|<name>|<teil>` (Web
  20.39.0, P5c/AP2) und `totp|<konto>` (Web 20.42.0, P5c/AP5), beide ohne
  Zeile dort. Das Schlüsselblatt zählt richtig („Archive des Protokolls und
  die Geheimnisse des Zweitfaktors"). SR-03 zieht die Tabelle nach —
  **und baut das Inventar des Vorgangs nicht aus der Doku, sondern aus den
  Aufrufern** (Abnahme: `grep -rn "sk_versiegeln(" server/` gegen die Liste
  des Jobs).
- **F-SR-02 Die Sperrliste von R4 nannte `install.php` für Nr. 247.**
  Gelesen: Die Datei würfelt beide Geheimnisse einmal, beim Einrichten;
  eine Rotation berührt sie nicht. R4-09 hat den Block davor umgebaut und
  „ab Zeile 397" gemieden — ohne Not, ohne Schaden. Zwei weitere Zeilen der
  Liste (`konto_lib.php`, `wartung_lib.php`/`db.php`) treffen ebenfalls
  nicht (2.4). Eine Sperrliste, die vor dem Konzept geschrieben wird, ist
  eine Vermutung; sie war hier zu weit, nicht zu eng.
- **F-SR-03 Die Runbook-Zeile „Serverschlüssel nachtragen: Adminbereich →
  Backup-Ziele" ist veraltet.** `Technik.md` 7 und 4.97c („auf der Seite
  „Backup-Ziele" nach") zeigen auf den Ort bis S10; seit S10/AP3 steht der
  Knopf auf Betrieb → Servereinstellungen, Karte „Schlüssel des Servers",
  als „Nachtragen vom Blatt" mit Kennungsprüfung — so sagt es
  `Handbuch.md` 12.5 richtig. SR-03 zieht beide Stellen nach.
- **F-SR-04 `edsk1:` trägt keine Kennung, `edka1:` schon.** Für den Anteil
  war die Kennung im Präfix nötig, weil der Server die Hülle nicht öffnen
  kann und trotzdem zählen muss (E-S10-05). Für den Serverschlüssel ist sie
  entbehrlich: Der Server **kann** öffnen, und ob eine Chiffre zum alten
  Schlüssel gehört, zeigt der Versuch. Ein neues Format `edsk2:` hätte
  `Backup-Format.md` 5 und 7.5 („Von Hand öffnen") umgeschrieben, ohne dass
  die Rotation es braucht (E-SR-08).
- **F-SR-05 Was auf einem Ziel liegt, bleibt unter dem alten Schlüssel —
  für immer.** Komplett-Stände, Adminpakete und Protokoll-Archive auf einem
  SFTP- oder FTPS-Ziel kann kein Vorgang auf dem Server umhüllen. Daraus
  folgt eine Regel, die dem Blatt heute widerspricht: „Nach jeder Rotation
  … alte Blätter vernichten" (Kachel „Was damit zu tun ist") gilt für den
  **Anteil** — für den Serverschlüssel darf das alte Blatt erst weg, wenn
  das Ziel nichts mehr trägt, das man mit ihm öffnen wollte (E-SR-10). Der
  Anlass einer Rotation („Verdacht, dass das Blatt in falsche Hände kam")
  heißt zugleich: Die alten Kopien auf dem Ziel sind mit einem Schlüssel
  versiegelt, den ein Fremder haben könnte — der Vorgang sagt das und
  verlangt einen frischen Komplett-Stand unter dem neuen Schlüssel (E-SR-09).
- **F-SR-06 `einrichtung` muss HTTPS-abhängig bleiben.** `install.php` sagt
  es im Kommentar vor `sitzung_starten('einrichtung')`: Die Art läuft auf
  einer Anlage, deren HTTPS-Lage die Einrichterin erst herstellt. Ein
  festes `secure` sperrte genau diese Einrichtung — still, weil der Browser
  das Cookie über HTTP nicht sendete und der Nachweis scheiterte. Nr. 251
  ist damit **eine** Zeile (`lesend`), nicht zwei (E-SR-06).
- **F-SR-07 Zu jedem Grund eines Sitzungsendes gehören zwei Texte.** Der
  Kopf von `session_lib.php` verlangt es (`SESSION_ENDE_GRUENDE`: die
  Zwischenseite in `session_beenden()` und die Meldung in
  `session_ende_text()`). SR-01 bringt den Grund `bindung` mit — und beide
  Texte; die Abnahme zählt sie.
- **F-SR-08 Die Verbindungsprobe läuft im Prüfstand nur in `haupt`.**
  `pruefablauf.json` ordnet sie `db.php` (klein) und `server/**` (haupt) zu;
  `ingest.php` trifft in klein die `ingestprobe`. SR-05 ist eine Korrektur —
  die Probe, die den Fehler misst, liefe von selbst nicht. Das Paket fährt
  sie von Hand (`php tools/proben/verbindung/probe.php --frei 20`) und
  trägt die Zahl ins Prüfdokument; ob `ingest.php` ein eigenes Muster auf
  die Verbindungsprobe bekommt, entscheidet SR-05 (Empfehlung: ja, klein —
  sie braucht die Wurzel der Datenbank und läuft nur örtlich, genau wie
  `nummern`).
- **F-SR-09 Drei Stellen sagen „Wer sie liest, ist angemeldet".** Der Kopf
  von `sitzung_lib.php`, `Technik.md` (Sitzungsablage, Schritt 16) und der
  Backlog-Eintrag Nr. 241 in `Backlog-Erledigt.md` (bleibt wörtlich, ist
  Geschichte). Nach SR-01 stimmt der Satz an den ersten beiden nicht mehr
  und wird ersetzt — nicht ergänzt (`CLAUDE.md` 2, „Entfernte Funktionen
  werden ausgetragen").
- **F-SR-10 Die Fahrplanzeile 18 nannte sechs Punkte, der Backlog trägt
  acht.** Nr. 251 kam mit Schritt 15 (E-ZE-12), Nr. 232 mit R4-01 (Q-R4-12).
  Die Fahrplanzeile nennt sie seit Fassung 139 mit (SR-00; bis zum Merge
  von 17 als 137 gezählt).
- **F-SR-11 Der Komplett-Stand trägt keine Kennung seines Schlüssels.** Der
  Dateiname ist Zeit plus 32 Bit Zufall, der Kopf sagt `kdf: null`. Ob ein
  Stand zum heutigen oder zum bisherigen Schlüssel gehört, zeigt nur der
  Versuch am ersten Block. Für die Plakette „anderer Schlüssel" in der
  Liste der Komplett-Stände genügt das (ein Block je Datei); ein Kopffeld
  `kennung` für **neue** Stände ist billig und macht die Liste ohne Öffnen
  lesbar — SR-03 entscheidet und trägt es in `Backup-Format.md` 6.3 ein.
- **F-SR-12 `nummern.py` meldet auf diesem Zweig vier Überschneidungen,
  die keine sind.** Es vergleicht den Arbeitsbaum gegen
  `merge-base(HEAD, origin/main)` und jeden Remote-Zweig gegen dessen
  Basis; ein Zweig, der auf einem anderen Arbeitszweig sitzt (E-SR-02),
  „legt" dessen neue Nummern mit an — hier **340 bis 343** aus 17. Kein
  Fehler von SR, und das Werkzeug tut, was sein Kopf sagt; nur steht der
  gestapelte Zweig dort nicht unter „Was es nicht kann" — SR-06 trägt den
  Satz ein, kein Umbau. **Folge:** Der Prüfbericht dieses Konzept-Commits
  trägt `nummern` rot. Grün wird die Zeile mit dem Merge von 17 in `main`,
  und erst danach wird der Konzept-PR gestellt — mit einem frischen
  Bericht auf dem dann gemergten Baum (`Pruefablauf.md` 5.3). **Erledigt
  28.09.2026:** 17 ist gemergt, dieser Zweig hat `main` aufgenommen,
  `nummern.py` meldet 0 (Prüfdokument 2).
- **F-SR-13 SP-11 setzt für Passkeys eine WebAuthn-Serverbibliothek
  voraus — das gilt seit Web 20.43.0 nicht mehr.**
  `Vorbereitung-Sicherheitspaket.md` SP-11 und Nr. 146 sagen „brauchen
  eine WebAuthn-Serverbibliothek (Fremdbestandteil)". Seit dem Rückweg
  (Konzept RW) prüft `rw_pruefen()` ECDSA-P-256-Signaturen über eine
  Server-Herausforderung mit phpseclib, und `Crypt/RSA` liegt in derselben
  vendorierten Bibliothek (`Lizenzen.md` 3). Was fehlt, ist ein
  CBOR-Leser für die Registrierung — wenige hundert Zeilen eigener Code,
  kein Fremdbestandteil (E-SR-30). SR-09 trägt den Stand in SP-11 ein,
  ohne den alten Satz zu löschen: Das Dokument ist ein Protokoll seiner
  Zeit. *(Gefunden in der Nachfassung vom 27.09.2026.)*
- **F-SR-14 `nummern.py` meldet während eines offenen Merges die Nummern
  von `main` als Kollision** (28.09.2026, beim Aufnehmen von `main` nach
  `Pruefablauf.md` 5.3). Vor dem Merge-Commit ist
  `merge-base(HEAD, origin/main)` der alte Abzweigpunkt, und die Nummern,
  die `main` seither vergeben hat — hier 344 bis 347 aus 17 —, stehen im
  Arbeitsbaum als „neu" und in `origin/main` als „neu": vier
  Scheinüberschneidungen, und der Prüfstand vor dem Commit (5.3, Schritt 2)
  lieferte für den Merge-Commit einen roten Bericht. Nach dem Commit ist
  die Basis `origin/main`, und es sind null. **Umgangen:** Der Merge-Commit
  trägt keinen Bericht; der Bericht steht im Folge-Commit über denselben
  Baum samt Prüfdokument. Werkzeug und 5.3 nachziehen: **Nr. 352**
  (`nächste Backlog-Runde`), ein Satz steht seit heute in 5.3.
- **F-SR-15 Die lesenden Seiten lesen `user_id` ohne `auth_guard.php`.**
  Gefunden beim Lesen vor SR-01 (28.09.2026): `doku_seite.php`,
  `rechtstext_seite.php`, `notfallblatt.php` und `codeblatt.php` starten die
  Art `lesend` und fragen `$_SESSION['user_id']` selbst — für den
  angemeldeten Kopf und die Kontoadresse auf dem Blatt. Mit einer gelesenen
  Sitzungsdatei hätten sie auch nach der Bindung beides gezeigt; das Konzept
  sah die Prüfung nur in `auth_guard.php` und `login.php` vor. **Gelöst in
  SR-01** (E-SR-39): `sitzung_starten('lesend')` verwirft eine Sitzung mit
  `user_id`, aber ohne passende Bindung, ohne zu schreiben — eine Stelle
  statt vier. Die Sitzungsprobe misst es am Notfallblatt (Teil 5).
- **F-SR-16 Vier weitere Proben fälschen Sitzungen.** Das Konzept nannte für
  SR-01 die Zweitfaktor- und die Rückwegprobe. Gemessen: Auch `rollen`,
  `wartung` und `protokoll` legen Sitzungsdateien selbst an und schicken nur
  `PHPSESSID` — genau das, was die Bindung abweist; die Zweitfaktorprobe
  führte zudem nur **ein** Cookie (das zuletzt gesetzte). Ohne Nachzug wären
  alle fünf rot geworden, und zwar mit einem Fehler der Probe, nicht der
  Anwendung. **Gelöst in SR-01:** Die gefälschten Sitzungen tragen den Hash,
  die Anfragen das Cookie; die Zweitfaktorprobe hat einen Behälter nach
  Namen. Die Protokollprobe zählt den Bindungswert zu den Marken, die das
  Fehlerprotokoll nicht tragen darf.
- **F-SR-17 Die `abmelde-probe` kann kein Cookie prüfen.** Das Konzept
  wollte, dass sie „das zweite Cookie mitprüft". Sie ist eine statische
  Seite unter `http-server`, die den `sessionStorage` nach dem Abmeldeweg
  misst (Nr. 22) — ohne Anwendung, ohne Server-Cookie. **Gelöst:** „Abmelden
  löscht beide Cookies" misst die Sitzungsprobe (Teil 6); die
  `abmelde-probe` bleibt, wie sie ist.
- **F-SR-18 Die Sammelanleitung der Proben nannte eine veraltete Zahl.**
  `tools/proben/LIESMICH.md` sagte „`alle` fährt **22**; Stand 24.09.2026
  22 von 22 grün", `RUF` in `proben.sh` hatte vor SR-01 aber **24** Einträge
  (Zweitfaktor- und Rückwegprobe kamen danach dazu, der Satz blieb). Mit der
  Sitzungsprobe sind es 25; die Zahl ist in SR-01 neu gemessen und
  eingetragen (Prüfdokument 2).
- **F-SR-19 Die Protokollart `einstellung_geaendert` gibt es nicht.** SR-02
  sagt „Protokoll `einstellung_geaendert` wie die übrigen Karten". Gemessen
  (28.09.2026): Die Karten der Servereinstellungen schreiben je eine eigene
  Art (`einstellungen_konten`, `frist_geaendert`, …), eine gemeinsame steht
  in `PROTOKOLL_ARTEN` nicht. **Gelöst in SR-02:** eine eigene Art
  `einstellungen_anmeldung` (Beschriftung „Einstellungen", Reiter
  Verwaltung), gebaut wie `einstellungen_konten`.
- **F-SR-20 Die Umleitung antwortet 302, nicht 303.** SR-02 sagt „nach jedem
  POST der Seite eine 303 mit Flash". R4-11 hat die elf Seiten mit 302
  umgestellt, und die Rollenprobe misst sie alle mit derselben Zeile; eine
  303 auf der zwölften wäre ein zweites Muster ohne Gewinn — jeder heutige
  Browser folgt einer 302 aus einem POST mit GET, und der Bedienweg misst
  genau das („Neu laden ist ein GET"). **Gebaut: 302**, wie R4-11.
- **F-SR-21 `bild()` der Bedienprobe fotografierte die Seite des Läufers.**
  Gefunden beim Ansehen der Bilder für P-SR-05: Ein Weg mit eigenem
  Browserkontext (`eigenerKontext()`) arbeitet auf einer anderen Seite als
  der Läufer, `k.bild()` kannte aber nur dessen. Die zwei Bilder des Wegs
  `zweitfaktor-einrichten` (seit P5c/AP5) und die zwei neuen zeigten die
  Startseite des Demo-Kontos — unter den Namen „Tor", „Codes", „Haken",
  „Gemerkte Geräte". **Gelöst in SR-02:** `bild(name, seite)`; die vier
  Aufrufe geben ihre Seite mit. Dazu wartet der Haken-Weg, bis der Schalter
  ausgeglitten ist — das erste richtige Bild zeigte ihn auf halbem Weg,
  also scheinbar aus.
- **F-SR-22 Den Link-Weg (`pw_handling.php`) fuhr keine Probe bis zum
  Speichern.** SR-02 lässt dort die Geräte vergessen; gemessen hätte das
  niemand. Die Rollenprobe fragt nur, ob ein Link ausgegeben wird, der
  Bedienweg zum Rückweg setzt ein erstes Passwort im Browser. **Gelöst in
  SR-02:** Fall 8b der Zweitfaktorprobe — Link-Token in `password_resets`,
  Hüllen aus Zufall in der Form, die der Server prüft (er öffnet sie nie),
  danach Zeilen 0 und ein Protokolleintrag; Gegenprobe ohne den Aufruf rot.
- **F-SR-23 Die Bedienprobe nannte ihre Zahl zweimal, einmal veraltet.**
  `tools/bedienprobe/LIESMICH.md` sagte oben „68 Bedienwege" und unter
  „Erwartete Zahl" 71 — der Satz oben war bei vier Wegen nicht mitgegangen.
  **Gelöst in SR-02:** Die Zahl steht nur noch unter „Erwartete Zahl" (jetzt
  73), oben ein Verweis.
- **F-SR-24 Vier Prüfmittel rufen Handlungen der Liste auf.** SR-07 nannte
  als Prüfmittel Zweitfaktor-, Rollenprobe und Bedienweg. Gemessen vor dem
  Bau (`grep` über `tools/`): Der **Bilderlauf** fotografiert das
  Schlüsselblatt und läuft rund 25 Minuten, länger als ein Code frisch ist;
  der **Betriebslauf der Anteilprobe** öffnet das Blatt und drückt vier
  Schlüsselgriffe; die **Wartungsprobe** ruft das Blatt mit einer
  gefälschten Sitzung; der **Kreislauf** löscht ein Konto. Ohne Nachzug
  hätte der Bilderlauf nach einer Viertelstunde die Bestätigung unter dem
  Namen „Schlüsselblatt" fotografiert, und die Wartungsprobe hätte den Umweg
  statt der Wartung gemessen. **Gelöst in SR-07:** Die Wartungsprobe legt
  ihre Sitzungen frisch an; Bilderlauf und Betriebslauf gehen die
  Bestätigung mit `codeSchritt()` (dasselbe Formular wie der Code-Schritt),
  ein Griff, der auf ihr landet, bricht ab; der Kreislauf bleibt — er
  meldet sich mit Code an, löscht nach rund 80 Sekunden und scheitert
  sonst laut („ließ sich nicht löschen").
- **F-SR-25 Die Kontoseite zeigte keine Meldung aus einer Umleitung.**
  `admin_user.php` gibt ihre Ergebnisse selbst aus und rief `flash_holen()`
  nie; der Hinweis nach der Bestätigung wäre in der Sitzung liegen
  geblieben, bis eine andere Seite ihn abholt. **Gelöst in SR-07:** Die
  Seite übernimmt eine Meldung aus der Umleitung, wenn der POST selbst
  keine hat.
- **F-SR-26 Die Abnahme „`grep` = Länge der Liste" zählt Kommentare mit.**
  `grep -rn "zweitfaktor_frisch_verlangen(" server/` trifft neben den sechs
  Aufrufen die Definition und die Kommentare, die den Namen nennen, und
  sieht nicht, ob der Name im Aufruf in der Liste steht. **Gelöst in
  SR-07:** Registerzeile Z43 mit eigener Regel über den Tokenizer — je
  Eintrag genau ein Aufruf mit fester Zeichenkette, ein Name außerhalb der
  Liste und ein Aufruf ohne feste Zeichenkette sind Befunde.
- **F-SR-27 Die Runbook-Aufzählung der Wartungsausnahmen war unvollständig.**
  `Technik.md` 7 („Was währenddessen erreichbar bleibt") nannte sechs
  Betriebsseiten und ließ `betrieb_sicherheit.php`, Komplett-Backup und
  Backup-Ziele aus — die Konstante führte sechzehn Einträge. **Gelöst in
  SR-07:** vollständig, mit `zweitfaktor.php` und dem Verweis auf die
  Konstante als maßgeblich.
- **F-SR-28 Die Migrationskommentare versprachen einen Vergleich, den keine
  Probe fährt.** Die Kommentare an `2026_09_28_vertraute_geraete` (SR-02)
  und dem Entwurf von `2026_09_28_passkeys` sagten, die Schemaprobe halte
  fest, dass Migration und `schema.sql` dieselbe Tabelle bauen. Die
  Schemaprobe spielt `schema.sql` auf vier Fassungen ein und prüft die
  Vorabliste; migriert gegen frisch vergleicht niemand. **Gelöst in SR-09,
  soweit es hier geht:** beide Kommentare berichtigt, der Vergleich von Hand
  gemessen (`SHOW CREATE TABLE` ohne `AUTO_INCREMENT`, beide Tabellen
  zeichengleich — Prüfdokument), die Lücke als **Nr. 353** (`nächste
  Backlog-Runde`).
- **F-SR-29 Der Bilderlauf kann den Abschnitt „Passkeys" nicht zeigen.** Er
  läuft gegen `https://127.0.0.1:8443`, und für eine IP-Adresse gibt es
  keine Passkeys (E-SR-42) — der Abschnitt fehlt dort mit Absicht. P-SR-05
  verlangt Bilder der Karte mit Liste; **gelöst in SR-09:** Sie kommen aus
  dem Bedienweg `einstellungen-profil-passkey`, der für seinen Lauf die
  Adresse auf `localhost` stellt (Karte mit einem Passkey, Code-Schritt mit
  dem Knopf). Der Bilderlauf selbst bleibt, wie er ist.
- **F-SR-30 Die Bedienprobe nannte nach SR-07 noch 73 Wege.** SR-07 hatte
  `betrieb-server-frischer-code` dazugebracht (74 im Prüfbericht), die
  `LIESMICH` nicht nachgezogen — dieselbe Sorte wie F-SR-18 und F-SR-23.
  **Gelöst in SR-09:** 74, mit SR-09 75, und der Satz sagt, dass hier bis
  SR-09 73 stand.
- **F-SR-31 2,2 Sekunden reichen OPcache nicht immer.** Teil 5d der
  Zweitfaktorprobe und der Bedienweg stellen `app.base_url` auf `localhost`
  und warteten danach 2,2 s. Beim Gegenlesen vor dem Prüfstand lief die
  Probe zweimal hintereinander: im ersten Lauf **7 von 9** Erwartungen in
  5d rot (kein Passkey-Knopf — der Server sah noch die alte Adresse), im
  zweiten 0. OPcache rechnet mit der Anfragezeit in **ganzen Sekunden**:
  Wurde `config.php` zuletzt in Sekunde L geprüft, gilt die alte Fassung
  bis einschließlich L+2, und sicher ist erst ein Abstand von drei
  Sekunden. Dieselbe Falle hatte die Ratenprobe schon (F-P5c-123, dort
  1,2 s). **Gelöst in SR-09:** beide warten 3,2 s, der Grund steht im
  Kopf; gemessen mit wiederholten Läufen (Prüfdokument).
- **F-SR-32 Die Mailprobe kannte `bezeichnung` nicht — und ist beim Bau
  nicht gefahren worden.** Sie hält für jede Vorlage des Katalogs die
  Pflichtwerte in einem Beispielsatz; die zwei neuen Vorlagen
  (`passkey_angelegt`, `passkey_entfernt`) brachten den Wert `bezeichnung`,
  der dort fehlte. `mail_lib.php` stand im Paket, die Mailprobe hätte
  örtlich laufen müssen und lief nicht — gefunden hat es erst der Prüfstand
  (1 rot, 53 grün). Dieselbe Sorte wie F-RP-04. **Gelöst in SR-09:** der
  Wert steht im Beispielsatz, Mailprobe 51 Prüfungen, 0 Befunde, danach der
  Prüfstand neu.

**Aus der Gegenlesung H-SR-08** (28.09.2026, Stand `dca31f1`, E-SR-44):
sieben Fable-Leser, je Befund eine oder zwei Gegenprüfungen, ein Kritiker —
**41 Befunde, alle bestätigt: 0 hoch, 1 mittel, 19 niedrig, 21 Hinweise.**
Hier nach Themen zu zwölf zusammengefasst; die Einzelkennungen (M1 bis M35,
N1-1 bis N2-3) und ihre Belege stehen im Prüfdokument 2. Kein Befund
erlaubt, ohne den eigenen Passkey hineinzukommen; der CBOR-Leser hat an 81
gezielten Eingaben kein Längenfeld falsch gelesen.

- **F-SR-33 RSA-Schlüssel hatten nur eine Untergrenze** (M1 mittel, M2).
  `pk_cose_laden()` prüfte die Bitlänge ab 2048 und sonst nichts. Über 3072
  Bit Modul lehnt OpenSSL einen Exponenten über 64 Bit ab, und phpseclib
  rechnet die Potenz dann in reinem PHP (berichtigt mit F-SR-58): Ein Schlüssel mit 4096 Bit Modul und 4096 Bit Exponent
  kostete **6,2 s je Prüfung** (nachgestellt, weil der Gegenprüfer von M1
  ausfiel), 8192/8192 Bit beim Leser 45 s — wer ein Konto mit Zweitfaktor
  hat, legt sich so einen Passkey an und lastet die Anlage mit parallelen
  Anmeldeversuchen aus. Mit **e = 1** ist die Signatur das Bild der
  Nachricht und ohne Geheimnis rechenbar; e = 0, 2, gerade und ein gerades
  Modul gingen ebenso durch — alles nur für den eigenen Schlüssel. **Gelöst
  mit H-SR-08:** Modul 2048 bis 4096 Bit und ungerade, Exponent ungerade,
  3 bis 64 Bit.
- **F-SR-34 Der Leser war nachgiebiger, als sein Kopf sagte** (M3, M4, M8,
  M16, M17, M21, M22, M23). PHP machte aus dem Textschlüssel „1" den
  Schlüssel 1, und ein COSE-Schlüssel mit lauter Textlabels ging durch;
  verbotene Zusatzlabels (auch `d`) wurden überlesen; Karte und Liste waren
  nach dem Lesen nicht zu unterscheiden, die Erweiterungen mussten keine
  Karte sein; die Tiefe zählte Werte mit; Base64url nahm einen Zeilenumbruch
  am Ende und nicht-kanonische Endzeichen; eine leere Herausforderung in der
  Ablage wäre durchgegangen, `crossOrigin` zählte nur als Bool, `topOrigin`
  wurde nicht gelesen; eine leere DER-Folge endete in einem TypeError; ein
  EC-Punkt mit x ≥ p wurde still reduziert; `BS` ohne `BE` ging durch.
  **Gelöst mit H-SR-08:** numerische Textschlüssel und fremde Labels sind
  eine Ablehnung, `pk_cbor_art()` prüft das Kopfbyte, wo eine Karte
  verlangt ist, die Tiefe zählt Behälter, Base64url mit `\z` und
  Rückprobe, Herausforderung mindestens 43 Zeichen, `crossOrigin` muss
  fehlen oder `false` sein, `topOrigin` ist eine Ablehnung, die Signatur
  wird vorher als ASN.1-Folge gelesen, x und y liegen unter p, `BS` verlangt
  `BE`.
- **F-SR-35 Kein Deckel, keine Typen** (M6, M7). Der Endpunkt nahm jede
  Rumpfgröße, und eine Liste aus 1,4 MB leerer Ketten brach im
  Speicherfehler ab statt in einer Ablehnung; Felder vom falschen JSON-Typ
  gingen durch `(string)` — eine PHP-Warnung je Anfrage im Reiter System,
  für jeden auslösbar, der das Passwort hat, und eine Bezeichnung als Liste
  hieß „Array". **Gelöst mit H-SR-08:** `pk_feld()` (Text, kanonisch,
  dekodiert höchstens `PK_FELD_MAX` = 32 KiB), `PK_ANTWORT_MAX` (192 KiB)
  im Endpunkt und in beiden Formularwegen, die Bezeichnung als Text.
- **F-SR-36 Der Zähler — nicht atomar, und seine Warnung erreichte
  niemanden** (M11, N1-1, N1-3). Zwei gleichzeitige Anmeldungen mit
  derselben Kennung wurden beide angenommen. Der Eintrag `passkey_zaehler`
  steht im Reiter Protokoll, den die Rolle `user` nicht sieht; die Meldung
  „versuche es noch einmal" ließ den einzigen Hinweis nach einem Klick
  verschwinden, und der Eintrag nannte eine Nummer, die niemand einem
  Passkey zuordnen kann — E-SR-33 baut aber darauf, dass die Betroffene
  sieht und entscheidet. **Gelöst mit H-SR-08:** `UPDATE … WHERE id = ? AND
  zaehler = ?` (bei beiden 0 nur `zuletzt_am`); Mail `passkey_zaehler` mit
  Deckel über `gewarnt_am` (E-SR-46); Meldung „zuletzt auf einem anderen
  Gerät benutzt — vielleicht eine Kopie"; Protokoll mit dem Namen.
- **F-SR-37 Fehlversuche ohne Fehler** (M14, N1-2). Eine Passkey-Antwort
  ohne, mit abgelaufener oder verdrängter Herausforderung zählte im Topf
  `totp`, ebenso jede Ablehnung wegen des Zählers — nach dem Gebrauch einer
  Kopie auch die des Originals mit gültiger Signatur; fünf in 15 Minuten
  sperrten das Konto mit „Zu viele falsche Codes". Der Rückweg zählt
  denselben Fall nicht. **Gelöst mit H-SR-08** (E-SR-50): `PkFehler` trägt
  einen Code, `pk_anmeldung_pruefen()` eine Art, und nur `pruefung` zählt.
- **F-SR-38 Der Lebenszyklus hatte Lücken** (M15, M30, N2-3).
  `totp_abschalten()` löschte die Passkeys **hinter** seiner Transaktion;
  scheiterte das, galten sie nach dem Wiedereinschalten weiter, und keine
  Einrichtung räumte sie. Reset-Mail und die zwei Rückfragen sagten nicht,
  dass die Passkeys mitgehen, und der SQL-Notweg im Runbook ließ sie und
  die gemerkten Geräte stehen — genau der Weg der einzigen BetreiberIn.
  **Gelöst mit H-SR-08:** Geräte und Passkeys in der Transaktion, das
  Einrichten räumt verwaiste (`weg` `einrichtung`), `pk_anlegen()` liest
  `totp_seit` unter `FOR UPDATE`, die Texte, zwei Zeilen SQL im Runbook.
- **F-SR-39 Endpunkt und Ablagen** (M26, M27, M28, M32). „Diesen Passkey
  gibt es hier schon" antwortete für die Kennungen **aller** Konten — mit
  Attestation `none` frei wählbar, also ein kleines Orakel —, und ein
  Wettlauf zweier Konten endete in 500. `passkey_best` trug kein Konto. Der
  Endpunkt verbrauchte die Herausforderung vor der Prüfung der Bezeichnung:
  Nach einem 400 blieb ein Passkey im Authenticator ohne Zeile. Steuerzeichen
  in der Bezeichnung fügten in der Textmail Zeilen ein. **Gelöst mit
  H-SR-08:** nach außen „nicht angenommen" (E-SR-45), `23000` → dieselbe
  Antwort, alle drei Ablagen mit Konto (`pk_ablage_passt()`), Rumpf und
  Bezeichnung vor der Entnahme und im Browser vor dem Dialog,
  `pk_bezeichnung_saeubern()`.
- **F-SR-40 Ein Umlaut-Name oder ein Punkt am Ende von `app.base_url`**
  (M5) ergab einen Ursprung, den kein Browser schickt: Knopf da, jede
  Zeremonie gescheitert. **Gelöst mit H-SR-08:** `idn_to_ascii()` (UTS 46),
  der Punkt fällt weg.
- **F-SR-41 Eine andere Adresse ließ tote Passkeys stehen** (M13, M29).
  Nach einem Umzug oder einem Komplett-Stand auf einer anderen Adresse
  bot die Anmeldung Passkeys an, die dort nie gelten, der Browser meldete
  „abgebrochen", und die Zeilen zählten zur Zehnergrenze; Handbuch und
  Backup-Format sagten „gibt es ihn nicht". Unter einem zweiten Namen der
  Anlage erschien der Knopf ebenso ins Leere (M13). **Gelöst mit H-SR-08:**
  `rp_id` je Zeile (E-SR-48), die Karte zeigt fremde mit Plakette, der Text
  des Browsers nennt den dritten Fall. M13 bleibt: Keine Anlage läuft unter
  einem zweiten Namen (E-SR-42).
- **F-SR-42 Der erste Index über 767 Byte** (M31). `uq_passkeys_credential`
  über `VARCHAR(1364)` scheitert unter einem COMPACT-Zeilenformat, wie es
  manche Hoster noch fahren, und die Schemaprobe sieht es nicht. **Gelöst mit H-SR-08:** eindeutig über `credential_hash CHAR(64)`
  (E-SR-47), die Migration an Ort und Stelle (E-SR-51).
- **F-SR-43 Die Passkeyprobe maß „nicht angenommen", nie den Grund** (M9,
  M10, M33, M34). Ob ein Fall an der behaupteten Stelle scheiterte, sah sie
  nicht; den ED-Pfad, AT in der Anmeldung, `crossOrigin`, Kennungslängen,
  Rest hinter den Daten, Base64url-Fehlformen und die Kontobindung maß sie
  gar nicht, die Ablehnungen nur mit ES256; Teil 5d „dieselbe Antwort noch
  einmal" hätte auch der Zähler abgewiesen. **Gelöst mit H-SR-08:** jede
  Ablehnung mit Grund-Stück, bei der Anmeldung mit Art; die fehlenden
  Fälle; RS256-Ablehnungen; 5d mit alter Herausforderung und frischer
  Signatur — 104 Prüfungen (57).
- **F-SR-44 Texte, die mehr versprachen als der Code** (M12, M18, M19, M20,
  M24, M25, M35, N2-1, N2-2). „Der Code-Schritt wird phishingfest" (E-SR-29,
  CHANGELOG, Handbuch): Solange der App-Code daneben steht, weicht eine
  nachgemachte Seite auf ihn aus. Die Begründung von E-SR-42 über den
  „Proxy mit eigenem Namen" trug so nicht. Was bewusst so bleibt (BER-Lesung
  der ES256-Signatur, Erweiterungen mit einfachem Wert, AT in der Anmeldung)
  stand nirgends; was der Schlüsselbund speichert, auch nicht; zwei kleine
  Abweichungen vom Paketstext (der Abschnitt nur in `einstellungen.php`,
  Klick-Handler statt `anlegen()`/`bestaetigen()`) nannte der Erledigt-Block
  nicht. Im Zustand „Geheimnis lässt sich nicht öffnen" meldet ein Passkey
  weiter an, fünf Texte sagten „nur noch Wiederherstellungscodes", und der
  empfohlene Weg „ausschalten und neu einrichten" löscht die Passkeys, ohne
  dass es dasteht. **Gelöst mit H-SR-08:** Kopf der Bibliothek mit „Was
  bewusst so bleibt", E-SR-42 berichtigt, E-SR-49, die Texte in `login.php`,
  `zweitfaktor.php`, `einstellungen.php`, Handbuch 3.1f und `Technik.md`;
  21.10.0 im CHANGELOG mit Vermerk berichtigt.

**Aus der Nachprüfung der Behebung** (29.09.2026, E-SR-44): fünf Fable-Leser
über die geänderten Stellen, je ein Gegenprüfer — **24 Meldungen, davon zwei
derselbe Befund (B-1, C-2); 23 bestätigt, einer unklar (D-3): 1 mittel,
6 niedrig, 17 Hinweise.** Zusammengefasst zu acht; die Einzelkennungen
(A-1 bis E-6) stehen im Prüfdokument 2. Je Themenblock das Urteil der Leser:
RSA-Grenzen, Leser, Zähler, Speicherung und Endpunkt behoben; teilweise
M21 (Signaturform), M6 (das Formularfeld), N1-2 (Urheber), F-SR-40 (ß),
M12, M25, N2-1 (je eine Textstelle).

- **F-SR-45 Die ES256-Signatur hatte keinen Formdeckel** (A-1 mittel, A-2).
  Das Feld `signature` durfte 32 KiB groß sein, und phpseclib liest BER:
  geschachtelte unbestimmte Längen kopiert es je Ebene — **356 MiB und
  2,1 s je Anfrage**, auf 128 MB Speicher ein Fatal Error, erreichbar mit
  Passwort und eigenem ES256-Passkey vor der Zählung. Die ASN.1-Vorprüfung
  aus F-SR-34 fing nur die leere Folge; eine Zeitangabe mit Nullbyte endete
  in einem ValueError, ein fremdes Etikett an Stelle von r in einem
  TypeError trotz Vorprüfung, eine konstruierte Bitkette in einer
  PHP-Warnung. Nicht neu eingeführt (der alte Weg rechnete dasselbe), aber
  in der geänderten Stelle. **Gelöst:** `pk_es256_form()` prüft die Form
  selbst — Folge mit genau zwei INTEGER zu 1 bis 33 Byte, höchstens 73
  Byte —, dahinter `instanceof BigInteger` für r und s; eine RS256-Signatur
  muss so lang sein wie der Modul.
- **F-SR-46 Kleine Strengen der Behebung** (A-5, A-6, B-2, B-3, D-1). n und
  e mit führenden Nullbytes wurden angenommen und still gekürzt (RFC 8230
  verlangt die kürzeste Form — dieselbe „Reparatur statt Ablehnung" wie bei
  den Koordinaten); `pk_spki_laden()` lud RSA jeder Größe, die Grenzen
  galten nur beim Anlegen; der Felddeckel ließ 32 770 statt 32 768 Byte zu;
  `attStmt` durfte eine Liste sein; die IDN-Umschrift lief mit
  Übergangsregeln (`straße` → `strasse`, der Browser schickt
  `xn--strae-oqa`). **Gelöst:** alle fünf, je mit Probenfall.
- **F-SR-47 Das Formularfeld selbst** (B-1, C-2). `passkey_antwort[]=x`
  machte aus dem Feld eine Liste, und `(string)` warf die PHP-Warnung, die
  F-SR-35 für die Felder darin abgestellt hatte. **Gelöst:** Text oder leer,
  in `login.php` und `zweitfaktor.php`; gemessen über HTTP.
- **F-SR-48 Der Endpunkt protokollierte, was er nicht geprüft hatte** (E-1).
  Jede Einsendung ohne gültige Herausforderung schrieb „Passkey
  abgewiesen" — ohne Ratentopf, auch für einen zweiten Reiter oder einen
  zweiten Klick, der zudem einen Passkey im Authenticator ohne Zeile
  hinterließ. **Gelöst:** Art `herausforderung` → 400 `abgelaufen` ohne
  Protokoll (die Regel von E-SR-50); `passkey.js` lässt den Knopf nach einer
  Antwort des Servers aus.
- **F-SR-49 Der Preis von E-SR-50, und der Urheber** (C-1, N1-2-Rest). Eine
  Kopie mit zurückliegendem Zähler holt ohne Bremse auf, und jeder Versuch
  schreibt eine orange Zeile; im Code-Schritt trägt der Eintrag den Urheber
  `job`. **Gelöst:** der Preis benannt (E-SR-52), der Eintrag trägt
  `daten.weg` (`anmeldung` oder `bestaetigung`).
- **F-SR-50 `protokoll()` schluckt in einer Transaktion einen Deadlock**
  (D-3, vom Gegenprüfer „unklar"). Bricht der INSERT mit 1213 ab, rollt
  InnoDB die ganze Transaktion zurück und beendet sie; der Rumpf merkt es
  nicht, die folgenden Anweisungen laufen im Autocommit, und erst `commit()`
  wirft. Seit SR-02 steht `protokoll()` auch beim Passwort in einer
  Transaktion (E-SR-40), dort als letzte Anweisung. **Nicht hier gelöst:**
  Nr. 354 (E-SR-53). *Bis zur dritten Lesung stand hier „`totp_abschalten()`
  meldet Erfolg" — nachgestellt ist es ein Fehler mit halbem Stand
  (F-SR-63).*
- **F-SR-51 Was die Proben der Behebung nicht maßen** (A-3, B-4, C-3, D-4,
  E-4). Die RSA-Grenzen nur von außen, die Signaturform nur mit der leeren
  Folge, `BS` ohne `BE` nur bei der Registrierung, der Deckel an keinem
  Eingang über HTTP, die Hälfte „`pruefung` zählt" von E-SR-50 gar nicht,
  die Atomarität von `totp_abschalten()` nur im Erfolgsfall, und dass ein
  400 am Endpunkt die Herausforderung stehen lässt. **Gelöst:** alle als
  Fälle — die Atomarität mit einem Auslöser, der das Löschen scheitern
  lässt; Passkeyprobe 122, Teil 5d 13 Fälle.
- **F-SR-52 Texte** (A-4, C-4, D-2, E-2, E-3, E-5, E-6). Die Begründung der
  64 Bit (OpenSSL rechnet die Potenz, nicht die Prüfung, und die Grenze gilt
  nur über 3072 Bit), zwei Code-Kommentare „nur noch
  Wiederherstellungscodes", 413 statt 400 in `Technik.md`, „phishingfest" in
  Zeile 98 und E-SR-29 ohne Vermerk, der Schlüsselbund nicht im
  Datenschutz-Baustein, `intl` als Voraussetzung für Umlaut-Namen nicht
  genannt, und dass zwei Zähler-Warnungen zu einer Mail werden können.
  **Gelöst:** alle Stellen.
- **F-SR-53 Das Gedränge hatte drei Stellen, nicht eine.** Das Konzept
  (E-SR-12) sah die `days`-Zeile. Die Messung zu SR-05 — Verbindungsprobe
  mit `--frei 20`, das Serverprotokoll nach Datei und Zeile gezählt — ergab
  in drei Runden zu 20 **39 × 503**: 16 am `UPDATE days`, 12 am Upsert auf
  `missions`, 10 an `UPDATE devices SET last_seen`. Die Geräte-Zeile ist
  derselbe Kreuzungspunkt wie der Diensttag: `missions.device_id` hat einen
  Fremdschlüssel, jeder Upload nimmt die geteilte Sperre und wollte später
  die exklusive. Der Upsert war die Folge der beiden: Ohne sie verschwand er
  aus der Zählung. **Gelöst in SR-05** (E-SR-54): beide Zeilen hinter dem
  Commit.
- **F-SR-54 Die Verbindungsprobe kannte die Lage „alle Plätze frei" nicht.**
  Die Abnahme von Nr. 210 und dieses Konzepts ist `--frei 20`; dort stand
  die Erwartung „Die Enge schlägt durch" immer offen, weil es keine Enge
  gibt, und die Probe endete rot, auch mit 0 × 503. **Gelöst in SR-05**
  (E-SR-55): zwei Lagen, eine Runde ohne Enge, dazu zwei neue Erwartungen
  in beiden — kein 503 aus dem Gedränge, keines im Serverprotokoll.
- **F-SR-55 Nach dem Umbau misst die Verbindungsprobe die Schleife nicht
  mehr** — es gibt kein Gedränge, also keine Wiederholung; und damit auch
  nicht, ob ein zweiter Anlauf die Antwortfelder doppelt füllt. **Gelöst in
  SR-05:** Teil 12 der Ingestprobe erzwingt die Wiederholung über einen
  Auslöser (1213 per `SIGNAL` — kein echter Deadlock, F-SR-67) und eine
  MEMORY-Tabelle (ihr Stand überlebt den Rollback). Die
  erste Fassung setzte den Auslöser an das Einfügen des Einsatzes, **vor**
  die Punktschleife, und maß das Zurücksetzen der Prüfliste nicht — die
  Gegenprobe ohne Zurücksetzen blieb grün; der Auslöser sitzt jetzt am
  `UPDATE … letzter_punkt_am` dahinter. Und eine Gegenprobe über HTTP, die
  unmittelbar nach dem Ändern von `ingest.php` lief, maß den alten Stand
  (OPcache der Sandbox); mit vier Sekunden Pause wurde sie rot (Prüfdokument).
- **F-SR-56 Ein `usleep()` außerhalb von `ratelimit_lib.php` ist ein
  Registerbefund** (Z33, Decke 0 — jede verzögerte Antwort gehört zu
  `rate_gleiche_dauer()`). Der erste Einbau hatte zwei. **Gelöst in SR-05:**
  `gedraengel_abstand()` in `wartung_lib.php` bei den übrigen
  Gedränge-Funktionen, Z33 mit Decke 1 und ausgeschriebenem Grund: Es wartet
  auf die Datenbank, nicht vor einer Antwort.
- **F-SR-57 Der Paketstext nennt einen Abschnitt, der etwas anderes
  beschreibt.** „Der Weg durch `ingest.php`" in `Technik.md` gehört zu den
  Schnittvermerken (S4/A2), nicht zum Ablauf der Annahme. **Gelöst in
  SR-05:** Die Schleife steht in 5e.1, wo das Gedränge steht; der
  Schnittabschnitt bleibt, wie er ist.
- **F-SR-58 Zwei alte Sätze zu den 64 Bit** (dritte Lesung DA-1). Im
  CHANGELOG-Eintrag zu 21.11.0 stand neben der berichtigten Begründung noch
  die alte („phpseclib überlässt die RS256-Prüfung OpenSSL nur bis zu einem
  Exponenten von 64 Bit"), und F-SR-33 hier trug denselben Satz — F-SR-52
  meldete „alle Stellen". **Gelöst in 21.11.2:** beide ersetzt.
- **F-SR-59 `pk_spki_laden()` prüfte die Größen, nicht alle Grenzen**
  (DA-2). Der Lader nahm e = 1, gerade Exponenten und einen geraden Modul;
  die Texte sagten „dieselben Grenzen". Keine Kostenwirkung, und eine solche
  Zeile kommt nur per Schreibzugriff auf die Datenbank. **Gelöst in
  21.11.2:** Exponent ungerade und mindestens 3, Modul ungerade — wie
  `pk_cose_laden()`; die kürzeste Form entfällt, das SPKI ist schon
  kanonisch.
- **F-SR-60 Drei Einarbeitungen ohne Beleg** (DA-3, DA-4, C3-3). Vom Lader
  maß die Probe einen der neun Fälle in den Grenzen; von der Signaturform
  keine der erlaubten Schreibweisen (`81 L`, zusätzliche Null), obwohl der
  Kopf der Bibliothek sie zusagt; `daten.weg` am Zähler-Eintrag gar nicht.
  Ein Rückbau jeder der drei Stellen wäre grün geblieben. **Gelöst in
  21.11.2:** als Fälle der Passkeyprobe.
- **F-SR-61 Die Bestätigungsseite: Liste und Deckel nur gelesen** (3B-1).
  Teil 5d schickte beides an den Code-Schritt von `login.php`, nicht an
  `zweitfaktor.php`; F-SR-47 und F-SR-51 sagten „in beiden" und „gemessen".
  Der Code beider Dateien ist gleich und richtig. **Gelöst in 21.11.2:** Fall
  5a in Teil 5d.
- **F-SR-62 Der Knopf sperrt nach dem Absenden, beschrieben war „nach einer
  Antwort des Servers"** (3B-2). `gesendet` steht vor dem `postJson()`; nach
  einem Netzfehler, einem 413 oder einem 403 `zweitfaktor_frisch` blieb der
  Knopf aus, und die Meldung sagte nicht „neu laden" — das stand nur in den
  zwei Antworten 400 `abgelaufen` und `passkey`. Das Verhalten ist richtig
  (der Passkey liegt schon im Authenticator), die Texte nicht. **Gelöst in
  21.11.2** (E-SR-58): Nach dem Absenden hängt `passkey.js` „Bitte die Seite
  neu laden." an jede Meldung, die es nicht schon sagt; Kommentar,
  CHANGELOG, `Technik.md` 4.99q und Prüfdokument sagen „nach dem Absenden".
- **F-SR-63 Nr. 354 war falsch beschrieben, und seine Abnahme stellte den
  Fall nicht her** (C3-1, C3-4). Nach einem Deadlock beendet InnoDB die
  Transaktion, die folgenden Anweisungen derselben Verbindung laufen im
  Autocommit, und `commit()` wirft „There is no active transaction" — der
  Aufrufer meldet also keinen Erfolg, sondern einen Fehler, und was nach dem
  Eintrag kam, steht. Ein `SIGNAL` im Auslöser bricht nur die Anweisung ab.
  **Nachgestellt** mit einem echten Deadlock (Prüfdokument 2): in
  `totp_abschalten()` Faktor an, Codes und Geräte zurück, die Passkeys
  gelöscht samt Eintrag „mit dem Zweitfaktor", keine Mail. Nicht schlimmer
  als beschrieben, aber anders (E-SR-56). **Gelöst in 21.11.2:** Nr. 354,
  F-SR-50 und Prüfdokument 1 berichtigt; die Behebung bleibt Nr. 354.
- **F-SR-64 Die Zählung der Nachprüfung ging nicht auf** (C3-2).
  Prüfdokument 2: „23 Befunde: 1 mittel, 6 niedrig, 17 Hinweise" — das sind
  24, und die Klammer nannte fünf. Je Meldung gezählt sind es 24 (1, 6, 17),
  je Befund 23 (1, 5, 17). **Gelöst in 21.11.2:** beide Zählungen
  ausgeschrieben.
- **F-SR-65 Ein Formularfeld als Liste gibt eine PHP-Warnung — außerhalb
  der Passkeys** (3B-4). `(string)($_POST['code'] ?? '')` im selben
  Formular wie `passkey_antwort`, dazu `signatur` und `tokens`; projektweit
  114 Stellen. Älter als SR-09, nicht Gegenstand von H-SR-08. **Nicht
  hier gelöst:** Nr. 355 (E-SR-59).
- **F-SR-66 Ein Bedienweg lief im ersten Prüfstand zu SR-05 in eine
  Zeitüberschreitung** — `p5c-ap8-adhoc-tag-in-der-luft`, im `finally` an
  `#basesel`, 30 s unsichtbar. Unsichtbar ist es genau dann, wenn der Tag
  auf „Anderes Rettungsmittel" steht; der Weg las also schon einen
  umgestellten Tag als Anfang. Allein wiederholt war er grün, ebenso der
  zweite ganze Lauf. Die Erklärung, die passt — der Weg davor stellte
  denselben Tag zurück und wartete eine feste Zeit auf die Antwort —, ist
  **nicht belegt**; nachgestellt ist die Wirkung (ein Vorschritt stellt den
  Tag um, die alte Fassung läuft in dieselbe Zeitüberschreitung). **Gelöst in 21.11.2:**
  Die Wege in `ap6.mjs` warten auf die Antwort von `api/day.php`, und ein
  Anfang auf „Anderes Rettungsmittel" bricht mit einem Satz ab, der das sagt.
- **F-SR-67 Teil 12 der Ingestprobe hieß „erzwingt den Deadlock"** — C3-4
  gilt auch dort: Der Auslöser meldet 1213 per `SIGNAL`, InnoDB rollt
  nichts zurück. Für `ingest.php` ist das gleichwertig, weil der `catch` die
  Transaktion selbst zurückrollt und der Rumpf nichts schluckt (der innere
  `catch` der Punktschleife reicht alles außer Dubletten weiter); den echten
  Deadlock misst die Verbindungsprobe. **Gelöst in 21.11.2:** die Sätze im
  Kopf von Teil 12, in `Technik.md` 5e.1, im CHANGELOG und hier (F-SR-55).

*Befunde aus SR-03 (29.09.2026):*

- **F-SR-68 „Mit Ankreuzfeld wie beim Anteil" — der Anteil hat keins.**
  Das Paket SR-03 sagt es so; „Server-Anteil wechseln" hat nur die
  Rückfrage des Browsers. Gefragt statt nachgebaut: Die Betreiberin hat den
  Haken als Pflicht entschieden, im Server geprüft (E-SR-63).
- **F-SR-69 Halbe Nebendateien im Kontoordner hätte die Aufbewahrung
  angefasst.** Der erste Entwurf baute ein umgehülltes Paket neben dem
  alten. Die Aufbewahrung und `edbak_verzeichnis_abgleichen()` lesen die
  Kontoordner; eine halbe Datei dort wäre entweder als Paket gezählt oder
  von einem Aufräumer entfernt worden, während der Job noch schreibt.
  **Gelöst:** ein eigener Arbeitsordner `sicherungen/.schluesselwechsel/`
  (kein gültiger Kontoname, also von keinem Leser gesehen), den jedes
  Häppchen zuerst leert.
- **F-SR-70 `konto.json` kann sich mitten im Umhüllen ändern** — eine
  Freigabe, ein Widerruf, eine Sicherung schreiben sie neu. **Gelöst:**
  Vor dem Umbenennen wird die Datei noch einmal gelesen; ist sie nicht mehr
  die gelesene, bleibt sie („später"), und der Nachweis findet sie wieder.
  Dasselbe für Zeilen über das bedingte `UPDATE` (E-SR-70).
- **F-SR-71 Der Wiederanlauf beim Archiv verglich die neue Datei mit der
  alten Kennung** — beim eigenen Durchsehen vor der Probe gefunden: Das
  Soll-Manifest bekam die neue Kennung erst nach der Prüfung, also bestand
  eine fertige neue Datei den Nachweis nie und wurde neu gebaut. Nicht
  gefährlich, aber der Wiederanlauf fand nie statt. **Gelöst:** Kennung
  vorher gesetzt; ein eigener Fall der Probe, Gegenprobe rot (G2).
- **F-SR-72 Der Kopf der Anteilprobe sagte, nur Teil D fasse `config.php`
  an** — seit Schritt 15 schreibt `stelle()` die Datei über
  `konfig_stellen()` schon in Teil B. **Gelöst** mit Teil E.
- **F-SR-73 Die Überschrift „Serverschlüssel wechseln" klebte am
  Knopffuß** (Bild zu P-SR-05, 1280 px). **Gelöst:** das Formular in
  `.listen-form` (Linie und Abstand darüber, vorhandener Baustein). Die zwei
  alten Abschnitte „Nachtragen vom Blatt" und „Neuanfang" haben denselben
  Abstand; sie stehen nur in der Lage *abweichend*, kein Bild zeigt sie —
  **Nr. 356**.
- **F-SR-74 Der Anstoß des Komplett-Stands fehlte im ersten Bau.** Q-SR-03
  sagt „der Vorgang … stößt ‚Jetzt sichern' an"; gebaut war nur die
  Bedingung. Gefunden beim Nachziehen des Runbooks. **Gelöst** (E-SR-68):
  nach dem Nachweis ein Auftrag, nicht beim Beginn.
- **F-SR-75 Der Huckepack-Weg hätte in der Probe mitgearbeitet.** Jeder
  Seitenabruf der Probe stößt über `run_cleanup_if_due()` die Jobs an; der
  Fall „mit 1 s nur die Zeilen" wäre ein Wettlauf gewesen, und ein
  Komplett-Lauf hätte die Bedingung „frischer Stand" vorzeitig erfüllt.
  **Gelöst:** Die Probe hält die Jobs an (`jobs_pause()`), `sw_jetzt()`
  fragt die Pause nicht. Nebenbei gemessen: Die Mails gehen sofort hinaus,
  und die örtliche Anlage hat einen SMTP-Eintrag ohne Gegenstelle — jede
  schreibt eine Zeile „smtp:" in den Reiter System; die Probe räumt ihre.
- **F-SR-76 „Jetzt weiterarbeiten" erscheint örtlich nie.** Das Häppchen
  des Wechsels selbst (acht Sekunden) schafft die 13 Stücke der Anlage; der
  Knopf stünde im Bedienweg nie da, und seine Handlung bliebe ungemessen.
  **Gelöst:** Der Bedienweg setzt den Durchgang vor Schritt 4 zurück —
  dieselbe Lage wie nach einem abgebrochenen Lauf.
- **F-SR-77 Zwei weitere Stellen zeigten auf den Ort bis S10** (zu
  F-SR-03): die Zeile zu `admin_sicherungsziele.php` in der
  Verzeichnisstruktur von `Technik.md` („Serverschlüssel nachtragen") und
  das Runbook mit einer „fertigen Zeile zum Einfügen", die es seit S10
  nicht mehr gibt. **Gelöst** mit F-SR-03.

**Aus der Gegenlesung H-SR-06** (04.10.2026, Stand `9977172`, E-SR-71):
vier Fable-Leser nach Blickwinkel (Krypto-Kern und Wettläufe, Job und
Nachweis, Oberfläche und Betrieb, Doku und Probe), je Leser ein
Gegenprüfer; die Leser haben die Punkte, die das Prüfdokument „nicht
geprüft" nannte, nachgerechnet oder im Container mit künstlichen Daten
gemessen. **42 Meldungen: 29 bestätigt, 10 teilweise, 3 widerlegt; nach
der Gegenprüfung 2 hoch, 8 mittel, 13 niedrig, 16 Hinweise.** Hier zu
sechs Befunden zusammengefasst (E-SR-78); die Einzelkennungen (K-1 bis
K-9, J-1 bis J-13, O-1 bis O-9, D-1 bis D-11) mit Belegen, Rechnungen und
Gegenprüfung stehen im Prüfdokument. Widerlegt: J-4 (ein kurzes Schreiben
gibt bei einer Zeichenkette `false`, gemessen), J-6 (der Zustand wird
außerhalb eines Wechsels geleert), J-8 (bewusst so, E-SR-68). Die Leitfragen
aus E-SR-23 beantworteten alle vier mit „ja, mit Einschränkungen" — die
Einschränkungen sind die Befunde.

- **F-SR-78 Der Wechsel schützte sich nicht gegen sich selbst** (K-1 hoch,
  K-6, D-4). Zwei gleichzeitig abgeschickte Wechsel liefen ohne Sperre;
  ein Prozess, der `config.php` vor dem Schreiben des anderen gemerkt
  hatte, hielt den Eintrag für leer und konnte den frisch gewürfelten
  Schlüssel überschreiben — nachdem ein Häppchen schon Zeilen unter ihm
  umgehüllt hatte. Ein Häppchen neben dem Wechsel schrieb an seinem Ende
  den alten Zustand über den neuen. Ein Abbruch zwischen den beiden
  Schreibschritten hinterließ `server_key_alt` = `server_key`, und das hieß
  „Wechsel" — mit sich selbst. Die Probe maß die Riegel „läuft bereits"
  und „nur aus bereit" nie. **Gelöst mit H-SR-06:** jeder Griff an einen
  Schlüssel in `config.php` unter der Sperre der Jobzeile
  (`schluessel_unter_sperre()`, E-SR-76), `config.php` danach neu gelesen;
  `server_key` nur ersetzt, wenn dort noch der gesicherte Wert steht; „ein
  gültiger Eintrag steht da" zählt die Datei; alt = neu ist `bereit`. Die
  Probe startet zwei Wechsel aus zwei Prozessen und ruft einen dritten.
  **Beim Bau gemessen:** Die Gegenprobe ohne Sperre blieb zunächst grün —
  die Kinder lasen `config.php` erst nach dem Startschuss, also frisch;
  jetzt vorher. Dann fing die vorhandene Gegenprobe in
  `config_eintrag_schreiben()` die Verschränkung ab, und in einer dritten
  scheiterte der zweite am Umbenennen, weil beide Schreiber dieselbe
  Nebendatei `config.neu.php` benutzen — auch das schließt die Sperre aus.
  Erst mit 150 ms Versatz und einer Pause zwischen den Schreibschritten
  stellte die Gegenprobe den Fall aus K-1 her (beide durch, der zweite
  Schlüssel über dem ersten); mit nur einem der beiden Riegel blieb sie
  grün.
- **F-SR-79 Prozesse mit dem bisherigen Schlüssel im Speicher** (J-2
  mittel, K-2/J-3/O-1 mittel). Jeder Prozess hält `config.php` und den
  Schlüssel in einer `static`. Ein CLI-Lauf (bis 300 s) oder eine Anfrage,
  die vor dem Wechsel begann, versiegelte bis zu ihrem Ende mit dem
  bisherigen; der Nachweis war örtlich nach acht Sekunden durch, danach sah
  niemand die Nachzügler, und das Entfernen war erlaubt. Ein CLI-Lauf sah
  im Häppchen noch die Lage `bereit` und leerte den Zustand. Ein
  Komplett-Stand, dessen Versiegelung über den Wechsel lief, trug Blöcke
  unter beiden Schlüsseln (Kopf: der bisherige), ließ sich nicht einspielen
  und verdrängte einen lesbaren; fiel der Wechsel zwischen Dump und erstes
  Siegel-Häppchen, log der Kopf (Leser und Gegenprüfer haben beides
  nachgebaut). **Gelöst mit H-SR-06:** Frist von zehn Minuten vor dem
  Nachweis (E-SR-73), im Riegel nachgemessen; jedes Häppchen liest
  `config.php` neu; `komp_kopf_angleichen()` beginnt die Versiegelung bei
  einer anderen Kennung von vorn (E-SR-72).
- **F-SR-80 Ein Wechsel ohne Beginn** (K-3 mittel, O-2 mittel, D-6 mittel,
  K-5, J-7, O-4, K-7, O-3). Stand der Wechsel von Hand in `config.php` —
  der Weg, den das Runbook für das Einspielen eines Stands von vorher
  beschrieb —, legte das Häppchen still einen Zustand an: kein Protokoll
  „gewechselt", keine Mail, keine fällige Rückfrage; Bedingung 3 blieb bis
  zum nächsten Quartal zu, und der einzige Ausgang war das Austragen von
  Hand. Der Runbook-Satz „der Job findet nichts umzuhüllen" war falsch:
  Der Dump bringt Zeilen unter dem bisherigen mit, und wer vor dem nächsten
  Häppchen austrug, machte Zweitfaktor-Geheimnisse und Zugänge stumm. Warf
  `sw_beginnen()`, stand der neue Schlüssel ohne Zustand da. Statuszeile und
  Karte sagten ohne Zustand „alles umgehüllt" und zugleich „es läuft kein
  Wechsel". Der Zustand wurde ohne Abgleich mit dem Schlüsselpaar geglaubt,
  auch einer aus einem eingespielten Dump. **Gelöst mit H-SR-06:** der
  Beginn wie über die Karte, mit Vermerk und eigenem Betreff (E-SR-74);
  ein Fehler im Beginn nimmt den Wechsel nicht zurück; fremdes
  Schlüsselpaar = kein Zustand; `sw_nach_einspielen()` aus
  `wiederherstellen.php`; Runbook berichtigt; Karte und Statuszeile mit der
  Lage „nicht begonnen".
- **F-SR-81 Der Job konnte hängen bleiben oder falsch zählen** (K-4
  mittel, J-1 mittel, K-8, J-9, J-10, J-11, J-12). Ein Stück, das warf,
  brach jedes Häppchen an derselben Stelle ab, ohne Namen. Ein Paket mit
  lesbarem Manifest und unlesbarem Teil hieß im Umhüllen `verloren`, im
  Nachweis `alt` — der Durchgang begann in jedem Häppchen von vorn. Ein
  Archiv mit dritter Kennung im Namen hieß `verloren`, obwohl der bisherige
  es öffnete. Die Stückliste wurde je Stück neu gebildet (gemessen 4,35 s je
  Durchgang bei 300 Konten); `zip_lesen()` wurde nicht ausgewertet; leere
  Objekte im Archiv-Manifest wurden `[]`. **Gelöst mit H-SR-06:** ein
  Stück, das wirft, wird genannt und hält Bedingung 1 zu (E-SR-77); der
  Nachweis versucht bei einem Manifest unter dem bisherigen jeden Teil;
  Archive werden nach dem Manifest umgehüllt und vor dem Ablegen
  nachgewiesen. **Beim Bau gefunden, alle drei von der eigenen Probe:**
  (1) der Nachweis trug ein gescheitertes Stück aus der Fehlerliste aus,
  weil es dort `alt` sagt — jede Runde meldete es neu (zwölf Zeilen im
  Reiter System); (2) `sw_paket()` ließ bei `verloren` den halben Bau im
  Arbeitsordner liegen, bis zum nächsten Häppchen; (3) ein neu versiegelter
  Wert, der den Nachweis nicht bestand, hieß `verloren` — er öffnet aber
  mit dem bisherigen; `sw_umsiegeln()` wirft jetzt.
- **F-SR-82 Auskünfte, die nicht stimmten** (O-5, O-6, O-7, O-8, O-9, D-5,
  D-11, J-5, J-13, K-9). Die Abschluss-Mail überholte eine offene
  Beginn-Mail (gemeinsamer Schlüssel) — **gelöst:**
  `serverschluessel_abgeschlossen`. Ein Komplett-Stand unter einem dritten
  Schlüssel ließ sich „herunterladen" (leere Datei, Protokoll „heruntergeladen")
  — **gelöst:** Abweisung vor Protokoll und Kopfzeilen, kein Angebot in der
  Liste. Der Reiter Archiv verwies während eines Wechsels auf den Schlüssel
  von damals — **gelöst:** eigene Meldung und Plakette „bisheriger
  Schlüssel". Karte und Runbook sagten „der Job arbeitet weiter", auch wo
  ohne Auslöser keine Datei je vorankommt — **gelöst:** gesagt; der
  Nachweis nimmt die kleine Reserve und läuft huckepack. Das
  Wiederanlaufpaket nach einem Wechsel stand nirgends — **gelöst:**
  Runbook Schritt 7, Handbuch, Abschluss-Mail. „Jetzt weiterarbeiten"
  leitete nach einem Fehlschlag nicht um — **gelöst** (wie die Rundmail).
  `anteil_neuanfang()` machte das Blatt nicht fällig, `edbak_begleit_lesen()`
  las mit leerer Kennung die Wurzel, „drei Kacheln" setzte einen Anteil
  voraus, „94 MB" war Fassung 1 (Fassung 3 ≈ 15 MB, im Container in 0,28 s
  umgehüllt) — **gelöst**. Der Klassenname im Fehlertext jedes Jobs:
  **Nr. 357**.
- **F-SR-83 Die Probe** (D-1 hoch, D-2, D-3, D-7, D-8, D-9, D-10). Ihr
  Rückweg legte `config.php` auch dann auf A zurück und löschte die
  Stand-Datei, wenn der Job B → A nicht fertig war — nach einem Fehler,
  einer stehengebliebenen Jobsperre oder beim zweiten `--zurueck` —, und
  B stand danach nirgends mehr; dazu legte die Abbruchsicherung von
  `konfig_stellen()` aus Teil 3 nach einem Fatal den Text ohne B zurück.
  Die Stand-Datei lag beim ersten Schreiben bis zum `chmod` mit 0644 da. Sie
  maß Endzustände, nicht den Nachweis vor dem Ersetzen; ihr Zähler sah
  `\sk_versiegeln(` nicht; ohne SMTP meldete sie die Mail-Zeilen grün.
  **Gelöst mit H-SR-06:** Rückweg nur nach „fertig", sonst A und B stehen
  lassen, Datei behalten, mit Zahl melden, ein zweites `--zurueck` macht
  weiter; Teil 3 ohne `konfig_stellen()`; `umask(0077)`; G6 mit einem
  Versiegler, der für einen Zweck lügt; `T_NAME_FULLY_QUALIFIED`; ohne SMTP
  „nicht gemessen" (rot); Zahlen im Prüfdokument berichtigt. **Beim Bau
  gefunden:** Die Zeile „Paket mit kaputtem Teil … trotzdem fertig" maß das
  „fertig" nicht (ihre Gegenprobe wurde nur an Folgestellen rot) — jetzt
  schon.

**Aus der Nachprüfung von H-SR-06** (04.10.2026, Workflow `wf_663b2eba-579`,
lesend, vier Leser und vier Gegenprüfer, mit Rechnungen; E-SR-71): Jede der
42 Meldungen ist behoben, D-2 und D-7 nur zur Hälfte. Neu kamen 26 Meldungen
dazu (23 bestätigt, 3 teilweise) — nach der Gegenprüfung eine hoch, zwei
mittel, zwölf niedrig, elf Hinweise; eine Teilbehauptung widerlegt (der
zweite Auslöser in ND/NA-1). Sie stehen als
vier Befunde im Konzept (E-SR-79), die Einzelkennungen im Prüfdokument.

- **F-SR-84 Der Rückweg der Probe nach einem Wettlauf ohne Sieger**
  (ND/NA-1 hoch, ND/NA-2, -3, -6). Kam keines der zwei Kinder durch — eine
  stehengebliebene Jobsperre wies beide ab —, schrieb die Probe den
  unveränderten Schlüssel A als B in die Stand-Datei. Der Rückweg stellte A
  und A ein (Lage `bereit`), der Job tat nichts, jedes `--zurueck` endete
  „nicht fertig". Wechselte der Hauptlauf danach selbst (sein dritter
  Wechsel ging dann durch), stand sein B' nirgends sonst — dieselbe Klasse
  wie D-1. Dazu: Die Kinder starteten `php` aus dem Suchpfad; nach einem
  nicht fertigen Rückweg nannte der Hauptlauf die Lage statt des
  Rückwegs; `--zurueck` hielt die Jobs nicht an und löste die Sperre
  bedingungslos; der Titel „sieben Stücke" maß sechs. **Gelöst:** B ist nur
  ein Wert, der nicht A ist (an beiden Stellen); ohne Sieger hält die Probe
  an; `PHP_BINARY`; die Stand-Datei vor der Lage; eine stehende Sperre ist
  eine Vorbedingung; `--zurueck` pausiert die Jobs und löst nur eine alte
  Sperre; „sechs Stücke". **Widerlegt:** „`php` nicht im Pfad" führt nicht
  zum Hänger — `proc_open()` gibt dann `false`, und der Rückweg findet A/A
  in der Datei und legt zurück (gemessen von der Gegenprüfung).
- **F-SR-85 Der Job kreiste oder sagte Falsches** (NB-1 mittel, NB-2
  mittel, NB-3, -4, -5, -6, NC/NA-1, -2, NA/NA-6). Ein Archivteil, der sich
  nicht in den Arbeitsordner schreiben ließ, hieß `verloren`, der Nachweis
  sagte `alt`, der Durchgang kreiste. Ein Nachweis-Durchgang ohne Stück
  setzte `nachweis_begonnen` nie: Bedingung 1 blieb zu, jedes Häppchen
  schrieb „umgehüllt" ins Protokoll (gemessen: 1, 2, 3, 4). Eine Ausnahme
  nach dem Selbstanlauf ließ den Rahmen den leeren Zustand zurückschreiben —
  zweiter Beginn, zweite Mail (gemessen). Über der Decke von 20 griff die
  Kurzschaltung „nur Fehler" nicht, und der Reiter System bekam vier Zeilen
  je Stück und Häppchen (gemessen: 24, dann 28). Ein Beginn über die Karte,
  dessen Inventar oder Zustand scheiterte, wurde als Handeintrag nachgeholt
  — Protokoll und Mail behaupteten etwas Falsches. Die Lage `abweichend`
  wischte den Zustand. `verloren` wurde nie bereinigt, auch nicht beim
  Einspielen. **Gelöst:** `sw_archiv()` wirft beim Schreibfehler;
  `nachweis_begonnen` beim Eintritt; nach dem Selbstanlauf zurück;
  `fehler_mehr` und eine Systemzeile je Häppchen; das Inventar hält den
  Beginn nicht auf, und der Text des nachgeholten Beginns nennt beide
  Möglichkeiten; geleert nur bei `bereit`; `verloren` je Durchgang
  (`sw_verloren_durchgang_ende()`), beim Einspielen leer.
- **F-SR-86 Griffe und Lage** (NA/NA-1, -2, -3, -4, -5, NB-7). Das Blatt
  druckte die Waise als „bisherigen"; die Rücknahme eines halben Griffs
  räumte einen `server_key_alt` weg, auch wenn jemand an der Sperre vorbei
  `server_key` geändert hatte (gemessen im Nachbau: danach Lage
  `abweichend`, das Alte ohne Eintrag); eine stehengebliebene Sperre sperrt
  bis zu einer Stunde jeden Griff, und die Meldung sagte „gleich noch
  einmal"; die Waise beim Anteil hieß `rotation` und sperrte den Wechsel des
  Serverschlüssels; der Neuanfang des Anteils war nicht gemessen.
  **Gelöst:** Blatt-Kachel nur bei verschiedenem Wert (beide Schlüssel);
  `config_halbes_zuruecknehmen()`; die Meldung nennt „seit" und
  „spätestens … frei" (die Stunde selbst bleibt, E-SR-79); die Waise beim
  Anteil wie beim Serverschlüssel, auch in `anteil_wechseln()`; Anteilprobe
  (8a), (E6) bis (E9).
- **F-SR-87 Auskünfte und Beschreibung** (NC/NA-3, -4, -5, -6, -7,
  ND/NA-4, -5). Der Knopf „Jetzt weiterarbeiten" hing am rohen Zustand, die
  Zeile darüber am gefilterten; `komp_kopf_angleichen()` las die Kennung aus
  `konfig()`, den Schlüssel aus einer anderen `static`; der Grund auf der
  Karte ist der rohe Ausnahmetext; zwei Kleinzeilen hatten zwei Sätze und
  die Liste der Komplett-Stände einen Absatz je Stand (`Design.md`); die
  Mail eines Handeintrags ging in der Anfrage einer beliebigen Person
  hinaus; Technik und Dateikopf sagten nicht, dass der Nachweis eine Datei
  unter dem bisherigen ganz liest; `Backlog-Erledigt.md` nannte für SR-03
  „E-SR-60 bis -67". **Gelöst:** alle, bis auf den Grund (zu Nr. 357
  gelegt, E-SR-79).

**Aus der Nachmessung zu H-SR-06** (04.10.2026, E-SR-80): Die vier Punkte,
die das Prüfdokument von 21.12.1 „gelesen, nicht gemessen" nannte, sind
gemessen (Prüfdokument 2). Zwei Auskünfte stimmten dabei nicht:

- **F-SR-88 Zwei Zahlen, die nicht stimmten** (Nachmessung M2, M3a).
  (1) `fehler_mehr` wurde bei jedem Neustart des Umhüllens geleert — und dort
  endet jedes Häppchen, sobald der Rest nur aus werfenden Stücken besteht.
  Gemessen mit 23 werfenden Stücken: nach der Frist `fehler_mehr` 0 nach
  jedem Häppchen; die Karte hätte 20 ohne „und 3 weitere" gezeigt, und der
  Satz vor dem Entfernen zählte die drei nie. (2) Warf das Inventar beim
  Beginn, sagten Karte und Plakette bis zum ersten Häppchen „noch 0 von 0"
  und die Statuszeile „noch 0 Stück(e)", Plakette „noch 0" (gemessen).
  **Gelöst (Web 21.12.2):** `fehler_mehr_lauf` zählt den laufenden
  Durchgang, `fehler_mehr` hält den letzten ganzen, und Karte, Riegeltext und
  Meldung nehmen die größere von beiden (`sw_fehler_zahl()`); `sw_gezaehlt()`
  — „die Zahl der Stücke zählt das erste Häppchen", Plakette „wird gezählt",
  auch in der Meldung von „Jetzt weiterarbeiten"; `sw_rueckstand()` gibt
  ungezählt `null`. **Aus der Gegenprüfung der Nachmessung** (lesender
  Workflow, E-SR-71): Die erste Fassung nahm nur den letzten ganzen
  Durchgang — mitten im ersten Durchgang stand wieder „20" (gemessen:
  Plakette 20 statt 22); die Meldung von „Jetzt weiterarbeiten" konnte noch
  „noch 0 von 0" sagen; die Probe las „nicht begonnen" an der falschen
  Zeile. Alle drei behoben, die ersten zwei gemessen; beide Auskünfte hält die
  Probe seither fest (gesetzter Zustand, GH-28 und GH-29).

## 3. Entscheidungen und Fragen

### 3.1 Entscheidungen

| Nr. | Entscheidung | Von | Grund |
|---|---|---|---|
| E-SR-01 | **Kürzel `SR`; das Konzept entsteht mit Fable, die Umsetzung mit Opus, ohne Fable-Schritt** (Q-SR-09: dazu eine Fable-Gegenlesung von SR-03, E-SR-23). | Konzept | R14, K2, K8. Die Fahrplanzeile „nach K1, Fable" meint das Konzept. |
| E-SR-02 | **Das Konzept sitzt auf dem 17er-Zweig** (`claude/schritt-17-konzept-mockups-q0yjcm`, `26b4761`), nicht auf `main`; sein PR wird erst nach dem Merge von 17 gegen `main` gestellt. Die Umsetzung läuft danach auf einem eigenen Zweig von `main`. | Betreiberin, 27.09.2026 („aktuellste Code-Version") | Der Befund soll den Code lesen, auf dem 18 aufbaut — R4-09 und R4-10 haben Dateien der Sperrliste geändert. Preis: Wird der 17er-Zweig umgeschrieben, muss dieser Zweig nachziehen (`Pruefablauf.md` 5.3); ein PR vor dem 17er-Merge zeigte 17 als eigenen Unterschied. |
| E-SR-03 | **Backlog-Spanne 350 bis 359**, eingetragen mit SR-00 vor jeder Vergabe. | Konzept (E-SD-21) | 340 bis 349 gehören 17 (vergeben 340, 341); `nummern.py` misst die Kollision seit R4-03. |
| E-SR-04 | **Sitzungsbindung: ein zweites Cookie mit 32 Zufallsbyte, dessen SHA-256 in der Sitzung liegt** — gesetzt beim halben Stand (`totp_halb`) **und** in `anmeldung_vollenden()` (neu gewürfelt, wie die Kennung); geprüft in `auth_guard.php` unmittelbar nach `user_id` und in `login.php` vor Code- und Schlüsselschritt. Fehlt das Cookie oder passt der Hash nicht: Sitzung beenden, Grund `bindung` (401 JSON für `api/`). **Alte Sitzungen werden nicht übernommen** — nach dem Ausrollen meldet sich jede Angemeldete einmal neu an, mit Ansage (Changelog, Runbook 7). | Konzept | E-SA-09 wörtlich. Der halbe Stand trägt die Herausforderung des Rückwegs und fünf Minuten Passwortnachweis — eine gelesene Datei darf auch ihn nicht tragen. Eine Übernahme („Sitzung ohne Hash gilt weiter") wäre genau das Loch, das der Umbau schließt: Die gelesene Datei hätte keinen Hash. Dasselbe hat Schritt 16 getan (E-SA-08). |
| E-SR-05 | **Alle Cookie-Parameter stehen in `sitzung_lib.php`** — die Sitzungsarten in `SITZUNG_ARTEN`, Bindungs- und Gerätecookie in einer zweiten Tabelle daneben, gesetzt und gelöscht über je eine Funktion. `sitzungshaertung.php` zählt künftig auch `setcookie()`: erlaubt nur in `sitzung_lib.php` und `session_lib.php`. | Konzept (R83, E-ZE-12) | Neun Sitzungsstarts in vier Fassungen sind entstanden, weil jeder abschrieb, was in der Nähe stand. Ein drittes Cookie mit eigenen Parametern in `login.php` wäre der Anfang derselben Geschichte. |
| E-SR-06 | **Nr. 251: `lesend` bekommt `secure` fest; `einrichtung` bleibt HTTPS-abhängig**, mit dem Grund aus F-SR-06 als Kommentar an der Tabelle. | Konzept | `lesend` startet nur mit vorhandenem Cookie (F-ZE-2), und das Cookie der Art `app` ist `secure` — über HTTP käme es nie an. Ein festes `secure` ändert dort nichts am Verhalten und nimmt den Unterschied aus der Tabelle. `einrichtung` läuft, bevor HTTPS steht. |
| E-SR-07 | **„Gerät merken": Token 32 Byte im Cookie, SHA-256 in der Tabelle `vertraute_geraete`** (`user_id` mit Kaskade, `token_hash`, `angelegt_am`, `zuletzt_am`) — die Dauer kommt aus der Einstellung je Rollengruppe (E-SR-17), nicht aus einer Konstante; **kein User-Agent, kein Gerätename**; gemerkt wird nur nach einem **App-Code** (nicht nach Wiederherstellungscode, nicht nach dem Rückweg — Q-SR-02); die Karte „Zweitfaktor" zeigt die Zahl und „Alle vergessen". **Vergessen** wird bei Passwortwechsel und -reset (dort, wo `session_epoch` steigt), bei `totp_abschalten()` (jeder Weg), mit dem Konto (Kaskade) und im Demo-Reset; ein Aufräumschritt löscht Abgelaufene. **Protokoll** bei Merken und Vergessen, nicht je Nutzung. | Konzept | SP-11 und Nr. 141 sagen „30 Tage" und „eigener Hash"; R36 verbietet Telemetrie — ein User-Agent wäre eine, und ein Gerätename ist eine Eingabe, die niemand pflegt. Wer sein Passwort wechselt, weil er Missbrauch vermutet, will den anderen draußen haben — das Cookie des Fremden muss dann mit fallen, sonst überdauert es den Wechsel wie einst die Sitzung (M1-09). Ein Protokolleintrag je Anmeldung wäre Rauschen. |
| E-SR-08 | **Der Wechsel des Serverschlüssels folgt dem Muster der Anteil-Rotation:** `server_key_alt` in `config.php` (`CONFIG_SCHREIBBAR`), Lage `rotation` in `serverschluessel_zustand()`, die Marke wandert mit dem Beginn auf die neue Kennung; `sk_versiegeln()` versiegelt nur mit dem neuen, `sk_oeffnen()` öffnet mit dem neuen und dann mit dem bisherigen. **Kein neues Format** — `edsk1:` bleibt; der Fortschritt wird vom Job gezählt, nicht am Präfix. | Konzept (F-SR-04) | Der Server kann öffnen; ein `edsk2:` mit Kennung schriebe `Backup-Format.md` 5 und 7.5 um und hielfe nur der Zählung. Das Anteil-Muster ist gebaut, geprüft (Anteilprobe) und der Betreiberin bekannt (Handbuch 12.5, Tabelle der Lagen). |
| E-SR-09 | **Der Vorgang ist ein Job mit Häppchen und Nachweis** (`schluesselwechsel` im Katalog, eigene `schluesselwechsel_lib.php`): Inventar aus den Aufrufern (F-SR-01) — Sicherungsziele, Zweitfaktor-Geheimnisse, Adminpakete samt `konto.json`, Protokoll-Archive —, je Stück: mit dem bisherigen öffnen, mit dem neuen versiegeln, mit dem neuen wieder öffnen und vergleichen, **erst dann** ersetzen (Dateien über eine Nebendatei und `rename`, Zeilen in einer Transaktion je Zeile). Zustand in `jobs.zustand`; die Karte zeigt „noch n von m", ein Knopf „Jetzt weiterarbeiten" fährt ein Häppchen ohne Job-Auslöser. **„Alten Schlüssel entfernen" verlangt drei Dinge:** Inventar vollständig umgehüllt, ein **Komplett-Stand unter dem neuen Schlüssel** jünger als der Beginn, und die Rückfrage (E-SR-11) beantwortet. Komplett-Stände werden **nicht** umgehüllt (Q-SR-03). | Konzept | Nr. 247 nennt den Nachweis der Öffenbarkeit vor dem Verwerfen als „den Schritt, dessen Fehlen den Vorgang gefährlich macht". Ein Stand ist eine Momentaufnahme — ein frischer unter dem neuen Schlüssel ersetzt das Umhüllen alter, und die Aufbewahrung räumt die alten wie bisher. Der Job-Rahmen (Häppchen, Zustand, Sperre, `KOMP_LAUF_MAX_S`-Muster) ist da; ein Vorgang, der in einer Anfrage tausend Dateien umschreibt, liefe in die Zeitgrenze des Hosters. |
| E-SR-10 | **Was auf einem Ziel liegt, bleibt unter dem alten Schlüssel.** Der Vorgang sagt es beim Start und beim Abschluss; das Schlüsselblatt trägt während der Rotation „Serverschlüssel (bisheriger)" mit dem Satz, was er noch öffnet; **die Regel „alte Blätter vernichten" gilt für den Serverschlüssel erst, wenn das Ziel nichts mehr unter ihm trägt** — bis dahin wird das alte Blatt als „bisheriger Serverschlüssel, Kennung …, gilt für Stände bis <Datum>" in der Betriebsakte behalten. Der Runbook-Abschnitt „Wiederanlauf" sagt, welcher Schlüssel welchen Stand öffnet. | Konzept (F-SR-05) | Ohne diesen Satz vernichtete die Betreiberin nach der Anleitung des Blatts den einzigen Schlüssel zu den Ständen auf dem Ziel — und merkte es beim Wiederanlauf. |
| E-SR-11 | **Nr. 233: Jede Rotation — Anteil und Serverschlüssel — setzt `schluesselblatt_bestaetigt_am` zurück.** Die Rückfrage kommt damit bei der nächsten Anmeldung jeder BetreiberIn, mit einem Satz voran: „Ein Wert hat gewechselt — drucke das Blatt neu; während der Rotation gehört auch der bisherige (Kennung …) darauf." Gefragt werden weiter nur die **aktuellen** Werte (vier Felder); der bisherige wird genannt, nicht abgefragt. | Konzept | Nr. 233 sagt selbst, wo es zu schließen ist: „Der Rotationsvorgang selbst sollte sagen, dass das Blatt neu gedruckt gehört — er ist die Stelle, an der es auffällt." Sechs Felder je nach Lage verwirren mehr, als sie prüfen (E-P5b-10, unverändert); dass der bisherige Wert auf dem Blatt steht, prüft der Ausdruck selbst — er druckt ihn. |
| E-SR-12 | **Nr. 210: Der Transaktionsrumpf von `ingest.php` läuft in einer Schleife mit höchstens drei Anläufen** bei 1213 und 1205 (`gedraengel_erkannt()`), mit kurzem Zufallsabstand (50–200 ms) dazwischen; der Rumpf bleibt an Ort und Stelle (Weg B: `for` um `beginTransaction()`/`try`, keine Zerlegung in Funktionen); was der Rumpf für die Antwort befüllt, wird am Anfang jedes Anlaufs zurückgesetzt (Liste im Paket). **`dt_zeitraum_fortschreiben()` wandert hinter den Commit** — eine eigene kurze Anweisung, idempotent (min/max), mit demselben Wiederholungsrahmen. Nach dem dritten Anlauf 503 wie heute. | Konzept | Der Rumpf gehört zum Gerätevertrag und ist 670 Zeilen; ihn in eine Closure mit zwei Dutzend `use (&…)` zu heben, ist die Art Umbau, bei der eine Variable still stehen bleibt. Die `days`-Zeile ist der Kreuzungspunkt aller Uploads eines Tags; wer sie erst nach dem Commit anfasst, hält ihre Sperre nicht mehr, während er Punkte einfügt — der Deadlock verliert seinen zweiten Arm, und die Schleife wird zum Netz statt zur Regel. |
| E-SR-13 | **Nr. 249 (Q-SR-05 = B, ergänzt durch E-SR-24): ein Notzugang `zweitfaktor_notweg.php`**, unangemeldet, nur wenn **genau eine** BetreiberIn existiert (`betreiberinnen_zahl() === 1`) und ihr Zweitfaktor eingeschaltet ist; Nachweis über eine Datei mit Zufallsnamen im Anwendungsverzeichnis (Muster `install.php`, M1-11) — die Hilfe dafür wandert als `nachweis_lib.php` heraus, weil `install.php` und `wiederherstellen.php` sie schon je einmal tragen (R83, dritter Verbraucher); Topf `notweg` (fünf je Stunde, Leiter); Erfolg: `totp_abschalten($id, 'notweg')`, Protokoll `totp_zurueckgesetzt` mit `weg = notweg`, Mail `totp_zurueckgesetzt`, danach Anmeldung mit Passwort ins Einrichtungstor. Die Seite gibt unangemeldet **keine Auskunft** (K-11-Linie): weder, ob es genau eine BetreiberIn gibt, noch welche. **Der SQL-Weg bleibt im Runbook** — als letzter. | Konzept | Wer die Nachweisdatei anlegen kann, hat den Webspace — dasselbe Vertrauen wie der, der heute SQL absetzt; der Unterschied ist, dass der Weg im Protokoll steht, eine Mail auslöst und kein Datenbankwerkzeug braucht. Bei zwei BetreiberInnen setzt die andere zurück (E-P5c-42); eine Tür, die dann offen bliebe, wäre ein zweiter Weg ohne Not. |
| E-SR-14 | **Reihenfolge SR-01 → SR-02 → SR-07 → SR-09 → SR-05 → SR-03 → SR-04 → SR-08 → SR-06, seriell, ohne Fächerung.** (SR-09 eingefügt mit der Nachfassung.) | Konzept, ergänzt 27.09.2026 (zweimal) | Alle Pakete schreiben `server/` (Rahmenplan 4: nacheinander). Erst die Sitzung, weil SR-02 ihre Cookie-Tabelle braucht; der frische Code (SR-07) gleich danach, weil SR-03 und SR-04 seine Griffe schon mit ihm bauen; die Passkeys (SR-09) direkt dahinter, weil sie den Haken aus SR-02 und die Frische aus SR-07 mitbenutzen und dieselben Anmeldedateien anfassen — danach sind `login.php`, `einstellungen.php` und `zweitfaktor_teile.php` für den Rest der Runde zu; SR-05 vor SR-03, weil es klein ist und `ingest.php` niemand sonst anfasst; SR-03 vor SR-04, weil der Notzugang die Rotation nicht kennen muss, die Rotation aber den Zweitfaktor (Geheimnisse umhüllen); der Proof-of-Work (SR-08) zuletzt, weil er keine andere Datei der Runde teilt. |
| E-SR-15 | **Keine Mockups** (Q-SR-08: bestätigt): Der Haken im Code-Schritt ist ein `.schalter` in der Anmeldekarte, die Zeile in der Karte „Zweitfaktor" eine `zeile()` mit Knopf, der Fortschritt in der Karte „Schlüssel des Servers" die Lage `rotation` mit Zählung wie beim Anteil, der Notzugang das Gerüst von `wiederherstellen.php`. Die Bilder des Bilderlaufs gehen ins Prüfdokument. | Konzept (`CLAUDE.md` 5, `Design.md` 9) | Ein neuer Baustein entsteht nur mit Mockup; hier entsteht keiner. |
| E-SR-16 | **Die Sätze „Wer sie liest, ist angemeldet" werden ersetzt**, nicht ergänzt: Kopf von `sitzung_lib.php`, `Technik.md` (Sitzungsablage); `Backlog-Erledigt.md` Nr. 241 bleibt wörtlich. Dazu die Doku-Stellen aus F-SR-01 und F-SR-03. | Konzept (F-SR-09) | `CLAUDE.md` 2, Punkt 3. |
| E-SR-17 | Q-SR-01, Q-SR-10: **„Gerät merken" für alle Rollen, mit einstellbarer Dauer je Rollengruppe** — eine Zahl für NutzerInnen (Vorgabe 30 Tage), eine für Support, Admin und BetreiberIn (Vorgabe 7 Tage); Auswahl aus, 1, 7, 14, 30, 90 Tage; **0 heißt: kein Haken**. Ort: Betrieb → Servereinstellungen, neue Karte „Anmeldung" (zwei Wahllisten, eigenes Speichern; in derselben Karte später die Frist des frischen Codes, E-SR-20). **Die Dauer wird beim Prüfen gerechnet, nicht beim Merken:** Die Tabelle trägt `angelegt_am`, gültig ist eine Zeile, solange `angelegt_am` plus der heutigen Dauer der Rollengruppe in der Zukunft liegt. Der Text am Haken nennt die Dauer („Dieses Gerät 7 Tage merken"). Keine persönliche Wahl im Profil. | Betreiberin, 27.09.2026 | „Der Zeitraum sollte einstellbar sein, für User und für Admins die Standard-Einstellung." Gerechnet beim Prüfen, damit eine verkürzte Einstellung sofort für alle gilt und 0 alle Geräte auf einmal abmeldet — sonst hieße „aus" erst in 30 Tagen aus. Zwei Zahlen statt einer, weil die Verwaltung kürzer laufen soll als der Dienst; keine dritte Ebene im Profil, weil eine Einstellung, die kaum jemand ändert, nur Pflege kostet (Q-SR-10, wie empfohlen). |
| E-SR-18 | Q-SR-02: **Gemerkt wird nur nach einem App-Code.** Nach Wiederherstellungscode oder Rückweg zeigt der Code-Schritt keinen Haken. *Ergänzt durch E-SR-32: Ein Passkey zählt wie ein App-Code.* | Betreiberin, 27.09.2026 | wie empfohlen (Begründung in E-SR-07). |
| E-SR-19 | Q-SR-08: **Keine Mockups**; die Bilder des Bilderlaufs kommen ins Prüfdokument (P-SR-05). | Betreiberin, 27.09.2026 | wie empfohlen (E-SR-15). |
| E-SR-20 | Q-SR-11: **Ein frischer Code vor kritischen Handlungen** — Paket SR-07. „Frisch" heißt: In dieser Sitzung wurde in den letzten **15 Minuten** ein Code aus der App oder ein Wiederherstellungscode eingegeben (`$_SESSION['zf_frisch_bis']`, gesetzt in `login.php` nach dem Code-Schritt und auf der Bestätigungsseite); eine Anmeldung über ein gemerktes Gerät und der Rückweg setzen nichts. **Die Liste** (Konstante, eine Stelle): alle `schluessel_*`-Handlungen in `betrieb_server.php`, die Anzeige von `betrieb_schluesselblatt.php`, in `admin_user.php` Rollenwechsel, Zweitfaktor zurücksetzen und Konto löschen, in `einstellungen.php` den eigenen Zweitfaktor ausschalten. **Mechanik:** `zweitfaktor_frisch_verlangen($handlung)` in `auth_guard.php`, gerufen vor `csrf_check()` wie ein Rollentor (E-P5c-85); nicht frisch → 303 auf `zweitfaktor.php?bestaetigen=1&zurueck=<eigener Pfad>` (nur Pfade der eigenen Anwendung), dort Code eingeben (Topf `totp`), zurück auf die Seite mit dem Hinweis „Code bestätigt — bitte die Handlung noch einmal auslösen"; für `api/` 403 JSON `zweitfaktor_frisch`. Ein Konto **ohne** Zweitfaktor hat nichts zu bestätigen; die Prüfung ist dort ein Durchlass — die Liste trifft ohnehin nur Verwaltungshandlungen und das eigene Ausschalten. | Betreiberin, 27.09.2026 („Ja, kurze Liste") | Ein gemerkter, unbeaufsichtigter Rechner mit bekanntem Passwort reicht sonst für einen Schlüsselwechsel. 15 Minuten sind die Frist des Topfes `totp`; die abgeschickte Handlung wird nicht nachgespielt, weil ein gespeicherter POST samt Formular-Token die Art Zwischenspeicher ist, die beim nächsten Umbau falsch abgespielt wird — ein zweiter Klick ist billiger. Vor dem Token wie jedes Tor, damit die Rollenprobe den Umweg von der Token-Ablehnung unterscheiden kann. |
| E-SR-21 | Q-SR-03: **Komplett-Stände werden nicht umgehüllt; ein frischer Stand unter dem neuen Schlüssel ist Abschlussbedingung.** | Betreiberin, 27.09.2026 | wie empfohlen (E-SR-09, E-SR-10). |
| E-SR-22 | Q-SR-04: **Mail an alle BetreiberInnen** bei Beginn und Abschluss des Schlüsselwechsels, nur Kennungen, nie Werte. Vorlage `serverschluessel_gewechselt`. | Betreiberin, 27.09.2026 | wie empfohlen. |
| E-SR-23 | Q-SR-09: **Opus baut SR-03, Fable liest es vor dem PR gegen** — Haltepunkt H-SR-06: Die Umsetzungsinstanz hält nach SR-03 an, sagt es, und eine Fable-Instanz liest das Paket gegen das Konzept (Inventar vollständig? Nachweis vor dem Ersetzen? die drei Bedingungen als Riegel in der Funktion?); Befunde als F-SR-NN ins Konzept, Behebung durch Opus, dann weiter mit SR-04. | Betreiberin, 27.09.2026 | Ein zweites Augenpaar am gefährlichsten Paket, ohne den ganzen Bau mit Fable zu bezahlen. Kein Fable-**Schritt** im Sinn von K8 — die Umsetzung wechselt das Modell nicht, sie pausiert für eine Lesung. |
| E-SR-24 | Q-SR-05: **(B) Notzugang — und zusätzlich ein Zufallswert in der Datenbank.** `app_state.notzugang_geheim` (32 Zufallsbyte hex) entsteht mit der Migration von SR-02 (oder beim ersten Aufruf, wenn er fehlt) und wird nach jedem gelungenen Notzugang neu gewürfelt. Die Seite verlangt **drei** Dinge: die Nachweisdatei im Anwendungsverzeichnis (Webspace), den Wert aus der Datenbank (das Runbook nennt die eine Abfrage für das Datenbankwerkzeug des Hosters) und das Passwort des Kontos (Wissen). Verglichen wird mit `hash_equals()`, jede Abweichung antwortet gleich und gleich lang. Der Wert steht **nirgends** in der Oberfläche und nicht auf dem Schlüsselblatt. | Betreiberin, 27.09.2026 („B und zusätzlich einen Zufallswert in der Datenbank, damit nicht nur FTP-Zugriff ausreicht") | Der Wert schützt gegen den, der Dateien **lesen** kann — ein Webspace-Backup, ein Lesezugang — und gegen den, der nur die Datenbank hat. **Ehrlich dazu:** Wer auf dem Webspace **schreiben** kann, kann eine PHP-Datei hochladen und hat damit auch die Datenbank; gegen ihn hilft kein Wert auf dem Server, und das gilt für jeden Weg, den die Anwendung selbst anbietet. Das Runbook sagt das. Nach dem Gebrauch neu gewürfelt, damit ein einmal abgeschriebener Wert nicht als Dauerzugang liegen bleibt. |
| E-SR-25 | Q-SR-06: **Nr. 232 → `nächste Backlog-Runde`, `nur auf Anlass`.** | Betreiberin, 27.09.2026 | wie empfohlen. |
| E-SR-26 | Q-SR-07: **Nr. 228 wird gebaut — fest, ohne Anlass, ohne Schalter** (Paket SR-08). Der Server stellt beim Laden der Registrierungsseite eine Aufgabe (32 Zufallsbyte, Sitzung, zehn Minuten, einmal gültig); ein Worker im Browser rechnet ab dem Laden, **während die Person tippt**, SHA-256 über WebCrypto, ohne Fremdbestandteil (`assets/pow.js`); der Absendeknopf wartet nur, wenn die Person schneller ist als der Worker („Sicherheitsprüfung läuft …"). Der Server prüft **eine** SHA-256 (unter einer Millisekunde), **vor** jeder Adressprüfung, im selben Fehlerpfad wie Honeypot und Mindestausfülldauer — die Antwortzeitgleichheit aus E-P5b-13 bleibt (Δ < 50 ms). **Die Schwierigkeit ist eine Konstante**, die SR-08 misst und festlegt: Ziel ist der Median **unter einer Sekunde auf einem aktuellen Handy** und unter drei Sekunden auf dem alten Diensthandy (Chromium mit vierfacher CPU-Drosselung als Ersatz im Prüfstand); die gemessenen Hashraten und die gewählte Bitzahl stehen im Prüfdokument. | Betreiberin, 27.09.2026 („Bauen wir einfach fest ein") | Die Frage „wie viel Verzögerung" beantwortet die Bauform: Die Rechnung läuft nebenher, solange jemand Adresse und Passwort tippt (zehn bis dreißig Sekunden), und ist dann in aller Regel fertig — spürbar wird sie nur bei einem Skript, das das Formular sofort abschickt, und genau das ist der Zweck. Ohne JavaScript geht die Registrierung ohnehin nicht (E-P5b-13). Kein Schalter, weil ein Schalter, den niemand umlegt, eine zweite Wahrheit ist (R74). |
| E-SR-27 | **Konzept freigegeben.** Die Umsetzung beginnt nach dem Merge von 17 und des Konzept-PR auf einem eigenen Zweig von `main` (Opus), arbeitet die acht Pakete in der Reihenfolge aus E-SR-14 durch und hält nur an: H-SR-05 (die Migration und das einmalige Neuanmelden ansagen), H-SR-06 (Gegenlesung von SR-03 durch Fable), bei Problemen und vor dem PR (H-SR-04). Keine Fächerung. | Betreiberin, 27.09.2026 („Freigabe") | K5, K6: alle Q sind entschieden, der Paketschnitt steht; `CLAUDE.md` 7: ohne Fächerungszeile keine Fächerung. *Ergänzt 27.09.2026: dazu der Halt H-SR-08 nach SR-09 (E-SR-36).* |
| E-SR-28 | **Passkeys mit PRF als Ersatz der Passwortableitung werden nicht weiterverfolgt.** Nr. 146 verliert den Satz; Schritt 12 stellt die Frage nicht neu, es sei denn, das Bedrohungsmodell wirft sie selbst auf. | Betreiberin, 27.09.2026 („Stufe B lassen wir") | Das Geheimnis, aus dem der Datenschlüssel entstünde, läge bei synchronisierten Passkeys im Schlüsselbund von Apple oder Google — eine Frage an die Zusage der Ende-zu-Ende-Verschlüsselung, nicht an ein Paket; PRF gibt es nicht auf jedem Authenticator, also blieben zwei Ableitungswege je Konto; Rang Haupt in der Größe von S10 (Verschlüsselung, Anmeldung, Reset, Schlüsselerneuerung, Anhebelauf). |
| E-SR-29 | **Passkeys als zweiter Faktor neben TOTP — Paket SR-09, in Schritt 18** (Nachfassung des freigegebenen Konzepts; Nr. 350 aus der eigenen Spanne). Ein Passkey ist ein **weiteres Verfahren desselben Faktors**: Voraussetzung ist der eingeschaltete Zweitfaktor, Codes und Rückweg bleiben der Notweg, `totp_abschalten()` nimmt die Passkeys mit — auf jedem Weg. Ob ein Passkey den Faktor auch allein tragen darf, fragt Q-SR-12 — beantwortet: nein (E-SR-35). | Betreiberin, 27.09.2026 („Stufe A geht ins Paket 18. Nachfassung ist ok") | Ein TOTP-Code lässt sich auf einer gefälschten Seite abgreifen und weiterreichen; eine WebAuthn-Signatur ist an den Ursprung gebunden — der Code-Schritt wird phishingfest, und das ist der Gewinn für Support, Admin und BetreiberIn (K-5). *Berichtigt mit H-SR-08 (F-SR-44): phishingfest ist die Passkey-Antwort, nicht der Schritt — der App-Code daneben bleibt abfischbar.* In 18 statt als eigener Schritt, weil die Anmeldedateien hier ohnehin offen sind und 12b den Code danach liest (R86). Nicht als Ersatz von TOTP, weil Einrichtungstor, Codes und Rückweg am TOTP-Verfahren hängen — das wäre ein Umbau der Anmeldung, nicht ein Verfahren mehr. |
| E-SR-30 | **Bauform ohne Fremdbestandteil:** `passkey_lib.php` mit eigenem CBOR-Leser für die Teilmenge, die Registrierung und COSE brauchen (Ganzzahlen, Byte- und Textketten, Listen und Karten bestimmter Länge; alles andere ist eine Ablehnung); Signaturen über phpseclib (`Crypt/EC` ES256 wie der Rückweg, `Crypt/RSA` RS256 PKCS#1 v1.5); **Attestation `none`** wird verlangt und nicht geprüft; erlaubt sind genau zwei Algorithmen (-7, -257); `rp.id` ist der Host, der Ursprung wird gegen den eigenen geprüft; **UP** muss gesetzt sein, **UV** ist `preferred` und wird nicht verlangt. Der öffentliche Schlüssel wird bei der Registrierung nach SPKI überführt und so gespeichert. | Konzept (F-SR-13) | Was SP-11 als Fremdbibliothek ansah, liegt zu vier Fünfteln im Haus: `rw_pruefen()` ist der Kern einer Assertion. Ein CBOR-Leser für vier Typen ist klein und lesbar; eine WebAuthn-Bibliothek brächte Attestation-Ketten, Metadaten-Dienste und ein Dutzend Formate mit, die hier niemand braucht. Attestation sagt, welcher Hersteller den Authenticator gebaut hat — für einen zweiten Faktor nach dem Passwort ohne Wert und mit Datenschutzpreis (R36). UV nicht verlangt, weil das Passwort das Wissen ist und der Passkey den Besitz beweist; wer UV verlangte, schlösse Hardware-Schlüssel ohne PIN aus. SPKI, weil der Anmeldeweg dann ohne CBOR auskommt und `PublicKeyLoader` die eine Stelle bleibt (R83). |
| E-SR-31 | **Ort: der Abschnitt „Passkeys" in der Karte „Zweitfaktor"** (Einstellungen → Profil), keine eigene Karte; „Passkey hinzufügen" und „Entfernen" stehen in `ZF_FRISCH_HANDLUNGEN` (E-SR-20) — die Karte zeigt den Knopf nur bei frischem Code, sonst den Verweis „Zuerst Code bestätigen", und der Endpunkt prüft es noch einmal (403). Bezeichnung optional, bis 40 Zeichen, sonst „Passkey vom <Datum>"; **kein** User-Agent, **keine** AAGUID; höchstens zehn je Konto. | Konzept | R74: ein Faktor, eine Karte. Wer eine fremde Sitzung erbeutet hat, darf sich damit keinen dauerhaften zweiten Faktor anlegen — genau der Fall, für den E-SR-20 gebaut ist; der Verweis statt eines 403 im Browser, weil die Person die Reihenfolge sehen soll, bevor der Plattform-Dialog aufgeht. Eine Bezeichnung braucht die Liste, sobald zwei Einträge darin stehen (Handy, Laptop) — anders als das Gerätecookie, das niemand sieht (E-SR-07); die AAGUID ist bei Attestation `none` ohnehin null. |
| E-SR-32 | **Im Code-Schritt ist der Passkey der dritte Weg neben App-Code und Wiederherstellungscode:** Knopf „Mit Passkey bestätigen" über dem Codefeld, nur wenn das Konto Passkeys hat und der Browser `PublicKeyCredential` kennt; die Herausforderung liegt im halben Stand wie die des Rückwegs; die Antwort geht als Formularfeld an `login.php` (kein eigener Endpunkt), gezählt im Topf `totp`. **Ein Passkey zählt wie ein App-Code:** Haken „Gerät merken" (E-SR-18) und `zf_frisch_bis` (E-SR-20); dasselbe auf `zweitfaktor.php?bestaetigen=1`. Ohne JavaScript bleibt der Codeweg. | Konzept | Der Rückweg hat den Weg vorgezeichnet (RW-03): Herausforderung im halben Stand, Signatur als Feld, Prüfung vor `anmeldung_vollenden()` — ein zweiter Weg daneben, nicht ein zweiter Mechanismus. Ein Passkey beweist Gerätebesitz mit Nutzergeste, mindestens so stark wie ein App-Code; ihn schwächer zu zählen hieße, den sichereren Weg unbequemer zu machen. |
| E-SR-33 | **Signaturzähler:** neu > alt oder beide 0 → gut; sonst Ablehnung und Protokoll `passkey_zaehler` (orange), der Passkey bleibt. Anlegen und Entfernen schreiben Protokoll (`passkey_angelegt`, `passkey_entfernt`) und eine Mail an das Konto; die Anmeldung mit Passkey schreibt nichts. | Konzept | Ein Zähler, der zurückläuft, ist ein Klon-Verdacht (WebAuthn Level 2, 6.1.1) — aber synchronisierte Passkeys melden dauerhaft 0, und ein Löschen bei Verdacht sperrte die Betroffene aus ihrem eigenen Konto; sie sieht den Eintrag und entscheidet. Mail bei Anlegen und Entfernen wie beim Rückweg (`rueckweg_erneuert`), weil ein neuer zweiter Faktor an einem erbeuteten Konto sonst still bliebe; je Anmeldung wäre Rauschen (E-SR-07). |
| E-SR-34 | **Namen: Was den Faktor meint, heißt `zweitfaktor_*`; was ein Verfahren meint, `totp_*` oder `pk_*`.** Die neuen Helfer aus SR-02 (Gerät merken, erkennen, vergessen, Dauer) heißen deshalb `zweitfaktor_geraet_*`, die Protokollarten `zweitfaktor_geraet_gemerkt` und `zweitfaktor_geraete_vergessen`; `zweitfaktor_frisch*` (SR-07) heißt schon so. Bestehende `totp_*`-Helfer, die den Faktor tragen (`totp_an()`, `totp_abschalten()`, `totp_zustand()`), werden **nicht** umbenannt; `totp_lib.php` bleibt die Bibliothek des Faktors. | Konzept, Nachfassung 27.09.2026 | Seit SR-09 merkt ein Gerät nach App-Code **oder** Passkey — ein `totp_geraet_merken()` hieße dann falsch. Die alten Namen bleiben, weil 12b den Code liest, den es gibt, und ein Umbenennen von zwanzig Aufrufern kein Sicherheitsgewinn ist. |
| E-SR-35 | Q-SR-12: **Passkey nur zusätzlich zum eingeschalteten TOTP** (E-SR-29 bestätigt). „Passkey als einziger Zweitfaktor" wird **Nr. 351**, `nach v1.0`, `zurückgestellt` — mit den drei Umbauten, die er kostete, im Eintrag. | Betreiberin, 27.09.2026 | wie empfohlen: Codes, Einrichtungstor und Reset hängen am TOTP-Verfahren; ein Faktor ohne App ist ein Umbau der Anmeldung, nicht ein Verfahren mehr. Support, Admin und BetreiberIn haben die App ohnehin. |
| E-SR-36 | Q-SR-13: **SR-09 bekommt die Fable-Gegenlesung wie SR-03** — Haltepunkt H-SR-08 ist fest: Die Umsetzungsinstanz hält nach SR-09 an und sagt es, Fable liest `passkey_lib.php` gegen das Konzept, Befunde als F-SR-NN, Behebung durch Opus, dann SR-05. Zwei Halte in der Runde, kein Fable-Schritt (K8). | Betreiberin, 27.09.2026 | wie empfohlen: Ein eigener CBOR-Leser und eine Signaturprüfung sind der Code, bei dem ein falsch gelesenes Längenfeld einen fremden Schlüssel annimmt; die Passkeyprobe misst nur Vorhergesehenes. |
| E-SR-37 | **Nr. 250 gehört zu SR-02, Nr. 344 zu SR-03.** Beide sind mit dem Merge von 17 (28.09.2026, E-R4-37) zu Ziel 18 gekommen: `betrieb_server.php` leitet nach jedem POST um (`flash_setzen()` mit Ort, Ton und Ergebnis, der Weg aus R4-11) — gebaut in SR-02, das die Seite um die Karte „Anmeldung" erweitert; `edbak_freigabe_widerrufen()` und `edbak_begleit_schreiben()` verlangen `edbak_kennung_gueltig()` — gebaut in SR-03, das `adminbackup_lib.php` ohnehin offen hat, und von der Gegenlesung (H-SR-06) mitgelesen. Beide Pakete bleiben in ihrer Stufe. | Konzept, 28.09.2026 (nach dem Merge von 17) | Konzept R4 2.3 hat die Dateien für 18 freigehalten; ein eigenes Paket für zwei Handgriffe wäre mehr Buchführung als Code. Nr. 250 ist ein geänderter Weg (Umleiten), und SR-02 ändert die Wege dieser Seite ohnehin (E-SR-17). |
| E-SR-38 | **Die Umsetzung beginnt vor dem Merge des Konzept-PR, gestapelt auf ihm.** Arbeitszweig `claude/pr95-stufe-18-ztactt`, per Fast-Forward auf den Kopf von PR #95 (`d88522e`); der PR der Umsetzung wird erst nach dem Merge von #95 gestellt. Weicht von E-SR-27 und P-SR-03 ab („nach dem Merge, eigener Zweig von `main`"). | Betreiberin, 28.09.2026 („Hol dir den Stand von PR95 und leg mit Stufe 18 los") | Der Konzept-PR trägt nur `docs/`, Stufe 1 ist grün; auf ihn zu warten, hielte die Umsetzung an, ohne dass sich an ihrer Grundlage etwas ändert. Preis: Wird #95 umgeschrieben (Squash, Rebase), zieht dieser Zweig nach (`Pruefablauf.md` 5.3); `nummern.py` zieht Geerbtes ab (R4-11) und misst deshalb keine Scheinkollision — gemessen: 0 Überschneidungen. |
| E-SR-39 | **F-SR-15 wird in SR-01 gebaut:** Die lesenden Seiten prüfen die Bindung zentral in `sitzung_starten('lesend')` und sehen eine ungebundene Anmeldung nicht; verworfen wird ohne Schreiben, beendet wird sie erst von der nächsten angemeldeten Seite. | Betreiberin, 28.09.2026 (Rückfrage vor SR-01, wie empfohlen) | Ziel 1 des Konzepts heißt „eine gelesene Sitzungsdatei ist wertlos"; vier Seiten, die mit ihr noch Kopf und Adresse zeigten, ließen den Satz halb wahr. Eine Stelle statt vier (R83). Nicht beendet, weil eine lesende Seite lesbar bleiben und nicht abmelden soll. |
| E-SR-40 | **Das Vergessen gemerkter Geräte beim Passwortwechsel und -reset steht in derselben Transaktion wie das Passwort** (`einstellungen.php`, `pw_handling.php`), nicht dahinter. | Umsetzung, 28.09.2026 (SR-02) | Der erste Entwurf setzte es hinter die Transaktion, „weil es ins Protokoll schreibt". Ein Fehler dort hätte dann ein gewechseltes Passwort mit der Meldung „Es wurde nichts geändert" gezeigt, der Browser hätte den neuen Schlüssel nicht übernommen (M2-07), und der gemerkte Browser des Fremden hätte weiter gegolten. In der Transaktion gilt beides oder keines; `db_transaktion()` hängt sich an die laufende an, `protokoll()` fängt seine eigenen Fehler. |
| E-SR-41 | **Eine Handlung der Liste, die eine Seite ist, trägt ein eigenes Abbruchziel** (`'abbruch'` in `ZF_FRISCH_HANDLUNGEN`; heute nur das Schlüsselblatt → `betrieb_server.php#k-schluessel`); es reist als `abbruch` mit und wird wie `zurueck` geprüft. | Umsetzung, 28.09.2026 (SR-07) | Bei einem POST ist der Rücksprung die Karte, aus der geklickt wurde; bei einer Seite ist es die Seite selbst — und „Abbrechen" dorthin schickte wieder auf die Bestätigung, eine Schleife (gefunden beim Schreiben des Bedienwegs). Ein eigenes Feld statt einer Regel „bei GET woanders hin", weil nur die Liste weiß, woher eine Seite kommt. |
| E-SR-42 | **Der eigene Ursprung und die `rp.id` kommen aus `app.base_url`, nicht aus der `Host`-Kopfzeile** (`pk_ursprung()` in `passkey_lib.php`). Folge: keine Passkeys für eine IP-Adresse, ohne HTTPS (außer `localhost`) und unter jeder anderen Adresse als der eingetragenen. **Weicht vom Konzept ab** (SR-09: „`pk_ursprung()` nimmt die Stelle in `kopfzeilen_lib.php`, die den Host schon liest"). **Bestätigt von der Betreiberin am 28.09.2026 bei H-SR-08** (Weg B gegen A „aus der Anfrage" und C „mehrere erlaubte Adressen"); **keine Anlage läuft unter einer zweiten Adresse** — deshalb wird der Knopf unter einem fremden Namen nicht eigens ausgeblendet (M13, F-SR-41). | Umsetzung, 28.09.2026 (SR-09); bestätigt von der Betreiberin, 28.09.2026 | **Berichtigt mit H-SR-08 (F-SR-44, M24):** Hier stand, ein Proxy, der mit seinem eigenen Namen weiterreicht, ließe seinen Ursprung als den eigenen gelten. Das trug so nicht — vor einer nachgemachten Seite schützt vor allem der **Browser**, der einen Passkey für diese Adresse unter einer anderen gar nicht herausgibt, und ein Proxy, der die Anlage beim Hoster erreichen will, muss ohnehin ihren echten Namen in `Host` schicken. Den Ausschlag geben drei Dinge: WebAuthn gleicht mit dem Ursprung ab, den die Anlage **erwartet**, nicht mit dem, den die Anfrage über sich behauptet; eine `rp.id` muss über Jahre dieselbe bleiben, und unter einem zufällig benutzten zweiten Namen entstünde einer, der unter dem Hauptnamen nie gilt; und keine Kopfzeile muss bewertet werden (`Host` oder hinter einem Vermittler `X-Forwarded-Host`). `app_url()` ist die vorhandene Stelle für die eigene Adresse (R83); die Host-Stelle in `kopfzeilen_lib.php` bleibt für den CSP-Bericht richtig. Preis: Die Sandbox auf 127.0.0.1 zeigt keine Passkeys; die Proben stellen die Adresse für ihren Lauf auf `localhost`. |
| E-SR-43 | **Die Passkey-Knöpfe tragen kein Schloss**; „Passkey hinzufügen" trägt das Plus, „Mit Passkey bestätigen" kein Zeichen. | Umsetzung, 28.09.2026 (SR-09) | Das Schloss ist in dieser Anwendung das Zeichen für ein Ende-zu-Ende-verschlüsseltes Feld (`CLAUDE.md` 4, Riegel `kennzeichnung`); ein Schloss an einem Anmeldeknopf verwässerte es. Der erste Entwurf trug es an allen drei Knöpfen. |
| E-SR-44 | **Die Gegenlesung H-SR-08 läuft als lesender Fable-Workflow**, nicht als eigene Fable-Instanz: sieben Leser nach Blickwinkel (CBOR, COSE, Registrierung, Anmeldung, Anbindung, Speicherung, Konzepttreue), je Befund eine oder zwei Gegenprüfungen, ein Kritiker mit Nachlesern — 53 Agenten; nach der Behebung eine **Nachprüfung** der geänderten Stellen, lesend, höchstens zehn Agenten. Weicht von „Fächerung: keine" im Kopf ab. | Betreiberin, 28.09.2026 („Kannst du das selbst umstellen? Starte gerne, wenn das geht"; Nachprüfung mit dem Plan bestätigt) | Eine Fable-Instanz hätte die Betreiberin umstellen müssen; die Workflow-Agenten laufen auf dem Modell, das der Aufruf nennt. `CLAUDE.md` 7 nimmt Prüfarbeit und erzählenden Text von der Fächerung aus — eine Lesung ist **Messung** (lesend, ohne Nebenwirkung) und damit gut gefächert. Keiner der Agenten schreibt; Behebung, Proben und Texte macht die Umsetzungsinstanz seriell. |
| E-SR-45 | Q-SR-14: **Eine abgewiesene Registrierung heißt nach außen „nicht angenommen"** — auch „diese Kennung gibt es schon"; der Grund steht im Protokoll (`passkey_abgewiesen`, neutral). Ein Wettlauf zweier Konten (`23000`) endet in derselben Antwort. | Betreiberin, 28.09.2026 (wie empfohlen) | Mit Attestation `none` ist die Kennung frei wählbar; die eigene Antwort darauf verriet, ob eine Kennung irgendwo auf der Anlage liegt (M26). Die Verwaltung braucht den Grund, die Person nicht. |
| E-SR-46 | Q-SR-15: **Ein zurückgelaufener Zähler löst eine Mail an die Kontoadresse aus**, Vorlage `passkey_zaehler`, **höchstens eine je Passkey und Tag** (Spalte `gewarnt_am`); die Meldung im Code-Schritt sagt, was es heißen kann, und der Protokolleintrag nennt den Passkey beim Namen. | Betreiberin, 28.09.2026 (wie empfohlen) | E-SR-33 lässt den Passkey stehen, weil die Betroffene sieht und entscheidet — die Rolle `user` sah nichts (N1-1). Der Deckel, weil jeder weitere Versuch mit der Kopie sonst eine weitere Mail wäre; auslösen kann ihn nur, wer einen gültigen Schlüssel hat. |
| E-SR-47 | Q-SR-16: **Eindeutig über den SHA-256 der Kennung** (`credential_hash CHAR(64)`, `ascii_bin`); die Kennung selbst steht daneben ohne Index, die Anmeldung sucht über den Hash. | Betreiberin, 28.09.2026 (wie empfohlen) | Der Index über `VARCHAR(1364)` war der erste des Schemas über 767 Byte und scheitert unter COMPACT (M31). Die Migration war noch nirgends ausgeliefert, die Änderung also billig (E-SR-51). |
| E-SR-48 | Q-SR-17: **Jede Zeile trägt ihre `rp_id`**; Code-Schritt und Bestätigung bieten nur Passkeys der heutigen Adresse an, die Zehnergrenze zählt je Adresse, die Karte zeigt die übrigen mit der Plakette „andere Adresse" (entfernbar), die Kontoseite der Verwaltung zählt alle. | Betreiberin, 28.09.2026 (**abweichend** von der Empfehlung „nur Doku und bessere Meldung") | Nach einem Umzug stand sonst ein Knopf, der ins Leere führt, und tote Zeilen belegten die Grenze (M29). Die Spalte kostet eine Zeile in der Migration, solange sie nirgends ausgeliefert ist. |
| E-SR-49 | Q-SR-18: **Ein Passkey hängt nicht am Serverschlüssel — gewollt.** Lässt sich das App-Geheimnis nach einem Wiederanlauf mit anderem Schlüssel nicht öffnen, meldet ein Passkey weiter an; die Texte (Code-Schritt, Bestätigung, Karte, Handbuch, Runbook) sagen es und nennen die Reihenfolge danach: ausschalten oder zurücksetzen lassen, neu einrichten, Passkeys neu anlegen. | Betreiberin, 28.09.2026 (wie empfohlen) | Der Passkey ist der bequemste Weg durch genau diese Lage, und er ist nicht schwächer als ein Wiederherstellungscode (N2-1). Ihn dort zu sperren hieße, einen funktionierenden Faktor abzuschalten, weil ein anderer Teil nicht öffnet. |
| E-SR-50 | **Eine Passkey-Ablehnung zählt nur als Fehlversuch, wenn geprüft wurde**: Art `pruefung` ja; `herausforderung` (keine, abgelaufen, verdrängt) und `zaehler` nein. | Umsetzung, 28.09.2026 (H-SR-08, F-SR-37) | Der Topf `totp` bremst das Raten von Codes; eine abgelaufene Anfrage und ein zurückgelaufener Zähler sind kein Raten — nach dem Gebrauch einer Kopie sperrte sonst das Original mit gültiger Signatur das eigene Konto. Der Rückweg zählt denselben Fall schon nicht. |
| E-SR-51 | **Die Migration `2026_09_28_passkeys` wird an Ort und Stelle geändert**, nicht durch eine zweite ergänzt; ihre Kennung und `'web' => '21.10'` bleiben. | Umsetzung, 28.09.2026 (H-SR-08) | 21.10.0 ist nirgends ausgeliefert — nicht auf `main`, nicht auf Staging; eine zweite Migration hätte eine Tabelle umgebaut, die es auf keiner Anlage gibt, und `migration_lib.php` um einen Katalogeintrag ohne Gegenstand verlängert. Örtlich ist die Tabelle neu eingespielt, migriert gegen frisch zeichengleich (Prüfdokument). **Preis:** Wer 21.10.0 örtlich eingespielt hat, spielt die Tabelle neu ein. |
| E-SR-52 | **Eine Ablehnung der Art `zaehler` bleibt ungezählt**; der Preis — eine Kopie holt ohne Bremse auf, jeder Versuch schreibt eine orange Zeile — ist benannt (CHANGELOG, `Technik.md` 4.99q), kein eigener Topf. | Umsetzung, 29.09.2026 (Nachprüfung, F-SR-49) | Der Zähler ist gegen eine Kopie des privaten Schlüssels keine Hürde: Wer den Schlüssel hat, signiert den Zähler, den er will. Eine Bremse hielte nur die Betroffene auf, deren Original nach dem Gebrauch einer Kopie zurückliegt — genau der Fall, den N1-2 beanstandet hat. Die orangen Zeilen kann nur erzeugen, wer Passwort und Schlüssel hat; der könnte sich auch anmelden. |
| E-SR-53 | **F-SR-50 wird nicht in H-SR-08 behoben, sondern Nr. 354** (`nächste Backlog-Runde`). | Umsetzung, 29.09.2026 (Nachprüfung) | Die Ursache ist `protokoll()` selbst, und sie trifft seit SR-02 auch E-SR-40. Ein `protokoll()`, das in einer Transaktion wirft, ändert alle Aufrufer; zwei Einträge hinter den Commit zu legen, hieße, E-SR-40 und diese Stelle verschieden zu bauen. Beides verdient ein eigenes Paket mit eigener Probe (ein Auslöser mit `40001`). Selten: ein Deadlock an einem INSERT ohne Fremdschlüssel. |
| E-SR-54 | **`UPDATE devices SET last_seen` wandert mit `dt_zeitraum_fortschreiben()` hinter den Commit**, in dieselbe kurze Anweisung mit eigenem Wiederholungsrahmen. Scheitert sie nach drei Anläufen, antwortet die Anfrage 503, obwohl der Datensatz steht; die Uhr liefert nach, und das ist eine Wiederholung. | Umsetzung, 29.09.2026 (SR-05, F-SR-53) | Gemessen, nicht vermutet: 10 der 39 Deadlocks standen dort. Dasselbe Muster wie der Diensttag (geteilte Sperre über den Fremdschlüssel, später die exklusive), und `last_seen` ist eine Anzeige, die niemand innerhalb der Transaktion braucht. Die Messung danach: Das Verschieben beider Zeilen allein bringt 180 von 180. |
| E-SR-55 | **Die Verbindungsprobe bekommt eine zweite Lage** (ohne Enge, `--frei` ≥ belegte Plätze: eine Runde, 0 × 503 verlangt) statt eines eigenen Werkzeugs; **für `ingest.php` und `wartung_lib.php` läuft sie ab Stufe klein** (F-SR-08 beantwortet: ja, über das vorhandene Muster `datenbank`). Der Rahmen bleibt in `ingest.php` (Empfehlung des Pakets). | Umsetzung, 29.09.2026 (SR-05, F-SR-54) | Die Probe stellt die Lage schon her; eine zweite Probe für dieselbe Lage wäre Grundsatz 1 zuwider. In `klein`, weil die Probe den Fehler misst, den eine Korrektur an `ingest.php` beheben oder zurückbringen kann — sie braucht die Wurzel der Datenbank und läuft nur örtlich, wie `nummern`. |
| E-SR-56 | **Nr. 354 wird gemessen und festgehalten, nicht behoben** — Antwort (a); fiele die Messung schlimmer aus als beschrieben, wird neu gefragt. | Betreiberin, 29.09.2026 (Nachprüfung der offenen Punkte) | Die Behebung ändert `protokoll()` für alle Aufrufer und ist ein eigenes Paket (E-SR-53). **Gemessen:** anders, nicht schlimmer (F-SR-63) — statt eines gemeldeten Erfolgs ein Fehler mit halbem Stand; ein zweiter Versuch schließt ab. Kein Anlass für die Rückfrage. |
| E-SR-57 | **Die vier offenen Punkte von H-SR-08 werden gemessen, die Einarbeitung ein drittes Mal gelesen** — sofort, parallel zum Prüfstand von SR-05, ohne Last- und Kostentests (Fable, lesend, drei Leser, drei Gegenprüfer). | Betreiberin, 29.09.2026 | „Nur gelesen" am Anfang des Prüfdokuments ist ein Zustand, kein Ziel. Ohne Last, weil der Prüfstand zeitkritische Proben fuhr; wo ein Beleg Last braucht, rechnen die Leser und sagen es. |
| E-SR-58 | **Nach dem Absenden sagt die Meldung immer „Bitte die Seite neu laden"**, statt nur Kommentar und Texte auf das Verhalten zu verengen. | Umsetzung, 29.09.2026 (F-SR-62) | Ein grauer Knopf neben einer Meldung ohne Ausweg ist eine Sackgasse. Der Satz stimmt in jedem Fall nach dem Absenden — ob der Server den Passkey bekam, weiß der Browser dann nicht. |
| E-SR-59 | **F-SR-65 wird Nr. 355** (`nächste Backlog-Runde`), nicht hier behoben. | Umsetzung, 29.09.2026 | 114 Stellen, projektweit, älter als die Passkeys. Eine Stelle je Sache (R83) mit Registerzeile ist ein eigenes Paket; hier eine Hälfte davon zu bauen, hieße zwei Wege für dieselbe Sache. |
| E-SR-60 | **Anteil-Rotation und Serverschlüssel-Wechsel laufen nur nacheinander**; der Riegel steht in beiden Funktionen (`anteil_wechseln()`, `serverschluessel_wechseln()`), der Knopf fehlt zusätzlich. | Betreiberin, 29.09.2026 (vor SR-03) | Nebeneinander trüge das Blatt vier Kacheln und zwei Seiten; mit drei passt es gerade auf eine (P5c/AP9: 1013 von 1017 px; im Bedienweg als PDF gezählt: 1 Seite). Ein Riegel nur am Knopf wäre eine Bitte. |
| E-SR-61 | **Was sich mit keinem der beiden öffnen lässt, wird gemeldet, nicht blockiert** — genannt auf der Karte (bis zu zwanzig, ohne Kontokennung) und im Protokoll, nicht angefasst. | Betreiberin, 29.09.2026 (vor SR-03) | Es war vor dem Wechsel schon stumm; ein Wechsel, der daran hängen bliebe, ließe sich nie abschließen — und gerade bei Verdacht muss er abschließen. |
| E-SR-62 | **Die Mail geht an jedes Konto der Rolle BetreiberIn mit Passwort** (`mail_betreiberinnen()`, neben `mail_betriebsziele()`), beim Beginn und beim Abschluss, nur Kennungen. | Betreiberin, 29.09.2026 (vor SR-03) | Q-SR-04 sagte „alle BetreiberInnen"; die Betriebsziele sind eine Adressliste der Anlage, keine Personen. Ohne Passwort gibt es niemanden, der das Konto bedient. |
| E-SR-63 | **Beim Start ein Pflicht-Haken und die Rückfrage des Browsers**; der Haken wird im Server geprüft (`serverschluessel_wechseln($kopienVerstanden)`). | Betreiberin, 29.09.2026 (vor SR-03; F-SR-68) | Der Vorgang erreicht das Backup-Ziel nicht (E-SR-10); das soll beim Start gesagt und bestätigt sein, nicht erst im Handbuch stehen. |
| E-SR-64 | **Der Zustand des Jobs ist ein Zeiger, keine Liste**: Phase, Zweck, das letzte bearbeitete Stück (lexikalisch; Zeilennummern auf zehn Stellen), Zahlen je Durchgang, `umgehuellt` über alle. | Umsetzung, 29.09.2026 | `jobs.zustand` ist `TEXT` (64 KiB); eine Liste aller Stücke wüchse mit der Anlage. Der Zeiger setzt nach jedem Abbruch dort an, wo das letzte fertige Stück war. |
| E-SR-65 | **„Jetzt weiterarbeiten" geht über `jobs_einen_lauf()`** mit dem Auslöser `seite` — abweichend von E-SR-09 („ohne Job-Auslöser"). | Umsetzung, 29.09.2026 | Zwei Umhüller gleichzeitig (Knopf und Cron) räumten einander den Arbeitsordner leer; der Rahmen hat die Sperre `laeuft_seit`. Der Knopf fragt die Pause nicht, wie „Jetzt sichern". |
| E-SR-66 | **Die Abnahme von Nr. 344 steht in der Schlüsselwechselprobe (Teil 7), nicht in der Freigabeprobe.** | Umsetzung, 29.09.2026 | Die Freigabeprobe arbeitet als NutzerIn im Browser und hat kein Adminkonto; die Schlüsselwechselprobe hat die gebundene Sitzung einer BetreiberIn schon. Derselbe POST, dieselbe Abnahme. |
| E-SR-67 | **Neue Komplett-Stände tragen `kennung` im Kopf** (nur bei `kdf: null`; F-SR-11 entschieden). | Umsetzung, 29.09.2026 | Billig, und die Liste liest sich ohne Öffnen; die Bedingung „Stand unter dem neuen" prüft Kopf **und** ersten Block. Über die Bindung hängt die Kennung an jedem Block. `Backup-Format.md` 6.3. |
| E-SR-68 | **Der frische Komplett-Stand wird nach dem Nachweis angestoßen** (`sw_komplett_anstossen()`), nicht beim Beginn (Q-SR-03, F-SR-74). | Umsetzung, 29.09.2026 | Ein Stand mitten im Umhüllen trüge Zeilen unter beiden Schlüsseln und verlangte beim Einspielen beide. Ein von Hand früher angelegter Stand erfüllt die Bedingung trotzdem (sie sagt „jünger als der Beginn", E-SR-09) — das Runbook sagt, erst danach. |
| E-SR-69 | **Die Probe fährt den Rückweg mit demselben Job** (B → A) und vergleicht danach jedes Stück der Anlage mit dem Bild von vorher; sie hält die Jobs an. Der Bedienweg benutzt ihre Buchführung (`--merken`, `--stand`, `--zurueck`). | Umsetzung, 29.09.2026 (F-SR-75) | Der erste Wechsel hüllt auch die Geheimnisse der Anlage selbst um (Zweitfaktor der Prüfkonten); `config.php` byte-gleich zurückzulegen machte sie unlesbar. Zwei Buchführungen für denselben Vorgang liefen auseinander. |
| E-SR-70 | **Eine Zeile wird mit einem bedingten `UPDATE … WHERE id = ? AND feld = <gelesen>` ersetzt**, nicht in einer Transaktion je Zeile (E-SR-09). | Umsetzung, 29.09.2026 (F-SR-70) | Eine Anweisung ist atomar; die Bedingung fängt zusätzlich, was eine Transaktion nicht fängt — einen Wert, der zwischen Lesen und Schreiben neu gespeichert wurde. Dann „später", und der Nachweis findet ihn. |
| E-SR-71 | **Die Gegenlesung H-SR-06 läuft als lesender Fable-Workflow wie H-SR-08**: vier Leser, je Leser ein Gegenprüfer; die Leser rechnen oder messen die Punkte, die das Prüfdokument „nicht geprüft" nennt (künstliche Daten, gekennzeichnet). **Opus behebt die bestätigten Befunde im selben Zug**; danach eine Nachprüfung der Behebung, lesend, höchstens zehn Agenten. | Betreiberin, 04.10.2026 („Workflow wie H-SR-08", „Ja, rechnen") | Dasselbe Mittel wie bei H-SR-08 (E-SR-44); das Rechnen nimmt dem Prüfdokument die Zeilen „nicht geprüft", die sich ohne Staging beantworten lassen. |
| E-SR-72 | **Ein Komplett-Stand, dessen Versiegelung über einen Wechsel läuft, wird neu versiegelt; der Wechsel wartet nicht.** | Betreiberin, 04.10.2026 (F-SR-79) | Im Ernstfall — Verdacht auf einen kompromittierten Schlüssel — darf ein laufendes Backup den Wechsel nicht um Stunden aufhalten. Der Dump ist Klartext; nur das Versiegeln beginnt von vorn, wie beim Archivjob. |
| E-SR-73 | **Der Nachweis beginnt frühestens zehn Minuten nach dem Beginn**, und Bedingung 1 verlangt das. | Betreiberin, 04.10.2026 (F-SR-79) | Länger als jeder Prozess, der vor dem Wechsel begonnen haben kann (CLI 300 s, Anfrage 240/300 s). Ein Riegel „kein Wechsel, solange ein Job läuft" deckte die Anfragen nicht, die ebenfalls versiegeln. Preis: zehn Minuten bis Bedingung 1. |
| E-SR-74 | **Ein Wechsel, der von Hand in `config.php` steht, bekommt den Beginn eines Wechsels über die Karte** — Protokoll mit Vermerk, Mail an jede BetreiberIn, die Rückfrage sofort fällig; abgeschlossen wird über die Karte. | Betreiberin, 04.10.2026 (F-SR-80) | Ein Schlüssel, den jemand an der Karte vorbei einträgt, ist genau das, was die zweite BetreiberIn erfahren soll; und ohne die fällige Rückfrage blieb der Abschluss bis zu einem Quartal zu. |
| E-SR-75 | **Die Behebung ist Web 21.12.1, Korrekturstufe.** | Betreiberin, 04.10.2026 | Fehlerbehebung an der eben eingeführten Funktion; kein Schema, keine neue Funktion im eigentlichen Sinn. |
| E-SR-76 | **Die Sperre ist die der Jobzeile `schluesselwechsel` und steht um jeden Griff an einen Schlüssel in `config.php`**; dazu bedingtes Ersetzen und „gültiger Eintrag" aus der Datei. | Umsetzung, 04.10.2026 (F-SR-78) | Dasselbe bedingte `UPDATE` wie im Jobrahmen schließt Griffe und Häppchen gegenseitig aus, ohne zweite Mechanik. Eine Sperrdatei wäre ein neunter Pfad, den die Kette nicht ausliefern darf (`CLAUDE.md` 3); `GET_LOCK` ist auf Produktiv nie gemessen. Die beiden Riegel in `config_eintrag_schreiben()` halten auch für einen Schreiber, der die Sperre nicht nimmt — die Gegenproben zeigen jeden für sich. |
| E-SR-77 | **Ein Stück, das beim Umhüllen wirft, wird genannt und übersprungen und hält Bedingung 1 zu**; der nächste Versuch kommt mit dem nächsten Häppchen. `sw_umsiegeln()` wirft, statt `null` zu geben. | Umsetzung, 04.10.2026 (F-SR-81) | Es liegt noch unter dem bisherigen — anders als ein `verloren`es (E-SR-61), das schon vorher stumm war. Ein Stück darf den Wechsel nicht anhalten, aber auch nicht seinen bisherigen Schlüssel verlieren. |
| E-SR-78 | **Die 42 Meldungen von H-SR-06 stehen als sechs Befunde im Konzept**, die Einzelkennungen im Prüfdokument. | Umsetzung, 04.10.2026 | Befundnummern sind zweistellig (`Pruefablauf.md` 7); nach F-SR-77 bleiben 22, und SR-04, SR-08 und SR-06 brauchen noch welche. H-SR-08 hat dasselbe getan (41 Meldungen, zwölf Befunde). |
| E-SR-79 | **Die Funde der Nachprüfung von H-SR-06 stehen als vier Befunde (F-SR-84 bis -87) und werden im selben Zug behoben** — bis auf drei, die bewusst bleiben: die Stunde, die eine stehengebliebene Sperre die Griffe sperrt (die Meldung nennt jetzt die Zeit), der Grund als Ausnahmetext (zu Nr. 357) und die Stand-Datei der Probe im Temp-Verzeichnis (D-2, 0600). Die Waise beim Anteil heißt wie beim Serverschlüssel `bereit`. | Umsetzung, 04.10.2026 | E-SR-71 sagt „im selben Zug". Die Sperre teilen Job und Griffe; eine kürzere Frist nur für die Griffe brauchte eine zweite Sperre, und ein Häppchen, das an der Zeitgrenze stirbt, ist selten. Nr. 357 betrifft jeden Job. Ein eigener Ordner für die Stand-Datei wäre eine Stelle mehr zum Vergessen; 0600 vom ersten Byte an steht. Nach F-SR-87 bleiben zwölf Befundnummern (nach F-SR-88 elf). |
| E-SR-80 | **Die Punkte, die das Prüfdokument von 21.12.1 „gelesen, nicht gemessen" nennt, werden gemessen — als eigener Nachtrag nach dem Commit von H-SR-06**, mit eigenem Prüfstand und Commit. Der gefährliche Gegenfall (die Behebung der Probe zurück, dritter Wechsel) läuft gegen eine vollständige Sicherung der Anlage und wird danach zurückgelegt. „Mehr als 20" und die Ausfälle beim Beginn werden einmal gemessen (Verfälschung im Scratchpad); Knopf, Kleinzeile und Download unter drittem Schlüssel kommen fest in die Schlüsselwechselprobe. Findet eine Messung einen Fehler im Code, wird das Web 21.12.2. | Betreiberin, 04.10.2026 („Prüf das bitte noch"; „Erst committen, dann Nachtrag") | Was nur gelesen ist, ist nicht belegt (`CLAUDE.md` 6). Der Commit von H-SR-06 wartet nicht auf eine Messung, die selbst einen Prüfstand braucht. Fest in die Probe nur, was billig ist und bleibt; 22 Konto-Ordner und Verfälschungen des Codes gehören nicht in jeden Lauf. |

### 3.2 Fragen an die Betreiberin

Jede mit Empfehlung; „alles wie empfohlen" ist eine gültige Antwort.

| Nr. | Frage | Empfehlung | vor |
|---|---|---|---|
| Q-SR-01 | **„Gerät merken" für alle Rollen** — auch Support, Admin und BetreiberIn? Oder nur für NutzerInnen? | **Alle Rollen**, 30 Tage. Die Pflichtrollen melden sich am häufigsten an; ein gemerktes Gerät ist Passwort plus Gerätebesitz, und die Karte zeigt jeder, wie viele Geräte sie gemerkt hat. Alternative: nur `user` — dann fällt der Haken bei Pflichtrollen weg, und die Karte auch. | SR-02 |
| Q-SR-02 | **Nur nach einem App-Code merken** — nicht nach Wiederherstellungscode, nicht nach dem Rückweg? | **Ja.** Ein Wiederherstellungscode heißt „das Handy fehlte", der Rückweg „Handy und Codes fehlen" — in beiden Lagen ist das Gerät vor der Betroffenen nicht als ihres ausgewiesen. | SR-02 |
| Q-SR-03 | **Komplett-Stände beim Schlüsselwechsel:** neu sichern statt umhüllen? Alte lokale Stände bleiben unter dem alten Schlüssel, bis die Aufbewahrung sie räumt; ein frischer Stand unter dem neuen ist Abschlussbedingung. | **Ja.** Umhüllen hieße, jeden Block jedes Stands neu zu schreiben (Gigabyte, in Häppchen, über Tage); ein frischer Stand ist ohnehin fällig, weil der alte Schlüssel als kompromittiert gilt. Preis: Bis zum frischen Stand gibt es keinen Komplett-Stand unter dem neuen Schlüssel — der Vorgang sagt das und stößt „Jetzt sichern" an. | SR-03 |
| Q-SR-04 | **Mail an alle BetreiberInnen** bei Beginn und Abschluss eines Serverschlüssel-Wechsels? | **Ja**, an alle mit der Rolle BetreiberIn (nicht Admin): Ein Schlüsselwechsel ist ein Sicherheitsereignis, und die zweite BetreiberIn soll es nicht erst am Blatt merken. Vorlage `serverschluessel_gewechselt`, ohne Werte, mit Kennungen. | SR-03 |
| Q-SR-05 | **Nr. 249:** (B) den Notzugang mit Nachweisdatei bauen (E-SR-13), oder (C) nur den Runbook-Notweg ordnen und Nr. 249 als `nicht umsetzen` schließen? | **(B).** Der SQL-Weg steht an der Anwendung vorbei — kein Protokoll, keine Mail, und er verlangt ein Datenbankwerkzeug, das nicht jede Selbsthosterin hat. (B) kostet ein Paket mittlerer Größe; (C) kostet nichts und lässt den Fall, für den die Bus-Faktor-Zeile orange steht, beim Hoster. | SR-04 |
| Q-SR-06 | **Nr. 232** → `nächste Backlog-Runde`, `nur auf Anlass`? SR fasst `einstieg_lib.php` an, baut aber keine dritte Frist — und der Eintrag sagt: „Lohnt sich, wenn die nächste Frist dazukommt." | **Ja.** Eine Zeitinjektion in `einstieg_lib.php` nur für zwei Fristen ist teurer als der Fehler (der Eintrag selbst). | SR-06 |
| Q-SR-07 | **Nr. 228** → `nach v1.0`, `nur auf Anlass`? Der Anlass — verfallene, nie bestätigte Konten je Woche — entsteht erst mit offener Registrierung (Welle nach R41), und der Zähler steht im Protokoll (`konto_geloescht`, `weg = verfall`). | **Ja.** Bis zur Öffnung gibt es nichts zu messen; 18 baut nichts daran. | SR-06 |
| Q-SR-08 | **Mockups** für den Haken im Code-Schritt, die Zeile in der Karte „Zweitfaktor", den Fortschritt in der Karte „Schlüssel des Servers" und die Seite des Notzugangs? | **Nein** (E-SR-15): vorhandene Bausteine, kein neuer; die Bilder aus dem Bilderlauf kommen ins Prüfdokument (P-SR-05). Wer eines will, sagt es vor SR-02. | SR-02 |
| Q-SR-09 | **Fable für SR-03** (der Schlüsselwechsel), oder Opus wie überall? | **Opus.** Das Konzept legt Inventar, Reihenfolge und Nachweis fest; das Risiko trägt die Prüfliste (P-SR-08 bis -12: der ganze Vorgang auf der Sandbox mit allen sechs Zwecken, danach jedes Stück geöffnet). Alternative: Fable nur für die Gegenlesung des Pakets vor dem PR — ein Halt, kein Schritt. | SR-03 |
| Q-SR-10 | *(aus der Antwort auf Q-SR-01)* **Die einstellbare Dauer des Merkens — wo und für wen?** Zwei Vorgaben je Rollengruppe · zwei Vorgaben und persönlich kürzer wählbar · eine Vorgabe für alle. | **Zwei Vorgaben je Rollengruppe** (NutzerInnen 30, Verwaltungsrollen 7 Tage; aus, 1, 7, 14, 30, 90), keine persönliche Wahl. | SR-02 |
| Q-SR-11 | *(aus der Rückfrage der Betreiberin zu Q-SR-01)* **Kritische Aktionen trotz gemerktem Gerät — ein „frischer Code"?** Kurze Liste · nur der Betrieb · keiner. | **Ja, kurze Liste** (Schlüssel-Griffe, Schlüsselblatt, Rolle ändern, fremden Zweitfaktor zurücksetzen, Konto löschen, eigenen Zweitfaktor ausschalten); 15 Minuten; ein Paket. | SR-07 |
| Q-SR-12 | *(aus der Nachfassung 27.09.2026)* **Darf ein Passkey den Zweitfaktor auch allein tragen** — ohne TOTP-App —, oder nur zusätzlich zum eingeschalteten TOTP? | **Nur zusätzlich** (E-SR-29). Allein hieße: Wiederherstellungscodes ohne TOTP-Geheimnis, ein Einrichtungstor, das einen Passkey annimmt, ein Reset-Weg ohne App — die Anmeldung würde umgebaut, nicht erweitert. Wer es allein will, bekommt einen eigenen Backlog-Punkt für später. Alternative: allein erlaubt — dann wächst SR-09 um etwa die Hälfte. | SR-09 |
| Q-SR-13 | *(aus der Nachfassung 27.09.2026)* **Bekommt SR-09 dieselbe Fable-Gegenlesung wie SR-03** (E-SR-23), oder baut Opus es ohne? | **Ja, Gegenlesung** (H-SR-08): Ein selbst geschriebener CBOR-Leser und eine Signaturprüfung sind die Art Code, bei der ein falsch gelesenes Längenfeld einen fremden Schlüssel annimmt — und die Probe misst nur die Fälle, die jemand vorhergesehen hat. Preis: eine zweite Lesung. Alternative: Opus allein, die Passkeyprobe als einziger Beleg. | SR-09 |
| Q-SR-14 | *(aus H-SR-08, M26)* **Soll der Endpunkt nach außen nur „nicht angenommen" sagen**, auch wenn es die Kennung schon gibt — oder so bleiben? | **Nur „nicht angenommen"**, der Grund ins Protokoll. | H-SR-08 |
| Q-SR-15 | *(aus H-SR-08, N1-1)* **Eine Mail bei zurückgelaufenem Zähler** (`passkey_zaehler`, höchstens eine je Passkey und Tag), oder nur das Protokoll? | **Mail.** Ohne sie erfährt die Rolle `user` nie von einer möglichen Kopie. | H-SR-08 |
| Q-SR-16 | *(aus H-SR-08, M31)* **Eindeutigkeit über einen SHA-256 der Kennung**, oder den Index über 767 Byte lassen und die Voraussetzung dokumentieren? | **Hash**, `CHAR(64)` — die Migration ist noch nirgends ausgeliefert. | H-SR-08 |
| Q-SR-17 | *(aus H-SR-08, M29)* **Alte Passkeys nach einem Umzug:** nur Doku und eine bessere Meldung, oder die `rp.id` je Zeile speichern und nur passende anbieten? | **Nur Doku und Meldung** — kein Umzug geplant. | H-SR-08 |
| Q-SR-18 | *(aus H-SR-08, N2-1)* **Passkey bei unlesbarem App-Geheimnis:** gewollt, Texte berichtigen — oder Passkeys in diesem Zustand sperren? | **Gewollt**, Texte berichtigen. | H-SR-08 |

**Beantwortet am 27.09.2026** in der Konzeptsitzung (Klickrunde mit
Erklärung je Frage): Q-SR-01 „alle Rollen, aber einstellbare Dauer" →
E-SR-17 mit Q-SR-10; Q-SR-02 wie empfohlen → E-SR-18; Q-SR-03 → E-SR-21;
Q-SR-04 → E-SR-22; Q-SR-05 **(B) plus Datenbankwert** → E-SR-24;
Q-SR-06 → E-SR-25; Q-SR-07 **abweichend: fest einbauen** → E-SR-26;
Q-SR-08 → E-SR-19; Q-SR-09 **Opus baut, Fable liest gegen** → E-SR-23;
Q-SR-10 wie empfohlen → E-SR-17; Q-SR-11 wie empfohlen → E-SR-20.

**Beantwortet am 27.09.2026 (Nachfassung, Klickrunde):** Q-SR-12 wie
empfohlen → E-SR-35 (dazu Nr. 351); Q-SR-13 wie empfohlen → E-SR-36.

**Beantwortet am 28.09.2026 bei H-SR-08:** E-SR-42 bestätigt (Weg B, keine
zweite Adresse); Q-SR-14 wie empfohlen → E-SR-45; Q-SR-15 wie empfohlen →
E-SR-46; Q-SR-16 wie empfohlen → E-SR-47; Q-SR-17 **abweichend: `rp_id` je
Zeile** → E-SR-48; Q-SR-18 wie empfohlen → E-SR-49. Kein Q ist offen.

### 3.3 Haltepunkte

- **H-SR-01 vor SR-02:** Q-SR-01, -02, -08, -10 entschieden — **erfüllt
  27.09.2026.**
- **H-SR-02 vor SR-03:** Q-SR-03, -04, -09 entschieden — **erfüllt
  27.09.2026**; die Betreiberin weiß, dass der Vorgang auf Staging einmal
  ganz gefahren wird (P-SR-08 ff.) und dass ein Komplett-Stand dabei
  entsteht.
- **H-SR-03 vor SR-04:** Q-SR-05 entschieden — **erfüllt 27.09.2026.**
- **H-SR-04 vor dem PR:** Prüfdokument 1 bis 4 gefüllt; Prüfstand des
  Kopf-Commits grün (Stufe `haupt`, Migration); H-SR-06 durchlaufen.
- **H-SR-06 nach SR-03 (E-SR-23):** Die Umsetzungsinstanz hält an und sagt
  es; eine Fable-Instanz liest SR-03 gegen das Konzept (Inventar, Nachweis,
  die drei Bedingungen als Riegel, `sk_oeffnen()` mit zwei Schlüsseln, das
  Blatt); Befunde als F-SR-NN, Behebung durch Opus, dann SR-04. **Erreicht
  29.09.2026** nach dem Commit `SR-03`. **Durchlaufen 04.10.2026:** gelesen
  als Workflow mit Rechnungen (E-SR-71), 42 Meldungen (F-SR-78 bis -83),
  E-SR-72 bis -75 von der Betreiberin, behoben in Web 21.12.1 (Abschnitt 4,
  nach SR-03).
- **H-SR-07 vor SR-09:** Q-SR-12, -13 entschieden — **erfüllt
  27.09.2026** (E-SR-35, -36).
- **H-SR-08 nach SR-09 (E-SR-36):** wie H-SR-06 — Fable liest
  `passkey_lib.php` gegen das Konzept (die CBOR-Teilmenge und jede
  Ablehnung, Ursprung und `rpIdHash`, die Signatur beider Algorithmen, der
  Zähler, die Frische an Karte und Endpunkt); Befunde als F-SR-NN, Behebung
  durch Opus, dann SR-05. **Erreicht 28.09.2026** nach dem Commit `SR-09`;
  dazu E-SR-42 (Ursprung aus der Konfiguration, Abweichung vom Konzept) zur
  Bestätigung. **Durchlaufen 28./29.09.2026:** gelesen als Workflow
  (E-SR-44), 41 Befunde (F-SR-33 bis -44), E-SR-42 bestätigt, Q-SR-14 bis
  -18 beantwortet, behoben in Web 21.11.0 (Abschnitt 4, nach SR-09), die
  Behebung von Fable nachgeprüft (F-SR-45 bis -52) und eingearbeitet bis
  auf Nr. 354. **Nachtrag 29.09.2026 (Web 21.11.2):** die vier offenen
  Punkte gemessen, die Einarbeitung ein drittes Mal gelesen (F-SR-58 bis
  -67, E-SR-56 bis -59).
- **H-SR-05 mit dem Merge:** Die Migrationen `vertraute_geraete` (SR-02)
  und `passkeys` (SR-09) stehen aus,
  bis eine Administratorin `update.php` ruft — die Kette lässt den
  Wartungsmodus an und sagt es (`CLAUDE.md` 3). **Und: Nach dem Ausrollen
  ist jede Angemeldete einmal abgemeldet** (E-SR-04) — die Ankündigung
  (12.8) sagt es vorher.

## 4. Arbeitspakete

Ein Paket je Thema; Reihenfolge nach E-SR-14. Jedes Paket: Code, dann
Doku, dann Prüfmittel (`CLAUDE.md` 6), Statusblock fortschreiben, pushen
(K7). Angaben zur Stufe sind Empfehlungen für die Umsetzung (K3).

**SR-00 Konzept, Spanne, Fahrplanzeile.** *Erledigt 27.09.2026:* dieses
Konzept und das Prüfdokument; Backlog-Spanne 350 bis 359; Rahmenplan
Fassung 139 (bis zum Merge von 17 als 137 gezählt; Kopf, Fahrplanzeile 18
mit Nr. 232 und 251, Verlaufszeile).
Nur `docs/`. *Gemessen:* `decken.py` 20 Decken, `uebersicht.py --pruefen`
0/0, `nummern.py` 0 (Zahlen im Prüfdokument 2).

**SR-01 Sitzungsbindung** — Nr. 242, Nr. 251. `sitzung_lib.php`: Tabelle
der Zusatzcookies (`bindung`: `secure` fest, `Strict`, `httponly`, Pfad `/`,
Sitzungsdauer) neben `SITZUNG_ARTEN`; `sitzung_binden()` (würfelt 32 Byte,
setzt das Cookie, legt `hash('sha256', …)` in `$_SESSION['bindung']`),
`sitzung_bindung_ok()`, `sitzung_bindung_loeschen()`; `lesend` auf
`secure => true`, Kommentar zu `einrichtung` (F-SR-06). `login.php`: binden
beim Anlegen von `totp_halb` und in `anmeldung_vollenden()` nach
`session_regenerate_id()`; Code- und Schlüsselschritt prüfen den halben
Stand (ohne Bindung: Stand verwerfen, Vormerkfach räumen, Hinweis wie
„abgelaufen"). `auth_guard.php`: nach `user_id` die Prüfung, Grund
`bindung` → `sitzung_beenden_passend()`. `session_lib.php`: Grund
`bindung` in `SESSION_ENDE_GRUENDE` mit **beiden** Texten (F-SR-07);
`session_verwerfen()` und `session_beenden()` löschen das Bindungscookie
mit. `tools/quelltext/sitzungshaertung.php`: `setcookie()` außerhalb der
zwei Dateien ist ein Befund (E-SR-05), Selbstprobe erweitert. **Neue
Probe** `tools/proben/sitzung/` (Anlass Nr. 242; `Pruefablauf.md` 6.12):
über HTTP anmelden (Prüfkonto mit Zweitfaktor, Code aus
`tools/zweitfaktor/totp.php`), dann dieselbe Sitzungskennung **ohne** das
Bindungscookie → 401/`login.php?ende=bindung`; halber Stand ohne Cookie →
verworfen; Abmelden räumt beide Cookies; eine Sitzungsdatei, die vor dem
Umbau angelegt wurde (nachgestellt: ohne `bindung`), endet mit Grund
`bindung`. `abmelde-probe` prüft das zweite Cookie mit. `pruefablauf.json`:
Muster `sitzung_lib.php`, `auth_guard.php`, `login.php`, `session_lib.php`
→ `sitzungsprobe`. **Doku:** `Technik.md` Sitzungsablage („Was auf dem
Spiel steht" neu gefasst — die Datei allein ist keine Sitzung mehr), 4.99q
(halber Stand gebunden), 7 (Runbook „Nach dem Ausrollen sind alle
abgemeldet" — auch mit dieser Fassung; die Ankündigung davor); Handbuch
3.1 (ein Satz: zwei Cookies, warum); Kopf von `sitzung_lib.php` (E-SR-16);
`Review-Krypto-Sicherheit.md` 3.2 bleibt (Eingang von 12, wird dort
gelesen). Backlog 242 und 251 nach `Backlog-Erledigt.md`.
*Abnahme:* Sitzungsprobe grün mit den vier Fällen; `sitzungshaertung`
0 Befunde, Selbstprobe alle Fälle; `grep -rn "setcookie(" server/
--include=*.php` nur in den zwei Dateien; Zweitfaktor- und Rückwegprobe
unverändert grün (der halbe Stand trägt jetzt eine Bindung — beide Proben
melden sich über HTTP an und müssen das Cookie mitführen); Bedienprobe
`zweitfaktor.mjs` grün; Textprobe 0 neu.
*Stufe:* Web Neben. *Fächerung:* keine.
*Erledigt 28.09.2026 (Web 21.7.0):* gebaut wie beschrieben —
`SITZUNG_COOKIES` mit `bindung` (`EDBIND`), `sitzung_cookie_setzen()`,
`sitzung_cookie_loeschen()`, `sitzung_cookie_lesen()`, `sitzung_binden()`,
`sitzung_bindung_ok()`, `sitzung_bindung_loeschen()` in `sitzung_lib.php`;
`lesend` fest `secure`, Kommentar an `einrichtung`; Grund `bindung` mit
beiden Texten; `setcookie()`-Regel (e) in der Sitzungshärtung. **Dazu,
nicht im Konzept:** die lesenden Seiten (F-SR-15, E-SR-39) und drei weitere
Proben (F-SR-16). **Abweichungen:** Die `abmelde-probe` prüft kein Cookie
(F-SR-17) — das tut die Sitzungsprobe; das Muster in `pruefablauf.json`
heißt `sitzungsbindung` und trägt `notfallblatt.php` mit. **Probleme beim
Bau:** Die Anlage fiel mit dem Ende des Hintergrundaufrufs, der sie
eingerichtet hatte (MariaDB und PHP-Server waren dessen Kinder) —
`hochfahren.sh` im Vordergrund startet sie über `setsid` dauerhaft. Die
erste Fassung der Sitzungsprobe hatte drei Fehler der Probe (GET ohne
Cookie statt POST ohne Cookie, `api/range.php` ohne Jahr,
`session_start()` nach der ersten Ausgabe) und eine grüne Zeile ohne
Gegenstand; alle vier vor dem ersten grünen Lauf behoben. Die erste
Gegenprobe der `setcookie()`-Regel war falsch gebaut (Zeile hinter `?>`
angehängt) und maß nichts; die zweite im PHP-Teil fand den Befund. Zahlen
im Prüfdokument 2.

**SR-02 Gerät merken** — Rest aus Nr. 141 (E-P5c-41); Q-SR-01, -02, -08
entschieden (H-SR-01). `schema.sql` und `migration_lib.php`: Tabelle
`vertraute_geraete` (E-SR-07), Migration `2026_..._vertraute_geraete`, ID
in die `skipped`-Liste; Stufenregel → Prüfstand `haupt`. `totp_lib.php`:
`zweitfaktor_geraet_merken($userId)` (würfelt, setzt das Cookie über die Tabelle
aus SR-01, schreibt den Hash), `zweitfaktor_geraet_erkannt($userId)` (Cookie →
Hash → Zeile, deren `angelegt_am` plus der heutigen Dauer der Rollengruppe
in der Zukunft liegt, `zuletzt_am` fortschreiben — E-SR-17),
`zweitfaktor_geraete_vergessen($userId, $weg)`, gerufen aus `totp_abschalten()`;
`totp_zustand()` liefert die Zahl; `zweitfaktor_geraet_dauer($rolle)` liest die
zwei Einstellungen (`app_state`, Schlüssel `zf_geraet_tage_user`,
`zf_geraet_tage_verwaltung`, Vorgaben 30 und 7) — 0 heißt: kein Haken, und
`zweitfaktor_geraet_erkannt()` gibt nie „erkannt". `betrieb_server.php`: neue Karte
„Anmeldung" mit zwei Wahllisten (aus, 1, 7, 14, 30, 90 Tage) und eigenem
Speichern; Protokoll `einstellung_geaendert` wie die übrigen Karten; **dazu Nr. 250
(E-SR-37): jeder POST der Seite leitet danach um** (`flash_setzen()` mit Ort,
Ton und Ergebnis, der Weg aus R4-11), nicht nur der neue. `login.php`: nach dem Passwort und vor
dem halben Stand `zweitfaktor_geraet_erkannt()` → direkt `anmeldung_vollenden()`;
im Code-Schritt der `.schalter` „Dieses Gerät n Tage merken" mit der Dauer
der eigenen Rollengruppe (nur im App-Code-Formular, E-SR-18; bei Dauer 0
gar nicht), ausgewertet nach `totp_anmeldung_pruefen()` mit `art === 'app'`. `einstellungen.php`/`zweitfaktor_teile.php`: Zeile
„Gemerkte Geräte: n" mit Knopf „Alle vergessen" (POST, `csrf_check()`);
beim Passwortwechsel `zweitfaktor_geraete_vergessen()` neben `session_epoch + 1`;
`pw_handling.php` ebenso beim Reset. `demo_lib.php`:
`demo_zweitfaktor_leeren()` räumt die Tabelle. `jobs_lib.php`: Schritt
`vertraute_geraete` in `job_aufraeumen_schritte()` (Abgelaufene); der
Riegel `jobregister` zählt ihn, `Technik.md` 4.97a nennt achtzehn Schritte.
`protokoll_lib.php`: Arten `zweitfaktor_geraet_gemerkt` (neutral),
`zweitfaktor_geraete_vergessen` (neutral, `daten.weg`). Berechtigungsmatrix
(`Technik.md` 4.99p, Rollenprobe): „Alle vergessen" nur am eigenen Konto —
die Kontoseite der Verwaltung setzt den Zweitfaktor zurück, und das vergisst
mit. **Doku:** `Technik.md` 3 (Tabelle), 4.97a, 4.99q (Absatz „Gerät
merken": Was das Cookie ist, was es nicht ist, wann es fällt), 4.99p;
`Handbuch.md` 3.1f (der Satz „gibt es nicht" fällt; Bedienung, die 30 Tage,
„Alle vergessen", was der Passwortwechsel tut), 11.1 (Zurücksetzen vergisst
mit); `Backup-Format.md` 6 (die Tabelle reist im Komplett-Stand mit, nur
Hashes) und 1 bis 5 (das Konto-Backup trägt sie **nicht** — ein Cookie
gehört zu einem Browser, nicht zu einem Konto); Rechtstexte: der Baustein
zur Datenschutzerklärung nennt das Cookie (Handbuch 11.5a — SR-02 prüft,
ob dort ein Cookie-Absatz steht, und ergänzt ihn; Zuarbeit an die
Betreiberin, den Baustein neu zu übernehmen). **Prüfmittel:**
Zweitfaktorprobe, neuer Teil: merken → abmelden → anmelden ohne Code →
„Alle vergessen" → Code wieder fällig; Passwortwechsel vergisst; Ablauf
mit gestelltem `gueltig_bis`; Wiederherstellungscode merkt nicht; fremdes
Cookie an fremdem Konto zählt nicht. Bedienweg `zweitfaktor.mjs` erweitert;
Bedienweg `betrieb_server.mjs`: nach jedem POST der Seite eine 303 mit
Flash (Nr. 250); Backlog 250 nach `Backlog-Erledigt.md`;
`migrationsregister`, `jobregister`, `schemaprobe` (haupt); Rollenprobe
für „Alle vergessen"; Bilderlauf des Code-Schritts mit Haken (P-SR-05).
Dazu die Fälle der Einstellung: Dauer 0 → kein Haken, gemerkte Geräte
gelten nicht mehr; Dauer von 30 auf 7 gesenkt → ein 10 Tage altes Gerät
gilt nicht mehr, ohne dass jemand „vergessen" drückt; Verwaltungsrolle
und NutzerIn bekommen verschiedene Texte am Haken.
*Abnahme:* Zweitfaktorprobe grün mit den neun neuen Fällen;
`uebersicht`/`bestand` grün; `update.php` auf der Sandbox legt die Tabelle
an; `SHOW CREATE TABLE vertraute_geraete` gleich Schema und Migration;
Bilderlauf der Karte „Anmeldung".
*Stufe:* Web Neben, **mit Migration** (Prüfstand `haupt`; nach dem Deploy
`update.php`). *Fächerung:* keine.
*Erledigt 28.09.2026 (Web 21.8.0):* gebaut wie beschrieben — Tabelle und
Migration `2026_09_28_vertraute_geraete` (legt auch
`app_state.notzugang_geheim` an, E-SR-24; `skip` erst, wenn beides steht),
die Helfer `zweitfaktor_geraet_*` in `totp_lib.php` mit den zwei
Einstellungen, der Haken im App-Formular, das Erkennen vor dem halben
Stand, „Alle vergessen", die Karte „Anmeldung", der Aufräumschritt
(achtzehn), zwei Protokollarten, zwei Matrixzeilen; `betrieb_server.php`
leitet nach jedem erfolgreichen POST um — eine Stelle hinter allen zehn
Zweigen, die Meldung in der Karte (Nr. 250). **Dazu, nicht im Konzept:**
Fall 8b der Zweitfaktorprobe (F-SR-22); die Karten „Protokoll" und
„Adresssuche" in der Umleitungsmessung der Rollenprobe (vier statt zwei);
`bild(name, seite)` der Bedienprobe (F-SR-21); `pw_handling.php` und
`einstellungen.php` im Muster der Zweitfaktorprobe (`pruefablauf.json`);
ein Satz zur Meldung in der Karte in Handbuch 12.5. **Abweichungen:** eine
eigene Protokollart statt `einstellung_geaendert` (F-SR-19); 302 statt 303
(F-SR-20); die Zeile „Gemerkte Geräte" steht in `einstellungen.php`, wo die
Karte gebaut wird — `zweitfaktor_teile.php` bleibt unberührt; „Ablauf mit
gestelltem `gueltig_bis`" misst die Probe mit zurückgestelltem
`angelegt_am`, weil die Tabelle nach E-SR-17 kein `gueltig_bis` hat. Der
Cookie-Absatz im Datenschutz-Baustein (Handbuch 11.5a) fehlte und steht
jetzt da; ihn zu übernehmen ist Teil des Postens „Datenschutzerklärung des
Dienstes" in Rahmenplan 6.3 (P-SR-17). **Probleme beim Bau:** Das Vergessen
stand im Entwurf hinter der Transaktion des Passworts (E-SR-40). Die neue
Matrixzeile rief `einstellungen.php` ohne `?t=profil` — ohne `t` zeigt die
Seite die Übersicht und endet vor dem POST, vier Zellen „unerwartet 200".
Der Bedienweg wartete nach dem zweiten Passwort auf `login.php` und stand
dort schon, bevor das Formular abgeschickt war — „MIT Code", obwohl die
Anlage gemerkt hatte. Zwei Riegel: „Administratorin" statt der Hausform in
einer Meldung (Wortliste) und ein `=== 'user'` über die Gruppe, das der
Rollenvergleichs-Riegel Z13 zu Recht zählt (jetzt eine Zuordnung); die
Symboltabelle in `Design.md` neu erzeugt (ein Haken mehr). Alle vor dem
ersten grünen Lauf behoben. Zahlen im Prüfdokument 2.

**SR-07 Frischer Code vor kritischen Handlungen** — Q-SR-11 (E-SR-20).
`login.php`: nach einem angenommenen App- oder Wiederherstellungscode
`$_SESSION['zf_frisch_bis'] = time() + ZF_FRISCH_S` (900); die Anmeldung
über ein gemerktes Gerät und der Rückweg setzen nichts. `auth_guard.php`:
`zweitfaktor_frisch(): bool`, `zweitfaktor_frisch_verlangen(string $handlung)`
— kehrt zurück, wenn frisch oder wenn das Konto keinen Zweitfaktor hat;
sonst für `api/` 403 JSON `zweitfaktor_frisch`, für Seiten `flash_setzen()`
mit dem Hinweis und 303 auf `zweitfaktor.php?bestaetigen=1&zurueck=<pfad>`
(nur ein relativer Pfad einer Seite dieser Anwendung, geprüft gegen eine
Liste, sonst `index.php`). Die Liste `ZF_FRISCH_HANDLUNGEN` (Konstante in
`db.php` neben den Rollen): `betrieb_server.php` alle `schluessel_*`,
`betrieb_schluesselblatt.php` (GET, vor der Ausgabe), `admin_user.php`
Rollenwechsel, `totp_zuruecksetzen`, `user_delete`, `einstellungen.php`
`totp_ausschalten`. Die Aufrufe stehen **vor** `csrf_check()` wie ein
Rollentor (E-P5c-85). `zweitfaktor.php`: der Modus `bestaetigen` zeigt das
Codefeld der Anmeldung (App-Code oder Wiederherstellungscode, `.feld-code`,
Topf `totp` mit den Merkmalen des Kontos), setzt bei Erfolg die Frist und
leitet auf `zurueck` um; Abbruch führt auf `zurueck` ohne Frist. Die Seite
ist im Wartungsmodus erreichbar (die BetreiberIn braucht sie vor den
Schlüsseln). `tools/zaehlung/register.php`: eine Zeile „frischer Code" —
jede Handlung der Liste hat genau einen Aufruf, und die Zahl der Aufrufe ist
die Länge der Liste. **Doku:** `Technik.md` 4.99q (Absatz „Frischer Code":
was frisch heißt, die Liste, warum kein Nachspielen), 4.99p (Spalte
„frischer Code" in der Matrix), 4.99d/Betrieb; `Handbuch.md` 3.1f (ein
Absatz), 11.1 (Kontoseite: der Code vor Rolle, Zurücksetzen, Löschen),
12.5 (der Code vor den Schlüssel-Griffen und dem Blatt). **Prüfmittel:**
Zweitfaktorprobe, neuer Teil: Anmeldung über gemerktes Gerät →
`betrieb_schluesselblatt.php` antwortet 303 auf `zweitfaktor.php`; Code
eingeben → 200; Frist gestellt auf abgelaufen → wieder 303; nach echtem
Code-Schritt bei der Anmeldung → sofort 200; Konto ohne Zweitfaktor → kein
Umweg; `zurueck` mit fremder Adresse → `index.php`; API-Handlung → 403 JSON.
Rollenprobe: die sechs Handlungen der Liste je einmal ohne frischen Code
(Umweg) und mit (durch). Bedienweg `betrieb_server.mjs` erweitert.
*Abnahme:* `grep -rn "zweitfaktor_frisch_verlangen(" server/` = Länge von
`ZF_FRISCH_HANDLUNGEN` (Register-Zeile Decke 0); Zweitfaktorprobe grün mit
den sieben neuen Fällen; Rollenprobe grün; Textprobe 0 neu.
*Stufe:* Web Neben. *Fächerung:* keine.
*Erledigt 28.09.2026 (Web 21.9.0):* gebaut wie beschrieben — `ZF_FRISCH_S`
und `ZF_FRISCH_HANDLUNGEN` in `db.php` (sechs Handlungen: `schluessel` für
alle sieben Griffe mit einem Aufruf, `schluesselblatt`, `rollenwechsel`,
`totp_zuruecksetzen`, `user_delete`, `totp_ausschalten`), `zweitfaktor_frisch()`
und `zweitfaktor_frisch_verlangen()` in `auth_guard.php`, dazu
`zweitfaktor_zurueck()` (die Prüfung des Rücksprungs); die Frist in
`login.php` nach App- und Wiederherstellungscode, `anmeldung_vollenden()`
räumt eine alte; der Modus `bestaetigen` in `zweitfaktor.php` (Topf `totp`,
Sperre, Protokoll bei einem Wiederherstellungscode) und die Seite in
`WARTUNG_AUSNAHMEN`; sechs Aufrufe vor dem Token; Registerzeile Z43.
**Dazu, nicht im Konzept:** das Abbruchziel (E-SR-41); die Meldung auf der
Kontoseite (F-SR-25); die vier Prüfmittel (F-SR-24); die Spalte der Matrix
misst den Umweg mit dem Konto der kleinsten Rolle und einmal den Durchlass
ohne Zweitfaktor; drei neue Matrixzeilen (Rollenwechsel, Blatt,
Ausschalten); die Runbook-Aufzählung (F-SR-27). **Abweichungen:** Z43 statt
`grep` (F-SR-26); **der Fall „API → 403 JSON" ist nicht gemessen** — keine
Handlung der Liste ist heute ein Endpunkt, der erste kommt mit SR-09
(`passkey_anlegen`), dort wird er gemessen; vor der Bestätigung steht kein
Flash, die Seite sagt selbst, warum sie fragt (ein Flash wäre eine zweite
Stelle für denselben Satz); das Ausschalten des eigenen Zweitfaktors fragt
nur, wo Ausschalten geht — eine Pflichtrolle bekäme sonst erst den Code und
dann „Pflicht". **Probleme beim Bau:** Das Abbrechen beim Blatt lief im
ersten Entwurf im Kreis (E-SR-41). Die Registerregel zählte zwei Aufrufe
auf einer Zeile doppelt (Gegenprobe 5 statt 3) und hätte die
Fortsetzungszeile `'abbruch' => …` als Eintrag gelesen — beides behoben,
Gegenproben 3 und 1. Teil 5c der Zweitfaktorprobe überschrieb den Zähler
`$gut` der Probe (Abbruch mit „Cannot increment array"). Die Rollenprobe
legte die unfrischen Sitzungen nach ihrer ersten Ausgabe an, wo
`session_start()` nicht mehr geht — 15 rote Zellen „302 → login.php"; jetzt
entstehen sie oben mit den frischen. Alle vor dem ersten grünen Lauf
behoben. Zahlen im Prüfdokument 2.

**SR-09 Passkeys als zweiter Faktor** — Nr. 350; Nachfassung vom
27.09.2026 (E-SR-29 bis -34); Q-SR-12, -13 entschieden (H-SR-07).
`schema.sql` und `migration_lib.php`: Tabelle `passkeys` (`id`, `user_id`
mit Kaskade, `credential_id` Base64url eindeutig, `oeffentlich` SPKI als
PEM, `alg` (-7 ES256, -257 RS256), `zaehler`, `bezeichnung` bis 40 Zeichen
oder leer, `angelegt_am`, `zuletzt_am`), Migration `2026_..._passkeys`, ID
in die `skipped`-Liste; Stufenregel → Prüfstand `haupt`. **Neu
`passkey_lib.php`** (die eine Stelle für WebAuthn, R83): `pk_cbor_lesen()`
(nur, was Registrierung und COSE brauchen — Ganzzahlen, Byte- und
Textketten, Listen und Karten bestimmter Länge; Fließzahlen, Marken,
unbestimmte Längen und Verschachtelung über acht sind eine Ablehnung),
`pk_registrierung_pruefen($ablage, $antwort)` (`clientDataJSON`: `type`
`webauthn.create`, `challenge` gleich der gestellten, `origin` gleich dem
eigenen; `attestationObject`: `authData` mit `rpIdHash` gleich
`sha256(Host)`, Flags UP und AT gesetzt, Zähler, `credentialId`,
COSE-Schlüssel nur EC2/P-256 mit `alg` -7 oder RSA mit `alg` -257; `fmt`
wird gelesen und **nicht** geprüft — E-SR-30), `pk_anmeldung_pruefen($halb,
$antwort)` (`type` `webauthn.get`, Herausforderung, Ursprung, `rpIdHash`,
UP, Signatur über `authData ‖ sha256(clientDataJSON)` mit phpseclib —
`Crypt/EC` wie `rw_pruefen()`, `Crypt/RSA` PKCS#1 v1.5 mit SHA-256 —,
Zähler nach E-SR-33), `pk_liste($userId)`, `pk_anlegen()`,
`pk_entfernen($userId, $id)`, `pk_alle_entfernen($userId, $weg)` — gerufen
aus `totp_abschalten()` (jeder Weg, wie die Geräte aus SR-02);
`pk_herausforderung_stellen(array &$ablage)` nach dem Muster von
`rw_herausforderung_stellen()`; `pk_ursprung()` nimmt die Stelle in
`kopfzeilen_lib.php`, die den Host schon liest (`https_tor()`-Umfeld),
statt eine zweite zu bauen. Der öffentliche Schlüssel wird bei der
Registrierung aus COSE in ein phpseclib-Objekt überführt und als SPKI
gespeichert — beim Anmelden lädt `PublicKeyLoader` ohne CBOR.
`einstellungen.php`/`zweitfaktor_teile.php`: in der Karte „Zweitfaktor"
der Abschnitt „Passkeys" (nur bei eingeschaltetem Zweitfaktor, E-SR-29):
Liste (`bezeichnung` oder „Passkey vom <Datum>", angelegt, zuletzt), je
Zeile „Entfernen" (POST, `csrf_check()`, davor
`zweitfaktor_frisch_verlangen('passkey_entfernen')`), Knopf „Passkey
hinzufügen" mit Feld „Bezeichnung (optional)" — **nur wenn
`zweitfaktor_frisch()`**, sonst der Verweis „Zuerst Code bestätigen" auf
`zweitfaktor.php?bestaetigen=1&zurueck=…` (E-SR-31); die Herausforderung
liegt als `data-`-Attribut am Abschnitt und in `$_SESSION['passkey_reg']`
(zehn Minuten, einmal gültig). **Neu `api/passkey_anlegen.php`** (POST JSON
über `EdApi.postJson()`, `zweitfaktor_frisch_verlangen('passkey_anlegen')`
→ 403 JSON, Prüfung, Zeile, Protokoll, Mail; der elfte wird abgelehnt).
**Neu `assets/passkey.js`** (ohne Fremdbestandteil): `anlegen()` baut
`PublicKeyCredentialCreationOptions` (`rp.id` Host, `user.id` Kontonummer
als Bytes, `pubKeyCredParams` -7 und -257, `userVerification: 'preferred'`,
`residentKey: 'preferred'`, `attestation: 'none'`, `excludeCredentials` die
vorhandenen) und schickt `clientDataJSON`, `attestationObject`, `rawId`
Base64url; `bestaetigen()` baut `PublicKeyCredentialRequestOptions`
(`allowCredentials` aus dem Markup, `userVerification: 'preferred'`), trägt
die Antwort (`authenticatorData`, `clientDataJSON`, `signature`, `rawId`)
in ein verstecktes Feld `passkey_antwort` und schickt das Formular ab; ohne
`PublicKeyCredential` im Browser bleibt der Knopf verborgen. `login.php`: im
Code-Schritt, wenn das Konto Passkeys hat, über dem Codefeld der Knopf „Mit
Passkey bestätigen" (E-SR-32; Herausforderung in `totp_halb['passkey']` wie
die des Rückwegs, `allowCredentials` als `data`-Attribut); POST mit
`passkey_antwort` → `pk_anmeldung_pruefen()` im Topf `totp` →
`anmeldung_vollenden()`, `zuletzt_am`; **zählt wie ein App-Code**: Haken
„Gerät merken" (E-SR-18), `zf_frisch_bis` (E-SR-20).
`zweitfaktor.php?bestaetigen=1` (SR-07): der Knopf ebenso. `admin_user.php`:
„Zweitfaktor zurücksetzen" nimmt die Passkeys über `totp_abschalten()`
mit, nichts Eigenes; die Kontoseite zeigt die Zahl. `demo_lib.php`:
`demo_zweitfaktor_leeren()` räumt die Tabelle. `db.php`:
`ZF_FRISCH_HANDLUNGEN` um `passkey_anlegen`, `passkey_entfernen`
(Register-Zeile aus SR-07 zählt mit). `protokoll_lib.php`: `passkey_angelegt`
(neutral), `passkey_entfernt` (neutral, `daten.weg`), `passkey_zaehler`
(orange). `mail_lib.php`: Vorlagen `passkey_angelegt`, `passkey_entfernt`
nach dem Muster `rueckweg_erneuert`. `totp_lib.php`: die Helfer aus SR-02
tragen die Namen aus E-SR-34. **Doku:** `Technik.md` 3 (Tabelle), 4.99q
(Absatz „Passkeys": beide Zeremonien, was geprüft wird und was nicht —
Attestation —, Speicherform, Zähler, warum kein Fremdbestandteil, warum
kein PRF — E-SR-28), 4.99p (zwei Handlungen mehr in der Spalte „frischer
Code", der Endpunkt in der Matrix); `Handbuch.md` 3.1f (Abschnitt
„Passkeys": was, für wen, hinzufügen, anmelden, entfernen; ein Passkey gilt
nur für die Adresse der Anlage — Staging und Produktiv sind zwei; Ausschalten
und Zurücksetzen nehmen die Passkeys mit), 11.1 (Kontoseite: Zahl), 11.5a
(Datenschutz-Baustein: öffentlicher Schlüssel und Kennung als gespeicherte
Daten — Zuarbeit wie in SR-02); `Backup-Format.md` 6 (die Tabelle reist im
Komplett-Stand mit) und 5 (das Konto-Backup trägt sie **nicht**: ein
Passkey gilt nur für den Ursprung, an dem er entstand); `Lizenzen.md` 3
(phpseclib: dritter Verwender — `Crypt/RSA` und `Crypt/EC` für WebAuthn);
`Vorbereitung-Sicherheitspaket.md` SP-11 (der Satz „brauchen eine
WebAuthn-Serverbibliothek" bekommt den Stand, F-SR-13). Backlog 350 nach
`Backlog-Erledigt.md`. **Prüfmittel:** neue Probe
`tools/proben/passkey/probe.php` (Anlass Nr. 350) **ohne Browser**: sie
erzeugt mit phpseclib je ein EC- und ein RSA-Paar, baut daraus gültige
`attestationObject`/`clientDataJSON` und Assertions selbst und misst
`passkey_lib.php` — ES256 durch, RS256 durch; abgelehnt: falscher `origin`,
falscher `rpIdHash`, fremde Herausforderung, `type` vertauscht, UP nicht
gesetzt, Zähler zurück (mit Protokolleintrag), unbekannte Kennung, `alg`
-8, `kty` fremd, CBOR abgeschnitten, Fließzahl, unbestimmte Länge,
Verschachtelung neun, der elfte Passkey; `totp_abschalten()` räumt die
Tabelle. Bedienweg `einstellungen_profil_passkey.mjs` (neu; **nur
Chromium** — CDP `WebAuthn.enable`, `WebAuthn.addVirtualAuthenticator`
`ctap2`/`internal` mit Nutzerprüfung; unter `--motor firefox|webkit`
meldet der Weg „nicht gemessen", nicht grün): Code bestätigen → hinzufügen
mit Bezeichnung → Liste zeigt eine Zeile → abmelden → anmelden → „Mit
Passkey bestätigen" → angemeldet, Haken gemerkt zählt → Blatt ohne neuen
Code (frisch) → Entfernen → Anmeldung wieder mit Code; ohne frischen Code
ist der Knopf ein Verweis. Zweitfaktorprobe: Passkey zählt für Merken und
Frische; Rollenprobe: der Endpunkt ohne Rolle und ohne Frische (403);
`cspprobe` (kein Inline-Skript); `schemaprobe`, `migrationsregister`
(haupt); Textprobe; Bilderlauf der Karte mit Liste (P-SR-05).
*Abnahme:* Passkeyprobe grün mit allen Fällen (Zahl im Prüfdokument);
Bedienweg grün; `grep -rn "zweitfaktor_frisch_verlangen(" server/` = Länge
der Liste (zwei mehr als nach SR-07); `SHOW CREATE TABLE passkeys` gleich
Schema und Migration; `sha256sum -c phpseclib3.sha256` unverändert 338
Dateien (kein neuer Fremdcode); Textprobe 0 neu.
*Stufe:* Web Neben, **mit Migration** (Prüfstand `haupt`; nach dem Deploy
`update.php`). *Fächerung:* keine.
*Erledigt 28.09.2026 (Web 21.10.0):* gebaut wie beschrieben —
`passkey_lib.php` (CBOR-Leser mit Tiefe acht, `pk_cose_laden()` →
phpseclib-JWK → SPKI, beide Zeremonien, Zählerregel, `pk_liste()`,
`pk_anlegen()` unter `FOR UPDATE` auf der Kontozeile, `pk_entfernen()`,
`pk_alle_entfernen()` aus `totp_abschalten()`), Tabelle und Migration
`2026_09_28_passkeys` (`credential_id` `VARCHAR(1364)` `ascii_bin`: 1023
Byte als Base64url), der Abschnitt in der Karte, `api/passkey_anlegen.php`,
`assets/passkey.js`, der Knopf in `login.php` und auf
`zweitfaktor.php?bestaetigen=1` (Herausforderung dort in
`$_SESSION['passkey_best']`), `demo_zweitfaktor_leeren()`, die Zahl auf der
Kontoseite, drei Protokollarten, zwei Mailvorlagen, zwei Einträge in
`ZF_FRISCH_HANDLUNGEN` (acht Aufrufe für acht Handlungen, Z43).
**Dazu, nicht im Konzept:** der Bauhelfer `tools/proben/passkey/bauen.php`
(ein Authenticator zum Nachbauen, von Passkeyprobe und Zweitfaktorprobe
geteilt); Teil 5d der Zweitfaktorprobe misst auch den Endpunkt über HTTP
(anlegen mit frischem Code samt Protokoll und Mail, dieselbe Registrierung
noch einmal → 400); die Rollenprobe erwartet an einem Endpunkt der Liste
403 JSON statt der Umleitung; der Datenschutz-Baustein 11.5a bekommt einen
dritten Absatz; F-SR-28 bis -32. **Abweichungen:** Ursprung aus
`app.base_url` (E-SR-42 — zur Bestätigung); `pk_anmeldung_pruefen($userId,
$ablage, $antwort)` statt `($halb, $antwort)` — die Ablage ist der halbe
Stand **oder** die Bestätigung, das Konto kommt getrennt; kein Schloss an
den Knöpfen (E-SR-43); die Bilder der Karte kommen aus dem Bedienweg, nicht
aus dem Bilderlauf (F-SR-29); `Backup-Format.md` 5 bekommt den Satz über
Abschnitt 4 wie bei SR-02, nicht einen eigenen. **Der Fall „API → 403
JSON" aus SR-07 ist jetzt gemessen** (Rollenprobe und Teil 5d).
**Probleme beim Bau:** Die Spalte hieß im ersten Entwurf `kennung` und im
Konzept `credential_id` — auf das Konzept umgestellt. Ein
`https://host:443` in `base_url` hätte jeden Vergleich gebrochen, weil der
Browser den Standardport weglässt — `pk_ursprung()` lässt ihn jetzt auch
weg. `app_url()` merkt sich die Adresse je Prozess; die Adressfälle der
Passkeyprobe laufen deshalb in einem eigenen PHP-Prozess. Der erste Einbau
in `login.php` setzte den Erfolgszweig in ein `elseif` hinter die
Ratenprüfung — umgebaut, bevor er lief. Die Gegenproben: Tor im Endpunkt
herausgenommen → die Rollenprobe rot (1 von 530); „Gerät merken" nur nach
App-Code → Teil 5d rot (Gerät nicht gemerkt). Beim Gegenlesen vor dem
Prüfstand war Teil 5d in einem von zwei Läufen rot — die Wartezeit nach dem
Stellen der Adresse war zu kurz (F-SR-31). Der erste Prüfstand war rot an
der Mailprobe (F-SR-32). Zahlen im Prüfdokument 2.

**H-SR-08 Gegenlesung und Behebung** (kein Paket, ein Halt; E-SR-36).
*Erledigt 29.09.2026 (Web 21.11.0):* gelesen als lesender Fable-Workflow
(E-SR-44) am Stand `dca31f1`, 41 Befunde (F-SR-33 bis -44), alle behoben
oder als bewusst benannt; die Betreiberin hat E-SR-42 bestätigt und Q-SR-14
bis -18 beantwortet (E-SR-45 bis -49). **Gebaut:** in `passkey_lib.php` die
RSA-Grenzen, der strengere Leser (`pk_cbor_art()`, Labels, Textschlüssel,
Tiefe, `pk_b64u_lesen()` kanonisch, `pk_feld()`, x und y unter p, `BS`/`BE`,
ASN.1-Vorprüfung, `crossOrigin`/`topOrigin`, Herausforderung ≥ 43 Zeichen),
`PkFehler` mit Code und `art` in der Antwort der Anmeldung, der atomare
Zähler, `pk_zaehler_warnen()`, `rp_id` und `credential_hash` in Registrierung,
Suche, Liste (`hier`), Zahl (`pk_zahl($id, $alle)`) und Grenze,
`pk_ablage_passt()` für alle drei Ablagen, `pk_bezeichnung_saeubern()`,
`pk_anlegen()` mit `totp_seit` unter Sperre und `23000` → `vorhanden`,
`pk_alle_entfernen()` mit `weg` `einrichtung`; Tabelle und Migration
(`credential_hash`, `rp_id`, `gewarnt_am`, Index `idx_konto (user_id,
rp_id)`) an Ort und Stelle (E-SR-51); `totp_abschalten()` mit Geräten und
Passkeys in der Transaktion, `totp_einrichtung_beginnen()` räumt verwaiste;
`login.php` und `zweitfaktor.php` mit Deckel, Konto an der Ablage, Zählung
nur bei `pruefung` und drei Meldungen (abgelaufen, abgewiesen, nicht
angenommen); `api/passkey_anlegen.php` mit Deckel, Bezeichnung vor der
Entnahme und `$abweisen` (400 „nicht angenommen", Protokoll
`passkey_abgewiesen`); die Karte mit fremden Zeilen; `passkey.js` mit
geprüfter Bezeichnung und dem dritten Fall im Text; Mailvorlage
`passkey_zaehler`, der Passkey-Satz in `totp_zurueckgesetzt`, die zwei
Rückfragen; der Kopf der Bibliothek mit „Was bewusst so bleibt" und der
berichtigten Begründung von E-SR-42. **Prüfmittel:** Passkeyprobe mit
Grund je Ablehnung (104 nach der Behebung, 122 nach der Nachprüfung, vorher
57), Teil 5d der Zweitfaktorprobe mit drei Fällen mehr (und nach der
Nachprüfung noch einmal zwei, zwei geschärft), der Bauhelfer mit Option `client`. **Abweichungen vom Plan,
den die Betreiberin bestätigt hat:** Web **21.11.0** statt 21.10.1 — mit
E-SR-46 (Mail) und E-SR-48 (Adresse je Zeile) kamen neue Funktionen dazu;
**zwölf** Befunde statt elf (F-SR-44 für die Texte eigens). **M13 nicht
gebaut** (keine zweite Adresse, E-SR-42). **Die zwei Abweichungen aus M35**
bleiben, wie sie sind, und stehen jetzt hier: Der Abschnitt „Passkeys"
steht nur in `einstellungen.php` (die Karte hat keine zweite Stelle), und
`passkey.js` bindet Klick-Handler an `data-`-Merkmale statt zwei Funktionen
`anlegen()`/`bestaetigen()` auszuliefern — die CSP verbietet Inline-Aufrufe
ohnehin. **Probleme beim Bau:** `fetchColumn()` liefert bei fehlender
Kontozeile `false`, nicht `null` — `pk_anlegen()` prüft beides. Ein
Probenfall „abgeschnitten in einer Liste" war falsch gebaut (`82 01`
scheiterte schon an „Liste länger als der Rest") — jetzt `81 19 01`. Zwei
Gegenproben maßen zuerst nichts: Die Mutation am Exponenten ließ die
Prüfung stehen (`false && A || B`), und der Tagesdeckel blieb grün, weil
beide Fälle in derselben Sekunde liefen und das `UPDATE` nichts änderte —
die Probe stellt `gewarnt_am` vor dem zweiten Fall eine Stunde zurück.
**Die Nachprüfung** (E-SR-44; fünf Leser, fünf Gegenprüfer) fand 23 weitere
Befunde an den geänderten Stellen (F-SR-45 bis -52); eingearbeitet sind
`pk_es256_form()` und die RS256-Länge, die kürzeste Form und die Grenzen in
`pk_spki_laden()`, der Felddeckel auf das Byte, `attStmt` als Karte, die
IDN-Umschrift ohne Übergangsregeln, das Formularfeld als Text, der Endpunkt
ohne Protokoll bei abgelaufener Herausforderung und `passkey.js` mit
gesperrtem Knopf, `daten.weg` am Zähler-Eintrag, die Texte; die Proben um
18 und 2 Fälle (zwei bestehende geschärft), darunter die Atomarität von `totp_abschalten()` mit einem
Auslöser. F-SR-50 ist Nr. 354 (E-SR-53). **Eine dritte Lesung gab es
zunächst nicht** — die Einarbeitung belegten die Proben und zehn
Gegenproben; nachgeholt am selben Tag (E-SR-57, Nachtrag nach SR-05).
Zahlen im Prüfdokument 2.

**SR-05 `ingest.php` ohne Deadlock** — Nr. 210 (E-SR-12). `ingest.php`:
Schleife um `beginTransaction()`/`try` (höchstens drei Anläufe, Abstand
`random_int(50, 200)` ms), Zurücksetzen der Antwortfelder am Anlaufbeginn
(`$stored`, `$behalten`, der Sammler `$pruef` — die Umsetzung zählt die
Variablen, die der Rumpf befüllt und die Antwort liest, und setzt genau
diese zurück); die frühen `commit()` der Dublettenzweige bleiben (sie
beenden die Anfrage); `dt_zeitraum_fortschreiben()` hinter den Commit als
eigene Anweisung im selben Wiederholungsrahmen (das `if` mit
`ingest_tag_offen()` wandert mit). `transaktion_lib.php`: der Satz
„bekommt in Schritt 18 eine Deadlock-Behandlung" wird zur Gegenwart; ob
der Rahmen als `db_transaktion_wiederholt()` dort steht oder in
`ingest.php` bleibt, entscheidet die Umsetzung — Empfehlung: **in
`ingest.php`**, solange es den einen Verbraucher gibt (R83: zentralisiert
wird beim zweiten). `wartung_lib.php`: der Satz im Kopf von
`gedraengel_vermerken()` („Die eigentliche Abhilfe steht aus") fällt; das
Gedrängel wird weiter vermerkt, aber erst nach dem letzten Anlauf.
`pruefablauf.json`: `ingest.php` → zusätzlich `verbindungsprobe` in klein
(F-SR-08), Begründung im Anlass. **Doku:** `Technik.md` 5e.1 (Abhilfe
gebaut, was wiederholt wird, was nicht), 4.97b? — nein: der Weg durch
`ingest.php` (Abschnitt „Der Weg durch `ingest.php`") nennt die Schleife;
`JSON-Vertrag.md` 5 unverändert (503 bleibt möglich, und die Regel „später
unverändert erneut" bleibt die des Clients — ein Satz dazu, dass der Server
selbst wiederholt). Backlog 210 nach `Backlog-Erledigt.md`.
*Abnahme:* `php tools/proben/verbindung/probe.php --frei 20` → **0 × 503,
20 von 20 Paketen da, 0 Gedrängel im Fehlerprotokoll** (der Sollwert aus
Nr. 210); dazu einmal mit `--frei 1` gegen die Verbindungsgrenze → 503
wie bisher (die Schleife wiederholt kein 1226); Ingestprobe unverändert
grün; Textprobe 0 neu.
*Stufe:* Web Korrektur. *Fächerung:* keine.
*Erledigt 29.09.2026 (Web 21.11.1):* gebaut wie beschrieben — die Schleife
um `beginTransaction()`/`try` in `ingest.php` (`INGEST_ANLAEUFE` = 3,
`gedraengel_abstand()` 50–200 ms), am Anlaufbeginn zurückgesetzt: die
Prüfliste aus einem Abzug vor der Transaktion (`clone`), `$behalten`,
`$einsatzNeu`, `$stored`, `$verworfen`, `$gesperrt` — **sechs** Variablen,
gezählt an dem, was der Rumpf nur ergänzt und die Antwort liest; alles
andere, was die Antwort liest, setzt der Rumpf auf jedem Weg neu. Die
frühen `commit()` der Dublettenzweige bleiben. `dt_zeitraum_fortschreiben()`
samt `ingest_tag_offen()` **und `last_seen`** (E-SR-54) hinter dem Commit,
mit eigenem Rahmen; `ingest_scheitern()` als eine Stelle für 503 und 500
(das Gedrängel erst nach dem letzten Anlauf im Protokoll). Wiederholt wird
nur, was nicht bestätigt ist (`$bestaetigt`); der Vermerk der Demo-Änderung
steht weiter unmittelbar hinter dem Commit — die Zweitfaktorprobe prüft das
an der Zeile davor. `transaktion_lib.php` und `wartung_lib.php` sprechen in
der Gegenwart, das Register Z16 auch. **Dazu, nicht im Konzept:** die Geräte-
Zeile (F-SR-53), zwei Lagen der Verbindungsprobe (F-SR-54), Teil 12 der
Ingestprobe (F-SR-55), Z33 (F-SR-56). **Abweichung:** Die Schleife steht in
`Technik.md` 5e.1, nicht im Abschnitt „Der Weg durch `ingest.php`"
(F-SR-57). **Probleme beim Bau:** Die Maschine startete zweimal neu (vor dem
Commit von H-SR-08 und danach) — Anlage und Plattform jeweils neu
hochgefahren, nichts verloren. Die zwei `usleep()` des ersten Einbaus
rissen Z33. Zahlen im Prüfdokument 2.

**H-SR-08, Nachtrag: die offenen Punkte gemessen** — erledigt 29.09.2026
(Web 21.11.2; E-SR-56 bis -59, F-SR-58 bis -67). Nach 21.11.0 standen vier
Punkte als „nur gelesen" am Anfang des Prüfdokuments; die Betreiberin wollte
sie gemessen (E-SR-57). **Die dritte Lesung** (Fable, drei Leser nach den
Blöcken der Nachprüfung, drei Gegenprüfer, lesend, ohne Last) fand an der
Einarbeitung zwölf Meldungen, elf bestätigt, eine widerlegt (3B-3: die
Lücke stand schon im Prüfdokument); keine mittel, eine niedrig (C3-1). Von
den 23 geprüften Einarbeitungen: 19 behoben, 4 teilweise (A-4,
B-4/E-4, D-3, der Rest von N1-2) — die vier sind F-SR-58, -61, -63, -60.
**Der Zähler unter echtem Wettlauf:** ein Fall der Passkeyprobe mit zwei
Prozessen und einer dritten Verbindung, die beide am `UPDATE` festhält
(F-SR-60). **Nr. 354:** mit einem echten Deadlock nachgestellt (eine zweite,
schwerere Transaktion sperrt die Lücke hinter der letzten Protokollzeile,
dann die Kontozeile; InnoDB opfert `totp_abschalten()`): Faktor an, Codes
und Geräte zurück, Passkeys gelöscht samt Eintrag, `commit()` wirft „There
is no active transaction", keine Mail — zweimal, gleich (F-SR-63, E-SR-56).
**Der Knopf:** Schritt 1b und 1c des Bedienwegs `einstellungen-profil-passkey`
(verdrängte Herausforderung, gerissenes Netz). Im Code: `pk_spki_laden()`
mit allen Grenzen (F-SR-59), `passkey.js` mit „Bitte die Seite neu laden."
nach dem Absenden (F-SR-62, E-SR-58). Dazu die Wege in `ap6.mjs`, die auf
die Antwort warten (F-SR-66), und die Texte zu Teil 12 (F-SR-67). **Probleme
beim Bau:** Die Gegenprobe zum Wettlauf blieb zuerst grün — beide Prozesse
schrieben Zähler 6 in derselben Sekunde, MariaDB zählt eine unveränderte
Zeile nicht, und `rowCount()` 0 machte die zweite auch ohne Bedingung zur
Kopie; mit 6 und 7 ist sie rot (Zähler danach 6 — ein verlorenes Update).
Der erste 81-L-Fall baute eine Signatur von 72 Byte und hätte einen Deckel
von 72 nicht gefunden; er signiert jetzt, bis er 73 Byte hat. Und PHP machte
aus dem Schlüssel `'81'` eine Zahl — die Probe brach mit einem `TypeError`
ab; die zwei Zeilen im Reiter System (dazu eine eigene Abfrage mit falschem
Spaltennamen) sind von Hand gelöscht. Der Bedienweg maß zweimal nichts:
Schritt 1b lief zuerst nach dem gültigen Anlegen, und der Authenticator
verweigerte einen zweiten Passkey, weil die Seite den ersten ausschließt
(`excludeCredentials`) — der Server wurde nie gefragt; 1b und 1c laufen
jetzt davor. Danach las der Weg den Hinweis „Der Browser fragt jetzt …"
statt der Meldung danach. Zur Nachstellung von F-SR-66 stellte ein
Vorschritt den Tag auf „Anderes Rettungsmittel": die alte `ap6.mjs` lief
in dieselbe Zeitüberschreitung wie der rote Lauf, die neue bricht mit dem
Satz ab. Zahlen im Prüfdokument 2.

**SR-03 Serverschlüssel wechseln als Vorgang** — Nr. 247, Nr. 233; Q-SR-03,
-04, -09 entschieden (H-SR-02). `serverkrypto_lib.php`: `server_key_alt`
(`CONFIG_SCHREIBBAR`, `serverschluessel_alt()`, `config_gemerktes_verwerfen()`),
`sk_oeffnen()` zwei Schlüssel, `serverschluessel_zustand()` mit `rotation`
und `kennung_alt`, `serverschluessel_wechseln()` (alt sichern, neu würfeln,
Marke auf neu, Blatt-Marke zurücksetzen E-SR-11, Job anstoßen, Protokoll,
Mail), `serverschluessel_alt_entfernen()` mit den drei Bedingungen aus
E-SR-09 als Riegel in der Funktion. **Neu `schluesselwechsel_lib.php`:**
`sw_inventar()` (die vier umhüllbaren Zwecke, Zählung je Zweck),
`sw_haeppchen()` (je Aufruf so viele Stücke, wie `zeitLinks` zulässt;
Zeilen in `backup_targets` und `users`, Dateien der Adminpakete samt
`konto.json`, Protokoll-Archive mit neuem Namen — danach gilt die Datei
als „nur lokal" und der Versand schickt sie neu, so bekommt das Ziel eine
Kopie unter dem neuen Schlüssel), `sw_nachweis()` (jedes Stück nach dem
Ersetzen mit dem neuen Schlüssel geöffnet), Zustand in `jobs.zustand`,
Wiederanlauf nach Abbruch (ein halb geschriebenes Stück wird verworfen —
Nebendatei, `rename`). `jobs_lib.php`: Job `schluesselwechsel` im Katalog
(läuft nur bei Lage `rotation`, sonst `fertig`), Rückstand = offene
Stücke. `betrieb_server.php`: Karte „Schlüssel des Servers", Serverschlüssel
in der Lage `rotation`: neue und bisherige Kennung, „noch n von m Stücken",
Knöpfe „Jetzt weiterarbeiten" und — sobald die drei Bedingungen stehen —
„Alten Schlüssel entfernen"; der Knopf „Serverschlüssel wechseln" nur in
`bereit`, mit Ankreuzfeld wie beim Anteil; **vor jedem `schluessel_*`-Griff
der frische Code** (SR-07, E-SR-20) — keine zweite Passwortabfrage. `betrieb_schluesselblatt.php`:
Kachel „Serverschlüssel (bisheriger)" mit dem Satz aus E-SR-10; die Zeile
„Wann neu" unterscheidet Anteil und Serverschlüssel. `status_lib.php`:
Zeile Serverschlüssel kennt `rotation` (blau mit Zahl). `einstieg_lib.php`:
`blatt_neu_faellig()` (Marke löschen), gerufen aus beiden Rotationen;
`rueckfrage_dialog.php`/Blatt-Dialog: der Satz voran während einer Rotation
(E-SR-11). `komplett_lib.php`: Öffnen mit neu, dann alt; die Liste der
Stände zeigt „anderer Schlüssel", wenn keiner öffnet (F-SR-11; Kopffeld
`kennung` für neue Stände nach Entscheidung der Umsetzung, dann
`Backup-Format.md` 6.3). `adminbackup_lib.php`: **Nr. 344 (E-SR-37)** —
`edbak_freigabe_widerrufen()` und `edbak_begleit_schreiben()` verlangen
`edbak_kennung_gueltig()` wie `edbak_ordner_loeschen()`, die Aufrufer
melden den Fehlschlag; sonst keine Änderung an der Logik;
`sicherungsziel_lib.php`, `protokoll_archiv_lib.php`: keine Änderung — sie
rufen `sk_oeffnen()`; `protokoll_archive()` vergleicht die Kennung im Namen
weiter mit der **aktuellen** (ein Archiv unter dem alten Namen ist nach dem
Umhüllen umbenannt). `config.example.php`: `server_key_alt` als Kommentar
wie `kdf_anteil_alt`. `protokoll_lib.php`: Arten `serverschluessel_gewechselt`
(orange), `serverschluessel_umgehuellt` (blau, mit Zahlen),
`serverschluessel_alt_entfernt` (neutral). `mail_lib.php`: Vorlage
`serverschluessel_gewechselt` (Q-SR-04). **Doku:** `Technik.md` 4.97c
(sechs Zwecke — F-SR-01; die Rotation: Lagen, Job, Nachweis, was nicht
umgehüllt wird), 4.97d (welcher Schlüssel welchen Stand öffnet), 7
(Runbook: „Serverschlüssel wechseln" neu mit Auslöser, Ablauf, Dauer und
der Blatt-Regel aus E-SR-10; „Wiederanlauf" Schritt 3 präzisiert; die
veraltete Zeile aus F-SR-03), 4.99o (Rückfrage nach einer Rotation);
`Handbuch.md` 12.5 (Karte: Lage `rotation` für den Serverschlüssel, der
Vorgang in fünf Sätzen, die Blatt-Regel), 12.7 (Backup-Ziele: alte Kopien),
11.4a/Protokoll (drei Arten); `Backup-Format.md` 5, 6, 7 (je ein Absatz:
Schlüsselwechsel, was danach womit öffnet); Schlüsselblatt-Text; Kopf von
`serverkrypto_lib.php` („Was passiert, wenn er sich ändert" wird zur
Rotation). Backlog 247, 233 und 344 nach `Backlog-Erledigt.md`. **Prüfmittel:**
neue Probe `tools/proben/schluesselwechsel/` (Anlass Nr. 247): auf der
Sandbox je Zweck ein Stück anlegen (ein Ziel mit Passwort, ein Konto mit
Zweitfaktor, ein Adminpaket, ein Protokoll-Archiv, ein Komplett-Stand),
den Wechsel starten, Häppchen bis fertig, **jedes Stück mit dem neuen
Schlüssel öffnen und mit dem Klartext davor vergleichen**, den alten
Komplett-Stand mit dem alten Schlüssel öffnen (bleibt), „Alten Schlüssel
entfernen" vor dem frischen Stand → verweigert mit Grund, nach dem
frischen Stand → `config.php` ohne `server_key_alt`, danach öffnet alles
außer dem alten Stand; Blatt zeigt während der Rotation drei Kachelnamen;
Rückfrage fällig nach dem Start; Protokoll drei Arten; Abbruch mitten im
Häppchen und Wiederanlauf (kein halbes Stück). Dazu `anteilprobe`
(Anteil-Rotation setzt die Blatt-Marke zurück), `freigabeprobe`,
`komplettprobe`, `versandprobe` (Archiv unter neuem Namen geht hinaus),
`protokollprobe`, `zweitfaktorprobe` (Code-Anmeldung während und nach der
Rotation) unverändert grün; `freigabeprobe`, neuer Fall: POST `widerrufen`
mit einem Handgriff aus Nullen → Fehlermeldung, keine Datei in der Wurzel
der Ablage (Nr. 344); `jobregister`, `installweiche`, Bilderlauf der
Karte in drei Lagen (P-SR-05).
*Abnahme:* Schlüsselwechselprobe grün mit allen Fällen; `grep -rn
"sk_versiegeln(" server/` = die Zwecke im Inventar (Zahl im Prüfdokument);
Sechs-Zwecke-Tabelle in `Technik.md` gleich der Liste im Code; die Karte
in drei Lagen im Bilderlauf.
*Stufe:* Web Neben (keine Migration; `config.php` bekommt einen
Kommentareintrag mehr). *Fächerung:* keine — ein Job, ein Changelog-Ton.

*Erledigt 29.09.2026 (Web 21.12.0):* gebaut wie beschrieben, mit den
Antworten der Betreiberin vom selben Tag (E-SR-60 bis -63) und sieben
Entscheidungen der Umsetzung (E-SR-64 bis -70). **Kern:**
`server_key_alt` in `CONFIG_SCHREIBBAR`, `serverschluessel_alt()`,
`sk_oeffnen_mit_wem()` (neu, dann alt; `sk_oeffnen()` ruft es), die Lage
`rotation` mit wandernder Marke, `serverschluessel_wechseln()` mit Haken und
beiden Riegeln, `serverschluessel_alt_entfernen()` mit den drei Bedingungen
als Riegel; `anteil_wechseln()` mit Riegel und Blatt-Marke.
**`schluesselwechsel_lib.php`** (neu): Inventar aus den vier Zwecken, je
Stück umsiegeln und mit dem neuen nachweisen, Zeilen bedingt (E-SR-70),
Dateien über den Arbeitsordner (F-SR-69), Archive unter neuem Namen samt
Wiederanlauf (F-SR-71), Nachweis-Durchgang, Anstoß des Komplett-Stands
(E-SR-68), Bedingungen, Mail (`mail_betreiberinnen()`). Job
`schluesselwechsel` vor `versand`; „Jetzt weiterarbeiten" über den Rahmen
(E-SR-65). **Komplett:** `kennung` im Kopf (E-SR-67),
`komp_serverschluessel_fuer()` für Liste, Download und Einspielen.
**Oberfläche:** Karte mit Lage „Wechsel", Zeilen „Umhüllung", „Bevor der
bisherige gehen darf", „Mit keinem der beiden zu öffnen"; Abschnitt
„Serverschlüssel wechseln" in `.listen-form` (F-SR-73); Blatt mit Kachel
„Serverschlüssel (bisheriger)"; Satz im Blatt-Dialog; Statuszeile;
Plaketten der Komplett-Liste; drei Protokollarten, eine Mailvorlage.
**Nr. 344** in beiden Funktionen. **Prüfmittel:** Schlüsselwechselprobe
(40, A → B und zurück), Anteilprobe Teil E, Bedienweg
`betrieb-server-schluesselwechsel` (Blatt als PDF: eine Seite). **Nicht im
Konzept, dazu:** die Kennung im Kopf entschieden (F-SR-11 → E-SR-67), der
Anstoß nach dem Nachweis statt beim Beginn (F-SR-74), `Technik.md` 4.97a
mit dem Auslöser `seite`, zwei veraltete Stellen zu F-SR-03 (F-SR-77),
Nr. 356 (F-SR-73). **Abweichungen:** E-SR-65 (Rahmen statt „ohne
Job-Auslöser"), E-SR-66 (Nr. 344 in der Schlüsselwechselprobe), E-SR-70
(bedingtes `UPDATE` statt Transaktion je Zeile). **Probleme beim Bau:**
Der Container war nach der Zusammenfassung neu gestartet — Anlage mit
`hochfahren.sh` wieder hoch, nichts verloren. Der erste Lauf der Probe war
2 rot: Sie legte ihre Sitzung mit `session_start()` nach der ersten
Ausgabe an, PHP verweigerte, Blatt und Karte antworteten 302; jetzt
schreibt sie die Sitzungsdatei selbst. Ihre ersten Läufe hinterließen elf
Zeilen im Reiter System (acht „smtp:", drei PHP-Warnungen) — von Hand
gelöscht, die Probe räumt „smtp:" seither selbst (F-SR-75). Die Gegenprobe
zu Nr. 344 legte, wie erwartet, eine `konto.json` in die Wurzel; die ließ
die folgenden Gegenproben an Teil 7 rot werden („lag schon vor dem
Aufruf da") — gelöscht, und die Probe entfernt seither eine Datei, die ihr
Aufruf gelegt hat. Im Code stand E-SR-64 zweimal für Verschiedenes
(Zeiger und Kopfkennung); die Kopfkennung heißt E-SR-67. Das erste Bild der
Lage „bereit" hatte keine Symbole — der Bedienweg wartet jetzt auf die
Netzruhe. Die Zählung riss Z25: `sw_zeit()` las ISO-UTC selbst statt über
`iso_utc_lesen()` — umgestellt. Der erste Prüfstand war 1 rot und 1 nicht
gemessen: Die Mailprobe vermisste `phase` im Beispielsatz — derselbe Fall
wie F-SR-32, und wieder erst im Prüfstand gefunden, weil die Mailprobe beim
Anlegen der Vorlage nicht mitlief —, und die Schemaprobe fand das
Plattform-Modul nicht, weil der Docker-Dienst nach dem Neustart des
Containers nicht lief. Beispielsatz ergänzt, Dienst und Modul gestartet,
zweiter Lauf — den brach ein weiterer Neustart des Containers im
Bilderlauf ab. Der dritte war bis auf zwei Wege der Bedienprobe am
Demo-Konto grün (fehlende Stammdaten-Vorschläge; nach einem Demo-Reset
einzeln grün, Ursache vermutlich der abgebrochene Lauf, nicht belegt); der
vierte steht in der Commit-Nachricht. Zahlen im Prüfdokument 2.

**H-SR-06 Gegenlesung und Behebung** (kein Paket, ein Halt; E-SR-23).
*Erledigt 04.10.2026 (Web 21.12.1):* gelesen als lesender Fable-Workflow
mit Rechnungen (E-SR-71) am Stand `9977172`, 42 Meldungen (F-SR-78 bis -83),
die Betreiberin hat E-SR-72 bis -75 entschieden. **Gebaut:** in
`serverkrypto_lib.php` `schluessel_unter_sperre()` um jeden Griff an einen
Schlüssel (neun Funktionen), `config_eintrag_in_datei()`, bedingtes
Ersetzen und „gültig" aus der Datei in `config_eintrag_schreiben()`, alt =
neu als `bereit`, der Beginn, der den Wechsel nicht zurücknimmt, das Blatt
beim Neuanfang des Anteils; in `schluesselwechsel_lib.php` der Beginn mit
Weg (`oberflaeche`/`hand`), `sw_zustand_passt()`, die Frist (`SW_NACHZUEGLER_S`,
`sw_nachweis_ab()`, `sw_nachweis_wartet()`), die Fehlerliste mit
`sw_fehler_bereinigen()`, der Nachweis an den Teilen
(`sw_paket_teile_lesbar()`, Archiv), `sw_archiv()` neu (Nachweis vor dem
Ablegen, Kennung aus `config.php`, Objekte, `zip_lesen()`), die Stückliste
je Häppchen, `sw_umsiegeln()` wirft, `sw_nach_einspielen()`, zwei
Mailschlüssel; `komp_kopf_angleichen()` in `komplett_lib.php`;
`wiederherstellen.php` ruft den Rücksatz; Karte und Statuszeile mit „nicht
begonnen", „Nachweis ab", „Ließen sich nicht umhüllen", Umleitung auch nach
einem Fehlschlag; der Download eines Stands unter drittem Schlüssel weist
vorher ab; der Reiter Archiv mit „bisheriger Schlüssel";
`edbak_begleit_lesen()` verlangt eine gültige Kennung. **Prüfmittel:**
Schlüsselwechselprobe mit sicherem Rückweg (der den Handeintrag misst) und
neuen Fällen (54, vorher 40); Gegenproben GH-1a bis GH-9 je Behebung,
G6 mit lügendem Versiegler (Prüfdokument); Mailprobe mit der neuen
Vorlage; Bedienweg mit gekürzter Frist und der Kachelzahl aus der Lage des
Anteils. **Nicht im Plan, dazu:** die übrigen Schlüsselgriffe unter
derselben Sperre (der Plan nannte drei; „jeder Griff" stimmt nur mit allen),
`sw_umsiegeln()` wirft (beim Planen von G6 gefunden), Nr. 357.
**Abweichungen:** keine von den Entscheidungen; die 42 Meldungen als sechs
Befunde (E-SR-78). **Probleme beim Bau:** Der Container war neu gestartet —
Anlage mit `hochfahren.sh` wieder hoch, nichts verloren. Der erste Lauf der
neuen Probe war 3 rot: zwölf Zeilen im Reiter System (die Fehlerliste im
Nachweis ausgetragen), ein liegengebliebener Bauordner, und ein Prüfausdruck
`?? 'x'`, der einen Zeiger `null` als fehlend las — alle drei behoben
(F-SR-81). Die Gegenprobe ohne Sperre blieb zweimal grün, bevor sie den Fall
aus K-1 herstellte (F-SR-78); die zur Frage „Paket mit kaputtem Teil" traf
ihre eigene Zeile nicht (F-SR-83). Die Befundnummern reichten nicht für
eine Nummer je Meldung (E-SR-78).

**Nachprüfung der Behebung** (E-SR-71): lesender Fable-Workflow
`wf_663b2eba-579`, acht Agenten (vier Leser, vier Gegenprüfer), 0
ausgefallen, rund 3290 s, 394 Werkzeugaufrufe. Jede Behebung hielt, D-2 und
D-7 zur Hälfte; 26 neue Meldungen, eine hoch, zwei mittel (F-SR-84 bis -87,
E-SR-79). **Eingearbeitet:** `sw_archiv()` wirft beim Schreibfehler;
`nachweis_begonnen` beim Eintritt; das Häppchen endet nach dem
Selbstanlauf; `fehler_mehr`; „abweichend" leert nicht; `verloren` je
Durchgang (`sw_verloren_durchgang_ende()`) und beim Einspielen leer; das
Inventar hält den Beginn nicht auf; der nachgeholte Beginn nennt beide
Möglichkeiten; Handeintrags-Mails für den Mailjob; Waise beim Anteil und
auf dem Blatt; `config_halbes_zuruecknehmen()`; die Sperrmeldung mit Zeit;
der Knopf an `$swPhase`; `komp_kopf_angleichen()` mit Kennung aus dem
Schlüssel; Kleinzeilen mit einem Satz. **Prüfmittel:** Schlüsselwechselprobe
60 (vorher 54) — Rückweg ohne A als B, Abbruch ohne Sieger, `PHP_BINARY`,
Vorbedingungen in neuer Reihenfolge mit Sperre, `--zurueck` pausiert; neue
Zeilen für Sperrmeldung, Rücknahme, Waise auf dem Blatt,
Archiv-Schreibfehler, leeren Nachweis, „abweichend", `verloren` nach dem
Einspielen, Handeintrag ohne Arbeit und ohne Sofortversand. Anteilprobe 66
(vorher 61). Gegenproben GH-10 bis GH-22 (Prüfdokument). **Probleme beim
Bau:** Der erste Lauf der Probe war 4 rot — der neue Fall „Rücknahme"
schrieb „X neu, A bisher", und `konfig_stellen_schreiben()` ließ dabei die
Marke zu X wandern (so liest die Lage einen soeben begonnenen Wechsel);
danach hieß die Waise `abweichend`, beide Kinder hörten „nur aus bereit",
und die Probe hielt — wie jetzt vorgesehen — an. Der Rückweg legte
byte-gleich zurück. Behoben in der Probe (Marke zurück auf A); in der
Anwendung ist das Wandern richtig. Prüfstand: im Prüfdokument.

**Nachmessung zu H-SR-06** (kein Paket; E-SR-80). *Erledigt 04.10.2026
(Web 21.12.2):* die vier Punkte, die das Prüfdokument von 21.12.1 „gelesen,
nicht gemessen" nannte, auf Wunsch der Betreiberin gemessen, nach dem Commit
von H-SR-06 (`bac4320`). **Gemessen:** der gefährliche Gegenfall zu ND/NA-1
mit Sicherung der Anlage (M1: B' nirgends, 12 von 12 Stücken ohne
Rückweg; M1b: derselbe Verlust mit einer echten Sperre, die zwischen den
Kindern und dem dritten Wechsel verfällt; beide Male der Abzug der Datenbank
nach dem Zurückspielen byte-gleich); mehr als 20 werfende Stücke (M2 mit
Behebung, M2alt mit dem alten Code, M2vor ohne F-SR-88, M2mitte und
M2mittevor mitten im ersten Durchgang); die Ausfälle beim Beginn (M3a
Inventar, M3avor ohne F-SR-88, M3b Zustand); Knopf, Kleinzeile, Download und
die zwei Auskünfte aus F-SR-88 fest in der Schlüsselwechselprobe (65, vorher
60). **Gefunden:** F-SR-88.
**Gebaut:** `sw_fehler_zahl()`, `fehler_mehr_lauf`, `sw_gezaehlt()`,
`sw_rueckstand()` ungezählt `null`, die Meldung von „Jetzt weiterarbeiten";
in der Probe der Knopf an seiner Aktion, „nicht begonnen" an der Zeile
„Umhüllung", fünf neue Prüfungen, `sleep(3)` vor der Liste (OPcache).
**Prüfmittel:** das Messgerüst `messungen.py` mit `anlage.sh` (Sicherung,
Zurückspielen, Vergleich) liegt im Scratchpad der Sitzung, nicht im
Repositorium (E-SR-80: einmalig); Gegenproben GH-1 bis GH-29; ein lesender
Gegenprüf-Workflow über die Messungen. **Probleme beim Bau:** Der erste
Lauf der erweiterten Probe war 2 rot — der Webserver sah über OPcache noch
`server_key_alt` und ließ den Download durch (F-SR-31, Ursache in der
Probe). Ein Ersatz in `status_lib.php` setzte einen Platzhalter statt des
Texts (Anker eines Hilfsskripts falsch) — vor jedem Lauf bemerkt und
behoben. Die Gegenprüfung fand die eigene erste Fassung von F-SR-88
unvollständig (oben). Prüfstand: im Prüfdokument.

**SR-04 Notzugang der einzigen BetreiberIn** — Nr. 249; Q-SR-05 = (B)
(H-SR-03). `nachweis_lib.php` (neu, aus `install.php` und
`wiederherstellen.php` herausgehoben; beide rufen es — Zählung im Register
`tools/zaehlung/register.php` als neue Zeile „Nachweisdatei"):
`nachweis_finden_oder_wuerfeln($muster)`, `nachweis_steht()`,
`nachweis_entfernen()`. `zweitfaktor_notweg.php` (neu): unangemeldet,
`sitzung_starten('app')` für das Formular-Token, `https_tor()`; zeigt den
Dateinamen `zweitfaktor-notweg-<32 hex>.txt`; POST mit Kontoadresse,
Passwort-Token (derselbe Weg wie `login.php`, `login_zeile()`) **und dem
Datenbankwert** (E-SR-24: `app_state.notzugang_geheim`, 64 Hexzeichen, in
Vierergruppen abtippbar wie die Schlüssel, `schluessel_eingabe_normalisieren()`)
— erst wenn die Datei liegt, der Wert mit `hash_equals()` stimmt, das
Passwort stimmt, das Konto BetreiberIn ist, genau eine BetreiberIn existiert
und `totp_an()`: `totp_abschalten($id, 'notweg')`, Nachweisdatei löschen,
**Wert neu würfeln**, Protokoll, Mail, Weiterleitung auf `login.php` (dort
Code-Schritt nicht mehr, Einrichtungstor danach). Der Wert entsteht mit der
Migration von SR-02 (`INSERT` in `app_state`) und, falls er fehlt, beim
ersten Aufruf der Seite; er steht nirgends in der Oberfläche. Jede andere
Lage antwortet **gleich** („Der Notzugang steht für dieses Konto nicht
bereit") mit `rate_gleiche_dauer()` — auch ein falscher Wert und eine
fehlende Datei sind dieselbe Antwort; Topf `notweg` (fünf je Stunde je
Adresse, mit Leiter, `sicherheit_ereignisse`); `WARTUNG_AUSNAHMEN` nimmt die Seite auf
(im Wartungsmodus muss sie erreichbar sein — dort steht die BetreiberIn,
die den Zweitfaktor verloren hat, ohnehin am häufigsten). `totp_lib.php`:
Weg `notweg` in der Liste der Wege. `protokoll_lib.php`: der Text zu
`totp_zurueckgesetzt` nennt den Weg. **Doku:** `Technik.md` 4.99q (der
vierte Weg, was er verlangt, was er nicht verrät), 7 (Runbook „Notweg" neu
geordnet: 1 Rückweg, 2 zweite BetreiberIn, 3 Notzugang mit Nachweisdatei,
4 SQL — „an der Anwendung vorbei, Betriebsakte"; **mit dem Satz aus
E-SR-24:** Der Datenbankwert schützt gegen Lesezugriff, nicht gegen einen,
der auf dem Webspace schreiben kann — die eine Abfrage für das
Datenbankwerkzeug steht wörtlich da), 4.99p (Matrix: die Seite ohne Rolle);
`Handbuch.md` 3.1f („Handy weg und die Codes auch?" bekommt den dritten
Absatz), 12.x (Betrieb: der Notzugang aus Sicht der BetreiberIn — welche
Datei, wohin, wo der Wert steht, was danach); `Pruefablauf.md` 8 bleibt.
Backlog 249 nach `Backlog-Erledigt.md`. **Prüfmittel:** Zweitfaktorprobe,
neuer Teil: ohne Datei, mit falscher Datei, ohne Wert, mit falschem Wert,
mit richtiger Datei aber zwei BetreiberInnen, mit falschem Passwort, mit
Admin-Konto — alle **gleiche Antwort, gleiche Dauer**; richtig →
Zweitfaktor aus, Protokoll `weg = notweg`, Mail eingereiht, Datei weg,
**Wert neu** (der alte gilt nicht mehr), nächste Anmeldung im
Einrichtungstor; sechs Fehlversuche → Topf. Rollenprobe: die Seite ist für keine Rolle ein
Weg ohne Datei. Wartungsprobe: Seite antwortet im Wartungsmodus.
`zaehlung` (Register: zwei Verbraucher der Nachweis-Hilfe → drei).
*Abnahme:* Zweitfaktorprobe grün mit den zehn Fällen; `grep -rn
"install-nachweis-\|nachweis" server/install.php server/wiederherstellen.php`
zeigt nur noch Aufrufe der Bibliothek; Register-Zeile Decke 0; nach einem
gelungenen Notzugang steht in `app_state` ein anderer Wert als davor.
*Stufe:* Web Neben. *Fächerung:* keine.

**SR-08 Proof-of-Work bei der Registrierung** — Nr. 228 (E-SR-26).
`registrieren.php`: beim GET eine Aufgabe in die Sitzung
(`$_SESSION['pow'] = [aufgabe: 32 Zufallsbyte hex, bis: time() + 600,
benutzt: false]`) und als verstecktes Feld `pow_aufgabe` ins Formular; beim
POST **vor** Honeypot, Mindestausfülldauer und jeder Adressprüfung:
`pow_ok($aufgabeAusSitzung, $_POST['pow_loesung'])` — Aufgabe gleich der in
der Sitzung, nicht abgelaufen, nicht benutzt, `hash('sha256', aufgabe .
loesung)` beginnt mit `POW_BITS` Nullbits; danach `benutzt = true` und eine
neue Aufgabe für das nächste Formular. Ein Fehlschlag nimmt **denselben
Weg** wie die übrigen Bremsen: gleiche Meldung, `rate_gleiche_dauer()`,
Zählung in `reg` — die Antwort sagt nie, woran es lag (E-P5b-13).
`assets/pow.js` (neu, ohne Fremdbestandteil): startet beim Laden einen
Worker (`assets/pow-worker.js`, aus eigener Quelle — `script-src 'self'`
deckt Worker), der mit `crypto.subtle.digest('SHA-256', …)` Lösungen
probiert und die erste passende zurückgibt; das Formular trägt sie in
`pow_loesung` ein. Ist der Worker beim Absenden noch nicht fertig, sperrt
`pow.js` den Knopf mit der Kleinzeile „Sicherheitsprüfung läuft …" und
schickt ab, sobald die Lösung da ist; Fehler im Worker (kein WebCrypto,
Worker gesperrt) → Kleinzeile mit Hinweis, kein stilles Warten. **Die
Schwierigkeit misst das Paket:** Hashrate des Workers in Chromium ohne und
mit vierfacher CPU-Drosselung (Bedienprobe, `page.emulateCPUThrottling`),
daraus `POW_BITS` so, dass der Median gedrosselt unter drei Sekunden und
ungedrosselt unter einer liegt; Zahlen und Bitzahl ins Prüfdokument, die
Konstante mit Herkunftskommentar in `registrieren.php`. **Doku:**
`Technik.md` 4.99m (die vierte Stufe: Aufgabe, Worker, Prüfung, Zahlen),
`Handbuch.md` (Registrierung: „Während du tippst, löst dein Browser eine
kleine Rechenaufgabe; sie schützt vor Massenanmeldungen und braucht
kein Zutun"), `docs/Lizenzen.md` unverändert (kein Fremdbestandteil),
Datenschutz-Baustein unverändert (nichts verlässt das Gerät); Kopf von
`registrieren.php` (R37 (4): „notfalls" ist eingetreten, per Entscheidung).
Backlog 228 nach `Backlog-Erledigt.md`. **Prüfmittel:** Ratenprobe, neuer
Teil (die Töpfe `reg*` liegen dort): ohne Lösung, mit falscher Lösung, mit
abgelaufener Aufgabe, mit zweimal derselben Aufgabe → dieselbe Antwort und
Dauer wie eine falsche Adresse (Δ < 50 ms, gemessen); mit richtiger Lösung
→ durch. Bedienprobe `registrieren.mjs` (neu, `tools/bedienprobe/wege/`):
Seite laden, Formular füllen, absenden; misst die Zeit bis zur Lösung
ungedrosselt und gedrosselt. `cspprobe` (Worker unter der Richtlinie).
*Abnahme:* Ratenprobe grün mit den fünf neuen Fällen; Bedienweg grün mit
Zahlen (Median, Höchstwert je Lage); `grep -c "pow_" server/registrieren.php`
und die Register-Zeile der JSON-Ausgänge unverändert; Textprobe 0 neu.
*Stufe:* Web Neben. *Fächerung:* keine.

**SR-06 Buchführung und Abschluss** — nur `docs/`, `tools/steuerung/`
mittelbar. Backlog: 232 → `nächste Backlog-Runde` (Q-SR-06, E-SR-25;
`verschieben.py` nur für Erledigte — die Kopfzeile von Hand,
`uebersicht.py --pruefen` danach); 228 ist mit SR-08 **erledigt**, 350 mit
SR-09, 250 mit SR-02, 344 mit SR-03, nicht umgehängt; `uebersicht.py --ziel 18` → **0**. Nr. 146 trägt
seinen Stand seit der Nachfassung (E-SR-28) — nichts mehr zu tun.
Prüfdokument 1 bis 6 vollständig; Statusblock „gebaut"; Prüfstand des
Kopf-Commits (`haupt`); Pull Request gegen `main` mit dem Bericht, nach
`Pruefablauf.md` 5.3, wenn `main` sich bewegt hat. **Nach der Freigabe des
Abschlusses** (K9, nicht in diesem Paket): Erledigt-Zeile in Rahmenplan 8,
Reste nach 6.1 (die Prüfpunkte der Betreiberin), Verlaufszeile, dieses
Konzept löschen; das Prüfdokument bleibt bis zur Prüfliste.
*Abnahme:* `decken.py` 20 Decken 0 gerissen, `uebersicht.py --pruefen` 0/0,
`--ziel 18` 0 Einträge, `nummern.py` 0.
*Stufe:* keine. *Fächerung:* keine.

## 5. Was bei jeder Codeänderung mitläuft

`CLAUDE.md` 2, je Paket: Versionsstufe nach Berührung (Web in
`server/version.php` mit Kopfabsatz; Uhr und Android bleiben, kein Paket
fasst sie an), `docs/CHANGELOG.md` mit Begründung — bei SR-01 der Satz,
dass jede Angemeldete einmal neu anmeldet, bei SR-02 die Migration —,
Dokumentation nachziehen (entfernte Sätze austragen, E-SR-16), Backlog:
erledigte Punkte wörtlich nach `Backlog-Erledigt.md` mit Schlusssatz
(E-R4-06), Rahmenplan nur an den vier Anlässen. Die Prüfmittel laufen
zuletzt; der Prüfstand (`tools/pruefstand/pruefen.sh`) erzeugt den Bericht
für die Commit-Nachricht — SR-02, SR-09 und der Kopf-Commit in `haupt`;
nach einem fremden Merge `Pruefablauf.md` 5.3. Ein neues Prüfmittel (SR-01,
SR-03, SR-09)
geht den Weg aus `Pruefablauf.md` 6.12 und trägt seine Anlass-Zeile.

## 6. Abschluss

Nach der Freigabe des Abschlusses (K9): Erledigt-Zeile in Rahmenplan 8
(eine Tabellenzeile, Prüfzahlen im Prüfdokument), Reste nach Abschnitt 6,
Backlog in die Kopfzeilen, Verlaufszeile, dieses Konzept löschen. Das
Prüfdokument bleibt, bis seine Prüfliste abgehakt ist. Danach beginnt
Schritt 12 mit dem Bedrohungsmodell; der Code-Review 12b liest nach S11,
was hier gebaut ist (R86).

## 7. Quellen

`docs/Rahmenplan.md` 3 (Fahrplanzeile 18), 4; `docs/Backlog.md` (Nr. 210,
228, 232, 233, 242, 247, 249, 251; seit dem Merge von 17 auch 250, 344); `docs/Backlog-Erledigt.md` (Nr. 141,
241); Konzept Sitzungsablage (E-SA-09; gelöscht `f6cb5fd`, letzte Fassung
`git show f6cb5fd^:docs/konzepte/Konzept-Sitzungsablage.md`); Konzept P5c
(E-P5c-41, -42, Abschnitt 8; gelöscht `00cacef`); Konzept R4 2.3
(Sperrliste), E-R4-11; `Vorbereitung-Sicherheitspaket.md` SP-3, SP-11;
`Review-Krypto-Sicherheit.md` 3.2, K-5; `docs/Technik.md` 4.97c, 4.97d,
4.99o, 4.99q, 5e, 5e.1, 7; `docs/Handbuch.md` 3.1f, 11.4b, 12.5;
`docs/Backup-Format.md` 6, 7; `docs/Pruefablauf.md` 4, 5, 6.12, 7;
`server/sitzung_lib.php`, `session_lib.php`, `auth_guard.php`, `login.php`,
`totp_lib.php`, `rueckweg_lib.php`, `serverkrypto_lib.php`,
`betrieb_server.php`, `betrieb_schluesselblatt.php`,
`api/schluesselblatt_pruefen.php`, `einstieg_lib.php`, `ingest.php`,
`transaktion_lib.php`, `wartung_lib.php`, `protokoll_archiv_lib.php`,
`komplett_lib.php`, `install.php`, `registrieren.php`, `admin_user.php`,
`zweitfaktor.php`; `tools/pruefstand/pruefablauf.json`,
`tools/quelltext/sitzungshaertung.php`, `tools/proben/verbindung/probe.php`.
Die Klickrunde vom 27.09.2026 (Q-SR-01 bis -11) ist in E-SR-17 bis -26
festgehalten; ihr Wortlaut steht nur dort. **Nachfassung vom 27.09.2026
(SR-09):** `docs/Backlog.md` Nr. 146, 350; `server/api/rueckweg_anlegen.php`,
`server/assets/rueckweg.js`, `server/vendor/phpseclib3/Crypt/` (EC, RSA),
`server/kopfzeilen_lib.php`; `docs/Lizenzen.md` 3;
`tools/bedienprobe/probe.mjs` (CDP-Sitzung), `LIESMICH.md` („fährt nur
Chromium"); W3C Web Authentication Level 2 (Zeremonien 7.1 und 7.2, Zähler
6.1.1), RFC 8949 (CBOR), RFC 9053 (COSE-Algorithmen).
