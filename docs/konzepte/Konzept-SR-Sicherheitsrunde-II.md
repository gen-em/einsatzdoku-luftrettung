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
die Schritt 15 (E-ZE-12) und R4-01 (Q-R4-12) hierher gehängt haben.
**Modell:** Konzept Fable (R14); Umsetzung **Opus** (K2), **kein
Fable-Schritt**; SR-03 bekommt vor dem PR eine **Gegenlesung durch Fable**
(Q-SR-09, E-SR-23, H-SR-06) — ein Halt, kein Schritt.
**Fächerung (`CLAUDE.md` 7):** keine, in keinem Paket. Alle Pakete
schreiben `server/`, und jedes trägt erzählenden Text; die Konzeptsitzung
hat nicht gefächert (2.1).
**Versionsstufe:** legt die Umsetzung fest (K3). Je Paket in Abschnitt 4
vermerkt: SR-05 Korrektur; SR-01, SR-03, SR-04, SR-07, SR-08 Neben; SR-02
Neben **mit Migration** — der Prüfstand fährt dafür `haupt` (Stufenregel `migration`),
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
dieses Konzepts auf einem eigenen Zweig von `main`.
**Backlog-Spanne:** **350 bis 359** (E-SD-21, eingetragen mit `SR-00`
vor jeder Vergabe). Vergeben aus der Spanne: siehe Statusblock.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **27.09.2026 — Konzept vollständig, alle Fragen beantwortet, zur Freigabe vorgelegt.** Befund an acht Punkten und fünf Themen, gelesen am Stand R4-10 (2). Paketschnitt: **acht Pakete** (4) — die Klickrunde vom 27.09.2026 hat zwei dazugebracht (SR-07 frischer Code, SR-08 Proof-of-Work). Kein Konzept-PR offen: Der Zweig sitzt auf dem 17er-Zweig und wird erst nach dessen Merge gegen `main` gestellt (E-SR-02); bis dahin ist `nummern` im Prüfbericht rot, mit vier 17er-Nummern (F-SR-12). |
> | Entschieden | **E-SR-01 bis E-SR-26** (Abschnitt 3.1). Von der Betreiberin am 27.09.2026: E-SR-02 (Ausgangsstand) und E-SR-17 bis -26 (die Antworten der Klickrunde, Q-SR-01 bis -11); die übrigen aus dem Konzept. |
> | Offen | **Die Freigabe des Konzepts.** Keine Q mehr offen. |
> | Umsetzung | noch nicht begonnen. **SR-00 erledigt** (Konzept, Spanne, Fahrplanzeile — Rahmenplan Fassungen 137 und 138). |
> | Fable-Schritte | keine; **eine Fable-Gegenlesung** von SR-03 vor dem PR (H-SR-06, E-SR-23). |
> | Fächerung | keine. |
> | Nummern | 350 bis 359 reserviert; vergeben: **keine**. |

---

## 1. Auftrag

**Ziel:** Fünf Lücken schließen, die frühere Konzepte benannt und
ausdrücklich hierher gelegt haben — und danach trägt **kein offener
Backlog-Punkt mehr `gehört zu: 18`**: erledigt, oder mit Begründung an
seinen richtigen Ort gehängt.

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

**Nicht Ziel:** alles, was Schritt 12 gehört (der Review liest, was hier
gebaut ist); die Verschlüsselung der Ortsdaten (12a, R78); ein zweiter
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

**Keine Probe gefahren, kein Prüfstand.** Trefferzahlen sind `grep` am
27.09.2026; jede Aussage „so verhält sich die Anlage" stammt aus dem Code
und der Doku. Die örtliche Anlage lief nur für den Prüfstand dieses
Commits. Nicht gefächert: Fünf Themen, jedes hängt an denselben drei
Dateien (`sitzung_lib.php`, `login.php`, `serverkrypto_lib.php`), und eine
Gegenprüfung durch Agenten hätte hier vor allem eines gemessen — dass
niemand die Anlage anfassen durfte.

### 2.2 Die acht Punkte — Einordnung

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
| `totp_lib.php`, `zweitfaktor*.php`, `einstellungen.php`, `admin_user.php`, `pw_handling.php` | 242/249 | ja — SR-02 (Gerät merken, vergessen beim Passwortwechsel und Zurücksetzen), SR-04 nur `totp_lib.php` |
| `serverkrypto_lib.php`, `betrieb_server.php`, `betrieb_schluesselblatt.php`, `api/schluesselblatt_pruefen.php`, `einstieg_lib.php`, `sicherungsziel_lib.php`, `*_archiv_lib.php`, `adminbackup_lib.php`, `komplett_lib.php` | 233/247 | ja — SR-03; dazu `jobs_lib.php` (ein Job) und ein neues `schluesselwechsel_lib.php` |
| `install.php` ab Zeile 397 | 247 | **nein** — die Schlüssel entstehen dort, die Rotation lebt in `serverkrypto_lib.php`; R4-09 durfte die Datei unbesorgt anfassen (F-SR-02) |
| `ingest.php`, `diensttag_lib.php`, `transaktion_lib.php` | 210 | `ingest.php` ja, `transaktion_lib.php` nur ein Kommentar; `diensttag_lib.php` nein (R4-15 hat `days.created_at` dort gebaut, SR-05 liest es nur) |
| `konto_lib.php`, `demo_lib.php`, `wartung_lib.php`, `db.php`, `schema.sql`, `migration_lib.php` | 249/228/242/210 | `schema.sql`, `migration_lib.php`, `demo_lib.php`, `jobs_lib.php` — SR-02; `konto_lib.php`, `wartung_lib.php`, `db.php` nein |
| `registrieren.php`, `ratelimit_lib.php`, `zip_lib.php` | 228 | `registrieren.php` ja (SR-08, Proof-of-Work); `ratelimit_lib.php` bekommt einen Topf (SR-04); `zip_lib.php` nein |

**Was 17 in diesen Dateien geändert hat und SR übernimmt:** `json_out()` in
`api/rueckfrage.php`, `api/schluessel_erneuern.php`,
`api/schluesselblatt_pruefen.php` (R4-09); der Zweig `user_delete` über
`konto_loeschen()` (R4-10); `days.created_at` (R4-15, offen am 27.09.2026);
die Demo-Änderungsmarke (R4-14, offen) — je eine Zeile in `ingest.php` und
`auth_guard.php`, die SR nicht umbaut. Gemergt ist 17 am 27.09.2026
**nicht**; die Umsetzung beginnt auf `main` nach dem Merge und misst neu.

**PK-06 bis PK-08** schreiben die Workflows und `CLAUDE.md` 3 und 6; SR
fasst `.github/` nicht an. **Schritt 12** liest danach: `Review-Krypto-
Sicherheit.md` nennt in 3.2 die Sitzung („Sitzungscookie Secure, HttpOnly,
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
  Die Fahrplanzeile nennt sie seit Fassung 137 mit (SR-00).
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
  Bericht auf dem dann gemergten Baum (`Pruefablauf.md` 5.3).

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
| E-SR-14 | **Reihenfolge SR-01 → SR-02 → SR-07 → SR-05 → SR-03 → SR-04 → SR-08 → SR-06, seriell, ohne Fächerung.** | Konzept, ergänzt 27.09.2026 | Alle Pakete schreiben `server/` (Rahmenplan 4: nacheinander). Erst die Sitzung, weil SR-02 ihre Cookie-Tabelle braucht; der frische Code (SR-07) gleich danach, weil SR-03 und SR-04 seine Griffe schon mit ihm bauen; SR-05 vor SR-03, weil es klein ist und `ingest.php` niemand sonst anfasst; SR-03 vor SR-04, weil der Notzugang die Rotation nicht kennen muss, die Rotation aber den Zweitfaktor (Geheimnisse umhüllen); der Proof-of-Work (SR-08) zuletzt, weil er keine andere Datei der Runde teilt. |
| E-SR-15 | **Keine Mockups** (Q-SR-08: bestätigt): Der Haken im Code-Schritt ist ein `.schalter` in der Anmeldekarte, die Zeile in der Karte „Zweitfaktor" eine `zeile()` mit Knopf, der Fortschritt in der Karte „Schlüssel des Servers" die Lage `rotation` mit Zählung wie beim Anteil, der Notzugang das Gerüst von `wiederherstellen.php`. Die Bilder des Bilderlaufs gehen ins Prüfdokument. | Konzept (`CLAUDE.md` 5, `Design.md` 9) | Ein neuer Baustein entsteht nur mit Mockup; hier entsteht keiner. |
| E-SR-16 | **Die Sätze „Wer sie liest, ist angemeldet" werden ersetzt**, nicht ergänzt: Kopf von `sitzung_lib.php`, `Technik.md` (Sitzungsablage); `Backlog-Erledigt.md` Nr. 241 bleibt wörtlich. Dazu die Doku-Stellen aus F-SR-01 und F-SR-03. | Konzept (F-SR-09) | `CLAUDE.md` 2, Punkt 3. |
| E-SR-17 | Q-SR-01, Q-SR-10: **„Gerät merken" für alle Rollen, mit einstellbarer Dauer je Rollengruppe** — eine Zahl für NutzerInnen (Vorgabe 30 Tage), eine für Support, Admin und BetreiberIn (Vorgabe 7 Tage); Auswahl aus, 1, 7, 14, 30, 90 Tage; **0 heißt: kein Haken**. Ort: Betrieb → Servereinstellungen, neue Karte „Anmeldung" (zwei Wahllisten, eigenes Speichern; in derselben Karte später die Frist des frischen Codes, E-SR-20). **Die Dauer wird beim Prüfen gerechnet, nicht beim Merken:** Die Tabelle trägt `angelegt_am`, gültig ist eine Zeile, solange `angelegt_am` plus der heutigen Dauer der Rollengruppe in der Zukunft liegt. Der Text am Haken nennt die Dauer („Dieses Gerät 7 Tage merken"). Keine persönliche Wahl im Profil. | Betreiberin, 27.09.2026 | „Der Zeitraum sollte einstellbar sein, für User und für Admins die Standard-Einstellung." Gerechnet beim Prüfen, damit eine verkürzte Einstellung sofort für alle gilt und 0 alle Geräte auf einmal abmeldet — sonst hieße „aus" erst in 30 Tagen aus. Zwei Zahlen statt einer, weil die Verwaltung kürzer laufen soll als der Dienst; keine dritte Ebene im Profil, weil eine Einstellung, die kaum jemand ändert, nur Pflege kostet (Q-SR-10, wie empfohlen). |
| E-SR-18 | Q-SR-02: **Gemerkt wird nur nach einem App-Code.** Nach Wiederherstellungscode oder Rückweg zeigt der Code-Schritt keinen Haken. | Betreiberin, 27.09.2026 | wie empfohlen (Begründung in E-SR-07). |
| E-SR-19 | Q-SR-08: **Keine Mockups**; die Bilder des Bilderlaufs kommen ins Prüfdokument (P-SR-05). | Betreiberin, 27.09.2026 | wie empfohlen (E-SR-15). |
| E-SR-20 | Q-SR-11: **Ein frischer Code vor kritischen Handlungen** — Paket SR-07. „Frisch" heißt: In dieser Sitzung wurde in den letzten **15 Minuten** ein Code aus der App oder ein Wiederherstellungscode eingegeben (`$_SESSION['zf_frisch_bis']`, gesetzt in `login.php` nach dem Code-Schritt und auf der Bestätigungsseite); eine Anmeldung über ein gemerktes Gerät und der Rückweg setzen nichts. **Die Liste** (Konstante, eine Stelle): alle `schluessel_*`-Handlungen in `betrieb_server.php`, die Anzeige von `betrieb_schluesselblatt.php`, in `admin_user.php` Rollenwechsel, Zweitfaktor zurücksetzen und Konto löschen, in `einstellungen.php` den eigenen Zweitfaktor ausschalten. **Mechanik:** `zweitfaktor_frisch_verlangen($handlung)` in `auth_guard.php`, gerufen vor `csrf_check()` wie ein Rollentor (E-P5c-85); nicht frisch → 303 auf `zweitfaktor.php?bestaetigen=1&zurueck=<eigener Pfad>` (nur Pfade der eigenen Anwendung), dort Code eingeben (Topf `totp`), zurück auf die Seite mit dem Hinweis „Code bestätigt — bitte die Handlung noch einmal auslösen"; für `api/` 403 JSON `zweitfaktor_frisch`. Ein Konto **ohne** Zweitfaktor hat nichts zu bestätigen; die Prüfung ist dort ein Durchlass — die Liste trifft ohnehin nur Verwaltungshandlungen und das eigene Ausschalten. | Betreiberin, 27.09.2026 („Ja, kurze Liste") | Ein gemerkter, unbeaufsichtigter Rechner mit bekanntem Passwort reicht sonst für einen Schlüsselwechsel. 15 Minuten sind die Frist des Topfes `totp`; die abgeschickte Handlung wird nicht nachgespielt, weil ein gespeicherter POST samt Formular-Token die Art Zwischenspeicher ist, die beim nächsten Umbau falsch abgespielt wird — ein zweiter Klick ist billiger. Vor dem Token wie jedes Tor, damit die Rollenprobe den Umweg von der Token-Ablehnung unterscheiden kann. |
| E-SR-21 | Q-SR-03: **Komplett-Stände werden nicht umgehüllt; ein frischer Stand unter dem neuen Schlüssel ist Abschlussbedingung.** | Betreiberin, 27.09.2026 | wie empfohlen (E-SR-09, E-SR-10). |
| E-SR-22 | Q-SR-04: **Mail an alle BetreiberInnen** bei Beginn und Abschluss des Schlüsselwechsels, nur Kennungen, nie Werte. Vorlage `serverschluessel_gewechselt`. | Betreiberin, 27.09.2026 | wie empfohlen. |
| E-SR-23 | Q-SR-09: **Opus baut SR-03, Fable liest es vor dem PR gegen** — Haltepunkt H-SR-06: Die Umsetzungsinstanz hält nach SR-03 an, sagt es, und eine Fable-Instanz liest das Paket gegen das Konzept (Inventar vollständig? Nachweis vor dem Ersetzen? die drei Bedingungen als Riegel in der Funktion?); Befunde als F-SR-NN ins Konzept, Behebung durch Opus, dann weiter mit SR-04. | Betreiberin, 27.09.2026 | Ein zweites Augenpaar am gefährlichsten Paket, ohne den ganzen Bau mit Fable zu bezahlen. Kein Fable-**Schritt** im Sinn von K8 — die Umsetzung wechselt das Modell nicht, sie pausiert für eine Lesung. |
| E-SR-24 | Q-SR-05: **(B) Notzugang — und zusätzlich ein Zufallswert in der Datenbank.** `app_state.notzugang_geheim` (32 Zufallsbyte hex) entsteht mit der Migration von SR-02 (oder beim ersten Aufruf, wenn er fehlt) und wird nach jedem gelungenen Notzugang neu gewürfelt. Die Seite verlangt **drei** Dinge: die Nachweisdatei im Anwendungsverzeichnis (Webspace), den Wert aus der Datenbank (das Runbook nennt die eine Abfrage für das Datenbankwerkzeug des Hosters) und das Passwort des Kontos (Wissen). Verglichen wird mit `hash_equals()`, jede Abweichung antwortet gleich und gleich lang. Der Wert steht **nirgends** in der Oberfläche und nicht auf dem Schlüsselblatt. | Betreiberin, 27.09.2026 („B und zusätzlich einen Zufallswert in der Datenbank, damit nicht nur FTP-Zugriff ausreicht") | Der Wert schützt gegen den, der Dateien **lesen** kann — ein Webspace-Backup, ein Lesezugang — und gegen den, der nur die Datenbank hat. **Ehrlich dazu:** Wer auf dem Webspace **schreiben** kann, kann eine PHP-Datei hochladen und hat damit auch die Datenbank; gegen ihn hilft kein Wert auf dem Server, und das gilt für jeden Weg, den die Anwendung selbst anbietet. Das Runbook sagt das. Nach dem Gebrauch neu gewürfelt, damit ein einmal abgeschriebener Wert nicht als Dauerzugang liegen bleibt. |
| E-SR-25 | Q-SR-06: **Nr. 232 → `nächste Backlog-Runde`, `nur auf Anlass`.** | Betreiberin, 27.09.2026 | wie empfohlen. |
| E-SR-26 | Q-SR-07: **Nr. 228 wird gebaut — fest, ohne Anlass, ohne Schalter** (Paket SR-08). Der Server stellt beim Laden der Registrierungsseite eine Aufgabe (32 Zufallsbyte, Sitzung, zehn Minuten, einmal gültig); ein Worker im Browser rechnet ab dem Laden, **während die Person tippt**, SHA-256 über WebCrypto, ohne Fremdbestandteil (`assets/pow.js`); der Absendeknopf wartet nur, wenn die Person schneller ist als der Worker („Sicherheitsprüfung läuft …"). Der Server prüft **eine** SHA-256 (unter einer Millisekunde), **vor** jeder Adressprüfung, im selben Fehlerpfad wie Honeypot und Mindestausfülldauer — die Antwortzeitgleichheit aus E-P5b-13 bleibt (Δ < 50 ms). **Die Schwierigkeit ist eine Konstante**, die SR-08 misst und festlegt: Ziel ist der Median **unter einer Sekunde auf einem aktuellen Handy** und unter drei Sekunden auf dem alten Diensthandy (Chromium mit vierfacher CPU-Drosselung als Ersatz im Prüfstand); die gemessenen Hashraten und die gewählte Bitzahl stehen im Prüfdokument. | Betreiberin, 27.09.2026 („Bauen wir einfach fest ein") | Die Frage „wie viel Verzögerung" beantwortet die Bauform: Die Rechnung läuft nebenher, solange jemand Adresse und Passwort tippt (zehn bis dreißig Sekunden), und ist dann in aller Regel fertig — spürbar wird sie nur bei einem Skript, das das Formular sofort abschickt, und genau das ist der Zweck. Ohne JavaScript geht die Registrierung ohnehin nicht (E-P5b-13). Kein Schalter, weil ein Schalter, den niemand umlegt, eine zweite Wahrheit ist (R74). |

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

**Beantwortet am 27.09.2026** in der Konzeptsitzung (Klickrunde mit
Erklärung je Frage): Q-SR-01 „alle Rollen, aber einstellbare Dauer" →
E-SR-17 mit Q-SR-10; Q-SR-02 wie empfohlen → E-SR-18; Q-SR-03 → E-SR-21;
Q-SR-04 → E-SR-22; Q-SR-05 **(B) plus Datenbankwert** → E-SR-24;
Q-SR-06 → E-SR-25; Q-SR-07 **abweichend: fest einbauen** → E-SR-26;
Q-SR-08 → E-SR-19; Q-SR-09 **Opus baut, Fable liest gegen** → E-SR-23;
Q-SR-10 wie empfohlen → E-SR-17; Q-SR-11 wie empfohlen → E-SR-20.

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
  Blatt); Befunde als F-SR-NN, Behebung durch Opus, dann SR-04.
- **H-SR-05 mit dem Merge:** Die Migration `vertraute_geraete` steht aus,
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
Fassung 137 (Kopf, Fahrplanzeile 18 mit Nr. 232 und 251, Verlaufszeile).
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

**SR-02 Gerät merken** — Rest aus Nr. 141 (E-P5c-41); Q-SR-01, -02, -08
entschieden (H-SR-01). `schema.sql` und `migration_lib.php`: Tabelle
`vertraute_geraete` (E-SR-07), Migration `2026_..._vertraute_geraete`, ID
in die `skipped`-Liste; Stufenregel → Prüfstand `haupt`. `totp_lib.php`:
`totp_geraet_merken($userId)` (würfelt, setzt das Cookie über die Tabelle
aus SR-01, schreibt den Hash), `totp_geraet_erkannt($userId)` (Cookie →
Hash → Zeile, deren `angelegt_am` plus der heutigen Dauer der Rollengruppe
in der Zukunft liegt, `zuletzt_am` fortschreiben — E-SR-17),
`totp_geraete_vergessen($userId, $weg)`, gerufen aus `totp_abschalten()`;
`totp_zustand()` liefert die Zahl; `totp_geraet_dauer($rolle)` liest die
zwei Einstellungen (`app_state`, Schlüssel `zf_geraet_tage_user`,
`zf_geraet_tage_verwaltung`, Vorgaben 30 und 7) — 0 heißt: kein Haken, und
`totp_geraet_erkannt()` gibt nie „erkannt". `betrieb_server.php`: neue Karte
„Anmeldung" mit zwei Wahllisten (aus, 1, 7, 14, 30, 90 Tage) und eigenem
Speichern; Protokoll `einstellung_geaendert` wie die übrigen Karten. `login.php`: nach dem Passwort und vor
dem halben Stand `totp_geraet_erkannt()` → direkt `anmeldung_vollenden()`;
im Code-Schritt der `.schalter` „Dieses Gerät n Tage merken" mit der Dauer
der eigenen Rollengruppe (nur im App-Code-Formular, E-SR-18; bei Dauer 0
gar nicht), ausgewertet nach `totp_anmeldung_pruefen()` mit `art === 'app'`. `einstellungen.php`/`zweitfaktor_teile.php`: Zeile
„Gemerkte Geräte: n" mit Knopf „Alle vergessen" (POST, `csrf_check()`);
beim Passwortwechsel `totp_geraete_vergessen()` neben `session_epoch + 1`;
`pw_handling.php` ebenso beim Reset. `demo_lib.php`:
`demo_zweitfaktor_leeren()` räumt die Tabelle. `jobs_lib.php`: Schritt
`vertraute_geraete` in `job_aufraeumen_schritte()` (Abgelaufene); der
Riegel `jobregister` zählt ihn, `Technik.md` 4.97a nennt achtzehn Schritte.
`protokoll_lib.php`: Arten `totp_geraet_gemerkt` (neutral),
`totp_geraete_vergessen` (neutral, `daten.weg`). Berechtigungsmatrix
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
`Backup-Format.md` 6.3). `adminbackup_lib.php`, `sicherungsziel_lib.php`,
`protokoll_archiv_lib.php`: keine Änderung an der Logik — sie rufen
`sk_oeffnen()`; `protokoll_archive()` vergleicht die Kennung im Namen
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
Rotation). Backlog 247 und 233 nach `Backlog-Erledigt.md`. **Prüfmittel:**
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
Rotation) unverändert grün; `jobregister`, `installweiche`, Bilderlauf der
Karte in drei Lagen (P-SR-05).
*Abnahme:* Schlüsselwechselprobe grün mit allen Fällen; `grep -rn
"sk_versiegeln(" server/` = die Zwecke im Inventar (Zahl im Prüfdokument);
Sechs-Zwecke-Tabelle in `Technik.md` gleich der Liste im Code; die Karte
in drei Lagen im Bilderlauf.
*Stufe:* Web Neben (keine Migration; `config.php` bekommt einen
Kommentareintrag mehr). *Fächerung:* keine — ein Job, ein Changelog-Ton.

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
`uebersicht.py --pruefen` danach); 228 ist mit SR-08 **erledigt**, nicht
umgehängt; `uebersicht.py --ziel 18` → **0**.
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
für die Commit-Nachricht — SR-02 und der Kopf-Commit in `haupt`; nach einem
fremden Merge `Pruefablauf.md` 5.3. Ein neues Prüfmittel (SR-01, SR-03)
geht den Weg aus `Pruefablauf.md` 6.12 und trägt seine Anlass-Zeile.

## 6. Abschluss

Nach der Freigabe des Abschlusses (K9): Erledigt-Zeile in Rahmenplan 8
(eine Tabellenzeile, Prüfzahlen im Prüfdokument), Reste nach Abschnitt 6,
Backlog in die Kopfzeilen, Verlaufszeile, dieses Konzept löschen. Das
Prüfdokument bleibt, bis seine Prüfliste abgehakt ist. Danach beginnt
Schritt 12 mit dem Bedrohungsmodell — und liest, was hier gebaut ist.

## 7. Quellen

`docs/Rahmenplan.md` 3 (Fahrplanzeile 18), 4; `docs/Backlog.md` (Nr. 210,
228, 232, 233, 242, 247, 249, 251); `docs/Backlog-Erledigt.md` (Nr. 141,
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
festgehalten; ihr Wortlaut steht nur dort.
