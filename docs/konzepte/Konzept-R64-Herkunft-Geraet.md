# Konzept R64 — Herkunft und Gerät je Einsatz, Sperrvermerke in der Sicherung (Nr. 63)

**Server-Teil des Rahmenplan-Schritts 6 (S4-Rest) · Beschlüsse R64 (Nr. 83,
Fassung 22) und Nr. 63 (B-S4-10) · Auftrag des Auftraggebers vom 04.09.2026
· Ablage `docs/konzepte/` (K1), Lebenszyklus R62 · nachgeliefert zu einem
laufenden Schritt: die S4-Rest-Instanz arbeitet die Android-Pakete ab und
nimmt dieses Paket auf, sobald sie dort ankommt.**

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 04.09.2026 — **AP1 und AP3 erledigt** (Web 14.0.0 und 14.1.0). Entscheidungen E-R64-01 bis E-R64-16 stehen; keine offene F-Frage |
> | Paket in Arbeit | **AP2** (Sicherung: Momentaufnahme und Sperrvermerke, Nutzlast 9) |
> | Erledigt | **AP1** — Datenmodell, Ableitung, anlegende Stellen, Migration `2026_09_04_herkunft_geraet`; Ingestprobe 39/0, Register 42 = 42 · **AP3** — Export und Anzeige; CSV-Kreislauf 8965/1023/5, Excel unverändert 31 Spalten, Wortliste 0/0/0 |
> | Reihenfolge | **AP1 → AP3 → AP2 → AP4 → AP5.** AP3 ist auf Weisung des Auftraggebers vorgezogen worden, weil die Anzeige zwischen AP1 und AP3 für `android`, `wear` und `schnitt` weiter „Uhr" zeigte. Kein fachlicher Konflikt: AP3 und AP2 fassen keine gemeinsame Datei an |
> | Wo es hakt | nichts. Der größte Einzelposten bleibt AP4 (Referenz: Kopplungsweg und Schnitt im Werkzeug, alle drei Läufe von vorn) |
> | Fable-Schritt | **keiner** (Spalten, eine Migration, ein Dateiformat mit Verweisen — Standardmodell nach K2) |
> | Erhoben an | `main` vom 04.09.2026, 05:59 UTC: **Web 13.2.0, Uhr 3.0.0, Android 0.10.2** (PR #31 und #32 gemergt — der Rahmenplan-Kopf sagt noch „Android 0.7.7, Paket E nicht gemergt", siehe B-R64-02). Kein S4-Rest-Zweig gepusht |
> | Erhoben aus | dem Repositorium allein: `schema.sql`, `update.php`, `ingest.php`, `backup_lib.php`, `spur_lib.php`, `api/schneiden.php`, `api/export_data.php`, `api/backup_restore.php`, `adminbackup_lib.php`, `einsatz.php`, `geraete_lib.php`, `pair.php`, `docs/Backup-Format.md`, `docs/Export-Format.md`, `docs/JSON-Vertrag.md`, `docs/Backlog.md`, `tools/referenzdatensatz/`. Kein Server, kein Gerät in der Konzeptsitzung — was sich so nicht ermitteln ließ, steht in Abschnitt 9 |

Dieses Dokument wird während der Umsetzung fortgeschrieben (Statusblock,
Abschnitt 6 „Umsetzungsstand", „Probleme", Abschnitt 8 Fehlerfunde) und der
Zweig nach jedem Paket gepusht (K7). Das Prüfdokument entsteht daneben als
`docs/konzepte/Pruefdokument-R64-Herkunft-Geraet.md` (K9, E-R64-14). Nach
der Freigabe des S4-Rest-Abschlusses geht es denselben Weg wie das Konzept
S4 (R62): Erledigt-Zeile in Rahmenplan Abschnitt 8, Reste nach Abschnitt 6,
Backlog nach Abschnitt 5, löschen.

**Was dieses Dokument nicht festlegt (K2, K3):** keine Versionsnummern
(die Umsetzung entscheidet; Anhaltspunkt: eine Migration und ein geänderter
Datenweg der Sicherung sind nach der Zählweise in `version.php` eine
**Hauptversion** des Web), keine Modellempfehlung je Paket, keine neuen
Backlog-Nummern — Kandidaten heißen „Backlog-Kandidat". **Nummernkreise:**
E-R64, F-R64, B-R64 sind von E-S4/F-S4/B-S4 getrennt.

**Warum ein eigenes Konzept und kein Nachtrag im Konzept S4:** Das Konzept
S4 beschreibt Handy und Uhr; sein Abschnitt 13 nennt R64 nur als Zeile. Der
Server-Teil hat ein eigenes Dateiformat, eine Migration und eigene
Prüfmittel — das ist ein Paket mit eigener Abnahme, nicht eine Zeile in einer
Prüfliste. Es läuft **auf demselben Zweig** wie der übrige S4-Rest (E-R64-13).

---

## 0. Auftrag an die Umsetzungsinstanz (Zusatzprompt)

> Der S4-Rest bekommt sein Server-Paket: **R64 — Herkunft und Gerät je
> Einsatz** und **Nr. 63 — Sperrvermerke des Schnitts in der
> Konto-Sicherung**, zusammen als **eine** Formatänderung der Sicherung und
> **eine** Migration. Grundlage ist dieses Dokument.
>
> 1. Android-Pakete des S4-Rests zuerst fertig bauen und pushen. Dieses
>    Paket beginnt danach, auf demselben Zweig.
> 2. Es gibt keine offene F-Frage; F-R64-01/-02 sind als E-R64-15/-16
>    entschieden. Was während der Umsetzung neu aufkommt, wird als F-R64-03
>    ff. dem Auftraggeber mit Empfehlung vorgelegt (K6).
> 3. Umsetzung nach Abschnitt 5 (Verhalten) und 6 (Pakete AP1–AP5, in dieser
>    Reihenfolge). Berührt werden `server/` und `docs/` und
>    `tools/referenzdatensatz/`, **nicht** `android/`, **nicht** `watch/`.
>    Alles, was `track_cuts` liest oder schreibt, geht durch `spur_lib.php`
>    (dort begründet; CLAUDE.md 4).
> 4. Nach jedem Paket: Statusblock, „Umsetzungsstand", Push (K7).
> 5. Prüfmittel zuletzt (Abschnitt 7): Kreisläufe csv, edbak und edbak-alt
>    mit **0 unerklärt** (der edbak-Kreislauf trägt jetzt den Schnitt der
>    Referenz), Wiederherstellungsprobe mit dem neuen Teil 5 (Grenzfälle),
>    Ingestprobe, Referenz-Exporte und Fixture neu erzeugt, Register
>    gegengezählt (**+1**), Wortliste 0/0/0, `php -l`.
> 6. Kein Fable-Schritt. Kein Push auf `main` vor der Bestätigung — der
>    S4-Rest kommt als Ganzes (K7).
> 7. Am Ende: Buchführung nach Abschnitt 10 (Rahmenplan Fassung 27, Backlog
>    63/83, Vertrag Abschnitt 8) mit den dort stehenden Einfügeblöcken und
>    Gegenproben.

---

## 1. Aufgabe

**R64** (Rahmenplan Abschnitt 7, Beschluss vom 02.09.2026 zu Nr. 83, Weg b),
soweit er in diesem Paket umzusetzen ist:

1. Geräteart und Modell werden beim Anlegen als **Momentaufnahme** an
   `missions` und `rest_segments` kopiert, in die Sicherung aufgenommen, der
   Bestand per Migration aus `devices` nachgefüllt; Trennen bleibt Löschen
   (R47).
2. `origin` bekommt eigene Werte: `watch` bleibt für die Garmin-Uhr, neu
   `android`, `wear` und `schnitt` neben `manual` und `import`, gesetzt beim
   Anlegen aus Geräteart und `client_ref`-Präfix; Feldkatalog, Export- und
   Backup-Format, Kreisläufe (R24) und Referenz ziehen nach.
3. *Nicht hier:* Sichtbarkeit im Betriebslage-Dashboard (Nr. 80, P5) und als
   Kachel der Zeitraumübersicht (Nr. 88, Backlog-Runde). Dieses Paket liefert
   die Spalten, aus denen beide lesen.

**Nr. 63** (B-S4-10): Die Sperrvermerke des Schnitts (`track_cuts`) überstehen
die Konto-Sicherung nicht. Nach einem Wiedereinspielen liefert ein Gerät mit
gepufferten Punkten den geschnittenen Zeitraum nach, und die Fahrt liegt in
Einsatz *und* Segment. Der Vermerk verweist auf zwei Kennungen (Quelle und
Ziel), die das Einspielen erst neu vergibt — er muss über **Verweise** in die
Datei, wie die Spuren seit Nutzlast 8. Dazu `docs/Backup-Format.md`, beide
Kreislaufproben und ein Prüffall.

**Warum zusammen:** Der Rahmenplan (Schritt 6, Backlog 63 und 83) koppelt
beides ausdrücklich — „eine Formatänderung, ein Kreislauf". Getrennt gäbe es
zwei Nutzlast-Sprünge kurz nacheinander und zwei Referenz-Erneuerungen.

---

## 2. Befund (am Code gelesen, `main` 04.09.2026)

### 2.1 Wie die Herkunft heute entsteht

`missions.origin` ist `ENUM('watch','manual','import') NOT NULL DEFAULT
'watch'`, gesetzt beim Anlegen und danach nie geändert (Schema-Kommentar).
`rest_segments` hat keine Herkunftsspalte. Acht Stellen legen Einsätze oder
Segmente an:

| Stelle | Präfix (`JSON-Vertrag` 8) | Gerät (`device_id`) | `origin` heute | Bemerkung |
|---|---|---|---|---|
| `ingest.php`, Garmin-Uhr | `m-` / `r-` | gekoppeltes Gerät | **nicht gesetzt** → Vorgabe `watch` | richtig |
| `ingest.php`, Android-Handy | `am-` / `ar-` | gekoppeltes Handy | Vorgabe `watch` | **falsch**: ein Handy-Einsatz trägt heute die Plakette „Uhr" |
| `ingest.php`, Wear OS über das Handy | `wm-` | gekoppeltes Handy | Vorgabe `watch` | dito; das Präfix sagt, *wo* begonnen wurde, das Gerät ist das Handy (E-S4-11) |
| `einsatz_form.php` | `man-` | virtuell `manual-<konto>` | `manual` | |
| `api/import_commit.php` (CSV) | `imp-` | virtuell | `import` | |
| `api/gpx_import.php` (Einsatz oder Segment) | `imp-` | virtuell (`gpx_import_geraet()`) | `import` (Einsatz) | |
| `api/schneiden.php` | `cut-` | virtuell (`schnitt_geraet()`) | **`manual`** | wird `schnitt` |
| `backup_lib.php`, Einspielen | aus der Datei, sonst `bak-` | **keins** (`device_id` steht nicht in der Datei) | aus der Datei, sonst Ableitung über das Präfix (`edbak_origin_edited()`) | |

Die Ableitung aus dem Präfix steht heute **dreimal**: in der Migration
`2026_07_30_herkunft_bearbeitungsstatus` (SQL), in `edbak_origin_edited()`
(PHP) und als Kommentar in `api/export_data.php` (dort seit Web 3.3.2
ausdrücklich *nicht mehr* gerechnet). Der Kommentar in `backup_lib.php`
verlangt, dass Migration und Restore „die Regel nicht zweimal unterschiedlich
hinschreiben" — sie tun es aber.

**`cut-` fehlt in der Präfix-Tabelle des Vertrags** (Abschnitt 8 nennt
zehn Präfixe, `schneiden.php` vergibt ein elftes). → B-R64-01.

### 2.2 Was das Gerät heute weiß — und warum es am Einsatz nicht ankommt

`devices` trägt seit Web 12.9.0 (S6, R42) `geraet_art VARCHAR(16)`
(`uhr` | `handy` | `sonstiges` | NULL), `geraet_modell VARCHAR(191)`
(aufgelöst aus `geraetemodelle.php`, Sammelnamen bis 156 Zeichen) und
`geraet_teil VARCHAR(64)` (Rohangabe). Alle drei NULL-bar, ausschließlich
beim **Koppeln** gefüllt; von Hand angelegte, virtuelle und vor 12.9.0
gekoppelte Geräte bleiben leer — mit Absicht, begründet in der Migration.

`missions.device_id` und `rest_segments.device_id` stehen auf `ON DELETE SET
NULL`; Trennen (`pair.php`) ist der vorgesehene Normalfall bei geteilter
Uhr (Nr. 14). `device_id` steht **nicht** in der Sicherung. Gemessen am
02.09.2026 (Backlog 83): **82 von 82 Einsätzen und 95 von 95 Segmenten des
Demo-Kontos ohne Geräteverweis**, obwohl 76 davon `origin = 'watch'`
tragen. Das Muster, das trägt, gibt es im Projekt schon einmal: `day_refs`
schreibt die *öffentliche* Gerätekennung in die Datei und verknüpft beim
Einspielen neu (`backup_lib.php`, Diensttage) — 16 von 16 mit Verweis.

### 2.3 Die Konto-Sicherung heute

- **Eine** Funktion baut das innere JSON in drei Betriebsarten
  (`edbak_build()`: ganze Nutzlast, nur Kopf, Fenster). Die Spaltenliste
  `$missionSpalten` ist aufgezählt (M5-07): „Kommt künftig eine Spalte hinzu,
  die mitgesichert werden soll, ist sie hier einzutragen." Die
  Segment-Abfrage ist eine zweite, kürzere Liste. Kindlisten je Einsatz:
  `phases`, `resources`, `rea`, `crew` — immer vorhanden, leer erlaubt.
- **Nutzlast 8** (Verweise, `spur_ref`) wird geschrieben, wenn ohne Punkte
  gebaut wird; **Nutzlast 7** (Punktlisten) sonst — so entsteht die
  Demo-Fixture (`server/demo/fixture.json.gz`, `tools/referenzdatensatz/fixture/`).
  Der Rückweg unterscheidet an `$nutzlast >= 8`, welchen Spurweg er nimmt.
- **Schranke nach oben:** `api/backup_restore.php` hat `NUTZLAST_HOECHSTENS =
  8` mit dem Kommentar, eine künftige Fassung 9 dürfe nicht still im
  8er-Zweig laufen. Daneben stehen **drei fest verdrahtete 8**:
  `adminbackup_lib.php` Zeile ~600 (`'nutzlast' => 8` im Admin-Backup
  Fassung 2) und ~2108 (`$f['version'] = 8` beim Einspielen), `api/backup_eintraege_restore.php`
  Zeile ~55. `einstellungen.php` reicht `kopf.version` nur durch.
- Beim Einspielen werden Einsätze über `user_id + client_ref` wiedererkannt
  („bereits vorhanden", mit Spurkarte auch dann), Segmente ebenso.
  `client_ref` ist je Gerät eindeutig; über zwei Geräte eines Kontos kann
  derselbe Wert theoretisch zweimal vorkommen (Kommentar zu Nr. 34) — das
  Einspielen nimmt diese Grenze heute schon in Kauf.

### 2.4 Die Sperrvermerke heute

`track_cuts` (Web 12.5.0, S4/A2, E-S4-53): `owner_type` ENUM(`mission`,`rest`),
`owner_id`, `mission_id` (Ziel), `von_ts`, `bis_ts` (Sekunden seit 1970),
`n_punkte`, `erstellt_am`. Zugriff **nur** über `spur_lib.php`:
`schnitt_vermerken()`, `schnitte_lesen()` (für `ingest.php`, eine Abfrage vor
der Punktschleife), `schnitt_gesperrt()`, `schnitte_zum_einsatz()`
(Rückgängig), `schnitte_loeschen()` (nach id / Ziel / Konto),
`schnitte_loeschen_quelle()`. Das Komplett-Backup trägt die Tabelle mit (`SHOW
FULL TABLES`); die Konto-Sicherung kennt sie nicht (B-S4-10).

### 2.5 Export, Anzeige, Doku

- `api/export_data.php`: `EXPORT_ORIGIN_LABEL` = `watch→uhr, manual→manuell,
  import→import`, Rückfall `'uhr'` für Unbekanntes. Wertevorrat bewusst
  deutsch, weil ausgelieferte Exporte ihn tragen. Excel (Standard) führt die
  Herkunft **nicht** (Export-Format 2, „Bewusst nicht in Excel").
- `einsatz.php`: `ORIGIN_LABEL = { watch: 'Uhr', manual: 'manuell', import:
  'importiert' }`, Rückfall `'Uhr'`. `api/mission.php` reicht `origin` roh
  durch; **kein Client liest ihn** (weder `android/` noch `watch/`).
- `Export-Format.md` 3.6 (Herkunft/edited/manual), 3.8 Feldlisten (`herkunft`:
  `uhr | manuell | import`), 5.1 (Rückimport übernimmt `herkunft` und `edited`
  nicht). `Backup-Format.md` 2 (Feldkonventionen zu `origin`, Ableitungsregel),
  4 („Was nicht in der Datei steht"). `Handbuch.md` ~673 (Kennzeichen-Tabelle),
  ~1843 (Export), 4.1b (Schneiden, Sperre), ~2327 (Geräte). `JSON-Vertrag.md` 8.
- **„Feldkatalog"** in R64 meint die **Feldlisten** des Export-Formats (3.8)
  samt `felder.csv` — nicht `mission_fields.php`, denn Art und Modell sind
  keine Eingabefelder (E-R64-08).

### 2.6 Prüfmittel und Referenz

- Kreisläufe `tools/referenzdatensatz/vergleich/kreislauf.py` in drei Arten
  (`csv`, `edbak`, `edbak-alt`), Ausnahmelisten mit gemessenen Zahlen;
  `herkunft`/`edited`/`manual` sind dort erklärte Abweichungen des CSV-Umlaufs.
- **Die Referenzgeräte entstehen von Hand** (`einspielen.py`, Stufe `geraet`,
  über `einstellungen.php?t=geraete`) — sie tragen **keine** Art und kein
  Modell. Ohne Änderung wäre die Momentaufnahme im Referenzbestand nach
  diesem Paket durchgehend NULL, und der Kreislauf belegte die neuen Spalten
  nur trivial (→ E-R64-15).
- **Kein Schnitt im Referenzbestand** — und damit auch keiner im Demo-Konto
  (→ E-R64-16).
- **Referenz = Demo-Konto.** Die Fixture `server/demo/fixture.json.gz` entsteht
  aus dem Referenzbestand (Nutzlast 7) und wird alle 30 Minuten über
  `edbak_restore()` in das Demo-Konto eingespielt (`demo_lib.php`). Was in
  der Referenz steht, ist auf dem Produktivserver sichtbar — und was der
  Rückweg zurückbringen muss, wird dort alle 30 Minuten wieder gebraucht.
- `tools/wiederherstellungs-probe/probe.php` (vier Teile, serverseitiger
  Rundlauf `edbak_build()` → `edbak_restore()` an einer lokalen Datenbank) und
  `tools/ingestprobe/probe.php` (echtes HTTP gegen `ingest.php`) sind die
  Träger des Prüffalls Nr. 63.

### 2.7 Berührungen mit dem laufenden S4-Rest

Die Android-Pakete fassen `android/` an. Gemeinsame Dateien mit diesem Paket
sind nur die der Buchführung: `server/version.php`, `docs/CHANGELOG.md`,
`docs/Handbuch.md`, `docs/Rahmenplan.md`, `docs/Backlog.md`, das
Prüfdokument S4 (eine Verweiszeile). Der Hinweis auf überlappende Diensttage
(R57) fasst die Tagesansicht an — dieses Paket nicht. **Kein fachlicher
Konflikt**; die Reihenfolge Android → dieses Paket vermeidet auch den
Merge-Kleinkram.

---

## 3. Entscheidungen (E-R64) — aus der Konzeptsitzung mit dem Auftraggeber am 04.09.2026

| Nr. | Entscheidung | Begründung |
|---|---|---|
| **E-R64-01** | **Das Präfix entscheidet, die Geräteart ist Rückfall.** `m-`/`r-` → `watch`, `am-`/`ar-` → `android`, `wm-` → `wear`, `man-` → `manual`, `imp-` → `import`, `cut-` → `schnitt`. Unbekanntes Präfix: `geraet_art = 'handy'` → `android`, sonst `watch` (die bisherige Vorgabe). Segmente haben keine Herkunftsspalte, für sie gilt die Regel nur als Lesart des Präfixes | `am-` und `wm-` kommen vom **selben** Gerät (Handy); nur das Präfix trennt sie. Beides gleichrangig zu nehmen ginge nicht |
| **E-R64-02** | **Ein Herkunftswert je Client-App, nicht je Hersteller.** `watch` bleibt die Garmin-App (R64). Eine künftige App eines anderen Uhrenherstellers ist ein neuer Client mit eigenem Präfix im Vertrag und bekommt einen eigenen Wert; der Hersteller steht im Modell (Momentaufnahme) | Der Auftraggeber erwartet weitere Uhrenhersteller. Die Systematik trägt das, ohne `watch` umzudeuten; die CSV-Beschriftung `uhr` bleibt an `watch` gebunden, weil ausgelieferte Exporte sie tragen |
| **E-R64-03** | **`origin` wird `VARCHAR(16)` statt ENUM.** Die erlaubten Werte stehen **einmal** im Code (`HERKUNFT_WERTE`), geprüft beim Schreiben; die Ableitung steht **einmal** als Funktion (`herkunft_ableiten()`). Migration und `edbak_origin_edited()` benutzen dieselbe Regel (SQL-Spiegelung der Funktion, mit Verweis) | Dasselbe Muster wie `geraet_art` — dort ausdrücklich so begründet („Ein ENUM braucht für jede neue Geräteart eine Migration"). Ein neuer Client kostet dann drei Einträge (Vertrag, Wertliste, Beschriftungen) und keine Migration |
| **E-R64-04** | **Keine Herkunftsspalte am Ruhesegment.** Das Segment bekommt die Momentaufnahme (Art, Modell), sonst nichts; ob es von Garmin oder Handy stammt, sagt sein Präfix | Nr. 88 zählt Einsätze; ein Schnitt legt einen *neuen* Einsatz an, der die Herkunft selbst trägt (E-R64-06) |
| **E-R64-05** | **Zwei Spalten je Zeile: `geraet_art`, `geraet_modell`** — genau wie R64. Die Rohangabe `geraet_teil` bleibt am Gerät. Folge, angenommen: Ein Einsatz mit einem beim Anlegen unbekannten Modell trägt dauerhaft „unbekannt", auch wenn `devices` später nachaufgelöst würde | Das ist der Preis von Weg (b), den R64 angenommen hat; der Fall der Nachauflösung ist nach Einschätzung des Auftraggebers hypothetisch |
| **E-R64-06** | **Der Schnitt kopiert Art und Modell von der Quelle** (Segment oder Einsatz) auf den neuen Einsatz und setzt `origin = 'schnitt'`. Der Geräteverweis bleibt das virtuelle Gerät `manual-<konto>` (unverändert) | Am geschnittenen Einsatz steht damit beides: *dass* er durch einen Schnitt entstand und *welches Gerät* die Spur aufgezeichnet hat. Die Momentaufnahme hängt nicht am Geräteverweis |
| **E-R64-07** | **Sperrvermerke in der Datei als Liste `schnitte` am Ziel-Einsatz**, Quelle über `quelle_art` + `quelle_ref` (die `client_ref` der Quelle). Beim Einspielen wird die Quelle über die im selben Lauf gemerkte Zuordnung `client_ref → neue Kennung` aufgelöst; nicht auflösbar → Vermerk verworfen und gezählt; Ziel bereits vorhanden → Vermerk übersprungen und gezählt | Das ist derselbe Anker, mit dem das Einspielen Einsätze heute wiedererkennt. Alternativen verworfen: am Segment (symmetrisch, aber die Segmente sind flach und der Einsatz trägt ohnehin Kindlisten); eigene Liste im Kopf (im Fenstermodus ohne natürlichen Platz, eine Abfrage mehr) |
| **E-R64-08** | **Nutzlast 8 → 9.** Die Punktlisten-Variante bleibt **7** und trägt die neuen Felder ebenfalls (sie unterscheidet sich weiter nur durch die Punkte; Nr. 46 schafft sie in P7 ab). Der Rückweg unterscheidet weiter an `>= 8`, alle Felder dieses Pakets sind in jeder Nutzlast **optional** (fehlen → NULL bzw. keine Vermerke). `NUTZLAST_HOECHSTENS` und die drei verdrahteten 8 (2.3) ziehen nach | Die Nummer sagt einem Leser, was in der Datei stehen *kann*; beim Papierkorb wurde genauso gezählt (E-S1-07). Die Schranke nach oben ist genau für diesen Sprung gebaut |
| **E-R64-09** | **Beschriftungen.** Datenbank `watch / android / wear / manual / import / schnitt`. CSV `herkunft` (deutsch, bestehende Werte unverändert): `uhr / handy / wear / manuell / import / schnitt`. Plakette der Einsatzansicht: `Uhr / Handy / Wear / manuell / importiert / Schnitt`. **Rückfall für einen unbekannten Wert ist der Rohwert**, nicht mehr `uhr`/`Uhr` | Ein künftiger Wert ohne Beschriftung darf nicht als „Uhr" erscheinen |
| **E-R64-10** | **Export: `geraet_art` und `geraet_modell` als neue Spalten am Ende von `einsaetze.csv` und `ruhezeiten.csv`, samt `felder.csv`.** Excel (Standard) bleibt unverändert — weder Geräte noch Herkunft; GuteSeele unverändert. Der Rückimport übernimmt die Spalten nicht (wie `herkunft`: sie beschreiben das Quellkonto); erklärte Abweichung im CSV-Kreislauf mit gemessener Zahl | Auftraggeber: Geräte nicht in Excel, Herkunft nicht in Excel |
| **E-R64-11** | **Eine Migration** (Kennung nach Datum, Muster `2026_09_xx_herkunft_geraet`): ENUM → VARCHAR, vier neue Spalten, Nachfüllung aus `devices` über den noch stehenden Verweis, Umzug von `cut-` → `schnitt`, `am-` → `android`, `wm-` → `wear`. Kein `zerstoert`, kein `inhalt`: nichts fällt weg. Register in `schema.sql` und `update.php` **+1** | R64: „Bestand per Migration nachgefüllt, solange die Geräte noch stehen" |
| **E-R64-12** | **`erstellt_am` und `n_punkte` reisen mit** und werden beim Einspielen übernommen (anders als `deleted_at`, das neu entsteht): Ein Vermerk ist ein *Ereignis* der Vergangenheit, keine Frist dieser Installation | Der Zeitpunkt sagt, wann geschnitten wurde; eine Frist hängt nicht daran |
| **E-R64-13** | **Ein Paket des S4-Rests, derselbe Zweig, nach den Android-Paketen.** Kein eigener Zweig, kein eigener Merge | Auftraggeber: „Teil des S4-Rest-Laufs, der läuft schon, das Konzept wird nachgeliefert" |
| **E-R64-14** | **Eigenes Prüfdokument** `Pruefdokument-R64-Herkunft-Geraet.md` (K9); das Prüfdokument S4 bekommt eine Verweiszeile | Das Prüfdokument S4 prüft Handy und Uhr; dieses Paket hat eigene Proben und Zahlen |
| **E-R64-15** (F-R64-01) | **Die Referenzgeräte werden echt gekoppelt.** Die Stufe `geraet` in `einspielen.py` geht den S5-Weg (`pair.php` `start` mit Geräteblock → Code im Web des Kontos einlösen → `bestaetigen`) statt der Geräteseite: ein Gerät `uhr` mit einer realen Teilenummer aus `geraetemodelle.php` (aufgelöstes Modell), eines `handy` mit Hersteller/Modell wie die Handy-App sie meldet. Kennungen und Schlüssel kommen aus der Kopplungsantwort. Die Momentaufnahme steht damit an allen 82 Uhr-Einsätzen und 95 Segmenten der Referenz | Der Kreislauf ist das Prüfmittel, an dem R24 hängt; mit NULL belegte er nichts. Und das Demo-Konto zeigt damit echte Gerätenamen — heute auf der Geräteseite, später in Kachel (Nr. 88) und Dashboard (Nr. 80). Der Kopplungsweg ist in `tools/kopplungsprobe/probe.php` beschrieben; Sitzung und CSRF hat `einspielen.py` schon |
| **E-R64-16** (F-R64-02) | **Ein Schnitt im Referenzbestand.** Neue Einspielstufe `schneiden` (nach `zuordnen`, vor `nachtragen`): über `api/schneiden.php` wird aus **einem** festgelegten Ruhesegment **ein** Einsatz mit festgelegtem Zeitraum und den drei Schnitt-Phasen geschnitten; Segment, Zeitraum und Phasen stehen in den Quelldaten (`quelldaten/`, neues Objekt `schnitte`, `FORMAT.md`). Die Referenz zählt danach **88 Einsätze** (87 + 1 geschnittener), 100 Segmente (eines mit gewanderten Punkten), ein Vermerk. Die Wiederherstellungsprobe bekommt Teil 5 **nur für die Grenzfälle**, die die Referenz nicht zeigt (verwaiste Quelle, zweites Einspielen, unbrauchbare Zeiten) | Abweichend von der ersten Empfehlung (b), auf Einwand des Auftraggebers: Der Schnitt ist im **Demo-Konto sichtbar** (Plakette „Schnitt", Sperre am Segment, Handbuch 4.1b), und weil der Demo-Reset die Fixture alle 30 Minuten über den Rückweg einspielt, wird Nr. 63 **auf dem Produktivserver dauerhaft alle 30 Minuten geprüft** — ein besserer Beleg als jede Probe. Die Kosten (Quelldaten, Matrix, Ausnahmelisten neu gemessen) fallen wegen des Formatwechsels großteils ohnehin an |

---

## 4. Offene Fragen (F-R64) — **beide entschieden am 04.09.2026** (E-R64-15, E-R64-16)

Festgehalten mit Optionen und der ursprünglichen Empfehlung, damit der Weg
nachvollziehbar bleibt; F-R64-02 wurde **abweichend** von der Empfehlung
entschieden.

| Nr. | Frage | Optionen | Empfehlung → Entscheidung |
|---|---|---|---|
| **F-R64-01** | **Wie kommen Art und Modell in den Referenzbestand?** Die Stufe `geraet` legt die zwei Referenzgeräte heute von Hand an — ohne Art und Modell (2.6) | (a) Stufe `geraet` koppelt über den S5-Weg (`pair.php` `start` → Code im Web einlösen → `bestaetigen`) mit Geräteblock: ein Garmin-Gerät mit realer Teilenummer aus `geraetemodelle.php` (aufgelöstes Modell) und ein Handy; die Momentaufnahme steht dann an 82 Einsätzen und 95 Segmenten, und der edbak-Kreislauf belegt, dass sie den Umlauf übersteht · (b) Geräte bleiben von Hand angelegt, Momentaufnahme im Referenzbestand NULL; der Beleg kommt allein aus der Wiederherstellungsprobe (Teil 5) | Empfohlen **(a)** — der Kreislauf belegt mit NULL nichts; Kopplungsweg in `tools/kopplungsprobe/` beschrieben. **Entschieden (a), E-R64-15** |
| **F-R64-02** | **Wo wird Nr. 63 bewiesen?** Der Referenzbestand hat keinen Schnitt | (a) Referenzbestand um **einen** Schnitt erweitern (neue Einspielstufe `schneiden` über `api/schneiden.php`); die Zahlen der Referenz ändern sich (Einsätze +1), `quelldaten/FORMAT.md`, Abdeckungsmatrix und alle Ausnahmelisten ziehen nach · (b) Prüffall in der **Wiederherstellungsprobe** (Teil 5): Schnitt über `spur_teilen()` + `schnitt_vermerken()` an einem Probekonto, sichern, in ein frisches Konto einspielen, `track_cuts` und die Sperre gegen `ingest.php` prüfen; der Referenzbestand bleibt schnittfrei und trägt `schnitte: []` | Empfohlen **(b)** wegen der Kosten am Referenzbestand. **Entschieden (a) plus Probe für die Grenzfälle, E-R64-16** — Einwand des Auftraggebers: Referenz ist das Demo-Konto; ein Schnitt dort ist sichtbar, und der 30-Minuten-Reset prüft Nr. 63 dauerhaft |

---

## 5. Zielbild und Verhalten

### 5.1 Datenmodell

| Tabelle | Spalte | Typ | Bedeutung |
|---|---|---|---|
| `missions` | `origin` | `VARCHAR(16) NOT NULL DEFAULT 'watch'` (bisher ENUM) | Herkunft; Wertevorrat `HERKUNFT_WERTE` im Code; gesetzt beim Anlegen, nie geändert (wie bisher) |
| `missions` | `geraet_art` | `VARCHAR(16) NULL` | Momentaufnahme von `devices.geraet_art` beim Anlegen (`uhr` / `handy` / `sonstiges`); NULL = unbekannt |
| `missions` | `geraet_modell` | `VARCHAR(191) NULL` | Momentaufnahme von `devices.geraet_modell` (voller Sammelname, gekürzt wird bei der Anzeige) |
| `rest_segments` | `geraet_art` | `VARCHAR(16) NULL` | wie oben |
| `rest_segments` | `geraet_modell` | `VARCHAR(191) NULL` | wie oben |

**NULL bleibt NULL, dauerhaft** — dieselbe Begründung wie bei `devices`
(Migration `2026_09_02_geraetekennung`): Formular, Import, GPX-Import und
Demo haben nichts zu melden; „unbekannt" ist eine Sache der Anzeige.

**Die Momentaufnahme wird nie nachgezogen.** Ein Gerät, das später neu
koppelt oder anders aufgelöst wird, ändert keinen Einsatz. Das ist die
Definition von „Momentaufnahme" (E-R64-05).

**`HERKUNFT_WERTE`** = `['watch', 'android', 'wear', 'manual', 'import',
'schnitt']` — an **einer** Stelle im Code, mit Kommentar: ein Wert je
Client-App (E-R64-02), Reihenfolge ist die Reihenfolge der Beschriftungen.
Vorschlag für den Ort: `server/geraete_lib.php` (dort lebt das Gerätewissen;
`ingest.php`, `pair.php`, `schneiden.php` laden es schon oder können es
laden). Die Umsetzung entscheidet, wenn `backup_lib.php` und
`export_data.php` es dort nicht sauber laden können — dann `db.php`.

### 5.2 Die Ableitung: `herkunft_ableiten(string $clientRef, ?string $geraetArt): string`

```
Präfix von $clientRef        →  Herkunft
  'wm-'                       →  'wear'
  'am-' | 'ar-'               →  'android'
  'm-'  | 'r-'                →  'watch'
  'man-'                      →  'manual'
  'imp-'                      →  'import'
  'cut-'                      →  'schnitt'
  sonst:  $geraetArt === 'handy'  →  'android'
          sonst                   →  'watch'
```

Kein Präfix ist Anfang eines anderen (`am-` beginnt nicht mit `m-`, `man-`
auch nicht); die Reihenfolge der Prüfung ist deshalb frei — die Liste oben ist
die dokumentierte. `bak-` (Einspielen ohne eigene
Kennung) fällt in „sonst" und wird `watch` — heute schon so, und die Datei
trägt in diesem Fall ohnehin meist ihren eigenen `origin`.

**Wer sie aufruft:** `ingest.php` (beide Inserts; Geräteart aus der
`devices`-Zeile, deren `SELECT` um `geraet_art, geraet_modell` wächst),
`edbak_origin_edited()` (nur, wenn die Datei keinen gültigen `origin`
trägt), die Wiederherstellungsprobe. Formular, Import, GPX-Import und Schnitt
setzen ihren Wert weiterhin **ausdrücklich** (`manual`, `import`, `import`,
`schnitt`) — sie *sind* die Regel für ihr Präfix, ein Aufruf brächte dort
nichts als eine Umleitung.

**Die Migration spiegelt die Regel in SQL** (`LIKE 'cut-%'` usw.), mit
Verweis auf die Funktion — wie 2026_07_30 es getan hat, nur dass die Regel
jetzt eine benannte Stelle hat, auf die der Kommentar zeigen kann.

### 5.3 Anlegende Stellen nach diesem Paket

| Stelle | `origin` | `geraet_art` / `geraet_modell` |
|---|---|---|
| `ingest.php`, Einsatz | `herkunft_ableiten($clientRef, $dev['geraet_art'])`, **ausdrücklich im INSERT**; `ON DUPLICATE KEY UPDATE` fasst weder `origin` noch die Momentaufnahme an (beides ist „beim Anlegen", wie bisher `origin`) | aus `$dev` |
| `ingest.php`, Segment | — (E-R64-04) | aus `$dev` |
| `einsatz_form.php` | `manual` (unverändert) | NULL (virtuelles Gerät hat nichts) — die Spalten stehen **nicht** im INSERT; die Vorgabe NULL genügt |
| `api/import_commit.php`, `api/gpx_import.php` | `import` (unverändert) | NULL, wie oben |
| `api/schneiden.php` | **`schnitt`** (bisher `manual`) | **von der Quelle kopiert**: die Abfrage des Quellsegments (`$seg`) wächst um beide Spalten; bei Quelle `mission` entsprechend. Quelle ohne Angabe → NULL |
| `backup_lib.php`, Einspielen | aus der Datei, wenn in `HERKUNFT_WERTE`; sonst `herkunft_ableiten($ref, null)` | aus der Datei (beide Spalten gehören in die feste Spaltenliste des Einsatz-INSERT und in den Segment-INSERT, **nicht** in `$extraCols` — die kommen aus dem Formular-Feldkatalog); fehlen sie (Nutzlast ≤ 8), NULL |

`manual = 1` beim Schnitt bleibt (Schutzschalter gegen Überschreiben durch die
Uhr, unabhängig von der Herkunft — Export-Format 3.6).

### 5.4 Die Sicherung: Nutzlast 9

**Im Kern, je Einsatz** (Ausschnitt; alles Übrige wie Nutzlast 8):

```json
{
  "client_ref": "cut-66d8f1a2b3c4d",
  "origin": "schnitt", "edited": 0, "manual": 1,
  "geraet_art": "uhr",
  "geraet_modell": "fēnix 7 / fēnix 7 Solar / …",
  "schnitte": [
    { "quelle_art": "rest",
      "quelle_ref": "r-17-3391822034",
      "von_ts": 1756718400, "bis_ts": 1756722000,
      "n_punkte": 143,
      "erstellt_am": "2026-09-02 10:14:31" }
  ],
  "phases": [ … ], "resources": [ … ], "rea": [ … ], "crew": { … }
}
```

**Je Segment:** `geraet_art`, `geraet_modell` neben den bestehenden Feldern.

**Regeln:**

- `schnitte` steht **an jedem Einsatz**, leer erlaubt — wie `phases`. Ein
  Leser, der den Schlüssel nicht kennt, übergeht ihn (der Rückweg liest nur
  benannte Schlüssel).
- `quelle_art` ∈ {`rest`, `mission`}; `quelle_ref` ist die `client_ref` der
  Quelle **im selben Konto**; `von_ts`/`bis_ts` Sekunden seit 1970, ganzzahlig,
  `von_ts <= bis_ts`; `n_punkte` ≥ 1; `erstellt_am` UTC `Y-m-d H:i:s`.
- **Erzeugung** (`edbak_build()`): eine Abfrage je Fenster über `track_cuts
  WHERE mission_id IN ({IDS})` (`sql_in_bloecken`), dazu die Auflösung
  `owner_type`/`owner_id` → `client_ref` in **einer** weiteren Abfrage je Art
  (kein N+1, M5-12). Vermerke, deren Quelle nicht mehr existiert, kann es
  nicht geben (`schnitte_loeschen_quelle()` beim Löschen) — sollte doch einer
  auftauchen, wird er **nicht** geschrieben und im Kommentar als Karteileiche
  benannt. Beide Lesevorgänge gehören als Funktionen nach `spur_lib.php`
  (`schnitte_zu_einsaetzen()` o. ä.) — die Regel „hinter diese Datei" gilt
  auch für das Backup.
- **Nutzlastnummer:** `edbak_build()` schreibt `9` (ohne Punkte) bzw. `7`
  (mit Punkten); der Kopf des Containers (`nutzlast`) folgt daraus.
  `NUTZLAST_HOECHSTENS = 9`; `adminbackup_lib.php` (`'nutzlast' => 9`,
  `$f['version'] = 9`), `api/backup_eintraege_restore.php` (`= 9`) ziehen
  nach — **alle vier Stellen, sonst legt eine Fassung-9-Datei still einen
  halben Bestand an** (Kommentar in `backup_restore.php`).

### 5.5 Der Rückweg (`edbak_restore()`)

1. **Zuordnung merken.** Während der Einsatz- und der Segmentschleife wird je
   Art die Zuordnung `client_ref → Kennung im Ziel` geführt — auch für
   „bereits vorhanden" (dieselbe Begründung wie bei der Spurkarte: eine Quelle
   kann im Ziel schon stehen). Neu angelegte Einsätze werden zusätzlich als
   „neu" markiert.
2. **Dritter Durchgang, nach beiden Schleifen, in derselben Transaktion:** für
   jeden Einsatz der Datei mit nicht leerer `schnitte`-Liste:
   - Ziel **bereits vorhanden** → alle seine Vermerke **übersprungen**
     (`schnitte.uebersprungen`). Ein Restore darf keine Sperren
     wiederbeleben, die jemand im Ziel bewusst zurückgenommen hat; und für
     einen unveränderten Bestand stehen sie ohnehin.
   - Quelle nicht auflösbar (nicht in der Datei, im Ziel übersprungen, Art
     unbekannt) → Vermerk **verworfen** (`schnitte.verworfen`), Prüflisten-Meldung
     mit `quelle_ref`.
   - Werte unbrauchbar (Prüfschicht: `pruef_zahl` für die Zeiten und
     `n_punkte`, `pruef_utc_oder_sql` für `erstellt_am`) → Vermerk verworfen,
     Meldung; **die Zeile kostet nur sich selbst** (Web 8.0.0-Linie).
   - sonst `schnitt_vermerken()` über `spur_lib.php` — mit `erstellt_am` aus
     der Datei, deshalb bekommt die Funktion einen optionalen Parameter (oder
     eine Schwester); die Umsetzung entscheidet, ohne die bestehenden Aufrufer
     zu ändern. Zähler `schnitte.uebernommen`.
3. **Rückmeldung:** `$stats['schnitte'] = ['uebernommen' => n, 'uebersprungen'
   => n, 'verworfen' => n]`; `wiederherstellen.php`/`import_ui.js` zeigen eine
   Zeile „Sperrvermerke: n übernommen, n verworfen" nur, wenn die Datei
   welche trug. Der Admin-Rückweg (`adminbackup_lib.php`) reicht die Zähler
   durch wie die übrigen.
4. **Beide Rückwege** (Nutzlast 7 mit Punktlisten, 8/9 mit Verweisen) laufen
   durch dieselben Schleifen; der dritte Durchgang ist vom Spurweg
   unabhängig. Nutzlast ≤ 8 hat keine `schnitte`, also nichts zu tun.
5. **Wiederaufnahme** nach abgebrochenem Lauf: Alle Einsätze „bereits
   vorhanden" → Vermerke übersprungen. Das ist die Grenze dieses Pakets und
   steht so in `Backup-Format.md` 4: Wer nach einem Abbruch zwischen Kern und
   Spurteilen die Vermerke braucht, spielt in ein frisches Konto ein. (Die
   Spurkarte hat dasselbe Problem gelöst, weil Spuren nachgeliefert werden
   *müssen*; Vermerke sind selten und der Fall ist ein Abbruch in einem
   Abbruch.)

### 5.6 Export

- `einsaetze.csv` und `ruhezeiten.csv`: `geraet_art`, `geraet_modell` als
  letzte Spalten; leer bei NULL. `felder.csv` beschreibt beide.
- `herkunft`: Beschriftung nach E-R64-09 über eine erweiterte
  `EXPORT_ORIGIN_LABEL`; Rückfall der **Rohwert**.
- Excel (Standard), GuteSeele: **unverändert**, Export-Format 2 nennt die
  Geräteangaben in „Bewusst nicht in Excel".
- Rückimport (`export_csv_v1`): die beiden Spalten werden **nicht gelesen**.
  Vorher prüfen, dass der Kopfzeilen-Abgleich (`import_ui.js`,
  `ImportCore.findeKopfzeile`) unbekannte Spalten still übergeht (Abschnitt 9,
  Punkt 3); Export-Format 5.1 nennt sie neben `herkunft`/`edited`.
- `tools/vollstaendigkeit/` (falls es Spalten zählt) und `Export-Format.md`
  3.8 ziehen nach.

### 5.7 Anzeige

- `einsatz.php`: `ORIGIN_LABEL` nach E-R64-09, Rückfall Rohwert. Sonst
  nichts — die Einsatzansicht zeigt das Modell **nicht** (Nr. 88 und Nr. 80
  sind die Anzeigen; hier wäre es eine dritte, ungefragt).
- Alle Leser von `origin` per `grep` durchgehen (`api/day.php`, `suche.php`,
  `zeitraum.php`, `tag_spuren.php`, `gpx.php`, `gpx_lib.php`, `jobs_lib.php`,
  `trash_lib.php`): keiner darf einen der drei alten Werte als vollständige
  Liste voraussetzen. Erwartung aus der Lektüre: keiner tut es; belegen.

### 5.8 Migration

```
id      2026_09_xx_herkunft_geraet          (Datum der Umsetzung)
web     <Fassung der Umsetzung>             (K3)
label   Herkunft und Gerät je Einsatz (R64), Sperrvermerke in der Sicherung (Nr. 63)
skip    _hat_spalte($pdo, 'missions', 'geraet_art')
sql
  ALTER TABLE missions MODIFY origin VARCHAR(16) NOT NULL DEFAULT 'watch'
  ALTER TABLE missions
    ADD COLUMN geraet_art    VARCHAR(16)  NULL AFTER edited,
    ADD COLUMN geraet_modell VARCHAR(191) NULL AFTER geraet_art
  ALTER TABLE rest_segments
    ADD COLUMN geraet_art    VARCHAR(16)  NULL AFTER final,
    ADD COLUMN geraet_modell VARCHAR(191) NULL AFTER geraet_art
  UPDATE missions m JOIN devices d ON d.id = m.device_id
     SET m.geraet_art = d.geraet_art, m.geraet_modell = d.geraet_modell
  UPDATE rest_segments r JOIN devices d ON d.id = r.device_id
     SET r.geraet_art = d.geraet_art, r.geraet_modell = d.geraet_modell
  UPDATE missions SET origin = 'schnitt' WHERE client_ref LIKE 'cut-%'
  UPDATE missions SET origin = 'android' WHERE client_ref LIKE 'am-%'
  UPDATE missions SET origin = 'wear'    WHERE client_ref LIKE 'wm-%'
```

- Der ENUM-Wechsel steht **zuerst**, sonst schlagen die drei letzten UPDATEs
  fehl (unbekannter ENUM-Wert wird in MariaDB je nach `sql_mode` zu `''`
  oder zum Fehler — beides falsch).
- `AFTER`-Positionen sind Vorschlag; `schema.sql` bekommt die Spalten an
  derselben Stelle, mit Kommentar, der den Kommentar an `devices` **nicht
  wiederholt, sondern auf ihn verweist**.
- Der Kommentarblock im Migrationsrumpf (Stil der Nachbarn): warum Weg (b),
  warum VARCHAR, warum NULL bleibt, warum die Nachfüllung nur den noch
  stehenden Verweis nutzt (R47: Trennen ist Löschen), warum `cut-` bisher
  `manual` war.
- Register: `schema.sql` (`INSERT IGNORE INTO schema_migrations … 'skipped'`)
  und die Liste in `update.php` je **+1**; beim Merge gegenzählen
  (Rahmenplan 2.2).
- **`update.php` wird vom Auftraggeber ausgelöst** (R66); die Migration reiht
  sich hinter die vier wartenden ein (Rahmenplan-Kopf). Das Prüfdokument
  nennt sie als ersten Punkt der Prüfliste — wie bei S5.

### 5.9 Dokumentation (Pflegepflichten, CLAUDE.md 2 und 9)

| Dokument | Was |
|---|---|
| `docs/Backup-Format.md` | 1.2 Beispielkopf `nutzlast: 9` · 2: Absatz „Nutzlastversion 9", die neuen Felder je Einsatz und Segment, `schnitte` mit Regeln, Feldkonvention `origin` (Wertevorrat als Liste, Ableitungsregel neu, `cut-`) · 3: Import-Verhalten der Vermerke (drei Ausgänge, Wiederaufnahme-Grenze) · 4: „Sperrvermerke kommen seit Nutzlast 9 zurück; `device_id` weiterhin nicht — Art und Modell dafür als Momentaufnahme" · 5: Admin-Backup Fassung 2 trägt Nutzlast 9 |
| `docs/Export-Format.md` | 2 „Bewusst nicht in Excel" +Geräteangaben · 3.6 Wertevorrat `herkunft` (sechs Werte, Bedeutung je Client-App, `uhr` = Garmin) · 3.8 Feldlisten beider Dateien · 5.1 nicht übernommen: +`geraet_*` · 5.3 unverändert |
| `docs/JSON-Vertrag.md` | 8: Zeile `cut-` (B-R64-01); neue Spalte „Herkunft (`origin`)" in der Präfix-Tabelle; Satz, dass der Server das Präfix weiterhin nicht **prüft**, aber die Herkunft daraus **ableitet**; Regel „ein Wert je Client-App" als Hinweis für künftige Clients |
| `docs/Handbuch.md` | Kennzeichen-Tabelle (~673): sechs Zeilen · Export (~1843) · 4.1b Schneiden: die Sperre übersteht jetzt die Konto-Sicherung; Rückmeldung beim Wiederherstellen · Geräte (~2327): „wird je Einsatz und Ruhezeit festgehalten" · Sicherung/Wiederherstellen: die neue Zeile der Rückmeldung · Demo-Konto: Hinweis, dass dort ein Schnitt und gekoppelte Geräte zu sehen sind (nach AP4) |
| `docs/Technik.md` | Tabellenbeschreibung, falls sie Spalten führt; Hinweis auf `HERKUNFT_WERTE` und `herkunft_ableiten()` an der Stelle, die die Herkunft erklärt |
| `docs/CHANGELOG.md` | Eintrag nach Zählweise; **MIGRATION ERFORDERLICH** mit Kennung (Muster `version.php`) |
| `docs/Backlog.md` | 63 und 83: Nachtrag „Konzept/Umsetzung", Nr. 88 und 80 unverändert (Verweis auf die Spalten) |
| `docs/Rahmenplan.md` | Abschnitt 10 dieses Dokuments |
| `tools/wortliste/` | Bereiche a und c (sichtbare Texte, Doku) |

---

## 6. Arbeitspakete

Reihenfolge AP1 → AP2 → AP3 → AP4 → AP5. AP1–AP3 sind je ein Commit; AP4 kann
zwei werden (Werkzeug, dann Referenz). Jedes Paket endet mit `php -l` über
die geänderten Dateien und dem Push (K7).

### AP1 — Datenmodell, Ableitung, anlegende Stellen, Migration

| | |
|---|---|
| **Ziel** | Jeder ab jetzt angelegte Einsatz und jedes Segment trägt die richtige Herkunft und die Momentaufnahme; der Bestand ist nachgefüllt |
| **Dateien** | `server/schema.sql` (vier Spalten, `origin` VARCHAR, Register) · `server/update.php` (Migration 5.8, Register) · `server/geraete_lib.php` (oder `db.php`): `HERKUNFT_WERTE`, `herkunft_ableiten()` · `server/ingest.php` (Geräteabfrage +2 Spalten; beide INSERTs: `origin` ausdrücklich, Momentaufnahme) · `server/api/schneiden.php` (`origin = 'schnitt'`, Kopie von der Quelle; `$seg`-Abfrage +2) · `server/backup_lib.php` nur `edbak_origin_edited()` (Wertliste, Ableitung) — der Rest in AP2 · `server/api/export_data.php` **nur** der Rückfall (Rohwert), Beschriftungen in AP3 |
| **Schritte** | 1. Wertliste und Funktion anlegen, Kommentar nach 5.1/5.2. 2. Migration mit Rumpfkommentar; Register beide Seiten. 3. `schema.sql` nachziehen. 4. `ingest.php`, `schneiden.php`, `edbak_origin_edited()`. 5. Alle `origin`-Leser durchgehen (5.7), Befund im Umsetzungsstand notieren |
| **Abnahme** | Ingestprobe grün, dazu **drei neue Fälle**: Garmin-Kennung → `watch` + Art/Modell des Probegeräts; `am-` → `android`; `wm-` → `wear`; und je ein Segment mit Momentaufnahme · Migration auf einer Kopie des Referenzbestands: Zählungen **vorher/nachher** (`origin` je Wert, `geraet_art` NULL/gefüllt), im Prüfdokument · `update.php` `skip` greift beim zweiten Lauf · Register gegengezählt +1 · Schnitt an einem Segment mit Momentaufnahme: neuer Einsatz trägt `schnitt` und dieselben zwei Werte |
| **Berührungen** | keine mit Android-Paketen; `update.php` trägt die vier wartenden Migrationen — **nicht anfassen**, nur anhängen |

### AP2 — Sicherung: Momentaufnahme und Sperrvermerke, Nutzlast 9

| | |
|---|---|
| **Ziel** | Die Konto-Sicherung trägt Art, Modell und Sperrvermerke; das Einspielen bringt sie zurück |
| **Dateien** | `server/backup_lib.php` (`$missionSpalten` +2, Segment-Abfrage +2, `schnitte` je Einsatz in allen drei Betriebsarten, `'version' => 9`, Rückweg 5.5, Zähler) · `server/spur_lib.php` (Lesefunktion für das Backup, `schnitt_vermerken()` mit Zeitpunkt) · `server/api/backup_restore.php` (`NUTZLAST_HOECHSTENS = 9`) · `server/adminbackup_lib.php` (zwei 8 → 9, Zähler durchreichen) · `server/api/backup_eintraege_restore.php` (8 → 9) · `server/wiederherstellen.php`, `server/assets/import_ui.js` (Rückmeldungszeile) · `docs/Backup-Format.md` |
| **Schritte** | 1. Erzeugung (Kopf, Fenster, ganz) mit `schnitte`. 2. Rückweg mit Zuordnung und drittem Durchgang. 3. Vier Nummern. 4. Rückmeldung. 5. `Backup-Format.md` |
| **Abnahme** | Wiederherstellungsprobe **alle bisherigen Teile grün** plus **Teil 5 (Nr. 63, Grenzfälle)**, siehe 7.1 · Containerprobe grün (`_spur_index` weiterhin weg; Kopf trägt `nutzlast: 9`) · edbak-Kreislauf **0 unerklärt** gegen die erneuerte Referenz (AP4 — bis dahin gegen die alte mit erklärten „neu"-Abweichungen, Zahl notiert) · Eine Nutzlast-8-Datei (Referenz vom 31.08.) spielt sich weiterhin ein: Momentaufnahme NULL, keine Vermerke, keine Meldung · Eine Datei mit `version: 10` wird mit `version_neu` abgewiesen · Messstand: Backup-Zeit und Speicherspitze am 5000er-Bestand (R35) unverändert bis auf die eine Abfrage je Fenster; Zahl ins Prüfdokument |
| **Berührungen** | keine |

### AP3 — Export und Anzeige

| | |
|---|---|
| **Ziel** | CSV zeigt Geräteangaben und die sechs Herkunftswerte; die Einsatzansicht beschriftet richtig |
| **Dateien** | `server/api/export_data.php` (zwei Spalten in beiden Dateien, `felder.csv`, `EXPORT_ORIGIN_LABEL`) · `server/einsatz.php` (`ORIGIN_LABEL`) · `docs/Export-Format.md` · `docs/Handbuch.md` (Kennzeichen, Export) · ggf. `tools/vollstaendigkeit/` |
| **Schritte** | 1. Spalten und Beschriftungen. 2. Prüfen, dass der CSV-Rückimport die Spalten übergeht (9.3); Befund notieren. 3. Doku |
| **Abnahme** | CSV-Kreislauf **0 unerklärt** mit **zwei neuen Regeln** (`einsaetze.geraet_art`, `einsaetze.geraet_modell`: „wert → leer", GEMESSEN n×; `ruhezeiten/*` ist schon erklärt) · Excel (Standard) hat **dieselbe Spaltenzahl wie vorher** (31) · Bilderlauf der Einsatzansicht: Plakette „Handy" an einem `am-`-Einsatz, „Schnitt" an einem `cut-`-Einsatz (zwei Bilder) · Wortliste a und c 0/0/0 |
| **Berührungen** | `Handbuch.md` gemeinsam mit Android-Doku — Merge-Kleinkram |

### AP4 — Referenz, Kreisläufe, Fixture (E-R64-15, E-R64-16)

| | |
|---|---|
| **Ziel** | Der Referenzbestand trägt gekoppelte Geräte mit Modell und einen Schnitt; Referenz-Exporte und Fixture tragen das neue Format; alle drei Kreisläufe stehen auf 0 unerklärt; das Demo-Konto zeigt Gerätenamen und den Schnitt |
| **Dateien** | `tools/referenzdatensatz/einspielen/einspielen.py` (Stufe `geraet` über den Kopplungsweg, E-R64-15; **neue Stufe `schneiden`**, E-R64-16; `ALLE_STUFEN`) · `tools/referenzdatensatz/quelldaten/` (Geräteblöcke der zwei Geräte; neues Objekt `schnitte` mit Segment-Referenz, Zeitraum, Phasen; `FORMAT.md`; `pruefen.py` Abdeckungsmatrix: Herkunft `schnitt`, Momentaufnahme) · `tools/referenzdatensatz/generator/` (falls Payloads oder Erwartungswerte die Geräte kennen) · `tools/referenzdatensatz/referenz/*` (beide Dateien neu) · `tools/referenzdatensatz/fixture/erzeugen.php` → `server/demo/fixture.json.gz` · `tools/referenzdatensatz/vergleich/ausnahmen/*.json` (csv: zwei Regeln für `geraet_*`; die Zahlen aller einsatzweiten Regeln neu gemessen, weil ein Einsatz dazukommt; edbak-alt: Regeln „neu" für `geraet_art`, `geraet_modell`, `schnitte`, GEMESSEN; edbak: keine erwartet — der Vermerk muss unverändert zurückkommen) · `tools/referenzdatensatz/LIESMICH.md` (Zahlen: 88 Einsätze, Geräte gekoppelt, Schnitt; Stufenliste) · `tools/referenzdatensatz/browser/` nur, wenn der Schnitt dort gebraucht wird (er läuft über die API, nicht den Browser) · ggf. `vergleich/normalisieren.py` (nur, wenn `erstellt_am` des Vermerks im Umlauf nicht stabil ist — nach E-R64-12 soll es das sein; bleibt es instabil, ist das ein Fund, kein Filter) |
| **Schritte** | 1. Stufe `geraet` umbauen: Geräteseite → Kopplungsweg mit Geräteblock; Kennung und Schlüssel aus der Antwort; Zustand wie bisher. 2. Quelldaten: Geräteblöcke, Objekt `schnitte` (ein Segment, ein Zeitraum innerhalb seiner Spur, drei Phasen), `FORMAT.md`, `pruefen.py`. 3. Stufe `schneiden` (API-Aufruf wie `assets/schneiden.js`, Rückmeldung prüfen: `genommen > 0`). 4. Die drei Läufe der Referenz von vorn (LIESMICH „Die drei Läufe"), **Reihenfolge beachten** (F-P1-J). 5. Ausnahmelisten messen und eintragen. 6. Fixture erzeugen; Demo-Reset lokal fahren; im Demo-Konto nachsehen: Geräteseite mit Modellen, geschnittener Einsatz mit Plakette „Schnitt", Sperre am Segment (Tagesansicht) |
| **Abnahme** | `kreislauf.py --art edbak --frisch`, `--art csv --frisch`, `--art edbak-alt --frisch`: **0 / 0 / 0 unerklärt**, alle Zahlen ins Prüfdokument · Referenz-`.edbak` trägt `nutzlast: 9`, an **allen** Uhr-Einsätzen und Segmenten eine nicht-NULL Momentaufnahme, an **genau einem** Einsatz eine `schnitte`-Liste mit einem Vermerk · nach dem edbak-Umlauf: `track_cuts` im Zielkonto **eine** Zeile, gleiche Zeiten, gleiches `erstellt_am` · Fixture spielt sich ein (Demo-Reset), Zahlen **88 / 16 / 100**, `schnitte.uebernommen = 1` in der Rückmeldung des Resets (Protokoll) · `quelldaten/pruefen.py` und `generator/pruefen.py` grün mit Zahl |
| **Berührungen** | keine mit anderen Paketen. **Der Demo-Reset auf dem Produktivserver** übernimmt die neue Fixture mit dem Merge — ab dann steht der Schnitt im Demo-Konto; das Handbuch (AP5) darf darauf verweisen |

### AP5 — Doku, Buchführung, Prüfdokument, Rahmenplan

| | |
|---|---|
| **Ziel** | Alles, was CLAUDE.md 2 und 9 verlangen; das Prüfdokument steht; Rahmenplan Fassung 27 |
| **Dateien** | `server/version.php` · `docs/CHANGELOG.md` · `docs/JSON-Vertrag.md` 8 · `docs/Technik.md` · `docs/Backlog.md` 63/83 · `docs/Rahmenplan.md` (Abschnitt 10) · `docs/konzepte/Pruefdokument-R64-Herkunft-Geraet.md` (neu, K9) · `docs/konzepte/Pruefdokument-S4-Handy-Uhr-Client.md` (eine Verweiszeile) · dieses Konzept (Statusblock, Umsetzungsstand) |
| **Abnahme** | Wortliste a–d 0/0/0 · Vollständigkeit nach CLAUDE.md 6 · Gegenproben aus Abschnitt 10 grün · Prüfdokument nach K9-Muster mit **allen Zahlen aus AP1–AP4** und der abhakbaren Liste 7.2 |

### Umsetzungsstand (wird fortgeschrieben)

| Paket | Stand | Fassung | Zahlen | Anmerkung |
|---|---|---|---|---|
| AP1 | **erledigt** 04.09.2026 | Web **14.0.0** | Ingestprobe **39 Erwartungen, 0 nicht erfüllt** (davon 9 neu in Teil 8) · Migration an einer Installation mit 272 Einsätzen und 242 Segmenten: `origin` vorher watch 177 / manual 5 / import 90, nachher watch 162 / android 12 / wear 3 / manual 4 / import 90 / schnitt 1 · Momentaufnahme nachgefüllt: 85 von 272 Einsätzen (81 uhr, 4 handy), 108 von 242 Segmenten (100 uhr, 8 handy) — der Rest hat keinen Geräteverweis mehr · zweiter Lauf: `skip` greift · frische Installation aus `schema.sql`: vier Spalten da, `origin` VARCHAR(16) · **Register 42 = 42** · Schnitt über `api/schneiden.php`: `origin='schnitt'`, `manual=1`, Art und Modell von der Quelle geerbt | Einzelheiten unter „Umsetzungsstand AP1" |
| AP2 | offen | | | |
| AP3 | **erledigt** 04.09.2026 (vorgezogen) | Web **14.1.0** | CSV-Kreislauf **8965 Einzelvergleiche, 1023 erwartete, 5 unerklärte** (alle fünf in `felder.csv`, Folge der noch nicht erneuerten Referenz — siehe unten), 0 ungenutzte Regeln · `einsaetze.csv` **94 Spalten**, `ruhezeiten.csv` **11** · Excel (Standard) **31 Spalten, unverändert** · Plaketten im Browser belegt: Handy, Wear, Schnitt · Wortliste **0/0/0** bei 79 Regeln · Vollständigkeit 278 | Einzelheiten unter „Umsetzungsstand AP3" |
| AP4 | offen | | | |
| AP5 | **Vorarbeit erledigt**, Rest offen | — (nur `docs/`) | **P-8 nachgemessen statt gelesen:** Ein Archiv aus Web 14.1.0 (mit den zwei neuen Spalten) durch einen frischen CSV-Umlauf — Profil erkannt, „12 Einsätze, 0 Hinweise, 0 Fehler", **2011 Einzelvergleiche, 171 erwartete, 0 unerklärte** · JSON-Vertrag auf **Fassung 2.2**: `cut-` in der Präfix-Tabelle nachgetragen (**B-R64-01 damit behoben**), Spalte „Herkunft (`origin`)" je Präfix, Abschnitt zur Ableitung · **Prüfdokument angelegt** (`Pruefdokument-R64-Herkunft-Geraet.md`) mit allen Zahlen aus AP1 und AP3 und acht Prüfpunkten · Verweiszeile im Prüfdokument S4 | Offen bleiben: `version.php`-Erzählung des Abschlusses, Technik.md, Backlog 63/83, Rahmenplan Fassung 27, Prüfpunkte zu AP2/AP4 |

### Umsetzungsstand AP1 (04.09.2026, Web 14.0.0)

**Geändert:** `server/version.php` (14.0.0) · `server/schema.sql` (vier
Spalten, `origin` VARCHAR, Register) · `server/update.php` (Migration
`2026_09_04_herkunft_geraet`) · `server/geraete_lib.php` (`HERKUNFT_WERTE`,
`herkunft_ableiten()`) · `server/ingest.php` (Geräteabfrage +2 Spalten, beide
INSERTs) · `server/api/schneiden.php` (`origin='schnitt'`, Kopie von der
Quelle) · `server/backup_lib.php` (nur `edbak_origin_edited()`) ·
`server/api/export_data.php` (nur der Rückfall) · `tools/ingestprobe/probe.php`
(Teil 8, zweites Gerät) · `docs/CHANGELOG.md`.

**Ort der Wertliste: `geraete_lib.php`, wie im Konzept vorgeschlagen** (5.1).
Die Alternative `db.php` war erwogen, weil `geraete_lib.php` die erzeugte
Modelltabelle mitlädt (19 KB, 346 Zeilen) und `ingest.php` der heißeste Pfad
der Anwendung ist. Dagegen entschieden: Das Gerätewissen bleibt an einer
Stelle, und die Kosten sind mit OPcache nicht messbar. Wirkliche Verbraucher
sind ohnehin nur `ingest.php` und `backup_lib.php` — Anzeige und Export
brauchen die Liste nicht, weil ihr Rückfall der Rohwert ist.

**Befund zu 5.7 (alle `origin`-Leser durchgegangen).** Gesucht über `server/`
und `tools/`, ohne Vendor-Verzeichnis:

| Stelle | Was sie tut | Bewertung |
|---|---|---|
| `api/mission.php:291` | reicht roh durch, Rückfall `'watch'` | setzt keine geschlossene Liste voraus — **unverändert richtig** |
| `api/export_data.php` | `EXPORT_ORIGIN_LABEL` mit Rückfall | **in AP1 auf den Rohwert umgestellt**; Beschriftungen in AP3 |
| `einsatz.php:589` | `ORIGIN_LABEL[m.origin]`, sonst „Uhr" | zeigt für die drei neuen Werte weiter „Uhr" → **AP3** |
| `assets/export.js:702` | Beschreibungstext der Spalte `herkunft` in `felder.csv` | → **AP3** |
| `backup_lib.php:225` | `$missionSpalten` | → **AP2** |
| `einsatz_form.php`, `api/import_commit.php`, `api/gpx_import.php` | setzen ihren Wert ausdrücklich | **unverändert** (5.2) |
| `tools/gpxprobe/`, `tools/spurprobe/` | legen Einsätze mit ausdrücklichem `origin` an | **unverändert** |

Die im Konzept genannten Verdächtigen `api/day.php`, `suche.php`,
`zeitraum.php`, `tag_spuren.php`, `gpx.php`, `gpx_lib.php`, `jobs_lib.php`
und `trash_lib.php` nennen `origin` **überhaupt nicht**. Die Erwartung aus der
Lektüre ist damit belegt statt vermutet: **Kein Leser setzt die drei alten
Werte als vollständige Liste voraus**, und die einzigen zwei mit einer
Zuordnungstabelle haben beide einen Rückfall.

**Was AP1 bewusst offenlässt:** Zwischen AP1 und AP3 zeigt die Einsatzansicht
für `android`, `wear` und `schnitt` weiterhin „Uhr", und der CSV-Export zeigt
dort den Rohwert. Das ist kein Auslieferungsstand — der S4-Rest kommt als
Ganzes auf `main` (K7).

### Umsetzungsstand AP3 (04.09.2026, Web 14.1.0) — **vorgezogen vor AP2**

**Warum vorgezogen.** Der Auftraggeber hat nach dem Abschluss von AP1
verlangt, die dort benannte Anzeigelücke sofort zu schließen: Zwischen AP1 und
AP3 zeigte die Einsatzansicht für `android`, `wear` und `schnitt` weiterhin
„Uhr", und der CSV-Export gab den Rohwert aus. Das war für diese drei Werte
kurzzeitig schlechter als vor AP1. Die Reihenfolge ist damit **AP1 → AP3 →
AP2 → AP4 → AP5**; fachlich kostet das nichts, weil AP3 (`export_data.php`,
`einsatz.php`, `export.js`, Formatdoku) und AP2 (Sicherungsdateien) keine
gemeinsame Datei anfassen.

**Geändert:** `server/version.php` (14.1.0) · `server/api/export_data.php`
(`EXPORT_ORIGIN_LABEL` sechs Werte, beide Abfragen +2 Spalten, beide
Nutzlasten) · `server/assets/export.js` (zwei Spalten am Ende von
`einsaetze.csv` und `ruhezeiten.csv`, `herkunft`-Beschreibung) ·
`server/assets/import_profiles.js` (zwei Spalten mit `target: null`) ·
`server/einsatz.php` (`ORIGIN_LABEL` sechs Werte, Rückfall Rohwert) ·
`docs/Export-Format.md` (2, 3.6, **neu 3.6a**, 3.8, 5.1) ·
`docs/Handbuch.md` (Kennzeichen 4.2, Export 8, Geräte 10) ·
`docs/CHANGELOG.md` · `tools/referenzdatensatz/vergleich/ausnahmen/csv_umlauf.json`
(zwei Regeln) · `tools/wortliste/ausnahmen.json` (eine Regel).

**Was gemessen wurde:**

| Prüfmittel | Zahl |
|---|---|
| Einsatzansicht im Browser (Chromium, angemeldet) | drei Einsätze, drei Plaketten: **„Handy"** (257, `am-`), **„Wear"** (263, `wm-`), **„Schnitt"** (393, `cut-`). Konsolenfehler außer den bekannten Kachelabrufen: 0 |
| CSV-Export, echter Lauf über die Oberfläche | `einsaetze.csv` **94 Spalten**, die letzten beiden `geraet_art`, `geraet_modell`; `herkunft` = handy 12 / wear 3 / schnitt 1; `geraet_art` = handy 5, leer 11 · `ruhezeiten.csv` **11 Spalten**, letzte beide dieselben, `geraet_art` = handy 8, leer 34, **keine** Spalte `herkunft` · `felder.csv` beschreibt alle vier neuen Zeilen |
| **Excel (Standard), Gegenprobe** | **31 Spalten, unverändert** — dieselbe Liste wie in Export-Format.md 2 |
| CSV-Kreislauf (`kreislauf.py --art csv --frisch`) | 8965 Einzelvergleiche, **1023 erwartete**, **5 unerklärte**, 0 ungenutzte Regeln |
| Wortliste | **0 / 0 / 0** bei 79 Regeln, alle gegriffen |
| Vollständigkeit | 278 (unverändert) |

**Die fünf unerklärten Abweichungen sind benannt und bleiben stehen.** Sie
liegen sämtlich in `felder.csv`: vier neue Zeilen (`geraet_art`,
`geraet_modell` je in `einsaetze.csv` und `ruhezeiten.csv`) und eine geänderte
Beschreibung (`herkunft`). Das ist **keine Eigenschaft des Umlaufs**, sondern
die Folge davon, dass die Referenzdatei vom 24.08.2026 älter ist als der Code
— mit der Erneuerung der Referenz in AP4 verschwinden sie. Eine Ausnahmeregel
dafür wäre ein Filter, kein Ausnahmegrund (`vergleich/LIESMICH.md`). Die zwei
Regeln, die **hinzugekommen** sind, beschreiben dagegen einen echten Verlust
des Umlaufs: `einsaetze.geraet_art` und `-modell` werden vom Rückimport nicht
übernommen, gemessen 82×.

**Eine Wortlisten-Ausnahme ist neu** (`herkunft-wertevorrat-garmin`, Klasse G).
Der Wertevorrat der Herkunft muss die Garmin-App **benennen** — das ist der
Inhalt der Entscheidung E-R64-02, nicht Nachlässigkeit: `uhr` ist die
Garmin-App, und eine künftige App eines anderen Herstellers bekommt einen
eigenen Wert. Damit eine einzige Regel reicht, ist der Wortlaut an allen fünf
Stellen auf „Garmin-Uhr-App" vereinheitlicht worden. Ein Modellbeispiel
(`Venu 3S`) ist dabei **entfallen** statt ausgenommen zu werden — es trug
nichts, was der Satz „bei einer Uhr der Sammelname der Hardware" nicht besser
sagt.

### Vorgezogene AP5-Arbeit (04.09.2026, keine Fassungserhöhung)

Zwei Stücke aus AP5 sind vorgezogen worden, weil sie an AP2 nichts hängen und
beide Male ein **Befund** dahintersteht, der nicht auf das Ende warten sollte:

**`docs/JSON-Vertrag.md` auf Fassung 2.2 (B-R64-01).** Die Präfix-Tabelle in
Abschnitt 8 nannte zehn Präfixe, `api/schneiden.php` vergibt seit Web 12.5.0
ein elftes (`cut-`). Weil an den Präfixen Verhalten hängt — der Vertrag sagt
das selbst —, war das keine Auslassung, sondern eine falsche Zusage. Die
Tabelle trägt jetzt elf Zeilen und eine **vierte Spalte** mit der Herkunft je
Präfix; dazu ein Abschnitt, der sagt, dass kein Client eine Herkunft schickt
oder liest, wie der Rückfall aussieht und warum ein künftiger Client einen
**eigenen** Wert bekommt statt unter `watch` mitzulaufen. Abschnitt 0 („Stand
der Durchsetzung") sagt jetzt, dass der Server die Präfixe weiterhin nicht
**prüft**, sie seit Web 14.0.0 aber **auswertet**.

**`docs/konzepte/Pruefdokument-R64-Herkunft-Geraet.md` angelegt** (E-R64-14),
mit allen Zahlen aus AP1 und AP3, acht abhakbaren Prüfpunkten und den Grenzen
der benutzten Prüfmittel. Es trägt einen Kasten, der sagt, dass es unfertig
ist. **Erster Prüfpunkt ist `update.php`** — ohne die Migration antwortet
jeder Upload mit einem Fehler, und das sieht man an den Geräten, nicht im Web.
Das Prüfdokument S4 hat die Verweiszeile bekommen.

**Keine Fassungserhöhung**: Es ist nur `docs/` (CLAUDE.md 2 — drei Zählungen,
drei Auslieferungen).

### Probleme und wie sie gelöst wurden

| Nr. | Was auffiel | Wie es gelöst wurde |
|---|---|---|
| **P-R64-01** | In `server/version.php` stand der Absatz zu **13.3.0 vor dem zu 13.2.0** — beim Eintragen des R57-Hinweises war er an die falsche Stelle geraten. Die Datei ist eine Erzählung in Reihenfolge; das ist ihr einziger Zweck | Block verschoben, im Absatz zu 14.0.0 vermerkt |
| **P-R64-02** | Der `edited`-Rückfall in `edbak_origin_edited()` kennt `cut-` nicht: Ein geschnittener Einsatz trägt `manual = 1` und gälte danach als „bearbeitet" | **Nicht geändert, sondern begründet.** Der Zweig greift nur für Dateien der Formatversion ≤ 3, und die sind älter als der Schnitt (Web 12.5.0) — es kann keine solche Datei geben. Die Zeile einzubauen hieße, toten Code gegen einen unmöglichen Fall zu stellen; ein Kommentar an Ort und Stelle sagt das |
| **P-R64-03** | Die Ingestprobe konnte den Rückfall „unbekanntes Präfix" nicht von der Regel unterscheiden: Mit einem einzigen Uhr-Gerät ergibt beides `watch` | Die Probe legt ein **zweites Gerät** an (`handy`). Damit trennen sich die Fälle: dasselbe unbekannte Präfix ergibt am Handy `android`, an der Uhr `watch` |
| **P-R64-04** | Die Anmeldung der Schnitt-Probe scheiterte über `http://127.0.0.1:8080` **ohne jede Fehlermeldung** — das Sitzungs-Cookie trägt `secure` und kommt über blankes HTTP nicht zurück; die Anmeldung gelang (302), und die Folgeseite wies zurück auf die Anmeldung | Über die TLS-Terminierung gefahren (`lokal_starten.sh`, `https://127.0.0.1:8443`). Steht so schon in der LIESMICH des Einspielwerkzeugs — hier nur nicht gelesen |
| **P-R64-05** | Die Wortliste meldete nach AP3 **6 Treffer** (`garmin`, `venu`) — der Wertevorrat der Herkunft muss die Garmin-App benennen, das ist der Inhalt von E-R64-02 | Eine Ausnahme der Klasse G (`herkunft-wertevorrat-garmin`) statt sechs. Dafür ist der Wortlaut an allen fünf Stellen auf „Garmin-Uhr-App" vereinheitlicht worden; das Modellbeispiel `Venu 3S` ist **entfallen** statt ausgenommen — „bei einer Uhr der Sammelname der Hardware" sagt dasselbe besser |
| **B-R64-02** (Fund, kein Fehler dieses Pakets) | Beim Beleg von Prüfpunkt P-8 aufgefallen: Ein **geschnittener Einsatz kommt über den CSV-Rückimport nicht zurück**. `SCHNITT_PHASEN` vergibt nur die Phasen 3, 4 und 7 — nie die Phase 2 (Alarmierung) —, und `uhrzeit_ortszeit` ist im Importprofil `export_csv_v1` eine **Pflichtangabe**. Die Zeile erscheint als Fehler und muss von Hand nachgetragen oder übersprungen werden. Dasselbe trifft jeden Einsatz ohne Alarmierung. Gemessen: 4 von 16 Zeilen des Probe-Archivs | **Nicht behoben, sondern aufgeschrieben.** Es ist Bestandsverhalten seit Web 12.5.0 und liegt außerhalb von R64; eine Behebung hieße, die Pflichtangaben des Importprofils zu ändern, und das ist eine Entscheidung. Vermerkt im Prüfdokument bei P-8 und als **Backlog-Kandidat** für die Backlog-Runde nach dem S4-Rest. R64 macht den Fall nur sichtbar, weil es die Herkunft `schnitt` überhaupt erst benennt |
| **P-R64-06** | Der CSV-Kreislauf steht nach AP3 auf **5 unerklärt** statt 0. Die Abweichungen liegen in `felder.csv` (vier neue Zeilen, eine geänderte Beschreibung) | **Keine Ausnahmeregel geschrieben.** Sie sind keine Eigenschaft des Umlaufs, sondern die Folge einer Referenzdatei, die älter ist als der Code; mit AP4 verschwinden sie. Eine Regel dafür wäre ein Filter (`vergleich/LIESMICH.md`). Die Zahl steht stattdessen in der Beschreibung der Ausnahmeliste und hier |

---

## 7. Prüfprotokoll-Soll (für das Prüfdokument, K9)

### 7.1 Maschinell — mit Zahlen

| Prüfmittel | Soll |
|---|---|
| **edbak-Kreislauf mit dem Schnitt der Referenz (Nr. 63, Hauptfall)** | Der Umlauf Referenz → frisches Konto → Export belegt: der Vermerk kommt unverändert zurück (`quelle_ref`, Zeiten, `n_punkte`, `erstellt_am`), `track_cuts` im Zielkonto hat **eine** Zeile. Dazu, einmal von Hand oder in der Probe: `ingest.php` gegen das eingespielte Segment mit einem Punkt **innerhalb** des Zeitraums → abgewiesen; **außerhalb** → angenommen (Ingestprobe-Muster) |
| **Wiederherstellungsprobe, neuer Teil 5 „Sperrvermerke — Grenzfälle"** | An einem Probekonto: Segment mit Spur, Schnitt über `spur_teilen()` + `schnitt_vermerken()` (oder `api/schneiden.php`, wenn die Probe HTTP kann), `edbak_build()` ohne Punkte. Erwartungen: (1) zweites Einspielen derselben Datei in dasselbe Konto: `uebersprungen = 1`, `track_cuts` weiter **eine** Zeile; (2) Datei mit verwaister `quelle_ref`: `verworfen = 1`, Meldung mit der Kennung, Lauf läuft durch; (3) Datei mit `bis_ts < von_ts`: verworfen, Lauf läuft durch; (4) Datei mit `n_punkte = 0`: verworfen; (5) Nutzlast-8-Datei ohne `schnitte`: keine Zähler, keine Meldung. Dazu die Momentaufnahme: ein Einsatz mit `geraet_art`/`geraet_modell` kommt mit beiden Werten zurück; einer ohne bleibt NULL |
| **Demo-Reset** (dauerhaft, Produktivserver) | Nach dem Merge: jeder Reset spielt die Fixture mit dem Schnitt ein. Protokoll des Resets zeigt `schnitte.uebernommen = 1`; das Demo-Konto zeigt den geschnittenen Einsatz. **Das ist der Dauerbeleg für Nr. 63** — steht so im Prüfdokument und im Betreiberteil des Handbuchs |
| Wiederherstellungsprobe Teile 1–4 | unverändert grün |
| Ingestprobe | unverändert grün, **+3 Fälle** (AP1) |
| Containerprobe | grün; Kopf `nutzlast: 9`; `_spur_index` nicht in der Datei |
| Kreisläufe csv / edbak / edbak-alt | **0 / 0 / 0 unerklärt**, Zahlen je Liste; Referenz **88 / 16 / 100** |
| Messstand (R35) | Sicherung am 5000er-Bestand: Zeit und Speicherspitze vorher/nachher |
| Register | `schema.sql` und `update.php` je **+1**, gleich |
| `php -l` | 0 Fehler über alle geänderten Dateien |
| Wortliste a–d | 0 / 0 / 0 |
| Bilderlauf | Einsatzansicht mit „Handy" und „Schnitt"; Wiederherstellen-Rückmeldung mit Vermerkzeile — 0 Überlauf, 0 Konsolenfehler |

### 7.2 Von Hand — abhakbare Prüfliste (Bedienweg · Erwartung · Bedeutung eines Fehlschlags)

1. **Migration auf dem Produktivserver** (`update.php`, Auftraggeber) · läuft in einem Zug, Zählungen vorher/nachher stimmen mit der lokalen Probe überein · *Fehlschlag: alle folgenden Punkte gegenstandslos; nichts anlegen, bis sie steht.*
2. **Handy-Einsatz nach der Migration** (S24, Track-Fassung) · Einsatzansicht zeigt Plakette „Handy"; CSV-Export trägt `handy` und das Handymodell · *Fehlschlag: Ableitung oder Geräteabfrage in `ingest.php` falsch.*
3. **Wear-Einsatz** (sobald eine Wear-OS-Uhr vorliegt — Schritt 6 nennt den Gerätetest) · Plakette „Wear", Modell ist das **Handy** · *Fehlschlag: Präfixreihenfolge.*
4. **Garmin-Einsatz** · Plakette „Uhr" wie bisher, Modell der Uhr (nur, wenn die Uhr nach Web 12.9.0 gekoppelt wurde — sonst leer, und das ist richtig) · *Fehlschlag: Rückfall statt Präfix.*
5. **Schnitt aus einem Garmin-Segment** · neuer Einsatz: Plakette „Schnitt", CSV `schnitt`, Art/Modell wie das Segment · *Fehlschlag: Kopie von der Quelle fehlt.*
6. **Sichern → in ein zweites Konto einspielen** (mit dem Schnitt aus 5) · Rückmeldung „Sperrvermerke: 1 übernommen"; danach ein gepufferter Punkt der Uhr im geschnittenen Zeitraum landet **nicht** im Segment (im Zweifel mit der Ingestprobe gegen das Zielkonto) · *Fehlschlag: Nr. 63 nicht behoben.*
6a. **Demo-Konto nach dem ersten Reset mit der neuen Fixture** · Geräteseite zeigt zwei Geräte mit Modell; ein Einsatz trägt „Schnitt"; das Quellsegment zeigt in der Tagesansicht den geschnittenen Bereich · *Fehlschlag: Fixture alt, oder der Rückweg bringt den Vermerk nicht.*
7. **Alte Sicherung** (vor diesem Paket erstellt) einspielen · läuft ohne Meldung; Momentaufnahme leer; keine Vermerke · *Fehlschlag: Optionalität verletzt.*
8. **Excel (Standard)** exportieren · Spaltenzahl unverändert, keine Geräte, keine Herkunft · *Fehlschlag: E-R64-10.*

### 7.3 Nicht prüfbar aus dem Container — steht im Prüfdokument an erster Stelle

- Ob der Produktivbestand `am-`/`wm-`-Einsätze trägt und wie viele (nur die
  Zählung der Migration zeigt es).
- Ob die Produktivgeräte Art/Modell tragen (vor 12.9.0 gekoppelte nicht —
  R42 hat das benannt; die Nachfüllung bleibt dort leer, ohne Fehler).
- Der Wear-Fall (3) bis eine Wear-OS-Uhr vorliegt.

---

## 8. Fehlerfunde am Bestand (B-R64, K4)

| Nr. | Fund | Fundstelle | Umgang |
|---|---|---|---|
| B-R64-01 | **`cut-` fehlt in der Präfix-Tabelle des Vertrags.** `schneiden.php` vergibt es seit Web 12.6.0; Abschnitt 8 nennt zehn Präfixe | `docs/JSON-Vertrag.md` 8 | mit AP5 nachgetragen (steht ohnehin an) |
| B-R64-02 | **Der Rahmenplan-Kopf ist überholt:** „Android 0.7.7", „Paket E … nicht gemergt" — `main` trägt seit PR #31/#32 (04.09.2026) Android 0.10.2 mit Paket E; die Zeile in Abschnitt 6 („Merge von Paket E … vor Schritt 6") ist erledigt | `docs/Rahmenplan.md` Kopf, Abschnitt 3 Zeile 5, Abschnitt 6 | mit Fassung 27 berichtigen (Abschnitt 10); **dieselbe Sorte Fehler wie die Berichtigung in Fassung 24** — zwei Aussagen über denselben Sachverhalt |
| B-R64-03 | **Die Ableitungsregel der Herkunft steht dreimal**, obwohl `backup_lib.php` verlangt, sie „an einer einzigen Stelle" zu formulieren | `update.php` (Migration 2026_07_30), `backup_lib.php` (`edbak_origin_edited`), `api/export_data.php` (Kommentar) | behoben durch E-R64-03 (eine Funktion; die alte Migration bleibt, wie sie ist — abgeschickte Rümpfe sind unveränderlich) |
| B-R64-04 | **Android-Einsätze tragen heute die Plakette „Uhr"** (`origin` läuft in die Vorgabe) | `ingest.php` | behoben durch AP1 und die Migration |

---

## 9. Was sich aus dem Repositorium nicht ermitteln ließ

1. **Bestand auf dem Produktivserver:** Zahl der `am-`/`wm-`/`cut-`-Einsätze und
   der Geräte mit Art/Modell. Die Migration zählt; das Prüfdokument nennt die
   Zahlen.
2. **`sql_mode` der Produktivdatenbank** — entscheidet, ob ein unbekannter
   ENUM-Wert still zu `''` würde. Deshalb steht der ENUM-Wechsel zuerst
   (5.8); die Reihenfolge macht die Frage gegenstandslos.
3. **Verhalten des CSV-Rückimports bei unbekannten Spalten.** Aus
   `import_ui.js` (`findeKopfzeile`, `verarbeiteMatrix`) ist zu erwarten, dass
   nicht zugeordnete Spalten übergangen werden — **belegen** (AP3), sonst ist
   es ein Fund.
4. **Ob `tools/vollstaendigkeit/` Exportspalten zählt.** Falls ja, ziehen die
   Zahlen nach.
5. **Ob die Wiederherstellungsprobe `api/schneiden.php` per HTTP erreichen
   kann** oder direkt `spur_teilen()` ruft — beides genügt für Teil 5; die
   Umsetzung wählt den Weg, den die Probe schon kennt.
6. **Ob `demo_lib.php` die Demo-Geräte mit Art/Modell anlegt.** Für die
   Momentaufnahme an den Demo-Einsätzen ist das unerheblich (sie steht in der
   Fixture); für die Geräteseite des Demo-Kontos entscheidet es, ob dort
   Modelle stehen. Befund in AP4 notieren; fehlt es, ist es ein
   Backlog-Kandidat, kein Teil dieses Pakets.

---

## 10. Wirkung auf Rahmenplan und Backlog — Einfügeblöcke für Fassung 27

Alle Blöcke ersetzen den genannten Text **wörtlich**; Fassungsnummer 27,
Datum das der Buchführung (AP5). Zeilennummern nach `main` vom 04.09.2026.

**10.1 Kopf, Zeile 3:** `**Fassung 26 (03.09.2026)**` → `**Fassung 27 (<Datum>)**`.

**10.2 Kopf, Standzeile** (B-R64-02). Ersetzen:

> `**Stand am 03.09.2026, abends:** \`main\` trägt **Web 13.2.0** und **Uhr 3.0.0** (S5 ist gemergt, PR #28 und #29) sowie **Android 0.7.7**.`

durch:

> `**Stand am <Datum>:** \`main\` trägt **Web 13.2.0** und **Uhr 3.0.0** (S5 gemergt, PR #28 und #29) sowie **Android 0.10.2** (Paket E gemergt, PR #31 und #32, 04.09.2026).`

und den Absatz `**Paket E des S5-Zusatzes (Android-Ortung und Dienstende) ist gebaut, aber nicht gemergt** … (beide fassen \`HauptActivity.kt\`, \`strings.xml\` und das Manifest an).` **streichen**; in Abschnitt 6 die Zeile `| **Merge von Paket E** … | Schritt 5 / Schritt 6 | vor Schritt 6 |` **streichen**; in Schritt 5 den Absatz „**Paket E** … wartet auf den Merge" auf „gemergt 04.09.2026 (Android 0.10.2)" kürzen.

**10.3 Abschnitt 3, Zeile Schritt 6, Spalte „Konzept":**

> `Konzept S4, Abschnitt 13` → `Konzept S4, Abschnitt 13; **Server-Teil (R64, Nr. 63): \`docs/konzepte/Konzept-R64-Herkunft-Geraet.md\`**`

**10.4 Abschnitt 3, Text zu Schritt 6.** Ersetzen:

> `**Herkunft und Gerät je Einsatz nach R64** (Nr. 83): Momentaufnahme \`geraet_art\`/\`geraet_modell\` an \`missions\` und \`rest_segments\`, \`origin\` um \`android\`, \`wear\`, \`schnitt\` erweitert, Migration füllt den Bestand aus \`devices\` nach, Feldkatalog, Export- und Backup-Format und Kreisläufe (R24) ziehen zusammen mit Nr. 63 nach — eine Formatänderung, ein Kreislauf`

durch:

> `**Herkunft und Gerät je Einsatz nach R64 und Sperrvermerke nach Nr. 63** — eigenes Konzept \`docs/konzepte/Konzept-R64-Herkunft-Geraet.md\` (04.09.2026, nachgeliefert; Pakete AP1–AP5 nach den Android-Paketen auf demselben Zweig): Momentaufnahme \`geraet_art\`/\`geraet_modell\` an \`missions\` und \`rest_segments\`, \`origin\` als Wertliste im Code (VARCHAR, E-R64-03) mit \`android\`, \`wear\`, \`schnitt\`, Ableitung aus dem Präfix (E-R64-01), Sperrvermerke als \`schnitte\` am Ziel-Einsatz der Sicherung (E-R64-07), **Nutzlast 9** (E-R64-08), eine Migration, Referenz und Fixture neu — eine Formatänderung, ein Kreislauf`

und `· Backlog 63 (Sperrvermerke des Schnitts in die Konto-Sicherung)` **streichen** (ist im Satz davor aufgegangen).

**10.5 Abschnitt 5, Backlog-Tabelle:**

> Zeile 63, Spalte 4: `\`Backup-Format.md\`, Kreisläufe` → `Konzept R64 (E-R64-07: Liste \`schnitte\` am Ziel-Einsatz, Quelle über \`client_ref\`); Prüffall in der Wiederherstellungsprobe`
>
> Zeile 83, Spalte 4, anhängen: `; **Konzept 04.09.2026** \`Konzept-R64-Herkunft-Geraet.md\` — Präfix vor Geräteart, ein Wert je Client-App, \`origin\` VARCHAR`

**10.6 Abschnitt 6, Zuarbeit Datenschutzerklärung.** In der Zeile
`**Datenschutzerklärung um die Gerätekennung ergänzen** — seit Web 12.9.0 wird beim Koppeln Art und Modell erhoben; …` nach „erhoben" einfügen:
` **und seit dem S4-Rest (R64) je Einsatz und Ruhezeit als Momentaufnahme festgehalten**`.

**10.7 Abschnitt 7, Zeile R64, Spalte „Status":**

> `gilt; Speicherung S4-Rest, Dashboard P5, Kachel Nr. 88` → `gilt; Speicherung S4-Rest — **Konzept 04.09.2026** (\`Konzept-R64-Herkunft-Geraet.md\`): Präfix entscheidet, Geräteart Rückfall; **ein Herkunftswert je Client-App**, Hersteller im Modell; \`origin\` als Wertliste im Code statt ENUM; Dashboard P5, Kachel Nr. 88`

**10.8 Abschnitt 10, Änderungsverlauf, neue letzte Zeile:**

> `| **27** | **<Datum>** | **Konzept R64/Nr. 63 nachgeliefert** (\`docs/konzepte/Konzept-R64-Herkunft-Geraet.md\`, E-R64-01 bis -16): Präfix vor Geräteart, ein Wert je Client-App, \`origin\` VARCHAR, zwei Spalten Momentaufnahme, Schnitt kopiert von der Quelle, Sperrvermerke als \`schnitte\` am Ziel-Einsatz, Nutzlast 9, CSV ja / Excel nein, eigenes Prüfdokument, Referenz mit gekoppelten Geräten und einem Schnitt (Demo-Konto zeigt beides, Demo-Reset als Dauerbeleg für Nr. 63); Schritt 6, Backlog 63/83, R64 und Abschnitt 6 entsprechend. **Berichtigt:** Kopf und Abschnitt 6 nannten Paket E als ungemergt — \`main\` trägt seit 04.09.2026 Android 0.10.2 (PR #31/#32). |`

**10.9 `docs/Backlog.md`:** Punkt 63 und 83 je ein Absatz *„Konzept 04.09.2026
(`Konzept-R64-Herkunft-Geraet.md`): …"* mit den E-Nummern, die den Punkt
betreffen (63: E-R64-07, -08, -12, -16; 83: E-R64-01 bis -06, -09 bis -11, -15).

**Gegenproben vor dem Push (AP5):**

- `grep -c "Fassung 27" docs/Rahmenplan.md` ≥ 2 (Kopf und Verlauf);
  `grep -c "Fassung 26 (" docs/Rahmenplan.md` = 0 im Kopf (im Verlauf bleibt
  die Zeile 26).
- `grep -n "0.7.7\|nicht gemergt" docs/Rahmenplan.md` zeigt keine Aussage über
  Paket E mehr — nur Historie im Verlauf.
- `grep -c "Konzept-R64-Herkunft-Geraet" docs/Rahmenplan.md` ≥ 5 (Schritt 6
  Tabelle, Schritt 6 Text, Backlog 63, Backlog 83, R64, Verlauf).
- Backlog-Nummern eindeutig: `grep -oE '^[0-9]+\.' docs/Backlog.md | tr -d '.'
  | sort -n | uniq -d` leer.
- Register: Kennungsliste in `schema.sql` und `update.php` gleich lang, die
  neue Kennung in beiden.

---

## 11. Nicht Umfang

- **Anzeigen** der Gerätestatistik: Dashboard je Installation (Nr. 80, P5)
  und Kachel je NutzerIn (Nr. 88, Backlog-Runde, Mockup nach `Design.md` 1).
- **Herkunft am Ruhesegment** (E-R64-04); **Rohangabe am Einsatz** (E-R64-05).
- **Nachziehen der Momentaufnahme** bei späterer Kopplung oder Nachauflösung.
- **Weiches Löschen von Geräten** (Weg a aus Nr. 83) — verworfen mit R64.
- **Änderungen an `android/` und `watch/`** — beide senden schon, was
  gebraucht wird (Präfix, Geräteblock beim Koppeln).
- **Abschaffen der Nutzlast 7** (Nr. 46, P7) und der Ableitung für Dateien
  ohne `origin` (Nutzlast ≤ 3) — bleibt bis dahin.
- **Der Hinweis auf überlappende Diensttage** (R57) — anderes Paket desselben
  Schritts.
- **Datenschutzerklärung** — Zuarbeit des Auftraggebers (Abschnitt 6 des
  Rahmenplans, 10.6); dieses Paket ändert keinen Rechtstext.
- **Excel** — keine neuen Spalten (E-R64-10).
